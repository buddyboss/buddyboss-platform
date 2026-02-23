/**
 * BuddyBoss Admin AJAX Utilities
 *
 * @package BuddyBoss
 * @since BuddyBoss [BBVERSION]
 */

/**
 * Make an AJAX request to WordPress admin-ajax.php
 *
 * @param {string} action  - The AJAX action name
 * @param {Object} data    - Additional data to send
 * @param {Object} options - Optional fetch options (e.g. { signal } for AbortController)
 * @return {Promise} Promise resolving to response data
 */
export function ajaxFetch(action, data = {}, options = {}) {
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
		signal: options.signal,
	}).then((response) => {
		if (!response.ok) {
			throw new Error('HTTP ' + response.status + ': ' + response.statusText);
		}
		return response.json();
	});
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
 * Toggle a feature (activate or deactivate)
 *
 * @param {string}  featureId - Feature ID
 * @param {boolean} active    - True to activate, false to deactivate
 * @param {Object}  options   - Optional fetch options (e.g. { signal } for AbortController)
 * @return {Promise} Promise resolving to response
 */
export function toggleFeature(featureId, active, options = {}) {
	return ajaxFetch('bb_admin_toggle_feature', {
		feature_id: featureId,
		status: active ? 'active' : 'inactive',
	}, options);
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

/**
 * Get all group types
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @return {Promise} Promise resolving to group types array
 */
export function getGroupTypes() {
	return ajaxFetch('bb_admin_get_group_types');
}

/**
 * Create a new group type
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {Object} data - Group type data
 * @return {Promise} Promise resolving to response
 */
export function createGroupType(data) {
	return ajaxFetch('bb_admin_create_group_type', data);
}

/**
 * Update an existing group type
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {number} typeId - Group type post ID
 * @param {Object} data   - Group type data
 * @return {Promise} Promise resolving to response
 */
export function updateGroupType(typeId, data) {
	return ajaxFetch('bb_admin_update_group_type', { type_id: typeId, ...data });
}

/**
 * Delete a group type
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {number} typeId - Group type post ID
 * @return {Promise} Promise resolving to response
 */
export function deleteGroupType(typeId) {
	return ajaxFetch('bb_admin_delete_group_type', { type_id: typeId });
}

/**
 * Get groups listing with pagination, filters, and sorting.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {Object} data    - Query parameters (page, per_page, search, status, sort, group_type, include_meta).
 * @param {Object} options - Optional fetch options (e.g. { signal } for AbortController).
 * @return {Promise} Promise resolving to response.
 */
export function getGroups(data, options) {
	return ajaxFetch('bb_admin_get_groups', data, options);
}

/**
 * Delete a single group.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {number} groupId - Group ID.
 * @return {Promise} Promise resolving to response.
 */
export function deleteGroup(groupId) {
	return ajaxFetch('bb_admin_delete_group', { group_id: groupId });
}

/**
 * Perform bulk action on groups.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {Array}  groupIds - Array of group IDs.
 * @param {string} action   - Bulk action to perform.
 * @return {Promise} Promise resolving to response.
 */
export function groupBulkAction(groupIds, action) {
	return ajaxFetch('bb_admin_group_bulk_action', {
		group_ids: groupIds.join(','),
		do_action: action,
	});
}
