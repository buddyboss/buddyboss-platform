<?php
/**
 * Broadcast_Frontend — frontend announcement delivery.
 *
 * On the `wp` hook: queries active announcements, evaluates targeting
 * server-side, and passes only matched announcements to the browser via
 * wp_localize_script. Assets are only enqueued when matches exist.
 *
 * AJAX: handles impression + CTA click events, and dismiss events from the frontend.
 * Both wp_ajax_ and wp_ajax_nopriv_ are registered (nopriv fires for
 * logged-in users on frontend pages — this is required, not optional).
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'DAY_IN_SECONDS' ) ) {
	define( 'DAY_IN_SECONDS', 86400 );
}

class Broadcast_Frontend {

	/**
	 * Register hooks. Called once by Broadcast::load_frontend().
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'wp',                              array( __CLASS__, 'maybe_serve_announcements' ) );
		add_action( 'wp_ajax_broadcast_event',         array( __CLASS__, 'handle_event' ) );
		add_action( 'wp_ajax_nopriv_broadcast_event',  array( __CLASS__, 'handle_event' ) );
		add_action( 'wp_ajax_broadcast_dismiss',        array( __CLASS__, 'handle_dismiss' ) );
		add_action( 'wp_ajax_nopriv_broadcast_dismiss', array( __CLASS__, 'handle_dismiss' ) );
	}

	/**
	 * Evaluate targeting and enqueue assets for matched announcements.
	 *
	 * Fires on the `wp` hook — after WP_Query, before template load.
	 * BuddyBoss APIs (bp_has_member_type, groups_is_user_member) are safe
	 * to call here because bp_init has already fired.
	 *
	 * @return void
	 */
	public static function maybe_serve_announcements() {
		if ( is_admin() || ! is_user_logged_in() ) {
			return;
		}

		$user_id       = get_current_user_id();
		$announcements = Broadcast_Announcement::get_active();

		if ( empty( $announcements ) ) {
			return;
		}

		$matched = array();
		foreach ( $announcements as $ann ) {
			if ( Broadcast_Targeting::user_matches( $user_id, (int) $ann->id ) ) {
				// Check page_url restriction (TGT-07).
				$rules          = Broadcast_Announcement::get_targeting_rules( (int) $ann->id );
				$page_url_rules = array_filter( $rules, function( $r ) { return $r->rule_type === 'page_url'; } );
				if ( ! empty( $page_url_rules ) ) {
					$page_rule   = reset( $page_url_rules );
					$page_config = json_decode( $page_rule->rule_config, true );
					if ( ! Broadcast_Targeting::page_url_matches( $page_config ) ) {
						continue; // Skip — wrong page.
					}
				}

				// Check repeat-schedule dismissal (TGT-09).
				$reopen_days = (int) ( $ann->reopen_after_days ?? 0 );
				if ( $reopen_days > 0 ) {
					global $wpdb;
					$dismissed_at = $wpdb->get_var( $wpdb->prepare(
						"SELECT dismissed_at FROM {$wpdb->prefix}broadcast_user_dismissals
						 WHERE announcement_id = %d AND user_id = %d",
						$ann->id,
						$user_id
					) );

					if ( $dismissed_at ) {
						$days_since = ( time() - strtotime( $dismissed_at ) ) / DAY_IN_SECONDS;
						if ( $days_since < $reopen_days ) {
							continue; // Still within suppress window.
						}
					}
				}

				$matched[] = array(
					'id'                => (int) $ann->id,
					'type'              => $ann->type,
					'title'             => $ann->title,
					'body'              => $ann->body,
					'image_id'          => (int) $ann->image_id,
					'image_url'         => $ann->image_id ? wp_get_attachment_image_url( (int) $ann->image_id, 'medium' ) : '',
					'cta_label'         => $ann->cta_label,
					'cta_url'           => $ann->cta_url,
					'display_position'  => $ann->display_position,
					'closeable'         => (bool) $ann->closeable,
					'reopen_after_days' => (int) $ann->reopen_after_days,
				);
			}
		}

		if ( empty( $matched ) ) {
			return; // No matched announcements — enqueue nothing.
		}

		wp_enqueue_script(
			'broadcast-frontend',
			BROADCAST_URL . 'assets/js/broadcast-frontend.js',
			array( 'jquery' ),
			BROADCAST_VERSION,
			true
		);
		wp_enqueue_style(
			'broadcast-frontend',
			BROADCAST_URL . 'assets/css/broadcast-frontend.css',
			array(),
			BROADCAST_VERSION
		);
		wp_localize_script( 'broadcast-frontend', 'broadcastData', array(
			'announcements' => $matched,
			'ajaxUrl'       => admin_url( 'admin-ajax.php' ),
			'nonce'         => wp_create_nonce( 'broadcast_event' ),
		) );
	}

	/**
	 * AJAX: Record or update a dismissal for the current user.
	 * Uses INSERT ... ON DUPLICATE KEY UPDATE for upsert behavior.
	 *
	 * @return void
	 */
	public static function handle_dismiss() {
		check_ajax_referer( 'broadcast_event', 'nonce' );

		if ( ! is_user_logged_in() ) {
			wp_send_json_error( array( 'message' => 'Not authenticated.' ) );
			return;
		}

		$announcement_id = absint( $_POST['announcement_id'] ?? 0 );
		if ( ! $announcement_id ) {
			wp_send_json_error( array( 'message' => 'Invalid announcement ID.' ) );
			return;
		}

		global $wpdb;
		$table   = $wpdb->prefix . 'broadcast_user_dismissals';
		$user_id = get_current_user_id();
		$now     = current_time( 'mysql' );

		$wpdb->query( $wpdb->prepare(
			"INSERT INTO {$table} (announcement_id, user_id, dismissed_at)
			 VALUES (%d, %d, %s)
			 ON DUPLICATE KEY UPDATE dismissed_at = %s",
			$announcement_id,
			$user_id,
			$now,
			$now
		) );

		wp_send_json_success();
	}

	/**
	 * AJAX: Record an announcement event (impression or cta_click).
	 *
	 * Both wp_ajax_ and wp_ajax_nopriv_ are required for frontend AJAX
	 * from logged-in users (wp_ajax_nopriv_ fires for any non-WP-admin request).
	 *
	 * Impression deduplication: one impression per user per announcement per day.
	 *
	 * @return void
	 */
	public static function handle_event() {
		check_ajax_referer( 'broadcast_event', 'nonce' );

		if ( ! is_user_logged_in() ) {
			wp_send_json_error( array( 'message' => 'Not authenticated.' ) );
			return;
		}

		$announcement_id = absint( $_POST['announcement_id'] ?? 0 );
		$event_type      = sanitize_key( $_POST['event_type'] ?? '' );

		if ( ! $announcement_id || ! in_array( $event_type, array( 'impression', 'cta_click' ), true ) ) {
			wp_send_json_error( array( 'message' => 'Invalid parameters.' ) );
			return;
		}

		global $wpdb;

		// Deduplicate impressions: one per user per announcement per calendar day.
		if ( $event_type === 'impression' ) {
			$user_id  = get_current_user_id();
			$existing = $wpdb->get_var( $wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->prefix}broadcast_analytics_events
				 WHERE announcement_id = %d
				   AND user_id = %d
				   AND event_type = 'impression'
				   AND DATE(occurred_at) = CURDATE()",
				$announcement_id,
				$user_id
			) );
			if ( $existing > 0 ) {
				wp_send_json_success(); // Already recorded today.
				return;
			}
		}

		$wpdb->insert(
			$wpdb->prefix . 'broadcast_analytics_events',
			array(
				'announcement_id' => $announcement_id,
				'user_id'         => get_current_user_id(),
				'event_type'      => $event_type,
				'occurred_at'     => current_time( 'mysql' ),
			),
			array( '%d', '%d', '%s', '%s' )
		);

		wp_send_json_success();
	}
}
