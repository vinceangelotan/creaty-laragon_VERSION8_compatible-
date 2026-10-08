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

function parseServicePackagePrice($value): float
{
    return max(0, (float) $value);
}

function formatServicePackagePrice(float $value): string
{
    return 'P ' . number_format(max(0, $value), 2);
}

function calculateDiscountedServicePackagePrice(float $basePrice, int $discountPercent): float
{
    $normalizedDiscount = max(0, min(95, $discountPercent));

    return max(0, $basePrice * (1 - ($normalizedDiscount / 100)));
}

function formatServiceCollectionLabel(string $raw): string
{
    $normalized = trim(preg_replace('/\s+/', ' ', str_replace(['_', '-'], ' ', $raw)) ?? '');
    if ($normalized === '') {
        return 'Untitled';
    }

    $lowerWords = ['and', 'or', 'the', 'for', 'to', 'of', 'in', 'on', 'at'];
    $parts = preg_split('/\s+/', $normalized) ?: [];
    $formatted = [];

    foreach ($parts as $index => $part) {
        if (preg_match('/^[A-Z0-9]{2,}$/', $part) === 1) {
            $formatted[] = $part;
            continue;
        }

        $token = ucfirst(strtolower($part));
        if ($index > 0 && in_array(strtolower($token), $lowerWords, true)) {
            $token = strtolower($token);
        }

        $formatted[] = $token;
    }

    return implode(' ', $formatted);
}

function parseServiceCollectionFolderName(string $folderName): array
{
    $parts = explode('_', $folderName, 2);
    $categoryRaw = $parts[0] ?? $folderName;
    $nameRaw = $parts[1] ?? $categoryRaw;

    return [
        'category' => formatServiceCollectionLabel($categoryRaw),
        'name' => formatServiceCollectionLabel($nameRaw),
    ];
}

/**
 * @return string
 */
function resolveServiceCollectionMediaType(string $relativePath): string
{
    $extension = strtolower((string) pathinfo($relativePath, PATHINFO_EXTENSION));

    if (in_array($extension, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true)) {
        return 'image';
    }

    if (in_array($extension, ['mp4', 'webm', 'ogg'], true)) {
        return 'video';
    }

    return '';
}

/**
 * @return array<int, array<string, string>>
 */
function collectServiceCollectionMedia(string $projectRoot, string $collectionDirectory): array
{
    if (!is_dir($collectionDirectory)) {
        return [];
    }

    $mediaItems = [];
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($collectionDirectory, FilesystemIterator::SKIP_DOTS)
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

        $normalizedPath = str_replace('\\', '/', $relativePath);
        $mediaType = resolveServiceCollectionMediaType($normalizedPath);

        if ($mediaType === '') {
            continue;
        }

        $mediaItems[] = [
            'path' => $normalizedPath,
            'type' => $mediaType,
        ];
    }

    usort(
        $mediaItems,
        static function (array $left, array $right): int {
            return strnatcasecmp((string) ($left['path'] ?? ''), (string) ($right['path'] ?? ''));
        }
    );

    return array_values($mediaItems);
}

/**
 * @return array<int, array<string, mixed>>
 */
function collectServicePackageCollections(string $projectRoot, string $packageFolder): array
{
    $normalizedFolder = trim((string) $packageFolder);

    if ($normalizedFolder === '') {
        return [];
    }

    $packageDirectory = $projectRoot
        . DIRECTORY_SEPARATOR . 'assets'
        . DIRECTORY_SEPARATOR . 'service_packages'
        . DIRECTORY_SEPARATOR . $normalizedFolder;

    if (!is_dir($packageDirectory)) {
        return [];
    }

    $collections = [];
    $iterator = new DirectoryIterator($packageDirectory);

    foreach ($iterator as $entry) {
        if (!$entry->isDir() || $entry->isDot()) {
            continue;
        }

        $folderName = $entry->getFilename();
        $labels = parseServiceCollectionFolderName($folderName);

        $collections[] = [
            'folder_name' => $folderName,
            'category_label' => $labels['category'],
            'collection_label' => $labels['name'],
            'media_items' => collectServiceCollectionMedia($projectRoot, $entry->getPathname()),
        ];
    }

    usort(
        $collections,
        static function (array $left, array $right): int {
            return strnatcasecmp((string) ($left['folder_name'] ?? ''), (string) ($right['folder_name'] ?? ''));
        }
    );

    return $collections;
}

