<?php

/**
 * Forums Filters
 *
 * @package BuddyBoss\Core
 */

/**
 * This file contains the filters that are used through-out Forums. They are
 * consolidated here to make searching for them easier, and to help developers
 * understand at a glance the order in which things occur.
 *
 * There are a few common places that additional filters can currently be found
 *
 *  - Forums: In {@link bbPress::setup_actions()} in bbpress.php
 *  - Admin: More in {@link BBP_Admin::setup_actions()} in admin.php
 *
 * @see /core/actions.php
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Attach Forums to WordPress
 *
 * Forums uses its own internal actions to help aid in third-party plugin
 * development, and to limit the amount of potential future code changes when
 * updates to WordPress core occur.
 *
 * These actions exist to create the concept of 'plugin dependencies'. They
 * provide a safe way for plugins to execute code *only* when Forums is
 * installed and activated, without needing to do complicated guesswork.
 *
 * For more information on how this works, see the 'Plugin Dependency' section
 * near the bottom of this file.
 *
 *           v--WordPress Actions       v--Forums Sub-actions
 */
add_filter( 'request', 'bbp_request', 10 );
add_filter( 'template_include', 'bbp_template_include', 10 );
add_filter( 'wp_title', 'bbp_title', 10, 3 );
add_filter( 'body_class', 'bbp_body_class', 10, 2 );
add_filter( 'map_meta_cap', 'bbp_map_meta_caps', 10, 4 );
add_filter( 'allowed_themes', 'bbp_allowed_themes', 10 );
add_filter( 'redirect_canonical', 'bbp_redirect_canonical', 10 );
add_filter( 'plugin_locale', 'bbp_plugin_locale', 10, 2 );

// Fix post author id for anonymous posts (set it back to 0) when the post status is changed
add_filter( 'wp_insert_post_data', 'bbp_fix_post_author', 30, 2 );

// Force comments_status on Forums post types
add_filter( 'comments_open', 'bbp_force_comment_status' );

// Remove forums roles from list of all roles
add_filter( 'editable_roles', 'bbp_filter_blog_editable_roles' );

// Reply title fallback
add_filter( 'the_title', 'bbp_get_reply_title_fallback', 2, 2 );

/**
 * Feeds
 *
 * Forums comes with a number of custom RSS2 feeds that get handled outside
 * the normal scope of feeds that WordPress would normally serve. To do this,
 * we filter every page request, listen for a feed request, and trap it.
 */
add_filter( 'bbp_request', 'bbp_request_feed_trap' );

/**
 * Template Compatibility
 *
 * If you want to completely bypass this and manage your own custom Forums
 * template hierarchy, start here by removing this filter, then look at how
 * bbp_template_include() works and do something similar. :)
 */
add_filter( 'bbp_template_include', 'bbp_template_include_theme_supports', 2, 1 );
add_filter( 'bbp_template_include', 'bbp_template_include_theme_compat', 4, 2 );

// Filter Forums template locations
add_filter( 'bbp_get_template_stack', 'bbp_add_template_stack_locations' );

// Links
add_filter( 'paginate_links', 'bbp_add_view_all' );
add_filter( 'bbp_get_topic_permalink', 'bbp_add_view_all' );
add_filter( 'bbp_get_reply_permalink', 'bbp_add_view_all' );
add_filter( 'bbp_get_forum_permalink', 'bbp_add_view_all' );

// wp_filter_kses on new/edit topic/reply title
add_filter( 'bbp_new_reply_pre_title', 'wp_filter_kses' );
add_filter( 'bbp_new_topic_pre_title', 'wp_filter_kses' );
add_filter( 'bbp_edit_reply_pre_title', 'wp_filter_kses' );
add_filter( 'bbp_edit_topic_pre_title', 'wp_filter_kses' );

