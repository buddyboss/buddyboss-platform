<?php

/**
 * Deprecated functions.
 *
 * @deprecated BuddyBoss 2.3.41
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Setup the user profile hash to the user meta.
 *
 * @since BuddyBoss 2.3.1
 *
 * @param array $user_ids User IDs.
 */
function bboss_set_bluk_user_profile_slug( $user_ids ) {
	_deprecated_function( __FUNCTION__, '2.3.41', 'bboss_set_bulk_user_profile_slug' );
	bboss_set_bulk_user_profile_slug( $user_ids );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_set_bluk_user_profile_slug' ) && ! function_exists( 'bb_set_bluk_user_profile_slug' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_set_bluk_user_profile_slug()}.
	 *
	 * @deprecated Use bboss_set_bluk_user_profile_slug() instead.
	 */
	function bb_set_bluk_user_profile_slug( $user_ids ) {
		return bboss_set_bluk_user_profile_slug( $user_ids );
	}
}
// @bbwt-com-end compat-layer

