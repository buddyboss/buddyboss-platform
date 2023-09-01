<?php
/**
 * BuddyPress DB schema.
 *
 * @since   BuddyPress 2.3.0
 * @package BuddyBoss\Core\Administration
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Main installer.
 *
 * Can be passed an optional array of components to explicitly run installation
 * routines on, typically the first time a component is activated in Settings.
 *
 * @since BuddyPress 1.0.0
 *
 * @param array|bool $active_components Components to install.
 */
function bp_core_install( $active_components = false ) {

	bp_pre_schema_upgrade();

	// If no components passed, get all the active components from the main site.
	if ( empty( $active_components ) ) {

		/** This filter is documented in bp-core/admin/bp-core-admin-components.php */
		$active_components = apply_filters( 'bp_active_components', bp_get_option( 'bp-active-components' ) );

		// check for xprofile is active component in db or not if not then update it.
		if ( empty( $active_components['xprofile'] ) ) {
			$active_components['xprofile'] = 1;

			bp_update_option( 'bp-active-components', $active_components );
		}
	}

	if ( function_exists( 'bb_is_email_queue' ) && bb_is_email_queue() ) {
		// Install email queue table.
		bb_email_queue()::create_db_table();
	}

	if ( function_exists( 'bb_load_reaction' ) ) {
		// Create table for the bb reactions.
		bb_load_reaction()->create_table();
	}

	// Install Activity Feeds even when inactive (to store last_activity data).
	bp_core_install_activity_streams();

	// Install the signups table.
	bp_core_maybe_install_signups();

	// Install item subscriptions.
	bb_core_install_subscription();

	// Notifications.
	if ( ! empty( $active_components['notifications'] ) ) {
		bp_core_install_notifications();
	}

	// Connections.
	if ( ! empty( $active_components['friends'] ) ) {
		bp_core_install_friends();
	}

	// Follow.
	if ( ! empty( $active_components['activity'] ) ) {
		bp_core_install_follow();
	}

	// Extensible Groups.
	if ( ! empty( $active_components['groups'] ) ) {
		bp_core_install_groups();
	}

	// Private Messaging.
	if ( ! empty( $active_components['messages'] ) ) {
		bp_core_install_private_messaging();
	}

	// Profile Fields.
	if ( ! empty( $active_components['xprofile'] ) ) {
		bp_core_install_extended_profiles();
	}

	// Blog tracking.
	if ( ! empty( $active_components['blogs'] ) ) {
		bp_core_install_blog_tracking();
	}

	// Discussion forums.
	if ( ! empty( $active_components['forums'] ) ) {
		bp_core_install_discussion_forums();
	}

	// Media.
	if ( ! empty( $active_components['media'] ) ) {
		bp_core_install_media();
		bp_core_install_document();
		if ( false === bp_get_option( 'bp_media_symlink_support', false ) ) {
			bp_update_option( 'bp_media_symlink_support', 1 );
		}
	}

	if ( ! empty( $active_components['moderation'] ) ) {
		bp_core_install_suspend();
		bp_core_install_moderation();
	}

	if ( class_exists( '\BuddyBoss\Performance\Performance' ) ) {
		\BuddyBoss\Performance\Performance::instance()->on_activation();
	}

	do_action( 'bp_core_install', $active_components );

	// Needs to flush all cache when component activate/deactivate.
	wp_cache_flush();

	// Reset the permalink to fix the 404 on some pages.
	flush_rewrite_rules();
}

/**
 * Uninstall forums if $uninstalled_components['forums'] is not empty.
 *
 * @since BuddyBoss 1.0.0
 */
function bp_core_uninstall( $uninstalled_components ) {
	// Discussion forums
	if ( ! empty( $uninstalled_components['forums'] ) ) {
		bp_core_uninstall_discussion_forums();
	}
}

/**
 * Install database tables for the Notifications component.
 *
 * @since BuddyPress 1.0.0
 */
function bp_core_install_notifications() {
	$sql             = array();
	$charset_collate = $GLOBALS['wpdb']->get_charset_collate();
	$bp_prefix       = bp_core_get_table_prefix();

	$sql[] = "CREATE TABLE {$bp_prefix}bp_notifications (
				id bigint(20) NOT NULL AUTO_INCREMENT,
				user_id bigint(20) NOT NULL,
				item_id bigint(20) NOT NULL,
				secondary_item_id bigint(20),
				component_name varchar(75) NOT NULL,
				component_action varchar(75) NOT NULL,
				date_notified datetime NOT NULL,
				is_new bool NOT NULL DEFAULT 0,
				PRIMARY KEY  (id),
				KEY item_id (item_id),
				KEY secondary_item_id (secondary_item_id),
				KEY user_id (user_id),
				KEY is_new (is_new),
				KEY component_name (component_name),
				KEY component_action (component_action),
				KEY useritem (user_id,is_new)
			) {$charset_collate};";

	$sql[] = "CREATE TABLE {$bp_prefix}bp_notifications_meta (
				id bigint(20) NOT NULL AUTO_INCREMENT,
				notification_id bigint(20) NOT NULL,
				meta_key varchar(255) DEFAULT NULL,
				meta_value longtext DEFAULT NULL,
				PRIMARY KEY  (id),
				KEY notification_id (notification_id),
				KEY meta_key (meta_key(191))
			) {$charset_collate};";

	dbDelta( $sql );
}

/**
 * Install database tables for the Activity component.
 *
 * @since BuddyPress 1.0.0
 */
