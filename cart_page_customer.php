<?php
if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {
    header('Location: customer-cart/');
    exit;
}

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$assetBase = $assetBase ?? '';
$homePath = $homePath ?? '';
$loginPath = $loginPath ?? 'customer-login/';
$productListPath = $homePath . '#featured-products-title';

$isCustomerLoggedIn = isset($_SESSION['customer_id']);
$cartCount = $isCustomerLoggedIn ? (int) ($_SESSION['customer_cart_count'] ?? 0) : 0;
$accountLabel = $isCustomerLoggedIn ? 'Account' : 'Sign In';
$accountSettingsPath = $assetBase . 'customer-account-settings/';
$logoutPath = $assetBase . 'customer-logout/';
$cartPath = $assetBase . 'customer-cart/';
$reservationHistoryPath = $cartPath . '?view=history';
$eventsPath = $assetBase . 'customer-events/';
$servicesPath = $assetBase . 'customer-services/';

require_once __DIR__ . '/config/products_repository.php';
require_once __DIR__ . '/config/event_packages_repository.php';
require_once __DIR__ . '/config/services_packages_repository.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/customer_orders_repository.php';
require_once __DIR__ . '/config/gcash_qr_repository.php';
require_once __DIR__ . '/config/customer_notifications_repository.php';
require_once __DIR__ . '/config/customer_gcash_profiles_repository.php';
require_once __DIR__ . '/config/customer_terms_repository.php';
require_once __DIR__ . '/config/meeting_places_repository.php';

function normalize_cart_package_camera_key($value): string
{
    $normalized = strtolower(trim((string) $value));
    $normalized = preg_replace('/[^a-z0-9-]+/', '-', $normalized) ?? $normalized;

    return trim((string) $normalized, '-');
}

function map_cart_package_camera_catalog($repository): array
{
    if (!is_array($repository)) {
        return [];
    }

    $catalog = [];

    foreach ($repository as $packageKey => $packageRecord) {
        if (!is_string($packageKey) || trim($packageKey) === '' || !is_array($packageRecord)) {
            continue;
        }

        if (!empty($packageRecord['archived'])) {
            continue;
        }

        $normalizedPackageKey = normalize_cart_package_camera_key($packageKey);
        if ($normalizedPackageKey === '') {
            continue;
        }

        $catalog[$normalizedPackageKey] = [
            'camera1' => normalize_cart_package_camera_key($packageRecord['camera1'] ?? ''),
            'camera2' => normalize_cart_package_camera_key($packageRecord['camera2'] ?? ''),
            'backupCamera1' => normalize_cart_package_camera_key($packageRecord['backupCamera1'] ?? ''),
            'backupCamera2' => normalize_cart_package_camera_key($packageRecord['backupCamera2'] ?? ''),
        ];
    }

    return $catalog;
}

function cart_history_asset_url(string $assetBase, string $path, string $fallback = ''): string
{
    $normalizedPath = ltrim(trim(str_replace('\\', '/', $path)), '/');

    if ($normalizedPath === '') {
        return $fallback;
    }

    if (preg_match('/^(?:https?:)?\/\//i', $normalizedPath)) {
        return $normalizedPath;
    }

    return $assetBase . $normalizedPath;
}

function cart_history_discounted_price($priceValue, $discountPercent): float
{
    $price = max(0, (float) $priceValue);
    $discount = max(0, min(95, (int) $discountPercent));

    if ($discount <= 0) {
        return $price;
    }

    return $price - ($price * ($discount / 100));
}

