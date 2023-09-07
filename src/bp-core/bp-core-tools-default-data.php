<?php

/**
 * Save dummy data value when dummy data add button is pressed
 *
 * @BuddyBoss 1.0.0
 */
function bp_admin_tools_default_data_save() {

	if ( ! empty( $_POST['bp-admin-clear'] ) ) {
		bp_dd_clear_db();
		echo '<div id="message" class="updated fade"><p>' . __( 'Everything was deleted.', 'buddyboss' ) . '</p></div>';
	}

	if ( isset( $_POST['bp-admin-submit'] ) ) {
		// Cound what we have just imported.
		$imported = array();

		// Check nonce before we do anything.
		check_admin_referer( 'bp-admin-tools-default-data' );

		// Import users
		if ( isset( $_POST['bp']['import-users'] ) && ! bp_dd_is_imported( 'users', 'users' ) ) {
			bp_dd_delete_dummy_members_related_data();
			bp_delete_option( 'bp_dd_import_users' );
			$users             = bp_dd_import_users();
			$imported['users'] = sprintf( __( '%s new members', 'buddyboss' ), bp_core_number_format( count( $users ) ) );
			bp_dd_update_import( 'users', 'users' );
		}

		if ( isset( $_POST['bp']['import-profile'] ) && ! bp_dd_is_imported( 'users', 'xprofile' ) ) {
			$profile             = bp_dd_import_users_profile();
			$imported['profile'] = sprintf( __( '%s profile field entries', 'buddyboss' ), bp_core_number_format( $profile ) );
			bp_dd_update_import( 'users', 'xprofile' );
		}

		if ( isset( $_POST['bp']['import-friends'] ) && ! bp_dd_is_imported( 'users', 'friends' ) ) {
			$friends             = bp_dd_import_users_friends();
			$imported['friends'] = sprintf( __( '%s member connections', 'buddyboss' ), bp_core_number_format( $friends ) );
			bp_dd_update_import( 'users', 'friends' );
		}

		if ( isset( $_POST['bp']['import-messages'] ) && ! bp_dd_is_imported( 'users', 'messages' ) ) {
			$messages             = bp_dd_import_users_messages();
			$imported['messages'] = sprintf( __( '%s private messages', 'buddyboss' ), bp_core_number_format( count( $messages ) ) );
			bp_dd_update_import( 'users', 'messages' );
		}

		if ( isset( $_POST['bp']['import-activity'] ) && ! bp_dd_is_imported( 'users', 'activity' ) ) {
			$activity             = bp_dd_import_users_activity();
			$imported['activity'] = sprintf( __( '%s personal activity items', 'buddyboss' ), bp_core_number_format( $activity ) );
			bp_dd_update_import( 'users', 'activity' );
		}

		// Import groups
		if ( isset( $_POST['bp']['import-groups'] ) && ! bp_dd_is_imported( 'groups', 'groups' ) ) {
			$groups             = bp_dd_import_groups();
			$imported['groups'] = sprintf( __( '%s new social groups', 'buddyboss' ), bp_core_number_format( count( $groups ) ) );
			bp_dd_update_import( 'groups', 'groups' );
		}
		if ( isset( $_POST['bp']['import-g-members'] ) && ! bp_dd_is_imported( 'groups', 'members' ) ) {
			$g_members             = bp_dd_import_groups_members();
			$imported['g_members'] = sprintf( __( '%s group members (1 user can be in several groups)', 'buddyboss' ), bp_core_number_format( count( $g_members ) ) );
			bp_dd_update_import( 'groups', 'members' );
		}

		if ( isset( $_POST['bp']['import-g-activity'] ) && ! bp_dd_is_imported( 'groups', 'activity' ) ) {
			$g_activity             = bp_dd_import_groups_activity();
			$imported['g_activity'] = sprintf( __( '%s group activity items', 'buddyboss' ), bp_core_number_format( $g_activity ) );
			bp_dd_update_import( 'groups', 'activity' );
		}

		if ( isset( $_POST['bp']['import-forums'] ) && ! bp_dd_is_imported( 'forums', 'forums' ) ) {
			$forums             = bp_dd_import_forums();
			$imported['forums'] = sprintf( __( '%s forums activity items', 'buddyboss' ), count( $forums ) );
			bp_dd_update_import( 'forums', 'forums' );
		}

		if ( isset( $_POST['bp']['import-f-topics'] ) && ! bp_dd_is_imported( 'forums', 'topics' ) ) {
			$topics               = bp_dd_import_forums_topics();
			$imported['g_topics'] = sprintf( __( '%s discussion activity items', 'buddyboss' ), count( $topics ) );
			bp_dd_update_import( 'forums', 'topics' );
		}

		if ( isset( $_POST['bp']['import-f-replies'] ) && ! bp_dd_is_imported( 'forums', 'replies' ) ) {
			$topics                = bp_dd_import_forums_topics_replies();
			$imported['g_replies'] = sprintf( __( '%s reply activity items', 'buddyboss' ), count( $topics ) );
			bp_dd_update_import( 'forums', 'replies' );
		}

		if ( isset( $_POST['bp']['import-g-forums'] ) && ! bp_dd_is_imported( 'groups', 'forums' ) ) {
			$groupsforums         = bp_dd_import_forums_in_groups();
			$imported['g_forums'] = sprintf( __( 'In %s group forums, discussions and replies were added', 'buddyboss' ), count( $groupsforums ) );
			bp_dd_update_import( 'groups', 'forums' );

		}
		?>

		<div id="message" class="updated fade">
			<p>
				<?php
				_e( 'Data was successfully imported', 'buddyboss' );
				if ( count( $imported ) > 0 ) {
					echo ':<ul class="results"><li>';
					echo implode( '</li><li>', $imported );
					echo '</li></ul>';
				}
				?>
			</p>
		</div>

		<?php
	}

	if ( bp_is_active( 'forums' ) ) {

		$forum_ids = bp_get_option( 'bp_dd_imported_forum_ids', array() );
		if ( ! empty( $forum_ids ) ) {
			foreach ( $forum_ids as $forum_id ) {
				bbp_update_forum_topic_count( $forum_id );
				bbp_update_forum_reply_count( $forum_id );
			}
		}

		$topic_ids = bp_get_option( 'bp_dd_imported_topic_ids', array() );
		if ( ! empty( $topic_ids ) ) {
			foreach ( $topic_ids as $topic_id ) {
				bbp_update_topic_reply_count( $topic_id );
			}
		}
	}
}

