<?php
/**
 * BuddyBoss Groups Filters.
 *
 * @package BuddyBoss\Groups\Filters
 * @since BuddyPress 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

// Filter BuddyPress template locations.
add_filter( 'bp_groups_get_directory_template', 'bp_add_template_locations' );
add_filter( 'bp_get_single_group_template', 'bp_add_template_locations' );

/* Apply WordPress defined filters */
add_filter( 'bp_get_group_description', 'wptexturize' );
add_filter( 'bp_get_group_description_excerpt', 'wptexturize' );
add_filter( 'bp_get_group_name', 'wptexturize' );

add_filter( 'bp_get_group_description', 'convert_smilies' );
add_filter( 'bp_get_group_description_excerpt', 'convert_smilies' );

add_filter( 'bp_get_group_description', 'convert_chars' );
add_filter( 'bp_get_group_description_excerpt', 'convert_chars' );
add_filter( 'bp_get_group_name', 'convert_chars' );

add_filter( 'bp_get_group_description', 'wpautop' );
add_filter( 'bp_get_group_description_excerpt', 'wpautop' );

add_filter( 'bp_get_group_description', 'make_clickable', 9 );
add_filter( 'bp_get_group_description_excerpt', 'make_clickable', 9 );

add_filter( 'bp_get_group_name', 'wp_filter_kses', 1 );
add_filter( 'bp_get_group_permalink', 'wp_filter_kses', 1 );
add_filter( 'bp_get_group_description', 'bp_groups_filter_kses', 1 );
add_filter( 'bp_get_new_group_description', 'bp_groups_filter_kses', 1 );

add_filter( 'bp_get_group_description_excerpt', 'wp_filter_kses', 1 );
add_filter( 'groups_group_name_before_save', 'wp_filter_kses', 1 );
add_filter( 'groups_group_description_before_save', 'bp_groups_filter_kses', 1 );

add_filter( 'bp_get_group_description', 'stripslashes' );
add_filter( 'bp_get_group_description_excerpt', 'stripslashes' );
add_filter( 'bp_get_group_name', 'stripslashes' );
add_filter( 'bp_get_group_member_name', 'stripslashes' );
add_filter( 'bp_get_group_member_link', 'stripslashes' );

add_filter( 'groups_group_name_before_save', 'force_balance_tags' );
add_filter( 'groups_group_description_before_save', 'force_balance_tags' );

// Trim trailing spaces from name and description when saving.
add_filter( 'groups_group_name_before_save', 'trim' );
add_filter( 'groups_group_description_before_save', 'trim' );

// Support emoji.
if ( function_exists( 'wp_encode_emoji' ) ) {
	add_filter( 'groups_group_description_before_save', 'wp_encode_emoji' );
}

// Escape output of new group creation details.
add_filter( 'bp_get_new_group_name', 'esc_attr' );
add_filter( 'bp_get_new_group_description', 'esc_textarea' );

// Format numerical output.
add_filter( 'bp_get_total_group_count', 'bp_core_number_format' );
add_filter( 'bp_get_group_total_for_member', 'bp_core_number_format' );
add_filter( 'bp_get_group_total_members', 'bp_core_number_format' );

// Activity component integration.
add_filter( 'bp_activity_at_name_do_notifications', 'bp_groups_disable_at_mention_notification_for_non_public_groups', 10, 4 );
add_filter( 'bbp_forums_at_name_do_notifications', 'bp_groups_disable_at_mention_forums_notification_for_non_public_groups', 10, 4 );

// Exclude Forums if group type hide.
add_filter( 'bbp_after_has_forums_parse_args', 'bp_groups_exclude_forums_by_group_type_args' );
// Exclude Forums if group type hide.
add_filter( 'bbp_after_has_topics_parse_args', 'bp_groups_exclude_forums_topics_by_group_type_args' );

// media scope filter.
add_filter( 'bp_media_set_groups_scope_args', 'bp_groups_filter_media_scope', 10, 2 );
add_filter( 'bp_video_set_groups_scope_args', 'bp_groups_filter_video_scope', 10, 2 );
add_filter( 'bp_document_set_document_groups_scope_args', 'bp_groups_filter_document_scope', 10, 2 );
add_filter( 'bp_document_set_folder_groups_scope_args', 'bp_groups_filter_folder_scope', 10, 2 );

add_filter( 'bp_get_group_name', 'bb_core_remove_unfiltered_html', 99 );
add_filter( 'bp_get_new_group_name', 'bb_core_remove_unfiltered_html', 99 );
add_filter( 'groups_group_name_before_save', 'bb_core_remove_unfiltered_html', 99 );

// setup backward compatibilty to retrieve the encoded value from db.
add_filter( 'groups_group_name_before_save', 'html_entity_decode' );
add_filter( 'bp_get_group_name', 'html_entity_decode' );
add_filter( 'bp_get_group_description', 'html_entity_decode' );

// Load Group Notifications.
add_action( 'bp_groups_includes', 'bb_load_groups_notifications' );

// Filter group count.
add_filter( 'bp_groups_get_where_count_conditions', 'bb_groups_count_update_where_sql', 10, 2 );

// The user suspends/unsuspends and only when a single group organizer then fire these hooks.
add_action( 'bp_suspend_hide_user', 'bb_group_remove_suspended_user', 99, 1 );
add_action( 'bp_suspend_unhide_user', 'bb_group_add_unsuspended_user', 9, 1 );

add_action( 'bp_before_group_body', 'bb_before_group_body_callback' );
add_action( 'bp_after_group_body', 'bb_after_group_body_callback' );
add_action( 'bp_before_subgroups_loop', 'bb_before_group_body_callback' );
add_action( 'bp_after_subgroups_loop', 'bb_after_group_body_callback' );

add_filter( 'bb_readylaunch_left_sidebar_middle_content', 'bb_readylaunch_middle_content_my_groups', 10, 1 );

