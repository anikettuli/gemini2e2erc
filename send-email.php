<?php
/**
 * Contact Form Email Handler
 * 
 * Sends contact form submissions to the organization email
 * with a CC to the person who submitted the form.
 * 
 * Works with GoDaddy cPanel hosting using domain-authenticated email.
 */

require_once 'config.php';

// Load .env file for SMTP credentials (if exists)
$env_file = __DIR__ . '/.env';
if (file_exists($env_file)) {
    $lines = file($env_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || strpos($line, '#') === 0 || strpos($line, '=') === false) continue;
        list($key, $value) = explode('=', $line, 2);
        $k = trim($key);
        $v = trim($value);
        if (in_array($k, ['SMTP_HOST', 'SMTP_PORT', 'SMTP_USER', 'SMTP_PASS'])) {
            $_ENV[$k] = $v;
        }
    }
}

// Error logging configuration
ini_set('display_errors', '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);

// Only process POST requests
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: index.html");
    exit;
}

// =============================================================================
// 1. SANITIZE AND VALIDATE INPUT
// =============================================================================

$name = isset($_POST["name"]) ? strip_tags(trim($_POST["name"])) : '';
$email = isset($_POST["email"]) ? filter_var(trim($_POST["email"]), FILTER_SANITIZE_EMAIL) : '';
$phone = isset($_POST["phone"]) ? strip_tags(trim($_POST["phone"])) : '';
$subject = isset($_POST["subject"]) ? strip_tags(trim($_POST["subject"])) : 'General Inquiry';
$message = isset($_POST["message"]) ? trim($_POST["message"]) : '';
$subscribe = isset($_POST["subscribe"]) ? true : false;

// Validate required fields
if (empty($name) || empty($email) || empty($message)) {
    error_log("[send-email.php] Missing required fields - name: " . (empty($name) ? 'EMPTY' : 'OK') . 
              ", email: " . (empty($email) ? 'EMPTY' : 'OK') . 
              ", message: " . (empty($message) ? 'EMPTY' : 'OK'));
    header("Location: index.html?status=error&reason=missing_fields#contact");
    exit;
}

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    error_log("[send-email.php] Invalid email format: $email");
    header("Location: index.html?status=error&reason=invalid_email#contact");
    exit;
}

// =============================================================================
// 2. EMAIL CONFIGURATION
// =============================================================================

$from_email = $_ENV['SMTP_USER'] ?? 'noreply@2e2erc.org';
$from_name = 'Lions District 2-E2 ERC';
$to_email = $GLOBAL_EMAIL; // 2e2erc1854@gmail.com from config.php

// SMTP settings for PHPMailer (if available)
$smtp_host = $_ENV['SMTP_HOST'] ?? 'p3plzcpnl507374.prod.phx3.secureserver.net';
$smtp_port = $_ENV['SMTP_PORT'] ?? 465;
$smtp_user = $_ENV['SMTP_USER'] ?? 'noreply@2e2erc.org';
$smtp_pass = $_ENV['SMTP_PASS'] ?? '';

// =============================================================================
// 3. BUILD EMAIL CONTENT (Well-formatted HTML)
// =============================================================================

$email_subject = "New Contact Form Submission from $name";
$current_date = date('F j, Y \a\t g:i A');

// Escape HTML entities in user input
$safe_name = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
$safe_email = htmlspecialchars($email, ENT_QUOTES, 'UTF-8');
$safe_phone = htmlspecialchars($phone, ENT_QUOTES, 'UTF-8');
$safe_subject = htmlspecialchars($subject, ENT_QUOTES, 'UTF-8');
$safe_message = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');

// HTML email body
$html_body = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="margin: 0; padding: 0; font-family: Arial, Helvetica, sans-serif; background-color: #f4f4f4;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color: #f4f4f4; padding: 20px 0;">
        <tr>
            <td align="center">
                <table role="presentation" width="600" cellspacing="0" cellpadding="0" style="background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                    <!-- Header -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #1e3a5f 0%, #2d5a87 100%); padding: 30px; text-align: center;">
                            <h1 style="color: #ffffff; margin: 0; font-size: 24px;">📬 New Contact Form Submission</h1>
                            <p style="color: #a8c5e2; margin: 10px 0 0 0; font-size: 14px;">' . $current_date . '</p>
                        </td>
                    </tr>
                    
                    <!-- Content -->
                    <tr>
                        <td style="padding: 30px;">
                            <!-- Contact Details Card -->
                            <table width="100%" cellspacing="0" cellpadding="0" style="background-color: #f8f9fa; border-radius: 6px; margin-bottom: 20px;">
                                <tr>
                                    <td style="padding: 20px;">
                                        <h2 style="color: #1e3a5f; margin: 0 0 15px 0; font-size: 18px; border-bottom: 2px solid #e9a319; padding-bottom: 10px;">Contact Details</h2>
                                        
                                        <table width="100%" cellspacing="0" cellpadding="8">
                                            <tr>
                                                <td width="100" style="color: #666; font-weight: bold; vertical-align: top;">👤 Name:</td>
                                                <td style="color: #333;">' . $safe_name . '</td>
                                            </tr>
                                            <tr>
                                                <td style="color: #666; font-weight: bold; vertical-align: top;">📧 Email:</td>
                                                <td style="color: #333;"><a href="mailto:' . $safe_email . '" style="color: #2d5a87;">' . $safe_email . '</a></td>
                                            </tr>';

