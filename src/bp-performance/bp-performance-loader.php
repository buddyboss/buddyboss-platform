<?php
/**
 * BuddyBoss Performance Loader.
 *
 * A performance component, Allow to cache BuddyBoss Platform REST API.
 *
 * @package BuddyBoss\Performance\Loader
 * @since BuddyBoss 1.5.7
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Set up the bp-performance component.
 *
 * @since BuddyBoss 1.5.7
 */
function bp_setup_performance() {
	buddypress()->performance = new BP_Performance_Component();
}
add_action( 'bp_setup_components', 'bp_setup_performance', 1 );
