<?php
/**
 * Main Integration Class.
 *
 * @package BuddyBoss\Core
 * @since BuddyBoss 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * API to create BuddyBoss Integration.
 *
 * @since BuddyBoss 1.0.0
 */
#[\AllowDynamicProperties]
class BP_Integration {

	/**
	 * Translatable name for the integration.
	 *
	 * @internal
	 */
	public $name = '';

	/**
	 * Unique ID for the integration.
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public $id = '';

	/**
	 * Unique slug for the integration, for use in query strings and URLs.
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public $slug = '';

	/**
	 * The path to the integration's files.
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public $path = '';

	public function start( $id = '', $name = '', $path = '', $params = array() ) {

		// Internal identifier of integration.
		$this->id = $id;

		// Internal integration name.
		$this->name = $name;

		// Path for includes.
		$this->path = trailingslashit( buddypress()->integration_dir ) . $path;
		$this->url  = trailingslashit( buddypress()->integration_url ) . $path;

		if ( $params ) {
			if ( isset( $params['required_plugin'] ) ) {
				$this->required_plugin = $params['required_plugin'];
			}
		}

		$this->setup_actions();
	}

	public function setup_actions() {
		add_action( 'bp_register_admin_integrations', array( $this, 'setup_admin_integration_tab' ) );

		if ( $this->is_activated() ) {
			$this->activation();
			add_action( 'bp_include', array( $this, 'includes' ), 8 );
			add_action( 'bp_late_include', array( $this, 'late_includes' ) );

			// Register BP REST Endpoints.
			if ( bp_rest_in_buddypress() && bp_rest_api_is_available() ) {
				add_action( 'bp_rest_api_init', array( $this, 'rest_api_init' ), 10 );
			}
		}


	}

	public function is_activated() {
		if ( ! $this->required_plugin ) {
			return false;
		}

		$plugins = get_option( 'active_plugins' ) ?: array();
		if ( in_array( $this->required_plugin, $plugins ) ) {
			return true;
		}

		if ( ! is_multisite() ) {
			return false;
		}

		$plugins = get_site_option( 'active_sitewide_plugins' ) ?: array();
		return isset( $plugins[ $this->required_plugin ] );
	}

	public function activation() {
		// place to put default value
	}

	public function setup_admin_integration_tab() {
		if ( $this->admin_tab ) {
			require_once trailingslashit( $this->path ) . $this->admin_tab;
		}
	}

	public function includes( $includes = array() ) {
		if ( empty( $includes ) ) {
			return;
		}

		$slashed_path = trailingslashit( $this->path );

		// Loop through files to be included.
		foreach ( (array) $includes as $file ) {

			$paths = array(

				// Passed with no extension.
				'bp-' . $this->id . '/bp-' . $this->id . '-' . $file . '.php',
				'bp-' . $this->id . '-' . $file . '.php',
				'bp-' . $this->id . '/' . $file . '.php',

				// Passed with extension.
				$file,
				'bp-' . $this->id . '-' . $file,
				'bp-' . $this->id . '/' . $file,
			);

			foreach ( $paths as $path ) {
				if ( @is_file( $slashed_path . $path ) ) {
					require $slashed_path . $path;
					break;
				}
			}
		}
	}

	public function late_includes() {}

	/**
	 * Init the BuddyBoss REST API.
	 *
	 * @since BuddyBoss 1.3.5
	 *
	 * @param array $controllers The list of BP REST controllers to load.
	 */
	public function rest_api_init( $controllers = array() ) {
		if ( is_array( $controllers ) && $controllers ) {
			// Built-in controllers.
			$_controllers = $controllers;

			/**
			 * Use this filter to disable all or some REST API controllers
			 * for the component.
			 *
			 * This is a dynamic hook that is based on the component string ID.
			 *
			 * @since BuddyBoss 1.3.5
			 *
			 * @param array $controllers The list of BuddyBoss REST API controllers to load.
			 */
			$controllers = (array) apply_filters( 'bp_' . $this->id . '_rest_api_controllers', $controllers );

			foreach( $controllers as $controller ) {
				if ( ! in_array( $controller, $_controllers, true ) || ! class_exists( $controller ) ) {
					continue;
				}

				$component_controller = new $controller;
				$component_controller->register_routes();
			}
		}

		/**
		 * Fires in the rest_api_init method inside BP_Component.
		 *
		 * This is a dynamic hook that is based on the component string ID.
		 *
		 * @since BuddyBoss 1.3.5
		 */
		do_action( 'bp_' . $this->id . '_rest_api_init' );
	}
}
