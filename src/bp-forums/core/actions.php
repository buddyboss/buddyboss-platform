<?php

/**
 * Forums Actions
 *
 * @package BuddyBoss\Core
 */

/**
 * This file contains the actions that are used through-out Forums. They are
 * consolidated here to make searching for them easier, and to help developers
 * understand at a glance the order in which things occur.
 *
 * There are a few common places that additional actions can currently be found
 *
 *  - Forums: In {@link bbPress::setup_actions()} in bbpress.php
 *  - Admin: More in {@link BBP_Admin::setup_actions()} in admin.php
 *
 * @see /core/filters.php
 */

// Exit if accessed directly.
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
 *           v--WordPress Actions        v--Forums Sub-actions
 */
add_action( 'plugins_loaded', 'bbp_loaded', 10 );
add_action( 'init', 'bbp_init', 0 ); // Early for bbp_register.
add_action( 'parse_query', 'bbp_parse_query', 2 ); // Early for overrides.
add_action( 'widgets_init', 'bbp_widgets_init', 10 );
add_action( 'generate_rewrite_rules', 'bbp_generate_rewrite_rules', 10 );
add_action( 'wp_enqueue_scripts', 'bbp_enqueue_scripts', 10 );
add_action( 'wp_head', 'bbp_head', 10 );
add_action( 'wp_footer', 'bbp_footer', 10 );
add_action( 'wp_roles_init', 'bbp_roles_init', 10 );
add_action( 'set_current_user', 'bbp_setup_current_user', 10 );
add_action( 'setup_theme', 'bbp_setup_theme', 10 );
add_action( 'after_setup_theme', 'bbp_after_setup_theme', 10 );
add_action( 'template_redirect', 'bbp_template_redirect', 8 ); // Before BuddyPress's 10 [BB2225].
add_action( 'login_form_login', 'bbp_login_form_login', 10 );
add_action( 'profile_update', 'bbp_profile_update', 10, 2 ); // user_id and old_user_data.
add_action( 'user_register', 'bbp_user_register', 10 );

/**
 * bbp_loaded - Attached to 'plugins_loaded' above.
 *
 * Attach various loader actions to the bbp_loaded action.
 * The load order helps to execute code at the correct time.
 *                                                         v---Load order
 */
add_action( 'bbp_loaded', 'bbp_constants', 2 );
add_action( 'bbp_loaded', 'bbp_boot_strap_globals', 4 );
add_action( 'bbp_loaded', 'bbp_includes', 6 );
add_action( 'bbp_loaded', 'bbp_setup_globals', 8 );
add_action( 'bbp_loaded', 'bbp_setup_option_filters', 10 );
add_action( 'bbp_loaded', 'bbp_setup_user_option_filters', 12 );
add_action( 'bbp_loaded', 'bbp_register_theme_packages', 14 );
add_action( 'bbp_loaded', 'bbp_filter_user_roles_option', 16 );

/**
 * bbp_init - Attached to 'init' above.
 *
 * Attach various initialization actions to the init action.
 * The load order helps to execute code at the correct time.
 *                                               v---Load order
 */
add_action( 'bbp_init', 'bbp_register', 0 );
add_action( 'bbp_init', 'bbp_add_rewrite_tags', 20 );
add_action( 'bbp_init', 'bbp_add_rewrite_rules', 30 );
add_action( 'bbp_init', 'bbp_add_permastructs', 40 );
add_action( 'bbp_init', 'bbp_setup_engagements', 50  );
add_action( 'bbp_init', 'bbp_ready', 999 );

/**
 * bbp_roles_init - Attached to 'wp_roles_init' above.
 */
add_action( 'bbp_roles_init', 'bbp_add_forums_roles', 1 );

/**
 * When setting up the current user, make sure they have a role for the forums.
 *
 * This is multisite aware, thanks to bbp_filter_user_roles_option(), hooked to
 * the 'bbp_loaded' action above.
 */
add_action( 'bbp_setup_current_user', 'bbp_set_current_user_default_role' );

