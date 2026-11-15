<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once ERC_ROOT_DIR . '/vendor/phpmailer/Exception.php';
require_once ERC_ROOT_DIR . '/vendor/phpmailer/PHPMailer.php';
require_once ERC_ROOT_DIR . '/vendor/phpmailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;

/**
 * Strip CR, LF, NUL, and ASCII control characters from email headers/subjects.
 */
function erc_sanitize_header(string $value): string
{
    $clean = preg_replace('/[\x00-\x1F\x7F]+/', ' ', $value);
    return trim(is_string($clean) ? $clean : '');
}

/**
 * Append a single-line America/Chicago timestamped error entry to data/email_errors.log (0600).
 */
function erc_log_email_error(string $message): void
{
    date_default_timezone_set('America/Chicago');
    if (!is_dir(ERC_DATA_DIR)) {
        @mkdir(ERC_DATA_DIR, 0755, true);
    }

    $logPath = ERC_DATA_DIR . '/email_errors.log';
    $singleLine = erc_sanitize_header($message);
    $entry = '[' . date('Y-m-d H:i:s T') . '] ' . $singleLine . "\n";

    $fp = @fopen($logPath, 'ab');
    if ($fp !== false) {
        if (flock($fp, LOCK_EX)) {
            @fwrite($fp, $entry);
            @fflush($fp);
            flock($fp, LOCK_UN);
        }
        @fclose($fp);
        @chmod($logPath, 0600);
    }
}

/**
 * Send an HTML + plain-text email via PHPMailer v7.2.0 using GoDaddy SMTP settings.
 */
function erc_send_mail(
    string $toEmail,
    string $subject,
    string $htmlBody,
    string $textBody = '',
    string $replyToEmail = '',
    string $replyToName = ''
): bool {
    date_default_timezone_set('America/Chicago');

    $cleanTo = erc_sanitize_header($toEmail);
    $cleanSubject = erc_sanitize_header($subject);
    if ($cleanTo === '' || filter_var($cleanTo, FILTER_VALIDATE_EMAIL) === false) {
        erc_log_email_error('Invalid recipient email rejected: ' . $cleanTo);
        return false;
    }

    $env = erc_load_env();
    $host = trim((string)($env['SMTP_HOST'] ?? '')) ?: 'p3plzcpnl507374.prod.phx3.secureserver.net';
    $port = (int)(trim((string)($env['SMTP_PORT'] ?? '')) ?: 465);
    $user = trim((string)($env['SMTP_USER'] ?? '')) ?: 'noreply@2e2erc.org';
    $pass = (string)($env['SMTP_PASS'] ?? '');

    if (trim($pass) === '') {
        erc_log_email_error('SMTP skipped (SMTP_PASS unset) | to=' . $cleanTo . ' | subject=' . $cleanSubject);
        return false;
    }

    try {
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = $host;
        $mail->SMTPAuth = true;
        $mail->Username = $user;
        $mail->Password = $pass;
        $mail->Port = $port;
        $mail->Timeout = 15;

        if ($port === 465) {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        } elseif ($port === 587) {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        } else {
            $mail->SMTPSecure = $port === 25 ? '' : PHPMailer::ENCRYPTION_STARTTLS;
        }

        $mail->CharSet = PHPMailer::CHARSET_UTF8;
        $mail->setFrom($user, 'Lions District 2-E2 ERC');
        $mail->addAddress($cleanTo);

        $cleanReplyTo = erc_sanitize_header($replyToEmail);
        if ($cleanReplyTo !== '' && filter_var($cleanReplyTo, FILTER_VALIDATE_EMAIL) !== false) {
            $mail->addReplyTo($cleanReplyTo, erc_sanitize_header($replyToName));
        }

        $mail->isHTML(true);
        $mail->Subject = $cleanSubject;
        $mail->Body = $htmlBody;
        $mail->AltBody = $textBody !== '' ? $textBody : trim(strip_tags(str_replace(['<br>', '<br/>', '<br />', '</p>', '</li>'], "\n", $htmlBody)));

        return $mail->send();
    } catch (\Throwable $ex) {
        erc_log_email_error('PHPMailer failed | to=' . $cleanTo . ' | subject=' . $cleanSubject . ' | error=' . $ex->getMessage());
        return false;
    }
}

/**
 * Send volunteer confirmation email + admin notification email (non-fatal on SMTP error).
 *
 * @param array<string, mixed> $event
 * @param array<string, string> $signup
 * @return array{volunteerSent: bool, adminSent: bool}
 */
