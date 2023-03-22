<?php
/**
 * BuddyBoss Performance Component Class.
 *
 * @package BuddyBoss\Performance\Loader
 * @since   BuddyBoss 1.6.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Creates Performance component.
 *
 * @since BuddyBoss 1.5.7
 */
#[\AllowDynamicProperties]
class BP_Performance_Component extends BP_Component {

	/**
	 * Start the performance component creation process.
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function __construct() {
		parent::start(
			'performance',
			__( 'API Caching', 'buddyboss' ),
			buddypress()->plugin_dir,
			array(
				'adminbar_myaccount_order' => 100,
			)
		);

	}

	/**
	 * Include Performance component files.
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @see   BP_Component::includes() for a description of arguments.
	 *
	 * @param array $includes See BP_Component::includes() for a description.
	 */
	public function includes( $includes = array() ) {
		$includes = array(
			'filters',
			'functions',
			'settings',
		);

		if ( is_admin() ) {
			$includes[] = 'admin';
		}

		$bb_performance = dirname( __FILE__ ) . '/class-performance.php';
		if ( file_exists( $bb_performance ) ) {
			require_once $bb_performance;
		}

		if ( class_exists( 'BuddyBoss\Performance\Performance' ) ) {
			$performance = \BuddyBoss\Performance\Performance::instance();
			if ( method_exists( $performance, 'validate' ) ) {
				$performance->validate();
			}
		}

		parent::includes( $includes );
	}

	/**
	 * Set up component global data.
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @see   BP_Component::setup_globals() for a description of arguments.
	 *
	 * @param array $args See BP_Component::setup_globals() for a description.
	 */
	public function setup_globals( $args = array() ) {

		// Define a slug, if necessary.
		if ( ! defined( 'BP_PERFORMANCE_SLUG' ) ) {
			define( 'BP_PERFORMANCE_SLUG', $this->id );
		}

		// All globals for performance component.
		// Note that global_tables is included in this array.
		parent::setup_globals(
			array(
				'slug'      => 'performance',
				'root_slug' => BP_PERFORMANCE_SLUG,
			)
		);
	}
}