// Prevent posting malicious or malformed content on new/edit topic/reply
add_filter( 'bbp_new_reply_pre_content', 'bbp_encode_bad', 10 );
add_filter( 'bbp_new_reply_pre_content', 'bbp_code_trick', 20 );
add_filter( 'bbp_new_reply_pre_content', 'bbp_filter_kses', 30 );
add_filter( 'bbp_new_reply_pre_content', 'bbp_convert_mentions', 40 );
add_filter( 'bbp_new_reply_pre_content', 'balanceTags', 50 );
add_filter( 'bbp_new_topic_pre_content', 'bbp_encode_bad', 10 );
add_filter( 'bbp_new_topic_pre_content', 'bbp_code_trick', 20 );
add_filter( 'bbp_new_topic_pre_content', 'bbp_filter_kses', 30 );
add_filter( 'bbp_new_topic_pre_content', 'bbp_convert_mentions', 40 );
add_filter( 'bbp_new_topic_pre_content', 'balanceTags', 50 );
add_filter( 'bbp_new_forum_pre_content', 'bbp_encode_bad', 10 );
add_filter( 'bbp_new_forum_pre_content', 'bbp_code_trick', 20 );
add_filter( 'bbp_new_forum_pre_content', 'bbp_filter_kses', 30 );
add_filter( 'bbp_new_forum_pre_content', 'bbp_convert_mentions', 40 );
add_filter( 'bbp_new_forum_pre_content', 'balanceTags', 50 );
add_filter( 'bbp_edit_reply_pre_content', 'bbp_encode_bad', 10 );
add_filter( 'bbp_edit_reply_pre_content', 'bbp_code_trick', 20 );
add_filter( 'bbp_edit_reply_pre_content', 'bbp_filter_kses', 30 );
add_filter( 'bbp_edit_reply_pre_content', 'bbp_convert_mentions', 40 );
add_filter( 'bbp_edit_reply_pre_content', 'balanceTags', 50 );
add_filter( 'bbp_edit_topic_pre_content', 'bbp_encode_bad', 10 );
add_filter( 'bbp_edit_topic_pre_content', 'bbp_code_trick', 20 );
add_filter( 'bbp_edit_topic_pre_content', 'bbp_filter_kses', 30 );
add_filter( 'bbp_edit_topic_pre_content', 'bbp_convert_mentions', 40 );
add_filter( 'bbp_edit_topic_pre_content', 'balanceTags', 50 );
add_filter( 'bbp_edit_forum_pre_content', 'bbp_encode_bad', 10 );
add_filter( 'bbp_edit_forum_pre_content', 'bbp_code_trick', 20 );
add_filter( 'bbp_edit_forum_pre_content', 'bbp_filter_kses', 30 );
add_filter( 'bbp_edit_forum_pre_content', 'bbp_convert_mentions', 40 );
add_filter( 'bbp_edit_forum_pre_content', 'balanceTags', 50 );

// No follow and stripslashes on user profile links
add_filter( 'bbp_get_reply_author_link', 'bbp_rel_nofollow' );
add_filter( 'bbp_get_reply_author_link', 'stripslashes' );
add_filter( 'bbp_get_topic_author_link', 'bbp_rel_nofollow' );
add_filter( 'bbp_get_topic_author_link', 'stripslashes' );
add_filter( 'bbp_get_user_favorites_link', 'bbp_rel_nofollow' );
add_filter( 'bbp_get_user_favorites_link', 'stripslashes' );
add_filter( 'bbp_get_user_subscribe_link', 'bbp_rel_nofollow' );
add_filter( 'bbp_get_user_subscribe_link', 'stripslashes' );
add_filter( 'bbp_get_user_profile_link', 'bbp_rel_nofollow' );
add_filter( 'bbp_get_user_profile_link', 'stripslashes' );
add_filter( 'bbp_get_user_profile_edit_link', 'bbp_rel_nofollow' );
add_filter( 'bbp_get_user_profile_edit_link', 'stripslashes' );
add_filter( 'bbp_get_reply_author_role', 'bbp_adjust_forum_role_labels', 10, 2 );