/**
 * Filter output of Group Description through WordPress's KSES API.
 *
 * @since BuddyPress 1.1.0
 *
 * @param string $content Content to filter.
 * @return string
 */
function bp_groups_filter_kses( $content = '' ) {

	$allowed_tags = array();
	/**
	 * Note that we don't immediately bail if $content is empty. This is because
	 * WordPress's KSES API calls several other filters that might be relevant
	 * to someone's workflow (like `pre_kses`)
	 */

	// Add our own tags allowed in group descriptions.
	$allowed_tags['a']           = array();
	$allowed_tags['a']['href']   = true;
	$allowed_tags['a']['title']  = true;
	$allowed_tags['a']['class']  = array();
	$allowed_tags['a']['target'] = array();
	$allowed_tags['i']           = array();
	$allowed_tags['b']           = array();
	$allowed_tags['strong']      = array();
	$allowed_tags['em']          = array();
	$allowed_tags['blockquote']  = array();
	$allowed_tags['ol']          = array();
	$allowed_tags['ul']          = array();
	$allowed_tags['li']          = array();
	$allowed_tags['code']        = array();

	/**
	 * Filters the HTML elements allowed for a given context.
	 *
	 * @since BuddyPress 1.2.0
	 *
	 * @param string $allowed_tags Allowed tags, attributes, and/or entities.
	 */
	$tags = apply_filters( 'bp_groups_filter_kses', $allowed_tags );

	// Convert HTML entities to their corresponding characters.
	$content = html_entity_decode( $content, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401 );

	// Return KSES'ed content, allowing the above tags.
	return wp_kses( $content, $tags );
}

/**
 * Should BuddyPress load the mentions scripts and related assets, including results to prime the
 * mentions suggestions?
 *
 * @since BuddyPress 2.2.0
 *
 * @param bool $load_mentions    True to load mentions assets, false otherwise.
 * @param bool $mentions_enabled True if mentions are enabled.
 * @return bool True if mentions scripts should be loaded.
 */
function bp_groups_maybe_load_mentions_scripts( $load_mentions, $mentions_enabled ) {
	if ( ! $mentions_enabled ) {
		return $load_mentions;
	}

	if ( $load_mentions || bp_is_group_activity() ) {
		return true;
	}

	return $load_mentions;
}
add_filter( 'bp_activity_maybe_load_mentions_scripts', 'bp_groups_maybe_load_mentions_scripts', 10, 2 );

/**
 * Disable at-mention notifications for users who are not a member of the non-public group where the activity appears.
 *
 * @since BuddyPress 2.5.0
 *
 * @param bool                 $send      Whether to send the notification.
 * @param array                $usernames Array of all usernames being notified.
 * @param int                  $user_id   ID of the user to be notified.
 * @param BP_Activity_Activity $activity  Activity object.
 * @return bool
 */
function bp_groups_disable_at_mention_notification_for_non_public_groups( $send, $usernames, $user_id, $activity ) {
	// Skip the check for administrators, who can get notifications from non-public groups.
	if ( bp_user_can( $user_id, 'bp_moderate' ) ) {
		return $send;
	}

	if ( 'activity_update' === $activity->type ) {
		$group_id = 'groups' === $activity->component ? $activity->item_id : 0;
	} elseif ( 'activity_comment' === $activity->type ) {
		$comment  = new BP_Activity_Activity( $activity->item_id );
		$group_id = ! empty( $comment->component ) && 'groups' === $comment->component ? $comment->item_id : 0;
	}

	if ( $group_id && ! bp_user_can( $user_id, 'groups_access_group', array( 'group_id' => $group_id ) ) ) {
		$send = false;
	}

	return $send;
}

/**
 * Disable at-mention forum notifications for users who are not a member of the non-public group.
 *
 * @param bool  $send Whether to send the notification.
 * @param array $usernames Array of all usernames being notified.
 * @param int   $user_id ID of the user to be notified.
 * @param int   $forum_id ID of the forum.
 *
 * @return bool
 * @since BuddyBoss 1.2.9
 */
function bp_groups_disable_at_mention_forums_notification_for_non_public_groups( $send, $usernames, $user_id, $forum_id ) {
	// Skip the check for administrators, who can get notifications from non-public groups.
	if ( bp_user_can( $user_id, 'bp_moderate' ) ) {
		return $send;
	}

	// Get group ID's for this forum
	$group_ids = bbp_get_forum_group_ids( $forum_id );

	// Bail if the post isn't associated with a group
	if ( empty( $group_ids ) ) {
		return $send;
	}

	// @todo Multiple group forums/forum groups
	$group_id = $group_ids[0];

	if ( ! bp_user_can( $user_id, 'groups_access_group', array( 'group_id' => $group_id ) ) ) {
		$send = false;
	}

	return $send;
}

/**
 * Filter the bp_user_can value to determine what the user can do
 * with regards to a specific group.
 *
 * @since BuddyPress 3.0.0
 *
 * @param bool   $retval     Whether or not the current user has the capability.
 * @param int    $user_id
 * @param string $capability The capability being checked for.
 * @param int    $site_id    Site ID. Defaults to the BP root blog.
 * @param array  $args       Array of extra arguments passed.
 *
 * @return bool
 */
