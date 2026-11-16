<?php
/**
 * Deprecated Functions
 *
 * @package BuddyBoss\Core
 * @deprecated BuddyPress 2.0.0
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * bboss_activity_clear_meta_cache_for_activity()
 *
 * @deprecated BuddyPress 2.0.0
 */
function bboss_activity_clear_meta_cache_for_activity() {
	_deprecated_function( __FUNCTION__, '2.0.0', 'Use WP metadata API instead' );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_activity_clear_meta_cache_for_activity' ) && ! function_exists( 'bp_activity_clear_meta_cache_for_activity' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_activity_clear_meta_cache_for_activity()}.
	 *
	 * @deprecated Use bboss_activity_clear_meta_cache_for_activity() instead.
	 */
	function bp_activity_clear_meta_cache_for_activity() {
		return bboss_activity_clear_meta_cache_for_activity();
	}
}
// @bbwt-com-end compat-layer


/**
 * bboss_blogs_catch_published_post()
 *
 * @deprecated BuddyPress 2.0.0
 */
function bboss_blogs_catch_published_post() {
	_deprecated_function( __FUNCTION__, '2.0', 'bp_blogs_catch_transition_post_status()' );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_blogs_catch_published_post' ) && ! function_exists( 'bp_blogs_catch_published_post' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_blogs_catch_published_post()}.
	 *
	 * @deprecated Use bboss_blogs_catch_published_post() instead.
	 */
	function bp_blogs_catch_published_post() {
		return bboss_blogs_catch_published_post();
	}
}
// @bbwt-com-end compat-layer


/**
 * bboss_messages_screen_inbox_mark_notifications()
 *
 * @deprecated BuddyPress 2.0.0
 */
function bboss_messages_screen_inbox_mark_notifications() {
	_deprecated_function( __FUNCTION__, '2.0', 'bp_messages_screen_conversation_mark_notifications()' );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_messages_screen_inbox_mark_notifications' ) && ! function_exists( 'bp_messages_screen_inbox_mark_notifications' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_messages_screen_inbox_mark_notifications()}.
	 *
	 * @deprecated Use bboss_messages_screen_inbox_mark_notifications() instead.
	 */
	function bp_messages_screen_inbox_mark_notifications() {
		return bboss_messages_screen_inbox_mark_notifications();
	}
}
// @bbwt-com-end compat-layer