if (!empty($phone)) {
    $html_body .= '
                                            <tr>
                                                <td style="color: #666; font-weight: bold; vertical-align: top;">📞 Phone:</td>
                                                <td style="color: #333;"><a href="tel:' . $safe_phone . '" style="color: #2d5a87;">' . $safe_phone . '</a></td>
                                            </tr>';
}

$html_body .= '
                                            <tr>
                                                <td style="color: #666; font-weight: bold; vertical-align: top;">📋 Subject:</td>
                                                <td style="color: #333;">' . $safe_subject . '</td>
                                            </tr>
                                            <tr>
                                                <td style="color: #666; font-weight: bold; vertical-align: top;">📰 Newsletter:</td>
                                                <td style="color: #333;">' . ($subscribe ? '<span style="color: #28a745;">✓ Yes, subscribed</span>' : '<span style="color: #6c757d;">Not subscribed</span>') . '</td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                            
                            <!-- Message Card -->
                            <table width="100%" cellspacing="0" cellpadding="0" style="background-color: #fff3cd; border-left: 4px solid #e9a319; border-radius: 0 6px 6px 0;">
                                <tr>
                                    <td style="padding: 20px;">
                                        <h2 style="color: #1e3a5f; margin: 0 0 15px 0; font-size: 18px;">💬 Message</h2>
                                        <p style="color: #333; line-height: 1.6; margin: 0; white-space: pre-wrap;">' . $safe_message . '</p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    
                    <!-- Footer -->
                    <tr>
                        <td style="background-color: #1e3a5f; padding: 20px; text-align: center;">
                            <p style="color: #a8c5e2; margin: 0; font-size: 12px;">
                                This email was sent from the contact form at <a href="https://www.2e2erc.org" style="color: #e9a319;">2e2erc.org</a>
                            </p>
                            <p style="color: #a8c5e2; margin: 10px 0 0 0; font-size: 12px;">
                                Lions District 2-E2 Eyeglass Recycling Center<br>
                                5621 Bunker Blvd, Watauga, TX 76148
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>';

// Plain text version for email clients that don't support HTML
$plain_body = "NEW CONTACT FORM SUBMISSION\n";
$plain_body .= "===========================\n\n";
$plain_body .= "Received: $current_date\n\n";
$plain_body .= "CONTACT DETAILS\n";
$plain_body .= "---------------\n";
$plain_body .= "Name: $name\n";
$plain_body .= "Email: $email\n";
if (!empty($phone)) {
    $plain_body .= "Phone: $phone\n";
}
$plain_body .= "Subject: $subject\n";
$plain_body .= "Newsletter: " . ($subscribe ? "Yes" : "No") . "\n\n";
$plain_body .= "MESSAGE\n";
$plain_body .= "-------\n";
$plain_body .= "$message\n\n";
$plain_body .= "---\n";
$plain_body .= "Lions District 2-E2 Eyeglass Recycling Center\n";
$plain_body .= "5621 Bunker Blvd, Watauga, TX 76148\n";
$plain_body .= "https://www.2e2erc.org\n";

// =============================================================================
// 4. TRY PHPMAILER FIRST (IF AVAILABLE)
// =============================================================================

// Check for PHPMailer in various locations
$phpmailer_loaded = false;

$autoload_paths = [
    __DIR__ . '/vendor/autoload.php',
    __DIR__ . '/../vendor/autoload.php'
];

foreach ($autoload_paths as $autoload) {
    if (file_exists($autoload)) {
        require_once $autoload;
        $phpmailer_loaded = class_exists('PHPMailer\PHPMailer\PHPMailer');
        break;
    }
}

// Try direct include if autoload didn't work
if (!$phpmailer_loaded) {
    $phpmailer_src = __DIR__ . '/vendor/phpmailer/phpmailer/src/PHPMailer.php';
    if (file_exists($phpmailer_src)) {
        require_once $phpmailer_src;
        require_once __DIR__ . '/vendor/phpmailer/phpmailer/src/SMTP.php';
        require_once __DIR__ . '/vendor/phpmailer/phpmailer/src/Exception.php';
        $phpmailer_loaded = class_exists('PHPMailer\PHPMailer\PHPMailer');
    }
}