function bp_groups_user_can_filter( $retval, $user_id, $capability, $site_id, $args ) {
	if ( empty( $args['group_id'] ) ) {
		$group_id = bp_get_current_group_id();
	} else {
		$group_id = (int) $args['group_id'];
	}

	switch ( $capability ) {
		case 'groups_join_group':
			// Return early if the user isn't logged in or the group ID is unknown.
			if ( ! $user_id || ! $group_id ) {
				break;
			}

			// Set to false to begin with.
			$retval = false;

			// The group must allow joining, and the user should not currently be a member.
			$group = groups_get_group( $group_id );
			if ( ( 'public' === bp_get_group_status( $group )
				&& ! groups_is_user_member( $user_id, $group->id )
				&& ! groups_is_user_banned( $user_id, $group->id ) )
				// Site admins can join any group they are not a member of.
				|| ( bp_user_can( $user_id, 'bp_moderate' )
				&& ! groups_is_user_member( $user_id, $group->id ) )
			) {
				$retval = true;
			}
			break;

		case 'groups_request_membership':
			// Return early if the user isn't logged in or the group ID is unknown.
			if ( ! $user_id || ! $group_id ) {
				break;
			}

			// Set to false to begin with.
			$retval = false;

			/*
			* The group must accept membership requests, and the user should not
			* currently be a member or be banned.
			*/
			$group = groups_get_group( $group_id );
			if ( 'private' === bp_get_group_status( $group )
				&& ! groups_is_user_member( $user_id, $group->id )
				&& ! groups_check_for_membership_request( $user_id, $group->id )
				&& ! groups_is_user_banned( $user_id, $group->id )
			) {
				$retval = true;
			}
			break;

		case 'groups_send_invitation':
			// Return early if the user isn't logged in or the group ID is unknown.
			if ( ! $user_id || ! $group_id ) {
				break;
			}

			/*
			* The group must allow invitations, and the user should not
			* currently be a member or be banned from the group.
			*/
			// Users with the 'bp_moderate' cap can always send invitations.
			if ( bp_user_can( $user_id, 'bp_moderate' ) ) {
				$retval = true;
			} else {
				$invite_status = bp_group_get_invite_status( $group_id );

				switch ( $invite_status ) {
					case 'admins':
						if ( groups_is_user_admin( $user_id, $group_id ) ) {
							$retval = true;
						}
						break;

					case 'mods':
						if ( groups_is_user_mod( $user_id, $group_id ) || groups_is_user_admin( $user_id, $group_id ) ) {
							$retval = true;
						}
						break;

					case 'members':
						if ( groups_is_user_member( $user_id, $group_id ) ) {
							$retval = true;
						}
						break;
				}
			}
			break;

		case 'groups_receive_invitation':
			// Return early if the user isn't logged in or the group ID is unknown.
			if ( ! $user_id || ! $group_id ) {
				break;
			}

			// Set to false to begin with.
			$retval = false;

			/*
			* The group must allow invitations, and the user should not
			* currently be a member or be banned from the group.
			*/
			$group = groups_get_group( $group_id );
			if ( ! groups_is_user_member( $user_id, $group->id )
				&& ! groups_is_user_banned( $user_id, $group->id )
			) {
				$retval = true;
			}
			break;

		case 'groups_access_group':
			// Return early if the group ID is unknown.
			if ( ! $group_id ) {
				break;
			}

			$group = groups_get_group( $group_id );

			// If the check is for the logged-in user, use the BP_Groups_Group property.
			if ( $user_id === bp_loggedin_user_id() ) {
				$retval = $group->user_has_access;

				/*
				* If the check is for a specified user who is not the logged-in user
				* run the check manually.
				*/
			} elseif ( 'public' === bp_get_group_status( $group ) || groups_is_user_member( $user_id, $group->id ) ) {
				$retval = true;
			}
			break;

		case 'groups_see_group':
			// Return early if the group ID is unknown.
			if ( ! $group_id ) {
				break;
			}

			$group = groups_get_group( $group_id );

			// If the check is for the logged-in user, use the BP_Groups_Group property.
			if ( $user_id === bp_loggedin_user_id() ) {
				$retval = $group->is_visible;

				/*
				* If the check is for a specified user who is not the logged-in user
				* run the check manually.
				*/
			} elseif ( 'hidden' !== bp_get_group_status( $group ) || groups_is_user_member( $user_id, $group->id ) ) {
				$retval = true;
			}
			break;
	}

	return $retval;

}
add_filter( 'bp_user_can', 'bp_groups_user_can_filter', 10, 5 );

/**
 * Filter the bp_activity_user_can_delete value to allow moderators to delete activities of a group.
 *
 * @since BuddyBoss 1.0.0
 *
 * @param bool                       $can_delete     Whether or not the current user has the capability.
 * @param false|BP_Activity_Activity $activity
 *
 * @return bool
 */
function bp_groups_allow_mods_to_delete_activity( $can_delete, $activity ) {

	// Allow Mods to delete activity of group
	if ( ! $can_delete && is_user_logged_in() && 'groups' === $activity->component ) {
		$group = groups_get_group( $activity->item_id );

		// As per the new logic moderator can delete the activity of all the users. So removed the && ! groups_is_user_admin( $activity->user_id, $activity->item_id ) condition.
		if (
			! empty( $group ) &&
			(
				groups_is_user_mod( get_current_user_id(), $activity->item_id ) ||
				groups_is_user_admin( get_current_user_id(), $activity->item_id )
			) &&
			! groups_is_user_admin( bp_get_activity_user_id(), $activity->item_id )
		) {
			$can_delete = true;
		}
	}

	return $can_delete;
}
add_filter( 'bp_activity_user_can_delete', 'bp_groups_allow_mods_to_delete_activity', 10, 2 );

/**
 * Exclude Forums from forum loop if any forum is attached to a group and that group have a
 * group type and that group type is hidden from the directory page.
 *
 * @since BuddyBoss 1.1.9
 *
 * @param $args_forum
 * @return mixed $args_forum
 */
function bp_groups_exclude_forums_by_group_type_args( $args_forum ) {
	if ( bbp_is_forum_archive() || bbp_is_topic_archive() ) {
		$exclude_forum_ids = array();
		// Check group type enabled
		if ( true === bp_disable_group_type_creation() ) {
			// Get excluded group ids.
			$exclude_group_ids = array_unique( bp_groups_get_excluded_group_ids_by_type() );
			foreach ( $exclude_group_ids as $exclude_group_id ) {
				// Get forums id by group id.
				$exclude_forum_ids_by_group = bbp_get_group_forum_ids( (int) $exclude_group_id );
				foreach ( $exclude_forum_ids_by_group as $exclude_id ) {
					// Set $exclude_forum_ids array.
					$exclude_forum_ids[] = $exclude_id;
				}
			}
		}
		if ( isset( $exclude_forum_ids ) && ! empty( $exclude_forum_ids ) ) {
			$args_forum['post__not_in'] = $exclude_forum_ids;
		}
	}
	return $args_forum;
}

