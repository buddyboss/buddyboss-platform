<?php
/**
 * BuddyBoss Activity Functions.
 *
 * Functions for the Activity Feeds component.
 *
 * @package BuddyBoss\Activity
 * @since BuddyPress 1.5.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Check whether the $bp global lists an activity directory page.
 *
 * @since BuddyPress 1.5.0
 *
 * @return bool True if activity directory page is found, otherwise false.
 */
function bp_activity_has_directory() {
	return (bool) ! empty( buddypress()->pages->activity->id );
}

/**
 * Are mentions enabled or disabled?
 *
 * The Mentions feature does a number of things, all of which will be turned
 * off if you disable mentions:
 *   - Detecting and auto-linking @username in all BP/WP content.
 *   - Sending BP notifications and emails to users when they are mentioned
 *     using the @username syntax.
 *   - The Public Message button on user profiles.
 *
 * Mentions are enabled by default. To disable, put the following line in
 * bp-custom.php or your theme's functions.php file:
 *
 *   add_filter( 'bp_activity_do_mentions', '__return_false' );
 *
 * @since BuddyPress 1.8.0
 *
 * @return bool $retval True to enable mentions, false to disable.
 */
function bp_activity_do_mentions() {

	/**
	 * Filters whether or not mentions are enabled.
	 *
	 * @since BuddyPress 1.8.0
	 *
	 * @param bool $enabled True to enable mentions, false to disable.
	 */
	return (bool) apply_filters( 'bp_activity_do_mentions', true );
}

/**
 * Should BuddyPress load the mentions scripts and related assets, including results to prime the
 * mentions suggestions?
 *
 * @since BuddyPress 2.1.0
 *
 * @return bool True if mentions scripts should be loaded.
 */
function bp_activity_maybe_load_mentions_scripts() {
	$mentions_enabled = bp_activity_do_mentions() && bp_is_user_active();
	$load_mentions    = $mentions_enabled && ( bp_is_activity_component() || is_admin() );

	/**
	 * Filters whether or not BuddyPress should load mentions scripts and assets.
	 *
	 * @since BuddyPress 2.1.0
	 *
	 * @param bool $load_mentions    True to load mentions assets, false otherwise.
	 * @param bool $mentions_enabled True if mentions are enabled.
	 */
	return (bool) apply_filters( 'bp_activity_maybe_load_mentions_scripts', $load_mentions, $mentions_enabled );
}

/**
 * Find mentioned users from activity content
 *
 * @since BuddyPress 1.5.0
 * @version Buddyboss 1.2.0
 *
 * @param string $content The content of the activity, usually found in
 *                        $activity->content.
 * @return array|bool Associative array with user ID as key and username as
 *                    value. Boolean false if no mentions found.
 */
function bp_activity_find_mentions( $content ) {

	/**
	 * Filters the mentioned users.
	 *
	 * @since BuddyPress 2.5.0
	 * @version Buddyboss 1.2.0
	 *
	 * @param array $mentioned_users Associative array with user IDs as keys and usernames as values.
	 * @param string $content Activity content
	 */
	return apply_filters( 'bp_activity_mentioned_users', array(), $content );
}

/**
 * Locate usernames in an activity content string, as designated by an @ sign.
 *
 * @since Buddyboss 1.2.0
 * @version  Buddyboss 1.2.0
 * @deprecated BuddyBoss 1.2.8
 *
 * @param  array  $mentioned_users Associative array with user IDs as keys and usernames as values.
 * @param string $content Activity content.
 * @return array|bool Associative array with user ID as key and username as
 *                    value. Boolean false if no mentions found.
 */
function bp_activity_find_mention_by_at_sign( $mentioned_users, $content ) {
	$pattern = '/[@]+([A-Za-z0-9-_\.@]+)\b/';
	preg_match_all( $pattern, $content, $usernames );

	// Make sure there's only one instance of each username.
	$usernames = array_unique( $usernames[1] );

	// Bail if no usernames.
	if ( empty( $usernames ) ) {
		return $mentioned_users;
	}

	// We've found some mentions! Check to see if users exist.
	foreach ( (array) array_values( $usernames ) as $username ) {
		$user_id = bp_activity_get_userid_from_mentionname( $username );

		// The user ID exists, so let's add it to our array.
		if ( ! empty( $user_id ) ) {
			$mentioned_users[ $user_id ] = $username;
		}
	}

	if ( empty( $mentioned_users ) ) {
		return $mentioned_users;
	}

	return $mentioned_users;
}

/**
 * Reset a user's unread mentions list and count.
 *
 * @since BuddyPress 1.5.0
 *
 * @param int $user_id The id of the user whose unread mentions are being reset.
 */
function bp_activity_clear_new_mentions( $user_id ) {
	bp_delete_user_meta( $user_id, 'bp_new_mention_count' );
	bp_delete_user_meta( $user_id, 'bp_new_mentions' );

	/**
	 * Fires once mentions has been reset for a given user.
	 *
	 * @since BuddyPress  2.5.0
	 *
	 * @param  int $user_id The id of the user whose unread mentions are being reset.
	 */
	do_action( 'bp_activity_clear_new_mentions', $user_id );
}

/**
 * Adjusts mention count for mentioned users in activity items.
 *
 * This function is useful if you only have the activity ID handy and you
 * haven't parsed an activity item for @mentions yet.
 *
 * Currently, only used in {@link bp_activity_delete()}.
 *
 * @since BuddyPress 1.5.0
 *
 * @param int    $activity_id The unique id for the activity item.
 * @param string $action      Can be 'delete' or 'add'. Defaults to 'add'.
 * @return bool
 */
function bp_activity_adjust_mention_count( $activity_id = 0, $action = 'add' ) {

	// Bail if no activity ID passed.
	if ( empty( $activity_id ) ) {
		return false;
	}

	// Get activity object.
	$activity = new BP_Activity_Activity( $activity_id );

	// Try to find mentions.
	$usernames = bp_activity_find_mentions( strip_tags( $activity->content ) );

	// Still empty? Stop now.
	if ( empty( $usernames ) ) {
		return false;
	}

	// Increment mention count foreach mentioned user.
	foreach ( (array) array_keys( $usernames ) as $user_id ) {
		bp_activity_update_mention_count_for_user( $user_id, $activity_id, $action );
	}
}

/**
 * Update the mention count for a given user.
 *
 * This function should be used when you've already parsed your activity item
 * for @mentions.
 *
 * @since BuddyPress 1.7.0
 *
 * @param int    $user_id     The user ID.
 * @param int    $activity_id The unique ID for the activity item.
 * @param string $action      'delete' or 'add'. Default: 'add'.
 * @return bool
 */
function bp_activity_update_mention_count_for_user( $user_id, $activity_id, $action = 'add' ) {

	if ( empty( $user_id ) || empty( $activity_id ) ) {
		return false;
	}

	// Adjust the mention list and count for the member.
	$new_mention_count = (int) bp_get_user_meta( $user_id, 'bp_new_mention_count', true );
	$new_mentions      = bp_get_user_meta( $user_id, 'bp_new_mentions', true );

	// Make sure new mentions is an array.
	if ( empty( $new_mentions ) ) {
		$new_mentions = array();
	}

	switch ( $action ) {
		case 'delete':
			$key = array_search( $activity_id, $new_mentions );

			if ( $key !== false ) {
				unset( $new_mentions[ $key ] );
			}

			break;

		case 'add':
		default:
			if ( ! in_array( $activity_id, $new_mentions ) ) {
				$new_mentions[] = (int) $activity_id;
			}

			break;
	}

	// Get an updated mention count.
	$new_mention_count = count( $new_mentions );

	// Resave the user_meta.
	bp_update_user_meta( $user_id, 'bp_new_mention_count', $new_mention_count );
	bp_update_user_meta( $user_id, 'bp_new_mentions', $new_mentions );

	return true;
}

/**
 * Get a user ID from a "mentionname", the name used for a user in @-mentions.
 *
 * @since BuddyPress 1.9.0
 * @deprecated BuddyBoss 1.2.8
 *
 * @param string $mentionname Username of user in @-mentions.
 * @return int|bool ID of the user, if one is found. Otherwise false.
 */
function bp_activity_get_userid_from_mentionname( $mentionname ) {
	$user_id = false;

	/*
	 * In username compatibility mode, hyphens are ambiguous between
	 * actual hyphens and converted spaces.
	 *
	 * @todo There is the potential for username clashes between 'foo bar'
	 * and 'foo-bar' in compatibility mode. Come up with a system for
	 * unique mentionnames.
	 */
	if ( bp_is_username_compatibility_mode() ) {
		// First, try the raw username.
		$userdata = get_user_by( 'login', $mentionname );

		// Doing a direct query to use proper regex. Necessary to
		// account for hyphens + spaces in the same user_login.
		if ( empty( $userdata ) || ! is_a( $userdata, 'WP_User' ) ) {
			global $wpdb;
			$regex   = esc_sql( str_replace( '-', '[ \-]', $mentionname ) );
			$user_id = $wpdb->get_var( "SELECT ID FROM {$wpdb->users} WHERE user_login REGEXP '{$regex}'" );
		} else {
			$user_id = $userdata->ID;
		}

		// When username compatibility mode is disabled, the mentionname is
		// the same as the nicename.
	} else {
		$user_id = bp_core_get_userid_from_nickname( $mentionname );
	}

	return $user_id;
}

/** Actions ******************************************************************/

/**
 * Register an activity 'type' and its action description/callback.
 *
 * Activity actions are strings used to describe items in the activity feed,
 * such as 'Joe became a registered member' or 'Bill and Susie are now
 * friends'. Each activity type (such as 'new_member' or 'friendship_created')
 * used by a component should be registered using this function.
 *
 * While it's possible to post items to the activity feed whose types are
 * not registered using bp_activity_set_action(), it is not recommended;
 * unregistered types will not be displayed properly in the activity admin
 * panel, and dynamic action generation (which is essential for multilingual
 * sites, etc) will not work.
 *
 * @since BuddyPress 1.1.0
 *
 * @param  string        $component_id    The unique string ID of the component.
 * @param  string        $type            The action type.
 * @param  string        $description     The action description.
 * @param  callable|bool $format_callback Callback for formatting the action string.
 * @param  string|bool   $label           String to describe this action in the activity feed filter dropdown.
 * @param  array         $context         Optional. Activity feed contexts where the filter should appear.
 *                                        Values: 'activity', 'member', 'member_groups', 'group'.
 * @param  int           $position        Optional. The position of the action when listed in dropdowns.
 * @return bool False if any param is empty, otherwise true.
 */
function bp_activity_set_action( $component_id, $type, $description, $format_callback = false, $label = false, $context = array(), $position = 0 ) {
	$bp = buddypress();

	// Return false if any of the above values are not set.
	if ( empty( $component_id ) || empty( $type ) || empty( $description ) ) {
		return false;
	}

	// Set activity action.
	if ( ! isset( $bp->activity->actions ) || ! is_object( $bp->activity->actions ) ) {
		$bp->activity->actions = new stdClass();
	}

	// Verify callback.
	if ( ! is_callable( $format_callback ) ) {
		$format_callback = '';
	}

	if ( ! isset( $bp->activity->actions->{$component_id} ) || ! is_object( $bp->activity->actions->{$component_id} ) ) {
		$bp->activity->actions->{$component_id} = new stdClass();
	}

	/**
	 * Filters the action type being set for the current activity item.
	 *
	 * @since BuddyPress 1.1.0
	 *
	 * @param array    $array           Array of arguments for action type being set.
	 * @param string   $component_id    ID of the current component being set.
	 * @param string   $type            Action type being set.
	 * @param string   $description     Action description for action being set.
	 * @param callable $format_callback Callback for formatting the action string.
	 * @param string   $label           String to describe this action in the activity feed filter dropdown.
	 * @param array    $context         Activity feed contexts where the filter should appear. 'activity', 'member',
	 *                                  'member_groups', 'group'.
	 */
	$bp->activity->actions->{$component_id}->{$type} = apply_filters(
		'bp_activity_set_action',
		array(
			'key'             => $type,
			'value'           => $description,
			'format_callback' => $format_callback,
			'label'           => $label,
			'context'         => $context,
			'position'        => $position,
		),
		$component_id,
		$type,
		$description,
		$format_callback,
		$label,
		$context
	);

	// Sort the actions of the affected component.
	$action_array = (array) $bp->activity->actions->{$component_id};
	$action_array = bp_sort_by_key( $action_array, 'position', 'num' );

	// Restore keys.
	$bp->activity->actions->{$component_id} = new stdClass();
	foreach ( $action_array as $key_ordered ) {
		$bp->activity->actions->{$component_id}->{$key_ordered['key']} = $key_ordered;
	}

	return true;
}

/**
 * Set tracking arguments for a given post type.
 *
 * @since BuddyPress 2.2.0
 *
 * @global $wp_post_types
 *
 * @param string $post_type The name of the post type, as registered with WordPress. Eg 'post' or 'page'.
 * @param array  $args {
 *     An associative array of tracking parameters. All items are optional.
 *     @type string   $bp_activity_admin_filter String to use in the Dashboard > Activity dropdown.
 *     @type string   $bp_activity_front_filter String to use in the front-end dropdown.
 *     @type string   $bp_activity_new_post     String format to use for generating the activity action. Should be a
 *                                              translatable string where %1$s is replaced by a user link and %2$s is
 *                                              the URL of the newly created post.
 *     @type string   $bp_activity_new_post_ms  String format to use for generating the activity action on Multisite.
 *                                              Should be a translatable string where %1$s is replaced by a user link,
 *                                              %2$s is the URL of the newly created post, and %3$s is a link to
 *                                              the site.
 *     @type string   $component_id             ID of the BuddyPress component to associate the activity item.
 *     @type string   $action_id                Value for the 'type' param of the new activity item.
 *     @type callable $format_callback          Callback for formatting the activity action string.
 *                                              Default: 'bp_activity_format_activity_action_custom_post_type_post'.
 *     @type array    $contexts                 The directory contexts in which the filter will show.
 *                                              Default: array( 'activity' ).
 *     @type array    $position                 Position of the item in filter dropdowns.
 *     @type string   $singular                 Singular, translatable name of the post type item. If no value is
 *                                              provided, it's pulled from the 'singular_name' of the post type.
 *     @type bool     $activity_comment         Whether to allow comments on the activity items. Defaults to true if
 *                                              the post type does not natively support comments, otherwise false.
 * }
 * @return bool
 */
function bp_activity_set_post_type_tracking_args( $post_type = '', $args = array() ) {
	global $wp_post_types;

	if ( empty( $wp_post_types[ $post_type ] ) || ! post_type_supports( $post_type, 'buddypress-activity' ) || ! is_array( $args ) ) {
		return false;
	}

	$activity_labels = array(
		/* Post labels */
		'bp_activity_admin_filter',
		'bp_activity_front_filter',
		'bp_activity_new_post',
		'bp_activity_new_post_ms',
		/* Comment labels */
		'bp_activity_comments_admin_filter',
		'bp_activity_comments_front_filter',
		'bp_activity_new_comment',
		'bp_activity_new_comment_ms',
	);

	// Labels are loaded into the post type object.
	foreach ( $activity_labels as $label_type ) {
		if ( ! empty( $args[ $label_type ] ) ) {
			$wp_post_types[ $post_type ]->labels->{$label_type} = $args[ $label_type ];
			unset( $args[ $label_type ] );
		}
	}

	// If there are any additional args, put them in the bp_activity attribute of the post type.
	if ( ! empty( $args ) ) {
		$wp_post_types[ $post_type ]->bp_activity = $args;
	}
}

/**
 * Get tracking arguments for a specific post type.
 *
 * @since BuddyPress 2.2.0
 * @since BuddyPress 2.5.0 Add post type comments tracking args
 *
 * @param  string $post_type Name of the post type.
 * @return object The tracking arguments of the post type.
 */
function bp_activity_get_post_type_tracking_args( $post_type ) {
	if ( ! post_type_supports( $post_type, 'buddypress-activity' ) ) {
		return false;
	}

	$post_type_object           = get_post_type_object( $post_type );
	$post_type_support_comments = post_type_supports( $post_type, 'comments' );

	$post_type_activity = array(
		'component_id'            => buddypress()->activity->id,
		'action_id'               => 'new_' . $post_type,
		'format_callback'         => 'bp_activity_format_activity_action_custom_post_type_post',
		'front_filter'            => $post_type_object->labels->name,
		'contexts'                => array( 'activity' ),
		'position'                => 0,
		'singular'                => strtolower( $post_type_object->labels->singular_name ),
		'activity_comment'        => ! $post_type_support_comments,
		'comment_action_id'       => false,
		'comment_format_callback' => 'bp_activity_format_activity_action_custom_post_type_comment',
	);

	if ( ! empty( $post_type_object->bp_activity ) ) {
		$post_type_activity = bp_parse_args( (array) $post_type_object->bp_activity, $post_type_activity, $post_type . '_tracking_args' );
	}

	$post_type_activity = (object) $post_type_activity;

	// Try to get the admin filter from the post type labels.
	if ( ! empty( $post_type_object->labels->bp_activity_admin_filter ) ) {
		$post_type_activity->admin_filter = $post_type_object->labels->bp_activity_admin_filter;

		// Fall back to a generic name.
	} else {
		$post_type_activity->admin_filter = __( 'New item published', 'buddyboss' );
	}

	// Check for the front filter in the post type labels.
	if ( ! empty( $post_type_object->labels->bp_activity_front_filter ) ) {
		$post_type_activity->front_filter = $post_type_object->labels->bp_activity_front_filter;
	}

	// Try to get the action for new post type action on non-multisite installations.
	if ( ! empty( $post_type_object->labels->bp_activity_new_post ) ) {
		$post_type_activity->new_post_type_action = $post_type_object->labels->bp_activity_new_post;
	}

	// Try to get the action for new post type action on multisite installations.
	if ( ! empty( $post_type_object->labels->bp_activity_new_post_ms ) ) {
		$post_type_activity->new_post_type_action_ms = $post_type_object->labels->bp_activity_new_post_ms;
	}

	// If the post type supports comments and has a comment action id, build the comments tracking args
	if ( $post_type_support_comments && ! empty( $post_type_activity->comment_action_id ) ) {
		// Init a new container for the activity type for comments
		$post_type_activity->comments_tracking = new stdClass();

		// Build the activity type for comments
		$post_type_activity->comments_tracking->component_id = $post_type_activity->component_id;
		$post_type_activity->comments_tracking->action_id    = $post_type_activity->comment_action_id;

		// Try to get the comments admin filter from the post type labels.
		if ( ! empty( $post_type_object->labels->bp_activity_comments_admin_filter ) ) {
			$post_type_activity->comments_tracking->admin_filter = $post_type_object->labels->bp_activity_comments_admin_filter;

			// Fall back to a generic name.
		} else {
			$post_type_activity->comments_tracking->admin_filter = __( 'New item comment posted', 'buddyboss' );
		}

		$post_type_activity->comments_tracking->format_callback = $post_type_activity->comment_format_callback;

		// Check for the comments front filter in the post type labels.
		if ( ! empty( $post_type_object->labels->bp_activity_comments_front_filter ) ) {
			$post_type_activity->comments_tracking->front_filter = $post_type_object->labels->bp_activity_comments_front_filter;

			// Fall back to a generic name.
		} else {
			$post_type_activity->comments_tracking->front_filter = __( 'Item comments', 'buddyboss' );
		}

		$post_type_activity->comments_tracking->contexts = $post_type_activity->contexts;
		$post_type_activity->comments_tracking->position = (int) $post_type_activity->position + 1;

		// Try to get the action for new post type comment action on non-multisite installations.
		if ( ! empty( $post_type_object->labels->bp_activity_new_comment ) ) {
			$post_type_activity->comments_tracking->new_post_type_comment_action = $post_type_object->labels->bp_activity_new_comment;
		}

		// Try to get the action for new post type comment action on multisite installations.
		if ( ! empty( $post_type_object->labels->bp_activity_new_comment_ms ) ) {
			$post_type_activity->comments_tracking->new_post_type_comment_action_ms = $post_type_object->labels->bp_activity_new_comment_ms;
		}
	}

	// Finally make sure we'll be able to find the post type this activity type is associated to.
	$post_type_activity->post_type = $post_type;

	/**
	 * Filters tracking arguments for a specific post type.
	 *
	 * @since BuddyPress 2.2.0
	 *
	 * @param object $post_type_activity The tracking arguments of the post type.
	 * @param string $post_type          Name of the post type.
	 */
	return apply_filters( 'bp_activity_get_post_type_tracking_args', $post_type_activity, $post_type );
}

/**
 * Get tracking arguments for all post types.
 *
 * @since BuddyPress 2.2.0
 * @since BuddyPress 2.5.0 Include post type comments tracking args if needed
 *
 * @return array List of post types with their tracking arguments.
 */
function bp_activity_get_post_types_tracking_args() {
	// Fetch all public post types.
	$post_types = get_post_types( array( 'public' => true ), 'names' );

	$post_types_tracking_args = array();

	foreach ( $post_types as $post_type ) {
		$track_post_type = bp_activity_get_post_type_tracking_args( $post_type );

		if ( ! empty( $track_post_type ) ) {
			// Set the post type comments tracking args
			if ( ! empty( $track_post_type->comments_tracking->action_id ) ) {
				// Used to check support for comment tracking by activity type (new_post_type_comment)
				$track_post_type->comments_tracking->comments_tracking = true;

				// Used to be able to find the post type this activity type is associated to.
				$track_post_type->comments_tracking->post_type = $post_type;

				$post_types_tracking_args[ $track_post_type->comments_tracking->action_id ] = $track_post_type->comments_tracking;

				// Used to check support for comment tracking by activity type (new_post_type)
				$track_post_type->comments_tracking = true;
			}

			$post_types_tracking_args[ $track_post_type->action_id ] = $track_post_type;
		}
	}

	/**
	 * Filters tracking arguments for all post types.
	 *
	 * @since BuddyPress 2.2.0
	 *
	 * @param array $post_types_tracking_args Array of post types with
	 *                                        their tracking arguments.
	 */
	return apply_filters( 'bp_activity_get_post_types_tracking_args', $post_types_tracking_args );
}

/**
 * Check if the *Post Type* activity supports a specific feature.
 *
 * @since BuddyPress 2.5.0
 *
 * @param  string $activity_type The activity type to check.
 * @param  string $feature       The feature to check. Currently supports:
 *                               'post-type-comment-tracking', 'post-type-comment-reply' & 'comment-reply'.
 *                               See inline doc for more info.
 * @return bool
 */
function bp_activity_type_supports( $activity_type = '', $feature = '' ) {
	$retval = false;

	$bp = buddypress();

	switch ( $feature ) {
		/**
		 * Does this activity type support comment tracking?
		 *
		 * eg. 'new_blog_post' and 'new_blog_comment' will both return true.
		 */
		case 'post-type-comment-tracking':
			// Set the activity track global if not set yet
			if ( empty( $bp->activity->track ) ) {
				$bp->activity->track = bp_activity_get_post_types_tracking_args();
			}

			if ( ! empty( $bp->activity->track[ $activity_type ]->comments_tracking ) ) {
				$retval = true;
			}
			break;

		/**
		 * Is this a parent activity type that support post comments?
		 *
		 * eg. 'new_blog_post' will return true; 'new_blog_comment' will return false.
		 */
		case 'post-type-comment-reply':
			// Set the activity track global if not set yet.
			if ( empty( $bp->activity->track ) ) {
				$bp->activity->track = bp_activity_get_post_types_tracking_args();
			}

			if ( ! empty( $bp->activity->track[ $activity_type ]->comments_tracking ) && ! empty( $bp->activity->track[ $activity_type ]->comment_action_id ) ) {
				$retval = true;
			}
			break;

		/**
		 * Does this activity type support comment & reply?
		 */
		case 'comment-reply':
			// Set the activity track global if not set yet.
			if ( empty( $bp->activity->track ) ) {
				$bp->activity->track = bp_activity_get_post_types_tracking_args();
			}

			// Post Type activities.
			if ( ! empty( $bp->activity->track[ $activity_type ] ) ) {
				if ( isset( $bp->activity->track[ $activity_type ]->activity_comment ) ) {
					$retval = $bp->activity->track[ $activity_type ]->activity_comment;
				}

				// Eventually override with comment synchronization feature.
				if ( isset( $bp->activity->track[ $activity_type ]->comments_tracking ) ) {
					$retval = $bp->activity->track[ $activity_type ]->comments_tracking && ! bp_disable_blogforum_comments();
				}

				// Retired Forums component.
			} elseif ( 'new_forum_topic' === $activity_type || 'new_forum_post' === $activity_type ) {
				$retval = ! bp_disable_blogforum_comments();

				// Comment is disabled for discussion and reply discussion.
			} elseif ( 'bbp_topic_create' === $activity_type || 'bbp_reply_create' === $activity_type ) {
				$retval = false;

				// By Default, all other activity types are supporting comments.
			} else {
				$retval = true;
			}
			break;
	}

	return $retval;
}

/**
 * Get a specific tracking argument for a given activity type
 *
 * @since BuddyPress 2.5.0
 *
 * @param  string $activity_type the activity type.
 * @param  string $arg           the key of the tracking argument.
 * @return mixed        the value of the tracking arg, false if not found.
 */
function bp_activity_post_type_get_tracking_arg( $activity_type, $arg = '' ) {
	if ( empty( $activity_type ) || empty( $arg ) ) {
		return false;
	}

	$bp = buddypress();

	// Set the activity track global if not set yet
	if ( empty( $bp->activity->track ) ) {
		$bp->activity->track = bp_activity_get_post_types_tracking_args();
	}

	if ( isset( $bp->activity->track[ $activity_type ]->{$arg} ) ) {
		return $bp->activity->track[ $activity_type ]->{$arg};
	} else {
		return false;
	}
}

/**
 * Get all components' activity actions, sorted by their position attribute.
 *
 * @since BuddyPress 2.2.0
 *
 * @return object Actions ordered by their position.
 */
function bp_activity_get_actions() {
	$bp = buddypress();

	// Set the activity track global if not set yet.
	if ( empty( $bp->activity->track ) ) {
		$bp->activity->track = bp_activity_get_post_types_tracking_args();
	}

	// Create the actions for the post types, if they haven't already been created.
	if ( ! empty( $bp->activity->track ) ) {
		foreach ( $bp->activity->track as $post_type ) {
			if ( isset( $bp->activity->actions->{$post_type->component_id}->{$post_type->action_id} ) ) {
				continue;
			}

			bp_activity_set_action(
				$post_type->component_id,
				$post_type->action_id,
				$post_type->admin_filter,
				$post_type->format_callback,
				$post_type->front_filter,
				$post_type->contexts,
				$post_type->position
			);
		}
	}

	return $bp->activity->actions;
}

