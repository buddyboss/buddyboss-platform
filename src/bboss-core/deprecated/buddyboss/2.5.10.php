<?php
/**
 * Deprecated functions.
 *
 * @deprecated BuddyBoss 2.5.20
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

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
function bboss_activity_get_favorite_users_string( $activity_id ) {
	_deprecated_function( __FUNCTION__, '2.5.10', 'bboss_activity_reaction_names_and_count' );

	if ( ! bboss_is_activity_like_active() ) {
		return 0;
	}

	return bboss_activity_reaction_names_and_count( $activity_id, 'activity' );

}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_activity_get_favorite_users_string' ) && ! function_exists( 'bp_activity_get_favorite_users_string' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_activity_get_favorite_users_string()}.
	 *
	 * @deprecated Use bboss_activity_get_favorite_users_string() instead.
	 */
	function bp_activity_get_favorite_users_string( $activity_id ) {
		return bboss_activity_get_favorite_users_string( $activity_id );
	}
}
// @bbwt-com-end compat-layer


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
function bboss_activity_get_favorite_users_tooltip_string( $activity_id ) {
	_deprecated_function( __FUNCTION__, '2.5.10', 'bboss_activity_reaction_names_and_count' );

	if ( ! bboss_is_activity_like_active() ) {
		return false;
	}

	return bboss_activity_reaction_names_and_count( $activity_id );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_activity_get_favorite_users_tooltip_string' ) && ! function_exists( 'bp_activity_get_favorite_users_tooltip_string' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_activity_get_favorite_users_tooltip_string()}.
	 *
	 * @deprecated Use bboss_activity_get_favorite_users_tooltip_string() instead.
	 */
	function bp_activity_get_favorite_users_tooltip_string( $activity_id ) {
		return bboss_activity_get_favorite_users_tooltip_string( $activity_id );
	}
}
// @bbwt-com-end compat-layer

