<?php

/**
 * Forums Capabilites
 *
 * The functions in this file are used primarily as convenient wrappers for
 * capability output in user profiles. This includes mapping capabilities and
 * groups to human readable strings,
 *
 * @package BuddyBoss\Capabilities
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/** Mapping *******************************************************************/

/**
 * Returns an array of capabilities based on the role that is being requested.
 *
 * @since bbPress (r2994)
 *
 * @todo Map all of these and deprecate
 *
 * @param string $role Optional. Defaults to The role to load caps for
 * @uses apply_filters() Allow return value to be filtered
 *
 * @return array Capabilities for $role
 */
function bbp_get_caps_for_role( $role = '' ) {

	// Which role are we looking for?
	switch ( $role ) {

		// Keymaster
		case bbp_get_keymaster_role():
			$caps = array(

				// Keymasters only
				'keep_gate'             => true,

				// Primary caps
				'spectate'              => true,
				'participate'           => true,
				'moderate'              => true,
				'throttle'              => true,
				'view_trash'            => true,

				// Forum caps
				'publish_forums'        => true,
				'edit_forums'           => true,
				'edit_others_forums'    => true,
				'delete_forums'         => true,
				'delete_others_forums'  => true,
				'read_private_forums'   => true,
				'read_hidden_forums'    => true,

				// Topic caps
				'publish_topics'        => true,
				'edit_topics'           => true,
				'edit_others_topics'    => true,
				'delete_topics'         => true,
				'delete_others_topics'  => true,
				'read_private_topics'   => true,

				// Reply caps
				'publish_replies'       => true,
				'edit_replies'          => true,
				'edit_others_replies'   => true,
				'delete_replies'        => true,
				'delete_others_replies' => true,
				'read_private_replies'  => true,

				// Topic tag caps
				'manage_topic_tags'     => true,
				'edit_topic_tags'       => true,
				'delete_topic_tags'     => true,
				'assign_topic_tags'     => true,
			);

			break;

		// Moderator
		case bbp_get_moderator_role():
			$caps = array(

				// Primary caps
				'spectate'              => true,
				'participate'           => true,
				'moderate'              => true,
				'throttle'              => true,
				'view_trash'            => true,

				// Forum caps
				'publish_forums'        => true,
				'edit_forums'           => true,
				'read_private_forums'   => true,
				'read_hidden_forums'    => true,

				// Topic caps
				'publish_topics'        => true,
				'edit_topics'           => true,
				'edit_others_topics'    => true,
				'delete_topics'         => true,
				'delete_others_topics'  => true,
				'read_private_topics'   => true,

				// Reply caps
				'publish_replies'       => true,
				'edit_replies'          => true,
				'edit_others_replies'   => true,
				'delete_replies'        => true,
				'delete_others_replies' => true,
				'read_private_replies'  => true,

				// Topic tag caps
				'manage_topic_tags'     => true,
				'edit_topic_tags'       => true,
				'delete_topic_tags'     => true,
				'assign_topic_tags'     => true,
			);

			break;

		// Spectators can only read
		case bbp_get_spectator_role():
			$caps = array(

				// Primary caps
				'spectate' => true,
			);

			break;

		// Explicitly blocked
		case bbp_get_blocked_role():
			$caps = array(

				// Primary caps
				'spectate'              => false,
				'participate'           => false,
				'moderate'              => false,
				'throttle'              => false,
				'view_trash'            => false,

				// Forum caps
				'publish_forums'        => false,
				'edit_forums'           => false,
				'edit_others_forums'    => false,
				'delete_forums'         => false,
				'delete_others_forums'  => false,
				'read_private_forums'   => false,
				'read_hidden_forums'    => false,

				// Topic caps
				'publish_topics'        => false,
				'edit_topics'           => false,
				'edit_others_topics'    => false,
				'delete_topics'         => false,
				'delete_others_topics'  => false,
				'read_private_topics'   => false,

				// Reply caps
				'publish_replies'       => false,
				'edit_replies'          => false,
				'edit_others_replies'   => false,
				'delete_replies'        => false,
				'delete_others_replies' => false,
				'read_private_replies'  => false,

				// Topic tag caps
				'manage_topic_tags'     => false,
				'edit_topic_tags'       => false,
				'delete_topic_tags'     => false,
				'assign_topic_tags'     => false,
			);

			break;

		// Participant/Default
		case bbp_get_participant_role():
		default:
			$caps = array(

				// Primary caps
				'spectate'            => true,
				'participate'         => true,

				// Forum caps
				'read_private_forums' => true,

				// Topic caps
				'publish_topics'      => true,
				'edit_topics'         => true,

				// Reply caps
				'publish_replies'     => true,
				'edit_replies'        => true,

				// Topic tag caps
				'assign_topic_tags'   => true,
			);

			break;
	}

	return apply_filters( 'bbp_get_caps_for_role', $caps, $role );
}

