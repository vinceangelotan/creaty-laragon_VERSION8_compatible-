<?php
if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {
    header('Location: index.php');
    exit;
}

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$isAdminView = isset($isAdminView) && $isAdminView === true;
$assetBase = $assetBase ?? '';
$homePath = $homePath ?? '';
$loginPath = $loginPath ?? 'customer-login/';

$isCustomerLoggedIn = isset($_SESSION['customer_id']);
$isAdminLoggedIn = isset($_SESSION['user_id']) && !isset($_SESSION['customer_id']);

if ($isAdminView && !$isAdminLoggedIn) {
    header('Location: ' . $assetBase . 'admin/');
    exit;
}

require __DIR__ . '/config/event_packages_repository.php';
require __DIR__ . '/config/products_repository.php';
require __DIR__ . '/config/equipment_inventory_repository.php';

function parseEventPackagePrice($value): float
{
    return max(0, (float) $value);
}

function formatEventPackagePrice(float $value): string
{
    return 'P ' . number_format(max(0, $value), 2);
}

function calculateDiscountedEventPackagePrice(float $basePrice, int $discountPercent): float
{
    $normalizedDiscount = max(0, min(95, $discountPercent));

    return max(0, $basePrice * (1 - ($normalizedDiscount / 100)));
}

function is_supported_event_image_extension(string $path): bool
{
    $extension = strtolower((string) pathinfo($path, PATHINFO_EXTENSION));

    return in_array($extension, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true);
}

function normalize_event_asset_path(string $path): string
{
    $normalized = trim(str_replace('\\', '/', rawurldecode($path)));
    $normalized = ltrim($normalized, '/');
    $normalized = preg_replace('#/+#', '/', $normalized) ?? $normalized;

    return trim($normalized);
}

function is_event_gallery_asset_path(string $path): bool
{
    if (strpos($path, '..') !== false) {
        return false;
    }

    return strpos($path, 'assets/event_packages/') === 0;
}

function event_gallery_folder_from_path(string $path): string
{
    $normalizedPath = normalize_event_asset_path($path);
    $prefix = 'assets/event_packages/';

    if (strpos($normalizedPath, $prefix) !== 0) {
        return '';
    }

    $remainder = substr($normalizedPath, strlen($prefix));
    if ($remainder === false || $remainder === '') {
        return '';
    }

    $segments = explode('/', $remainder);

    return trim((string) ($segments[0] ?? ''));
}

/**
 * @param string[] $paths
 * @return string[]
 */
function filter_thumbnail_paths_for_folder(array $paths, string $folder): array
{
    $targetFolder = trim($folder);
    if ($targetFolder === '') {
        return array_values($paths);
    }

    $filtered = [];

    foreach ($paths as $path) {
        if (event_gallery_folder_from_path((string) $path) !== $targetFolder) {
            continue;
        }

        $filtered[] = (string) $path;
    }

    return array_values($filtered);
}

/**
 * @return string[]
 */
function collect_all_event_gallery_images(string $projectRoot): array
{
    $galleryRoot = $projectRoot . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'event_packages';
    if (!is_dir($galleryRoot)) {
        return [];
    }

    $images = [];
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($galleryRoot, FilesystemIterator::SKIP_DOTS)
    );

    foreach ($iterator as $fileInfo) {
        if (!$fileInfo->isFile()) {
            continue;
        }

        $absolutePath = $fileInfo->getPathname();
        $relativePath = substr($absolutePath, strlen($projectRoot) + 1);
        if ($relativePath === false || $relativePath === '') {
            continue;
        }

        $normalizedPath = normalize_event_asset_path($relativePath);
        if (!is_supported_event_image_extension($normalizedPath) || !is_event_gallery_asset_path($normalizedPath)) {
            continue;
        }

        $segments = explode('/', $normalizedPath);
        $skipAsset = false;

        foreach ($segments as $segment) {
            if ($segment !== '' && strpos($segment, '_') === 0) {
                $skipAsset = true;
                break;
            }
        }

        if ($skipAsset) {
            continue;
        }

        $images[$normalizedPath] = $normalizedPath;
    }

    $imageValues = array_values($images);
    natcasesort($imageValues);

    return array_values($imageValues);
}

/**
 * @param mixed $input
 * @return string[]
 */
function sanitize_selected_thumbnail_paths($input, string $projectRoot): array
{
    $paths = $input;

    if (is_string($paths)) {
        $decoded = json_decode($paths, true);

        if (is_array($decoded)) {
            $paths = $decoded;
        } else {
            $paths = [];
        }
    }

    if (!is_array($paths)) {
        return [];
    }

    $selected = [];

    foreach ($paths as $path) {
        $normalizedPath = normalize_event_asset_path((string) $path);

        if ($normalizedPath === '' || !is_event_gallery_asset_path($normalizedPath) || !is_supported_event_image_extension($normalizedPath)) {
            continue;
        }

        if (isset($selected[$normalizedPath])) {
            continue;
        }

        $absolutePath = $projectRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $normalizedPath);
        if (!is_file($absolutePath)) {
            continue;
        }

        $selected[$normalizedPath] = $normalizedPath;
    }

    return array_values($selected);
}

