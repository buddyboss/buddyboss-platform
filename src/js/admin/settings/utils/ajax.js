/**
 * BuddyBoss Admin AJAX Utilities
 *
 * @package BuddyBoss
 * @since BuddyBoss [BBVERSION]
 */

/**
 * Recursively append a value to FormData using PHP bracket notation.
 *
 * Handles scalars, arrays, nested objects, and Blobs at any depth.
 * Example: appendToFormData( fd, 'order', { 5: { 0: 381 } } )
 *   → order[5][0] = 381
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {FormData} formData The FormData instance to append to.
 * @param {string}   key      The current bracket-notation key.
 * @param {*}        value    The value to append.
 * @param {Array}    seen     Visited objects tracker to prevent circular reference loops.
 */
function appendToFormData( formData, key, value, seen ) {
	if ( ! Array.isArray( seen ) ) {
		seen = [];
	}
	if ( null === value || 'undefined' === typeof value ) {
		return;
	}
	if ( value instanceof Blob ) {
		formData.append( key, value );
	} else if ( Array.isArray( value ) ) {
		if ( -1 !== seen.indexOf( value ) ) {
			return;
		}
		seen.push( value );
		value.forEach( function ( item, idx ) {
			appendToFormData( formData, key + '[' + idx + ']', item, seen );
		} );
	} else if ( 'object' === typeof value ) {
		if ( -1 !== seen.indexOf( value ) ) {
			return;
		}
		seen.push( value );
		Object.keys( value ).forEach( function ( subKey ) {
			appendToFormData( formData, key + '[' + subKey + ']', value[ subKey ], seen );
		} );
	} else if ( 'boolean' === typeof value ) {
		// Convert booleans to 1/0 so PHP empty() works correctly
		// ("false" string is truthy in PHP, "0" is falsy).
		formData.append( key, value ? '1' : '0' );
	} else {
		formData.append( key, value );
	}
}

/**
 * Make an AJAX request to WordPress admin-ajax.php
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {string} action  - The AJAX action name
 * @param {Object} data    - Additional data to send
 * @param {Object} options - Optional fetch options (e.g. { signal } for AbortController)
 * @return {Promise} Promise resolving to response data
 */
export function ajaxFetch( action, data, options ) {
	data = data || {};
	options = options || {};

	var ajaxUrl = ( window.bbAdminData && window.bbAdminData.ajaxUrl ) || '/wp-admin/admin-ajax.php';
	var nonce = ( window.bbAdminData && window.bbAdminData.ajaxNonce ) || '';

	var formData = new FormData();
	formData.append( 'action', action );
	formData.append( 'nonce', nonce );

	// Append additional data using recursive bracket notation serialization.
	Object.keys( data ).forEach( function ( key ) {
		appendToFormData( formData, key, data[ key ] );
	} );

	return fetch( ajaxUrl, {
		method: 'POST',
		credentials: 'same-origin',
		body: formData,
		signal: options.signal,
	} ).then( function ( response ) {
		if ( ! response.ok ) {
			// Parse JSON body for server error messages (e.g., 403 from wp_send_json_error).
			return response.json().then( function ( body ) {
				if ( body && body.data && body.data.message ) {
					throw new Error( body.data.message );
				}
				throw new Error( 'HTTP ' + response.status + ': ' + response.statusText );
			} ).catch( function ( parseError ) {
				// If we already built a meaningful Error, re-throw it.
				// SyntaxError means JSON.parse() failed; fall through to HTTP status message.
				if ( ! ( parseError instanceof SyntaxError ) ) {
					throw parseError;
				}
				throw new Error( 'HTTP ' + response.status + ': ' + response.statusText );
			} );
		}
		return response.json();
	} );
}

/**
 * Get all features
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @return {Promise} Promise resolving to features array
 */
export function getFeatures() {
	return ajaxFetch( 'bb_admin_get_features' );
}

// Module-level cache for features list.
var featuresCache = null;
var featuresCachePromise = null;

/**
 * Get features with caching (prevents duplicate AJAX calls)
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @return {Promise} Promise resolving to features array
 */
