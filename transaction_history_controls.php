<?php
$historyScope = ($transactionHistoryScope ?? '') === 'admin' ? 'admin' : 'customer';
$historyControlPrefix = $historyScope . '-history';
$historyAccountId = $historyScope === 'admin'
    ? (isset($_SESSION['user_id']) ? 'admin-' . $_SESSION['user_id'] : 'staff-' . ($_SESSION['staff_id'] ?? ''))
    : 'customer-' . ($_SESSION['customer_id'] ?? '');
?>
<form class="transaction-history-controls" data-transaction-history-controls="<?php echo $historyScope; ?>" data-history-state-key="<?php echo htmlspecialchars($historyAccountId, ENT_QUOTES, 'UTF-8'); ?>" aria-label="Filter and sort reservation history">
    <div class="transaction-history-fields">
        <label class="transaction-history-field transaction-history-search" for="<?php echo $historyControlPrefix; ?>-search">
            <span>Search reservations</span>
            <input id="<?php echo $historyControlPrefix; ?>-search" type="search" name="search" maxlength="150" placeholder="<?php echo $historyScope === 'admin' ? 'Name, reservation ID, camera or package' : 'Reservation ID, camera or package'; ?>" autocomplete="off">
        </label>
        <label class="transaction-history-field" for="<?php echo $historyControlPrefix; ?>-status">
            <span>Status</span>
            <select id="<?php echo $historyControlPrefix; ?>-status" name="status">
                <option value="">All statuses</option>
                <option value="pending">Pending (all)</option>
                <option value="pending-receipt">Pending receipt</option>
                <option value="payment-review">Payment review</option>
                <option value="approved">Approved (all)</option>
                <option value="for-pickup">For pickup</option>
                <option value="ongoing">Ongoing</option>
                <option value="return">For return</option>
                <option value="completed">Completed</option>
                <option value="canceled">Canceled</option>
                <option value="awaiting-refund">Awaiting refund</option>
                <option value="rejected">Rejected</option>
                <option value="refunded">Refunded</option>
            </select>
        </label>
        <label class="transaction-history-field" for="<?php echo $historyControlPrefix; ?>-camera">
            <span>Camera / package</span>
            <select id="<?php echo $historyControlPrefix; ?>-camera" name="camera">
                <option value="">All cameras and packages</option>
            </select>
        </label>
        <label class="transaction-history-field" for="<?php echo $historyControlPrefix; ?>-sort">
            <span>Sort by</span>
            <select id="<?php echo $historyControlPrefix; ?>-sort" name="sort">
                <?php if ($historyScope === 'admin'): ?>
                    <option value="queue">Pending priority first</option>
                <?php endif; ?>
                <option value="created-desc">Date placed: newest first</option>
                <option value="created-asc">Date placed: oldest first</option>
                <option value="receive-asc">Receiving date: earliest first</option>
                <option value="receive-desc">Receiving date: latest first</option>
                <option value="return-asc">Return date: earliest first</option>
                <option value="return-desc">Return date: latest first</option>
                <?php if ($historyScope === 'admin'): ?>
                    <option value="name-asc">Customer name: A–Z</option>
                    <option value="name-desc">Customer name: Z–A</option>
                <?php endif; ?>
                <option value="camera-asc">Camera / package: A–Z</option>
                <option value="camera-desc">Camera / package: Z–A</option>
            </select>
        </label>
        <label class="transaction-history-field" for="<?php echo $historyControlPrefix; ?>-date-field">
            <span>Filter dates by</span>
            <select id="<?php echo $historyControlPrefix; ?>-date-field" name="dateField">
                <option value="created">Date placed</option>
                <option value="receive">Receiving date</option>
                <option value="return">Return date</option>
            </select>
        </label>
        <label class="transaction-history-field" for="<?php echo $historyControlPrefix; ?>-from">
            <span>From date</span>
            <input id="<?php echo $historyControlPrefix; ?>-from" type="date" name="from" aria-describedby="<?php echo $historyControlPrefix; ?>-error">
        </label>
        <label class="transaction-history-field" for="<?php echo $historyControlPrefix; ?>-to">
            <span>To date</span>
            <input id="<?php echo $historyControlPrefix; ?>-to" type="date" name="to" aria-describedby="<?php echo $historyControlPrefix; ?>-error">
        </label>
        <div class="transaction-history-reset-wrap">
            <button class="transaction-history-reset" type="reset">Reset filters</button>
        </div>
    </div>
    <p class="transaction-history-error" id="<?php echo $historyControlPrefix; ?>-error" data-history-error role="alert" hidden></p>
    <p class="transaction-history-summary" data-history-summary role="status" aria-live="polite" aria-atomic="true"></p>
</form>
