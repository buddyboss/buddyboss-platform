/* jshint devel: true */
/* global BP_Register */

jQuery( document ).ready( function() {

	// Get Existing Register page field ids.
	var getExistingFieldsSelector = jQuery('#signup_profile_field_ids');

	// Add new hidden field for keep existing field to add again in change profile type action.
	var hiddenField  = jQuery("<input type=\"hidden\" class=\"onloadfields\" value='' />");

	// Append new field to body.
	jQuery('body').append(hiddenField);

	var tinyMceAdded = 0;
	var onLoadField  = jQuery('body .onloadfields');
	var firstCall    = 0;

	// Add existing profile fields ids to new hidden field value.
	onLoadField.val( getExistingFieldsSelector.val() );

	if ( typeof( tinymce ) !== 'undefined' ) {
		tinymce.remove('textarea');
	}

	// Profile Type field select box change action.
	jQuery( document ).on( 'change', 'body #buddypress #register-page #signup-form .layout-wrap #profile-details-section .editfield fieldset select#' + BP_Register.field_id , function() {

		if ( 1 === firstCall ) {
			jQuery( 'body .ajax_added' ).remove();
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

					var divList = jQuery( 'body #profile-details-section > .editfield' );
					divList.sort(function(a, b){
						return jQuery(a).data('index' ) - jQuery(b).data('index' );
					});

					jQuery( '#profile-details-section' ).html( divList );

					if ( typeof( tinymce ) !== 'undefined' ) {

						tinymce.remove('textarea');

						tinymce.init(
							{
								selector: 'textarea',
								branding: false,
								menubar:false,
								statusbar: false,
								plugins: 'lists fullscreen link',
								toolbar: ' bold italic underline blockquote strikethrough bullist numlist alignleft aligncenter alignright undo redo link fullscreen',

							}
						);
						tinyMCE.execCommand('mceRepaint');
					}
				}
			}
		});
	});
} );
