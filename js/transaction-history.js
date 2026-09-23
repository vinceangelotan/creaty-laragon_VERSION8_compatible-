(function (root) {
    "use strict";

    var statusValues = ["", "pending", "pending-receipt", "payment-review", "approved", "for-pickup", "ongoing", "return", "completed", "canceled", "awaiting-refund", "rejected", "refunded"];
    var sortValues = ["queue", "created-desc", "created-asc", "receive-asc", "receive-desc", "return-asc", "return-desc", "name-asc", "name-desc", "camera-asc", "camera-desc"];
    var collator = new Intl.Collator(undefined, { sensitivity: "base", numeric: true });
    var dateFormatter = new Intl.DateTimeFormat("en-CA", { timeZone: "Asia/Manila", year: "numeric", month: "2-digit", day: "2-digit" });
    var controllers = {};

    function normalizeText(value) {
        return String(value || "").trim().toLocaleLowerCase();
    }

    function validDate(value) {
        var text = String(value || "");
        if (!/^\d{4}-\d{2}-\d{2}$/.test(text)) {
            return "";
        }
        var date = new Date(text + "T00:00:00Z");
        return Number.isFinite(date.getTime()) && date.toISOString().slice(0, 10) === text ? text : "";
    }

    function normalizeState(state, scope) {
        state = state || {};
        var sort = sortValues.indexOf(state.sort) !== -1 ? state.sort : (scope === "admin" ? "queue" : "created-desc");
        if (scope !== "admin" && (sort === "queue" || sort.indexOf("name-") === 0)) {
            sort = "created-desc";
        }
        return {
            search: String(state.search || "").slice(0, 150),
            status: statusValues.indexOf(state.status) !== -1 ? state.status : "",
            camera: normalizeText(state.camera),
            sort: sort,
            dateField: ["created", "receive", "return"].indexOf(state.dateField) !== -1 ? state.dateField : "created",
            from: validDate(state.from),
            to: validDate(state.to)
        };
    }

    function itemNames(order) {
        return (Array.isArray(order.items) ? order.items : []).map(function (item) {
            return String(item && item.name || "").trim();
        }).filter(Boolean);
    }

    function statusToken(order) {
        var token = normalizeText(order.statusToken || order.status_token || order.status).replace(/\s+/g, "-");
        return ({ confirmed: "approved", "for-return": "return", "past-return": "return", cancelled: "canceled" })[token] || token;
    }

    function matchesStatus(order, selected) {
        var token = statusToken(order);
        var hasReceipt = Boolean(order.paymentReceiptPath || order.payment_receipt_path);
        var gcash = normalizeText(order.paymentMethod || order.payment_method) === "gcash";
        if (selected === "pending-receipt") {
            return token === "pending" && (Boolean(order.waitingForPaymentReceipt) || (gcash && !hasReceipt));
        }
        if (selected === "payment-review") {
            return token === "pending" && (Boolean(order.waitingForPaymentReview) || (gcash && hasReceipt));
        }
        if (selected === "for-pickup") {
            return token === "approved" && Boolean(order.forPickupReady);
        }
        return !selected || selected === token;
    }

    function createdTimestamp(order) {
        var unix = Number(order.createdAtUnix);
        if (Number.isFinite(unix) && unix > 0) {
            return unix * 1000;
        }
        var raw = String(order.createdAtRaw || order.createdAt || order.created_at || "").trim();
        // Repository timestamps without an offset are shop-local, not browser-local.
        if (/^\d{4}-\d{2}-\d{2}[ T]\d{2}:\d{2}(?::\d{2})?$/.test(raw)) {
            raw = raw.replace(" ", "T") + "+08:00";
        } else if (validDate(raw)) {
            raw += "T00:00:00+08:00";
        }
        var timestamp = raw ? Date.parse(raw) : NaN;
        return Number.isFinite(timestamp) ? timestamp : null;
    }

    function orderDate(order, field) {
        if (field !== "created") {
            return validDate(order[field + "Date"] || order[field + "_date"]);
        }
        var timestamp = createdTimestamp(order);
        if (timestamp === null) {
            return "";
        }
        var parts = {};
        dateFormatter.formatToParts(new Date(timestamp)).forEach(function (part) { parts[part.type] = part.value; });
        return parts.year + "-" + parts.month + "-" + parts.day;
    }

    function dateSortValue(order, field) {
        if (field === "created") {
            return createdTimestamp(order);
        }
        var date = orderDate(order, field);
        if (!date) {
            return null;
        }
        var time = String(order[field + "Time"] || order[field + "_time"] || "00:00");
        return date + "T" + (/^\d{2}:\d{2}$/.test(time) ? time : "00:00");
    }

    function queryOrders(orders, state, scope) {
        state = normalizeState(state, scope);
        if (state.from && state.to && state.from > state.to) {
            return [];
        }
        var terms = normalizeText(state.search).split(/\s+/).filter(Boolean);
        var sortParts = state.sort.split("-");
        var sortField = sortParts[0];
        var direction = sortParts[1] === "desc" ? -1 : 1;
        return (Array.isArray(orders) ? orders : []).map(function (order, index) {
            return { order: order, index: index, names: itemNames(order) };
        }).filter(function (entry) {
            var order = entry.order;
            if (!matchesStatus(order, state.status)) {
                return false;
            }
            if (state.camera && !entry.names.some(function (name) { return normalizeText(name) === state.camera; })) {
                return false;
            }
            var searchText = normalizeText([order.id, order.orderNumber, order.name, order.email, entry.names.join(" ")].join(" "));
            if (!terms.every(function (term) { return searchText.indexOf(term) !== -1; })) {
                return false;
            }
            if (state.from || state.to) {
                var date = orderDate(order, state.dateField);
                if (!date || (state.from && date < state.from) || (state.to && date > state.to)) {
                    return false;
                }
            }
            return true;
        }).sort(function (left, right) {
            var comparison = 0;
            if (sortField === "queue") {
                var queueRank = function (order) { return statusToken(order) === "pending" ? (order.isPriorityCustomer ? 0 : 1) : 2; };
                comparison = queueRank(left.order) - queueRank(right.order);
            } else if (sortField === "name" || sortField === "camera") {
                var leftLabel = sortField === "name" ? String(left.order.name || "") : left.names.slice().sort(collator.compare).join(", ");
                var rightLabel = sortField === "name" ? String(right.order.name || "") : right.names.slice().sort(collator.compare).join(", ");
                if (!leftLabel || !rightLabel) {
                    comparison = leftLabel ? -1 : (rightLabel ? 1 : 0);
                } else {
                    comparison = collator.compare(leftLabel, rightLabel) * direction;
                }
            } else {
                var leftDate = dateSortValue(left.order, sortField);
                var rightDate = dateSortValue(right.order, sortField);
                if (leftDate === null || rightDate === null) {
                    comparison = leftDate === null ? (rightDate === null ? 0 : 1) : -1;
                } else {
                    comparison = (leftDate < rightDate ? -1 : (leftDate > rightDate ? 1 : 0)) * direction;
                }
            }
            return comparison || left.index - right.index;
        }).map(function (entry) { return entry.order; });
    }

    function syncCameraOptions(controller, orders) {
        var options = new Map();
        orders.forEach(function (order) {
            itemNames(order).forEach(function (name) { options.set(normalizeText(name), name); });
        });
        var signature = JSON.stringify(Array.from(options).sort(function (a, b) { return collator.compare(a[1], b[1]); }));
        if (signature === controller.cameraSignature) {
            return;
        }
        controller.cameraSignature = signature;
        var select = controller.form.elements.namedItem("camera");
        select.replaceChildren(new Option("All cameras and packages", ""));
        JSON.parse(signature).forEach(function (entry) { select.add(new Option(entry[1], entry[0])); });
        if (controller.state.camera && !options.has(controller.state.camera)) {
            select.add(new Option(controller.state.camera + " (no current reservations)", controller.state.camera));
        }
        select.value = controller.state.camera;
    }

    function updateSummary(controller, total, count) {
        var invalid = Boolean(controller.state.from && controller.state.to && controller.state.from > controller.state.to);
        var error = controller.form.querySelector("[data-history-error]");
        error.hidden = !invalid;
        error.textContent = invalid ? "From date must be on or before To date." : "";
        controller.form.elements.namedItem("to").setCustomValidity(invalid ? error.textContent : "");
        controller.form.elements.namedItem("to").setAttribute("aria-invalid", invalid ? "true" : "false");
        controller.form.querySelector("[data-history-summary]").textContent = invalid
            ? "Correct the date range to see matching reservations."
            : "Showing " + count + " of " + total + " reservation" + (total === 1 ? "" : "s") + ". Dates use Philippine time.";
    }

    function selectOrders(scope, orders) {
        var controller = controllers[scope];
        if (!controller) {
            return queryOrders(orders, {}, scope);
        }
        syncCameraOptions(controller, orders);
        var selected = queryOrders(orders, controller.state, scope);
        updateSummary(controller, orders.length, selected.length);
        return selected;
    }

    function renderAdmin() {
        var orders = Array.isArray(root.__creatyAdminBookings) ? root.__creatyAdminBookings : [];
        var selected = selectOrders("admin", orders);
        var rows = new Map();
        root.document.querySelectorAll("[data-admin-booking-row]").forEach(function (row) {
            row.hidden = true;
            rows.set(row.getAttribute("data-admin-booking-id"), row);
        });
        selected.forEach(function (order) {
            var row = rows.get(String(order.id || ""));
            if (row) {
                row.hidden = false;
                row.parentElement.appendChild(row);
            }
        });
        var empty = root.document.querySelector("[data-admin-bookings-empty]");
        if (empty) {
            empty.hidden = selected.length > 0;
            empty.firstElementChild.textContent = orders.length ? "No reservations match these filters. Adjust or reset the filters above." : "No reservations yet.";
        }
    }

    function initialize(form) {
        var scope = form.getAttribute("data-transaction-history-controls");
        var storageKey = "creaty_history_filters_v1:" + root.location.pathname + ":" + form.getAttribute("data-history-state-key");
        var saved = {};
        try { saved = JSON.parse(root.sessionStorage.getItem(storageKey)) || {}; } catch (error) { /* Storage is optional. */ }
        var controller = { form: form, state: normalizeState(saved, scope), onChange: scope === "admin" ? renderAdmin : null };
        controllers[scope] = controller;
        Object.keys(controller.state).forEach(function (name) {
            if (name !== "camera") {
                form.elements.namedItem(name).value = controller.state[name];
            }
        });
        function update() {
            var values = {};
            Object.keys(controller.state).forEach(function (name) { values[name] = form.elements.namedItem(name).value; });
            controller.state = normalizeState(values, scope);
            try { root.sessionStorage.setItem(storageKey, JSON.stringify(controller.state)); } catch (error) { /* Storage is optional. */ }
            if (controller.onChange) {
                controller.onChange();
            }
        }
        form.addEventListener("submit", function (event) { event.preventDefault(); update(); });
        form.addEventListener("input", update);
        form.addEventListener("change", update);
        form.addEventListener("reset", function (event) {
            event.preventDefault();
            controller.state = normalizeState({}, scope);
            Object.keys(controller.state).forEach(function (name) { form.elements.namedItem(name).value = controller.state[name]; });
            update();
        });
        if (controller.onChange) {
            controller.onChange();
        }
    }

    var api = {
        queryOrders: queryOrders,
        normalizeState: normalizeState,
        orderDate: orderDate,
        createdTimestamp: createdTimestamp,
        selectOrders: selectOrders,
        setRenderer: function (scope, callback) {
            if (controllers[scope]) {
                controllers[scope].onChange = callback;
            }
        }
    };
    if (typeof module !== "undefined" && module.exports) {
        module.exports = api;
    }
    root.CreatyTransactionHistory = api;
    if (root.document) {
        root.document.querySelectorAll("[data-transaction-history-controls]").forEach(initialize);
    }
})(typeof window !== "undefined" ? window : globalThis);
