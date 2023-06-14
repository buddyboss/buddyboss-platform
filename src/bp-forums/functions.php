<?php

/**
 * Main Forums BuddyBoss Class
 *
 * @package BuddyBoss\Forums
 * @todo    maybe move to BuddyBoss Forums once bbPress 1.1 can be removed
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/** BuddyBoss Helpers ********************************************************/

/**
 * Return Forums' component name/ID ('forums' by default)
 *
 * This is used primarily for Notifications integration.
 *
 * @since bbPress (r5232)
 * @return string
 */
function bbp_get_component_name() {

	// Use existing ID.
	if ( ! empty( bbpress()->extend->buddypress->id ) ) {
		$retval = bbpress()->extend->buddypress->id;

		// Use default.
	} else {
		$retval = 'forums';
	}

	return apply_filters( 'bbp_get_component_name', $retval );
}

/**
 * Filter the current Forums user ID with the current BuddyBoss user ID
 *
 * @since bbPress (r3552)
 *
 * @param int  $user_id
 * @param bool $displayed_user_fallback
 * @param bool $current_user_fallback
 *
 * @return int User ID
 */
function bbp_filter_user_id( $user_id = 0, $displayed_user_fallback = true, $current_user_fallback = false ) {

	// Define local variable.
	$bbp_user_id = 0;

	// Get possible user ID's.
	$did = bp_displayed_user_id();
	$lid = bp_loggedin_user_id();

	// Easy empty checking.
	if ( ! empty( $user_id ) && is_numeric( $user_id ) ) {
		$bbp_user_id = $user_id;
		// Currently viewing or editing a user.
	} elseif ( ( true === $displayed_user_fallback ) && ! empty( $did ) ) {
		$bbp_user_id = $did;
		// Maybe fallback on the current_user ID.
	} elseif ( ( true === $current_user_fallback ) && ! empty( $lid ) ) {
		$bbp_user_id = $lid;
	}

	return $bbp_user_id;
}

add_filter( 'bbp_get_user_id', 'bbp_filter_user_id', 10, 3 );

/**
 * Filter the Forums is_single_user function with BuddyBoss equivalent
 *
 * @since bbPress (r3552)
 *
 * @param bool $is Optional. Default false
 *
 * @return bool True if viewing single user, false if not
 */
function bbp_filter_is_single_user( $is = false ) {
	if ( ! empty( $is ) ) {
		return $is;
	}

	return bp_is_user();
}

add_filter( 'bbp_is_single_user', 'bbp_filter_is_single_user', 10, 1 );

/**
 * Filter the Forums is_user_home function with BuddyBoss equivalent
 *
 * @since bbPress (r3552)
 *
 * @param bool $is Optional. Default false
 *
 * @return bool True if viewing single user, false if not
 */
function bbp_filter_is_user_home( $is = false ) {
	if ( ! empty( $is ) ) {
		return $is;
	}

	return bp_is_my_profile();
}

add_filter( 'bbp_is_user_home', 'bbp_filter_is_user_home', 10, 1 );

/**
 * Add the topic title to the <title> if viewing a single group forum topic
 *
 * @since bbPress (r5161)
 *
 * @param string $old_title (Not used)
 * @param string $sep       The separator to use
 *
 * @param string $new_title The title to filter
 *
 * @return string The possibly modified title
 */
function bbp_filter_modify_page_title( $new_title = '', $old_title = '', $sep = '' ) {

	// Only filter if group forums are active.
	if ( bbp_is_group_forums_active() ) {

		// Only filter for single group forum topics.
		if ( bp_is_group_forum_topic() || bp_is_group_forum_topic_edit() ) {

			// Get the topic.
			$topic = get_posts(
				array(
					'name'                   => bp_action_variable( 1 ),
					'post_status'            => 'publish',
					'post_type'              => bbp_get_topic_post_type(),
					'numberposts'            => 1,
					'update_post_meta_cache' => false,
					'update_post_term_cache' => false,
				)
			);

			// Add the topic title to the <title>.
			$new_title .= bbp_get_topic_title( $topic[0]->ID ) . ' ' . $sep . ' ';
		}
	}

	// Return the title.
	return $new_title;
}

