<?php
$page_title = 'JSON Explorer';
include __DIR__ . '/src/includes/header.php';
?>

<main class="app-shell">
    <section class="hero-card">
        <div>
            <p class="eyebrow">Local JSON workspace</p>
            <h1>Understand and manage any JSON file</h1>
            <p class="hero-copy">Load a JSON file, inspect its unique structure, manage its records and download the updated result. Your data never leaves this browser.</p>
        </div>
        <label class="upload-button" for="jsonFile">Choose JSON file</label>
        <input id="jsonFile" type="file" accept="application/json,.json" hidden>
    </section>

    <div id="notice" class="notice" role="status" aria-live="polite">No JSON file loaded.</div>

    <section id="workspace" class="workspace" hidden>
        <div class="toolbar">
            <div>
                <span id="fileName" class="file-name"></span>
                <span id="recordCount" class="record-count"></span>
            </div>
            <div class="toolbar-actions">
                <label id="collectionLabel" hidden>
                    Collection
                    <select id="collectionSelect"></select>
                </label>
                <button id="downloadButton" class="button button-secondary" type="button">Download JSON</button>
            </div>
        </div>

        <nav class="tabs" aria-label="JSON views">
            <button class="tab is-active" type="button" data-tab="structure">Unique structure</button>
            <button class="tab" type="button" data-tab="records">Items</button>
        </nav>

        <section id="structurePanel" class="panel tab-panel">
            <div class="panel-heading">
                <div>
                    <p class="eyebrow">Schema overview</p>
                    <h2>Unique data structure</h2>
                </div>
                <button id="expandStructure" class="text-button" type="button">Expand all</button>
            </div>
            <div id="structureTree" class="structure-tree"></div>
        </section>

        <section id="recordsPanel" class="panel tab-panel" hidden>
            <div class="panel-heading records-heading">
                <div>
                    <p class="eyebrow">Data manager</p>
                    <h2>Items</h2>
                </div>
                <div class="records-actions">
                    <input id="recordSearch" type="search" placeholder="Search items..." aria-label="Search items">
                    <button id="newRecordButton" class="button button-primary" type="button">New item</button>
                </div>
            </div>
            <div id="recordsList" class="records-list"></div>
        </section>
    </section>
</main>

<dialog id="recordDialog" class="record-dialog">
    <form id="recordForm" method="dialog">
        <div class="dialog-heading">
            <div>
                <p class="eyebrow">JSON item</p>
                <h2 id="dialogTitle">New item</h2>
            </div>
            <button class="icon-button" type="button" data-close-dialog aria-label="Close">×</button>
        </div>
        <div id="dynamicFields" class="dynamic-fields"></div>
        <div class="dialog-actions">
            <button class="button button-secondary" type="button" data-close-dialog>Cancel</button>
            <button class="button button-primary" type="submit">Save item</button>
        </div>
    </form>
</dialog>

<script src="/assets/js/menu-manager.js"></script>
<?php include __DIR__ . '/src/includes/footer.php'; ?>
