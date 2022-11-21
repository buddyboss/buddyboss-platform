/* global Bp_Moderation, BP_ADMIN */
jQuery( document ).ready(
	function ($) {

		/**
		 * Function for hide/unhide content on frontend from backend listing.
		 */
		$( document ).on(
			'click',
			'.bp-hide-request',
			function (event) {
				event.preventDefault();

				var curObj     = $( this );
				var sub_action = curObj.attr( 'data-action' );

				if ('hide' === sub_action) {
					if ( ! confirm( Bp_Moderation.strings.confirm_msg )) {
						return false;
					}
				} else if ('unhide' === sub_action) {
					if ( ! confirm( Bp_Moderation.strings.unhide_confirm_msg )) {
						return false;
					}
				}

				$( '.bp-moderation-ajax-msg p' ).text( '' ).parent().addClass( 'hidden' );

				curObj.addClass( 'disabled' );
				var id    = curObj.attr( 'data-id' );
				var type  = curObj.attr( 'data-type' );
				var nonce = curObj.attr( 'data-nonce' );
				var data  = {
					action: 'bp_moderation_content_actions_request',
					id: id,
					type: type,
					sub_action: sub_action,
					nonce: nonce,
				};

				$( event.currentTarget ).append( ' <i class="bb-icon-l bb-icon-spinner animate-spin"></i>' );

				$.post(
					ajaxurl,
					data,
					function (response) {
						var hideArg = '';
						var url     = window.location.href;
						if (true === response.success) {
							if ('hide' === sub_action) {
								curObj.addClass( 'green' );
								curObj.attr( 'data-action', 'unhide' );
								curObj.attr( 'title', Bp_Moderation.strings.unhide_label );
								curObj.text( Bp_Moderation.strings.unhide_label );
								hideArg = 'hidden';
							} else if ('unhide' === sub_action) {
								curObj.removeClass( 'green' );
								curObj.attr( 'data-action', 'hide' );
								curObj.attr( 'title', Bp_Moderation.strings.hide_label );
								curObj.text( Bp_Moderation.strings.hide_label );
								hideArg = 'unhide';
							}

							if (url.indexOf( '?' ) > -1) {
								url += '&' + hideArg + '=1';
							} else {
								url += '?' + hideArg + '=1';
							}
							window.location.href = url;
						} else {
							var msg = '';
							if (response.data.message.errors.bp_moderation_missing_data) {
								msg = response.data.message.errors.bp_moderation_missing_data;
							} else if (response.data.message.errors.bp_moderation_content_actions_request) {
								msg = response.data.message.errors.bp_moderation_content_actions_request;
							} else if (response.data.message.errors.bp_moderation_invalid_access) {
								msg = response.data.message.errors.bp_moderation_invalid_access;
							}
							$( '.bp-moderation-ajax-msg' ).removeClass( 'notice-success' ).addClass( 'notice-error' );
							$( '.bp-moderation-ajax-msg p' ).text( msg ).parent().removeClass( 'hidden' );
							$( event.currentTarget ).find( '.bb-icon-loader' ).remove();
						}
						curObj.removeClass( 'disabled' );
					}
				);
			}
		);

		/**
		 * Function for suspend/unsuspend user from backend listing.
		 */
		$( document ).on(
			'click',
			'.bp-block-user',
			function (event) {
				event.preventDefault();
				var curObj     = $( this );
				var sub_action = curObj.attr( 'data-action' );

				if ('suspend' === sub_action) {
					if ( ! confirm( BP_ADMIN.moderation.suspend_confirm_message )) {
						return false;
					}
				} else if ('unsuspend' === sub_action) {
					if ( ! confirm( BP_ADMIN.moderation.unsuspend_confirm_message )) {
						return false;
					}
				}

				$( '.bp-moderation-ajax-msg p' ).text( '' ).parent().addClass( 'hidden' );

				curObj.addClass( 'disabled' );
				var id    = curObj.attr( 'data-id' );
				var type  = curObj.attr( 'data-type' );
				var nonce = curObj.attr( 'data-nonce' );
				var data  = {
					action: 'bp_moderation_user_actions_request',
					id: id,
					type: type,
					sub_action: sub_action,
					nonce: nonce,
				};

				$( event.currentTarget ).append( ' <i class="bb-icon-l bb-icon-spinner animate-spin"></i>' );

				$.post(
					ajaxurl,
					data,
					function (response) {
						var hideArg = '';
						if (true === response.success) {
							var url = window.location.href;
							if ('suspend' === sub_action) {
								curObj.attr( 'data-action', 'unsuspend' );
								curObj.addClass( 'green' );
								if (curObj.hasClass( 'content-author' )) {
									curObj.text( Bp_Moderation.strings.unsuspend_author_label );
									curObj.attr( 'title', Bp_Moderation.strings.unsuspend_author_label );
								} else {
									curObj.text( Bp_Moderation.strings.unsuspend_label );
									curObj.attr( 'title', Bp_Moderation.strings.unsuspend_label );
								}
								hideArg = 'suspended';
							} else if ('unsuspend' === sub_action) {
								curObj.attr( 'data-action', 'suspend' );
								curObj.removeClass( 'green' );
								if (curObj.hasClass( 'content-author' )) {
									curObj.text( Bp_Moderation.strings.suspend_author_label );
									curObj.attr( 'title', Bp_Moderation.strings.suspend_author_label );
								} else {
									curObj.text( Bp_Moderation.strings.suspend_label );
									curObj.attr( 'title', Bp_Moderation.strings.suspend_label );
								}
								hideArg = 'unsuspended';
							}

							if (url.indexOf( '?' ) > -1) {
								url += '&' + hideArg + '=1';
							} else {
								url += '?' + hideArg + '=1';
							}
							window.location.href = url;
						} else {
							var msg = '';
							if (response.data.message.errors.bp_moderation_user_missing_data) {
								msg = response.data.message.errors.bp_moderation_user_missing_data;
							} else if (response.data.message.errors.bp_moderation_invalid_access) {
								msg = response.data.message.errors.bp_moderation_invalid_access;
							}
							$( '.bp-moderation-ajax-msg' ).removeClass( 'notice-success' ).addClass( 'notice-error' );
							$( '.bp-moderation-ajax-msg p' ).text( msg ).parent().removeClass( 'hidden' );
							$( event.currentTarget ).find( '.bb-icon-loader' ).remove();
						}
						curObj.removeClass( 'disabled' );
					}
				);
			}
		);
	}
);
