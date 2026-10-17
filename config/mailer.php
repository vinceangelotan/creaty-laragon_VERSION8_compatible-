<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php';

/**
 * Sends one HTML email through the driver set in config/mail.php (gmail or mailpit).
 */
function creaty_send_mail(string $toEmail, string $subject, string $htmlBody, string $altBody): bool
{
    $cfg  = require __DIR__ . '/mail.php';
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->CharSet = 'UTF-8';

        if ($cfg['driver'] === 'gmail') {
            $g = $cfg['gmail'];
            $mail->Host       = $g['host'];
            $mail->SMTPAuth   = true;
            $mail->Username   = $g['username'];
            $mail->Password   = $g['password'];
            $mail->Port       = (int) $g['port'];
            $mail->SMTPSecure = ($g['encryption'] ?? 'tls') === 'ssl'
                ? PHPMailer::ENCRYPTION_SMTPS
                : PHPMailer::ENCRYPTION_STARTTLS;
        } else {
            $mail->Host        = $cfg['mailpit']['host'];
            $mail->Port        = $cfg['mailpit']['port'];
            $mail->SMTPAuth    = false;
            $mail->SMTPSecure  = '';
            $mail->SMTPAutoTLS = false;
        }

        if (!empty($cfg['debug'])) {
            $mail->SMTPDebug = 2;
        }

        $mail->setFrom($cfg['from_email'], $cfg['from_name']);
        $mail->addAddress($toEmail);

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $htmlBody;
        $mail->AltBody = $altBody;

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log('Mail failed: ' . $mail->ErrorInfo);
        return false;
    }
}

function creaty_code_email_html(string $heading, string $intro, string $code): string
{
    return "
        <div style='font-family:Arial,sans-serif;max-width:420px;margin:auto;text-align:center'>
          <h2>{$heading}</h2>
          <p>{$intro}</p>
          <p style='font-size:34px;letter-spacing:8px;font-weight:bold;margin:24px 0'>{$code}</p>
          <p style='color:#666'>This code expires in 10 minutes. If you didn't request it, you can ignore this email.</p>
        </div>";
}

/**
 * Signup email verification code (uses the verification_* columns).
 */
function send_customer_verification_code(mysqli $conn, string $table, int $customerId, string $toEmail, string $firstName = ''): bool
{
    $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    $hash = password_hash($code, PASSWORD_DEFAULT);

    $stmt = $conn->prepare(
        "UPDATE {$table}
         SET verification_code_hash = ?,
             verification_expires_at = DATE_ADD(NOW(), INTERVAL 10 MINUTE),
             verification_last_sent_at = NOW(),
             verification_attempts = 0
         WHERE id = ?"
    );
    $stmt->bind_param('si', $hash, $customerId);
    $stmt->execute();
    $stmt->close();

    $safeName = htmlspecialchars($firstName !== '' ? $firstName : 'there', ENT_QUOTES, 'UTF-8');

    return creaty_send_mail(
        $toEmail,
        'Your verification code',
        creaty_code_email_html('Verify your email', "Hi {$safeName}, use this code to finish creating your account:", $code),
        "Your verification code is {$code}. It expires in 10 minutes."
    );
}

/**
 * Forgot-password reset code (uses the reset_* columns).
 */
function send_customer_password_reset_code(mysqli $conn, string $table, int $customerId, string $toEmail, string $firstName = ''): bool
{
    $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    $hash = password_hash($code, PASSWORD_DEFAULT);

    $stmt = $conn->prepare(
        "UPDATE {$table}
         SET reset_code_hash = ?,
             reset_expires_at = DATE_ADD(NOW(), INTERVAL 10 MINUTE),
             reset_last_sent_at = NOW(),
             reset_attempts = 0
         WHERE id = ?"
    );
    $stmt->bind_param('si', $hash, $customerId);
    $stmt->execute();
    $stmt->close();

    $safeName = htmlspecialchars($firstName !== '' ? $firstName : 'there', ENT_QUOTES, 'UTF-8');

    return creaty_send_mail(
        $toEmail,
        'Your password reset code',
        creaty_code_email_html('Reset your password', "Hi {$safeName}, use this code to reset your password:", $code),
        "Your password reset code is {$code}. It expires in 10 minutes."
    );
}
