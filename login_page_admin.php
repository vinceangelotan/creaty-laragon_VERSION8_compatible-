<?php
if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {
    header('Location: index.php');
    exit;
}

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$routeBase = $routeBase ?? 'admin/';
$assetBase = $assetBase ?? '';
$customerLoginPath = $customerLoginPath ?? $assetBase . 'customer-login/';

require_once __DIR__ . '/config/db.php';

$adminAccountsTable = $adminAccountsTable ?? 'admin_accounts';

if (isset($_SESSION['customer_id'])) {
    header('Location: ' . $assetBase);
    exit;
}

if (isset($_SESSION['staff_id'])) {
    header('Location: ' . $routeBase . 'dashboard/');
    exit;
}

if (isset($_SESSION['user_id'])) {
    header('Location: ' . $routeBase . 'dashboard/');
    exit;
}

$errorMessage = '';
$successMessage = '';
$usernameValue = '';

if (isset($_GET['registered']) && $_GET['registered'] === '1') {
    $successMessage = 'Account created. You can sign in now.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usernameValue = trim($_POST['username'] ?? '');
    $passwordValue = $_POST['password'] ?? '';

    if ($usernameValue === '' || $passwordValue === '') {
        $errorMessage = 'Please enter both username and password.';
    } else {
        $loginStmt = $conn->prepare("SELECT id, username, password FROM {$adminAccountsTable} WHERE username = ? LIMIT 1");
        $loginStmt->bind_param('s', $usernameValue);
        $loginStmt->execute();
        $userResult = $loginStmt->get_result();
        $user = $userResult->fetch_assoc();
        $loginStmt->close();

        if ($user && password_verify($passwordValue, $user['password'])) {
            $_SESSION['user_id'] = (int) $user['id'];
            $_SESSION['username'] = $user['username'];

            header('Location: ' . $routeBase . 'dashboard/');
            exit;
        }

        $errorMessage = 'Invalid username or password.';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login | Creaty</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>css/style.css?v=20261008-2">
</head>
<body class="login-page">
    <main class="login-page-shell">
        <a class="auth-switch-link" href="<?php echo htmlspecialchars($customerLoginPath, ENT_QUOTES, 'UTF-8'); ?>" data-auth-switch aria-label="Switch to customer login">
            <img src="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>assets/icons/customer-login-icon.svg" alt="">
        </a>

        <section class="login-card reveal">
            <div class="brand-wrap">
                <img class="brand-logo" src="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>assets/images/main_logo.png" alt="The Nifty Fifty">
            </div>

            <h1 class="login-title">ADMIN LOG IN</h1>

            <form class="admin-login-form" action="" method="post" novalidate>
                <?php if ($errorMessage !== ''): ?>
                    <p class="form-message form-message-error"><?php echo htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8'); ?></p>
                <?php elseif ($successMessage !== ''): ?>
                    <p class="form-message form-message-success"><?php echo htmlspecialchars($successMessage, ENT_QUOTES, 'UTF-8'); ?></p>
                <?php endif; ?>

                <label class="sr-only" for="username">Username</label>
                <div class="input-row">
                    <span class="field-icon" aria-hidden="true">
                        <img src="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>assets/icons/email_icon.svg" alt="">
                    </span>
                    <input id="username" name="username" type="text" placeholder="Enter your username" value="<?php echo htmlspecialchars($usernameValue, ENT_QUOTES, 'UTF-8'); ?>" autocomplete="username">
                </div>

                <label class="sr-only" for="password">Password</label>
                <div class="input-row">
                    <span class="field-icon" aria-hidden="true">
                        <img src="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>assets/icons/password_icon.svg" alt="">
                    </span>
                    <input id="password" name="password" type="password" placeholder="Enter your password" autocomplete="current-password">
                    <button
                        class="toggle-visibility"
                        type="button"
                        aria-label="Show password"
                        data-target="password"
                        data-icon-on="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>assets/icons/visibility_on.svg"
                        data-icon-off="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>assets/icons/visibility_off.svg"
                    >
                        <img src="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>assets/icons/visibility_off.svg" alt="">
                    </button>
                </div>

                <div class="login-options">
                    <a class="support-link" href="#">Forgot Password?</a>

                    <label class="remember-wrap" for="remember">
                        <span>Remember me</span>
                        <input id="remember" name="remember" type="checkbox">
                    </label>
                </div>

                <button class="login-submit" type="submit">LOGIN</button>

                <p class="login-hint">Demo credentials: admin / admin</p>
                <p class="switch-auth">Need administrator access? <a href="<?php echo htmlspecialchars($routeBase, ENT_QUOTES, 'UTF-8'); ?>signup/">Create an account</a></p>
            </form>
        </section>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script src="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>js/script.js"></script>
</body>
</html>
