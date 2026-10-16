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

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/mailer.php'; // NEW: loads the email-sending function

$customerAccountsTable = $customerAccountsTable ?? 'customer_accounts';

function customer_signup_skill_level_options()
{
    return ['Beginner', 'Professional'];
}

function normalize_customer_signup_skill_level($value, $allowEmpty = false)
{
    $candidate = trim((string) $value);

    if ($candidate === '' && $allowEmpty) {
        return '';
    }

    foreach (customer_signup_skill_level_options() as $option) {
        if (strcasecmp($candidate, (string) $option) === 0) {
            return (string) $option;
        }
    }

    return $allowEmpty ? '' : customer_signup_skill_level_options()[0];
}

$errorMessage = '';
$firstNameValue = '';
$lastNameValue = '';
$emailValue = '';
$customerSkillLevelOptions = customer_signup_skill_level_options();
$skillLevelValue = normalize_customer_signup_skill_level($_POST['skill_level'] ?? '', true);
$privacyAccepted = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstNameValue = trim($_POST['first_name'] ?? '');
    $lastNameValue = trim($_POST['last_name'] ?? '');
    $emailValue = trim($_POST['email'] ?? '');
    $skillLevelValue = normalize_customer_signup_skill_level($_POST['skill_level'] ?? '', true);
    $passwordValue = $_POST['password'] ?? '';
    $confirmPasswordValue = $_POST['confirm_password'] ?? '';
    $privacyAccepted = isset($_POST['privacy_policy']);

    if ($firstNameValue === '' || $lastNameValue === '' || $emailValue === '' || $passwordValue === '' || $confirmPasswordValue === '') {
        $errorMessage = 'Please complete all fields.';
    } elseif ($skillLevelValue === '') {
        $errorMessage = 'Please select your skill level.';
    } elseif (!filter_var($emailValue, FILTER_VALIDATE_EMAIL)) {
        $errorMessage = 'Please enter a valid email address.';
    } elseif ($passwordValue !== $confirmPasswordValue) {
        $errorMessage = 'Passwords do not match.';
    } elseif (!$privacyAccepted) {
        $errorMessage = 'You must accept the Privacy Policy to create an account.';
    } else {
        $checkStmt = $conn->prepare("SELECT id FROM {$customerAccountsTable} WHERE email = ? LIMIT 1");
        $checkStmt->bind_param('s', $emailValue);
        $checkStmt->execute();
        $existingCustomerResult = $checkStmt->get_result();
        $existingCustomer = $existingCustomerResult->fetch_assoc();
        $checkStmt->close();

        if ($existingCustomer) {
            $errorMessage = 'An account with that email already exists.';
        } else {
            $hashedPassword = password_hash($passwordValue, PASSWORD_DEFAULT);
            $insertStmt = $conn->prepare("INSERT INTO {$customerAccountsTable} (first_name, last_name, email, skill_level, password, privacy_policy_accepted_at) VALUES (?, ?, ?, ?, ?, NOW())");
            $insertStmt->bind_param('sssss', $firstNameValue, $lastNameValue, $emailValue, $skillLevelValue, $hashedPassword);

            if ($insertStmt->execute()) {
                // CHANGED: send the verification code email right after the account is created
                $newCustomerId = (int) $insertStmt->insert_id;
                $insertStmt->close();

                $_SESSION['pending_customer_verification_id'] = $newCustomerId;
                send_customer_verification_code($conn, $customerAccountsTable, $newCustomerId, $emailValue, $firstNameValue);

                header('Location: ' . $routeBase . 'customer-verify-email/');
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
    <title>Sign Up | Creaty</title>
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

            <h1 class="login-title">SIGN UP</h1>

            <form class="customer-signup-form" action="#" method="post" novalidate>
                <?php if ($errorMessage !== ''): ?>
                    <p class="form-message form-message-error"><?php echo htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8'); ?></p>
                <?php endif; ?>

                <div class="signup-fields">
                    <label class="sr-only" for="customer_first_name">First name</label>
                    <div class="input-row input-row-plain">
                        <input id="customer_first_name" name="first_name" type="text" placeholder="First name" autocomplete="given-name" value="<?php echo htmlspecialchars($firstNameValue, ENT_QUOTES, 'UTF-8'); ?>">
                    </div>

                    <label class="sr-only" for="customer_last_name">Last name</label>
                    <div class="input-row input-row-plain">
                        <input id="customer_last_name" name="last_name" type="text" placeholder="Last name" autocomplete="family-name" value="<?php echo htmlspecialchars($lastNameValue, ENT_QUOTES, 'UTF-8'); ?>">
                    </div>

                    <label class="sr-only" for="customer_signup_email">Email address</label>
                    <div class="input-row">
                        <span class="field-icon" aria-hidden="true">
                            <img src="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>assets/icons/email_icon.svg" alt="">
                        </span>
                        <input id="customer_signup_email" name="email" type="email" placeholder="Email address" autocomplete="email" value="<?php echo htmlspecialchars($emailValue, ENT_QUOTES, 'UTF-8'); ?>">
                        <span class="field-icon" aria-hidden="true"></span>
                    </div>

                    <label class="sr-only" for="customer_signup_password">Password</label>
                    <div class="input-row">
                        <span class="field-icon" aria-hidden="true">
                            <img src="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>assets/icons/password_icon.svg" alt="">
                        </span>
                        <input id="customer_signup_password" name="password" type="password" placeholder="Create a password" autocomplete="new-password">
                        <button
                            class="toggle-visibility"
                            type="button"
                            aria-label="Show password"
                            data-target="customer_signup_password"
                            data-icon-on="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>assets/icons/visibility_on.svg"
                            data-icon-off="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>assets/icons/visibility_off.svg"
                        >
                            <img src="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>assets/icons/visibility_off.svg" alt="">
                        </button>
                    </div>

                    <label class="sr-only" for="customer_signup_confirm_password">Confirm password</label>
                    <div class="input-row">
                        <span class="field-icon" aria-hidden="true">
                            <img src="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>assets/icons/password_icon.svg" alt="">
                        </span>
                        <input id="customer_signup_confirm_password" name="confirm_password" type="password" placeholder="Re-enter your password" autocomplete="new-password">
                        <button
                            class="toggle-visibility"
                            type="button"
                            aria-label="Show password"
                            data-target="customer_signup_confirm_password"
                            data-icon-on="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>assets/icons/visibility_on.svg"
                            data-icon-off="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>assets/icons/visibility_off.svg"
                        >
                            <img src="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>assets/icons/visibility_off.svg" alt="">
                        </button>
                    </div>

                    <label class="sr-only" for="customer_signup_skill_level">Skill Level</label>
                    <div class="input-row input-row-plain">
                        <select id="customer_signup_skill_level" name="skill_level" aria-label="Skill Level" required>
                            <option value="" disabled<?php echo $skillLevelValue === '' ? ' selected' : ''; ?>>Skill Level</option>
                            <?php foreach ($customerSkillLevelOptions as $skillOption): ?>
                                <option value="<?php echo htmlspecialchars((string) $skillOption, ENT_QUOTES, 'UTF-8'); ?>"<?php echo strcasecmp($skillLevelValue, (string) $skillOption) === 0 ? ' selected' : ''; ?>>
                                    <?php echo htmlspecialchars((string) $skillOption, ENT_QUOTES, 'UTF-8'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <label class="policy-check" for="privacy_policy">
                    <input id="privacy_policy" name="privacy_policy" type="checkbox" required <?php echo $privacyAccepted ? 'checked' : ''; ?>>
                    <span>I agree to the <a href="<?php echo htmlspecialchars($customerPrivacyPolicyPath, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer">Privacy Policy</a>.</span>
                </label>

                <button class="login-submit" type="submit">CREATE ACCOUNT</button>

                <p class="switch-auth">Already a Member? <a href="<?php echo htmlspecialchars($customerLoginPath, ENT_QUOTES, 'UTF-8'); ?>">Log in</a></p>
            </form>
        </section>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script src="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>js/script.js"></script>
</body>
</html>