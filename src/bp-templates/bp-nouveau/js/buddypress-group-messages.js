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

			$group_messages_select.on('select2:unselect', function(e) {
				var data = e.params.data;
				$( '#group-messages-send-to-input option[value="' + data.id + '"]' ).each(function() {
					$(this).remove();
				});
				$( '#item-body #group-messages-container .bb-groups-messages-left #members-list li.' + data.id ).removeClass( 'selected' );
			});

			$group_messages_select.select2().prop( 'disabled', true );

			$('.group-messages-select-members-dropdown').on('change', function () {

				// Reset select2
				$group_messages_select.val('').trigger( 'change' );

				var valueSelected 		 		 	 = this.value;
				if ( valueSelected ) {
					feedbackSelector.addClass( 'bp-messages-feedback-hide' );
					feedbackSelectorLeft.addClass( 'bp-messages-feedback-hide' );
					feedbackParagraphTagSelector.html( '' );
					if ( 'all' === valueSelected ) {
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
						$( '#group-messages-container .bb-groups-messages-left .group-messages-members-listing' ).hide();
						$group_messages_select.select2( 'data', { id: BP_Nouveau.group_messages.select_default_value, text: BP_Nouveau.group_messages.select_default_text });
						$group_messages_select.val('all').trigger( 'change' );
						return 1;
					} else {
						$( '#group-messages-send-to-input option[value="all"]' ).each(function() {
							$(this).remove();
						});
						$group_messages_select.select2().prop( 'disabled', false );
						feedbackSelector.removeClass( 'bp-messages-feedback-hide' );
						feedbackSelectorLeft.removeClass( 'bp-messages-feedback-hide' );
						$( '#group-messages-container .bb-groups-messages-right .bp-messages-feedback .bp-feedback' ).addClass( 'info' );
						feedbackParagraphTagSelector.html( BP_Nouveau.group_messages.invites_form_separate );
						$( '#group-messages-container .bb-groups-messages-left .group-messages-search' ).show();
						$( '#group-messages-container .bb-groups-messages-left .group-messages-members-listing' ).show();
						$( '#group-messages-container .bb-groups-messages-left .bp-messages-feedback .bp-feedback' ).addClass( 'loading' );
						feedbackParagraphTagSelectorLeft.html( BP_Nouveau.group_messages.loading );

						var data = {
							'action': 'groups_get_group_members_listing',
							'nonce' : BP_Nouveau.group_messages.nonces.retrieve_group_members,
							'group' : BP_Nouveau.group_messages.group_id,
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

				} else {
					$( this ).closest( 'li' ).addClass( 'selected' );
					if ( ! $group_messages_select.find( "option[value='" + data.id + "']" ).length ) { // jshint ignore:line
						var newOption = new Option(data.text, data.id, true, true);
						$group_messages_select.append(newOption).trigger('change');
					}
				}


			});

			$( document ).on( 'click', '#item-body #group-messages-container .bb-groups-messages-left .last #bp-group-messages-next-page', function() {
				$( '#item-body #group-messages-container .bb-groups-messages-left .bp-messages-feedback').show();
				var data = {
					'action': 'groups_get_group_members_listing',
					'nonce' : BP_Nouveau.group_messages.nonces.retrieve_group_members,
					'group' : BP_Nouveau.group_messages.group_id,
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
				page = page - 2;
				var data = {
					'action': 'groups_get_group_members_listing',
					'nonce' : BP_Nouveau.group_messages.nonces.retrieve_group_members,
					'group' : BP_Nouveau.group_messages.group_id,
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
				var data = {
					'action': 'groups_get_group_members_listing',
					'nonce' : BP_Nouveau.group_messages.nonces.retrieve_group_members,
					'group' : BP_Nouveau.group_messages.group_id,
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
							$( '#item-body #group-messages-container .bb-groups-messages-right' ).html('');
							var feedbackHtml = '<div class="bp-feedback success bp-feedback-content-no-error"><span class="bp-icon" aria-hidden="true"></span><p> ' + response.data.feedback + ' </p></div>';
							$('#item-body #group-messages-container .bb-groups-messages-right').append(feedbackHtml);
						} else {
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
						text: '',
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

				if (!_.isUndefined(BP_Nouveau.media) && !_.isUndefined(BP_Nouveau.media.emoji)) {

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
		}

	};

	// Launch BP Nouveau Groups
	bp.Nouveau.GroupMessages.start();

} )( bp, jQuery );