/**
 * @return string[]
 */
function collectEventPackageFolders(string $projectRoot): array
{
    $packagesRoot = $projectRoot . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'event_packages';
    if (!is_dir($packagesRoot)) {
        return [];
    }

    $folders = [];
    $iterator = new DirectoryIterator($packagesRoot);

    foreach ($iterator as $entry) {
        if (!$entry->isDir() || $entry->isDot()) {
            continue;
        }

        $folderName = $entry->getFilename();
        if ($folderName === '' || $folderName[0] === '_') {
            continue;
        }

        $folders[] = $folderName;
    }

    natcasesort($folders);

    return array_values($folders);
}

function build_event_package_camera_label(string $productKey, array $productRecord): string
{
    $brand = trim((string) ($productRecord['brand'] ?? ''));
    $name = trim((string) ($productRecord['name'] ?? ''));

    if ($brand !== '' && $name !== '') {
        return $brand . ' ' . $name;
    }

    if ($name !== '') {
        return $name;
    }

    if ($brand !== '') {
        return $brand;
    }

    return strtoupper(str_replace('-', ' ', $productKey));
}

function build_event_package_camera_options(array $products, array $inventory): array
{
    $options = [];

    foreach ($inventory as $productKey => $inventoryEntry) {
        if (!is_array($inventoryEntry)) {
            continue;
        }

        $normalizedKey = normalize_event_package_key((string) $productKey);

        if ($normalizedKey === '' || isset($options[$normalizedKey])) {
            continue;
        }

        $productRecord = isset($products[$normalizedKey]) && is_array($products[$normalizedKey])
            ? $products[$normalizedKey]
            : [];

        $options[$normalizedKey] = [
            'key' => $normalizedKey,
            'label' => build_event_package_camera_label($normalizedKey, $productRecord),
        ];
    }

    uasort(
        $options,
        static function (array $left, array $right): int {
            return strcasecmp((string) ($left['label'] ?? ''), (string) ($right['label'] ?? ''));
        }
    );

    return array_values($options);
}

function build_event_package_camera_key_lookup(array $cameraOptions): array
{
    $lookup = [];

    foreach ($cameraOptions as $cameraOption) {
        if (!is_array($cameraOption)) {
            continue;
        }

        $cameraKey = normalize_event_package_key((string) ($cameraOption['key'] ?? ''));
        if ($cameraKey === '') {
            continue;
        }

        $lookup[$cameraKey] = true;
    }

    return $lookup;
}

function sanitize_event_package_camera_assignment($value, array $cameraKeyLookup): string
{
    $cameraKey = normalize_event_package_key((string) $value);

    if ($cameraKey === '' || !isset($cameraKeyLookup[$cameraKey])) {
        return '';
    }

    return $cameraKey;
}

$eventPackagesRepository = load_event_packages_repository();
$cameraInventory = load_equipment_inventory_repository();
$cameraProducts = load_products_repository();
$eventPackageCameraOptions = build_event_package_camera_options($cameraProducts, $cameraInventory);
$eventPackageCameraKeyLookup = build_event_package_camera_key_lookup($eventPackageCameraOptions);

