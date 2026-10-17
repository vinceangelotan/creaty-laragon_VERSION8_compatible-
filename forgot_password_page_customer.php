<?php
if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {
    header('Location: index.php');
    exit;
}

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$routeBase = $routeBase ?? '';
$assetBase = $assetBase ?? '';
$customerLoginPath = $customerLoginPath ?? 'customer-login/';
$customerResetPasswordPath = $customerResetPasswordPath ?? $routeBase . 'customer-reset-password/';

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/mailer.php';

$customerAccountsTable = $customerAccountsTable ?? 'customer_accounts';
$errorMessage = '';
$emailValue = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $emailValue = trim((string) ($_POST['email'] ?? ''));

    if ($emailValue === '') {
        $errorMessage = 'Please enter your email address.';
    } elseif (!filter_var($emailValue, FILTER_VALIDATE_EMAIL)) {
        $errorMessage = 'Please enter a valid email address.';
    } else {
        $lookupStmt = $conn->prepare(
            "SELECT id, first_name, email, email_verified_at,
                    TIMESTAMPDIFF(SECOND, reset_last_sent_at, NOW()) AS seconds_since_sent
             FROM {$customerAccountsTable} WHERE email = ? LIMIT 1"
        );
        $lookupStmt->bind_param('s', $emailValue);
        $lookupStmt->execute();
        $account = $lookupStmt->get_result()->fetch_assoc();
        $lookupStmt->close();

        // Same result for every email, so nobody can use this form to find out who has an account.
        $_SESSION['pending_password_reset_email'] = $emailValue;
        $_SESSION['password_reset_requested_at'] = time();

        $cooldownOver = $account && ($account['seconds_since_sent'] === null || (int) $account['seconds_since_sent'] >= 60);

        if ($account && !empty($account['email_verified_at']) && $cooldownOver) {
            send_customer_password_reset_code(
                $conn,
                $customerAccountsTable,
                (int) $account['id'],
                (string) $account['email'],
                (string) $account['first_name']
            );
        }

        header('Location: ' . $customerResetPasswordPath);
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password | Creaty</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>css/style.css?v=20261008-2">
</head>
<body class="login-page">
    <main class="login-page-shell">
        <section class="login-card reveal">
            <div class="brand-wrap">
                <img class="brand-logo" src="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>assets/images/main_logo.png" alt="The Nifty Fifty">
            </div>

            <h1 class="login-title">FORGOT PASSWORD</h1>
            <p class="verify-copy text-center">Enter your account email and we'll send you a 6-digit code to reset your password.</p>

            <form class="customer-login-form" action="" method="post" novalidate>
                <?php if ($errorMessage !== ''): ?>
                    <p class="form-message form-message-error"><?php echo htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8'); ?></p>
                <?php endif; ?>

                <label class="sr-only" for="forgot_email">Email address</label>
                <div class="input-row">
                    <span class="field-icon" aria-hidden="true">
                        <img src="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>assets/icons/email_icon.svg" alt="">
                    </span>
                    <input id="forgot_email" name="email" type="email" placeholder="Email address" autocomplete="email" value="<?php echo htmlspecialchars($emailValue, ENT_QUOTES, 'UTF-8'); ?>">
                    <span class="field-icon" aria-hidden="true"></span>
                </div>

                <button class="login-submit" type="submit">SEND CODE</button>

                <p class="switch-auth">Remembered it? <a href="<?php echo htmlspecialchars($customerLoginPath, ENT_QUOTES, 'UTF-8'); ?>">Back to login</a></p>
            </form>
        </section>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script src="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>js/script.js"></script>
</body>
</html>