/**
 * Adds capabilities to WordPress user roles.
 *
 * @since bbPress (r2608)
 */
function bbp_add_caps() {

	// Loop through available roles and add caps
	foreach ( bbp_get_wp_roles()->role_objects as $role ) {
		foreach ( bbp_get_caps_for_role( $role->name ) as $cap => $value ) {
			$role->add_cap( $cap, $value );
		}
	}

	do_action( 'bbp_add_caps' );
}

/**
 * Removes capabilities from WordPress user roles.
 *
 * @since bbPress (r2608)
 */
function bbp_remove_caps() {

	// Loop through available roles and remove caps
	foreach ( bbp_get_wp_roles()->role_objects as $role ) {
		foreach ( array_keys( bbp_get_caps_for_role( $role->name ) ) as $cap ) {
			$role->remove_cap( $cap );
		}
	}

	do_action( 'bbp_remove_caps' );
}

/**
 * Get the $wp_roles global without needing to declare it everywhere
 *
 * @since bbPress (r4293)
 *
 * @return WP_Roles
 */
function bbp_get_wp_roles() {
	$wp_roles = bbp_get_global_object( 'wp_roles', 'WP_Roles' );

	// Set roles if not loaded
	if ( empty( $wp_roles ) ) {
		$wp_roles = $GLOBALS['wp_roles'] = new WP_Roles();
	}

	return $wp_roles;
}

/**
 * Get the available roles minus Forums' dynamic roles
 *
 * @since bbPress (r5064)
 *
 * @uses bbp_get_wp_roles() To load and get the $wp_roles global
 * @return array
 */
function bbp_get_blog_roles() {

	// Get WordPress's roles (returns $wp_roles global)
	$wp_roles = bbp_get_wp_roles();

	// Apply the WordPress 'editable_roles' filter to let plugins ride along.
	//
	// We use this internally via bbp_filter_blog_editable_roles() to remove
	// any custom Forums roles that are added to the global.
	$the_roles = isset( $wp_roles->roles ) ? $wp_roles->roles : false;
	$all_roles = apply_filters( 'editable_roles', $the_roles );

	return apply_filters( 'bbp_get_blog_roles', $all_roles, $wp_roles );
}

/** Forum Roles ***************************************************************/

/**
 * Add the Forums roles to the $wp_roles global.
 *
 * We do this to avoid adding these values to the database.
 *
 * @since bbPress (r4290)
 *
 * @param WP_Roles $wp_roles The main WordPress roles global
 *
 * @uses bbp_get_wp_roles() To load and get the $wp_roles global
 * @uses bbp_get_dynamic_roles() To get and add Forums' roles to $wp_roles
 * @return WP_Roles The main $wp_roles global
 */
function bbp_add_forums_roles( $wp_roles = null ) {

	// Attempt to get global roles if not passed in & not mid-initialization
	if ( ( null === $wp_roles ) && ! doing_action( 'wp_roles_init' ) ) {
		$wp_roles = bbp_get_wp_roles();
	}

	// Loop through dynamic roles and add them to the $wp_roles array
	foreach ( bbp_get_dynamic_roles() as $role_id => $details ) {
		$wp_roles->roles[ $role_id ]        = $details;
		$wp_roles->role_objects[ $role_id ] = new WP_Role( $role_id, $details['capabilities'] );
		$wp_roles->role_names[ $role_id ]   = $details['name'];
	}

	// Return the modified $wp_roles array
	return $wp_roles;
}

/**
 * Helper function to add filter to option_wp_user_roles
 *
 * @since bbPress (r4363)
 *
 * @see _bbp_reinit_dynamic_roles()
 *
 */
