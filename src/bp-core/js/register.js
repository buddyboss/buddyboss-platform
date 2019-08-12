/* jshint devel: true */
/* global BP_REGISTER */

jQuery( document ).ready( function() {

	// Get Existing Register page field ids.
	var getExistingFieldsSelector = jQuery('#signup_profile_field_ids');

	// Add new hidden field for keep existing field to add again in change profile type action.
	var hiddenField  = jQuery("<input type=\"hidden\" class=\"onloadfields\" value='' />");
	var tinyMceAdded = 0;
	var onLoadField  = jQuery('.onloadfields');
	var newField     = jQuery('body .newely_added');

	// Append new field to body.
	jQuery('body').append(hiddenField);

	// Add existing profile fields ids to new hidden field value.
	onLoadField.val( getExistingFieldsSelector.val() );

	// Profile Type field select box change action.
	jQuery( '#' + BP_REGISTER.field_id ).on('change', function() {

		// On change set the older fields value to #signup_profile_field_ids hidden field
		if ( newField.length ) {

			// Remove the new field.
			newField.remove();
			getExistingFieldsSelector.val( jQuery('.onloadfields').val() );
		}

		var getExistingFields = getExistingFieldsSelector.val();
		var getSelectedValue  = this.value;
		var appendHtmlDiv 	  = jQuery('.register-section.extended-profile');
		var fixedIds 		  = onLoadField.val();

		// Ajax get the data based on the selected profile type.
		jQuery.post(
			BP_REGISTER.ajaxurl, {
				action: BP_REGISTER.action,
				'fields': getExistingFields,
				'fixedIds': fixedIds,
				'tinymce': tinyMceAdded,
				'type': getSelectedValue
			},
			function ( response ) {

				var responseArr = jQuery.parseJSON( response );

				if ( true === parseInt( responseArr[ 'field_html' ] ) ) {
					tinyMceAdded = 1;
				}

				getExistingFieldsSelector.val('');
				getExistingFieldsSelector.val(responseArr[ 'field_ids' ] );
				appendHtmlDiv.append( responseArr[ 'field_html' ] );
				if ( typeof( tinymce ) !== 'undefined' ) {
					tinymce.init(
						{
							selector: 'textarea',
							menubar:false,
							statusbar: false,
							format: {
								removeformat: [
									{selector: 'b,strong,em,i,font,u,strike', remove : 'all', split : true, expand : false, block_expand: true, deep : true},
								]
							}
						}
					);
					//tinyMCE.execCommand("mceRepaint");
				}
			}
		);
	});
} );
