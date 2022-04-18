<?php
/**
 * BuddyBoss Messages Filters.
 *
 * Apply WordPress defined filters to private messages.
 *
 * @package BuddyBoss\Messages\Filters
 * @since BuddyPress 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

add_filter( 'bp_get_message_notice_subject', 'wp_filter_kses', 1 );
add_filter( 'bp_get_message_notice_text', 'wp_filter_kses', 1 );
add_filter( 'bp_get_message_thread_subject', 'wp_filter_kses', 1 );
add_filter( 'bp_get_message_thread_excerpt', 'wp_filter_kses', 1 );
add_filter( 'bp_get_messages_subject_value', 'wp_filter_kses', 1 );
add_filter( 'bp_get_messages_content_value', 'wp_filter_kses', 1 );
add_filter( 'messages_message_subject_before_save', 'wp_filter_kses', 1 );
add_filter( 'messages_notice_subject_before_save', 'wp_filter_kses', 1 );
add_filter( 'bp_get_the_thread_subject', 'wp_filter_kses', 1 );

add_filter( 'bp_get_the_thread_message_content', 'bp_messages_filter_kses', 1 );
add_filter( 'messages_message_content_before_save', 'bp_messages_filter_kses', 1 );
add_filter( 'messages_notice_message_before_save', 'bp_messages_filter_kses', 1 );
add_filter( 'bp_get_message_thread_content', 'bp_messages_filter_kses', 1 );

add_filter( 'messages_message_content_before_save', 'force_balance_tags' );
add_filter( 'messages_message_subject_before_save', 'force_balance_tags' );
add_filter( 'messages_notice_message_before_save', 'force_balance_tags' );
add_filter( 'messages_notice_subject_before_save', 'force_balance_tags' );

if ( function_exists( 'wp_encode_emoji' ) ) {
	add_filter( 'messages_message_subject_before_save', 'wp_encode_emoji' );
	add_filter( 'messages_message_content_before_save', 'wp_encode_emoji' );
	add_filter( 'messages_notice_message_before_save', 'wp_encode_emoji' );
	add_filter( 'messages_notice_subject_before_save', 'wp_encode_emoji' );
}

add_filter( 'bp_get_message_notice_subject', 'wptexturize' );
add_filter( 'bp_get_message_notice_text', 'wptexturize' );
add_filter( 'bp_get_message_thread_subject', 'wptexturize' );
add_filter( 'bp_get_message_thread_excerpt', 'wptexturize' );
add_filter( 'bp_get_the_thread_message_content', 'wptexturize' );
add_filter( 'bp_get_message_thread_content', 'wptexturize' );

add_filter( 'bp_get_message_notice_subject', 'convert_smilies', 2 );
add_filter( 'bp_get_message_notice_text', 'convert_smilies', 2 );
add_filter( 'bp_get_message_thread_subject', 'convert_smilies', 2 );
add_filter( 'bp_get_message_thread_excerpt', 'convert_smilies', 2 );
add_filter( 'bp_get_the_thread_message_content', 'convert_smilies', 2 );
add_filter( 'bp_get_message_thread_content', 'convert_smilies', 2 );

add_filter( 'bp_get_message_notice_subject', 'convert_chars' );
add_filter( 'bp_get_message_notice_text', 'convert_chars' );
add_filter( 'bp_get_message_thread_subject', 'convert_chars' );
add_filter( 'bp_get_message_thread_excerpt', 'convert_chars' );
add_filter( 'bp_get_the_thread_message_content', 'convert_chars' );
add_filter( 'bp_get_message_thread_content', 'convert_chars' );

add_filter( 'bp_get_message_notice_text', 'make_clickable', 9 );
add_filter( 'bp_get_the_thread_message_content', 'make_clickable', 9 );
add_filter( 'bp_get_message_thread_content', 'make_clickable', 9 );

add_filter( 'bp_get_message_notice_text', 'wpautop' );
add_filter( 'bp_get_the_thread_message_content', 'wpautop' );
add_filter( 'bp_get_message_thread_content', 'wpautop' );

add_filter( 'bp_get_message_notice_subject', 'stripslashes_deep' );
add_filter( 'bp_get_message_notice_text', 'stripslashes_deep' );
add_filter( 'bp_get_message_thread_subject', 'stripslashes_deep' );
add_filter( 'bp_get_message_thread_excerpt', 'stripslashes_deep' );
add_filter( 'bp_get_message_get_recipient_usernames', 'stripslashes_deep' );
add_filter( 'bp_get_messages_subject_value', 'stripslashes_deep' );
add_filter( 'bp_get_messages_content_value', 'stripslashes_deep' );
add_filter( 'bp_get_the_thread_message_content', 'stripslashes_deep' );
add_filter( 'bp_get_the_thread_subject', 'stripslashes_deep' );
add_filter( 'bp_get_message_thread_content', 'stripslashes_deep', 1 );

// Actions
add_action( 'messages_screen_compose', 'maybe_redirects_to_previous_thread_message' );

add_action( 'groups_join_group', 'bp_group_messages_join_new_member', 10, 2 );
add_action( 'groups_accept_invite', 'bp_group_messages_accept_new_member', 10, 2 );
add_action( 'groups_banned_member', 'bp_group_messages_banned_member', 10, 2 );
add_action( 'groups_ban_member', 'bp_group_messages_admin_banned_member', 10, 2 );
add_action( 'groups_unban_member', 'bp_group_messages_unbanned_member', 10, 2 );
add_action( 'groups_membership_accepted', 'bp_group_messages_groups_membership_accepted', 10, 3 );
add_action( 'messages_message_sent', 'bp_media_messages_save_group_data' );

add_action( 'groups_leave_group', 'bp_group_messages_remove_group_member_from_thread', 10, 2 );
add_action( 'groups_remove_member', 'bp_group_messages_remove_group_member_from_thread', 10, 2 );

add_filter( 'bp_repair_list', 'bp_messages_repair_items_unread_count' );

add_filter( 'bp_recipients_recipient_get_where_conditions', 'bp_recipients_recipient_get_where_conditions_callback', 10, 2 );
add_filter( 'bp_core_get_js_strings', 'bp_core_get_js_strings_callback', 10, 1 );

// Load Messages Notifications.
add_action( 'bp_messages_includes', 'bb_load_messages_notifications', 20 );
add_action( 'bp_notification_settings', 'messages_screen_notification_settings', 2 );

/**
 * Enforce limitations on viewing private message contents
 *
 * @since BuddyPress 2.3.2
 *
 * @see bp_has_message_threads() for description of parameters
 *
 * @param array|string $args See {@link bp_has_message_threads()}.
 * @return array|string
 */
