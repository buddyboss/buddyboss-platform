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
function bbp_setup_akismet() {

	// Bail if no akismet
	if ( ! defined( 'AKISMET_VERSION' ) ) {
		return;
	}

	// Bail if Akismet is turned off
	if ( ! bbp_is_akismet_active() ) {
		return;
	}

	// Include the Akismet Component
	require bbpress()->includes_dir . 'extend/akismet.php';

	// Instantiate Akismet for Forums
	bbpress()->extend->akismet = new BBP_Akismet();
}
