<?php
require_once 'config.php';

// Load environment variables from .env file
$env_file = __DIR__ . '/.env';
if (file_exists($env_file)) {
    $lines = file($env_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            list($key, $value) = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value);
        }
    }
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
    if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
        // Fallback to basic mail() if PHPMailer not available
        $headers = "From: $email\r\n";
        $headers .= "Reply-To: $email\r\n";
        
        if (mail($to_email, "New Contact Message from $name", $email_body, $headers)) {
            header("Location: index.html?status=success");
        } else {
            header("Location: index.html?status=error");
        }
        exit;
    }

    // 6. Use PHPMailer for Gmail SMTP
    use PHPMailer\PHPMailer\PHPMailer;
    use PHPMailer\PHPMailer\Exception;

    try {
        $mail = new PHPMailer(true);
        
        // SMTP configuration
        $mail->isSMTP();
        $mail->Host = $smtp_host;
        $mail->SMTPAuth = true;
        $mail->Username = $smtp_user;
        $mail->Password = $smtp_pass;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SSL;
        $mail->Port = $smtp_port;

        // Set from and to
        $mail->setFrom($from_email, '2E2 ERC');
        $mail->addAddress($to_email);  // Primary recipient
        $mail->addCC($email);          // CC the user
        $mail->addReplyTo($email, $name);

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
                $user_mail->SMTPSecure = PHPMailer::ENCRYPTION_SSL;
                $user_mail->Port = $smtp_port;
                
                $user_mail->setFrom($from_email, 'Lions District 2-E2 ERC');
                $user_mail->addAddress($email);
                $user_mail->isHTML(false);
                $user_mail->Subject = "We received your message - 2E2 ERC";
                $user_mail->Body = "Hi $name,\n\nThank you for reaching out to the Lions District 2-E2 Eyeglass Recycling Center. We've received your message and will respond within 24 hours.\n\nBest regards,\nLions District 2-E2 ERC\n\n5621 Bunker Blvd\nWatauga, TX 76148\nP.O. Box 1854\nKeller, TX 76244";
                $user_mail->send();
            } catch (Exception $e) {
                // Silently fail on confirmation email, but main email was sent
            }
            
            header("Location: index.html?status=success");
        } else {
            header("Location: index.html?status=error");
        }
    } catch (Exception $e) {
        header("Location: index.html?status=error");
    }
} else {
    // Not a POST request
    header("Location: index.html");
}
?>