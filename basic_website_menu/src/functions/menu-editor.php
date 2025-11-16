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
        <div class="menu-item border border-gray-300 rounded-md p-4 my-4 bg-white shadow-sm" data-level="<?= $level ?>">
            <!-- Block Title -->
            <h3 class="text-lg font-semibold mb-4 text-gray-700">
                Level <?= $level + 1 ?> item —
                <span class="text-gray-500">
                    <?= htmlspecialchars($item['title'] ?? 'untitled') ?>
                </span>
            </h3>

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
        <?php
    }
}
?>

<!-- Main Form -->
<form method="post" action="#" class="mt-6">

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

    <div class="mt-6 flex gap-3">
        <button type="submit"
                class="px-5 py-2 bg-blue-600 text-white rounded-md opacity-50 cursor-not-allowed"
                disabled>
            Save (coming soon)
        </button>

        <button type="button"
                onclick="history.back()"
                class="px-5 py-2 bg-gray-200 text-gray-800 rounded-md">
            Back
        </button>
    </div>
</form>

<!-- JS to dynamically add/remove items -->
<script>
(function () {
    /**
     * Finds the next available numeric index for a given name prefix.
     * Example: prefix "menu[0][children]" searches for menu[0][children][N][title]
     */
    function getNextIndex(container, prefix) {
        let maxIndex = -1;
        const escPrefix = prefix.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'); // escape for regex

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

    /**
     * Creates HTML for a new empty menu item.
     */
    function createItemHTML(prefix, index, level) {
        const blockName   = prefix + '[' + index + ']';
        const childPrefix = blockName + '[children]';
        const canAddChild = level < 2;

        return `
        <div class="menu-item border border-gray-300 rounded-md p-4 my-4 bg-white shadow-sm" data-level="${level}">
            <h3 class="text-lg font-semibold mb-4 text-gray-700">
                New item (level ${level + 1})
            </h3>

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
        </div>`;
    }

    document.addEventListener('click', function (e) {

        // Add sibling item
        const addSiblingBtn = e.target.closest('.btn-add-sibling');
        if (addSiblingBtn) {
            const item      = addSiblingBtn.closest('.menu-item');
            const container = item.parentElement;
            const prefix    = container.dataset.namePrefix;
            const level     = parseInt(container.dataset.level || '0', 10);

            const newIndex = getNextIndex(container, prefix);
            const html     = createItemHTML(prefix, newIndex, level);

            item.insertAdjacentHTML('afterend', html);
            return;
        }

        // Add child item
        const addChildBtn = e.target.closest('.btn-add-child');
        if (addChildBtn) {
            const item      = addChildBtn.closest('.menu-item');
            const container = item.querySelector(':scope > .menu-children');
            if (!container) return;

            const prefix = container.dataset.namePrefix;
            const level  = parseInt(container.dataset.level || '0', 10);

            const newIndex = getNextIndex(container, prefix);
            const html     = createItemHTML(prefix, newIndex, level);

            container.insertAdjacentHTML('beforeend', html);
            return;
        }

        // Delete item
        const deleteBtn = e.target.closest('.btn-delete-item');
        if (deleteBtn) {
            const item = deleteBtn.closest('.menu-item');
            if (item && confirm('Remove this item and all its subitems?')) {
                item.remove();
            }
            return;
        }

        // Add top-level item
        const addRootBtn = e.target.closest('.btn-add-root');
        if (addRootBtn) {
            const container = document.getElementById('menuRoot');
            const prefix    = container.dataset.namePrefix;
            const level     = parseInt(container.dataset.level || '0', 10);

            const newIndex = getNextIndex(container, prefix);
            const html     = createItemHTML(prefix, newIndex, level);

            container.insertAdjacentHTML('beforeend', html);
            return;
        }
    });
})();
</script>
