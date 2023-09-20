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

add_filter( 'bp_get_message_notice_text', 'bb_autop' );
add_filter( 'bp_get_the_thread_message_content', 'bb_autop' );
add_filter( 'bp_get_message_thread_content', 'bb_autop' );

add_filter( 'bp_get_message_thread_excerpt', 'bb_messages_make_nofollow_filter' );
add_filter( 'bp_get_the_thread_message_content', 'bb_messages_make_nofollow_filter' );
add_filter( 'bp_get_message_thread_content', 'bb_messages_make_nofollow_filter' );

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

add_filter( 'bp_messages_message_validated_content', 'bb_check_is_message_content_empty', 10, 3 );

add_filter( 'bp_core_get_js_strings', 'bp_core_get_js_strings_callback', 10, 1 );

// Load Messages Notifications.
add_action( 'bp_messages_includes', 'bb_load_messages_notifications', 20 );
add_action( 'bp_notification_settings', 'messages_screen_notification_settings', 2 );

// Hide archived thread notifications.
add_filter( 'bp_notifications_get_where_conditions', 'bb_messages_hide_archived_notifications', 10, 2 );

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
	$user_id   = bp_core_get_userid_from_nicename( $recipient );

	$thread_id = BP_Messages_Message::get_existing_thread( array( $user_id ), bp_loggedin_user_id() );
	if ( ! $thread_id ) {
		return;
	}

	$is_thread_archived = messages_is_valid_archived_thread( $thread_id, bp_loggedin_user_id() );

	if ( ! $is_thread_archived ) {
		$thread_url = esc_url( bp_core_get_user_domain( bp_loggedin_user_id() ) . bp_get_messages_slug() . '/view/' . $thread_id . '/' );
	} else {
		$thread_url = esc_url( bb_get_message_archived_thread_view_link( $thread_id ) );
	}

	wp_safe_redirect( $thread_url );
	exit();
}

/**
 * Catch links in messages text so target="_blank" and rel=nofollow can be added.
 *
 * @since BuddyBoss 2.2.4
 *
 * @param string $text Messages text.
 *
 * @return string $text Text with rel=nofollow added to any links.
 */
function bb_messages_make_nofollow_filter( $text ) {
	return preg_replace_callback( '|<a (.+?)>|i', 'bb_messages_make_nofollow_filter_callback', $text );
}

/**
 * Add rel=nofollow to a link.
 *
 * @since BuddyBoss 2.2.4
 *
 * @param array $matches Items matched by preg_replace_callback() in bb_messages_make_nofollow_filter_callback().
 *
 * @return string $text Link with rel=nofollow added.
 */
