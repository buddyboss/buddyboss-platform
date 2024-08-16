<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

/**
 * Class BB_Core_Usage_Ctrl
 */
class BB_Core_Usage_Ctrl extends BB_Core_Base_Ctrl {

	/**
	 * Load hooks
	 */
	public function load_hooks() {

		$disable_send_data = bp_get_option( 'bb_disable_send_data', false );
		if ( ! $disable_send_data ) {
			add_filter( 'cron_schedules', array( $this, 'intervals' ) );
			add_action( 'bb_snapshot_worker', array( $this, 'snapshot' ) );

			if ( ! ( $snapshot_timestamp = wp_next_scheduled( 'bb_snapshot_worker' ) ) ) {
				wp_schedule_event( time() + BB_Core_Utils::weeks( 1 ), 'bb_snapshot_interval', 'bb_snapshot_worker' );
			}
		}

	}

	/**
	 * Intervals
	 *
	 * @param array $schedules The schedules.
	 *
	 * @return array
	 */
	public function intervals( $schedules ) {
		$schedules['bb_snapshot_interval'] = array(
			'interval' => BB_Core_Utils::weeks( 1 ),
			'display'  => __( 'BuddyBoss Snapshot Interval', 'buddyboss' ),
		);

		return $schedules;
	}

	/**
	 * Snapshot
	 */
	public function snapshot() {

		$disable_send_data = bp_get_option( 'bb_disable_send_data', false );

		if ( $disable_send_data ) {
			return;
		}

		// This is here because we've learned through sad experience that we can't fully
		// rely on WP-CRON to wait for an entire week, so we check here to ensure we're ready.
		$already_sent = BB_Core_Expiring_Option::get( 'sent_snapshot' );

		if ( ! empty( $already_sent ) ) {
			BB_Core_Utils::debug_log( __( 'Your site is attempting to send too many snapshots, we\'ll put an end to that.', 'buddyboss' ) );
			return;
		}

		$ep =
			'aHR0cHM6Ly9tZW1iZXJwcmVz' .
			'cy1hbmFseXRpY3MuaGVyb2t1' .
			'YXBwLmNvbS9zbmFwc2hvdA==';

		$usage = new BB_Core_Usage();
		$body  = wp_json_encode( $usage->snapshot() );

		BB_Core_Utils::error_log( '$body' );
		BB_Core_Utils::error_log( $body );

		$headers = array(
			'Accept'         => 'application/json',
			'Content-Type'   => 'application/json',
			'Content-Length' => strlen( $body ),
		);

		// Setup variable for wp_remote_request.
		$post = array(
			'method'  => 'POST',
			'headers' => $headers,
			'body'    => $body,
		);

		BB_Core_Utils::error_log( print_r( $usage->snapshot(), true ) );

		// $resp = wp_remote_request(base64_decode($ep), $post);

		// 6 days so we don't accidentally miss the weekly cron
		BB_Core_Expiring_Option::set( 'sent_snapshot', 1, BB_Core_Utils::days( 6 ) );
	}

} // End class
