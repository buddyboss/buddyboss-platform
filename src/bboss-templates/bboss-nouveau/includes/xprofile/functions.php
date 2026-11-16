<?php
/**
 * xProfile functions
 *
 * @since BuddyPress 3.0.0
 * @version 3.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Register Scripts for the xProfile component
 *
 * @since BuddyPress 3.0.0
 *
 * @param array $scripts The array of scripts to register
 *
 * @return array The same array with the specific groups scripts.
 */
function bboss_nouveau_xprofile_register_scripts( $scripts = array() ) {
	if ( ! isset( $scripts['bp-nouveau'] ) ) {
		return $scripts;
	}

	return array_merge( $scripts, array(
		'bp-nouveau-xprofile' => array(
			'file'         => 'js/buddypress-xprofile%s.js',
			'dependencies' => array( 'bp-nouveau', 'jquery-ui-sortable' ),
			'footer'       => true,
		),
	) );
}

/**
 * Enqueue the xprofile scripts
 *
 * @since BuddyPress 3.0.0
 */
function bboss_nouveau_xprofile_enqueue_scripts() {
	if ( ! bboss_is_user_profile_edit() && ! bboss_is_register_page() ) {
		return;
	}
	wp_enqueue_script( 'bp-nouveau-xprofile' );
}

// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename names of this file's symbols (decision 0013): declared
// here, guarded on the renamed declaration, so each old name exists exactly when the new one does.
if ( function_exists( 'bboss_nouveau_xprofile_enqueue_scripts' ) && ! function_exists( 'bp_nouveau_xprofile_enqueue_scripts' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_nouveau_xprofile_enqueue_scripts()}.
	 *
	 * @deprecated Use bboss_nouveau_xprofile_enqueue_scripts() instead.
	 */
	function bp_nouveau_xprofile_enqueue_scripts() {
		return bboss_nouveau_xprofile_enqueue_scripts();
	}
}
if ( function_exists( 'bboss_nouveau_xprofile_register_scripts' ) && ! function_exists( 'bp_nouveau_xprofile_register_scripts' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_nouveau_xprofile_register_scripts()}.
	 *
	 * @deprecated Use bboss_nouveau_xprofile_register_scripts() instead.
	 */
	function bp_nouveau_xprofile_register_scripts( $scripts = array() ) {
		return bboss_nouveau_xprofile_register_scripts( $scripts );
	}
}
// @bbwt-com-end compat-layer