/**
 * Retrieve the current action from a component and key.
 *
 * @since BuddyPress 1.1.0
 *
 * @param string $component_id The unique string ID of the component.
 * @param string $key          The action key.
 * @return string|bool Action value if found, otherwise false.
 */
function bp_activity_get_action( $component_id, $key ) {

	// Return false if any of the above values are not set.
	if ( empty( $component_id ) || empty( $key ) ) {
		return false;
	}

	$actions = bp_activity_get_actions();
	$retval  = false;

	if ( isset( $actions->{$component_id}->{$key} ) ) {
		$retval = $actions->{$component_id}->{$key};
	}

	/**
	 * Filters the current action by component and key.
	 *
	 * @since BuddyPress 1.1.0
	 *
	 * @param string|bool $retval       The action key.
	 * @param string      $component_id The unique string ID of the component.
	 * @param string      $key          The action key.
	 */
	return apply_filters( 'bp_activity_get_action', $retval, $component_id, $key );
}

/**
 * Fetch details of all registered activity types.
 *
 * @since BuddyPress 1.7.0
 *
 * @return array array( type => description ), ...
 */
function bp_activity_get_types() {
	$actions = array();

	// Walk through the registered actions, and build an array of actions/values.
	foreach ( bp_activity_get_actions() as $action ) {
		$action = array_values( (array) $action );

		for ( $i = 0, $i_count = count( $action ); $i < $i_count; $i++ ) {
			$actions[ $action[ $i ]['key'] ] = $action[ $i ]['value'];
		}
	}

	// This was a mis-named activity type from before BP 1.6.
	unset( $actions['friends_register_activity_action'] );

	/**
	 * Filters the available activity types.
	 *
	 * @since BuddyPress 1.7.0
	 *
	 * @param array $actions Array of registered activity types.
	 */
	return apply_filters( 'bp_activity_get_types', $actions );
}

/**
 * Gets the current activity context.
 *
 * The "context" is the current view type, corresponding roughly to the
 * current component. Use this context to determine which activity actions
 * should be whitelisted for the filter dropdown.
 *
 * @since BuddyPress 2.8.0
 *
 * @return string Activity context. 'member', 'member_groups', 'group', 'activity'.
 */
function bp_activity_get_current_context() {
	// On member pages, default to 'member', unless this is a user's Groups activity.
	if ( bp_is_user() ) {
		if ( bp_is_active( 'groups' ) && bp_is_current_action( bp_get_groups_slug() ) ) {
			$context = 'member_groups';
		} else {
			$context = 'member';
		}

		// On individual group pages, default to 'group'.
	} elseif ( bp_is_active( 'groups' ) && bp_is_group() ) {
		$context = 'group';

		// 'activity' everywhere else.
	} else {
		$context = 'activity';
	}

	return $context;
}

/**
 * Gets a flat list of activity actions compatible with a given context.
 *
 * @since BuddyPress 2.8.0
 *
 * @param string $context Optional. Name of the context. Defaults to the current context.
 * @return array
 */
function bp_activity_get_actions_for_context( $context = '' ) {
	if ( ! $context ) {
		$context = bp_activity_get_current_context();
	}

	$actions = array();
	foreach ( bp_activity_get_actions() as $component_actions ) {
		foreach ( $component_actions as $component_action ) {
			if ( in_array( $context, (array) $component_action['context'], true ) ) {
				$actions[] = $component_action;
			}
		}
	}

	return $actions;
}

/** Favorites ****************************************************************/

/**
 * Get a users favorite activity feed items.
 *
 * @since BuddyPress 1.2.0
 *
 * @param int $user_id ID of the user whose favorites are being queried.
 * @return array IDs of the user's favorite activity items.
 */
function bp_activity_get_user_favorites( $user_id = 0 ) {

	// Fallback to logged in user if no user_id is passed.
	if ( empty( $user_id ) ) {
		$user_id = bp_displayed_user_id();
	}

	// Get favorites for user.
	$favs = bp_get_user_meta( $user_id, 'bp_favorite_activities', true );

	/**
	 * Filters the favorited activity items for a specified user.
	 *
	 * @since BuddyPress 1.2.0
	 *
	 * @param array $favs Array of user's favorited activity items.
	 */
	return apply_filters( 'bp_activity_get_user_favorites', $favs );
}

/**
 * Add an activity feed item as a favorite for a user.
 *
 * @since BuddyPress 1.2.0
 *
 * @param int $activity_id ID of the activity item being favorited.
 * @param int $user_id     ID of the user favoriting the activity item.
 * @return bool True on success, false on failure.
 */
function bp_activity_add_user_favorite( $activity_id, $user_id = 0 ) {

	// Fallback to logged in user if no user_id is passed.
	if ( empty( $user_id ) ) {
		$user_id = bp_loggedin_user_id();
	}

	$my_favs = bp_get_user_meta( $user_id, 'bp_favorite_activities', true );
	if ( empty( $my_favs ) || ! is_array( $my_favs ) ) {
		$my_favs = array();
	}

	// Bail if the user has already favorited this activity item.
	if ( in_array( $activity_id, $my_favs ) ) {
		return false;
	}

	// Add to user's favorites.
	$my_favs[] = $activity_id;

	// Update the total number of users who have favorited this activity.
	$fav_count = bp_activity_get_meta( $activity_id, 'favorite_count' );
	$fav_count = ! empty( $fav_count ) ? (int) $fav_count + 1 : 1;

	// Update the users who have favorited this activity.
	$users = bp_activity_get_meta( $activity_id, 'bp_favorite_users', true );
	if ( empty( $users ) || ! is_array( $users ) ) {
		$users = array();
	}
	// Add to activity's favorited users.
	$users[] = $user_id;

	// Update user meta.
	bp_update_user_meta( $user_id, 'bp_favorite_activities', array_unique( $my_favs ) );

	// Update activity meta
	bp_activity_update_meta( $activity_id, 'bp_favorite_users', array_unique( $users ) );

	// Update activity meta counts.
	if ( bp_activity_update_meta( $activity_id, 'favorite_count', $fav_count ) ) {

		/**
		 * Fires if bp_activity_update_meta() for favorite_count is successful and before returning a true value for success.
		 *
		 * @since BuddyPress 1.2.1
		 *
		 * @param int $activity_id ID of the activity item being favorited.
		 * @param int $user_id     ID of the user doing the favoriting.
		 */
		do_action( 'bp_activity_add_user_favorite', $activity_id, $user_id );

		// Add user reaction.
		if ( function_exists( 'bb_load_reaction' ) ) {
			$reaction_id = bb_load_reaction()->bb_reactions_get_like_reaction_id();
			bb_load_reaction()->bb_add_user_item_reaction(
				array(
					'item_type'   => 'activity',
					'reaction_id' => $reaction_id,
					'item_id'     => $activity_id,
				)
			);
		}

		// Success.
		return true;

		// Saving meta was unsuccessful for an unknown reason.
	} else {

		/**
		 * Fires if bp_activity_update_meta() for favorite_count is unsuccessful and before returning a false value for failure.
		 *
		 * @since BuddyPress 1.5.0
		 *
		 * @param int $activity_id ID of the activity item being favorited.
		 * @param int $user_id     ID of the user doing the favoriting.
		 */
		do_action( 'bp_activity_add_user_favorite_fail', $activity_id, $user_id );

		return false;
	}
}

/**
 * Remove an activity feed item as a favorite for a user.
 *
 * @since BuddyPress 1.2.0
 *
 * @param int $activity_id ID of the activity item being unfavorited.
 * @param int $user_id     ID of the user unfavoriting the activity item.
 * @return bool True on success, false on failure.
 */
function bp_activity_remove_user_favorite( $activity_id, $user_id = 0 ) {

	// Fallback to logged in user if no user_id is passed.
	if ( empty( $user_id ) ) {
		$user_id = bp_loggedin_user_id();
	}

	$my_favs = bp_get_user_meta( $user_id, 'bp_favorite_activities', true );
	$my_favs = array_flip( (array) $my_favs );

	// Bail if the user has not previously favorited the item.
	if ( ! isset( $my_favs[ $activity_id ] ) ) {
		return false;
	}

	// Remove the fav from the user's favs.
	unset( $my_favs[ $activity_id ] );
	$my_favs = array_unique( array_flip( $my_favs ) );

	// Update the total number of users who have favorited this activity.
	$fav_count = bp_activity_get_meta( $activity_id, 'favorite_count' );

	// Update the users who have favorited this activity.
	$users = bp_activity_get_meta( $activity_id, 'bp_favorite_users', true );
	if ( empty( $users ) || ! is_array( $users ) ) {
		$users = array();
	}

	if ( in_array( $user_id, $users ) ) {
		$pos = array_search( $user_id, $users );
		unset( $users[ $pos ] );
	}

	// Update activity meta
	bp_activity_update_meta( $activity_id, 'bp_favorite_users', array_unique( $users ) );

	if ( ! empty( $fav_count ) ) {

		// Deduct from total favorites.
		if ( bp_activity_update_meta( $activity_id, 'favorite_count', (int) $fav_count - 1 ) ) {

			// Update users favorites.
			if ( bp_update_user_meta( $user_id, 'bp_favorite_activities', $my_favs ) ) {

				/**
				 * Fires if bp_update_user_meta() is successful and before returning a true value for success.
				 *
				 * @since BuddyPress 1.2.1
				 *
				 * @param int $activity_id ID of the activity item being unfavorited.
				 * @param int $user_id     ID of the user doing the unfavoriting.
				 */
				do_action( 'bp_activity_remove_user_favorite', $activity_id, $user_id );

				// Remove user reaction.
				if ( function_exists( 'bb_load_reaction' ) ) {
					$reaction_id = bb_load_reaction()->bb_reactions_get_like_reaction_id();
					bb_load_reaction()->bb_remove_user_item_reactions(
						array(
							'item_id'     => $activity_id,
							'item_type'   => 'activity',
							'user_id'     => $user_id,
							'reaction_id' => $reaction_id,
						)
					);
				}

				// Success.
				return true;

				// Error updating.
			} else {
				return false;
			}

			// Error updating favorite count.
		} else {
			return false;
		}

		// Error getting favorite count.
	} else {
		return false;
	}
}

/**
 * Get like count for activity
 *
 * @since BuddyBoss 1.0.0
 *
 * @param $activity_id
 *
 * @return int|string
 */
function bp_activity_get_favorite_users_string( $activity_id ) {

	if ( ! bp_is_activity_like_active() ) {
		return 0;
	}

	$like_count      = bp_activity_get_meta( $activity_id, 'favorite_count', true );
	$like_count      = ( isset( $like_count ) && ! empty( $like_count ) ) ? $like_count : 0;
	$favorited_users = bp_activity_get_meta( $activity_id, 'bp_favorite_users', true );

	if ( empty( $favorited_users ) || ! is_array( $favorited_users ) ) {
		return 0;
	}

	if ( $like_count > sizeof( $favorited_users ) ) {
		$like_count = sizeof( $favorited_users );
	}

	$current_user_fav = false;
	if ( bp_loggedin_user_id() && in_array( bp_loggedin_user_id(), $favorited_users ) ) {
		$current_user_fav = true;
		if ( sizeof( $favorited_users ) > 1 ) {
			$pos = array_search( bp_loggedin_user_id(), $favorited_users );
			unset( $favorited_users[ $pos ] );
		}
	}

	$return_str = '';
	if ( 1 == $like_count ) {
		if ( $current_user_fav ) {
			$return_str = __( 'You like this', 'buddyboss' );
		} else {
			$user_data         = get_userdata( array_pop( $favorited_users ) );
			$user_display_name = ! empty( $user_data ) ? bp_core_get_user_displayname( $user_data->ID ) : __( 'Unknown', 'buddyboss' );
			$return_str        = $user_display_name . ' ' . __( 'likes this', 'buddyboss' );
		}
	} elseif ( 2 == $like_count ) {
		if ( $current_user_fav ) {
			$return_str .= __( 'You and', 'buddyboss' ) . ' ';

			$user_data         = get_userdata( array_pop( $favorited_users ) );
			$user_display_name = ! empty( $user_data ) ? bp_core_get_user_displayname( $user_data->ID ) : __( 'Unknown', 'buddyboss' );
			$return_str       .= $user_display_name . ' ' . __( 'like this', 'buddyboss' );
		} else {
			$user_data         = get_userdata( array_pop( $favorited_users ) );
			$user_display_name = ! empty( $user_data ) ? bp_core_get_user_displayname( $user_data->ID ) : __( 'Unknown', 'buddyboss' );
			$return_str       .= $user_display_name . ' ' . __( 'and', 'buddyboss' ) . ' ';

			$user_data         = get_userdata( array_pop( $favorited_users ) );
			$user_display_name = ! empty( $user_data ) ? bp_core_get_user_displayname( $user_data->ID ) : __( 'Unknown', 'buddyboss' );
			$return_str       .= $user_display_name . ' ' . __( 'like this', 'buddyboss' );
		}
	} elseif ( 3 == $like_count ) {

		if ( $current_user_fav ) {
			$return_str .= __( 'You,', 'buddyboss' ) . ' ';

			$user_data         = get_userdata( array_pop( $favorited_users ) );
			$user_display_name = ! empty( $user_data ) ? bp_core_get_user_displayname( $user_data->ID ) : __( 'Unknown', 'buddyboss' );
			$return_str       .= $user_display_name . ' ' . __( 'and', 'buddyboss' ) . ' ';

			$return_str .= ' ' . __( '1 other like this', 'buddyboss' );
		} else {

			$user_data         = get_userdata( array_pop( $favorited_users ) );
			$user_display_name = ! empty( $user_data ) ? bp_core_get_user_displayname( $user_data->ID ) : __( 'Unknown', 'buddyboss' );
			$return_str       .= $user_display_name . ', ';

			$user_data         = get_userdata( array_pop( $favorited_users ) );
			$user_display_name = ! empty( $user_data ) ? bp_core_get_user_displayname( $user_data->ID ) : __( 'Unknown', 'buddyboss' );
			$return_str       .= $user_display_name . ' ' . __( 'and', 'buddyboss' ) . ' ';

			$return_str .= ' ' . __( '1 other like this', 'buddyboss' );
		}
	} elseif ( 3 < $like_count ) {

		$like_count = ( isset( $like_count ) && ! empty( $like_count ) ) ? (int) $like_count - 2 : 0;

		if ( $current_user_fav ) {
			$return_str .= __( 'You,', 'buddyboss' ) . ' ';

			$user_data         = get_userdata( array_pop( $favorited_users ) );
			$user_display_name = ! empty( $user_data ) ? bp_core_get_user_displayname( $user_data->ID ) : __( 'Unknown', 'buddyboss' );
			$return_str       .= $user_display_name . ' ' . __( 'and', 'buddyboss' ) . ' ';
		} else {
			$user_data         = get_userdata( array_pop( $favorited_users ) );
			$user_display_name = ! empty( $user_data ) ? bp_core_get_user_displayname( $user_data->ID ) : __( 'Unknown', 'buddyboss' );
			$return_str       .= $user_display_name . ', ';

			$user_data         = get_userdata( array_pop( $favorited_users ) );
			$user_display_name = ! empty( $user_data ) ? bp_core_get_user_displayname( $user_data->ID ) : __( 'Unknown', 'buddyboss' );
			$return_str       .= $user_display_name . ' ' . __( 'and', 'buddyboss' ) . ' ';
		}

		if ( $like_count > 1 ) {
			$return_str .= $like_count . ' ' . __( 'others like this', 'buddyboss' );
		} else {
			$return_str .= $like_count . ' ' . __( 'other like this', 'buddyboss' );
		}
	} else {
		$return_str = $like_count;
	}

	return $return_str;
}


/**
 * Get users for activity favorite tooltip
 *
 * @since BuddyBoss 1.0.0
 *
 * @param $activity_id
 *
 * @return string
 */
function bp_activity_get_favorite_users_tooltip_string( $activity_id ) {

	if ( ! bp_is_activity_like_active() ) {
		return false;
	}

	$current_user_id = get_current_user_id();
	$favorited_users = bp_activity_get_meta( $activity_id, 'bp_favorite_users', true );

	if ( ! empty( $favorited_users ) ) {
		$like_text       = bp_activity_get_favorite_users_string( $activity_id );
		$favorited_users = array_reduce(
			$favorited_users,
			function ( $carry, $user_id ) use ( $current_user_id, $like_text ) {
				if ( $user_id != $current_user_id ) {
					$user_display_name = bp_core_get_user_displayname( $user_id );
					if ( strpos( $like_text, $user_display_name ) === false ) {
						$carry .= $user_display_name . ',&#10;';
					}
				}

				return $carry;
			}
		);
	}

	return ! empty( $favorited_users ) ? trim( $favorited_users, ',&#10;' ) : '';
}

/**
 * Check if BuddyPress activity favorites data needs upgrade & Update to BuddyBoss activity like data
 *
 * @since BuddyBoss 1.0.0
 */
function bp_activity_favorites_upgrade_data() {
	$bp_activity_favorites = bp_get_option( 'bp_activity_favorites', false );

	if ( ! $bp_activity_favorites && bp_is_active( 'activity' ) ) {

		if ( bp_is_large_install() ) {
			$admin_url = bp_get_admin_url( add_query_arg( array( 'page' => 'bp-tools' ), 'admin.php' ) );
			$notice    = sprintf(
				'%1$s <a href="%2$s">%3$s</a> %4$s',
				__( 'Due to the large size of your users table, you need to manually update user activity favorites data via BuddyBoss > ', 'buddyboss' ),
				esc_url( $admin_url ),
				__( 'Tools', 'buddyboss' ),
				__( ' > Repair Community. Check the box "Update activity favorites data" and click on "Repair Items". ', 'buddyboss' )
			);

			bp_core_add_admin_notice( $notice, 'error' );
			return;
		}

		$args = array(
			'fields' => 'ID',
		);

		// The Query
		$user_query = new WP_User_Query( $args );

		// User Loop
		if ( $user_query->get_results() ) {
			foreach ( $user_query->get_results() as $user_id ) {
				$user_favs = bp_get_user_meta( $user_id, 'bp_favorite_activities', true );

				if ( empty( $user_favs ) || ! is_array( $user_favs ) ) {
					continue;
				}

				foreach ( $user_favs as $fav ) {

					// Update the users who have favorited this activity.
					$users = bp_activity_get_meta( $fav, 'bp_favorite_users', true );
					if ( empty( $users ) || ! is_array( $users ) ) {
						$users = array();
					}
					// Add to activity's favorited users.
					$users[] = $user_id;

					// Update activity meta
					bp_activity_update_meta( $fav, 'bp_favorite_users', array_unique( $users ) );

				}
			}

			bp_update_option( 'bp_activity_favorites', true );
		}
	}
}

/**
 * Check whether an activity item exists with a given content string.
 *
 * @since BuddyPress 1.1.0
 *
 * @param string $content The content to filter by.
 * @return int|null The ID of the located activity item. Null if none is found.
 */
function bp_activity_check_exists_by_content( $content ) {

	/**
	 * Filters the results of the check for whether an activity item exists by specified content.
	 *
	 * @since BuddyPress 1.1.0
	 *
	 * @param BP_Activity_Activity $value ID of the activity if found, else null.
	 */
	return apply_filters( 'bp_activity_check_exists_by_content', BP_Activity_Activity::check_exists_by_content( $content ) );
}

/**
 * Retrieve the last time activity was updated.
 *
 * @since BuddyPress 1.0.0
 *
 * @return string Date last updated.
 */
function bp_activity_get_last_updated() {

	/**
	 * Filters the value for the last updated time for an activity item.
	 *
	 * @since BuddyPress 1.1.0
	 *
	 * @param BP_Activity_Activity $last_updated Date last updated.
	 */
	return apply_filters( 'bp_activity_get_last_updated', BP_Activity_Activity::get_last_updated() );
}

/**
 * Retrieve the number of favorite activity feed items a user has.
 *
 * @since BuddyPress 1.2.0
 *
 * @param int $user_id ID of the user whose favorite count is being requested.
 * @return int Total favorite count for the user.
 */
function bp_activity_total_favorites_for_user( $user_id = 0 ) {

	// Fallback on displayed user, and then logged in user.
	if ( empty( $user_id ) ) {
		$user_id = ( bp_displayed_user_id() ) ? bp_displayed_user_id() : bp_loggedin_user_id();
	}

	return BP_Activity_Activity::total_favorite_count( $user_id );
}

/**
 * Get activity visibility levels out of the $bp global.
 *
 * @since BuddyBoss 1.2.3
 *
 * @return array
 */
function bp_activity_get_visibility_levels() {

	/**
	 * Filters the activity visibility levels out of the $bp global.
	 *
	 * @since BuddyBoss 1.2.3
	 *
	 * @param array $visibility_levels Array of visibility levels.
	 */
	return apply_filters( 'bp_activity_get_visibility_levels', buddypress()->activity->visibility_levels );
}

/** Meta *********************************************************************/

/**
 * Delete a meta entry from the DB for an activity feed item.
 *
 * @since BuddyPress 1.2.0
 *
 * @global wpdb $wpdb WordPress database abstraction object.
 *
 * @param int    $activity_id ID of the activity item whose metadata is being deleted.
 * @param string $meta_key    Optional. The key of the metadata being deleted. If
 *                            omitted, all metadata associated with the activity
 *                            item will be deleted.
 * @param string $meta_value  Optional. If present, the metadata will only be
 *                            deleted if the meta_value matches this parameter.
 * @param bool   $delete_all  Optional. If true, delete matching metadata entries
 *                            for all objects, ignoring the specified object_id. Otherwise,
 *                            only delete matching metadata entries for the specified
 *                            activity item. Default: false.
 * @return bool True on success, false on failure.
 */
function bp_activity_delete_meta( $activity_id, $meta_key = '', $meta_value = '', $delete_all = false ) {

	// Legacy - if no meta_key is passed, delete all for the item.
	if ( empty( $meta_key ) ) {
		$all_meta = bp_activity_get_meta( $activity_id );
		$keys     = ! empty( $all_meta ) ? array_keys( $all_meta ) : array();

		// With no meta_key, ignore $delete_all.
		$delete_all = false;
	} else {
		$keys = array( $meta_key );
	}

	$retval = true;

	add_filter( 'query', 'bp_filter_metaid_column_name' );
	foreach ( $keys as $key ) {
		$retval = delete_metadata( 'activity', $activity_id, $key, $meta_value, $delete_all );
	}
	remove_filter( 'query', 'bp_filter_metaid_column_name' );

	return $retval;
}

/**
 * Get metadata for a given activity item.
 *
 * @since BuddyPress 1.2.0
 *
 * @param int    $activity_id ID of the activity item whose metadata is being requested.
 * @param string $meta_key    Optional. If present, only the metadata matching
 *                            that meta key will be returned. Otherwise, all metadata for the
 *                            activity item will be fetched.
 * @param bool   $single      Optional. If true, return only the first value of the
 *                            specified meta_key. This parameter has no effect if meta_key is not
 *                            specified. Default: true.
 * @return mixed The meta value(s) being requested.
 */
function bp_activity_get_meta( $activity_id = 0, $meta_key = '', $single = true ) {
	add_filter( 'query', 'bp_filter_metaid_column_name' );
	$retval = get_metadata( 'activity', $activity_id, $meta_key, $single );
	remove_filter( 'query', 'bp_filter_metaid_column_name' );

	/**
	 * Filters the metadata for a specified activity item.
	 *
	 * @since BuddyPress 1.5.0
	 *
	 * @param mixed  $retval      The meta values for the activity item.
	 * @param int    $activity_id ID of the activity item.
	 * @param string $meta_key    Meta key for the value being requested.
	 * @param bool   $single      Whether to return one matched meta key row or all.
	 */
	return apply_filters( 'bp_activity_get_meta', $retval, $activity_id, $meta_key, $single );
}

/**
 * Update a piece of activity meta.
 *
 * @since BuddyPress 1.2.0
 *
 * @param int    $activity_id ID of the activity item whose metadata is being updated.
 * @param string $meta_key    Key of the metadata being updated.
 * @param mixed  $meta_value  Value to be set.
 * @param mixed  $prev_value  Optional. If specified, only update existing metadata entries
 *                            with the specified value. Otherwise, update all entries.
 * @return bool|int Returns false on failure. On successful update of existing
 *                  metadata, returns true. On successful creation of new metadata,
 *                  returns the integer ID of the new metadata row.
 */
function bp_activity_update_meta( $activity_id, $meta_key, $meta_value, $prev_value = '' ) {
	add_filter( 'query', 'bp_filter_metaid_column_name' );
	$retval = update_metadata( 'activity', $activity_id, $meta_key, $meta_value, $prev_value );
	remove_filter( 'query', 'bp_filter_metaid_column_name' );

	return $retval;
}

/**
 * Add a piece of activity metadata.
 *
 * @since BuddyPress 2.0.0
 *
 * @param int    $activity_id ID of the activity item.
 * @param string $meta_key    Metadata key.
 * @param mixed  $meta_value  Metadata value.
 * @param bool   $unique      Optional. Whether to enforce a single metadata value for the
 *                            given key. If true, and the object already has a value for
 *                            the key, no change will be made. Default: false.
 * @return int|bool The meta ID on successful update, false on failure.
 */
function bp_activity_add_meta( $activity_id, $meta_key, $meta_value, $unique = false ) {
	add_filter( 'query', 'bp_filter_metaid_column_name' );
	$retval = add_metadata( 'activity', $activity_id, $meta_key, $meta_value, $unique );
	remove_filter( 'query', 'bp_filter_metaid_column_name' );

	return $retval;
}

/** Clean up *****************************************************************/

/**
 * Completely remove a user's activity data.
 *
 * @since BuddyPress 1.5.0
 *
 * @param int $user_id ID of the user whose activity is being deleted.
 * @return bool
 */