/**
 * Exclude Forums topic from topic loop if any forum is attached to a group and that group have a
 * group type and that group type is hidden from the directory page.
 *
 * @since BuddyBoss 1.1.9
 *
 * @param $args_topic
 * @return mixed $args_topic
 */
function bp_groups_exclude_forums_topics_by_group_type_args( $args_topic ) {

	if ( bbp_is_forum_archive() || bbp_is_topic_archive() ) {
		$exclude_topic_ids = array();
		// Check group type enabled
		if ( true === bp_disable_group_type_creation() ) {
			// Get excluded group ids.
			$exclude_group_ids = array_unique( bp_groups_get_excluded_group_ids_by_type() );
			foreach ( $exclude_group_ids as $exclude_group_id ) {
				// Get forums id by group id.
				$exclude_forum_ids = bbp_get_group_forum_ids( $exclude_group_id );
				// Loop forum ids to get topics
				foreach ( $exclude_forum_ids as $exclude_forum_id ) {
					$args = array(
						'post_parent' => $exclude_forum_id,
						'post_type'   => bbp_get_topic_post_type(),
						'numberposts' => - 1,
						'fields'      => 'ids',
					);
					// Get topics of forum.
					$topics = get_children( $args );
					foreach ( $topics as $exclude_topic_id ) {
						// Set $exclude_topic_ids array.
						$exclude_topic_ids[] = $exclude_topic_id;
					}
				}
			}
		}
		if ( ! empty( $exclude_topic_ids ) ) {
			$args_topic['post__not_in'] = $exclude_topic_ids;
		}
	}
	return $args_topic;
}

/**
 * Set up media arguments for use with the 'groups' scope.
 *
 * @since BuddyBoss 1.1.9
 *
 * @param array $retval Empty array by default.
 * @param array $filter Current activity arguments.
 * @return array
 */
function bp_groups_filter_media_scope( $retval = array(), $filter = array() ) {

	// Determine the user_id.
	if ( ! empty( $filter['user_id'] ) ) {
		$user_id = $filter['user_id'];
	} else {
		$user_id = bp_displayed_user_id()
			? bp_displayed_user_id()
			: bp_loggedin_user_id();
	}

	if ( 'groups' !== $filter['scope'] ) {
		// Fetch public groups.
		$public_groups = groups_get_groups( array(
			'fields'   => 'ids',
			'status'   => 'public',
			'per_page' => - 1,
		) );
	}
	if ( ! empty( $public_groups['groups'] ) ) {
		$public_groups = $public_groups['groups'];
	} else {
		$public_groups = array();
	}

	// Determine groups of user.
	$groups = groups_get_user_groups( $user_id );
	if ( ! empty( $groups['groups'] ) ) {
		$groups = $groups['groups'];
	} else {
		$groups = array();
	}

	$group_ids = false;
	if ( ! empty( $groups ) && ! empty( $public_groups ) ) {
		$group_ids = array( 'groups' => array_unique( array_merge( $groups, $public_groups ) ) );
	} elseif ( empty( $groups ) && ! empty( $public_groups ) ) {
		$group_ids = array( 'groups' => $public_groups );
	} elseif ( ! empty( $groups ) && empty( $public_groups ) ) {
		$group_ids = array( 'groups' => $groups );
	}

	if ( empty( $group_ids ) ) {
		$group_ids = array( 'groups' => 0 );
	}

	if ( bp_is_group() ) {
		$group_ids = array( 'groups' => array( bp_get_current_group_id() ) );
	}

	$args = array(
		'relation' => 'AND',
		array(
			'column'  => 'group_id',
			'compare' => 'IN',
			'value'   => (array) $group_ids['groups'],
		),
		array(
			'column' => 'privacy',
			'value'  => 'grouponly',
		),
	);

	if ( ! bp_is_group_albums_support_enabled() ) {
		$args[] = array(
			'column'  => 'album_id',
			'compare' => '=',
			'value'   => '0',
		);
	}

	if ( ! empty( $filter['search_terms'] ) ) {
		$args[] = array(
			'relation' => 'OR',
			array(
				'column'  => 'title',
				'compare' => 'LIKE',
				'value'   => $filter['search_terms'],
			),
			array(
				'column'  => 'description',
				'compare' => 'LIKE',
				'value'   => $filter['search_terms'],
			),
		);
	}

	$retval = array(
		'relation' => 'OR',
		$args,
	);

	return $retval;
}

/**
 * Set up video arguments for use with the 'groups' scope.
 *
 * @since BuddyBoss 1.7.0
 *
 * @param array $retval Empty array by default.
 * @param array $filter Current activity arguments.
 * @return array
 */
