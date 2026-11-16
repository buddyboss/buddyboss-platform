<?php
/**
 * Deprecated functions.
 *
 * @deprecated BuddyPress 2.4.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * bboss_group_avatar_edit_form()
 *
 * @deprecated BuddyPress 2.4.0
 */
function bboss_group_avatar_edit_form() {
	_deprecated_function( __FUNCTION__, '2.4' );
}

/**
 * bboss_groups_avatar_upload()
 *
 * @deprecated BuddyPress 2.4.0
 *
 * @param bool $deprecated Param deprecated 1.1.0
 */
function bboss_groups_avatar_upload( $deprecated = true ) {
	_deprecated_function( __FUNCTION__, '2.4' );
}

// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename names of this file's symbols (decision 0013): declared
// here, guarded on the renamed declaration, so each old name exists exactly when the new one does.
if ( function_exists( 'bboss_group_avatar_edit_form' ) && ! function_exists( 'bp_group_avatar_edit_form' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_group_avatar_edit_form()}.
	 *
	 * @deprecated Use bboss_group_avatar_edit_form() instead.
	 */
	function bp_group_avatar_edit_form() {
		return bboss_group_avatar_edit_form();
	}
}
if ( function_exists( 'bboss_groups_avatar_upload' ) && ! function_exists( 'groups_avatar_upload' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_groups_avatar_upload()}.
	 *
	 * @deprecated Use bboss_groups_avatar_upload() instead.
	 */
	function groups_avatar_upload( $deprecated = true ) {
		return bboss_groups_avatar_upload( $deprecated );
	}
}
// @bbwt-com-end compat-layer
