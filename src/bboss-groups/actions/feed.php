<?php
/**
 * Groups: RSS feed action
 *
 * @package BuddyBoss\Group\Actions
 * @since BuddyPress 3.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Load the activity feed for the current group.
 *
 * @since BuddyPress 1.2.0
 *
 * @return false|null False on failure.
 */
function bboss_groups_action_group_feed() {

	// Get current group.
	$group = bboss_groups_get_current_group();

	if ( ! bboss_is_active( 'activity' ) || ! bboss_is_groups_component() || ! $group || ! bboss_is_current_action( 'feed' ) ) {
		return false;
	}

	// If group isn't public or if logged-in user is not a member of the group, do
	// not output the group activity feed.
	if ( ! bboss_group_is_visible( $group ) ) {
		return false;
	}

	// Set up the feed.
	bboss()->activity->feed = new BBoss_Activity_Feed(
		array(
			'id'            => 'group',

			/* translators: Group activity RSS title - "[Site Name] | [Group Name] | Activity" */
			'title'         => sprintf( __( '%1$s | %2$s | Activity', 'buddyboss' ), bboss_get_site_name(), bboss_get_current_group_name() ),

			'link'          => bboss_get_group_permalink( $group ),
			/* translators: %s: group name. */
			'description'   => sprintf( __( 'Activity feed for the group, %s.', 'buddyboss' ), bboss_get_current_group_name() ),
			'activity_args' => array(
				'object'           => bboss()->groups->id,
				'primary_id'       => bboss_get_current_group_id(),
				'display_comments' => 'threaded',
			),
		)
	);
}
add_action( 'bp_actions', 'groups_action_group_feed' );

// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename names of this file's symbols (decision 0013): declared
// here, guarded on the renamed declaration, so each old name exists exactly when the new one does.
if ( function_exists( 'bboss_groups_action_group_feed' ) && ! function_exists( 'groups_action_group_feed' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_groups_action_group_feed()}.
	 *
	 * @deprecated Use bboss_groups_action_group_feed() instead.
	 */
	function groups_action_group_feed() {
		return bboss_groups_action_group_feed();
	}
}
// @bbwt-com-end compat-layer
