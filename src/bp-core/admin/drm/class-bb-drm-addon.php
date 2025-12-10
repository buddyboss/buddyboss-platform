<?php
/**
 * BuddyBoss DRM Add-on
 *
 * Handles DRM checks for specific add-on plugins.
 * This class can be instantiated by any BuddyBoss add-on plugin to enforce license requirements.
 *
 * @package BuddyBoss\Core\Admin\DRM
 * @since BuddyBoss [BBVERSION]
 */

namespace BuddyBoss\Core\Admin\DRM;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * DRM Add-on class for validating individual add-on licenses.
 */
class BB_DRM_Addon extends BB_Base_DRM {

	/**
	 * The product slug for this add-on (e.g., 'buddyboss-platform-pro').
	 *
	 * @var string
	 */
	protected $product_slug = '';

	/**
	 * The add-on plugin name for display.
	 *
	 * @var string
	 */
	protected $plugin_name = '';

	/**
	 * Constructor for the BB_DRM_Addon class.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param string $product_slug The Mothership product slug.
	 * @param string $plugin_name  The plugin display name.
	 */
	public function __construct( $product_slug, $plugin_name = '' ) {
		$this->product_slug = $product_slug;
		$this->plugin_name  = $plugin_name ? $plugin_name : $product_slug;
		$this->event_name   = 'addon-' . sanitize_key( $product_slug );

		parent::__construct();

		// Hook into DRM event for this specific add-on.
		add_action( 'bb_drm_addon_event_' . $this->product_slug, array( $this, 'drm_event' ), 10, 3 );
	}

	/**
	 * Runs the DRM check for this add-on.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return void
	 */
	public function run() {
		// NOTE: This method is only called when license is NOT valid.
		// Cleanup when license becomes valid is handled by BB_DRM_Registry::cleanup_addon_drm().
		// So we don't need to check license validity here.

		// Skip DRM checks on development environments.
		if ( BB_DRM_Helper::is_dev_environment() ) {
			return;
		}

		// Get the event for this add-on.
		$event = $this->get_latest_event();

		if ( $event ) {
			$days = BB_DRM_Helper::days_elapsed( $event->created_at );

			// Timeline for paid add-ons per BuddyBoss DRM Messaging.md:
			// 0-7 days: No impact (grace period)
			// 7-13 days: LOW - Plugin notification only (inbox)
			// 14-21 days: MEDIUM - Yellow admin notice + Site Health + Plugin notification
			// 21-30 days: HIGH - Orange admin notice + Site Health + Plugin notification + Email
			// 30+ days: LOCKED - Red admin notice + Site Health (Critical) + Plugin notification + Email + Features disabled.
			if ( $days >= 7 && $days <= 13 ) {
				$this->set_status( BB_DRM_Helper::DRM_LOW );
			} elseif ( $days >= 14 && $days <= 21 ) {
				$this->set_status( BB_DRM_Helper::DRM_MEDIUM );
			} elseif ( $days >= 22 && $days <= 30 ) {
				$this->set_status( BB_DRM_Helper::DRM_HIGH );
			} elseif ( $days >= 31 ) {
				$this->set_status( BB_DRM_Helper::DRM_LOCKED );
			}

			// Always create IPN for current status, regardless of whether event was just fired.
			// This ensures notifications are always visible for all current statuses.
			if ( '' !== $this->drm_status ) {
				$this->create_inplugin_notification( $this->drm_status );
			}
		}

		// DRM status detected - fire the event for email/admin notices.
		if ( '' !== $this->drm_status ) {
			do_action( 'bb_drm_addon_event_' . $this->product_slug, $event, $days, $this->drm_status );
		}
	}