// Run filters on reply content
add_filter( 'bbp_get_reply_content', 'bbp_make_clickable', 4 );
add_filter( 'bbp_get_reply_content', 'wptexturize', 6 );
add_filter( 'bbp_get_reply_content', 'convert_chars', 8 );
add_filter( 'bbp_get_reply_content', 'capital_P_dangit', 10 );
add_filter( 'bbp_get_reply_content', 'convert_smilies', 20 );
add_filter( 'bbp_get_reply_content', 'force_balance_tags', 30 );
add_filter( 'bbp_get_reply_content', 'wpautop', 40 );
add_filter( 'bbp_get_reply_content', 'bbp_remove_html_tags', 45 );
add_filter( 'bbp_get_reply_content', 'bbp_rel_nofollow', 50 );

// Run filters on topic content
add_filter( 'bbp_get_topic_content', 'bbp_make_clickable', 4 );
add_filter( 'bbp_get_topic_content', 'wptexturize', 6 );
add_filter( 'bbp_get_topic_content', 'convert_chars', 8 );
add_filter( 'bbp_get_topic_content', 'capital_P_dangit', 10 );
add_filter( 'bbp_get_topic_content', 'convert_smilies', 20 );
add_filter( 'bbp_get_topic_content', 'force_balance_tags', 30 );
add_filter( 'bbp_get_topic_content', 'do_blocks', 9 );
add_filter( 'bbp_get_topic_content', 'wpautop', 40 );
add_filter( 'bbp_get_topic_content', 'bbp_remove_html_tags', 45 );
add_filter( 'bbp_get_topic_content', 'bbp_rel_nofollow', 50 );
add_filter( 'bbp_get_topic_content', 'bb_forums_hide_single_url', 999999, 1 );
add_filter( 'bbp_get_reply_content', 'bb_forums_hide_single_url', 999999, 1 );

// Form textarea output - undo the code-trick done pre-save, and sanitize
add_filter( 'bbp_get_form_forum_content', 'bbp_code_trick_reverse' );
add_filter( 'bbp_get_form_forum_content', 'esc_textarea' );
add_filter( 'bbp_get_form_forum_content', 'trim' );
add_filter( 'bbp_get_form_topic_content', 'bbp_code_trick_reverse' );
add_filter( 'bbp_get_form_topic_content', 'esc_textarea' );
add_filter( 'bbp_get_form_topic_content', 'trim' );
add_filter( 'bbp_get_form_reply_content', 'bbp_code_trick_reverse' );
add_filter( 'bbp_get_form_reply_content', 'esc_textarea' );
add_filter( 'bbp_get_form_reply_content', 'trim' );

// Add number format filter to functions requesting formatted values.
add_filter( 'bbp_get_user_topic_count', 'bbp_number_format', 10 );
add_filter( 'bbp_get_user_reply_count', 'bbp_number_format', 10 );
add_filter( 'bbp_get_user_post_count', 'bbp_number_format', 10 );
add_filter( 'bbp_get_forum_subforum_count', 'bbp_number_format', 10 );
add_filter( 'bbp_get_forum_topic_count', 'bbp_number_format', 10 );
add_filter( 'bbp_get_forum_reply_count', 'bbp_number_format', 10 );
add_filter( 'bbp_get_forum_post_count', 'bbp_number_format', 10 );
add_filter( 'bbp_get_topic_voice_count', 'bbp_number_format', 10 );
add_filter( 'bbp_get_topic_reply_count', 'bbp_number_format', 10 );
add_filter( 'bbp_get_topic_post_count', 'bbp_number_format', 10 );
add_filter( 'bbp_get_topic_revision_count', 'bbp_number_format', 10 );
add_filter( 'bbp_get_reply_revision_count', 'bbp_number_format', 10 );
add_filter( 'bbp_get_forum_topic_count_hidden', 'bbp_number_format', 10 );
add_filter( 'bbp_get_topic_reply_count_hidden', 'bbp_number_format', 10 );