function bp_groups_filter_video_scope( $retval = array(), $filter = array() ) {

	// Determine the user_id.
	if ( ! empty( $filter['user_id'] ) ) {
		$user_id = $filter['user_id'];
	} else {
		$user_id = bp_displayed_user_id()
			? bp_displayed_user_id()
			: bp_loggedin_user_id();
	}

	if ( 'groups' !== $filter['scope'] ) {
		// Fetch public groups.
		$public_groups = groups_get_groups(
			array(
				'fields'   => 'ids',
				'status'   => 'public',
				'per_page' => - 1,
			)
		);
	}
	if ( ! empty( $public_groups['groups'] ) ) {
		$public_groups = $public_groups['groups'];
	} else {
		$public_groups = array();
	}

	// Determine groups of user.
	$groups = groups_get_user_groups( $user_id );
	if ( ! empty( $groups['groups'] ) ) {
		$groups = $groups['groups'];
	} else {
		$groups = array();
	}

	$group_ids = false;
	if ( ! empty( $groups ) && ! empty( $public_groups ) ) {
		$group_ids = array( 'groups' => array_unique( array_merge( $groups, $public_groups ) ) );
	} elseif ( empty( $groups ) && ! empty( $public_groups ) ) {
		$group_ids = array( 'groups' => $public_groups );
	} elseif ( ! empty( $groups ) && empty( $public_groups ) ) {
		$group_ids = array( 'groups' => $groups );
	}

	if ( empty( $group_ids ) ) {
		$group_ids = array( 'groups' => 0 );
	}

	if ( bp_is_group() ) {
		$group_ids = array( 'groups' => array( bp_get_current_group_id() ) );
	}

	$args = array(
		'relation' => 'AND',
		array(
			'column'  => 'group_id',
			'compare' => 'IN',
			'value'   => (array) $group_ids['groups'],
		),
		array(
			'column' => 'privacy',
			'value'  => 'grouponly',
		),
	);

	if ( ! bp_is_group_video_support_enabled() ) {
		$args[] = array(
			'column'  => 'album_id',
			'compare' => '=',
			'value'   => '0',
		);
	}

	if ( ! empty( $filter['search_terms'] ) ) {
		$args[] = array(
			'relation' => 'OR',
			array(
				'column'  => 'title',
				'compare' => 'LIKE',
				'value'   => $filter['search_terms'],
			),
			array(
				'column'  => 'description',
				'compare' => 'LIKE',
				'value'   => $filter['search_terms'],
			),
		);
	}

	$retval = array(
		'relation' => 'OR',
		$args,
	);

	return $retval;
}

/**
 * Set up document arguments for use with the 'groups' scope.
 *
 * @since BuddyBoss 1.1.9
 *
 * @param array $retval Empty array by default.
 * @param array $filter Current activity arguments.
 * @return array
 */
function bp_groups_filter_document_scope( $retval = array(), $filter = array() ) {

	// Determine the user_id.
	if ( ! empty( $filter['user_id'] ) ) {
		$user_id = $filter['user_id'];
	} else {
		$user_id = bp_displayed_user_id()
			? bp_displayed_user_id()
			: bp_loggedin_user_id();
	}

	$folder_id = 0;
	$folders   = array();
	if ( ! empty( $filter['folder_id'] ) ) {
		$folder_id = (int) $filter['folder_id'];
	}

	if ( 'groups' !== $filter['scope'] ) {
		// Fetch public groups.
		$public_groups = groups_get_groups( array(
			'fields'   => 'ids',
			'status'   => 'public',
			'per_page' => - 1,
		) );
	}

	if ( ! empty( $public_groups['groups'] ) ) {
		$public_groups = $public_groups['groups'];
	} else {
		$public_groups = array();
	}

	// Determine groups of user.
	$groups = groups_get_user_groups( $user_id );
	if ( ! empty( $groups['groups'] ) ) {
		$groups = $groups['groups'];
	} else {
		$groups = array();
	}

	$group_ids = false;
	if ( ! empty( $groups ) && ! empty( $public_groups ) ) {
		$group_ids = array( 'groups' => array_unique( array_merge( $groups, $public_groups ) ) );
	} elseif ( empty( $groups ) && ! empty( $public_groups ) ) {
		$group_ids = array( 'groups' => $public_groups );
	} elseif ( ! empty( $groups ) && empty( $public_groups ) ) {
		$group_ids = array( 'groups' => $groups );
	}

	if ( empty( $group_ids ) ) {
		$group_ids = array( 'groups' => array( 0 ) );
	}

	if ( bp_is_group() ) {
		$group_ids = array( 'groups' => array( bp_get_current_group_id() ) );
	} elseif ( ! empty( $filter['group_id'] ) ) {
		$group_ids = array( 'groups' => array( $filter['group_id'] ) );
	}

	if ( ! empty( $filter['search_terms'] ) ) {
		if ( ! empty( $folder_id ) ) {
			$folder_ids       = array();
			$fetch_folder_ids = bp_document_get_folder_children( (int) $folder_id );
			if ( $fetch_folder_ids ) {
				foreach ( $fetch_folder_ids as $single_folder ) {
					$single_folder_ids = bp_document_get_folder_children( (int) $single_folder );
					if ( $single_folder_ids ) {
						array_merge( $folder_ids, $single_folder_ids );
					}
					array_push( $folder_ids, $single_folder );
				}
			}

			$folder_ids[] = $folder_id;
			$folders      = array(
				'column'  => 'folder_id',
				'compare' => 'IN',
				'value'   => $folder_ids,
			);
		}
	} else {
		if ( ! empty( $folder_id ) ) {
			$folders = array(
				'column'  => 'folder_id',
				'compare' => '=',
				'value'   => $folder_id,
			);
		} else {
			$folders = array(
				'column' => 'folder_id',
				'value'  => 0,
			);
		}
	}

	if ( ! bp_is_group_document_support_enabled() ) {
		$group_ids['groups'] = array( 0 );
	}

	$args = array(
		'relation' => 'AND',
		array(
			'column'  => 'group_id',
			'compare' => 'IN',
			'value'   => (array) $group_ids['groups'],
		),
		array(
			'column' => 'privacy',
			'value'  => 'grouponly',
		),
		$folders,
	);

	return $args;
}

