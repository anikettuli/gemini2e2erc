<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 1. Sanitize Input
    $name = strip_tags(trim($_POST["name"]));
    $email = filter_var(trim($_POST["email"]), FILTER_SANITIZE_EMAIL);
    $message = trim($_POST["message"]);

    // 2. Configuration
    $recipient = "info@2e2erc.org"; 
    $subject = "New Contact Message from $name";
    
    // 3. Build Email Content
    $email_content = "Name: $name\n";
    $email_content .= "Email: $email\n\n";
    $email_content .= "Message:\n$message\n";

    // 4. Build Headers
    $headers = "From: $name <$email>";

    // 5. Send
    if (mail($recipient, $subject, $email_content, $headers)) {
        // Success: Redirect back with a success parameter
        header("Location: index.html?status=success");
    } else {
        // Failure
        header("Location: index.html?status=error");
    }
} else {
    // Not a POST request
    header("Location: index.html");
}
?>