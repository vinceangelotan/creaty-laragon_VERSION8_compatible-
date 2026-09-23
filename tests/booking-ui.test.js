// Static browser checks: no PHP server, database, or application JSON is used.
// Run with Node and Playwright installed (set CHROME_PATH if needed).
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const os = require('node:os');
const vm = require('node:vm');
const { chromium } = require('playwright');

const root = path.resolve(__dirname, '..');
const source = fs.readFileSync(path.join(root, 'js/script.js'), 'utf8');
const peakSource = source.slice(source.indexOf('    function peakReservedQuantity('), source.indexOf('    var revealItems'));
const peak = vm.runInNewContext(peakSource + '\npeakReservedQuantity');
assert.equal(peak([{startTs: 0, endTs: 10, qty: 1}, {startTs: 10, endTs: 20, qty: 1}], 0, 20), 1);
assert.equal(peak([{startTs: 0, endTs: 15, qty: 2}, {startTs: 10, endTs: 20, qty: 1}], 0, 20), 3);
assert.equal(peak([{startTs: 0, endTs: 10, qty: 1}], 10, 20), 0);
assert.equal(peak([{startTs: 0, endTs: 10, qty: 1}, {startTs: NaN, endTs: 15, qty: 1}], 0, 5), 1);

const dashboard = fs.readFileSync(path.join(root, 'dashboard_page_admin.php'), 'utf8');
const reasonSource = fs.readFileSync(path.join(root, 'config/booking_reason_options.php'), 'utf8');
function optionsFor(status) {
    const block = reasonSource.match(new RegExp("'" + status + "' => \\[([\\s\\S]*?)\\n        \\],"))[1];
    return Array.from(block.matchAll(/'([^']+)' => '((?:\\'|[^'])*)'/g)).map((match) =>
        `<option value="${match[1]}" data-reason-status="${status}">${match[2].replace(/\\'/g, "'")}</option>`).join('');
}
let modals = dashboard.slice(dashboard.indexOf('        <div class="admin-booking-detail-backdrop"'), dashboard.indexOf('        <div class="admin-booking-delivery-backdrop"'));
modals = modals.replace(/(<select[^>]+data-admin-booking-review-choice[^>]*>)[\s\S]*?<\/select>/, '$1<option value="">Select a reason</option>' + optionsFor('rejected') + optionsFor('refunded') + '<option value="others">Others</option></select>');
modals = modals.replace(/(<select[^>]+data-admin-booking-cancel-choice[^>]*>)[\s\S]*?<\/select>/, '$1<option value="">Select a reason</option>' + optionsFor('canceled') + '<option value="others">Others</option></select>');
modals = modals.replace(/<\?php[\s\S]*?\?>/g, '');
const css = fs.readFileSync(path.join(root, 'css/style.css'), 'utf8');
const fixture = { id: 'fixture-order', name: 'Sample Customer', status: 'PENDING', statusToken: 'pending', paymentMethod: 'gcash', paymentReceiptPath: 'receipt.png', waitingForPaymentReview: true, receivingMethod: 'pickup', returningMethod: 'pickup', receiveDate: '2027-01-10', receiveTime: '10:00', returnDate: '2027-01-11', returnTime: '10:00', items: [{name: 'Canon EOS R50', qty: 1, days: 1, price: 900}], total: 900 };

