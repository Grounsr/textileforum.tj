<?php
// send.php – Registration form handler

$to = 'info@textileforum.tj';
$subject = 'New registration - International Textile Forum Tajikistan 2026';

$first = trim($_POST['first_name'] ?? '');
$last  = trim($_POST['last_name'] ?? '');
$email = trim($_POST['email'] ?? '');
$org   = trim($_POST['organization'] ?? '');
$type  = trim($_POST['participation_type'] ?? '');
$lang  = trim($_POST['lang'] ?? 'en');

$body = "New registration from textileforum.tj\n\n" .
        "Name: $first $last\n" .
        "Email: $email\n" .
        "Organization: $org\n" .
        "Participation type: $type\n" .
        "Language: $lang\n";

$headers = "From: no-reply@textileforum.tj\r\n" .
           "Reply-To: $email\r\n" .
           "Content-Type: text/plain; charset=UTF-8\r\n";

if ($email && filter_var($email, FILTER_VALIDATE_EMAIL) && mail($to, $subject, $body, $headers)) {
    header('Location: /thank-you.html');
    exit;
} else {
    echo 'Error sending message. Please try again later.';
}
