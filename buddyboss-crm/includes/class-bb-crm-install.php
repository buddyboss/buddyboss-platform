<?php
/**
 * BuddyBoss CRM Installation
 *
 * Handles database table creation and plugin setup
 *
 * @package BuddyBossCRM
 * @since 1.0.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * BuddyBoss CRM Installation Class
 *
 * @since 1.0.0
 */
class BB_CRM_Install {

    /**
     * Database version
     *
     * @var string
     */
    private static $db_version = '1.2.0';

    /**
     * Run installation
     *
     * @since 1.0.0
     */
    public static function install() {
        global $wpdb;

        // Check if we need to install or upgrade
        $installed_version = get_option( 'bb_crm_db_version', '0' );

        if ( version_compare( $installed_version, self::$db_version, '<' ) ) {
            self::migrate_table_names();
            self::create_tables();
            self::set_default_options();
            update_option( 'bb_crm_db_version', self::$db_version );
        } else {
            // Always run dbDelta to pick up any schema changes.
            self::create_tables();
        }
    }

    /**
     * Rename legacy bp_ tables to bb_ naming convention.
     * Safe to run multiple times — skips tables that don't exist or already have the new name.
     *
     * @since 1.2.0
     */
    private static function migrate_table_names() {
        global $wpdb;

        $bb_prefix = function_exists( 'bp_core_get_table_prefix' ) ? bp_core_get_table_prefix() : $wpdb->base_prefix;

        $renames = array(
            'bp_tags'                  => 'bb_tags',
            'bp_user_tags'             => 'bb_user_tags',
            'bp_tag_categories'        => 'bb_tag_categories',
            'bp_user_lists'            => 'bb_user_lists',
            'bp_user_list_tags'        => 'bb_user_list_tags',
            'bp_user_list_assignments' => 'bb_user_list_assignments',
            'bp_automation_rules'      => 'bb_automation_rules',
            'bp_automation_queue'      => 'bb_automation_queue',
            'bp_automation_log'        => 'bb_automation_log',
            'bp_tag_history'           => 'bb_tag_history',
            'bp_crm_campaigns'         => 'bb_crm_campaigns',
        );

        foreach ( $renames as $old => $new ) {
            $old_table = $bb_prefix . $old;
            $new_table = $bb_prefix . $new;

            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
            $old_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $old_table ) );
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
            $new_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $new_table ) );

            if ( $old_exists && ! $new_exists ) {
                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.SchemaChange
                $wpdb->query( "RENAME TABLE `{$old_table}` TO `{$new_table}`" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            }
        }
    }

    /**
     * Create database tables
     *
     * @since 1.0.0
     */
    private static function create_tables() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        $bb_prefix = function_exists( 'bp_core_get_table_prefix' ) ? bp_core_get_table_prefix() : $wpdb->base_prefix;

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

        // Table 1: Tags
        $sql_tags = "CREATE TABLE {$bb_prefix}bb_tags (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            name varchar(200) NOT NULL,
            slug varchar(200) NOT NULL,
            color varchar(7) DEFAULT '#0073aa',
            icon varchar(100) DEFAULT '',
            visibility enum('public','members-only','admin-only','self-only') DEFAULT 'public',
            priority int(11) DEFAULT 0,
            expires_days int(11) DEFAULT 0,
            description text,
            category_id bigint(20) UNSIGNED DEFAULT 0,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY slug (slug),
            KEY name (name(191)),
            KEY visibility (visibility),
            KEY priority (priority),
            KEY category_id (category_id),
            KEY visibility_priority (visibility, priority)
        ) $charset_collate;";

        // Table 2: User Tags (assignments)
        $sql_user_tags = "CREATE TABLE {$bb_prefix}bb_user_tags (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id bigint(20) UNSIGNED NOT NULL,
            tag_id bigint(20) UNSIGNED NOT NULL,
            applied_by bigint(20) UNSIGNED DEFAULT 0,
            applied_at datetime NOT NULL,
            expires_at datetime DEFAULT NULL,
            source varchar(50) DEFAULT 'manual',
            meta longtext,
            PRIMARY KEY  (id),
            UNIQUE KEY user_tag (user_id, tag_id),
            KEY user_id (user_id),
            KEY tag_id (tag_id),
            KEY user_id_applied_at (user_id, applied_at),
            KEY tag_id_applied_at (tag_id, applied_at),
            KEY expires_at (expires_at),
            KEY source (source),
            KEY user_expires (user_id, expires_at)
        ) $charset_collate;";

        // Table 3: Tag Categories
        $sql_categories = "CREATE TABLE {$bb_prefix}bb_tag_categories (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            name varchar(200) NOT NULL,
            slug varchar(200) NOT NULL,
            description text,
            parent_id bigint(20) UNSIGNED DEFAULT 0,
            order_position int(11) DEFAULT 0,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY slug (slug),
            KEY parent_id (parent_id),
            KEY order_position (order_position)
        ) $charset_collate;";

        // Table 4: User Lists
        $sql_lists = "CREATE TABLE {$bb_prefix}bb_user_lists (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            name varchar(200) NOT NULL,
            slug varchar(200) NOT NULL,
            description text,
            list_type enum('static','dynamic') DEFAULT 'static',
            match_type varchar(10) DEFAULT 'any',
            conditions longtext DEFAULT NULL,
            created_by bigint(20) UNSIGNED DEFAULT 0,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY slug (slug),
            KEY created_by (created_by),
            KEY match_type (match_type),
            KEY list_type (list_type)
        ) $charset_collate;";

        // Table 5: User List Tags (many-to-many)
        $sql_list_tags = "CREATE TABLE {$bb_prefix}bb_user_list_tags (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            list_id bigint(20) UNSIGNED NOT NULL,
            tag_id bigint(20) UNSIGNED NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY list_tag (list_id, tag_id),
            KEY list_id (list_id),
            KEY tag_id (tag_id)
        ) $charset_collate;";

        // Table 6: User List Assignments (auto-synced)
        $sql_list_assignments = "CREATE TABLE {$bb_prefix}bb_user_list_assignments (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            list_id bigint(20) UNSIGNED NOT NULL,
            user_id bigint(20) UNSIGNED NOT NULL,
            assigned_at datetime NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY list_user (list_id, user_id),
            KEY list_id (list_id),
            KEY user_id (user_id)
        ) $charset_collate;";

        // Table 7: Automation Rules
        $sql_automation_rules = "CREATE TABLE {$bb_prefix}bb_automation_rules (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            name varchar(200) NOT NULL,
            description text,
            trigger_type varchar(50) NOT NULL,
            trigger_config longtext,
            conditions longtext,
            actions longtext,
            is_active tinyint(1) DEFAULT 1,
            priority int(11) DEFAULT 0,
            created_by bigint(20) UNSIGNED DEFAULT 0,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY trigger_type (trigger_type),
            KEY is_active (is_active),
            KEY priority (priority),
            KEY trigger_active (trigger_type, is_active)
        ) $charset_collate;";

        // Table 8: Automation Queue
        $sql_automation_queue = "CREATE TABLE {$bb_prefix}bb_automation_queue (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            rule_id bigint(20) UNSIGNED NOT NULL,
            user_id bigint(20) UNSIGNED NOT NULL,
            action_type varchar(50) NOT NULL,
            action_config longtext,
            status enum('pending','processing','completed','failed') DEFAULT 'pending',
            attempts int(11) DEFAULT 0,
            scheduled_at datetime NOT NULL,
            processed_at datetime DEFAULT NULL,
            error_message text,
            PRIMARY KEY  (id),
            KEY rule_id (rule_id),
            KEY user_id (user_id),
            KEY status (status),
            KEY scheduled_at (scheduled_at),
            KEY status_scheduled (status, scheduled_at)
        ) $charset_collate;";

        // Table 9: Automation Log
        $sql_automation_log = "CREATE TABLE {$bb_prefix}bb_automation_log (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            rule_id bigint(20) UNSIGNED NOT NULL,
            user_id bigint(20) UNSIGNED NOT NULL,
            action_type varchar(50) NOT NULL,
            status enum('success','failed') NOT NULL,
            executed_at datetime NOT NULL,
            execution_time_ms int(11) DEFAULT 0,
            details longtext,
            error_message text,
            PRIMARY KEY  (id),
            KEY rule_id (rule_id),
            KEY user_id (user_id),
            KEY executed_at (executed_at),
            KEY status (status)
        ) $charset_collate;";

        // Table 10: Tag History (audit trail)
        $sql_tag_history = "CREATE TABLE {$bb_prefix}bb_tag_history (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id bigint(20) UNSIGNED NOT NULL,
            tag_id bigint(20) UNSIGNED NOT NULL,
            action enum('added','removed','expired') NOT NULL,
            performed_by bigint(20) UNSIGNED DEFAULT 0,
            performed_at datetime NOT NULL,
            source varchar(50) DEFAULT 'manual',
            notes text,
            PRIMARY KEY  (id),
            KEY user_id (user_id),
            KEY tag_id (tag_id),
            KEY performed_at (performed_at),
            KEY action (action),
            KEY user_tag (user_id, tag_id)
        ) $charset_collate;";

        // Table 11: Email Campaigns
        $sql_campaigns = "CREATE TABLE {$bb_prefix}bb_crm_campaigns (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            name varchar(200) NOT NULL,
            subject varchar(500) NOT NULL DEFAULT '',
            preheader varchar(500) NOT NULL DEFAULT '',
            from_name varchar(200) NOT NULL DEFAULT '',
            from_email varchar(200) NOT NULL DEFAULT '',
            reply_to varchar(200) NOT NULL DEFAULT '',
            body longtext,
            recipient_type varchar(20) DEFAULT 'list',
            recipient_ids longtext,
            status enum('draft','sending','sent','failed') DEFAULT 'draft',
            sent_at datetime DEFAULT NULL,
            total_recipients int(11) DEFAULT 0,
            created_by bigint(20) UNSIGNED DEFAULT 0,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY status (status),
            KEY created_by (created_by)
        ) $charset_collate;";

        // Execute table creation
        dbDelta( $sql_tags );
        dbDelta( $sql_user_tags );
        dbDelta( $sql_categories );
        dbDelta( $sql_lists );
        dbDelta( $sql_list_tags );
        dbDelta( $sql_list_assignments );
        dbDelta( $sql_automation_rules );
        dbDelta( $sql_automation_queue );
        dbDelta( $sql_automation_log );
        dbDelta( $sql_tag_history );
        dbDelta( $sql_campaigns );

        wp_cache_flush();
        flush_rewrite_rules();
    }

    /**
     * Set default options
     *
     * @since 1.0.0
     */
    private static function set_default_options() {
        // Default settings
        $defaults = array(
            'bb_crm_enable_tag_history' => '1',
            'bb_crm_enable_automations' => '1',
            'bb_crm_enable_broadcasts' => '1',
            'bb_crm_tags_per_page' => '20',
            'bb_crm_enable_frontend_display' => '1',
        );

        foreach ( $defaults as $key => $value ) {
            if ( ! get_option( $key ) ) {
                add_option( $key, $value );
            }
        }
    }

    /**
     * Get database version
     *
     * @since 1.0.0
     * @return string
     */
    public static function get_db_version() {
        return self::$db_version;
    }
}
