"use strict";

const test = require("node:test");
const assert = require("node:assert/strict");
const history = require("../js/transaction-history.js");

const orders = [
    { id: "P1", name: "Zara Santos", email: "zara@example.test", statusToken: "pending", isPriorityCustomer: false, paymentMethod: "gcash", items: [{ name: "Canon R10" }], createdAt: "2026-09-22T16:05:00Z", receiveDate: "2026-09-24", receiveTime: "10:00", returnDate: "2026-09-25" },
    { id: "C2", name: "Ana Cruz", statusToken: "completed", items: [{ name: "Sony A7" }, { name: "Canon R2" }], createdAt: "2026-09-22T15:59:00Z", receiveDate: "2026-09-23", receiveTime: "09:00", returnDate: "2026-09-24" },
    { id: "P3", name: "Ben Cruz", statusToken: "pending", isPriorityCustomer: true, paymentMethod: "gcash", paymentReceiptPath: "receipt.png", items: [{ name: "Canon R2" }], createdAt: "2026-09-21T01:00:00+08:00", receiveDate: "2026-09-24", receiveTime: "08:00", returnDate: "2026-09-26" },
    { id: "R4", name: "Ana Cruz", statusToken: "rejected", items: [], createdAt: "not-a-date" },
    { id: "A5", name: "Cara Lim", statusToken: "approved", forPickupReady: true, items: [{ name: "Sony A7" }], createdAt: "2026-09-20T01:00:00+08:00", receiveDate: "2026-09-24", receiveTime: "11:00" }
];

function ids(state, scope = "admin", source = orders) {
    return history.queryOrders(source, state, scope).map(order => order.id);
}

test("all statuses remain in history; queue preserves priority and source order", () => {
    assert.deepEqual(ids({}), ["P3", "P1", "C2", "R4", "A5"]);
    assert.equal(ids({}, "customer").length, orders.length);
    assert.deepEqual(ids({ status: "rejected" }), ["R4"]);
});

test("pending substatus filters stay within pending transactions", () => {
    assert.deepEqual(ids({ status: "pending" }), ["P3", "P1"]);
    assert.deepEqual(ids({ status: "pending-receipt" }), ["P1"]);
    assert.deepEqual(ids({ status: "payment-review" }), ["P3"]);
    assert.deepEqual(ids({ status: "for-pickup" }), ["A5"]);
    assert.deepEqual(ids({ status: "approved" }), ["A5"]);
});

test("date bounds are inclusive and follow Philippine dates across UTC midnight", () => {
    assert.equal(history.orderDate(orders[0], "created"), "2026-09-23");
    assert.equal(history.orderDate(orders[1], "created"), "2026-09-22");
    assert.deepEqual(ids({ from: "2026-09-23", to: "2026-09-23" }), ["P1"]);
    assert.deepEqual(ids({ from: "2026-09-22", to: "2026-09-23", sort: "created-asc" }), ["C2", "P1"]);
});

test("date placed without a timezone is interpreted in shop time", () => {
    const order = { createdAt: "2026-09-23 00:30:00" };
    assert.equal(history.createdTimestamp(order), Date.parse("2026-09-22T16:30:00Z"));
    assert.equal(history.orderDate(order, "created"), "2026-09-23");
});

test("range uses selected schedule field and rejects an inverted range", () => {
    assert.deepEqual(ids({ dateField: "return", from: "2026-09-25", to: "2026-09-26" }), ["P3", "P1"]);
    assert.deepEqual(ids({ from: "2026-09-26", to: "2026-09-25" }), []);
    assert.equal(history.normalizeState({ from: "2026-02-30" }, "admin").from, "");
});

test("search, status, camera and date filters combine; camera can be the second item", () => {
    assert.deepEqual(ids({ search: "  ANA   canon ", camera: "CANON R2", status: "completed", dateField: "receive", from: "2026-09-23", to: "2026-09-23" }), ["C2"]);
    assert.deepEqual(ids({ search: "zara@example.test" }), ["P1"]);
    assert.deepEqual(ids({ camera: "archived camera" }), []);
});

test("date sorting is chronological and missing dates stay last in either direction", () => {
    assert.deepEqual(ids({ sort: "created-desc" }), ["P1", "C2", "P3", "A5", "R4"]);
    assert.deepEqual(ids({ sort: "created-asc" }), ["A5", "P3", "C2", "P1", "R4"]);
    assert.deepEqual(ids({ sort: "receive-asc" }), ["C2", "P3", "P1", "A5", "R4"]);
    assert.deepEqual(ids({ sort: "return-desc" }), ["P3", "P1", "C2", "R4", "A5"]);
});

test("name sort is stable; camera sort uses natural model order and missing labels last", () => {
    assert.deepEqual(ids({ sort: "name-asc" }), ["C2", "R4", "P3", "A5", "P1"]);
    assert.deepEqual(ids({ sort: "camera-asc" }), ["P3", "C2", "P1", "A5", "R4"]);
    assert.deepEqual(ids({ sort: "camera-desc" }), ["A5", "P1", "C2", "P3", "R4"]);
});

test("filtering never mutates orders and safely normalizes old saved settings", () => {
    const snapshot = JSON.stringify(orders);
    ids({ sort: "camera-asc" });
    assert.equal(JSON.stringify(orders), snapshot);
    assert.equal(history.normalizeState({ sort: "unknown", status: "unknown" }, "admin").sort, "queue");
    assert.equal(history.normalizeState({ sort: "name-asc" }, "customer").sort, "created-desc");
    assert.deepEqual(history.queryOrders([], {}, "customer"), []);
});