function bp_activity_remove_all_user_data( $user_id = 0 ) {
	if ( empty( $user_id ) ) {
		return false;
	}

	// Clear the user's activity from the sitewide stream and clear their activity tables.
	bp_activity_delete( array( 'user_id' => $user_id ) );

	// Removed users liked activity meta.
	bp_activity_remove_user_favorite_meta( $user_id );

	// Remove any usermeta.
	bp_delete_user_meta( $user_id, 'bp_latest_update' );
	bp_delete_user_meta( $user_id, 'bp_favorite_activities' );

	// Execute additional code
	do_action( 'bp_activity_remove_data', $user_id ); // Deprecated! Do not use!

	/**
	 * Fires after the removal of all of a user's activity data.
	 *
	 * @since BuddyPress 1.5.0
	 *
	 * @param int $user_id ID of the user being deleted.
	 */
	do_action( 'bp_activity_remove_all_user_data', $user_id );
}
add_action( 'wpmu_delete_user', 'bp_activity_remove_all_user_data' );
add_action( 'delete_user', 'bp_activity_remove_all_user_data' );

/**
 * Mark all of the user's activity as spam.
 *
 * @since BuddyPress 1.6.0
 *
 * @global wpdb $wpdb WordPress database abstraction object.
 *
 * @param int $user_id ID of the user whose activity is being spammed.
 * @return bool
 */
function bp_activity_spam_all_user_data( $user_id = 0 ) {
	global $wpdb;

	// Do not delete user data unless a logged in user says so.
	if ( empty( $user_id ) || ! is_user_logged_in() ) {
		return false;
	}

	// Get all the user's activities.
	$activities = bp_activity_get(
		array(
			'display_comments' => 'stream',
			'filter'           => array( 'user_id' => $user_id ),
			'show_hidden'      => true,
		)
	);

	$bp = buddypress();

	// Mark each as spam.
	foreach ( (array) $activities['activities'] as $activity ) {

		// Create an activity object.
		$activity_obj = new BP_Activity_Activity();
		foreach ( $activity as $k => $v ) {
			$activity_obj->$k = $v;
		}

		// Mark as spam.
		bp_activity_mark_as_spam( $activity_obj );

		/*
		 * If Akismet is present, update the activity history meta.
		 *
		 * This is usually taken care of when BP_Activity_Activity::save() happens, but
		 * as we're going to be updating all the activity statuses directly, for efficiency,
		 * we need to update manually.
		 */
		if ( ! empty( $bp->activity->akismet ) ) {
			$bp->activity->akismet->update_activity_spam_meta( $activity_obj );
		}

		// Tidy up.
		unset( $activity_obj );
	}

	// Mark all of this user's activities as spam.
	$wpdb->query( $wpdb->prepare( "UPDATE {$bp->activity->table_name} SET is_spam = 1 WHERE user_id = %d", $user_id ) );

	/**
	 * Fires after all activity data from a user has been marked as spam.
	 *
	 * @since BuddyPress 1.6.0
	 *
	 * @param int   $user_id    ID of the user whose activity is being marked as spam.
	 * @param array $activities Array of activity items being marked as spam.
	 */
	do_action( 'bp_activity_spam_all_user_data', $user_id, $activities['activities'] );
}
add_action( 'bp_make_spam_user', 'bp_activity_spam_all_user_data' );

/**
 * Mark all of the user's activity as ham (not spam).
 *
 * @since BuddyPress 1.6.0
 *
 * @global wpdb $wpdb WordPress database abstraction object.
 *
 * @param int $user_id ID of the user whose activity is being hammed.
 * @return bool
 */
function bp_activity_ham_all_user_data( $user_id = 0 ) {
	global $wpdb;

	// Do not delete user data unless a logged in user says so.
	if ( empty( $user_id ) || ! is_user_logged_in() ) {
		return false;
	}

	// Get all the user's activities.
	$activities = bp_activity_get(
		array(
			'display_comments' => 'stream',
			'filter'           => array( 'user_id' => $user_id ),
			'show_hidden'      => true,
			'spam'             => 'all',
		)
	);

	$bp = buddypress();

	// Mark each as not spam.
	foreach ( (array) $activities['activities'] as $activity ) {

		// Create an activity object.
		$activity_obj = new BP_Activity_Activity();
		foreach ( $activity as $k => $v ) {
			$activity_obj->$k = $v;
		}

		// Mark as not spam.
		bp_activity_mark_as_ham( $activity_obj );

		/*
		 * If Akismet is present, update the activity history meta.
		 *
		 * This is usually taken care of when BP_Activity_Activity::save() happens, but
		 * as we're going to be updating all the activity statuses directly, for efficiency,
		 * we need to update manually.
		 */
		if ( ! empty( $bp->activity->akismet ) ) {
			$bp->activity->akismet->update_activity_ham_meta( $activity_obj );
		}

		// Tidy up.
		unset( $activity_obj );
	}

	// Mark all of this user's activities as not spam.
	$wpdb->query( $wpdb->prepare( "UPDATE {$bp->activity->table_name} SET is_spam = 0 WHERE user_id = %d", $user_id ) );

	/**
	 * Fires after all activity data from a user has been marked as ham.
	 *
	 * @since BuddyPress 1.6.0
	 *
	 * @param int   $user_id    ID of the user whose activity is being marked as ham.
	 * @param array $activities Array of activity items being marked as ham.
	 */
	do_action( 'bp_activity_ham_all_user_data', $user_id, $activities['activities'] );
}
add_action( 'bp_make_ham_user', 'bp_activity_ham_all_user_data' );

/**
 * Allow core components and dependent plugins to register activity actions.
 *
 * @since BuddyPress 1.2.0
 */
function bp_register_activity_actions() {
	/**
	 * Fires on bp_init to allow core components and dependent plugins to register activity actions.
	 *
	 * @since BuddyPress 1.2.0
	 */
	do_action( 'bp_register_activity_actions' );
}
add_action( 'bp_init', 'bp_register_activity_actions', 8 );

/**
 * Register the activity feed actions for updates.
 *
 * @since BuddyPress 1.6.0
 */
function bp_activity_register_activity_actions() {
	$bp = buddypress();

	bp_activity_set_action(
		$bp->activity->id,
		'activity_update',
		__( 'Posted a status update', 'buddyboss' ),
		'bp_activity_format_activity_action_activity_update',
		__( 'Updates', 'buddyboss' ),
		array( 'activity', 'group', 'member', 'member_groups' )
	);

	bp_activity_set_action(
		$bp->activity->id,
		'activity_comment',
		__( 'Replied to a status update', 'buddyboss' ),
		'bp_activity_format_activity_action_activity_comment',
		__( 'Activity Comments', 'buddyboss' )
	);

	/**
	 * Fires at the end of the activity actions registration.
	 *
	 * Allows plugin authors to add their own activity actions alongside the core actions.
	 *
	 * @since BuddyPress 1.6.0
	 */
	do_action( 'bp_activity_register_activity_actions' );

	// Backpat. Don't use this.
	do_action( 'updates_register_activity_actions' );
}
add_action( 'bp_register_activity_actions', 'bp_activity_register_activity_actions' );

/**
 * Generate an activity action string for an activity item.
 *
 * @since BuddyPress 2.0.0
 *
 * @param object $activity Activity data object.
 * @return string|bool Returns false if no callback is found, otherwise returns
 *                     the formatted action string.
 */
function bp_activity_generate_action_string( $activity ) {

	// Check for valid input.
	if ( empty( $activity->component ) || empty( $activity->type ) ) {
		return false;
	}

	// Check for registered format callback.
	$actions = bp_activity_get_actions();
	if ( empty( $actions->{$activity->component}->{$activity->type}['format_callback'] ) ) {
		return false;
	}

	// We apply the format_callback as a filter.
	add_filter( 'bp_activity_generate_action_string', $actions->{$activity->component}->{$activity->type}['format_callback'], 10, 2 );

	/**
	 * Filters the string for the activity action being returned.
	 *
	 * @since BuddyPress 2.0.0
	 *
	 * @param BP_Activity_Activity $action   Action string being requested.
	 * @param BP_Activity_Activity $activity Activity item object.
	 */
	$action = apply_filters( 'bp_activity_generate_action_string', $activity->action, $activity );

	// Remove the filter for future activity items.
	remove_filter( 'bp_activity_generate_action_string', $actions->{$activity->component}->{$activity->type}['format_callback'], 10 );

	return $action;
}

/**
 * Format 'activity_update' activity actions.
 *
 * @since BuddyPress 2.0.0
 *
 * @param string $action   Static activity action.
 * @param object $activity Activity data object.
 * @return string $action
 */
function bp_activity_format_activity_action_activity_update( $action, $activity ) {
	if ( bp_activity_do_mentions() && $usernames = bp_activity_find_mentions( $activity->content ) ) {
		$mentioned_users      = array_filter( array_map( 'bp_get_user_by_nickname', $usernames ) );
		$mentioned_users_link = array_map(
			function( $mentioned_user ) {
					return bp_core_get_userlink( $mentioned_user->ID );
			},
			$mentioned_users
		);

		$last_user_link = array_pop( $mentioned_users_link );

		$action = sprintf(
			__( '%1$s <span class="activity-to">to</span> %2$s%3$s%4$s', 'buddyboss' ),
			bp_core_get_userlink( $activity->user_id ),
			$mentioned_users_link ? implode( ', ', $mentioned_users_link ) : '',
			$mentioned_users_link ? __( ' and ', 'buddyboss' ) : '',
			$last_user_link
		);
	} else {
		$action = sprintf( __( '%s posted an update', 'buddyboss' ), bp_core_get_userlink( $activity->user_id ) );
	}

	/**
	 * Filters the formatted activity action update string.
	 *
	 * @since BuddyPress 1.2.0
	 *
	 * @param string               $action   Activity action string value.
	 * @param BP_Activity_Activity $activity Activity item object.
	 */
	return apply_filters( 'bp_activity_new_update_action', $action, $activity );
}

/**
 * Format 'activity_comment' activity actions.
 *
 * @since BuddyPress 2.0.0
 *
 * @param string $action   Static activity action.
 * @param object $activity Activity data object.
 * @return string $action
 */
function bp_activity_format_activity_action_activity_comment( $action, $activity ) {
	$action = sprintf( __( '%s posted a new activity comment', 'buddyboss' ), bp_core_get_userlink( $activity->user_id ) );

	/**
	 * Filters the formatted activity action comment string.
	 *
	 * @since BuddyPress 1.2.0
	 *
	 * @param string               $action   Activity action string value.
	 * @param BP_Activity_Activity $activity Activity item object.
	 */
	return apply_filters( 'bp_activity_comment_action', $action, $activity );
}

/**
 * Format activity action strings for custom post types.
 *
 * @since BuddyPress 2.2.0
 *
 * @param string $action   Static activity action.
 * @param object $activity Activity data object.
 * @return string $action
 */
function bp_activity_format_activity_action_custom_post_type_post( $action, $activity ) {
	$bp = buddypress();

	// Fetch all the tracked post types once.
	if ( empty( $bp->activity->track ) ) {
		$bp->activity->track = bp_activity_get_post_types_tracking_args();
	}

	if ( empty( $activity->type ) || empty( $bp->activity->track[ $activity->type ] ) ) {
		return $action;
	}

	$user_link = bp_core_get_userlink( $activity->user_id );
	$blog_url  = get_home_url( $activity->item_id );

	if ( empty( $activity->post_url ) ) {
		$post_url = add_query_arg( 'p', $activity->secondary_item_id, trailingslashit( $blog_url ) );
	} else {
		$post_url = $activity->post_url;
	}

	if ( is_multisite() ) {
		$blog_link = '<a href="' . esc_url( $blog_url ) . '">' . get_blog_option( $activity->item_id, 'blogname' ) . '</a>';

		if ( ! empty( $bp->activity->track[ $activity->type ]->new_post_type_action_ms ) ) {
			$action = sprintf( $bp->activity->track[ $activity->type ]->new_post_type_action_ms, $user_link, $post_url, $blog_link );
		} else {
			$action = sprintf( __( '%1$s wrote a new <a href="%2$s">item</a>, on the site %3$s', 'buddyboss' ), $user_link, esc_url( $post_url ), $blog_link );
		}
	} else {
		if ( ! empty( $bp->activity->track[ $activity->type ]->new_post_type_action ) ) {
			$action = sprintf( $bp->activity->track[ $activity->type ]->new_post_type_action, $user_link, $post_url );
		} else {
			$action = sprintf( __( '%1$s wrote a new <a href="%2$s">item</a>', 'buddyboss' ), $user_link, esc_url( $post_url ) );
		}
	}

	/**
	 * Filters the formatted custom post type activity post action string.
	 *
	 * @since BuddyPress 2.2.0
	 *
	 * @param string               $action   Activity action string value.
	 * @param BP_Activity_Activity $activity Activity item object.
	 */
	return apply_filters( 'bp_activity_custom_post_type_post_action', $action, $activity );
}

/**
 * Format activity action strings for custom post types comments.
 *
 * @since BuddyPress 2.5.0
 *
 * @param string $action   Static activity action.
 * @param object $activity Activity data object.
 *
 * @return string
 */
function bp_activity_format_activity_action_custom_post_type_comment( $action, $activity ) {
	$bp = buddypress();

	// Fetch all the tracked post types once.
	if ( empty( $bp->activity->track ) ) {
		$bp->activity->track = bp_activity_get_post_types_tracking_args();
	}

	if ( empty( $activity->type ) || empty( $bp->activity->track[ $activity->type ] ) ) {
		return $action;
	}

	$user_link = bp_core_get_userlink( $activity->user_id );

	if ( is_multisite() ) {
		$blog_link = '<a href="' . esc_url( get_home_url( $activity->item_id ) ) . '">' . get_blog_option( $activity->item_id, 'blogname' ) . '</a>';

		if ( ! empty( $bp->activity->track[ $activity->type ]->new_post_type_comment_action_ms ) ) {
			$action = sprintf( $bp->activity->track[ $activity->type ]->new_post_type_comment_action_ms, $user_link, $activity->primary_link, $blog_link );
		} else {
			$action = sprintf( __( '%1$s commented on the <a href="%2$s">item</a>, on the site %3$s', 'buddyboss' ), $user_link, $activity->primary_link, $blog_link );
		}
	} else {
		if ( ! empty( $bp->activity->track[ $activity->type ]->new_post_type_comment_action ) ) {
			$action = sprintf( $bp->activity->track[ $activity->type ]->new_post_type_comment_action, $user_link, $activity->primary_link );
		} else {
			$action = sprintf( __( '%1$s commented on the <a href="%2$s">item</a>', 'buddyboss' ), $user_link, $activity->primary_link );
		}
	}

	/**
	 * Filters the formatted custom post type activity comment action string.
	 *
	 * @since BuddyPress 2.5.0
	 *
	 * @param string               $action   Activity action string value.
	 * @param BP_Activity_Activity $activity Activity item object.
	 */
	return apply_filters( 'bp_activity_custom_post_type_comment_action', $action, $activity );
}

/*
 * Business functions are where all the magic happens in BuddyPress. They will
 * handle the actual saving or manipulation of information. Usually they will
 * hand off to a database class for data access, then return
 * true or false on success or failure.
 */

/**
 * Retrieve an activity or activities.
 *
 * The bp_activity_get() function shares all arguments with BP_Activity_Activity::get().
 * The following is a list of bp_activity_get() parameters that have different
 * default values from BP_Activity_Activity::get() (value in parentheses is
 * the default for the bp_activity_get()).
 *   - 'per_page' (false)
 *
 * @since BuddyPress 1.2.0
 * @since BuddyPress 2.4.0 Introduced the `$fields` parameter.
 *
 * @see BP_Activity_Activity::get() For more information on accepted arguments
 *      and the format of the returned value.
 *
 * @param array|string $args See BP_Activity_Activity::get() for description.
 * @return array $activity See BP_Activity_Activity::get() for description.
 */
function bp_activity_get( $args = '' ) {

	$r = bp_parse_args(
		$args,
		array(
			'max'               => false,        // Maximum number of results to return.
			'fields'            => 'all',
			'page'              => 1,            // Page 1 without a per_page will result in no pagination.
			'per_page'          => false,        // results per page
			'sort'              => 'DESC',       // sort ASC or DESC
			'order_by'          => false,         // order by.
			'display_comments'  => false,        // False for no comments. 'stream' for within stream display, 'threaded' for below each activity item.

			'privacy'           => false,        // Privacy of activity
			'search_terms'      => false,        // Pass search terms as a string
			'meta_query'        => false,        // Filter by activity meta. See WP_Meta_Query for format
			'date_query'        => false,        // Filter by date. See first parameter of WP_Date_Query for format.
			'filter_query'      => false,
			'show_hidden'       => false,        // Show activity items that are hidden site-wide?
			'exclude'           => false,        // Comma-separated list of activity IDs to exclude.
			'in'                => false,        // Comma-separated list or array of activity IDs to which you
											 // want to limit the query.
			'spam'              => 'ham_only',   // 'ham_only' (default), 'spam_only' or 'all'.
			'update_meta_cache' => true,
			'count_total'       => false,
			'scope'             => false,

			/**
			 * Pass filters as an array -- all filter items can be multiple values comma separated:
			 * array(
			 *     'user_id'      => false, // User ID to filter on.
			 *     'object'       => false, // Object to filter on e.g. groups, profile, status, friends.
			 *     'action'       => false, // Action to filter on e.g. activity_update, profile_updated.
			 *     'primary_id'   => false, // Object ID to filter on e.g. a group_id or blog_id etc.
			 *     'secondary_id' => false, // Secondary object ID to filter on e.g. a post_id.
			 * );
			 */
			'filter'            => array(),
		),
		'activity_get'
	);

	$activity = BP_Activity_Activity::get(
		array(
			'page'              => $r['page'],
			'per_page'          => $r['per_page'],
			'max'               => $r['max'],
			'sort'              => $r['sort'],
			'order_by'          => $r['order_by'],
			'privacy'           => $r['privacy'],
			'search_terms'      => $r['search_terms'],
			'meta_query'        => $r['meta_query'],
			'date_query'        => $r['date_query'],
			'filter_query'      => $r['filter_query'],
			'filter'            => $r['filter'],
			'scope'             => $r['scope'],
			'display_comments'  => $r['display_comments'],
			'show_hidden'       => $r['show_hidden'],
			'exclude'           => $r['exclude'],
			'in'                => $r['in'],
			'spam'              => $r['spam'],
			'update_meta_cache' => $r['update_meta_cache'],
			'count_total'       => $r['count_total'],
			'fields'            => $r['fields'],
		)
	);

	/**
	 * Filters the requested activity item(s).
	 *
	 * @since BuddyPress 1.2.0
	 *
	 * @param BP_Activity_Activity $activity Requested activity object.
	 * @param array                $r        Arguments used for the activity query.
	 */
	return apply_filters_ref_array( 'bp_activity_get', array( &$activity, &$r ) );
}

/**
 * Fetch specific activity items.
 *
 * @since BuddyPress 1.2.0
 *
 * @see BP_Activity_Activity::get() For more information on accepted arguments.
 *
 * @param array|string $args {
 *     All arguments and defaults are shared with BP_Activity_Activity::get(),
 *     except for the following:
 *     @type string|int|array Single activity ID, comma-separated list of IDs,
 *                            or array of IDs.
 * }
 * @return array $activity See BP_Activity_Activity::get() for description.
 */
function bp_activity_get_specific( $args = '' ) {

	$r = bp_parse_args(
		$args,
		array(
			'activity_ids'      => false,      // A single activity_id or array of IDs.
			'display_comments'  => false,      // True or false to display threaded comments for these specific activity items.
			'max'               => false,      // Maximum number of results to return.
			'page'              => 1,          // Page 1 without a per_page will result in no pagination.
			'per_page'          => false,      // Results per page.
			'show_hidden'       => true,       // When fetching specific items, show all.
			'privacy'           => false,      // privacy of activity.
			'sort'              => 'DESC',     // Sort ASC or DESC
			'spam'              => 'ham_only', // Retrieve items marked as spam.
			'scope'             => false, // Retrieve items marked as spam.
			'update_meta_cache' => true,
		),
		'activity_get_specific'
	);

	$get_args = array(
		'display_comments'  => $r['display_comments'],
		'in'                => $r['activity_ids'],
		'max'               => $r['max'],
		'page'              => $r['page'],
		'per_page'          => $r['per_page'],
		'show_hidden'       => $r['show_hidden'],
		'privacy'           => $r['privacy'],
		'sort'              => $r['sort'],
		'spam'              => $r['spam'],
		'scope'             => $r['scope'],
		'update_meta_cache' => $r['update_meta_cache'],
	);

	/**
	 * Filters the requested specific activity item.
	 *
	 * @since BuddyPress 1.2.0
	 *
	 * @param BP_Activity_Activity $activity Requested activity object.
	 * @param array                $args     Original passed in arguments.
	 * @param array                $get_args Constructed arguments used with request.
	 */
	return apply_filters( 'bp_activity_get_specific', BP_Activity_Activity::get( $get_args ), $args, $get_args );
}

/**
 * Add an activity item.
 *
 * @since BuddyPress 1.1.0
 * @since BuddyPress 2.6.0 Added 'error_type' parameter to $args.
 *
 * @param array|string $args {
 *     An array of arguments.
 *     @type int|bool $id                Pass an activity ID to update an existing item, or
 *                                       false to create a new item. Default: false.
 *     @type string   $action            Optional. The activity action/description, typically
 *                                       something like "Joe posted an update". Values passed to this param
 *                                       will be stored in the database and used as a fallback for when the
 *                                       activity item's format_callback cannot be found (eg, when the
 *                                       component is disabled). As long as you have registered a
 *                                       format_callback for your $type, it is unnecessary to include this
 *                                       argument - BP will generate it automatically.
 *                                       See {@link bp_activity_set_action()}.
 *     @type string   $content           Optional. The content of the activity item.
 *     @type string   $component         The unique name of the component associated with
 *                                       the activity item - 'groups', 'profile', etc.
 *     @type string   $type              The specific activity type, used for directory
 *                                       filtering. 'new_blog_post', 'activity_update', etc.
 *     @type string   $primary_link      Optional. The URL for this item, as used in
 *                                       RSS feeds. Defaults to the URL for this activity
 *                                       item's permalink page.
 *     @type int|bool $user_id           Optional. The ID of the user associated with the activity
 *                                       item. May be set to false or 0 if the item is not related
 *                                       to any user. Default: the ID of the currently logged-in user.
 *     @type int      $item_id           Optional. The ID of the associated item.
 *     @type int      $secondary_item_id Optional. The ID of a secondary associated item.
 *     @type string   $date_recorded     Optional. The GMT time, in Y-m-d h:i:s format, when
 *                                       the item was recorded. Defaults to the current time.
 *     @type bool     $hide_sitewide     Should the item be hidden on sitewide streams?
 *                                       Default: false.
 *     @type bool     $is_spam           Should the item be marked as spam? Default: false.
 *     @type string   $privacy           Privacy of the activity Default: public.
 *     @type string   $error_type        Optional. Error type. Either 'bool' or 'wp_error'. Default: 'bool'.
 * }
 * @return WP_Error|bool|int The ID of the activity on success. False on error.
 */
function bp_activity_add( $args = '' ) {

	$r = bp_parse_args(
		$args,
		array(
			'id'                => false,                  // Pass an existing activity ID to update an existing entry.
			'action'            => '',                     // The activity action - e.g. "Jon Doe posted an update"
			'content'           => '',                     // Optional: The content of the activity item e.g. "BuddyPress is awesome guys!"
			'component'         => false,                  // The name/ID of the component e.g. groups, profile, mycomponent.
			'type'              => false,                  // The activity type e.g. activity_update, profile_updated.
			'primary_link'      => '',                     // Optional: The primary URL for this item in RSS feeds (defaults to activity permalink).
			'user_id'           => bp_loggedin_user_id(),  // Optional: The user to record the activity for, can be false if this activity is not for a user.
			'item_id'           => false,                  // Optional: The ID of the specific item being recorded, e.g. a blog_id.
			'secondary_item_id' => false,                  // Optional: A second ID used to further filter e.g. a comment_id.
			'recorded_time'     => bp_core_current_time(), // The GMT time that this activity was recorded.
			'hide_sitewide'     => false,                  // Should this be hidden on the sitewide activity feed?
			'is_spam'           => false,                  // Is this activity item to be marked as spam?
			'privacy'           => 'public',               // privacy of the activity
			'error_type'        => 'bool',
		),
		'activity_add'
	);

	// Make sure we are backwards compatible.
	if ( empty( $r['component'] ) && ! empty( $r['component_name'] ) ) {
		$r['component'] = $r['component_name'];
	}

	if ( empty( $r['type'] ) && ! empty( $r['component_action'] ) ) {
		$r['type'] = $r['component_action'];
	}

	// Setup activity to be added.
	$activity                    = new BP_Activity_Activity( $r['id'] );
	$activity->user_id           = $r['user_id'];
	$activity->component         = $r['component'];
	$activity->type              = $r['type'];
	$activity->content           = $r['content'];
	$activity->primary_link      = $r['primary_link'];
	$activity->item_id           = $r['item_id'];
	$activity->secondary_item_id = $r['secondary_item_id'];
	$activity->date_recorded     = empty( $r['id'] ) ? $r['recorded_time'] : $activity->date_recorded;
	$activity->hide_sitewide     = $r['hide_sitewide'];
	$activity->is_spam           = $r['is_spam'];
	$activity->privacy           = $r['privacy'];
	$activity->error_type        = $r['error_type'];
	$activity->action            = ! empty( $r['action'] ) ? $r['action'] : bp_activity_generate_action_string( $activity );

	$save = $activity->save();

	if ( 'wp_error' === $r['error_type'] && is_wp_error( $save ) ) {
		return $save;
	} elseif ( 'bool' === $r['error_type'] && false === $save ) {
		return false;
	}

	// If this is an activity comment, rebuild the tree.
	if ( 'activity_comment' === $activity->type ) {
		// Also clear the comment cache for the parent activity ID.
		wp_cache_delete( $activity->item_id, 'bp_activity_comments' );
		wp_cache_delete( 'bp_get_child_comments_' . $activity->item_id, 'bp_activity_comments' );

		BP_Activity_Activity::rebuild_activity_comment_tree( $activity->item_id );
	}

	wp_cache_delete( 'bp_activity_sitewide_front', 'bp' );

	/**
	 * Fires at the end of the execution of adding a new activity item, before returning the new activity item ID.
	 *
	 * @since BuddyPress 1.1.0
	 *
	 * @param array $r Array of parsed arguments for the activity item being added.
	 */
	do_action( 'bp_activity_add', $r );

	return $activity->id;
}

