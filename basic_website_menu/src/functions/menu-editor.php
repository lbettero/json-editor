<?php
// src/functions/menu-editor.php

// Load JSON
require_once __DIR__ . '/menu.php';

$menuPath  = __DIR__ . '/../../assets/data/menu.json';
$menuData  = getMenuData($menuPath);
$menuError = $menuData['error'] ?? null;
$items     = $menuError ? [] : $menuData;

// ---------------------------------------------------------
// Detect existing global fields from JSON (propagação rígida)
// ---------------------------------------------------------
function collectGlobalFields($items) {
    $standard = ['title','url','tags','children'];
    $fields = [];

    $walk = function($nodes) use (&$walk, &$fields, $standard) {
        foreach ($nodes as $n) {
            foreach ($n as $key => $value) {
                if (!in_array($key, $standard)) {
                    if (!isset($fields[$key])) {
                        $fields[$key] = [
                            'type' => detectFieldType($value),
                            'default' => $value
                        ];
                    }
                }
            }

            if (!empty($n['children'])) {
                $walk($n['children']);
            }
        }
    };

    $walk($items);
    return $fields;
}

// Detect type for the editor's dropdown
function detectFieldType($value) {
    if (is_bool($value)) return 'boolean';
    if (is_numeric($value)) return 'number';
    if (is_array($value) || is_object($value)) return 'json';
    return 'string';
}

$globalFields = collectGlobalFields($items);

/**
 * Renders menu fields (recursive)
 */