function bp_messages_enforce_current_user( $args = array() ) {

	// Non-community moderators can only ever see their own messages.
	if ( is_user_logged_in() && ! bp_current_user_can( 'bp_moderate' ) ) {
		$_user_id = (int) bp_loggedin_user_id();
		if ( $_user_id !== (int) $args['user_id'] ) {
			$args['user_id'] = $_user_id;
		}
	}

	// Return possibly modified $args array.
	return $args;
}
add_filter( 'bp_after_has_message_threads_parse_args', 'bp_messages_enforce_current_user', 5 );

/**
 * Custom kses filtering for message content.
 *
 * @since BuddyPress 3.0.0
 *
 * @param string $content The message content.
 * @return string         The filtered message content.
 */
function bp_messages_filter_kses( $content ) {
	$messages_allowedtags      = bp_get_allowedtags();
	$messages_allowedtags['p'] = array();

	/**
	 * Filters the allowed HTML tags for BuddyBoss Messages content.
	 *
	 * @since BuddyPress 3.0.0
	 *
	 * @param array $value Array of allowed HTML tags and attributes.
	 */
	$messages_allowedtags = apply_filters( 'bp_messages_allowed_tags', $messages_allowedtags );
	return wp_kses( $content, $messages_allowedtags );
}

/**
 * [maybe_redirects_to_previous_thread_message description]
 *
 * @return [type] [description]
 */
function maybe_redirects_to_previous_thread_message() {
	$recipient = bp_get_messages_username_value();
	$user_id   = bp_core_get_userid( $recipient );

	if ( ! $thread_id = BP_Messages_Message::get_existing_thread( array( $user_id ), bp_loggedin_user_id() ) ) {
		return;
	}

	$thread_url = esc_url( bp_core_get_user_domain( bp_loggedin_user_id() ) . bp_get_messages_slug() . '/view/' . $thread_id . '/' );

	wp_safe_redirect( $thread_url );
	exit();
}

/**
 * Add new message to a existing group thread when someone membership is accepted in group.
 *
 * @since BuddyBoss 1.2.9
 *
 * @param $user_id
 * @param $group_id
 * @param $accepted
 */
