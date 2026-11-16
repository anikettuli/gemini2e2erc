#!/usr/bin/env node
import http from 'node:http';
import net from 'node:net';
import path from 'node:path';
import fs from 'node:fs';
import { spawn } from 'node:child_process';
import { fileURLToPath } from 'node:url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);
const redesignDir = path.resolve(__dirname, '..');
const repoRootDir = path.dirname(redesignDir);
const routerScript = path.join(redesignDir, 'router.php');

const DEFAULT_PHP_BIN =
  'C:\\Users\\anike\\AppData\\Local\\Microsoft\\WinGet\\Packages\\PHP.PHP.8.4_Microsoft.Winget.Source_8wekyb3d8bbwe\\php.exe';
const DEFAULT_PHP_EXT_DIR =
  'C:\\Users\\anike\\AppData\\Local\\Microsoft\\WinGet\\Packages\\PHP.PHP.8.4_Microsoft.Winget.Source_8wekyb3d8bbwe\\ext';

const phpBin = process.env.PHP_BIN || (fs.existsSync(DEFAULT_PHP_BIN) ? DEFAULT_PHP_BIN : 'php');
const phpExtDir = process.env.PHP_EXT_DIR || DEFAULT_PHP_EXT_DIR;

const LEGACY_BACKEND_PORT = Number(process.env.LEGACY_BACKEND_PORT || 8766);
const LEGACY_PROXY_PORT = Number(process.env.LEGACY_PROXY_PORT || 8765);
const REDESIGN_BACKEND_PORT = Number(process.env.REDESIGN_BACKEND_PORT || 8771);
const REDESIGN_PROXY_PORT = Number(process.env.REDESIGN_PROXY_PORT || 8770);

const children = [];
const proxies = [];
let shuttingDown = false;

function buildPhpArgs(backendPort, docRoot, routerFile = null) {
  const args = [];
  if (fs.existsSync(phpExtDir)) {
    args.push('-d', `extension_dir=${phpExtDir}`);
    for (const ext of ['mbstring', 'exif', 'gd', 'openssl', 'fileinfo']) {
      args.push('-d', `extension=${ext}`);
    }
  }
  args.push('-S', `127.0.0.1:${backendPort}`, '-t', docRoot);
  if (routerFile) {
    args.push(routerFile);
  }
  return args;
}

function spawnPhpServer(label, backendPort, docRoot, routerFile = null) {
  const args = buildPhpArgs(backendPort, docRoot, routerFile);
  const child = spawn(phpBin, args, {
    cwd: docRoot,
    stdio: ['ignore', 'pipe', 'pipe'],
    windowsHide: true,
  });

  child.stdout.on('data', (chunk) => {
    const text = chunk.toString('utf8').trim();
    if (text) {
      process.stdout.write(`[${label}:php] ${text}\n`);
    }
  });

  child.stderr.on('data', (chunk) => {
    const text = chunk.toString('utf8').trim();
    if (text) {
      process.stderr.write(`[${label}:php] ${text}\n`);
    }
  });

  child.on('exit', (code, signal) => {
    if (!shuttingDown) {
      process.stderr.write(
        `[${label}] PHP backend exited unexpectedly (code=${code}, signal=${signal}). Shutting down.\n`
      );
      shutdown(1);
    }
  });

  children.push(child);
  return child;
}

function waitForPort(host, port, timeoutMs = 15000) {
  const deadline = Date.now() + timeoutMs;
  return new Promise((resolve, reject) => {
    const attempt = () => {
      const socket = net.createConnection({ host, port });
      socket.once('connect', () => {
        socket.end();
        resolve();
      });
      socket.once('error', () => {
        socket.destroy();
        if (Date.now() >= deadline) {
          reject(new Error(`Timed out waiting for ${host}:${port}`));
          return;
        }
        setTimeout(attempt, 100);
      });
    };
    attempt();
  });
}

