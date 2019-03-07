<?php
/**
 * LearnDash integration group sync helpers
 * 
 * @package BuddyBoss\LearnDash
 * @since BuddyBoss 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Returns LearnDash path.
 *
 * @since BuddyBoss 1.0.0
 */
function bp_learndash_path($path = '') {
    return trailingslashit( buddypress()->integrations['learndash']->path ) . trim($path, '/\\');
}

/**
 * Returns LearnDash url.
 *
 * @since BuddyBoss 1.0.0
 */
function bp_learndash_url($path = '') {
    return trailingslashit( buddypress()->integrations['learndash']->url ) . trim($path, '/\\');
}

/**
 * Return specified BuddyBoss LearnDash sync component.
 *
 * @since BuddyBoss 1.0.0
 */
function bp_ld_sync($component = null) {
	global $bp_ld_sync;
	return $component ? $bp_ld_sync->$component : $bp_ld_sync;
}

/**
 * Return array of LearnDash group courses.
 *
 * @since BuddyBoss 1.0.0
 */
function bp_learndash_get_group_courses($bpGroupId) {
	$generator = bp_ld_sync('buddypress')->sync->generator($bpGroupId);

	if (! $generator->hasLdGroup()) {
		return [];
	}

	return learndash_group_enrolled_courses($generator->getLdGroupId());
}

// forward compatibility
if (! function_exists('learndash_get_post_type_slug')) {
	/**
	 * Returns array of slugs used by LearnDash integration.
	 *
	 * @since BuddyBoss 1.0.0
	 */
	function learndash_get_post_type_slug($type) {
		$postTypes = [
			'course'       => 'sfwd-courses',
			'lesson'       => 'sfwd-lessons',
			'topic'        => 'sfwd-topic',
			'quiz'         => 'sfwd-quiz',
			'question'     => 'sfwd-question',
			'transactions' => 'sfwd-transactions',
			'group'        => 'groups',
			'assignment'   => 'sfwd-assignment',
			'essays'       => 'sfwd-essays',
			'certificates' => 'sfwd-certificates',
		];

		return $postTypes[$type];
	}
}