/**
 * Get plugin admin area root page: settings.php for WPMS and tool.php for WP.
 *
 * @since BuddyBoss 1.0.0
 *
 * @return string
 */
function bp_dd_get_root_admin_page() {
	return is_multisite() ? 'settings.php' : 'tools.php';
}

/**
 * Delete all imported information.
 *
 * @since BuddyBoss 1.0.0
 */
function bp_dd_clear_db() {

	// delete all BB groups
	bp_dd_delete_dummy_groups();

	// delete all the forums
	bp_dd_delete_dummy_forum();

	// delete all the topic
	bp_dd_delete_dummy_topic();

	// Delete reply
	bp_dd_delete_dummy_reply();

	bp_dd_delete_dummy_members_related_data();

	bp_dd_delete_import_records();
}

/**
 * Delete all the members related data
 *
 * @since BuddyBoss 1.0.0
 */
function bp_dd_delete_dummy_members_related_data() {
	// delete all the dummy members
	bp_dd_delete_dummy_members();

	// delete all the dummy xprofile fields
	bp_dd_delete_dummy_xprofile();
}

/**
 * Delete all the dummy members
 *
 * @since BuddyBoss 1.0.0
 */
function bp_dd_delete_dummy_members() {
	/*
	* Users and all their data.
	*/
	$users = bp_get_option( 'bp_dd_imported_user_ids' );
	if ( ! empty( $users ) ) {
		$users_str = implode( ',', (array) $users );

		foreach ( (array) $users as $user_id ) {
			bp_core_delete_account( $user_id );
		}
	}
}

/**
 * Delete all the forum dummy reply
 *
 * @since BuddyBoss 1.0.0
 */
function bp_dd_delete_dummy_reply() {
	/**
	 * Delete reply
	 */
	$forums = bp_get_option( 'bp_dd_imported_reply_ids' );
	if ( ! empty( $forums ) ) {
		foreach ( (array) $forums as $forum_id ) {
			wp_delete_post( $forum_id );
		}
	}
}

/**
 * Delete all the forum dummy topic
 *
 * @since BuddyBoss 1.0.0
 */
function bp_dd_delete_dummy_topic() {
	/**
	 * Delete topics
	 */
	$forums = bp_get_option( 'bp_dd_imported_topic_ids' );
	if ( ! empty( $forums ) ) {
		foreach ( (array) $forums as $forum_id ) {
			wp_delete_post( $forum_id );
		}
	}
}


/**
 * Delete all the forum dummy forum
 *
 * @since BuddyBoss 1.0.0
 */
function bp_dd_delete_dummy_forum() {

	/**
	 * Delete Forums
	 */
	if ( bp_is_active( 'forums' ) ) {
		$forums = bp_get_option( 'bp_dd_imported_forum_ids' );
		if ( ! empty( $forums ) ) {
			foreach ( (array) $forums as $forum_id ) {
				wp_delete_post( $forum_id );
				bbp_delete_forum( $forum_id );
			}
		}
	}
}

/**
 * Delete all the forum dummy groups
 *
 * @since BuddyBoss 1.0.0
 */
function bp_dd_delete_dummy_groups() {

	/**
	 * Groups
	 */
	$groups = bp_get_option( 'bp_dd_imported_group_ids' );
	if ( ! empty( $groups ) ) {
		foreach ( (array) $groups as $group_id ) {
			groups_delete_group( $group_id );
		}
	}
}

/**
 * Delete all the BB dummy xprofile fields
 *
 * @since BuddyBoss 1.0.0
 */
function bp_dd_delete_dummy_xprofile() {
	/*
	 * Deleting xProfile groups and fields.
	 */
	$xprofile_ids = bp_get_option( 'bp_dd_imported_user_xprofile_ids' );
	foreach ( (array) $xprofile_ids as $xprofile_id ) {
		xprofile_delete_field_group( $xprofile_id );
	}
}

/**
 * Fix the date issue, when all joined_group events took place at the same time.
 *
 * @since BuddyBoss 1.0.0
 *
 * @param array $args Arguments that are passed to bp_activity_add().
 *
 * @return array
 */
function bp_dd_groups_join_group_date_fix( $args ) {
	if (
		$args['type'] === 'joined_group' &&
		$args['component'] === 'groups'
	) {
		$args['recorded_time'] = bp_dd_get_random_date( 25, 1 );
	}

	return $args;
}

/**
 * Fix the date issue, when all member connections are done at the same time.
 *
 * @since BuddyBoss 1.0.0
 *
 * @param string $current_time Default BuddyBoss current timestamp.
 *
 * @return string
 */
function bp_dd_friends_add_friend_date_fix( $current_time ) {
	return strtotime( bp_dd_get_random_date( 43 ) );
}

/**
 * Get the array (or a string) of group IDs.
 *
 * @since BuddyBoss 1.0.0
 *
 * @param int    $count If you need all, use 0.
 * @param string $output What to return: 'array' or 'string'. If string - comma separated.
 *
 * @return array|string Default is array.
 */