function bp_core_install_activity_streams() {
	$sql             = array();
	$charset_collate = $GLOBALS['wpdb']->get_charset_collate();
	$bp_prefix       = bp_core_get_table_prefix();

	$sql[] = "CREATE TABLE {$bp_prefix}bp_activity (
				id bigint(20) NOT NULL AUTO_INCREMENT,
				user_id bigint(20) NOT NULL,
				component varchar(75) NOT NULL,
				type varchar(75) NOT NULL,
				action text NOT NULL,
				content longtext NOT NULL,
				primary_link text NOT NULL,
				item_id bigint(20) NOT NULL,
				secondary_item_id bigint(20) DEFAULT NULL,
				date_recorded datetime NOT NULL,
				hide_sitewide bool DEFAULT 0,
				mptt_left int(11) NOT NULL DEFAULT 0,
				mptt_right int(11) NOT NULL DEFAULT 0,
				is_spam tinyint(1) NOT NULL DEFAULT 0,
				privacy varchar(75) NOT NULL DEFAULT 'public',
				PRIMARY KEY  (id),
				KEY date_recorded (date_recorded),
				KEY user_id (user_id),
				KEY item_id (item_id),
				KEY secondary_item_id (secondary_item_id),
				KEY component (component),
				KEY type (type),
				KEY mptt_left (mptt_left),
				KEY mptt_right (mptt_right),
				KEY hide_sitewide (hide_sitewide),
				KEY is_spam (is_spam)
			) {$charset_collate};";

	$sql[] = "CREATE TABLE {$bp_prefix}bp_activity_meta (
				id bigint(20) NOT NULL AUTO_INCREMENT,
				activity_id bigint(20) NOT NULL,
				meta_key varchar(255) DEFAULT NULL,
				meta_value longtext DEFAULT NULL,
				PRIMARY KEY  (id),
				KEY activity_id (activity_id),
				KEY meta_key (meta_key(191))
			) {$charset_collate};";

	dbDelta( $sql );
}

/**
 * Install database tables for the Friends component.
 *
 * @since BuddyPress 1.0.0
 */
function bp_core_install_friends() {
	$sql             = array();
	$charset_collate = $GLOBALS['wpdb']->get_charset_collate();
	$bp_prefix       = bp_core_get_table_prefix();

	$sql[] = "CREATE TABLE {$bp_prefix}bp_friends (
				id bigint(20) NOT NULL AUTO_INCREMENT,
				initiator_user_id bigint(20) NOT NULL,
				friend_user_id bigint(20) NOT NULL,
				is_confirmed bool DEFAULT 0,
				is_limited bool DEFAULT 0,
				date_created datetime NOT NULL,
				PRIMARY KEY  (id),
				KEY initiator_user_id (initiator_user_id),
				KEY friend_user_id (friend_user_id)
			) {$charset_collate};";

	dbDelta( $sql );
}

/**
 * Install database tables for the Follow component.
 *
 * @since BuddyBoss 1.0.0
 */
function bp_core_install_follow() {
	$sql             = array();
	$charset_collate = $GLOBALS['wpdb']->get_charset_collate();
	$bp_prefix       = bp_core_get_table_prefix();

	$sql[] = "CREATE TABLE {$bp_prefix}bp_follow (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			leader_id bigint(20) NOT NULL,
			follower_id bigint(20) NOT NULL,
			PRIMARY KEY  (id),
			KEY followers (leader_id, follower_id)
		) {$charset_collate};";

	dbDelta( $sql );
}

/**
 * Install database tables for the Groups component.
 *
 * @since BuddyPress 1.0.0
 */
function bp_core_install_groups() {
	$sql             = array();
	$charset_collate = $GLOBALS['wpdb']->get_charset_collate();
	$bp_prefix       = bp_core_get_table_prefix();

	$sql[] = "CREATE TABLE {$bp_prefix}bp_groups (
				id bigint(20) NOT NULL AUTO_INCREMENT,
				creator_id bigint(20) NOT NULL,
				name varchar(100) NOT NULL,
				slug varchar(200) NOT NULL,
				description longtext NOT NULL,
				status varchar(10) NOT NULL DEFAULT 'public',
				parent_id bigint(20) NOT NULL DEFAULT 0,
				enable_forum tinyint(1) NOT NULL DEFAULT '1',
				date_created datetime NOT NULL,
				PRIMARY KEY  (id),
				KEY creator_id (creator_id),
				KEY status (status),
				KEY parent_id (parent_id)
			) {$charset_collate};";

	$sql[] = "CREATE TABLE {$bp_prefix}bp_groups_members (
				id bigint(20) NOT NULL AUTO_INCREMENT,
				group_id bigint(20) NOT NULL,
				user_id bigint(20) NOT NULL,
				inviter_id bigint(20) NOT NULL,
				is_admin tinyint(1) NOT NULL DEFAULT '0',
				is_mod tinyint(1) NOT NULL DEFAULT '0',
				user_title varchar(100) NOT NULL,
				date_modified datetime NOT NULL,
				comments longtext NOT NULL,
				is_confirmed tinyint(1) NOT NULL DEFAULT '0',
				is_banned tinyint(1) NOT NULL DEFAULT '0',
				invite_sent tinyint(1) NOT NULL DEFAULT '0',
				PRIMARY KEY  (id),
				KEY group_id (group_id),
				KEY is_admin (is_admin),
				KEY is_mod (is_mod),
				KEY user_id (user_id),
				KEY inviter_id (inviter_id),
				KEY is_confirmed (is_confirmed)
			) {$charset_collate};";

	$sql[] = "CREATE TABLE {$bp_prefix}bp_groups_groupmeta (
				id bigint(20) NOT NULL AUTO_INCREMENT,
				group_id bigint(20) NOT NULL,
				meta_key varchar(255) DEFAULT NULL,
				meta_value longtext DEFAULT NULL,
				PRIMARY KEY  (id),
				KEY group_id (group_id),
				KEY meta_key (meta_key(191))
			) {$charset_collate};";

	$sql[] = "CREATE TABLE {$bp_prefix}bp_groups_membermeta (
				id bigint(20) NOT NULL AUTO_INCREMENT,
				member_id bigint(20) NOT NULL,
				meta_key varchar(255) DEFAULT NULL,
				meta_value longtext DEFAULT NULL,
				PRIMARY KEY  (id),
				KEY member_id (member_id),
				KEY meta_key (meta_key(191))
			) {$charset_collate};";

	dbDelta( $sql );
}

/**
 * Install database tables for the Messages component.
 *
 * @since BuddyPress 1.0.0
 */
