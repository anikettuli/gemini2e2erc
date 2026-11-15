<?php
/**
 * Shared Email Utilities and Hardened Lightweight SMTP Client
 */

date_default_timezone_set('America/Chicago');
require_once __DIR__ . '/config.php';

/**
 * Appends an operational error entry to a non-public log inside protected data/.
 */
function erc_log_mail_error(string $message): void {
    $dataDir = __DIR__ . '/data';
    if (!is_dir($dataDir)) {
        @mkdir($dataDir, 0755, true);
    }
    $logFile = $dataDir . '/email_errors.log';
    $line = '[' . date('Y-m-d H:i:s T') . '] ' . preg_replace('/[\r\n]+/', ' ', $message) . PHP_EOL;
    @file_put_contents($logFile, $line, FILE_APPEND | LOCK_EX);
    @chmod($logFile, 0600);
}

/**
 * Loads SMTP configuration from .env file with GoDaddy cPanel defaults.
 */
function get_smtp_config(): array {
    $env = erc_load_env();

    return [
        'host' => $env['SMTP_HOST'] ?? 'p3plzcpnl507374.prod.phx3.secureserver.net',
        'port' => (int)($env['SMTP_PORT'] ?? 465),
        'user' => $env['SMTP_USER'] ?? 'noreply@2e2erc.org',
        'pass' => $env['SMTP_PASS'] ?? '',
        'admin_email' => $GLOBALS['GLOBAL_EMAIL'] ?? '2e2erc1854@gmail.com',
    ];
}

/**
 * Shared Branded HTML Email Template
 */
function render_email_template(string $title, string $content_html, ?string $received_date = null): string {
    $date = $received_date ?: date('F j, Y \a\t g:i A');
    $escaped_title = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
    $escaped_date = htmlspecialchars($date, ENT_QUOTES, 'UTF-8');

    return "
    <html>
    <body style='font-family: Arial, sans-serif; color: #333; line-height: 1.6;'>
        <div style='max-width: 600px; margin: 0 auto; border: 1px solid #ddd; border-radius: 8px; overflow: hidden;'>
            <div style='background: #00447c; color: #fff; padding: 20px; text-align: center;'>
                <h2 style='margin:0; font-size: 24px;'>$escaped_title</h2>
            </div>
            <div style='padding: 30px;'>
                $content_html
            </div>
            <div style='background: #f8f9fa; padding: 15px; text-align: center; font-size: 13px; color: #777; border-top: 1px solid #eee;'>
                Lions District 2-E2 ERC<br>
                Received: $escaped_date
            </div>
        </div>
    </body>
    </html>
    ";
}

/**
 * Lightweight Authenticated SMTP Client (RFC 5321 compliant header & dot-stuffing protection)
 */
class SimpleSMTP {
    private $host;
    private $port;
    private $user;
    private $pass;
    private $socket;
    private $timeout = 10;
    private $debug = false;

    public function __construct($host, $port, $user, $pass) {
        $this->host = (string)$host;
        $this->port = (int)$port;
        $this->user = (string)$user;
        $this->pass = (string)$pass;
    }

    /**
     * Strips CR, LF, and NUL characters to prevent SMTP header/command injection.
     */
    public static function sanitizeHeader(string $value): string {
        return trim(str_replace(["\r", "\n", "\0"], ' ', $value));
    }

    /**
     * Normalizes line endings to CRLF and applies RFC 5321 section 4.5.2 dot-stuffing
     * so body lines starting with "." cannot prematurely terminate DATA and smuggle commands.
     */
    public static function formatDataBody(string $body): string {
        $normalized = str_replace(["\r\n", "\r"], "\n", $body);
        $stuffed = preg_replace('/^\./m', '..', $normalized);
        return str_replace("\n", "\r\n", $stuffed);
    }

