<?php
/**
 * Activity functions
 *
 * @since BuddyPress 3.0.0
 * @version 3.1.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Register Scripts for the Activity component
 *
 * @since BuddyPress 3.0.0
 *
 * @param array $scripts  The array of scripts to register.
 *
 * @return array The same array with the specific activity scripts.
 */
function bp_nouveau_activity_register_scripts( $scripts = array() ) {
	if ( ! isset( $scripts['bp-nouveau'] ) ) {
		return $scripts;
	}

	return array_merge(
		$scripts,
		array(
			'bp-nouveau-activity'           => array(
				'file'         => 'js/buddypress-activity%s.js',
				'dependencies' => array( 'bp-nouveau', 'wp-util', 'wp-backbone' ),
				'footer'       => true,
			),
			'bp-nouveau-activity-post-form' => array(
				'file'         => 'js/buddypress-activity-post-form%s.js',
				'dependencies' => array( 'bp-nouveau', 'bp-nouveau-activity', 'json2', 'wp-backbone' ),
				'footer'       => true,
			),
		)
	);
}

/**
 * Enqueue the activity scripts
 *
 * @since BuddyPress 3.0.0
 */
function bp_nouveau_activity_enqueue_scripts() {
	if ( ! bp_is_activity_component() && ! bp_is_group_activity() && ! bp_is_media_component() && ! bp_is_video_component() && ! bp_is_document_component() && ! bp_is_media_directory() && ! bp_is_document_directory() && ! bp_is_video_directory() && ! bp_is_group_media() && ! bp_is_group_document() && ! bp_is_group_video() && ! bp_is_group_albums() && ! bp_is_group_folders() && ! bp_is_messages_component() ) { // media popup overlay needs activity scripts.
		return;
	}

	wp_enqueue_script( 'bp-nouveau-activity' );
	wp_enqueue_script( 'bp-medium-editor' );
	wp_enqueue_style( 'bp-medium-editor' );
	wp_enqueue_style( 'bp-medium-editor-beagle' );

	// Enqueue activity form parts and js required for single activity.

	if ( bp_nouveau_current_user_can( 'publish_activity' ) ) {
		wp_enqueue_script( 'bp-nouveau-activity-post-form' );
		bp_get_template_part( 'common/js-templates/activity/form' );
	}
}

/**
 * Localize the strings needed for the Activity Post form UI
 *
 * @since BuddyPress 3.0.0
 *
 * @param array $params Associative array containing the JS Strings needed by scripts.
 *
 * @return array The same array with specific strings for the Activity Post form UI if needed.
 */