function bp_core_install_private_messaging() {
	$sql             = array();
	$charset_collate = $GLOBALS['wpdb']->get_charset_collate();
	$bp_prefix       = bp_core_get_table_prefix();

	$sql[] = "CREATE TABLE {$bp_prefix}bp_messages_messages (
				id bigint(20) NOT NULL AUTO_INCREMENT,
				thread_id bigint(20) NOT NULL,
				sender_id bigint(20) NOT NULL,
				subject varchar(200) NOT NULL,
				message longtext NOT NULL,
				is_deleted tinyint(1) NOT NULL DEFAULT '0',
				date_sent datetime NOT NULL,
				PRIMARY KEY  (id),
				KEY sender_id (sender_id),
				KEY thread_id (thread_id)
			) {$charset_collate};";

	$sql[] = "CREATE TABLE {$bp_prefix}bp_messages_recipients (
				id bigint(20) NOT NULL AUTO_INCREMENT,
				user_id bigint(20) NOT NULL,
				thread_id bigint(20) NOT NULL,
				unread_count int(10) NOT NULL DEFAULT '0',
				sender_only tinyint(1) NOT NULL DEFAULT '0',
				is_deleted tinyint(1) NOT NULL DEFAULT '0',
				is_hidden tinyint(1) NOT NULL DEFAULT '0',
				PRIMARY KEY  (id),
				KEY user_id (user_id),
				KEY thread_id (thread_id),
				KEY is_deleted (is_deleted),
				KEY is_hidden (is_hidden),
				KEY sender_only (sender_only),
				KEY unread_count (unread_count)
			) {$charset_collate};";

	$sql[] = "CREATE TABLE {$bp_prefix}bp_messages_notices (
				id bigint(20) NOT NULL AUTO_INCREMENT,
				subject varchar(200) NOT NULL,
				message longtext NOT NULL,
				date_sent datetime NOT NULL,
				is_active tinyint(1) NOT NULL DEFAULT '0',
				PRIMARY KEY  (id),
				KEY is_active (is_active)
			) {$charset_collate};";

	$sql[] = "CREATE TABLE {$bp_prefix}bp_messages_meta (
				id bigint(20) NOT NULL AUTO_INCREMENT,
				message_id bigint(20) NOT NULL,
				meta_key varchar(255) DEFAULT NULL,
				meta_value longtext DEFAULT NULL,
				PRIMARY KEY  (id),
				KEY message_id (message_id),
				KEY meta_key (meta_key(191))
			) {$charset_collate};";

	dbDelta( $sql );
}

/**
 * Install database tables for the Profiles component.
 *
 * @since BuddyPress 1.0.0
 */
function bp_core_install_extended_profiles() {
	$sql             = array();
	$charset_collate = $GLOBALS['wpdb']->get_charset_collate();
	$bp_prefix       = bp_core_get_table_prefix();

	$sql[] = "CREATE TABLE {$bp_prefix}bp_xprofile_groups (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				name varchar(150) NOT NULL,
				description mediumtext NOT NULL,
				group_order bigint(20) NOT NULL DEFAULT '0',
				can_delete tinyint(1) NOT NULL,
				PRIMARY KEY  (id),
				KEY can_delete (can_delete)
			) {$charset_collate};";

	$sql[] = "CREATE TABLE {$bp_prefix}bp_xprofile_fields (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				group_id bigint(20) unsigned NOT NULL,
				parent_id bigint(20) unsigned NOT NULL,
				type varchar(150) NOT NULL,
				name varchar(150) NOT NULL,
				description longtext NOT NULL,
				is_required tinyint(1) NOT NULL DEFAULT '0',
				is_default_option tinyint(1) NOT NULL DEFAULT '0',
				field_order bigint(20) NOT NULL DEFAULT '0',
				option_order bigint(20) NOT NULL DEFAULT '0',
				order_by varchar(15) NOT NULL DEFAULT '',
				can_delete tinyint(1) NOT NULL DEFAULT '1',
				PRIMARY KEY  (id),
				KEY group_id (group_id),
				KEY parent_id (parent_id),
				KEY field_order (field_order),
				KEY can_delete (can_delete),
				KEY is_required (is_required)
			) {$charset_collate};";

	$sql[] = "CREATE TABLE {$bp_prefix}bp_xprofile_data (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				field_id bigint(20) unsigned NOT NULL,
				user_id bigint(20) unsigned NOT NULL,
				value longtext NOT NULL,
				last_updated datetime NOT NULL,
				PRIMARY KEY  (id),
				KEY field_id (field_id),
				KEY user_id (user_id)
			) {$charset_collate};";

	$sql[] = "CREATE TABLE {$bp_prefix}bp_xprofile_meta (
				id bigint(20) NOT NULL AUTO_INCREMENT,
				object_id bigint(20) NOT NULL,
				object_type varchar(150) NOT NULL,
				meta_key varchar(255) DEFAULT NULL,
				meta_value longtext DEFAULT NULL,
				PRIMARY KEY  (id),
				KEY object_id (object_id),
				KEY meta_key (meta_key(191))
			) {$charset_collate};";

	dbDelta( $sql );

	bp_core_install_default_profiles_fields();
}

/**
 * Install default profile fields.
 *
 * @since BuddyBoss 1.0.0
 */
