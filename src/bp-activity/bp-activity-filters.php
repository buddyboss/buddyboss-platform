<?php
/**
 * Filters related to the Activity Feeds component.
 *
 * @package BuddyBoss\Activity
 * @since BuddyPress 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/* Filters *******************************************************************/

// Apply WordPress defined filters.
add_filter( 'bp_get_activity_action', 'bp_activity_filter_kses', 1 );
add_filter( 'bp_get_activity_content_body', 'bp_activity_filter_kses', 1 );
add_filter( 'bp_get_activity_content', 'bp_activity_filter_kses', 1 );
add_filter( 'bp_get_activity_parent_content', 'bp_activity_filter_kses', 1 );
add_filter( 'bp_get_activity_latest_update', 'bp_activity_filter_kses', 1 );
add_filter( 'bp_get_activity_latest_update_excerpt', 'bp_activity_filter_kses', 1 );
add_filter( 'bp_get_activity_feed_item_description', 'bp_activity_filter_kses', 1 );
add_filter( 'bp_activity_content_before_save', 'bp_activity_filter_kses', 1 );
add_filter( 'bp_activity_action_before_save', 'bp_activity_filter_kses', 1 );
add_filter( 'bp_activity_latest_update_content', 'bp_activity_filter_kses', 1 );
add_filter( 'bp_activity_comment_content', 'bp_activity_filter_kses', 1 );

add_filter( 'bp_get_activity_action', 'force_balance_tags' );
add_filter( 'bp_get_activity_content_body', 'force_balance_tags' );
add_filter( 'bp_get_activity_content', 'force_balance_tags' );
add_filter( 'bp_get_activity_latest_update', 'force_balance_tags' );
add_filter( 'bp_get_activity_latest_update_excerpt', 'force_balance_tags' );
add_filter( 'bp_get_activity_feed_item_description', 'force_balance_tags' );
add_filter( 'bp_activity_content_before_save', 'force_balance_tags' );
add_filter( 'bp_activity_action_before_save', 'force_balance_tags' );

if ( function_exists( 'wp_encode_emoji' ) ) {
	add_filter( 'bp_activity_content_before_save', 'wp_encode_emoji' );
}

add_filter( 'bp_activity_mentioned_users', 'bp_find_mentions_by_at_sign', 10, 2 );

add_filter( 'bp_get_activity_action', 'wptexturize' );
add_filter( 'bp_get_activity_content_body', 'wptexturize' );
add_filter( 'bp_get_activity_content', 'wptexturize' );
add_filter( 'bp_get_activity_parent_content', 'wptexturize' );
add_filter( 'bp_get_activity_latest_update', 'wptexturize' );
add_filter( 'bp_get_activity_latest_update_excerpt', 'wptexturize' );
add_filter( 'bp_activity_get_embed_excerpt', 'wptexturize' );

add_filter( 'bp_get_activity_action', 'convert_smilies' );
add_filter( 'bp_get_activity_content_body', 'convert_smilies' );
add_filter( 'bp_get_activity_content', 'convert_smilies' );
add_filter( 'bp_get_activity_parent_content', 'convert_smilies' );
add_filter( 'bp_get_activity_latest_update', 'convert_smilies' );
add_filter( 'bp_get_activity_latest_update_excerpt', 'convert_smilies' );
add_filter( 'bp_activity_get_embed_excerpt', 'convert_smilies' );

add_filter( 'bp_get_activity_action', 'convert_chars' );
add_filter( 'bp_get_activity_content_body', 'convert_chars' );
add_filter( 'bp_get_activity_content', 'convert_chars' );
add_filter( 'bp_get_activity_parent_content', 'convert_chars' );
add_filter( 'bp_get_activity_latest_update', 'convert_chars' );
add_filter( 'bp_get_activity_latest_update_excerpt', 'convert_chars' );
add_filter( 'bp_activity_get_embed_excerpt', 'convert_chars' );

add_filter( 'bp_get_activity_action', 'wpautop' );
add_filter( 'bp_get_activity_content_body', 'wpautop' );
add_filter( 'bp_get_activity_content', 'wpautop' );
add_filter( 'bp_get_activity_feed_item_description', 'wpautop' );
add_filter( 'bp_activity_get_embed_excerpt', 'wpautop' );

add_filter( 'bp_get_activity_action', 'make_clickable', 9 );
add_filter( 'bp_get_activity_content_body', 'make_clickable', 9 );
add_filter( 'bp_get_activity_content', 'make_clickable', 9 );
add_filter( 'bp_get_activity_parent_content', 'make_clickable', 9 );
add_filter( 'bp_get_activity_latest_update', 'make_clickable', 9 );
add_filter( 'bp_get_activity_latest_update_excerpt', 'make_clickable', 9 );
add_filter( 'bp_get_activity_feed_item_description', 'make_clickable', 9 );
add_filter( 'bp_activity_get_embed_excerpt', 'make_clickable', 9 );

add_filter( 'bp_acomment_name', 'stripslashes_deep', 5 );
add_filter( 'bp_get_activity_action', 'stripslashes_deep', 5 );
add_filter( 'bp_get_activity_content', 'stripslashes_deep', 5 );
add_filter( 'bp_get_activity_content_body', 'stripslashes_deep', 5 );
add_filter( 'bp_get_activity_parent_content', 'stripslashes_deep', 5 );
add_filter( 'bp_get_activity_latest_update', 'stripslashes_deep', 5 );
add_filter( 'bp_get_activity_latest_update_excerpt', 'stripslashes_deep', 5 );
add_filter( 'bp_get_activity_feed_item_description', 'stripslashes_deep', 5 );

add_filter( 'bp_activity_primary_link_before_save', 'esc_url_raw' );

// Apply BuddyPress-defined filters.
add_filter( 'bp_get_activity_content', 'bp_activity_make_nofollow_filter' );
add_filter( 'bp_get_activity_content_body', 'bp_activity_make_nofollow_filter' );
add_filter( 'bp_get_activity_parent_content', 'bp_activity_make_nofollow_filter' );
add_filter( 'bp_get_activity_latest_update', 'bp_activity_make_nofollow_filter' );
add_filter( 'bp_get_activity_latest_update_excerpt', 'bp_activity_make_nofollow_filter' );
add_filter( 'bp_get_activity_feed_item_description', 'bp_activity_make_nofollow_filter' );
add_filter( 'bp_activity_new_at_mention_permalink', 'bp_activity_new_at_mention_permalink', 11, 3 );

add_filter( 'pre_comment_content', 'bp_activity_at_name_filter' );
add_filter( 'the_content', 'bp_activity_at_name_filter' );
add_filter( 'bp_activity_get_embed_excerpt', 'bp_activity_at_name_filter' );
add_filter( 'bp_activity_comment_content', 'bp_activity_at_name_filter' );

add_filter( 'bp_get_activity_parent_content', 'bp_create_excerpt' );

add_filter( 'bp_get_activity_content_body', 'bp_activity_truncate_entry', 5 );
add_filter( 'bp_get_activity_content', 'bp_activity_truncate_entry', 5 );

add_filter( 'bp_get_total_favorite_count_for_user', 'bp_core_number_format' );
add_filter( 'bp_get_total_mention_count_for_user', 'bp_core_number_format' );

add_filter( 'bp_activity_get_embed_excerpt', 'bp_activity_embed_excerpt_onclick_location_filter', 9 );
// add_filter( 'bp_after_has_activities_parse_args', 'bp_activity_display_all_types_on_just_me' );

add_filter( 'bp_get_activity_content_body', 'bp_activity_link_preview', 20, 2 );
add_action( 'bp_has_activities', 'bp_activity_has_activity_filter', 10, 2 );
add_action( 'bp_has_activities', 'bp_activity_has_media_activity_filter', 10, 2 );
add_action( 'bp_activity_after_delete', 'bb_activity_delete_link_review_attachment', 10, 1 );

/* Actions *******************************************************************/

// At-name filter.
add_action( 'bp_activity_before_save', 'bp_activity_at_name_filter_updates' );

// Activity feed moderation.
add_action( 'bp_activity_before_save', 'bp_activity_check_moderation_keys', 2, 1 );
add_action( 'bp_activity_before_save', 'bp_activity_check_blacklist_keys', 2, 1 );

// Activity link preview
add_action( 'bp_activity_after_save', 'bp_activity_save_link_data', 2, 1 );
add_action( 'bp_activity_after_save', 'bp_activity_update_comment_privacy', 3 );

// Remove Activity if uncheck the options from the backend BuddyBoss > Settings > Activity > Posts in Activity Feed >BuddyBoss Platform
add_action( 'bp_activity_before_save', 'bp_activity_remove_platform_updates', 999, 1 );

add_action( 'bp_media_add', 'bp_activity_media_add', 9 );
add_filter( 'bp_media_add_handler', 'bp_activity_create_parent_media_activity', 9 );
add_filter( 'bp_media_add_handler', 'bp_activity_edit_update_media', 10 );

add_action( 'bp_video_add', 'bp_activity_video_add', 9 );
add_filter( 'bp_video_add_handler', 'bp_activity_create_parent_video_activity', 9 );
add_filter( 'bp_video_add_handler', 'bp_activity_edit_update_video', 10 );

add_action( 'bp_document_add', 'bp_activity_document_add', 9 );
add_filter( 'bp_document_add_handler', 'bp_activity_create_parent_document_activity', 9 );
add_filter( 'bp_document_add_handler', 'bp_activity_edit_update_document', 10 );

// Temporary filter to remove edit button on popup until we fully make compatible on edit everywhere in popup/reply/comment.
add_filter( 'bb_nouveau_get_activity_entry_bubble_buttons', 'bp_nouveau_remove_edit_activity_entry_buttons', 999, 2 );

// Obey BuddyBoss commenting rules.
add_filter( 'bp_activity_can_comment', 'bb_activity_has_comment_access' );

// Obey BuddyBoss comment reply rules.
add_filter( 'bp_activity_can_comment_reply', 'bb_activity_has_comment_reply_access', 10, 2 );

// Filter for comment meta button.
add_filter( 'bp_nouveau_get_activity_comment_buttons', 'bb_remove_discussion_comment_reply_button', 10, 3 );

// Filter check content empty or not for the media, document and GIF data.
add_filter( 'bb_is_activity_content_empty', 'bb_check_is_activity_content_empty' );

// Load Activity Notifications.
add_action( 'bp_activity_includes', 'bb_load_activity_notifications' );

// Remove deleted members link from mention for activity/comment.
add_filter( 'bp_get_activity_content', 'bb_mention_remove_deleted_users_link', 20, 1 );
add_filter( 'bp_get_activity_content_body', 'bb_mention_remove_deleted_users_link', 20, 1 );
add_filter( 'bp_activity_comment_content', 'bb_mention_remove_deleted_users_link', 20, 1 );

/** Functions *****************************************************************/

/**
 * Types of activity feed items to moderate.
 *
 * @since BuddyPress 1.6.0
 *
 * @return array $types List of the activity types to moderate.
 */
function bp_activity_get_moderated_activity_types() {
	$types = array(
		'activity_comment',
		'activity_update',
	);

	/**
	 * Filters the default activity types that BuddyPress should moderate.
	 *
	 * @since BuddyPress 1.6.0
	 *
	 * @param array $types Default activity types to moderate.
	 */
	return apply_filters( 'bp_activity_check_activity_types', $types );
}

/**
 * Moderate the posted activity item, if it contains moderate keywords.
 *
 * @since BuddyPress 1.6.0
 *
 * @param BP_Activity_Activity $activity The activity object to check.
 */
function bp_activity_check_moderation_keys( $activity ) {

	// Only check specific types of activity updates.
	if ( ! in_array( $activity->type, bp_activity_get_moderated_activity_types() ) ) {
		return;
	}

	// Send back the error so activity update fails.
	// @todo This is temporary until some kind of moderation is built.
	$moderate = bp_core_check_for_moderation( $activity->user_id, '', $activity->content, 'wp_error' );
	if ( is_wp_error( $moderate ) ) {
		$activity->errors = $moderate;

		// Backpat.
		$activity->component = false;
	}
}

/**
 * Mark the posted activity as spam, if it contains blacklist keywords.
 *
 * @since BuddyPress 1.6.0
 *
 * @param BP_Activity_Activity $activity The activity object to check.
 */
function bp_activity_check_blacklist_keys( $activity ) {

	// Only check specific types of activity updates.
	if ( ! in_array( $activity->type, bp_activity_get_moderated_activity_types() ) ) {
		return;
	}

	// Send back the error so activity update fails.
	// @todo This is temporary until some kind of trash status is built.
	$blacklist = bp_core_check_for_blacklist( $activity->user_id, '', $activity->content, 'wp_error' );
	if ( is_wp_error( $blacklist ) ) {
		$activity->errors = $blacklist;

		// Backpat.
		$activity->component = false;
	}
}

/**
 * Save link preview data into activity meta key "_link_preview_data"
 *
 * @since BuddyBoss 1.0.0
 *
 * @param $activity
 */
function bp_activity_save_link_data( $activity ) {

	// bail if the request is for privacy update.
	if ( 
		isset( $_POST['action'] ) && 
		in_array(
			$_POST['action'], // phpcs:ignore WordPress.Security.NonceVerification.Missing
			array(
				'activity_update_privacy',
				'bbp-new-topic',
				'bbp-new-reply',
				'bbp-edit-topic',
				'bbp-edit-reply',
			),
			true
		)
	) {
		return;
	}

	// Check if link_url is missing http protocol then update it.
	$link_url = '';
	if ( ! empty( $_POST['link_url'] ) ) {
		$parsed_url = wp_parse_url( $_POST['link_url'] );
		if ( ! $parsed_url || empty( $parsed_url['host'] ) ) {
			$link_url = 'http://' . $_POST['link_url'];
		} else {
			$link_url = $_POST['link_url'];
		}
	}

	$link_url   = ! empty( $link_url ) ? filter_var( $link_url, FILTER_VALIDATE_URL ) : '';
	$link_embed = isset( $_POST['link_embed'] ) ? filter_var( $_POST['link_embed'], FILTER_VALIDATE_BOOLEAN ) : false;

	// Check if link url is set or not.
	if ( empty( $link_url ) ) {
		if ( false === $link_embed ) {
			bp_activity_update_meta( $activity->id, '_link_embed', '0' );

			// This will remove the preview data if the activity don't have anymore link in content.
			bp_activity_update_meta( $activity->id, '_link_preview_data', '' );
		}

		return;
	}

	// Return if link embed was used activity is in edit.
	if ( true === $link_embed && 'activity_comment' === $activity->type ) {
		return;
	}

	$link_title       = ! empty( $_POST['link_title'] ) ? filter_var( $_POST['link_title'] ) : '';
	$link_description = ! empty( $_POST['link_description'] ) ? filter_var( $_POST['link_description'] ) : '';
	$link_image       = ! empty( $_POST['link_image'] ) ? filter_var( $_POST['link_image'], FILTER_VALIDATE_URL ) : '';

	// Check if link embed was used.
	if ( true === $link_embed && ! empty( $link_url ) ) {
		bp_activity_update_meta( $activity->id, '_link_embed', $link_url );
		bp_activity_update_meta( $activity->id, '_link_preview_data', '' );

		return;
	} else {
		bp_activity_update_meta( $activity->id, '_link_embed', '0' );
	}

	$preview_data['url'] = $link_url;

	if ( ! empty( $link_image ) ) {
		$attachment_id = bb_media_sideload_attachment( $link_image );
		if ( $attachment_id ) {
			$preview_data['attachment_id'] = $attachment_id;
		} else {
			// store non downloadable urls as it is in preview data.
			$preview_data['image_url'] = $link_image;
		}
	}

	$preview_data['link_image_index_save'] = isset( $_POST['link_image_index_save'] ) ? filter_var( $_POST['link_image_index_save'] ) : '';

	if ( ! empty( $link_title ) ) {
		$preview_data['title'] = $link_title;
	}

	if ( ! empty( $link_description ) ) {
		$preview_data['description'] = $link_description;
	}

	bp_activity_update_meta( $activity->id, '_link_preview_data', $preview_data );
}