function bp_nouveau_activity_localize_scripts( $params = array() ) {
	static $group_query_cache = array();
	if ( ! bp_is_activity_component() && ! bp_is_group_activity() && ! bp_is_media_component() && ! bp_is_document_component() && ! bp_is_media_directory() && ! bp_is_document_directory() && ! bp_is_group_media() && ! bp_is_group_document() && ! bp_is_group_albums() && ! bp_is_group_folders() && ( ! isset( $_REQUEST ) && ! isset( $_REQUEST['bp_search'] ) ) ) {
		// media popup overlay needs activity scripts.
		return $params;
	}

	// Draft activity meta key.
	$draft_activity_meta_key = 'draft_user';

	if ( 0 < bp_displayed_user_id() ) {
		$draft_activity_meta_key = 'draft_user_' . bp_displayed_user_id();
	}

	$activity_params = array(
		'user_id'           => bp_loggedin_user_id(),
		'object'            => 'user',
		'backcompat'        => (bool) has_action( 'bp_activity_post_form_options' ),
		'post_nonce'        => wp_create_nonce( 'post_update', '_wpnonce_post_update' ),
		'post_draft_nonce'  => wp_create_nonce( 'post_draft_activity' ),
		'excluded_hosts'    => array(),
		'user_can_post'     => ( is_user_logged_in() && bb_user_can_create_activity() ),
		'is_activity_edit'  => bp_is_activity_edit() ? (int) bp_current_action() : false,
		'displayed_user_id' => bp_displayed_user_id(),
		'errors'            => array(
			'empty_post_update' => esc_html__( 'Sorry, Your update cannot be empty.', 'buddyboss' ),
			'post_fail'         => esc_html__( 'An error occurred while saving your post.', 'buddyboss' ),
			'media_fail'        => esc_html__( 'To change the media type, remove existing media from your post.', 'buddyboss' ),
		),
	);

	$user_displayname = bp_get_loggedin_user_fullname();

	if ( buddypress()->avatar->show_avatars ) {
		$width  = bp_core_avatar_thumb_width();
		$height = bp_core_avatar_thumb_height();
		$activity_params = array_merge( $activity_params, array(
			'avatar_url'        => bp_get_loggedin_user_avatar( array(
				'width'  => $width,
				'height' => $height,
				'html'   => false,
			) ),
			'avatar_width'      => $width,
			'avatar_height'     => $height,
			'user_display_name' => bp_core_get_user_displayname( bp_loggedin_user_id() ),
			'user_domain'       => bp_loggedin_user_domain(),
			'avatar_alt'        => sprintf(
			/* translators: %s = member name */
				__( 'Profile photo of %s', 'buddyboss' ),
				$user_displayname
			),
		) );
	}

	if ( bp_is_activity_autoload_active() ) {
		$activity_params['autoload'] = true;
	}

	if ( bp_is_activity_link_preview_active() ) {
		$activity_params['link_preview'] = true;
	}

	/**
	 * Filters the included, specific, Action buttons.
	 *
	 * @since BuddyPress 3.0.0
	 *
	 * @param array $value The array containing the button params. Must look like:
	 * array( 'buttonid' => array(
	 *  'id'      => 'buttonid',                            // Id for your action
	 *  'caption' => __( 'Button caption', 'text-domain' ),
	 *  'icon'    => 'dashicons-*',                         // The dashicon to use
	 *  'order'   => 0,
	 *  'handle'  => 'button-script-handle',                // The handle of the registered script to enqueue
	 * );
	 */
	$activity_buttons = apply_filters( 'bp_nouveau_activity_buttons', array() );

	if ( ! empty( $activity_buttons ) ) {
		$activity_params['buttons'] = bp_sort_by_key( $activity_buttons, 'order', 'num' );

		// Enqueue Buttons scripts and styles
		foreach ( $activity_params['buttons'] as $key_button => $buttons ) {
			if ( empty( $buttons['handle'] ) ) {
				continue;
			}

			if ( wp_style_is( $buttons['handle'], 'registered' ) ) {
				wp_enqueue_style( $buttons['handle'] );
			}

			if ( wp_script_is( $buttons['handle'], 'registered' ) ) {
				wp_enqueue_script( $buttons['handle'] );
			}

			unset( $activity_params['buttons'][ $key_button ]['handle'] );
		}
	}

	// Activity Objects
	if ( ! bp_is_single_item() && ! bp_is_user() ) {
		$activity_objects = array(
			'profile' => array(
				'text'                     => __( 'Post in: Profile', 'buddyboss' ),
				'autocomplete_placeholder' => '',
				'priority'                 => 5,
			),
		);

		// the groups component is active & the current user is at least a member of 1 group
		if ( bp_is_active( 'groups' ) && bp_has_groups( array( 'user_id' => bp_loggedin_user_id(), 'max' => 1, 'search_terms' => false ) ) ) {
			$activity_objects['group'] = array(
				'text'                      => esc_html__( 'Post in: Group', 'buddyboss' ),
				'autocomplete_placeholder'  => esc_html__( 'Search groups', 'buddyboss' ),
				'priority'                  => 10,
				'loading_group_placeholder' => esc_html__( 'Loading groups...', 'buddyboss' ),
				'finding_group_placeholder' => esc_html__( 'Finding groups...', 'buddyboss' ),
				'no_groups_found'           => esc_html__( 'No groups found.', 'buddyboss' ),
			);
			$group_args = array(
				'user_id'     => bp_loggedin_user_id(),
				'show_hidden' => true,
				'per_page'    => bb_activity_post_form_groups_per_page(),
				'orderby'     => 'name',
				'order'       => 'ASC',
				'page'        => 1,
			);
			$cache_key  = 'bbp_default_groups_' . md5( maybe_serialize( $group_args ) );
			if ( ! isset( $group_query_cache[ $cache_key ] ) ) {
				add_filter( 'bp_groups_get_join_sql', 'bb_groups_get_join_sql_for_activity', 10, 2 );
				add_filter( 'bp_groups_get_where_conditions', 'bb_groups_get_where_conditions_for_activity', 10, 2 );
				$group_query_cache[ $cache_key ] = groups_get_groups( $group_args );
				remove_filter( 'bp_groups_get_join_sql', 'bb_groups_get_join_sql_for_activity', 10, 2 );
				remove_filter( 'bp_groups_get_where_conditions', 'bb_groups_get_where_conditions_for_activity', 10, 2 );
			}
			$groups = $group_query_cache[ $cache_key ];

			$activity_objects['group_list'] = array();
			if ( isset( $groups['groups'] ) ) {
				$activity_objects['group_list']       = array_map( 'bp_nouveau_prepare_group_for_js', $groups['groups'] );
				$activity_objects['group_count']      = isset( $groups['total'] ) ? $groups['total'] : 0;
				$activity_objects['group_total_page'] = ceil( $groups['total'] / bb_activity_post_form_groups_per_page() );
			}
		}

		/**
		 * Filters the activity objects to apply for localized javascript data.
		 *
		 * @since BuddyPress 3.0.0
		 *
		 * @param array $activity_objects Array of activity objects.
		 */
		$activity_params['objects'] = apply_filters( 'bp_nouveau_activity_objects', $activity_objects );
	}

	$activity_strings = array(
		'whatsnewPlaceholder' => sprintf( __( 'Share what\'s on your mind, %s...', 'buddyboss' ), bp_core_get_user_displayname( bp_loggedin_user_id() ) ),
		'whatsnewLabel'       => esc_html__( 'Post what\'s new', 'buddyboss' ),
		'whatsnewpostinLabel' => esc_html__( 'Post in', 'buddyboss' ),
		'postUpdateButton'    => esc_html__( 'Post', 'buddyboss' ),
		'updatePostButton'    => esc_html__( 'Update Post', 'buddyboss' ),
		'cancelButton'        => esc_html__( 'Cancel', 'buddyboss' ),
		'commentLabel'        => esc_html__( '%d Comment', 'buddyboss' ),
		'commentsLabel'       => esc_html__( '%d Comments', 'buddyboss' ),
		'loadingMore'         => esc_html__( 'Loading...', 'buddyboss' ),
		'discardButton'       => esc_html__( 'Discard Draft', 'buddyboss' ),
	);

    if ( bp_get_displayed_user() && ! bp_is_my_profile() ) {
        $activity_strings['whatsnewPlaceholder'] = sprintf( esc_html__( 'Write something to %s...', 'buddyboss' ), bp_get_user_firstname( bp_get_displayed_user_fullname() ) );
    }

	if ( bp_is_group() ) {
		$activity_strings['whatsnewPlaceholder'] = esc_html__( 'Share something with the group...', 'buddyboss' );

		$activity_params = array_merge(
			$activity_params,
			array(
				'object'       => 'group',
				'item_id'      => bp_get_current_group_id(),
				'item_name'    => bp_get_current_group_name(),
				'group_avatar' => bp_get_group_avatar_url( groups_get_group( bp_get_current_group_id() ) ), // Add group avatar in get activity data object.
			)
		);

		$draft_activity_meta_key = 'draft_group_' . bp_get_current_group_id();
	}

	// Get draft activity.
	$draft_activity                    = bp_get_user_meta( bp_loggedin_user_id(), $draft_activity_meta_key, true );
	$activity_params['draft_activity'] = $draft_activity;

	$activity_params['access_control_settings'] = array(
		'can_create_activity'          => bb_user_can_create_activity(),
		'can_create_activity_media'    => bb_user_can_create_media(),
		'can_create_activity_document' => bb_user_can_create_document(),
	);

	$params['activity'] = array(
		'params'  => $activity_params,
		'strings' => $activity_strings,
	);

	return $params;
}