function bbp_filter_user_roles_option() {

	$role_key = bbp_db()->prefix . 'user_roles';

	add_filter( 'option_' . $role_key, '_bbp_reinit_dynamic_roles' );
}

/**
 * This is necessary because in a few places (noted below) WordPress initializes
 * a blog's roles directly from the database option. When this happens, the
 * $wp_roles global gets flushed, causing a user to magically lose any
 * dynamically assigned roles or capabilities when $current_user in refreshed.
 *
 * Because dynamic multiple roles is a new concept in WordPress, we work around
 * it here for now, knowing that improvements will come to WordPress core later.
 *
 * Also note that if using the $wp_user_roles global non-database approach,
 * Forums does not have an intercept point to add its dynamic roles.
 *
 * @see switch_to_blog()
 * @see restore_current_blog()
 * @see WP_Roles::_init()
 *
 * @since bbPress (r4363)
 *
 * @internal Used by Forums to reinitialize dynamic roles on blog switch
 *
 * @param array $roles
 * @return array Combined array of database roles and dynamic Forums roles
 */
function _bbp_reinit_dynamic_roles( $roles = array() ) {
	foreach ( bbp_get_dynamic_roles() as $role_id => $details ) {
		$roles[ $role_id ] = $details;
	}
	return $roles;
}

/**
 * Fetch a filtered list of forum roles that the current user is
 * allowed to have.
 *
 * Simple function who's main purpose is to allow filtering of the
 * list of forum roles so that plugins can remove inappropriate ones depending
 * on the situation or user making edits.
 *
 * Specifically because without filtering, anyone with the edit_users
 * capability can edit others to be administrators, even if they are
 * only editors or authors. This filter allows admins to delegate
 * user management.
 *
 * @since bbPress (r4284)
 *
 * @return array
 */
function bbp_get_dynamic_roles() {
	return (array) apply_filters(
		'bbp_get_dynamic_roles',
		array(

			// Keymaster
			bbp_get_keymaster_role()   => array(
				'name'         => 'Keymaster',
				'capabilities' => bbp_get_caps_for_role( bbp_get_keymaster_role() ),
			),

			// Moderator
			bbp_get_moderator_role()   => array(
				'name'         => 'Moderator',
				'capabilities' => bbp_get_caps_for_role( bbp_get_moderator_role() ),
			),

			// Participant
			bbp_get_participant_role() => array(
				'name'         => 'Participant',
				'capabilities' => bbp_get_caps_for_role( bbp_get_participant_role() ),
			),

			// Spectator
			bbp_get_spectator_role()   => array(
				'name'         => 'Spectator',
				'capabilities' => bbp_get_caps_for_role( bbp_get_spectator_role() ),
			),

			// Blocked
			bbp_get_blocked_role()     => array(
				'name'         => 'Blocked',
				'capabilities' => bbp_get_caps_for_role( bbp_get_blocked_role() ),
			),
		)
	);
}

/**
 * Gets a translated role name from a role ID
 *
 * @since bbPress (r4792)
 *
 * @param string $role_id
 * @return string Translated role name
 */
function bbp_get_dynamic_role_name( $role_id = '' ) {
	$roles = bbp_get_dynamic_roles();
	$role  = isset( $roles[ $role_id ] ) ? bbp_translate_user_role( $roles[ $role_id ]['name'] ) : '';

	return apply_filters( 'bbp_get_dynamic_role_name', $role, $role_id, $roles );
}

/**
 * Removes the Forums roles from the editable roles array
 *
 * This used to use array_diff_assoc() but it randomly broke before 2.2 release.
 * Need to research what happened, and if there's a way to speed this up.
 *
 * @since bbPress (r4303)
 *
 * @param array $all_roles All registered roles
 * @return array
 */
function bbp_filter_blog_editable_roles( $all_roles = array() ) {

	// Loop through Forums roles
	foreach ( array_keys( bbp_get_dynamic_roles() ) as $bbp_role ) {

		// Loop through WordPress roles
		foreach ( array_keys( $all_roles ) as $wp_role ) {

			// If keys match, unset
			if ( $wp_role === $bbp_role ) {
				unset( $all_roles[ $wp_role ] );
			}
		}
	}

	return $all_roles;
}

