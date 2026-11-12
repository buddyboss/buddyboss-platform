<?php
/**
 * BuddyBoss Settings Loader.
 *
 * @package BuddyBoss\Settings\Loader
 * @since BuddyPress 1.5.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Creates our Settings component.
 *
 * @since BuddyPress 1.5.0
 */
#[\AllowDynamicProperties]
class BBoss_Settings_Component extends BBoss_Component {

	/**
	 * Start the settings component creation process.
	 *
	 * @since BuddyPress 1.5.0
	 */
	public function __construct() {
		parent::start(
			'settings',
			'Account',
			bboss()->plugin_dir,
			array(
				'adminbar_myaccount_order' => 21,
			)
		);
	}

	/**
	 * Include files.
	 *
	 * @since BuddyPress 1.5.0
	 *
	 * @param array $includes Array of values to include. Not used.
	 */
	public function includes( $includes = array() ) {
		parent::includes(
			array(
				'template',
				'functions',
			)
		);
	}

	/**
	 * Late includes method.
	 *
	 * Only load up certain code when on specific pages.
	 *
	 * @since BuddyPress 3.0.0
	 */
	public function late_includes() {
		// Bail if PHPUnit is running.
		if ( defined( 'BP_TESTS_DIR' ) ) {
			return;
		}

		// Bail if not on Settings component.
		if ( ! bboss_is_settings_component() ) {
			return;
		}

		$actions = array( 'notifications', 'capabilities', 'delete-account', 'export' );

		// Authenticated actions.
		if ( is_user_logged_in() ) {
			if ( ! bboss_current_action() || bboss_is_current_action( 'general' ) ) {
				require $this->path . 'bboss-settings/actions/general.php';

				// Specific to post requests.
			} elseif ( bboss_is_post_request() && in_array( bboss_current_action(), $actions, true ) ) {
				require $this->path . 'bboss-settings/actions/' . bboss_current_action() . '.php';
			}
		}

		// Screens - User profile integration.
		if ( bboss_is_user() ) {
			require $this->path . 'bboss-settings/screens/general.php';

			// Sub-nav items.
			if ( in_array( bboss_current_action(), $actions, true ) ) {
				require $this->path . 'bboss-settings/screens/' . bboss_current_action() . '.php';
			}
		}
	}

	/**
	 * Setup globals.
	 *
	 * The BP_SETTINGS_SLUG constant is deprecated, and only used here for
	 * backwards compatibility.
	 *
	 * @since BuddyPress 1.5.0
	 *
	 * @param array $args Array of arguments.
	 */
	public function setup_globals( $args = array() ) {

		// Define a slug, if necessary.
		if ( ! defined( 'BBOSS_SETTINGS_SLUG' ) ) {
			define( 'BBOSS_SETTINGS_SLUG', $this->id );
		}

		// All globals for settings component.
		parent::setup_globals(
			array(
				'slug'          => BBOSS_SETTINGS_SLUG,
				'has_directory' => false,
			)
		);
	}

	/**
	 * Set up navigation.
	 *
	 * @since BuddyPress 1.5.0
	 *
	 * @param array $main_nav Array of main nav items.
	 * @param array $sub_nav  Array of sub nav items.
	 */
	public function setup_nav( $main_nav = array(), $sub_nav = array() ) {

		// Determine user to use.
		if ( bboss_displayed_user_domain() ) {
			$user_domain = bboss_displayed_user_domain();
		} elseif ( bboss_loggedin_user_domain() ) {
			$user_domain = bboss_loggedin_user_domain();
		} else {
			return;
		}

		$access        = bboss_core_can_edit_settings();
		$slug          = bboss_get_settings_slug();
		$settings_link = trailingslashit( $user_domain . $slug );

		// Add the settings navigation item.
		$main_nav = array(
			'name'                    => __( 'Account', 'buddyboss-platform' ),
			'slug'                    => $slug,
			'position'                => 21,
			'show_for_displayed_user' => $access,
			'screen_function'         => 'bboss_settings_screen_general',
			'default_subnav_slug'     => 'general',
		);

		// Add General Settings nav item.
		$sub_nav[] = array(
			'name'            => __( 'Login Information', 'buddyboss-platform' ),
			'slug'            => 'general',
			'parent_url'      => $settings_link,
			'parent_slug'     => $slug,
			'screen_function' => 'bboss_settings_screen_general',
			'position'        => 10,
			'user_has_access' => $access,
		);

		$data = bboss_core_notification_preferences_data();
		// Add Email nav item. Formerly called 'Notifications', we
		// retain the old slug and function names for backward compat.
		$sub_nav[] = array(
			'name'            => $data['menu_title'],
			'slug'            => 'notifications',
			'parent_url'      => $settings_link,
			'parent_slug'     => $slug,
			'screen_function' => 'bboss_settings_screen_notification',
			'position'        => 20,
			'user_has_access' => $access,
			'item_css_class'  => $data['item_css_class'],
		);

		if ( ! empty( bboss_get_subscriptions_types() ) ) {
			// Common params to all nav items.
			$default_params_notifications = array(
				'parent_slug'     => $slug . '_notifications',
				'screen_function' => 'bboss_settings_screen_notification',
				'user_has_access' => $access,
			);

			$sub_nav[] = array_merge(
				array(
					'name'       => __( 'Preferences', 'buddyboss-platform' ),
					'parent_url' => $settings_link,
					'slug'       => 'notifications',
					'position'   => 1,
				),
				$default_params_notifications
			);

			$sub_nav[] = array_merge(
				array(
					'name'       => __( 'Subscriptions', 'buddyboss-platform' ),
					'parent_url' => trailingslashit( $settings_link . 'notifications' ),
					'slug'       => 'subscriptions',
					'position'   => 2,
				),
				$default_params_notifications
			);
		}

		$sub_nav[] = array(
			'name'            => __( 'Export Data', 'buddyboss-platform' ),
			'slug'            => 'export',
			'parent_url'      => $settings_link,
			'parent_slug'     => $slug,
			'screen_function' => 'bboss_settings_screen_export_data',
			'position'        => 80,
			'user_has_access' => $access,
		);

		if( bboss_is_active( 'moderation' ) && bboss_is_moderation_member_blocking_enable() ){
			$sub_nav[] = array(
				'name'            => __( 'Blocked Members', 'buddyboss-platform' ),
				'slug'            => 'blocked-members',
				'parent_url'      => $settings_link,
				'parent_slug'     => $slug,
				'screen_function' => 'bboss_moderation_screen',
				'position'        => 65,
				'user_has_access' => $access,
			);
		}

		// Add Spam Account nav item.
		if ( bboss_current_user_can( 'bp_moderate' ) ) {
			$sub_nav[] = array(
				'name'            => __( 'Capabilities', 'buddyboss-platform' ),
				'slug'            => 'capabilities',
				'parent_url'      => $settings_link,
				'parent_slug'     => $slug,
				'screen_function' => 'bboss_settings_screen_capabilities',
				'position'        => 80,
				'user_has_access' => ! bboss_is_my_profile(),
			);
		}

		// Add Delete Account nav item.
		if ( ( ! bboss_disable_account_deletion() && bboss_is_my_profile() ) || bboss_current_user_can( 'delete_users' ) ) {
			$sub_nav[] = array(
				'name'            => __( 'Delete Account', 'buddyboss-platform' ),
				'slug'            => 'delete-account',
				'parent_url'      => $settings_link,
				'parent_slug'     => $slug,
				'screen_function' => 'bboss_settings_screen_delete_account',
				'position'        => 90,
				'user_has_access' => ! is_super_admin( bboss_displayed_user_id() ),
			);
		}

		parent::setup_nav( $main_nav, $sub_nav );
	}

