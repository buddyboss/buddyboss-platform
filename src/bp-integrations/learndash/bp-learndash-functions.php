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

// Make Course extension to default in group
add_filter( 'bp_groups_default_extension', 'bp_set_course_group_default_tab' );

/**
 * Make course extension to default in group.
 *
 * @since BuddyBoss 1.0.0
 *
 * @param $component
 *
 * @return mixed
 */
function bp_set_course_group_default_tab( $component ) {

	// Get the group nav order based on the customizer settings.
	$nav_tabs = bp_nouveau_get_appearance_settings( 'group_nav_order' );
	$va = bp_ld_sync( 'settings' )->get( 'buddypress.enabled', true );
	if ( isset( $nav_tabs[0] ) && 'courses' === $nav_tabs[0] && bp_is_active( 'groups' ) && is_plugin_active( 'sfwd-lms/sfwd_lms.php' ) && '1' === $va ) {
		if ( empty( bp_learndash_get_group_courses( bp_get_current_group_id() ) ) ) {
			return $component;
		} else {
			return $nav_tabs[0];
		}
	}

	return $component;
}

// Make Reports extension to default in group
add_filter( 'bp_groups_default_extension', 'bp_set_reports_group_default_tab' );

/**
 * Make reports extension to default in group.
 *
 * @since BuddyBoss 1.0.0
 *
 * @param $component
 *
 * @return mixed
 */
function bp_set_reports_group_default_tab( $component ) {

	// Get the group nav order based on the customizer settings.
	$nav_tabs = bp_nouveau_get_appearance_settings( 'group_nav_order' );
	$va = bp_ld_sync( 'settings' )->get( 'reports.enabled', true );
	if ( isset( $nav_tabs[0] ) && 'reports' === $nav_tabs[0] && bp_is_active( 'groups' ) && is_plugin_active( 'sfwd-lms/sfwd_lms.php' ) && '1' === $va ) {
		if ( empty( bp_learndash_get_group_courses( bp_get_current_group_id() ) ) ) {
			return $component;
		} else {
			return $nav_tabs[0];
		}
	}

	return $component;
}
