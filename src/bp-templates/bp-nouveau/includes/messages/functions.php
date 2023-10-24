<?php
/**
 * Messages functions
 *
 * @since BuddyPress 3.0.0
 * @version 3.1.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Enqueue styles for the Messages UI (mentions).
 *
 * @since BuddyPress 3.0.0
 *
 * @param array $styles Optional. The array of styles to enqueue.
 *
 * @return array The same array with the specific messages styles.
 */
function bp_nouveau_messages_enqueue_styles( $styles = array() ) {
	if ( ! bp_is_user_messages() ) {
		return $styles;
	}

	return array_merge(
		$styles,
		array(
			'bp-nouveau-messages-at' => array(
				'file'         => buddypress()->plugin_url . 'bp-core/css/mentions%1$s%2$s.css',
				'dependencies' => array( 'bp-nouveau' ),
				'version'      => bp_get_version(),
			),
		)
	);
}

/**
 * Register Scripts for the Messages component
 *
 * @since BuddyPress 3.0.0
 *
 * @param array $scripts The array of scripts to register
 *
 * @return array The same array with the specific messages scripts.
 */
function bp_nouveau_messages_register_scripts( $scripts = array() ) {
	if ( ! isset( $scripts['bp-nouveau'] ) ) {
		return $scripts;
	}

	return array_merge(
		$scripts,
		array(
			'bp-nouveau-messages-at' => array(
				'file'         => buddypress()->plugin_url . 'bp-core/js/mentions%s.js',
				'dependencies' => array( 'bp-nouveau', 'jquery', 'jquery-atwho' ),
				'version'      => bp_get_version(),
				'footer'       => true,
			),
			'bp-nouveau-messages'    => array(
				'file'         => 'js/buddypress-messages%s.js',
				'dependencies' => array( 'bp-nouveau', 'json2', 'wp-backbone', 'bp-nouveau-messages-at', 'bp-select2' ),
				'footer'       => true,
			),
		)
	);
}

/**
 * Enqueue the messages scripts
 *
 * @since BuddyPress 3.0.0
 */
function bp_nouveau_messages_enqueue_scripts() {
	if ( ! bp_is_user_messages() ) {
		return;
	}

	wp_enqueue_script( 'bp-nouveau-messages' );
	wp_enqueue_script( 'bp-select2' );
	wp_enqueue_script( 'bp-medium-editor' );
	wp_enqueue_style( 'bp-medium-editor' );
	wp_enqueue_style( 'bp-medium-editor-beagle' );

	// Add The tiny MCE init specific function.
	add_filter( 'tiny_mce_before_init', 'bp_nouveau_messages_at_on_tinymce_init', 10, 2 );
}

/**
 * Localize the strings needed for the messages UI
 *
 * @since BuddyPress 3.0.0
 *
 * @param  array $params Associative array containing the JS Strings needed by scripts
 * @return array         The same array with specific strings for the messages UI if needed.
 */