// Add number-not-negative filter to values that can never be negative numbers.
add_filter( 'bbp_get_user_topic_count',             'bbp_number_not_negative', 8 );
add_filter( 'bbp_get_user_reply_count',             'bbp_number_not_negative', 8 );
add_filter( 'bbp_get_user_post_count',              'bbp_number_not_negative', 8 );
add_filter( 'bbp_get_forum_subforum_count',         'bbp_number_not_negative', 8 );
add_filter( 'bbp_get_forum_topic_count',            'bbp_number_not_negative', 8 );
add_filter( 'bbp_get_forum_reply_count',            'bbp_number_not_negative', 8 );
add_filter( 'bbp_get_forum_post_count',             'bbp_number_not_negative', 8 );
add_filter( 'bbp_get_topic_voice_count',            'bbp_number_not_negative', 8 );
add_filter( 'bbp_get_topic_reply_count',            'bbp_number_not_negative', 8 );
add_filter( 'bbp_get_topic_post_count',             'bbp_number_not_negative', 8 );
add_filter( 'bbp_get_forum_topic_count_hidden',     'bbp_number_not_negative', 8 );
add_filter( 'bbp_get_topic_reply_count_hidden',     'bbp_number_not_negative', 8 );
add_filter( 'bbp_get_topic_revision_count',         'bbp_number_not_negative', 8 );
add_filter( 'bbp_get_reply_revision_count',         'bbp_number_not_negative', 8 );
add_filter( 'bbp_get_user_topic_count_int',         'bbp_number_not_negative', 8 );
add_filter( 'bbp_get_user_reply_count_int',         'bbp_number_not_negative', 8 );
add_filter( 'bbp_get_user_post_count_int',          'bbp_number_not_negative', 8 );
add_filter( 'bbp_get_forum_subforum_count_int',     'bbp_number_not_negative', 8 );
add_filter( 'bbp_get_forum_topic_count_int',        'bbp_number_not_negative', 8 );
add_filter( 'bbp_get_forum_reply_count_int',        'bbp_number_not_negative', 8 );
add_filter( 'bbp_get_forum_post_count_int',         'bbp_number_not_negative', 8 );
add_filter( 'bbp_get_topic_voice_count_int',        'bbp_number_not_negative', 8 );
add_filter( 'bbp_get_topic_reply_count_int',        'bbp_number_not_negative', 8 );
add_filter( 'bbp_get_topic_post_count_int',         'bbp_number_not_negative', 8 );
add_filter( 'bbp_get_forum_topic_count_hidden_int', 'bbp_number_not_negative', 8 );
add_filter( 'bbp_get_topic_reply_count_hidden_int', 'bbp_number_not_negative', 8 );
add_filter( 'bbp_get_topic_revision_count_int',     'bbp_number_not_negative', 8 );
add_filter( 'bbp_get_reply_revision_count_int',     'bbp_number_not_negative', 8 );

// Sanitize displayed user data
add_filter( 'bbp_get_displayed_user_field', 'bbp_sanitize_displayed_user_field', 10, 3 );

// Run wp_kses_data on topic/reply content in admin section
if ( is_admin() ) {
	add_filter( 'bbp_get_reply_content', 'bbp_kses_data' );
	add_filter( 'bbp_get_topic_content', 'bbp_kses_data' );

	// Revisions (only when not in admin)
} else {
	add_filter( 'bbp_get_reply_content', 'bbp_reply_content_append_revisions', 99, 2 );
	add_filter( 'bbp_get_topic_content', 'bbp_topic_content_append_revisions', 99, 2 );
}

