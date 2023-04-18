/* jshint devel: true */
/* global BP_Register */

jQuery( document ).ready( function() {

	// Get Existing Register page field ids.
	var getExistingFieldsSelector = jQuery('body .layout-wrap #profile-details-section #signup_profile_field_ids');

	// Add new hidden field for keep existing field to add again in change profile type action.
	var hiddenField  = jQuery('<input type="hidden" class="onloadfields" value="" />');
	var existsField  = jQuery('<input type="hidden" name="signup_profile_field_ids" id="signup_profile_field_ids" value="" />');
	var prevField  	= jQuery('<input type="hidden" name="signup_profile_field_id_prev" id="signup_profile_field_id_prev" value="" />');

	// Append new field to body.
	jQuery('body').append(hiddenField);
	jQuery('body').append(prevField);

	var onLoadField  = jQuery('body .onloadfields');
	var firstCall    = 0;

	// Add existing profile fields ids to new hidden field value.
	onLoadField.val( getExistingFieldsSelector.val() );

	if ( typeof window.tinymce !== 'undefined' ) {
		jQuery( window.tinymce.editors ).each( function( index ) {
			window.tinymce.editors[index].on('change', function () {
				window.tinymce.editors[index].save();
			});
		});
	}

	var dropDownSelected = jQuery( 'body #buddypress #register-page #signup-form .layout-wrap #profile-details-section .editfield fieldset select#' + BP_Register.field_id);

	if ( typeof dropDownSelected.val() !== 'undefined' && dropDownSelected.val().length ) {
		if ( 1 === firstCall ) {
			jQuery( 'body .ajax_added' ).remove();
			getExistingFieldsSelector.val( jQuery('.onloadfields').val() );
			prevField.val(dropDownSelected.val());
		}

		var getExistingFields = getExistingFieldsSelector.val();
		var getSelectedValue  = dropDownSelected.val();
		var appendHtmlDiv 	  = jQuery('.register-section.extended-profile');
		var fixedIds 		  = onLoadField.val();

		var data = {
			'action'  : 'xprofile_get_field',
			'_wpnonce': BP_Register.nonce,
			'fields'  : getExistingFields,
			'fixedIds': fixedIds,
			'type'	  : getSelectedValue
		};

		// Ajax get the data based on the selected profile type.
		jQuery.ajax({
			type: 'GET',
			url: BP_Register.ajaxurl,
			data: data,
			success: function ( response ) {

				if ( response.success ) {
					firstCall = 1;

					getExistingFieldsSelector.val('');
					getExistingFieldsSelector.val( response.data.field_ids );
					appendHtmlDiv.append( response.data.field_html );

					var divList = jQuery( 'body .layout-wrap #profile-details-section > .editfield' );
					divList.sort(function(a, b){
						return jQuery(a).data('index' ) - jQuery(b).data('index' );
					});

					jQuery( '.layout-wrap #profile-details-section' ).html( divList );
					jQuery( 'body .layout-wrap #profile-details-section' ).append( existsField );
					existsField.val( response.data.field_ids );

					jQuery('.register-section textarea.wp-editor-area').each(function() {
						// Remove older html structure to resolve conflict.
						wp.editor.remove( jQuery(this).attr('id') );
						wp.editor.initialize( jQuery(this).attr('id'), {
							tinymce: {
								wpautop: true,
								branding: false,
								menubar:false,
								statusbar: true,
								elementpath: true,
								plugins: 'lists fullscreen link',
								toolbar1: 'bold italic underline blockquote strikethrough bullist numlist alignleft aligncenter alignright undo redo link fullscreen',
								setup: function (editor) {
									editor.on('change', function () {
										editor.save();
									});
								}
							},
							quicktags: {
								buttons: 'strong,em,link,block,del,ins,img,ul,ol,li,code,more,close'
							}
						} );
					});
				}
			}
		});
	}

	// Profile Type field select box change action.
	jQuery( document ).on( 'change', 'body #buddypress #register-page #signup-form .layout-wrap #profile-details-section .editfield fieldset select#' + BP_Register.field_id , function() {



		var registerSubmitButtonSelector = jQuery( 'body #buddypress #register-page #signup-form .submit #signup_submit' );
		registerSubmitButtonSelector.prop( 'disabled', true );

		if ( 1 === firstCall ) {
			//jQuery( 'body .ajax_added' ).remove();
			getExistingFieldsSelector.val( jQuery('.onloadfields').val() );
		}

		var getExistingFields = getExistingFieldsSelector.val();
		var getSelectedValue  = this.value;
		var appendHtmlDiv 	  = jQuery('.register-section.extended-profile');
		var fixedIds 		  = onLoadField.val();

		var data = {
			'action'  : 'xprofile_get_field',
			'_wpnonce': BP_Register.nonce,
			'fields'  : getExistingFields,
			'fixedIds': fixedIds,
			'type'	  : getSelectedValue,
			'prevId'  :prevField.val()
		};
		prevField.val(this.value);
		// Ajax get the data based on the selected profile type.
		jQuery.ajax({
			type: 'GET',
			url: BP_Register.ajaxurl,
			data: data,
			success: function ( response ) {

				if ( response.success ) {
					var exist_field_by = [];
					if ( existsField.val() ) {
						exist_field_by = existsField.val().split(',');
					}
					var new_data 	= response.data.field_ids.split(',');
					var difference = [];
					if ( exist_field_by ) {
						jQuery.grep( exist_field_by , function( el ) {
					        if (jQuery.inArray( el, new_data ) == -1){
				        		difference.push( el );
				        	}
						});

						if ( difference.length !== 0 ) {
							jQuery.each( difference , function( index, value ) {
							  	appendHtmlDiv.find( '.field_' + value ).remove();
							});
						}
					}
					registerSubmitButtonSelector.prop( 'disabled', false );

					firstCall = 1;

					getExistingFieldsSelector.val('');
					getExistingFieldsSelector.val( response.data.field_ids );
					appendHtmlDiv.append( response.data.field_html );

					var divList = jQuery( 'body .layout-wrap #profile-details-section > .editfield' );
					divList.sort(function(a, b){
						return jQuery(a).data('index' ) - jQuery(b).data('index' );
					});

					jQuery( '.layout-wrap #profile-details-section' ).html( divList );
					jQuery( 'body .layout-wrap #profile-details-section' ).append( existsField );
					existsField.val( response.data.field_ids );

					jQuery('.register-section textarea.wp-editor-area').each(function() {
						// Remove older html structure to resolve conflict.
						wp.editor.remove( jQuery(this).attr('id') );
						wp.editor.initialize( jQuery(this).attr('id'), {
							tinymce: {
								wpautop: true,
								branding: false,
								menubar:false,
								statusbar: true,
								elementpath: true,
								plugins: 'lists fullscreen link',
								toolbar1: 'bold italic underline blockquote strikethrough bullist numlist alignleft aligncenter alignright undo redo link fullscreen',
								setup: function (editor) {
									editor.on('change', function () {
										editor.save();
									});
								}
							},
							quicktags: {
								buttons: 'strong,em,link,block,del,ins,img,ul,ol,li,code,more,close'
							}
						} );
					});
				} else {
					registerSubmitButtonSelector.prop( 'disabled', false );
				}
			}
		});
	});

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
			jQuery( document ).find( signup_email_confirm ).before( html_error );
			return_val = false;
		}
		if ( jQuery( document ).find( signup_password ).length && jQuery( document ).find( signup_password ).val() == '' ) {
			jQuery( document ).find( signup_password ).before( html_error );
			return_val = false;
		}
		if ( jQuery( document ).find( signup_password_confirm ).length && jQuery( document ).find( signup_password_confirm ).val() == '' ) {
			jQuery( document ).find( signup_password_confirm ).before( html_error );
			return_val = false;
		}
		jQuery( '.required-field' ).each( function() {

			if ( jQuery( this ).find( 'input[type="text"]' ).length && jQuery( this ).find( 'input[type="text"] ').val() == '' ) {
				jQuery( this ).find( 'input[type="text"]' ).before( html_error );
				return_val = false;
			}
			if ( jQuery( this ).find( 'input[type="number"]' ).length && jQuery( this ).find( 'input[type="number"] ').val() == '' ) {
				jQuery( this ).find( 'input[type="number"]' ).before( html_error );
				return_val = false;
			}
			if ( jQuery( this ).find( 'textarea' ).length && jQuery( this ).find( 'textarea' ).val() == '' ) {
				jQuery( this ).find( 'textarea' ).before( html_error );
				return_val = false;
			}
			if ( jQuery( this ).find( 'select' ).length && jQuery( this ).find( 'select' ).val() == '' ) {
				jQuery( this ).find( 'select' ).before( html_error );
				return_val = false;
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
			jQuery( document ).find( signup_email ).before( html_error );
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
			    	if (response.signup_email) {
				    	var html_serror = '<div class="bp-messages bp-feedback error">';
							html_serror += '<span class="bp-icon" aria-hidden="true"></span>';
							html_serror += '<p>' + response.signup_email + '</p>';
							html_serror += '</div>';

                		jQuery( document ).find( signup_email ).before( html_serror );
                		return_val = false;
                	}
                	var nickname = 'field_'+response.field_id;
                	if (response.signup_username) {
                		var html_uerror = '<div class="bp-messages bp-feedback error">';
							html_uerror += '<span class="bp-icon" aria-hidden="true"></span>';
							html_uerror += '<p>' + response.signup_username + '</p>';
							html_uerror += '</div>';
                		jQuery( document ).find( '#'+nickname ).before( html_uerror );
                		return_val = false;
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
				return;
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
			errorMessageSelector.addClass( 'show mismatch' ).html( BP_Register.mismatch_email );
			return;
		}
	}

} );