function bp_nouveau_messages_localize_scripts( $params = array() ) {
	if ( ! bp_is_user_messages() ) {
		return $params;
	}

	$current_thread_type = '';
	if ( bp_is_messages_component() && is_user_logged_in() ) {
		$current_thread_type = 'unarchived';
		if ( 'archived' === bp_current_action() ) {
			$current_thread_type = 'archived';
		}
	}

	$params['messages'] = array(
		'errors'                     => array(
			'send_to'         => __( 'Please add at least one recipient.', 'buddyboss' ),
			'message_content' => __( 'Please add some content to your message.', 'buddyboss' ),
			'no_messages'     => __( 'Sorry, no messages were found.', 'buddyboss' ),
			'media_fail'      => __( 'To change the media type, remove existing media from your post.', 'buddyboss' ),
		),
		'nonces'                     => array(
			'send'           => wp_create_nonce( 'messages_send_message' ),
			'load_recipient' => wp_create_nonce( 'messages_load_recipient' ),
		),
		'loading'                    => __( 'Loading messages.', 'buddyboss' ),
		'doingAction'                => array(
			'read'          => __( 'Marking read.', 'buddyboss' ),
			'unread'        => __( 'Marking unread.', 'buddyboss' ),
			'delete'        => __( 'Deleting messages.', 'buddyboss' ),
			'star'          => __( 'Starring messages.', 'buddyboss' ),
			'unstar'        => __( 'Unstarring messages.', 'buddyboss' ),
			'hide_thread'   => __( 'Archiving conversation.', 'buddyboss' ),
			'unhide_thread' => __( 'Unarchiving conversation.', 'buddyboss' ),
		),
		'type_message'               => __( 'Write a message...', 'buddyboss' ),
		'delete_confirmation'        => __( 'Are you sure you want to permanently delete all of your messages from this conversation? This cannot be undone.', 'buddyboss' ),
		'delete_thread_confirmation' => __( 'As a site admin you are able to delete conversations. Are you sure you want to permanently delete this conversation and all of its messages? This cannot be undone.', 'buddyboss' ),
		'bulk_actions'               => bp_nouveau_messages_get_bulk_actions(),
		'howtoBulk'                  => __( 'Use the select box to define your bulk action and click on the &#10003; button to apply.', 'buddyboss' ),
		'toOthers'                   => array(
			'one'   => __( '1 other', 'buddyboss' ),
			'more'  => __( '%d others', 'buddyboss' ),
			'other' => __( 'others', 'buddyboss' ),
		),
		'rootUrl'                    => urldecode( wp_parse_url( trailingslashit( bp_displayed_user_domain() . bp_get_messages_slug() ), PHP_URL_PATH ) ),
		'hasThreads'                 => bp_has_message_threads( bp_ajax_querystring( 'messages' ) ),
		'today'                      => __( 'Today', 'buddyboss' ),
		'video_default_url'          => ( function_exists( 'bb_get_video_default_placeholder_image' ) && ! empty( bb_get_video_default_placeholder_image() ) ? bb_get_video_default_placeholder_image() : '' ),
		'message_url'                => trailingslashit( bp_loggedin_user_domain() . bp_get_messages_slug() ),
		'message_archived_url'       => trailingslashit( bb_get_messages_archived_url() ),
		'current_thread_id'          => (int) bp_action_variable( 0 ),
		'is_blocked_by_members'      => function_exists( 'bb_moderation_get_blocked_by_user_ids' ) ? bb_moderation_get_blocked_by_user_ids( get_current_user_id() ) : array(),
		'current_thread_type'        => $current_thread_type,
		'gif_media'                  => __( 'Sent a gif', 'buddyboss' ),
		'single_media'               => __( 'Sent a photo', 'buddyboss' ),
		'multiple_media'             => __( 'Sent some photos', 'buddyboss' ),
		'single_video'               => __( 'Sent a video', 'buddyboss' ),
		'multiple_video'             => __( 'Sent some videos', 'buddyboss' ),
		'single_document'            => __( 'Sent a document', 'buddyboss' ),
		'multiple_document'          => __( 'Sent some documents', 'buddyboss' ),
	);

	// Star private messages.
	if ( bp_is_active( 'messages', 'star' ) ) {
		$params['messages'] = array_merge(
			$params['messages'],
			array(
				'strings'          => array(
					'text_unstar'         => __( 'Unstar', 'buddyboss' ),
					'text_star'           => __( 'Star', 'buddyboss' ),
					'title_unstar'        => __( 'Starred', 'buddyboss' ),
					'title_star'          => __( 'Not starred', 'buddyboss' ),
					'title_unstar_thread' => __( 'Remove all starred messages in this thread', 'buddyboss' ),
					'title_star_thread'   => __( 'Star the first message in this thread', 'buddyboss' ),
				),
				'is_single_thread' => (int) bp_is_messages_conversation(),
				'star_counter'     => 0,
				'unstar_counter'   => 0,
			)
		);
	}

	return $params;
}

/**
 * Adjust message navigation for notices and composing.
 *
 * @since BuddyPress 3.0.0
 */
function bp_nouveau_messages_adjust_nav() {
	$bp = buddypress();

	$secondary_nav_items = $bp->members->nav->get_secondary( array( 'parent_slug' => bp_get_messages_slug() ), false );

	if ( empty( $secondary_nav_items ) ) {
		return;
	}

	foreach ( $secondary_nav_items as $secondary_nav_item ) {
		if ( empty( $secondary_nav_item->slug ) ) {
			continue;
		}

		if ( 'notices' === $secondary_nav_item->slug ) {
			bp_core_remove_subnav_item( bp_get_messages_slug(), $secondary_nav_item->slug, 'members' );
		} elseif ( 'compose' === $secondary_nav_item->slug ) {
			$bp->members->nav->edit_nav(
				array(
					'user_has_access' => bp_is_my_profile(),
				),
				$secondary_nav_item->slug,
				bp_get_messages_slug()
			);
		}
	}
}

/**
 * Adjust admin message navigation for notices.
 *
 * @since BuddyPress 3.0.0
 */
function bp_nouveau_messages_adjust_admin_nav( $admin_nav ) {
	if ( empty( $admin_nav ) ) {
		return $admin_nav;
	}

	$user_messages_link = trailingslashit( bp_loggedin_user_domain() . bp_get_messages_slug() );

	foreach ( $admin_nav as $nav_iterator => $nav ) {
		$nav_id = str_replace( 'my-account-messages-', '', $nav['id'] );

		if ( 'notices' === $nav_id ) {
			$admin_nav[ $nav_iterator ]['href'] = esc_url(
				add_query_arg(
					array(
						'page' => 'bp-notices',
					),
					bp_get_admin_url( 'admin.php' )
				)
			);
		}
	}

	return $admin_nav;
}

