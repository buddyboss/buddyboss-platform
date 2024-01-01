<?php
/**
 * Deprecated functions.
 *
 * @deprecated BuddyBoss [BBVERSION]
 */

/**
 * Get like count for activity
 *
 * @since BuddyBoss 1.0.0
 * @deprecated BuddyBoss [BBVERSION]
 *
 * @param int $activity_id The activity ID.
 *
 * @return int|string
 */
function bp_activity_get_favorite_users_string( $activity_id ) {
	_deprecated_function( __FUNCTION__, '2.5.10', 'bb_activity_reaction_names_and_count' );

	if ( ! bp_is_activity_like_active() ) {
		return 0;
	}

	return bb_activity_reaction_names_and_count( $activity_id, 'activity' );

}

/**
 * Get users for activity favorite tooltip
 *
 * @since BuddyBoss 1.0.0
 * @deprecated BuddyBoss [BBVERSION]
 *
 * @param int $activity_id The activity ID.
 *
 * @return string
 */
function bp_activity_get_favorite_users_tooltip_string( $activity_id ) {
	_deprecated_function( __FUNCTION__, '2.5.10', 'bb_activity_reaction_names_and_count' );

	if ( ! bp_is_activity_like_active() ) {
		return false;
	}

	return bb_activity_reaction_names_and_count( $activity_id );
}

/**
 * Delete users liked activity meta.
 *
 * @since BuddyBoss 1.2.5
 * @deprecated BuddyBoss [BBVERSION]
 *
 * @param int $user_id To delete user id.
 * @return bool True on success, false on failure.
 */
function bp_activity_remove_user_favorite_meta( $user_id = 0 ) {
	_deprecated_function( __FUNCTION__, '2.5.10', 'bb_remove_user_reactions' );

	if ( empty( $user_id ) ) {
		return false;
	}

	return bb_remove_user_reactions( $user_id );
}