    public function send($from, $to, $subject, $html_body, $from_name = '', $reply_to = '', $cc = '') {
        $cleanFrom = self::sanitizeHeader((string)$from);
        $cleanTo = self::sanitizeHeader((string)$to);
        $cleanSubject = self::sanitizeHeader((string)$subject);
        $cleanFromName = self::sanitizeHeader((string)$from_name);
        $cleanReplyTo = self::sanitizeHeader((string)$reply_to);
        $cleanCc = self::sanitizeHeader((string)$cc);
        $cleanEnvelopeSender = self::sanitizeHeader($this->user !== '' ? $this->user : $cleanFrom);

        if (!filter_var($cleanFrom, FILTER_VALIDATE_EMAIL) || !filter_var($cleanTo, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Invalid sender or recipient email address.');
        }
        if ($cleanReplyTo !== '' && !filter_var($cleanReplyTo, FILTER_VALIDATE_EMAIL)) {
            $cleanReplyTo = '';
        }
        if ($cleanCc !== '' && !filter_var($cleanCc, FILTER_VALIDATE_EMAIL)) {
            $cleanCc = '';
        }

        $ehloHost = preg_replace('/[^a-zA-Z0-9.-]/', '', $_SERVER['SERVER_NAME'] ?? 'localhost') ?: 'localhost';
        $scheme = ($this->port === 465) ? 'ssl://' : '';
        $connectHost = $scheme . $this->host;

        $this->log("Connecting to $connectHost:$this->port");
        $this->socket = @fsockopen($connectHost, $this->port, $errno, $errstr, $this->timeout);

        if (!$this->socket) {
            throw new Exception("Connection failed: $errno $errstr");
        }

        stream_set_timeout($this->socket, $this->timeout);

        try {
            $this->read();
            $this->cmd("EHLO $ehloHost");

            if ($this->port === 587) {
                $this->cmd('STARTTLS');
                $cryptoMethod = STREAM_CRYPTO_METHOD_TLS_CLIENT;
                if (defined('STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT')) {
                    $cryptoMethod |= STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT;
                }
                if (defined('STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT')) {
                    $cryptoMethod |= STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT;
                }
                if (!stream_socket_enable_crypto($this->socket, true, $cryptoMethod)) {
                    throw new Exception('STARTTLS negotiation failed');
                }
                $this->cmd("EHLO $ehloHost");
            }

            $this->cmd('AUTH LOGIN');
            $this->cmd(base64_encode($this->user));
            $this->cmd(base64_encode($this->pass));

            $this->cmd("MAIL FROM:<$cleanEnvelopeSender>");
            $this->cmd("RCPT TO:<$cleanTo>");
            if ($cleanCc !== '') {
                $this->cmd("RCPT TO:<$cleanCc>");
            }

            $this->cmd('DATA');

            $headers = [
                'MIME-Version: 1.0',
                'Content-Type: text/html; charset=UTF-8',
                'Date: ' . date('r'),
                'Subject: ' . $cleanSubject,
                'From: ' . ($cleanFromName !== '' ? '"' . addcslashes($cleanFromName, '"\\') . "\" <$cleanFrom>" : $cleanFrom),
                'To: ' . $cleanTo,
            ];
            if ($cleanCc !== '') {
                $headers[] = 'Cc: ' . $cleanCc;
            }
            if ($cleanReplyTo !== '') {
                $headers[] = 'Reply-To: ' . $cleanReplyTo;
            }

            $safeBody = self::formatDataBody((string)$html_body);
            $data = implode("\r\n", $headers) . "\r\n\r\n" . $safeBody . "\r\n.";
            $this->cmd($data);

            $this->cmd('QUIT');
        } finally {
            if (is_resource($this->socket)) {
                fclose($this->socket);
            }
            $this->socket = null;
        }

        return true;
    }

    private function cmd(string $command): void {
        $this->log('CLIENT: ' . (strpos($command, 'AUTH') === 0 || strlen($command) > 100 ? substr($command, 0, 10) . '...' : $command));
        if (fwrite($this->socket, $command . "\r\n") === false) {
            throw new Exception('Failed writing command to SMTP socket');
        }
        $response = $this->read();

        $code = (int)substr($response, 0, 3);
        if ($code >= 400 || $code === 0) {
            throw new Exception("SMTP Error [$code]: " . trim($response));
        }
    }

    private function read(): string {
        $response = '';
        while (($str = fgets($this->socket, 515)) !== false) {
            $response .= $str;
            if (isset($str[3]) && $str[3] === ' ') {
                break;
            }
        }
        $this->log('SERVER: ' . trim($response));
        return $response;
    }

    private function log(string $msg): void {
        if ($this->debug) {
            erc_log_mail_error("[SimpleSMTP] $msg");
        }
    }
}
