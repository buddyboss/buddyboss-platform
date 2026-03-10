<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Creates and updates the automations database tables.
 */
class BB_CRM_Auto_Install {

	const DB_VERSION = '1.2.0';

	public static function install() {
		global $wpdb;
		$charset = $wpdb->get_charset_collate();

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		// Automations table.
		dbDelta( "CREATE TABLE {$wpdb->prefix}bp_crm_automations (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			name varchar(200) NOT NULL,
			description text,
			status varchar(20) NOT NULL DEFAULT 'active',
			trigger_type varchar(100) NOT NULL,
			trigger_config longtext,
			conditions longtext,
			actions longtext,
			priority int(11) NOT NULL DEFAULT 10,
			run_count bigint(20) NOT NULL DEFAULT 0,
			last_run datetime DEFAULT NULL,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY status (status),
			KEY trigger_type (trigger_type)
		) $charset;" );

		// Automation log table.
		dbDelta( "CREATE TABLE {$wpdb->prefix}bp_crm_automation_log (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			automation_id bigint(20) NOT NULL,
			user_id bigint(20) NOT NULL DEFAULT 0,
			trigger_type varchar(100) NOT NULL,
			trigger_data longtext,
			conditions_passed tinyint(1) NOT NULL DEFAULT 1,
			actions_result longtext,
			status varchar(20) NOT NULL DEFAULT 'success',
			error_message text,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY automation_id (automation_id),
			KEY user_id (user_id),
			KEY status (status),
			KEY created_at (created_at)
		) $charset;" );

		// Automation queue table (for wait/delay sequences).
		dbDelta( "CREATE TABLE {$wpdb->prefix}bp_crm_automation_queue (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			automation_id bigint(20) NOT NULL,
			user_id bigint(20) NOT NULL,
			trigger_type varchar(100) NOT NULL,
			trigger_data longtext,
			pending_actions longtext NOT NULL,
			scheduled_at datetime NOT NULL,
			status varchar(20) NOT NULL DEFAULT 'pending',
			created_at datetime NOT NULL,
			PRIMARY KEY (id),
			KEY status_scheduled (status, scheduled_at),
			KEY automation_id (automation_id)
		) $charset;" );

		// Email opens tracking table (for sequence stop-if-opened logic).
		dbDelta( "CREATE TABLE {$wpdb->prefix}bp_crm_email_opens (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			user_id bigint(20) NOT NULL,
			campaign_id bigint(20) NOT NULL,
			token varchar(64) NOT NULL,
			sent_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			opened_at datetime DEFAULT NULL,
			PRIMARY KEY (id),
			UNIQUE KEY token (token),
			KEY user_campaign (user_id, campaign_id)
		) $charset;" );

		update_option( 'bb_crm_auto_db_version', self::DB_VERSION );
	}

	public static function get_db_version() {
		return get_option( 'bb_crm_auto_db_version', '—' );
	}
}
