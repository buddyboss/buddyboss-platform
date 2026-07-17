<?php
/**
 * BuddyBoss Core Loader.
 *
 * Core contains the commonly used functions, classes, and APIs.
 *
 * @package BuddyBoss\Core
 * @since BuddyPress 1.5.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Creates the Core component.
 *
 * @since BuddyPress 1.5.0
 */
class BP_Core extends BP_Component {

	/**
	 * Start the members component creation process.
	 *
	 * @since BuddyPress 1.5.0
	 */
	public function __construct() {
		parent::start(
			'core',
			'BuddyBoss Core',
			buddypress()->plugin_dir
		);

		$this->bootstrap();
	}

	/**
	 * Populate the global data needed before BuddyPress can continue.
	 *
	 * This involves figuring out the currently required, activated, deactivated,
	 * and optional components.
	 *
	 * @since BuddyPress 1.5.0
	 */
	private function bootstrap() {
		/**
		 * Fires before the loading of individual components and after BuddyBoss Core.
		 *
		 * Allows plugins to run code ahead of the other components.
		 *
		 * @since BuddyPress 1.2.0
		 */
		do_action( 'bp_core_loaded' );

		$this->load_components();
		$this->load_integrations();
	}

	/**
	 * Load components files
	 *
	 * @since BuddyBoss 1.0.0
	 */
	private function load_components() {
		$bp = buddypress();

		/**
		 * Filters the included and optional components.
		 *
		 * @since BuddyPress 1.5.0
		 *
		 * @param array $value Array of included and optional components.
		 */
		$bp->optional_components = apply_filters(
			'bp_optional_components',
			array(
				'settings',
				'notifications',
				'groups',
				'forums',
				'activity',
				'media',
				'document',
				'video',
				'messages',
				'friends',
				'invites',
				'moderation',
				'search',
				'blogs',
			)
		);

		/**
		 * Filters the required components.
		 *
		 * @since BuddyPress 1.5.0
		 *
		 * @param array $value Array of required components.
		 */
		$bp->required_components = apply_filters( 'bp_required_components', array( 'members', 'xprofile' ) );

		// Get a list of activated components.
		if ( $active_components = bp_get_option( 'bp-active-components' ) ) {

			/**
			 * Filters the list of active components.
			 *
			 * @since BuddyPress 1.5.0
			 *
			 * @param array $value Array of active components.
			 */
			$bp->active_components = apply_filters( 'bp_active_components', $active_components );

			/**
			 * Filters the deactivated components.
			 *
			 * @since BuddyPress 1.0.0
			 *
			 * @param array $value Array of deactivated components.
			 */
			$bp->deactivated_components = apply_filters( 'bp_deactivated_components', array_values( array_diff( array_values( array_merge( $bp->optional_components, $bp->required_components ) ), array_keys( $bp->active_components ) ) ) );

			// Pre 1.5 Backwards compatibility.
		} elseif ( $deactivated_components = bp_get_option( 'bp-deactivated-components' ) ) {

			// Trim off namespace and filename.
			foreach ( array_keys( (array) $deactivated_components ) as $component ) {
				$trimmed[] = str_replace( '.php', '', str_replace( 'bp-', '', $component ) );
			}

			/** This filter is documented in bp-core/bp-core-loader.php */
			$bp->deactivated_components = apply_filters( 'bp_deactivated_components', $trimmed );

			// Setup the active components.
			$active_components = array_fill_keys( array_diff( array_values( array_merge( $bp->optional_components, $bp->required_components ) ), array_values( $bp->deactivated_components ) ), '1' );

			/** This filter is documented in bp-core/classes/class-bp-core.php */
			$bp->active_components = apply_filters( 'bp_active_components', $bp->active_components );

			// Default to all components active.
		} else {

			// Set globals.
			$bp->deactivated_components = array();

			// Setup the active components.
			$active_components = array_fill_keys( array_values( array_merge( $bp->optional_components, $bp->required_components ) ), '1' );

			/** This filter is documented in bp-core/classes/class-bp-core.php */
			$bp->active_components = apply_filters( 'bp_active_components', $bp->active_components );
		}

		/*
		 * Drop active components whose directories are not present in this build
		 * (e.g. premium-only components stripped from the free package), so that
		 * bp_is_active() reflects what is actually loadable. The stored option is
		 * scrubbed too, so code reading `bp-active-components` directly (performance
		 * must-use integrations, feature registry migration fallback) stays in sync.
		 */
		$unavailable_components = array();
		foreach ( array_keys( (array) $bp->active_components ) as $component ) {
			if ( ! in_array( $component, $bp->optional_components, true ) ) {
				continue;
			}

			$directory_available = file_exists( $bp->plugin_dir . 'bp-' . $component . '/bp-' . $component . '-loader.php' );

			/** This filter is documented in bp-core/bp-core-functions.php */
			$directory_available = (bool) apply_filters( 'bb_component_directory_available', $directory_available, $component );

			if ( ! $directory_available ) {
				$unavailable_components[] = $component;
				unset( $bp->active_components[ $component ] );
			}
		}

		if ( ! empty( $unavailable_components ) ) {
			$bp->deactivated_components = array_unique( array_merge( (array) $bp->deactivated_components, $unavailable_components ) );

			// Scrub only the unavailable keys from the stored option, leaving
			// runtime-filtered values out of the database write.
			$stored_components   = (array) bp_get_option( 'bp-active-components', array() );
			$scrubbed_components = array_diff_key( $stored_components, array_flip( $unavailable_components ) );
			if ( $scrubbed_components !== $stored_components ) {
				bp_update_option( 'bp-active-components', $scrubbed_components );

				// Remember WHY these were removed (code unavailable, not an admin
				// choice) so a provider plugin (e.g. BuddyBoss Addons supplying
				// video/document) can restore them on its (re)activation instead
				// of the components staying permanently disabled.
				$scrub_memory = (array) bp_get_option( 'bb_scrubbed_unavailable_components', array() );
				foreach ( $unavailable_components as $unavailable_component ) {
					if ( isset( $stored_components[ $unavailable_component ] ) ) {
						$scrub_memory[ $unavailable_component ] = $stored_components[ $unavailable_component ];
					}
				}
				bp_update_option( 'bb_scrubbed_unavailable_components', $scrub_memory );
			}
		}

		// Loop through optional components.
		foreach ( $bp->optional_components as $component ) {
			if ( bp_is_active( $component ) && file_exists( $bp->plugin_dir . 'bp-' . $component . '/bp-' . $component . '-loader.php' ) ) {
				include $bp->plugin_dir . 'bp-' . $component . '/bp-' . $component . '-loader.php';
			}
		}

		// Loop through required components.
		foreach ( $bp->required_components as $component ) {
			if ( file_exists( $bp->plugin_dir . 'bp-' . $component . '/bp-' . $component . '-loader.php' ) ) {
				include $bp->plugin_dir . 'bp-' . $component . '/bp-' . $component . '-loader.php';
			}
		}

		// Add Core to required components.
		$bp->required_components[] = 'core';

		/**
		 * Fires after the loading of individual components.
		 *
		 * @since BuddyPress 2.0.0
		 */
		do_action( 'bp_core_components_included' );
	}