/**
 * Post an activity update.
 *
 * @since BuddyPress 1.2.0
 *
 * @param array|string $args {
 *     @type int    $id         ID of the activity if update existing item.
 *     @type string $content    The content of the activity update.
 *     @type int    $user_id    Optional. Defaults to the logged-in user.
 *     @type string $error_type Optional. Error type to return. Either 'bool' or 'wp_error'. Defaults to
 *                              'bool' for boolean. 'wp_error' will return a WP_Error object.
 * }
 * @return int|bool|WP_Error $activity_id The activity id on success. On failure, either boolean false or WP_Error
 *                                        object depending on the 'error_type' $args parameter.
 */
function bp_activity_post_update( $args = '' ) {
	global $bp_activity_edit;

	$r = bp_parse_args(
		$args,
		array(
			'id'            => false,
			'content'       => false,
			'user_id'       => bp_loggedin_user_id(),
			'component'     => buddypress()->activity->id,
			'hide_sitewide' => false,
			'type'          => 'activity_update',
			'privacy'       => 'public',
			'error_type'    => 'bool',
		)
	);

	// if ( empty( $r['content'] ) || !strlen( trim( $r['content'] ) ) ) {
	// return false;
	// }

	if ( bp_is_user_inactive( $r['user_id'] ) ) {
		if ( 'wp_error' === $r['error_type'] ) {
			return new WP_Error( 'bp_activity_inactive_user', __( 'User account has not yet been activated.', 'buddyboss' ) );
		}
		return false;
	}

	$bp_activity_edit = false;
	$activity_id      = false;

	// Record this on the user's profile.
	$activity_content = $r['content'];
	$primary_link     = bp_core_get_userlink( $r['user_id'], false, true );

	/**
	 * Filters the new activity content for current activity item.
	 *
	 * @since BuddyPress 1.2.0
	 *
	 * @param string $activity_content Activity content posted by user.
	 */
	$add_content = apply_filters( 'bp_activity_new_update_content', $activity_content );

	/**
	 * Filters the activity primary link for current activity item.
	 *
	 * @since BuddyPress 1.2.0
	 *
	 * @param string $primary_link Link to the profile for the user who posted the activity.
	 */
	$add_primary_link = apply_filters( 'bp_activity_new_update_primary_link', $primary_link );

	if ( ! empty( $r['id'] ) ) {
		$activity = new BP_Activity_Activity( $r['id'] );

		if ( ! empty( $activity->id ) ) {
			$bp_activity_edit = true;

			if ( ! bp_activity_user_can_edit( $activity ) ) {
				if ( 'wp_error' === $r['error_type'] ) {
					return new WP_Error( 'error', __( 'Allowed time for editing this activity is passed already, you can not edit now.', 'buddyboss' ) );
				} else {
					return false;
				}
			}

			$activity_id = bp_activity_add(
				array(
					'id'                => $activity->id,
					'action'            => $activity->action,
					'content'           => $add_content,
					'component'         => $activity->component,
					'type'              => $activity->type,
					'primary_link'      => $add_primary_link,
					'user_id'           => $activity->user_id,
					'item_id'           => $activity->item_id,
					'secondary_item_id' => $activity->secondary_item_id,
					'recorded_time'     => $activity->date_recorded,
					'hide_sitewide'     => $activity->hide_sitewide,
					'is_spam'           => $activity->is_spam,
					'privacy'           => $r['privacy'],
					'error_type'        => $r['error_type'],
				)
			);

			/**
			 * Addition from the BuddyBoss
			 * Add meta to ensure that this activity has been edited.
			 */
			bp_activity_update_meta( $activity->id, '_is_edited', bp_core_current_time() );

		}
	} else {
		// Now write the values.
		$activity_id = bp_activity_add(
			array(
				'user_id'       => $r['user_id'],
				'content'       => $add_content,
				'primary_link'  => $add_primary_link,
				'component'     => $r['component'],
				'type'          => $r['type'],
				'hide_sitewide' => $r['hide_sitewide'],
				'privacy'       => $r['privacy'],
				'error_type'    => $r['error_type'],
			)
		);
	}

	// Bail on failure.
	if ( false === $activity_id || is_wp_error( $activity_id ) ) {
		return $activity_id;
	}

	if ( ! empty( $r['content'] ) && ! strlen( trim( $r['content'] ) ) ) {
		$update_activity = true;

		if ( $bp_activity_edit ) {
			$latest_activity = bp_get_user_meta( bp_loggedin_user_id(), 'bp_latest_update', true );

			if ( $latest_activity['id'] !== $activity_id ) {
				$update_activity = false;
			}
		}

		/**
		 * Filters the latest update content for the activity item.
		 *
		 * @param string $r Content of the activity update.
		 * @param string $activity_content Content of the activity update.
		 *
		 * @since BuddyPress 1.6.0
		 */
		$activity_content = apply_filters( 'bp_activity_latest_update_content', $r['content'], $activity_content );

		if ( $update_activity ) {
			// Add this update to the "latest update" usermeta so it can be fetched anywhere.
			$data = array(
				'id'      => $activity_id,
				'content' => $activity_content,
			);
			bp_update_user_meta( bp_loggedin_user_id(), 'bp_latest_update', $data );
		}
	}

	/**
	 * Fires at the end of an activity post update, before returning the updated activity item ID.
	 *
	 * @since BuddyPress 1.2.0
	 *
	 * @param string $content     Content of the activity post update.
	 * @param int    $user_id     ID of the user posting the activity update.
	 * @param int    $activity_id ID of the activity item being updated.
	 */
	do_action( 'bp_activity_posted_update', $r['content'], $r['user_id'], $activity_id );

	return $activity_id;
}

/**
 * Create an activity item for a newly published post type post.
 *
 * @since BuddyPress 2.2.0
 *
 * @param int          $post_id ID of the new post.
 * @param WP_Post|null $post    Post object.
 * @param int          $user_id ID of the post author.
 * @return null|WP_Error|bool|int The ID of the activity on success. False on error.
 */
function bp_activity_post_type_publish( $post_id = 0, $post = null, $user_id = 0 ) {

	if ( ! is_a( $post, 'WP_Post' ) ) {
		return;
	}

	// Get the post type tracking args.
	$activity_post_object = bp_activity_get_post_type_tracking_args( $post->post_type );

	if ( 'publish' != $post->post_status || ! empty( $post->post_password ) || empty( $activity_post_object->action_id ) ) {
		return;
	}

	if ( empty( $post_id ) ) {
		$post_id = $post->ID;
	}

	$blog_id = get_current_blog_id();

	if ( empty( $user_id ) ) {
		$user_id = (int) $post->post_author;
	}

	// Bail if an activity item already exists for this post.
	$existing = bp_activity_get(
		array(
			'filter' => array(
				'action'       => $activity_post_object->action_id,
				'primary_id'   => $blog_id,
				'secondary_id' => $post_id,
			),
		)
	);

	if ( ! empty( $existing['activities'] ) ) {
		return;
	}

	/**
	 * Filters whether or not to post the activity.
	 *
	 * This is a variable filter, dependent on the post type,
	 * that lets components or plugins bail early if needed.
	 *
	 * @since BuddyPress 2.2.0
	 *
	 * @param bool $value   Whether or not to continue.
	 * @param int  $blog_id ID of the current site.
	 * @param int  $post_id ID of the current post being published.
	 * @param int  $user_id ID of the current user or post author.
	 */
	if ( false === apply_filters( "bp_activity_{$post->post_type}_pre_publish", true, $blog_id, $post_id, $user_id ) ) {
		return;
	}

	// Record this in activity feeds.
	$blog_url = get_home_url( $blog_id );
	$post_url = add_query_arg(
		'p',
		$post_id,
		trailingslashit( $blog_url )
	);

	// Backward compatibility filters for the 'blogs' component.
	if ( 'blogs' == $activity_post_object->component_id ) {
		$activity_content      = apply_filters( 'bp_blogs_activity_new_post_content', '', $post, $post_url, $post->post_type );
		$activity_primary_link = apply_filters( 'bp_blogs_activity_new_post_primary_link', $post_url, $post_id, $post->post_type );
	} else {
		$activity_content      = $post->post_content;
		$activity_primary_link = $post_url;
	}

	$activity_args = array(
		'user_id'           => $user_id,
		'content'           => $activity_content,
		'primary_link'      => $activity_primary_link,
		'component'         => $activity_post_object->component_id,
		'type'              => $activity_post_object->action_id,
		'item_id'           => $blog_id,
		'secondary_item_id' => $post_id,
		'recorded_time'     => $post->post_date_gmt,
	);

	if ( ! empty( $activity_args['content'] ) ) {
		// Create the excerpt.
		$activity_summary = bp_activity_create_summary( $activity_args['content'], $activity_args );

		// Backward compatibility filter for blog posts.
		if ( 'blogs' == $activity_post_object->component_id ) {
			$activity_args['content'] = apply_filters( 'bp_blogs_record_activity_content', $activity_summary, $activity_args['content'], $activity_args, $post->post_type );
		} else {
			$activity_args['content'] = $activity_summary;
		}
	}

	// Set up the action by using the format functions.
	$action_args = array_merge(
		$activity_args,
		array(
			'post_title' => $post->post_title,
			'post_url'   => $post_url,
		)
	);

	$activity_args['action'] = call_user_func_array( $activity_post_object->format_callback, array( '', (object) $action_args ) );

	// Make sure the action is set.
	if ( empty( $activity_args['action'] ) ) {
		return;
	} else {
		// Backward compatibility filter for the blogs component.
		if ( 'blogs' == $activity_post_object->component_id ) {
			$activity_args['action'] = apply_filters( 'bp_blogs_record_activity_action', $activity_args['action'] );
		}
	}

	$activity_id = bp_activity_add( $activity_args );

	/**
	 * Fires after the publishing of an activity item for a newly published post type post.
	 *
	 * @since BuddyPress 2.2.0
	 *
	 * @param int     $activity_id   ID of the newly published activity item.
	 * @param WP_Post $post          Post object.
	 * @param array   $activity_args Array of activity arguments.
	 */
	do_action( 'bp_activity_post_type_published', $activity_id, $post, $activity_args );

	return $activity_id;
}

/**
 * Update the activity item for a custom post type entry.
 *
 * @since BuddyPress 2.2.0
 *
 * @param WP_Post|null $post Post item.
 * @return null|WP_Error|bool True on success, false on failure.
 */
function bp_activity_post_type_update( $post = null ) {

	if ( ! is_a( $post, 'WP_Post' ) ) {
		return;
	}

	// Get the post type tracking args.
	$activity_post_object = bp_activity_get_post_type_tracking_args( $post->post_type );

	if ( empty( $activity_post_object->action_id ) ) {
		return;
	}

	$activity_id = bp_activity_get_activity_id(
		array(
			'component'         => $activity_post_object->component_id,
			'item_id'           => get_current_blog_id(),
			'secondary_item_id' => $post->ID,
			'type'              => $activity_post_object->action_id,
		)
	);

	// Activity ID doesn't exist, so stop!
	if ( empty( $activity_id ) ) {
		return;
	}

	// Delete the activity if the post was updated with a password.
	if ( ! empty( $post->post_password ) ) {
		bp_activity_delete( array( 'id' => $activity_id ) );
	}

	// Update the activity entry.
	$activity = new BP_Activity_Activity( $activity_id );

	if ( ! empty( $post->post_content ) ) {
		$activity_summary = bp_activity_create_summary( $post->post_content, (array) $activity );

		// Backward compatibility filter for the blogs component.
		if ( 'blogs' == $activity_post_object->component_id ) {
			$activity->content = apply_filters( 'bp_blogs_record_activity_content', $activity_summary, $post->post_content, (array) $activity, $post->post_type );
		} else {
			$activity->content = $activity_summary;
		}
	}

	// Save the updated activity.
	$updated = $activity->save();

	/**
	 * Fires after the updating of an activity item for a custom post type entry.
	 *
	 * @since BuddyPress 2.2.0
	 * @since BuddyPress 2.5.0 Add the post type tracking args parameter
	 *
	 * @param WP_Post              $post                 Post object.
	 * @param BP_Activity_Activity $activity             Activity object.
	 * @param object               $activity_post_object The post type tracking args object.
	 */
	do_action( 'bp_activity_post_type_updated', $post, $activity, $activity_post_object );

	return $updated;
}

/**
 * Unpublish an activity for the custom post type.
 *
 * @since BuddyPress 2.2.0
 *
 * @param int          $post_id ID of the post being unpublished.
 * @param WP_Post|null $post    Post object.
 * @return bool True on success, false on failure.
 */
function bp_activity_post_type_unpublish( $post_id = 0, $post = null ) {

	if ( ! is_a( $post, 'WP_Post' ) ) {
		return;
	}

	// Get the post type tracking args.
	$activity_post_object = bp_activity_get_post_type_tracking_args( $post->post_type );

	if ( empty( $activity_post_object->action_id ) ) {
		return;
	}

	if ( empty( $post_id ) ) {
		$post_id = $post->ID;
	}

	$delete_activity_args = array(
		'item_id'           => get_current_blog_id(),
		'secondary_item_id' => $post_id,
		'component'         => $activity_post_object->component_id,
		'type'              => $activity_post_object->action_id,
		'user_id'           => false,
	);

	$deleted = bp_activity_delete_by_item_id( $delete_activity_args );

	/**
	 * Fires after the unpublishing for the custom post type.
	 *
	 * @since BuddyPress 2.2.0
	 *
	 * @param array   $delete_activity_args Array of arguments for activity deletion.
	 * @param WP_Post $post                 Post object.
	 * @param bool    $activity             Whether or not the activity was successfully deleted.
	 */
	do_action( 'bp_activity_post_type_unpublished', $delete_activity_args, $post, $deleted );

	return $deleted;
}

/**
 * Create an activity item for a newly posted post type comment.
 *
 * @since BuddyPress 2.5.0
 *
 * @param  int         $comment_id           ID of the comment.
 * @param  bool        $is_approved          Whether the comment is approved or not.
 * @param  object|null $activity_post_object The post type tracking args object.
 * @return null|WP_Error|bool|int The ID of the activity on success. False on error.
 */
function bp_activity_post_type_comment( $comment_id = 0, $is_approved = true, $activity_post_object = null ) {
	// Get the users comment
	$post_type_comment = get_comment( $comment_id );

	// Don't record activity if the comment hasn't been approved
	if ( empty( $is_approved ) ) {
		return false;
	}

	// Don't record activity if no email address has been included
	if ( empty( $post_type_comment->comment_author_email ) ) {
		return false;
	}

	// Don't record activity if the comment has already been marked as spam
	if ( 'spam' === $is_approved ) {
		return false;
	}

	// Get the user by the comment author email.
	$user = get_user_by( 'email', $post_type_comment->comment_author_email );

	// If user isn't registered, don't record activity
	if ( empty( $user ) ) {
		return false;
	}

	// Get the user_id
	$user_id = (int) $user->ID;

	// Get blog and post data
	$blog_id = get_current_blog_id();

	// Get the post
	$post_type_comment->post = get_post( $post_type_comment->comment_post_ID );

	if ( ! is_a( $post_type_comment->post, 'WP_Post' ) ) {
		return false;
	}

	/**
	 * Filters whether to publish activities about the comment regarding the post status
	 *
	 * @since BuddyPress 2.5.0
	 *
	 * @param bool true to bail, false otherwise.
	 */
	$is_post_status_not_allowed = (bool) apply_filters( 'bp_activity_post_type_is_post_status_allowed', 'publish' !== $post_type_comment->post->post_status || ! empty( $post_type_comment->post->post_password ) );

	// If this is a password protected post, or not a public post don't record the comment
	if ( $is_post_status_not_allowed ) {
		return false;
	}

	// Set post type
	$post_type = $post_type_comment->post->post_type;

	if ( empty( $activity_post_object ) ) {
		// Get the post type tracking args.
		$activity_post_object = bp_activity_get_post_type_tracking_args( $post_type );

		// Bail if the activity type does not exist
		if ( empty( $activity_post_object->comments_tracking->action_id ) ) {
			return false;
		}
	}

	// Set the $activity_comment_object
	$activity_comment_object = $activity_post_object->comments_tracking;

	/**
	 * Filters whether or not to post the activity about the comment.
	 *
	 * This is a variable filter, dependent on the post type,
	 * that lets components or plugins bail early if needed.
	 *
	 * @since BuddyPress 2.5.0
	 *
	 * @param bool $value      Whether or not to continue.
	 * @param int  $blog_id    ID of the current site.
	 * @param int  $post_id    ID of the current post being commented.
	 * @param int  $user_id    ID of the current user.
	 * @param int  $comment_id ID of the current comment being posted.
	 */
	if ( false === apply_filters( "bp_activity_{$post_type}_pre_comment", true, $blog_id, $post_type_comment->post->ID, $user_id, $comment_id ) ) {
		return false;
	}

	// Is this an update ?
	$activity_id = bp_activity_get_activity_id(
		array(
			'user_id'           => $user_id,
			'component'         => $activity_comment_object->component_id,
			'type'              => $activity_comment_object->action_id,
			'item_id'           => $blog_id,
			'secondary_item_id' => $comment_id,
		)
	);

	// Record this in activity feeds.
	$comment_link = get_comment_link( $post_type_comment->comment_ID );

	// Backward compatibility filters for the 'blogs' component.
	if ( 'blogs' == $activity_comment_object->component_id ) {
		$activity_content      = apply_filters_ref_array( 'bp_blogs_activity_new_comment_content', array( '', &$post_type_comment, $comment_link ) );
		$activity_primary_link = apply_filters_ref_array( 'bp_blogs_activity_new_comment_primary_link', array( $comment_link, &$post_type_comment ) );
	} else {
		$activity_content      = $post_type_comment->comment_content;
		$activity_primary_link = $comment_link;
	}

	$activity_args = array(
		'id'            => $activity_id,
		'user_id'       => $user_id,
		'content'       => $activity_content,
		'primary_link'  => $activity_primary_link,
		'component'     => $activity_comment_object->component_id,
		'recorded_time' => $post_type_comment->comment_date_gmt,
	);

	if ( bp_disable_blogforum_comments() ) {
		$blog_url = get_home_url( $blog_id );
		$post_url = add_query_arg(
			'p',
			$post_type_comment->post->ID,
			trailingslashit( $blog_url )
		);

		$activity_args['type']              = $activity_comment_object->action_id;
		$activity_args['item_id']           = $blog_id;
		$activity_args['secondary_item_id'] = $post_type_comment->comment_ID;

		if ( ! empty( $activity_args['content'] ) ) {
			// Create the excerpt.
			$activity_summary = bp_activity_create_summary( $activity_args['content'], $activity_args );

			// Backward compatibility filter for blog comments.
			if ( 'blogs' == $activity_post_object->component_id ) {
				$activity_args['content'] = apply_filters( 'bp_blogs_record_activity_content', $activity_summary, $activity_args['content'], $activity_args, $post_type );
			} else {
				$activity_args['content'] = $activity_summary;
			}
		}

		// Set up the action by using the format functions.
		$action_args = array_merge(
			$activity_args,
			array(
				'post_title' => $post_type_comment->post->post_title,
				'post_url'   => $post_url,
				'blog_url'   => $blog_url,
				'blog_name'  => get_blog_option( $blog_id, 'blogname' ),
			)
		);

		$activity_args['action'] = call_user_func_array( $activity_comment_object->format_callback, array( '', (object) $action_args ) );

		// Make sure the action is set.
		if ( empty( $activity_args['action'] ) ) {
			return;
		} else {
			// Backward compatibility filter for the blogs component.
			if ( 'blogs' === $activity_post_object->component_id ) {
				$activity_args['action'] = apply_filters( 'bp_blogs_record_activity_action', $activity_args['action'] );
			}
		}

		$activity_id = bp_activity_add( $activity_args );
	}

	/**
	 * Fires after the publishing of an activity item for a newly published post type post.
	 *
	 * @since BuddyPress 2.5.0
	 *
	 * @param int        $activity_id          ID of the newly published activity item.
	 * @param WP_Comment $post_type_comment    Comment object.
	 * @param array      $activity_args        Array of activity arguments.
	 * @param object     $activity_post_object the post type tracking args object.
	 */
	do_action_ref_array( 'bp_activity_post_type_comment', array( &$activity_id, $post_type_comment, $activity_args, $activity_post_object ) );

	return $activity_id;
}
add_action( 'comment_post', 'bp_activity_post_type_comment', 10, 2 );
add_action( 'edit_comment', 'bp_activity_post_type_comment', 10 );

/**
 * Create an activity item for a newly posted post type comment from REST API.
 *
 * @since BuddyBoss 2.0.1
 *
 * @param WP_Comment $comment WP_Comment class object.
 *
 * @return void
 */
function bb_rest_activity_post_type_comment( $comment ) {
	// Bail if not a comment.
	if (
		empty( $comment )
		|| ! $comment instanceof WP_Comment
	) {
		return;
	}

	bp_activity_post_type_comment( $comment->comment_ID, $comment->comment_approved );
}

add_action( 'rest_after_insert_comment', 'bb_rest_activity_post_type_comment', 10, 1 );

/**
 * Remove an activity item when a comment about a post type is deleted.
 *
 * @since BuddyPress 2.5.0
 *
 * @param  int         $comment_id           ID of the comment.
 * @param  object|null $activity_post_object The post type tracking args object.
 * @return bool True on success. False on error.
 */
function bp_activity_post_type_remove_comment( $comment_id = 0, $activity_post_object = null ) {
	if ( empty( $activity_post_object ) ) {
		$comment = get_comment( $comment_id );
		if ( ! $comment ) {
			return;
		}

		$post_type = get_post_type( $comment->comment_post_ID );
		if ( ! $post_type ) {
			return;
		}

		// Get the post type tracking args.
		$activity_post_object = bp_activity_get_post_type_tracking_args( $post_type );

		// Bail if the activity type does not exist
		if ( empty( $activity_post_object->comments_tracking->action_id ) ) {
			return false;
		}
	}

	// Set the $activity_comment_object
	$activity_comment_object = $activity_post_object->comments_tracking;

	if ( empty( $activity_comment_object->action_id ) ) {
		return false;
	}

	$deleted = false;

	if ( bp_disable_blogforum_comments() ) {
		$deleted = bp_activity_delete_by_item_id(
			array(
				'item_id'           => get_current_blog_id(),
				'secondary_item_id' => $comment_id,
				'component'         => $activity_comment_object->component_id,
				'type'              => $activity_comment_object->action_id,
				'user_id'           => false,
			)
		);
	}

	/**
	 * Fires after the custom post type comment activity was removed.
	 *
	 * @since BuddyPress 2.5.0
	 *
	 * @param bool       $deleted              True if the activity was deleted false otherwise
	 * @param WP_Comment $comment              Comment object.
	 * @param object     $activity_post_object The post type tracking args object.
	 * @param string     $value                The post type comment activity type.
	 */
	do_action( 'bp_activity_post_type_remove_comment', $deleted, $comment_id, $activity_post_object, $activity_comment_object->action_id );

	return $deleted;
}
add_action( 'delete_comment', 'bp_activity_post_type_remove_comment', 10, 1 );

/**
 * Add an activity comment.
 *
 * @since BuddyPress 1.2.0
 * @since BuddyPress 2.5.0 Add a new possible parameter $skip_notification for the array of arguments.
 *              Add the $primary_link parameter for the array of arguments.
 * @since BuddyPress 2.6.0 Added 'error_type' parameter to $args.
 *
 * @param array|string $args {
 *     An array of arguments.
 *     @type int    $id                Optional. Pass an ID to update an existing comment.
 *     @type string $content           The content of the comment.
 *     @type int    $user_id           Optional. The ID of the user making the comment.
 *                                     Defaults to the ID of the logged-in user.
 *     @type int    $activity_id       The ID of the "root" activity item, ie the oldest
 *                                     ancestor of the comment.
 *     @type int    $parent_id         Optional. The ID of the parent activity item, ie the item to
 *                                     which the comment is an immediate reply. If not provided,
 *                                     this value defaults to the $activity_id.
 *     @type string $primary_link      Optional. the primary link for the comment.
 *                                     Defaults to an empty string.
 *     @type bool   $skip_notification Optional. false to send a comment notification, false otherwise.
 *                                     Defaults to false.
 *     @type string $error_type        Optional. Error type. Either 'bool' or 'wp_error'. Default: 'bool'.
 * }
 * @return WP_Error|bool|int The ID of the comment on success, otherwise false.
 */
