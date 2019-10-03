<?php
/**
 * Plugin Name: BuddyBoss Platform
 * Plugin URI:  https://buddyboss.com/
 * Description: The BuddyBoss Platform adds community features to WordPress. Member Profiles, Activity Feeds, Direct Messaging, Notifications, and more!
 * Author:      BuddyBoss
 * Author URI:  https://buddyboss.com/
 * Version:     1.1.9
 * Text Domain: buddyboss
 * Domain Path: /languages/
 * License:     GPLv2 or later (license.txt)
 */

/**
 * These files should always remain compatible with the minimum version of
 * PHP supported by WordPress.
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;


if ( ! defined( 'BP_PLATFORM_VERSION' ) ) {
	define( 'BP_PLATFORM_VERSION', '1.1.9' );
}

global $bp_incompatible_plugins;
global $is_bp_active;
global $bp_plugin_file;
global $is_bb_active;
global $bb_plugin_file;
global $bp_sitewide_plugins;
global $bp_plugins;
$bp_incompatible_plugins = array();
$is_bp_active            = false;
$bp_plugin_file          = 'buddypress/bp-loader.php';

$is_bb_active        = false;
$bb_plugin_file      = 'bbpress/bbpress.php';
$bp_sitewide_plugins = array();

if ( is_multisite() ) {
	// get network-activated plugins
	foreach ( get_site_option( 'active_sitewide_plugins', array() ) as $key => $value ) {
		$bp_sitewide_plugins[] = $key;
	}
}

$bp_plugins   = array_merge( $bp_sitewide_plugins, get_option( 'active_plugins' ) );
$bp_plugins[] = isset( $_GET['plugin'] ) ? $_GET['plugin'] : array();

// check if BuddyPress is activated
if ( in_array( $bp_plugin_file, $bp_plugins ) ) {
	$is_bp_active = true;
}

// check if bbPress is activated
if ( in_array( $bb_plugin_file, $bp_plugins ) ) {
	$is_bb_active = true;
}

/**
 * Prevent running BuddyBoss Platform if any incompatible plugins are active.
 * Show admin error message instead.
 */
$bp_incompatible_plugins_list = array(
	'buddypress-global-search/buddypress-global-search.php' => __( 'The BuddyBoss Platform can\'t work while BuddyPress Global Search plugin is active. Global Search functionality is built into the platform. Please deactivate BuddyPress Global Search first, if you wish to activate BuddyBoss Platform.', 'buddyboss' ),
	'buddypress-followers/loader.php' => __( 'The BuddyBoss Platform can\'t work while BuddyPress Follow plugin is active. Follow functionality is built into the platform. Please deactivate BuddyPress Follow first, if you wish to activate BuddyBoss Platform.', 'buddyboss' ),
);

foreach ( $bp_incompatible_plugins_list as $incompatible_plugin => $error_message ) {
	if ( in_array( $incompatible_plugin, $bp_plugins ) ) {
		$bp_incompatible_plugins[] = $error_message;
	}
}

