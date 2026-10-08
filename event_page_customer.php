<?php
if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {
    header('Location: customer-events/');
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
require __DIR__ . '/config/event_collections_archive_repository.php';
require __DIR__ . '/config/event_collection_images_archive_repository.php';

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

$eventPackagesRepository = load_event_packages_repository();
$eventPackages = [];

foreach ($eventPackagesRepository as $packageKey => $packageRecord) {
    if (!is_array($packageRecord)) {
        continue;
    }

    if (!empty($packageRecord['archived'])) {
        continue;
    }

    $priceValue = parseEventPackagePrice($packageRecord['price'] ?? 0);
    $discountPercent = max(0, min(95, (int) ($packageRecord['discountPercent'] ?? 0)));

    $eventPackages[(string) $packageKey] = [
        'title' => trim((string) ($packageRecord['title'] ?? strtoupper(str_replace('-', ' ', (string) $packageKey)))),
        'price_value' => $priceValue,
        'price_label' => formatEventPackagePrice($priceValue),
        'discount_percent' => $discountPercent,
        'folder' => trim((string) ($packageRecord['folder'] ?? '')),
    ];
}

$selectedPackageKey = strtolower(trim((string) ($_GET['package'] ?? 'wedding')));
if (!isset($eventPackages[$selectedPackageKey])) {
    $availablePackageKeys = array_keys($eventPackages);
    $selectedPackageKey = $availablePackageKeys !== [] ? (string) $availablePackageKeys[0] : '';
}

if (!$isAdminView && isset($_GET['add_package'])) {
    if (!$isCustomerLoggedIn) {
        $currentPageUrl = $_SERVER['REQUEST_URI'] ?? ($assetBase . 'customer-event/?package=' . urlencode($selectedPackageKey));
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
$notificationsPath = $assetBase . 'admin/notifications/';
$adminNotificationCount = 0;

if ($isAdminView) {
    require_once __DIR__ . '/config/message_notifications_repository.php';
    $adminNotificationCount = count_unread_message_notifications();
}

require_once __DIR__ . '/customer_notifications_center.php';
$customerNotificationCenter = build_customer_notification_center($assetBase, !$isAdminView && $isCustomerLoggedIn);
$eventDetailPath = $isAdminView ? 'admin/event/' : 'customer-event/';
$selectedPackage = $selectedPackageKey !== ''
    ? $eventPackages[$selectedPackageKey]
    : [
        'title' => 'EVENT PACKAGE',
        'price_value' => 0,
        'price_label' => formatEventPackagePrice(0),
        'discount_percent' => 0,
        'folder' => ''
    ];
$selectedPackagePriceValue = (float) ($selectedPackage['price_value'] ?? 0);
$selectedPackageDiscount = max(0, min(95, (int) ($selectedPackage['discount_percent'] ?? 0)));
$selectedPackageDiscountedValue = calculateDiscountedEventPackagePrice($selectedPackagePriceValue, $selectedPackageDiscount);
$selectedPackageFinalValue = $selectedPackageDiscount > 0 ? $selectedPackageDiscountedValue : $selectedPackagePriceValue;
$selectedPackageFinalLabel = formatEventPackagePrice($selectedPackageFinalValue);

function formatEventLabel(string $raw): string
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

function parseEventFolderName(string $folderName): array
{
    $parts = explode('_', $folderName, 2);
    $categoryRaw = $parts[0] ?? $folderName;
    $nameRaw = $parts[1] ?? $categoryRaw;

    return [
        'category' => formatEventLabel($categoryRaw),
        'name' => formatEventLabel($nameRaw),
    ];
}

/**
 * @return string[]
 */
function collectEventPhotos(string $projectRoot, string $eventDirectory): array
{
    if (!is_dir($eventDirectory)) {
        return [];
    }

    $images = [];
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($eventDirectory, FilesystemIterator::SKIP_DOTS)
    );

    foreach ($iterator as $fileInfo) {
        if (!$fileInfo->isFile()) {
            continue;
        }

        $extension = strtolower((string) pathinfo($fileInfo->getFilename(), PATHINFO_EXTENSION));
        if (!in_array($extension, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true)) {
            continue;
        }

        $absolutePath = $fileInfo->getPathname();
        $relativePath = substr($absolutePath, strlen($projectRoot) + 1);
        if ($relativePath === false || $relativePath === '') {
            continue;
        }

        $images[] = str_replace('\\', '/', $relativePath);
    }

    natcasesort($images);

    return array_values($images);
}

/**
 * @return array<int, array<string, mixed>>
 */
function collectPackageEvents(string $projectRoot, string $packageFolder): array
{
    $packageDirectory = $projectRoot . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'event_packages' . DIRECTORY_SEPARATOR . $packageFolder;
    if (!is_dir($packageDirectory)) {
        return [];
    }

    $events = [];
    $iterator = new DirectoryIterator($packageDirectory);

    foreach ($iterator as $entry) {
        if (!$entry->isDir() || $entry->isDot()) {
            continue;
        }

        $folderName = $entry->getFilename();
        $labels = parseEventFolderName($folderName);

        $events[] = [
            'folder_name' => $folderName,
            'category_label' => $labels['category'],
            'event_label' => $labels['name'],
            'images' => collectEventPhotos($projectRoot, $entry->getPathname()),
        ];
    }

    usort(
        $events,
        static function (array $left, array $right): int {
            return strnatcasecmp($left['folder_name'], $right['folder_name']);
        }
    );

    return $events;
}

function buildAssetUrl(string $assetBasePath, string $relativePath): string
{
    $segments = explode('/', str_replace('\\', '/', $relativePath));
    $encodedPath = implode('/', array_map('rawurlencode', $segments));
    $normalizedBase = $assetBasePath === '' ? '' : rtrim($assetBasePath, '/') . '/';

    return $normalizedBase . $encodedPath;
}

function isEventCollectionArchivedForPackage(array $archivedCollections, string $packageKey, string $packageFolder, string $collectionFolder): bool
{
    $normalizedCollectionFolder = normalize_event_collection_folder_name($collectionFolder);
    if ($normalizedCollectionFolder === '') {
        return false;
    }

    $normalizedPackageKey = normalize_event_package_key($packageKey);
    $normalizedPackageFolder = trim($packageFolder);

    foreach ($archivedCollections as $archivedEntry) {
        if (!is_array($archivedEntry)) {
            continue;
        }

        $entryCollectionFolder = normalize_event_collection_folder_name($archivedEntry['collectionFolder'] ?? '');
        if ($entryCollectionFolder === '' || strcasecmp($entryCollectionFolder, $normalizedCollectionFolder) !== 0) {
            continue;
        }

        $entryPackageFolder = trim((string) ($archivedEntry['packageFolder'] ?? ''));
        $entryPackageKey = normalize_event_package_key((string) ($archivedEntry['packageKey'] ?? ''));

        if ($normalizedPackageFolder !== '' && $entryPackageFolder !== '' && strcasecmp($entryPackageFolder, $normalizedPackageFolder) === 0) {
            return true;
        }

        if ($normalizedPackageKey !== '' && $entryPackageKey !== '' && $entryPackageKey === $normalizedPackageKey) {
            return true;
        }
    }

    return false;
}

function buildArchivedEventCollectionImageLookups(array $archivedImages, string $packageKey, string $packageFolder): array
{
    $setLookup = [];
    $listLookup = [];
    $normalizedPackageKey = normalize_event_package_key($packageKey);
    $normalizedPackageFolder = trim($packageFolder);

    foreach ($archivedImages as $archivedEntry) {
        if (!is_array($archivedEntry)) {
            continue;
        }

        $entryCollectionFolder = normalize_event_collection_folder_name($archivedEntry['collectionFolder'] ?? '');
        $entryImagePath = normalize_event_collection_image_relative_path($archivedEntry['imagePath'] ?? '');

        if ($entryCollectionFolder === '' || $entryImagePath === '') {
            continue;
        }

        $entryPackageFolder = trim((string) ($archivedEntry['packageFolder'] ?? ''));
        $entryPackageKey = normalize_event_package_key((string) ($archivedEntry['packageKey'] ?? ''));
        $isSamePackage = false;

        if ($normalizedPackageFolder !== '' && $entryPackageFolder !== '' && strcasecmp($entryPackageFolder, $normalizedPackageFolder) === 0) {
            $isSamePackage = true;
        } elseif ($normalizedPackageKey !== '' && $entryPackageKey !== '' && $entryPackageKey === $normalizedPackageKey) {
            $isSamePackage = true;
        }

        if (!$isSamePackage) {
            continue;
        }

        $collectionKey = strtolower($entryCollectionFolder);
        $pathKey = strtolower($entryImagePath);

        if (!isset($setLookup[$collectionKey])) {
            $setLookup[$collectionKey] = [];
        }

        if (!isset($listLookup[$collectionKey])) {
            $listLookup[$collectionKey] = [];
        }

        if (isset($setLookup[$collectionKey][$pathKey])) {
            continue;
        }

        $setLookup[$collectionKey][$pathKey] = true;
        $listLookup[$collectionKey][] = $entryImagePath;
    }

    return [
        'set' => $setLookup,
        'list' => $listLookup,
    ];
}

$projectRoot = __DIR__;
$selectedPackageFolder = trim((string) ($selectedPackage['folder'] ?? ''));
$archivedEventCollections = load_archived_event_collections_repository();
$archivedEventCollectionImages = load_archived_event_collection_images_repository();
$packageEvents = array_values(array_filter(
    collectPackageEvents($projectRoot, $selectedPackageFolder),
    static function (array $event) use ($archivedEventCollections, $selectedPackageKey, $selectedPackageFolder): bool {
        $folderName = (string) ($event['folder_name'] ?? '');

        return !isEventCollectionArchivedForPackage(
            $archivedEventCollections,
            (string) $selectedPackageKey,
            $selectedPackageFolder,
            $folderName
        );
    }
));

$archivedCollectionImageLookups = buildArchivedEventCollectionImageLookups(
    $archivedEventCollectionImages,
    (string) $selectedPackageKey,
    $selectedPackageFolder
);

$archivedCollectionImageLookup = is_array($archivedCollectionImageLookups['set'] ?? null)
    ? $archivedCollectionImageLookups['set']
    : [];

$archivedCollectionImageListLookup = is_array($archivedCollectionImageLookups['list'] ?? null)
    ? $archivedCollectionImageLookups['list']
    : [];

$packageEvents = array_map(
    static function (array $event) use ($archivedCollectionImageLookup, $archivedCollectionImageListLookup): array {
        $collectionKey = strtolower(normalize_event_collection_folder_name($event['folder_name'] ?? ''));
        $excludedPathLookup = $collectionKey !== '' && isset($archivedCollectionImageLookup[$collectionKey])
            ? $archivedCollectionImageLookup[$collectionKey]
            : [];

        $event['archived_images'] = $collectionKey !== '' && isset($archivedCollectionImageListLookup[$collectionKey])
            ? array_values((array) $archivedCollectionImageListLookup[$collectionKey])
            : [];

        $event['images'] = array_values(array_filter(
            (array) ($event['images'] ?? []),
            static function ($imagePath) use ($excludedPathLookup): bool {
                $normalizedPath = strtolower(normalize_event_collection_image_relative_path((string) $imagePath));

                if ($normalizedPath === '') {
                    return true;
                }

                return !isset($excludedPathLookup[$normalizedPath]);
            }
        ));

        return $event;
    },
    $packageEvents
);

$categoryGroups = [];

foreach ($packageEvents as $event) {
    $category = $event['category_label'];
    if (!isset($categoryGroups[$category])) {
        $categoryGroups[$category] = [];
    }

    $categoryGroups[$category][] = $event;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $isAdminView ? 'Admin Events | ' : 'The Nifty Fifty | '; ?><?php echo htmlspecialchars($selectedPackage['title'], ENT_QUOTES, 'UTF-8'); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>css/style.css?v=20261008-2">
</head>
<body class="events-page event-detail-page">
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

    <main class="events-shell event-detail-shell">
        <section class="catalog-section reveal"<?php echo $isAdminView ? ' data-admin-event-collections-shell' : ''; ?>>
            <div class="catalog-header">
                <a class="catalog-back" href="<?php echo htmlspecialchars($eventsPath, ENT_QUOTES, 'UTF-8'); ?>" aria-label="Back to event packages">
                    <span class="catalog-back-icon" aria-hidden="true"></span>
                </a>
                <div class="event-detail-header-copy">
                    <h1><?php echo htmlspecialchars($selectedPackage['title'], ENT_QUOTES, 'UTF-8'); ?></h1>
                    <p>Event folders below are categorized sample galleries for this package.</p>
                </div>
                <?php if ($isAdminView): ?>
                    <button class="event-detail-admin-add-collection" type="button" data-admin-event-collection-add-open>Add Collection</button>
                <?php endif; ?>
            </div>

            <article class="event-package-cta">
                <div class="event-package-cta-copy">
                    <p class="event-package-cta-label">Selected Package</p>
                    <h2><?php echo htmlspecialchars($selectedPackage['title'], ENT_QUOTES, 'UTF-8'); ?></h2>
                </div>

                <div class="event-package-cta-actions">
                    <span class="package-price-stack">
                        <?php if ($selectedPackageDiscount > 0): ?>
                            <span class="package-price-original"><?php echo htmlspecialchars(formatEventPackagePrice($selectedPackagePriceValue), ENT_QUOTES, 'UTF-8'); ?></span>
                            <span class="package-price-discounted"><?php echo htmlspecialchars(formatEventPackagePrice($selectedPackageDiscountedValue), ENT_QUOTES, 'UTF-8'); ?></span>
                        <?php else: ?>
                            <span class="package-price-discounted"><?php echo htmlspecialchars(formatEventPackagePrice($selectedPackagePriceValue), ENT_QUOTES, 'UTF-8'); ?></span>
                        <?php endif; ?>
                    </span>
                    <?php if ($isAdminView): ?>
                        <button
                            type="button"
                            onclick="window.location.href='<?php echo htmlspecialchars($eventsPath, ENT_QUOTES, 'UTF-8'); ?>'"
                        >
                            BACK TO EVENTS
                        </button>
                    <?php else: ?>
                        <?php
                        $packagePreview = $assetBase . 'assets/images/main_logo.png';
                        if ($packageEvents !== [] && !empty($packageEvents[0]['images'])) {
                            $packagePreview = buildAssetUrl($assetBase, $packageEvents[0]['images'][0]);
                        }
                        $eventDetailLoginUrl = $loginPath . '?redirect=' . rawurlencode($_SERVER['REQUEST_URI'] ?? ($assetBase . $eventDetailPath . '?package=' . urlencode($selectedPackageKey)));
                        ?>
                        <button
                            type="button"
                            data-add-cart
                            data-item-id="event-<?php echo htmlspecialchars($selectedPackageKey, ENT_QUOTES, 'UTF-8'); ?>"
                            data-item-type="event-package"
                            data-item-name="<?php echo htmlspecialchars($selectedPackage['title'], ENT_QUOTES, 'UTF-8'); ?>"
                            data-item-copy="Package booking for <?php echo htmlspecialchars($selectedPackage['title'], ENT_QUOTES, 'UTF-8'); ?> sample galleries."
                            data-item-image="<?php echo htmlspecialchars($packagePreview, ENT_QUOTES, 'UTF-8'); ?>"
                            data-item-price="<?php echo htmlspecialchars($selectedPackageFinalLabel, ENT_QUOTES, 'UTF-8'); ?>"
                            <?php if (!$isCustomerLoggedIn): ?>
                                data-login-url="<?php echo htmlspecialchars($eventDetailLoginUrl, ENT_QUOTES, 'UTF-8'); ?>"
                            <?php endif; ?>
                        >
                            ADD TO CART
                        </button>
                    <?php endif; ?>
                </div>
            </article>

            <article class="event-gallery-empty-state" data-admin-event-collections-empty<?php echo $categoryGroups === [] ? '' : ' hidden'; ?>>
                <h2>No event photos found yet.</h2>
                <p>This package has no uploaded event galleries at the moment.</p>
            </article>

            <?php foreach ($categoryGroups as $categoryLabel => $events): ?>
                <?php
                $categorySlug = strtolower(str_replace(' ', '-', (string) $categoryLabel));
                $categoryCount = count($events);
                ?>
                <section class="event-category-section" aria-labelledby="category-<?php echo htmlspecialchars($categorySlug, ENT_QUOTES, 'UTF-8'); ?>">
                    <header class="event-category-head">
                        <h2 id="category-<?php echo htmlspecialchars($categorySlug, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($categoryLabel, ENT_QUOTES, 'UTF-8'); ?></h2>
                        <span data-admin-event-category-count><?php echo $categoryCount; ?> event<?php echo $categoryCount === 1 ? '' : 's'; ?></span>
                    </header>

                    <div class="event-card-grid">
                        <?php foreach ($events as $event): ?>
                            <article
                                class="event-gallery-card<?php echo $isAdminView ? ' event-gallery-card-admin' : ''; ?>"
                                <?php if ($isAdminView): ?>
                                    data-admin-event-collection-card
                                    data-admin-event-package-key="<?php echo htmlspecialchars((string) $selectedPackageKey, ENT_QUOTES, 'UTF-8'); ?>"
                                    data-admin-event-package-title="<?php echo htmlspecialchars((string) ($selectedPackage['title'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                                    data-admin-event-package-folder="<?php echo htmlspecialchars($selectedPackageFolder, ENT_QUOTES, 'UTF-8'); ?>"
                                    data-admin-event-collection-folder="<?php echo htmlspecialchars((string) ($event['folder_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                                    data-admin-event-collection-category="<?php echo htmlspecialchars((string) $categoryLabel, ENT_QUOTES, 'UTF-8'); ?>"
                                    data-admin-event-collection-name="<?php echo htmlspecialchars((string) ($event['event_label'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                                    data-admin-event-collection-archived-images="<?php echo htmlspecialchars((string) json_encode(array_values((array) ($event['archived_images'] ?? [])), JSON_UNESCAPED_SLASHES), ENT_QUOTES, 'UTF-8'); ?>"
                                <?php endif; ?>
                            >
                                <?php if ($isAdminView): ?>
                                    <div class="event-gallery-admin-actions">
                                        <button class="product-card-admin-edit" type="button" data-admin-event-collection-edit aria-label="Edit collection <?php echo htmlspecialchars((string) ($event['event_label'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                                            <img src="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>assets/icons/pencil.svg" alt="">
                                        </button>
                                        <button class="product-card-admin-remove" type="button" data-admin-remove-event-collection aria-label="Archive collection <?php echo htmlspecialchars((string) ($event['event_label'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">&times;</button>
                                    </div>
                                <?php endif; ?>

                                <header class="event-gallery-meta">
                                    <h3><?php echo htmlspecialchars($event['event_label'], ENT_QUOTES, 'UTF-8'); ?></h3>
                                </header>

                                <?php if ($event['images'] !== []): ?>
                                    <div class="event-photo-masonry" aria-label="<?php echo htmlspecialchars($event['event_label'], ENT_QUOTES, 'UTF-8'); ?> gallery">
                                        <?php foreach ($event['images'] as $imageIndex => $imagePath): ?>
                                            <figure class="event-photo-item">
                                                <img
                                                    src="<?php echo htmlspecialchars(buildAssetUrl($assetBase, $imagePath), ENT_QUOTES, 'UTF-8'); ?>"
                                                    data-admin-event-image-path="<?php echo htmlspecialchars((string) $imagePath, ENT_QUOTES, 'UTF-8'); ?>"
                                                    alt="<?php echo htmlspecialchars($event['event_label'] . ' photo ' . ($imageIndex + 1), ENT_QUOTES, 'UTF-8'); ?>"
                                                    loading="lazy"
                                                >
                                            </figure>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <div class="event-card-empty">No photos were found for this event yet.</div>
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
            data-admin-event-collection-create-endpoint="<?php echo htmlspecialchars($assetBase . 'admin/dashboard/create_event_collection.php', ENT_QUOTES, 'UTF-8'); ?>"
            data-admin-event-collection-archive-endpoint="<?php echo htmlspecialchars($assetBase . 'admin/dashboard/archive_event_collection.php', ENT_QUOTES, 'UTF-8'); ?>"
            data-admin-event-collection-restore-endpoint="<?php echo htmlspecialchars($assetBase . 'admin/dashboard/restore_archived_event_collection.php', ENT_QUOTES, 'UTF-8'); ?>"
            data-admin-event-collection-update-endpoint="<?php echo htmlspecialchars($assetBase . 'admin/dashboard/update_event_collection_images.php', ENT_QUOTES, 'UTF-8'); ?>"
            data-admin-event-collection-asset-base="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>"
            data-admin-event-collection-package-key="<?php echo htmlspecialchars((string) $selectedPackageKey, ENT_QUOTES, 'UTF-8'); ?>"
            data-admin-event-collection-package-folder="<?php echo htmlspecialchars((string) $selectedPackageFolder, ENT_QUOTES, 'UTF-8'); ?>"
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
                        Add a <strong>Main Tag</strong> and <strong>Collection Name</strong>. You can upload images afterwards via Edit Collection.
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
                        Use the red <strong>&times;</strong> button to exclude an image. Excluded images dim out. Click the green <strong>&#10003;</strong> button to undo the exclusion before saving. Editing Main Tag or Collection Name will rename this collection.
                    </p>

                    <div class="admin-event-collection-edit-toolbar">
                        <button class="admin-edit-secondary admin-event-collection-add-trigger" type="button" data-admin-event-collection-add-trigger>Add Image</button>
                        <input type="file" accept="image/png,image/jpeg,image/webp,image/gif" multiple data-admin-event-collection-add-input hidden>
                    </div>

                    <p class="admin-event-collection-edit-feedback" data-admin-event-collection-edit-feedback hidden></p>

                    <div class="admin-event-collection-edit-grid" data-admin-event-collection-edit-grid></div>
                    <p class="admin-event-collection-edit-empty" data-admin-event-collection-edit-empty hidden>No images in this collection yet. Add one to get started.</p>

                    <div class="admin-edit-actions">
                        <button class="admin-edit-secondary" type="button" data-admin-event-collection-edit-cancel>Cancel</button>
                        <button class="admin-edit-primary" type="submit" data-admin-event-collection-edit-save>Save Changes</button>
                    </div>
                </form>
            </section>
        </div>

        <aside class="admin-undo-toast" data-admin-undo-toast hidden aria-live="polite" aria-atomic="true">
            <p class="admin-undo-toast-message" data-admin-undo-message>Collection archived.</p>
            <button class="admin-undo-toast-button" type="button" data-admin-undo-action>Undo</button>
        </aside>

        <div class="admin-action-modal-backdrop" data-admin-action-modal-backdrop hidden>
            <section class="admin-action-modal" role="dialog" aria-modal="true" aria-labelledby="admin-action-modal-title">
                <div class="admin-action-modal-head">
                    <h2 id="admin-action-modal-title" data-admin-action-modal-title>Confirm Action</h2>
                    <button class="admin-action-modal-close" type="button" data-admin-action-modal-close aria-label="Close confirmation modal">&times;</button>
                </div>

                <p class="admin-action-modal-copy" data-admin-action-modal-message>Please confirm this action.</p>

                <label class="admin-action-modal-quantity" data-admin-action-modal-quantity-wrap hidden>
                    <span>Quantity</span>
                    <input type="number" min="1" max="200" step="1" value="1" data-admin-action-modal-quantity-input>
                </label>

                <div class="admin-action-modal-actions">
                    <button class="admin-action-modal-cancel" type="button" data-admin-action-modal-cancel>Cancel</button>
                    <button class="admin-action-modal-confirm" type="button" data-admin-action-modal-confirm>Confirm</button>
                </div>
            </section>
        </div>
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
