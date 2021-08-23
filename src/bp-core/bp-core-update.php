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

	// Current DB version of this site (per site in a multisite network).
	$current_db   = bp_get_option( '_bp_db_version' );
	$current_live = bp_get_db_version();

	// Compare versions (cast as int and bool to be safe).
	$is_update = (bool) ( (int) $current_db < (int) $current_live );

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
	if ( $action == 'activate' ) {
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

		// Apply schema and set Activity and XProfile components as active.
		bp_core_install( $default_components );
		bp_update_option( 'bp-active-components', $default_components );
		bp_core_add_page_mappings( $default_components, 'delete' );
		bp_core_install_emails();
		bp_core_install_invitations();

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

		// Version 3.1.1
		if ( $raw_db_version < 13731 ) {
			bp_update_to_3_1_1();
		}

		// Version 3.1.1
		if ( $raw_db_version < 14001 ) {
			bb_update_to_1_2_3();
		}

		// Version 3.1.1
		if ( $raw_db_version < 14801 ) {
			bp_update_to_1_2_4();
		}

		if ( $raw_db_version < 14901 ) {
			bp_update_to_1_2_9();
		}

		if ( $raw_db_version < 15200 ) {
			bp_update_to_1_3_0();
		}

		// Version 1.3.5
		if ( $raw_db_version < 15601 ) {
			bb_update_to_1_3_5();
		}

		// Version 1.4.0
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
	}

	/* All done! *************************************************************/

	// Bump the version.
	bp_version_bump();

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

	// Update post_titles
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
	bb_core_enable_default_symlink_support();
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

function bp_update_default_doc_extensions() {

	$get_extensions = bp_get_option( 'bp_document_extensions_support', array());

	//	$changed_array = array(
	//		'bb_doc_52'   => array(
	//			'description' => '7z Archive XYZ',
	//		)
	//	);
	//
	//
	//	if ( !empty( $changed_array ) ) {
	//		foreach ( $changed_array as $k => $v ) {
	//			if ( array_key_exists( $k, $get_extensions ) ) {
	//				$extension = $get_extensions[$k];
	//				$get_extensions[$k] = array_replace( $extension, $v );
	//			} else {
	//				// For newly add key.
	//				$get_extensions[$k] = $v;
	//			}
	//		}
	//	}
	//
	//	$removed_array = array(
	//		'bb_doc_51'
	//	);
	//
	//	if ( !empty( $removed_array ) ) {
	//		foreach (  $removed_array as $key ) {
	//			unset( $get_extensions[$key] );
	//		}
	//
	//	}

	//bp_update_option( 'bp_document_extensions_support', $get_extensions );
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
				$slug    = get_page_uri( $page_id );

				// Set BBPress root Slug
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
	if ( class_exists( 'BP_BuddyBoss_Platform_Updater' ) ) {
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
 *
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
	// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
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
