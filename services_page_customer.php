<?php
if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {
    header('Location: customer-services/');
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

require __DIR__ . '/config/services_packages_repository.php';
require __DIR__ . '/config/products_repository.php';
require __DIR__ . '/config/equipment_inventory_repository.php';

$cartCount = $isAdminView ? 0 : ($isCustomerLoggedIn ? (int) ($_SESSION['customer_cart_count'] ?? 0) : 0);
$accountLabel = $isAdminView ? 'Admin' : ($isCustomerLoggedIn ? 'Account' : 'Sign In');
$accountSettingsPath = $isAdminView ? ($assetBase . 'admin/dashboard/') : ($assetBase . 'customer-account-settings/');
$logoutPath = $isAdminView ? ($assetBase . 'admin/logout.php') : ($assetBase . 'customer-logout/');
$cartPath = $assetBase . 'customer-cart/';
$eventsPath = $isAdminView ? ($assetBase . 'admin/events/') : ($assetBase . 'customer-events/');
$servicesPath = $isAdminView ? ($assetBase . 'admin/services/') : ($assetBase . 'customer-services/');
$serviceDetailPath = $isAdminView ? 'admin/service/' : 'customer-service/';
$notificationsPath = $assetBase . 'admin/notifications/';
$adminNotificationCount = 0;

if ($isAdminView) {
    require_once __DIR__ . '/config/message_notifications_repository.php';
    $adminNotificationCount = count_unread_message_notifications();
}

$adminFeaturedProductsPath = $assetBase . 'admin/dashboard/?catalog=featured#featured-products-title';

require_once __DIR__ . '/customer_notifications_center.php';
$customerNotificationCenter = build_customer_notification_center($assetBase, !$isAdminView && $isCustomerLoggedIn);

function parse_service_package_price($value): float
{
    return max(0, (float) $value);
}

function format_service_package_price(float $value): string
{
    return 'P ' . number_format(max(0, $value), 2);
}

function calculate_discounted_service_package_price(float $basePrice, int $discountPercent): float
{
    $normalizedDiscount = max(0, min(95, $discountPercent));

    return max(0, $basePrice * (1 - ($normalizedDiscount / 100)));
}

function is_supported_service_image_extension(string $path): bool
{
    $extension = strtolower((string) pathinfo($path, PATHINFO_EXTENSION));

    return in_array($extension, ['jpg', 'jpeg', 'png', 'webp', 'gif', 'mp4', 'webm', 'ogg'], true);
}

function is_supported_service_video_extension(string $path): bool
{
    $extension = strtolower((string) pathinfo($path, PATHINFO_EXTENSION));

    return in_array($extension, ['mp4', 'webm', 'ogg'], true);
}

function normalize_service_asset_path(string $path): string
{
    $normalized = trim(str_replace('\\', '/', rawurldecode($path)));
    $normalized = ltrim($normalized, '/');
    $normalized = preg_replace('#/+#', '/', $normalized) ?? $normalized;

    return trim($normalized);
}

function is_service_gallery_asset_path(string $path): bool
{
    if (strpos($path, '..') !== false) {
        return false;
    }

    return strpos($path, 'assets/service_packages/') === 0;
}

function service_gallery_folder_from_path(string $path): string
{
    $normalizedPath = normalize_service_asset_path($path);
    $prefix = 'assets/service_packages/';

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

function is_service_collection_image_path(string $path): bool
{
    $normalizedPath = normalize_service_asset_path($path);
    $prefix = 'assets/service_packages/';

    if (strpos($normalizedPath, $prefix) !== 0) {
        return false;
    }

    $remainder = substr($normalizedPath, strlen($prefix));
    if ($remainder === false || $remainder === '') {
        return false;
    }

    $segments = array_values(array_filter(explode('/', $remainder), static function ($segment): bool {
        return trim((string) $segment) !== '';
    }));

    return count($segments) >= 3;
}

/**
 * @param string[] $paths
 * @return string[]
 */
function filter_service_thumbnail_paths_for_folder(array $paths, string $folder): array
{
    $targetFolder = trim($folder);
    if ($targetFolder === '') {
        return array_values($paths);
    }

    $filtered = [];

    foreach ($paths as $path) {
        if (service_gallery_folder_from_path((string) $path) !== $targetFolder) {
            continue;
        }

        $filtered[] = (string) $path;
    }

    return array_values($filtered);
}

/**
 * @return string[]
 */
function collect_all_service_gallery_images(string $projectRoot): array
{
    $galleryRoot = $projectRoot . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'service_packages';
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

        $normalizedPath = normalize_service_asset_path($relativePath);
        if (!is_supported_service_image_extension($normalizedPath) || !is_service_gallery_asset_path($normalizedPath) || !is_service_collection_image_path($normalizedPath)) {
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
function sanitize_selected_service_thumbnail_paths($input, string $projectRoot): array
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
        $normalizedPath = normalize_service_asset_path((string) $path);

        if ($normalizedPath === '' || !is_service_gallery_asset_path($normalizedPath) || !is_supported_service_image_extension($normalizedPath) || !is_service_collection_image_path($normalizedPath)) {
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
function resolve_service_package_slideshow_images(array $servicePackage, string $projectRoot): array
{
    $selectedImages = sanitize_selected_service_thumbnail_paths($servicePackage['selected_thumbnail_images'] ?? [], $projectRoot);
    $selectedImages = filter_service_thumbnail_paths_for_folder($selectedImages, (string) ($servicePackage['folder'] ?? ''));

    return $selectedImages;
}

function build_service_asset_url(string $assetBasePath, string $relativePath): string
{
    $segments = explode('/', str_replace('\\', '/', $relativePath));
    $encodedPath = implode('/', array_map('rawurlencode', $segments));
    $normalizedBase = $assetBasePath === '' ? '' : rtrim($assetBasePath, '/') . '/';

    return $normalizedBase . $encodedPath;
}

function build_service_package_camera_label(string $productKey, array $productRecord): string
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

function build_service_package_camera_options(array $products, array $inventory): array
{
    $options = [];

    foreach ($inventory as $productKey => $inventoryEntry) {
        if (!is_array($inventoryEntry)) {
            continue;
        }

        $normalizedKey = normalize_service_package_key((string) $productKey);

        if ($normalizedKey === '' || isset($options[$normalizedKey])) {
            continue;
        }

        $productRecord = isset($products[$normalizedKey]) && is_array($products[$normalizedKey])
            ? $products[$normalizedKey]
            : [];

        $options[$normalizedKey] = [
            'key' => $normalizedKey,
            'label' => build_service_package_camera_label($normalizedKey, $productRecord),
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

function build_service_package_camera_key_lookup(array $cameraOptions): array
{
    $lookup = [];

    foreach ($cameraOptions as $cameraOption) {
        if (!is_array($cameraOption)) {
            continue;
        }

        $cameraKey = normalize_service_package_key((string) ($cameraOption['key'] ?? ''));
        if ($cameraKey === '') {
            continue;
        }

        $lookup[$cameraKey] = true;
    }

    return $lookup;
}

function sanitize_service_package_camera_assignment($value, array $cameraKeyLookup): string
{
    $cameraKey = normalize_service_package_key((string) $value);

    if ($cameraKey === '' || !isset($cameraKeyLookup[$cameraKey])) {
        return '';
    }

    return $cameraKey;
}

$servicePackagesRepository = load_services_packages_repository();
$cameraInventory = load_equipment_inventory_repository();
$cameraProducts = load_products_repository();
$servicePackageCameraOptions = build_service_package_camera_options($cameraProducts, $cameraInventory);
$servicePackageCameraKeyLookup = build_service_package_camera_key_lookup($servicePackageCameraOptions);

if ($isAdminView && strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $adminServiceAction = trim((string) ($_POST['admin_service_action'] ?? ''));

    if ($adminServiceAction === 'update_service_package') {
        $packageKey = normalize_service_package_key((string) ($_POST['package_key'] ?? ''));
        $titleValue = trim((string) ($_POST['title'] ?? ''));
        $descriptionValue = trim((string) ($_POST['description'] ?? ''));

        if (function_exists('mb_substr')) {
            $descriptionValue = trim((string) mb_substr($descriptionValue, 0, 256, 'UTF-8'));
        } else {
            $descriptionValue = trim((string) substr($descriptionValue, 0, 256));
        }

        $priceValue = (float) ($_POST['price'] ?? 0);
        $discountValue = (int) ($_POST['discountPercent'] ?? 0);
        $durationUnitValue = normalize_service_package_duration_unit($_POST['durationUnit'] ?? 'hours');
        $durationValue = clamp_service_package_duration_value($durationUnitValue, (int) ($_POST['durationValue'] ?? 1));
        $durationUnitValue = normalize_service_package_duration_unit($durationUnitValue);
        $camera1Value = sanitize_service_package_camera_assignment($_POST['camera1'] ?? '', $servicePackageCameraKeyLookup);
        $camera2Value = sanitize_service_package_camera_assignment($_POST['camera2'] ?? '', $servicePackageCameraKeyLookup);
        $backupCamera1Value = sanitize_service_package_camera_assignment($_POST['backupCamera1'] ?? '', $servicePackageCameraKeyLookup);
        $backupCamera2Value = sanitize_service_package_camera_assignment($_POST['backupCamera2'] ?? '', $servicePackageCameraKeyLookup);

        $updated = false;

        if ($packageKey !== '' && isset($servicePackagesRepository[$packageKey]) && is_array($servicePackagesRepository[$packageKey])) {
            if ($titleValue !== '' && $priceValue >= 0) {
                $servicePackagesRepository[$packageKey]['title'] = $titleValue;
                $servicePackagesRepository[$packageKey]['description'] = $descriptionValue;
                $servicePackagesRepository[$packageKey]['price'] = number_format($priceValue, 2, '.', '');
                $servicePackagesRepository[$packageKey]['discountPercent'] = max(0, min(95, $discountValue));
                $servicePackagesRepository[$packageKey]['durationUnit'] = $durationUnitValue;
                $servicePackagesRepository[$packageKey]['durationValue'] = $durationValue;
                $servicePackagesRepository[$packageKey]['camera1'] = $camera1Value;
                $servicePackagesRepository[$packageKey]['camera2'] = $camera2Value;
                $servicePackagesRepository[$packageKey]['backupCamera1'] = $backupCamera1Value;
                $servicePackagesRepository[$packageKey]['backupCamera2'] = $backupCamera2Value;

                $updated = save_services_packages_repository($servicePackagesRepository);
            }
        }

        $redirectTarget = $_SERVER['REQUEST_URI'] ?? ($assetBase . 'admin/services/');
        $redirectPath = strtok($redirectTarget, '?') ?: $redirectTarget;
        $query = $_GET;
        $query['service_updated'] = $updated ? '1' : '0';
        $queryString = http_build_query($query);

        header('Location: ' . $redirectPath . ($queryString !== '' ? '?' . $queryString : ''));
        exit;
    }

    if ($adminServiceAction === 'update_service_package_thumbnails') {
        $packageKey = normalize_service_package_key((string) ($_POST['package_key'] ?? ''));
        $selectedPaths = sanitize_selected_service_thumbnail_paths($_POST['selected_paths_json'] ?? '[]', __DIR__);
        $updated = false;

        if ($packageKey !== '' && isset($servicePackagesRepository[$packageKey]) && is_array($servicePackagesRepository[$packageKey])) {
            $servicePackagesRepository[$packageKey]['thumbnail_images'] = filter_service_thumbnail_paths_for_folder($selectedPaths, $packageKey);
            $updated = save_services_packages_repository($servicePackagesRepository);
        }

        $redirectTarget = $_SERVER['REQUEST_URI'] ?? ($assetBase . 'admin/services/');
        $redirectPath = strtok($redirectTarget, '?') ?: $redirectTarget;
        $query = $_GET;
        $query['service_thumb_updated'] = $updated ? '1' : '0';
        $queryString = http_build_query($query);

        header('Location: ' . $redirectPath . ($queryString !== '' ? '?' . $queryString : ''));
        exit;
    }
}

$serviceTypeLabels = service_package_type_labels();
$serviceGroups = [];

foreach ($serviceTypeLabels as $serviceTypeKey => $serviceTypeLabel) {
    $serviceGroups[$serviceTypeKey] = [
        'key' => (string) $serviceTypeKey,
        'label' => (string) $serviceTypeLabel,
        'packages' => [],
    ];
}

foreach ($servicePackagesRepository as $packageKey => $packageRecord) {
    if (!is_array($packageRecord)) {
        continue;
    }

    $serviceType = normalize_service_package_type($packageRecord['serviceType'] ?? default_service_package_type());
    if (!isset($serviceGroups[$serviceType])) {
        $serviceGroups[$serviceType] = [
            'key' => $serviceType,
            'label' => strtoupper(str_replace('-', ' ', $serviceType)),
            'packages' => [],
        ];
    }

    $priceValue = parse_service_package_price($packageRecord['price'] ?? 0);
    $discountPercent = max(0, min(95, (int) ($packageRecord['discountPercent'] ?? 0)));
    $discountedPriceValue = calculate_discounted_service_package_price($priceValue, $discountPercent);
    $durationUnit = normalize_service_package_duration_unit($packageRecord['durationUnit'] ?? 'hours');
    $durationValue = clamp_service_package_duration_value($durationUnit, (int) ($packageRecord['durationValue'] ?? 1));
    $durationUnit = normalize_service_package_duration_unit($durationUnit);
    $packageFolder = normalize_service_package_key((string) $packageKey);
    $selectedThumbnailImages = sanitize_selected_service_thumbnail_paths($packageRecord['thumbnail_images'] ?? [], __DIR__);
    $selectedThumbnailImages = filter_service_thumbnail_paths_for_folder($selectedThumbnailImages, $packageFolder);

    $serviceGroups[$serviceType]['packages'][] = [
        'key' => (string) $packageKey,
        'title' => trim((string) ($packageRecord['title'] ?? 'PACKAGE')),
        'description' => trim((string) ($packageRecord['description'] ?? '')),
        'price_value' => $priceValue,
        'price_label' => format_service_package_price($priceValue),
        'discount_percent' => $discountPercent,
        'discounted_price_value' => $discountedPriceValue,
        'discounted_price_label' => format_service_package_price($discountedPriceValue),
        'duration_unit' => $durationUnit,
        'duration_value' => $durationValue,
        'folder' => $packageFolder,
        'selected_thumbnail_images' => $selectedThumbnailImages,
        'camera1' => sanitize_service_package_camera_assignment($packageRecord['camera1'] ?? '', $servicePackageCameraKeyLookup),
        'camera2' => sanitize_service_package_camera_assignment($packageRecord['camera2'] ?? '', $servicePackageCameraKeyLookup),
        'backupCamera1' => sanitize_service_package_camera_assignment($packageRecord['backupCamera1'] ?? '', $servicePackageCameraKeyLookup),
        'backupCamera2' => sanitize_service_package_camera_assignment($packageRecord['backupCamera2'] ?? '', $servicePackageCameraKeyLookup),
        'sort_order' => max(1, (int) ($packageRecord['sortOrder'] ?? 1)),
    ];
}

foreach ($serviceGroups as &$serviceGroup) {
    usort(
        $serviceGroup['packages'],
        static function (array $left, array $right): int {
            $leftOrder = (int) ($left['sort_order'] ?? 9999);
            $rightOrder = (int) ($right['sort_order'] ?? 9999);

            if ($leftOrder === $rightOrder) {
                return strcasecmp((string) ($left['title'] ?? ''), (string) ($right['title'] ?? ''));
            }

            return $leftOrder <=> $rightOrder;
        }
    );
}
unset($serviceGroup);

$serviceGalleryImageCandidates = $isAdminView ? collect_all_service_gallery_images(__DIR__) : [];

foreach ($serviceGroups as &$serviceGroup) {
    foreach ($serviceGroup['packages'] as &$servicePackage) {
        $servicePackage['images'] = resolve_service_package_slideshow_images($servicePackage, __DIR__);
    }
    unset($servicePackage);
}
unset($serviceGroup);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $isAdminView ? 'Admin Services | Creaty' : 'The Nifty Fifty | Services'; ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>css/style.css?v=20260923-1">
</head>
<body class="events-page services-page">
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
                <a class="topbar-cart" href="<?php echo htmlspecialchars($cartPath, ENT_QUOTES, 'UTF-8'); ?>" aria-label="Reservation">
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
            <?php else: ?>
                <a class="account-pill" href="<?php echo htmlspecialchars($loginPath, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($accountLabel, ENT_QUOTES, 'UTF-8'); ?></a>
            <?php endif; ?>
        </div>

        <nav class="section-nav section-nav-disabled<?php echo $isAdminView ? ' section-nav-admin' : ' section-nav-with-history'; ?>" aria-label="Catalog filters"<?php if ($isAdminView): ?> data-admin-nav data-admin-dashboard-base-url="<?php echo htmlspecialchars($homePath, ENT_QUOTES, 'UTF-8'); ?>"<?php endif; ?>>
            <?php if ($isAdminView): ?>
                <a class="section-nav-section admin-nav-primary" data-admin-nav-item="primary" href="<?php echo htmlspecialchars($homePath . '#admin-dashboard-overview', ENT_QUOTES, 'UTF-8'); ?>">DASHBOARD</a>
                <span class="section-nav-section is-active admin-nav-primary" data-admin-nav-item="primary" aria-current="page">SERVICES</span>
                <a class="section-nav-section admin-nav-primary" data-admin-nav-item="primary" href="<?php echo htmlspecialchars($adminFeaturedProductsPath, ENT_QUOTES, 'UTF-8'); ?>">BRANDS</a>

                <button class="section-nav-section admin-nav-alt" type="button" data-admin-nav-item="swapped" data-admin-nav-pill data-admin-panel-target="equipments" hidden>EQUIPMENTS</button>
                <button class="section-nav-section admin-nav-alt" type="button" data-admin-nav-item="swapped" data-admin-nav-pill data-admin-panel-target="bookings" hidden>RESERVATIONS</button>
                <button class="section-nav-section admin-nav-alt" type="button" data-admin-nav-item="swapped" data-admin-nav-pill data-admin-panel-target="reports" hidden>REPORTS</button>
                <button class="section-nav-section admin-nav-alt" type="button" data-admin-nav-item="swapped" data-admin-nav-pill data-admin-panel-target="users" hidden>USERS</button>

                <button class="section-nav-swap" type="button" data-admin-nav-swap aria-pressed="false" aria-label="Swap admin navigation" title="Show management bar">
                    <img src="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>assets/icons/swap_horizontal_arrows.svg" alt="" aria-hidden="true">
                </button>
            <?php else: ?>
                <span class="section-nav-filter is-disabled" aria-disabled="true">BRANDS</span>
                <a class="section-nav-section" href="<?php echo htmlspecialchars($eventsPath, ENT_QUOTES, 'UTF-8'); ?>">EVENTS</a>
                <a class="section-nav-section is-active" href="<?php echo htmlspecialchars($servicesPath, ENT_QUOTES, 'UTF-8'); ?>" aria-current="page">SERVICES</a>
                <span class="section-nav-filter is-disabled" aria-disabled="true">DATE</span>
                <?php if ($isCustomerLoggedIn): ?>
                    <a class="section-nav-history-link" href="<?php echo htmlspecialchars($cartPath . '?view=history', ENT_QUOTES, 'UTF-8'); ?>">Reservation History</a>
                <?php endif; ?>
            <?php endif; ?>
        </nav>
    </header>

    <main class="events-shell">
        <section class="catalog-section reveal">
            <div class="catalog-header">
                <a class="catalog-back" href="<?php echo htmlspecialchars($homePath, ENT_QUOTES, 'UTF-8'); ?>" aria-label="Back to home">
                    <span class="catalog-back-icon" aria-hidden="true"></span>
                </a>
                <h1>SERVICES</h1>
            </div>

            <?php if ($isAdminView && isset($_GET['service_updated'])): ?>
                <p class="admin-events-flash-message<?php echo (string) $_GET['service_updated'] === '1' ? ' is-success' : ' is-error'; ?>">
                    <?php echo (string) $_GET['service_updated'] === '1' ? 'Services package updated.' : 'Unable to update services package.'; ?>
                </p>
            <?php endif; ?>

            <?php if ($isAdminView && isset($_GET['service_thumb_updated'])): ?>
                <p class="admin-events-flash-message<?php echo (string) $_GET['service_thumb_updated'] === '1' ? ' is-success' : ' is-error'; ?>">
                    <?php echo (string) $_GET['service_thumb_updated'] === '1' ? 'Slideshow thumbnails updated.' : 'Unable to update slideshow thumbnails.'; ?>
                </p>
            <?php endif; ?>

            <?php foreach ($serviceGroups as $groupIndex => $serviceGroup): ?>
                <?php if ((array) ($serviceGroup['packages'] ?? []) === []): ?>
                    <?php continue; ?>
                <?php endif; ?>

                <?php
                $groupPackages = (array) ($serviceGroup['packages'] ?? []);
                $groupMaxDescriptionChars = 0;

                foreach ($groupPackages as $groupPackage) {
                    $groupDescription = trim((string) (($groupPackage['description'] ?? '')));
                    if (function_exists('mb_strlen')) {
                        $groupDescriptionLength = (int) mb_strlen($groupDescription, 'UTF-8');
                    } else {
                        $groupDescriptionLength = (int) strlen($groupDescription);
                    }

                    if ($groupDescriptionLength > $groupMaxDescriptionChars) {
                        $groupMaxDescriptionChars = $groupDescriptionLength;
                    }
                }

                // Approximate number of wrapped lines for this card width using the longest description in the group.
                $groupDescriptionLines = max(2, (int) ceil($groupMaxDescriptionChars / 36));
                if ($groupMaxDescriptionChars > 0) {
                    // Keep a consistent breathing room above price/action controls.
                    $groupDescriptionLines += 1;
                }
                ?>

                <section
                    class="service-group"
                    aria-labelledby="service-group-<?php echo htmlspecialchars((string) $groupIndex, ENT_QUOTES, 'UTF-8'); ?>"
                    style="--service-description-lines: <?php echo htmlspecialchars((string) $groupDescriptionLines, ENT_QUOTES, 'UTF-8'); ?>;"
                >
                    <h2 class="service-group-label" id="service-group-<?php echo htmlspecialchars((string) $groupIndex, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars((string) ($serviceGroup['label'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></h2>

                    <div class="package-grid">
                        <?php foreach ($groupPackages as $servicePackage): ?>
                            <?php
                            $servicePackageKey = (string) ($servicePackage['key'] ?? '');
                            $servicePackageTitle = trim((string) ($servicePackage['title'] ?? 'PACKAGE'));
                            $servicePackageDescription = trim((string) ($servicePackage['description'] ?? ''));
                            $servicePackageBasePriceValue = (float) ($servicePackage['price_value'] ?? 0);
                            $servicePackageDiscountPercent = max(0, min(95, (int) ($servicePackage['discount_percent'] ?? 0)));
                            $servicePackageDiscountedPriceValue = calculate_discounted_service_package_price($servicePackageBasePriceValue, $servicePackageDiscountPercent);
                            $servicePackageFinalPriceValue = $servicePackageDiscountPercent > 0 ? $servicePackageDiscountedPriceValue : $servicePackageBasePriceValue;
                            $servicePackageBasePriceLabel = format_service_package_price($servicePackageBasePriceValue);
                            $servicePackageDiscountedPriceLabel = format_service_package_price($servicePackageDiscountedPriceValue);
                            $servicePackageFinalPriceLabel = format_service_package_price($servicePackageFinalPriceValue);
                            $servicePackageImages = (array) ($servicePackage['images'] ?? []);
                            $detailPageUrl = $assetBase . $serviceDetailPath . '?package=' . urlencode($servicePackageKey);
                            ?>
                            <article
                                class="package-card<?php echo $servicePackageDiscountPercent > 0 ? ' package-card-highlight' : ''; ?>"
                                data-admin-service-package
                                data-admin-service-package-key="<?php echo htmlspecialchars($servicePackageKey, ENT_QUOTES, 'UTF-8'); ?>"
                                data-admin-service-package-title="<?php echo htmlspecialchars($servicePackageTitle, ENT_QUOTES, 'UTF-8'); ?>"
                                data-admin-service-package-description="<?php echo htmlspecialchars($servicePackageDescription, ENT_QUOTES, 'UTF-8'); ?>"
                                data-admin-service-package-price="<?php echo htmlspecialchars(number_format($servicePackageBasePriceValue, 2, '.', ''), ENT_QUOTES, 'UTF-8'); ?>"
                                data-admin-service-package-discount="<?php echo htmlspecialchars((string) $servicePackageDiscountPercent, ENT_QUOTES, 'UTF-8'); ?>"
                                data-admin-service-package-duration-unit="<?php echo htmlspecialchars((string) ($servicePackage['duration_unit'] ?? 'hours'), ENT_QUOTES, 'UTF-8'); ?>"
                                data-admin-service-package-duration-value="<?php echo htmlspecialchars((string) max(1, (int) ($servicePackage['duration_value'] ?? 1)), ENT_QUOTES, 'UTF-8'); ?>"
                                data-admin-service-package-folder="<?php echo htmlspecialchars((string) ($servicePackage['folder'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                                data-admin-service-selected-thumbnails="<?php echo htmlspecialchars((string) json_encode(array_values((array) ($servicePackage['selected_thumbnail_images'] ?? [])), JSON_UNESCAPED_SLASHES), ENT_QUOTES, 'UTF-8'); ?>"
                                data-admin-service-package-camera-1="<?php echo htmlspecialchars((string) ($servicePackage['camera1'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                                data-admin-service-package-camera-2="<?php echo htmlspecialchars((string) ($servicePackage['camera2'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                                data-admin-service-package-backup-camera-1="<?php echo htmlspecialchars((string) ($servicePackage['backupCamera1'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                                data-admin-service-package-backup-camera-2="<?php echo htmlspecialchars((string) ($servicePackage['backupCamera2'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                            >
                                <?php if ($isAdminView): ?>
                                    <button class="product-card-admin-edit" type="button" data-admin-service-edit aria-label="Edit <?php echo htmlspecialchars($servicePackageTitle, ENT_QUOTES, 'UTF-8'); ?> services package">
                                        <img src="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>assets/icons/pencil.svg" alt="">
                                    </button>
                                    <button class="product-card-admin-remove" type="button" data-admin-remove-service-package aria-label="Remove <?php echo htmlspecialchars($servicePackageTitle, ENT_QUOTES, 'UTF-8'); ?> services package">&times;</button>
                                <?php endif; ?>

                                <?php if ($servicePackageDiscountPercent > 0): ?>
                                    <div class="product-ribbon">PROMO <?php echo htmlspecialchars((string) $servicePackageDiscountPercent, ENT_QUOTES, 'UTF-8'); ?>% OFF!</div>
                                <?php endif; ?>

                                <div
                                    class="package-thumb<?php echo $servicePackageImages !== [] ? ' package-slideshow' : ''; ?>"
                                    <?php if ($servicePackageImages !== []): ?>
                                        data-package-slideshow
                                        data-autoplay-ms="6200"
                                    <?php endif; ?>
                                    aria-label="<?php echo htmlspecialchars($servicePackageTitle, ENT_QUOTES, 'UTF-8'); ?> sample photos"
                                >
                                    <?php if ($servicePackageImages !== []): ?>
                                        <div class="package-slides">
                                            <?php foreach ($servicePackageImages as $imageIndex => $imagePath): ?>
                                                <?php $isVideoSlide = is_supported_service_video_extension((string) $imagePath); ?>
                                                <?php if ($isVideoSlide): ?>
                                                    <video
                                                        class="package-slide<?php echo $imageIndex === 0 ? ' is-active' : ''; ?>"
                                                        src="<?php echo htmlspecialchars(build_service_asset_url($assetBase, (string) $imagePath), ENT_QUOTES, 'UTF-8'); ?>"
                                                        autoplay
                                                        muted
                                                        loop
                                                        playsinline
                                                        preload="metadata"
                                                        aria-hidden="<?php echo $imageIndex === 0 ? 'false' : 'true'; ?>"
                                                    ></video>
                                                <?php else: ?>
                                                    <img
                                                        class="package-slide<?php echo $imageIndex === 0 ? ' is-active' : ''; ?>"
                                                        src="<?php echo htmlspecialchars(build_service_asset_url($assetBase, (string) $imagePath), ENT_QUOTES, 'UTF-8'); ?>"
                                                        alt="<?php echo htmlspecialchars($servicePackageTitle . ' sample ' . ($imageIndex + 1), ENT_QUOTES, 'UTF-8'); ?>"
                                                        loading="lazy"
                                                        aria-hidden="<?php echo $imageIndex === 0 ? 'false' : 'true'; ?>"
                                                    >
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php else: ?>
                                        <div class="package-empty-state">Thumbnail coming soon.</div>
                                    <?php endif; ?>
                                </div>

                                <div class="package-body">
                                    <h2><?php echo htmlspecialchars($servicePackageTitle, ENT_QUOTES, 'UTF-8'); ?></h2>
                                    <p class="service-package-description"><?php echo htmlspecialchars($servicePackageDescription, ENT_QUOTES, 'UTF-8'); ?></p>
                                    <div class="package-footer">
                                        <span class="package-price-stack">
                                            <?php if ($servicePackageDiscountPercent > 0): ?>
                                                <span class="package-price-original"><?php echo htmlspecialchars($servicePackageBasePriceLabel, ENT_QUOTES, 'UTF-8'); ?></span>
                                                <span class="package-price-discounted"><?php echo htmlspecialchars($servicePackageDiscountedPriceLabel, ENT_QUOTES, 'UTF-8'); ?></span>
                                            <?php else: ?>
                                                <span class="package-price-discounted"><?php echo htmlspecialchars($servicePackageFinalPriceLabel, ENT_QUOTES, 'UTF-8'); ?></span>
                                            <?php endif; ?>
                                        </span>
                                        <button
                                            type="button"
                                            onclick="window.location.href='<?php echo htmlspecialchars($detailPageUrl, ENT_QUOTES, 'UTF-8'); ?>'"
                                        >
                                            OPEN PACKAGE
                                        </button>
                                    </div>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endforeach; ?>
        </section>
    </main>

    <?php if ($isAdminView): ?>
        <div class="admin-edit-modal-backdrop admin-service-edit-modal-backdrop" data-admin-service-edit-backdrop hidden>
            <section class="admin-edit-modal admin-service-edit-modal" role="dialog" aria-modal="true" aria-labelledby="admin-service-edit-title">
                <div class="admin-edit-modal-head">
                    <h2 id="admin-service-edit-title">Edit Services Package</h2>
                    <button class="admin-edit-close" type="button" data-admin-service-edit-close aria-label="Close edit window">&times;</button>
                </div>

                <div class="admin-service-edit-tabs" role="tablist" aria-label="Services package edit sections">
                    <button class="admin-service-edit-tab is-active" type="button" data-admin-service-edit-tab="details" aria-selected="true">Details</button>
                    <button class="admin-service-edit-tab" type="button" data-admin-service-edit-tab="duration" aria-selected="false">Duration</button>
                    <button class="admin-service-edit-tab" type="button" data-admin-service-edit-tab="camera" aria-selected="false">Camera</button>
                </div>

                <form class="admin-edit-form admin-service-edit-form" method="post" action="" data-admin-service-edit-form>
                    <input type="hidden" name="admin_service_action" value="update_service_package">
                    <input type="hidden" name="package_key" value="" data-admin-service-edit-key>

                    <div class="admin-edit-fields-column admin-service-edit-panel is-active" data-admin-service-edit-panel="details">
                        <label class="admin-edit-label" for="admin-service-edit-name">Package Name</label>
                        <input id="admin-service-edit-name" type="text" name="title" data-admin-service-edit-name required>

                        <label class="admin-edit-label" for="admin-service-edit-description">Description</label>
                        <textarea id="admin-service-edit-description" name="description" rows="4" maxlength="256" data-admin-service-edit-description></textarea>

                        <label class="admin-edit-label" for="admin-service-edit-price">Price</label>
                        <div class="admin-edit-money-field">
                            <span class="admin-edit-currency" aria-hidden="true">&#8369;</span>
                            <input id="admin-service-edit-price" type="number" min="0" step="0.01" name="price" data-admin-service-edit-price required>
                        </div>

                        <label class="admin-edit-label" for="admin-service-edit-discount">Discount Percentage</label>
                        <input id="admin-service-edit-discount" type="number" min="0" max="95" step="1" name="discountPercent" data-admin-service-edit-discount>
                    </div>

                    <div class="admin-edit-fields-column admin-service-edit-panel" data-admin-service-edit-panel="duration" hidden>
                        <label class="admin-edit-label" for="admin-service-edit-duration-unit">Duration Unit</label>
                        <select id="admin-service-edit-duration-unit" name="durationUnit" data-admin-service-edit-duration-unit required>
                            <option value="hours">Hours</option>
                            <option value="days">Days</option>
                        </select>

                        <label class="admin-edit-label" for="admin-service-edit-duration-value">Duration Value</label>
                        <input id="admin-service-edit-duration-value" type="number" min="1" max="24" step="1" name="durationValue" data-admin-service-edit-duration-value required>
                    </div>

                    <div class="admin-edit-fields-column admin-service-edit-panel" data-admin-service-edit-panel="camera" hidden>
                        <label class="admin-edit-label" for="admin-service-edit-camera-1">Camera 1</label>
                        <select id="admin-service-edit-camera-1" name="camera1" data-admin-service-edit-camera-1>
                            <option value="">Select Camera</option>
                            <?php foreach ($servicePackageCameraOptions as $cameraOption): ?>
                                <option value="<?php echo htmlspecialchars((string) ($cameraOption['key'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars((string) ($cameraOption['label'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></option>
                            <?php endforeach; ?>
                        </select>

                        <label class="admin-edit-label" for="admin-service-edit-camera-2">Camera 2</label>
                        <select id="admin-service-edit-camera-2" name="camera2" data-admin-service-edit-camera-2>
                            <option value="">Select Camera</option>
                            <?php foreach ($servicePackageCameraOptions as $cameraOption): ?>
                                <option value="<?php echo htmlspecialchars((string) ($cameraOption['key'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars((string) ($cameraOption['label'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></option>
                            <?php endforeach; ?>
                        </select>

                        <label class="admin-edit-label" for="admin-service-edit-backup-camera-1">Backup Camera 1</label>
                        <select id="admin-service-edit-backup-camera-1" name="backupCamera1" data-admin-service-edit-backup-camera-1>
                            <option value="">Select Camera</option>
                            <?php foreach ($servicePackageCameraOptions as $cameraOption): ?>
                                <option value="<?php echo htmlspecialchars((string) ($cameraOption['key'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars((string) ($cameraOption['label'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></option>
                            <?php endforeach; ?>
                        </select>

                        <label class="admin-edit-label" for="admin-service-edit-backup-camera-2">Backup Camera 2</label>
                        <select id="admin-service-edit-backup-camera-2" name="backupCamera2" data-admin-service-edit-backup-camera-2>
                            <option value="">Select Camera</option>
                            <?php foreach ($servicePackageCameraOptions as $cameraOption): ?>
                                <option value="<?php echo htmlspecialchars((string) ($cameraOption['key'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars((string) ($cameraOption['label'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="admin-edit-actions">
                        <button type="button" class="admin-edit-secondary admin-event-edit-thumbs-open" data-admin-service-set-thumbnails-edit>Set Thumbnail</button>
                        <button type="button" class="admin-edit-secondary" data-admin-service-edit-cancel>Cancel</button>
                        <button type="submit" class="admin-edit-primary">Save</button>
                    </div>
                </form>
            </section>
        </div>

        <div class="admin-edit-modal-backdrop admin-event-thumbs-modal-backdrop" data-admin-service-thumbs-backdrop hidden>
            <section class="admin-edit-modal admin-event-thumbs-modal" role="dialog" aria-modal="true" aria-labelledby="admin-service-thumbs-title">
                <div class="admin-edit-modal-head">
                    <h2 id="admin-service-thumbs-title">Set Slideshow Thumbnails</h2>
                    <button class="admin-edit-close" type="button" data-admin-service-thumbs-close aria-label="Close thumbnail selector">&times;</button>
                </div>

                <form class="admin-edit-form admin-event-thumbs-form" method="post" action="" data-admin-service-thumbs-form>
                    <input type="hidden" name="admin_service_action" value="update_service_package_thumbnails">
                    <input type="hidden" name="package_key" value="" data-admin-service-thumbs-key>
                    <input type="hidden" name="selected_paths_json" value="[]" data-admin-service-thumbs-input>

                    <p class="admin-event-thumbs-note">
                        Select media in order. The number badge shows slideshow order for <strong data-admin-service-thumbs-package-title>this package</strong>.
                    </p>

                    <?php if ($serviceGalleryImageCandidates === []): ?>
                        <p class="admin-event-thumbs-empty">No service collection media found.</p>
                    <?php else: ?>
                        <div class="admin-event-thumbs-grid" data-admin-service-thumbs-grid>
                            <?php foreach ($serviceGalleryImageCandidates as $candidatePath): ?>
                                <?php $candidateLabel = str_replace('assets/service_packages/', '', (string) $candidatePath); ?>
                                <?php $candidateFolder = service_gallery_folder_from_path((string) $candidatePath); ?>
                                <?php $candidateIsVideo = is_supported_service_video_extension((string) $candidatePath); ?>
                                <button
                                    class="admin-event-thumb-item"
                                    type="button"
                                    data-admin-service-thumb-item
                                    data-image-path="<?php echo htmlspecialchars((string) $candidatePath, ENT_QUOTES, 'UTF-8'); ?>"
                                    data-image-folder="<?php echo htmlspecialchars((string) $candidateFolder, ENT_QUOTES, 'UTF-8'); ?>"
                                    aria-label="Toggle thumbnail <?php echo htmlspecialchars((string) $candidateLabel, ENT_QUOTES, 'UTF-8'); ?>"
                                >
                                    <span class="admin-event-thumb-order" data-admin-service-thumb-order hidden></span>
                                    <?php if ($candidateIsVideo): ?>
                                        <video src="<?php echo htmlspecialchars(build_service_asset_url($assetBase, (string) $candidatePath), ENT_QUOTES, 'UTF-8'); ?>" autoplay muted loop playsinline preload="metadata"></video>
                                    <?php else: ?>
                                        <img src="<?php echo htmlspecialchars(build_service_asset_url($assetBase, (string) $candidatePath), ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars((string) $candidateLabel, ENT_QUOTES, 'UTF-8'); ?>">
                                    <?php endif; ?>
                                    <span class="admin-event-thumb-meta"><?php echo htmlspecialchars((string) $candidateLabel, ENT_QUOTES, 'UTF-8'); ?></span>
                                </button>
                            <?php endforeach; ?>
                        </div>
                        <p class="admin-event-thumbs-empty" data-admin-service-thumbs-folder-empty hidden>No media found in this package category.</p>
                    <?php endif; ?>

                    <div class="admin-edit-actions">
                        <button type="button" class="admin-edit-secondary" data-admin-service-thumbs-cancel>Cancel</button>
                        <button type="submit" class="admin-edit-primary">Save Thumbnails</button>
                    </div>
                </form>
            </section>
        </div>
    <?php else: ?>
        <?php require __DIR__ . '/customer_message_modal.php'; ?>
        <?php render_customer_notification_modal($customerNotificationCenter); ?>
        <?php render_customer_notification_center_bootstrap_script($customerNotificationCenter, $assetBase); ?>
    <?php endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script src="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>js/script.js?v=20260923-1"></script>
</body>
</html>
