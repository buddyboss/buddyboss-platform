<?php
/**
 * BuddyBoss Invites Template Functions.
 *
 * @package BuddyBoss
 * @subpackage InvitesTemplates
 * @since BuddyBoss 3.1.1
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Output the invites component slug.
 *
 * @since BuddyPress 1.5.0
 */
function bp_invites_slug() {
	echo bp_get_invites_slug();
}
	/**
	 * Return the invites component slug.
	 *
	 * @since BuddyPress 1.5.0
	 *
	 * @return string
	 */
	function bp_get_invites_slug() {

		/**
		 * Filters the groups component slug.
		 *
		 * @since BuddyPress 1.5.0
		 *
		 * @param string $slug Groups component slug.
		 */
		return apply_filters( 'bp_get_invites_slug', buddypress()->invites->slug );
	}

/**
 * Output the groups component root slug.
 *
 * @since BuddyPress 1.5.0
 */
function bp_invites_root_slug() {
	echo bp_get_invites_root_slug();
}
	/**
	 * Return the groups component root slug.
	 *
	 * @since BuddyPress 1.5.0
	 *
	 * @return string
	 */
	function bp_get_invites_root_slug() {

		/**
		 * Filters the groups component root slug.
		 *
		 * @since BuddyPress 1.5.0
		 *
		 * @param string $root_slug Groups component root slug.
		 */
		return apply_filters( 'bp_get_invites_root_slug', buddypress()->invites->root_slug );
	}