/**
 * Update activity comment privacy with parent activity privacy update.
 *
 * @since BuddyBoss 1.4.0
 *
 * @param BP_Activity_Activity $activity Activity object
 */
function bp_activity_update_comment_privacy( $activity ) {
	$activity_comments = bp_activity_get_specific(
		array(
			'activity_ids'     => array( $activity->id ),
			'display_comments' => true,
		)
	);

	if ( ! empty( $activity_comments ) && ! empty( $activity_comments['activities'] ) && isset( $activity_comments['activities'][0]->children ) ) {
		$children = $activity_comments['activities'][0]->children;
		if ( ! empty( $children ) ) {
			foreach ( $children as $comment ) {
				bp_activity_comment_privacy_update( $comment, $activity->privacy );
			}
		}
	}
}

/**
 * Recursive function to update privacy of comment with nested level.
 *
 * @since BuddyBoss 1.4.0
 *
 * @param BP_Activity_Activity $comment Activity comment object.
 * @param string               $privacy Parent Activity privacy.
 */
function bp_activity_comment_privacy_update( $comment, $privacy ) {
	$comment_activity          = new BP_Activity_Activity( $comment->id );
	$comment_activity->privacy = $privacy;
	$comment_activity->save();

	if ( ! empty( $comment->children ) ) {
		foreach ( $comment->children as $child_comment ) {
			bp_activity_comment_privacy_update( $child_comment, $privacy );
		}
	}
}

/**
 * Custom kses filtering for activity content.
 *
 * @since BuddyPress 1.1.0
 *
 * @param string $content The activity content.
 * @return string $content Filtered activity content.
 */
function bp_activity_filter_kses( $content ) {
	/**
	 * Filters the allowed HTML tags for BuddyBoss Activity content.
	 *
	 * @since BuddyPress 1.2.0
	 *
	 * @param array $value Array of allowed HTML tags and attributes.
	 */
	$activity_allowedtags = apply_filters( 'bp_activity_allowed_tags', bp_get_allowedtags() );
	return wp_kses( $content, $activity_allowedtags );
}

/**
 * Find and link @-mentioned users in the contents of a given item.
 *
 * @since BuddyPress 1.2.0
 *
 * @param string $content     The contents of a given item.
 * @param int    $activity_id The activity id. Deprecated.
 * @return string $content Content filtered for mentions.
 */
function bp_activity_at_name_filter( $content, $activity_id = 0 ) {

	// Are mentions disabled?
	if ( ! bp_activity_do_mentions() ) {
		return $content;
	}

	// Try to find mentions.
	$usernames = bp_activity_find_mentions( $content );

	// No mentions? Stop now!
	if ( empty( $usernames ) ) {
		return $content;
	}

	// We don't want to link @mentions that are inside of links, so we
	// temporarily remove them.
	$replace_count = 0;
	$replacements  = array();
	foreach ( $usernames as $username ) {
		// Prevent @ name linking inside <a> tags.
		preg_match_all( '/(<a.*?(?!<\/a>)@' . $username . '.*?<\/a>)/', $content, $content_matches );
		if ( ! empty( $content_matches[1] ) ) {
			foreach ( $content_matches[1] as $replacement ) {
				$unique_index                  = '#BPAN' . $replace_count . '#';
				$replacements[ $unique_index ] = $replacement;
				$content                       = str_replace( $replacement, $unique_index, $content );
				$replace_count++;
			}
		}
	}

	// Linkify the mentions with the username.
	foreach ( (array) $usernames as $user_id => $username ) {
		$pattern = '/(?<=[^A-Za-z0-9\_\/\.\-\*\+\=\%\$\#\?]|^)@' . preg_quote( $username, '/' ) . '\b(?!\/)/';
		$content = preg_replace( $pattern, "<a class='bp-suggestions-mention' href='" . bp_core_get_user_domain( $user_id ) . "' rel='nofollow'>@$username</a>", $content );
	}

	// Put everything back.
	if ( ! empty( $replacements ) ) {
		foreach ( $replacements as $placeholder => $original ) {
			$content = str_replace( $placeholder, $original, $content );
		}
	}

	// Return the content.
	return $content;
}

/**
 * Catch mentions in an activity item before it is saved into the database.
 *
 * If mentions are found, replace @mention text with user links and add our
 * hook to send mention notifications after the activity item is saved.
 *
 * @since BuddyPress 1.5.0
 *
 * @param BP_Activity_Activity $activity Activity Object.
 */
function bp_activity_at_name_filter_updates( $activity ) {
	// Are mentions disabled?
	if ( ! bp_activity_do_mentions() ) {
		return;
	}

	// If activity was marked as spam, stop the rest of this function.
	if ( ! empty( $activity->is_spam ) ) {
		return;
	}

	// Try to find mentions.
	$usernames = bp_activity_find_mentions( $activity->content );

	// We have mentions!
	if ( ! empty( $usernames ) ) {
		// Replace @mention text with userlinks.
		foreach ( (array) $usernames as $user_id => $username ) {
			$pattern = '/(?<=[^A-Za-z0-9\_\/\.\-\*\+\=\%\$\#\?]|^)@' . preg_quote( $username, '/' ) . '\b(?!\/)/';
			$activity->content = preg_replace( $pattern, "<a class='bp-suggestions-mention' href='" . bp_core_get_user_domain( $user_id ) . "' rel='nofollow'>@$username</a>", $activity->content );
		}

		// Add our hook to send @mention emails after the activity item is saved.
		add_action( 'bp_activity_after_save', 'bp_activity_at_name_send_emails' );

		// Temporary variable to avoid having to run bp_activity_find_mentions() again.
		buddypress()->activity->mentioned_users = $usernames;
	}
}

/**
 * Sends emails and BP notifications for users @-mentioned in an activity item.
 *
 * @since BuddyPress 1.7.0
 *
 * @param BP_Activity_Activity $activity The BP_Activity_Activity object.
 */
function bp_activity_at_name_send_emails( $activity ) {
	// Are mentions disabled?
	if ( ! bp_activity_do_mentions() || ( ! empty( $activity->privacy ) && 'onlyme' === $activity->privacy ) ) {
		return;
	}

	// If our temporary variable doesn't exist, stop now.
	if ( empty( buddypress()->activity->mentioned_users ) ) {
		return;
	}

	// Grab our temporary variable from bp_activity_at_name_filter_updates().
	$usernames = buddypress()->activity->mentioned_users;

	// Get rid of temporary variable.
	unset( buddypress()->activity->mentioned_users );

	// Send @mentions and setup BP notifications.
	foreach ( (array) $usernames as $user_id => $username ) {

		/**
		 * Filters BuddyPress' ability to send email notifications for @mentions.
		 *
		 * @since BuddyPress 1.6.0
		 * @since BuddyPress 2.5.0 Introduced `$user_id` and `$activity` parameters.
		 *
		 * @param bool                 $value     Whether or not BuddyPress should send a notification to the mentioned users.
		 * @param array                $usernames Array of users potentially notified.
		 * @param int                  $user_id   ID of the current user being notified.
		 * @param BP_Activity_Activity $activity  Activity object.
		 */
		if ( apply_filters( 'bp_activity_at_name_do_notifications', true, $usernames, $user_id, $activity ) ) {
			bp_activity_at_message_notification( $activity->id, $user_id );

			// Updates mention count for the user.
			bp_activity_update_mention_count_for_user( $user_id, $activity->id );
		}
	}
}

/**
 * Catch links in activity text so rel=nofollow can be added.
 *
 * @since BuddyPress 1.2.0
 *
 * @param string $text Activity text.
 * @return string $text Text with rel=nofollow added to any links.
 */
function bp_activity_make_nofollow_filter( $text ) {
	return preg_replace_callback( '|<a (.+?)>|i', 'bp_activity_make_nofollow_filter_callback', $text );
}

	/**
	 * Add rel=nofollow to a link.
	 *
	 * @since BuddyPress 1.2.0
	 *
	 * @param array $matches Items matched by preg_replace_callback() in bp_activity_make_nofollow_filter().
	 * @return string $text Link with rel=nofollow added.
	 */
function bp_activity_make_nofollow_filter_callback( $matches ) {
	$text = $matches[1];
	$text = str_replace( array( ' rel="nofollow"', " rel='nofollow'" ), '', $text );

	// Extract URL from href
	preg_match_all( '#\bhttps?://[^,\s()<>]+(?:\([\w\d]+\)|([^,[:punct:]\s]|/))#', $text, $match );

	$url_host      = ( isset( $match[0] ) && isset( $match[0][0] ) ? parse_url( $match[0][0], PHP_URL_HOST ) : '' );
	$base_url_host = parse_url( site_url(), PHP_URL_HOST );

	// If site link then nothing to do.
	if ( $url_host == $base_url_host || empty( $url_host ) ) {
		return "<a $text rel=\"nofollow\">";
		// Else open in new tab.
	} else {
		return "<a target='_blank' $text rel=\"nofollow\">";
	}

}

/**
 * Truncate long activity entries when viewed in activity feeds.
 *
 * This method can only be used inside the Activity loop.
 *
 * @since BuddyPress 1.5.0
 * @since BuddyPress 2.6.0 Added $args parameter.
 *
 * @param string $text The original activity entry text.
 * @param array  $args {
 *     Optional parameters. See $options argument of {@link bp_create_excerpt()}
 *     for all available parameters.
 * }.
 * @return string $excerpt The truncated text.
 */
function bp_activity_truncate_entry( $text, $args = array() ) {
	global $activities_template;

	/**
	 * Provides a filter that lets you choose whether to skip this filter on a per-activity basis.
	 *
	 * @since BuddyPress 2.3.0
	 *
	 * @param bool $value If true, text should be checked to see if it needs truncating.
	 */
	$maybe_truncate_text = apply_filters(
		'bp_activity_maybe_truncate_entry',
		isset( $activities_template->activity->type ) && ! in_array( $activities_template->activity->type, array( 'new_blog_post' ), true )
	);

	// The full text of the activity update should always show on the single activity screen.
	if ( empty( $args['force_truncate'] ) && ( ! $maybe_truncate_text || bp_is_single_activity() ) ) {
		return $text;
	}

	/**
	 * Filters the appended text for the activity excerpt.
	 *
	 * @since BuddyPress 1.5.0
	 *
	 * @param string $value Internationalized "Read more" text.
	 */
	$append_text = apply_filters( 'bp_activity_excerpt_append_text', __( ' Read more', 'buddyboss' ) );

	$excerpt_length = bp_activity_get_excerpt_length();

	$args = bp_parse_args( $args, array( 'ending' => __( '&hellip;', 'buddyboss' ) ) );

	// Run the text through the excerpt function. If it's too short, the original text will be returned.
	$excerpt = bp_create_excerpt( $text, $excerpt_length, $args );

	/*
	 * If the text returned by bp_create_excerpt() is different from the original text (ie it's
	 * been truncated), add the "Read More" link. Note that bp_create_excerpt() is stripping
	 * shortcodes, so we have strip them from the $text before the comparison.
	 */
	if ( strlen( $excerpt ) < strlen( strip_shortcodes( $text ) ) ) {
		$id      = ! empty( $activities_template->activity->current_comment->id ) ? 'acomment-read-more-' . $activities_template->activity->current_comment->id : 'activity-read-more-' . bp_get_activity_id();
		$excerpt = sprintf( '%1$s<span class="activity-read-more" id="%2$s"><a href="%3$s" rel="nofollow">%4$s</a></span>', $excerpt, $id, bp_get_activity_thread_permalink(), $append_text );
	}

	/**
	 * Filters the composite activity excerpt entry.
	 *
	 * @since BuddyPress 1.5.0
	 *
	 * @param string $excerpt     Excerpt text and markup to be displayed.
	 * @param string $text        The original activity entry text.
	 * @param string $append_text The final append text applied.
	 */
	return apply_filters( 'bp_activity_truncate_entry', $excerpt, $text, $append_text );
}

/**
 * Embed link preview in activity content
 *
 * @param $content
 * @param $activity
 *
 * @since BuddyBoss 1.0.0
 *
 * @return string
 */
function bp_activity_link_preview( $content, $activity ) {

	$activity_id  = $activity->id;
	$preview_data = bp_activity_get_meta( $activity_id, '_link_preview_data', true );

	if ( empty( $preview_data['url'] ) ) {
		return $content;
	}

	$preview_data = bp_parse_args(
		$preview_data,
		array(
			'title'       => '',
			'description' => '',
		)
	);

	$parse_url   = wp_parse_url( $preview_data['url'] );
	$domain_name = '';
	if ( ! empty( $parse_url['host'] ) ) {
		$domain_name = str_replace( 'www.', '', $parse_url['host'] );
	}

	$description = $preview_data['description'];
	$read_more   = ' &hellip; <a class="activity-link-preview-more" href="' . esc_url( $preview_data['url'] ) . '" target="_blank" rel="nofollow">' . __( 'Continue reading', 'buddyboss' ) . '</a>';
	$description = wp_trim_words( $description, 40, $read_more );

	$content = make_clickable( $content );

	$content .= '<div class="activity-link-preview-container">';
	if ( ! empty( $preview_data['attachment_id'] ) ) {
		$image_url = wp_get_attachment_image_url( $preview_data['attachment_id'], 'full' );
		$content  .= '<div class="activity-link-preview-image">';
		$content  .= '<div class="activity-link-preview-image-cover">';
		$content  .= '<a href="' . esc_url( $preview_data['url'] ) . '" target="_blank"><img src="' . esc_url( $image_url ) . '" /></a>';
		$content  .= '</div>';
		$content  .= '</div>';
	} elseif ( ! empty( $preview_data['image_url'] ) ) {
		$content .= '<div class="activity-link-preview-image">';
		$content .= '<div class="activity-link-preview-image-cover">';
		$content .= '<a href="' . esc_url( $preview_data['url'] ) . '" target="_blank"><img src="' . esc_url( $preview_data['image_url'] ) . '" /></a>';
		$content .= '</div>';
		$content .= '</div>';
	}
	$content .= '<div class="activity-link-preview-info">';
	$content .= '<p class="activity-link-preview-link-name">' . esc_html( $domain_name ) . '</p>';
	$content .= '<p class="activity-link-preview-title"><a href="' . esc_url( $preview_data['url'] ) . '" target="_blank" rel="nofollow">' . esc_html( $preview_data['title'] ) . '</a></p>';
	$content .= '<div class="activity-link-preview-excerpt"><p>' . $description . '</p></div>';
	$content .= '</div>';
	$content .= '</div>';

	return $content;
}