/**
 * bbp_register - Attached to 'init' above on 0 priority.
 *
 * Attach various initialization actions early to the init action.
 * The load order helps to execute code at the correct time.
 *                                                         v---Load order
 */
add_action( 'bbp_register', 'bbp_register_post_types', 2 );
add_action( 'bbp_register', 'bbp_register_post_statuses', 4 );
add_action( 'bbp_register', 'bbp_register_taxonomies', 6 );
add_action( 'bbp_register', 'bbp_register_views', 8 );
add_action( 'bbp_register', 'bbp_register_shortcodes', 10 );

// Autoembeds.
add_action( 'bbp_init', 'bbp_reply_content_autoembed', 8 );
add_action( 'bbp_init', 'bbp_topic_content_autoembed', 8 );

/**
 * bbp_ready - attached to end 'bbp_init' above.
 *
 * Attach actions to the ready action after Forums has fully initialized.
 * The load order helps to execute code at the correct time.
 *                                                v---Load order
 */
add_action( 'bbp_ready', 'bbp_setup_akismet', 2 ); // Spam prevention for topics and replies.

// Try to load the bbpress-functions.php file from the active themes.
add_action( 'bbp_after_setup_theme', 'bbp_load_theme_functions', 10 );

// Widgets.
add_action( 'bbp_widgets_init', array( 'BBP_Login_Widget', 'register_widget' ), 10 );
add_action( 'bbp_widgets_init', array( 'BBP_Views_Widget', 'register_widget' ), 10 );
add_action( 'bbp_widgets_init', array( 'BBP_Search_Widget', 'register_widget' ), 10 );
add_action( 'bbp_widgets_init', array( 'BBP_Forums_Widget', 'register_widget' ), 10 );
add_action( 'bbp_widgets_init', array( 'BBP_Topics_Widget', 'register_widget' ), 10 );
add_action( 'bbp_widgets_init', array( 'BBP_Replies_Widget', 'register_widget' ), 10 );
add_action( 'bbp_widgets_init', array( 'BBP_Stats_Widget', 'register_widget' ), 10 );

// Notices (loaded after bbp_init for translations).
add_action( 'bbp_head', 'bbp_login_notices' );
add_action( 'bbp_head', 'bbp_topic_notices' );
add_action( 'bbp_template_notices', 'bbp_template_notices' );

// Always exclude private/hidden forums if needed.
add_action( 'pre_get_posts', 'bbp_pre_get_posts_normalize_forum_visibility', 4 );

// Profile Page Messages.
add_action( 'bbp_template_notices', 'bbp_notice_edit_user_success' );
add_action( 'bbp_template_notices', 'bbp_notice_edit_user_is_super_admin', 2 );

// Before Delete/Trash/Untrash Topic.
add_action( 'wp_trash_post', 'bbp_trash_forum' );
add_action( 'trash_post', 'bbp_trash_forum' );
add_action( 'untrash_post', 'bbp_untrash_forum' );
add_action( 'delete_post', 'bbp_delete_forum' );

// After Deleted/Trashed/Untrashed Topic.
add_action( 'trashed_post', 'bbp_trashed_forum' );
add_action( 'untrashed_post', 'bbp_untrashed_forum' );
add_action( 'deleted_post', 'bbp_deleted_forum' );

// Auto trash/untrash/delete a forums topics.
add_action( 'bbp_delete_forum', 'bbp_delete_forum_topics', 10 );
add_action( 'bbp_trash_forum', 'bbp_trash_forum_topics', 10 );
add_action( 'bbp_untrash_forum', 'bbp_untrash_forum_topics', 10 );

// New/Edit Forum.
add_action( 'bbp_new_forum', 'bbp_update_forum', 10 );
add_action( 'bbp_edit_forum', 'bbp_update_forum', 10 );

// Save forum extra metadata.
add_action( 'bbp_new_forum_post_extras', 'bbp_save_forum_extras', 2 );
add_action( 'bbp_edit_forum_post_extras', 'bbp_save_forum_extras', 2 );
add_action( 'bbp_forum_attributes_metabox_save', 'bbp_save_forum_extras', 2 );

