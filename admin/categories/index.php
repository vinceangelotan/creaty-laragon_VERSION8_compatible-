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

function redirect_admin_categories_page($status, $message)
{
    $query = http_build_query([
        'status' => $status,
        'message' => $message
    ]);

    header('Location: index.php' . ($query !== '' ? '?' . $query : ''));
    exit;
}

function update_product_category_payload(array $product, $categoryLabel)
{
    $product['category'] = sanitize_product_category_label($categoryLabel);

    return $product;
}

if (strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $action = trim((string) ($_POST['category_action'] ?? ''));
    $categories = load_product_categories_repository();
    $categoryMap = product_category_value_map($categories);
    $selectedValue = trim((string) ($_POST['category_value'] ?? ''));
    $selectedCategory = $selectedValue !== '' ? resolve_product_category_label($selectedValue, $categories) : '';

    try {
        if ($action === 'add_category') {
            $newCategory = sanitize_product_category_label($_POST['new_category'] ?? '');

            if ($newCategory === '') {
                throw new RuntimeException('Category name is required.');
            }

            if ($newCategory === '__manage_categories__') {
                throw new RuntimeException('Please choose a valid category name.');
            }

            if (resolve_product_category_label($newCategory, $categories) !== '') {
                throw new RuntimeException('That category already exists.');
            }

            $categories[] = $newCategory;

            if (!save_product_categories_repository($categories)) {
                throw new RuntimeException('Unable to save the new category.');
            }

            redirect_admin_categories_page('success', 'Category added successfully.');
        }

        if ($action === 'rename_category') {
            if ($selectedCategory === '') {
                throw new RuntimeException('Category to rename was not found.');
            }

            $newCategory = sanitize_product_category_label($_POST['new_category'] ?? '');

            if ($newCategory === '') {
                throw new RuntimeException('New category name is required.');
            }

            if ($newCategory === '__manage_categories__') {
                throw new RuntimeException('Please choose a valid category name.');
            }

            $currentCategoryValue = product_category_slug($selectedCategory);
            $newCategoryValue = product_category_slug($newCategory);
            $duplicateCategory = resolve_product_category_label($newCategory, $categories);

            if (
                $newCategoryValue !== $currentCategoryValue
                && $duplicateCategory !== ''
                && product_category_slug($duplicateCategory) !== $currentCategoryValue
            ) {
                throw new RuntimeException('Another category already uses that name.');
            }

            $nextCategories = [];
            $didReplaceCategory = false;

            foreach ($categories as $categoryLabel) {
                if (product_category_slug($categoryLabel) !== $currentCategoryValue) {
                    $nextCategories[] = $categoryLabel;
                    continue;
                }

                if (!$didReplaceCategory) {
                    $nextCategories[] = $newCategory;
                    $didReplaceCategory = true;
                }
            }

            if (!$didReplaceCategory) {
                throw new RuntimeException('Category to rename was not found.');
            }

            $products = load_products_repository();
            $activeUpdatedCount = 0;

            foreach ($products as $productKey => $product) {
                if (!is_array($product)) {
                    continue;
                }

                $productCategoryValue = product_category_slug(normalize_product_category($product['category'] ?? default_product_category()));
                if ($productCategoryValue !== $currentCategoryValue) {
                    continue;
                }

                $products[$productKey] = update_product_category_payload($product, $newCategory);
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

                $productCategoryValue = product_category_slug(normalize_product_category($archivedProduct['category'] ?? default_product_category()));
                if ($productCategoryValue !== $currentCategoryValue) {
                    continue;
                }

                $archivedProducts[$index]['product'] = update_product_category_payload($archivedProduct, $newCategory);
                $archivedUpdatedCount++;
            }

            if (!save_product_categories_repository($nextCategories)) {
                throw new RuntimeException('Unable to save renamed category.');
            }

            if (!save_products_repository($products)) {
                throw new RuntimeException('Unable to save active products for this rename.');
            }

            if (!save_archived_products_repository($archivedProducts)) {
                throw new RuntimeException('Unable to save archived products for this rename.');
            }

            redirect_admin_categories_page(
                'success',
                'Category renamed. Updated ' . $activeUpdatedCount . ' active product(s) and ' . $archivedUpdatedCount . ' archived product(s).'
            );
        }

        if ($action === 'remove_category') {
            if ($selectedCategory === '') {
                throw new RuntimeException('Category to remove was not found.');
            }

            if (count($categoryMap) <= 1) {
                throw new RuntimeException('At least one category must remain available.');
            }

            $targetCategoryValue = product_category_slug($selectedCategory);
            $nextCategories = [];

            foreach ($categories as $categoryLabel) {
                if (product_category_slug($categoryLabel) !== $targetCategoryValue) {
                    $nextCategories[] = $categoryLabel;
                }
            }

            if (!$nextCategories) {
                throw new RuntimeException('At least one category must remain available.');
            }

            $fallbackCategory = normalize_product_category($nextCategories[0]);
            $products = load_products_repository();
            $activeUpdatedCount = 0;

            foreach ($products as $productKey => $product) {
                if (!is_array($product)) {
                    continue;
                }

                $productCategoryValue = product_category_slug(normalize_product_category($product['category'] ?? default_product_category()));
                if ($productCategoryValue !== $targetCategoryValue) {
                    continue;
                }

                $products[$productKey] = update_product_category_payload($product, $fallbackCategory);
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

                $productCategoryValue = product_category_slug(normalize_product_category($archivedProduct['category'] ?? default_product_category()));
                if ($productCategoryValue !== $targetCategoryValue) {
                    continue;
                }

                $archivedProducts[$index]['product'] = update_product_category_payload($archivedProduct, $fallbackCategory);
                $archivedUpdatedCount++;
            }

            if (!save_product_categories_repository($nextCategories)) {
                throw new RuntimeException('Unable to remove category from list.');
            }

            if (!save_products_repository($products)) {
                throw new RuntimeException('Unable to save active products after category removal.');
            }

            if (!save_archived_products_repository($archivedProducts)) {
                throw new RuntimeException('Unable to save archived products after category removal.');
            }

            redirect_admin_categories_page(
                'success',
                'Category removed. Reassigned ' . $activeUpdatedCount . ' active product(s) and ' . $archivedUpdatedCount . ' archived product(s) to "' . $fallbackCategory . '".'
            );
        }

        throw new RuntimeException('Unknown action.');
    } catch (Throwable $error) {
        redirect_admin_categories_page('error', $error->getMessage());
    }
}