if ( empty( $is_bp_active ) && empty( $is_bb_active ) && empty( $bp_incompatible_plugins ) ) {

	if ( ! defined( 'BP_VERSION' ) ) {
		define( 'BP_VERSION', '4.3.0' );
	}

	/**
	 * Action for removing the spoofing of BuddyPress and bbPress.
     *
	 * @since BuddyBoss 1.1.10
	 */
	function bp_core_unset_bbpress_buddypress_active() {
		remove_filter( 'option_active_plugins', 'bp_core_set_bbpress_buddypress_active', 10 );
	}

	/**
	 * Action fire before option updated/save list activated plugins.
	 *
	 * @since BuddyBoss 1.1.10
	 */
	function bp_core_unset_bbpress_buddypress_option() {
	    add_filter( 'pre_update_option_active_plugins', 'pre_update_option_active_plugins' );
    }

	/**
	 * Removing the spoofing of BuddyPress and bbPress when option updated.
	 *
	 * @since BuddyBoss 1.1.10
	 */
    function pre_update_option_active_plugins( $value ) {
	    global $bp_plugin_file, $bb_plugin_file;
	    $value = array_diff( $value, array( $bb_plugin_file, $bp_plugin_file ) );
	    return $value;
    }

	/**
	 * Again set the spoofing of BuddyPress and bbPress on Admin Notices
     *
	 * @since BuddyBoss 1.1.10
	 */
    function bp_core_set_bbpress_buddypress_on_admin_notices() {

	    add_filter( 'option_active_plugins', 'bp_core_set_bbpress_buddypress_active', 10, 2 );
    }

	/**
	 * Filter for setting the spoofing of BuddyPress.
	 *
	 * @param $value
	 * @param $option
	 *
	 * @since BuddyBoss 1.0.0
	 * @return mixed
	 */
	function bp_core_set_bbpress_buddypress_active( $value, $option ) {

		global $bp_plugin_file, $bb_plugin_file;

		// Do not add the "bbpress/bbpress.php" & "buddypress/bp-loader.php" on "/wp-admin/plugins.php" page otherwise it will show the plugin file not exists error.
		if ( is_network_admin()
		     || strpos( $_SERVER['REQUEST_URI'], '/wp-admin/plugins.php' ) !== false
		     || strpos( $_SERVER['REQUEST_URI'], '/wp-admin/admin-ajax.php' ) !== false
		) {

			/**
			 * Add this so the spoofing plugin does not get loaded by WordPress
			 */
			add_action( 'muplugins_loaded', 'bp_core_unset_bbpress_buddypress_active' );
			add_action( 'pre_current_active_plugins', 'bp_core_unset_bbpress_buddypress_active' );

			/**
			 * Add this so that spoofing plugin does not get added into DB at the time of plugin activation
			 */
			add_action( 'activate_plugin', 'bp_core_unset_bbpress_buddypress_active' );


			if ( empty( $_REQUEST['action'] ) ) {
				/**
				 * Add this so that plugin table does not show the spoofing plugin are activated
				 */
				add_action( 'admin_init', 'bp_core_unset_bbpress_buddypress_active', 100000 );

			}

            add_action( 'admin_notices', 'bp_core_set_bbpress_buddypress_on_admin_notices', -1 );
		}

		/**
		 * Add this so that spoofing plugin does not get added into DB at the time of plugin deactivation
		 */
		add_action( 'deactivated_plugin', 'bp_core_unset_bbpress_buddypress_option' );

		// Check if Forum Component is enabled if so then add
		if ( bp_is_active( 'forums' ) ) {
			array_push( $value, $bb_plugin_file );
		}
		array_push( $value, $bp_plugin_file );

		return $value;
	}

	if ( is_multisite() ) {
		/**
		 * Load Plugin after plugin is been loaded
		 */
		function bp_core_plugins_loaded_callback() {

			// Filter for setting the spoofing of BuddyPress.
			add_filter( 'option_active_plugins', 'bp_core_set_bbpress_buddypress_active', 10, 2 );
		}

		add_action( 'bp_init', 'bp_core_plugins_loaded_callback', 100 );
	} else {
		// Filter for setting the spoofing of BuddyPress.
		add_filter( 'option_active_plugins', 'bp_core_set_bbpress_buddypress_active', 10, 2 );
	}


	// Required PHP version.
	define( 'BP_REQUIRED_PHP_VERSION', '5.3.0' );

	/**
	 * The main function responsible for returning the one true BuddyBoss Instance to functions everywhere.
	 *
	 * Use this function like you would a global variable, except without needing
	 * to declare the global.
	 *
	 * Example: <?php $bp = buddypress(); ?>
	 *
	 * @return BuddyPress|null The one true BuddyPress Instance.
	 */
	function buddypress() {
		return BuddyPress::instance();
	}

	/**
	 * Adds an admin notice to installations that don't meet minimum PHP requirement.
	 *
	 * @since BuddyPress 2.8.0
	 */
	function bp_php_requirements_notice() {
		if ( ! current_user_can( 'update_core' ) ) {
			return;
		}

		?>

		<div id="message" class="error notice">
			<p><strong><?php esc_html_e( 'Your site does not support BuddyBoss Platform.', 'buddyboss' ); ?></strong>
			</p>
			<?php /* translators: 1: current PHP version, 2: required PHP version */ ?>
			<p><?php printf( esc_html__( 'Your site is currently running PHP version %1$s, while BuddyBoss Platform requires version %2$s or greater.', 'buddyboss' ), esc_html( phpversion() ), esc_html( BP_REQUIRED_PHP_VERSION ) ); ?></p>
			<p><?php esc_html_e( 'Please update your server or deactivate BuddyBoss Platform.', 'buddyboss' ); ?></p>
		</div>

		<?php
	}

	if ( version_compare( phpversion(), BP_REQUIRED_PHP_VERSION, '<' ) ) {
		add_action( 'admin_notices', 'bp_php_requirements_notice' );
		add_action( 'network_admin_notices', 'bp_php_requirements_notice' );

		return;
	} else {
		require dirname( __FILE__ ) . '/class-buddypress.php';

		// load the member switch class so all the hook prior to bp_init can be hook in
		require dirname( __FILE__ ) . '/bp-members/classes/class-bp-core-members-switching.php';

		/*
		 * Hook BuddyPress early onto the 'plugins_loaded' action.
		 *
		 * This gives all other plugins the chance to load before BuddyBoss Platform,
		 * to get their actions, filters, and overrides setup without
		 * BuddyBoss Platform being in the way.
		 */
		if ( defined( 'BUDDYPRESS_LATE_LOAD' ) ) {
			add_action( 'plugins_loaded', 'buddypress', (int) BUDDYPRESS_LATE_LOAD );

			$bp_forum_active = array_key_exists( 'forums', bp_get_option( 'bp-active-components', array() ) );

			// A lot of actions in bbpress require before component init,
			// hence we grab the pure db value and load the class
			// so all the hook prior to bp_init can be hook in
			if ( $bp_forum_active ) {
				require dirname( __FILE__ ) . '/bp-forums/classes/class-bbpress.php';
				add_action( 'plugins_loaded', 'bbpress', (int) BUDDYPRESS_LATE_LOAD );
			}

			// "And now here's something we hope you'll really like!"
		} else {
			$GLOBALS['bp'] = buddypress();

			$bp_forum_active = array_key_exists( 'forums', bp_get_option( 'bp-active-components', array() ) );
			if ( $bp_forum_active ) {
				require dirname( __FILE__ ) . '/bp-forums/classes/class-bbpress.php';
				$GLOBALS['bbp'] = bbpress();
			}
		}
	}
} else {
	/**
	 * Displays an admin notice when BuddyPress plugin is active.
	 *
	 * @since BuddyBoss 1.0.0
	 * @return void
	 */
	function bp_duplicate_notice() {

		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}

		global $bp_incompatible_plugins;
		global $bp_plugin_file;
		global $bb_plugin_file;
		global $bp_sitewide_plugins;
		global $is_bp_active;
		global $is_bb_active;
		global $bp_plugins;

		// Disable BuddyPress message
		if ( $is_bp_active ) {
			if ( is_multisite() && ( is_network_admin() && ! in_array( $bp_plugin_file, $bp_sitewide_plugins ) || in_array( $bp_plugin_file, $bp_plugins ) ) ) {
				return;
			}
			$bp_plugins_url = is_network_admin() ? network_admin_url( 'plugins.php' ) : admin_url( 'plugins.php' );
			$link_plugins   = sprintf( "<a href='%s'>%s</a>", $bp_plugins_url, __( 'deactivate', 'buddyboss' ) );
			?>

			<div id="message" class="error notice">
				<p><strong><?php esc_html_e( 'BuddyBoss Platform is disabled.', 'buddyboss' ); ?></strong></p>
				<p><?php printf( esc_html__( 'The BuddyBoss Platform can\'t work while BuddyPress plugin is active. Please %s BuddyPress to re-enable BuddyBoss Platform.', 'buddyboss' ), $link_plugins ); ?></p>
			</div>

			<?php
		}

		// Disable bbPress message
		if ( $is_bb_active ) {

			if ( is_multisite() && ( is_network_admin() && ! in_array( $bb_plugin_file, $bp_sitewide_plugins ) || in_array( $bb_plugin_file, $bp_plugins ) ) ) {
				return;
			}
			$bp_plugins_url = is_network_admin() ? network_admin_url( 'plugins.php' ) : admin_url( 'plugins.php' );
			$link_plugins   = sprintf( "<a href='%s'>%s</a>", $bp_plugins_url, __( 'deactivate', 'buddyboss' ) );
			?>

			<div id="message" class="error notice">
				<p><strong><?php esc_html_e( 'BuddyBoss Platform is disabled.', 'buddyboss' ); ?></strong></p>
				<p><?php printf( esc_html__( 'The BuddyBoss Platform can\'t work while bbPress plugin is active. Please %s bbPress to re-enable BuddyBoss Platform.', 'buddyboss' ), $link_plugins ); ?></p>
			</div>

			<?php
		}

		if ( ! empty( $bp_incompatible_plugins ) ) {
			foreach ( $bp_incompatible_plugins as $incompatible_plugin_message ) {
				?>
				<div id="message" class="error notice">
					<p><strong><?php esc_html_e( 'BuddyBoss Platform is disabled.', 'buddyboss' ); ?></strong></p>
					<?php
					printf( '<p>%s</p>', $incompatible_plugin_message );
					?>
				</div>
				<?php
			}
		}
	}

	/**
	 * You can't have bbPress and BuddyBoss Platform both active at the same time!
	 */
	add_action( 'admin_notices', 'bp_duplicate_notice' );
	add_action( 'network_admin_notices', 'bp_duplicate_notice' );
}
