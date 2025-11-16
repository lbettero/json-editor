// menu-editor.js
(function () {

    /* -----------------------------------------
     *  GET NEXT INDEX FOR NEW ITEMS
     * ----------------------------------------- */
    function getNextIndex(container, prefix) {
        let maxIndex = -1;
        const escPrefix = prefix.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');

        const inputs = container.querySelectorAll('input[name^="' + prefix + '["]');
        inputs.forEach(input => {
            const re = new RegExp('^' + escPrefix + '\\[(\\d+)]\\[');
            const match = input.name.match(re);
            if (match) {
                const idx = parseInt(match[1], 10);
                if (!Number.isNaN(idx) && idx > maxIndex) {
                    maxIndex = idx;
                }
            }
        });

        return maxIndex + 1;
    }


    /* -----------------------------------------
     *  TEMPLATE FOR NEW ITEMS
     * ----------------------------------------- */
    function createItemHTML(prefix, index, level) {
        const blockName   = prefix + '[' + index + ']';
        const childPrefix = blockName + '[children]';
        const canAddChild = level < 2;

        return `
        <div class="menu-item border border-gray-300 rounded-md p-4 my-4 bg-white shadow-sm" data-level="${level}">
            
            <!-- Header + toggle -->
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-700">New item (level ${level + 1})</h3>
                <button type="button"
                        class="btn-toggle-item text-sm text-gray-500 inline-flex items-center gap-1"
                        aria-expanded="true">
                    <span class="icon">▼</span>
                    <span class="label">Collapse</span>
                </button>
            </div>

            <!-- Collapsible body -->
            <div class="menu-item-body space-y-4">
                
                <!-- Title -->
                <div>
                    <label class="block text-sm font-medium text-gray-600 mb-1">Title</label>
                    <input type="text"
                           name="${blockName}[title]"
                           class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring focus:ring-blue-200">
                </div>

                <!-- URL -->
                <div>
                    <label class="block text-sm font-medium text-gray-600 mb-1">URL</label>
                    <input type="text"
                           name="${blockName}[url]"
                           class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring focus:ring-blue-200">
                </div>

                <!-- Tags -->
                <div>
                    <label class="block text-sm font-medium text-gray-600 mb-1">Tags (comma-separated)</label>
                    <input type="text"
                           name="${blockName}[tags]"
                           class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring focus:ring-blue-200">
                </div>

                <!-- Actions -->
                <div class="mt-2 flex flex-wrap gap-2 menu-actions">
                    
                    <button type="button"
                            class="btn-action btn-add-sibling inline-flex items-center gap-1 px-3 py-1 border border-gray-300 rounded-md text-sm bg-white hover:bg-gray-50">
                        <img src="/assets/icons/additem.png" class="w-4 h-4">
                        Add new item
                    </button>

                    ${canAddChild ? `
                    <button type="button"
                            class="btn-action btn-add-child inline-flex items-center gap-1 px-3 py-1 border border-gray-300 rounded-md text-sm bg-white hover:bg-gray-50">
                        <img src="/assets/icons/additem.png" class="w-4 h-4">
                        Add subitem
                    </button>` : ''}

                    <button type="button"
                            class="btn-action btn-delete-item inline-flex items-center gap-1 px-3 py-1 border border-red-300 rounded-md bg-red-50 text-red-700 hover:bg-red-100">
                        <img src="/assets/icons/deleteitem.png" class="w-4 h-4">
                        Remove item
                    </button>

                </div>

                <!-- Children -->
                <div class="menu-children mt-4 pl-4 border-l border-dashed border-gray-300"
                     data-name-prefix="${childPrefix.replace(/"/g, '&quot;')}"
                     data-level="${level + 1}">
                </div>

            </div>
        </div>`;
    }


    /* -----------------------------------------
     *  EVENT LISTENER FOR ALL BUTTONS
     * ----------------------------------------- */
    document.addEventListener('click', function (e) {

        /* ------------------------------
         *  Expand/Collapse
         * ------------------------------ */
        const toggleBtn = e.target.closest('.btn-toggle-item');
        if (toggleBtn) {
            const item = toggleBtn.closest('.menu-item');
            const body = item.querySelector('.menu-item-body');
            if (!body) return;

            const icon  = toggleBtn.querySelector('.icon');
            const label = toggleBtn.querySelector('.label');

            const isHidden = body.classList.toggle('hidden');
            toggleBtn.setAttribute('aria-expanded', String(!isHidden));

            icon.textContent  = isHidden ? '▶' : '▼';
            label.textContent = isHidden ? 'Expand' : 'Collapse';

            return;
        }


        /* ------------------------------
         *  Add sibling item
         * ------------------------------ */
        const addSiblingBtn = e.target.closest('.btn-add-sibling');
        if (addSiblingBtn) {
            const item = addSiblingBtn.closest('.menu-item');
            const container = item.parentElement;
            const prefix    = container.dataset.namePrefix;
            const level     = parseInt(container.dataset.level || "0", 10);

            const newIndex = getNextIndex(container, prefix);
            const html     = createItemHTML(prefix, newIndex, level);

            item.insertAdjacentHTML('afterend', html);
            return;
        }


        /* ------------------------------
         *  Add child item
         * ------------------------------ */
        const addChildBtn = e.target.closest('.btn-add-child');
        if (addChildBtn) {
            const item = addChildBtn.closest('.menu-item');
            const container = item.querySelector(':scope > .menu-item-body > .menu-children');
            if (!container) return;

            const prefix = container.dataset.namePrefix;
            const level  = parseInt(container.dataset.level || "0", 10);

            const newIndex = getNextIndex(container, prefix);
            const html     = createItemHTML(prefix, newIndex, level);

            container.insertAdjacentHTML('beforeend', html);
            return;
        }


        /* ------------------------------
         *  Delete item
         * ------------------------------ */
        const deleteBtn = e.target.closest('.btn-delete-item');
        if (deleteBtn) {
            const item = deleteBtn.closest('.menu-item');
            if (item && confirm("Remove this item and all its subitems?")) {
                item.remove();
            }
            return;
        }


        /* ------------------------------
         *  Add ROOT item
         * ------------------------------ */
        const addRootBtn = e.target.closest('.btn-add-root');
        if (addRootBtn) {
            const container = document.getElementById('menuRoot');
            const prefix    = container.dataset.namePrefix;
            const level     = parseInt(container.dataset.level || "0", 10);

            const newIndex = getNextIndex(container, prefix);
            const html     = createItemHTML(prefix, newIndex, level);

            container.insertAdjacentHTML('beforeend', html);
            return;
        }
    });

})();