function erc_send_volunteer_emails(array $event, array $signup): array
{
    $siteConfig = erc_get_site_config();
    $adminEmail = erc_get_admin_email();

    $volName = (string)($signup['name'] ?? 'Volunteer');
    $volEmail = (string)($signup['email'] ?? '');
    $volPhone = (string)($signup['phone'] ?? '');
    $eventTitle = (string)($event['title'] ?? 'Volunteer Session');
    $eventDate = (string)($event['date'] ?? '');
    $eventTime = (string)($event['time'] ?? '');
    $eventLocation = (string)($event['location'] ?? '5621 Bunker Blvd, Watauga, TX 76148');

    $formattedDate = $eventDate;
    $dt = \DateTimeImmutable::createFromFormat('Y-m-d', $eventDate);
    if ($dt !== false) {
        $formattedDate = $dt->format('l, F j, Y');
    }

    $volSubject = 'Volunteer Registration Confirmed: ' . erc_sanitize_header($eventTitle);
    $volHtml = '<h2>Thank You for Volunteering!</h2>'
        . '<p>Hello ' . e($volName) . ',</p>'
        . '<p>Your spot is confirmed for the Lions District 2-E2 Eyeglass Recycling Center session below:</p>'
        . '<ul>'
        . '<li><strong>Session:</strong> ' . e($eventTitle) . '</li>'
        . '<li><strong>Date:</strong> ' . e($formattedDate) . '</li>'
        . '<li><strong>Time:</strong> ' . e($eventTime) . '</li>'
        . '<li><strong>Location:</strong> ' . e($eventLocation) . '</li>'
        . '</ul>'
        . '<p><strong>Arrival Note:</strong> Free parking is available on site. Follow the wooden walkway along the east side of the building to the rear entrance.</p>'
        . '<p>Questions or schedule changes? Reply to this message or reach us at '
        . e($siteConfig['phone']) . ' / ' . e($siteConfig['email']) . '.</p>';

    $volText = "Thank You for Volunteering!\n\n"
        . "Hello {$volName},\n"
        . "Your spot is confirmed:\n"
        . "- Session: {$eventTitle}\n"
        . "- Date: {$formattedDate}\n"
        . "- Time: {$eventTime}\n"
        . "- Location: {$eventLocation}\n\n"
        . "Arrival Note: Free parking is available. Follow the wooden walkway along the east side of the building to the rear entrance.\n"
        . "Questions? Contact {$siteConfig['phone']} or {$siteConfig['email']}.\n";

    $volunteerSent = erc_send_mail($volEmail, $volSubject, $volHtml, $volText, $siteConfig['email'], 'Lions District 2-E2 ERC');

    $adminSubject = 'New Volunteer Signup: ' . erc_sanitize_header($eventTitle) . ' (' . erc_sanitize_header($eventDate) . ')';
    $adminHtml = '<h2>New Volunteer Registration</h2>'
        . '<ul>'
        . '<li><strong>Session:</strong> ' . e($eventTitle) . ' (' . e($formattedDate) . ', ' . e($eventTime) . ')</li>'
        . '<li><strong>Volunteer:</strong> ' . e($volName) . '</li>'
        . '<li><strong>Email:</strong> ' . e($volEmail) . '</li>'
        . '<li><strong>Phone:</strong> ' . e($volPhone !== '' ? $volPhone : 'Not provided') . '</li>'
        . '<li><strong>Current Count:</strong> ' . e((string)($event['people'] ?? '')) . ' / ' . e((string)($event['maxPeople'] ?? '')) . '</li>'
        . '</ul>';

    $adminText = "New Volunteer Registration\n"
        . "Session: {$eventTitle} ({$formattedDate}, {$eventTime})\n"
        . "Volunteer: {$volName}\n"
        . "Email: {$volEmail}\n"
        . 'Phone: ' . ($volPhone !== '' ? $volPhone : 'Not provided') . "\n"
        . 'Count: ' . (string)($event['people'] ?? '') . ' / ' . (string)($event['maxPeople'] ?? '') . "\n";

    $adminSent = erc_send_mail($adminEmail, $adminSubject, $adminHtml, $adminText, $volEmail, $volName);

    return [
        'volunteerSent' => $volunteerSent,
        'adminSent'     => $adminSent,
    ];
}

/**
 * Send inbound contact form submission exclusively to the configured admin email.
 *
 * @param array{name: string, email: string, phone: string, subject: string, message: string} $payload
 */
function erc_send_contact_email(array $payload): bool
{
    $adminEmail = erc_get_admin_email();

    $subjectLabels = [
        'volunteer'             => 'Volunteer (Individual)',
        'group-volunteer'       => 'Group Volunteer',
        'donation-items'        => 'Donate Items',
        'donation-money'        => 'Donate Money',
        'need-collection-boxes' => 'Collection Boxes',
        'partnership'           => 'Partnership',
        'question'              => 'Question',
        'other'                 => 'Other',
    ];

    $rawSubject = trim((string)($payload['subject'] ?? 'General Inquiry'));
    $subjectText = $subjectLabels[strtolower($rawSubject)] ?? ($rawSubject !== '' ? $rawSubject : 'General Inquiry');

    $name = (string)$payload['name'];
    $email = (string)$payload['email'];
    $phone = (string)$payload['phone'];
    $message = (string)$payload['message'];

    $mailSubject = '[2E2 ERC Contact] ' . erc_sanitize_header($subjectText) . ' - ' . erc_sanitize_header($name);
    $html = '<h2>Website Contact Inquiry</h2>'
        . '<p><strong>Topic:</strong> ' . e($subjectText) . '</p>'
        . '<p><strong>Name:</strong> ' . e($name) . '</p>'
        . '<p><strong>Email:</strong> ' . e($email) . '</p>'
        . '<p><strong>Phone:</strong> ' . e($phone !== '' ? $phone : 'Not provided') . '</p>'
        . '<hr>'
        . '<p style="white-space:pre-wrap;">' . nl2br(e($message)) . '</p>';

    $text = "Website Contact Inquiry\n"
        . "Topic: {$subjectText}\n"
        . "Name: {$name}\n"
        . "Email: {$email}\n"
        . 'Phone: ' . ($phone !== '' ? $phone : 'Not provided') . "\n\n"
        . "Message:\n{$message}\n";

    return erc_send_mail($adminEmail, $mailSubject, $html, $text, $email, $name);
}
