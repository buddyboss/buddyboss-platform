<?php
/**
 * BuddyBoss Media group photos functions
 *
 * Functions for handling media in buddypress groups.
 *
 * @author     BuddyBoss
 * @category   Core
 * @package    BuddyBoss/Functions
 */

/**
 * Is the current page part of a single group's media screens?
 *
 * Eg http://example.com/groups/mygroup/photos/uploads/.
 *
 * @return bool True if the current page is part of a single group's page.
 */
function bbm_is_group_media_page() {
	$component_slug = buddyboss_media_component_slug();
	return (bool) ( bp_is_single_item() && bp_is_groups_component() && bp_is_current_action( $component_slug ) );
}

/**
 * HTML admin subnav items for group media page
 *
 * @param object|bool $group Optional. Group object.
 *                           Default: current group in the loop.
 */
function bbm_group_media_tabs( $group = false ) {
	global $groups_template;

	if ( empty( $group ) ) {
		$group = ( $groups_template->group ) ? $groups_template->group : groups_get_current_group();
	}

	$css_id = 'manage-members';

	if ( 'private' == $group->status ) {
		$css_id = 'membership-requests';
	}

	add_filter( "bp_get_options_nav_{$css_id}", 'bp_group_admin_tabs_backcompat', 10, 3 );

	bp_get_options_nav( $group->slug . '_photos' );

	remove_filter( "bp_get_options_nav_{$css_id}", 'bp_group_admin_tabs_backcompat', 10, 3 );
}

/**
 * Is the current page a specific group admin screen?
 *
 * @since 1.1.0
 *
 * @param string $slug Admin screen slug.
 * @return bool
 */
function bbm_is_group_media_screen( $slug = '' ) {
	return (bool) ( bbm_is_group_media_page() && bp_is_action_variable( $slug ) );
}

/**
 * Output the 'checked' value, if needed, for a given media_status on the group create/admin screens
 *
 * @since 1.5.0
 *
 * @param string      $setting The setting you want to check against ('members',
 *                             'mods', or 'admins').
 * @param object|bool $group   Optional. Group object. Default: current group in loop.
 */
function bbm_group_show_media_status_setting( $setting, $group = false ) {
	$group_id = isset( $group->id ) ? $group->id : false;

	$invite_status = bbm_group_get_media_status( $group_id );

	if ( $setting == $invite_status ) {
		echo ' checked="checked"';
	}
}

/**
 * Get the media status of a group.
 *
 * 'media_status' became part of BuddyPress in BP 1.5. In order to provide
 * backward compatibility with earlier installations, groups without a status
 * set will default to 'members', ie all members in a group can send
 * invitations. Filter 'bbm_group_get_media_status_fallback' to change this
 * fallback behavior.
 *
 * This function can be used either in or out of the loop.
 *
 * @param int|bool $group_id Optional. The ID of the group whose status you want to
 *                           check. Default: the displayed group, or the current group
 *                           in the loop.
 * @return bool|string Returns false when no group can be found. Otherwise
 *                     returns the group invite status, from among 'members',
 *                     'mods', and 'admins'.
 */
function bbm_group_get_media_status( $group_id = false ) {
	global $groups_template;

	if ( !$group_id ) {
		$bp = buddypress();

		if ( isset( $bp->groups->current_group->id ) ) {
			// Default to the current group first.
			$group_id = $bp->groups->current_group->id;
		} elseif ( isset( $groups_template->group->id ) ) {
			// Then see if we're in the loop.
			$group_id = $groups_template->group->id;
		} else {
			return false;
		}
	}

	$media_status = groups_get_groupmeta( $group_id, 'media_status' );

	// Backward compatibility. When 'invite_status' is not set, fall back to a default value.
	if ( ! $media_status ) {
		$media_status = apply_filters( 'bbm_group_media_status_fallback', 'members' );
	}

	/**
	 * Filters the media status of a group.
	 *
	 * Media status in this case means who from the group can send invites.
	 *
	 * @param string $invite_status Membership level needed to send an invite.
	 * @param int    $group_id      ID of the group whose status is being checked.
	 */
	return apply_filters( 'bbm_group_get_media_status', $media_status, $group_id );
}

/**
 * Can a user create albums in the specified group?
 *
 * @param int $group_id The group ID to check.
 * @param int $user_id  The user ID to check.
 * @return bool
 */
function bbm_groups_user_can_create_albums( $group_id = 0, $user_id = 0 ) {
	$can_create_albums = false;
	$media_status    = false;

	// If $user_id isn't specified, we check against the logged-in user.
	if ( ! $user_id ) {
		$user_id = bp_loggedin_user_id();
	}

	// If $group_id isn't specified, use existing one if available.
	if ( ! $group_id ) {
		$group_id = bp_get_current_group_id();
	}

	if ( $user_id ) {
		// Users with the 'bp_moderate' cap can always send invitations.
		if ( user_can( $user_id, 'bp_moderate' ) ) {
			$can_create_albums = true;
		} else {
			$media_status = bbm_group_get_media_status( $group_id );

			switch ( $media_status ) {
				case 'admins' :
					if ( groups_is_user_admin( $user_id, $group_id ) ) {
						$can_create_albums = true;
					}
					break;

				case 'mods' :
					if ( groups_is_user_mod( $user_id, $group_id ) || groups_is_user_admin( $user_id, $group_id ) ) {
						$can_create_albums = true;
					}
					break;

				case 'members' :
					if ( groups_is_user_member( $user_id, $group_id ) ) {
						$can_create_albums = true;
					}
					break;
			}
		}
	}

	/**
	 * Filters whether a user can create albums in a group.
	 *
	 * @param bool $can_create_albums   Whether the user can create albums
	 * @param int  $group_id            The group ID being checked
	 * @param bool $media_status        The group's current media status
	 * @param int  $user_id             The user ID being checked
	 */
	return apply_filters( 'bbm_groups_user_can_create_albums', $can_create_albums, $group_id, $media_status, $user_id );
}

