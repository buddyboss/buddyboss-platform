<?php
/**
 * Groups functions
 *
 * @since BuddyPress 3.0.0
 * @version 3.1.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Provide a convenience function to add markup wrapper for message strings
 *
 * @param string $message The message text string
 * @param string $type    The message type - 'error, 'info', 'warning', success'
 *
 * @return string
 *
 * @since BuddyPress 3.0
 */
function bp_nouveau_message_markup_wrapper( $message, $type ) {
	if ( ! $message ) {
		return false;
	}

	$message = '<div class=" ' . esc_attr( "bp-feedback {$type}" ) . '"><span class="bp-icon" aria-hidden="true"></span><p>' . esc_html( $message ) . '</p></div>';

	return $message;
}

/**
 * Register Scripts for the Groups component
 *
 * @since BuddyPress 3.0.0
 *
 * @param array $scripts Optional. The array of scripts to register.
 *
 * @return array The same array with the specific groups scripts.
 */
function bp_nouveau_groups_register_scripts( $scripts = array() ) {
	if ( ! isset( $scripts['bp-nouveau'] ) ) {
		return $scripts;
	}

	$message_scripts = array();

	$message_scripts['bp-nouveau-group-invites'] =  array(
			'file'         => 'js/buddypress-group-invites%s.js',
			'dependencies' => array( 'bp-nouveau', 'json2', 'wp-backbone' ),
			'footer'       => true,
		);

	if ( true === bp_disable_group_messages() ) {
		$message_scripts['bp-nouveau-group-messages'] = array(
			'file'         => 'js/buddypress-group-messages%s.js',
			'dependencies' => array( 'bp-nouveau', 'json2', 'wp-backbone', 'bp-nouveau-messages-at', 'bp-select2' ),
			'footer'       => true,
		);
	}

	return array_merge( $scripts, $message_scripts );

}

/**
 * Enqueue the groups scripts
 *
 * @since BuddyPress 3.0.0
 */
function bp_nouveau_groups_enqueue_scripts() {
	// Neutralize Ajax when using BuddyBoss Groups & member widgets on default front page
	if ( bp_is_group_home() && bp_nouveau_get_appearance_settings( 'group_front_page' ) ) {
		wp_add_inline_style( 'bp-nouveau', '
			#group-front-widgets #groups-list-options,
			#group-front-widgets #members-list-options {
				display: none;
			}
		' );
	}

	if ( ! bp_is_group_invites() && ! ( bp_is_group_create() && bp_is_group_creation_step( 'group-invites' ) ) ) {
		return;
	}

	wp_enqueue_script( 'bp-select2' );
	wp_enqueue_script( 'bp-nouveau-group-invites' );
}

/**
 * Can all members be invited to join any group?
 *
 * @since BuddyPress 3.0.0
 *
 * @param bool $default False to allow. True to disallow.
 *
 * @return bool
 */
function bp_nouveau_groups_disallow_all_members_invites( $default = false ) {
	/**
	 * Filter to remove the All members nav, returning true
	 *
	 * @since BuddyPress 3.0.0
	 *
	 * @param bool $default True to disable the nav. False otherwise.
	 */
	return apply_filters( 'bp_nouveau_groups_disallow_all_members_invites', $default );
}

/**
 * Localize the strings needed for the Group's Invite UI
 *
 * @since BuddyPress 3.0.0
 *
 * @param array $params Associative array containing the JS Strings needed by scripts
 *
 * @return array The same array with specific strings for the Group's Invite UI if needed.
 */
function bp_nouveau_groups_localize_scripts( $params = array() ) {
	if ( ! bp_is_group_invites() && ! ( bp_is_group_create() && bp_is_group_creation_step( 'group-invites' ) ) ) {
		return $params;
	}

	$show_pending = bp_group_has_invites( array( 'user_id' => 'any' ) ) && ! bp_is_group_create();

	// Init the Group invites nav
	$invites_nav = array(
		'members' => array(
			'id'      => 'members',
			'caption' => __( 'All Members', 'buddyboss' ),
			'order'   => 5,
		),
		'invited' => array(
			'id'      => 'invited',
			'caption' => __( 'Pending Invites', 'buddyboss' ),
			'order'   => 90,
			'hide'    => (int) ! $show_pending,
		),
		'invites' => array(
			'id'      => 'invites',
			'caption' => __( 'Send Invites', 'buddyboss' ),
			'order'   => 100,
			'hide'    => 1,
			'href'    => '#send-invites-editor',
		),
	);

	if ( bp_is_active( 'friends' ) ) {
		$invites_nav['friends'] = array(
			'id'      => 'friends',
			'caption' => __( 'My Connections', 'buddyboss' ),
			'order'   => 0,
		);

		if ( true === bp_nouveau_groups_disallow_all_members_invites() ) {
			unset( $invites_nav['members'] );
		}
	}

	$params['group_invites'] = array(
		'nav'                     => bp_sort_by_key( $invites_nav, 'order', 'num' ),
		'loading'                 => __( 'Loading members. Please wait.', 'buddyboss' ),
		'removing'                => __( 'Removing member invite. Please wait.', 'buddyboss' ),
		'invites_form'            => '',
		'cancel_invite_tooltip'   => __( 'Cancel Invite', 'buddyboss' ),
		'add_invite_tooltip'      => __( 'Send Invite', 'buddyboss' ),
		'invites_form_reset'      => __( 'Group invitations cleared. Please use one of the available tabs to select members to invite.', 'buddyboss' ),
		'invites_sending'         => __( 'Sending group invitations. Please wait.', 'buddyboss' ),
		'removeUserInvite'        => __( 'Cancel invitation %s', 'buddyboss' ),
		'all_member_invited'      => __( 'All members of this group are invited.', 'buddyboss' ),
		'member_invite_info_text' => __( 'Select members to invite by clicking the + button next to each member.', 'buddyboss' ),
		'group_id'                => ! bp_get_current_group_id() ? bp_get_new_group_id() : bp_get_current_group_id(),
		'is_group_create'         => bp_is_group_create(),
		'nonces'                  => array(
			'uninvite'     => wp_create_nonce( 'groups_invite_uninvite_user' ),
			'send_invites' => wp_create_nonce( 'groups_send_invites' ),
		),
	);

	return $params;
}

