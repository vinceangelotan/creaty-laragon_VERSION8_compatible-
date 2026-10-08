<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (isset($_SESSION['customer_id'])) {
    header('Location: ../../');
    exit;
}

if (!isset($_SESSION['user_id'])) {
    header('Location: ../');
    exit;
}

require dirname(__DIR__, 2) . '/config/products_repository.php';
require dirname(__DIR__, 2) . '/config/equipment_inventory_repository.php';
require_once dirname(__DIR__, 2) . '/config/customer_orders_repository.php';

function redirect_admin_brands_page($status, $message)
{
    $query = http_build_query([
        'status' => $status,
        'message' => $message
    ]);

    header('Location: index.php' . ($query !== '' ? '?' . $query : ''));
    exit;
}

function update_product_brand_payload(array $product, $brandLabel)
{
    $nextBrand = sanitize_product_brand_label($brandLabel);
    $product['brand'] = $nextBrand;

    $specs = is_array($product['specs'] ?? null) ? $product['specs'] : [];
    $specs['Brand'] = [$nextBrand];
    $product['specs'] = $specs;

    return $product;
}

if (strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $action = trim((string) ($_POST['brand_action'] ?? ''));
    if (in_array($action, ['rename_brand', 'remove_brand'], true)) {
        $brandMutationLock = customer_orders_repository_acquire_lock();
        if ($brandMutationLock === null) {
            redirect_admin_brands_page('error', 'Inventory is busy. Please try this brand change again.');
        }
    }

    $brands = load_product_brands_repository();
    $brandMap = product_brand_value_map($brands);
    $selectedValue = trim((string) ($_POST['brand_value'] ?? ''));
    $selectedBrand = $selectedValue !== '' ? resolve_product_brand_label($selectedValue, $brands) : '';

    try {
        if ($action === 'add_brand') {
            $newBrand = sanitize_product_brand_label($_POST['new_brand'] ?? '');

            if ($newBrand === '') {
                throw new RuntimeException('Brand name is required.');
            }

            if ($newBrand === '__manage_brands__') {
                throw new RuntimeException('Please choose a valid brand name.');
            }

            if (resolve_product_brand_label($newBrand, $brands) !== '') {
                throw new RuntimeException('That brand already exists.');
            }

            $brands[] = $newBrand;

            if (!save_product_brands_repository($brands)) {
                throw new RuntimeException('Unable to save the new brand.');
            }

            redirect_admin_brands_page('success', 'Brand added successfully.');
        }

        if ($action === 'rename_brand') {
            if ($selectedBrand === '') {
                throw new RuntimeException('Brand to rename was not found.');
            }

            $newBrand = sanitize_product_brand_label($_POST['new_brand'] ?? '');

            if ($newBrand === '') {
                throw new RuntimeException('New brand name is required.');
            }

            if ($newBrand === '__manage_brands__') {
                throw new RuntimeException('Please choose a valid brand name.');
            }

            $currentBrandValue = product_brand_slug($selectedBrand);
            $newBrandValue = product_brand_slug($newBrand);
            $duplicateBrand = resolve_product_brand_label($newBrand, $brands);

            if (
                $newBrandValue !== $currentBrandValue
                && $duplicateBrand !== ''
                && product_brand_slug($duplicateBrand) !== $currentBrandValue
            ) {
                throw new RuntimeException('Another brand already uses that name.');
            }

            $nextBrands = [];
            $didReplaceBrand = false;

            foreach ($brands as $brandLabel) {
                if (product_brand_slug($brandLabel) !== $currentBrandValue) {
                    $nextBrands[] = $brandLabel;
                    continue;
                }

                if (!$didReplaceBrand) {
                    $nextBrands[] = $newBrand;
                    $didReplaceBrand = true;
                }
            }

            if (!$didReplaceBrand) {
                throw new RuntimeException('Brand to rename was not found.');
            }

            $products = load_products_repository();
            $activeUpdatedCount = 0;

            foreach ($products as $productKey => $product) {
                if (!is_array($product)) {
                    continue;
                }

                $productBrandValue = product_brand_slug(normalize_product_brand($product['brand'] ?? default_product_brand()));
                if ($productBrandValue !== $currentBrandValue) {
                    continue;
                }

                $products[$productKey] = update_product_brand_payload($product, $newBrand);
                $activeUpdatedCount++;
            }

            $archivedProducts = load_archived_products_repository();
            $archivedUpdatedCount = 0;

            foreach ($archivedProducts as $index => $entry) {
                if (!is_array($entry)) {
                    continue;
                }

                $archivedProduct = is_array($entry['product'] ?? null) ? $entry['product'] : null;
                if (!is_array($archivedProduct)) {
                    continue;
                }

                $productBrandValue = product_brand_slug(normalize_product_brand($archivedProduct['brand'] ?? default_product_brand()));
                if ($productBrandValue !== $currentBrandValue) {
                    continue;
                }

                $archivedProducts[$index]['product'] = update_product_brand_payload($archivedProduct, $newBrand);
                $archivedUpdatedCount++;
            }

            if (!save_product_brands_repository($nextBrands)) {
                throw new RuntimeException('Unable to save renamed brand.');
            }

            if (!save_products_repository($products)) {
                throw new RuntimeException('Unable to save active products for this rename.');
            }

            if (!save_archived_products_repository($archivedProducts)) {
                throw new RuntimeException('Unable to save archived products for this rename.');
            }

            redirect_admin_brands_page(
                'success',
                'Brand renamed. Updated ' . $activeUpdatedCount . ' active product(s) and ' . $archivedUpdatedCount . ' archived product(s).'
            );
        }

        if ($action === 'remove_brand') {
            if ($selectedBrand === '') {
                throw new RuntimeException('Brand to remove was not found.');
            }

            if (count($brandMap) <= 1) {
                throw new RuntimeException('At least one brand must remain available.');
            }

            $targetBrandValue = product_brand_slug($selectedBrand);
            $products = load_products_repository();
            $archivedProducts = load_archived_products_repository();
            $orders = load_customer_orders_repository();
            $equipmentInventory = sync_equipment_inventory_with_products(
                $products,
                load_equipment_inventory_repository(),
                12
            );
            $archivedEquipmentUnits = load_archived_equipment_units_repository();
            $projectRoot = dirname(__DIR__, 2);

            $productKeysToArchive = [];

            foreach ($products as $productKey => $product) {
                if (!is_array($product)) {
                    continue;
                }

                $productBrandValue = product_brand_slug(normalize_product_brand($product['brand'] ?? default_product_brand()));
                if ($productBrandValue !== $targetBrandValue) {
                    continue;
                }

                $productKeysToArchive[] = (string) $productKey;
            }

            // Validate every model before archive_product_record can move any images.
            $reservationIntervals = customer_order_group_intervals_by_product(
                customer_order_build_camera_reservation_intervals($orders, $products)
            );
            foreach ($productKeysToArchive as $productKey) {
                $productLabel = equipment_model_label_from_product($products[$productKey]);
                foreach ((array) ($equipmentInventory[$productKey]['units'] ?? []) as $unit) {
                    if (normalize_equipment_status_token($unit['status'] ?? '') === 'in-use') {
                        throw new RuntimeException('Cannot remove this brand while ' . $productLabel . ' has a unit in use. Complete its return first.');
                    }
                }

                $normalizedProductKey = normalize_customer_order_product_key($productKey);
                if (customer_order_peak_reserved_quantity($reservationIntervals[$normalizedProductKey] ?? [], time()) > 0) {
                    throw new RuntimeException('Cannot remove this brand while ' . $productLabel . ' has active or upcoming reservations. Resolve those bookings first.');
                }
            }

            $archivedProductsCount = 0;
            $archivedEquipmentCount = 0;

            foreach ($productKeysToArchive as $productKey) {
                if (!isset($products[$productKey]) || !is_array($products[$productKey])) {
                    continue;
                }

                $archiveInventoryResult = archive_all_equipment_units_for_product(
                    $equipmentInventory,
                    $archivedEquipmentUnits,
                    $products,
                    $productKey,
                    'Brand "' . $selectedBrand . '" removed from brand manager'
                );

                $equipmentInventory = $archiveInventoryResult['inventory'];
                $archivedEquipmentUnits = $archiveInventoryResult['archivedUnits'];

                $newArchivedUnitKeys = [];
                foreach ((array) ($archiveInventoryResult['archivedEntries'] ?? []) as $archivedEntry) {
                    if (!is_array($archivedEntry)) {
                        continue;
                    }

                    $candidateArchiveKey = trim((string) ($archivedEntry['archiveKey'] ?? ''));
                    if ($candidateArchiveKey !== '') {
                        $newArchivedUnitKeys[] = $candidateArchiveKey;
                    }
                }

                $archiveResult = archive_product_record($products, $productKey, $projectRoot, $archivedProducts);
                $products = $archiveResult['products'];
                $archivedProducts = $archiveResult['archivedProducts'];
                $productArchiveKey = trim((string) ($archiveResult['archivedEntry']['archiveKey'] ?? ''));

                if ($productArchiveKey !== '') {
                    foreach ($archivedEquipmentUnits as &$archivedUnitEntry) {
                        if (!is_array($archivedUnitEntry)) {
                            continue;
                        }

                        if (trim((string) ($archivedUnitEntry['productKey'] ?? '')) !== $productKey) {
                            continue;
                        }

                        if (!in_array(trim((string) ($archivedUnitEntry['archiveKey'] ?? '')), $newArchivedUnitKeys, true)) {
                            continue;
                        }

                        if (isset($archivedUnitEntry['productArchiveKey']) && trim((string) $archivedUnitEntry['productArchiveKey']) !== '') {
                            continue;
                        }

                        $archivedUnitEntry['productArchiveKey'] = $productArchiveKey;
                    }
                    unset($archivedUnitEntry);
                }

                $archivedProductsCount++;
                $archivedEquipmentCount += count((array) ($archiveInventoryResult['archivedEntries'] ?? []));
            }

            $nextBrands = [];

            foreach ($brands as $brandLabel) {
                if (product_brand_slug($brandLabel) !== $targetBrandValue) {
                    $nextBrands[] = $brandLabel;
                }
            }

            if (!$nextBrands) {
                throw new RuntimeException('At least one brand must remain available.');
            }

            if (!save_archived_products_repository($archivedProducts)) {
                throw new RuntimeException('Unable to save archived products after brand removal.');
            }

            if (!save_products_repository($products)) {
                throw new RuntimeException('Unable to save active products after brand removal.');
            }

            if (!save_archived_equipment_units_repository($archivedEquipmentUnits)) {
                throw new RuntimeException('Unable to save archived equipment after brand removal.');
            }

            $equipmentInventory = sync_equipment_inventory_with_products(
                $products,
                $equipmentInventory,
                12
            );

            if (!save_equipment_inventory_repository($equipmentInventory)) {
                throw new RuntimeException('Unable to save equipment inventory after brand removal.');
            }

            if (!save_product_brands_repository($nextBrands)) {
                throw new RuntimeException('Unable to remove brand from list.');
            }

            redirect_admin_brands_page(
                'success',
                'Brand removed. Archived ' . $archivedProductsCount . ' product(s) and ' . $archivedEquipmentCount . ' equipment unit(s).'
            );
        }

        throw new RuntimeException('Unknown action.');
    } catch (Throwable $error) {
        redirect_admin_brands_page('error', $error->getMessage());
    }
}