/**
 * Include extra JavaScript dependencies for activity component.
 *
 * @since BuddyPress 2.0.0
 *
 * @param array $js_handles The original dependencies.
 * @return array $js_handles The new dependencies.
 */
function bp_activity_get_js_dependencies( $js_handles = array() ) {
	if ( bp_activity_do_heartbeat() ) {
		$js_handles[] = 'heartbeat';
	}

	return $js_handles;
}
// add_filter( 'bp_core_get_js_dependencies', 'bp_activity_get_js_dependencies', 10, 1 );
// NOTICE: this dependency breaks activity stream when heartbeat is dequed via external sources

/**
 * Enqueue Heartbeat js for the activity
 *
 * @since BuddyBoss 1.1.1
 */
function bp_activity_enqueue_heartbeat_js() {
	if ( bp_activity_do_heartbeat() ) {
		wp_enqueue_script( 'heartbeat' );
	}
}
add_action( 'bp_nouveau_enqueue_scripts', 'bp_activity_enqueue_heartbeat_js' );

/**
 * Add a just-posted classes to the most recent activity item.
 *
 * We use these classes to avoid pagination issues when items are loaded
 * dynamically into the activity feed.
 *
 * @since BuddyPress 2.0.0
 *
 * @param string $classes Array of classes for most recent activity item.
 * @return string $classes
 */
function bp_activity_newest_class( $classes = '' ) {
	$bp = buddypress();

	if ( ! empty( $bp->activity->last_recorded ) && $bp->activity->last_recorded == bp_get_activity_date_recorded() ) {
		$classes .= ' new-update';
	}

	$classes .= ' just-posted';
	return $classes;
}

/**
 * Returns $args to force display of all member activity types on members activity feed.
 *
 * @since BuddyBoss 1.0.0
 *
 * @param array $args
 * @return array $args
 */
function bp_activity_display_all_types_on_just_me( $args ) {

	if ( empty( $args['scope'] ) || 'all' !== $args['scope'] || ! bp_loggedin_user_id() ) {
		return $args;
	}

	if ( bp_is_user() && 'all' === $args['scope'] && empty( bp_current_action() ) ) {
		$scope         = array( 'just-me' );
		$args['scope'] = implode( ',', $scope );
		return $args;
	}

	$scope = array( 'just-me' );
	if ( bp_activity_do_mentions() ) {
		$scope[] = 'mentions';
	}

	if ( bp_is_active( 'friends' ) ) {
		$scope[] = 'friends';
	}

	if ( bp_is_active( 'groups' ) ) {
		$scope[] = 'groups';
	}

	if ( bp_is_activity_follow_active() ) {
		$scope[] = 'following';
	}

	$args['scope'] = implode( ',', $scope );

	return $args;
}

/**
 * Check if Activity Heartbeat feature i on to add a timestamp class.
 *
 * @since BuddyPress 2.0.0
 *
 * @param string $classes Array of classes for timestamp.
 * @return string $classes
 */
function bp_activity_timestamp_class( $classes = '' ) {

	if ( ! bp_activity_do_heartbeat() ) {
		return $classes;
	}

	$activity_date = bp_get_activity_date_recorded();

	if ( empty( $activity_date ) ) {
		return $classes;
	}

	$classes .= ' date-recorded-' . strtotime( $activity_date );

	return $classes;
}
add_filter( 'bp_get_activity_css_class', 'bp_activity_timestamp_class', 9, 1 );

/**
 * Use WordPress Heartbeat API to check for latest activity update.
 *
 * @since BuddyPress 2.0.0
 *
 * @param array $response Array containing Heartbeat API response.
 * @param array $data     Array containing data for Heartbeat API response.
 * @return array $response
 */
function bp_activity_heartbeat_last_recorded( $response = array(), $data = array() ) {
	if ( empty( $data['bp_activity_last_recorded'] ) ) {
		return $response;
	}

	if ( ! bp_is_activity_heartbeat_active() ) {
		return $response;
	}

	// Use the querystring argument stored in the cookie (to preserve
	// filters), but force the offset to get only new items.
	$activity_latest_args = bp_parse_args(
		bp_ajax_querystring( 'activity' ),
		array( 'since' => date_i18n( 'Y-m-d H:i:s', $data['bp_activity_last_recorded'] ) ),
		'activity_latest_args'
	);

	if ( ! empty( $data['bp_activity_last_recorded_search_terms'] ) && empty( $activity_latest_args['search_terms'] ) ) {
		$activity_latest_args['search_terms'] = addslashes( $data['bp_activity_last_recorded_search_terms'] );
	}

	$newest_activities      = array();
	$last_activity_recorded = 0;

	// Temporarily add a just-posted class for new activity items.
	add_filter( 'bp_get_activity_css_class', 'bp_activity_newest_class', 10, 1 );

	ob_start();
	if ( bp_has_activities( $activity_latest_args ) ) {
		while ( bp_activities() ) {
			bp_the_activity();

			$atime = strtotime( bp_get_activity_date_recorded() );
			if ( $last_activity_recorded < $atime ) {
				$last_activity_recorded = $atime;
			}

			bp_get_template_part( 'activity/entry' );
		}
	}

	$newest_activities['activities']    = ob_get_contents();
	$newest_activities['last_recorded'] = $last_activity_recorded;
	ob_end_clean();

	// Remove the temporary filter.
	remove_filter( 'bp_get_activity_css_class', 'bp_activity_newest_class', 10 );

	if ( ! empty( $newest_activities['last_recorded'] ) ) {
		$response['bp_activity_newest_activities'] = $newest_activities;
	}

	return $response;
}
add_filter( 'heartbeat_received', 'bp_activity_heartbeat_last_recorded', 10, 2 );
add_filter( 'heartbeat_nopriv_received', 'bp_activity_heartbeat_last_recorded', 10, 2 );

/**
 * Set the strings for WP HeartBeat API where needed.
 *
 * @since BuddyPress 2.0.0
 *
 * @param array $strings Localized strings.
 * @return array $strings
 */
function bp_activity_heartbeat_strings( $strings = array() ) {

	if ( ! bp_activity_do_heartbeat() ) {
		return $strings;
	}

	$global_pulse = 0;

	/**
	 * Filter that checks whether the global heartbeat settings already exist.
	 *
	 * @since BuddyPress 2.0.0
	 *
	 * @param array $value Heartbeat settings array.
	 */
	$heartbeat_settings = apply_filters( 'heartbeat_settings', array() );
	if ( ! empty( $heartbeat_settings['interval'] ) ) {
		// 'Fast' is 5
		$global_pulse = is_numeric( $heartbeat_settings['interval'] ) ? absint( $heartbeat_settings['interval'] ) : 5;
	}

	/**
	 * Filters the pulse frequency to be used for the BuddyBoss Activity heartbeat.
	 *
	 * @since BuddyPress 2.0.0
	 *
	 * @param int $value The frequency in seconds between pulses.
	 */
	$bp_activity_pulse = apply_filters( 'bp_activity_heartbeat_pulse', 15 );

	/**
	 * Use the global pulse value unless:
	 * a. the BP-specific value has been specifically filtered, or
	 * b. it doesn't exist (ie, BP will be the only one using the heartbeat,
	 *    so we're responsible for enabling it)
	 */
	if ( has_filter( 'bp_activity_heartbeat_pulse' ) || empty( $global_pulse ) ) {
		$pulse = $bp_activity_pulse;
	} else {
		$pulse = $global_pulse;
	}

	$strings = array_merge(
		$strings,
		array(
			'newest' => __( 'Load Newest', 'buddyboss' ),
			'pulse'  => absint( $pulse ),
		)
	);

	return $strings;
}
add_filter( 'bp_core_get_js_strings', 'bp_activity_heartbeat_strings', 10, 1 );

/** Scopes ********************************************************************/

/**
 * Set up activity arguments for use with the 'just-me' scope.
 *
 * @since BuddyPress 2.2.0
 *
 * @param array $retval Empty array by default.
 * @param array $filter Current activity arguments.
 * @return array $retval
 */
function bp_activity_filter_just_me_scope( $retval = array(), $filter = array() ) {

	// Determine the user_id.
	if ( ! empty( $filter['user_id'] ) ) {
		$user_id = $filter['user_id'];
	} else {
		$user_id = bp_displayed_user_id()
			? bp_displayed_user_id()
			: bp_loggedin_user_id();
	}

	// Should we show all items regardless of sitewide visibility?
	$show_hidden = array();
	if ( ! empty( $user_id ) && $user_id !== bp_loggedin_user_id() ) {
		$show_hidden = array(
			'column' => 'hide_sitewide',
			'value'  => 0,
		);
	}

	$privacy = array( 'public' );
	if ( is_user_logged_in() ) {
		$privacy[] = 'loggedin';
		if ( bp_is_active( 'friends' ) ) {

			// Determine friends of user.
			$friends = friends_get_friend_user_ids( $user_id );

			if ( $user_id === bp_loggedin_user_id() || bp_is_activity_directory() ) {
				$friends[] = bp_loggedin_user_id();
			}

			if ( ! empty( $friends ) && in_array( bp_loggedin_user_id(), $friends ) ) {
				$privacy[] = 'friends';
			}
		}

		if ( $user_id === bp_loggedin_user_id() ) {
			$privacy[] = 'onlyme';
		}
	}

	$retval = array(
		'relation' => 'AND',
		array(
			'column' => 'user_id',
			'value'  => $user_id,
		),
		array(
			'column'  => 'privacy',
			'value'   => $privacy,
			'compare' => 'IN',
		),
		$show_hidden,

		// Overrides.
		'override' => array(
			// 'display_comments' => bp_show_streamed_activity_comment() ? 'stream' : 'threaded',
			'filter'      => array( 'user_id' => 0 ),
			'show_hidden' => true,
		),
	);

	return $retval;
}
add_filter( 'bp_activity_set_just-me_scope_args', 'bp_activity_filter_just_me_scope', 10, 2 );

/**
 * Set up activity arguments for use with the 'public' scope.
 *
 * @since BuddyBoss 1.4.3
 *
 * @param array $retval Empty array by default.
 * @param array $filter Current activity arguments.
 * @return array $retval
 */
function bp_activity_filter_public_scope( $retval = array(), $filter = array() ) {

	$privacy = array( 'public' );
	if ( bp_loggedin_user_id() ) {
		$privacy[] = 'loggedin';
	}

	$retval = array(
		'relation' => 'AND',
		array(
			'column'  => 'privacy',
			'value'   => $privacy,
			'compare' => 'IN',
		),
		array(
			'column' => 'hide_sitewide',
			'value'  => 0,
		),
	);

	return $retval;
}
add_filter( 'bp_activity_set_public_scope_args', 'bp_activity_filter_public_scope', 10, 2 );

/**
 * Set up activity arguments for use with the 'favorites' scope.
 *
 * @since BuddyPress 2.2.0
 *
 * @param array $retval Empty array by default.
 * @param array $filter Current activity arguments.
 * @return array $retval
 */
function bp_activity_filter_favorites_scope( $retval = array(), $filter = array() ) {

	// Determine the user_id.
	if ( ! empty( $filter['user_id'] ) ) {
		$user_id = $filter['user_id'];
	} else {
		$user_id = bp_displayed_user_id()
			? bp_displayed_user_id()
			: bp_loggedin_user_id();
	}

	// Determine the favorites.
	$favs = bp_activity_get_user_favorites( $user_id );
	if ( empty( $favs ) ) {
		$favs = array( 0 );
	}

	// Should we show all items regardless of sitewide visibility?
	$show_hidden = array();
	if ( ! empty( $user_id ) && ( $user_id !== bp_loggedin_user_id() ) ) {
		$show_hidden = array(
			'column' => 'hide_sitewide',
			'value'  => 0,
		);
	}

	$friends_filter = array();
	$onlyme_filter  = array();
	$privacy        = array( 'public' );
	if ( is_user_logged_in() ) {
		$privacy[] = 'loggedin';

		if ( bp_is_active( 'friends' ) ) {
			// Determine friends of user.
			$friends = friends_get_friend_user_ids( $user_id );
			if ( empty( $friends ) ) {
				$friends = array( 0 );
			}

			if ( $user_id === bp_loggedin_user_id() ) {
				$friends[] = bp_loggedin_user_id();
				$friends   = array_unique( $friends );
			}

			if ( ! empty( $friends ) ) {
				$friends_filter = array(
					'relation' => 'AND',
					array(
						'column'  => 'user_id',
						'compare' => 'IN',
						'value'   => (array) $friends,
					),
					array(
						'column'  => 'privacy',
						'compare' => '=',
						'value'   => 'friends',
					),
					array(
						'column'  => 'id',
						'compare' => 'IN',
						'value'   => (array) $favs,
					),
					$show_hidden,
				);
			}
		}

		if ( $user_id === bp_loggedin_user_id() ) {
			$onlyme_filter = array(
				'relation' => 'AND',
				array(
					'column'  => 'user_id',
					'compare' => '=',
					'value'   => $user_id,
				),
				array(
					'column'  => 'privacy',
					'compare' => '=',
					'value'   => 'onlyme',
				),
				array(
					'column'  => 'id',
					'compare' => 'IN',
					'value'   => (array) $favs,
				),
				$show_hidden,
			);
			$privacy[]     = 'loggedin';
		}
	}

	$retval = array(
		'relation' => 'AND',
		array(
			'column'  => 'id',
			'compare' => 'IN',
			'value'   => (array) $favs,
		),
		array(
			'column'  => 'privacy',
			'compare' => 'IN',
			'value'   => $privacy,
		),
		$show_hidden,
	);

	if ( empty( $friends_filter ) && empty( $onlyme_filter ) ) {
		$retval['override'] = array(
			'filter'      => array( 'user_id' => 0 ),
			'show_hidden' => true,
		);
	}

	if ( ! empty( $friends_filter ) || ! empty( $onlyme_filter ) ) {
		$retval = array(
			'relation' => 'OR',
			$retval,
			$friends_filter,
			$onlyme_filter,
			// Overrides.
			'override' => array(
				'filter'      => array( 'user_id' => 0 ),
				'show_hidden' => true,
			),
		);
	}

	return $retval;
}
add_filter( 'bp_activity_set_favorites_scope_args', 'bp_activity_filter_favorites_scope', 10, 2 );


