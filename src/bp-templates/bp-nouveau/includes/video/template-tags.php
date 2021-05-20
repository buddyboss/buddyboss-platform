<?php
/**
 * Video Template tags
 *
 * @package BuddyBoss\Core
 *
 * @since BuddyBoss 1.7.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Before Video's directory content legacy do_action hooks wrapper
 *
 * @since BuddyBoss 1.7.0
 */
function bp_nouveau_before_video_directory_content() {
	/**
	 * Fires at the beginning of the templates BP injected content.
	 *
	 * @since BuddyBoss 1.7.0
	 */
	do_action( 'bp_before_directory_video' );

	/**
	 * Fires before the video directory display content.
	 *
	 * @since BuddyBoss 1.7.0
	 */
	do_action( 'bp_before_directory_video_content' );
}

/**
 * After Video's directory content legacy do_action hooks wrapper
 *
 * @since BuddyBoss 1.7.0
 */
function bp_nouveau_after_video_directory_content() {
	/**
	 * Fires after the display of the video list.
	 *
	 * @since BuddyBoss 1.7.0
	 */
	do_action( 'bp_after_directory_video_list' );

	/**
	 * Fires inside and displays the video directory display content.
	 */
	do_action( 'bp_directory_video_content' );

	/**
	 * Fires after the video directory display content.
	 *
	 * @since BuddyBoss 1.7.0
	 */
	do_action( 'bp_after_directory_video_content' );

	/**
	 * Fires after the video directory listing.
	 *
	 * @since BuddyBoss 1.7.0
	 */
	do_action( 'bp_after_directory_video' );

}

/**
 * Fire specific hooks into the video entry template
 *
 * @since BuddyBoss 1.7.0
 *
 * @param string $when   Optional. Either 'before' or 'after'.
 * @param string $suffix Optional. Use it to add terms at the end of the hook name.
 */
function bp_nouveau_video_hook( $when = '', $suffix = '' ) {
	$hook = array( 'bp' );

	if ( $when ) {
		$hook[] = $when;
	}

	// It's an video entry hook.
	$hook[] = 'video';

	if ( $suffix ) {
		$hook[] = $suffix;
	}

	bp_nouveau_hook( $hook );
}

/**
 * Output the Video timestamp into the bp-timestamp attribute.
 *
 * @since BuddyBoss 1.7.0
 */
function bp_nouveau_video_timestamp() {
	echo esc_attr( bp_nouveau_get_video_timestamp() );
}

/**
 * Get the Video timestamp.
 *
 * @since BuddyBoss 1.7.0
 *
 * @return integer The Video timestamp.
 */
function bp_nouveau_get_video_timestamp() {
	/**
	 * Filter here to edit the video timestamp.
	 *
	 * @since BuddyBoss 1.7.0
	 *
	 * @param integer $value The Video timestamp.
	 */
	return apply_filters( 'bp_nouveau_get_video_timestamp', strtotime( bp_get_video_date_created() ) );
}
