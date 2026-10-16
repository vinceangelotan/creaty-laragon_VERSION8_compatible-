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
$customerForgotPasswordPath = $customerForgotPasswordPath ?? $routeBase . 'customer-forgot-password/';

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/mailer.php';

$customerAccountsTable = $customerAccountsTable ?? 'customer_accounts';
$errorMessage = '';
$infoMessage = '';
$pendingEmail = (string) ($_SESSION['pending_password_reset_email'] ?? '');

if ($pendingEmail === '') {
    header('Location: ' . $customerForgotPasswordPath);
    exit;
}

$customerStmt = $conn->prepare(
    "SELECT id, first_name, email, email_verified_at, reset_code_hash, reset_attempts,
            TIMESTAMPDIFF(SECOND, reset_last_sent_at, NOW()) AS seconds_since_sent,
            (reset_expires_at IS NOT NULL AND reset_expires_at < NOW()) AS is_expired
     FROM {$customerAccountsTable} WHERE email = ? LIMIT 1"
);
$customerStmt->bind_param('s', $pendingEmail);
$customerStmt->execute();
$customer = $customerStmt->get_result()->fetch_assoc();
$customerStmt->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (($_POST['action'] ?? '') === 'resend') {
        // Visible 60-second cooldown (session based, so it looks the same for every email)
        $requestedAt = (int) ($_SESSION['password_reset_requested_at'] ?? 0);
        $wait = 60 - (time() - $requestedAt);

        if ($wait > 0) {
            $errorMessage = 'Please wait ' . $wait . ' seconds before requesting another code.';
        } else {
            $_SESSION['password_reset_requested_at'] = time();

            $dbCooldownOver = $customer && ($customer['seconds_since_sent'] === null || (int) $customer['seconds_since_sent'] >= 60);

            if ($customer && !empty($customer['email_verified_at']) && $dbCooldownOver) {
                send_customer_password_reset_code(
                    $conn,
                    $customerAccountsTable,
                    (int) $customer['id'],
                    (string) $customer['email'],
                    (string) $customer['first_name']
                );
            }

            $infoMessage = 'If that email has an account, a new code has been sent.';
        }
    } else {
        $codeValue = trim((string) ($_POST['reset_code'] ?? ''));
        $newPassword = (string) ($_POST['new_password'] ?? '');
        $confirmPassword = (string) ($_POST['confirm_password'] ?? '');

        if ($codeValue === '' || $newPassword === '' || $confirmPassword === '') {
            $errorMessage = 'Please complete all fields.';
        } elseif (strlen($newPassword) < 8) {
            $errorMessage = 'Your new password must be at least 8 characters.';
        } elseif ($newPassword !== $confirmPassword) {
            $errorMessage = 'Passwords do not match.';
        } elseif (!$customer || empty($customer['reset_code_hash'])) {
            $errorMessage = 'Invalid or expired code. Please request a new one.';
        } elseif ((int) $customer['is_expired'] === 1) {
            $errorMessage = 'That code has expired. Please request a new one.';
        } elseif ((int) $customer['reset_attempts'] >= 5) {
            $errorMessage = 'Too many attempts. Please request a new code.';
        } elseif (!password_verify($codeValue, (string) $customer['reset_code_hash'])) {
            $attemptStmt = $conn->prepare("UPDATE {$customerAccountsTable} SET reset_attempts = reset_attempts + 1 WHERE id = ?");
            $customerIdForAttempt = (int) $customer['id'];
            $attemptStmt->bind_param('i', $customerIdForAttempt);
            $attemptStmt->execute();
            $attemptStmt->close();
            $errorMessage = 'Incorrect code.';
        } else {
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $customerId = (int) $customer['id'];

            $updateStmt = $conn->prepare(
                "UPDATE {$customerAccountsTable}
                 SET password = ?, reset_code_hash = NULL, reset_expires_at = NULL, reset_attempts = 0
                 WHERE id = ?"
            );
            $updateStmt->bind_param('si', $hashedPassword, $customerId);
            $updateStmt->execute();
            $updateStmt->close();

            unset($_SESSION['pending_password_reset_email'], $_SESSION['password_reset_requested_at']);

            header('Location: ' . $customerLoginPath . '?password_reset=1');
            exit;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password | Creaty</title>
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

            <h1 class="login-title">RESET PASSWORD</h1>
            <p class="verify-copy text-center">
                If <?php echo htmlspecialchars($pendingEmail, ENT_QUOTES, 'UTF-8'); ?> has an account, we've sent a 6-digit code to it.<br>
                Enter the code and choose a new password.
            </p>

            <form class="customer-login-form" action="" method="post" novalidate>
                <?php if ($infoMessage !== ''): ?>
                    <p class="form-message form-message-success"><?php echo htmlspecialchars($infoMessage, ENT_QUOTES, 'UTF-8'); ?></p>
                <?php endif; ?>

                <?php if ($errorMessage !== ''): ?>
                    <p class="form-message form-message-error"><?php echo htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8'); ?></p>
                <?php endif; ?>

                <label class="sr-only" for="reset_code">Reset code</label>
                <div class="input-row input-row-plain">
                    <input id="reset_code" name="reset_code" type="text" placeholder="Enter 6-digit code" autocomplete="one-time-code" inputmode="numeric" maxlength="6">
                </div>

                <label class="sr-only" for="reset_new_password">New password</label>
                <div class="input-row">
                    <span class="field-icon" aria-hidden="true">
                        <img src="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>assets/icons/password_icon.svg" alt="">
                    </span>
                    <input id="reset_new_password" name="new_password" type="password" placeholder="New password (min. 8 characters)" autocomplete="new-password">
                    <button
                        class="toggle-visibility"
                        type="button"
                        aria-label="Show password"
                        data-target="reset_new_password"
                        data-icon-on="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>assets/icons/visibility_on.svg"
                        data-icon-off="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>assets/icons/visibility_off.svg"
                    >
                        <img src="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>assets/icons/visibility_off.svg" alt="">
                    </button>
                </div>

                <label class="sr-only" for="reset_confirm_password">Confirm new password</label>
                <div class="input-row">
                    <span class="field-icon" aria-hidden="true">
                        <img src="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>assets/icons/password_icon.svg" alt="">
                    </span>
                    <input id="reset_confirm_password" name="confirm_password" type="password" placeholder="Re-enter your new password" autocomplete="new-password">
                    <button
                        class="toggle-visibility"
                        type="button"
                        aria-label="Show password"
                        data-target="reset_confirm_password"
                        data-icon-on="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>assets/icons/visibility_on.svg"
                        data-icon-off="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>assets/icons/visibility_off.svg"
                    >
                        <img src="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>assets/icons/visibility_off.svg" alt="">
                    </button>
                </div>

                <button class="login-submit" type="submit">RESET PASSWORD</button>
            </form>

            <form action="" method="post" class="mt-3 text-center">
                <input type="hidden" name="action" value="resend">
                <button type="submit" class="btn btn-link">Didn't get it? Resend code</button>
            </form>

            <p class="switch-auth"><a href="<?php echo htmlspecialchars($customerLoginPath, ENT_QUOTES, 'UTF-8'); ?>">Back to login</a></p>
        </section>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script src="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>js/script.js"></script>
</body>
</html>
