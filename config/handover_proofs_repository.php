<?php
/*
 * Handover photo proofs (photo taken with the customer when the equipment is handed over).
 *
 * Kept completely separate from payment receipts:
 *   - images are saved in   assets/handover_proofs/
 *   - records are saved in  config/handover_proofs.json  (one entry per order)
 * customer_orders.json and customer_orders_repository.php are not changed by this feature.
 */

function handover_proofs_repository_path()
{
    return __DIR__ . '/handover_proofs.json';
}

function handover_proofs_repository_acquire_lock()
{
    $handle = @fopen(handover_proofs_repository_path() . '.lock', 'c');

    if ($handle === false) {
        return null;
    }

    if (!flock($handle, LOCK_EX)) {
        fclose($handle);
        return null;
    }

    return $handle;
}

function handover_proofs_normalize_path($value)
{
    $path = str_replace('\\', '/', trim((string) $value));
    $path = ltrim($path, '/');

    if ($path === '' || strpos($path, '..') !== false || strpos($path, 'assets/handover_proofs/') !== 0) {
        return '';
    }

    if (!preg_match('/^[a-zA-Z0-9_\-\/.]+$/', $path)) {
        return '';
    }

    return $path;
}

function load_handover_proofs_repository()
{
    $path = handover_proofs_repository_path();

    if (!is_file($path)) {
        return [];
    }

    $raw = file_get_contents($path);
    $decoded = json_decode(preg_replace('/^\xEF\xBB\xBF/', '', (string) $raw), true);

    if (!is_array($decoded)) {
        return [];
    }

    $proofs = [];

    foreach ($decoded as $orderId => $record) {
        if (!is_array($record)) {
            continue;
        }

        $normalizedPath = handover_proofs_normalize_path($record['path'] ?? '');
        if ($normalizedPath === '') {
            continue;
        }

        $uploadedBy = strtolower(trim((string) ($record['uploaded_by'] ?? '')));

        $proofs[(string) $orderId] = [
            'order_id' => (string) $orderId,
            'path' => $normalizedPath,
            'uploaded_at' => trim((string) ($record['uploaded_at'] ?? '')),
            'uploaded_by' => in_array($uploadedBy, ['admin', 'staff'], true) ? $uploadedBy : 'admin',
        ];
    }

    return $proofs;
}

function save_handover_proofs_repository($proofs)
{
    $encoded = json_encode($proofs, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

    if ($encoded === false) {
        return false;
    }

    return file_put_contents(handover_proofs_repository_path(), $encoded, LOCK_EX) !== false;
}

function find_handover_proof_for_order($orderId)
{
    $targetOrderId = trim((string) $orderId);

    if ($targetOrderId === '') {
        return null;
    }

    $proofs = load_handover_proofs_repository();

    return $proofs[$targetOrderId] ?? null;
}

function save_handover_proof_image_from_data_url($imageDataUrl, $projectRoot, $orderId)
{
    $dataUrl = trim((string) $imageDataUrl);

    if (!preg_match('/^data:image\/(png|jpe?g|webp);base64,(.+)$/i', $dataUrl, $matches)) {
        throw new RuntimeException('Invalid handover photo payload.');
    }

    $binary = base64_decode((string) ($matches[2] ?? ''), true);
    if ($binary === false || $binary === '') {
        throw new RuntimeException('Invalid handover photo data.');
    }

    if (strlen($binary) > 10 * 1024 * 1024) {
        throw new RuntimeException('Handover photo is too large.');
    }

    $extensionRaw = strtolower((string) ($matches[1] ?? 'jpg'));
    $extension = $extensionRaw === 'jpeg' ? 'jpg' : $extensionRaw;

    $targetDirRelative = 'assets/handover_proofs';
    $targetDir = rtrim((string) $projectRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR
        . str_replace('/', DIRECTORY_SEPARATOR, $targetDirRelative);

    if (!is_dir($targetDir) && !mkdir($targetDir, 0777, true) && !is_dir($targetDir)) {
        throw new RuntimeException('Unable to access handover photo directory.');
    }

    $safeOrderId = strtolower(trim((string) preg_replace('/[^a-z0-9-]+/i', '-', (string) $orderId), '-'));
    if ($safeOrderId === '') {
        $safeOrderId = 'order';
    }

    $filename = $safeOrderId . '-handover.' . $extension;
    $absolutePath = $targetDir . DIRECTORY_SEPARATOR . $filename;

    if (file_put_contents($absolutePath, $binary, LOCK_EX) === false) {
        throw new RuntimeException('Unable to save handover photo.');
    }

    return $targetDirRelative . '/' . $filename;
}

/*
 * Saves the handover photo for an order. Returns the saved proof record, or null when the
 * order is not eligible (must be approved / ongoing / for return, pick-up or meet-up,
 * and the handover must not be confirmed yet).
 */
function upload_handover_proof_by_admin($orderId, $imageDataUrl, $projectRoot, $uploadedBy = 'admin')
{
    if (!function_exists('find_customer_order_record_by_id')) {
        require_once __DIR__ . '/customer_orders_repository.php';
    }

    $targetOrderId = trim((string) $orderId);
    $normalizedUploadedBy = strtolower(trim((string) $uploadedBy)) === 'staff' ? 'staff' : 'admin';

    if ($targetOrderId === '') {
        return null;
    }

    $order = find_customer_order_record_by_id($targetOrderId);
    if (!is_array($order)) {
        return null;
    }

    $statusToken = normalize_customer_order_status_token($order['status'] ?? 'pending');
    if (!in_array($statusToken, ['approved', 'ongoing', 'return'], true)) {
        return null;
    }

    $receivingMethod = normalize_customer_order_receiving_method($order['receiving_method'] ?? '');
    if (!in_array($receivingMethod, customer_order_handover_supported_receiving_methods(), true)) {
        return null;
    }

    if (normalize_customer_order_handover_confirmed_at($order['receive_handover_confirmed_at'] ?? '') !== '') {
        return null;
    }

    $lock = handover_proofs_repository_acquire_lock();
    if ($lock === null) {
        return null;
    }

    $proofs = load_handover_proofs_repository();
    $previousPath = isset($proofs[$targetOrderId]) ? (string) $proofs[$targetOrderId]['path'] : '';
    $newPath = save_handover_proof_image_from_data_url($imageDataUrl, $projectRoot, $targetOrderId);

    $proofs[$targetOrderId] = [
        'order_id' => $targetOrderId,
        'path' => $newPath,
        'uploaded_at' => customer_order_now_iso8601(),
        'uploaded_by' => $normalizedUploadedBy,
    ];

    if (!save_handover_proofs_repository($proofs)) {
        return null;
    }

    if ($previousPath !== '' && $previousPath !== $newPath) {
        $previousAbsolutePath = rtrim((string) $projectRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR
            . str_replace('/', DIRECTORY_SEPARATOR, $previousPath);

        if (is_file($previousAbsolutePath)) {
            @unlink($previousAbsolutePath);
        }
    }

    return $proofs[$targetOrderId];
}
