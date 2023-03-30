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
 * Defines the BuddyBoss Search Component.
 *
 * @since BuddyBoss 1.0.0
 */
#[\AllowDynamicProperties]
class BP_Search_Component extends BP_Component {
	/**
	 * Start the search component creation process
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function __construct() {
		parent::start(
			'search',
			__( 'Search', 'buddyboss' ),
			buddypress()->plugin_dir
		);
	}

	/**
	 * Setup globals
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function setup_globals( $args = array() ) {
		parent::setup_globals( $args );
	}
}