// Suppress private forum details
add_filter( 'bbp_get_forum_topic_count', 'bbp_suppress_private_forum_meta', 10, 2 );
add_filter( 'bbp_get_forum_reply_count', 'bbp_suppress_private_forum_meta', 10, 2 );
add_filter( 'bbp_get_forum_post_count', 'bbp_suppress_private_forum_meta', 10, 2 );
add_filter( 'bbp_get_forum_freshness_link', 'bbp_suppress_private_forum_meta', 10, 2 );
add_filter( 'bbp_get_author_link', 'bbp_suppress_private_author_link', 10, 2 );
add_filter( 'bbp_get_topic_author_link', 'bbp_suppress_private_author_link', 10, 2 );
add_filter( 'bbp_get_reply_author_link', 'bbp_suppress_private_author_link', 10, 2 );

// Topic and reply author display names
add_filter( 'bbp_get_topic_author_display_name', 'wptexturize' );
add_filter( 'bbp_get_topic_author_display_name', 'convert_chars' );
add_filter( 'bbp_get_topic_author_display_name', 'esc_html' );
add_filter( 'bbp_get_reply_author_display_name', 'wptexturize' );
add_filter( 'bbp_get_reply_author_display_name', 'convert_chars' );
add_filter( 'bbp_get_reply_author_display_name', 'esc_html' );

/**
 * Add filters to anonymous post author data
 */
// Post author name
add_filter( 'bbp_pre_anonymous_post_author_name', 'trim', 10 );
add_filter( 'bbp_pre_anonymous_post_author_name', 'sanitize_text_field', 10 );
add_filter( 'bbp_pre_anonymous_post_author_name', 'wp_filter_kses', 10 );
add_filter( 'bbp_pre_anonymous_post_author_name', '_wp_specialchars', 30 );

// Save email
add_filter( 'bbp_pre_anonymous_post_author_email', 'trim', 10 );
add_filter( 'bbp_pre_anonymous_post_author_email', 'sanitize_email', 10 );
add_filter( 'bbp_pre_anonymous_post_author_email', 'wp_filter_kses', 10 );

// Save URL
add_filter( 'bbp_pre_anonymous_post_author_website', 'trim', 10 );
add_filter( 'bbp_pre_anonymous_post_author_website', 'wp_strip_all_tags', 10 );
add_filter( 'bbp_pre_anonymous_post_author_website', 'esc_url_raw', 10 );
add_filter( 'bbp_pre_anonymous_post_author_website', 'wp_filter_kses', 10 );

// Queries
add_filter( 'posts_request', '_bbp_has_replies_where', 10, 2 );

// Capabilities
add_filter( 'bbp_map_meta_caps', 'bbp_map_primary_meta_caps', 10, 4 ); // Primary caps
add_filter( 'bbp_map_meta_caps', 'bbp_map_forum_meta_caps', 10, 4 ); // Forums
add_filter( 'bbp_map_meta_caps', 'bbp_map_topic_meta_caps', 10, 4 ); // Topics
add_filter( 'bbp_map_meta_caps', 'bbp_map_reply_meta_caps', 10, 4 ); // Replies
add_filter( 'bbp_map_meta_caps', 'bbp_map_topic_tag_meta_caps', 10, 4 ); // Topic tags

// Clickables
add_filter( 'bbp_make_clickable', 'bbp_make_urls_clickable', 2 ); // https://bbpress.org
add_filter( 'bbp_make_clickable', 'bbp_make_ftps_clickable', 4 ); // ftps://bbpress.org
add_filter( 'bbp_make_clickable', 'bbp_make_emails_clickable', 6 ); // jjj@bbpress.org
add_filter( 'bbp_make_clickable', 'bbp_make_mentions_clickable', 8 ); // @jjj

// Search forum discussion with tags.
add_filter( 'posts_where', 'bb_forum_search_by_topic_tags', 10, 2 );

// Remove deleted members link from mention for topic/reply.
add_filter( 'bbp_get_topic_content', 'bb_mention_remove_deleted_users_link', 20, 1 );
add_filter( 'bbp_get_reply_content', 'bb_mention_remove_deleted_users_link', 20, 1 );

add_filter( 'bbp_get_topic_content', 'bb_forums_link_preview', 999, 2 );
add_filter( 'bbp_get_reply_content', 'bb_forums_link_preview', 999, 2 );