	/**
	 * Load integrations files
	 *
	 * @since BuddyBoss 1.0.0
	 */
	private function load_integrations() {
		$bp = buddypress();

		/**
		 * Filters the included and optional integrations.
		 *
		 * @since BuddyBoss 1.0.0
		 *
		 * @param array $value Array of included and optional integrations.
		 */
		/*
		 * LearnDash was removed from this whitelist in BuddyBoss 3.0.0
		 * when the integration was extracted into the buddyboss-learndash
		 * addon plugin. Addons register themselves via `bp_setup_integrations`
		 * / `bb_after_register_features`; Platform no longer needs to list
		 * their slugs here.
		 */
		$bp->available_integrations = apply_filters(
			'bp_integrations',
			array(
				'buddyboss-app',
				'pusher',
				'recaptcha',
				'compatibility',
			)
		);

		$integration_dir     = $bp->plugin_dir . '/bp-integrations/';
		$feature_integration_dir = $bp->plugin_dir . '/bb-features/integrations/';

		foreach ( $bp->available_integrations as $integration ) {
			$file = "{$integration_dir}{$integration}/bp-{$integration}-loader.php";
			if ( file_exists( $file ) ) {
				include $file;
				continue;
			}

			$file = "{$integration_dir}{$integration}/bb-{$integration}-loader.php";
			if ( file_exists( $file ) ) {
				include $file;
				continue;
			}

			// Check bb-features/integrations/ for migrated integrations.
			$file = "{$feature_integration_dir}{$integration}/bb-{$integration}-loader.php";
			if ( file_exists( $file ) ) {
				include $file;
			}
		}

		/**
		 * Fires after the loading of individual integrations.
		 *
		 * @since BuddyBoss 1.0.0
		 */
		do_action( 'bp_core_integrations_included' );
	}

