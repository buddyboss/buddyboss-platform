<?php
/**
 * BuddyPress Updater.
 *
 * @package BuddyBoss\Updater
 * @since BuddyPress 1.6.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Is this a fresh installation of BuddyPress?
 *
 * If there is no raw DB version, we infer that this is the first installation.
 *
 * @return bool True if this is a fresh BP install, otherwise false.
 * @since BuddyPress 1.7.0
 */
function bp_is_install() {
	return ! bp_get_db_version_raw();
}

/**
 * Is this a BuddyPress update?
 *
 * Determined by comparing the registered BuddyPress version to the version
 * number stored in the database. If the registered version is greater, it's
 * an update.
 *
 * @return bool True if update, otherwise false.
 * @since BuddyPress 1.6.0
 */
function bp_is_update() {

	// Get current DB version.
	$current_db = (int) bp_get_option( '_bp_db_version' );
	// Get the raw database version.
	$current_live = (int) bp_get_db_version();

	// Pro plugin version history.
	bp_version_bump();
	$bb_plugin_version_history = (array) bp_get_option( 'bb_plugin_version_history', array() );
	$initial_version_data      = ! empty( $bb_plugin_version_history ) ? end( $bb_plugin_version_history ) : array();
	$bb_version_exists         = ! empty( $initial_version_data ) && ! empty( $initial_version_data['version'] ) && (string) BP_PLATFORM_VERSION === (string) $initial_version_data['version'];
	if ( ! $bb_version_exists || $current_live !== $current_db ) {
		$current_date                = new DateTime( 'now', new DateTimeZone( 'UTC' ) );
		$bb_latest_plugin_version    = array(
			'db_version' => $current_live,
			'date'       => $current_date->format( 'Y-m-d H:i:s' ),
			'version'    => BP_PLATFORM_VERSION,
		);
		$bb_plugin_version_history[] = $bb_latest_plugin_version;
		bp_update_option( 'bb_plugin_version_history', array_filter( $bb_plugin_version_history ) );
	}

	$is_update = false;
	if ( $current_live !== $current_db ) {
		$is_update = true;
	}

	// Return the product of version comparison.
	return $is_update;
}

/**
 * Determine whether BuddyPress is in the process of being activated.
 *
 * @param string $basename BuddyPress basename.
 *
 * @return bool True if activating BuddyPress, false if not.
 * @since BuddyPress 1.6.0
 */
function bp_is_activation( $basename = '' ) {
	$bp     = buddypress();
	$action = false;

	if ( ! empty( $_REQUEST['action'] ) && ( '-1' != $_REQUEST['action'] ) ) {
		$action = $_REQUEST['action'];
	} elseif ( ! empty( $_REQUEST['action2'] ) && ( '-1' != $_REQUEST['action2'] ) ) {
		$action = $_REQUEST['action2'];
	}

	// Bail if not activating.
	if ( empty( $action ) || ! in_array( $action, array( 'activate', 'activate-selected' ) ) ) {
		return false;
	}

	// The plugin(s) being activated.
	if ( 'activate' === $action ) {
		$plugins = isset( $_GET['plugin'] ) ? array( $_GET['plugin'] ) : array();
	} else {
		$plugins = isset( $_POST['checked'] ) ? (array) $_POST['checked'] : array();
	}

	// Set basename if empty.
	if ( empty( $basename ) && ! empty( $bp->basename ) ) {
		$basename = $bp->basename;
	}

	// Bail if no basename.
	if ( empty( $basename ) ) {
		return false;
	}

	// Is BuddyPress being activated?
	return in_array( $basename, $plugins );
}

/**
 * Determine whether BuddyPress is in the process of being deactivated.
 *
 * @param string $basename BuddyPress basename.
 *
 * @return bool True if deactivating BuddyPress, false if not.
 * @since BuddyPress 1.6.0
 */
function bp_is_deactivation( $basename = '' ) {
	$bp     = buddypress();
	$action = false;

	if ( ! empty( $_REQUEST['action'] ) && ( '-1' != $_REQUEST['action'] ) ) {
		$action = $_REQUEST['action'];
	} elseif ( ! empty( $_REQUEST['action2'] ) && ( '-1' != $_REQUEST['action2'] ) ) {
		$action = $_REQUEST['action2'];
	}

	// Bail if not deactivating.
	if ( empty( $action ) || ! in_array( $action, array( 'deactivate', 'deactivate-selected' ) ) ) {
		return false;
	}

	// The plugin(s) being deactivated.
	if ( 'deactivate' == $action ) {
		$plugins = isset( $_GET['plugin'] ) ? array( $_GET['plugin'] ) : array();
	} else {
		$plugins = isset( $_POST['checked'] ) ? (array) $_POST['checked'] : array();
	}

	// Set basename if empty.
	if ( empty( $basename ) && ! empty( $bp->basename ) ) {
		$basename = $bp->basename;
	}

	// Bail if no basename.
	if ( empty( $basename ) ) {
		return false;
	}

	// Is bbPress being deactivated?
	return in_array( $basename, $plugins );
}

/**
 * Update the BP version stored in the database to the current version.
 *
 * @since BuddyPress 1.6.0
 */
function bp_version_bump() {
	bp_update_option( '_bp_db_version', bp_get_db_version() );
}

/**
 * Set up the BuddyPress updater.
 *
 * @since BuddyPress 1.6.0
 */
function bp_setup_updater() {

	// Are we running an outdated version of BuddyPress?
	if ( ! bp_is_update() ) {
		return;
	}

	bp_version_updater();
}

/**
 * Initialize an update or installation of BuddyPress.
 *
 * BuddyPress's version updater looks at what the current database version is,
 * and runs whatever other code is needed - either the "update" or "install"
 * code.
 *
 * This is most often used when the data schema changes, but should also be used
 * to correct issues with BuddyPress metadata silently on software update.
 *
 * @since BuddyPress 1.7.0
 */
function bp_version_updater() {

	// Get current DB version.
	$current_db = (int) bp_get_option( '_bp_db_version' );
	// Get the raw database version.
	$raw_db_version = (int) bp_get_db_version_raw();

	/**
	 * Filters the default components to activate for a new install.
	 *
	 * @param array $value Array of default components to activate.
	 *
	 * @since BuddyPress 1.7.0
	 */
	$default_components = apply_filters(
		'bp_new_install_default_components',
		array(
			'activity'      => 1,
			'members'       => 1,
			'settings'      => 1,
			'xprofile'      => 1,
			'notifications' => 1,
		)
	);

	$get_default_forum = bp_get_option( 'bbp_set_forum_to_default', '' );
	if ( 'yes' === $get_default_forum ) {
		$default_components['forums'] = 1;
	}

	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	require_once buddypress()->plugin_dir . '/bp-core/admin/bp-core-admin-schema.php';
	$switched_to_root_blog = false;

	// Make sure the current blog is set to the root blog.
	if ( ! bp_is_root_blog() ) {
		switch_to_blog( bp_get_root_blog_id() );
		bp_register_taxonomies();

		$switched_to_root_blog = true;
	}

	// Install BP schema and activate only Activity and XProfile.
	if ( bp_is_install() ) {

		do_action( 'bb_core_before_install', $default_components );

		// Apply schema and set Activity and XProfile components as active.
		bp_core_install( $default_components );
		bp_update_option( 'bp-active-components', $default_components );
		bp_core_add_page_mappings( $default_components, 'delete' );
		bp_core_install_emails();
		bp_core_install_invitations();

		do_action( 'bb_core_after_install', $default_components );

		// Upgrades.
	} else {

		// Run the schema install to update tables.
		bp_core_install();

		// Version 1.5.0.
		if ( $raw_db_version < 1801 ) {
			bp_update_to_1_5();
			bp_core_add_page_mappings( $default_components, 'delete' );
		}

		// Version 1.6.0.
		if ( $raw_db_version < 6067 ) {
			bp_update_to_1_6();
		}

		// Version 1.9.0.
		if ( $raw_db_version < 7553 ) {
			bp_update_to_1_9();
		}

		// Version 1.9.2.
		if ( $raw_db_version < 7731 ) {
			bp_update_to_1_9_2();
		}

		// Version 2.0.0.
		if ( $raw_db_version < 7892 ) {
			bp_update_to_2_0();
		}

		// Version 2.0.1.
		if ( $raw_db_version < 8311 ) {
			bp_update_to_2_0_1();
		}

		// Version 2.2.0.
		if ( $raw_db_version < 9181 ) {
			bp_update_to_2_2();
		}

		// Version 2.3.0.
		if ( $raw_db_version < 9615 ) {
			bp_update_to_2_3();
		}

		// Version 2.5.0.
		if ( $raw_db_version < 10440 ) {
			bp_update_to_2_5();
		}

		// Version 2.7.0.
		if ( $raw_db_version < 11105 ) {
			bp_update_to_2_7();
		}

		// Version 3.1.1.
		if ( $raw_db_version < 13731 ) {
			bp_update_to_3_1_1();
		}

		// Version 3.1.1.
		if ( $raw_db_version < 14001 ) {
			bb_update_to_1_2_3();
		}

		// Version 3.1.1.
		if ( $raw_db_version < 14801 ) {
			bp_update_to_1_2_4();
		}

		if ( $raw_db_version < 14901 ) {
			bp_update_to_1_2_9();
		}

		if ( $raw_db_version < 15200 ) {
			bp_update_to_1_3_0();
		}

		// Version 1.3.5.
		if ( $raw_db_version < 15601 ) {
			bb_update_to_1_3_5();
		}

		// Version 1.4.0.
		if ( $raw_db_version < 15800 ) {
			bp_update_to_1_4_0();
		}

		if ( $raw_db_version < 16000 ) {
			bp_update_to_1_4_3();
		}

		if ( $raw_db_version < 16201 ) {
			bp_update_to_1_5_1();
		}

		if ( $raw_db_version < 16301 ) {
			bp_update_to_1_5_5();
		}

		if ( $raw_db_version < 16401 ) {
			bb_update_to_1_5_6();
		}

		// Version 1.7.0.
		if ( $raw_db_version < 16601 ) {
			bp_update_to_1_7_0();
		}

		// Version 1.7.2.
		if ( $raw_db_version < 16901 ) {
			bb_update_to_1_7_2();
		}

		if ( $raw_db_version < 17401 ) {
			bb_update_to_1_7_5();
		}

		if ( $raw_db_version < 17701 ) {
			bb_update_to_1_7_7();
		}

		if ( $raw_db_version < 17901 ) {
			bb_update_to_1_7_8();
		}

		if ( $raw_db_version < 17951 ) {
			bb_update_to_1_8_1();
		}

		if ( $raw_db_version < 18401 ) {
			bb_update_to_1_8_6();
		}

		if ( $raw_db_version < 18501 ) {
			bb_update_to_1_9_0_1();
		}

		if ( $raw_db_version < 18651 ) {
			bb_update_to_1_9_1();
		}

		if ( $raw_db_version < 18701 ) {
			bb_update_to_1_9_3();
		}

		if ( $raw_db_version < 18855 ) {
			bb_update_to_2_1_1();
		}

		if ( $raw_db_version < 18951 ) {
			bb_update_to_2_1_4();
		}

		if ( $raw_db_version < 18981 ) {
			bb_update_to_2_1_5();
		}

		if ( $raw_db_version < 19081 ) {
			bb_update_to_2_2_3();
		}

		if ( $raw_db_version < 19181 ) {
			bb_update_to_2_2_4();
		}

		if ( $raw_db_version < 19281 ) {
			bb_update_to_2_2_5();
		}

		if ( $raw_db_version < 19381 ) {
			bb_update_to_2_2_6();
		}

		if ( $raw_db_version < 19481 ) {
			bb_update_to_2_2_7();
		}

		if ( $raw_db_version < 19551 ) {
			bb_update_to_2_2_8();
		}

		if ( $raw_db_version < 19571 ) {
			bb_update_to_2_2_9();
		}

		if ( $raw_db_version < 19971 ) {
			bb_update_to_2_3_0();
		}

		if ( $raw_db_version < 19991 ) {
			bb_update_to_2_3_1();
		}

		if ( $raw_db_version < 20001 ) {
			bb_update_to_2_3_3();
		}

		if ( $raw_db_version < 20101 ) {
			bb_update_to_2_3_4();
		}

		if ( $raw_db_version < 20111 ) {
			bb_update_to_2_3_41();
		}

		if ( $raw_db_version < 20211 ) {
			bb_update_to_2_3_50();
		}

		if ( $raw_db_version < 20261 ) {
			bb_update_to_2_3_60();
		}

		if ( $raw_db_version < 20371 ) {
			bb_update_to_2_3_80();
		}

		if ( $raw_db_version < 20561 ) {
			bb_update_to_2_4_10();
		}

		if ( $raw_db_version !== $current_db ) {
			// @todo - Write only data manipulate migration here. ( This is not for DB structure change ).

			// Run migration about the moderation.
			if ( function_exists( 'bb_moderation_migration_on_update' ) ) {
				bb_moderation_migration_on_update();
			}

			if ( function_exists( 'bb_xprofile_update_social_network_fields' ) ) {
				bb_xprofile_update_social_network_fields();
			}

			// Create the table when class loaded.
			if ( class_exists( '\BuddyBoss\Performance\Performance' ) ) {
				\BuddyBoss\Performance\Performance::instance()->on_activation();
			}

			if ( function_exists( 'bb_messages_migration' ) ) {
				bb_messages_migration();
			}

			// Run migration about activity.
			if ( function_exists( 'bb_activity_migration' ) ) {
				bb_activity_migration();
			}
		}
	}

	/* All done! *************************************************************/

	if ( $switched_to_root_blog ) {
		restore_current_blog();
	}
}

