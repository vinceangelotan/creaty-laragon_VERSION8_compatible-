(function () {
    "use strict";

    function initEquipmentInventory() {
        var panel = document.querySelector('[data-admin-dashboard-panel="equipments"]');
        if (!panel) return;

        var search = panel.querySelector("[data-equipment-search]");
        var toggleAll = panel.querySelector("[data-equipment-toggle-all]");
        var results = panel.querySelector("[data-equipment-results]");
        var emptySearch = panel.querySelector("[data-equipment-empty-search]");
        var storageKey = "creaty-equipment-expanded:" + window.location.pathname;
        var expandedKeys = [];

        try {
            var storedKeys = JSON.parse(window.sessionStorage.getItem(storageKey) || "[]");
            if (Array.isArray(storedKeys)) expandedKeys = storedKeys;
        } catch (error) {
            // Inventory remains usable when browser storage is unavailable.
        }

        var groups = Array.from(panel.querySelectorAll("[data-equipment-group]")).map(function (row) {
            var button = row.querySelector("[data-equipment-expand]");
            return {
                row: row,
                button: button,
                detail: document.getElementById(button.getAttribute("aria-controls")),
                key: row.getAttribute("data-product-key"),
                searchText: (row.getAttribute("data-search") || "").toLocaleLowerCase(),
                quantity: Number(row.getAttribute("data-quantity")) || 0
            };
        });

        function setExpanded(group, expanded) {
            group.button.setAttribute("aria-expanded", String(expanded));
            group.detail.hidden = !expanded || group.row.hidden;
            group.row.classList.toggle("is-expanded", expanded);
            group.button.querySelector("[data-equipment-expand-label]").textContent = expanded ? "Hide unit IDs" : "View unit IDs";
        }

        function rememberExpansion(group, expanded) {
            expandedKeys = expandedKeys.filter(function (key) { return key !== group.key; });
            if (expanded) expandedKeys.push(group.key);
            try {
                window.sessionStorage.setItem(storageKey, JSON.stringify(expandedKeys));
            } catch (error) {
                // Persistence is optional; expansion still works for this page.
            }
        }

        function updateToggleAll() {
            var visible = groups.filter(function (group) { return !group.row.hidden; });
            var allExpanded = visible.length > 0 && visible.every(function (group) {
                return group.button.getAttribute("aria-expanded") === "true";
            });
            toggleAll.disabled = visible.length === 0;
            toggleAll.textContent = allExpanded ? "Collapse all models" : "Expand all models";
            toggleAll.setAttribute("data-expanded", String(allExpanded));
        }

        function filterGroups() {
            var query = search.value.trim().toLocaleLowerCase();
            var words = query.split(/\s+/).filter(Boolean);
            var visibleModels = 0;
            var visibleUnits = 0;

            groups.forEach(function (group) {
                var matches = words.every(function (word) { return group.searchText.indexOf(word) !== -1; });
                group.row.hidden = !matches;
                setExpanded(group, matches && (query !== "" || expandedKeys.indexOf(group.key) !== -1));
                if (matches) {
                    visibleModels++;
                    visibleUnits += group.quantity;
                }
            });

            results.textContent = visibleModels + (visibleModels === 1 ? " model" : " models") + " · " + visibleUnits + (visibleUnits === 1 ? " unit" : " units");
            emptySearch.hidden = query === "" || visibleModels > 0;
            updateToggleAll();
        }

        groups.forEach(function (group) {
            function toggleGroup() {
                var expanded = group.button.getAttribute("aria-expanded") !== "true";
                setExpanded(group, expanded);
                rememberExpansion(group, expanded);
                updateToggleAll();
            }

            group.button.addEventListener("click", toggleGroup);
            group.row.addEventListener("click", function (event) {
                if (!event.target.closest("button, a, input, select, form")) toggleGroup();
            });
        });

        search.addEventListener("input", filterGroups);
        toggleAll.addEventListener("click", function () {
            var expanded = toggleAll.getAttribute("data-expanded") !== "true";
            groups.forEach(function (group) {
                if (group.row.hidden) return;
                setExpanded(group, expanded);
                rememberExpansion(group, expanded);
            });
            updateToggleAll();
        });

        filterGroups();
    }

    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", initEquipmentInventory);
    } else {
        initEquipmentInventory();
    }
}());