function buildServiceAssetUrl(string $assetBasePath, string $relativePath): string
{
    $segments = explode('/', str_replace('\\', '/', $relativePath));
    $encodedPath = implode('/', array_map('rawurlencode', $segments));
    $normalizedBase = $assetBasePath === '' ? '' : rtrim($assetBasePath, '/') . '/';

    return $normalizedBase . $encodedPath;
}

$servicePackagesRepository = load_services_packages_repository();
$servicePackages = [];

foreach ($servicePackagesRepository as $packageKey => $packageRecord) {
    if (!is_string($packageKey) || trim($packageKey) === '' || !is_array($packageRecord)) {
        continue;
    }

    $normalizedPackageKey = normalize_service_package_key((string) $packageKey);
    if ($normalizedPackageKey === '') {
        continue;
    }

    $priceValue = parseServicePackagePrice($packageRecord['price'] ?? 0);
    $discountPercent = max(0, min(95, (int) ($packageRecord['discountPercent'] ?? 0)));

    $durationUnit = normalize_service_package_duration_unit($packageRecord['durationUnit'] ?? 'hours');
    $durationValue = (int) ($packageRecord['durationValue'] ?? 1);
    $durationValue = clamp_service_package_duration_value($durationUnit, $durationValue);
    $durationUnit = normalize_service_package_duration_unit($durationUnit);

    $servicePackages[$normalizedPackageKey] = [
        'service_type' => normalize_service_package_type($packageRecord['serviceType'] ?? default_service_package_type()),
        'title' => trim((string) ($packageRecord['title'] ?? strtoupper(str_replace('-', ' ', $normalizedPackageKey)))),
        'description' => trim((string) ($packageRecord['description'] ?? '')),
        'price_value' => $priceValue,
        'price_label' => formatServicePackagePrice($priceValue),
        'discount_percent' => $discountPercent,
        'duration_unit' => $durationUnit,
        'duration_value' => $durationValue,
        'folder' => $normalizedPackageKey,
    ];
}

$selectedPackageKey = normalize_service_package_key((string) ($_GET['package'] ?? ''));
if (!isset($servicePackages[$selectedPackageKey])) {
    $availablePackageKeys = array_keys($servicePackages);
    $selectedPackageKey = $availablePackageKeys !== [] ? (string) $availablePackageKeys[0] : '';
}