function bp_activity_new_comment( $args = '' ) {
	global $bb_activity_comment_edit;

	$bp = buddypress();

	$r = bp_parse_args(
		$args,
		array(
			'id'                => false,
			'content'           => false,
			'user_id'           => bp_loggedin_user_id(),
			'activity_id'       => false, // ID of the root activity item.
			'parent_id'         => false, // ID of a parent comment (optional).
			'primary_link'      => '',
			'skip_notification' => false,
			'error_type'        => 'bool',
			'skip_error'        => true,
		)
	);

	// Error type is boolean; need to initialize some variables for backpat.
	if ( 'bool' === $r['error_type'] ) {
		if ( empty( $bp->activity->errors ) ) {
			$bp->activity->errors = array();
		}
	}

	// Default error message.
	$feedback = __( 'There was an error posting your reply. Please try again.', 'buddyboss' );

	// Filter to skip comment content check for comment notification.
	$check_empty_content = apply_filters( 'bp_has_activity_comment_content', true );

	// Bail if missing necessary data.
	if ( ( $check_empty_content && ( empty( $r['content'] ) && false === $r['skip_error'] ) ) || empty( $r['user_id'] ) || empty( $r['activity_id'] ) ) {

		$error = new WP_Error( 'missing_data', $feedback );

		if ( 'wp_error' === $r['error_type'] ) {
			return $error;

			// Backpat.
		} else {
			$bp->activity->errors['new_comment'] = $error;
			return false;
		}
	}

	// Maybe set current activity ID as the parent.
	if ( empty( $r['parent_id'] ) ) {
		$r['parent_id'] = $r['activity_id'];
	}

	$activity_id = $r['activity_id'];

	// Get the parent activity.
	$activity = new BP_Activity_Activity( $activity_id );

	// Bail if the parent activity does not exist.
	if ( empty( $activity->date_recorded ) ) {
		$error = new WP_Error( 'missing_activity', __( 'The item you were replying to no longer exists.', 'buddyboss' ) );

		if ( 'wp_error' === $r['error_type'] ) {
			return $error;

			// Backpat.
		} else {
			$bp->activity->errors['new_comment'] = $error;
			return false;
		}
	}

	// update comment privacy with parent one.
	if ( ! empty( $activity->privacy ) ) {
		$privacy = $activity->privacy;
	} else {
		$privacy = 'public';
	}

	// Check to see if the parent activity is hidden, and if so, hide this comment publicly.
	$is_hidden = $activity->hide_sitewide ? 1 : 0;

	/**
	 * Filters the content of a new comment.
	 *
	 * @since BuddyPress 1.2.0
	 * @since BuddyPress 3.0.0 Added $context parameter to disambiguate from bp_get_activity_comment_content().
	 *
	 * @param string $r       Content for the newly posted comment.
	 * @param string $context This filter's context ("new").
	 */
	$comment_content = apply_filters( 'bp_activity_comment_content', $r['content'], 'new' );

	$bb_activity_comment_edit = false;
	if ( ! empty( $r['id'] ) ) {
		$activity_comment = new BP_Activity_Activity( $r['id'] );

		if ( ! empty( $activity_comment->id ) ) {
			$bb_activity_comment_edit = true;

			if ( ! bb_activity_comment_user_can_edit( $activity_comment ) ) {
				if ( 'wp_error' === $r['error_type'] ) {
					return new WP_Error( 'error', __( 'Allowed time for editing this activity comment is passed already, you can not edit now.', 'buddyboss' ) );
				} else {
					return false;
				}
			}

			$comment_id = bp_activity_add(
				array(
					'id'                => $activity_comment->id,
					'action'            => $activity_comment->action,
					'content'           => $comment_content,
					'component'         => $activity_comment->component,
					'type'              => $activity_comment->type,
					'primary_link'      => $activity_comment->primary_link,
					'user_id'           => $activity_comment->user_id,
					'item_id'           => $activity_comment->item_id,
					'secondary_item_id' => $activity_comment->secondary_item_id,
					'recorded_time'     => $activity_comment->date_recorded,
					'hide_sitewide'     => $activity_comment->hide_sitewide,
					'is_spam'           => $activity_comment->is_spam,
					'privacy'           => $activity_comment->privacy,
					'error_type'        => $r['error_type'],
				)
			);

			/**
			 * Addition from the BuddyBoss
			 * Add meta to ensure that this activity has been edited.
			 */
			bp_activity_update_meta( $activity_comment->id, '_is_edited', bp_core_current_time() );
		}
	} else {
		// Insert the activity comment.
		$comment_id = bp_activity_add(
			array(
				'id'                => $r['id'],
				'content'           => $comment_content,
				'component'         => buddypress()->activity->id,
				'type'              => 'activity_comment',
				'primary_link'      => $r['primary_link'],
				'user_id'           => $r['user_id'],
				'item_id'           => $activity_id,
				'secondary_item_id' => $r['parent_id'],
				'hide_sitewide'     => $is_hidden,
				'privacy'           => $privacy,
				'error_type'        => $r['error_type'],
			)
		);
	}

	// Bail on failure.
	if ( false === $comment_id || is_wp_error( $comment_id ) ) {
		return $comment_id;
	}

	// Comment caches are stored only with the top-level item.
	wp_cache_delete( $activity_id, 'bp_activity_comments' );
	wp_cache_delete( 'bp_get_child_comments_' . $activity_id, 'bp_activity_comments' );

	// Walk the tree to clear caches for all parent items.
	$clear_id = $r['parent_id'];
	while ( $clear_id != $activity_id ) {
		$clear_object = new BP_Activity_Activity( $clear_id );
		wp_cache_delete( $clear_id, 'bp_activity' );
		$clear_id = intval( $clear_object->secondary_item_id );
	}
	wp_cache_delete( $activity_id, 'bp_activity' );

	if ( empty( $r['skip_notification'] ) ) {
		/**
		 * Fires near the end of an activity comment posting, before the returning of the comment ID.
		 * Sends a notification to the user @see bp_activity_new_comment_notification_helper().
		 *
		 * @since BuddyPress 1.2.0
		 *
		 * @param int                  $comment_id ID of the newly posted activity comment.
		 * @param array                $r          Array of parsed comment arguments.
		 * @param BP_Activity_Activity $activity   Activity item being commented on.
		 */
		do_action( 'bp_activity_comment_posted', $comment_id, $r, $activity );
	} else {
		/**
		 * Fires near the end of an activity comment posting, before the returning of the comment ID.
		 * without sending a notification to the user
		 *
		 * @since BuddyPress 2.5.0
		 *
		 * @param int                  $comment_id ID of the newly posted activity comment.
		 * @param array                $r          Array of parsed comment arguments.
		 * @param BP_Activity_Activity $activity   Activity item being commented on.
		 */
		do_action( 'bp_activity_comment_posted_notification_skipped', $comment_id, $r, $activity );
	}

	if ( empty( $comment_id ) ) {
		$error = new WP_Error( 'comment_failed', $feedback );

		if ( 'wp_error' === $r['error_type'] ) {
			return $error;

			// Backpat.
		} else {
			$bp->activity->errors['new_comment'] = $error;
		}
	}

	return $comment_id;
}

/**
 * Fetch the activity_id for an existing activity entry in the DB.
 *
 * @since BuddyPress 1.2.0
 *
 * @see BP_Activity_Activity::get() For more information on accepted arguments.
 *
 * @param array|string $args See BP_Activity_Activity::get() for description.
 * @return int $activity_id The ID of the activity item found.
 */
function bp_activity_get_activity_id( $args = '' ) {

	$r = bp_parse_args(
		$args,
		array(
			'user_id'           => false,
			'component'         => false,
			'type'              => false,
			'item_id'           => false,
			'secondary_item_id' => false,
			'action'            => false,
			'content'           => false,
			'date_recorded'     => false,
		)
	);

	/**
	 * Filters the activity ID being requested.
	 *
	 * @since BuddyPress 1.2.0
	 * @since BuddyPress 2.5.0 Added the `$r` and `$args` parameters.
	 *
	 * @param BP_Activity_Activity $value ID returned by BP_Activity_Activity get_id() method with provided arguments.
	 * @param array                $r     Parsed function arguments.
	 * @param array                $args  Arguments passed to the function.
	 */
	return apply_filters(
		'bp_activity_get_activity_id',
		BP_Activity_Activity::get_id(
			$r['user_id'],
			$r['component'],
			$r['type'],
			$r['item_id'],
			$r['secondary_item_id'],
			$r['action'],
			$r['content'],
			$r['date_recorded']
		),
		$r,
		$args
	);
}

/**
 * Delete activity item(s).
 *
 * If you're looking to hook into one action that provides the ID(s) of
 * the activity/activities deleted, then use:
 *
 * add_action( 'bp_activity_deleted_activities', 'my_function' );
 *
 * The action passes one parameter that is a single activity ID or an
 * array of activity IDs depending on the number deleted.
 *
 * If you are deleting an activity comment please use bp_activity_delete_comment();
 *
 * @since BuddyPress 1.0.0
 *
 * @see BP_Activity_Activity::get() For more information on accepted arguments.
 *
 * @param array|string $args To delete specific activity items, use
 *                           $args = array( 'id' => $ids ); Otherwise, to use
 *                           filters for item deletion, the argument format is
 *                           the same as BP_Activity_Activity::get().
 *                           See that method for a description.
 * @return bool True on success, false on failure.
 */
function bp_activity_delete( $args = '' ) {

	// Pass one or more the of following variables to delete by those variables.
	$args = bp_parse_args(
		$args,
		array(
			'id'                => false,
			'action'            => false,
			'content'           => false,
			'component'         => false,
			'type'              => false,
			'primary_link'      => false,
			'user_id'           => false,
			'item_id'           => false,
			'secondary_item_id' => false,
			'date_recorded'     => false,
			'hide_sitewide'     => false,
		)
	);

	/**
	 * Fires before an activity item proceeds to be deleted.
	 *
	 * @since BuddyPress 1.5.0
	 *
	 * @param array $args Array of arguments to be used with the activity deletion.
	 */
	do_action( 'bp_before_activity_delete', $args );

	// Adjust the new mention count of any mentioned member.
	bp_activity_adjust_mention_count( $args['id'], 'delete' );

	$activity_ids_deleted = BP_Activity_Activity::delete( $args );
	if ( empty( $activity_ids_deleted ) ) {
		return false;
	}

	// Check if the user's latest update has been deleted.
	$user_id = empty( $args['user_id'] )
		? bp_loggedin_user_id()
		: $args['user_id'];

	$latest_update = bp_get_user_meta( $user_id, 'bp_latest_update', true );
	if ( ! empty( $latest_update['id'] ) ) {
		if ( in_array( (int) $latest_update['id'], (array) $activity_ids_deleted ) ) {
			bp_delete_user_meta( $user_id, 'bp_latest_update' );
		}
	}

	/**
	 * Fires after the activity item has been deleted.
	 *
	 * @since BuddyPress 1.0.0
	 *
	 * @param array $args Array of arguments used with the activity deletion.
	 */
	do_action( 'bp_activity_delete', $args );

	/**
	 * Fires after the activity item has been deleted.
	 *
	 * @since BuddyPress 1.2.0
	 *
	 * @param array $activity_ids_deleted Array of affected activity item IDs.
	 */
	do_action( 'bp_activity_deleted_activities', $activity_ids_deleted );

	wp_cache_delete( 'bp_activity_sitewide_front', 'bp' );

	return true;
}

/**
 * Delete users liked activity meta.
 *
 * @since BuddyBoss 1.2.5
 *
 * @param int To delete user id.
 * @return bool True on success, false on failure.
 */
function bp_activity_remove_user_favorite_meta( $user_id = 0 ) {

	if ( empty( $user_id ) ) {
		return false;
	}

	/**
	 * For delete user id from other liked activity
	 */
	$activity_ids = bp_get_user_meta( $user_id, 'bp_favorite_activities', true );

	// Loop through activity ids and attempt to delete favorite.
	if ( ! empty( $activity_ids ) && is_array( $activity_ids ) && count( $activity_ids ) > 0 ) {
		foreach ( $activity_ids as $activity_id ) {
			$activity = new BP_Activity_Activity( $activity_id );
			// Attempt to delete meta value.
			if ( ! empty( $activity->id ) ) {

				// Update the users who have favorited this activity.
				$users = bp_activity_get_meta( $activity_id, 'bp_favorite_users', true );
				if ( empty( $users ) || ! is_array( $users ) ) {
					$users = array();
				}

				$found_user = array_search( $user_id, $users );
				if ( ! empty( $found_user ) ) {
					unset( $users[ $found_user ] );
				}

				// Update activity meta
				bp_activity_update_meta( $activity_id, 'bp_favorite_users', array_unique( array_values( $users ) ) );

				// Update the total number of users who have favorited this activity.
				$fav_count = bp_activity_get_meta( $activity_id, 'favorite_count' );

				if ( ! empty( $fav_count ) ) {
					bp_activity_update_meta( $activity_id, 'favorite_count', (int) $fav_count - 1 );
				}
			}
		}
	}

	return true;
}
	/**
	 * Delete an activity item by activity id.
	 *
	 * You should use bp_activity_delete() instead.
	 *
	 * @since BuddyPress 1.1.0
	 * @deprecated 1.2.0
	 *
	 * @param array|string $args See BP_Activity_Activity::get for a
	 *                           description of accepted arguments.
	 * @return bool True on success, false on failure.
	 */
function bp_activity_delete_by_item_id( $args = '' ) {

	$r = bp_parse_args(
		$args,
		array(
			'item_id'           => false,
			'component'         => false,
			'type'              => false,
			'user_id'           => false,
			'secondary_item_id' => false,
		)
	);

	return bp_activity_delete( $r );
}

	/**
	 * Delete an activity item by activity id.
	 *
	 * @since BuddyPress 1.1.0
	 *
	 * @param int $activity_id ID of the activity item to be deleted.
	 * @return bool True on success, false on failure.
	 */
function bp_activity_delete_by_activity_id( $activity_id ) {
	return bp_activity_delete( array( 'id' => $activity_id ) );
}

	/**
	 * Delete an activity item by its content.
	 *
	 * You should use bp_activity_delete() instead.
	 *
	 * @since BuddyPress 1.1.0
	 * @deprecated 1.2.0
	 *
	 * @param int    $user_id   The user id.
	 * @param string $content   The activity id.
	 * @param string $component The activity component.
	 * @param string $type      The activity type.
	 * @return bool True on success, false on failure.
	 */
function bp_activity_delete_by_content( $user_id, $content, $component, $type ) {
	return bp_activity_delete(
		array(
			'user_id'   => $user_id,
			'content'   => $content,
			'component' => $component,
			'type'      => $type,
		)
	);
}

	/**
	 * Delete a user's activity for a component.
	 *
	 * You should use bp_activity_delete() instead.
	 *
	 * @since BuddyPress 1.1.0
	 * @deprecated 1.2.0
	 *
	 * @param int    $user_id   The user id.
	 * @param string $component The activity component.
	 * @return bool True on success, false on failure.
	 */
function bp_activity_delete_for_user_by_component( $user_id, $component ) {
	return bp_activity_delete(
		array(
			'user_id'   => $user_id,
			'component' => $component,
		)
	);
}

/**
 * Delete an activity comment.
 *
 * @since BuddyPress 1.2.0
 *
 * @todo Why is an activity id required? We could look this up.
 * @todo Why do we encourage users to call this function directly? We could just
 *       as easily examine the activity type in bp_activity_delete() and then
 *       call this function with the proper arguments if necessary.
 *
 * @param int $activity_id The ID of the "root" activity, ie the comment's
 *                         oldest ancestor.
 * @param int $comment_id  The ID of the comment to be deleted.
 * @return bool True on success, false on failure.
 */
function bp_activity_delete_comment( $activity_id, $comment_id ) {
	$deleted = false;

	/**
	 * Filters whether BuddyPress should delete an activity comment or not.
	 *
	 * You may want to hook into this filter if you want to override this function and
	 * handle the deletion of child comments differently. Make sure you return false.
	 *
	 * @since BuddyPress 1.2.0
	 * @since BuddyPress 2.5.0 Add the deleted parameter (passed by reference)
	 *
	 * @param bool $value       Whether BuddyPress should continue or not.
	 * @param int  $activity_id ID of the root activity item being deleted.
	 * @param int  $comment_id  ID of the comment being deleted.
	 * @param bool $deleted     Whether the activity comment has been deleted or not.
	 */
	if ( ! apply_filters_ref_array( 'bp_activity_delete_comment_pre', array( true, $activity_id, $comment_id, &$deleted ) ) ) {
		return $deleted;
	}

	// Check if comment still exists.
	$comment = new BP_Activity_Activity( $comment_id );
	if ( empty( $comment->id ) ) {
		return false;
	}

	// Delete any children of this comment.
	bp_activity_delete_children( $activity_id, $comment_id );

	// Delete the actual comment.
	if ( ! bp_activity_delete(
		array(
			'id'   => $comment_id,
			'type' => 'activity_comment',
		)
	) ) {
		return false;
	} else {
		$deleted = true;
	}

	// Purge comment cache for the root activity update.
	wp_cache_delete( $activity_id, 'bp_activity_comments' );
	wp_cache_delete( 'bp_get_child_comments_' . $activity_id, 'bp_activity_comments' );

	// Recalculate the comment tree.
	BP_Activity_Activity::rebuild_activity_comment_tree( $activity_id );

	/**
	 * Fires at the end of the deletion of an activity comment, before returning success.
	 *
	 * @since BuddyPress 1.2.0
	 *
	 * @param int $activity_id ID of the activity that has had a comment deleted from.
	 * @param int $comment_id  ID of the comment that was deleted.
	 */
	do_action( 'bp_activity_delete_comment', $activity_id, $comment_id );

	return $deleted;
}

	/**
	 * Delete an activity comment's children.
	 *
	 * @since BuddyPress 1.2.0
	 *
	 * @param int $activity_id The ID of the "root" activity, ie the
	 *                         comment's oldest ancestor.
	 * @param int $comment_id  The ID of the comment to be deleted.
	 */
function bp_activity_delete_children( $activity_id, $comment_id ) {
	// Check if comment still exists.
	$comment = new BP_Activity_Activity( $comment_id );
	if ( empty( $comment->id ) ) {
		return;
	}

	// Get activity children to delete.
	$children = BP_Activity_Activity::get_child_comments( $comment_id );

	// Recursively delete all children of this comment.
	if ( ! empty( $children ) ) {
		foreach ( (array) $children as $child ) {
			bp_activity_delete_children( $activity_id, $child->id );
		}
	}

	// Delete the comment itself.
	bp_activity_delete(
		array(
			'secondary_item_id' => $comment_id,
			'type'              => 'activity_comment',
			'item_id'           => $activity_id,
		)
	);
}

/**
 * Get the permalink for a single activity item.
 *
 * When only the $activity_id param is passed, BP has to instantiate a new
 * BP_Activity_Activity object. To save yourself some processing overhead,
 * be sure to pass the full $activity_obj parameter as well, if you already
 * have it available.
 *
 * @since BuddyPress 1.2.0
 *
 * @param int         $activity_id  The unique id of the activity object.
 * @param object|bool $activity_obj Optional. The activity object.
 * @return string $link Permalink for the activity item.
 */
function bp_activity_get_permalink( $activity_id, $activity_obj = false ) {
	$bp = buddypress();

	if ( empty( $activity_obj ) ) {
		$activity_obj = new BP_Activity_Activity( $activity_id );
	}

	if ( isset( $activity_obj->current_comment ) ) {
		$activity_obj = $activity_obj->current_comment;
	}

	$use_primary_links = array(
		'new_blog_post',
		'new_blog_comment',
		'new_forum_topic',
		'new_forum_post',
	);

	if ( ! empty( $bp->activity->track ) ) {
		$use_primary_links = array_merge( $use_primary_links, array_keys( $bp->activity->track ) );
	}

	if ( false !== array_search( $activity_obj->type, $use_primary_links ) ) {
		$link = $activity_obj->primary_link;
	} else {
		if ( 'activity_comment' == $activity_obj->type ) {
			$link = bp_get_root_domain() . '/' . bp_get_activity_root_slug() . '/p/' . $activity_obj->item_id . '/#acomment-' . $activity_obj->id;
		} else {
			$link = bp_get_root_domain() . '/' . bp_get_activity_root_slug() . '/p/' . $activity_obj->id . '/';
		}
	}

	/**
	 * Filters the activity permalink for the specified activity item.
	 *
	 * @since BuddyPress 1.2.0
	 *
	 * @param array $array Array holding activity permalink and activity item object.
	 */
	return apply_filters_ref_array( 'bp_activity_get_permalink', array( $link, &$activity_obj ) );
}

/**
 * Can a user see a particular activity item?
 *
 * @since BuddyPress 3.0.0
 *
 * @param  BP_Activity_Activity $activity Activity object.
 * @param  integer              $user_id  User ID.
 * @return boolean True on success, false on failure.
 */
function bp_activity_user_can_read( $activity, $user_id = 0 ) {
	$retval = true;

	// Fallback.
	if ( empty( $user_id ) ) {
		$user_id = bp_loggedin_user_id();
	}

	// If activity is from a group, do extra cap checks.
	if ( bp_is_active( 'groups' ) && buddypress()->groups->id === $activity->component ) {
		// Check to see if the user has access to the activity's parent group.
		$group = groups_get_group( $activity->item_id );
		if ( $group ) {
			// For logged-in user, we can check against the 'user_has_access' prop.
			if ( bp_loggedin_user_id() === $user_id ) {
				$retval = $group->user_has_access;

				// Manually check status.
			} elseif ( 'private' === $group->status || 'hidden' === $group->status ) {
				// Only group members that are not banned can view.
				if ( ! groups_is_user_member( $user_id, $activity->item_id ) || groups_is_user_banned( $user_id, $activity->item_id ) ) {
					$retval = false;
				}
			}
		}
	}

	// Spammed items are not visible to the public.
	if ( $activity->is_spam ) {
		$retval = false;
	}

	// Site moderators can view anything.
	if ( bp_current_user_can( 'bp_moderate' ) ) {
		$retval = true;
	}

	/**
	 * Filters whether the current user has access to an activity item.
	 *
	 * @since BuddyPress 3.0.0
	 *
	 * @param bool                 $retval   Return value.
	 * @param int                  $user_id  Current user ID.
	 * @param BP_Activity_Activity $activity Activity object.
	 */
	return apply_filters( 'bp_activity_user_can_read', $retval, $user_id, $activity );
}

/**
 * Hide a user's activity.
 *
 * @since BuddyPress 1.2.0
 *
 * @param int $user_id The ID of the user whose activity is being hidden.
 * @return bool True on success, false on failure.
 */
function bp_activity_hide_user_activity( $user_id ) {
	return BP_Activity_Activity::hide_all_for_user( $user_id );
}

/**
 * Take content, remove images, and replace them with a single thumbnail image.
 *
 * The format of items in the activity feed is such that we do not want to
 * allow an arbitrary number of arbitrarily large images to be rendered.
 * However, the activity feed is built to elegantly display a single
 * thumbnail corresponding to the activity comment. This function looks
 * through the content, grabs the first image and converts it to a thumbnail,
 * and removes the rest of the images from the string.
 *
 * As of BuddyPress 2.3, this function is no longer in use.
 *
 * @since BuddyPress 1.2.0
 *
 * @param string      $content The content of the activity item.
 * @param string|bool $link    Optional. The unescaped URL that the image should link
 *                             to. If absent, the image will not be a link.
 * @param array|bool  $args    Optional. The args passed to the activity
 *                             creation function (eg bp_blogs_record_activity()).
 * @return string $content The content with images stripped and replaced with a
 *                         single thumb.
 */
function bp_activity_thumbnail_content_images( $content, $link = false, $args = false ) {

	preg_match_all( '/<img[^>]*>/Ui', $content, $matches );

	// Remove <img> tags. Also remove caption shortcodes and caption text if present.
	$content = preg_replace( '|(\[caption(.*?)\])?<img[^>]*>([^\[\[]*\[\/caption\])?|', '', $content );

	if ( ! empty( $matches ) && ! empty( $matches[0] ) ) {

		// Get the SRC value.
		preg_match( '/<img.*?(src\=[\'|"]{0,1}.*?[\'|"]{0,1})[\s|>]{1}/i', $matches[0][0], $src );

		// Get the width and height.
		preg_match( '/<img.*?(height\=[\'|"]{0,1}.*?[\'|"]{0,1})[\s|>]{1}/i', $matches[0][0], $height );
		preg_match( '/<img.*?(width\=[\'|"]{0,1}.*?[\'|"]{0,1})[\s|>]{1}/i', $matches[0][0], $width );

		if ( ! empty( $src ) ) {
			$src = substr( substr( str_replace( 'src=', '', $src[1] ), 0, -1 ), 1 );

			if ( isset( $width[1] ) ) {
				$width = substr( substr( str_replace( 'width=', '', $width[1] ), 0, -1 ), 1 );
			}

			if ( isset( $height[1] ) ) {
				$height = substr( substr( str_replace( 'height=', '', $height[1] ), 0, -1 ), 1 );
			}

			if ( empty( $width ) || empty( $height ) ) {
				$width  = 100;
				$height = 100;
			}

			$ratio      = (int) $width / (int) $height;
			$new_height = (int) $height >= 100 ? 100 : $height;
			$new_width  = $new_height * $ratio;
			$image      = '<img src="' . esc_url( $src ) . '" width="' . absint( $new_width ) . '" height="' . absint( $new_height ) . '" alt="' . __( 'Thumbnail', 'buddyboss' ) . '" class="align-left thumbnail" />';

			if ( ! empty( $link ) ) {
				$image = '<a href="' . esc_url( $link ) . '">' . $image . '</a>';
			}

			$content = $image . $content;
		}
	}

	/**
	 * Filters the activity content that had a thumbnail replace images.
	 *
	 * @since BuddyPress 1.2.0
	 *
	 * @param string $content Activity content that had images replaced in.
	 * @param array  $matches Array of all image tags found in the posted content.
	 * @param array  $args    Arguments passed into function creating the activity update.
	 */
	return apply_filters( 'bp_activity_thumbnail_content_images', $content, $matches, $args );
}

/**
 * Gets the excerpt length for activity items.
 *
 * @since BuddyPress 2.8.0
 *
 * @return int Character length for activity excerpts.
 */
function bp_activity_get_excerpt_length() {
	/**
	 * Filters the excerpt length for the activity excerpt.
	 *
	 * @since BuddyPress 1.5.0
	 *
	 * @param int Character length for activity excerpts.
	 */
	return (int) apply_filters( 'bp_activity_excerpt_length', 358 );
}

/**
 * Create a rich summary of an activity item for the activity feed.
 *
 * More than just a simple excerpt, the summary could contain oEmbeds and other types of media.
 * Currently, it's only used for blog post items, but it will probably be used for all types of
 * activity in the future.
 *
 * @since BuddyPress 2.3.0
 *
 * @param string $content  The content of the activity item.
 * @param array  $activity The data passed to bp_activity_add() or the values
 *                         from an Activity obj.
 * @return string $summary
 */
