<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

header('Content-Type: application/json; charset=UTF-8');

function customer_notifications_mark_read_respond(int $statusCode, array $payload): void
{
    http_response_code($statusCode);
    echo json_encode($payload, JSON_UNESCAPED_SLASHES);
    exit;
}

if (!isset($_SESSION['customer_id']) || isset($_SESSION['user_id'])) {
    customer_notifications_mark_read_respond(401, [
        'ok' => false,
        'message' => 'Unauthorized.',
    ]);
}

if (strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    customer_notifications_mark_read_respond(405, [
        'ok' => false,
        'message' => 'Method not allowed.',
    ]);
}

require_once __DIR__ . '/config/customer_notifications_repository.php';
require_once __DIR__ . '/config/customer_orders_repository.php';

// Status notifications are appended under the booking lock. Keep the complete
// mark-read operation under that same lock so it cannot erase a new notice.
$notificationMutationLock = customer_orders_repository_acquire_lock();
if ($notificationMutationLock === null) {
    customer_notifications_mark_read_respond(503, [
        'ok' => false,
        'message' => 'Notifications are busy. Please try again.',
    ]);
}

$customerId = trim((string) ($_SESSION['customer_id'] ?? ''));

if ($customerId === '') {
    customer_notifications_mark_read_respond(401, [
        'ok' => false,
        'message' => 'Unauthorized.',
    ]);
}

$rawBody = file_get_contents('php://input');
$decodedBody = json_decode((string) $rawBody, true);

$markAllOrderNotificationsRaw = is_array($decodedBody)
    ? ($decodedBody['markAllOrderNotifications'] ?? '')
    : ($_POST['markAllOrderNotifications'] ?? '');
$markAllOrderNotifications = false;

if (is_bool($markAllOrderNotificationsRaw)) {
    $markAllOrderNotifications = $markAllOrderNotificationsRaw;
} else {
    $markAllOrderNotifications = in_array(
        strtolower(trim((string) $markAllOrderNotificationsRaw)),
        ['1', 'true', 'yes', 'on'],
        true
    );
}

if ($markAllOrderNotifications) {
    $markResult = mark_customer_notifications_as_read_by_type_for_customer($customerId, 'order-status');
    $notifications = $markResult['notifications'] ?? null;

    if (!is_array($notifications)) {
        customer_notifications_mark_read_respond(500, [
            'ok' => false,
            'message' => 'Unable to process notifications.',
        ]);
    }

    if (!empty($markResult['changed'])) {
        if (!save_customer_notifications_repository($notifications)) {
            customer_notifications_mark_read_respond(500, [
                'ok' => false,
                'message' => 'Unable to save read state.',
            ]);
        }
    }

    customer_notifications_mark_read_respond(200, [
        'ok' => true,
        'changed' => (bool) ($markResult['changed'] ?? false),
        'markMode' => 'order-status-bulk',
        'updatedCount' => (int) ($markResult['updatedCount'] ?? 0),
        'updatedNotificationIds' => array_values($markResult['updatedNotificationIds'] ?? []),
        'unreadCount' => count_unread_customer_notifications_for_customer($customerId, $notifications),
    ]);
}

$notificationId = trim((string) (
    (is_array($decodedBody) ? ($decodedBody['notificationId'] ?? '') : '')
    ?: ($_POST['notificationId'] ?? '')
));

if ($notificationId === '') {
    customer_notifications_mark_read_respond(422, [
        'ok' => false,
        'message' => 'Notification id is required.',
    ]);
}

$markResult = mark_customer_notification_as_read_for_customer($customerId, $notificationId);
$notifications = $markResult['notifications'] ?? null;

if (!is_array($notifications)) {
    customer_notifications_mark_read_respond(500, [
        'ok' => false,
        'message' => 'Unable to process notifications.',
    ]);
}

if (!empty($markResult['changed'])) {
    if (!save_customer_notifications_repository($notifications)) {
        customer_notifications_mark_read_respond(500, [
            'ok' => false,
            'message' => 'Unable to save read state.',
        ]);
    }
}

if (($markResult['updated'] ?? null) === null) {
    customer_notifications_mark_read_respond(404, [
        'ok' => false,
        'message' => 'Notification not found.',
        'unreadCount' => count_unread_customer_notifications_for_customer($customerId, $notifications),
    ]);
}

customer_notifications_mark_read_respond(200, [
    'ok' => true,
    'changed' => (bool) ($markResult['changed'] ?? false),
    'notificationId' => $notificationId,
    'unreadCount' => count_unread_customer_notifications_for_customer($customerId, $notifications),
]);
