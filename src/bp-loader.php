<?php
/**
 * Plugin Name: BuddyBoss Platform
 * Plugin URI:  https://buddyboss.com/
 * Description: The BuddyBoss Platform adds community features to WordPress. Member Profiles, Activity Feeds, Direct Messaging, Notifications, and more!
 * Author:      BuddyBoss
 * Author URI:  https://buddyboss.com/
 * Version:     3.4.4
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

define('PLATFORM_EDITION', 'developer');

if ( ! defined( 'BP_SOURCE_SUBDIRECTORY' ) && file_exists( dirname( __FILE__ ) . '/vendor/autoload.php' ) ) {
	require dirname( __FILE__ ) . '/vendor/autoload.php';
}

if ( ! defined( 'BP_PLATFORM_VERSION' ) ) {
	define( 'BP_PLATFORM_VERSION', '3.4.4' );
}

if ( ! defined( 'BP_PLATFORM_API' ) ) {
	define( 'BP_PLATFORM_API', plugin_dir_url( __FILE__ ) );
}

// Load translation files.
add_action( 'plugins_loaded', 'bp_core_load_buddypress_textdomain', 0 );

// The plugins_loaded-priority-0 load above runs before multilingual plugins
// (WPML, Polylang) have resolved the request language, so it can cache the
// catalog for the wrong locale. Re-run once the locale is final, and whenever
// it changes mid-request (switch_to_locale() — e.g. per-recipient emails);
// the loader itself is a no-op when the loaded locale is already correct.
add_action( 'init', 'bp_core_load_buddypress_textdomain', 0 );
// Core unloads every textdomain inside change_locale() itself — before this
// action fires — and its registry cannot re-load from this plugin's custom
// locations. Run early so other change_locale callbacks that render buddyboss
// strings already see the correct catalog. Also cover WPML's and Polylang's
// own switch events, which change the locale without firing change_locale;
// the loader self-no-ops when the locale is unchanged.
add_action( 'change_locale', 'bp_core_load_buddypress_textdomain', 0 );
// Priority 20: after WPML String Translation swaps its $l10n entries (10),
// so the reload is not immediately overwritten and re-done.
add_action( 'wpml_language_has_switched', 'bp_core_load_buddypress_textdomain', 20 );
// Priority 10: after Polylang's own-language-tools manager removes its
// load_textdomain_mofile blocker on this same action (priority 2). That blocker
// is front-end only — PLL_OLT_Manager returns early in wp-admin and for REST.
// Polylang's own change_locale dispatch is conditional on the core locale
// switcher already existing, so it does not fire at plugins_loaded; these two
// actions are the only reliable Polylang entry points.
add_action( 'pll_language_defined', 'bp_core_load_buddypress_textdomain', 10 );
// Same blocker is lifted on this sibling action when no languages are configured
// yet (fresh install, or the last language was deleted); without it those
// requests would keep the neutered plugins_loaded catalog until init:0.
add_action( 'pll_no_language_defined', 'bp_core_load_buddypress_textdomain', 10 );

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
$bp_plugins   = array_merge( $bp_sitewide_plugins, (array) get_option( 'active_plugins', array() ) );
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
	'buddypress-global-search/buddypress-global-search.php',
	'buddypress-followers/loader.php'
);

foreach ( $bp_incompatible_plugins_list as $incompatible_plugin ) {
	if ( in_array( $incompatible_plugin, $bp_plugins ) ) {
		$bp_incompatible_plugins[] = $incompatible_plugin;
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
			$incompatible_plugins_list_messages = array(
				'buddypress-global-search/buddypress-global-search.php' => __( 'The BuddyBoss Platform can\'t work while BuddyPress Global Search plugin is active. Global Search functionality is built into the platform. Please deactivate BuddyPress Global Search first, if you wish to activate BuddyBoss Platform.', 'buddyboss' ),
				'buddypress-followers/loader.php'                       => __( 'The BuddyBoss Platform can\'t work while BuddyPress Follow plugin is active. Follow functionality is built into the platform. Please deactivate BuddyPress Follow first, if you wish to activate BuddyBoss Platform.', 'buddyboss' ),
			);
			foreach ( $bp_incompatible_plugins as $incompatible_plugin_key ) {
				?>
				<div id="message" class="error notice">
					<p><strong><?php esc_html_e( 'BuddyBoss Platform is disabled.', 'buddyboss' ); ?></strong></p>
					<?php
					printf( '<p>%s</p>', $incompatible_plugins_list_messages[ $incompatible_plugin_key ] );
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

if ( ! function_exists( 'bp_core_load_buddypress_textdomain' ) ) {
	/**
	 * Load the buddyboss translation file for current language.
	 *
	 * @since BuddyPress 1.0.2
	 * @since BuddyBoss 2.7.90 Moved function from bp-core-functions.php and made logic updates.
	 * @since BuddyBoss [BBVERSION] Reloads the catalog when the locale has changed since the last
	 *                              load, so late locale resolution (WPML/Polylang) and mid-request
	 *                              switch_to_locale() calls translate correctly.
	 *
	 * @return bool True when load_textdomain() reported success for the authoritative
	 *              location. That may be a real file on disk, or a catalog served by an
	 *              `override_load_textdomain` / `pre_load_textdomain` / `load_translation_file`
	 *              listener for a path that does not exist (Loco Translate and WPML String
	 *              Translation both do this) — so a true here means "a catalog is in place",
	 *              not "a file was read".
	 *
	 *              False when nothing needed doing — the locale was unchanged and the domain
	 *              already loaded, the change_locale fast path found core's own reload
	 *              sufficient, or the bundled languages/ directory was already registered with
	 *              core's textdomain registry and no location produced a catalog.
	 *
	 *              Otherwise the return value is load_plugin_textdomain()'s, which since
	 *              WP 6.7 no longer loads anything: it registers the path for core's
	 *              just-in-time loader and returns true for any string domain. So a true from
	 *              that branch means "the JIT path is registered", NOT "translations are
	 *              loaded" — do not treat it as proof of a loaded catalog. On WP 6.1-6.6 that
	 *              branch still loads the file and its return value is a genuine load result.
	 */
	function bp_core_load_buddypress_textdomain() {
		static $loaded_locale   = null;
		static $path_registered = false;

		$domain = 'buddyboss';
		// determine_locale(): unlike get_locale(), it resolves the user's admin
		// language in wp-admin — matching core's own plugin-catalog resolution
		// and this function's load_plugin_textdomain() fallback (WP < 5.0
		// fallback kept while the readme floor still predates it).
		//
		// Only from `init` onwards, though. In wp-admin (and on `_locale=user`
		// JSON requests) determine_locale() calls get_user_locale() ->
		// wp_get_current_user(), which fires the `determine_current_user` filter
		// and memoises its result for the whole request. Resolving the user from
		// plugins_loaded:0 — the earliest point a plugin can act — risks doing so
		// before an authentication plugin that happens to load after this one has
		// registered its `determine_current_user` filter; that filter would then
		// never be consulted and the requester would resolve as logged out.
		// Nothing is lost by waiting: the plugins_loaded pass is no longer
		// authoritative here, and the init:0 re-run registered at the top of this
		// file supplies the admin-user locale.
		//
		// did_action() is already truthy inside init:0 (core increments the count
		// before running callbacks), so this covers both "during" and "after".
		// Hooks that fire before init (pll_language_defined, on setup_theme) fall
		// back to get_locale(), which the multilingual plugin has already filtered
		// to the request language; init:0 then re-runs and corrects any admin-user
		// difference.
		//
		// The one exception is when the current user has ALREADY been resolved by
		// something else: $GLOBALS['current_user'] is exactly the memo
		// wp_get_current_user() consults, so reading it costs nothing and fires no
		// filter. Taking the admin-user locale in that case narrows the
		// plugins_loaded -> init window during which strings frozen at bp_loaded
		// (component names, widget titles, Settings 2.0 feature labels) would
		// otherwise be captured under the site locale instead of the admin's.
		//
		// This is the locale every later translation lookup will use, so it is
		// also the locale the catalog must be REGISTERED under — see the third
		// argument on load_textdomain() below.
		if ( function_exists( 'determine_locale' ) && ( did_action( 'init' ) || isset( $GLOBALS['current_user'] ) ) ) {
			$request_locale = determine_locale();
		} else {
			$request_locale = get_locale();
		}

		// `plugin_locale` selects which catalog FILE belongs to this domain, and it
		// is allowed to differ from the request locale — WPML formal/informal
		// variants and per-plugin language overrides both rely on that. Apply it so
		// the custom-location lookup and the load_plugin_textdomain() fallback agree
		// on a file name, and so WPML String Translation — which uses `plugin_locale`
		// as its registry of translatable plugin domains — sees a `buddyboss` catalog
		// served from a custom location.
		//
		// It is used for the file name ONLY. Never pass it to load_textdomain() as
		// the registration locale: that value goes straight into the process-wide
		// WP_Translation_Controller and would repoint lookups for every other domain.
		$locale = apply_filters( 'plugin_locale', $request_locale, $domain );

		// Re-attempt when either locale changed since the last attempt OR the
		// domain is not loaded: the locale check alone would strand the domain
		// after a third-party unload_textdomain() (Loco Translate unloads every
		// prematurely-loaded domain when it boots, and WPML's published BuddyBoss
		// workaround does the same before re-calling this function) and after
		// Polylang's load blocker. Cost of the loaded check: locales with no
		// catalog anywhere (en_US) repeat the whole body on each registered hook —
		// four is_readable() probes, an unload, four load_textdomain() calls and
		// the registry fallback. Accepted: the work is small and bounded, and the
		// alternative is failing to recover from a third-party unload.
		$locale_key = $request_locale . '|' . $locale;
		if ( $locale_key !== $loaded_locale || ! is_textdomain_loaded( $domain ) ) {
			$loaded_locale   = $locale_key;
			$mofile_custom   = sprintf( '%s-%s.mo', $domain, $locale );
			$plugin_dir_path = defined( 'BP_PLUGIN_DIR' ) ? BP_PLUGIN_DIR : plugin_dir_path( __FILE__ );
			$plugin_dir      = $plugin_dir_path;
			if ( defined( 'BP_SOURCE_SUBDIRECTORY' ) && ! empty( constant( 'BP_SOURCE_SUBDIRECTORY' ) ) ) {
				$plugin_dir = $plugin_dir . 'src';
			}

			/**
			 * Filters the locations to load language files from.
			 *
			 * @since BuddyBoss 2.7.90
			 *
			 * @param array $value Array of directories to check for language files in.
			 */
			$locations = apply_filters(
				'buddyboss_locale_locations',
				array(
					trailingslashit( WP_LANG_DIR . '/' . $domain ),
					trailingslashit( WP_LANG_DIR ),
					trailingslashit( WP_LANG_DIR . '/plugins' ),
					trailingslashit( $plugin_dir . '/languages' ),
				)
			);

			// Re-index so the positional comparison in the load loop below is
			// meaningful even when a filter returns a sparse or associative array,
			// and so a filter returning a non-array cannot fatal the foreach.
			$locations = array_values( (array) $locations );

			// Which location, if any, actually has a catalog for this locale
			// (either format — load_textdomain() prefers the .l10n.php sibling).
			$phpfile_custom = substr( $mofile_custom, 0, -3 ) . '.l10n.php';
			$found_mofile   = '';
			$found_dir      = '';
			$found_index    = -1;
			foreach ( $locations as $index => $location ) {
				if ( is_readable( $location . $mofile_custom ) || is_readable( $location . $phpfile_custom ) ) {
					$found_mofile = $location . $mofile_custom;
					$found_dir    = $location;
					$found_index  = $index;
					break;
				}
			}

			// Teach core's just-in-time loader about the bundled languages/ dir —
			// ONCE per request, and never re-assert it afterwards.
			// _load_textdomain_just_in_time() cannot serve a directory the registry
			// does not know, and the registry only ever learns this one from
			// load_plugin_textdomain() — which the loop below skips whenever it
			// satisfies the load itself.
			//
			// Re-asserting it on init:0 and on every change_locale would be actively
			// harmful: WP_Textdomain_Registry holds exactly ONE custom path per
			// domain, so a later registration by a site or a translation plugin
			// (a Loco Translate custom bundle, an mu-plugin calling
			// load_plugin_textdomain() at after_setup_theme) would be overwritten and
			// core's JIT would stop finding their catalog for the rest of the request.
			// set_custom_path() is not free either — it array_filter()s this domain's
			// cached lookups, discarding "not found" entries and forcing a directory
			// re-scan on the next get().
			//
			// WP 6.1+ only (the registry does not exist earlier), so the fast path
			// below may only trust this directory when the registration happened.
			if (
				! $path_registered
				&& isset( $GLOBALS['wp_textdomain_registry'] )
				&& is_object( $GLOBALS['wp_textdomain_registry'] )
				&& method_exists( $GLOBALS['wp_textdomain_registry'], 'set_custom_path' )
			) {
				$GLOBALS['wp_textdomain_registry']->set_custom_path( $domain, $plugin_dir . '/languages' );
				$path_registered = true;
			}
			$core_serves_plugin_dir = $path_registered;

			// During change_locale (and only there — on other hooks a loaded
			// catalog may still be the stale previous-locale one), core's locale
			// switcher has already unloaded the domain and JIT-reloaded it for the
			// NEW locale from the directories its registry knows: WP_LANG_DIR/
			// plugins, plus the bundled languages/ dir once registered above.
			//
			// We do not rely on that reload having succeeded. is_textdomain_loaded()
			// is a genuine post-condition check — it excludes NOOP_Translations, and
			// load_textdomain() only registers a real translations object after the
			// file for the new locale actually loaded. So when core's JIT is
			// unavailable (WP < 6.1, or a third party left the l10n_unloaded flag
			// set) this test is simply false and we do the full reload.
			//
			// When it did succeed and our probe found nothing in a
			// higher-precedence location than the one core used, the loaded catalog
			// is already the best available: skip the redundant unload + full MO
			// re-parse. That matters on pre-WP 6.5, which has no translation-file
			// cache — a cron switching locale per recipient across hundreds of
			// emails would otherwise re-parse the catalog on every switch.
			// Remaining gap: a catalog in WP_LANG_DIR/buddyboss/ or WP_LANG_DIR
			// root (both higher precedence, neither known to core) still forces the
			// full reload on each switch.
			//
			// Directory equality, not a string prefix: a location NESTED under one
			// of these — reachable through the buddyboss_locale_locations filter
			// above — is not something core's registry can serve, and treating it as
			// such would skip a reload that is genuinely needed.
			$core_has_best = '' === $found_mofile
				|| trailingslashit( WP_LANG_DIR . '/plugins' ) === $found_dir
				|| ( $core_serves_plugin_dir && trailingslashit( $plugin_dir . '/languages' ) === $found_dir );

			// is_textdomain_loaded() is locale-AGNOSTIC: it proves some catalog is in
			// $l10n, not that it is the one for $request_locale. Core's own switcher
			// unloads before firing change_locale so the two normally agree, but a
			// third party that dispatches change_locale as a plain notification
			// without unloading first would leave the stale previous-locale catalog
			// in place and this fast path would trust it. WP 6.5+ exposes a
			// locale-aware check; require both (the core call additionally excludes
			// NOOP_Translations, which the controller's does not).
			$domain_loaded = is_textdomain_loaded( $domain );
			if ( $domain_loaded && class_exists( 'WP_Translation_Controller' ) ) {
				$domain_loaded = WP_Translation_Controller::get_instance()->is_textdomain_loaded( $domain, $request_locale );
			}

			if (
				doing_action( 'change_locale' )
				&& $domain_loaded
				&& $core_has_best
				// Core's JIT builds its filename from determine_locale(), so when a
				// plugin_locale filter points this domain at a different catalog than
				// the request locale, whatever core reloaded is NOT the file the probe
				// matched. The fast path may not be trusted in that case.
				&& $locale === $request_locale
			) {
				return false;
			}

			// Reloadable: a plain unload would set the l10n_unloaded flag and
			// permanently disable core's JIT loading for this domain, removing
			// the native fallback for any locale change these hooks miss.
			// ($reloadable is WP 6.1+; older cores ignore the extra arg and
			// behave like the pre-fix plain unload — no regression there. A
			// flag already set by a third party's plain unload stays set on
			// WP 6.5+; acceptable, because the hooks registered above cover
			// every locale change, so the lost JIT backstop is never needed).
			unload_textdomain( $domain, true );

			// $reloadable = true above keeps core's JIT alive, but it also means
			// core deliberately does NOT drop the domain from
			// WP_Translation_Controller ("reloadable text domains don't actually
			// need to be unloaded"). On WP 6.5+ the controller appends each loaded
			// file and resolves first-match-wins, so without this the reload below
			// could not promote a higher-precedence catalog over one already loaded
			// for the same locale — which is the entire purpose of walking
			// $locations in order.
			//
			// Scoped to this domain and this locale, so other domains and other
			// locales (a switch_to_locale() stack) are untouched, and $l10n_unloaded
			// stays unset so the JIT backstop survives. Only done when the probe
			// found a real catalog to re-establish: on a request where every catalog
			// is filter-served (Loco Translate mapping into wp-content/languages/loco,
			// WPML String Translation serving from wp-content/languages/wpml) there is
			// nothing to promote, and clearing would risk dropping a layer that a
			// third party will not re-add.
			if ( '' !== $found_mofile && class_exists( 'WP_Translation_Controller' ) ) {
				WP_Translation_Controller::get_instance()->unload_textdomain( $domain, $request_locale );
			}

			// Loco Translate keeps its edits in a mirror of WP_LANG_DIR at
			// wp-content/languages/loco, and injects them by REWRITING a path we
			// pass to load_textdomain(): Loco_hooks_LoadHelper::filter_load_translation_file()
			// maps WP_LANG_DIR/... and a plugin's own languages/ dir onto
			// LOCO_LANG_DIR/... . But the only place its UI ever SAVES a plugin
			// catalog is LOCO_LANG_DIR/plugins/ (Loco_package_Plugin::getSystemTargets),
			// which is reachable only from locations 3 and 4 below. A catalog in
			// WP_LANG_DIR/buddyboss/ or WP_LANG_DIR root ends the walk before those,
			// so a site that has both would silently ignore every Loco edit.
			//
			// Offer it first and let the walk below layer the base catalog
			// underneath: the WP 6.5+ controller appends files and resolves
			// first-match-wins, so a translation Loco overrides wins while every
			// string it does not carry still falls through to the base catalog
			// (a Loco custom PO is frequently a partial sync).
			//
			// Deliberately outside $locations: it must never set $found_mofile or
			// terminate the walk, and a site filter must not be able to drop it.
			// The return value is ignored for the same reason. Loco passes a path
			// already under LOCO_LANG_DIR straight through, so this cannot recurse.
			// The constant is defined only when Loco is active, and a site may
			// define it empty to switch Loco's loader off — hence the truthy check.
			if ( defined( 'LOCO_LANG_DIR' ) && LOCO_LANG_DIR ) {
				load_textdomain( $domain, trailingslashit( LOCO_LANG_DIR ) . 'plugins/' . $mofile_custom, $request_locale );
			}

			// Attempt each location in precedence order — unconditionally, even
			// when the probe above found nothing readable on disk.
			// load_textdomain() is an extension point, not just a file read: it
			// fires `pre_load_textdomain`, `override_load_textdomain`,
			// `load_textdomain_mofile` and `load_translation_file`, which third
			// parties use to serve a catalog for a path that does not exist.
			// Loco Translate maps each attempted path under WP_LANG_DIR to its
			// wp-content/languages/loco/ equivalent and returns that mapped path
			// when the original is missing; WPML String Translation layers its
			// custom MOs from wp-content/languages/wpml/. Neither directory is one
			// of the probed locations, so gating the loop on the probe would
			// silently drop every such translation. The probe's only job is the
			// change_locale fast path above.
			//
			// Trying each location (rather than just the one the probe matched)
			// also keeps the legacy semantics: an unreadable or corrupt file at a
			// higher-precedence location must not mask a valid catalog at a later
			// one.
			foreach ( $locations as $index => $location ) {
				$mofile = $location . $mofile_custom;

				// $request_locale, NOT $locale, is what the catalog is registered
				// under. load_textdomain() calls
				// WP_Translation_Controller::set_locale() with this value BEFORE it
				// inspects any file, and that controller is a process-wide singleton
				// shared by every text domain: handing it a plugin_locale-filtered
				// value that differs from the request locale would repoint lookups
				// for core's own `default` domain too and blank out the whole page.
				// Registering under the request locale is also what makes the catalog
				// findable, since that is the locale every later lookup uses.
				//
				// Passing it explicitly rather than letting core call
				// determine_locale() itself preserves the plugins_loaded deferral
				// documented above.
				$loaded = load_textdomain( $domain, $mofile, $request_locale );

				// A truthy return is NOT proof that this location's catalog loaded:
				// `override_load_textdomain` / `pre_load_textdomain` listeners answer
				// true for paths that do not exist, and the WP 6.5+ translation
				// controller reports success for a file already registered earlier in
				// the request. Stopping on the first truthy return therefore skipped
				// the location that really held the catalog — on repeat locale
				// switches that silently dropped the translations entirely.
				//
				// So: keep going until the probe's own position is reached, which
				// lets every higher-precedence override listener layer on top first
				// (they win anyway — the controller resolves first-match-wins). Then
				// stop. Compare positions rather than paths, so a genuine load at the
				// probe's position or later still terminates the walk even if the
				// path was rewritten by a `load_textdomain_mofile` filter. When
				// nothing exists on disk anywhere, the first truthy return wins —
				// that is the override-served case.
				if ( $loaded && ( '' === $found_mofile || $index >= $found_index ) ) {
					return true;
				}
			}

			// Nothing loaded. Hand the bundled directory to core's JIT loader as a
			// last resort — but only if we have not already registered it above,
			// because load_plugin_textdomain() calls set_custom_path() internally and
			// would clobber any custom path a third party registered after us.
			if ( $path_registered ) {
				// Still mirror the one other thing load_plugin_textdomain() does:
				// clear a NOOP_Translations placeholder left by an earlier failed JIT
				// attempt, so core can try again.
				if ( isset( $GLOBALS['l10n'][ $domain ] ) && $GLOBALS['l10n'][ $domain ] instanceof NOOP_Translations ) {
					unset( $GLOBALS['l10n'][ $domain ] );
				}

				return false;
			}

			$plugin_folder       = plugin_basename( $plugin_dir_path );
			$buddyboss_lang_path = $plugin_folder . '/languages';
			if ( defined( 'BP_SOURCE_SUBDIRECTORY' ) && ! empty( constant( 'BP_SOURCE_SUBDIRECTORY' ) ) ) {
				$buddyboss_lang_path = $plugin_folder . '/src/languages';
			}

			return load_plugin_textdomain( $domain, false, $buddyboss_lang_path );
		}

		return false;
	}
}