// New/Edit Reply.
add_action( 'bbp_new_reply', 'bbp_update_reply', 10, 7 );
add_action( 'bbp_edit_reply', 'bbp_update_reply', 10, 7 );

// Before Delete/Trash/Untrash Reply.
add_action( 'wp_trash_post', 'bbp_trash_reply' );
add_action( 'trash_post', 'bbp_trash_reply' );
add_action( 'untrash_post', 'bbp_untrash_reply' );
add_action( 'delete_post', 'bbp_delete_reply' );

// After Deleted/Trashed/Untrashed Reply.
add_action( 'trashed_post', 'bbp_trashed_reply' );
add_action( 'untrashed_post', 'bbp_untrashed_reply', 10, 2 );
add_action( 'deleted_post', 'bbp_deleted_reply' );

// New/Edit Topic.
add_action( 'bbp_new_topic', 'bbp_update_topic', 10, 5 );
add_action( 'bbp_edit_topic', 'bbp_update_topic', 10, 5 );

// Split/Merge Topic.
add_action( 'bbp_merged_topic', 'bbp_merge_topic_count', 1, 3 );
add_action( 'bbp_post_split_topic', 'bbp_split_topic_count', 1, 3 );

// Move Reply.
add_action( 'bbp_post_move_reply', 'bbp_move_reply_count', 1, 3 );

// Before Delete/Trash/Untrash Topic.
add_action( 'wp_trash_post', 'bbp_trash_topic' );
add_action( 'trash_post', 'bbp_trash_topic' );
add_action( 'untrash_post', 'bbp_untrash_topic' );
add_action( 'delete_post', 'bbp_delete_topic' );

// After Deleted/Trashed/Untrashed Topic.
add_action( 'trashed_post', 'bbp_trashed_topic' );
add_action( 'untrashed_post', 'bbp_untrashed_topic' );
add_action( 'deleted_post', 'bbp_deleted_topic' );

// Favorites.
add_action( 'bbp_trash_topic', 'bbp_remove_topic_from_all_favorites' );
add_action( 'bbp_delete_topic', 'bbp_remove_topic_from_all_favorites' );

// Subscriptions.
add_action( 'bbp_delete_topic', 'bbp_remove_topic_from_all_subscriptions' );
add_action( 'bbp_delete_forum', 'bbp_remove_forum_from_all_subscriptions' );
add_action( 'bbp_new_reply', 'bbp_notify_topic_subscribers', 9999, 5 );
add_action( 'bbp_new_topic', 'bbp_notify_forum_subscribers', 9999, 4 );

// Sticky.
add_action( 'bbp_trash_topic', 'bbp_unstick_topic' );
add_action( 'bbp_delete_topic', 'bbp_unstick_topic' );

// Update topic branch.
add_action( 'bbp_trashed_topic', 'bbp_update_topic_walker' );
add_action( 'bbp_untrashed_topic', 'bbp_update_topic_walker' );
add_action( 'bbp_deleted_topic', 'bbp_update_topic_walker' );
add_action( 'bbp_spammed_topic', 'bbp_update_topic_walker' );
add_action( 'bbp_unspammed_topic', 'bbp_update_topic_walker' );

// Update reply branch.
add_action( 'bbp_trashed_reply', 'bbp_update_reply_walker' );
add_action( 'bbp_untrashed_reply', 'bbp_update_reply_walker' );
add_action( 'bbp_deleted_reply', 'bbp_update_reply_walker' );
add_action( 'bbp_spammed_reply', 'bbp_update_reply_walker' );
add_action( 'bbp_unspammed_reply', 'bbp_update_reply_walker' );

// User status.
// @todo make these sub-actions.
add_action( 'make_ham_user', 'bbp_make_ham_user' );
add_action( 'make_spam_user', 'bbp_make_spam_user' );

// User role.
add_action( 'bbp_profile_update', 'bbp_profile_update_role' );

// Hook WordPress admin actions to Forums profiles on save.
add_action( 'bbp_user_edit_after', 'bbp_user_edit_after' );

