/* jshint devel: true */
/* global BP_Register */

jQuery( document ).ready( function() {

	//for form validation
	jQuery( document ).on( 'click', 'body #buddypress #register-page #signup-form #signup_submit', function() {
		//e.preventDefault();
		var html_error = '<div class="bp-messages bp-feedback error">';
			html_error += '<span class="bp-icon" aria-hidden="true"></span>';
			html_error += '<p>' + BP_Register.required_field + '</p>';
			html_error += '</div>';
		var signup_email 			= jQuery( '#signup_email' ),
			signup_email_confirm 	= jQuery( '#signup_email_confirm' ),
			signup_password 		= jQuery( '#signup_password' ),
			signup_password_confirm = jQuery( '#signup_password_confirm' );
		var return_val = true;
		jQuery( '.register-page .error' ).remove();

		if ( jQuery( document ).find( signup_email_confirm ).length && jQuery( document ).find( signup_email_confirm ).val() == '' ) {
			jQuery( document ).find( signup_email_confirm ).after( html_error );
			jQuery( document ).find( signup_email_confirm ).addClass( 'invalid' );
			return_val = false;
		} else {
			jQuery( document ).find( signup_email_confirm ).removeClass( 'invalid' );
		}
		if ( jQuery( document ).find( signup_password ).length && jQuery( document ).find( signup_password ).val() == '' ) {
			jQuery( document ).find( signup_password ).after( html_error );
			jQuery( document ).find( signup_password ).addClass( 'invalid' );
			return_val = false;
		} else {
			jQuery( document ).find( signup_password ).removeClass( 'invalid' );
		}
		if ( jQuery( document ).find( signup_password_confirm ).length && jQuery( document ).find( signup_password_confirm ).val() == '' ) {
			jQuery( document ).find( signup_password_confirm ).after( html_error );
			jQuery( document ).find( signup_password_confirm ).addClass( 'invalid' );
			return_val = false;
		} else {
			jQuery( document ).find( signup_password_confirm ).removeClass( 'invalid' );
		}
		jQuery( '.required-field' ).each( function() {

			if ( jQuery( this ).find( 'input[type="text"]' ).length && jQuery( this ).find( 'input[type="text"] ').val() == '' ) {
				jQuery( this ).find( 'input[type="text"]' ).after( html_error );
				jQuery( this ).find( 'input[type="text"]' ).addClass( 'invalid' );
				return_val = false;
			} else {
				jQuery( this ).find( 'input[type="text"]' ).removeClass( 'invalid' );
			}
			if ( jQuery( this ).find( 'input[type="number"]' ).length && jQuery( this ).find( 'input[type="number"] ').val() == '' ) {
				jQuery( this ).find( 'input[type="number"]' ).after( html_error );
				jQuery( this ).find( 'input[type="number"]' ).addClass( 'invalid' );
				return_val = false;
			} else {
				jQuery( this ).find( 'input[type="number"]' ).removeClass( 'invalid' );
			}
			if ( jQuery( this ).find( 'textarea' ).length && jQuery( this ).find( 'textarea' ).val() == '' || undefined === typeof jQuery( this ).find( 'textarea' ).val() ) {
				jQuery( this ).find( 'textarea' ).after( html_error );
				jQuery( this ).find( 'textarea' ).addClass( 'invalid' );
				return_val = false;
			} else {
				jQuery( this ).find( 'textarea' ).removeClass( 'invalid' );
			}
			if ( jQuery( this ).find( 'select' ).length && jQuery( this ).find( 'select' ).val() == '' ) {
				jQuery( this ).find( 'select' ).after( html_error );
				jQuery( this ).find( 'select' ).addClass( 'invalid' );
				return_val = false;
			} else {
				jQuery( this ).find( 'select' ).removeClass( 'invalid' );
			}
			if ( jQuery( this ).find( 'input[type="checkbox"]' ).length ) {
					var checked_check = 0;
					jQuery( this ).find('input[type="checkbox"]' ).each( function() {
					    if ( jQuery( this ).prop( 'checked' ) == true ){
					        checked_check++;
					    }
					});
					if ( 0 >= checked_check ) {
						jQuery( this ).find( 'legend' ).next().append( html_error );
						return_val = false;
					}
			}
		});
		if ( jQuery( document ).find( signup_email ).length && jQuery( document ).find( signup_email ).val() == '' ) {
			jQuery( document ).find( signup_email ).after( html_error );
			jQuery( document ).find( signup_email ).addClass( 'invalid' );
			return_val = false;
		}else{
			bp_register_validate_confirm_email();

            jQuery.ajax({
			    type: 'POST',
			    url: ajaxurl,
			    dataType: 'json',
				async: false,
			    data: jQuery( 'body #buddypress #register-page #signup-form' ).serialize() + '&action=check_email',
			    success: function ( response ) {
				    if ( response.signup_email ) {
					    var html_serror = '<div class="bp-messages bp-feedback error">';
					    html_serror += '<span class="bp-icon" aria-hidden="true"></span>';
					    html_serror += '<p>' + response.signup_email + '</p>';
					    html_serror += '</div>';
					
					    jQuery( document ).find( signup_email ).after( html_serror );
					    jQuery( document ).find( signup_email ).addClass( 'invalid' );
					    return_val = false;
				    } else {
					    jQuery( document ).find( signup_email ).removeClass( 'invalid' );
				    }
				    var nickname = 'field_' + response.field_id;
				    if ( response.signup_username ) {
					    var html_uerror = '<div class="bp-messages bp-feedback error">';
					    html_uerror += '<span class="bp-icon" aria-hidden="true"></span>';
					    html_uerror += '<p>' + response.signup_username + '</p>';
					    html_uerror += '</div>';
					    jQuery( document ).find( '#' + nickname ).after( html_uerror );
					    jQuery( document ).find( '#' + nickname ).addClass( 'invalid' );
					    return_val = false;
				    } else {
					    jQuery( document ).find( '#' + nickname ).removeClass( 'invalid' );
				    }
				    return true;
			    }
			});
		}
		if ( ! return_val ) {
			var target = jQuery( '.error' ).first();
			if (target.length) {
				jQuery('html,body').animate({
					scrollTop: target.offset().top
				}, 1000);
				return false;
			}
		}
		return return_val;
	});

	// Bind signup_email to keyup events in the email fields
	var emailSelector, confirmEmailSelector, errorMessageSelector;
	emailSelector 	  = jQuery( '#signup_email' );
	if ( emailSelector.length ) {
		emailSelector.on( 'focusout', bp_register_validate_email );
    }

	confirmEmailSelector =  jQuery( '#signup_email_confirm' );
    if ( confirmEmailSelector.length ) {
		confirmEmailSelector.on( 'keyup change' , bp_register_validate_confirm_email );
    }


	function bp_register_validate_email() {
		var email1 				 = emailSelector.val(),
			email2 				 = confirmEmailSelector.val(),
		    regex 				 = /^([a-zA-Z0-9_\.\-\+])+\@(([a-zA-Z0-9\-])+\.)+([a-zA-Z0-9]{2,4})+$/;
			errorMessageSelector = jQuery( '#email-strength-result' );

		// Reset classes and result text
		errorMessageSelector.removeClass( 'show mismatch bad' );
		if( ( '' === email1 && '' === email2 ) && regex.test( email1 ) ) {
			errorMessageSelector.html( '' );
			return;
		}else{
			errorMessageSelector.html( '' );
			if ( ( email1 !== '' || email2 !== '' ) && !regex.test( email1 ) ) {
				errorMessageSelector.addClass( 'show bad' ).html( BP_Register.valid_email );
				return;
			}
			if ( ( email2 !== '' ) && ( email1 !== email2 ) && confirmEmailSelector.length ) {
				errorMessageSelector.addClass( 'show mismatch' ).html( BP_Register.mismatch_email );
				jQuery( document ).find( emailSelector ).addClass( 'invalid' );
				jQuery( document ).find( confirmEmailSelector ).addClass( 'invalid' );
				return;
			} else {
				jQuery( document ).find( emailSelector ).removeClass( 'invalid' );
				jQuery( document ).find( confirmEmailSelector ).removeClass( 'invalid' );
			}
		}
	}

	function bp_register_validate_confirm_email() {

		if(	window.event.keyCode === 9 ){
			return;
		}

		var email1 				 = emailSelector.val(),
		    email2 				 = confirmEmailSelector.val(),
		    regex 				 = /^([a-zA-Z0-9_\.\-\+])+\@(([a-zA-Z0-9\-])+\.)+([a-zA-Z0-9]{2,4})+$/;
			errorMessageSelector = jQuery( '#email-strength-result' );

		// Reset classes and result text
		errorMessageSelector.removeClass( 'show mismatch bad' );
		if ( ( '' === email1 && '' === email2 ) || ( '' === email2 ) || ( regex.test( email2 ) && regex.test( email1 ) && ( email1 === email2	) ) ) {
			errorMessageSelector.html( '' );
			return;
		}
		if ( email1 !== email2 && confirmEmailSelector.length ) {
			jQuery( document ).find( emailSelector ).addClass( 'invalid' );
			jQuery( document ).find( confirmEmailSelector ).addClass( 'invalid' );
			errorMessageSelector.addClass( 'show mismatch' ).html( BP_Register.mismatch_email );
			return;
		} else {
			jQuery( document ).find( emailSelector ).removeClass( 'invalid' );
			jQuery( document ).find( confirmEmailSelector ).removeClass( 'invalid' );
		}
	}

} );
