<?php
/**
 * BuddyBoss DRM Invalid
 *
 * Handles DRM checks when license is invalid or expired.
 *
 * @package BuddyBoss\Core\Admin\DRM
 * @since BuddyBoss [BBVERSION]
 */

namespace BuddyBoss\Core\Admin\DRM;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * DRM: Invalid class.
 */
class BB_DRM_Invalid extends BB_Base_DRM {

	/**
	 * Constructor for the BB_DRM_Invalid class.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	public function __construct() {
		parent::__construct();
		$this->event_name = BB_DRM_Helper::INVALID_LICENSE_EVENT;
		add_action( 'bb_drm_invalid_license_event', array( $this, 'drm_event' ), 10, 3 );
	}

	/**
	 * Runs the DRM invalid check functionality.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	public function run() {
		$event = $this->get_latest_event();

		if ( $event ) {
			$days = BB_DRM_Helper::days_elapsed( $event->created_at );

			// Timeline for Platform invalid/expired license per BuddyBoss DRM Messaging.md:
			// 0-7 days: No messaging (grace period)
			// 7-13 days: Plugin Notification (Inbox)
			// 14-21 days: Plugin Notification + Admin Bar/Notice (Yellow) + Site Health
			// 21-30 days: Plugin Notification + Admin Bar/Notice (Orange) + Site Health + Admin Email
			// 30+ days: Features Disabled + Admin Bar/Notice (Red) + Site Health + Admin Email.
			if ( $days >= 7 && $days <= 13 ) {
				$this->set_status( BB_DRM_Helper::DRM_LOW );
			} elseif ( $days >= 14 && $days <= 21 ) {
				$this->set_status( BB_DRM_Helper::DRM_MEDIUM );
			} elseif ( $days >= 22 && $days <= 30 ) {
				$this->set_status( BB_DRM_Helper::DRM_HIGH );
			} elseif ( $days >= 31 ) {
				$this->set_status( BB_DRM_Helper::DRM_LOCKED );
			}
		}

		// DRM status detected.
		if ( '' !== $this->drm_status ) {
			do_action( 'bb_drm_invalid_license_event', $event, $days, $this->drm_status );
		}
	}
}