// Caches.
add_action( 'bbp_new_forum_pre_extras', 'bbp_clean_post_cache' );
add_action( 'bbp_new_forum_post_extras', 'bbp_clean_post_cache' );
add_action( 'bbp_new_topic_pre_extras', 'bbp_clean_post_cache' );
add_action( 'bbp_new_topic_post_extras', 'bbp_clean_post_cache' );
add_action( 'bbp_new_reply_pre_extras', 'bbp_clean_post_cache' );
add_action( 'bbp_new_reply_post_extras', 'bbp_clean_post_cache' );
/**
 * Forums needs to redirect the user around in a few different circumstances:
 *
 * 1. POST and GET requests
 * 2. Accessing private or hidden content (forums/topics/replies)
 * 3. Editing forums, topics, replies, users, and tags
 * 4. Forums specific AJAX requests
 */
add_action( 'bbp_template_redirect', 'bbp_forum_enforce_blocked', 1 );
add_action( 'bbp_template_redirect', 'bbp_forum_enforce_hidden', 1 );
add_action( 'bbp_template_redirect', 'bbp_forum_enforce_private', 1 );
add_action( 'bbp_template_redirect', 'bbp_post_request', 10 );
add_action( 'bbp_template_redirect', 'bbp_get_request', 10 );
add_action( 'bbp_template_redirect', 'bbp_check_forum_edit', 10 );
add_action( 'bbp_template_redirect', 'bbp_check_topic_edit', 10 );
add_action( 'bbp_template_redirect', 'bbp_check_reply_edit', 10 );
add_action( 'bbp_template_redirect', 'bbp_check_topic_tag_edit', 10 );

// Must be after bbp_template_include_theme_compat.
add_action( 'bbp_template_redirect', 'bbp_remove_adjacent_posts', 10 );

// Theme-side POST requests.
add_action( 'bbp_post_request', 'bbp_do_ajax', 1 );
add_action( 'bbp_post_request', 'bbp_edit_topic_tag_handler', 1 );
add_action( 'bbp_post_request', 'bbp_edit_forum_handler', 1 );
add_action( 'bbp_post_request', 'bbp_edit_reply_handler', 1 );
add_action( 'bbp_post_request', 'bbp_edit_topic_handler', 1 );
add_action( 'bbp_post_request', 'bbp_merge_topic_handler', 1 );
add_action( 'bbp_post_request', 'bbp_split_topic_handler', 1 );
add_action( 'bbp_post_request', 'bbp_move_reply_handler', 1 );
add_action( 'bbp_post_request', 'bbp_new_forum_handler', 10 );
add_action( 'bbp_post_request', 'bbp_new_reply_handler', 10 );
add_action( 'bbp_post_request', 'bbp_new_topic_handler', 10 );

// Theme-side GET requests.
add_action( 'bbp_get_request', 'bbp_toggle_topic_handler', 1 );
add_action( 'bbp_get_request', 'bbp_toggle_reply_handler', 1 );
add_action( 'bbp_get_request', 'bbp_favorites_handler', 1 );
add_action( 'bbp_get_request', 'bbp_subscriptions_handler', 1 );
add_action( 'bbp_get_request', 'bbp_forum_subscriptions_handler', 1 );
add_action( 'bbp_get_request', 'bbp_search_results_redirect', 10 );

// Maybe convert the users password.
add_action( 'bbp_login_form_login', 'bbp_user_maybe_convert_pass' );

add_action( 'wp_ajax_post_topic_reply_draft', 'bb_post_topic_reply_draft' );

add_action( 'wp_footer', 'bb_forum_add_content_popup' );

add_action( 'bbp_new_topic', 'bb_forums_save_link_preview_data' );
add_action( 'bbp_new_reply', 'bb_forums_save_link_preview_data' );
add_action( 'bbp_edit_topic', 'bb_forums_save_link_preview_data' );
add_action( 'bbp_edit_reply', 'bb_forums_save_link_preview_data' );

/**
 * Register the forum notifications.
 *
 * @since BuddyBoss 1.9.3
 */