add_action( 'bp_modify_page_title', 'bbp_filter_modify_page_title', 10, 3 );

/** BuddyBoss Screens ********************************************************/

/**
 * Hook Forums topics template into plugins template
 *
 * @since bbPress (r3552)
 *
 * @uses  add_action() To add the content hook
 * @uses  bp_core_load_template() To load the plugins template
 */
function bbp_member_forums_screen_topics() {
	add_action( 'bp_template_content', 'bbp_member_forums_topics_content' );
	bp_core_load_template( apply_filters( 'bbp_member_forums_screen_topics', 'members/single/plugins' ) );
}

/**
 * Hook Forums replies template into plugins template
 *
 * @since bbPress (r3552)
 *
 * @uses  add_action() To add the content hook
 * @uses  bp_core_load_template() To load the plugins template
 */
function bbp_member_forums_screen_replies() {
	add_action( 'bp_template_content', 'bbp_member_forums_replies_content' );
	bp_core_load_template( apply_filters( 'bbp_member_forums_screen_replies', 'members/single/plugins' ) );
}

/**
 * Hook Forums favorites template into plugins template
 *
 * @since bbPress (r3552)
 *
 * @uses  add_action() To add the content hook
 * @uses  bp_core_load_template() To load the plugins template
 */
function bbp_member_forums_screen_favorites() {
	add_action( 'bp_template_content', 'bbp_member_forums_favorites_content' );
	bp_core_load_template( apply_filters( 'bbp_member_forums_screen_favorites', 'members/single/plugins' ) );
}

/**
 * Hook Forums subscriptions template into plugins template
 *
 * @since bbPress (r3552)
 *
 * @uses  add_action() To add the content hook
 * @uses  bp_core_load_template() To load the plugins template
 */
function bbp_member_forums_screen_subscriptions() {
	add_action( 'bp_template_content', 'bbp_member_forums_subscriptions_content' );
	bp_core_load_template( apply_filters( 'bbp_member_forums_screen_subscriptions', 'members/single/plugins' ) );
}

/** BuddyBoss Templates ******************************************************/

/**
 * Get the topics created template part
 *
 * @since bbPress (r3552)
 *
 * @uses  bbp_get_template_part()s
 */
function bbp_member_forums_topics_content() {
	?>
	<div id="bbpress-forums">
		<?php bbp_get_template_part( 'user', 'topics-created' ); ?>
	</div>
	<?php
}

/**
 * Get the topics replied to template part
 *
 * @since bbPress (r3552)
 *
 * @uses  bbp_get_template_part()
 */
function bbp_member_forums_replies_content() {
	?>
	<div id="bbpress-forums">
		<?php bbp_get_template_part( 'user', 'replies-created' ); ?>
	</div>
	<?php
}

/**
 * Get the topics favorited template part
 *
 * @since bbPress (r3552)
 *
 * @uses  bbp_get_template_part()
 */
function bbp_member_forums_favorites_content() {
	?>
	<div id="bbpress-forums">
		<?php bbp_get_template_part( 'user', 'favorites' ); ?>
	</div>
	<?php
}

/**
 * Get the topics subscribed template part
 *
 * @since bbPress (r3552)
 *
 * @uses  bbp_get_template_part()
 */
function bbp_member_forums_subscriptions_content() {
	?>
	<div id="bbpress-forums">
		<?php bbp_get_template_part( 'user', 'subscriptions' ); ?>
	</div>
	<?php
}

/** Forum/Group Sync **********************************************************/

/**
 * These functions are used to keep the many-to-many relationships between
 * groups and forums synchronized. Each forum and group stores ponters to each
 * other in their respective meta. This way if a group or forum is deleted
 * their associattions can be updated without much effort.
 */

/**
 * Get forum ID's for a group
 *
 * @since bbPress (r3653)
 *
 * @param type $group_id
 */