/**
 * Add notice notification for member.
 *
 * @since BuddyPress 3.0.0
 */
function bp_nouveau_add_notice_notification_for_user( $notifications, $user_id ) {
	if ( ! bp_is_active( 'messages' ) || ! doing_action( 'admin_bar_menu' ) ) {
		return $notifications;
	}

	$notice = BP_Messages_Notice::get_active();
	if ( empty( $notice->id ) ) {
		return $notifications;
	}

	$closed_notices = bp_get_user_meta( $user_id, 'closed_notices', true );
	if ( empty( $closed_notices ) ) {
		$closed_notices = array();
	}

	if ( in_array( $notice->id, $closed_notices, true ) ) {
		return $notifications;
	}

	$notice_notification                    = new stdClass();
	$notice_notification->id                = 0;
	$notice_notification->user_id           = $user_id;
	$notice_notification->item_id           = $notice->id;
	$notice_notification->secondary_item_id = '';
	$notice_notification->component_name    = 'messages';
	$notice_notification->component_action  = 'new_notice';
	$notice_notification->date_notified     = $notice->date_sent;
	$notice_notification->is_new            = '1';

	return array_merge( $notifications, array( $notice_notification ) );
}

/**
 * Format for notice notifications.
 *
 * @since BuddyPress 3.0.0
 */
function bp_nouveau_format_notice_notification_for_user( $array ) {
	if ( ! empty( $array['text'] ) || ! doing_action( 'admin_bar_menu' ) ) {
		return $array;
	}

	return array(
		'text' => __( 'New sitewide notice', 'buddyboss' ),
		'link' => bp_loggedin_user_domain(),
	);
}

/**
 * Unregister BP_Messages_Sitewide_Notices_Widget.
 *
 * @since BuddyPress 3.0.0
 */
function bp_nouveau_unregister_notices_widget() {
	unregister_widget( 'BP_Messages_Sitewide_Notices_Widget' );
}

/**
 * Add active sitewide notices to the BP template_message global.
 *
 * @since BuddyPress 3.0.0
 */
function bp_nouveau_push_sitewide_notices() {
	// Do not show notices if user is not logged in.
	if ( ! is_user_logged_in() ) {
		return;
	}

	$notice = BP_Messages_Notice::get_active();
	if ( empty( $notice ) ) {
		return;
	}

	$user_id = bp_loggedin_user_id();

	$closed_notices = bp_get_user_meta( $user_id, 'closed_notices', true );
	if ( empty( $closed_notices ) ) {
		$closed_notices = array();
	}

	if ( $notice->id && is_array( $closed_notices ) && ! in_array( $notice->id, $closed_notices ) ) {
		// Inject the notice into the template_message if no other message has priority.
		$bp = buddypress();

		/**
		 * The "bp-message" cookie takes precedence over notices because it needs to be immediately displayed
		 * after a user unsubscribes and requires prompt action.
		 */
		if ( empty( $bp->template_message ) && empty( $_COOKIE['bp-message'] ) ) {
			$message                   = sprintf(
				'<strong class="subject">%s</strong>
				%s',
				stripslashes( $notice->subject ),
				stripslashes( $notice->message )
			);
			$bp->template_message      = $message;
			$bp->template_message_type = 'bp-sitewide-notice';
		}
	}
}

/**
 * Disable the WP Editor buttons not allowed in invites content.
 *
 * @since BuddyBoss 1.0.0
 *
 * @param array $buttons The WP Editor buttons list.
 *
 * @return array          The filtered WP Editor buttons list.
 */
function bp_nouveau_invites_mce_buttons( $buttons = array() ) {
	$remove_buttons = array(
		'wp_more',
		'spellchecker',
		'wp_adv',
		'fullscreen',
		'alignleft',
		'alignright',
		'aligncenter',
		'formatselect',
	);

	// Remove unused buttons.
	$buttons = array_diff( $buttons, $remove_buttons );

	// Add the image button
	// array_push( $buttons, 'image' );

	return $buttons;
}

/**
 * Disable the WP Editor buttons not allowed in messages content.
 *
 * @since BuddyPress 3.0.0
 *
 * @param array                                              $buttons The WP Editor buttons list.
 * @param array          The filtered WP Editor buttons list.
 */
function bp_nouveau_messages_mce_buttons( $buttons = array() ) {
	$remove_buttons = array(
		'wp_more',
		'spellchecker',
		'wp_adv',
		'fullscreen',
		'alignleft',
		'alignright',
		'aligncenter',
		'formatselect',
	);

	// Remove unused buttons
	$buttons = array_diff( $buttons, $remove_buttons );

	// Add the image button
	// array_push( $buttons, 'image' );

	return $buttons;
}

