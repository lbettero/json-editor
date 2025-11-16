<?php
// src/functions/menu-editor.php

// Reuse JSON reading functions
require_once __DIR__ . '/menu.php';

$menuPath  = __DIR__ . '/../../assets/data/menu.json';
$menuData  = getMenuData($menuPath);
$menuError = $menuData['error'] ?? null;
$items     = $menuError ? [] : $menuData;

/**
 * Renders menu fields (recursive).
 *
 * @param array  $items      Menu items at this level
 * @param string $namePrefix Name prefix for inputs (ex: menu, menu[0][children], etc.)
 * @param int    $level      Depth level (0 = root, 1 = child, 2 = grandchild)
 */
function renderMenuFields(array $items, string $namePrefix = 'menu', int $level = 0): void
{
    foreach ($items as $index => $item) {
        $blockName   = $namePrefix . '[' . $index . ']';
        $childPrefix = $blockName . '[children]';
        $canAddChild = $level < 2; // up to level 2 you can add sub-items
        ?>
        <div class="menu-item border border-gray-300 rounded-md p-4 my-4 bg-white shadow-sm"
             data-level="<?= $level ?>">

            <!-- HEADER: title + collapse/expand -->
            <div class="menu-item-header flex items-center justify-between gap-2">
                <h3 class="text-lg font-semibold text-gray-700">
                    Level <?= $level + 1 ?> item —
                    <span class="text-gray-500">
                        <?= htmlspecialchars($item['title'] ?? 'untitled') ?>
                    </span>
                </h3>

                <button type="button"
                        class="btn-toggle-item inline-flex items-center gap-1 text-xs px-2 py-1 border border-gray-300 rounded-md bg-gray-50 hover:bg-gray-100">
                    <span class="toggle-label">Collapse</span>
                    <span class="toggle-icon">▾</span>
                </button>
            </div>

            <!-- BODY: fields, actions, children -->
            <div class="menu-item-body mt-4">

                <!-- Title Field -->
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-600 mb-1">Title</label>
                    <input type="text"
                           name="<?= $blockName ?>[title]"
                           value="<?= htmlspecialchars($item['title'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                           class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring focus:ring-blue-200">
                </div>

                <!-- URL Field -->
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-600 mb-1">URL</label>
                    <input type="text"
                           name="<?= $blockName ?>[url]"
                           value="<?= htmlspecialchars($item['url'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                           class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring focus:ring-blue-200">
                </div>

                <!-- Tags Field -->
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-600 mb-1">Tags (comma-separated)</label>
                    <input type="text"
                           name="<?= $blockName ?>[tags]"
                           value="<?= htmlspecialchars(isset($item['tags']) ? implode(', ', $item['tags']) : '', ENT_QUOTES, 'UTF-8') ?>"
                           class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring focus:ring-blue-200">
                </div>

                <!-- Item Actions -->
                <div class="mt-2 flex flex-wrap gap-2 menu-actions">

                    <!-- Order controls -->
                    <button type="button"
                            class="btn-action btn-move-up inline-flex items-center gap-1 px-2 py-1 border border-gray-300 rounded-md text-xs bg-white hover:bg-gray-50">
                        ↑ Move up
                    </button>

                    <button type="button"
                            class="btn-action btn-move-down inline-flex items-center gap-1 px-2 py-1 border border-gray-300 rounded-md text-xs bg-white hover:bg-gray-50">
                        ↓ Move down
                    </button>

                    <button type="button"
                            class="btn-action btn-indent inline-flex items-center gap-1 px-2 py-1 border border-gray-300 rounded-md text-xs bg-white hover:bg-gray-50">
                        → Indent
                    </button>

                    <button type="button"
                            class="btn-action btn-outdent inline-flex items-center gap-1 px-2 py-1 border border-gray-300 rounded-md text-xs bg-white hover:bg-gray-50">
                        ← Outdent
                    </button>

                    <!-- Add new sibling item -->
                    <button type="button"
                            class="btn-action btn-add-sibling inline-flex items-center gap-1 px-3 py-1 border border-gray-300 rounded-md text-sm bg-white hover:bg-gray-50">
                        <img src="/assets/icons/additem.png" alt="Add item" class="w-4 h-4">
                        Add new item
                    </button>

                    <!-- Add child item -->
                    <?php if ($canAddChild): ?>
                        <button type="button"
                                class="btn-action btn-add-child inline-flex items-center gap-1 px-3 py-1 border border-gray-300 rounded-md text-sm bg-white hover:bg-gray-50">
                            <img src="/assets/icons/additem.png" alt="Add subitem" class="w-4 h-4">
                            Add subitem
                        </button>
                    <?php endif; ?>

                    <!-- Delete item -->
                    <button type="button"
                            class="btn-action btn-delete-item inline-flex items-center gap-1 px-3 py-1 border border-red-300 rounded-md text-sm bg-red-50 hover:bg-red-100 text-red-700">
                        <img src="/assets/icons/deleteitem.png" alt="Delete item" class="w-4 h-4">
                        Remove item
                    </button>
                </div>

                <!-- Children container -->
                <div class="menu-children mt-4 pl-4 border-l border-dashed border-gray-300"
                     data-name-prefix="<?= htmlspecialchars($childPrefix, ENT_QUOTES, 'UTF-8') ?>"
                     data-level="<?= $level + 1 ?>">
                    <?php
                    if (!empty($item['children']) && is_array($item['children'])) {
                        renderMenuFields($item['children'], $childPrefix, $level + 1);
                    }
                    ?>
                </div>
            </div>
        </div>
        <?php
    }
}
?>

