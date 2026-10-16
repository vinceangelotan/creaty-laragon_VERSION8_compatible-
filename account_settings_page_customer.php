<?php
if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {
    header('Location: customer-account-settings/');
    exit;
}

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$assetBase = $assetBase ?? '';
$homePath = $homePath ?? '';
$loginPath = $loginPath ?? 'customer-login/';
$accountSettingsPath = $accountSettingsPath ?? $assetBase . 'customer-account-settings/';
$logoutPath = $logoutPath ?? $assetBase . 'customer-logout/';
$cartPath = $cartPath ?? $assetBase . 'customer-cart/';
$eventsPath = $eventsPath ?? $assetBase . 'customer-events/';
$servicesPath = $servicesPath ?? $assetBase . 'customer-services/';

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/customer_gcash_profiles_repository.php';
require_once __DIR__ . '/customer_notifications_center.php';

$isCustomerLoggedIn = isset($_SESSION['customer_id']);
if (!$isCustomerLoggedIn) {
    $currentPageUrl = $_SERVER['REQUEST_URI'] ?? ($assetBase . 'customer-account-settings/');
    header('Location: ' . $loginPath . '?redirect=' . rawurlencode($currentPageUrl));
    exit;
}

function customer_account_skill_level_options()
{
    return ['Beginner', 'Professional'];
}

function default_customer_account_skill_level()
{
    return customer_account_skill_level_options()[0];
}

function normalize_customer_account_skill_level($value)
{
    $candidate = trim((string) $value);

    foreach (customer_account_skill_level_options() as $option) {
        if (strcasecmp($candidate, (string) $option) === 0) {
            return (string) $option;
        }
    }

    return default_customer_account_skill_level();
}

function normalize_customer_account_phone($value)
{
    $rawPhone = trim((string) $value);

    if ($rawPhone === '') {
        return '';
    }

    $digits = preg_replace('/\D+/', '', $rawPhone) ?? '';

    if (strlen($digits) === 12 && strpos($digits, '63') === 0) {
        $digits = '0' . substr($digits, 2);
    }

    if (strlen($digits) === 10 && strpos($digits, '9') === 0) {
        $digits = '0' . $digits;
    }

    if (strlen($digits) !== 11 || strpos($digits, '09') !== 0) {
        return '';
    }

    return $digits;
}

$cartCount = (int) ($_SESSION['customer_cart_count'] ?? 0);
$accountLabel = 'Account';
$customerNotificationCenter = build_customer_notification_center($assetBase, $isCustomerLoggedIn);

$fullName = trim((string) ($_SESSION['customer_name'] ?? ''));
$nameParts = preg_split('/\s+/', $fullName) ?: [];
$firstNameDefault = $nameParts[0] ?? '';
$lastNameDefault = count($nameParts) > 1 ? implode(' ', array_slice($nameParts, 1)) : '';
$emailDefault = (string) ($_SESSION['customer_email'] ?? '');
$phoneDefault = normalize_customer_account_phone($_SESSION['customer_phone'] ?? '');

$customerAccountsTable = $customerAccountsTable ?? 'customer_accounts';
$customerId = (string) ($_SESSION['customer_id'] ?? '');
$customerSkillLevelOptions = customer_account_skill_level_options();
$skillLevelValue = default_customer_account_skill_level();

if ($customerId !== '') {
    $customerProfileStmt = $conn->prepare("SELECT first_name, last_name, email, customer_phone, skill_level FROM {$customerAccountsTable} WHERE id = ? LIMIT 1");

    if ($customerProfileStmt instanceof mysqli_stmt) {
        $customerIdInt = (int) $customerId;
        $customerProfileStmt->bind_param('i', $customerIdInt);
        $customerProfileStmt->execute();
        $customerProfileResult = $customerProfileStmt->get_result();
        $customerProfile = $customerProfileResult ? $customerProfileResult->fetch_assoc() : null;
        $customerProfileStmt->close();

        if (is_array($customerProfile)) {
            $resolvedFirstName = trim((string) ($customerProfile['first_name'] ?? ''));
            $resolvedLastName = trim((string) ($customerProfile['last_name'] ?? ''));
            $resolvedEmail = trim((string) ($customerProfile['email'] ?? ''));

            if ($resolvedFirstName !== '') {
                $firstNameDefault = $resolvedFirstName;
            }

            if ($resolvedLastName !== '') {
                $lastNameDefault = $resolvedLastName;
            }

            if ($resolvedEmail !== '') {
                $emailDefault = $resolvedEmail;
            }

            $resolvedPhone = normalize_customer_account_phone($customerProfile['customer_phone'] ?? $phoneDefault);

            if ($resolvedPhone !== '') {
                $phoneDefault = $resolvedPhone;
            }

            $skillLevelValue = normalize_customer_account_skill_level($customerProfile['skill_level'] ?? $skillLevelValue);
        }
    }
}