if ($isAdminView && strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $adminEventAction = trim((string) ($_POST['admin_event_action'] ?? ''));

    if ($adminEventAction === 'update_event_package') {
        $packageKey = normalize_event_package_key((string) ($_POST['package_key'] ?? ''));
        $titleValue = trim((string) ($_POST['title'] ?? ''));
        $priceValue = (float) ($_POST['price'] ?? 0);
        $discountValue = (int) ($_POST['discountPercent'] ?? 0);
        $camera1Value = sanitize_event_package_camera_assignment($_POST['camera1'] ?? '', $eventPackageCameraKeyLookup);
        $camera2Value = sanitize_event_package_camera_assignment($_POST['camera2'] ?? '', $eventPackageCameraKeyLookup);
        $backupCamera1Value = sanitize_event_package_camera_assignment($_POST['backupCamera1'] ?? '', $eventPackageCameraKeyLookup);
        $backupCamera2Value = sanitize_event_package_camera_assignment($_POST['backupCamera2'] ?? '', $eventPackageCameraKeyLookup);

        $updated = false;

        if ($packageKey !== '' && isset($eventPackagesRepository[$packageKey]) && is_array($eventPackagesRepository[$packageKey]) && empty($eventPackagesRepository[$packageKey]['archived'])) {
            if ($titleValue !== '' && $priceValue >= 0) {
                $eventPackagesRepository[$packageKey]['title'] = $titleValue;
                $eventPackagesRepository[$packageKey]['price'] = number_format($priceValue, 2, '.', '');
                $eventPackagesRepository[$packageKey]['discountPercent'] = max(0, min(95, $discountValue));
                $eventPackagesRepository[$packageKey]['camera1'] = $camera1Value;
                $eventPackagesRepository[$packageKey]['camera2'] = $camera2Value;
                $eventPackagesRepository[$packageKey]['backupCamera1'] = $backupCamera1Value;
                $eventPackagesRepository[$packageKey]['backupCamera2'] = $backupCamera2Value;

                $updated = save_event_packages_repository($eventPackagesRepository);
            }
        }

        $redirectTarget = $_SERVER['REQUEST_URI'] ?? ($assetBase . 'admin/events/');
        $redirectPath = strtok($redirectTarget, '?') ?: $redirectTarget;
        $query = $_GET;
        $query['event_updated'] = $updated ? '1' : '0';
        $queryString = http_build_query($query);

        header('Location: ' . $redirectPath . ($queryString !== '' ? '?' . $queryString : ''));
        exit;
    }

    if ($adminEventAction === 'update_event_package_thumbnails') {
        $packageKey = normalize_event_package_key((string) ($_POST['package_key'] ?? ''));
        $selectedPaths = sanitize_selected_thumbnail_paths($_POST['selected_paths_json'] ?? '[]', __DIR__);
        $updated = false;

        if ($packageKey !== '' && isset($eventPackagesRepository[$packageKey]) && is_array($eventPackagesRepository[$packageKey]) && empty($eventPackagesRepository[$packageKey]['archived'])) {
            $packageFolder = trim((string) ($eventPackagesRepository[$packageKey]['folder'] ?? ''));
            $eventPackagesRepository[$packageKey]['thumbnail_images'] = filter_thumbnail_paths_for_folder($selectedPaths, $packageFolder);
            $updated = save_event_packages_repository($eventPackagesRepository);
        }

        $redirectTarget = $_SERVER['REQUEST_URI'] ?? ($assetBase . 'admin/events/');
        $redirectPath = strtok($redirectTarget, '?') ?: $redirectTarget;
        $query = $_GET;
        $query['event_thumb_updated'] = $updated ? '1' : '0';
        $queryString = http_build_query($query);

        header('Location: ' . $redirectPath . ($queryString !== '' ? '?' . $queryString : ''));
        exit;
    }
}

if (!$isAdminView && isset($_GET['add_event'])) {
    if (!$isCustomerLoggedIn) {
        $currentPageUrl = $_SERVER['REQUEST_URI'] ?? ($assetBase . 'customer-events/');
        $redirectQuery = '?redirect=' . rawurlencode($currentPageUrl);
        header('Location: ' . $loginPath . $redirectQuery);
        exit;
    }

    $currentCount = (int) ($_SESSION['customer_cart_count'] ?? 0);
    $_SESSION['customer_cart_count'] = $currentCount + 1;

    $redirectPath = strtok($_SERVER['REQUEST_URI'] ?? '', '?');
    $query = $_GET;
    unset($query['add_event']);
    $queryString = http_build_query($query);
    $cleanUrl = $redirectPath . ($queryString !== '' ? '?' . $queryString : '');

    header('Location: ' . $cleanUrl);
    exit;
}

$cartCount = $isAdminView ? 0 : ($isCustomerLoggedIn ? (int) ($_SESSION['customer_cart_count'] ?? 0) : 0);
$accountLabel = $isAdminView ? 'Admin' : ($isCustomerLoggedIn ? 'Account' : 'Sign In');
$accountSettingsPath = $isAdminView ? ($assetBase . 'admin/dashboard/') : ($assetBase . 'customer-account-settings/');
$logoutPath = $isAdminView ? ($assetBase . 'admin/logout.php') : ($assetBase . 'customer-logout/');
$cartPath = $assetBase . 'customer-cart/';
$eventsPath = $isAdminView ? ($assetBase . 'admin/events/') : ($assetBase . 'customer-events/');
$servicesPath = $isAdminView ? ($assetBase . 'admin/services/') : ($assetBase . 'customer-services/');
$eventDetailPath = $isAdminView ? 'admin/event/' : 'customer-event/';
$notificationsPath = $assetBase . 'admin/notifications/';
$adminNotificationCount = 0;

if ($isAdminView) {
    require_once __DIR__ . '/config/message_notifications_repository.php';
    $adminNotificationCount = count_unread_message_notifications();
}

require_once __DIR__ . '/customer_notifications_center.php';
$customerNotificationCenter = build_customer_notification_center($assetBase, !$isAdminView && $isCustomerLoggedIn);

$projectRoot = __DIR__;
$availableEventFolders = collectEventPackageFolders($projectRoot);
$eventPackages = [];