/**
 * Set up activity arguments for use with the 'favorites' scope.
 *
 * @since BuddyPress 2.2.0
 *
 * @param array $retval Empty array by default.
 * @param array $filter Current activity arguments.
 * @return array $retval
 */
function bp_activity_filter_mentions_scope( $retval = array(), $filter = array() ) {

	// Are mentions disabled?
	if ( ! bp_activity_do_mentions() ) {
		return $retval;
	}

	// Determine the user_id.
	if ( ! empty( $filter['user_id'] ) ) {
		$user_id = $filter['user_id'];
	} else {
		$user_id = bp_displayed_user_id()
			? bp_displayed_user_id()
			: bp_loggedin_user_id();
	}

	$privacy     = array( 'public' );
	$friends     = array();
	$show_hidden = array();
	$user_groups = array();

	if ( is_user_logged_in() ) {
		$privacy[] = 'loggedin';

		if ( bp_is_active( 'friends' ) && $user_id ) {
			// Determine friends of user.
			$friends = friends_get_friend_user_ids( $user_id );
		}
	}

	if ( bp_is_active( 'groups' ) ) {

		if ( ! empty( $user_id ) && $user_id !== bp_loggedin_user_id() ) {
			$show_hidden = array(
				'column' => 'hide_sitewide',
				'value'  => 0,
			);
		}

		// Fetch public groups.
		$public_groups = groups_get_groups(
			array(
				'fields'   => 'ids',
				'status'   => 'public',
				'per_page' => - 1,
			)
		);

		if ( ! empty( $public_groups['groups'] ) ) {
			$public_groups = $public_groups['groups'];
		} else {
			$public_groups = array();
		}

		if ( is_user_logged_in() ) {
			$groups = groups_get_user_groups( $user_id );
			if ( ! empty( $groups['groups'] ) ) {
				$user_groups = $groups['groups'];
			} else {
				$user_groups = array();
			}
		}

		$user_groups = array_unique( array_merge( $user_groups, $public_groups ) );
	}

	$privacy_scope = array();
	if ( ! empty( $friends ) ) {
		$privacy_scope[] = array(
			'relation' => 'AND',
			array(
				'column'  => 'user_id',
				'compare' => 'IN',
				'value'   => (array) $friends,
			),
			array(
				'column'  => 'privacy',
				'compare' => '=',
				'value'   => 'friends',
			),
		);
	}

	if ( ! empty( $user_groups ) ) {
		$privacy_scope[] = array(
			'relation' => 'AND',
			array(
				'column'  => 'item_id',
				'compare' => 'IN',
				'value'   => $user_groups,
			),
			array(
				'column' => 'component',
				'value'  => buddypress()->groups->id,
			),
			array(
				'column'  => 'privacy',
				'compare' => '=',
				'value'   => 'public',
			),
			$show_hidden,
		);
	}

	$privacy_scope[] = array(
		'relation' => 'AND',
		array(
			'column'  => 'privacy',
			'compare' => 'IN',
			'value'   => $privacy,
		),
		array(
			'column'  => 'component',
			'compare' => '!=',
			'value'   => 'groups',
		),
	);

	if ( ! empty( $privacy_scope ) && count( $privacy_scope ) > 1 ) {
		$privacy_scope['relation'] = 'OR';
	}

	$retval = array(
		'relation' => 'AND',
		array(
			'column'  => 'content',
			'compare' => 'LIKE',

			// Start search at @ symbol and stop search at closing tag delimiter.
			'value'   => '@' . bp_activity_get_user_mentionname( $user_id ) . '<',
		),
		// Overrides.
		'override' => array(
			// 'display_comments' => bp_show_streamed_activity_comment() ? 'stream' : 'threaded',
			'filter'      => array( 'user_id' => 0 ),
			'show_hidden' => true,
		),
		$privacy_scope,
	);

	return $retval;
}
add_filter( 'bp_activity_set_mentions_scope_args', 'bp_activity_filter_mentions_scope', 10, 2 );

/**
 * Filter the members loop on a follow page.
 *
 * This is done so we can return users that:
 *   - the current user is following (on a user page or member directory); or
 *   - are following the displayed user on the displayed user's followers page
 *
 * @since BuddyBoss 1.0.0
 *
 * @param array|string $qs The querystring for the BP loop.
 * @param str          $object The current object for the querystring.
 *
 * @return array|string Modified querystring
 */
function bp_add_member_follow_scope_filter( $qs, $object ) {
	// not on the members object? stop now!
	if ( 'members' !== $object ) {
		return $qs;
	}

	// members directory
	if ( ! bp_is_user() && bp_is_members_directory() ) {
		$qs_args = bp_parse_args( $qs );
		// check if members scope is following before manipulating.
		if ( isset( $qs_args['scope'] ) && 'following' === $qs_args['scope'] ) {
			$qs .= '&include=' . bp_get_following_ids(
				array(
					'user_id' => bp_loggedin_user_id(),
				)
			);
		}
	}

	return $qs;
}
add_filter( 'bp_ajax_querystring', 'bp_add_member_follow_scope_filter', 20, 2 );

/**
 * Set up activity arguments for use with the 'following' scope.
 *
 * For details on the syntax, see {@link BP_Activity_Query}.
 *
 * @since BuddyBoss 1.0.0
 *
 * @param array $retval Empty array by default.
 * @param array $filter Current activity arguments.
 *
 * @return array
 */
function bp_users_filter_activity_following_scope( $retval = array(), $filter = array() ) {

	// Is follow active?
	if ( ! bp_is_activity_follow_active() ) {
		return $retval;
	}

	// Determine the user_id.
	if ( ! empty( $filter['user_id'] ) ) {
		$user_id = $filter['user_id'];
	} else {
		$user_id = bp_displayed_user_id()
			? bp_displayed_user_id()
			: bp_loggedin_user_id();
	}

	// Determine following of user.
	$following_ids = bp_get_following(
		array(
			'user_id' => $user_id,
		)
	);

	if ( empty( $following_ids ) ) {
		$following_ids = array( 0 );
	}

	$privacy = array( 'public' );
	if ( bp_loggedin_user_id() ) {
		$privacy[] = 'loggedin';
	}

	$friends_follow = array();
	if ( bp_is_active( 'friends' ) && $user_id === bp_loggedin_user_id() ) {
		// Determine friends of user.
		$friends = friends_get_friend_user_ids( $user_id );
		if ( ! empty( $friends ) ) {

			$friends_follower_ids = array_intersect( $following_ids, $friends );
			if ( ! empty( $friends_follower_ids ) ) {
				$friends_follow[] = array(
					'relation' => 'AND',
					array(
						'column'  => 'user_id',
						'compare' => 'IN',
						'value'   => (array) $friends_follower_ids,
					),
					array(
						'column'  => 'privacy',
						'compare' => '=',
						'value'   => 'friends',
					),
				);
			}
		}
	}

	$friends_follow[] = array(
		'relation' => 'AND',
		array(
			'column'  => 'user_id',
			'compare' => 'IN',
			'value'   => (array) $following_ids,
		),
		array(
			'column'  => 'privacy',
			'compare' => 'IN',
			'value'   => (array) $privacy,
		),
	);

	if ( ! empty( $friends_follow ) && count( $friends_follow ) > 1 ) {
		$friends_follow['relation'] = 'OR';
	}

	$retval = array(
		'relation' => 'AND',
		$friends_follow,

		// we should only be able to view sitewide activity content for those the user
		// is following.
		array(
			'column' => 'hide_sitewide',
			'value'  => 0,
		),

		// overrides.
		'override' => array(
			'filter'      => array(
				'user_id' => 0,
			),
			'show_hidden' => true,
		),
	);

	return $retval;
}

add_filter( 'bp_activity_set_following_scope_args', 'bp_users_filter_activity_following_scope', 10, 2 );

/**
 * Do not add the activity if uncheck the options from the
 * backend BuddyBoss > Settings > Activity > Posts in Activity Feed >BuddyBoss Platform
 *
 * @param $activity_object
 *
 * @since BuddyBoss 1.0.0
 */
function bp_activity_remove_platform_updates( $activity_object ) {

	if ( false === bp_platform_is_feed_enable( 'bp-feed-platform-' . $activity_object->type ) ) {
		$activity_object->type = false;
	}
}

/**
 * Fix to BuddyBoss media activity data
 *
 * @since BuddyBoss 1.0.0
 */
function bp_activity_media_fix_data() {

	$privacy    = array( 'public', 'loggedin', 'friends', 'onlyme', 'grouponly', 'media' );
	$meta_query = array(
		array(
			'relation' => 'OR',
			'key'      => 'bp_media_activity',
			'compare'  => 'EXISTS',
		),
	);

	$result = BP_Activity_Activity::get(
		array(
			'per_page'    => 10000,
			'privacy'     => $privacy,
			'meta_query'  => $meta_query,
			'show_hidden' => true,
		)
	);

	if ( ! empty( $result['activities'] ) ) {
		foreach ( $result['activities'] as $activity ) {
			$activity = new BP_Activity_Activity( $activity->id );

			if ( ! empty( $activity ) ) {
				$activity->privacy = 'media';
				$activity->save();
			}
		}
	}
}

/**
 * Filter the activities of friends privacy
 *
 * @since BuddyBoss 1.0.0
 * @param $has_activities
 * @param $activities
 *
 * @return mixed
 */
function bp_activity_has_activity_filter( $has_activities, $activities ) {

	if ( ! $has_activities || ! bp_is_active( 'friends' ) || ! is_user_logged_in() || is_super_admin() ) {
		return $has_activities;
	}

	if ( ! empty( $activities->activities ) ) {
		foreach ( $activities->activities as $key => $activity ) {

			if ( 'friends' == $activity->privacy && $activity->user_id !== bp_loggedin_user_id() ) {

				$remove_from_stream = false;
				$is_friend          = friends_check_friendship( bp_loggedin_user_id(), $activity->user_id );
				if ( ! $is_friend ) {
					$remove_from_stream = true;
				}

				if ( $remove_from_stream && isset( $activities->activity_count ) ) {
					$activities->activity_count = $activities->activity_count - 1;

					if ( isset( $activities->total_activity_count ) ) {
						$activities->total_activity_count = $activities->total_activity_count - 1;
					}

					unset( $activities->activities[ $key ] );
				}
			}
		}
	}

	$activities->activities = array_values( $activities->activities );
	if ( $activities->activity_count === 0 ) {
		return false;
	}
	return $has_activities;
}

/**
 * Filter the activities for document and media privacy
 *
 * @since BuddyBoss 1.4.3
 * @param $has_activities
 * @param $activities
 *
 * @return mixed
 */
function bp_activity_has_media_activity_filter( $has_activities, $activities ) {

	if ( ! $has_activities || ! bp_is_active( 'media' ) || ! bp_is_single_activity() ) {
		return $has_activities;
	}

	if ( ! empty( $activities->activities ) ) {
		foreach ( $activities->activities as $key => $activity ) {
			if ( in_array( $activity->privacy, array( 'media', 'document' ), true ) ) {
				$parent_activity_id = false;
				if ( ! empty( $activity->secondary_item_id ) ) {
					$parent_activity_id = $activity->secondary_item_id;
				} else {
					$attachment_id = BP_Media::get_activity_attachment_id( $activity->id );
					if ( ! empty( $attachment_id ) ) {
						$parent_activity_id = get_post_meta( $attachment_id, 'bp_media_parent_activity_id', true );
					} else {
						$attachment_id = BP_Video::get_activity_attachment_id( $activity->id );
						if ( ! empty( $attachment_id ) ) {
							$parent_activity_id = get_post_meta( $attachment_id, 'bp_video_parent_activity_id', true );
						}
					}
				}

				if ( ! empty( $parent_activity_id ) ) {
					$parent         = new BP_Activity_Activity( $parent_activity_id );
					$parent_user    = $parent->user_id;
					$parent_privacy = $parent->privacy;

					if ( 'public' === $parent_privacy ) {
						continue;
					}

					$remove_from_stream = false;

					if ( 'loggedin' === $parent_privacy && ! bp_loggedin_user_id() ) {
						$remove_from_stream = true;
					}

					if ( false === $remove_from_stream && 'onlyme' === $parent_privacy && bp_loggedin_user_id() !== $parent_user ) {
						$remove_from_stream = true;
					}

					if ( false === $remove_from_stream && 'friends' === $parent_privacy ) {
						if ( bp_is_active( 'friends' ) ) {
							$is_friend = friends_check_friendship( bp_loggedin_user_id(), $parent_user );
							if ( ! $is_friend && bp_loggedin_user_id() !== $parent_user ) {
								$remove_from_stream = true;
							}
						} else {
							$remove_from_stream = true;
						}
					}

					if ( $remove_from_stream && isset( $activities->activity_count ) ) {
						$activities->activity_count = $activities->activity_count - 1;

						if ( isset( $activities->total_activity_count ) ) {
							$activities->total_activity_count = $activities->total_activity_count - 1;
						}

						unset( $activities->activities[ $key ] );
					}
				}
			}
		}
	}

	$activities->activities = array_values( $activities->activities );
	if ( 0 === $activities->activity_count ) {
		return false;
	}

	return $has_activities;
}

/**
 * Create media activity for each media uploaded
 *
 * @since BuddyBoss 1.2.0
 * @param $media
 */