function bp_groups_filter_folder_scope( $retval = array(), $filter = array() ) {

	if ( ! bp_is_group_document_support_enabled() ) {
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

	$folder_id = 0;
	$folders = array();

	if ( ! empty( $filter['folder_id'] ) ) {
		$folder_id = (int) $filter['folder_id'];
	}

	if ( 'groups' !== $filter['scope'] ) {
		// Fetch public groups.
		$public_groups = groups_get_groups( array(
			'fields'   => 'ids',
			'status'   => 'public',
			'per_page' => - 1,
		) );
	}

	if ( ! empty( $public_groups['groups'] ) ) {
		$public_groups = $public_groups['groups'];
	} else {
		$public_groups = array();
	}

	// Determine groups of user.
	$groups = groups_get_user_groups( $user_id );
	if ( ! empty( $groups['groups'] ) ) {
		$groups = $groups['groups'];
	} else {
		$groups = array();
	}

	$group_ids = false;
	if ( ! empty( $groups ) && ! empty( $public_groups ) ) {
		$group_ids = array( 'groups' => array_unique( array_merge( $groups, $public_groups ) ) );
	} elseif ( empty( $groups ) && ! empty( $public_groups ) ) {
		$group_ids = array( 'groups' => $public_groups );
	} elseif ( ! empty( $groups ) && empty( $public_groups ) ) {
		$group_ids = array( 'groups' => $groups );
	}

	if ( empty( $group_ids ) ) {
		$group_ids = array( 'groups' => array( 0 ) );
	}

	if ( bp_is_group() ) {
		$group_ids = array( 'groups' => array( bp_get_current_group_id() ) );
	} elseif ( ! empty( $filter['group_id'] ) ) {
		$group_ids = array( 'groups' => array( $filter['group_id'] ) );
	}

	if ( ! empty( $filter['search_terms'] ) ) {
		if ( ! empty( $folder_id ) ) {
			$folder_ids = array();
			$fetch_folder_ids = bp_document_get_folder_children( (int) $folder_id );
			if ( $fetch_folder_ids ) {
				foreach ( $fetch_folder_ids as $single_folder ) {
					$single_folder_ids = bp_document_get_folder_children( (int) $single_folder );
					if ( $single_folder_ids ) {
						array_merge( $folder_ids, $single_folder_ids );
					}
					array_push( $folder_ids, $single_folder );
				}
			}

			$folder_ids[] = $folder_id;
			$folders      = array(
				'column'  => 'parent',
				'compare' => 'IN',
				'value'   => $folder_ids,
			);
		}
	} else {
		if ( ! empty( $folder_id ) ) {
			$folders = array(
				'column'  => 'parent',
				'compare' => '=',
				'value'   => $folder_id,
			);
		} else {
			$folders = array(
				'column' => 'parent',
				'value'  => 0,
			);
		}
	}

	$args = array(
		'relation' => 'AND',
		array(
			'column'  => 'group_id',
			'compare' => 'IN',
			'value'   => (array) $group_ids['groups'],
		),
		array(
			'column' => 'privacy',
			'value'  => 'grouponly',
		),
		$folders,
	);

	return $args;
}

/**
 * Filters the member IDs for the current group member query.
 *
 * Use this filter to build a custom query (such as when you've
 * defined a custom 'type').
 *
 * @since BuddyBoss 1.5.8
 *
 * @param array                 $group_member_ids          Array of associated member IDs.
 * @param BP_Group_Member_Query $group_member_query_object Current BP_Group_Member_Query instance.
 */
function bb_group_member_query_group_message_member_ids( $group_member_ids, $group_member_query_object ) {

	if ( bp_is_active( 'groups' ) && bp_is_group_single() && bp_is_group_messages() && 'private-message' === bb_get_group_current_messages_tab() ) {

		$can_send_arr  = array();
		$cant_send_arr = array();

		// Check if force friendship is enabled and check recipients.
		if ( bp_force_friendship_to_message() && bp_is_active( 'friends' ) ) {
			foreach ( $group_member_ids as $member_id ) {
				if ( friends_check_friendship( bp_loggedin_user_id(), $member_id ) ) {
					$can_send_arr[] = $member_id;
				} else {
					$cant_send_arr[] = $member_id;
				}
			}
			$group_member_ids = array_merge( $can_send_arr, $cant_send_arr );
		}
	}

	/**
	 * Filters the member IDs for the current group member query.
	 *
	 * Use this filter to build a custom query (such as when you've
	 * defined a custom 'type').
	 *
	 * @since BuddyBoss 1.5.8
	 *
	 * @param array                 $group_member_ids          Array of associated member IDs.
	 * @param BP_Group_Member_Query $group_member_query_object Current BP_Group_Member_Query instance.
	 */
	return apply_filters( 'bb_group_member_query_group_message_member_ids', $group_member_ids, $group_member_query_object );
}
add_filter( 'bp_group_member_query_group_member_ids', 'bb_group_member_query_group_message_member_ids', 9999, 2 );

/**
 * Filters the my-groups menu url for the logged in group member.
 *
 * When there is My gorups menu available on the website,
 * use this filter to fix the current user's gorups link.
 *
 * @since BuddyBoss 1.9.3
 *
 * @param array $sorted_menu_objects Array of menu objects.
 * @param array $args                Array of arguments.
 */
function bb_my_group_menu_url( $sorted_menu_objects, $args ) {

	if ( 'header-menu' !== $args->theme_location ) {
		return $sorted_menu_objects;
	}

	foreach ( $sorted_menu_objects as $key => $menu_object ) {

		// Replace the URL when bp_loggedin_user_domain && bp_displayed_user_domain are not same.
		if ( class_exists( 'BuddyPress' ) ) {
			if ( bp_loggedin_user_domain() !== bp_displayed_user_domain() ) {
				$menu_object->url = str_replace( bp_displayed_user_domain(), bp_loggedin_user_domain(), $menu_object->url );
			}
		}
	}

	return $sorted_menu_objects;
}
add_filter( 'wp_nav_menu_objects', 'bb_my_group_menu_url', 10, 2 );


/**
 * Register the group notifications.
 *
 * @since BuddyBoss 1.9.3
 */
function bb_load_groups_notifications() {
	if ( class_exists( 'BP_Groups_Notification' ) ) {
		BP_Groups_Notification::instance();
	}
}

/**
 * Custom css for all group type's label. ( i.e - Background color, Text color)
 *
 * @since BuddyBoss 2.0.0
 */
function bb_load_group_type_label_custom_css() {
	if ( true === bp_disable_group_type_creation() ) {
		$registered_group_types = bp_groups_get_group_types();
		$cache_key              = 'bb-group-type-label-css';
		$group_type_custom_css  = wp_cache_get( $cache_key, 'bp_groups_group_type' );
		if ( false === $group_type_custom_css && ! empty( $registered_group_types ) ) {
			foreach ( $registered_group_types as $type ) {
				$label_color_data = function_exists( 'bb_get_group_type_label_colors' ) ? bb_get_group_type_label_colors( $type ) : '';
				if (
					isset( $label_color_data ) &&
					isset( $label_color_data['color_type'] ) &&
					'custom' === $label_color_data['color_type']
				) {
					$background_color      = isset( $label_color_data['background-color'] ) ? $label_color_data['background-color'] : '';
					$text_color            = isset( $label_color_data['color'] ) ? $label_color_data['color'] : '';
					$class_name            = 'body .bp-group-meta .group-type.bb-current-group-' . $type;
					$group_type_custom_css .= $class_name . ' {' . "background-color:$background_color;" . '}';
					$group_type_custom_css .= $class_name . ' {' . "color:$text_color;" . '}';
				}
			}
			wp_cache_set( $cache_key, $group_type_custom_css, 'bp_groups_group_type' );
		}
		wp_add_inline_style( 'bp-nouveau', $group_type_custom_css );
	}

	// load the group card template.
	bb_group_card_template();
}
add_action( 'bp_enqueue_scripts', 'bb_load_group_type_label_custom_css', 12 );

/**
 * Filters the Where SQL statement.
 *
 * @since 2.3.4
 *
 * @param array $where_conditions Group Where sql.
 * @param array $args             Query arguments.
 *
 * @return mixed Where SQL
 */
function bb_groups_count_update_where_sql( $where_conditions, $args = array() ) {

	if ( ! bp_is_user_groups() && bp_is_groups_directory() && true === (bool) bp_enable_group_hide_subgroups() ) {
		$where_conditions[] = 'g.parent_id = 0';
	}

	return $where_conditions;
}

/**
 * Remove suspended user and assign site admin as group organizer only when a single group organizer.
 *
 * @since BuddyBoss 2.6.10
 *
 * @param int $user_id User id.
 *
 * @return void
 */
function bb_group_remove_suspended_user( $user_id ) {
	global $wpdb, $bp;
	if ( empty( $user_id ) ) {
		return;
	}
	// Remove user when suspended.
	if (
		function_exists( 'bp_moderation_is_user_suspended' ) &&
		bp_moderation_is_user_suspended( $user_id )
	) {
		$group_ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT DISTINCT group_id FROM {$bp->groups->table_name_members} WHERE user_id = %d AND is_confirmed = %d AND is_banned = %d AND is_admin = %d ORDER BY date_modified ASC",
				$user_id,
				1,
				0,
				1
			)
		);

		if ( ! empty( $group_ids ) ) {
			$admin = get_users(
				array(
					'blog_id' => bp_get_root_blog_id(),
					'fields'  => 'id',
					'number'  => 1,
					'orderby' => 'ID',
					'role'    => 'administrator',
					'exclude' => array( $user_id ),
				)
			);
			foreach ( $group_ids as $group_id ) {
				if ( count( groups_get_group_admins( $group_id ) ) < 2 ) {

					if ( ! empty( $admin ) ) {
						if ( bp_is_active( 'messages' ) ) {
							remove_action( 'groups_join_group', 'bp_group_messages_join_new_member', 10, 2 );
						}
						add_filter( 'bb_group_join_groups_record_activity', 'bb_group_join_groups_record_activity_unsuspend_users' );

						groups_join_group( $group_id, $admin[0] );

						remove_filter( 'bb_group_join_groups_record_activity', 'bb_group_join_groups_record_activity_unsuspend_users' );
						if ( bp_is_active( 'messages' ) ) {
							add_action( 'groups_join_group', 'bp_group_messages_join_new_member', 10, 2 );
						}
						$member = new BP_Groups_Member( $admin[0], $group_id );
						$member->promote( 'admin' );
					}
				}

				BP_Groups_Member::delete( $user_id, $group_id );

				// Update the group meta to store organiser when they suspended.
				$suspended_users = groups_get_groupmeta( $group_id, 'bb_suspended_users' );
				if ( ! empty( $suspended_users ) && ! empty( $suspended_users['admins'] ) ) {
					$suspended_users['admin'][] = $user_id;
				} else {
					$suspended_users = array(
						'admin' => array(
							$user_id,
						),
					);
				}

				$suspended_users['admin'] = array_unique( $suspended_users['admin'] );
				groups_update_groupmeta( $group_id, 'bb_suspended_users', $suspended_users );
			}
		}
	}
}

