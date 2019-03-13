<?php
/**
 * BuddyBoss Media Template Functions.
 *
 * @package BuddyBoss\Media\Templates
 * @since BuddyBoss 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Output the media component slug.
 *
 * @since BuddyBoss 1.0.0
 *
 */
function bp_media_slug() {
	echo bp_get_media_slug();
}
/**
 * Return the media component slug.
 *
 * @since BuddyBoss 1.0.0
 *
 * @return string
 */
function bp_get_media_slug() {

	/**
	 * Filters the media component slug.
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @param string $slug Media component slug.
	 */
	return apply_filters( 'bp_get_media_slug', buddypress()->media->slug );
}
