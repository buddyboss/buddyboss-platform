<?php
/**
 * TutorLMS integration filters
 *
 * @package BuddyBoss\TutorLMS
 * @since BuddyBoss [BBVERSION]
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

add_filter( 'bb_feed_excluded_post_types', 'bb_feed_not_allowed_tutorlms_post_types' );
function bb_feed_not_allowed_tutorlms_post_types( $post_types ) {

	$bb_tutorlms_posttypes = ! empty( bb_tutorlms_get_post_types() ) ? bb_tutorlms_get_post_types() : array();

	if ( ! empty( $post_types ) ) {
		$post_types = array_merge( $post_types, $bb_tutorlms_posttypes );
	} else {
		$post_types = $bb_tutorlms_posttypes;
	}

	return $post_types;
}