function bp_group_messages_groups_membership_accepted( $user_id, $group_id, $accepted ) {

	global $wpdb, $bp, $messages_template;

	$group_thread = (int) groups_get_groupmeta( (int) $group_id, 'group_message_thread' );

	if ( $group_thread > 0 ) {

		$first_message = BP_Messages_Thread::get_first_message( $group_thread );

		$message_users_ids = bp_messages_get_meta( $first_message->id, 'message_users_ids', true ); // users list
		$message_users_ids = explode( ',', $message_users_ids );
		array_push( $message_users_ids, $user_id );
		$group_name = bp_get_group_name( groups_get_group( $group_id ) );
		$text       = sprintf( __( 'Joined "%s" ', 'buddyboss' ), $group_name );

		bp_messages_update_meta( $first_message->id, 'message_users_ids', implode( ',', $message_users_ids ) );

		$wpdb->query( $wpdb->prepare( "INSERT INTO {$bp->messages->table_name_recipients} ( user_id, thread_id, unread_count ) VALUES ( %d, %d, 0 )", $user_id, $group_thread ) );

		remove_action( 'messages_message_sent', 'messages_notification_new_message', 10 );
		remove_action( 'messages_message_sent', 'bp_messages_message_sent_add_notification', 10 );
		$new_reply = messages_new_message(
			array(
				'thread_id'  => $group_thread,
				'sender_id'  => $user_id,
				'subject'    => '',
				'content'    => '<p> </p>',
				'date_sent'  => $date_sent = bp_core_current_time(),
				'error_type' => 'wp_error',
			)
		);

		add_action( 'messages_message_sent', 'messages_notification_new_message', 10 );
		add_action( 'messages_message_sent', 'bp_messages_message_sent_add_notification', 10 );

		$last_message = BP_Messages_Thread::get_last_message( $group_thread );
		bp_messages_update_meta( $last_message->id, 'group_message_group_joined', 'yes' );
		bp_messages_update_meta( $last_message->id, 'group_id', $group_id );
	}
}

/**
 * Save group message meta.
 *
 * @since BuddyBoss 1.2.9
 *
 * @param object $message Message object.
 */
function bp_media_messages_save_group_data( &$message ) {

	if ( false === bp_disable_group_messages() ) {
		return;
	}

	static $cache               = array();
	static $cache_first_message = array();

	$group                   = ( isset( $_POST ) && isset( $_POST['group'] ) && '' !== $_POST['group'] ) ? trim( $_POST['group'] ) : ''; // Group id.
	$message_users           = ( isset( $_POST ) && isset( $_POST['users'] ) && '' !== $_POST['users'] ) ? trim( $_POST['users'] ) : ''; // all - individual.
	$message_type            = ( isset( $_POST ) && isset( $_POST['type'] ) && '' !== $_POST['type'] ) ? trim( $_POST['type'] ) : ''; // open - private.
	$message_meta_users_list = ( isset( $_POST ) && isset( $_POST['message_meta_users_list'] ) && '' !== $_POST['message_meta_users_list'] ) ? trim( $_POST['message_meta_users_list'] ) : ''; // users list.
	$thread_type             = ( isset( $_POST ) && isset( $_POST['message_thread_type'] ) && '' !== $_POST['message_thread_type'] ) ? trim( $_POST['message_thread_type'] ) : ''; // new - reply.

	if ( '' === $message_meta_users_list && isset( $group ) && '' !== $group ) {
		if ( isset( $cache[ $group ] ) ) {
			$message_meta_users_list = $cache[ $group ];
		} else {
			// Fetch all the group members.
			$members = BP_Groups_Member::get_group_member_ids( (int) $group );

			// Exclude logged-in user ids from the members list.
			if ( in_array( bp_loggedin_user_id(), $members, true ) ) {
				$members = array_values( array_diff( $members, array( bp_loggedin_user_id() ) ) );
			}
			$message_meta_users_list = implode( ',', $members );
			$cache[ $group ]         = $message_meta_users_list;
		}
	}

	if ( isset( $group ) && '' !== $group ) {
		$thread_key = 'group_message_thread_id_' . $message->thread_id;
		bp_messages_update_meta( $message->id, 'group_id', $group );
		bp_messages_update_meta( $message->id, 'group_message_users', $message_users );
		bp_messages_update_meta( $message->id, 'group_message_type', $message_type );
		bp_messages_update_meta( $message->id, 'group_message_thread_type', $thread_type );
		bp_messages_update_meta( $message->id, 'group_message_fresh', 'yes' );
		bp_messages_update_meta( $message->id, $thread_key, $group );
		bp_messages_update_meta( $message->id, 'message_from', 'group' );
		bp_messages_update_meta( $message->id, 'message_sender', bp_loggedin_user_id() );
		bp_messages_update_meta( $message->id, 'message_users_ids', $message_meta_users_list );
		bp_messages_update_meta( $message->id, 'group_message_thread_id', $message->thread_id );
	} else {

		if ( isset( $cache_first_message[ $message->thread_id ] ) ) {
			$message_id = $cache_first_message[ $message->thread_id ];
		} else {
			$first_message                              = BP_Messages_Thread::get_first_message( (int) $message->thread_id );
			$message_id                                 = (int) $first_message->id;
			$cache_first_message[ $message->thread_id ] = $message_id;
		}

		if ( $message_id > 0 ) {
			$group         = bp_messages_get_meta( $message_id, 'group_id', true );
			$message_users = bp_messages_get_meta( $message_id, 'group_message_users', true );
			$message_type  = bp_messages_get_meta( $message_id, 'group_message_type', true );
			$thread_type   = bp_messages_get_meta( $message_id, 'group_message_thread_type', true );
		}

		if ( $group ) {
			$thread_key = 'group_message_thread_id_' . $message->thread_id;
			bp_messages_update_meta( $message->id, 'group_id', $group );
			bp_messages_update_meta( $message->id, 'group_message_users', $message_users );
			bp_messages_update_meta( $message->id, 'group_message_type', $message_type );
			bp_messages_update_meta( $message->id, 'group_message_thread_type', $thread_type );
			bp_messages_update_meta( $message->id, $thread_key, $group );
			bp_messages_update_meta( $message->id, 'message_sender', bp_loggedin_user_id() );
			bp_messages_update_meta( $message->id, 'message_from', 'personal' );
			bp_messages_update_meta( $message->id, 'group_message_thread_id', $message->thread_id );
		}
	}
}

