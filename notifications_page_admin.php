<?php
if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {
    header('Location: admin/notifications/');
    exit;
}

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$routeBase = $routeBase ?? 'admin/';
$assetBase = $assetBase ?? '';

$isAdminSession = isset($_SESSION['user_id']) && !isset($_SESSION['customer_id']);
$isStaffSession = isset($_SESSION['staff_id']) && !isset($_SESSION['customer_id']);

if (!$isAdminSession && !$isStaffSession) {
    header('Location: ' . $routeBase);
    exit;
}

$accountLabel = $isStaffSession ? 'STAFF' : 'Admin';
$homeLabel = $isStaffSession ? 'Staff Home' : 'Admin Home';
$adminHomePath = $routeBase . 'dashboard/';
$logoutPath = $routeBase . 'logout.php';
$notificationsPath = $routeBase . 'notifications/';
$bookingsPath = $routeBase . 'dashboard/?admin_view=bookings';
$markReadEndpoint = $routeBase . 'notifications/mark_read.php';
$liveUpdatesEndpoint = $routeBase . 'notifications/live_updates.php';

require_once __DIR__ . '/config/message_notifications_repository.php';

$notifications = load_message_notifications_repository();
$adminNotificationCount = count_unread_message_notifications($notifications);

$notificationTabOptions = ['inbox', 'orders'];
$requestedNotificationTab = strtolower(trim((string) ($_GET['tab'] ?? '')));
$savedNotificationTab = strtolower(trim((string) ($_COOKIE['creaty_admin_notifications_tab'] ?? '')));

if (in_array($requestedNotificationTab, $notificationTabOptions, true)) {
    $activeNotificationTab = $requestedNotificationTab;
} elseif (in_array($savedNotificationTab, $notificationTabOptions, true)) {
    $activeNotificationTab = $savedNotificationTab;
} else {
    $activeNotificationTab = 'inbox';
}

if (!headers_sent()) {
    $isSecureRequest = !empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off';
    setcookie('creaty_admin_notifications_tab', $activeNotificationTab, time() + (60 * 60 * 24 * 365), '/', '', $isSecureRequest, true);
}

$notificationsForView = array_values($notifications);
usort($notificationsForView, function ($left, $right) {
    $leftRead = (bool) ($left['is_read'] ?? false);
    $rightRead = (bool) ($right['is_read'] ?? false);

    if ($leftRead !== $rightRead) {
        return $leftRead <=> $rightRead;
    }

    $leftTimestamp = strtotime((string) ($left['created_at'] ?? ''));
    $rightTimestamp = strtotime((string) ($right['created_at'] ?? ''));

    $leftValue = $leftTimestamp !== false ? (int) $leftTimestamp : 0;
    $rightValue = $rightTimestamp !== false ? (int) $rightTimestamp : 0;

    return $rightValue <=> $leftValue;
});

$inboxNotificationsForView = [];
$orderNotificationsForView = [];

foreach ($notificationsForView as $notificationRecord) {
    if (!is_array($notificationRecord)) {
        continue;
    }

    $normalizedNotificationRecord = normalize_message_notification_record($notificationRecord);

    if (($normalizedNotificationRecord['type'] ?? 'message') === 'order') {
        $orderNotificationsForView[] = $normalizedNotificationRecord;
        continue;
    }

    $inboxNotificationsForView[] = $normalizedNotificationRecord;
}

$activeNotificationsForView = $activeNotificationTab === 'orders'
    ? $orderNotificationsForView
    : $inboxNotificationsForView;

$inboxTabPath = $notificationsPath . '?tab=inbox';
$ordersTabPath = $notificationsPath . '?tab=orders';
$currentNotificationsPath = $notificationsPath . '?tab=' . rawurlencode($activeNotificationTab);
$activeTabTitle = $activeNotificationTab === 'orders' ? 'Orders' : 'Inbox';
$activeTabDescription = $activeNotificationTab === 'orders'
    ? 'New reservation order alerts appear here so they do not clutter inbox messages.'
    : 'Customer messages and non-order updates appear here.';
$activeTabListAriaLabel = $activeNotificationTab === 'orders'
    ? 'Order notifications'
    : 'Inbox notifications';

