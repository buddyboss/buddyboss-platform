<?php
/**
 * BuddyBoss Media Loader.
 *
 * A media component, Allow your users to upload photos and create albums.
 *
 * @package BuddyBoss\Media\Loader
 * @since BuddyBoss 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Set up the bp-media component.
 *
 * @since BuddyBoss 1.0.0
 */
function bboss_setup_media() {
	bboss()->media = new BBoss_Media_Component();
}
add_action( 'bp_setup_components', 'bboss_setup_media', 5 );