function bbp_get_group_forum_ids( $group_id = 0 ) {

	// Assume no forums.
	$forum_ids = array();

	// Use current group if none is set.
	if ( empty( $group_id ) ) {
		$group_id = bp_get_current_group_id();
	}

	// Get the forums.
	if ( ! empty( $group_id ) ) {
		$forum_ids = groups_get_groupmeta( $group_id, 'forum_id' );
	}

	// Make sure result is an array.
	if ( ! is_array( $forum_ids ) ) {
		$forum_ids = (array) $forum_ids;
	}

	// Trim out any empty array items.
	$forum_ids = array_filter( $forum_ids );

	return (array) apply_filters( 'bbp_get_group_forum_ids', $forum_ids, $group_id );
}

/**
 * Get group ID's for a forum
 *
 * @since bbPress (r3653)
 *
 * @param type $forum_id
 */
function bbp_get_forum_group_ids( $forum_id = 0 ) {

	// Assume no forums.
	$group_ids = array();

	// Use current group if none is set.
	if ( empty( $forum_id ) ) {
		$forum_id = bbp_get_forum_id();
	}

	// Get the forums.
	if ( ! empty( $forum_id ) ) {
		$group_ids = get_post_meta( $forum_id, '_bbp_group_ids', true );
	}

	// Make sure result is an array.
	if ( ! is_array( $group_ids ) ) {
		$group_ids = (array) $group_ids;
	}

	// Trim out any empty array items.
	$group_ids = array_filter( $group_ids );

	return (array) apply_filters( 'bbp_get_forum_group_ids', $group_ids, $forum_id );
}

/**
 * Get forum ID's for a group
 *
 * @since bbPress (r3653)
 *
 * @param type $group_id
 */
function bbp_update_group_forum_ids( $group_id = 0, $forum_ids = array() ) {

	// Use current group if none is set.
	if ( empty( $group_id ) ) {
		$group_id = bp_get_current_group_id();
	}

	// Trim out any empties.
	$forum_ids = array_filter( $forum_ids );

	// Get the forums.
	return groups_update_groupmeta( $group_id, 'forum_id', $forum_ids );
}

/**
 * Update group ID's for a forum
 *
 * @since bbPress (r3653)
 *
 * @param type $forum_id
 */
function bbp_update_forum_group_ids( $forum_id = 0, $group_ids = array() ) {
	$forum_id = bbp_get_forum_id( $forum_id );

	// Trim out any empties.
	$group_ids = array_filter( $group_ids );

	// Get the forums.
	return update_post_meta( $forum_id, '_bbp_group_ids', $group_ids );
}

/**
 * Add a group to a forum
 *
 * @since bbPress (r3653)
 *
 * @param type $group_id
 */
function bbp_add_group_id_to_forum( $forum_id = 0, $group_id = 0 ) {

	// Validate forum_id.
	$forum_id = bbp_get_forum_id( $forum_id );

	// Use current group if none is set.
	if ( empty( $group_id ) ) {
		$group_id = bp_get_current_group_id();
	}

	// Get current group IDs.
	$group_ids = bbp_get_forum_group_ids( $forum_id );

	// Maybe update the groups forums.
	if ( ! in_array( $group_id, $group_ids ) ) {
		$group_ids[] = $group_id;

		return bbp_update_forum_group_ids( $forum_id, $group_ids );
	}
}

/**
 * Remove a forum from a group
 *
 * @since bbPress (r3653)
 *
 * @param type $group_id
 */
function bbp_add_forum_id_to_group( $group_id = 0, $forum_id = 0 ) {

	// Validate forum_id.
	$forum_id = bbp_get_forum_id( $forum_id );

	// Use current group if none is set.
	if ( empty( $group_id ) ) {
		$group_id = bp_get_current_group_id();
	}

	// Get current group IDs.
	$forum_ids = bbp_get_group_forum_ids( $group_id );

	// Maybe update the groups forums.
	if ( ! in_array( $forum_id, $forum_ids ) ) {
		$forum_ids[] = $forum_id;

		return bbp_update_group_forum_ids( $group_id, $forum_ids );
	}
}

/**
 * Remove a group from a forum
 *
 * @since bbPress (r3653)
 *
 * @param type $group_id
 */
