<?php

add_filter( 'bp_core_wpsignup_redirect', 'bp_ld_popup_register_redirect', 10 );

/**
 * Do not redirect to user on register page if user doing registration on LD Popup.
 *
 * @param bool $bool
 *
 * @since BuddyBoss 1.2.3
 */
function bp_ld_popup_register_redirect( $bool ) {

	if (
		   isset( $_POST )
		&& isset( $_POST['learndash-registration-form'] )
		&& 'true' === $_POST['learndash-registration-form']
	) {
		return false;
	}

	return $bool;
}
