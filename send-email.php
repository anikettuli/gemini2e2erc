<?php
require_once 'config.php';

// Import PHPMailer classes at file scope
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Load credential variables from a local .env in current or parent dirs
$env_locations = [__DIR__ . '/.env', __DIR__ . '/../.env', __DIR__ . '/../../.env'];
$env_file = null;
foreach ($env_locations as $candidate) {
    if (file_exists($candidate)) {
        $env_file = $candidate;
        break;
    }
}
if ($env_file) {
    $lines = file($env_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || strpos($line, '#') === 0 || strpos($line, '=') === false) continue;
        list($key, $value) = explode('=', $line, 2);
        $k = trim($key);
        $v = trim($value);
        // Only import credential keys from .env (do not import arbitrary variables)
        if (in_array($k, ['SMTP_HOST', 'SMTP_PORT', 'SMTP_USER', 'SMTP_PASS'])) {
            $_ENV[$k] = $v;
        }
    }
}

// Debug flag: only read from actual environment variables, not from .env
$DEBUG = (getenv('DEBUG') === '1');
// Always keep display_errors off so the user sees normal behavior; enable full reporting to logs
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
error_reporting(E_ALL);

// If Composer autoload is available, require it (helps PHPMailer availability)
$composerAutoload = __DIR__ . '/vendor/autoload.php';
if (!file_exists($composerAutoload)) {
    $composerAutoload = __DIR__ . '/../vendor/autoload.php';
}
if (file_exists($composerAutoload)) {
    require_once $composerAutoload;
}

