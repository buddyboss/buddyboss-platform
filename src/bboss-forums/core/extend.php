<?php

/**
 * Forums Extentions
 *
 * There's a world of really cool plugins out there, and Forums comes with
 * support for some of the most popular ones.
 *
 * @package BuddyBoss\Extend
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Loads Akismet inside the Forums global class
 *
 * @since bbPress (r3277)
 *
 * @return If Forums is not active
 */
function bboss_bbp_setup_akismet() {

	// Bail if no akismet
	if ( ! defined( 'AKISMET_VERSION' ) ) {
		return;
	}

	// Bail if Akismet is turned off
	if ( ! bboss_bbp_is_akismet_active() ) {
		return;
	}

	// Include the Akismet Component
	require bboss_forums()->includes_dir . 'extend/akismet.php';

	// Instantiate Akismet for Forums
	bboss_forums()->extend->akismet = new BBoss_BBP_Akismet();
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_bbp_setup_akismet' ) && ! function_exists( 'bbp_setup_akismet' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_bbp_setup_akismet()}.
	 *
	 * @deprecated Use bboss_bbp_setup_akismet() instead.
	 */
	function bbp_setup_akismet() {
		return bboss_bbp_setup_akismet();
	}
}
// @bbwt-com-end compat-layer

