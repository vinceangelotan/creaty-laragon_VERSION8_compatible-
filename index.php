<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$isAdminLoggedIn = isset($_SESSION['user_id']);
if ($isAdminLoggedIn) {
    header('Location: admin/dashboard/');
    exit;
}

$isStaffLoggedIn = isset($_SESSION['staff_id']);
if ($isStaffLoggedIn) {
    header('Location: admin/dashboard/');
    exit;
}

$isCustomerLoggedIn = isset($_SESSION['customer_id']);
$cartCount = $isCustomerLoggedIn ? (int) ($_SESSION['customer_cart_count'] ?? 0) : 0;
$accountLabel = $isCustomerLoggedIn ? 'Account' : 'Sign In';
$accountSettingsPath = 'customer-account-settings/';
$logoutPath = 'customer-logout/';

require __DIR__ . '/config/products_repository.php';
require_once __DIR__ . '/customer_notifications_center.php';
$products = load_products_repository();

// Show every camera, but list the ones that match the account's skill level first.
function order_products_for_skill_level($products, $skillLevel)
{
    if (!is_array($products)) {
        return [];
    }

    $targetSkillLevel = normalize_product_skill_level($skillLevel);
    $matching = [];
    $others = [];

    foreach ($products as $productKey => $product) {
        $productSkillLevels = is_array($product)
            ? normalize_product_skill_levels($product['skillLevels'] ?? ($product['skillLevel'] ?? default_product_skill_level()))
            : [];

        if (in_array($targetSkillLevel, $productSkillLevels, true)) {
            $matching[$productKey] = $product;
        } else {
            $others[$productKey] = $product;
        }
    }

    return $matching + $others;
}

$productBrandOptions = load_product_brands_repository();
$productBrandValueMap = product_brand_value_map($productBrandOptions);
$customerNotificationCenter = build_customer_notification_center('', $isCustomerLoggedIn);

if ($isCustomerLoggedIn) {
    $customerSkillLevel = trim((string) ($_SESSION['customer_skill_level'] ?? ''));

    if ($customerSkillLevel === '') {
        require_once __DIR__ . '/config/db.php';

        $customerId = (int) ($_SESSION['customer_id'] ?? 0);
        $customerAccountsTable = $customerAccountsTable ?? 'customer_accounts';

        if ($customerId > 0) {
            $skillStmt = $conn->prepare("SELECT skill_level FROM {$customerAccountsTable} WHERE id = ? LIMIT 1");

            if ($skillStmt instanceof mysqli_stmt) {
                $skillStmt->bind_param('i', $customerId);
                $skillStmt->execute();
                $skillResult = $skillStmt->get_result();
                $skillRecord = $skillResult ? $skillResult->fetch_assoc() : null;
                $skillStmt->close();

                if (is_array($skillRecord)) {
                    $customerSkillLevel = trim((string) ($skillRecord['skill_level'] ?? ''));
                }
            }
        }
    }

    $customerSkillLevel = normalize_product_skill_level($customerSkillLevel);
    $_SESSION['customer_skill_level'] = $customerSkillLevel;
    $products = order_products_for_skill_level($products, $customerSkillLevel);
}

if (!is_array($products)) {
    $products = [];
}

$catalogSkillOptions = product_skill_level_options();
$catalogCategoryOptions = product_category_value_map(load_product_categories_repository());
$catalogPriceMaximum = 100;
foreach ($products as $catalogProduct) {
    if (!is_array($catalogProduct)) {
        continue;
    }
    $catalogPrice = max(0, (float) ($catalogProduct['price'] ?? 0));
    $catalogDiscount = max(0, min(95, (int) ($catalogProduct['discountPercent'] ?? 0)));
    $catalogPriceMaximum = max($catalogPriceMaximum, $catalogPrice * (1 - $catalogDiscount / 100));
}
$catalogPriceMaximum = (int) (ceil($catalogPriceMaximum / 100) * 100);

