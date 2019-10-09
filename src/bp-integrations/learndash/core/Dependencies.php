<?php
/**
 * BuddyBoss LearnDash integration Dependencies class.
 *
 * @package BuddyBoss\LearnDash
 * @since BuddyBoss 1.0.0
 */

namespace Buddyboss\LearndashIntegration\Core;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Class hendling plugin dependencies
 *
 * @since BuddyBoss 1.0.0
 */
class Dependencies {

	protected $dependencies       = array();
	protected $loadedDependencies = array();

	/**
	 * Constructor
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function __construct() {
		 $this->dependencies = array(
			 'bp_init'        => __( 'BuddyBoss Platform', 'buddyboss' ),
			 'learndash_init' => __( 'Learndash LMS', 'buddyboss' ),
		 );

		 $this->registerHooks();
	}

	/**
	 * Add hook to each dependencies' init hook
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function registerHooks() {
		foreach ( $this->dependencies as $hook => $__ ) {
			add_action( $hook, array( $this, 'dependencyLoaded' ) );
		}

		add_action( 'init', array( $this, 'appendDependencyChecker' ) );
	}

	/**
	 * Set a flag when dependency is init
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function dependencyLoaded() {
		$this->loadedDependencies[] = current_filter();
	}

	/**
	 * Check if the required dependencies are all init
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function appendDependencyChecker() {
		 global $wp_filter;

		$callbackKeys         = array_keys( $wp_filter['init']->callbacks );
		$lastCallbackPriority = $callbackKeys[ count( $callbackKeys ) - 1 ];

		add_action( 'init', array( $this, 'dependencyChecker' ), $lastCallbackPriority );
	}

	/**
	 * Run sub action based on depencency init status
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function dependencyChecker() {
		$success = count( $this->dependencies ) == count( $this->loadedDependencies );
		do_action( $success ? 'bp_ld_sync/depencencies_loaded' : 'bp_ld_sync/depencencies_failed', $this );
	}

	/**
	 * Check if any dependencies is missing
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function getMissingDepencencies() {
		return array_diff_key( $this->dependencies, array_flip( $this->loadedDependencies ) );
	}

	/**
	 * Get the dependencies that are loaded successfully
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function getLoadedDepencencies() {
		return array_intersect_key( $this->dependencies, array_flip( $this->loadedDependencies ) );
	}
}
