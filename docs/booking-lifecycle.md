# Booking expiry and equipment reservations

Pending bookings expire at their scheduled receiving date and time in `Asia/Manila`. Cash bookings become **Canceled**. GCash bookings with a submitted receipt become **Awaiting Refund**, retaining the receipt and the existing refund workflow. Both statuses release reserved equipment. The customer receives the automatic status notification with the reason for expiry.

The existing 10-minute deadline for a missing GCash receipt still applies. Approved bookings continue through the existing receiving and return transitions. Repeated reads do not duplicate expiry notifications.

Pending bookings reserve the requested quantity of each model for the booked interval. Additional units and non-overlapping dates remain bookable. Capacity uses the highest simultaneous reservation count, so sequential bookings do not incorrectly exhaust a second unit. Booking mutations hold one repository lock across loading, checking availability, and saving, including automatic transitions and receipt uploads.

Order records are the source of truth for reservations and status. If updating the derived inventory in-use flags fails after an order is saved, the committed order still succeeds and its status notification is sent. The failure is logged, and the next booking read retries inventory synchronization.

## Lifecycle maintenance

Customer and staff requests that load bookings apply these transitions automatically. With no website traffic, the JSON is updated on the next request. To persist transitions while the site is idle, configure the hosting scheduler or Windows Task Scheduler to run this CLI command every minute:

```text
php scripts/process_booking_lifecycle.php
```

Use the installation's full PHP executable and script paths in the scheduler. This entry point operates on the configured booking data and sends the normal status notifications. No scheduler is installed by this change, and the script rejects HTTP requests.

## Regression checks

Run manually with the Laragon PHP CLI from the project directory:

```text
php tests/booking_lifecycle_test.php
```

The test copies repository code into a unique OS temporary directory and creates synthetic JSON there. It never loads or writes the project's real order, inventory, or notification JSON. It covers the expiry boundary, paid receipt preservation, idempotent notifications, pending capacity, adjacent and overlapping reservations, and two concurrent PHP processes attempting to reserve the last camera. The concurrency check requires `proc_open`.
