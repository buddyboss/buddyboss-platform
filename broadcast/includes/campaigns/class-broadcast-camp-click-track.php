<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Handles click tracking redirects and open pixel tracking for Broadcast Campaigns.
 */
class Broadcast_Camp_Click_Track {

	const CLICK_QUERY_VAR = 'broadcast_camp_click';
	const OPEN_QUERY_VAR  = 'broadcast_camp_open';

	public static function init() {
		add_action( 'init',              array( __CLASS__, 'add_rewrite_rules' ) );
		add_filter( 'query_vars',        array( __CLASS__, 'add_query_vars' ) );
		add_action( 'template_redirect', array( __CLASS__, 'handle_requests' ) );
	}

	public static function add_rewrite_rules() {
		add_rewrite_rule(
			'^broadcast-click/([a-f0-9]+)/?$',
			'index.php?' . self::CLICK_QUERY_VAR . '=$matches[1]',
			'top'
		);
	}

	public static function add_query_vars( $vars ) {
		$vars[] = self::CLICK_QUERY_VAR;
		$vars[] = self::OPEN_QUERY_VAR;
		return $vars;
	}

	public static function handle_requests() {
		// Handle click redirect.
		$click_token = get_query_var( self::CLICK_QUERY_VAR );
		if ( $click_token ) {
			$token        = sanitize_text_field( $click_token );
			$original_url = self::get_original_url( $token );

			if ( ! $original_url ) {
				wp_redirect( home_url( '/' ) );
				exit;
			}

			wp_redirect( esc_url_raw( $original_url ) );
			exit;
		}

		// Handle open pixel.
		$open_token = get_query_var( self::OPEN_QUERY_VAR );
		if ( $open_token ) {
			self::record_open( sanitize_text_field( $open_token ) );
			// Return 1x1 transparent GIF.
			header( 'Content-Type: image/gif' );
			header( 'Cache-Control: no-cache, no-store, must-revalidate' );
			header( 'Pragma: no-cache' );
			header( 'Expires: 0' );
			echo base64_decode( 'R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7' );
			exit;
		}
	}

	private static function get_original_url( $token ) {
		global $wpdb;

		$table = $wpdb->prefix . 'broadcast_camp_clicks';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
		if ( $wpdb->get_var( "SHOW TABLES LIKE '{$table}'" ) !== $table ) {
			return false;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
		$row = $wpdb->get_row( $wpdb->prepare(
			"SELECT id, original_url FROM `{$table}` WHERE token = %s LIMIT 1",
			$token
		) );

		if ( ! $row ) {
			return false;
		}

		return $row->original_url;
	}

	private static function record_open( $token ) {
		global $wpdb;

		$table = $wpdb->prefix . 'broadcast_camp_opens';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
		if ( $wpdb->get_var( "SHOW TABLES LIKE '{$table}'" ) !== $table ) {
			return;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query( $wpdb->prepare(
			"UPDATE `{$table}` SET opened_at = %s WHERE token = %s AND opened_at IS NULL",
			current_time( 'mysql' ),
			$token
		) );
	}
}