$firstNameValue = $firstNameDefault;
$lastNameValue = $lastNameDefault;
$emailValue = $emailDefault;
$phoneValue = $phoneDefault;
$customerGcashProfile = find_customer_gcash_profile_for_customer($customerId);
$gcashNameValue = (string) ($customerGcashProfile['gcash_name'] ?? '');
$gcashNumberValue = (string) ($customerGcashProfile['gcash_number'] ?? '');
$addressOneValue = '';
$addressTwoValue = '';
$cityValue = '';
$provinceValue = '';
$postalValue = '';
$infoMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formAction = trim((string) ($_POST['form_action'] ?? ''));

    $firstNameValue = trim($_POST['first_name'] ?? '');
    $lastNameValue = trim($_POST['last_name'] ?? '');
    $emailValue = trim($_POST['email'] ?? '');
    $phoneValue = normalize_customer_account_phone($_POST['phone'] ?? '');
    $skillLevelValue = normalize_customer_account_skill_level($_POST['skill_level'] ?? $skillLevelValue);
    $gcashNameValue = trim($_POST['gcash_name'] ?? $gcashNameValue);
    $gcashNumberValue = trim($_POST['gcash_number'] ?? $gcashNumberValue);
    $addressOneValue = trim($_POST['address_line_one'] ?? '');
    $addressTwoValue = trim($_POST['address_line_two'] ?? '');
    $cityValue = trim($_POST['city'] ?? '');
    $provinceValue = trim($_POST['province'] ?? '');
    $postalValue = trim($_POST['postal_code'] ?? '');

    if ($formAction === 'profile_info') {
        if ($phoneValue === '') {
            $infoMessage = 'Please enter a valid contact number (ex. 09XXXXXXXXX).';
        } else {
            $updateSkillStmt = $conn->prepare("UPDATE {$customerAccountsTable} SET skill_level = ?, customer_phone = ? WHERE id = ? LIMIT 1");

            if ($updateSkillStmt instanceof mysqli_stmt) {
                $customerIdInt = (int) $customerId;
                $updateSkillStmt->bind_param('ssi', $skillLevelValue, $phoneValue, $customerIdInt);

                if ($updateSkillStmt->execute()) {
                    $_SESSION['customer_skill_level'] = $skillLevelValue;
                    $_SESSION['customer_phone'] = $phoneValue;
                    $infoMessage = 'Skill level and contact number updated successfully. Other personal information fields are still preview only.';
                } else {
                    $infoMessage = 'Unable to save profile details right now.';
                }

                $updateSkillStmt->close();
            } else {
                $infoMessage = 'Unable to save profile details right now.';
            }
        }
    } elseif ($formAction === 'gcash_info') {
        $savedGcashProfile = upsert_customer_gcash_profile_for_customer($customerId, $gcashNameValue, $gcashNumberValue);

        if (is_array($savedGcashProfile)) {
            $gcashNameValue = (string) ($savedGcashProfile['gcash_name'] ?? '');
            $gcashNumberValue = (string) ($savedGcashProfile['gcash_number'] ?? '');
            $infoMessage = 'GCash information updated successfully.';
        } else {
            $infoMessage = 'Unable to save GCash information right now.';
        }
    } elseif ($formAction === 'address_info') {
        $infoMessage = 'Address updated for preview only. Profile saving will be added in a future update.';
    } else {
        $infoMessage = 'Changes are for preview only. Profile saving will be added in a future update.';
    }
}

$displayName = trim($firstNameValue . ' ' . $lastNameValue);
$displayName = $displayName !== '' ? $displayName : 'None';
$displayEmail = $emailValue !== '' ? $emailValue : 'None';
$displayPhone = $phoneValue !== '' ? $phoneValue : 'None';
$displaySkillLevel = $skillLevelValue !== '' ? $skillLevelValue : default_customer_account_skill_level();
$displayGcashName = $gcashNameValue !== '' ? $gcashNameValue : 'None';
$displayGcashNumber = $gcashNumberValue !== '' ? $gcashNumberValue : 'None';

