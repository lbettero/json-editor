<?php
// src/functions/save-menu.php

$menuPath = __DIR__ . '/../../assets/data/menu.json';

/**
 * Normalizes menu items:
 * - Removes completely empty items
 * - Converts tags (string -> array)
 * - Recursively processes children
 */
function normalizeMenu(array $items): array
{
    $normalized = [];

    foreach ($items as $item) {

        $hasTitle = !empty($item['title']);
        $hasUrl   = !empty($item['url']);
        $hasTags  = !empty($item['tags']);
        $hasChild = !empty($item['children']);

        // if there is nothing filled, ignore
        if (!$hasTitle && !$hasUrl && !$hasTags && !$hasChild) {
            continue;
        }

        $entry = [];

        // title
        $entry['title'] = trim($item['title'] ?? '');

        // url
        if ($hasUrl) {
            $entry['url'] = trim($item['url']);
        }

        // tags: string "a, b, c" -> ["a","b","c"]
        if ($hasTags) {
            $tags = array_map('trim', explode(',', $item['tags']));
            $tags = array_filter($tags); // remove empty ones
            if (!empty($tags)) {
                $entry['tags'] = array_values($tags);
            }
        }

        // recursive children
        if (!empty($item['children']) && is_array($item['children'])) {
            $children = normalizeMenu($item['children']);
            if (!empty($children)) {
                $entry['children'] = $children;
            }
        }

        $normalized[] = $entry;
    }

    return $normalized;
}

$statusType    = 'success';
$statusMessage = 'Menu saved successfully!';
$errorDetails  = '';

if (!isset($_POST['menu'])) {
    $statusType    = 'error';
    $statusMessage = 'No menu data was sent.';
} else {
    try {
        $rawMenu = $_POST['menu'];

        $menu = normalizeMenu($rawMenu);

        // ensure the directory exists
        $dir = dirname($menuPath);
        if (!is_dir($dir)) {
            if (!mkdir($dir, 0777, true) && !is_dir($dir)) {
                throw new RuntimeException('Unable to create the data directory.');
            }
        }

        $json = json_encode($menu, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        if ($json === false) {
            throw new RuntimeException('Error converting menu to JSON.');
        }

        if (file_put_contents($menuPath, $json) === false) {
            throw new RuntimeException('Failed to write the menu.json file.');
        }

    } catch (Throwable $e) {
        $statusType    = 'error';
        $statusMessage = 'An error occurred while saving the menu.';
        $errorDetails  = $e->getMessage();
    }
}

// set page title for the header
$page_title = ($statusType === 'success') ? 'Menu saved' : 'Error saving menu';

// include standard header
include __DIR__ . '/../includes/header.php';
?>

<section class="py-10 max-w-2xl mx-auto px-4">
    <?php if ($statusType === 'success'): ?>
        <div class="bg-green-50 border border-green-200 text-green-800 px-5 py-4 rounded-md shadow-sm">
            <h1 class="text-xl font-semibold mb-2">✔ Menu saved successfully</h1>
            <p class="text-sm mb-4">
                If you wish, you can download the <strong>updated menu.json</strong> by clicking the button below.
            </p>

            <a href="/assets/data/menu.json"
               download
               class="inline-flex items-center gap-2 px-4 py-2 bg-gray-100 hover:bg-gray-200 border border-gray-300 rounded-md text-gray-800 text-sm shadow-sm">
                <img src="/assets/icons/downloadjson.png" alt="Download JSON" class="w-5 h-5">
                Download menu.json
            </a>
        </div>
    <?php else: ?>
        <div class="bg-red-50 border border-red-200 text-red-800 px-5 py-4 rounded-md shadow-sm">
            <h1 class="text-xl font-semibold mb-2">⚠ Error saving the menu</h1>
            <p class="text-sm mb-2"><?= htmlspecialchars($statusMessage) ?></p>
            <?php if ($errorDetails): ?>
                <p class="text-xs opacity-80">
                    Technical details: <?= htmlspecialchars($errorDetails) ?>
                </p>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <div class="mt-6 flex flex-wrap gap-3">
        <a href="/menu-manager.php"
           class="inline-flex items-center gap-2 px-4 py-2 rounded-md bg-blue-600 hover:bg-blue-700 text-white text-sm">
            <img src="/assets/icons/setaback.png" alt="Back to menu editor" class="w-5 h-5">
            Back to menu editor
        </a>
    </div>
</section>

<?php
// include standard footer
include __DIR__ . '/../includes/footer.php';