// Helper: create and send a downloadable error log (only active when DEBUG=1)
function send_error_download($title, $details = []) {
    global $DEBUG, $smtp_host, $smtp_port, $smtp_user, $from_email, $to_email;
    if (!$DEBUG) return false;

    // Build report
    $ts = date('Y-m-d H:i:s');
    $report = "Send Email Error Report\n";
    $report .= "Time: $ts\n";
    $report .= "Title: $title\n\n";

    // Request info
    $report .= "Request Method: " . ($_SERVER['REQUEST_METHOD'] ?? 'N/A') . "\n";
    $report .= "Request URI: " . ($_SERVER['REQUEST_URI'] ?? 'N/A') . "\n";
    $report .= "Remote Addr: " . ($_SERVER['REMOTE_ADDR'] ?? 'N/A') . "\n";
    $report .= "User Agent: " . ($_SERVER['HTTP_USER_AGENT'] ?? 'N/A') . "\n\n";

    // Sanitized POST data
    $report .= "POST (sanitized):\n";
    $p = [];
    foreach (['name','email','phone','subject','message'] as $k) {
        $v = $_POST[$k] ?? null;
        if ($k === 'message' && $v) $v = substr($v,0,2000); // limit size
        $p[$k] = $v;
    }
    $report .= json_encode($p, JSON_PRETTY_PRINT) . "\n\n";

    // SMTP settings (mask password)
    $report .= "SMTP:\n";
    $report .= " Host: " . ($smtp_host ?? 'N/A') . "\n";
    $report .= " Port: " . ($smtp_port ?? 'N/A') . "\n";
    $report .= " User: " . ($smtp_user ?? 'N/A') . "\n";
    $report .= " From: " . ($from_email ?? 'N/A') . "\n";
    $report .= " To: " . ($to_email ?? 'N/A') . "\n\n";

    // Extra details passed in
    if (!empty($details)) {
        $report .= "Details:\n";
        if (is_array($details) || is_object($details)) {
            $report .= print_r($details, true) . "\n";
        } else {
            $report .= (string)$details . "\n";
        }
    }

    // Last PHP error
    $last = error_get_last();
    $report .= "Last PHP error:\n" . print_r($last, true) . "\n";

    // Prepare download headers
    $fname = 'send-email-error-' . date('Ymd-His') . '.log';
    header('Content-Type: text/plain; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $fname . '"');
    header('Content-Length: ' . strlen($report));
    echo $report;
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 1. Sanitize Input
    $name = strip_tags(trim($_POST["name"]));
    $email = filter_var(trim($_POST["email"]), FILTER_SANITIZE_EMAIL);
    $phone = isset($_POST["phone"]) ? strip_tags(trim($_POST["phone"])) : '';
    $subject = isset($_POST["subject"]) ? strip_tags(trim($_POST["subject"])) : 'General Inquiry';
    $message = trim($_POST["message"]);
    $subscribe = isset($_POST["subscribe"]) ? 1 : 0;

    // 2. Validate inputs
    if (!$name || !$email || !$message) {
        if ($DEBUG) {
            $err = "Missing required fields: name/email/message";
            echo "<!doctype html><meta charset=\"utf-8\"><title>Error</title><script>alert(" . json_encode($err) . ");</script>";
            exit;
        }
        header("Location: index.html?status=error");
        exit;
    }

    // 3. cPanel SMTP Configuration (loaded from .env)
    $smtp_host = $_ENV['SMTP_HOST'] ?? 'p3plzcpnl507374.prod.phx3.secureserver.net';
    $smtp_port = $_ENV['SMTP_PORT'] ?? 465;
    $smtp_user = $_ENV['SMTP_USER'] ?? 'noreply@2e2erc.org';
    $smtp_pass = $_ENV['SMTP_PASS'] ?? '';
    $from_email = $_ENV['SMTP_USER'] ?? 'noreply@2e2erc.org';
    $to_email = $GLOBAL_EMAIL;           // 2e2erc1854@gmail.com

    // 4. Build Email Content
    $email_body = "New Contact Message\n";
    $email_body .= "========================\n\n";
    $email_body .= "Name: $name\n";
    $email_body .= "Email: $email\n";
    if ($phone) {
        $email_body .= "Phone: $phone\n";
    }
    $email_body .= "Subject: $subject\n";
    $email_body .= "Subscribe to Updates: " . ($subscribe ? "Yes" : "No") . "\n\n";
    $email_body .= "Message:\n";
    $email_body .= "$message\n";

    // 5. Check if PHPMailer is available
    if (!class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
        // Fallback to basic mail() if PHPMailer not available
        // Use a domain-approved From address and set Reply-To to the user to avoid rejection
        $headers = "From: $from_email\r\n";
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $headers .= "Reply-To: $email\r\n";
        }
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
        
        // Attempt to set envelope sender with -f to improve deliverability
        $mail_sent = mail($to_email, "New Contact Message from $name", $email_body, $headers, '-f' . $from_email);
        if ($mail_sent) {
            header("Location: index.html?status=success");
        } else {
            // Log details for investigation
            $lastError = error_get_last();
            error_log("[send-email.php] mail() failed. to=$to_email from=$from_email replyto=$email last_error=" . json_encode($lastError));

            if ($DEBUG) {
                // Start a download with the last error details and context
                $details = ['lastError' => $lastError];
                send_error_download('mail() fallback failed', $details);
                // send_error_download exits
            }
            header("Location: index.html?status=error");
        }
        exit;
    }

    // 6. Use PHPMailer for Gmail SMTP

    try {
        $mail = new PHPMailer(true);
        
        // SMTP configuration
        $mail->isSMTP();
        $mail->Host = $smtp_host;
        $mail->SMTPAuth = true;
        $mail->Username = $smtp_user;
        $mail->Password = $smtp_pass;
        // Choose encryption based on common ports
        if (intval($smtp_port) === 465) {
            if (defined('PHPMailer\\PHPMailer\\PHPMailer::ENCRYPTION_SMTPS')) {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            } else {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SSL;
            }
        } elseif (intval($smtp_port) === 587) {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        } else {
            // leave default; allow autonegotiate
        }
        $mail->Port = $smtp_port;
        $mail->SMTPAutoTLS = true;

        // Set from and to
        $mail->setFrom($from_email, '2E2 ERC');
        $mail->addAddress($to_email);  // Primary recipient
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $mail->addCC($email);          // CC the user
            $mail->addReplyTo($email, $name);
        }

        // Content
        $mail->isHTML(false);
        $mail->Subject = "New Contact Message from $name";
        $mail->Body = $email_body;

        // Send
        if ($mail->send()) {
            // Optional: Send confirmation email to user
            try {
                $user_mail = new PHPMailer(true);
                $user_mail->isSMTP();
                $user_mail->Host = $smtp_host;
                $user_mail->SMTPAuth = true;
                $user_mail->Username = $smtp_user;
                $user_mail->Password = $smtp_pass;
                if (intval($smtp_port) === 465) {
                    if (defined('PHPMailer\\PHPMailer\\PHPMailer::ENCRYPTION_SMTPS')) {
                        $user_mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                    } else {
                        $user_mail->SMTPSecure = PHPMailer::ENCRYPTION_SSL;
                    }
                } elseif (intval($smtp_port) === 587) {
                    $user_mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                }
                $user_mail->Port = $smtp_port;
                
                $user_mail->setFrom($from_email, 'Lions District 2-E2 ERC');
                if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $user_mail->addAddress($email);
                    $user_mail->isHTML(false);
                    $user_mail->Subject = "We received your message - 2E2 ERC";
                    $user_mail->Body = "Hi $name,\n\nThank you for reaching out to the Lions District 2-E2 Eyeglass Recycling Center. We've received your message and will respond within 24 hours.\n\nBest regards,\nLions District 2-E2 ERC\n\n5621 Bunker Blvd\nWatauga, TX 76148\nP.O. Box 1854\nKeller, TX 76244";
                    $user_mail->send();
                }
            } catch (Exception $e) {
                // Silently fail on confirmation email, but main email was sent
            }
            
            header("Location: index.html?status=success");
        } else {
            // Log details for debugging
            error_log('[send-email.php] PHPMailer send() returned false. ErrorInfo: ' . $mail->ErrorInfo);
            if ($DEBUG) {
                $details = ['ErrorInfo' => $mail->ErrorInfo];
                send_error_download('PHPMailer send() returned false', $details);
            }
            header("Location: index.html?status=error");
        }
    } catch (\Exception $e) {
        // Always log exceptions
        error_log('[send-email.php] Exception while sending mail: ' . $e->getMessage());
        if ($DEBUG) {
            // Provide a downloadable error log containing exception info
            $details = ['exceptionMessage' => $e->getMessage(), 'trace' => $e->getTraceAsString()];
            send_error_download('Exception while sending email', $details);
        }
        header("Location: index.html?status=error");
    }
} else {
    // Not a POST request
    header("Location: index.html");
}
?>