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
 * bp_activity_clear_meta_cache_for_activity()
 *
 * @deprecated BuddyPress 2.0.0
 */
function bp_activity_clear_meta_cache_for_activity() {
	_deprecated_function( __FUNCTION__, '2.0.0', 'Use WP metadata API instead' );
}

/**
 * bp_blogs_catch_published_post()
 *
 * @deprecated BuddyPress 2.0.0
 */
function bp_blogs_catch_published_post() {
	_deprecated_function( __FUNCTION__, '2.0', 'bp_blogs_catch_transition_post_status()' );
}

/**
 * bp_messages_screen_inbox_mark_notifications()
 *
 * @deprecated BuddyPress 2.0.0
 */
function bp_messages_screen_inbox_mark_notifications() {
	_deprecated_function( __FUNCTION__, '2.0', 'bp_messages_screen_conversation_mark_notifications()' );
}