function bb_messages_make_nofollow_filter_callback( $matches ) {
	$text = $matches[1];
	$text = str_replace( array( ' rel="nofollow"', " rel='nofollow'" ), '', $text );

	// Extract URL from href.
	preg_match_all( '#\bhttps?://[^,\s()<>]+(?:\([\w\d]+\)|([^,[:punct:]\s]|/))#', $text, $match );

	$url_host      = ( isset( $match[0] ) && isset( $match[0][0] ) ? wp_parse_url( $match[0][0], PHP_URL_HOST ) : '' );
	$base_url_host = wp_parse_url( site_url(), PHP_URL_HOST );

	// If site link then nothing to do.
	if ( $url_host === $base_url_host || empty( $url_host ) ) {
		return "<a $text rel=\"nofollow\">";
		// Else open in new tab.
	} else {
		return "<a target='_blank' $text rel=\"nofollow\">";
	}
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

		$message_users_ids = bp_messages_get_meta( $first_message->id, 'message_users_ids', true ); // users list.
		$message_users_ids = explode( ',', $message_users_ids );
		array_push( $message_users_ids, $user_id );
		$group_name = bp_get_group_name( groups_get_group( $group_id ) );
		$text       = sprintf( __( 'Joined "%s" ', 'buddyboss' ), $group_name );

		bp_messages_update_meta( $first_message->id, 'message_users_ids', implode( ',', $message_users_ids ) );

		$wpdb->query( $wpdb->prepare( "INSERT INTO {$bp->messages->table_name_recipients} ( user_id, thread_id, unread_count ) VALUES ( %d, %d, 0 )", $user_id, $group_thread ) );

		if ( bb_is_last_message_group_join_message( $group_thread, $user_id ) ) {
			return;
		}

		remove_action( 'messages_message_sent', 'messages_notification_new_message', 10 );
		remove_action( 'messages_message_sent', 'bp_messages_message_sent_add_notification', 10 );
		$new_reply = messages_new_message(
			array(
				'thread_id'  => $group_thread,
				'sender_id'  => $user_id,
				'subject'    => false,
				'content'    => '<p> </p>',
				'date_sent'  => $date_sent = bp_core_current_time(),
				'error_type' => 'wp_error',
				'mark_read'  => true,
			)
		);

		add_action( 'messages_message_sent', 'messages_notification_new_message', 10 );
		add_action( 'messages_message_sent', 'bp_messages_message_sent_add_notification', 10 );

		$last_message = BP_Messages_Thread::get_last_message( $group_thread );
		bp_messages_update_meta( $last_message->id, 'group_id', $group_id );
		$joined_user = array(
			'user_id' => $user_id,
			'time'    => bp_core_current_time(),
		);
		bp_messages_update_meta( $last_message->id, 'group_message_group_joined_users', array( $joined_user ) );
		bp_messages_update_meta( $last_message->id, 'group_message_group_joined', 'yes' );
		bp_messages_update_meta( $last_message->id, 'group_message_users', 'all' );
		bp_messages_update_meta( $last_message->id, 'group_message_type', 'open' );
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
	$thread_action           = ( isset( $_POST ) && isset( $_POST['action'] ) && '' !== $_POST['action'] ) ? trim( $_POST['action'] ) : ''; // new - reply.

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
		bp_messages_update_meta( $message->id, 'message_users_ids', $message_meta_users_list );
		bp_messages_update_meta( $message->id, 'group_message_thread_id', $message->thread_id );
		bp_messages_update_meta( $message->id, 'thread_action', $thread_action );
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
			$message_from = 'personal';
			if ( 'all' === $message_users ) {
				$message_from = 'group';
			}
			bp_messages_update_meta( $message->id, 'message_from', $message_from );
			bp_messages_update_meta( $message->id, 'group_message_thread_id', $message->thread_id );
			bp_messages_update_meta( $message->id, 'thread_action', $thread_action );
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

		if ( bb_is_last_message_group_join_message( $group_thread, $user_id ) ) {
			return;
		}

		remove_action( 'messages_message_sent', 'messages_notification_new_message', 10 );
		remove_action( 'messages_message_sent', 'bp_messages_message_sent_add_notification', 10 );
		$new_reply = messages_new_message(
			array(
				'thread_id'  => $group_thread,
				'sender_id'  => $user_id,
				'subject'    => false,
				'content'    => '<p> </p>',
				'date_sent'  => bp_core_current_time(),
				'error_type' => 'wp_error',
				'mark_read'  => true,
			)
		);
		add_action( 'messages_message_sent', 'messages_notification_new_message', 10 );
		add_action( 'messages_message_sent', 'bp_messages_message_sent_add_notification', 10 );

		$last_message = BP_Messages_Thread::get_last_message( $group_thread );
		bp_messages_update_meta( $last_message->id, 'group_message_group_joined', 'yes' );
		bp_messages_update_meta( $last_message->id, 'group_message_users', 'all' );
		bp_messages_update_meta( $last_message->id, 'group_message_type', 'open' );
		bp_messages_update_meta( $last_message->id, 'group_id', $group_id );
		$joined_user = array(
			'user_id' => $user_id,
			'time'    => bp_core_current_time(),
		);
		bp_messages_update_meta( $last_message->id, 'group_message_group_joined_users', array( $joined_user ) );
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

		if ( bb_is_last_message_group_left_message( $group_thread, $user_id ) ) {
			return;
		}

		remove_action( 'messages_message_sent', 'messages_notification_new_message', 10 );
		remove_action( 'messages_message_sent', 'bp_messages_message_sent_add_notification', 10 );
		$new_reply = messages_new_message(
			array(
				'sender_id'  => $user_id,
				'thread_id'  => $group_thread,
				'subject'    => false,
				'content'    => '<p> </p>',
				'date_sent'  => bp_core_current_time(),
				'error_type' => 'wp_error',
				'mark_read'  => true,
			)
		);
		add_action( 'messages_message_sent', 'messages_notification_new_message', 10 );
		add_action( 'messages_message_sent', 'bp_messages_message_sent_add_notification', 10 );

		$last_message = BP_Messages_Thread::get_last_message( $group_thread );
		bp_messages_update_meta( $last_message->id, 'group_id', $group_id );
		bp_messages_update_meta( $last_message->id, 'group_message_group_left', 'yes' );
		bp_messages_update_meta( $last_message->id, 'group_message_users', 'all' );
		bp_messages_update_meta( $last_message->id, 'group_message_type', 'open' );
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$bp->messages->table_name_recipients} WHERE user_id = %d AND thread_id = %d", $user_id, (int) $group_thread ) );
		$left_user = array(
			'user_id' => $user_id,
			'time'    => bp_core_current_time(),
		);
		bp_messages_update_meta( $last_message->id, 'group_message_group_left_users', array( $left_user ) );

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

		if ( bb_is_last_message_group_join_message( $group_thread, $user_id ) ) {
			return;
		}

		remove_action( 'messages_message_sent', 'messages_notification_new_message', 10 );
		remove_action( 'messages_message_sent', 'bp_messages_message_sent_add_notification', 10 );
		$new_reply = messages_new_message(
			array(
				'thread_id'  => $group_thread,
				'sender_id'  => $user_id,
				'subject'    => false,
				'content'    => '<p> </p>',
				'date_sent'  => $date_sent = bp_core_current_time(),
				'error_type' => 'wp_error',
				'mark_read'  => true,
			)
		);

		add_action( 'messages_message_sent', 'messages_notification_new_message', 10 );
		add_action( 'messages_message_sent', 'bp_messages_message_sent_add_notification', 10 );

		$last_message = BP_Messages_Thread::get_last_message( $group_thread );
		bp_messages_update_meta( $last_message->id, 'group_message_group_joined', 'yes' );
		bp_messages_update_meta( $last_message->id, 'group_message_users', 'all' );
		bp_messages_update_meta( $last_message->id, 'group_message_type', 'open' );
		bp_messages_update_meta( $last_message->id, 'group_id', $group_id );
		$joined_user = array(
			'user_id' => $user_id,
			'time'    => bp_core_current_time(),
		);
		bp_messages_update_meta( $last_message->id, 'group_message_group_joined_users', array( $joined_user ) );
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
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$bp->messages->table_name_recipients} WHERE user_id = %d AND thread_id = %d", $user_id, (int) $group_thread ) );

		/**
		 * Fired action after user banned for the message.
		 *
		 * @since BuddyBoss 2.1.4
		 *
		 * @param int $group_thread Group thread ID.
		 * @param int $user_id      User id.
		 * @param int $group_id     Group id.
		 */
		do_action( 'bb_group_messages_banned_member', $group_thread, $user_id, $group_id );
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
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$bp->messages->table_name_recipients} WHERE user_id = %d AND thread_id = %d", $user_id, (int) $group_thread ) );

		/**
		 * Fired action after user banned for the message.
		 *
		 * @since BuddyBoss 2.1.4
		 *
		 * @param int $group_thread Group thread ID.
		 * @param int $user_id      User id.
		 * @param int $group_id     Group id.
		 */
		do_action( 'bb_group_messages_banned_member', $group_thread, $user_id, $group_id );

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

		/**
		 * Fired action after user un-banned for the message.
		 *
		 * @since BuddyBoss 2.1.4
		 *
		 * @param int $group_thread Group thread ID.
		 * @param int $user_id      User id.
		 * @param int $group_id     Group id.
		 */
		do_action( 'bb_group_messages_unbanned_member', $group_thread, $user_id, $group_id );

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

			if ( bb_is_last_message_group_join_message( $group_thread, $user_id ) ) {
				return;
			}

			remove_action( 'messages_message_sent', 'messages_notification_new_message', 10 );
			remove_action( 'messages_message_sent', 'bp_messages_message_sent_add_notification', 10 );
			$new_reply = messages_new_message(
				array(
					'thread_id'  => $group_thread,
					'sender_id'  => $user_id,
					'subject'    => false,
					'content'    => '<p> </p>',
					'date_sent'  => bp_core_current_time(),
					'error_type' => 'wp_error',
					'mark_read'  => true,
				)
			);
			add_action( 'messages_message_sent', 'messages_notification_new_message', 10 );
			add_action( 'messages_message_sent', 'bp_messages_message_sent_add_notification', 10 );

			$last_message = BP_Messages_Thread::get_last_message( $group_thread );
			bp_messages_update_meta( $last_message->id, 'group_message_group_joined', 'yes' );
			bp_messages_update_meta( $last_message->id, 'group_message_users', 'all' );
			bp_messages_update_meta( $last_message->id, 'group_message_type', 'open' );
			bp_messages_update_meta( $last_message->id, 'group_id', $group_id );
			$joined_user = array(
				'user_id' => $user_id,
				'time'    => bp_core_current_time(),
			);
			bp_messages_update_meta( $last_message->id, 'group_message_group_joined_users', array( $joined_user ) );
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

			if ( bb_is_last_message_group_left_message( $group_thread, $user_id ) ) {
				return;
			}

			remove_action( 'messages_message_sent', 'messages_notification_new_message', 10 );
			remove_action( 'messages_message_sent', 'bp_messages_message_sent_add_notification', 10 );
			$new_reply = messages_new_message(
				array(
					'sender_id'  => $user_id,
					'thread_id'  => $group_thread,
					'subject'    => false,
					'content'    => '<p> </p>',
					'date_sent'  => bp_core_current_time(),
					'error_type' => 'wp_error',
					'mark_read'  => true,
				)
			);
			add_action( 'messages_message_sent', 'messages_notification_new_message', 10 );
			add_action( 'messages_message_sent', 'bp_messages_message_sent_add_notification', 10 );

			$last_message = BP_Messages_Thread::get_last_message( $group_thread );
			bp_messages_update_meta( $last_message->id, 'group_message_group_left', 'yes' );
			bp_messages_update_meta( $last_message->id, 'group_id', $group_id );
			bp_messages_update_meta( $last_message->id, 'group_message_users', 'all' );
			bp_messages_update_meta( $last_message->id, 'group_message_type', 'open' );

			$wpdb->query( $wpdb->prepare( "DELETE FROM {$bp->messages->table_name_recipients} WHERE user_id = %d AND thread_id = %d", $user_id, (int) $group_thread ) );
			$left_user = array(
				'user_id' => $user_id,
				'time'    => bp_core_current_time(),
			);
			bp_messages_update_meta( $last_message->id, 'group_message_group_left_users', array( $left_user ) );
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
		$where_conditions['exclude_active_users'] = 'r.user_id NOT IN ( ' . implode( ', ', $r['exclude_admin_user'] ) . ' )';
	}
	return $where_conditions;
}

