<?php
/**
 * BuddyBoss Search Loader.
 *
 * The search component allow your users to search the entire network
 *
 * @package BuddyBoss
 * @subpackage Search
 * @since BuddyBoss 3.1.1
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Defines the BuddyBoss Search Component.
 *
 * @since BuddyBoss 3.1.1
 */
class BP_Search_Component extends BP_Component {
	/**
	 * Start the search component creation process
	 *
	 * @since BuddyBoss 3.1.1
	 */
	public function __construct() {
		parent::start(
			'search',
			__( 'Search', 'buddyboss' ),
			buddypress()->plugin_dir
		);
	}

	/**
	 * Include BuddyBoss classes and functions
	 */
	public function includes( $includes = array() ) {
		parent::includes( $includes );
	}

	/**
	 * Setup globals
	 *
	 * @since BuddyBoss 3.1.1
	 */
	public function setup_globals( $args = array() ) {
		parent::setup_globals( $args );
	}

}