/**
 * Add new message to a existing group thread when someone join in group.
 *
 * @since BuddyBoss 1.2.9
 *
 * @param int $group_id Group id.
 * @param int $user_id User id.
 */
function bp_group_messages_join_new_member( $group_id, $user_id ) {
	global $wpdb, $bp;

	$group_thread = (int) groups_get_groupmeta( (int) $group_id, 'group_message_thread' );

	if ( $group_thread > 0 ) {

		$first_message     = BP_Messages_Thread::get_first_message( $group_thread );
		$message_users_ids = bp_messages_get_meta( $first_message->id, 'message_users_ids', true ); // users list.
		$message_users_ids = explode( ',', $message_users_ids );
		array_push( $message_users_ids, $user_id );

		bp_messages_update_meta( $first_message->id, 'message_users_ids', implode( ',', $message_users_ids ) );

		$wpdb->query( $wpdb->prepare( "INSERT INTO {$bp->messages->table_name_recipients} ( user_id, thread_id, unread_count ) VALUES ( %d, %d, 0 )", $user_id, $group_thread ) );

		remove_action( 'messages_message_sent', 'messages_notification_new_message', 10 );
		remove_action( 'messages_message_sent', 'bp_messages_message_sent_add_notification', 10 );
		$new_reply = messages_new_message(
			array(
				'thread_id'  => $group_thread,
				'sender_id'  => $user_id,
				'subject'    => '',
				'content'    => '<p> </p>',
				'date_sent'  => bp_core_current_time(),
				'error_type' => 'wp_error',
			)
		);
		add_action( 'messages_message_sent', 'messages_notification_new_message', 10 );
		add_action( 'messages_message_sent', 'bp_messages_message_sent_add_notification', 10 );

		$last_message = BP_Messages_Thread::get_last_message( $group_thread );
		bp_messages_update_meta( $last_message->id, 'group_message_group_joined', 'yes' );
		bp_messages_update_meta( $last_message->id, 'group_id', $group_id );
	}
}


/**
 * Add new message to a existing group thread when someone remove from group.
 *
 * @since BuddyBoss 1.2.9
 *
 * @param int $group_id Group id.
 * @param int $user_id User id.
 */
function bp_group_messages_remove_group_member_from_thread( $group_id, $user_id ) {

	global $wpdb, $bp;

	$group_thread = (int) groups_get_groupmeta( (int) $group_id, 'group_message_thread' );

	if ( $group_thread > 0 ) {
		$first_message     = BP_Messages_Thread::get_first_message( $group_thread );
		$message_users_ids = bp_messages_get_meta( $first_message->id, 'message_users_ids', true ); // users list.
		$message_users_ids = explode( ',', $message_users_ids );
		if ( ( $key = array_search( $user_id, $message_users_ids ) ) !== false ) {
			unset( $message_users_ids[ $key ] );
		}

		bp_messages_update_meta( $first_message->id, 'message_users_ids', implode( ',', $message_users_ids ) );

		remove_action( 'messages_message_sent', 'messages_notification_new_message', 10 );
		remove_action( 'messages_message_sent', 'bp_messages_message_sent_add_notification', 10 );
		$new_reply = messages_new_message(
			array(
				'sender_id'  => $user_id,
				'thread_id'  => $group_thread,
				'subject'    => '',
				'content'    => '<p> </p>',
				'date_sent'  => bp_core_current_time(),
				'error_type' => 'wp_error',
			)
		);
		add_action( 'messages_message_sent', 'messages_notification_new_message', 10 );
		add_action( 'messages_message_sent', 'bp_messages_message_sent_add_notification', 10 );

		$last_message = BP_Messages_Thread::get_last_message( $group_thread );
		bp_messages_update_meta( $last_message->id, 'group_message_group_left', 'yes' );
		bp_messages_update_meta( $last_message->id, 'group_id', $group_id );
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$bp->messages->table_name_recipients} WHERE user_id = %d AND thread_id = %d", $user_id, (int) $group_thread ) );

	}
}