function bp_activity_media_add( $media ) {
	global $bp_media_upload_count, $bp_new_activity_comment, $bp_activity_post_update_id, $bp_activity_post_update;

	if ( ! empty( $media ) && empty( $media->activity_id ) ) {
		$parent_activity_id = false;
		if ( ! empty( $bp_activity_post_update ) && ! empty( $bp_activity_post_update_id ) ) {
			$parent_activity_id = (int) $bp_activity_post_update_id;
		}

		if ( $bp_media_upload_count > 1 || ! empty( $bp_new_activity_comment ) ) {

			if ( bp_is_active( 'groups' ) && ! empty( $bp_new_activity_comment ) && empty( $media->group_id ) ) {
				$comment = new BP_Activity_Activity( $bp_new_activity_comment );

				if ( ! empty( $comment->item_id ) ) {
					$comment_activity = new BP_Activity_Activity( $comment->item_id );
					if ( ! empty( $comment_activity->component ) && buddypress()->groups->id === $comment_activity->component ) {
						$media->group_id = $comment_activity->item_id;
						$media->privacy  = 'comment';
						$media->album_id = 0;
					}
				}
			}

			// Check when new activity coment is empty then set privacy comment - 2121.
			if ( ! empty( $bp_new_activity_comment ) ) {
				$activity_id     = $bp_new_activity_comment;
				$media->privacy  = 'comment';
				$media->album_id = 0;
			} else {

				$args = array(
					'hide_sitewide' => true,
					'privacy'       => 'media',
				);

				// Create activity only if not created previously.
				if ( ! empty( $media->group_id ) && bp_is_active( 'groups' ) ) {
					$args['item_id'] = $media->group_id;
					$args['type']    = 'activity_update';
					$current_group   = groups_get_group( $media->group_id );
					$args['action']  = sprintf(
					/* translators: 1. User Link. 2. Group link. */
						__( '%1$s posted an update in the group %2$s', 'buddyboss' ),
						bp_core_get_userlink( $media->user_id ),
						'<a href="' . bp_get_group_permalink( $current_group ) . '">' . bp_get_group_name( $current_group ) . '</a>'
					);
					$activity_id = groups_record_activity( $args );
				} else {
					$activity_id = bp_activity_post_update( $args );
				}
			}

			if ( $activity_id ) {

				// save media activity id in media.
				$media->activity_id = $activity_id;
				$media->save();

				// update activity meta.
				bp_activity_update_meta( $activity_id, 'bp_media_activity', '1' );
				bp_activity_update_meta( $activity_id, 'bp_media_id', $media->id );

				// save attachment meta for activity.
				update_post_meta( $media->attachment_id, 'bp_media_activity_id', $activity_id );

				if ( $parent_activity_id ) {

					// If new activity comment is empty - 2121.
					if ( empty( $bp_new_activity_comment ) ) {
						$media_activity                    = new BP_Activity_Activity( $activity_id );
						$media_activity->secondary_item_id = $parent_activity_id;
						$media_activity->save();
					}

					// save parent activity id in attachment meta.
					update_post_meta( $media->attachment_id, 'bp_media_parent_activity_id', $parent_activity_id );
				}
			}
		} else {

			if ( $parent_activity_id ) {

				// If the media posted in activity comment then set the activity id to comment id.- 2121.
				if ( ! empty( $bp_new_activity_comment ) ) {
					$parent_activity_id = $bp_new_activity_comment;
					$media->privacy     = 'comment';
				}

				// save media activity id in media.
				$media->activity_id = $parent_activity_id;
				$media->save();

				// save parent activity id in attachment meta.
				update_post_meta( $media->attachment_id, 'bp_media_parent_activity_id', $parent_activity_id );
			}
		}
	}
}

/**
 * Create main activity for the media uploaded and saved.
 *
 * @since BuddyBoss 1.2.0
 *
 * @param $media_ids
 *
 * @return mixed
 */