function bb_load_forums_notifications() {
	if ( class_exists( 'BP_Forums_Notification' ) ) {
		BP_Forums_Notification::instance();
	}
}
// Load Forums Notifications.
add_action( 'bp_forums_includes', 'bb_load_forums_notifications' );

/**
 * Add Forum/Topic Subscribe email settings to the Settings > Notifications page.
 *
 * @since BuddyBoss 1.5.9
 */
function forums_notification_settings() {
	if ( bp_action_variables() ) {
		bp_do_404();

		return;
	}

	// Bail out if legacy method not enabled.
	if ( false === bb_enabled_legacy_email_preference() || ! bbp_is_subscriptions_active() ) {
		return;
	}

	$notification_forums_following_reply = bp_get_user_meta( bp_displayed_user_id(), 'notification_forums_following_reply', true );
	$notification_forums_following_topic = bp_get_user_meta( bp_displayed_user_id(), 'notification_forums_following_topic', true );
	if ( ! $notification_forums_following_reply ) {
		$notification_forums_following_reply = 'yes';
	}

	if ( ! $notification_forums_following_topic ) {
		$notification_forums_following_topic = 'yes';
	}
	?>

	<table class="notification-settings" id="forums-notification-settings">
		<thead>
		<tr>
			<th class="icon"></th>
			<th class="title"><?php esc_html_e( 'Forums', 'buddyboss' ); ?></th>
			<th class="yes"><?php esc_html_e( 'Yes', 'buddyboss' ); ?></th>
			<th class="no"><?php esc_html_e( 'No', 'buddyboss' ); ?></th>
		</tr>
		</thead>
		<tbody>
		<tr id="forums-notification-settings-new-message">
			<td></td>
			<td><?php esc_html_e( 'A member replies to a discussion you are subscribed to', 'buddyboss' ); ?></td>
			<td class="yes">
				<div class="bp-radio-wrap">
					<input type="radio" name="notifications[notification_forums_following_reply]" id="notification-forums-reply-new-messages-yes" class="bs-styled-radio" value="yes" <?php checked( $notification_forums_following_reply, 'yes', true ); ?> />
					<label for="notification-forums-reply-new-messages-yes"><span class="bp-screen-reader-text"><?php esc_html_e( 'Yes, send email', 'buddyboss' ); ?></span></label>
				</div>
			</td>
			<td class="no">
				<div class="bp-radio-wrap">
					<input type="radio" name="notifications[notification_forums_following_reply]" id="notification-forums-reply-new-messages-no" class="bs-styled-radio" value="no" <?php checked( $notification_forums_following_reply, 'no', true ); ?> />
					<label for="notification-forums-reply-new-messages-no"><span class="bp-screen-reader-text"><?php esc_html_e( 'No, do not send email', 'buddyboss' ); ?></span></label>
				</div>
			</td>
		</tr>
		<tr id="forums-notification-settings-new-message">
			<td></td>
			<td><?php esc_html_e( 'A member creates a discussion in a forum you are subscribed to', 'buddyboss' ); ?></td>
			<td class="yes">
				<div class="bp-radio-wrap">
					<input type="radio" name="notifications[notification_forums_following_topic]" id="notification-forums-topic-new-messages-yes" class="bs-styled-radio" value="yes" <?php checked( $notification_forums_following_topic, 'yes', true ); ?> />
					<label for="notification-forums-topic-new-messages-yes"><span class="bp-screen-reader-text"><?php esc_html_e( 'Yes, send email', 'buddyboss' ); ?></span></label>
				</div>
			</td>
			<td class="no">
				<div class="bp-radio-wrap">
					<input type="radio" name="notifications[notification_forums_following_topic]" id="notification-forums-topic-new-messages-no" class="bs-styled-radio" value="no" <?php checked( $notification_forums_following_topic, 'no', true ); ?> />
					<label for="notification-forums-topic-new-messages-no"><span class="bp-screen-reader-text"><?php esc_html_e( 'No, do not send email', 'buddyboss' ); ?></span></label>
				</div>
			</td>
		</tr>
		<?php

		/**
		 * Fires inside the closing </tbody> tag for forums screen notification settings.
		 *
		 * @since BuddyBoss 1.5.9
		 */
		do_action( 'forums_screen_notification_settings' );
		?>
		</tbody>
	</table>

	<?php
}
add_action( 'bp_notification_settings', 'forums_notification_settings', 11 );