/**
 * Add new message to group thread if new member joined the group.
 *
 * @since BuddyBoss 1.2.9
 *
 * @param int $user_id  User id.
 * @param int $group_id Group id.
 */
function bp_group_messages_accept_new_member( $user_id, $group_id ) {

	global $wpdb, $bp;

	$group_thread = (int) groups_get_groupmeta( (int) $group_id, 'group_message_thread' );

	if ( $group_thread > 0 ) {

		$first_message     = BP_Messages_Thread::get_first_message( $group_thread );
		$message_users_ids = bp_messages_get_meta( $first_message->id, 'message_users_ids', true ); // users list.
		$message_users_ids = explode( ',', $message_users_ids );

		array_push( $message_users_ids, $user_id );
		$group_name = bp_get_group_name( groups_get_group( $group_id ) );

		bp_messages_update_meta( $first_message->id, 'message_users_ids', implode( ',', $message_users_ids ) );

		$wpdb->query( $wpdb->prepare( "INSERT INTO {$bp->messages->table_name_recipients} ( user_id, thread_id, unread_count ) VALUES ( %d, %d, 0 )", $user_id, $group_thread ) );

		remove_action( 'messages_message_sent', 'messages_notification_new_message', 10 );
		remove_action( 'messages_message_sent', 'bp_messages_message_sent_add_notification', 10 );
		$new_reply = messages_new_message(
			array(
				'thread_id'  => $group_thread,
				'sender_id'  => $user_id,
				'subject'    => '',
				'content'    => '<p> </p>',
				'date_sent'  => $date_sent = bp_core_current_time(),
				'error_type' => 'wp_error',
			)
		);

		add_action( 'messages_message_sent', 'messages_notification_new_message', 10 );
		add_action( 'messages_message_sent', 'bp_messages_message_sent_add_notification', 10 );

		$last_message = BP_Messages_Thread::get_last_message( $group_thread );
		bp_messages_update_meta( $last_message->id, 'group_message_group_joined', 'yes' );
		bp_messages_update_meta( $last_message->id, 'group_id', $group_id );
	}
}

/**
 * Add new message to group thread when someone from the group ban.
 *
 * @since BuddyBoss 1.2.9
 *
 * @param int $user_id  User id.
 * @param int $group_id Group id.
 */
function bp_group_messages_banned_member( $user_id, $group_id ) {

	global $wpdb, $bp;

	$group_thread = (int) groups_get_groupmeta( (int) $group_id, 'group_message_thread' );

	if ( $group_thread > 0 ) {
		$first_message     = BP_Messages_Thread::get_first_message( $group_thread );
		$message_users_ids = bp_messages_get_meta( $first_message->id, 'message_users_ids', true ); // users list.
		$message_users_ids = explode( ',', $message_users_ids );
		$group_name        = bp_get_group_name( groups_get_group( $group_id ) );
		$text              = sprintf( __( 'Left "%s" ', 'buddyboss' ), $group_name );
		if ( ( $key = array_search( $user_id, $message_users_ids ) ) !== false ) {
			unset( $message_users_ids[ $key ] );
		}

		bp_messages_update_meta( $first_message->id, 'message_users_ids', implode( ',', $message_users_ids ) );

		remove_action( 'messages_message_sent', 'messages_notification_new_message', 10 );
		remove_action( 'messages_message_sent', 'bp_messages_message_sent_add_notification', 10 );
		$new_reply = messages_new_message(
			array(
				'sender_id'  => $user_id,
				'thread_id'  => $group_thread,
				'subject'    => '',
				'content'    => '<p> </p>',
				'date_sent'  => bp_core_current_time(),
				'error_type' => 'wp_error',
			)
		);
		add_action( 'messages_message_sent', 'messages_notification_new_message', 10 );
		add_action( 'messages_message_sent', 'bp_messages_message_sent_add_notification', 10 );

		$last_message = BP_Messages_Thread::get_last_message( $group_thread );
		bp_messages_update_meta( $last_message->id, 'group_message_group_ban', 'yes' );
		bp_messages_update_meta( $last_message->id, 'group_id', $group_id );
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$bp->messages->table_name_recipients} WHERE user_id = %d AND thread_id = %d", $user_id, (int) $group_thread ) );

	}
}

