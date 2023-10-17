<?php
/**
 * TutorLMS integration group sync helpers
 *
 * @package BuddyBoss\TutorLMS
 * @since BuddyBoss 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Returns TutorLMS path.
 *
 * @since BuddyBoss 1.0.0
 */
function bb_tutorlms_path( $path = '' ) {
	return trailingslashit( buddypress()->integrations['tutorlms']->path ) . trim( $path, '/\\' );
}

/**
 * Returns TutorLMS url.
 *
 * @since BuddyBoss 1.0.0
 */
function bb_tutorlms_url( $path = '' ) {
	return trailingslashit( buddypress()->integrations['tutorlms']->url ) . trim( $path, '/\\' );
}

function bb_tutorlms( $component = null ) {
	global $bb_tutorlms;

	return $component ? $bb_tutorlms->$component : $bb_tutorlms;
}

// forward compatibility
if ( ! function_exists( 'tutorlms_get_post_type_slug' ) ) {
	/**
	 * Returns array of slugs used by TutorLMS integration.
	 *
	 * @since BuddyBoss 1.0.0
	 */
	function tutorlms_get_post_type_slug( $type ) {
		$postTypes = array(
			'course'       => 'courses',
			'lesson'       => 'lesson',
			'topic'        => 'topics',
			'quiz'         => 'tutor_quiz',
		);

		return $postTypes[ $type ];
	}
}

/**
 * Get the course style view
 *
 * @since BuddyBoss 1.2.0
 *
 * @return string
 */
function bb_tutorlms_page_display() {

	if ( empty( $_COOKIE['courseview'] ) || $_COOKIE['courseview'] == '' ) {

		if ( function_exists( 'bp_get_view' ) ):
			$view = bp_get_view();
		else:
			$view = 'grid';
		endif;
	} else {
		$view = $_COOKIE['courseview'];
	}

	return $view;
}

/**
 * Check if there is any certificated created by the admin and if so then show the certificate tab or else hide the tab
 *
 * @since BuddyBoss 1.2.0
 *
 * @return $value bool
 */
function bb_core_tutorlms_certificates_enables() {
	static $cache = null;

	$value = false;
	$args  = array(
		'post_type'   => 'sfwd-certificates',
		'post_status' => 'publish',
		'numberposts' => 1,
		'fields'      => 'ids',
		// 'numberposts' => 1 -> We just check here if certification available then display tab in profile section.
		// So if we get only one course then we can verify it like certificate available or not.
	);

	if ( null === $cache ) {
		$query = get_posts( $args );
	} else {
		$query = $cache;
	}

	if ( ! empty( $query ) && count( $query ) > 0 ) {
		$value = true;
	}

	return $value;
}

/**
 * Social Group Sync View Tutorial button.
 *
 * @since BuddyBoss 1.5.8
 */
function bb_tutorial_social_group_sync() {
	?>

	<p>
		<a class="button" href="<?php echo bp_get_admin_url(
			add_query_arg(
				array(
					'page'    => 'bb-help',
					'article' => 62877,
				),
				'admin.php'
			)
		); ?>"><?php _e( 'View Tutorial', 'buddyboss' ); ?></a>
	</p>

	<?php
}

/**
 * My Courses Tab View Tutorial button.
 *
 * @since BuddyBoss 1.5.8
 */
function bb_profiles_tutorial_my_courses() {
	?>

	<p>
		<a class="button" href="<?php echo bp_get_admin_url(
			add_query_arg(
				array(
					'page'    => 'bb-help',
					'article' => 83110,
				),
				'admin.php'
			)
		); ?>"><?php _e( 'View Tutorial', 'buddyboss' ); ?></a>
	</p>

	<?php
}