function bp_core_install_default_profiles_fields() {
	global $wpdb;

	$bp_prefix = bp_core_get_table_prefix();

	bp_core_update_group_fields_id_in_db();

	$is_multisite = is_multisite() ? true : false;

	// These values should only be updated if they are not already present.
	if ( ! bp_get_option( 'bp-xprofile-base-group-name' ) ) {
		bp_update_option( 'bp-xprofile-base-group-name', __( 'General', 'buddyboss' ) );
	}

	if ( ! bp_get_option( 'bp-xprofile-firstname-field-name' ) ) {
		bp_update_option( 'bp-xprofile-firstname-field-name', __( 'First Name', 'buddyboss' ) );
	}

	if ( ! bp_get_option( 'bp-xprofile-lastname-field-name' ) ) {
		bp_update_option( 'bp-xprofile-lastname-field-name', __( 'Last Name', 'buddyboss' ) );
	}

	if ( ! bp_get_option( 'bp-xprofile-nickname-field-name' ) ) {
		bp_update_option( 'bp-xprofile-nickname-field-name', __( 'Nickname', 'buddyboss' ) );
	}

	// Insert the default group and fields.
	$insert_sql = array();

	$base_group_id = bp_xprofile_base_group_id();
	if ( ! $wpdb->get_var( "SELECT id FROM {$bp_prefix}bp_xprofile_groups WHERE id = {$base_group_id}" ) ) {

		$result = $wpdb->insert(
			"{$bp_prefix}bp_xprofile_groups",
			array(
				'name'        => bp_get_option( 'bp-xprofile-base-group-name' ),
				'description' => '',
				'can_delete'  => 0,
			)
		);

		if ( $result ) {
			$base_group_id = $wpdb->insert_id;
			if ( $is_multisite ) {
				add_site_option( 'bp-xprofile-base-group-id', $base_group_id );
			}
		}
	}
	bp_update_option( 'bp-xprofile-base-group-id', $base_group_id );

	// First name
	$first_name_id = bp_xprofile_firstname_field_id();

	if ( $first_name_id > 0 ) {
		if ( ! $wpdb->get_var( "SELECT id FROM {$bp_prefix}bp_xprofile_fields WHERE id = {$first_name_id}" ) ) {
			$result = $wpdb->insert(
				"{$bp_prefix}bp_xprofile_fields",
				array(
					'group_id'    => $base_group_id,
					'parent_id'   => 0,
					'type'        => 'textbox',
					'name'        => bp_get_option( 'bp-xprofile-firstname-field-name' ),
					'description' => '',
					'is_required' => 1,
					'can_delete'  => 0,
				)
			);
			if ( $result ) {
				$first_name_id = $wpdb->insert_id;
				if ( $is_multisite ) {
					add_site_option( 'bp-xprofile-firstname-field-id', $first_name_id );
				}
			}
		}
	} else {
		$result = $wpdb->insert(
			"{$bp_prefix}bp_xprofile_fields",
			array(
				'group_id'    => $base_group_id,
				'parent_id'   => 0,
				'type'        => 'textbox',
				'name'        => bp_get_option( 'bp-xprofile-firstname-field-name' ),
				'description' => '',
				'is_required' => 1,
				'can_delete'  => 0,
			)
		);
		if ( $result ) {
			$first_name_id = $wpdb->insert_id;
			if ( $is_multisite ) {
				add_site_option( 'bp-xprofile-firstname-field-id', $first_name_id );
			}
		}
	}
	bp_update_option( 'bp-xprofile-firstname-field-id', $first_name_id );

	// Last name
	$last_name_id = bp_xprofile_lastname_field_id();
	if ( $last_name_id > 0 ) {
		if ( ! $wpdb->get_var( "SELECT id FROM {$bp_prefix}bp_xprofile_fields WHERE id = {$last_name_id}" ) ) {
			$result = $wpdb->insert(
				"{$bp_prefix}bp_xprofile_fields",
				array(
					'group_id'    => $base_group_id,
					'parent_id'   => 0,
					'type'        => 'textbox',
					'name'        => bp_get_option( 'bp-xprofile-lastname-field-name' ),
					'description' => '',
					'is_required' => 1,
					'can_delete'  => 0,
				)
			);
			if ( $result ) {
				$last_name_id = $wpdb->insert_id;
				if ( $is_multisite ) {
					add_site_option( 'bp-xprofile-lastname-field-id', $last_name_id );
				}
			}
		}
	} else {
		$result = $wpdb->insert(
			"{$bp_prefix}bp_xprofile_fields",
			array(
				'group_id'    => $base_group_id,
				'parent_id'   => 0,
				'type'        => 'textbox',
				'name'        => bp_get_option( 'bp-xprofile-lastname-field-name' ),
				'description' => '',
				'is_required' => 1,
				'can_delete'  => 0,
			)
		);
		if ( $result ) {
			$last_name_id = $wpdb->insert_id;
			if ( $is_multisite ) {
				add_site_option( 'bp-xprofile-lastname-field-id', $last_name_id );
			}
		}
	}
	bp_update_option( 'bp-xprofile-lastname-field-id', $last_name_id );

	// Nickname
	$nickname_id = bp_xprofile_nickname_field_id();
	if ( $last_name_id > 0 ) {
		if ( ! $wpdb->get_var( "SELECT id FROM {$bp_prefix}bp_xprofile_fields WHERE id = {$nickname_id}" ) ) {
			$result = $wpdb->insert(
				"{$bp_prefix}bp_xprofile_fields",
				array(
					'group_id'    => $base_group_id,
					'parent_id'   => 0,
					'type'        => 'textbox',
					'name'        => bp_get_option( 'bp-xprofile-nickname-field-name' ),
					'description' => '',
					'is_required' => 1,
					'can_delete'  => 0,
				)
			);
			if ( $result ) {
				$nickname_id = $wpdb->insert_id;
				if ( $is_multisite ) {
					add_site_option( 'bp-xprofile-nickname-field-id', $nickname_id );
				}
			}
		}
	} else {
		$result = $wpdb->insert(
			"{$bp_prefix}bp_xprofile_fields",
			array(
				'group_id'    => $base_group_id,
				'parent_id'   => 0,
				'type'        => 'textbox',
				'name'        => bp_get_option( 'bp-xprofile-nickname-field-name' ),
				'description' => '',
				'is_required' => 1,
				'can_delete'  => 0,
			)
		);
		if ( $result ) {
			$nickname_id = $wpdb->insert_id;
			if ( $is_multisite ) {
				add_site_option( 'bp-xprofile-nickname-field-id', $nickname_id );
			}
		}
	}
	bp_update_option( 'bp-xprofile-nickname-field-id', $nickname_id );
}

/**
 * Install database tables for the Sites component.
 *
 * @since BuddyPress 1.0.0
 */
