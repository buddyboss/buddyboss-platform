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

			// Standalone Knowledge Base modal helper — lets add-on admin surfaces
			// (Membership, Courses) mount the shared KB modal via window.bbKb.
			require $this->admin_dir . 'bb-kb-standalone.php';
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

			// Redirect the legacy `?page=bp-help` admin URL to the new Settings 2.0
			// Help tab. The `bp-help` submenu no longer exists, so WordPress denies
			// access to that slug from wp-admin/includes/menu.php (via
			// `admin_page_access_denied`) BEFORE `admin_init` fires. We hook the
			// access-denied action so the redirect runs in that path, and keep the
			// `admin_init` hook as a fallback for any context where the slug is
			// still resolvable. Both run before output, so `wp_safe_redirect()` is safe.
			add_action( 'admin_page_access_denied', array( $this, 'bb_redirect_legacy_help_page' ) );
			add_action( 'admin_init', array( $this, 'bb_redirect_legacy_help_page' ) );

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

			// Fix the "View details"/"View version details" links on the Plugins page, which
			// otherwise point to a WordPress.org plugin_information lookup that always fails.
			add_filter( 'site_transient_update_plugins', array( $this, 'bb_fix_plugin_details_link' ), 20 );
			add_filter( 'plugins_api', array( $this, 'bb_plugins_api_information' ), 10, 3 );

			// Later than any add-on's own handler, so one that is active always
			// answers for itself. Every add-on in the suite registers at 10 except
			// BuddyBoss App, which registers at 99 - and, unlike the others, does not
			// check $result before answering, so it overwrites rather than defers.
			// 999 stays behind both conventions; see
			// bb_get_known_buddyboss_addon_slugs() for why that matters.
			add_filter( 'plugins_api', array( $this, 'bb_plugins_api_addon_fallback' ), 999, 3 );

			// Keep the licensed package URL out of the plugin dependency cache.
			add_filter( 'pre_set_site_transient_wp_plugin_dependencies_plugin_data', array( $this, 'bb_strip_dependency_api_data' ) );
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

			// Legacy "Plugin Integrations" submenu removed in Settings 2.0. The
			// per-integration settings now live inside the Settings grid under the
			// "Integrations" category (the bp-integrations URL is redirected by
			// bb_redirect_bp_integrations_* in bp-core-admin-actions.php).
			//
			// Separately, the Integrations *marketplace* — a curated directory of
			// third-party integrations fetched from buddyboss.com — is its own
			// standalone React page under the new bb-integrations slug. Registered
			// unconditionally (like bb-settings above): add_submenu_page() only
			// stores the callback string; WP invokes bb_admin_integrations_page()
			// at render time, by which point bb-admin-integrations-page.php is loaded.
			$hooks[] = add_submenu_page(
				$this->settings_page,
				__( 'BuddyBoss Integrations', 'buddyboss' ),
				__( 'Integrations', 'buddyboss' ),
				$this->capability,
				'bb-integrations',
				'bb_admin_integrations_page'
			);

			// ReadyLaunch legacy admin page retired in BuddyBoss 3.0.0 —
			// the `bb-readylaunch` URL now redirects to Appearance in Settings 2.0
			// via `bp_core_admin_backpat_menu()` (`bp-core-admin-actions.php`).

			// Help submenu points at the Settings 2.0 Help tab. WordPress treats
			// a `menu_slug` containing a URL (with `?`) as a direct link rather
			// than registering a new page — same trick used by the Emails
			// submenu below. Direct visits to the legacy `?page=bp-help` URL
			// are redirected server-side by `bb_redirect_legacy_help_page()`.
			$hooks[] = add_submenu_page(
				$this->settings_page,
				__( 'Help', 'buddyboss' ),
				__( 'Help', 'buddyboss' ),
				$this->capability,
				'admin.php?page=bb-settings&tab=help',
				''
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

		/**
		 * Redirect legacy `?page=bp-help` URLs to the new Settings 2.0 Help tab.
		 *
		 * The Help submenu now points at `?page=bb-settings&tab=help` directly and
		 * the legacy `bp-help` submenu no longer exists, but bookmarks, plugin
		 * links and old in-app links may still hit `bp-help`. Send those visitors
		 * to the new URL so a single canonical Help page exists.
		 *
		 * Hooked on both `admin_page_access_denied` and `admin_init`. Because the
		 * `bp-help` slug is no longer a registered submenu, WordPress denies access
		 * to it in `wp-admin/includes/menu.php` (firing `admin_page_access_denied`)
		 * before `admin_init` runs — so the access-denied hook is what actually
		 * catches the redirect; `admin_init` is a fallback. The method is
		 * idempotent: it no-ops unless `?page=bp-help` is the current request.
		 *
		 * @since BuddyBoss 3.1.0
		 */
		public function bb_redirect_legacy_help_page() {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only navigation redirect, no state change.
			$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
			if ( 'bp-help' !== $page ) {
				return;
			}

			if ( ! current_user_can( $this->capability ) ) {
				return;
			}

			wp_safe_redirect( bp_get_admin_url( 'admin.php?page=bb-settings&tab=help' ) );
			exit;
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
		 * Normalize BuddyBoss update transient entries so their details links work.
		 *
		 * BuddyBoss Platform is distributed from BuddyBoss's own servers, not the
		 * WordPress.org plugin directory. WordPress core builds the "View details"
		 * (plugin row) and "View version details" (update notice) links from the
		 * 'slug' it finds on this transient. A missing slug is not an option either:
		 * core then falls back to $response->id (the plugin file path) or renders
		 * the external 'url' inside a thickbox iframe, which buddyboss.com refuses
		 * via X-Frame-Options — both dead ends. So the slug is normalized here and
		 * bb_plugins_api_information() serves the plugin information for it locally,
		 * so the links open a working modal instead of a WordPress.org 404. The
		 * same normalization is applied to any BuddyBoss add-on entry whose slug
		 * does not name its directory, which bb_plugins_api_addon_fallback() then
		 * answers for.
		 *
		 * Both transient keys matter, for two different core paths: 'response'
		 * feeds wp_plugin_update_row() (the update notice's "View version x.x.x
		 * details" link), while WP_Plugins_List_Table::prepare_items() merges
		 * whichever of 'response' or 'no_update' holds the plugin into the row's
		 * data, and that is where the row's own "View details" link gets its
		 * slug. Dropping 'no_update' would therefore lose that link in the
		 * common, already-up-to-date case.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param mixed $value Value of the 'update_plugins' site transient.
		 *
		 * @return mixed
		 */
		public function bb_fix_plugin_details_link( $value ) {
			if ( empty( $value ) || ! is_object( $value ) ) {
				return $value;
			}

			$platform_file = $this->bb_get_platform_plugin_file();

			foreach ( array( 'response', 'no_update' ) as $key ) {
				if ( empty( $value->{$key} ) ) {
					continue;
				}

				/*
				 * A cache, staging or update-manager plugin that round-trips this
				 * transient through JSON leaves the container as an object rather than
				 * an array. Read it the same way bb_get_plugin_update_entry() does, or
				 * the two disagree about the same transient: this one would skip the
				 * entry and leave the slug wrong while that one still returned it, and
				 * a details modal carrying a download_link with a slug that names no
				 * installed directory sends core off to delete the whole update
				 * transient. Entries are objects, so assigning through the cast still
				 * reaches the entry the transient holds.
				 */
				$entries = is_object( $value->{$key} ) ? (array) $value->{$key} : $value->{$key};

				if ( ! is_array( $entries ) ) {
					continue;
				}

				foreach ( $entries as $file => $entry ) {
					// Third-party update managers are known to rewrite this transient
					// with array entries; assigning a property on one fatals on PHP 8.
					if ( ! is_object( $entry ) ) {
						continue;
					}

					$slug = dirname( (string) $file );

					// A single-file plugin has no directory to be named by.
					if ( '' === $slug || '.' === $slug ) {
						continue;
					}

					// Already naming itself correctly; nothing to normalize.
					if ( isset( $entry->slug ) && $entry->slug === $slug ) {
						continue;
					}

					/*
					 * Only this plugin and its add-ons. This runs on every admin
					 * read of the update transient, so the order of the two tests
					 * is the point: the platform is settled by a string compare,
					 * and everything else goes to bb_is_buddyboss_addon_file(),
					 * which rejects on the directory name before it touches the
					 * filesystem. An entry only gets that far if its slug is
					 * already wrong - which today means a third-party updater has
					 * rewritten it, since BuddyBoss's own feed names the directory.
					 */
					if ( $file !== $platform_file && ! $this->bb_is_buddyboss_addon_file( $file ) ) {
						continue;
					}

					/*
					 * With the slug set, every core details link resolves through
					 * plugins_api (bb_plugins_api_information, or the add-on
					 * fallback); core reads the entry's 'url' only when the slug is
					 * absent, so it is left untouched here.
					 *
					 * Naming the directory also matters beyond the link: a details
					 * modal that carries a download_link makes core run
					 * install_plugin_install_status(), which matches this transient
					 * on slug. A slug that names no installed directory drops it
					 * into a branch that deletes the whole update transient and runs
					 * a blocking wp_update_plugins() on a modal click.
					 */
					$entry->slug = $slug;
				}
			}

			return $value;
		}

		/**
		 * Whether an installed plugin file belongs to a BuddyBoss add-on.
		 *
		 * Memoized per request: this answers inside a site transient filter, which
		 * runs on every read of the plugin update data. The memo is per request
		 * only, so a plugin installed mid-request - by an auto-installer calling
		 * wp_clean_plugins_cache() - is not reclassified until the next one. That
		 * costs at most one page load of a missing details link, and nothing
		 * derived from the memo is ever persisted.
		 *
		 * Reading headers is the expensive half, so nothing reaches it without
		 * first passing bb_maybe_buddyboss_addon_slug(). That matters because
		 * get_plugins() scans the whole plugin directory and its cache group is
		 * registered non-persistent by WordPress, so a persistent object cache
		 * does not spare it: every request that got here would pay the scan
		 * again.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param string $plugin_file Plugin basename, e.g. 'buddyboss-sharing/buddyboss-sharing.php'.
		 *
		 * @return bool True when the file belongs to a BuddyBoss add-on of this plugin.
		 */
		protected function bb_is_buddyboss_addon_file( $plugin_file ) {
			static $checked = array();

			$plugin_file = (string) $plugin_file;

			if ( isset( $checked[ $plugin_file ] ) ) {
				return $checked[ $plugin_file ];
			}

			// Cheap first, filesystem second.
			if ( ! $this->bb_maybe_buddyboss_addon_slug( dirname( $plugin_file ) ) ) {
				$checked[ $plugin_file ] = false;

				return false;
			}

			if ( ! function_exists( 'get_plugins' ) ) {
				require_once ABSPATH . 'wp-admin/includes/plugin.php';
			}

			$plugins = get_plugins();

			$checked[ $plugin_file ] = isset( $plugins[ $plugin_file ] )
				&& $this->bb_is_buddyboss_addon( $plugin_file, $plugins[ $plugin_file ] );

			return $checked[ $plugin_file ];
		}

		/**
		 * Whether a plugin directory is worth reading headers for.
		 *
		 * A pre-filter, not a classifier. bb_is_buddyboss_addon() still decides,
		 * and still decides on headers - this only keeps the obviously unrelated
		 * away from the filesystem, because the caller runs on every admin read of
		 * the update transient and the header read behind it is not cached across
		 * requests. It can therefore only ever reject: a directory it accepts is
		 * put through the full test unchanged, so the set of plugins classified as
		 * add-ons cannot grow by adding this.
		 *
		 * It can, though, reject a genuine add-on: one installed into a directory
		 * that does not name BuddyBoss. That is the same rename that already
		 * costs an add-on its changelog in bb_get_addon_release_term(), and the
		 * filter below is the way back for a site that has done it.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param string $slug Plugin directory name.
		 *
		 * @return bool True when the directory could belong to a BuddyBoss add-on.
		 */
		protected function bb_maybe_buddyboss_addon_slug( $slug ) {
			$slug = (string) $slug;

			$possible = ( 0 === strpos( $slug, 'buddyboss' ) )
				|| in_array( $slug, $this->bb_get_known_buddyboss_addon_slugs(), true );

			/**
			 * Filters whether a plugin directory is worth reading headers for.
			 *
			 * Answering true only submits the directory to the full add-on test;
			 * it does not make it an add-on. Use it for a BuddyBoss add-on that
			 * has been installed into a renamed directory.
			 *
			 * @since BuddyBoss [BBVERSION]
			 *
			 * @param bool   $possible Whether the directory could belong to a BuddyBoss add-on.
			 * @param string $slug     Plugin directory name.
			 */
			return (bool) apply_filters( 'bb_possible_buddyboss_addon_slug', $possible, $slug );
		}

		/**
		 * Whether a plugin's headers mark it as a BuddyBoss add-on of this plugin.
		 *
		 * Deliberately narrow. A plugin qualifies only when it is authored by
		 * BuddyBoss AND either declares this plugin in its "Requires Plugins"
		 * header — which is what marks it an add-on distributed from BuddyBoss's
		 * own servers — or appears in bb_get_known_buddyboss_addon_slugs(), the
		 * explicit list of BuddyBoss-distributed products that predate that
		 * header. BuddyBoss also publishes standalone plugins through the
		 * WordPress.org directory — those satisfy neither test, resolve through
		 * WordPress.org perfectly well, and must never be claimed here.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param string $plugin_file Plugin basename.
		 * @param array  $plugin_data Plugin headers as returned by get_plugins().
		 *
		 * @return bool True when the plugin is a BuddyBoss add-on of this plugin.
		 */
		protected function bb_is_buddyboss_addon( $plugin_file, $plugin_data ) {
			$author = ! empty( $plugin_data['AuthorName'] ) ? $plugin_data['AuthorName'] : ( ! empty( $plugin_data['Author'] ) ? $plugin_data['Author'] : '' );

			if ( false === stripos( wp_strip_all_tags( $author ), 'buddyboss' ) ) {
				return false;
			}

			// Read the header directly: WordPress only exposes 'RequiresPlugins'
			// in plugin data from 6.5 onwards, and this has to hold on 6.0.
			$headers  = get_file_data( WP_PLUGIN_DIR . '/' . $plugin_file, array( 'RequiresPlugins' => 'Requires Plugins' ) );
			$requires = ! empty( $headers['RequiresPlugins'] ) ? array_map( 'trim', explode( ',', $headers['RequiresPlugins'] ) ) : array();

			/*
			 * Add-ons name this plugin by its canonical slug, so match that as
			 * well as the directory this copy actually lives in - the two differ
			 * once the folder is renamed. Products that predate the header are
			 * recognised from the explicit list instead.
			 */
			return in_array( $this->bb_get_platform_plugin_slug(), $requires, true )
				|| in_array( 'buddyboss-platform', $requires, true )
				|| in_array( dirname( $plugin_file ), $this->bb_get_known_buddyboss_addon_slugs(), true );
		}

		/**
		 * Get the platform's plugin basename, e.g. 'buddyboss-platform/bp-loader.php'.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @return string Plugin basename.
		 */
		protected function bb_get_platform_plugin_file() {
			/*
			 * buddypress()->basename is built from BP_PLUGIN_DIR, which PHP resolves
			 * to a realpath - so on a symlinked install it names the link target
			 * rather than the folder WordPress knows this plugin by. Handing the
			 * realpath to plugin_basename() maps it back through the realpaths
			 * WordPress registers for every active plugin; handing it the
			 * already-relative basename is a no-op that keeps the wrong name.
			 */
			return plugin_basename( BP_PLUGIN_DIR . 'bp-loader.php' );
		}

		/**
		 * Read one plugin's entry from the 'update_plugins' site transient.
		 *
		 * Note: reading this transient re-runs the site_transient_update_plugins
		 * filters, including bb_fix_plugin_details_link() - harmless, since that
		 * filter only normalizes the entry's slug.
		 *
		 * The container is normalized rather than indexed where it is found.
		 * 'response' is an array as core writes it, but a plugin that round-trips
		 * this transient through JSON - which caching, staging-sync and update
		 * manager plugins do routinely - hands back a stdClass, and indexing an
		 * object with [] is an uncaught Error, on 7.4 as much as on 8.x; it has
		 * never been a notice. isset() is no protection at all here: the
		 * dimension fetch is evaluated before the existence test, so it throws
		 * from inside isset() itself, and it throws whether or not the property
		 * is present. Casting to array restores the JSON shape to what core
		 * wrote, and disarms every other object as well - the cast reads the
		 * property table and never calls offsetExists(), so an ArrayAccess
		 * implementation whose offsetExists() throws cannot reach out of here
		 * either. A container that is not an object at all never gets indexed in
		 * the first place.
		 *
		 * What this does not buy is survivability of that transient shape, and
		 * the cast should not be read as a claim to it. WordPress core indexes
		 * the same container unguarded - wp_plugin_update_row() opens with
		 * isset( $current->response[ $file ] ) - so on the very screen this
		 * feature serves, an object-shaped 'response' fatals in core whatever
		 * this function does. What the cast buys is narrower and still worth
		 * having: this filter is not the one that fatals, and it agrees with
		 * bb_fix_plugin_details_link() about the same transient, which is what
		 * stops the pair from disagreeing over whether an entry exists.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param string $plugin_file Plugin basename, e.g. 'buddyboss-platform/bp-loader.php'.
		 *
		 * @return object|null Update entry for the plugin, or null when there is none to read.
		 */
		protected function bb_get_plugin_update_entry( $plugin_file ) {
			$update_data = get_site_transient( 'update_plugins' );

			$response = ( is_object( $update_data ) && isset( $update_data->response ) )
				? (array) $update_data->response
				: array();

			$update = isset( $response[ $plugin_file ] ) ? $response[ $plugin_file ] : null;

			// Third-party update managers are known to rewrite this transient with
			// array entries; reading a property off one warns under WP_DEBUG.
			return is_object( $update ) ? $update : null;
		}

		/**
		 * Resolve a "last updated" date for locally served plugin information.
		 *
		 * WP_Plugin_Dependencies::get_dependency_api_data() caches plugin API data
		 * for every "Requires Plugins" slug, but only keeps a cached entry that
		 * carries a non-empty 'last_updated'. Answering with an empty string makes
		 * core re-call plugins_api() - and so re-read the update transient - on
		 * every plugins.php and plugin-install.php load, so always answer with a
		 * real date.
		 *
		 * Public API. BuddyBoss add-on plugins call this from their own
		 * plugins_api handlers behind an is_callable() guard, which reports
		 * false for a method they cannot reach. Narrowing this does not fatal,
		 * then - each add-on silently drops the changelog it would have built
		 * and renders a bare link instead - and that is the reason to treat the
		 * visibility as fixed rather than a reason to relax about it: the
		 * failure is six products quietly losing a section, with nothing raised
		 * anywhere to say so. data_contract_methods() in tests/phpunit/
		 * testcases/core/class-bp-admin-release-notes.php lists the whole
		 * contract; keep it in step. It is a record, not a gate - that suite
		 * does not currently run on this branch.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param string      $plugin_file Plugin basename, e.g. 'buddyboss-platform/bp-loader.php'.
		 * @param object|null $update      Optional. Update transient entry for the plugin.
		 *
		 * @return string Last updated date in GMT 'Y-m-d H:i:s' format.
		 */
		public function bb_get_plugin_last_updated( $plugin_file, $update = null ) {
			// Third-party update managers are known to rewrite this transient with
			// array entries; reading a property off one warns under WP_DEBUG.
			if ( is_object( $update ) && ! empty( $update->last_updated ) ) {
				return (string) $update->last_updated;
			}

			$path  = WP_PLUGIN_DIR . '/' . $plugin_file;
			$mtime = file_exists( $path ) ? filemtime( $path ) : false;

			return gmdate( 'Y-m-d H:i:s', false !== $mtime ? $mtime : time() );
		}

		/**
		 * Resolve the 'download_link' for locally served plugin information.
		 *
		 * Answering with an empty string is correct and must stay that way. Core
		 * reads download_link on exactly one path - install_plugin_install_status()
		 * reporting 'install', which drives update.php?action=install-plugin - and
		 * that status is unreachable for anything already installed, because the
		 * plugin's own directory is what takes core out of that branch. A pending
		 * update instead matches the update transient on slug, reports
		 * 'update_available' and sends the user to action=upgrade-plugin, which
		 * downloads the transient's own 'package'. An installed plugin that is up
		 * to date reports 'latest_installed' and offers no button at all. So the
		 * licensed package this plugin is distributed with is never sourced from
		 * here, and leaving this empty cannot break an update or produce a wrong
		 * download. Do not "fix" an empty value by synthesizing a URL: the only
		 * thing that would achieve is handing out a download link on a path core
		 * never asks for one.
		 *
		 * The capability test is defence in depth rather than a live leak. On the
		 * paths above core never echoes the value, but plugins_api() is a public
		 * filter callable from any context, the front end included, and the value
		 * on hand is a licensed package URL - so it is only ever assembled for
		 * someone who could install or update with it anyway.
		 *
		 * The pair is core's own: install_plugin_information() gates the modal's
		 * footer button on 'install_plugins' OR 'update_plugins', so testing only
		 * the first would withhold the value from a user core would have offered
		 * "Update Now" to, and leave that footer empty for them. The two caps are
		 * granted and revoked together in every core configuration - multisite
		 * restricts both to super admins, DISALLOW_FILE_MODS removes both - so
		 * they only come apart under a role editor, which is exactly the case
		 * worth matching core on. Widening costs nothing at rest either:
		 * bb_strip_dependency_api_data() removes the value where it would
		 * otherwise have been persisted, so this decides who is handed it in a
		 * response, not what is written to the database.
		 *
		 * Public API. BuddyBoss add-on plugins call this from their own
		 * plugins_api handlers behind an is_callable() guard, which reports
		 * false for a method they cannot reach. Narrowing this does not fatal,
		 * then - each add-on silently drops the changelog it would have built
		 * and renders a bare link instead - and that is the reason to treat the
		 * visibility as fixed rather than a reason to relax about it: the
		 * failure is six products quietly losing a section, with nothing raised
		 * anywhere to say so. data_contract_methods() in tests/phpunit/
		 * testcases/core/class-bp-admin-release-notes.php lists the whole
		 * contract; keep it in step. It is a record, not a gate - that suite
		 * does not currently run on this branch.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param string $package Package URL from the update transient, if any.
		 *
		 * @return string Package URL, or empty string.
		 */
		public function bb_get_plugin_download_link( $package ) {
			if (
				empty( $package ) ||
				( ! current_user_can( 'install_plugins' ) && ! current_user_can( 'update_plugins' ) )
			) {
				return '';
			}

			return (string) $package;
		}

		/**
		 * Keep the licensed package URL out of the plugin dependency cache.
		 *
		 * WP_Plugin_Dependencies::get_dependency_api_data() calls plugins_api()
		 * for every "Requires Plugins" slug - which is this plugin, for each
		 * add-on - and stores the whole answer in a site transient with no
		 * expiration. Before this plugin answered for itself that call returned a
		 * WP_Error and nothing was cached; now it returns a real payload, and
		 * 'download_link' on it is a signed, licence-bearing package URL whose
		 * path also names the licence bundle. Nothing reads it back: the two
		 * places core renders this cache
		 * (WP_Plugins_List_Table::get_dependency_view_details_link() and
		 * WP_Plugin_Install_List_Table) use only 'name' and 'version'. So it is
		 * stored forever, read never - which is the whole of the problem, and
		 * removing it costs nothing.
		 *
		 * Done here rather than by withholding the value in
		 * bb_get_plugin_download_link(), because nothing available inside
		 * plugins_api() distinguishes this caller reliably. The two callers are
		 * separated by request, not by arguments - install_plugin_information()
		 * is hooked to install_plugins_pre_{$tab} and exits before
		 * plugin-install.php reaches WP_Plugin_Dependencies::initialize() - and
		 * core carries a TODO on exactly that structure. Filtering the write
		 * instead depends on none of it, and leaves the value intact for all
		 * three paths that genuinely consume it: the modal footer,
		 * update.php?action=install-plugin and wp_ajax_install_plugin.
		 *
		 * 'sections' goes too. It is the changelog HTML, it is never rendered
		 * from this cache either, and on the modal request - the only caller that
		 * fetches notes - execution exits before this transient is written, so
		 * the key is only ever present carrying data nothing asked for.
		 *
		 * Every BuddyBoss entry is covered, not only this plugin's own. Each
		 * add-on in the suite now answers plugins_api for its own slug, and
		 * bb_plugins_api_addon_fallback() answers for any that does not, so the
		 * payload carrying a licensed package URL is no longer this plugin's
		 * alone. Nothing has to name an add-on as a dependency today for that to
		 * matter: this store never expires, so a single entry written once -
		 * after a product declares "Requires Plugins: buddyboss-<add-on>", or
		 * after someone copies an add-on's example plugin into wp-content/plugins
		 * - is a credential left in the database until somebody deletes the row
		 * by hand. Covering the whole suite here costs one array walk and spares
		 * six add-ons an identical fix each.
		 *
		 * Scope is decided by bb_get_installed_buddyboss_addon(), which is the
		 * same narrow test the add-on fallback answers on: BuddyBoss-authored,
		 * and either naming this plugin in "Requires Plugins" or listed in
		 * bb_get_known_buddyboss_addon_slugs(). A slug that is not this plugin
		 * and does not pass that test is another plugin's entry and is left
		 * exactly as it was handed over. That test does reach the filesystem,
		 * but only after bb_maybe_buddyboss_addon_slug() has rejected on the
		 * directory name, and then only as far as an author-header read for a
		 * directory that survived it. This filter also runs on the write rather
		 * than the read - core writes this transient once per dependency slug it
		 * newly resolves, not once per page load.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param mixed $value Value of the 'wp_plugin_dependencies_plugin_data' site transient.
		 *
		 * @return mixed
		 */
		public function bb_strip_dependency_api_data( $value ) {
			if ( ! is_array( $value ) ) {
				return $value;
			}

			$platform_slug = $this->bb_get_platform_plugin_slug();

			foreach ( $value as $slug => $entry ) {
				// Core stores each entry as (array) $information; anything else was
				// not written from here and is left exactly as it was handed over.
				if ( ! is_array( $entry ) ) {
					continue;
				}

				// Cheap compare first, then the filesystem test - and only for a
				// slug that could plausibly be ours. See the scope note above.
				if (
					$slug !== $platform_slug &&
					! $this->bb_get_installed_buddyboss_addon( $slug )
				) {
					continue;
				}

				unset( $value[ $slug ]['download_link'], $value[ $slug ]['sections'] );
			}

			return $value;
		}

		/**
		 * Get the platform's plugin directory slug, e.g. 'buddyboss-platform'.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @return string Plugin directory slug.
		 */
		protected function bb_get_platform_plugin_slug() {
			return dirname( $this->bb_get_platform_plugin_file() );
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

		/**
		 * Serve plugin information for BuddyBoss Platform locally.
		 *
		 * Handles the plugin-information modal opened by the plugin row's
		 * "View details" link and the update notice's "View version x.x.x
		 * details" link, which otherwise query WordPress.org and return
		 * "Plugin not found." The changelog section is fetched from the
		 * buddyboss.com release notes API when available and always links to
		 * the full release notes page.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param false|object|array $result The result object or array. Default false.
		 * @param string             $action The type of information being requested from the Plugin Installation API.
		 * @param object             $args   Plugin API arguments.
		 *
		 * @return false|object Plugin information for BuddyBoss Platform, or the original result.
		 */
		public function bb_plugins_api_information( $result, $action, $args ) {
			/*
			 * install_plugin_information() passes $_REQUEST['plugin'] through
			 * wp_unslash() and nothing else, so the slug arrives with whatever
			 * type the request gave it - ?plugin[]=x makes it an array. Refuse
			 * anything that is not a string: a non-string names no plugin this
			 * could answer for, and core degrades on the same input rather than
			 * failing, so declining here leaves that behaviour intact.
			 */
			if (
				'plugin_information' !== $action ||
				empty( $args->slug ) ||
				! is_string( $args->slug ) ||
				false !== $result
			) {
				return $result;
			}

			$plugin_file = $this->bb_get_platform_plugin_file();

			if ( dirname( $plugin_file ) !== $args->slug ) {
				return $result;
			}

			$new_version = BP_PLATFORM_VERSION;
			$package     = '';
			$update      = $this->bb_get_plugin_update_entry( $plugin_file );

			if ( ! empty( $update->new_version ) ) {
				$new_version = $update->new_version;
				$package     = ! empty( $update->package ) ? $update->package : '';
			}

			$state     = 'skipped';
			$changelog = $this->bb_should_fetch_release_notes( $args )
				? $this->bb_get_release_notes_html( $new_version, 'releases-platform', $state )
				: '';

			/*
			 * Link text names the version the URL actually resolves to, not the one
			 * the update feed reported: bb_get_release_notes_page_url() reduces
			 * 3.4.4-beta1 to the 3.4.4 release page, and a link reading "version
			 * 3.4.4-beta1" that opens /3-4-4/ misstates where it goes.
			 *
			 * With no notes to show, there is no evidence a page for this version
			 * exists — the fetch that would have found one is what just came back
			 * empty — so the link falls back to the releases archive, which always
			 * does, rather than sending the user to a likely 404.
			 */
			$linked_version = $this->bb_normalize_release_version( $new_version );

			if ( '' !== $changelog && '' !== $linked_version ) {
				$release_url  = $this->bb_get_release_notes_page_url( $linked_version );
				$release_text = sprintf(
					/* translators: %s: version number. */
					__( 'View the full release notes for version %s on buddyboss.com', 'buddyboss' ),
					$linked_version
				);
			} else {
				$release_url  = $this->bb_get_release_notes_page_url();
				$release_text = __( 'View all release notes on buddyboss.com', 'buddyboss' );
			}

			$information = array(
				// Not translated, and tags stripped: core echoes $api->name straight
				// into the modal's <h2> without escaping it, and it is a product name.
				'name'          => 'BuddyBoss Platform',
				'slug'          => $args->slug,

				/*
				 * Sanitized, not normalized, and the difference matters: see
				 * bb_sanitize_plugin_version(). The short of it is that this key is
				 * both printed and fed to version_compare(), and the value that is
				 * right for the release link beside it - 3.4.4 for a 3.4.4-beta1
				 * build - is the wrong value here, because it compares greater than
				 * what is installed.
				 *
				 * Greater is the one answer install_plugin_install_status() cannot
				 * absorb: it runs delete_site_transient( 'update_plugins' ) plus a
				 * blocking wp_update_plugins() and recurses, on every modal open.
				 * Two paths reach that comparison and they need different things.
				 *
				 * No update pending: core's lookup scans only the transient's
				 * 'response' container, and an up-to-date plugin sits in
				 * 'no_update', so the lookup cannot match and the comparison always
				 * runs. Nothing filters this path. The only defence is that the
				 * value below still names the release that is installed, which is
				 * exactly what sanitizing preserves and normalizing destroyed.
				 * bb_fix_plugin_details_link() does not help here and must not be
				 * cited as though it did.
				 *
				 * An update is pending: the lookup matches on slug, reports
				 * 'update_available' and returns before comparing - but only if the
				 * entry carries the right slug, which is what
				 * bb_fix_plugin_details_link() does exist to guarantee. See the note
				 * on bb_get_installed_buddyboss_addon() for why the two predicates
				 * behind that have to stay identical.
				 *
				 * Residual: this reports BP_PLATFORM_VERSION where core reads the
				 * plugin's Version header. They are the same string in every shipped
				 * build; a build where they diverge would land in the branch above.
				 */
				'version'       => $this->bb_sanitize_plugin_version( $new_version ),
				'author'        => '<a href="https://buddyboss.com/" target="_blank" rel="noopener noreferrer">BuddyBoss</a>',
				'homepage'      => 'https://buddyboss.com/',
				'last_updated'  => $this->bb_get_plugin_last_updated( $plugin_file, $update ),
				'sections'      => array(
					'description' => '<p>' . esc_html__( 'The BuddyBoss Platform adds community features to WordPress. Member Profiles, Activity Feeds, Direct Messaging, Notifications, and more!', 'buddyboss' ) . '</p>',
					'changelog'   => $this->bb_build_changelog_section( $changelog, $release_url, $release_text, $state ),
				),
				'download_link' => $this->bb_get_plugin_download_link( $package ),
			);

			/**
			 * Filters the locally served plugin information for BuddyBoss Platform.
			 *
			 * Return plain text for 'name' and 'version'. install_plugin_information()
			 * prints both without escaping - 'name' into the modal's <h2> with no
			 * filtering at all, 'version' through wp_kses() with the installer's own
			 * short allowlist, which still passes links, images and class attributes
			 * straight to the page. Markup returned here is markup in wp-admin.
			 *
			 * @since BuddyBoss [BBVERSION]
			 *
			 * @param array  $information Plugin information served to the plugin-information modal.
			 * @param string $new_version Version number the information describes.
			 * @param object $args        Plugin API arguments.
			 */
			$information = apply_filters( 'bb_platform_plugins_api_information', $information, $new_version, $args );

			return (object) $information;
		}

		/**
		 * Whether a plugins_api() caller is the one that renders release notes.
		 *
		 * Two tests, and only one of them is a reading of the API contract.
		 *
		 * The contract half: 'fields' is an opt-out map, so a caller that does not
		 * mention 'sections' is taking the default, and the default is true. Only
		 * an explicit false means the sections are unwanted.
		 *
		 * The other half is a fingerprint, and is named as one rather than dressed
		 * up as a contract. Reading 'fields' alone does not separate the modal from
		 * the rest: WP_Plugin_Dependencies::get_dependency_api_data() asks for
		 * short_description and icons and mentions no 'sections' at all, so the
		 * contract-correct answer for it is "yes, sections wanted" - and it runs on
		 * every plugins.php and plugin-install.php load, which would put a blocking
		 * HTTP request into an ordinary page render for data nothing displays.
		 * WP_Plugin_Install_List_Table::get_dependencies_notice() passes no fields
		 * at all and is likewise a page render, not the modal. IFRAME_REQUEST is
		 * what actually separates them: wp-admin/plugin-install.php defines it only
		 * for tab=plugin-information, which is the single screen that renders a
		 * changelog.
		 *
		 * Fingerprints go stale, so this one is used only where being wrong is
		 * survivable. A false negative here costs a changelog section - the modal
		 * still renders, still links to the full notes - which is why it gates this
		 * and nothing that matters more.
		 *
		 * Public API. BuddyBoss add-on plugins call this from their own
		 * plugins_api handlers behind an is_callable() guard, which reports
		 * false for a method they cannot reach. Narrowing this does not fatal,
		 * then - each add-on silently drops the changelog it would have built
		 * and renders a bare link instead - and that is the reason to treat the
		 * visibility as fixed rather than a reason to relax about it: the
		 * failure is six products quietly losing a section, with nothing raised
		 * anywhere to say so. data_contract_methods() in tests/phpunit/
		 * testcases/core/class-bp-admin-release-notes.php lists the whole
		 * contract; keep it in step. It is a record, not a gate - that suite
		 * does not currently run on this branch.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param object $args Plugin API arguments.
		 *
		 * @return bool True when release notes should be fetched for this caller.
		 */
		public function bb_should_fetch_release_notes( $args ) {
			$fields = isset( $args->fields ) ? (array) $args->fields : array();

			$wants_sections = ! isset( $fields['sections'] ) || $fields['sections'];

			return $wants_sections && defined( 'IFRAME_REQUEST' ) && IFRAME_REQUEST;
		}

		/**
		 * Build the changelog section shown in the plugin-information modal.
		 *
		 * The section is never empty: when no notes came back it says why, rather
		 * than leaving a lone link that looks like the whole changelog. Without
		 * that, an unpublished or not-yet-written release reads as though the
		 * modal loaded correctly and the release simply had nothing to report.
		 *
		 * Why the reason is carried this far rather than collapsed into "no notes":
		 * the three ways of having none are three different things to tell someone.
		 * A release with no notes published yet is not broken and never will be
		 * fixed by waiting. A fetch that failed might work on the next try. And a
		 * caller that lost the fetch lock is looking at notes that another request
		 * is loading successfully at that moment, so telling it anything failed
		 * would be false. All three are reachable; saying "could not be loaded" for
		 * all three was wrong for two of them.
		 *
		 * Public API. BuddyBoss add-on plugins call this from their own
		 * plugins_api handlers behind an is_callable() guard, which reports
		 * false for a method they cannot reach. Narrowing this does not fatal,
		 * then - each add-on silently drops the changelog it would have built
		 * and renders a bare link instead - and that is the reason to treat the
		 * visibility as fixed rather than a reason to relax about it: the
		 * failure is six products quietly losing a section, with nothing raised
		 * anywhere to say so. data_contract_methods() in tests/phpunit/
		 * testcases/core/class-bp-admin-release-notes.php lists the whole
		 * contract; keep it in step. It is a record, not a gate - that suite
		 * does not currently run on this branch.
		 *
		 * $link_text is plain, unescaped text, and is escaped here. So is
		 * bb_get_addon_changelog_section()'s, which wraps this - one rule for
		 * both, because two public methods taking link text under opposite
		 * contracts is a trap that cannot be undone once add-ons ship against
		 * it. This one used to interpolate raw, on the grounds that its only
		 * callers were in this class and handed it escaped strings; that stopped
		 * being true when add-ons began calling it, and three of them wrote
		 * comments refusing to adopt the convention rather than propagate it.
		 *
		 * Escaping here rather than trusting callers is the direction that fails
		 * safely. A caller that escapes anyway renders "&amp;" where it meant
		 * "&" - visible, reported, fixed. A caller that forgets puts remote text
		 * into wp-admin markup, and nothing says so. There is no runtime way to
		 * tell the two apart, so the default has to be the one whose failure is
		 * loud.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param string $notes     Sanitized release notes HTML; empty when none
		 *                          could be fetched.
		 * @param string $url       URL the trailing link points at.
		 * @param string $link_text Plain, unescaped text for the trailing link;
		 *                          escaped here. Do not pass esc_html() output.
		 * @param string $state     Why there are no notes, when there are none:
		 *                          'failed' for a fetch that did not complete,
		 *                          'empty' for a release with nothing published,
		 *                          'locked' for a fetch another request is running,
		 *                          'skipped' for a caller that asked for no
		 *                          sections and gets the link alone.
		 *
		 * @return string Changelog section HTML.
		 */
		public function bb_build_changelog_section( $notes, $url, $link_text, $state = 'failed' ) {
			$section = '';

			if ( '' !== $notes ) {
				$section .= $notes;
			} else {
				$notice = '';

				switch ( (string) $state ) {
					case 'empty':
						$notice = esc_html__( 'No release notes have been published for this version yet.', 'buddyboss' );
						break;
					case 'locked':
						$notice = esc_html__( 'The release notes are still loading. Reload this window in a moment to see them.', 'buddyboss' );
						break;
					case 'skipped':
						break;
					default:
						$notice = esc_html__( 'The release notes for this version could not be loaded right now.', 'buddyboss' );
						break;
				}

				if ( '' !== $notice ) {
					$section .= '<p>' . $notice . '</p>';
				}
			}

			/*
			 * Core's $plugins_allowedtags keeps span[class] but drops rel, and then
			 * links_add_target() forces target="_blank" on every link in the section
			 * regardless. The new tab is therefore a certainty rather than a choice,
			 * so it is announced. 'screen-reader-text' is styled inside the iframe:
			 * iframe_header() enqueues 'colors', which depends on 'wp-admin', which
			 * bundles common.css.
			 */
			$section .= sprintf(
				'<p><a href="%1$s" target="_blank" rel="noopener noreferrer">%2$s<span class="screen-reader-text"> %3$s</span></a></p>',
				esc_url( $url ),
				esc_html( (string) $link_text ),
				esc_html__( '(opens in a new tab)', 'buddyboss' )
			);

			return $section;
		}

		/**
		 * Reduce an update-feed version to the release it belongs to.
		 *
		 * The version comes from the update feed, so it is neither trusted nor
		 * guaranteed to be the plain number a release page is filed under. The
		 * value is trimmed, an optional leading 'v' is dropped, and only the
		 * leading run of digits and dots is kept - so a suffixed version
		 * (e.g. 3.4.4-beta1) truncates to its base release (3.4.4) instead of
		 * splicing into 3.4.41, and a mangled value cannot alter a URL path or
		 * inject query arguments. Stray dots are then tidied so a value such as
		 * '3.4.1.' cannot become the slug '3-4-1-'.
		 *
		 * Whatever survives has to look like a version before it is handed back:
		 * two numeric segments or more, separated by dots. That last test is
		 * what stops a separator this does not understand from being answered
		 * confidently rather than not at all. '3,4,4' truncates to '3', which
		 * without the test would send a user to a real-looking /3/ release page
		 * that is not their release - a wrong answer is worse here than none,
		 * because every caller already handles none: the fetchers bail early and
		 * the link falls back to the releases archive, which always resolves.
		 *
		 * The price of that strictness is that a single-segment version ('3')
		 * also comes back empty. No BuddyBoss product has shipped one, and a
		 * bare integer is no more resolvable to a release page than '3,4,4' was.
		 *
		 * Every caller that turns a version into a URL, a query argument, a
		 * cache key or link text goes through this, so the link a user reads
		 * always names the same release the link actually opens.
		 *
		 * Public API. BuddyBoss add-on plugins call this from their own
		 * plugins_api handlers behind an is_callable() guard, which reports
		 * false for a method they cannot reach. Narrowing this does not fatal,
		 * then - each add-on silently drops the changelog it would have built
		 * and renders a bare link instead - and that is the reason to treat the
		 * visibility as fixed rather than a reason to relax about it: the
		 * failure is six products quietly losing a section, with nothing raised
		 * anywhere to say so. data_contract_methods() in tests/phpunit/
		 * testcases/core/class-bp-admin-release-notes.php lists the whole
		 * contract; keep it in step. It is a record, not a gate - that suite
		 * does not currently run on this branch.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param string $version Version number from the update feed.
		 *
		 * @return string Normalized version number, or an empty string.
		 */
		public function bb_normalize_release_version( $version ) {
			// Nothing scalar to read: an array or object cannot be a version.
			if ( ! is_scalar( $version ) ) {
				return '';
			}

			/*
			 * Bound the input before any pattern runs against it. The value ends
			 * up in a query string, a cache key and a URL path, so a feed that
			 * answers with a megabyte of digits and dots must not be carried into
			 * any of them. 128 is longer than any real version by an order of
			 * magnitude.
			 *
			 * Trimming first is what makes a padded value work at all: the
			 * extraction below is anchored, so a single leading space used to
			 * yield an empty string and silently disable the whole fetch.
			 */
			$version = substr( trim( (string) $version ), 0, 128 );

			// A feed that files its releases as 'v2.0' is naming 2.0.
			$version = (string) preg_replace( '/^[vV]/', '', $version );

			preg_match( '/^[0-9.]+/', $version, $matches );

			$version = isset( $matches[0] ) ? $matches[0] : '';

			// Collapse runs of dots ('3..4') and drop leading/trailing ones.
			$version = trim( (string) preg_replace( '/\.{2,}/', '.', $version ), '.' );

			return preg_match( '/^[0-9]+(?:\.[0-9]+)+$/', $version ) ? $version : '';
		}

		/**
		 * Make a version safe to print without changing which release it names.
		 *
		 * The sibling of bb_normalize_release_version(), and deliberately not the
		 * same function. That one answers "which release page does this belong
		 * to", and reducing 3.4.4-beta1 to 3.4.4 is the right answer to that
		 * question. This one answers "what is installed", where the suffix is the
		 * whole point and dropping it produces a value that names a different
		 * release from the one on disk.
		 *
		 * The distinction is load-bearing, because the 'version' key is not
		 * display-only. install_plugin_information() prints it - core applies
		 * wp_kses() with its own short allowlist and then echoes the result
		 * unescaped, and that allowlist still passes links, images and class
		 * attributes - but install_plugin_install_status() also feeds it to
		 * version_compare() against the installed plugin's own Version header.
		 * A value that is neither equal to nor lower than the installed one
		 * sends core into a branch that runs delete_site_transient(
		 * 'update_plugins' ) and a blocking wp_update_plugins(), then recurses.
		 * Handing that comparison a version with its pre-release suffix removed
		 * is exactly how to land there: '2.0.3' against an installed
		 * '2.0.3-beta2' is greater, not equal and not lower.
		 *
		 * So the two jobs are separated rather than compromised between. Removing
		 * every character a version cannot contain is what makes printing safe -
		 * with no '<' left there is no tag for kses to pass through - while '-',
		 * '+', '_' and letters survive, so version_compare() still orders a
		 * pre-release below its release and the comparison above resolves the way
		 * the installed copy deserves.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param string $version Version number from a plugin header or update feed.
		 *
		 * @return string Version safe to print and to compare, or an empty string.
		 */
		protected function bb_sanitize_plugin_version( $version ) {
			// Nothing scalar to read: an array or object cannot be a version.
			if ( ! is_scalar( $version ) ) {
				return '';
			}

			// Bounded for the same reason bb_normalize_release_version() bounds:
			// the value can arrive from a remote feed, and it is printed.
			$version = substr( trim( (string) $version ), 0, 128 );

			return (string) preg_replace( '/[^0-9A-Za-z.+_-]/', '', $version );
		}

		/**
		 * Get the buddyboss.com release notes page URL.
		 *
		 * Public API. BuddyBoss add-on plugins call this from their own
		 * plugins_api handlers behind an is_callable() guard, which reports
		 * false for a method they cannot reach. Narrowing this does not fatal,
		 * then - each add-on silently drops the changelog it would have built
		 * and renders a bare link instead - and that is the reason to treat the
		 * visibility as fixed rather than a reason to relax about it: the
		 * failure is six products quietly losing a section, with nothing raised
		 * anywhere to say so. data_contract_methods() in tests/phpunit/
		 * testcases/core/class-bp-admin-release-notes.php lists the whole
		 * contract; keep it in step. It is a record, not a gate - that suite
		 * does not currently run on this branch.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param string $version   Optional. Version number to link directly to;
		 *                          empty for the release notes archive.
		 * @param string $page_base Optional. Release notes archive base URL, so
		 *                          BuddyBoss add-on plugins can reuse this helper
		 *                          for their own release pages; defaults to the
		 *                          Platform releases archive.
		 *
		 * @return string Release notes page URL.
		 */
		public function bb_get_release_notes_page_url( $version = '', $page_base = '' ) {
			$url     = ! empty( $page_base ) ? trailingslashit( $page_base ) : 'https://buddyboss.com/resources/buddyboss-platform-releases/';
			$version = $this->bb_normalize_release_version( $version );

			if ( '' !== $version ) {
				$url .= str_replace( '.', '-', $version ) . '/';
			}

			return $url;
		}

		/**
		 * Fetch the release notes HTML for a version from buddyboss.com.
		 *
		 * Queries the public releases-platform REST endpoint and caches the
		 * result. A failed or empty response is cached briefly so the Plugins
		 * screen never hammers the remote site, and the caller falls back to a
		 * plain release notes link.
		 *
		 * Public API. BuddyBoss add-on plugins call this from their own
		 * plugins_api handlers behind an is_callable() guard, which reports
		 * false for a method they cannot reach. Narrowing this does not fatal,
		 * then - each add-on silently drops the changelog it would have built
		 * and renders a bare link instead - and that is the reason to treat the
		 * visibility as fixed rather than a reason to relax about it: the
		 * failure is six products quietly losing a section, with nothing raised
		 * anywhere to say so. data_contract_methods() in tests/phpunit/
		 * testcases/core/class-bp-admin-release-notes.php lists the whole
		 * contract; keep it in step. It is a record, not a gate - that suite
		 * does not currently run on this branch.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param string $version   Version number, e.g. '3.4.4'.
		 * @param string $rest_base Optional. Releases post type REST base on
		 *                          buddyboss.com/resources, so BuddyBoss add-on
		 *                          plugins can reuse this helper for their own
		 *                          release feeds; defaults to the Platform
		 *                          releases post type.
		 * @param string $state     Optional. Set to why there are no notes, when
		 *                          there are none: 'ok', 'empty', 'failed' or
		 *                          'locked'. See bb_build_changelog_section().
		 *                          Passed by reference.
		 *
		 * @return string Sanitized release notes HTML, or empty string if unavailable.
		 */
		public function bb_get_release_notes_html( $version, $rest_base = 'releases-platform', &$state = null ) {
			/*
			 * 'skipped' until something is actually asked of the remote. The
			 * bail below is reached when the version or the feed name could not
			 * be resolved, and no request is made - so reporting 'empty' there
			 * would render "no release notes have been published for this
			 * version yet", a statement about the release that nothing checked
			 * and that is not knowable from here.
			 */
			$state     = 'skipped';
			$version   = $this->bb_normalize_release_version( $version );
			$rest_base = sanitize_key( str_replace( '/', '', (string) $rest_base ) );

			/*
			 * Bail on an empty version rather than requesting '?slug='. WordPress
			 * parses an empty slug to an empty post_name__in, drops the filter
			 * entirely and answers with the newest releases - which would render
			 * some other version's notes under this version's heading, and cache
			 * them for half a day.
			 */
			if ( '' === $version || '' === $rest_base ) {
				return '';
			}

			// Past the bail, so a request is going to be made or a cached answer
			// read. From here 'empty' is a finding rather than an assumption.
			$state = 'empty';

			$cache_key = 'bb_release_notes_' . md5( $rest_base . '_' . $version );
			$cached    = get_site_transient( $cache_key );

			if ( false !== $cached ) {
				return $this->bb_read_release_notes_cache( $cached, $state );
			}

			$lock_key = $this->bb_claim_release_notes_lock( $cache_key );

			if ( '' === $lock_key ) {
				$state = 'locked';

				return '';
			}

			$endpoint = add_query_arg(
				array(
					'slug'    => str_replace( '.', '-', $version ),
					'_fields' => 'title,content,link,release_fields',
				),
				'https://buddyboss.com/resources/wp-json/wp/v2/' . $rest_base
			);

			$response = wp_remote_get( esc_url_raw( $endpoint ), $this->bb_release_notes_request_args() );

			if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
				$state = 'failed';

				$this->bb_cache_release_notes( $cache_key, '', $state );
				delete_site_transient( $lock_key );

				return '';
			}

			$items = json_decode( wp_remote_retrieve_body( $response ), true );
			$item  = ( is_array( $items ) && ! empty( $items[0] ) && is_array( $items[0] ) ) ? $items[0] : array();
			$html  = ! empty( $item ) ? $this->bb_extract_release_notes_html( $item ) : '';

			// The release post's own permalink is what any relative link in its body
			// is relative to, so it makes the most accurate base; 'link' is asked for
			// in _fields above, but fall back when the remote omits it.
			$base = ! empty( $item['link'] ) && is_string( $item['link'] ) ? $item['link'] : '';

			/*
			 * Record the failure before attempting the work, and replace it with
			 * the result once the work is done. Preparing the markup is the one
			 * step here that a hostile payload can make expensive, and a request
			 * killed part way through it writes nothing - so with the order the
			 * other way round the next page load repeats the same stall, and the
			 * one after that, indefinitely. Written pessimistically, a fetch that
			 * dies costs an hour of the plain release notes link instead.
			 *
			 * 'failed' is the honest label for that pessimistic write, because the
			 * only way it survives to be read is a request that did not finish.
			 * Whichever of the two outcomes below is reached replaces it.
			 */
			$this->bb_cache_release_notes( $cache_key, '', 'failed' );

			$html = $this->bb_prepare_release_notes_html( $html, $base );

			$state = '' !== $html ? 'ok' : 'empty';

			$this->bb_cache_release_notes( $cache_key, $html, $state );
			delete_site_transient( $lock_key );

			return $html;
		}

		/**
		 * Store release notes and the reason there are none, if there are none.
		 *
		 * The reason has to outlive the request that discovered it. A release with
		 * nothing published and a fetch that failed both cache an empty string, and
		 * for the twelve hours or the hour that follows, every reader of that cache
		 * entry would otherwise have to guess which it was - and guessing wrong
		 * tells someone their notes failed to load when the release simply has
		 * none. So the reason is cached with the markup.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param string $cache_key Site transient key.
		 * @param string $html      Sanitized release notes HTML, or empty string.
		 * @param string $state     Why there are no notes; see bb_build_changelog_section().
		 *
		 * @return void
		 */
		protected function bb_cache_release_notes( $cache_key, $html, $state ) {
			$payload = array(
				'html'  => (string) $html,
				'state' => (string) $state,
			);

			set_site_transient( $cache_key, $payload, '' !== $html ? 12 * HOUR_IN_SECONDS : HOUR_IN_SECONDS );
		}

		/**
		 * Read a cached release notes entry, in either shape it can be in.
		 *
		 * Earlier builds cached the markup as a bare string. Those entries are
		 * still live for up to twelve hours after an upgrade, so they are read
		 * rather than discarded: a bare string carries no reason, and the only
		 * reason it can safely be given is the one that claims least.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param mixed  $cached Cached value, already known not to be false.
		 * @param string $state  Set to the cached reason. Passed by reference.
		 *
		 * @return string Sanitized release notes HTML, or empty string.
		 */
		protected function bb_read_release_notes_cache( $cached, &$state ) {
			if ( is_array( $cached ) ) {
				$html  = isset( $cached['html'] ) && is_string( $cached['html'] ) ? $cached['html'] : '';
				$state = isset( $cached['state'] ) && is_string( $cached['state'] ) ? $cached['state'] : 'empty';

				return $html;
			}

			$html  = is_string( $cached ) ? $cached : '';
			$state = '' !== $html ? 'ok' : 'empty';

			return $html;
		}

		/**
		 * Arguments for a release notes HTTP request.
		 *
		 * Deliberately short: these requests run synchronously inside the
		 * plugin-information iframe, so the wait is time a user spends looking at
		 * an unpainted modal. Release notes are a nicety - the modal always renders
		 * a link to the full notes - so failing fast beats stalling.
		 *
		 * The timeout is the whole worst case for one caller only. bb_get_release_
		 * notes_html(), which is how this plugin fetches its own notes, makes a
		 * single request, so five seconds is the most it can cost. The add-on path
		 * does not work that way: bb_get_addon_release_notes_html() resolves a term,
		 * then searches for the release, then fetches its body, and all three are
		 * sequential and hold the same lock. Three requests, so fifteen seconds, on
		 * the first fetch for a term; ten thereafter, because the term ID caches for
		 * a week. Each step gates the next, so a failure costs less than a success,
		 * not more - but no step ever runs in parallel with another, and the lock
		 * TTL has to stay above the total rather than above this number.
		 *
		 * The body is capped as well as the wait. Without a cap the remote alone
		 * decides how much this request buffers into memory and how much markup
		 * the transforms below walk, and neither is a decision to hand to the far
		 * end of a network connection. A truncated body is not parsed as a partial
		 * document: it fails json_decode(), so an oversized response resolves to
		 * the same "no notes" answer as an unreachable one.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @return array Arguments for wp_remote_get().
		 */
		protected function bb_release_notes_request_args() {
			return array(
				'timeout'             => 5,
				'limit_response_size' => $this->bb_release_notes_max_bytes(),
			);
		}

		/**
		 * Largest release notes payload that is worth processing.
		 *
		 * Two jobs: it caps what wp_remote_get() buffers, and it caps what the
		 * markup transforms walk. The number is chosen to sit far above any real
		 * release notes page - the longest Platform release runs well under a
		 * tenth of this once rendered - and far below the size at which walking
		 * the markup costs enough to notice, so the only documents it turns away
		 * are ones no release ever produces.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @return int Maximum payload size in bytes.
		 */
		protected function bb_release_notes_max_bytes() {
			return 256 * KB_IN_BYTES;
		}

		/**
		 * Claim a short-lived lock around a release notes fetch.
		 *
		 * Without one, every admin opening the details modal while the cache is
		 * cold pays the full remote stall in parallel. With it only the first
		 * caller fetches; the rest fall back to the plain release notes link and
		 * pick up the cached notes on their next view. Deliberately not written to
		 * the negative cache, so a caller that loses the race is not held off for
		 * an hour.
		 *
		 * Not atomic - WordPress offers no atomic transient add - but losing the
		 * race only costs a duplicate request, never a wrong answer.
		 *
		 * The window this reports 'locked' for is the network phase, not the
		 * whole fetch. Both callers read the cache before they reach this, and
		 * both write a pessimistic 'failed' entry once the network work is done
		 * and before the markup transforms run - so a request arriving during
		 * those transforms is answered from that entry and told the notes could
		 * not be loaded, about a fetch that is in fact about to succeed. The
		 * transforms are milliseconds against a five-second request, so the
		 * mislabelled window is the small one, and the alternative - deferring
		 * the pessimistic write - is what lets a fetch that dies mid-transform
		 * stall every page load after it.
		 *
		 * The lifetime has to outlast the work it guards, or it stops guarding
		 * anything: the add-on path makes three requests in series, so at the
		 * request timeout its worst case is already past a minute once the
		 * transforms are counted, and a lock that expires mid-fetch lets exactly
		 * the pile-up it exists to prevent start anyway. A lock left stranded by
		 * a fetch that died is harmless in return, because the caller writes its
		 * negative cache entry before doing any work - so callers that arrive
		 * afterwards are answered from the cache and never reach this at all.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param string $cache_key Cache key the fetch will populate.
		 *
		 * @return string Lock key to release when the fetch finishes, or an empty
		 *                string when another request already holds the lock.
		 */
		protected function bb_claim_release_notes_lock( $cache_key ) {
			$lock_key = $cache_key . '_lock';

			if ( false !== get_site_transient( $lock_key ) ) {
				return '';
			}

			set_site_transient( $lock_key, 1, 3 * MINUTE_IN_SECONDS );

			return $lock_key;
		}

		/**
		 * Make remote release notes HTML safe to render, and able to survive core.
		 *
		 * Sanitizing is only half the job. install_plugin_information() puts every
		 * section through two more passes of its own before anything is printed,
		 * and both of them damage release notes that arrive in perfectly good HTML:
		 *
		 * - wp_kses( $content, $plugins_allowedtags ) keeps a far shorter list than
		 *   wp_kses_post() does. Tags outside it are dropped while their text is
		 *   kept, so a changelog laid out as a table arrives as one run-together
		 *   paragraph. bb_remap_release_notes_tags() rewrites what it can into tags
		 *   that list does allow, before core ever sees them.
		 * - links_add_base_url( $content, 'https://wordpress.org/plugins/<slug>/' )
		 *   runs unconditionally, ungated by $api->external, so a relative link in
		 *   the notes is rewritten to a wordpress.org URL that 404s. Resolving
		 *   every href and src against buddyboss.com here defuses it: core's helper
		 *   leaves an already-absolute URL alone.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param string $html     Raw release notes HTML.
		 * @param string $base_url Optional. URL that relative links in the notes are
		 *                         relative to; defaults to the buddyboss.com
		 *                         resources area the release posts live in.
		 *
		 * @return string Sanitized HTML, or empty string.
		 */
		protected function bb_prepare_release_notes_html( $html, $base_url = '' ) {
			if ( ! is_string( $html ) || '' === $html ) {
				return '';
			}

			/*
			 * Everything below walks the markup, and the remote is what decides
			 * how much of it there is. Refuse a document larger than any release
			 * ever produces instead of walking it.
			 *
			 * Belt and braces, and deliberately so: the same number is already
			 * passed to wp_remote_get() as 'limit_response_size', so an oversized
			 * response is truncated on the wire, fails json_decode() and arrives
			 * here as an empty string rather than as a large one. This is the
			 * second line, for a caller that hands over markup from somewhere
			 * other than bb_release_notes_request_args().
			 *
			 * Returning empty is reported by both callers as 'empty', not
			 * 'failed', so the reader is told the release has nothing published.
			 * That is a shade off the truth for a refusal, and it is left that
			 * way on purpose: correcting it means threading a state out of a
			 * function whose whole job is string in, string out, to distinguish a
			 * case the caller cannot currently reach.
			 */
			if ( strlen( $html ) > $this->bb_release_notes_max_bytes() ) {
				return '';
			}

			/*
			 * The release feed contains tags whose closing bracket is missing at
			 * line ends (e.g. "</ul\r\n"); repair them so wp_kses_post() does not
			 * escape the fragment into visible text, then balance whatever is left.
			 *
			 * A tag only counts as orphaned when no '>' closes it before the next
			 * '<' or the end of the string. That test is what keeps a legitimately
			 * multi-line tag intact: in "<a\n  href=\"...\">Read more" the '>' does
			 * arrive, so the tag is left alone rather than truncated to "<a>" with
			 * its attributes escaping into the page as visible body prose.
			 *
			 * The run that looks for that '<' stops at a double quote, because a
			 * '<' inside an attribute value is not the start of anything: without
			 * that, "<div\ndata-x=\"<img src=x onerror=...>\">" reads as an orphaned
			 * <div> and the repair closes it early, promoting the attribute's text
			 * into live markup that was never markup in the document. Excluding
			 * '=' as well would also catch the single-quoted spelling, but it stops
			 * repairing an orphan followed by prose containing '=', so the quote
			 * alone is what is excluded. The single-quoted case is left: it is rare,
			 * and wp_kses_post() below strips whatever it promotes.
			 */
			$repaired = preg_replace(
				'/<(\/?[a-z][a-z0-9]*)(?=[ \t]*(?:$|[\r\n][^<>"]*(?:<|$)))/i',
				'<${1}>',
				$html
			);

			if ( null !== $repaired ) {
				$html = $repaired;
			}

			$html = $this->bb_remap_release_notes_tags( $html );

			/*
			 * Resolve relative hrefs and srcs against buddyboss.com. Only a host
			 * under our own control is ever used as the base: the URL arrives from
			 * the remote feed, and an attacker-chosen base would turn every
			 * relative link in the notes into a link to their site.
			 *
			 * The scheme is checked as well as the host, and has to be, because
			 * the base is applied after wp_kses_post() has had its look. A base
			 * of "javascript://buddyboss.com/" passes any test that only asks
			 * about the host, and every relative link resolved against it comes
			 * out carrying that scheme with nothing left to strip it. The
			 * installer's own sanitizing pass does drop it, but only for callers
			 * that go through the modal, and these notes are reachable directly.
			 */
			$base   = is_string( $base_url ) ? $base_url : '';
			$parts  = '' !== $base ? wp_parse_url( $base ) : array();
			$parts  = is_array( $parts ) ? $parts : array();
			$host   = ! empty( $parts['host'] ) ? strtolower( $parts['host'] ) : '';
			$scheme = ! empty( $parts['scheme'] ) ? strtolower( $parts['scheme'] ) : '';

			if (
				! in_array( $scheme, array( 'http', 'https' ), true ) ||
				( 'buddyboss.com' !== $host && ! preg_match( '/\.buddyboss\.com$/', $host ) )
			) {
				$base = 'https://buddyboss.com/resources/';
			}

			/*
			 * Sanitize before resolving, not after. links_add_base_url() only
			 * matches a quoted attribute value, so running it first silently skips
			 * every unquoted src and href in the notes - and core then resolves
			 * those same attributes against wordpress.org, which is the 404 this
			 * whole step exists to prevent. wp_kses_post() rewrites attributes into
			 * quoted form, so resolving afterwards reaches all of them.
			 *
			 * Sanitizing first is also the safer order: kses decodes entities and
			 * drops disallowed protocols before any URL is built, so a protocol
			 * spelled "&#106;avascript:" is already gone rather than being carried
			 * into a resolved URL. force_balance_tags() stays last - it only opens
			 * and closes tags, copying attribute text verbatim, so it cannot
			 * reintroduce an unresolved URL.
			 */
			$html = wp_kses_post( $html );
			$html = links_add_base_url( $html, $base, array( 'src', 'href' ) );

			return force_balance_tags( $html );
		}

		/**
		 * Rewrite release notes markup into tags the plugin-information modal keeps.
		 *
		 * Core's install_plugin_information() re-filters every section through its
		 * own $plugins_allowedtags, which allows only a, abbr, acronym, code, pre, em,
		 * strong, div, span, p, br, ul, ol, li, h1-h6, img and blockquote. Anything
		 * else loses its tags while keeping its text, so the markup below is
		 * translated into that vocabulary first.
		 *
		 * Runs before wp_kses_post(), so everything produced here is sanitized
		 * along with the rest rather than being trusted on its way out.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param string $html Repaired release notes HTML.
		 *
		 * @return string HTML using only tags the modal renders.
		 */
		protected function bb_remap_release_notes_tags( $html ) {
			// Exact equivalents. The alternation cannot catch <br> or <img>: after
			// '<b' the optional attribute run needs whitespace or '>', not 'r'.
			$replacements = array(
				'#<b(\s[^>]*)?>#i' => '<strong>',
				'#</b\s*>#i'       => '</strong>',
				'#<i(\s[^>]*)?>#i' => '<em>',
				'#</i\s*>#i'       => '</em>',
			);

			foreach ( $replacements as $pattern => $replacement ) {
				$result = preg_replace( $pattern, $replacement, $html );

				if ( null !== $result ) {
					$html = $result;
				}
			}

			/*
			 * Demote headings. Core prints the plugin name as the modal's <h2>, so a
			 * release note that opens with its own <h1> or <h2> either duplicates the
			 * document's top heading or jumps back up a level under it. Shifting each
			 * one down a step, floored at h3 and capped at h6, keeps the notes below
			 * the modal's heading while preserving their own relative nesting.
			 */
			$result = preg_replace_callback(
				'#<(/?)h([1-6])(\s[^>]*)?>#i',
				static function ( $matches ) {
					$level = max( 3, min( 6, (int) $matches[2] + 1 ) );

					return '<' . $matches[1] . 'h' . $level . '>';
				},
				$html
			);

			if ( null !== $result ) {
				$html = $result;
			}

			return $this->bb_convert_release_notes_tables( $html );
		}

		/**
		 * Turn release notes tables into lists the modal can render.
		 *
		 * A table is the one structure with no equivalent in the modal's tag list,
		 * and losing it is not neutral: core keeps the cell text and drops the tags,
		 * so a three-column changelog row arrives as "FixedActivity feedPROD-1234"
		 * with nothing between the cells. A list is not a table - column headings
		 * stop being associated with their cells, and a screen reader can no longer
		 * navigate it as a grid - but every value stays separated, in order, and
		 * readable, which the alternative does not manage. Converting is the lesser
		 * loss, so it is what happens here.
		 *
		 * Header cells are kept as leading bold text on their row rather than
		 * dropped, so a header row still reads as one, and a caption becomes the
		 * list's first item so its text is not lost.
		 *
		 * Regex rather than DOMDocument on purpose: ext-dom is not guaranteed on a
		 * WordPress host, this runs on markup that wp_kses_post() sanitizes
		 * immediately afterwards, and the worst outcome of a mismatch is a row that
		 * reads oddly rather than anything unsafe.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param string $html Release notes HTML.
		 *
		 * @return string HTML with tables rewritten as lists.
		 */
		protected function bb_convert_release_notes_tables( $html ) {
			if ( false === stripos( $html, '<table' ) ) {
				return $html;
			}

			/*
			 * Every pattern below pairs an opening tag with its closing one across
			 * arbitrary content. Give one of them an opening tag whose partner is
			 * missing and the search for that partner runs to the end of the
			 * document - from every unclosed tag in turn, so the cost grows with
			 * the square of how many there are. A document of a few hundred
			 * kilobytes of unclosed rows takes tens of seconds, and this runs
			 * inside a synchronous admin request, so the markup is checked for
			 * balance first and left alone when it does not balance.
			 *
			 * Nothing is lost by refusing: a table this declines to convert still
			 * reaches wp_kses_post(), and a feed whose rows do not close is not
			 * one whose columns would have survived conversion anyway. Real notes
			 * are rendered by WordPress and always balance.
			 */
			if ( ! $this->bb_release_notes_tags_balance( $html ) ) {
				return $html;
			}

			// Each row becomes one list item whose cells are separated visibly.
			$result = preg_replace_callback(
				'#<tr(?:\s[^>]*)?>(.*?)</tr\s*>#is',
				array( $this, 'bb_convert_release_notes_row' ),
				$html
			);

			if ( null !== $result ) {
				$html = $result;
			}

			$replacements = array(
				// A caption precedes the rows, so it becomes the first list item.
				'#<caption(?:\s[^>]*)?>(.*?)</caption\s*>#is' => '<li><strong>$1</strong></li>',
				'#<table(?:\s[^>]*)?>#i'                 => '<ul>',
				'#</table\s*>#i'                         => '</ul>',
				// Grouping elements carry no content of their own.
				'#</?(?:thead|tbody|tfoot|colgroup)(?:\s[^>]*)?>#i' => '',
				'#<col(?:\s[^>]*)?/?>#i'                 => '',
				// Any cell left outside a row, so no text is stranded bare in a <ul>.
				'#<(t[dh])(?:\s[^>]*)?>(.*?)</\1\s*>#is' => '<li>$2</li>',
			);

			foreach ( $replacements as $pattern => $replacement ) {
				$result = preg_replace( $pattern, $replacement, $html );

				if ( null !== $result ) {
					$html = $result;
				}
			}

			return $html;
		}

		/**
		 * Whether every table tag the conversion pairs up has its closing partner.
		 *
		 * Counts openings against closings for the elements
		 * bb_convert_release_notes_tables() matches as pairs. A surplus of
		 * openings is what makes those patterns search to the end of the document
		 * and back for a partner that is not there, so it is the condition worth
		 * testing before any of them runs. Counting is a handful of linear scans
		 * and costs nothing next to what it avoids.
		 *
		 * The running total is tested at every step, not only at the end, and
		 * that is the whole of what makes this a guard. A final total of zero
		 * says the document has as many closings as openings; it does not say
		 * each opening has one *after* it. "</tr></tr><tr><tr>" balances on the
		 * final count while still leaving two openings with no partner ahead of
		 * them, which is exactly the shape the patterns scan to the end of the
		 * document for - so a closings-first payload sized just under
		 * bb_release_notes_max_bytes() passed the end-of-loop test and still
		 * cost tens of seconds. Refusing the moment the total goes negative
		 * catches that, because a closing tag arriving with nothing open is the
		 * first observable sign of it.
		 *
		 * td and th are counted apart, and that is not tidiness. The patterns
		 * pair a cell with a backreference - "</\1>" - so a <th> is satisfied
		 * only by a </th>, never by a </td>. Counted together, N openings of one
		 * spelling followed by N closings of the other is a balanced tally and a
		 * document in which not one opening has a partner: every <th> then scans
		 * to the end of the document for a </th> that is not there. Measured at
		 * the byte cap this fetch allows, that shape - and its alternating
		 * sibling, "<td>x</th>" repeated - cost 164 and 91 seconds respectively
		 * while this function answered true. One tally per spelling is what
		 * makes the answer match what the patterns actually pair.
		 *
		 * Scoped per element, and only per element. Tags of different names are
		 * not checked against each other, so "<tr><td></tr></td>" still passes -
		 * correctly, for this purpose: both patterns find their partner and
		 * neither scans past it, so nothing here is quadratic. This answers the
		 * question the conversion needs answered, which is whether any single
		 * pattern will hunt to the end of the document, not whether the markup
		 * is well-formed.
		 *
		 * A word boundary keeps the names exact: "<th" must not count "<thead",
		 * and "<tr" must not count "<track".
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param string $html Release notes HTML.
		 *
		 * @return bool True when, within each element name, every opening tag has
		 *              a closing partner after it.
		 */
		protected function bb_release_notes_tags_balance( $html ) {
			/*
			 * One entry per element name the conversion pairs up. Splitting or
			 * merging entries here changes which documents are refused: an entry
			 * must cover exactly the tags one pattern treats as interchangeable,
			 * and the cell patterns treat none.
			 */
			$pairs = array(
				'tr'      => '#</?tr\b#i',
				'td'      => '#</?td\b#i',
				'th'      => '#</?th\b#i',
				'caption' => '#</?caption\b#i',
			);

			foreach ( $pairs as $pattern ) {
				if ( ! preg_match_all( $pattern, $html, $matches ) ) {
					continue;
				}

				$open = 0;

				foreach ( $matches[0] as $tag ) {
					// A closing tag is the only one whose second character is '/'.
					$open += ( '/' === $tag[1] ) ? -1 : 1;

					// Closed something that was never opened: the tags are
					// interleaved, so the end-of-loop total proves nothing.
					if ( $open < 0 ) {
						return false;
					}
				}

				if ( 0 !== $open ) {
					return false;
				}
			}

			return true;
		}

		/**
		 * Build one list item from a release notes table row.
		 *
		 * Callback for bb_convert_release_notes_tables(). Header cells keep their
		 * emphasis, and cells are joined with a visible separator so adjacent values
		 * cannot read as one word.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param array $matches Match from the row pattern; [1] is the row's cells.
		 *
		 * @return string One list item.
		 */
		protected function bb_convert_release_notes_row( $matches ) {
			$row = isset( $matches[1] ) ? $matches[1] : '';

			if ( ! preg_match_all( '#<(t[dh])(?:\s[^>]*)?>(.*?)</\1\s*>#is', $row, $cells, PREG_SET_ORDER ) ) {
				// A row with nothing recognizable in it: keep whatever text it held.
				return '<li>' . $row . '</li>';
			}

			$parts = array();

			foreach ( $cells as $cell ) {
				$content = trim( $cell[2] );

				if ( '' === $content ) {
					continue;
				}

				$parts[] = ( 'th' === strtolower( $cell[1] ) ) ? '<strong>' . $content . '</strong>' : $content;
			}

			if ( empty( $parts ) ) {
				return '';
			}

			return '<li>' . implode( ' &middot; ', $parts ) . '</li>';
		}

		/**
		 * Pull the release notes HTML out of a releases REST item.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param array $item One item from a releases REST collection.
		 *
		 * @return string Raw release notes HTML, or empty string.
		 */
		protected function bb_extract_release_notes_html( $item ) {
			if ( ! is_array( $item ) ) {
				return '';
			}

			if ( ! empty( $item['content']['rendered'] ) ) {
				return $item['content']['rendered'];
			}

			if ( empty( $item['release_fields'] ) ) {
				return '';
			}

			$fields = $item['release_fields'];

			if ( is_string( $fields ) ) {
				return $fields;
			}

			if ( is_array( $fields ) ) {
				foreach ( array( 'changelog', 'changes', 'release_notes', 'content' ) as $field_key ) {
					if ( ! empty( $fields[ $field_key ] ) && is_string( $fields[ $field_key ] ) ) {
						return $fields[ $field_key ];
					}
				}
			}

			return '';
		}

		/**
		 * Fetch the release notes HTML for a BuddyBoss add-on version.
		 *
		 * Add-on releases all share one post type on buddyboss.com, separated by a
		 * term of its "addons" taxonomy, so the add-on is identified by term slug
		 * rather than by its own post type. Their post slugs cannot be relied on -
		 * the prefix differs per add-on and WordPress appends a disambiguation
		 * suffix to duplicates (addons-1-1-1-2), and a few predate the convention
		 * entirely - but the post title is always the plain version number, so the
		 * version is matched on that.
		 *
		 * Public API. BuddyBoss add-on plugins call this from their own
		 * plugins_api handlers behind an is_callable() guard, which reports
		 * false for a method they cannot reach. Narrowing this does not fatal,
		 * then - each add-on silently drops the changelog it would have built
		 * and renders a bare link instead - and that is the reason to treat the
		 * visibility as fixed rather than a reason to relax about it: the
		 * failure is six products quietly losing a section, with nothing raised
		 * anywhere to say so. data_contract_methods() in tests/phpunit/
		 * testcases/core/class-bp-admin-release-notes.php lists the whole
		 * contract; keep it in step. It is a record, not a gate - that suite
		 * does not currently run on this branch.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param string $version   Version number, e.g. '2.1.2'.
		 * @param string $term_slug Term slug of the add-on in the releases taxonomy.
		 * @param string $state     Optional. Set to why there are no notes, when
		 *                          there are none: 'ok', 'empty', 'failed' or
		 *                          'locked'. See bb_build_changelog_section().
		 *                          Passed by reference.
		 *
		 * @return string Sanitized release notes HTML, or empty string if unavailable.
		 */
		public function bb_get_addon_release_notes_html( $version, $term_slug, &$state = null ) {
			// 'skipped' until something is asked of the remote; see the same
			// opening in bb_get_release_notes_html() for why.
			$state     = 'skipped';
			$version   = $this->bb_normalize_release_version( $version );
			$term_slug = sanitize_title( (string) $term_slug );

			if ( '' === $version || '' === $term_slug ) {
				return '';
			}

			$state = 'empty';

			$cache_key = 'bb_release_notes_addon_' . md5( $term_slug . '_' . $version );
			$cached    = get_site_transient( $cache_key );

			if ( false !== $cached ) {
				return $this->bb_read_release_notes_cache( $cached, $state );
			}

			$lock_key = $this->bb_claim_release_notes_lock( $cache_key );

			if ( '' === $lock_key ) {
				$state = 'locked';

				return '';
			}

			/*
			 * Three requests at worst, and each one gates the next: a step that
			 * cannot answer stops the chain rather than sending the one after it
			 * off with nothing to work with. The distinction the $failed flags
			 * carry is not about control flow, which short-circuits either way -
			 * it is about which of two very different answers gets cached for the
			 * hour that follows. A term that has no post for this version and a
			 * term lookup that never reached the network both end here with no
			 * markup, and only one of them is worth telling someone to retry.
			 */
			$html   = '';
			$failed = false;

			$term_id = $this->bb_get_addon_release_term_id( $term_slug, $failed );

			if ( ! $failed && $term_id ) {
				$release_id = $this->bb_find_addon_release_id( $term_id, $version, $failed );

				if ( ! $failed && $release_id ) {
					$html = $this->bb_get_addon_release_body( $release_id, $failed );
				}
			}

			// Pessimistic first, result second, for the reason given in
			// bb_get_release_notes_html(): work that dies must not leave the cache
			// empty and the next page load repeating it.
			$this->bb_cache_release_notes( $cache_key, '', 'failed' );

			$html = $this->bb_prepare_release_notes_html( $html );

			if ( '' !== $html ) {
				$state = 'ok';
			} elseif ( $failed ) {
				$state = 'failed';
			} else {
				$state = 'empty';
			}

			$this->bb_cache_release_notes( $cache_key, $html, $state );
			delete_site_transient( $lock_key );

			return $html;
		}

		/**
		 * Get the buddyboss.com releases archive URL for an add-on.
		 *
		 * Add-on releases are grouped under a term in the releases taxonomy, and
		 * that term's archive is the only stable page an add-on can link to.
		 *
		 * Deliberately an archive and never a single release. The per-release
		 * permalinks exist, but they are filed under a prefix that differs per
		 * product and cannot be derived from anything an add-on holds
		 * (buddyboss-learndash 1.0.3 is 'bbld-1-0-3', offload media 2.1.2 is
		 * 'om-2-1-2', sharing 2.0.3 is 'sharing-2-0-3') - which is the same reason
		 * bb_find_addon_release_id() matches releases on post title rather than
		 * slug. Appending a version to the term archive instead produces a URL
		 * that looks right and 404s, so the version is not passed on.
		 *
		 * Public API. BuddyBoss add-on plugins call this from their own
		 * plugins_api handlers behind an is_callable() guard, which reports
		 * false for a method they cannot reach. Narrowing this does not fatal,
		 * then - each add-on silently drops the changelog it would have built
		 * and renders a bare link instead - and that is the reason to treat the
		 * visibility as fixed rather than a reason to relax about it: the
		 * failure is six products quietly losing a section, with nothing raised
		 * anywhere to say so. data_contract_methods() in tests/phpunit/
		 * testcases/core/class-bp-admin-release-notes.php lists the whole
		 * contract; keep it in step. It is a record, not a gate - that suite
		 * does not currently run on this branch.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param string $term_slug Term slug of the add-on in the releases taxonomy.
		 *
		 * @return string Releases archive URL.
		 */
		public function bb_get_addon_release_archive_url( $term_slug ) {
			$term_slug = sanitize_title( (string) $term_slug );

			// Without a term there is no archive to name, so fall back to the one
			// that lists every add-on release.
			$base = '' !== $term_slug
				? 'https://buddyboss.com/resources/addons/' . $term_slug . '/'
				: 'https://buddyboss.com/resources/buddyboss-addons/';

			return $this->bb_get_release_notes_page_url( '', $base );
		}

		/**
		 * Build a finished changelog section for a BuddyBoss add-on.
		 *
		 * One call for the whole of what an add-on's plugins_api handler needs in
		 * its 'changelog' section: the notes are fetched, every reason for having
		 * none is turned into the right sentence, and the trailing link resolves
		 * to that add-on's own releases archive. An add-on therefore has no fetch
		 * state to interpret, no link to assemble and no archive URL to derive -
		 * the three things six separate implementations each had to get right.
		 *
		 * $link_text is plain, unescaped text, and so is bb_build_changelog_
		 * section()'s - the two agree, and the agreement is the point. They used
		 * to disagree, which meant two public methods taking link text under
		 * opposite contracts with nothing in either signature to say which was
		 * which. Callers got it right, but only by writing it down each time.
		 * Do not reintroduce the split: a contract like that freezes the moment
		 * add-ons ship against it, and there is no runtime way to tell a
		 * pre-escaping caller from a naive one afterwards.
		 *
		 * Hand over $args and the fetch is gated the same way this plugin gates
		 * its own, by bb_should_fetch_release_notes(). That gate is not a nicety:
		 * WP_Plugin_Dependencies::get_dependency_api_data() calls plugins_api()
		 * for every "Requires Plugins" slug on every plugins.php and
		 * plugin-install.php load, and the add-on fetch below is three sequential
		 * requests at the request timeout - so an ungated caller puts up to
		 * fifteen seconds of blocking HTTP into an ordinary admin page render,
		 * for a section that page never displays.
		 *
		 * The default is null rather than an empty array or false, and the
		 * distinction is load-bearing. bb_should_fetch_release_notes() reads
		 * isset( $args->fields ), so an empty object is indistinguishable from
		 * real arguments that name no fields - which is the permissive case, and
		 * answers true. Only null can mean "not supplied", and "not supplied"
		 * has to keep fetching, because every add-on calling this today gates
		 * for itself before it gets here and must not silently stop rendering a
		 * changelog.
		 *
		 * Public API. BuddyBoss add-on plugins call this from their own
		 * plugins_api handlers behind an is_callable() guard, which reports
		 * false for a method they cannot reach. Narrowing this does not fatal,
		 * then - each add-on silently drops the changelog it would have built
		 * and renders a bare link instead - and that is the reason to treat the
		 * visibility as fixed rather than a reason to relax about it: the
		 * failure is six products quietly losing a section, with nothing raised
		 * anywhere to say so. data_contract_methods() in tests/phpunit/
		 * testcases/core/class-bp-admin-release-notes.php lists the whole
		 * contract; keep it in step. It is a record, not a gate - that suite
		 * does not currently run on this branch.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param string      $version   Version number the notes should describe.
		 * @param string      $term_slug Term slug of the add-on in the releases taxonomy.
		 * @param string      $link_url  Optional. URL for the trailing link; defaults to
		 *                               the add-on's releases archive.
		 * @param string      $link_text Optional. Plain, unescaped text for the trailing
		 *                               link; defaults to a generic release notes label.
		 * @param object|null $args      Optional. The plugins_api() arguments this is
		 *                               answering for, so the remote fetch is gated to
		 *                               the caller that renders a changelog. Null means
		 *                               the caller has gated already; see above.
		 *
		 * @return string Changelog section HTML.
		 */
		public function bb_get_addon_changelog_section( $version, $term_slug, $link_url = '', $link_text = '', $args = null ) {
			$term_slug = sanitize_title( (string) $term_slug );

			$fetch = ( null === $args ) || $this->bb_should_fetch_release_notes( $args );

			/*
			 * 'skipped' renders the trailing link on its own with no sentence
			 * beside it, which is the only honest answer when nothing was asked
			 * of the remote - a caller that wanted no sections, a term slug this
			 * does not recognize, or a version that could not be resolved.
			 * Saying "no release notes have been published" in any of those
			 * cases is a claim about the release that nothing checked.
			 *
			 * Only the fetch replaces it, and bb_get_addon_release_notes_html()
			 * holds the same rule internally: it stays on 'skipped' through its
			 * own unresolvable-input bail and moves to 'empty' only once it is
			 * committed to reading an answer.
			 */
			$state = 'skipped';
			$notes = ( $fetch && '' !== $term_slug )
				? $this->bb_get_addon_release_notes_html( $version, $term_slug, $state )
				: '';

			$link_url = '' !== (string) $link_url
				? (string) $link_url
				: $this->bb_get_addon_release_archive_url( $term_slug );

			// Plain text both ways: bb_build_changelog_section() escapes it.
			$link_text = '' !== (string) $link_text
				? (string) $link_text
				: __( 'Visit the plugin website for release information', 'buddyboss' );

			return $this->bb_build_changelog_section( $notes, $link_url, $link_text, $state );
		}

		/**
		 * Find the ID of an add-on release post by version number.
		 *
		 * Asks the collection to search for the version rather than paging through
		 * it, and restricts the search to post titles, which is where an add-on's
		 * version number lives. Narrowing it that way is what makes the answer
		 * authoritative: an unrestricted search also matches every release whose
		 * body happens to mention the number, and enough of those ahead of the one
		 * being looked for would push it off the only page that is ever read.
		 *
		 * 'search_columns' arrived in WordPress 6.2. A remote older than that
		 * ignores it - unregistered REST parameters are dropped, not rejected - and
		 * answers with an unrestricted search instead, which is why the page size
		 * stays large enough to be a usable fallback rather than being trimmed to
		 * what the narrowed search needs.
		 *
		 * The title is compared again in PHP either way, because a search is a
		 * substring match: 2.1.2 also matches a 2.1.21 release.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param int    $term_id Releases taxonomy term ID for the add-on.
		 * @param string $version Normalized version number, e.g. '2.1.2'.
		 * @param bool   $failed  Set to true when the request did not complete, as
		 *                        opposed to completing with no match. Passed by
		 *                        reference.
		 *
		 * @return int Release post ID, or 0 when no release carries that version.
		 */
		protected function bb_find_addon_release_id( $term_id, $version, &$failed = null ) {
			$failed = false;

			$endpoint = add_query_arg(
				array(
					'addons'         => (int) $term_id,
					'search'         => $version,
					'search_columns' => 'post_title',
					'per_page'       => 100,
					'_fields'        => 'id,title',
				),
				'https://buddyboss.com/resources/wp-json/wp/v2/bb-addons'
			);

			$response = wp_remote_get( esc_url_raw( $endpoint ), $this->bb_release_notes_request_args() );

			if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
				$failed = true;

				return 0;
			}

			$items = json_decode( wp_remote_retrieve_body( $response ), true );

			if ( ! is_array( $items ) ) {
				return 0;
			}

			foreach ( $items as $item ) {
				if ( ! is_array( $item ) || empty( $item['id'] ) ) {
					continue;
				}

				$title = isset( $item['title']['rendered'] ) ? trim( wp_strip_all_tags( $item['title']['rendered'] ) ) : '';

				if ( $title === $version ) {
					return (int) $item['id'];
				}
			}

			return 0;
		}

		/**
		 * Fetch one add-on release post's notes by ID.
		 *
		 * Requests the same fields the collection scan used to ask for, so the
		 * field the notes are read from does not change - only the number of
		 * releases whose body travels over the wire.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param int  $release_id Release post ID.
		 * @param bool $failed     Set to true when the request did not complete, as
		 *                         opposed to completing with nothing to show.
		 *                         Passed by reference.
		 *
		 * @return string Raw release notes HTML, or empty string.
		 */
		protected function bb_get_addon_release_body( $release_id, &$failed = null ) {
			$failed = false;

			$endpoint = add_query_arg(
				array( '_fields' => 'title,release_fields' ),
				'https://buddyboss.com/resources/wp-json/wp/v2/bb-addons/' . (int) $release_id
			);

			$response = wp_remote_get( esc_url_raw( $endpoint ), $this->bb_release_notes_request_args() );

			if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
				$failed = true;

				return '';
			}

			$item = json_decode( wp_remote_retrieve_body( $response ), true );

			return is_array( $item ) ? $this->bb_extract_release_notes_html( $item ) : '';
		}

		/**
		 * Resolve an add-on releases taxonomy term slug to its term ID.
		 *
		 * The posts collection filters on term IDs, and those are specific to
		 * buddyboss.com, so the stable term slug is resolved here instead of
		 * hard-coding an ID. Cached separately from the notes: term IDs change far
		 * less often than releases appear.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * Only a completed lookup is cached, and that is what makes a cached 0
		 * mean something. Caching a failure as 0 reads back an hour later as "the
		 * remote has no term for this add-on", which the caller turns into "no
		 * release notes have been published for this version yet" - a statement
		 * about the product, made on the strength of a request that never
		 * arrived. Leaving the failure uncached costs no retry storm either: the
		 * caller has already written its own hour-long 'failed' entry over the
		 * whole fetch before this could be reached again.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param string $term_slug Term slug in the releases taxonomy.
		 * @param bool   $failed    Set to true when the request did not complete, as
		 *                          opposed to completing with no such term. Always
		 *                          set, including for an answer served from the
		 *                          cache - where it is false, and truthfully so,
		 *                          because only a completed lookup is ever cached.
		 *                          Passed by reference.
		 *
		 * @return int Term ID, or 0 when it cannot be resolved.
		 */
		protected function bb_get_addon_release_term_id( $term_slug, &$failed = null ) {
			$failed = false;

			$cache_key = 'bb_release_addon_term_' . md5( $term_slug );
			$cached    = get_site_transient( $cache_key );

			if ( false !== $cached ) {
				return (int) $cached;
			}

			$endpoint = add_query_arg(
				array(
					'slug'    => $term_slug,
					'_fields' => 'id',
				),
				'https://buddyboss.com/resources/wp-json/wp/v2/addons'
			);

			$response = wp_remote_get( esc_url_raw( $endpoint ), $this->bb_release_notes_request_args() );
			$term_id  = 0;

			if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
				$failed = true;

				// Nothing was learned, so nothing is recorded; see the note above.
				return 0;
			}

			$terms = json_decode( wp_remote_retrieve_body( $response ), true );

			if ( is_array( $terms ) && ! empty( $terms[0]['id'] ) ) {
				$term_id = (int) $terms[0]['id'];
			}

			set_site_transient( $cache_key, $term_id, $term_id ? WEEK_IN_SECONDS : HOUR_IN_SECONDS );

			return $term_id;
		}

		/**
		 * Serve plugin information for an installed BuddyBoss add-on that did not
		 * answer for itself.
		 *
		 * Each BuddyBoss add-on answers plugins_api for its own slug, but only
		 * from code that is running. Deactivate it and nothing answers; leave it
		 * active and its handler can still be missing, because an add-on that
		 * registers the handler from inside an integration loses it whenever that
		 * integration declines to boot. The Plugins screen offers "View details"
		 * in both cases — the slug reaches the row from the update transient,
		 * which this plugin's updater populates — and without a handler that
		 * request falls through to WordPress.org and dies with "Plugin not
		 * found."
		 *
		 * This is registered at priority 999 and answers only when nothing else
		 * did, so an add-on that can speak for itself always does. Both halves
		 * are load-bearing: the add-ons that check $result are deferred to by the
		 * second, and BuddyBoss App - which does not check it, and registers at
		 * 99 - is deferred to by the first. The details here come from the
		 * add-on's plugin headers and the update transient, which is all that can
		 * be read from the outside.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param false|object|array $result The result object or array. Default false.
		 * @param string             $action The type of information being requested from the Plugin Installation API.
		 * @param object             $args   Plugin API arguments.
		 *
		 * @return false|object Plugin information for the add-on, or the original result.
		 */
		public function bb_plugins_api_addon_fallback( $result, $action, $args ) {
			// A non-string slug names no installed directory; see
			// bb_plugins_api_information() for where it comes from.
			if (
				'plugin_information' !== $action ||
				empty( $args->slug ) ||
				! is_string( $args->slug ) ||
				false !== $result
			) {
				return $result;
			}

			$plugin = $this->bb_get_installed_buddyboss_addon( $args->slug );

			if ( empty( $plugin['file'] ) ) {
				return $result;
			}

			$plugin_file = $plugin['file'];
			$plugin_data = $plugin['data'];

			$new_version = ! empty( $plugin_data['Version'] ) ? $plugin_data['Version'] : '';
			$package     = '';
			$update      = $this->bb_get_plugin_update_entry( $plugin_file );

			if ( ! empty( $update->new_version ) ) {
				$new_version = $update->new_version;
				$package     = ! empty( $update->package ) ? $update->package : '';
			}

			$plugin_uri = ! empty( $plugin_data['PluginURI'] ) ? $plugin_data['PluginURI'] : 'https://buddyboss.com/';
			$author_uri = ! empty( $plugin_data['AuthorURI'] ) ? $plugin_data['AuthorURI'] : 'https://buddyboss.com/';
			$author     = ! empty( $plugin_data['Author'] ) ? wp_strip_all_tags( $plugin_data['Author'] ) : 'BuddyBoss';

			/*
			 * Serve the add-on's release notes as well, so the modal reads the same
			 * whether the add-on answered for itself or this fallback did. An
			 * add-on that is running fetches its own notes and never reaches here;
			 * one that is not - inactive, or with its handler behind an integration
			 * that has not booted - would otherwise be left with a bare link.
			 */
			$changelog = '';
			$state     = 'skipped';
			$term      = $this->bb_get_addon_release_term( $args->slug );

			if ( $this->bb_should_fetch_release_notes( $args ) ) {
				$rest_base = $this->bb_get_addon_release_post_type( $args->slug );

				if ( '' !== $term ) {
					$changelog = $this->bb_get_addon_release_notes_html( $new_version, $term, $state );
				} elseif ( '' !== $rest_base ) {
					$changelog = $this->bb_get_release_notes_html( $new_version, $rest_base, $state );
				}
			}

			/*
			 * An add-on with a releases archive gets linked to it. The plugin's own
			 * URI is the fallback for one that has none, and it is a weak link for
			 * the purpose - every BuddyBoss add-on ships the same marketing site as
			 * its PluginURI, so a link captioned "release information" would land on
			 * a page with none.
			 */
			$release_url = '' !== $term
				? $this->bb_get_addon_release_archive_url( $term )
				: $plugin_uri;

			$information = array(
				'name'          => wp_strip_all_tags( $plugin_data['Name'] ),
				'slug'          => $args->slug,

				/*
				 * Sanitized for the same reason as in bb_plugins_api_information(),
				 * and the hazard documented there is sharper here: this value comes
				 * from the add-on's own Version header whenever no update is
				 * pending, which is precisely the path where core's slug lookup
				 * cannot match and the version_compare() always runs. An add-on
				 * shipping a pre-release header is the ordinary case for that
				 * branch, so the suffix has to survive this far.
				 */
				'version'       => $this->bb_sanitize_plugin_version( $new_version ),
				'author'        => '<a href="' . esc_url( $author_uri ) . '" target="_blank" rel="noopener noreferrer">' . esc_html( $author ) . '</a>',
				'homepage'      => esc_url( $plugin_uri ),
				'last_updated'  => $this->bb_get_plugin_last_updated( $plugin_file, $update ),
				'sections'      => array(
					'description' => '<p>' . wp_kses_post( $plugin_data['Description'] ) . '</p>',
					'changelog'   => $this->bb_build_changelog_section(
						$changelog,
						$release_url,
						__( 'Visit the plugin website for release information', 'buddyboss' ),
						$state
					),
				),
				'download_link' => $this->bb_get_plugin_download_link( $package ),
			);

			/**
			 * Filters the plugin information served for an inactive BuddyBoss add-on.
			 *
			 * Return plain text for 'name' and 'version'. install_plugin_information()
			 * prints both without escaping - 'name' into the modal's <h2> with no
			 * filtering at all, 'version' through wp_kses() with the installer's own
			 * short allowlist, which still passes links, images and class attributes
			 * straight to the page. Markup returned here is markup in wp-admin.
			 *
			 * @since BuddyBoss [BBVERSION]
			 *
			 * @param array  $information Plugin information served to the plugin-information modal.
			 * @param string $new_version Version number the information describes.
			 * @param object $args        Plugin API arguments (the requested slug is $args->slug).
			 */
			$information = apply_filters( 'bb_plugins_api_addon_fallback_information', $information, $new_version, $args );

			return (object) $information;
		}

		/**
		 * Map an add-on's plugin slug to its term in the releases taxonomy.
		 *
		 * Add-on releases are grouped on buddyboss.com by a term whose slug does
		 * not match the plugin directory, so the pairing is kept here. Add-ons
		 * that ship their own handler pass their term directly and never consult
		 * this; it exists for the ones this fallback answers for.
		 *
		 * Keyed on the directory the add-on is installed into, which is the only
		 * name plugins_api() is given to work with. Rename that directory and the
		 * lookup misses: the modal then shows its description, version and author
		 * with a bare link in place of the changelog, which is the same thing it
		 * shows for an add-on that has no releases feed. Degraded, not broken -
		 * and the bb_addon_release_terms filter below is the way back for a site
		 * that has renamed one.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param string $slug Plugin directory slug.
		 *
		 * @return string Term slug, or empty string when the add-on has no releases feed.
		 */
		protected function bb_get_addon_release_term( $slug ) {
			/*
			 * Key is the plugin directory, value the term slug on buddyboss.com.
			 * The three oldest products carry a "-releases" suffix on the term
			 * and the newer ones do not, so neither shape can be derived - the
			 * pairing has to be recorded.
			 *
			 * Member Blogging installs into 'buddyboss-member-blogging', not
			 * 'member-blogging': bb_member_blogging_plugin_slug() is what this
			 * plugin already uses to resolve that same product against the add-on
			 * server, and the key has to be the directory plugins_api() is given.
			 * Its term slug is the one pairing here not checked against the live
			 * taxonomy; if it is wrong the lookup simply misses and the modal
			 * degrades to a bare link, exactly as it does for an add-on with no
			 * releases feed at all.
			 */
			$terms = array(
				'buddyboss-offload-media'   => 'buddyboss-offload-media-releases',
				'buddyboss-gamification'    => 'buddyboss-gamification-releases',
				'buddyboss-sharing'         => 'buddyboss-sharing-releases',
				'buddyboss-learndash'       => 'buddyboss-learndash',
				'buddyboss-addons'          => 'buddyboss-addons',
				'buddyboss-tools'           => 'buddyboss-tools',
				'buddyboss-member-blogging' => 'member-blogging',
			);

			/**
			 * Filters the plugin slug to releases-taxonomy term map.
			 *
			 * @since BuddyBoss [BBVERSION]
			 *
			 * @param array $terms Map of plugin directory slug to releases term slug.
			 */
			$terms = apply_filters( 'bb_addon_release_terms', $terms );

			return isset( $terms[ $slug ] ) ? (string) $terms[ $slug ] : '';
		}

		/**
		 * Map an add-on's plugin slug to its own releases post type.
		 *
		 * A few products keep their releases in a post type of their own rather
		 * than in the shared add-on taxonomy, so they are resolved separately
		 * from bb_get_addon_release_term().
		 *
		 * Keyed on the installed directory name, with the same consequence a
		 * rename has there: the changelog degrades to a bare link, and the
		 * bb_addon_release_post_types filter below is the way back.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param string $slug Plugin directory slug.
		 *
		 * @return string Releases post type REST base, or empty string.
		 */
		protected function bb_get_addon_release_post_type( $slug ) {
			$types = array(
				'buddyboss-platform-pro' => 'releases-platformpro',
				'buddyboss-app'          => 'releases-app',
			);

			/**
			 * Filters the plugin slug to releases post type map.
			 *
			 * @since BuddyBoss [BBVERSION]
			 *
			 * @param array $types Map of plugin directory slug to releases post type REST base.
			 */
			$types = apply_filters( 'bb_addon_release_post_types', $types );

			return isset( $types[ $slug ] ) ? (string) $types[ $slug ] : '';
		}

		/**
		 * Plugin slugs distributed by BuddyBoss that carry no "Requires Plugins" header.
		 *
		 * The header is what normally marks a plugin as distributed from BuddyBoss's
		 * own servers rather than the WordPress.org directory. A product that shipped
		 * before the header existed still needs answering here, otherwise its details
		 * modal falls through to WordPress.org and reports "Plugin not found." while
		 * it is deactivated - it cannot answer for itself with its code not running.
		 *
		 * Before changing the priority bb_plugins_api_addon_fallback() is
		 * registered at, read this: BuddyBoss App, the one entry below, registers
		 * its own plugins_api handler at priority 99, and that handler answers for
		 * its slug without checking $result or $action first. It therefore
		 * overwrites whatever came before it rather than deferring to it, so the
		 * only way for the fallback to stay out of its way is to run after it -
		 * hence 999, rather than the 20 that was there when this list was first
		 * added. Every other add-on in the suite registers at 10 and does check
		 * $result, so any priority above 10 works for them and only App forces the
		 * question. A priority between 10 and 99 does not merely waste the work:
		 * with a releases feed now mapped for App in
		 * bb_get_addon_release_post_type(), it would spend blocking HTTP requests
		 * and the notes transform on an answer App discards.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @return array Plugin directory slugs.
		 */
		protected function bb_get_known_buddyboss_addon_slugs() {
			$slugs = array( 'buddyboss-app' );

			/**
			 * Filters the BuddyBoss add-on slugs recognized without a "Requires Plugins" header.
			 *
			 * @since BuddyBoss [BBVERSION]
			 *
			 * @param array $slugs Plugin directory slugs.
			 */
			$slugs = apply_filters( 'bb_known_buddyboss_addon_slugs', $slugs );

			return array_map( 'sanitize_title', (array) $slugs );
		}

		/**
		 * Resolve a slug to an installed BuddyBoss add-on of this plugin.
		 *
		 * Which plugins qualify, and why the test is deliberately narrow, is
		 * documented on bb_is_buddyboss_addon().
		 *
		 * Deliberately says nothing about whether the add-on is active. Activity
		 * is not the condition that matters here - having answered for itself is,
		 * and the caller already knows that from $result being false. The two are
		 * not the same thing: an add-on registers its own plugins_api handler from
		 * inside an integration that can decline to boot (buddyboss-learndash
		 * skips its includes() entirely once its licence lock trips), leaving a
		 * plugin that is active by every measure WordPress reports and still has
		 * no handler. Turning this into an is_plugin_active() test would hand
		 * those users "Plugin not found." Answering for an add-on that did
		 * register costs nothing either way, because the caller never reaches
		 * here once someone has answered.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param string $slug Plugin directory slug.
		 *
		 * @return array Array with 'file' (plugin basename) and 'data' (plugin headers), or empty array.
		 */
		protected function bb_get_installed_buddyboss_addon( $slug ) {
			$platform_slug = $this->bb_get_platform_plugin_slug();

			// This plugin answers for itself in bb_plugins_api_information().
			if ( $slug === $platform_slug ) {
				return array();
			}

			/*
			 * Early-out before get_plugins(), which is reached for every slug that
			 * arrives at this priority unanswered - including each WordPress.org
			 * plugin card opened on plugin-install.php. An add-on always lives in a
			 * directory of its own, so a slug naming none cannot be one. The slug
			 * comes off the request, so refuse anything that is not a bare
			 * directory name before it reaches the filesystem.
			 */
			if (
				! is_string( $slug ) ||
				'' === $slug ||
				false !== strpbrk( $slug, '/\\' ) ||
				0 === strpos( $slug, '.' )
			) {
				return array();
			}

			/*
			 * The same directory-name pre-filter bb_is_buddyboss_addon_file()
			 * applies, and applied here for correctness rather than for speed.
			 *
			 * These two are the suite's two answers to "is this one of ours", and
			 * they have to give the same answer. bb_fix_plugin_details_link() uses
			 * that one to decide whether to normalize an entry's slug; this one
			 * decides whether bb_plugins_api_addon_fallback() answers for that
			 * slug, and bb_get_plugin_update_entry() then looks the entry up by
			 * plugin file. Let the two disagree and a plugin exists whose modal
			 * carries a download_link found by file while its transient slug was
			 * never corrected - and install_plugin_install_status() matches on
			 * slug, so it misses, drops into its "install" branch, and runs
			 * delete_site_transient( 'update_plugins' ) plus a blocking
			 * wp_update_plugins() on every modal open, settling on no button at
			 * all. Nothing shipped reaches that today, because every add-on
			 * directory in the suite starts with "buddyboss" or is named in
			 * bb_get_known_buddyboss_addon_slugs(); one predicate is how it stays
			 * that way.
			 *
			 * A genuine add-on installed into a renamed directory is refused by
			 * both, consistently, and bb_possible_buddyboss_addon_slug is the one
			 * filter that brings it back to both at once.
			 */
			if ( ! $this->bb_maybe_buddyboss_addon_slug( $slug ) ) {
				return array();
			}

			if ( ! is_dir( WP_PLUGIN_DIR . '/' . $slug ) ) {
				return array();
			}

			if ( ! function_exists( 'get_plugins' ) ) {
				require_once ABSPATH . 'wp-admin/includes/plugin.php';
			}

			foreach ( get_plugins() as $file => $data ) {
				if ( dirname( $file ) !== $slug ) {
					continue;
				}

				if ( ! $this->bb_is_buddyboss_addon( $file, $data ) ) {
					continue;
				}

				return array(
					'file' => $file,
					'data' => $data,
				);
			}

			return array();
		}
	}
endif; // End class_exists check.