$flashType = strtolower(trim((string) ($_GET['status'] ?? '')));
if (!in_array($flashType, ['success', 'error', 'warning'], true)) {
    $flashType = '';
}

$flashMessage = trim((string) ($_GET['message'] ?? ''));
$brands = load_product_brands_repository();
$brandMap = product_brand_value_map($brands);
$products = load_products_repository();
$archivedProducts = load_archived_products_repository();
$usage = [];

foreach ($brandMap as $brandValue => $brandLabel) {
    $usage[$brandValue] = [
        'label' => $brandLabel,
        'active' => 0,
        'archived' => 0
    ];
}

foreach ($products as $product) {
    if (!is_array($product)) {
        continue;
    }

    $brandLabel = normalize_product_brand($product['brand'] ?? default_product_brand());
    $brandValue = product_brand_slug($brandLabel);

    if (!isset($usage[$brandValue])) {
        $usage[$brandValue] = [
            'label' => $brandLabel,
            'active' => 0,
            'archived' => 0
        ];
    }

    $usage[$brandValue]['active']++;
}

foreach ($archivedProducts as $entry) {
    if (!is_array($entry)) {
        continue;
    }

    $product = is_array($entry['product'] ?? null) ? $entry['product'] : null;
    if (!is_array($product)) {
        continue;
    }

    $brandLabel = normalize_product_brand($product['brand'] ?? default_product_brand());
    $brandValue = product_brand_slug($brandLabel);

    if (!isset($usage[$brandValue])) {
        $usage[$brandValue] = [
            'label' => $brandLabel,
            'active' => 0,
            'archived' => 0
        ];
    }

    $usage[$brandValue]['archived']++;
}

