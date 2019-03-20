<?php
/**
 * BuddyBoss LearnDash integration group sync helpers
 * 
 * @package BuddyBoss\LearnDash
 * @since BuddyBoss 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Calls and returns global $learndash_buddypress_groups_sync
 *
 * @since BuddyBoss 1.0.0
 */
function bp_learndash_groups_sync() {
	global $learndash_buddypress_groups_sync;

	return $learndash_buddypress_groups_sync;
}