function bp_dd_get_random_groups_ids( $count = 1, $output = 'array' ) {
	$groups_arr = (array) bp_get_option( 'bp_dd_imported_group_ids' );

	if ( ! empty( $groups_arr ) ) {
		$total_groups = count( $groups_arr );
		if ( $count <= 0 || $count > $total_groups ) {
			$count = $total_groups;
		}

		// Get random groups.
		$random_keys = (array) array_rand( $groups_arr, $count );
		$groups      = array();
		foreach ( $groups_arr as $key => $value ) {
			if ( in_array( $key, $random_keys ) ) {
				$groups[] = $value;
			}
		}
	} else {
		global $wpdb;
		$bp = buddypress();

		$limit = '';
		if ( $count > 0 ) {
			$limit = 'LIMIT ' . (int) $count;
		}

		$groups = $wpdb->get_col( "SELECT id FROM {$bp->groups->table_name} ORDER BY rand() {$limit}" );
	}

	/**
	 * Convert to integers, because get_col() returns array of strings.
	 */
	$groups = array_map( 'intval', $groups );

	if ( $output === 'string' ) {
		return implode( ',', $groups );
	}

	return $groups;
}

/**
 * Get the array (or a string) of forums group IDs.
 *
 * @since BuddyBoss 1.0.0
 *
 * @param int    $count If you need all, use 0.
 * @param string $output What to return: 'array' or 'string'. If string - comma separated.
 *
 * @return array|string Default is array.
 */
function bp_dd_get_forums_enable_groups_ids( $count, $output = 'array' ) {
	$groups_arr = (array) bp_get_option( 'bp_dd_imported_forums_group_ids' );
	if ( ! empty( $groups_arr ) ) {
		$total_groups = count( $groups_arr );
		if ( $count <= 0 || $count > $total_groups ) {
			$count = $total_groups;
		}

		// Get random groups.
		$random_keys = (array) array_rand( $groups_arr, $count );
		$groups      = array();
		foreach ( $groups_arr as $key => $value ) {
			if ( in_array( $key, $random_keys ) ) {
				$groups[] = $value;
			}
		}
	}

	/**
	 * Convert to integers, because get_col() returns array of strings.
	 */
	$groups = array_map( 'intval', $groups );

	if ( $output === 'string' ) {
		return implode( ',', $groups );
	}

	return $groups;
}


/**
 * Get the array (or a string) of forum IDs.
 *
 * @since BuddyBoss 1.0.0
 *
 * @param int    $count If you need all, use 0.
 * @param string $output What to return: 'array' or 'string'. If string - comma separated.
 *
 * @return array|string Default is array.
 */
function bp_dd_get_random_forums_ids( $count = 1, $output = 'array' ) {
	$forums_arr = (array) bp_get_option( 'bp_dd_imported_forum_ids' );

	if ( ! empty( $forums_arr ) ) {
		$total_forums = count( $forums_arr );
		if ( $count <= 0 || $count > $total_forums ) {
			$count = $total_forums;
		}

		// Get random groups.
		$random_keys = (array) array_rand( $forums_arr, $count );
		$forums      = array();
		foreach ( $forums_arr as $key => $value ) {
			if ( in_array( $key, $random_keys ) ) {
				$forums[] = $value;
			}
		}
	}

	/*
	 * Convert to integers, because get_col() returns array of strings.
	 */
	$forums = array_map( 'intval', $forums );

	if ( $output === 'string' ) {
		return implode( ',', $forums );
	}

	return $forums;
}

/**
 * Get the array (or a string) of topics IDs.
 *
 * @since BuddyBoss 1.0.0
 *
 * @param int    $count If you need all, use 0.
 * @param string $output What to return: 'array' or 'string'. If string - comma separated.
 *
 * @return array|string Default is array.
 */
function bp_dd_get_random_topics_ids( $count = 1, $output = 'array' ) {
	$topics_arr = (array) bp_get_option( 'bp_dd_imported_topic_ids' );

	if ( ! empty( $topics_arr ) ) {
		$total_topics = count( $topics_arr );
		if ( $count <= 0 || $count > $total_topics ) {
			$count = $total_topics;
		}

		// Get random groups.
		$random_keys = (array) array_rand( $topics_arr, $count );
		$topics      = array();
		foreach ( $topics_arr as $key => $value ) {
			if ( in_array( $key, $random_keys ) ) {
				$topics[] = $value;
			}
		}
	}

	/*
	 * Convert to integers, because get_col() returns array of strings.
	 */
	$topics = array_map( 'intval', $topics );

	if ( $output === 'string' ) {
		return implode( ',', $topics );
	}

	return $topics;
}

/**
 * Get the array (or a string) of user IDs.
 *
 * @since BuddyBoss 1.0.0
 *
 * @param int    $count If you need all, use 0.
 * @param string $output What to return: 'array' or 'string'. If string - comma separated.
 *
 * @return array|string Default is array.
 */
function bp_dd_get_random_users_ids( $count = 1, $output = 'array' ) {
	$users_arr = (array) bp_get_option( 'bp_dd_imported_user_ids' );

	if ( ! empty( $users_arr ) ) {
		$total_members = count( $users_arr );
		if ( $count <= 0 || $count > $total_members ) {
			$count = $total_members;
		}

		// Get random users.
		$random_keys = (array) array_rand( $users_arr, $count );
		$users       = array();
		foreach ( $users_arr as $key => $value ) {
			if ( in_array( $key, $random_keys ) ) {
				$users[] = $value;
			}
		}
	} else {
		// Get by default (if no users were imported) all currently registered users.
		$users = get_users(
			array(
				'fields' => 'ID',
			)
		);
	}

	/*
	 * Convert to integers, because get_col() and get_users() return array of strings.
	 */
	$users = array_map( 'intval', $users );

	if ( $output === 'string' ) {
		return implode( ',', $users );
	}

	return $users;
}