$addressParts = array_filter([
    $addressOneValue,
    $addressTwoValue,
    $cityValue,
    $provinceValue,
    $postalValue
], static function ($part) {
    return $part !== '';
});

$displayAddress = count($addressParts) ? implode(', ', $addressParts) : 'No address yet.';

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>The Nifty Fifty | Profile</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>css/style.css?v=20261008-2">
</head>
<body class="account-settings-page">
    <header class="site-header">
        <div class="topbar">
            <a class="brand-badge" href="<?php echo htmlspecialchars($homePath, ENT_QUOTES, 'UTF-8'); ?>">
                <img src="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>assets/images/main_logo.png" alt="The Nifty Fifty">
            </a>

            <a class="topbar-link topbar-help" href="#">
                <img src="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>assets/icons/help_icon.svg" alt="">
                <span>Help</span>
            </a>

            <form class="topbar-search" action="#" method="get">
                <input type="search" name="q" placeholder="Search cameras, services, or rentals">
            </form>

            <a class="topbar-cart" href="<?php echo htmlspecialchars($cartPath, ENT_QUOTES, 'UTF-8'); ?>" aria-label="Reservation">
                <img src="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>assets/icons/cart_icon.svg" alt="">
                <span class="cart-count"><?php echo $cartCount; ?></span>
            </a>

            <a class="topbar-link" href="#" data-message-us-open>Message us</a>
            <div class="dropdown topbar-account-menu">
                <button class="account-pill account-pill-toggle dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <?php echo htmlspecialchars($accountLabel, ENT_QUOTES, 'UTF-8'); ?>
                </button>
                <ul class="dropdown-menu dropdown-menu-end account-dropdown-menu">
                    <li><a class="dropdown-item" href="<?php echo htmlspecialchars($accountSettingsPath, ENT_QUOTES, 'UTF-8'); ?>">Account Settings</a></li>
                    <li><a class="dropdown-item" href="<?php echo htmlspecialchars($cartPath, ENT_QUOTES, 'UTF-8'); ?>">My Reservation</a></li>
                    <li><a class="dropdown-item" href="<?php echo htmlspecialchars($cartPath . '?view=history', ENT_QUOTES, 'UTF-8'); ?>">Reservation History</a></li>
                    <li><a class="dropdown-item" href="<?php echo htmlspecialchars($eventsPath, ENT_QUOTES, 'UTF-8'); ?>">Browse Events</a></li>
                    <li><a class="dropdown-item" href="<?php echo htmlspecialchars($servicesPath, ENT_QUOTES, 'UTF-8'); ?>">Browse Services</a></li>
                    <li><a class="dropdown-item" href="#">Help Center</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item account-logout-item" href="<?php echo htmlspecialchars($logoutPath, ENT_QUOTES, 'UTF-8'); ?>">Log Out</a></li>
                </ul>

                <?php render_customer_notification_trigger_button($customerNotificationCenter, $assetBase); ?>
            </div>
        </div>

        <nav class="section-nav section-nav-disabled section-nav-with-history" aria-label="Catalog filters" hidden style="display: none;">
            <span class="section-nav-filter is-disabled" aria-disabled="true">PROFILE</span>
            <span class="section-nav-section is-disabled" aria-disabled="true">PERSONAL INFORMATION</span>
            <span class="section-nav-filter is-disabled" aria-disabled="true">ADDRESS</span>
            <a class="section-nav-history-link" href="<?php echo htmlspecialchars($cartPath . '?view=history', ENT_QUOTES, 'UTF-8'); ?>">Reservation History</a>
        </nav>
    </header>

    <main class="account-settings-shell">
        <section class="account-settings-card reveal">
            <div class="account-settings-head">
                <h1>Profile</h1>
                <p>Manage your personal information, GCash info, and address.</p>
            </div>

            <?php if ($infoMessage !== ''): ?>
                <p class="form-message form-message-success"><?php echo htmlspecialchars($infoMessage, ENT_QUOTES, 'UTF-8'); ?></p>
            <?php endif; ?>

            <section class="profile-section-card" aria-labelledby="profile-personal-heading">
                <div class="profile-section-head">
                    <h2 id="profile-personal-heading">Personal Information</h2>
                </div>

                <div class="profile-info-grid" aria-label="Personal information details">
                    <div class="profile-info-item">
                        <span>Name</span>
                        <strong><?php echo htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8'); ?></strong>
                    </div>
                    <div class="profile-info-item">
                        <span>Email</span>
                        <strong><?php echo htmlspecialchars($displayEmail, ENT_QUOTES, 'UTF-8'); ?></strong>
                    </div>
                    <div class="profile-info-item">
                        <span>Contact Number</span>
                        <strong><?php echo htmlspecialchars($displayPhone, ENT_QUOTES, 'UTF-8'); ?></strong>
                    </div>
                    <div class="profile-info-item">
                        <span>Skill Level</span>
                        <strong><?php echo htmlspecialchars($displaySkillLevel, ENT_QUOTES, 'UTF-8'); ?></strong>
                    </div>
                </div>

                <div class="profile-section-actions">
                    <button type="button" class="profile-action-button" data-profile-toggle="profile-info-editor">Edit Info</button>
                </div>

                <form id="profile-info-editor" class="profile-editor-panel" action="" method="post" hidden novalidate>
                    <input type="hidden" name="form_action" value="profile_info">
                    <input type="hidden" name="gcash_name" value="<?php echo htmlspecialchars($gcashNameValue, ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="gcash_number" value="<?php echo htmlspecialchars($gcashNumberValue, ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="address_line_one" value="<?php echo htmlspecialchars($addressOneValue, ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="address_line_two" value="<?php echo htmlspecialchars($addressTwoValue, ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="city" value="<?php echo htmlspecialchars($cityValue, ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="province" value="<?php echo htmlspecialchars($provinceValue, ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="postal_code" value="<?php echo htmlspecialchars($postalValue, ENT_QUOTES, 'UTF-8'); ?>">

                    <div class="account-settings-grid">
                        <label>
                            <span>First Name</span>
                            <input type="text" name="first_name" value="<?php echo htmlspecialchars($firstNameValue, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Enter first name">
                        </label>

                        <label>
                            <span>Last Name</span>
                            <input type="text" name="last_name" value="<?php echo htmlspecialchars($lastNameValue, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Enter last name">
                        </label>

                        <label>
                            <span>Email</span>
                            <input type="email" name="email" value="<?php echo htmlspecialchars($emailValue, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Email address">
                        </label>

                        <label>
                            <span>Contact Number</span>
                            <input type="text" name="phone" value="<?php echo htmlspecialchars($phoneValue, ENT_QUOTES, 'UTF-8'); ?>" placeholder="09xx xxx xxxx" inputmode="numeric" pattern="^09[0-9]{9}$" maxlength="11" required>
                        </label>

                        <label>
                            <span>Skill Level</span>
                            <select name="skill_level" required>
                                <?php foreach ($customerSkillLevelOptions as $skillOption): ?>
                                    <option value="<?php echo htmlspecialchars((string) $skillOption, ENT_QUOTES, 'UTF-8'); ?>"<?php echo strcasecmp($skillLevelValue, (string) $skillOption) === 0 ? ' selected' : ''; ?>>
                                        <?php echo htmlspecialchars((string) $skillOption, ENT_QUOTES, 'UTF-8'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                    </div>

                    <div class="account-settings-actions">
                        <button type="submit">Save Info</button>
                        <button type="button" class="profile-action-button is-ghost" data-profile-cancel="profile-info-editor">Cancel</button>
                    </div>
                </form>
            </section>

            <section class="profile-section-card" aria-labelledby="profile-gcash-heading">
                <div class="profile-section-head">
                    <h2 id="profile-gcash-heading">GCash Info</h2>
                </div>

                <div class="profile-info-grid" aria-label="GCash information details">
                    <div class="profile-info-item">
                        <span>GCash Name</span>
                        <strong><?php echo htmlspecialchars($displayGcashName, ENT_QUOTES, 'UTF-8'); ?></strong>
                    </div>
                    <div class="profile-info-item">
                        <span>GCash Number</span>
                        <strong><?php echo htmlspecialchars($displayGcashNumber, ENT_QUOTES, 'UTF-8'); ?></strong>
                    </div>
                </div>

                <div class="profile-section-actions">
                    <button type="button" class="profile-action-button" data-profile-toggle="profile-gcash-editor">Edit GCash Info</button>
                </div>

                <form id="profile-gcash-editor" class="profile-editor-panel" action="" method="post" hidden novalidate>
                    <input type="hidden" name="form_action" value="gcash_info">
                    <input type="hidden" name="first_name" value="<?php echo htmlspecialchars($firstNameValue, ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="last_name" value="<?php echo htmlspecialchars($lastNameValue, ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="email" value="<?php echo htmlspecialchars($emailValue, ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="phone" value="<?php echo htmlspecialchars($phoneValue, ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="address_line_one" value="<?php echo htmlspecialchars($addressOneValue, ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="address_line_two" value="<?php echo htmlspecialchars($addressTwoValue, ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="city" value="<?php echo htmlspecialchars($cityValue, ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="province" value="<?php echo htmlspecialchars($provinceValue, ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="postal_code" value="<?php echo htmlspecialchars($postalValue, ENT_QUOTES, 'UTF-8'); ?>">

                    <div class="account-settings-grid">
                        <label>
                            <span>GCash Name</span>
                            <input type="text" name="gcash_name" value="<?php echo htmlspecialchars($gcashNameValue, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Enter GCash account name">
                        </label>

                        <label>
                            <span>GCash Number</span>
                            <input type="text" name="gcash_number" value="<?php echo htmlspecialchars($gcashNumberValue, ENT_QUOTES, 'UTF-8'); ?>" placeholder="09xx xxx xxxx">
                        </label>
                    </div>

                    <div class="account-settings-actions">
                        <button type="submit">Save GCash Info</button>
                        <button type="button" class="profile-action-button is-ghost" data-profile-cancel="profile-gcash-editor">Cancel</button>
                    </div>
                </form>
            </section>

            <section class="profile-section-card" aria-labelledby="profile-address-heading">
                <div class="profile-section-head">
                    <h2 id="profile-address-heading">Address</h2>
                </div>

                <p class="profile-address-copy"><?php echo htmlspecialchars($displayAddress, ENT_QUOTES, 'UTF-8'); ?></p>

                <div class="profile-section-actions">
                    <button type="button" class="profile-action-button" data-profile-toggle="profile-address-editor">
                        <span class="profile-action-icon" aria-hidden="true">+</span>
                        <span>Add Address</span>
                    </button>
                </div>

                <form id="profile-address-editor" class="profile-editor-panel" action="" method="post" hidden novalidate>
                    <input type="hidden" name="form_action" value="address_info">
                    <input type="hidden" name="first_name" value="<?php echo htmlspecialchars($firstNameValue, ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="last_name" value="<?php echo htmlspecialchars($lastNameValue, ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="email" value="<?php echo htmlspecialchars($emailValue, ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="phone" value="<?php echo htmlspecialchars($phoneValue, ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="gcash_name" value="<?php echo htmlspecialchars($gcashNameValue, ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="gcash_number" value="<?php echo htmlspecialchars($gcashNumberValue, ENT_QUOTES, 'UTF-8'); ?>">

                    <div class="account-settings-grid">
                        <label class="account-field-wide">
                            <span>Address Line 1</span>
                            <input type="text" name="address_line_one" value="<?php echo htmlspecialchars($addressOneValue, ENT_QUOTES, 'UTF-8'); ?>" placeholder="House number and street">
                        </label>

                        <label class="account-field-wide">
                            <span>Address Line 2</span>
                            <input type="text" name="address_line_two" value="<?php echo htmlspecialchars($addressTwoValue, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Barangay, subdivision, or landmark">
                        </label>

                        <label>
                            <span>City / Municipality</span>
                            <input type="text" name="city" value="<?php echo htmlspecialchars($cityValue, ENT_QUOTES, 'UTF-8'); ?>" placeholder="City">
                        </label>

                        <label>
                            <span>Province</span>
                            <input type="text" name="province" value="<?php echo htmlspecialchars($provinceValue, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Province">
                        </label>

                        <label>
                            <span>Postal Code</span>
                            <input type="text" name="postal_code" value="<?php echo htmlspecialchars($postalValue, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Postal code">
                        </label>
                    </div>

                    <div class="account-settings-actions">
                        <button type="submit">Save Address</button>
                        <button type="button" class="profile-action-button is-ghost" data-profile-cancel="profile-address-editor">Cancel</button>
                    </div>
                </form>
            </section>

        </section>
    </main>

    <?php require __DIR__ . '/customer_message_modal.php'; ?>
    <?php render_customer_notification_modal($customerNotificationCenter); ?>
    <?php render_customer_notification_center_bootstrap_script($customerNotificationCenter, $assetBase); ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script src="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>js/script.js?v=20260923-1"></script>
</body>
</html>