/**
 * Get activity directory navigation menu items.
 *
 * @since BuddyPress 3.0.0
 */
function bp_nouveau_get_activity_directory_nav_items() {
	$nav_items = array();

	$nav_items['all'] = array(
		'component' => 'activity',
		'slug'      => 'all', // slug is used because BP_Core_Nav requires it, but it's the scope
		'li_class'  => array( 'dynamic' ),
		'link'      => bp_get_activity_directory_permalink(),
		'text'      => __( 'All Updates', 'buddyboss' ),
		'count'     => false,
		'position'  => 5,
	);

	// deprecated hooks
	$deprecated_hooks = array(
		array( 'bp_before_activity_type_tab_all', 'activity', 0 ),
		array( 'bp_activity_type_tabs', 'activity', 46 ),
	);

	if ( is_user_logged_in() ) {
		$deprecated_hooks = array_merge(
			$deprecated_hooks,
			array(
				array( 'bp_before_activity_type_tab_friends', 'activity', 6 ),
				array( 'bp_before_activity_type_tab_groups', 'activity', 16 ),
				array( 'bp_before_activity_type_tab_favorites', 'activity', 26 ),
			)
		);

		// If the user has favorite create a nav item
		if ( bp_is_activity_like_active() && bp_get_total_favorite_count_for_user( bp_loggedin_user_id() ) ) {
			$nav_items['favorites'] = array(
				'component' => 'activity',
				'slug'      => 'favorites', // slug is used because BP_Core_Nav requires it, but it's the scope
				'li_class'  => array(),
				'link'      => bp_loggedin_user_domain() . bp_get_activity_slug() . '/favorites/',
				'text'      => __( 'Likes', 'buddyboss' ),
				'count'     => false,
				'position'  => 10,
			);
		}

		if ( bp_is_activity_tabs_active() ) {

			// The friends component is active and user has friends
			if ( bp_is_active( 'friends' ) && bp_get_total_friend_count( bp_loggedin_user_id() ) ) {
				$nav_items['friends'] = array(
					'component' => 'activity',
					'slug'      => 'friends', // slug is used because BP_Core_Nav requires it, but it's the scope
					'li_class'  => array( 'dynamic' ),
					'link'      => bp_loggedin_user_domain() . bp_get_activity_slug() . '/' . bp_get_friends_slug() . '/',
					'text'      => __( 'Connections', 'buddyboss' ),
					'count'     => false,
					'position'  => 15,
				);
			}

			// The groups component is active and user has groups
			if ( bp_is_active( 'groups' ) && bp_get_total_group_count_for_user( bp_loggedin_user_id() ) ) {
				$nav_items['groups'] = array(
					'component' => 'activity',
					'slug'      => 'groups', // slug is used because BP_Core_Nav requires it, but it's the scope
					'li_class'  => array( 'dynamic' ),
					'link'      => bp_loggedin_user_domain() . bp_get_activity_slug() . '/' . bp_get_groups_slug() . '/',
					'text'      => __( 'Groups', 'buddyboss' ),
					'count'     => false,
					'position'  => 25,
				);
			}

			// Mentions are allowed
			if ( bp_activity_do_mentions() ) {
				$deprecated_hooks[] = array( 'bp_before_activity_type_tab_mentions', 'activity', 36 );

				$nav_items['mentions'] = array(
					'component' => 'activity',
					'slug'      => 'mentions', // slug is used because BP_Core_Nav requires it, but it's the scope
					'li_class'  => array( 'dynamic' ),
					'link'      => bp_loggedin_user_domain() . bp_get_activity_slug() . '/mentions/',
					'text'      => __( 'Mentions', 'buddyboss' ),
					'count'     => false,
					'position'  => 45,
				);
			}

			// Following tab
			if ( bp_is_activity_follow_active() ) {

				$nav_items['following'] = array(
					'component' => 'activity',
					'slug'      => 'following', // slug is used because BP_Core_Nav requires it, but it's the scope
					'li_class'  => array( 'dynamic' ),
					'link'      => bp_loggedin_user_domain() . bp_get_activity_slug() . '/following/',
					'text'      => __( 'Following', 'buddyboss' ),
					'count'     => false,
					'position'  => 55,
				);
			}
		}
	}

	// Check for deprecated hooks.
	foreach ( $deprecated_hooks as $deprectated_hook ) {
		list( $hook, $component, $position ) = $deprectated_hook;

		$extra_nav_items = bp_nouveau_parse_hooked_dir_nav( $hook, $component, $position );

		if ( ! empty( $extra_nav_items ) ) {
			$nav_items = array_merge( $nav_items, $extra_nav_items );
		}
	}

	/**
	 * Filters the activity directory navigation items.
	 *
	 * Use this filter to introduce your custom nav items for the activity directory.
	 *
	 * @since BuddyPress 3.0.0
	 *
	 * @param array $nav_items The list of the activity directory nav items.
	 */
	return apply_filters( 'bp_nouveau_get_activity_directory_nav_items', $nav_items );
}

