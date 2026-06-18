<?php
/**
 * BuddyBoss DRM Update Router
 *
 * Routes BuddyBoss Platform plugin updates based on license state and delivers
 * the paid Platform build (which bundles the video and document components) to
 * licensed customers.
 *
 * Behaviour:
 * - No license key            -> updates come from wordpress.org (default WP behaviour).
 * - Valid PAID license present -> updates come from the Mothership. The wordpress.org
 *   update check is prevented from clobbering the paid build with the free one, and the
 *   paid build is fetched immediately (rather than waiting for the next update cycle) so
 *   the bundled video/document components become available.
 *
 * The actual Mothership update injection is performed by the vendor AddonsManager
 * (GroundLevel\Mothership\Manager\AddonsManager) on the `site_transient_update_plugins`
 * filter. This class complements it by suppressing the wordpress.org entry and by forcing
 * an immediate install when the paid component folders are missing from the current build.
 *
 * @package BuddyBoss\Core\Admin\DRM
 * @since BuddyBoss [BBVERSION]
 */

namespace BuddyBoss\Core\Admin\DRM;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * DRM Update Router class.
 *
 * @since BuddyBoss [BBVERSION]
 */
class BB_DRM_Update_Router {

	/**
	 * Cron hook fired to install the paid Platform build.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @var string
	 */
	const INSTALL_HOOK = 'bb_drm_install_paid_build';

	/**
	 * Transient key used to lock concurrent installs.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @var string
	 */
	const LOCK_KEY = 'bb_drm_paid_build_installing';

	/**
	 * The resolved dynamic plugin ID (edition) used for the license-status hook name.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @var string
	 */
	private $plugin_id = '';

	/**
	 * Register WordPress hooks.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param string $plugin_id Optional. The resolved dynamic plugin ID. When empty it is
	 *                          resolved locally. Passing the controller's value keeps the
	 *                          `{plugin_id}_license_status_changed` hook name consistent.
	 */
	public function setup_hooks( $plugin_id = '' ) {
		$this->plugin_id = ! empty( $plugin_id ) ? $plugin_id : $this->get_plugin_id();

		// Req #1 + #2: route the Platform's own update away from wordpress.org when a paid
		// license is active AND the Mothership actually offers the Platform build, so the
		// paid build wins. When the Mothership has nothing to serve we fail open and leave
		// the wordpress.org payload untouched (the site keeps receiving updates).
		add_filter( 'http_request_args', array( $this, 'suppress_wporg_update_check' ), 10, 2 );

		// Req #3: deliver the paid build immediately on license add/update.
		add_action( 'bb_drm_license_activated', array( $this, 'schedule_paid_build_install' ) );
		add_action( $this->plugin_id . '_license_status_changed', array( $this, 'on_license_status_changed' ), 10, 2 );

		// Background install handler.
		add_action( self::INSTALL_HOOK, array( $this, 'install_paid_build' ) );
	}

	/**
	 * Get the dynamic plugin ID from the Mothership connection.
	 *
	 * Mirrors BB_DRM_Controller::get_plugin_id() so the license-status hook name resolves
	 * to the same edition-specific value the vendor LicenseManager fires.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return string The plugin ID.
	 */
	private function get_plugin_id() {
		if ( ! class_exists( '\BuddyBoss\Core\Admin\Mothership\BB_Plugin_Connector' ) ) {
			return defined( 'PLATFORM_EDITION' ) ? PLATFORM_EDITION : 'buddyboss-platform';
		}

		try {
			$connector = new \BuddyBoss\Core\Admin\Mothership\BB_Plugin_Connector();
			return $connector->getCurrentPluginId();
		} catch ( \Exception $e ) {
			return defined( 'PLATFORM_EDITION' ) ? PLATFORM_EDITION : 'buddyboss-platform';
		}
	}

	/**
	 * Get the Platform plugin basename (e.g. "buddyboss-platform/bp-loader.php").
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return string The plugin basename.
	 */
	private function get_platform_basename() {
		$bp = buddypress();

		if ( ! empty( $bp->file ) ) {
			return plugin_basename( $bp->file );
		}

		return 'buddyboss-platform/bp-loader.php';
	}