	/**
	 * Set up the Toolbar.
	 *
	 * @since BuddyPress 1.5.0
	 *
	 * @param array $wp_admin_nav Array of Admin Bar items.
	 */
	public function setup_admin_bar( $wp_admin_nav = array() ) {

		// Menus for logged in user.
		if ( is_user_logged_in() ) {

			// Setup the logged in user variables.
			$settings_link = trailingslashit( bboss_loggedin_user_domain() . bboss_get_settings_slug() );

			// Add main Settings menu.
			$wp_admin_nav[] = array(
				'parent' => bboss()->my_account_menu_id,
				'id'     => 'my-account-' . $this->id,
				'title'  => __( 'Account', 'buddyboss-platform' ),
				'href'   => $settings_link,
			);

			// General Account.
			$wp_admin_nav[] = array(
				'parent'   => 'my-account-' . $this->id,
				'id'       => 'my-account-' . $this->id . '-general',
				'title'    => __( 'Login Information', 'buddyboss-platform' ),
				'href'     => $settings_link,
				'position' => 10,
			);

			// Notifications - only add the tab when there is something to display there.
			if ( has_action( 'bp_notification_settings' ) ) {
				$data           = bboss_core_notification_preferences_data();
				$wp_admin_nav[] = array(
					'parent'   => 'my-account-' . $this->id,
					'id'       => 'my-account-' . $this->id . '-notifications',
					'title'    => $data['menu_title'],
					'href'     => trailingslashit( $settings_link . 'notifications' ),
					'position' => 20,
				);
			}

			$wp_admin_nav[] = array(
				'parent'   => 'my-account-' . $this->id,
				'id'       => 'my-account-' . $this->id . '-export',
				'title'    => __( 'Export Data', 'buddyboss-platform' ),
				'href'     => trailingslashit( $settings_link . 'export/' ),
				'position' => 50,
			);

			// Delete Account
			if ( ! bboss_current_user_can( 'bp_moderate' ) && ! bboss_core_get_root_option( 'bp-disable-account-deletion' ) ) {
				$wp_admin_nav[] = array(
					'parent'   => 'my-account-' . $this->id,
					'id'       => 'my-account-' . $this->id . '-delete-account',
					'title'    => __( 'Delete Account', 'buddyboss-platform' ),
					'href'     => trailingslashit( $settings_link . 'delete-account' ),
					'position' => 90,
				);
			}

			if ( bboss_is_active( 'moderation' ) && bboss_is_moderation_member_blocking_enable() ) {
				// Blocked Members.
				$wp_admin_nav[] = array(
					'parent'   => 'my-account-' . $this->id,
					'id'       => 'my-account-' . $this->id . '-blocked-members',
					'title'    => __( 'Blocked Members', 'buddyboss-platform' ),
					'href'     => trailingslashit( $settings_link . 'blocked-members/' ),
					'position' => 31,
				);
			}
		}

		parent::setup_admin_bar( $wp_admin_nav );
	}

	/**
	 * Init the BuddyBoss REST API.
	 *
	 * @param array $controllers Optional. See BBoss_Component::rest_api_init() for description.
	 *
	 * @since BuddyBoss 1.3.5
	 */
	public function rest_api_init( $controllers = array() ) {
		parent::rest_api_init( array(
			'BBoss_REST_Account_Settings_Endpoint',
			'BBoss_REST_Account_Settings_Options_Endpoint',
		) );
	}

	/**
	 * Register the Settings Blocks.
	 *
	 * @since BuddyBoss 2.9.00
	 *
	 * @param array $blocks Optional. See BBoss_Component::blocks_init() for description.
	 */
	public function blocks_init( $blocks = array() ) {
		parent::blocks_init( array() );
	}
}
