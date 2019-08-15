/* jshint undef: false */
/* Email Verify */
/* global pwsL10n */
/* @version 3.0.0 */
( function( $ ){
	function check_email() {
		var email1 		  = $( '#signup_email' ).val();
		var email2 		  = $( '#signup_email_confirm' );
		var emailValidate = $( '#email-validate-result' );

		if ( email2.length ) {

			email2 = email2.val();

			// Reset classes and result text
			emailValidate.removeClass( 'hide show mismatch error' );
			if ( ! email1 ) {
				emailValidate.html( pwsL10n.empty );
				return;
			}

			strength = wp.passwordStrength.meter( email1, wp.passwordStrength.userInputBlacklist(), email2 );

			switch ( strength ) {
				case 5:
					emailValidate.addClass( 'show error' ).html( BP_Signup_Email.mismatch );
					break;
				default:
					emailValidate.addClass( 'hide' ).html( BP_Signup_Email.mismatch );
					break;
			}
		}
	}

	function validate_email() {
		var email1 = $( '#signup_email' ).val();
		var emailValidate = $( '#email-validate-result' );
		var emailRegex = /^([a-zA-Z0-9_.+-])+\@(([a-zA-Z0-9-])+\.)+([a-zA-Z0-9]{2,4})+$/;

		emailValidate.removeClass( 'hide show mismatch error' );

		if ( ! emailRegex.test( email1 ) ) {
			emailValidate.addClass( 'show error' ).html( BP_Signup_Email.incorrect );
		}
	}

	// Bind check_pass_strength to keyup events in the password fields
	$( document ).ready( function() {
		$( '#signup_email' ).val( '' ).keyup( validate_email );

		if ( $( '#signup_email_confirm' ).length ) {
			$( '#signup_email_confirm' ).val('' ).keyup( check_email );
		}

	} );

} )( jQuery );
