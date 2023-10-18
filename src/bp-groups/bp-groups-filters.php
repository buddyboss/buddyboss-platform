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

add_filter( 'bp_repair_list', 'bb_groups_repair_group_subscriptions', 11 );
add_action( 'bp_actions', 'bb_group_subscriptions_handler' );

// Filter group count.
add_filter( 'bp_groups_get_where_count_conditions', 'bb_groups_count_update_where_sql', 10, 2 );

// Remove from group forums and topics.
add_action( 'groups_leave_group', 'bb_groups_unsubscribe_group_forums_topic', 10, 2 );

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

	if ( 'groups' === $activity->component && ! bp_user_can( $user_id, 'groups_access_group', array( 'group_id' => $activity->item_id ) ) ) {
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
			)
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
			)
		);
	}

	$retval = array(
		'relation' => 'OR',
		$args
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
			)
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
}
add_action( 'bp_enqueue_scripts', 'bb_load_group_type_label_custom_css', 12 );

/**
 * Send subscription notification to users after post an activity.
 *
 * @since BuddyBoss 2.2.8
 *
 * @param string $content     The content of the update.
 * @param int    $user_id     ID of the user posting the update.
 * @param int    $group_id    ID of the group being posted to.
 * @param bool   $activity_id Whether the activity recording succeeded.
 *
 * @return void
 */
function bb_subscription_send_subscribe_group_notifications( $content, $user_id, $group_id, $activity_id ) {
	global $bp_activity_edit;

	// Bail if subscriptions are turned off.
	if ( ! bb_is_enabled_subscription( 'group' ) || ! bp_is_active( 'activity' ) ) {
		return;
	}

	if ( empty( $user_id ) || empty( $group_id ) || empty( $activity_id ) || $bp_activity_edit ) {
		return;
	}

	$activity = new BP_Activity_Activity( $activity_id );

	if ( empty( $activity ) || ( ! empty( $activity->item_id ) && $activity->item_id !== (int) $group_id ) ) {
		return;
	}

	// Return if main activity post not found or activity is media/document/video.
	if (
		empty( $activity ) ||
		'groups' !== $activity->component ||
		in_array( $activity->privacy, array( 'document', 'media', 'video', 'onlyme' ), true )
	) {
		return;
	}

	$activity_user_id = $activity->user_id;
	$poster_name      = bp_core_get_user_displayname( $activity_user_id );
	$activity_link    = bp_activity_get_permalink( $activity_id );
	$group            = groups_get_group( $group_id );
	$media_ids        = bp_activity_get_meta( $activity_id, 'bp_media_ids', true );
	$document_ids     = bp_activity_get_meta( $activity_id, 'bp_document_ids', true );
	$video_ids        = bp_activity_get_meta( $activity_id, 'bp_video_ids', true );
	$gif_data         = bp_activity_get_meta( $activity_id, '_gif_data', true );

	if ( ! empty( wp_strip_all_tags( $activity->content ) ) ) {
		$activity_type = __( 'an update', 'buddyboss' );
	} elseif ( $media_ids ) {
		$media_ids = array_filter( ! is_array( $media_ids ) ? explode( ',', $media_ids ) : $media_ids );
		if ( count( $media_ids ) > 1 ) {
			$activity_type = __( 'some photos', 'buddyboss' );
		} else {
			$activity_type = __( 'a photo', 'buddyboss' );
		}
	} elseif ( $document_ids ) {
		$document_ids = array_filter( ! is_array( $document_ids ) ? explode( ',', $document_ids ) : $document_ids );
		if ( count( $document_ids ) > 1 ) {
			$activity_type = __( 'some documents', 'buddyboss' );
		} else {
			$activity_type = __( 'a document', 'buddyboss' );
		}
	} elseif ( $video_ids ) {
		$video_ids = array_filter( ! is_array( $video_ids ) ? explode( ',', $video_ids ) : $video_ids );
		if ( count( $video_ids ) > 1 ) {
			$activity_type = __( 'some videos', 'buddyboss' );
		} else {
			$activity_type = __( 'a video', 'buddyboss' );
		}
	} elseif ( $gif_data ) {
		$activity_type = __( 'a gif', 'buddyboss' );
	} else {
		$activity_type = __( 'an update', 'buddyboss' );
	}

	$args = array(
		'tokens' => array(
			'activity'      => $activity,
			'poster.name'   => $poster_name,
			'activity.url'  => esc_url( $activity_link ),
			'group.url'     => esc_url( bp_get_group_permalink( $group ) ),
			'group.name'    => bp_get_group_name( $group ),
			'activity.type' => $activity_type,
		),
	);

	bb_send_notifications_to_subscribers(
		array(
			'type'              => 'group',
			'item_id'           => $group_id,
			'notification_from' => 'bb_groups_subscribed_activity',
			'data'              => array(
				'activity_id'  => $activity_id,
				'author_id'    => $activity_user_id,
				'email_tokens' => $args,
			),
		)
	);
}
add_action( 'bp_groups_posted_update', 'bb_subscription_send_subscribe_group_notifications', 11, 4 );

