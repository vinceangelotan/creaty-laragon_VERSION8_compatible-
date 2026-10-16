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
$customerPrivacyPolicyPath = $customerPrivacyPolicyPath ?? 'customer-privacy-policy/';
$customerForgotPasswordPath = $customerForgotPasswordPath ?? $routeBase . 'customer-forgot-password/'; // NEW
$adminSignupPath = $adminSignupPath ?? $routeBase . 'admin/signup/';
$staffDashboardPath = $staffDashboardPath ?? $routeBase . 'admin/dashboard/';

require_once __DIR__ . '/config/db.php';

$customerAccountsTable = $customerAccountsTable ?? 'customer_accounts';
$staffAccountsTable = $staffAccountsTable ?? 'staff_accounts';
$adminAccountsTable = $adminAccountsTable ?? 'admin_accounts';

$successMessage = '';
$errorMessage = '';
$identifierValue = '';
$redirectTarget = trim((string) ($_POST['redirect'] ?? $_GET['redirect'] ?? ''));

$defaultRedirect = $routeBase === '' ? '/' : $routeBase;

if (isset($_SESSION['user_id'])) {
    header('Location: ' . $routeBase . 'admin/dashboard/');
    exit;
}

if (isset($_SESSION['staff_id'])) {
    header('Location: ' . $staffDashboardPath);
    exit;
}

if (isset($_SESSION['customer_id'])) {
    if ($redirectTarget !== '' && preg_match('/^(?:\/|\.\.?\/)/', $redirectTarget) === 1 && strpos($redirectTarget, '://') === false && strpos($redirectTarget, '//') !== 0) {
        header('Location: ' . $redirectTarget);
        exit;
    }

    header('Location: ' . $defaultRedirect);
    exit;
}

