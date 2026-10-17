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
$customerSignupPath = $customerSignupPath ?? 'customer-signup/';

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/mailer.php'; // NEW: loads the email-sending function

$customerAccountsTable = $customerAccountsTable ?? 'customer_accounts';
$errorMessage = '';
$infoMessage = ''; // NEW: for "new code sent" messages
$customerEmail = '';
$pendingCustomerId = (int) ($_SESSION['pending_customer_verification_id'] ?? 0);

if ($pendingCustomerId <= 0) {
    header('Location: ' . $customerSignupPath);
    exit;
}

// CHANGED: also load the verification fields
$customerStmt = $conn->prepare(
    "SELECT email, first_name, email_verified_at, verification_code_hash, verification_expires_at,
            verification_attempts,
            TIMESTAMPDIFF(SECOND, verification_last_sent_at, NOW()) AS seconds_since_sent,
            (verification_expires_at IS NOT NULL AND verification_expires_at < NOW()) AS is_expired
     FROM {$customerAccountsTable} WHERE id = ? LIMIT 1"
);
$customerStmt->bind_param('i', $pendingCustomerId);
$customerStmt->execute();
$customer = $customerStmt->get_result()->fetch_assoc();
$customerStmt->close();

if (!$customer) {
    unset($_SESSION['pending_customer_verification_id']);
    header('Location: ' . $customerSignupPath);
    exit;
}

// NEW: already verified, go straight to login
if (!empty($customer['email_verified_at'])) {
    unset($_SESSION['pending_customer_verification_id']);
    header('Location: ' . $customerLoginPath . '?verified=1');
    exit;
}

$customerEmail = $customer['email'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (($_POST['action'] ?? '') === 'resend') {
        // NEW: resend button (60-second cooldown)
        $sinceSent = $customer['seconds_since_sent'];
        if ($sinceSent !== null && (int) $sinceSent < 60) {
            $errorMessage = 'Please wait ' . (60 - (int) $sinceSent) . ' seconds before requesting another code.';
        } elseif (send_customer_verification_code($conn, $customerAccountsTable, $pendingCustomerId, $customerEmail, (string) $customer['first_name'])) {
            $infoMessage = 'A new code has been sent to your email.';
        } else {
            $errorMessage = 'We could not send the email right now. Please try again shortly.';
        }
    } else {
        // CHANGED: the code is now actually checked
        $verificationCode = trim($_POST['verification_code'] ?? '');

        if ($verificationCode === '') {
            $errorMessage = 'Please enter a verification code.';
        } elseif (empty($customer['verification_code_hash'])) {
            $errorMessage = 'No active code. Please request a new one.';
        } elseif ((int) $customer['is_expired'] === 1) {
            $errorMessage = 'That code has expired. Please request a new one.';
        } elseif ((int) $customer['verification_attempts'] >= 5) {
            $errorMessage = 'Too many attempts. Please request a new code.';
        } elseif (!password_verify($verificationCode, $customer['verification_code_hash'])) {
            $attemptStmt = $conn->prepare("UPDATE {$customerAccountsTable} SET verification_attempts = verification_attempts + 1 WHERE id = ?");
            $attemptStmt->bind_param('i', $pendingCustomerId);
            $attemptStmt->execute();
            $attemptStmt->close();
            $errorMessage = 'Incorrect verification code.';
        } else {
            $verifyStmt = $conn->prepare(
                "UPDATE {$customerAccountsTable}
                 SET email_verified_at = NOW(), verification_code_hash = NULL,
                     verification_expires_at = NULL, verification_attempts = 0
                 WHERE id = ?"
            );
            $verifyStmt->bind_param('i', $pendingCustomerId);
            $verifyStmt->execute();
            $verifyStmt->close();

            unset($_SESSION['pending_customer_verification_id']);

            header('Location: ' . $customerLoginPath . '?verified=1');
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
    <title>Verify Email | Creaty</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>css/style.css?v=20261008-2">
</head>
<body class="verify-page">
    <main class="login-page-shell">
        <section class="login-card reveal">
            <div class="brand-wrap">
                <img class="brand-logo" src="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>assets/images/main_logo.png" alt="The Nifty Fifty">
            </div>

            <div class="verify-card">
                <h1 class="verify-title">PLEASE VERIFY YOUR EMAIL ADDRESS</h1>
                <p class="verify-copy">
                    We've sent an email to <?php echo htmlspecialchars($customerEmail, ENT_QUOTES, 'UTF-8'); ?><br>
                    please enter the code below
                </p>

                <form class="verify-form" action="" method="post" novalidate>
                    <?php if ($infoMessage !== ''): ?>
                        <p class="form-message"><?php echo htmlspecialchars($infoMessage, ENT_QUOTES, 'UTF-8'); ?></p>
                    <?php endif; ?>

                    <?php if ($errorMessage !== ''): ?>
                        <p class="form-message form-message-error"><?php echo htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8'); ?></p>
                    <?php endif; ?>

                    <label class="sr-only" for="verification_code">Verification code</label>
                    <div class="input-row input-row-plain verify-input-row">
                        <input id="verification_code" name="verification_code" type="text" placeholder="Enter verification code" autocomplete="one-time-code" inputmode="numeric" maxlength="6">
                    </div>

                    <div class="verify-actions">
                        <button class="verify-submit" type="submit">CONFIRM</button>
                    </div>
                </form>

                <!-- NEW: resend form -->
                <form action="" method="post" class="mt-3 text-center">
                    <input type="hidden" name="action" value="resend">
                    <button type="submit" class="btn btn-link">Didn't get it? Resend code</button>
                </form>
            </div>
        </section>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script src="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>js/script.js"></script>
</body>
</html>