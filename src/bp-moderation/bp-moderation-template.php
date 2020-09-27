<?php
/**
 * BuddyBoss Moderation Template Functions.
 *
 * @package BuddyBoss\Moderation
 * @since BuddyBoss 1.5.4
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Output the moderation component slug.
 *
 * @since BuddyBoss 1.5.4
 */
function bp_moderation_slug() {
	echo bp_get_moderation_slug();
}
	/**
	 * Return the moderation component slug.
	 *
	 * @since BuddyBoss 1.5.4
	 *
	 * @return string The moderation component slug.
	 */
function bp_get_moderation_slug() {

	/**
	 * Filters the moderation component slug.
	 *
	 * @since BuddyBoss 1.5.4
	 *
	 * @param string $slug Activity component slug.
	 */
	return apply_filters( 'bp_get_moderation_slug', buddypress()->moderation->slug );
}

/**
 * Output the moderation component root slug.
 *
 * @since BuddyBoss 1.5.4
 */
function bp_moderation_root_slug() {
	echo bp_get_moderation_root_slug();
}
	/**
	 * Return the moderation component root slug.
	 *
	 * @since BuddyBoss 1.5.4
	 *
	 * @return string The moderation component root slug.
	 */
function bp_get_moderation_root_slug() {

	/**
	 * Filters the moderation component root slug.
	 *
	 * @since BuddyBoss 1.5.4
	 *
	 * @param string $root_slug Activity component root slug.
	 */
	return apply_filters( 'bp_get_moderation_root_slug', buddypress()->moderation->root_slug );
}


