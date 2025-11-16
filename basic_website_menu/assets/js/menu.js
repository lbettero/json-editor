// assets/js/menu.js

function menuComponent(jsonData) {
	// --- Normalizes a string by removing accents and converting to lowercase ---
	const normalize = s => (s || '').toString().toLowerCase()
		.normalize('NFD').replace(/[\u0300-\u036f]/g, '');

	// --- Splits a string into alphanumeric tokens ---
	const tokenize = s => normalize(s).split(/[^a-z0-9]+/).filter(Boolean);

	// --- Converts a query into individual terms (words or quoted phrases) ---
	const parseTerms = q => {
		const m = [...(q.match(/"([^"]+)"|\S+/g) || [])];
		return m.map(t => normalize(t.replace(/^"|"$/g, ''))).filter(Boolean);
	};

	// --- Checks whether any token starts with the searched term ---
	const startsInTokens = (tokens, term) =>
		tokens.some(tok => tok === term || tok.startsWith(term));

	// --- Propagates tags from parent nodes to children, creating an inherited tag set ---
	const withInheritedTags = (nodes, parentTags = []) =>
		nodes.map(n => {
			const own = Array.isArray(n.tags) ? n.tags : [];
			const effectiveTags = [...new Set([...parentTags, ...own])];
			const copy = { ...n, effectiveTags };
			if (Array.isArray(n.children)) copy.children = withInheritedTags(n.children, effectiveTags);
			return copy;
		});

	// --- Original menu structure with inherited tags applied ---
	const original = withInheritedTags(JSON.parse(jsonData));

	// --- Computes a relevance score for a node based on search terms ---
	const scoreNode = (node, terms) => {
		const titleTokens = tokenize(node.title);
		const tagTokens = (node.effectiveTags || []).flatMap(tokenize);
		let score = 0;
		for (const term of terms) {
			if (startsInTokens(titleTokens, term)) score += 6;   // Partial match at start of title
			if (titleTokens.includes(term)) score += 5;         // Exact match in title
			if (startsInTokens(tagTokens, term)) score += 3;    // Partial tag match
			if (tagTokens.includes(term)) score += 2;           // Exact match in tags
		}
		return score;
	};

	return {
		// --- Initial component state ---
		originalMenu: original,
		search: '',
		filter: '',
		open: null,

		// --- Initializes custom events to filter and reset the menu ---
		init() {
			window.addEventListener('menu:filter', e => {
				this.filter = e.detail;
				this.search = '';
				this.open = null;
			});
			window.addEventListener('menu:reset', () => {
				this.resetFilters();
			});
		},

		// --- Returns the menu filtered by search terms and category ---
		get filteredMenu() {
			const terms = parseTerms(this.search);
			const filterCat = normalize(this.filter);

			// --- Determines whether a node matches all search conditions ---
			const matchAND = (node) => {
				const titleTokens = tokenize(node.title);
				const tagTokens = (node.effectiveTags || []).flatMap(tokenize);

				const termOK = terms.every(term =>
					startsInTokens(titleTokens, term) ||
					startsInTokens(tagTokens, term)
				);

				const catOK = !filterCat ||
					startsInTokens(titleTokens, filterCat) ||
					startsInTokens(tagTokens, filterCat);

				return (terms.length ? termOK : true) && catOK;
			};

			// --- Recursively searches through the entire menu tree ---
			const recur = (nodes) => {
				const mapped = nodes.map(node => {
					const kids = Array.isArray(node.children) ? recur(node.children) : [];
					const isMatch = matchAND(node);
					if (isMatch || kids.length) {
						const score = terms.length ? scoreNode(node, terms) : 0;
						return { ...node, children: kids, _score: score };
					}
					return null;
				}).filter(Boolean);

				// --- Sorts the results by relevance score ---
				return mapped.sort((a, b) => (b._score || 0) - (a._score || 0));
			};

			return recur(this.originalMenu);
		},

		// --- Applies a category filter ---
		filterCategory(cat) { this.filter = cat; },

		// --- Resets all filters and the search field ---
		resetFilters() { this.search = ''; this.filter = ''; this.open = null; },

		// --- Highlights search terms inside displayed text ---
		highlight(text) {
			if (!this.search) return text;
			const terms = parseTerms(this.search).map(t => t.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'));
			if (!terms.length) return text;
			const rx = new RegExp('\\b(' + terms.join('|') + ')', 'gi');
			return normalize(text)
				? text.replace(rx, '<mark class="bg-yellow-200">$1</mark>')
				: text;
		}
	}
}
