<?php
/**
 * Main BuddyPress Admin Class.
 *
 * @package BuddyBoss
 * @subpackage CoreAdministration
 * @since BuddyPress 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( !class_exists( 'BP_Admin' ) ) :

/**
 * Load BuddyPress plugin admin area.
 *
 * @todo Break this apart into each applicable Component.
 *
 * @since BuddyPress 1.6.0
 */
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
	 *
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
		$this->admin_dir  = trailingslashit( $bp->plugin_dir  . 'bp-core/admin' ); // Admin path.
		$this->admin_url  = trailingslashit( $bp->plugin_url  . 'bp-core/admin' ); // Admin url.
		$this->images_url = trailingslashit( $this->admin_url . 'images'        ); // Admin images URL.
		$this->css_url    = trailingslashit( $this->admin_url . 'css'           ); // Admin css URL.
		$this->js_url     = trailingslashit( $this->admin_url . 'js'            ); // Admin css URL.

		// Main settings page.
		$this->settings_page = 'buddyboss-platform'; // always use custom menu item, instead of setting page

		// Main capability.
		$this->capability = bp_core_do_network_admin() ? 'manage_network_options' : 'manage_options';
	}

	/**
	 * Include required files.
	 *
	 * @since BuddyPress 1.6.0
	 */
	private function includes() {
		require( $this->admin_dir . 'bp-core-admin-actions.php'    );
		require( $this->admin_dir . 'bp-core-admin-settings.php'   );
		require( $this->admin_dir . 'bp-core-admin-functions.php'  );
		require( $this->admin_dir . 'bp-core-admin-components.php' );
		require( $this->admin_dir . 'bp-core-admin-pages.php'      );
		require( $this->admin_dir . 'bp-core-admin-slugs.php'      );
		require( $this->admin_dir . 'bp-core-admin-tools.php'      );
	}

	/**
	 * Set up the admin hooks, actions, and filters.
	 *
	 * @since BuddyPress 1.6.0
	 *
	 */
	private function setup_actions() {

		/* General Actions ***************************************************/

		// Add some page specific output to the <head>.
		add_action( 'bp_admin_head',            array( $this, 'admin_head'  ), 999 );

		// Add menu item to settings menu.
		add_action( 'admin_menu',               array( $this, 'site_admin_menus' ), 5 );
		add_action( bp_core_admin_hook(),       array( $this, 'admin_menus' ), 5 );
		add_action( bp_core_admin_hook(),       array( $this, 'adjust_buddyboss_menus' ), 100 );

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

		// Add a link to BuddyPress Hello in the admin bar.
		add_action( 'admin_bar_menu', array( $this, 'admin_bar_about_link' ), 100 );

		// Add a description of new BuddyPress tools in the available tools page.
		add_action( 'tool_box',            'bp_core_admin_available_tools_intro' );
		add_action( 'bp_network_tool_box', 'bp_core_admin_available_tools_intro' );

		// On non-multisite, catch.
		add_action( 'load-users.php', 'bp_core_admin_user_manage_spammers' );

		// Emails.
		add_filter( 'manage_' . bp_get_email_post_type() . '_posts_columns',       array( $this, 'emails_register_situation_column' ) );
		add_action( 'manage_' . bp_get_email_post_type() . '_posts_custom_column', array( $this, 'emails_display_situation_column_data' ), 10, 2 );

		// BuddyPress Hello.
		add_action( 'admin_footer', array( $this, 'about_screen' ) );

		/* Filters ***********************************************************/

		// Add link to settings page.
		add_filter( 'plugin_action_links',               array( $this, 'modify_plugin_action_links' ), 10, 2 );
		add_filter( 'network_admin_plugin_action_links', array( $this, 'modify_plugin_action_links' ), 10, 2 );

		// Add "Mark as Spam" row actions on users.php.
		add_filter( 'ms_user_row_actions', 'bp_core_admin_user_row_actions', 10, 2 );
		add_filter( 'user_row_actions',    'bp_core_admin_user_row_actions', 10, 2 );

		// Emails
		add_filter( 'bp_admin_menu_order', array( $this, 'emails_admin_menu_order' ), 20 );
	}

	/**
	 * Register site- or network-admin nav menu elements.
	 *
	 * Contextually hooked to site or network-admin depending on current configuration.
	 *
	 * @since BuddyPress 1.6.0
	 */
	public function admin_menus() {

		// Bail if user cannot moderate.
		if ( ! bp_current_user_can( 'manage_options' ) ) {
			return;
		}

		$hooks = array();

		// Changed in BP 1.6 . See bp_core_admin_backpat_menu().
		$hooks[] = add_menu_page(
			__( 'BuddyBoss', 'buddyboss' ),
			__( 'BuddyBoss', 'buddyboss' ),
			$this->capability,
			$this->settings_page,
			'bp_core_admin_backpat_menu',
			buddypress()->plugin_url . 'bp-core/images/admin/logo.svg',
			62
		);

		$hooks[] = add_submenu_page(
			'bp-general-settings',
			__( 'BuddyBoss Help', 'buddyboss' ),
			__( 'Help', 'buddyboss' ),
			$this->capability,
			'bp-general-settings',
			'bp_core_admin_backpat_page'
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

		if ( ! is_plugin_active( 'appboss/appboss.php' ) ) {
			$hooks[] = add_submenu_page(
				$this->settings_page,
				__( 'Mobile App', 'buddyboss' ),
				__( 'Mobile App', 'buddyboss' ),
				$this->capability,
				'bp-appboss',
				'bp_core_admin_appboss'
			);
		}

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
			$tools_parent,
			__( 'BuddyBoss Tools', 'buddyboss' ),
			__( 'BuddyBoss', 'buddyboss' ),
			$this->capability,
			'bp-tools',
			'bp_core_admin_tools'
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
			$GLOBALS['menu'][26][2] = esc_url_raw( $email_url );
		}

		// foreach( $hooks as $hook ) {
		// 	add_action( "admin_head-$hook", 'bp_core_modify_admin_menu_highlight' );
		// }
	}

	public function adjust_buddyboss_menus() {
		global $menu, $submenu;

		// only if login user has access to menu
		if ( ! isset( $submenu[ 'buddyboss-platform' ] ) ) {
			return;
		}

		// make sure app integration is last
		$app_menu = '';
		foreach ( $submenu[ 'buddyboss-platform' ] as $index => $pmenu ) {
			if ( $pmenu[2] == 'bp-appboss' ) {
				$app_menu = $pmenu;
				unset( $submenu[ 'buddyboss-platform' ][ $index ] );
				break;
			}
		}

		$submenu[ 'buddyboss-platform' ] = array_values( $submenu[ 'buddyboss-platform' ] );

		if ( $app_menu ) {
			$submenu[ 'buddyboss-platform' ][] = $app_menu;
		}

		// if there's no buddyboss plugin, don't do anything
		if (! array_key_exists('buddyboss-settings', $submenu)) {
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
			_x( 'Emails', 'screen heading', 'buddyboss' ),
			_x( 'Emails', 'screen heading', 'buddyboss' ),
			$this->capability,
			'bp-emails-customizer-redirect',
			'bp_email_redirect_to_customizer'
		);

		// Emails > Customize.
		$hooks[] = add_submenu_page(
			'edit.php?post_type=' . bp_get_email_post_type(),
			_x( 'Customize', 'email menu label', 'buddyboss' ),
			_x( 'Customize', 'email menu label', 'buddyboss' ),
			$this->capability,
			'bp-emails-customizer-redirect',
			'bp_email_redirect_to_customizer'
		);

		foreach( $hooks as $hook ) {
			add_action( "admin_head-$hook", 'bp_core_modify_admin_menu_highlight' );
		}
	}

	/**
	 * Register the settings.
	 *
	 * @since BuddyPress 1.6.0
	 *
	 */
	public function register_admin_settings() {

		$bp = buddypress();
		require_once trailingslashit( $bp->plugin_dir  . 'bp-core/classes' ) . '/class-bp-admin-tab.php';
		require_once trailingslashit( $bp->plugin_dir  . 'bp-core/classes' ) . '/class-bp-admin-setting-tab.php';
		require_once $this->admin_dir . '/settings/bp-admin-setting-general.php';
		require_once $this->admin_dir . '/settings/bp-admin-setting-xprofile.php';
		require_once $this->admin_dir . '/settings/bp-admin-setting-activity.php';
		require_once $this->admin_dir . '/settings/bp-admin-setting-groups.php';
		require_once $this->admin_dir . '/settings/bp-admin-setting-friends.php';
		require_once $this->admin_dir . '/settings/bp-admin-setting-messages.php';
		require_once $this->admin_dir . '/settings/bp-admin-setting-registration.php';
		require_once $this->admin_dir . '/settings/bp-admin-setting-forums.php';
		require_once $this->admin_dir . '/settings/bp-admin-setting-search.php';
		require_once $this->admin_dir . '/settings/bp-admin-setting-credit.php';
		require_once $this->admin_dir . '/settings/bp-admin-setting-invites.php';
	}

	/**
	 * Register the integrations.
	 *
	 * @since BuddyPress 1.6.0
	 *
	 */
	public function register_admin_integrations() {

		$bp = buddypress();
		require_once trailingslashit( $bp->plugin_dir  . 'bp-core/classes' ) . '/class-bp-admin-tab.php';
		require_once trailingslashit( $bp->plugin_dir  . 'bp-core/classes' ) . '/class-bp-admin-integration-tab.php';

		// integrations should be loaded in its loader file
	}

	/**
	 * Add a link to BuddyPress Hello to the admin bar.
	 *
	 * @since BuddyPress 1.9.0
	 * @since BuddyPress 3.0.0 Hooked at priority 100 (was 15).
	 *
	 * @param WP_Admin_Bar $wp_admin_bar
	 */
	public function admin_bar_about_link( $wp_admin_bar ) {
		if ( ! is_user_logged_in() ) {
			return;
		}

		$wp_admin_bar->add_menu( array(
			'parent' => 'wp-logo',
			'id'     => 'bp-about',
			'title'  => esc_html_x( 'Hello, BuddyBoss!', 'Colloquial alternative to "learn about BuddyBoss"', 'buddyboss' ),
			'href'   => bp_get_admin_url( '?hello=buddypress' ),
			'meta'   => array(
				'class' => 'say-hello-buddypress',
			),
		) );
	}

	/**
	 * Add Settings link to plugins area.
	 *
	 * @since BuddyPress 1.6.0
	 * @since BuddyBoss 1.0.0 
	 * Updated the Settings path
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
		return array_merge( $links, array(
			'settings'    => '<a href="' . esc_url( bp_get_admin_url( 'admin.php?page=bp-components' ) ) . '">' . esc_html_x( 'Settings', 'buddyboss' ) . '</a>',
			'about'    => '<a href="' . esc_url( bp_get_admin_url( '?hello=buddypress' ) ) . '">' . esc_html_x( 'About', 'buddyboss' ) . '</a>'
		) );
	}

	/**
	 * Add some general styling to the admin area.
	 *
	 * @since BuddyPress 1.6.0
	 */
	public function admin_head() {

		// Settings pages.
		remove_submenu_page( $this->settings_page, $this->settings_page );

		// remove_submenu_page( $this->settings_page, 'bp-page-settings' );
		// remove_submenu_page( $this->settings_page, 'bp-settings'      );
		// remove_submenu_page( $this->settings_page, 'bp-credits'       );

		// Network Admin Tools.
		remove_submenu_page( 'network-tools', 'network-tools' );

		// About and Credits pages.
		remove_submenu_page( 'index.php', 'bp-about'   );
		remove_submenu_page( 'index.php', 'bp-credits' );
	}

	/**
	 * Add some general styling to the admin area.
	 *
	 * @since BuddyPress 1.6.0
	 */
	public function enqueue_scripts() {
		wp_enqueue_style( 'bp-admin-common-css' );

		// BuddyPress Hello
		if ( 0 === strpos( get_current_screen()->id, 'dashboard' ) && ! empty( $_GET['hello'] ) && $_GET['hello'] === 'buddypress' ) {
			wp_enqueue_style( 'bp-hello-css' );
			wp_enqueue_script( 'bp-hello-js' );
		}
	}

	/** About *****************************************************************/

	/**
	 * Output the BuddyPress Hello template.
	 *
	 * @since BuddyPress 1.7.0 Screen content.
	 * @since BuddyPress 3.0.0 Now outputs BuddyPress Hello template.
	 */
	public function about_screen() {
		if ( 0 !== strpos( get_current_screen()->id, 'dashboard' ) || empty( $_GET['hello'] ) || $_GET['hello'] !== 'buddypress' ) {
			return;
		}

		include $this->admin_dir . 'templates/about-screen.php';
	}

	/**
	 * Output the credits screen.
	 *
	 * Hardcoding this in here is pretty janky. It's fine for now, but we'll
	 * want to leverage api.wordpress.org eventually.
	 *
	 * @since BuddyPress 1.7.0
	 */
	public function credits_screen() {
		include $this->admin_dir . 'templates/about-screen.php';
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
			'situation' => _x( 'Situations', 'Email post type', 'buddyboss' )
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
			$pre     = strpos( $version, '-' );

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
		$styles = apply_filters( 'bp_core_admin_register_styles', array(
			// Legacy.
			'bp-admin-common-css' => array(
				'file'         => $common_css,
				'dependencies' => array(),
			),

			// 2.5
			'bp-customizer-controls' => array(
				'file'         => "{$url}customizer-controls{$min}.css",
				'dependencies' => array(),
			),

			// 3.0
			'bp-hello-css' => array(
				'file'         => "{$url}hello{$min}.css",
				'dependencies' => array( 'bp-admin-common-css' ),
			),
		) );

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
		$scripts = apply_filters( 'bp_core_admin_register_scripts', array(
			// 2.5
			'bp-customizer-controls' => array(
				'file'         => "{$url}customizer-controls{$min}.js",
				'dependencies' => array( 'jquery' ),
				'footer'       => true,
			),

			// 3.0
			'bp-hello-js' => array(
				'file'         => "{$url}hello{$min}.js",
				'dependencies' => array(),
				'footer'       => true,
			),
		) );

		$version = bp_get_version();

		foreach ( $scripts as $id => $script ) {
			wp_register_script( $id, $script['file'], $script['dependencies'], $version, $script['footer'] );
		}
	}
}
endif; // End class_exists check.
