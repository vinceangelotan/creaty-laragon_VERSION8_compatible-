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

$productKey = trim((string) ($payload['productKey'] ?? ''));
if ($productKey === '') {
    http_response_code(422);
    echo json_encode(['ok' => false, 'message' => 'Product key is required']);
    exit;
}

$projectRoot = dirname(__DIR__, 2);

try {
    // Hold the same lock as booking submission until every archive write finishes.
    $archiveMutationLock = customer_orders_repository_acquire_lock();
    if ($archiveMutationLock === null) {
        http_response_code(503);
        echo json_encode(['ok' => false, 'message' => 'Inventory is busy. Please try archiving this product again.']);
        exit;
    }

    $products = load_products_repository();
    if (!isset($products[$productKey]) || !is_array($products[$productKey])) {
        http_response_code(404);
        echo json_encode(['ok' => false, 'message' => 'Product was not found. Refresh the dashboard before trying again.']);
        exit;
    }

    $orders = load_customer_orders_repository();
    $equipmentInventory = sync_equipment_inventory_with_products(
        $products,
        load_equipment_inventory_repository(),
        12
    );
    $archivedEquipmentUnits = load_archived_equipment_units_repository();

    foreach ((array) ($equipmentInventory[$productKey]['units'] ?? []) as $unit) {
        if (normalize_equipment_status_token($unit['status'] ?? '') === 'in-use') {
            http_response_code(409);
            echo json_encode(['ok' => false, 'message' => 'This product has a unit in use. Complete its return before archiving the product.']);
            exit;
        }
    }

    $reservationIntervals = customer_order_group_intervals_by_product(
        customer_order_build_camera_reservation_intervals($orders, $products)
    );
    $normalizedProductKey = normalize_customer_order_product_key($productKey);
    if (customer_order_peak_reserved_quantity($reservationIntervals[$normalizedProductKey] ?? [], time()) > 0) {
        http_response_code(409);
        echo json_encode(['ok' => false, 'message' => 'This product still has active or upcoming reservations. Resolve those bookings in Reservations before archiving it.']);
        exit;
    }

    $archiveInventoryResult = archive_all_equipment_units_for_product(
        $equipmentInventory,
        $archivedEquipmentUnits,
        $products,
        $productKey,
        'Featured product archived from admin dashboard card'
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

    $result = archive_product_record($products, $productKey, $projectRoot);
    $archiveKey = trim((string) ($result['archivedEntry']['archiveKey'] ?? ''));

    if ($archiveKey !== '') {
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

            $archivedUnitEntry['productArchiveKey'] = $archiveKey;
        }
        unset($archivedUnitEntry);
    }

    if (!save_archived_products_repository($result['archivedProducts'])) {
        throw new RuntimeException('Unable to save archive data.');
    }

    if (!save_products_repository($result['products'])) {
        throw new RuntimeException('Unable to update active products.');
    }

    if (!save_archived_equipment_units_repository($archivedEquipmentUnits)) {
        throw new RuntimeException('Unable to save removed equipment units.');
    }

    $equipmentInventory = sync_equipment_inventory_with_products(
        $result['products'],
        $equipmentInventory,
        12
    );

    if (!save_equipment_inventory_repository($equipmentInventory)) {
        throw new RuntimeException('Unable to update equipment inventory.');
    }

    echo json_encode([
        'ok' => true,
        'archivedEntry' => $result['archivedEntry'],
        'archivedEquipmentCount' => count($archiveInventoryResult['archivedEntries'])
    ]);
} catch (Throwable $error) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'message' => $error->getMessage()
    ]);
}