/**
 * Make sure bp_get_activity_show_filters() will return the filters and the context
 * instead of the output.
 *
 * @since BuddyPress 3.0.0
 *
 * @param string $output  HTML output
 * @param array  $filters Optional.
 * @param string $context
 *
 * @return array
 */
function bp_nouveau_get_activity_filters_array( $output = '', $filters = array(), $context = '' ) {
	return array(
		'filters' => $filters,
		'context' => $context,
	);
}

/**
 * Get Dropdown filters of the activity component
 *
 * @since BuddyPress 3.0.0
 *
 * @return array the filters
 */
function bp_nouveau_get_activity_filters() {
	add_filter( 'bp_get_activity_show_filters', 'bp_nouveau_get_activity_filters_array', 10, 3 );

	$filters_data = bp_get_activity_show_filters();

	remove_filter( 'bp_get_activity_show_filters', 'bp_nouveau_get_activity_filters_array', 10, 3 );

	$action = '';
	if ( 'group' === $filters_data['context'] ) {
		$action = 'bp_group_activity_filter_options';
	} elseif ( 'member' === $filters_data['context'] || 'member_groups' === $filters_data['context'] ) {
		$action = 'bp_member_activity_filter_options';
	} else {
		$action = 'bp_activity_filter_options';
	}

	$filters = $filters_data['filters'];

	if ( $action ) {
		return bp_nouveau_parse_hooked_options( $action, $filters );
	}

	return $filters;
}

