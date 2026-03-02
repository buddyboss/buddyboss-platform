<?php
/**
 * BuddyBoss Document Loader.
 *
 * A document component, Allow your users to upload photos and create folders.
 *
 * @package BuddyBoss\Document\Loader
 * @since BuddyBoss 1.4.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Set up the bp-document component.
 *
 * @since BuddyBoss 1.4.0
 */
function bp_setup_document() {
	buddypress()->document = new BP_Document_Component();
}
add_action( 'bp_setup_components', 'bp_setup_document', 5 );