/**
 * Add new message to group thread when someone from the group ban.
 *
 * @since BuddyBoss 1.2.9
 *
 * @param int $group_id Group id.
 * @param int $user_id  User id.
 */
function bp_group_messages_admin_banned_member( $group_id, $user_id ) {

	if ( ! is_admin() ) {
		return;
	}

	global $wpdb, $bp;

	$group_thread = (int) groups_get_groupmeta( (int) $group_id, 'group_message_thread' );

	if ( $group_thread > 0 ) {
		$first_message     = BP_Messages_Thread::get_first_message( $group_thread );
		$message_users_ids = bp_messages_get_meta( $first_message->id, 'message_users_ids', true ); // users list.
		$message_users_ids = explode( ',', $message_users_ids );
		if ( ( $key = array_search( $user_id, $message_users_ids ) ) !== false ) {
			unset( $message_users_ids[ $key ] );
		}

		bp_messages_update_meta( $first_message->id, 'message_users_ids', implode( ',', $message_users_ids ) );

		remove_action( 'messages_message_sent', 'messages_notification_new_message', 10 );
		remove_action( 'messages_message_sent', 'bp_messages_message_sent_add_notification', 10 );
		$new_reply = messages_new_message(
			array(
				'sender_id'  => $user_id,
				'thread_id'  => $group_thread,
				'subject'    => '',
				'content'    => '<p> </p>',
				'date_sent'  => bp_core_current_time(),
				'error_type' => 'wp_error',
			)
		);
		add_action( 'messages_message_sent', 'messages_notification_new_message', 10 );
		add_action( 'messages_message_sent', 'bp_messages_message_sent_add_notification', 10 );

		$last_message = BP_Messages_Thread::get_last_message( $group_thread );
		bp_messages_update_meta( $last_message->id, 'group_message_group_ban', 'yes' );
		bp_messages_update_meta( $last_message->id, 'group_id', $group_id );
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$bp->messages->table_name_recipients} WHERE user_id = %d AND thread_id = %d", $user_id, (int) $group_thread ) );

	}
}

/**
 * Add new message to group thread when someone from the group unban.
 *
 * @since BuddyBoss 1.2.9
 *
 * @param int $group_id Group id.
 * @param int $user_id  User id.
 */
function bp_group_messages_unbanned_member( $group_id, $user_id ) {

	global $wpdb, $bp;

	$group_thread = (int) groups_get_groupmeta( (int) $group_id, 'group_message_thread' );

	if ( $group_thread > 0 ) {

		$first_message     = BP_Messages_Thread::get_first_message( $group_thread );
		$message_users_ids = bp_messages_get_meta( $first_message->id, 'message_users_ids', true ); // users list.
		$message_users_ids = explode( ',', $message_users_ids );
		array_push( $message_users_ids, $user_id );

		bp_messages_update_meta( $first_message->id, 'message_users_ids', implode( ',', $message_users_ids ) );

		$wpdb->query( $wpdb->prepare( "INSERT INTO {$bp->messages->table_name_recipients} ( user_id, thread_id, unread_count ) VALUES ( %d, %d, 0 )", $user_id, $group_thread ) );

		remove_action( 'messages_message_sent', 'messages_notification_new_message', 10 );
		remove_action( 'messages_message_sent', 'bp_messages_message_sent_add_notification', 10 );
		$new_reply = messages_new_message(
			array(
				'thread_id'  => $group_thread,
				'sender_id'  => $user_id,
				'subject'    => '',
				'content'    => '<p> </p>',
				'date_sent'  => bp_core_current_time(),
				'error_type' => 'wp_error',
			)
		);
		add_action( 'messages_message_sent', 'messages_notification_new_message', 10 );
		add_action( 'messages_message_sent', 'bp_messages_message_sent_add_notification', 10 );

		$last_message = BP_Messages_Thread::get_last_message( $group_thread );
		bp_messages_update_meta( $last_message->id, 'group_message_group_un_ban', 'yes' );
		bp_messages_update_meta( $last_message->id, 'group_id', $group_id );
	}
}

/**
 * Remove member to Group thread when h/she joined the group.
 *
 * @since BuddyBoss 1.3.0
 *
 * @param int $group_id Group id.
 * @param int $user_id  User id.
 */