foreach ($eventPackagesRepository as $packageKey => $packageRecord) {
    if (!is_array($packageRecord)) {
        continue;
    }

    if (!empty($packageRecord['archived'])) {
        continue;
    }

    $rawPrice = parseEventPackagePrice($packageRecord['price'] ?? 0);
    $discountPercent = max(0, min(95, (int) ($packageRecord['discountPercent'] ?? 0)));
    $folder = trim((string) ($packageRecord['folder'] ?? ''));

    if ($folder === '' && $availableEventFolders !== []) {
        $folder = (string) $availableEventFolders[0];
    }

    $selectedThumbnailImages = sanitize_selected_thumbnail_paths($packageRecord['thumbnail_images'] ?? [], $projectRoot);
    $selectedThumbnailImages = filter_thumbnail_paths_for_folder($selectedThumbnailImages, $folder);
    $camera1Key = sanitize_event_package_camera_assignment($packageRecord['camera1'] ?? '', $eventPackageCameraKeyLookup);
    $camera2Key = sanitize_event_package_camera_assignment($packageRecord['camera2'] ?? '', $eventPackageCameraKeyLookup);
    $backupCamera1Key = sanitize_event_package_camera_assignment($packageRecord['backupCamera1'] ?? '', $eventPackageCameraKeyLookup);
    $backupCamera2Key = sanitize_event_package_camera_assignment($packageRecord['backupCamera2'] ?? '', $eventPackageCameraKeyLookup);

    $eventPackages[] = [
        'key' => (string) $packageKey,
        'title' => trim((string) ($packageRecord['title'] ?? strtoupper(str_replace('-', ' ', (string) $packageKey)))),
        'price_value' => $rawPrice,
        'price_label' => formatEventPackagePrice($rawPrice),
        'discount_percent' => $discountPercent,
        'folder' => $folder,
        'selected_thumbnail_images' => $selectedThumbnailImages,
        'camera1' => $camera1Key,
        'camera2' => $camera2Key,
        'backupCamera1' => $backupCamera1Key,
        'backupCamera2' => $backupCamera2Key,
    ];
}

$eventGalleryImageCandidates = $isAdminView ? collect_all_event_gallery_images($projectRoot) : [];

/**
 * @return string[]
 */
function resolve_event_package_slideshow_images(array $eventPackage, string $projectRoot): array
{
    $selectedImages = sanitize_selected_thumbnail_paths($eventPackage['selected_thumbnail_images'] ?? [], $projectRoot);
    $selectedImages = filter_thumbnail_paths_for_folder($selectedImages, (string) ($eventPackage['folder'] ?? ''));

    return $selectedImages;
}

function buildAssetUrl(string $assetBasePath, string $relativePath): string
{
    $segments = explode('/', str_replace('\\', '/', $relativePath));
    $encodedPath = implode('/', array_map('rawurlencode', $segments));
    $normalizedBase = $assetBasePath === '' ? '' : rtrim($assetBasePath, '/') . '/';

    return $normalizedBase . $encodedPath;
}

