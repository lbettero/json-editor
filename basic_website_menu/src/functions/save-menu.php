<?php
// src/functions/save-menu.php

$menuPath = __DIR__ . '/../../assets/data/menu.json';

const MAX_REQUEST_BYTES = 1048576;
const MAX_MENU_ITEMS = 2000;
const MAX_MENU_DEPTH = 3;

function denyRequest(int $status, string $message): void
{
    http_response_code($status);
    header('Content-Type: text/plain; charset=UTF-8');
    header('Cache-Control: no-store');
    exit($message);
}

function requireAuthorizedWriteRequest(): void
{
    if (getenv('JSON_EDITOR_ENABLE_LEGACY_SAVE') !== '1') {
        denyRequest(403, 'Server-side menu saving is disabled.');
    }
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        header('Allow: POST');
        denyRequest(405, 'POST is required.');
    }

    $expectedUser = getenv('JSON_EDITOR_ADMIN_USER');
    $expectedPassword = getenv('JSON_EDITOR_ADMIN_PASSWORD');
    if (!$expectedUser || !$expectedPassword
        || !hash_equals($expectedUser, $_SERVER['PHP_AUTH_USER'] ?? '')
        || !hash_equals($expectedPassword, $_SERVER['PHP_AUTH_PW'] ?? '')) {
        header('WWW-Authenticate: Basic realm="JSON Editor"');
        denyRequest(401, 'Authentication is required.');
    }

    $host = strtolower(preg_replace('/:\d+$/', '', $_SERVER['HTTP_HOST'] ?? ''));
    $source = $_SERVER['HTTP_ORIGIN'] ?? ($_SERVER['HTTP_REFERER'] ?? '');
    $sourceHost = strtolower((string) parse_url($source, PHP_URL_HOST));
    if ($host === '' || $sourceHost === '' || !hash_equals($host, $sourceHost)) {
        denyRequest(403, 'The request origin is not allowed.');
    }

    $contentLength = (int) ($_SERVER['CONTENT_LENGTH'] ?? 0);
    if ($contentLength <= 0 || $contentLength > MAX_REQUEST_BYTES) {
        denyRequest(413, 'The request body is empty or too large.');
    }
}

function validateMenuShape(array $items, int $depth = 1, int &$count = 0): void
{
    if ($depth > MAX_MENU_DEPTH) {
        throw new InvalidArgumentException('The menu exceeds the maximum nesting depth.');
    }
    foreach ($items as $item) {
        if (!is_array($item) || ++$count > MAX_MENU_ITEMS) {
            throw new InvalidArgumentException('The menu structure is invalid or too large.');
        }
        if (isset($item['children'])) {
            if (!is_array($item['children'])) {
                throw new InvalidArgumentException('Menu children must be an array.');
            }
            validateMenuShape($item['children'], $depth + 1, $count);
        }
    }
}

requireAuthorizedWriteRequest();

/**
 * Converts a value string to the appropriate type according to schema
 */
function castValue($value, $type)
{
    switch ($type) {
        case 'boolean':
            return ($value === 'true' || $value === true);

        case 'number':
            return is_numeric($value) ? $value + 0 : 0;

        case 'json':
            $decoded = json_decode($value, true);
            return $decoded === null ? $value : $decoded;

        case 'string':
        default:
            return trim((string)$value);
    }
}

/**
 * Rigid propagation of global fields into all items
 */
function applyGlobalFields(array $items, array $schema): array
{
    $result = [];

    foreach ($items as $item) {
        $newItem = [];

        // Standard base fields
        $title = trim($item['title'] ?? '');
        $url   = trim($item['url'] ?? '');
        $tags  = trim($item['tags'] ?? '');

        if ($title !== '') $newItem['title'] = $title;
        if ($url !== '')   $newItem['url']   = $url;

        // tags -> array
        if ($tags !== '') {
            $parts = array_filter(array_map('trim', explode(',', $tags)));
            if (!empty($parts)) {
                $newItem['tags'] = array_values($parts);
            }
        }

        // Apply global fields rigidly
        foreach ($schema as $fieldName => $def) {
            $raw = $item[$fieldName] ?? $def['default'];
            $newItem[$fieldName] = castValue($raw, $def['type']);
        }

        // Children (recursive)
        if (!empty($item['children']) && is_array($item['children'])) {
            $newItem['children'] = applyGlobalFields($item['children'], $schema);
        }

        $result[] = $newItem;
    }

    return $result;
}