/**
 * Show album delete button if user has permission to delete it
 * @param bool|false $album_id
 * @return string|void
 */
function bbm_group_media_btn_delete_album( $album_id = false ) {

	if ( ! $album_id ) {
		$album_id = buddyboss_media_album_get_id();
	}

	$can_delete_albums = bbm_groups_user_can_delete_albums( $album_id );

	if ( ! $can_delete_albums ) {
		return;
	}

	$group_link = bp_get_group_permalink( buddypress()->groups->current_group );
	$albums_url = trailingslashit( $group_link . buddyboss_media_component_slug() . '/albums' );

	$delete_album_url	 = esc_url( add_query_arg( 'delete', $album_id, $albums_url ) );
	$delete_album_url	 = esc_url( add_query_arg( 'nonce', wp_create_nonce( 'bboss_media_delete_album' ), $delete_album_url ) );

	$confimation_message = __( 'Are you sure you want to delete this album? When you delete an album, all its photos go under global uploads.', 'buddyboss-media' );

	$anchor = "<a class='button album-delete bp-title-button' href='" . esc_url( $delete_album_url ) . "' onclick='return confirm(\"" . $confimation_message . "\");' >" . __( 'Delete Album', 'buddyboss-media' ) . "</a>";
	echo apply_filters( 'bbm_group_media_btn_delete_album', $anchor, $album_id );
}

/**
 * Whether user has a capability or role for a group album delete
 * @param $album_id
 * @param int $group_id
 * @param int $user_id
 * @return mixed|void
 */
function bbm_groups_user_can_delete_albums( $album_id, $group_id = 0, $user_id = 0 ) {
	global $wpdb;
	$can_delete_albums = false;

	// If $user_id isn't specified, we check against the logged-in user.
	if ( ! $user_id ) {
		$user_id = bp_loggedin_user_id();
	}

	// If $group_id isn't specified, use existing one if available.
	if ( ! $group_id ) {
		$group_id = bp_get_current_group_id();
	}

	// Users with the wp 'admin' cap can always delete albums.
	if ( groups_is_user_admin( $user_id, $group_id ) ) {
		$can_delete_albums = true;

	// Users with the 'bp_moderate' cap can always delete album.
	} else if ( groups_is_user_mod( $user_id, $group_id  ) ) {
		$can_delete_albums = true;
	} else if ( groups_is_user_member( $user_id, $group_id ) ) {

		$album_author = $wpdb->get_var( $wpdb->prepare( "SELECT user_id FROM {$wpdb->prefix}buddyboss_media_albums WHERE id = %d", $album_id ) );

		if ( $album_author == $user_id ) {
			$can_delete_albums = true;
		}
	}

	return apply_filters( 'bbm_groups_user_can_delete_albums', $can_delete_albums );
}

/**
 * Retrieve photo count form group
 * @param int $group_id
 * @return array
 */
function bbm_groups_media_photo_count( $group_id ) {
	global $wpdb;

	$sql            = $wpdb->prepare("SELECT count(m.id) FROM {$wpdb->prefix}buddyboss_media m INNER JOIN {$wpdb->posts} p ON p.ID = m.media_id WHERE p.post_type = 'attachment' AND m.activity_id IN ( SELECT id FROM {$wpdb->base_prefix}bp_activity WHERE component = 'groups' AND item_id = %d )" , $group_id );
	$photo_count    = $wpdb->get_var( $sql );

	return $photo_count;
}

/**
 * Remove private/hidden group media from global media page
 * @param $group
 */
function bbm_change_sitewide_activity( $group ) {
	global $wpdb;

	//Set hide_sitewide = 1 for private/hidden groups activity
	$hide_sitewide = 'public' === $group->status ? 0 : 1;

	//Update hide_sitewide flag
	$wpdb->query("UPDATE {$wpdb->base_prefix}bp_activity SET hide_sitewide = {$hide_sitewide} WHERE component = 'groups' AND item_id = {$group->id}");
}

add_action( 'groups_group_after_save', 'bbm_change_sitewide_activity', 10, 1 );

add_action( 'wp_ajax_buddyboss_get_group_albums', 'bbm_get_group_albums_ajax_callback' );

/**
 * Fetch all the groups albums and send result in json
 */
function bbm_get_group_albums_ajax_callback() {
	global $wpdb;

	$activity_id = $_GET['activity_id'];

	// Find group id from activity id
	$query = $wpdb->prepare( "SELECT item_id FROM {$wpdb->base_prefix}bp_activity WHERE id = %d", $activity_id );

	$group_id = $wpdb->get_var( $query );
	$user_id = bp_loggedin_user_id();

	if ( ! empty( $group_id )
		&& groups_is_user_member( $user_id, $group_id ) ) { // Current user must be a member of group

		$albums	 = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT id, title FROM {$wpdb->prefix}buddyboss_media_albums WHERE group_id = %d AND user_id = %d",
				$group_id,
				$user_id
			)
		);

		wp_send_json_success( $albums );
	}

	wp_send_json_error();
}
