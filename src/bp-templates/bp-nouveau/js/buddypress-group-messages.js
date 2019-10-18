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
			var selected  = [];

			// Activate bp_mentions
			this.addSelect2( $group_messages_select );
			this.activateTinyMce();

			var feedbackSelector 	 		 	 = $( '#group-messages-container .bb-groups-messages-right .bp-messages-feedback' );
			var feedbackParagraphTagSelector 	 = $( '#group-messages-container .bb-groups-messages-right .bp-messages-feedback .bp-feedback p' );
			var feedbackSelectorLeft 	 		 = $( '#group-messages-container .bb-groups-messages-left .group-messages-members-listing .bp-messages-feedback' );
			var feedbackParagraphTagSelectorLeft = $( '#group-messages-container .bb-groups-messages-left .group-messages-members-listing .bp-messages-feedback .bp-feedback p' );


			$('.group-messages-select-members-dropdown').on('change', function () {

				// Reset select2
				$group_messages_select.val('').trigger( 'change' );

				var valueSelected 		 		 	 = this.value;
				if ( valueSelected ) {
					feedbackSelector.addClass( 'bp-messages-feedback-hide' );
					feedbackSelectorLeft.addClass( 'bp-messages-feedback-hide' );
					feedbackParagraphTagSelector.html( '' );
					if ( 'all' === valueSelected ) {
						feedbackSelector.removeClass( 'bp-messages-feedback-hide' );
						$( '#group-messages-container .bb-groups-messages-right .bp-messages-feedback .bp-feedback' ).addClass( 'info' );
						feedbackParagraphTagSelector.html( BP_Nouveau.group_messages.invites_form_all );
						$( '#group-messages-container .bb-groups-messages-left .group-messages-search' ).hide();
						$( '#group-messages-container .bb-groups-messages-left .group-messages-members-listing' ).hide();
						$group_messages_select.select2( 'data', { id: BP_Nouveau.group_messages.select_default_value, text: BP_Nouveau.group_messages.select_default_text });
						$group_messages_select.val('all').trigger( 'change' );
						return 1;
					} else {
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




				// selected = $group_messages_select.select2( 'data' );
				//
				// var found = jQuery.inArray( userId, selected );
				// if (found >= 0) {
				// 	// Element was found, remove it.
				// 	selected.splice( found, 1 );
				// } else {
				// 	var newOption = new Option(data.text, data.id, false, false);
				// 	$group_messages_select.append(newOption).trigger('change');
				// 	selected.push( userId );
				// }

				if ( $( this ).closest( 'li' ).hasClass( 'selected' ) ) {

					$( this ).closest( 'li' ).removeClass( 'selected' );


					var new_data = $.grep( $group_messages_select.select2('data'), function ( value ) {
						return value['id'] != userId;
					});
					console.log( new_data );

					$group_messages_select.val( null ).trigger( 'change' );
					$group_messages_select.select2('data', new_data);

				} else {
					$( this ).closest( 'li' ).addClass( 'selected' );
					// Set the value, creating a new option if necessary
					if ( ! $group_messages_select.find( "option[value='" + data.id + "']" ).length ) {
						// Create a DOM Option and pre-select by default
						var newOption = new Option(data.text, data.id, true, true);
						// Append it to the select
						$group_messages_select.append(newOption).trigger('change');
						console.log('add');
						console.log(data.id);
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

				var editor = new window.MediumEditor('#group_message_content',{
					placeholder: {
						text: '',
						hideOnClick: true
					},
					toolbar: {
						buttons: ['bold', 'italic', 'unorderedlist','orderedlist', 'quote', 'anchor' ]
					}
				});

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
				tinymce.EditorManager.execCommand( 'mceAddEditor', true, 'message_content' );
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