	/**
	 * Include bp-core files.
	 *
	 * @since BuddyPress 1.6.0
	 *
	 * @see BP_Component::includes() for description of parameters.
	 *
	 * @param array $includes See {@link BP_Component::includes()}.
	 */
	public function includes( $includes = array() ) {

		if ( ! is_admin() ) {
			return;
		}

		$includes = array(
			'admin',
		);

		parent::includes( $includes );
	}

	/**
	 * Set up bp-core global settings.
	 *
	 * Sets up a majority of the BuddyPress globals that require a minimal
	 * amount of processing, meaning they cannot be set in the BuddyPress class.
	 *
	 * @since BuddyPress 1.5.0
	 *
	 * @see BP_Component::setup_globals() for description of parameters.
	 *
	 * @param array $args See {@link BP_Component::setup_globals()}.
	 */
	public function setup_globals( $args = array() ) {
		$bp = buddypress();

		/** Database *********************************************************
		 */

		// Get the base database prefix.
		if ( empty( $bp->table_prefix ) ) {
			$bp->table_prefix = bp_core_get_table_prefix();
		}

		// The domain for the root of the site where the main blog resides.
		if ( empty( $bp->root_domain ) ) {
			$bp->root_domain = bp_core_get_root_domain();
		}

		// Fetches all of the core BuddyPress settings in one fell swoop.
		if ( empty( $bp->site_options ) ) {
			$bp->site_options = bp_core_get_root_options();
		}

		// The names of the core WordPress pages used to display BuddyPress content.
		if ( empty( $bp->pages ) ) {
			$bp->pages = bp_core_get_directory_pages();
		}

		/** Basic current user data ******************************************
		 */

		// Logged in user is the 'current_user'.
		$current_user = wp_get_current_user();

		// The user ID of the user who is currently logged in.
		$bp->loggedin_user     = new stdClass();
		$bp->loggedin_user->id = isset( $current_user->ID ) ? $current_user->ID : 0;

		/** Avatars **********************************************************
		 */

		// Fetches the default Gravatar image to use if the user/group/blog has no avatar or gravatar.
		$bp->grav_default = new stdClass();

		/**
		 * Filters the default user Gravatar.
		 *
		 * @since BuddyPress 1.1.0
		 *
		 * @param string $value Default user Gravatar.
		 */
		$bp->grav_default->user = apply_filters( 'bp_user_gravatar_default', $bp->site_options['avatar_default'] );

		/**
		 * Filters the default group Gravatar.
		 *
		 * @since BuddyPress 1.1.0
		 *
		 * @param string $value Default group Gravatar.
		 */
		$bp->grav_default->group = apply_filters( 'bp_group_gravatar_default', $bp->grav_default->user );

		/**
		 * Filters the default blog Gravatar.
		 *
		 * @since BuddyPress 1.1.0
		 *
		 * @param string $value Default blog Gravatar.
		 */
		$bp->grav_default->blog = apply_filters( 'bp_blog_gravatar_default', $bp->grav_default->user );

		// Notifications table. Included here for legacy purposes. Use
		// bp-notifications instead.
		$bp->core->table_name_notifications = $bp->table_prefix . 'bp_notifications';

		// Backward compatibility for plugins modifying the legacy bp_nav and bp_options_nav global properties.
		$bp->bp_nav         = new BP_Core_BP_Nav_BackCompat();
		$bp->bp_options_nav = new BP_Core_BP_Options_Nav_BackCompat();

		/**
		 * Used to determine if user has admin rights on current content. If the
		 * logged in user is viewing their own profile and wants to delete
		 * something, is_item_admin is used. This is a generic variable so it
		 * can be used by other components. It can also be modified, so when
		 * viewing a group 'is_item_admin' would be 'true' if they are a group
		 * admin, and 'false' if they are not.
		 */
		bp_update_is_item_admin( bp_user_has_access(), 'core' );

		// Is the logged in user is a mod for the current item?
		bp_update_is_item_mod( false, 'core' );

		/**
		 * Fires at the end of the setup of bp-core globals setting.
		 *
		 * @since BuddyPress 1.1.0
		 */
		do_action( 'bp_core_setup_globals' );
	}

