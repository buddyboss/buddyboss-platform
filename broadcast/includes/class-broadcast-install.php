<?php
/**
 * Broadcast Install — handles DB table creation and schema upgrades.
 *
 * Called from the activation hook in broadcast.php.
 */

defined( 'ABSPATH' ) || exit;

class Broadcast_Install {

    /**
     * DB schema version. Increment when table structure changes.
     *
     * @var string
     */
    private static $db_version = '3.0.0';

    /**
     * Run on plugin activation.
     *
     * @return void
     */
    public static function install() {
        self::create_tables();
        update_option( 'broadcast_db_version', self::$db_version );
    }

    /**
     * Create or upgrade custom DB tables using dbDelta.
     *
     * dbDelta is safe to call on every activation — it compares the
     * existing schema and only applies changes. No data loss risk.
     *
     * @return void
     */
    private static function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        // Table: targeting rules (per-announcement targeting config).
        $sql_targeting = "CREATE TABLE {$wpdb->prefix}broadcast_targeting_rules (
  id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  announcement_id bigint(20) UNSIGNED NOT NULL,
  rule_type varchar(50) NOT NULL,
  rule_config longtext,
  created_at datetime NOT NULL,
  PRIMARY KEY  (id),
  KEY announcement_id (announcement_id),
  KEY rule_type (rule_type)
) $charset_collate;";

        // Table: analytics events (impressions + CTA clicks).
        $sql_analytics = "CREATE TABLE {$wpdb->prefix}broadcast_analytics_events (
  id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  announcement_id bigint(20) UNSIGNED NOT NULL,
  user_id bigint(20) UNSIGNED NOT NULL,
  event_type varchar(20) NOT NULL,
  occurred_at datetime NOT NULL,
  PRIMARY KEY  (id),
  KEY announcement_id (announcement_id),
  KEY user_id (user_id),
  KEY event_type (event_type),
  KEY announcement_event (announcement_id, event_type)
) $charset_collate;";

        // Table: announcements (core announcement records).
        $sql_announcements = "CREATE TABLE {$wpdb->prefix}broadcast_announcements (
  id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  name varchar(255) NOT NULL,
  description text,
  type varchar(20) NOT NULL DEFAULT 'popup',
  status varchar(20) NOT NULL DEFAULT 'active',
  enabled tinyint(1) NOT NULL DEFAULT 1,
  title varchar(255),
  body longtext,
  image_id bigint(20) UNSIGNED DEFAULT NULL,
  cta_label varchar(255),
  cta_url varchar(2083),
  display_position varchar(20) DEFAULT 'middle',
  closeable tinyint(1) NOT NULL DEFAULT 1,
  reopen_after_days int UNSIGNED DEFAULT 0,
  start_date datetime DEFAULT NULL,
  end_date datetime DEFAULT NULL,
  last_sent_inbox_at datetime DEFAULT NULL,
  last_sent_bell_at datetime DEFAULT NULL,
  created_at datetime NOT NULL,
  updated_at datetime NOT NULL,
  PRIMARY KEY  (id),
  KEY type (type),
  KEY enabled (enabled),
  KEY start_date (start_date),
  KEY end_date (end_date)
) $charset_collate;";

        $sql_dismissals = "CREATE TABLE {$wpdb->prefix}broadcast_user_dismissals (
  id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  announcement_id bigint(20) UNSIGNED NOT NULL,
  user_id bigint(20) UNSIGNED NOT NULL,
  dismissed_at datetime NOT NULL,
  PRIMARY KEY  (id),
  UNIQUE KEY user_announcement (user_id, announcement_id),
  KEY announcement_id (announcement_id)
) $charset_collate;";

        dbDelta( $sql_targeting );
        dbDelta( $sql_analytics );
        dbDelta( $sql_announcements );
        dbDelta( $sql_dismissals );
    }
}