/**
 * Get a random date between some days in history.
 * If [30;5] is specified - that means a random date between 30 and 5 days from now.
 *
 * @since BuddyBoss 1.0.0
 *
 * @param int $days_from
 * @param int $days_to
 *
 * @return string
 */
function bp_dd_get_random_date( $days_from = 30, $days_to = 0 ) {
	// $days_from should always be less than $days_to
	if ( $days_to > $days_from ) {
		$days_to = $days_from - 1;
	}

	$date_from = new DateTime( 'now - ' . $days_from . ' days' );
	$date_to   = new DateTime( 'now - ' . $days_to . ' days' );

	return date_i18n( 'Y-m-d H:i:s', mt_rand( $date_from->getTimestamp(), $date_to->getTimestamp() ) );
}

/**
 * Get the current timestamp, using current blog time settings.
 *
 * @since BuddyBoss 1.0.0
 *
 * @return int
 */
function bp_dd_get_time() {
	return (int) current_time( 'timestamp' );
}


/**
 * Check whether something was imported or not.
 *
 * @since BuddyBoss 1.0.0
 *
 * @param string $group Possible values: users, groups
 * @param string $import What exactly was imported
 *
 * @return bool
 */
function bp_dd_is_imported( $group, $import ) {
	$group  = sanitize_key( $group );
	$import = sanitize_key( $import );

	if ( ! in_array( $group, array( 'users', 'groups', 'forums' ) ) ) {
		return false;
	}

	return array_key_exists( $import, (array) bp_get_option( 'bp_dd_import_' . $group ) );
}

/**
 * Display a disabled attribute for inputs of the particular value was already imported.
 *
 * @since BuddyBoss 1.0.0
 *
 * @param string $group
 * @param string $import
 */
function bp_dd_imported_disabled( $group, $import ) {
	$group  = sanitize_key( $group );
	$import = sanitize_key( $import );

	if ( ! in_array( $group, array( 'users', 'groups', 'forums' ) ) ) {
		echo '';
	}

	echo bp_dd_is_imported( $group, $import ) ? 'disabled="disabled" checked="checked"' : '';
}

/**
 * Save when the importing was done.
 *
 * @since BuddyBoss 1.0.0
 *
 * @param string $group
 * @param string $import
 *
 * @return bool
 */
function bp_dd_update_import( $group, $import ) {
	$group  = sanitize_key( $group );
	$import = (string) sanitize_key( $import );

	if ( ! in_array( $group, array( 'users', 'groups', 'forums' ) ) ) {
		return false;
	}

	$values            = bp_get_option( 'bp_dd_import_' . $group, array() );
	$values[ $import ] = bp_dd_get_time();

	return bp_update_option( 'bp_dd_import_' . $group, $values );
}

/**
 * Remove all imported ids and the indication, that importing was done.
 *
 * @since BuddyBoss 1.0.0
 */
function bp_dd_delete_import_records() {
	bp_delete_option( 'bp_dd_import_users' );
	bp_delete_option( 'bp_dd_import_groups' );
	bp_delete_option( 'bp_dd_import_forums' );

	bp_delete_option( 'bp_dd_imported_user_ids' );
	bp_delete_option( 'bp_dd_imported_group_ids' );
	bp_delete_option( 'bp_dd_imported_forums_group_ids' );

	bp_delete_option( 'bp_dd_imported_forum_ids' );
	bp_delete_option( 'bp_dd_imported_topic_ids' );
	bp_delete_option( 'bp_dd_imported_reply_ids' );

	bp_delete_option( 'bp_dd_imported_user_messages_ids' );
	bp_delete_option( 'bp_dd_imported_user_xprofile_ids' );
}

/**
 *  Importer engine - USERS
 *
 * @since BuddyBoss 1.0.0
 */
function bp_dd_import_users() {
	$users = array();

	$users_data = require BP_DEFAULT_DATA_DIR . 'data/users.php';

	$image_url         = BP_DEFAULT_DATA_URL . 'data/images/members/';
	$cover_image_url   = $image_url . 'cover/';
	$avatars_image_url = $image_url . 'avatars/';

	foreach ( $users_data as $user ) {
		$user_id = wp_insert_user(
			array(
				'user_login'      => $user['login'],
				'display_name'    => $user['display_name'],
				'first_name'      => $user['first_name'],
				'last_name'       => $user['last_name'],
				'user_email'      => $user['email'],
				'user_registered' => bp_dd_get_random_date( 45, 1 ),
				'user_pass'       => wp_generate_password( 8, false ),
			)
		);

		if ( bp_is_active( 'xprofile' ) ) {
			xprofile_set_field_data( 1, $user_id, $user['display_name'] );
		}
		// $name = explode( ' ', $user['display_name'] );
		// update_user_meta( $user_id, 'first_name', $name[0] );
		// update_user_meta( $user_id, 'last_name', isset( $name[1] ) ? $name[1] : '' );

		bp_update_user_last_activity( $user_id, bp_dd_get_random_date( 5 ) );

		bp_update_user_meta( $user_id, 'notification_messages_new_message', 'no' );
		bp_update_user_meta( $user_id, 'notification_friends_friendship_request', 'no' );
		bp_update_user_meta( $user_id, 'notification_friends_friendship_accepted', 'no' );

		if ( ! empty( $user['avatars'] ) ) {
			bp_update_user_meta( $user_id, 'avatars', $avatars_image_url . $user['avatars'] );
		}

		if ( ! empty( $user['cover-image'] ) ) {
			bp_update_user_meta( $user_id, 'cover-image', $cover_image_url . $user['cover-image'] );
		}

		$users[] = $user_id;
	}

	if ( ! empty( $users ) ) {
		/** @noinspection PhpParamsInspection */
		bp_update_option( 'bp_dd_imported_user_ids', $users );
	}

	return $users;
}

