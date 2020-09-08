<?php
/**
 * xProfile functions
 *
 * @since BuddyPress 3.0.0
 * @version 3.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Register Scripts for the xProfile component
 *
 * @since BuddyPress 3.0.0
 *
 * @param array $scripts The array of scripts to register
 *
 * @return array The same array with the specific groups scripts.
 */
function bp_nouveau_xprofile_register_scripts( $scripts = array() ) {
	if ( ! isset( $scripts['bp-nouveau'] ) ) {
		return $scripts;
	}

	return array_merge( $scripts, array(
		'bp-nouveau-xprofile' => array(
			'file'         => 'js/buddypress-xprofile%s.js',
			'dependencies' => array( 'bp-nouveau', 'jquery-ui-sortable' ),
			'footer'       => true,
		),
	) );
}

/**
 * Enqueue the xprofile scripts
 *
 * @since BuddyPress 3.0.0
 */
function bp_nouveau_xprofile_enqueue_scripts() {
	if ( ! bp_is_user_profile_edit() && ! bp_is_register_page() ) {
		return;
	}
	wp_enqueue_script( 'bp-nouveau-xprofile' );
}


/**
 * Return profile header buttons
 *
 * @since BuddyBoss 1.5.1
 *
 * @return mixed|void
 */

function bb_profile_header_buttons() {

	$buttons = apply_filters( 'bb_profile_header_buttons', array(
		'member_friendship' => __( 'Connect', 'buddyboss' ),
		'member_follow'     => __( 'Follow', 'buddyboss' ),
		'private_message'   => __( 'Message', 'buddyboss' ),
		'member_switch'     => __( 'View As', 'buddyboss' ),
	) );

	return $buttons;
}

/**
 * Save Profile Header
 * @since BuddyBoss 1.5.1
 */

add_action( 'wp_ajax_save_profile_header_buttons_order', 'save_profile_header_buttons_order' );

function save_profile_header_buttons_order(){
	$buttons = sanitize_text_field( $_POST['buttons'] );

	/**
	 * Filter the profile header buttons
	 *
	 * @since BuddyBoss 1.5.1
	 */
	update_option( '_bb_profile_header_buttons', apply_filters( '_bb_profile_header_buttons', $buttons ) );

	wp_send_json_success();
}

function bb_get_profile_header_buttons(){

	/**
	 * Filter the header buttons
	 *
	 * @since BuddyBoss 1.5.1
	 */

	return apply_filters( 'bb_get_profile_header_buttons', get_option( '_bb_profile_header_buttons' ) );
}
