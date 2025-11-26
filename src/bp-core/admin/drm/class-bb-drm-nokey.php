<?php
/**
 * BuddyBoss DRM No Key
 *
 * Handles DRM checks when no license key is present.
 *
 * @package BuddyBoss\Core\Admin\DRM
 * @since 3.0.0
 */

namespace BuddyBoss\Core\Admin\DRM;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * DRM No Key class.
 */
class BB_DRM_NoKey extends BB_Base_DRM {

	/**
	 * Constructor for the BB_DRM_NoKey class.
	 */
	public function __construct() {
		parent::__construct();
		$this->event_name = BB_DRM_Helper::NO_LICENSE_EVENT;
		add_action( 'bb_drm_no_license_event', array( $this, 'drm_event' ), 10, 3 );
	}

	/**
	 * Runs the DRM no-key check functionality.
	 */
	public function run() {
		$event = $this->get_latest_event();

		if ( $event ) {
			$days = BB_DRM_Helper::days_elapsed( $event->created_at );

			// New timeline for Platform no license key:
			// 0-7 days: No impact
			// 7-14 days: Notification informing license key not activated
			// 14-21 days: Yellow Warning (activate license)
			// 21-30 days: Orange Warning (features will be disabled)
			// 30+ days: Red Warning (backend settings blocked)
			if ( $days >= 7 && $days < 14 ) {
				$this->set_status( BB_DRM_Helper::DRM_LOW );
			} elseif ( $days >= 14 && $days < 21 ) {
				$this->set_status( BB_DRM_Helper::DRM_MEDIUM );
			} elseif ( $days >= 21 && $days < 30 ) {
				$this->set_status( BB_DRM_Helper::DRM_HIGH );
			} elseif ( $days >= 30 ) {
				$this->set_status( BB_DRM_Helper::DRM_LOCKED );
			}
		}

		// DRM status detected.
		if ( '' !== $this->drm_status ) {
			do_action( 'bb_drm_no_license_event', $event, $days, $this->drm_status );
		}
	}
}