(async function () {
    const browser = await chromium.launch({headless: true, ...(process.env.CHROME_PATH ? {executablePath: process.env.CHROME_PATH} : {})});
    try {
        const page = await browser.newPage({viewport: {width: 1280, height: 850}});
        await page.route('**/*', route => route.abort());
        const errors = [];
        page.on('pageerror', error => errors.push(error.message));
        await page.setContent('<style>' + css + '</style><button data-admin-booking-row data-admin-booking-id="fixture-order">Open reservation</button>' + modals);
        await page.evaluate(order => {
            window.__creatyAdminBookings = [order];
            window.submissions = [];
            HTMLFormElement.prototype.submit = function () { window.submissions.push(Object.fromEntries(new FormData(this))); };
        }, fixture);
        await page.addScriptTag({content: source});
        await page.evaluate(() => document.dispatchEvent(new Event('DOMContentLoaded')));
        assert.deepEqual(errors, [], 'Main script must initialize without runtime errors');
        await page.click('[data-admin-booking-row]');
        await page.click('[data-admin-booking-cancel-open]');
        assert.equal(await page.locator('[data-admin-booking-cancel-custom]').isVisible(), false);
        await page.click('[data-admin-booking-cancel-confirm]');
        assert.equal(await page.locator('[data-admin-booking-cancel-error]').isVisible(), true);
        assert.equal(await page.evaluate(() => window.submissions.length), 0);
        await page.selectOption('[data-admin-booking-cancel-choice]', 'others');
        await page.fill('[data-admin-booking-cancel-reason]', '   ');
        await page.click('[data-admin-booking-cancel-confirm]');
        assert.equal(await page.evaluate(() => window.submissions.length), 0);
        await page.fill('[data-admin-booking-cancel-reason]', 'Customer moved the shoot to next week.');
        await page.click('[data-admin-booking-cancel-confirm]');
        assert.equal(await page.evaluate(() => window.submissions.at(-1).reason_choice), 'others');
        assert.equal(await page.evaluate(() => window.submissions.at(-1).cancel_reason), 'Customer moved the shoot to next week.');
        await page.keyboard.press('Escape');
        await page.click('[data-admin-booking-cancel-open]');
        assert.equal(await page.inputValue('[data-admin-booking-cancel-choice]'), '');
        await page.selectOption('[data-admin-booking-cancel-choice]', 'schedule-change');
        assert.equal(await page.locator('[data-admin-booking-cancel-custom]').isVisible(), false);
        await page.click('[data-admin-booking-cancel-confirm]');
        assert.equal(await page.evaluate(() => window.submissions.at(-1).cancel_reason), 'The rental schedule has changed.');
        await page.keyboard.press('Escape');
        await page.click('[data-admin-booking-review-open][data-admin-booking-review-mode="rejected"]');
        assert.equal(await page.locator('[data-admin-booking-review-choice] option[value="canceled-booking"]').isDisabled(), true);
        await page.selectOption('[data-admin-booking-review-choice]', 'receipt-unreadable');
        await page.click('[data-admin-booking-review-confirm]');
        assert.equal(await page.evaluate(() => window.submissions.at(-1).next_status), 'rejected');
        await page.keyboard.press('Escape');
        assert.equal(await page.locator('[data-admin-booking-detail-backdrop]').isVisible(), true, 'Escape closes decision only');
        await page.click('[data-admin-booking-review-open][data-admin-booking-review-mode="refunded"]');
        assert.equal(await page.locator('[data-admin-booking-review-choice] option[value="receipt-unreadable"]').isDisabled(), true);
        await page.selectOption('[data-admin-booking-review-choice]', 'canceled-booking');
        const beforeRefund = await page.evaluate(() => window.submissions.length);
        await page.click('[data-admin-booking-review-confirm]');
        assert.equal(await page.evaluate(() => window.submissions.length), beforeRefund, 'Refund still requires proof');
        await page.keyboard.press('Escape');
        await page.click('[data-admin-booking-cancel-open]');
        await page.selectOption('[data-admin-booking-cancel-choice]', 'others');
        await page.setViewportSize({width: 375, height: 667});
        await page.locator('[data-admin-booking-cancel-confirm]').focus();
        await page.keyboard.press('Tab');
        assert.equal(await page.evaluate(() => document.activeElement.hasAttribute('data-admin-booking-cancel-close')), true, 'Tab wraps within dialog');
        const dimensions = await page.locator('.admin-booking-cancel-modal').evaluate(node => ({width: node.getBoundingClientRect().width, height: node.getBoundingClientRect().height}));
        assert.ok(dimensions.width <= 375 && dimensions.height <= 667, 'Dialog fits narrow viewport');
        const screenshot = path.join(os.tmpdir(), 'creaty-booking-reason-mobile.png');
        await page.screenshot({path: screenshot});
        assert.deepEqual(errors, []);
        console.log('PASS: availability boundaries; reason presets/Others; empty reason validation; decision reset; refund proof guard; Escape/focus; mobile layout.');
        console.log('Static fixture screenshot: ' + screenshot);

        const historyPage = await browser.newPage({viewport: {width: 1280, height: 850}});
        await historyPage.route('**/*', route => route.abort());
        historyPage.on('pageerror', error => errors.push(error.message));
        let controls = fs.readFileSync(path.join(root, 'transaction_history_controls.php'), 'utf8')
            .replace(/<\?php echo \$historyScope; \?>/g, 'admin')
            .replace(/<\?php echo \$historyControlPrefix; \?>/g, 'admin-history')
            .replace(/<\?php[\s\S]*?\?>/g, '');
        const historyOrders = [fixture, {...fixture, id: 'second-order', name: 'Ana Cruz', statusToken: 'completed', status: 'COMPLETED', items: [{name: 'Sony A7', qty: 1, days: 1}], createdAt: '2026-09-21T09:00:00+08:00'}];
        const rows = historyOrders.map(order => `<tr data-admin-booking-row data-admin-booking-id="${order.id}"><td>${order.name}</td><td>${order.id}</td><td>${order.items[0].name}</td><td>${order.receiveDate}</td><td>${order.status}</td></tr>`).join('');
        await historyPage.setContent('<style>' + css + fs.readFileSync(path.join(root, 'css/transaction-history.css'), 'utf8') + '</style><section class="admin-bookings-shell" data-admin-dashboard-panel="bookings">' + controls + '<div class="admin-bookings-table-wrap"><table class="admin-bookings-table"><tbody>' + rows + '<tr data-admin-bookings-empty hidden><td colspan="5">No matching reservations</td></tr></tbody></table></div></section>');
        await historyPage.evaluate(orders => { window.__creatyAdminBookings = orders; }, historyOrders);
        await historyPage.addScriptTag({path: path.join(root, 'js/transaction-history.js')});
        assert.equal(await historyPage.locator('[data-admin-booking-row]:visible').count(), 2);
        await historyPage.selectOption('[name="status"]', 'completed');
        assert.equal(await historyPage.locator('[data-admin-booking-row]:visible').count(), 1);
        await historyPage.fill('[name="search"]', 'No matching person');
        assert.equal(await historyPage.locator('[data-admin-booking-row]:visible').count(), 0);
        await historyPage.click('button[type="reset"]');
        assert.equal(await historyPage.locator('[data-admin-booking-row]:visible').count(), 2);
        await historyPage.selectOption('[name="sort"]', 'name-asc');
        assert.equal(await historyPage.locator('[data-admin-booking-row]').first().getAttribute('data-admin-booking-id'), 'second-order');
        await historyPage.selectOption('[name="camera"]', 'canon eos r50');
        assert.equal(await historyPage.locator('[data-admin-booking-row]:visible').count(), 1);
        await historyPage.fill('[name="from"]', '2026-09-24');
        await historyPage.fill('[name="to"]', '2026-09-20');
        assert.equal(await historyPage.locator('[data-history-error]').isVisible(), true);
        await historyPage.click('button[type="reset"]');
        await historyPage.setViewportSize({width: 375, height: 800});
        assert.equal(await historyPage.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth), true, 'History controls and table must not overflow page');
        const historyScreenshot = path.join(os.tmpdir(), 'creaty-history-mobile.png');
        await historyPage.screenshot({path: historyScreenshot, fullPage: true});
        assert.deepEqual(errors, []);
        console.log('PASS: history status/search/camera filters, reset, name sorting, invalid dates, and mobile containment.');
        console.log('Static history screenshot: ' + historyScreenshot);
    } finally {
        await browser.close();
    }
})().catch(error => { console.error(error); process.exitCode = 1; });
