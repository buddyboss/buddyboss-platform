import apiFetch from '@wordpress/api-fetch';

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

export const fetchMenus = async () => {
	try {
	  // Try the most common endpoint for menus
	  const menus = await apiFetch({ path: '/wp/v2/menus?per_page=99', method: 'GET' });
	  return menus;
	} catch (e) {
	  // Try fallback endpoint if needed
	  try {
		const menus = await apiFetch({ path: '/menus/v1/menus?per_page=99', method: 'GET' });
		return menus;
	  } catch (err) {
		console.error('Error fetching menus:', err);
		return [];
	  }
	}
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

/**
 * Fetch help categories from the BuddyBoss knowledge base API.
 * Implements localStorage caching to avoid unnecessary API calls.
 *
 * @param {string} parentId - The parent category ID to fetch children for.
 * @returns {Promise} Promise that resolves to an array of category objects.
 */
export const fetchHelpCategories = async (parentId = null) => {
	const cacheKey = `bb_rl_help_categories_${parentId || 'root'}`;
	const cached = getFromCache(cacheKey);
	if (cached) {
		return cached;
	}

	let apiUrl = 'https://www.buddyboss.com/wp-json/wp/v2/ht-kb-category?orderby=term_order&per_page=99';
	if (parentId) {
		apiUrl += `&parent=${parentId}`;
	}

	try {
		const response = await fetch(apiUrl);
		if (!response.ok) {
			throw new Error('Failed to fetch help categories');
		}
		const categories = await response.json();

		saveToCache(cacheKey, categories);
		return categories;
	} catch (error) {
		console.error('Error fetching help categories:', error);
		throw error;
	}
};