function createStreamingProxy(label, backendPort, proxyPort) {
  const server = http.createServer((req, res) => {
    const headers = { ...req.headers };
    delete headers.expect;
    delete headers.connection;
    delete headers['keep-alive'];
    const remoteIp = req.socket?.remoteAddress || '127.0.0.1';
    const existingXff = headers['x-forwarded-for'];
    headers['x-forwarded-for'] = existingXff ? `${existingXff}, ${remoteIp}` : remoteIp;
    headers['x-forwarded-proto'] = headers['x-forwarded-proto'] || 'http';
    if (req.headers.host) {
      headers['x-forwarded-host'] = headers['x-forwarded-host'] || req.headers.host;
    }
    headers['x-forwarded-port'] = headers['x-forwarded-port'] || String(proxyPort);

    const proxyReq = http.request(
      {
        hostname: '127.0.0.1',
        port: backendPort,
        method: req.method,
        path: req.url,
        headers,
      },
      (proxyRes) => {
        res.writeHead(proxyRes.statusCode || 502, proxyRes.headers);
        proxyRes.pipe(res);
      }
    );

    proxyReq.on('error', (err) => {
      process.stderr.write(`[${label}:proxy] Upstream error: ${err.message}\n`);
      if (!res.headersSent) {
        res.writeHead(502, { 'Content-Type': 'text/plain; charset=UTF-8' });
      }
      if (!res.writableEnded) {
        res.end('502 Bad Gateway\n');
      }
    });

    req.on('aborted', () => {
      proxyReq.destroy();
    });

    req.pipe(proxyReq);
  });

  proxies.push(server);

  return new Promise((resolve, reject) => {
    server.once('error', reject);
    // Bind without specifying IPv4-only host so Node binds dual-stack when available,
    // handling both http://127.0.0.1:<port> and http://localhost:<port> (::1 / 127.0.0.1).
    server.listen(proxyPort, () => {
      resolve(server);
    });
  });
}

function shutdown(exitCode = 0) {
  if (shuttingDown) return;
  shuttingDown = true;

  for (const server of proxies) {
    try {
      server.close();
      server.closeAllConnections?.();
    } catch {
      // ignore close errors
    }
  }

  for (const child of children) {
    if (child && !child.killed) {
      try {
        child.kill('SIGTERM');
      } catch {
        // ignore
      }
      if (process.platform === 'win32' && child.pid) {
        try {
          spawn('taskkill', ['/PID', String(child.pid), '/T', '/F'], {
            stdio: 'ignore',
            windowsHide: true,
          });
        } catch {
          // ignore
        }
      }
    }
  }

  setTimeout(() => process.exit(exitCode), 150).unref();
}

process.on('SIGINT', () => shutdown(0));
process.on('SIGTERM', () => shutdown(0));
process.on('uncaughtException', (err) => {
  process.stderr.write(`Fatal error: ${err.stack || err.message}\n`);
  shutdown(1);
});

async function main() {
  if (!fs.existsSync(routerScript)) {
    throw new Error(`Missing router script: ${routerScript}`);
  }

  spawnPhpServer('legacy', LEGACY_BACKEND_PORT, repoRootDir, null);
  spawnPhpServer('redesign', REDESIGN_BACKEND_PORT, redesignDir, routerScript);

  await Promise.all([
    waitForPort('127.0.0.1', LEGACY_BACKEND_PORT),
    waitForPort('127.0.0.1', REDESIGN_BACKEND_PORT),
  ]);

  await Promise.all([
    createStreamingProxy('legacy', LEGACY_BACKEND_PORT, LEGACY_PROXY_PORT),
    createStreamingProxy('redesign', REDESIGN_BACKEND_PORT, REDESIGN_PROXY_PORT),
  ]);

  process.stdout.write(
    [
      'Dual-port preview servers ready:',
      `  Legacy root (/)       -> http://127.0.0.1:${LEGACY_PROXY_PORT}/ (upstream 127.0.0.1:${LEGACY_BACKEND_PORT})`,
      `  Redesign (redesign/)  -> http://127.0.0.1:${REDESIGN_PROXY_PORT}/ (upstream 127.0.0.1:${REDESIGN_BACKEND_PORT})`,
      'Press Ctrl+C to stop both servers.',
    ].join('\n') + '\n'
  );
}

main().catch((err) => {
  process.stderr.write(`Startup failed: ${err.message}\n`);
  shutdown(1);
});