/**
 * The keymaster role for Forums users
 *
 * @since bbPress (r4284)
 *
 * @uses apply_filters() Allow override of hardcoded keymaster role
 * @return string
 */
function bbp_get_keymaster_role() {
	return apply_filters( 'bbp_get_keymaster_role', 'bbp_keymaster' );
}

/**
 * The moderator role for Forums users
 *
 * @since bbPress (r3410)
 *
 * @uses apply_filters() Allow override of hardcoded moderator role
 * @return string
 */
function bbp_get_moderator_role() {
	return apply_filters( 'bbp_get_moderator_role', 'bbp_moderator' );
}

/**
 * The participant role for registered user that can participate in forums
 *
 * @since bbPress (r3410)
 *
 * @uses apply_filters() Allow override of hardcoded participant role
 * @return string
 */
function bbp_get_participant_role() {
	return apply_filters( 'bbp_get_participant_role', 'bbp_participant' );
}

/**
 * The spectator role is for registered users without any capabilities
 *
 * @since bbPress (r3860)
 *
 * @uses apply_filters() Allow override of hardcoded spectator role
 * @return string
 */
function bbp_get_spectator_role() {
	return apply_filters( 'bbp_get_spectator_role', 'bbp_spectator' );
}

/**
 * The blocked role is for registered users that cannot spectate or participate
 *
 * @since bbPress (r4284)
 *
 * @uses apply_filters() Allow override of hardcoded blocked role
 * @return string
 */
function bbp_get_blocked_role() {
	return apply_filters( 'bbp_get_blocked_role', 'bbp_blocked' );
}

/** Deprecated ****************************************************************/

/**
 * Adds Forums-specific user roles.
 *
 * @since bbPress (r2741)
 * @deprecated since version 2.2
 */
function bbp_add_roles() {
	_doing_it_wrong( 'bbp_add_roles', __( 'Editable forum roles no longer exist.', 'buddyboss' ), '2.2' );
}

/**
 * Removes bbPress-specific user roles from the `wp_user_roles` array.
 *
 * This is currently only used when updating, uninstalling, or resetting bbPress.
 *
 * @since bbPress (r2741)
 *
 * @see   bbp_do_uninstall()
 * @see   bbp_version_updater()
 * @see   bbp_admin_reset_handler()
 */
function bbp_remove_roles() {

	// Remove the Forums roles
	foreach ( array_keys( bbp_get_dynamic_roles() ) as $bbp_role ) {
		remove_role( $bbp_role );
	}

	// Some early adopters may have a deprecated visitor role. It was later
	// replaced by the Spectator role.
	remove_role( 'bbp_visitor' );
}

/**
 * Map forum capabilities to WP roles
 *
 * @since  BuddyBoss 1.0.0
 */
function bbp_map_caps_to_wp_roles() {
	$wp_bbp_role_map = array(
		'administrator' => 'bbp_keymaster',
		'editor'        => 'bbp_moderator',
	);

	foreach ( $wp_bbp_role_map as $wp_role => $bbp_role ) {
		$wp_role_object = get_role( $wp_role );

		if ( empty( $wp_role_object ) ) { // check if role exists if not then continue
			continue;
		}

		$bbp_caps = bbp_get_caps_for_role( $bbp_role );

		foreach ( array_keys( $bbp_caps ) as $cap ) {
			$wp_role_object->add_cap( $cap );
		}
	}
}

/**
 * Restore forum capabilities from WP roles
 *
 * @since  BuddyBoss 1.0.0
 */
function bbp_restore_caps_from_wp_roles() {
	$wp_bbp_role_map = array(
		'administrator' => 'bbp_keymaster',
		'editor'        => 'bbp_moderator',
	);

	foreach ( $wp_bbp_role_map as $wp_role => $bbp_role ) {
		$wp_role_object = get_role( $wp_role );

		if ( empty( $wp_role_object ) ) { // check if role exists if not then continue
			continue;
		}

		$bbp_caps = bbp_get_caps_for_role( $bbp_role );

		foreach ( array_keys( $bbp_caps ) as $cap ) {
			$wp_role_object->remove_cap( $cap );
		}
	}

	return array(
		'status'  => 1,
		'message' => __( 'Complete!', 'buddyboss' ),
	);
}
