/* global bp, BP_Nouveau, _, Backbone, tinymce, bp_media_dropzone, BBTopicsManager */
/* @version [BBVERSION] */
/*jshint esversion: 6 */
window.wp = window.wp || {};
window.bp = window.bp || {};

( function ( exports, $ ) {
	bp.Nouveau = bp.Nouveau || {};

	// Bail if not set.
	if ( 'undefined' === typeof bp.Nouveau.Activity || 'undefined' === typeof BP_Nouveau ) {
		return;
	}

	var bpNouveau              = 'undefined' === typeof bp.Nouveau.Activity.LocalizeActivityVars ? BP_Nouveau : bp.Nouveau.Activity.LocalizeActivityVars,
		bbRlAjaxUrl            = bpNouveau.ajaxurl,
		bbRlMedia              = bpNouveau.media,
		bbRlActivity           = bpNouveau.activity,
		bbRlNonce              = bpNouveau.nonces,
		bbRlDocument           = bpNouveau.document,
		bbRlVideo              = bpNouveau.video,
		bbRlIsActivitySchedule = bpNouveau.activity_schedule,
		bbRlIsActivityPolls    = bpNouveau.activity_polls,
		bbRlPlaceholderText    = bpNouveau.anchorPlaceholderText;

	_.extend( bp, _.pick( wp, 'Backbone', 'ajax', 'template' ) );

	bp.Models      = bp.Models || {};
	bp.Collections = bp.Collections || {};
	bp.Views       = bp.Views || {};

	// Set the global variable for the edit activity privacy/album_id/folder_id/group_id maintain.
	bp.privacyEditable       = true;
	bp.album_id              = 0;
	bp.folder_id             = 0;
	bp.group_id              = 0;
	bp.privacy               = 'public';
	bp.draft_ajax_request    = null;
	bp.old_draft_data        = false;
	bp.draft_activity        = {
		object: false,
		data_key: false,
		data: false,
		post_action: 'update',
		allow_delete_media: false,
		display_post: ''
	};
	bp.draft_local_interval  = false;
	bp.draft_ajax_interval   = false;
	bp.draft_content_changed = false;

	/**
	 * [Activity description]
	 *
	 * @type {Object}
	 */
	bp.Nouveau.Activity.postForm = {
		start: function () {
			this.views           = new Backbone.Collection();
			this.ActivityObjects = new bp.Collections.ActivityObjects();
			this.buttons         = new Backbone.Collection();

			if ( ! _.isUndefined( window.Dropzone ) && ! _.isUndefined( bbRlMedia ) ) {
				this.dropzoneView();
			}

			this.postFormView();

			this.postFormPlaceholderView();

			// Get current draft activity.
			this.getCurrentDraftActivity();
			this.syncDraftActivity();
			this.reloadWindow();
		},

		postFormView: function () {
			this.model        = new bp.Models.Activity(
				_.pick(
					bbRlActivity.params,
					[ 'user_id', 'item_id', 'object' ]
				)
			);
			var $activityForm = $( '.bb-rl-activity-update-form' );
			// Do not carry on if the main element is not available.
			if ( ! $activityForm.length ) {
				return;
			}

			// Create the BuddyPress Uploader.
			this.postForm = new bp.Views.PostForm();

			// Add it to views.
			this.views.add( { id: 'post_form', view: this.postForm } );

			// Display it.
			this.postForm.inject( '#' + $activityForm[0].id );

			// Wrap Avatar and Content section into header.
			var $formHeaderElements = $activityForm.find(
				'#bb-rl-user-status-huddle, #bb-rl-whats-new-privacy-stage, #bb-rl-whats-new-content, #bb-rl-whats-new-attachments'
			);

			// Wrap elements into the header section.
			if ( $formHeaderElements.length ) {
				$formHeaderElements.wrapAll( '<div class="bb-rl-whats-new-form-header"></div>' );
			}

			// Move Privacy stage into status huddle.
			var $privacyStage = $activityForm.find( '#bb-rl-whats-new-privacy-stage' );
			if ( $privacyStage.length ) {
				$privacyStage.appendTo( $activityForm.find( '.bb-rl-activity-post-name-status' ) );
			}

			var $this = this;

			$( document ).on(
				'click',
				'.bb-rl-activity-update-form.modal-popup:not(.bb-rl-activity-edit) .bb-rl-activity-update-form-overlay',
				function () {

					// Store data forcefully.
					if ( ! $this.postForm.$el.hasClass( 'bb-rl-activity-edit' ) ) {
						bp.Nouveau.Activity.postForm.clearDraftInterval();
						bp.Nouveau.Activity.postForm.collectDraftActivity();
						bp.Nouveau.Activity.postForm.postDraftActivity( false, false );
					}

					setTimeout(
						function () {
							$( '.bb-rl-activity-update-form.modal-popup #bb-rl-whats-new' ).blur();
							$( '.bb-rl-activity-update-form.modal-popup #bb-rl-aw-whats-new-reset' ).trigger( 'click' );
							// Post activity hide modal.
							var $singleActivityFormWrap = $( '#bb-rl-single-activity-edit-form-wrap' );
							if ( $singleActivityFormWrap.length ) {
								$singleActivityFormWrap.hide();
							}
						},
						0
					);
				}
			);

			Backbone.trigger( 'mediaprivacy' );
		},

		postFormPlaceholderView: function () {
			// Do not carry on if the main element is not available.
			var $activityFormPlaceholder = $( '#bb-rl-activity-form-placeholder' );
			if ( ! $activityFormPlaceholder.length ) {
				return;
			}

			// Create placeholder.
			this.postFormPlaceholder = new bp.Views.PostFormPlaceholder();

			// Add it to a view collection.
			this.views.add( { id: 'bb_rl_post_form_placeholder', view: this.postFormPlaceholder } );

			// Display it within selector.
			this.postFormPlaceholder.inject( '#' + $activityFormPlaceholder[0].id );

			var $headerElements = $( '.bb-rl-activity-form-placeholder #bb-rl-user-status-huddle, .bb-rl-activity-form-placeholder #bb-rl-whats-new-content-placeholder' );

			// Wrap the elements in a header container if they exist.
			if ( $headerElements.length ) {
				$headerElements.wrapAll( '<div class="bb-rl-whats-new-form-header"></div>' );
			}
		},

		dropzoneView: function () {
			this.dropzone = null;

			// set up dropzones auto discover to false so it does not automatically set dropzones.
			window.Dropzone.autoDiscover = false;

			this.dropzone_options = {
				url                 		: bbRlAjaxUrl,
				timeout             		: 3 * 60 * 60 * 1000,
				dictFileTooBig      		: bbRlMedia.dictFileTooBig,
				dictDefaultMessage  		: bbRlMedia.dropzone_media_message,
				acceptedFiles       		: 'image/*',
				autoProcessQueue    		: true,
				addRemoveLinks      		: true,
				uploadMultiple      		: false,
				maxFiles            		: ! _.isUndefined( bbRlMedia.maxFiles ) ? bbRlMedia.maxFiles : 10,
				maxFilesize         		: ! _.isUndefined( bbRlMedia.max_upload_size ) ? bbRlMedia.max_upload_size : 2,
				dictMaxFilesExceeded		: bbRlMedia.media_dict_file_exceeded,
				dictCancelUploadConfirmation: bbRlMedia.dictCancelUploadConfirmation,
				// previewTemplate : document.getElementsByClassName( 'activity-post-media-template' )[0].innerHTML.
				maxThumbnailFilesize: ! _.isUndefined( bbRlMedia.max_upload_size ) ? bbRlMedia.max_upload_size : 2,
			};

			// if defined, add custom dropzone options.
			if ( ! _.isUndefined( bbRlMedia.dropzone_options ) ) {
				Object.assign( this.dropzone_options, bbRlMedia.dropzone_options );
			}
		},

		displayEditActivity: function ( activity_data, activity_URL_preview ) {
			bp.draft_activity.allow_delete_media = true;
			bp.draft_activity.display_post       = 'edit';
			var self                             = this;

			// reset post form before editing.
			self.postForm.$el.trigger( 'reset' );

			// set edit activity data.
			self.editActivityData = activity_data;

			this.model.set( 'edit_activity', true );
			self.postForm.$el.addClass( 'bb-rl-activity-edit' ).addClass( 'loading' );
			self.postForm.$el.find( '.bb-rl-activity-privacy__label-group' ).hide().find( 'input#group' ).attr( 'disabled', true ); // disable group visibility level.
			self.postForm.$el.removeClass( 'bp-hide' );
			self.postForm.$el.find( '#bb-rl-whats-new-toolbar' ).addClass( 'hidden' );

			// add a pause to form to let it cool down a bit.
			setTimeout(
				function () {

					var bpActivityEvent = new Event( 'bp_activity_edit' );
					bp.Nouveau.Activity.postForm.displayEditDraftActivityData( activity_data, bpActivityEvent, activity_URL_preview );
				},
				0
			);

		},

		/**
		 *
		 * Renamed it displayEditActivityPopup to displayEditActivityForm();
		 *
		 * @param activity_data
		 * @param activity_URL_preview
		 */
		displayEditActivityForm : function ( activity_data, activity_URL_preview ) {
			var self                     = this,
				$activityForm            = $( '#bb-rl-activity-form' ),
				$activityFormPlaceholder = $( '#bb-rl-activity-form-placeholder' ),
				$singleActivityFormWrap  = $( '#bb-rl-single-activity-edit-form-wrap' );
			if ( $singleActivityFormWrap.length ) {
				$singleActivityFormWrap.show();
			}
			var $whatsNew = $( '#bb-rl-whats-new' );

			// Set the global variable for the edit activity privacy/album_id/folder_id/group_id maintain.
			bp.privacyEditable = activity_data.can_edit_privacy;
			bp.album_id        = activity_data.album_id;
			bp.folder_id       = activity_data.folder_id;
			bp.group_id        = activity_data.group_id;
			bp.privacy         = activity_data.privacy;

			// Set the activity value.
			self.displayEditActivity( activity_data, activity_URL_preview );
			this.model.set( 'edit_activity', true );

			var edit_activity_editor         = $whatsNew[0];
			var edit_activity_editor_content = $( '#bb-rl-whats-new-content' )[0];

			window.activity_edit_editor = new window.MediumEditor(
				edit_activity_editor,
				{
					placeholder: {
						text: '',
						hideOnClick: true
					},
					toolbar: {
						buttons: [ 'bold', 'italic', 'unorderedlist', 'orderedlist', 'quote', 'anchor', 'pre' ],
						relativeContainer: edit_activity_editor_content,
						static: true,
						updateOnEmptySelection: true
					},
					imageDragging: false,
					anchor: {
						linkValidation: true
					}
				}
			);

			window.activity_edit_editor.subscribe(
				'editablePaste',
				function ( e ) {
					setTimeout(
						function () {
							// Wrap all target <li> elements in a single <ul>.
							var targetLiElements = $( e.target ).find( 'li' ).filter(
								function () {
									return ! $( this ).parent().is( 'ul' ) && ! $( this ).parent().is( 'ol' );
								}
							);
							if (targetLiElements.length > 0) {
								targetLiElements.wrapAll( '<ul></ul>' );
							}
						},
						0
					);
				}
			);

			// Now Show the Modal.
			$activityForm.addClass( 'modal-popup' ).closest( 'body' ).addClass( 'bb-rl-activity-modal-open' );

			$activityFormPlaceholder.show();

			setTimeout(
				function () {
					$( '#bb-rl-whats-new img.emoji' ).each(
						function ( index, Obj) {
							$( Obj ).addClass( 'bb-rl-emojioneemoji' );
							var emojis = $( Obj ).attr( 'alt' );
							$( Obj ).attr( 'data-emoji-char', emojis );
							$( Obj ).removeClass( 'emoji' );
						}
					);
				},
				10
			);

			self.activityEditHideModalEvent();
		},

		activityEditHideModalEvent: function () {
			var self      = this,
				$document = $( document );

			$document.on(
				'keyup',
				function ( event ) {
					if ( 27 === event.keyCode && false === event.ctrlKey ) {
						$( '.bb-rl-activity-update-form.modal-popup.bb-rl-activity-edit #bb-rl-aw-whats-new-reset' ).trigger( 'click' );
					}
				}
			);

			$document.on(
				'click',
				'.bb-rl-activity-update-form.modal-popup.bb-rl-activity-edit #bb-rl-aw-whats-new-reset',
				function () {
					self.postActivityEditHideModal();
				}
			);

		},

		postActivityEditHideModal: function () {

			// Reset Global variable after edit activity.
			bp.privacyEditable = true;
			bp.album_id        = 0;
			bp.folder_id       = 0;
			bp.group_id        = 0;
			bp.privacy         = 'public';

			$( '.bb-rl-activity-update-form.modal-popup' ).removeClass( 'modal-popup bb-rl-group-activity' ).closest( 'body' ).removeClass( 'bb-rl-activity-modal-open' );

			var $activityFormPlaceholder = $( '#bb-rl-activity-form-placeholder' ),
				$singleActivityFormWrap  = $( '#bb-rl-single-activity-edit-form-wrap' ),
				$tabActivityFormWrap     = $( '#bb-rl-activity-form' ),
				$whatsNewContent         = $( '#bb-rl-whats-new-content' );

			if ( $whatsNewContent.parent().is( '.bb-rl-edit-activity-content-wrap' ) ) {
				$whatsNewContent.unwrap();
			}

			$activityFormPlaceholder.hide();

			if ( $singleActivityFormWrap.length ) {
				$singleActivityFormWrap.hide();
			}

			if ( $tabActivityFormWrap.hasClass( 'is-bp-hide' ) ) {
				$tabActivityFormWrap.addClass( 'bp-hide' );
			}

			bp.Views.ActivityHeader.prototype.resetMultiMediaOptions();
		},

		displayEditDraftActivityData: function ( activity_data, bpActivityEvent, activity_URL_preview ) {
			var self = this;

			self.postForm.$el.parent( '#bb-rl-activity-form' ).removeClass( 'bp-hide' );
			self.postForm.$el.find( '#bb-rl-whats-new' ).html( activity_data.content );
			if ( activity_URL_preview != null ) {
				self.postForm.$el.find( '#bb-rl-whats-new' ).data( 'activity-url-preview', activity_URL_preview );
			}
			var element = self.postForm.$el.find( '#bb-rl-whats-new' ).get( 0 );
			element.focus();

			if ( 0 < parseInt( activity_data.id ) ) {

				if ( 'undefined' !== typeof window.getSelection && 'undefined' !== typeof document.createRange ) {
					var range = document.createRange();
					range.selectNodeContents( element );
					range.collapse( false );
					var selection = window.getSelection();
					selection.removeAllRanges();
					selection.addRange( range );
				}

				self.postForm.$el.find( '#bb-rl-activity-id' ).val( activity_data.id );
			} else {
				activity_data.gif          = activity_data.gif_data;
				activity_data.group_name   = activity_data.item_name;
				activity_data.group_avatar = activity_data.group_image;

				if ( 'group' === activity_data.object ) {
					activity_data.object = 'groups';
				}
			}

			// Set link image index and confirm image index.
			self.postForm.model.set(
				{
					link_image_index      : activity_data.link_image_index_save,
					link_image_index_save : activity_data.link_image_index_save
				}
			);

			if ( 'undefined' !== typeof bbRlIsActivitySchedule && bbRlIsActivitySchedule.strings.activity_schedule_enabled ) {
				if ( 'scheduled' === activity_data.activity_action_type || 'scheduled' === activity_data.status ) {

					// Set Schedule post data.
					self.postForm.model.set(
						{
							activity_schedule_date_raw: activity_data.activity_schedule_date_raw,
							activity_schedule_date    : activity_data.activity_schedule_date,
							activity_schedule_time    : activity_data.activity_schedule_time,
							activity_schedule_meridiem: activity_data.activity_schedule_meridiem
						}
					);

					if ( 'scheduled' === activity_data.status ) {
						self.postForm.model.set( 'activity_action_type', activity_data.status );
					} else {
						self.postForm.model.set( 'activity_action_type', activity_data.activity_action_type );
						// Check if time has passed and trigger warning.
						var activity_schedule_datetime = activity_data.activity_schedule_date_raw + ' ' + activity_data.activity_schedule_time + ' ' + activity_data.activity_schedule_meridiem,
							activity_schedule_date     = new Date( activity_schedule_datetime ),
							current_date               = new Date( bp.Nouveau.bbServerTime().currentServerTime );
						if ( current_date > activity_schedule_date ) {
							Backbone.trigger( 'onError', 'undefined' !== typeof bbRlIsActivitySchedule ? bbRlIsActivitySchedule.strings.scheduleWarning : '', 'warning' );
						}
					}
				} else if ( 'published' === activity_data.status ) {
					self.postForm.$el.addClass( 'hide-schedule-button' );
				}
			}

			// Display schedule button icon when privacy is not group for admin.
			var $whatsNewForm = $( '#bb-rl-whats-new-form' );
			if (
				'group' !== activity_data.privacy &&
				! _.isUndefined( bbRlIsActivitySchedule ) &&
				! _.isUndefined( bbRlIsActivitySchedule.params.can_schedule_in_feed ) &&
				true === bbRlIsActivitySchedule.params.can_schedule_in_feed
			) {
				$whatsNewForm.find( '.bb-schedule-post_dropdown_section' ).removeClass( 'bp-hide' );
			}

			// Display poll button icon when privacy is not group for admin.
			if (
				'group' !== activity_data.privacy &&
				! _.isUndefined( bbRlIsActivityPolls ) &&
				! _.isUndefined( bbRlIsActivityPolls.params.can_create_poll_activity ) &&
				true === bbRlIsActivityPolls.params.can_create_poll_activity
			) {
				$whatsNewForm.find( '.bb-post-poll-button' ).removeClass( 'bp-hide' );
			}

			// Show Hide Schedule post button according to group privacy.
			if ( 'group' === activity_data.privacy ) {
				// When change group from news feed.
				var schedule_allowed = $whatsNewForm.find( '#bb-rl-item-opt-' + activity_data.item_id ).data( 'allow-schedule-post' );
				if ( _.isUndefined( schedule_allowed ) ) {
					// When change group from news feed.
					if ( ! _.isUndefined( activity_data.schedule_allowed ) && 'enabled' === activity_data.schedule_allowed ) {
						schedule_allowed = activity_data.schedule_allowed;
						self.postForm.model.set( 'schedule_allowed', activity_data.schedule_allowed );
					} else if ( ! _.isUndefined( activity_data.schedule_allowed ) && 'disabled' === activity_data.schedule_allowed ) {
						schedule_allowed = 'disabled';
						self.postForm.model.set( 'schedule_allowed', activity_data.schedule_allowed );
					} else if (
						// On group page.
						! _.isUndefined( bbRlIsActivitySchedule ) &&
						! _.isUndefined( bbRlIsActivitySchedule.params.can_schedule_in_feed ) &&
						true === bbRlIsActivitySchedule.params.can_schedule_in_feed
					) {
						schedule_allowed = 'enabled';
					}
				}

				if ( ! _.isUndefined( schedule_allowed ) && 'enabled' === schedule_allowed ) {
					$whatsNewForm.find( '.bb-schedule-post_dropdown_section' ).removeClass( 'bp-hide' );
				} else {

					// If schedule post is not allowed, then reset schedule post data.
					self.postForm.model.set(
						{
							activity_action_type      : null,
							activity_schedule_date_raw: null,
							activity_schedule_date    : null,
							activity_schedule_time    : null,
							activity_schedule_meridiem: null,
							schedule_allowed          : 'disabled'
						}
					);
					$whatsNewForm.find( '.bb-schedule-post_dropdown_section' ).addClass( 'bp-hide' );
				}

				// Poll data.
				var polls_allowed = $whatsNewForm.find( '#bb-rl-item-opt-' + activity_data.item_id ).data( 'allow-polls' );
				if ( _.isUndefined( polls_allowed ) ) {
					// When change group from news feed.
					if ( ! _.isUndefined( activity_data.polls_allowed ) && 'enabled' === activity_data.polls_allowed ) {
						polls_allowed = activity_data.polls_allowed;
						self.postForm.model.set( 'polls_allowed', activity_data.polls_allowed );
					} else if ( ! _.isUndefined( activity_data.polls_allowed ) && 'disabled' === activity_data.polls_allowed ) {
						polls_allowed = 'disabled';
						self.postForm.model.set( 'polls_allowed', activity_data.polls_allowed );
					} else if (
						// On group page.
						! _.isUndefined( bbRlIsActivityPolls ) &&
						! _.isUndefined( bbRlIsActivityPolls.params.can_create_poll_activity ) &&
						true === bbRlIsActivityPolls.params.can_create_poll_activity
					) {
						polls_allowed = 'enabled';
					}
				}

				if ( ! _.isUndefined( polls_allowed ) && 'enabled' === polls_allowed ) {
					$whatsNewForm.find( '.bb-post-poll-button' ).removeClass( 'bp-hide' );
				} else {
					$whatsNewForm.find( '.bb-post-poll-button' ).addClass( 'bp-hide' );
				}
			}

			// Set poll data.
			if ( ! _.isUndefined( activity_data.poll ) && ! $.isEmptyObject( activity_data.poll ) ) {
				var pollObject = {
					id: activity_data.poll.id,
					user_id: parseInt( activity_data.poll.user_id ),
					item_id: ! _.isUndefined( activity_data.poll.item_id ) ? activity_data.poll.item_id : 0,
					vote_disabled_date: activity_data.poll.vote_disabled_date,
					question: activity_data.poll.question,
					options: activity_data.poll.options,
					allow_multiple_options: activity_data.poll.allow_multiple_options || false,
					allow_new_option: activity_data.poll.allow_new_option || false,
					duration: activity_data.poll.duration || 3,
					total_votes: activity_data.poll.total_votes,
					edit_poll: activity_data.edit_poll,
				};

				self.postForm.model.set(
					{
						poll    : pollObject,
						poll_id : activity_data.poll.id
					}
				);
			}

			var tool_box = $( '.bb-rl-activity-form.bb-rl-focus-in #bb-rl-whats-new-toolbar' );

			if ( ! _.isUndefined( self.activityToolbar ) ) {
				// Close and destroy existing gif,media,document,video instance.
				self.activityToolbar.closeSelectors( ['gif', 'media', 'document', 'video'] );
			}

			// Inject GIF.
			if ( ! _.isUndefined( activity_data.gif ) && Object.keys( activity_data.gif ).length ) {
				// close and destroy existing media instance.
				self.activityToolbar.toggleGifSelector( bpActivityEvent );
				self.activityToolbar.gifMediaSearchDropdownView.model.set( 'gif_data', activity_data.gif );

				// Make tool box button disable.
				self.bbMakeToolBoxButtonDisabled(
					{
						toolBox  : tool_box,
						btnId    : '#bb-rl-activity-gif-button',
						btnClass : 'active'
					}
				);
				// END Toolbox Button.
			}

			// Inject medias.
			if ( ! _.isUndefined( activity_data.media ) && activity_data.media.length ) {
				// open media uploader for editing media.
				if ( ! _.isUndefined( self.activityToolbar ) ) {
					self.activityToolbar.toggleMediaSelector( bpActivityEvent );
				}

				// Make tool box button disable.
				self.bbMakeToolBoxButtonDisabled(
					{
						toolBox  : tool_box,
						btnId    : '#bb-rl-activity-media-button',
						btnClass : 'active no-click'
					}
				);
				// END Toolbox Button.

				bp.Readylaunch.Utilities.injectFiles(
					{
						commonData  : activity_data.media,
						id          : activity_data.id,
						self        : self,
						fileType    : 'media',
						dropzoneObj : self.dropzone,
						draftData   : true,
					}
				);
			}

			// Inject Documents.
			if ( ! _.isUndefined( activity_data.document ) && activity_data.document.length ) {
				// open document uploader for editing document.

				if ( ! _.isUndefined( self.activityToolbar ) ) {
					self.activityToolbar.toggleDocumentSelector( bpActivityEvent );
				}

				// Make tool box button disable.
				self.bbMakeToolBoxButtonDisabled(
					{
						toolBox  : tool_box,
						btnId    : '#bb-rl-activity-document-button',
						btnClass : 'active no-click'
					}
				);

				// END Toolbox Button.
				bp.Readylaunch.Utilities.injectFiles(
					{
						commonData  : activity_data.document,
						id          : activity_data.id,
						self        : self,
						fileType    : 'document',
						dropzoneObj : self.dropzone,
						draftData   : true,
					}
				);
			}

			// Inject Videos.
			if ( ! _.isUndefined( activity_data.video ) && activity_data.video.length ) {

				if ( ! _.isUndefined( self.activityToolbar ) ) {
					self.activityToolbar.toggleVideoSelector( bpActivityEvent );
				}

				// Make tool box button disable.
				self.bbMakeToolBoxButtonDisabled(
					{
						toolBox  : tool_box,
						btnId    : '#bb-rl-activity-video-button',
						btnClass : 'active no-click'
					}
				);
				// END Toolbox Button.
				bp.Readylaunch.Utilities.injectFiles(
					{
						commonData  : activity_data.video,
						id          : activity_data.id,
						self        : self,
						fileType    : 'video',
						dropzoneObj : self.dropzone,
						draftData   : true,
					}
				);
			}

			self.postForm.$el.find( '#bb-rl-whats-new' ).trigger( 'keyup' );
			self.postForm.$el.removeClass( 'loading' );

			// Update privacy status label.
			var privacy_label = self.postForm.$el.find( '#' + activity_data.privacy ).data( 'title' );
			self.postForm.$el.find( '#bb-rl-activity-privacy-point' ).removeClass().addClass( activity_data.privacy );
			self.postForm.$el.find( '.bb-rl-activity-privacy-status' ).text( privacy_label );
			self.postForm.$el.find( '.bb-rl-activity-privacy__input#' + activity_data.privacy ).prop( 'checked', true );

			// Update privacy status.
			var bpListActivity     = $( '[data-bp-list="activity"] #bb-rl-activity-' + activity_data.id ),
				privacy            = bpListActivity.find( 'ul.activity-privacy li.selected' ).data( 'value' ),
				privacy_edit_label = bpListActivity.find( 'ul.activity-privacy li.selected' ).text();

			if ( ! _.isUndefined( privacy ) ) {
				self.postForm.$el.find( '#bb-rl-activity-privacy-point' ).removeClass().addClass( privacy );
				self.postForm.$el.find( '.bb-rl-activity-privacy-status' ).text( privacy_edit_label );
				self.postForm.$el.find( '.bb-rl-activity-privacy__input#' + privacy ).prop( 'checked', true );
			}

			if ( ! _.isUndefined( activity_data ) ) {
				var typeSupport = $( '#bb-rl-whats-new-toolbar' ),
					$postEmoji  = $( '#bb-rl-editor-toolbar .bb-rl-post-emoji' ),
					context     = (
						! _.isUndefined( activity_data.object ) && 'groups' === activity_data.object
					) ? 'group' : 'profile',
					types       = ['media', 'document', 'video'];

				types.forEach(
					function ( type ) {
						var subtype        = 'document' === type ? 'media' : type,
						activityKeyGroup   = 'group_' + type,
						activityKeyProfile = 'profile_' + type;
						if ( 'groups' === context ) {
							if ( ! _.isUndefined( activity_data[ activityKeyGroup ] ) && false === activity_data[ activityKeyGroup ] ) {
								typeSupport.find( '.bb-rl-post-' + subtype + '.bb-rl-' + type + '-support' ).removeClass( 'active' ).addClass( 'bb-rl-' + type + '-support-hide' );
								$( '.bb-rl-edit-activity-content-wrap #bb-rl-whats-new-attachments .bb-rl-activity-' + type + '-container #bb-rl-activity-post-' + type + '-uploader .dz-default.dz-message' ).hide();
							} else {
								typeSupport.removeClass( 'bb-rl-' + type + '-support-hide' );
							}
						} else {
							var activityContainerElem = $( '.bb-rl-activity-' + type + '-container' );
							if ( ! _.isUndefined( activity_data[ activityKeyProfile ] ) && false === activity_data[ activityKeyProfile ] ) {
								typeSupport.find( '.bb-rl-post-' + subtype + '.bb-rl-' + type + '-support' ).removeClass( 'active' ).addClass( 'bb-rl-' + type + '-support-hide' );
								activityContainerElem.find( '#bb-rl-activity-post-' + type + '-uploader .dz-default.dz-message' ).hide();
								activityContainerElem.css( 'pointer-events', 'none' );
							} else {
								activityContainerElem.css( 'pointer-events', 'auto' );
								typeSupport.removeClass( 'bb-rl-' + type + '-support-hide' );
							}
						}
					}
				);

				var emojiElement = $( '#bb-rl-whats-new-textarea' ).find( 'img.bb-rl-emojioneemoji' ),
					contextKey   = context === 'groups' ? 'groups' : 'profile';
				if ( 'groups' === context ) {
					bp.Nouveau.Activity.postForm.postGifGroup = new bp.Views.PostGifGroup( { model : this.model } );
				} else {
					bp.Nouveau.Activity.postForm.postGifProfile = new bp.Views.PostGifProfile( { model : this.model } );
				}

				// Check if emoji is enabled for the current context.
				if ( ! _.isUndefined( bbRlMedia ) && ! _.isUndefined( bbRlMedia.emoji ) && ! _.isUndefined( bbRlMedia.emoji[ contextKey ] ) && false === bbRlMedia.emoji[ contextKey ] ) {
					emojiElement.remove();
					$postEmoji.addClass( 'bb-rl-post-emoji-hide' );
				} else {
					$postEmoji.removeClass( 'bb-rl-post-emoji-hide' );
				}
			}

			// set object of activity and item id when group activity.
			if ( ! _.isUndefined( activity_data.object ) && ! _.isUndefined( activity_data.item_id ) && 'groups' === activity_data.object ) {
				self.postForm.model.set(
					{
						item_id    : activity_data.item_id,
						object     : 'group',
						group_name : activity_data.group_name,
					}
				);

				self.postForm.$el.find( 'input#group' ).prop( 'checked', true );
				var $privacyPointElem = self.postForm.$el.find( '#bb-rl-activity-privacy-point' );
				if ( 0 < parseInt( activity_data.id ) ) {
					$privacyPointElem.removeClass().addClass( 'group bb-rl-activity-edit-group' );
				} else {
					if ( ! _.isUndefined( bp.draft_activity ) && '' !== bp.draft_activity.object && 'group' === bp.draft_activity.object && bp.draft_activity.data && '' !== bp.draft_activity.data ) {
						$privacyPointElem.removeClass().addClass( 'group bb-rl-activity-edit-group' );
					} else {
						$privacyPointElem.removeClass().addClass( 'group' );
					}
				}

				$privacyPointElem.find( 'i.bb-icon-angle-down' ).remove();
				self.postForm.$el.find( '.bb-rl-activity-privacy-status' ).text( activity_data.group_name );
				// display group avatar when edit any feed.
				if ( activity_data.group_avatar && false === activity_data.group_avatar.includes( 'mystery-group' ) ) {
					$privacyPointElem.find( 'span.bb-rl-privacy-point-icon' ).removeClass( 'bb-rl-privacy-point-icon' ).addClass( 'group-bb-rl-privacy-point-icon' ).html( '<img src="' + activity_data.group_avatar + '" alt=""/>' );
				}
			}

			// Do not allow the edit privacy if activity is belongs to any folder/album.
			if ( ! bp.privacyEditable && 'groups' !== activity_data.object ) {
				self.postForm.$el.addClass( 'bb-rl-activity-edit--privacy-idle' );
			} else {
				self.postForm.$el.removeClass( 'bb-rl-activity-edit--privacy-idle' );
			}

			if (
				! _.isUndefined( BP_Nouveau.activity.params.topics ) &&
				! _.isUndefined( BP_Nouveau.activity.params.topics.bb_is_enabled_activity_topics ) &&
				BP_Nouveau.activity.params.topics.bb_is_enabled_activity_topics &&
				! _.isUndefined( activity_data.topics )
			) {
				activity_data.topics.topic_id   = activity_data.topics.topic_id || 0;
				activity_data.topics.topic_name = activity_data.topics.topic_name || '';
				if (
					activity_data.item_id &&
					'groups' === activity_data.object
				) {
					activity_data.topics.topic_lists = activity_data.topics.topic_lists;
				} else {
					activity_data.topics.topic_lists = BP_Nouveau.activity.params.topics.topic_lists;
				}
			}

			if ( 0 < parseInt( activity_data.id ) ) {
				Backbone.trigger( 'editactivity' );
			} else {
				self.postForm.$el.removeClass( 'bb-rl-focus-in--empty loading' );
			}

			if (
				! _.isUndefined( BP_Nouveau.activity.params.topics ) &&
				BP_Nouveau.activity.params.topics.topic_lists.length > 0 &&
				! _.isUndefined( BP_Nouveau.activity.params.topics.bb_is_enabled_activity_topics ) &&
				BP_Nouveau.activity.params.topics.bb_is_enabled_activity_topics &&
				! _.isUndefined( BP_Nouveau.activity.params.topics.bb_is_activity_topic_required ) &&
				BP_Nouveau.activity.params.topics.bb_is_activity_topic_required
			) {
				BBTopicsManager.bbTopicValidateContent( {
					self         : self,
					selector     : self.postForm.$el,
					validContent : bp.Nouveau.Activity.postForm.validateContent(),
					class        : 'bb-rl-focus-in--empty',
					data         : activity_data,
					action       : 'draft_activity_loaded'
				} );
			}

			if ( activity_data && activity_data.topics ) {
				if ( '' === bp.draft_activity.display_post ) {
					if ( 'scheduled' !== activity_data.status ) {
						self.postForm.model.set( 'topics', activity_data.topics );
						bp.draft_activity.data.topics = activity_data.topics;
					}
				}
				if ( 0 !== parseInt( activity_data.topics.topic_id ) ) {
					var $topicElement = $( '.bb-rl-topic-selector-list a[data-topic-id="' + activity_data.topics.topic_id + '"]' );
					if ( $topicElement.length > 0 ) {
						$topicElement.addClass( 'selected' );
						var topicName = activity_data.topics.topic_name;
						if ( ! topicName ) {
							topicName = $topicElement.text();
						}
						$( '.bb-rl-topic-selector-button' ).text( topicName );
					}
				} else {
					Backbone.trigger( 'topic:update', activity_data.topics );
				}
			}
		},

		getCurrentDraftActivity: function () {
			if ( $( 'body' ).hasClass( 'activity' ) && ! _.isUndefined( bbRlActivity.params.object ) ) {
				bp.draft_activity.object = bbRlActivity.params.object;

				// Draft activity data.
				var activityDraftKey = 'draft_' + bbRlActivity.params.object;
				if ( 'group' === bbRlActivity.params.object ) {
					activityDraftKey = 'draft_' + bbRlActivity.params.object + '_' + bbRlActivity.params.item_id;
				} else if ( 0 < bbRlActivity.params.displayed_user_id ) {
					activityDraftKey = 'draft_' + bbRlActivity.params.object + '_' + bbRlActivity.params.displayed_user_id;
				}

				bp.draft_activity.data_key = activityDraftKey; // Save back to the object.
				var draft_data             = localStorage.getItem( activityDraftKey );
				if ( ! _.isUndefined( draft_data ) && null !== draft_data && 0 < draft_data.length ) {
					if ( 'deleted' !== $.cookie( activityDraftKey ) ) {
						// Parse data with JSON.
						var draft_activity_local_data = JSON.parse( draft_data );
						bp.draft_activity.data        = draft_activity_local_data.data;
					} else {
						$.removeCookie( activityDraftKey );
					}
				}
			}

			return bp.draft_activity;
		},

		isProfileDraftActivity: function ( activity_data ) {
			return ! (
				! _.isUndefined( activity_data ) && ! _.isUndefined( activity_data.object ) && ! _.isUndefined( activity_data.item_id ) && 'groups' === activity_data.object
			);
		},

		displayDraftActivity: function () {
			var activity_data = bp.draft_activity.data,
				$this         = this;

			bp.draft_activity.allow_delete_media = true;

			var $whatsNewForm = $( '#bb-rl-whats-new-form' );
			// Checked the draft is available or doesn't edit activity.
			if ( ! activity_data || $whatsNewForm.hasClass( 'bb-rl-activity-edit' ) ) {
				return;
			}

			var is_profile_activity = this.isProfileDraftActivity( activity_data ),
				types               = ['media', 'document', 'video'],
				typesLength         = types.length;
			for ( var i = 0; i < typesLength; i++ ) {
				if ( !_.isUndefined( BP_Nouveau[ types[ i ] ] ) ) {
					// Sync profile/group media/document/video.
					$this.syncMediaDocVideo( activity_data, types[ i ], is_profile_activity );
				}
			}

			setTimeout(
				function () {

					if ( $( 'body' ).hasClass( 'bb-rl-activity-modal-open' ) ) {

						// Add loader.
						$this.postForm.$el.addClass( 'loading' ).addClass( 'has-draft' );

						var bpActivityEvent = new Event( 'bp_activity_edit' );

						bp.Nouveau.Activity.postForm.displayEditDraftActivityData( activity_data, bpActivityEvent, activity_data.link_url );
					}

				},
				0
			);
		},

		syncDraftActivity: function () {
			if ( ( ! bp.draft_activity.data || '' === bp.draft_activity.data ) && ! _.isUndefined( bbRlActivity.params.draft_activity.data_key ) ) {

				const draftKey = bp.draft_activity.data_key;
				if ( 'deleted' === $.cookie( bp.draft_activity.data_key ) ) {
					bp.draft_activity.data             = false;
					bbRlActivity.params.draft_activity = '';
					localStorage.removeItem( draftKey );
					$.removeCookie( draftKey );
				} else {
					bp.old_draft_data = bbRlActivity.params.draft_activity.data;
					bp.draft_activity = bbRlActivity.params.draft_activity;
					localStorage.setItem( draftKey, JSON.stringify( bp.draft_activity ) );
				}

			}
		},

		collectDraftActivity: function () {
			var self = this,
				meta = {};

			if ( _.isUndefined( this.postForm ) || this.postForm.$el.hasClass( 'bb-rl-activity-edit' ) ) {
				return;
			}

			// Set the content and meta.
			_.each(
				self.postForm.$el.serializeArray(),
				function ( pair ) {
					pair.name = pair.name.replace( '[]', '' );
					if ( pair.name.startsWith( 'bb-poll-question-option[' ) ) {
						pair.name = pair.name.replace( /\[\d+\]/, '' );
					}
					if ( - 1 === _.indexOf( ['aw-whats-new-submit', 'whats-new-post-in', 'bb-schedule-activity-date-field', 'bb-schedule-activity-meridian', 'bb-schedule-activity-time-field', 'bb-poll-question-field', 'bb-poll-duration', 'bb-poll-question-option', 'bb-poll-allow-multiple-answer', 'bb-poll-allow-new-option'], pair.name ) ) {
						if ( _.isUndefined( meta[ pair.name ] ) ) {
							meta[ pair.name ] = pair.value;
						} else {
							if ( ! _.isArray( meta[ pair.name ] ) ) {
								meta[ pair.name ] = [meta[ pair.name ]];
							}

							meta[ pair.name ].push( pair.value );
						}
					}
				}
			);

			// Add valid line breaks.
			var content = $.trim( self.postForm.$el.find( '#bb-rl-whats-new' )[ 0 ].innerHTML.replace( /<div>/gi, '\n' ).replace( /<\/div>/gi, '' ) );
			content     = content.replace( /&nbsp;/g, ' ' );

			self.postForm.model.set( 'content', content, {silent: true} );

			// Silently add meta.
			self.postForm.model.set( meta, {silent: true} );

			// Process attachments (media, document, video).
			var isUndefinedOrEmpty = function ( value ) {
				return _.isUndefined( value ) || ! value.length;
			};
			var processAttachments = function ( type ) {
				var attachments = self.postForm.model.get( type );
				if ( ! isUndefinedOrEmpty( attachments ) ) {
					_.each(
						attachments,
						function ( attachment ) {
							if ( 'group' === self.postForm.model.get( 'object' ) ) {
								attachment.group_id = self.postForm.model.get( 'item_id' );
							} else {
								delete attachment.group_id;
							}
						}
					);
					self.postForm.model.set( type, attachments );
				}
			};
			['media', 'document', 'video'].forEach(
				function ( type ) {
					processAttachments( type );
				}
			);

			var filteredContent = $( $.parseHTML( content ) ).text().trim();
			if ( content.includes( 'data-emoji-char' ) && '' === filteredContent ) {
				filteredContent = content;
			}

			// validation for content editor.
			if ( '' === filteredContent &&
				_.every( [self.postForm.model.get( 'media' ), self.postForm.model.get( 'document' ), self.postForm.model.get( 'video' ), self.postForm.model.get( 'gif_data' )], isUndefinedOrEmpty ) &&
				(
					(
						! _.isUndefined( self.postForm.model.get( 'poll' ) ) &&
						! $.isEmptyObject( self.postForm.model.get( 'poll' ) ) &&
						! Object.keys( self.postForm.model.get( 'poll' ) ).length
					) ||
					_.isUndefined( self.postForm.model.get( 'poll' ) )
				) &&
				(
					(
						! _.isUndefined( self.postForm.model.get( 'topics' ) ) &&
						! $.isEmptyObject( self.postForm.model.get( 'topics' ) ) &&
						! Object.keys( self.postForm.model.get( 'topics' ) ).length &&
						! self.postForm.model.get( 'topics' ).topic_id
					) ||
					(
						_.isUndefined( self.postForm.model.get( 'topics' ) ) ||
						1 > parseInt( self.postForm.model.get( 'topics' ).topic_id )
					)
				)
			) {
				if ( bp.draft_content_changed ) {
					localStorage.removeItem( bp.draft_activity.data_key );
					bp.Nouveau.Activity.postForm.resetDraftActivity( true );
				} else {
					bp.draft_activity.data = false;
					localStorage.removeItem( bp.draft_activity.data_key );
				}

				return false;
			}

			var data = {};

			// Remove all unused model attribute.
			data = _.omit(
				_.extend( data, self.postForm.model.attributes ),
				[
					'link_images',
					'link_image_index',
					'link_success',
					'link_error',
					'link_error_msg',
					'link_scrapping',
					'link_loading',
					'posting',
				]
			);

			if ( 0 < bp.draft_activity.data.item_id && 'group' === data.privacy && ( 0 === parseInt( data.item_id ) || parseInt( bp.draft_activity.data.item_id ) === parseInt( data.item_id ) ) ) {
				var itemID = bp.draft_activity.data.item_id;
				_.assign(
					data,
					{
						item_id        : parseInt( itemID ),
						item_name      : bp.draft_activity.data.item_name,
						group_image    : bp.draft_activity.data.group_image,
						'group-privacy': 'bb-rl-item-opt-' + itemID
					}
				);
				self.postForm.model.set( data );
			}

			// Form link preview data to pass in request if available.
			if ( self.postForm.model.get( 'link_success' ) ) {
				var images = self.postForm.model.get( 'link_images' ),
					index  = self.postForm.model.get( 'link_image_index' );
				if ( images && images.length ) {
					data = _.extend(
						data,
						{
							'link_image': images[ index ],
						}
					);
				}

			} else {
				data = _.omit(
					data,
					[
						'link_title',
						'link_description',
						'link_url',
					]
				);
			}

			// Set Draft activity data.
			self.checkedActivityDataChanged( bp.old_draft_data, data );

			bp.draft_activity.data = data;
			localStorage.setItem( bp.draft_activity.data_key, JSON.stringify( bp.draft_activity ) );
		},

		checkedActivityDataChanged: function ( old_data, new_data ) {

			if ( bp.draft_content_changed ) {
				return;
			}

			var draft_data_keys = [
				'object',
				'user_id',
				'content',
				'item_id',
				'item_name',
				'group_image',
				'media',
				'document',
				'video',
				'gif_data',
				'privacy',
				'privacy_modal',
				'link_embed',
				'link_description',
				'link_image',
				'link_title',
				'link_url',
				'activity_action_type',
				'activity_schedule_date_raw',
				'activity_schedule_date',
				'activity_schedule_time',
				'activity_schedule_meridiem',
				'schedule_allowed',
				'poll',
				'poll_id',
				'polls_allowed',
				'topics',
			];

			_.each(
				draft_data_keys,
				function ( pair ) {

					if ( ! _.isUndefined( old_data[ pair ] ) && _.isUndefined( new_data[ pair ] ) ) {
						bp.draft_content_changed = true;
					} else if ( _.isUndefined( old_data[ pair ] ) && ! _.isUndefined( new_data[ pair ] ) ) {
						bp.draft_content_changed = true;
					}

					if ( - 1 === _.indexOf(
						[
							'media',
							'document',
							'video',
							'gif_data',
						],
						pair
					) && ! _.isUndefined( old_data[ pair ] ) && ! _.isUndefined( new_data[ pair ] ) ) {

						if ( 'object' === pair ) {

							bp.draft_content_changed = true;
							if ( -1 !== _.indexOf( [ 'groups', 'group' ], new_data[ pair ] ) && -1 !== _.indexOf( [ 'groups', 'group' ], old_data[ pair ] ) ) {
								bp.draft_content_changed = false;
							} else if ( -1 !== _.indexOf( [ 'user' ], new_data[ pair ] ) && -1 !== _.indexOf( [ 'user' ], old_data[ pair ] ) ) {
								bp.draft_content_changed = false;
							}

						} else if ( 'user_id' === pair || 'item_id' === pair ) {

							if ( parseInt( old_data[ pair ] ) !== parseInt( new_data[ pair ] ) ) {
								bp.draft_content_changed = true;
							}

						} else if ( 'link_embed' === pair ) {

							if ( JSON.parse( old_data[ pair ] ) !== JSON.parse( new_data[ pair ] ) ) {
								bp.draft_content_changed = true;
							}

						} else if ( old_data[ pair ] !== new_data[ pair ] ) {
							bp.draft_content_changed = true;
						}

					}
				}
			);
		},

		storeDraftActivity: function () {
			var self = this;

			if ( ! $( 'body' ).hasClass( 'bb-rl-activity-modal-open' ) || self.postForm.$el.hasClass( 'bb-rl-activity-edit' ) ) {
				return;
			}

			bp.Nouveau.Activity.postForm.collectDraftActivity();
		},

		postDraftActivity: function ( is_force_saved, is_reload_window ) {

			if ( _.isUndefined( this.postForm ) || this.postForm.$el.hasClass( 'bb-rl-activity-edit' ) ) {
				return;
			}

			if ( ! is_force_saved && ( _.isUndefined( bp.draft_activity ) || ( ! _.isUndefined( bp.draft_activity ) && ( ! bp.draft_activity.data || '' === bp.draft_activity.data ) ) ) ) {
				return;
			}

			// Checked the content changed or not.
			if ( ! is_force_saved && ! bp.draft_content_changed ) {
				return;
			}

			if ( ! is_reload_window ) {
				if ( bp.draft_ajax_request ) {
					bp.draft_ajax_request.abort();
				}

				var draft_data = {
					_wpnonce_post_draft: bbRlActivity.params.post_draft_nonce,
					draft_activity: bp.draft_activity
				};

				// Some firewalls restrict iframe tag in form post like wordfence.
				if (
					! _.isUndefined( draft_data.draft_activity ) &&
					! _.isUndefined( draft_data.draft_activity.data ) &&
					! _.isUndefined( draft_data.draft_activity.data.link_description ) &&
					! _.isUndefined( draft_data.draft_activity.data.link_embed ) &&
					true === draft_data.draft_activity.data.link_embed
				) {
					draft_data.draft_activity.data.link_description = '';
				}

				// Send data to server.
				bp.draft_ajax_request = bp.ajax.post( 'post_draft_activity', draft_data ).done(
					function () {}
				).fail(
					function () {}
				);

			} else {
				const formData = new FormData();
				formData.append( '_wpnonce_post_draft', bbRlActivity.params.post_draft_nonce );
				formData.append( 'action', 'post_draft_activity' );
				formData.append( 'draft_activity', JSON.stringify( bp.draft_activity ) );

				navigator.sendBeacon( bbRlAjaxUrl, formData );
			}

			bp.old_draft_data        = bp.draft_activity.data;
			bp.draft_content_changed = false;
		},

		resetDraftActivity: function ( is_send_server ) {
			var self = this;

			// Delete the activity from the database.
			$.cookie( bp.draft_activity.data_key, 'deleted' );
			bp.draft_activity.post_action = 'delete';
			if ( is_send_server ) {
				bp.Nouveau.Activity.postForm.postDraftActivity( true, true );
			}
			bp.draft_activity.data = false;
			localStorage.removeItem( bp.draft_activity.data_key );
			self.postForm.$el.removeClass( 'has-draft' );
			bp.draft_activity.post_action        = 'update';
			bp.draft_activity.allow_delete_media = false;
			bp.draft_activity.display_post       = '';

			// Check if user can schedule in feed after discard draft.
			var $whatsNewForm = $( '#bb-rl-whats-new-form' );
			if (
				! _.isUndefined( bbRlIsActivitySchedule ) &&
				! _.isUndefined( bbRlIsActivitySchedule.params.can_schedule_in_feed ) &&
				true === bbRlIsActivitySchedule.params.can_schedule_in_feed
			) {
				$whatsNewForm.find( '.bb-schedule-post_dropdown_section' ).removeClass( 'bp-hide' );
			}

			// Check if user can create poll in feed after discard draft.
			if (
				! _.isUndefined( bbRlIsActivityPolls ) &&
				! _.isUndefined( bbRlIsActivityPolls.params.can_create_poll_activity ) &&
				true === bbRlIsActivityPolls.params.can_create_poll_activity
			) {
				$whatsNewForm.find( '.bb-post-poll-button' ).removeClass( 'bp-hide' );
			}
		},

		reloadWindow: function () {

			const postDraftActivityHandler = function ( event ) {
				if ( 'undefined' !== typeof event ) {
					bp.Nouveau.Activity.postForm.collectDraftActivity();
					bp.Nouveau.Activity.postForm.postDraftActivity( false, true );
				}
			};

			// This will work only for Chrome.
			window.onbeforeunload = postDraftActivityHandler;

			// This will work only for other browsers.
			window.unload = postDraftActivityHandler;
		},

		syncMediaDocVideo : function ( activity_data, type, is_profile_activity ) {
			var $whatsNewToolbarElem = $( '#whats-new-toolbar' ),
				profileKey           = 'profile_' + type,
				groupKey             = 'group_' + type,
				subToolbarSelector   = '.post-' + type + '.' + type + '-support';

			activity_data[ profileKey ] = BP_Nouveau[ type ][ profileKey ];
			activity_data[ groupKey ]   = BP_Nouveau[ type ][ groupKey ];
			if ( 'document' === type ) {
				// Change type for class name and localize var for a document.
				var changeType              = 'media';
				subToolbarSelector          = '.post-' + changeType + ' .' + type + '-support';
				activity_data[ profileKey ] = BP_Nouveau[ changeType ][ profileKey ];
				activity_data[ groupKey ]   = BP_Nouveau[ changeType ][ groupKey ];
			}
			var toolbarSelector = $whatsNewToolbarElem.find( subToolbarSelector );

			// Delete it from activity data if not supported.
			if ( activity_data[ profileKey ] === false && is_profile_activity ) {
				delete activity_data[ type ];
			} else if ( activity_data[ groupKey ] === false && ! is_profile_activity ) {
				delete activity_data[ type ];
			}

			// Update toolbar UI based on settings.
			if ( BP_Nouveau[ type ][ profileKey ] === false ) {
				$( toolbarSelector ).removeClass( 'active' ).addClass( type + '-support-hide' );
				Backbone.trigger( 'activity_' + type + '_close' );
			} else {
				$( toolbarSelector ).removeClass( type + '-support-hide' );
			}
		},

		bbMakeToolBoxButtonDisabled : function ( args ) {
			var uploaderButtons = [
				'#bb-rl-activity-media-button',
				'#bb-rl-activity-video-button',
				'#bb-rl-activity-document-button',
				'#bb-rl-activity-gif-button'
			];
			uploaderButtons.forEach(
				function ( buttonClass ) {
					bp.Nouveau.Activity.disabledCommentUploader( args.toolBox, buttonClass, buttonClass === args.btnId ? args.btnClass : '' );
				}
			);
		},

		clearDraftInterval: function () {
			clearInterval( bp.draft_local_interval );
			bp.draft_local_interval = false;
			clearInterval( bp.draft_ajax_interval );
			bp.draft_ajax_interval = false;
		},

		validateContent: function() {
			var $whatsNew = $( '#bb-rl-whats-new-form' ).find( '#bb-rl-whats-new' );
			var content = $.trim( $whatsNew[0].innerHTML.replace( /<div>/gi, '\n' ).replace( /<\/div>/gi, '' ) );
			content     = content.replace( /&nbsp;/g, ' ' );

			if ( content.replace( /<p>/gi, '' ).replace( /<\/p>/gi, '' ).replace( /<br>/gi, '' ) === '' ) {
				$whatsNew[0].innerHTML = '';
			}
			
			if ( $( $.parseHTML( content ) ).text().trim() !== '' || content.includes( 'class="emoji"' ) || ( ! _.isUndefined( this.postForm.model.get( 'link_success' ) ) && true === this.postForm.model.get( 'link_success' ) ) || ( ! _.isUndefined( this.postForm.model.get( 'video' ) ) && 0 !== this.postForm.model.get('video').length ) || ( ! _.isUndefined( this.postForm.model.get( 'document' ) ) && 0 !== this.postForm.model.get('document').length ) || ( ! _.isUndefined( this.postForm.model.get( 'media' ) ) && 0 !== this.postForm.model.get('media').length ) || ( ! _.isUndefined( this.postForm.model.get( 'gif_data' ) ) && ! _.isEmpty( this.postForm.model.get( 'gif_data' ) ) ) || ( ! _.isUndefined( this.postForm.model.get( 'poll' ) ) && ! _.isEmpty( this.postForm.model.get( 'poll' ) ) ) ) {
				return true;
			}
			
			return false;
		},

	};

	bp.Backbone.View.prototype.close = function () {
		this.remove();
		this.unbind();
		if ( this.onClose ) {
			this.onClose();
		}
	};

	if ( _.isUndefined( bp.View ) ) {
		// Extend wp.Backbone.View with .prepare() and .inject().
		bp.View = bp.Backbone.View.extend(
			{
				inject: function ( selector ) {
					this.render();
					$( selector ).html( this.el );
					this.views.ready();
				},

				prepare: function () {
					if ( ! _.isUndefined( this.model ) && _.isFunction( this.model.toJSON ) ) {
						return this.model.toJSON();
					} else {
						return {};
					}
				}
			}
		);
	}

	/** Models ****************************************************************/

	// The Activity to post.
	bp.Models.Activity = Backbone.Model.extend(
		{
			defaults: {
				id: 0,
				user_id: 0,
				item_id: 0,
				item_name: '',
				object: '',
				content: '',
				posting: false,
				link_success: false,
				link_error: false,
				link_error_msg: '',
				link_scrapping: false,
				link_images: [],
				link_image_index: 0,
				link_title: '',
				link_description: '',
				link_url: '',
				gif_data: {},
				privacy: 'public',
				privacy_modal: 'general',
				edit_activity: false,
				group_image: '',
				link_image_index_save: '0',
			}
		}
	);

	bp.Models.GifResults = Backbone.Model.extend(
		{
			defaults: {
				q: '',
				data: []
			}
		}
	);

	bp.Models.GifData = Backbone.Model.extend( {} );

	// Git results collection returned from giphy api.
	bp.Collections.GifDatas = Backbone.Collection.extend(
		{
			// Reference to this collection's model.
			model: bp.Models.GifData
		}
	);

	// Object, the activity is attached to (group or blog or any other).
	bp.Models.ActivityObject = Backbone.Model.extend(
		{
			defaults: {
				id: 0,
				name: '',
				avatar_url: '',
				object_type: 'group'
			}
		}
	);

	// Model object, to fetch ajax data for activity group when load more.
	bp.Models.fetchData = Backbone.Model.extend( {} );

	/** Collections ***********************************************************/

	// Objects, the activity can be attached to (groups or blogs or any others).
	bp.Collections.ActivityObjects = Backbone.Collection.extend(
		{
			model: bp.Models.ActivityObject,

			sync: function ( method, model, options ) {

				if ( 'read' === method ) {
					options         = options || {};
					options.context = this;
					options.data    = _.extend(
						options.data || {},
						{
							action: 'bp_nouveau_get_activity_objects'
						}
					);

					return bp.ajax.send( options );
				}
			},

			parse: function ( resp ) {
				if ( ! _.isArray( resp ) ) {
					resp = [ resp ];
				}

				return resp;
			}

		}
	);

	// Pass ajax url if we use any model to fetch data via load more.
	bp.Collections.fetchCollection = Backbone.Collection.extend(
		{
			model: bp.Models.fetchData,
			url: bbRlAjaxUrl
		}
	);

	/** Views *****************************************************************/

	// Header.
	bp.Views.ActivityHeader = bp.View.extend(
		{
			tagName: 'header',
			id: 'bb-rl-activity-header',
			template: bp.template( 'activity-header' ),
			className: 'bb-rl-bb-model-header',

			events: {
				'click .bb-rl-model-close-button': 'close'
			},

			initialize: function () {
				this.listenTo( Backbone, 'editactivity', this.updateEditActivityHeader );
				this.model.on( 'change:edit_activity', this.render, this );
			},

			render: function () {
				this.$el.html( this.template( this.model.toJSON() ) );

				if ( bp.Views.activitySchedulePost !== undefined ) {
					// Check if template exists before adding the view.
					if ( document.getElementById( 'tmpl-activity-schedule-post' ) ) {
						this.views.add( new bp.Views.activitySchedulePost( { model: this.model } ) );
						$( '.bb-rl-activity-form' ).addClass( 'bb-rl-activity-form--schedule' );

						// Display schedule button icon when privacy is not group for admin.
						var $whatsNewForm = $( '#bb-rl-whats-new-form' );
						if (
							'group' !== this.model.get( 'privacy' ) &&
							! _.isUndefined( bbRlIsActivitySchedule ) &&
							! _.isUndefined( bbRlIsActivitySchedule.params.can_schedule_in_feed ) &&
							true === bbRlIsActivitySchedule.params.can_schedule_in_feed
						) {
							$whatsNewForm.find( '.bb-schedule-post_dropdown_section' ).removeClass( 'bp-hide' );
						}
					}
				}

				return this;
			},

			updateEditActivityHeader: function () {
				this.model.set( 'edit_activity', true );
			},

			close: function ( e ) {

				// Store data forcefully.
				if ( ! this.$el.parent().hasClass( 'bb-rl-activity-edit' ) ) {
					bp.Nouveau.Activity.postForm.clearDraftInterval();
					bp.Nouveau.Activity.postForm.collectDraftActivity();
					bp.Nouveau.Activity.postForm.postDraftActivity( false, false );
				}

				// Reset Global variable after edit activity.
				bp.privacyEditable = true;
				bp.album_id        = 0;
				bp.folder_id       = 0;
				bp.group_id        = 0;
				bp.privacy         = 'public';

				e.preventDefault();

				$( 'body' ).removeClass( 'bb-rl-initial-post-form-open' );
				this.$el.parent().find( '#bb-rl-aw-whats-new-reset' ).trigger( 'click' ); // Trigger reset.
				this.model.set( 'privacy_modal', 'general' );

				// Loose post form textarea focus for Safari.
				if ( navigator.userAgent.includes( 'Safari' ) && ! navigator.userAgent.includes( 'Chrome' ) ) {
					$( 'input' ).focus().blur();
				}

				var $formElem = this.$el.closest( '#bb-rl-whats-new-form' );
				$formElem.removeClass( 'bb-rl-focus-in--blank-group' ); // Reset privacy status submit button.
				$formElem.removeClass( 'bb-rl-activity-edit--privacy-idle' ); // Update privacy editable state class.

				// Post activity hide modal.
				var $singleActivityFormWrap = $( '#bb-rl-single-activity-edit-form-wrap' );
				$singleActivityFormWrap.hide();

				var $tabActivityFormWrap = $( '#bb-rl-activity-form' );
				if ( $tabActivityFormWrap.hasClass( 'is-bp-hide' ) ) {
					$tabActivityFormWrap.addClass( 'bp-hide' );
				}

				this.resetMultiMediaOptions();
			},

			resetMultiMediaOptions: function () {

				if ( null !== window.activityMediaAction ) {
					$( '.bb-rl-activity-update-form.modal-popup' ).find( '#' + window.activityMediaAction ).trigger( 'click' );
					window.activityMediaAction = null;
				}

				$( '#bb-rl-whats-new-form' ).removeClass( 'focus-in--attm' );
			}
		}
	);

	// Feedback messages.
	bp.Views.activityFeedback = bp.View.extend(
		{
			tagName: 'div',
			id: 'message-feedback',
			template: bp.template( 'activity-post-form-feedback' ),
			events: {
				'click .bb-rl-notice__close': 'removeNotice'
			},

			removeNotice: function () {
				Backbone.trigger( 'cleanFeedBack' );
			},

			initialize: function () {
				this.model = new Backbone.Model();

				if ( this.options.value ) {
					this.model.set( 'message', this.options.value, { silent: true } );
				}

				this.type = 'info';

				if ( ! _.isUndefined( this.options.type ) && 'info' !== this.options.type ) {
					this.type = this.options.type;
				}

				this.el.className = 'bb-rl-notice bb-rl-notice--' + this.type;
			}
		}
	);

	// Activity Media View.
	bp.Views.ActivityMedia = bp.View.extend(
		{
			tagName: 'div',
			className: 'bb-rl-activity-media-container',
			template: bp.template( 'activity-media' ),
			media: [],

			initialize: function () {
				this.model.set( 'media', this.media );
				this.listenTo( Backbone, 'activity_media_toggle', this.toggle_media_uploader );
				this.listenTo( Backbone, 'activity_media_close', this.destroy );
			},

			toggle_media_uploader: function () {
				var self = this;
				if (self.$el.find( '#bb-rl-activity-post-media-uploader' ).hasClass( 'open' )) {
					self.destroy();
				} else {
					self.open_media_uploader();
				}
			},

			destroy: function () {
				var self               = this;
				var $mediaUploaderElem = self.$el.find( '#bb-rl-activity-post-media-uploader' );
				if ( ! _.isNull( bp.Nouveau.Activity.postForm.dropzone )) {
					bp.Nouveau.Activity.postForm.dropzone.destroy();
					$mediaUploaderElem.html( '' );
				}
				self.media = [];
				$mediaUploaderElem.removeClass( 'open' ).addClass( 'closed' );
				$( '#bb-rl-whats-new-attachments' ).addClass( 'empty' ).closest( '#bb-rl-whats-new-form' ).removeClass( 'focus-in--attm' );
			},

			open_media_uploader: function () {
				var self = this;
				if ( self.$el.find( '#bb-rl-activity-post-media-uploader' ).hasClass( 'open' ) ) {
					return false;
				}
				self.destroy();

				var dropzoneOptions = bp.Readylaunch.Utilities.createDropzoneOptions(
					{
						dictFileTooBig               : bbRlMedia.dictFileTooBig,
						dictDefaultMessage           : bbRlMedia.dropzone_media_message,
						acceptedFiles                : 'image/*',
						maxFiles                     : ! _.isUndefined( bbRlMedia.maxFiles ) ? bbRlMedia.maxFiles : 10,
						maxFilesize                  : ! _.isUndefined( bbRlMedia.max_upload_size ) ? bbRlMedia.max_upload_size : 2,
						thumbnailWidth               : 140,
						thumbnailHeight              : 140,
						dictMaxFilesExceeded         : bbRlMedia.media_dict_file_exceeded,
						previewTemplate              : document.getElementsByClassName( 'activity-post-default-template' )[ 0 ].innerHTML,
						dictCancelUploadConfirmation : bbRlMedia.dictCancelUploadConfirmation,
						dictInvalidFileType          : bp_media_dropzone.dictInvalidFileType,
					}
				);

				bp.Nouveau.Activity.postForm.dropzone = new window.Dropzone( '#bb-rl-activity-post-media-uploader', dropzoneOptions );

				bp.Readylaunch.Utilities.setupDropzoneEventHandlers(
					this,
					bp.Nouveau.Activity.postForm.dropzone,
					{
						ActiveComponent          : 'activity',
						modelKey                 : 'media',
						uploaderSelector         : '#bb-rl-activity-post-media-uploader',
						parentSelector           : '#bb-rl-whats-new-form',
						parentAttachmentSelector : '#bb-rl-whats-new-attachments',
						actionName               : 'media_upload',
						nonceName                : 'media',
						mediaType                : 'media',
						otherButtonSelectors     : [
							'#bb-rl-activity-document-button',
							'#bb-rl-activity-video-button',
							'#bb-rl-activity-gif-button'
						],
						errorMessage             : bbRlMedia.bb_rl_invalid_media_type,
					}
				);
			}
		}
	);

	// Activity Document View.
	bp.Views.ActivityDocument = bp.View.extend(
		{
			tagName: 'div',
			className: 'bb-rl-activity-document-container',
			template: bp.template( 'activity-document' ),
			document: [],

			initialize: function () {
				this.model.set( 'document', this.document );
				this.listenTo( Backbone, 'activity_document_toggle', this.toggle_document_uploader );
				this.listenTo( Backbone, 'activity_document_close', this.destroyDocument );
			},

			toggle_document_uploader: function () {
				var self = this;
				if ( self.$el.find( '#bb-rl-activity-post-document-uploader' ).hasClass( 'open' ) ) {
					self.destroyDocument();
				} else {
					self.open_document_uploader();
				}
			},

			destroyDocument: function () {
				var self                  = this;
				var $documentUploaderElem = self.$el.find( '#bb-rl-activity-post-document-uploader' );
				if ( ! _.isNull( bp.Nouveau.Activity.postForm.dropzone ) ) {
					bp.Nouveau.Activity.postForm.dropzone.destroy();
					$documentUploaderElem.html( '' );
				}
				self.document = [];
				$documentUploaderElem.removeClass( 'open' ).addClass( 'closed' );
				$( '#bb-rl-whats-new-attachments' ).addClass( 'empty' ).closest( '#bb-rl-whats-new-form' ).removeClass( 'focus-in--attm' );
			},

			open_document_uploader: function () {
				var self = this;
				if ( self.$el.find( '#bb-rl-activity-post-document-uploader' ).hasClass( 'open' ) ) {
					return false;
				}
				self.destroyDocument();

				var dropzoneOptions = bp.Readylaunch.Utilities.createDropzoneOptions(
					{
						dictFileTooBig               : bbRlMedia.dictFileTooBig,
						acceptedFiles                : bbRlMedia.document_type,
						createImageThumbnails        : false,
						dictDefaultMessage           : bbRlMedia.dropzone_document_message,
						maxFiles                     : ! _.isUndefined( bbRlDocument.maxFiles ) ? bbRlDocument.maxFiles : 10,
						maxFilesize                  : ! _.isUndefined( bbRlDocument.max_upload_size ) ? bbRlDocument.max_upload_size : 2,
						dictInvalidFileType          : bbRlDocument.dictInvalidFileType,
						dictMaxFilesExceeded         : bbRlMedia.document_dict_file_exceeded,
						previewTemplate              : document.getElementsByClassName( 'activity-post-document-template' )[ 0 ].innerHTML,
						dictCancelUploadConfirmation : bbRlMedia.dictCancelUploadConfirmation,
					}
				);

				bp.Nouveau.Activity.postForm.dropzone = new window.Dropzone( '#bb-rl-activity-post-document-uploader', dropzoneOptions );

				bp.Readylaunch.Utilities.setupDropzoneEventHandlers(
					this,
					bp.Nouveau.Activity.postForm.dropzone,
					{
						ActiveComponent          : 'activity',
						modelKey                 : 'document',
						uploaderSelector         : '#bb-rl-activity-post-document-uploader',
						parentSelector           : '#bb-rl-whats-new-form',
						parentAttachmentSelector : '#bb-rl-whats-new-attachments',
						actionName               : 'document_document_upload',
						nonceName                : 'media',
						mediaType                : 'document',
						otherButtonSelectors     : [
							'#bb-rl-activity-media-button',
							'#bb-rl-activity-gif-button',
							'#bb-rl-activity-video-button'
						],
						errorMessage             : bbRlMedia.bb_rl_invalid_media_type,
					}
				);
			}
		}
	);

	// Activity Video View.
	bp.Views.ActivityVideo = bp.View.extend(
		{
			tagName: 'div',
			className: 'bb-rl-activity-video-container',
			template: bp.template( 'activity-video' ),
			video: [],
			videoDropzoneObj: null,
			editActivityData: null,

			initialize: function () {
				this.model.set( 'video', this.video );
				this.listenTo( Backbone, 'activity_video_toggle', this.toggle_video_uploader );
				this.listenTo( Backbone, 'activity_video_close', this.destroyVideo );
			},

			toggle_video_uploader: function () {
				var self = this;
				if ( self.$el.find( '#bb-rl-activity-post-video-uploader' ).hasClass( 'open' ) ) {
					self.destroyVideo();
				} else {
					self.open_video_uploader();
				}
			},

			destroyVideo: function () {
				var self               = this;
				var $videoUploaderElem = self.$el.find( '#bb-rl-activity-post-video-uploader' );
				if ( ! _.isNull( bp.Nouveau.Activity.postForm.dropzone ) ) {
					bp.Nouveau.Activity.postForm.dropzone.destroy();
					$videoUploaderElem.html( '' );
				}
				self.video = [];
				$videoUploaderElem.removeClass( 'open' ).addClass( 'closed' );
				$( '#bb-rl-whats-new-attachments' ).addClass( 'empty' ).closest( '#bb-rl-whats-new-form' ).removeClass( 'focus-in--attm' );
				$( 'body' ).removeClass( 'video-post-form-open' );
			},

			open_video_uploader: function () {
				var self = this;
				if ( self.$el.find( '#bb-rl-activity-post-video-uploader' ).hasClass( 'open' ) ) {
					return false;
				}
				self.destroyVideo();

				var dropzoneOptions = bp.Readylaunch.Utilities.createDropzoneOptions(
					{
						dictFileTooBig               : bbRlVideo.dictFileTooBig,
						acceptedFiles                : bbRlVideo.video_type,
						createImageThumbnails        : false,
						dictDefaultMessage           : bbRlVideo.dropzone_video_message,
						maxFiles                     : ! _.isUndefined( bbRlVideo.maxFiles ) ? bbRlVideo.maxFiles : 10,
						maxFilesize                  : ! _.isUndefined( bbRlVideo.max_upload_size ) ? bbRlVideo.max_upload_size : 2,
						dictInvalidFileType          : bbRlVideo.dictInvalidFileType,
						dictMaxFilesExceeded         : bbRlVideo.video_dict_file_exceeded,
						previewTemplate              : document.getElementsByClassName( 'activity-post-video-template' )[ 0 ].innerHTML,
						dictCancelUploadConfirmation : bbRlVideo.dictCancelUploadConfirmation,
					}
				);

				bp.Nouveau.Activity.postForm.dropzone = new window.Dropzone( '#bb-rl-activity-post-video-uploader', dropzoneOptions );

				bp.Readylaunch.Utilities.setupDropzoneEventHandlers(
					this,
					bp.Nouveau.Activity.postForm.dropzone,
					{
						ActiveComponent          : 'activity',
						modelKey                 : 'video',
						uploaderSelector         : '#bb-rl-activity-post-video-uploader',
						parentSelector           : '#bb-rl-whats-new-form',
						parentAttachmentSelector : '#bb-rl-whats-new-attachments',
						actionName               : 'video_upload',
						nonceName                : 'video',
						mediaType                : 'video',
						otherButtonSelectors     : [
							'#bb-rl-activity-media-button',
							'#bb-rl-activity-gif-button',
							'#bb-rl-activity-document-button'
						],
						errorMessage             : bbRlMedia.bb_rl_invalid_media_type,
					}
				);

				$( 'body' ).addClass( 'video-post-form-open' );
			},

			createVideoThumbnailFromUrl: function ( mock_file ) {
				var self = this;
				self.videoDropzoneObj.createVideoThumbnailFromUrl(
					mock_file,
					self.videoDropzoneObj.options.thumbnailWidth,
					self.videoDropzoneObj.options.thumbnailHeight,
					self.videoDropzoneObj.options.thumbnailMethod,
					true,
					function ( thumbnail ) {
						self.videoDropzoneObj.emit( 'thumbnail', mock_file, thumbnail );
						self.videoDropzoneObj.emit( 'complete', mock_file );
					}
				);
			}
		}
	);

	// Activity link preview.
	bp.Views.ActivityLinkPreview = bp.View.extend(
		{
			tagName: 'div',
			className: 'activity-url-scrapper-container',
			template: bp.template( 'activity-link-preview' ),
			events: {
				'click #activity-link-preview-button': 'toggleURLInput',
				'click #activity-url-prevPicButton': 'prev',
				'click #activity-url-nextPicButton': 'next',
				'click #activity-link-preview-remove-image': 'close',
				'click #activity-close-link-suggestion': 'destroy',
				'click .icon-exchange': 'displayPrevNextButton',
				'click #activity-link-preview-select-image': 'selectImageForPreview'
			},

			initialize: function () {
				this.model.set(
					{
						'link_scrapping': false,
						'link_embed'    : false,
					}
				);
				this.listenTo( this.model, 'change', this.render );
				document.addEventListener( 'activity_link_preview_open', this.open.bind( this ) );
				document.addEventListener( 'activity_link_preview_close', this.destroy.bind( this ) );
			},

			render: function () {
				// do not re render if post form is submitting.
				if ( this.model.get( 'posting' ) ) {
					return;
				}

				this.$el.html( this.template( this.model.toJSON() ) );
				// Show/Hide Preview Link image button.
				if (
					'undefined' !== typeof this.model.get( 'link_swap_image_button' ) &&
					1 === this.model.get( 'link_swap_image_button' )
				) {
					this.displayNextPrevButtonView();
				}

				// if link embed is used then add class to container.
				if ( this.model.get( 'link_embed' ) ) {

					// support for instgram embed after ajax.
					if ( ! _.isUndefined( window.instgrm ) ) {
						window.instgrm.Embeds.process();
					}

					// support for facebook embed after ajax.
					if ( ! _.isUndefined( window.FB ) && ! _.isUndefined( window.FB.XFBML ) ) {
						window.FB.XFBML.parse( this.el );
					}

					this.$el.addClass( 'activity-post-form-link-wp-embed' );
				} else {
					this.$el.removeClass( 'activity-post-form-link-wp-embed' );
				}
				return this;
			},

			prev: function () {
				var imageIndex = this.model.get( 'link_image_index' );
				if ( imageIndex > 0 ) {
					this.model.set( 'link_image_index', imageIndex - 1 );
				}
			},

			next: function () {
				var imageIndex = this.model.get( 'link_image_index' );
				var images     = this.model.get( 'link_images' );
				if ( imageIndex < images.length - 1 ) {
					this.model.link_image_index++;
					this.model.set( 'link_image_index', imageIndex + 1 );
				}
			},

			open: function ( e ) {
				e.preventDefault();
				this.model.set( 'link_scrapping', true );
				this.$el.addClass( 'open' );
			},

			close: function ( e ) {
				e.preventDefault();
				this.model.set(
					{
						link_images: [],
						link_image_index: 0,
						link_image_index_save: '0',
					}
				);
			},

			destroy: function ( e ) {
				if ( ! _.isUndefined( e ) ) {
					e.preventDefault();
				}
				// Set default values.
				this.model.set(
					{
						link_success: false,
						link_error: false,
						link_error_msg: '',
						link_scrapping: false,
						link_images: [],
						link_image_index: 0,
						link_title: '',
						link_description: '',
						link_url: '',
						link_embed: false,
						link_swap_image_button: 0,
						link_image_index_save: '0',
					}
				);
				document.removeEventListener( 'activity_link_preview_open', this.open.bind( this ) );
				document.removeEventListener( 'activity_link_preview_close', this.destroy.bind( this ) );

				$( '#bb-rl-whats-new' ).removeData( 'activity-url-preview' );
				$( '#bb-rl-whats-new-attachments' ).addClass( 'empty' ).closest( '#bb-rl-whats-new-form' ).removeClass( 'focus-in--attm' );
			},

			displayPrevNextButton: function ( e ) {
				e.preventDefault();
				this.model.set( 'link_swap_image_button', 1 );
				this.displayNextPrevButtonView();
			},

			displayNextPrevButtonView: function () {
				$( '#activity-url-prevPicButton' ).show();
				$( '#activity-url-nextPicButton' ).show();
				$( '#activity-link-preview-select-image' ).show();
				$( '#icon-exchange' ).hide();
				$( '#activity-link-preview-remove-image' ).hide();
			},

			selectImageForPreview: function ( e ) {
				e.preventDefault();
				var imageIndex = this.model.get( 'link_image_index' );
				this.model.set( 'link_image_index_save', imageIndex );
				$( '#icon-exchange' ).show();
				$( '#activity-link-preview-remove-image' ).show();
				$( '#activity-link-preview-select-image' ).hide();
				$( '#activity-url-prevPicButton' ).hide();
				$( '#activity-url-nextPicButton' ).hide();
			}
		}
	);

	// Activity gif selector.
	bp.Views.ActivityAttachedGifPreview = bp.View.extend(
		{
			tagName: 'div',
			className: 'bb-rl-activity-attached-gif-container',
			template: bp.template( 'activity-attached-gif' ),
			standalone: false,
			events: {
				'click .gif-image-remove': 'destroy'
			},

			initialize: function ( options ) {
				this.destroy = this.destroy.bind( this );

				// Check if standalone is provided in options and update the property.
				if ( options && options.standalone !== undefined ) {
					this.standalone = options.standalone;
				}

				this.listenTo( this.model, 'change', this.render );
				this.listenTo( Backbone, 'activity_gif_close', this.destroy );
			},

			render: function () {
				this.$el.html( this.template( this.model.toJSON() ) );

				var gifData = this.model.get( 'gif_data' );
				if ( ! _.isEmpty( gifData ) ) {
					this.el.style.backgroundImage = 'url(' + gifData.images.fixed_width.url + ')';
					this.el.style.backgroundSize  = 'contain';
					this.el.style.minHeight       = gifData.images.original.height + 'px';
					this.el.style.width           = gifData.images.original.width + 'px';
					$( '#bb-rl-whats-new-attachments' ).removeClass( 'empty' ).closest( '#bb-rl-whats-new-form' ).addClass( 'focus-in--attm' );

					if ( ! _.isUndefined( bp.draft_activity.data.gif_data ) && bp.draft_activity.data.gif_data.id !== gifData.id ) {
						bp.draft_content_changed = true;
					} else if ( _.isUndefined( bp.draft_activity.data.gif_data ) ) {
						bp.draft_content_changed = true;
					}
				}

				return this;
			},

			destroy: function ( event ) {
				var old_gif_data = this.model.get( 'gif_data' );

				this.model.set( 'gif_data', {} );
				if ( $( '#message-feedback' ).hasClass( 'noMediaError' ) ) {
					this.model.unset( 'errors' );
				}
				this.el.style.backgroundImage = '';
				this.el.style.backgroundSize  = '';
				this.el.style.minHeight       = '0px';
				this.el.style.width           = '0px';
				$( '#bb-rl-whats-new-attachments' ).addClass( 'empty' ).closest( '#bb-rl-whats-new-form' ).removeClass( 'focus-in--attm' );
				var tool_box = this.$el.parents( '#bb-rl-whats-new-form' );
				this.enableButtonsInToolBox(
					tool_box,
					[
					'#bb-rl-activity-document-button',
					'#bb-rl-activity-media-button',
					'#bb-rl-activity-video-button',
					'#bb-rl-activity-gif-button'
					]
				);

				if ( this.standalone ) {
					this.$el.closest( '.screen-content, .elementor-widget-container, .buddypress-wrap' ).find( '#bb-rl-activity-modal .ac-form' ).removeClass( 'has-gif' );
				} else {
					this.$el.closest( '.ac-form' ).removeClass( 'has-gif' );
				}

				var tool_box_comment = this.$el.parents( '.bb-rl-ac-form-container' );
				this.enableButtonsInToolBox(
					tool_box_comment,
					[
					'.bb-rl-ac-reply-toolbar .bb-rl-ac-reply-media-button',
					'.bb-rl-ac-reply-toolbar .bb-rl-ac-reply-document-button',
					'.bb-rl-ac-reply-toolbar .bb-rl-ac-reply-video-button',
					'.bb-rl-ac-reply-toolbar .bb-rl-ac-reply-gif-button'
					]
				);

				if ( tool_box_comment.find( '.ac-textarea' ).children( '.ac-input' ).length > 0 ) {
					var $activity_comment_content = tool_box_comment.find( '.ac-textarea' ).children( '.ac-input' ).html();

					var content = $.trim( $activity_comment_content.replace( /<div>/gi, '\n' ).replace( /<\/div>/gi, '' ) );
					content     = content.replace( /&nbsp;/g, ' ' );

					var content_text = tool_box_comment.find( '.ac-textarea' ).children( '.ac-input' ).text().trim();
					if ( content_text !== '' || content.indexOf( 'bb-rl-emojioneemoji' ) >= 0 ) {
						$( tool_box_comment ).closest( 'form' ).addClass( 'has-content' );
					} else {
						$( tool_box_comment ).closest( 'form' ).removeClass( 'has-content' );
					}
				}

				if ( ! _.isUndefined( event ) && ! _.isEmpty( old_gif_data ) && _.isEmpty( this.model.get( 'gif_data' ) ) ) {
					bp.draft_content_changed = true;
				}
			},

			enableButtonsInToolBox: function ( toolBox, selectors ) {
				selectors.forEach(
					function ( selector ) {
						var button = toolBox.find( selector );
						if ( button.length ) {
								button.parents( '.bb-rl-post-elements-buttons-item' ).removeClass( 'disable no-click' );
								button.removeClass( 'open' );
						}
					}
				);
			}
		}
	);

	// Gif search dropdown.
	bp.Views.GifMediaSearchDropdown = bp.View.extend(
		{
			tagName: 'div',
			className: 'bb-rl-activity-attached-gif-container',
			template: bp.template( 'gif-media-search-dropdown' ),
			total_count: 0,
			offset: 0,
			limit: 20,
			q: null,
			requests: [],
			standalone: false,
			events: {
				'keydown .search-query-input': 'search',
				'click .found-media-item': 'select'
			},

			initialize: function ( options ) {
				this.select = this.select.bind( this );

				// Check if standalone is provided in options and update the property.
				if ( options && options.standalone !== undefined ) {
					this.standalone = options.standalone;
				}

				this.options = options || {};
				this.giphy   = new window.Giphy( bbRlMedia.gif_api_key );

				this.gifDataItems = new bp.Collections.GifDatas();
				this.listenTo( this.gifDataItems, 'add', this.addOne );
				this.listenTo( this.gifDataItems, 'reset', this.addAll );

				document.addEventListener( 'scroll', _.bind( this.loadMore, this ), true );

			},

			render: function () {
				this.$el.html( this.template( this.model.toJSON() ) );
				this.$gifResultItem = this.$el.find( '.gif-search-results-list' );
				this.loadTrending();
				return this;
			},

			search: function ( e ) {

				// Prevent search dropdown from closing with an enter key.
				if ( e.key === 'Enter' || e.keyCode === 13 ) {
					e.preventDefault();
					return false;
				}

				var self = this;

				if ( this.Timeout != null ) {
					clearTimeout( this.Timeout );
				}

				if ( '' === e.target.value ) {
					this.loadTrending();
					return;
				}

				this.Timeout = setTimeout(
					function () {
						this.Timeout = null;
						self.searchGif( e.target.value );
					},
					1000
				);

			},

			searchGif: function ( q ) {
				var self    = this;
				self.q      = q;
				self.offset = 0;

				self.clearRequests();
				self.el.classList.add( 'loading' );
				this.$el.find( '.gif-no-results' ).removeClass( 'show' );
				this.$el.find( '.gif-no-connection' ).removeClass( 'show' );

				var request = self.giphy.search(
					{
						q: q,
						offset: self.offset,
						fmt: 'json',
						limit: this.limit
					},
					function ( response ) {
						if ( undefined !== response.data.length && 0 === response.data.length ) {
							$( self.el ).find( '.gif-no-results' ).addClass( 'show' );
						}
						if ( undefined !== response.meta.status && 200 !== response.meta.status ) {
							$( self.el ).find( '.gif-no-connection' ).addClass( 'show' );
						}
						self.gifDataItems.reset( response.data );
						self.total_count = response.pagination.total_count;
						self.el.classList.remove( 'loading' );
					},
					function () {
						$( self.el ).find( '.gif-no-connection' ).addClass( 'show' );
					}
				);

				self.requests.push( request );
				self.offset = self.offset + self.limit;
			},

			select: function ( e ) {
				e.preventDefault();
				this.$el.parent().removeClass( 'open' );
				var model = this.gifDataItems.findWhere( { id: e.currentTarget.dataset.id } );
				this.model.set( 'gif_data', model.attributes );

				var toolBox = this.$el.parents( '#bb-rl-whats-new-form' );
				// Disable buttons in the main form.
				this.disableButtonsInToolBox(
					toolBox,
					[
					'#bb-rl-activity-document-button',
					'#bb-rl-activity-media-button',
					'#bb-rl-activity-video-button'
					]
				);

				var toolBoxComment = this.$el.parents( '.bb-rl-ac-reply-content' );
				this.disableButtonsInToolBox(
					toolBoxComment,
					[
					'.bb-rl-ac-reply-toolbar .bb-rl-ac-reply-media-button',
					'.bb-rl-ac-reply-toolbar .bb-rl-ac-reply-document-button',
					'.bb-rl-ac-reply-toolbar .bb-rl-ac-reply-video-button'
					]
				);

				var whatNewForm = this.$el.closest( '#bb-rl-whats-new-form' );

				if ( this.standalone ) {
					this.$el.closest( '.screen-content, .elementor-widget-container, .bb-rl-wrap, .buddypress-wrap' ).find( '#bb-rl-activity-modal .ac-form' ).addClass( 'has-gif' );
				} else {
					this.$el.closest( '.ac-form' ).addClass( 'has-gif' );
				}

				var whatNewScroll = whatNewForm.find( '.bb-rl-whats-new-scroll-view' );
				if ( whatNewScroll.length > 0 ) {
					whatNewScroll.stop().animate(
						{
							scrollTop: whatNewScroll[0].scrollHeight
						},
						300
					);
				}

				e.stopPropagation();
			},

			// Add a single GifDataItem to the list by creating a view for it, and
			// appending its element to the `<ul>`.
			addOne: function ( data ) {
				var view = new bp.Views.GifDataItem( { model: data } );
				this.$gifResultItem.append( view.render().el );
			},

			// Add all items in the **GifDataItem** collection at once.
			addAll: function () {
				this.$gifResultItem.html( '' );
				this.gifDataItems.each( this.addOne, this );
			},

			loadTrending: function () {
				var self    = this;
				self.offset = 0;
				self.q      = null;

				self.clearRequests();
				self.el.classList.add( 'loading' );

				var request = self.giphy.trending(
					{
						offset: self.offset,
						fmt: 'json',
						limit: this.limit
					},
					function ( response ) {
						self.gifDataItems.reset( response.data );
						self.total_count = response.pagination.total_count;
						self.el.classList.remove( 'loading' );
					}
				);

				self.requests.push( request );
				self.offset = self.offset + self.limit;
			},

			loadMore: function ( event ) {
				if ( 'gif-search-results' === event.target.id ) { // or any other filtering condition.
					var el = event.target;
					if ( el.scrollTop + el.offsetHeight >= el.scrollHeight && ! el.classList.contains( 'loading' ) ) {
						if ( this.total_count > 0 && this.offset <= this.total_count ) {
							var self   = this,
								params = {
									offset: self.offset,
									fmt   : 'json',
									limit : self.limit
							};

							self.el.classList.add( 'loading' );
							var request;
							if ( _.isNull( self.q ) ) {
								request = self.giphy.trending( params, _.bind( self.loadMoreResponse, self ) );
							} else {
								request = self.giphy.search( _.extend( { q: self.q }, params ), _.bind( self.loadMoreResponse, self ) );
							}

							self.requests.push( request );
							this.offset = this.offset + this.limit;
						}
					}
				}
			},

			clearRequests: function () {
				this.gifDataItems.reset();
				var requestLength = this.requests.length;
				for ( var i = 0; i < requestLength; i++ ) {
					this.requests[ i ].abort();
				}

				this.requests = [];
			},

			loadMoreResponse: function ( response ) {
				this.el.classList.remove( 'loading' );
				this.gifDataItems.add( response.data );
			},

			disableButtonsInToolBox: function ( toolBox, selectors ) {
				selectors.forEach(
					function ( selector ) {
						var button = toolBox.find( selector );
						if ( button.length ) {
								button.parents( '.bb-rl-post-elements-buttons-item' ).addClass( 'disable' );
						}
					}
				);
			}
		}
	);

	// Gif search dropdown single item.
	bp.Views.GifDataItem = bp.View.extend(
		{
			tagName: 'li',
			template: wp.template( 'gif-result-item' ),
			initialize: function () {
				this.listenTo( this.model, 'change', this.render );
				this.listenTo( this.model, 'destroy', this.remove );

				window.addEventListener( 'resize', this.render.bind( this ) );
			},

			render: function () {
				var bgNo           = Math.floor( Math.random() * ( 6 - 1 + 1 ) ) + 1,
					images         = this.model.get( 'images' ),
					strictWidth    = window.innerWidth > 768 ? 140 : 130,
					originalWidth  = images.original.width,
					originalHeight = images.original.height,
					relativeHeight = ( strictWidth * originalHeight ) / originalWidth;

				this.$el.html( this.template( this.model.toJSON() ) );
				this.el.classList.add( 'bg' + bgNo );
				this.el.style.height = relativeHeight + 'px';

				return this;
			}

		}
	);

	// Regular input.
	bp.Views.ActivityInput = bp.View.extend(
		{
			tagName: 'input',
			attributes: {
				type: 'text'
			},

			initialize: function () {
				if ( ! _.isObject( this.options ) ) {
					return;
				}

				_.each(
					this.options,
					function ( value, key ) {
						this.$el.prop( key, value );
					},
					this
				);

				this.listenTo( this.model, 'change:link_loading', this.onLinkScrapping );
			},

			onLinkScrapping: function () {
				this.$el.prop( 'disabled', false );
			}
		}
	);

	// The content of the activity.
	bp.Views.WhatsNew = bp.View.extend(
		{
			tagName: 'div',
			className: 'bb-rl-suggestions',
			id: 'bb-rl-whats-new',
			events: {
				'paste': 'handlePaste',
				'keyup': 'handleKeyUp',
				'click': 'handleClick'
			},
			attributes: {
				name: 'whats-new',
				cols: '50',
				rows: '4',
				placeholder: bbRlActivity.strings.whatsnewPlaceholder,
				'aria-label': bbRlActivity.strings.whatsnewLabel,
				contenteditable: true,
				autocorrect: 'off',
				'data-suggestions-group-id': ! _.isUndefined( bbRlActivity.params.object ) && 'group' === bbRlActivity.params.object ? bbRlActivity.params.item_id : false,
			},
			loadURLAjax: null,
			loadedURLs: [],

			initialize: function () {
				this.on( 'ready', this.adjustContent, this );
				this.on( 'ready', this.activateTinyMce, this );
				this.options.activity.on( 'change:content', this.resetContent, this );
				this.linkTimeout = null;
			},

			adjustContent: function () {

				// First adjust layout.
				this.$el.css(
					{
						resize: 'none',
						height: '50px'
					}
				);

				// Check for mention.
				var mention = bp.Nouveau.getLinkParams( null, 'r' ) || null;

				if ( ! _.isNull( mention ) ) {
					this.$el.text( '@' + _.escape( mention ) + ' ' );
					this.$el.focus();
				}
			},

			resetContent: function ( activity ) {
				if ( _.isUndefined( activity ) ) {
					return;
				}

				this.$el.html( activity.get( 'content' ) );
			},

			handlePaste: function () {
				// trigger keyup event of this view to handle changes.
				this.$el.trigger( 'keyup' );
			},

			handleKeyUp: function () {
				var self = this;

				if ( ! _.isUndefined( bbRlActivity.params.link_preview ) ) {
					if ( this.linkTimeout != null ) {
						clearTimeout( this.linkTimeout );
					}

					this.linkTimeout = setTimeout(
						function () {
							this.linkTimeout = null;
							self.scrapURL( window.activity_editor.getContent() );
						},
						500
					);
				}

				this.saveCaretPosition();

				var scrollView   = this.$el.closest( '.bb-rl-whats-new-scroll-view' ),
					scrollHeight = scrollView.prop( 'scrollHeight' ),
					clientHeight = scrollView.prop( 'clientHeight' ),
					form         = this.$el.closest( '#bb-rl-whats-new-form' );
				if ( scrollHeight > clientHeight ) {
					form.addClass( 'focus-in--scroll' );
				} else {
					form.removeClass( 'focus-in--scroll' );
				}
			},

			handleClick: function () {
				this.saveCaretPosition();
			},

			saveCaretPosition: function () {
				if ( window.getSelection && document.createRange ) {
					var sel = window.getSelection && window.getSelection();
					if ( sel && sel.rangeCount > 0 ) {
						window.activityCaretPosition = sel.getRangeAt( 0 );
					}
				} else {
					window.activityCaretPosition = document.selection.createRange();
				}
			},

			scrapURL: function ( urlText ) {
				var urlString            = '',
					activity_URL_preview = this.$el.closest( '#bb-rl-whats-new' ).data( 'activity-url-preview' );

				if ( urlText === null && activity_URL_preview === undefined ) {
					return;
				}

				// Create a DOM parser.
				var parser         = new DOMParser(),
					doc            = parser.parseFromString( urlText, 'text/html' ),
					anchorElements = doc.querySelectorAll( 'a.bp-suggestions-mention' ); // Exclude the mention links from the urlText.
				anchorElements.forEach(
					function ( anchor ) {
						anchor.remove();
					}
				);

				// parse html now to get the url.
				urlText = doc.body.innerHTML;

				if ( urlText.indexOf( '<img' ) >= 0 ) {
					urlText = urlText.replace( /<img .*?>/g, '' );
				}

				if ( urlText.indexOf( 'http://' ) >= 0 ) {
					urlString = this.getURL( 'http://', urlText );
				} else if ( urlText.indexOf( 'https://' ) >= 0 ) {
					urlString = this.getURL( 'https://', urlText );
				} else if ( urlText.indexOf( 'www.' ) >= 0 ) {
					urlString = this.getURL( 'www', urlText );
				}

				if ( urlString !== '' ) {
					// check if the url of any of the excluded video oembeds.
					var url_a    = document.createElement( 'a' );
					url_a.href   = urlString;
					var hostname = url_a.hostname;
					if ( bbRlActivity.params.excluded_hosts.indexOf( hostname ) !== -1 ) {
						urlString = '';
					}
				}

				if ( '' !== urlString ) {
					this.loadURLPreview( urlString );
				} else if ( activity_URL_preview !== undefined ) {
					this.loadURLPreview( activity_URL_preview );
				}
			},

			getURL: function ( prefix, urlText ) {
				var urlString   = '';
				urlText         = urlText.replace( /&nbsp;/g, '' );
				var startIndex  = urlText.indexOf( prefix ),
					responseUrl = '';

				if ( ! _.isUndefined( $( $.parseHTML( urlText ) ).attr( 'href' ) ) ) {
					urlString = $( urlText ).attr( 'href' );
				} else {
					var urlTextLength = urlText.length;
					for ( var i = startIndex; i < urlTextLength; i++ ) {
						if (
							urlText[ i ] === ' ' ||
							urlText[ i ] === '\n' ||
							( urlText[ i ] === '"' && urlText[ i + 1 ] === '>' ) ||
							( urlText[ i ] === '<' && urlText[ i + 1 ] === 'b' && urlText[ i + 2 ] === 'r' )
						) {
							break;
						} else {
							urlString += urlText[ i ];
						}
					}
					if ( prefix === 'www' ) {
						prefix    = 'http://';
						urlString = prefix + urlString;
					}
				}

				var div       = document.createElement( 'div' );
				div.innerHTML = urlString;
				var elements  = div.getElementsByTagName( '*' );

				while ( elements[ 0 ] ) {
					elements[ 0 ].parentNode.removeChild( elements[ 0 ] );
				}

				if ( div.innerHTML.length > 0 ) {
					responseUrl = div.innerHTML;
				}

				return responseUrl;
			},

			loadURLPreview: function ( url ) {
				var self   = this,
					regexp = /^(http:\/\/www\.|https:\/\/www\.|http:\/\/|https:\/\/)?[a-z0-9]+([\-\.]{1}[a-z0-9]+)*\.[a-z]{2,24}(:[0-9]{1,5})?(\/.*)?$/;
				url        = $.trim( url );
				if ( regexp.test( url ) ) {
					if ( ( ! _.isUndefined( self.options.activity.get( 'link_success' ) ) && true === self.options.activity.get( 'link_success' ) ) && self.options.activity.get( 'link_url' ) === url ) {
						return false;
					}

					if ( url.includes( window.location.hostname ) && ( url.includes( 'download_document_file' ) || url.includes( 'download_media_file' ) || url.includes( 'download_video_file' ) ) ) {
						return false;
					}

					var urlResponse = false;
					if ( self.loadedURLs.length ) {
						$.each(
							self.loadedURLs,
							function ( index, urlObj ) {
								if ( urlObj.url === url ) {
									urlResponse = urlObj.response;
									return false;
								}
							}
						);
					}

					if ( self.loadURLAjax != null ) {
						self.loadURLAjax.abort();
					}

					self.options.activity.set(
						{
							link_scrapping: true,
							link_loading: true,
							link_error: false,
							link_url: url,
							link_embed: false
						}
					);

					if ( ! urlResponse ) {
						self.loadURLAjax = bp.ajax.post( 'bp_activity_parse_url', { url: url } ).always(
							function ( response ) {
								self.setURLResponse( response, url );
							}
						);
					} else {
						self.setURLResponse( urlResponse, url );
					}
				}
			},

			setURLResponse: function ( response, url ) {
				var self = this;

				self.options.activity.set( 'link_loading', false );

				if ( response.title === '' && response.images === '' ) {
					self.options.activity.set( 'link_scrapping', false );
					return;
				}

				if ( response.error === '' ) {
					var urlImages = response.images;
					if (
						true === self.options.activity.get( 'edit_activity' ) && 'undefined' === typeof self.options.activity.get( 'link_image_index_save' ) && '' === self.options.activity.get( 'link_image_index_save' )
					) {
						urlImages = '';
					}
					var urlImagesIndex = '';
					if ( '' !== self.options.activity.get( 'link_image_index' ) ) {
						urlImagesIndex = parseInt( self.options.activity.get( 'link_image_index' ) );
					}

					var prev_activity_preview_url = this.$el.closest( '#bb-rl-whats-new' ).data( 'activity-url-preview' ),
						link_image_index_save     = self.options.activity.get( 'link_image_index_save' );
					if ( '' !== prev_activity_preview_url && prev_activity_preview_url !== url ) {

						// Reset older preview data.
						urlImagesIndex        = 0;
						link_image_index_save = 0;
						this.$el.closest( '#bb-rl-whats-new' ).data( 'activity-url-preview', url );

					}
					self.options.activity.set(
						{
							link_success: true,
							link_title: ! _.isUndefined( response.title ) ? response.title : '',
							link_description: ! _.isUndefined( response.description ) ? response.description : '',
							link_images: urlImages,
							link_image_index: urlImagesIndex,
							link_image_index_save: link_image_index_save,
							link_embed: ! _.isUndefined( response.wp_embed ) && response.wp_embed
						}
					);

					var $whatsNewAttachments = $( '#bb-rl-whats-new-attachments' );
					$whatsNewAttachments.removeClass( 'empty' ).closest( '#bb-rl-whats-new-form' ).addClass( 'focus-in--attm' );

					if ( $whatsNewAttachments.hasClass( 'activity-video-preview' ) ) {
						$whatsNewAttachments.removeClass( 'activity-video-preview' );
					}

					if ( $whatsNewAttachments.hasClass( 'activity-link-preview' ) ) {
						$whatsNewAttachments.removeClass( 'activity-link-preview' );
					}

					if ( $( '.bb-rl-activity-media-container' ).length ) {
						if ( ( 'undefined' !== typeof response.description && response.description.indexOf( 'iframe' ) > -1 ) || ( ! _.isUndefined( response.wp_embed ) && response.wp_embed ) ) {
							$whatsNewAttachments.addClass( 'activity-video-preview' );
						} else {
							$whatsNewAttachments.addClass( 'activity-link-preview' );
						}
					}

					self.loadedURLs.push( { 'url': url, 'response': response } );

				} else {
					self.options.activity.set(
						{
							link_success: false,
							link_error: true,
							link_error_msg: response.error
						}
					);
				}
			},
			activateTinyMce: function () {

				if ( ! _.isUndefined( window.MediumEditor ) ) {
					var $whatsNew = $( '#bb-rl-whats-new' );
					$whatsNew.each(
						function () {
							var $this           = $( this ),
								whatsnewcontent = $this.closest( '#bb-rl-whats-new-form' ).find( '.bb-rl-editor-toolbar__medium' )[ 0 ];

							if ( ! $( this ).closest( '.edit-activity-modal-body' ).length ) {

								window.activity_editor = new window.MediumEditor(
									$this,
									{
										placeholder: {
											text: '',
											hideOnClick: true
										},
										toolbar: {
											buttons: [ 'bold', 'italic', 'unorderedlist', 'orderedlist', 'quote', 'anchor', 'pre' ],
											relativeContainer: whatsnewcontent,
											static: true,
											updateOnEmptySelection: true
										},
										paste: {
											forcePlainText: false,
											cleanPastedHTML: true,
											cleanReplacements: [
												[ new RegExp( /<div/gi ), '<p' ],
												[ new RegExp( /<\/div/gi ), '</p' ],
												[ new RegExp( /<h[1-6]/gi ), '<b' ],
												[ new RegExp( /<\/h[1-6]/gi ), '</b' ],
											],
											cleanAttrs: [ 'class', 'style', 'dir', 'id' ],
											cleanTags: [ 'meta', 'div', 'main', 'section', 'article', 'aside', 'button', 'svg', 'canvas', 'figure', 'input', 'textarea', 'select', 'label', 'form', 'table', 'thead', 'tfooter', 'colgroup', 'col', 'tr', 'td', 'th', 'dl', 'dd', 'center', 'caption', 'nav', 'img' ],
											unwrapTags: []
										},
										imageDragging: false,
										anchor: {
											placeholderText: bbRlPlaceholderText,
											linkValidation: true
										}
									}
								);

								window.activity_editor.subscribe(
									'editablePaste',
									function ( e ) {
										setTimeout(
											function () {
												// Wrap all target <li> elements in a single <ul>.
												var targetLiElements = $( e.target ).find( 'li' ).filter(
													function () {
														return ! $( this ).parent().is( 'ul' ) && ! $( this ).parent().is( 'ol' );
													}
												);
												if (targetLiElements.length > 0) {
													targetLiElements.wrapAll( '<ul></ul>' );
												}
											},
											0
										);
									}
								);
							}
						}
					);

					$( document ).on(
						'keyup',
						'.bb-rl-activity-form .medium-editor-toolbar-input',
						function ( event ) {

							var URL = event.target.value;

							if ( bp.Nouveau.isURL( URL ) ) {
								$( event.target ).removeClass( 'isNotValid' ).addClass( 'isValid' );
							} else {
								$( event.target ).removeClass( 'isValid' ).addClass( 'isNotValid' );
							}

						}
					);

					// check for mentions in the url, if set any then focus to editor.
					var mention = bp.Nouveau.getLinkParams( null, 'r' ) || null;

					// Check for mention.
					if ( ! _.isNull( mention ) ) {
						$( '#message_content' ).focus();
					}

				} else if ( ! _.isUndefined( tinymce ) ) {
					tinymce.EditorManager.execCommand( 'mceAddEditor', true, 'bb-rl-whats-new' );
				}
			}
		}
	);

	bp.Views.WhatsNewPostIn = bp.View.extend(
		{
			tagName: 'select',
			id: 'bb-rl-whats-new-post-in',

			attributes: {
				name: 'whats-new-post-in',
				'aria-label': bbRlActivity.strings.whatsnewpostinLabel
			},

			events: {
				change: 'change'
			},

			keys: [],

			initialize: function () {
				this.model = new Backbone.Model();

				this.filters = this.options.filters || {};

				// Build <option> elements, sort by priority, and append to the select element.
				var $options = _.chain( this.filters ).map(
					function ( filter, value ) {
						return {
							el      : $( '<option></option>' ).val( value ).html( filter.text )[0],
							priority: filter.priority || 50
						};
					}
				).sortBy( 'priority' ).pluck( 'el' ).value();

				// Append the sorted options to the select element.
				this.$el.append( $options );
			},

			change: function () {
				var selectedValue = this.el.value,
					filter        = this.filters[ selectedValue ];

				if ( filter ) {
					this.model.set(
						{
							'selected'    : selectedValue,
							'placeholder' : filter.autocomplete_placeholder
						}
					);
				}
			}
		}
	);

	bp.Views.ActivityPrivacy = bp.View.extend(
		{
			tagName: 'div',
			id: 'bb-rl-activity-post-form-privacy',
			template: bp.template( 'activity-post-form-privacy' ),

			initialize: function () {
				this.model = new bp.Models.Activity();
			},
		}
	);

	bp.Views.Item = bp.View.extend(
		{
			tagName: 'div',
			className: 'bb-rl-activity-object',
			template: bp.template( 'activity-target-item' ),

			initialize: function () {
				if ( this.model.get( 'selected' ) ) {
					this.el.className += ' selected';
				}
			},

			events: {
				click: 'setObject'
			},

			setObject: function ( event ) {
				event.preventDefault();

				var whats_new_form = $( '#bb-rl-whats-new-form' );

				if ( true === this.model.get( 'selected' ) ) {
					this.model.unset( 'selected' );
				}

				whats_new_form.removeClass( 'bb-rl-focus-in--blank-group' );
				var $this = this;
				if (
					$this.model.hasOwnProperty( 'attributes' ) &&
					$this.model.attributes.hasOwnProperty( 'object_type' ) &&
					'group' === $this.model.attributes.object_type
				) {
					var previousSelected = _.find(
						this.model.collection.models,
						function ( model ) {
							return model !== $this.model && model.get( 'selected' );
						}
					);
					if ( previousSelected ) {
						previousSelected.set( 'selected', false );
					}
				}
				this.model.set( 'selected', true );

				var typeSupport     = $( '#bb-rl-whats-new-toolbar' ),
					types           = ['media', 'document', 'video'],
					modelAttributes = this.model.attributes;
				types.forEach(
					function ( type ) {
						var groupType  = 'group_' + type,
							changeType = 'document' === type ? 'media' : type;
						if ( 'undefined' !== typeof modelAttributes[ groupType ] && false === modelAttributes[ groupType ] ) {
							var dropzone = bp.Nouveau.Activity.postForm.dropzone;

							if ( ! dropzone || 'bb-rl-activity-post-' + type + '-uploader' === dropzone.element.id ) {
								typeSupport.find( 'bb-rl-post-' + changeType + '.bb-rl-' + type + '-support' ).removeClass( 'active' ).addClass( 'bb-rl-' + type + '-support-hide' );
								Backbone.trigger( 'activity_' + type + '_close' );
							}
						} else {
							typeSupport.find( 'bb-rl-post-' + changeType + '.bb-rl-' + type + '-support' ).removeClass( 'bb-rl-' + type + '-support-hide' );
						}
					}
				);
			},
		}
	);

	bp.Views.AutoComplete = bp.View.extend(
		{
			tagName: 'div',
			id: 'bb-rl-whats-new-post-in-box-items',
			ac_req: false,

			events: {
				keyup: 'autoComplete'
			},

			initialize: function () {
				var autocomplete = new bp.Views.ActivityInput(
					{
						type: 'text',
						id: 'activity-autocomplete',
						placeholder: this.options.placeholder || ''
					}
				).render();

				this.$el.html( autocomplete.$el );
				autocomplete.$el.wrapAll( '<span class="activity-autocomplete-wrapper" />' ).after( '<span class="activity-autocomplete-clear"><i class="bb-icons-rl-x"></i></span>' );
				this.$el.append( '<div id="bb-rl-activity-group-ac-items"></div>' );

				this.$activityGroupAcItems = this.$el.find( '#bb-rl-activity-group-ac-items' );

				this.on( 'ready', this.setFocus, this );
				if ( 'group' === this.options.type ) {
					var default_group_ac_list_item = bbRlActivity.params.objects.group_list;
					if ( default_group_ac_list_item ) {
						this.collection.add( default_group_ac_list_item );
						_.each(
							this.collection.models,
							function ( item ) {
								this.addItemView( item );
							},
							this
						);
					}

					var group_total_page = bbRlActivity.params.objects.group_total_page,
						group_count      = bbRlActivity.params.objects.group_count;
					if ( group_total_page > 1 && group_count > this.collection.models.length ) {
						var $this = this;
						this.$activityGroupAcItems.addClass( 'group_scrolling load_more_data' );
						var $scrollable = this.$activityGroupAcItems,
							currentPage = 1;
						$scrollable.on(
							'scroll',
							function () {
								window.acScrollPosition = $scrollable.scrollTop();
								if ( $this.$activityGroupAcItems.hasClass( 'load_more_data' ) ) {
									currentPage++;
									if ( currentPage > group_total_page ) {
										$this.$activityGroupAcItems.removeClass( 'load_more_data' );
										currentPage = 1;
										return false;
									} else {
										$this.loadMoreData( $this, currentPage );
									}
								}
							}
						);
					}
				}
				this.collection.on( 'add', this.addItemView, this );
				this.collection.on( 'reset', this.cleanView, this );
			},

			setFocus: function () {
				this.$el.find( '#activity-autocomplete' ).focus();
				// After select any group it will scroll to a particular selected group.
				if ( $( '#bb-rl-activity-group-ac-items .bb-rl-activity-object' ).length ) {
					var activityGroupAcItems = $( '#bb-rl-activity-group-ac-items' );
					$( '.bb-rl-activity-object' ).each(
						function () {
							if ( $( this ).hasClass( 'selected' ) ) {
									activityGroupAcItems.scrollTop( window.acScrollPosition );
									activityGroupAcItems.on(
										'scroll',
										function () {
											window.acScrollPosition = $( this ).scrollTop();
										}
									);
							}
						}
					);
				}
			},

			addItemView: function ( item ) {
				var group_ac_list_item = new bp.Views.Item( { model: item } );
				this.$activityGroupAcItems.append( group_ac_list_item.render().$el );
			},

			autoComplete: function () {
				var $this          = this,
					search         = $( '#activity-autocomplete' ).val(),
					whats_new_form = $this.$el.closest( '#bb-rl-whats-new-form' );

				if ( 0 === parseInt( search.length ) ) {
					this.autoCompleteCollectionData( $this, search );
					$this.$activityGroupAcItems.addClass( 'load_more_data' );
					$this.$el.removeClass( 'activity-is-autocomplete' );

					// Disable the privacy status submit button if groups search filter is cleared.
					whats_new_form.addClass( 'bb-rl-focus-in--blank-group' );
				} else {
					$this.$el.addClass( 'activity-is-autocomplete' );

					$( '#bb-rl-whats-new-post-in-box-items .activity-autocomplete-clear' ).on(
						'click',
						function () {
							$( '#activity-autocomplete' ).val( '' ).keyup();

							// Disable the privacy status submit button if groups search filter is cleared.
							whats_new_form.addClass( 'bb-rl-focus-in--blank-group' );
						}
					);
				}

				if ( 2 > search.length ) {
					return;
				}

				this.autoCompleteCollectionData( $this, search );
			},

			autoCompleteCollectionData: function ( $this, search ) {
				// Reset the collection before starting a new search.
				this.collection.reset();

				if ( this.ac_req ) {
					this.ac_req.abort();
				}

				var $elem = this.$activityGroupAcItems;
				if ( 'group' === this.options.type ) {
					$elem.html( '<div class="groups-selection groups-selection--finding"><i class="bb-rl-loader"></i><span class="groups-selection__label">' + bbRlActivity.params.objects.group.finding_group_placeholder + '</span></div>' );
					$elem.addClass( 'group_scrolling--revive' );
				} else {
					$elem.html( '<i class="dashicons dashicons-update animate-spin"></i>' );
				}

				var attrData = {
					type: this.options.type,
					nonce: bbRlNonce.activity
				};
				if ( '' !== search ) {
					attrData.search = search;
				}

				this.ac_req = this.collection.fetch(
					{
						data: attrData,
						success: _.bind( this.itemFetched, this, $this.options.type ),
						error: _.bind( this.itemFetched, this, $this.options.type ),
					}
				);
			},

			itemFetched: function ( optionType, items ) {
				if ( ! items.length ) {
					this.cleanView( optionType );
				}
				var $elem = this.$activityGroupAcItems;
				if ( 'group' === optionType ) {
					$elem.find( '.groups-selection--finding' ).remove();
					$elem.removeClass( 'group_scrolling--revive' );
				} else {
					$elem.find( 'i.dashicons' ).remove();
				}
			},

			cleanView: function ( optionType ) {
				var $elem = this.$activityGroupAcItems;
				if ( 'group' === optionType ) {
					$elem.html( '<span class="groups-selection groups-selection--no-groups">' + bbRlActivity.params.objects.group.no_groups_found + '</span>' );
				} else {
					$elem.html( '' );
				}
				_.each(
					this.views._views[''],
					function ( view ) {
						view.remove();
					}
				);
			},

			loadMoreData: function ( $this, currentPage ) {
				if ( ! this.$el.find( '#bb-rl-activity-group-ac-items .groups-selection--loading' ).length ) {
					this.$el.find( '#bb-rl-activity-group-ac-items .bb-rl-activity-object:last' ).after( '<div class="groups-selection groups-selection--loading"><i class="bb-rl-loader"></i><span class="groups-selection__label">' + bbRlActivity.params.objects.group.loading_group_placeholder + '</span></div>' );
				}
				var checkSucessData = false,
					fetchGroup      = new bp.Collections.fetchCollection();
				fetchGroup.fetch(
					{
						type: 'POST',
						data: {
							type: $this.options.type,
							nonce: bbRlNonce.activity,
							page: currentPage,
							action: 'bp_nouveau_get_activity_objects'
						},
						success: function ( collection, object ) {
							if ( true === object.success ) {
								$this.collection.add( object.data );
								$( '#bb-rl-activity-group-ac-items .groups-selection--loading' ).remove();
								checkSucessData = true;
							}
						},
					}
				);
				return checkSucessData;
			}
		}
	);

	bp.Views.UserStatusHuddle = bp.View.extend(
		{
			tagName: 'div',
			id: 'bb-rl-user-status-huddle',
			className: 'bp-activity-huddle',

			initialize: function () {
				this.views.add( new bp.Views.CaseAvatar( { model: this.model } ) );
				this.views.add( new bp.Views.CaseHeading( { model: this.model } ) );
				this.views.add( new bp.Views.CasePrivacy( { model: this.model } ) );

				$( '#bb-rl-whats-new-heading, #bb-rl-whats-new-status' ).wrapAll( '<div class="bb-rl-activity-post-name-status" />' );
				setTimeout(
					function () {
						$( '.activity-singular #bb-rl-whats-new-heading, .activity-singular #bb-rl-whats-new-status, .activity-singular #activity-schedule-section' ).wrapAll( '<div class="bb-rl-activity-post-name-status" />' );
					},
					1000
				);
			},
		}
	);

	bp.Views.CaseAvatar = bp.View.extend(
		{
			tagName: 'div',
			id: 'bb-rl-whats-new-avatar',
			template: bp.template( 'activity-post-case-avatar' ),

			initialize: function () {
				this.model = new Backbone.Model(
					_.pick(
						bbRlActivity.params,
						[
							'user_id',
							'avatar_url',
							'avatar_width',
							'avatar_height',
							'avatar_alt',
							'user_domain',
							'user_display_name'
						]
					)
				);

				if ( this.model.has( 'avatar_url' ) ) {
					this.model.set( 'display_avatar', true );
				}
			}
		}
	);

	bp.Views.CaseHeading = bp.View.extend(
		{
			tagName: 'div',
			id: 'bb-rl-whats-new-heading',
			template: bp.template( 'activity-post-case-heading' ),

			initialize: function () {
				this.model = new Backbone.Model(
					_.pick(
						bbRlActivity.params,
						[
							'user_id',
							'avatar_url',
							'avatar_width',
							'avatar_height',
							'avatar_alt',
							'user_domain',
							'user_display_name'
						]
					)
				);

				if ( this.model.has( 'avatar_url' ) ) {
					this.model.set( 'display_avatar', true );
				}
			}
		}
	);

	bp.Views.CasePrivacy = bp.View.extend(
		{
			tagName: 'div',
			id: 'bb-rl-whats-new-status',
			template: bp.template( 'activity-post-case-privacy' ),
			events: {
				'click #bb-rl-activity-privacy-point': 'privacyTarget'
			},

			initialize: function () {
				this.listenTo( Backbone, 'privacy:updatestatus', this.updateStatus );
				this.model.on( 'change:privacy', this.render, this );
			},

			render: function () {
				this.$el.html( this.template( this.model.toJSON() ) );
				var whats_new_form = $( '#bb-rl-whats-new-form' );

				if ( ! _.isUndefined( bbRlActivity.params.object ) && 'group' === bbRlActivity.params.object && 'group' === bbRlActivity.params.object ) {
					this.model.set(
						{
							item_name : bbRlActivity.params.item_name,
							privacy   : 'group'
						}
					);

					var group_name = bbRlActivity.params.item_name;
					whats_new_form.find( '.bb-rl-activity-privacy-status' ).text( group_name );

					this.$el.find( '#bb-rl-activity-privacy-point' ).removeClass().addClass( 'group bp-activity-focus-group-active' );
					// Display image of the group.
					if ( bbRlActivity.params.group_avatar && false === bbRlActivity.params.group_avatar.includes( 'mystery-group' ) ) {
						this.$el.find( '#bb-rl-activity-privacy-point span.bb-rl-bb-rl-privacy-point-icon' ).removeClass( 'bb-rl-privacy-point-icon' ).addClass( 'group-bb-rl-privacy-point-icon' ).html( '<img src="' + bbRlActivity.params.group_avatar + '" alt=""/>' );
					} else {
						this.$el.find( '#bb-rl-activity-privacy-point span.group-bb-rl-privacy-point-icon img' ).remove();
						this.$el.find( '#bb-rl-activity-privacy-point span.group-bb-rl-privacy-point-icon' ).removeClass( 'group-bb-rl-privacy-point-icon' ).addClass( 'bb-rl-privacy-point-icon' );
					}

					bp.draft_activity.data.item_id            = bbRlActivity.params.item_id;
					bp.draft_activity.data.group_name         = bbRlActivity.params.item_name;
					bp.draft_activity.data.group_image        = bbRlActivity.params.group_avatar;
					bp.draft_activity.data.item_name          = bbRlActivity.params.item_name;
					bp.draft_activity.data.privacy            = 'group';
					bp.draft_activity.data[ 'group-privacy' ] = 'bb-rl-item-opt-' + bbRlActivity.params.item_id;

					localStorage.setItem( bp.draft_activity.data_key, JSON.stringify( bp.draft_activity ) );
				}

				if ( ! _.isUndefined( bp.draft_activity ) && '' !== bp.draft_activity.object && 'group' === bp.draft_activity.object && bp.draft_activity.data && '' !== bp.draft_activity.data ) {
					this.model.set(
						{
							item_name : bp.draft_activity.data.item_name,
							privacy   : 'group'
						}
					);

					whats_new_form.find( '.bb-rl-activity-privacy-status' ).text( bp.draft_activity.data.item_name );

					this.$el.find( '#bb-rl-activity-privacy-point' ).removeClass().addClass( 'group bp-activity-focus-group-active' );
					// display image of the group.
					if ( bp.draft_activity.data.group_image && false === bp.draft_activity.data.group_image.includes( 'mystery-group' ) ) {
						this.$el.find( '#bb-rl-activity-privacy-point span.bb-rl-privacy-point-icon' ).removeClass( 'bb-rl-privacy-point-icon' ).addClass( 'group-bb-rl-privacy-point-icon' ).html( '<img src="' + bp.draft_activity.data.group_image + '" alt=""/>' );
					} else {
						this.$el.find( '#bb-rl-activity-privacy-point span.group-bb-rl-privacy-point-icon img' ).remove();
						this.$el.find( '#bb-rl-activity-privacy-point span.group-bb-rl-privacy-point-icon' ).removeClass( 'group-bb-rl-privacy-point-icon' ).addClass( 'bb-rl-privacy-point-icon' );
					}
				}

				return this;
			},

			updateStatus: function () {
				this.model.get( 'privacy' );
			},

			privacyTarget: function ( e ) {
				if ( this.$el.find( '#bb-rl-activity-privacy-point' ).hasClass( 'bb-rl-activity-edit-group' ) || ( ! _.isUndefined( bbRlActivity.params.object ) && 'group' === bbRlActivity.params.object ) || ! bp.privacyEditable ) {
					return false;
				}
				e.preventDefault();
				var whats_new_form = $( '#bb-rl-whats-new-form' );
				if ( whats_new_form.hasClass( 'bb-rl-focus-in--privacy' ) ) {
					whats_new_form.removeClass( 'bb-rl-focus-in--privacy' );
					$( '#bb-rl-activity-post-form-privacy' ).hide();
				} else {
					$( '#bb-rl-activity-post-form-privacy' ).show();
					whats_new_form.addClass( 'bb-rl-focus-in--privacy' );
					if ( whats_new_form.hasClass( 'bb-rl-activity-edit' ) ) {
						this.model.set( 'privacy', this.$el.closest( '#bb-rl-whats-new-form' ).find( '.bb-rl-activity-privacy__input:checked' ).val() );
					}

					if( whats_new_form.hasClass( 'bb-rl-focus-in--privacy' ) ) {
						$( '.bb-rl-activity-privacy-stage' ).css( 'margin-top', '-' + $( '.bb-rl-whats-new-scroll-view' ).scrollTop() + 'px' );
					}
				}
			}
		}
	);

	bp.Views.PrivacyStage = bp.View.extend(
		{
			tagName: 'div',
			id: 'bb-rl-whats-new-privacy-stage',
			className: 'bb-rl-activity-privacy-stage',
			events: {
				'click #bb-rl-privacy-status-submit': 'privacyStatusSubmit',
				'click #bb-rl-privacy-status-back': 'backPrivacySelector',
				'click #bb-rl-privacy-status-group-back': 'backGroupSelector',
				'click #bb-rl-whats-new-post-in-box-header .bb-rl-model-close-button': 'backGroupSelector',
				'click input.bb-rl-activity-privacy__input': 'privacySelector'
			},

			initialize: function () {
				if ( ( ! _.isUndefined( bbRlActivity.params.objects ) && 1 < _.keys( bbRlActivity.params.objects ).length ) || ( ! _.isUndefined( bbRlActivity.params.object ) && 'user' === bbRlActivity.params.object ) ) {
					var privacy_body = new bp.Views.PrivacyStageBody( { model: this.model } );
					this.views.add( privacy_body );

					if ( this.$el.find( '.bb-rl-whats-new-post-in-box--overlay' ).length === 0 ) {
						this.$el.find( '.bb-rl-privacy-status-form-body' ).append( '<div class="bb-rl-whats-new-post-in-box--overlay"></div>' );
					}
				}

				this.views.add( new bp.Views.PrivacyStageFooter( { model: this.model } ) );
			},

			privacyStatusSubmit: function ( e ) {
				e.preventDefault();

				var selected_privacy = this.$el.find( '.bb-rl-activity-privacy__input:checked' ).val();
				this.model.set(
					{
						privacy       : selected_privacy,
						privacy_modal : 'general'
					}
				);

				if ( ! _.isUndefined( bbRlMedia ) ) {
					bp.Nouveau.Activity.postForm.postGifProfile = new bp.Views.PostGifProfile( { model: this.model } );
				}

				var whats_new_form = $( '#bb-rl-whats-new-form' );
				whats_new_form.removeClass( 'bb-rl-focus-in--privacy bb-rl-focus-in--group' );

				Backbone.trigger( 'privacy:updatestatus' );

				var group_item_id = this.model.attributes.item_id;
				if ( 'group' === selected_privacy ) {
					var group_name = whats_new_form.find( '#bb-rl-item-opt-' + group_item_id ).data( 'title' );
					whats_new_form.find( '.bb-rl-activity-privacy-status' ).text( group_name );
					whats_new_form.find( '#bb-rl-activity-privacy-point' ).removeClass().addClass( selected_privacy );
					this.model.set(
						{
							item_name  : group_name,
							group_name : group_name
						}
					);
					// display image of the group.
					if ( this.model.attributes.group_image && false === this.model.attributes.group_image.includes( 'mystery-group' ) ) {
						whats_new_form.find( '#bb-rl-activity-privacy-point span.bb-rl-privacy-point-icon' ).removeClass( 'bb-rl-privacy-point-icon' ).addClass( 'group-bb-rl-privacy-point-icon' );
						whats_new_form.find( '#bb-rl-activity-privacy-point span.group-bb-rl-privacy-point-icon' ).html( '<img src="' + this.model.attributes.group_image + '" alt=""/>' );
					} else {
						whats_new_form.find( '#bb-rl-activity-privacy-point span.group-bb-rl-privacy-point-icon img' ).remove();
						whats_new_form.find( '#bb-rl-activity-privacy-point span.group-bb-rl-privacy-point-icon' ).removeClass( 'group-bb-rl-privacy-point-icon' ).addClass( 'bb-rl-privacy-point-icon' );
					}
					if ( ! _.isUndefined( bbRlMedia ) ) {
						bp.Nouveau.Activity.postForm.postGifGroup = new bp.Views.PostGifGroup( { model: this.model } );
					}

					// Check schedule post is allowed in this group or not.
					var schedule_allowed = whats_new_form.find( '#bb-rl-item-opt-' + group_item_id ).data( 'allow-schedule-post' );
					if ( ! _.isUndefined( schedule_allowed ) && 'enabled' === schedule_allowed ) {
						this.model.set( 'schedule_allowed', schedule_allowed );
						whats_new_form.find( '.bb-schedule-post_dropdown_section' ).removeClass( 'bp-hide' );
						Backbone.trigger( 'cleanFeedBack' );
					} else if ( 'scheduled' === this.model.attributes.activity_action_type ) {
						// Reset the schedule data.
						this.model.set(
							{
								activity_action_type       : null,
								activity_schedule_date_raw : null,
								activity_schedule_date     : null,
								activity_schedule_time     : null,
								activity_schedule_meridiem : null,
								schedule_allowed           : 'disabled'
							}
						);
						whats_new_form.find( '.bb-schedule-post_dropdown_section' ).addClass( 'bp-hide' );

						// Show a Warning message.
						Backbone.trigger( 'onError', bbRlIsActivitySchedule.strings.notAllowScheduleWarning, 'error' );
					} else {
						this.model.set( 'schedule_allowed', 'disabled' );
						whats_new_form.find( '.bb-schedule-post_dropdown_section' ).addClass( 'bp-hide' );
					}

					// Check a poll is allowed in this group or not.
					var polls_allowed = whats_new_form.find( '#bb-rl-item-opt-' + group_item_id ).data( 'allow-polls' );
					if ( ! _.isUndefined( polls_allowed ) && 'enabled' === polls_allowed ) {
						this.model.set( 'polls_allowed', polls_allowed );
						whats_new_form.find( '.bb-post-poll-button' ).removeClass( 'bp-hide' );
						Backbone.trigger( 'cleanFeedBack' );
					} else {
						this.model.set(
							{
								polls_allowed : 'disabled',
								poll          : {},
								poll_id       : '',
							}
						);
						whats_new_form.find( '.bb-post-poll-button' ).addClass( 'bp-hide' );
					}

					// Render topic selector for the group activity.
					if (
						! _.isUndefined( BP_Nouveau.activity.params.topics ) &&
						! _.isUndefined( BP_Nouveau.activity.params.topics.bb_is_enabled_activity_topics ) &&
						BP_Nouveau.activity.params.topics.bb_is_enabled_activity_topics
					) {
						if (
							this.model.get( 'topics' ) &&
							this.model.get( 'topics' ).topic_lists
						) {
							if (
								!_.isUndefined( BP_Nouveau.activity.params.topics.bb_is_enabled_group_activity_topics ) &&
								BP_Nouveau.activity.params.topics.bb_is_enabled_group_activity_topics
							) {
								Backbone.trigger( 'topic:update', this.model.get( 'topics' ) );
							} else {
								Backbone.trigger( 'topic:update', {} );
							}
						}
					}
				} else {

					// Clear schedule post data when change privacy.
					if (
						! _.isUndefined( bbRlIsActivitySchedule ) &&
						! _.isUndefined( bbRlIsActivitySchedule.params.can_schedule_in_feed ) &&
						true === bbRlIsActivitySchedule.params.can_schedule_in_feed
					) {
						whats_new_form.find( '.bb-schedule-post_dropdown_section' ).removeClass( 'bp-hide' );
					} else {
						// Reset the schedule data.
						this.model.set(
							{
								activity_action_type       : null,
								activity_schedule_date_raw : null,
								activity_schedule_date     : null,
								activity_schedule_time     : null,
								activity_schedule_meridiem : null,
								schedule_allowed           : 'disabled'
							}
						);
						whats_new_form.find( '.bb-schedule-post_dropdown_section' ).addClass( 'bp-hide' );
					}

					// Clear poll data when change privacy.
					if (
						! _.isUndefined( bbRlIsActivityPolls ) &&
						! _.isUndefined( bbRlIsActivityPolls.params.can_create_poll_activity ) &&
						true === bbRlIsActivityPolls.params.can_create_poll_activity
					) {
						whats_new_form.find( '.bb-post-poll-button' ).removeClass( 'bp-hide' );
					} else {
						this.model.set(
							{
								polls_allowed : 'disabled',
								poll          : {},
								poll_id       : '',
							}
						);
						whats_new_form.find( '.bb-post-poll-button' ).addClass( 'bp-hide' );
					}

					Backbone.trigger( 'cleanFeedBack' );

					var privacy       = this.model.attributes.privacy,
						privacy_label = whats_new_form.find( '#' + privacy ).data( 'title' );
					whats_new_form.find( '#bb-rl-activity-privacy-point' ).removeClass().addClass( privacy );
					whats_new_form.find( '.bb-rl-activity-privacy-status' ).text( privacy_label );
					whats_new_form.find( '.bb-rl-activity-privacy__input#' + privacy ).prop( 'checked', true );

					whats_new_form.find( '#bb-rl-activity-privacy-point span.group-bb-rl-privacy-point-icon img' ).remove();
					whats_new_form.find( '#bb-rl-activity-privacy-point span.group-bb-rl-privacy-point-icon' ).removeClass( 'group-bb-rl-privacy-point-icon' ).addClass( 'bb-rl-privacy-point-icon' );

					this.model.set(
						{
							item_id       : 0,
							item_name     : '',
							group_name    : '',
							group_image   : '',
							group_privacy : ''
						}
					);

					// Set topic lists for the global activity.
					if (
						! _.isUndefined( BP_Nouveau.activity.params.topics ) &&
						! _.isUndefined( BP_Nouveau.activity.params.topics.bb_is_enabled_activity_topics ) &&
						BP_Nouveau.activity.params.topics.bb_is_enabled_activity_topics &&
						! _.isUndefined( BP_Nouveau.activity.params.topics.topic_lists )
					) {
						var topic_lists = BP_Nouveau.activity.params.topics.topic_lists;

						var topicData = {
							topic_lists : topic_lists,
						};
						if (
							whats_new_form.find( '.bb-rl-topic-selector-list li a.selected' ).length > 0 &&
							whats_new_form.find( '.bb-rl-topic-selector-list li a.selected' ).data( 'topic-id' )
						) {
							topicData.topic_id   = whats_new_form.find( '.bb-rl-topic-selector-list li a.selected' ).data( 'topic-id' );
							topicData.topic_name = $.trim( whats_new_form.find( '.bb-rl-topic-selector-list li a.selected' ).html() );
						}
						if ( ! topicData.topic_id && this.model.get( 'topics' ) && this.model.get( 'topics' ).topic_id ) {
							topicData.topic_id = this.model.get( 'topics' ).topic_id;
						}
						if ( ! topicData.topic_name && this.model.get( 'topics' ) && this.model.get( 'topics' ).topic_name ) {
							topicData.topic_name = this.model.get( 'topics' ).topic_name;
						}
						this.model.set( 'topics', topicData );
						
						if ( topic_lists.length > 0 ) {
							$( '.whats-new-topic-selector' ).removeClass( 'bp-hide' );
						} else {
							$( '.whats-new-topic-selector' ).addClass( 'bp-hide' );
						}
						Backbone.trigger( 'topic:update', this.model.get( 'topics' ) );
					}

					bp.draft_activity.data.item_id            = 0;
					bp.draft_activity.data.group_name         = '';
					bp.draft_activity.data.group_image        = '';
					bp.draft_activity.data.item_name          = '';
					bp.draft_activity.data.privacy            = privacy;
					bp.draft_activity.data[ 'group-privacy' ] = '';

					localStorage.setItem( bp.draft_activity.data_key, JSON.stringify( bp.draft_activity ) );
				}
			},

			backPrivacySelector: function ( e ) {
				e.preventDefault();
				var privacyStatus = this.model.get( 'privacy' ),
					$whatsNewForm = $( '#bb-rl-whats-new-form' );
				$whatsNewForm.removeClass( 'bb-rl-focus-in--privacy bb-rl-focus-in--group' );
				this.model.set( 'privacy_modal', 'general' );
				this.$el.find( 'input#' + privacyStatus ).prop( 'checked', true );
				if ( $whatsNewForm.hasClass( 'bb-rl-activity-edit' ) ) {
					this.model.set( 'privacy', this.$el.find( '.bb-rl-activity-privacy__input:checked' ).val() );
				}
			},

			backGroupSelector: function ( e ) {
				e.preventDefault();
				var whats_new_form = $( '#bb-rl-whats-new-form' );
				this.model.set( 'privacy_modal', 'profile' );
				whats_new_form.removeClass( 'bb-rl-focus-in--group' );
				var privacyStatus = this.model.get( 'privacy' );
				this.$el.find( 'input#' + privacyStatus ).prop( 'checked', true );
				$( '#bb-rl-activity-post-form-privacy' ).show();

				// Enable save button.
				whats_new_form.removeClass( 'bb-rl-focus-in--blank-group' );
			},

			privacySelector: function ( e ) {
				var whats_new_form = $( '#bb-rl-whats-new-form' );
				if ( $( e.currentTarget ).val() === 'group' ) {
					$( e.currentTarget ).closest( '#bb-rl-whats-new-privacy-stage' ).find( '#bb-rl-whats-new-post-in' ).val( 'group' ).trigger( 'change' );
					whats_new_form.addClass( 'bb-rl-focus-in--group' );
					this.model.set( 'privacy_modal', 'group' );
					// First time when we open group selector and select any one group and close it.
					// and then back again on the same screen then object should be group to display the same view screen.
					this.model.set( 'object', $( e.currentTarget ).val() );

					// Disable save button if no group selected.
					if ( 0 === this.model.attributes.item_id ) {
						whats_new_form.addClass( 'bb-rl-focus-in--blank-group' );
					}
				} else {
					$( '#bb-rl-privacy-status-submit' ).click();
					this.model.set( 'object', 'user' );

					// Update multi media options dependent on profile/group view.
					Backbone.trigger( 'mediaprivacytoolbar' );
				}
			}
		}
	);

	bp.Views.PrivacyStageBody = bp.View.extend(
		{
			tagName: 'div',
			id: 'bb-rl-whats-new-privacy-stage-body',
			className: 'bb-rl-privacy-status-form-body',

			initialize: function () {
				// activity privacy options for profile.
				if ( ( ! _.isUndefined( bbRlActivity.params.objects ) && 1 < _.keys( bbRlActivity.params.objects ).length ) || ( ! _.isUndefined( bbRlActivity.params.object ) && 'user' === bbRlActivity.params.object ) ) {
					var privacy = new bp.Views.ActivityPrivacy( { model: this.model } );
					this.views.add( privacy );
				}

				if ( _.isUndefined( bbRlActivity.params.objects ) && 'user' === bbRlActivity.params.object ) {
					this.$el.find( '.bb-rl-activity-privacy__label-group' ).hide().find( 'input#group' ).attr( 'disabled', true ); // disable group visibility level.
				}

				// Select box for the object.
				if ( ! _.isUndefined( bbRlActivity.params.objects ) && 1 < _.keys( bbRlActivity.params.objects ).length && ( bp.Nouveau.Activity.postForm.editActivityData === false || _.isUndefined( bp.Nouveau.Activity.postForm.editActivityData ) ) ) {
					this.views.add( new bp.Views.FormTarget( { model: this.model } ) );

					// when editing activity, need to display which object is being edited.
				} else if ( bp.Nouveau.Activity.postForm.editActivityData !== false && ! _.isUndefined( bp.Nouveau.Activity.postForm.editActivityData ) ) {
					this.views.add( new bp.Views.EditActivityPostIn( { model: this.model } ) );
				}
			}
		}
	);

	bp.Views.PrivacyStageFooter = bp.View.extend(
		{
			tagName: 'div',
			id: 'bb-rl-whats-new-privacy-stage-footer',
			className: 'bb-rl-privacy-status-form-footer',
			template: bp.template( 'activity-post-privacy-stage-footer' )
		}
	);

	bp.Views.FormContent = bp.View.extend(
		{
			tagName: 'div',
			id: 'bb-rl-whats-new-content',
			events: {
				'click .medium-editor-toolbar-actions': 'focusEditor',
				'input #bb-rl-whats-new': 'focusEditorOnChange',
				'click .medium-editor-toolbar li.close-btn': 'hideToolbarSelector',
			},

			initialize: function () {
				this.$el.html( $( '<div></div>' ).prop( 'id', 'bb-rl-whats-new-textarea' ) );
				this.$el.append( '<input type="hidden" name="id" id="bb-rl-activity-id" value="0"/>' );
				this.views.set( '#bb-rl-whats-new-textarea', new bp.Views.WhatsNew( { activity: this.options.activity } ) );
			},

			hideToolbarSelector: function ( e ) {
				e.preventDefault();
				var medium_editor = $( e.currentTarget ).closest( '#bb-rl-whats-new-form' ).find( '.medium-editor-toolbar' );
				medium_editor.removeClass( 'active' );
			},

			focusEditor: function ( e ) {
				if ( window.activity_editor.exportSelection() === null ) {
					$( e.currentTarget ).closest( '#bb-rl-whats-new-form' ).find( '#bb-rl-whats-new-textarea > div' ).focus();
				}
				e.preventDefault();
			},
			focusEditorOnChange: function ( e ) {
				// Fix issue of Editor loose focus when formatting is opened after selecting text.
				var medium_editor = $( e.currentTarget ).closest( '#bb-rl-whats-new-form' ).find( '.medium-editor-toolbar' );
				setTimeout(
					function () {
						medium_editor.addClass( 'medium-editor-toolbar-active' );
						$( e.currentTarget ).closest( '#bb-rl-whats-new-form' ).find( '#bb-rl-whats-new-textarea > div' ).focus();
					},
					0
				);
			}
		}
	);

	bp.Views.FormOptions = bp.View.extend(
		{
			tagName: 'div',
			id: 'bb-rl-whats-new-options',
			template: bp.template( 'activity-post-form-options' )
		}
	);

	bp.Views.FormTargetHeader = bp.View.extend(
		{
			tagname: 'div',
			id: 'bb-rl-whats-new-post-in-box-header',
			className: 'bb-rl-whats-new-post-in-box-header',
			template: bp.template( 'activity-edit-postin-header' )
		}
	);

	bp.Views.FormTarget = bp.View.extend(
		{
			tagName: 'div',
			id: 'bb-rl-whats-new-post-in-box',
			className: 'in-profile',

			initialize: function () {
				var select = new bp.Views.WhatsNewPostIn( { filters: bbRlActivity.params.objects } );
				this.views.add( select );

				select.model.on( 'change', this.attachAutocomplete, this );
				bp.Nouveau.Activity.postForm.ActivityObjects.on( 'change:selected', this.postIn, this );

				this.toggleMultiMediaOptions();
			},

			attachAutocomplete: function ( model ) {
				if ( 0 !== bp.Nouveau.Activity.postForm.ActivityObjects.models.length ) {
					bp.Nouveau.Activity.postForm.ActivityObjects.reset();
				}

				// Clean up views.
				_.each(
					this.views._views[ '' ],
					function ( view ) {
						if ( ! _.isUndefined( view.collection ) ) {
							view.remove();
						}
					}
				);

				if ( 'profile' !== model.get( 'selected' ) ) {
					this.views.add(
						[
						new bp.Views.FormTargetHeader(),
						new bp.Views.AutoComplete(
							{
								collection: bp.Nouveau.Activity.postForm.ActivityObjects,
								type: model.get( 'selected' ),
								placeholder: model.get( 'placeholder' )
							}
						),
						new bp.Views.PrivacyStageFooter()
						]
					);

					// Set the object type.
					this.model.set( 'object', model.get( 'selected' ) );
				} else {
					this.model.set( { object: 'user', item_id: 0 } );
				}

				this.updateDisplay();
				this.toggleMultiMediaOptions();
			},

			postIn: function ( model ) {
				if ( _.isUndefined( model.get( 'id' ) ) ) {
					// Reset the item id.
					this.model.set( 'item_id', 0 );

					// When the model has been cleared, Attach Autocomplete!
					this.attachAutocomplete( new Backbone.Model( { selected: this.model.get( 'object' ) } ) );
					return;
				}

				// Set the item id for the selected object.
				this.model.set( 'item_id', model.get( 'id' ) );
				if ( 'group' === this.model.get( 'object' ) ) {
					this.views.remove( '#bb-rl-whats-new-post-in-box-items' );
					this.views.add(
						[
						new bp.Views.FormTargetHeader(),
						new bp.Views.AutoComplete(
							{
								collection: bp.Nouveau.Activity.postForm.ActivityObjects,
								type: this.model.get( 'object' ),
								placeholder: bbRlActivity.params.objects.group.autocomplete_placeholder,
							}
						),
						new bp.Views.PrivacyStageFooter()
						]
					);
					// Set the object type.
					this.model.set(
						{
							object      : this.model.get( 'object' ),
							group_name  : model.get( 'name' ),
							item_name   : model.get( 'name' ),
							group_image : model.get( 'avatar_url' ),
							group_url   : model.get( 'group_url' )
						}
					);

					// Set topic lists for the group activity.
					if (
						! _.isUndefined( BP_Nouveau.activity.params.topics ) &&
						! _.isUndefined( BP_Nouveau.activity.params.topics.bb_is_enabled_activity_topics ) &&
						BP_Nouveau.activity.params.topics.bb_is_enabled_activity_topics &&
						! _.isUndefined( BP_Nouveau.activity.params.topics.bb_is_enabled_group_activity_topics ) &&
						BP_Nouveau.activity.params.topics.bb_is_enabled_group_activity_topics
					) {
						var group_topic_lists = model.get( 'topics' );

						if ( group_topic_lists && group_topic_lists.topic_lists ) {
							group_topic_lists = group_topic_lists.topic_lists;
						} else {
							group_topic_lists = model.get( 'topic_lists' );
						}

						group_topic_lists = ! _.isUndefined( group_topic_lists ) ? group_topic_lists : [];

						this.model.set( 'topics', {
							topic_lists : group_topic_lists
						} );

						Backbone.trigger( 'topic:update', this.model.get( 'topics' ) );

						if ( group_topic_lists.length > 0 ) {
							$( '.whats-new-topic-selector' ).removeClass( 'bp-hide' );
						} else {
							$( '.whats-new-topic-selector' ).addClass( 'bp-hide' );
						}
					}
				} else {
					this.views.set('#bb-rl-whats-new-post-in-box-items', new bp.Views.Item({ model: model }));
					
					// Set topic lists for the global activity.
					if (
						! _.isUndefined( BP_Nouveau.activity.params.topics ) &&
						! _.isUndefined( BP_Nouveau.activity.params.topics.bb_is_enabled_activity_topics ) &&
						BP_Nouveau.activity.params.topics.bb_is_enabled_activity_topics &&
						! _.isUndefined( BP_Nouveau.activity.params.topics.topic_lists )
					) {
						var topic_lists = BP_Nouveau.activity.params.topics.topic_lists;
						topic_lists     = ! _.isUndefined( topic_lists ) ? topic_lists : [];

						this.model.set( 'topics', {
							topic_lists : topic_lists
						} );
						bp.draft_activity.data.topics = {
							topic_lists : topic_lists
						};

						Backbone.trigger( 'topic:update', topic_lists );
					}
				}
			},

			updateDisplay: function () {
				if ( 'user' !== this.model.get( 'object' ) ) {
					this.$el.removeClass();

				} else if ( ! this.$el.hasClass( 'in-profile' ) ) {
					this.$el.addClass( 'in-profile' );

					$( '#bb-rl-activity-post-form-privacy' ).show();
				}
			},

			toggleMultiMediaOptions: function () {
				if ( ! _.isUndefined( bbRlMedia ) ) {

					bp.mediaUtilities.handleMediaSupport(
						{
							triggerEvent  : true, // Trigger Backbone events.
							closeDropzone : false, // Close dropzone.
							dropzoneObj   : bp.Nouveau.Activity.postForm.dropzone // Dropzone object.
						},
						this.model
					);

					var $showToolbarButton = $( '#bb-rl-show-toolbar-button' );
					$showToolbarButton.removeClass( 'active' );
					$showToolbarButton.parent( '.bb-rl-show-toolbar' ).attr( 'data-bp-tooltip', $showToolbarButton.parent( '.bb-rl-show-toolbar' ).attr( 'data-bp-tooltip-show' ) );
				}
			}
		}
	);

	bp.Views.EditorToolbar = bp.View.extend(
		{
			tagName: 'div',
			id: 'bb-rl-editor-toolbar',
			template: bp.template( 'editor-toolbar' ),
			events: {
				'click .bb-rl-post-mention': 'triggerMention',
			},

			triggerMention: function ( e ) {
				e.preventDefault();
				var $this         = this.$el,
					editor        = $this.closest( '.bb-rl-activity-update-form' ).find( '#bb-rl-whats-new' ),
					scrollPosition = $this.closest( '.bb-rl-whats-new-scroll-view' ).scrollTop();

				setTimeout(
					function () {
						editor.focus();

						// Restore caret position start.
						if ( window.activityCaretPosition ) {
							if (window.getSelection && document.createRange) {
								var range = document.createRange();
								range.setStart( window.activityCaretPosition.startContainer, window.activityCaretPosition.startOffset );
								range.setEnd( window.activityCaretPosition.endContainer, window.activityCaretPosition.endOffset );
								var sel = window.getSelection();
								sel.removeAllRanges();
								sel.addRange( range );
							} else {
								var textRange = document.body.createTextRange();
								textRange.moveToElementText( editor[0] );
								textRange.setStart( window.activityCaretPosition.startContainer, window.activityCaretPosition.startOffset );
								textRange.setEnd( window.activityCaretPosition.endContainer, window.activityCaretPosition.endOffset );
								textRange.select();
							}
						}
						// Restore caret position end.

						// Get character before cursor start.
						var currentRange = window.getSelection().getRangeAt( 0 ).cloneRange();
						currentRange.collapse( true );
						currentRange.setStart( editor[0], 0 );
						var precedingChar = currentRange.toString().slice( -1 );
						// Get character before cursor end.

						if ( ! $( currentRange.endContainer.parentElement ).hasClass( 'atwho-inserted' ) ) { // Do nothing if mention '@' is already inserted.

							if ( precedingChar.trim() === '') { // Check if there's space or add one.
								document.execCommand( 'insertText', false, '@' );
							} else if ( precedingChar !== '@' ) {
								document.execCommand( 'insertText', false, ' @' );
							}

						}
						editor.trigger( 'keyup' );
						setTimeout(
							function () {
								editor.trigger( 'keyup' );
								$this.closest( '.bb-rl-whats-new-scroll-view' ).scrollTop( scrollPosition );
							},
							0
						);
					},
					0
				);

			},
		}
	);

	bp.Views.ActivityToolbar = bp.View.extend(
		{
			tagName: 'div',
			id: 'bb-rl-whats-new-toolbar',
			template: bp.template( 'whats-new-toolbar' ),
			events: {
				'click .bb-rl-post-elements-buttons-item.disable .bb-rl-toolbar-button': 'disabledButton',
				'click #activity-link-preview-button': 'toggleURLInput',
				'click #bb-rl-activity-gif-button': 'toggleGifSelector',
				'click #bb-rl-activity-media-button': 'toggleMediaSelector',
				'click #bb-rl-activity-document-button': 'toggleDocumentSelector',
				'click #bb-rl-activity-video-button': 'toggleVideoSelector',
				'click [class*="post-elements-buttons-item"]:not( .bb-rl-post-gif ):not( .bb-rl-post-media ):not( .bb-rl-post-video )': 'activeButton',
				'click .bb-rl-post-elements-buttons-item.bb-rl-post-gif:not(.disable)': 'activeMediaButton',
				'click .bb-rl-post-elements-buttons-item.bb-rl-post-media:not(.disable)': 'activeMediaButton',
				'click .bb-rl-post-elements-buttons-item.bb-rl-post-video:not(.disable)': 'activeVideoButton',
				'click .bb-rl-post-elements-buttons-item:not(.bb-rl-post-gif):not(.active)': 'scrollToMedia',
				'click .bb-rl-show-toolbar': 'toggleToolbarSelector',
			},

			toggleToolbarSelector: function ( e ) {
				e.preventDefault();
				var medium_editor = $( e.currentTarget ).closest( '#bb-rl-whats-new-form' ).find( '.medium-editor-toolbar' );

				if ( ! medium_editor.hasClass( 'active' ) ) { // Check only when opening toolbar.
					bp.Nouveau.mediumEditorButtonsWarp( medium_editor );
				}
				$( e.currentTarget ).find( '.bb-rl-toolbar-button' ).toggleClass( 'active' );
				if ( $( e.currentTarget ).find( '.bb-rl-toolbar-button' ).hasClass( 'active' ) ) {
					$( e.currentTarget ).attr( 'data-bp-tooltip', jQuery( e.currentTarget ).attr( 'data-bp-tooltip-hide' ) );
					if ( window.activity_editor.exportSelection() != null ) {
						medium_editor.addClass( 'medium-editor-toolbar-active' );
					}
				} else {
					$( e.currentTarget ).attr( 'data-bp-tooltip', jQuery( e.currentTarget ).attr( 'data-bp-tooltip-show' ) );
					if ( window.activity_editor.exportSelection() === null ) {
						medium_editor.removeClass( 'medium-editor-toolbar-active' );
					}
					medium_editor.find( 'li.medium-editor-action-more' ).removeClass( 'active' );
				}
				$( window.activity_editor.elements[0] ).focus();
				medium_editor.toggleClass( 'medium-editor-toolbar-active active' );
			},

			gifMediaSearchDropdownView: false,

			initialize: function () {
				document.addEventListener( 'keydown', _.bind( this.closePickersOnEsc, this ) );
				$( document ).on( 'click', _.bind( this.closePickersOnClick, this ) );
			},

			render: function () {
				this.$el.html( this.template( this.model.attributes ) );
				this.$self        = this.$el.find( '#bb-rl-activity-gif-button' );
				this.$gifPickerEl = this.$el.find( '.bb-rl-gif-media-search-dropdown' );
				this.$el.removeClass( 'hidden' );
				setTimeout(
					function () {
						var $thisEl = $( '.bb-rl-activity-form #bb-rl-whats-new-toolbar' );
						if ( $thisEl ) {
							if ( $thisEl.children( ':visible' ).length === 0 ) {
								$thisEl.addClass( 'hidden' );
							} else {
								$thisEl.removeClass( 'hidden' );
							}
						}
					},
					0
				);

				return this;
			},

			toggleURLInput: function ( e ) {
				var event;
				e.preventDefault();
				this.closeSelectors( ['media', 'gif', 'document', 'video'] );

				if ( this.model.get( 'link_scrapping' ) ) {
					event = new Event( 'activity_link_preview_close' );
				} else {
					event = new Event( 'activity_link_preview_open' );
				}
				document.dispatchEvent( event );
			},

			closeURLInput: function () {
				var event = new Event( 'activity_link_preview_close' );
				document.dispatchEvent( event );
			},

			toggleGifSelector: function ( e ) {
				e.preventDefault();

				var parentElement = $( e.currentTarget ).closest( '.bb-rl-post-elements-buttons-item' );
				if ( parentElement.hasClass( 'no-click' ) || parentElement.hasClass( 'disable' ) ) {
					return;
				}

				this.closeSelectors( ['media', 'document', 'video'] );

				if ( this.$gifPickerEl.is( ':empty' ) ) {
					this.gifMediaSearchDropdownView = new bp.Views.GifMediaSearchDropdown( { model: this.model } );
					this.$gifPickerEl.html( this.gifMediaSearchDropdownView.render().el );
				}

				var gif_box = $( e.currentTarget ).parents( '#bb-rl-whats-new-form' ).find( '#bb-rl-whats-new-attachments .bb-rl-activity-attached-gif-container' );
				if ( this.$self.hasClass( 'open' ) && gif_box.length && '' === $.trim( gif_box.html() ) ) {
					this.$self.removeClass( 'open' );
				} else {
					this.$self.addClass( 'open' );
				}
				if ( e.type !== 'bp_activity_edit' ) {
					this.$gifPickerEl.toggleClass( 'open' );
				}
			},

			toggleMediaSelector: function ( e ) {
				e.preventDefault();
				var parentElement = $( e.currentTarget ).closest( '.bb-rl-post-elements-buttons-item' );
				if ( ! $( '.bb-rl-activity-form' ).hasClass( 'bb-rl-focus-in' ) || parentElement.hasClass( 'no-click' ) || parentElement.hasClass( 'disable' ) ) {
					return;
				}

				this.closeSelectors( ['gif', 'document', 'video'] );

				Backbone.trigger( 'activity_media_toggle' );
			},

			toggleDocumentSelector: function ( e ) {
				e.preventDefault();

				var parentElement = $( e.currentTarget ).closest( '.bb-rl-post-elements-buttons-item' );
				if ( ! $( '.bb-rl-activity-form' ).hasClass( 'bb-rl-focus-in' ) || parentElement.hasClass( 'no-click' ) || parentElement.hasClass( 'disable' )) {
					return;
				}

				this.closeSelectors( ['gif', 'media', 'video'] );

				Backbone.trigger( 'activity_document_toggle' );
			},

			toggleVideoSelector: function ( e ) {
				e.preventDefault();
				var parentElement = $( e.currentTarget ).closest( '.bb-rl-post-elements-buttons-item' );
				if ( ! $( '.bb-rl-activity-form' ).hasClass( 'bb-rl-focus-in' ) || parentElement.hasClass( 'no-click' ) || parentElement.hasClass( 'disable' )) {
					return;
				}
				this.closeSelectors( ['media', 'document', 'gif'] );

				Backbone.trigger( 'activity_video_toggle' );
			},

			closeSelectors : function ( args ) {
				if ( ! args ) {
					return false;
				}
				args.forEach(
					function ( arg ) {
						Backbone.trigger( 'activity_' + arg + '_close' );
					}
				);
			},

			closePickersOnEsc: function ( event ) {
				if ( event.key === 'Escape' || event.keyCode === 27 ) {
					if ( ! _.isUndefined( bbRlMedia ) && ! _.isUndefined( bbRlMedia.gif_api_key ) ) {
						this.$self.removeClass( 'open' );
						this.$gifPickerEl.removeClass( 'open' );
					}
				}
			},

			closePickersOnClick: function ( event ) {
				var $targetEl = $( event.target );

				if ( ! _.isUndefined( bbRlMedia ) && ! _.isUndefined( bbRlMedia.gif_api_key ) &&
					! $targetEl.closest( '.bb-rl-post-gif' ).length ) {

					var gif_box = $targetEl.parents( 'form' ).find( '#bb-rl-whats-new-attachments .bb-rl-activity-attached-gif-container' );
					if ( gif_box.length && $.trim( gif_box.html() ) !== '' ) {
						this.$self.addClass( 'open' );
					} else {
						$( '.bb-rl-post-gif' ).removeClass( 'active' );
					}

					this.$gifPickerEl.removeClass( 'open' );
				}

			},

			activeButton: function ( event ) {
				var $buttonItems = this.$el.find( '.bb-rl-post-elements-buttons-item:not( .bb-rl-post-gif ):not( .bb-rl-post-media ):not( .bb-rl-post-video ):not( .bb-rl-show-toolbar )' );

				if ( $( event.currentTarget ).hasClass( 'active' ) ) {
					$buttonItems.removeClass( 'active' );
				} else {
					$buttonItems.removeClass( 'active' );
					event.currentTarget.classList.add( 'active' );
				}

				var gif_box = $( event.currentTarget ).parents( '#bb-rl-whats-new-form' ).find( '#bb-rl-whats-new-attachments .bb-rl-activity-attached-gif-container' );
				if ( gif_box.length && '' === $.trim( gif_box.html() ) ) {
					this.$self.removeClass( 'open' );
				}
			},

			activeMediaButton: function ( event ) {
				var $mediaButtons = this.$el.find( '.bb-rl-post-elements-buttons-item.bb-rl-post-gif, .bb-rl-post-elements-buttons-item.bb-rl-post-media, .bb-rl-post-elements-buttons-item.bb-rl-post-video' );

				if ( $( event.currentTarget ).hasClass( 'active' ) ) {
					$mediaButtons.removeClass( 'active' );
				} else {
					$mediaButtons.removeClass( 'active' );
					event.currentTarget.classList.add( 'active' );
				}
			},

			activeVideoButton: function ( event ) {
				this.$el.find( '.bb-rl-post-elements-buttons-item.bb-rl-post-gif, .bb-rl-post-elements-buttons-item.bb-rl-post-media' ).removeClass( 'active' );

				if ( $( event.currentTarget ).hasClass( 'active' ) ) {
					event.currentTarget.classList.remove( 'active' );
				} else {
					event.currentTarget.classList.add( 'active' );
				}
			},

			disabledButton: function () {
				Backbone.trigger( 'onError', bbRlActivity.params.errors.media_fail, 'info noMediaError' );
			},

			scrollToMedia: function () {
				var whatNewForm   = this.$el.closest( '#bb-rl-whats-new-form' );
				var whatNewScroll = whatNewForm.find( '.bb-rl-whats-new-scroll-view' );

				whatNewScroll.stop().animate(
					{
						scrollTop: whatNewScroll[0].scrollHeight
					},
					300
				);
			}
		}
	);

	bp.Views.ActivityAttachments = bp.View.extend(
		{
			tagName: 'div',
			id: 'bb-rl-whats-new-attachments',
			activityLinkPreview: null,
			activityAttachedGifPreview: null,
			activityMedia: null,
			activityDocument: null,
			activityVideo: null,
			className: 'empty',
			initialize: function () {
				if ( ! _.isUndefined( bbRlActivity.params.link_preview ) ) {
					this.activityLinkPreview = new bp.Views.ActivityLinkPreview( { model: this.model } );
					this.views.add( this.activityLinkPreview );
				}

				if (!_.isUndefined(bp.Views.activityPollView)) {
					var pollViewTemplate = document.getElementById( 'tmpl-bb-activity-poll-view' );
					if ( pollViewTemplate ) {
						this.activityPollView = new bp.Views.activityPollView( { model: this.model }) ;
						this.views.add(this.activityPollView);
					}
				}

				if ( ! _.isUndefined( window.Dropzone ) ) {
					this.activityMedia = new bp.Views.ActivityMedia( { model: this.model } );
					this.views.add( this.activityMedia );

					this.activityDocument = new bp.Views.ActivityDocument( { model: this.model } );
					this.views.add( this.activityDocument );

					this.activityVideo = new bp.Views.ActivityVideo( { model: this.model } );
					this.views.add( this.activityVideo );
				}

				this.activityAttachedGifPreview = new bp.Views.ActivityAttachedGifPreview( { model: this.model } );
				this.views.add( this.activityAttachedGifPreview );
			},
			onClose: function () {
				if ( bp.draft_activity.data ) {
					bp.draft_activity.allow_delete_media = false;
					bp.draft_activity.display_post       = '';
				}
				if ( ! _.isNull( this.activityLinkPreview ) ) {
					this.activityLinkPreview.destroy();
				}
				if ( ! _.isNull( this.activityAttachedGifPreview ) ) {
					this.activityAttachedGifPreview.destroy();
				}
				if ( ! _.isNull( this.activityMedia ) ) {
					this.activityMedia.destroy();
				}
				if ( ! _.isNull( this.activityDocument ) ) {
					this.activityDocument.destroyDocument();
				}
				if ( ! _.isNull( this.activityVideo ) ) {
					this.activityVideo.destroyVideo();
				}
			}
		}
	);

	/**
	 * Now build the buttons!
	 *
	 * @type {[type]}
	 */
	bp.Views.FormButtons = bp.View.extend(
		{
			tagName: 'div',
			id: 'whats-new-actions',

			initialize: function () {
				this.views.add( new bp.View( { tagName: 'ul', id: 'bb-rl-whats-new-buttons' } ) );

				_.each(
					this.collection.models,
					function ( button ) {
						this.addItemView( button );
					},
					this
				);

				this.collection.on( 'change:active', this.isActive, this );
			},

			addItemView: function ( button ) {
				this.views.add( '#bb-rl-whats-new-buttons', new bp.Views.FormButton( { model: button } ) );
			},

			isActive: function ( button ) {
				// Clean up views.
				_.each(
					this.views._views[ '' ],
					function ( view, index ) {
						if ( 0 !== index ) {
							view.remove();
						}
					}
				);

				// Then loop threw all buttons to update their status.
				if ( true === button.get( 'active' ) ) {
					_.each(
						this.views._views[ '#bb-rl-whats-new-buttons' ],
						function ( view ) {
							if ( view.model.get( 'id' ) !== button.get( 'id' ) ) {
								// Silently update the model.
								view.model.set( 'active', false, { silent: true } );

								// Remove the active class.
								view.$el.removeClass( 'active' );

								// Trigger an even to let Buttons reset.
								// their modifications to the activity model.
								this.collection.trigger( 'reset:' + view.model.get( 'id' ), this.model );
							}
						},
						this
					);

					// Tell the active Button to load its content.
					this.collection.trigger( 'display:' + button.get( 'id' ), this );

					// Trigger an even to let Buttons reset
					// their modifications to the activity model.
				} else {
					this.collection.trigger( 'reset:' + button.get( 'id' ), this.model );
				}
			}
		}
	);

	bp.Views.FormButton = bp.View.extend(
		{
			tagName: 'li',
			className: 'bb-rl-whats-new-buttons',
			template: bp.template( 'activity-post-form-buttons' ),

			events: {
				click: 'setActive'
			},

			setActive: function ( event ) {
				var isActive = this.model.get( 'active' ) || false;

				// Stop event propagation.
				event.preventDefault();

				if ( false === isActive ) {
					this.$el.addClass( 'active' );
					this.model.set( 'active', true );
				} else {
					this.$el.removeClass( 'active' );
					this.model.set( 'active', false );
				}
			}
		}
	);

	bp.Views.FormSubmit = bp.View.extend(
		{
			tagName: 'div',
			id: 'whats-new-submit',
			className: 'in-profile',

			initialize: function () {
				this.reset = new bp.Views.ActivityInput(
					{
						type: 'reset',
						id: 'bb-rl-aw-whats-new-reset',
						className: 'bb-rl-button bb-rl-button--secondaryFill',
						value: bbRlActivity.strings.cancelButton
					}
				);

				var buttomText = bbRlActivity.strings.postUpdateButton;
				if ( $( '#bb-rl-whats-new-form' ).hasClass( 'bb-rl-activity-edit' ) ) {
					buttomText = bbRlActivity.strings.updatePostButton;
				}

				if ( 'scheduled' === this.model.get( 'activity_action_type' ) || 'scheduled' === this.model.get( 'activity_status' ) ) {
					buttomText = bbRlActivity.strings.updatePostButton;
				}

				this.submit = new bp.Views.ActivityInput(
					{
						model: this.model,
						type: 'submit',
						id: 'aw-whats-new-submit',
						className: 'bb-rl-button bb-rl-button--brandFill',
						name: 'aw-whats-new-submit',
						value: buttomText
					}
				);

				this.views.set( [ this.submit, this.reset ] );

				this.model.on( 'change:object', this.updateDisplay, this );
				this.model.on( 'change:posting', this.updateStatus, this );
				this.model.on( 'change:activity_action_type', this.updateSubmitLabel, this );
			},

			updateDisplay: function ( model ) {
				if ( _.isUndefined( model ) ) {
					return;
				}

				if ( 'user' !== model.get( 'object' ) ) {
					this.$el.removeClass( 'in-profile' );
				} else if ( ! this.$el.hasClass( 'in-profile' ) ) {
					this.$el.addClass( 'in-profile' );
				}
			},

			updateStatus: function ( model ) {
				if ( _.isUndefined( model ) ) {
					return;
				}

				if ( model.get( 'posting' ) ) {
					this.submit.el.disabled = true;
					this.reset.el.disabled  = true;

					this.submit.el.classList.add( 'loading' );
				} else {
					this.submit.el.disabled = false;
					this.reset.el.disabled  = false;

					this.submit.el.classList.remove( 'loading' );
				}
			},

			updateSubmitLabel: function ( model ) {
				var buttomText = bbRlActivity.strings.postUpdateButton;
				if ( $( '#bb-rl-whats-new-form' ).hasClass( 'bb-rl-activity-edit' ) ) {
					buttomText = bbRlActivity.strings.updatePostButton;
				}

				if ( 'scheduled' === model.get( 'activity_action_type' ) || 'scheduled' === this.model.get( 'activity_status' ) ) {
					this.submit.el.value = 'undefined' !== typeof bbRlIsActivitySchedule ? bbRlIsActivitySchedule.strings.schedulePostButton : '';
				} else {
					this.submit.el.value = buttomText;
				}
			}
		}
	);

	bp.Views.EditActivityPostIn = bp.View.extend(
		{
			template: bp.template( 'activity-edit-postin' ),
			initialize: function () {
				this.model.on( 'change', this.render, this );
			},
			render: function () {
				this.$el.html( this.template( this.model.attributes ) );
				return this;
			}
		}
	);

	bp.Views.FormSubmitWrapper = bp.View.extend(
		{
			tagName: 'div',
			id: 'bb-rl-activity-form-submit-wrapper',
			initialize: function () {
				$( '#bb-rl-whats-new-form' ).addClass( 'bb-rl-focus-in' ).parent().addClass( 'modal-popup' ).closest( 'body' ).addClass( 'bb-rl-activity-modal-open' ); // add some class to form so that DOM knows about focus.

				// Show placeholder form.
				$( '#bb-rl-activity-form-placeholder' ).show();

				// Add BB Poll View with template check.
				if ( !_.isUndefined( bp.Views.activityPollForm ) ) {
					// Check if poll templates exist.
					var pollFormTemplate = document.getElementById( 'tmpl-bb-activity-poll-form' );
					if ( pollFormTemplate ) {
						this.views.add( new bp.Views.activityPollForm( { model: this.model } ) );
					}
				}

				this.views.add(
					new bp.Views.ActivityInput(
						{
							model: this.model,
							type: 'button',
							id: 'bb-rl-discard-draft-activity',
							className: 'button outline',
							name: 'bb-rl-discard-draft-activity',
							value: bbRlActivity.strings.discardButton
						}
					)
				);

				// Render topic selector for the global activity.
				if (
					! _.isUndefined( BP_Nouveau.activity.params.topics ) &&
					! _.isUndefined( BP_Nouveau.activity.params.topics.bb_is_enabled_activity_topics ) &&
					BP_Nouveau.activity.params.topics.bb_is_enabled_activity_topics &&
					! _.isUndefined( bp.Views.TopicSelector ) &&
					! this.model.get( 'has_topic_selector' )
				) {
					if ( 0 === $( '.whats-new-topic-selector' ).length ) {
						this.views.add( new bp.Views.TopicSelector( { model : this.model } ) );
						this.model.set( 'has_topic_selector', true );
					}
				}

				this.views.add( new bp.Views.FormSubmit( { model: this.model } ) );
			}
		}
	);

	bp.Views.PostForm = bp.View.extend(
		{
			tagName: 'form',
			className: 'bb-rl-activity-form',
			id: 'bb-rl-whats-new-form',

			attributes: {
				name: 'whats-new-form',
				method: 'post'
			},

			events: {
				'focus #bb-rl-whats-new': 'displayFull',
				'input #bb-rl-whats-new': 'postValidate',
				'reset': 'resetForm',
				'submit': 'postUpdate',
				'keydown': 'postUpdate',
				'click #bb-rl-whats-new-toolbar': 'triggerDisplayFull',
				'change .medium-editor-toolbar-input': 'mediumLink',
				'click #bb-rl-discard-draft-activity': 'discardDraftActivity',
			},

			initialize: function () {
				var activityParams = _.pick(
					bbRlActivity.params,
					[ 'user_id', 'item_id', 'object' ]
				);

				// Pick parameters from bbRlIsActivitySchedule.params.
				if ( ! _.isUndefined( bbRlIsActivitySchedule ) ) {
					var scheduleParams = _.pick(
						bbRlIsActivitySchedule.params,
						[ 'can_schedule_in_feed' ]
					);

					activityParams = _.extend( activityParams, scheduleParams );
				}

				// Pick parameters from bbRlIsActivityPolls.params.
				if ( ! _.isUndefined( bbRlIsActivityPolls ) ) {
					var pollParams = _.pick(
						bbRlIsActivityPolls.params,
						[ 'can_create_poll_activity' ]
					);

					activityParams = _.extend( activityParams, pollParams );
				}

				// Create the model with the merged parameters.
				this.model = new bp.Models.Activity( activityParams );

				this.listenTo( Backbone, 'mediaprivacy', this.updateMultiMediaOptions );
				this.listenTo( Backbone, 'mediaprivacytoolbar', this.updateMultiMediaToolbar );

				this.listenTo( Backbone, 'onError', this.onError );
				this.listenTo( Backbone, 'cleanFeedBack', this.cleanFeedback );

				this.listenTo( Backbone, 'triggerToastMessage', this.triggerToastMessage );

				// Listen for poll changes and update form state.
				this.model.on( 'change:poll', function ( model ) {
					if ( model.get( 'poll' ) ) {
						var $form = $( '#bb-rl-whats-new-form' );
						if ( $form.hasClass( 'bb-rl-focus-in--empty' ) ) {
							$form.removeClass( 'bb-rl-focus-in--empty' );
						}
					}
				} );

				if ( 'user' === bbRlActivity.params.object ) {
					if ( ! bbRlActivity.params.access_control_settings.can_create_activity ) {
						this.$el.addClass( 'bp-hide' );
					} else {
						this.$el.removeClass( 'bp-hide' );
					}
				}

				// Clone the model to set the resetted one.
				this.resetModel = this.model.clone();

				this.views.set(
					[
						new bp.Views.ActivityHeader( { model: this.model } ),
						new bp.Views.UserStatusHuddle( { model: this.model } ),
						new bp.Views.PrivacyStage( { model: this.model } ),
						new bp.Views.FormContent( { activity: this.model, model: this.model } ),
						new bp.Views.EditorToolbar( { model: this.model } ),
						new bp.Views.ActivityToolbar( { model: this.model } ) // Add Toolbar to show in default view.
					]
				);

				this.model.on( 'change:errors', this.displayFeedback, this );

				var $this = this;
				$( document ).ready(
					function ( event ) {
						var $whatsNewForm = $( '#bb-rl-whats-new-form' );
						$whatsNewForm.closest( 'body' ).addClass( 'bb-rl-initial-post-form-open' );
						if ( $( 'body' ).hasClass( 'bb-rl-initial-post-form-open' ) ) {
								$this.displayFull( event );
								$this.$el.closest( '.bb-rl-activity-update-form' ).find( '#bb-rl-aw-whats-new-reset' ).trigger( 'click' ); // Trigger reset.
						}
					}
				);
			},

			postValidate: function () {
				var $whatsNew = this.$el.find( '#bb-rl-whats-new' ),
					content   = $.trim( $whatsNew[ 0 ].innerHTML.replace( /<div>/gi, '\n' ).replace( /<\/div>/gi, '' ) );
				content       = content.replace( /&nbsp;/g, ' ' );

				if ( content.replace( /<p>/gi, '' ).replace( /<\/p>/gi, '' ).replace( /<br>/gi, '' ) === '' ) {
					$whatsNew[0].innerHTML = '';
				}

				var validContent = bp.Nouveau.Activity.postForm.validateContent();
				if ( validContent ) {
					this.$el.removeClass( 'bb-rl-focus-in--empty loading' );
				} else {
					this.$el.addClass( 'bb-rl-focus-in--empty' );
				}

				// Validate topic content.
				if (
					! _.isUndefined( BP_Nouveau.activity.params.topics ) &&
					! _.isUndefined( BP_Nouveau.activity.params.topics.bb_is_enabled_activity_topics ) &&
					BP_Nouveau.activity.params.topics.bb_is_enabled_activity_topics &&
					! _.isUndefined( BP_Nouveau.activity.params.topics.bb_is_activity_topic_required ) &&
					BP_Nouveau.activity.params.topics.bb_is_activity_topic_required &&
					! _.isUndefined( BBTopicsManager )
				) {
					BBTopicsManager.bbTopicValidateContent( {
						self         : this,
						selector     : this.$el,
						validContent : validContent,
						class        : 'bb-rl-focus-in--empty',
						data         : this.model.attributes,
						action       : 'postValidate'
					} );
				}
			},

			mediumLink: function () {
				var value = $( '.medium-editor-toolbar-input' ).val();

				if ( value !== '' ) {
					$( '#bb-rl-whats-new-form' ).removeClass( 'bb-rl-focus-in--empty' );
				}
			},

			displayFull: function ( event ) {
				var $whatsNewForm = $( '#bb-rl-whats-new-form' );
				// Remove post update notice before opening a modal.
				if ( 6 !== this.views._views[ '' ].length && $( this.views._views[ '' ][6].$el ).hasClass( 'updated' ) ) {
					this.cleanFeedback();
					$whatsNewForm.removeClass( 'bottom-notice' );
				}

				if ( 6 !== this.views._views[ '' ].length ) {
					return;
				}

				if ( 'focusin' === event.type ) {
					$whatsNewForm.closest( 'body' ).removeClass( 'bb-rl-initial-post-form-open' ).addClass( event.type + '-post-form-open' );
				}
				this.model.on( 'change:video change:document change:media change:gif_data change:privacy, change:link_success', this.postValidate, this );

				// Remove feedback.
				var self = this;
				_.each(
					this.views._views[ '' ],
					function ( view ) {
						if ( 'message-feedback' === view.$el.prop( 'id' ) && ! view.$el.hasClass( 'noMediaError' ) ) { // Do not remove Media error message.
							self.cleanFeedback();
							self.$el.removeClass( 'has-feedback' );
						}
					}
				);

				_.each(
					this.views._views[ '' ],
					function ( view, index ) {
						if ( index > 4 ) {
							view.close(); // Remove Toolbar shown in default view.
						}
					}
				);

				$( event.target ).css(
					{
						resize: 'vertical',
						height: 'auto'
					}
				);

				// Backcompat custom fields.
				if ( true === bbRlActivity.params.backcompat ) {
					this.views.add( new bp.Views.FormOptions( { model: this.model } ) );
				}

				// Attach buttons.
				if ( ! _.isUndefined( bbRlActivity.params.buttons ) ) {
					// Global.
					bp.Nouveau.Activity.postForm.buttons.set( bbRlActivity.params.buttons );
					this.views.add(
						new bp.Views.FormButtons(
							{
								collection: bp.Nouveau.Activity.postForm.buttons,
								model: this.model
							}
						)
					);
				}

				bp.Nouveau.Activity.postForm.activityAttachments = new bp.Views.ActivityAttachments( { model: this.model } );
				this.views.add( bp.Nouveau.Activity.postForm.activityAttachments );
				bp.Nouveau.Activity.postForm.activityToolbar = new bp.Views.ActivityToolbar( { model: this.model } );
				this.views.add( bp.Nouveau.Activity.postForm.activityToolbar );

				this.views.add( new bp.Views.FormSubmitWrapper( { model: this.model } ) );

				var $bodyElem = $( 'body' );
				// Wrap Toolbar and submit Wrapper into footer.
				if ( $bodyElem.hasClass( event.type + '-post-form-open' ) ) {
					var $elem = $( '.bb-rl-activity-update-form #bb-rl-whats-new-form' );
					$elem.append( '<div class="bb-rl-whats-new-form-footer"></div>' );
					$elem.find( '#bb-rl-whats-new-toolbar' ).appendTo( '.bb-rl-whats-new-form-footer' );
					$elem.find( '#bb-rl-activity-form-submit-wrapper' ).appendTo( '.bb-rl-whats-new-form-footer' );

					if (
						! _.isUndefined( bbRlIsActivitySchedule ) &&
						! _.isUndefined( typeof bbRlIsActivitySchedule.params.can_schedule_in_feed ) &&
						true === bbRlIsActivitySchedule.params.can_schedule_in_feed
					) {
						$whatsNewForm.find( '.bb-schedule-post_dropdown_section' ).removeClass( 'bp-hide' );
					}

					// Add Poll button.
					if (
						! _.isUndefined( bbRlIsActivityPolls ) &&
						! _.isUndefined( typeof bbRlIsActivityPolls.params.can_create_poll_activity ) &&
						true === bbRlIsActivityPolls.params.can_create_poll_activity
					) {
						$whatsNewForm.find( '.bb-post-poll-button' ).removeClass( 'bp-hide' );
					}
				}

				if ( $( '.bb-rl-activity-update-form .bb-rl-whats-new-scroll-view' ).length ) {
					$( '.bb-rl-activity-update-form  #bb-rl-whats-new-attachments' ).appendTo( '.bb-rl-activity-update-form .bb-rl-whats-new-scroll-view' );
				} else {
					$( '.bb-rl-activity-update-form .bb-rl-whats-new-form-header, .bb-rl-activity-update-form  #bb-rl-whats-new-attachments' ).wrapAll( '<div class="bb-rl-whats-new-scroll-view"></div>' );
					$( '.bb-rl-whats-new-scroll-view' ).on(
						'scroll',
						function ( e ) {
							if ( ! ( /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test( navigator.userAgent ) ) ) {
								$( '.atwho-container #atwho-ground-whats-new .atwho-view' ).hide();
							}

							if( $( '.bb-rl-activity-form' ).hasClass( 'bb-rl-focus-in--privacy' ) ) {
								$( '.bb-rl-activity-privacy-stage' ).css( 'margin-top', '-' + $( e.currentTarget ).scrollTop() + 'px' );
							}
						}
					);

					// Hide mention dropdown while window resized.
					$( window ).on(
						'resize',
						function () {
							$( '.atwho-container #atwho-ground-whats-new .atwho-view:visible' ).hide();
						}
					);
				}
				this.updateMultiMediaOptions();

				// Trigger Media click.
				if ( window.activityMediaAction !== null ) {
					$( '.bb-rl-activity-update-form.modal-popup' ).find( '#' + window.activityMediaAction ).trigger( 'click' );
					window.activityMediaAction = null;
				}
				// Add Overlay.
				if ( $( '.bb-rl-activity-update-form .bb-rl-activity-update-form-overlay' ).length === 0 ) {
					$( '.bb-rl-activity-update-form.modal-popup' ).prepend( '<div class="bb-rl-activity-update-form-overlay"></div>' );
				}
				this.activityHideModalEvent();

				if ( $bodyElem.hasClass( event.type + '-post-form-open' ) && ! $whatsNewForm.hasClass( 'bb-rl-activity-edit' ) ) {

					if ( ! bp.draft_local_interval ) {
						bp.draft_local_interval = setInterval(
							function () {
								bp.Nouveau.Activity.postForm.storeDraftActivity();
							},
							3000
						);
					}

					if ( ! bp.draft_ajax_interval ) {
						bp.draft_ajax_interval = setInterval(
							function () {
								bp.Nouveau.Activity.postForm.postDraftActivity( false, false );
							},
							20000
						);
					}

					// Display draft activity.
					bp.Nouveau.Activity.postForm.displayDraftActivity();
				}

				if ( ! _.isUndefined( bbRlMedia ) &&
							! _.isUndefined( bbRlMedia.emoji ) &&
							(
								(
									! _.isUndefined( bbRlMedia.emoji.profile ) && bbRlMedia.emoji.profile
								) ||
								(
									! _.isUndefined( bbRlMedia.emoji.groups ) && bbRlMedia.emoji.groups
								)
							)
						) {
							var $whatsNew = $( '#bb-rl-whats-new' );
							if( $whatsNew.data('emojioneArea') ) {
								// Clean up the existing instance
								var emojiContainer = $whatsNew.closest('form').find('.bb-rl-post-emoji');

								// Remove the emojioneArea instance
								delete $whatsNew[0].emojioneArea;

								// Clean up the container
								emojiContainer.empty();
							}

							$whatsNew.emojioneArea(
								{
									standalone: true,
									hideSource: false,
									container: '#bb-rl-whats-new-toolbar .bb-rl-post-emoji',
									autocomplete: false,
									pickerPosition: 'top',
									hidePickerOnBlur: true,
									useInternalCDN: false,
									events: {
										emojibtn_click: function () {
											$whatsNew[0].emojioneArea.hidePicker();
											if ( window.getSelection && document.createRange ) { // Get caret position when user adds emoji.
												var sel = window.getSelection && window.getSelection();
												if ( sel && sel.rangeCount > 0 ) {
													window.activityCaretPosition = sel.getRangeAt( 0 );
												}
											} else {
												window.activityCaretPosition = document.selection.createRange();
											}

											// Enable post submit button.
											$whatsNewForm.removeClass( 'bb-rl-focus-in--empty' );
										},

										picker_show: function () {
											$( this.button[0] ).closest( '.bb-rl-post-emoji' ).addClass( 'active' );
										},

										picker_hide: function () {
											$( this.button[0] ).closest( '.bb-rl-post-emoji' ).removeClass( 'active' );
										},
									}
								}
							);
						}

				$( 'a.bp-suggestions-mention:empty' ).remove();
				
				// Trigger modal opened event.
				$( document ).trigger( 'bb_display_full_form' );
			},

			activityHideModalEvent: function () {

				$( document ).on(
					'keyup',
					function ( event ) {
						if ( event.keyCode === 27 && false === event.ctrlKey ) {
							var $elem = $( '.bb-rl-activity-update-form.modal-popup' );
							setTimeout(
								function () {
									$elem.find( '#bb-rl-whats-new' ).blur();
									$elem.find( '#bb-rl-aw-whats-new-reset' ).trigger( 'click' );
									// Post activity hide modal.
									var $singleActivityFormWrap = $( '#bb-rl-single-activity-edit-form-wrap' );
									if ( $singleActivityFormWrap.length ) {
											$singleActivityFormWrap.hide();
									}
								},
								0
							);
						}
					}
				);

			},

			triggerDisplayFull: function ( event ) {
				event.preventDefault();

				// Check for media click.
				if ( $( event.target ).hasClass( 'bb-rl-toolbar-button' ) || $( event.target ).parent().hasClass( 'bb-rl-toolbar-button' ) ) {
					window.activityMediaAction = $( event.target ).parent().attr( 'id' );
					if ( 'undefined' === typeof window.activityMediaAction ) {
						window.activityMediaAction = $( event.target ).attr( 'id' );
					}
				}
				if ( ! this.$el.hasClass( 'bb-rl-focus-in' ) ) {
					// Set focus on "#whats-new" to trigger 'displayFull'.
					var element           = this.$el.find( '#bb-rl-whats-new' )[ 0 ],
						element_selection = window.getSelection(),
						element_range     = document.createRange();
					element_range.setStart( element, 0 );
					element_range.setEnd( element, 0 );
					element_selection.removeAllRanges();
					element_selection.addRange( element_range );
				}
			},

			resetForm: function () {
				_.each(
					this.views._views[ '' ],
					function ( view, index ) {
						if ( index > 4 ) {
							view.close();
						}
					}
				);
				var $whatsNew          = $( '#bb-rl-whats-new' ),
					whats_new_form     = $( '#bb-rl-whats-new-form' ),
					$showToolbarButton = $( '#bb-rl-show-toolbar-button' );

				$whatsNew.css(
					{
						resize: 'none',
						height: '50px'
					}
				);
				whats_new_form.removeClass( 'bb-rl-focus-in bb-rl-focus-in--privacy bb-rl-focus-in--group bb-rl-focus-in--scroll has-draft' ).parent().removeClass( 'modal-popup' ).closest( 'body' ).removeClass( 'bb-rl-activity-modal-open' ); // remove class when reset.

				// Hide placeholder form.
				$( '#bb-rl-activity-form-placeholder' ).hide();

				$( '#bb-rl-whats-new-content' ).find( '#bb-rl-activity-id' ).val( '' ); // reset activity id if in edit mode.
				bp.Nouveau.Activity.postForm.postForm.$el.removeClass( 'bb-rl-activity-edit hide-schedule-button' );

				if ( ! _.isUndefined( bbRlActivity.params.objects ) ) {
					bp.Nouveau.Activity.postForm.postForm.$el.find( '.bb-rl-activity-privacy__label-group' ).show().find( 'input#group' ).attr( 'disabled', false ); // enable back group visibility level.
				}

				this.model.set( 'edit_activity', false );
				bp.Nouveau.Activity.postForm.editActivityData = false;

				if ( 'user' === bbRlActivity.params.object ) {
					if ( ! bbRlActivity.params.access_control_settings.can_create_activity ) {
						this.$el.addClass( 'bp-hide' );
					} else {
						this.$el.removeClass( 'bp-hide' );
					}
				}

				// Reset the model.
				this.model.clear();
				this.model.set( this.resetModel.attributes );

				whats_new_form.find( '#public.bb-rl-activity-privacy__input' ).prop( 'checked', true );
				whats_new_form.find( '#bb-rl-activity-group-ac-items .bb-rl-activity-object' ).removeClass( 'selected' );
				whats_new_form.find( '#bb-rl-activity-group-ac-items .bb-rl-activity-object__radio' ).prop( 'checked', false );

				$( '.medium-editor-toolbar' ).removeClass( 'active medium-editor-toolbar-active' );
				$showToolbarButton.removeClass( 'active' );
				$( '.medium-editor-action' ).removeClass( 'medium-editor-button-active' );
				$( '.medium-editor-toolbar-actions' ).show();
				$( '.medium-editor-toolbar-form' ).removeClass( 'medium-editor-toolbar-form-active' );
				$showToolbarButton.parent( '.bb-rl-show-toolbar' ).attr( 'data-bp-tooltip', $showToolbarButton.parent( '.bb-rl-show-toolbar' ).attr( 'data-bp-tooltip-show' ) );

				// Add Toolbar to show in default view.
				bp.Nouveau.Activity.postForm.activityToolbar = new bp.Views.ActivityToolbar( { model: this.model } );
				this.views.add( bp.Nouveau.Activity.postForm.activityToolbar );

				// Reset activity link preview.
				$whatsNew.removeData( 'activity-url-preview' );

				// Remove footer wrapper.
				this.$el.find( '.bb-rl-whats-new-form-footer' ).remove();

				this.updateMultiMediaOptions();
			},

			cleanFeedback: function () {
				_.each(
					this.views._views[ '' ],
					function ( view ) {
						if ( 'message-feedback' === view.$el.prop( 'id' ) ) {
							view.remove();
							$( '#bb-rl-whats-new-form #bb-rl-activity-header' ).css( { 'margin-bottom': 0 } );
						}
					}
				);
			},

			triggerToastMessage: function ( title, message, type, url, autoHide ) {
				$( document ).trigger( 'bb_trigger_toast_message', [ title, message, type, url, autoHide ] );
			},

			displayFeedback: function ( model ) {
				if ( _.isUndefined( this.model.get( 'errors' ) ) ) {
					this.cleanFeedback();
					this.$el.removeClass( 'has-feedback' );
				} else {
					this.cleanFeedback(); // Clean if there's any error already displayed.
					this.views.add( new bp.Views.activityFeedback( model.get( 'errors' ) ) );
					this.$el.addClass( 'has-feedback' );
					var errorHeight = this.$el.find( '#message-feedback' ).outerHeight( true );
					this.$el.find( '#bb-rl-activity-header' ).css( { 'margin-bottom': errorHeight + 'px' } );
				}
			},

			decodeHtml: function (html) {

				var txt       = document.createElement( 'textarea' );
				txt.innerHTML = html;
				return txt.value;

			},

			postUpdate: function ( event ) {
				var self = this,
					meta = {}, edit = false;

				if ( event ) {
					if ( 'keydown' === event.type && ( 13 !== event.keyCode || ! event.ctrlKey ) ) {
						return event;
					}

					event.preventDefault();
				}

				// unset all errors before submit.
				self.model.unset( 'errors' );

				// Set the content and meta.
				_.each(
					self.$el.serializeArray(),
					function ( pair ) {
						pair.name = pair.name.replace( '[]', '' );
						if ( pair.name.startsWith( 'bb-poll-question-option[' ) ) {
							pair.name = pair.name.replace( /\[\d+\]/, '' );
						}
						if ( -1 === _.indexOf( [ 'aw-whats-new-submit', 'whats-new-post-in', 'bb-schedule-activity-date-field', 'bb-schedule-activity-meridian', 'bb-schedule-activity-time-field', 'bb-poll-question-field', 'bb-poll-duration', 'bb-poll-question-option', 'bb-poll-allow-multiple-answer', 'bb-poll-allow-new-option' ], pair.name ) ) {
							if ( _.isUndefined( meta[ pair.name ] ) ) {
								meta[ pair.name ] = pair.value;
							} else {
								if ( ! _.isArray( meta[pair.name] ) ) {
									meta[pair.name] = [ meta[pair.name] ];
								}

								meta[ pair.name ].push( pair.value );
							}
						}
					}
				);

				// Post content.
				var $whatsNew        = self.$el.find( '#bb-rl-whats-new' ),
					atwho_query      = $whatsNew.find( 'span.atwho-query' ),
					atwhoQueryLength = atwho_query.length;
				for ( var i = 0; i < atwhoQueryLength; i++ ) {
					$( atwho_query[ i ] ).replaceWith( atwho_query[ i ].innerText );
				}

				// transform other emoji into emojionearea emoji.
				$whatsNew.find( 'img.emoji' ).each(
					function ( index, Obj) {
						$( Obj ).addClass( 'bb-rl-emojioneemoji' );
						var emojis = $( Obj ).attr( 'alt' );
						$( Obj ).attr( 'data-emoji-char', emojis );
						$( Obj ).removeClass( 'emoji' );
					}
				);

				// Transform emoji image into emoji unicode.
				$whatsNew.find( 'img.bb-rl-emojioneemoji' ).replaceWith(
					function () {
						return this.dataset.emojiChar;
					}
				);

				// Add valid line breaks.
				var content = $.trim( $whatsNew[0].innerHTML.replace( /<div>/gi, '\n' ).replace( /<\/div>/gi, '' ) );
				content     = content.replace( /&nbsp;/g, ' ' );

				self.model.set( 'content', content, { silent: true } );

				// Silently add meta.
				self.model.set( meta, { silent: true } );

				var medias      = self.model.get( 'media' ),
					modelObject = self.model.get( 'object' ),
					mediaLength = medias && medias.length;
				if ( 'group' === modelObject && ! _.isUndefined( medias ) && mediaLength ) {
					for ( var k = 0; k < mediaLength; k++ ) {
						medias[ k ].group_id = self.model.get( 'item_id' );
					}
					self.model.set( 'media', medias );
				}

				var document       = self.model.get( 'document' ),
					documentLength = document && document.length;
				if ( 'group' === modelObject && ! _.isUndefined( document ) && documentLength ) {
					for ( var d = 0; d < documentLength; d++ ) {
						document[ d ].group_id = self.model.get( 'item_id' );
					}
					self.model.set( 'document', document );
				}

				var video       = self.model.get( 'video' ),
					videoLength = video && video.length;
				if ( 'group' === modelObject && ! _.isUndefined( video ) && videoLength ) {
					for ( var v = 0; v < videoLength; v++ ) {
						video[ v ].group_id = self.model.get( 'item_id' );
					}
					self.model.set( 'video', video );
				}

				// validation for content editor.
				if (
					$( $.parseHTML( content ) ).text().trim() === '' &&
					( ! _.isUndefined( this.model.get( 'link_success' ) ) && true !== this.model.get( 'link_success' ) ) &&
					(
					( ! _.isUndefined( self.model.get( 'video' ) ) && ! self.model.get( 'video' ).length ) &&
					( ! _.isUndefined( self.model.get( 'document' ) ) && ! self.model.get( 'document' ).length ) &&
					( ! _.isUndefined( self.model.get( 'media' ) ) && ! self.model.get( 'media' ).length ) &&
					( ! _.isUndefined( self.model.get( 'gif_data' ) ) && ! Object.keys( self.model.get( 'gif_data' ) ).length ) &&
					( ! _.isUndefined( self.model.get( 'poll' ) ) && ! Object.keys( self.model.get( 'poll' ) ).length )
					)
				) {
					self.model.set(
						'errors',
						{
							type: 'error',
							value: bbRlActivity.params.errors.empty_post_update
						}
					);
					return false;
				}

				// update posting status true.
				self.model.set( 'posting', true );

				var data = {
					'_wpnonce_post_update': bbRlActivity.params.post_nonce
				};

				// Add the Akismet nonce if it exists.
				var bpNonceElem = $( '#_bp_as_nonce' );
				if ( bpNonceElem.length ) {
					if ( bpNonceElem.val() ) {
						data._bp_as_nonce = bpNonceElem.val();
					}
				}

				// Remove all unused model attribute.
				data = _.omit(
					_.extend( data, this.model.attributes ),
					[
						'link_images',
						'link_image_index',
						'link_image_index_save',
						'link_success',
						'link_error',
						'link_error_msg',
						'link_scrapping',
						'link_loading',
						'posting',
						'group_image',
						'can_schedule_in_feed',
						'can_create_poll_activity',
						'bb-poll-question-option',
						'poll',
						'topics'
					]
				);

				var topicSelector = $( '#buddypress .whats-new-topic-selector .bb-rl-topic-selector-list li' );
				if ( topicSelector.length ) {
					var topicId   = topicSelector.find( 'a.selected' ).data( 'topic-id' ) || 0;
					data.topic_id = topicId;
				}

				// Form link preview data to pass in request if available.
				if ( self.model.get( 'link_success' ) ) {
					var images       = self.model.get( 'link_images' ),
						index        = self.model.get( 'link_image_index' ),
						indexConfirm = self.model.get( 'link_image_index_save' );
					if ( images && images.length ) {
						data = _.extend(
							data,
							{
								'link_image': images[ indexConfirm ],
								'link_image_index': index,
								'link_image_index_save' : indexConfirm
							}
						);
					}

				} else {
					data = _.omit(
						data,
						[
							'link_title',
							'link_description',
							'link_url'
						]
					);
				}

				// check if edit activity.
				if ( self.model.get( 'id' ) > 0 ) {
					edit      = true;
					data.edit = 1;

					if ( ! bp.privacyEditable ) {
						data.privacy = bp.privacy;
					}
				}

				// Some firewalls restrict iframe tag in form post like wordfence.
				if (
					! _.isUndefined( data.link_description ) &&
					! _.isUndefined( data.link_embed ) &&
					true === data.link_embed
				) {
					data.link_description = '';
				}

				bp.ajax.post( 'post_update', data ).done(
					function ( response ) {

						// check if edit activity then scroll up 1px so image will load automatically.
						if ( self.model.get( 'id' ) > 0 ) {
							$( 'html, body' ).animate(
								{
									scrollTop: $( window ).scrollTop() + 1
								}
							);
						}

						// At first, hide the modal.
						bp.Nouveau.Activity.postForm.postActivityEditHideModal();

						var store       = bp.Nouveau.getStorage( 'bp-activity' ),
							searchTerms = $( '[data-bp-search="activity"] input[type="search"]' ).val(), matches = {},
							toPrepend   = false;

						// Look for matches if the stream displays search results.
						if ( searchTerms ) {
							searchTerms = new RegExp( searchTerms, 'im' );
							matches     = response.activity.match( searchTerms );
						}

						/**
						 * Before injecting the activity into the stream, we need to check the filter
						 * and search terms are consistent with it when posting from a single item or
						 * from the Activity directory.
						 */
						if ( ( ! searchTerms || matches ) ) {
							toPrepend = ! store.filter || 0 === parseInt( store.filter, 10 ) || 'activity_update' === store.filter;
						}

						/**
						 * "My Groups" tab is active.
						 */
						if ( toPrepend && response.is_directory ) {
							toPrepend = ( 'all' === store.scope && ( 'user' === self.model.get( 'object' ) || 'group' === self.model.get( 'object' ) ) ) || ( self.model.get( 'object' ) + 's' === store.scope );
						}

						/**
						 * In the user activity timeline, user is posting on other user's timeline
						 * it will not have activity to prepend/append because of scope and privacy.
						 */
						if ( '' === response.activity && response.is_user_activity && response.is_active_activity_tabs ) {
							toPrepend = false;
						}

						var medias       = self.model.get( 'media' ),
							mediasLength = medias && medias.length;
						if ( ! _.isUndefined( medias ) && mediasLength ) {
							for ( var k = 0; k < mediasLength; k++ ) {
								medias[ k ].saved = true;
							}
							self.model.set( 'media', medias );
						}

						var link_embed = false;
						if ( true === self.model.get( 'link_embed' ) ) {
							link_embed = true;
						}

						var documents       = self.model.get( 'document' ),
							documentsLength = documents && documents.length;
						if ( ! _.isUndefined( documents ) && documentsLength ) {
							for ( var d = 0; d < documentsLength; d++ ) {
								documents[ d ].saved = true;
							}
							self.model.set( 'document', documents );
						}

						var videos       = self.model.get( 'video' ),
							videosLength = videos && videos.length;
						if ( ! _.isUndefined( videos ) && videosLength ) {
							for ( var v = 0; v < videosLength; v++ ) {
								videos[ v ].saved = true;
							}
							self.model.set( 'video', videos );
						}

						if ( '' === self.model.get( 'id' ) || 0 === parseInt( self.model.get( 'id' ) ) ) {
							// Reset draft activity.
							bp.Nouveau.Activity.postForm.resetDraftActivity( false );
						}

						// Reset the form.
						self.resetForm();

						// Trigger Toast message if it is a scheduled post.
						if ( 'scheduled' === data.activity_action_type ) {
							var title    = 'undefined' !== typeof bbRlIsActivitySchedule ? bbRlIsActivitySchedule.strings.EditSuccessScheduleTitle : '',
								desc     = 'undefined' !== typeof bbRlIsActivitySchedule ? bbRlIsActivitySchedule.strings.EditSuccessScheduleDesc : '',
								LinkText = 'undefined' !== typeof bbRlIsActivitySchedule ? bbRlIsActivitySchedule.strings.EditViewSchedulePost : '';

							if ( ! data.edit_activity ) { // It's a new scheduled post.
								title    = 'undefined' !== typeof bbRlIsActivitySchedule ? bbRlIsActivitySchedule.strings.successScheduleTitle : '';
								desc     = 'undefined' !== typeof bbRlIsActivitySchedule ? bbRlIsActivitySchedule.strings.successScheduleDesc : '';
								LinkText = 'undefined' !== typeof bbRlIsActivitySchedule ? bbRlIsActivitySchedule.strings.viewSchedulePosts : '';
							}

							if ( '' !== title && '' !== desc && '' !== LinkText ) {
								var scheduleUrl = '';
								if ( ! _.isUndefined( data.privacy ) && 'group' === data.privacy && ! _.isUndefined( data.group_url ) ) {
									scheduleUrl = data.group_url + '?action=scheduled_posts';
								}
								Backbone.trigger(
									'triggerToastMessage',
									title,
									'<div>' + desc + ' <span class="toast-messages-action_link bb-view-scheduled-posts"> ' + LinkText + '</span></div>',
									'success',
									scheduleUrl,
									true
								);
							}
						}

						// Prevent activity from being prepended if it doesn't belong to the current topic.
						var currentTopicSlug = new URLSearchParams( window.location.search ).get( 'bb-topic' );
						if ( currentTopicSlug && '' !== response.activity ) {
							var activityData = response.activity.match( /data-bp-activity="([^"]*)"/ );
							if ( activityData && activityData[1] ) {
								var parsedData = JSON.parse(self.decodeHtml( activityData[1] ) );
								if (
									!_.isUndefined( parsedData.topics ) &&
									!_.isUndefined( parsedData.topics.topic_slug ) &&
									parsedData.topics.topic_slug !== currentTopicSlug
								) {
									toPrepend = false;
								}
							}
						}

						var activityElemSel = $( '#bb-rl-activity-' + response.id );

						// Display a successful feedback if the activity is not consistent with the displayed stream.
						if ( ! toPrepend ) {

							self.views.add(
								new bp.Views.activityFeedback(
									{
										value: response.message,
										type: 'updated'
									}
								)
							);
							$( '#bb-rl-whats-new-form' ).addClass( 'bottom-notice' );

							// Edit activity.
						} else if ( edit && 'scheduled' !== data.activity_action_type && activityElemSel.length ) {
							activityElemSel.replaceWith( response.activity );

							// Extract value of data-bp-activity.
							var start_index              = response.activity.indexOf( 'data-bp-activity="' ) + 'data-bp-activity="'.length,
								end_index                = response.activity.indexOf( '"', start_index ),
								data_bp_activity         = response.activity.substring( start_index, end_index ),
								decoded_data_bp_activity = self.decodeHtml( data_bp_activity ),
								parsed_data_bp_activity  = JSON.parse( decoded_data_bp_activity ); // Parse data-bp-activity attribute value as JSON.

							// Handle HTML entities in the content.
							// Update the content property with the decoded content.
							parsed_data_bp_activity.content = $( '<div>' ).html( parsed_data_bp_activity.content ).html();

							var activity_modal_item     = $( '#bb-rl-activity-modal .bb-rl-activity-list .activity-item' ),
								activity_target         = activity_modal_item.find( '.bb-rl-activity-content' ).find( '.bb-rl-activity-inner' ),
								activity_privacy_status = activity_modal_item.find( '.bb-rl-media-privacy-wrap' ).find( '.privacy-wrap' ).find( '.privacy' ),
								activity_privacy_list   = activity_modal_item.find( '.bb-rl-media-privacy-wrap' ).find( '.activity-privacy li' );
							if ( activity_modal_item.length > 0 ) {
								var content = activityElemSel.find( '.bb-rl-activity-content' ).find( '.bb-rl-activity-inner' ).html();
								activity_target.empty();
								activity_target.append( content );
								activity_modal_item.data( 'bp-activity', parsed_data_bp_activity );
								activity_privacy_status.removeClass().addClass( 'privacy selected ' + parsed_data_bp_activity.privacy );
								activity_privacy_list.removeClass( 'selected' );
								activity_privacy_list.filter(
									function () {
										return $( this ).hasClass( parsed_data_bp_activity.privacy );
									}
								).addClass( 'selected' );
							}

							// Inject the activity into the stream only if it hasn't been done already (HeartBeat).
						} else if ( ! activityElemSel.length ) {

							// It's the very first activity, let's make sure the container can welcome it!.
							if ( ! $( '#bb-rl-activity-stream ul.bb-rl-activity-list' ).length ) {
								$( '#bb-rl-activity-stream' ).html( $( '<ul></ul>' ).addClass( 'bb-rl-activity-list bb-rl-item-list bb-rl-list' ) );
							}

							// Check if there is a pinned activity with .bb-pinned class.
							var pinned_activity = $( '#bb-rl-activity-stream ul.bb-rl-activity-list li:first.bb-pinned' );

							if ( pinned_activity.length > 0 ) {

								// If a pinned activity with .bb-pinned class is found, insert after it.
								bp.Nouveau.inject( '#bb-rl-activity-stream ul.bb-rl-activity-list li:first.bb-pinned', response.activity, 'after' );
							} else {

								// Prepend the activity.
								bp.Nouveau.inject( '#bb-rl-activity-stream ul.bb-rl-activity-list', response.activity, 'prepend' );
							}

							// replace dummy image with original image by faking scroll event.
							jQuery( window ).scroll();

							if ( link_embed ) {
								if ( ! _.isUndefined( window.instgrm ) ) {
									window.instgrm.Embeds.process();
								}
								if ( ! _.isUndefined( window.FB ) && ! _.isUndefined( window.FB.XFBML ) ) {
									window.FB.XFBML.parse( $( document ).find( '#bb-rl-activity-' + response.id ).get( 0 ) );
								}
							}
						}

						// Loose post form textarea focus for Safari.
						if ( navigator.userAgent.includes( 'Safari' ) && ! navigator.userAgent.includes( 'Chrome' ) ) {
							$( 'input' ).focus().blur();
						}
					}
				).fail(
					function ( response ) {
						self.model.set( 'posting', false );
						self.model.set(
							'errors',
							{
								type: 'error',
								value:  undefined === response.message ? bbRlActivity.params.errors.post_fail : response.message
							}
						);
					}
				);
			},

			updateMultiMediaOptions: function () {

				if ( ! _.isUndefined( bbRlMedia ) ) {
					bp.mediaUtilities.handleMediaSupport(
						{
							triggerEvent           : true, // Trigger Backbone events.
							closeDropzone          : false, // Close dropzone.
							bypassValidateDropZone : true,
						},
						this.model
					);
				}
			},

			updateMultiMediaToolbar: function () {

				if ( ! _.isUndefined( bbRlMedia ) ) {
					bp.mediaUtilities.handleMediaSupport(
						{
							triggerEvent           : false, // Trigger Backbone events.
							closeDropzone          : true, // Close dropzone.
							bypassValidateDropZone : true,
						},
						this.model
					);
				}
			},

			onError: function ( error, type ) {
				var erroType = type || 'error';
				this.model.unset( 'errors' );
				this.model.set(
					'errors',
					{
						type: erroType,
						value: error
					}
				);
			},

			discardDraftActivity: function () {

				// Reset view data.
				_.each(
					this.views._views[ '' ],
					function ( view, index ) {
						if ( index > 4 ) {
							view.close();
						}
					}
				);

				var $whatsNew                = $( '#bb-rl-whats-new' );
				var $activityFormPlaceholder = $( '#bb-rl-activity-form-placeholder' );
				$whatsNew.css(
					{
						resize: 'none',
						height: '50px'
					}
				);

				// Hide placeholder form.
				$activityFormPlaceholder.hide();

				$( '#bb-rl-whats-new-content' ).find( '#bb-rl-activity-id' ).val( '' ); // reset activity id if in edit mode.
				bp.Nouveau.Activity.postForm.postForm.$el.removeClass( 'bb-rl-activity-edit' );

				if ( ! _.isUndefined( bbRlActivity.params.objects ) ) {
					// enable back group visibility level.
					bp.Nouveau.Activity.postForm.postForm.$el.find( '.bb-rl-activity-privacy__label-group' ).show().find( 'input#group' ).attr( 'disabled', false );
				}

				this.model.set( 'edit_activity', false );
				bp.Nouveau.Activity.postForm.editActivityData = false;

				if ( 'user' === bbRlActivity.params.object ) {
					if ( ! bbRlActivity.params.access_control_settings.can_create_activity ) {
						this.$el.addClass( 'bp-hide' );
					} else {
						this.$el.removeClass( 'bp-hide' );
					}
				}

				// Remove topic data from draft activity data.
				if ( bp.draft_activity.data.topics ) {
					delete bp.draft_activity.data.topics;
				}

				// Reset the model.
				this.model.clear();
				this.model.set( this.resetModel.attributes );

				// Remove footer wrapper.
				this.$el.find( '.bb-rl-whats-new-form-footer' ).remove();

				// Reset view.
				var whats_new_form     = $( '#bb-rl-whats-new-form' ),
					$showToolbarButton = $( '#bb-rl-show-toolbar-button' );

				whats_new_form.find( '#public.bb-rl-activity-privacy__input' ).prop( 'checked', true );
				whats_new_form.find( '#bb-rl-activity-group-ac-items .bb-rl-activity-object__radio' ).prop( 'checked', false ).removeAttr( 'checked' );
				whats_new_form.find( '#bb-rl-activity-group-ac-items .bb-rl-radio-style.selected' ).removeClass( 'selected' );

				$( '.medium-editor-toolbar' ).removeClass( 'active medium-editor-toolbar-active' );
				$showToolbarButton.removeClass( 'active' );
				$( 'medium-editor-action' ).removeClass( 'medium-editor-button-active' );
				$( '.medium-editor-toolbar-actions' ).show();
				$( '.medium-editor-toolbar-form' ).removeClass( 'medium-editor-toolbar-form-active' );
				$showToolbarButton.parent( '.bb-rl-show-toolbar' ).attr( 'data-bp-tooltip', $showToolbarButton.parent( '.bb-rl-show-toolbar' ).attr( 'data-bp-tooltip-show' ) );

				// Add Toolbar to show in default view.
				bp.Nouveau.Activity.postForm.activityAttachments = new bp.Views.ActivityAttachments( { model: this.model } );
				this.views.add( bp.Nouveau.Activity.postForm.activityAttachments );
				bp.Nouveau.Activity.postForm.activityToolbar = new bp.Views.ActivityToolbar( { model: this.model } );
				this.views.add( bp.Nouveau.Activity.postForm.activityToolbar );
				this.views.add( new bp.Views.FormSubmitWrapper( { model: this.model } ) );

				// Wrap Toolbar and submit Wrapper into footer.
				if ( $( 'body' ).hasClass( 'focusin-post-form-open' ) ) {
					var $elem = $( '.bb-rl-activity-update-form #bb-rl-whats-new-form' );
					$elem.append( '<div class="bb-rl-whats-new-form-footer"></div>' );
					$elem.find( '#bb-rl-whats-new-toolbar' ).appendTo( '.bb-rl-whats-new-form-footer' );
					$elem.find( '#bb-rl-activity-form-submit-wrapper' ).appendTo( '.bb-rl-whats-new-form-footer' );
				}

				if ( $( '.bb-rl-activity-update-form .bb-rl-whats-new-scroll-view' ).length ) {
					$( '.bb-rl-activity-update-form  #bb-rl-whats-new-attachments' ).appendTo( '.bb-rl-activity-update-form .bb-rl-whats-new-scroll-view' );
				} else {
					$( '.bb-rl-activity-update-form .bb-rl-whats-new-form-header, .bb-rl-activity-update-form #bb-rl-whats-new-attachments' ).wrapAll( '<div class="bb-rl-whats-new-scroll-view"></div>' );
					$( '.bb-rl-whats-new-scroll-view' ).on(
						'scroll',
						function () {
							if ( ! (
								/Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test( navigator.userAgent )
							) ) {
								$( '.atwho-container #atwho-ground-whats-new .atwho-view' ).hide();
							}
						}
					);

					// Hide mention dropdown while window resized.
					$( window ).on(
						'resize',
						function () {
							$( '.atwho-container #atwho-ground-whats-new .atwho-view:visible' ).hide();
						}
					);
				}

				// Topic validates while discard draft activity.
				if ( $( '.whats-new-topic-selector:visible' ).length ) {
					this.postValidate();
				}

				this.updateMultiMediaOptions();

				// Reinitialize emoji.
				if ( ! _.isUndefined( bbRlMedia ) &&
							! _.isUndefined( bbRlMedia.emoji ) &&
							(
								(
									! _.isUndefined( bbRlMedia.emoji.profile ) && bbRlMedia.emoji.profile
								) ||
								(
									! _.isUndefined( bbRlMedia.emoji.groups ) && bbRlMedia.emoji.groups
								)
							)
						) {
							var $bbRlWhatNew     = $( '#bb-rl-whats-new' );
							var $bbRlWhatNewForm = $( '#bb-rl-whats-new-form' );
							if( $bbRlWhatNew.data('emojioneArea') ) {
								// Clean up the existing instance
								var emojiContainer = $bbRlWhatNew.closest('form').find('.bb-rl-post-emoji');

								// Remove the emojioneArea instance
								delete $bbRlWhatNew[0].emojioneArea;

								// Clean up the container
								emojiContainer.empty();
							}

							$bbRlWhatNew.emojioneArea(
								{
									standalone: true,
									hideSource: false,
									container: '#bb-rl-whats-new-toolbar .bb-rl-post-emoji',
									autocomplete: false,
									pickerPosition: 'top',
									hidePickerOnBlur: true,
									useInternalCDN: false,
									events: {
										emojibtn_click: function () {
											$bbRlWhatNew[0].emojioneArea.hidePicker();
											if ( window.getSelection && document.createRange ) { // Get caret position when user adds emoji.
												var sel = window.getSelection && window.getSelection();
												if ( sel && sel.rangeCount > 0 ) {
													window.activityCaretPosition = sel.getRangeAt( 0 );
												}
											} else {
												window.activityCaretPosition = document.selection.createRange();
											}

											// Enable post submit button.
											$bbRlWhatNewForm.removeClass( 'bb-rl-focus-in--empty' );
										},

										picker_show: function () {
											$( this.button[0] ).closest( '.bb-rl-post-emoji' ).addClass( 'active' );
										},

										picker_hide: function () {
											$( this.button[0] ).closest( '.bb-rl-post-emoji' ).removeClass( 'active' );
										},
									}
								}
							);
						}

				// Delete the activity from the database.
				bp.Nouveau.Activity.postForm.resetDraftActivity( true );
			},
		}
	);

	bp.Views.PostFormPlaceholder = bp.View.extend(
		{
			tagName: 'form',
			className: 'bb-rl-activity-form-placeholder',
			id: 'bb-rl-whats-new-form-placeholder',

			initialize: function () {
				this.model = new bp.Models.Activity(
					_.pick(
						bbRlActivity.params,
						[ 'user_id', 'item_id', 'object' ]
					)
				);

				// Clone the model to set the resetted one.
				this.resetModel = this.model.clone();

				this.views.set(
					[
						new bp.Views.UserStatusHuddle( { model: this.model } ),
						new bp.Views.FormPlaceholderContent( { activity: this.model, model: this.model } ),
					]
				);

			},

		}
	);

	bp.Views.FormPlaceholderContent = bp.View.extend(
		{
			tagName: 'div',
			id: 'bb-rl-whats-new-content-placeholder',

			initialize: function () {
				this.$el.html( $( '<div></div>' ).prop( 'id', 'bb-rl-whats-new-textarea-placeholder' ) );
				this.views.set( '#bb-rl-whats-new-textarea-placeholder', new bp.Views.WhatsNewPlaceholder() );
			},
		}
	);

	bp.Views.WhatsNewPlaceholder = bp.View.extend(
		{
			tagName: 'div',
			className: 'bb-rl-suggestions-placehoder',
			id: 'bb-rl-whats-new-placeholder',
			attributes: {
				name: 'whats-new-placeholder',
				cols: '50',
				rows: '4',
				placeholder: bbRlActivity.strings.whatsnewPlaceholder,
				'aria-label': bbRlActivity.strings.whatsnewLabel,
				contenteditable: true,
			},
		}
	);

	bp.Views.PostGifProfile = bp.View.extend(
		{
			initialize: function () {
				// check gif is enable in profile or not.
				var $postGif = $( '#bb-rl-whats-new-toolbar .bb-rl-post-gif' );
				if ( _.isUndefined( bbRlMedia ) || _.isUndefined( bbRlMedia.gif ) || ( !_.isUndefined( bbRlMedia.gif.profile ) && bbRlMedia.gif.profile === false ) || _.isUndefined( bbRlMedia.gif_api_key ) || bbRlMedia.gif_api_key === '' ) {
					$postGif.removeClass( 'active' ).addClass( 'bb-rl-post-gif-hide' );
				} else {
					$postGif.removeClass( 'bb-rl-post-gif-hide' );
				}
			},
		}
	);

	bp.Views.PostGifGroup = bp.View.extend(
		{
			initialize: function () {
				// check gif is enable in groups or not.
				var $postGif = $( '#bb-rl-whats-new-toolbar .bb-rl-post-gif' );
				if ( _.isUndefined( bbRlMedia ) || _.isUndefined( bbRlMedia.gif ) || ( !_.isUndefined( bbRlMedia.gif.groups ) && bbRlMedia.gif.groups === false ) || _.isUndefined( bbRlMedia.gif_api_key ) || bbRlMedia.gif_api_key === '' ) {
					$postGif.removeClass( 'active' ).addClass( 'bb-rl-post-gif-hide' );
				} else {
					$postGif.removeClass( 'bb-rl-post-gif-hide' );
				}
			},
		}
	);

	bp.mediaUtilities                    = bp.mediaUtilities || {};
	bp.mediaUtilities.handleMediaSupport = function ( options, model ) {
		var typeSupport          = $( '#bb-rl-whats-new-toolbar' ),
			$postEmoji           = $( '#bb-rl-editor-toolbar .bb-rl-post-emoji' ),
			$whatsNewAttachments = $( '#bb-rl-whats-new-attachments' ),
			context              = model && model.get( 'object' ) ? 'group' : 'user',
			types                = ['media', 'document', 'video'];

		types.forEach(
			function ( type ) {
				var subtype        = 'document' === type ? 'media' : type,
				activityKeyGroup   = 'group_' + type,
				activityKeyProfile = 'profile_' + type;
				if ( 'groups' === context ) {
					if ( false === bbRlMedia[ activityKeyGroup ] ) {
						var validateDropZone = false;
						if (
						'undefined' === typeof options.dropzoneObj ||
						null === options.dropzoneObj ||
						'bb-rl-activity-post-' + type + '-uploader' === options.dropzoneObj.element.id
						) {
								validateDropZone = true;
						}
						if ( validateDropZone || options.bypassValidateDropZone ) {
							typeSupport.find( '.bb-rl-post-' + subtype + '.bb-rl-' + type + '-support' ).removeClass( 'active' ).addClass( 'bb-rl-' + type + '-support-hide' );
							if ( options.triggerEvent ) {
								Backbone.trigger( 'activity_' + type + '_close' );
							}
						}
						if ( options.closeDropzone ) {
							$whatsNewAttachments.find( '.dropzone.media-dropzone' ).removeClass( 'open dz-clickable' ).addClass( 'closed' );
						}
					} else {
						typeSupport.find( '.bb-rl-post-' + subtype + '.bb-rl-' + type + '-support' ).removeClass( 'bb-rl-' + type + '-support-hide' );
					}
				} else {
					if ( false === bbRlMedia[ activityKeyProfile ] ) {
						typeSupport.find( '.bb-rl-post-' + subtype + '.bb-rl-' + type + '-support' ).removeClass( 'active' ).addClass( 'bb-rl-' + type + '-support-hide' );
						$whatsNewAttachments.find( '.dropzone.media-dropzone' ).removeClass( 'open dz-clickable' ).addClass( 'closed' );
					} else {
						typeSupport.find( '.bb-rl-post-' + subtype + '.bb-rl-' + type + '-support' ).removeClass( 'bb-rl-' + type + '-support-hide' );
					}
				}
			}
		);

		var emojiElement = $( '#bb-rl-whats-new-textarea' ).find( 'img.bb-rl-emojioneemoji' ),
			contextKey   = context === 'groups' ? 'groups' : 'profile';
		if ( 'groups' === context ) {
			bp.Nouveau.Activity.postForm.postGifGroup = new bp.Views.PostGifGroup( { model : this.model } );
		} else {
			bp.Nouveau.Activity.postForm.postGifProfile = new bp.Views.PostGifProfile( { model : this.model } );
		}

		// Check if emoji is enabled for the current context.
		if ( ! _.isUndefined( bbRlMedia ) && ! _.isUndefined( bbRlMedia.emoji ) && ! _.isUndefined( bbRlMedia.emoji[ contextKey ] ) && false === bbRlMedia.emoji[ contextKey ] ) {
			emojiElement.remove();
			$postEmoji.addClass( 'bb-rl-post-emoji-hide' );
		} else {
			$postEmoji.removeClass( 'bb-rl-post-emoji-hide' );
		}
		$( '.medium-editor-toolbar' ).removeClass( 'active medium-editor-toolbar-active' );
	};

	bp.Nouveau.Activity.postForm.start();

} )( bp, jQuery );