if (!$isAdminView && isset($_GET['add_package'])) {
    if (!$isCustomerLoggedIn) {
        $currentPageUrl = $_SERVER['REQUEST_URI'] ?? ($assetBase . 'customer-service/?package=' . urlencode($selectedPackageKey));
        $redirectQuery = '?redirect=' . rawurlencode($currentPageUrl);
        header('Location: ' . $loginPath . $redirectQuery);
        exit;
    }

    $currentCount = (int) ($_SESSION['customer_cart_count'] ?? 0);
    $_SESSION['customer_cart_count'] = $currentCount + 1;

    $redirectPath = strtok($_SERVER['REQUEST_URI'] ?? '', '?');
    $query = $_GET;
    unset($query['add_package']);
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
$serviceDetailPath = $isAdminView ? 'admin/service/' : 'customer-service/';
$notificationsPath = $assetBase . 'admin/notifications/';
$adminNotificationCount = 0;

if ($isAdminView) {
    require_once __DIR__ . '/config/message_notifications_repository.php';
    $adminNotificationCount = count_unread_message_notifications();
}

require_once __DIR__ . '/customer_notifications_center.php';
$customerNotificationCenter = build_customer_notification_center($assetBase, !$isAdminView && $isCustomerLoggedIn);

$selectedPackage = $selectedPackageKey !== ''
    ? $servicePackages[$selectedPackageKey]
    : [
        'service_type' => default_service_package_type(),
        'title' => 'SERVICE PACKAGE',
        'description' => '',
        'price_value' => 0,
        'price_label' => formatServicePackagePrice(0),
        'discount_percent' => 0,
        'duration_unit' => 'hours',
        'duration_value' => 1,
        'folder' => '',
    ];

$selectedPackagePriceValue = (float) ($selectedPackage['price_value'] ?? 0);
$selectedPackageDiscount = max(0, min(95, (int) ($selectedPackage['discount_percent'] ?? 0)));
$selectedPackageDiscountedValue = calculateDiscountedServicePackagePrice($selectedPackagePriceValue, $selectedPackageDiscount);
$selectedPackageFinalValue = $selectedPackageDiscount > 0 ? $selectedPackageDiscountedValue : $selectedPackagePriceValue;
$selectedPackageFinalLabel = formatServicePackagePrice($selectedPackageFinalValue);
$selectedPackageDurationUnit = normalize_service_package_duration_unit($selectedPackage['duration_unit'] ?? 'hours');
$selectedPackageDurationValue = clamp_service_package_duration_value($selectedPackageDurationUnit, (int) ($selectedPackage['duration_value'] ?? 1));
$selectedPackageDurationUnit = normalize_service_package_duration_unit($selectedPackageDurationUnit);
$selectedPackageServiceType = normalize_service_package_type((string) ($selectedPackage['service_type'] ?? default_service_package_type()));
$allowServiceCollectionVideos = strpos($selectedPackageServiceType, 'videography') !== false;

$projectRoot = __DIR__;
$selectedPackageFolder = trim((string) ($selectedPackage['folder'] ?? ''));
$packageCollections = collectServicePackageCollections($projectRoot, $selectedPackageFolder);

$categoryGroups = [];

foreach ($packageCollections as $collection) {
    $category = (string) ($collection['category_label'] ?? 'Untitled');

    if (!isset($categoryGroups[$category])) {
        $categoryGroups[$category] = [];
    }

    $categoryGroups[$category][] = $collection;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $isAdminView ? 'Admin Services | ' : 'The Nifty Fifty | '; ?><?php echo htmlspecialchars((string) ($selectedPackage['title'] ?? 'Service Package'), ENT_QUOTES, 'UTF-8'); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>css/style.css?v=20261008-2">
</head>
<body class="events-page event-detail-page service-detail-page">
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
                <a class="topbar-cart" href="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>customer-cart/" aria-label="Reservation">
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

        <nav class="section-nav section-nav-disabled section-nav-with-history" aria-label="Catalog filters">
            <span class="section-nav-filter is-disabled" aria-disabled="true">BRANDS</span>
            <a class="section-nav-section" href="<?php echo htmlspecialchars($eventsPath, ENT_QUOTES, 'UTF-8'); ?>">EVENTS</a>
            <a class="section-nav-section is-active" href="<?php echo htmlspecialchars($servicesPath, ENT_QUOTES, 'UTF-8'); ?>" aria-current="page">SERVICES</a>
            <span class="section-nav-filter is-disabled" aria-disabled="true">DATE</span>
            <?php if ($isCustomerLoggedIn): ?>
                <a class="section-nav-history-link" href="<?php echo htmlspecialchars($cartPath . '?view=history', ENT_QUOTES, 'UTF-8'); ?>">Reservation History</a>
            <?php endif; ?>
        </nav>
    </header>

    <main class="events-shell event-detail-shell">
        <section
            class="catalog-section reveal"
            <?php echo $isAdminView ? ' data-admin-event-collections-shell data-admin-event-collection-count-singular="collection" data-admin-event-collection-count-plural="collections"' : ''; ?>
        >
            <div class="catalog-header">
                <a class="catalog-back" href="<?php echo htmlspecialchars($servicesPath, ENT_QUOTES, 'UTF-8'); ?>" aria-label="Back to services packages">
                    <span class="catalog-back-icon" aria-hidden="true"></span>
                </a>
                <div class="event-detail-header-copy">
                    <h1><?php echo htmlspecialchars((string) ($selectedPackage['title'] ?? 'SERVICE PACKAGE'), ENT_QUOTES, 'UTF-8'); ?></h1>
                    <p>Service collections below are categorized sample galleries for this package.</p>
                </div>
                <?php if ($isAdminView): ?>
                    <button class="event-detail-admin-add-collection" type="button" data-admin-event-collection-add-open>Add Collection</button>
                <?php endif; ?>
            </div>

            <article class="event-package-cta">
                <div class="event-package-cta-copy">
                    <p class="event-package-cta-label">Selected Service</p>
                    <h2><?php echo htmlspecialchars((string) ($selectedPackage['title'] ?? 'SERVICE PACKAGE'), ENT_QUOTES, 'UTF-8'); ?></h2>
                </div>

                <div class="event-package-cta-actions">
                    <span class="package-price-stack">
                        <?php if ($selectedPackageDiscount > 0): ?>
                            <span class="package-price-original"><?php echo htmlspecialchars(formatServicePackagePrice($selectedPackagePriceValue), ENT_QUOTES, 'UTF-8'); ?></span>
                            <span class="package-price-discounted"><?php echo htmlspecialchars(formatServicePackagePrice($selectedPackageDiscountedValue), ENT_QUOTES, 'UTF-8'); ?></span>
                        <?php else: ?>
                            <span class="package-price-discounted"><?php echo htmlspecialchars(formatServicePackagePrice($selectedPackagePriceValue), ENT_QUOTES, 'UTF-8'); ?></span>
                        <?php endif; ?>
                    </span>
                    <?php if ($isAdminView): ?>
                        <button
                            type="button"
                            onclick="window.location.href='<?php echo htmlspecialchars($servicesPath, ENT_QUOTES, 'UTF-8'); ?>'"
                        >
                            BACK TO SERVICES
                        </button>
                    <?php else: ?>
                        <?php
                        $packagePreview = $assetBase . 'assets/images/main_logo.png';

                        foreach ($packageCollections as $previewCollection) {
                            $previewMediaItems = (array) ($previewCollection['media_items'] ?? []);

                            foreach ($previewMediaItems as $previewMediaItem) {
                                if (!is_array($previewMediaItem)) {
                                    continue;
                                }

                                $previewMediaType = (string) ($previewMediaItem['type'] ?? '');
                                $previewMediaPath = (string) ($previewMediaItem['path'] ?? '');

                                if ($previewMediaType !== 'image' || $previewMediaPath === '') {
                                    continue;
                                }

                                $packagePreview = buildServiceAssetUrl($assetBase, $previewMediaPath);
                                break 2;
                            }
                        }

                        $serviceDetailLoginUrl = $loginPath . '?redirect=' . rawurlencode($_SERVER['REQUEST_URI'] ?? ($assetBase . $serviceDetailPath . '?package=' . urlencode($selectedPackageKey)));
                        ?>
                        <button
                            type="button"
                            data-service-purchase
                            data-item-id="service-<?php echo htmlspecialchars($selectedPackageKey, ENT_QUOTES, 'UTF-8'); ?>"
                            data-item-type="service-package"
                            data-service-package-key="<?php echo htmlspecialchars($selectedPackageKey, ENT_QUOTES, 'UTF-8'); ?>"
                            data-item-name="<?php echo htmlspecialchars((string) ($selectedPackage['title'] ?? 'SERVICE PACKAGE'), ENT_QUOTES, 'UTF-8'); ?>"
                            data-item-copy="Service package reservation for <?php echo htmlspecialchars((string) ($selectedPackage['title'] ?? 'SERVICE PACKAGE'), ENT_QUOTES, 'UTF-8'); ?>."
                            data-item-image="<?php echo htmlspecialchars($packagePreview, ENT_QUOTES, 'UTF-8'); ?>"
                            data-item-price="<?php echo htmlspecialchars($selectedPackageFinalLabel, ENT_QUOTES, 'UTF-8'); ?>"
                            data-duration-unit="<?php echo htmlspecialchars((string) $selectedPackageDurationUnit, ENT_QUOTES, 'UTF-8'); ?>"
                            data-duration-value="<?php echo htmlspecialchars((string) $selectedPackageDurationValue, ENT_QUOTES, 'UTF-8'); ?>"
                            data-service-cart-url="<?php echo htmlspecialchars($assetBase . 'customer-cart/?view=services-cart', ENT_QUOTES, 'UTF-8'); ?>"
                            <?php if (!$isCustomerLoggedIn): ?>
                                data-login-url="<?php echo htmlspecialchars($serviceDetailLoginUrl, ENT_QUOTES, 'UTF-8'); ?>"
                            <?php endif; ?>
                        >
                            ADD TO RESERVATION
                        </button>
                    <?php endif; ?>
                </div>
            </article>

            <article class="event-gallery-empty-state" data-admin-event-collections-empty<?php echo $categoryGroups === [] ? '' : ' hidden'; ?>>
                <h2>No service collections found yet.</h2>
                <p>This package has no uploaded collections at the moment.</p>
            </article>

            <?php foreach ($categoryGroups as $categoryLabel => $collections): ?>
                <?php
                $categorySlug = strtolower(str_replace(' ', '-', (string) $categoryLabel));
                $categoryCount = count($collections);
                ?>
                <section class="event-category-section" aria-labelledby="service-category-<?php echo htmlspecialchars($categorySlug, ENT_QUOTES, 'UTF-8'); ?>">
                    <header class="event-category-head">
                        <h2 id="service-category-<?php echo htmlspecialchars($categorySlug, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars((string) $categoryLabel, ENT_QUOTES, 'UTF-8'); ?></h2>
                        <span data-admin-event-category-count><?php echo $categoryCount; ?> collection<?php echo $categoryCount === 1 ? '' : 's'; ?></span>
                    </header>

                    <div class="event-card-grid">
                        <?php foreach ($collections as $collection): ?>
                            <article
                                class="event-gallery-card<?php echo $isAdminView ? ' event-gallery-card-admin' : ''; ?>"
                                <?php if ($isAdminView): ?>
                                    data-admin-event-collection-card
                                    data-admin-event-package-key="<?php echo htmlspecialchars((string) $selectedPackageKey, ENT_QUOTES, 'UTF-8'); ?>"
                                    data-admin-event-package-title="<?php echo htmlspecialchars((string) ($selectedPackage['title'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                                    data-admin-event-package-folder="<?php echo htmlspecialchars((string) $selectedPackageFolder, ENT_QUOTES, 'UTF-8'); ?>"
                                    data-admin-event-collection-folder="<?php echo htmlspecialchars((string) ($collection['folder_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                                    data-admin-event-collection-category="<?php echo htmlspecialchars((string) $categoryLabel, ENT_QUOTES, 'UTF-8'); ?>"
                                    data-admin-event-collection-name="<?php echo htmlspecialchars((string) ($collection['collection_label'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                                    data-admin-event-collection-archived-images="[]"
                                <?php endif; ?>
                            >
                                <?php if ($isAdminView): ?>
                                    <div class="event-gallery-admin-actions">
                                        <button class="product-card-admin-edit" type="button" data-admin-event-collection-edit aria-label="Edit collection <?php echo htmlspecialchars((string) ($collection['collection_label'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                                            <img src="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>assets/icons/pencil.svg" alt="">
                                        </button>
                                    </div>
                                <?php endif; ?>

                                <header class="event-gallery-meta">
                                    <h3><?php echo htmlspecialchars((string) ($collection['collection_label'] ?? 'Collection'), ENT_QUOTES, 'UTF-8'); ?></h3>
                                </header>

                                <?php if ((array) ($collection['media_items'] ?? []) !== []): ?>
                                    <div class="event-photo-masonry" aria-label="<?php echo htmlspecialchars((string) ($collection['collection_label'] ?? 'Collection'), ENT_QUOTES, 'UTF-8'); ?> gallery">
                                        <?php foreach ((array) ($collection['media_items'] ?? []) as $mediaIndex => $mediaItem): ?>
                                            <?php
                                            $mediaPath = is_array($mediaItem) ? (string) ($mediaItem['path'] ?? '') : '';
                                            $mediaType = is_array($mediaItem) ? (string) ($mediaItem['type'] ?? 'image') : 'image';
                                            if ($mediaPath === '') {
                                                continue;
                                            }
                                            ?>
                                            <figure class="event-photo-item">
                                                <?php if ($mediaType === 'video'): ?>
                                                    <video
                                                        src="<?php echo htmlspecialchars(buildServiceAssetUrl($assetBase, $mediaPath), ENT_QUOTES, 'UTF-8'); ?>"
                                                        data-admin-event-image-path="<?php echo htmlspecialchars($mediaPath, ENT_QUOTES, 'UTF-8'); ?>"
                                                        data-admin-event-media-type="video"
                                                        controls
                                                        muted
                                                        playsinline
                                                        preload="metadata"
                                                    ></video>
                                                <?php else: ?>
                                                    <img
                                                        src="<?php echo htmlspecialchars(buildServiceAssetUrl($assetBase, $mediaPath), ENT_QUOTES, 'UTF-8'); ?>"
                                                        data-admin-event-image-path="<?php echo htmlspecialchars($mediaPath, ENT_QUOTES, 'UTF-8'); ?>"
                                                        data-admin-event-media-type="image"
                                                        alt="<?php echo htmlspecialchars((string) ($collection['collection_label'] ?? 'Collection') . ' media ' . ((int) $mediaIndex + 1), ENT_QUOTES, 'UTF-8'); ?>"
                                                        loading="lazy"
                                                    >
                                                <?php endif; ?>
                                            </figure>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <div class="event-card-empty">No media were found for this collection yet.</div>
                                <?php endif; ?>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endforeach; ?>
        </section>
    </main>

    <?php if ($isAdminView): ?>
        <div
            data-admin-event-collection-config
            data-admin-event-collection-create-endpoint="<?php echo htmlspecialchars($assetBase . 'admin/dashboard/create_service_collection.php', ENT_QUOTES, 'UTF-8'); ?>"
            data-admin-event-collection-update-endpoint="<?php echo htmlspecialchars($assetBase . 'admin/dashboard/update_service_collection_images.php', ENT_QUOTES, 'UTF-8'); ?>"
            data-admin-event-collection-asset-base="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>"
            data-admin-event-collection-package-key="<?php echo htmlspecialchars((string) $selectedPackageKey, ENT_QUOTES, 'UTF-8'); ?>"
            data-admin-event-collection-package-folder="<?php echo htmlspecialchars((string) $selectedPackageFolder, ENT_QUOTES, 'UTF-8'); ?>"
            data-admin-event-collection-allow-video="<?php echo $allowServiceCollectionVideos ? '1' : '0'; ?>"
            hidden
        ></div>

        <div class="admin-edit-modal-backdrop admin-event-collection-create-backdrop" data-admin-event-collection-create-backdrop hidden>
            <section class="admin-edit-modal admin-event-collection-create-modal" role="dialog" aria-modal="true" aria-labelledby="admin-event-collection-create-title">
                <div class="admin-edit-modal-head">
                    <h2 id="admin-event-collection-create-title">Add Collection</h2>
                    <button class="admin-edit-close" type="button" data-admin-event-collection-create-close aria-label="Close add collection modal">&times;</button>
                </div>

                <form class="admin-edit-form admin-event-collection-create-form" data-admin-event-collection-create-form>
                    <p class="admin-event-collection-create-note">
                        Add a <strong>Main Tag</strong> and <strong>Collection Name</strong>. You can upload <?php echo $allowServiceCollectionVideos ? 'images and videos' : 'images'; ?> afterwards via Edit Collection.
                    </p>

                    <div class="admin-event-collection-create-fields">
                        <label class="admin-edit-label" for="admin-event-collection-create-category">Main Tag</label>
                        <input id="admin-event-collection-create-category" type="text" data-admin-event-collection-create-category maxlength="120" required>

                        <label class="admin-edit-label" for="admin-event-collection-create-name">Collection Name</label>
                        <input id="admin-event-collection-create-name" type="text" data-admin-event-collection-create-name maxlength="120" required>
                    </div>

                    <p class="admin-event-collection-create-feedback" data-admin-event-collection-create-feedback hidden></p>

                    <div class="admin-edit-actions">
                        <button class="admin-edit-secondary" type="button" data-admin-event-collection-create-cancel>Cancel</button>
                        <button class="admin-edit-primary" type="submit" data-admin-event-collection-create-save>Create Collection</button>
                    </div>
                </form>
            </section>
        </div>

        <div class="admin-edit-modal-backdrop admin-event-collection-edit-backdrop" data-admin-event-collection-edit-backdrop hidden>
            <section class="admin-edit-modal admin-event-collection-edit-modal" role="dialog" aria-modal="true" aria-labelledby="admin-event-collection-edit-title">
                <div class="admin-edit-modal-head">
                    <h2 id="admin-event-collection-edit-title" data-admin-event-collection-edit-title>Edit Collection</h2>
                    <button class="admin-edit-close" type="button" data-admin-event-collection-edit-close aria-label="Close collection editor">&times;</button>
                </div>

                <form class="admin-edit-form admin-event-collection-edit-form" data-admin-event-collection-edit-form>
                    <input type="hidden" data-admin-event-collection-edit-package-key>
                    <input type="hidden" data-admin-event-collection-edit-package-folder>
                    <input type="hidden" data-admin-event-collection-edit-collection-folder>

                    <div class="admin-event-collection-edit-fields">
                        <label class="admin-edit-label" for="admin-event-collection-edit-category">Main Tag</label>
                        <input id="admin-event-collection-edit-category" type="text" data-admin-event-collection-edit-category maxlength="120" required>

                        <label class="admin-edit-label" for="admin-event-collection-edit-name">Collection Name</label>
                        <input id="admin-event-collection-edit-name" type="text" data-admin-event-collection-edit-name maxlength="120" required>
                    </div>

                    <p class="admin-event-collection-edit-note">
                        Use the red <strong>&times;</strong> button to mark a media file for removal. Marked items dim out. Click the green <strong>&#10003;</strong> button to undo before saving. Editing Main Tag or Collection Name will rename this collection.
                    </p>

                    <div class="admin-event-collection-edit-toolbar">
                        <button class="admin-edit-secondary admin-event-collection-add-trigger" type="button" data-admin-event-collection-add-trigger><?php echo $allowServiceCollectionVideos ? 'Add Media' : 'Add Image'; ?></button>
                        <input type="file" accept="<?php echo htmlspecialchars($allowServiceCollectionVideos ? 'image/png,image/jpeg,image/webp,image/gif,video/mp4,video/webm,video/ogg' : 'image/png,image/jpeg,image/webp,image/gif', ENT_QUOTES, 'UTF-8'); ?>" multiple data-admin-event-collection-add-input hidden>
                    </div>

                    <p class="admin-event-collection-edit-feedback" data-admin-event-collection-edit-feedback hidden></p>

                    <div class="admin-event-collection-edit-grid" data-admin-event-collection-edit-grid></div>
                    <p class="admin-event-collection-edit-empty" data-admin-event-collection-edit-empty hidden>No media in this collection yet. Add one to get started.</p>

                    <div class="admin-edit-actions">
                        <button class="admin-edit-secondary" type="button" data-admin-event-collection-edit-cancel>Cancel</button>
                        <button class="admin-edit-primary" type="submit" data-admin-event-collection-edit-save>Save Changes</button>
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
