<?php

add_action( 'bp_core_before_wpsignup_redirect', 'bp_ld_popup_register_redirect', 10 );

/**
 * Do not redirect to user on register page if user doing registration on LD Popup.
 *
 * @param $redirect
 *
 * @since BuddyBoss 1.1.9
 */
function bp_ld_popup_register_redirect() {
	if ( isset( $_POST ) && isset( $_POST['learndash-registration-form'] ) && 'true' === $_POST['learndash-registration-form'] ) {
		$url = isset( $_POST['redirect_to'] ) ? $_POST['redirect_to'] : bp_get_signup_page();
		bp_core_redirect( esc_url( $url ) );
	}
}
