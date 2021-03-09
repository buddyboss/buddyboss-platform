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
add_filter( 'bp_get_total_group_count_for_user', 'bp_core_number_format' );

// Activity component integration.
add_filter( 'bp_activity_at_name_do_notifications', 'bp_groups_disable_at_mention_notification_for_non_public_groups', 10, 4 );
add_filter( 'bbp_forums_at_name_do_notifications', 'bp_groups_disable_at_mention_forums_notification_for_non_public_groups', 10, 4 );

// Default group avatar.
add_filter( 'bp_core_avatar_default', 'bp_groups_default_avatar', 10, 3 );
add_filter( 'bp_core_avatar_default_thumb', 'bp_groups_default_avatar', 10, 3 );

// Exclude Forums if group type hide.
add_filter( 'bbp_after_has_forums_parse_args', 'bp_groups_exclude_forums_by_group_type_args' );
// Exclude Forums if group type hide.
add_filter( 'bbp_after_has_topics_parse_args', 'bp_groups_exclude_forums_topics_by_group_type_args' );
// media scope filter.
add_filter( 'bp_media_set_groups_scope_args', 'bp_groups_filter_media_scope', 10, 2 );
add_filter( 'bp_document_set_document_groups_scope_args', 'bp_groups_filter_document_scope', 10, 2 );
add_filter( 'bp_document_set_folder_groups_scope_args', 'bp_groups_filter_folder_scope', 10, 2 );

/**
 * Filter output of Group Description through WordPress's KSES API.
 *
 * @since BuddyPress 1.1.0
 *
 * @param string $content Content to filter.
 * @return string
 */
function bp_groups_filter_kses( $content = '' ) {

	/**
	 * Note that we don't immediately bail if $content is empty. This is because
	 * WordPress's KSES API calls several other filters that might be relevant
	 * to someone's workflow (like `pre_kses`)
	 */

	// Get allowed tags using core WordPress API allowing third party plugins
	// to target the specific `buddypress-groups` context.
	$allowed_tags = wp_kses_allowed_html( 'buddypress-groups' );

	// Add our own tags allowed in group descriptions.
	$allowed_tags['a']['class']    = array();
	$allowed_tags['img']           = array();
	$allowed_tags['img']['src']    = array();
	$allowed_tags['img']['alt']    = array();
	$allowed_tags['img']['width']  = array();
	$allowed_tags['img']['height'] = array();
	$allowed_tags['img']['class']  = array();
	$allowed_tags['img']['id']     = array();
	$allowed_tags['code']          = array();
	$allowed_tags['ol']            = array();
	$allowed_tags['ul']            = array();
	$allowed_tags['li']            = array();
	$allowed_tags['a']['target']   = array();

	/**
	 * Filters the HTML elements allowed for a given context.
	 *
	 * @since BuddyPress 1.2.0
	 *
	 * @param string $allowed_tags Allowed tags, attributes, and/or entities.
	 */
	$tags = apply_filters( 'bp_groups_filter_kses', $allowed_tags );

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
function bp_groups_disable_at_mention_notification_for_non_public_groups( $send, $usernames, $user_id, BP_Activity_Activity $activity ) {
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
 * Use the mystery group avatar for groups.
 *
 * @since BuddyPress 2.6.0
 *
 * @param string $avatar Current avatar src.
 * @param array  $params Avatar params.
 * @return string
 */
function bp_groups_default_avatar( $avatar, $params ) {
	if ( isset( $params['object'] ) && 'group' === $params['object'] ) {
		if ( isset( $params['type'] ) && 'thumb' === $params['type'] ) {
			$file = 'mystery-group-50.png';
		} else {
			$file = 'mystery-group.png';
		}

		$avatar = buddypress()->plugin_url . "bp-core/images/$file";
	}

	return $avatar;
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
	if ( ! $can_delete && is_user_logged_in() && 'groups' == $activity->component ) {
		$group = groups_get_group( $activity->item_id );

		if ( ! empty( $group ) &&
			 ! groups_is_user_admin( $activity->user_id, $activity->item_id ) &&
			 groups_is_user_mod( apply_filters( 'bp_loggedin_user_id', get_current_user_id() ), $activity->item_id )
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
			'column'  => 'title',
			'compare' => 'LIKE',
			'value'   => $filter['search_terms'],
		);
	}

	$retval = array(
		'relation' => 'OR',
		$args
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
				'column'  => 'parent',
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