/**
 * Function will check content is empty or not for the media, document and gif.
 * Return true if content is empty with the media, document and gif object and allow empty content in DB for the media, document and gif.
 *
 * @since BuddyBoss 2.0.4
 *
 * @param bool         $validated_content True if message is valid, false otherwise.
 * @param string       $content           Message content.
 * @param array|object $post              Request object.
 *
 * @return bool
 */
function bb_check_is_message_content_empty( $validated_content, $content, $post ) {
	if ( ! empty( $post['content'] ) || ! empty( $post['message_content'] ) || ! empty( $content ) ) {
		return true;
	}

	return false;
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
	$params['current']['message_user_id']           = bp_loggedin_user_id();

	$archived_threads_ids = array();

	if ( is_user_logged_in() ) {
		$hidden_threads = BP_Messages_Thread::get_current_threads_for_user(
			array(
				'fields'      => 'ids',
				'user_id'     => bp_loggedin_user_id(),
				'is_hidden'   => true,
				'thread_type' => 'archived',
			)
		);

		if ( ! empty( $hidden_threads ) ) {
			$archived_threads_ids = $hidden_threads['threads'];
		}
	}

	$params['archived_threads'] = $archived_threads_ids;

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

/**
 * Validate the thread is group thread or not.
 *
 * @since BuddyBoss 2.1.4
 *
 * @param int $thread_id Thread ID.
 *
 * @return int
 */
function bb_messages_validate_groups_thread( $thread_id ) {

	if ( false === bp_disable_group_messages() ) {
		$first_message           = BP_Messages_Thread::get_first_message( $thread_id );
		$group_message_thread_id = bp_messages_get_meta( $first_message->id, 'group_message_thread_id', true ); // group.
		$message_users           = bp_messages_get_meta( $first_message->id, 'group_message_users', true ); // all - individual.
		$message_type            = bp_messages_get_meta( $first_message->id, 'group_message_type', true ); // open - private.
		$message_from            = bp_messages_get_meta( $first_message->id, 'message_from', true ); // group.

		if ( 'group' === $message_from && $group_message_thread_id && 'all' === $message_users && 'open' === $message_type ) {
			$thread_id = 0;
		}
	}

	return $thread_id;
}

add_filter( 'bb_messages_validate_thread', 'bb_messages_validate_groups_thread' );

/**
 * Display the html for the notification preferences actions.
 *
 * @since BuddyBoss 2.1.4
 *
 * @return void
 */
function bb_messages_compose_action_sub_nav() {
	?>
	<div class="bb_more_options message-action-options">
		<a href="#" class="bb_more_options_action" data-action="more_options">
			<i class="bb-icon-f bb-icon-ellipsis-h"></i>
		</a>
		<ul class="bb_more_options_list message_action__list">
			<li class="archived-messages">
				<a href="<?php bb_messages_archived_url(); ?>" class="archived-page" data-action="more_options"><?php echo esc_html__( 'Archived messages', 'buddyboss' ); ?></a>
			</li>
			<?php
			if ( bp_is_user_messages() && bp_is_active( 'notifications' ) ) {
				$settings_slug = function_exists( 'bp_get_settings_slug' ) ? bp_get_settings_slug() : 'settings';
				$settings_link = bp_core_get_user_domain( bp_loggedin_user_id() ) . $settings_slug . '/notifications/';
				$class         = function_exists( 'bb_enabled_legacy_email_preference' ) && false === bb_enabled_legacy_email_preference() ? 'notification_preferences' : 'email_preferences';
				$title         = function_exists( 'bb_enabled_legacy_email_preference' ) && false === bb_enabled_legacy_email_preference() ? __( 'Notification preferences', 'buddyboss' ) : __( 'Email preferences', 'buddyboss' );
				?>
				<li class="<?php echo esc_attr( $class ); ?>">
					<a href="<?php echo esc_url( $settings_link ); ?>" data-action="more_options"><?php echo esc_html( $title ); ?></a>
				</li>
			<?php } ?>
		</ul>
	</div>
	<?php
}
add_action( 'bb_nouveau_after_nav_link_compose-action', 'bb_messages_compose_action_sub_nav' );

/**
 * Function to exclude the archived notification for messages.
 *
 * @since BuddyBoss 2.1.4
 *
 * @param array $where_conditions Where clause to get notifications.
 * @param array $args             Parsed arguments to get notifications.
 *
 * @return array
 */
function bb_messages_hide_archived_notifications( $where_conditions, $args ) {
	global $wpdb, $bp;

	if ( is_user_logged_in() ) {

		// The user_id.
		if ( ! empty( $args['user_id'] ) ) {
			$user_id_in = implode( ',', wp_parse_id_list( $args['user_id'] ) );
		} else {
			$user_id_in = bp_loggedin_user_id();
		}

		$messages_query = $wpdb->prepare( "SELECT DISTINCT m.id FROM {$bp->messages->table_name_recipients} r INNER JOIN {$bp->messages->table_name_messages} m ON m.thread_id = r.thread_id WHERE r.user_id IN ({$user_id_in}) AND r.is_deleted = %d AND r.is_hidden = %d", 0, 1 );

		$where_conditions['archived_exclude'] = "( item_id NOT IN ({$messages_query}) OR component_name = 'messages' )";
	}

	return $where_conditions;
}

/**
 * Schedule an event on change notification settings.
 *
 * @since BuddyBoss 2.1.4
 */
function bb_schedule_event_on_update_notification_settings() {

	// phpcs:ignore WordPress.Security.NonceVerification.Missing
	if ( true === bb_enabled_legacy_email_preference() || ! bp_current_user_can( 'bp_moderate' ) || ! isset( $_POST['time_delay_email_notification'] ) ) {
		return;
	}

	$old_scheduled_time                  = bb_get_delay_email_notifications_time();
	$new_scheduled_time                  = (int) sanitize_text_field( wp_unslash( $_POST['time_delay_email_notification'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
	$is_enabled_delay_notification_after = isset( $_POST['delay_email_notification'] ) ? sanitize_text_field( wp_unslash( $_POST['delay_email_notification'] ) ) : false; // phpcs:ignore WordPress.Security.NonceVerification.Missing

	if (
		! empty( $old_scheduled_time ) &&
		(
			( $is_enabled_delay_notification_after && $old_scheduled_time !== $new_scheduled_time ) ||
			( ! $is_enabled_delay_notification_after )
		)
	) {
		$old_schedule_found = bb_get_delay_notification_time_by_minutes( $old_scheduled_time );
		// Un-schedule the scheduled event.
		if ( ! empty( $old_schedule_found ) ) {
			$timestamp = wp_next_scheduled( 'bb_digest_email_notifications_hook' );
			if ( $timestamp ) {
				wp_unschedule_event( $timestamp, 'bb_digest_email_notifications_hook' );
			}
		}
	}

	if ( $is_enabled_delay_notification_after ) {

		$new_schedule_found = bb_get_delay_notification_time_by_minutes( $new_scheduled_time );
		// Schedule an action if it's not already scheduled.
		if ( ! empty( $new_schedule_found ) ) {
			bp_core_schedule_cron( 'digest_email_notifications', 'bb_digest_message_email_notifications', $new_schedule_found['schedule_key'] );
		}
	}
}
add_action( 'bp_init', 'bb_schedule_event_on_update_notification_settings', 2 );

/**
 * Prepare the email notification content.
 *
 * @since BuddyBoss 2.1.4
 */
function bb_digest_message_email_notifications() {
	global $wpdb;
	$bp_prefix = bp_core_get_table_prefix();

	// Get all defined time.
	$db_delay_time = bb_get_delay_email_notifications_time();

	if ( ! empty( $db_delay_time ) ) {
		$get_delay_time_array = bb_get_delay_notification_time_by_minutes( $db_delay_time );

		if ( ! empty( $get_delay_time_array ) && $db_delay_time === $get_delay_time_array['value'] ) {

			$current_date = bp_core_current_time();
			$start_date   = wp_date( 'Y-m-d H:i:s', strtotime( $current_date . ' -' . $db_delay_time . ' minutes' ), new DateTimeZone( 'UTC' ) );

			$results = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT m.*, r.user_id, r.unread_count FROM `{$bp_prefix}bp_messages_messages` AS m LEFT JOIN `{$bp_prefix}bp_messages_recipients` AS r ON m.thread_id = r.thread_id LEFT JOIN `{$bp_prefix}bp_messages_meta` AS meta1 ON ( m.id = meta1.message_id AND meta1.meta_key = 'bb_sent_digest_email' ) WHERE m.date_sent >= %s AND m.date_sent <= %s AND r.unread_count > %d AND r.is_deleted = %d AND m.sender_id != r.user_id AND r.is_hidden = %d AND meta1.message_id IS NULL ORDER BY m.thread_id, m.id ASC",
					$start_date,
					$current_date,
					0,
					0,
					0
				)
			);

			$threads = array();
			if ( ! empty( $results ) ) {
				foreach ( $results as $unread_message ) {

					// Check the message for group joined.
					$is_left_message = bp_messages_get_meta( $unread_message->id, 'group_message_group_joined' );
					if ( 'yes' === $is_left_message ) {
						continue;
					}

					// Check the message for group left.
					$is_left_message = bp_messages_get_meta( $unread_message->id, 'group_message_group_left' );
					if ( 'yes' === $is_left_message ) {
						continue;
					}

					if ( $unread_message->sender_id === $unread_message->user_id ) {
						continue;
					}

					$threads[ $unread_message->thread_id ]['thread_id'] = $unread_message->thread_id;

					// Set messages.
					$threads[ $unread_message->thread_id ]['recipients'][ $unread_message->user_id ][] = array(
						'message_id'    => $unread_message->id,
						'sender_id'     => $unread_message->sender_id,
						'recipients_id' => $unread_message->user_id,
						'message'       => $unread_message->message,
						'subject'       => $unread_message->subject,
						'thread_id'     => $unread_message->thread_id,
					);

					// Save meta to sent unread digest email notifications.
					bp_messages_update_meta( $unread_message->id, 'bb_sent_digest_email', 'yes' );
				}
			}

			if ( ! empty( $threads ) ) {
				foreach ( $threads as $thread ) {

					if ( empty( $thread['recipients'] ) ) {
						continue;
					}

					// check if it has enough recipients to use batch emails.
					$min_count_recipients = function_exists( 'bb_email_queue_has_min_count' ) && bb_email_queue_has_min_count( (array) $thread['recipients'] );

					if ( function_exists( 'bb_is_email_queue' ) && bb_is_email_queue() && $min_count_recipients ) {
						global $bb_background_updater;

						$chunk_recipient_array = array_chunk( $thread['recipients'], bb_get_email_queue_min_count() );

						if ( ! empty( $chunk_recipient_array ) ) {
							foreach ( $chunk_recipient_array as $chunk_recipient ) {
								$bb_background_updater->data(
									array(
										'type'     => 'email',
										'group'    => 'digest_email_messages',
										'data_id'  => $thread['thread_id'],
										'priority' => 5,
										'callback' => 'bb_render_digest_messages_template',
										'args'     => array(
											$chunk_recipient,
											$thread['thread_id'],
										),
									),
								);
								$bb_background_updater->save();
							}
							$bb_background_updater->dispatch();
						}
					} else {
						bb_render_digest_messages_template( $thread['recipients'], $thread['thread_id'] );
					}
				}
			}
		}
	}
}
add_action( 'bb_digest_email_notifications_hook', 'bb_digest_message_email_notifications' );

/**
 * Function will fetch only those message recipients which is available in groups.
 * .
 * @since BuddyBoss 2.1.4
 *
 * @param $sql
 * @param $r
 *
 * @return string
 */
function bb_recipients_recipient_get_join_sql_with_group_members( $sql, $r ) {
	global $wpdb;
	$sql .= ' JOIN ' . $wpdb->base_prefix . 'bp_groups_members gm ON ( gm.user_id = r.user_id )';
	return $sql;
}

