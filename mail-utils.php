<?php
/**
 * Shared Email Utilities and SMTP Client
 */

// Set timezone to Central Time
date_default_timezone_set('America/Chicago');

// Load Configuration
require_once 'config.php'; 

/**
 * Loads SMTP configuration from .env file
 */
function get_smtp_config() {
    $env_file = __DIR__ . '/.env';
    $env_config = [];
    if (file_exists($env_file)) {
        $lines = file($env_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || strpos($line, '#') === 0 || strpos($line, '=') === false) continue;
            list($key, $value) = explode('=', $line, 2);
            $env_config[trim($key)] = trim($value);
        }
    }

    return [
        'host' => $env_config['SMTP_HOST'] ?? 'p3plzcpnl507374.prod.phx3.secureserver.net',
        'port' => $env_config['SMTP_PORT'] ?? 465,
        'user' => $env_config['SMTP_USER'] ?? 'noreply@2e2erc.org',
        'pass' => $env_config['SMTP_PASS'] ?? '',
        'admin_email' => $GLOBALS['GLOBAL_EMAIL'] ?? '2e2erc1854@gmail.com'
    ];
}

/**
 * LIGHTWEIGHT SMTP CLASS (No dependencies)
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
        $this->host = $host;
        $this->port = $port;
        $this->user = $user;
        $this->pass = $pass;
    }

    public function send($from, $to, $subject, $html_body, $from_name = '', $reply_to = '', $cc = '') {
        $scheme = ($this->port == 465) ? 'ssl://' : ''; 
        $connect_host = $scheme . $this->host;

        $this->log("Connecting to $connect_host:$this->port");
        $this->socket = fsockopen($connect_host, $this->port, $errno, $errstr, $this->timeout);

        if (!$this->socket) {
            throw new Exception("Connection failed: $errno $errstr");
        }

        $this->read(); 
        $this->cmd("EHLO " . ($_SERVER['SERVER_NAME'] ?? 'localhost'));
        
        if ($this->port == 587) {
            $this->cmd("STARTTLS");
            $crypto_method = STREAM_CRYPTO_METHOD_TLS_CLIENT;
            if (defined('STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT')) {
                $crypto_method |= STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT;
            }
            if (!stream_socket_enable_crypto($this->socket, true, $crypto_method)) {
                throw new Exception("STARTTLS failed");
            }
            $this->cmd("EHLO " . ($_SERVER['SERVER_NAME'] ?? 'localhost'));
        }

        $this->cmd("AUTH LOGIN");
        $this->cmd(base64_encode($this->user));
        $this->cmd(base64_encode($this->pass));

        $this->cmd("MAIL FROM: <$this->user>");
        
        $this->cmd("RCPT TO: <$to>");
        if (!empty($cc)) {
            $this->cmd("RCPT TO: <$cc>");
        }

        $this->cmd("DATA");

        $headers = [];
        $headers[] = "MIME-Version: 1.0";
        $headers[] = "Content-Type: text/html; charset=UTF-8";
        $headers[] = "Date: " . date('r');
        $headers[] = "Subject: $subject";
        $headers[] = "From: " . ($from_name ? "$from_name <$from>" : $from);
        $headers[] = "To: $to";
        if (!empty($cc)) {
            $headers[] = "Cc: $cc";
        }
        if ($reply_to) {
            $headers[] = "Reply-To: $reply_to";
        }

        $data = implode("\r\n", $headers) . "\r\n\r\n" . $html_body . "\r\n.";
        $this->cmd($data);

        $this->cmd("QUIT");
        fclose($this->socket);
        
        return true;
    }

    private function cmd($command) {
        $this->log("CLIENT: " . (strpos($command, 'AUTH') === 0 || strlen($command) > 100 ? substr($command, 0, 10) . '...' : $command));
        fwrite($this->socket, $command . "\r\n");
        $response = $this->read();
        
        $code = substr($response, 0, 3);
        if ($code >= 400) {
            throw new Exception("SMTP Error [$code]: " . $response);
        }
    }

    private function read() {
        $response = "";
        while ($str = fgets($this->socket, 515)) {
            $response .= $str;
            if (substr($str, 3, 1) == " ") { break; }
        }
        $this->log("SERVER: " . trim($response));
        return $response;
    }
    
    private function log($msg) {
        if ($this->debug) {
            error_log("[SimpleSMTP] $msg");
        }
    }
}
?>