/** Deprecated ****************************************************************/

/**
 * The following filters are deprecated.
 *
 * These filters were most likely replaced by bbp_parse_args(), which includes
 * both passive and aggressive filters anywhere parse_args is used to compare
 * default arguments to passed arguments, without needing to litter the
 * codebase with _before_ and _after_ filters everywhere.
 */

/**
 * Deprecated locale filter
 *
 * @since bbPress (r4213)
 *
 * @param string $locale
 * @return string  $domain
 */
function _bbp_filter_locale( $locale = '', $domain = '' ) {

	// Only apply to the Forums text-domain
	if ( bbpress()->domain !== $domain ) {
		return $locale;
	}

	return apply_filters( 'bbpress_locale', $locale, $domain );
}
add_filter( 'bbp_plugin_locale', '_bbp_filter_locale', 10, 1 );

/**
 * Deprecated forums query filter
 *
 * @since bbPress (r3961)
 * @param array $args
 * @return array
 */
function _bbp_has_forums_query( $args = array() ) {
	return apply_filters( 'bbp_has_forums_query', $args );
}
add_filter( 'bbp_after_has_forums_parse_args', '_bbp_has_forums_query' );

/**
 * Deprecated topics query filter
 *
 * @since bbPress (r3961)
 * @param array $args
 * @return array
 */
function _bbp_has_topics_query( $args = array() ) {
	return apply_filters( 'bbp_has_topics_query', $args );
}
add_filter( 'bbp_after_has_topics_parse_args', '_bbp_has_topics_query' );

/**
 * Deprecated replies query filter
 *
 * @since bbPress (r3961)
 * @param array $args
 * @return array
 */
function _bbp_has_replies_query( $args = array() ) {
	return apply_filters( 'bbp_has_replies_query', $args );
}
add_filter( 'bbp_after_has_replies_parse_args', '_bbp_has_replies_query' );

/**
 * Search forum discussion by tag when enabled the 'Discussion Tags' from the Network search.
 *
 * @since BuddyBoss 2.2.4
 *
 * @param string $where    Where statement query.
 * @param object $wp_query WP_Query object.
 *
 * @return mixed|string
 */
function bb_forum_search_by_topic_tags( $where, $wp_query ) {
	global $wpdb;

	// If search component is not enabled, return.
	if ( ! bp_is_active( 'search' ) ) {
		return $where;
	}

	// Get query post types array .
	$post_types      = (array) $wp_query->get( 'post_type' );
	$topic_post_type = bbp_get_topic_post_type();
	$topic_taxonomy  = bbp_get_topic_tag_tax_id();

	if ( ! is_admin() && array_intersect( array( $topic_post_type, bbp_get_reply_post_type() ), $post_types ) && ! empty( $wp_query->get( 's' ) ) && bp_is_search_post_type_taxonomy_enable( $topic_taxonomy, $topic_post_type ) ) {

		$matching_terms = get_terms(
			array(
				'taxonomy'   => $topic_taxonomy,
				'fields'     => 'ids',
				'name__like' => $wp_query->get( 's' ),
			)
		);

		if ( ! empty( $matching_terms ) && ! is_wp_error( $matching_terms ) ) {
			$where .= " OR $wpdb->posts.ID IN (SELECT DISTINCT $wpdb->posts.ID FROM $wpdb->posts LEFT JOIN $wpdb->term_relationships ON ( $wpdb->posts.ID = $wpdb->term_relationships.object_id ) WHERE $wpdb->term_relationships.term_taxonomy_id IN (" . implode( ',', $matching_terms ) . ')) ';
		}
	}

	return $where;
}

/**
 * Fires when a forum/topic is transitioned from one status to another.
 *
 * @since 2.2.6
 *
 * @param string  $new_status New post status.
 * @param string  $old_status Old post status.
 * @param WP_Post $post       Post object.
 */