/**
 * Save topic/reply draft data.
 *
 * @since BuddyBoss 2.0.4
 */
function bb_post_topic_reply_draft() {
	if ( ! is_user_logged_in() || empty( $_POST['_wpnonce_post_topic_reply_draft'] ) || ! wp_verify_nonce( $_POST['_wpnonce_post_topic_reply_draft'], 'post_topic_reply_draft_data' ) ) {
		wp_send_json_error();
	}

	$draft_topic_reply = $_REQUEST['draft_topic_reply'] ?? '';
	$usermeta_key      = 'bb_user_topic_reply_draft';
	$user_id           = bp_loggedin_user_id();
	$all_data          = array();

	if ( ! empty( $_REQUEST['draft_topic_reply'] ) && ! is_array( $_REQUEST['draft_topic_reply'] ) ) {
		$draft_topic_reply = json_decode( stripslashes( $draft_topic_reply ), true );
	}

	if ( ! empty( $_REQUEST['all_data'] ) && ! is_array( $_REQUEST['all_data'] ) ) {
		$all_data = json_decode( stripslashes( $_REQUEST['all_data'] ), true );
	}

	if ( is_array( $draft_topic_reply ) && isset( $draft_topic_reply['data_key'], $draft_topic_reply['object'] ) ) {

		$existing_draft = bp_get_user_meta( $user_id, $usermeta_key, true );

		if ( isset( $existing_draft[ $draft_topic_reply['data_key'] ] ) ) {
			$removed_data = $existing_draft[ $draft_topic_reply['data_key'] ];

			// Delete medias.
			if ( isset( $removed_data['bbp_media'] ) && ! empty( $removed_data['bbp_media'] ) ) {
				$remove_media_data = json_decode( stripslashes( $removed_data['bbp_media'] ), true );

				if ( ! empty( $remove_media_data ) ) {
					foreach ( $remove_media_data as $media_attachment ) {
						if ( ! empty( $media_attachment['id'] ) && 0 < (int) $media_attachment['id'] ) {
							wp_delete_attachment( $media_attachment['id'], true );
						}
					}
				}
			}

			// Delete documents.
			if ( isset( $removed_data['bbp_document'] ) && ! empty( $removed_data['bbp_document'] ) ) {
				$remove_document_data = json_decode( stripslashes( $removed_data['bbp_document'] ), true );

				if ( ! empty( $remove_document_data ) ) {
					foreach ( $remove_document_data as $document_attachment ) {
						if ( ! empty( $document_attachment['id'] ) && 0 < (int) $document_attachment['id'] ) {
							wp_delete_attachment( $document_attachment['id'], true );
						}
					}
				}
			}

			// Delete videos.
			if ( isset( $removed_data['bbp_video'] ) && ! empty( $removed_data['bbp_video'] ) ) {
				$remove_video_data = json_decode( stripslashes( $removed_data['bbp_video'] ), true );

				if ( ! empty( $remove_video_data ) ) {
					foreach ( $remove_video_data as $video_attachment ) {
						if ( ! empty( $video_attachment['id'] ) && 0 < (int) $video_attachment['id'] ) {
							wp_delete_attachment( $video_attachment['id'], true );
						}
					}
				}
			}

			unset( $existing_draft[ $draft_topic_reply['data_key'] ] );
		}

		if ( empty( $existing_draft ) || is_string( $existing_draft ) ) {
			$existing_draft = array();
		}

		if ( isset( $draft_topic_reply['post_action'] ) && 'update' === $draft_topic_reply['post_action'] ) {

			// Set media draft meta key to avoid delete from cron job 'bp_media_delete_orphaned_attachments'.
			if ( isset( $draft_topic_reply['data']['bbp_media'] ) && ! empty( $draft_topic_reply['data']['bbp_media'] ) ) {
				$new_media_data = json_decode( stripslashes( $draft_topic_reply['data']['bbp_media'] ), true );

				if ( ! empty( $new_media_data ) ) {
					foreach ( $new_media_data as $media_key => $new_media_attachment ) {
						if ( ! isset( $new_media_attachment['bb_media_draft'] ) ) {
							$new_media_data[ $media_key ]['bb_media_draft'] = 1;
							update_post_meta( $new_media_attachment['id'], 'bb_media_draft', 1 );
						}
					}
				}

				$draft_topic_reply['data']['bbp_media'] = wp_json_encode( $new_media_data );
			}

			// Set document draft meta key to avoid delete from cron job 'bp_media_delete_orphaned_attachments'.
			if ( isset( $draft_topic_reply['data']['bbp_document'] ) && ! empty( $draft_topic_reply['data']['bbp_document'] ) ) {
				$new_document_data = json_decode( stripslashes( $draft_topic_reply['data']['bbp_document'] ), true );

				if ( ! empty( $new_document_data ) ) {
					foreach ( $new_document_data as $document_key => $new_document_attachment ) {
						if ( ! isset( $new_document_attachment['bb_media_draft'] ) ) {
							$new_document_data[ $document_key ]['bb_media_draft'] = 1;
							update_post_meta( $new_document_attachment['id'], 'bb_media_draft', 1 );
						}
					}
				}

				$draft_topic_reply['data']['bbp_document'] = wp_json_encode( $new_document_data );
			}

			// Set video draft meta key to avoid delete from cron job 'bp_media_delete_orphaned_attachments'.
			if ( isset( $draft_topic_reply['data']['bbp_video'] ) && ! empty( $draft_topic_reply['data']['bbp_video'] ) ) {
				$new_video_data = json_decode( stripslashes( $draft_topic_reply['data']['bbp_video'] ), true );

				if ( ! empty( $new_video_data ) ) {
					foreach ( $new_video_data as $video_key => $new_video_attachment ) {
						if ( ! isset( $new_video_attachment['bb_media_draft'] ) ) {
							$new_video_data[ $video_key ]['bb_media_draft'] = 1;
							update_post_meta( $new_video_attachment['id'], 'bb_media_draft', 1 );
						}
					}
				}

				$draft_topic_reply['data']['bbp_video'] = wp_json_encode( $new_video_data );
			}

			$existing_draft[ $draft_topic_reply['data_key'] ] = $draft_topic_reply;

			if ( ! empty( $all_data ) ) {

				foreach ( $all_data as $data_key => $d_data ) {

					// Avoid conflict with current data.
					if ( $draft_topic_reply['data_key'] === $data_key ) {
						continue;
					}

					// Update the all data.
					if ( isset( $existing_draft[ $data_key ] ) ) {
						$existing_draft[ $data_key ]['data'] = $d_data;
					}
				}
			}
		}

		bp_update_user_meta( $user_id, $usermeta_key, $existing_draft );
	}

	wp_send_json_success(
		array(
			'draft_activity' => $draft_topic_reply,
		)
	);
}