function build_cart_catalog_payload(array $productsRepository, array $eventPackagesRepository, array $servicePackagesRepository, string $assetBase): array
{
    $fallbackImage = $assetBase . 'assets/images/main_logo.png';
    $catalog = [];

    foreach ($productsRepository as $productKey => $productRecord) {
        if (!is_string($productKey) || trim($productKey) === '' || !is_array($productRecord)) {
            continue;
        }

        $itemId = 'camera-' . trim($productKey);
        $nameParts = [
            trim((string) ($productRecord['brand'] ?? '')),
            trim((string) ($productRecord['name'] ?? '')),
        ];
        $name = trim(implode(' ', array_filter($nameParts, static function ($value): bool {
            return $value !== '';
        })));

        $catalog[$itemId] = [
            'id' => $itemId,
            'type' => 'camera',
            'productKey' => trim($productKey),
            'name' => $name !== '' ? $name : 'Camera',
            'copy' => trim((string) ($productRecord['tagline'] ?? '')),
            'image' => cart_history_asset_url($assetBase, (string) ($productRecord['cameraImage'] ?? ''), $fallbackImage),
            'price' => cart_history_discounted_price($productRecord['price'] ?? 0, $productRecord['discountPercent'] ?? 0),
            'originalPrice' => max(0, (float) ($productRecord['price'] ?? 0)),
            'discountPercent' => max(0, min(95, (int) ($productRecord['discountPercent'] ?? 0))),
        ];
    }

    foreach ($eventPackagesRepository as $packageKey => $packageRecord) {
        if (!is_string($packageKey) || trim($packageKey) === '' || !is_array($packageRecord) || !empty($packageRecord['archived'])) {
            continue;
        }

        $itemId = 'event-' . trim($packageKey);
        $thumbnailImages = is_array($packageRecord['thumbnail_images'] ?? null)
            ? array_values($packageRecord['thumbnail_images'])
            : [];
        $imagePath = isset($thumbnailImages[0]) ? (string) $thumbnailImages[0] : '';
        $title = trim((string) ($packageRecord['title'] ?? 'EVENT PACKAGE'));

        $catalog[$itemId] = [
            'id' => $itemId,
            'type' => 'event-package',
            'productKey' => trim($packageKey),
            'name' => $title !== '' ? $title : 'EVENT PACKAGE',
            'copy' => 'Event package with curated coverage style and sample gallery references.',
            'image' => cart_history_asset_url($assetBase, $imagePath, $fallbackImage),
            'price' => cart_history_discounted_price($packageRecord['price'] ?? 0, $packageRecord['discountPercent'] ?? 0),
            'originalPrice' => max(0, (float) ($packageRecord['price'] ?? 0)),
            'discountPercent' => max(0, min(95, (int) ($packageRecord['discountPercent'] ?? 0))),
        ];
    }

    foreach ($servicePackagesRepository as $packageKey => $packageRecord) {
        if (!is_string($packageKey) || trim($packageKey) === '' || !is_array($packageRecord) || !empty($packageRecord['archived'])) {
            continue;
        }

        $itemId = 'service-' . trim($packageKey);
        $thumbnailImages = is_array($packageRecord['thumbnail_images'] ?? null)
            ? array_values($packageRecord['thumbnail_images'])
            : [];
        $imagePath = isset($thumbnailImages[0]) ? (string) $thumbnailImages[0] : '';
        $title = trim((string) ($packageRecord['title'] ?? 'SERVICE PACKAGE'));

        $catalog[$itemId] = [
            'id' => $itemId,
            'type' => 'service-package',
            'productKey' => trim($packageKey),
            'servicePackageKey' => trim($packageKey),
            'name' => $title !== '' ? $title : 'SERVICE PACKAGE',
            'copy' => trim((string) ($packageRecord['description'] ?? '')),
            'image' => cart_history_asset_url($assetBase, $imagePath, $fallbackImage),
            'price' => cart_history_discounted_price($packageRecord['price'] ?? 0, $packageRecord['discountPercent'] ?? 0),
            'originalPrice' => max(0, (float) ($packageRecord['price'] ?? 0)),
            'discountPercent' => max(0, min(95, (int) ($packageRecord['discountPercent'] ?? 0))),
            'durationUnit' => (string) ($packageRecord['durationUnit'] ?? 'hours'),
            'durationValue' => (int) ($packageRecord['durationValue'] ?? 1),
        ];
    }

    return $catalog;
}

$productsRepository = load_products_repository();
$eventPackagesRepository = load_event_packages_repository();
$servicePackagesRepository = load_services_packages_repository();
$eventPackageCameraCatalog = map_cart_package_camera_catalog($eventPackagesRepository);
$servicePackageCameraCatalog = map_cart_package_camera_catalog($servicePackagesRepository);
$availableCartItemIds = [];

if (is_array($productsRepository)) {
    foreach ($productsRepository as $productKey => $productRecord) {
        if (!is_string($productKey) || trim($productKey) === '' || !is_array($productRecord)) {
            continue;
        }

        $availableCartItemIds[] = 'camera-' . trim($productKey);
    }
}

if (is_array($eventPackagesRepository)) {
    foreach ($eventPackagesRepository as $packageKey => $packageRecord) {
        if (!is_string($packageKey) || trim($packageKey) === '' || !is_array($packageRecord)) {
            continue;
        }

        if (!empty($packageRecord['archived'])) {
            continue;
        }

        $availableCartItemIds[] = 'event-' . trim($packageKey);
    }
}

if (is_array($servicePackagesRepository)) {
    foreach ($servicePackagesRepository as $packageKey => $packageRecord) {
        if (!is_string($packageKey) || trim($packageKey) === '' || !is_array($packageRecord)) {
            continue;
        }

        if (!empty($packageRecord['archived'])) {
            continue;
        }

        $availableCartItemIds[] = 'service-' . trim($packageKey);
    }
}