function bbp_remove_group_id_from_forum( $forum_id = 0, $group_id = 0 ) {

	// Validate forum_id.
	$forum_id = bbp_get_forum_id( $forum_id );

	// Use current group if none is set.
	if ( empty( $group_id ) ) {
		$group_id = bp_get_current_group_id();
	}

	// Get current group IDs.
	$group_ids = bbp_get_forum_group_ids( $forum_id );

	// Maybe update the groups forums.
	if ( in_array( $group_id, $group_ids ) ) {
		$group_ids = array_diff( array_values( $group_ids ), (array) $group_id );

		return bbp_update_forum_group_ids( $forum_id, $group_ids );
	}
}

/**
 * Remove a forum from a group
 *
 * @since bbPress (r3653)
 *
 * @param type $group_id
 */
function bbp_remove_forum_id_from_group( $group_id = 0, $forum_id = 0 ) {

	// Validate forum_id.
	$forum_id = bbp_get_forum_id( $forum_id );

	// Use current group if none is set.
	if ( empty( $group_id ) ) {
		$group_id = bp_get_current_group_id();
	}

	// Get current group IDs.
	$forum_ids = bbp_get_group_forum_ids( $group_id );

	// Maybe update the groups forums.
	if ( in_array( $forum_id, $forum_ids ) ) {
		$forum_ids = array_diff( array_values( $forum_ids ), (array) $forum_id );

		return bbp_update_group_forum_ids( $group_id, $forum_ids );
	}
}

/**
 * Remove a group from aall forums
 *
 * @since bbPress (r3653)
 *
 * @param type $group_id
 */
function bbp_remove_group_id_from_all_forums( $group_id = 0 ) {

	// Use current group if none is set.
	if ( empty( $group_id ) ) {
		$group_id = bp_get_current_group_id();
	}

	// Get current group IDs.
	$forum_ids = bbp_get_group_forum_ids( $group_id );

	// Loop through forums and remove this group from each one.
	foreach ( (array) $forum_ids as $forum_id ) {
		bbp_remove_group_id_from_forum( $group_id, $forum_id );
	}
}

/**
 * Remove a forum from all groups
 *
 * @since bbPress (r3653)
 *
 * @param type $forum_id
 */
function bbp_remove_forum_id_from_all_groups( $forum_id = 0 ) {

	// Validate.
	$forum_id  = bbp_get_forum_id( $forum_id );
	$group_ids = bbp_get_forum_group_ids( $forum_id );

	// Loop through groups and remove this forum from each one.
	foreach ( (array) $group_ids as $group_id ) {
		bbp_remove_forum_id_from_group( $forum_id, $group_id );
	}
}

/**
 * Return true if a forum is a group forum
 *
 * @since bbPress (r4571)
 *
 * @param int $forum_id
 *
 * @return bool True if it is a group forum, false if not
 * @uses  bbp_get_forum_group_ids() To get the forum's group ids
 * @uses  apply_filters() Calls 'bbp_forum_is_group_forum' with the forum id
 * @uses  bbp_get_forum_id() To get the forum id
 */
function bbp_is_forum_group_forum( $forum_id = 0 ) {

	// Validate.
	$forum_id = bbp_get_forum_id( $forum_id );

	// Check for group ID's.
	$group_ids = bbp_get_forum_group_ids( $forum_id );

	// Check if the forum has groups.
	$retval = (bool) ! empty( $group_ids );

	return (bool) apply_filters( 'bbp_is_forum_group_forum', $retval, $forum_id, $group_ids );
}

/*** Group Member Status ******************************************************/

/**
 * Is the current user an admin of the current group
 *
 * @since bbPress (r4632)
 *
 * @return bool If current user is an admin of the current group
 * @uses  bp_is_group()
 * @uses  bbpress()
 * @uses  get_current_user_id()
 * @uses  bp_get_current_group_id()
 * @uses  groups_is_user_admin()
 * @uses  is_user_logged_in()
 */
function bbp_group_is_admin() {

	// Bail if user is not logged in or not looking at a group.
	if ( ! is_user_logged_in() || ! bp_is_group() ) {
		return false;
	}

	$bbp = bbpress();

	// Set the global if not set.
	if ( ! isset( $bbp->current_user->is_group_admin ) ) {
		$bbp->current_user->is_group_admin = groups_is_user_admin( get_current_user_id(), bp_get_current_group_id() );
	}

	// Return the value.
	return (bool) $bbp->current_user->is_group_admin;
}

