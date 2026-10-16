<?php
/**
 * Shared Email Utilities and Lightweight Standalone SMTP Client
 * Lions District 2-E2 ERC
 */

require_once __DIR__ . '/config.php';

/**
 * Loads SMTP configuration securely
 */
function get_smtp_config() {
    global $GLOBAL_EMAIL;
    return [
        'host' => get_env_variable('SMTP_HOST', 'p3plzcpnl507374.prod.phx3.secureserver.net'),
        'port' => (int)get_env_variable('SMTP_PORT', 465),
        'user' => get_env_variable('SMTP_USER', 'noreply@2e2erc.org'),
        'pass' => get_env_variable('SMTP_PASS', ''),
        'admin_email' => $GLOBAL_EMAIL
    ];
}

/**
 * Shared Branded Email Template
 */
function render_email_template($title, $content_html, $received_date = null) {
    $date = $received_date ?: date('F j, Y \a\t g:i A');
    $escaped_title = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
    $escaped_date = htmlspecialchars($date, ENT_QUOTES, 'UTF-8');

    return "
    <!DOCTYPE html>
    <html>
    <head>
      <meta charset='UTF-8'>
      <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color: #1f2937; line-height: 1.6; margin: 0; padding: 20px; background-color: #f3f4f6; }
        .wrapper { max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 12px; overflow: hidden; border: 1px solid #e5e7eb; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
        .header { background: #004990; color: #ffffff; padding: 28px 24px; text-align: center; }
        .header h1 { margin: 0; font-size: 22px; font-weight: 700; letter-spacing: -0.02em; }
        .header p { margin: 6px 0 0; font-size: 14px; opacity: 0.9; }
        .content { padding: 32px 28px; font-size: 15px; }
        .footer { background: #f9fafb; padding: 20px; text-align: center; font-size: 13px; color: #6b7280; border-top: 1px solid #e5e7eb; }
        hr { border: 0; border-top: 1px solid #e5e7eb; margin: 24px 0; }
        strong { color: #111827; }
        a { color: #0284c7; text-decoration: none; }
      </style>
    </head>
    <body>
      <div class='wrapper'>
        <div class='header'>
          <h1>$escaped_title</h1>
          <p>Lions District 2-E2 Eyeglass Recycling Center</p>
        </div>
        <div class='content'>
          $content_html
        </div>
        <div class='footer'>
          Lions District 2-E2 ERC &bull; 5621 Bunker Blvd, Watauga, TX 76148<br>
          <span style='font-size: 12px;'>Received: $escaped_date</span>
        </div>
      </div>
    </body>
    </html>
    ";
}

/**
 * Lightweight Standalone SMTP Client (No external dependencies)
 */
class SimpleSMTP {
    private $host;
    private $port;
    private $user;
    private $pass;
    private $socket;
    private $timeout = 10;

    public function __construct($host, $port, $user, $pass) {
        $this->host = $host;
        $this->port = (int)$port;
        $this->user = $user;
        $this->pass = $pass;
    }

    public function send($from, $to, $subject, $html_body, $from_name = '', $reply_to = '', $cc = '') {
        $scheme = ($this->port === 465) ? 'ssl://' : '';
        $connect_host = $scheme . $this->host;

        $this->socket = @fsockopen($connect_host, $this->port, $errno, $errstr, $this->timeout);
        if (!$this->socket) {
            throw new Exception("SMTP Connection failed: $errno $errstr");
        }

        $heloHost = (!empty($_SERVER['SERVER_NAME']) && $_SERVER['SERVER_NAME'] !== 'localhost' && !filter_var($_SERVER['SERVER_NAME'], FILTER_VALIDATE_IP)) ? $_SERVER['SERVER_NAME'] : '2e2erc.org';
        $this->cmd("EHLO " . $heloHost);

        if ($this->port === 587) {
            $this->cmd("STARTTLS");
            $crypto_method = STREAM_CRYPTO_METHOD_TLS_CLIENT;
            if (defined('STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT')) {
                $crypto_method |= STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT;
            }
            if (!stream_socket_enable_crypto($this->socket, true, $crypto_method)) {
                throw new Exception("SMTP STARTTLS negotiation failed");
            }
            $this->cmd("EHLO " . $heloHost);
        }

        if (!empty($this->pass)) {
            $this->cmd("AUTH LOGIN");
            $this->cmd(base64_encode($this->user));
            $this->cmd(base64_encode($this->pass));
        }

        $clean_from = preg_replace('/[\r\n]/', '', $from);
        $clean_to = preg_replace('/[\r\n]/', '', $to);
        $clean_cc = preg_replace('/[\r\n]/', '', $cc);
        $clean_subject = preg_replace('/[\r\n]/', ' ', $subject);

        $this->cmd("MAIL FROM: <$clean_from>");
        $this->cmd("RCPT TO: <$clean_to>");
        if (!empty($clean_cc)) {
            $this->cmd("RCPT TO: <$clean_cc>");
        }

        $this->cmd("DATA");

        $headers = [];
        $headers[] = "MIME-Version: 1.0";
        $headers[] = "Content-Type: text/html; charset=UTF-8";
        $headers[] = "Date: " . date('r');
        $headers[] = "Subject: $clean_subject";
        $headers[] = "From: " . ($from_name ? "$from_name <$clean_from>" : $clean_from);
        $headers[] = "To: $clean_to";
        if (!empty($clean_cc)) {
            $headers[] = "Cc: $clean_cc";
        }
        if (!empty($reply_to)) {
            $clean_reply = preg_replace('/[\r\n]/', '', $reply_to);
            $headers[] = "Reply-To: $clean_reply";
        }

        $payload = implode("\r\n", $headers) . "\r\n\r\n" . $html_body . "\r\n.";
        $this->cmd($payload);

        $this->cmd("QUIT");
        @fclose($this->socket);
        return true;
    }

    private function cmd($command) {
        fwrite($this->socket, $command . "\r\n");
        $response = $this->read();
        $code = (int)substr($response, 0, 3);
        if ($code >= 400) {
            throw new Exception("SMTP Error [$code]: " . $response);
        }
    }

    private function read() {
        $response = "";
        while ($str = fgets($this->socket, 515)) {
            $response .= $str;
            if (substr($str, 3, 1) === " ") { break; }
        }
        return $response;
    }
}
?>