/**
 * Perform database operations that must take place before the general schema upgrades.
 *
 * `dbDelta()` cannot handle certain operations - like changing indexes - so we do it here instead.
 *
 * @since BuddyPress 2.3.0
 */
function bp_pre_schema_upgrade() {
	global $wpdb;

	$raw_db_version = (int) bp_get_db_version_raw();
	$bp_prefix      = bp_core_get_table_prefix();

	// 2.3.0: Change index lengths to account for utf8mb4.
	if ( $raw_db_version < 9695 ) {
		// Map table_name => columns.
		$tables = array(
			$bp_prefix . 'bp_activity_meta'       => array( 'meta_key' ),
			$bp_prefix . 'bp_groups_groupmeta'    => array( 'meta_key' ),
			$bp_prefix . 'bp_messages_meta'       => array( 'meta_key' ),
			$bp_prefix . 'bp_notifications_meta'  => array( 'meta_key' ),
			$bp_prefix . 'bp_user_blogs_blogmeta' => array( 'meta_key' ),
			$bp_prefix . 'bp_xprofile_meta'       => array( 'meta_key' ),
		);

		foreach ( $tables as $table_name => $indexes ) {
			foreach ( $indexes as $index ) {
				if ( $wpdb->query( $wpdb->prepare( 'SHOW TABLES LIKE %s', bp_esc_like( $table_name ) ) ) ) {
					$wpdb->query( "ALTER TABLE {$table_name} DROP INDEX {$index}" );
				}
			}
		}
	}
}

/** Upgrade Routines **********************************************************/

/**
 * Remove unused metadata from database when upgrading from < 1.5.
 *
 * Database update methods based on version numbers.
 *
 * @since BuddyPress 1.7.0
 */
function bp_update_to_1_5() {

	// Delete old database version options.
	delete_site_option( 'bp-activity-db-version' );
	delete_site_option( 'bp-blogs-db-version' );
	delete_site_option( 'bp-friends-db-version' );
	delete_site_option( 'bp-groups-db-version' );
	delete_site_option( 'bp-messages-db-version' );
	delete_site_option( 'bp-xprofile-db-version' );
}

/**
 * Remove unused metadata from database when upgrading from < 1.6.0.
 *
 * Database update methods based on version numbers.
 *
 * @since BuddyPress 1.7.0
 */
function bp_update_to_1_6() {

	// Delete possible site options.
	delete_site_option( 'bp-db-version' );
	delete_site_option( '_bp_db_version' );
	delete_site_option( 'bp-core-db-version' );
	delete_site_option( '_bp-core-db-version' );

	// Delete possible blog options.
	delete_blog_option( bp_get_root_blog_id(), 'bp-db-version' );
	delete_blog_option( bp_get_root_blog_id(), 'bp-core-db-version' );
	delete_site_option( bp_get_root_blog_id(), '_bp-core-db-version' );
	delete_site_option( bp_get_root_blog_id(), '_bp_db_version' );
}

/**
 * Add the notifications component to active components.
 *
 * Notifications was added in 1.9.0, and previous installations will already
 * have the core notifications API active. We need to add the new Notifications
 * component to the active components option to retain existing functionality.
 *
 * @since BuddyPress 1.9.0
 */
function bp_update_to_1_9() {

	// Setup hardcoded keys.
	$active_components_key      = 'bp-active-components';
	$notifications_component_id = 'notifications';

	// Get the active components.
	$active_components = bp_get_option( $active_components_key );

	// Add notifications.
	if ( ! in_array( $notifications_component_id, $active_components ) ) {
		$active_components[ $notifications_component_id ] = 1;
	}

	// Update the active components option.
	bp_update_option( $active_components_key, $active_components );
}

/**
 * Perform database updates for BP 1.9.2.
 *
 * In 1.9, BuddyPress stopped registering its theme directory when it detected
 * that bp-default (or a child theme) was not currently being used, in effect
 * deprecating bp-default. However, this ended up causing problems when site
 * admins using bp-default would switch away from the theme temporarily:
 * bp-default would no longer be available, with no obvious way (outside of
 * a manual filter) to restore it. In 1.9.2, we add an option that flags
 * whether bp-default or a child theme is active at the time of upgrade; if so,
 *
 * the theme directory will continue to be registered even if the theme is
 * deactivated temporarily. Thus, new installations will not see bp-default,
 * but legacy installations using the theme will continue to see it.
 *
 * @since BuddyPress 1.9.2
 */
function bp_update_to_1_9_2() {
	if ( 'bp-default' === get_stylesheet() || 'bp-default' === get_template() ) {
		update_site_option( '_bp_retain_bp_default', 1 );
	}
}

/**
 * 2.0 update routine.
 *
 * - Ensure that the activity tables are installed, for last_activity storage.
 * - Migrate last_activity data from usermeta to activity table.
 * - Add values for all BuddyPress options to the options table.
 *
 * @since BuddyPress 2.0.0
 */
function bp_update_to_2_0() {

	/* Install activity tables for 'last_activity' ***************************/

	bp_core_install_activity_streams();

	/* Migrate 'last_activity' data ******************************************/

	bp_last_activity_migrate();

	/* Migrate signups data **************************************************/

	if ( ! is_multisite() ) {

		// Maybe install the signups table.
		bp_core_maybe_install_signups();

		// Run the migration script.
		bp_members_migrate_signups();
	}

	/* Add BP options to the options table ***********************************/

	bp_add_options();
}

/**
 * 2.0.1 database upgrade routine.
 *
 * @since BuddyPress 2.0.1
 */
function bp_update_to_2_0_1() {

	// We purposely call this during both the 2.0 upgrade and the 2.0.1 upgrade.
	// Don't worry; it won't break anything, and safely handles all cases.
	bp_core_maybe_install_signups();
}

/**
 * 2.2.0 update routine.
 *
 * - Add messages meta table.
 * - Update the component field of the 'new members' activity type.
 * - Clean up hidden friendship activities.
 *
 * @since BuddyPress 2.2.0
 */
function bp_update_to_2_2() {

	// Also handled by `bp_core_install()`.
	if ( bp_is_active( 'messages' ) ) {
		bp_core_install_private_messaging();
	}

	if ( bp_is_active( 'activity' ) ) {
		bp_migrate_new_member_activity_component();

		if ( bp_is_active( 'friends' ) ) {
			bp_cleanup_friendship_activities();
		}
	}
}

/**
 * 2.3.0 update routine.
 *
 * - Add notifications meta table.
 *
 * @since BuddyPress 2.3.0
 */
function bp_update_to_2_3() {

	// Also handled by `bp_core_install()`.
	if ( bp_is_active( 'notifications' ) ) {
		bp_core_install_notifications();
	}
}

/**
 * 2.5.0 update routine.
 *
 * - Add emails.
 *
 * @since BuddyPress 2.5.0
 */
function bp_update_to_2_5() {
	bp_core_install_emails();
}

/**
 * 2.7.0 update routine.
 *
 * - Add email unsubscribe salt.
 * - Save legacy directory titles to the corresponding WP pages.
 * - Add ignore deprecated code option (false for updates).
 *
 * @since BuddyPress 2.7.0
 */
function bp_update_to_2_7() {
	bp_add_option( 'bp-emails-unsubscribe-salt', base64_encode( wp_generate_password( 64, true, true ) ) );

	// Update post_titles.
	bp_migrate_directory_page_titles();

	/*
	 * Add `parent_id` column to groups table.
	 * Also handled by `bp_core_install()`.
	 */
	if ( bp_is_active( 'groups' ) ) {
		bp_core_install_groups();

		// Invalidate all cached group objects.
		global $wpdb;
		$bp = buddypress();

		$group_ids = $wpdb->get_col( "SELECT id FROM {$bp->groups->table_name}" );

		foreach ( $group_ids as $group_id ) {
			wp_cache_delete( $group_id, 'bp_groups' );
		}
	}

	// Do not ignore deprecated code for existing installs.
	bp_add_option( '_bp_ignore_deprecated_code', false );
}

/**
 * 3.1.1 update routine.
 *
 * - Add follow table.
 *
 * @since BuddyBoss 1.0.0
 */
function bp_update_to_3_1_1() {

	bp_core_install_activity_streams();
	bp_core_install_follow();
	bp_core_install_default_profiles_fields();
	bp_core_install_bbp_emails();
	bp_core_install_invites_email();
	bp_core_update_activity_favorites();
	bp_core_fix_media_activities();
	bp_core_install_media();
}

/**
 * 1.2.3 update routine.
 *
 * - Add follow table.
 *
 * @since BuddyBoss 1.2.3
 */
function bp_update_to_1_2_4() {
	bp_core_install_media();
}

function bp_update_to_1_4_0() {
	bp_core_install_document();
}

/**
 * Fix forums media showing in users profile media tab.
 *
 * @since BuddyBoss 1.4.3
 */
function bp_update_to_1_4_3() {
	global $wpdb;
	$bp      = buddypress();
	$squery  = "SELECT GROUP_CONCAT( pm.meta_value ) as media_id FROM {$wpdb->posts} p, {$wpdb->postmeta} pm WHERE p.ID = pm.post_id and p.post_type in ( 'forum', 'topic', 'reply' ) and pm.meta_key = 'bp_media_ids' and pm.meta_value != ''";
	$records = $wpdb->get_col( $squery );
	if ( ! empty( $records ) && bp_is_active( 'media' ) ) {
		$records = reset( $records );
		if ( ! empty( $records ) ) {
			$update_query = "UPDATE {$bp->media->table_name} SET `privacy`= 'forums' WHERE id in (" . $records . ')';
			$wpdb->query( $update_query );
		}
	}
}

/**
 * Fix forums media showing in users profile media tab.
 *
 * @since BuddyBoss 1.5.1
 */
function bp_update_to_1_5_1() {
	if ( bp_is_active( 'xprofile' ) ) {
		$nickname_field_id = bp_xprofile_nickname_field_id();
		bp_xprofile_update_field_meta( $nickname_field_id, 'default_visibility', 'public' );
		bp_xprofile_update_field_meta( $nickname_field_id, 'allow_custom_visibility', 'disabled' );
	}
}

/**
 * Update media table for the video components.
 *
 * @since BuddyBoss 1.7.0
 */
function bp_update_to_1_7_0() {
	bp_core_install_media();
	bp_update_option( 'bp_media_symlink_support', 1 );
}

/**
 * Flush rewrite rule after update.
 *
 * @since BuddyBoss 1.7.2
 */
function bb_update_to_1_7_2() {
	flush_rewrite_rules();
	bb_update_to_1_7_2_activity_setting_feed_comments_migration();
}

/**
 * Function to update data
 *
 * @since BuddyBoss 1.7.5
 */
function bb_update_to_1_7_5() {
	global $bp_background_updater;

	$bp_background_updater->push_to_queue(
		array(
			'callback' => 'bb_moderation_bg_update_moderation_data',
			'args'     => array(),
		)
	);
	$bp_background_updater->save()->schedule_event();

}

/**
 * Function to update data
 * - Updated .htaccess file for bb files protection.
 *
 * @since BuddyBoss 1.7.7
 */
