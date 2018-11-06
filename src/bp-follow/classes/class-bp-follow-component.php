<?php
/**
 * BuddyBoss Follow Feeds Loader.
 *
 * The follow component is for users to create relationships with each other.
 *
 * @package BuddyBoss
 * @subpackage Follow
 * @since BuddyBoss 3.1.1
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Defines the BuddyBoss Follow Component.
 *
 * @since BuddyBoss 3.1.1
 */
class BP_Follow_Component extends BP_Component {

	/**
	 * Start the follow component creation process.
	 *
	 * @since BuddyBoss 3.1.1
	 */
	public function __construct() {
		parent::start(
			'follow',
			_x( 'User Followings', 'Follow screen page <title>', 'buddyboss' ),
			buddypress()->plugin_dir
		);
	}

	/**
	 * Include bp-friends files.
	 *
	 * @since BuddyBoss 3.1.1
	 *
	 * @see BP_Component::includes() for description of parameters.
	 *
	 * @param array $includes See {@link BP_Component::includes()}.
	 */
	public function includes( $includes = array() ) {
		$includes = array(
			'filters',
			'template',
			'functions',
		);

		parent::includes( $includes );
	}

	/**
	 * Late includes method.
	 *
	 * Only load up certain code when on specific pages.
	 *
	 * @since BuddyBoss 3.1.1
	 */
	public function late_includes() {
		// Bail if PHPUnit is running.
		if ( defined( 'BP_TESTS_DIR' ) ) {
			return;
		}
	}

	/**
	 * Set up bp-follow global settings.
	 *
	 * The BP_FOLLOW_SLUG constant is deprecated, and only used here for
	 * backwards compatibility.
	 *
	 * @since BuddyBoss 3.1.1
	 *
	 * @see BP_Component::setup_globals() for description of parameters.
	 *
	 * @param array $args See {@link BP_Component::setup_globals()}.
	 */
	public function setup_globals( $args = array() ) {
		$bp = buddypress();

		// Deprecated. Do not use.
		// Defined conditionally to support unit tests.
		if ( ! defined( 'BP_FOLLOW_DB_VERSION' ) ) {
			define( 'BP_FOLLOW_DB_VERSION', '1800' );
		}

		// Define a slug, if necessary.
		if ( ! defined( 'BP_FOLLOW_SLUG' ) ) {
			define( 'BP_FOLLOW_SLUG', $this->id );
		}

		// Global tables for the follow component.
		$global_tables = array(
			'table_name_follow' => $bp->table_prefix . 'bp_follow',
		);

		// All globals for the follow component.
		// Note that global_tables is included in this array.
		$args = array(
			'slug'                  => BP_FOLLOW_SLUG,
			'has_directory'         => false,
			'search_string'         => __( 'Search Followers...', 'buddyboss' ),
			'global_tables'         => $global_tables
		);

		parent::setup_globals( $args );
	}
}
