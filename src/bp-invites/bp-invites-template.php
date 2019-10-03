<?php
/**
 * BuddyBoss Invites Template Functions.
 *
 * @package BuddyBoss\Invites\Templates
 * @since BuddyBoss 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Output the invites component slug.
 *
 * @since BuddyBoss 1.0.0
 */
function bp_invites_slug() {
	echo bp_get_invites_slug();
}
	/**
	 * Return the invites component slug.
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @return string
	 */
function bp_get_invites_slug() {

	/**
	 * Filters the invites component slug.
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @param string $slug Invites component slug.
	 */
	return apply_filters( 'bp_get_invites_slug', buddypress()->invites->slug );
}

/**
 * Output the invites component root slug.
 *
 * @since BuddyBoss 1.0.0
 */
function bp_invites_root_slug() {
	echo bp_get_invites_root_slug();
}
	/**
	 * Return the invites component root slug.
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @return string
	 */
function bp_get_invites_root_slug() {

	/**
	 * Filters the invites component root slug.
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @param string $root_slug Invites component root slug.
	 */
	return apply_filters( 'bp_get_invites_root_slug', buddypress()->invites->root_slug );
}

