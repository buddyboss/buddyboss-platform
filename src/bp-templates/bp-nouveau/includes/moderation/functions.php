<?php

/**
 * Moderation functions
 *
 * @since BuddyBoss 1.5.4
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Register Scripts for the Moderation component
 *
 * @since BuddyBoss 1.5.4
 *
 * @param array $scripts The array of scripts to register
 *
 * @return array The same array with the specific moderation scripts.
 */
function bp_nouveau_moderation_register_scripts( $scripts = array() ) {
	if ( ! isset( $scripts['bp-nouveau'] ) ) {
		return $scripts;
	}

	return array_merge( $scripts, array(
		'bp-nouveau-moderation' => array(
			'file'         => 'js/buddypress-moderation%s.js',
			'dependencies' => array( 'bp-nouveau' ),
			'footer'       => true,
		)
	) );
}

/**
 * Enqueue the moderation scripts
 *
 * @since BuddyBoss 1.5.4
 */
function bp_nouveau_moderation_enqueue_scripts() {

	wp_enqueue_script( 'bp-nouveau-moderation' );
}
