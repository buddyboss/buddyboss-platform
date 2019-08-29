<?php

add_filter( 'bp_core_before_wpsignup_redirect', 'ld_register_redirect' );

/**
 * Do not redirect to user on register page if user doing registration on LD Popup.
 *
 * @param $redirect
 *
 * @since BuddyBoss 1.1.9
 *
 * @return bool
 */
function ld_register_redirect( $redirect ) {
	if ( $redirect && isset( $_POST ) && isset( $_POST['learndash-registration-form'] ) && 'true' === $_POST['learndash-registration-form'] ) {
		$redirect = false;
	}

	return $redirect;
}
