/* jshint undef: false */
/* Password Verify */
/* global pwsL10n */
/* @version 3.0.0 */
( function( $ ){
	function check_pass_strength() {
		var pass1 = $( '.password-entry' ).val(),
		    pass2 = $( '.password-entry-confirm' ).val(),
		    strength;

		// Reset classes and result text
		$( '#pass-strength-result' ).removeClass( 'show mismatch short bad good strong' );
		if ( ! pass1 ) {
			$( '#pass-strength-result' ).html( pwsL10n.empty );
			return;
		}

		strength = wp.passwordStrength.meter( pass1, wp.passwordStrength.userInputBlacklist(), pass2 );

		switch ( strength ) {
			case 2:
				$( '#pass-strength-result' ).addClass( 'show bad' ).html( pwsL10n.bad );
				break;
			case 3:
				$( '#pass-strength-result' ).addClass( 'show good' ).html( pwsL10n.good );
				break;
			case 4:
				$( '#pass-strength-result' ).addClass( 'show strong' ).html( pwsL10n.strong );
				break;
			case 5:
				$( '#pass-strength-result' ).addClass( 'show mismatch' ).html( pwsL10n.mismatch );
				break;
			default:
				$( '#pass-strength-result' ).addClass( 'show short' ).html( pwsL10n.short );
				break;
		}
	}

	// Bind check_pass_strength to keyup events in the password fields
	$( document ).ready( function() {
		$( '.password-entry' ).val( '' ).keyup( check_pass_strength );
		$( '.password-entry-confirm' ).val( '' ).keyup( check_pass_strength );
	} );


	// Bind check_pass_strength to keyup events in the password fields
	$(document).ready(function () {

		if ( typeof BP_Signup_Email !== 'undefined' && typeof BP_Signup_Email.signup !== 'undefined' && 'show' === BP_Signup_Email.signup ) {

			$('#signup_email').val('').keyup(validate_email);

			if ($('#signup_email_confirm').length) {
				$('#signup_email_confirm').val('').keyup(check_email);
			}

		}

	});

	function check_email() {
		var email1 = $('#signup_email').val();
		var email2 = $('#signup_email_confirm');
		var emailValidate = $('#email-validate-result');

		if (email2.length) {

			email2 = email2.val();

			// Reset classes and result text
			emailValidate.removeClass('hide show mismatch error');
			if (!email1) {
				emailValidate.html(pwsL10n.empty);
				return;
			}

			strength = wp.passwordStrength.meter(email1, wp.passwordStrength.userInputBlacklist(), email2);

			switch (strength) {
				case 5:
					emailValidate.addClass('show error').html(BP_Signup_Email.mismatch);
					break;
				default:
					emailValidate.addClass('hide').html(BP_Signup_Email.mismatch);
					break;
			}
		}
	}

	function validate_email() {
		var email1 = $('#signup_email').val();
		var emailValidate = $('#email-validate-result');
		var emailRegex = /^([a-zA-Z0-9_.+-])+\@(([a-zA-Z0-9-])+\.)+([a-zA-Z0-9]{2,4})+$/;

		emailValidate.removeClass('hide show mismatch error');

		if (!emailRegex.test(email1)) {
			emailValidate.addClass('show error').html(BP_Signup_Email.incorrect);
		}
	}

} )( jQuery );
