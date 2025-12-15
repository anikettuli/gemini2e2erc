<?php
/**
 * Contact Form Email Handler (Standalone Authenticated SMTP)
 * 
 * Implements a lightweight SMTP client to send authenticated emails
 * without external dependencies (like PHPMailer/Composer).
 * This solves issues where mail() is blocked or works silently.
 */

// Load Configuration
require_once 'config.php'; // Defines $GLOBAL_EMAIL

// Load Credentials from .env
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

// SMTP Settings
$SMTP_HOST = $env_config['SMTP_HOST'] ?? 'p3plzcpnl507374.prod.phx3.secureserver.net';
$SMTP_PORT = $env_config['SMTP_PORT'] ?? 465;
$SMTP_USER = $env_config['SMTP_USER'] ?? 'noreply@2e2erc.org';
$SMTP_PASS = $env_config['SMTP_PASS'] ?? '';
$TO_EMAIL   = $GLOBAL_EMAIL; // 2e2erc1854@gmail.com

// Logging
ini_set('display_errors', 0);
ini_set('log_errors', 1);
$log_file = __DIR__ . '/email_errors.log';
ini_set('error_log', $log_file);
// Create log file if verify writable
if (!file_exists($log_file)) { @touch($log_file); @chmod($log_file, 0666); }
error_reporting(E_ALL);

// Only allow POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: index.html");
    exit;
}

// Sanitize Inputs
$name = strip_tags(trim($_POST["name"] ?? ''));
$email = filter_var(trim($_POST["email"] ?? ''), FILTER_SANITIZE_EMAIL);
$phone = strip_tags(trim($_POST["phone"] ?? ''));
$subject_raw = strip_tags(trim($_POST["subject"] ?? 'General Inquiry'));
$message = trim($_POST["message"] ?? '');
$subscribe = isset($_POST["subscribe"]) ? 'Yes' : 'No';

if (empty($name) || empty($email) || empty($message) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header("Location: index.html?status=error&reason=invalid_input#contact");
    exit;
}

// Prepare Content
$email_subject = "$subject_raw: $name";
$current_date = date('F j, Y \a\t g:i A');

$html_body = "
<html>
<body style='font-family: Arial, sans-serif; color: #333;'>
    <div style='max-width: 600px; margin: 0 auto; border: 1px solid #ddd; border-radius: 5px;'>
        <div style='background: #00447c; color: #fff; padding: 15px; text-align: center;'>
            <h2 style='margin:0;'>New Website Inquiry</h2>
        </div>
        <div style='padding: 20px;'>
            <p><strong>Name:</strong> " . htmlspecialchars($name) . "</p>
            <p><strong>Email:</strong> <a href='mailto:$email'>$email</a></p>
            <p><strong>Phone:</strong> " . htmlspecialchars($phone) . "</p>
            <p><strong>Subject:</strong> " . htmlspecialchars($subject_raw) . "</p>
            <hr>
            <h3>Message:</h3>
            <p>" . nl2br(htmlspecialchars($message)) . "</p>
        </div>
        <div style='background: #f4f4f4; padding: 10px; text-align: center; font-size: 12px; color: #777;'>
            Received: $current_date
        </div>
    </div>
</body>
</html>
";

// Send using our custom Lightweight SMTP class
$error = null;
try {
    $mail = new SimpleSMTP($SMTP_HOST, $SMTP_PORT, $SMTP_USER, $SMTP_PASS);
    
    // Send to Admin with CC to User
    $mail->send(
        $SMTP_USER,         // From
        $TO_EMAIL,          // To (Admin)
        $email_subject,     // Subject
        $html_body,         // Body
        "Lions 2-E2 ERC",   // From Name
        $email,             // Reply-To (User's email)
        $email              // CC (User's email) -> ADDED THIS
    );
    
    // (Separate confirmation email block removed)

    header("Location: index.html?status=success#contact");
    exit;

} catch (Exception $e) {
    $error = $e->getMessage();
    error_log("SMTP Error: " . $error);
    // Determine reason for URL
    $reason = 'send_failed';
    if (strpos($error, 'Authentic') !== false) $reason = 'auth_failed';
    if (strpos($error, 'connect') !== false) $reason = 'connect_failed';
    
    header("Location: index.html?status=error&reason=$reason#contact");
    exit;
}

// -----------------------------------------------------------------------------
// LIGHTWEIGHT SMTP CLASS (No dependencies)
// -----------------------------------------------------------------------------
class SimpleSMTP {
    private $host;
    private $port;
    private $user;
    private $pass;
    private $socket;
    private $timeout = 10;
    private $debug = false; // Set to true to see logs in error_log

    public function __construct($host, $port, $user, $pass) {
        $this->host = $host;
        $this->port = $port;
        $this->user = $user;
        $this->pass = $pass;
    }

    public function send($from, $to, $subject, $html_body, $from_name = '', $reply_to = '', $cc = '') {
        $scheme = ($this->port == 465) ? 'ssl://' : ''; // Helper for implicit SSL
        $connect_host = $scheme . $this->host;

        $this->log("Connecting to $connect_host:$this->port");
        $this->socket = fsockopen($connect_host, $this->port, $errno, $errstr, $this->timeout);

        if (!$this->socket) {
            throw new Exception("Connection failed: $errno $errstr");
        }

        $this->read(); // Greeting
        $this->cmd("EHLO " . $_SERVER['SERVER_NAME']);
        
        // STARTTLS if needed (port 587)
        if ($this->port == 587) {
            $this->cmd("STARTTLS");
            $crypto_method = STREAM_CRYPTO_METHOD_TLS_CLIENT;
            if (defined('STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT')) {
                $crypto_method |= STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT;
            }
            if (!stream_socket_enable_crypto($this->socket, true, $crypto_method)) {
                throw new Exception("STARTTLS failed");
            }
            $this->cmd("EHLO " . $_SERVER['SERVER_NAME']);
        }

        // AUTH
        $this->cmd("AUTH LOGIN");
        $this->cmd(base64_encode($this->user));
        $this->cmd(base64_encode($this->pass));

        // Mail Transaction
        $this->cmd("MAIL FROM: <$this->user>");
        
        // Recipient: TO
        $this->cmd("RCPT TO: <$to>");
        // Recipient: CC
        if (!empty($cc)) {
            $this->cmd("RCPT TO: <$cc>");
        }

        $this->cmd("DATA");

        // Headers
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

        // Send Content
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
        
        // Check for error codes (4xx or 5xx)
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