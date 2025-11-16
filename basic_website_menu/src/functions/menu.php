<?php
/**
 * Loads data from the menu.json file and converts it into a PHP array.
 * Returns an error message if the file does not exist or if the JSON cannot be decoded.
 */
function getMenuData(string $path): array
{
	if (!file_exists($path)) return ["error" => "menu.json file not found."];
	$json = file_get_contents($path);
	$data = json_decode($json, true);
	if (json_last_error() !== JSON_ERROR_NONE) return ["error" => "Error decoding the JSON file."];
	return $data;
}

/**
 * Main function that prints the complete menu using Alpine.js.
 */
function renderMenu(): void
{
	$menuPath = __DIR__ . '/../../assets/data/menu.json';
	$menuData = getMenuData($menuPath);
	$menuError = $menuData['error'] ?? null;
	$items = !isset($menuData['error']) ? $menuData : [];
	$menuJSON = htmlspecialchars(json_encode($items, JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8');
	?>

	<!-- ====================== MAIN MENU BLOCK ====================== -->
	<nav 
		x-data="menuComponent(<?= "'$menuJSON'" ?>)" 
		x-init="init()" 
		class="flex flex-col md:flex-row items-center justify-between py-1 gap-3">

		<!-- ===== Desktop Menu (horizontal for large screens) ===== -->
		<div class="hidden md:block w-full mt-4" role="menubar">
			<template x-for="(item, i) in filteredMenu" :key="i">
				<div 
					class="inline-block relative group mx-1" 
					@mouseenter="open = i" 
					@mouseleave="open = null">

					<!-- First-level button -->
					<button 
						class="px-3 py-2 rounded-md hover:bg-gray-100 transition"
						:class="{'bg-yellow-100': filter && startsInTokens(tokenize(item.title), normalize(filter))}" 
						:aria-expanded="open === i"
						x-html="highlight(item.title)">
					</button>

					<!-- ===== Second-level submenu ===== -->
					<template x-if="item.children">
						<ul
							class="absolute left-0 mt-1 bg-white/95 border rounded-md shadow-md w-52 z-20 break-words whitespace-normal"
							x-show="open === i || (search && (item.children?.length))"
							x-transition
							@mouseenter="open = i" 
							@mouseleave="open = null">
							
							<template x-for="(child, j) in item.children" :key="child.title">
								<li 
									class="relative" 
									x-data="{ openChild: null }" 
									@mouseenter="openChild = j" 
									@mouseleave="openChild = null">

									<!-- Second-level link/button -->
									<a 
										:href="child.url || '#'" 
										class="block px-3 py-2 hover:bg-gray-50" 
										x-html="highlight(child.title)">
									</a>

									<!-- ===== Third level (or deeper) ===== -->
									<template x-if="child.children">
										<ul 
											x-data="{ openLeft: false }"
											x-init="$watch('openChild', value => {
												if (value === j) {
													$nextTick(() => {
														const rect = $el.getBoundingClientRect();
														openLeft = (window.innerWidth - rect.right) < 200;
													});
												}
											})"
											:class="openLeft
												? 'absolute top-0 right-full -mr-2 bg-white/95 border rounded-md shadow-md w-52 z-30 break-words whitespace-normal'
												: 'absolute top-0 left-full -ml-2 bg-white/95 border rounded-md shadow-md w-52 z-30 break-words whitespace-normal'"
											x-show="openChild === j || (search && (child.children?.length))"
											x-transition>
											
											<template x-for="grandchild in child.children" :key="grandchild.title">
												<li>
													<a 
														:href="grandchild.url || '#'" 
														class="block px-3 py-2 hover:bg-gray-50" 
														x-html="highlight(grandchild.title)">
													</a>
												</li>
											</template>
										</ul>
									</template>
								</li>
							</template>
						</ul>
					</template>
				</div>
			</template>
		</div>

		<!-- ===== Search field (dynamically filters menu) ===== -->
		<div class="w-full md:w-1/3 px-3">
			<input 
				type="search" 
				x-model.debounce.250ms="search" 
				placeholder="Search..." 
				class="w-full min-w-[200px] border rounded-md px-3 py-2 text-sm"/>
		</div>

		<!-- ===== Mobile menu (collapsible structure for small screens) ===== -->
		<div class="md:hidden w-full border-t pt-3 space-y-2" role="menu">
			<template x-for="(item, i) in filteredMenu" :key="i">
				<details class="group">
					<!-- First level of mobile menu -->
					<summary class="flex justify-between items-center px-3 py-2 cursor-pointer hover:bg-gray-100">
						<span x-html="highlight(item.title)"></span>
						<svg class="size-4 transition-transform group-open:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor">
							<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
						</svg>
					</summary>

					<!-- Second level inside the mobile menu -->
					<div class="pl-4 border-l ml-2 mt-1">
						<template x-if="item.children">
							<template x-for="(child, j) in item.children" :key="child.title">
								<div>
									<!-- Simple link with no further levels -->
									<template x-if="!child.children">
										<a 
											:href="child.url || '#'" 
											class="block px-3 py-1 hover:bg-gray-50 text-sm" 
											x-html="highlight(child.title)">
										</a>
									</template>

									<!-- Sublevel inside mobile menu -->
									<template x-if="child.children">
										<details class="group pl-3 border-l ml-2 mt-1">
											<summary class="flex justify-between items-center px-3 py-1 cursor-pointer hover:bg-gray-50 text-sm">
												<span x-html="highlight(child.title)"></span>
												<svg class="size-3 transition-transform group-open:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor">
													<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
												</svg>
											</summary>

											<!-- Third level of the mobile menu -->
											<div class="pl-3 border-l ml-2 mt-1">
												<template x-for="grandchild in child.children" :key="grandchild.title">
													<a 
														:href="grandchild.url || '#'" 
														class="block px-3 py-1 hover:bg-gray-50 text-sm" 
														x-html="highlight(grandchild.title)">
													</a>
												</template>
											</div>
										</details>
									</template>
								</div>
							</template>
						</template>
					</div>
				</details>
			</template>

			<!-- Message displayed when no search results are found -->
			<div x-show="!filteredMenu.length" class="text-sm text-gray-500 px-3 py-2">
				No results for <strong x-text="search"></strong>.
			</div>
		</div>
	</nav>

	<?php
}
?>