function bp_activity_create_summary( $content, $activity ) {
	$args = array(
		'width' => isset( $GLOBALS['content_width'] ) ? (int) $GLOBALS['content_width'] : 'medium',
	);

	// Get the WP_Post object if this activity type is a blog post.
	if ( 'new_blog_post' === $activity['type'] ) {
		$content = get_post( $activity['secondary_item_id'] );
	}

	/**
	 * Filter the class name of the media extractor when creating an Activity summary.
	 *
	 * Use this filter to change the media extractor used to extract media info for the activity item.
	 *
	 * @since BuddyPress 2.3.0
	 *
	 * @param string $extractor Class name.
	 * @param string $content   The content of the activity item.
	 * @param array  $activity  The data passed to bp_activity_add() or the values from an Activity obj.
	 */
	$extractor = apply_filters( 'bp_activity_create_summary_extractor_class', 'BP_Media_Extractor', $content, $activity );
	$extractor = new $extractor();

	/**
	 * Filter the arguments passed to the media extractor when creating an Activity summary.
	 *
	 * @since BuddyPress 2.3.0
	 *
	 * @param array              $args      Array of bespoke data for the media extractor.
	 * @param string             $content   The content of the activity item.
	 * @param array              $activity  The data passed to bp_activity_add() or the values from an Activity obj.
	 * @param BP_Media_Extractor $extractor The media extractor object.
	 */
	$args = apply_filters( 'bp_activity_create_summary_extractor_args', $args, $content, $activity, $extractor );

	// Extract media information from the $content.
	$media = $extractor->extract( $content, BP_Media_Extractor::ALL, $args );

	// If we converted $content to an object earlier, flip it back to a string.
	if ( is_a( $content, 'WP_Post' ) ) {

		// For the post and custom post type get the excerpt first.
		$excerpt = get_the_excerpt( $content->ID );

		// Get the excerpt first if found otherwise it will take the post content.
		$content = ( $excerpt ) ?: $content->post_content;
	}

	$para_count     = substr_count( strtolower( wpautop( $content ) ), '<p>' );
	$has_audio      = ! empty( $media['has']['audio'] ) && $media['has']['audio'];
	$has_videos     = ! empty( $media['has']['videos'] ) && $media['has']['videos'];
	$has_feat_image = ! empty( $media['has']['featured_images'] ) && $media['has']['featured_images'];
	$has_galleries  = ! empty( $media['has']['galleries'] ) && $media['has']['galleries'];
	$has_images     = ! empty( $media['has']['images'] ) && $media['has']['images'];
	$has_embeds     = false;

	// Embeds must be subtracted from the paragraph count.
	if ( ! empty( $media['has']['embeds'] ) ) {
		$has_embeds  = $media['has']['embeds'] > 0;
		$para_count -= $media['has']['embeds'];
	}

	$extracted_media = array();
	$use_media_type  = '';
	$image_source    = '';

	// If it's a short article and there's an embed/audio/video, use it.
	if ( $para_count <= 3 ) {
		if ( $has_embeds ) {
			$use_media_type = 'embeds';
		} elseif ( $has_audio ) {
			$use_media_type = 'audio';
		} elseif ( $has_videos ) {
			$use_media_type = 'videos';
		}
	}

	// If not, or in any other situation, try to use an image.
	if ( ! $use_media_type && $has_images ) {
		$use_media_type = 'images';
		$image_source   = 'html';

		// Featured Image > Galleries > inline <img>.
		if ( $has_feat_image ) {
			$image_source = 'featured_images';

		} elseif ( $has_galleries ) {
			$image_source = 'galleries';
		}
	}

	// Extract an item from the $media results.
	if ( $use_media_type ) {
		if ( $use_media_type === 'images' ) {
			$extracted_media = wp_list_filter( $media[ $use_media_type ], array( 'source' => $image_source ) );
			$extracted_media = array_shift( $extracted_media );
		} else {
			$extracted_media = array_shift( $media[ $use_media_type ] );
		}

		/**
		 * Filter the results of the media extractor when creating an Activity summary.
		 *
		 * @since BuddyPress 2.3.0
		 *
		 * @param array  $extracted_media Extracted media item. See {@link BP_Media_Extractor::extract()} for format.
		 * @param string $content         Content of the activity item.
		 * @param array  $activity        The data passed to bp_activity_add() or the values from an Activity obj.
		 * @param array  $media           All results from the media extraction.
		 *                                See {@link BP_Media_Extractor::extract()} for format.
		 * @param string $use_media_type  The kind of media item that was preferentially extracted.
		 * @param string $image_source    If $use_media_type was "images", the preferential source of the image.
		 *                                Otherwise empty.
		 */
		$extracted_media = apply_filters(
			'bp_activity_create_summary_extractor_result',
			$extracted_media,
			$content,
			$activity,
			$media,
			$use_media_type,
			$image_source
		);
	}

	// Generate a text excerpt for this activity item (and remove any oEmbeds URLs).
	$summary = bp_create_excerpt(
		html_entity_decode( $content, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401 ),
		225,
		array(
			'html'              => false,
			'filter_shortcodes' => true,
			'strip_tags'        => true,
			'remove_links'      => true,
		)
	);

	if ( 'embeds' === $use_media_type ) {
		$summary .= PHP_EOL . PHP_EOL . '<p>' . $extracted_media['url'] . '</p>';
	} elseif ( 'images' === $use_media_type ) {
		$extracted_media_url = isset( $extracted_media['url'] ) ? $extracted_media['url'] : '';
		$summary            .= sprintf( ' <img src="%s">', esc_url( $extracted_media_url ) );
	} elseif ( in_array( $use_media_type, array( 'audio', 'videos' ), true ) ) {
		$summary .= PHP_EOL . PHP_EOL . $extracted_media['original'];  // Full shortcode.
	}

	/**
	 * Filters the newly-generated summary for the activity item.
	 *
	 * @since BuddyPress 2.3.0
	 *
	 * @param string $summary         Activity summary HTML.
	 * @param string $content         Content of the activity item.
	 * @param array  $activity        The data passed to bp_activity_add() or the values from an Activity obj.
	 * @param array  $extracted_media Media item extracted. See {@link BP_Media_Extractor::extract()} for format.
	 */
	return apply_filters( 'bp_activity_create_summary', $summary, $content, $activity, $extracted_media );
}

/**
 * Fetch whether the current user is allowed to mark items as spam.
 *
 * @since BuddyPress 1.6.0
 *
 * @return bool True if user is allowed to mark activity items as spam.
 */
function bp_activity_user_can_mark_spam() {

	/**
	 * Filters whether the current user should be able to mark items as spam.
	 *
	 * @since BuddyPress 1.6.0
	 *
	 * @param bool $moderate Whether or not the current user has bp_moderate capability.
	 */
	return apply_filters( 'bp_activity_user_can_mark_spam', bp_current_user_can( 'bp_moderate' ) );
}

/**
 * Mark an activity item as spam.
 *
 * @since BuddyPress 1.6.0
 *
 * @todo We should probably save $source to activity meta.
 *
 * @param BP_Activity_Activity $activity The activity item to be spammed.
 * @param string               $source   Optional. Default is "by_a_person" (ie, a person has
 *                                       manually marked the activity as spam). BP core also
 *                                       accepts 'by_akismet'.
 */
function bp_activity_mark_as_spam( &$activity, $source = 'by_a_person' ) {
	$bp = buddypress();

	$activity->is_spam = 1;

	// Clear the activity feed first page cache.
	wp_cache_delete( 'bp_activity_sitewide_front', 'bp' );

	// Clear the activity comment cache for this activity item.
	wp_cache_delete( $activity->id, 'bp_activity_comments' );
	wp_cache_delete( 'bp_get_child_comments_' . $activity->id, 'bp_activity_comments' );

	// If Akismet is active, and this was a manual spam/ham request, stop Akismet checking the activity.
	if ( 'by_a_person' == $source && ! empty( $bp->activity->akismet ) ) {
		remove_action( 'bp_activity_before_save', array( $bp->activity->akismet, 'check_activity' ), 4 );

		// Build data package for Akismet.
		$activity_data = BP_Akismet::build_akismet_data_package( $activity );

		// Tell Akismet this is spam.
		$activity_data = $bp->activity->akismet->send_akismet_request( $activity_data, 'submit', 'spam' );

		// Update meta.
		add_action( 'bp_activity_after_save', array( $bp->activity->akismet, 'update_activity_spam_meta' ), 1, 1 );
	}

	/**
	 * Fires at the end of the process to mark an activity item as spam.
	 *
	 * @since BuddyPress 1.6.0
	 *
	 * @param BP_Activity_Activity $activity Activity item being marked as spam.
	 * @param string               $source   Source of determination of spam status. For example
	 *                                       "by_a_person" or "by_akismet".
	 */
	do_action( 'bp_activity_mark_as_spam', $activity, $source );
}

/**
 * Mark an activity item as ham.
 *
 * @since BuddyPress 1.6.0
 *
 * @param BP_Activity_Activity $activity The activity item to be hammed. Passed by reference.
 * @param string               $source   Optional. Default is "by_a_person" (ie, a person has
 *                                       manually marked the activity as spam). BP core also accepts
 *                                       'by_akismet'.
 */
function bp_activity_mark_as_ham( &$activity, $source = 'by_a_person' ) {
	$bp = buddypress();

	$activity->is_spam = 0;

	// Clear the activity feed first page cache.
	wp_cache_delete( 'bp_activity_sitewide_front', 'bp' );

	// Clear the activity comment cache for this activity item.
	wp_cache_delete( $activity->id, 'bp_activity_comments' );
	wp_cache_delete( 'bp_get_child_comments_' . $activity->id, 'bp_activity_comments' );

	// If Akismet is active, and this was a manual spam/ham request, stop Akismet checking the activity.
	if ( 'by_a_person' == $source && ! empty( $bp->activity->akismet ) ) {
		remove_action( 'bp_activity_before_save', array( $bp->activity->akismet, 'check_activity' ), 4 );

		// Build data package for Akismet.
		$activity_data = BP_Akismet::build_akismet_data_package( $activity );

		// Tell Akismet this is spam.
		$activity_data = $bp->activity->akismet->send_akismet_request( $activity_data, 'submit', 'ham' );

		// Update meta.
		add_action( 'bp_activity_after_save', array( $bp->activity->akismet, 'update_activity_ham_meta' ), 1, 1 );
	}

	/**
	 * Fires at the end of the process to mark an activity item as ham.
	 *
	 * @since BuddyPress 1.6.0
	 *
	 * @param BP_Activity_Activity $activity Activity item being marked as ham.
	 * @param string               $source   Source of determination of ham status. For example
	 *                                       "by_a_person" or "by_akismet".
	 */
	do_action( 'bp_activity_mark_as_ham', $activity, $source );
}

/* Emails *********************************************************************/

/**
 * Send email and BP notifications when a user is mentioned in an update.
 *
 * @since BuddyPress 1.2.0
 *
 * @param int $activity_id      The ID of the activity update.
 * @param int $receiver_user_id The ID of the user who is receiving the update.
 */
function bp_activity_at_message_notification( $activity_id, $receiver_user_id ) {
	$notifications = BP_Core_Notification::get_all_for_user( $receiver_user_id, 'all' );

	// Don't leave multiple notifications for the same activity item.
	foreach ( $notifications as $notification ) {
		if ( $activity_id == $notification->item_id ) {
			return;
		}
	}

	$activity     = new BP_Activity_Activity( $activity_id );
	$email_type   = 'activity-at-message';
	$group_name   = '';
	$message_link = bp_activity_get_permalink( $activity_id );
	$poster_name  = bp_core_get_user_displayname( $activity->user_id );

	remove_filter( 'bp_get_activity_content_body', 'convert_smilies' );
	remove_filter( 'bp_get_activity_content_body', 'wpautop' );
	remove_filter( 'bp_get_activity_content_body', 'bp_activity_truncate_entry', 5 );

	/** This filter is documented in bp-activity/bp-activity-template.php */
	$content = apply_filters_ref_array( 'bp_get_activity_content_body', array( $activity->content, &$activity ) );

	add_filter( 'bp_get_activity_content_body', 'convert_smilies' );
	add_filter( 'bp_get_activity_content_body', 'wpautop' );
	add_filter( 'bp_get_activity_content_body', 'bp_activity_truncate_entry', 5 );

	$type_key = 'notification_activity_new_mention';

	if ( ! bb_enabled_legacy_email_preference() ) {
		$email_type = 'new-mention';
		$type_key   = bb_get_prefences_key( 'legacy', $type_key );
	}

	// Now email the user with the contents of the message (if they have enabled email notifications).
	if ( true === bb_is_notification_enabled( $receiver_user_id, $type_key ) ) {

		// Check the sender is blocked by recipient or not.
		if ( true === (bool) apply_filters( 'bb_is_recipient_moderated', false, $receiver_user_id, get_current_user_id() ) ) {
			return;
		}

		if ( bp_is_active( 'groups' ) && bp_is_group() ) {
			$email_type = 'groups-at-message';
			$group_name = bp_get_current_group_name();

			if ( ! bb_enabled_legacy_email_preference() ) {
				$email_type = 'new-mention-group';
			}
		}

		$unsubscribe_args = array(
			'user_id'           => $receiver_user_id,
			'notification_type' => $email_type,
		);

		$notification_type_html = '';
		$reply_text             = '';
		$title_text             = '';

		if ( 'activity_comment' === $activity->type ) {
			if ( ! empty( $activity->item_id ) ) {
				$parent_activity = new BP_Activity_Activity( $activity->item_id );
				if ( ! empty( $parent_activity ) && 'blogs' === $parent_activity->component ) {
					$notification_type_html = esc_html__( 'post', 'buddyboss' );
					$title_text             = get_the_title( $parent_activity->secondary_item_id );
					$message_link           = get_permalink( $parent_activity->secondary_item_id );
				} else {
					$notification_type_html = esc_html__( 'post', 'buddyboss' );
				}
			} else {
				$notification_type_html = esc_html__( 'post', 'buddyboss' );
			}
			$reply_text = esc_html__( 'View Comment', 'buddyboss' );
		} elseif ( 'blogs' === $activity->component ) {
			$notification_type_html = esc_html__( 'post', 'buddyboss' );
			$reply_text             = esc_html__( 'View Post', 'buddyboss' );
			$title_text             = get_the_title( $activity->secondary_item_id );
			$message_link           = get_permalink( $activity->secondary_item_id );
		} else {
			$notification_type_html = esc_html__( 'post', 'buddyboss' );
			$reply_text             = esc_html__( 'View Post', 'buddyboss' );
		}

		$args = array(
			'tokens' => array(
				'activity'          => $activity,
				'usermessage'       => wp_strip_all_tags( $content ),
				'group.name'        => $group_name,
				'mentioned.url'     => $message_link,
				'poster.name'       => $poster_name,
				'receiver-user.id'  => $receiver_user_id,
				'unsubscribe'       => esc_url( bp_email_get_unsubscribe_link( $unsubscribe_args ) ),
				'mentioned.type'    => $notification_type_html,
				'mentioned.content' => '',
				'reply_text'        => $reply_text,
				'title_text'        => $title_text,
			),
		);

		bp_send_email( $email_type, $receiver_user_id, $args );
	}

	/**
	 * Fires after the sending of an @mention email notification.
	 *
	 * @since BuddyPress 1.5.0
	 * @since BuddyPress 2.5.0 $subject, $message, $content arguments unset and deprecated.
	 *
	 * @param BP_Activity_Activity $activity         Activity Item object.
	 * @param string               $deprecated       Removed in 2.5; now an empty string.
	 * @param string               $deprecated       Removed in 2.5; now an empty string.
	 * @param string               $deprecated       Removed in 2.5; now an empty string.
	 * @param int                  $receiver_user_id The ID of the user who is receiving the update.
	 */
	do_action( 'bp_activity_sent_mention_email', $activity, '', '', '', $receiver_user_id );
}

/**
 * Send email and BP notifications when an activity item receives a comment.
 *
 * @since BuddyPress 1.2.0
 * @since BuddyPress 2.5.0 Updated to use new email APIs.
 *
 * @param int   $comment_id   The comment id.
 * @param int   $commenter_id The ID of the user who posted the comment.
 * @param array $params       {@link bp_activity_new_comment()}.
 */
function bp_activity_new_comment_notification( $comment_id = 0, $commenter_id = 0, $params = array() ) {
	$original_activity = new BP_Activity_Activity( $params['activity_id'] );
	$poster_name       = bp_core_get_user_displayname( $commenter_id );
	$thread_link       = bp_activity_get_permalink( $params['activity_id'] );
	$usernames         = bp_activity_do_mentions() ? bp_activity_find_mentions( $params['content'] ) : array();

	remove_filter( 'bp_get_activity_content_body', 'convert_smilies' );
	remove_filter( 'bp_get_activity_content_body', 'wpautop' );
	remove_filter( 'bp_get_activity_content_body', 'bp_activity_truncate_entry', 5 );

	/** This filter is documented in bp-activity/bp-activity-template.php */
	$content = apply_filters_ref_array( 'bp_get_activity_content_body', array( $params['content'], &$original_activity ) );

	add_filter( 'bp_get_activity_content_body', 'convert_smilies' );
	add_filter( 'bp_get_activity_content_body', 'wpautop' );
	add_filter( 'bp_get_activity_content_body', 'bp_activity_truncate_entry', 5 );

	$type_key = 'notification_activity_new_reply';
	if ( ! bb_enabled_legacy_email_preference() ) {
		$type_key = bb_get_prefences_key( 'legacy', $type_key );
	}

	if ( $original_activity->user_id != $commenter_id ) {
		if (
			(
				function_exists( 'bb_moderation_allowed_specific_notification' ) &&
				bb_moderation_allowed_specific_notification(
					array(
						'type'              => buddypress()->activity->id,
						'group_id'          => 'groups' === $original_activity->component ? $original_activity->item_id : '',
						'recipient_user_id' => $original_activity->user_id,
						'sender_id'         => $original_activity->user_id,
					)
				)
			) ||
			(
				'groups' === $original_activity->component &&
				1 === $original_activity->hide_sitewide &&
				! groups_is_user_member( $original_activity->user_id, $original_activity->item_id )
			)
		) {
			return;
		}

		$send_email = true;

		if ( ! empty( $usernames ) && array_key_exists( $original_activity->user_id, $usernames ) ) {
			if ( true === bb_is_notification_enabled( $original_activity->user_id, 'bb_new_mention' ) ) {
				$send_email = false;
			}
		}

		// Send an email if the user hasn't opted-out.
		if ( true === $send_email && true === bb_is_notification_enabled( $original_activity->user_id, $type_key ) ) {

			$unsubscribe_args = array(
				'user_id'           => $original_activity->user_id,
				'notification_type' => 'activity-comment',
			);

			$args = array(
				'tokens' => array(
					'comment.id'                => $comment_id,
					'commenter.id'              => $commenter_id,
					'usermessage'               => wp_strip_all_tags( $content ),
					'original_activity.user_id' => $original_activity->user_id,
					'poster.name'               => $poster_name,
					'thread.url'                => esc_url( $thread_link ),
					'unsubscribe'               => esc_url( bp_email_get_unsubscribe_link( $unsubscribe_args ) ),
				),
			);

			bp_send_email( 'activity-comment', $original_activity->user_id, $args );
		}

		/**
		 * Fires at the point that notifications should be sent for activity comments.
		 *
		 * @since BuddyPress 2.6.0
		 *
		 * @param BP_Activity_Activity $original_activity The original activity.
		 * @param int                  $comment_id        ID for the newly received comment.
		 * @param int                  $commenter_id      ID of the user who made the comment.
		 * @param array                $params            Arguments used with the original activity comment.
		 */
		do_action( 'bp_activity_sent_reply_to_update_notification', $original_activity, $comment_id, $commenter_id, $params );
	}

	/*
	 * If this is a reply to another comment, send an email notification to the
	 * author of the immediate parent comment.
	 */
	if ( empty( $params['parent_id'] ) || ( $params['activity_id'] == $params['parent_id'] ) ) {
		return;
	}

	$parent_comment = new BP_Activity_Activity( $params['parent_id'] );

	if ( $parent_comment->user_id != $commenter_id && $original_activity->user_id != $parent_comment->user_id ) {
		if (
			(
				function_exists( 'bb_moderation_allowed_specific_notification' ) &&
				bb_moderation_allowed_specific_notification(
					array(
						'type'              => buddypress()->activity->id,
						'group_id'          => 'groups' === $original_activity->component ? $original_activity->item_id : '',
						'recipient_user_id' => $parent_comment->user_id,
						'sender_id'         => $original_activity->user_id,
					)
				)
			) ||
			(
				'groups' === $parent_comment->component &&
				1 === $parent_comment->hide_sitewide &&
				! groups_is_user_member( $parent_comment->user_id, $parent_comment->item_id )
			)
		) {
			return;
		}

		$send_email = true;

		if ( ! empty( $usernames ) && array_key_exists( $parent_comment->user_id, $usernames ) ) {
			if ( true === bb_is_notification_enabled( $parent_comment->user_id, 'bb_new_mention' ) ) {
				$send_email = false;
			}
		}

		// Send an email if the user hasn't opted-out.
		if ( true === $send_email && true === bb_is_notification_enabled( $parent_comment->user_id, $type_key ) ) {

			$unsubscribe_args = array(
				'user_id'           => $parent_comment->user_id,
				'notification_type' => 'activity-comment-author',
			);

			$args = array(
				'tokens' => array(
					'comment.id'             => $comment_id,
					'commenter.id'           => $commenter_id,
					'usermessage'            => wp_strip_all_tags( $content ),
					'parent-comment-user.id' => $parent_comment->user_id,
					'poster.name'            => $poster_name,
					'thread.url'             => esc_url( $thread_link ),
					'unsubscribe'            => esc_url( bp_email_get_unsubscribe_link( $unsubscribe_args ) ),
				),
			);

			bp_send_email( 'activity-comment-author', $parent_comment->user_id, $args );
		}

		/**
		 * Fires at the point that notifications should be sent for comments on activity replies.
		 *
		 * @since BuddyPress 2.6.0
		 *
		 * @param BP_Activity_Activity $parent_comment The parent activity.
		 * @param int                  $comment_id     ID for the newly received comment.
		 * @param int                  $commenter_id   ID of the user who made the comment.
		 * @param array                $params         Arguments used with the original activity comment.
		 */
		do_action( 'bp_activity_sent_reply_to_reply_notification', $parent_comment, $comment_id, $commenter_id, $params );
	}
}

/**
 * Return if the activity stream should show activty comments as streamed or threaded
 */
function bp_show_streamed_activity_comment() {
	return apply_filters( 'bp_show_streamed_activity_comment', bp_get_option( 'show_streamed_activity_comment', false ) );
}

/**
 * Helper method to map action arguments to function parameters.
 *
 * @since BuddyPress 1.9.0
 *
 * @param int   $comment_id ID of the comment being notified about.
 * @param array $params     Parameters to use with notification.
 */
function bp_activity_new_comment_notification_helper( $comment_id, $params ) {
	global $bb_activity_comment_edit;

	// Return if $comment_id empty or edit activity comment.
	if ( empty( $comment_id ) || $bb_activity_comment_edit ) {
		return;
	}

	bp_activity_new_comment_notification( $comment_id, $params['user_id'], $params );
}
add_action( 'bp_activity_comment_posted', 'bp_activity_new_comment_notification_helper', 10, 2 );

/** Embeds *******************************************************************/

/**
 * Set up activity oEmbed cache during the activity loop.
 *
 * During an activity loop, this function sets up the hooks necessary to grab
 * each item's embeds from the cache, or put them in the cache if they are
 * not there yet.
 *
 * This does not cover recursive activity comments, as they do not use a real loop.
 * For that, see {@link bp_activity_comment_embed()}.
 *
 * @since BuddyPress 1.5.0
 *
 * @see BP_Embed
 * @see bp_embed_activity_cache()
 * @see bp_embed_activity_save_cache()
 */
function bp_activity_embed() {
	add_filter( 'embed_post_id', 'bp_get_activity_id' );
	add_filter( 'oembed_dataparse', 'bp_activity_oembed_dataparse', 10, 2 );
	add_filter( 'bp_embed_get_cache', 'bp_embed_activity_cache', 10, 3 );
	add_action( 'bp_embed_update_cache', 'bp_embed_activity_save_cache', 10, 3 );
}
add_action( 'activity_loop_start', 'bp_activity_embed' );

/**
 * Cache full oEmbed response from oEmbed.
 *
 * @since BuddyPress 2.6.0
 *
 * @param string $retval Current oEmbed result.
 * @param object $data   Full oEmbed response.
 * @param string $url    URL used for the oEmbed request.
 * @return string
 */
function bp_activity_oembed_dataparse( $retval, $data ) {
	buddypress()->activity->oembed_response = $data;

	return $retval;
}

/**
 * Set up activity oEmbed cache while recursing through activity comments.
 *
 * While crawling through an activity comment tree
 * ({@link bp_activity_recurse_comments}), this function sets up the hooks
 * necessary to grab each comment's embeds from the cache, or put them in
 * the cache if they are not there yet.
 *
 * @since BuddyPress 1.5.0
 *
 * @see BP_Embed
 * @see bp_embed_activity_cache()
 * @see bp_embed_activity_save_cache()
 */
function bp_activity_comment_embed() {
	add_filter( 'embed_post_id', 'bp_get_activity_comment_id' );
	add_filter( 'bp_embed_get_cache', 'bp_embed_activity_cache', 10, 3 );
	add_action( 'bp_embed_update_cache', 'bp_embed_activity_save_cache', 10, 3 );
}
add_action( 'bp_before_activity_comment', 'bp_activity_comment_embed' );

/**
 * When a user clicks on a "Read More" item, make sure embeds are correctly parsed and shown for the expanded content.
 *
 * @since BuddyPress 1.5.0
 *
 * @see BP_Embed
 *
 * @param object $activity The activity that is being expanded.
 */
function bp_dtheme_embed_read_more( $activity ) {
	buddypress()->activity->read_more_id = $activity->id;

	add_filter(
		'embed_post_id',
		function() {
			return buddypress()->activity->read_more_id;
		}
	);
	add_filter( 'bp_embed_get_cache', 'bp_embed_activity_cache', 10, 3 );
	add_action( 'bp_embed_update_cache', 'bp_embed_activity_save_cache', 10, 3 );
}
add_action( 'bp_dtheme_get_single_activity_content', 'bp_dtheme_embed_read_more' );

/**
 * Clean up 'embed_post_id' filter after comment recursion.
 *
 * This filter must be removed so that the non-comment filters take over again
 * once the comments are done being processed.
 *
 * @since BuddyPress 1.5.0
 *
 * @see bp_activity_comment_embed()
 */
function bp_activity_comment_embed_after_recurse() {
	remove_filter( 'embed_post_id', 'bp_get_activity_comment_id' );
}
add_action( 'bp_after_activity_comment', 'bp_activity_comment_embed_after_recurse' );

/**
 * Fetch an activity item's cached embeds.
 *
 * Used during {@link BP_Embed::parse_oembed()} via {@link bp_activity_embed()}.
 *
 * @since BuddyPress 1.5.0
 *
 * @see BP_Embed::parse_oembed()
 *
 * @param string $cache    An empty string passed by BP_Embed::parse_oembed() for
 *                         functions like this one to filter.
 * @param int    $id       The ID of the activity item.
 * @param string $cachekey The cache key generated in BP_Embed::parse_oembed().
 * @return mixed The cached embeds for this activity item.
 */
