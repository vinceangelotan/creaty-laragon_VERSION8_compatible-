<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

header('Content-Type: application/json; charset=UTF-8');

if (!isset($_SESSION['user_id']) || isset($_SESSION['customer_id'])) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'message' => 'Unauthorized']);
    exit;
}

if (strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'message' => 'Method not allowed']);
    exit;
}

require dirname(__DIR__, 2) . '/config/products_repository.php';
require dirname(__DIR__, 2) . '/config/equipment_inventory_repository.php';
require_once dirname(__DIR__, 2) . '/config/customer_orders_repository.php';

$payloadRaw = file_get_contents('php://input');
$payload = json_decode((string) $payloadRaw, true);
if (!is_array($payload)) {
    $payload = $_POST;
}

$archiveKey = trim((string) ($payload['archiveKey'] ?? ''));
if ($archiveKey === '') {
    http_response_code(422);
    echo json_encode(['ok' => false, 'message' => 'Archive key is required']);
    exit;
}

$projectRoot = dirname(__DIR__, 2);

try {
    $restoreMutationLock = customer_orders_repository_acquire_lock();
    if ($restoreMutationLock === null) {
        http_response_code(503);
        echo json_encode(['ok' => false, 'message' => 'Inventory is busy. Please try restoring this product again.']);
        exit;
    }

    $products = load_products_repository();
    $archivedProducts = load_archived_products_repository();
    // Lifecycle reconciliation may update in-use statuses; read inventory afterward.
    load_customer_orders_repository();
    $equipmentInventory = sync_equipment_inventory_with_products(
        $products,
        load_equipment_inventory_repository(),
        12
    );
    $archivedEquipmentUnits = load_archived_equipment_units_repository();

    $result = restore_archived_product_record($products, $archivedProducts, $archiveKey, $projectRoot);

    if (!save_products_repository($result['products'])) {
        throw new RuntimeException('Unable to save active products.');
    }

    $restoredBrand = normalize_product_brand($result['restoredProduct']['brand'] ?? default_product_brand());
    if (!ensure_product_brand_exists($restoredBrand)) {
        throw new RuntimeException('Unable to restore product brand availability.');
    }

    if (!save_archived_products_repository($result['archivedProducts'])) {
        throw new RuntimeException('Unable to save archive data.');
    }

    $restoredKey = trim((string) ($result['restoredKey'] ?? ''));
    $restoredOriginalKey = trim((string) ($result['restoredEntry']['originalKey'] ?? ''));

    if ($restoredOriginalKey !== '' && $restoredKey !== '' && $restoredOriginalKey !== $restoredKey) {
        foreach ($archivedEquipmentUnits as &$archivedUnitEntry) {
            if (!is_array($archivedUnitEntry)) {
                continue;
            }

            if (trim((string) ($archivedUnitEntry['productKey'] ?? '')) !== $restoredOriginalKey) {
                continue;
            }

            $archivedUnitEntry['productKey'] = $restoredKey;
        }
        unset($archivedUnitEntry);
    }

    $linkedUnitArchiveKeys = [];

    foreach ($archivedEquipmentUnits as $archivedUnitEntry) {
        if (!is_array($archivedUnitEntry)) {
            continue;
        }

        $unitArchiveKey = trim((string) ($archivedUnitEntry['archiveKey'] ?? ''));
        $unitProductArchiveKey = trim((string) ($archivedUnitEntry['productArchiveKey'] ?? ''));

        if ($unitArchiveKey === '' || $unitProductArchiveKey !== $archiveKey) {
            continue;
        }

        $linkedUnitArchiveKeys[] = $unitArchiveKey;
    }

    $restoredEquipmentCount = 0;

    foreach ($linkedUnitArchiveKeys as $linkedUnitArchiveKey) {
        try {
            $restoreUnitResult = restore_archived_equipment_unit(
                $equipmentInventory,
                $archivedEquipmentUnits,
                $result['products'],
                $linkedUnitArchiveKey
            );

            $equipmentInventory = $restoreUnitResult['inventory'];
            $archivedEquipmentUnits = $restoreUnitResult['archivedUnits'];
            $restoredEquipmentCount++;
        } catch (Throwable $ignoredRestoreError) {
            continue;
        }
    }

    $equipmentInventory = sync_equipment_inventory_with_products(
        $result['products'],
        $equipmentInventory,
        12
    );

    if (!save_archived_equipment_units_repository($archivedEquipmentUnits)) {
        throw new RuntimeException('Unable to save removed equipment units.');
    }

    if (!save_equipment_inventory_repository($equipmentInventory)) {
        throw new RuntimeException('Unable to save equipment inventory.');
    }

    echo json_encode([
        'ok' => true,
        'restoredKey' => $result['restoredKey'],
        'restoredProduct' => $result['restoredProduct'],
        'restoredEquipmentCount' => $restoredEquipmentCount
    ]);
} catch (Throwable $error) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'message' => $error->getMessage()
    ]);
}