$availableCartItemIds = array_values(array_unique($availableCartItemIds));
$cartCatalogPayload = build_cart_catalog_payload(
    is_array($productsRepository) ? $productsRepository : [],
    is_array($eventPackagesRepository) ? $eventPackagesRepository : [],
    is_array($servicePackagesRepository) ? $servicePackagesRepository : [],
    $assetBase
);
$equipmentAvailability = customer_order_build_equipment_availability_payload([
    'horizon_days' => 1095,
]);
$orderSubmitEndpoint = $assetBase . 'customer_order_submit.php';
$orderCancelEndpoint = $assetBase . 'customer_order_cancel.php';
$orderReceiptUploadEndpoint = $assetBase . 'customer_order_upload_receipt.php';
$orderDeliveryReceiptUploadEndpoint = $assetBase . 'customer_order_upload_delivery_receipt.php';
$customerOrders = [];
$customerOrdersSignature = '';
$customerNotifications = [];
$customerNotificationsForFrontend = [];
$customerNotificationUnreadCount = 0;
$customerNotificationLiveUpdatesEndpoint = $assetBase . 'customer_notifications_live_updates.php';
$customerNotificationMarkReadEndpoint = $assetBase . 'customer_notifications_mark_read.php';
$requestedCartView = strtolower(trim((string) ($_GET['view'] ?? 'cart')));
$allowedCartViews = ['cart', 'services-cart', 'order-status', 'history'];
$initialCartView = in_array($requestedCartView, $allowedCartViews, true) ? $requestedCartView : 'cart';
$isServicesCartInitialView = $initialCartView === 'services-cart';
$gcashQrSettings = load_gcash_qr_repository();
$gcashQrImagePath = trim((string) ($gcashQrSettings['qrImagePath'] ?? ''));
$gcashPaymentInfo = [
    'imageUrl' => $gcashQrImagePath !== '' ? $assetBase . ltrim($gcashQrImagePath, '/') : '',
    'accountName' => (string) ($gcashQrSettings['accountName'] ?? ''),
    'accountNumber' => (string) ($gcashQrSettings['accountNumber'] ?? '')
];
$customerTermsSettings = load_customer_terms_repository();
$customerTermsDisplayHtml = customer_terms_prepare_display_html((string) ($customerTermsSettings['contentHtml'] ?? ''));
$customerGcashInfo = map_customer_gcash_profile_for_frontend([]);
$customerPhone = '';

