<?php
/**
 * The BuddyBoss Platform.
 *
 * BuddyBoss is social networking software with a twist.
 *
 * @package BuddyBoss\Main
 * @since BuddyPress 1.0.0
 */

/**
 * Plugin Name: BuddyBoss Platform
 * Plugin URI:  https://buddyboss.com/
 * Description: The BuddyBoss Platform adds community features to WordPress. Member Profiles, Activity Feeds, Direct Messaging, Notifications, and more!
 * Author:      BuddyBoss
 * Author URI:  https://buddyboss.com/
 * Version:     1.0.0
 * Text Domain: buddyboss
 * Domain Path: /languages/
 * License:     GPLv2 or later (license.txt)
 */

/**
 * This files should always remain compatible with the minimum version of
 * PHP supported by WordPress.
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Make sure BuddyPress and bbPress are not activated.
 *
 * We're not using 'is_plugin_active' functions because you need to include the
 * /wp-admin/includes/plugin.php file in order to use that function.
 *
 * @since BuddyBoss 1.0.0
 */

$is_bp_active   = false;
$bp_plugin_file = 'buddypress/bp-loader.php';

$is_bb_active   = false;
$bb_plugin_file = 'bbpress/bbpress.php';

if ( is_multisite() ) {
	// get network-activated plugins
	$plugins = get_site_option( 'active_sitewide_plugins' );

	if ( isset( $plugins[ $bp_plugin_file ] ) ) {
		$is_bp_active = true;
	}

	if ( isset( $plugins[ $bb_plugin_file ] ) ) {
		$is_bb_active = true;
	}
}

if ( ! $is_bp_active ) {
	// get activated plugins
	$plugins = get_option( 'active_plugins' );

	if ( in_array( $bp_plugin_file, $plugins ) ) {
		$is_bp_active = true;
	}

	if ( in_array( $bb_plugin_file, $plugins ) ) {
		$is_bb_active = true;
	}
}

if ( $is_bp_active ) {
	require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
	deactivate_plugins( plugin_basename( 'buddyboss-platform/bp-loader.php' ) );
	$plugins_url  = is_network_admin() ? network_admin_url( 'plugins.php' ) : admin_url( 'plugins.php' );
	$link_plugins = sprintf( "<a href='%s'>%s</a>", $plugins_url, __( 'deactivate', 'buddyboss' ) );
	wp_die( sprintf( esc_html__( 'BuddyBoss Platform is disabled. The BuddyBoss Platform can\'t work while BuddyPress plugin is active. Please %s BuddyPress to re-enable BuddyBoss Platform.', 'buddyboss' ), $link_plugins ), 'BuddyBoss Platform dependency check', array( 'back_link' => true ) );

	return;
}

if ( $is_bp_active ) {

	/**
	 * Displays an admin notice when BuddyPress plugin is active.
	 *
	 * @since BuddyBoss 1.0.0
	 * @return void
	 */
	function bp_duplicate_buddypress_notice() {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}

		$plugins_url  = is_network_admin() ? network_admin_url( 'plugins.php' ) : admin_url( 'plugins.php' );
		$link_plugins = sprintf( "<a href='%s'>%s</a>", $plugins_url, __( 'deactivate', 'buddyboss' ) );
		?>

        <div id="message" class="error notice">
            <p><strong><?php esc_html_e( 'BuddyBoss Platform is disabled.', 'buddyboss' ); ?></strong></p>
            <p><?php printf( esc_html__( 'The BuddyBoss Platform can\'t work while BuddyPress plugin is active. Please %s BuddyPress to re-enable BuddyBoss Platform.', 'buddyboss' ), $link_plugins ); ?></p>
        </div>

		<?php
	}

	/**
	 * You can't have BuddyPress and BuddyBoss Platform both active at the same time!
	 */
	add_action( 'admin_notices', 'bp_duplicate_buddypress_notice' );
	add_action( 'network_admin_notices', 'bp_duplicate_buddypress_notice' );

}

if ( $is_bb_active ) {
	require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
	deactivate_plugins( plugin_basename( 'buddyboss-platform/bp-loader.php' ) );
	$plugins_url  = is_network_admin() ? network_admin_url( 'plugins.php' ) : admin_url( 'plugins.php' );
	$link_plugins = sprintf( "<a href='%s'>%s</a>", $plugins_url, __( 'deactivate', 'buddyboss' ) );
	wp_die( sprintf( esc_html__( 'BuddyBoss Platform is disabled. The BuddyBoss Platform can\'t work while bbPress plugin is active. Please %s bbPress to re-enable BuddyBoss Platform.', 'buddyboss' ), $link_plugins ), 'BuddyBoss Platform dependency check', array( 'back_link' => true ) );

	return;
}