/**
 * Import extended profile fields.
 *
 * @since BuddyBoss 1.0.0
 *
 * @return int
 */
function bp_dd_import_users_profile() {
	$count = 0;

	if ( ! bp_is_active( 'xprofile' ) ) {
		return $count;
	}

	$data = array();

	$xprofile_structure = require_once BP_DEFAULT_DATA_DIR . 'data/xprofile_structure.php';

	// Firstly, import profile groups.
	foreach ( $xprofile_structure as $group_type => $group_data ) {
		$group_id = xprofile_insert_field_group(
			array(
				'name'        => $group_data['name'],
				'description' => $group_data['desc'],
			)
		);
		$groups[] = $group_id;

		// Then import fields.
		foreach ( $group_data['fields'] as $field_type => $field_data ) {
			$field_id = xprofile_insert_field(
				array(
					'field_group_id' => $group_id,
					'parent_id'      => 0,
					'type'           => $field_type,
					'name'           => $field_data['name'],
					'description'    => $field_data['desc'],
					'is_required'    => $field_data['required'],
					'order_by'       => 'custom',
				)
			);

			if ( $field_id ) {
				bp_xprofile_update_field_meta( $field_id, 'default_visibility', $field_data['default-visibility'] );

				bp_xprofile_update_field_meta( $field_id, 'allow_custom_visibility', $field_data['allow-custom-visibility'] );

				$data[ $field_id ]['type'] = $field_type;

				// finally import options
				if ( ! empty( $field_data['options'] ) ) {
					foreach ( $field_data['options'] as $option ) {
						$option_id = xprofile_insert_field(
							array(
								'field_group_id'    => $group_id,
								'parent_id'         => $field_id,
								'type'              => 'option',
								'name'              => $option['name'],
								'can_delete'        => true,
								'is_default_option' => $option['is_default_option'],
								'option_order'      => $option['option_order'],
							)
						);

						$data[ $field_id ]['options'][ $option_id ] = $option['name'];
					}
				} else {
					$data[ $field_id ]['options'] = array();
				}
			}
		}
	}

	$xprofile_data = require_once BP_DEFAULT_DATA_DIR . 'data/xprofile_data.php';
	$users         = bp_dd_get_random_users_ids( 0 );

	// Now import profile fields data for all fields for each user.
	foreach ( $users as $user_id ) {
		foreach ( $data as $field_id => $field_data ) {
			switch ( $field_data['type'] ) {
				case 'datebox':
				case 'textarea':
				case 'number':
				case 'textbox':
				case 'url':
				case 'selectbox':
				case 'radio':
					if ( xprofile_set_field_data( $field_id, $user_id, $xprofile_data[ $field_data['type'] ][ array_rand( $xprofile_data[ $field_data['type'] ] ) ] ) ) {
						$count ++;
					}
					break;

				case 'checkbox':
				case 'multiselectbox':
					if ( xprofile_set_field_data( $field_id, $user_id, explode( ',', $xprofile_data[ $field_data['type'] ][ array_rand( $xprofile_data[ $field_data['type'] ] ) ] ) ) ) {
						$count ++;
					}
					break;
			}
		}
	}

	if ( ! empty( $groups ) ) {
		/** @noinspection PhpParamsInspection */
		bp_update_option( 'bp_dd_imported_user_xprofile_ids', $groups );
	}

	return $count;
}

/**
 * Import private messages between users.
 *
 * @since BuddyBoss 1.0.0
 *
 * @return array
 */
function bp_dd_import_users_messages() {
	$messages            = array();
	$random_thread_reply = array();

	if ( ! bp_is_active( 'messages' ) ) {
		return $messages;
	}

	/** @var $messages_subjects array */
	/** @var $messages_content array */
	require BP_DEFAULT_DATA_DIR . 'data/messages.php';

	$users_data = require BP_DEFAULT_DATA_DIR . 'data/users.php';
	$users_data = wp_list_pluck( $users_data, 'login', 'refer_id' );
	foreach ( $messages as $message ) {
		$user_object = get_user_by( 'login', $users_data[ $message['sender_refer_id'] ] );

		$user_id            = empty( $user_object->ID ) ? bp_dd_get_random_users_ids( 1, 'string' ) : $user_object->ID;
		$recipients_numbers = empty( $message['recipients'] ) ? 1 : $message['recipients'];
		$recipients         = bp_dd_get_random_users_ids( absint( $recipients_numbers ), 'array' );

		$thread_reply = empty( $message['thread_reply'] ) ? 1 : $message['thread_reply'];

		$message_id = messages_new_message(
			array(
				'sender_id'  => $user_id,
				'recipients' => $recipients,
				'subject'    => $message['content'],
				'content'    => $message['content'],
				'date_sent'  => bp_dd_get_random_date( 29, 16 ),
			)
		);

		$messages[] = $message_id;

		$all_recipients = array_merge( $recipients, (array) $user_id );

		// first level messages
		for ( $i = 0; $i <= $thread_reply; $i ++ ) {
			$replying_sender_id     = $all_recipients[ array_rand( $all_recipients ) ];
			$replying_recipients_id = array_diff( $all_recipients, (array) $replying_sender_id );

			$messages[] = messages_new_message(
				array(
					'sender_id'  => $replying_sender_id,
					'recipients' => $replying_recipients_id,
					'thread_id'  => $message_id,
					'subject'    => $message['content'],
					'content'    => $random_thread_reply[ array_rand( $random_thread_reply ) ],
					'date_sent'  => bp_dd_get_random_date( 15, 1 ),
				)
			);
		}
	}

	if ( ! empty( $messages ) ) {
		/** @noinspection PhpParamsInspection */
		bp_update_option( 'bp_dd_imported_user_messages_ids', $messages );
	}

	return $messages;
}