function bb_update_to_1_7_7() {
	$upload_dir        = wp_get_upload_dir();
	$media_htaccess    = $upload_dir['basedir'] . '/bb_medias/.htaccess';
	$document_htaccess = $upload_dir['basedir'] . '/bb_documents/.htaccess';
	$video_htaccess    = $upload_dir['basedir'] . '/bb_videos/.htaccess';

	if ( ! class_exists( '\WP_Filesystem_Direct' ) ) {
		require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-base.php';
		require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-direct.php';
	}

	$wp_files_system = new \WP_Filesystem_Direct( array() );

	if ( file_exists( $media_htaccess ) ) {
		$wp_files_system->delete( $media_htaccess, false, 'f' );
	}

	if ( file_exists( $document_htaccess ) ) {
		$wp_files_system->delete( $document_htaccess, false, 'f' );
	}

	if ( file_exists( $video_htaccess ) ) {
		$wp_files_system->delete( $video_htaccess, false, 'f' );
	}
}

function bp_update_default_doc_extensions() {

	$get_extensions = bp_get_option( 'bp_document_extensions_support', array() );

	// $changed_array = array(
	// 'bb_doc_52'   => array(
	// 'description' => '7z Archive XYZ',
	// )
	// );
	//
	//
	// if ( !empty( $changed_array ) ) {
	// foreach ( $changed_array as $k => $v ) {
	// if ( array_key_exists( $k, $get_extensions ) ) {
	// $extension = $get_extensions[$k];
	// $get_extensions[$k] = array_replace( $extension, $v );
	// } else {
	// For newly add key.
	// $get_extensions[$k] = $v;
	// }
	// }
	// }
	//
	// $removed_array = array(
	// 'bb_doc_51'
	// );
	//
	// if ( !empty( $removed_array ) ) {
	// foreach (  $removed_array as $key ) {
	// unset( $get_extensions[$key] );
	// }
	//
	// }

	// bp_update_option( 'bp_document_extensions_support', $get_extensions );
}


/**
 * 1.2.3 update routine.
 *
 * @since BuddyBoss 1.2.3
 */
function bb_update_to_1_2_3() {
	bp_add_option( '_bp_ignore_deprecated_code', false );

	// Fix current forums media privacy to 'forums'.
	bp_core_fix_forums_media();
}

/**
 * Updates the component field for new_members type.
 *
 * @since BuddyPress 2.2.0
 *
 * @global wpdb $wpdb WordPress database abstraction object.
 */
function bp_migrate_new_member_activity_component() {
	global $wpdb;
	$bp = buddypress();

	// Update the component for the new_member type.
	$wpdb->update(
		// Activity table.
		$bp->members->table_name_last_activity,
		array(
			'component' => $bp->members->id,
		),
		array(
			'component' => 'xprofile',
			'type'      => 'new_member',
		),
		// Data sanitization format.
		array(
			'%s',
		),
		// WHERE sanitization format.
		array(
			'%s',
			'%s',
		)
	);
}

/**
 * Remove all hidden friendship activities.
 *
 * @since BuddyPress 2.2.0
 */
function bp_cleanup_friendship_activities() {
	bp_activity_delete(
		array(
			'component'     => buddypress()->friends->id,
			'type'          => 'friendship_created',
			'hide_sitewide' => true,
		)
	);
}

/**
 * Update WP pages so that their post_title matches the legacy component directory title.
 *
 * As of 2.7.0, component directory titles come from the `post_title` attribute of the corresponding WP post object,
 * instead of being hardcoded. To ensure that directory titles don't change for existing installations, we update these
 * WP posts with the formerly hardcoded titles.
 *
 * @since BuddyPress 2.7.0
 */
function bp_migrate_directory_page_titles() {
	$bp_pages = bp_core_get_directory_page_ids( 'all' );

	$default_titles = bp_core_get_directory_page_default_titles();

	$legacy_titles = array(
		'activity' => __( 'Site-Wide Activity', 'buddyboss' ),
		'blogs'    => __( 'Sites', 'buddyboss' ),
		'groups'   => __( 'Groups', 'buddyboss' ),
		'members'  => __( 'Members', 'buddyboss' ),
	);

	foreach ( $bp_pages as $component => $page_id ) {
		if ( ! isset( $legacy_titles[ $component ] ) ) {
			continue;
		}

		$page = get_post( $page_id );
		if ( ! $page ) {
			continue;
		}

		// If the admin has changed the default title, don't touch it.
		if ( isset( $default_titles[ $component ] ) && $default_titles[ $component ] !== $page->post_title ) {
			continue;
		}

		// If the saved page title is the same as the legacy title, there's nothing to do.
		if ( $legacy_titles[ $component ] == $page->post_title ) {
			continue;
		}

		// Update the page with the legacy title.
		wp_update_post(
			array(
				'ID'         => $page_id,
				'post_title' => $legacy_titles[ $component ],
			)
		);
	}
}

/**
 * Redirect user to BP's What's New page on first page load after activation.
 *
 * @since BuddyPress 1.7.0
 *
 * @internal Used internally to redirect BuddyPress to the about page on activation.
 */
function bp_add_activation_redirect() {

	// Bail if activating from network, or bulk.
	if ( isset( $_GET['activate-multi'] ) ) {
		return;
	}

	// Install the API cache table on plugin activation if mu file was found.
	if ( class_exists( '\BuddyBoss\Performance\Performance' ) ) {
		\BuddyBoss\Performance\Performance::instance()->on_activation();
	}

	// Record that this is a new installation, so we show the right
	// welcome message.
	if ( bp_is_install() ) {
		set_transient( '_bp_is_new_install', true, 30 );
	}

	// Check in Current DB having a below 2 options are saved for the BBPress Topic & Topic Reply previously.
	$topic_slug         = get_option( '_bbp_topic_slug' );
	$topic_tag_slug     = get_option( '_bbp_topic_tag_slug' );
	$topic_archive_slug = get_option( '_bbp_topic_archive_slug' );

	if ( empty( $topic_slug ) ) {

		// Check if there is any topics their in DB.
		$topics = get_posts(
			array(
				'post_type'              => 'topic',
				'numberposts'            => 1,
				'suppress_filters'       => false,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
			)
		);

		// If found the topics then go ahead.
		if ( ! empty( $topics ) ) {

			// Topics found so set the _bbp_topic_slug to "topic" instead of "discussion" otherwise it will create the issue who used previously BBPress.
			update_option( '_bbp_topic_slug', 'topic' );

			// Set Flag to enable Forums default.
			update_option( 'bbp_set_forum_to_default', 'yes' );

			// Get current active component.
			$bp_active_components = get_option( 'bp-active-components', array() );

			if ( ! in_array( 'forums', $bp_active_components ) ) {

				// Add Forums to current components.
				$bp_active_components['forums'] = 1;

				// Set the forums to in DB.
				bp_update_option( 'bp-active-components', $bp_active_components );

			}

			// If Forums page is not set.
			$option = bp_get_option( '_bbp_root_slug_custom_slug', '' );
			if ( '' === $option ) {

				$default_title = bp_core_get_directory_page_default_titles();
				$title         = ( isset( $default_title['new_forums_page'] ) ) ? $default_title['new_forums_page'] : '';

				$new_page = array(
					'post_title'     => $title,
					'post_status'    => 'publish',
					'post_author'    => bp_loggedin_user_id(),
					'post_type'      => 'page',
					'comment_status' => 'closed',
					'ping_status'    => 'closed',
				);

				// Create Forums Page.
				$page_id = wp_insert_post( $new_page );

				bp_update_option( '_bbp_root_slug_custom_slug', $page_id );
				$slug = get_page_uri( $page_id );

				// Set BBPress root Slug.
				bp_update_option( '_bbp_root_slug', urldecode( $slug ) );

			}

			// Reset the permalink.
			bp_update_option( 'rewrite_rules', '' );

		}

		if ( empty( $topic_archive_slug ) ) {
			update_option( '_bbp_topic_archive_slug', 'topics' );
		}

		if ( empty( $topic_tag_slug ) ) {
			// Tags found so set the _bbp_topic_tag_slug to "topic-reply" instead of "discussion-reply" otherwise it will create the issue who used previously BBPress.
			update_option( '_bbp_topic_tag_slug', 'topic-tag' );

		}
	}

	// Add the transient to redirect.
	set_transient( '_bp_activation_redirect', true, 30 );
}

/**
 * Platform plugin updater
 *
 * @since BuddyBoss 1.0.0
 */
function bp_platform_plugin_updater() {
	if ( ! class_exists( 'BB_Platform_Pro' ) && class_exists( 'BP_BuddyBoss_Platform_Updater' ) ) {
		new BP_BuddyBoss_Platform_Updater( 'https://update.buddyboss.com/plugin', basename( BP_PLUGIN_DIR ) . '/bp-loader.php', 847 );
	}
}

/** Signups *******************************************************************/

/**
 * Check if the signups table needs to be created or upgraded.
 *
 * @since BuddyPress 2.0.0
 *
 * @global wpdb $wpdb WordPress database abstraction object.
 */
function bp_core_maybe_install_signups() {
	global $wpdb;

	// The table to run queries against.
	$signups_table = $wpdb->base_prefix . 'signups';

	// Suppress errors because users shouldn't see what happens next.
	$old_suppress = $wpdb->suppress_errors();

	// Never use bp_core_get_table_prefix() for any global users tables.
	$table_exists = (bool) $wpdb->get_results( "DESCRIBE {$signups_table};" );

	// Table already exists, so maybe upgrade instead?
	if ( true === $table_exists ) {

		// Look for the 'signup_id' column.
		$column_exists = $wpdb->query( "SHOW COLUMNS FROM {$signups_table} LIKE 'signup_id'" );

		// 'signup_id' column doesn't exist, so run the upgrade
		if ( empty( $column_exists ) ) {
			bp_core_upgrade_signups();
		}

		// Table does not exist, and we are a single site, so install the multisite
		// signups table using WordPress core's database schema.
	} elseif ( ! is_multisite() ) {
		bp_core_install_signups();
	}

	// Restore previous error suppression setting.
	$wpdb->suppress_errors( $old_suppress );
}

/** Activation Actions ********************************************************/

/**
 * Fire activation hooks and events.
 *
 * Runs on BuddyPress activation.
 *
 * @since BuddyPress 1.6.0
 */
function bp_activation() {

	// Force refresh theme roots.
	delete_site_transient( 'theme_roots' );

	// Add options.
	bp_add_options();

	/**
	 * Fires during the activation of BuddyPress.
	 *
	 * Use as of 1.6.0.
	 *
	 * @since BuddyPress 1.6.0
	 */
	do_action( 'bp_activation' );

	// @deprecated as of 1.6.0
	do_action( 'bp_loader_activate' );
}

/**
 * Fire deactivation hooks and events.
 *
 * Runs on BuddyPress deactivation.
 *
 * @since BuddyPress 1.6.0
 */
function bp_deactivation() {

	// Force refresh theme roots.
	delete_site_transient( 'theme_roots' );

	// Switch to WordPress's default theme if current parent or child theme
	// depend on bp-default. This is to prevent white screens of doom.
	if ( in_array( 'bp-default', array( get_template(), get_stylesheet() ) ) ) {
		switch_theme( WP_DEFAULT_THEME, WP_DEFAULT_THEME );
		update_option( 'template_root', get_raw_theme_root( WP_DEFAULT_THEME, true ) );
		update_option( 'stylesheet_root', get_raw_theme_root( WP_DEFAULT_THEME, true ) );
	}

	/**
	 * Fires during the deactivation of BuddyPress.
	 *
	 * Use as of 1.6.0.
	 *
	 * @since BuddyPress 1.6.0
	 */
	do_action( 'bp_deactivation' );

	// @deprecated as of 1.6.0
	do_action( 'bp_loader_deactivate' );
}

/**
 * Fire uninstall hook.
 *
 * Runs when uninstalling BuddyPress.
 *
 * @since BuddyPress 1.6.0
 */
function bp_uninstall() {

	/**
	 * Fires during the uninstallation of BuddyPress.
	 *
	 * @since BuddyPress 1.6.0
	 */
	do_action( 'bp_uninstall' );
}

/**
 * Update activity favorites data
 *
 * @since BuddyBoss 1.0.0
 */
function bp_core_update_activity_favorites() {
	if ( bp_is_active( 'activity' ) ) {
		bp_activity_favorites_upgrade_data();
	}
}

/**
 * Fix media activities
 *
 * @since BuddyBoss 1.0.0
 */
function bp_core_fix_media_activities() {
	if ( bp_is_active( 'activity' ) ) {
		bp_activity_media_fix_data();
	}
}