$flashType = strtolower(trim((string) ($_GET['status'] ?? '')));
if (!in_array($flashType, ['success', 'error', 'warning'], true)) {
    $flashType = '';
}

$flashMessage = trim((string) ($_GET['message'] ?? ''));
$categories = load_product_categories_repository();
$categoryMap = product_category_value_map($categories);
$products = load_products_repository();
$archivedProducts = load_archived_products_repository();
$usage = [];

foreach ($categoryMap as $categoryValue => $categoryLabel) {
    $usage[$categoryValue] = [
        'label' => $categoryLabel,
        'active' => 0,
        'archived' => 0
    ];
}

foreach ($products as $product) {
    if (!is_array($product)) {
        continue;
    }

    $categoryLabel = normalize_product_category($product['category'] ?? default_product_category());
    $categoryValue = product_category_slug($categoryLabel);

    if (!isset($usage[$categoryValue])) {
        $usage[$categoryValue] = [
            'label' => $categoryLabel,
            'active' => 0,
            'archived' => 0
        ];
    }

    $usage[$categoryValue]['active']++;
}

foreach ($archivedProducts as $entry) {
    if (!is_array($entry)) {
        continue;
    }

    $product = is_array($entry['product'] ?? null) ? $entry['product'] : null;
    if (!is_array($product)) {
        continue;
    }

    $categoryLabel = normalize_product_category($product['category'] ?? default_product_category());
    $categoryValue = product_category_slug($categoryLabel);

    if (!isset($usage[$categoryValue])) {
        $usage[$categoryValue] = [
            'label' => $categoryLabel,
            'active' => 0,
            'archived' => 0
        ];
    }

    $usage[$categoryValue]['archived']++;
}

