<?php
/**
 * Media functions
 *
 * @since BuddyBoss 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Register Scripts for the Media component
 *
 * @since BuddyBoss 1.0.0
 *
 * @param array $scripts The array of scripts to register
 *
 * @return array The same array with the specific media scripts.
 */
function bp_nouveau_media_register_scripts( $scripts = array() ) {
	if ( ! isset( $scripts['bp-nouveau'] ) ) {
		return $scripts;
	}

	return array_merge( $scripts, array(
		'bp-nouveau-media' => array(
			'file'         => 'js/buddypress-media%s.js',
			'dependencies' => array( 'bp-nouveau' ),
			'footer'       => true,
		),
		'bp-nouveau-media-theatre' => array(
			'file'         => 'js/buddypress-media-theatre%s.js',
			'dependencies' => array( 'bp-nouveau' ),
			'version'      => bp_get_version(),
			'footer'       => true,
		),
	) );
}

/**
 * Enqueue the media scripts
 *
 * @since BuddyBoss 1.0.0
 */
function bp_nouveau_media_enqueue_scripts() {
	wp_enqueue_script( 'bp-nouveau-media' );
	wp_enqueue_script( 'bp-nouveau-media-theatre' );
}

/**
 * Localize the strings needed for the messages UI
 *
 * @since BuddyPress 3.0.0
 *
 * @param  array $params Associative array containing the JS Strings needed by scripts
 * @return array         The same array with specific strings for the messages UI if needed.
 */
function bp_nouveau_media_localize_scripts( $params = array() ) {

	$params['media'] = array(
		'max_upload_size' => bp_media_file_upload_max_size(),
	);

	if ( bp_is_single_album() ) {
		$params['media']['album_id'] = (int) bp_action_variable( 0 );
	}

	return $params;
}

/**
 * Add media theatre template for activity pages
 */
function bp_nouveau_media_add_theatre_template() {
	bp_get_template_part( 'media/theatre' );
}

/**
 * Get activity entry media to render on front end
 */
function bp_nouveau_media_activity_entry() {
	$media_ids = bp_activity_get_meta( bp_get_activity_id(), 'bp_media_ids', true );

	if ( ! empty( $media_ids ) && bp_has_media( array( 'include' => $media_ids ) ) ) {
		while ( bp_media() ) {
			bp_the_media();
			bp_get_template_part( 'media/activity-entry' );
		}
	}
}

/**
 * Update media for activity
 *
 * @param $content
 * @param $user_id
 * @param $activity_id
 *
 * @since BuddyBoss 1.0.0
 *
 * @return bool
 */
function bp_nouveau_media_update_media_meta( $content, $user_id, $activity_id ) {

	if ( ! isset( $_POST['media'] ) || empty( $_POST['media'] ) ) {
		return false;
	}

	$media_list = $_POST['media'];

	if ( ! empty( $media_list ) ) {
		$media_ids = array();
		foreach ( $media_list as $media_index => $media ) {

			// remove actions to avoid infinity loop
			remove_action( 'bp_activity_posted_update', 'bp_nouveau_media_update_media_meta', 10, 3 );
			remove_action( 'bp_groups_posted_update', 'bp_nouveau_media_groups_update_media_meta', 10, 4 );

			// make an activity for the media
			$content = '&nbsp;';
			$a_id = bp_activity_post_update( array( 'content' => $content, 'hide_sitewide' => true ) );

			add_action( 'bp_activity_posted_update', 'bp_nouveau_media_update_media_meta', 10, 3 );
			add_action( 'bp_groups_posted_update', 'bp_nouveau_media_groups_update_media_meta', 10, 4 );

			$media_id = bp_media_add(
				array(
					'title'         => ! empty( $media['name'] ) ? $media['name'] : '&nbsp;',
					'album_id'      => ! empty( $media['album_id'] ) ? $media['album_id'] : 0,
					'activity_id'   => $a_id,
					'privacy'       => ! empty( $media['privacy'] ) ? $media['privacy'] : 'public',
					'attachment_id' => ! empty( $media['id'] ) ? $media['id'] : 0,
					'menu_order'    => isset( $media['menu_order'] ) ? absint( $media['menu_order'] ) : $media_index,
				)
			);

			if ( $media_id ) {
				$media_ids[] = $media_id;
			}
		}

		$media_ids = implode( ',', $media_ids );

		//save media meta for activity
		if ( ! empty( $activity_id ) ) {
			bp_activity_update_meta( $activity_id, 'bp_media_ids', $media_ids );
		}
	}
}

/**
 * Update media for group activity
 *
 * @param $content
 * @param $user_id
 * @param $group_id
 * @param $activity_id
 *
 * @since BuddyBoss 1.0.0
 *
 * @return bool
 */
function bp_nouveau_media_groups_update_media_meta( $content, $user_id, $group_id, $activity_id ) {
	bp_nouveau_media_update_media_meta( $content, $user_id, $activity_id );
}