/**
 * Function add pop up for single page forum content.
 *
 * @since BuddyBoss 2.2.1
 */
function bb_forum_add_content_popup() {
	global $template_forum_ids;

	if ( empty( $template_forum_ids ) ) {
		return;
	}

	$template_forum_ids = array_unique( $template_forum_ids );

	// Output the extracted IDs.
	foreach ( $template_forum_ids as $forum_id ) {
	?>
		<!-- Forum description popup -->
		<div class="bb-action-popup" id="single-forum-description-popup-<?php echo esc_attr( $forum_id ); ?>" style="display: none">
			<transition name="modal">
				<div class="modal-mask bb-white bbm-model-wrap">
					<div class="modal-wrapper">
						<div class="modal-container">
							<header class="bb-model-header">
								<h4><span class="target_name"><?php echo esc_html__( 'Forum Description', 'buddyboss' ); ?></span></h4>
								<a class="bb-close-action-popup bb-model-close-button" href="#">
									<span class="bb-icon-l bb-icon-times"></span>
								</a>
							</header>
							<div class="bb-action-popup-content">
								<?php echo wpautop( wp_kses_post( bbp_get_forum_content( $forum_id ) ) ); ?>
							</div>
						</div>
					</div>
				</div>
			</transition>
		</div> <!-- .bb-action-popup -->
	<?php
	}

	unset( $template_forum_ids );
}

