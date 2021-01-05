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
 * @since BuddyBoss 1.6.0
 */
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

	/**
	 * Set up the component entries in the WordPress Admin Bar.
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @see   BP_Component::setup_nav() for a description of the $wp_admin_nav
	 *      parameter array.
	 *
	 * @param array $wp_admin_nav See BP_Component::setup_admin_bar() for a description.
	 */
	public function setup_admin_bar( $wp_admin_nav = array() ) {
		// Menus for logged in user.
		if ( is_user_logged_in() && bp_is_profile_media_support_enabled() ) {

			// Setup the logged in user variables.
			$media_link = trailingslashit( bp_loggedin_user_domain() . bp_get_media_slug() );

			// Add main Messages menu.
			$wp_admin_nav[] = array(
				'parent' => buddypress()->my_account_menu_id,
				'id'     => 'my-account-' . $this->id,
				'title'  => __( 'Photos', 'buddyboss' ),
				'href'   => $media_link,
			);

			// Media.
			$wp_admin_nav[] = array(
				'parent'   => 'my-account-' . $this->id,
				'id'       => 'my-account-' . $this->id . '-my-media',
				'title'    => __( 'My Photos', 'buddyboss' ),
				'href'     => $media_link,
				'position' => 10,
			);

			if ( bp_is_profile_albums_support_enabled() ) {
				// Albums.
				$wp_admin_nav[] = array(
					'parent'   => 'my-account-' . $this->id,
					'id'       => 'my-account-' . $this->id . '-albums',
					'title'    => __( 'My Albums', 'buddyboss' ),
					'href'     => trailingslashit( $media_link . 'albums' ),
					'position' => 20,
				);
			}
		}

		parent::setup_admin_bar( $wp_admin_nav );
	}
}