/**
 * 1.2.8 update routine.
 *
 * @since BuddyBoss 1.2.9
 */
function bp_update_to_1_2_9() {
	bp_core_install_group_message_email();
}

/**
 * Fix forums media
 *
 * @since BuddyBoss 1.2.3
 */
function bp_core_fix_forums_media() {
	if ( bp_is_active( 'forums' ) && bp_is_active( 'media' ) ) {
		bbp_fix_forums_media();
	}
}

function bp_update_to_1_3_0() {
	bp_core_install_private_messaging();
}

/**
 * 1.3.5 update routine.
 *
 * - Create the invitations table.
 * - Migrate requests and invitations to the new table.
 */
function bb_update_to_1_3_5() {
	bp_core_install_invitations();

	if ( bp_is_active( 'groups' ) ) {
		bp_groups_migrate_invitations();
	}
}

/**
 * Fix message media showing in group photos tab.
 *
 * @since BuddyBoss 1.5.5
 */
function bp_update_to_1_5_5() {

	global $wpdb;
	$bp = buddypress();

	// Reset the message media to group_id to 0, activity_id to 0, album_id to 0 as it's never associated with the groups, activity and album.
	$wpdb->query( $wpdb->prepare( "UPDATE {$bp->media->table_name} SET `group_id`= 0, `activity_id`= 0, `album_id`= 0 WHERE privacy = %s and ( group_id > 0 OR activity_id > 0 OR album_id > 0 )", 'message' ) );
}

/**
 * Create Moderation emails.
 *
 * @since BuddyBoss 1.5.6
 */
function bb_update_to_1_5_6() {
	bp_core_install_moderation_emails();
}

/**
 * Add new video support to document extensions.
 *
 * @since BuddyBoss 1.7.0
 */
add_filter(
	'bp_media_allowed_document_type',
	function ( $extensions ) {
		$changed_array = array(
			'bb_doc_77' => array(
				'extension'   => '.mp4',
				'mime_type'   => 'video/mp4',
				'description' => __( 'MP4', 'buddyboss' ),
				'is_default'  => 1,
				'is_active'   => 1,
				'icon'        => '',
			),
			'bb_doc_78' => array(
				'extension'   => '.webm',
				'mime_type'   => 'video/webm',
				'description' => __( 'WebM', 'buddyboss' ),
				'is_default'  => 1,
				'is_active'   => 1,
				'icon'        => '',
			),
			'bb_doc_79' => array(
				'extension'   => '.ogg',
				'mime_type'   => 'video/ogg',
				'description' => __( 'Ogg', 'buddyboss' ),
				'is_default'  => 1,
				'is_active'   => 1,
				'icon'        => '',
			),
			'bb_doc_80' => array(
				'extension'   => '.mov',
				'mime_type'   => 'video/quicktime',
				'description' => __( 'Quicktime', 'buddyboss' ),
				'is_default'  => 1,
				'is_active'   => 1,
				'icon'        => '',
			),
		);

		$extensions = array_merge( $extensions, $changed_array );

		return $extensions;
	}
);

/**
 * Migration for activity setting feed comments.
 * Enable all custom post type comments when the default post type comments are enable.
 *
 * @since BuddyBoss 1.7.2
 *
 * @uses bb_feed_post_types()                    Get all post types.
 * @uses bb_post_type_feed_option_name()         Option key for individual post type.
 * @uses bb_post_type_feed_comment_option_name() Option key for individual post type comment.
 * @uses bp_is_post_type_feed_enable()           Checks if post type feed is enabled.
 *
 * @return void
 */
function bb_update_to_1_7_2_activity_setting_feed_comments_migration() {
	$custom_post_types = bb_feed_post_types();

	// Run over all custom post type.
	foreach ( $custom_post_types as $post_type ) {
		// Post type option name.
		$pt_opt_name = bb_post_type_feed_option_name( $post_type );

		// Post type comment option name.
		$ptc_opt_name = bb_post_type_feed_comment_option_name( $post_type );

		if ( bp_is_post_type_feed_enable( $post_type ) ) {
			bp_update_option( $ptc_opt_name, 1 );
		}
	}
}

/**
 * 1.7.8 update routine.
 * Update forum meta with its associated group id.
 *
 * @since BuddyBoss 1.7.8
 *
 * @return void
 */
function bb_update_to_1_7_8() {
	// Return, when group or forum component deactive.
	if ( ! bp_is_active( 'groups' ) || ! bp_is_active( 'forums' ) ) {
		return;
	}

	// Get all forum associated groups.
	$group_data = groups_get_groups(
		array(
			'per_page'   => -1,
			'fields'     => 'ids',
			'status'     => array( 'public', 'private', 'hidden' ),
			'meta_query' => array(
				'relation' => 'AND',
				array(
					'key'     => 'forum_id',
					'value'   => 'a:0:{}',
					'compare' => '!=',
				),
				array(
					'key'     => 'forum_id',
					'value'   => '',
					'compare' => '!=',
				),
			),
		)
	);

	$groups = empty( $group_data['groups'] ) ? array() : $group_data['groups'];

	if ( ! empty( $groups ) ) {
		foreach ( $groups as $group_id ) {
			$forum_ids = groups_get_groupmeta( $group_id, 'forum_id' );

			if ( empty( $forum_ids ) ) {
				continue;
			}

			// Group never contains multiple forums.
			$forum_id  = current( $forum_ids );
			$group_ids = bbp_get_forum_group_ids( $forum_id );
			$group_ids = empty( $group_ids ) ? array() : $group_ids;

			if ( ! empty( $group_ids ) && in_array( $group_id, $group_ids, true ) ) {
				continue;
			}

			$group_ids[] = $group_id;

			bbp_update_forum_group_ids( $forum_id, $group_ids );
		}
	}
}

/**
 * update routine.
 * Created new table for bp email queue.
 *
 * @since BuddyBoss 1.8.1
 */
function bb_update_to_1_8_1() {
	if ( function_exists( 'bb_email_queue' ) ) {
		// Install email queue table.
		bb_email_queue()::create_db_table();
	}
}

/**
 * update routine.
 * Migrate default cover images from theme option.
 *
 * @since BuddyBoss 1.8.6
 */
function bb_update_to_1_8_6() {
	global $buddyboss_theme_options;

	// Do not ignore deprecated code for existing installs.
	bp_add_option( '_bp_ignore_deprecated_code', false );

	// Purge all the cache for API.
	if ( class_exists( 'BuddyBoss\Performance\Cache' ) ) {
		BuddyBoss\Performance\Cache::instance()->purge_all();
	}

	// Delete custom css transient.
	delete_transient( 'buddyboss_theme_compressed_elementor_custom_css' );

	/* Check if options are empty */
	if ( empty( $buddyboss_theme_options ) ) {
		$buddyboss_theme_options = get_option( 'buddyboss_theme_options', array() );
	}

	$reset_files = $_FILES;
	$reset_post  = $_POST;

	// Set Profile Avatar.
	$show_profile_avatar = bp_get_option( 'show_avatars' );
	$default_avatar      = bp_get_option( 'avatar_default', 'mystery' );

	if ( $show_profile_avatar && 'mystery' === $default_avatar ) {
		bp_update_option( 'bp-profile-avatar-type', 'BuddyBoss' );
		bp_update_option( 'bp-default-profile-avatar-type', 'buddyboss' );
	} else {
		bp_update_option( 'bp-profile-avatar-type', 'WordPress' );
	}

	// Set Group Avatar.
	bp_update_option( 'bp-default-group-avatar-type', 'buddyboss' );

	// Profile Cover.
	bp_update_option( 'bp-default-profile-cover-type', 'buddyboss' );

	if ( ! bp_disable_cover_image_uploads() && function_exists( 'buddyboss_theme_get_option' ) && class_exists( 'BP_Attachment_Cover_Image' ) ) {

		$temp_profile_cover = bb_to_1_8_6_upload_temp_cover_file( 'buddyboss_profile_cover_default' );

		if ( isset( $temp_profile_cover['filename'], $temp_profile_cover['path'], $temp_profile_cover['url'] ) && ! empty( $temp_profile_cover['filename'] ) && ! empty( $temp_profile_cover['path'] ) && ! empty( $temp_profile_cover['url'] ) ) {

			add_filter( 'bp_attachments_get_allowed_types', 'bb_to_1_8_6_allow_extension', 10, 2 );
			add_filter( 'bp_attachment_upload_overrides', 'bb_to_1_8_6_change_overrides' );
			add_filter( 'bp_attachments_cover_image_upload_dir', 'bb_to_1_8_6_image_upload_dir', 99 );

			// Upload the file.
			$cover_image_attachment                        = new BP_Attachment_Cover_Image();
			$_POST['action']                               = $cover_image_attachment->action;
			$_POST['profile_cover_upload']                 = true;
			$_FILES[ $cover_image_attachment->file_input ] = array(
				'tmp_name' => $temp_profile_cover['path'],
				'name'     => basename( $temp_profile_cover['path'] ),
				'type'     => wp_check_filetype( $temp_profile_cover['url'] )['type'],
				'error'    => 0,
				'size'     => filesize( $temp_profile_cover['path'] ),
			);

			// No error.
			$profile_cover_image = $cover_image_attachment->upload( $_FILES );

			remove_filter( 'bp_attachments_get_allowed_types', 'bb_to_1_8_6_allow_extension' );
			remove_filter( 'bp_attachment_upload_overrides', 'bb_to_1_8_6_change_overrides' );
			remove_filter( 'bp_attachments_cover_image_upload_dir', 'bb_to_1_8_6_image_upload_dir' );

			if ( ! empty( $profile_cover_image ) && isset( $profile_cover_image['url'] ) ) {
				bp_update_option( 'bp-default-profile-cover-type', 'custom' );
				bp_update_option( 'bp-default-custom-profile-cover', $profile_cover_image['url'] );

				if ( ! class_exists( '\WP_Filesystem_Direct' ) ) {
					require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-base.php';
					require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-direct.php';
				}
				$file_system_direct = new WP_Filesystem_Direct( false );
				$file_system_direct->rmdir( wp_upload_dir()['basedir'] . '/bb-cover', true );

				// Delete option after migration.
				bp_delete_option( 'buddyboss_profile_cover_default_migration' );

				if ( isset( $buddyboss_theme_options['buddyboss_profile_cover_default'] ) ) {
					add_option( 'bb_platform_profile_cover_default_migration', $buddyboss_theme_options['buddyboss_profile_cover_default'] );
					unset( $buddyboss_theme_options['buddyboss_profile_cover_default'] );
				}
			}

			// Reset POST and FILES request.
			$_FILES = $reset_files;
			$_POST  = $reset_post;
		} else {
			bp_update_option( 'bp-default-profile-cover-type', 'none' );
		}
	}

	// Group Cover.
	bp_update_option( 'bp-default-group-cover-type', 'buddyboss' );

	if ( ! bp_disable_group_cover_image_uploads() && function_exists( 'buddyboss_theme_get_option' ) && class_exists( 'BP_Attachment_Cover_Image' ) ) {

		$temp_group_cover = bb_to_1_8_6_upload_temp_cover_file( 'buddyboss_group_cover_default' );

		if ( isset( $temp_group_cover['filename'], $temp_group_cover['path'], $temp_group_cover['url'] ) && ! empty( $temp_group_cover['filename'] ) && ! empty( $temp_group_cover['path'] ) && ! empty( $temp_group_cover['url'] ) ) {

			add_filter( 'bp_attachments_get_allowed_types', 'bb_to_1_8_6_allow_extension', 10, 2 );
			add_filter( 'bp_attachment_upload_overrides', 'bb_to_1_8_6_change_overrides' );
			add_filter( 'bp_attachments_cover_image_upload_dir', 'bb_to_1_8_6_image_upload_dir', 99 );

			// Upload the file.
			$group_cover_image_attachment                        = new BP_Attachment_Cover_Image();
			$_POST['action']                                     = $group_cover_image_attachment->action;
			$_POST['group_cover_upload']                         = true;
			$_FILES[ $group_cover_image_attachment->file_input ] = array(
				'tmp_name' => $temp_group_cover['path'],
				'name'     => basename( $temp_group_cover['path'] ),
				'type'     => wp_check_filetype( $temp_group_cover['url'] )['type'],
				'error'    => 0,
				'size'     => filesize( $temp_group_cover['path'] ),
			);

			// No error.
			$group_cover_image = $group_cover_image_attachment->upload( $_FILES );

			remove_filter( 'bp_attachments_get_allowed_types', 'bb_to_1_8_6_allow_extension' );
			remove_filter( 'bp_attachment_upload_overrides', 'bb_to_1_8_6_change_overrides' );
			remove_filter( 'bp_attachments_cover_image_upload_dir', 'bb_to_1_8_6_image_upload_dir' );

			if ( ! empty( $group_cover_image ) && isset( $group_cover_image['url'] ) ) {
				bp_update_option( 'bp-default-group-cover-type', 'custom' );
				bp_update_option( 'bp-default-custom-group-cover', $group_cover_image['url'] );

				if ( ! class_exists( '\WP_Filesystem_Direct' ) ) {
					require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-base.php';
					require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-direct.php';
				}
				$file_system_direct = new WP_Filesystem_Direct( false );
				$file_system_direct->rmdir( wp_upload_dir()['basedir'] . '/bb-cover', true );

				// Delete option after migration.
				bp_delete_option( 'buddyboss_group_cover_default_migration' );

				if ( isset( $buddyboss_theme_options['buddyboss_group_cover_default'] ) ) {
					add_option( 'bb_platform_cover_default_migration', $buddyboss_theme_options['buddyboss_group_cover_default'] );
					unset( $buddyboss_theme_options['buddyboss_group_cover_default'] );
				}
			}

			// Reset POST and FILES request.
			$_FILES = $reset_files;
			$_POST  = $reset_post;
		} else {
			bp_update_option( 'bp-default-group-cover-type', 'none' );
		}
	}

	if ( ! empty( $buddyboss_theme_options ) ) {
		update_option( 'buddyboss_theme_options', $buddyboss_theme_options );
	}
}