function bp_core_install_blog_tracking() {
	$sql             = array();
	$charset_collate = $GLOBALS['wpdb']->get_charset_collate();
	$bp_prefix       = bp_core_get_table_prefix();

	$sql[] = "CREATE TABLE {$bp_prefix}bp_user_blogs (
				id bigint(20) NOT NULL AUTO_INCREMENT,
				user_id bigint(20) NOT NULL,
				blog_id bigint(20) NOT NULL,
				PRIMARY KEY  (id),
				KEY user_id (user_id),
				KEY blog_id (blog_id)
			) {$charset_collate};";

	$sql[] = "CREATE TABLE {$bp_prefix}bp_user_blogs_blogmeta (
				id bigint(20) NOT NULL AUTO_INCREMENT,
				blog_id bigint(20) NOT NULL,
				meta_key varchar(255) DEFAULT NULL,
				meta_value longtext DEFAULT NULL,
				PRIMARY KEY  (id),
				KEY blog_id (blog_id),
				KEY meta_key (meta_key(191))
			) {$charset_collate};";

	dbDelta( $sql );
}

/** Discussion Forums *********************************************************/

/**
 * Run the bbpress activation.
 *
 * @since BuddyBoss 1.0.0
 */
function bp_core_install_discussion_forums() {
	require_once buddypress()->plugin_dir . 'bp-forums/classes/class-bbpress.php';
	bbpress();

	bbp_activation();
	bbp_map_caps_to_wp_roles();
}

/**
 * Run the bbpress deactivation.
 *
 * @since BuddyBoss 1.0.0
 */
function bp_core_uninstall_discussion_forums() {
	bbp_deactivation();
}

/** Media *********************************************************/

/**
 * Install database tables for Media component.
 *
 * @since BuddyBoss 1.0.0
 */
function bp_core_install_media() {
	$sql             = array();
	$charset_collate = $GLOBALS['wpdb']->get_charset_collate();
	$bp_prefix       = bp_core_get_table_prefix();

	$sql[] = "CREATE TABLE {$bp_prefix}bp_media_albums (
	   id bigint(20) NOT NULL AUTO_INCREMENT,
	   user_id bigint(20) NOT NULL,
	   group_id bigint(20) NULL,
	   date_created datetime NULL DEFAULT '0000-00-00 00:00:00',
	   title text NOT NULL,
	   privacy varchar(50) NULL DEFAULT 'public',
	   PRIMARY KEY  (id)
   ) {$charset_collate};";

	$sql[] = "CREATE TABLE {$bp_prefix}bp_media (
		id bigint(20) NOT NULL AUTO_INCREMENT ,
		blog_id bigint(20) NULL DEFAULT NULL,
		attachment_id bigint(20) NOT NULL ,
		user_id bigint(20) NOT NULL,
		title text,
		album_id bigint(20),
		group_id bigint(20),
		activity_id bigint(20) NULL DEFAULT NULL ,
		message_id bigint(20) NULL DEFAULT 0 ,
		privacy varchar(50) NULL DEFAULT 'public',
		type varchar(50) NULL DEFAULT 'photo',
		menu_order bigint(20) NULL DEFAULT 0 ,
		date_created datetime DEFAULT '0000-00-00 00:00:00',
		PRIMARY KEY  (id),
		KEY attachment_id (attachment_id),
		KEY user_id (user_id),
		KEY album_id (album_id),
		KEY media_author_id (album_id,user_id),
		KEY activity_id (activity_id),
		KEY blog_id (blog_id),
		KEY message_id (message_id),
		KEY group_id (group_id),
		KEY privacy (privacy),
		KEY type (type),
		KEY menu_order (menu_order),
		KEY date_created (date_created)
	) {$charset_collate};";

	dbDelta( $sql );
}

function bp_core_install_document() {
	$sql             = array();
	$charset_collate = $GLOBALS['wpdb']->get_charset_collate();
	$bp_prefix       = bp_core_get_table_prefix();

	$sql[] = "CREATE TABLE {$bp_prefix}bp_document_folder (
	   id bigint(20) NOT NULL AUTO_INCREMENT,
	   blog_id bigint(20) NULL DEFAULT NULL,
	   user_id bigint(20) NOT NULL,
	   group_id bigint(20) NULL,
	   parent bigint(20) NULL DEFAULT 0,
	   title text NOT NULL,
	   privacy varchar(50) NULL DEFAULT 'public',
	   date_created datetime NULL DEFAULT '0000-00-00 00:00:00',
	   date_modified datetime NULL DEFAULT '0000-00-00 00:00:00',
	   PRIMARY KEY  (id)
   ) {$charset_collate};";

	$sql[] = "CREATE TABLE {$bp_prefix}bp_document_folder_meta (
				id bigint(20) NOT NULL AUTO_INCREMENT,
				folder_id bigint(20) NOT NULL,
				meta_key varchar(255) DEFAULT NULL,
				meta_value longtext DEFAULT NULL,
				PRIMARY KEY  (id),
				KEY folder_id (folder_id),
				KEY meta_key (meta_key(191))
			) {$charset_collate};";

	$sql[] = "CREATE TABLE {$bp_prefix}bp_document (
		id bigint(20) NOT NULL AUTO_INCREMENT ,
		blog_id bigint(20) NULL DEFAULT NULL,
		attachment_id bigint(20) NOT NULL ,
		user_id bigint(20) NOT NULL,
		title text,
		folder_id bigint(20),
		group_id bigint(20),
		activity_id bigint(20) NULL DEFAULT NULL ,
		message_id bigint(20) NULL DEFAULT 0 ,
		privacy varchar(50) NULL DEFAULT 'public',
		menu_order bigint(20) NULL DEFAULT 0 ,
		date_created datetime DEFAULT '0000-00-00 00:00:00',
		date_modified datetime NULL DEFAULT '0000-00-00 00:00:00',
		PRIMARY KEY  (id),
		KEY attachment_id (attachment_id),
		KEY user_id (user_id),
		KEY folder_id (folder_id),
		KEY document_author_id (folder_id,user_id),
		KEY activity_id (activity_id),
		KEY blog_id (blog_id),
		KEY message_id (message_id),
		KEY group_id (group_id),
		KEY privacy (privacy),
		KEY menu_order (menu_order),
		KEY date_created (date_created),
		KEY date_modified (date_modified)
	) {$charset_collate};";

	$sql[] = "CREATE TABLE {$bp_prefix}bp_document_meta (
				id bigint(20) NOT NULL AUTO_INCREMENT,
				document_id bigint(20) NOT NULL,
				meta_key varchar(255) DEFAULT NULL,
				meta_value longtext DEFAULT NULL,
				PRIMARY KEY  (id),
				KEY document_id (document_id),
				KEY meta_key (meta_key(191))
			) {$charset_collate};";

	dbDelta( $sql );
}