function bb_forums_update_subscription_status( $new_status, $old_status, $post ) {
	if ( $new_status !== $old_status && ! empty( $post->post_type ) && in_array( $post->post_type, array( bbp_get_forum_post_type(), bbp_get_topic_post_type() ), true ) ) {

		$blog_id = 0;
		if ( is_multisite() ) {
			$blog_id = get_current_blog_id();
		}

		$subscription_status = 1;
		if ( ! empty( $new_status ) && in_array( $new_status, array( bbp_get_spam_status_id(), bbp_get_trash_status_id(), bbp_get_pending_status_id() ), true ) ) {
			$subscription_status = 0;
		}

		$subscription_type = 'topic';
		if ( bbp_get_forum_post_type() === $post->post_type ) {
			$subscription_type = 'forum';
		}

		bb_subscriptions_update_subscriptions_status( $subscription_type, $post->ID, $subscription_status, $blog_id );
	}
}

add_action( 'transition_post_status', 'bb_forums_update_subscription_status', 999, 3 );

/**
 * Remove forum and topic subscriptions when add forum to group.
 *
 * @since BuddyBoss 2.2.8
 *
 * @param int    $group_id   The ID of group.
 * @param string $meta_key   The meta key of group.
 * @param string $meta_value The meta value of group.
 *
 * @return void
 */
function bb_remove_group_forum_topic_subscriptions_add_group_meta( $group_id, $meta_key, $meta_value ) {
	if (
		! empty( $group_id ) &&
		'forum_id' === $meta_key &&
		bp_is_active( 'forums' )
	) {
		bb_delete_group_forum_topic_subscriptions( $group_id );
	}
}
add_action( 'add_group_meta', 'bb_remove_group_forum_topic_subscriptions_add_group_meta', 10, 3 );

/**
 * Remove forum and topic subscriptions when update forum to group.
 *
 * @since BuddyBoss 2.2.8
 *
 * @param int    $meta_id    The ID of group meta.
 * @param int    $group_id   The ID of group.
 * @param string $meta_key   The meta key of group.
 * @param string $meta_value The meta value of group.
 *
 * @return void
 */
function bb_remove_group_forum_topic_subscriptions_update_group_meta( $meta_id, $group_id, $meta_key, $meta_value ) {
	if (
		! empty( $group_id ) &&
		'forum_id' === $meta_key &&
		bp_is_active( 'forums' )
	) {
		bb_delete_group_forum_topic_subscriptions( $group_id );
	}
}
add_action( 'updated_group_meta', 'bb_remove_group_forum_topic_subscriptions_update_group_meta', 10, 4 );

/**
 * Remove unintentional empty paragraph coming from the medium editor when only link preview.
 *
 * @since BuddyBoss 2.3.60
 *
 * @param string $content Topic and reply content.
 *
 * @return string $content Topic and reply content
 */
function bb_filter_empty_editor_content( $content = '' ) {
	if ( preg_match( '/^(<p><br><\/p>|<p><br \/><\/p>|<p><\/p>|<p><br\/><\/p>)$/i', $content ) ) {
		$content = '';
	}

	return $content;
}

add_filter( 'bbp_new_topic_pre_content', 'bb_filter_empty_editor_content', 1 );
add_filter( 'bbp_new_reply_pre_content', 'bb_filter_empty_editor_content', 1 );
add_filter( 'bbp_edit_topic_pre_content', 'bb_filter_empty_editor_content', 1 );
add_filter( 'bbp_edit_reply_pre_content', 'bb_filter_empty_editor_content', 1 );

/**
 * Embed link preview in Topic/Reply content
 *
 * @param string $content Topic/Reply content.
 * @param int    $post_id Topic/Reply id.
 *
 * @since BuddyBoss 2.3.60
 *
 * @return string
 */
