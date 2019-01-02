<?php


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
		$this->id   = $id;

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
		add_action( 'bp_register_admin_integrations', array( $this, 'setup_admin_integartion_tab' ) );

		if ( $this->is_activated() ) {
			$this->activation();
			add_action( 'bp_include',                array( $this, 'includes'               ), 8 );
			add_action( 'bp_late_include',           array( $this, 'late_includes'          ) );
		}
	}

	public function is_activated() {
		if ( ! $this->required_plugin ) {
			return false;
		}

		$plugins = get_option( 'active_plugins' ) ?: [];
		if ( in_array( $this->required_plugin, $plugins ) ) {
			return true;
		}

		if ( ! is_multisite() ) {
			return false;
		}

		$plugins = get_site_option( 'active_sitewide_plugins' ) ?: [];
		return isset( $plugins[$this->required_plugin] );
	}

	public function activation() {
		// place to put default value
	}

	public function setup_admin_integartion_tab() {
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
				'bp-' . $this->id . '/bp-' . $this->id . '-' . $file  . '.php',
				'bp-' . $this->id . '-' . $file . '.php',
				'bp-' . $this->id . '/' . $file . '.php',

				// Passed with extension.
				$file,
				'bp-' . $this->id . '-' . $file,
				'bp-' . $this->id . '/' . $file,
			);

			foreach ( $paths as $path ) {
				if ( @is_file( $slashed_path . $path ) ) {
					require( $slashed_path . $path );
					break;
				}
			}
		}
	}

	public function late_includes() {}
}