/** Search *********************************************************/

/** Signups *******************************************************************/

/**
 * Install the signups table.
 *
 * @since BuddyPress 2.0.0
 *
 * @global wpdb $wpdb WordPress database abstraction object.
 */
function bp_core_install_signups() {
	global $wpdb;

	// Signups is not there and we need it so let's create it.
	require_once buddypress()->plugin_dir . '/bp-core/admin/bp-core-admin-schema.php';
	require_once ABSPATH . 'wp-admin/includes/upgrade.php';

	// Never use bp_core_get_table_prefix() for any global users tables.
	$wpdb->signups = $wpdb->base_prefix . 'signups';

	// Use WP's core CREATE TABLE query.
	$create_queries = wp_get_db_schema( 'ms_global' );
	if ( ! is_array( $create_queries ) ) {
		$create_queries = explode( ';', $create_queries );
		$create_queries = array_filter( $create_queries );
	}

	// Filter out all the queries except wp_signups.
	foreach ( $create_queries as $key => $query ) {
		if ( preg_match( '|CREATE TABLE ([^ ]*)|', $query, $matches ) ) {
			if ( trim( $matches[1], '`' ) !== $wpdb->signups ) {
				unset( $create_queries[ $key ] );
			}
		}
	}

	// Run WordPress's database upgrader.
	if ( ! empty( $create_queries ) ) {
		dbDelta( $create_queries );
	}
}

/**
 * Update the signups table, adding `signup_id` column and drop `domain` index.
 *
 * This is necessary because WordPress's `pre_schema_upgrade()` function wraps
 * table ALTER's in multisite checks, and other plugins may have installed their
 * own sign-ups table; Eg: Gravity Forms User Registration Add On.
 *
 * @since BuddyPress 2.0.1
 *
 * @see   pre_schema_upgrade()
 * @link  https://core.trac.wordpress.org/ticket/27855 WordPress Trac Ticket
 * @link  https://buddypress.trac.wordpress.org/ticket/5563 BuddyPress Trac Ticket
 *
 * @global wpdb $wpdb WordPress database abstraction object.
 */
function bp_core_upgrade_signups() {
	global $wpdb;

	// Bail if global tables should not be upgraded.
	if ( defined( 'DO_NOT_UPGRADE_GLOBAL_TABLES' ) ) {
		return;
	}

	// Never use bp_core_get_table_prefix() for any global users tables.
	$wpdb->signups = $wpdb->base_prefix . 'signups';

	// Attempt to alter the signups table.
	$wpdb->query( "ALTER TABLE {$wpdb->signups} ADD signup_id BIGINT(20) NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST" );
	$wpdb->query( "ALTER TABLE {$wpdb->signups} DROP INDEX domain" );
}

/**
 * Add default emails.
 *
 * @since BuddyPress 2.5.0
 */
function bp_core_install_emails() {
	$defaults = array(
		'post_status' => 'publish',
		'post_type'   => bp_get_email_post_type(),
	);

	$emails       = bp_email_get_schema();
	$descriptions = bp_email_get_type_schema( 'description' );

	// Add these emails to the database.
	foreach ( $emails as $id => $email ) {

		// Some emails are multisite-only.
		if ( ! is_multisite() && isset( $email['args'] ) && ! empty( $email['args']['multisite'] ) ) {
			continue;
		}

		$post_id = wp_insert_post( bp_parse_args( $email, $defaults, 'install_email_' . $id ) );
		if ( ! $post_id ) {
			continue;
		}

		$tt_ids = wp_set_object_terms( $post_id, $id, bp_get_email_tax_type() );
		foreach ( $tt_ids as $tt_id ) {
			$term = get_term_by( 'term_taxonomy_id', (int) $tt_id, bp_get_email_tax_type() );
			wp_update_term(
				(int) $term->term_id,
				bp_get_email_tax_type(),
				array(
					'description' => $descriptions[ $id ],
				)
			);
		}
	}

	bp_update_option( 'bp-emails-unsubscribe-salt', base64_encode( wp_generate_password( 64, true, true ) ) );

	/**
	 * Fires after BuddyPress adds the posts for its emails.
	 *
	 * @since BuddyPress 2.5.0
	 */
	do_action( 'bp_core_install_emails' );
}

/**
 * Add default bbp emails.
 *
 * @since BuddyBoss 1.0.0
 */
function bp_core_install_bbp_emails() {
	$defaults = array(
		'post_status' => 'publish',
		'post_type'   => bp_get_email_post_type(),
	);

	$emails       = bp_email_get_schema();
	$descriptions = bp_email_get_type_schema( 'description' );

	// Add these emails to the database.
	foreach ( $emails as $id => $email ) {

		if ( strpos( $id, 'bbp-new-forum-', 0 ) === false ) {
			continue;
		}

		// Some emails are multisite-only.
		if ( ! is_multisite() && isset( $email['args'] ) && ! empty( $email['args']['multisite'] ) ) {
			continue;
		}

		$post_id = wp_insert_post( bp_parse_args( $email, $defaults, 'install_email_' . $id ) );
		if ( ! $post_id ) {
			continue;
		}

		$tt_ids = wp_set_object_terms( $post_id, $id, bp_get_email_tax_type() );
		foreach ( $tt_ids as $tt_id ) {
			$term = get_term_by( 'term_taxonomy_id', (int) $tt_id, bp_get_email_tax_type() );
			wp_update_term(
				(int) $term->term_id,
				bp_get_email_tax_type(),
				array(
					'description' => $descriptions[ $id ],
				)
			);
		}
	}

	bp_update_option( 'bp-emails-unsubscribe-salt', base64_encode( wp_generate_password( 64, true, true ) ) );

	/**
	 * Fires after BuddyPress adds the posts for its bbp emails.
	 *
	 * @since BuddyBoss 1.0.0
	 */
	do_action( 'bp_core_install_bbp_emails' );
}