/**
 * Adds a small avatar to activity meta action if the activity is between a connection or group.
 *
 * @since BuddyPress 3.0.0
 */
function bp_nouveau_activity_secondary_avatars( $action, $activity ) {
	switch ( $activity->component ) {
		case 'groups':
		case 'friends':
			// Only insert avatar if one exists.
			if ( $secondary_avatar = bp_get_activity_secondary_avatar() ) {
				$reverse_content = strrev( $action );
				$position        = strpos( $reverse_content, 'a<' );
				$action          = substr_replace( $action, $secondary_avatar, -$position - 2, 0 );
			}
			break;
	}

	return $action;
}

/**
 * Add class to newest activities by type.
 *
 * @since BuddyPress 3.0.0
 */
function bp_nouveau_activity_scope_newest_class( $classes = '' ) {
	if ( ! is_user_logged_in() ) {
		return $classes;
	}

	$user_id    = bp_loggedin_user_id();
	$my_classes = array();

	/*
	 * HeartBeat requests will transport the scope.
	 * See bp_nouveau_ajax_querystring().
	 */
	$scope = '';

	if ( ! empty( $_POST['data']['bp_heartbeat']['scope'] ) ) {
		$scope = sanitize_key( $_POST['data']['bp_heartbeat']['scope'] );
	}

	// Add specific classes to perform specific actions on the client side.
	if ( $scope && bp_is_activity_directory() ) {
		$component = bp_get_activity_object_name();

		/*
		 * These classes will be used to count the number of newest activities for
		 * the 'Mentions', 'My Groups' & 'My Connections' tabs
		 */
		if ( 'all' === $scope ) {
			if ( 'groups' === $component && bp_is_active( $component ) ) {
				// Is the current user a member of the group the activity is attached to?
				if ( groups_is_user_member( $user_id, bp_get_activity_item_id() ) ) {
					$my_classes[] = 'bp-my-groups';
				}
			}

			// Connections can post in groups the user is a member of
			if ( bp_is_active( 'friends' ) && (int) $user_id !== (int) bp_get_activity_user_id() ) {
				if ( friends_check_friendship( $user_id, bp_get_activity_user_id() ) ) {
					$my_classes[] = 'bp-my-friends';
				}
			}

			// A mention can be posted by a friend within a group
			if ( true === bp_activity_do_mentions() ) {
				$new_mentions = bp_get_user_meta( $user_id, 'bp_new_mentions', true );

				// The current activity is one of the new mentions
				if ( is_array( $new_mentions ) && in_array( bp_get_activity_id(), $new_mentions ) ) {
					$my_classes[] = 'bp-my-mentions';
				}
			}

		/*
		 * This class will be used to highlight the newest activities when
		 * viewing the 'Mentions', 'My Groups' or the 'My Connections' tabs
		 */
		} elseif ( 'friends' === $scope || 'groups' === $scope || 'mentions' === $scope ) {
			$my_classes[] = 'newest_' . $scope . '_activity';
		}

		// Leave other components do their specific stuff if needed.
		/**
		 * Filters the classes to be applied to the newest activity item.
		 *
		 * Leave other components do their specific stuff if needed.
		 *
		 * @since BuddyPress 3.0.0
		 *
		 * @param array  $my_classes Array of classes to output to class attribute.
		 * @param string $scope      Current scope for the activity type.
		 */
		$my_classes = (array) apply_filters( 'bp_nouveau_activity_scope_newest_class', $my_classes, $scope );

		if ( ! empty( $my_classes ) ) {
			$classes .= ' ' . join( ' ', $my_classes );
		}
	}

	return $classes;
}