function bp_messages_add_user_to_group_message_thread( $group_id, $user_id ) {

	global $wpdb, $bp;

	// Add Member to group messages thread.
	if ( true === bp_disable_group_messages() && bp_is_active( 'messages' ) ) {

		$group_thread = (int) groups_get_groupmeta( (int) $group_id, 'group_message_thread' );

		$recipients = BP_Messages_Thread::get_recipients_for_thread( (int) $group_thread );
		$recipients = wp_list_pluck( $recipients, 'user_id' );
		if ( $group_thread > 0 && ! in_array( (int) $user_id, $recipients, true ) ) {

			$first_message     = BP_Messages_Thread::get_first_message( $group_thread );
			$message_users_ids = bp_messages_get_meta( $first_message->id, 'message_users_ids', true ); // users list.
			$message_users_ids = explode( ',', $message_users_ids );
			array_push( $message_users_ids, $user_id );

			bp_messages_update_meta( $first_message->id, 'message_users_ids', implode( ',', $message_users_ids ) );

			$wpdb->query( $wpdb->prepare( "INSERT INTO {$bp->messages->table_name_recipients} ( user_id, thread_id, unread_count ) VALUES ( %d, %d, 0 )", $user_id, $group_thread ) );

			remove_action( 'messages_message_sent', 'messages_notification_new_message', 10 );
			remove_action( 'messages_message_sent', 'bp_messages_message_sent_add_notification', 10 );
			$new_reply = messages_new_message(
				array(
					'thread_id'  => $group_thread,
					'sender_id'  => $user_id,
					'subject'    => '',
					'content'    => '<p> </p>',
					'date_sent'  => bp_core_current_time(),
					'error_type' => 'wp_error',
				)
			);
			add_action( 'messages_message_sent', 'messages_notification_new_message', 10 );
			add_action( 'messages_message_sent', 'bp_messages_message_sent_add_notification', 10 );

			$last_message = BP_Messages_Thread::get_last_message( $group_thread );
			bp_messages_update_meta( $last_message->id, 'group_message_group_joined', 'yes' );
			bp_messages_update_meta( $last_message->id, 'group_id', $group_id );
		}
	}

}

/**
 * Add member to Group thread when h/she joined the group.
 *
 * @since BuddyBoss 1.3.0
 *
 * @param int $group_id Group id.
 * @param int $user_id  User id.
 */
function bp_messages_remove_user_to_group_message_thread( $group_id, $user_id ) {

	global $wpdb, $bp;

	if ( true === bp_disable_group_messages() && bp_is_active( 'messages' ) ) {

		$group_thread = (int) groups_get_groupmeta( (int) $group_id, 'group_message_thread' );
		$recipients   = BP_Messages_Thread::get_recipients_for_thread( (int) $group_thread );
		$recipients   = wp_list_pluck( $recipients, 'user_id' );

		if ( $group_thread > 0 && in_array( (int) $user_id, $recipients, true ) ) {

			$first_message     = BP_Messages_Thread::get_first_message( $group_thread );
			$message_users_ids = bp_messages_get_meta( $first_message->id, 'message_users_ids', true ); // users list.
			$message_users_ids = explode( ',', $message_users_ids );
			if ( ( $key = array_search( $user_id, $message_users_ids ) ) !== false ) {
				unset( $message_users_ids[ $key ] );
			}

			bp_messages_update_meta( $first_message->id, 'message_users_ids', implode( ',', $message_users_ids ) );

			remove_action( 'messages_message_sent', 'messages_notification_new_message', 10 );
			remove_action( 'messages_message_sent', 'bp_messages_message_sent_add_notification', 10 );
			$new_reply = messages_new_message(
				array(
					'sender_id'  => $user_id,
					'thread_id'  => $group_thread,
					'subject'    => '',
					'content'    => '<p> </p>',
					'date_sent'  => bp_core_current_time(),
					'error_type' => 'wp_error',
				)
			);
			add_action( 'messages_message_sent', 'messages_notification_new_message', 10 );
			add_action( 'messages_message_sent', 'bp_messages_message_sent_add_notification', 10 );

			$last_message = BP_Messages_Thread::get_last_message( $group_thread );
			bp_messages_update_meta( $last_message->id, 'group_message_group_left', 'yes' );
			bp_messages_update_meta( $last_message->id, 'group_id', $group_id );
			$wpdb->query( $wpdb->prepare( "DELETE FROM {$bp->messages->table_name_recipients} WHERE user_id = %d AND thread_id = %d", $user_id, (int) $group_thread ) );
		}
	}
}

/**
 * Add message notification repair list item.
 *
 * @param array $repair_list Repair list.
 *
 * @since BuddyBoss 1.5.0
 * @return array Repair list items.
 */
function bp_messages_repair_items_unread_count( $repair_list ) {
	$repair_list[] = array(
		'bp-repair-messages-unread-count',
		esc_html__( 'Repair unread messages count', 'buddyboss' ),
		'bp_messages_admin_repair_unread_messages_count',
	);
	return $repair_list;
}

/**
 * Repair unread messages count.
 *
 * @since BuddyBoss 1.5.0
 */