/**
 * Upload default cover image to temp directory.
 *
 * @since BuddyBoss 1.8.6
 *
 * @param string $cover_type The option name of cover. 'buddyboss_profile_cover_default' or 'buddyboss_group_cover_default'.
 * @return array Array containing the path, URL, and file name.
 */
function bb_to_1_8_6_upload_temp_cover_file( $cover_type ) {
	$data = array(
		'filename' => '',
		'path'     => '',
		'url'      => '',
	);

	$default_cover_url = buddyboss_theme_get_option( $cover_type, 'url' );

	if ( empty( $default_cover_url ) ) {
		$bb_default_cover_url = bp_get_option( $cover_type . '_migration', array() );

		if ( ! empty( $bb_default_cover_url ) && isset( $bb_default_cover_url['url'] ) ) {
			$default_cover_url = $bb_default_cover_url['url'];
		}
	}

	if ( ! empty( $default_cover_url ) ) {

		$default_cover_path = str_replace( trailingslashit( get_site_url() ), ABSPATH, $default_cover_url );
		$upload_dir         = wp_upload_dir();
		$upload_dir         = $upload_dir['basedir'];

		// Create temp folder.
		$upload_dir = $upload_dir . '/bb-cover';

		// If folder not exists then create.
		if ( ! is_dir( $upload_dir ) ) {

			// Create temp folder.
			wp_mkdir_p( $upload_dir );
			chmod( $upload_dir, 0777 );
		}

		if ( is_dir( $upload_dir ) ) {
			$data['filename'] = basename( $default_cover_path );
			$data['path']     = trailingslashit( $upload_dir ) . $data['filename'];
			$data['url']      = str_replace( ABSPATH, trailingslashit( get_site_url() ), $data['path'] );

			if ( ! file_exists( $data['path'] ) ) {
				if ( copy( $default_cover_path, $data['path'] ) ) {
					// Return copied file information.
					return $data;
				}
			}
		}
	}

	return $data;
}

/**
 * Allow 'webp' extension to migrate cover images.
 *
 * @since BuddyBoss 1.8.6
 *
 * @param array  $exts List of allowed extensions.
 * @param string $type The requested file type.
 * @return array List of allowed extensions.
 */
function bb_to_1_8_6_allow_extension( $exts, $type ) {
	$exts[] = 'webp';
	return $exts;
}

/**
 * Disallow to check 'action' param when migrate cover images.
 *
 * @since BuddyBoss 1.8.6
 *
 * @param array $overrides The wp_handle_upload overrides.
 * @return array The wp_handle_upload overrides.
 */
function bb_to_1_8_6_change_overrides( $overrides ) {
	$overrides['test_form'] = false;
	return $overrides;
}

/**
 * Gets the upload dir array for cover photos.
 *
 * @since BuddyBoss 1.8.6
 *
 * @return array See wp_upload_dir().
 */
function bb_to_1_8_6_image_upload_dir( $args ) {
	// Set the subdir.
	$subdir = '/members/0/cover-image';
	if ( isset( $_POST['group_cover_upload'] ) ) {
		$subdir = '/groups/0/cover-image';
	}

	$upload_dir = bp_attachments_uploads_dir_get();

	return array(
		'path'    => $upload_dir['basedir'] . $subdir,
		'url'     => set_url_scheme( $upload_dir['baseurl'] ) . $subdir,
		'subdir'  => $subdir,
		'basedir' => $upload_dir['basedir'],
		'baseurl' => set_url_scheme( $upload_dir['baseurl'] ),
		'error'   => false,
	);
}

/**
 * Clear the scheduled cron job of symlink.
 *
 * @since BuddyBoss 1.9.0.1
 *
 * @return void
 */
function bb_update_to_1_9_0_1() {
	wp_clear_scheduled_hook( 'bb_bb_video_deleter_older_symlink_hook' );
	wp_clear_scheduled_hook( 'bb_bb_document_deleter_older_symlink_hook' );
	wp_clear_scheduled_hook( 'bb_bb_media_deleter_older_symlink_hook' );
}

/**
 * Update routine.
 * Migrate the cover sizes from the theme option.
 *
 * @since BuddyBoss 1.9.1
 */
function bb_update_to_1_9_1() {
	// Display plugin update notice.
	update_option( '_bb_is_update', true );

	// If enabled follow component then return follow as primary action.
	$primary_action = '';
	if ( ! function_exists( 'bb_platform_pro' ) && function_exists( 'bp_is_active' ) && bp_is_active( 'activity' ) && function_exists( 'bp_is_activity_follow_active' ) && bp_is_activity_follow_active() ) {
		$primary_action = 'follow';
	}
	bp_update_option( 'bb-member-profile-primary-action', $primary_action );

	if ( ! function_exists( 'buddyboss_theme' ) ) {
		return;
	}

	// Get BuddyBoss theme options.
	global $buddyboss_theme_options;

	// Get BuddyBoss theme version.
	$bb_theme_version = wp_get_theme()->get( 'Version' );

	// Check the theme already upto date or not.
	if ( function_exists( 'buddyboss_theme' ) && version_compare( $bb_theme_version, '1.8.7', '>=' ) ) {
		return;
	}

	// Check if options are empty.
	if ( empty( $buddyboss_theme_options ) ) {
		$buddyboss_theme_options = bp_get_option( 'buddyboss_theme_options', array() );
	}

	if ( ! empty( $buddyboss_theme_options ) ) {
		bp_update_option( 'old_buddyboss_theme_options_1_8_7', $buddyboss_theme_options );
	}

	$profile_cover_width  = $buddyboss_theme_options['buddyboss_profile_cover_width'] ?? bp_get_option( 'buddyboss_profile_cover_width' );
	$profile_cover_height = $buddyboss_theme_options['buddyboss_profile_cover_height'] ?? bp_get_option( 'buddyboss_profile_cover_height' );
	$group_cover_width    = $buddyboss_theme_options['buddyboss_group_cover_width'] ?? bp_get_option( 'buddyboss_group_cover_width' );
	$group_cover_height   = $buddyboss_theme_options['buddyboss_group_cover_height'] ?? bp_get_option( 'buddyboss_group_cover_height' );

	if ( ! empty( $profile_cover_width ) ) {
		bp_delete_option( 'bb-pro-cover-profile-width' );
		bp_add_option( 'bb-pro-cover-profile-width', $profile_cover_width );
	}

	if ( ! empty( $profile_cover_height ) ) {
		bp_delete_option( 'bb-pro-cover-profile-height' );
		bp_add_option( 'bb-pro-cover-profile-height', $profile_cover_height );
	}

	if ( ! empty( $group_cover_width ) ) {
		bp_delete_option( 'bb-pro-cover-group-width' );
		bp_add_option( 'bb-pro-cover-group-width', $group_cover_width );
	}

	if ( ! empty( $group_cover_height ) ) {
		bp_delete_option( 'bb-pro-cover-group-height' );
		bp_add_option( 'bb-pro-cover-group-height', $group_cover_height );
	}
}

/**
 * Update routine.
 *
 * @since BuddyBoss 1.9.3
 */
function bb_update_to_1_9_3() {

	// Update the email situation labels.
	bb_core_update_email_situation_labels();

	// Update the users settings.
	bb_core_update_user_settings();

	// Installed missing emails.
	bp_admin_install_emails();
}

/**
 * Update the email situation labels.
 *
 * @since BuddyBoss 1.9.3
 */
function bb_core_update_email_situation_labels() {

	$email_situation_labels = array(
		'activity-at-message'          => esc_html__( 'A member is mentioned in an activity post', 'buddyboss' ),
		'groups-at-message'            => esc_html__( 'A member is mentioned in a group activity post', 'buddyboss' ),
		'zoom-scheduled-meeting-email' => esc_html__( 'A Zoom meeting is scheduled in a group', 'buddyboss' ),
		'zoom-scheduled-webinar-email' => esc_html__( 'A Zoom webinar is scheduled in a group', 'buddyboss' ),
	);

	foreach ( $email_situation_labels as $situation_slug => $situation_label ) {
		$situation_term = get_term_by( 'slug', $situation_slug, bp_get_email_tax_type() );

		if ( ! empty( $situation_term ) && $situation_term->term_id ) {
			wp_update_term(
				(int) $situation_term->term_id,
				bp_get_email_tax_type(),
				array(
					'description' => $situation_label,
				)
			);
		}
	}

}

/**
 * Update the users settings.
 *
 * @since BuddyBoss 1.9.3
 */
function bb_core_update_user_settings() {
	global $bp_background_updater;

	$user_ids = get_users(
		array(
			'fields'     => 'ids',
			'meta_query' => array(
				array(
					'key'     => 'last_activity',
					'compare' => 'EXISTS',
				),
			),
		)
	);

	if ( empty( $user_ids ) ) {
		return;
	}

	foreach ( array_chunk( $user_ids, 200 ) as $chunk ) {
		$bp_background_updater->data(
			array(
				array(
					'callback' => 'migrate_notification_preferences',
					'args'     => array( $chunk ),
				),
			)
		);

		$bp_background_updater->save()->schedule_event();
	}
}

/**
 * Migrate notification preferences.
 *
 * @since BuddyBoss 1.9.3
 *
 * @param array $user_ids Array of user ids.
 *
 * @return void
 */
function migrate_notification_preferences( $user_ids ) {
	$all_keys = bb_preferences_key_maps();

	if ( empty( $user_ids ) || empty( $all_keys ) ) {
		return;
	}

	foreach ( $user_ids as $user_id ) {
		foreach ( $all_keys as $old_key => $new_key ) {
			$old_key = str_replace( array( '_0', '_1' ), '', $old_key );
			if ( metadata_exists( 'user', $user_id, $old_key ) ) {
				$old_val = get_user_meta( $user_id, $old_key, true );
				update_user_meta( $user_id, $new_key, $old_val );
			}
		}
	}
}

/**
 * Update moderation tables.
 *
 * @since BuddyBoss 2.1.1
 */
function bb_update_to_2_1_1() {
	bb_moderation_add_user_report_column();
}

/**
 * Function to add user report column in moderation for user report.
 *
 * @since BuddyBoss 2.1.1
 */
function bb_moderation_add_user_report_column() {

	global $wpdb;

	$row = $wpdb->get_results( "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name = '{$wpdb->base_prefix}bp_moderation' AND column_name = 'user_report'" ); //phpcs:ignore

	if ( empty( $row ) ) {
		$wpdb->query( "ALTER TABLE {$wpdb->base_prefix}bp_moderation ADD user_report TINYINT NULL DEFAULT '0'" ); //phpcs:ignore
	}

	$row = $wpdb->get_results( "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name = '{$wpdb->base_prefix}bp_suspend' AND column_name = 'user_report'" ); //phpcs:ignore

	if ( empty( $row ) ) {
		$wpdb->query( "ALTER TABLE {$wpdb->base_prefix}bp_suspend ADD user_report TINYINT NULL DEFAULT '0'" ); //phpcs:ignore
	}
}

