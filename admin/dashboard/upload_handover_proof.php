<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

header('Content-Type: application/json; charset=UTF-8');

function upload_handover_proof_respond(int $statusCode, array $payload): void
{
    http_response_code($statusCode);
    echo json_encode($payload, JSON_UNESCAPED_SLASHES);
    exit;
}

if (strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    upload_handover_proof_respond(405, [
        'ok' => false,
        'message' => 'Method not allowed.',
    ]);
}

$isAdminSession = isset($_SESSION['user_id']) && !isset($_SESSION['customer_id']);
$isStaffSession = isset($_SESSION['staff_id']) && !isset($_SESSION['customer_id']);

if (!$isAdminSession && !$isStaffSession) {
    upload_handover_proof_respond(401, [
        'ok' => false,
        'message' => 'Please log in to upload handover photos.',
    ]);
}

require_once dirname(__DIR__, 2) . '/config/customer_orders_repository.php';
require_once dirname(__DIR__, 2) . '/config/handover_proofs_repository.php';

$rawBody = file_get_contents('php://input');
$decodedBody = json_decode((string) $rawBody, true);
$payload = is_array($decodedBody) ? $decodedBody : [];

$submittedToken = (string) ($payload['bookingActionToken'] ?? ($_POST['booking_action_token'] ?? ''));
$expectedToken = (string) ($_SESSION['booking_action_token'] ?? '');

if ($expectedToken === '' || !hash_equals($expectedToken, $submittedToken)) {
    upload_handover_proof_respond(403, [
        'ok' => false,
        'message' => 'Your session changed. Refresh the reservations page and try again.',
    ]);
}

$orderId = trim((string) ($payload['orderId'] ?? ($_POST['order_id'] ?? '')));
$imageDataUrl = trim((string) ($payload['imageDataUrl'] ?? ($_POST['image_data_url'] ?? '')));

if ($orderId === '') {
    upload_handover_proof_respond(422, [
        'ok' => false,
        'message' => 'Order ID is required.',
    ]);
}

if ($imageDataUrl === '') {
    upload_handover_proof_respond(422, [
        'ok' => false,
        'message' => 'Please select a handover photo to upload.',
    ]);
}

if (!preg_match('/^data:image\/(png|jpe?g|webp);base64,/i', $imageDataUrl)) {
    upload_handover_proof_respond(422, [
        'ok' => false,
        'message' => 'Please upload a PNG, JPG or WEBP image.',
    ]);
}

try {
    $updatedOrder = upload_handover_proof_by_admin(
        $orderId,
        $imageDataUrl,
        dirname(__DIR__, 2),
        $isStaffSession ? 'staff' : 'admin'
    );
} catch (Throwable $error) {
    upload_handover_proof_respond(500, [
        'ok' => false,
        'message' => 'Handover photo upload failed due to a technical issue. Please try again.',
    ]);
}

if ($updatedOrder === null) {
    upload_handover_proof_respond(409, [
        'ok' => false,
        'message' => 'This reservation is not eligible for a handover photo (it must be approved, pick-up or meet-up, and not yet handed over).',
    ]);
}

upload_handover_proof_respond(200, [
    'ok' => true,
    'message' => 'Handover photo uploaded successfully.',
    'handoverProof' => [
        'path' => (string) ($updatedOrder['path'] ?? ''),
        'uploadedAt' => (string) ($updatedOrder['uploaded_at'] ?? ''),
        'uploadedBy' => (string) ($updatedOrder['uploaded_by'] ?? ''),
    ],
]);