/**
 * Add default invites emails.
 *
 * @since BuddyBoss 1.0.0
 */
function bp_core_install_invites_email() {
	$defaults = array(
		'post_status' => 'publish',
		'post_type'   => bp_get_email_post_type(),
	);

	$emails       = bp_email_get_schema();
	$descriptions = bp_email_get_type_schema( 'description' );

	// Add these emails to the database.
	foreach ( $emails as $id => $email ) {

		if ( strpos( $id, 'invites-member-', 0 ) === false ) {
			continue;
		}

		// Some emails are multisite-only.
		if ( ! is_multisite() && isset( $email['args'] ) && ! empty( $email['args']['multisite'] ) ) {
			continue;
		}

		$post_id = wp_insert_post( bp_parse_args( $email, $defaults, 'install_email_' . $id ) );
		if ( ! $post_id ) {
			continue;
		}

		$tt_ids = wp_set_object_terms( $post_id, $id, bp_get_email_tax_type() );
		foreach ( $tt_ids as $tt_id ) {
			$term = get_term_by( 'term_taxonomy_id', (int) $tt_id, bp_get_email_tax_type() );
			wp_update_term(
				(int) $term->term_id,
				bp_get_email_tax_type(),
				array(
					'description' => $descriptions[ $id ],
				)
			);
		}
	}

	/**
	 * Fires after BuddyPress adds the posts for its bbp emails.
	 *
	 * @since BuddyBoss 1.0.0
	 */
	do_action( 'bp_core_install_invites_email' );
}

/**
 * Add default invites emails.
 *
 * @since BuddyBoss 1.2.9
 */
function bp_core_install_group_message_email() {
	$defaults = array(
		'post_status' => 'publish',
		'post_type'   => bp_get_email_post_type(),
	);

	$emails       = bp_email_get_schema();
	$descriptions = bp_email_get_type_schema( 'description' );

	// Add these emails to the database.
	foreach ( $emails as $id => $email ) {

		if ( strpos( $id, 'group-message-', 0 ) === false ) {
			continue;
		}

		// Some emails are multisite-only.
		if ( ! is_multisite() && isset( $email['args'] ) && ! empty( $email['args']['multisite'] ) ) {
			continue;
		}

		$post_id = wp_insert_post( bp_parse_args( $email, $defaults, 'install_email_' . $id ) );
		if ( ! $post_id ) {
			continue;
		}

		$tt_ids = wp_set_object_terms( $post_id, $id, bp_get_email_tax_type() );
		foreach ( $tt_ids as $tt_id ) {
			$term = get_term_by( 'term_taxonomy_id', (int) $tt_id, bp_get_email_tax_type() );
			wp_update_term(
				(int) $term->term_id,
				bp_get_email_tax_type(),
				array(
					'description' => $descriptions[ $id ],
				)
			);
		}
	}

	/**
	 * Fires after BuddyPress adds the posts for its bbp emails.
	 *
	 * @since BuddyBoss 1.0.0
	 */
	do_action( 'bp_core_install_group_message_email' );
}

/**
 * Install database tables for the Invitations API
 *
 * @since BuddyBoss 1.3.5
 *
 * @since BuddyPress 5.0.0
 *
 * @uses  bp_core_set_charset()
 * @uses  bp_core_get_table_prefix()
 * @uses  dbDelta()
 */
function bp_core_install_invitations() {
	$sql             = array();
	$charset_collate = $GLOBALS['wpdb']->get_charset_collate();
	$bp_prefix       = bp_core_get_table_prefix();
	$sql[]           = "CREATE TABLE {$bp_prefix}bp_invitations (
		id bigint(20) NOT NULL AUTO_INCREMENT,
		user_id bigint(20) NOT NULL,
		inviter_id bigint(20) NOT NULL,
		invitee_email varchar(100) DEFAULT NULL,
		class varchar(120) NOT NULL,
		item_id bigint(20) NOT NULL,
		secondary_item_id bigint(20) DEFAULT NULL,
		type varchar(12) NOT NULL DEFAULT 'invite',
		content longtext DEFAULT '',
		date_modified datetime NOT NULL,
		invite_sent tinyint(1) NOT NULL DEFAULT '0',
		accepted tinyint(1) NOT NULL DEFAULT '0',
		PRIMARY KEY  (id),
		KEY user_id (user_id),
		KEY inviter_id (inviter_id),
		KEY invitee_email (invitee_email),
		KEY class (class),
		KEY item_id (item_id),
		KEY secondary_item_id (secondary_item_id),
		KEY type (type),
		KEY invite_sent (invite_sent),
		KEY accepted (accepted)
		) {$charset_collate};";

	$sql[] = "CREATE TABLE {$bp_prefix}bp_invitations_invitemeta (
		id bigint(20) NOT NULL AUTO_INCREMENT,
		invite_id bigint(20) NOT NULL,
		meta_key varchar(255) DEFAULT NULL,
		meta_value longtext DEFAULT NULL,
		PRIMARY KEY  (id),
		KEY invite_id (invite_id),
		KEY meta_key (meta_key(191))
	) {$charset_collate};";

	dbDelta( $sql );

	/**
	 * Fires after BuddyPress adds the invitations table.
	 *
	 * @since BuddyPress 5.0.0
	 */
	do_action( 'bp_core_install_invitations' );
}

/** Suspend *********************************************************/
/**
 * Install database tables for the Suspend
 *
 * @since BuddyBoss 1.5.6
 *
 * @uses  bp_core_set_charset()
 * @uses  bp_core_get_table_prefix()
 * @uses  dbDelta()
 */