/**
 * Re-assign user when unsuspend to the group only when a single group organizer.
 *
 * @since BuddyBoss 2.6.10
 *
 * @param int $user_id User id.
 *
 * @return void
 */
function bb_group_add_unsuspended_user( $user_id ) {
	global $wpdb, $bp;

	if ( empty( $user_id ) ) {
		return;
	}

	// Remove user when un-suspended.
	$group_metas = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT group_id, meta_value FROM {$bp->groups->table_name_groupmeta} WHERE meta_key = %s AND meta_value LIKE %s ORDER BY id ASC",
			'bb_suspended_users',
			'%' . $wpdb->esc_like( $user_id ) . '%'
		),
		ARRAY_A
	);

	if ( ! empty( $group_metas ) ) {
		foreach ( $group_metas as $group ) {
			$group_meta = maybe_unserialize( $group['meta_value'] );

			if ( ! empty( $group_meta ) && ! empty( $group_meta['admin'] ) ) {
				// Search for the value in the array.
				$result_index = array_search( $user_id, $group_meta['admin'] );

				// Check if the value was found.
				if ( false !== $result_index ) {

					// Remove that user from meta.
					unset( $group_meta['admin'][ $result_index ] );

					if ( bp_is_active( 'messages' ) ) {
						remove_action( 'groups_join_group', 'bp_group_messages_join_new_member', 10, 2 );
					}
					add_filter( 'bb_group_join_groups_record_activity', 'bb_group_join_groups_record_activity_unsuspend_users' );

					// Join this user in the group.
					groups_join_group( $group['group_id'], $user_id );

					remove_filter( 'bb_group_join_groups_record_activity', 'bb_group_join_groups_record_activity_unsuspend_users' );
					if ( bp_is_active( 'messages' ) ) {
						add_action( 'groups_join_group', 'bp_group_messages_join_new_member', 10, 2 );
					}

					// Promoted to admin.
					$member = new BP_Groups_Member( $user_id, $group['group_id'] );
					$member->promote( 'admin' );

					// Update the group meta.
					groups_update_groupmeta( $group['group_id'], 'bb_suspended_users', $group_meta );
				}
			}
		}
	}
}

