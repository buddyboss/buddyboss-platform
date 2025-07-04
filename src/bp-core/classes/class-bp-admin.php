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
			require $this->admin_dir . 'bp-core-admin-components.php';
			require $this->admin_dir . 'bp-core-admin-pages.php';
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

			// Emails.
			add_filter( 'manage_' . bp_get_email_post_type() . '_posts_columns', array( $this, 'emails_register_situation_column' ) );
			add_action( 'manage_' . bp_get_email_post_type() . '_posts_custom_column', array( $this, 'emails_display_situation_column_data' ), 10, 2 );

			// Hello BuddyBoss/App.
			add_action( 'admin_footer', array( $this, 'document_extension_mime_type_check_screen' ) );
			add_action( 'admin_footer', array( $this, 'video_extension_mime_type_check_screen' ) );
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

			// DeRegisters jquery-ui-style from the WP Job Manager plugin in WP admin /wp-admin/admin.php?page=bp-profile-setup page.
			add_action( 'admin_enqueue_scripts', array( $this, 'deregister_wp_job_manager_shared_assets' ), 21 );

			add_action( 'admin_menu', array( $this, 'bp_emails_add_sub_menu_page_admin_menu' ) );
			add_action( bp_core_admin_hook(), array( $this, 'bp_emails_add_sub_menu_page_admin_menu' ) );

			add_action( 'admin_menu', array( $this, 'bp_add_main_menu_page_admin_menu' ) );
			add_action( 'admin_menu', array( $this, 'adjust_buddyboss_menus' ), 100 );

			add_action( 'admin_footer', array( $this, 'bb_display_update_plugin_information' ) );
		}

		/**
		 * DeRegisters jquery-ui-style from the WP Job Manager plugin in WP admin /wp-admin/admin.php?page=bp-profile-setup page.
		 *
		 * @since BuddyBoss 1.0.0
		 */
		public function deregister_wp_job_manager_shared_assets() {

			global $pagenow, $current_screen;

			if ( is_plugin_active( 'wp-job-manager/wp-job-manager.php' ) && isset( $_GET['page'] ) && 'bp-profile-setup' === $_GET['page'] && 'admin.php' === $pagenow && isset( $current_screen->id ) && 'buddyboss_page_bp-profile-setup' === $current_screen->id ) {

				wp_dequeue_style( 'jquery-ui-style' );
				wp_deregister_style( 'jquery-ui-style' );

			}

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

			// Add the option pages.
			$hooks[] = add_submenu_page(
				$this->settings_page,
				__( 'BuddyBoss Components', 'buddyboss' ),
				__( 'Components', 'buddyboss' ),
				$this->capability,
				'bp-components',
				'bp_core_admin_components_settings'
			);

			$hooks[] = add_submenu_page(
				$this->settings_page,
				__( 'Pages', 'buddyboss' ),
				__( 'Pages', 'buddyboss' ),
				$this->capability,
				'bp-pages',
				'bp_core_admin_pages_settings'
			);

			$hooks[] = add_submenu_page(
				$this->settings_page,
				__( 'BuddyBoss Settings', 'buddyboss' ),
				__( 'Settings', 'buddyboss' ),
				$this->capability,
				'bp-settings',
				'bp_core_admin_settings'
			);

			$hooks[] = add_submenu_page(
				$this->settings_page,
				__( 'Plugin Integrations', 'buddyboss' ),
				__( 'Integrations', 'buddyboss' ),
				$this->capability,
				'bp-integrations',
				'bp_core_admin_integrations'
			);

			$hooks[] = add_submenu_page(
				$this->settings_page,
				__( 'Upgrade', 'buddyboss' ),
				sprintf(
					/* translators: New Tag */
					__( 'Upgrade %s', 'buddyboss' ),
					'<span class="bb-upgrade-nav-tag">' . esc_html__( 'New', 'buddyboss' ) . '</span>'
				),
				$this->capability,
				'bb-upgrade',
				array( $this, 'bp_upgrade_screen' )
			);

			// Credits.
			$hooks[] = add_submenu_page(
				$this->settings_page,
				__( 'Credits', 'buddyboss' ),
				__( 'Credits', 'buddyboss' ),
				$this->capability,
				'bp-credits',
				array( $this, 'bp_credits_screen' )
			);
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
				'admin.php?page=bp-integrations&tab=bp-compatibility'
			);

			// Add the option pages.
			$hooks[] = add_submenu_page(
				$this->settings_page,
				__( 'BuddyBoss Components', 'buddyboss' ),
				__( 'Components', 'buddyboss' ),
				$this->capability,
				'bp-components',
				'bp_core_admin_components_settings'
			);

			$hooks[] = add_submenu_page(
				$this->settings_page,
				__( 'Pages', 'buddyboss' ),
				__( 'Pages', 'buddyboss' ),
				$this->capability,
				'bp-pages',
				'bp_core_admin_pages_settings'
			);

			$hooks[] = add_submenu_page(
				$this->settings_page,
				__( 'BuddyBoss Settings', 'buddyboss' ),
				__( 'Settings', 'buddyboss' ),
				$this->capability,
				'bp-settings',
				'bp_core_admin_settings'
			);

			$hooks[] = add_submenu_page(
				$this->settings_page,
				__( 'Plugin Integrations', 'buddyboss' ),
				__( 'Integrations', 'buddyboss' ),
				$this->capability,
				'bp-integrations',
				'bp_core_admin_integrations'
			);

			$hooks[] = add_submenu_page(
				$this->settings_page,
				__( 'Upgrade', 'buddyboss' ),
				sprintf(
					/* translators: New Tag */
					__( 'Upgrade %s', 'buddyboss' ),
					'<span class="bb-upgrade-nav-tag">' . esc_html__( 'New', 'buddyboss' ) . '</span>'
				),
				$this->capability,
				'bb-upgrade',
				array( $this, 'bp_upgrade_screen' )
			);

			// Credits.
			$hooks[] = add_submenu_page(
				$this->settings_page,
				__( 'Credits', 'buddyboss' ),
				__( 'Credits', 'buddyboss' ),
				$this->capability,
				'bp-credits',
				array( $this, 'bp_credits_screen' )
			);

			// ReadyLaunch.
			$hooks[] = add_submenu_page(
				$this->settings_page,
				__( 'ReadyLaunch', 'buddyboss' ),
				sprintf(
					/* translators: New Tag */
					__( 'ReadyLaunch %s', 'buddyboss' ),
					'<span class="bb-rl-nav-tag color-green">' . esc_html__( 'BETA', 'buddyboss' ) . '</span>'
				),
				$this->capability,
				'bb-readylaunch',
				'bb_readylaunch_settings_page_html',
				99
			);

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

			// For network-wide configs, add a link to (the root site's) Emails screen.
			if ( is_network_admin() && bp_is_network_activated() ) {
				$email_labels = bp_get_email_post_type_labels();
				$email_url    = get_admin_url( bp_get_root_blog_id(), 'edit.php?post_type=' . bp_get_email_post_type() );

				$hooks[] = add_menu_page(
					$email_labels['name'],
					$email_labels['menu_name'],
					$this->capability,
					'',
					'',
					'dashicons-email-alt',
					26
				);

				// Hack: change the link to point to the root site's admin, not the network admin.
				// $GLOBALS['menu'][26][2] = esc_url_raw( $email_url );
			}

			// foreach( $hooks as $hook ) {
			// add_action( "admin_head-$hook", 'bp_core_modify_admin_menu_highlight' );
			// }
		}

		/**
		 * Output the credits screen.
		 *
		 * @since BuddyBoss 1.0.0
		 */
		public function bp_credits_screen() {
			?>

		<div class="wrap">
			<h2 class="nav-tab-wrapper"><?php bp_core_admin_tabs( __( 'Credits', 'buddyboss' ) ); ?></h2>
			<?php include $this->admin_dir . 'templates/credit-screen.php'; ?>
		</div>

			<?php

		}

		/**
		 * Output the upgrade screen.
		 *
		 * @since BuddyBoss 2.6.30
		 */
		public function bp_upgrade_screen() {
			$active_tab = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : 'bb-upgrade'; // phpcs:ignore
			?>
			<div class="wrap wrap--upgrade">
				<div class="advance-tab-header">
					<div class="advance-brand">
						<img alt="" class="upgrade-brand" src="<?php echo esc_url( buddypress()->plugin_url . 'bp-core/images/admin/credits-buddyboss.png' ); ?>" />
					</div>
					<div class="nav-settings-subsubsub">
						<ul class="subsubsub">
							<?php bb_core_upgrade_admin_tabs(); ?>
						</ul>
					</div>
					<div class="adv-sep"></div>
					<div class="advance-actions">
						<a href="https://support.buddyboss.com/" target="_blank" class="advance-action-link"><?php esc_html_e( 'Contact', 'buddyboss' ); ?><i class="bb-icon-l bb-icon-arrow-up"></i></a>
						<a href="https://www.buddyboss.com/bbwebupgrade" target="_blank" class="advance-nav-action advance-nav-action--upgrade">
							<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
								<path d="M23.25 7.49999C23.2504 7.08886 23.1542 6.68339 22.9691 6.31625C22.7841 5.94911 22.5154 5.63057 22.1847 5.38629C21.854 5.14201 21.4705 4.97882 21.0652 4.90988C20.6599 4.84093 20.2441 4.86815 19.8512 4.98934C19.4583 5.11054 19.0994 5.32232 18.8034 5.60762C18.5074 5.89293 18.2825 6.24378 18.1469 6.6319C18.0113 7.02003 17.9688 7.43459 18.0227 7.84216C18.0767 8.24974 18.2256 8.63895 18.4575 8.97843L15.946 12.0722L13.6875 6.88124C14.0994 6.53457 14.3948 6.06964 14.5335 5.54946C14.6723 5.02928 14.6477 4.47902 14.4632 3.97325C14.2787 3.46749 13.9432 3.03069 13.502 2.72206C13.0609 2.41343 12.5356 2.24789 11.9972 2.24789C11.4588 2.24789 10.9335 2.41343 10.4924 2.72206C10.0512 3.03069 9.71569 3.46749 9.53118 3.97325C9.34667 4.47902 9.32213 5.02928 9.46089 5.54946C9.59965 6.06964 9.89499 6.53457 10.3069 6.88124L8.05408 12.0694L5.54251 8.97562C5.86515 8.50288 6.02443 7.93765 5.99612 7.36601C5.9678 6.79437 5.75343 6.24765 5.38566 5.80912C5.01788 5.37058 4.51686 5.06426 3.95889 4.9368C3.40092 4.80935 2.81659 4.86775 2.29488 5.1031C1.77317 5.33846 1.34268 5.73788 1.06897 6.24053C0.795258 6.74317 0.693327 7.32151 0.778699 7.88744C0.864072 8.45338 1.13207 8.97591 1.54188 9.37544C1.95169 9.77498 2.48084 10.0296 3.04876 10.1006L4.40626 18.2466C4.46462 18.5968 4.64532 18.9149 4.9162 19.1444C5.18708 19.374 5.5306 19.4999 5.88564 19.5H18.1144C18.4694 19.4999 18.8129 19.374 19.0838 19.1444C19.3547 18.9149 19.5354 18.5968 19.5938 18.2466L20.9503 10.1044C21.5852 10.0251 22.1692 9.71667 22.5927 9.23709C23.0162 8.75751 23.2499 8.13978 23.25 7.49999ZM12 3.74999C12.2225 3.74999 12.44 3.81597 12.625 3.93959C12.81 4.06321 12.9542 4.23891 13.0394 4.44448C13.1245 4.65004 13.1468 4.87624 13.1034 5.09447C13.06 5.3127 12.9528 5.51316 12.7955 5.67049C12.6382 5.82782 12.4377 5.93497 12.2195 5.97838C12.0013 6.02179 11.7751 5.99951 11.5695 5.91436C11.3639 5.82921 11.1882 5.68502 11.0646 5.50001C10.941 5.31501 10.875 5.0975 10.875 4.87499C10.875 4.57663 10.9935 4.29048 11.2045 4.0795C11.4155 3.86852 11.7016 3.74999 12 3.74999ZM2.25001 7.49999C2.25001 7.27749 2.316 7.05998 2.43961 6.87498C2.56323 6.68997 2.73893 6.54578 2.9445 6.46063C3.15006 6.37548 3.37626 6.3532 3.59449 6.39661C3.81272 6.44002 4.01318 6.54716 4.17051 6.7045C4.32784 6.86183 4.43499 7.06229 4.4784 7.28052C4.52181 7.49875 4.49953 7.72495 4.41438 7.93051C4.32923 8.13608 4.18504 8.31178 4.00003 8.4354C3.81503 8.55901 3.59752 8.62499 3.37501 8.62499C3.07665 8.62499 2.7905 8.50647 2.57952 8.29549C2.36854 8.08451 2.25001 7.79836 2.25001 7.49999ZM18.1144 18H5.88564L4.58064 10.1737L7.66783 13.9687C7.73775 14.0561 7.82632 14.1267 7.92706 14.1753C8.02779 14.224 8.13814 14.2495 8.25002 14.25C8.28388 14.2501 8.31771 14.248 8.35127 14.2434C8.47904 14.2261 8.60017 14.176 8.70297 14.0982C8.80577 14.0204 8.88677 13.9173 8.93814 13.7991L11.685 7.48031C11.8942 7.50654 12.1058 7.50654 12.315 7.48031L15.0619 13.7991C15.1133 13.9173 15.1943 14.0204 15.2971 14.0982C15.3999 14.176 15.521 14.2261 15.6488 14.2434C15.6823 14.248 15.7162 14.2501 15.75 14.25C15.8619 14.2495 15.9722 14.224 16.073 14.1753C16.1737 14.1267 16.2623 14.0561 16.3322 13.9687L19.4194 10.17L18.1144 18ZM20.625 8.62499C20.4025 8.62499 20.185 8.55901 20 8.4354C19.815 8.31178 19.6708 8.13608 19.5857 7.93051C19.5005 7.72495 19.4782 7.49875 19.5216 7.28052C19.565 7.06229 19.6722 6.86183 19.8295 6.7045C19.9869 6.54716 20.1873 6.44002 20.4055 6.39661C20.6238 6.3532 20.85 6.37548 21.0555 6.46063C21.2611 6.54578 21.4368 6.68997 21.5604 6.87498C21.684 7.05998 21.75 7.27749 21.75 7.49999C21.75 7.79836 21.6315 8.08451 21.4205 8.29549C21.2095 8.50647 20.9234 8.62499 20.625 8.62499Z" fill="white"/>
							</svg>
							<?php esc_html_e( 'Upgrade Now', 'buddyboss' ); ?>
						</a>
					</div>
				</div>
				<?php
				if ( 'bb-integrations' === $active_tab ) {
					include $this->admin_dir . 'templates/upgrade-integrations-screen.php';
				} elseif ( 'bb-performance-tester' === $active_tab ) {
					include $this->admin_dir . 'templates/upgrade-performance-tester-screen.php';
				} else {
					include $this->admin_dir . 'templates/upgrade-screen.php';
				}
				?>
			</div>
			<?php
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
				$email_url = 'edit.php?post_type=' . bp_get_email_post_type();
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
				$email_url = get_admin_url( bp_get_root_blog_id(), 'edit.php?post_type=' . bp_get_email_post_type() ); // buddyboss-settings
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
			require_once $this->admin_dir . '/settings/bp-admin-setting-general.php';
			require_once $this->admin_dir . '/settings/bp-admin-setting-xprofile.php';
			require_once $this->admin_dir . '/settings/bp-admin-setting-activity.php';
			require_once $this->admin_dir . '/settings/bp-admin-setting-groups.php';
			require_once $this->admin_dir . '/settings/bp-admin-setting-friends.php';
			require_once $this->admin_dir . '/settings/bp-admin-setting-messages.php';
			require_once $this->admin_dir . '/settings/bp-admin-setting-notifications.php';
			require_once $this->admin_dir . '/settings/bp-admin-setting-registration.php';
			require_once $this->admin_dir . '/settings/bp-admin-setting-forums.php';
			require_once $this->admin_dir . '/settings/bp-admin-setting-search.php';
			require_once $this->admin_dir . '/settings/bp-admin-setting-media.php';
			require_once $this->admin_dir . '/settings/bp-admin-setting-credit.php';
			require_once $this->admin_dir . '/settings/bp-admin-setting-invites.php';
			require_once $this->admin_dir . '/settings/bp-admin-setting-document.php';
			require_once $this->admin_dir . '/settings/bp-admin-setting-moderation.php';
			require_once $this->admin_dir . '/settings/bp-admin-setting-video.php';
			require_once $this->admin_dir . '/settings/bp-admin-setting-labs.php';
			require_once $this->admin_dir . '/settings/bb-admin-setting-reactions.php';
			require_once $this->admin_dir . '/settings/bb-admin-setting-performance.php';

			// @todo: used for bp-performance will enable in feature.
			// require_once $this->admin_dir . '/settings/bp-admin-setting-performance.php';
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
					'settings'      => '<a href="' . esc_url( bp_get_admin_url( 'admin.php?page=bp-settings' ) ) . '">' . esc_html__( 'Settings', 'buddyboss' ) . '</a>',
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

			// Credits page.
			remove_submenu_page( 'index.php', 'bp-credits' );
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

			// Enqueue only post_type is member type and group type.
			if (
				0 === strpos( get_current_screen()->id, 'bp-group-type' ) ||
				0 === strpos( get_current_screen()->id, 'bp-member-type' )
			) {
				wp_enqueue_style( 'wp-color-picker' );
				wp_enqueue_script( 'wp-color-picker' );
			}
		}

		/** About BuddyBoss and BuddyBoss App ********************************************/

		/**
		 * Output the document mime type checker screen.
		 */
		public function document_extension_mime_type_check_screen() {
			if ( isset( $_GET ) && isset( $_GET['tab'] ) && 'bp-document' !== $_GET['tab'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				return;
			}

			include $this->admin_dir . 'templates/check-document-mime-type.php';
		}

		/**
		 * Output the video mime type checker screen.
		 *
		 * @since BuddyBoss 1.7.0
		 */
		public function video_extension_mime_type_check_screen() {
			if ( isset( $_GET ) && isset( $_GET['tab'] ) && 'bp-video' !== $_GET['tab'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				return;
			}

			include $this->admin_dir . 'templates/check-video-mime-type.php';
		}

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

		/** Emails ****************************************************************/

		/**
		 * Registers 'Situations' column on Emails dashboard page.
		 *
		 * @since BuddyPress 2.6.0
		 *
		 * @param array $columns Current column data.
		 * @return array
		 */
		public function emails_register_situation_column( $columns = array() ) {
			$situation = array(
				'situation' => __( 'Situations', 'buddyboss' ),
			);

			// Inject our 'Situations' column just before the last 'Date' column.
			return array_slice( $columns, 0, -1, true ) + $situation + array_slice( $columns, -1, null, true );
		}

		/**
		 * Output column data for our custom 'Situations' column.
		 *
		 * @since BuddyPress 2.6.0
		 *
		 * @param string $column  Current column name.
		 * @param int    $post_id Current post ID.
		 */
		public function emails_display_situation_column_data( $column = '', $post_id = 0 ) {
			if ( 'situation' !== $column ) {
				return;
			}

			// Grab email situations for the current post.
			$situations = wp_list_pluck( get_the_terms( $post_id, bp_get_email_tax_type() ), 'description' );

			// Output each situation as a list item.
			echo '<ul><li>';
			echo implode( '</li><li>', $situations );
			echo '</li></ul>';
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
			array_push( $custom_menus, 'edit.php?post_type=' . bp_get_email_post_type() );

			if ( is_network_admin() && bp_is_network_activated() ) {
				array_push(
					$custom_menus,
					get_admin_url( bp_get_root_blog_id(), 'edit.php?post_type=' . bp_get_email_post_type() )
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
			// Check the transient to see if we've just updated the plugin.
			global $bp;
			include trailingslashit( $bp->plugin_dir . 'bp-core/admin' ) . 'templates/update-buddyboss.php';
			delete_option( '_bb_is_update' );
		}
	}
endif; // End class_exists check.
