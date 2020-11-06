/* global BP_ADMIN */
jQuery( document ).ready( function ( $ ) {
	$( document ).on( 'click', '.bp-hide-request, .bp-block-user', function () {
		if ( !confirm( 'Are you sure you?' ) ) { //Todo: need to add translated message
			return false;
		}
		var curObj = $( this );
		var id = curObj.attr( 'data-id' );
		var type = curObj.attr( 'data-type' );
		var nonce = curObj.attr( 'data-nonce' );
		var sub_action = curObj.attr( 'data-action' );
		var data = {
			action: 'bp_moderation_hide_request',
			id: id,
			type: type,
			sub_action: sub_action,
			nonce: nonce,
		};
		$.post( ajaxurl, data, function ( response ) {
			var result = $.parseJSON( response );
			if ( true === result.success ) {
				if ( 'hide' === sub_action ) {
					curObj.attr( 'data-action', 'unhide' );
					curObj.attr( 'title', 'Unhide' ); //todo: add translated lable
				} else if ( 'unhide' === sub_action ) {
					curObj.attr( 'data-action', 'hide' );
					curObj.attr( 'title', 'Hide' ); //todo: add translated lable
				}
			} else {
				alert( result.message );
			}
		} );
	} );
} );