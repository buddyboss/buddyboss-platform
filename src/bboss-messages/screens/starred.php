<?php
/**
 * Messages: User's "Messages > Starred" screen handler
 *
 * @package BuddyBoss\Message\Screens
 * @since BuddyPress 3.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Screen handler to display a user's "Starred" private messages page.
 *
 * @since BuddyPress 2.3.0
 */
function bboss_messages_star_screen() {
	add_action( 'bp_template_content', 'bp_messages_star_content' );

	/**
	 * Fires right before the loading of the "Starred" messages box.
	 *
	 * @since BuddyPress 2.3.0
	 */
	do_action( 'bp_messages_screen_star' );

	bboss_core_load_template( 'members/single/plugins' );
}

/**
 * Screen content callback to display a user's "Starred" messages page.
 *
 * @since BuddyPress 2.3.0
 */
function bboss_messages_star_content() {
	// Add our message thread filter.
	add_filter( 'bp_after_has_message_threads_parse_args', 'bp_messages_filter_starred_message_threads' );

	// Load the message loop template part.
	bboss_get_template_part( 'members/single/messages/messages-loop' );

	// Remove our filter.
	remove_filter( 'bp_after_has_message_threads_parse_args', 'bp_messages_filter_starred_message_threads' );
}

// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename names of this file's symbols (decision 0013): declared
// here, guarded on the renamed declaration, so each old name exists exactly when the new one does.
if ( function_exists( 'bboss_messages_star_content' ) && ! function_exists( 'bp_messages_star_content' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_messages_star_content()}.
	 *
	 * @deprecated Use bboss_messages_star_content() instead.
	 */
	function bp_messages_star_content() {
		return bboss_messages_star_content();
	}
}
if ( function_exists( 'bboss_messages_star_screen' ) && ! function_exists( 'bp_messages_star_screen' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_messages_star_screen()}.
	 *
	 * @deprecated Use bboss_messages_star_screen() instead.
	 */
	function bp_messages_star_screen() {
		return bboss_messages_star_screen();
	}
}
// @bbwt-com-end compat-layer