/**
 * Import Activity - aka "status updates".
 *
 * @since BuddyBoss 1.0.0
 *
 * @return int Number of activity records that were inserted into the database.
 */
function bp_dd_import_users_activity() {
	$count = 0;

	if ( ! bp_is_active( 'activity' ) ) {
		return $count;
	}

	$users = bp_dd_get_random_users_ids( 0 );

	/** @var $activity array */
	require BP_DEFAULT_DATA_DIR . 'data/activity.php';

	for ( $i = 0; $i < 75; $i ++ ) {
		$user    = $users[ array_rand( $users ) ];
		$content = $activity[ array_rand( $activity ) ];

		if ( $bp_activity_id = bp_activity_post_update(
			array(
				'user_id' => $user,
				'content' => $content,
			)
		)
		) {
			$bp_activity                = new BP_Activity_Activity( $bp_activity_id );
			$bp_activity->date_recorded = bp_dd_get_random_date( 44 );
			if ( $bp_activity->save() ) {
				$count ++;
			}
		}
	}

	return $count;
}

/**
 * Get random users from the DB and generate member connections.
 *
 * @since BuddyBoss 1.0.0
 *
 * @return int
 */
function bp_dd_import_users_friends() {
	$count = 0;

	if ( ! bp_is_active( 'friends' ) ) {
		return $count;
	}

	$users = bp_dd_get_random_users_ids( 50 );

	add_filter( 'bp_core_current_time', 'bp_dd_friends_add_friend_date_fix' );

	for ( $i = 0; $i < 100; $i ++ ) {
		$user_one = $users[ array_rand( $users ) ];
		$user_two = $users[ array_rand( $users ) ];

		// Make them friends if possible.
		if ( friends_add_friend( $user_one, $user_two, true ) ) {
			$count ++;
		}
	}

	remove_filter( 'bp_core_current_time', 'bp_dd_friends_add_friend_date_fix' );

	return $count;
}

/**
 *  Importer engine - GROUPS
 *
 * @since BuddyBoss 1.0.0
 *
 * @param bool|array $users Users list we want to work with. Get random if empty.
 *
 * @return array
 */
function bp_dd_import_groups( $users = false ) {
	$groups          = array();
	$group_ids       = array();
	$forum_group_ids = array();

	if ( ! bp_is_active( 'groups' ) ) {
		return $group_ids;
	}

	// Use currently available users from DB if no default were specified.
	if ( empty( $users ) ) {
		$users = get_users();
	}

	$image_url         = BP_DEFAULT_DATA_URL . 'data/images/groups/';
	$cover_image_url   = $image_url . 'cover/';
	$avatars_image_url = $image_url . 'avatars/';

	require BP_DEFAULT_DATA_DIR . '/data/groups.php';

	foreach ( $groups as $group ) {
		$creator_id = is_object( $users[ array_rand( $users ) ] ) ? $users[ array_rand( $users ) ]->ID : $users[ array_rand( $users ) ];
		$cur        = groups_create_group(
			array(
				'creator_id'   => $creator_id,
				'name'         => $group['name'],
				'description'  => $group['description'],
				'slug'         => groups_check_slug( sanitize_title( esc_attr( $group['name'] ) ) ),
				'status'       => $group['status'],
				'date_created' => bp_dd_get_random_date( 30, 5 ),
			)
		);

		groups_update_groupmeta( $cur, 'last_activity', bp_dd_get_random_date( 10 ) );

		// add cover images url into DB
		if ( ! empty( $group['cover-image'] ) ) {
			groups_update_groupmeta( $cur, 'cover-image', $cover_image_url . $group['cover-image'] );
		}

		// add cover images url into DB
		if ( ! empty( $group['avatars'] ) ) {
			groups_update_groupmeta( $cur, 'avatars', $avatars_image_url . $group['avatars'] );
		}

		$group_ids[] = $cur;

		if ( ! empty( $group['forums'] ) ) {
			$forum_group_ids[] = $cur;
		}
	}

	if ( ! empty( $group_ids ) ) {
		/** @noinspection PhpParamsInspection */
		bp_update_option( 'bp_dd_imported_group_ids', $group_ids );
		bp_update_option( 'bp_dd_imported_forums_group_ids', $forum_group_ids );
	}

	return $group_ids;
}

/**
 * Import groups activity - aka "status updates".
 *
 * @since BuddyBoss 1.0.0
 *
 * @return int
 */
function bp_dd_import_groups_activity() {
	$count = 0;

	if ( ! bp_is_active( 'groups' ) || ! bp_is_active( 'activity' ) ) {
		return $count;
	}

	$users  = bp_dd_get_random_users_ids( 0 );
	$groups = bp_dd_get_random_groups_ids( 0 );

	/** @var $activity array */
	require BP_DEFAULT_DATA_DIR . 'data/activity.php';

	for ( $i = 0; $i < 150; $i ++ ) {
		$user_id  = $users[ array_rand( $users ) ];
		$group_id = $groups[ array_rand( $groups ) ];
		$content  = $activity[ array_rand( $activity ) ];

		if ( ! groups_is_user_member( $user_id, $group_id ) ) {
			continue;
		}

		if ( $bp_activity_id = groups_post_update(
			array(
				'user_id'  => $user_id,
				'group_id' => $group_id,
				'content'  => $content,
			)
		)
		) {
			$bp_activity                = new BP_Activity_Activity( $bp_activity_id );
			$bp_activity->date_recorded = bp_dd_get_random_date( 29 );
			if ( $bp_activity->save() ) {
				$count ++;
			}
		}
	}

	return $count;
}

