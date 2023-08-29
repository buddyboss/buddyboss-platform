<?php
/**
 * Plugin Name: BuddyBoss Platform
 * Plugin URI:  https://buddyboss.com/
 * Description: The BuddyBoss Platform adds community features to WordPress. Member Profiles, Activity Feeds, Direct Messaging, Notifications, and more!
 * Author:      BuddyBoss
 * Author URI:  https://buddyboss.com/
 * Version:     2.4.10
 * Text Domain: buddyboss
 * Domain Path: /languages/
 * License:     GPLv2 or later (license.txt)
 */

/**
 * These files should always remain compatible with the minimum version of
 * PHP supported by WordPress.
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( ! defined( 'BP_SOURCE_SUBDIRECTORY' ) && file_exists( dirname( __FILE__ ) . '/vendor/autoload.php' ) ) {
	require dirname( __FILE__ ) . '/vendor/autoload.php';
}

if ( ! defined( 'BP_PLATFORM_VERSION' ) ) {
	define( 'BP_PLATFORM_VERSION', '2.4.10' );
}

if ( ! defined( 'BP_PLATFORM_API' ) ) {
	define( 'BP_PLATFORM_API', plugin_dir_url( __FILE__ ) );
}

global $bp_incompatible_plugins;
global $buddyboss_platform_plugin_file;
global $is_bp_active;
global $bp_plugin_file;
global $is_bb_active;
global $bb_plugin_file;
global $bp_sitewide_plugins;
global $bp_plugins;
global $bp_is_multisite;

$is_bp_active   = false;
$bp_plugin_file = 'buddypress/bp-loader.php';

$is_bb_active   = false;
$bb_plugin_file = 'bbpress/bbpress.php';

$buddyboss_platform_plugin_file = 'buddyboss-platform/bp-loader.php';

$bp_sitewide_plugins     = array();
$bp_is_multisite         = is_multisite();
$bp_incompatible_plugins = array();

if ( $bp_is_multisite ) {
	// get network-activated plugins.
	foreach ( get_site_option( 'active_sitewide_plugins', array() ) as $key => $value ) {
		$bp_sitewide_plugins[] = $key;
	}
}
$bp_plugins   = array_merge( $bp_sitewide_plugins, get_option( 'active_plugins' ) );
$bp_plugins[] = isset( $_REQUEST['plugin'] ) ? $_REQUEST['plugin'] : array();

// check if BuddyPress is activated.
if ( in_array( $bp_plugin_file, $bp_plugins ) ) {
	$is_bp_active = true;
}

// check if bbPress is activated.
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
	 * @since BuddyBoss 1.2.0
	 */
	function bp_core_unset_bbpress_buddypress_active() {
		global $bp_is_multisite;
		remove_filter( 'option_active_plugins', 'bp_core_set_bbpress_buddypress_active', 0 );

		if ( $bp_is_multisite ) {
			remove_filter( 'site_option_active_sitewide_plugins', 'bp_core_set_bbpress_buddypress_active', 0 );
		}
	}

	/**
	 * Again set the spoofing of BuddyPress and bbPress on Admin Notices
	 *
	 * @since BuddyBoss 1.2.0
	 */
	function bp_core_set_bbpress_buddypress_on_admin_notices() {
		global $bp_is_multisite;

		add_filter( 'option_active_plugins', 'bp_core_set_bbpress_buddypress_active', 0 );
		if ( $bp_is_multisite ) {
			add_filter( 'site_option_active_sitewide_plugins', 'bp_core_set_bbpress_buddypress_active', 0 );
		}
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
	function bp_core_set_bbpress_buddypress_active( $value = array() ) {

		global $bp_plugin_file, $bb_plugin_file, $bp_is_multisite, $buddyboss_platform_plugin_file;

		// Do not add the "bbpress/bbpress.php" & "buddypress/bp-loader.php" on "/wp-admin/plugins.php" page otherwise it will show the plugin file not exists error.

		$admin_url    = admin_url();
		$site_url     = site_url();
		$root_path    = str_replace( $site_url, '', $admin_url );
		$plugins_path = $root_path . 'plugins.php';
		$ajax_path    = $root_path . 'admin-ajax.php';

		// Hide My WP plugin compatibility.
		if ( class_exists( 'HideMyWP' ) ) {
			if ( is_multisite() ) {
				// Get the current site ID.
				$site_id = get_current_blog_id();
				$options = get_blog_option( $site_id, 'hide_my_wp' );
			} else {
				$options = get_option( 'hide_my_wp' );
			}

			$new_admin_path     = ! empty( $options['new_admin_path'] ) ? $options['new_admin_path'] : '';
			$replace_admin_ajax = ! empty( $options['replace_admin_ajax'] ) ? $options['replace_admin_ajax'] : '';

			if ( '' !== $new_admin_path ) {
				$plugins_path = '/' . $new_admin_path . '/plugins.php';

				/**
				 * Admin plugins directory path.
				 *
				 * @since BuddyBoss 1.4.7
				 *
				 * @param string $plugins_path Admin plugins directory path.
				 *
				 */
				$plugins_path = apply_filters( 'bp_admin_plugins_path', $plugins_path );
			}

			if ( '' !== $new_admin_path && '' !== $replace_admin_ajax ) {
				$ajax_path = '/' . $new_admin_path . '/' . $replace_admin_ajax;

				/**
				 * admin-ajax.php path.
				 *
				 * @since BuddyBoss 1.4.7
				 *
				 * @param string $ajax_path admin-ajax.php path.
				 *
				 */
				$ajax_path = apply_filters( 'bp_admin_ajax_path', $ajax_path );
			} elseif ( '' !== $new_admin_path && '' === $replace_admin_ajax ) {
				$ajax_path = '/' . $new_admin_path . '/admin-ajax.php';

				/**
				 * admin-ajax.php path.
				 *
				 * @since BuddyBoss 1.4.7
				 *
				 * @param string $ajax_path admin-ajax.php path.
				 *
				 */
				$ajax_path = apply_filters( 'bp_admin_ajax_path', $ajax_path );
			}
		}

		if ( is_network_admin()
			 || strpos( $_SERVER['REQUEST_URI'], $plugins_path ) !== false
			 || strpos( $_SERVER['REQUEST_URI'], $ajax_path ) !== false
		) {

			/**
			 * Add this so the spoofing plugin does not get loaded by WordPress
			 */
			add_action( 'pre_current_active_plugins', 'bp_core_unset_bbpress_buddypress_active', 10000 );
			add_action( 'all_admin_notices', 'bp_core_unset_bbpress_buddypress_active', 100000 );
			if ( empty( $bp_is_multisite ) ) {
				add_action( 'muplugins_loaded', 'bp_core_unset_bbpress_buddypress_active', 10000 );
			}

			if ( empty( $_GET['action'] ) || $_GET['action'] != 'activate' ) {

				add_action( 'admin_init', 'bp_core_unset_bbpress_buddypress_active', 100000 );

				add_filter( 'all_plugins', 'bp_core_unset_bbpress_buddypress_active_all_plugins', - 1 );

				if ( isset( $_REQUEST['checked'] ) ) {
					add_action( 'load-plugins.php', 'bp_core_set_bbpress_buddypress_on_admin_notices', - 1 );
				}
			}

			/**
			 * Add this so the spoofing plugin does get loaded by WordPress
			 */
			add_action( 'plugin_loaded', 'bp_core_set_bbpress_buddypress_on_admin_notices', - 1 );
			add_action( 'admin_notices', 'bp_core_set_bbpress_buddypress_on_admin_notices', - 1 );
		}

		if ( $bp_is_multisite ) {
			// Check if Forum Component is enabled if so then add.
			if ( function_exists( 'bp_is_active' ) && bp_is_active( 'forums' ) ) {
				$value[ $bb_plugin_file ] = empty( $value[ $buddyboss_platform_plugin_file ] ) ? '' : $value[ $buddyboss_platform_plugin_file ];
			}
			$value[ $bp_plugin_file ] = empty( $value[ $buddyboss_platform_plugin_file ] ) ? '' : $value[ $buddyboss_platform_plugin_file ];
		} else {
			// Check if Forum Component is enabled if so then add.
			if ( function_exists( 'bp_is_active' ) && bp_is_active( 'forums' ) ) {
				array_push( $value, $bb_plugin_file );
			}
			array_push( $value, $bp_plugin_file );
		}

		return $value;
	}

	/**
	 * Remove the BuddyPress and bbPress Spoofing
	 *
	 * @since BuddyBoss 1.2.0
	 *
	 * @param $plugins
	 *
	 * @return mixed
	 */
	function bp_core_unset_bbpress_buddypress_active_all_plugins( $plugins ) {
		bp_core_unset_bbpress_buddypress_active();

		return $plugins;
	}

	/**
	 * Removing the spoofing of BuddyPress and bbPress when option updated.
	 *
	 * @since BuddyBoss 1.2.0
	 */
	function bp_pre_update_option_active_plugins( $value ) {
		global $bp_plugin_file, $bb_plugin_file, $bp_is_multisite;
		if ( $bp_is_multisite ) {
			if ( isset( $value[ $bb_plugin_file ] ) ) {
				unset( $value[ $bb_plugin_file ] );
			}
			if ( isset( $value[ $bp_plugin_file ] ) ) {
				unset( $value[ $bp_plugin_file ] );
			}
		} else {
			$value = array_diff( $value, array( $bp_plugin_file, $bb_plugin_file ) );
		}

		/**
		 * Remove empty value from array
		 */
		$value = array_filter( $value );

		return $value;
	}

	if ( ! is_network_admin() ) {
		add_filter( 'option_active_plugins', 'bp_core_set_bbpress_buddypress_active', 0 );
	}
	// Filter for setting the spoofing of BuddyPress.
	add_filter( 'pre_update_option_active_plugins', 'bp_pre_update_option_active_plugins' );

	if ( $bp_is_multisite ) {
		add_filter( 'site_option_active_sitewide_plugins', 'bp_core_set_bbpress_buddypress_active', 0 );
		add_filter( 'pre_add_site_option_active_sitewide_plugins', 'bp_pre_update_option_active_plugins' );
		add_filter( 'pre_update_site_option_active_sitewide_plugins', 'bp_pre_update_option_active_plugins' );
	}


	// Required PHP version.
	define( 'BP_REQUIRED_PHP_VERSION', '5.3.0' );

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

		// load the member switch class so all the hook prior to bp_init can be hook in.
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
			// so all the hook prior to bp_init can be hook in.
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
		global $is_bp_active;
		global $is_bb_active;

		// Disable BuddyPress message.
		if ( $is_bp_active ) {
			$bp_plugins_url = is_network_admin() ? network_admin_url( 'plugins.php' ) : admin_url( 'plugins.php' );
			$link_plugins   = sprintf( "<a href='%s'>%s</a>", $bp_plugins_url, __( 'deactivate', 'buddyboss' ) );
			?>

			<div id="message" class="error notice">
				<p><strong><?php esc_html_e( 'BuddyBoss Platform is disabled.', 'buddyboss' ); ?></strong></p>
				<p><?php printf( esc_html__( 'The BuddyBoss Platform can\'t work while BuddyPress plugin is active. Please %s BuddyPress to re-enable BuddyBoss Platform.', 'buddyboss' ), $link_plugins ); ?></p>
			</div>

			<?php
		}

		// Disable bbPress message.
		if ( $is_bb_active ) {
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