	/**
	 * Whether a valid PAID Platform license is active.
	 *
	 * Free/developer editions and unlicensed sites return false so wordpress.org keeps
	 * serving updates for them.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return bool True when a paid edition license is active.
	 */
	public function is_paid_active() {
		if ( ! BB_DRM_Helper::is_valid() ) {
			return false;
		}

		$plugin_id     = $this->get_plugin_id();
		$free_editions = array( 'bb-platform-free', 'developer', 'buddyboss-platform' );
		$is_paid       = ! empty( $plugin_id ) && ! in_array( $plugin_id, $free_editions, true );

		/**
		 * Filters whether the active license is considered a paid Platform edition.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param bool   $is_paid   Whether the current edition is paid.
		 * @param string $plugin_id The resolved dynamic plugin ID.
		 */
		return (bool) apply_filters( 'bb_drm_is_paid_edition', $is_paid, $plugin_id );
	}

	/**
	 * Strip the Platform plugin from the wordpress.org update check when a paid license is active.
	 *
	 * Prevents wordpress.org from reporting (and downgrading to) the free build while the
	 * paid Mothership build is installed. When no paid license is active the payload is left
	 * untouched so wordpress.org continues to serve updates (req #1).
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param array  $args HTTP request arguments.
	 * @param string $url  The request URL.
	 * @return array Filtered request arguments.
	 */
	public function suppress_wporg_update_check( $args, $url ) {
		if ( false === strpos( (string) $url, '//api.wordpress.org/plugins/update-check' ) ) {
			return $args;
		}

		if ( empty( $args['body']['plugins'] ) || ! $this->is_paid_active() ) {
			return $args;
		}

		// Fail open: only suppress the wordpress.org entry when the Mothership genuinely
		// offers the Platform build. Otherwise the site would be left with no update source.
		if ( null === $this->get_routed_platform_product() ) {
			return $args;
		}

		$payload = json_decode( $args['body']['plugins'], true );
		if ( ! is_array( $payload ) || empty( $payload['plugins'] ) ) {
			return $args;
		}

		$basename = $this->get_platform_basename();

		if ( isset( $payload['plugins'][ $basename ] ) ) {
			unset( $payload['plugins'][ $basename ] );
		}

		if ( ! empty( $payload['active'] ) && is_array( $payload['active'] ) ) {
			$payload['active'] = array_values( array_diff( $payload['active'], array( $basename ) ) );
		}

		$args['body']['plugins'] = wp_json_encode( $payload );

		return $args;
	}

	/**
	 * Handle the Mothership cron license-status change event.
	 *
	 * On a valid (paid) status we schedule delivery of the paid build. On an invalid/expired
	 * status we intentionally do nothing here: `is_paid_active()` immediately returns false,
	 * so `suppress_wporg_update_check()` stops suppressing and wordpress.org resumes serving
	 * the (free) Platform build, which the normal update flow then installs. The currently
	 * installed paid build keeps working until that update lands.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param bool  $is_valid Whether the license is valid.
	 * @param mixed $status   The Mothership status response (unused).
	 */
	public function on_license_status_changed( $is_valid, $status = null ) {
		if ( $is_valid ) {
			$this->schedule_paid_build_install();
		}
	}

	/**
	 * Schedule a one-off background install of the paid Platform build.
	 *
	 * Keeps the license activation request fast by deferring the (potentially heavy)
	 * download + replace to a single cron event.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	public function schedule_paid_build_install() {
		if ( ! $this->is_paid_active() ) {
			return;
		}

		// Nothing to do when the paid components are already present in this build.
		if ( $this->has_paid_components() ) {
			return;
		}

		if ( wp_next_scheduled( self::INSTALL_HOOK ) ) {
			return;
		}

		wp_schedule_single_event( time() + 5, self::INSTALL_HOOK );
	}

	/**
	 * Whether the paid-only video and document component folders are present in this build.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return bool True when both component directories exist.
	 */
	private function has_paid_components() {
		if ( ! function_exists( 'bb_is_component_directory_available' ) ) {
			return true;
		}

		return bb_is_component_directory_available( 'video' ) && bb_is_component_directory_available( 'document' );
	}

	/**
	 * Resolve the Mothership product representing the Platform itself, if offered.
	 *
	 * This is the single source of truth shared by both the wordpress.org suppression guard
	 * and the paid-build installer: the build is only routed to the Mothership when the
	 * Mothership product list contains a product whose main file matches the Platform.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return object|null The matching product object, or null when not offered.
	 */
	private function get_routed_platform_product() {
		if ( ! class_exists( '\BuddyBoss\Core\Admin\Mothership\BB_Addons_Manager' ) ) {
			return null;
		}

		$response = \BuddyBoss\Core\Admin\Mothership\BB_Addons_Manager::getAddons( true );
		if ( ! is_object( $response ) || empty( $response->products ) || ! is_array( $response->products ) ) {
			return null;
		}

		$basename = $this->get_platform_basename();

		foreach ( $response->products as $product ) {
			$main_file = isset( $product->main_file ) ? $product->main_file : '';
			if ( $main_file === $basename ) {
				return $product;
			}
		}

		return null;
	}