function bp_messages_admin_repair_unread_messages_count() {
	global $wpdb;

	$offset           = isset( $_POST['offset'] ) ? (int) ( $_POST['offset'] ) : 0;
	$bp               = buddypress();
	$recipients_query = "SELECT DISTINCT thread_id FROM {$bp->messages->table_name_recipients} LIMIT 50 OFFSET $offset ";
	$recipients       = $wpdb->get_results( $recipients_query );

	if ( ! empty( $recipients ) ) {
		foreach ( $recipients as $recipient ) {
			$thread_id = (int) $recipient->thread_id;
			if ( ! empty( $thread_id ) ) {
				$is_valid = messages_is_valid_thread( $thread_id );
				if ( empty( $is_valid ) ) {
					messages_delete_thread( $thread_id, bp_loggedin_user_id() );
				}
			}
			$offset ++;
		}
		$records_updated = sprintf( __( '%s message threads updated successfully.', 'buddyboss' ), bp_core_number_format( $offset ) );

		return array(
			'status'  => 'running',
			'offset'  => $offset,
			'records' => $records_updated,
		);
	} else {
		return array(
			'status'  => 1,
			'message' => __( 'Repairing unread messages count &hellip; Complete!', 'buddyboss' ),
		);
	}
}

/**
 * Exclude current user and admin user when we open blocked member list.
 *
 * @since BuddyBoss 1.7.6
 *
 * @return string Exclude admin users from message block list.
 */
function bp_recipients_recipient_get_where_conditions_callback( $where_conditions, $r ) {
	if ( ! empty( $r['exclude_admin_user'] ) ) {
		$where_conditions['exclude_active_users'] = 'user_id NOT IN ( ' . implode( ', ', $r['exclude_admin_user'] ) . ' )';
	}
	return $where_conditions;
}

/**
 * Add nonce for the moderation when click on block member button.
 *
 * @param  array $params Get params.
 *
 * @return array $params Return params.
 */
function bp_core_get_js_strings_callback( $params ) {
	$params['nonce']['bp_moderation_content_nonce'] = wp_create_nonce( 'bp-moderation-content' );
	return $params;
}

/**
 * Register the messages notifications.
 *
 * @since BuddyBoss 1.9.3
 */
function bb_load_messages_notifications() {
	if ( class_exists( 'BP_Messages_Notification' ) ) {
		BP_Messages_Notification::instance();
	}
}

/**
 * Render the markup for the Messages section of Settings > Notifications.
 *
 * @since BuddyPress 1.0.0
 */
function messages_screen_notification_settings() {

	if ( bp_action_variables() ) {
		bp_do_404();
		return;
	}

	// Bail out if legacy method not enabled.
	if ( false === bb_enabled_legacy_email_preference() ) {
		return;
	}

	if ( ! $new_messages = bp_get_user_meta( bp_displayed_user_id(), 'notification_messages_new_message', true ) ) {
		$new_messages = 'yes';
	} ?>

	<table class="notification-settings" id="messages-notification-settings">
		<thead>
		<tr>
			<th class="icon"></th>
			<th class="title"><?php esc_html_e( 'Messages', 'buddyboss' ); ?></th>
			<th class="yes"><?php esc_html_e( 'Yes', 'buddyboss' ); ?></th>
			<th class="no"><?php esc_html_e( 'No', 'buddyboss' ); ?></th>
		</tr>
		</thead>

		<tbody>
		<tr id="messages-notification-settings-new-message">
			<td></td>
			<td><?php esc_html_e( 'A member sends you a new message', 'buddyboss' ); ?></td>
			<td class="yes">
				<div class="bp-radio-wrap">
					<input type="radio" name="notifications[notification_messages_new_message]" id="notification-messages-new-messages-yes" class="bs-styled-radio" value="yes" <?php checked( $new_messages, 'yes', true ); ?> />
					<label for="notification-messages-new-messages-yes"><span class="bp-screen-reader-text"><?php esc_html_e( 'Yes, send email', 'buddyboss' ); ?></span></label>
				</div>
			</td>
			<td class="no">
				<div class="bp-radio-wrap">
					<input type="radio" name="notifications[notification_messages_new_message]" id="notification-messages-new-messages-no" class="bs-styled-radio" value="no" <?php checked( $new_messages, 'no', true ); ?> />
					<label for="notification-messages-new-messages-no"><span class="bp-screen-reader-text"><?php esc_html_e( 'No, do not send email', 'buddyboss' ); ?></span></label>
				</div>
			</td>
		</tr>

		<?php

		/**
		 * Fires inside the closing </tbody> tag for messages screen notification settings.
		 *
		 * @since BuddyPress 1.0.0
		 */
		do_action( 'messages_screen_notification_settings' );
		?>
		</tbody>
	</table>

	<?php

}