if (isset($_GET['verified']) && $_GET['verified'] === '1') {
    $successMessage = 'Email verified. Your account has been created successfully.';
} elseif (isset($_GET['registered']) && $_GET['registered'] === '1') {
    $successMessage = 'Account created successfully. Please verify your email address first.';
} elseif (isset($_GET['admin_registered']) && $_GET['admin_registered'] === '1') {
    $successMessage = 'Admin account created. You can sign in now.';
} elseif (isset($_GET['password_reset']) && $_GET['password_reset'] === '1') { // NEW
    $successMessage = 'Your password has been reset. You can log in with your new password.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifierValue = trim((string) ($_POST['identifier'] ?? ''));
    $passwordValue = $_POST['password'] ?? '';

    if ($identifierValue === '' || $passwordValue === '') {
        $errorMessage = 'Please enter both login ID and password.';
    } else {
        $isEmailIdentifier = filter_var($identifierValue, FILTER_VALIDATE_EMAIL) !== false;

        if ($isEmailIdentifier) {
            $loginStmt = $conn->prepare("SELECT id, name, email, password FROM {$staffAccountsTable} WHERE email = ? LIMIT 1");

            if ($loginStmt) {
                $loginStmt->bind_param('s', $identifierValue);
                $loginStmt->execute();
                $staffResult = $loginStmt->get_result();
                $staffRecord = $staffResult ? $staffResult->fetch_assoc() : null;
                $loginStmt->close();

                if ($staffRecord && password_verify($passwordValue, (string) ($staffRecord['password'] ?? ''))) {
                    $_SESSION['staff_id'] = (int) ($staffRecord['id'] ?? 0);
                    $_SESSION['staff_name'] = trim((string) ($staffRecord['name'] ?? ''));
                    $_SESSION['staff_email'] = (string) ($staffRecord['email'] ?? '');
                    $_SESSION['staff_role'] = 'staff';

                    header('Location: ' . $staffDashboardPath);
                    exit;
                }
            }

            $loginStmt = $conn->prepare("SELECT id, first_name, last_name, email, customer_phone, skill_level, password, email_verified_at FROM {$customerAccountsTable} WHERE email = ? LIMIT 1");

            if ($loginStmt) {
                $loginStmt->bind_param('s', $identifierValue);
                $loginStmt->execute();
                $customerResult = $loginStmt->get_result();
                $customer = $customerResult ? $customerResult->fetch_assoc() : null;
                $loginStmt->close();
            } else {
                $customer = null;
            }

            if (!$customer || !password_verify($passwordValue, (string) ($customer['password'] ?? ''))) {
                $errorMessage = 'Invalid login ID or password.';
            } elseif ($customer['email_verified_at'] === null) {
                $errorMessage = 'Please verify your email address before logging in.';
            } else {
                $customerSkillLevel = trim((string) ($customer['skill_level'] ?? ''));

                if (strcasecmp($customerSkillLevel, 'Professional') === 0) {
                    $customerSkillLevel = 'Professional';
                } else {
                    $customerSkillLevel = 'Beginner';
                }

                $_SESSION['customer_id'] = (int) $customer['id'];
                $_SESSION['customer_name'] = trim(($customer['first_name'] ?? '') . ' ' . ($customer['last_name'] ?? ''));
                $_SESSION['customer_email'] = $customer['email'];
                $_SESSION['customer_phone'] = trim((string) ($customer['customer_phone'] ?? ''));
                $_SESSION['customer_skill_level'] = $customerSkillLevel;

                if (!isset($_SESSION['customer_cart_count'])) {
                    $_SESSION['customer_cart_count'] = 0;
                }

                if ($redirectTarget !== '' && preg_match('/^(?:\/|\.\.?\/)/', $redirectTarget) === 1 && strpos($redirectTarget, '://') === false && strpos($redirectTarget, '//') !== 0) {
                    header('Location: ' . $redirectTarget);
                    exit;
                }

                header('Location: ' . $defaultRedirect);
                exit;
            }
        } else {
            $loginStmt = $conn->prepare("SELECT id, username, password FROM {$adminAccountsTable} WHERE username = ? LIMIT 1");

            if ($loginStmt) {
                $loginStmt->bind_param('s', $identifierValue);
                $loginStmt->execute();
                $adminResult = $loginStmt->get_result();
                $admin = $adminResult ? $adminResult->fetch_assoc() : null;
                $loginStmt->close();
            } else {
                $admin = null;
            }

            if ($admin && password_verify($passwordValue, (string) ($admin['password'] ?? ''))) {
                $_SESSION['user_id'] = (int) ($admin['id'] ?? 0);
                $_SESSION['username'] = (string) ($admin['username'] ?? '');

                header('Location: ' . $routeBase . 'admin/dashboard/');
                exit;
            }

            $errorMessage = 'Invalid login ID or password.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Creaty</title>
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

            <h1 class="login-title">LOG IN</h1>

            <form class="customer-login-form" action="#" method="post" novalidate>
                <?php if ($errorMessage !== ''): ?>
                    <p class="form-message form-message-error"><?php echo htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8'); ?></p>
                <?php elseif ($successMessage !== ''): ?>
                    <p class="form-message form-message-success"><?php echo htmlspecialchars($successMessage, ENT_QUOTES, 'UTF-8'); ?></p>
                <?php endif; ?>

                <input type="hidden" name="redirect" value="<?php echo htmlspecialchars($redirectTarget, ENT_QUOTES, 'UTF-8'); ?>">

                <label class="sr-only" for="login_identifier">Email or username</label>
                <div class="input-row">
                    <span class="field-icon" aria-hidden="true">
                        <img src="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>assets/icons/email_icon.svg" alt="">
                    </span>
                    <input id="login_identifier" name="identifier" type="text" placeholder="Email / Username" autocomplete="username" value="<?php echo htmlspecialchars($identifierValue, ENT_QUOTES, 'UTF-8'); ?>">
                    <span class="field-icon" aria-hidden="true"></span>
                </div>

                <label class="sr-only" for="customer_password">Password</label>
                <div class="input-row">
                    <span class="field-icon" aria-hidden="true">
                        <img src="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>assets/icons/password_icon.svg" alt="">
                    </span>
                    <input id="customer_password" name="password" type="password" placeholder="Enter your password" autocomplete="current-password">
                    <button
                        class="toggle-visibility"
                        type="button"
                        aria-label="Show password"
                        data-target="customer_password"
                        data-icon-on="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>assets/icons/visibility_on.svg"
                        data-icon-off="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>assets/icons/visibility_off.svg"
                    >
                        <img src="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>assets/icons/visibility_off.svg" alt="">
                    </button>
                </div>

                <div class="login-options">
                    <!-- CHANGED: was href="#" -->
                    <a class="support-link" href="<?php echo htmlspecialchars($customerForgotPasswordPath, ENT_QUOTES, 'UTF-8'); ?>">Forgot Password?</a>

                    <label class="remember-wrap" for="customer_remember">
                        <span>Remember me</span>
                        <input id="customer_remember" name="remember" type="checkbox">
                    </label>
                </div>

                <button class="login-submit" type="submit">LOGIN</button>

                <p class="switch-auth">Not a member? <a href="<?php echo htmlspecialchars($customerSignupPath, ENT_QUOTES, 'UTF-8'); ?>">Sign up as customer</a></p>
                <p class="switch-auth">Are you an admin? <a href="<?php echo htmlspecialchars($adminSignupPath, ENT_QUOTES, 'UTF-8'); ?>">Sign up here</a></p>
            </form>
        </section>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script src="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>js/script.js"></script>
</body>
</html>
