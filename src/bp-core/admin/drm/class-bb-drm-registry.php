<?php
/**
 * BuddyBoss DRM Registry
 *
 * Central registry for managing DRM across multiple add-on plugins.
 * Add-ons register themselves here to participate in the DRM system.
 *
 * @package BuddyBoss\Core\Admin\DRM
 * @since 3.0.0
 */

namespace BuddyBoss\Core\Admin\DRM;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * DRM Registry class for managing multiple add-on DRM instances.
 */
class BB_DRM_Registry {

	/**
	 * Registered add-ons.
	 *
	 * @var array
	 */
	private static $addons = array();

	/**
	 * The single instance of the class.
	 *
	 * @var BB_DRM_Registry
	 */
	private static $instance = null;

	/**
	 * Get the singleton instance.
	 *
	 * @return BB_DRM_Registry
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		// Private constructor to enforce singleton.
	}

	/**
	 * Register an add-on for DRM management.
	 *
	 * Add-on plugins should call this during their initialization.
	 *
	 * Example usage from Platform Pro:
	 * ```php
	 * BB_DRM_Registry::register_addon(
	 *     'buddyboss-platform-pro',
	 *     'BuddyBoss Platform Pro',
	 *     array(
	 *         'version' => '2.10.0',
	 *         'file' => BB_PLATFORM_PRO_PLUGIN_FILE
	 *     )
	 * );
	 * ```
	 *
	 * @param string $product_slug The Mothership product slug.
	 * @param string $plugin_name  The plugin display name.
	 * @param array  $args         Additional arguments (version, file, etc.).
	 * @return BB_DRM_Addon|false The DRM addon instance or false on error.
	 */
	public static function register_addon( $product_slug, $plugin_name, $args = array() ) {
		// Validate inputs.
		if ( empty( $product_slug ) || empty( $plugin_name ) ) {
			return false;
		}

		// Check if already registered.
		if ( isset( self::$addons[ $product_slug ] ) ) {
			return self::$addons[ $product_slug ]['drm'];
		}

		// Create DRM instance for this add-on.
		$drm_addon = new BB_DRM_Addon( $product_slug, $plugin_name );

		// Store in registry.
		self::$addons[ $product_slug ] = array(
			'drm'         => $drm_addon,
			'plugin_name' => $plugin_name,
			'args'        => $args,
			'registered'  => time(),
		);

		// Hook into admin_init to run DRM checks.
		add_action(
			'admin_init',
			function() use ( $drm_addon, $product_slug ) {
				self::run_addon_drm_check( $product_slug );
			},
			25
		);

		return $drm_addon;
	}

	/**
	 * Run DRM check for a specific add-on.
	 *
	 * @param string $product_slug The product slug.
	 */
	private static function run_addon_drm_check( $product_slug ) {
		if ( ! isset( self::$addons[ $product_slug ] ) ) {
			return;
		}

		$addon = self::$addons[ $product_slug ];
		$drm   = $addon['drm'];

		// Check if license is valid.
		if ( $drm->is_addon_licensed() ) {
			// License valid - clean up any DRM state.
			self::cleanup_addon_drm( $product_slug );
			return;
		}

		// Create event if needed.
		$event = $drm->get_latest_event();
		if ( ! $event ) {
			$drm->create_event();
		}

		// Run DRM checks.
		$drm->run();
	}

	/**
	 * Cleanup DRM state for an add-on when license becomes valid.
	 *
	 * @param string $product_slug The product slug.
	 */
	private static function cleanup_addon_drm( $product_slug ) {
		$event_key = 'bb_drm_event_addon-' . sanitize_key( $product_slug );
		delete_option( $event_key );

		// Dismiss notifications for this add-on.
		$notifications = new BB_Notifications();
		$notifications->dismiss_events( 'bb-drm-addon-' . $product_slug );
	}

	/**
	 * Get all registered add-ons.
	 *
	 * @return array Array of registered add-ons.
	 */
	public static function get_registered_addons() {
		return self::$addons;
	}

	/**
	 * Check if a specific add-on is registered.
	 *
	 * @param string $product_slug The product slug.
	 * @return bool True if registered.
	 */
	public static function is_addon_registered( $product_slug ) {
		return isset( self::$addons[ $product_slug ] );
	}

	/**
	 * Get DRM instance for a specific add-on.
	 *
	 * @param string $product_slug The product slug.
	 * @return BB_DRM_Addon|null The DRM instance or null.
	 */
	public static function get_addon_drm( $product_slug ) {
		if ( ! isset( self::$addons[ $product_slug ] ) ) {
			return null;
		}
		return self::$addons[ $product_slug ]['drm'];
	}

	/**
	 * Check if any features should be locked for a specific add-on.
	 *
	 * Add-ons should call this before enabling their features.
	 *
	 * Example usage:
	 * ```php
	 * if ( BB_DRM_Registry::should_lock_addon_features( 'buddyboss-platform-pro' ) ) {
	 *     // Don't load features
	 *     return;
	 * }
	 * ```
	 *
	 * @param string $product_slug The product slug.
	 * @return bool True if features should be locked.
	 */
	public static function should_lock_addon_features( $product_slug ) {
		$drm = self::get_addon_drm( $product_slug );
		if ( ! $drm ) {
			// Not registered - allow by default (or you could lock by default).
			return false;
		}

		return $drm->should_lock_features();
	}

	/**
	 * Get lockout message for display.
	 *
	 * @param string $product_slug The product slug.
	 * @return string The lockout message.
	 */
	public static function get_lockout_message( $product_slug ) {
		if ( ! isset( self::$addons[ $product_slug ] ) ) {
			return '';
		}

		$plugin_name = self::$addons[ $product_slug ]['plugin_name'];

		return sprintf(
			/* translators: %1$s: plugin name, %2$s: license page URL */
			__( '%1$s features are currently disabled. Please <a href="%2$s">activate your license</a> to restore functionality.', 'buddyboss' ),
			$plugin_name,
			bp_get_admin_url( 'admin.php?page=buddyboss-license' )
		);
	}

	/**
	 * Helper function to display a lockout notice.
	 *
	 * Add-ons can use this to show a consistent lockout message.
	 *
	 * @param string $product_slug The product slug.
	 * @param string $context      The context (admin_notice, inline, modal).
	 */
	public static function display_lockout_notice( $product_slug, $context = 'admin_notice' ) {
		$message = self::get_lockout_message( $product_slug );

		if ( empty( $message ) ) {
			return;
		}

		switch ( $context ) {
			case 'admin_notice':
				?>
				<div class="notice notice-error bb-drm-lockout-notice">
					<p><strong><?php echo wp_kses_post( $message ); ?></strong></p>
				</div>
				<?php
				break;

			case 'inline':
				?>
				<div class="bb-drm-lockout-inline">
					<p><?php echo wp_kses_post( $message ); ?></p>
				</div>
				<?php
				break;

			case 'modal':
				?>
				<div class="bb-drm-lockout-modal" style="padding: 20px; background: #f9f9f9; border: 2px solid #dc3232; border-radius: 4px; margin: 20px 0;">
					<h3 style="color: #dc3232; margin-top: 0;"><?php esc_html_e( 'License Required', 'buddyboss' ); ?></h3>
					<p><?php echo wp_kses_post( $message ); ?></p>
				</div>
				<?php
				break;
		}
	}
}