<!-- Main Form -->
<form id="menuEditorForm" method="post" action="/src/functions/save-menu.php" class="mt-6">

    <?php if ($menuError): ?>
        <div class="bg-red-100 border border-red-300 text-red-700 px-4 py-3 rounded mb-4">
            <?= htmlspecialchars($menuError, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <div id="menuRoot"
         class="menu-children space-y-4"
         data-name-prefix="menu"
         data-level="0">
        <?php
        if (!$menuError && !empty($items)) {
            renderMenuFields($items, 'menu', 0);
        } else {
            echo '<p class="text-gray-600">No menu items found.</p>';
        }
        ?>
    </div>

    <!-- Add root-level item -->
    <div class="mt-4">
        <button type="button"
                class="btn-action btn-add-root inline-flex items-center gap-1 px-3 py-1 border border-gray-300 rounded-md text-sm bg-white hover:bg-gray-50">
            <img src="/assets/icons/additem.png" alt="Add item" class="w-4 h-4">
            Add new top-level item
        </button>
    </div>

    <!-- Save + Back -->
    <div class="mt-6 flex gap-3">

        <!-- SAVE BUTTON (enabled, with icon) -->
        <button type="submit"
                class="inline-flex items-center gap-2 px-5 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
            <img src="/assets/icons/recordjson.png" alt="Save JSON" class="w-5 h-5">
            Save menu.json
        </button>

        <button type="button"
                onclick="history.back()"
                class="px-5 py-2 bg-gray-200 text-gray-800 rounded-md">
            Back
        </button>
    </div>
</form>

<!-- JS to dynamically add/remove items, reorder and collapse -->
<script>
(function () {

    /**
     * Returns the next index for a new item in the given container.
     * Uses the number of existing .menu-item elements (DOM order).
     */
    function getNextIndex(container) {
        return container.querySelectorAll(':scope > .menu-item').length;
    }

    /**
     * Creates HTML for a new empty menu item.
     */
    function createItemHTML(prefix, index, level) {
        const blockName   = prefix + '[' + index + ']';
        const childPrefix = blockName + '[children]';
        const canAddChild = level < 2;

        return `
        <div class="menu-item border border-gray-300 rounded-md p-4 my-4 bg-white shadow-sm" data-level="${level}">
            <div class="menu-item-header flex items-center justify-between gap-2">
                <h3 class="text-lg font-semibold text-gray-700">
                    New item (level ${level + 1})
                </h3>
                <button type="button"
                        class="btn-toggle-item inline-flex items-center gap-1 text-xs px-2 py-1 border border-gray-300 rounded-md bg-gray-50 hover:bg-gray-100">
                    <span class="toggle-label">Collapse</span>
                    <span class="toggle-icon">▾</span>
                </button>
            </div>

            <div class="menu-item-body mt-4">

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-600 mb-1">Title</label>
                    <input type="text"
                           name="${blockName}[title]"
                           class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring focus:ring-blue-200">
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-600 mb-1">URL</label>
                    <input type="text"
                           name="${blockName}[url]"
                           class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring focus:ring-blue-200">
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-600 mb-1">Tags (comma-separated)</label>
                    <input type="text"
                           name="${blockName}[tags]"
                           class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring focus:ring-blue-200">
                </div>

                <div class="mt-2 flex flex-wrap gap-2 menu-actions">

                    <button type="button"
                            class="btn-action btn-move-up inline-flex items-center gap-1 px-2 py-1 border border-gray-300 rounded-md text-xs bg-white hover:bg-gray-50">
                        ↑ Move up
                    </button>

                    <button type="button"
                            class="btn-action btn-move-down inline-flex items-center gap-1 px-2 py-1 border border-gray-300 rounded-md text-xs bg-white hover:bg-gray-50">
                        ↓ Move down
                    </button>

                    <button type="button"
                            class="btn-action btn-indent inline-flex items-center gap-1 px-2 py-1 border border-gray-300 rounded-md text-xs bg-white hover:bg-gray-50">
                        → Indent
                    </button>

                    <button type="button"
                            class="btn-action btn-outdent inline-flex items-center gap-1 px-2 py-1 border border-gray-300 rounded-md text-xs bg-white hover:bg-gray-50">
                        ← Outdent
                    </button>

                    <button type="button"
                            class="btn-action btn-add-sibling inline-flex items-center gap-1 px-3 py-1 border border-gray-300 rounded-md text-sm bg-white hover:bg-gray-50">
                        <img src="/assets/icons/additem.png" alt="Add item" class="w-4 h-4">
                        Add new item
                    </button>

                    ${canAddChild ? `
                    <button type="button"
                            class="btn-action btn-add-child inline-flex items-center gap-1 px-3 py-1 border border-gray-300 rounded-md text-sm bg-white hover:bg-gray-50">
                        <img src="/assets/icons/additem.png" alt="Add subitem" class="w-4 h-4">
                        Add subitem
                    </button>` : ''}

                    <button type="button"
                            class="btn-action btn-delete-item inline-flex items-center gap-1 px-3 py-1 border border-red-300 rounded-md text-sm bg-red-50 hover:bg-red-100 text-red-700">
                        <img src="/assets/icons/deleteitem.png" alt="Delete item" class="w-4 h-4">
                        Remove item
                    </button>
                </div>

                <div class="menu-children mt-4 pl-4 border-l border-dashed border-gray-300"
                     data-name-prefix="${childPrefix.replace(/"/g, '&quot;')}"
                     data-level="${level + 1}">
                </div>
            </div>
        </div>`;
    }

    /**
     * Collapse/expand a single item.
     */
    function toggleItem(item) {
        const body  = item.querySelector(':scope > .menu-item-body');
        const label = item.querySelector(':scope .btn-toggle-item .toggle-label');
        const icon  = item.querySelector(':scope .btn-toggle-item .toggle-icon');

        if (!body || !label || !icon) return;

        const isHidden = body.classList.toggle('hidden');
        if (isHidden) {
            label.textContent = 'Expand';
            icon.textContent  = '▸';
        } else {
            label.textContent = 'Collapse';
            icon.textContent  = '▾';
        }
    }

    /**
     * Updates data-level attributes based on DOM hierarchy (root = 0).
     */
    function updateLevels() {
        const root = document.getElementById('menuRoot');
        if (!root) return;

        function walk(container, level) {
            container.dataset.level = String(level);
            const items = container.querySelectorAll(':scope > .menu-item');
            items.forEach(item => {
                item.dataset.level = String(level);
                const childrenContainer = item.querySelector(':scope > .menu-item-body > .menu-children');
                if (childrenContainer) {
                    walk(childrenContainer, level + 1);
                }
            });
        }

        walk(root, 0);
    }

    /**
     * Rebuilds all input name attributes to reflect the current DOM structure
     * before sending to PHP.
     */
    function rebuildNames() {
        const root = document.getElementById('menuRoot');
        if (!root) return;

        function walk(container, prefix) {
            const items = container.querySelectorAll(':scope > .menu-item');
            items.forEach((item, index) => {
                const blockName = prefix + '[' + index + ']';

                const titleInput = item.querySelector('input[name$="[title]"]');
                if (titleInput) titleInput.name = blockName + '[title]';

                const urlInput = item.querySelector('input[name$="[url]"]');
                if (urlInput) urlInput.name = blockName + '[url]';

                const tagsInput = item.querySelector('input[name$="[tags]"]');
                if (tagsInput) tagsInput.name = blockName + '[tags]';

                const childrenContainer = item.querySelector(':scope > .menu-item-body > .menu-children');
                if (childrenContainer) {
                    const childPrefix = blockName + '[children]';
                    childrenContainer.dataset.namePrefix = childPrefix;
                    walk(childrenContainer, childPrefix);
                }
            });
        }

        walk(root, 'menu');
    }

    document.addEventListener('click', function (e) {

        // Collapse/expand item
        const toggleBtn = e.target.closest('.btn-toggle-item');
        if (toggleBtn) {
            const item = toggleBtn.closest('.menu-item');
            if (item) toggleItem(item);
            return;
        }

        // Add sibling item
        const addSiblingBtn = e.target.closest('.btn-add-sibling');
        if (addSiblingBtn) {
            const item      = addSiblingBtn.closest('.menu-item');
            const container = item.parentElement;
            const prefix    = container.dataset.namePrefix || 'menu';
            const level     = parseInt(container.dataset.level || '0', 10);

            const newIndex = getNextIndex(container);
            const html     = createItemHTML(prefix, newIndex, level);

            item.insertAdjacentHTML('afterend', html);
            updateLevels();
            return;
        }

        // Add child item
        const addChildBtn = e.target.closest('.btn-add-child');
        if (addChildBtn) {
            const item      = addChildBtn.closest('.menu-item');
            const container = item.querySelector(':scope > .menu-item-body > .menu-children');
            if (!container) return;

            const prefix = container.dataset.namePrefix || 'menu';
            const level  = parseInt(container.dataset.level || '0', 10);

            const newIndex = getNextIndex(container);
            const html     = createItemHTML(prefix, newIndex, level);

            container.insertAdjacentHTML('beforeend', html);
            updateLevels();
            return;
        }

        // Delete item
        const deleteBtn = e.target.closest('.btn-delete-item');
        if (deleteBtn) {
            const item = deleteBtn.closest('.menu-item');
            if (item && confirm('Remove this item and all its subitems?')) {
                item.remove();
                updateLevels();
            }
            return;
        }

        // Add top-level item
        const addRootBtn = e.target.closest('.btn-add-root');
        if (addRootBtn) {
            const container = document.getElementById('menuRoot');
            const prefix    = container.dataset.namePrefix || 'menu';
            const level     = parseInt(container.dataset.level || '0', 10);

            const newIndex = getNextIndex(container);
            const html     = createItemHTML(prefix, newIndex, level);

            container.insertAdjacentHTML('beforeend', html);
            updateLevels();
            return;
        }

        // Move up
        const moveUpBtn = e.target.closest('.btn-move-up');
        if (moveUpBtn) {
            const item = moveUpBtn.closest('.menu-item');
            if (!item) return;
            const prev = item.previousElementSibling;
            if (prev && prev.classList.contains('menu-item')) {
                item.parentElement.insertBefore(item, prev);
                updateLevels();
            }
            return;
        }

        // Move down
        const moveDownBtn = e.target.closest('.btn-move-down');
        if (moveDownBtn) {
            const item = moveDownBtn.closest('.menu-item');
            if (!item) return;
            const next = item.nextElementSibling;
            if (next && next.classList.contains('menu-item')) {
                item.parentElement.insertBefore(next, item);
                updateLevels();
            }
            return;
        }

        // Indent (become child of previous sibling)
        const indentBtn = e.target.closest('.btn-indent');
        if (indentBtn) {
            const item = indentBtn.closest('.menu-item');
            if (!item) return;
            const prev = item.previousElementSibling;
            if (!prev || !prev.classList.contains('menu-item')) return;

            const childrenContainer = prev.querySelector(':scope > .menu-item-body > .menu-children');
            if (!childrenContainer) return;

            const currentParent = item.parentElement;
            childrenContainer.appendChild(item);

            updateLevels();
            return;
        }

        // Outdent (move one level up, after parent)
        const outdentBtn = e.target.closest('.btn-outdent');
        if (outdentBtn) {
            const item = outdentBtn.closest('.menu-item');
            if (!item) return;

            const parentChildren = item.parentElement; // .menu-children
            const parentItem     = parentChildren.closest('.menu-item');

            if (!parentItem) {
                // Already at root level
                return;
            }

            const grandParentChildren = parentItem.parentElement; // parent's container
            if (!grandParentChildren) return;

            grandParentChildren.insertBefore(item, parentItem.nextElementSibling);
            updateLevels();
            return;
        }
    });

    // Before submitting, rebuild all name attributes to reflect current structure
    document.addEventListener('DOMContentLoaded', function () {
        const form = document.getElementById('menuEditorForm');
        if (!form) return;

        updateLevels();

        form.addEventListener('submit', function () {
            rebuildNames();
        });
    });

})();
</script>