$canRemoveBrands = count($brandMap) > 1;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Brands | Creaty</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../css/admin-refinements.css?v=20261008-1">
    <style>
        body {
            margin: 0;
            background: #0c0e12;
            color: #f4f4f4;
            font-family: 'Montserrat', sans-serif;
        }

        .brands-shell {
            width: min(100%, 1100px);
            margin: 0 auto;
            padding: 1.25rem 1rem 2.25rem;
            display: grid;
            gap: 1rem;
        }

        .brands-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 0.8rem;
            flex-wrap: wrap;
        }

        .brands-top h1 {
            margin: 0;
            font-size: 1.35rem;
            letter-spacing: 0.01em;
        }

        .brands-top a {
            color: #dde531;
            text-decoration: none;
            font-weight: 700;
        }

        .brands-note {
            margin: 0;
            color: #b7becb;
            line-height: 1.45;
            font-size: 0.9rem;
        }

        .brands-flash {
            border-radius: 10px;
            border: 1px solid transparent;
            padding: 0.72rem 0.82rem;
            font-size: 0.9rem;
            line-height: 1.35;
        }

        .brands-flash-success {
            background: rgba(75, 160, 80, 0.18);
            border-color: rgba(75, 160, 80, 0.6);
            color: #d8ffd9;
        }

        .brands-flash-error {
            background: rgba(190, 67, 67, 0.2);
            border-color: rgba(190, 67, 67, 0.62);
            color: #ffd9d9;
        }

        .brands-add-form {
            display: flex;
            gap: 0.6rem;
            align-items: end;
            flex-wrap: wrap;
            padding: 0.85rem;
            border: 1px solid rgba(255, 255, 255, 0.18);
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.03);
        }

        .brands-field {
            display: grid;
            gap: 0.35rem;
            min-width: min(320px, 100%);
            flex: 1 1 320px;
        }

        .brands-field label {
            font-size: 0.82rem;
            color: #cdd3de;
            font-weight: 600;
        }

        .brands-field input {
            border: 1px solid rgba(255, 255, 255, 0.23);
            background: #121620;
            color: #f4f4f4;
            border-radius: 9px;
            padding: 0.55rem 0.62rem;
            font: inherit;
        }

        .brands-button {
            border: 1px solid #dde531;
            border-radius: 9px;
            background: #dde531;
            color: #11131a;
            font: inherit;
            font-weight: 700;
            padding: 0.58rem 0.86rem;
            cursor: pointer;
            min-height: 40px;
        }

        .brands-button:hover,
        .brands-button:focus-visible {
            filter: brightness(0.96);
        }

        .brands-button-danger {
            border-color: rgba(219, 89, 89, 0.75);
            background: rgba(219, 89, 89, 0.15);
            color: #ffd3d3;
        }

        .brands-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 0.8rem;
        }

        .brand-card {
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.03);
            padding: 0.85rem;
            display: grid;
            gap: 0.65rem;
        }

        .brand-card h2 {
            margin: 0;
            font-size: 1.04rem;
        }

        .brand-meta {
            margin: 0;
            color: #c2c9d5;
            font-size: 0.84rem;
        }

        .brand-inline-form {
            display: grid;
            gap: 0.45rem;
        }

        .brand-inline-form input {
            border: 1px solid rgba(255, 255, 255, 0.23);
            background: #121620;
            color: #f4f4f4;
            border-radius: 8px;
            padding: 0.48rem 0.55rem;
            font: inherit;
            font-size: 0.88rem;
        }

        .brand-card-actions {
            display: flex;
            gap: 0.55rem;
            align-items: center;
            flex-wrap: wrap;
        }

        .brand-card-actions .brands-button {
            min-height: 36px;
            font-size: 0.82rem;
        }

        .brand-remove-note {
            margin: 0;
            color: #adb4c0;
            font-size: 0.76rem;
        }

        .brands-modal-backdrop {
            position: fixed;
            inset: 0;
            z-index: 1200;
            display: grid;
            place-items: center;
            padding: 1rem;
            background: rgba(5, 7, 10, 0.76);
        }

        .brands-modal-backdrop[hidden] {
            display: none;
        }

        .brands-modal {
            width: min(100%, 460px);
            border-radius: 12px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            background: #121620;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.45);
            padding: 0.9rem;
            display: grid;
            gap: 0.8rem;
        }

        .brands-modal-head {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 0.6rem;
        }

        .brands-modal-head h2 {
            margin: 0;
            font-size: 1rem;
        }

        .brands-modal-close {
            width: 32px;
            height: 32px;
            border-radius: 999px;
            border: 1px solid rgba(255, 255, 255, 0.24);
            background: transparent;
            color: #f4f4f4;
            font-size: 1.1rem;
            line-height: 1;
            cursor: pointer;
        }

        .brands-modal-close:hover,
        .brands-modal-close:focus-visible {
            background: rgba(255, 255, 255, 0.1);
        }

        .brands-modal-message {
            margin: 0;
            color: #d7dbe3;
            line-height: 1.45;
            font-size: 0.9rem;
        }

        .brands-modal-actions {
            display: flex;
            justify-content: flex-end;
            gap: 0.55rem;
            flex-wrap: wrap;
        }

        .brands-modal-cancel {
            border-color: rgba(255, 255, 255, 0.24);
            background: transparent;
            color: #f4f4f4;
        }

        body.brands-modal-open {
            overflow: hidden;
        }
    </style>
