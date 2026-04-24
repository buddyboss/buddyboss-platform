<?php
/**
 * Main BuddyPress Admin Class.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyPress 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'BP_Admin' ) ) :

	/**
	 * Load BuddyPress plugin admin area.
	 *
	 * @todo Break this apart into each applicable Component.
	 *
	 * @since BuddyPress 1.6.0
	 */
	#[\AllowDynamicProperties]
	class BP_Admin {

		/** Directory *************************************************************/

		/**
		 * Path to the BuddyPress admin directory.
		 *
		 * @since BuddyPress 1.6.0
		 * @var string $admin_dir
		 */
		public $admin_dir = '';

		/** URLs ******************************************************************/

		/**
		 * URL to the BuddyPress admin directory.
		 *
		 * @since BuddyPress 1.6.0
		 * @var string $admin_url
		 */
		public $admin_url = '';

		/**
		 * URL to the BuddyPress images directory.
		 *
		 * @since BuddyPress 1.6.0
		 * @var string $images_url
		 */
		public $images_url = '';

		/**
		 * URL to the BuddyPress admin CSS directory.
		 *
		 * @since BuddyPress 1.6.0
		 * @var string $css_url
		 */
		public $css_url = '';

		/**
		 * URL to the BuddyPress admin JS directory.
		 *
		 * @since BuddyPress 1.6.0
		 * @var string
		 */
		public $js_url = '';

		/** Other *****************************************************************/

		/**
		 * Notices used for user feedback, like saving settings.
		 *
		 * @since BuddyPress 1.9.0
		 * @var array()
		 */
		public $notices = array();

		/** Methods ***************************************************************/

		/**
		 * The main BuddyPress admin loader.
		 *
		 * @since BuddyPress 1.6.0
		 */
		public function __construct() {
			$this->setup_globals();
			$this->includes();
			$this->setup_actions();
		}

		/**
		 * Set admin-related globals.
		 *
		 * @since BuddyPress 1.6.0
		 */
		private function setup_globals() {
			$bp = buddypress();

			// Paths and URLs
			$this->admin_dir  = trailingslashit( $bp->plugin_dir . 'bp-core/admin' ); // Admin path.
			$this->admin_url  = trailingslashit( $bp->plugin_url . 'bp-core/admin' ); // Admin url.
			$this->images_url = trailingslashit( $this->admin_url . 'images' ); // Admin images URL.
			$this->css_url    = trailingslashit( $this->admin_url . 'css' ); // Admin css URL.
			$this->js_url     = trailingslashit( $this->admin_url . 'js' ); // Admin css URL.

			// Main settings page.
			$this->settings_page = 'buddyboss-platform'; // always use custom menu item, instead of setting page

			// Child Admin Settings page will redirect to BuddyPress integration page.
			$this->child_settings_page = bp_core_do_network_admin() ? 'settings.php' : 'options-general.php';

			// Main capability.
			$this->capability = bp_core_do_network_admin() ? 'manage_network_options' : 'manage_options';
		}

		/**
		 * Include required files.
		 *
		 * @since BuddyPress 1.6.0
		 */
		private function includes() {
			require $this->admin_dir . 'bp-core-admin-actions.php';
			require $this->admin_dir . 'bp-core-admin-settings.php';
			require $this->admin_dir . 'bp-core-admin-functions.php';
			require $this->admin_dir . 'bp-core-admin-slugs.php';
			require $this->admin_dir . 'bp-core-admin-tools.php';
			require $this->admin_dir . 'bp-core-admin-help.php';
			require $this->admin_dir . 'bp-core-admin-theme-settings.php';

			// Load the BuddyBoss React settings.
			require $this->admin_dir . 'bb-settings/index.php';
		}

		/**
		 * Set up the admin hooks, actions, and filters.
		 *
		 * @since BuddyPress 1.6.0
		 */
		private function setup_actions() {

			/* General Actions ***************************************************/

			// Add some page specific output to the <head>.
			add_action( 'bp_admin_head', array( $this, 'admin_head' ), 999 );

			// Add menu item to settings menu.
			add_action( bp_core_admin_hook(), array( $this, 'site_admin_menus' ), 68 );
			add_action( bp_core_admin_hook(), array( $this, 'admin_menus' ), 5 );
			// add_action( bp_core_admin_hook(),       array( $this, 'admin_menus_components' ), 75 );
			add_action( bp_core_admin_hook(), array( $this, 'adjust_buddyboss_menus' ), 100 );

			// Enqueue all admin JS and CSS.
			add_action( 'bp_admin_enqueue_scripts', array( $this, 'admin_register_styles' ), 1 );
			add_action( 'bp_admin_enqueue_scripts', array( $this, 'admin_register_scripts' ), 1 );
			add_action( 'bp_admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

			/* BuddyPress Actions ************************************************/

			// Load the BuddyPress metabox in the WP Nav Menu Admin UI.
			add_action( 'load-nav-menus.php', 'bp_admin_wp_nav_menu_meta_box' );

			// Add settings.
			add_action( 'bp_register_admin_settings', array( $this, 'register_admin_settings' ), 5 );

			// Add integrations
			add_action( 'bp_register_admin_integrations', array( $this, 'register_admin_integrations' ), 5 );

			// Add a description of new BuddyPress tools in the available tools page.
			add_action( 'tool_box', 'bp_core_admin_available_tools_intro' );
			add_action( 'bp_network_tool_box', 'bp_core_admin_available_tools_intro' );

			// On non-multisite, catch.
			add_action( 'load-users.php', 'bp_core_admin_user_manage_spammers' );

			// Email CPT column hooks removed — migrated to Settings 2.0 (AJAX handler).

			// Hello BuddyBoss/App.
			add_action( 'admin_footer', array( $this, 'about_buddyboss_app_screen' ) );

			/* Filters ***********************************************************/

			// Add link to settings page.
			add_filter( 'plugin_action_links', array( $this, 'modify_plugin_action_links' ), 10, 2 );
			add_filter( 'network_admin_plugin_action_links', array( $this, 'modify_plugin_action_links' ), 10, 2 );

			// Add "Mark as Spam" row actions on users.php.
			add_filter( 'ms_user_row_actions', 'bp_core_admin_user_row_actions', 10, 2 );
			add_filter( 'user_row_actions', 'bp_core_admin_user_row_actions', 10, 2 );

			// Emails
			add_filter( 'bp_admin_menu_order', array( $this, 'emails_admin_menu_order' ), 20 );

			// Add the separator above the BuddyBoss in admin.
			// add_filter( 'menu_order', array( $this, 'buddyboss_menu_order' ) );

			// Add the separator above the plugins in admin.
			add_filter( 'menu_order', array( $this, 'buddyboss_plugins_menu_order' ) );

			add_action( 'admin_menu', array( $this, 'bp_emails_add_sub_menu_page_admin_menu' ) );
			add_action( bp_core_admin_hook(), array( $this, 'bp_emails_add_sub_menu_page_admin_menu' ) );

			add_action( 'admin_menu', array( $this, 'bp_add_main_menu_page_admin_menu' ) );
			add_action( 'admin_menu', array( $this, 'adjust_buddyboss_menus' ), 100 );

			add_action( 'admin_footer', array( $this, 'bb_display_update_plugin_information' ) );
		}

		/**
		 * Add the separator above the BuddyBoss menu in admin.
		 *
		 * @param int $menu_order Menu order.
		 *
		 * @since BuddyBoss 1.0.0
		 *
		 * @return array
		 */
		public function buddyboss_menu_order( $menu_order ) {
			// Initialize our custom order array.
			$buddyboss_menu_order = array();

			// Get the index of our custom separator.
			$buddyboss_separator = array_search( 'separator-buddyboss-platform', $menu_order, true );

			// Loop through menu order and do some rearranging.
			foreach ( $menu_order as $index => $item ) {

				if ( 'buddyboss-platform' === $item ) {
					$buddyboss_menu_order[] = 'separator-buddyboss';
					$buddyboss_menu_order[] = $item;
					unset( $menu_order[ $buddyboss_separator ] );
				} elseif ( ! in_array( $item, array( 'separator-buddyboss' ), true ) ) {
					$buddyboss_menu_order[] = $item;
				}
			}

			// Return order.
			return $buddyboss_menu_order;
		}

		/**
		 * Add the separator above the plugins menu in admin.
		 *
		 * @param int $menu_order Menu order.
		 *
		 * @since BuddyBoss 1.0.0
		 *
		 * @return array
		 */
		public function buddyboss_plugins_menu_order( $menu_order ) {
			// Initialize our custom order array.
			$plugins_menu_order = array();

			// Get the index of our custom separator.
			$plugins_separator = array_search( 'separator-plugins.php', $menu_order, true );

			// Loop through menu order and do some rearranging.
			foreach ( $menu_order as $index => $item ) {

				if ( 'plugins.php' === $item ) {
					$plugins_menu_order[] = 'separator-plugins';
					$plugins_menu_order[] = $item;
					unset( $menu_order[ $plugins_separator ] );
				} elseif ( ! in_array( $item, array( 'separator-plugins' ), true ) ) {
					$plugins_menu_order[] = $item;
				}
			}

			// Return order.
			return $plugins_menu_order;
		}

		/**
		 * Register main settings menu elements.
		 *
		 * @since BuddyBoss 1.0.0
		 */
		public function admin_menus_components() {

			$hooks = array();

			$hooks[] = add_submenu_page(
				$this->settings_page,
				__( '', 'buddyboss' ),
				__( '', 'buddyboss' ),
				$this->capability,
				'bp-plugin-separator-notice',
				''
			);

			// Legacy "Components" submenu removed in Settings 2.0. Components now live
			// inside the Settings grid as feature cards. The bp-components URL is
			// redirected to bb-settings by bb_redirect_bp_settings_before_permission_check()
			// in bp-core-admin-actions.php.

			// Legacy "Pages" submenu retired in Settings 2.0 — the page-directory
			// mapping now lives under Appearance → Pages inside the React admin.
			// Bookmarks and third-party links targeting `admin.php?page=bp-pages`
			// are forwarded by the `bp-pages` branch inside
			// `bb_redirect_bp_settings_before_permission_check()`
			// (`src/bp-core/admin/bp-core-admin-actions.php`), which runs on
			// `admin_menu` at PHP_INT_MAX so it fires before WP's permission
			// gate — required because the submenu slug no longer exists here.

			// Settings 2.0 replaces the legacy bp-settings submenu at the same menu position.
			// The 'bb-settings' slug points to the React admin registered in bb-admin-settings-page.php;
			// the label is "Settings" (not "Settings 2.0") so end users don't see transitional naming.
			$hooks[] = add_submenu_page(
				$this->settings_page,
				__( 'BuddyBoss Settings', 'buddyboss' ),
				__( 'Settings', 'buddyboss' ),
				$this->capability,
				'bb-settings',
				function_exists( 'bb_admin_settings_page' ) ? 'bb_admin_settings_page' : 'bp_core_admin_settings'
			);

			// Legacy "Plugin Integrations" submenu removed in Settings 2.0. Integrations
			// now live inside the Settings grid under the "Integrations" category.
			// The bp-integrations URL is redirected by bb_redirect_bp_integrations_*
			// in bp-core-admin-actions.php.
		}

		/**
		 * Register network-admin nav menu elements.
		 *
		 * Contextually hooked to network-admin depending on current configuration.
		 *
		 * @since BuddyBoss 1.2.3
		 */
		public function bp_add_main_menu_page_admin_menu() {

			global $menu;

			// Bail if user cannot moderate.
			if ( ! bp_current_user_can( 'manage_options' ) ) {
				return;
			}
			// Add BuddyBoss Menu separator above the BuddyBoss and below the BuddyBoss
			if ( bp_current_user_can( 'manage_options' ) ) {
				$menu[] = array( '', 'read', 'separator-buddyboss', '', 'wp-menu-separator buddyboss' ); // WPCS: override ok.
				$menu[] = array( '', 'read', 'separator-plugins', '', 'wp-menu-separator plugins' ); // WPCS: override ok.
			}

			$hooks = array();
			if ( is_multisite() && bp_is_network_activated() && ! bp_is_multiblog_mode() ) {
				$hooks[] = add_menu_page(
					'BuddyBoss',
					'BuddyBoss',
					$this->capability,
					$this->settings_page,
					'bp_core_admin_backpat_menu',
					'none',
					3
				);
			}
		}

		/**
		 * Register site- or network-admin nav menu elements.
		 *
		 * Contextually hooked to site or network-admin depending on current configuration.
		 *
		 * @since BuddyPress 1.6.0
		 */
		public function admin_menus() {

			global $menu;

			// Bail if user cannot moderate.
			if ( ! bp_current_user_can( 'manage_options' ) ) {
				return;
			}

			// Add BuddyBoss Menu separator above the BuddyBoss and below the BuddyBoss
			if ( bp_current_user_can( 'manage_options' ) ) {
				$menu[] = array( '', 'read', 'separator-buddyboss', '', 'wp-menu-separator buddyboss' ); // WPCS: override ok.
				$menu[] = array( '', 'read', 'separator-plugins', '', 'wp-menu-separator plugins' ); // WPCS: override ok.
			}

			$hooks = array();

			// Changed in BP 1.6 . See bp_core_admin_backpat_menu().
			$hooks[] = add_menu_page(
				'BuddyBoss',
				'BuddyBoss',
				$this->capability,
				$this->settings_page,
				'bp_core_admin_backpat_menu',
				'none',
				3
			);

			$hooks[] = add_submenu_page(
				'bp-general-settings',
				__( 'BuddyBoss Help', 'buddyboss' ),
				__( 'Help', 'buddyboss' ),
				$this->capability,
				'bp-general-settings',
				'bp_core_admin_backpat_page'
			);

			// Add the Separator.
			// $hooks[] = add_submenu_page(
			// $this->settings_page,
			// __( '', 'buddyboss' ),
			// __( '', 'buddyboss' ),
			// $this->capability,
			// 'bp-plugin-separator-notice',
			// ''
			// );

			// Add the option pages.
			$hooks[] = add_submenu_page(
				$this->child_settings_page,
				__( 'BuddyPress Settings', 'buddyboss' ),
				__( 'BuddyPress', 'buddyboss' ),
				$this->capability,
				'admin.php?page=bb-settings'
			);

			// Legacy "Components" submenu removed in Settings 2.0. Components now live
			// inside the Settings grid as feature cards. The bp-components URL is
			// redirected to bb-settings by bb_redirect_bp_settings_before_permission_check()
			// in bp-core-admin-actions.php.

			// Legacy "Pages" submenu retired in Settings 2.0 — the page-directory
			// mapping now lives under Appearance → Pages inside the React admin.
			// Bookmarks and third-party links targeting `admin.php?page=bp-pages`
			// are forwarded by the `bp-pages` branch inside
			// `bb_redirect_bp_settings_before_permission_check()`
			// (`src/bp-core/admin/bp-core-admin-actions.php`), which runs on
			// `admin_menu` at PHP_INT_MAX so it fires before WP's permission
			// gate — required because the submenu slug no longer exists here.

			// Settings 2.0 replaces the legacy bp-settings submenu at the same menu position.
			// The 'bb-settings' slug points to the React admin registered in bb-admin-settings-page.php;
			// the label is "Settings" (not "Settings 2.0") so end users don't see transitional naming.
			$hooks[] = add_submenu_page(
				$this->settings_page,
				__( 'BuddyBoss Settings', 'buddyboss' ),
				__( 'Settings', 'buddyboss' ),
				$this->capability,
				'bb-settings',
				function_exists( 'bb_admin_settings_page' ) ? 'bb_admin_settings_page' : 'bp_core_admin_settings'
			);

			// Legacy "Plugin Integrations" submenu removed in Settings 2.0. Integrations
			// now live inside the Settings grid under the "Integrations" category.
			// The bp-integrations URL is redirected by bb_redirect_bp_integrations_*
			// in bp-core-admin-actions.php.

			// ReadyLaunch legacy admin page retired in BuddyBoss [BBVERSION] —
			// the `bb-readylaunch` URL now redirects to Appearance in Settings 2.0
			// via `bp_core_admin_backpat_menu()` (`bp-core-admin-actions.php`).

			// For consistency with non-Multisite, we add a Tools menu in
			// the Network Admin as a home for our Tools panel.
			if ( is_multisite() && bp_core_do_network_admin() ) {
				$tools_parent = 'network-tools';

				$hooks[] = add_menu_page(
					__( 'Tools', 'buddyboss' ),
					__( 'Tools', 'buddyboss' ),
					$this->capability,
					$tools_parent,
					'bp_core_tools_top_level_item',
					'',
					24 // Just above Settings.
				);

				$hooks[] = add_submenu_page(
					$tools_parent,
					__( 'Available Tools', 'buddyboss' ),
					__( 'Available Tools', 'buddyboss' ),
					$this->capability,
					'available-tools',
					'bp_core_admin_available_tools_page'
				);
			} else {
				$tools_parent = 'tools.php';
			}

			$hooks[] = add_submenu_page(
				$this->settings_page,
				__( 'Tools', 'buddyboss' ),
				__( 'Tools', 'buddyboss' ),
				$this->capability,
				'bp-tools',
				'bp_core_admin_tools'
			);

			$hooks[] = add_submenu_page(
				$this->settings_page,
				__( 'Help', 'buddyboss' ),
				__( 'Help', 'buddyboss' ),
				$this->capability,
				'bp-help',
				'bp_core_admin_help'
			);

			$hooks[] = add_submenu_page(
				$this->settings_page,
				__( '', 'buddyboss' ),
				__( '', 'buddyboss' ),
				$this->capability,
				'bp-plugin-separator-notice',
				''
			);

			// Network admin email menu removed — migrated to Settings 2.0.
		}

		public function adjust_buddyboss_menus() {
			global $menu, $submenu;

			// only if login user has access to menu
			if ( ! isset( $submenu['buddyboss-platform'] ) ) {
				return;
			}

			$submenu['buddyboss-platform'] = array_values( $submenu['buddyboss-platform'] );

			if ( isset( $app_menu ) ) {
				$submenu['buddyboss-platform'][] = $app_menu;
			}

			// Make Settings 2.0 the default landing page for the BuddyBoss top-level menu.
			//
			// WordPress builds the parent menu's <a href> from $submenu[$parent_slug][0][2]
			// (the slug of the first submenu row). To send users to bb-settings without
			// reordering the visible submenu list (Pages, Settings, Upgrade, …), we prepend
			// a hidden pseudo-row whose only job is to drive that parent link. The 5th
			// element ("hidden" class) keeps the row out of view; the page_title is set to
			// match the real Settings row so get_admin_page_title() returns the right value
			// regardless of which row matches first.
			$bb_default_row = array(
				'',                                                // menu_title (empty so screen readers skip it).
				$this->capability,                                  // capability.
				'bb-settings',                                       // menu_slug — drives the parent's href.
				__( 'BuddyBoss Settings', 'buddyboss' ),            // page_title — keeps <title> intact when this row matches first.
				'bb-default-page-link hidden',                      // 5th element: classes on the rendered <li>.
			);
			array_unshift( $submenu['buddyboss-platform'], $bb_default_row );

			// if there's no buddyboss plugin, don't do anything
			if ( ! array_key_exists( 'buddyboss-settings', $submenu ) ) {
				return;
			}

			add_submenu_page( $this->settings_page, '', '', $this->capability, 'bp-plugin-seperator' );

			$submenu['buddyboss-platform'] = array_merge(
				$submenu['buddyboss-platform'],
				$submenu['buddyboss-settings']
			);

			remove_menu_page( 'buddyboss-settings' );
			unset( $submenu['buddyboss-settings'] );
		}

		/**
		 * Register site-admin nav menu elements.
		 *
		 * @since BuddyPress 2.5.0
		 */
		public function site_admin_menus() {
			if ( ! bp_current_user_can( 'manage_options' ) ) {
				return;
			}

			$hooks = array();

			// Appearance > Emails.
			$hooks[] = add_theme_page(
				__( 'Emails', 'buddyboss' ),
				__( 'Emails', 'buddyboss' ),
				$this->capability,
				'bp-emails-customizer-redirect',
				'bp_email_redirect_to_customizer'
			);

			if ( ! is_network_admin() && ! bp_is_network_activated() ) {
				$email_url = 'admin.php?page=bb-settings&tab=emails&panel=all_emails';
				$hooks[]   = add_submenu_page(
					'buddyboss-platform',
					__( 'Emails', 'buddyboss' ),
					__( 'Emails', 'buddyboss' ),
					'bp_moderate',
					$email_url,
					''
				);
			}

			foreach ( $hooks as $hook ) {
				add_action( "admin_head-$hook", 'bp_core_modify_admin_menu_highlight' );
			}
		}

		public function bp_emails_add_sub_menu_page_admin_menu() {

			if ( is_multisite() && bp_is_network_activated() && bp_is_root_blog() ) {
				$email_url = get_admin_url( bp_get_root_blog_id(), 'admin.php?page=bb-settings&tab=emails&panel=all_emails' );
				// Add our screen.
				$hook = add_submenu_page(
					'buddyboss-platform',
					__( 'Emails', 'buddyboss' ),
					__( 'Emails', 'buddyboss' ),
					'bp_moderate',
					$email_url,
					''
				);
			}
		}

		/**
		 * Register the settings.
		 *
		 * @since BuddyPress 1.6.0
		 */
		public function register_admin_settings() {

			$bp = buddypress();
			require_once trailingslashit( $bp->plugin_dir . 'bp-core/classes' ) . '/class-bp-admin-tab.php';
			require_once trailingslashit( $bp->plugin_dir . 'bp-core/classes' ) . '/class-bp-admin-setting-tab.php';
			require_once trailingslashit( $bp->plugin_dir . 'bp-core/classes' ) . '/class-bb-admin-setting-fields.php';
		}

		/**
		 * Register the integrations.
		 *
		 * @since BuddyPress 1.6.0
		 */
		public function register_admin_integrations() {

			$bp = buddypress();
			require_once trailingslashit( $bp->plugin_dir . 'bp-core/classes' ) . '/class-bp-admin-tab.php';
			require_once trailingslashit( $bp->plugin_dir . 'bp-core/classes' ) . '/class-bp-admin-integration-tab.php';

			// integrations should be loaded in its loader file
		}

		/**
		 * Add Settings link to plugins area.
		 *
		 * @since BuddyPress 1.6.0
		 * @since BuddyBoss 1.0.0 Updated the Settings path
		 *
		 * @param array  $links Links array in which we would prepend our link.
		 * @param string $file  Current plugin basename.
		 * @return array Processed links.
		 */
		public function modify_plugin_action_links( $links, $file ) {

			// Return normal links if not BuddyPress.
			if ( plugin_basename( buddypress()->basename ) != $file ) {
				return $links;
			}

			// Add a few links to the existing links array.
			return array_merge(
				$links,
				array(
					'settings'      => '<a href="' . esc_url( bp_get_admin_url( 'admin.php?page=bb-settings' ) ) . '">' . esc_html__( 'Settings', 'buddyboss' ) . '</a>',
					'about'         => '<a href="' . esc_url( bp_get_admin_url( '?hello=buddyboss' ) ) . '">' . esc_html__( 'About', 'buddyboss' ) . '</a>',
					'release_notes' => '<a href="javascript:void(0);" id="bb-plugin-release-link">' . esc_html__( 'Release Notes', 'buddyboss' ) . '</a>',
				)
			);
		}

		/**
		 * Add some general styling to the admin area.
		 *
		 * @since BuddyPress 1.6.0
		 */
		public function admin_head() {

			// Settings pages.
			remove_submenu_page( $this->settings_page, $this->settings_page );

			// Network Admin Tools.
			remove_submenu_page( 'network-tools', 'network-tools' );
		}

		/**
		 * Add some general styling to the admin area.
		 *
		 * @since BuddyPress 1.6.0
		 * @since BuddyBoss 1.0.0 Added support for Hello BuddyBoss App
		 */
		public function enqueue_scripts( $hook ) {
			wp_enqueue_style( 'bp-admin-common-css' );

			wp_enqueue_script( 'bp-fitvids-js' );

            // phpcs:ignore
			if ( isset( $_GET['page'] ) && 'bp-help' === $_GET['page'] ) {
				wp_enqueue_script( 'bp-wp-api-js' );
				wp_enqueue_script( 'bp-help-js' );

				$bp_help_base_url = bp_get_admin_url(
					add_query_arg(
						array(
							'page' => 'bp-help',
						),
						'admin.php'
					)
				);

				wp_localize_script(
					'bp-help-js',
					'BP_HELP',
					array(
						'ajax_url'           => admin_url( 'admin-ajax.php' ),
						'bb_help_url'        => $bp_help_base_url,
						'bb_help_title'      => esc_html__( 'Docs', 'buddyboss' ),
						'bb_help_no_network' => __( '<strong>You are offline.</strong> Documentation requires internet access.', 'buddyboss' ),
					)
				);
			}

			// Hello BuddyBoss.
			wp_enqueue_style( 'bp-hello-css' );
			wp_enqueue_script( 'bp-hello-js' );
			wp_localize_script(
				'bp-hello-js',
				'BP_HELLO',
				array(
					'bb_display_auto_popup' => get_option( '_bb_is_update' ),
				)
			);
		}

		/** About BuddyBoss and BuddyBoss App ********************************************/

		/**
		 * Output the Hello BuddyBoss App template.
		 *
		 * @since BuddyBoss 1.0.0 Output the Hello BuddyBoss App template.
		 */
		public function about_buddyboss_app_screen() {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( 0 !== strpos( get_current_screen()->id, 'dashboard' ) || empty( $_GET['hello'] ) || 'buddyboss-app' !== $_GET['hello'] ) {
				return;
			}

			include $this->admin_dir . 'templates/about-buddyboss-app.php';
		}

		/** Helpers ***************************************************************/

		/**
		 * Return true/false based on whether a query argument is set.
		 *
		 * @see bp_do_activation_redirect()
		 *
		 * @since BuddyPress 2.2.0
		 *
		 * @return bool
		 */
		public static function is_new_install() {
			return (bool) isset( $_GET['is_new_install'] );
		}

		/**
		 * Return a user-friendly version-number string, for use in translations.
		 *
		 * @since BuddyPress 2.2.0
		 *
		 * @return string
		 */
		public static function display_version() {

			// Use static variable to prevent recalculations.
			static $display = '';

			// Only calculate on first run.
			if ( '' === $display ) {

				// Get current version.
				$version = bp_get_version();

				// Check for prerelease hyphen.
				$pre = strpos( $version, '-' );

				// Strip prerelease suffix.
				$display = ( false !== $pre )
				? substr( $version, 0, $pre )
				: $version;
			}

			// Done!
			return $display;
		}

		/**
		 * Add Emails menu item to custom menus array.
		 *
		 * Several BuddyPress components have top-level menu items in the Dashboard,
		 * which all appear together in the middle of the Dashboard menu. This function
		 * adds the Emails screen to the array of these menu items.
		 *
		 * @since BuddyPress 2.4.0
		 *
		 * @param array $custom_menus The list of top-level BP menu items.
		 * @return array $custom_menus List of top-level BP menu items, with Emails added.
		 */
		public function emails_admin_menu_order( $custom_menus = array() ) {
			array_push( $custom_menus, 'admin.php?page=bb-settings&tab=emails&panel=all_emails' );

			if ( is_network_admin() && bp_is_network_activated() ) {
				array_push(
					$custom_menus,
					get_admin_url( bp_get_root_blog_id(), 'admin.php?page=bb-settings&tab=emails&panel=all_emails' )
				);
			}

			return $custom_menus;
		}

		/**
		 * Register styles commonly used by BuddyPress wp-admin screens.
		 *
		 * @since BuddyPress 2.5.0
		 */
		public function admin_register_styles() {
			$min = bp_core_get_minified_asset_suffix();
			$url = $this->css_url;

			/**
			 * Filters the BuddyBoss Core Admin CSS file path.
			 *
			 * @since BuddyPress 1.6.0
			 *
			 * @param string $file File path for the admin CSS.
			 */
			$common_css = apply_filters( 'bp_core_admin_common_css', "{$url}common{$min}.css" );

			/**
			 * Filters the BuddyPress admin stylesheet files to register.
			 *
			 * @since BuddyPress 2.5.0
			 *
			 * @param array $value Array of admin stylesheet file information to register.
			 */
			$styles = apply_filters(
				'bp_core_admin_register_styles',
				array(
					// Legacy.
					'bp-admin-common-css'    => array(
						'file'         => $common_css,
						'dependencies' => array(),
					),

					// 2.5
					'bp-customizer-controls' => array(
						'file'         => "{$url}customizer-controls{$min}.css",
						'dependencies' => array(),
					),

					// 3.0
					'bp-hello-css'           => array(
						'file'         => "{$url}hello{$min}.css",
						'dependencies' => array( 'bp-admin-common-css' ),
					),
				)
			);

			$version = bp_get_version();

			foreach ( $styles as $id => $style ) {
				wp_register_style( $id, $style['file'], $style['dependencies'], $version );
				wp_style_add_data( $id, 'rtl', true );

				if ( $min ) {
					wp_style_add_data( $id, 'suffix', $min );
				}
			}
		}

		/**
		 * Register JS commonly used by BuddyPress wp-admin screens.
		 *
		 * @since BuddyPress 2.5.0
		 */
		public function admin_register_scripts() {
			$min = bp_core_get_minified_asset_suffix();
			$url = $this->js_url;

			/**
			 * Filters the BuddyPress admin JS files to register.
			 *
			 * @since BuddyPress 2.5.0
			 *
			 * @param array $value Array of admin JS file information to register.
			 */
			$scripts = apply_filters(
				'bp_core_admin_register_scripts',
				array(
					// 2.5
					'bp-customizer-controls' => array(
						'file'         => "{$url}customizer-controls{$min}.js",
						'dependencies' => array( 'jquery' ),
						'footer'       => true,
					),

					// 3.0
					'bp-hello-js'            => array(
						'file'         => "{$url}hello{$min}.js",
						'dependencies' => array(),
						'footer'       => true,
					),

					// 1.1
					'bp-fitvids-js'          => array(
						'file'         => "{$url}fitvids{$min}.js",
						'dependencies' => array(),
						'footer'       => true,
					),

					'bp-wp-api-js'           => array(
						'file'         => "{$url}lib/wpapi{$min}.js",
						'dependencies' => array(),
						'footer'       => true,
					),

					// 1.2.3
					'bp-help-js'             => array(
						'file'         => "{$url}help{$min}.js",
						'dependencies' => array( 'jquery' ),
						'footer'       => true,
					),
				)
			);

			$version = bp_get_version();

			foreach ( $scripts as $id => $script ) {
				wp_register_script( $id, $script['file'], $script['dependencies'], $version, $script['footer'] );
			}
		}

		/**
		 * Display plugin information after plugin successfully updated.
		 *
		 * @since BuddyBoss 1.9.1
		 */
		public function bb_display_update_plugin_information() {
			if ( 0 !== strpos( get_current_screen()->id, 'plugins' ) ) {
				return;
			}

			// Output the modal HTML template.
			// This is needed for the Release Notes link to work.
			// Use output buffering and error handling to prevent breaking WordPress scripts.
			global $bp;
			$template_path = trailingslashit( $bp->plugin_dir . 'bp-core/admin' ) . 'templates/update-buddyboss.php';

			if ( file_exists( $template_path ) ) {
				// Use output buffering to catch any errors.
				ob_start();
				try {
					// Suppress any errors from the template to prevent breaking the page.
					@include $template_path;
					$output = ob_get_clean();

					// Only output if we got valid HTML (not an error).
					if ( ! empty( $output ) && false === strpos( $output, 'Fatal error' ) ) {
						echo $output; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					}
				} catch ( Exception $e ) {
					ob_end_clean();
					// Silently fail to prevent breaking WordPress admin.
				}
			}

			// Clean up the update flag to prevent database bloat.
			delete_option( '_bb_is_update' );
		}
	}
endif; // End class_exists check.