/**
 * Is the current user a moderator of the current group
 *
 * @since bbPress (r4632)
 *
 * @return bool If current user is a moderator of the current group
 * @uses  bp_is_group()
 * @uses  bbpress()
 * @uses  get_current_user_id()
 * @uses  bp_get_current_group_id()
 * @uses  groups_is_user_admin()
 * @uses  is_user_logged_in()
 */
function bbp_group_is_mod() {

	// Bail if user is not logged in or not looking at a group.
	if ( ! is_user_logged_in() || ! bp_is_group() ) {
		return false;
	}

	$bbp = bbpress();

	// Set the global if not set.
	if ( ! isset( $bbp->current_user->is_group_mod ) ) {
		$bbp->current_user->is_group_mod = groups_is_user_mod( get_current_user_id(), bp_get_current_group_id() );
	}

	// Return the value.
	return (bool) $bbp->current_user->is_group_mod;
}

/**
 * Is the current user a member of the current group
 *
 * @since bbPress (r4632)
 *
 * @return bool If current user is a member of the current group
 * @uses  bp_is_group()
 * @uses  bbpress()
 * @uses  get_current_user_id()
 * @uses  bp_get_current_group_id()
 * @uses  groups_is_user_admin()
 * @uses  is_user_logged_in()
 */
function bbp_group_is_member() {

	// Bail if user is not logged in or not looking at a group.
	if ( ! is_user_logged_in() || ! bp_is_group() ) {
		return false;
	}

	$bbp = bbpress();

	// Set the global if not set.
	if ( ! isset( $bbp->current_user->is_group_member ) ) {
		$bbp->current_user->is_group_member = groups_is_user_member( get_current_user_id(), bp_get_current_group_id() );
	}

	// Return the value.
	return (bool) $bbp->current_user->is_group_member;
}

/**
 * Is the current user banned from the current group
 *
 * @since bbPress (r4632)
 *
 * @return bool If current user is banned from the current group
 * @uses  bp_is_group()
 * @uses  bbpress()
 * @uses  get_current_user_id()
 * @uses  bp_get_current_group_id()
 * @uses  groups_is_user_admin()
 * @uses  is_user_logged_in()
 */
function bbp_group_is_banned() {

	// Bail if user is not logged in or not looking at a group.
	if ( ! is_user_logged_in() || ! bp_is_group() ) {
		return false;
	}

	$bbp = bbpress();

	// Set the global if not set.
	if ( ! isset( $bbp->current_user->is_group_banned ) ) {
		$bbp->current_user->is_group_banned = groups_is_user_banned( get_current_user_id(), bp_get_current_group_id() );
	}

	// Return the value.
	return (bool) $bbp->current_user->is_group_banned;
}

/**
 * Is the current user the creator of the current group
 *
 * @since bbPress (r4632)
 *
 * @return bool If current user the creator of the current group
 * @uses  bp_is_group()
 * @uses  bbpress()
 * @uses  get_current_user_id()
 * @uses  bp_get_current_group_id()
 * @uses  groups_is_user_admin()
 * @uses  is_user_logged_in()
 */
function bbp_group_is_creator() {

	// Bail if user is not logged in or not looking at a group.
	if ( ! is_user_logged_in() || ! bp_is_group() ) {
		return false;
	}

	$bbp = bbpress();

	// Set the global if not set.
	if ( ! isset( $bbp->current_user->is_group_creator ) ) {
		$bbp->current_user->is_group_creator = groups_is_user_creator( get_current_user_id(), bp_get_current_group_id() );
	}

	// Return the value.
	return (bool) $bbp->current_user->is_group_creator;
}

/**
 * Enables the TinyMce in Forum Topic and reply content.
 *
 * @since BuddyBoss 1.0.0
 *
 * @param array $args
 *
 * @return array
 */
function bbp_forum_topics_reply_enable_tinymce_editor( $args = array() ) {

	// Filter for removing the visual/text tabs hide..
	add_filter( 'wp_editor_settings', 'bbp_forum_topics_reply_tinymce_settings' );

	// Enable the tinyMce..
	$args['tinymce'] = true;
	$args['tinymce'] = array(
		'toolbar1' => 'bold, italic, bullist, numlist, blockquote, link',
	);

	return $args;
}