function renderMenuFields(array $items, array $globalFields, string $namePrefix='menu', int $level=0): void 
{
    foreach ($items as $index => $item) {

        $blockName   = $namePrefix . '[' . $index . ']';
        $childPrefix = $blockName . '[children]';
        $canAddChild = $level < 2;

        // Standard fields
        $standard = ['title','url','tags','children'];
        $itemKeys = array_keys($item);
        $extraKeys = array_diff($itemKeys, $standard, array_keys($globalFields));
        ?>
        
        <div class="menu-item border border-gray-300 rounded-md p-4 my-4 bg-white shadow-sm"
             data-level="<?= $level ?>">

            <!-- HEADER -->
            <div class="menu-item-header flex items-center justify-between gap-2">
                <h3 class="text-lg font-semibold text-gray-700">
                    Level <?= $level+1 ?> —
                    <span class="text-gray-500"><?= htmlspecialchars($item['title'] ?? 'untitled') ?></span>
                </h3>

                <button type="button"
                        class="btn-toggle-item inline-flex items-center gap-1 text-xs px-2 py-1 border border-gray-300 rounded-md bg-gray-50 hover:bg-gray-100">
                    <span class="toggle-label">Collapse</span>
                    <span class="toggle-icon">▾</span>
                </button>
            </div>

            <!-- BODY -->
            <div class="menu-item-body mt-4">

                <!-- STANDARD FIELDS -->
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-600">Title</label>
                    <input type="text" 
                           name="<?= $blockName ?>[title]"
                           value="<?= htmlspecialchars($item['title'] ?? '') ?>"
                           class="w-full border border-gray-300 rounded px-3 py-2">
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-600">URL</label>
                    <input type="text" 
                           name="<?= $blockName ?>[url]"
                           value="<?= htmlspecialchars($item['url'] ?? '') ?>"
                           class="w-full border border-gray-300 rounded px-3 py-2">
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-600">Tags</label>
                    <input type="text" 
                           name="<?= $blockName ?>[tags]"
                           value="<?= isset($item['tags']) ? htmlspecialchars(implode(', ', $item['tags'])) : '' ?>"
                           class="w-full border border-gray-300 rounded px-3 py-2">
                </div>

                <!-- GLOBAL FIELDS -->
                <?php if (!empty($globalFields)): ?>
                <div class="mt-4 p-3 border rounded bg-blue-50">
                    <h4 class="font-semibold text-sm text-blue-900 mb-2">Global fields</h4>

                    <?php foreach ($globalFields as $fieldName => $def): ?>
                        <?php 
                            $currentValue = $item[$fieldName] ?? $def['default'];

                            if ($def['type'] === 'json') {
                                $currentValue = json_encode($currentValue, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
                            }
                        ?>

                        <div class="mb-3">
                            <label class="block text-xs font-medium text-gray-700">
                                <?= htmlspecialchars($fieldName) ?> 
                                <span class="text-gray-400">(<?= $def['type'] ?>)</span>
                            </label>

                            <?php if ($def['type'] === 'boolean'): ?>
                                <select name="<?= $blockName ?>[<?= $fieldName ?>]"
                                        class="border rounded px-2 py-1 text-sm">
                                    <option value="true"  <?= ($currentValue===true || $currentValue==="true")?"selected":"" ?>>true</option>
                                    <option value="false" <?= ($currentValue===false || $currentValue==="false")?"selected":"" ?>>false</option>
                                </select>

                            <?php else: ?>
                                <input type="text"
                                       name="<?= $blockName ?>[<?= $fieldName ?>]"
                                       value="<?= htmlspecialchars($currentValue) ?>"
                                       class="w-full border rounded px-2 py-1 text-sm">
                            <?php endif; ?>

                        </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <!-- ITEM-SPECIFIC EXTRA FIELDS -->
                <?php if (!empty($extraKeys)): ?>
                <div class="mt-4 p-3 border rounded bg-gray-50">
                    <h4 class="text-sm font-semibold text-gray-700 mb-2">Advanced fields</h4>
                    <?php foreach ($extraKeys as $k): ?>
                        <div class="mb-2">
                            <label class="text-xs font-medium"><?= htmlspecialchars($k) ?></label>
                            <input type="text" 
                                   name="<?= $blockName ?>[<?= htmlspecialchars($k) ?>]"
                                   value="<?= htmlspecialchars(json_encode($item[$k], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)) ?>"
                                   class="w-full border rounded px-2 py-1 text-sm">
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <!-- ACTION BUTTONS -->
                <div class="mt-4 flex flex-wrap gap-2">

                    <button type="button" class="btn-action btn-move-up px-2 py-1 border text-xs">↑ Move up</button>
                    <button type="button" class="btn-action btn-move-down px-2 py-1 border text-xs">↓ Move down</button>
                    <button type="button" class="btn-action btn-indent px-2 py-1 border text-xs">→ Indent</button>
                    <button type="button" class="btn-action btn-outdent px-2 py-1 border text-xs">← Outdent</button>
                    <button type="button" class="btn-action btn-add-sibling px-3 py-1 border text-sm">
                        <img src="/assets/icons/additem.png" class="w-4 h-4"> Add new item
                    </button>

                    <?php if ($canAddChild): ?>
                    <button type="button" class="btn-action btn-add-child px-3 py-1 border text-sm">
                        <img src="/assets/icons/additem.png" class="w-4 h-4"> Add subitem
                    </button>
                    <?php endif; ?>

                    <button type="button" class="btn-action btn-delete-item px-3 py-1 border text-sm bg-red-50 text-red-700">
                        <img src="/assets/icons/deleteitem.png" class="w-4 h-4"> Remove
                    </button>

                </div>

                <!-- CHILDREN -->
                <div class="menu-children mt-4 pl-4 border-l border-dashed"
                     data-name-prefix="<?= htmlspecialchars($childPrefix) ?>"
                     data-level="<?= $level+1 ?>">
                    <?php
                    if (!empty($item['children'])) {
                        renderMenuFields($item['children'], $globalFields, $childPrefix, $level+1);
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

    <!-- GLOBAL FIELD MANAGER -->
    <div id="globalFieldsBox" class="p-4 border rounded bg-white mb-6">
        <h2 class="text-lg font-bold mb-3">Global fields</h2>

        <div id="globalFieldsContainer">
            <?php foreach ($globalFields as $field => $data): ?>
                <div class="global-field-item flex items-center gap-3 mb-2">

                    <input type="text" class="border rounded px-2 py-1 text-sm w-40"
                        name="globalFields[name][]" value="<?= htmlspecialchars($field) ?>">

                    <select name="globalFields[type][]" class="border rounded px-2 py-1 text-sm">
                        <option value="string"  <?= $data['type']==='string'?'selected':'' ?>>string</option>
                        <option value="number"  <?= $data['type']==='number'?'selected':'' ?>>number</option>
                        <option value="boolean" <?= $data['type']==='boolean'?'selected':'' ?>>boolean</option>
                        <option value="json"    <?= $data['type']==='json'?'selected':'' ?>>json</option>
                    </select>

                    <input type="text" class="border rounded px-2 py-1 text-sm flex-grow"
                        name="globalFields[default][]"
                        value="<?= htmlspecialchars(
                            $data['type']==='json'
                                ? json_encode($data['default'], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)
                                : $data['default']
                        ) ?>">

                    <button type="button"
                            class="btn-remove-global-field text-red-600 text-xs px-2 py-1 border border-red-300 rounded">
                        Remove
                    </button>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- ⚠ Instruction message added here -->
        <p class="mt-3 text-xs text-gray-600 italic">
            After adding or removing global fields, <strong>save the menu</strong> so that these fields appear in all items.
        </p>

        <button type="button" id="btnAddGlobalField"
                class="mt-4 px-3 py-1 border text-sm rounded bg-gray-100">
            + Add global field
        </button>
    </div>



    <!-- MENU STRUCTURE -->
    <div id="menuRoot" class="menu-children space-y-4"
         data-name-prefix="menu" data-level="0">

        <?php
        if (!$menuError && !empty($items)) {
            renderMenuFields($items, $globalFields, 'menu', 0);
        } else {
            echo '<p class="text-gray-600">No menu items found.</p>';
        }
        ?>
    </div>

    <!-- ADD ROOT -->
    <div class="mt-4">
        <button type="button" class="btn-action btn-add-root px-3 py-1 border text-sm">
            <img src="/assets/icons/additem.png" class="w-4 h-4"> Add new top-level item
        </button>
    </div>

    <!-- SAVE -->
    <div class="mt-6 flex gap-3">
        <button type="submit"
                class="px-5 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 flex items-center gap-2">
            <img src="/assets/icons/recordjson.png" class="w-5 h-5"> Save menu.json
        </button>

        <button type="button" onclick="history.back()"
                class="px-5 py-2 bg-gray-200 rounded-md">
            Back
        </button>
    </div>
</form>

<script src="/assets/js/menu-editor.js"></script>
