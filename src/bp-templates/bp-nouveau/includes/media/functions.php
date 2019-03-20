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
		'bp-nouveau-media-dropzone-options' => array(
			'file'         => buddypress()->plugin_url . 'bp-media/js/dropzone-options.js',
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
	wp_enqueue_script( 'bp-nouveau-media-dropzone-options' );
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

	return $params;
}