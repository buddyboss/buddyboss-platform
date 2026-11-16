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

add_action( 'bp_setup_components', 'bp_setup_search', 6 );

require bboss()->plugin_dir . 'bboss-search/classes/class-bboss-search-core.php';

// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename names of this file's symbols (decision 0013): declared
// here, guarded on the renamed declaration, so each old name exists exactly when the new one does.
if ( function_exists( 'bboss_setup_search' ) && ! function_exists( 'bp_setup_search' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_setup_search()}.
	 *
	 * @deprecated Use bboss_setup_search() instead.
	 */
	function bp_setup_search() {
		return bboss_setup_search();
	}
}
// @bbwt-com-end compat-layer