/**
 * Migrate group member meta table, is_deleted column in messages table and migrate existing email templates.
 *
 * @since BuddyBoss 2.1.4
 */
function bb_update_to_2_1_4() {

	// Do not ignore deprecated code for existing installs.
	bp_update_option( '_bp_ignore_deprecated_code', false );

	// Create 'bp_groups_membermeta' table.
	if ( bp_is_active( 'groups' ) ) {
		bp_core_install_groups();
	}

	// Create 'bp_invitations_invitemeta' table.
	bp_core_install_invitations();

	// Add 'is_deleted' column in 'bp_messages_messages' table.
	bb_messages_add_is_deleted_column();

	// Migrate the 'bp_messages_deleted' value from 'wp_bp_messages_meta' table to 'is_deleted' column in 'bp_messages_messages' table.
	bb_messages_migrate_is_deleted_column();

	// For existing customer set default values for Messaging Notifications metabox.
	bb_set_default_value_for_messaging_notifications_metabox();

	// Update the messages email templates.
	bb_migrate_messages_email_templates();

}

/**
 * Add 'is_deleted' column to bp_messages table.
 *
 * @since BuddyBoss 2.1.4
 *
 * @return void
 */
function bb_messages_add_is_deleted_column() {
	global $wpdb;

	// Add 'is_deleted' column in 'bp_messages_messages' table.
	$row = $wpdb->get_results( "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name = '{$wpdb->base_prefix}bp_messages_messages' AND column_name = 'is_deleted'" ); //phpcs:ignore

	if ( empty( $row ) ) {
		$wpdb->query( "ALTER TABLE {$wpdb->base_prefix}bp_messages_messages ADD `is_deleted` TINYINT( 1 ) NOT NULL DEFAULT '0' AFTER `message`" ); //phpcs:ignore
	}
}

/**
 * Migrate message table for is_deleted column.
 *
 * @since BuddyBoss 2.1.4
 *
 * @return void
 */
function bb_messages_migrate_is_deleted_column() {
	global $wpdb;

	$table_name = $wpdb->base_prefix . 'bp_messages_messages';
	$meta_table = $wpdb->base_prefix . 'bp_messages_meta';

	$query = $wpdb->prepare(
		'SELECT DISTINCT `message_id` FROM `' . $meta_table . '` WHERE `meta_key` = %s',  // phpcs:ignore
		'bp_messages_deleted'
	);

	// phpcs:ignore
	$wpdb->query(
		$wpdb->prepare(
			'UPDATE  `' . $table_name . '` SET `is_deleted` = %s WHERE `id` IN ( ' . $query . ' )',  // phpcs:ignore
			'1'
		)
	);
}

/**
 * For existing customer set default values for Messaging Notifications metabox.
 *
 * @since BuddyBoss 2.1.4
 *
 * @return void
 */
function bb_set_default_value_for_messaging_notifications_metabox() {
	bp_update_option( 'delay_email_notification', 0 );
	bp_update_option( 'time_delay_email_notification', 15 );
}

/**
 * For existing customer update the messages email template.
 *
 * @since BuddyBoss 2.1.4
 *
 * @return void
 */
function bb_migrate_messages_email_templates() {
	$emails = get_posts(
		array(
			'post_status'            => 'publish',
			'post_type'              => bp_get_email_post_type(),
			'suppress_filters'       => false,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
			'tax_query'              => array(
				array(
					'taxonomy' => bp_get_email_tax_type(),
					'field'    => 'slug',
					'terms'    => array( 'group-message-email', 'messages-unread' ), // phpcs:ignore
				),
			),
		)
	);

	if ( $emails ) {
		foreach ( $emails as $email ) {

			// First verify the content if already have 3 brackets.
			$existing_token                      = array();
			$existing_token['{{{sender.name}}}'] = '{{sender.name}}';
			$existing_token['{{{group.name}}}']  = '{{group.name}}';

			// Replace tokens to existing content.
			$post_content = strtr( $email->post_content, $existing_token );
			$post_title   = strtr( $email->post_title, $existing_token );
			$post_excerpt = strtr( $email->post_excerpt, $existing_token );

			// Generate token to replace in existing email templates.
			$token                    = array();
			$token['{{sender.name}}'] = '{{{sender.name}}}';
			$token['{{group.name}}']  = '{{{group.name}}}';

			// Replace actual tokens to existing content.
			$post_content = strtr( $post_content, $token );
			$post_title   = strtr( $post_title, $token );
			$post_excerpt = strtr( $post_excerpt, $token );

			// Update the email template.
			wp_update_post(
				array(
					'ID'           => $email->ID,
					'post_title'   => $post_title,
					'post_content' => $post_content,
					'post_excerpt' => $post_excerpt,
				)
			);
		}
	}
}

/**
 * Clear api cache on the update.
 *
 * @since BuddyBoss 2.1.5
 *
 * @return void
 */
function bb_update_to_2_1_5() {
	// Purge all the cache for API.
	if ( class_exists( 'BuddyBoss\Performance\Cache' ) ) {
		BuddyBoss\Performance\Cache::instance()->purge_all();
	}
}

/**
 * Install email template for activity following post.
 *
 * @since BuddyBoss 2.2.3
 *
 * @return void
 */
function bb_update_to_2_2_3() {
	$defaults = array(
		'post_status' => 'publish',
		'post_type'   => bp_get_email_post_type(),
	);

	$email = array(
		/* translators: do not remove {} brackets or translate its contents. */
		'post_title'   => __( '[{{{site.name}}}] {{poster.name}} posted {{activity.type}}.', 'buddyboss' ),
		/* translators: do not remove {} brackets or translate its contents. */
		'post_content' => __( "<a href=\"{{{poster.url}}}\">{{poster.name}}</a> posted {{activity.type}}:\n\n{{{activity.content}}}", 'buddyboss' ),
		/* translators: do not remove {} brackets or translate its contents. */
		'post_excerpt' => __( "{{poster.name}} posted {{activity.type}}:\n\n{{{activity.content}}}\\n\nView the post: {{{activity.url}}}", 'buddyboss' ),
	);

	$id = 'new-activity-following';

	if (
		term_exists( $id, bp_get_email_tax_type() ) &&
		get_terms(
			array(
				'taxonomy' => bp_get_email_tax_type(),
				'slug'     => $id,
				'fields'   => 'count',
			)
		) > 0
	) {
		return;
	}

	$post_id = wp_insert_post( bp_parse_args( $email, $defaults, 'install_email_' . $id ) );
	if ( ! $post_id ) {
		return;
	}

	$tt_ids = wp_set_object_terms( $post_id, $id, bp_get_email_tax_type() );

	foreach ( $tt_ids as $tt_id ) {
		$term = get_term_by( 'term_taxonomy_id', (int) $tt_id, bp_get_email_tax_type() );
		wp_update_term(
			(int) $term->term_id,
			bp_get_email_tax_type(),
			array(
				'description' => esc_html__( 'New activity post by someone a member is following', 'buddyboss' ),
			)
		);
	}
}

/**
 * Clear web and api cache on the update.
 *
 * @since BuddyBoss 2.2.4
 *
 * @return void
 */
function bb_update_to_2_2_4() {
	wp_cache_flush();
	// Purge all the cache for API.
	if ( class_exists( 'BuddyBoss\Performance\Cache' ) ) {
		// Clear members API cache.
		BuddyBoss\Performance\Cache::instance()->purge_by_component( 'bp-members' );
	}
}

/**
 * Install email template for following.
 *
 * @since BuddyBoss 2.2.5
 *
 * @return void
 */
function bb_update_to_2_2_5() {
	wp_cache_flush();
	// Purge all the cache for API.
	if ( class_exists( 'BuddyBoss\Performance\Cache' ) ) {
		BuddyBoss\Performance\Cache::instance()->purge_by_component( 'post_comment' );
	}

	$defaults = array(
		'post_status' => 'publish',
		'post_type'   => bp_get_email_post_type(),
	);

	$email = array(
		/* translators: do not remove {} brackets or translate its contents. */
		'post_title'   => __( '[{{{site.name}}}] {{follower.name}} started following you', 'buddyboss' ),
		/* translators: do not remove {} brackets or translate its contents. */
		'post_content' => __( "<a href=\"{{{follower.url}}}\">{{follower.name}}</a> started following you.\n\n{{{member.card}}}", 'buddyboss' ),
		/* translators: do not remove {} brackets or translate its contents. */
		'post_excerpt' => __( "{{follower.name}} started following you.\n\nTo learn more about them, visit their profile: {{{follower.url}}}", 'buddyboss' ),
	);

	$id = 'new-follower';

	if (
		term_exists( $id, bp_get_email_tax_type() ) &&
		get_terms(
			array(
				'taxonomy' => bp_get_email_tax_type(),
				'slug'     => $id,
				'fields'   => 'count',
			)
		) > 0
	) {
		return;
	}

	$post_id = wp_insert_post( bp_parse_args( $email, $defaults, 'install_email_' . $id ) );
	if ( ! $post_id ) {
		return;
	}

	$tt_ids = wp_set_object_terms( $post_id, $id, bp_get_email_tax_type() );

	foreach ( $tt_ids as $tt_id ) {
		$term = get_term_by( 'term_taxonomy_id', (int) $tt_id, bp_get_email_tax_type() );
		wp_update_term(
			(int) $term->term_id,
			bp_get_email_tax_type(),
			array(
				'description' => esc_html__( 'A member receives a new follower', 'buddyboss' ),
			)
		);
	}
}

/**
 * Clear web and api cache on the update.
 *
 * @since BuddyBoss 2.2.6
 *
 * @return void
 */
function bb_update_to_2_2_6() {
	wp_cache_flush();
	// Purge all the cache for API.
	if ( class_exists( 'BuddyBoss\Performance\Cache' ) ) {
		// Clear medias API cache.
		BuddyBoss\Performance\Cache::instance()->purge_by_component( 'bp-media-photos' );
		BuddyBoss\Performance\Cache::instance()->purge_by_component( 'bp-media-albums' );
		BuddyBoss\Performance\Cache::instance()->purge_by_component( 'bp-document' );
		BuddyBoss\Performance\Cache::instance()->purge_by_component( 'bp-video' );
	}
	bb_migrate_subscriptions();
}

/**
 * Migrate forum/topic subscription to new table.
 *
 * @since BuddyBoss 2.2.6
 *
 * @return void
 */
function bb_migrate_subscriptions() {
	$is_already_run = get_transient( 'bb_migrate_subscriptions' );
	if ( $is_already_run ) {
		return;
	}

	set_transient( 'bb_migrate_subscriptions', 'yes', HOUR_IN_SECONDS );
	// Create subscription table.
	bb_core_install_subscription();

	// Migrate the subscription data to new table.
	bb_subscriptions_migrate_users_forum_topic( true, true );

	// Flush the cache to delete all old cached subscriptions.
	wp_cache_flush();
}

/**
 * Clear web and api cache on the update.
 *
 * @since BuddyBoss 2.2.7
 *
 * @return void
 */
function bb_update_to_2_2_7() {
	// Clear cache.
	if (
		function_exists( 'wp_cache_flush_group' ) &&
		function_exists( 'wp_cache_supports' ) &&
		wp_cache_supports( 'flush_group' )
	) {
		wp_cache_flush_group( 'bp_activity' );
		wp_cache_flush_group( 'bp_groups' );
		wp_cache_flush_group( 'bbpress_posts' );
		wp_cache_flush_group( 'post_comment' );
		wp_cache_flush_group( 'bbp-forums' );
		wp_cache_flush_group( 'bbp-replies' );
		wp_cache_flush_group( 'bbp-topics' );
		wp_cache_flush_group( 'blog_post' );
		wp_cache_flush_group( 'bp-notifications' );
	} else {
		wp_cache_flush();
	}

	// Purge all the cache for API.
	if ( class_exists( 'BuddyBoss\Performance\Cache' ) ) {
		// Clear API cache.
		BuddyBoss\Performance\Cache::instance()->purge_by_component( 'bp_activity' );
		BuddyBoss\Performance\Cache::instance()->purge_by_component( 'bp_groups' );
		BuddyBoss\Performance\Cache::instance()->purge_by_component( 'bbpress_posts' );
		BuddyBoss\Performance\Cache::instance()->purge_by_component( 'post_comment' );
		BuddyBoss\Performance\Cache::instance()->purge_by_component( 'bbp-forums' );
		BuddyBoss\Performance\Cache::instance()->purge_by_component( 'bbp-replies' );
		BuddyBoss\Performance\Cache::instance()->purge_by_component( 'bbp-topics' );
		BuddyBoss\Performance\Cache::instance()->purge_by_component( 'blog_post' );
		// Clear notifications API cache.
		BuddyBoss\Performance\Cache::instance()->purge_by_component( 'bp-notifications' );
	}
}

