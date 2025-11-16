<?php
    // public/menu-manager.php
    $page_title = "Gestor de Menu – SMART SMALL THINGS";

    include __DIR__ . '/src/includes/header.php';

    // Caminho para o editor do menu
    $menuEditorPath = __DIR__ . '/src/functions/menu-editor.php';
?>

<!-- ====================== CONTEÚDO PRINCIPAL ====================== -->
<section id="inicio" class="py-8">
    <div class="container mx-auto max-w-5xl px-4">
        <h1 class="text-2xl font-bold mb-6">Gestor do Menu</h1>

        <p class="mb-4 text-gray-700">
            Aqui você pode visualizar e editar os itens do menu carregados a partir do arquivo 
            <strong>menu.json</strong>.
        </p>

        <div class="p-4 border rounded-md bg-white shadow-sm">
            <?php
            if (file_exists($menuEditorPath)) {
                include $menuEditorPath;
            } else {
                echo "<p class='text-red-600 font-semibold'>
                        Erro: o arquivo menu-editor.php não foi encontrado em 
                        <code>$menuEditorPath</code>.
                    </p>";
            }
            ?>
        </div>
    </div>
</section>

<!-- ====================== SETAS FLUTUANTES ====================== -->
<div id="scrollUp" class="scroll-arrow">
    <img src="/assets/icons/setaup.png" alt="Subir" class="arrow-icon w-5 h-5">
</div>

<div id="scrollDown" class="scroll-arrow">
    <img src="/assets/icons/setadown.png" alt="Descer" class="arrow-icon w-5 h-5">
</div>

<!-- ====================== JAVASCRIPT ====================== -->
<script src="/assets/js/menu-manager.js"></script>

<?php include __DIR__ . '/src/includes/footer.php'; ?>