export function getCachedFeatures() {
	if ( featuresCache ) {
		return Promise.resolve( featuresCache );
	}

	if ( featuresCachePromise ) {
		return featuresCachePromise;
	}

	featuresCachePromise = getFeatures().then( function ( response ) {
		if ( response.success && response.data ) {
			featuresCache = response.data;
			return featuresCache;
		}
		// Clear promise so next call retries instead of returning empty forever.
		featuresCachePromise = null;
		return [];
	} ).catch( function ( error ) {
		featuresCachePromise = null;
		throw error;
	} );

	return featuresCachePromise;
}

/**
 * Invalidate features cache - call when features are activated/deactivated
 *
 * @since BuddyBoss [BBVERSION]
 */
export function invalidateFeaturesCache() {
	featuresCache = null;
	featuresCachePromise = null;
}

/**
 * Update a feature in the cache
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {string} featureId   Feature ID
 * @param {Object} updatedData Updated feature data
 */
export function updateFeatureInCache( featureId, updatedData ) {
	if ( featuresCache && Array.isArray( featuresCache ) ) {
		featuresCache = featuresCache.map( function ( feature ) {
			if ( feature.id === featureId ) {
				return Object.assign( {}, feature, updatedData );
			}
			return feature;
		} );
	}
}

/**
 * Toggle a feature (activate or deactivate)
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {string}  featureId - Feature ID
 * @param {boolean} active    - True to activate, false to deactivate
 * @param {Object}  options   - Optional fetch options (e.g. { signal } for AbortController)
 * @return {Promise} Promise resolving to response
 */
export function toggleFeature( featureId, active, options ) {
	return ajaxFetch( 'bb_admin_toggle_feature', {
		feature_id: featureId,
		status: active ? 'active' : 'inactive',
	}, options || {} );
}

/**
 * Search settings
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {string} query - Search query
 * @return {Promise} Promise resolving to search results
 */
export function searchSettings( query ) {
	return ajaxFetch( 'bb_admin_search_settings', { query: query } );
}

/**
 * Get feature settings
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {string} featureId - Feature ID
 * @return {Promise} Promise resolving to feature settings
 */
export function getFeatureSettings( featureId ) {
	return ajaxFetch( 'bb_admin_get_feature_settings', { feature_id: featureId } );
}

/**
 * Get platform settings (WordPress options)
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {Array}  options      - Array of option names to retrieve
 * @param {Object} fetchOptions - Optional fetch options (e.g. { signal }).
 * @return {Promise} Promise resolving to settings object
 */
export function getPlatformSettings( options, fetchOptions ) {
	return ajaxFetch( 'bb_admin_get_platform_settings', { options: options }, fetchOptions || {} );
}

/**
 * Save a platform setting (WordPress option)
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {string} optionName  - Option name
 * @param {*}      optionValue - Option value
 * @return {Promise} Promise resolving to response
 */
export function savePlatformSetting( optionName, optionValue ) {
	return ajaxFetch( 'bb_admin_save_platform_setting', {
		option_name: optionName,
		option_value: optionValue,
	} );
}

/**
 * Get a page of group types.
 *
 * Server-side pagination — pass `page` (1-based) and `per_page` (clamped
 * server-side via PER_PAGE_CAP). Pass `include_meta: 0` on subsequent
 * paginated requests to skip the heavy `member_types` payload that only the
 * first load needs.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {Object} params  Optional. { page, per_page, include_meta }.
 * @param {Object} options Optional. Pass-through fetch options (e.g. signal).
 * @return {Promise} Promise resolving to { group_types, total, ... }.
 */
export function getGroupTypes( params, options ) {
	return ajaxFetch( 'bb_admin_get_group_types', params || {}, options );
}

/**
 * Create a new group type
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {Object} data - Group type data
 * @return {Promise} Promise resolving to response
 */
