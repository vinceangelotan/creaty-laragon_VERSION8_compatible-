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
$customerLoginPath = $customerLoginPath ?? $routeBase . 'customer-login/';
$adminLoginPath = $adminLoginPath ?? $routeBase . 'admin/';
$staffDashboardPath = $staffDashboardPath ?? $routeBase . 'admin/dashboard/';

require_once __DIR__ . '/config/db.php';

$staffAccountsTable = $staffAccountsTable ?? 'staff_accounts';

$defaultRedirect = $routeBase === '' ? '/' : $routeBase;

if (isset($_SESSION['customer_id'])) {
    header('Location: ' . $defaultRedirect);
    exit;
}

if (isset($_SESSION['user_id']) || isset($_SESSION['staff_id'])) {
    header('Location: ' . $staffDashboardPath);
    exit;
}

$errorMessage = '';
$emailValue = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $emailValue = trim((string) ($_POST['email'] ?? ''));
    $passwordValue = (string) ($_POST['password'] ?? '');

    if ($emailValue === '' || $passwordValue === '') {
        $errorMessage = 'Please enter both email and password.';
    } elseif (!filter_var($emailValue, FILTER_VALIDATE_EMAIL)) {
        $errorMessage = 'Please enter a valid email address.';
    } else {
        $loginStmt = $conn->prepare("SELECT id, name, email, password FROM {$staffAccountsTable} WHERE email = ? LIMIT 1");

        if (!$loginStmt) {
            $errorMessage = 'Unable to process login right now.';
        } else {
            $loginStmt->bind_param('s', $emailValue);
            $loginStmt->execute();
            $staffResult = $loginStmt->get_result();
            $staffRecord = $staffResult ? $staffResult->fetch_assoc() : null;
            $loginStmt->close();

            if ($staffRecord && password_verify($passwordValue, (string) ($staffRecord['password'] ?? ''))) {
                unset($_SESSION['user_id'], $_SESSION['username']);

                $_SESSION['staff_id'] = (int) ($staffRecord['id'] ?? 0);
                $_SESSION['staff_name'] = trim((string) ($staffRecord['name'] ?? ''));
                $_SESSION['staff_email'] = (string) ($staffRecord['email'] ?? '');
                $_SESSION['staff_role'] = 'staff';

                header('Location: ' . $staffDashboardPath);
                exit;
            }

            $errorMessage = 'Invalid email or password.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Login | Creaty</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>css/style.css?v=20261008-2">
</head>
<body class="login-page">
    <main class="login-page-shell">
        <a class="auth-switch-link" href="<?php echo htmlspecialchars($adminLoginPath, ENT_QUOTES, 'UTF-8'); ?>" data-auth-switch aria-label="Switch to administrator login">
            <img src="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>assets/icons/admin-login-icon.svg" alt="">
        </a>

        <section class="login-card reveal">
            <div class="brand-wrap">
                <img class="brand-logo" src="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>assets/images/main_logo.png" alt="The Nifty Fifty">
            </div>

            <h1 class="login-title">STAFF LOGIN</h1>

            <form class="staff-login-form" action="" method="post" novalidate>
                <?php if ($errorMessage !== ''): ?>
                    <p class="form-message form-message-error"><?php echo htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8'); ?></p>
                <?php endif; ?>

                <label class="sr-only" for="staff_email">Email address</label>
                <div class="input-row">
                    <span class="field-icon" aria-hidden="true">
                        <img src="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>assets/icons/email_icon.svg" alt="">
                    </span>
                    <input id="staff_email" name="email" type="email" placeholder="Enter your email address" value="<?php echo htmlspecialchars($emailValue, ENT_QUOTES, 'UTF-8'); ?>" autocomplete="email">
                    <span class="field-icon" aria-hidden="true"></span>
                </div>

                <label class="sr-only" for="staff_password">Password</label>
                <div class="input-row">
                    <span class="field-icon" aria-hidden="true">
                        <img src="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>assets/icons/password_icon.svg" alt="">
                    </span>
                    <input id="staff_password" name="password" type="password" placeholder="Enter your password" autocomplete="current-password">
                    <button
                        class="toggle-visibility"
                        type="button"
                        aria-label="Show password"
                        data-target="staff_password"
                        data-icon-on="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>assets/icons/visibility_on.svg"
                        data-icon-off="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>assets/icons/visibility_off.svg"
                    >
                        <img src="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>assets/icons/visibility_off.svg" alt="">
                    </button>
                </div>

                <div class="login-options">
                    <a class="support-link" href="#">Forgot Password?</a>

                    <label class="remember-wrap" for="staff_remember">
                        <span>Remember me</span>
                        <input id="staff_remember" name="remember" type="checkbox">
                    </label>
                </div>

                <button class="login-submit" type="submit">LOGIN</button>

                <p class="switch-auth">Need customer access? <a href="<?php echo htmlspecialchars($customerLoginPath, ENT_QUOTES, 'UTF-8'); ?>">Log in as customer</a></p>
            </form>
        </section>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script src="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>js/script.js"></script>
</body>
</html>
