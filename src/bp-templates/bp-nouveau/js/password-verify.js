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
		strength_grid = '<div class="pass-str-scale"><span class="ps-cl1"></span><span class="ps-cl2"></span><span class="ps-cl3"></span><span class="ps-cl4"></span></div>';

		switch ( strength ) {
			case 2:
				$( '#pass-strength-result' ).addClass( 'show bad' ).html( strength_grid + '<div class="pass-str-label">' + pwsL10n.bad + '</div>' );
				break;
			case 3:
				$( '#pass-strength-result' ).addClass( 'show good' ).html( strength_grid + '<div class="pass-str-label">' + pwsL10n.good + '</div>' );
				break;
			case 4:
				$( '#pass-strength-result' ).addClass( 'show strong' ).html( strength_grid + '<div class="pass-str-label">' + pwsL10n.strong + '</div>' );
				break;
			case 5:
				$( '#pass-strength-result' ).addClass( 'show mismatch' ).html( strength_grid + '<div class="pass-str-label">' + pwsL10n.mismatch + '</div>' );
				break;
			default:
				$( '#pass-strength-result' ).addClass( 'show short' ).html( strength_grid + '<div class="pass-str-label">' + pwsL10n.short + '</div>' );
				break;
		}
	}

	// Bind check_pass_strength to keyup events in the password fields
	$( document ).ready( function() {
		$( '.password-entry' ).val( '' ).keyup( check_pass_strength );
		$( '.password-entry-confirm' ).val( '' ).keyup( check_pass_strength );
	} );

} )( jQuery );
