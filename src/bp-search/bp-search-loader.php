<?php
/**
 * BuddyBoss Search Loader.
 *
 * The search component allow your users to search the entire network
 *
 * @package BuddyBoss\Search
 * @since BuddyBoss 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Set up the bp-search component.
 *
 * @since BuddyBoss 1.0.0
 */
function bp_setup_search() {
	buddypress()->search = new BP_Search_Component();
}

add_action( 'bp_setup_components', 'bp_setup_search', 6 );

require buddypress()->plugin_dir . 'bp-search/classes/class-bp-search-core.php';
