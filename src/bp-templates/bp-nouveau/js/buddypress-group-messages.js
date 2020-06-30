/* global wp, bp, BP_Nouveau, _ */
/* @version 3.0.0 */
window.wp = window.wp || {};
window.bp = window.bp || {};

( function( exports, $ ) {

	// Bail if not set
	if ( typeof BP_Nouveau === 'undefined' ) {
		return;
	}

	_.extend( bp, _.pick( wp, 'Backbone', 'ajax', 'template' ) );

	bp.Models      = bp.Models || {};
	bp.Collections = bp.Collections || {};
	bp.Views       = bp.Views || {};

	bp.Nouveau = bp.Nouveau || {};

	bp.Models.ACReply = Backbone.Model.extend(
		{
			defaults: {
				gif_data: {}
			}
		}
	);

	/**
	 * [Nouveau description]
	 *
	 * @type {Object}
	 */
	bp.Nouveau.GroupMessages = {
		/**
		 * [start description]
		 *
		 * @return {[type]} [description]
		 */
		start: function() {
			this.views = new Backbone.Collection();
			this.displayFeedback( BP_Nouveau.group_messages.invites_form_all, 'info' );
			this.mediumEditor = false;

			this.setupGlobals();

			var $group_messages_select = $( 'body' ).find( '#group-messages-send-to-input' );
			var page 				   = 1;

			// Activate bp_mentions
			this.addSelect2( $group_messages_select );
			this.activateTinyMce();

			var feedbackParagraphTagSelectorLeft = $( '#group-messages-container .bb-groups-messages-left .group-messages-members-listing .bp-messages-feedback .bp-feedback p' );
			var memberListUl 					 = $( '.group-messages-members-listing #members-list' );
			var memberListUlLast 				 = $( '.group-messages-members-listing .last' );
			var memberTotalText 				 = $( '#item-body #group-messages-container .bb-groups-messages-left .total-members-text' );
			var searchText 						 = $( '#item-body #group-messages-container .bb-groups-messages-left #group_messages_search' ).val();

			$( '#item-body #group-messages-container .bb-groups-messages-right #send_group_message_form .bb-groups-messages-right-top .select2-container .selection .select2-selection--multiple .select2-selection__rendered .select2-search--inline .select2-search__field' ).prop( 'disabled', true );

			$( document ).on(
				'click',
				'#item-body #group-messages-container .bb-groups-messages-right #send_group_message_form .bb-groups-messages-right-top .select2-container .selection .select2-selection--multiple .select2-selection__rendered .select2-search--inline .select2-search__field',
				function() {
					$( this ).prop( 'disabled', true );
				}
			);

			memberListUl.scroll( this.loadMoreMessageMembers );

			$group_messages_select.on(
				'select2:unselect',
				function(e) {
					var data = e.params.data;
					$( '#group-messages-send-to-input option[value="' + data.id + '"]' ).each(
						function() {
							$( this ).remove();
						}
					);
					$( '#item-body #group-messages-container .bb-groups-messages-left #members-list li.' + data.id ).removeClass( 'selected' );
					$( '#item-body #group-messages-container .bb-groups-messages-left #members-list li.' + data.id + ' .action button' ).attr( 'data-bp-tooltip', BP_Nouveau.group_messages.add_recipient );
				}
			);

			$group_messages_select.select2().prop( 'disabled', true );
			$( '.bp-select-members-wrap .select2-selection__choice__remove' ).hide();

			var data = {
				'action': 'groups_get_group_members_listing',
				'nonce' : BP_Nouveau.group_messages.nonces.retrieve_group_members,
				'group' : BP_Nouveau.group_messages.group_id,
				'type'  : 'all',
				'page'  : page
			};

			$.ajax(
				{
					type: 'POST',
					url: BP_Nouveau.ajaxurl,
					async: false,
					data: data,
					success: function (response) {
						if ( response.success && 'no_member' !== response.data.results ) {
							// memberListUl.html('');
							memberListUl.html( response.data.results );
							$( '.group-messages-members-listing #members-list li .action' ).hide();
							memberListUlLast.html( '' );
							memberListUlLast.html( response.data.pagination );
							$( '#item-body #group-messages-container .bb-groups-messages-left .bp-messages-feedback' ).hide();
							memberTotalText.html( '' );
							memberTotalText.html( response.data.total_count );
						} else if ( response.success && 'no_member' === response.data.results ) {
							$( '#group-messages-container .bb-groups-messages-right .bp-messages-feedback' ).removeClass( 'bp-messages-feedback-hide' );
							$( '#group-messages-container .bb-groups-messages-right .bp-messages-feedback .bp-feedback' ).addClass( 'feedback' );
							$( '#group-messages-container .bb-groups-messages-right .bp-messages-feedback .bp-feedback p' ).html( BP_Nouveau.group_messages.group_no_member );
							var feedbackNotice = $( '#item-body .bp-messages-feedback' ).html();
							$( '#item-body' ).html( '' );
							$( '#item-body' ).html( feedbackNotice );
						} else {
							$( '.group-messages-members-listing #members-list' ).html( '' );
							memberListUlLast.html( '' );
							$( '#group-messages-container .bb-groups-messages-left .bp-messages-feedback .bp-feedback' ).addClass( 'error' );
							feedbackParagraphTagSelectorLeft.html( BP_Nouveau.group_messages.no_member );
						}

					}
				}
			);

			$( '.bb-groups-messages-left-inner .bb-panel-head input#bp-group-message-switch-checkbox' ).on(
				'change',
				function () {

					// $( '#group-messages-container .bb-groups-messages-left .bb-groups-messages-left-inner .bb-panel-head #bp-message-dropdown-options' ).removeClass( 'bp-message-dropdown-options-hide' );

					page = 1;
					var valueSelected;
					// Reset select2
					$group_messages_select.val( '' ).trigger( 'change' );
					if ( $( this ).is( ':checked' ) ) {
						valueSelected = 'all';
					} else {
						valueSelected = 'single';
					}

					if ( valueSelected ) {
						if ( 'all' === valueSelected ) {
							// $('.bb-groups-messages-left').removeClass('bb-select-member-view');
							$( '#group-messages-send-to-input option' ).each(
								function() {
									$( this ).remove();
								}
							);
							$group_messages_select.append( $( '<option>' ).val( BP_Nouveau.group_messages.select_default_value ).text( BP_Nouveau.group_messages.select_default_text ) );
							$group_messages_select.select2().prop( 'disabled', true );
							$group_messages_select.select2( 'data', { id: BP_Nouveau.group_messages.select_default_value, text: BP_Nouveau.group_messages.select_default_text } );
							$group_messages_select.val( 'all' ).trigger( 'change' );
							$( '.group-messages-members-listing #members-list li .action' ).hide();
							$( '.group-messages-members-listing #members-list li' ).removeClass( 'selected' );
							if ( $( '#item-body #group-messages-container .bb-groups-messages-right .remove-after-few-seconds' ).length === 0 ) {
								$( '.bb-groups-messages-right .bp-messages-feedback' ).show();
								$( '.bb-groups-messages-right .bp-messages-feedback .bp-feedback p' ).html( BP_Nouveau.group_messages.feedback_select_all );
							}
							$( '.bp-select-members-wrap .select2-selection__choice__remove' ).hide();
						} else {
							$( '.bb-groups-messages-right .bp-messages-feedback .bp-feedback p' ).html( BP_Nouveau.group_messages.feedback_individual );
							$( '.bb-groups-messages-right .bp-messages-feedback' ).show();
							$( '#group-messages-send-to-input option[value="all"]' ).each(
								function() {
									$( this ).remove();
								}
							);
							$group_messages_select.select2().prop( 'disabled', false );
							$( '#group-messages-container .bb-groups-messages-right #send_group_message_form .bb-groups-messages-right-top .select2-container .selection .select2-selection--multiple .select2-selection__rendered .select2-search--inline .select2-search__field' ).prop( 'disabled', true );
							$( '.group-messages-members-listing #members-list li .action' ).show();
							$( '.bp-select-members-wrap .select2-selection__choice__remove' ).show();
						}
					}
				}
			);

			$( document ).on(
				'click',
				'#item-body #group-messages-container .bb-groups-messages-left #members-list li.load-more .group-message-load-more-button',
				function() {
					$( '#group-messages-container .group-messages-members-listing .last #bp-group-messages-next-page' ).trigger( 'click' );
				}
			);
			$( document ).on(
				'click',
				'#item-body #group-messages-container .bb-groups-messages-left #members-list li .action .group-add-remove-invite-button',
				function() {

					var userId   = $( this ).attr( 'data-bp-user-id' );
					var userName = $( this ).attr( 'data-bp-user-name' );

					var data = {
						id: userId,
						text: userName
					};

					if ( $( this ).closest( 'li' ).hasClass( 'selected' ) ) {

						$( this ).closest( 'li' ).removeClass( 'selected' );

						var newArray = [];
						var newData  = $.grep(
							$group_messages_select.select2( 'data' ),
							function (value) {
								return value['id'] != userId; // jshint ignore:line
							}
						);

						newData.forEach(
							function(data) {
								newArray.push( +data.id );
							}
						);

						$group_messages_select.val( newArray ).trigger( 'change' );

						$( '#group-messages-send-to-input option[value="' + userId + '"]' ).each(
							function() {
								$( this ).remove();
							}
						);

						$( this ).attr( 'data-bp-tooltip', BP_Nouveau.group_messages.add_recipient );

					} else {
						$( this ).closest( 'li' ).addClass( 'selected' );
						if ( ! $group_messages_select.find( "option[value='" + data.id + "']" ).length ) { // jshint ignore:line
							var newOption = new Option( data.text, data.id, true, true );
							$group_messages_select.append( newOption ).trigger( 'change' );
						}
						$( this ).attr( 'data-bp-tooltip', BP_Nouveau.group_messages.remove_recipient );
					}

				}
			);

			$( document ).on(
				'click',
				'#item-body #group-messages-container .bb-groups-messages-left .last #bp-group-messages-next-page',
				function() {
					// $( '#item-body #group-messages-container .bb-groups-messages-left .bp-messages-feedback').show();
					// $( '#item-body #group-messages-container .bb-groups-messages-left .bp-messages-feedback .bp-feedback').addClass( 'info' );
					// feedbackParagraphTagSelectorLeft.html( BP_Nouveau.group_messages.loading );
					page 	 = page + 1;
					var type = '';
					if ( $( '#bp-group-message-switch-checkbox' ).is( ':checked' ) ) {
						type = 'all';
					} else {
						type = 'individual';
					}
					var data = {
						'action': 'groups_get_group_members_listing',
						'nonce' : BP_Nouveau.group_messages.nonces.retrieve_group_members,
						'group' : BP_Nouveau.group_messages.group_id,
						'type'  : type,
						'page'  : page
					};
					$.ajax(
						{
							type: 'POST',
							url: BP_Nouveau.ajaxurl,
							data: data,
							success: function (response) {
								if ( response.success && 'no_member' !== response.data.results ) {
									$( '#group-messages-container .group-messages-members-listing #members-list li.load-more' ).remove();
									memberListUl.append( response.data.results );
									memberListUlLast.html( '' );
									memberListUlLast.html( response.data.pagination );
									// page = response.data.page;
									$( '#item-body #group-messages-container .bb-groups-messages-left .bp-messages-feedback' ).hide();
									if ( 'all' === type ) {
										$( '.group-messages-members-listing #members-list li .action' ).hide();
									} else {
										$( '.group-messages-members-listing #members-list li .action' ).show();
									}
								} else {
									memberListUl.html( '' );
									memberListUlLast.html( '' );
									$( '#group-messages-container .bb-groups-messages-left .bp-messages-feedback .bp-feedback' ).addClass( 'error' );
									feedbackParagraphTagSelectorLeft.html( BP_Nouveau.group_messages.no_member );
								}
							}
						}
					);
				}
			);

			$( document ).on(
				'click',
				'#item-body #group-messages-container .bb-groups-messages-left .last #bp-group-messages-prev-page',
				function() {
					$( '#item-body #group-messages-container .bb-groups-messages-left .bp-messages-feedback' ).show();
					$( '#item-body #group-messages-container .bb-groups-messages-left .bp-messages-feedback .bp-feedback' ).addClass( 'info' );
					feedbackParagraphTagSelectorLeft.html( BP_Nouveau.group_messages.loading );
					page 	 = page - 1;
					var type = '';
					if ( $( '#bp-group-message-switch-checkbox' ).is( ':checked' ) ) {
						type = 'all';
					} else {
						type = 'individual';
					}
					var data = {
						'action': 'groups_get_group_members_listing',
						'nonce' : BP_Nouveau.group_messages.nonces.retrieve_group_members,
						'group' : BP_Nouveau.group_messages.group_id,
						'type'  : type,
						'page'  : page
					};

					$.ajax(
						{
							type: 'POST',
							url: BP_Nouveau.ajaxurl,
							data: data,
							success: function (response) {
								if ( response.success && 'no_member' !== response.data.results ) {
									memberListUl.html( '' );
									memberListUl.html( response.data.results );
									memberListUlLast.html( '' );
									memberListUlLast.html( response.data.pagination );
									$( '#item-body #group-messages-container .bb-groups-messages-left .bp-messages-feedback' ).hide();
									// page = response.data.page;
								} else {
									memberListUl.html( '' );
									memberListUlLast.html( '' );
									$( '#group-messages-container .bb-groups-messages-left .bp-messages-feedback .bp-feedback' ).addClass( 'error' );
									feedbackParagraphTagSelectorLeft.html( BP_Nouveau.group_messages.no_member );
								}
							}
						}
					);
				}
			);

			$( document ).on(
				'click',
				'#item-body #group-messages-container .bb-groups-messages-left #group_messages_search',
				function() {

					if ( '' === searchText ) {
						return false;
					}

					var type = '';
					if ( $( '#bp-group-message-switch-checkbox' ).is( ':checked' ) ) {
						type = 'all';
					} else {
						type = 'individual';
					}

					page = 1;

					var data = {
						'action': 'groups_get_group_members_listing',
						'nonce' : BP_Nouveau.group_messages.nonces.retrieve_group_members,
						'group' : BP_Nouveau.group_messages.group_id,
						'type'  : searchText,
						'term'  : searchText,
						'page'  : page
					};

					$.ajax(
						{
							type: 'POST',
							url: BP_Nouveau.ajaxurl,
							data: data,
							success: function (response) {
								if ( response.success && 'no_member' !== response.data.results ) {
									memberListUl.html( '' );
									memberListUl.html( response.data.results );
									memberListUlLast.html( '' );
									memberListUlLast.html( response.data.pagination );
									$( '#item-body #group-messages-container .bb-groups-messages-left .bp-messages-feedback' ).hide();
									// page = response.data.page;
								} else {
									memberListUl.html( '' );
									memberListUlLast.html( '' );
									$( '#group-messages-container .bb-groups-messages-left .bp-messages-feedback .bp-feedback' ).addClass( 'error' );
									feedbackParagraphTagSelectorLeft.html( BP_Nouveau.group_messages.no_member );
								}
								if ( 'all' === type ) {
									$( '.group-messages-members-listing #members-list li .action' ).hide();
								} else {
									$( '.group-messages-members-listing #members-list li .action' ).show();
								}
							}
						}
					);
				}
			);

			$( document ).on(
				'click',
				'#group-messages-container .group-messages-compose',
				function( e ) {
					e.preventDefault();
					$( '#item-body #group-messages-container .bb-groups-messages-right-top .group-messages-compose' ).hide();
					$( '#item-body #group-messages-container .bb-groups-messages-left' ).show();
					$( '#item-body #group-messages-container .bb-groups-messages-right .remove-after-few-seconds' ).remove();
					$( '#item-body .bb-groups-messages-right-top .bp-messages-feedback' ).show();
					$( '#item-body #group-messages-container .bb-groups-messages-right .select2-container' ).show();
					$( '#item-body #group-messages-container .bb-groups-messages-right .bb-groups-messages-right-bottom' ).show();
					$( '#item-body #group-messages-container .bb-groups-messages-right .bb-groups-messages-right-bottom .group-messages-type' ).val( 'open' );

					if ( $( '#bp-group-message-switch-checkbox' ).is( ':checked' ) ) {
						$( '.bb-groups-messages-right .bp-messages-feedback .bp-feedback p' ).html( BP_Nouveau.group_messages.feedback_select_all );
					}
				}
			);

			$( document ).on(
				'click',
				'#group-messages-container #send_group_message_button',
				function( e ) {
					e.preventDefault();
					var user, type;
					var users_list = [];
					var checkbox   = $( '#bp-group-message-switch-checkbox' ).is( ':checked' );
					if ( checkbox ) {
						user 	   = 'all';
						users_list = [];
					} else {
						user        = 'individual';
						var newData = $.grep(
							$group_messages_select.select2( 'data' ),
							function (value) {
								return value['id'] != 0; // jshint ignore:line
							}
						);

						newData.forEach(
							function(data) {
								users_list.push( +data.id );
							}
						);
					}

					if ( 'open' === $( '.group-messages-type :selected' ).val() ) {
						type = 'open';
					} else {
						type = 'private';
					}

					var content = '';
					var editor  = '';
					if ( typeof window.group_messages_editor !== 'undefined' ) {
						editor = window.group_messages_editor;
					}

					content = editor.getContent();
					if ( editor && $.trim( editor.getContent().replace( '<p><br></p>','' ) ) === '' ) {
						content = '';
					} else if ( ! editor && $.trim( $( '#item-body #group-messages-container .bb-groups-messages-right #send_group_message_form' ).find( '#group_message_content' ).val() ) === '' ) {
						content = '';
					}

					var media   	   = $( '#item-body #group-messages-container .bb-groups-messages-right #send_group_message_form .bb-groups-messages-right-bottom #bp_group_messages_media' ).val();
					var document   	   = $( '#item-body #group-messages-container .bb-groups-messages-right #send_group_message_form .bb-groups-messages-right-bottom #bp_group_messages_document' ).val();
					var gif     	   = $( '#item-body #group-messages-container .bb-groups-messages-right #send_group_message_form .bb-groups-messages-right-bottom #bp_group_messages_gif' ).val();
					var contentError   = $( '#item-body #group-messages-container .bb-groups-messages-right #send_group_message_form .bb-groups-messages-right-top .bp-messages-feedback .bp-feedback-content-no-error' );
					var recipientError = $( '#item-body #group-messages-container .bb-groups-messages-right #send_group_message_form .bb-groups-messages-right-top .bp-messages-feedback .bp-feedback-recipient-no-error' );

					if ( '' === content && '' === media && '' === document && '' === gif ) {
						if ( ! contentError.length ) {
							var feedbackHtml = '<div class="bp-feedback error bp-feedback-content-no-error"><span class="bp-icon" aria-hidden="true"></span><p> ' + BP_Nouveau.group_messages.no_content + ' </p></div>';
							$( '#item-body #group-messages-container .bb-groups-messages-right #send_group_message_form .bb-groups-messages-right-top .bp-messages-feedback' ).append( feedbackHtml );
						}
						return false;
					} else {
						if ( contentError.length ) {
							contentError.remove();
						}
					}

					if ( ! checkbox && 0 === users_list.length ) {
						if ( ! recipientError.length ) {
							var recipientHtml = '<div class="bp-feedback error bp-feedback-content-no-error"><span class="bp-icon" aria-hidden="true"></span><p> ' + BP_Nouveau.group_messages.no_recipient + ' </p></div>';
							$( '#item-body #group-messages-container .bb-groups-messages-right #send_group_message_form .bb-groups-messages-right-top .bp-messages-feedback' ).append( recipientHtml );
						}
						return false;
					} else {
						if ( recipientError.length ) {
							recipientError.remove();
						}
					}

					var data = {
						'action'  	 	: 'groups_get_group_members_send_message',
						'nonce'   	 	: BP_Nouveau.group_messages.nonces.send_messages_users,
						'group'   	 	: BP_Nouveau.group_messages.group_id,
						'content' 	 	: window.group_messages_editor.getContent(),
						'media'   	 	: media,
						'document'   	: document,
						'users'   		: user,
						'users_list'    : users_list,
						'type'    		: type,
						'gif'     	 	: gif
					};

					$.ajax(
						{
							type: 'POST',
							url: BP_Nouveau.ajaxurl,
							data: data,
							success: function (response) {
								var dropzone_container  = $( 'div#bp-group-messages-post-media-uploader' );
								var containerAttachment = $( '#whats-new-attachments .bp-group-messages-attached-gif-container' );
								var inputHiddenGif 		= $( '#bp_group_messages_gif' );
								var feedbackSelector 	= $( '#item-body .bb-groups-messages-right-top .bp-messages-feedback' );

								if ( response.success ) {

									$( '#item-body #group-messages-container .bb-groups-messages-right .select2-container' ).hide();
									$( '#item-body #group-messages-container .bb-groups-messages-right .bb-groups-messages-right-bottom' ).hide();
									$( '#item-body #group-messages-container .remove-after-few-seconds' ).remove();
									$( '#item-body #group-messages-container .bb-groups-messages-left' ).hide();
									$( '#item-body #group-messages-container .bb-groups-messages-right-top .group-messages-compose' ).show();

									var feedbackHtmlSuccess  = '';
									var feedbackReplaceCount = response.data.feedback.replace( '%%count%%', users_list.length );
									feedbackHtmlSuccess      = '<div class="bp-feedback success bp-feedback-content-no-error remove-after-few-seconds"><span class="bp-icon" aria-hidden="true"></span><p> ' + feedbackReplaceCount + ' </p></div>';

									feedbackSelector.hide();
									feedbackSelector.after( feedbackHtmlSuccess );

									window.group_messages_editor.setContent( '' );
									if ( typeof window.Dropzone !== 'undefined' && dropzone_container.length ) {

										if ( bp.Nouveau.Media.dropzone_media.length ) {

											$( bp.Nouveau.Media.dropzone_media ).each(
												function( i ) {
													bp.Nouveau.Media.dropzone_media[i].saved = true;
												}
											);
										}

										$( '.dropzone' ).each(
											function () {
												var dropzoneControl = $( this )[0].dropzone;
												if (dropzoneControl) {
													dropzoneControl.destroy();
													dropzoneControl.dropzone_media = [];
												}
											}
										);
										$( 'div#bp-group-messages-post-media-uploader' ).html( '' );
										$( 'div#bp-group-messages-post-media-uploader' ).addClass( 'closed' ).removeClass( 'open' );

										$( 'div#bp-group-messages-post-document-uploader' ).html( '' );
										$( 'div#bp-group-messages-post-document-uploader' ).addClass( 'closed' ).removeClass( 'open' );
									}

									setTimeout(
										function() {
											// $( '#item-body #group-messages-container .bb-groups-messages-right .remove-after-few-seconds' ).remove();
											// $( '#item-body .bb-groups-messages-right-top .bp-messages-feedback' ).show();
											// $( '#item-body #group-messages-container .bb-groups-messages-right .select2-container' ).show();
											// $( '#item-body #group-messages-container .bb-groups-messages-right .bb-groups-messages-right-bottom' ).show();

											if ( ! $( '#bp-group-message-switch-checkbox' ).is( ':checked' ) ) {
												$( '#bp-group-message-switch-checkbox' ).trigger( 'click' );
											}

											// if ( $( '#bp-group-message-switch-checkbox' ).is( ':checked' ) ) {
											// $('.bb-groups-messages-right .bp-messages-feedback .bp-feedback p').html( BP_Nouveau.group_messages.feedback_select_all );
											// }

											$( '#item-body #group-messages-container .bb-groups-messages-right .select2-container' ).hide();
										},
										3000
									);

									if ( containerAttachment.length ) {
										$( '#whats-new-toolbar .bp-group-messages-attached-gif-container' ).parent().removeClass( 'open' );
										$( '#whats-new-toolbar #bp-group-messages-gif-button' ).removeClass( 'active' );
										containerAttachment.addClass( 'closed' );
										containerAttachment.find( '.gif-image-container img' ).attr( 'src', '' );
										containerAttachment[0].style = '';
										if (inputHiddenGif.length) {
											inputHiddenGif.val( '' );
										}
									}
									$( '#item-body #group-messages-container .bb-groups-messages-right .select2-container' ).hide();
								} else {
									$( '#item-body #group-messages-container .remove-after-few-seconds' ).remove();
									var feedbackHtml = '<div class="bp-feedback error bp-feedback-content-no-error remove-after-few-seconds"><span class="bp-icon" aria-hidden="true"></span><p> ' + response.data.feedback + ' </p></div>';
									feedbackSelector.after( feedbackHtml );
									feedbackSelector.hide();
									setTimeout(
										function() {
											$( '.remove-after-few-seconds' ).hide();
											$( '#item-body .bb-groups-messages-right-top .bp-messages-feedback' ).removeAttr( 'style' );
										},
										3000
									);
								}
							}
						}
					);

				}
			);

			$( document ).on(
				'click',
				'#item-body #group-messages-container .bb-groups-messages-left #group_messages_search_submit',
				function( e ) {
					e.preventDefault();
					$( '#item-body #group-messages-container .bb-groups-messages-left .bp-messages-feedback' ).show();
					$( '#group-messages-container .bb-groups-messages-left .bp-messages-feedback .bp-feedback' ).removeClass( 'error' );
					$( '#group-messages-container .bb-groups-messages-left .bp-messages-feedback .bp-feedback' ).addClass( 'info' );
					feedbackParagraphTagSelectorLeft.html( BP_Nouveau.group_messages.loading );
					var term = $( '#item-body #group-messages-container .bb-groups-messages-left .group-messages-search .bp-search #group_messages_search_form #group_messages_search' ).val();
					page 	 = 1;
					var type = '';
					if ( $( '#bp-group-message-switch-checkbox' ).is( ':checked' ) ) {
						type = 'all';
					} else {
						type = 'individual';
					}

					var data = {
						'action': 'groups_get_group_members_listing',
						'nonce' : BP_Nouveau.group_messages.nonces.retrieve_group_members,
						'group' : BP_Nouveau.group_messages.group_id,
						'type'  : type,
						'page'  : page,
						'term'  : term
					};

					$.ajax(
						{
							type: 'POST',
							url: BP_Nouveau.ajaxurl,
							data: data,
							success: function (response) {
								if ( response.success && 'no_member' !== response.data.results ) {
									memberListUl.html( '' );
									memberListUl.html( response.data.results );
									memberListUlLast.html( '' );
									memberListUlLast.html( response.data.pagination );
									$( '#item-body #group-messages-container .bb-groups-messages-left .bp-messages-feedback' ).hide();
								} else {
									memberListUl.html( '' );
									memberListUlLast.html( '' );
									$( '#group-messages-container .bb-groups-messages-left .bp-messages-feedback .bp-feedback' ).addClass( 'error' );
									feedbackParagraphTagSelectorLeft.html( BP_Nouveau.group_messages.no_member );
								}
								if ( 'all' === type ) {
									$( '.group-messages-members-listing #members-list li .action' ).hide();
								} else {
									$( '.group-messages-members-listing #members-list li .action' ).show();
								}
							}
						}
					);
				}
			);

			$( '.bb-close-select-members' ).on(
				'click',
				function(e) {
					e.preventDefault();
					$( '.bb-groups-messages-left' ).removeClass( 'bb-select-member-view' );
				}
			);

			$( document ).on(
				'click',
				'#group-messages-container .bb-add-members',
				function(e) {
					e.preventDefault();
					$( '.bb-groups-messages-left' ).addClass( 'bb-select-member-view' );
				}
			);

			$( document ).on(
				'click',
				'#group-messages-container .show-toolbar',
				function(e) {
					e.preventDefault();
					var medium_editor = $( e.currentTarget ).closest( '#bp-group-message-content' ).find( '.medium-editor-toolbar' );
					$( e.currentTarget ).find( '.toolbar-button' ).toggleClass( 'active' );
					if ( jQuery( e.currentTarget ).find( '.toolbar-button' ).hasClass( 'active' ) ) {
						jQuery( e.currentTarget ).attr( 'data-bp-tooltip',jQuery( e.currentTarget ).attr( 'data-bp-tooltip-hide' ) );
					} else {
						jQuery( e.currentTarget ).attr( 'data-bp-tooltip',jQuery( e.currentTarget ).attr( 'data-bp-tooltip-show' ) );
					}
					medium_editor.toggleClass( 'active' );
				}
			);
			$( document ).on(
				'click',
				'#group-messages-container .medium-editor-toolbar-actions',
				function(e) {
					$( e.currentTarget ).closest( '#bp-group-message-content' ).find( '#group_message_content' ).focus();
				}
			);
		},

		/**
		 * [setupGlobals description]
		 *
		 * @return {[type]} [description]
		 */
		setupGlobals: function() {
		},

		activateTinyMce: function() {
			if ( ! _.isUndefined( window.MediumEditor ) ) {

				window.group_messages_editor = new window.MediumEditor(
					'#group_message_content',
					{
						placeholder: {
							text: BP_Nouveau.group_messages.type_message,
							hideOnClick: true
						},
						toolbar: {
							buttons: ['bold', 'italic', 'unorderedlist','orderedlist', 'quote', 'anchor', 'pre' ],
							relativeContainer: document.getElementById( 'whats-new-toolbar' ),
							static: true,
							updateOnEmptySelection: true
						}
					}
				);

				window.group_messages_editor.subscribe(
					'editableInput',
					function () {
						$( '#group_message_content_hidden' ).val( window.group_messages_editor.getContent() );
					}
				);

				if ( ! _.isUndefined( BP_Nouveau.media ) && ! _.isUndefined( BP_Nouveau.media.emoji ) && $( '#group_message_content' ).length && BP_Nouveau.media.emoji.messages === true ) {
					$( '#group_message_content' ).emojioneArea(
						{
							standalone: true,
							hideSource: false,
							container: $( '#whats-new-toolbar > .post-emoji' ),
							autocomplete: false,
							pickerPosition: 'bottom',
							hidePickerOnBlur: true,
							useInternalCDN: false,
							events: {
								emojibtn_click: function () {
									$( '#group_message_content' )[0].emojioneArea.hidePicker();
								}
							}
						}
					);
				}

				// check for mentions in the url, if set any then focus to editor
				var mention = bp.Nouveau.getLinkParams( null, 'r' ) || null;

				// Check for mention
				if ( ! _.isNull( mention ) ) {
					$( '#message_content' ).focus();
				}

			} else if ( typeof tinymce !== 'undefined' ) {
				tinymce.EditorManager.execCommand( 'mceAddEditor', true, 'message_content' ); // jshint ignore:line
			}
		},

		addMentions: function() {
			// Add autocomplete to send_to field
			$( this.el ).find( '#group-messages-send-to-input' ).bp_mentions(
				{
					data: [],
					suffix: ' '
				}
			);
		},

		addSelect2: function( $input ) {

			var ArrayData = [];
			$input.select2();

			// Add element into the Arrdata array.
			$input.on(
				'select2:select',
				function(e) {
					var data = e.params.data;
					ArrayData.push( data.id );
				}
			);

			// Remove element into the Arrdata array.
			$input.on(
				'select2:unselect',
				function(e) {
					var data  = e.params.data;
					ArrayData = jQuery.grep(
						ArrayData,
						function(value) {
							return value !== data.id;
						}
					);
				}
			);

		},

		displayFeedback: function( message, type ) {
			$( '#group-messages-container .bb-groups-messages-right .bp-messages-feedback' ).removeClass( 'bp-messages-feedback-hide' );
			$( '#group-messages-container .bb-groups-messages-right .bp-messages-feedback .bp-feedback' ).addClass( type );
			$( '#group-messages-container .bb-groups-messages-right .bp-messages-feedback .bp-feedback p' ).html( message );
		},

		removeFeedback: function() {
			$( '#group-messages-container .bb-groups-messages-right .bp-messages-feedback' ).addClass( 'bp-messages-feedback-hide' );
			$( '#group-messages-container .bb-groups-messages-right .bp-messages-feedback .bp-feedback p' ).html( '' );
		},

		// members autoload
		loadMoreMessageMembers: function ( event ) {

			var target = $( event.currentTarget );
			if ( ( target[0].scrollHeight - ( target.scrollTop() ) ) === target.innerHeight() ) {
				var element = $( '#group-messages-container .group-messages-members-listing #members-list li.load-more' );
				if ( element.length ) {
					$( '#group-messages-container .group-messages-members-listing .last #bp-group-messages-next-page' ).trigger( 'click' );
				}
			}
		}

	};

	// Launch BP Nouveau Groups
	bp.Nouveau.GroupMessages.start();

} )( bp, jQuery );