add_filter( 'bbp_after_get_the_content_parse_args', 'bbp_forum_topics_reply_enable_tinymce_editor' );

/**
 * Enable TinyMce Quicktags in Forum Topic and reply content.
 *
 * @since BuddyBoss 1.0.0
 *
 * @param $settings
 *
 * @return mixed
 */
function bbp_forum_topics_reply_tinymce_settings( $settings ) {

	// visual/text tabs hide..
	$settings['quicktags'] = false;

	return $settings;
}

/**
 * Update Forum status depending on the group status
 *
 * @since BuddyBoss 1.1.5
 */
function bbp_forum_update_forum_status_when_group_updates( $group_id ) {

	if ( $group_id ) {
		$forum_ids = array_values( bbp_get_group_forum_ids( $group_id ) );
		if ( ! empty( $forum_ids ) ) {
			// Get the group.
			$group = groups_get_group( absint( $group_id ) );

			foreach ( $forum_ids as $forum_id ) {
				if ( ! empty( $forum_id ) ) {

					// Set the default forum status.
					switch ( $group->status ) {
						case 'hidden':
							$status = bbp_get_hidden_status_id();
							break;
						case 'private':
							$status = bbp_get_private_status_id();
							break;
						case 'public':
						default:
							$status = bbp_get_public_status_id();
							break;
					}

					wp_update_post(
						array(
							'ID'          => $forum_id,
							'post_status' => $status,
						)
					);

					$child_forums = bb_get_all_nested_subforums( $forum_id );
					if ( $child_forums ) {
						foreach ( $child_forums as $child_forum_id ) {
							if ( get_post_status( $child_forum_id ) !== $status ) {
								wp_update_post(
									array(
										'ID'          => $child_forum_id,
										'post_status' => $status,
									)
								);
							}
						}
					}
				}
			}
		}

		// Update Forums' internal private and forum ID variables
		bbp_repair_forum_visibility();

	}
}

add_action( 'groups_group_settings_edited', 'bbp_forum_update_forum_status_when_group_updates', 100 );
add_action( 'bp_group_admin_after_edit_screen_save', 'bbp_forum_update_forum_status_when_group_updates', 10 );

/**
 * Get Sub Forum's group id,
 * if not associated with any group then it searches for the parent forums to fetch group associated
 * otherwise returns false
 *
 * @since BuddyBoss 1.1.9
 *
 * @param $forum_id
 *
 * @return bool|int|mixed
 */
function bbp_forum_recursive_group_id( $forum_id ) {

	if ( empty( $forum_id ) ) {
		return false;
	}

	// initialize a few things.
	$group_id          = 0;
	$found_group_forum = false;
	$reached_the_top   = false;

	// This loop works our way up to the top of the topic->sub-forum->parent-forum hierarchy..
	// We will stop climbing when we find a forum_id that is also the id of a group's forum..
	// When we find that, we've found the group, and we can stop looking..
	// Or if we get to the top of the hierarchy, we'll bail out of the loop, never having found a forum.
	// that is associated with a group..
	while ( ! $found_group_forum && ! $reached_the_top ) {
		$forum_group_ids = bbp_get_forum_group_ids( $forum_id );
		if ( ! empty( $forum_group_ids ) ) {
			// We've found the forum_id that corresponds to the group's forum.
			$found_group_forum = true;
			$group_id          = $forum_group_ids[0];
		} else {
			$current_post = get_post( $forum_id );
			if ( $current_post->post_parent ) {
				// $post->post_parent will be the ID of the parent, not an object.
				$forum_id = $current_post->post_parent;
			} else {
				// We've reached the top of the hierarchy.
				$reached_the_top = true;
			}
		}
	}

	if ( $group_id ) {
		return $group_id;
	}

	return false;
}

add_action( 'wp_ajax_search_tags', 'bbp_forum_topic_reply_ajax_form_search_tags' );

/**
 * Search the tags that already added on forums previously and give the suggestions list.
 *
 * @since BuddyBoss 1.1.9
 */