/**
 * Add group subscription repair list item.
 *
 * @since BuddyBoss 2.2.8
 *
 * @param array $repair_list Repair list.
 *
 * @return array Repair list items.
 */
function bb_groups_repair_group_subscriptions( $repair_list ) {
	if ( bp_is_active( 'groups' ) ) {
		$repair_list[] = array(
			'bb-repair-group-subscription',
			esc_html__( 'Migrate Group forum and discussion subscriptions data structure to the new subscription flow', 'buddyboss' ),
			'bb_migrate_group_subscription',
		);
	}

	return $repair_list;
}

/**
 * Handles the front end subscribing and unsubscribing topics.
 *
 * @since BuddyBoss 2.2.8
 *
 * @return void|WP_Error
 */
function bb_group_subscriptions_handler() {
	global $wp;

	if ( ! function_exists( 'bb_is_enabled_subscription' ) || ! bb_is_enabled_subscription( 'group' ) ) {
		return;
	}

	// Bail if no group ID is passed.
	if ( empty( $_GET['action'] ) || empty( $_GET['group_id'] ) || empty( $_GET['_wpnonce'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
		return;
	}

	// Get required data.
	$user_id  = get_current_user_id();
	$group_id = (int) sanitize_text_field( wp_unslash( $_GET['group_id'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
	$action   = sanitize_text_field( wp_unslash( $_GET['action'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
	$nonce    = sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
	$group    = groups_get_group( $group_id );

	// Setup possible get actions.
	$possible_actions = array(
		'subscribe',
		'unsubscribe',
	);

	// Bail if actions aren't meant for this function.
	if ( ! in_array( $action, $possible_actions, true ) ) {
		return;
	}

	$message = '';
	$type    = 'error';

	// Check for empty group.
	if ( empty( $group_id ) || empty( $group->id ) ) {
		$message = __( 'No group was found! Which group are you subscribing/unsubscribing to?', 'buddyboss' );
	} elseif ( ! wp_verify_nonce( $nonce, 'bb-group-subscription-' . $group_id ) ) {
		$message = __( 'There was a problem subscribing/unsubscribing from that group!', 'buddyboss' );
	} elseif ( ! groups_is_user_member( $user_id, $group_id ) ) {
		$message = __( 'You are not part of that group!', 'buddyboss' );
	}

	$group_name = sprintf(
		'<strong>%s</strong>',
		bp_get_group_name( $group )
	);

	$is_subscription = bb_is_member_subscribed_group( $group_id, $user_id );

	if ( empty( $message ) && 'subscribe' === $action ) {
		if ( $is_subscription ) {
			$message = __( '%s You are already subscribe this group.', 'buddyboss' );
		} else {
			$subscription_id = bb_create_subscription(
				array(
					'user_id'           => $user_id,
					'item_id'           => $group_id,
					'type'              => 'group',
					'secondary_item_id' => $group->parent_id,
				)
			);

			if ( is_wp_error( $subscription_id ) ) {
				$message = sprintf(
				/* translators: Group name */
					__( 'There was a problem subscribing to %s.', 'buddyboss' ),
					$group_name
				);
			} else {
				$message = sprintf(
				/* translators: Group name */
					__( 'You\'ve been subscribed to %s.', 'buddyboss' ),
					$group_name
				);
				$type    = 'success';
			}
		}
	} elseif ( empty( $message ) && 'unsubscribe' === $action ) {
		if (  ! $is_subscription || ! bb_delete_subscription( $is_subscription ) ) {
			$message = sprintf(
			/* translators: Group name */
				__( 'There was a problem unsubscribing from %s.', 'buddyboss' ),
				$group_name
			);
		} else {
			$message = sprintf(
			/* translators: Group name */
				__( 'You\'ve been unsubscribed from %s.', 'buddyboss' ),
				$group_name
			);
			$type    = 'success';
		}
	}

	if ( ! empty( $message ) ) {
		bp_core_add_message( $message, $type );
	}
	wp_safe_redirect( esc_url( trailingslashit( home_url( $wp->request ) ) ) );
	exit();
}

/**
 * Display group header action button when layout is left.
 *
 * @since BuddyBoss 2.2.8
 *
 * @return void
 */
function bb_group_single_left_header_actions() {
	if ( 'left' === bb_platform_group_header_style() ) {
		bb_group_single_header_actions();
	}
}
add_action( 'bb_group_single_top_header_action', 'bb_group_single_left_header_actions' );

/**
 * Display group header action button when layout is center.
 *
 * @since BuddyBoss 2.2.8
 *
 * @return void
 */
function bb_group_single_center_header_actions() {
	if ( 'centered' === bb_platform_group_header_style() ) {
		bb_group_single_header_actions();
	}
}
add_action( 'bb_group_single_bottom_header_action', 'bb_group_single_center_header_actions' );

/**
 * Delete group subscription when delete the group.
 *
 * @since BuddyBoss 2.2.8
 *
 * @param int $group_id ID of the group.
 *
 * @return bool|int True on success, false on failure.
 */
function bb_delete_group_subscriptions( $group_id ) {
	bb_delete_subscriptions_by_item( 'group', $group_id );
}
add_action( 'groups_delete_group', 'bb_delete_group_subscriptions' );

/**
 * Send subscription notification to users after upload media/documents/videos in the group.
 *
 * @since BuddyBoss 2.2.9.1
 *
 * @param string $content     The content of the update.
 * @param int    $user_id     ID of the user posting the update.
 * @param bool   $activity_id Whether the activity recording succeeded.
 *
 * @return void
 */
function bb_subscription_send_subscribe_group_media_notifications( $content, $user_id, $activity_id ) {
	global $bp_activity_edit;

	// Bail if subscriptions are turned off.
	if ( ! bb_is_enabled_subscription( 'group' ) || ! bp_is_active( 'activity' ) ) {
		return;
	}

	if ( empty( $user_id ) || empty( $activity_id ) || $bp_activity_edit ) {
		return;
	}

	$activity = new BP_Activity_Activity( $activity_id );

	// Return if main activity post not found or activity is media/document/video.
	if (
		empty( $activity ) ||
		'groups' !== $activity->component ||
		in_array( $activity->privacy, array( 'document', 'media', 'video', 'onlyme' ), true )
	) {
		return;
	}

	bb_subscription_send_subscribe_group_notifications( $content, $user_id, $activity->item_id, $activity_id );
}
add_action( 'bb_media_after_create_parent_activity', 'bb_subscription_send_subscribe_group_media_notifications', 10, 3 );
add_action( 'bb_document_after_create_parent_activity', 'bb_subscription_send_subscribe_group_media_notifications', 10, 3 );
add_action( 'bb_video_after_create_parent_activity', 'bb_subscription_send_subscribe_group_media_notifications', 10, 3 );

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
 * Unsubscribe a user from a group forum topics.
 *
 * @since BuddyBoss 2.4.50
 *
 * @param int $group_id Group ID
 * @param int $user_id  User ID
 *
 * @return void
 */
function bb_groups_unsubscribe_group_forums_topic( $group_id, $user_id ) {

	// Bail if forum is disabled.
	if ( ! bp_is_active( 'forums' ) ) {
		return;
	}

	// Bail if group id and user id is not set.
	if ( empty( $group_id ) || empty( $user_id ) ) {
		return;
	}

	$forum_ids = bbp_get_group_forum_ids( $group_id );
	if ( empty( $forum_ids ) ) {
		return;
	}

	$notifications = BB_Subscriptions::get(
		array(
			'user_id'           => $user_id,
			'type'              => 'topic',
			'fields'            => 'item_id',
			'secondary_item_id' => $forum_ids,
		)
	);

	if ( empty( $notifications['subscriptions'] ) ) {
		return;
	}

	// Loop through subscribed topics and remove user from this group related topics.
	foreach ( (array) $notifications['subscriptions'] as $topic_id ) {
		$topic_forum_id = bbp_get_topic_forum_id( $topic_id );
		if (
			bbp_is_forum_private( $topic_forum_id ) ||
			bbp_is_forum_hidden( $topic_forum_id )
		) {
			bbp_remove_user_topic_subscription( $user_id, $topic_id );
		}
	}
}