/**
 * Migrate when update the platform to the latest version.
 *
 * @since BuddyBoss 2.2.8
 *
 * @return void
 */
function bb_update_to_2_2_8() {
	$is_already_run = get_transient( 'bb_migrate_group_subscriptions' );
	if ( $is_already_run ) {
		return;
	}

	set_transient( 'bb_migrate_group_subscriptions', 'yes', HOUR_IN_SECONDS );

	// Default enabled the group subscriptions.
	bp_update_option( 'bb_enable_group_subscriptions', 1 );

	// Install new group subscription email templates.
	bb_migrate_group_subscription_email_templates();

	// Migrate group subscriptions.
	bb_migrate_group_subscription( true );
}

/**
 * Install group subscription email templates.
 *
 * @since BuddyBoss 2.2.8
 *
 * @return void
 */
function bb_migrate_group_subscription_email_templates() {
	$email_templates = array(
		array(
			/* translators: do not remove {} brackets or translate its contents. */
			'post_title'       => __( '[{{{site.name}}}] {{poster.name}} posted {{activity.type}} in {{group.name}}', 'buddyboss' ),
			/* translators: do not remove {} brackets or translate its contents. */
			'post_content'     => __( "<a href=\"{{{poster.url}}}\">{{poster.name}}</a> posted {{activity.type}} in <a href=\"{{{group.url}}}\">{{group.name}}</a>:\n\n{{{activity.content}}}", 'buddyboss' ),
			/* translators: do not remove {} brackets or translate its contents. */
			'post_excerpt'     => __( "{{poster.name}} posted {{activity.type}} in {{group.name}}:\n\n{{{activity.content}}}\"\n\nView the post: {{{activity.url}}}", 'buddyboss' ),
			'post_status'      => 'publish',
			'post_type'        => bp_get_email_post_type(),
			'id'               => 'groups-new-activity',
			'term_description' => __( 'New activity post in a group a member is subscribed to', 'buddyboss' ),
		),
		array(
			/* translators: do not remove {} brackets or translate its contents. */
			'post_title'       => __( '[{{{site.name}}}] New discussion in {{group.name}}', 'buddyboss' ),
			/* translators: do not remove {} brackets or translate its contents. */
			'post_content'     => __( "<a href=\"{{{poster.url}}}\">{{poster.name}}</a> created a discussion in <a href=\"{{{group.url}}}\">{{group.name}}</a>:\n\n{{{discussion.content}}}", 'buddyboss' ),
			/* translators: do not remove {} brackets or translate its contents. */
			'post_excerpt'     => __( "{{poster.name}} created a discussion {{discussion.title}} in {{group.name}}:\n\n{{{discussion.content}}}\n\nDiscussion Link: {{discussion.url}}", 'buddyboss' ),
			'post_status'      => 'publish',
			'post_type'        => bp_get_email_post_type(),
			'id'               => 'groups-new-discussion',
			'term_description' => __( 'New forum discussion in a group a member is subscribed to', 'buddyboss' ),
		),
	);

	foreach ( $email_templates as $email_template ) {
		$email_template_id = $email_template['id'];
		$term_description  = $email_template['term_description'];
		unset( $email_template['id'] );
		unset( $email_template['term_description'] );

		if (
			term_exists( $email_template_id, bp_get_email_tax_type() ) &&
			get_terms(
				array(
					'taxonomy' => bp_get_email_tax_type(),
					'slug'     => $email_template_id,
					'fields'   => 'count',
				)
			) > 0
		) {
			continue;
		}

		$post_id = wp_insert_post( $email_template );
		if ( ! $post_id ) {
			continue;
		}

		$tt_ids = wp_set_object_terms( $post_id, $email_template_id, bp_get_email_tax_type() );

		foreach ( $tt_ids as $tt_id ) {
			$term = get_term_by( 'term_taxonomy_id', (int) $tt_id, bp_get_email_tax_type() );
			wp_update_term(
				(int) $term->term_id,
				bp_get_email_tax_type(),
				array(
					'description' => $term_description,
				)
			);
		}
	}
}

/**
 * Background job to update friends count.
 *
 * @since BuddyBoss 2.2.9
 *
 * @return void
 */
function bb_update_to_2_2_9() {
	$is_already_run = get_transient( 'bb_update_to_2_2_9' );
	if ( $is_already_run ) {
		return;
	}

	set_transient( 'bb_update_to_2_2_9', 'yes', HOUR_IN_SECONDS );

	bb_create_background_member_friends_count();
}

/**
 * Create a background job to update the friend count when member suspend/un-suspend.
 *
 * @since BuddyBoss 2.2.9
 *
 * @param int $paged The current page. Default 1.
 *
 * @return void
 */
function bb_create_background_member_friends_count( $paged = 1 ) {
	global $bp_background_updater;

	if ( ! bp_is_active( 'friends' ) ) {
		return;
	}

	if ( empty( $paged ) ) {
		$paged = 1;
	}

	$per_page = 50;
	$offset   = ( ( $paged - 1 ) * $per_page );

	$user_ids = get_users(
		array(
			'fields'     => 'ids',
			'number'     => $per_page,
			'offset'     => $offset,
			'orderby'    => 'ID',
			'order'      => 'DESC',
			'meta_query' => array(
				array(
					'key'     => 'total_friend_count',
					'compare' => 'EXISTS',
				),
			),
		)
	);

	if ( empty( $user_ids ) ) {
		return;
	}

	$bp_background_updater->data(
		array(
			array(
				'callback' => 'bb_migrate_member_friends_count',
				'args'     => array( $user_ids, $paged ),
			),
		)
	);
	$bp_background_updater->save()->schedule_event();
}

/**
 * Update the friend count when member suspend/un-suspend.
 *
 * @since BuddyBoss 2.2.9
 *
 * @param array $user_ids Array of user ID.
 * @param int   $paged    The current page. Default 1.
 *
 * @return void
 */
function bb_migrate_member_friends_count( $user_ids, $paged ) {
	if ( empty( $user_ids ) ) {
		return;
	}

	foreach ( $user_ids as $user_id ) {
		bp_has_members( 'type=alphabetical&page=1&scope=personal&per_page=1&user_id=' . $user_id );
		$query_friend_count = (int) $GLOBALS['members_template']->total_member_count;
		$meta_friend_count  = (int) friends_get_total_friend_count( $user_id );

		if ( $query_friend_count !== $meta_friend_count ) {
			bp_update_user_meta( $user_id, 'total_friend_count', $query_friend_count );
		}
	}

	// Call recursive to finish update for all users.
	$paged++;
	bb_create_background_member_friends_count( $paged );
}

/**
 * Migration for the activity widget based on the relevant feed.
 *
 * @since BuddyBoss 2.3.0
 *
 * @return void
 */
function bb_update_to_2_3_0() {

	if ( bp_is_relevant_feed_enabled() ) {
		$settings = get_option( 'widget_bp_latest_activities' );
		if ( ! empty( $settings ) ) {
			foreach ( $settings as $k => $widget_data ) {
				if ( ! is_int( $k ) ) {
					continue;
				}

				if ( ! empty( $widget_data ) ) {
					if ( ! isset( $widget_data['relevant'] ) ) {
						$widget_data['relevant'] = (bool) bp_is_relevant_feed_enabled();
					}

					$settings[ $k ] = $widget_data;
				}
			}
		}

		update_option( 'widget_bp_latest_activities', $settings );
	}
}

/**
 * Background job to generate user profile slug.
 * Load BuddyBoss Presence API mu plugin.
 *
 * @since BuddyBoss 2.3.1
 *
 * @return void
 */
function bb_update_to_2_3_1() {

	$is_already_run = get_transient( 'bb_update_to_2_3_1' );
	if ( $is_already_run ) {
		return;
	}

	set_transient( 'bb_update_to_2_3_1', 'yes', DAY_IN_SECONDS );

	if ( class_exists( 'BB_Presence' ) ) {
		BB_Presence::bb_load_presence_api_mu_plugin();
		BB_Presence::bb_check_native_presence_load_directly();
	}

	bb_generate_member_profile_links_on_update();
}

/**
 * Function to run while update.
 *
 * @since BuddyBoss 2.3.3
 *
 * @return void
 */
function bb_update_to_2_3_3() {
	bb_repair_member_unique_slug();
}

/**
 * Background job to repair user profile slug.
 *
 * @since BuddyBoss 2.3.3
 *
 * @param int $paged Number of page.
 *
 * @return void
 */
function bb_repair_member_unique_slug( $paged = 1 ) {
	global $bp_background_updater;

	if ( empty( $paged ) ) {
		$paged = 1;
	}

	$per_page = 50;
	$offset   = ( ( $paged - 1 ) * $per_page );

	$user_ids = get_users(
		array(
			'fields'     => 'ids',
			'number'     => $per_page,
			'offset'     => $offset,
			'orderby'    => 'ID',
			'order'      => 'ASC',
			'meta_query' => array(
				array(
					'key'     => 'bb_profile_slug',
					'compare' => 'EXISTS',
				),
			),
		)
	);

	if ( empty( $user_ids ) ) {
		return;
	}

	$bp_background_updater->data(
		array(
			array(
				'callback' => 'bb_remove_duplicate_member_slug',
				'args'     => array( $user_ids, $paged ),
			),
		)
	);
	$bp_background_updater->save()->schedule_event();
}

/**
 * Delete duplicate bb_profile_slug_ key from the usermeta table.
 *
 * @since BuddyBoss 2.3.3
 *
 * @param array $user_ids Array of user ID.
 * @param int   $paged    Number of page.
 *
 * @return void
 */
function bb_remove_duplicate_member_slug( $user_ids, $paged ) {
	global $wpdb;

	foreach ( $user_ids as $user_id ) {
		$unique_slug = bp_get_user_meta( $user_id, 'bb_profile_slug', true );

		$wpdb->query(
			$wpdb->prepare(
			// phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.LikeWildcardsInQuery
				"DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE 'bb_profile_slug_%' AND meta_key != %s AND user_id = %d",
				"bb_profile_slug_{$unique_slug}",
				$user_id
			)
		);
	}

	$paged++;
	bb_repair_member_unique_slug( $paged );
}

/**
 * Updated buddyboss mu file.
 * Migration favorites from user meta to topic meta.
 *
 * @since BuddyBoss 2.3.4
 *
 * @return void
 */
function bb_update_to_2_3_4() {
	if ( file_exists( WPMU_PLUGIN_DIR . '/buddyboss-presence-api.php' ) ) {

		if ( ! class_exists( '\WP_Filesystem_Direct' ) ) {
			require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-base.php';
			require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-direct.php';
		}

		$wp_files_system = new \WP_Filesystem_Direct( array() );
		$wp_files_system->delete( WPMU_PLUGIN_DIR . '/buddyboss-presence-api.php', false, 'f' );
	}

	$is_already_run = get_transient( 'bb_migrate_favorites' );
	if ( $is_already_run ) {
		return;
	}

	set_transient( 'bb_migrate_favorites', 'yes', DAY_IN_SECONDS );
	// Migrate the topic favorites.
	if ( function_exists( 'bb_admin_upgrade_user_favorites' ) ) {
		bb_admin_upgrade_user_favorites( true, get_current_blog_id() );
	}

	wp_cache_flush();
}

/**
 * Background job to update user profile slug.
 *
 * @since BuddyBoss 2.3.41
 *
 * @return void
 */
function bb_update_to_2_3_41() {
	$is_already_run = get_transient( 'bb_update_to_2_3_4' );
	if ( $is_already_run ) {
		return;
	}

	set_transient( 'bb_update_to_2_3_4', 'yes', DAY_IN_SECONDS );

	bb_core_update_repair_member_slug();
}

/**
 * Update the member slugs.
 *
 * @since BuddyBoss 2.3.41
 */
