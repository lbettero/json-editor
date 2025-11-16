<?php
    // public/menu-manager.php
    $page_title = "Menu Manager – SMART SMALL THINGS";

    include __DIR__ . '/src/includes/header.php';

    // Path to the menu editor
    $menuEditorPath = __DIR__ . '/src/functions/menu-editor.php';
?>

<!-- ====================== MAIN CONTENT ====================== -->
<section id="start" class="py-8">
    <div class="container mx-auto max-w-5xl px-4">
        <h1 class="text-2xl font-bold mb-6">Menu Manager</h1>

        <p class="mb-4 text-gray-700">
            Here you can view and edit the menu items loaded from the 
            <strong>menu.json</strong> file.
        </p>

        <div class="p-4 border rounded-md bg-white shadow-sm">
            <?php
            if (file_exists($menuEditorPath)) {
                include $menuEditorPath;
            } else {
                echo "<p class='text-red-600 font-semibold'>
                        Error: the file menu-editor.php was not found at 
                        <code>$menuEditorPath</code>.
                    </p>";
            }
            ?>
        </div>
    </div>
</section>

<!-- ====================== FLOATING ARROWS ====================== -->
<div id="scrollUp" class="scroll-arrow">
    <img src=\"/assets/icons/setaup.png\" alt=\"Scroll up\" class=\"arrow-icon w-5 h-5\">
</div>

<div id="scrollDown" class="scroll-arrow">
    <img src=\"/assets/icons/setadown.png\" alt=\"Scroll down\" class=\"arrow-icon w-5 h-5\">
</div>

<!-- ====================== JAVASCRIPT ====================== -->
<script src="/assets/js/menu-manager.js"></script>

<?php include __DIR__ . '/src/includes/footer.php'; ?>
