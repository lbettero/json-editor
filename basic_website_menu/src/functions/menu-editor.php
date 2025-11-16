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
        $canAddChild = $level < 2;
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

            <!-- BODY -->
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

                <!-- Buttons -->
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

                    <!-- Add new sibling -->
                    <button type="button"
                            class="btn-action btn-add-sibling inline-flex items-center gap-1 px-3 py-1 border border-gray-300 rounded-md text-sm bg-white hover:bg-gray-50">
                        <img src="/assets/icons/additem.png" alt="Add item" class="w-4 h-4">
                        Add new item
                    </button>

                    <!-- Add child -->
                    <?php if ($canAddChild): ?>
                        <button type="button"
                                class="btn-action btn-add-child inline-flex items-center gap-1 px-3 py-1 border border-gray-300 rounded-md text-sm bg-white hover:bg-gray-50">
                            <img src="/assets/icons/additem.png" alt="Add subitem" class="w-4 h-4">
                            Add subitem
                        </button>
                    <?php endif; ?>

                    <!-- Delete -->
                    <button type="button"
                            class="btn-action btn-delete-item inline-flex items-center gap-1 px-3 py-1 border border-red-300 rounded-md text-sm bg-red-50 hover:bg-red-100 text-red-700">
                        <img src="/assets/icons/deleteitem.png" alt="Delete item" class="w-4 h-4">
                        Remove item
                    </button>
                </div>

                <!-- Children -->
                <div class="menu-children mt-4 pl-4 border-l border-dashed border-gray-300"
                     data-name-prefix="<?= htmlspecialchars($childPrefix, ENT_QUOTES, 'UTF-8') ?>"
                     data-level="<?= $level + 1 ?>">
                    <?php
                    if (!empty($item['children'])) {
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

<!-- MAIN FORM -->
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

    <!-- Add ROOT item -->
    <div class="mt-4">
        <button type="button"
                class="btn-action btn-add-root inline-flex items-center gap-1 px-3 py-1 border border-gray-300 rounded-md text-sm bg-white hover:bg-gray-50">
            <img src="/assets/icons/additem.png" alt="Add item" class="w-4 h-4">
            Add new top-level item
        </button>
    </div>

    <!-- Save -->
    <div class="mt-6 flex gap-3">
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

<!-- EXTERNAL JS -->
<script src="/assets/js/menu-editor.js"></script>