/**
 * Returns id of member who sent group invite.
 *
 * @since BuddyPress 3.0.0
 */
function bp_nouveau_groups_get_inviter_ids( $user_id, $group_id ) {
	if ( empty( $user_id ) || empty( $group_id ) ) {
		return false;
	}

	return BP_Nouveau_Group_Invite_Query::get_inviter_ids( $user_id, $group_id );
}

/**
 * Prepare list of group invites for JS.
 *
 * @since BuddyPress 3.0.0
 */
function bp_nouveau_prepare_group_potential_invites_for_js( $user ) {
	$bp = buddypress();

	$response = array(
		'id'     => intval( $user->ID ),
		'name'   => bp_core_get_user_displayname( intval( $user->ID ) ),
		'avatar' => htmlspecialchars_decode(
			bp_core_fetch_avatar(
				array(
					'item_id' => $user->ID,
					'object'  => 'user',
					'type'    => 'thumb',
					'width'   => 150,
					'height'  => 150,
					'html'    => false,
				)
			),
			ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401
		),
	);

	// Group id
	$group_id = bp_get_current_group_id()?: (int) $_REQUEST['group_id'];

	// Do extra queries only if needed
	if ( ! empty( $bp->groups->invites_scope ) && 'invited' === $bp->groups->invites_scope ) {
		$response['is_sent'] = (bool) groups_check_user_has_invite( $user->ID, $group_id );

		$inviter_ids = bp_nouveau_groups_get_inviter_ids( $user->ID, $group_id );

		foreach ( $inviter_ids as $inviter_id ) {
			$class = false;

			if ( bp_loggedin_user_id() === (int) $inviter_id ) {
				$class = 'group-self-inviter';
			}

			$response['invited_by'][] = array(
				'avatar' => htmlspecialchars_decode(
					bp_core_fetch_avatar(
						array(
							'item_id' => $inviter_id,
							'object'  => 'user',
							'type'    => 'thumb',
							'width'   => 50,
							'height'  => 50,
							'html'    => false,
							'class'   => $class,
						)
					),
					ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401
				),
				'user_link' => bp_core_get_userlink( $inviter_id, false, true ),
				'user_name' => bp_core_get_username( $inviter_id ),
				'name'      => bp_core_get_user_displayname( intval( $inviter_id ) ),
			);
		}

		if ( bp_is_item_admin() ) {
			$response['can_edit'] = true;
		} else {
			$response['can_edit'] = in_array( bp_loggedin_user_id(), $inviter_ids );
		}
	}

	/**
	 * Filters the response value for potential group invite data for use with javascript.
	 *
	 * @since BuddyPress 3.0.0
	 *
	 * @param array   $response Array of invite data.
	 * @param WP_User $user User object.
	 */
	return apply_filters( 'bp_nouveau_prepare_group_potential_invites_for_js', $response, $user );
}

/**
 * Get potential group invites.
 *
 * @since BuddyPress 3.0.0
 */
function bp_nouveau_get_group_potential_invites( $args = array() ) {
	$r = bp_parse_args( $args, array(
		'group_id'     => bp_get_current_group_id(),
		'type'         => 'alphabetical',
		'per_page'     => 20,
		'page'         => 1,
		'search_terms' => false,
		'member_type'  => false,
		'user_id'      => 0,
		'is_confirmed' => true,
	) );

	if ( empty( $r['group_id'] ) ) {
		return false;
	}

	// Check the current user's access to the group.
	if ( ! bp_groups_user_can_send_invites( $r['group_id'] ) ) {
		return false;
	}

	/*
	 * If it's not a friend request and users can restrict invites to friends,
	 * make sure they are not displayed in results.
	 */
	if ( ! $r['user_id'] && bp_is_active( 'friends' ) && bp_is_active( 'settings' ) && ! bp_nouveau_groups_disallow_all_members_invites() ) {
		$r['meta_query'] = array(
			array(
				'key'     => '_bp_nouveau_restrict_invites_to_friends',
				'compare' => 'NOT EXISTS',
			),
		);
	}

	$query = new BP_Nouveau_Group_Invite_Query( $r );

	$response = new stdClass();

	$response->meta  = array( 'total_page' => 0, 'current_page' => 0 );
	$response->users = array();

	if ( ! empty( $query->results ) ) {
		$response->users = $query->results;

		if ( ! empty( $r['per_page'] ) ) {
			$response->meta = array(
				'total_page' => ceil( (int) $query->total_users / (int) $r['per_page'] ),
				'page'       => (int) $r['page'],
			);
		}
	}

	return $response;
}

/**
 * Create group invites steps
 *
 * @since BuddyPress 3.0.0
 */
function bp_nouveau_group_invites_create_steps( $steps = array() ) {
	if ( bp_is_active( 'friends' ) && isset( $steps['group-invites'] ) ) {
		// Simply change the name
		$steps['group-invites']['name'] = __( 'Invite', 'buddyboss' );
		return $steps;
	}

	// Add the create step if friends component is not active
	$steps['group-invites'] = array(
		'name'     => __( 'Invite', 'buddyboss' ),
		'position' => 30,
	);

	return $steps;
}

/**
 * Setup group invite navigation menu item.
 *
 * @since BuddyPress 3.0.0
 */
