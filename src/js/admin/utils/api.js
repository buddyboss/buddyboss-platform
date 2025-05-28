import apiFetch from '@wordpress/api-fetch';

// Store the initial settings for comparison
let initialSettings = null;

// Static cache for help content
const helpContentCache = new Map();

// Cache duration in milliseconds (e.g., 1 hour)
const CACHE_DURATION = 60 * 60 * 1000;

/**
 * Check if cached content is still valid
 * 
 * @param {Object} cachedData - The cached data object
 * @returns {boolean} Whether the cache is still valid
 */
const isCacheValid = (cachedData) => {
    if (!cachedData || !cachedData.timestamp) {
        return false;
    }
    const now = Date.now();
    return (now - cachedData.timestamp) < CACHE_DURATION;
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

    // Check cache first
    const cachedContent = helpContentCache.get(contentId);
    if (cachedContent && isCacheValid(cachedContent)) {
        console.log('Returning cached help content for ID:', contentId);
        return cachedContent.data;
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

        // Cache the content with timestamp
        helpContentCache.set(contentId, {
            data: contentObject,
            timestamp: Date.now()
        });

        return contentObject;
    } catch (error) {
        console.error('Error fetching help content:', error);
        throw error;
    }
};

/**
 * Clear the help content cache for a specific ID or all cache if no ID provided
 * 
 * @param {string} [contentId] - Optional ID of the content to clear from cache
 */
export const clearHelpContentCache = (contentId) => {
    if (contentId) {
        helpContentCache.delete(contentId);
    } else {
        helpContentCache.clear();
    }
};