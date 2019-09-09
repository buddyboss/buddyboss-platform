/* jshint undef: false */
/* Email Verify */
/* global pwsL10n */
/* @version 3.0.0 */
( function( $ ){
	
	function check_email() {
		console.log("here");
		var email1 = $( '#signup_email' ).val(),
		    regex = /^([a-zA-Z0-9_\.\-\+])+\@(([a-zA-Z0-9\-])+\.)+([a-zA-Z0-9]{2,4})+$/;
		// Reset classes and result text
		$( '#email-strength-result' ).removeClass( 'show mismatch short bad good strong' );
		if(regex.test(email1)) {
			$( '#email-strength-result' ).html( "" );
			return;
		}

		if (!regex.test(email1)) {
			$( '#email-strength-result' ).addClass( 'show bad' ).html( "Enter valid email" );
			return;
		}
	}

	function check_email_confirm() {
		console.log("here");
		var email1 = $( '#signup_email' ).val(),
		    email2 = $( '#signup_email_confirm' ).val(),
		    regex = /^([a-zA-Z0-9_\.\-\+])+\@(([a-zA-Z0-9\-])+\.)+([a-zA-Z0-9]{2,4})+$/;
		// Reset classes and result text
		$( '#email-strength-result' ).removeClass( 'show mismatch short bad good strong' );
		if(regex.test(email2)) {
			$( '#email-strength-result' ).html( "" );
			return;
		}

		if (email1 !== email2) {
			$( '#email-strength-result' ).addClass( 'show mismatch' ).html( "Email are mismatch" );
			return;
		}
				
			
	}

	// Bind signup_email to keyup events in the email fields
	$( document ).ready( function() {
		$( '#signup_email' ).val( '' ).on('keyup keypress blur change', check_email );
		$( '#signup_email_confirm' ).val( '' ).on('keyup keypress blur change', check_email_confirm );
	} );

} )( jQuery );
