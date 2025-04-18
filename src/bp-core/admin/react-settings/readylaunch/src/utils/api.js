import apiFetch from '@wordpress/api-fetch';

/**
 * Fetch ReadyLaunch settings from the WordPress REST API.
 *
 * @returns {Promise} Promise that resolves to settings object.
 */
export const fetchSettings = async() => {
	try {
		return await apiFetch(
			{
				path: '/buddyboss/v1/readylaunch/settings',
				method: 'GET',
			}
		);
	} catch (error) {
		console.error( 'Error fetching ReadyLaunch settings:', error );
		return null;
	}
};

/**
 * Save ReadyLaunch settings to the WordPress REST API.
 *
 * @param {Object} settings - The settings object to save.
 * @returns {Promise} Promise that resolves to updated settings object.
 */
export const saveSettings = async( settings ) => {
	try {
		return await apiFetch(
			{
				path: '/buddyboss/v1/readylaunch/settings',
				method: 'POST',
				data: settings,
			}
		);
	} catch (error) {
		console.error( 'Error saving ReadyLaunch settings:', error );
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