/**
 * Import groups members.
 *
 * @since BuddyBoss 1.0.0
 *
 * @param bool $groups We can import random groups or work with a predefined list.
 *
 * @return array
 */
function bp_dd_import_groups_members( $groups = false ) {
	$members = array();

	if ( ! bp_is_active( 'groups' ) ) {
		return $members;
	}

	if ( ! $groups ) {
		$groups = bp_dd_get_random_groups_ids( 0 );
	}

	add_filter( 'bp_after_activity_add_parse_args', 'bp_dd_groups_join_group_date_fix' );

	foreach ( $groups as $group_id ) {
		$user_ids = bp_dd_get_random_users_ids( mt_rand( 2, 15 ) );

		foreach ( $user_ids as $user_id ) {
			if ( groups_join_group( $group_id, $user_id ) ) {
				$members[] = $group_id;
			}
		}
	}

	remove_filter( 'bp_after_activity_add_parse_args', 'bp_dd_groups_join_group_date_fix' );

	return $members;
}

/**
 * Create forums
 *
 * @since BuddyBoss 1.0.0
 *
 * @param $forum
 * @param array $users
 *
 * @return bool|int|WP_Error
 */
function bp_dd_create_forums( $forum, $users = array() ) {
	if ( empty( $users ) ) {
		$users = bp_dd_get_random_users_ids( 0 );
	}

	$creator_id = is_object( $users[ array_rand( $users ) ] ) ? $users[ array_rand( $users ) ]->ID : $users[ array_rand( $users ) ];

	$args = array(
		'post_title'   => $forum['name'],
		'post_content' => $forum['description'],
		'post_status'  => $forum['visibility'],
		'post_author'  => $creator_id,
	);

	$forum_id = bbp_insert_forum( $args );

	if ( ! empty( $forum_id ) && isset( $forum['status'] ) ) {
		update_post_meta( $forum_id, '_bbp_status', $forum['status'] );
	}

	return $forum_id;
}

/**
 *  Importer engine - FORMS
 *
 * @since BuddyBoss 1.0.0
 *
 * @param bool|array $users Users list we want to work with. Get random if empty.
 *
 * @return array
 */
function bp_dd_import_forums( $users = false ) {
	$forums    = array();
	$forum_ids = bp_get_option( 'bp_dd_imported_forum_ids', array() );

	if ( ! bp_is_active( 'forums' ) ) {
		return $forum_ids;
	}

	// Use currently available users from DB if no default were specified.
	if ( empty( $users ) ) {
		$users = bp_dd_get_random_users_ids( 0 );
	}

	require BP_DEFAULT_DATA_DIR . 'data/forums.php';

	foreach ( $forums as $forum ) {

		$forum_id = bp_dd_create_forums( $forum, $users );

		if ( ! empty( $forum_id ) ) {
			$forum_ids[] = $forum_id;
		}
	}

	if ( ! empty( $forum_ids ) ) {
		/** @noinspection PhpParamsInspection */
		bp_update_option( 'bp_dd_imported_forum_ids', array_unique( $forum_ids ) );
	}

	return $forum_ids;
}

/**
 * Function to create topic inside forums
 *
 * @since BuddyBoss 1.0.0
 *
 * @param $topic_data
 * @param $forum_id
 * @param array      $users
 *
 * @return bool|int|WP_Error
 */
function bp_dd_create_forums_topics( $topic_data, $forum_id, $users = array() ) {
	$topic_id = false;
	if ( empty( $users ) ) {
		$users = bp_dd_get_random_users_ids( 0 );
	}

	$creator_id = is_object( $users[ array_rand( $users ) ] ) ? $users[ array_rand( $users ) ]->ID : $users[ array_rand( $users ) ];

	// Create the initial topic
	$topic_id = bbp_insert_topic(
		array(
			'post_parent'  => $forum_id,
			'post_title'   => $topic_data['name'],
			'post_content' => $topic_data['description'],
			'post_status'  => $topic_data['status'],
			'post_author'  => $creator_id,
		),
		array( 'forum_id' => $forum_id )
	);

	return $topic_id;
}

/**
 * Import topics in to forums.
 *
 * @since BuddyBoss 1.0.0
 *
 * @param bool $forums We can import random groups or work with a predefined list.
 *
 * @return array
 */
function bp_dd_import_forums_topics( $forums = false ) {
	$topics     = array();
	$topics_ids = bp_get_option( 'bp_dd_imported_topic_ids', array() );

	if ( ! bp_is_active( 'forums' ) ) {
		return $topics_ids;
	}

	if ( empty( $forums ) ) {
		$forums = bp_dd_get_random_forums_ids( 0 );
	}

	$users = bp_dd_get_random_users_ids( 0 );

	require BP_DEFAULT_DATA_DIR . 'data/forums_topics.php';
	foreach ( $forums as $forum_id ) {
		$topic = (array) array_rand( $topics, absint( rand( 2, count( $topics ) ) ) );
		foreach ( $topic as $topic_key ) {
			$topic_data = $topics[ $topic_key ];

			// Create the initial topic
			$topic_id = bp_dd_create_forums_topics( $topic_data, $forum_id, $users );

			if ( ! empty( $topic_id ) ) {
				$topics_ids[] = $topic_id;
			}
		}
	}

	if ( ! empty( $topics_ids ) ) {
		/** @noinspection PhpParamsInspection */
		bp_update_option( 'bp_dd_imported_topic_ids', array_unique( $topics_ids ) );
	}

	return $topics_ids;
}