function bb_core_update_repair_member_slug() {
	global $wpdb, $bp_background_updater;

	$user_ids = $wpdb->get_col(
		$wpdb->prepare(
			"SELECT u.ID FROM `{$wpdb->users}` AS u LEFT JOIN `{$wpdb->usermeta}` AS um ON ( u.ID = um.user_id AND um.meta_key = %s ) WHERE ( um.user_id IS NULL OR LENGTH(meta_value) = %d ) ORDER BY u.ID",
			'bb_profile_slug',
			40
		)
	);

	if ( empty( $user_ids ) ) {
		return;
	}

	foreach ( array_chunk( $user_ids, 50 ) as $chunk ) {
		$bp_background_updater->data(
			array(
				array(
					'callback' => 'bb_set_bulk_user_profile_slug',
					'args'     => array( $chunk ),
				),
			)
		);

		$bp_background_updater->save()->schedule_event();
	}
}

/**
 * Clear web and api cache on the update.
 * Install email template for activity following post.
 *
 * @since BuddyBoss 2.3.50
 *
 * @return void
 */
function bb_update_to_2_3_50() {
	// Clear cache.
	wp_cache_flush();
	// Purge all the cache for API.
	if ( class_exists( 'BuddyBoss\Performance\Cache' ) ) {
		// Clear API cache.
		BuddyBoss\Performance\Cache::instance()->purge_all();
	}

	$defaults = array(
		'post_status' => 'publish',
		'post_type'   => bp_get_email_post_type(),
	);

	$email = array(
		/* translators: do not remove {} brackets or translate its contents. */
		'post_title'   => __( '[{{{site.name}}}] {{commenter.name}} replied to your comment', 'buddyboss' ),
		/* translators: do not remove {} brackets or translate its contents. */
		'post_content' => __( "<a href=\"{{{commenter.url}}}\">{{commenter.name}}</a> replied to your comment:\n\n{{{comment_reply}}}", 'buddyboss' ),
		/* translators: do not remove {} brackets or translate its contents. */
		'post_excerpt' => __( "{{commenter.name}} replied to your comment:\n\n{{{comment_reply}}}\n\nView the comment: {{{comment.url}}}", 'buddyboss' ),
	);

	$id = 'new-comment-reply';

	if (
		term_exists( $id, bp_get_email_tax_type() ) &&
		get_terms(
			array(
				'taxonomy' => bp_get_email_tax_type(),
				'slug'     => $id,
				'fields'   => 'count',
			)
		) > 0
	) {
		return;
	}

	$post_id = wp_insert_post( bp_parse_args( $email, $defaults, 'install_email_' . $id ) );
	if ( ! $post_id ) {
		return;
	}

	$tt_ids = wp_set_object_terms( $post_id, $id, bp_get_email_tax_type() );

	foreach ( $tt_ids as $tt_id ) {
		$term = get_term_by( 'term_taxonomy_id', (int) $tt_id, bp_get_email_tax_type() );
		wp_update_term(
			(int) $term->term_id,
			bp_get_email_tax_type(),
			array(
				'description' => esc_html__( 'A member receives a reply to their WordPress post comment', 'buddyboss' ),
			)
		);
	}
}

/**
 * Update background job once plugin update.
 * Migration to add index and new column to media tables.
 * Save the default notification types.
 *
 * @since BuddyBoss 2.3.60
 *
 * @return void
 */
function bb_update_to_2_3_60() {
	global $wpdb;

	// Disabled notification for post type comment reply notification.
	$enabled_notification = bp_get_option( 'bb_enabled_notification', array() );
	if ( ! isset( $enabled_notification['bb_posts_new_comment_reply'] ) ) {
		bb_disable_notification_type( 'bb_posts_new_comment_reply' );
	}

	bb_background_update_group_member_count();

	$tables = array(
		$wpdb->base_prefix . 'bp_media'    => array(
			'blog_id',
			'message_id',
			'group_id',
			'privacy',
			'type',
			'menu_order',
			'date_created',
		),
		$wpdb->base_prefix . 'bp_document' => array(
			'blog_id',
			'message_id',
			'group_id',
			'privacy',
			'menu_order',
			'date_created',
			'date_modified',
		),
	);

	$table_exists = array();
	foreach ( $tables as $table_name => $indexes ) {
		if ( $wpdb->query( $wpdb->prepare( 'SHOW TABLES LIKE %s', bp_esc_like( $table_name ) ) ) ) {
			$row = $wpdb->get_results( "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name = '{$table_name}' AND column_name = 'message_id'" );
			if ( empty( $row ) ) {
				$wpdb->query( "ALTER TABLE {$table_name} ADD message_id BIGINT(20) DEFAULT 0 AFTER activity_id" );
			}
			foreach ( $indexes as $index ) {
				$wpdb->query( "ALTER TABLE {$table_name} ADD INDEX ({$index})" );
			}
			$table_exists[ $table_name ] = true;
		}
	}

	// Update older data.
	bb_create_background_message_media_document_update( $table_exists );
}

/**
 * Function to update group member count with background updater.
 *
 * @since BuddyBoss 2.3.60
 */
function bb_background_update_group_member_count() {
	global $wpdb, $bp_background_updater;

	if ( ! bp_is_active( 'groups' ) ) {
		return;
	}

	// Fetch all groups.
	$sql       = "SELECT DISTINCT id FROM {$wpdb->base_prefix}bp_groups ORDER BY id DESC";
	$group_ids = $wpdb->get_col( $sql );

	if ( empty( $group_ids ) ) {
		return;
	}

	$min_count = (int) apply_filters( 'bb_update_group_member_count', 10 );

	if ( count( $group_ids ) > $min_count ) {
		foreach ( array_chunk( $group_ids, $min_count ) as $chunk ) {
			$bp_background_updater->data(
				array(
					array(
						'callback' => 'bb_update_group_member_count',
						'args'     => array( $chunk ),
					),
				)
			);
			$bp_background_updater->save()->schedule_event();
		}
	} else {
		bb_update_group_member_count( $group_ids );
	}
}

/**
 * Schedule event for message media and document migration.
 *
 * @since BuddyBoss 2.3.60
 *
 * @param array $table_exists List of tables.
 * @param int   $paged        Page number.
 */
function bb_create_background_message_media_document_update( $table_exists, $paged = 1 ) {
	global $wpdb, $bp_background_updater;

	if ( empty( $paged ) ) {
		$paged = 1;
	}

	$per_page = 50;
	$offset   = ( ( $paged - 1 ) * $per_page );
	$results  = array();

	$message_meta_table_name = $wpdb->base_prefix . 'bp_messages_meta';
	if ( $wpdb->query( $wpdb->prepare( 'SHOW TABLES LIKE %s', bp_esc_like( $message_meta_table_name ) ) ) ) {
		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT message_id, meta_key, meta_value FROM {$message_meta_table_name} WHERE meta_key IN
				('bp_media_ids', 'bp_video_ids', 'bp_document_ids') AND meta_value !=''
				ORDER BY message_id LIMIT %d offset %d",
				$per_page,
				$offset
			)
		);
	}

	if ( empty( $results ) ) {
		return;
	}

	$bp_background_updater->data(
		array(
			array(
				'callback' => 'bb_migrate_message_media_document',
				'args'     => array( $table_exists, $results, $paged ),
			),
		)
	);
	$bp_background_updater->save()->schedule_event();
}

/**
 * Message media and document migration callback.
 *
 * @since BuddyBoss 2.3.60
 *
 * @param array $table_exists List of tables.
 * @param array $results      Results from message meta table.
 * @param int   $paged        Page number.
 */
function bb_migrate_message_media_document( $table_exists, $results, $paged ) {
	global $wpdb;

	if ( empty( $results ) ) {
		return;
	}

	foreach ( $results as $result ) {
		$table_name = $wpdb->base_prefix . 'bp_media';
		if ( 'bp_document_ids' === $result->meta_key ) {
			$table_name = $wpdb->base_prefix . 'bp_document';
		}

		// Check valid ids & update message_id column.
		if (
			! empty( $table_exists ) &&
			array_key_exists( $table_name, $table_exists ) &&
			isset( $result->message_id ) &&
			isset( $result->meta_value ) &&
			preg_match( '/^\d+(?:,\d+)*$/', $result->meta_value )
		) {

			$query = $wpdb->prepare( "UPDATE {$table_name} SET message_id = %d WHERE id IN ( {$result->meta_value} )", $result->message_id );

			$wpdb->query( $query );

			$id_array = explode( ',', $result->meta_value );
			if ( ! empty( $id_array ) ) {
				foreach ( $id_array as $media_id ) {
					$media = '';
					if ( 'bp_document_ids' === $result->meta_key && class_exists( 'BP_Document' ) ) {
						$media = new BP_Document( $media_id );
					} elseif ( class_exists( 'BP_Media' ) ) {
						$media = new BP_Media( $media_id );
					}
					if ( ! empty( $media ) ) {
						update_post_meta( $media->attachment_id, 'bp_media_parent_message_id', $media->message_id );
					}
				}
			}
		}
	}

	// Call recursive to finish update for all records.
	$paged++;
	bb_create_background_message_media_document_update( $table_exists, $paged );
}

/**
 * Migrate data when plugin update.
 *
 * @since BuddyBoss 2.3.80
 */
function bb_update_to_2_3_80() {
	bb_core_update_repair_duplicate_following_notification();

	// Purge all the cache for API.
	if ( class_exists( 'BuddyBoss\Performance\Cache' ) ) {
		BuddyBoss\Performance\Cache::instance()->purge_all();
	}
}

/**
 * Function will fetch and delete duplicate following notification data.
 *
 * @since BuddyBoss 2.3.80
 */
function bb_core_update_repair_duplicate_following_notification() {
	global $wpdb;
	$bp = buddypress();

	$sql  = "DELETE FROM {$bp->notifications->table_name}";
	$sql .= ' WHERE id IN (';
	$sql .= " SELECT * FROM ( SELECT DISTINCT n1.id FROM {$bp->notifications->table_name} n1";
	$sql .= " JOIN {$bp->notifications->table_name} n2 ON n1.user_id = n2.user_id";
	$sql .= ' WHERE n1.secondary_item_id = n2.secondary_item_id';
	$sql .= ' AND n1.date_notified < n2.date_notified';
	$sql .= ' AND n1.component_name = %s AND n1.component_action = %s';
	$sql .= ' ORDER BY n1.id DESC) AS ids';
	$sql .= ' )';

	// Remove duplicate notification ids.
	$wpdb->query( $wpdb->prepare( $sql, 'activity', 'bb_following_new' ) );

	// Purge all the cache for API.
	if ( class_exists( 'BuddyBoss\Performance\Cache' ) ) {
		// Clear notifications API cache.
		BuddyBoss\Performance\Cache::instance()->purge_by_component( 'bp-notifications' );
	}
}

/**
 * Migrate icon class for the documents.
 * Assign the group organizer to the group has 0 members.
 *
 * @since BuddyBoss 2.4.10
 *
 * @return void
 */
function bb_update_to_2_4_10() {
	global $wpdb;

	if ( bp_is_active( 'document' ) ) {
		$saved_extensions = bp_get_option( 'bp_document_extensions_support', array() );
		$default          = bp_media_allowed_document_type();

		foreach ( $default as $key => $value ) {
			if ( isset( $saved_extensions[ $key ] ) ) {
				$document_file_extension          = substr( strrchr( $value['extension'], '.' ), 1 );
				$new_icon                         = bp_document_svg_icon( $document_file_extension );
				$saved_extensions[ $key ]['icon'] = $new_icon;
			}
		}

		bp_update_option( 'bp_document_extensions_support', $saved_extensions );
	}

	if ( ! bp_is_active( 'groups' ) ) {
		return;
	}

	$groups     = $wpdb->base_prefix . 'bp_groups';
	$group_meta = $wpdb->base_prefix . 'bp_groups_groupmeta';

	$sql = "SELECT g.id FROM {$groups} g";
	$sql .= " INNER JOIN {$group_meta} gm ON gm.group_id = g.id";
	$sql .= ' WHERE gm.meta_key = %s AND gm.meta_value = %d';

	// Get the group ids with 0 members.
	$groups = $wpdb->get_results( $wpdb->prepare( $sql, 'total_member_count', 0 ) );

	if ( ! empty( $groups ) ) {
		$admin = get_users(
			array(
				'blog_id' => bp_get_root_blog_id(),
				'fields'  => 'ID',
				'number'  => 1,
				'orderby' => 'ID',
				'role'    => 'administrator',
			)
		);

		if ( ! empty( $admin ) && ! is_wp_error( $admin ) ) {
			$admin_id = current( $admin );

			// Assign the group organizer to all the group that has 0 members.
			foreach ( $groups as $group ) {
				groups_join_group( $group->id, $admin_id );
				$member = new BP_Groups_Member( $admin_id, $group->id );
				$member->promote( 'admin' );
			}
		}
	}
}
