<?php

/**
 * Moderation functions
 *
 * @since BuddyBoss 1.5.6
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Register Scripts for the Moderation component
 *
 * @since BuddyBoss 1.5.6
 *
 * @param array $scripts The array of scripts to register
 *
 * @return array The same array with the specific moderation scripts.
 */
function bboss_nouveau_moderation_register_scripts( $scripts = array() ) {
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
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_nouveau_moderation_register_scripts' ) && ! function_exists( 'bp_nouveau_moderation_register_scripts' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_nouveau_moderation_register_scripts()}.
	 *
	 * @deprecated Use bboss_nouveau_moderation_register_scripts() instead.
	 */
	function bp_nouveau_moderation_register_scripts( $scripts = array() ) {
		return bboss_nouveau_moderation_register_scripts( $scripts );
	}
}
// @bbwt-com-end compat-layer


/**
 * Enqueue the moderation scripts
 *
 * @since BuddyBoss 1.5.6
 */
function bboss_nouveau_moderation_enqueue_scripts() {

	wp_enqueue_script( 'bp-nouveau-moderation' );
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_nouveau_moderation_enqueue_scripts' ) && ! function_exists( 'bp_nouveau_moderation_enqueue_scripts' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_nouveau_moderation_enqueue_scripts()}.
	 *
	 * @deprecated Use bboss_nouveau_moderation_enqueue_scripts() instead.
	 */
	function bp_nouveau_moderation_enqueue_scripts() {
		return bboss_nouveau_moderation_enqueue_scripts();
	}
}
// @bbwt-com-end compat-layer


/**
 * Localize the strings needed for the moderation UI
 *
 * @since BuddyBoss 1.5.6
 *
 * @param array $params Associative array containing the JS Strings needed by scripts
 *
 * @return array         The same array with specific strings for the messages UI if needed.
 */
function bboss_nouveau_moderation_localize_scripts( $params = array() ) {

	//initialize moderation vars because it is used globally
	$params['moderation'] = array(
		'unblock_user_msg' => esc_html__( 'Are you sure you want to unblock this member?', 'buddyboss' ),
		'no_user_msg' => esc_html__( 'No blocked members found.', 'buddyboss' ),
	);

	return $params;
}
// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename name of the function above (decision 0013): declared right after it, guarded on it, so the old name exists exactly when the new one does.
if ( function_exists( 'bboss_nouveau_moderation_localize_scripts' ) && ! function_exists( 'bp_nouveau_moderation_localize_scripts' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_nouveau_moderation_localize_scripts()}.
	 *
	 * @deprecated Use bboss_nouveau_moderation_localize_scripts() instead.
	 */
	function bp_nouveau_moderation_localize_scripts( $params = array() ) {
		return bboss_nouveau_moderation_localize_scripts( $params );
	}
}
// @bbwt-com-end compat-layer