function format_message_notification_datetime(string $value): string
{
    $timestamp = strtotime($value);

    if ($timestamp === false) {
        return 'Unknown time';
    }

    return date('M d, Y g:i A', $timestamp);
}

function message_notification_view_data(array $notification): array
{
    global $assetBase;

    $type = strtolower(trim((string) ($notification['type'] ?? 'message')));
    if ($type === '') {
        $type = 'message';
    }

    $payload = is_array($notification['payload'] ?? null) ? $notification['payload'] : [];

    $title = trim((string) ($notification['title'] ?? 'Notification'));
    if ($title === '') {
        $title = 'Notification';
    }

    $summary = trim((string) ($notification['summary'] ?? ''));
    if ($summary === '') {
        $summary = $title;
    }

    $orderId = trim((string) ($payload['order_id'] ?? ''));
    if ($type === 'order' && $orderId !== '') {
        $orderLabel = strtoupper($orderId);

        if ($title === '' || strtolower($title) === 'notification') {
            $title = 'A new order has been placed: ' . $orderLabel;
        }

        if ($summary === '' || strtolower($summary) === 'notification') {
            $summary = 'A new order has been placed: ' . $orderLabel;
        }
    }

    $senderName = trim((string) ($payload['sender_name'] ?? ($notification['sender_name'] ?? 'System')));
    if ($senderName === '') {
        $senderName = 'System';
    }

    $senderEmail = trim((string) ($payload['sender_email'] ?? ($notification['sender_email'] ?? '')));
    $messageBody = trim((string) ($payload['message'] ?? ($notification['message'] ?? '')));

    $attachmentsInput = $payload['attachments'] ?? ($notification['attachments'] ?? []);
    $attachments = [];

    if (is_array($attachmentsInput)) {
        foreach ($attachmentsInput as $attachmentPath) {
            $normalized = normalize_message_notification_attachment_path($attachmentPath);

            if ($normalized === '' || isset($attachments[$normalized])) {
                continue;
            }

            if (preg_match('/^(https?:)?\/\//i', $normalized) === 1) {
                $attachments[$normalized] = $normalized;
                continue;
            }

            $assetPrefix = (string) $assetBase;

            if ($assetPrefix !== '' && strpos($normalized, $assetPrefix) === 0) {
                $attachments[$normalized] = $normalized;
                continue;
            }

            $attachments[$normalized] = (string) $assetBase . ltrim($normalized, '/');
        }
    }

    return [
        'id' => trim((string) ($notification['id'] ?? '')),
        'type' => $type,
        'title' => $title,
        'summary' => $summary,
        'sender_name' => $senderName,
        'sender_email' => $senderEmail,
        'message' => $messageBody,
        'order_id' => strtoupper($orderId),
        'attachments' => array_values($attachments),
        'is_read' => (bool) ($notification['is_read'] ?? false),
        'created_at' => (string) ($notification['created_at'] ?? ''),
        'created_at_label' => format_message_notification_datetime((string) ($notification['created_at'] ?? '')),
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($isStaffSession ? 'Staff Notifications | Creaty' : 'Admin Notifications | Creaty', ENT_QUOTES, 'UTF-8'); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>css/style.css?v=20261008-2">
</head>
<body class="events-page admin-notifications-page">
    <header class="site-header">
        <div class="topbar topbar-admin">
            <a class="brand-badge" href="<?php echo htmlspecialchars($adminHomePath, ENT_QUOTES, 'UTF-8'); ?>">
                <img src="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>assets/images/main_logo.png" alt="The Nifty Fifty">
            </a>

            <form class="topbar-search landing-search" action="#" method="get">
                <input type="search" name="q" placeholder="Search notifications">
            </form>

            <div class="topbar-admin-actions">
                <a
                    class="topbar-notification-button"
                    href="<?php echo htmlspecialchars($currentNotificationsPath, ENT_QUOTES, 'UTF-8'); ?>"
                    aria-label="Notifications"
                    title="Notifications"
                    data-admin-notification-trigger
                    data-notification-count="<?php echo htmlspecialchars((string) $adminNotificationCount, ENT_QUOTES, 'UTF-8'); ?>"
                >
                    <span class="topbar-notification-text">Notifications</span>
                    <span class="topbar-notification-icon-wrap" aria-hidden="true">
                        <img class="topbar-notification-icon" src="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>assets/icons/notifications.svg" alt="">
                        <span class="cart-count topbar-notification-count" data-admin-notification-count aria-hidden="true"><?php echo htmlspecialchars((string) $adminNotificationCount, ENT_QUOTES, 'UTF-8'); ?></span>
                    </span>
                </a>

                <div class="dropdown topbar-account-menu">
                    <button class="account-pill account-pill-toggle dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <?php echo htmlspecialchars($accountLabel, ENT_QUOTES, 'UTF-8'); ?>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end account-dropdown-menu">
                        <li><a class="dropdown-item" href="<?php echo htmlspecialchars($adminHomePath, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($homeLabel, ENT_QUOTES, 'UTF-8'); ?></a></li>
                        <?php if (!$isStaffSession): ?>
                            <li><a class="dropdown-item" href="<?php echo htmlspecialchars($assetBase . 'archive/', ENT_QUOTES, 'UTF-8'); ?>">Archived</a></li>
                            <li><hr class="dropdown-divider"></li>
                        <?php endif; ?>
                        <li><a class="dropdown-item account-logout-item" href="<?php echo htmlspecialchars($logoutPath, ENT_QUOTES, 'UTF-8'); ?>">Log Out</a></li>
                    </ul>
                </div>
            </div>
        </div>

        <nav class="section-nav admin-notification-tabs" aria-label="Notifications view">
            <a
                class="section-nav-filter<?php echo $activeNotificationTab === 'inbox' ? ' is-active' : ''; ?>"
                href="<?php echo htmlspecialchars($inboxTabPath, ENT_QUOTES, 'UTF-8'); ?>"
                <?php echo $activeNotificationTab === 'inbox' ? 'aria-current="page"' : ''; ?>
            >
                INBOX
            </a>
            <a
                class="section-nav-filter<?php echo $activeNotificationTab === 'orders' ? ' is-active' : ''; ?>"
                href="<?php echo htmlspecialchars($ordersTabPath, ENT_QUOTES, 'UTF-8'); ?>"
                <?php echo $activeNotificationTab === 'orders' ? 'aria-current="page"' : ''; ?>
            >
                ORDERS
            </a>
        </nav>
    </header>

    <main class="admin-notifications-shell">
        <section class="admin-notifications-panel reveal" aria-labelledby="admin-notifications-title">
            <div class="admin-notifications-head">
                <h1 id="admin-notifications-title"><?php echo htmlspecialchars($activeTabTitle, ENT_QUOTES, 'UTF-8'); ?></h1>
                <p><?php echo htmlspecialchars($activeTabDescription, ENT_QUOTES, 'UTF-8'); ?></p>
            </div>

            <?php if (!$activeNotificationsForView): ?>
                <p class="admin-notifications-empty">No <?php echo htmlspecialchars(strtolower($activeTabTitle), ENT_QUOTES, 'UTF-8'); ?> notifications yet.</p>
            <?php else: ?>
                <div class="admin-notifications-list" role="list" aria-label="<?php echo htmlspecialchars($activeTabListAriaLabel, ENT_QUOTES, 'UTF-8'); ?>">
                    <?php foreach ($activeNotificationsForView as $notification): ?>
                        <?php
                            $view = message_notification_view_data(is_array($notification) ? $notification : []);
                            $orderIdForView = trim((string) ($view['order_id'] ?? ''));
                            $bookingsUrlForView = $view['type'] === 'order' ? $bookingsPath : '';
                            $attachmentsJson = json_encode($view['attachments'], JSON_UNESCAPED_SLASHES);

                            if (!is_string($attachmentsJson)) {
                                $attachmentsJson = '[]';
                            }
                        ?>
                        <button
                            class="admin-notification-item<?php echo $view['is_read'] ? ' is-read' : ' is-unread'; ?>"
                            type="button"
                            role="listitem"
                            data-admin-notification-item
                            data-notification-id="<?php echo htmlspecialchars($view['id'], ENT_QUOTES, 'UTF-8'); ?>"
                            data-notification-type="<?php echo htmlspecialchars($view['type'], ENT_QUOTES, 'UTF-8'); ?>"
                            data-notification-title="<?php echo htmlspecialchars($view['title'], ENT_QUOTES, 'UTF-8'); ?>"
                            data-notification-summary="<?php echo htmlspecialchars($view['summary'], ENT_QUOTES, 'UTF-8'); ?>"
                            data-notification-sender="<?php echo htmlspecialchars($view['sender_name'], ENT_QUOTES, 'UTF-8'); ?>"
                            data-notification-email="<?php echo htmlspecialchars($view['sender_email'], ENT_QUOTES, 'UTF-8'); ?>"
                            data-notification-message="<?php echo htmlspecialchars($view['message'], ENT_QUOTES, 'UTF-8'); ?>"
                            data-notification-order-id="<?php echo htmlspecialchars($orderIdForView, ENT_QUOTES, 'UTF-8'); ?>"
                            data-notification-bookings-url="<?php echo htmlspecialchars($bookingsUrlForView, ENT_QUOTES, 'UTF-8'); ?>"
                            data-notification-created-at="<?php echo htmlspecialchars($view['created_at_label'], ENT_QUOTES, 'UTF-8'); ?>"
                            data-notification-attachments="<?php echo htmlspecialchars($attachmentsJson, ENT_QUOTES, 'UTF-8'); ?>"
                            data-notification-is-read="<?php echo $view['is_read'] ? '1' : '0'; ?>"
                        >
                            <span class="admin-notification-item-main">
                                <span class="admin-notification-item-head">
                                    <span class="admin-notification-unread-dot"<?php echo $view['is_read'] ? ' hidden' : ''; ?> aria-hidden="true"></span>
                                    <span class="admin-notification-type-pill"><?php echo htmlspecialchars(strtoupper($view['type']), ENT_QUOTES, 'UTF-8'); ?></span>
                                    <span class="admin-notification-sender"><?php echo htmlspecialchars($view['sender_name'], ENT_QUOTES, 'UTF-8'); ?></span>
                                </span>
                                <span class="admin-notification-title"><?php echo htmlspecialchars($view['title'], ENT_QUOTES, 'UTF-8'); ?></span>
                            </span>
                            <span class="admin-notification-time"><?php echo htmlspecialchars($view['created_at_label'], ENT_QUOTES, 'UTF-8'); ?></span>
                        </button>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </main>

    <section class="admin-notification-modal" data-admin-notification-modal hidden>
        <div class="admin-notification-modal-backdrop" data-admin-notification-close></div>
        <section class="admin-notification-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="admin-notification-modal-title">
            <div class="admin-notification-modal-head">
                <h2 id="admin-notification-modal-title" data-admin-notification-modal-title>Notification</h2>
                <button class="admin-notification-modal-close" type="button" aria-label="Close notification details" data-admin-notification-close>&times;</button>
            </div>

            <div class="admin-notification-modal-meta">
                <span class="admin-notification-type-pill" data-admin-notification-modal-type>MESSAGE</span>
                <span class="admin-notification-modal-sender" data-admin-notification-modal-sender></span>
                <span class="admin-notification-modal-email" data-admin-notification-modal-email></span>
                <span class="admin-notification-modal-time" data-admin-notification-modal-time></span>
            </div>

            <div class="admin-notification-modal-scroll">
                <p class="admin-notification-modal-summary" data-admin-notification-modal-summary hidden></p>
                <p class="admin-notification-modal-message" data-admin-notification-modal-message hidden></p>
                <div class="admin-notification-modal-attachments" data-admin-notification-modal-attachments hidden></div>
                <p class="admin-notification-modal-empty" data-admin-notification-modal-empty hidden>No details available for this notification type yet.</p>
            </div>
        </section>
    </section>

    <script>
        window.__creatyAdminNotificationMarkReadEndpoint = <?php echo json_encode($markReadEndpoint, JSON_UNESCAPED_SLASHES); ?>;
        window.__creatyAdminNotificationLiveEndpoint = <?php echo json_encode($liveUpdatesEndpoint, JSON_UNESCAPED_SLASHES); ?>;
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script src="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>js/script.js?v=20260923-1"></script>
</body>
</html>
