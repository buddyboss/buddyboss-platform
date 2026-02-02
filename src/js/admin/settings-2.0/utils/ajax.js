/**
 * BuddyBoss Admin AJAX Utilities
 *
 * @package BuddyBoss
 * @since BuddyBoss 3.0.0
 */

/**
 * Make an AJAX request to WordPress admin-ajax.php
 *
 * @param {string} action - The AJAX action name
 * @param {Object} data   - Additional data to send
 * @return {Promise} Promise resolving to response data
 */
export function ajaxFetch(action, data = {}) {
	const ajaxUrl = window.bbAdminData?.ajaxUrl || '/wp-admin/admin-ajax.php';
	const nonce = window.bbAdminData?.ajaxNonce || '';

	const formData = new FormData();
	formData.append('action', action);
	formData.append('nonce', nonce);

	// Append additional data
	Object.keys(data).forEach((key) => {
		formData.append(key, data[key]);
	});

	return fetch(ajaxUrl, {
		method: 'POST',
		credentials: 'same-origin',
		body: formData,
	}).then((response) => response.json());
}

/**
 * Get all features
 *
 * @return {Promise} Promise resolving to features array
 */
export function getFeatures() {
	return ajaxFetch('bb_admin_get_features');
}

// Module-level cache for features list
let featuresCache = null;
let featuresCachePromise = null;

/**
 * Get features with caching (prevents duplicate AJAX calls)
 *
 * @return {Promise} Promise resolving to features array
 */
export async function getCachedFeatures() {
	if (featuresCache) {
		return featuresCache;
	}

	if (featuresCachePromise) {
		return featuresCachePromise;
	}

	featuresCachePromise = getFeatures().then((response) => {
		if (response.success && response.data) {
			featuresCache = response.data;
			return featuresCache;
		}
		return [];
	});

	return featuresCachePromise;
}

/**
 * Invalidate features cache - call when features are activated/deactivated
 */
export function invalidateFeaturesCache() {
	featuresCache = null;
	featuresCachePromise = null;
}

/**
 * Update a feature in the cache
 *
 * @param {string} featureId   Feature ID
 * @param {Object} updatedData Updated feature data
 */
export function updateFeatureInCache(featureId, updatedData) {
	if (featuresCache && Array.isArray(featuresCache)) {
		featuresCache = featuresCache.map((feature) =>
			feature.id === featureId ? { ...feature, ...updatedData } : feature
		);
	}
}

/**
 * Activate a feature
 *
 * @param {string} featureId - Feature ID to activate
 * @return {Promise} Promise resolving to response
 */
export function activateFeature(featureId) {
	return ajaxFetch('bb_admin_activate_feature', { feature_id: featureId });
}

/**
 * Deactivate a feature
 *
 * @param {string} featureId - Feature ID to deactivate
 * @return {Promise} Promise resolving to response
 */
export function deactivateFeature(featureId) {
	return ajaxFetch('bb_admin_deactivate_feature', { feature_id: featureId });
}

/**
 * Search settings
 *
 * @param {string} query - Search query
 * @return {Promise} Promise resolving to search results
 */
export function searchSettings(query) {
	return ajaxFetch('bb_admin_search_settings', { query });
}

/**
 * Get feature settings
 *
 * @param {string} featureId - Feature ID
 * @return {Promise} Promise resolving to feature settings
 */
export function getFeatureSettings(featureId) {
	return ajaxFetch('bb_admin_get_feature_settings', { feature_id: featureId });
}

/**
 * Get platform settings (WordPress options)
 *
 * @param {Array} options - Array of option names to retrieve
 * @return {Promise} Promise resolving to settings object
 */
export function getPlatformSettings(options) {
	return ajaxFetch('bb_admin_get_platform_settings', { options });
}

/**
 * Save a platform setting (WordPress option)
 *
 * @param {string} optionName  - Option name
 * @param {*}      optionValue - Option value
 * @return {Promise} Promise resolving to response
 */
export function savePlatformSetting(optionName, optionValue) {
	return ajaxFetch('bb_admin_save_platform_setting', { 
		option_name: optionName, 
		option_value: optionValue 
	});
}
