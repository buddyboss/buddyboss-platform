<?php
/**
 * BuddyBoss LearnDash Integration Class.
 *
 * @package BuddyBoss\LearnDash
 * @since BuddyBoss 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Setup the bp learndash class.
 *
 * @since BuddyBoss 1.0.0
 */
class BP_Learndash_Integration extends BP_Integration {

	public function __construct() {
		$this->start(
			'learndash',
			__( 'LearnDash', 'buddyboss' ),
			'learndash',
			array(
				'required_plugin' => 'sfwd-lms/sfwd_lms.php',
			)
		);
	}

	/**
	 * Register admin setting tab
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function setup_admin_integration_tab() {
		require_once trailingslashit( $this->path ) . 'bp-admin-learndash-tab.php';

		new BP_Learndash_Admin_Integration_Tab(
			"bp-{$this->id}",
			$this->name,
			array(
				'root_path'       => $this->path,
				'root_url'        => $this->url,
				'required_plugin' => $this->required_plugin,
			)
		);
	}

	/**
	 * Load function files
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function includes( $includes = array() ) {
		parent::includes(
			array(
				'functions',
				'filters',
				'groups-sync/loader.php',
				'core/Core.php',
			)
		);
	}

	/**
	 * Setup cache.
	 *
	 * @since BuddyBoss 1.9.0
	 */
	public function setup_cache_groups() {
		// Global groups.
		wp_cache_add_global_groups(
			array(
				'ld_courses_progress',
			)
		);

		parent::setup_cache_groups();
	}

	/**
	 * Init the BuddyBoss REST API.
	 *
	 * @param array $controllers Optional. See BP_Component::rest_api_init() for description.
	 *
	 * @since BuddyBoss 1.3.5
	 */
	public function rest_api_init( $controllers = array() ) {
		if ( ! class_exists( 'BP_REST_Learndash_Courses_Endpoint' ) ) {

			$file_path = trailingslashit( $this->path ) . '/classes/class-bp-rest-learndash-courses-endpoint.php';
			if ( file_exists( $file_path ) ) {
				require_once $file_path;
			}

			parent::rest_api_init( array(
				'BP_REST_Learndash_Courses_Endpoint',
			) );
		}
	}
}