function bb_forums_link_preview( $content, $post_id ) {

	$preview_data = get_post_meta( $post_id, '_link_preview_data', true );

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

	$content .= '<div class="bb-link-preview-container">';
	if ( ! empty( $preview_data['attachment_id'] ) ) {
		$image_url = wp_get_attachment_image_url( $preview_data['attachment_id'], 'full' );
		$content  .= '<div class="bb-link-preview-image">';
		$content  .= '<div class="bb-link-preview-image-cover">';
		$content  .= '<a href="' . esc_url( $preview_data['url'] ) . '" target="_blank"><img src="' . esc_url( $image_url ) . '" /></a>';
		$content  .= '</div>';
		$content  .= '</div>';
	} elseif ( ! empty( $preview_data['image_url'] ) ) {
		$content .= '<div class="bb-link-preview-image">';
		$content .= '<div class="bb-link-preview-image-cover">';
		$content .= '<a href="' . esc_url( $preview_data['url'] ) . '" target="_blank"><img src="' . esc_url( $preview_data['image_url'] ) . '" /></a>';
		$content .= '</div>';
		$content .= '</div>';
	}
	$content .= '<div class="bb-link-preview-info">';
	$content .= '<p class="bb-link-preview-link-name">' . esc_html( $domain_name ) . '</p>';
	$content .= '<p class="bb-link-preview-title"><a href="' . esc_url( $preview_data['url'] ) . '" target="_blank" rel="nofollow">' . esc_html( $preview_data['title'] ) . '</a></p>';
	$content .= '<div class="bb-link-preview-excerpt"><p>' . $description . '</p></div>';
	$content .= '</div>';
	$content .= '</div>';

	return $content;
}

/**
 * Redirect to the 404 page if the no replies for single topic page.
 *
 * @since BuddyBoss 2.3.60
 *
 * @param string $template The path of the template to include.
 *
 * @return string $template Template file to use.
 */
function bb_single_topic_no_replies_redirect_to_404( $template ) {
	if ( bbp_is_single_topic() && ! bp_is_activity_component() ) {
		if ( ! bbp_has_replies() && bbp_get_paged() > 1 ) {
			$template_404 = locate_template( '404.php' );
			if ( ! empty( $template_404 ) ) {
				global $wp_query;
				$wp_query->set_404();
				status_header( 404 );
				return $template_404;
			}
		}
	}

	return $template;
}

add_filter( 'template_include', 'bb_single_topic_no_replies_redirect_to_404' );

/**
 * Hides single URL from forum topic and reply content.
 *
 * @since 2.4.50
 *
 * @param string $content The forum topic or reply content.
 *
 * @return string
 */
function bb_forums_hide_single_url( $content ) {
	if ( empty( $content ) ) {
		return $content;
	}

	if ( strpos( $content, '<iframe' ) === false ) {
		return $content;
	}

	if ( preg_match_all( '/<p[^>]*>.*?<\/p>/', $content, $matches ) && ! empty( $matches[0] ) ) {
		$topic_content	= implode( '', $matches[0]  );	// Extract only post content. '$content' also contains author, edit and other details.
		$raw_content	= preg_replace( array( '/<a[^>]*>/', '/<\/a>/', '/<p[^>]*>/', '/<\/p>/', '/<iframe[^>]*>.*?<\/iframe>/', '/\n/', '/\r/' ), array( '', '', '', '', '' ), $topic_content );
		$content_length	= strlen( $raw_content );
		$prefixes		= '/^(http\:\/\/|https\:\/\/|www\.)/';
		$url			= '';

		if ( preg_match( $prefixes, $raw_content ) ) {
			for ( $i = 0; $i < $content_length; $i++ ) {
				if ( in_array( $raw_content[ $i ], array( ' ', '\n', '\r' ) ) ) {
					break;
				} else {
					$url .= $raw_content[ $i ];
				}
			}

			if ( ! empty( $url ) && empty( trim( str_replace( $url, '', $raw_content ) ) ) ) {
				$content	= preg_replace( '/^<p/', '<p style="display: none;"', $content, 1 );
			}
		}
	}

	return $content;
}
