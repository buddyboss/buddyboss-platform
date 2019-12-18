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
add_action( 'groups_membership_accepted', 'bp_group_messages_groups_membership_accepted', 10, 3 );

add_action( 'groups_leave_group', 'bp_group_messages_remove_group_member_from_thread', 10, 2 );
add_action( 'groups_remove_member', 'bp_group_messages_remove_group_member_from_thread', 10, 2 );

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

	wp_redirect( $thread_url );
	exit();
}

/**
 * Add new message to a existing group thread when someone membership is accepted in group.
 *
 * @since BuddyBoss 1.2.3
 *
 * @param $user_id
 * @param $group_id
 * @param $accepted
 */
function bp_group_messages_groups_membership_accepted( $user_id, $group_id, $accepted ) {

	global $wpdb, $bp, $messages_template;

	$sql     = "SELECT * FROM " . $bp->messages->table_name_meta . " WHERE `meta_key` = 'group_id' AND `meta_value` = %s";
	$sql     = $wpdb->prepare( $sql, $group_id );
	$results = $wpdb->get_results( $sql );

	foreach ( $results as $result ) {

		$message_id              = $result->message_id;
		$group                   = bp_messages_get_meta( $message_id, 'group_id', true ); // group id
		$message_users           = bp_messages_get_meta( $message_id, 'group_message_users', true ); // all - individual
		$message_type            = bp_messages_get_meta( $message_id, 'group_message_type', true ); // open - private
		$thread_type             = bp_messages_get_meta( $message_id, 'group_message_thread_type', true ); // new - reply
		$message_users_ids       = bp_messages_get_meta( $message_id, 'message_users_ids', true ); // users list
		$message_from            = bp_messages_get_meta( $message_id, 'message_from', true ); // group
		$group_message_thread_id = bp_messages_get_meta( $message_id, 'group_message_thread_id', true ); // group

		if ( $message_from &&
		     $thread_type &&
		     $message_type &&
		     $message_users &&
		     $group &&
		     $message_users_ids &&
		     (int) $group === (int) $group_id &&
		     'all' === $message_users &&
		     'open' === $message_type &&
		     'new' === $thread_type &&
		     'group' === $message_from ) {

			$message_users_ids = explode( ',', $message_users_ids );
			array_push( $message_users_ids, $user_id );
			$group_name        = bp_get_group_name( groups_get_group( $group ) );
			$text              = sprintf( __( 'Joined "%s" ', 'buddyboss' ), $group_name );

			bp_messages_update_meta( $message_id, 'message_users_ids', implode( ',', $message_users_ids ) );

			$wpdb->query( $wpdb->prepare( "INSERT INTO {$bp->messages->table_name_recipients} ( user_id, thread_id, unread_count ) VALUES ( %d, %d, 0 )", $user_id, $group_message_thread_id ) );

			$new_reply = messages_new_message( array(
				'thread_id' => $group_message_thread_id,
				'sender_id' => $user_id,
				'subject'   => '',
				'content'   => '<p> </p>',
				'date_sent' => $date_sent = bp_core_current_time(),
				'error_type' => 'wp_error',
			) );

			if ( ! is_wp_error( $new_reply ) && true === is_int( ( int ) $new_reply ) ) {
				if ( bp_has_message_threads( array( 'include' => $new_reply ) ) ) {
					while ( bp_message_threads() ) {
						bp_message_thread();
						$last_message_id = (int) $messages_template->thread->last_message_id;
						bp_messages_update_meta( $last_message_id, 'group_message_group_joined', 'yes' );
						bp_messages_update_meta( $last_message_id, 'group_id', $group_id );
					}
				}
			}
		}
	}
}

/**
 * Add new message to a existing group thread when someone join in group.
 *
 * @since BuddyBoss 1.2.3
 *
 * @param $group_id
 * @param $user_id
 */