if ( $is_bb_active ) {

	/**
	 * Displays an admin notice when bbPress plugin is active.
	 *
	 * @since BuddyBoss 1.0.0
	 * @return void
	 */
	function bp_duplicate_bbpress_notice() {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}

		$plugins_url  = is_network_admin() ? network_admin_url( 'plugins.php' ) : admin_url( 'plugins.php' );
		$link_plugins = sprintf( "<a href='%s'>%s</a>", $plugins_url, __( 'deactivate', 'buddyboss' ) );
		?>

        <div id="message" class="error notice">
            <p><strong><?php esc_html_e( 'BuddyBoss Platform is disabled.', 'buddyboss' ); ?></strong></p>
            <p><?php printf( esc_html__( 'The BuddyBoss Platform can\'t work while bbPress plugin is active. Please %s bbPress to re-enable BuddyBoss Platform.', 'buddyboss' ), $link_plugins ); ?></p>
        </div>

		<?php
	}

	/**
	 * You can't have bbPress and BuddyBoss Platform both active at the same time!
	 */
	add_action( 'admin_notices', 'bp_duplicate_bbpress_notice' );
	add_action( 'network_admin_notices', 'bp_duplicate_bbpress_notice' );

}

/**
 * BuddyBoss Platform's code is already loaded when BuddyPress is being activated and BuddyPress doesn't have function
 * checks to avoid duplicate function declarations. This leads to a fatal error.
 * To avoid that, we prevent BuddyPress from activating and we show a nice notice, instead of the dirty fatal error.
 */
if ( ! function_exists( 'bp_prevent_activating_buddypress' ) ) {

	add_action( 'admin_init', 'bp_prevent_activating_buddypress' );

	/**
	 * Check if the current request is to activate BuddyPress plugins and redirect accordingly.
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @global string $pagenow
	 */
	function bp_prevent_activating_buddypress() {
		global $pagenow;

		if ( $pagenow == 'plugins.php' ) {

			if ( isset( $_GET['action'] ) && $_GET['action'] == 'activate' && isset( $_GET['plugin'] ) ) {

				if ( $_GET['plugin'] == 'buddypress/bp-loader.php' ) {
					wp_redirect( self_admin_url( 'plugins.php?bp_prevent_activating_buddypress=1' ), 301 );
					exit;
				}

			}

			if ( isset( $_GET['bp_prevent_activating_buddypress'] ) ) {
				add_action( 'admin_notices', 'bp_prevented_activating_buddypress_notice' );
				add_action( 'network_admin_notices', 'bp_prevented_activating_buddypress_notice' );
			}
		}
	}

	/**
	 * Show a notice that an attempt to activate BuddyPress plugin was blocked.
	 *
	 * @since BuddyBoss 1.0.0
	 */
	function bp_prevented_activating_buddypress_notice() {
		?>

        <div id="message" class="error notice">
            <p><strong><?php esc_html_e( 'BuddyPress can\'t be activated.', 'buddyboss' ); ?></strong></p>
            <p><?php _e( 'The BuddyBoss Platform can\'t work while BuddyPress plugin is active. Please deactivate BuddyBoss Platform first, if you wish to activate BuddyPress.', 'buddyboss' ); ?></p>
        </div>

		<?php
	}

}

/**
 * BuddyBoss Platform's code is already loaded when BBPress is being activated and BBPress doesn't have function
 * checks to avoid duplicate function declarations. This leads to a fatal error.
 * To avoid that, we prevent BBPress from activating and we show a nice notice, instead of the dirty fatal error.
 */
if ( ! function_exists( 'bp_prevent_activating_bbpress' ) ) {

	add_action( 'admin_init', 'bp_prevent_activating_bbpress' );

	/**
	 * Check if the current request is to activate BBPress plugins and redirect accordingly.
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @global string $pagenow
	 */
	function bp_prevent_activating_bbpress() {
		global $pagenow;

		if ( $pagenow == 'plugins.php' ) {

			if ( isset( $_GET['action'] ) && $_GET['action'] == 'activate' && isset( $_GET['plugin'] ) ) {

				if ( $_GET['plugin'] == 'bbpress/bbpress.php' ) {
					wp_redirect( self_admin_url( 'plugins.php?bp_prevent_activating_bbpress=1' ), 301 );
					exit;
				}

			}

			if ( isset( $_GET['bp_prevent_activating_bbpress'] ) ) {
				add_action( 'admin_notices', 'bp_prevented_activating_bbpress_notice' );
				add_action( 'network_admin_notices', 'bp_prevented_activating_bbpress_notice' );
			}
		}
	}

	/**
	 * Show a notice that an attempt to activate BBPress plugin was blocked.
	 *
	 * @since BuddyBoss 1.0.0
	 */
	function bp_prevented_activating_bbpress_notice() {
		?>

        <div id="message" class="error notice">
            <p><strong><?php esc_html_e( 'BBPress can\'t be activated.', 'buddyboss' ); ?></strong></p>
            <p><?php _e( 'The BuddyBoss Platform can\'t work while BBPress plugin is active. Please deactivate BuddyBoss Platform first, if you wish to activate BBPress.', 'buddyboss' ); ?></p>
        </div>

		<?php
	}

}