function bbp_forum_topic_reply_ajax_form_search_tags() {

	$response = array(
		'feedback' => sprintf(
			'<div class="bp-feedback error bp-ajax-message"><span class="bp-icon" aria-hidden="true"></span><p>%s</p></div>',
			esc_html__( 'There was a problem performing this action. Please try again.', 'buddyboss' )
		),
	);

	// Bail if not a POST action..
	if ( ! bp_is_get_request() ) {
		wp_send_json_error( $response );
	}

	if ( empty( $_GET['_wpnonce'] ) ) {
		wp_send_json_error( $response );
	}

	// Use default nonce.
	$nonce = $_GET['_wpnonce'];
	$check = 'search_tag';

	// Nonce check!.
	if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, $check ) ) {
		wp_send_json_error( $response );
	}

	if ( empty( $_GET['term'] ) ) {
		wp_send_json_error( $response );
	}

	// WP_Term_Query arguments.
	$args = array(
		'taxonomy'   => array( 'topic-tag' ),
		'search'     => $_GET['term'],
		'hide_empty' => false,
	);

	// The Term Query.
	$term_query = new WP_Term_Query( $args );

	$tags = array();

	// The Loop.
	if ( ! empty( $term_query ) && ! is_wp_error( $term_query ) ) {
		$tags = $term_query->terms;
	}

	if ( empty( $tags ) ) {
		$tags = array();
	}

	wp_send_json_success(
		array(
			'results' => array_map(
				function ( $result ) {
					return array(
						'id'   => $result->slug,
						'text' => $result->name,
					);
				},
				$tags
			),
		)
	);
}

/**
 * Email queue get record
 *
 * @since BuddyBoss 1.7.8
 *
 * @param array  $terms          terms name.
 * @param int    $topic_id       topic id.
 * @param string $taxonomy       taxonomy name.
 * @param string $existing_terms comma separated existing terms name.
 *
 * @return array|false|WP_Error Array of term taxonomy IDs of affected terms. WP_Error or false on failure.
 */
function bb_add_topic_tags( $terms, $topic_id, $taxonomy, $existing_terms = '' ) {
	if ( ! empty( $existing_terms ) ) {
		$existing_terms = explode( ',', $existing_terms );
		$existing_terms = array_map(
			function ( $single ) {
				return trim( $single );
			},
			$existing_terms
		);

		$deleted_terms = array_diff( $existing_terms, $terms );

		if ( ! empty( $deleted_terms ) ) {
			$deleted_terms = array_map(
				function ( $single ) use ( $taxonomy ) {
					$get_term = get_term_by( 'name', $single, $taxonomy );
					if ( ! empty( $get_term->slug ) ) {
						return $get_term->slug;
					}
				},
				$deleted_terms
			);
			wp_remove_object_terms( $topic_id, $deleted_terms, $taxonomy );
		}
	}

	// update tags.
	if ( ! empty( $terms ) ) {
		$term_ids = array();

		foreach ( $terms as $term_name ) {
			$args['name']     = $term_name;
			$args['slug']     = $term_name;
			$args['taxonomy'] = $taxonomy;

			$term_info = get_term_by( 'name', $term_name, $taxonomy, ARRAY_A );
			if ( ! $term_info ) {
				$term_info = wp_insert_term( $term_name, $taxonomy, $args );
			}
			if ( ! empty( $term_info ) && ! is_wp_error( $term_info ) ) {
				$term_ids[] = $term_info['term_id'];
			}
		}

		if ( ! empty( $term_ids ) ) {
			$terms = wp_set_post_terms( $topic_id, $term_ids, $taxonomy, true );
		}
	}

	return $terms;
}

/**
 * Localize the strings needed for the Forum/Topic UI
 *
 * @since BuddyBoss 2.0.4
 *
 * @param array $params Associative array containing the JS Strings needed by scripts.
 *
 * @return array The same array with specific strings for the Forum/Topic UI if needed.
 */
