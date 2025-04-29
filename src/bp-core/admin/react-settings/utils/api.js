import apiFetch from '@wordpress/api-fetch';

// Store the initial settings for comparison
let initialSettings = null;

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

	const changedSettings = {};

	// Helper function to compare nested objects
	const compareObjects = (current, initial, path = '') => {
		for (const key in current) {
			const currentPath = path ? `${path}.${key}` : key;
			
			// Skip if key doesn't exist in initial settings
			if (!(key in initial)) {
				// Set the changed value in the changedSettings object
				const pathParts = currentPath.split('.');
				let target = changedSettings;
				
				for (let i = 0; i < pathParts.length - 1; i++) {
					target[pathParts[i]] = target[pathParts[i]] || {};
					target = target[pathParts[i]];
				}
				
				target[pathParts[pathParts.length - 1]] = current[key];
				continue;
			}

			if (typeof current[key] === 'object' && current[key] !== null) {
				if (Array.isArray(current[key])) {
					// Handle arrays by comparing each element
					if (JSON.stringify(current[key]) !== JSON.stringify(initial[key])) {
						const pathParts = currentPath.split('.');
						let target = changedSettings;
						
						for (let i = 0; i < pathParts.length - 1; i++) {
							target[pathParts[i]] = target[pathParts[i]] || {};
							target = target[pathParts[i]];
						}
						
						target[pathParts[pathParts.length - 1]] = current[key];
					}
				} else {
					// Handle nested objects
					compareObjects(current[key], initial[key], currentPath);
				}
			} else if (current[key] !== initial[key]) {
				// Handle primitive values
				const pathParts = currentPath.split('.');
				let target = changedSettings;
				
				for (let i = 0; i < pathParts.length - 1; i++) {
					target[pathParts[i]] = target[pathParts[i]] || {};
					target = target[pathParts[i]];
				}
				
				target[pathParts[pathParts.length - 1]] = current[key];
			}
		}
	};

	compareObjects(currentSettings, initialSettings);
	return changedSettings;
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