/**
 * Display tinyMCE editor when editing messages in Dashboard.
 *
 * @since BuddyPress 3.0.0
 */
function bp_nouveau_messages_at_on_tinymce_init( $settings, $editor_id ) {
	// We only apply the mentions init to the visual post editor in the WP dashboard.
	if ( 'message_content' === $editor_id ) {
		$settings['init_instance_callback'] = 'window.bp.Nouveau.Messages.tinyMCEinit';
	}

	return $settings;
}

/**
 * Get message date.
 *
 * @since BuddyPress 3.0.0
 */
function bp_nouveau_get_message_date( $date, $date_format = '' ) {
	$now  = bp_core_current_time( true, 'timestamp' );
	$date = strtotime( $date );

	$now_date  = getdate( $now );
	$date_date = getdate( $date );
	$compare   = array_diff( $date_date, $now_date );
	// $date_format = 'Y/m/d';

	// Use Timezone string if set.
	$timezone_string = bp_get_option( 'timezone_string' );
	if ( ! empty( $timezone_string ) ) {
		$timezone_object = timezone_open( $timezone_string );
		$datetime_object = date_create( "@{$date}" );

		if ( false !== $timezone_object && false !== $datetime_object ) {
			$timezone_offset = timezone_offset_get( $timezone_object, $datetime_object ) / HOUR_IN_SECONDS;
		} else {
			$timezone_offset = bp_get_option( 'gmt_offset' );
		}

		// Fall back on less reliable gmt_offset
	} else {
		$timezone_offset = bp_get_option( 'gmt_offset' );
	}

	// Calculate time based on the offset
	$calculated_time = $date + ( $timezone_offset * HOUR_IN_SECONDS );

	// use M j for all, will revisit this later
	// if ( empty( $compare['mday'] ) && empty( $compare['mon'] ) && empty( $compare['year'] ) ) {
	// $date_format = 'H:i';

	// } elseif ( empty( $compare['mon'] ) || empty( $compare['year'] ) ) {
	// $date_format = 'M j';
	// }

	if ( empty( $date_format ) ) {
		$date_format = 'M j';
	}

	/**
	 * Filters the message date for BuddyPress Nouveau display.
	 *
	 * @since BuddyPress 3.0.0
	 *
	 * @param string $value           Internationalization-ready formatted date value.
	 * @param mixed  $calculated_time Calculated time.
	 * @param string $date            Date value.
	 * @param string $date_format     Format to convert the calcuated date to.
	 */
	return apply_filters( 'bp_nouveau_get_message_date', date_i18n( $date_format, $calculated_time, true ), $calculated_time, $date, $date_format );
}

/**
 * Output bulk actions for messages.
 *
 * @since BuddyPress 3.0.0
 */
function bp_nouveau_messages_get_bulk_actions() {
	ob_start();
	bp_messages_bulk_management_dropdown();

	$bulk_actions = array();
	$bulk_options = ob_get_clean();

	$matched = preg_match_all( '/<option value="(.*?)"\s*>(.*?)<\/option>/', $bulk_options, $matches, PREG_SET_ORDER );

	if ( $matched && is_array( $matches ) ) {
		foreach ( $matches as $i => $match ) {
			if ( 0 === $i ) {
				continue;
			}

			if ( isset( $match[1] ) && isset( $match[2] ) ) {
				$bulk_actions[] = array(
					'value' => trim( $match[1] ),
					'label' => trim( $match[2] ),
				);
			}
		}
	}

	return $bulk_actions;
}

/**
 * Register notifications filters for the messages component.
 *
 * @since BuddyPress 3.0.0
 */
function bp_nouveau_messages_notification_filters() {

	if ( ! bb_enabled_legacy_email_preference() ) {
		return;
	}

	bp_nouveau_notifications_register_filter(
		array(
			'id'       => 'new_message',
			'label'    => __( 'New private messages', 'buddyboss' ),
			'position' => 115,
		)
	);
}

/**
 * Fires Messages Legacy hooks to catch the content and add them
 * as extra keys to the JSON Messages UI reply.
 *
 * @since BuddyPress 3.0.1
 *
 * @param array $hooks The list of hooks to fire.
 * @return array       An associative containing the caught content.
 */
function bp_nouveau_messages_catch_hook_content( $hooks = array() ) {
	$content = array();

	ob_start();
	foreach ( $hooks as $js_key => $hook ) {
		if ( ! has_action( $hook ) ) {
			continue;
		}

		// Fire the hook.
		do_action( $hook );

		// Catch the sanitized content.
		$content[ $js_key ] = bp_strip_script_and_style_tags( ob_get_contents() );

		// Clean the buffer.
		ob_clean();
	}
	ob_end_clean();

	return $content;
}