/**
 * Function will not allow to record group activity when group organizer
 * unsuspend where group have only one organizer.
 *
 * @since BuddyBoss 2.6.10
 *
 * @return bool Return false.
 */
function bb_group_join_groups_record_activity_unsuspend_users() {
	return false;
}

/**
 * Add subgroup args for single/home page to avoid looping for subgroups.
 *
 * @since BuddyBoss 2.6.40
 */
function bb_before_group_body_callback() {
	add_filter( 'bp_after_groups_template_parse_args', 'bb_add_subgroups_args_single_home' );
}

/**
 * Remove subgroup args for single/home page to avoid looping for subgroups.
 *
 * @since BuddyBoss 2.6.40
 */
function bb_after_group_body_callback() {
	remove_filter( 'bp_after_groups_template_parse_args', 'bb_add_subgroups_args_single_home' );
}

/**
 * Add subgroups args to fetch subgroups for the single/home page.
 *
 * @since BuddyBoss 2.6.40
 *
 * @param array $args Group args.
 *
 * @return array
 */
function bb_add_subgroups_args_single_home( $args ) {
	if ( ( isset( $_POST['template'] ) && 'group_subgroups' === $_POST['template'] ) || bp_is_group_subgroups() ) {
		$descendant_groups   = bp_get_descendent_groups( bp_get_current_group_id(), bp_loggedin_user_id() );
		$ids                 = wp_list_pluck( $descendant_groups, 'id' );
		$args['include']     = $ids;
		$args['slug']        = '';
		$args['type']        = '';
		$args['show_hidden'] = true;
	}

	/**
	 * Filters the group args for single/home page to avoid looping for subgroups.
	 *
	 * @since BuddyBoss 2.6.40
	 *
	 * @param array $args Group args.
	 */
	return apply_filters( 'bb_add_subgroups_args_single_home', $args );
}

/**
 * Add group hover card template.
 *
 * @since BuddyBoss 2.8.20
 */
function bb_group_card_template() {
	bp_get_template_part( 'groups/group-card' );
}

/**
 * Delete group activity topic when delete the group.
 *
 * @since BuddyBoss 2.8.80
 *
 * @param int $group_id ID of the group.
 *
 * @return bool|int True on success, false on failure.
 */
function bb_delete_group_activity_topic( $group_id ) {
	global $wpdb;

	$table_prefix = bp_core_get_table_prefix();
	$deleted      = $wpdb->delete( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$table_prefix . 'bb_topic_relationships',
		array(
			'item_id'   => $group_id,
			'item_type' => 'groups',
		),
		array( '%d', '%s' )
	);

	if ( false === $deleted ) {
		return false;
	}

	return true;
}
add_action( 'groups_delete_group', 'bb_delete_group_activity_topic' );

/**
 * Retrieves the groups the logged-in user is a member of and adds them to the provided arguments array.
 *
 * @since BuddyBoss 2.9.00
 *
 * @param array $args Arguments array to which the group data will be added.
 *
 * @return array Modified arguments array with the user's groups data.
 */
function bb_readylaunch_middle_content_my_groups( $args = array() ) {
	$group_data = array(
		'integration' => 'groups',
	);

	if ( $args['has_sidebar_data'] && $args['is_sidebar_enabled_for_groups'] ) {
		$group_data['heading']    = __( 'My Groups', 'buddyboss-platform' );
		$group_data['error_text'] = __( 'There are no groups to display.', 'buddyboss-platform' );

		$user_id    = bp_loggedin_user_id();
		$group_args = array(
			'user_id'  => $user_id,
			'per_page' => 6,
		);
		if ( ! empty( $user_id ) ) {
			$count = groups_total_groups_for_user( $user_id );
		} else {
			$count = bp_get_total_group_count();
		}

		$groups = groups_get_groups( $group_args );
		if ( ! empty( $groups['groups'] ) ) {
			foreach ( $groups['groups'] as $group ) {
				$group_id                         = $group->id;
				$thumbnail_url                    = bp_get_group_avatar_url( $group );
				$group_data['items'][ $group_id ] = array(
					'title'     => $group->name,
					'permalink' => bp_get_group_permalink( $group ),
					'thumbnail' => '<img src="' . $thumbnail_url . '" alt="' . $group->name . '" class="avatar group--avatar avatar-200 photo" width="200" height="200"/>',
				);
			}

			if ( $count > 6 ) {
				$group_data['has_more_items'] = true;
				$group_data['show_more_link'] = bp_get_groups_directory_permalink();
			}
		}
	}

	$args['groups'] = $group_data;

	return $args;
}
