<?php
/**
 * Deprecated functions.
 *
 * @deprecated BuddyBoss 1.2.3
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Get the display_name for member based on user_id
 *
 * @since BuddyBoss 1.0.0
 *
 * @param string $display_name
 * @param int    $user_id
 *
 * @return string
 */
function bboss_core_get_member_display_name( $display_name, $user_id = null ) {

	_deprecated_function( __FUNCTION__, '1.2.3', 'bboss_core_get_user_displayname' );

	// some cases it calls the filter directly, therefore no user id is passed
	if ( ! $user_id ) {
		return $display_name;
	}

	$old_display_name = $display_name;

	$display_name = bboss_xprofile_get_member_display_name( $user_id );

	if ( empty( $display_name ) ) {
		$display_name = $old_display_name;
	}

	return apply_filters( 'bp_core_get_member_display_name', trim( $display_name ), $user_id );
}

// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename names of this file's symbols (decision 0013): declared
// here, guarded on the renamed declaration, so each old name exists exactly when the new one does.
if ( function_exists( 'bboss_core_get_member_display_name' ) && ! function_exists( 'bp_core_get_member_display_name' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_core_get_member_display_name()}.
	 *
	 * @deprecated Use bboss_core_get_member_display_name() instead.
	 */
	function bp_core_get_member_display_name( $display_name, $user_id = null ) {
		return bboss_core_get_member_display_name( $display_name, $user_id );
	}
}
// @bbwt-com-end compat-layer
