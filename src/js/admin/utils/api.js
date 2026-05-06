const CACHE_DURATION_DAYS = 3;
const CACHE_DURATION_IN_MILLIS = CACHE_DURATION_DAYS * 24 * 60 * 60 * 1000;

/**
 * Retrieves an item from localStorage cache if it's not expired.
 * @param {string} cacheKey - The key for the cache item.
 * @returns {any|null} The cached data or null if not found or expired.
 */
const getFromCache = (cacheKey) => {
	const cachedData = localStorage.getItem(cacheKey);
	if (cachedData) {
		const { timestamp, data } = JSON.parse(cachedData);
		const now = new Date().getTime();
		if (now - timestamp < CACHE_DURATION_IN_MILLIS) {
			return data;
		}
	}
	return null;
};

/**
 * Saves an item to the localStorage cache with a timestamp.
 * @param {string} cacheKey - The key for the cache item.
 * @param {any} data - The data to cache.
 */
const saveToCache = (cacheKey, data) => {
	const cacheValue = {
		timestamp: new Date().getTime(),
		data: data,
	};
	localStorage.setItem(cacheKey, JSON.stringify(cacheValue));
};

/**
 * Clears the help content cache for a specific content ID or all help content.
 * @param {string} [contentId] - Optional content ID or URL (with article=). If not provided, clears all help content cache.
 */
export const clearHelpContentCache = (contentId = null) => {
	if (contentId) {
		const kbId = resolveHelpContentId(contentId);
		if (kbId) {
			localStorage.removeItem(`bb_help_content_${kbId}`);
		}
	} else {
		// Clear all help content cache entries
		for (let i = 0; i < localStorage.length; i++) {
			const key = localStorage.key(i);
			if (key && key.startsWith('bb_help_content_')) {
				localStorage.removeItem(key);
			}
		}
	}
};

/**
 * Creates a debounced function that delays invoking func until after wait milliseconds have elapsed
 * since the last time the debounced function was invoked.
 *
 * @param {Function} func - The function to debounce.
 * @param {number} wait - The number of milliseconds to delay.
 * @returns {Function} The debounced function.
 */
export const debounce = (func, wait) => {
	let timeout;

	return function executedFunction(...args) {
		const later = () => {
			clearTimeout(timeout);
			func(...args);
		};

		clearTimeout(timeout);
		timeout = setTimeout(later, wait);
	};
};

/**
 * Resolve help content ID from a URL or raw ID.
 * Backend may pass help_url as full URL (e.g. admin.php?page=bp-help&article=127197).
 *
 * @param {string} contentId - URL with article= param or numeric/slug ID.
 * @returns {string} Resolved article ID for the ht-kb API.
 */
const resolveHelpContentId = (contentId) => {
	if (!contentId || typeof contentId !== 'string') {
		return '';
	}
	const trimmed = contentId.trim();

	// Defense in depth: PHP's esc_url_raw() prepends 'http://' to bare numeric
	// strings, so a help_url registered as the bare KB article ID '636101' can
	// arrive here as 'http://636101'. The PHP layer guards against this in
	// bb_sanitize_help_url(), but we strip the synthetic scheme here too so a
	// regression in any caller surfaces as a working article load instead of a
	// 404 to wp/v2/ht-kb/http://<id>.
	const bareIdFromBadScheme = trimmed.match(/^https?:\/\/(\d+)\/?$/i);
	if (bareIdFromBadScheme) {
		return bareIdFromBadScheme[1];
	}

	// Full URL: extract article query param.
	if (trimmed.startsWith('http') || trimmed.includes('?')) {
		try {
			const url = trimmed.startsWith('http') ? trimmed : `https://example.com?${trimmed.split('?')[1] || ''}`;
			const params = new URL(url).searchParams;
			const article = params.get('article');
			if (article) {
				return String(article);
			}
		} catch (e) {
			// Fall through to use trimmed as-is.
		}
	}
	return trimmed;
};

/**
 * Fetch help content from the BuddyBoss knowledge base API.
 * Implements caching to avoid unnecessary API calls.
 *
 * @param {string} contentId - The ID of the help content to fetch, or a URL containing article=.
 * @returns {Promise} Promise that resolves to help content object.
 */
export const fetchHelpContent = async (contentId) => {
	if (!contentId) {
		throw new Error('Content ID is required');
	}

	const kbId = resolveHelpContentId(contentId);
	if (!kbId) {
		throw new Error('Could not determine help article ID');
	}

	const cacheKey = `bb_help_content_${kbId}`;
	const cached = getFromCache(cacheKey);
	if (cached) {
		return cached;
	}

	try {
		const response = await fetch(`https://buddyboss.com/wp-json/wp/v2/ht-kb/${kbId}`);
		if (!response.ok) {
			const message = `Failed to fetch help content (${response.status})`;
			throw new Error(message);
		}
		const data = await response.json();

		// Prepare content object
		const contentObject = {
			title: data.title?.rendered ?? '',
			content: data.content?.rendered ?? '',
			videoId: data.acf?.video_id || null,
			imageUrl: data.acf?.featured_image || null,
		};

		saveToCache(cacheKey, contentObject);
		return contentObject;
	} catch (error) {
		// Log with context; rethrow so caller can show user message.
		console.error('Error fetching help content:', error.message || error);
		throw error;
	}
};

