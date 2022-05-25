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

	var tinyMceAdded = 0;
	var onLoadField  = jQuery('body .onloadfields');
	var firstCall    = 0;

	// Add existing profile fields ids to new hidden field value.
	onLoadField.val( getExistingFieldsSelector.val() );

	if ( typeof window.tinymce !== 'undefined' ) {
		window.tinymce.remove('textarea');
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
			'tinymce' : tinyMceAdded,
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

					if ( true === parseInt( response.data.field_html ) ) {
						tinyMceAdded = 1;
					}

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

					if ( typeof window.tinymce !== 'undefined' ) {

						window.tinymce.remove('textarea');

						window.tinymce.init(
							{
								selector: 'textarea.wp-editor-area',
								branding: false,
								menubar:false,
								statusbar: false,
								plugins: 'lists fullscreen link',
								toolbar: ' bold italic underline blockquote strikethrough bullist numlist alignleft aligncenter alignright undo redo link fullscreen',

							}
						);
						window.tinymce.execCommand('mceRepaint');
					}
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
			'tinymce' : tinyMceAdded,
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

					if ( true === parseInt( response.data.field_html ) ) {
						tinyMceAdded = 1;
					}

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

					if ( typeof window.tinymce !== 'undefined' ) {

						window.tinymce.remove('textarea');

						window.tinymce.init(
							{
								selector: 'textarea.wp-editor-area',
								branding: false,
								menubar:false,
								statusbar: false,
								plugins: 'lists fullscreen link',
								toolbar: ' bold italic underline blockquote strikethrough bullist numlist alignleft aligncenter alignright undo redo link fullscreen',

							}
						);

						window.tinymce.on('init', function () {
							if (window.tinymce.inline) {
								window.tinymce.execCommand('mceRepaint');
							}
						});
					}
				} else {
					registerSubmitButtonSelector.prop( 'disabled', false );
				}
			}
		});
	});

} );
