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
function bboss_setup_search() {
	bboss()->search = new BBoss_Search_Component();
}

add_action( 'bp_setup_components', 'bboss_setup_search', 6 );

require bboss()->plugin_dir . 'bboss-search/classes/class-bboss-search-core.php';