</head>
<body>
    <main class="brands-shell">
        <div class="brands-top">
            <h1>Manage Brands</h1>
            <a href="../dashboard/">Back to Dashboard</a>
        </div>

        <p class="brands-note">Removing a brand archives all active products under it. Restoring any archived product automatically re-adds its brand to your dropdowns.</p>

        <?php if ($flashType !== '' && $flashMessage !== ''): ?>
            <div class="brands-flash <?php echo htmlspecialchars('brands-flash-' . $flashType, ENT_QUOTES, 'UTF-8'); ?>" role="status">
                <?php echo htmlspecialchars($flashMessage, ENT_QUOTES, 'UTF-8'); ?>
            </div>
        <?php endif; ?>

        <form method="post" action="" class="brands-add-form">
            <input type="hidden" name="brand_action" value="add_brand">
            <div class="brands-field">
                <label for="new-brand-name">Add New Brand</label>
                <input id="new-brand-name" name="new_brand" type="text" maxlength="60" required>
            </div>
            <button class="brands-button" type="submit">Add Brand</button>
        </form>

        <section class="brands-grid" aria-label="Brand records">
            <?php foreach ($brandMap as $brandValue => $brandLabel): ?>
                <?php
                    $brandStats = $usage[$brandValue] ?? ['active' => 0, 'archived' => 0];
                    $activeCount = (int) ($brandStats['active'] ?? 0);
                    $archivedCount = (int) ($brandStats['archived'] ?? 0);
                ?>
                <article class="brand-card">
                    <h2><?php echo htmlspecialchars($brandLabel, ENT_QUOTES, 'UTF-8'); ?></h2>
                    <p class="brand-meta">Active products: <?php echo htmlspecialchars((string) $activeCount, ENT_QUOTES, 'UTF-8'); ?></p>
                    <p class="brand-meta">Archived products: <?php echo htmlspecialchars((string) $archivedCount, ENT_QUOTES, 'UTF-8'); ?></p>

                    <form method="post" action="" class="brand-inline-form" data-brand-rename-form data-brand-label="<?php echo htmlspecialchars($brandLabel, ENT_QUOTES, 'UTF-8'); ?>">
                        <input type="hidden" name="brand_action" value="rename_brand">
                        <input type="hidden" name="brand_value" value="<?php echo htmlspecialchars($brandValue, ENT_QUOTES, 'UTF-8'); ?>">
                        <input name="new_brand" type="text" maxlength="60" value="<?php echo htmlspecialchars($brandLabel, ENT_QUOTES, 'UTF-8'); ?>" required>
                        <div class="brand-card-actions">
                            <button class="brands-button" type="submit">Rename Brand</button>
                        </div>
                    </form>

                    <form method="post" action="" data-brand-remove-form data-brand-label="<?php echo htmlspecialchars($brandLabel, ENT_QUOTES, 'UTF-8'); ?>">
                        <input type="hidden" name="brand_action" value="remove_brand">
                        <input type="hidden" name="brand_value" value="<?php echo htmlspecialchars($brandValue, ENT_QUOTES, 'UTF-8'); ?>">
                        <div class="brand-card-actions">
                            <button class="brands-button brands-button-danger" type="submit"<?php echo $canRemoveBrands ? '' : ' disabled'; ?>>Remove Brand</button>
                            <?php if (!$canRemoveBrands): ?>
                                <p class="brand-remove-note">At least one brand must remain.</p>
                            <?php endif; ?>
                        </div>
                    </form>
                </article>
            <?php endforeach; ?>
        </section>
    </main>

    <div class="brands-modal-backdrop" data-brands-modal-backdrop hidden>
        <section class="brands-modal" role="dialog" aria-modal="true" aria-labelledby="brands-modal-title" aria-describedby="brands-modal-message">
            <div class="brands-modal-head">
                <h2 id="brands-modal-title" data-brands-modal-title>Confirm Action</h2>
                <button class="brands-modal-close" type="button" data-brands-modal-cancel aria-label="Close">&times;</button>
            </div>

            <p class="brands-modal-message" id="brands-modal-message" data-brands-modal-message>Are you sure you want to continue?</p>

            <div class="brands-modal-actions">
                <button class="brands-button brands-modal-cancel" type="button" data-brands-modal-cancel>Cancel</button>
                <button class="brands-button brands-button-danger" type="button" data-brands-modal-confirm>Proceed</button>
            </div>
        </section>
    </div>

    <script>
    (function () {
        var modalBackdrop = document.querySelector('[data-brands-modal-backdrop]');
        var modalTitle = document.querySelector('[data-brands-modal-title]');
        var modalMessage = document.querySelector('[data-brands-modal-message]');
        var modalConfirm = document.querySelector('[data-brands-modal-confirm]');
        var modalCancelButtons = document.querySelectorAll('[data-brands-modal-cancel]');
        var pendingForm = null;

        function closeModal() {
            pendingForm = null;

            if (!modalBackdrop) {
                return;
            }

            modalBackdrop.hidden = true;
            document.body.classList.remove('brands-modal-open');
        }

        function openModal(config) {
            if (!modalBackdrop || !modalTitle || !modalMessage || !modalConfirm) {
                return;
            }

            pendingForm = config && config.form ? config.form : null;
            modalTitle.textContent = config && config.title ? config.title : 'Confirm Action';
            modalMessage.textContent = config && config.message ? config.message : 'Are you sure you want to continue?';
            modalConfirm.textContent = config && config.confirmLabel ? config.confirmLabel : 'Proceed';

            modalBackdrop.hidden = false;
            document.body.classList.add('brands-modal-open');
        }

        if (modalConfirm) {
            modalConfirm.addEventListener('click', function () {
                var formToSubmit = pendingForm;
                closeModal();

                if (formToSubmit) {
                    formToSubmit.submit();
                }
            });
        }

        modalCancelButtons.forEach(function (button) {
            button.addEventListener('click', closeModal);
        });

        if (modalBackdrop) {
            modalBackdrop.addEventListener('click', function (event) {
                if (event.target === modalBackdrop) {
                    closeModal();
                }
            });
        }

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && modalBackdrop && !modalBackdrop.hidden) {
                closeModal();
            }
        });

        document.querySelectorAll('[data-brand-remove-form]').forEach(function (form) {
            form.addEventListener('submit', function (event) {
                var brandLabel = form.getAttribute('data-brand-label') || 'this brand';
                event.preventDefault();

                openModal({
                    form: form,
                    title: 'Remove Brand',
                    message: 'Removing "' + brandLabel + '" will archive all products related to it. Proceed or Cancel?',
                    confirmLabel: 'Proceed'
                });
            });
        });

        document.querySelectorAll('[data-brand-rename-form]').forEach(function (form) {
            form.addEventListener('submit', function (event) {
                var brandLabel = form.getAttribute('data-brand-label') || 'this brand';
                var renameInput = form.querySelector('input[name="new_brand"]');
                var nextLabel = renameInput ? String(renameInput.value || '').trim() : '';
                var warning = 'Renaming "' + brandLabel + '" will affect all products under it. Proceed or Cancel?';

                if (nextLabel !== '' && nextLabel.toLowerCase() !== brandLabel.toLowerCase()) {
                    warning = 'Renaming "' + brandLabel + '" to "' + nextLabel + '" will affect all products under it. Proceed or Cancel?';
                }

                event.preventDefault();

                openModal({
                    form: form,
                    title: 'Rename Brand',
                    message: warning,
                    confirmLabel: 'Proceed'
                });
            });
        });
    })();
    </script>
</body>
</html>