function bp_nouveau_group_setup_nav() {
	if ( ! bp_is_group() || ! bp_groups_user_can_send_invites() ) {
		return;
	}

	// Simply change the name
	if ( bp_is_active( 'friends' ) ) {
		$bp = buddypress();

		$bp->groups->nav->edit_nav(
			array( 'name' => __( 'Send Invites', 'buddyboss' ) ),
			'invite',
			bp_get_current_group_slug()
		);

	// Create the Subnav item for the group
	} else {
		$current_group = groups_get_current_group();
		$group_link    = bp_get_group_permalink( $current_group );

		bp_core_new_subnav_item( array(
			'name'            => __( 'Send Invites', 'buddyboss' ),
			'slug'            => 'invite',
			'parent_url'      => $group_link,
			'parent_slug'     => $current_group->slug,
			'screen_function' => 'groups_screen_group_invite',
			'item_css_id'     => 'invite',
			'position'        => 70,
			'user_has_access' => $current_group->user_has_access,
			'no_access_url'   => $group_link,
		) );

		if ( ! bp_is_active( 'friends' ) ) {

			bp_core_new_subnav_item( array(
				'name'            => __( 'Send Invites', 'buddyboss' ),
				'slug'            => 'invite/send-invites',
				'parent_url'      => $group_link,
				'parent_slug'     => $current_group->slug . '_invite',
				'screen_function' => 'groups_screen_group_invite',
				'item_css_id'     => 'send-invites',
				'position'        => 71,
				'user_has_access' => $current_group->user_has_access,
				'no_access_url'   => $group_link,
			) );

			bp_core_new_subnav_item( array(
				'name'            => __( 'Pending Invites', 'buddyboss' ),
				'slug'            => 'invite/pending-invites',
				'parent_url'      => $group_link,
				'parent_slug'     => $current_group->slug . '_invite',
				'screen_function' => 'groups_screen_group_invite',
				'item_css_id'     => 'pending-invites',
				'position'        => 72,
				'user_has_access' => $current_group->user_has_access,
				'no_access_url'   => $group_link,
			) );
		}
	}
}

/**
 * Returns group invite custom message.
 *
 * @since BuddyPress 3.0.0
 */
