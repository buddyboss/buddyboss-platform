/* global BP_ADMIN */
jQuery( document ).ready( function ( $ ) {
	$( document ).on( 'click', '.bp-hide-request, .bp-block-user', function () {
		if ( !confirm( Bp_Moderation.strings.confirm_msg ) ) {
			return false;
		}

		$( '.bp-moderation-ajax-msg p' ).text('').parent.addClass('hidden');

		var curObj = $( this );
		curObj.addClass( 'disabled' );
		var id = curObj.attr( 'data-id' );
		var type = curObj.attr( 'data-type' );
		var nonce = curObj.attr( 'data-nonce' );
		var sub_action = curObj.attr( 'data-action' );
		var data = {
			action: 'bp_moderation_content_actions_request',
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
					curObj.attr( 'title', Bp_Moderation.strings.unhide_label );
					if ( curObj.hasClass( 'single-report-btn' ) && 'user' !== type ) {
						curObj.text( Bp_Moderation.strings.unhide_label );
					} else if ( 'user' === type && curObj.hasClass( 'content-author' ) ) {
						curObj.text( Bp_Moderation.strings.unsuspend_author_label );
					} else if ( curObj.hasClass( 'single-report-btn' ) ) {
						curObj.text( Bp_Moderation.strings.unsuspend_member_label );
					}
				} else if ( 'unhide' === sub_action ) {
					curObj.attr( 'data-action', 'hide' );
					curObj.attr( 'title', Bp_Moderation.strings.hide_label );
					if ( curObj.hasClass( 'single-report-btn' ) && 'user' !== type ) {
						curObj.text( Bp_Moderation.strings.hide_label );
					} else if ( 'user' === type && curObj.hasClass( 'content-author' ) ) {
						curObj.text( Bp_Moderation.strings.suspend_author_label );
					} else if ( curObj.hasClass( 'single-report-btn' ) ) {
						curObj.text( Bp_Moderation.strings.suspend_member_label );
					}
				}
				$( '.bp-moderation-ajax-msg p' ).text( result.message ).parent().removeClass('hidden');
			} else {
				$( '.bp-moderation-ajax-msg p' ).text( result.message.errors.bp_moderation_content_actions_request ).parent().removeClass('hidden');
			}
			curObj.removeClass( 'disabled' );
		} );
	} );
} );