	/**
	 * Check if this specific add-on has a valid license.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return bool True if licensed, false otherwise.
	 */
	public function is_addon_licensed() {
		// Cache result per request to avoid repeated IPN calls.
		static $cache = array();

		if ( isset( $cache[ $this->product_slug ] ) ) {
			return $cache[ $this->product_slug ];
		}

		// Check if on staging server (always allow).
		if ( $this->is_staging_server() ) {
			$cache[ $this->product_slug ] = true;
			return true;
		}

		// Check if Platform has valid license.
		if ( ! BB_DRM_Helper::is_valid() ) {
			$cache[ $this->product_slug ] = false;
			return false;
		}

		// Check if this specific product is enabled in Mothership.
		if ( ! class_exists( '\BuddyBoss\Core\Admin\Mothership\BB_Addons_Manager' ) ) {
			$cache[ $this->product_slug ] = false;
			return false;
		}

		$result                       = \BuddyBoss\Core\Admin\Mothership\BB_Addons_Manager::checkProductBySlug( $this->product_slug );
		$cache[ $this->product_slug ] = $result;

		return $result;
	}

	/**
	 * Check if running on staging server.
	 * Staging server detection is disabled as it's handled by BB_DRM_Helper::is_dev_environment().
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return bool Always returns false.
	 */
	private function is_staging_server() {
		return false;
	}

	/**
	 * Creates an in-plugin notification for this add-on.
	 * Creates addon-specific notifications in addition to the consolidated notification.
	 * Each addon gets its own notification with a unique type identifier.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param string $drm_status The DRM status.
	 */
	protected function create_inplugin_notification( $drm_status ) {
		$drm_info = BB_DRM_Helper::get_info( $drm_status, $this->event_name, 'inplugin' );
		if ( empty( $drm_info['heading'] ) ) {
			return;
		}

		// Determine icon based on severity.
		if ( BB_DRM_Helper::DRM_LOCKED === $drm_status || BB_DRM_Helper::DRM_HIGH === $drm_status ) {
			$icon_url = buddypress()->plugin_url . 'bp-core/images/dh-icon.png'; // Red.
		} else {
			$icon_url = buddypress()->plugin_url . 'bp-core/images/dl-icon.png'; // Yellow.
		}

		$notifications = new BB_Notifications();
		// Use addon-specific type to keep these separate from consolidated notification.
		// Format: bb-drm-addon-{product-slug}.
		$notification_type = 'bb-drm-addon-' . $this->product_slug;
		$notification_id   = 'license_' . $this->event_name . '_' . BB_DRM_Helper::get_status_key( $drm_status );

		$notifications->add(
			array(
				'id'      => $notification_id,
				'title'   => $drm_info['heading'],
				'content' => $drm_info['message'],
				'type'    => $notification_type,
				'segment' => '',
				'saved'   => time(),
				'end'     => '',
				'icon'    => $icon_url,
				'buttons' => array(
					'main'      => array(
						'text'   => __( 'Activate Your License', 'buddyboss' ),
						'url'    => $drm_info['activation_link'],
						'target' => '_self',
					),
					'secondary' => array(
						'text'   => __( 'Contact Support', 'buddyboss' ),
						'url'    => $drm_info['support_link'],
						'target' => '_blank',
					),
				),
			)
		);
	}

	/**
	 * Sends an email notification for this add-on.
	 * NOTE: Individual addon emails are disabled. All emails are sent via
	 * BB_DRM_Registry::send_consolidated_email() to prevent duplicate emails.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param string $drm_status The DRM status.
	 */
	protected function send_email( $drm_status ) {
		// No-op: Consolidated emails handled by BB_DRM_Registry.
	}

	/**
	 * Displays admin notices related to DRM for this add-on.
	 *
	 * NOTE: Individual addon notices are disabled. All notices are rendered via
	 * BB_DRM_Registry::render_consolidated_admin_notices() to prevent duplicate notices.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	public function admin_notices() {
		// No-op: Consolidated notices handled by BB_DRM_Registry.
	}

	/**
	 * Check if this add-on's features should be locked.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return bool True if features should be locked.
	 */
	public function should_lock_features() {
		// First check if the license is valid.
		if ( $this->is_addon_licensed() ) {
			return false;
		}

		// Check if there's a DRM event for this addon.
		$event = $this->get_latest_event();
		if ( ! $event ) {
			return false;
		}

		// Calculate days elapsed.
		$days = BB_DRM_Helper::days_elapsed( $event->created_at );

		// Lock if grace period has expired (31+ days for addons per BuddyBoss DRM Messaging.md).
		return $days >= 31;
	}
}
