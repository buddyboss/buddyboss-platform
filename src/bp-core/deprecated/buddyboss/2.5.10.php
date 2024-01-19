<?php
/**
 * Deprecated functions.
 *
 * @deprecated BuddyBoss 2.5.20
 */

/**
 * Get like count for activity
 *
 * @since BuddyBoss 1.0.0
 * @deprecated BuddyBoss 2.5.20
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
 * @deprecated BuddyBoss 2.5.20
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