$howItWorksSlots = [];
for ($slot = 1; $slot <= 4; $slot++) {
    $relativePath = 'assets/how_it_works/' . $slot . '.png';
    $absolutePath = __DIR__ . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);

    $howItWorksSlots[] = [
        'slot' => $slot,
        'relativePath' => $relativePath,
        'exists' => is_file($absolutePath)
    ];
}

$promoBannerSlots = [];
$promoBannerDir = __DIR__ . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'promo_images';

if (is_dir($promoBannerDir)) {
    $promoBannerEntries = scandir($promoBannerDir);

    if (is_array($promoBannerEntries)) {
        foreach ($promoBannerEntries as $entry) {
            if (!is_string($entry)) {
                continue;
            }

            if (!preg_match('/^(\d+)\.png$/i', $entry, $matches)) {
                continue;
            }

            $slot = (int) ($matches[1] ?? 0);
            if ($slot < 1) {
                continue;
            }

            $promoBannerSlots[] = [
                'slot' => $slot,
                'relativePath' => 'assets/promo_images/' . $entry,
                'exists' => true
            ];
        }
    }
}

usort($promoBannerSlots, static function ($left, $right) {
    $leftSlot = (int) ($left['slot'] ?? 0);
    $rightSlot = (int) ($right['slot'] ?? 0);

    return $leftSlot <=> $rightSlot;
});

$activePromoBannerSlots = $promoBannerSlots;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>The Nifty Fifty</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="css/style.css?v=20261015-2">
    <link rel="stylesheet" href="css/catalog-filters.css?v=20261015-2">
