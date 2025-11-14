<?php
/**
 * BuddyBoss DRM Invalid
 *
 * Handles DRM checks when license is invalid or expired.
 *
 * @package BuddyBoss\Core\Admin\DRM
 * @since 3.0.0
 */

namespace BuddyBoss\Core\Admin\DRM;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * DRM Invalid class.
 */
class BB_DRM_Invalid extends BB_Base_DRM {

	/**
	 * Constructor for the BB_DRM_Invalid class.
	 */
	public function __construct() {
		parent::__construct();
		$this->event_name = BB_DRM_Helper::INVALID_LICENSE_EVENT;
		add_action( 'bb_drm_invalid_license_event', array( $this, 'drm_event' ), 10, 3 );
	}

	/**
	 * Runs the DRM invalid check functionality.
	 */
	public function run() {
		$event = $this->get_latest_event();

		if ( $event ) {
			$days = BB_DRM_Helper::days_elapsed( $event->created_at );

			if ( $days >= 7 && $days <= 20 ) {
				$this->set_status( BB_DRM_Helper::DRM_MEDIUM );
			} elseif ( $days >= 21 ) {
				$this->set_status( BB_DRM_Helper::DRM_LOCKED );
			}
		}

		// DRM status detected.
		if ( '' !== $this->drm_status ) {
			do_action( 'bb_drm_invalid_license_event', $event, $days, $this->drm_status );
		}
	}
}
