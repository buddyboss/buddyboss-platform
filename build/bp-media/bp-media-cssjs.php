<?php
/**
 * BuddyBoss Media CSS and JS.
 *
 * @package BuddyBoss\Media\Scripts
 * @since BuddyPress 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Enqueue the JS for dropzone.
 */
function bp_media_add_dropzone_js() {
	// Include the dropzone JS
    $url = buddypress()->plugin_url . 'bp-media/js/';
    wp_enqueue_script( 'bp-media-dropzone', "{$url}dropzone.min.js", false, bp_get_version() );
    //wp_enqueue_script( 'bp-media-dropzone-wrapper', "{$url}dropzone-wrapper.js", array( 'jquery' ), bp_get_version() );
    //wp_enqueue_script( 'bp-media-dropzone-main', "{$url}dropzone-main.js", false, bp_get_version(), true );
}
add_action( 'bp_enqueue_scripts', 'bp_media_add_dropzone_js', 0 );

/**
 * Enqueue the CSS for dropzone.
 */
function bp_media_add_dropzone_css() {
    $url = buddypress()->plugin_url . 'bp-media/css/';
    wp_enqueue_style( 'bp-media-dropzone', "{$url}dropzone.min.css", array(), bp_get_version() );
}
//add_action( 'wp_head', 'bp_media_add_dropzone_css' );
