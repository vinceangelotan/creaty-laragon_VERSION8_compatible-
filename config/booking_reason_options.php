<?php

function booking_reason_options($status)
{
    $options = [
        'canceled' => [
            'customer-request' => 'Customer requested cancellation.',
            'schedule-change' => 'The rental schedule has changed.',
            'equipment-unavailable' => 'The requested equipment is unavailable.',
            'duplicate-booking' => 'Duplicate booking.',
            'unable-to-contact' => 'Unable to confirm the booking with the customer.',
        ],
        'rejected' => [
            'payment-unverified' => 'The payment could not be verified.',
            'receipt-unreadable' => 'The payment receipt is incomplete or unreadable.',
            'payment-mismatch' => 'The payment amount or reference does not match the booking.',
            'documents-incomplete' => 'Required booking documents are incomplete.',
        ],
        'refunded' => [
            'canceled-booking' => 'Payment refunded for the canceled booking.',
            'equipment-unavailable' => 'Payment refunded because the requested equipment is unavailable.',
            'duplicate-payment' => 'Duplicate payment refunded.',
            'customer-request' => 'Payment refunded at the customer\'s request.',
        ],
    ];

    return $options[$status] ?? [];
}

function resolve_booking_reason($status, $choice, $customReason)
{
    $choice = trim((string) $choice);
    if ($choice === 'others') {
        return normalize_customer_order_cancel_reason($customReason);
    }

    return booking_reason_options($status)[$choice] ?? '';
}
