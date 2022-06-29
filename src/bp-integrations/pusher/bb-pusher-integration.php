<?php
/**
 * BuddyBoss Pusher Integration Class.
 *
 * @package BuddyBossPro/Integration
 * @since [BBVERSION]
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Setup the BB Pusher class.
 *
 * @since [BBVERSION]
 */
class BB_Pusher_Integration extends BP_Integration {

	/**
	 * BB_Pusher_Integration constructor.
	 */
	public function __construct() {
		$this->start(
			'pusher',
			__( 'Pusher', 'buddyboss-pro' ),
			'pusher',
			array(
				'required_plugin' => array(),
			)
		);

		// Include the code.
		$this->includes();
	}

	/**
	 * Setup actions for integration.
	 *
	 * @since [BBVERSION]
	 */
	public function setup_actions() {
		add_action( 'bp_admin_enqueue_scripts', array( $this, 'enqueue_scripts_styles' ) );

		parent::setup_actions();

		add_action( 'bp_rest_api_init', array( $this, 'rest_api_init' ), 10 );
	}

	/**
	 * Enqueue admin related scripts and styles.
	 *
	 * @since [BBVERSION]
	 */
	public function enqueue_scripts_styles() {

	}

	/**
	 * Register template path for BB.
	 *
	 * @since [BBVERSION]
	 * @return string template path
	 */
	public function register_template() {
		return bb_pusher_integration_path( '/templates' );
	}

	/**
	 * Includes.
	 *
	 * @param array $includes Array of file paths to include.
	 * @since [BBVERSION]
	 */
	public function includes( $includes = array() ) {
		$slashed_path = trailingslashit( buddypress()->integration_dir ) . $this->id . '/';

		$includes = array(
			'functions',
			'actions',
			'filters',
			'cache',
			'template',
		);

		// Loop through files to be included.
		foreach ( (array) $includes as $file ) {

			$paths = array(

				// Passed with no extension.
				'bb-' . $this->id . '/bb-' . $this->id . '-' . $file . '.php',
				'bb-' . $this->id . '-' . $file . '.php',
				'bb-' . $this->id . '/' . $file . '.php',

				// Passed with extension.
				$file,
				'bb-' . $this->id . '-' . $file,
				'bb-' . $this->id . '/' . $file,
			);

			foreach ( $paths as $path ) {
				if ( @is_file( $slashed_path . $path ) ) { // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
					require $slashed_path . $path;
					break;
				}
			}
		}
	}

	/**
	 * Register Pusher setting tab.
	 *
	 * @since [BBVERSION]
	 */
	public function setup_admin_integration_tab() {

		require_once trailingslashit( buddypress()->integration_dir ) . $this->id . '/bb-pusher-admin-tab.php';

		new BB_Pusher_Admin_Integration_Tab(
			"bb-{$this->id}",
			$this->name,
			array(
				'root_path'       => trailingslashit( buddypress()->integration_dir ) . $this->id,
				'root_url'        => trailingslashit( buddypress()->integration_url ) . $this->id,
				'required_plugin' => $this->required_plugin,
			)
		);
	}

	/**
	 * Init the BuddyBoss REST API.
	 *
	 * @param array $controllers Optional. See BP_Component::rest_api_init() for description.
	 *
	 * @since [BBVERSION]
	 */
	public function rest_api_init( $controllers = array() ) {
		if ( ! class_exists( 'BB_REST_Pusher_Endpoint' ) ) {

			$file_path = bb_pusher_integration_path( '/includes/class-bb-rest-pusher-endpoint.php' );
			if ( file_exists( $file_path ) ) {
				require_once $file_path;
			}

			parent::rest_api_init(
				array(
					'BB_REST_Pusher_Endpoint',
				)
			);
		} elseif ( class_exists( 'BB_REST_Pusher_Endpoint' ) ) {
			parent::rest_api_init(
				array(
					'BB_REST_Pusher_Endpoint',
				)
			);
		}
	}

}
