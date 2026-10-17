<?php
// TEMPORARY TEST FILE. Delete it when you're done.
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/vendor/autoload.php';

$cfg = require __DIR__ . '/config/mail.php';

header('Content-Type: text/html; charset=UTF-8');
echo '<pre style="white-space:pre-wrap;font-size:14px">';
echo "Driver in config/mail.php: " . htmlspecialchars((string) $cfg['driver']) . "\n";

if ($cfg['driver'] !== 'gmail') {
    echo "\nSTOP: driver is not 'gmail'. Change it to 'gmail' in config/mail.php.\n";
    exit;
}

$g = $cfg['gmail'];

if (strpos($g['username'], 'PUT_YOUR') !== false || strpos($g['password'], 'PUT_YOUR') !== false || strpos($cfg['from_email'], 'PUT_YOUR') !== false) {
    echo "\nSTOP: you still have PUT_YOUR_... placeholders in config/mail.php.\n";
    exit;
}

echo "Username: " . htmlspecialchars($g['username']) . "\n";
echo "App Password length: " . strlen($g['password']) . " (must be exactly 16, no spaces)\n";
echo "Port/encryption: " . (int) $g['port'] . " / " . htmlspecialchars((string) ($g['encryption'] ?? 'tls')) . "\n";
echo "OpenSSL loaded: " . (extension_loaded('openssl') ? 'yes' : 'NO (needs to be enabled)') . "\n\n";

$mail = new PHPMailer(true);
try {
    $mail->isSMTP();
    $mail->SMTPDebug  = 2;
    $mail->Debugoutput = function ($str, $level) {
        // hide the encoded login lines so nothing sensitive is printed
        $str = preg_replace('/(CLIENT -> SERVER: )[A-Za-z0-9+\/=]{12,}\s*$/m', '$1[hidden]', $str);
        echo htmlspecialchars($str);
    };
    $mail->Host       = $g['host'];
    $mail->SMTPAuth   = true;
    $mail->Username   = $g['username'];
    $mail->Password   = $g['password'];
    $mail->Port       = (int) $g['port'];
    $mail->SMTPSecure = ($g['encryption'] ?? 'tls') === 'ssl'
        ? PHPMailer::ENCRYPTION_SMTPS
        : PHPMailer::ENCRYPTION_STARTTLS;

    $mail->setFrom($cfg['from_email'], $cfg['from_name']);
    $mail->addAddress($g['username']); // sends the test to yourself

    $mail->Subject = 'Creaty test email';
    $mail->Body    = 'If you can read this, Gmail sending works.';
    $mail->send();

    echo "\n\nRESULT: SENT OK. Check the inbox (and Spam) of " . htmlspecialchars($g['username']) . "\n";
} catch (Exception $e) {
    echo "\n\nRESULT: FAILED\n";
    echo htmlspecialchars($mail->ErrorInfo) . "\n";
}
echo '</pre>';
