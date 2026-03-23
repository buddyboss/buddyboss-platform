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

/**
 * Get appearance settings (bp_nouveau_appearance option)
 *
 * @return {Promise} Promise resolving to appearance settings
 */
export function getAppearanceSettings() {
	return ajaxFetch('bb_admin_get_appearance_settings');
}

/**
 * Save appearance settings
 *
 * @param {Object} settings - Settings object to save
 * @return {Promise} Promise resolving to response
 */
export function saveAppearanceSettings(settings) {
	return ajaxFetch('bb_admin_save_appearance_settings', settings);
}

/*
|--------------------------------------------------------------------------
| Activity AJAX Functions
|--------------------------------------------------------------------------
*/

/**
 * Get activity types
 *
 * @return {Promise} Promise resolving to activity types array
 */
export function getActivityTypes() {
	return ajaxFetch('bb_admin_get_activity_types');
}

/**
 * Get activity topics
 *
 * @return {Promise} Promise resolving to activity topics array
 */
export function getActivityTopics() {
	return ajaxFetch('bb_admin_get_activity_topics');
}

/**
 * Get activities list
 *
 * @param {Object} params - Query parameters
 * @return {Promise} Promise resolving to activities data
 */
export function getActivities(params = {}) {
	return ajaxFetch('bb_admin_get_activities', params);
}

/**
 * Get single activity
 *
 * @param {number} activityId - Activity ID
 * @return {Promise} Promise resolving to activity data
 */
export function getActivity(activityId) {
	return ajaxFetch('bb_admin_get_activity', { activity_id: activityId });
}

/**
 * Update activity
 *
 * @param {number} activityId - Activity ID
 * @param {Object} data       - Activity data to update
 * @return {Promise} Promise resolving to updated activity
 */
export function updateActivity(activityId, data) {
	return ajaxFetch('bb_admin_update_activity', { activity_id: activityId, ...data });
}

/**
 * Delete activity
 *
 * @param {number} activityId - Activity ID
 * @return {Promise} Promise resolving to response
 */
export function deleteActivity(activityId) {
	return ajaxFetch('bb_admin_delete_activity', { activity_id: activityId });
}

/**
 * Mark activity as spam/ham
 *
 * @param {number}  activityId - Activity ID
 * @param {boolean} isSpam     - Whether to mark as spam (true) or ham (false)
 * @return {Promise} Promise resolving to response
 */
export function spamActivity(activityId, isSpam = true) {
	return ajaxFetch('bb_admin_spam_activity', { activity_id: activityId, is_spam: isSpam ? '1' : '0' });
}

/*
|--------------------------------------------------------------------------
| Groups AJAX Functions
|--------------------------------------------------------------------------
*/

/**
 * Get groups list
 *
 * @param {Object} params - Query parameters
 * @return {Promise} Promise resolving to groups data
 */
export function getGroups(params = {}) {
	return ajaxFetch('bb_admin_get_groups', params);
}

/**
 * Get single group
 *
 * @param {number} groupId - Group ID
 * @return {Promise} Promise resolving to group data
 */
export function getGroup(groupId) {
	return ajaxFetch('bb_admin_get_group', { group_id: groupId });
}

/**
 * Create a new group
 *
 * @param {Object} data - Group data
 * @return {Promise} Promise resolving to created group
 */
export function createGroup(data) {
	return ajaxFetch('bb_admin_create_group', data);
}

/**
 * Update a group
 *
 * @param {number} groupId - Group ID
 * @param {Object} data    - Group data to update
 * @return {Promise} Promise resolving to updated group
 */
export function updateGroup(groupId, data) {
	return ajaxFetch('bb_admin_update_group', { group_id: groupId, ...data });
}

/**
 * Delete a group
 *
 * @param {number} groupId - Group ID
 * @return {Promise} Promise resolving to response
 */
export function deleteGroup(groupId) {
	return ajaxFetch('bb_admin_delete_group', { group_id: groupId });
}

/**
 * Get group types
 *
 * @return {Promise} Promise resolving to group types array
 */
export function getGroupTypes() {
	return ajaxFetch('bb_admin_get_group_types');
}

/**
 * Create a group type
 *
 * @param {Object} data - Group type data
 * @return {Promise} Promise resolving to created group type
 */
export function createGroupType(data) {
	return ajaxFetch('bb_admin_create_group_type', data);
}

/**
 * Update a group type
 *
 * @param {number} typeId - Group type ID
 * @param {Object} data   - Group type data to update
 * @return {Promise} Promise resolving to updated group type
 */
export function updateGroupType(typeId, data) {
	return ajaxFetch('bb_admin_update_group_type', { type_id: typeId, ...data });
}

/**
 * Delete a group type
 *
 * @param {number} typeId - Group type ID
 * @return {Promise} Promise resolving to response
 */
export function deleteGroupType(typeId) {
	return ajaxFetch('bb_admin_delete_group_type', { type_id: typeId });
}
