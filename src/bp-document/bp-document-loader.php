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
 * @since BuddyBoss 1.3.0
 */
function bp_setup_document() {
	buddypress()->document = new BP_Document_Component();
}
add_action( 'bp_setup_components', 'bp_setup_document', 6 );
