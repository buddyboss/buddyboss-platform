<?php
/**
 * BuddyBoss Moderation Loader.
 *
 * An Moderation component, for users, groups, and site moderation.
 *
 * @since   BuddyBoss 1.5.6
 * @package BuddyBoss\Moderation
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Set up the bp-Moderation component.
 *
 * @since BuddyBoss 1.5.6
 */
function bboss_setup_moderation() {
	bboss()->moderation = new BBoss_Moderation_Component();
}

add_action( 'bp_setup_components', 'bp_setup_moderation', 1 );

// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename names of this file's symbols (decision 0013): declared
// here, guarded on the renamed declaration, so each old name exists exactly when the new one does.
if ( function_exists( 'bboss_setup_moderation' ) && ! function_exists( 'bp_setup_moderation' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_setup_moderation()}.
	 *
	 * @deprecated Use bboss_setup_moderation() instead.
	 */
	function bp_setup_moderation() {
		return bboss_setup_moderation();
	}
}
// @bbwt-com-end compat-layer
