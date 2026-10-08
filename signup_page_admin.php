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
$unifiedLoginPath = $unifiedLoginPath ?? $assetBase . 'customer-login/';

require_once __DIR__ . '/config/db.php';

$adminAccountsTable = $adminAccountsTable ?? 'admin_accounts';

if (isset($_SESSION['user_id'])) {
    header('Location: ' . $routeBase . 'dashboard/');
    exit;
}

$errorMessage = '';
$usernameValue = '';
$employeeNumberValue = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usernameValue = trim($_POST['username'] ?? '');
    $passwordValue = $_POST['password'] ?? '';
    $confirmPasswordValue = $_POST['confirm_password'] ?? '';
    $employeeNumberValue = trim($_POST['employee_number'] ?? '');

    if ($usernameValue === '' || $passwordValue === '' || $confirmPasswordValue === '' || $employeeNumberValue === '') {
        $errorMessage = 'Please complete all fields.';
    } elseif ($passwordValue !== $confirmPasswordValue) {
        $errorMessage = 'Passwords do not match.';
    } else {
        $checkStmt = $conn->prepare("SELECT id FROM {$adminAccountsTable} WHERE username = ? OR employee_number = ? LIMIT 1");
        $checkStmt->bind_param('ss', $usernameValue, $employeeNumberValue);
        $checkStmt->execute();
        $existingUserResult = $checkStmt->get_result();
        $existingUser = $existingUserResult->fetch_assoc();
        $checkStmt->close();

        if ($existingUser) {
            $errorMessage = 'Username or employee number already exists.';
        } else {
            $hashedPassword = password_hash($passwordValue, PASSWORD_DEFAULT);
            $insertStmt = $conn->prepare("INSERT INTO {$adminAccountsTable} (username, employee_number, password) VALUES (?, ?, ?)");
            $insertStmt->bind_param('sss', $usernameValue, $employeeNumberValue, $hashedPassword);

            if ($insertStmt->execute()) {
                $insertStmt->close();
                header('Location: ' . $unifiedLoginPath . '?admin_registered=1');
                exit;
            }

            $errorMessage = 'Unable to create the account right now.';
            $insertStmt->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Signup | Creaty</title>
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

            <h1 class="login-title">ADMIN SIGN UP</h1>

            <form class="admin-login-form" action="" method="post" novalidate>
                <?php if ($errorMessage !== ''): ?>
                    <p class="form-message form-message-error"><?php echo htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8'); ?></p>
                <?php endif; ?>

                <label class="sr-only" for="username">Username</label>
                <div class="input-row">
                    <span class="field-icon" aria-hidden="true">
                        <img src="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>assets/icons/email_icon.svg" alt="">
                    </span>
                    <input id="username" name="username" type="text" placeholder="Create a username" value="<?php echo htmlspecialchars($usernameValue, ENT_QUOTES, 'UTF-8'); ?>" autocomplete="username">
                </div>

                <label class="sr-only" for="password">Password</label>
                <div class="input-row">
                    <span class="field-icon" aria-hidden="true">
                        <img src="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>assets/icons/password_icon.svg" alt="">
                    </span>
                    <input id="password" name="password" type="password" placeholder="Create a password" autocomplete="new-password">
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

                <label class="sr-only" for="confirm_password">Confirm password</label>
                <div class="input-row">
                    <span class="field-icon" aria-hidden="true">
                        <img src="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>assets/icons/password_icon.svg" alt="">
                    </span>
                    <input id="confirm_password" name="confirm_password" type="password" placeholder="Re-enter your password" autocomplete="new-password">
                    <button
                        class="toggle-visibility"
                        type="button"
                        aria-label="Show password"
                        data-target="confirm_password"
                        data-icon-on="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>assets/icons/visibility_on.svg"
                        data-icon-off="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>assets/icons/visibility_off.svg"
                    >
                        <img src="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>assets/icons/visibility_off.svg" alt="">
                    </button>
                </div>

                <label class="sr-only" for="employee_number">Employee number</label>
                <div class="input-row">
                    <span class="field-icon" aria-hidden="true">
                        <img src="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>assets/icons/id_card.svg" alt="">
                    </span>
                    <input id="employee_number" name="employee_number" type="text" placeholder="Enter your employee number" value="<?php echo htmlspecialchars($employeeNumberValue, ENT_QUOTES, 'UTF-8'); ?>" autocomplete="off">
                    <span class="field-icon" aria-hidden="true"></span>
                </div>

                <button class="login-submit" type="submit">SIGN UP</button>

                <p class="switch-auth">Already have an account? <a href="<?php echo htmlspecialchars($routeBase, ENT_QUOTES, 'UTF-8'); ?>">Log in</a></p>
            </form>
        </section>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script src="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>js/script.js"></script>
</body>
</html>