</head>
<body class="home-page-customer">
    <header class="site-header">
        <div class="topbar">
            <a class="brand-badge" href="/">
                <img src="assets/images/main_logo.png" alt="The Nifty Fifty">
            </a>

            <a class="topbar-link topbar-help" href="#">
                <img src="assets/icons/help_icon.svg" alt="">
                <span>Help</span>
            </a>

            <form class="topbar-search landing-search" action="#" method="get">
                <input type="search" name="q" placeholder="Search products, events, or services">
            </form>

            <a class="topbar-cart" href="customer-cart/" aria-label="Cart">
                <img src="assets/icons/cart_icon.svg" alt="">
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
                        <li><a class="dropdown-item" href="customer-cart/">My Cart</a></li>
                        <li><a class="dropdown-item" href="customer-cart/?view=history">Reservation History</a></li>
                        <li><a class="dropdown-item" href="customer-events/">Browse Events</a></li>
                        <li><a class="dropdown-item" href="customer-services/">Browse Services</a></li>
                        <li><a class="dropdown-item" href="#">Help Center</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item account-logout-item" href="<?php echo htmlspecialchars($logoutPath, ENT_QUOTES, 'UTF-8'); ?>">Log Out</a></li>
                    </ul>

                    <?php render_customer_notification_trigger_button($customerNotificationCenter, ''); ?>
                </div>
            <?php else: ?>
                <a class="account-pill" href="customer-login/"><?php echo htmlspecialchars($accountLabel, ENT_QUOTES, 'UTF-8'); ?></a>
            <?php endif; ?>
        </div>

        <nav class="section-nav section-nav-interactive section-nav-with-history" aria-label="Catalog filters">
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

            <a class="section-nav-section" href="customer-events/">EVENTS</a>
            <a class="section-nav-section" href="customer-services/">SERVICES</a>

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
                <a class="section-nav-history-link" href="customer-cart/?view=history">Reservation History</a>
            <?php endif; ?>
        </nav>
    </header>

    <main class="landing-shell">
        <section class="promo-banner reveal" aria-label="Promo carousel">
            <button class="promo-arrow promo-arrow-left" type="button" aria-label="Previous promo">&#10094;</button>

            <div class="promo-carousel" aria-live="polite">
                <?php if ($activePromoBannerSlots): ?>
                    <?php foreach ($activePromoBannerSlots as $index => $promoSlot): ?>
                        <?php
                            $slotNumber = (int) ($promoSlot['slot'] ?? 0);
                            $slotPath = (string) ($promoSlot['relativePath'] ?? '');
                            $slotClass = $slotNumber === 1
                                ? 'promo-slide-one'
                                : ($slotNumber === 2 ? 'promo-slide-two' : 'promo-slide-three');
                        ?>
                        <div class="promo-slide <?php echo htmlspecialchars($slotClass, ENT_QUOTES, 'UTF-8'); ?><?php echo $index === 0 ? ' is-active' : ''; ?>" data-promo-slot="<?php echo htmlspecialchars((string) $slotNumber, ENT_QUOTES, 'UTF-8'); ?>">
                            <img class="promo-image" src="<?php echo htmlspecialchars($slotPath, ENT_QUOTES, 'UTF-8'); ?>" alt="Promo banner slot <?php echo htmlspecialchars((string) $slotNumber, ENT_QUOTES, 'UTF-8'); ?>">
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="promo-placeholder promo-placeholder-empty" data-promo-empty>
                        <span>No promo banners available.</span>
                    </div>
                <?php endif; ?>
            </div>

            <button class="promo-arrow promo-arrow-right" type="button" aria-label="Next promo">&#10095;</button>
        </section>

        <section class="landing-section reveal" aria-labelledby="how-it-works-title">
            <h2 class="landing-title" id="how-it-works-title">HOW IT WORKS</h2>

            <div class="steps-grid">
                <?php foreach ($howItWorksSlots as $step): ?>
                    <?php
                        $slot = (int) $step['slot'];
                        $hasImage = (bool) $step['exists'];
                        $stepPath = (string) $step['relativePath'];
                    ?>
                    <article class="step-card">
                        <?php if ($hasImage): ?>
                            <img class="step-image" src="<?php echo htmlspecialchars($stepPath, ENT_QUOTES, 'UTF-8'); ?>" alt="Step <?php echo htmlspecialchars((string) $slot, ENT_QUOTES, 'UTF-8'); ?> in the How it works section">
                        <?php else: ?>
                            <div class="step-placeholder">Step <?php echo htmlspecialchars((string) $slot, ENT_QUOTES, 'UTF-8'); ?> image will be added soon.</div>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="landing-section reveal" aria-labelledby="featured-products-title">
            <div class="catalog-results-heading">
                <h2 class="landing-title" id="featured-products-title" tabindex="-1">FEATURED PRODUCTS</h2>
                <p class="catalog-results-count" data-catalog-results-count role="status" aria-live="polite" aria-atomic="true"></p>
            </div>

            <div class="product-grid">
                <?php foreach ($products as $productKey => $product): ?>
                    <?php
                        if (!is_array($product)) {
                            continue;
                        }

                        $brandLabel = normalize_product_brand($product['brand'] ?? 'Canon');
                        $brandValue = product_brand_slug($brandLabel);
                        $productName = trim((string) ($product['name'] ?? ''));
                        $displayName = trim($brandLabel . ' ' . $productName);
                        $specOne = trim((string) ($product['spec1'] ?? ''));
                        $specTwo = trim((string) ($product['spec2'] ?? ''));
                        $price = (float) ($product['price'] ?? 0);
                        $discount = (int) ($product['discountPercent'] ?? 0);
                        $discount = max(0, min(95, $discount));
                        $isPromo = $discount > 0;
                        $discounted = $price * (1 - ($discount / 100));
                        $imagePath = trim((string) ($product['cameraImage'] ?? ''));
                        $skillLevels = normalize_product_skill_levels($product['skillLevels'] ?? ($product['skillLevel'] ?? default_product_skill_level()));
                        $isBeginnerCamera = in_array('Beginner', $skillLevels, true);
                        $isProfessionalCamera = in_array('Professional', $skillLevels, true);
                        if ($isBeginnerCamera && $isProfessionalCamera) {
                            $skillNote = 'FOR BEGINNERS & PROFESSIONALS';
                        } elseif ($isProfessionalCamera) {
                            $skillNote = 'FOR PROFESSIONALS';
                        } else {
                            $skillNote = 'FOR BEGINNERS';
                        }
                        $categoryValue = product_category_slug(normalize_product_category($product['category'] ?? default_product_category()));
                    ?>
                    <article class="product-card<?php echo $isPromo ? ' product-card-highlight' : ''; ?>" data-product-key="<?php echo htmlspecialchars((string) $productKey, ENT_QUOTES, 'UTF-8'); ?>" data-product-name="<?php echo htmlspecialchars($productName, ENT_QUOTES, 'UTF-8'); ?>" data-brand="<?php echo htmlspecialchars($brandValue, ENT_QUOTES, 'UTF-8'); ?>" data-skill-levels="<?php echo htmlspecialchars(strtolower(implode(' ', $skillLevels)), ENT_QUOTES, 'UTF-8'); ?>" data-category="<?php echo htmlspecialchars($categoryValue, ENT_QUOTES, 'UTF-8'); ?>" data-price="<?php echo htmlspecialchars(number_format($discounted, 2, '.', ''), ENT_QUOTES, 'UTF-8'); ?>">
                        <?php if ($isPromo): ?>
                            <div class="product-ribbon">PROMO <?php echo htmlspecialchars((string) $discount, ENT_QUOTES, 'UTF-8'); ?>% OFF!</div>
                        <?php endif; ?>
                        <a class="product-visual-link" href="customer-products/?product=<?php echo urlencode((string) $productKey); ?>" aria-label="View <?php echo htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8'); ?> product page">
                            <div class="product-visual">
                                <img class="product-visual-image" src="<?php echo htmlspecialchars($imagePath, ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8'); ?>">
                            </div>
                        </a>
                        <div class="product-copy">
                            <h3><a class="product-title-link" href="customer-products/?product=<?php echo urlencode((string) $productKey); ?>"><?php echo htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8'); ?></a></h3>
                            <p><?php echo htmlspecialchars($specOne, ENT_QUOTES, 'UTF-8'); ?></p>
                            <p><?php echo htmlspecialchars($specTwo, ENT_QUOTES, 'UTF-8'); ?></p>
                            <span class="product-skill-note"><?php echo htmlspecialchars($skillNote, ENT_QUOTES, 'UTF-8'); ?></span>
                            <?php if ($isPromo): ?>
                                <p style="margin-top: 0.85rem; margin-bottom: 0; text-align: center; font-size: 1.2rem; font-weight: 800; color: #dde531;">
                                    <span style="color: #a1a1aa; text-decoration: line-through; font-size: 0.95rem; font-weight: 600; margin-right: 0.45rem;">&#8369; <?php echo htmlspecialchars(number_format($price, 2), ENT_QUOTES, 'UTF-8'); ?></span>
                                    <span>&#8369; <?php echo htmlspecialchars(number_format($discounted, 2), ENT_QUOTES, 'UTF-8'); ?></span>
                                </p>
                            <?php else: ?>
                                <p style="margin-top: 0.85rem; margin-bottom: 0; text-align: center; font-size: 1.2rem; font-weight: 800; color: #f4f4f4;">&#8369; <?php echo htmlspecialchars(number_format($price, 2), ENT_QUOTES, 'UTF-8'); ?></p>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>

            <p class="product-grid-empty" hidden>No featured products match the selected filters.</p>
            <button class="catalog-empty-reset" type="button" data-catalog-filter-reset hidden>Clear all filters</button>
        </section>
    </main>

    <?php require __DIR__ . '/customer_message_modal.php'; ?>
    <?php render_customer_notification_modal($customerNotificationCenter); ?>
    <?php render_customer_notification_center_bootstrap_script($customerNotificationCenter, ''); ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script src="js/script.js?v=20261015-1"></script>
</body>
</html>
