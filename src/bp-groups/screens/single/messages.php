<?php
/**
 * Groups: Single group "Group Messages" screen handler
 *
 * @package BuddyBoss\Groups\Screens
 * @since BuddyBoss 1.2.9
 */

/**
 * Handle the display of a group's Group Messages page.
 *
 * @since BuddyBoss 1.2.9
 */
function groups_screen_group_messages() {

	if ( ! bp_is_single_item() || ! bp_disable_group_messages() || ! bp_is_current_action( 'messages' ) ) {
		return false;
	}

	if ( false === bp_get_group_current_messages_tab() && 'messages' === bp_current_action() ) {
		bp_core_redirect( bp_get_group_permalink( groups_get_current_group() ) . 'messages/public-message/' );
	}

	add_action( 'bp_template_content', 'bp_groups_public_message_screen_content' );

	/**
	 * Fires after the sending of a group message inside the group's Group Message page.
	 *
	 * @since BuddyBoss 1.2.9
	 *
	 * @param int $id ID of the group whose members are being displayed.
	 */
	do_action( 'groups_screen_group_messages' );

	/**
	 * Filters the template to load for a single group's message page.
	 *
	 * @since BuddyBoss 1.2.9
	 *
	 * @param string $value Path to a single group's template to load.
	 */
	bp_core_load_template( apply_filters( 'groups_template_group_messages', 'groups/single/home' ) );
}

/**
 * Load the template of the group public message.
 */
function bp_groups_public_message_screen_content() {
	bp_get_template_part( 'groups/single/messages' );
}
