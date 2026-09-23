<?php

// Run manually with Laragon's PHP CLI. All repository writes use disposable
// fixtures under the OS temporary directory; application JSON is never loaded.
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Run this regression test from the command line.');
}

$fixtureRoot = sys_get_temp_dir() . '/creaty-booking-test-' . bin2hex(random_bytes(8));
$fixtureConfig = $fixtureRoot . '/config';
if (!mkdir($fixtureConfig, 0700, true)) {
    throw new RuntimeException('Unable to create isolated test fixtures.');
}

function booking_test_assert($condition, $message)
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function booking_test_write_json($path, $payload)
{
    $encoded = json_encode($payload, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
    booking_test_assert(file_put_contents($path, $encoded) !== false, 'Unable to write fixture.');
}

try {
    foreach ([
        'customer_orders_repository.php',
        'customer_notifications_repository.php',
        'message_notifications_repository.php',
        'products_repository.php',
        'equipment_inventory_repository.php',
        'event_packages_repository.php',
        'services_packages_repository.php',
    ] as $file) {
        booking_test_assert(copy(dirname(__DIR__) . '/config/' . $file, $fixtureConfig . '/' . $file), 'Unable to copy repository code.');
    }

    // Inject an inventory-only write failure into the disposable repository copy.
    // Order and notification persistence continue through their real functions.
    $inventoryCode = str_replace("\r\n", "\n", file_get_contents($fixtureConfig . '/equipment_inventory_repository.php'));
    $saveHeader = 'function save_equipment_inventory_repository($inventory)' . "\n{";
    $failureGuard = <<<'PHP'

    if (!empty($GLOBALS['booking_test_fail_inventory_save'])) {
        return false;
    }
PHP;
    $inventoryCode = str_replace($saveHeader, $saveHeader . $failureGuard, $inventoryCode, $injectionCount);
    booking_test_assert($injectionCount === 1, 'Inventory failure injection must target exactly one fixture function.');
    booking_test_assert(file_put_contents($fixtureConfig . '/equipment_inventory_repository.php', $inventoryCode) !== false, 'Unable to prepare the failure fixture.');

    require $fixtureConfig . '/customer_orders_repository.php';
    booking_test_assert(realpath(dirname(customer_orders_repository_path())) === realpath($fixtureConfig), 'Repository must use the isolated fixture.');

    $products = ['test-camera' => ['name' => 'Test Camera', 'model' => 'Test Camera', 'brand' => 'Test']];
    $inventory = ['test-camera' => ['nextSerial' => 2, 'timesUsed' => 0, 'units' => [['serial' => 1, 'status' => 'available']]]];
    booking_test_write_json($fixtureConfig . '/products.json', $products);
    booking_test_write_json($fixtureConfig . '/equipment_inventory.json', $inventory);
    booking_test_write_json($fixtureConfig . '/customer_orders.json', []);
    booking_test_write_json($fixtureConfig . '/customer_notifications.json', []);
    booking_test_write_json($fixtureConfig . '/message_notifications.json', []);

    $item = ['product_key' => 'test-camera', 'name' => 'Test Camera', 'qty' => 1, 'days' => 1];
    $base = [
        'id' => 'expired-cash', 'customer_id' => 'customer-test', 'status' => 'pending',
        'receive_date' => '2026-09-24', 'receive_time' => '09:00',
        'return_date' => '2026-09-25', 'return_time' => '09:00',
        'receiving_method' => 'pickup', 'returning_method' => 'pickup',
        'payment_method' => 'cash-pickup', 'items' => [$item],
        'created_at' => '2026-09-23T09:00:00+08:00',
    ];
    $deadline = customer_order_schedule_timestamp('2026-09-24', '09:00');
    $paid = array_replace($base, ['id' => 'expired-paid', 'payment_method' => 'gcash', 'payment_receipt_path' => 'uploads/test-receipt.png']);
    $approved = array_replace($base, ['id' => 'approved', 'status' => 'approved']);
    $invalid = array_replace($base, ['id' => 'invalid', 'receive_date' => 'invalid']);
    $before = expire_customer_orders_pending_by_receiving_schedule([$base], $changed, $deadline - 1, $expired);
    booking_test_assert(!$changed && count($expired) === 0 && $before[0]['status'] === 'pending', 'Pending bookings must survive until the receiving time.');

    $expired = [];
    $result = expire_customer_orders_pending_by_receiving_schedule([$base, $paid, $approved, $invalid], $changed, $deadline, $expired);
    booking_test_assert($changed && count($expired) === 2, 'Exactly the two overdue pending bookings should expire.');
    booking_test_assert(normalize_customer_order_status_token($result[0]['status']) === 'canceled', 'Expired cash bookings should cancel.');
    booking_test_assert($result[0]['canceled_by'] === 'system' && $result[0]['cancel_reason'] !== '', 'Automatic cancellation needs an audit reason and actor.');
    booking_test_assert(normalize_customer_order_status_token($result[1]['status']) === 'awaiting-refund', 'A submitted receipt must keep the refund workflow.');
    booking_test_assert($result[1]['payment_receipt_path'] === $paid['payment_receipt_path'], 'Expiry must preserve payment evidence.');
    booking_test_assert($result[2]['status'] === 'approved' && $result[3]['status'] === 'pending', 'Approved and unscheduled records must not be canceled.');
    expire_customer_orders_pending_by_receiving_schedule($result, $changed, $deadline + 1, $expired);
    booking_test_assert(!$changed && count($expired) === 2, 'Expiry must be idempotent.');

    $missing = array_replace($base, ['payment_method' => 'gcash']);
    $receiptDeadline = customer_order_payment_receipt_deadline_timestamp($missing);
    $missingResult = expire_customer_orders_missing_payment_receipts([$missing], $changed, $receiptDeadline, $receiptExpired);
    booking_test_assert($changed && normalize_customer_order_status_token($missingResult[0]['status']) === 'canceled', 'The existing missing-receipt deadline must remain active.');
    booking_test_assert(customer_order_payment_receipt_deadline_timestamp($paid) === null, 'Submitted receipts must not trigger the missing-receipt deadline.');
    booking_test_assert(normalize_customer_order_canceled_by('staff') === 'staff', 'Staff cancellation attribution must be retained.');

    $intervals = customer_order_build_camera_reservation_intervals([$base], $products);
    $grouped = customer_order_group_intervals_by_product($intervals);
    $requirements = ['test-camera' => ['qty' => 1, 'days' => 1]];
    booking_test_assert(!customer_order_requirements_fit_schedule($requirements, $deadline, $grouped, ['test-camera' => 1], '', $failure), 'Pending orders must reserve the last unit.');
    booking_test_assert(customer_order_requirements_fit_schedule($requirements, $deadline, $grouped, ['test-camera' => 2], '', $failure), 'An unreserved second unit should remain bookable.');
    booking_test_assert(customer_order_requirements_fit_schedule($requirements, $deadline + 86400, $grouped, ['test-camera' => 1], '', $failure), 'Adjacent bookings should not overlap.');
    booking_test_assert(customer_order_build_camera_reservation_intervals([$result[0], $result[1]], $products) === [], 'Canceled/refund bookings should release inventory.');

    $sequential = [
        ['order_id' => 'first', 'start_ts' => $deadline, 'end_ts' => $deadline + 3600, 'qty' => 1],
        ['order_id' => 'second', 'start_ts' => $deadline + 3600, 'end_ts' => $deadline + 7200, 'qty' => 1],
    ];
    booking_test_assert(customer_order_peak_reserved_quantity($sequential) === 1, 'Sequential reservations must count by peak usage.');
    booking_test_assert(customer_order_requirements_fit_schedule($requirements, $deadline, ['test-camera' => $sequential], ['test-camera' => 2], '', $failure, $deadline + 7200), 'A longer booking must fit across sequential reservations when one unit stays free.');
    $overlapping = $sequential;
    $overlapping[1]['start_ts'] = $deadline + 1800;
    booking_test_assert(customer_order_peak_reserved_quantity($overlapping) === 2, 'Simultaneous reservations must count together.');
    booking_test_assert(customer_order_peak_reserved_quantity($overlapping, $deadline + 4000, null) === 1, 'Historical reservations should not block future capacity changes.');
    booking_test_assert(customer_order_peak_reserved_quantity($overlapping, null, null, 'second') === 1, 'An edited booking can exclude its own reservation.');

    // Exercise the actual load path, persistence, notifications, and duplicate reads.
    $pastDate = customer_order_datetime_from_timestamp()->modify('-2 days')->format('Y-m-d');
    booking_test_write_json($fixtureConfig . '/customer_orders.json', [
        array_replace($base, ['receive_date' => $pastDate]),
        array_replace($paid, ['receive_date' => $pastDate]),
    ]);
    $loaded = load_customer_orders_repository();
    booking_test_assert(normalize_customer_order_status_token($loaded[0]['status']) === 'canceled', 'Reading orders must persist overdue cancellation.');
    booking_test_assert(normalize_customer_order_status_token($loaded[1]['status']) === 'awaiting-refund', 'Reading orders must persist refund review.');
    $notifications = load_customer_notifications_repository();
    booking_test_assert(count($notifications) === 2, 'Each expired booking needs one status notification.');
    load_customer_orders_repository();
    booking_test_assert(count(load_customer_notifications_repository()) === 2, 'Refreshing must not duplicate expiry notifications.');

    // A failed derived-inventory write must not hide an already committed expiry
    // or prevent its notification. A later read must retry the inventory sync.
    booking_test_write_json($fixtureConfig . '/customer_orders.json', [array_replace($paid, ['id' => 'inventory-write-failure', 'receive_date' => $pastDate])]);
    booking_test_write_json($fixtureConfig . '/customer_notifications.json', []);
    $staleInventory = $inventory;
    $staleInventory['test-camera']['units'][0]['status'] = 'in-use';
    booking_test_write_json($fixtureConfig . '/equipment_inventory.json', $staleInventory);
    $previousErrorLog = ini_get('error_log');
    ini_set('error_log', $fixtureRoot . '/sync-errors.log');
    $GLOBALS['booking_test_fail_inventory_save'] = true;
    try {
        $loaded = load_customer_orders_repository();
    } finally {
        $GLOBALS['booking_test_fail_inventory_save'] = false;
        ini_set('error_log', $previousErrorLog);
    }
    $persisted = json_decode(file_get_contents($fixtureConfig . '/customer_orders.json'), true);
    booking_test_assert(normalize_customer_order_status_token($loaded[0]['status']) === 'awaiting-refund', 'A derived inventory failure must not fail the committed expiry.');
    booking_test_assert(normalize_customer_order_status_token($persisted[0]['status']) === 'awaiting-refund', 'The paid expiry must be persisted even if inventory sync fails.');
    booking_test_assert(count(load_customer_notifications_repository()) === 1, 'A committed expiry must notify despite derived inventory failure.');
    booking_test_assert(strpos(file_get_contents($fixtureRoot . '/sync-errors.log'), 'synchronization will retry') !== false, 'Inventory synchronization failure must be logged.');
    booking_test_assert(load_equipment_inventory_repository()['test-camera']['units'][0]['status'] === 'in-use', 'The failure fixture must leave inventory unsynchronized.');
    load_customer_orders_repository();
    booking_test_assert(load_equipment_inventory_repository()['test-camera']['units'][0]['status'] === 'available', 'A later successful read must repair the derived inventory state.');
    booking_test_assert(count(load_customer_notifications_repository()) === 1, 'Retrying inventory synchronization must not duplicate the expiry notification.');

    // Two independent PHP processes contend for one remaining camera.
    booking_test_write_json($fixtureConfig . '/customer_orders.json', []);
    $futureDate = customer_order_datetime_from_timestamp()->modify('+3 days')->format('Y-m-d');
    $returnDate = customer_order_datetime_from_timestamp()->modify('+4 days')->format('Y-m-d');
    booking_test_write_json($fixtureRoot . '/payload.json', [
        'items' => [$item], 'receiveDate' => $futureDate, 'receiveTime' => '09:00',
        'returnDate' => $returnDate, 'returnTime' => '09:00',
        'receivingMethod' => 'pickup', 'returningMethod' => 'pickup', 'paymentMethod' => 'cash-pickup',
    ]);
    $workerSource = <<<'PHP'
<?php
require __DIR__ . '/config/customer_orders_repository.php';
$payload = json_decode(file_get_contents(__DIR__ . '/payload.json'), true);
file_put_contents(__DIR__ . '/ready-' . $argv[1], 'ready');
$order = append_customer_order_for_customer('worker-' . $argv[1], 'Test Customer', 'test@example.invalid', $payload, $error);
echo json_encode(['ok' => $order !== null, 'error' => $error]);
PHP;
    booking_test_assert(file_put_contents($fixtureRoot . '/worker.php', $workerSource) !== false, 'Unable to create concurrency worker.');
    booking_test_assert(function_exists('proc_open'), 'proc_open is required for the concurrent booking regression.');
    $guard = customer_orders_repository_acquire_lock();
    booking_test_assert($guard !== null, 'Unable to hold the booking transaction lock.');
    $workers = [];

    for ($index = 1; $index <= 2; $index++) {
        $process = proc_open([PHP_BINARY, $fixtureRoot . '/worker.php', (string) $index], [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
        booking_test_assert(is_resource($process), 'Unable to start concurrent booking worker.');
        fclose($pipes[0]);
        $workers[] = ['process' => $process, 'stdout' => $pipes[1], 'stderr' => $pipes[2]];
    }

    $waitUntil = microtime(true) + 10;
    while ((!is_file($fixtureRoot . '/ready-1') || !is_file($fixtureRoot . '/ready-2')) && microtime(true) < $waitUntil) {
        usleep(10000);
        clearstatcache();
    }
    $bothReady = is_file($fixtureRoot . '/ready-1') && is_file($fixtureRoot . '/ready-2');
    unset($guard);
    booking_test_assert($bothReady, 'Both workers should reach the transaction boundary.');

    $successes = 0;
    foreach ($workers as $worker) {
        $output = stream_get_contents($worker['stdout']);
        $errors = stream_get_contents($worker['stderr']);
        fclose($worker['stdout']);
        fclose($worker['stderr']);
        booking_test_assert(proc_close($worker['process']) === 0, 'Concurrency worker failed: ' . $errors);
        $response = json_decode($output, true);
        booking_test_assert(is_array($response), 'Concurrency worker returned invalid output: ' . $output . $errors);
        $successes += !empty($response['ok']) ? 1 : 0;
    }

    booking_test_assert($successes === 1, 'Exactly one concurrent request should reserve the last unit.');
    booking_test_assert(count(load_customer_orders_repository()) === 1, 'The accepted reservation must be persisted once.');

    file_put_contents($fixtureConfig . '/customer_orders.json', '{invalid-json');
    $readFailed = false;
    try {
        load_customer_orders_repository();
    } catch (RuntimeException $error) {
        $readFailed = true;
    }
    booking_test_assert($readFailed, 'Unreadable booking state must fail instead of making all cameras available.');
    booking_test_assert(file_get_contents($fixtureConfig . '/customer_orders.json') === '{invalid-json', 'Invalid booking data must not be overwritten.');
    echo "Booking lifecycle, refund preservation, pending capacity, and concurrent submission checks passed.\n";
} finally {
    // Only unlink files created inside this unique fixture; never recurse or touch
    // the real config directory, even when a test fails.
    unset($guard);
    foreach ($workers ?? [] as $worker) {
        if (is_resource($worker['process'])) {
            proc_terminate($worker['process']);
            foreach (['stdout', 'stderr'] as $stream) {
                if (is_resource($worker[$stream])) {
                    fclose($worker[$stream]);
                }
            }
            proc_close($worker['process']);
        }
    }

    foreach (glob($fixtureConfig . '/*') ?: [] as $file) {
        if (is_file($file)) {
            unlink($file);
        }
    }
    foreach (glob($fixtureRoot . '/*') ?: [] as $file) {
        if (is_file($file)) {
            unlink($file);
        }
    }
    rmdir($fixtureConfig);
    rmdir($fixtureRoot);
}