function bp_group_messages_join_new_member( $group_id, $user_id ) {

	global $wpdb, $bp, $messages_template;

	$sql     = "SELECT * FROM " . $bp->messages->table_name_meta . " WHERE `meta_key` = 'group_id' AND `meta_value` = %s";
	$sql     = $wpdb->prepare( $sql, $group_id );
	$results = $wpdb->get_results( $sql );

	foreach ( $results as $result ) {

		$message_id              = $result->message_id;
		$group                   = bp_messages_get_meta( $message_id, 'group_id', true ); // group id
		$message_users           = bp_messages_get_meta( $message_id, 'group_message_users', true ); // all - individual
		$message_type            = bp_messages_get_meta( $message_id, 'group_message_type', true ); // open - private
		$thread_type             = bp_messages_get_meta( $message_id, 'group_message_thread_type', true ); // new - reply
		$message_users_ids       = bp_messages_get_meta( $message_id, 'message_users_ids', true ); // users list
		$message_from            = bp_messages_get_meta( $message_id, 'message_from', true ); // group
		$group_message_thread_id = bp_messages_get_meta( $message_id, 'group_message_thread_id', true ); // group

		if ( $message_from &&
		     $thread_type &&
		     $message_type &&
		     $message_users &&
		     $group &&
		     $message_users_ids &&
		     (int) $group === (int) $group_id &&
		     'all' === $message_users &&
		     'open' === $message_type &&
		     'new' === $thread_type &&
		     'group' === $message_from ) {

			$message_users_ids = explode( ',', $message_users_ids );
			array_push( $message_users_ids, $user_id );
			$group_name        = bp_get_group_name( groups_get_group( $group ) );
			$text              = sprintf( __( 'Joined "%s" ', 'buddyboss' ), $group_name );

			bp_messages_update_meta( $message_id, 'message_users_ids', implode( ',', $message_users_ids ) );

			$wpdb->query( $wpdb->prepare( "INSERT INTO {$bp->messages->table_name_recipients} ( user_id, thread_id, unread_count ) VALUES ( %d, %d, 0 )", $user_id, $group_message_thread_id ) );

			$new_reply = messages_new_message( array(
				'thread_id' => $group_message_thread_id,
				'sender_id' => $user_id,
				'subject'   => '',
				'content'   => '<p> </p>',
				'date_sent' => $date_sent = bp_core_current_time(),
				'error_type' => 'wp_error',
			) );

			if ( ! is_wp_error( $new_reply ) && true === is_int( ( int ) $new_reply ) ) {
				if ( bp_has_message_threads( array( 'include' => $new_reply ) ) ) {
					while ( bp_message_threads() ) {
						bp_message_thread();
						$last_message_id = (int) $messages_template->thread->last_message_id;
						bp_messages_update_meta( $last_message_id, 'group_message_group_joined', 'yes' );
						bp_messages_update_meta( $last_message_id, 'group_id', $group_id );
					}
				}
			}
		}
	}
}

/**
 * Add new message to a existing group thread when someone remove from group.
 *
 * @since BuddyBoss 1.2.3
 *
 * @param $group_id
 * @param $user_id
 */
function bp_group_messages_remove_group_member_from_thread( $group_id, $user_id ) {

	global $wpdb, $bp, $messages_template;

	$sql     = "SELECT * FROM " . $bp->messages->table_name_meta . " WHERE `meta_key` = 'group_id' AND `meta_value` = %s";
	$sql     = $wpdb->prepare( $sql, $group_id );
	$results = $wpdb->get_results( $sql );

	foreach ( $results as $result ) {

		$message_id              = $result->message_id;
		$group                   = bp_messages_get_meta( $message_id, 'group_id', true ); // group id
		$message_users           = bp_messages_get_meta( $message_id, 'group_message_users', true ); // all - individual
		$message_type            = bp_messages_get_meta( $message_id, 'group_message_type', true ); // open - private
		$thread_type             = bp_messages_get_meta( $message_id, 'group_message_thread_type', true ); // new - reply
		$message_users_ids       = bp_messages_get_meta( $message_id, 'message_users_ids', true ); // users list
		$message_from            = bp_messages_get_meta( $message_id, 'message_from', true ); // group
		$group_message_thread_id = bp_messages_get_meta( $message_id, 'group_message_thread_id', true ); // group

		if ( $message_from &&
		     $thread_type &&
		     $message_type &&
		     $message_users &&
		     $group &&
		     $message_users_ids &&
		     (int) $group === (int) $group_id &&
		     'new' === $thread_type &&
		     'group' === $message_from

		) {

			$message_users_ids = explode( ',', $message_users_ids );
			$group_name        = bp_get_group_name( groups_get_group( $group ) );
			$text              = sprintf( __( 'Left "%s" ', 'buddyboss' ), $group_name );
			if ((  $key = array_search( $user_id, $message_users_ids ) ) !== false ) {
				unset( $message_users_ids[$key] );
			}
			bp_messages_update_meta( $message_id, 'message_users_ids', implode( ',', $message_users_ids ) );
			if ( 'all' === $message_users ) {
				$new_reply = messages_new_message( array(
					'sender_id'  => $user_id,
					'thread_id'  => $group_message_thread_id,
					'subject'    => '',
					'content'    => '<p> </p>',
					'date_sent'  => $date_sent = bp_core_current_time(),
					'error_type' => 'wp_error',
				) );
				if ( ! is_wp_error( $new_reply ) && true === is_int( ( int ) $new_reply ) ) {
					if ( bp_has_message_threads( array( 'include' => $new_reply ) ) ) {
						while ( bp_message_threads() ) {
							bp_message_thread();
							$last_message_id = (int) $messages_template->thread->last_message_id;
							bp_messages_update_meta( $last_message_id, 'group_message_group_left', 'yes' );
							bp_messages_update_meta( $last_message_id, 'group_id', $group_id );
						}
					}
				}
			}
			$wpdb->query( $wpdb->prepare( "DELETE FROM {$bp->messages->table_name_recipients} WHERE user_id = %d AND thread_id = %d", $user_id, (int) $group_message_thread_id ) );
		}
	}
}

/**
 * This function will get all the messages into the thread list because in thread it's fetching last 10
 * messages and if in all  10 messages if there is no content then in thread it will showing blank to fix this
 * we need maximum messages and if we find the text then will skip on thread loop.
 *
 * @since BuddyBoss 1.2.3
 *
 * @param $total
 *
 * @return int
 */
function bp_threads_messages_show_more_messages( $total )  {

	$total = 99999;

	return $total;
}