/**
 * Get the activity query args for the widget.
 *
 * @since BuddyPress 3.0.0
 *
 * @return array The activity arguments.
 */
function bp_nouveau_activity_widget_query() {
	$args       = array();
	$bp_nouveau = bp_nouveau();

	if ( isset( $bp_nouveau->activity->widget_args ) ) {
		$args = $bp_nouveau->activity->widget_args;
	}

	/**
	 * Filter to edit the activity widget arguments.
	 *
	 * @since BuddyPress 3.0.0
	 *
	 * @param array $args The activity arguments.
	 */
	return apply_filters( 'bp_nouveau_activity_widget_query', $args );
}

/**
 * Register notifications filters for the activity component.
 *
 * @since BuddyPress 3.0.0
 */
function bp_nouveau_activity_notification_filters() {

	if ( ! bb_enabled_legacy_email_preference() ) {
		return;
	}

	$notifications = array(
		array(
			'id'       => 'new_at_mention',
			'label'    => __( 'New mentions', 'buddyboss' ),
			'position' => 5,
		),
		array(
			'id'       => 'update_reply',
			'label'    => __( 'New update replies', 'buddyboss' ),
			'position' => 15,
		),
		array(
			'id'       => 'comment_reply',
			'label'    => __( 'New update comment replies', 'buddyboss' ),
			'position' => 25,
		),
	);

	foreach ( $notifications as $notification ) {
		bp_nouveau_notifications_register_filter( $notification );
	}
}

/**
 * Add controls for the settings of the customizer for the activity component.
 *
 * @since BuddyPress 3.0.0
 *
 * @param array $controls Optional. The controls to add.
 *
 * @return array the controls to add.
 */
function bp_nouveau_activity_customizer_controls( $controls = array() ) {
	return array_merge( $controls, array(
//		'act_dir_layout' => array(
//			'label'      => __( 'Use column navigation for the Activity directory.', 'buddyboss' ),
//			'section'    => 'bp_nouveau_dir_layout',
//			'settings'   => 'bp_nouveau_appearance[activity_dir_layout]',
//			'type'       => 'checkbox',
//		),
//		'act_dir_tabs' => array(
//			'label'      => __( 'Use tab styling for Activity directory navigation.', 'buddyboss' ),
//			'section'    => 'bp_nouveau_dir_layout',
//			'settings'   => 'bp_nouveau_appearance[activity_dir_tabs]',
//			'type'       => 'checkbox',
//		),
	) );
}


