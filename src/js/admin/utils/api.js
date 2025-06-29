import apiFetch from '@wordpress/api-fetch';

// Store the initial settings for comparison
let initialSettings = null;
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
 * Fetch ReadyLaunch settings from the WordPress REST API.
 *
 * @returns {Promise} Promise that resolves to settings object.
 */
export const fetchSettings = async() => {
	try {
		const settings = await apiFetch(
			{
				path: '/buddyboss/v1/settings',
				method: 'GET',
			}
		);
		// Store initial settings for comparison
		initialSettings = JSON.parse(JSON.stringify(settings));
		return settings;
	} catch (error) {
		console.error( 'Error fetching settings:', error );
		return null;
	}
};

/**
 * Get only the changed settings by comparing with initial settings
 * 
 * @param {Object} currentSettings - Current settings object
 * @returns {Object} Object containing only changed settings
 */
const getChangedSettings = (currentSettings) => {
	if (!initialSettings) {
		return currentSettings;
	}

	return currentSettings;
};

/**
 * Save settings to the WordPress REST API.
 *
 * @param {Object} settings - The settings object to save.
 * @returns {Promise} Promise that resolves to updated settings object.
 */
export const saveSettings = async(settings) => {
	if (!settings) {
		console.error('No settings provided to save');
		return null;
	}

	try {
		// Get only changed settings
		const changedSettings = getChangedSettings(settings);
		
		// If no changes, return early
		if (Object.keys(changedSettings).length === 0) {
			return settings;
		}

		// Clean the changed settings object
		const cleanChangedSettings = { ...changedSettings };
		if (cleanChangedSettings.hasOwnProperty('_tempIds')) {
			delete cleanChangedSettings._tempIds;
		}

		const response = await apiFetch({
			path: '/buddyboss/v1/settings',
			method: 'POST',
			data: cleanChangedSettings,
		});

		// Update initial settings with the new values
		if (response) {
			initialSettings = JSON.parse(JSON.stringify(response));
		}

		return response;
	} catch (error) {
		console.error('Error saving settings:', error);
		return null;
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
 * Fetch help content from the BuddyBoss knowledge base API.
 * Implements caching to avoid unnecessary API calls.
 *
 * @param {string} contentId - The ID of the help content to fetch.
 * @returns {Promise} Promise that resolves to help content object.
 */
export const fetchHelpContent = async (contentId) => {
	if (!contentId) {
		throw new Error('Content ID is required');
	}

	const cacheKey = `bb_help_content_${contentId}`;
	const cached = getFromCache(cacheKey);
	if (cached) {
		return cached;
	}

	try {
		const response = await fetch(`https://buddyboss.com/wp-json/wp/v2/ht-kb/${contentId}`);
		if (!response.ok) {
			throw new Error('Failed to fetch help content');
		}
		const data = await response.json();

		// Prepare content object
		const contentObject = {
			title: data.title.rendered,
			content: data.content.rendered,
			videoId: data.acf?.video_id || null,
			imageUrl: data.acf?.featured_image || null
		};

		saveToCache(cacheKey, contentObject);
		return contentObject;
	} catch (error) {
		console.error('Error fetching help content:', error);
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