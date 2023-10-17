<?php
/**
 * BuddyBoss TutorLMS integration Dependencies class.
 *
 * @package BuddyBoss\TutorLMS
 * @since BuddyBoss 1.0.0
 */

namespace Buddyboss\TutorLMSIntegration\Core;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Class hendling plugin dependencies
 *
 * @since BuddyBoss 1.0.0
 */
class Dependencies {

	protected $dependencies       = array();
	protected $is_tutorlms_active = false;

	/**
	 * Constructor
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function __construct() {
		 $this->dependencies = array(
			 'tutorlms_init' => __( 'TutorLMS LMS', 'buddyboss' ),
		 );

		 $this->appendDependencyChecker();
	}

	/**
	 * Check if the required dependencies are all init
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function appendDependencyChecker() {
		 global $wp_filter;

		$callbackKeys         = array_keys( $wp_filter['bp_init']->callbacks );
		$lastCallbackPriority = $callbackKeys[ count( $callbackKeys ) - 1 ];

		add_action( 'bp_init', array( $this, 'dependencyChecker' ), $lastCallbackPriority );
	}

	/**
	 * Run sub action based on depencency init status
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function dependencyChecker() {
		$this->is_tutorlms_active = function_exists( 'tutor' ) ? true : false;
		do_action( $this->is_tutorlms_active ? 'bb_tutorlms/depencencies_loaded' : 'bb_tutorlms/depencencies_failed', $this );
	}

	/**
	 * Check if any dependencies is missing
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function getMissingDepencencies() {
		if ( false === $this->is_tutorlms_active ) {
			return $this->dependencies;
		}

		return;
	}

	/**
	 * Get the dependencies that are loaded successfully
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function getLoadedDepencencies() {
		if ( true === $this->is_tutorlms_active ) {
			return $this->dependencies;
		}

		return;
	}
}
