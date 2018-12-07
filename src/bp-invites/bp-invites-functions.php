<?php
/**
 * BuddyBoss Groups Functions.
 *
 * Functions are where all the magic happens in BuddyPress. They will
 * handle the actual saving or manipulation of information. Usually they will
 * hand off to a database class for data access, then return
 * true or false on success or failure.
 *
 * @package BuddyBoss
 * @subpackage GroupsFunctions
 * @since BuddyPress 1.5.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;



/**
 * Returns the name of the group type post type.
 *
 * @since BuddyBoss 3.1.1
 *
 * @return string The name of the group type post type.
 */
function bp_get_invite_post_type() {

	/**
	 * Filters the name of the group type post type.
	 *
	 * @since BuddyBoss 3.1.1
	 *
	 * @param string $value group Type post type name.
	 */
	return apply_filters( 'bp_get_invite_post_type', buddypress()->invite_post_type );
}

/**
 * Return labels used by the group type post type.
 *
 * @since BuddyBoss 3.1.1
 *
 * @return array
 */
function bp_get_invite_post_type_labels() {

	/**
	 * Filters group type post type labels.
	 *
	 * @since BuddyBoss 3.1.1
	 *
	 * @param array $value Associative array (name => label).
	 */
	return apply_filters( 'bp_get_invite_post_type_labels', array(
		'add_new_item'          => _x( 'New Sent Invite', 'group type post type label', 'buddyboss' ),
		'all_items'             => _x( 'Sent Invites', 'invite post type label', 'buddyboss' ),
		'edit_item'             => _x( 'Edit Sent Invite', 'invite post type label', 'buddyboss' ),
		'menu_name'             => _x( 'Invites', 'invite post type name', 'buddyboss' ),
		'name'                  => _x( 'Sent Invites', 'invite post type label', 'buddyboss' ),
		'new_item'              => _x( 'New Sent Invite', 'invite post type label', 'buddyboss' ),
		'not_found'             => _x( 'No Sent Invites found', 'invite post type label', 'buddyboss' ),
		'not_found_in_trash'    => _x( 'No Sent Invites found in trash', 'invite post type label', 'buddyboss' ),
		'search_items'          => _x( 'Search Sent Invite', 'invite post type label', 'buddyboss' ),
		'singular_name'         => _x( 'Sent Invite', 'invite post type singular name', 'buddyboss' ),
	) );

}

/**
 * Return array of features that the group type post type supports.
 *
 * @since BuddyBoss 3.1.1
 *
 * @return array
 */
function bp_get_invite_post_type_supports() {

	/**
	 * Filters the features that the group type post type supports.
	 *
	 * @since BuddyBoss 3.1.1
	 *
	 * @param array $value Supported features.
	 */
	return apply_filters( 'bp_get_invite_post_type_supports', array(
		'editor',
		'page-attributes',
		'title',
	) );
}
