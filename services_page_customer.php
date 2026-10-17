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

function service_package_collect_uploads(string $fieldName, string $packageKey, string $projectRoot): array
{
    $saved = [];

    if (!isset($_FILES[$fieldName]) || !is_array($_FILES[$fieldName]['name'] ?? null) || $packageKey === '') {
        return $saved;
    }

    $targetDir = $projectRoot . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'service_packages' . DIRECTORY_SEPARATOR . $packageKey . DIRECTORY_SEPARATOR . 'uploads';
    if (!is_dir($targetDir) && !@mkdir($targetDir, 0775, true) && !is_dir($targetDir)) {
        return $saved;
    }

    $allowedMimePrefixes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif', 'video/mp4', 'video/webm', 'video/ogg', 'application/ogg'];
    $maxBytes = 200 * 1024 * 1024;
    $names = $_FILES[$fieldName]['name'];
    $count = min(count($names), 30);

    for ($i = 0; $i < $count; $i++) {
        $error = (int) ($_FILES[$fieldName]['error'][$i] ?? UPLOAD_ERR_NO_FILE);
        $tmpName = (string) ($_FILES[$fieldName]['tmp_name'][$i] ?? '');
        $size = (int) ($_FILES[$fieldName]['size'][$i] ?? 0);
        $originalName = (string) ($names[$i] ?? '');

        if ($error !== UPLOAD_ERR_OK || $tmpName === '' || !is_uploaded_file($tmpName) || $size <= 0 || $size > $maxBytes) {
            continue;
        }

        if (!is_supported_service_image_extension($originalName)) {
            continue;
        }

        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = $finfo ? (string) finfo_file($finfo, $tmpName) : '';
            if ($finfo) {
                finfo_close($finfo);
            }
            if ($mime !== '' && !in_array($mime, $allowedMimePrefixes, true)) {
                continue;
            }
        }

        $extension = strtolower((string) pathinfo($originalName, PATHINFO_EXTENSION));
        $baseName = preg_replace('/[^a-zA-Z0-9_-]+/', '-', (string) pathinfo($originalName, PATHINFO_FILENAME));
        $baseName = trim((string) $baseName, '-');
        if ($baseName === '' || strpos($baseName, '_') === 0) {
            $baseName = 'photo';
        }

        $fileName = $baseName . '-' . substr(md5(uniqid((string) mt_rand(), true)), 0, 8) . '.' . $extension;

        if (move_uploaded_file($tmpName, $targetDir . DIRECTORY_SEPARATOR . $fileName)) {
            $saved[] = 'assets/service_packages/' . $packageKey . '/uploads/' . $fileName;
        }
    }

    return $saved;
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

                $uploadedPaths = service_package_collect_uploads('package_photos', $packageKey, __DIR__);
                if ($uploadedPaths !== []) {
                    $existingThumbs = (array) ($servicePackagesRepository[$packageKey]['thumbnail_images'] ?? []);
                    $servicePackagesRepository[$packageKey]['thumbnail_images'] = array_values(array_merge($existingThumbs, $uploadedPaths));
                }

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

    if ($adminServiceAction === 'add_service_package') {
        $newType = normalize_service_package_type($_POST['serviceType'] ?? default_service_package_type());
        $newTitle = trim((string) ($_POST['title'] ?? ''));
        $newDescription = trim((string) ($_POST['description'] ?? ''));
        $newDescription = function_exists('mb_substr') ? trim((string) mb_substr($newDescription, 0, 256, 'UTF-8')) : trim((string) substr($newDescription, 0, 256));
        $newPrice = (float) ($_POST['price'] ?? 0);
        $newDiscount = max(0, min(95, (int) ($_POST['discountPercent'] ?? 0)));
        $newDurationUnit = normalize_service_package_duration_unit($_POST['durationUnit'] ?? 'hours');
        $newDurationValue = clamp_service_package_duration_value($newDurationUnit, (int) ($_POST['durationValue'] ?? 1));
        $added = false;

        if ($newTitle !== '' && $newPrice >= 0) {
            $baseKey = normalize_service_package_key($newType . '-' . $newTitle);
            $newKey = $baseKey;
            $suffix = 2;
            while ($newKey === '' || isset($servicePackagesRepository[$newKey])) {
                $newKey = $baseKey . '-' . $suffix;
                $suffix++;
            }

            $maxSort = 0;
            foreach ($servicePackagesRepository as $existingRecord) {
                if (is_array($existingRecord) && normalize_service_package_type($existingRecord['serviceType'] ?? '') === $newType) {
                    $maxSort = max($maxSort, (int) ($existingRecord['sortOrder'] ?? 0));
                }
            }

            $servicePackagesRepository[$newKey] = [
                'serviceType' => $newType,
                'sortOrder' => $maxSort + 1,
                'title' => $newTitle,
                'description' => $newDescription,
                'price' => number_format($newPrice, 2, '.', ''),
                'discountPercent' => $newDiscount,
                'durationUnit' => $newDurationUnit,
                'durationValue' => $newDurationValue,
                'thumbnail_images' => service_package_collect_uploads('package_photos', $newKey, __DIR__),
                'camera1' => sanitize_service_package_camera_assignment($_POST['camera1'] ?? '', $servicePackageCameraKeyLookup),
                'camera2' => sanitize_service_package_camera_assignment($_POST['camera2'] ?? '', $servicePackageCameraKeyLookup),
                'backupCamera1' => sanitize_service_package_camera_assignment($_POST['backupCamera1'] ?? '', $servicePackageCameraKeyLookup),
                'backupCamera2' => sanitize_service_package_camera_assignment($_POST['backupCamera2'] ?? '', $servicePackageCameraKeyLookup),
            ];

            $added = save_services_packages_repository($servicePackagesRepository);
        }

        $redirectTarget = $_SERVER['REQUEST_URI'] ?? ($assetBase . 'admin/services/');
        $redirectPath = strtok($redirectTarget, '?') ?: $redirectTarget;
        $query = $_GET;
        $query['service_added'] = $added ? '1' : '0';

        header('Location: ' . $redirectPath . '?' . http_build_query($query));
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

// Data for the Brands / Date / Filters dropdowns. Picking a filter here sends the visitor to the homepage catalog.
$catalogHomePath = $assetBase !== '' ? $assetBase : './';
$productBrandValueMap = [];
$catalogSkillOptions = [];
$catalogCategoryOptions = [];
$catalogPriceMaximum = 100;

if (!$isAdminView) {
    $catalogProducts = load_products_repository();

    if (!is_array($catalogProducts)) {
        $catalogProducts = [];
    }

    $productBrandValueMap = product_brand_value_map(load_product_brands_repository());
    $catalogSkillOptions = product_skill_level_options();
    $catalogCategoryOptions = product_category_value_map(load_product_categories_repository());

    foreach ($catalogProducts as $catalogProduct) {
        if (!is_array($catalogProduct)) {
            continue;
        }
        $catalogPrice = max(0, (float) ($catalogProduct['price'] ?? 0));
        $catalogDiscount = max(0, min(95, (int) ($catalogProduct['discountPercent'] ?? 0)));
        $catalogPriceMaximum = max($catalogPriceMaximum, $catalogPrice * (1 - $catalogDiscount / 100));
    }
    $catalogPriceMaximum = (int) (ceil($catalogPriceMaximum / 100) * 100);
}
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
    <link rel="stylesheet" href="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>css/style.css?v=20261015-2">
    <?php if (!$isAdminView): ?>
        <link rel="stylesheet" href="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>css/catalog-filters.css?v=20261015-1">
    <?php endif; ?>
</head>
<body class="events-page services-page"<?php if (!$isAdminView): ?> data-catalog-redirect-url="<?php echo htmlspecialchars($catalogHomePath, ENT_QUOTES, 'UTF-8'); ?>"<?php endif; ?>>
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

                    <?php
                    $manageIsStaff = !isset($_SESSION['user_id']) && isset($_SESSION['staff_id']);
                    $manageBase = $homePath;
                    ?>
                    <div class="dropdown topbar-account-menu topbar-manage-menu" style="margin-right: 0.5rem;" data-admin-manage-menu>
                        <button class="account-pill account-pill-toggle dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" data-admin-manage-toggle>
                            Manage
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end account-dropdown-menu">
                            <li><a class="dropdown-item" href="<?php echo htmlspecialchars($manageBase . '?admin_view=equipments', ENT_QUOTES, 'UTF-8'); ?>">Equipments</a></li>
                            <li><a class="dropdown-item" href="<?php echo htmlspecialchars($manageBase . '?admin_view=bookings', ENT_QUOTES, 'UTF-8'); ?>">Reservations</a></li>
                            <?php if (!$manageIsStaff): ?>
                                <li><a class="dropdown-item" href="<?php echo htmlspecialchars($manageBase . '?admin_view=reports', ENT_QUOTES, 'UTF-8'); ?>">Reports</a></li>
                                <li><a class="dropdown-item" href="<?php echo htmlspecialchars($manageBase . '?admin_view=users', ENT_QUOTES, 'UTF-8'); ?>">Users</a></li>
                            <?php endif; ?>
                        </ul>
                    </div>

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

        <nav class="section-nav<?php echo $isAdminView ? ' section-nav-disabled section-nav-admin' : ' section-nav-interactive section-nav-with-history'; ?>" aria-label="Catalog filters"<?php if ($isAdminView): ?> data-admin-nav data-admin-dashboard-base-url="<?php echo htmlspecialchars($homePath, ENT_QUOTES, 'UTF-8'); ?>"<?php endif; ?>>
            <?php if ($isAdminView): ?>
                <a class="section-nav-section admin-nav-primary" data-admin-nav-item="primary" href="<?php echo htmlspecialchars($homePath . '#admin-dashboard-overview', ENT_QUOTES, 'UTF-8'); ?>">DASHBOARD</a>
                <span class="section-nav-section is-active admin-nav-primary" data-admin-nav-item="primary" aria-current="page">SERVICES</span>
                <a class="section-nav-section admin-nav-primary" data-admin-nav-item="primary" href="<?php echo htmlspecialchars($adminFeaturedProductsPath, ENT_QUOTES, 'UTF-8'); ?>">BRANDS</a>

                <button class="section-nav-section admin-nav-alt" type="button" data-admin-nav-item="swapped" data-admin-nav-pill data-admin-panel-target="equipments" hidden>EQUIPMENTS</button>
                <button class="section-nav-section admin-nav-alt" type="button" data-admin-nav-item="swapped" data-admin-nav-pill data-admin-panel-target="bookings" hidden>RESERVATIONS</button>
                <button class="section-nav-section admin-nav-alt" type="button" data-admin-nav-item="swapped" data-admin-nav-pill data-admin-panel-target="reports" hidden>REPORTS</button>
                <button class="section-nav-section admin-nav-alt" type="button" data-admin-nav-item="swapped" data-admin-nav-pill data-admin-panel-target="users" hidden>USERS</button>

                <button class="section-nav-swap" type="button" data-admin-nav-swap aria-pressed="false" aria-label="Swap admin navigation" title="Show management bar" hidden tabindex="-1" style="display:none !important;">
                    <img src="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>assets/icons/swap_horizontal_arrows.svg" alt="" aria-hidden="true">
                </button>

                <button class="section-nav-settings" type="button" data-services-open-dashboard-tool="settings" aria-label="Dashboard settings" title="Dashboard settings">
                    <img src="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>assets/icons/gear.svg" alt="" aria-hidden="true">
                </button>

                <button class="section-nav-print" type="button" data-services-open-dashboard-tool="print" aria-label="Print business report" title="Print business report">
                    <img src="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>assets/icons/print.svg" alt="" aria-hidden="true">
                </button>
            <?php else: ?>
                <div class="section-nav-item section-nav-item-filter">
                    <button class="section-nav-filter filter-toggle" type="button" aria-expanded="false" aria-controls="brands-filter-panel">
                        BRANDS
                    </button>

                    <div class="filter-panel filter-panel-brands" id="brands-filter-panel" hidden>
                        <button class="filter-option is-selected" type="button" data-filter-group="brand" data-filter-value="all">ALL BRANDS</button>
                        <?php foreach ($productBrandValueMap as $brandValue => $brandLabel): ?>
                            <button class="filter-option" type="button" data-filter-group="brand" data-filter-value="<?php echo htmlspecialchars($brandValue, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars(strtoupper($brandLabel), ENT_QUOTES, 'UTF-8'); ?></button>
                        <?php endforeach; ?>
                    </div>
                </div>

                <a class="section-nav-section" href="<?php echo htmlspecialchars($eventsPath, ENT_QUOTES, 'UTF-8'); ?>">EVENTS</a>
                <a class="section-nav-section is-active" href="<?php echo htmlspecialchars($servicesPath, ENT_QUOTES, 'UTF-8'); ?>" aria-current="page">SERVICES</a>

                <div class="section-nav-item section-nav-item-filter">
                    <button class="section-nav-filter filter-toggle" type="button" aria-expanded="false" aria-controls="date-filter-panel">
                        DATE
                    </button>

                    <div class="filter-panel filter-panel-date" id="date-filter-panel" hidden>
                        <div class="date-picker-tabs" role="tablist" aria-label="Date filter groups">
                            <button class="date-picker-tab is-active" type="button" data-date-tab="month" aria-selected="true">Month</button>
                            <button class="date-picker-tab" type="button" data-date-tab="day" aria-selected="false">Day</button>
                            <button class="date-picker-tab" type="button" data-date-tab="year" aria-selected="false">Year</button>
                        </div>

                        <div class="date-picker-content">
                            <section class="date-picker-view is-active" data-date-view="month" aria-label="Choose month">
                                <div class="filter-group-options date-filter-options-grid" data-date-month-options></div>
                            </section>

                            <section class="date-picker-view" data-date-view="day" aria-label="Choose day">
                                <div class="date-calendar-panel">
                                    <div class="date-calendar-header">
                                        <p class="date-calendar-title" data-calendar-title>Calendar</p>
                                    </div>
                                    <div class="date-calendar-weekdays" aria-hidden="true">
                                        <span>Sun</span>
                                        <span>Mon</span>
                                        <span>Tue</span>
                                        <span>Wed</span>
                                        <span>Thu</span>
                                        <span>Fri</span>
                                        <span>Sat</span>
                                    </div>
                                    <div class="date-calendar-grid" data-date-calendar-grid></div>
                                    <button class="filter-option date-clear-option" type="button" data-filter-group="day" data-filter-value="all">All Days</button>
                                </div>
                            </section>

                            <section class="date-picker-view" data-date-view="year" aria-label="Choose year">
                                <div class="filter-group-options date-filter-options-grid" data-date-year-options></div>
                            </section>
                        </div>
                    </div>
                </div>

                <div class="section-nav-item section-nav-item-filter catalog-filter-item">
                    <button class="section-nav-filter filter-toggle catalog-filter-toggle" type="button" aria-expanded="false" aria-controls="catalog-filter-panel">
                        FILTERS <span class="catalog-filter-count" data-catalog-filter-count hidden>0</span>
                    </button>

                    <form class="filter-panel catalog-filter-panel" id="catalog-filter-panel" data-catalog-filter-form hidden>
                        <div class="catalog-filter-heading">
                            <h2>Filter products</h2>
                            <button class="catalog-filter-reset" type="button" data-catalog-filter-reset>Clear all</button>
                        </div>

                        <fieldset class="catalog-filter-group">
                            <legend>Skill level</legend>
                            <div class="catalog-filter-options">
                                <?php foreach ($catalogSkillOptions as $catalogSkill): ?>
                                    <label class="catalog-filter-choice">
                                        <input type="checkbox" name="skills[]" value="<?php echo htmlspecialchars(strtolower($catalogSkill), ENT_QUOTES, 'UTF-8'); ?>" data-catalog-skill>
                                        <span><?php echo htmlspecialchars($catalogSkill, ENT_QUOTES, 'UTF-8'); ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </fieldset>

                        <fieldset class="catalog-filter-group">
                            <legend>Category</legend>
                            <div class="catalog-filter-options">
                                <?php foreach ($catalogCategoryOptions as $catalogCategoryValue => $catalogCategoryLabel): ?>
                                    <label class="catalog-filter-choice">
                                        <input type="checkbox" name="categories[]" value="<?php echo htmlspecialchars($catalogCategoryValue, ENT_QUOTES, 'UTF-8'); ?>" data-catalog-category>
                                        <span><?php echo htmlspecialchars($catalogCategoryLabel, ENT_QUOTES, 'UTF-8'); ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </fieldset>

                        <fieldset class="catalog-filter-group">
                            <legend>Price range</legend>
                            <div class="catalog-price-fields">
                                <label for="catalog-price-min">From</label>
                                <div class="catalog-price-input">
                                    <span aria-hidden="true">&#8369;</span>
                                    <input id="catalog-price-min" name="min_price" type="number" min="0" max="<?php echo $catalogPriceMaximum; ?>" step="0.01" value="0" inputmode="decimal" data-catalog-price="min">
                                </div>
                                <input class="catalog-price-slider" type="range" min="0" max="<?php echo $catalogPriceMaximum; ?>" step="0.01" value="0" aria-label="Minimum price" data-catalog-range="min">

                                <label for="catalog-price-max">To</label>
                                <div class="catalog-price-input">
                                    <span aria-hidden="true">&#8369;</span>
                                    <input id="catalog-price-max" name="max_price" type="number" min="0" max="<?php echo $catalogPriceMaximum; ?>" step="0.01" value="<?php echo $catalogPriceMaximum; ?>" inputmode="decimal" data-catalog-price="max">
                                </div>
                                <input class="catalog-price-slider" type="range" min="0" max="<?php echo $catalogPriceMaximum; ?>" step="0.01" value="<?php echo $catalogPriceMaximum; ?>" aria-label="Maximum price" data-catalog-range="max">
                            </div>
                        </fieldset>

                        <button class="catalog-filter-results" type="submit" data-catalog-view-results>View products</button>
                    </form>
                </div>
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

            <?php if ($isAdminView && isset($_GET['service_added'])): ?>
                <p class="admin-events-flash-message<?php echo (string) $_GET['service_added'] === '1' ? ' is-success' : ' is-error'; ?>">
                    <?php echo (string) $_GET['service_added'] === '1' ? 'New services package added.' : 'Unable to add services package. Check the package name and price.'; ?>
                </p>
            <?php endif; ?>

            <?php if ($isAdminView && isset($_GET['service_thumb_updated'])): ?>
                <p class="admin-events-flash-message<?php echo (string) $_GET['service_thumb_updated'] === '1' ? ' is-success' : ' is-error'; ?>">
                    <?php echo (string) $_GET['service_thumb_updated'] === '1' ? 'Slideshow thumbnails updated.' : 'Unable to update slideshow thumbnails.'; ?>
                </p>
            <?php endif; ?>

            <?php foreach ($serviceGroups as $groupIndex => $serviceGroup): ?>
                <?php if ((array) ($serviceGroup['packages'] ?? []) === [] && !$isAdminView): ?>
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
                    <?php if ($isAdminView): ?>
                        <div class="admin-service-add-row">
                            <button class="admin-service-add-button" type="button" data-admin-service-add-open data-service-type="<?php echo htmlspecialchars((string) ($serviceGroup['key'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" data-service-type-label="<?php echo htmlspecialchars((string) ($serviceGroup['label'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">+ Add Package</button>
                        </div>
                    <?php endif; ?>

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

                <form class="admin-edit-form admin-service-edit-form" method="post" action="" enctype="multipart/form-data" data-admin-service-edit-form>
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

                        <label class="admin-edit-label" for="admin-service-edit-photos">Add Photos / Videos for this Package</label>
                        <input id="admin-service-edit-photos" type="file" name="package_photos[]" accept="image/jpeg,image/png,image/webp,image/gif,video/mp4,video/webm,video/ogg" multiple>
                        <small class="admin-service-photo-hint">Idadagdag sa slideshow ng package. JPG, PNG, WEBP, GIF, MP4, WEBM.</small>
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

        <div class="admin-edit-modal-backdrop admin-service-edit-modal-backdrop" data-admin-service-add-backdrop hidden>
            <section class="admin-edit-modal admin-service-edit-modal" role="dialog" aria-modal="true" aria-labelledby="admin-service-add-title">
                <div class="admin-edit-modal-head">
                    <h2 id="admin-service-add-title">Add Package &mdash; <span data-admin-service-add-type-label>Photography</span></h2>
                    <button class="admin-edit-close" type="button" data-admin-service-add-close aria-label="Close add window">&times;</button>
                </div>

                <form class="admin-edit-form admin-service-edit-form" method="post" action="" enctype="multipart/form-data">
                    <input type="hidden" name="admin_service_action" value="add_service_package">
                    <input type="hidden" name="serviceType" value="photography" data-admin-service-add-type>

                    <div class="admin-edit-fields-column admin-service-edit-panel is-active">
                        <label class="admin-edit-label" for="admin-service-add-name">Package Name</label>
                        <input id="admin-service-add-name" type="text" name="title" placeholder="e.g. PACKAGE 4" required>

                        <label class="admin-edit-label" for="admin-service-add-description">Description</label>
                        <textarea id="admin-service-add-description" name="description" rows="3" maxlength="256"></textarea>

                        <label class="admin-edit-label" for="admin-service-add-price">Price</label>
                        <div class="admin-edit-money-field">
                            <span class="admin-edit-currency" aria-hidden="true">&#8369;</span>
                            <input id="admin-service-add-price" type="number" min="0" step="0.01" name="price" required>
                        </div>

                        <label class="admin-edit-label" for="admin-service-add-discount">Discount Percentage</label>
                        <input id="admin-service-add-discount" type="number" min="0" max="95" step="1" name="discountPercent" value="0">

                        <label class="admin-edit-label" for="admin-service-add-duration-unit">Duration Unit</label>
                        <select id="admin-service-add-duration-unit" name="durationUnit">
                            <option value="hours">Hours</option>
                            <option value="days">Days</option>
                        </select>

                        <label class="admin-edit-label" for="admin-service-add-duration-value">Duration Value</label>
                        <input id="admin-service-add-duration-value" type="number" min="1" max="24" step="1" name="durationValue" value="3" required>

                        <label class="admin-edit-label" for="admin-service-add-camera-1">Camera 1</label>
                        <select id="admin-service-add-camera-1" name="camera1"><option value="">Select Camera</option><?php foreach ($servicePackageCameraOptions as $cameraOption): ?><option value="<?php echo htmlspecialchars((string) ($cameraOption['key'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars((string) ($cameraOption['label'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></option><?php endforeach; ?></select>

                        <label class="admin-edit-label" for="admin-service-add-camera-2">Camera 2</label>
                        <select id="admin-service-add-camera-2" name="camera2"><option value="">Select Camera</option><?php foreach ($servicePackageCameraOptions as $cameraOption): ?><option value="<?php echo htmlspecialchars((string) ($cameraOption['key'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars((string) ($cameraOption['label'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></option><?php endforeach; ?></select>

                        <label class="admin-edit-label" for="admin-service-add-backup-1">Backup Camera 1</label>
                        <select id="admin-service-add-backup-1" name="backupCamera1"><option value="">Select Camera</option><?php foreach ($servicePackageCameraOptions as $cameraOption): ?><option value="<?php echo htmlspecialchars((string) ($cameraOption['key'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars((string) ($cameraOption['label'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></option><?php endforeach; ?></select>

                        <label class="admin-edit-label" for="admin-service-add-backup-2">Backup Camera 2</label>
                        <select id="admin-service-add-backup-2" name="backupCamera2"><option value="">Select Camera</option><?php foreach ($servicePackageCameraOptions as $cameraOption): ?><option value="<?php echo htmlspecialchars((string) ($cameraOption['key'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars((string) ($cameraOption['label'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></option><?php endforeach; ?></select>

                        <label class="admin-edit-label" for="admin-service-add-photos">Photos / Videos</label>
                        <input id="admin-service-add-photos" type="file" name="package_photos[]" accept="image/jpeg,image/png,image/webp,image/gif,video/mp4,video/webm,video/ogg" multiple>
                    </div>

                    <div class="admin-edit-actions">
                        <button type="button" class="admin-edit-secondary" data-admin-service-add-close>Cancel</button>
                        <button type="submit" class="admin-edit-primary">Add Package</button>
                    </div>
                </form>
            </section>
        </div>

        <style>
            .admin-service-add-row { margin: 0 0 1rem; }
            .admin-service-add-button { border: 1px solid rgba(255,255,255,.25); background: rgba(255,255,255,.06); color: inherit; border-radius: 12px; padding: .5rem 1rem; font-weight: 700; cursor: pointer; }
            .admin-service-add-button:hover { background: rgba(255,255,255,.14); }
            .admin-service-photo-hint { display: block; opacity: .7; margin-top: .35rem; }
            .admin-service-edit-modal-backdrop .admin-service-edit-form { max-height: 80vh; overflow-y: auto; }
            .section-nav-admin .section-nav-swap { display: none !important; }
        </style>
        <script>
            (function () {
                var backdrop = document.querySelector('[data-admin-service-add-backdrop]');
                if (backdrop) {
                    var typeInput = backdrop.querySelector('[data-admin-service-add-type]');
                    var typeLabel = backdrop.querySelector('[data-admin-service-add-type-label]');
                    document.querySelectorAll('[data-admin-service-add-open]').forEach(function (btn) {
                        btn.addEventListener('click', function () {
                            typeInput.value = btn.getAttribute('data-service-type') || 'photography';
                            typeLabel.textContent = btn.getAttribute('data-service-type-label') || '';
                            backdrop.hidden = false;
                        });
                    });
                    backdrop.querySelectorAll('[data-admin-service-add-close]').forEach(function (btn) {
                        btn.addEventListener('click', function () { backdrop.hidden = true; });
                    });
                    backdrop.addEventListener('click', function (e) { if (e.target === backdrop) { backdrop.hidden = true; } });
                }

                document.querySelectorAll('[data-services-open-dashboard-tool]').forEach(function (btn) {
                    btn.addEventListener('click', function () {
                        var base = <?php echo json_encode($homePath, JSON_UNESCAPED_SLASHES); ?>;
                        window.location.href = base + '?open_tool=' + encodeURIComponent(btn.getAttribute('data-services-open-dashboard-tool'));
                    });
                });
            })();
        </script>
    <?php else: ?>
        <?php require __DIR__ . '/customer_message_modal.php'; ?>
        <?php render_customer_notification_modal($customerNotificationCenter); ?>
        <?php render_customer_notification_center_bootstrap_script($customerNotificationCenter, $assetBase); ?>
    <?php endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script src="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>js/script.js?v=20261015-1"></script>
</body>
</html>
