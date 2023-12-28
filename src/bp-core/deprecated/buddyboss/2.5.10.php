<?php
/**
 * Deprecated functions.
 *
 * @deprecated BuddyBoss [BBVERSION]
 */

/**
 * Retrieve the number of favorite activity feed items a user has.
 *
 * @since BuddyPress 1.2.0
 * @deprecated BuddyBoss [BBVERSION]
 *
 * @param int $user_id ID of the user whose favorite count is being requested.
 * @return int Total favorite count for the user.
 */
function bp_activity_total_favorites_for_user( $user_id = 0 ) {
	_deprecated_function( __FUNCTION__, '2.5.10' );

	// Fallback on displayed user, and then logged in user.
	if ( empty( $user_id ) ) {
		$user_id = ( bp_displayed_user_id() ) ? bp_displayed_user_id() : bp_loggedin_user_id();
	}

	return bb_activity_total_reactions_count_for_user( $user_id, 'activity' );
}

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
	_deprecated_function( __FUNCTION__, '2.5.10' );

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
	_deprecated_function( __FUNCTION__, '2.5.10' );

	if ( ! bp_is_activity_like_active() ) {
		return false;
	}

	$current_user_id = get_current_user_id();
	$favorited_users = bp_activity_get_meta( $activity_id, 'bp_favorite_users', true );

	if ( ! empty( $favorited_users ) ) {
		$like_text       = bp_activity_get_favorite_users_string( $activity_id );
		$favorited_users = array_reduce(
			$favorited_users,
			function ( $carry, $user_id ) use ( $current_user_id, $like_text ) {
				if ( $user_id != $current_user_id ) {
					$user_display_name = bp_core_get_user_displayname( $user_id );
					if ( strpos( $like_text, $user_display_name ) === false ) {
						$carry .= $user_display_name . ',&#10;';
					}
				}

				return $carry;
			}
		);
	}

	return ! empty( $favorited_users ) ? trim( $favorited_users, ',&#10;' ) : '';
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
	_deprecated_function( __FUNCTION__, '2.5.10' );

	if ( empty( $user_id ) ) {
		return false;
	}

	return bb_remove_user_reactions( $user_id );
}
