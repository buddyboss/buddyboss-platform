/* jshint devel: true */
/* global BP_Confirm */

jQuery( document ).ready( function() {
	var getExistingFieldsSelector = jQuery('#signup_profile_field_ids');
	var hiddenField = jQuery("<input type=\"hidden\" class=\"onloadfields\" value='' />");
	var tinyMceAdded = 0;
	jQuery('body').append(hiddenField);
	jQuery('.onloadfields').val( getExistingFieldsSelector.val() );
	jQuery( '#' + BP_REGISTER.field_id ).on('change', function() {

		if ( jQuery('body .newely_added').length ) {
			jQuery('body .newely_added').remove();
			getExistingFieldsSelector.val( jQuery('.onloadfields').val() );
		}

		var getExistingFields = getExistingFieldsSelector.val();
		var getSelectedValue = this.value;
		var appendHtmlDiv = jQuery('.register-section.extended-profile');
		var fixedIds = jQuery('.onloadfields').val();
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
				//console.log( responseArr[ 'field_ids' ] );
				//console.log( responseArr[ 'field_html' ] );

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