function bp_activity_create_parent_media_activity( $media_ids ) {
	global $bp_media_upload_count, $bp_activity_post_update, $bp_media_upload_activity_content, $bp_activity_post_update_id, $bp_activity_edit;

	if ( ! empty( $media_ids ) && empty( $bp_activity_post_update ) && ! isset( $_POST['edit'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing

		$added_media_ids = $media_ids;
		$content         = false;

		if ( ! empty( $bp_media_upload_activity_content ) ) {

			/**
			 * Filters the content provided in the activity input field.
			 *
			 * @param string $value Activity message being posted.
			 *
			 * @since BuddyPress 1.2.0
			 */
			$content = apply_filters( 'bp_activity_post_update_content', $bp_media_upload_activity_content );
		}

		$group_id = FILTER_INPUT( INPUT_POST, 'group_id', FILTER_SANITIZE_NUMBER_INT );
		$album_id = false;

		// added fall back to get group_id from media.
		if ( empty( $group_id ) && ! empty( $added_media_ids ) ) {
			$media_object = new BP_Media( current( (array) $added_media_ids ) );
			if ( ! empty( $media_object->group_id ) ) {
				$group_id = $media_object->group_id;
			}
		}

		if ( bp_is_active( 'groups' ) && ! empty( $group_id ) && $group_id > 0 ) {
			remove_action( 'bp_groups_posted_update', 'bb_subscription_send_subscribe_group_notifications', 10, 4 );
			$activity_id = groups_post_update(
				array(
					'content'  => $content,
					'group_id' => $group_id,
				)
			);
			add_action( 'bp_groups_posted_update', 'bb_subscription_send_subscribe_group_notifications', 10, 4 );
		} else {
			remove_action( 'bp_activity_posted_update', 'bb_activity_send_email_to_following_post', 10, 3 );
			$activity_id = bp_activity_post_update( array( 'content' => $content ) );
			add_action( 'bp_activity_posted_update', 'bb_activity_send_email_to_following_post', 10, 3 );
		}

		// save media meta for activity.
		if ( ! empty( $activity_id ) ) {
			$privacy = 'public';

			foreach ( (array) $added_media_ids as $media_id ) {
				$media = new BP_Media( $media_id );

				// get one of the media's privacy for the activity privacy.
				$privacy = $media->privacy;

				// get media album id.
				if ( ! empty( $media->album_id ) ) {
					$album_id = $media->album_id;
				}

				if ( 1 === $bp_media_upload_count ) {
					// save media activity id in media.
					$media->activity_id = $activity_id;
					$media->save();
				}

				// save parent activity id in attachment meta.
				update_post_meta( $media->attachment_id, 'bp_media_parent_activity_id', $activity_id );
			}

			bp_activity_update_meta( $activity_id, 'bp_media_ids', implode( ',', $added_media_ids ) );

			// if media is from album then save album id in activity media.
			if ( ! empty( $album_id ) ) {
				bp_activity_update_meta( $activity_id, 'bp_media_album_activity', $album_id );
			}

			$main_activity = new BP_Activity_Activity( $activity_id );
			if ( empty( $group_id ) ) {
				if ( ! empty( $main_activity ) ) {
					$main_activity->privacy = $privacy;
					$main_activity->save();
				}
			}

			if ( bp_is_active( 'media' ) && ! empty( $main_activity->id ) && count( $media_ids ) > 1 ) {
				foreach ( $media_ids as $id ) {
					$media = new BP_Media( $id );
					if ( ! empty( $media->activity_id ) ) {
						$media_activity = new BP_Activity_Activity( $media->activity_id );
						if ( ! empty( $media_activity->id ) ) {
							$media_activity->secondary_item_id = $main_activity->id;
							$media_activity->save();
						}
					}
				}
			}

			if ( ! empty( $main_activity->id ) ) {
				do_action( 'bb_media_after_create_parent_activity', $main_activity->content, $main_activity->user_id, $main_activity->id );
			}
		}
	}

	return $media_ids;
}

/**
 * Update media and activity for media updation and deletion while editing the activity.
 *
 * @param $media_ids
 *
 * @return mixed
 * @since BuddyBoss 1.5.0
 */
function bp_activity_edit_update_media( $media_ids ) {
	global $bp_activity_edit, $bp_activity_post_update_id;

	if ( ( true === $bp_activity_edit || isset( $_POST['edit'] ) ) && ! empty( $bp_activity_post_update_id ) ) {
		$old_media_ids = bp_activity_get_meta( $bp_activity_post_update_id, 'bp_media_ids', true );
		$old_media_ids = explode( ',', $old_media_ids );

		if ( ! empty( $old_media_ids ) ) {
			$old_media_ids = wp_parse_id_list( $old_media_ids );
			$media_ids     = wp_parse_id_list( $media_ids );

			// old media count 1 and new media uploaded count is greater than 1.
			if ( 1 === count( $old_media_ids ) && 1 < count( $media_ids ) ) {
				$old_media_id = (int) $old_media_ids[0];

				// check if old media id is in new media uploaded.
				if ( in_array( $old_media_id, $media_ids, true ) ) {

					// Create new media activity for old media because it has only parent activity to show right now.
					$old_media = new BP_Media( $old_media_id );
					$args      = array(
						'hide_sitewide' => true,
						'privacy'       => 'media',
					);

					if ( ! empty( $old_media->group_id ) && bp_is_active( 'groups' ) ) {
						$args['item_id'] = $old_media->group_id;
						$args['type']    = 'activity_update';
						$current_group   = groups_get_group( $old_media->group_id );
						$args['action']  = sprintf( __( '%1$s posted an update in the group %2$s', 'buddyboss' ), bp_core_get_userlink( $old_media->user_id ), '<a href="' . bp_get_group_permalink( $current_group ) . '">' . bp_get_group_name( $current_group ) . '</a>' );
						$activity_id     = groups_record_activity( $args );
					} else {
						$activity_id = bp_activity_post_update( $args );
					}

					// media activity for old media is created and it is being assigned to the old media.
					// And media activity is being saved with needed data to figure out everything for it.
					if ( $activity_id ) {
						$old_media->activity_id = $activity_id;
						$old_media->save();

						$media_activity                    = new BP_Activity_Activity( $activity_id );
						$media_activity->secondary_item_id = $bp_activity_post_update_id;
						$media_activity->save();

						// update activity meta to tell it is media activity.
						bp_activity_update_meta( $activity_id, 'bp_media_activity', '1' );
						bp_activity_update_meta( $activity_id, 'bp_media_id', $old_media->id );

						// save attachment meta for activity.
						update_post_meta( $old_media->attachment_id, 'bp_media_activity_id', $activity_id );

						// save parent activity id in attachment meta.
						update_post_meta( $old_media->attachment_id, 'bp_media_parent_activity_id', $bp_activity_post_update_id );
					}
				}

				// old media count is greater than 1 and new media uploaded count is only 1 now.
			} elseif ( 1 < count( $old_media_ids ) && 1 === count( $media_ids ) ) {
				$new_media_id = (int) $media_ids[0];

				// check if new media is in old media uploaded, if yes then delete that media's media activity first.
				if ( in_array( $new_media_id, $old_media_ids, true ) ) {
					$new_media         = new BP_Media( $new_media_id );
					$media_activity_id = $new_media->activity_id;

					// delete media's assigned media activity.
					remove_action( 'bp_activity_after_delete', 'bp_media_delete_activity_media' );
					bp_activity_delete( array( 'id' => $media_activity_id ) );
					add_action( 'bp_activity_after_delete', 'bp_media_delete_activity_media' );

					// save parent activity id in media.
					$new_media->activity_id = $bp_activity_post_update_id;
					$new_media->save();

					// save parent activity id in attachment meta.
					update_post_meta( $new_media->attachment_id, 'bp_media_parent_activity_id', $bp_activity_post_update_id );
				}

				// old media and new media count is same and old media and new media are different.
			} elseif ( 1 === count( $old_media_ids ) && 1 === count( $media_ids ) ) {
				$new_media_id = (int) $media_ids[0];

				// check if new media is not in old media uploaded and.
				if ( ! in_array( $new_media_id, $old_media_ids, true ) ) {
					$old_media_id = $old_media_ids[0];
					$old_media    = new BP_Media( $old_media_id );

					// unset the activity id for old media and save it to save activity from deleting after.
					if ( ! empty( $old_media->id ) ) {
						$old_media->activity_id = false;
						$old_media->save();

						// delete attachment activity id meta.
						delete_post_meta( $old_media->attachment_id, 'bp_media_parent_activity_id' );
					}
				}
			}
		}
	}

	return $media_ids;
}

/**
 * Generate permalink for comment mention notification.
 *
 * @since BuddyBoss 1.2.5
 *
 * @param $link
 * @param $item_id
 * @param $secondary_item_id
 *
 * @return string
 */
function bp_activity_new_at_mention_permalink( $link, $item_id, $secondary_item_id ) {

	$activity_obj = new BP_Activity_Activity( $item_id );

	if ( 'activity_comment' === $activity_obj->type ) {

		$component_action = 'new_at_mention';
		$component_name   = 'activity';

		if ( ! bb_enabled_legacy_email_preference() ) {
			$component_action = 'bb_new_mention';
		}

		$notification = BP_Notifications_Notification::get(
			array(
				'user_id'           => bp_loggedin_user_id(),
				'item_id'           => $item_id,
				'secondary_item_id' => $secondary_item_id,
				'component_name'    => $component_name,
				'component_action'  => $component_action,
			)
		);

		if ( ! empty( $notification ) ) {
			$id   = current( $notification )->id;
			$link = add_query_arg( 'crid', (int) $id, bp_activity_get_permalink( $activity_obj->id ) );
		}
	}

	return $link;
}

/**
 * Create document activity for each document uploaded
 *
 * @param $document
 *
 * @since BuddyBoss 1.2.0
 */
function bp_activity_document_add( $document ) {
	global $bp_document_upload_count, $bp_new_activity_comment, $bp_activity_post_update_id, $bp_activity_post_update;

	if ( ! empty( $document ) && empty( $document->activity_id ) ) {
		$parent_activity_id = false;
		if ( ! empty( $bp_activity_post_update ) && ! empty( $bp_activity_post_update_id ) ) {
			$parent_activity_id = (int) $bp_activity_post_update_id;
		}

		if ( $bp_document_upload_count > 1 || ! empty( $bp_new_activity_comment ) ) {

			if ( bp_is_active( 'groups' ) && ! empty( $bp_new_activity_comment ) && empty( $document->group_id ) ) {
				$comment = new BP_Activity_Activity( $bp_new_activity_comment );

				if ( ! empty( $comment->item_id ) ) {
					$comment_activity = new BP_Activity_Activity( $comment->item_id );
					if ( ! empty( $comment_activity->component ) && buddypress()->groups->id === $comment_activity->component ) {
						$document->group_id = $comment_activity->item_id;
						$document->privacy  = 'comment';
						$document->album_id = 0;
					}
				}
			}

			// Check when new activity coment is empty then set privacy comment - 2121.
			if ( ! empty( $bp_new_activity_comment ) ) {
				$activity_id        = $bp_new_activity_comment;
				$document->privacy  = 'comment';
				$document->album_id = 0;
			} else {

				$args = array(
					'hide_sitewide' => true,
					'privacy'       => 'document',
				);

				// Create activity only if not created previously.
				if ( ! empty( $document->group_id ) && bp_is_active( 'groups' ) ) {
					$args['item_id'] = $document->group_id;
					$args['type']    = 'activity_update';
					$current_group   = groups_get_group( $document->group_id );
					$args['action']  = sprintf(
					/* translators: 1. User Link. 2. Group link. */
						__( '%1$s posted an update in the group %2$s', 'buddyboss' ),
						bp_core_get_userlink( $document->user_id ),
						'<a href="' . bp_get_group_permalink( $current_group ) . '">' . bp_get_group_name( $current_group ) . '</a>'
					);
					$activity_id = groups_record_activity( $args );
				} else {
					$activity_id = bp_activity_post_update( $args );
				}
			}

			if ( $activity_id ) {

				// save document activity id in document.
				$document->activity_id = $activity_id;
				$document->save();

				// update activity meta.
				bp_activity_update_meta( $activity_id, 'bp_document_activity', '1' );
				bp_activity_update_meta( $activity_id, 'bp_document_id', $document->id );

				// save attachment meta for activity.
				update_post_meta( $document->attachment_id, 'bp_document_activity_id', $activity_id );

				if ( $parent_activity_id ) {

					// If new activity comment is empty - 2121.
					if ( empty( $bp_new_activity_comment ) ) {
						$document_activity                    = new BP_Activity_Activity( $activity_id );
						$document_activity->secondary_item_id = $parent_activity_id;
						$document_activity->save();
					}

					// save parent activity id in attachment meta.
					update_post_meta( $document->attachment_id, 'bp_document_parent_activity_id', $parent_activity_id );
				}
			}
		} else {

			if ( $parent_activity_id ) {

				// If the document posted in activity comment then set the activity id to comment id.- 2121.
				if ( ! empty( $bp_new_activity_comment ) ) {
					$parent_activity_id = $bp_new_activity_comment;
					$document->privacy  = 'comment';
				}

				// save document activity id in document.
				$document->activity_id = $parent_activity_id;
				$document->save();

				// save parent activity id in attachment meta.
				update_post_meta( $document->attachment_id, 'bp_document_parent_activity_id', $parent_activity_id );
			}
		}
	}
}

/**
 * Create main activity for the media uploaded and saved.
 *
 * @param $document_ids
 *
 * @return mixed
 * @since BuddyBoss 1.2.0
 */
function bp_activity_create_parent_document_activity( $document_ids ) {
	global $bp_document_upload_count, $bp_activity_post_update, $bp_document_upload_activity_content, $bp_activity_post_update_id, $bp_activity_edit;

	if ( ! empty( $document_ids ) && empty( $bp_activity_post_update ) && ! isset( $_POST['edit'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing

		$added_document_ids = $document_ids;
		$content            = false;

		if ( ! empty( $bp_document_upload_activity_content ) ) {

			/**
			 * Filters the content provided in the activity input field.
			 *
			 * @param string $value Activity message being posted.
			 *
			 * @since BuddyPress 1.2.0
			 */
			$content = apply_filters( 'bp_activity_post_update_content', $bp_document_upload_activity_content );
		}

		$group_id  = FILTER_INPUT( INPUT_POST, 'group_id', FILTER_SANITIZE_NUMBER_INT );
		$folder_id = false;

		// added fall back to get group_id from document.
		if ( empty( $group_id ) && ! empty( $added_document_ids ) ) {
			$document_object = new BP_Document( current( (array) $added_document_ids ) );
			if ( ! empty( $document_object->group_id ) ) {
				$group_id = $document_object->group_id;
			}
		}

		if ( bp_is_active( 'groups' ) && ! empty( $group_id ) && $group_id > 0 ) {
			remove_action( 'bp_groups_posted_update', 'bb_subscription_send_subscribe_group_notifications', 10, 4 );
			$activity_id = groups_post_update(
				array(
					'content'  => $content,
					'group_id' => $group_id,
				)
			);
			add_action( 'bp_groups_posted_update', 'bb_subscription_send_subscribe_group_notifications', 10, 4 );
		} else {
			remove_action( 'bp_activity_posted_update', 'bb_activity_send_email_to_following_post', 10, 3 );
			$activity_id = bp_activity_post_update( array( 'content' => $content ) );
			add_action( 'bp_activity_posted_update', 'bb_activity_send_email_to_following_post', 10, 3 );
		}

		// save document meta for activity.
		if ( ! empty( $activity_id ) ) {
			$privacy = 'public';

			foreach ( (array) $added_document_ids as $document_id ) {
				$document = new BP_Document( $document_id );

				// get one of the media's privacy for the activity privacy.
				$privacy = $document->privacy;

				// get document folder id.
				if ( ! empty( $document->folder_id ) ) {
					$folder_id = $document->folder_id;
				}

				if ( 1 === $bp_document_upload_count ) {
					// save media activity id in media.
					$document->activity_id = $activity_id;
					$document->group_id    = $group_id;
					$document->save();
				}

				// save parent activity id in attachment meta.
				update_post_meta( $document->attachment_id, 'bp_document_parent_activity_id', $activity_id );
			}

			bp_activity_update_meta( $activity_id, 'bp_document_ids', implode( ',', $added_document_ids ) );

			// if document is from folder then save folder id in activity meta.
			if ( ! empty( $folder_id ) ) {
				bp_activity_update_meta( $activity_id, 'bp_document_folder_activity', $folder_id );
			}

			$main_activity = new BP_Activity_Activity( $activity_id );
			if ( empty( $group_id ) ) {
				if ( ! empty( $main_activity ) ) {
					$main_activity->privacy = $privacy;
					$main_activity->save();
				}
			}

			if ( bp_is_active( 'document' ) && ! empty( $main_activity->id ) && count( $document_ids ) > 1 ) {
				foreach ( $document_ids as $id ) {
					$document = new BP_Document( $id );
					if ( ! empty( $document->activity_id ) ) {
						$document_activity = new BP_Activity_Activity( $document->activity_id );
						if ( ! empty( $document_activity->id ) ) {
							$document_activity->secondary_item_id = $main_activity->id;
							$document_activity->save();
						}
					}
				}
			}

			if ( ! empty( $main_activity->id ) ) {
				do_action( 'bb_document_after_create_parent_activity', $main_activity->content, $main_activity->user_id, $main_activity->id );
			}
		}
	}

	return $document_ids;
}

/**
 * Update document and activity for document updation and deletion while editing the activity.
 *
 * @param $document_ids
 *
 * @return mixed
 * @since BuddyBoss 1.5.0
 */
function bp_activity_edit_update_document( $document_ids ) {
	global $bp_activity_edit, $bp_activity_post_update_id;

	if ( ( true === $bp_activity_edit || isset( $_POST['edit'] ) ) && ! empty( $bp_activity_post_update_id ) ) {
		$old_document_ids = bp_activity_get_meta( $bp_activity_post_update_id, 'bp_document_ids', true );
		$old_document_ids = explode( ',', $old_document_ids );

		if ( ! empty( $old_document_ids ) ) {
			$old_document_ids = wp_parse_id_list( $old_document_ids );
			$document_ids     = wp_parse_id_list( $document_ids );

			// old document count 1 and new document uploaded count is greater than 1.
			if ( 1 === count( $old_document_ids ) && 1 < count( $document_ids ) ) {
				$old_document_id = (int) $old_document_ids[0];

				// check if old document id is in new document uploaded.
				if ( in_array( $old_document_id, $document_ids, true ) ) {

					// Create new document activity for old document because it has only parent activity to show right now.
					$old_document = new BP_Document( $old_document_id );
					$args         = array(
						'hide_sitewide' => true,
						'privacy'       => 'document',
					);

					if ( ! empty( $old_document->group_id ) && bp_is_active( 'groups' ) ) {
						$args['item_id'] = $old_document->group_id;
						$args['type']    = 'activity_update';
						$current_group   = groups_get_group( $old_document->group_id );
						$args['action']  = sprintf( __( '%1$s posted an update in the group %2$s', 'buddyboss' ), bp_core_get_userlink( $old_document->user_id ), '<a href="' . bp_get_group_permalink( $current_group ) . '">' . bp_get_group_name( $current_group ) . '</a>' );
						$activity_id     = groups_record_activity( $args );
					} else {
						$activity_id = bp_activity_post_update( $args );
					}

					// document activity for old document is created and it is being assigned to the old document.
					// And document activity is being saved with needed data to figure out everything for it.
					if ( $activity_id ) {
						$old_document->activity_id = $activity_id;
						$old_document->save();

						$document_activity                    = new BP_Activity_Activity( $activity_id );
						$document_activity->secondary_item_id = $bp_activity_post_update_id;
						$document_activity->save();

						// update activity meta to tell it is document activity.
						bp_activity_update_meta( $activity_id, 'bp_document_activity', '1' );
						bp_activity_update_meta( $activity_id, 'bp_document_id', $old_document->id );

						// save attachment meta for activity.
						update_post_meta( $old_document->attachment_id, 'bp_document_activity_id', $activity_id );

						// save parent activity id in attachment meta.
						update_post_meta( $old_document->attachment_id, 'bp_document_parent_activity_id', $bp_activity_post_update_id );
					}
				}

				// old document count is greater than 1 and new document uploaded count is only 1 now.
			} elseif ( 1 < count( $old_document_ids ) && 1 === count( $document_ids ) ) {
				$new_document_id = (int) $document_ids[0];

				// check if new document is in old document uploaded, if yes then delete that document's document activity first.
				if ( in_array( $new_document_id, $old_document_ids, true ) ) {
					$new_document         = new BP_Document( $new_document_id );
					$document_activity_id = $new_document->activity_id;

					// delete document's assigned document activity.
					remove_action( 'bp_activity_after_delete', 'bp_document_delete_activity_document' );
					bp_activity_delete( array( 'id' => $document_activity_id ) );
					add_action( 'bp_activity_after_delete', 'bp_document_delete_activity_document' );

					// save parent activity id in document.
					$new_document->activity_id = $bp_activity_post_update_id;
					$new_document->save();

					// save parent activity id in attachment meta.
					update_post_meta( $new_document->attachment_id, 'bp_document_parent_activity_id', $bp_activity_post_update_id );
				}

				// old document and new document count is same and old document and new document are different.
			} elseif ( 1 === count( $old_document_ids ) && 1 === count( $document_ids ) ) {
				$new_document_id = (int) $document_ids[0];

				// check if new document is not in old document uploaded and.
				if ( ! in_array( $new_document_id, $old_document_ids, true ) ) {
					$old_document_id = $old_document_ids[0];
					$old_document    = new BP_Document( $old_document_id );

					// unset the activity id for old document and save it to save activity from deleting after.
					if ( ! empty( $old_document->id ) ) {
						$old_document->activity_id = false;
						$old_document->save();

						// delete attachment activity id meta.
						delete_post_meta( $old_document->attachment_id, 'bp_document_parent_activity_id' );
					}
				}
			}
		}
	}

	return $document_ids;
}

/**
 * We removing the Edit Button on Document/Media/Video Activity popup until we fully support on popup.
 *
 * @param array $buttons     Buttons Argument.
 * @param int   $activity_id Activity ID.
 *
 * @return mixed
 * @since BuddyBoss 1.7.2
 */
function bp_nouveau_remove_edit_activity_entry_buttons( $buttons, $activity_id ) {

	$exclude_action_arr = array( 'media_get_media_description', 'media_get_activity', 'document_get_document_description', 'document_get_activity', 'video_get_video_description', 'video_get_activity' );

	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( bp_is_activity_edit_enabled() && isset( $_REQUEST ) && isset( $_REQUEST['action'] ) && in_array( $_REQUEST['action'], $exclude_action_arr, true ) ) {
		$activity = new BP_Activity_Activity( $activity_id );
		if ( in_array( $activity->privacy, array( 'document', 'media', 'video' ), true ) ) {
			unset( $buttons['activity_edit'] );
		}
	}

	return $buttons;

}

add_action( 'bp_before_activity_activity_content', 'bp_blogs_activity_content_set_temp_content' );

/**
 * Function which set the temporary content on the blog post activity.
 *
 * @since BuddyBoss 1.5.5
 */
function bp_blogs_activity_content_set_temp_content() {

	global $activities_template;

	$activity = $activities_template->activity;
	if ( ( 'blogs' === $activity->component ) && isset( $activity->secondary_item_id ) && 'new_blog_' . get_post_type( $activity->secondary_item_id ) === $activity->type ) {
		$content = get_post( $activity->secondary_item_id );
		// If we converted $content to an object earlier, flip it back to a string.
		if ( is_a( $content, 'WP_Post' ) ) {
			$activities_template->activity->content = '&#8203;';
		}
	} elseif ( 'blogs' === $activity->component && 'new_blog_comment' === $activity->type && $activity->secondary_item_id && $activity->secondary_item_id > 0 ) {
		$activities_template->activity->content = '&#8203;';
	}

}

add_filter( 'bp_get_activity_content_body', 'bp_blogs_activity_content_with_read_more', 9999, 2 );

/**
 * Function which set the content on activity blog post.
 *
 * @param $content
 * @param $activity
 *
 * @return string
 *
 * @since BuddyBoss 1.5.5
 */
function bp_blogs_activity_content_with_read_more( $content, $activity ) {

	if ( ( 'blogs' === $activity->component ) && isset( $activity->secondary_item_id ) && 'new_blog_' . get_post_type( $activity->secondary_item_id ) === $activity->type ) {
		$blog_post = get_post( $activity->secondary_item_id );
		// If we converted $content to an object earlier, flip it back to a string.
		if ( is_a( $blog_post, 'WP_Post' ) ) {
			$content_img = apply_filters( 'bb_add_feature_image_blog_post_as_activity_content', '', $blog_post->ID );
			$post_title  = sprintf( '<a class="bb-post-title-link" href="%s"><span class="bb-post-title">%s</span></a>', esc_url( get_permalink( $blog_post->ID ) ), esc_html( $blog_post->post_title ) );
			$content     = bp_create_excerpt( bp_strip_script_and_style_tags( html_entity_decode( get_the_excerpt( $blog_post->ID ), ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401 ) ) );

			if ( empty( $content ) ) {
				$content = bp_create_excerpt( bp_strip_script_and_style_tags( html_entity_decode( $blog_post->post_content, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401 ) ) );
			}

			if ( false !== strrpos( $content, __( '&hellip;', 'buddyboss' ) ) ) {
				$content = str_replace( ' [&hellip;]', '&hellip;', $content );
				$content = apply_filters_ref_array( 'bp_get_activity_content', array( $content, $activity ) );
				preg_match( '/<iframe.*src=\"(.*)\".*><\/iframe>/isU', $content, $matches );
				if ( isset( $matches ) && array_key_exists( 0, $matches ) && ! empty( $matches[0] ) ) {
					$iframe  = $matches[0];
					$content = strip_tags( preg_replace( '/<iframe.*?\/iframe>/i', '', $content ), '<a>' );

					$content .= $iframe;
				}
				$content = sprintf( '%1$s <div class="bb-content-wrp">%2$s %3$s</div>', $content_img, $post_title, wpautop( $content ) );
			} else {
				$content = apply_filters_ref_array( 'bp_get_activity_content', array( $content, $activity ) );
				$content = strip_tags( $content, '<a><iframe><img><span><div>' );
				preg_match( '/<iframe.*src=\"(.*)\".*><\/iframe>/isU', $content, $matches );
				if ( isset( $matches ) && array_key_exists( 0, $matches ) && ! empty( $matches[0] ) ) {
					$content = $content;
				}
				$content = sprintf( '%1$s <div class="bb-content-wrp">%2$s %3$s</div>', $content_img, $post_title, wpautop( $content ) );
			}
		}
	} elseif ( 'blogs' === $activity->component && 'new_blog_comment' === $activity->type && $activity->secondary_item_id && $activity->secondary_item_id > 0 ) {
		$comment = get_comment( $activity->secondary_item_id );
		$content = bp_create_excerpt( html_entity_decode( $comment->comment_content ) );
		if ( false !== strrpos( $content, __( '&hellip;', 'buddyboss' ) ) ) {
			$content     = str_replace( ' [&hellip;]', '&hellip;', $content );
			$append_text = apply_filters( 'bp_activity_excerpt_append_text', __( ' Read more', 'buddyboss' ) );
			$content     = wpautop( sprintf( '%1$s<span class="activity-blog-post-link"><a href="%2$s" rel="nofollow">%3$s</a></span>', $content, get_comment_link( $activity->secondary_item_id ), $append_text ) );
		}
	}

	return $content;
}

add_filter( 'bp_get_activity_content', 'bp_blogs_activity_comment_content_with_read_more', 9999, 2 );

/**
 * Function which set the content on activity blog post comment.
 *
 * @param string               $content  Activity Content.
 * @param BP_Activity_Activity $activity Activity Object.
 *
 * @return string
 * @since BuddyBoss 1.5.5
 */
function bp_blogs_activity_comment_content_with_read_more( $content, $activity ) {

	if ( 'activity_comment' === $activity->type && $activity->item_id && $activity->item_id > 0 ) {
		// Get activity object.
		$comment_activity = new BP_Activity_Activity( $activity->item_id );
		if ( 'blogs' === $comment_activity->component && isset( $comment_activity->secondary_item_id ) && 'new_blog_' . get_post_type( $comment_activity->secondary_item_id ) === $comment_activity->type ) {
			$comment_post_type = $comment_activity->secondary_item_id;
			$get_post_type     = get_post_type( $comment_post_type );
			$comment_id        = bp_activity_get_meta( $activity->id, 'bp_blogs_' . $get_post_type . '_comment_id', true );
			if ( $comment_id ) {
				$comment = get_comment( $comment_id );
				if ( apply_filters( 'bp_blogs_activity_comment_content_with_read_more', true ) ) {
					$content = bp_create_excerpt( make_clickable( html_entity_decode( $comment->comment_content, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401 ) ) );
					if ( false !== strrpos( $content, __( '&hellip;', 'buddyboss' ) ) ) {
						$content     = str_replace( ' [&hellip;]', '&hellip;', $content );
						$append_text = apply_filters( 'bp_activity_excerpt_append_text', __( ' Read more', 'buddyboss' ) );
						$content     = sprintf( '%1$s<span class="activity-blog-post-link"><a href="%2$s" rel="nofollow">%3$s</a></span>', $content, get_comment_link( $comment_id ), $append_text );
					}
				} else {
					$content = make_clickable( html_entity_decode( $comment->comment_content, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401 ) );
				}
			}
		}
	}

	return $content;
}

/**
 * Describe activity commnet asscess rules.
 *
 * @since BuddyBoss 1.7.2
 *
 * @param boolean $retval Has comment permission.
 *
 * @return boolean
 */
function bb_activity_has_comment_access( $retval ) {
	global $activities_template;

	// Check blog post activity comment status.
	if ( bb_activity_blog_post_acivity( $activities_template->activity ) ) {
		return ! function_exists( 'bp_blogs_disable_activity_commenting' ) ? false : bp_blogs_disable_activity_commenting( $retval );
	}

	// Get the current action name.
	$action_name = $activities_template->activity->type;

	// Setup the array of possibly disabled actions.
	$disabled_actions = array(
		'bbp_topic_create',
		'bbp_reply_create',
	);

	// Comment is disabled for discussion and reply discussion.
	if ( in_array( $action_name, $disabled_actions, true ) ) {
		$retval = false;
	}

	return $retval;
}

/**
 * Disable the comment reply for discussion activity.
 *
 * @since BuddyBoss 1.7.2
 *
 * @param boolean $can_comment Comment permission status.
 * @param object  $comment     Activity data.
 *
 * @return boolean
 */
function bb_activity_has_comment_reply_access( $can_comment, $comment ) {
	if ( empty( $comment ) ) {
		return $can_comment;
	}

	// Get the current action name.
	$action_name = $comment->type;

	// Setup the array of possibly disabled actions.
	$comment_actions = array(
		'activity_comment',
	);

	// Comment is disabled for discussion and reply discussion.
	if ( in_array( $action_name, $comment_actions, true ) && bb_acivity_is_topic_comment( $comment->item_id ) ) {
		$can_comment = false;
	}

	return $can_comment;
}

/**
 * Remove comment reply button for discussion.
 *
 * @since BuddyBoss 1.7.2
 *
 * @param array $buttons             The list of buttons.
 * @param int   $activity_comment_id The current activity comment ID.
 * @param int   $activity_id         The current activity ID.
 *
 * @return boolean
 */
function bb_remove_discussion_comment_reply_button( $buttons, $activity_comment_id, $activity_id ) {
	if ( empty( $activity_id ) ) {
		return $buttons;
	}

	$activity = new BP_Activity_Activity( $activity_id );

	if ( empty( $activity->id ) ) {
		return $buttons;
	}

	// Get the current action name.
	$action_name = $activity->type;

	// Setup the array of possibly disabled actions.
	$disabled_actions = array(
		'bbp_topic_create',
		'bbp_reply_create',
	);

	// Comment is disabled for discussion and reply discussion.
	if ( ! empty( $buttons['activity_comment_reply'] ) && in_array( $action_name, $disabled_actions, true ) ) {
		$buttons['activity_comment_reply'] = '';
	}

	return $buttons;
}

/**
 * Function will check content empty or not for the media, document and gif.
 * If content will empty then return true and allow empty content in DB for the media, document and gif.
 *
 * @param array $data Get post data for the comments.
 *
 * @return bool
 */
function bb_check_is_activity_content_empty( $data ) {
	if ( empty( trim( wp_strip_all_tags( $data['content'] ) ) ) && ( isset( $data['gif_data'] ) || isset( $data['media'] ) || isset( $data['document'] ) ) ) {
		return true;
	} elseif ( empty( trim( wp_strip_all_tags( $data['content'] ) ) ) && ( isset( $data['media_gif'] ) || isset( $data['bp_media_ids'] ) || isset( $data['bp_documents'] ) ) ) {
		return true;
	} elseif ( ! empty( trim( wp_strip_all_tags( $data['content'] ) ) ) ) {
		return true;
	} else {
		return false;
	}
}

/**
 * Function will add feature image for blog post in the activity feed content.
 *
 * @param string $content
 * @param int    $blog_post_id
 *
 * @return string $content
 *
 * @since 1.6.4
 */
function bb_add_feature_image_blog_post_as_activity_content_callback( $content, $blog_post_id ) {
	if ( ! empty( $blog_post_id ) && ! empty( get_post_thumbnail_id( $blog_post_id ) ) ) {
		$content .= sprintf( ' <a class="bb-post-img-link" href="%s"><img src="%s" /></a>', esc_url( get_permalink( $blog_post_id ) ), esc_url( wp_get_attachment_image_url( get_post_thumbnail_id( $blog_post_id ), 'full' ) ) );
	}

	return $content;
}
add_filter( 'bb_add_feature_image_blog_post_as_activity_content', 'bb_add_feature_image_blog_post_as_activity_content_callback', 10, 2 );

/**
 * Create video activity for each video uploaded
 *
 * @since BuddyBoss 1.7.0
 *
 * @param object $video Video object.
 */
function bp_activity_video_add( $video ) {
	global $bp_video_upload_count, $bp_new_activity_comment, $bp_activity_post_update_id, $bp_activity_post_update;

	if ( ! empty( $video ) && empty( $video->activity_id ) ) {
		$parent_activity_id = false;
		if ( ! empty( $bp_activity_post_update ) && ! empty( $bp_activity_post_update_id ) ) {
			$parent_activity_id = (int) $bp_activity_post_update_id;
		}

		if ( $bp_video_upload_count > 1 || ! empty( $bp_new_activity_comment ) ) {

			if ( bp_is_active( 'groups' ) && ! empty( $bp_new_activity_comment ) && empty( $video->group_id ) ) {
				$comment = new BP_Activity_Activity( $bp_new_activity_comment );

				if ( ! empty( $comment->item_id ) ) {
					$comment_activity = new BP_Activity_Activity( $comment->item_id );
					if ( ! empty( $comment_activity->component ) && buddypress()->groups->id === $comment_activity->component ) {
						$video->group_id = $comment_activity->item_id;
						$video->privacy  = 'comment';
						$video->album_id = 0;
					}
				}
			}

			// Check when new activity coment is empty then set privacy comment - 2121.
			if ( ! empty( $bp_new_activity_comment ) ) {
				$activity_id     = $bp_new_activity_comment;
				$video->privacy  = 'comment';
				$video->album_id = 0;
			} else {

				$args = array(
					'hide_sitewide' => true,
					'privacy'       => 'video',
				);

				// Create activity only if not created previously.
				if ( ! empty( $video->group_id ) && bp_is_active( 'groups' ) ) {
					$args['item_id'] = $video->group_id;
					$args['type']    = 'activity_update';
					$current_group   = groups_get_group( $video->group_id );
					$args['action']  = sprintf(
						/* translators: 1. User Link. 2. Group link. */
						__( '%1$s posted an update in the group %2$s', 'buddyboss' ),
						bp_core_get_userlink( $video->user_id ),
						'<a href="' . bp_get_group_permalink( $current_group ) . '">' . bp_get_group_name( $current_group ) . '</a>'
					);
					$activity_id = groups_record_activity( $args );
				} else {
					$activity_id = bp_activity_post_update( $args );
				}
			}

			if ( $activity_id ) {

				// save video activity id in video.
				$video->activity_id = $activity_id;
				$video->save();

				// update activity meta.
				bp_activity_update_meta( $activity_id, 'bp_video_activity', '1' );
				bp_activity_update_meta( $activity_id, 'bp_video_id', $video->id );

				// save attachment meta for activity.
				update_post_meta( $video->attachment_id, 'bp_video_activity_id', $activity_id );

				if ( $parent_activity_id ) {

					// If new activity comment is empty - 2121.
					if ( empty( $bp_new_activity_comment ) ) {
						$video_activity                    = new BP_Activity_Activity( $activity_id );
						$video_activity->secondary_item_id = $parent_activity_id;
						$video_activity->save();
					}

					// save parent activity id in attachment meta.
					update_post_meta( $video->attachment_id, 'bp_video_parent_activity_id', $parent_activity_id );
				}
			}
		} else {

			if ( $parent_activity_id ) {

				// If the video posted in activity comment then set the activity id to comment id.- 2121.
				if ( ! empty( $bp_new_activity_comment ) ) {
					$parent_activity_id = $bp_new_activity_comment;
					$video->privacy     = 'comment';
				}

				// save video activity id in video.
				$video->activity_id = $parent_activity_id;
				$video->save();

				// save parent activity id in attachment meta.
				update_post_meta( $video->attachment_id, 'bp_video_parent_activity_id', $parent_activity_id );
			}
		}
	}
}

/**
 * Create main activity for the video uploaded and saved.
 *
 * @since BuddyBoss 1.7.0
 *
 * @param array $video_ids Video ids.
 *
 * @return mixed
 */
function bp_activity_create_parent_video_activity( $video_ids ) {
	global $bp_video_upload_count, $bp_activity_post_update, $bp_video_upload_activity_content, $bp_activity_post_update_id, $bp_activity_edit;

	if ( ! empty( $video_ids ) && empty( $bp_activity_post_update ) && ! isset( $_POST['edit'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing

		$added_video_ids = $video_ids;
		$content         = false;

		if ( ! empty( $bp_video_upload_activity_content ) ) {

			/**
			 * Filters the content provided in the activity input field.
			 *
			 * @param string $bp_video_upload_activity_content Activity message being posted.
			 *
			 * @since BuddyPress 1.2.0
			 */
			$content = apply_filters( 'bp_activity_post_update_content', $bp_video_upload_activity_content );
		}

		$group_id = FILTER_INPUT( INPUT_POST, 'group_id', FILTER_SANITIZE_NUMBER_INT );
		$album_id = false;

		// added fall back to get group_id from video.
		if ( empty( $group_id ) && ! empty( $added_video_ids ) ) {
			$video_object = new BP_Video( current( (array) $added_video_ids ) );
			if ( ! empty( $video_object->group_id ) ) {
				$group_id = $video_object->group_id;
			}
		}

		if ( bp_is_active( 'groups' ) && ! empty( $group_id ) && $group_id > 0 ) {
			remove_action( 'bp_groups_posted_update', 'bb_subscription_send_subscribe_group_notifications', 10, 4 );
			$activity_id = groups_post_update(
				array(
					'content'  => $content,
					'group_id' => $group_id,
				)
			);
			add_action( 'bp_groups_posted_update', 'bb_subscription_send_subscribe_group_notifications', 10, 4 );
		} else {
			remove_action( 'bp_activity_posted_update', 'bb_activity_send_email_to_following_post', 10, 3 );
			$activity_id = bp_activity_post_update( array( 'content' => $content ) );
			add_action( 'bp_activity_posted_update', 'bb_activity_send_email_to_following_post', 10, 3 );
		}

		// save video meta for activity.
		if ( ! empty( $activity_id ) ) {
			$privacy = 'public';

			foreach ( (array) $added_video_ids as $video_id ) {
				$video = new BP_Video( $video_id );

				// get one of the video's privacy for the activity privacy.
				$privacy = $video->privacy;

				// get video album id.
				if ( ! empty( $video->album_id ) ) {
					$album_id = $video->album_id;
				}

				if ( 1 === $bp_video_upload_count ) {
					// save video activity id in video.
					$video->activity_id = $activity_id;
					$video->save();
				}

				// save parent activity id in attachment meta.
				update_post_meta( $video->attachment_id, 'bp_video_parent_activity_id', $activity_id );
			}

			bp_activity_update_meta( $activity_id, 'bp_video_ids', implode( ',', $added_video_ids ) );

			// if video is from album then save album id in activity video.
			if ( ! empty( $album_id ) ) {
				bp_activity_update_meta( $activity_id, 'bp_video_album_activity', $album_id );
			}

			$main_activity = new BP_Activity_Activity( $activity_id );
			if ( empty( $group_id ) ) {
				if ( ! empty( $main_activity ) ) {
					$main_activity->privacy = $privacy;
					$main_activity->save();
				}
			}

			if ( bp_is_active( 'video' ) && ! empty( $main_activity->id ) && count( $video_ids ) > 1 ) {
				foreach ( $video_ids as $id ) {
					$video = new BP_Video( $id );
					if ( ! empty( $video->activity_id ) ) {
						$video_activity = new BP_Activity_Activity( $video->activity_id );
						if ( ! empty( $video_activity->id ) ) {
							$video_activity->secondary_item_id = $main_activity->id;
							$video_activity->save();
						}
					}
				}
			}

			if ( ! empty( $main_activity->id ) ) {
				do_action( 'bb_video_after_create_parent_activity', $main_activity->content, $main_activity->user_id, $main_activity->id );
			}
		}
	}

	return $video_ids;
}

/**
 * Update video and activity for video updation and deletion while editing the activity.
 *
 * @param array $video_ids Video ids.
 *
 * @return mixed
 * @since BuddyBoss 1.7.0
 */
function bp_activity_edit_update_video( $video_ids ) {
	global $bp_activity_edit, $bp_activity_post_update_id;

	// phpcs:ignore WordPress.Security.NonceVerification.Missing
	if ( ( true === $bp_activity_edit || isset( $_POST['edit'] ) ) && ! empty( $bp_activity_post_update_id ) ) {
		$old_video_ids = bp_activity_get_meta( $bp_activity_post_update_id, 'bp_video_ids', true );
		$old_video_ids = explode( ',', $old_video_ids );

		if ( ! empty( $old_video_ids ) ) {
			$old_video_ids = wp_parse_id_list( $old_video_ids );
			$video_ids     = wp_parse_id_list( $video_ids );

			// old video count 1 and new video uploaded count is greater than 1.
			if ( 1 === count( $old_video_ids ) && 1 < count( $video_ids ) ) {
				$old_video_id = (int) $old_video_ids[0];

				// check if old video id is in new video uploaded.
				if ( in_array( $old_video_id, $video_ids, true ) ) {

					// Create new video activity for old video because it has only parent activity to show right now.
					$old_video = new BP_Video( $old_video_id );
					$args      = array(
						'hide_sitewide' => true,
						'privacy'       => 'video',
					);

					if ( ! empty( $old_video->group_id ) && bp_is_active( 'groups' ) ) {
						$args['item_id'] = $old_video->group_id;
						$args['type']    = 'activity_update';
						$current_group   = groups_get_group( $old_video->group_id );
						$args['action']  = sprintf(
							/* translators: 1. User Link. 2. Group link. */
							__( '%1$s posted an update in the group %2$s', 'buddyboss' ),
							bp_core_get_userlink( $old_video->user_id ),
							'<a href="' . bp_get_group_permalink( $current_group ) . '">' . bp_get_group_name( $current_group ) . '</a>'
						);

						$activity_id = groups_record_activity( $args );
					} else {
						$activity_id = bp_activity_post_update( $args );
					}

					// video activity for old video is created and it is being assigned to the old video.
					// And video activity is being saved with needed data to figure out everything for it.
					if ( $activity_id ) {
						$old_video->activity_id = $activity_id;
						$old_video->save();

						$video_activity                    = new BP_Activity_Activity( $activity_id );
						$video_activity->secondary_item_id = $bp_activity_post_update_id;
						$video_activity->save();

						// update activity meta to tell it is video activity.
						bp_activity_update_meta( $activity_id, 'bp_video_activity', '1' );
						bp_activity_update_meta( $activity_id, 'bp_video_id', $old_video->id );

						// save attachment meta for activity.
						update_post_meta( $old_video->attachment_id, 'bp_video_activity_id', $activity_id );

						// save parent activity id in attachment meta.
						update_post_meta( $old_video->attachment_id, 'bp_video_parent_activity_id', $bp_activity_post_update_id );
					}
				}

				// old video count is greater than 1 and new video uploaded count is only 1 now.
			} elseif ( 1 < count( $old_video_ids ) && 1 === count( $video_ids ) ) {
				$new_video_id = (int) $video_ids[0];

				// check if new video is in old video uploaded, if yes then delete that video's video activity first.
				if ( in_array( $new_video_id, $old_video_ids, true ) ) {
					$new_video         = new BP_Video( $new_video_id );
					$video_activity_id = $new_video->activity_id;

					// delete video's assigned video activity.
					remove_action( 'bp_activity_after_delete', 'bp_video_delete_activity_video' );
					bp_activity_delete( array( 'id' => $video_activity_id ) );
					add_action( 'bp_activity_after_delete', 'bp_video_delete_activity_video' );

					// save parent activity id in video.
					$new_video->activity_id = $bp_activity_post_update_id;
					$new_video->save();

					// save parent activity id in attachment meta.
					update_post_meta( $new_video->attachment_id, 'bp_video_parent_activity_id', $bp_activity_post_update_id );
				}

				// old video and new video count is same and old video and new video are different.
			} elseif ( 1 === count( $old_video_ids ) && 1 === count( $video_ids ) ) {
				$new_video_id = (int) $video_ids[0];

				// check if new video is not in old video uploaded and.
				if ( ! in_array( $new_video_id, $old_video_ids, true ) ) {
					$old_video_id = $old_video_ids[0];
					$old_video    = new BP_Video( $old_video_id );

					// unset the activity id for old video and save it to save activity from deleting after.
					if ( ! empty( $old_video->id ) ) {
						$old_video->activity_id = false;
						$old_video->save();

						// delete attachment activity id meta.
						delete_post_meta( $old_video->attachment_id, 'bp_video_parent_activity_id' );
					}
				}
			}
		}
	}

	return $video_ids;
}

/**
 * Function will remove like and comment button for the media/document activity.
 *
 * @param array $buttons     Array of buttons.
 * @param int   $activity_id Activity ID.
 *
 * @return mixed
 *
 * @since BuddyBoss 1.7.8
 */
function bb_nouveau_get_activity_entry_buttons_callback( $buttons, $activity_id ) {
	$buttons['activity_favorite']            = '';
	$buttons['activity_conversation']        = '';
	$buttons['activity_report']              = '';
	$buttons['activity_comment_reply']       = '';
	$buttons['activity_edit']                = '';
	$buttons['activity_delete']              = '';
	$buttons['activity_state_comment_class'] = 'activity-state-no-comments';
	return $buttons;
}

/**
 * Action to delete link preview attachment.
 *
 * @param array $activities Array of activities.
 *
 * @since 1.7.6
 */
function bb_activity_delete_link_review_attachment( $activities ) {
	$activity_ids = wp_parse_id_list( wp_list_pluck( $activities, 'id' ) );

	if ( ! empty( $activity_ids ) ) {
		foreach ( $activity_ids as $activity_id ) {
			$link_preview_meta = bp_activity_get_meta( $activity_id, '_link_preview_data', true );
			if ( ! empty( $link_preview_meta ) && ! empty( $link_preview_meta['attachment_id'] ) ) {
				wp_delete_attachment( $link_preview_meta['attachment_id'], true );
			}
		}
	}
}

/**
 * Register the activity notifications.
 *
 * @since BuddyBoss 1.9.3
 */
function bb_load_activity_notifications() {
	if ( class_exists( 'BP_Activity_Notification' ) ) {
		BP_Activity_Notification::instance();
	}
}

/**
 * Add activity notifications settings to the notifications settings page.
 *
 * @since BuddyPress 1.2.0
 */
function bp_activity_screen_notification_settings() {

	// Bail out if legacy method not enabled.
	if ( false === bb_enabled_legacy_email_preference() ) {
		return;
	}

	if ( bp_activity_do_mentions() ) {
		if ( ! $mention = bp_get_user_meta( bp_displayed_user_id(), 'notification_activity_new_mention', true ) ) {
			$mention = 'yes';
		}
	}

	if ( ! $reply = bp_get_user_meta( bp_displayed_user_id(), 'notification_activity_new_reply', true ) ) {
		$reply = 'yes';
	}

	?>

	<table class="notification-settings" id="activity-notification-settings">
		<thead>
		<tr>
			<th class="icon">&nbsp;</th>
			<th class="title"><?php esc_html_e( 'Activity Feed', 'buddyboss' ); ?></th>
			<th class="yes"><?php esc_html_e( 'Yes', 'buddyboss' ); ?></th>
			<th class="no"><?php esc_html_e( 'No', 'buddyboss' ); ?></th>
		</tr>
		</thead>

		<tbody>
		<?php
		if ( bp_activity_do_mentions() ) :
			$current_user = wp_get_current_user();
			?>
			<tr id="activity-notification-settings-mentions">
				<td>&nbsp;</td>
				<td><?php printf( esc_html__( 'A member mentions you in an update using "@%s"', 'buddyboss' ), bp_activity_get_user_mentionname( $current_user->ID ) ); ?></td>
				<td class="yes">
					<div class="bp-radio-wrap">
						<input type="radio" name="notifications[notification_activity_new_mention]" id="notification-activity-new-mention-yes" class="bs-styled-radio"
							   value="yes" <?php checked( $mention, 'yes', true ); ?> />
						<label for="notification-activity-new-mention-yes"><span class="bp-screen-reader-text"><?php esc_html_e( 'Yes, send email', 'buddyboss' ); ?></span></label>
					</div>
				</td>
				<td class="no">
					<div class="bp-radio-wrap">
						<input type="radio" name="notifications[notification_activity_new_mention]" id="notification-activity-new-mention-no" class="bs-styled-radio"
							   value="no" <?php checked( $mention, 'no', true ); ?> />
						<label for="notification-activity-new-mention-no"><span class="bp-screen-reader-text"><?php esc_html_e( 'No, do not send email', 'buddyboss' ); ?></span></label>
					</div>
				</td>
			</tr>
		<?php endif; ?>

		<tr id="activity-notification-settings-replies">
			<td>&nbsp;</td>
			<td><?php esc_html_e( "A member replies to an update or comment you've posted", 'buddyboss' ); ?></td>
			<td class="yes">
				<div class="bp-radio-wrap">
					<input type="radio" name="notifications[notification_activity_new_reply]" id="notification-activity-new-reply-yes" class="bs-styled-radio"
						   value="yes" <?php checked( $reply, 'yes', true ); ?> />
					<label for="notification-activity-new-reply-yes"><span class="bp-screen-reader-text"><?php esc_html_e( 'Yes, send email', 'buddyboss' ); ?></span></label>
				</div>
			</td>
			<td class="no">
				<div class="bp-radio-wrap">
					<input type="radio" name="notifications[notification_activity_new_reply]" id="notification-activity-new-reply-no" class="bs-styled-radio"
						   value="no" <?php checked( $reply, 'no', true ); ?> />
					<label for="notification-activity-new-reply-no"><span class="bp-screen-reader-text"><?php esc_html_e( 'No, do not send email', 'buddyboss' ); ?></span></label>
				</div>
			</td>
		</tr>

		<?php

		/**
		 * Fires inside the closing </tbody> tag for activity screen notification settings.
		 *
		 * @since BuddyPress 1.2.0
		 */
		do_action( 'bp_activity_screen_notification_settings' )
		?>
		</tbody>
	</table>

	<?php

}
add_action( 'bp_notification_settings', 'bp_activity_screen_notification_settings', 1 );

/**
 * Fire an email when someone mentioned users into the blog post comment and post published from Rest API.
 *
 * @since BuddyBoss 2.0.1
 *
 * @param WP_Comment $comment WP_Comment class object.
 *
 * @return void
 */
function bb_rest_mention_post_type_comment( $comment ) {
	// Bail if not a comment.
	if (
		empty( $comment )
		|| ! $comment instanceof WP_Comment
	) {
		return;
	}

	bb_mention_post_type_comment( $comment->comment_ID, $comment->comment_approved );

}

add_action( 'rest_after_insert_comment', 'bb_rest_mention_post_type_comment', 10, 1 );

/**
 * Function will send notification to followers when activity posted.
 *
 * @since BuddyBoss 2.2.3
 *
 * @param string $content     Content of the activity post update.
 * @param int    $user_id     ID of the user posting the activity update.
 * @param int    $activity_id ID of the activity item being updated.
 */
function bb_activity_send_email_to_following_post( $content, $user_id, $activity_id ) {
	global $bp_activity_edit;

	// Return if $activity_id empty or edit activity.
	if ( empty( $activity_id ) || $bp_activity_edit || ! bp_is_activity_follow_active() ) {
		return;
	}

	$activity = new BP_Activity_Activity( $activity_id );

	// Return if main activity post not found or followers empty.
	if (
		empty( $activity ) ||
		'activity' !== $activity->component ||
		in_array( $activity->privacy, array( 'document', 'media', 'video', 'onlyme' ), true )
	) {
		return;
	}

	$usernames  = bp_activity_do_mentions() ? bp_activity_find_mentions( $content ) : array();
	$parse_args = array(
		'activity'  => $activity,
		'usernames' => $usernames,
		'item_id'   => $user_id,
	);

	// Send notification to followers.
	bb_activity_create_following_post_notification( $parse_args );
}

add_action( 'bp_activity_posted_update', 'bb_activity_send_email_to_following_post', 10, 3 );
add_action( 'bb_media_after_create_parent_activity', 'bb_activity_send_email_to_following_post', 10, 3 );
add_action( 'bb_document_after_create_parent_activity', 'bb_activity_send_email_to_following_post', 10, 3 );
add_action( 'bb_video_after_create_parent_activity', 'bb_activity_send_email_to_following_post', 10, 3 );

/**
 * Function will send notification to following user.
 *
 * @since BuddyBoss 2.2.5
 *
 * @param BP_Activity_Follow $follower Contains following data.
 */
function bb_send_email_to_follower( $follower ) {

	if ( empty( $follower ) || ! bp_is_activity_follow_active() || empty( $follower->leader_id ) ) {
		return;
	}

	$user_id           = $follower->follower_id; // Current user id.
	$following_user_id = $follower->leader_id; // Following user id.

	if ( true === bb_is_notification_enabled( $following_user_id, 'bb_following_new' ) ) {
		$args                          = array(
			'tokens' => array(
				'follower.id'   => $user_id,
				'follower.name' => bp_core_get_user_displayname( $user_id ),
				'follower.url'  => esc_url( bp_core_get_user_domain( $user_id ) ),
			),
		);
		$unsubscribe_args              = array(
			'user_id'           => $following_user_id,
			'notification_type' => 'new-follower',
		);
		$args['tokens']['unsubscribe'] = esc_url( bp_email_get_unsubscribe_link( $unsubscribe_args ) );
		// Send notification email.
		bp_send_email( 'new-follower', $following_user_id, $args );
	}

	if ( bp_is_active( 'notifications' ) ) {
		bp_notifications_add_notification(
			array(
				'user_id'           => $following_user_id,
				'item_id'           => $follower->id,
				'secondary_item_id' => $user_id,
				'component_name'    => buddypress()->activity->id,
				'component_action'  => 'bb_following_new',
				'date_notified'     => bp_core_current_time(),
				'is_new'            => 1,
			)
		);
	}
}
add_action( 'bp_start_following', 'bb_send_email_to_follower' );
