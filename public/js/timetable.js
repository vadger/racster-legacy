(function () {

	function hmToMinutes(hm) {
		const [h, m] = hm.split(':').map(Number);
		return h * 60 + m;
	}

	function getCssNumber(el, varName, fallback) {
		const v = getComputedStyle(el).getPropertyValue(varName).trim();
		const n = parseFloat(v);
		return Number.isFinite(n) ? n : fallback;
	}

	function initGridWeekScroll() {
		const root = document.getElementById('wt-week');
		const scroller = document.getElementById('wt-scroll');
		if (!root || !scroller) return;

		// Provided by Blade data-attributes
		const step = Number(root.dataset.step || 30);
		const startMin = Number(root.dataset.startMin || 0);
		const scrollTo = (root.dataset.scrollTo || '07:00');

		// Row height comes from CSS: .wt-week { --rowH: 24px; }
		const rowH = getCssNumber(root, '--rowH', 24);

		const targetMin = hmToMinutes(scrollTo);
		const offsetMin = Math.max(0, targetMin - startMin);

		// each "step" minutes = rowH pixels
		const y = (offsetMin / step) * rowH;

		// Let layout settle, then scroll
		requestAnimationFrame(() => {
			scroller.scrollTop = y;
		});
	}

	document.addEventListener('DOMContentLoaded', initGridWeekScroll);

	// Optional: expose for partial page reloads / turbo / ajax
	window.initGridWeekScroll = initGridWeekScroll;

})();
