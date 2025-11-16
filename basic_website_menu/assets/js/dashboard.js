// assets/js/dashboard.js

// --- Sends a custom event to filter the menu based on a tag ---
function sendMenuFilter(tag) {
	// Dispatches a global event that other components can listen to
	window.dispatchEvent(new CustomEvent('menu:filter', { detail: tag }));

	// --- Quick visual feedback (displays a pill with the filter name) ---
	const pill = document.getElementById('menuFilterPill');
	const name = document.getElementById('menuFilterName');

	// If the UI elements exist, update the text and show the pill
	if (pill && name) {
		name.textContent = tag;
		pill.classList.remove('hidden');
	}
}

// --- Restores the original menu state (clears active filters) ---
function resetMenu() {
	// Dispatches a global event to reset the menu filters
	window.dispatchEvent(new CustomEvent('menu:reset'));

	// Hides the filter pill, if it exists
	const pill = document.getElementById('menuFilterPill');
	if (pill) pill.classList.add('hidden');
}