function bb_nouveau_forum_localize_scripts( $params = array() ) {

	if ( function_exists( 'bp_is_active' ) && ! bp_is_active( 'forums' ) ) {
		return $params;
	}

	$user_id    = bp_loggedin_user_id();
	$draft_data = get_user_meta( $user_id, 'bb_user_topic_reply_draft', true );

	$params['forums'] = array(
		'params'  => array(
			'bb_current_user_id' => $user_id,
			'link_preview'       => bbp_use_autoembed() ? true : false,
		),
		'nonces'  => array(
			'post_topic_reply_draft' => wp_create_nonce( 'post_topic_reply_draft_data' ),
		),
		'strings' => array(
			'discardButton' => esc_html__( 'Discard Draft', 'buddyboss' ),
		),
	);

	$params['forums']['draft'] = array();
	if ( ! empty( $draft_data ) ) {
		foreach ( $draft_data as $data ) {

			if ( isset( $data['data_key'] ) ) {
				$params['forums']['draft'][ $data['data_key'] ] = $data;
			}
		}
	}

	return $params;
}

add_filter( 'bp_core_get_js_strings', 'bb_nouveau_forum_localize_scripts', 10, 1 );

/**
 * Update the forum/topic subscription when topic and forum merge/split/update parent.
 *
 * @since BuddyBoss 2.2.6
 *
 * @param int     $post_id Post ID.
 * @param WP_Post $post    Post object.
 */
function bb_subscription_update_secondary_item( $post_id, $post ) {
	if ( empty( $post_id ) || empty( $post ) ) {
		return;
	}

	// Check the post type.
	if ( empty( $post->post_type ) || ! in_array( $post->post_type, array( bbp_get_forum_post_type(), bbp_get_topic_post_type() ), true ) ) {
		return;
	}

	$subscription_type = '';
	if ( bbp_get_forum_post_type() === $post->post_type ) {
		$subscription_type = 'forum';
	} elseif ( bbp_get_topic_post_type() === $post->post_type ) {
		$subscription_type = 'topic';
	}

	if ( empty( $subscription_type ) ) {
		return;
	}

	// Update the secondary item ID.
	BB_Subscriptions::update_secondary_item_id(
		array(
			'type'              => $subscription_type,
			'item_id'           => $post->ID,
			'secondary_item_id' => $post->post_parent,
		)
	);
}

add_action( 'edit_post', 'bb_subscription_update_secondary_item', 999, 2 );

/**
 * Return true if a forum is a group forum.
 *
 * @since BuddyBoss 2.2.8
 *
 * @param int $forum_id Forum ID.
 *
 * @return bool True if it is a group forum, false if not.
 */
function bb_is_forum_group_forum( $forum_id = 0 ) {

	// Validate.
	$forum_id = bbp_get_forum_id( $forum_id );

	$retval = function_exists( 'bbp_is_group_forums_active' ) && function_exists( 'bbp_is_forum_group_forum' ) && bbp_is_group_forums_active() && bbp_is_forum_group_forum( $forum_id );

	return (bool) apply_filters( 'bb_is_forum_group_forum', $retval, $forum_id );
}

/**
 * AJAX endpoint for link preview URL parser.
 *
 * @since BuddyBoss 2.3.60
 */
function bb_forums_link_preview_parse_url() {
	// Get URL.
	$url = isset( $_POST['url'] ) ? $_POST['url'] : ''; // phpcs:ignore

	// Check if URL is validated.
	if ( empty( $url ) || ! filter_var( $url, FILTER_VALIDATE_URL ) ) {
		wp_send_json( array( 'error' => __( 'URL is not valid.', 'buddyboss' ) ) );
	}

	// Get URL parsed data.
	$parse_url_data = bp_core_parse_url( $url );

	// If empty data then send error.
	if ( empty( $parse_url_data ) ) {
		wp_send_json( array( 'error' => esc_html__( 'There was a problem generating a link preview.', 'buddyboss' ) ) );
	}

	// send json success.
	wp_send_json( $parse_url_data );
}

add_action( 'wp_ajax_bb_forums_parse_url', 'bb_forums_link_preview_parse_url' );
if ( bbp_allow_anonymous() ) {
	add_action( 'wp_ajax_nopriv_bb_forums_parse_url', 'bb_forums_link_preview_parse_url' );
}

