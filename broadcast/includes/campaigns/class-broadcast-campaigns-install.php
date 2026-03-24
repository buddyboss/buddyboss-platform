<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Handles DB table creation for Broadcast Campaigns.
 */
class Broadcast_Campaigns_Install {

	public static function install() {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		// ── Campaigns table ────────────────────────────────────────────────────
		$campaigns_table = $wpdb->prefix . 'broadcast_campaigns';
		$sql_campaigns   = "CREATE TABLE {$campaigns_table} (
			id               bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			name             varchar(255) NOT NULL DEFAULT '',
			subject          varchar(255) NOT NULL DEFAULT '',
			preheader        varchar(255) NOT NULL DEFAULT '',
			from_name        varchar(200) NOT NULL DEFAULT '',
			from_email       varchar(200) NOT NULL DEFAULT '',
			reply_to         varchar(200) NOT NULL DEFAULT '',
			body             longtext,
			body_post_id     bigint(20) unsigned NOT NULL DEFAULT 0,
			recipient_type   varchar(20) NOT NULL DEFAULT 'all',
			recipient_ids    longtext,
			status           enum('draft','queued','sending','sent','failed') NOT NULL DEFAULT 'draft',
			created_by       bigint(20) unsigned NOT NULL DEFAULT 0,
			created_at       datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
			updated_at       datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
			sent_at          datetime DEFAULT NULL,
			total_recipients int unsigned NOT NULL DEFAULT 0,
			PRIMARY KEY  (id),
			KEY status (status)
		) {$charset_collate};";
		dbDelta( $sql_campaigns );

		// ── Email Templates table ──────────────────────────────────────────────
		$tpl_table = $wpdb->prefix . 'broadcast_email_templates';
		$sql_tpl   = "CREATE TABLE {$tpl_table} (
			id          bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			name        varchar(200)        NOT NULL DEFAULT '',
			description text,
			subject     varchar(255)        NOT NULL DEFAULT '',
			body        longtext,
			created_by  bigint(20) unsigned NOT NULL DEFAULT 0,
			created_at  datetime            NOT NULL DEFAULT '0000-00-00 00:00:00',
			updated_at  datetime            NOT NULL DEFAULT '0000-00-00 00:00:00',
			PRIMARY KEY  (id)
		) {$charset_collate};";
		dbDelta( $sql_tpl );

		// ── Unsubscribes table ─────────────────────────────────────────────────
		$unsub_table = $wpdb->prefix . 'broadcast_camp_unsubscribes';
		$sql_unsub   = "CREATE TABLE {$unsub_table} (
			id               bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			email            varchar(200)        NOT NULL DEFAULT '',
			unsubscribed_at  datetime            NOT NULL DEFAULT '0000-00-00 00:00:00',
			PRIMARY KEY  (id),
			UNIQUE KEY email (email)
		) {$charset_collate};";
		dbDelta( $sql_unsub );

		// ── Campaign batch queue ───────────────────────────────────────────────
		$batches_table = $wpdb->prefix . 'broadcast_camp_batches';
		$sql_batches   = "CREATE TABLE {$batches_table} (
			id           bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			campaign_id  bigint(20) unsigned NOT NULL,
			user_ids     longtext NOT NULL,
			status       enum('pending','processing','done','failed') NOT NULL DEFAULT 'pending',
			created_at   datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			processed_at datetime DEFAULT NULL,
			PRIMARY KEY  (id),
			KEY campaign_status (campaign_id, status),
			KEY status (status)
		) {$charset_collate};";
		dbDelta( $sql_batches );

		// ── Campaign delivery log ──────────────────────────────────────────────
		$log_table = $wpdb->prefix . 'broadcast_camp_log';
		$sql_log   = "CREATE TABLE {$log_table} (
			id          bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			campaign_id bigint(20) unsigned NOT NULL,
			user_id     bigint(20) unsigned NOT NULL,
			email       varchar(200) NOT NULL DEFAULT '',
			status      enum('sent','failed','skipped') NOT NULL DEFAULT 'sent',
			sent_at     datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY campaign_id (campaign_id),
			KEY user_id (user_id),
			KEY campaign_status (campaign_id, status)
		) {$charset_collate};";
		dbDelta( $sql_log );

		// ── Campaign click tracking ────────────────────────────────────────────
		$clicks_table = $wpdb->prefix . 'broadcast_camp_clicks';
		$sql_clicks   = "CREATE TABLE {$clicks_table} (
			id           bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			campaign_id  bigint(20) unsigned NOT NULL,
			user_id      bigint(20) unsigned NOT NULL DEFAULT 0,
			original_url text NOT NULL,
			token        varchar(64) NOT NULL,
			clicked_at   datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY campaign_id (campaign_id),
			KEY token (token),
			KEY campaign_url (campaign_id, original_url(191))
		) {$charset_collate};";
		dbDelta( $sql_clicks );

		// ── Campaign open tracking ─────────────────────────────────────────────
		$opens_table = $wpdb->prefix . 'broadcast_camp_opens';
		$sql_opens   = "CREATE TABLE {$opens_table} (
			id          bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			campaign_id bigint(20) unsigned NOT NULL,
			user_id     bigint(20) unsigned NOT NULL,
			token       varchar(64) NOT NULL,
			sent_at     datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			opened_at   datetime DEFAULT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY campaign_user (campaign_id, user_id),
			KEY token (token)
		) {$charset_collate};";
		dbDelta( $sql_opens );

		update_option( 'broadcast_camp_flush_rules', '1' );
		update_option( 'broadcast_camp_version', BROADCAST_CAMP_VERSION );
	}
}