/**
 * Return Reply ID
 *
 * @since BuddyBoss 1.0.0
 *
 * @param $reply_data
 * @param $topic_id
 * @param array      $users
 *
 * @return bool|int|WP_Error
 */
function bp_dd_create_forums_topics_replies( $reply_data, $topic_id, $users = array() ) {
	if ( empty( $users ) ) {
		$users = bp_dd_get_random_users_ids( 0 );
	}

	$creator_id = is_object( $users[ array_rand( $users ) ] ) ? $users[ array_rand( $users ) ]->ID : $users[ array_rand( $users ) ];
	$forum_id   = get_post_meta( $topic_id, '_bbp_forum_id', true );

	$reply_id = bbp_insert_reply(
		array(
			'post_parent'  => $topic_id,
			'post_title'   => '',
			'post_content' => $reply_data,
			'post_author'  => $creator_id,
		),
		array(
			'topic_id' => $topic_id,
			'forum_id' => $forum_id,
		)
	);

	if ( ! empty( $reply_id ) ) {
		update_post_meta( $reply_id, '_bbp_topic_id', $topic_id );
	}

	return $reply_id;
}

/**
 * Import replies in to forums.
 *
 * @since BuddyBoss 1.0.0
 *
 * @param bool $forums We can import random groups or work with a predefined list.
 *
 * @return array
 */
function bp_dd_import_forums_topics_replies( $topics = false ) {
	$replies   = array();
	$reply_ids = bp_get_option( 'bp_dd_imported_reply_ids', array() );

	if ( ! bp_is_active( 'forums' ) ) {
		return $reply_ids;
	}

	if ( ! $topics ) {
		$topics = bp_dd_get_random_topics_ids( 0 );
	}

	$users = bp_dd_get_random_users_ids( 0 );

	require BP_DEFAULT_DATA_DIR . 'data/forums_replies.php';

	foreach ( $topics as $topic_id ) {

		$reply = (array) array_rand( $replies, absint( rand( 1, 7 ) ) );

		foreach ( $reply as $reply_key ) {
			$reply_data = $replies[ $reply_key ];

			$reply_id = bp_dd_create_forums_topics_replies( $reply_data, $topic_id, $users );

			if ( ! empty( $reply_id ) ) {
				$reply_ids[] = $reply_id;
			}
		}
	}

	if ( ! empty( $reply_ids ) ) {
		/** @noinspection PhpParamsInspection */
		bp_update_option( 'bp_dd_imported_reply_ids', array_unique( $reply_ids ) );
	}

	return $reply_ids;
}

/**
 * Import Forums in Groups
 *
 * @since BuddyBoss 1.0.0
 */
function bp_dd_import_forums_in_groups() {
	$topics     = array();
	$replies    = array();
	$forum_ids  = array();
	$topics_ids = array();
	$reply_ids  = array();
	$groups_ids = array();

	if ( ! bp_is_active( 'forums' ) || ! bp_is_active( 'groups' ) ) {
		return $groups_ids;
	}

	$users = bp_dd_get_random_users_ids( 0 );

	$groups_ids = bp_dd_get_forums_enable_groups_ids( 8 );

	foreach ( $groups_ids as $groups_id ) {
		// Get the group
		$group = groups_get_group( $groups_id );
		if ( ! empty( $group ) ) {
			$forum['name']        = $group->name;
			$forum['description'] = sprintf( __( 'Default Forum for %s Group', 'buddyboss' ), $group->name );
			$forum['visibility']  = $group->status;
			$forum['status']      = 'open';

			$forum_id = bp_dd_create_forums( $forum, $users );
			if ( $forum_id ) {
				bbp_add_forum_id_to_group( $groups_id, $forum_id );
				bbp_add_group_id_to_forum( $forum_id, $groups_id );

				$forum_ids[] = $forum_id;

				// Set forum enabled status
				$group->enable_forum = 1;

				// Save the group
				$group->save();
			}
		}

		bp_update_option( 'bp_dd_imported_forum_ids', array_merge( $forum_ids, bp_get_option( 'bp_dd_imported_forum_ids', array() ) ) );
	}

	require BP_DEFAULT_DATA_DIR . 'data/forums_topics.php';

	foreach ( $forum_ids as $forum_id ) {
		$topic = (array) array_rand( $topics, absint( rand( 2, count( $topics ) ) ) );
		foreach ( $topic as $topic_key ) {
			$topic_data = $topics[ $topic_key ];

			// Create the initial topic
			$topic_id = bp_dd_create_forums_topics( $topic_data, $forum_id, $users );

			if ( ! empty( $topic_id ) ) {
				$topics_ids[] = $topic_id;
			}
		}
	}

	bp_update_option( 'bp_dd_imported_topic_ids', array_merge( $topics_ids, bp_get_option( 'bp_dd_imported_topic_ids', array() ) ) );

	require BP_DEFAULT_DATA_DIR . 'data/forums_replies.php';

	foreach ( $topics_ids as $topic_id ) {
		$reply = (array) array_rand( $replies, absint( rand( 1, 7 ) ) );

		foreach ( $reply as $reply_key ) {
			$reply_data = $replies[ $reply_key ];

			$reply_id = bp_dd_create_forums_topics_replies( $reply_data, $topic_id, $users );

			if ( ! empty( $reply_id ) ) {
				$reply_ids[] = $reply_id;
			}
		}
	}

	bp_update_option( 'bp_dd_imported_reply_ids', array_merge( $reply_ids, bp_get_option( 'bp_dd_imported_reply_ids', array() ) ) );

	return $groups_ids;
}