	/**
	 * Setup cache groups
	 *
	 * @since BuddyPress 2.2.0
	 */
	public function setup_cache_groups() {

		// Global groups.
		wp_cache_add_global_groups(
			array(
				'bp',
				'bp_pages',
				'bp_invitations',
				'bb_subscriptions',
				'bb_reactions',
				'bb_reaction_data',
				'bb_topics',
			)
		);

		parent::setup_cache_groups();
	}

	/**
	 * Set up post types.
	 *
	 * @since BuddyPress BuddyPress (2.4.0)
	 */
	public function register_post_types() {

		// Emails
		if ( bp_is_root_blog() && ! is_network_admin() ) {
			register_post_type(
				bp_get_email_post_type(),
				apply_filters(
					'bp_register_email_post_type',
					array(
						'description'        => __( 'BuddyBoss emails', 'buddyboss-platform' ),
						'labels'             => bp_get_email_post_type_labels(),
						'menu_icon'          => 'dashicons-email-alt',
						'public'             => false,
						'publicly_queryable' => bp_current_user_can( 'bp_moderate' ),
						'query_var'          => false,
						'rewrite'            => false,
						'show_in_admin_bar'  => false,
						'show_in_menu'       => false,
						'show_ui'            => bp_current_user_can( 'bp_moderate' ),
						'supports'           => bp_get_email_post_type_supports(),
					)
				)
			);
		}

		if ( bp_is_active( 'groups' ) && true === bp_disable_group_type_creation() ) {
			// Register Group Types custom post type.
			register_post_type(
				bp_groups_get_group_type_post_type(),
				apply_filters(
					'bp_register_group_type_post_type',
					array(
						'description'        => __( 'BuddyBoss group type', 'buddyboss-platform' ),
						'labels'             => bp_groups_get_group_type_post_type_labels(),
						'public'             => false,
						'publicly_queryable' => false,
						'query_var'          => false,
						'rewrite'            => false,
						'show_in_admin_bar'  => false,
						'show_in_menu'       => false,
						'map_meta_cap'       => true,
						'show_in_rest'       => true,
						'show_ui'            => bp_current_user_can( 'bp_moderate' ),
						'supports'           => bp_groups_get_group_type_post_type_supports(),
					)
				)
			);
		}

		parent::register_post_types();
	}

	/**
	 * Init the BuddyBoss REST API.
	 *
	 * @param array $controllers Optional. See BP_Component::rest_api_init() for description.
	 *
	 * @since BuddyBoss 2.8.80
	 */
	public function rest_api_init( $controllers = array() ) {
		$controllers = array(
			/**
			 * As the core component is always loaded,
			 * let's register the Components endpoint here.
			 */
			'BB_REST_Topics_Endpoint'
		);

		parent::rest_api_init( $controllers );
	}

	/**
	 * Register the BB Core Blocks.
	 *
	 * @since BuddyBoss 2.9.00
	 *
	 * @param array $blocks Optional. See BP_Component::blocks_init() for description.
	 */
	public function blocks_init( $blocks = array() ) {
		parent::blocks_init( array() );
	}
}
