<?php
/**
 * BuddyBoss DRM Database Installer
 *
 * Handles database table creation and upgrades for the DRM system.
 *
 * @package BuddyBoss\Core\Admin\DRM
 * @since BuddyBoss [BBVERSION]
 */

namespace BuddyBoss\Core\Admin\DRM;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * DRM Installer class for managing database schema.
 */
class BB_DRM_Installer {

	/**
	 * Option name for storing database version.
	 */
	const DB_VERSION_OPTION = 'bb_drm_db_version';

	/**
	 * Current database version.
	 */
	const CURRENT_DB_VERSION = '1.0.0';

	/**
	 * Install or upgrade database tables.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return void
	 */
	public static function install() {
		$installed_version = get_option( self::DB_VERSION_OPTION, '0.0.0' );

		// Check if we need to install or upgrade.
		if ( version_compare( $installed_version, self::CURRENT_DB_VERSION, '<' ) ) {
			self::create_tables();
			self::maybe_migrate_from_options();

			// Update the database version.
			update_option( self::DB_VERSION_OPTION, self::CURRENT_DB_VERSION );

			do_action( 'bb_drm_installed', $installed_version, self::CURRENT_DB_VERSION );
		}
	}

	/**
	 * Create database tables.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return void
	 */
	private static function create_tables() {
		// Create events table.
		$events_created = BB_DRM_Event::create_table();

		do_action( 'bb_drm_tables_created', $events_created );
	}

	/**
	 * Migrate existing DRM events from wp_options to the database table.
	 * This ensures backward compatibility if DRM was already running with options.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return void
	 */
	private static function maybe_migrate_from_options() {
		global $wpdb;

		// Check if table exists.
		if ( ! BB_DRM_Event::table_exists() ) {
			return;
		}

		// Find all DRM event options.
		$drm_options = $wpdb->get_results(
			"SELECT option_name, option_value FROM {$wpdb->options}
			WHERE option_name LIKE 'bb_drm_event_%'"
		);

		if ( empty( $drm_options ) ) {
			return;
		}

		foreach ( $drm_options as $option ) {
			$event_data = maybe_unserialize( $option->option_value );

			if ( ! is_object( $event_data ) || ! isset( $event_data->event_name ) ) {
				continue;
			}

			// Extract event name from option_name (e.g., 'bb_drm_event_no-license' -> 'no-license').
			$event_name = str_replace( 'bb_drm_event_', '', $option->option_name );

			// Determine event type.
			$evt_id_type = 'platform';
			$evt_id      = 1;

			if ( strpos( $event_name, 'addon-' ) === 0 ) {
				$evt_id_type = 'addon';
				// Extract product slug from event name (e.g., 'addon-buddyboss-platform-pro').
				$evt_id = 1; // Could be enhanced to use a hash or ID if needed.
			}

			// Check if event already exists in database.
			$existing_event = BB_DRM_Event::get_one_by_event_and_evt_id_and_evt_id_type(
				$event_name,
				$evt_id,
				$evt_id_type
			);

			if ( ! $existing_event ) {
				// Create event in database.
				$new_event              = new BB_DRM_Event();
				$new_event->event       = $event_name;
				$new_event->evt_id      = $evt_id;
				$new_event->evt_id_type = $evt_id_type;
				$new_event->created_at  = isset( $event_data->created_at ) ? $event_data->created_at : current_time( 'mysql' );

				// Store any additional data as JSON args.
				$args = array();
				foreach ( $event_data as $key => $value ) {
					if ( ! in_array( $key, array( 'event_name', 'created_at' ), true ) ) {
						$args[ $key ] = $value;
					}
				}

				if ( ! empty( $args ) ) {
					$new_event->args = wp_json_encode( $args );
				}

				$new_event->store();
			}
		}

		do_action( 'bb_drm_migration_completed', count( $drm_options ) );
	}

	/**
	 * Uninstall database tables and options.
	 * USE WITH CAUTION - This will delete all DRM data!
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return void
	 */
	public static function uninstall() {
		// Drop tables.
		BB_DRM_Event::drop_table();

		// Delete options.
		delete_option( self::DB_VERSION_OPTION );

		// Clean up any remaining DRM event options.
		global $wpdb;
		$wpdb->query(
			"DELETE FROM {$wpdb->options}
			WHERE option_name LIKE 'bb_drm_event_%'"
		);

		do_action( 'bb_drm_uninstalled' );
	}

	/**
	 * Get the current database version.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return string Database version.
	 */
	public static function get_db_version() {
		return get_option( self::DB_VERSION_OPTION, '0.0.0' );
	}

	/**
	 * Check if database is up to date.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return bool True if up to date.
	 */
	public static function is_db_up_to_date() {
		$installed_version = self::get_db_version();
		return version_compare( $installed_version, self::CURRENT_DB_VERSION, '>=' );
	}

	/**
	 * Force reinstall of tables (useful for development/testing).
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return void
	 */
	public static function force_reinstall() {
		// Drop existing tables.
		BB_DRM_Event::drop_table();

		// Reset version.
		delete_option( self::DB_VERSION_OPTION );

		// Reinstall.
		self::install();
	}

	/**
	 * Get database statistics.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return array Statistics about DRM events.
	 */
	public static function get_stats() {
		return array(
			'db_version'      => self::get_db_version(),
			'table_exists'    => BB_DRM_Event::table_exists(),
			'total_events'    => BB_DRM_Event::get_count(),
			'platform_events' => self::get_platform_event_count(),
			'addon_events'    => self::get_addon_event_count(),
		);
	}

	/**
	 * Get count of platform events.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return int Event count.
	 */
	private static function get_platform_event_count() {
		global $wpdb;

		$table_name = BB_DRM_Event::get_table_name();
		return (int) $wpdb->get_var(
			"SELECT COUNT(*) FROM {$table_name} WHERE evt_id_type = 'platform'"
		);
	}

	/**
	 * Get count of addon events.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return int Event count.
	 */
	private static function get_addon_event_count() {
		global $wpdb;

		$table_name = BB_DRM_Event::get_table_name();
		return (int) $wpdb->get_var(
			"SELECT COUNT(*) FROM {$table_name} WHERE evt_id_type = 'addon'"
		);
	}
}
