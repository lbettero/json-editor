<?php
	// Obtiene el año actual para mostrarlo dinámicamente en el pie de página
	$year = date('Y');
?>

<!-- ====================== PIE DE PÁGINA ====================== -->
<footer class="bg-brand-navy text-white py-6 mt-auto">
	<div class="container mx-auto text-center">
		<!-- Texto informativo del pie -->
		<p class="text-sm">
			<span class="font-semibold">Proyecto Coterena — Menú Dinámico</span><br>
			<?= $year ?> | Desarrollado por <span class="text-brand-teal font-medium">Livia Pérez Bettero</span>
		</p>
	</div>
</footer>

<!-- Cierre del documento HTML -->
</body>
</html>
