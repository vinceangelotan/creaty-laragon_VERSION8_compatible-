const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

const source = fs.readFileSync(path.join(__dirname, '../js/script.js'), 'utf8').replace(/\r\n/g, '\n');
const filterSource = source.slice(source.indexOf('    function applyProductFilters()'), source.indexOf('    if (catalogFilterForm) {\n        catalogFilterForm.addEventListener'));

function card(brand, skills, category, price) {
    const attributes = { 'data-brand': brand, 'data-skill-levels': skills, 'data-category': category, 'data-price': String(price) };
    return {
        hidden: false,
        attributes,
        getAttribute(name) { return attributes[name] ?? null; },
        classList: {
            add() { this.owner.hidden = true; },
            toggle(name, hidden) { this.owner.hidden = hidden; },
            owner: null
        }
    };
}

function catalog(withForm = true) {
    const cards = [
        card('canon', 'beginner', 'photography', 400),
        card('fuji', 'beginner', 'photography', 315),
        card('sony', 'professional', 'videography', 899),
        card('nikon', 'beginner professional', 'photography', 250.5)
    ];
    cards.forEach(item => { item.classList.owner = item; });
    const inputs = {};
    const context = {
        productCards: cards,
        productEmpty: { hidden: true },
        catalogFilterForm: withForm ? { querySelector(selector) { return inputs[selector] ??= { value: '' }; } } : null,
        catalogFilterCount: {},
        catalogResultsCount: {},
        catalogViewResults: {},
        catalogResetButtons: [],
        catalogPriceLimit: 900,
        catalogFilters: { skills: [], categories: [], min: 0, max: 900 },
        activeFilters: { brand: 'all', month: 'all', day: 'all', year: 'all' },
        document: { querySelector() { return { setAttribute() {} }; } },
        getEffectiveToday() { return new Date(2026, 9, 8); },
        normalizeFilterValue(value) { return String(value || 'all').toLowerCase(); },
        calendarViewYear: 2026,
        calendarViewMonthIndex: 9,
        monthValueToIndex: { january: 0, october: 9 }
    };
    vm.createContext(context);
    vm.runInContext(filterSource, context);
    return { context, inputs, visible: () => cards.filter(item => !item.hidden) };
}

const { context, inputs, visible } = catalog();
context.applyProductFilters();
assert.equal(visible().length, 4, 'Unchecked groups show all eligible cards');
context.catalogFilters.skills = ['professional'];
context.applyProductFilters();
assert.equal(visible().length, 2, 'Multi-level products match either assigned skill');
context.catalogFilters.skills = ['beginner', 'professional'];
context.catalogFilters.categories = ['photography'];
context.applyProductFilters();
assert.equal(visible().length, 3, 'Skills form a union intersected with category');
context.catalogFilters.categories = ['photography', 'videography'];
context.applyProductFilters();
assert.equal(visible().length, 4, 'Multiple categories form a union');
context.catalogFilters.min = 315;
context.catalogFilters.max = 400;
context.applyProductFilters();
assert.deepEqual(visible().map(item => item.attributes['data-price']), ['400', '315'], 'Price endpoints include displayed promotional prices');
context.activeFilters.brand = 'fuji';
context.applyProductFilters();
assert.equal(visible().length, 1, 'New filters intersect the existing brand filter');
context.activeFilters.year = '2025';
context.applyProductFilters();
assert.equal(visible().length, 0, 'Existing future-date behavior is preserved');
assert.equal(context.productEmpty.hidden, false);
assert.equal(context.catalogResultsCount.textContent, '0 of 4 products');
context.activeFilters.year = 'all';
context.activeFilters.brand = 'all';
context.catalogFilters.skills = ['professional'];
context.catalogFilters.categories = ['photography'];
context.applyProductFilters();
assert.equal(visible().length, 0, 'All groups must match');

context.updateCatalogPrice('min', 950);
assert.equal(context.catalogFilters.min, 900);
assert.equal(context.catalogFilters.max, 900, 'Crossed endpoints keep a valid range');
context.updateCatalogPrice('max', 250.5);
assert.equal(context.catalogFilters.min, 250.5);
assert.equal(context.catalogFilters.max, 250.5, 'Decimal amounts are preserved');
assert.equal(inputs['[data-catalog-price="max"]'].value, '250.5');
assert.equal(inputs['[data-catalog-range="max"]'].value, '250.5', 'Slider and numeric input stay synchronized');
context.updateCatalogPrice('min', -10);
assert.equal(context.catalogFilters.min, 0);
context.updateCatalogPrice('max', '');
assert.equal(context.catalogFilters.max, 900, 'An empty maximum restores the catalog bound');
context.updateCatalogPrice('min', 'not-a-number');
assert.equal(context.catalogFilters.min, 0);

const admin = catalog(false);
admin.context.catalogFilters.skills = ['unsupported'];
admin.context.applyProductFilters();
assert.equal(admin.visible().length, 4, 'Pages without the customer form retain their existing behavior');
admin.context.productCards[0].attributes['data-admin-removed'] = 'true';
admin.context.applyProductFilters();
assert.equal(admin.visible().length, 3, 'Admin-removed cards remain excluded');

const empty = catalog();
empty.context.productCards = [];
empty.context.applyProductFilters();
assert.equal(empty.context.catalogResultsCount.textContent, '0 of 0 products');
assert.equal(empty.context.productEmpty.hidden, false);

console.log('PASS: skill/category combinations, promotional price boundaries, price normalization and synchronization, brand/date compatibility, empty catalog, and admin isolation.');