foreach ($eventPackages as &$eventPackage) {
    $eventPackage['images'] = resolve_event_package_slideshow_images($eventPackage, $projectRoot);
}
unset($eventPackage);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $isAdminView ? 'Admin Events | Creaty' : 'The Nifty Fifty | Events'; ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>css/style.css?v=20261008-2">
</head>
<body class="events-page">
    <header class="site-header">
        <div class="topbar<?php echo $isAdminView ? ' topbar-admin' : ''; ?>">
            <a class="brand-badge" href="<?php echo htmlspecialchars($homePath, ENT_QUOTES, 'UTF-8'); ?>">
                <img src="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>assets/images/main_logo.png" alt="The Nifty Fifty">
            </a>

            <?php if (!$isAdminView): ?>
                <a class="topbar-link topbar-help" href="#">
                    <img src="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>assets/icons/help_icon.svg" alt="">
                    <span>Help</span>
                </a>
            <?php endif; ?>

            <form class="topbar-search" action="#" method="get">
                <input type="search" name="q" placeholder="Search packages, events, or services">
            </form>

            <?php if (!$isAdminView): ?>
                <a class="topbar-cart" href="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>customer-cart/" aria-label="Cart">
                    <img src="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>assets/icons/cart_icon.svg" alt="">
                    <span class="cart-count"><?php echo $cartCount; ?></span>
                </a>
            <?php endif; ?>

            <?php if (!$isAdminView): ?>
                <a class="topbar-link" href="#" data-message-us-open>Message us</a>
            <?php endif; ?>

            <?php if ($isAdminView): ?>
                <div class="topbar-admin-actions">
                    <a
                        class="topbar-notification-button"
                        href="<?php echo htmlspecialchars($notificationsPath, ENT_QUOTES, 'UTF-8'); ?>"
                        aria-label="Notifications"
                        title="Notifications"
                        data-admin-notification-trigger
                        data-notification-count="<?php echo htmlspecialchars((string) $adminNotificationCount, ENT_QUOTES, 'UTF-8'); ?>"
                    >
                        <span class="topbar-notification-text">Notifications</span>
                        <span class="topbar-notification-icon-wrap" aria-hidden="true">
                            <img class="topbar-notification-icon" src="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>assets/icons/notifications.svg" alt="">
                            <span class="cart-count topbar-notification-count" aria-hidden="true"><?php echo htmlspecialchars((string) $adminNotificationCount, ENT_QUOTES, 'UTF-8'); ?></span>
                        </span>
                    </a>

                    <div class="dropdown topbar-account-menu">
                        <button class="account-pill account-pill-toggle dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <?php echo htmlspecialchars($accountLabel, ENT_QUOTES, 'UTF-8'); ?>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end account-dropdown-menu">
                            <li><a class="dropdown-item" href="<?php echo htmlspecialchars($accountSettingsPath, ENT_QUOTES, 'UTF-8'); ?>">Admin Home</a></li>
                            <li><a class="dropdown-item" href="<?php echo htmlspecialchars($eventsPath, ENT_QUOTES, 'UTF-8'); ?>">Browse Events</a></li>
                            <li><a class="dropdown-item" href="<?php echo htmlspecialchars($servicesPath, ENT_QUOTES, 'UTF-8'); ?>">Browse Services</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item account-logout-item" href="<?php echo htmlspecialchars($logoutPath, ENT_QUOTES, 'UTF-8'); ?>">Log Out</a></li>
                        </ul>
                    </div>
                </div>
            <?php elseif ($isCustomerLoggedIn): ?>
                <div class="dropdown topbar-account-menu">
                    <button class="account-pill account-pill-toggle dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <?php echo htmlspecialchars($accountLabel, ENT_QUOTES, 'UTF-8'); ?>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end account-dropdown-menu">
                        <li><a class="dropdown-item" href="<?php echo htmlspecialchars($accountSettingsPath, ENT_QUOTES, 'UTF-8'); ?>">Account Settings</a></li>
                        <li><a class="dropdown-item" href="<?php echo htmlspecialchars($cartPath, ENT_QUOTES, 'UTF-8'); ?>">My Cart</a></li>
                        <li><a class="dropdown-item" href="<?php echo htmlspecialchars($cartPath . '?view=history', ENT_QUOTES, 'UTF-8'); ?>">Reservation History</a></li>
                        <li><a class="dropdown-item" href="<?php echo htmlspecialchars($eventsPath, ENT_QUOTES, 'UTF-8'); ?>">Browse Events</a></li>
                        <li><a class="dropdown-item" href="<?php echo htmlspecialchars($servicesPath, ENT_QUOTES, 'UTF-8'); ?>">Browse Services</a></li>
                        <li><a class="dropdown-item" href="#">Help Center</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item account-logout-item" href="<?php echo htmlspecialchars($logoutPath, ENT_QUOTES, 'UTF-8'); ?>">Log Out</a></li>
                    </ul>

                    <?php render_customer_notification_trigger_button($customerNotificationCenter, $assetBase); ?>
                </div>
            <?php else: ?>
                <a class="account-pill" href="<?php echo htmlspecialchars($loginPath, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($accountLabel, ENT_QUOTES, 'UTF-8'); ?></a>
            <?php endif; ?>
        </div>

        <nav class="section-nav section-nav-disabled section-nav-with-history" aria-label="Catalog filters">
            <span class="section-nav-filter is-disabled" aria-disabled="true">BRANDS</span>
            <a class="section-nav-section is-active" href="<?php echo htmlspecialchars($eventsPath, ENT_QUOTES, 'UTF-8'); ?>" aria-current="page">EVENTS</a>
            <a class="section-nav-section" href="<?php echo htmlspecialchars($servicesPath, ENT_QUOTES, 'UTF-8'); ?>">SERVICES</a>
            <span class="section-nav-filter is-disabled" aria-disabled="true">DATE</span>
            <?php if ($isCustomerLoggedIn): ?>
                <a class="section-nav-history-link" href="<?php echo htmlspecialchars($cartPath . '?view=history', ENT_QUOTES, 'UTF-8'); ?>">Reservation History</a>
            <?php endif; ?>
        </nav>
    </header>

    <main class="events-shell">
        <section class="catalog-section reveal">
            <div class="catalog-header">
                <a class="catalog-back" href="<?php echo htmlspecialchars($homePath, ENT_QUOTES, 'UTF-8'); ?>" aria-label="Back to home">
                    <span class="catalog-back-icon" aria-hidden="true"></span>
                </a>
                <h1>EVENTS</h1>
            </div>

            <?php if ($isAdminView && isset($_GET['event_updated'])): ?>
                <p class="admin-events-flash-message<?php echo (string) $_GET['event_updated'] === '1' ? ' is-success' : ' is-error'; ?>">
                    <?php echo (string) $_GET['event_updated'] === '1' ? 'Event package updated.' : 'Unable to update event package.'; ?>
                </p>
            <?php endif; ?>

            <?php if ($isAdminView && isset($_GET['event_thumb_updated'])): ?>
                <p class="admin-events-flash-message<?php echo (string) $_GET['event_thumb_updated'] === '1' ? ' is-success' : ' is-error'; ?>">
                    <?php echo (string) $_GET['event_thumb_updated'] === '1' ? 'Slideshow thumbnails updated.' : 'Unable to update slideshow thumbnails.'; ?>
                </p>
            <?php endif; ?>

            <div class="package-grid">
                <?php foreach ($eventPackages as $index => $eventPackage): ?>
                    <?php
                    $images = $eventPackage['images'];
                    $title = $eventPackage['title'];
                    $basePriceValue = (float) ($eventPackage['price_value'] ?? 0);
                    $discountPercent = max(0, min(95, (int) ($eventPackage['discount_percent'] ?? 0)));
                    $discountedPriceValue = calculateDiscountedEventPackagePrice($basePriceValue, $discountPercent);
                    $finalPriceValue = $discountPercent > 0 ? $discountedPriceValue : $basePriceValue;
                    $basePriceLabel = formatEventPackagePrice($basePriceValue);
                    $discountedPriceLabel = formatEventPackagePrice($discountedPriceValue);
                    $finalPriceLabel = formatEventPackagePrice($finalPriceValue);
                    $detailPageUrl = $assetBase . $eventDetailPath . '?package=' . urlencode($eventPackage['key']);
                    ?>
                    <article
                        class="package-card<?php echo $discountPercent > 0 ? ' package-card-highlight' : ''; ?>"
                        data-admin-event-package
                        data-admin-event-package-key="<?php echo htmlspecialchars((string) $eventPackage['key'], ENT_QUOTES, 'UTF-8'); ?>"
                        data-admin-event-package-title="<?php echo htmlspecialchars((string) $title, ENT_QUOTES, 'UTF-8'); ?>"
                        data-admin-event-package-price="<?php echo htmlspecialchars(number_format($basePriceValue, 2, '.', ''), ENT_QUOTES, 'UTF-8'); ?>"
                        data-admin-event-package-discount="<?php echo htmlspecialchars((string) $discountPercent, ENT_QUOTES, 'UTF-8'); ?>"
                        data-admin-event-package-folder="<?php echo htmlspecialchars((string) ($eventPackage['folder'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                        data-admin-event-package-camera-1="<?php echo htmlspecialchars((string) ($eventPackage['camera1'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                        data-admin-event-package-camera-2="<?php echo htmlspecialchars((string) ($eventPackage['camera2'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                        data-admin-event-package-backup-camera-1="<?php echo htmlspecialchars((string) ($eventPackage['backupCamera1'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                        data-admin-event-package-backup-camera-2="<?php echo htmlspecialchars((string) ($eventPackage['backupCamera2'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                        data-admin-event-selected-thumbnails="<?php echo htmlspecialchars((string) json_encode(array_values((array) ($eventPackage['selected_thumbnail_images'] ?? [])), JSON_UNESCAPED_SLASHES), ENT_QUOTES, 'UTF-8'); ?>"
                    >
                        <?php if ($isAdminView): ?>
                            <button class="product-card-admin-edit" type="button" data-admin-event-edit aria-label="Edit <?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?> package">
                                <img src="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>assets/icons/pencil.svg" alt="">
                            </button>
                            <button class="product-card-admin-remove" type="button" data-admin-remove-event-package aria-label="Archive <?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?> package">&times;</button>
                        <?php endif; ?>

                        <?php if ($discountPercent > 0): ?>
                            <div class="product-ribbon">PROMO <?php echo htmlspecialchars((string) $discountPercent, ENT_QUOTES, 'UTF-8'); ?>% OFF!</div>
                        <?php endif; ?>

                        <div
                            class="package-thumb<?php echo $images !== [] ? ' package-slideshow' : ''; ?>"
                            <?php if ($images !== []): ?>
                                data-package-slideshow
                                data-autoplay-ms="6200"
                            <?php endif; ?>
                            aria-label="<?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?> sample photos"
                        >
                            <?php if ($images !== []): ?>
                                <div class="package-slides">
                                    <?php foreach ($images as $imageIndex => $imagePath): ?>
                                        <img
                                            class="package-slide<?php echo $imageIndex === 0 ? ' is-active' : ''; ?>"
                                            src="<?php echo htmlspecialchars(buildAssetUrl($assetBase, $imagePath), ENT_QUOTES, 'UTF-8'); ?>"
                                            alt="<?php echo htmlspecialchars($title . ' sample ' . ($imageIndex + 1), ENT_QUOTES, 'UTF-8'); ?>"
                                            loading="<?php echo $index === 0 && $imageIndex === 0 ? 'eager' : 'lazy'; ?>"
                                            aria-hidden="<?php echo $imageIndex === 0 ? 'false' : 'true'; ?>"
                                        >
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="package-empty-state">Thumbnail coming soon.</div>
                            <?php endif; ?>
                        </div>

                        <div class="package-body">
                            <h2>
                                <a class="package-title-link" href="<?php echo htmlspecialchars($detailPageUrl, ENT_QUOTES, 'UTF-8'); ?>">
                                    <?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?>
                                </a>
                            </h2>
                            <div class="package-footer">
                                <span class="package-price-stack">
                                    <?php if ($discountPercent > 0): ?>
                                        <span class="package-price-original"><?php echo htmlspecialchars($basePriceLabel, ENT_QUOTES, 'UTF-8'); ?></span>
                                        <span class="package-price-discounted"><?php echo htmlspecialchars($discountedPriceLabel, ENT_QUOTES, 'UTF-8'); ?></span>
                                    <?php else: ?>
                                        <span class="package-price-discounted"><?php echo htmlspecialchars($basePriceLabel, ENT_QUOTES, 'UTF-8'); ?></span>
                                    <?php endif; ?>
                                </span>
                                <?php if ($isAdminView): ?>
                                    <div class="package-admin-actions">
                                        <button
                                            type="button"
                                            onclick="window.location.href='<?php echo htmlspecialchars($detailPageUrl, ENT_QUOTES, 'UTF-8'); ?>'"
                                        >
                                            OPEN PACKAGE
                                        </button>
                                    </div>
                                <?php else: ?>
                                    <?php
                                    $eventPreview = $images !== []
                                        ? buildAssetUrl($assetBase, $images[0])
                                        : ($assetBase . 'assets/images/main_logo.png');
                                    $eventLoginUrl = $loginPath . '?redirect=' . rawurlencode($_SERVER['REQUEST_URI'] ?? ($assetBase . 'customer-events/'));
                                    ?>
                                    <button
                                        type="button"
                                        data-add-cart
                                        data-item-id="event-<?php echo htmlspecialchars($eventPackage['key'], ENT_QUOTES, 'UTF-8'); ?>"
                                        data-item-type="event-package"
                                        data-item-name="<?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?>"
                                        data-item-copy="Event package with curated coverage style and sample gallery references."
                                        data-item-image="<?php echo htmlspecialchars($eventPreview, ENT_QUOTES, 'UTF-8'); ?>"
                                        data-item-price="<?php echo htmlspecialchars($finalPriceLabel, ENT_QUOTES, 'UTF-8'); ?>"
                                        <?php if (!$isCustomerLoggedIn): ?>
                                            data-login-url="<?php echo htmlspecialchars($eventLoginUrl, ENT_QUOTES, 'UTF-8'); ?>"
                                        <?php endif; ?>
                                    >
                                        ADD TO CART
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
    </main>

    <?php if ($isAdminView): ?>
        <div class="admin-edit-modal-backdrop admin-event-edit-modal-backdrop" data-admin-event-edit-backdrop data-admin-event-archive-endpoint="<?php echo htmlspecialchars($assetBase . 'admin/dashboard/archive_event_package.php', ENT_QUOTES, 'UTF-8'); ?>" data-admin-event-restore-endpoint="<?php echo htmlspecialchars($assetBase . 'admin/dashboard/restore_archived_event_package.php', ENT_QUOTES, 'UTF-8'); ?>" hidden>
            <section class="admin-edit-modal admin-event-edit-modal" role="dialog" aria-modal="true" aria-labelledby="admin-event-edit-title">
                <div class="admin-edit-modal-head">
                    <h2 id="admin-event-edit-title">Edit Event Package</h2>
                    <button class="admin-edit-close" type="button" data-admin-event-edit-close aria-label="Close edit window">&times;</button>
                </div>

                <form class="admin-edit-form admin-event-edit-form" method="post" action="" data-admin-event-edit-form>
                    <input type="hidden" name="admin_event_action" value="update_event_package">
                    <input type="hidden" name="package_key" value="" data-admin-event-edit-key>

                    <div class="admin-edit-fields-column admin-event-edit-fields">
                        <label class="admin-edit-label" for="admin-event-edit-name">Package Name</label>
                        <input id="admin-event-edit-name" type="text" name="title" data-admin-event-edit-name required>

                        <label class="admin-edit-label" for="admin-event-edit-price">Price</label>
                        <div class="admin-edit-money-field">
                            <span class="admin-edit-currency" aria-hidden="true">&#8369;</span>
                            <input id="admin-event-edit-price" type="number" min="0" step="0.01" name="price" data-admin-event-edit-price required>
                        </div>

                        <label class="admin-edit-label" for="admin-event-edit-discount">Discount Percentage</label>
                        <input id="admin-event-edit-discount" type="number" min="0" max="95" step="1" name="discountPercent" data-admin-event-edit-discount>

                        <label class="admin-edit-label" for="admin-event-edit-camera-1">Camera 1</label>
                        <select id="admin-event-edit-camera-1" name="camera1" data-admin-event-edit-camera-1>
                            <option value="">Select Camera</option>
                            <?php foreach ($eventPackageCameraOptions as $cameraOption): ?>
                                <option value="<?php echo htmlspecialchars((string) ($cameraOption['key'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars((string) ($cameraOption['label'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></option>
                            <?php endforeach; ?>
                        </select>

                        <label class="admin-edit-label" for="admin-event-edit-camera-2">Camera 2</label>
                        <select id="admin-event-edit-camera-2" name="camera2" data-admin-event-edit-camera-2>
                            <option value="">Select Camera</option>
                            <?php foreach ($eventPackageCameraOptions as $cameraOption): ?>
                                <option value="<?php echo htmlspecialchars((string) ($cameraOption['key'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars((string) ($cameraOption['label'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></option>
                            <?php endforeach; ?>
                        </select>

                        <label class="admin-edit-label" for="admin-event-edit-backup-camera-1">Backup Camera 1</label>
                        <select id="admin-event-edit-backup-camera-1" name="backupCamera1" data-admin-event-edit-backup-camera-1>
                            <option value="">Select Camera</option>
                            <?php foreach ($eventPackageCameraOptions as $cameraOption): ?>
                                <option value="<?php echo htmlspecialchars((string) ($cameraOption['key'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars((string) ($cameraOption['label'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></option>
                            <?php endforeach; ?>
                        </select>

                        <label class="admin-edit-label" for="admin-event-edit-backup-camera-2">Backup Camera 2</label>
                        <select id="admin-event-edit-backup-camera-2" name="backupCamera2" data-admin-event-edit-backup-camera-2>
                            <option value="">Select Camera</option>
                            <?php foreach ($eventPackageCameraOptions as $cameraOption): ?>
                                <option value="<?php echo htmlspecialchars((string) ($cameraOption['key'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars((string) ($cameraOption['label'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="admin-edit-actions">
                        <button type="button" class="admin-edit-secondary admin-event-edit-thumbs-open" data-admin-event-set-thumbnails-edit>Set Thumbnail</button>
                        <button type="button" class="admin-edit-secondary" data-admin-event-edit-cancel>Cancel</button>
                        <button type="submit" class="admin-edit-primary">Save</button>
                    </div>
                </form>
            </section>
        </div>

        <div class="admin-edit-modal-backdrop admin-event-thumbs-modal-backdrop" data-admin-event-thumbs-backdrop hidden>
            <section class="admin-edit-modal admin-event-thumbs-modal" role="dialog" aria-modal="true" aria-labelledby="admin-event-thumbs-title">
                <div class="admin-edit-modal-head">
                    <h2 id="admin-event-thumbs-title">Set Slideshow Thumbnails</h2>
                    <button class="admin-edit-close" type="button" data-admin-event-thumbs-close aria-label="Close thumbnail selector">&times;</button>
                </div>

                <form class="admin-edit-form admin-event-thumbs-form" method="post" action="" data-admin-event-thumbs-form>
                    <input type="hidden" name="admin_event_action" value="update_event_package_thumbnails">
                    <input type="hidden" name="package_key" value="" data-admin-event-thumbs-key>
                    <input type="hidden" name="selected_paths_json" value="[]" data-admin-event-thumbs-input>

                    <p class="admin-event-thumbs-note">
                        Select images in order. The number badge shows slideshow order for <strong data-admin-event-thumbs-package-title>this package</strong>.
                    </p>

                    <?php if ($eventGalleryImageCandidates === []): ?>
                        <p class="admin-event-thumbs-empty">No event gallery images found.</p>
                    <?php else: ?>
                        <div class="admin-event-thumbs-grid" data-admin-event-thumbs-grid>
                            <?php foreach ($eventGalleryImageCandidates as $candidatePath): ?>
                                <?php $candidateLabel = str_replace('assets/event_packages/', '', (string) $candidatePath); ?>
                                <?php $candidateFolder = event_gallery_folder_from_path((string) $candidatePath); ?>
                                <button
                                    class="admin-event-thumb-item"
                                    type="button"
                                    data-admin-event-thumb-item
                                    data-image-path="<?php echo htmlspecialchars((string) $candidatePath, ENT_QUOTES, 'UTF-8'); ?>"
                                    data-image-folder="<?php echo htmlspecialchars((string) $candidateFolder, ENT_QUOTES, 'UTF-8'); ?>"
                                    aria-label="Toggle thumbnail <?php echo htmlspecialchars((string) $candidateLabel, ENT_QUOTES, 'UTF-8'); ?>"
                                >
                                    <span class="admin-event-thumb-order" data-admin-event-thumb-order hidden></span>
                                    <img src="<?php echo htmlspecialchars(buildAssetUrl($assetBase, (string) $candidatePath), ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars((string) $candidateLabel, ENT_QUOTES, 'UTF-8'); ?>">
                                    <span class="admin-event-thumb-meta"><?php echo htmlspecialchars((string) $candidateLabel, ENT_QUOTES, 'UTF-8'); ?></span>
                                </button>
                            <?php endforeach; ?>
                        </div>
                        <p class="admin-event-thumbs-empty" data-admin-event-thumbs-folder-empty hidden>No images found in this package category.</p>
                    <?php endif; ?>

                    <div class="admin-edit-actions">
                        <button type="button" class="admin-edit-secondary" data-admin-event-thumbs-cancel>Cancel</button>
                        <button type="submit" class="admin-edit-primary">Save Thumbnails</button>
                    </div>
                </form>
            </section>
        </div>

        <aside class="admin-undo-toast" data-admin-undo-toast hidden aria-live="polite" aria-atomic="true">
            <p class="admin-undo-toast-message" data-admin-undo-message>Event package archived.</p>
            <button class="admin-undo-toast-button" type="button" data-admin-undo-action>Undo</button>
        </aside>
    <?php endif; ?>

    <?php if (!$isAdminView): ?>
        <?php require __DIR__ . '/customer_message_modal.php'; ?>
        <?php render_customer_notification_modal($customerNotificationCenter); ?>
        <?php render_customer_notification_center_bootstrap_script($customerNotificationCenter, $assetBase); ?>
    <?php endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script src="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>js/script.js?v=20260923-1"></script>
</body>
</html>
