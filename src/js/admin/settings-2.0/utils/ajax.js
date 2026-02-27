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

	// Append additional data, handling arrays and objects with bracket notation.
	Object.keys(data).forEach((key) => {
		var val = data[key];
		if ( Array.isArray( val ) ) {
			val.forEach(function ( item ) {
				formData.append( key + '[]', item );
			});
		} else if ( val && 'object' === typeof val && ! ( val instanceof Blob ) ) {
			Object.keys( val ).forEach(function ( subKey ) {
				formData.append( key + '[' + subKey + ']', val[subKey] );
			});
		} else {
			formData.append( key, val );
		}
	});

	return fetch(ajaxUrl, {
		method: 'POST',
		credentials: 'same-origin',
		body: formData,
		signal: options.signal,
	}).then((response) => {
		if (!response.ok) {
			// Parse JSON body for server error messages (e.g., 403 from wp_send_json_error).
			return response.json().then((body) => {
				if (body && body.data && body.data.message) {
					throw new Error(body.data.message);
				}
				throw new Error('HTTP ' + response.status + ': ' + response.statusText);
			}).catch((parseError) => {
				// If JSON parsing itself failed, re-throw with HTTP status.
				if (parseError.message && !parseError.message.startsWith('HTTP ')) {
					throw parseError;
				}
				throw new Error('HTTP ' + response.status + ': ' + response.statusText);
			});
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
	}).catch((error) => {
		featuresCachePromise = null;
		throw error;
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
 * @since BuddyBoss [BBVERSION]
 *
 * @param {Array}  options    - Array of option names to retrieve
 * @param {Object} fetchOptions - Optional fetch options (e.g. { signal }).
 * @return {Promise} Promise resolving to settings object
 */
export function getPlatformSettings(options, fetchOptions) {
	return ajaxFetch('bb_admin_get_platform_settings', { options }, fetchOptions || {});
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
 * Create a new group.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {Object} data - Group data (name, description, status).
 * @return {Promise} Promise resolving to response.
 */
export function createGroup(data) {
	return ajaxFetch('bb_admin_create_group', data);
}

/**
 * Get a single group with registered meta fields.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {number} groupId - Group ID.
 * @param {Object} options - Optional fetch options.
 * @return {Promise} Promise resolving to response.
 */
export function getGroup(groupId, options) {
	return ajaxFetch('bb_admin_get_group', { group_id: groupId }, options);
}

/**
 * Save group data from the edit modal.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {Object} data    - Group data payload.
 * @param {Object} options - Optional fetch options.
 * @return {Promise} Promise resolving to response.
 */
export function saveGroup(data, options) {
	return ajaxFetch('bb_admin_save_group', data, options);
}

/**
 * Get group members with pagination.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {number} groupId - Group ID.
 * @param {Object} params  - Optional params (page, per_page).
 * @param {Object} options - Optional fetch options.
 * @return {Promise} Promise resolving to response.
 */
export function getGroupMembers(groupId, params, options) {
	return ajaxFetch('bb_admin_get_group_members', Object.assign({ group_id: groupId }, params), options);
}

/**
 * Add, remove, or change role of a group member.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {Object} data - Member data (group_id, user_id, role, action_type).
 * @return {Promise} Promise resolving to response.
 */
export function updateGroupMember(data) {
	return ajaxFetch('bb_admin_update_group_member', data);
}

/**
 * Get all member/profile types.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @return {Promise} Promise resolving to member types array.
 */
export function getMemberTypes() {
	return ajaxFetch('bb_admin_get_member_types');
}

/**
 * Create a new member/profile type.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {Object} data - Member type data.
 * @return {Promise} Promise resolving to response.
 */
export function createMemberType(data) {
	return ajaxFetch('bb_admin_create_member_type', data);
}

/**
 * Update an existing member/profile type.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {number} typeId - Member type post ID.
 * @param {Object} data   - Member type data.
 * @return {Promise} Promise resolving to response.
 */
export function updateMemberType(typeId, data) {
	return ajaxFetch('bb_admin_update_member_type', Object.assign({}, data, { type_id: typeId }));
}

/**
 * Delete a member/profile type.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {number} typeId - Member type post ID.
 * @return {Promise} Promise resolving to response.
 */
export function deleteMemberType(typeId) {
	return ajaxFetch('bb_admin_delete_member_type', { type_id: typeId });
}

/**
 * Get topics for a group.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {number} groupId - Group ID.
 * @param {Object} options - Optional fetch options (e.g. { signal } for AbortController).
 * @return {Promise} Promise resolving to response.
 */
export function getGroupTopics(groupId, options) {
	return ajaxFetch('bb_admin_get_group_topics', { group_id: groupId }, options || {});
}

/**
 * Perform bulk action on groups.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {Array}  groupIds  Array of group IDs.
 * @param {string} action    Bulk action to perform.
 * @param {Object} extraData Optional extra data to send with the request.
 * @return {Promise} Promise resolving to response.
 */
export function groupBulkAction(groupIds, action, extraData) {
	var data = {
		group_ids: groupIds.join(','),
		do_action: action,
	};
	if (extraData) {
		Object.keys(extraData).forEach(function (key) {
			data[key] = extraData[key];
		});
	}
	return ajaxFetch('bb_admin_group_bulk_action', data);
}

/**
 * Search forums for the async select field in the group edit modal.
 *
 * Passes an optional search term and page number to the server.
 * Returns { results: [{ value, label }], has_more: bool }.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {Object} params          Request parameters.
 * @param {string} params.term         Search term (empty = browse all).
 * @param {number} params.page         Page number (default 1).
 * @param {number} params.selected_id  Forum ID to resolve on initial load.
 * @param {Object} options             Fetch options (e.g. { signal }).
 * @return {Promise} Promise resolving to { results, has_more }.
 */
export function forumAutocomplete( params, options ) {
	return ajaxFetch( 'bb_admin_forum_autocomplete', params || {}, options || {} );
}
