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

	bp.Models.ACReply = Backbone.Model.extend( {
		defaults: {
			gif_data: {}
		}
	} );

	/**
	 * [Nouveau description]
	 * @type {Object}
	 */
	bp.Nouveau.GroupMessages = {
		/**
		 * [start description]
		 * @return {[type]} [description]
		 */
		start: function() {
			this.views    = new Backbone.Collection();
			this.displayFeedback( BP_Nouveau.group_messages.invites_form_all, 'info' );
			this.mediumEditor    = false;

			this.setupGlobals();

			var $group_messages_select = $( 'body' ).find( '#group-messages-send-to-input' );
			var page = 1;

			// Activate bp_mentions
			this.addSelect2( $group_messages_select );
			this.activateTinyMce();

			var feedbackSelector 	 		 	 = $( '#group-messages-container .bb-groups-messages-right .bp-messages-feedback' );
			var feedbackParagraphTagSelector 	 = $( '#group-messages-container .bb-groups-messages-right .bp-messages-feedback .bp-feedback p' );
			var feedbackSelectorLeft 	 		 = $( '#group-messages-container .bb-groups-messages-left .group-messages-members-listing .bp-messages-feedback' );
			var feedbackParagraphTagSelectorLeft = $( '#group-messages-container .bb-groups-messages-left .group-messages-members-listing .bp-messages-feedback .bp-feedback p' );

			var isMobile = false; //initiate as false
			// device detection
			if(/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|ipad|iris|kindle|Android|Silk|lge |maemo|midp|mmp|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows (ce|phone)|xda|xiino/i.test(navigator.userAgent)
				|| /1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i.test(navigator.userAgent.substr(0,4))) {
				isMobile = true;
			}

			$( '#item-body #group-messages-container .bb-groups-messages-right #send_group_message_form .bb-groups-messages-right-top .select2-container .selection .select2-selection--multiple .select2-selection__rendered .select2-search--inline .select2-search__field' ).prop( 'disabled', true );
			$( document ).on( 'click', '#item-body #group-messages-container .bb-groups-messages-right #send_group_message_form .bb-groups-messages-right-top .select2-container .selection .select2-selection--multiple .select2-selection__rendered .select2-search--inline .select2-search__field', function() {
				$( this ).prop( 'disabled', true );
			});

			$( $( '.group-messages-members-listing #members-list' ) ).scroll( this.loadMoreMessageMembers );


			$group_messages_select.on('select2:unselect', function(e) {
				var data = e.params.data;
				$( '#group-messages-send-to-input option[value="' + data.id + '"]' ).each(function() {
					$(this).remove();
				});
				$( '#item-body #group-messages-container .bb-groups-messages-left #members-list li.' + data.id ).removeClass( 'selected' );
				$( '#item-body #group-messages-container .bb-groups-messages-left #members-list li.' + data.id + ' .action button' ).attr( 'data-bp-tooltip', BP_Nouveau.group_messages.add_recipient );
			});

			$group_messages_select.select2().prop( 'disabled', true );

			var data = {
				'action': 'groups_get_group_members_listing',
				'nonce' : BP_Nouveau.group_messages.nonces.retrieve_group_members,
				'group' : BP_Nouveau.group_messages.group_id,
				'type'  : 'all',
				'page'  : page
			};

			$.ajax({
				type: 'POST',
				url: BP_Nouveau.ajaxurl,
				async: false,
				data: data,
				success: function (response) {
					if (response.success && 'no_member' !== response.data.results) {
						$('.group-messages-members-listing #members-list').html('');
						$('.group-messages-members-listing #members-list').html(response.data.results);
						$('.group-messages-members-listing .last').html('');
						$('.group-messages-members-listing .last').html(response.data.pagination);
						page = response.data.page;
						$('#item-body #group-messages-container .bb-groups-messages-left .bp-messages-feedback').hide();
						$('#item-body #group-messages-container .bb-groups-messages-left .total-members-text').html('');
						$('#item-body #group-messages-container .bb-groups-messages-left .total-members-text').html( response.data.total_count );
						return page;
					} else {
						$('.group-messages-members-listing #members-list').html('');
						$('.group-messages-members-listing .last').html('');
						$('#group-messages-container .bb-groups-messages-left .bp-messages-feedback .bp-feedback').addClass('error');
						feedbackParagraphTagSelectorLeft.html(BP_Nouveau.group_messages.no_member);
					}

				}
			});

			$('.group-messages-select-members-dropdown').on('change', function () {
				page  = 1;
				// Reset select2
				$group_messages_select.val('').trigger( 'change' );



				var valueSelected 		 		 	 = this.value;
				if ( valueSelected ) {
					feedbackSelector.addClass( 'bp-messages-feedback-hide' );
					feedbackSelectorLeft.addClass( 'bp-messages-feedback-hide' );
					feedbackParagraphTagSelector.html( '' );
					if ( 'all' === valueSelected ) {
						$('.bb-groups-messages-left').removeClass('bb-select-member-view');
						if ( $('.bb-groups-messages-left .bb-groups-messages-left-inner .bb-panel-head .add-more-members').length ) {
							$('.bb-groups-messages-left .bb-groups-messages-left-inner .bb-panel-head .add-more-members').remove();
						}
						if ( ! $( '#group-messages-container .bb-groups-messages-left .group-messages-members-listing #members-list').hasClass( 'all-members') ) {
							$( '#group-messages-container .bb-groups-messages-left .group-messages-members-listing #members-list').addClass( 'all-members' );
						}

						$( '#group-messages-send-to-input option' ).each(function() {
							$(this).remove();
						});
						if ( ! $group_messages_select.find( "option[value='all']" ).length ) { // jshint ignore:line
							var newOption = new Option( BP_Nouveau.group_messages.select_default_text, BP_Nouveau.group_messages.select_default_value, true, true);
							$group_messages_select.append(newOption).trigger('change');
						}
						$group_messages_select.select2().prop( 'disabled', true );
						feedbackSelector.removeClass( 'bp-messages-feedback-hide' );
						$( '#group-messages-container .bb-groups-messages-right .bp-messages-feedback .bp-feedback' ).addClass( 'info' );
						feedbackParagraphTagSelector.html( BP_Nouveau.group_messages.invites_form_all );
						$( '#group-messages-container .bb-groups-messages-left .group-messages-search' ).hide();
						$group_messages_select.select2( 'data', { id: BP_Nouveau.group_messages.select_default_value, text: BP_Nouveau.group_messages.select_default_text });
						$group_messages_select.val('all').trigger( 'change' );

						var data = {
							'action': 'groups_get_group_members_listing',
							'nonce' : BP_Nouveau.group_messages.nonces.retrieve_group_members,
							'group' : BP_Nouveau.group_messages.group_id,
							'type'  : 'all',
							'page'  : page
						};

						$.ajax({
							type: 'POST',
							url: BP_Nouveau.ajaxurl,
							async: false,
							data: data,
							success: function (response) {
								if (response.success && 'no_member' !== response.data.results) {
									$('.group-messages-members-listing #members-list').html('');
									$('.group-messages-members-listing #members-list').html(response.data.results);
									$('.group-messages-members-listing .last').html('');
									$('.group-messages-members-listing .last').html(response.data.pagination);
									page = response.data.page;
									$('#item-body #group-messages-container .bb-groups-messages-left .bp-messages-feedback').hide();
									$('#item-body #group-messages-container .bb-groups-messages-left .total-members-text').html('');
									$('#item-body #group-messages-container .bb-groups-messages-left .total-members-text').html( response.data.total_count );
									return page;
								} else {
									$('.group-messages-members-listing #members-list').html('');
									$('.group-messages-members-listing .last').html('');
									$('#group-messages-container .bb-groups-messages-left .bp-messages-feedback .bp-feedback').addClass('error');
									feedbackParagraphTagSelectorLeft.html(BP_Nouveau.group_messages.no_member);
								}

							}
						});
						return 1;
					} else {
						$('.bb-groups-messages-left').addClass('bb-select-member-view');
						if ( $( '#group-messages-container .bb-groups-messages-left .group-messages-members-listing #members-list').hasClass( 'all-members') ) {
							$( '#group-messages-container .bb-groups-messages-left .group-messages-members-listing #members-list').removeClass( 'all-members' );
						}

						$( '#group-messages-send-to-input option[value="all"]' ).each(function() {
							$(this).remove();
						});
						$group_messages_select.select2().prop( 'disabled', false );
						$( '#group-messages-container .bb-groups-messages-right #send_group_message_form .bb-groups-messages-right-top .select2-container .selection .select2-selection--multiple .select2-selection__rendered .select2-search--inline .select2-search__field' ).prop( 'disabled', true );
						feedbackSelector.removeClass( 'bp-messages-feedback-hide' );
						feedbackSelectorLeft.removeClass( 'bp-messages-feedback-hide' );
						$( '#group-messages-container .bb-groups-messages-right .bp-messages-feedback .bp-feedback' ).addClass( 'info' );
						feedbackParagraphTagSelector.html( BP_Nouveau.group_messages.invites_form_separate );
						$( '#group-messages-container .bb-groups-messages-left .group-messages-search' ).show();
						$( '#group-messages-container .bb-groups-messages-left .bp-messages-feedback .bp-feedback' ).addClass( 'loading' );
						feedbackParagraphTagSelectorLeft.html( BP_Nouveau.group_messages.loading );

						var data = {
							'action': 'groups_get_group_members_listing',
							'nonce' : BP_Nouveau.group_messages.nonces.retrieve_group_members,
							'group' : BP_Nouveau.group_messages.group_id,
							'type'  : 'individual',
							'page'  : page
						};

						$.ajax({
							type: 'POST',
							url: BP_Nouveau.ajaxurl,
							async: false,
							data: data,
							success: function (response) {
								if ( response.success && 'no_member' !== response.data.results ) {
									$( '.group-messages-members-listing #members-list').html( '' );
									$( '.group-messages-members-listing #members-list').html( response.data.results );
									$( '.group-messages-members-listing .last').html( '' );
									$( '.group-messages-members-listing .last').html( response.data.pagination );
									page = response.data.page;
									$( '#item-body #group-messages-container .bb-groups-messages-left .bp-messages-feedback').hide();
									$('#item-body #group-messages-container .bb-groups-messages-left .total-members-text').html('');
									return page;
								} else {
									$( '.group-messages-members-listing #members-list').html( '' );
									$( '.group-messages-members-listing .last').html( '' );
									$( '#group-messages-container .bb-groups-messages-left .bp-messages-feedback .bp-feedback' ).addClass( 'error' );
									feedbackParagraphTagSelectorLeft.html( BP_Nouveau.group_messages.no_member );
								}

							}
						});
					}
				}
			});

			$( document ).on( 'click', '#item-body #group-messages-container .bb-groups-messages-left #members-list li.load-more .group-message-load-more-button', function() {
				$( '#group-messages-container .group-messages-members-listing .last #bp-group-messages-next-page' ).trigger( 'click' );
			});
			$( document ).on( 'click', '#item-body #group-messages-container .bb-groups-messages-left #members-list li .action .group-add-remove-invite-button', function() {

				var userId   = $( this ).attr( 'data-bp-user-id');
				var userName = $( this ).attr( 'data-bp-user-name');

				var data = {
					id: userId,
					text: userName
				};

				if ( $( this ).closest( 'li' ).hasClass( 'selected' ) ) {

					$( this ).closest( 'li' ).removeClass( 'selected' );

					var newArray = [];
					var newData = $.grep( $group_messages_select.select2('data'), function (value) {
						return value['id'] != userId; // jshint ignore:line
					});

					newData.forEach(function(data) {
						newArray.push(+data.id);
					});

					$group_messages_select.val(newArray).trigger('change');

					$( '#group-messages-send-to-input option[value="' + userId + '"]' ).each(function() {
						$(this).remove();
					});

					$( this ).attr( 'data-bp-tooltip', BP_Nouveau.group_messages.add_recipient );

				} else {
					$( this ).closest( 'li' ).addClass( 'selected' );
					if ( ! $group_messages_select.find( "option[value='" + data.id + "']" ).length ) { // jshint ignore:line
						var newOption = new Option(data.text, data.id, true, true);
						$group_messages_select.append(newOption).trigger('change');
					}
					$( this ).attr( 'data-bp-tooltip', BP_Nouveau.group_messages.remove_recipient );
				}


			});

			$( document ).on( 'click', '#item-body #group-messages-container .bb-groups-messages-left .last #bp-group-messages-next-page', function() {
				$( '#item-body #group-messages-container .bb-groups-messages-left .bp-messages-feedback').show();
				$( '#item-body #group-messages-container .bb-groups-messages-left .bp-messages-feedback .bp-feedback').addClass( 'info' );
				feedbackParagraphTagSelectorLeft.html( BP_Nouveau.group_messages.loading );
				var data = {
					'action': 'groups_get_group_members_listing',
					'nonce' : BP_Nouveau.group_messages.nonces.retrieve_group_members,
					'group' : BP_Nouveau.group_messages.group_id,
					'type'  : $( '.group-messages-select-members-dropdown :selected' ).val(),
					'page'  : page
				};

				$.ajax({
					type: 'POST',
					url: BP_Nouveau.ajaxurl,
					data: data,
					success: function (response) {
						if ( response.success && 'no_member' !== response.data.results ) {
							$( '.group-messages-members-listing #members-list').append( response.data.results );
							$( '.group-messages-members-listing .last').html( '' );
							$( '.group-messages-members-listing .last').html( response.data.pagination );
							page = response.data.page;
							$( '#item-body #group-messages-container .bb-groups-messages-left .bp-messages-feedback').hide();
						} else {
							$( '.group-messages-members-listing #members-list').html( '' );
							$( '.group-messages-members-listing .last').html( '' );
							$( '#group-messages-container .bb-groups-messages-left .bp-messages-feedback .bp-feedback' ).addClass( 'error' );
							feedbackParagraphTagSelectorLeft.html( BP_Nouveau.group_messages.no_member );
						}

					}
				});
			} );

			$( document ).on( 'click', '#item-body #group-messages-container .bb-groups-messages-left .last #bp-group-messages-prev-page', function() {
				$( '#item-body #group-messages-container .bb-groups-messages-left .bp-messages-feedback').show();
				$( '#item-body #group-messages-container .bb-groups-messages-left .bp-messages-feedback .bp-feedback').addClass( 'info' );
				feedbackParagraphTagSelectorLeft.html( BP_Nouveau.group_messages.loading );
				page = page - 2;
				var data = {
					'action': 'groups_get_group_members_listing',
					'nonce' : BP_Nouveau.group_messages.nonces.retrieve_group_members,
					'group' : BP_Nouveau.group_messages.group_id,
					'type'  : $( '.group-messages-select-members-dropdown :selected' ).val(),
					'page'  : page
				};

				$.ajax({
					type: 'POST',
					url: BP_Nouveau.ajaxurl,
					data: data,
					success: function (response) {
						if ( response.success && 'no_member' !== response.data.results ) {
							$( '.group-messages-members-listing #members-list').html( '' );
							$( '.group-messages-members-listing #members-list').html( response.data.results );
							$( '.group-messages-members-listing .last').html( '' );
							$( '.group-messages-members-listing .last').html( response.data.pagination );
							$( '#item-body #group-messages-container .bb-groups-messages-left .bp-messages-feedback').hide();
							page = response.data.page;
						} else {
							$( '.group-messages-members-listing #members-list').html( '' );
							$( '.group-messages-members-listing .last').html( '' );
							$( '#group-messages-container .bb-groups-messages-left .bp-messages-feedback .bp-feedback' ).addClass( 'error' );
							feedbackParagraphTagSelectorLeft.html( BP_Nouveau.group_messages.no_member );
						}

					}
				});
			} );

			$( document ).on( 'click', '#item-body #group-messages-container .bb-groups-messages-left #group_messages_search', function() {
				var searchText = $( '#item-body #group-messages-container .bb-groups-messages-left #group_messages_search' ).val();
				if ( ''  === searchText )  {
					return false;
				}

				var data = {
					'action': 'groups_get_group_members_listing',
					'nonce' : BP_Nouveau.group_messages.nonces.retrieve_group_members,
					'group' : BP_Nouveau.group_messages.group_id,
					'type'  : $( '#item-body #group-messages-container .bb-groups-messages-left #group_messages_search' ).val(),
					'term'  : searchText,
					'page'  : 1
				};

				$.ajax({
					type: 'POST',
					url: BP_Nouveau.ajaxurl,
					data: data,
					success: function (response) {
						if ( response.success && 'no_member' !== response.data.results ) {
							$( '.group-messages-members-listing #members-list').html( '' );
							$( '.group-messages-members-listing #members-list').html( response.data.results );
							$( '.group-messages-members-listing .last').html( '' );
							$( '.group-messages-members-listing .last').html( response.data.pagination );
							$( '#item-body #group-messages-container .bb-groups-messages-left .bp-messages-feedback').hide();
							page = response.data.page;
						} else {
							$( '.group-messages-members-listing #members-list').html( '' );
							$( '.group-messages-members-listing .last').html( '' );
							$( '#group-messages-container .bb-groups-messages-left .bp-messages-feedback .bp-feedback' ).addClass( 'error' );
							feedbackParagraphTagSelectorLeft.html( BP_Nouveau.group_messages.no_member );
						}

					}
				});
			});

			$( document ).on( 'click', '#item-body #group-messages-container .bb-groups-messages-right #send_group_message_form .bb-groups-messages-right-bottom #send_group_message_button', function( e ) {
				e.preventDefault();
				var user, type;
				var users_list = [];
				if ( 'all' === $( '.group-messages-select-members-dropdown :selected' ).val() ) {
					user 	   = 'all';
					users_list = [];
				} else {
					user       = 'individual';
					var newData = $.grep( $group_messages_select.select2('data'), function (value) {
						return value['id'] != 0; // jshint ignore:line
					});

					newData.forEach(function(data) {
						users_list.push(+data.id);
					});
				}

				if ( 'open' === $( '.group-messages-type :selected' ).val() ) {
					type = 'open';
				} else {
					type = 'private';
				}

				var content = '';
				var editor = '';
				if ( typeof window.group_messages_editor !== 'undefined' ) {
					editor = window.group_messages_editor;
				}

				content = editor.getContent();
				if ( editor && $.trim( editor.getContent().replace('<p><br></p>','') ) === '' ) {
					content = '';
				} else if ( ! editor && $.trim( $( '#item-body #group-messages-container .bb-groups-messages-right #send_group_message_form'  ).find('#group_message_content').val() ) === '' ) {
					content = '';
				}

				var media   	 = $( '#item-body #group-messages-container .bb-groups-messages-right #send_group_message_form .bb-groups-messages-right-bottom #bp_group_messages_media' ).val();
				var gif     	 = $( '#item-body #group-messages-container .bb-groups-messages-right #send_group_message_form .bb-groups-messages-right-bottom #bp_group_messages_gif' ).val();
				var contentError = $( '#item-body #group-messages-container .bb-groups-messages-right #send_group_message_form .bb-groups-messages-right-top .bp-messages-feedback .bp-feedback-content-no-error' );
				var recipientError = $( '#item-body #group-messages-container .bb-groups-messages-right #send_group_message_form .bb-groups-messages-right-top .bp-messages-feedback .bp-feedback-recipient-no-error' );

				if  ( '' === content && '' === media && '' === gif ) {
					if ( ! contentError.length ) {
						var feedbackHtml = '<div class="bp-feedback error bp-feedback-content-no-error"><span class="bp-icon" aria-hidden="true"></span><p> ' + BP_Nouveau.group_messages.no_content + ' </p></div>';
						$('#item-body #group-messages-container .bb-groups-messages-right #send_group_message_form .bb-groups-messages-right-top .bp-messages-feedback').append(feedbackHtml);
					}
					return false;
				} else {
					if ( contentError.length ) {
						contentError.remove();
					}
				}

				if ( 'all' !== $( '.group-messages-select-members-dropdown :selected' ).val() && 0 === users_list.length ) {
					if ( ! recipientError.length ) {
						var recipientHtml = '<div class="bp-feedback error bp-feedback-content-no-error"><span class="bp-icon" aria-hidden="true"></span><p> ' + BP_Nouveau.group_messages.no_recipient + ' </p></div>';
						$('#item-body #group-messages-container .bb-groups-messages-right #send_group_message_form .bb-groups-messages-right-top .bp-messages-feedback').append(recipientHtml);
					}
					return false;
				}  else {
					if ( recipientError.length ) {
						recipientError.remove();
					}
				}

				var data = {
					'action'  	 	: 'groups_get_group_members_send_message',
					'nonce'   	 	: BP_Nouveau.group_messages.nonces.send_messages_users,
					'group'   	 	: BP_Nouveau.group_messages.group_id,
					'content' 	 	: $( '#item-body #group-messages-container .bb-groups-messages-right #send_group_message_form .bb-groups-messages-right-bottom #group_message_content_hidden' ).val(),
					'media'   	 	: media,
					'users'   		: user,
					'users_list'    : users_list,
					'type'    		: type,
					'gif'     	 	: gif
				};

				$.ajax({
					type: 'POST',
					url: BP_Nouveau.ajaxurl,
					data: data,
					success: function (response) {
						if ( response.success ) {
							$( '#item-body #group-messages-container .bb-groups-messages-left' ).hide();
							$( '#item-body #group-messages-container .bb-groups-messages-right' ).html('');
							var feedbackHtml = '<div class="bp-feedback success bp-feedback-content-no-error"><span class="bp-icon" aria-hidden="true"></span><p> ' + response.data.feedback + ' </p></div>';
							$('#item-body #group-messages-container .bb-groups-messages-right').append(feedbackHtml);
							if ( response.data.redirect_link ) {
								window.location.href = response.data.redirect_link;
							}
						} else {
							$( '#item-body #group-messages-container .bb-groups-messages-left' ).hide();
							$( '#item-body #group-messages-container .bb-groups-messages-right' ).html('');
							var feedbackHtml = '<div class="bp-feedback error bp-feedback-content-no-error"><span class="bp-icon" aria-hidden="true"></span><p> ' + response.data.feedback + ' </p></div>';
							$('#item-body #group-messages-container .bb-groups-messages-right').append(feedbackHtml);
						}
					}
				});

			});

			$( document ).on( 'click', '#item-body #group-messages-container .bb-groups-messages-left #group_messages_search_submit', function( e ) {
				e.preventDefault();
				$( '#item-body #group-messages-container .bb-groups-messages-left .bp-messages-feedback').show();
				$( '#group-messages-container .bb-groups-messages-left .bp-messages-feedback .bp-feedback' ).removeClass( 'error' );
				$( '#group-messages-container .bb-groups-messages-left .bp-messages-feedback .bp-feedback' ).addClass( 'info' );
				feedbackParagraphTagSelectorLeft.html( BP_Nouveau.group_messages.loading );
				var term = $( '#item-body #group-messages-container .bb-groups-messages-left .group-messages-search .bp-search #group_messages_search_form #group_messages_search' ).val();
				var data = {
					'action': 'groups_get_group_members_listing',
					'nonce' : BP_Nouveau.group_messages.nonces.retrieve_group_members,
					'group' : BP_Nouveau.group_messages.group_id,
					'type'  : $( '.group-messages-select-members-dropdown :selected' ).val(),
					'page'  : 1,
					'term'  : term
				};

				$.ajax({
					type: 'POST',
					url: BP_Nouveau.ajaxurl,
					data: data,
					success: function (response) {
						if ( response.success && 'no_member' !== response.data.results ) {
							$( '.group-messages-members-listing #members-list').html( '' );
							$( '.group-messages-members-listing #members-list').html( response.data.results );
							$( '.group-messages-members-listing .last').html( '' );
							$( '.group-messages-members-listing .last').html( response.data.pagination );
							$( '#item-body #group-messages-container .bb-groups-messages-left .bp-messages-feedback').hide();
						} else {
							$( '.group-messages-members-listing #members-list').html( '' );
							$( '.group-messages-members-listing .last').html( '' );
							$( '#group-messages-container .bb-groups-messages-left .bp-messages-feedback .bp-feedback' ).addClass( 'error' );
							feedbackParagraphTagSelectorLeft.html( BP_Nouveau.group_messages.no_member );
						}
					}
				});
			});

			$( '.bb-close-select-members' ).on( 'click', function(e) {
				e.preventDefault();
				$('.bb-groups-messages-left').removeClass('bb-select-member-view');
				if ( ! $('.bb-groups-messages-left .bb-groups-messages-left-inner .bb-panel-head .add-more-members').length ) {
					$('.bb-groups-messages-left .bb-groups-messages-left-inner .bb-panel-head').append('<div class="add-more-members"><a class="bb-add-members" href="#"><span class="dashicons dashicons-plus-alt"></span></a></div>');
				}
			});

			$( document ).on( 'click', '#item-body #group-messages-container .bb-groups-messages-left .bb-groups-messages-left-inner .bb-panel-head .add-more-members .bb-add-members', function() {
				$('.bb-groups-messages-left').addClass('bb-select-member-view');
			});
		},

		/**
		 * [setupGlobals description]
		 * @return {[type]} [description]
		 */
		setupGlobals: function() {

			if ( typeof window.Dropzone !== 'undefined' && typeof BP_Nouveau.media !== 'undefined' ) {

				// set up dropzones auto discover to false so it does not automatically set dropzones
				window.Dropzone.autoDiscover = false;

				this.dropzone_options = {
					url: BP_Nouveau.ajaxurl,
					timeout: 3 * 60 * 60 * 1000,
					acceptedFiles: 'image/*',
					autoProcessQueue: true,
					addRemoveLinks: true,
					uploadMultiple: false,
					maxFilesize: typeof BP_Nouveau.media.max_upload_size !== 'undefined' ? BP_Nouveau.media.max_upload_size : 2
				};
			}

			this.dropzone_obj = null;
			this.dropzone_media = [];

			this.models = [];
		},

		activateTinyMce: function() {
			if ( !_.isUndefined(window.MediumEditor) ) {

				window.group_messages_editor = new window.MediumEditor('#group_message_content',{
					placeholder: {
						text: BP_Nouveau.group_messages.type_message,
						hideOnClick: true
					},
					toolbar: {
						buttons: ['bold', 'italic', 'unorderedlist','orderedlist', 'quote', 'anchor' ]
					}
				});

				window.group_messages_editor.subscribe(
					'editableInput',
					function () {
						$( '#group_message_content_hidden' ).val( window.group_messages_editor.getContent() );
					}
				);

				if (!_.isUndefined(BP_Nouveau.media) && !_.isUndefined(BP_Nouveau.media.emoji) && $('#group_message_content').length ) {

					$('#group_message_content').emojioneArea({
						standalone: true,
						hideSource: false,
						container: $('#whats-new-toolbar > .post-emoji'),
						autocomplete: false,
						pickerPosition: 'bottom',
						hidePickerOnBlur: true,
						useInternalCDN: false,
						events: {
							emojibtn_click: function () {
								$('#group_message_content')[0].emojioneArea.hidePicker();
							}
						}
					});
				}

				// check for mentions in the url, if set any then focus to editor
				var mention = bp.Nouveau.getLinkParams( null, 'r' ) || null;

				// Check for mention
				if ( ! _.isNull( mention ) ) {
					$('#message_content').focus();
				}

			} else if ( typeof tinymce !== 'undefined' ) {
				tinymce.EditorManager.execCommand( 'mceAddEditor', true, 'message_content' ); // jshint ignore:line
			}
		},

		addMentions: function() {
			// Add autocomplete to send_to field
			$( this.el ).find( '#group-messages-send-to-input' ).bp_mentions( {
				data: [],
				suffix: ' '
			} );
		},

		addSelect2: function( $input ) {

			var ArrayData = [];
			$input.select2({
				placeholder: $input.attr( 'placeholder' ),
				minimumInputLength: 1,
				ajax: {
					url: bp.ajax.settings.url,
					dataType: 'json',
					delay: 250,
					data: function(params) {
						return $.extend( {}, params, {
							nonce: BP_Nouveau.group_messages.nonces.retrieve_group_members,
							action: 'groups_get_group_potential_user_send_messages',
							group: BP_Nouveau.group_messages.group_id
						});
					},
					cache: true,
					processResults: function( data ) {

						// Removed the element from results if already selected.
						if ( false === jQuery.isEmptyObject( ArrayData ) ) {
							$.each( ArrayData, function( index, value ) {
								for(var i=0;i<data.data.results.length;i++){
									if(data.data.results[i].id === value){
										data.data.results.splice(i,1);
									}
								}
							});
						}

						return {
							results: data && data.success? data.data.results : []
						};
					}
				}
			});

			// Add element into the Arrdata array.
			$input.on('select2:select', function(e) {
				var data = e.params.data;
				ArrayData.push(data.id);
			});

			// Remove element into the Arrdata array.
			$input.on('select2:unselect', function(e) {
				var data = e.params.data;
				ArrayData = jQuery.grep(ArrayData, function(value) {
					return value !== data.id;
				});
			});

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
		loadMoreMessageMembers: function () {
			var element 		  =  $( '#group-messages-container .group-messages-members-listing #members-list li.load-more' );

			if ( element.length ) {
				var top_of_element 	  = element.offset().top;
				var bottom_of_element = element.offset().top + element.outerHeight();
				var bottom_of_screen  = $(window).scrollTop() + $(window).innerHeight();
				var top_of_screen 	  = $(window).scrollTop();

				if ((bottom_of_screen > top_of_element) && (top_of_screen < bottom_of_element)) {
					element.remove();
					$( '#group-messages-container .group-messages-members-listing .last #bp-group-messages-next-page' ).trigger( 'click' );
				}
			}
		}

	};

	// Launch BP Nouveau Groups
	bp.Nouveau.GroupMessages.start();

} )( bp, jQuery );