function bp_embed_activity_cache( $cache, $id, $cachekey ) {
	$data = bp_activity_get_meta( $id, $cachekey );

	if (
		! empty( $data ) &&
		false !== strpos( $data, 'loom.com' ) &&
		false !== strpos( $data, 'sandbox' )
	) {
		return false;
	}

	return $data;
}

/**
 * Set an activity item's embed cache.
 *
 * Used during {@link BP_Embed::parse_oembed()} via {@link bp_activity_embed()}.
 *
 * @since BuddyPress 1.5.0
 *
 * @see BP_Embed::parse_oembed()
 *
 * @param string $cache    An empty string passed by BP_Embed::parse_oembed() for
 *                         functions like this one to filter.
 * @param string $cachekey The cache key generated in BP_Embed::parse_oembed().
 * @param int    $id       The ID of the activity item.
 */
function bp_embed_activity_save_cache( $cache, $cachekey, $id ) {
	bp_activity_update_meta( $id, $cachekey, $cache );

	// Cache full oEmbed response.
	if ( true === isset( buddypress()->activity->oembed_response ) ) {
		$cachekey = str_replace( '_oembed', '_oembed_response', $cachekey );
		bp_activity_update_meta( $id, $cachekey, buddypress()->activity->oembed_response );
	}
}

/**
 * Should we use Heartbeat to refresh activities?
 *
 * @since BuddyPress 2.0.0
 *
 * @return bool True if activity heartbeat is enabled, otherwise false.
 */
function bp_activity_do_heartbeat() {
	$retval = false;

	if ( bp_is_activity_heartbeat_active() && ( bp_is_activity_directory() || bp_is_group_activity() || bp_is_user_activity() ) ) {
		$retval = true;
	}

	/**
	 * Filters whether the heartbeat feature in the activity feed should be active.
	 *
	 * @since BuddyPress 2.8.0
	 *
	 * @param bool $retval Whether or not activity heartbeat is active.
	 */
	return (bool) apply_filters( 'bp_activity_do_heartbeat', $retval );
}

/**
 * AJAX endpoint for activity comments.
 *
 * @since BuddyBoss 1.0.0
 */
function bp_ajax_get_comments() {
	if ( empty( $_GET['activity_id'] ) ) {
		exit;
	}

	if ( bp_has_activities( 'include=' . $_GET['activity_id'] ) ) {
		while ( bp_activities() ) {
			bp_the_activity();
			bp_nouveau_activity_comments();
			exit;
		}
	}
}

// add_action( 'wp_ajax_bp_get_comments', 'bp_ajax_get_comments' );

/**
 * Detect a change in post type status, and initiate an activity update if necessary.
 *
 * @since BuddyPress 2.2.0
 *
 * @todo Support untrashing better.
 *
 * @param string $new_status New status for the post.
 * @param string $old_status Old status for the post.
 * @param object $post       Post data.
 */
function bp_activity_catch_transition_post_type_status( $new_status, $old_status, $post ) {
	if ( ! post_type_supports( $post->post_type, 'buddypress-activity' ) ) {
		return;
	}
	/**
	 * When enabled sync comment option from activity section then comment was going empty when
	 * reply from blog or custom post types.
	 */
	remove_action( 'bp_activity_before_save', 'bp_blogs_sync_activity_edit_to_post_comment', 20 );
	/**
	 * Fires before post type transition catch in activity
	 *
	 * @param bool true True for default.
	 * @param string $new_status New status for the post.
	 * @param string $old_status Old status for the post.
	 * @param WP_Post $post Post data.
	 *
	 * @since BuddyBoss 1.2.3
	 */
	$pre_transition = apply_filters( 'bp_activity_pre_transition_post_type_status', true, $new_status, $old_status, $post );

	if ( false === $pre_transition ) {
		return;
	}

	// This is an edit.
	if ( $new_status === $old_status ) {
		// An edit of an existing post should update the existing activity item.
		if ( $new_status == 'publish' ) {
			$edit = bp_activity_post_type_update( $post );

			// Post was never recorded into activity feed, so record it now!
			if ( null === $edit ) {
				bp_activity_post_type_publish( $post->ID, $post );
			}

			// Allow plugins to eventually deal with other post statuses.
		} else {
			/**
			 * Fires when editing the post and the new status is not 'publish'.
			 *
			 * This is a variable filter that is dependent on the post type
			 * being untrashed.
			 *
			 * @since BuddyPress 2.5.0
			 *
			 * @param WP_Post $post Post data.
			 * @param string $new_status New status for the post.
			 * @param string $old_status Old status for the post.
			 */
			do_action( 'bp_activity_post_type_edit_' . $post->post_type, $post, $new_status, $old_status );
		}

		return;
	}

	// Publishing a previously unpublished post.
	if ( 'publish' === $new_status ) {
		// Untrashing the post type - nothing here yet.
		if ( 'trash' == $old_status ) {

			/**
			 * Fires if untrashing post in a post type.
			 *
			 * This is a variable filter that is dependent on the post type
			 * being untrashed.
			 *
			 * @since BuddyPress 2.2.0
			 *
			 * @param WP_Post $post Post data.
			 */
			do_action( 'bp_activity_post_type_untrash_' . $post->post_type, $post );
		} else {
			// Record the post.
			bp_activity_post_type_publish( $post->ID, $post );
		}

		// Unpublishing a previously published post.
	} elseif ( 'publish' === $old_status ) {
		// Some form of pending status - only remove the activity entry.
		bp_activity_post_type_unpublish( $post->ID, $post );

		// For any other cases, allow plugins to eventually deal with it.
	} else {
		/**
		 * Fires when the old and the new post status are not 'publish'.
		 *
		 * This is a variable filter that is dependent on the post type
		 * being untrashed.
		 *
		 * @since BuddyPress 2.5.0
		 *
		 * @param WP_Post $post Post data.
		 * @param string $new_status New status for the post.
		 * @param string $old_status Old status for the post.
		 */
		do_action( 'bp_activity_post_type_transition_status_' . $post->post_type, $post, $new_status, $old_status );
	}
	/**
	 * When enabled sync comment option from activity section then comment was going empty when
	 * reply from blog or custom post types.
	 */
	add_action( 'bp_activity_before_save', 'bp_blogs_sync_activity_edit_to_post_comment', 20 );
}
add_action( 'transition_post_status', 'bp_activity_catch_transition_post_type_status', 10, 3 );

/**
 * When a post type comment status transition occurs, update the relevant activity's status.
 *
 * @since BuddyPress 2.5.0
 *
 * @param string     $new_status New comment status.
 * @param string     $old_status Previous comment status.
 * @param WP_Comment $comment Comment data.
 */
function bp_activity_transition_post_type_comment_status( $new_status, $old_status, $comment ) {
	$post_type = get_post_type( $comment->comment_post_ID );
	if ( ! $post_type ) {
		return;
	}

	// Get the post type tracking args.
	$activity_post_object = bp_activity_get_post_type_tracking_args( $post_type );

	// Bail if the activity type does not exist
	if ( empty( $activity_post_object->comments_tracking->action_id ) ) {
		return false;

		// Set the $activity_comment_object
	} else {
		$activity_comment_object = $activity_post_object->comments_tracking;
	}

	// Init an empty activity ID
	$activity_id = 0;

	/**
	 * Activity currently doesn't have any concept of a trash, or an unapproved/approved state.
	 *
	 * If a blog comment transitions to a "delete" or "hold" status, delete the activity item.
	 * If a blog comment transitions to trashed, or spammed, mark the activity as spam.
	 * If a blog comment transitions to approved (and the activity exists), mark the activity as ham.
	 * If a blog comment transitions to unapproved (and the activity exists), mark the activity as spam.
	 * Otherwise, record the comment into the activity feed.
	 */

	// This clause handles delete/hold.
	if ( in_array( $new_status, array( 'delete', 'hold' ) ) ) {
		return bp_activity_post_type_remove_comment( $comment->comment_ID, $activity_post_object );

		// These clauses handle trash, spam, and un-spams.
	} elseif ( in_array( $new_status, array( 'trash', 'spam', 'unapproved' ) ) ) {
		$action = 'spam_activity';
	} elseif ( 'approved' == $new_status ) {
		$action = 'ham_activity';
	}

	// Get the activity
	if ( bp_disable_blogforum_comments() ) {
		$activity_id = bp_activity_get_activity_id(
			array(
				'component'         => $activity_comment_object->component_id,
				'item_id'           => get_current_blog_id(),
				'secondary_item_id' => $comment->comment_ID,
				'type'              => $activity_comment_object->action_id,
			)
		);
	} else {
		$activity_id = get_comment_meta( $comment->comment_ID, 'bp_activity_comment_id', true );
	}

	/**
	 * Leave a chance to plugins to manage activity comments differently.
	 *
	 * @since BuddyPress  2.5.0
	 *
	 * @param bool        $value       True to override BuddyPress management.
	 * @param string      $post_type   The post type name.
	 * @param int         $activity_id The post type activity (0 if not found).
	 * @param string      $new_status  The new status of the post type comment.
	 * @param string      $old_status  The old status of the post type comment.
	 * @param WP_Comment  $comment Comment data.
	 */
	if ( true === apply_filters( 'bp_activity_pre_transition_post_type_comment_status', false, $post_type, $activity_id, $new_status, $old_status, $comment ) ) {
		return false;
	}

	// Check activity item exists
	if ( empty( $activity_id ) ) {
		// If no activity exists, but the comment has been approved, record it into the activity table.
		if ( 'approved' == $new_status ) {
			return bp_activity_post_type_comment( $comment->comment_ID, true, $activity_post_object );
		}

		return;
	}

	// Create an activity object
	$activity = new BP_Activity_Activity( $activity_id );
	if ( empty( $activity->component ) ) {
		return;
	}

	// Spam/ham the activity if it's not already in that state
	if ( 'spam_activity' === $action && ! $activity->is_spam ) {
		bp_activity_mark_as_spam( $activity );
	} elseif ( 'ham_activity' == $action ) {
		bp_activity_mark_as_ham( $activity );
	}

	// Add "new_post_type_comment" to the whitelisted activity types, so that the activity's Akismet history is generated
	$post_type_comment_action = $activity_comment_object->action_id;
	$comment_akismet_history  = function ( $activity_types ) use ( $post_type_comment_action ) {
		$activity_types[] = $post_type_comment_action;

		return $activity_types;
	};
	add_filter( 'bp_akismet_get_activity_types', $comment_akismet_history );

	// Make sure the activity change won't edit the comment if sync is on
	remove_action( 'bp_activity_before_save', 'bp_blogs_sync_activity_edit_to_post_comment', 20 );

	// Save the updated activity
	$activity->save();

	// Restore the action
	add_action( 'bp_activity_before_save', 'bp_blogs_sync_activity_edit_to_post_comment', 20 );

	// Remove the "new_blog_comment" activity type whitelist so we don't break anything
	remove_filter( 'bp_akismet_get_activity_types', $comment_akismet_history );
}
add_action( 'transition_comment_status', 'bp_activity_transition_post_type_comment_status', 10, 3 );

/**
 * Start following a user's activity.
 *
 * @since BuddyBoss 1.0.0
 *
 * @param array $args {
 *     Array of arguments.
 *     @type int $leader_id The user ID of the person we want to stop following.
 *     @type int $follower_id The user ID initiating the unfollow request.
 * }
 * @return bool
 */
function bp_start_following( $args = '' ) {

	$r = bp_parse_args(
		$args,
		array(
			'leader_id'   => bp_displayed_user_id(),
			'follower_id' => bp_loggedin_user_id(),
		)
	);

	$follow = new BP_Activity_Follow( $r['leader_id'], $r['follower_id'] );

	// existing follow already exists
	if ( ! empty( $follow->id ) ) {
		return false;
	}

	if ( ! $follow->save() ) {
		return false;
	}

	do_action_ref_array( 'bp_start_following', array( &$follow ) );

	return true;
}

/**
 * Stop following a user's activity.
 *
 * @since BuddyBoss 1.0.0
 *
 * @param array $args {
 *     Array of arguments.
 *     @type int $leader_id The user ID of the person we want to follow.
 *     @type int $follower_id The user ID initiating the follow request.
 * }
 * @return bool
 */
function bp_stop_following( $args = '' ) {

	$r = bp_parse_args(
		$args,
		array(
			'leader_id'   => bp_displayed_user_id(),
			'follower_id' => bp_loggedin_user_id(),
		)
	);

	$follow = new BP_Activity_Follow( $r['leader_id'], $r['follower_id'] );

	if ( empty( $follow->id ) || ! $follow->delete() ) {
		return false;
	}

	/**
	 * @todo add title/description
	 *
	 * @since BuddyBoss 1.0.0
	 */
	do_action_ref_array( 'bp_stop_following', array( &$follow ) );

	return true;
}

/**
 * Check if a user is already following another user.
 *
 * @since BuddyBoss 1.0.0
 *
 * @param array $args {
 *     Array of arguments.
 *     @type int $leader_id The user ID of the person we want to check.
 *     @type int $follower_id The user ID initiating the follow request.
 * }
 * @return bool
 */
function bp_is_following( $args = '' ) {

	$r = bp_parse_args(
		$args,
		array(
			'leader_id'   => bp_displayed_user_id(),
			'follower_id' => bp_loggedin_user_id(),
		)
	);

	$follow = new BP_Activity_Follow( $r['leader_id'], $r['follower_id'] );

	return apply_filters( 'bp_is_following', (int) $follow->id, $follow );
}

/**
 * Fetch the user IDs of all the followers of a particular user.
 *
 * @since BuddyBoss 1.0.0
 *
 * @param array $args {
 *     Array of arguments.
 *     @type int $user_id The user ID to get followers for.
 * }
 * @return array
 */
function bp_get_followers( $args = '' ) {

	$r = bp_parse_args(
		$args,
		array(
			'user_id'  => bp_displayed_user_id(),
			'page'     => false,
			'per_page' => false,
		)
	);

	return apply_filters( 'bp_get_followers', BP_Activity_Follow::get_followers( $r['user_id'], $r ) );
}

/**
 * Fetch the user IDs of all the users a particular user is following.
 *
 * @since BuddyBoss 1.0.0
 *
 * @param array $args {
 *     Array of arguments.
 *     @type int $user_id The user ID to fetch following user IDs for.
 * }
 * @return array
 */
function bp_get_following( $args = '' ) {

	$r = bp_parse_args(
		$args,
		array(
			'user_id'  => bp_displayed_user_id(),
			'page'     => false,
			'per_page' => false,
		)
	);

	return apply_filters( 'bp_get_following', BP_Activity_Follow::get_following( $r['user_id'], $r ) );
}

/**
 * Get the total followers and total following counts for a user.
 *
 * @since BuddyBoss 1.0.0
 *
 * @param array $args {
 *     Array of arguments.
 *     @type int $user_id The user ID to grab follow counts for.
 * }
 * @return array [ followers => int, following => int ]
 */
function bp_total_follow_counts( $args = '' ) {
	global $bp;

	$r = bp_parse_args(
		$args,
		array(
			'user_id' => bp_loggedin_user_id(),
		)
	);

	$count = false;

	/* try to get locally-cached values first */

	// logged-in user
	if ( $r['user_id'] == bp_loggedin_user_id() && is_user_logged_in() ) {

		if ( ! empty( $bp->loggedin_user->total_follow_counts ) ) {
			$count = $bp->loggedin_user->total_follow_counts;
		}

		// displayed user
	} elseif ( $r['user_id'] == bp_displayed_user_id() && bp_is_user() ) {

		if ( ! empty( $bp->displayed_user->total_follow_counts ) ) {
			$count = $bp->displayed_user->total_follow_counts;
		}
	}

	// no cached value, so query for it
	if ( $count === false ) {
		$count = BP_Activity_Follow::get_counts( $r['user_id'] );
	}

	return apply_filters( 'bp_total_follow_counts', $count, $r['user_id'] );
}

/**
 * Removes follow relationships for all users from a user who is deleted or spammed
 *
 * @since BuddyBoss 1.0.0
 *
 * @uses BP_Activity_Follow::delete_all_for_user() Deletes user ID from all following / follower records
 */
function bp_remove_follow_data( $user_id ) {
	/**
	 * Actions to perform before follow data is removed for user
	 *
	 * @since BuddyBoss 1.0.0
	 */
	do_action( 'bp_before_remove_follow_data', $user_id );

	$return = BP_Activity_Follow::delete_all_for_user( $user_id );

	/**
	 * Actions to perform after follow data is removed for user
	 *
	 * @since BuddyBoss 1.0.0
	 * @param int $user_id User id
	 * @param array|bool $return Array of ids deleted or false otherwise
	 */
	do_action( 'bp_remove_follow_data', $user_id, $return );
}
add_action( 'wpmu_delete_user', 'bp_remove_follow_data' );
add_action( 'delete_user', 'bp_remove_follow_data' );
add_action( 'make_spam_user', 'bp_remove_follow_data' );

/**
 * Update the custom post type activity feed after the attachment attached to that particular custom post type.
 *
 * @since BuddyBoss 1.0.0
 *
 * @param $post_id
 * @param $post
 * @param $update
 */
function bp_update_activity_feed_of_custom_post_type( $post_id, $post, $update ) {

	// check is WP_Post if not then return
	if ( ! is_a( $post, 'WP_Post' ) ) {
		return;
	}

	if ( 'post' === $post->post_type ) {
		return;
	}

	$enabled = bp_is_post_type_feed_enable( $post->post_type );

	// If enabled update the activity.
	if ( $enabled ) {

		// Get the post type tracking args.
		$activity_post_object = bp_activity_get_post_type_tracking_args( $post->post_type );

		if ( empty( $activity_post_object->action_id ) ) {
			return;
		}

		// Get the existing activity id based on the post.
		$activity_id = bp_activity_get_activity_id(
			array(
				'component'         => $activity_post_object->component_id,
				'item_id'           => get_current_blog_id(),
				'secondary_item_id' => $post->ID,
				'type'              => $activity_post_object->action_id,
			)
		);

		// Activity ID doesn't exist, so stop!
		if ( empty( $activity_id ) ) {
			return;
		}

		// Delete the activity if the post was updated with a password.
		if ( ! empty( $post->post_password ) ) {
			bp_activity_delete( array( 'id' => $activity_id ) );
		}

		// Update the activity entry.
		$activity = new BP_Activity_Activity( $activity_id );

		// get the excerpt first of post.
		$excerpt = get_the_excerpt( $post->ID );

		// if excerpt found then take content as a excerpt otherwise take the content as a post content.
		$content = ( $excerpt ) ?: $post->post_content;

		// If content not empty.
		if ( ! empty( $content ) ) {

			$activity_summary = bp_activity_create_summary( $content, (array) $activity );

			$src = wp_get_attachment_image_src( get_post_thumbnail_id( $post->ID ), 'full', false );

			if ( isset( $src[0] ) ) {
				$activity_summary .= sprintf( '<br/><img src="%s">', esc_url( $src[0] ) );
			} elseif ( isset( $_POST ) && isset( $_POST['_featured_image_id'] ) && ! empty( $_POST['_featured_image_id'] ) ) {
				$activity_summary .= sprintf( '<br/><img src="%s">', esc_url( wp_get_attachment_url( $_POST['_featured_image_id'] ) ) );
			}

			// Backward compatibility filter for the blogs component.
			if ( 'blogs' == $activity_post_object->component_id ) {
				$activity->content = apply_filters(
					'bp_update_activity_feed_of_custom_post_content',
					$activity_summary,
					$content,
					(array) $activity,
					$post->post_type
				);
			} else {
				$activity->content = $activity_summary;
			}
		} else {

			$src              = wp_get_attachment_image_src( get_post_thumbnail_id( $post->ID ), 'full', false );
			$activity_summary = '';
			if ( isset( $src[0] ) ) {
				$activity_summary = sprintf( ' <img src="%s">', esc_url( $src[0] ) );
			} elseif ( isset( $_POST ) && isset( $_POST['_featured_image_id'] ) && ! empty( $_POST['_featured_image_id'] ) ) {
				$activity_summary .= sprintf( '<img src="%s">', esc_url( wp_get_attachment_url( $_POST['_featured_image_id'] ) ) );
			}

			// Backward compatibility filter for the blogs component.
			if ( 'blogs' == $activity_post_object->component_id ) {
				$activity->content = apply_filters(
					'bp_update_activity_feed_of_custom_post_content',
					$activity_summary,
					$post->post_content,
					(array) $activity,
					$post->post_type
				);
			} else {
				$activity->content = $activity_summary;
			}
		}

		// Save the updated activity.
		$activity->save();

	}

}
// add_action( 'save_post', 'bp_update_activity_feed_of_custom_post_type', 88, 3 );


/**
 * Update the post activity feed after the attachment attached to that particular post.
 *
 * @since BuddyBoss 1.0.0
 *
 * @param $post
 * @param $request
 * @param $action
 */
function bp_update_activity_feed_of_post( $post, $request, $action ) {

	// check is WP_Post if not then return
	if ( ! is_a( $post, 'WP_Post' ) ) {
		return;
	}

	$enabled = bp_is_post_type_feed_enable( $post->post_type );

	// If enabled update the activity.
	if ( $enabled ) {

		// Get the post type tracking args.
		$activity_post_object = bp_activity_get_post_type_tracking_args( $post->post_type );

		if ( empty( $activity_post_object->action_id ) ) {
			return;
		}

		// Get the existing activity id based on the post.
		$activity_id = bp_activity_get_activity_id(
			array(
				'component'         => $activity_post_object->component_id,
				'item_id'           => get_current_blog_id(),
				'secondary_item_id' => $post->ID,
				'type'              => $activity_post_object->action_id,
			)
		);

		// Activity ID doesn't exist, so stop!
		if ( empty( $activity_id ) ) {
			return;
		}

		// Delete the activity if the post was updated with a password.
		if ( ! empty( $post->post_password ) ) {
			bp_activity_delete( array( 'id' => $activity_id ) );
		}

		// Update the activity entry.
		$activity = new BP_Activity_Activity( $activity_id );

		// get the excerpt first of post.
		$excerpt = get_the_excerpt( $post->ID );

		// if excerpt found then take content as a excerpt otherwise take the content as a post content.
		$content = ( $excerpt ) ?: $post->post_content;

		// If content not empty.
		if ( ! empty( $content ) ) {
			$activity_summary = bp_activity_create_summary( $content, (array) $activity );

			// Backward compatibility filter for the blogs component.
			if ( 'blogs' == $activity_post_object->component_id ) {
				$activity->content = apply_filters(
					'bp_update_activity_feed_of_custom_post_content',
					$activity_summary,
					$content,
					(array) $activity,
					$post->post_type
				);
			} else {
				$activity->content = $activity_summary;
			}
		} else {

			$src              = wp_get_attachment_image_src( get_post_thumbnail_id( $post->ID ), 'full', false );
			$activity_summary = '';
			if ( isset( $src[0] ) ) {
				$activity_summary = sprintf( ' <img src="%s">', esc_url( $src[0] ) );
			}

			// Backward compatibility filter for the blogs component.
			if ( 'blogs' == $activity_post_object->component_id ) {
				$activity->content = apply_filters(
					'bp_update_activity_feed_of_custom_post_content',
					$activity_summary,
					$post->post_content,
					(array) $activity,
					$post->post_type
				);
			} else {
				$activity->content = $activity_summary;
			}
		}

		// Save the updated activity.
		$activity->save();

	}

}
// add_action( 'rest_after_insert_post', 'bp_update_activity_feed_of_post', 99, 3 );

/**
 * AJAX endpoint for link preview URL parser.
 *
 * @since BuddyBoss 1.0.0
 */