export function createGroupType( data ) {
	return ajaxFetch( 'bb_admin_create_group_type', data );
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
export function updateGroupType( typeId, data ) {
	return ajaxFetch( 'bb_admin_update_group_type', Object.assign( { type_id: typeId }, data ) );
}

/**
 * Delete a group type
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {number} typeId - Group type post ID
 * @return {Promise} Promise resolving to response
 */
export function deleteGroupType( typeId ) {
	return ajaxFetch( 'bb_admin_delete_group_type', { type_id: typeId } );
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
export function getGroups( data, options ) {
	return ajaxFetch( 'bb_admin_get_groups', data, options );
}

/**
 * Delete a single group.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {number} groupId - Group ID.
 * @return {Promise} Promise resolving to response.
 */
export function deleteGroup( groupId ) {
	return ajaxFetch( 'bb_admin_delete_group', { group_id: groupId } );
}

/**
 * Create a new group.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {Object} data - Group data (name, description, status).
 * @return {Promise} Promise resolving to response.
 */
export function createGroup( data ) {
	return ajaxFetch( 'bb_admin_create_group', data );
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
export function getGroup( groupId, options ) {
	return ajaxFetch( 'bb_admin_get_group', { group_id: groupId }, options );
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
export function saveGroup( data, options ) {
	return ajaxFetch( 'bb_admin_save_group', data, options );
}

/**
 * Get group members for a single role.
 *
 * Initial mount fires this 4× in parallel (once per role); pagination clicks
 * fire it for the affected role; debounced search fires it once per visible
 * role with the active search term. The server passes `search` through to
 * BP's `groups_get_group_members()` `search_terms` arg so per-role filter
 * chains (moderation, privacy, suspension, third-party hooks) apply
 * unchanged.
 *
 * Request:
 *   { role: 'admin'|'mod'|'member'|'banned', page, per_page, search }
 * Response:
 *   { success, data: { members: [...], total } }
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {number} groupId - Group ID.
 * @param {Object} params  - Request params (`role`, `page`, `per_page`, `search`).
 * @param {Object} options - Optional fetch options (e.g., AbortSignal).
 * @return {Promise} Promise resolving to response.
 */
export function getGroupMembers( groupId, params, options ) {
	return ajaxFetch( 'bb_admin_get_group_members', Object.assign( { group_id: groupId }, params ), options );
}

/**
 * Add, remove, or change role of a group member.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {Object} data - Member data (group_id, user_id, role, action_type).
 * @return {Promise} Promise resolving to response.
 */
export function updateGroupMember( data ) {
	return ajaxFetch( 'bb_admin_update_group_member', data );
}

/**
 * Get a page of member/profile types.
 *
 * Server-side pagination — pass `page` (1-based) and `per_page` (clamped
 * server-side to 100). Pass `include_meta: 0` on subsequent paginated
 * requests to skip the heavy auxiliary payload (group_types, wp_roles,
 * published_pages, member_types_summary) that only the first load needs.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {Object} params  Optional. { page, per_page, include_meta }.
 * @param {Object} options Optional. Pass-through fetch options (e.g. signal).
 * @return {Promise} Promise resolving to { member_types, total, ... }.
 */
export function getMemberTypes( params, options ) {
	return ajaxFetch( 'bb_admin_get_member_types', params || {}, options );
}

/**
 * Create a new member/profile type.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {Object} data - Member type data.
 * @return {Promise} Promise resolving to response.
 */
export function createMemberType( data ) {
	return ajaxFetch( 'bb_admin_create_member_type', data );
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
export function updateMemberType( typeId, data ) {
	return ajaxFetch( 'bb_admin_update_member_type', Object.assign( {}, data, { type_id: typeId } ) );
}

/**
 * Delete a member/profile type.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {number} typeId  - Member type post ID.
 * @param {Object} options - Optional fetch options (e.g. { signal } for AbortController).
 * @return {Promise} Promise resolving to response.
 */
export function deleteMemberType( typeId, options ) {
	return ajaxFetch( 'bb_admin_delete_member_type', { type_id: typeId }, options );
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
export function getGroupTopics( groupId, options ) {
	return ajaxFetch( 'bb_admin_get_group_topics', { group_id: groupId }, options || {} );
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
export function groupBulkAction( groupIds, action, extraData ) {
	var data = {
		group_ids: groupIds.join( ',' ),
		do_action: action,
	};
	if ( extraData ) {
		Object.keys( extraData ).forEach( function ( key ) {
			data[ key ] = extraData[ key ];
		} );
	}
	return ajaxFetch( 'bb_admin_group_bulk_action', data );
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

/**
 * Get forums listing with pagination, filters, and sorting.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {Object} data    - Query parameters (page, per_page, search, status, sort, include_meta).
 * @param {Object} options - Optional fetch options (e.g. { signal } for AbortController).
 * @return {Promise} Promise resolving to response.
 */
export function getForums( data, options ) {
	return ajaxFetch( 'bb_admin_get_forums', data, options );
}

/**
 * Get a single forum for the edit modal.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {number} forumId - Forum ID.
 * @param {Object} options - Optional fetch options.
 * @return {Promise} Promise resolving to response.
 */
export function getForum( forumId, options ) {
	return ajaxFetch( 'bb_admin_get_forum', { forum_id: forumId }, options );
}

/**
 * Create a new forum.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {Object} data - Forum data (name, slug, description, visibility, forum_status, parent_id, image_id).
 * @return {Promise} Promise resolving to response.
 */
export function createForum( data ) {
	return ajaxFetch( 'bb_admin_create_forum', data );
}

/**
 * Update an existing forum.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {Object} data    - Forum data payload.
 * @param {Object} options - Optional fetch options.
 * @return {Promise} Promise resolving to response.
 */
export function saveForum( data, options ) {
	return ajaxFetch( 'bb_admin_save_forum', data, options );
}

/**
 * Perform bulk action on forums.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {Array}  forumIds  Array of forum IDs.
 * @param {string} action    Bulk action to perform.
 * @param {Object} extraData Optional extra data to send with the request.
 * @return {Promise} Promise resolving to response.
 */
export function forumBulkAction( forumIds, action, extraData ) {
	var data = {
		forum_ids: forumIds.join( ',' ),
		do_action: action,
	};
	if ( extraData ) {
		Object.keys( extraData ).forEach( function ( key ) {
			data[ key ] = extraData[ key ];
		} );
	}
	return ajaxFetch( 'bb_admin_forum_bulk_action', data );
}

/**
 * Get discussions listing with pagination, filters, and sorting.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {Object} data    - Query parameters (page, per_page, search, forum_id, sort, include_meta).
 * @param {Object} options - Optional fetch options (e.g. { signal } for AbortController).
 * @return {Promise} Promise resolving to response.
 */
export function getDiscussions( data, options ) {
	return ajaxFetch( 'bb_admin_get_discussions', data, options );
}

/**
 * Get a single discussion for the edit modal.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {number} topicId - Topic ID.
 * @param {Object} options - Optional fetch options.
 * @return {Promise} Promise resolving to response.
 */
export function getDiscussion( topicId, options ) {
	return ajaxFetch( 'bb_admin_get_discussion', { topic_id: topicId }, options );
}

/**
 * Create a new discussion.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {Object} data - Discussion data (title, description, forum_id, type, topic_status, visibility, tags).
 * @return {Promise} Promise resolving to response.
 */
export function createDiscussion( data ) {
	return ajaxFetch( 'bb_admin_create_discussion', data );
}

/**
 * Update an existing discussion.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {Object} data    - Discussion data payload.
 * @param {Object} options - Optional fetch options.
 * @return {Promise} Promise resolving to response.
 */
export function saveDiscussion( data, options ) {
	return ajaxFetch( 'bb_admin_save_discussion', data, options );
}

/**
 * Delete a single discussion.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {number} topicId - Topic ID.
 * @return {Promise} Promise resolving to response.
 */
export function deleteDiscussion( topicId ) {
	return ajaxFetch( 'bb_admin_delete_discussion', { topic_id: topicId } );
}

/**
 * Perform bulk action on discussions.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {Array}  topicIds  Array of topic IDs.
 * @param {string} action    Bulk action to perform.
 * @param {Object} extraData Optional extra data to send with the request.
 * @return {Promise} Promise resolving to response.
 */
export function discussionBulkAction( topicIds, action, extraData ) {
	var data = {
		topic_ids: topicIds.join( ',' ),
		do_action: action,
	};
	if ( extraData ) {
		Object.keys( extraData ).forEach( function ( key ) {
			data[ key ] = extraData[ key ];
		} );
	}
	return ajaxFetch( 'bb_admin_discussion_bulk_action', data );
}

/**
 * Search topic tags for autocomplete suggestions.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {string} search  Search term.
 * @param {Object} options Fetch options (e.g. signal for AbortController).
 * @returns {Promise} AJAX promise.
 */
export function searchTopicTags( search, options ) {
	return ajaxFetch( 'bb_admin_topic_tag_autocomplete', { search: search }, options );
}

/**
 * Get paginated topic tags list.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {Object} data    Query params (page, per_page, search, include_meta).
 * @param {Object} options Fetch options (e.g. signal for AbortController).
 * @returns {Promise} AJAX promise.
 */
export function getTopicTags( data, options ) {
	return ajaxFetch( 'bb_admin_get_topic_tags', data || {}, options || {} );
}

/**
 * Get a single topic tag by term ID.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {number} termId  Term ID.
 * @param {Object} options Fetch options.
 * @returns {Promise} AJAX promise.
 */
export function getTopicTag( termId, options ) {
	return ajaxFetch( 'bb_admin_get_topic_tag', { term_id: termId }, options || {} );
}

/**
 * Create a new topic tag.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {Object} data Tag data (name, slug, description).
 * @returns {Promise} AJAX promise.
 */
export function createTopicTag( data ) {
	return ajaxFetch( 'bb_admin_create_topic_tag', data );
}

/**
 * Save (update) an existing topic tag.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {Object} data    Tag data (term_id, name, slug, description).
 * @param {Object} options Fetch options.
 * @returns {Promise} AJAX promise.
 */
export function saveTopicTag( data, options ) {
	return ajaxFetch( 'bb_admin_save_topic_tag', data, options || {} );
}

/**
 * Delete a topic tag.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {number} termId Term ID to delete.
 * @returns {Promise} AJAX promise.
 */
export function deleteTopicTag( termId ) {
	return ajaxFetch( 'bb_admin_delete_topic_tag', { term_id: termId } );
}

/**
 * Perform bulk action on topic tags.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {Array}  termIds Array of term IDs.
 * @param {string} action  Bulk action (e.g. 'delete').
 * @returns {Promise} AJAX promise.
 */
export function topicTagBulkAction( termIds, action ) {
	return ajaxFetch( 'bb_admin_topic_tag_bulk_action', {
		term_ids: termIds.join( ',' ),
		do_action: action,
	} );
}

/**
 * Get paginated replies list.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {Object} data    Query params (page, per_page, search, forum_id, sort, include_meta).
 * @param {Object} options Fetch options (e.g. signal for AbortController).
 * @returns {Promise} AJAX promise.
 */
export function getReplies( data, options ) {
	return ajaxFetch( 'bb_admin_get_replies', data || {}, options || {} );
}

/**
 * Get a single reply by ID.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {number} replyId Reply ID.
 * @param {Object} options Fetch options.
 * @returns {Promise} AJAX promise.
 */
export function getReply( replyId, options ) {
	return ajaxFetch( 'bb_admin_get_reply', { reply_id: replyId }, options || {} );
}

/**
 * Create a new reply.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {Object} data Reply data (content, forum_id, topic_id, reply_to, visibility).
 * @returns {Promise} AJAX promise.
 */
export function createReply( data ) {
	return ajaxFetch( 'bb_admin_create_reply', data );
}

/**
 * Save (update) an existing reply.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {Object} data    Reply data (reply_id, content, forum_id, topic_id, reply_to, visibility).
 * @param {Object} options Fetch options.
 * @returns {Promise} AJAX promise.
 */
export function saveReply( data, options ) {
	return ajaxFetch( 'bb_admin_save_reply', data, options || {} );
}

/**
 * Delete a reply.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {number} replyId Reply ID to delete.
 * @returns {Promise} AJAX promise.
 */
export function deleteReply( replyId ) {
	return ajaxFetch( 'bb_admin_delete_reply', { reply_id: replyId } );
}

/**
 * Perform bulk action on replies.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {Array}  replyIds  Array of reply IDs.
 * @param {string} action    Bulk action (e.g. 'delete', 'spam', 'edit').
 * @param {Object} extraData Optional extra POST data (e.g. edit_visibility).
 * @returns {Promise} AJAX promise.
 */
export function replyBulkAction( replyIds, action, extraData ) {
	var data = {
		reply_ids: replyIds.join( ',' ),
		do_action: action,
	};
	if ( extraData ) {
		Object.keys( extraData ).forEach( function ( key ) {
			data[ key ] = extraData[ key ];
		} );
	}
	return ajaxFetch( 'bb_admin_reply_bulk_action', data );
}

/**
 * Autocomplete endpoint for discussions (topics).
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {Object} data    Query params (term, page, selected_id, forum_id).
 * @param {Object} options Fetch options.
 * @returns {Promise} AJAX promise.
 */
export function discussionAutocomplete( data, options ) {
	return ajaxFetch( 'bb_admin_discussion_autocomplete', data || {}, options || {} );
}

/**
 * Autocomplete endpoint for replies.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {Object} data    Query params (term, page, selected_id, topic_id).
 * @param {Object} options Fetch options.
 * @returns {Promise} AJAX promise.
 */
export function replyAutocomplete( data, options ) {
	return ajaxFetch( 'bb_admin_reply_autocomplete', data || {}, options || {} );
}

/**
 * Get all profile field groups with their fields.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {Object} options - Optional fetch options (e.g. { signal }).
 * @return {Promise} Promise resolving to response.
 */
export function getProfileFieldGroups( options ) {
	return ajaxFetch( 'bb_admin_get_profile_field_groups', {}, options || {} );
}

/**
 * Create a new profile field group (field set).
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {Object} data - Field group data (name, description, group_is_repeater).
 * @return {Promise} Promise resolving to response.
 */
export function createFieldGroup( data ) {
	return ajaxFetch( 'bb_admin_create_field_group', data );
}

/**
 * Update an existing profile field group (field set).
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {Object} data - Field group data (group_id, name, description, group_is_repeater).
 * @return {Promise} Promise resolving to response.
 */
export function updateFieldGroup( data ) {
	return ajaxFetch( 'bb_admin_update_field_group', data );
}

/**
 * Delete a profile field group (field set).
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {number} groupId - Field group ID.
 * @return {Promise} Promise resolving to response.
 */
export function deleteFieldGroup( groupId ) {
	return ajaxFetch( 'bb_admin_delete_field_group', { group_id: groupId } );
}

/**
 * Create or update a profile field.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {Object} data - Field data.
 * @return {Promise} Promise resolving to response.
 */
export function saveProfileField( data ) {
	return ajaxFetch( 'bb_admin_save_profile_field', data );
}

/**
 * Delete a profile field.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {number} fieldId - Field ID.
 * @param {Object} options - Optional fetch options (e.g. { signal }).
 * @return {Promise} Promise resolving to response.
 */
export function deleteProfileField( fieldId, options ) {
	return ajaxFetch( 'bb_admin_delete_profile_field', { field_id: fieldId }, options );
}

/**
 * Reorder profile field groups and fields.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {Object} data    - Order data (group_order, field_order).
 * @param {Object} options - Optional fetch options (e.g. { signal }).
 * @return {Promise} Promise resolving to response.
 */
export function reorderProfileFields( data, options ) {
	return ajaxFetch( 'bb_admin_reorder_profile_fields', data, options );
}

/**
 * Get profile search form fields (saved + available).
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {Object} options - Optional fetch options (e.g. { signal }).
 * @return {Promise} Promise resolving to response.
 */
export function getProfileSearchFields( options ) {
	return ajaxFetch( 'bb_admin_get_profile_search_fields', {}, options || {} );
}

/**
 * Save (add or update) a profile search field.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {Object} data - Field data (field_code, field_label, field_desc, field_mode, field_index).
 * @return {Promise} Promise resolving to response.
 */
export function saveProfileSearchField( data ) {
	return ajaxFetch( 'bb_admin_save_profile_search_field', data );
}

/**
 * Delete a profile search field by index.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {Object} data    - Delete data (field_index).
 * @param {Object} options - Optional fetch options (e.g. { signal }).
 * @return {Promise} Promise resolving to response.
 */
export function deleteProfileSearchField( data, options ) {
	return ajaxFetch( 'bb_admin_delete_profile_search_field', data, options );
}

/**
 * Reorder profile search fields.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {Object} data    - Order data (field_order array of old indices in new order).
 * @param {Object} options - Optional fetch options (e.g. { signal }).
 * @return {Promise} Promise resolving to response.
 */
export function reorderProfileSearchFields( data, options ) {
	return ajaxFetch( 'bb_admin_reorder_profile_search_fields', data, options || {} );
}

/**
 * Get email templates listing with pagination, search, and sorting.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {Object} data    - Query parameters (page, per_page, search, sort, include_meta).
 * @param {Object} options - Optional fetch options (e.g. { signal } for AbortController).
 * @return {Promise} Promise resolving to response.
 */
export function getEmailTemplates( data, options ) {
	return ajaxFetch( 'bb_admin_get_email_templates', data, options );
}

/**
 * Perform bulk action on email templates.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {Array}  emailIds Array of email template IDs.
 * @param {string} action   Bulk action to perform (e.g. 'trash').
 * @return {Promise} Promise resolving to response.
 */
export function emailTemplateBulkAction( emailIds, action ) {
	return ajaxFetch( 'bb_admin_email_template_bulk_action', {
		email_ids: emailIds.join( ',' ),
		do_action: action,
	} );
}

/**
 * Fetch paginated email invites list.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {Object} data            Query parameters (page, per_page, search, sort, filter, include_meta).
 * @param {Object} options         Optional fetch options.
 * @param {AbortSignal} options.signal AbortController signal.
 * @return {Promise} Promise resolving to response.
 */
export function getInvites( data, options ) {
	return ajaxFetch( 'bb_admin_get_invites', data, options );
}

/**
 * Perform bulk action on email invites.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {Array}  inviteIds Array of invite post IDs.
 * @param {string} action    Bulk action to perform (e.g. 'revoke').
 * @return {Promise} Promise resolving to response.
 */
export function invitesBulkAction( inviteIds, action ) {
	return ajaxFetch( 'bb_admin_invites_bulk_action', {
		invite_ids: inviteIds.join( ',' ),
		do_action: action,
	} );
}

/**
 * Fetch a single email template for editing.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {Object} data            Query parameters (email_id).
 * @param {Object} options         Optional fetch options.
 * @return {Promise} Promise resolving to response.
 */
export function getEmailTemplate( data, options ) {
	return ajaxFetch( 'bb_admin_get_email_template', data, options );
}

/**
 * Create or update an email template.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {Object} data    Email template data (email_id, title, content, etc.).
 * @param {Object} options Optional fetch options.
 * @return {Promise} Promise resolving to response.
 */
export function saveEmailTemplate( data, options ) {
	return ajaxFetch( 'bb_admin_save_email_template', data, options );
}

/**
 * Permanently delete email templates.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {Array} emailIds Array of email template post IDs.
 * @return {Promise} Promise resolving to response.
 */
export function deleteEmailTemplates( emailIds ) {
	return ajaxFetch( 'bb_admin_delete_email_templates', {
		email_ids: emailIds.join( ',' ),
	} );
}

/**
 * Bulk edit email templates (status and/or situation).
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {Object} data Bulk edit data (email_ids, status, email_type).
 * @return {Promise} Promise resolving to response.
 */
export function bulkEditEmailTemplates( data ) {
	return ajaxFetch( 'bb_admin_bulk_edit_email_templates', data );
}

/**
 * Get all email situations grouped by category.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {Object} options Optional fetch options.
 * @return {Promise} Promise resolving to grouped situations.
 */
var emailSituationsCache = null;

export function getEmailSituations( options ) {
	if ( emailSituationsCache ) {
		return Promise.resolve( { success: true, data: emailSituationsCache } );
	}
	return ajaxFetch( 'bb_admin_get_email_situations', {}, options ).then( function ( response ) {
		if ( response.success && response.data ) {
			emailSituationsCache = response.data;
		}
		return response;
	} );
}

/**
 * Get distinct post meta keys for email template custom field autocomplete.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {Object} options Optional fetch options (e.g. { signal }).
 * @return {Promise} Promise resolving to array of meta key strings.
 */
export function getEmailMetaKeys( options ) {
	return ajaxFetch( 'bb_admin_get_email_meta_keys', {}, options );
}

/**
 * Get all reporting categories (bpm_category taxonomy terms).
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {Object} options Optional fetch options (e.g. { signal }).
 * @return {Promise} Promise resolving to { categories, show_when_options }.
 */
export function getReportingCategories( options ) {
	return ajaxFetch( 'bb_admin_get_reporting_categories', {}, options );
}

/**
 * Create a new reporting category.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {Object} data Category data (name, description, show_when_reporting).
 * @return {Promise} Promise resolving to response.
 */
export function createReportingCategory( data ) {
	return ajaxFetch( 'bb_admin_create_reporting_category', data );
}

/**
 * Update an existing reporting category.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {number} termId Term ID.
 * @param {Object} data   Category data (name, description, show_when_reporting).
 * @return {Promise} Promise resolving to response.
 */
export function updateReportingCategory( termId, data ) {
	return ajaxFetch( 'bb_admin_update_reporting_category', Object.assign( { term_id: termId }, data ) );
}

/**
 * Delete a reporting category.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {number} termId Term ID.
 * @return {Promise} Promise resolving to response.
 */
export function deleteReportingCategory( termId ) {
	return ajaxFetch( 'bb_admin_delete_reporting_category', { term_id: termId } );
}

// ── Flagged Members ───────────────────────────────────────────────────────

/**
 * Get flagged members list.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {Object} params       Query parameters.
 * @param {Object} [options={}] Fetch options (signal, etc.).
 * @return {Promise} Promise resolving to response.
 */
export function getFlaggedMembers( params, options ) {
	return ajaxFetch( 'bb_admin_get_flagged_members', params || {}, options );
}

/**
 * Get member report details (reporters + blockers).
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {number} userId User ID.
 * @return {Promise} Promise resolving to response.
 */
export function getMemberReport( userId, moderationId, options ) {
	return ajaxFetch( 'bb_admin_get_member_report', { user_id: userId, moderation_id: moderationId }, options || {} );
}

/**
 * Suspend a member.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {number} userId User ID.
 * @return {Promise} Promise resolving to response.
 */
export function suspendMember( userId ) {
	return ajaxFetch( 'bb_admin_suspend_member', { user_id: userId } );
}

/**
 * Unsuspend a member.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {number} userId User ID.
 * @return {Promise} Promise resolving to response.
 */
export function unsuspendMember( userId ) {
	return ajaxFetch( 'bb_admin_unsuspend_member', { user_id: userId } );
}

/**
 * Bulk action on flagged members.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {string} action  Bulk action (suspend, unsuspend).
 * @param {Array}  userIds Array of user IDs.
 * @return {Promise} Promise resolving to response.
 */
export function flaggedMembersBulkAction( action, userIds ) {
	return ajaxFetch( 'bb_admin_flagged_members_bulk_action', {
		bulk_action: action,
		user_ids: userIds,
	} );
}

// ── Reported Content ─────────────────────────────────────────────────────

/**
 * Get reported content list.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {Object} params       Query parameters.
 * @param {Object} [options={}] Fetch options (signal, etc.).
 * @return {Promise} Promise resolving to response.
 */
export function getReportedContent( params, options ) {
	return ajaxFetch( 'bb_admin_get_reported_content', params || {}, options );
}

/**
 * Get content report details (reporters).
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {number} moderationId Moderation record ID.
 * @return {Promise} Promise resolving to response.
 */
export function getContentReport( moderationId, options ) {
	return ajaxFetch( 'bb_admin_get_content_report', { moderation_id: moderationId }, options || {} );
}

/**
 * Hide reported content.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {number} itemId   Content item ID.
 * @param {string} itemType Content type slug.
 * @return {Promise} Promise resolving to response.
 */
export function hideContent( itemId, itemType ) {
	return ajaxFetch( 'bb_admin_hide_content', { item_id: itemId, item_type: itemType } );
}

/**
 * Unhide reported content.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {number} itemId   Content item ID.
 * @param {string} itemType Content type slug.
 * @return {Promise} Promise resolving to response.
 */
export function unhideContent( itemId, itemType ) {
	return ajaxFetch( 'bb_admin_unhide_content', { item_id: itemId, item_type: itemType } );
}

/**
 * Suspend the owner of reported content.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {number} userId User ID.
 * @return {Promise} Promise resolving to response.
 */
export function suspendContentOwner( userId ) {
	return ajaxFetch( 'bb_admin_suspend_content_owner', { user_id: userId } );
}

/**
 * Unsuspend the owner of reported content.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {number} userId User ID.
 * @return {Promise} Promise resolving to response.
 */
export function unsuspendContentOwner( userId ) {
	return ajaxFetch( 'bb_admin_unsuspend_content_owner', { user_id: userId } );
}

/**
 * Bulk action on reported content items.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {string} action Bulk action (hide, unhide).
 * @param {Array}  ids    Array of moderation IDs.
 * @return {Promise} Promise resolving to response.
 */
export function reportedContentBulkAction( action, ids ) {
	return ajaxFetch( 'bb_admin_reported_content_bulk_action', {
		bulk_action: action,
		ids: ids,
	} );
}