$canRemoveCategories = count($categoryMap) > 1;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Categories | Creaty</title>
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

        .categories-shell {
            width: min(100%, 1100px);
            margin: 0 auto;
            padding: 1.25rem 1rem 2.25rem;
            display: grid;
            gap: 1rem;
        }

        .categories-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 0.8rem;
            flex-wrap: wrap;
        }

        .categories-top h1 {
            margin: 0;
            font-size: 1.35rem;
            letter-spacing: 0.01em;
        }

        .categories-top a {
            color: #dde531;
            text-decoration: none;
            font-weight: 700;
        }

        .categories-note {
            margin: 0;
            color: #b7becb;
            line-height: 1.45;
            font-size: 0.9rem;
        }

        .categories-flash {
            border-radius: 10px;
            border: 1px solid transparent;
            padding: 0.72rem 0.82rem;
            font-size: 0.9rem;
            line-height: 1.35;
        }

        .categories-flash-success {
            background: rgba(75, 160, 80, 0.18);
            border-color: rgba(75, 160, 80, 0.6);
            color: #d8ffd9;
        }

        .categories-flash-error {
            background: rgba(190, 67, 67, 0.2);
            border-color: rgba(190, 67, 67, 0.62);
            color: #ffd9d9;
        }

        .categories-add-form {
            display: flex;
            gap: 0.6rem;
            align-items: end;
            flex-wrap: wrap;
            padding: 0.85rem;
            border: 1px solid rgba(255, 255, 255, 0.18);
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.03);
        }

        .categories-field {
            display: grid;
            gap: 0.35rem;
            min-width: min(320px, 100%);
            flex: 1 1 320px;
        }

        .categories-field label {
            font-size: 0.82rem;
            color: #cdd3de;
            font-weight: 600;
        }

        .categories-field input {
            border: 1px solid rgba(255, 255, 255, 0.23);
            background: #121620;
            color: #f4f4f4;
            border-radius: 9px;
            padding: 0.55rem 0.62rem;
            font: inherit;
        }

        .categories-button {
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

        .categories-button:hover,
        .categories-button:focus-visible {
            filter: brightness(0.96);
        }

        .categories-button-danger {
            border-color: rgba(219, 89, 89, 0.75);
            background: rgba(219, 89, 89, 0.15);
            color: #ffd3d3;
        }

        .categories-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 0.8rem;
        }

        .category-card {
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.03);
            padding: 0.85rem;
            display: grid;
            gap: 0.65rem;
        }

        .category-card h2 {
            margin: 0;
            font-size: 1.04rem;
        }

        .category-meta {
            margin: 0;
            color: #c2c9d5;
            font-size: 0.84rem;
        }

        .category-inline-form {
            display: grid;
            gap: 0.45rem;
        }

        .category-inline-form input {
            border: 1px solid rgba(255, 255, 255, 0.23);
            background: #121620;
            color: #f4f4f4;
            border-radius: 8px;
            padding: 0.48rem 0.55rem;
            font: inherit;
            font-size: 0.88rem;
        }

        .category-card-actions {
            display: flex;
            gap: 0.55rem;
            align-items: center;
            flex-wrap: wrap;
        }

        .category-card-actions .categories-button {
            min-height: 36px;
            font-size: 0.82rem;
        }

        .category-remove-note {
            margin: 0;
            color: #adb4c0;
            font-size: 0.76rem;
        }

        .categories-modal-backdrop {
            position: fixed;
            inset: 0;
            z-index: 1200;
            display: grid;
            place-items: center;
            padding: 1rem;
            background: rgba(5, 7, 10, 0.76);
        }

        .categories-modal-backdrop[hidden] {
            display: none;
        }

        .categories-modal {
            width: min(100%, 460px);
            border-radius: 12px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            background: #121620;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.45);
            padding: 0.9rem;
            display: grid;
            gap: 0.8rem;
        }

        .categories-modal-head {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 0.6rem;
        }

        .categories-modal-head h2 {
            margin: 0;
            font-size: 1rem;
        }

        .categories-modal-close {
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

        .categories-modal-close:hover,
        .categories-modal-close:focus-visible {
            background: rgba(255, 255, 255, 0.1);
        }

        .categories-modal-message {
            margin: 0;
            color: #d7dbe3;
            line-height: 1.45;
            font-size: 0.9rem;
        }

        .categories-modal-actions {
            display: flex;
            justify-content: flex-end;
            gap: 0.55rem;
            flex-wrap: wrap;
        }

        .categories-modal-cancel {
            border-color: rgba(255, 255, 255, 0.24);
            background: transparent;
            color: #f4f4f4;
        }

        body.categories-modal-open {
            overflow: hidden;
        }
    </style>
</head>
<body>
    <main class="categories-shell">
        <div class="categories-top">
            <h1>Manage Categories</h1>
            <a href="../dashboard/">Back to Dashboard</a>
        </div>

        <p class="categories-note">Categories are used for recommendations and are visible on the customer product page. Removing a category reassigns its products to the first remaining category.</p>

        <?php if ($flashType !== '' && $flashMessage !== ''): ?>
            <div class="categories-flash <?php echo htmlspecialchars('categories-flash-' . $flashType, ENT_QUOTES, 'UTF-8'); ?>" role="status">
                <?php echo htmlspecialchars($flashMessage, ENT_QUOTES, 'UTF-8'); ?>
            </div>
        <?php endif; ?>

        <form method="post" action="" class="categories-add-form">
            <input type="hidden" name="category_action" value="add_category">
            <div class="categories-field">
                <label for="new-category-name">Add New Category</label>
                <input id="new-category-name" name="new_category" type="text" maxlength="60" required>
            </div>
            <button class="categories-button" type="submit">Add Category</button>
        </form>

        <section class="categories-grid" aria-label="Category records">
            <?php foreach ($categoryMap as $categoryValue => $categoryLabel): ?>
                <?php
                    $categoryStats = $usage[$categoryValue] ?? ['active' => 0, 'archived' => 0];
                    $activeCount = (int) ($categoryStats['active'] ?? 0);
                    $archivedCount = (int) ($categoryStats['archived'] ?? 0);
                ?>
                <article class="category-card">
                    <h2><?php echo htmlspecialchars($categoryLabel, ENT_QUOTES, 'UTF-8'); ?></h2>
                    <p class="category-meta">Active products: <?php echo htmlspecialchars((string) $activeCount, ENT_QUOTES, 'UTF-8'); ?></p>
                    <p class="category-meta">Archived products: <?php echo htmlspecialchars((string) $archivedCount, ENT_QUOTES, 'UTF-8'); ?></p>

                    <form method="post" action="" class="category-inline-form" data-category-rename-form data-category-label="<?php echo htmlspecialchars($categoryLabel, ENT_QUOTES, 'UTF-8'); ?>">
                        <input type="hidden" name="category_action" value="rename_category">
                        <input type="hidden" name="category_value" value="<?php echo htmlspecialchars($categoryValue, ENT_QUOTES, 'UTF-8'); ?>">
                        <input name="new_category" type="text" maxlength="60" value="<?php echo htmlspecialchars($categoryLabel, ENT_QUOTES, 'UTF-8'); ?>" required>
                        <div class="category-card-actions">
                            <button class="categories-button" type="submit">Rename Category</button>
                        </div>
                    </form>

                    <form method="post" action="" data-category-remove-form data-category-label="<?php echo htmlspecialchars($categoryLabel, ENT_QUOTES, 'UTF-8'); ?>">
                        <input type="hidden" name="category_action" value="remove_category">
                        <input type="hidden" name="category_value" value="<?php echo htmlspecialchars($categoryValue, ENT_QUOTES, 'UTF-8'); ?>">
                        <div class="category-card-actions">
                            <button class="categories-button categories-button-danger" type="submit"<?php echo $canRemoveCategories ? '' : ' disabled'; ?>>Remove Category</button>
                            <?php if (!$canRemoveCategories): ?>
                                <p class="category-remove-note">At least one category must remain.</p>
                            <?php endif; ?>
                        </div>
                    </form>
                </article>
            <?php endforeach; ?>
        </section>
    </main>

    <div class="categories-modal-backdrop" data-categories-modal-backdrop hidden>
        <section class="categories-modal" role="dialog" aria-modal="true" aria-labelledby="categories-modal-title" aria-describedby="categories-modal-message">
            <div class="categories-modal-head">
                <h2 id="categories-modal-title" data-categories-modal-title>Confirm Action</h2>
                <button class="categories-modal-close" type="button" data-categories-modal-cancel aria-label="Close">&times;</button>
            </div>

            <p class="categories-modal-message" id="categories-modal-message" data-categories-modal-message>Are you sure you want to continue?</p>

            <div class="categories-modal-actions">
                <button class="categories-button categories-modal-cancel" type="button" data-categories-modal-cancel>Cancel</button>
                <button class="categories-button categories-button-danger" type="button" data-categories-modal-confirm>Proceed</button>
            </div>
        </section>
    </div>

    <script>
    (function () {
        var modalBackdrop = document.querySelector('[data-categories-modal-backdrop]');
        var modalTitle = document.querySelector('[data-categories-modal-title]');
        var modalMessage = document.querySelector('[data-categories-modal-message]');
        var modalConfirm = document.querySelector('[data-categories-modal-confirm]');
        var modalCancelButtons = document.querySelectorAll('[data-categories-modal-cancel]');
        var pendingForm = null;

        function closeModal() {
            pendingForm = null;

            if (!modalBackdrop) {
                return;
            }

            modalBackdrop.hidden = true;
            document.body.classList.remove('categories-modal-open');
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
            document.body.classList.add('categories-modal-open');
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

        document.querySelectorAll('[data-category-remove-form]').forEach(function (form) {
            form.addEventListener('submit', function (event) {
                var categoryLabel = form.getAttribute('data-category-label') || 'this category';
                event.preventDefault();

                openModal({
                    form: form,
                    title: 'Remove Category',
                    message: 'Removing "' + categoryLabel + '" will reassign existing products to another category. Proceed or Cancel?',
                    confirmLabel: 'Proceed'
                });
            });
        });

        document.querySelectorAll('[data-category-rename-form]').forEach(function (form) {
            form.addEventListener('submit', function (event) {
                var categoryLabel = form.getAttribute('data-category-label') || 'this category';
                var renameInput = form.querySelector('input[name="new_category"]');
                var nextLabel = renameInput ? String(renameInput.value || '').trim() : '';
                var warning = 'Renaming "' + categoryLabel + '" will affect all products using it. Proceed or Cancel?';

                if (nextLabel !== '' && nextLabel.toLowerCase() !== categoryLabel.toLowerCase()) {
                    warning = 'Renaming "' + categoryLabel + '" to "' + nextLabel + '" will affect all products using it. Proceed or Cancel?';
                }

                event.preventDefault();

                openModal({
                    form: form,
                    title: 'Rename Category',
                    message: warning,
                    confirmLabel: 'Proceed'
                });
            });
        });
    })();
    </script>
</body>
</html>