function bp_activity_action_parse_url() {
	// Get URL.
	$url = $_POST['url'];

	// Check if URL is validated.
	if ( ! filter_var( $url, FILTER_VALIDATE_URL ) ) {
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

add_action( 'wp_ajax_bp_activity_parse_url', 'bp_activity_action_parse_url' );

/**
 * Function to add the content on top of activity listing
 *
 * @since BuddyBoss 1.2.5
 */
function bp_activity_directory_page_content() {

	$page_ids = bp_core_get_directory_page_ids();

	if ( ! empty( $page_ids['activity'] ) ) {
		$activity_page_content = get_post_field( 'post_content', $page_ids['activity'] );
		echo apply_filters( 'the_content', $activity_page_content );
	}
}

add_action( 'bp_before_directory_activity', 'bp_activity_directory_page_content' );


/**
 * Get default scope for the activity
 *
 * @since BuddyBoss 1.4.3
 *
 * @param string $scope Default scope.
 *
 * @return string
 */
function bp_activity_default_scope( $scope = 'all' ) {
	$new_scope = array();

	if ( bp_loggedin_user_id() && ( 'all' === $scope || empty( $scope ) ) ) {

		$new_scope[] = 'public';

		if ( bp_is_activity_directory() || bp_is_single_activity() ) {
			$new_scope[] = 'just-me';

			if ( bp_is_activity_directory() ) {
				$new_scope[] = 'public';
			}

			if ( bp_activity_do_mentions() ) {
				$new_scope[] = 'mentions';
			}

			if ( bp_is_active( 'friends' ) ) {
				$new_scope[] = 'friends';
			}

			if ( bp_is_active( 'groups' ) ) {
				$new_scope[] = 'groups';
			}

			if ( bp_is_activity_follow_active() ) {
				$new_scope[] = 'following';
			}

			if ( bp_is_single_activity() && bp_is_active( 'media' ) ) {
				$new_scope[] = 'media';
				$new_scope[] = 'document';
			}
		} elseif ( bp_is_user_activity() ) {
			if ( empty( bp_current_action() ) ) {
				$new_scope[] = 'just-me';
			} else {
				$new_scope[] = bp_current_action();
			}
		} elseif ( bp_is_active( 'group' ) && bp_is_group_activity() ) {
			$new_scope[] = 'groups';
		}
	} elseif ( ! bp_loggedin_user_id() && ( 'all' === $scope || empty( $scope ) ) ) {
		$new_scope[] = 'public';
	}

	$new_scope = array_unique( $new_scope );

	if ( empty( $new_scope ) ) {
		$new_scope = (array) $scope;
	}

	if ( bp_loggedin_user_id() && bp_is_activity_directory() && bp_is_relevant_feed_enabled() ) {
		$key = array_search( 'public', $new_scope, true );
		if ( is_array( $new_scope ) && false !== $key ) {
			unset( $new_scope[ $key ] );
			if ( bp_is_active( 'forums' ) ) {
				$new_scope[] = 'forums';
			}
		}
	}

	/**
	 * Filter to update default scope.
	 *
	 * @since BuddyBoss 1.4.3
	 */
	$new_scope = apply_filters( 'bp_activity_default_scope', $new_scope );

	return implode( ',', $new_scope );
}

/**
 * Get the Activity edit data.
 *
 * @since BuddyBoss 1.5.1
 *
 * @param int $activity_id Activity ID.
 *
 * @return array|bool The Activity edit data or false otherwise.
 */
function bp_activity_get_edit_data( $activity_id = 0 ) {
	global $activities_template;

	// check activity empty or not.
	if ( empty( $activity_id ) && empty( $activities_template ) ) {
		return false;
	}
	// get activity.
	if ( ! empty( $activities_template->activity ) ) {
		$activity = $activities_template->activity;
	} else {
		$activity = new BP_Activity_Activity( $activity_id );
	}

	// check activity exists.
	if ( empty( $activity->id ) ) {
		return false;
	}

	$can_edit_privacy        = true;
	$album_id                = 0;
	$folder_id               = 0;
	$group_id                = bp_is_active( 'groups' ) && buddypress()->groups->id === $activity->component ? $activity->item_id : 0;
	$group_name              = '';
	$album_activity_id       = bp_activity_get_meta( $activity_id, 'bp_media_album_activity', true );
	$album_video_activity_id = bp_activity_get_meta( $activity_id, 'bp_video_album_activity', true );
	$link_image_index_save   = '';

	if ( ! empty( $album_activity_id ) || ! empty( $album_video_activity_id ) ) {
		$album_id = $album_activity_id;
	}

	$folder_activity_id = bp_activity_get_meta( $activity_id, 'bp_document_folder_activity', true );
	if ( ! empty( $folder_activity_id ) ) {
		$folder_id = $folder_activity_id;
	}

	// if album or folder activity then set privacy edit to always false.
	if ( $album_id || $folder_id ) {
		$can_edit_privacy = false;
	}

	// if group activity then set privacy edit to always false.
	if ( 0 < (int) $group_id ) {
		$can_edit_privacy = false;
		$group            = groups_get_group( $group_id );
		$group_name       = bp_get_group_name( $group );
	}
	$group_avatar = bp_is_active( 'groups' ) ? bp_get_group_avatar_url( groups_get_group( $group_id ) ) : '';  // Add group avatar in get activity data object.

	// Link preview data.
	$link_preview_data = bp_activity_get_meta( $activity_id, '_link_preview_data', true );
	if ( isset( $link_preview_data['link_image_index_save'] ) ) {
		$link_image_index_save = $link_preview_data['link_image_index_save'];
	}
	/**
	 * Filter here to edit the activity edit data.
	 *
	 * @since BuddyBoss 1.5.1
	 *
	 * @param string $activity_data The Activity edit data.
	 */
	return apply_filters(
		'bp_activity_get_edit_data',
		array(
			'id'                    => $activity_id,
			'can_edit_privacy'      => $can_edit_privacy,
			'album_id'              => $album_id,
			'group_id'              => $group_id,
			'group_name'            => $group_name,
			'folder_id'             => $folder_id,
			'content'               => stripslashes( $activity->content ),
			'item_id'               => $activity->item_id,
			'object'                => $activity->component,
			'privacy'               => $activity->privacy,
			'group_avatar'          => $group_avatar,
			'link_image_index_save' => $link_image_index_save,
		)
	);
}

/**
 * Return the link to report activity
 *
 * @since BuddyBoss 1.5.6
 *
 * @param array $args Arguments
 *
 * @return string Link for a report a activity
 */
function bp_activity_get_report_link( $args = array() ) {

	if ( ! bp_is_active( 'moderation' ) || ! is_user_logged_in() ) {
		return false;
	}

	$args = bp_parse_args(
		$args,
		array(
			'id'                => 'activity_report',
			'component'         => 'moderation',
			'position'          => 10,
			'must_be_logged_in' => true,
			'button_attr'       => array(
				'data-bp-content-id'   => bp_get_activity_id(),
				'data-bp-content-type' => BP_Moderation_Activity::$moderation_type,
			),
		)
	);

	/**
	 * Filter Activity report link
	 *
	 * @since BuddyBoss 1.5.6
	 */
	return apply_filters( 'bp_activity_get_report_link', bp_moderation_get_report_button( $args, false ), $args );
}

/**
 * Return the link to report activity activity
 *
 * @since BuddyBoss 1.5.6
 *
 * @param array $args Arguments
 *
 * @return string Link for a report a activity comment
 */
function bp_activity_comment_get_report_link( $args = array() ) {

	if ( ! bp_is_active( 'moderation' ) || ! is_user_logged_in() ) {
		return false;
	}

	$args = bp_parse_args(
		$args,
		array(
			'id'                => 'activity_comment_report',
			'component'         => 'moderation',
			'position'          => 10,
			'must_be_logged_in' => true,
			'button_attr'       => array(
				'data-bp-content-id'   => bp_get_activity_comment_id(),
				'data-bp-content-type' => BP_Moderation_Activity_Comment::$moderation_type,
			),
		)
	);

	/**
	 * Filter Activity comment report link
	 *
	 * @since BuddyBoss 1.5.6
	 */
	return apply_filters( 'bp_activity_comment_get_report_link', bp_moderation_get_report_button( $args, false ), $args );
}

/**
 * This function will give the activity hierarchy
 *
 * @param int $activity_id Activity ID.
 *
 * @return array
 *
 * @since BuddyBoss 1.7.0
 */
function bb_get_activity_hierarchy( $activity_id ) {

	global $wpdb, $bp;

	$activity_table = $bp->activity->table_name;

	$cache_key = 'bb_activity_hierarchy_' . $activity_id;
	$data      = wp_cache_get( $cache_key, 'bp_activity' );

	if ( false === $data ) {
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$activity_query = $wpdb->prepare( "SELECT c.id FROM ( SELECT @r AS _id, (SELECT @r := secondary_item_id FROM {$activity_table} WHERE id = _id) AS secondary_item_id, @l := @l + 1 AS level FROM (SELECT @r := %d, @l := 0) vars, {$activity_table} m WHERE @r <> 0) d JOIN {$activity_table} c ON d._id = c.id ORDER BY d.level ASC", $activity_id );

		$data = $wpdb->get_results( $activity_query, ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		wp_cache_set( $cache_key, $data, 'bp_activity' );
	}

	return array_filter( $data );
}

/**
 * Is it blog post activity.
 *
 * @param object $activity Blog post activity data.
 *
 * @since BuddyBoss 1.7.2
 *
 * @return bool
 */
function bb_activity_blog_post_acivity( $activity ) {
	if ( ( 'blogs' === $activity->component ) && ! empty( $activity->secondary_item_id ) && 'new_blog_' . get_post_type( $activity->secondary_item_id ) === $activity->type ) {
		$blog_post = get_post( $activity->secondary_item_id );
		// If we converted $content to an object earlier, flip it back to a string.
		if ( is_a( $blog_post, 'WP_Post' ) ) {
			return true;
		}
	}

	return false;
}

/**
 * This function will give the topic id from topic activity.
 * - Used in Rest API
 *
 * @param object $activity Topic activity data.
 *
 * @since BuddyBoss 1.7.2
 *
 * @return int
 */
function bb_activity_topic_id( $activity ) {
	if ( empty( $activity ) ) {
		return false;
	}

	// When the activity type does not match with the topic.
	if ( 'bbp_topic_create' !== $activity->type ) {
		return false;
	}

	$topic_id = false;

	// Set topic id when activity component is not groups.
	if ( 'bbpress' === $activity->component ) {
		// Set topic id when activity type topic.
		$topic_id = $activity->item_id;
	}

	// Set topic id when activity component is groups.
	if ( 'groups' === $activity->component ) {
		// Set topic id when activity type topic.
		$topic_id = $activity->secondary_item_id;
	}

	return $topic_id;
}

/**
 * This function will give the reply topic id from reply activity.
 * - Used in Rest API
 *
 * @param object $activity Reply activity data.
 *
 * @since BuddyBoss 1.7.2
 *
 * @return int
 */
function bb_activity_reply_topic_id( $activity ) {
	if ( empty( $activity ) ) {
		return false;
	}

	// When the activity type does not match with the topic.
	if ( 'bbp_reply_create' !== $activity->type ) {
		return false;
	}

	$topic_id = false;

	// Set topic id when activity component is not groups.
	if ( 'bbpress' === $activity->component ) {
		// Set topic id when activity type reply.
		$topic_id = bbp_get_reply_topic_id( $activity->item_id );
	}

	// Set topic id when activity component is groups.
	if ( 'groups' === $activity->component ) {
		// Set topic id when activity type reply.
		$topic_id = bbp_get_reply_topic_id( $activity->secondary_item_id );
	}

	return $topic_id;
}

/**
 * Is it topic comment activity.
 * - Used in Rest API
 *
 * @param int $activity_id Activity id.
 *
 * @since BuddyBoss 1.7.2
 *
 * @return bool
 */
function bb_acivity_is_topic_comment( $activity_id ) {
	$item_activity = new BP_Activity_Activity( $activity_id );

	if ( empty( $item_activity ) ) {
		return false;
	}

	// Get the current action name.
	$action_name = $item_activity->type;

	// Setup the array of possibly disabled actions.
	$disabled_actions = array(
		'bbp_topic_create',
		'bbp_reply_create',
	);

	// Comment is disabled for discussion and reply discussion.
	if ( in_array( $action_name, $disabled_actions, true ) ) {
		return true;
	}

	return false;
}

/**
 * Function will use for how many groups to display at a time in the activity post form.
 *
 * @since BuddyBoss 1.8.6
 *
 * @return int
 */
function bb_activity_post_form_groups_per_page() {
	return apply_filters( 'bb_activity_post_form_groups_per_page', 10 );
}

/**
 * Follow button.
 *
 * @since BuddyBoss 2.1.3
 *
 * @param array $button HTML markup for follow button.
 *
 * @return array Array of button element.
 */
function bb_bp_get_add_follow_button( $button ) {

	if ( 'follow-button following' === $button['wrapper_class'] ) {
		$button['link_class'] .= ' small';
	} else {
		$button['link_class'] .= ' small outline';
	}

	$button['parent_element'] = 'div';
	$button['button_element'] = 'button';

	return $button;
}

/**
 * Function to send email and notification to followers when new activity post created.
 *
 * @since BuddyBoss 2.2.3
 *
 * @param array $args Array of arguments.
 */
function bb_activity_following_post_notification( $args ) {

	$r = bp_parse_args(
		$args,
		array(
			'activity'  => '',
			'usernames' => array(),
			'item_id'   => '',
			'user_ids'  => array(),
		)
	);

	if ( empty( $r['user_ids'] ) || empty( $r['activity'] ) ) {
		return;
	}

	$activity_id      = $r['activity']->id;
	$activity_user_id = ! empty( $r['item_id'] ) ? $r['item_id'] : $r['activity']->user_id;
	$poster_name      = bp_core_get_user_displayname( $activity_user_id );
	$activity_link    = bp_activity_get_permalink( $activity_id );
	$media_ids        = bp_activity_get_meta( $activity_id, 'bp_media_ids', true );
	$document_ids     = bp_activity_get_meta( $activity_id, 'bp_document_ids', true );
	$video_ids        = bp_activity_get_meta( $activity_id, 'bp_video_ids', true );

	if ( $media_ids ) {
		$media_ids = array_filter( ! is_array( $media_ids ) ? explode( ',', $media_ids ) : $media_ids );
		if ( count( $media_ids ) > 1 ) {
			$text = __( 'some photos', 'buddyboss' );
		} else {
			$text = __( 'a photo', 'buddyboss' );
		}
	} elseif ( $document_ids ) {
		$document_ids = array_filter( ! is_array( $document_ids ) ? explode( ',', $document_ids ) : $document_ids );
		if ( count( $document_ids ) > 1 ) {
			$text = __( 'some documents', 'buddyboss' );
		} else {
			$text = __( 'a document', 'buddyboss' );
		}
	} elseif ( $video_ids ) {
		$video_ids = array_filter( ! is_array( $video_ids ) ? explode( ',', $video_ids ) : $video_ids );
		if ( count( $video_ids ) > 1 ) {
			$text = __( 'some videos', 'buddyboss' );
		} else {
			$text = __( 'a video', 'buddyboss' );
		}
	} else {
		$text = __( 'an update', 'buddyboss' );
	}

	$args = array(
		'tokens' => array(
			'activity'      => $r['activity'],
			'activity.type' => $text,
			'poster.name'   => $poster_name,
			'activity.url'  => esc_url( $activity_link ),
		),
	);

	foreach ( $r['user_ids'] as $key => $user_id ) {
		$user_id           = (int) $user_id;
		$send_mail         = true;
		$send_notification = true;

		if ( ! empty( $r['usernames'] ) && isset( $r['usernames'][ $user_id ] ) ) {
			if ( true === bb_is_notification_enabled( $user_id, 'bb_new_mention' ) ) {
				$send_mail = false;
			}
		}

		if (
			'friends' === $r['activity']->privacy &&
			bp_is_active( 'friends' ) &&
			(int) $user_id !== (int) $activity_user_id &&
			! friends_check_friendship( $user_id, $activity_user_id )
		) {
			$send_notification = false;
			$send_mail         = false;
		}

		// It will check some condition to following notification disable, user blocked , mention notification enable
		// and mention available in post for follower user.
		if ( false === bb_is_notification_enabled( $user_id, 'bb_activity_following_post' ) ) {
			$send_mail = false;
		}

		if ( true === (bool) apply_filters( 'bb_is_recipient_moderated', false, $user_id, $activity_user_id ) ) {
			$send_notification = false;
			$send_mail         = false;
		}

		if ( true === $send_mail ) {
			$unsubscribe_args = array(
				'user_id'           => $user_id,
				'notification_type' => 'new-activity-following',
			);

			$args['tokens']['unsubscribe']      = esc_url( bp_email_get_unsubscribe_link( $unsubscribe_args ) );
			$args['tokens']['receiver-user.id'] = $user_id;

			// Send notification email.
			bp_send_email( 'new-activity-following', $user_id, $args );
		}

		if ( true === $send_notification && bp_is_active( 'notifications' ) ) {
			add_action( 'bp_notification_after_save', 'bb_notification_after_save_meta', 5, 1 );
			bp_notifications_add_notification(
				array(
					'user_id'           => $user_id,
					'item_id'           => $activity_id,
					'secondary_item_id' => $activity_user_id,
					'component_name'    => buddypress()->activity->id,
					'component_action'  => 'bb_activity_following_post',
					'date_notified'     => bp_core_current_time(),
					'is_new'            => 1,
				)
			);
			remove_action( 'bp_notification_after_save', 'bb_notification_after_save_meta', 5, 1 );
		}
	}
}

/**
 * Check whether activity comment is group comment or not.
 *
 * @since BuddyBoss 2.3.50
 *
 * @param object|int $comment Activity comment ID or object.
 *
 * @return bool
 */
function bb_is_group_activity_comment( $comment = 0 ) {

	$comment_id = 0;
	if ( empty( $comment ) ) {
		global $activities_template;
		$comment_id = isset( $activities_template->activity->current_comment->id ) ? $activities_template->activity->current_comment->id : 0;
	} elseif ( is_array( $comment ) ) {
		$comment_id = (int) $comment['id'];
	} elseif ( is_object( $comment ) ) {
		$comment_id = (int) $comment->id;
	} elseif ( is_int( $comment ) ) {
		$comment_id = (int) $comment;
	}

	if ( empty( $comment_id ) ) {
		return false;
	}

	$comment = new BP_Activity_Activity( $comment_id );

	if ( ! empty( $comment->item_id ) && ! empty( $comment->user_id ) ) {
		$main_activity = new BP_Activity_Activity( $comment->item_id );

		if (
			! empty( $main_activity->component ) &&
			'groups' === $main_activity->component
		) {
			return true;
		}
	}

	return false;
}

/**
 * Function to create a paginated backgroud job for activity following notifications.
 *
 * @since BuddyBoss 2.3.70
 *
 * @param array $args  Array of arguments.
 * @param array $paged Current page number for pagination.
 */
function bb_activity_create_following_post_notification( $args, $paged = 1 ) {
	if ( empty( $paged ) ) {
		$paged = 1;
	}

	$per_page       = apply_filters( 'bb_following_min_count', 20 );
	$follower_users = bp_get_followers(
		array(
			'user_id'  => $args['item_id'],
			'per_page' => $per_page,
			'page'     => $paged
		)
	);

	if ( empty( $follower_users ) ) {
		return;
	}

	if ( count( $follower_users ) > 0 ) {
		global $bb_background_updater;

		$args['user_ids'] = $follower_users;
		$args['paged']    = $paged;
		$bb_background_updater->data(
			array(
				'type'     => 'email',
				'group'    => 'activity_following_post',
				'data_id'  => $args['item_id'],
				'priority' => 5,
				'callback' => 'bb_activity_following_post_notification',
				'args'     => array( $args ),
			),
		);

		$bb_background_updater->save()->dispatch();
	}

	if ( isset( $args['user_ids'] ) ) {
		unset( $args['user_ids'] );
	}

	if ( isset( $args['paged'] ) ) {
		unset( $args['paged'] );
	}

	// Call recursive to finish update for all records.
	$paged++;
	bb_activity_create_following_post_notification( $args, $paged );
}

/**
 * Returns the list of available BuddyPress activity types.
 *
 * @since BuddyPress 9.0.0
 * @since BuddyBoss 2.3.90
 *
 * @return array An array of activity type labels keyed by type names.
 */
function bp_activity_get_types_list() {
	$actions_object = bp_activity_get_actions();
	$actions_array  = get_object_vars( $actions_object );

	$types = array();
	foreach ( $actions_array as $component => $actions ) {
		$new_types = wp_list_pluck( $actions, 'label', 'key' );

		if ( $types ) {
			// Makes sure activity types are unique.
			$new_types = array_diff_key( $new_types, $types );

			if ( 'friends' === $component ) {
				$new_types = array_diff_key(
					array(
						'friendship_accepted'              => false,
						'friendship_created'               => false,
						'friends_register_activity_action' => false,
					),
					$new_types
				);

				$new_types['friendship_accepted,friendship_created'] = __( 'Friendships', 'buddyboss' );
			}
		}

		$types = array_merge( $types, $new_types );
	}

	/**
	 * Filter here to edit the activity types list.
	 *
	 * @since BuddyPress 9.0.0
	 *
	 * @param array $types An array of activity type labels keyed by type names.
	 */
	return apply_filters( 'bp_activity_get_types_list', $types );
}

/**
 * Activity migration.
 *
 * @since BuddyBoss 2.4.30
 * @since BuddyBoss 2.4.50 Added support for the $raw_db_version and $current_db.
 *
 * @param int $raw_db_version Raw database version.
 * @param int $current_db Current DB version.
 *
 * @return void
 */
function bb_activity_migration( $raw_db_version, $current_db ) {

	/**
	 * Like migration into reaction.
	 *
	 * @since BuddyBoss 2.4.30
	 */
	if ( class_exists( 'BB_Reaction' ) ) {
		bb_load_reaction()->create_table();
		bb_load_reaction()->bb_register_activity_like();

		$is_already_run = get_transient( 'bb_migrate_activity_reaction' );

		// Migration Like to Reaction.
		if (
			! $is_already_run &&
			(
				$raw_db_version < 20601 || // Reaction release version 2.4.30.
				$raw_db_version < 20674 // Last release version 2.4.41.
			) &&
			$current_db >= 20674 // Current DB version 2.4.50.
		) {
			set_transient( 'bb_migrate_activity_reaction', true, HOUR_IN_SECONDS );
			bb_migrate_activity_like_reaction();
		}
	}
}

/**
 * Migrate activity like reaction.
 *
 * @since BuddyBoss 2.4.30
 *
 * @param int $paged Current page for fetch records.
 *
 * @return void
 */
function bb_migrate_activity_like_reaction( $paged = 1 ) {
	global $wpdb, $bp, $bb_background_updater;

	$reaction_id = bb_load_reaction()->bb_reactions_get_like_reaction_id();

	if ( empty( $paged ) ) {
		$paged = 1;
	}

	$per_page = 20;
	$offset   = ( ( $paged - 1 ) * $per_page );

	$results = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT * FROM {$bp->activity->table_name_meta} WHERE meta_key = 'bp_favorite_users' ORDER BY activity_id ASC LIMIT %d offset %d",
			$per_page,
			$offset
		)
	);

	if ( empty( $results ) ) {
		return;
	}

	$bb_background_updater->push_to_queue(
		array(
			'type'     => 'migration',
			'group'    => 'bb_activity_like_reaction_migration',
			'priority' => 4,
			'callback' => 'bb_activity_like_reaction_background_process_migration',
			'args'     => array( $results, $paged, $reaction_id ),
		)
	);
	$bb_background_updater->save()->schedule_event();

	// Delete previous existing migration from background jobs table.
	$table_name = $bb_background_updater::$table_name;
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$wpdb->query(
		$wpdb->prepare(
			"DELETE FROM {$table_name} WHERE `type` = %s AND `group` = %s ORDER BY id ASC limit 500", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			'migration',
			'bb_activity_like_reaction'
		)
	);
}

/**
 * Function to run like reaction within background process.
 *
 * @since BuddyBoss 2.4.30
 *
 * @param array $results     Activity like data.
 * @param int   $paged       Current page for migration.
 * @param int   $reaction_id Reaction ID.
 *
 * @return void
 */
function bb_activity_like_reaction_background_process_migration( $results, $paged, $reaction_id ) {
	global $wpdb, $bb_background_updater;

	$user_reaction_table = bb_load_reaction()::$user_reaction_table;

	if ( empty( $results ) ) {
		return;
	}

	foreach ( $results as $result ) {
		$activity_id = (int) $result->activity_id;
		$meta_value  = maybe_unserialize( $result->meta_value );
		if ( ! empty( $meta_value ) ) {
			$implode_meta_value = implode( ',', wp_parse_id_list( $meta_value ) );
			$data               = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT user_id FROM {$user_reaction_table} WHERE item_id = %d AND reaction_id = %d AND user_id IN ( {$implode_meta_value} )",
					$result->activity_id,
					$reaction_id
				),
				ARRAY_A
			);

			// Extract the user_id values using array_column.
			$user_ids = array_column( $data, 'user_id' );
			if ( ! empty( $user_ids ) ) {
				$meta_value = array_diff( $meta_value, $user_ids );
			}

			if ( ! empty( $meta_value ) ) {
				$min_count = (int) apply_filters( 'bb_update_users_like_reaction', 20 );
				if ( count( $meta_value ) > $min_count ) {
					foreach ( array_chunk( $meta_value, $min_count ) as $chunk ) {
						$bb_background_updater->push_to_queue(
							array(
								'type'     => 'migration',
								'group'    => 'bb_update_users_like_reaction',
								'priority' => 3,
								'callback' => 'bb_update_users_like_reaction',
								'args'     => array( $chunk, $activity_id, $reaction_id ),
							)
						);
						$bb_background_updater->save()->schedule_event();
					}
				} else {
					bb_update_users_like_reaction( $meta_value, $activity_id, $reaction_id );
				}
			}
		}
	}

	// Call recursive to finish update for all records.
	$paged ++;
	bb_migrate_activity_like_reaction( $paged );
}

/**
 * Add user item reaction.
 *
 * @since BuddyBoss 2.4.30
 *
 * @param array $user_ids    Array of user ids.
 * @param int   $activity_id Activity id.
 * @param int   $reaction_id Reaction id.
 *
 * @return void
 */
function bb_update_users_like_reaction( $user_ids, $activity_id, $reaction_id ) {
	foreach ( $user_ids as $user_id ) {
		bb_load_reaction()->bb_add_user_item_reaction(
			array(
				'user_id'     => $user_id,
				'reaction_id' => $reaction_id,
				'item_id'     => $activity_id,
				'item_type'   => 'activity',
			)
		);
	}
}

/**
 * Get the Activity comment edit data.
 *
 * @since BuddyBoss 2.4.40
 *
 * @param int $activity_comment_id Activity comment ID.
 *
 * @return array|bool The Activity comment edit data or false otherwise.
 */
function bb_activity_comment_get_edit_data( $activity_comment_id = 0 ) {
	global $activities_template;

	// check activity comment empty or not.
	if ( empty( $activity_comment_id ) && empty( $activities_template ) ) {
		return false;
	}

	$activity_comment = new stdClass();
	// get activity comment.
	if ( ! empty( $activity_comment_id ) ) {
		$activity_comment = new BP_Activity_Activity( $activity_comment_id );
	} elseif ( ! empty( $activities_template->activity->current_comment ) ) {
		$activity_comment = $activities_template->activity->current_comment;
	}

	// check activity comment exists.
	if ( empty( $activity_comment->id ) ) {
		return false;
	}

	$can_edit_privacy                = true;
	$album_id                        = 0;
	$folder_id                       = 0;
	$album_activity_comment__id      = bp_activity_get_meta( $activity_comment_id, 'bp_media_album_activity', true );
	$album_video_activity_comment_id = bp_activity_get_meta( $activity_comment_id, 'bp_video_album_activity', true );

	if ( ! empty( $album_activity_comment__id ) || ! empty( $album_video_activity_comment_id ) ) {
		$album_id = $album_activity_comment__id;
	}

	$folder_activity_comment_id = bp_activity_get_meta( $activity_comment_id, 'bp_document_folder_activity', true );
	if ( ! empty( $folder_activity_comment_id ) ) {
		$folder_id = $folder_activity_comment_id;
	}

	// if album or folder activity comment, then set privacy edit to always false.
	if ( $album_id || $folder_id ) {
		$can_edit_privacy = false;
	}

	/**
	 * Filter here to edit the activity comment edit data.
	 *
	 * @since BuddyBoss 2.4.40
	 *
	 * @param array $activity_comment_data The Activity comment edit data.
	 */
	return apply_filters(
		'bb_activity_comment_get_edit_data',
		array(
			'id'               => $activity_comment_id,
			'can_edit_privacy' => $can_edit_privacy,
			'album_id'         => $album_id,
			'folder_id'        => $folder_id,
			'content'          => stripslashes( bp_get_activity_comment_content( $activity_comment_id ) ),
			'item_id'          => $activity_comment->item_id,
			'object'           => $activity_comment->component,
			'privacy'          => $activity_comment->privacy,
		)
	);
}