function bp_nouveau_groups_invites_custom_message( $message = '' ) {
	if ( empty( $message ) ) {
		return $message;
	}

	$bp = buddypress();

	if ( empty( $bp->groups->invites_message ) ) {
		return $message;
	}

	$message = str_replace( '---------------------', "
---------------------\n
" . $bp->groups->invites_message . "\n
---------------------
	", $message );

	return $message;
}

/**
 * Format a Group for a json reply
 *
 * @since BuddyPress 3.0.0
 */
function bp_nouveau_prepare_group_for_js( $item ) {
	if ( empty( $item->id ) ) {
		return array();
	}

	$item_avatar_url = bp_core_fetch_avatar( array(
		'item_id'    => $item->id,
		'object'     => 'group',
		'type'       => 'thumb',
		'width'      => 100,
		'height'     => 100,
		'html'       => false
	) );

	return array(
		'id'             => $item->id,
		'name'           => bp_get_group_name( $item ),
		'avatar_url'     => $item_avatar_url,
		'object_type'    => 'group',
		'is_public'      => ( 'public' === $item->status ),
		'group_media'    => ( bp_is_active( 'media' ) && bp_is_group_media_support_enabled() && bb_media_user_can_upload( bp_loggedin_user_id(), $item->id ) ),
		'group_document' => ( bp_is_active( 'document' ) && bp_is_group_document_support_enabled() && bb_document_user_can_upload( bp_loggedin_user_id(), $item->id ) ),
		'group_video'    => ( bp_is_active( 'video' ) && bp_is_group_video_support_enabled() && bb_video_user_can_upload( bp_loggedin_user_id(), $item->id ) ),
	);
}

/**
 * Group invites restriction settings navigation.
 *
 * @since BuddyPress 3.0.0
 */
function bp_nouveau_groups_invites_restriction_nav() {
	$slug        = bp_get_settings_slug();
	$user_domain = bp_loggedin_user_domain();

	if ( bp_displayed_user_domain() ) {
		$user_domain = bp_displayed_user_domain();
	}

	bp_core_new_subnav_item( array(
		'name'            => __( 'Group Invites', 'buddyboss' ),
		'slug'            => 'invites',
		'parent_url'      => trailingslashit( $user_domain . $slug ),
		'parent_slug'     => $slug,
		'screen_function' => 'bp_nouveau_groups_screen_invites_restriction',
		'item_css_id'     => 'invites',
		'position'        => 70,
		'user_has_access' => bp_core_can_edit_settings(),
	), 'members' );
}

/**
 * Group invites restriction settings Admin Bar navigation.
 *
 * @since BuddyPress 3.0.0
 *
 * @param array $wp_admin_nav The list of settings admin subnav items.
 *
 * @return array The list of settings admin subnav items.
 */
function bp_nouveau_groups_invites_restriction_admin_nav( $wp_admin_nav ) {
	// Setup the logged in user variables.
	$settings_link = trailingslashit( bp_loggedin_user_domain() . bp_get_settings_slug() );

	// Add the "Group Invites" subnav item.
	$wp_admin_nav[] = array(
		'parent' => 'my-account-' . buddypress()->settings->id,
		'id'     => 'my-account-' . buddypress()->settings->id . '-invites',
		'title'  => __( 'Group Invites', 'buddyboss' ),
		'href'   => trailingslashit( $settings_link . 'invites/' ),
	);

	return $wp_admin_nav;
}

/**
 * Group invites restriction screen.
 *
 * @since BuddyPress 3.0.0
 */
function bp_nouveau_groups_screen_invites_restriction() {
	// Redirect if no invites restriction settings page is accessible.
	if ( 'invites' !== bp_current_action() || ! bp_is_active( 'friends' ) ) {
		bp_do_404();
		return;
	}

	if ( isset( $_POST['member-group-invites-submit'] ) ) {
		// Nonce check.
		check_admin_referer( 'bp_nouveau_group_invites_settings' );

		if ( bp_is_my_profile() || bp_current_user_can( 'bp_moderate' ) ) {
			if ( empty( $_POST['account-group-invites-preferences'] ) ) {
				bp_delete_user_meta( bp_displayed_user_id(), '_bp_nouveau_restrict_invites_to_friends' );
			} else {
				bp_update_user_meta( bp_displayed_user_id(), '_bp_nouveau_restrict_invites_to_friends', (int) $_POST['account-group-invites-preferences'] );
			}

			bp_core_add_message( __( 'Group invites preferences saved.', 'buddyboss' ) );
		} else {
			bp_core_add_message( __( 'You are not allowed to perform this action.', 'buddyboss' ), 'error' );
		}

		bp_core_redirect( trailingslashit( bp_displayed_user_domain() . bp_get_settings_slug() ) . 'invites/' );
	}

	/**
	 * Filters the template to load for the Group Invites settings screen.
	 *
	 * @since BuddyPress 3.0.0
	 *
	 * @param string $template Path to the Group Invites settings screen template to load.
	 */
	bp_core_load_template( apply_filters( 'bp_nouveau_groups_screen_invites_restriction', 'members/single/settings/group-invites' ) );
}

/**
 * Get group directory navigation menu items.
 *
 * @since BuddyPress 3.0.0
 */
function bp_nouveau_get_groups_directory_nav_items() {
	$nav_items = array();

	$nav_items['all'] = array(
		'component' => 'groups',
		'slug'      => 'all', // slug is used because BP_Core_Nav requires it, but it's the scope
		'li_class'  => array( 'selected' ),
		'link'      => bp_get_groups_directory_permalink(),
		'text'      => __( 'All Groups', 'buddyboss' ),
		'count'     => bp_get_total_group_count(),
		'position'  => 5,
	);

	if ( is_user_logged_in() ) {

		$group_type = bp_get_current_group_directory_type();

		if ( ! empty( $group_type ) ) {
			$result_groups   = groups_get_groups(
				array(
					'user_id'    => bp_loggedin_user_id(),
					'group_type' => $group_type,
					'fields'     => 'ids',
				)
			);
			$my_groups_count = isset( $result_groups['total'] ) ? (int) $result_groups['total'] : 0;
		} else {
			$my_groups_count = bp_get_total_group_count_for_user( bp_loggedin_user_id() );
		}

		// If the user has groups create a nav item
		if ( $my_groups_count ) {
			$nav_items['personal'] = array(
				'component' => 'groups',
				'slug'      => 'personal', // slug is used because BP_Core_Nav requires it, but it's the scope
				'li_class'  => array(),
				'link'      => bp_loggedin_user_domain() . bp_get_groups_slug() . '/my-groups/',
				'text'      => __( 'My Groups', 'buddyboss' ),
				'count'     => $my_groups_count,
				'position'  => 15,
			);
		}

		// If the user can create groups, add the create nav
		if ( bp_user_can_create_groups() ) {
			$nav_items['create'] = array(
				'component' => 'groups',
				'slug'      => 'create', // slug is used because BP_Core_Nav requires it, but it's the scope
				'li_class'  => array( 'no-ajax', 'group-create', 'create-button' ),
				'link'      => trailingslashit( bp_get_groups_directory_permalink() . 'create' ),
				'text'      => __( 'Create a Group', 'buddyboss' ),
				'count'     => false,
				'position'  => 999,
			);
		}
	}

	// Check for the deprecated hook :
	$extra_nav_items = bp_nouveau_parse_hooked_dir_nav( 'bp_groups_directory_group_filter', 'groups', 20 );

	if ( ! empty( $extra_nav_items ) ) {
		$nav_items = array_merge( $nav_items, $extra_nav_items );
	}

	/**
	 * Use this filter to introduce your custom nav items for the groups directory.
	 *
	 * @since BuddyPress 3.0.0
	 *
	 * @param  array $nav_items The list of the groups directory nav items.
	 */
	return apply_filters( 'bp_nouveau_get_groups_directory_nav_items', $nav_items );
}

/**
 * Get Dropdown filters for the groups component
 *
 * @since BuddyPress 3.0.0
 *
 * @param string $context 'directory' or 'user'
 *
 * @return array the filters
 */
function bp_nouveau_get_groups_filters( $context = '' ) {
	if ( empty( $context ) ) {
		return array();
	}

	$action = '';
	if ( 'user' === $context ) {
		$action = 'bp_member_group_order_options';
	} elseif ( 'directory' === $context ) {
		$action = 'bp_groups_directory_order_options';
	}

	/**
	 * Recommended, filter here instead of adding an action to 'bp_member_group_order_options'
	 * or 'bp_groups_directory_order_options'
	 *
	 * @since BuddyPress 3.0.0
	 *
	 * @param array  the members filters.
	 * @param string the context.
	 */
	$filters = apply_filters( 'bp_nouveau_get_groups_filters', array(
		'active'       => __( 'Recently Active', 'buddyboss' ),
		'popular'      => __( 'Most Members', 'buddyboss' ),
		'newest'       => __( 'Newly Created', 'buddyboss' ),
		'alphabetical' => __( 'Alphabetical', 'buddyboss' ),
	), $context );

	if ( $action ) {
		return bp_nouveau_parse_hooked_options( $action, $filters );
	}

	return $filters;
}

/**
 * Catch the arguments for buttons
 *
 * @since BuddyPress 3.0.0
 *
 * @param array $button The arguments of the button that BuddyPress is about to create.
 *
 * @return array An empty array to stop the button creation process.
 */
function bp_nouveau_groups_catch_button_args( $button = array() ) {
	/**
	 * Globalize the arguments so that we can use it
	 * in bp_nouveau_get_groups_buttons().
	 */
	bp_nouveau()->groups->button_args = $button;

	// return an empty array to stop the button creation process
	return array();
}

/**
 * Catch the content hooked to the 'bp_group_header_meta' action
 *
 * @since BuddyPress 3.0.0
 *
 * @return string|bool HTML Output if hooked. False otherwise.
 */
function bp_nouveau_get_hooked_group_meta() {
	ob_start();

	/**
	 * Fires after inside the group header item meta section.
	 *
	 * @since BuddyPress 1.2.0
	 */
	do_action( 'bp_group_header_meta' );

	$output = ob_get_clean();

	if ( ! empty( $output ) ) {
		return $output;
	}

	return false;
}

/**
 * Display the Widgets of Group extensions into the default front page?
 *
 * @since BuddyPress 3.0.0
 *
 * @return bool True to display. False otherwise.
 */
function bp_nouveau_groups_do_group_boxes() {
	$group_settings = bp_nouveau_get_appearance_settings();

	return ! empty( $group_settings['group_front_page'] ) && ! empty( $group_settings['group_front_boxes'] );
}

/**
 * Display description of the Group into the default front page?
 *
 * @since BuddyPress 3.0.0
 *
 * @return bool True to display. False otherwise.
 */
function bp_nouveau_groups_front_page_description() {
	$group_settings = bp_nouveau_get_appearance_settings();

	// This check is a problem it needs to be used in templates but returns true even if not on the front page
	// return false on this if we are not displaying the front page 'bp_is_group_home()'
	// This may well be a bad approach to re-think ~hnla.
	// @todo
	return ! empty( $group_settings['group_front_page'] ) && ! empty( $group_settings['group_front_description'] ) && bp_is_group_home();
}

/**
 * Add sections to the customizer for the groups component.
 *
 * @since BuddyPress 3.0.0
 *
 * @param array $sections the Customizer sections to add.
 *
 * @return array the Customizer sections to add.
 */
function bp_nouveau_groups_customizer_sections( $sections = array() ) {
	return array_merge( $sections, array(
		'bp_nouveau_group_primary_nav' => array(
			'title'       => __( 'Group Navigation', 'buddyboss' ),
			'panel'       => 'bp_nouveau_panel',
			'priority'    => 40,
			'description' => __( 'Customize the navigation menu for groups. See your changes by navigating to a group in the live-preview window.', 'buddyboss' ),
		),
	) );
}

/**
 * Add settings to the customizer for the groups component.
 *
 * @since BuddyPress 3.0.0
 *
 * @param array $settings Optional. The settings to add.
 *
 * @return array the settings to add.
 */
function bp_nouveau_groups_customizer_settings( $settings = array() ) {
	return array_merge( $settings, array(
		'bp_nouveau_appearance[group_front_page]'        => array(
			'index'             => 'group_front_page',
			'capability'        => 'bp_moderate',
			'sanitize_callback' => 'absint',
			'transport'         => 'refresh',
			'type'              => 'option',
		),
		'bp_nouveau_appearance[group_front_boxes]'       => array(
			'index'             => 'group_front_boxes',
			'capability'        => 'bp_moderate',
			'sanitize_callback' => 'absint',
			'transport'         => 'refresh',
			'type'              => 'option',
		),
		'bp_nouveau_appearance[group_front_description]' => array(
			'index'             => 'group_front_description',
			'capability'        => 'bp_moderate',
			'sanitize_callback' => 'absint',
			'transport'         => 'refresh',
			'type'              => 'option',
		),
		'bp_nouveau_appearance[group_nav_display]'       => array(
			'index'             => 'group_nav_display',
			'capability'        => 'bp_moderate',
			'sanitize_callback' => 'absint',
			'transport'         => 'refresh',
			'type'              => 'option',
		),
		'bp_nouveau_appearance[group_nav_order]'         => array(
			'index'             => 'group_nav_order',
			'capability'        => 'bp_moderate',
			'sanitize_callback' => 'bp_nouveau_sanitize_nav_order',
			'transport'         => 'refresh',
			'type'              => 'option',
		),
		'bp_nouveau_appearance[groups_dir_tabs]'         => array(
			'index'             => 'groups_dir_tabs',
			'capability'        => 'bp_moderate',
			'sanitize_callback' => 'absint',
			'transport'         => 'refresh',
			'type'              => 'option',
		),
		'bp_nouveau_appearance[group_default_tab]'       => array(
			'index'      => 'group_default_tab',
			'capability' => 'bp_moderate',
			'transport'  => 'refresh',
			'type'       => 'option',
		),
	) );
}

/**
 * Add controls for the settings of the customizer for the groups component.
 *
 * @since BuddyPress 3.0.0
 *
 * @param array $controls Optional. The controls to add.
 *
 * @return array the controls to add.
 */
function bp_nouveau_groups_customizer_controls( $controls = array() ) {

	// Default options for the groups default tab.
	if ( bp_is_active( 'activity' ) ) {
		$options = apply_filters( 'group_default_tab_options_list',
			array(
				'members'  => __( 'Members', 'buddyboss' ),
				'activity' => __( 'Feed', 'buddyboss' ),
			) );
	} else {
		$options = apply_filters( 'group_default_tab_options_list',
			array(
				'members' => __( 'Members', 'buddyboss' ),
			) );
	}

	if ( bp_is_active( 'media' ) && bp_is_group_media_support_enabled() ) {
		$options['photos'] = __( 'Photos', 'buddyboss' );
	}

	if ( bp_is_active( 'media' ) && bp_is_group_albums_support_enabled() ) {
		$options['albums'] = __( 'Albums', 'buddyboss' );
	}

	if ( bp_is_active( 'media' ) && bp_is_group_document_support_enabled() ) {
		$options['documents'] = __( 'Documents', 'buddyboss' );
	}

	if ( bp_is_active( 'media' ) && bp_is_group_video_support_enabled() ) {
		$options['videos'] = __( 'Videos', 'buddyboss' );
	}

	return array_merge( $controls,
		array(
			'group_nav_display' => array(
				'label'    => __( 'Display the group navigation vertically.', 'buddyboss' ),
				'section'  => 'bp_nouveau_group_primary_nav',
				'settings' => 'bp_nouveau_appearance[group_nav_display]',
				'type'     => 'checkbox',
			),
			'group_default_tab' => array(
				'label'       => __( 'Group navigation order', 'buddyboss' ),
				'description' => __( 'Set the default navigation tab when viewing a group. The dropdown only shows tabs that are available to all groups.', 'buddyboss' ),
				'section'     => 'bp_nouveau_group_primary_nav',
				'settings'    => 'bp_nouveau_appearance[group_default_tab]',
				'type'        => 'select',
				'choices'     => $options,
			),
			'group_nav_order'   => array(
				'class'    => 'BP_Nouveau_Nav_Customize_Control',
				'label'    => __( 'Reorder the primary navigation for a group.', 'buddyboss' ),
				'section'  => 'bp_nouveau_group_primary_nav',
				'settings' => 'bp_nouveau_appearance[group_nav_order]',
				'type'     => 'group',
			),
		) );
}

/**
 * Add the default group front template to the front template hierarchy.
 *
 * @since BuddyPress 3.0.0
 *
 * @param array           $templates Optional. The list of templates for the front.php template part.
 * @param BP_Groups_Group $group Optional. The group object.
 *
 * @return array The same list with the default front template if needed.
 */
function bp_nouveau_group_reset_front_template( $templates = array(), $group = null ) {
	if ( empty( $group->id ) ) {
		return $templates;
	}

	$use_default_front = bp_nouveau_get_appearance_settings( 'group_front_page' );

	// Setting the front template happens too early, so we need this!
	if ( is_customize_preview() ) {
		$use_default_front = bp_nouveau_get_temporary_setting( 'group_front_page', $use_default_front );
	}

	if ( ! empty( $use_default_front ) ) {
		array_push( $templates, 'groups/single/default-front.php' );
	}

	/**
	 * Filters the BuddyPress Nouveau template hierarchy after resetting front template for groups.
	 *
	 * @since BuddyPress 3.0.0
	 *
	 * @param array $templates Array of templates.
	 */
	return apply_filters( '_bp_nouveau_group_reset_front_template', $templates );
}

/**
 * Locate a single group template into a specific hierarchy.
 *
 * @since BuddyPress 3.0.0
 *
 * @param string $template Optional. The template part to get (eg: activity, members...).
 *
 * @return string The located template.
 */
function bp_nouveau_group_locate_template_part( $template = '' ) {
	$current_group = groups_get_current_group();
	$bp_nouveau    = bp_nouveau();

	if ( ! $template || empty( $current_group->id ) ) {
		return '';
	}

	// Use a global to avoid requesting the hierarchy for each template
	if ( ! isset( $bp_nouveau->groups->current_group_hierarchy ) ) {
		$bp_nouveau->groups->current_group_hierarchy = array(
			'groups/single/%s-id-' . (int) $current_group->id . '.php',
			'groups/single/%s-slug-' . sanitize_file_name( $current_group->slug ) . '.php',
		);

		/**
		 * Check for group types and add it to the hierarchy
		 */
		if ( bp_groups_get_group_types() ) {
			$current_group_type = bp_groups_get_group_type( $current_group->id );
			if ( ! $current_group_type ) {
				$current_group_type = 'none';
			}

			$bp_nouveau->groups->current_group_hierarchy[] = 'groups/single/%s-group-type-' . sanitize_file_name( $current_group_type ) . '.php';
		}

		$bp_nouveau->groups->current_group_hierarchy = array_merge( $bp_nouveau->groups->current_group_hierarchy, array(
			'groups/single/%s-status-' . sanitize_file_name( $current_group->status ) . '.php',
			'groups/single/%s.php'
		) );
	}

	// Init the templates
	$templates = array();

	// Loop in the hierarchy to fill it for the requested template part
	foreach ( $bp_nouveau->groups->current_group_hierarchy as $part ) {
		$templates[] = sprintf( $part, sanitize_file_name( $template ) );
	}

	/**
	 * Filters the found template parts for the group template part locating functionality.
	 *
	 * @since BuddyPress 3.0.0
	 *
	 * @param array $templates Array of found templates.
	 */
	return bp_locate_template( apply_filters( 'bp_nouveau_group_locate_template_part', $templates ), false, true );
}

/**
 * Load a single group template part
 *
 * @since BuddyPress 3.0.0
 *
 * @param string $template Optional. The template part to get (eg: activity, members...).
 *
 * @return string HTML output.
 */
function bp_nouveau_group_get_template_part( $template = '' ) {
	$located = bp_nouveau_group_locate_template_part( $template );

	if ( false !== $located ) {
		$slug = str_replace( '.php', '', $located );
		$name = null;

		/**
		 * Let plugins adding an action to bp_get_template_part get it from here.
		 *
		 * This is a variable hook that is dependent on the template part slug.
		 *
		 * @since BuddyPress 3.0.0
		 *
		 * @param string $slug Template part slug requested.
		 * @param string $name Template part name requested.
		 */
		do_action( 'get_template_part_' . $slug, $slug, $name );

		load_template( $located, true );
	}

	return $located;
}

/**
 * Are we inside the Current group's default front page sidebar?
 *
 * @since BuddyPress 3.0.0
 *
 * @return bool True if in the group's home sidebar. False otherwise.
 */
function bp_nouveau_group_is_home_widgets() {
	return ( true === bp_nouveau()->groups->is_group_home_sidebar );
}

/**
 * Filter the Latest activities Widget to only keep the one of the group displayed
 *
 * @since BuddyPress 3.0.0
 *
 * @param array $args Optional. The Activities Template arguments.
 *
 * @return array The Activities Template arguments.
 */
function bp_nouveau_group_activity_widget_overrides( $args = array() ) {
	return array_merge( $args, array(
		'object'     => 'groups',
		'primary_id' => bp_get_current_group_id(),
	) );
}

/**
 * Filter the Groups widget to only keep the displayed group.
 *
 * @since BuddyPress 3.0.0
 *
 * @param array $args Optional. The Groups Template arguments.
 *
 * @return array The Groups Template arguments.
 */
function bp_nouveau_group_groups_widget_overrides( $args = array() ) {
	return array_merge( $args, array(
		'include' => bp_get_current_group_id(),
	) );
}

/**
 * Filter the Members widgets to only keep members of the displayed group.
 *
 * @since BuddyPress 3.0.0
 *
 * @param array $args Optional. The Members Template arguments.
 *
 * @return array The Members Template arguments.
 */
function bp_nouveau_group_members_widget_overrides( $args = array() ) {
	$group_members = groups_get_group_members( array( 'exclude_admins_mods' => false ) );

	if ( empty( $group_members['members'] ) ) {
		return $args;
	}

	return array_merge( $args, array(
		'include' => wp_list_pluck( $group_members['members'], 'ID' ),
	) );
}

/**
 * Init the Group's default front page filters as we're in the sidebar
 *
 * @since BuddyPress 3.0.0
 */
function bp_nouveau_groups_add_home_widget_filters() {
	add_filter( 'bp_nouveau_activity_widget_query', 'bp_nouveau_group_activity_widget_overrides', 10, 1 );
	add_filter( 'bp_before_has_groups_parse_args', 'bp_nouveau_group_groups_widget_overrides', 10, 1 );
	add_filter( 'bp_before_has_members_parse_args', 'bp_nouveau_group_members_widget_overrides', 10, 1 );

	/**
	 * Fires after BuddyPress Nouveau groups have added their home widget filters.
	 *
	 * @since BuddyPress 3.0.0
	 */
	do_action( 'bp_nouveau_groups_add_home_widget_filters' );
}

/**
 * Remove the Group's default front page filters as we're no more in the sidebar
 *
 * @since BuddyPress 3.0.0
 */
function bp_nouveau_groups_remove_home_widget_filters() {
	remove_filter( 'bp_nouveau_activity_widget_query', 'bp_nouveau_group_activity_widget_overrides', 10, 1 );
	remove_filter( 'bp_before_has_groups_parse_args', 'bp_nouveau_group_groups_widget_overrides', 10, 1 );
	remove_filter( 'bp_before_has_members_parse_args', 'bp_nouveau_group_members_widget_overrides', 10, 1 );

	/**
	 * Fires after BuddyPress Nouveau groups have removed their home widget filters.
	 *
	 * @since BuddyPress 3.0.0
	 */
	do_action( 'bp_nouveau_groups_remove_home_widget_filters' );
}

/**
 * Get the hook, nonce, and eventually a specific template for Core Group's create screens.
 *
 * @since BuddyPress 3.0.0
 *
 * @param string $id Optional. The screen id
 *
 * @return mixed An array containing the hook dynamic part, the nonce, and eventually a specific template.
 *               False if it's not a core create screen.
 */
function bp_nouveau_group_get_core_create_screens( $id = '' ) {
	// screen id => dynamic part of the hooks, nonce & specific template to use.
	$screens = array(
		'group-details' => array(
			'hook'     => 'group_details_creation_step',
			'nonce'    => 'groups_create_save_group-details',
			'template' => 'groups/single/admin/edit-details',
		),
		'group-settings' => array(
			'hook'  => 'group_settings_creation_step',
			'nonce' => 'groups_create_save_group-settings',
		),
		'group-avatar' => array(
			'hook'  => 'group_avatar_creation_step',
			'nonce' => 'groups_create_save_group-avatar',
		),
		'group-cover-image' => array(
			'hook'  => 'group_cover_image_creation_step',
			'nonce' => 'groups_create_save_group-cover-image',
		),
		'group-invites' => array(
			'hook'     => 'group_invites_creation_step',
			'nonce'    => 'groups_create_save_group-invites',
			'template' => 'groups/single/invite/send-invites',
		),
	);

	if ( isset( $screens[ $id ] ) ) {
		return $screens[ $id ];
	}

	return false;
}

/**
 * Get the hook and nonce for Core Group's manage screens.
 *
 * @since BuddyPress 3.0.0
 *
 * @param string $id Optional. The screen id
 *
 * @return mixed An array containing the hook dynamic part and the nonce.
 *               False if it's not a core manage screen.
 */
function bp_nouveau_group_get_core_manage_screens( $id = '' ) {
	// screen id => dynamic part of the hooks & nonce.
	$screens = array(
		'edit-details'        => array( 'hook' => 'group_details_admin',             'nonce' => 'groups_edit_group_details'  ),
		'group-settings'      => array( 'hook' => 'group_settings_admin',            'nonce' => 'groups_edit_group_settings' ),
		'group-avatar'        => array(),
		'group-cover-image'   => array( 'hook' => 'group_settings_cover_image',      'nonce' => ''                           ),
		'manage-members'      => array( 'hook' => 'group_manage_members_admin',      'nonce' => ''                           ),
		'membership-requests' => array( 'hook' => 'group_membership_requests_admin', 'nonce' => ''                           ),
		'delete-group'        => array( 'hook' => 'group_delete_admin',              'nonce' => 'groups_delete_group'        ),
	);

	if ( isset( $screens[ $id ] ) ) {
		return $screens[ $id ];
	}

	return false;
}

/**
 * Register notifications filters for the groups component.
 *
 * @since BuddyPress 3.0.0
 */
function bp_nouveau_groups_notification_filters() {

	if ( ! bb_enabled_legacy_email_preference() ) {
		return;
	}

	$notifications = array(
		array(
			'id'       => 'new_membership_request',
			'label'    => __( 'Pending Group membership requests', 'buddyboss' ),
			'position' => 55,
		),
		array(
			'id'       => 'membership_request_accepted',
			'label'    => __( 'Accepted Group membership requests', 'buddyboss' ),
			'position' => 65,
		),
		array(
			'id'       => 'membership_request_rejected',
			'label'    => __( 'Rejected Group membership requests', 'buddyboss' ),
			'position' => 75,
		),
		array(
			'id'       => 'member_promoted_to_admin',
			'label'    => __( 'Group Organizer promotions', 'buddyboss' ),
			'position' => 85,
		),
		array(
			'id'       => 'member_promoted_to_mod',
			'label'    => __( 'Group Moderator promotions', 'buddyboss' ),
			'position' => 95,
		),
		array(
			'id'       => 'group_invite',
			'label'    => __( 'Group invitations', 'buddyboss' ),
			'position' => 105,
		),
	);

	foreach ( $notifications as $notification ) {
		bp_nouveau_notifications_register_filter( $notification );
	}
}

function bp_nouveau_group_pending_invites_set_page_title( $title ){

	global $bp;
	$new_title = '';

	if ( 'pending-invites' === bp_get_group_current_invite_tab() ) {
		$new_title = esc_html__( 'Pending Invites', 'buddyboss' );
	}

	if( strlen( $new_title ) > 0 ) {
		$title['title'] = $new_title;
	}

	return $title;
}

//Update title on Buddypress sub pages
function bp_nouveau_group_pending_invites_set_title_tag( $title ){

	global $bp;
	$new_title = "";

	if ( 'pending-invites' === bp_get_group_current_invite_tab() ) {
		$new_title         = esc_html__( 'Pending Invites', 'buddyboss' );
		$sep               = apply_filters( 'document_title_separator', '-' );
		$get_current_group = bp_get_current_group_name();

		$new_title = $new_title . ' ' . $sep . ' ' . $get_current_group . ' ' . $sep . ' ' . bp_get_site_name();
	}

	//Combine the new title with the old (separator and tagline)
	if( strlen($new_title) > 0 ){
		$title = $new_title . " " . $title;
	}

	return $title;
}

/**
 * Localize the strings needed for the Group's Message UI.
 *
 * @since BuddyBoss 1.2.9
 *
 * @param array $params Associative array containing the JS Strings needed by scripts
 *
 * @return array The same array with specific strings for the Group's Message UI if needed.
 */
function bp_nouveau_groups_messages_localize_scripts( $params = array() ) {

	if ( false === bp_disable_group_messages() ) {
		return $params;
	}

	$params['group_messages'] = array(
		'page'                  => 1,
		'type_message'          => __( 'Type message', 'buddyboss' ),
		'group_no_member'       => __( 'There are no other members in this group. Please add some members before sending a message.', 'buddyboss' ),
		'group_no_member_pro'   => __( 'You are not allowed to send private messages to any member of this group.', 'buddyboss' ),
		'loading'               => __( 'Loading members. Please wait.', 'buddyboss' ),
		'remove_recipient'      => __( 'Remove Member', 'buddyboss' ),
		'add_recipient'         => __( 'Add Member', 'buddyboss' ),
		'no_content'            => __( 'Please add some content to your message.', 'buddyboss' ),
		'no_recipient'          => __( 'Please add at least one recipient.', 'buddyboss' ),
		'select_default_text'   => __( 'All Group Members', 'buddyboss' ),
		'select_default_value'  => __( 'all', 'buddyboss' ),
		'no_member'             => __( 'No members were found. Try another filter.', 'buddyboss' ),
		'invites_form_all'      => __( 'This message will be delivered to all members of this group you can message.', 'buddyboss' ),
		'invites_form_separate' => __( 'Select group members to message by clicking the + button next to each member. Once you\'ve made a selection, click "Send Message" to create a new group message.', 'buddyboss' ),
		'invites_form_reset'    => __( 'Group invitations cleared. Please use one of the available tabs to select members to invite.', 'buddyboss' ),
		'invites_sending'       => __( 'Sending group invitations. Please wait.', 'buddyboss' ),
		'removeUserInvite'      => __( 'Cancel invitation %s', 'buddyboss' ),
		'feedback_select_all'   => __( 'This message will be delivered to all members of this group you can message.', 'buddyboss' ),
		'feedback_individual'   => __( 'Select individual recipients by clicking the + button next to each member.', 'buddyboss' ),
		'group_id'              => ! bp_get_current_group_id() ? bp_get_new_group_id() : bp_get_current_group_id(),
		'is_group_create'       => bp_is_group_create(),
		'nonces'                => array(
			'unmessage'              => wp_create_nonce( 'groups_message_unmessage_user' ),
			'send_messages'          => wp_create_nonce( 'groups_send_messages' ),
			'retrieve_group_members' => wp_create_nonce( 'retrieve_group_members' ),
			'send_messages_users'    => wp_create_nonce( 'send_messages_users' ),
		),
	);

	return $params;
}

/**
 * Enqueue the groups scripts
 *
 * @since BuddyBoss 1.2.9
 */
function bp_nouveau_groups_messages_enqueue_scripts() {

	if ( false === bp_disable_group_messages() ) {
		return;
	}

	if ( bp_is_group_messages() ) {
		wp_enqueue_script( 'bp-select2' );
		wp_enqueue_script( 'bp-medium-editor' );
		wp_enqueue_style( 'bp-medium-editor' );
		wp_enqueue_style( 'bp-medium-editor-beagle' );
		wp_enqueue_script( 'bp-nouveau-group-messages' );
	}
}
