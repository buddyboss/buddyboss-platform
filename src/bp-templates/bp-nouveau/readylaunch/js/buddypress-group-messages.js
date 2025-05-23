/* global wp, bp, BP_Nouveau, _ */
/* @version 3.0.0 */
window.wp = window.wp || {};
window.bp = window.bp || {};

( function( exports, $ ) {

	// Bail if not set.
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

	var bpNouveauLocal          = BP_Nouveau,
	    bpGroupMessages         = bpNouveauLocal.group_messages,
	    bpAddRecipient          = bpGroupMessages.add_recipient,
	    bpGroupMessagesNonces   = bpGroupMessages.nonces,
	    bpGroupMessagesNoMember = bpGroupMessages.no_member;

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
			this.views        = new Backbone.Collection();
			this.mediumEditor = false;

			this.setupGlobals();

			var $bodyElem              = $( 'body' ),
			    xhr_submit_message     = null,
			    $group_messages_select = $bodyElem.find( '#group-messages-send-to-input' ),
			    page                   = 1,
			    total_pages            = 1,
			    show_all               = '',
			    checkbox               = '',
			    allText                = $bodyElem.find( '.count-all-members-text' ).val(),
			    $switchCheckboxElem    = $( '#bp-group-message-switch-checkbox' );

			// Activate bp_mentions.
			this.addSelect2( $group_messages_select );
			this.activateTinyMce();

			var feedbackParagraphTagSelectorLeft = $( '#group-messages-container .bb-groups-messages-left .group-messages-members-listing .bp-messages-feedback .bp-feedback p' );
			var memberListUl 					 = $( '.group-messages-members-listing #members-list' );
			var memberListUlLast 				 = $( '.group-messages-members-listing .last' );
			var searchText 						 = $( '#item-body #group-messages-container .bb-groups-messages-left #group_messages_search' ).val();

			$( '#item-body #group-messages-container .bb-groups-messages-right #send_group_message_form .bb-groups-messages-right-top .select2-container .selection .select2-selection--multiple .select2-selection__rendered .select2-search--inline .select2-search__field' ).prop( 'disabled', true );

			var isGroupThreadPageSelector = $( '.groups.group-messages.public-message' );
			if ( isGroupThreadPageSelector.length ) {
				$( '.groups.group-messages.public-message .subnav #bb-rl-public-message-groups-li' ).addClass( 'current selected' );
			}

			var isGroupPrivateThreadPageSelector = $( '.groups.group-messages.private-message' );
			if ( isGroupPrivateThreadPageSelector.length ) {
				$( '.groups.group-messages.private-message .subnav #bb-rl-private-message-groups-li' ).addClass( 'current selected' );
			}

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
				function( e ) {
					var data = e.params.data;
					$( '#group-messages-send-to-input option[value="' + data.id + '"]' ).each(
						function() {
							$( this ).remove();
						}
					);
					$( '#item-body #group-messages-container .bb-groups-messages-left #members-list li.' + data.id ).removeClass( 'selected' );
					$( '#item-body #group-messages-container .bb-groups-messages-left #members-list li.' + data.id + ' .action button' ).attr( 'data-bp-tooltip', bpAddRecipient );
				}
			);

			var data = {
				'action'   : 'groups_get_group_members_listing',
				'nonce'    : bpGroupMessagesNonces.retrieve_group_members,
				'group'    : bpGroupMessages.group_id,
				'type'     : 'all',
				'page'     : page,
				'show_all' : show_all
			};

			$.ajax(
				{
					type: 'POST',
					url: BP_Nouveau.ajaxurl,
					async: false,
					data: data,
					success: function (response) {
						if ( response.success && 'no_member' !== response.data.results ) {
							memberListUl.html( response.data.results );
							memberListUlLast.html( '' );
							memberListUlLast.html( response.data.pagination );
							$( '#item-body #group-messages-container .bb-groups-messages-left .bp-messages-feedback' ).hide();

							if ( typeof response.data.total_page !== 'undefined' ) {
								total_pages = parseInt( response.data.total_page );
							}
						} else if ( response.success && 'no_member' === response.data.results ) {
							$( '#group-messages-container .bb-groups-messages-right .bp-messages-feedback' ).removeClass( 'bp-messages-feedback-hide' );
							$( '#group-messages-container .bb-groups-messages-right .bp-messages-feedback .bp-feedback' ).addClass( 'feedback' );
							$( '#group-messages-container .bb-groups-messages-right .bp-messages-feedback .bp-feedback p' ).html( bpGroupMessages.group_no_member );
							var feedbackNotice = $( '#item-body #group-messages-container .bb-groups-messages-right .bp-messages-feedback' ).html(),
							    $countElem     = $( '#group-messages-container .bb-groups-messages-right #send_group_message_form .send_group_message_form_private_bb_platform_pro_hidden_count' ),
							    $itemBodyElem  = $( '#item-body' );
							if ( $countElem.length && 0 === parseInt( $countElem.val() ) ) {
								$itemBodyElem.html( '' );
								$itemBodyElem.html( feedbackNotice );
							} else if ( $countElem.length && parseInt( $countElem.val() ) > 0 ) {
								$( '#item-body .group-messages-members-listing .bp-messages-feedback' ).removeClass( 'bp-messages-feedback-hide' );
								$( '#item-body .group-messages-members-listing .bp-messages-feedback .bp-feedback' ).addClass( 'info' );
								$( '#item-body .group-messages-members-listing .bp-messages-feedback .bp-feedback p' ).html( bpGroupMessages.group_no_member_pro );
							} else if ( ! $countElem.length ) {
								$itemBodyElem.html( '' );
								$itemBodyElem.html( feedbackNotice );
							}
						} else {
							$( '.group-messages-members-listing #members-list' ).html( '' );
							memberListUlLast.html( '' );
							$( '#group-messages-container .bb-groups-messages-left .bp-messages-feedback .bp-feedback' ).addClass( 'error' );
							feedbackParagraphTagSelectorLeft.html( bpGroupMessagesNoMember );
						}
					}
				}
			);

			$( '.bb-groups-messages-left-inner .bb-panel-head input#bp-group-message-switch-checkbox' ).on(
				'click',
				function () {

					var valueSelected;
					// Reset select2.
					$group_messages_select.val( '' ).trigger( 'change' );
					if ( $( this ).is( ':checked' ) ) {
						valueSelected = 'all';
					} else {
						valueSelected = 'single';
					}

					if ( valueSelected ) {
						if ( 'all' === valueSelected ) {
							$( '#group-messages-send-to-input option' ).each(
								function() {
									$( this ).remove();
								}
							);

							if ( '' !== allText ) {
								$group_messages_select.append( $( '<option>' ).val( bpGroupMessages.select_default_value ).text( allText ) );
							} else {
								$group_messages_select.append( $( '<option>' ).val( bpGroupMessages.select_default_value ).text( bpGroupMessages.select_default_text ) );
							}

							$group_messages_select.select2().prop( 'disabled', true );
							$group_messages_select.select2( 'data', {
								id : bpGroupMessages.select_default_value,
								text : bpGroupMessages.select_default_text
							} );
							$group_messages_select.val( 'all' ).trigger( 'change' );
							$( '.group-messages-members-listing #members-list li.can-grp-msg' ).addClass( 'is_disabled selected' );
							$( '.bp-select-members-wrap .select2-selection__choice__remove' ).hide();
							$( '#item-body #group-messages-container .bb-groups-messages-right .bb-groups-messages-right-bottom .group-messages-type' ).val( 'private' );
						} else {
							$( '#group-messages-send-to-input option[value="all"]' ).each(
								function() {
									$( this ).remove();
								}
							);
							$group_messages_select.select2().prop( 'disabled', false );
							$( '#group-messages-container .bb-groups-messages-right #send_group_message_form .bb-groups-messages-right-top .select2-container .selection .select2-selection--multiple .select2-selection__rendered .select2-search--inline .select2-search__field' ).prop( 'disabled', true );
							$( '.group-messages-members-listing #members-list li.can-grp-msg' ).removeClass( 'is_disabled selected' );
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
				'change',
				'#item-body #group-messages-container .bb-groups-messages-right .bb-groups-messages-right-bottom #bp-group-message-content #whats-new-toolbar #group-messages-new-submit .group-messages-type',
				function() {
					if ( 'private' !== this.value ) {
						if ( $switchCheckboxElem.is( ':checked' ) ) {
							$switchCheckboxElem.trigger( 'click' );
						}
						$switchCheckboxElem.prop( 'disabled', true ).parent().addClass( 'is_disbaled' );
					} else {
						$switchCheckboxElem.prop( 'disabled', false ).parent().removeClass( 'is_disbaled' );
					}
				}
			);

			$( document ).on(
				'click',
				'#item-body #group-messages-container .bb-groups-messages-left #members-list li .action .group-add-remove-invite-button',
				function() {

					var getCurrent = $group_messages_select.select2( 'val' );
					var canAdd     = true;

					if ( getCurrent ) {
						getCurrent.forEach(
							function(data) {
								if ( 'all' === data ) {
									canAdd = false;
								}
							}
						);
					}

					if ( canAdd ) {
						var userId   = $( this ).attr( 'data-bp-user-id' );
						var userName = $( this ).attr( 'data-bp-user-name' );
						var userAvatar = $( this ).closest( 'li' ).find( '.item-avatar img' ).attr( 'src' );

						var data = {
							id: userId,
							text: userName,
							avatar: userAvatar
						};

						if ( $( this ).closest( 'li' ).hasClass( 'selected' ) ) {

							$( this ).closest( 'li' ).removeClass( 'selected' );

							var newArray = [];
							var newData  = $.grep(
								$group_messages_select.select2( 'data' ),
								function( value ) {
									return value[ 'id' ] !== userId; // jshint ignore:line
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

							$( this ).attr( 'data-bp-tooltip', bpAddRecipient );

						} else {
							$( this ).closest( 'li' ).addClass( 'selected' );
							if ( ! $group_messages_select.find( "option[value='" + data.id + "']" ).length ) { // jshint ignore:line
								var newOption = new Option( data.text, data.id, true, true );
								$( newOption ).attr( 'data-avatar', data.avatar );
								$group_messages_select.append( newOption ).trigger( 'change' );
							}
							$( this ).attr( 'data-bp-tooltip', bpGroupMessages.remove_recipient );
						}
					}
				}
			);

			$( document ).on(
				'change',
				$group_messages_select,
				function() {
					if ( $group_messages_select[0].value ) {
						$group_messages_select.siblings( '.select2.select2-container' ).show();
					} else {
						$group_messages_select.siblings( '.select2.select2-container' ).hide();
					}
				}
			);

			if ( isGroupPrivateThreadPageSelector.length ) {

				var membersDiv = document.getElementById( 'members-list' );
				$( '.bb-icon-spinner' ).hide();
				var scroll_xhr;
				if ( $( membersDiv ).length ) {
					membersDiv.addEventListener(
						'scroll',
						function () {
							if ( membersDiv.offsetHeight + membersDiv.scrollTop + 30 >= membersDiv.scrollHeight ) {

								if ( page >= total_pages || scroll_xhr != null ) {
									return false;
								}

								if (
									! $( '#group-messages-container .group-messages-members-listing #members-list li.load-more' ).length &&
									! $( '#group-messages-container .group-messages-members-listing .last #bp-group-messages-next-page' ).length
								) {
									return false;
								}

								page = page + 1;

								$( '.bb-icon-spinner' ).show();

								var type = '';
								if ( $switchCheckboxElem.is( ':checked' ) ) {
									type = 'all';
								} else {
									type = 'individual';
								}

								var data = {
									'action'   : 'groups_get_group_members_listing',
									'nonce'    : bpGroupMessagesNonces.retrieve_group_members,
									'group'    : bpGroupMessages.group_id,
									'type'     : type,
									'page'     : page,
									'show_all' : show_all
								};

								scroll_xhr = $.ajax(
									{
										type: 'POST',
										url: BP_Nouveau.ajaxurl,
										data: data,
										success: function ( response ) {
											scroll_xhr = null;
											if ( response.success && 'no_member' !== response.data.results ) {
												$( '#group-messages-container .group-messages-members-listing #members-list li.load-more' ).remove();
												memberListUl.append( response.data.results );
												memberListUlLast.html( '' );
												memberListUlLast.html( response.data.pagination );
												$( '#item-body #group-messages-container .bb-groups-messages-left .bp-messages-feedback' ).hide();
												if ( 'all' === type ) {
													$( '.group-messages-members-listing #members-list li.can-grp-msg' ).addClass( 'is_disabled selected' );
												} else {

													var selected_user_ids = $group_messages_select.val();
													for ( var select_index = 0; select_index < selected_user_ids.length; select_index++ ) {
														var user_id = selected_user_ids[ select_index ];
														var user_li_sel = $( '.group-messages-members-listing #members-list li.can-grp-msg.' + user_id );
														if ( user_li_sel.length > 0 ) {
															user_li_sel.addClass( 'selected' );
														}
													}
												}
											} else {
												$( '#group-messages-container .bb-groups-messages-left .bp-messages-feedback .bp-feedback' ).addClass( 'error' );
												feedbackParagraphTagSelectorLeft.html( bpGroupMessagesNoMember );
											}
											$( '.bb-icon-spinner' ).hide();
										}
									}
								);
							}
						}
					);
				}

			}

			$( document ).on(
				'click',
				'#item-body #group-messages-container .bb-groups-messages-left .last #bp-group-messages-prev-page',
				function() {
					$( '#item-body #group-messages-container .bb-groups-messages-left .bp-messages-feedback' ).show();
					$( '#item-body #group-messages-container .bb-groups-messages-left .bp-messages-feedback .bp-feedback' ).addClass( 'info' );
					feedbackParagraphTagSelectorLeft.html( bpGroupMessages.loading );
					page = page - 1;
					var type;
					if ( $switchCheckboxElem.is( ':checked' ) ) {
						type = 'all';
					} else {
						type = 'individual';
					}
					var data = {
						'action'   : 'groups_get_group_members_listing',
						'nonce'    : bpGroupMessagesNonces.retrieve_group_members,
						'group'    : bpGroupMessages.group_id,
						'type'     : type,
						'page'     : page,
						'show_all' : show_all
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
									feedbackParagraphTagSelectorLeft.html( bpGroupMessagesNoMember );
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

					var type = 'individual';

					page = 1;

					var data = {
						'action'   : 'groups_get_group_members_listing',
						'nonce'    : bpGroupMessagesNonces.retrieve_group_members,
						'group'    : bpGroupMessages.group_id,
						'type'     : type,
						'page'     : page,
						'term'     : searchText,
						'show_all' : show_all
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
									feedbackParagraphTagSelectorLeft.html( bpGroupMessagesNoMember );
								}
								if ( 'all' === type ) {
									$( '.group-messages-members-listing #members-list li.can-grp-msg' ).addClass( 'is_disabled' );
								} else {
									$( '.group-messages-members-listing #members-list li.can-grp-msg' ).removeClass( 'is_disabled' );
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
					$( '#item-body #group-messages-container .bb-groups-messages-right' ).removeClass( 'full_width' );
					$( '#item-body #group-messages-container .bb-groups-messages-left' ).show();
					$( '#item-body #group-messages-container .bb-groups-messages-right .remove-after-few-seconds' ).remove();
					$( '#item-body #group-messages-container .bb-groups-messages-right .select2-container' ).show();
					$( '#item-body #group-messages-container .bb-groups-messages-right .bb-groups-messages-right-bottom' ).show();
					if ( $switchCheckboxElem.is( ':checked' ) ) {
						$( '#item-body #group-messages-container .bb-groups-messages-right .bb-groups-messages-right-bottom .group-messages-type' ).val( 'private' );
					} else {
						$( '#item-body #group-messages-container .bb-groups-messages-right .bb-groups-messages-right-bottom .group-messages-type' ).val( 'open' );
					}

					$( '#members-list .group-message-member-li.selected .invite-button' ).trigger( 'click' );

				}
			);

			$( document ).on(
				'click',
				'#group-messages-container #send_group_message_button',
				function( e ) {
					e.preventDefault();
					var user, type;
					var target     = $( e.currentTarget );
					var users_list = [];
					if ( xhr_submit_message ) {
						return;
					}

					var isGroupThreadPageSelector = $( '.groups.group-messages.public-message' );
					if ( isGroupThreadPageSelector.length ) {
						user 	   = 'all';
						users_list = [];
						type       = 'open';
					}

					var isGroupPrivateThreadPageSelector = $( '.groups.group-messages.private-message' );
					if ( isGroupPrivateThreadPageSelector.length ) {
						checkbox = $switchCheckboxElem.is( ':checked' );

						if ( checkbox ) {
							user       = 'all';
							users_list = [];
						} else {
							user = 'individual';
						}
						var newData = $.grep(
							$group_messages_select.select2( 'data' ),
							function( value ) {
								return value[ 'id' ] !== 0; // jshint ignore:line
							}
						);

						newData.forEach(
							function( data ) {
								users_list.push( +data.id );
							}
						);

						if ( 'open' === $( '.group-messages-type :selected' ).val() ) {
							type = 'open';
						} else {
							type = 'private';
						}
					}

					var content = '',
					    editor  = '';
					if ( typeof window.group_messages_editor !== 'undefined' ) {
						editor                   = window.group_messages_editor;
						var $groupMessageContent = $( '#group_message_content' );
						$groupMessageContent.find( 'img.emoji' ).each(
							function ( index, Obj ) {
								$( Obj ).addClass( 'emojioneemoji' );
								var emojis = $( Obj ).attr( 'alt' );
								$( Obj ).attr( 'data-emoji-char', emojis );
								$( Obj ).removeClass( 'emoji' );
							}
						);
						$groupMessageContent.find( 'img.emojioneemoji' ).replaceWith(
							function () {
								return this.dataset.emojiChar;
							}
						);
						content = editor.getContent();
					}

					// Add valid line breaks.
					content = $.trim( content.replace( /<div>/gi, '\n' ).replace( /<\/div>/gi, '' ) );
					content = content.replace( /&nbsp;/g, ' ' );

					var media                  = $( '#item-body #group-messages-container .bb-groups-messages-right #send_group_message_form .bb-groups-messages-right-bottom #bp_group_messages_media' ).val();
					var document               = $( '#item-body #group-messages-container .bb-groups-messages-right #send_group_message_form .bb-groups-messages-right-bottom #bp_group_messages_document' ).val();
					var video                  = $( '#item-body #group-messages-container .bb-groups-messages-right #send_group_message_form .bb-groups-messages-right-bottom #bp_group_messages_video' ).val();
					var gif                    = $( '#item-body #group-messages-container .bb-groups-messages-right #send_group_message_form .bb-groups-messages-right-bottom #bp_group_messages_gif' ).val();
					var $bpMessageFeedbackElem = $( '#item-body #group-messages-container .bb-groups-messages-right #send_group_message_form .bb-groups-messages-right-top .bp-messages-feedback' );
					var contentError           = $bpMessageFeedbackElem.find( '.bp-feedback-content-no-error' );
					var recipientError         = $bpMessageFeedbackElem.find( '.bp-feedback-recipient-no-error' );
					var $feedbackPElem         = $bpMessageFeedbackElem.find( '.bp-feedback p' );

					if ( $( $.parseHTML( content ) ).text().trim() === '' && '' === media && '' === document && '' === video && '' === gif ) {
						if ( ! contentError.length ) {
							$bpMessageFeedbackElem.removeClass( 'bp-messages-feedback-hide' );
							$bpMessageFeedbackElem.find( '.bp-feedback' ).addClass( 'error' );
							$feedbackPElem.html( '' );
							$feedbackPElem.html( bpGroupMessages.no_content );
						}
						return false;
					} else {
						if ( contentError.length ) {
							contentError.remove();
						}
					}

					if ( isGroupPrivateThreadPageSelector.length ) {
						checkbox = $switchCheckboxElem.is( ':checked' );
						if ( ! checkbox && 0 === users_list.length ) {
							if ( ! recipientError.length ) {
								$bpMessageFeedbackElem.removeClass( 'bp-messages-feedback-hide' );
								$bpMessageFeedbackElem.find( '.bp-feedback' ).addClass( 'error' );
								$feedbackPElem.html( '' );
								$feedbackPElem.html( bpGroupMessages.no_content );
							}
							return false;
						} else {
							if ( recipientError.length ) {
								recipientError.remove();
							}
						}
					}

					$bpMessageFeedbackElem.addClass( 'bp-messages-feedback-hide' );
					$bpMessageFeedbackElem.find( '.bp-feedback' ).removeClass( 'info' );
					$bpMessageFeedbackElem.find( '.bp-feedback' ).removeClass( 'error' );

					var data = {
						'action'     : 'groups_get_group_members_send_message',
						'nonce'      : bpGroupMessagesNonces.send_messages_users,
						'group'      : bpGroupMessages.group_id,
						'content'    : content,
						'media'      : media,
						'document'   : document,
						'video'      : video,
						'users'      : user,
						'users_list' : users_list,
						'type'       : type,
						'gif_data'   : gif
					};
					
					target.addClass( 'loading' ).attr( 'disabled', true );

					xhr_submit_message = $.ajax(
						{
							type: 'POST',
							url: BP_Nouveau.ajaxurl,
							data: data,
							success : function ( response ) {
								var dropzone_container  = $( 'div#bp-group-messages-post-media-uploader' );
								var containerAttachment = $( '#whats-new-attachments .bp-group-messages-attached-gif-container' );
								var inputHiddenGif      = $( '#bp_group_messages_gif' );
								var feedbackSelector    = $( '#item-body .bb-groups-messages-right-top .bp-messages-feedback' );
								var $select2Container   = $( '#item-body #group-messages-container .bb-groups-messages-right .select2-container' );
								target.removeClass( 'loading' ).attr( 'disabled', false );
								xhr_submit_message = null;
								if ( response.success ) {

									$select2Container.hide();
									$( '#item-body #group-messages-container .bb-groups-messages-right' ).addClass( 'full_width' );
									$( '#item-body #group-messages-container .bb-groups-messages-right .bb-groups-messages-right-bottom' ).hide();
									$( '#item-body #group-messages-container .remove-after-few-seconds' ).remove();
									$( '#item-body #group-messages-container .bb-groups-messages-left' ).hide();
									$( '#item-body #group-messages-container .bb-groups-messages-right-top .group-messages-compose' ).show();

									var feedbackHtmlSuccess;
									var feedbackReplaceCount = response.data.feedback.replace( '%%count%%', users_list.length );
									feedbackHtmlSuccess      = '<div class="bp-feedback success bp-feedback-content-no-error remove-after-few-seconds"><span class="bp-icon" aria-hidden="true"></span><p> ' + feedbackReplaceCount + ' </p></div>';

									feedbackSelector.hide();
									feedbackSelector.after( feedbackHtmlSuccess );

									// Reset formatting of editor.
									window.group_messages_editor.resetContent();

									if ( typeof window.Dropzone !== 'undefined' && dropzone_container.length ) {

										if ( bp.Nouveau.Media.dropzone_media.length ) {

											$( bp.Nouveau.Media.dropzone_media ).each(
												function( i ) {
													bp.Nouveau.Media.dropzone_media[ i ].saved = true;
												}
											);
										}

										$( '.dropzone' ).each(
											function() {
												var dropzoneControl = $( this )[ 0 ].dropzone;
												if ( dropzoneControl ) {
													dropzoneControl.destroy();
													dropzoneControl.dropzone_media = [];
												}
											}
										);
										dropzone_container.html( '' );
										dropzone_container.addClass( 'closed' ).removeClass( 'open' );

										var dropZoneDocElem = $( 'div#bp-group-messages-post-document-uploader' );
										dropZoneDocElem.html( '' );
										dropZoneDocElem.addClass( 'closed' ).removeClass( 'open' );

										var dropZoneVideoElem = $( 'div#bp-group-messages-post-video-uploader' );
										dropZoneVideoElem.html( '' );
										dropZoneVideoElem.addClass( 'closed' ).removeClass( 'open' );
									}

									if ( containerAttachment.length ) {
										$( '#whats-new-toolbar .bp-group-messages-attached-gif-container' ).parent().removeClass( 'open' );
										$( '#whats-new-toolbar #bp-group-messages-gif-button' ).removeClass( 'active' );
										containerAttachment.addClass( 'closed' );
										containerAttachment.find( '.gif-image-container img' ).attr( 'src', '' );
										containerAttachment[ 0 ].style = '';
										if ( inputHiddenGif.length ) {
											inputHiddenGif.val( '' );
										}
									}
									$select2Container.hide();
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
										5000
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
					var bpFeedbackElem = $( '#group-messages-container .bb-groups-messages-left .bp-messages-feedback .bp-feedback' );
					bpFeedbackElem.removeClass( 'error' );
					bpFeedbackElem.addClass( 'info' );
					feedbackParagraphTagSelectorLeft.html( bpGroupMessages.loading );
					var term = $( '#item-body #group-messages-container .bb-groups-messages-left .group-messages-search .bp-search #group_messages_search_form #group_messages_search' ).val();
					page     = 1;
					var type = '';
					if ( $switchCheckboxElem.is( ':checked' ) ) {
						type = 'all';
					} else {
						type = 'individual';
					}

					var data = {
						'action'   : 'groups_get_group_members_listing',
						'nonce'    : bpGroupMessagesNonces.retrieve_group_members,
						'group'    : bpGroupMessages.group_id,
						'type'     : type,
						'page'     : page,
						'term'     : term,
						'show_all' : show_all
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
									feedbackParagraphTagSelectorLeft.html( bpGroupMessagesNoMember );
								}
								if ( 'all' === type ) {
									$( '.group-messages-members-listing #members-list li.can-grp-msg' ).addClass( 'is_disabled selected' );
								} else {

									var selected_user_ids = $group_messages_select.val();
									for ( var select_index = 0; select_index < selected_user_ids.length; select_index++ ) {
										var user_id     = selected_user_ids[ select_index ];
										var user_li_sel = $( '.group-messages-members-listing #members-list li.can-grp-msg.' + user_id );
										if ( user_li_sel.length > 0 ) {
											user_li_sel.addClass( 'selected' );
										}
									}

									$( '.group-messages-members-listing #members-list li.can-grp-msg' ).removeClass( 'is_disabled' );
								}
							}
						}
					);
				}
			);

			$( '.bb-close-select-members' ).on(
				'click',
				function( e ) {
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
				function ( e ) {
					e.preventDefault();
					var medium_editor = $( e.currentTarget ).closest( '#bp-group-message-content' ).find( '.medium-editor-toolbar' );
					$( e.currentTarget ).find( '.toolbar-button' ).toggleClass( 'active' );
					if ( jQuery( e.currentTarget ).find( '.toolbar-button' ).hasClass( 'active' ) ) {
						jQuery( e.currentTarget ).attr( 'data-bp-tooltip', jQuery( e.currentTarget ).attr( 'data-bp-tooltip-hide' ) );
						if ( window.group_messages_editor.exportSelection() != null ) {
							medium_editor.addClass( 'medium-editor-toolbar-active' );
						}
					} else {
						jQuery( e.currentTarget ).attr( 'data-bp-tooltip', jQuery( e.currentTarget ).attr( 'data-bp-tooltip-show' ) );
						if ( window.group_messages_editor.exportSelection() === null ) {
							medium_editor.removeClass( 'medium-editor-toolbar-active' );
						}
					}
					$( window.group_messages_editor.elements[ 0 ] ).focus();
					medium_editor.toggleClass( 'active' );
				}
			);

			$( document ).on(
				'click',
				'#group-messages-container .medium-editor-toolbar-actions',
				function ( e ) {
					if ( window.group_messages_editor.exportSelection() === null ) {
						$( e.currentTarget ).closest( '#bp-group-message-content' ).find( '#group_message_content' ).focus();
					}
				}
			);

			$( document ).on(
				'input',
				'#group_message_content',
				function ( e ) { // Fix issue of Editor loose focus when formatting is opened after selecting text.
					var medium_editor = $( e.currentTarget ).closest( '#bp-group-message-content' ).find( '.medium-editor-toolbar' );
					setTimeout(
						function() {
							medium_editor.addClass( 'medium-editor-toolbar-active' );
							$( e.currentTarget ).closest( '#bp-group-message-content' ).find( '#group_message_content' ).focus();
						},
						0
					);
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
							text: bpGroupMessages.type_message,
							hideOnClick: true
						},
						toolbar: {
							buttons: ['bold', 'italic', 'unorderedlist','orderedlist', 'quote', 'anchor', 'pre' ],
							relativeContainer: document.getElementById( 'bb-rl-editor-toolbar' ),
							static: true,
							updateOnEmptySelection: true
						},
						paste: {
							forcePlainText: false,
							cleanPastedHTML: true,
							cleanReplacements: [
							[new RegExp( /<div/gi ), '<p'],
							[new RegExp( /<\/div/gi ), '</p'],
							[new RegExp( /<h[1-6]/gi ), '<b'],
							[new RegExp( /<\/h[1-6]/gi ), '</b'],
							],
							cleanAttrs: ['class', 'style', 'dir', 'id'],
							cleanTags: [ 'meta', 'div', 'main', 'section', 'article', 'aside', 'button', 'svg', 'canvas', 'figure', 'input', 'textarea', 'select', 'label', 'form', 'table', 'thead', 'tfooter', 'colgroup', 'col', 'tr', 'td', 'th', 'dl', 'dd', 'center', 'caption', 'nav', 'img' ],
							unwrapTags: []
						},
						imageDragging: false,
						anchor: {
							placeholderText: BP_Nouveau.anchorPlaceholderText,
							linkValidation: true
						}
					}
				);

				$( document ).on( 'keyup', '#bp-group-message-content .medium-editor-toolbar-input', function ( event ) {

					var URL = event.target.value;

					if ( bp.Nouveau.isURL( URL ) ) {
						$( event.target ).removeClass( 'isNotValid' ).addClass( 'isValid' );
					} else {
						$( event.target ).removeClass( 'isValid' ).addClass( 'isNotValid' );
					}

				} );

				window.group_messages_editor.subscribe(
					'editableInput',
					function () {
						$( '#group_message_content_hidden' ).val( window.group_messages_editor.getContent() );
					}
				);

				var $groupMessageContent = $( '#group_message_content' );
				if (
					! _.isUndefined( BP_Nouveau.media ) &&
					! _.isUndefined( BP_Nouveau.media.emoji ) &&
					$groupMessageContent.length &&
					(
						(
							! _.isUndefined( BP_Nouveau.media.emoji.messages ) &&
							BP_Nouveau.media.emoji.messages
						) ||
						(
							! _.isUndefined( BP_Nouveau.media.emoji.groups ) &&
							BP_Nouveau.media.emoji.groups
						)
					)
				) {
					$groupMessageContent.emojioneArea(
						{
							standalone       : true,
							hideSource       : false,
							container        : $( '#whats-new-toolbar > .post-emoji' ),
							autocomplete     : false,
							pickerPosition   : 'bottom',
							hidePickerOnBlur : true,
							useInternalCDN   : false,
							events           : {
								emojibtn_click : function() {
									$( '#group_message_content' )[ 0 ].emojioneArea.hidePicker();
									window.group_messages_editor.checkContentChanged();
								},

								picker_show : function() {
									$( this.button[ 0 ] ).closest( '.post-emoji' ).addClass( 'active' );
								},

								picker_hide : function() {
									$( this.button[ 0 ] ).closest( '.post-emoji' ).removeClass( 'active' );
								},
							}
						}
					);
				}

				// check for mentions in the url, if set any then focus to editor.
				var mention = bp.Nouveau.getLinkParams( null, 'r' ) || null;

				// Check for mention.
				if ( ! _.isNull( mention ) ) {
					$( '#message_content' ).focus();
				}

			} else if ( typeof tinymce !== 'undefined' ) {
				tinymce.EditorManager.execCommand( 'mceAddEditor', true, 'message_content' ); // jshint ignore:line
			}
		},

		addMentions: function() {
			// Add autocomplete to send_to field.
			$( this.el ).find( '#group-messages-send-to-input' ).bp_mentions(
				{
					data   : [],
					suffix : ' '
				}
			);
		},

		addSelect2: function( $input ) {

			var ArrayData = [], bodyElem = $( 'body' );
			if( bodyElem.hasClass( 'public-message' ) ) {
				$input.find( 'option[selected]' ).data( 'avatar', bodyElem.find( '.bb-rl-group-info img.avatar' ).attr( 'src' ) );
			}

			$input.select2({
				templateSelection: function( data ) {
					if ( !data.id ) {
						return data.text;
					}

					return $(
						'<div class="bb-rl-select2-selection-user">' +
							'<img class="select2-user-avatar" src="' + $( data.element ).data( 'avatar' ) + '" alt=""/>' +
							'<span class="select2-selection-user__name">' + data.text + '</span>' +
						'</div>'
					);
				}
			});

			// Add element into the Arrdata array.
			$input.on(
				'select2:select',
				function( e ) {
					var data = e.params.data;
					ArrayData.push( data.id );
				}
			);

			// Remove element into the Arrdata array.
			$input.on(
				'select2:unselect',
				function( e ) {
					var data  = e.params.data;
					ArrayData = jQuery.grep(
						ArrayData,
						function ( value ) {
							return value !== data.id;
						}
					);
				}
			);

		},

		displayFeedback: function( message, type ) {
			var $bpMessagesFeedbackElem = $( '#group-messages-container .bb-groups-messages-right .bp-messages-feedback' );
			$bpMessagesFeedbackElem.removeClass( 'bp-messages-feedback-hide' );
			$bpMessagesFeedbackElem.find( '.bp-feedback' ).addClass( type );
			$bpMessagesFeedbackElem.find( '.bp-feedback p' ).html( message );
		},

		removeFeedback: function() {
			var $bpMessagesFeedbackElem = $( '#group-messages-container .bb-groups-messages-right .bp-messages-feedback' );
			$bpMessagesFeedbackElem.addClass( 'bp-messages-feedback-hide' );
			$bpMessagesFeedbackElem.find( '.bp-feedback p' ).html( '' );
		},

		// Members autoload.
		loadMoreMessageMembers: function ( event ) {
			var target = $( event.currentTarget );
			if ( ( target[0].scrollHeight - ( target.scrollTop() ) ) === target.innerHeight() ) {
				var element       = $( '#group-messages-container .group-messages-members-listing #members-list li.load-more' ),
				    $nextPageElem = $( '#group-messages-container .group-messages-members-listing .last #bp-group-messages-next-page' );
				if ( element.length && $nextPageElem.length ) {
					$nextPageElem.trigger( 'click' );
				}
			}
		}

	};

	// Launch BP Nouveau Groups.
	bp.Nouveau.GroupMessages.start();

} )( bp, jQuery );
