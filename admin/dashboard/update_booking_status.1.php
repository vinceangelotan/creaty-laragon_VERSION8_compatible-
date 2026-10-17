<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$isAdminSession = isset($_SESSION['user_id']) && !isset($_SESSION['customer_id']);
$isStaffSession = isset($_SESSION['staff_id']) && !isset($_SESSION['customer_id']);

if (!$isAdminSession && !$isStaffSession) {
    header('Location: ../');
    exit;
}

$dashboardActor = $isStaffSession ? 'staff' : 'admin';

if (strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    header('Location: ./?admin_view=bookings');
    exit;
}

require_once dirname(__DIR__, 2) . '/config/customer_orders_repository.php';
require_once dirname(__DIR__, 2) . '/config/booking_reason_options.php';

function booking_action_redirect($message, $type = 'error')
{
    $_SESSION['admin_booking_flash'] = ['message' => $message, 'type' => $type];
    header('Location: ./?admin_view=bookings');
    exit;
}

$submittedToken = (string) ($_POST['booking_action_token'] ?? '');
$expectedToken = (string) ($_SESSION['booking_action_token'] ?? '');
if ($expectedToken === '' || !hash_equals($expectedToken, $submittedToken)) {
    booking_action_redirect('Your session changed. Refresh the reservations page and try again.');
}

$orderId = trim((string) ($_POST['order_id'] ?? ''));
$nextStatus = trim((string) ($_POST['next_status'] ?? 'pending'));
$cancelReason = trim((string) ($_POST['cancel_reason'] ?? ''));
$refundProofDataUrl = trim((string) ($_POST['refund_proof_data_url'] ?? ''));
$redirectTarget = './?admin_view=bookings';

// Every rejected transition has visible feedback instead of a silent reload.
$_SESSION['admin_booking_flash'] = [
    'type' => 'error',
    'message' => 'This booking changed or the action is no longer available. Review its current status and try again.',
];

if ($orderId === '') {
    header('Location: ' . $redirectTarget);
    exit;
}

$handoverMode = customer_order_handover_mode_from_action($nextStatus);
$nextStatusToken = $handoverMode === ''
    ? normalize_customer_order_status_token($nextStatus)
    : '';
$isReturnedEarlyAction = $handoverMode === '' && customer_order_is_returned_early_request($nextStatus);

if ($handoverMode === '' && customer_order_status_requires_reason($nextStatusToken)) {
    $cancelReason = resolve_booking_reason($nextStatusToken, $_POST['reason_choice'] ?? '', $cancelReason);
    if ($cancelReason === '') {
        booking_action_redirect('Select a reason, or choose Others and enter an explanation.');
    }
}

if ($handoverMode === '' && $nextStatusToken === 'awaiting-refund') {
    header('Location: ' . $redirectTarget);
    exit;
}

if ($handoverMode === '' && customer_order_status_requires_reason($nextStatusToken) && $cancelReason === '') {
    header('Location: ' . $redirectTarget);
    exit;
}

if ($handoverMode === '' && $nextStatusToken === 'refunded' && strpos($refundProofDataUrl, 'data:image/') !== 0) {
    booking_action_redirect('Upload the refund receipt image before confirming a refund.');
}

$currentOrder = find_customer_order_record_by_id($orderId);

if (!is_array($currentOrder)) {
    header('Location: ' . $redirectTarget);
    exit;
}

$currentStatusToken = normalize_customer_order_status_token($currentOrder['status'] ?? 'pending');
if (customer_order_is_terminal_status($currentStatusToken)) {
    header('Location: ' . $redirectTarget);
    exit;
}

if ($handoverMode !== '') {
    $updatedOrder = confirm_customer_order_receive_handover_by_id($orderId, $handoverMode, $dashboardActor);

    if ($updatedOrder === null) {
        header('Location: ' . $redirectTarget);
        exit;
    }

    booking_action_redirect('Equipment handover confirmed.', 'success');
}

if ($currentStatusToken === 'awaiting-refund' && $nextStatusToken !== 'refunded') {
    header('Location: ' . $redirectTarget);
    exit;
}

$isWaitingForPaymentReceipt = customer_order_requires_payment_receipt($currentOrder);
if ($isWaitingForPaymentReceipt && $nextStatusToken !== 'canceled') {
    header('Location: ' . $redirectTarget);
    exit;
}

$isWaitingForPaymentReview = customer_order_requires_payment_review($currentOrder);
if ($isWaitingForPaymentReview && !in_array($nextStatusToken, ['approved', 'rejected', 'refunded', 'canceled'], true)) {
    header('Location: ' . $redirectTarget);
    exit;
}

if (
    $currentStatusToken === 'pending'
    && !$isWaitingForPaymentReceipt
    && !$isWaitingForPaymentReview
    && !in_array($nextStatusToken, ['approved', 'canceled'], true)
) {
    header('Location: ' . $redirectTarget);
    exit;
}

if ($currentStatusToken === 'approved' && $nextStatusToken !== 'canceled') {
    header('Location: ' . $redirectTarget);
    exit;
}

if ($currentStatusToken === 'ongoing' && (!$isReturnedEarlyAction || $nextStatusToken !== 'completed')) {
    header('Location: ' . $redirectTarget);
    exit;
}

if ($currentStatusToken === 'return' && $nextStatusToken !== 'completed') {
    header('Location: ' . $redirectTarget);
    exit;
}

if ($nextStatusToken === 'completed') {
    if ($isReturnedEarlyAction && $currentStatusToken !== 'ongoing') {
        header('Location: ' . $redirectTarget);
        exit;
    }

    if (!$isReturnedEarlyAction && $currentStatusToken !== 'return') {
        header('Location: ' . $redirectTarget);
        exit;
    }
}

$updatedOrder = update_customer_order_status_by_id(
    $orderId,
    $nextStatus,
    customer_order_status_requires_reason($nextStatusToken) ? $cancelReason : '',
    customer_order_status_requires_reason($nextStatusToken) ? $dashboardActor : '',
    [
        'refund_proof_data_url' => $nextStatusToken === 'refunded' ? $refundProofDataUrl : '',
        'project_root' => dirname(__DIR__, 2),
    ]
);

if ($updatedOrder === null) {
    header('Location: ' . $redirectTarget);
    exit;
}

booking_action_redirect('Booking status updated successfully.', 'success');