function bp_core_install_suspend() {
	$sql             = array();
	$charset_collate = $GLOBALS['wpdb']->get_charset_collate();
	$bp_prefix       = bp_core_get_table_prefix();

	$sql[] = "CREATE TABLE {$bp_prefix}bp_suspend (
	   id bigint(20) NOT NULL AUTO_INCREMENT,
	   item_id bigint(20) NOT NULL,
	   item_type varchar(20) NOT NULL,
	   hide_sitewide tinyint(1) NOT NULL,
	   hide_parent tinyint(1) NOT NULL,
	   user_suspended tinyint(1) NOT NULL,
	   reported tinyint(1) NOT NULL,
	   user_report tinyint DEFAULT '0',
	   last_updated datetime NULL DEFAULT '0000-00-00 00:00:00',
	   blog_id bigint(20) NOT NULL,
	   PRIMARY KEY  (id),
	   KEY suspend_item_id (item_id,item_type,blog_id),
	   KEY suspend_item (item_id,item_type),
	   KEY item_id (item_id)
    ) {$charset_collate};";

	$sql[] = "CREATE TABLE {$bp_prefix}bp_suspend_details (
	   id bigint(20) NOT NULL AUTO_INCREMENT,
	   suspend_id bigint(20) NOT NULL,
	   user_id bigint(20) NOT NULL,
	   PRIMARY KEY  (id),
	   KEY suspend_details_id (suspend_id,user_id)
    ) {$charset_collate};";

	dbDelta( $sql );
}

/** Moderation *********************************************************/
/**
 * Install database tables for the Moderation
 *
 * @since BuddyBoss 1.5.6
 *
 * @uses  bp_core_set_charset()
 * @uses  bp_core_get_table_prefix()
 * @uses  dbDelta()
 */
function bp_core_install_moderation() {
	$sql             = array();
	$charset_collate = $GLOBALS['wpdb']->get_charset_collate();
	$bp_prefix       = bp_core_get_table_prefix();

	$sql[] = "CREATE TABLE {$bp_prefix}bp_moderation (
	   id bigint(20) NOT NULL AUTO_INCREMENT,
	   moderation_id bigint(20) NOT NULL,
	   user_id bigint(20) NOT NULL,
	   content longtext NOT NULL,
	   date_created datetime NULL DEFAULT '0000-00-00 00:00:00',
	   category_id bigint(20) NOT NULL,
	   user_report tinyint DEFAULT '0',
	   PRIMARY KEY  (id),
	   KEY moderation_report_id (moderation_id,user_id),
	   KEY user_id (user_id)
   	) {$charset_collate};";

	$sql[] = "CREATE TABLE {$bp_prefix}bp_moderation_meta (
		id bigint(20) NOT NULL AUTO_INCREMENT,
		moderation_id bigint(20) NOT NULL,
		meta_key varchar(255) DEFAULT NULL,
		meta_value longtext DEFAULT NULL,
		PRIMARY KEY  (id),
		KEY moderation_id (moderation_id),
		KEY meta_key (meta_key(191))
	) {$charset_collate};";

	dbDelta( $sql );
}

/**
 * Add moderation emails.
 *
 * @since BuddyBoss 1.5.6
 */
function bp_core_install_moderation_emails() {

	$defaults = array(
		'post_status' => 'publish',
		'post_type'   => bp_get_email_post_type(),
	);

	$emails       = bp_email_get_schema();
	$descriptions = bp_email_get_type_schema( 'description' );

	// Add these emails to the database.
	foreach ( $emails as $id => $email ) {

		if ( strpos( $id, 'content-moderation-', 0 ) === false && strpos( $id, 'user-moderation-', 0 ) === false ) {
			continue;
		}

		// Some emails are multisite-only.
		if ( ! is_multisite() && isset( $email['args'] ) && ! empty( $email['args']['multisite'] ) ) {
			continue;
		}

		$post_id = wp_insert_post( bp_parse_args( $email, $defaults, 'install_email_' . $id ) );
		if ( ! $post_id ) {
			continue;
		}

		$tt_ids = wp_set_object_terms( $post_id, $id, bp_get_email_tax_type() );
		foreach ( $tt_ids as $tt_id ) {
			$term = get_term_by( 'term_taxonomy_id', (int) $tt_id, bp_get_email_tax_type() );
			wp_update_term(
				(int) $term->term_id,
				bp_get_email_tax_type(),
				array(
					'description' => $descriptions[ $id ],
				)
			);
		}
	}

	/**
	 * Fires after BuddyPress adds the posts for its bbp emails.
	 *
	 * @since BuddyBoss 1.5.6
	 */
	do_action( 'bp_core_install_moderation_emails' );
}

/** Subscription *********************************************************/
/**
 * Install database tables for the subscriptions
 *
 * @since BuddyBoss 2.2.6
 *
 * @uses  get_charset_collate()
 * @uses  bp_core_get_table_prefix()
 * @uses  dbDelta()
 */
function bb_core_install_subscription() {
	$sql             = array();
	$charset_collate = $GLOBALS['wpdb']->get_charset_collate();
	$bp_prefix       = bp_core_get_table_prefix();

	$sql[] = "CREATE TABLE {$bp_prefix}bb_notifications_subscriptions (
	   id bigint(20) NOT NULL AUTO_INCREMENT,
	   blog_id bigint(20) NOT NULL,
	   user_id bigint(20) NOT NULL,
	   type varchar(255) NOT NULL,
	   item_id bigint(20) NOT NULL,
	   secondary_item_id bigint(20) NOT NULL,
	   status tinyint(1) NOT NULL DEFAULT '1',
	   date_recorded datetime NULL DEFAULT '0000-00-00 00:00:00',
	   PRIMARY KEY  (id),
	   KEY blog_id (blog_id),
	   KEY user_id (user_id),
	   KEY type (type),
	   KEY item_id (item_id),
	   KEY secondary_item_id (secondary_item_id),
	   KEY status (status),
	   KEY date_recorded (date_recorded)
   	) {$charset_collate};";

	dbDelta( $sql );
}