/**
 * Build global fields schema array from POST
 */
function buildGlobalFieldSchema(): array
{
    $schema = [];

    if (!isset($_POST['globalFields']['name'])) {
        return $schema;
    }

    $names  = $_POST['globalFields']['name'];
    $types  = $_POST['globalFields']['type'];
    $defaults = $_POST['globalFields']['default'];

    for ($i = 0; $i < count($names); $i++) {
        $name = trim($names[$i] ?? '');
        if ($name === '') continue;

        $type = $types[$i] ?? 'string';
        $def  = $defaults[$i] ?? '';

        // Cast default to proper type
        $schema[$name] = [
            'type'    => $type,
            'default' => castValue($def, $type)
        ];
    }

    return $schema;
}



// --------------------------------------------------
// MAIN PROCESSING
// --------------------------------------------------

$statusType    = 'success';
$statusMessage = 'Menu saved successfully!';
$errorDetails  = '';

if (!isset($_POST['menu'])) {
    $statusType    = 'error';
    $statusMessage = 'No menu data was sent.';
} else {

    try {
        $rawMenu  = $_POST['menu'];
        if (!is_array($rawMenu)) {
            throw new InvalidArgumentException('Menu data must be an array.');
        }
        $itemCount = 0;
        validateMenuShape($rawMenu, 1, $itemCount);
        $schema   = buildGlobalFieldSchema();

        // Ensure all items follow the schema (rigid propagation)
        $finalMenu = applyGlobalFields($rawMenu, $schema);

        // Ensure directory exists
        $dir = dirname($menuPath);
        if (!is_dir($dir)) {
            if (!mkdir($dir, 0777, true) && !is_dir($dir)) {
                throw new RuntimeException('Unable to create the data directory.');
            }
        }

        // Save JSON
        $json = json_encode($finalMenu, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        if ($json === false) {
            throw new RuntimeException('Error converting menu to JSON.');
        }

        $temporaryPath = tempnam($dir, 'menu-');
        if ($temporaryPath === false
            || file_put_contents($temporaryPath, $json, LOCK_EX) === false
            || !rename($temporaryPath, $menuPath)) {
            if ($temporaryPath && is_file($temporaryPath)) {
                unlink($temporaryPath);
            }
            throw new RuntimeException('Failed to write to menu.json.');
        }

    } catch (Throwable $e) {
        $statusType    = 'error';
        $statusMessage = 'An error occurred while saving the menu.';
        $errorDetails  = $e->getMessage();
    }
}


// --------------------------------------------------
// UI FEEDBACK
// --------------------------------------------------

// set page title
$page_title = ($statusType === 'success') ? 'Menu saved' : 'Error saving menu';

include __DIR__ . '/../includes/header.php';
?>

<section class="py-10 max-w-2xl mx-auto px-4">
    <?php if ($statusType === 'success'): ?>
        <div class="bg-green-50 border border-green-200 text-green-800 px-5 py-4 rounded-md shadow-sm">
            <h1 class="text-xl font-semibold mb-2">✔ Menu saved successfully</h1>
            <p class="text-sm mb-4">
                If you wish, you can download the <strong>updated menu.json</strong>.
            </p>

            <a href="/assets/data/menu.json" download
               class="inline-flex items-center gap-2 px-4 py-2 bg-gray-100 hover:bg-gray-200 border rounded text-gray-800 text-sm shadow-sm">
                <img src="/assets/icons/downloadjson.png" class="w-5 h-5">
                Download menu.json
            </a>
        </div>

    <?php else: ?>
        <div class="bg-red-50 border border-red-200 text-red-800 px-5 py-4 rounded-md shadow-sm">
            <h1 class="text-xl font-semibold mb-2">⚠ Error saving the menu</h1>
            <p class="text-sm mb-2"><?= htmlspecialchars($statusMessage) ?></p>
            <?php if ($errorDetails): ?>
                <p class="text-xs opacity-80">Technical details: <?= htmlspecialchars($errorDetails) ?></p>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <div class="mt-6 flex flex-wrap gap-3">
        <a href="/menu-manager.php"
           class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded text-sm">
            <img src="/assets/icons/setaback.png" class="w-5 h-5">
            Back to menu editor
        </a>
    </div>
</section>

<?php
include __DIR__ . '/../includes/footer.php';
