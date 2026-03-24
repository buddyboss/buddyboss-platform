<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * WP-Cron batch processor for Broadcast Campaigns.
 *
 * Registered hook: broadcast_process_campaign_queue
 * Interval: broadcast_every_five_minutes
 */
class Broadcast_Campaigns_Cron {

	const BATCH_SIZE        = 50;
	const STALE_LOCK_MINUTES = 10;

	public static function process_batch_queue() {
		global $wpdb;

		$batches_table   = $wpdb->prefix . 'broadcast_camp_batches';
		$campaigns_table = $wpdb->prefix . 'broadcast_campaigns';

		// ── Stale-lock recovery ───────────────────────────────────────────────
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query( $wpdb->prepare(
			"UPDATE `{$batches_table}`
			 SET status = 'pending'
			 WHERE status = 'processing'
			   AND processed_at < %s",
			gmdate( 'Y-m-d H:i:s', time() - ( self::STALE_LOCK_MINUTES * 60 ) )
		) );

		// ── Claim one pending batch (atomic) ──────────────────────────────────
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
		$batch = $wpdb->get_row(
			"SELECT * FROM `{$batches_table}` WHERE status = 'pending' ORDER BY id ASC LIMIT 1"
		);

		if ( ! $batch ) {
			return;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
		$claimed = $wpdb->query( $wpdb->prepare(
			"UPDATE `{$batches_table}` SET status = 'processing', processed_at = %s WHERE id = %d AND status = 'pending'",
			current_time( 'mysql' ),
			$batch->id
		) );

		if ( ! $claimed ) {
			return;
		}

		// ── Mark campaign as 'sending' on first batch ─────────────────────────
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query( $wpdb->prepare(
			"UPDATE `{$campaigns_table}` SET status = 'sending', updated_at = %s
			 WHERE id = %d AND status = 'queued'",
			current_time( 'mysql' ),
			$batch->campaign_id
		) );

		// ── Process batch ─────────────────────────────────────────────────────
		$user_ids    = json_decode( $batch->user_ids, true );
		$campaign_id = absint( $batch->campaign_id );
		$batch_ok    = true;

		if ( is_array( $user_ids ) && ! empty( $user_ids ) ) {
			foreach ( $user_ids as $user_id ) {
				$user_id = absint( $user_id );
				if ( ! $user_id ) continue;

				$delivery_status = 'skipped';

				if ( method_exists( 'Broadcast_Campaigns_Mailer', 'send_single' ) ) {
					$result          = Broadcast_Campaigns_Mailer::send_single( $campaign_id, $user_id );
					$delivery_status = $result ? 'sent' : 'failed';
				}

				self::log_delivery( $campaign_id, $user_id, $delivery_status );
			}
		}

		// ── Mark batch done ───────────────────────────────────────────────────
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query( $wpdb->prepare(
			"UPDATE `{$batches_table}` SET status = %s WHERE id = %d",
			$batch_ok ? 'done' : 'failed',
			$batch->id
		) );

		// ── Check if all batches are complete for this campaign ────────────────
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
		$remaining = (int) $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM `{$batches_table}` WHERE campaign_id = %d AND status IN ('pending','processing')",
			$campaign_id
		) );

		if ( 0 === $remaining ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
			$sent_count = (int) $wpdb->get_var( $wpdb->prepare(
				"SELECT COUNT(*) FROM `{$wpdb->prefix}broadcast_camp_log` WHERE campaign_id = %d AND status = 'sent'",
				$campaign_id
			) );

			$final_status = ( $sent_count > 0 ) ? 'sent' : 'failed';

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->query( $wpdb->prepare(
				"UPDATE `{$campaigns_table}` SET status = %s, sent_at = %s, total_recipients = %d, updated_at = %s WHERE id = %d",
				$final_status,
				current_time( 'mysql' ),
				$sent_count,
				current_time( 'mysql' ),
				$campaign_id
			) );
		}
	}

	public static function log_delivery( $campaign_id, $user_id, $status ) {
		global $wpdb;

		$user  = get_userdata( $user_id );
		$email = $user ? $user->user_email : '';

		$wpdb->insert(
			$wpdb->prefix . 'broadcast_camp_log',
			array(
				'campaign_id' => absint( $campaign_id ),
				'user_id'     => absint( $user_id ),
				'email'       => sanitize_email( $email ),
				'status'      => sanitize_key( $status ),
				'sent_at'     => current_time( 'mysql' ),
			),
			array( '%d', '%d', '%s', '%s', '%s' )
		);
	}
}