if ($isCustomerLoggedIn) {
    $customerId = (string) ($_SESSION['customer_id'] ?? '');
    $customerPhone = trim((string) ($_SESSION['customer_phone'] ?? ''));

    $customerProfileStmt = $conn->prepare("SELECT customer_phone FROM {$customerAccountsTable} WHERE id = ? LIMIT 1");

    if ($customerProfileStmt instanceof mysqli_stmt) {
        $customerIdInt = (int) $customerId;
        $customerProfileStmt->bind_param('i', $customerIdInt);
        $customerProfileStmt->execute();
        $customerProfileResult = $customerProfileStmt->get_result();
        $customerProfile = $customerProfileResult ? $customerProfileResult->fetch_assoc() : null;
        $customerProfileStmt->close();

        if (is_array($customerProfile)) {
            $resolvedPhone = trim((string) ($customerProfile['customer_phone'] ?? ''));

            if ($resolvedPhone !== '') {
                $customerPhone = $resolvedPhone;
                $_SESSION['customer_phone'] = $resolvedPhone;
            }
        }
    }

    $customerOrders = load_customer_orders_for_customer($customerId);
    $customerOrdersSignature = customer_orders_live_state_signature_for_customer($customerId);
    $customerNotifications = load_customer_notifications_for_customer($customerId, null, 20);
    $customerNotificationUnreadCount = count_unread_customer_notifications_for_customer($customerId, $customerNotifications);

    foreach ($customerNotifications as $notificationRecord) {
        if (!is_array($notificationRecord)) {
            continue;
        }

        $customerNotificationsForFrontend[] = map_customer_notification_for_frontend($notificationRecord);
    }

    $customerGcashInfo = map_customer_gcash_profile_for_frontend(
        find_customer_gcash_profile_for_customer($customerId)
    );
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>The Nifty Fifty | Reservation</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>css/style.css?v=20261015-4">
    <link rel="stylesheet" href="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>css/transaction-history.css?v=20260923-1">
</head>
<body class="cart-page">
    <header class="site-header">
        <div class="topbar topbar-customer-cart">
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

            <a class="topbar-cart" href="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>customer-cart/" aria-label="Reservation">
                <img src="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>assets/icons/cart_icon.svg" alt="">
                <span class="cart-count"><?php echo $cartCount; ?></span>
            </a>

            <a class="topbar-link" href="#" data-message-us-open>Message us</a>
            <?php if ($isCustomerLoggedIn): ?>
                <div class="dropdown topbar-account-menu">
                    <button class="account-pill account-pill-toggle dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <?php echo htmlspecialchars($accountLabel, ENT_QUOTES, 'UTF-8'); ?>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end account-dropdown-menu">
                        <li><a class="dropdown-item" href="<?php echo htmlspecialchars($accountSettingsPath, ENT_QUOTES, 'UTF-8'); ?>">Account Settings</a></li>
                        <li><a class="dropdown-item" href="<?php echo htmlspecialchars($cartPath, ENT_QUOTES, 'UTF-8'); ?>">My Reservation</a></li>
                        <li><a class="dropdown-item" href="<?php echo htmlspecialchars($reservationHistoryPath, ENT_QUOTES, 'UTF-8'); ?>">Reservation History</a></li>
                        <li><a class="dropdown-item" href="<?php echo htmlspecialchars($eventsPath, ENT_QUOTES, 'UTF-8'); ?>">Browse Events</a></li>
                        <li><a class="dropdown-item" href="<?php echo htmlspecialchars($servicesPath, ENT_QUOTES, 'UTF-8'); ?>">Browse Services</a></li>
                        <li><a class="dropdown-item" href="#">Help Center</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item account-logout-item" href="<?php echo htmlspecialchars($logoutPath, ENT_QUOTES, 'UTF-8'); ?>">Log Out</a></li>
                    </ul>
                </div>

                <button
                    class="topbar-notification-button topbar-notification-button-icon-only"
                    type="button"
                    aria-label="Notifications"
                    title="Notifications"
                    data-customer-notification-trigger
                >
                    <span class="topbar-notification-icon-wrap" aria-hidden="true">
                        <img class="topbar-notification-icon" src="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>assets/icons/notifications.svg" alt="">
                        <span class="cart-count topbar-notification-count" data-customer-notification-count aria-hidden="true"><?php echo htmlspecialchars((string) $customerNotificationUnreadCount, ENT_QUOTES, 'UTF-8'); ?></span>
                    </span>
                </button>
            <?php else: ?>
                <a class="account-pill" href="<?php echo htmlspecialchars($loginPath, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($accountLabel, ENT_QUOTES, 'UTF-8'); ?></a>
            <?php endif; ?>
        </div>

        <?php if (!$isServicesCartInitialView): ?>
            <nav class="section-nav" aria-label="Customer navigation">
                <button
                    type="button"
                    class="section-nav-filter<?php echo $initialCartView === 'cart' ? ' is-active' : ''; ?>"
                    data-cart-nav="cart"
                    <?php echo $initialCartView === 'cart' ? 'aria-current="page"' : ''; ?>
                >
                    Reservation
                </button>
                <button
                    type="button"
                    class="section-nav-section<?php echo $initialCartView === 'order-status' ? ' is-active' : ''; ?>"
                    data-cart-nav="order-status"
                    <?php echo $initialCartView === 'order-status' ? 'aria-current="page"' : ''; ?>
                >
                    Order Status
                </button>
                <button
                    type="button"
                    class="section-nav-section<?php echo $initialCartView === 'history' ? ' is-active' : ''; ?>"
                    data-cart-nav="history"
                    <?php echo $initialCartView === 'history' ? 'aria-current="page"' : ''; ?>
                >
                    Reservation History
                </button>
            </nav>
        <?php endif; ?>
    </header>

    <main class="cart-shell">
        <section class="cart-layout reveal">
            <div class="cart-main-column">
                <section data-cart-view="cart">
                <div class="cart-header-row">
                    <a class="catalog-back" href="<?php echo htmlspecialchars($productListPath, ENT_QUOTES, 'UTF-8'); ?>" aria-label="Back to featured products">
                        <span class="catalog-back-icon" aria-hidden="true"></span>
                    </a>
                    <h1 data-cart-main-heading>RESERVATION</h1>
                </div>

                <div class="cart-items-panel" data-cart-items-panel>
                    <p class="cart-items-empty" data-cart-empty-message>Your reservation is empty. Add event packages or camera rentals to continue.</p>
                </div>
                </section>

                <section class="profile-section-card cart-order-status-panel" data-cart-view="order-status" aria-labelledby="cart-order-status-heading" hidden>
                    <div class="profile-section-head">
                        <h2 id="cart-order-status-heading">Order Status</h2>
                    </div>

                    <p class="cart-order-status-copy">Track your reservations and current fulfillment status.</p>

                    <div class="profile-order-list" data-cart-orders-list aria-label="Order status list"></div>
                    <p class="cart-order-status-empty" data-cart-orders-empty hidden>No orders yet. Confirm a reservation to see it here.</p>
                </section>

                <section class="profile-section-card cart-history-panel" data-cart-view="history" aria-labelledby="cart-history-heading" hidden>
                    <div class="profile-section-head">
                        <h2 id="cart-history-heading">Reservation History</h2>
                    </div>

                    <p class="cart-history-copy">Find reservations by status, camera or date. Re-reserve items from a finished reservation.</p>

                    <?php $transactionHistoryScope = 'customer'; require __DIR__ . '/transaction_history_controls.php'; ?>

                    <div class="cart-history-grid" data-cart-history-list aria-label="Reservation history list"></div>
                    <p class="cart-history-empty" data-cart-history-empty hidden>No reservations yet. Your reservations will appear here after checkout.</p>
                </section>
            </div>

            <aside class="cart-sidebar" data-cart-sidebar>
                <section class="cart-booking-card" data-cart-booking>
                    <div class="cart-booking-group">
                        <h2>Receiving Date/Time:</h2>
                        <div class="cart-inline-fields">
                            <input type="date" data-booking-field="receiveDate" hidden aria-hidden="true" tabindex="-1">
                            <div class="cart-receive-date-display" data-receive-date-display>Select a receiving date</div>
                            <select data-booking-field="receiveTime">
                                <option value="08:00">08:00 AM</option>
                                <option value="09:00">09:00 AM</option>
                                <option value="10:00" selected>10:00 AM</option>
                                <option value="11:00">11:00 AM</option>
                                <option value="12:00">12:00 PM</option>
                                <option value="13:00">01:00 PM</option>
                                <option value="14:00">02:00 PM</option>
                                <option value="15:00">03:00 PM</option>
                                <option value="16:00">04:00 PM</option>
                                <option value="17:00">05:00 PM</option>
                            </select>
                        </div>

                        <div class="cart-receive-calendar" data-receive-date-calendar>
                            <div class="cart-receive-calendar-head">
                                <button type="button" class="cart-receive-calendar-nav" data-receive-calendar-nav="prev" aria-label="Previous month">&#10094;</button>
                                <p class="cart-receive-calendar-title" data-receive-calendar-title>Receiving Date</p>
                                <button type="button" class="cart-receive-calendar-nav" data-receive-calendar-nav="next" aria-label="Next month">&#10095;</button>
                            </div>
                            <div class="cart-receive-calendar-grid" data-receive-calendar-grid></div>
                            <p class="cart-receive-calendar-note" data-receive-calendar-note hidden>No available receiving dates in the current window for your selected items.</p>
                        </div>

                        <label class="cart-form-line" data-booking-place-row>
                            <span>Meeting Place:</span>
                            <?php $meetingPlaceOptions = load_meeting_places_repository(); ?>
                            <select data-booking-field="place">
                                <?php foreach ($meetingPlaceOptions as $meetingPlaceIndex => $meetingPlaceLabel): ?>
                                    <option value="<?php echo htmlspecialchars($meetingPlaceLabel, ENT_QUOTES, 'UTF-8'); ?>"<?php echo $meetingPlaceIndex === 0 ? ' selected' : ''; ?>><?php echo htmlspecialchars($meetingPlaceLabel, ENT_QUOTES, 'UTF-8'); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <input
                                type="text"
                                data-booking-field="eventPlace"
                                data-booking-event-place-input
                                placeholder="Enter event place"
                                maxlength="150"
                                hidden
                                disabled
                                aria-disabled="true"
                            >
                        </label>
                    </div>

                    <div class="cart-booking-group">
                        <h2>Returning Date/Time:</h2>
                        <div class="cart-inline-fields">
                            <input type="date" data-booking-field="returnDate" readonly disabled aria-disabled="true" tabindex="-1">
                            <select data-booking-field="returnTime" disabled aria-disabled="true">
                                <option value="08:00" selected>08:00 AM</option>
                                <option value="09:00">09:00 AM</option>
                                <option value="10:00">10:00 AM</option>
                                <option value="11:00">11:00 AM</option>
                                <option value="12:00">12:00 PM</option>
                                <option value="13:00">01:00 PM</option>
                                <option value="14:00">02:00 PM</option>
                                <option value="15:00">03:00 PM</option>
                                <option value="16:00">04:00 PM</option>
                                <option value="17:00">05:00 PM</option>
                            </select>
                        </div>

                        <p class="cart-late-note">Late returns = P50/hour</p>

                        <label class="cart-form-line"<?php echo $isServicesCartInitialView ? ' hidden' : ''; ?>>
                            <span>Courier:</span>
                            <select data-booking-field="courier">
                                <option value="lalamove" selected>Lalamove</option>
                                <option value="grab-express">GrabExpress</option>
                                <option value="lbc">LBC</option>
                                <option value="j-and-t">J&T Express</option>
                                <option value="self-booked">Self-booked Courier</option>
                            </select>
                        </label>
                    </div>

                    <div class="cart-methods-row">
                        <section class="cart-method-card">
                            <h3>Receiving Method:</h3>
                            <div class="cart-method-options" data-booking-method-group="receivingMethod">
                                <label class="cart-method-option">
                                    <input type="radio" name="receivingMethod" value="pickup" checked>
                                    <span>PICK-UP</span>
                                </label>
                                <label class="cart-method-option">
                                    <input type="radio" name="receivingMethod" value="meetup">
                                    <span>MEET-UP</span>
                                </label>
                                <label class="cart-method-option">
                                    <input type="radio" name="receivingMethod" value="delivery">
                                    <span>DELIVERY</span>
                                </label>
                            </div>
                        </section>

                        <section class="cart-method-card">
                            <h3>Returning Method:</h3>
                            <div class="cart-method-options" data-booking-method-group="returningMethod">
                                <label class="cart-method-option">
                                    <input type="radio" name="returningMethod" value="pickup">
                                    <span>DROP-OFF</span>
                                </label>
                                <label class="cart-method-option">
                                    <input type="radio" name="returningMethod" value="meetup" checked>
                                    <span>MEET-UP</span>
                                </label>
                                <label class="cart-method-option">
                                    <input type="radio" name="returningMethod" value="delivery">
                                    <span>DELIVERY</span>
                                </label>
                            </div>
                        </section>
                    </div>

                    <div class="cart-valid-id-block" data-delivery-only-block hidden>
                        <p>Valid Id (PhilSys, Tin Drivers license, Etc.)</p>
                        <div class="cart-upload-row">
                            <span data-upload-label="validId">No file selected</span>
                            <label class="cart-upload-button" aria-label="Upload valid ID">
                                <input type="file" accept="image/*" data-booking-field="validIdImage" hidden>
                                <span>&#8682;</span>
                            </label>
                        </div>
                        <p>Holding the Valid id near the face</p>
                        <div class="cart-upload-row">
                            <span data-upload-label="selfieId">No file selected</span>
                            <label class="cart-upload-button" aria-label="Upload selfie with valid ID">
                                <input type="file" accept="image/*" data-booking-field="selfieWithId" hidden>
                                <span>&#8682;</span>
                            </label>
                        </div>
                    </div>

                    <div class="cart-summary-card">
                        <h3>TOTAL:</h3>
                        <strong data-cart-total>P 0.00</strong>
                        <p class="cart-summary-breakdown" data-cart-breakdown>Subtotal P 0.00</p>
                    </div>

                    <select class="cart-payment-select" data-booking-field="paymentMethod"<?php echo $isServicesCartInitialView ? ' hidden disabled aria-disabled="true"' : ''; ?>>
                        <option value="gcash"<?php echo $isServicesCartInitialView ? '' : ' selected'; ?>>Gcash</option>
                        <option value="cash-pickup">Cash on Pickup</option>
                        <option value="cash-meetup"<?php echo $isServicesCartInitialView ? ' selected' : ''; ?>>Cash on Meetup</option>
                    </select>

                    <label class="cart-terms-consent" data-cart-terms-consent>
                        <input type="checkbox" data-cart-terms-checkbox>
                        <span class="cart-terms-consent-copy">
                            <span>Check if you agree to the terms and conditions.</span>
                            <button type="button" class="cart-terms-consent-link" data-cart-terms-open>Read Terms and Conditions</button>
                        </span>
                    </label>

                    <button class="cart-confirm-button" type="button">CONFIRM RESERVATION</button>

                    <p class="cart-booking-note" data-cart-booking-note>Please review and agree to the Terms and Conditions before confirming your reservation.</p>
                    <p class="cart-booking-payment-note" data-cart-booking-payment-note hidden>Receiving via delivery requires GCash payment.</p>
                    <a class="cart-booking-note-link" data-cart-booking-note-link href="<?php echo htmlspecialchars($accountSettingsPath, ENT_QUOTES, 'UTF-8'); ?>" hidden>Open Account Settings</a>
                </section>
            </aside>
        </section>
    </main>

    <?php require __DIR__ . '/customer_message_modal.php'; ?>

    <?php if ($isCustomerLoggedIn): ?>
        <section class="profile-modal cart-customer-notification-modal" data-customer-notification-modal hidden>
            <div class="profile-modal-backdrop" data-customer-notification-close></div>
            <div class="profile-modal-dialog cart-customer-notification-dialog" role="dialog" aria-modal="true" aria-labelledby="cart-customer-notification-title">
                <div class="cart-customer-notification-head">
                    <h3 id="cart-customer-notification-title">Notifications</h3>
                    <button type="button" class="profile-order-action" data-customer-notification-close>Close</button>
                </div>

                <div class="cart-customer-notification-list" data-customer-notification-list role="list"></div>
                <p class="cart-customer-notification-empty" data-customer-notification-empty hidden>No notifications yet.</p>
            </div>
        </section>
    <?php endif; ?>

    <section class="profile-modal cart-order-cancel-modal" data-cart-order-cancel-modal hidden>
        <div class="profile-modal-backdrop" data-cart-order-cancel-close></div>
        <div class="profile-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="cart-order-cancel-title">
            <h3 id="cart-order-cancel-title">Cancel reservation</h3>
            <p>Please tell us why you want to cancel this reservation.</p>
            <textarea data-cart-order-cancel-reason placeholder="Write your reason here" maxlength="500"></textarea>
            <p class="cart-order-cancel-error" data-cart-order-cancel-error hidden></p>
            <div class="profile-modal-actions">
                <button type="button" class="profile-order-action" data-cart-order-cancel-close>Back</button>
                <button type="button" class="profile-order-action danger" data-cart-order-cancel-confirm>Submit Cancel</button>
            </div>
        </div>
    </section>

    <section class="profile-modal cart-gcash-modal" data-cart-gcash-modal hidden>
        <div class="profile-modal-backdrop" data-cart-gcash-close></div>
        <div class="profile-modal-dialog cart-gcash-dialog" role="dialog" aria-modal="true" aria-labelledby="cart-gcash-title">
            <div class="cart-gcash-head">
                <h3 id="cart-gcash-title">GCash Payment</h3>
                <button type="button" class="cart-gcash-close-button" data-cart-gcash-close aria-label="Close payment modal">X</button>
            </div>
            <p data-cart-gcash-instruction>Scan QR in GCash.</p>

            <div class="cart-gcash-qr-box">
                <img src="" alt="GCash QR code" data-cart-gcash-qr-image hidden>
                <p class="cart-gcash-qr-empty" data-cart-gcash-qr-empty hidden>GCash QR is not set yet. Please contact Rental Services.</p>
            </div>

            <p class="cart-gcash-meta"><strong>Name:</strong> <span data-cart-gcash-name>-</span></p>
            <p class="cart-gcash-meta"><strong>Number:</strong> <span data-cart-gcash-number>-</span></p>

            <div class="cart-gcash-receipt-block" data-cart-gcash-receipt-block hidden>
                <input type="file" accept="image/*" data-cart-gcash-receipt-file hidden>
                <p class="cart-gcash-receipt-timer" data-cart-gcash-receipt-timer hidden></p>

                <div class="cart-gcash-customer-info" data-cart-customer-gcash-info>
                    <div class="cart-gcash-customer-head">
                        <h4>Your GCash</h4>
                        <a class="cart-gcash-customer-edit-link" href="<?php echo htmlspecialchars($accountSettingsPath, ENT_QUOTES, 'UTF-8'); ?>">Edit Info</a>
                    </div>
                    <p class="cart-gcash-customer-meta"><strong>Name:</strong> <span data-cart-customer-gcash-name-value>-</span></p>
                    <p class="cart-gcash-customer-meta"><strong>Number:</strong> <span data-cart-customer-gcash-number-value>-</span></p>
                </div>

                <div class="cart-gcash-upload-row">
                    <span class="cart-gcash-upload-filename" data-cart-gcash-receipt-filename>No file selected</span>
                    <button type="button" class="profile-order-action secondary" data-cart-gcash-receipt-select>Add Receipt</button>
                </div>

                <button type="button" class="profile-order-action primary" data-cart-gcash-upload>
                    Upload Receipt
                </button>

                <p class="cart-gcash-upload-message" data-cart-gcash-upload-message hidden></p>
            </div>

            <div class="profile-modal-actions">
                <button type="button" class="profile-order-action primary" data-cart-gcash-continue>Continue Reservation</button>
            </div>
        </div>
    </section>

    <section class="profile-modal cart-refund-proof-modal" data-cart-refund-proof-modal hidden>
        <div class="profile-modal-backdrop" data-cart-refund-proof-close></div>
        <div class="profile-modal-dialog cart-refund-proof-dialog" role="dialog" aria-modal="true" aria-labelledby="cart-refund-proof-title">
            <h3 id="cart-refund-proof-title">Refund Proof Screenshot</h3>

            <div class="cart-refund-proof-image-wrap">
                <img src="" alt="Refund proof screenshot" data-cart-refund-proof-image hidden>
                <p class="cart-refund-proof-empty" data-cart-refund-proof-empty hidden>Unable to load refund proof screenshot.</p>
            </div>

            <div class="profile-modal-actions">
                <button type="button" class="profile-order-action" data-cart-refund-proof-close>Close</button>
            </div>
        </div>
    </section>

    <section class="profile-modal cart-delivery-proof-modal" data-cart-delivery-proof-modal hidden>
        <div class="profile-modal-backdrop" data-cart-delivery-proof-close></div>
        <div class="profile-modal-dialog cart-refund-proof-dialog" role="dialog" aria-modal="true" aria-labelledby="cart-delivery-proof-title">
            <h3 id="cart-delivery-proof-title" data-cart-delivery-proof-title>Delivery Receipt</h3>

            <div class="cart-refund-proof-image-wrap">
                <img src="" alt="Delivery receipt" data-cart-delivery-proof-image hidden>
                <p class="cart-refund-proof-empty" data-cart-delivery-proof-empty hidden>Unable to load delivery receipt.</p>
            </div>

            <div class="profile-modal-actions">
                <button type="button" class="profile-order-action" data-cart-delivery-proof-close>Close</button>
            </div>
        </div>
    </section>

    <section class="profile-modal cart-delivery-upload-modal" data-cart-delivery-upload-modal hidden>
        <div class="profile-modal-backdrop" data-cart-delivery-upload-close></div>
        <div class="profile-modal-dialog cart-delivery-upload-dialog" role="dialog" aria-modal="true" aria-labelledby="cart-delivery-upload-title">
            <h3 id="cart-delivery-upload-title">Upload Return Delivery Receipt</h3>
            <p data-cart-delivery-upload-copy>Upload the courier handoff receipt to mark your return delivery as in transit.</p>

            <input type="file" accept="image/*" data-cart-delivery-upload-file hidden>

            <div class="cart-gcash-upload-row">
                <span class="cart-gcash-upload-filename" data-cart-delivery-upload-filename>No file selected</span>
                <button type="button" class="profile-order-action secondary" data-cart-delivery-upload-select>Add Receipt</button>
            </div>

            <label class="cart-delivery-upload-field" for="cart-delivery-upload-reference">
                <span>Delivery Reference (optional)</span>
                <input id="cart-delivery-upload-reference" type="text" maxlength="120" data-cart-delivery-upload-reference placeholder="Tracking number or reservation reference">
            </label>

            <label class="cart-delivery-upload-field" for="cart-delivery-upload-notes">
                <span>Delivery Notes (optional)</span>
                <textarea id="cart-delivery-upload-notes" maxlength="240" data-cart-delivery-upload-notes placeholder="Courier name, rider contact, or handoff notes"></textarea>
            </label>

            <p class="cart-delivery-upload-message" data-cart-delivery-upload-message hidden></p>

            <div class="profile-modal-actions">
                <button type="button" class="profile-order-action" data-cart-delivery-upload-close>Back</button>
                <button type="button" class="profile-order-action primary" data-cart-delivery-upload-submit>Upload Receipt</button>
            </div>
        </div>
    </section>

    <section class="cart-unavailable-modal" data-cart-unavailable-modal hidden>
        <div class="cart-unavailable-modal-backdrop" data-cart-unavailable-close></div>
        <div class="cart-unavailable-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="cart-unavailable-title">
            <h3 id="cart-unavailable-title">Unavailable items found</h3>
            <p data-cart-unavailable-message>Some items are no longer available and will be removed from your cart.</p>
            <div class="cart-unavailable-modal-actions">
                <button type="button" class="cart-unavailable-modal-cancel" data-cart-unavailable-close>Cancel</button>
                <button type="button" class="cart-unavailable-modal-confirm" data-cart-unavailable-confirm>Remove and Continue</button>
            </div>
        </div>
    </section>

    <section class="profile-modal cart-terms-modal" data-cart-terms-modal hidden>
        <div class="profile-modal-backdrop" data-cart-terms-close></div>
        <div class="profile-modal-dialog cart-terms-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="cart-terms-modal-title">
            <div class="cart-terms-modal-head">
                <h3 id="cart-terms-modal-title">Terms and Conditions</h3>
                <button type="button" class="profile-order-action" data-cart-terms-close>Close</button>
            </div>
            <p class="cart-terms-modal-copy">Scroll through the full agreement to enable the final confirmation button.</p>
            <div class="cart-terms-modal-scroll" data-cart-terms-scroll>
                <article class="cart-terms-markdown" aria-label="Full Terms and Conditions">
                    <?php echo $customerTermsDisplayHtml; ?>
                </article>
            </div>
            <div class="profile-modal-actions">
                <button type="button" class="profile-order-action" data-cart-terms-close>Back</button>
                <button type="button" class="profile-order-action primary" data-cart-terms-agree disabled>Agree</button>
            </div>
        </div>
    </section>

    <script>
        window.__creatyAssetBase = <?php echo json_encode($assetBase, JSON_UNESCAPED_SLASHES); ?>;
        window.__creatyCartAvailableItemIds = <?php echo json_encode($availableCartItemIds, JSON_UNESCAPED_SLASHES); ?>;
        window.__creatyCartCatalog = <?php echo json_encode($cartCatalogPayload, JSON_UNESCAPED_SLASHES); ?>;
        window.__creatyCustomerOrders = <?php echo json_encode($customerOrders, JSON_UNESCAPED_SLASHES); ?>;
        window.__creatyCustomerOrderSubmitEndpoint = <?php echo json_encode($orderSubmitEndpoint, JSON_UNESCAPED_SLASHES); ?>;
        window.__creatyCustomerOrderCancelEndpoint = <?php echo json_encode($orderCancelEndpoint, JSON_UNESCAPED_SLASHES); ?>;
        window.__creatyCustomerOrderReceiptUploadEndpoint = <?php echo json_encode($orderReceiptUploadEndpoint, JSON_UNESCAPED_SLASHES); ?>;
        window.__creatyCustomerOrderDeliveryReceiptUploadEndpoint = <?php echo json_encode($orderDeliveryReceiptUploadEndpoint, JSON_UNESCAPED_SLASHES); ?>;
        window.__creatyCustomerOrdersSignature = <?php echo json_encode($customerOrdersSignature, JSON_UNESCAPED_SLASHES); ?>;
        window.__creatyCustomerNotifications = <?php echo json_encode($customerNotificationsForFrontend, JSON_UNESCAPED_SLASHES); ?>;
        window.__creatyCustomerNotificationUnreadCount = <?php echo json_encode($customerNotificationUnreadCount, JSON_UNESCAPED_SLASHES); ?>;
        window.__creatyCustomerNotificationLiveEndpoint = <?php echo json_encode($customerNotificationLiveUpdatesEndpoint, JSON_UNESCAPED_SLASHES); ?>;
        window.__creatyCustomerNotificationMarkReadEndpoint = <?php echo json_encode($customerNotificationMarkReadEndpoint, JSON_UNESCAPED_SLASHES); ?>;
        window.__creatyCustomerLoggedIn = <?php echo json_encode($isCustomerLoggedIn); ?>;
        window.__creatyCustomerInitialView = <?php echo json_encode($initialCartView, JSON_UNESCAPED_SLASHES); ?>;
        window.__creatyGcashPaymentInfo = <?php echo json_encode($gcashPaymentInfo, JSON_UNESCAPED_SLASHES); ?>;
        window.__creatyCustomerGcashInfo = <?php echo json_encode($customerGcashInfo, JSON_UNESCAPED_SLASHES); ?>;
        window.__creatyCustomerPhone = <?php echo json_encode($customerPhone, JSON_UNESCAPED_SLASHES); ?>;
        window.__creatyCustomerAccountSettingsPath = <?php echo json_encode($accountSettingsPath, JSON_UNESCAPED_SLASHES); ?>;
        window.__creatyEquipmentAvailability = <?php echo json_encode($equipmentAvailability, JSON_UNESCAPED_SLASHES); ?>;
        window.__creatyEventPackageCameras = <?php echo json_encode($eventPackageCameraCatalog, JSON_UNESCAPED_SLASHES); ?>;
        window.__creatyServicePackageCameras = <?php echo json_encode($servicePackageCameraCatalog, JSON_UNESCAPED_SLASHES); ?>;
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script src="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>js/transaction-history.js?v=20260923-1"></script>
    <script src="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>js/script.js?v=20261015-2"></script>
</body>
</html>