if ($phpmailer_loaded) {
    try {
        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
        
        // SMTP Configuration
        $mail->isSMTP();
        $mail->Host = $smtp_host;
        $mail->SMTPAuth = true;
        $mail->Username = $smtp_user;
        $mail->Password = $smtp_pass;
        $mail->Port = intval($smtp_port);
        
        // Set encryption based on port
        if (intval($smtp_port) === 465) {
            $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
        } elseif (intval($smtp_port) === 587) {
            $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        }
        
        // Email addresses
        $mail->setFrom($from_email, $from_name);
        $mail->addAddress($to_email, 'Lions District 2-E2 ERC');
        $mail->addCC($email, $name); // CC the person who submitted
        $mail->addReplyTo($email, $name);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = $email_subject;
        $mail->Body = $html_body;
        $mail->AltBody = $plain_body;
        $mail->CharSet = 'UTF-8';
        
        $mail->send();
        
        // Send confirmation email to the submitter
        try {
            $confirm_mail = new \PHPMailer\PHPMailer\PHPMailer(true);
            $confirm_mail->isSMTP();
            $confirm_mail->Host = $smtp_host;
            $confirm_mail->SMTPAuth = true;
            $confirm_mail->Username = $smtp_user;
            $confirm_mail->Password = $smtp_pass;
            $confirm_mail->Port = intval($smtp_port);
            
            if (intval($smtp_port) === 465) {
                $confirm_mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
            } elseif (intval($smtp_port) === 587) {
                $confirm_mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            }
            
            $confirm_mail->setFrom($from_email, $from_name);
            $confirm_mail->addAddress($email, $name);
            $confirm_mail->isHTML(true);
            $confirm_mail->Subject = "We received your message - Lions District 2-E2 ERC";
            $confirm_mail->CharSet = 'UTF-8';
            
            $confirm_mail->Body = '<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"></head>
<body style="font-family: Arial, sans-serif; background-color: #f4f4f4; padding: 20px;">
    <table width="600" cellspacing="0" cellpadding="0" style="background: #fff; border-radius: 8px; margin: 0 auto; overflow: hidden;">
        <tr>
            <td style="background: linear-gradient(135deg, #1e3a5f 0%, #2d5a87 100%); padding: 30px; text-align: center;">
                <h1 style="color: #fff; margin: 0;">Thank You, ' . $safe_name . '! 🦁</h1>
            </td>
        </tr>
        <tr>
            <td style="padding: 30px;">
                <p style="color: #333; font-size: 16px; line-height: 1.6;">
                    We\'ve received your message regarding "<strong>' . $safe_subject . '</strong>" and will respond within 24-48 hours.
                </p>
                <p style="color: #333; font-size: 16px; line-height: 1.6;">
                    Thank you for reaching out to the Lions District 2-E2 Eyeglass Recycling Center!
                </p>
                <hr style="border: none; border-top: 1px solid #eee; margin: 20px 0;">
                <p style="color: #666; font-size: 14px;">
                    <strong>Lions District 2-E2 ERC</strong><br>
                    5621 Bunker Blvd, Watauga, TX 76148<br>
                    P.O. Box 1854, Keller, TX 76244
                </p>
            </td>
        </tr>
    </table>
</body>
</html>';
            
            $confirm_mail->AltBody = "Hi $name,\n\nThank you for reaching out! We've received your message regarding \"$subject\" and will respond within 24-48 hours.\n\nBest regards,\nLions District 2-E2 Eyeglass Recycling Center\n5621 Bunker Blvd, Watauga, TX 76148";
            
            $confirm_mail->send();
        } catch (\Exception $e) {
            // Confirmation email failed, but main email was sent - continue
            error_log("[send-email.php] Confirmation email failed: " . $e->getMessage());
        }
        
        error_log("[send-email.php] Email sent successfully via PHPMailer to $to_email with CC to $email");
        header("Location: index.html?status=success#contact");
        exit;
        
    } catch (\Exception $e) {
        error_log("[send-email.php] PHPMailer error: " . $e->getMessage());
        // Fall through to native mail() as backup
    }
}

// =============================================================================
// 5. FALLBACK TO NATIVE PHP mail() FUNCTION
// =============================================================================

// Generate a unique boundary for multipart email
$boundary = md5(uniqid(time()));

// Build headers
$headers = array();
$headers[] = "From: $from_name <$from_email>";
$headers[] = "Reply-To: $name <$email>";
$headers[] = "CC: $email";
$headers[] = "MIME-Version: 1.0";
$headers[] = "Content-Type: multipart/alternative; boundary=\"$boundary\"";
$headers[] = "X-Mailer: PHP/" . phpversion();

$headers_string = implode("\r\n", $headers);

// Build multipart email body
$email_content = "";
$email_content .= "--$boundary\r\n";
$email_content .= "Content-Type: text/plain; charset=UTF-8\r\n";
$email_content .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
$email_content .= $plain_body . "\r\n\r\n";
$email_content .= "--$boundary\r\n";
$email_content .= "Content-Type: text/html; charset=UTF-8\r\n";
$email_content .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
$email_content .= $html_body . "\r\n\r\n";
$email_content .= "--$boundary--";

// Send the email using PHP's mail() function
$mail_sent = @mail($to_email, $email_subject, $email_content, $headers_string, "-f$from_email");

if ($mail_sent) {
    error_log("[send-email.php] Email sent successfully via mail() to $to_email with CC to $email");
    header("Location: index.html?status=success#contact");
} else {
    $last_error = error_get_last();
    error_log("[send-email.php] mail() failed. Error: " . json_encode($last_error));
    header("Location: index.html?status=error&reason=mail_failed#contact");
}
exit;
?>