/**
 * Prevent running BuddyBoss Platform if any incompatible plugins are active.
 * Show admin error message instead.
 */
if ( ! function_exists( 'bp_check_incompatible_plugins' ) ) {

	add_action( 'admin_init', 'bp_check_incompatible_plugins', 0.50 );
	/**
	 * Check for incompatible plugins that are currently active.
	 *
	 * @since BuddyBoss 1.0.0
	 */
	function bp_check_incompatible_plugins() {
		if ( is_admin() && current_user_can( 'activate_plugins' ) ) {

			$incompatible_plugins = array(
				'buddypress-global-search/buddypress-global-search.php' => __( 'The BuddyBoss Platform can\'t work while BuddyPress Global Search plugin is active. Global Search functionality is built into the platform. Please deactivate BuddyPress Global Search first, if you wish to activate BuddyBoss Platform.', 'buddyboss' ),
				'buddypress-followers/loader.php'                       => __( 'The BuddyBoss Platform can\'t work while BuddyPress Follow plugin is active. Following/followers functionality is built into the platform. Please deactivate BuddyPress Follow first, if you wish to activate BuddyBoss Platform.', 'buddyboss' ),
			);

			$incompatible_plugins_messages = array();

			foreach ( $incompatible_plugins as $incompatible_plugin => $error_message ) {
				if ( is_plugin_active( $incompatible_plugin ) ) {
					$incompatible_plugins_messages[] = $error_message;
				}
			}

			if ( empty( $incompatible_plugins_messages ) ) {
				return;
			}

			global $bp_incompatible_plugins_messages;
			$bp_incompatible_plugins_messages = $incompatible_plugins_messages;

			add_action( 'admin_notices', 'bp_incompatible_plugins_deactivate_notice' );

			remove_action( 'bp_admin_init', 'bp_do_activation_redirect', 1 ); // Prevent activation redirect

			deactivate_plugins( 'buddyboss-platform/bp-loader.php' );

			if ( isset( $_GET['activate'] ) ) {
				unset( $_GET['activate'] );
			}
		}
	}
}

/**
 * We need to show a message for incompatible plugins that are currently active.
 *
 */
if ( ! function_exists( 'bp_incompatible_plugins_deactivate_notice' ) ) {
	/**
	 * Admin Notice for having one or more incompatible plugins activated.
	 *
	 * @since BuddyBoss 1.0.0
	 */
	function bp_incompatible_plugins_deactivate_notice() {
		global $bp_incompatible_plugins_messages;
		if ( empty( $bp_incompatible_plugins_messages ) ) {
			return;
		}
		?>
        <div id="message" class="error">
        <p><strong><?php esc_html_e( 'BuddyBoss Platform can\'t be activated.', 'buddyboss' ); ?></strong></p>
        <p><?php echo implode( '<br>', $bp_incompatible_plugins_messages ); ?></p>
        </div><?php
	}
}

if ( ! $is_bp_active && ! $is_bb_active ) {


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

		// A lot of actions in bbpress require before component init,
		// hence we grab the pure db value and load the class
		// so all the hook prior to bp_init can be hook in
		if ( $bp_forum_active = array_key_exists( 'forums', get_option( 'bp-active-components', [] ) ) ) {
			require dirname( __FILE__ ) . '/bp-forums/classes/class-bbpress.php';
		}

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

			if ( $bp_forum_active ) {
				add_action( 'plugins_loaded', 'bbpress', (int) BUDDYPRESS_LATE_LOAD );
			}

			// "And now here's something we hope you'll really like!"
		} else {
			$GLOBALS['bp'] = buddypress();

			if ( $bp_forum_active ) {
				$GLOBALS['bbp'] = bbpress();
			}
		}
	}

}
