# Feedback update

## Implemented behavior

- Unapproved pending reservations expire at their scheduled receiving time in Philippine time. Cash bookings are canceled; GCash bookings with a receipt remain in the refund queue. The existing 10-minute receipt deadline remains in place.
- Pending reservations hold the requested model quantity for the booked dates. Overlapping submissions are checked and saved under one lock. Separate dates and remaining units stay bookable.
- Staff/admin cancellation, rejection, and refund dialogs offer preset reasons and **Others**, which requires an explanation. Refunds still require proof. Invalid or stale decisions show feedback after redirect.
- Equipment inventory groups units by model and quantity. Expanding a model reveals unit IDs and existing actions. Search supports models and IDs; expansion survives reloads.
- Staff/admin and customer history supports status, search, camera/package, date range, and sorting. Customer history includes canceled, rejected, and refunded bookings. Active reservations open Order Status; finished reservations can be re-reserved.
- Hidden dialogs no longer intercept clicks. The changed controls support keyboard use, visible focus, empty states, and narrow screens. Inventory changes and product/brand archival cannot remove capacity needed by existing reservations.

## Checks performed

- Syntax lint passed for all 104 PHP files, without executing the PHP application.
- JavaScript syntax checks passed for the main script and both new UI modules.
- All 9 Node history regression tests passed.
- Isolated headless Chrome fixtures passed for reason selection, required custom text, decision resets, refund proof validation, keyboard focus/Escape, history filtering/sorting/reset, invalid date ranges, and mobile containment.
- Inventory fixture checks passed for expansion, remembered expansion, search, no results, and contained mobile scrolling.
- Mobile screenshots were visually inspected. Browser fixtures use synthetic markup/data, not a live PHP-rendered page.

## Laragon verification

The repository's `.github/copilot-instructions.md` reserves PHP application execution for the project owner. Live PHP/MySQL flows and the PHP behavioral regression suite have therefore **not** been executed in this change.

From a Laragon terminal, the isolated backend regression suite is:

```text
php tests/booking_lifecycle_test.php
```

It uses disposable synthetic JSON under the OS temporary directory. It covers expiry boundaries, refund preservation, concurrent submissions for the last unit, corrupted booking data, and recovery from a failed inventory synchronization.

The JavaScript checks can be repeated with:

```text
node --test tests/transaction-history.test.js
node tests/booking-ui.test.js
```

The browser check requires Playwright. Set `CHROME_PATH` to a local Chrome executable if Playwright has no bundled browser available.

For live acceptance, check these flows using test accounts/bookings:

1. Leave cash and receipted GCash orders pending beyond their receiving time, then refresh Order Status. Expect Canceled and Awaiting Refund respectively, with a system reason.
2. From two customer sessions, request the last camera for overlapping dates. Only one booking should succeed; another date or an additional free unit should remain bookable.
3. Try a preset reason and Others; blank custom text must be rejected. Confirm that the customer sees the selected reason and that a refund still requires its proof image.
4. Expand inventory, change a unit status, and reload. Try removing/archiving a model needed by an upcoming reservation; the change must be blocked with an explanation.
5. Combine history filters, reverse the date range, reset, and sort names/dates/cameras on desktop and mobile. Rejected/canceled/refunded customer records must remain accessible.

Expiry is applied on booking reads. For persistence while the site is idle, see [booking lifecycle maintenance](booking-lifecycle.md); the optional maintenance script is included, but no scheduler has been installed.