/**
 * Save link preview data into topic/reply meta key "_link_preview_data"
 *
 * @since BuddyBoss 2.3.60
 *
 * @param int $post_id Discussion/Reply id.
 */
function bb_forums_save_link_preview_data( $post_id ) {

	$link_preview_data = array();

	if (
		empty( $_POST['action'] ) || //phpcs:ignore WordPress.Security.NonceVerification.Missing
		! in_array(
			$_POST['action'], // phpcs:ignore WordPress.Security.NonceVerification.Missing
			array(
				'bbp-new-topic',
				'bbp-new-reply',
				'reply',
				'bbp-edit-topic',
				'bbp-edit-reply',
			),
			true
		)
	) {
		return false;
	}

	if ( ! empty( $_POST['link_preview_data'] ) ) {
		$link_preview_data = get_object_vars( json_decode( stripslashes( $_POST['link_preview_data'] ) ) );
	} else {

		// Allow Link preview related keys.
		$allowed_keys = array(
			'link_url',
			'link_embed',
			'link_title',
			'link_description',
			'link_image',
			'link_image_index_save'
		);

		// Filter the $_POST array to only include allowed keys.
		$link_preview_data = array_filter( $_POST, function( $key ) use ( $allowed_keys ) {
			return in_array( $key, $allowed_keys );
		}, ARRAY_FILTER_USE_KEY );
	}

	$link_url = '';
	if ( ! empty( $link_preview_data['link_url'] ) ) {
		$parsed_url = wp_parse_url( $link_preview_data['link_url'] );
		if ( ! $parsed_url || empty( $parsed_url['host'] ) ) {
			$link_url = 'http://' . $link_preview_data['link_url'];
		} else {
			$link_url = $link_preview_data['link_url'];
		}
	}

	$link_url   = ! empty( $link_url ) ? filter_var( $link_url, FILTER_VALIDATE_URL ) : '';
	$link_embed = isset( $link_preview_data['link_embed'] ) ? filter_var( $link_preview_data['link_embed'], FILTER_VALIDATE_BOOLEAN ) : false;

	// Check if link url is set or not.
	if ( empty( $link_url ) ) {
		if ( false === $link_embed ) {
			update_post_meta( $post_id, '_link_embed', '0' );

			// This will remove the preview data if the activity don't have anymore link in content.
			update_post_meta( $post_id, '_link_preview_data', '' );
		}

		return;
	}

	$link_title       = ! empty( $link_preview_data['link_title'] ) ? filter_var( $link_preview_data['link_title'] ) : '';
	$link_description = ! empty( $link_preview_data['link_description'] ) ? filter_var( $link_preview_data['link_description'] ) : '';
	$link_image       = ! empty( $link_preview_data['link_image'] ) ? filter_var( $link_preview_data['link_image'], FILTER_VALIDATE_URL ) : '';

	// Check if link embed was used.
	if ( true === $link_embed && ! empty( $link_url ) ) {
		update_post_meta( $post_id, '_link_embed', $link_url );
		update_post_meta( $post_id, '_link_preview_data', '' );

		return;
	} else {
		update_post_meta( $post_id, '_link_embed', '0' );
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

	$preview_data['link_image_index_save'] = isset( $link_preview_data['link_image_index_save'] ) ? filter_var( $link_preview_data['link_image_index_save'] ) : '';

	if ( ! empty( $link_title ) ) {
		$preview_data['title'] = $link_title;
	}

	if ( ! empty( $link_description ) ) {
		$preview_data['description'] = $link_description;
	}

	update_post_meta( $post_id, '_link_preview_data', $preview_data );
}
