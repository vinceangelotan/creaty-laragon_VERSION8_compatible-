<?php

// Optional scheduler entry point. Never expose lifecycle maintenance over HTTP.
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('This command is available from the command line only.');
}

require_once dirname(__DIR__) . '/config/customer_orders_repository.php';

$repositoryLock = customer_orders_repository_acquire_lock();
if ($repositoryLock === null) {
    fwrite(STDERR, "Unable to acquire the booking repository lock.\n");
    exit(1);
}

try {
    $orders = load_customer_orders_repository();
    fwrite(STDOUT, 'Processed lifecycle state for ' . count($orders) . ' bookings at ' . customer_order_now_iso8601() . PHP_EOL);
} catch (Throwable $error) {
    fwrite(STDERR, $error->getMessage() . PHP_EOL);
    exit(1);
}
