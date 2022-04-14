<?php
/**
 * BuddyBoss Video Loader.
 *
 * A video component, Allow your users to upload videos and create albums.
 *
 * @package BuddyBoss\Video\Loader
 * @since BuddyBoss 1.7.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Set up the bp-video component.
 *
 * @since BuddyBoss 1.7.0
 */
function bp_setup_video() {
	buddypress()->video = new BP_Video_Component();
}
add_action( 'bp_setup_components', 'bp_setup_video', 4 );
