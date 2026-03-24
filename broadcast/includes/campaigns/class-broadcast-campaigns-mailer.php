<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Handles sending campaigns via wp_mail().
 */
class Broadcast_Campaigns_Mailer {

	/**
	 * Queue a campaign for async delivery via WP-Cron batch processing.
	 *
	 * @param int $campaign_id
	 * @return true|WP_Error
	 */
	public static function queue_send( $campaign_id ) {
		global $wpdb;

		$campaign_id = absint( $campaign_id );
		if ( ! $campaign_id ) {
			return new WP_Error( 'invalid_id', __( 'Invalid campaign ID.', 'broadcast' ) );
		}

		$table = $wpdb->prefix . 'broadcast_campaigns';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
		$campaign = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM `{$table}` WHERE id = %d", $campaign_id ) );

		if ( ! $campaign ) {
			return new WP_Error( 'not_found', __( 'Campaign not found.', 'broadcast' ) );
		}

		if ( ! in_array( $campaign->status, array( 'draft', 'failed' ), true ) ) {
			return new WP_Error( 'invalid_status', __( 'Campaign is not in a sendable status.', 'broadcast' ) );
		}

		$user_ids = self::resolve_recipients( $campaign );

		if ( empty( $user_ids ) ) {
			return new WP_Error( 'no_recipients', __( 'No recipients found for this campaign.', 'broadcast' ) );
		}

		$batch_size    = defined( 'BROADCAST_CAMP_BATCH_SIZE' ) ? (int) BROADCAST_CAMP_BATCH_SIZE : 50;
		$chunks        = array_chunk( $user_ids, $batch_size );
		$batches_table = $wpdb->prefix . 'broadcast_camp_batches';

		foreach ( $chunks as $chunk ) {
			$wpdb->insert(
				$batches_table,
				array(
					'campaign_id' => $campaign_id,
					'user_ids'    => wp_json_encode( $chunk ),
					'status'      => 'pending',
					'created_at'  => current_time( 'mysql' ),
				),
				array( '%d', '%s', '%s', '%s' )
			);
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->update(
			$table,
			array( 'status' => 'queued', 'updated_at' => current_time( 'mysql' ) ),
			array( 'id' => $campaign_id ),
			array( '%s', '%s' ),
			array( '%d' )
		);

		if ( ! wp_next_scheduled( 'broadcast_process_campaign_queue' ) ) {
			wp_schedule_event( time(), 'broadcast_every_five_minutes', 'broadcast_process_campaign_queue' );
		}

		return true;
	}

	/**
	 * Send a single campaign email to one user.
	 * Called by Broadcast_Campaigns_Cron for each recipient.
	 *
	 * @param int $campaign_id
	 * @param int $user_id
	 * @return bool
	 */
	public static function send_single( $campaign_id, $user_id ) {
		global $wpdb;

		$campaign_id = absint( $campaign_id );
		$user_id     = absint( $user_id );

		if ( ! $campaign_id || ! $user_id ) {
			return false;
		}

		$table = $wpdb->prefix . 'broadcast_campaigns';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
		$campaign = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM `{$table}` WHERE id = %d", $campaign_id ) );

		if ( ! $campaign ) {
			return false;
		}

		$user = get_userdata( $user_id );
		if ( ! $user || ! is_email( $user->user_email ) ) {
			return false;
		}

		if ( Broadcast_Camp_Unsubscribe::is_unsubscribed( $user->user_email ) ) {
			return false;
		}

		$subject = self::replace_merge_tags( $campaign->subject, $user );

		if ( ! empty( $campaign->body_post_id ) ) {
			$raw_html = Broadcast_Camp_CPT::render_email_html( absint( $campaign->body_post_id ) );
			$body     = self::replace_merge_tags( $raw_html, $user );
		} else {
			$body = self::replace_merge_tags( $campaign->body, $user );
		}

		$headers = array( 'Content-Type: text/html; charset=UTF-8' );
		if ( ! empty( $campaign->from_name ) && ! empty( $campaign->from_email ) ) {
			$headers[] = 'From: ' . sanitize_text_field( $campaign->from_name ) . ' <' . sanitize_email( $campaign->from_email ) . '>';
		}
		if ( ! empty( $campaign->reply_to ) ) {
			$headers[] = 'Reply-To: ' . sanitize_email( $campaign->reply_to );
		}

		// ── Open tracking pixel ────────────────────────────────────────────────
		$opens_table = $wpdb->prefix . 'broadcast_camp_opens';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
		$opens_table_exists = $wpdb->get_var( "SHOW TABLES LIKE '{$opens_table}'" ) === $opens_table;
		if ( $opens_table_exists ) {
			$token = substr( hash_hmac( 'sha256', $user_id . ':' . $campaign_id, wp_salt( 'auth' ) ), 0, 64 );
			$wpdb->query( $wpdb->prepare(
				"INSERT IGNORE INTO `{$opens_table}` (user_id, campaign_id, token, sent_at) VALUES (%d, %d, %s, %s)",
				$user_id, $campaign_id, $token, current_time( 'mysql' )
			) );
			$pixel_url = add_query_arg( 'broadcast_camp_open', $token, home_url( '/' ) );
			$body     .= '<img src="' . esc_url( $pixel_url ) . '" width="1" height="1" alt="" style="display:none;border:0">';
		}

		// ── Click tracking ─────────────────────────────────────────────────────
		$clicks_table = $wpdb->prefix . 'broadcast_camp_clicks';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
		$clicks_table_exists = $wpdb->get_var( "SHOW TABLES LIKE '{$clicks_table}'" ) === $clicks_table;
		if ( $clicks_table_exists ) {
			$body = self::rewrite_links_for_tracking( $body, $campaign_id, $user_id );
		}

		return wp_mail( $user->user_email, $subject, $body, $headers );
	}

	/**
	 * Resolve recipient user IDs based on campaign settings.
	 *
	 * @param object $campaign
	 * @return int[]
	 */
	private static function resolve_recipients( $campaign ) {
		$user_ids = array();

		if ( 'all' === $campaign->recipient_type ) {
			$users    = get_users( array( 'fields' => 'ID', 'number' => -1, 'orderby' => 'ID' ) );
			$user_ids = array_map( 'absint', (array) $users );
		}

		return array_unique( array_filter( $user_ids ) );
	}

	/**
	 * Rewrite href links in email body through the click-tracking redirect.
	 *
	 * @param string $body
	 * @param int    $campaign_id
	 * @param int    $user_id
	 * @return string
	 */
	private static function rewrite_links_for_tracking( $body, $campaign_id, $user_id ) {
		global $wpdb;

		$campaign_id = absint( $campaign_id );
		$user_id     = absint( $user_id );

		$body = preg_replace_callback(
			'/href=["\']([^"\']+)["\']/i',
			function( $matches ) use ( $campaign_id, $user_id, $wpdb ) {
				$url = $matches[1];

				if ( empty( $url ) ) return $matches[0];
				if ( strpos( $url, 'broadcast-unsubscribe' ) !== false ) return $matches[0];
				if ( strpos( $url, 'broadcast_camp_open' )   !== false ) return $matches[0];
				if ( strpos( $url, 'broadcast-click/' )      !== false ) return $matches[0];
				if ( strpos( $url, 'mailto:' ) === 0 ) return $matches[0];
				if ( strpos( $url, 'tel:' )    === 0 ) return $matches[0];
				if ( strpos( $url, '#' )       === 0 ) return $matches[0];

				$token        = substr( hash_hmac( 'sha256', $url . ':' . $user_id . ':' . $campaign_id, wp_salt( 'auth' ) ), 0, 64 );
				$clicks_table = $wpdb->prefix . 'broadcast_camp_clicks';

				$wpdb->insert(
					$clicks_table,
					array(
						'campaign_id'  => $campaign_id,
						'user_id'      => $user_id,
						'original_url' => $url,
						'token'        => $token,
						'clicked_at'   => current_time( 'mysql' ),
					),
					array( '%d', '%d', '%s', '%s', '%s' )
				);

				$redirect_url = home_url( '/broadcast-click/' . $token . '/' );
				return 'href="' . esc_url( $redirect_url ) . '"';
			},
			$body
		);

		return $body ? $body : '';
	}

	/**
	 * Replace merge tags in content with user-specific values.
	 *
	 * @param string   $content
	 * @param \WP_User $user
	 * @return string
	 */
	public static function replace_merge_tags( $content, $user ) {
		$replacements = array(
			'{{first_name}}'      => $user->first_name,
			'{{last_name}}'       => $user->last_name,
			'{{display_name}}'    => $user->display_name,
			'{{email}}'           => $user->user_email,
			'{{site_name}}'       => get_bloginfo( 'name' ),
			'{{unsubscribe_url}}' => Broadcast_Camp_Unsubscribe::get_url( $user->user_email ),
		);

		return str_replace( array_keys( $replacements ), array_values( $replacements ), $content );
	}
}