	/**
	 * Resolve the validated Mothership download URL for the paid Platform build.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return string The package URL, or empty string when unavailable/untrusted.
	 */
	private function get_platform_package_url() {
		$product = $this->get_routed_platform_product();
		if ( null === $product ) {
			return '';
		}

		$embedded = isset( $product->_embedded ) ? $product->_embedded : null;
		$url      = ( $embedded && isset( $embedded->{'version-latest'}->url ) ) ? (string) $embedded->{'version-latest'}->url : '';

		return $this->is_trusted_package_url( $url ) ? $url : '';
	}

	/**
	 * Validate a paid-build package URL before it is handed to the installer.
	 *
	 * Requires a well-formed HTTPS URL. The package originates from the authenticated
	 * Mothership products API (the same source WordPress uses for a normal "update now"),
	 * and the `bb_drm_trusted_package_url` filter allows hosts to further restrict it.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param string $url The candidate package URL.
	 * @return bool True when the URL is trusted for installation.
	 */
	private function is_trusted_package_url( $url ) {
		if ( ! is_string( $url ) || '' === $url || ! filter_var( $url, FILTER_VALIDATE_URL ) ) {
			return false;
		}

		if ( 'https' !== strtolower( (string) wp_parse_url( $url, PHP_URL_SCHEME ) ) ) {
			return false;
		}

		/**
		 * Filters whether a resolved paid-build package URL is trusted for installation.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param bool   $trusted Whether the URL is trusted. Default true for HTTPS URLs.
		 * @param string $url     The candidate package URL.
		 */
		return (bool) apply_filters( 'bb_drm_trusted_package_url', true, $url );
	}

	/**
	 * Download and install the paid Platform build, replacing the current plugin.
	 *
	 * Runs in a cron context so the active plugin files are never swapped mid page request.
	 * Only fires when a paid license is active and the paid component folders are missing.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	public function install_paid_build() {
		if ( ! $this->is_paid_active() || $this->has_paid_components() ) {
			return;
		}

		// Prevent overlapping installs.
		if ( get_transient( self::LOCK_KEY ) ) {
			return;
		}
		set_transient( self::LOCK_KEY, 1, 15 * MINUTE_IN_SECONDS );

		$package = $this->get_platform_package_url();
		if ( empty( $package ) ) {
			bb_error_log( 'BuddyBoss DRM: paid Platform build install skipped - no package URL resolved.', true );
			delete_transient( self::LOCK_KEY );
			return;
		}

		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/misc.php';
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
		require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';

		// Cron has no request context, so only the "direct" filesystem method can initialise
		// here. On hosts that require FTP/SSH credentials this returns false and the customer
		// must run the update interactively from wp-admin; we log it distinctly for support.
		if ( ! \WP_Filesystem() ) {
			bb_error_log( 'BuddyBoss DRM: paid Platform build install aborted - WP_Filesystem unavailable in cron (non-direct filesystem; install interactively from Plugins screen).', true );
			delete_transient( self::LOCK_KEY );
			return;
		}

		$basename     = $this->get_platform_basename();
		$was_active   = is_plugin_active( $basename );
		$network_wide = is_plugin_active_for_network( $basename );

		$skin     = new \Automatic_Upgrader_Skin();
		$upgrader = new \Plugin_Upgrader( $skin );

		// Full replacement of the existing plugin folder with the paid build.
		$result = $upgrader->install(
			$package,
			array(
				'overwrite_package' => true,
			)
		);

		// Restore activation state if the overwrite dropped it. Activate non-silently so the
		// newly delivered video/document components run their activation/setup routines.
		if ( ! is_wp_error( $result ) && $result && $was_active && ! is_plugin_active( $basename ) ) {
			activate_plugin( $basename, '', $network_wide, false );
		}

		// Force a fresh update check so the installed version is reflected.
		delete_site_transient( 'update_plugins' );

		if ( is_wp_error( $result ) ) {
			bb_error_log( sprintf( 'BuddyBoss DRM: paid Platform build install failed - %s', $result->get_error_message() ), true );
		} else {
			bb_error_log( 'BuddyBoss DRM: paid Platform build installed successfully.', true );
		}

		delete_transient( self::LOCK_KEY );
	}
}
