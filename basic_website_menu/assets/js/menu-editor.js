/**
 * MENU EDITOR – JS MODULE (UPDATED FOR GLOBAL FIELDS)
 * ---------------------------------------------------
 * - Collapse/expand items
 * - Add/remove/move/indent/outdent menu items
 * - Dynamic reindex of name="" attributes
 * - NEW: Add/remove global fields
 * - NEW: Propagation rigid of global fields to all items
 */

(function () {

    // ---------------------------------------------------------------
    // UTILITIES
    // ---------------------------------------------------------------

    function getNextIndex(container) {
        return container.querySelectorAll(':scope > .menu-item').length;
    }

    function createItemHTML(prefix, index, level) {
        const blockName   = `${prefix}[${index}]`;
        const childPrefix = `${blockName}[children]`;
        const canAddChild = level < 2;

        // Global fields (cloned later)
        const globalFieldsHTML = buildGlobalFieldsHTML(blockName);

        return `
        <div class="menu-item border border-gray-300 rounded-md p-4 my-4 bg-white shadow-sm" 
             data-level="${level}">

            <div class="menu-item-header flex items-center justify-between gap-2">
                <h3 class="text-lg font-semibold text-gray-700">New item (level ${level+1})</h3>

                <button type="button"
                        class="btn-toggle-item inline-flex items-center gap-1 text-xs px-2 py-1 border rounded bg-gray-50">
                    <span class="toggle-label">Collapse</span>
                    <span class="toggle-icon">▾</span>
                </button>
            </div>

            <div class="menu-item-body mt-4">

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-600">Title</label>
                    <input type="text" name="${blockName}[title]"
                           class="w-full border rounded px-3 py-2">
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-600">URL</label>
                    <input type="text" name="${blockName}[url]"
                           class="w-full border rounded px-3 py-2">
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-600">Tags</label>
                    <input type="text" name="${blockName}[tags]"
                           class="w-full border rounded px-3 py-2">
                </div>

                <!-- GLOBAL FIELDS -->
                <div class="global-fields-per-item mt-4 p-3 border rounded bg-blue-50"
                     data-block="${blockName}">
                     ${globalFieldsHTML}
                </div>

                <div class="mt-2 flex flex-wrap gap-2 menu-actions">
                    <button type="button" class="btn-action btn-move-up px-2 py-1 border text-xs">↑ Move up</button>
                    <button type="button" class="btn-action btn-move-down px-2 py-1 border text-xs">↓ Move down</button>
                    <button type="button" class="btn-action btn-indent px-2 py-1 border text-xs">→ Indent</button>
                    <button type="button" class="btn-action btn-outdent px-2 py-1 border text-xs">← Outdent</button>

                    <button type="button" class="btn-action btn-add-sibling px-3 py-1 border text-sm">
                        <img src="/assets/icons/additem.png" class="w-4 h-4"> Add new item
                    </button>

                    ${canAddChild ? `
                    <button type="button" class="btn-action btn-add-child px-3 py-1 border text-sm">
                        <img src="/assets/icons/additem.png" class="w-4 h-4"> Add subitem
                    </button>` : ''}

                    <button type="button"
                            class="btn-action btn-delete-item px-3 py-1 border text-sm bg-red-50 text-red-700">
                        <img src="/assets/icons/deleteitem.png" class="w-4 h-4"> Remove
                    </button>
                </div>

                <div class="menu-children mt-4 pl-4 border-l border-dashed"
                     data-name-prefix="${childPrefix}"
                     data-level="${level+1}">
                </div>
            </div>
        </div>`;
    }

    // ---------------------------------------------------------------
    // GLOBAL FIELDS MANAGEMENT
    // ---------------------------------------------------------------

    function buildGlobalFieldsHTML(blockName) {
        const container = document.getElementById("globalFieldsContainer");
        if (!container) return "";

        const fields = container.querySelectorAll(".global-field-item");
        let html = "";

        fields.forEach((row) => {
            const fname  = row.querySelector('[name="globalFields[name][]"]').value.trim();
            const ftype  = row.querySelector('[name="globalFields[type][]"]').value;
            const fvalue = row.querySelector('[name="globalFields[default][]"]').value;

            if (!fname) return;

            html += `
                <div class="mb-2 global-field-row" data-field="${fname}">
                    <label class="text-xs font-medium text-gray-700">
                        ${fname}
                        <span class="text-gray-400">(${ftype})</span>
                    </label>
                    
                    ${ftype === 'boolean'
                        ? `<select name="${blockName}[${fname}]" class="border rounded px-2 py-1 text-sm">
                               <option value="true"  ${fvalue=="true"?"selected":""}>true</option>
                               <option value="false" ${fvalue=="false"?"selected":""}>false</option>
                           </select>`
                        : `<input type="text" 
                                   name="${blockName}[${fname}]"
                                   value="${escapeHtml(fvalue)}"
                                   class="w-full border rounded px-2 py-1 text-sm">`
                    }
                </div>`;
        });

        return html;
    }

    function propagateGlobalFieldsToAllItems() {
        const allItems = document.querySelectorAll(".global-fields-per-item");
        allItems.forEach(block => {
            const blockName = block.dataset.block;
            block.innerHTML = buildGlobalFieldsHTML(blockName);
        });
    }

    function escapeHtml(txt) {
        return txt.replace(/&/g, "&amp;")
                  .replace(/</g, "&lt;")
                  .replace(/>/g, "&gt;")
                  .replace(/"/g, "&quot;");
    }

    // ---------------------------------------------------------------
    // COLLAPSE / EXPAND
    // ---------------------------------------------------------------

    function toggleItem(item) {
        const body  = item.querySelector(':scope > .menu-item-body');
        const label = item.querySelector('.toggle-label');
        const icon  = item.querySelector('.toggle-icon');

        const isHidden = body.classList.toggle('hidden');
        if (isHidden) {
            label.textContent = "Expand";
            icon.textContent  = "▸";
        } else {
            label.textContent = "Collapse";
            icon.textContent  = "▾";
        }
    }

    // ---------------------------------------------------------------
    // LEVEL + NAME REBUILD
    // ---------------------------------------------------------------

    function updateLevels() {
        const root = document.getElementById("menuRoot");
        if (!root) return;

        function walk(c, level) {
            c.dataset.level = String(level);

            const items = c.querySelectorAll(':scope > .menu-item');
            items.forEach(item => {
                item.dataset.level = String(level);

                const kids = item.querySelector(':scope > .menu-item-body > .menu-children');
                if (kids) walk(kids, level + 1);
            });
        }

        walk(root, 0);
    }

    function rebuildNames() {
        const root = document.getElementById("menuRoot");
        if (!root) return;

        function walk(container, prefix) {
            const items = container.querySelectorAll(':scope > .menu-item');
            items.forEach((item, index) => {

                const blockName = `${prefix}[${index}]`;

                // Standard fields
                const title = item.querySelector('input[name$="[title]"]');
                if (title) title.name = `${blockName}[title]`;

                const url = item.querySelector('input[name$="[url]"]');
                if (url) url.name = `${blockName}[url]`;

                const tags = item.querySelector('input[name$="[tags]"]');
                if (tags) tags.name = `${blockName}[tags]`;

                // Global fields
                const globalBlock = item.querySelector(".global-fields-per-item");
                if (globalBlock) {
                    globalBlock.dataset.block = blockName;

                    const fields = globalBlock.querySelectorAll("[data-field]");
                    fields.forEach(f => {
                        const fname = f.dataset.field;
                        const input = f.querySelector("input,select");
                        if (input) input.name = `${blockName}[${fname}]`;
                    });
                }

                // Children
                const kids = item.querySelector(':scope > .menu-item-body > .menu-children');
                if (kids) {
                    kids.dataset.namePrefix = `${blockName}[children]`;
                    walk(kids, `${blockName}[children]`);
                }
            });
        }

        walk(root, "menu");
    }

    // ---------------------------------------------------------------
    // EVENT HANDLERS
    // ---------------------------------------------------------------

    document.addEventListener("click", function (e) {

        // Collapse/expand
        const toggleBtn = e.target.closest(".btn-toggle-item");
        if (toggleBtn) {
            const item = toggleBtn.closest(".menu-item");
            toggleItem(item);
            return;
        }

        // Add sibling
        const addSiblingBtn = e.target.closest(".btn-add-sibling");
        if (addSiblingBtn) {
            const item = addSiblingBtn.closest(".menu-item");
            const parent = item.parentElement;
            const prefix = parent.dataset.namePrefix;
            const level  = parseInt(parent.dataset.level || "0");

            const newIndex = getNextIndex(parent);
            const html = createItemHTML(prefix, newIndex, level);

            item.insertAdjacentHTML("afterend", html);
            updateLevels();
            propagateGlobalFieldsToAllItems();
            return;
        }

        // Add child
        const addChildBtn = e.target.closest(".btn-add-child");
        if (addChildBtn) {
            const item = addChildBtn.closest(".menu-item");
            const kids = item.querySelector(":scope > .menu-item-body > .menu-children");

            const prefix = kids.dataset.namePrefix;
            const level  = parseInt(kids.dataset.level || "0");

            const newIndex = getNextIndex(kids);
            const html = createItemHTML(prefix, newIndex, level);

            kids.insertAdjacentHTML("beforeend", html);
            updateLevels();
            propagateGlobalFieldsToAllItems();
            return;
        }

        // Delete item
        const delBtn = e.target.closest(".btn-delete-item");
        if (delBtn) {
            const item = delBtn.closest(".menu-item");
            if (confirm("Remove this item and all subitems?")) {
                item.remove();
                updateLevels();
            }
            return;
        }

        // Add root
        const addRoot = e.target.closest(".btn-add-root");
        if (addRoot) {
            const root = document.getElementById("menuRoot");
            const prefix = root.dataset.namePrefix;

            const newIndex = getNextIndex(root);
            const html     = createItemHTML(prefix, newIndex, 0);

            root.insertAdjacentHTML("beforeend", html);
            updateLevels();
            propagateGlobalFieldsToAllItems();
            return;
        }

        // Move up
        const moveUp = e.target.closest(".btn-move-up");
        if (moveUp) {
            const item = moveUp.closest(".menu-item");
            const prev = item.previousElementSibling;

            if (prev) prev.before(item);
            updateLevels();
            return;
        }

        // Move down
        const moveDown = e.target.closest(".btn-move-down");
        if (moveDown) {
            const item = moveDown.closest(".menu-item");
            const next = item.nextElementSibling;

            if (next) next.after(item);
            updateLevels();
            return;
        }

        // Indent (make child)
        const indent = e.target.closest(".btn-indent");
        if (indent) {
            const item = indent.closest(".menu-item");
            const prev = item.previousElementSibling;

            if (!prev) return;

            const kids = prev.querySelector(":scope > .menu-item-body > .menu-children");
            kids.appendChild(item);

            updateLevels();
            return;
        }

        // Outdent
        const outdent = e.target.closest(".btn-outdent");
        if (outdent) {
            const item = outdent.closest(".menu-item");
            const parentKids = item.parentElement;
            const parentItem = parentKids.closest(".menu-item");

            if (!parentItem) return;

            const grandKids = parentItem.parentElement;
            grandKids.insertBefore(item, parentItem.nextElementSibling);

            updateLevels();
            return;
        }

        // Remove global field
        const rmGlobal = e.target.closest(".btn-remove-global-field");
        if (rmGlobal) {
            rmGlobal.closest(".global-field-item").remove();
            propagateGlobalFieldsToAllItems();
            return;
        }
    });

    // ---------------------------------------------------------------
    // FORM SUBMIT
    // ---------------------------------------------------------------

    document.addEventListener("DOMContentLoaded", function () {

        updateLevels();
        propagateGlobalFieldsToAllItems();

        const form = document.getElementById("menuEditorForm");
        if (form) {
            form.addEventListener("submit", function () {
                rebuildNames();
            });
        }

        // ADD GLOBAL FIELD BUTTON
        const addGlobalBtn = document.getElementById("btnAddGlobalField");
        if (addGlobalBtn) {
            addGlobalBtn.addEventListener("click", function () {

                const container = document.getElementById("globalFieldsContainer");
                const row = document.createElement("div");

                row.className = "global-field-item flex items-center gap-3 mb-2";
                row.innerHTML = `
                    <input type="text" class="border rounded px-2 py-1 text-sm w-40"
                           name="globalFields[name][]" placeholder="newField">

                    <select name="globalFields[type][]" class="border rounded px-2 py-1 text-sm">
                        <option value="string">string</option>
                        <option value="number">number</option>
                        <option value="boolean">boolean</option>
                        <option value="json">json</option>
                    </select>

                    <input type="text"
                           class="border rounded px-2 py-1 text-sm flex-grow"
                           name="globalFields[default][]" placeholder="default value">

                    <button type="button"
                            class="btn-remove-global-field text-red-600 text-xs px-2 py-1 border border-red-300 rounded">
                        Remove
                    </button>
                `;

                container.appendChild(row);
                propagateGlobalFieldsToAllItems();
            });
        }
    });

})();
