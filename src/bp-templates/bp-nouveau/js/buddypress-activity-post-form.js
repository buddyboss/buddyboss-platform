/* global bp, BP_Nouveau, _, Backbone, tinymce, bp_media_dropzone */
/* @version 3.1.0 */
/*jshint esversion: 6 */
window.wp = window.wp || {};
window.bp = window.bp || {};

( function ( exports, $ ) {
	bp.Nouveau = bp.Nouveau || {};

	// Bail if not set.
	if ( typeof bp.Nouveau.Activity === 'undefined' || typeof BP_Nouveau === 'undefined' ) {
		return;
	}

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

			if ( ! _.isUndefined( window.Dropzone ) && ! _.isUndefined( BP_Nouveau.media ) ) {
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
			this.model = new bp.Models.Activity(
				_.pick(
					BP_Nouveau.activity.params,
					[ 'user_id', 'item_id', 'object' ]
				)
			);
			// Do not carry on if the main element is not available.
			if ( ! $( '#bp-nouveau-activity-form' ).length ) {
				return;
			}

			// Create the BuddyPress Uploader.
			this.postForm = new bp.Views.PostForm();

			// Add it to views.
			this.views.add( { id: 'post_form', view: this.postForm } );

			// Display it.
			this.postForm.inject( '#bp-nouveau-activity-form' );

			// Wrap Avatar and Content section into header.
			$( '.activity-update-form #user-status-huddle, .activity-update-form #whats-new-content, .activity-update-form  #whats-new-attachments' ).wrapAll( '<div class="whats-new-form-header"></div>' );

			var $this = this;

			$( document ).on(
				'click',
				'.activity-update-form.modal-popup:not(.bp-activity-edit) .activity-update-form-overlay',
				function() {

					// Store data forcefully.
					if ( ! $this.postForm.$el.hasClass( 'bp-activity-edit' ) ) {
						bp.Nouveau.Activity.postForm.clearDraftInterval();
						bp.Nouveau.Activity.postForm.collectDraftActivity();
						bp.Nouveau.Activity.postForm.postDraftActivity( false, false );
					}

					setTimeout(
						function() {
							$( '.activity-update-form.modal-popup #whats-new' ).blur();
							$( '.activity-update-form.modal-popup #aw-whats-new-reset' ).trigger( 'click' );
							// Post activity hide modal.
							var $singleActivityFormWrap = $( '#bp-nouveau-single-activity-edit-form-wrap' );
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
			if ( ! $( '#bp-nouveau-activity-form-placeholder' ).length ) {
				return;
			}

			// Create placeholder.
			this.postFormPlaceholder = new bp.Views.PostFormPlaceholder();

			// Add it to views collection.
			this.views.add( { id: 'post_form_placeholder', view: this.postFormPlaceholder } );

			// Display it within selector.
			this.postFormPlaceholder.inject( '#bp-nouveau-activity-form-placeholder' );

			$( '.activity-form-placeholder #user-status-huddle, .activity-form-placeholder #whats-new-content-placeholder' ).wrapAll( '<div class="whats-new-form-header"></div>' );
		},

		dropzoneView: function () {
			this.dropzone = null;

			// set up dropzones auto discover to false so it does not automatically set dropzones.
			window.Dropzone.autoDiscover = false;

			this.dropzone_options = {
				url                 		: BP_Nouveau.ajaxurl,
				timeout             		: 3 * 60 * 60 * 1000,
				dictFileTooBig      		: BP_Nouveau.media.dictFileTooBig,
				dictDefaultMessage  		: BP_Nouveau.media.dropzone_media_message,
				acceptedFiles       		: 'image/*',
				autoProcessQueue    		: true,
				addRemoveLinks      		: true,
				uploadMultiple      		: false,
				maxFiles            		: ! _.isUndefined( BP_Nouveau.media.maxFiles ) ? BP_Nouveau.media.maxFiles : 10,
				maxFilesize         		: ! _.isUndefined( BP_Nouveau.media.max_upload_size ) ? BP_Nouveau.media.max_upload_size : 2,
				dictMaxFilesExceeded		: BP_Nouveau.media.media_dict_file_exceeded,
				dictCancelUploadConfirmation: BP_Nouveau.media.dictCancelUploadConfirmation,
				// previewTemplate : document.getElementsByClassName( 'activity-post-media-template' )[0].innerHTML.
				maxThumbnailFilesize: ! _.isUndefined( BP_Nouveau.media.max_upload_size ) ? BP_Nouveau.media.max_upload_size : 2,
			};

			// if defined, add custom dropzone options.
			if ( ! _.isUndefined( BP_Nouveau.media.dropzone_options ) ) {
				Object.assign( this.dropzone_options, BP_Nouveau.media.dropzone_options );
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
			self.postForm.$el.addClass( 'bp-activity-edit' ).addClass( 'loading' );
			self.postForm.$el.find( '.bp-activity-privacy__label-group' ).hide().find( 'input#group' ).attr( 'disabled', true ); // disable group visibility level.
			self.postForm.$el.removeClass( 'bp-hide' );
			self.postForm.$el.find( '#whats-new-toolbar' ).addClass( 'hidden' );

			// add a pause to form to let it cool down a bit.
			setTimeout(
				function() {

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
		 */
		displayEditActivityForm : function( activity_data, activity_URL_preview ) {
			var self = this;

			var $activityForm            = $( '#bp-nouveau-activity-form' );
			var $activityFormPlaceholder = $( '#bp-nouveau-activity-form-placeholder' );
			var $singleActivityFormWrap  = $( '#bp-nouveau-single-activity-edit-form-wrap' );

			if ( $singleActivityFormWrap.length ) {
				$singleActivityFormWrap.show();
			}

			// Set the global variable for the edit activity privacy/album_id/folder_id/group_id maintain.
			bp.privacyEditable = activity_data.can_edit_privacy;
			bp.album_id        = activity_data.album_id;
			bp.folder_id       = activity_data.folder_id;
			bp.group_id        = activity_data.group_id;
			bp.privacy         = activity_data.privacy;

			// Set the activity value.
			self.displayEditActivity( activity_data, activity_URL_preview );
			this.model.set( 'edit_activity', true );

			var edit_activity_editor         = $( '#whats-new' )[0];
			var edit_activity_editor_content = $( '#whats-new-content' )[0];

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

			window.activity_edit_editor.subscribe( 'editablePaste', function ( e ) {
				setTimeout( function() {
					// Wrap all target <li> elements in a single <ul>
					var targetLiElements = $(e.target).find('li').filter(function() {
						return !$(this).parent().is('ul') && !$(this).parent().is('ol');
					});
					if (targetLiElements.length > 0) {
						targetLiElements.wrapAll('<ul></ul>');
					}
				}, 0 );
			});

			// Now Show the Modal.
			$activityForm.addClass( 'modal-popup' ).closest('body').addClass( 'activity-modal-open' );

			$activityFormPlaceholder.show();

			setTimeout(
				function() {
					$( '#whats-new img.emoji' ).each(
						function( index, Obj) {
							$( Obj ).addClass( 'emojioneemoji' );
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
			var self = this;

			$( document ).on(
				'keyup',
				function ( event ) {
					if ( event.keyCode === 27 && false === event.ctrlKey ) {
						$( '.activity-update-form.modal-popup.bp-activity-edit #aw-whats-new-reset' ).trigger( 'click' );
					}
				}
			);

			$( document ).on(
				'click',
				'.activity-update-form.modal-popup.bp-activity-edit #aw-whats-new-reset',
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

			$( '.activity-update-form.modal-popup' ).removeClass( 'modal-popup group-activity' ).closest( 'body' ).removeClass( 'activity-modal-open' );

			var $activityFormPlaceholder = $( '#bp-nouveau-activity-form-placeholder' );
			var $singleActivityFormWrap  = $( '#bp-nouveau-single-activity-edit-form-wrap' );
			var $tabActivityFormWrap     = $( '#bp-nouveau-activity-form' );

			// unwrap hw wrapped content section.
			if ( $( '#whats-new-content' ).parent().is( '.edit-activity-content-wrap' ) ) {
				$( '#whats-new-content' ).unwrap();
			}

			$activityFormPlaceholder.hide();

			if ( $singleActivityFormWrap.length ) {
				$singleActivityFormWrap.hide();
			}

			if ( $tabActivityFormWrap.hasClass( 'is-bp-hide' ) ) {
				$tabActivityFormWrap.addClass( 'bp-hide' );
			}
		},

		createThumbnailFromUrl: function ( mock_file ) {
			var self = this;
			self.dropzone.createThumbnailFromUrl(
				mock_file,
				self.dropzone.options.thumbnailWidth,
				self.dropzone.options.thumbnailHeight,
				self.dropzone.options.thumbnailMethod,
				true,
				function ( thumbnail ) {
					self.dropzone.emit( 'thumbnail', mock_file, thumbnail );
					self.dropzone.emit( 'complete', mock_file );
				}
			);
		},

		displayEditDraftActivityData: function ( activity_data, bpActivityEvent, activity_URL_preview ) {
			var self = this;

			self.postForm.$el.parent( '#bp-nouveau-activity-form' ).removeClass( 'bp-hide' );
			self.postForm.$el.find( '#whats-new' ).html( activity_data.content );
			if( activity_URL_preview != null ) {
				self.postForm.$el.find( '#whats-new' ).data( 'activity-url-preview', activity_URL_preview );
			}
			var element = self.postForm.$el.find( '#whats-new' ).get( 0 );
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

				self.postForm.$el.find( '#bp-activity-id' ).val( activity_data.id );
			} else {
				activity_data.gif          = activity_data.gif_data;
				activity_data.group_name   = activity_data.item_name;
				activity_data.group_avatar = activity_data.group_image;

				if ( 'group' === activity_data.object ) {
					activity_data.object = 'groups';
				}
			}
			// Set link image index and confirm image index.
			self.postForm.model.set( 'link_image_index', activity_data.link_image_index_save );
			self.postForm.model.set( 'link_image_index_save', activity_data.link_image_index_save );

			var tool_box = $( '.activity-form.focus-in #whats-new-toolbar' );

			if ( ! _.isUndefined( self.activityToolbar ) ) {
				// close and destroy existing gif instance.
				self.activityToolbar.closeGifSelector( bpActivityEvent );
				// close and destroy existing media instance.
				self.activityToolbar.closeMediaSelector( bpActivityEvent );
				// close and destroy existing document instance.
				self.activityToolbar.closeDocumentSelector( bpActivityEvent );
				// close and destroy existing video instance.
				self.activityToolbar.closeVideoSelector( bpActivityEvent );
			}

			// Inject GIF.
			if ( ! _.isUndefined( activity_data.gif ) && Object.keys( activity_data.gif ).length ) {
				// close and destroy existing media instance.
				self.activityToolbar.toggleGifSelector( bpActivityEvent );
				self.activityToolbar.gifMediaSearchDropdownView.model.set( 'gif_data', activity_data.gif );

				// Make tool box button disable.
				if ( tool_box.find( '#activity-media-button' ) ) {
					tool_box.find( '#activity-media-button' ).parents( '.post-elements-buttons-item' ).addClass( 'disable' );
				}
				if ( tool_box.find( '#activity-document-button' ) ) {
					tool_box.find( '#activity-document-button' ).parents( '.post-elements-buttons-item' ).addClass( 'disable' );
				}
				if ( tool_box.find( '#activity-video-button' ) ) {
					tool_box.find( '#activity-video-button' ).parents( '.post-elements-buttons-item' ).addClass( 'disable' );
				}
				if ( tool_box.find( '#activity-gif-button' ) ) {
					tool_box.find( '#activity-gif-button' ).parents( '.post-elements-buttons-item' ).addClass( 'active' );
				}
				// END Toolbox Button.
			}

			// Inject medias.
			if ( ! _.isUndefined( activity_data.media ) && activity_data.media.length ) {
				// open media uploader for editing media.
				if ( ! _.isUndefined( self.activityToolbar ) ) {
					self.activityToolbar.toggleMediaSelector( bpActivityEvent );
				}

				// Make tool box button disable.
				if ( tool_box.find( '#activity-media-button' ) ) {
					tool_box.find( '#activity-media-button' ).parents( '.post-elements-buttons-item' ).addClass( 'active no-click' );
				}
				if ( tool_box.find( '#activity-document-button' ) ) {
					tool_box.find( '#activity-document-button' ).parents( '.post-elements-buttons-item' ).addClass( 'disable' );
				}
				if ( tool_box.find( '#activity-video-button' ) ) {
					tool_box.find( '#activity-video-button' ).parents( '.post-elements-buttons-item' ).addClass( 'disable' );
				}
				if ( tool_box.find( '#activity-gif-button' ) ) {
					tool_box.find( '#activity-gif-button' ).parents( '.post-elements-buttons-item' ).addClass( 'disable' );
				}
				// END Toolbox Button.

				var mock_file = false;
				for ( var i = 0; i < activity_data.media.length; i++ ) {
					mock_file = false;

					var media_edit_data = {};
					if ( 0 < parseInt( activity_data.id ) ) {
						media_edit_data = {
							'id': activity_data.media[ i ].attachment_id,
							'media_id': activity_data.media[ i ].id,
							'name': activity_data.media[ i ].name,
							'thumb': activity_data.media[ i ].thumb,
							'url': activity_data.media[ i ].url,
							'uuid': activity_data.media[ i ].attachment_id,
							'menu_order': activity_data.media[ i ].menu_order,
							'album_id': activity_data.media[ i ].album_id,
							'group_id': activity_data.media[ i ].group_id,
							'saved': true
						};
					} else {
						media_edit_data = {
							'id': activity_data.media[ i ].id,
							'name': activity_data.media[ i ].name,
							'thumb': activity_data.media[ i ].thumb,
							'url': activity_data.media[ i ].url,
							'uuid': activity_data.media[ i ].id,
							'menu_order': activity_data.media[ i ].menu_order,
							'album_id': activity_data.media[ i ].album_id,
							'group_id': activity_data.media[ i ].group_id,
							'saved': false
						};
					}

					mock_file = {
						name: activity_data.media[ i ].title,
						accepted: true,
						kind: 'image',
						upload: {
							filename: activity_data.media[ i ].title,
							uuid: activity_data.media[ i ].attachment_id
						},
						dataURL: activity_data.media[ i ].url,
						id: activity_data.media[ i ].attachment_id,
						media_edit_data: media_edit_data
					};

					if ( self.dropzone ) {
						self.dropzone.files.push( mock_file );
						self.dropzone.emit( 'addedfile', mock_file );
						self.createThumbnailFromUrl( mock_file );
						self.dropzone.emit( 'dz-success' );
						self.dropzone.emit( 'dz-complete' );
					}
				}
			}

			// Inject Documents.
			if ( ! _.isUndefined( activity_data.document ) && activity_data.document.length ) {
				// open document uploader for editing document.

				if ( ! _.isUndefined( self.activityToolbar ) ) {
					self.activityToolbar.toggleDocumentSelector( bpActivityEvent );
				}

				// Make tool box button disable.
				if ( tool_box.find( '#activity-media-button' ) ) {
					tool_box.find( '#activity-media-button' ).parents( '.post-elements-buttons-item' ).addClass( 'disable' );
				}
				if ( tool_box.find( '#activity-video-button' ) ) {
					tool_box.find( '#activity-video-button' ).parents( '.post-elements-buttons-item' ).addClass( 'disable' );
				}
				if ( tool_box.find( '#activity-document-button' ) ) {
					tool_box.find( '#activity-document-button' ).parents( '.post-elements-buttons-item' ).addClass( 'active no-click' );
				}
				if ( tool_box.find( '#activity-gif-button' ) ) {
					tool_box.find( '#activity-gif-button' ).parents( '.post-elements-buttons-item' ).addClass( 'disable' );
				}
				// END Toolbox Button.

				var doc_file = false;
				for ( var doci = 0; doci < activity_data.document.length; doci++ ) {
					doc_file = false;

					var document_edit_data = {};
					if ( 0 < parseInt( activity_data.id ) ) {
						document_edit_data = {
							'id': activity_data.document[ doci ].doc_id,
							'name': activity_data.document[ doci ].full_name,
							'full_name': activity_data.document[ doci ].full_name,
							'type': 'document',
							'url': activity_data.document[ doci ].url,
							'size': activity_data.document[ doci ].size,
							'uuid': activity_data.document[ doci ].doc_id,
							'document_id': activity_data.document[ doci ].id,
							'menu_order': activity_data.document[ doci ].menu_order,
							'folder_id': activity_data.document[ doci ].folder_id,
							'group_id': activity_data.document[ doci ].group_id,
							'saved': true,
							'svg_icon': !_.isUndefined( activity_data.document[ doci ].svg_icon ) ? activity_data.document[ doci ].svg_icon : ''
						};
					} else {
						document_edit_data = {
							'id': activity_data.document[ doci ].id,
							'name': activity_data.document[ doci ].full_name,
							'full_name': activity_data.document[ doci ].full_name,
							'type': 'document',
							'url': activity_data.document[ doci ].url,
							'size': activity_data.document[ doci ].size,
							'uuid': activity_data.document[ doci ].id,
							'menu_order': activity_data.document[ doci ].menu_order,
							'folder_id': activity_data.document[ doci ].folder_id,
							'group_id': activity_data.document[ doci ].group_id,
							'saved': false,
							'svg_icon': !_.isUndefined( activity_data.document[ doci ].svg_icon ) ? activity_data.document[ doci ].svg_icon : ''
						};
					}

					doc_file = {
						name: activity_data.document[ doci ].full_name,
						size: activity_data.document[ doci ].size,
						accepted: true,
						kind: 'file',
						upload: {
							filename: activity_data.document[ doci ].full_name,
							uuid: activity_data.document[ doci ].doc_id
						},
						dataURL: activity_data.document[ doci ].url,
						id: activity_data.document[ doci ].doc_id,
						document_edit_data: document_edit_data,
						svg_icon: !_.isUndefined( activity_data.document[ doci ].svg_icon ) ? activity_data.document[ doci ].svg_icon : ''
					};

					if ( self.dropzone ) {
						self.dropzone.files.push( doc_file );
						self.dropzone.emit( 'addedfile', doc_file );
						self.dropzone.emit( 'complete', doc_file );
					}
				}
			}

			// Inject Videos.
			if ( ! _.isUndefined( activity_data.video ) && activity_data.video.length ) {

				if ( ! _.isUndefined( self.activityToolbar ) ) {
					self.activityToolbar.toggleVideoSelector( bpActivityEvent );
				}

				// Make tool box button disable.
				if ( tool_box.find( '#activity-media-button' ) ) {
					tool_box.find( '#activity-media-button' ).parents( '.post-elements-buttons-item' ).addClass( 'disable' );
				}
				if ( tool_box.find( '#activity-document-button' ) ) {
					tool_box.find( '#activity-document-button' ).parents( '.post-elements-buttons-item' ).addClass( 'disable' );
				}
				if ( tool_box.find( '#activity-video-button' ) ) {
					tool_box.find( '#activity-video-button' ).parents( '.post-elements-buttons-item' ).addClass( 'active no-click' );
				}
				if ( tool_box.find( '#activity-gif-button' ) ) {
					tool_box.find( '#activity-gif-button' ).parents( '.post-elements-buttons-item' ).addClass( 'disable' );
				}
				// END Toolbox Button.

				var video_file = false;
				for ( var vidi = 0; vidi < activity_data.video.length; vidi++ ) {
					video_file = false;

					var video_edit_data = {};
					if ( 0 < parseInt( activity_data.id ) ) {
						video_edit_data = {
							'id': activity_data.video[ vidi ].vid_id,
							'name': activity_data.video[ vidi ].name,
							'type': 'video',
							'thumb': activity_data.video[ vidi ].thumb,
							'url': activity_data.video[ vidi ].url,
							'size': activity_data.video[ vidi ].size,
							'uuid': activity_data.video[ vidi ].vid_id,
							'video_id': activity_data.video[ vidi ].id,
							'menu_order': activity_data.video[ vidi ].menu_order,
							'album_id': activity_data.video[ vidi ].album_id,
							'group_id': activity_data.video[ vidi ].group_id,
							'saved': true
						};
					} else {
						video_edit_data = {
							'id': activity_data.video[ vidi ].id,
							'name': activity_data.video[ vidi ].name,
							'type': 'video',
							'thumb': activity_data.video[ vidi ].thumb,
							'url': activity_data.video[ vidi ].url,
							'size': activity_data.video[ vidi ].size,
							'uuid': activity_data.video[ vidi ].id,
							'menu_order': activity_data.video[ vidi ].menu_order,
							'album_id': activity_data.video[ vidi ].album_id,
							'group_id': activity_data.video[ vidi ].group_id,
							'saved': false,
						};
					}

					video_file = {
						name: activity_data.video[ vidi ].name,
						size: activity_data.video[ vidi ].size,
						accepted: true,
						kind: 'file',
						upload: {
							filename: activity_data.video[ vidi ].name,
							uuid: activity_data.video[ vidi ].vid_id
						},
						dataURL: activity_data.video[ vidi ].url,
						id: activity_data.video[ vidi ].vid_id,
						video_edit_data: video_edit_data
					};

					if ( self.dropzone ) {
						self.dropzone.files.push( video_file );
						self.dropzone.emit( 'addedfile', video_file );
						self.dropzone.emit( 'complete', video_file );
					}

				}

			}

			self.postForm.$el.find( '#whats-new' ).trigger( 'keyup' );
			self.postForm.$el.removeClass( 'loading' );

			// Update privacy status label.
			var privacy_label = self.postForm.$el.find( '#' + activity_data.privacy ).data( 'title' );
			self.postForm.$el.find( '#bp-activity-privacy-point' ).removeClass().addClass( activity_data.privacy );
			self.postForm.$el.find( '.bp-activity-privacy-status' ).text( privacy_label );
			self.postForm.$el.find( '.bp-activity-privacy__input#' + activity_data.privacy ).prop( 'checked', true );

			// Update privacy status.
			var privacy            = $( '[data-bp-list="activity"] #activity-' + activity_data.id ).find( 'ul.activity-privacy li.selected' ).data( 'value' ),
				privacy_edit_label = $( '[data-bp-list="activity"] #activity-' + activity_data.id ).find( 'ul.activity-privacy li.selected' ).text();

			if ( ! _.isUndefined( privacy ) ) {
				self.postForm.$el.find( '#bp-activity-privacy-point' ).removeClass().addClass( privacy );
				self.postForm.$el.find( '.bp-activity-privacy-status' ).text( privacy_edit_label );
				self.postForm.$el.find( '.bp-activity-privacy__input#' + privacy ).prop( 'checked', true );
			}

			if ( ! _.isUndefined( activity_data ) ) {
				if ( ! _.isUndefined( activity_data.object ) && ! _.isUndefined( activity_data.item_id ) && 'groups' === activity_data.object ) {

					// check media is enable in groups or not.
					if ( ! _.isUndefined( activity_data.group_media ) && false === activity_data.group_media ) {
						$( '#whats-new-toolbar .post-media.media-support' ).removeClass( 'active' ).addClass( 'media-support-hide' );
						$( '.edit-activity-content-wrap #whats-new-attachments .activity-media-container #activity-post-media-uploader .dz-default.dz-message' ).hide();
					} else {
						$( '#whats-new-toolbar .post-media.media-support' ).removeClass( 'media-support-hide' );
					}

					// check document is enable in groups or not.
					if ( ! _.isUndefined( activity_data.group_document ) && false === activity_data.group_document ) {
						$( '#whats-new-toolbar .post-media.document-support' ).removeClass( 'active' ).addClass( 'document-support-hide' );
						$( '.edit-activity-content-wrap #whats-new-attachments .activity-document-container #activity-post-document-uploader .dz-default.dz-message' ).hide();
					} else {
						$( '#whats-new-toolbar .post-media.document-support' ).removeClass( 'document-support-hide' );
					}

					// check video is enable in groups or not.
					if ( ! _.isUndefined( activity_data.group_video ) && false === activity_data.group_video ) {
						$( '#whats-new-toolbar .post-video.video-support' ).removeClass( 'active' ).addClass( 'video-support-hide' );
						$( '.edit-activity-content-wrap #whats-new-attachments .activity-video-container #activity-post-video-uploader .dz-default.dz-message' ).hide();
					} else {
						$( '#whats-new-toolbar .post-video.video-support' ).removeClass( 'video-support-hide' );
					}

					bp.Nouveau.Activity.postForm.postGifGroup = new bp.Views.PostGifGroup( { model: this.model } );

					// check emoji is enable in groups or not.
					if ( ! _.isUndefined( BP_Nouveau.media.emoji.groups ) && false === BP_Nouveau.media.emoji.groups ) {
						$( '#whats-new-textarea' ).find( 'img.emojioneemoji' ).remove();
						$( '#editor-toolbar .post-emoji' ).addClass( 'post-emoji-hide' );
					} else {
						$( '#editor-toolbar .post-emoji' ).removeClass( 'post-emoji-hide' );
					}

				} else {
					// check media is enable in profile or not.
					if ( ! _.isUndefined( activity_data.profile_media ) && false === activity_data.profile_media ) {
						$( '#whats-new-toolbar .post-media.media-support' ).removeClass( 'active' ).addClass( 'media-support-hide' );
						$( '.activity-media-container #activity-post-media-uploader .dz-default.dz-message' ).hide();
						$( '.activity-media-container' ).css( 'pointer-events', 'none' );
					} else {
						$( '.activity-media-container' ).css( 'pointer-events', 'auto' );
						$( '#whats-new-toolbar .post-media.media-support' ).removeClass( 'media-support-hide' );
					}

					// check document is enable in profile or not.
					if ( ! _.isUndefined( activity_data.profile_document ) && false === activity_data.profile_document ) {
						$( '#whats-new-toolbar .post-media.document-support' ).removeClass( 'active' ).addClass( 'document-support-hide' );
						$( '.activity-document-container #activity-post-document-uploader .dz-default.dz-message' ).hide();
						$( '.activity-document-container' ).css( 'pointer-events', 'none' );
					} else {
						$( '.activity-document-container' ).css( 'pointer-events', 'auto' );
						$( '#whats-new-toolbar .post-media.document-support' ).removeClass( 'document-support-hide' );
					}

					// check video is enable in profile or not.
					if ( ! _.isUndefined( activity_data.profile_video ) && false === activity_data.profile_video ) {
						$( '#whats-new-toolbar .post-video.video-support' ).removeClass( 'active' ).addClass( 'video-support-hide' );
						$( '.activity-video-container #activity-post-video-uploader .dz-default.dz-message' ).hide();
						$( '.activity-video-container' ).css( 'pointer-events', 'none' );
					} else {
						$( '.activity-video-container' ).css( 'pointer-events', 'auto' );
						$( '#whats-new-toolbar .post-video.video-support' ).removeClass( 'video-support-hide' );
					}

					bp.Nouveau.Activity.postForm.postGifProfile = new bp.Views.PostGifProfile( {model: this.model} );

					// check emoji is enable in profile or not.
					if ( ! _.isUndefined( BP_Nouveau.media.emoji.profile ) && false === BP_Nouveau.media.emoji.profile ) {
						$( '#whats-new-textarea' ).find( 'img.emojioneemoji' ).remove();
						$( '#editor-toolbar .post-emoji' ).addClass( 'post-emoji-hide' );
					} else {
						$( '#editor-toolbar .post-emoji' ).removeClass( 'post-emoji-hide' );
					}

				}
			}

			// set object of activity and item id when group activity.
			if ( ! _.isUndefined( activity_data.object ) && ! _.isUndefined( activity_data.item_id ) && 'groups' === activity_data.object ) {
				self.postForm.model.set( 'item_id', activity_data.item_id );
				self.postForm.model.set( 'object', 'group' );
				self.postForm.model.set( 'group_name', activity_data.group_name );

				self.postForm.$el.find( 'input#group' ).prop( 'checked', true );
				if ( 0 < parseInt( activity_data.id ) ) {
					self.postForm.$el.find( '#bp-activity-privacy-point' ).removeClass().addClass( 'group bp-activity-edit-group' );
				} else {
					if ( ! _.isUndefined( bp.draft_activity ) && '' !== bp.draft_activity.object && 'group' === bp.draft_activity.object && bp.draft_activity.data && '' !== bp.draft_activity.data ) {
						self.postForm.$el.find( '#bp-activity-privacy-point' ).removeClass().addClass( 'group bp-activity-edit-group' );
					} else {
						self.postForm.$el.find( '#bp-activity-privacy-point' ).removeClass().addClass( 'group' );
					}
				}

				self.postForm.$el.find( '#bp-activity-privacy-point' ).find( 'i.bb-icon-angle-down' ).remove();
				self.postForm.$el.find( '.bp-activity-privacy-status' ).text( activity_data.group_name );
				// display group avatar when edit any feed.
				if ( activity_data.group_avatar && false === activity_data.group_avatar.includes( 'mystery-group' ) ) {
					self.postForm.$el.find( '#bp-activity-privacy-point span.privacy-point-icon' ).removeClass( 'privacy-point-icon' ).addClass( 'group-privacy-point-icon' ).html( '<img src="' + activity_data.group_avatar + '" alt=""/>' );
				}
			}

			// Do not allow the edit privacy if activity is belongs to any folder/album.
			if ( ! bp.privacyEditable && 'groups' !== activity_data.object ) {
				self.postForm.$el.addClass( 'bp-activity-edit--privacy-idle' );
			} else {
				self.postForm.$el.removeClass( 'bp-activity-edit--privacy-idle' );
			}

			if ( 0 < parseInt( activity_data.id ) ) {
				Backbone.trigger( 'editactivity' );
			} else {
				self.postForm.$el.removeClass( 'focus-in--empty loading' );
			}

		},

		getCurrentDraftActivity: function () {
			if ( $( 'body' ).hasClass( 'activity' ) && ! _.isUndefined( BP_Nouveau.activity.params.object ) ) {
				bp.draft_activity.object = BP_Nouveau.activity.params.object;

				// Draft activity data.
				bp.draft_activity.data_key = 'draft_' + BP_Nouveau.activity.params.object;
				if ( 'group' === BP_Nouveau.activity.params.object ) {
					bp.draft_activity.data_key = 'draft_' + BP_Nouveau.activity.params.object + '_' + BP_Nouveau.activity.params.item_id;
				} else if ( 0 < BP_Nouveau.activity.params.displayed_user_id ) {
					bp.draft_activity.data_key = 'draft_' + BP_Nouveau.activity.params.object + '_' + BP_Nouveau.activity.params.displayed_user_id;
				}

				var draft_data = localStorage.getItem( bp.draft_activity.data_key );
				if ( ! _.isUndefined( draft_data ) && null !== draft_data && 0 < draft_data.length ) {
					if ( 'deleted' !== $.cookie( bp.draft_activity.data_key ) ) {
				 		// Parse data with JSON.
						var draft_activity_local_data = JSON.parse( draft_data );
						bp.draft_activity.data        = draft_activity_local_data.data;
					} else {
						$.removeCookie( bp.draft_activity.data_key );
					}
				}
			}

			return bp.draft_activity;
		},

		isProfileDraftActivity: function ( activity_data ) {
			if ( ! _.isUndefined( activity_data ) && ! _.isUndefined( activity_data.object ) && ! _.isUndefined( activity_data.item_id ) && 'groups' === activity_data.object ) {
				return false;
			}

			return true;
		},

		displayDraftActivity: function () {
			var activity_data = bp.draft_activity.data,
				$this         = this;

			bp.draft_activity.allow_delete_media = true;

			// Checked the draft is available or doesn't edit activity.
			if ( ! activity_data || $( '#whats-new-form' ).hasClass( 'bp-activity-edit' ) ) {
				return;
			}

			var is_profile_activity = this.isProfileDraftActivity( activity_data );

			// Sync profile/group media.
			activity_data.profile_media = BP_Nouveau.media.profile_media;
			activity_data.group_media   = BP_Nouveau.media.group_media;
			if ( false === activity_data.profile_media && is_profile_activity ) {
				delete activity_data.media;
			} else if ( false === activity_data.group_media && ! is_profile_activity ) {
				delete activity_data.media;
			}

			// Sync profile/group document.
			activity_data.profile_document = BP_Nouveau.media.profile_document;
			activity_data.group_document   = BP_Nouveau.media.group_document;
			if ( false === activity_data.profile_document && is_profile_activity ) {
				delete activity_data.document;
			} else if ( false === activity_data.group_document && ! is_profile_activity ) {
				delete activity_data.document;
			}

			// Sync profile/group video.
			activity_data.profile_video = BP_Nouveau.video.profile_video;
			activity_data.group_video   = BP_Nouveau.video.group_video;
			if ( false === activity_data.profile_video && is_profile_activity ) {
				delete activity_data.video;
			} else if ( false === activity_data.group_video && ! is_profile_activity ) {
				delete activity_data.video;
			}

			// check media is enabled in profile or not.
			if ( false === BP_Nouveau.media.profile_media ) {
				$( '#whats-new-toolbar .post-media.media-support' ).removeClass( 'active' ).addClass( 'media-support-hide' );
				Backbone.trigger( 'activity_media_close' );
			} else {
				$( '#whats-new-toolbar .post-media.media-support' ).removeClass( 'media-support-hide' );
			}

			// check media is enable in profile or not.
			if ( false === BP_Nouveau.media.profile_document ) {
				$( '#whats-new-toolbar .post-media.document-support' ).removeClass( 'active' ).addClass( 'document-support-hide' );
				Backbone.trigger( 'activity_document_close' );
			} else {
				$( '#whats-new-toolbar .post-media.document-support' ).removeClass( 'document-support-hide' );
			}

			// check video is enable in profile or not.
			if ( false === BP_Nouveau.video.profile_video ) {
				$( '#whats-new-toolbar .post-video.video-support' ).removeClass( 'active' ).addClass( 'video-support-hide' );
				Backbone.trigger( 'activity_video_close' );
			} else {
				$( '#whats-new-toolbar .post-video.video-support' ).removeClass( 'video-support-hide' );
			}

			setTimeout(
				function () {

					if ( $( 'body' ).hasClass( 'activity-modal-open' ) ) {

						// Add loader.
						$this.postForm.$el.addClass( 'loading' ).addClass( 'has-draft' );

						var bpActivityEvent = new Event( 'bp_activity_edit' );

						bp.Nouveau.Activity.postForm.displayEditDraftActivityData( activity_data, bpActivityEvent );
					}

				},
				0
			);
		},

		syncDraftActivity: function() {
			if ( ( ! bp.draft_activity.data || '' === bp.draft_activity.data ) && ! _.isUndefined( BP_Nouveau.activity.params.draft_activity.data_key ) ) {

				if ( 'deleted' === $.cookie( bp.draft_activity.data_key ) ) {
					bp.draft_activity.data                    = false;
					BP_Nouveau.activity.params.draft_activity = '';
					localStorage.removeItem( bp.draft_activity.data_key );
					$.removeCookie( bp.draft_activity.data_key );
				} else {
					bp.old_draft_data = BP_Nouveau.activity.params.draft_activity.data;
					bp.draft_activity = BP_Nouveau.activity.params.draft_activity;
					localStorage.setItem( bp.draft_activity.data_key, JSON.stringify( bp.draft_activity ) );
				}

			}
		},

		collectDraftActivity: function() {
			var self = this,
				meta = {};

			if ( _.isUndefined( this.postForm ) || this.postForm.$el.hasClass( 'bp-activity-edit' ) ) {
				return;
			}

			// Set the content and meta.
			_.each(
				self.postForm.$el.serializeArray(),
				function( pair ) {
					pair.name = pair.name.replace( '[]', '' );
					if ( - 1 === _.indexOf( ['aw-whats-new-submit', 'whats-new-post-in'], pair.name ) ) {
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
			var content = $.trim( self.postForm.$el.find( '#whats-new' )[ 0 ].innerHTML.replace( /<div>/gi, '\n' ).replace( /<\/div>/gi, '' ) );
			content     = content.replace( /&nbsp;/g, ' ' );

			self.postForm.model.set( 'content', content, {silent: true} );

			// Silently add meta.
			self.postForm.model.set( meta, {silent: true} );

			var medias = self.postForm.model.get( 'media' );
			if ( 'group' === self.postForm.model.get( 'object' ) && ! _.isUndefined( medias ) && medias.length ) {
				for ( var k = 0; k < medias.length; k ++ ) {
					medias[ k ].group_id = self.postForm.model.get( 'item_id' );
				}
				self.postForm.model.set( 'media', medias );
			} else if ( ! _.isUndefined( medias ) && medias.length ) {
				for ( var md = 0; md < medias.length; md ++ ) {
					delete medias[ md ].group_id;
				}
				self.postForm.model.set( 'media', medias );
			}

			var document = self.postForm.model.get( 'document' );
			if ( 'group' === self.postForm.model.get( 'object' ) && ! _.isUndefined( document ) && document.length ) {
				for ( var d = 0; d < document.length; d ++ ) {
					document[ d ].group_id = self.postForm.model.get( 'item_id' );
				}
				self.postForm.model.set( 'document', document );
			} else if ( ! _.isUndefined( document ) && document.length ) {
				for ( var dd = 0; dd < document.length; dd ++ ) {
					delete document[ dd ].group_id;
				}
				self.postForm.model.set( 'document', document );
			}

			var video = self.postForm.model.get( 'video' );
			if ( 'group' === self.postForm.model.get( 'object' ) && ! _.isUndefined( video ) && video.length ) {
				for ( var v = 0; v < video.length; v ++ ) {
					video[ v ].group_id = self.postForm.model.get( 'item_id' );
				}
				self.postForm.model.set( 'video', video );
			} else if ( ! _.isUndefined( video ) && video.length ) {
				for ( var vd = 0; vd < video.length; vd ++ ) {
					delete video[ vd ].group_id;
				}
				self.postForm.model.set( 'video', video );
			}

			var filtered_content = $( $.parseHTML( content ) ).text().trim();
			if ( content.includes( 'data-emoji-char' ) && '' === filtered_content ) {
				filtered_content = content;
			}

			// validation for content editor.
			if ( '' === filtered_content && ( ( ( ! _.isUndefined( self.postForm.model.get( 'video' ) ) && ! self.postForm.model.get( 'video' ).length ) || _.isUndefined( self.postForm.model.get( 'video' ) ) ) && ( ( ! _.isUndefined( self.postForm.model.get( 'document' ) ) && ! self.postForm.model.get( 'document' ).length ) || _.isUndefined( self.postForm.model.get( 'document' ) ) ) && ( ( ! _.isUndefined( self.postForm.model.get( 'media' ) ) && ! self.postForm.model.get( 'media' ).length ) || _.isUndefined( self.postForm.model.get( 'media' ) ) ) && ( ( ! _.isUndefined( self.postForm.model.get( 'gif_data' ) ) && ! Object.keys( self.postForm.model.get( 'gif_data' ) ).length ) || _.isUndefined( self.postForm.model.get( 'media' ) ) ) ) ) {
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
				data.item_id          = parseInt( bp.draft_activity.data.item_id );
				data.item_name        = bp.draft_activity.data.item_name;
				data.group_image      = bp.draft_activity.data.group_image;
				data['group-privacy'] = 'bp-item-opt-' + bp.draft_activity.data.item_id;

				self.postForm.model.set( 'item_id', parseInt( bp.draft_activity.data.item_id ) );
				self.postForm.model.set( 'item_name', bp.draft_activity.data.item_name );
				self.postForm.model.set( 'group_image', bp.draft_activity.data.group_image );
				self.postForm.model.set( 'group-privacy', 'bp-item-opt-' + bp.draft_activity.data.item_id );
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

		checkedActivityDataChanged: function( old_data, new_data ) {

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
				'link_url'
			];

			_.each(
				draft_data_keys,
				function( pair ) {

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

							if ( -1 !== _.indexOf( [ 'groups', 'group' ], new_data[ pair ] ) && -1 !== _.indexOf( [ 'groups', 'group' ], old_data[ pair ] ) ) {
								bp.draft_content_changed = false;
							} else if ( -1 !== _.indexOf( [ 'user' ], new_data[ pair ] ) && -1 !== _.indexOf( [ 'user' ], old_data[ pair ] ) ) {
								bp.draft_content_changed = false;
							} else {
								bp.draft_content_changed = true;
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

		storeDraftActivity: function() {
			var self = this;

			if ( ! $( 'body' ).hasClass( 'activity-modal-open' ) || self.postForm.$el.hasClass( 'bp-activity-edit' ) ) {
				return;
			}

			bp.Nouveau.Activity.postForm.collectDraftActivity();
		},

		postDraftActivity: function( is_force_saved, is_reload_window ) {

			if ( _.isUndefined( this.postForm ) || this.postForm.$el.hasClass( 'bp-activity-edit' ) ) {
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
					_wpnonce_post_draft: BP_Nouveau.activity.params.post_draft_nonce,
					draft_activity: bp.draft_activity
				};

				// Send data to server.
				bp.draft_ajax_request = bp.ajax.post( 'post_draft_activity', draft_data ).done(
					function () {}
				).fail(
					function () {}
				);

			} else {
				const formData = new FormData();
				formData.append( '_wpnonce_post_draft', BP_Nouveau.activity.params.post_draft_nonce );
				formData.append( 'action', 'post_draft_activity' );
				formData.append( 'draft_activity', JSON.stringify( bp.draft_activity ) );

				navigator.sendBeacon( BP_Nouveau.ajaxurl, formData );
			}

			bp.old_draft_data        = bp.draft_activity.data;
			bp.draft_content_changed = false;
		},

		resetDraftActivity: function( is_send_server ) {
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
		},

		reloadWindow: function() {

			// This will work only for Chrome.
			window.onbeforeunload = function (event) {
				if ( 'undefined' !== typeof event ) {
					bp.Nouveau.Activity.postForm.collectDraftActivity();
					bp.Nouveau.Activity.postForm.postDraftActivity( false, true );
				}
			};

			// This will work only for other browsers.
			window.unload = function (event) {
				if ( 'undefined' !== typeof event ) {
					bp.Nouveau.Activity.postForm.collectDraftActivity();
					bp.Nouveau.Activity.postForm.postDraftActivity( false, true );
				}
			};
		},

		clearDraftInterval: function() {
			clearInterval( bp.draft_local_interval );
			bp.draft_local_interval = false;
			clearInterval( bp.draft_ajax_interval );
			bp.draft_ajax_interval = false;
		}

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

	// Model object, to fetch ajax data for activity group when load more
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
	bp.Collections.fetchCollection = Backbone.Collection.extend( {
		model: bp.Models.fetchData,
		url: BP_Nouveau.ajaxurl
	} );

	/** Views *****************************************************************/

	// Header.
	bp.Views.ActivityHeader = bp.View.extend(
		{
			tagName: 'header',
			id: 'activity-header',
			template: bp.template( 'activity-header' ),
			className: 'bb-model-header',

			events: {
				'click .bb-model-close-button': 'close'
			},

			initialize: function() {
				this.listenTo(Backbone, 'privacy:headerupdate', this.updateHeader);
				this.listenTo(Backbone, 'editactivity', this.updateEditActivityHeader);
				this.model.on( 'change:privacy_modal', this.render, this );
				this.model.on( 'change:edit_activity', this.render, this );
			},

			render: function () {
				this.$el.html( this.template( this.model.toJSON() ) );
				return this;
			},

			updateHeader: function() {
				this.model.set( 'privacy_modal', 'profile' );
			},

			updateEditActivityHeader: function() {
				this.model.set( 'edit_activity', true );
			},

			close: function ( e ) {

				// Store data forcefully.
				if ( ! this.$el.parent().hasClass( 'bp-activity-edit' ) ) {
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

				$( 'body' ).removeClass( 'initial-post-form-open' );
				this.$el.parent().find( '#aw-whats-new-reset' ).trigger( 'click' ); //Trigger reset
				this.model.set( 'privacy_modal', 'general' );

				// Reset group
				// var selected_item = this.$el.closest( '#whats-new-form' ).find( '.bp-activity-object.selected' );
				// selected_item.find( '.privacy-radio' ).removeClass( 'selected' );
				// selected_item.find( '.bp-activity-object__radio' ).prop('checked', false);
				// selected_item.removeClass( 'selected' );

				// Reset privacy status submit button
				this.$el.closest( '#whats-new-form' ).removeClass( 'focus-in--blank-group' );

				// Update privacy editable state class
				this.$el.closest( '#whats-new-form' ).removeClass( 'bp-activity-edit--privacy-idle' );

				// Post activity hide modal
				var $singleActivityFormWrap = $( '#bp-nouveau-single-activity-edit-form-wrap' );
				$singleActivityFormWrap.hide();

				var $tabActivityFormWrap = $( '#bp-nouveau-activity-form' );
				if ( $tabActivityFormWrap.hasClass( 'is-bp-hide' ) ) {
					$tabActivityFormWrap.addClass( 'bp-hide' );
				}

				this.resetMultiMediaOptions();
			},

			resetMultiMediaOptions: function () {

				if( window.activityMediaAction !== null ) {
					$( '.activity-update-form.modal-popup' ).find( '#' + window.activityMediaAction ).trigger( 'click' );
					window.activityMediaAction = null;
				}

				$( '#whats-new-form' ).removeClass( 'focus-in--attm' );

			}
		}
	);

	// Feedback messages.
	bp.Views.activityFeedback = bp.View.extend(
		{
			tagName: 'div',
			id: 'message-feedabck',
			template: bp.template( 'activity-post-form-feedback' ),

			initialize: function () {
				this.model = new Backbone.Model();

				if ( this.options.value ) {
					this.model.set( 'message', this.options.value, { silent: true } );
				}

				this.type = 'info';

				if ( ! _.isUndefined( this.options.type ) && 'info' !== this.options.type ) {
					this.type = this.options.type;
				}

				this.el.className = 'bp-messages bp-feedback ' + this.type;
			}
		}
	);

	// Activity Media.
	bp.Views.ActivityMedia = bp.View.extend(
		{
			tagName: 'div',
			className: 'activity-media-container',
			template: bp.template( 'activity-media' ),
			media: [],

			initialize: function () {

				this.model.set( 'media', this.media );

				this.listenTo( Backbone, 'activity_media_toggle', this.toggle_media_uploader );
				this.listenTo( Backbone, 'activity_media_close', this.destroy );
			},

			toggle_media_uploader: function () {
				var self = this;
				if ( self.$el.find( '#activity-post-media-uploader' ).hasClass( 'open' ) ) {
					self.destroy();
				} else {
					self.open_media_uploader();
				}
			},

			destroy: function () {
				var self = this;
				if ( ! _.isNull( bp.Nouveau.Activity.postForm.dropzone ) ) {
					bp.Nouveau.Activity.postForm.dropzone.destroy();
					self.$el.find( '#activity-post-media-uploader' ).html( '' );
				}
				self.media = [];
				self.$el.find( '#activity-post-media-uploader' ).removeClass( 'open' ).addClass( 'closed' );

				$( '#whats-new-attachments' ).addClass( 'empty' ).closest( '#whats-new-form' ).removeClass( 'focus-in--attm' );
			},

			open_media_uploader: function () {
				var self = this;
				if ( self.$el.find( '#activity-post-media-uploader' ).hasClass( 'open' ) ) {
					return false;
				}
				self.destroy();

				this.dropzone_options = {
					url                 : BP_Nouveau.ajaxurl,
					timeout             : 3 * 60 * 60 * 1000,
					dictFileTooBig      : BP_Nouveau.media.dictFileTooBig,
					dictDefaultMessage  : BP_Nouveau.media.dropzone_media_message,
					acceptedFiles       : 'image/*',
					autoProcessQueue    : true,
					addRemoveLinks      : true,
					uploadMultiple      : false,
					maxFiles            : ! _.isUndefined( BP_Nouveau.media.maxFiles ) ? BP_Nouveau.media.maxFiles : 10,
					maxFilesize         : ! _.isUndefined( BP_Nouveau.media.max_upload_size ) ? BP_Nouveau.media.max_upload_size : 2,
					thumbnailWidth		: 140,
					thumbnailHeight		: 140,
					dictMaxFilesExceeded: BP_Nouveau.media.media_dict_file_exceeded,
					previewTemplate : document.getElementsByClassName( 'activity-post-default-template' )[0].innerHTML,
					dictCancelUploadConfirmation: BP_Nouveau.media.dictCancelUploadConfirmation,
					maxThumbnailFilesize: ! _.isUndefined( BP_Nouveau.media.max_upload_size ) ? BP_Nouveau.media.max_upload_size : 2,
					dictInvalidFileType: bp_media_dropzone.dictInvalidFileType,
				};

				bp.Nouveau.Activity.postForm.dropzone = new window.Dropzone( '#activity-post-media-uploader', this.dropzone_options );

				bp.Nouveau.Activity.postForm.dropzone.on(
					'addedfile',
					function ( file ) {
						if ( file.media_edit_data ) {
							self.media.push( file.media_edit_data );
							self.model.set( 'media', self.media );
						}
					}
				);

				bp.Nouveau.Activity.postForm.dropzone.on(
					'uploadprogress',
					function( element ) {

						self.$el.closest( '#whats-new-form').addClass( 'media-uploading' );

						var circle        = $( element.previewElement ).find( '.dz-progress-ring circle' )[0];
						var radius        = circle.r.baseVal.value;
						var circumference = radius * 2 * Math.PI;

						circle.style.strokeDasharray  = circumference + ' ' + circumference;
						circle.style.strokeDashoffset = circumference;
						var offset                    = circumference - element.upload.progress.toFixed( 0 ) / 100 * circumference;
						circle.style.strokeDashoffset = offset;
					}
				);

				bp.Nouveau.Activity.postForm.dropzone.on(
					'sending',
					function ( file, xhr, formData ) {
						formData.append( 'action', 'media_upload' );
						formData.append( '_wpnonce', BP_Nouveau.nonces.media );

						var tool_box = self.$el.parents( '#whats-new-form' );
						if ( tool_box.find( '#activity-document-button' ) ) {
							tool_box.find( '#activity-document-button' ).parents( '.post-elements-buttons-item' ).addClass( 'disable' );
						}
						if ( tool_box.find( '#activity-video-button' ) ) {
							tool_box.find( '#activity-video-button' ).parents( '.post-elements-buttons-item' ).addClass( 'disable' );
						}
						if ( tool_box.find( '#activity-gif-button' ) ) {
							tool_box.find( '#activity-gif-button' ).parents( '.post-elements-buttons-item' ).addClass( 'disable' );
						}
						if ( tool_box.find( '#activity-media-button' ) ) {
							tool_box.find( '#activity-media-button' ).parents( '.post-elements-buttons-item' ).addClass( 'no-click' );
						}
					}
				);

				bp.Nouveau.Activity.postForm.dropzone.on(
					'success',
					function ( file, response ) {
						if ( response.data.id ) {

							// Set the album_id and group_id if activity belongs to any album and group in edit activity on new uploaded media id.
							if ( ! bp.privacyEditable ) {
								response.data.album_id = bp.album_id;
								response.data.group_id = bp.group_id;
								response.data.privacy  = bp.privacy;
							}

							file.id                  = response.data.id;
							response.data.uuid       = file.upload.uuid;
							response.data.group_id   = ! _.isUndefined( BP_Nouveau.media ) && ! _.isUndefined( BP_Nouveau.media.group_id ) ? BP_Nouveau.media.group_id : false;
							response.data.saved      = false;
							response.data.menu_order = $( file.previewElement ).closest( '.dropzone' ).find( file.previewElement ).index() - 1;
							self.media.push( response.data );
							self.model.set( 'media', self.media );

							var image = $( file.previewElement ).find( '.dz-image img' )[0];
							var isLoaded = image.complete && image.naturalHeight !== 0;
							if (!isLoaded) {
								var node, _i, _len, _ref, _results;
								var message = BP_Nouveau.media.invalid_media_type;
								file.previewElement.classList.add( 'dz-error' );
								_ref     = file.previewElement.querySelectorAll( '[data-dz-errormessage]' );
								_results = [];
								for ( _i = 0, _len = _ref.length; _i < _len; _i++ ) {
									node = _ref[_i];
									_results.push( node.textContent = message );
								}

								// Unset media if all uploaded media has error
								response.data.menu_order_error_count = $( file.previewElement ).closest( '.dropzone' ).find( '.dz-preview.dz-error' ).length;
								if ( self.media.length === response.data.menu_order_error_count ) {
									self.model.unset( 'media' );
								}
								return _results;
							}


						} else {
							Backbone.trigger( 'onError', ( '<div>' + BP_Nouveau.media.invalid_media_type + '. ' + response.data.feedback + '</div>' ) );
							this.removeFile( file );
						}

						bp.draft_content_changed = true;
					}
				);

				bp.Nouveau.Activity.postForm.dropzone.on(
					'error',
					function ( file, response ) {
						if ( file.accepted ) {
							if ( ! _.isUndefined( response ) && ! _.isUndefined( response.data ) && ! _.isUndefined( response.data.feedback ) ) {
								$( file.previewElement ).find( '.dz-error-message span' ).text( response.data.feedback );
							} else if( file.status == 'error' && ( file.xhr && file.xhr.status == 0) ) { // update server error text to user friendly
								$( file.previewElement ).find( '.dz-error-message span' ).text( BP_Nouveau.media.connection_lost_error );
							}
						} else {
							Backbone.trigger( 'onError', ( '<div>' + BP_Nouveau.media.invalid_media_type + '. ' + ( response ? response : '' ) + '</div>' ) );
							this.removeFile( file );
							self.$el.closest( '#whats-new-form').removeClass( 'media-uploading' );
						}
					}
				);

				bp.Nouveau.Activity.postForm.dropzone.on(
					'removedfile',
					function ( file ) {
						if ( true === bp.draft_activity.allow_delete_media ) {
							if ( self.media.length ) {
								for ( var i in self.media ) {
									if ( file.id === self.media[i].id ) {
										if ( !_.isUndefined( self.media[i].saved ) && !self.media[i].saved ) {
											bp.Nouveau.Media.removeAttachment( file.id );
										}
										self.media.splice( i, 1 );
										self.model.set( 'media', self.media );
									} else {
										if ( 'edit' !== bp.draft_activity.display_post && file.media_edit_data ) {
											var attachment_id = file.media_edit_data.id;
											if ( attachment_id === self.media[i].id ) {
												self.media.splice( i, 1 );
												self.model.set( 'media', self.media );
												bp.Nouveau.Media.removeAttachment( attachment_id );
											}
										}
									}
								}

								// Unset media if all uploaded media has error.
								var media_error_count = self.$el.find( '.dz-preview.dz-error' ).length;
								if ( self.media.length === media_error_count ) {
									self.model.unset( 'media' );
								}
							}

							if ( !_.isNull( bp.Nouveau.Activity.postForm.dropzone.files ) && bp.Nouveau.Activity.postForm.dropzone.files.length === 0 ) {
								self.$el.closest( '#whats-new-form').removeClass( 'media-uploading' );
								var tool_box = self.$el.parents( '#whats-new-form' );
								if ( tool_box.find( '#activity-document-button' ) ) {
									tool_box.find( '#activity-document-button' ).parents( '.post-elements-buttons-item' ).removeClass( 'disable no-click' );
								}
								if ( tool_box.find( '#activity-video-button' ) ) {
									tool_box.find( '#activity-video-button' ).parents( '.post-elements-buttons-item' ).removeClass( 'disable no-click' );
								}
								if ( tool_box.find( '#activity-gif-button' ) ) {
									tool_box.find( '#activity-gif-button' ).parents( '.post-elements-buttons-item' ).removeClass( 'disable no-click' );
								}
								if ( tool_box.find( '#activity-media-button' ) ) {
									tool_box.find( '#activity-media-button' ).parents( '.post-elements-buttons-item' ).removeClass( 'no-click' );
								}

								self.model.unset( 'media' );
								if ( $( '#message-feedabck' ).hasClass( 'noMediaError' ) ) {
									self.model.unset( 'errors' );
								}
							}

							bp.draft_content_changed = true;
						}
					}
				);

				// Enable submit button when all medias are uploaded
				bp.Nouveau.Activity.postForm.dropzone.on(
					'complete',
					function() {
						if ( this.getUploadingFiles().length === 0 && this.getQueuedFiles().length === 0 && this.files.length > 0 ) {
							self.$el.closest( '#whats-new-form').removeClass( 'media-uploading' );
						}
					}
				);

				self.$el.find( '#activity-post-media-uploader' ).addClass( 'open' ).removeClass( 'closed' );
				$( '#whats-new-attachments' ).removeClass( 'empty' ).closest( '#whats-new-form' ).addClass( 'focus-in--attm' );
			}

		}
	);

	// Activity Document.
	bp.Views.ActivityDocument = bp.View.extend(
		{
			tagName: 'div',
			className: 'activity-document-container',
			template: bp.template( 'activity-document' ),
			document: [],

			initialize: function () {

				this.model.set( 'document', this.document );

				this.listenTo( Backbone, 'activity_document_toggle', this.toggle_document_uploader );
				this.listenTo( Backbone, 'activity_document_close', this.destroyDocument );
			},

			toggle_document_uploader: function () {

				var self = this;
				if ( self.$el.find( '#activity-post-document-uploader' ).hasClass( 'open' ) ) {
					self.destroyDocument();
				} else {
					self.open_document_uploader();
				}
			},

			destroyDocument: function () {
				var self = this;
				if ( ! _.isNull( bp.Nouveau.Activity.postForm.dropzone ) ) {
					bp.Nouveau.Activity.postForm.dropzone.destroy();
					self.$el.find( '#activity-post-document-uploader' ).html( '' );
				}
				self.document = [];
				self.$el.find( '#activity-post-document-uploader' ).removeClass( 'open' ).addClass( 'closed' );

				$( '#whats-new-attachments' ).addClass( 'empty' ).closest( '#whats-new-form' ).removeClass( 'focus-in--attm' );
			},

			open_document_uploader: function () {
				var self = this;

				if ( self.$el.find( '#activity-post-document-uploader' ).hasClass( 'open' ) ) {
					return false;
				}
				self.destroyDocument();

				var dropzone_options = {
					url                  		: BP_Nouveau.ajaxurl,
					timeout              		: 3 * 60 * 60 * 1000,
					dictFileTooBig       		: BP_Nouveau.media.dictFileTooBig,
					acceptedFiles        		: BP_Nouveau.media.document_type,
					createImageThumbnails		: false,
					dictDefaultMessage   		: BP_Nouveau.media.dropzone_document_message,
					autoProcessQueue     		: true,
					addRemoveLinks       		: true,
					uploadMultiple       		: false,
					maxFiles             		: ! _.isUndefined( BP_Nouveau.document.maxFiles ) ? BP_Nouveau.document.maxFiles : 10,
					maxFilesize          		: ! _.isUndefined( BP_Nouveau.document.max_upload_size ) ? BP_Nouveau.document.max_upload_size : 2,
					dictInvalidFileType  		: BP_Nouveau.document.dictInvalidFileType,
					dictMaxFilesExceeded 		: BP_Nouveau.media.document_dict_file_exceeded,
					previewTemplate 	 		: document.getElementsByClassName( 'activity-post-document-template' )[0].innerHTML,
					dictCancelUploadConfirmation: BP_Nouveau.media.dictCancelUploadConfirmation,
				};

				bp.Nouveau.Activity.postForm.dropzone = new window.Dropzone( '#activity-post-document-uploader', dropzone_options );

				bp.Nouveau.Activity.postForm.dropzone.on(
					'addedfile',
					function ( file ) {
						if ( file.document_edit_data ) {
							self.document.push( file.document_edit_data );
							self.model.set( 'document', self.document );
						}
					}
				);

				bp.Nouveau.Activity.postForm.dropzone.on(
					'uploadprogress',
					function( element ) {

						self.$el.closest( '#whats-new-form').addClass( 'media-uploading' );

						var circle        = $( element.previewElement ).find( '.dz-progress-ring circle' )[0];
						var radius        = circle.r.baseVal.value;
						var circumference = radius * 2 * Math.PI;

						circle.style.strokeDasharray  = circumference + ' ' + circumference;
						circle.style.strokeDashoffset = circumference;
						var offset                    = circumference - element.upload.progress.toFixed( 0 ) / 100 * circumference;
						circle.style.strokeDashoffset = offset;
					}
				);

				bp.Nouveau.Activity.postForm.dropzone.on(
					'sending',
					function ( file, xhr, formData ) {
						formData.append( 'action', 'document_document_upload' );
						formData.append( '_wpnonce', BP_Nouveau.nonces.media );

						var tool_box = self.$el.parents( '#whats-new-form' );
						if ( tool_box.find( '#activity-media-button' ) ) {
							tool_box.find( '#activity-media-button' ).parents( '.post-elements-buttons-item' ).addClass( 'disable' );
						}
						if ( tool_box.find( '#activity-gif-button' ) ) {
							tool_box.find( '#activity-gif-button' ).parents( '.post-elements-buttons-item' ).addClass( 'disable' );
						}
						if ( tool_box.find( '#activity-video-button' ) ) {
							tool_box.find( '#activity-video-button' ).parents( '.post-elements-buttons-item' ).addClass( 'disable' );
						}
						if ( tool_box.find( '#activity-document-button' ) ) {
							tool_box.find( '#activity-document-button' ).parents( '.post-elements-buttons-item' ).addClass( 'no-click' );
						}
					}
				);

				bp.Nouveau.Activity.postForm.dropzone.on(
					'success',
					function ( file, response ) {
						if ( response.data.id ) {

							// Set the folder_id and group_id if activity belongs to any folder and group in edit activity on new uploaded media id.
							if ( ! bp.privacyEditable ) {
								response.data.folder_id = bp.folder_id;
								response.data.group_id  = bp.group_id;
								response.data.privacy   = bp.privacy;
							}

							file.id                  = response.data.id;
							response.data.uuid       = file.upload.uuid;
							response.data.group_id   = ! _.isUndefined( BP_Nouveau.media ) && ! _.isUndefined( BP_Nouveau.media.group_id ) ? BP_Nouveau.media.group_id : false;
							response.data.svg_icon   = ( ! _.isUndefined( response.data.svg_icon ) ? response.data.svg_icon : '' );
							response.data.saved      = false;
							response.data.menu_order = $( file.previewElement ).closest( '.dropzone' ).find( file.previewElement ).index() - 1;
							self.document.push( response.data );
							self.model.set( 'document', self.document );

							var filename      = file.upload.filename;
							var fileExtension = filename.substr( ( filename.lastIndexOf( '.' ) + 1 ) );
							var file_icon     = ( ! _.isUndefined( response.data.svg_icon ) ? response.data.svg_icon : '' );
							var icon_class    = ! _.isEmpty( file_icon ) ? file_icon : 'bb-icon-file-' + fileExtension;

							if ( $( file.previewElement ).find( '.dz-details .dz-icon .bb-icon-file' ).length ) {
								$( file.previewElement ).find( '.dz-details .dz-icon .bb-icon-file' ).removeClass( 'bb-icon-file' ).addClass( icon_class );
							}
						} else {
							var node, _i, _len, _ref, _results;
							var message = response.data.feedback;
							file.previewElement.classList.add( 'dz-error' );
							_ref     = file.previewElement.querySelectorAll( '[data-dz-errormessage]' );
							_results = [];
							for ( _i = 0, _len = _ref.length; _i < _len; _i++ ) {
								node = _ref[_i];
								_results.push( node.textContent = message );
							}
							return _results;
						}

						bp.draft_content_changed = true;
					}
				);

				bp.Nouveau.Activity.postForm.dropzone.on(
					'accept',
					function ( file, done ) {
						if ( file.size == 0 ) {
							done( BP_Nouveau.media.empty_document_type );
						} else {
							done();
						}
					}
				);

				bp.Nouveau.Activity.postForm.dropzone.on(
					'error',
					function ( file, response ) {
						if ( file.accepted ) {
							if ( ! _.isUndefined( response ) && ! _.isUndefined( response.data ) && ! _.isUndefined( response.data.feedback ) ) {
								$( file.previewElement ).find( '.dz-error-message span' ).text( response.data.feedback );
							} else if( file.status == 'error' && ( file.xhr && file.xhr.status == 0) ) { // update server error text to user friendly
								$( file.previewElement ).find( '.dz-error-message span' ).text( BP_Nouveau.media.connection_lost_error );
							}
						} else {
							Backbone.trigger( 'onError', ( '<div>' + BP_Nouveau.media.invalid_file_type + '. ' + ( response ? response : '' ) + '<div>' ) );
							this.removeFile( file );
							self.$el.closest( '#whats-new-form').removeClass( 'media-uploading' );
						}
					}
				);

				bp.Nouveau.Activity.postForm.dropzone.on(
					'removedfile',
					function ( file ) {
						if ( true === bp.draft_activity.allow_delete_media ) {
							if ( self.document.length ) {
								for ( var i in self.document ) {
									if ( file.id === self.document[i].id ) {
										if ( !_.isUndefined( self.document[i].saved ) && !self.document[i].saved ) {
											bp.Nouveau.Media.removeAttachment( file.id );
										}
										self.document.splice( i, 1 );
										self.model.set( 'document', self.document );
									} else {
										if ( 'edit' !== bp.draft_activity.display_post && file.document_edit_data ) {
											var attachment_id = file.document_edit_data.id;
											if ( attachment_id === self.document[i].id ) {
												self.document.splice( i, 1 );
												self.model.set( 'document', self.document );
												bp.Nouveau.Media.removeAttachment( attachment_id );
											}
										}
									}
								}
							}

							if ( !_.isNull( bp.Nouveau.Activity.postForm.dropzone.files ) && bp.Nouveau.Activity.postForm.dropzone.files.length === 0 ) {
								self.$el.closest( '#whats-new-form').removeClass( 'media-uploading' );
								var tool_box = self.$el.parents( '#whats-new-form' );
								if ( tool_box.find( '#activity-media-button' ) ) {
									tool_box.find( '#activity-media-button' ).parents( '.post-elements-buttons-item' ).removeClass( 'disable active no-click' );
								}
								if ( tool_box.find( '#activity-video-button' ) ) {
									tool_box.find( '#activity-video-button' ).parents( '.post-elements-buttons-item' ).removeClass( 'disable active no-click' );
								}
								if ( tool_box.find( '#activity-gif-button' ) ) {
									tool_box.find( '#activity-gif-button' ).parents( '.post-elements-buttons-item' ).removeClass( 'disable active no-click' );
								}
								if ( tool_box.find( '#activity-document-button' ) ) {
									tool_box.find( '#activity-document-button' ).parents( '.post-elements-buttons-item' ).removeClass( 'disable no-click' );
								}

								self.model.unset( 'document' );
								if ( $( '#message-feedabck' ).hasClass( 'noMediaError' ) ) {
									self.model.unset( 'errors' );
								}
							}

							bp.draft_content_changed = true;
						}
					}
				);

				// Enable submit button when all documents are uploaded
				bp.Nouveau.Activity.postForm.dropzone.on(
					'complete',
					function( file ) {
						if ( this.getUploadingFiles().length === 0 && this.getQueuedFiles().length === 0 && this.files.length > 0 ) {
							self.$el.closest( '#whats-new-form' ).removeClass( 'media-uploading' );
						}

						var filename  = !_.isUndefined( file.name ) ? file.name : '';
						var fileExtension = filename.substr( ( filename.lastIndexOf( '.' ) + 1 ) );
						var file_icon     = ( ! _.isUndefined( file.svg_icon ) ? file.svg_icon : '' );
						var icon_class    = ! _.isEmpty( file_icon ) ? file_icon : 'bb-icon-file-' + fileExtension;

						if (
							$( file.previewElement ).find( '.dz-details .dz-icon .bb-icon-file' ).length  &&
							$( file.previewElement ).find( '.dz-details .dz-icon .bb-icon-file' ).hasClass( 'bb-icon-file' )
						) {
							$( file.previewElement ).find( '.dz-details .dz-icon .bb-icon-file' ).removeClass( 'bb-icon-file' ).addClass( icon_class );
						}
					}
				);

				self.$el.find( '#activity-post-document-uploader' ).addClass( 'open' ).removeClass( 'closed' );
				$( '#whats-new-attachments' ).removeClass( 'empty' ).closest( '#whats-new-form' ).addClass( 'focus-in--attm' );
			}

		}
	);

	// Activity Video.
	bp.Views.ActivityVideo = bp.View.extend(
		{
			tagName: 'div',
			className: 'activity-video-container',
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
				if ( self.$el.find( '#activity-post-video-uploader' ).hasClass( 'open' ) ) {
					self.destroyVideo();
				} else {
					self.open_video_uploader();
				}
			},

			destroyVideo: function () {
				var self = this;
				if ( ! _.isNull( bp.Nouveau.Activity.postForm.dropzone ) ) {
					bp.Nouveau.Activity.postForm.dropzone.destroy();
					self.$el.find( '#activity-post-video-uploader' ).html( '' );
				}
				self.video = [];
				self.$el.find( '#activity-post-video-uploader' ).removeClass( 'open' ).addClass( 'closed' );

				$( '#whats-new-attachments' ).addClass( 'empty' ).closest( '#whats-new-form' ).removeClass( 'focus-in--attm' );
			},

			open_video_uploader: function () {
				var self = this;
				if ( self.$el.find( '#activity-post-video-uploader' ).hasClass( 'open' ) ) {
					return false;
				}
				self.destroyVideo();
				this.dropzone_options = {
					url: BP_Nouveau.ajaxurl,
					timeout: 3 * 60 * 60 * 1000,
					dictFileTooBig: BP_Nouveau.video.dictFileTooBig,
					acceptedFiles: BP_Nouveau.video.video_type,
					createImageThumbnails: false,
					dictDefaultMessage: BP_Nouveau.video.dropzone_video_message,
					autoProcessQueue: true,
					addRemoveLinks: true,
					uploadMultiple: false,
					maxFiles: typeof BP_Nouveau.video.maxFiles !== 'undefined' ? BP_Nouveau.video.maxFiles : 10,
					maxFilesize: typeof BP_Nouveau.video.max_upload_size !== 'undefined' ? BP_Nouveau.video.max_upload_size : 2,
					dictInvalidFileType: BP_Nouveau.video.dictInvalidFileType,
					dictMaxFilesExceeded : BP_Nouveau.video.video_dict_file_exceeded,
					previewTemplate : document.getElementsByClassName( 'activity-post-video-template' )[0].innerHTML,
					dictCancelUploadConfirmation: BP_Nouveau.video.dictCancelUploadConfirmation,
				};
				bp.Nouveau.Activity.postForm.dropzone = new window.Dropzone( '#activity-post-video-uploader', this.dropzone_options );

				bp.Nouveau.Activity.postForm.dropzone.on(
					'addedfile',
					function ( file ) {
						if ( file.video_edit_data ) {
							self.video.push( file.video_edit_data );
							self.model.set( 'video', self.video );
						}

						if ( file.dataURL && file.video_edit_data.thumb.length ) {
							// Get Thumbnail image from response.
							$( file.previewElement ).find( '.dz-video-thumbnail' ).prepend( '<img src=" ' + file.video_edit_data.thumb + ' " />' );
							$( file.previewElement ).closest( '.dz-preview' ).addClass( 'dz-has-thumbnail' );
						} else {

							if ( bp.Nouveau.getVideoThumb ) {
								bp.Nouveau.getVideoThumb( file, '.dz-video-thumbnail' );
							}

						}

					}
				);

				bp.Nouveau.Activity.postForm.dropzone.on(
					'sending',
					function ( file, xhr, formData ) {
						formData.append( 'action', 'video_upload' );
						formData.append( '_wpnonce', BP_Nouveau.nonces.video );

						var tool_box = self.$el.parents( '#whats-new-form' );
						if ( tool_box.find( '#activity-media-button' ) ) {
							tool_box.find( '#activity-media-button' ).parents( '.post-elements-buttons-item' ).addClass( 'disable' );
						}
						if ( tool_box.find( '#activity-gif-button' ) ) {
							tool_box.find( '#activity-gif-button' ).parents( '.post-elements-buttons-item' ).addClass( 'disable' );
						}
						if ( tool_box.find( '#activity-document-button' ) ) {
							tool_box.find( '#activity-document-button' ).parents( '.post-elements-buttons-item' ).addClass( 'disable' );
						}
						if ( tool_box.find( '#activity-video-button' ) ) {
							tool_box.find( '#activity-video-button' ).parents( '.post-elements-buttons-item' ).addClass( 'no-click' );
						}
					}
				);

				bp.Nouveau.Activity.postForm.dropzone.on(
					'uploadprogress',
					function( element ) {

						self.$el.closest( '#whats-new-form').addClass( 'media-uploading' );

						var circle        = $( element.previewElement ).find( '.dz-progress-ring circle' )[0];
						var radius        = circle.r.baseVal.value;
						var circumference = radius * 2 * Math.PI;

						circle.style.strokeDasharray  = circumference + ' ' + circumference;
						var offset                    = circumference - element.upload.progress.toFixed( 0 ) / 100 * circumference;
						if ( element.upload.progress <= 99 ) {
							$( element.previewElement ).find( '.dz-progress-count' ).text( element.upload.progress.toFixed( 0 ) + '% ' + BP_Nouveau.video.i18n_strings.video_uploaded_text );
							circle.style.strokeDashoffset = offset;
						} else if ( element.upload.progress === 100 ) {
							circle.style.strokeDashoffset = circumference - 0.99 * circumference;
							$( element.previewElement ).find( '.dz-progress-count' ).text( '99% ' + BP_Nouveau.video.i18n_strings.video_uploaded_text );
						}
					}
				);

				bp.Nouveau.Activity.postForm.dropzone.on(
					'success',
					function ( file, response ) {

						if ( file.upload.progress === 100 ) {
							$( file.previewElement ).find( '.dz-progress-ring circle' )[0].style.strokeDashoffset = 0;
							$( file.previewElement ).find( '.dz-progress-count' ).text( '100% ' + BP_Nouveau.video.i18n_strings.video_uploaded_text );
							$( file.previewElement ).closest( '.dz-preview' ).addClass( 'dz-complete' );
						}

						if ( response.data.id ) {

							// Set the folder_id and group_id if activity belongs to any folder and group in edit activity on new uploaded media id.
							if ( ! bp.privacyEditable ) {
								response.data.album_id = bp.album_id;
								response.data.group_id = bp.group_id;
								response.data.privacy  = bp.privacy;
							}

							file.id                  = response.data.id;
							response.data.uuid       = file.upload.uuid;
							response.data.group_id   = ! _.isUndefined( BP_Nouveau.video ) && ! _.isUndefined( BP_Nouveau.video.group_id ) ? BP_Nouveau.video.group_id : false;
							response.data.saved      = false;

							var thumbnailCheck = setInterval( function () {
								if( $( file.previewElement ).closest( '.dz-preview' ).hasClass( 'dz-has-no-thumbnail' ) || $( file.previewElement ).closest( '.dz-preview' ).hasClass( 'dz-has-thumbnail' ) ) {
									response.data.js_preview = $( file.previewElement ).find( '.dz-video-thumbnail img' ).attr( 'src' );
									response.data.menu_order = $( file.previewElement ).closest( '.dropzone' ).find( file.previewElement ).index() - 1;
									self.video.push( response.data );
									self.model.set( 'video', self.video );
									clearInterval( thumbnailCheck );
								}
							});
						} else {
							var node, _i, _len, _ref, _results;
							var message = response.data.feedback;
							file.previewElement.classList.add( 'dz-error' );
							_ref     = file.previewElement.querySelectorAll( '[data-dz-errormessage]' );
							_results = [];
							for ( _i = 0, _len = _ref.length; _i < _len; _i++ ) {
								node = _ref[ _i ];
								_results.push( node.textContent = message );
							}
							return _results;
						}

						bp.draft_content_changed = true;
					}
				);

				bp.Nouveau.Activity.postForm.dropzone.on(
					'accept',
					function ( file, done ) {
						if ( file.size == 0 ) {
							done( BP_Nouveau.video.empty_video_type );
						} else {
							done();
						}
					}
				);

				bp.Nouveau.Activity.postForm.dropzone.on(
					'error',
					function ( file, response ) {
						if ( file.accepted ) {
							if ( ! _.isUndefined( response ) && ! _.isUndefined( response.data ) && ! _.isUndefined( response.data.feedback ) ) {
								$( file.previewElement ).find( '.dz-error-message span' ).text( response.data.feedback );
							} else if( file.status == 'error' && ( file.xhr && file.xhr.status == 0) ) { // update server error text to user friendly
								$( file.previewElement ).find( '.dz-error-message span' ).text( BP_Nouveau.media.connection_lost_error );
							}
						} else {
							Backbone.trigger( 'onError', ( '<div>' + BP_Nouveau.video.invalid_video_type + '. ' + ( response ? response : '' ) + '<div>' ) );
							this.removeFile( file );
							self.$el.closest( '#whats-new-form').removeClass( 'media-uploading' );
						}
					}
				);

				bp.Nouveau.Activity.postForm.dropzone.on(
					'removedfile',
					function ( file ) {
						if ( true === bp.draft_activity.allow_delete_media ) {
							if ( self.video.length ) {
								for ( var i in self.video ) {
									if ( file.id === self.video[i].id ) {
										if ( !_.isUndefined( self.video[i].saved ) && !self.video[i].saved ) {
											bp.Nouveau.Media.removeAttachment( file.id );
										}
										self.video.splice( i, 1 );
										self.model.set( 'video', self.video );
									} else {
										if ( 'edit' !== bp.draft_activity.display_post && file.video_edit_data ) {
											var attachment_id = file.video_edit_data.id;
											if ( attachment_id === self.video[i].id ) {
												self.video.splice( i, 1 );
												self.model.set( 'video', self.video );
												bp.Nouveau.Media.removeAttachment( attachment_id );
											}
										}
									}
								}
							}

							if ( !_.isNull( bp.Nouveau.Activity.postForm.dropzone.files ) && bp.Nouveau.Activity.postForm.dropzone.files.length === 0 ) {
								self.$el.closest( '#whats-new-form').removeClass( 'media-uploading' );
								var tool_box = self.$el.parents( '#whats-new-form' );
								if ( tool_box.find( '#activity-media-button' ) ) {
									tool_box.find( '#activity-media-button' ).parents( '.post-elements-buttons-item' ).removeClass( 'disable active no-click' );
								}
								if ( tool_box.find( '#activity-gif-button' ) ) {
									tool_box.find( '#activity-gif-button' ).parents( '.post-elements-buttons-item' ).removeClass( 'disable active no-click' );
								}
								if ( tool_box.find( '#activity-document-button' ) ) {
									tool_box.find( '#activity-document-button' ).parents( '.post-elements-buttons-item' ).removeClass( 'disable active no-click' );
								}

								self.model.unset( 'video' );
								if ( $( '#message-feedabck' ).hasClass( 'noMediaError' ) ) {
									self.model.unset( 'errors' );
								}
							}

							bp.draft_content_changed = true;
						}
					}
				);

				// Enable submit button when all videos are uploaded
				bp.Nouveau.Activity.postForm.dropzone.on(
					'complete',
					function() {
						if ( this.getUploadingFiles().length === 0 && this.getQueuedFiles().length === 0 && this.files.length > 0 ) {
							self.$el.closest( '#whats-new-form').removeClass( 'media-uploading' );
						}
					}
				);

				self.$el.find( '#activity-post-video-uploader' ).addClass( 'open' ).removeClass( 'closed' );
				$( '#whats-new-attachments' ).removeClass( 'empty' ).closest( '#whats-new-form' ).addClass( 'focus-in--attm' );
				$( '#whats-new-form' ).closest( 'body' ).addClass( 'video-post-form-open' );
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
			},

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
				this.model.set( 'link_scrapping', false );
				this.model.set( 'link_embed', false );
				this.listenTo( this.model, 'change', this.render );
				document.addEventListener( 'activity_link_preview_open', this.open.bind( this ) );
				document.addEventListener( 'activity_link_preview_close', this.destroy.bind( this ) );
			},

			render: function () {
				// do not re render if post form is submitting.
				if ( this.model.get( 'posting' ) == true ) {
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
				if ( this.model.get( 'link_embed' ) == true ) {

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

				$( '#whats-new' ).removeData( 'activity-url-preview' );
				$( '#whats-new-attachments' ).addClass( 'empty' ).closest( '#whats-new-form' ).removeClass( 'focus-in--attm' );
			},

			displayPrevNextButton: function ( e ) {
				e.preventDefault();
				this.model.set( 'link_swap_image_button', 1 );
				this.displayNextPrevButtonView();
			},

			displayNextPrevButtonView: function () {
				$('#activity-url-prevPicButton').show();
				$('#activity-url-nextPicButton').show();
				$('#activity-link-preview-select-image').show();
				$('#icon-exchange').hide();
				$('#activity-link-preview-remove-image').hide();
			},

			selectImageForPreview: function ( e ) {
				e.preventDefault();
				var imageIndex = this.model.get( 'link_image_index' );
				this.model.set( 'link_image_index_save', imageIndex );
				$('#icon-exchange').show();
				$('#activity-link-preview-remove-image').show();
				$('#activity-link-preview-select-image').hide();
				$('#activity-url-prevPicButton').hide();
				$('#activity-url-nextPicButton').hide();
			}
		}
	);

	// Activity gif selector.
	bp.Views.ActivityAttachedGifPreview = bp.View.extend(
		{
			tagName: 'div',
			className: 'activity-attached-gif-container',
			template: bp.template( 'activity-attached-gif' ),
			events: {
				'click .gif-image-remove': 'destroy'
			},

			initialize: function () {
				this.listenTo( this.model, 'change', this.render );
				this.listenTo( Backbone, 'activity_gif_close', this.destroy );
			},

			render: function () {
				this.$el.html( this.template( this.model.toJSON() ) );

				var gifData = this.model.get( 'gif_data' );
				if ( ! _.isEmpty( gifData ) ) {
					this.el.style.backgroundImage = 'url(' + gifData.images.fixed_width.url + ')';
					this.el.style.backgroundSize  = 'contain';
					this.el.style.minHeight          = gifData.images.original.height + 'px';
					this.el.style.width           = gifData.images.original.width + 'px';
					$( '#whats-new-attachments' ).removeClass( 'empty' ).closest( '#whats-new-form' ).addClass( 'focus-in--attm' );

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
				if( $( '#message-feedabck' ).hasClass( 'noMediaError') ) {
					this.model.unset( 'errors' );
				}
				this.el.style.backgroundImage = '';
				this.el.style.backgroundSize  = '';
				this.el.style.minHeight          = '0px';
				this.el.style.width           = '0px';
				//document.removeEventListener( 'activity_gif_close', this.destroy.bind( this ) );
				$( '#whats-new-attachments' ).addClass( 'empty' ).closest( '#whats-new-form' ).removeClass( 'focus-in--attm' );
				var tool_box = this.$el.parents( '#whats-new-form' );
				if ( tool_box.find( '#activity-document-button' ) ) {
					tool_box.find( '#activity-document-button' ).parents( '.post-elements-buttons-item' ).removeClass( 'disable' );
					tool_box.find( '#activity-document-button' ).parents( '.post-elements-buttons-item' ).removeClass( 'no-click' );
				}
				if ( tool_box.find( '#activity-media-button' ) ) {
					tool_box.find( '#activity-media-button' ).parents( '.post-elements-buttons-item' ).removeClass( 'disable' );
					tool_box.find( '#activity-media-button' ).parents( '.post-elements-buttons-item' ).removeClass( 'no-click' );
				}
				if ( tool_box.find( '#activity-video-button' ) ) {
					tool_box.find( '#activity-video-button' ).parents( '.post-elements-buttons-item' ).removeClass( 'disable' );
					tool_box.find( '#activity-video-button' ).parents( '.post-elements-buttons-item' ).removeClass( 'no-click' );
				}
				if ( tool_box.find( '#activity-gif-button' ) ) {
					tool_box.find( '#activity-gif-button' ).removeClass( 'open' ).parents( '.post-elements-buttons-item' ).removeClass( 'active' );
					tool_box.find( '#activity-gif-button' ).removeClass( 'open' ).parents( '.post-elements-buttons-item' ).removeClass( 'no-click' );
				}

				var tool_box_comment = this.$el.parents( '.ac-reply-content' );
				this.$el.closest( '.ac-form' ).removeClass( 'has-gif' );
				if ( tool_box_comment.find( '.ac-reply-toolbar .ac-reply-media-button' ) ) {
					tool_box_comment.find( '.ac-reply-toolbar .ac-reply-media-button' ).parents( '.post-elements-buttons-item' ).removeClass( 'disable' );
					tool_box_comment.find( '.ac-reply-toolbar .ac-reply-media-button' ).parents( '.post-elements-buttons-item' ).removeClass( 'no-click' );
				}
				if ( tool_box_comment.find( '.ac-reply-toolbar .ac-reply-document-button' ) ) {
					tool_box_comment.find( '.ac-reply-toolbar .ac-reply-document-button' ).parents( '.post-elements-buttons-item' ).removeClass( 'disable' );
					tool_box_comment.find( '.ac-reply-toolbar .ac-reply-document-button' ).parents( '.post-elements-buttons-item' ).removeClass( 'no-click' );
				}
				if ( tool_box_comment.find( '.ac-reply-toolbar .ac-reply-video-button' ) ) {
					tool_box_comment.find( '.ac-reply-toolbar .ac-reply-video-button' ).parents( '.post-elements-buttons-item' ).removeClass( 'disable' );
					tool_box_comment.find( '.ac-reply-toolbar .ac-reply-video-button' ).parents( '.post-elements-buttons-item' ).removeClass( 'no-click' );
				}
				if ( tool_box_comment.find( '.ac-reply-toolbar .ac-reply-gif-button' ) ) {
					tool_box_comment.find( '.ac-reply-toolbar .ac-reply-gif-button' ).removeClass( 'active' );
					tool_box_comment.find( '.ac-reply-toolbar .ac-reply-gif-button' ).removeClass( 'no-click' );
				}

				if ( tool_box_comment.find( '.ac-textarea' ).children( '.ac-input' ).length > 0 ) {
					var $activity_comment_content = tool_box_comment.find( '.ac-textarea' ).children( '.ac-input' ).html();

					var content = $.trim( $activity_comment_content.replace( /<div>/gi, '\n' ).replace( /<\/div>/gi, '' ) );
					content = content.replace( /&nbsp;/g, ' ' );

					var content_text = tool_box_comment.find( '.ac-textarea' ).children( '.ac-input' ).text().trim();
					if ( content_text !== '' || content.indexOf( 'emojioneemoji' ) >= 0 ) {
						$( tool_box_comment ).closest( 'form' ).addClass( 'has-content' );
					} else {
						$( tool_box_comment ).closest( 'form' ).removeClass( 'has-content' );
					}
				}

				if ( ! _.isUndefined( event ) && ! _.isEmpty( old_gif_data ) && _.isEmpty( this.model.get( 'gif_data' ) ) ) {
					bp.draft_content_changed = true;
				}
			}
		}
	);

	// Gif search dropdown.
	bp.Views.GifMediaSearchDropdown = bp.View.extend(
		{
			tagName: 'div',
			className: 'activity-attached-gif-container',
			template: bp.template( 'gif-media-search-dropdown' ),
			total_count: 0,
			offset: 0,
			limit: 20,
			q: null,
			requests: [],
			events: {
				'keydown .search-query-input': 'search',
				'click .found-media-item': 'select'
			},

			initialize: function ( options ) {
				this.options = options || {};
				this.giphy   = new window.Giphy( BP_Nouveau.media.gif_api_key );

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

				// Prevent search dropdown from closing with enter key
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

				var tool_box = this.$el.parents( '#whats-new-form' );
				if ( tool_box.find( '#activity-document-button' ) ) {
					tool_box.find( '#activity-document-button' ).parents( '.post-elements-buttons-item' ).addClass( 'disable' );
				}
				if ( tool_box.find( '#activity-media-button' ) ) {
					tool_box.find( '#activity-media-button' ).parents( '.post-elements-buttons-item' ).addClass( 'disable' );
				}
				if ( tool_box.find( '#activity-video-button' ) ) {
					tool_box.find( '#activity-video-button' ).parents( '.post-elements-buttons-item' ).addClass( 'disable' );
				}

				var tool_box_comment = this.$el.parents( '.ac-reply-content' );
				if ( tool_box_comment.find( '.ac-reply-toolbar .ac-reply-media-button' ) ) {
					tool_box_comment.find( '.ac-reply-toolbar  .ac-reply-media-button' ).parents( '.post-elements-buttons-item' ).addClass( 'disable' );
				}
				if ( tool_box_comment.find( '.ac-reply-toolbar .ac-reply-document-button' ) ) {
					tool_box_comment.find( '.ac-reply-toolbar  .ac-reply-document-button' ).parents( '.post-elements-buttons-item' ).addClass( 'disable' );
				}
				if ( tool_box_comment.find( '.ac-reply-toolbar .ac-reply-video-button' ) ) {
					tool_box_comment.find( '.ac-reply-toolbar  .ac-reply-video-button' ).parents( '.post-elements-buttons-item' ).addClass( 'disable' );
				}

				var whatNewForm = this.$el.closest( '#whats-new-form' );
				this.$el.closest( '.ac-form' ).addClass( 'has-gif' );

				var whatNewScroll = whatNewForm.find( '.whats-new-scroll-view' );
				whatNewScroll.stop().animate({
					scrollTop: whatNewScroll[0].scrollHeight
				}, 300);
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
				if ( event.target.id === 'gif-search-results' ) { // or any other filtering condition.
					var el = event.target;
					if ( el.scrollTop + el.offsetHeight >= el.scrollHeight && ! el.classList.contains( 'loading' ) ) {
						if ( this.total_count > 0 && this.offset <= this.total_count ) {
							var self   = this,
								params = {
									offset: self.offset,
									fmt: 'json',
									limit: self.limit
								};

							self.el.classList.add( 'loading' );
							var request = null;
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

				for ( var i = 0; i < this.requests.length; i++ ) {
					this.requests[ i ].abort();
				}

				this.requests = [];
			},

			loadMoreResponse: function ( response ) {
				this.el.classList.remove( 'loading' );
				this.gifDataItems.add( response.data );
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
			},

			render: function () {
				var bgNo   = Math.floor( Math.random() * ( 6 - 1 + 1 ) ) + 1,
					images = this.model.get( 'images' );

				this.$el.html( this.template( this.model.toJSON() ) );
				this.el.classList.add( 'bg' + bgNo );
				this.el.style.height = images.fixed_width.height + 'px';

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
			className: 'bp-suggestions',
			id: 'whats-new',
			events: {
				'paste': 'handlePaste',
				'keyup': 'handleKeyUp',
				'click': 'handleClick'
			},
			attributes: {
				name: 'whats-new',
				cols: '50',
				rows: '4',
				placeholder: BP_Nouveau.activity.strings.whatsnewPlaceholder,
				'aria-label': BP_Nouveau.activity.strings.whatsnewLabel,
				contenteditable: true,
				autocorrect: 'off',
				'data-suggestions-group-id': ! _.isUndefined( BP_Nouveau.activity.params.object ) && 'group' === BP_Nouveau.activity.params.object ? BP_Nouveau.activity.params.item_id : false,
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

				if ( ! _.isUndefined( BP_Nouveau.activity.params.link_preview ) ) {
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

				var scrollViewScrollHeight = this.$el.closest( '.whats-new-scroll-view' ).prop('scrollHeight');
				var scrollViewClientHeight = this.$el.closest( '.whats-new-scroll-view' ).prop('clientHeight');

				if ( scrollViewScrollHeight > scrollViewClientHeight ) {
					this.$el.closest( '#whats-new-form' ).addClass( 'focus-in--scroll' );
				} else {
					this.$el.closest( '#whats-new-form' ).removeClass( 'focus-in--scroll' );
				}


			},

			handleClick: function() {
				this.saveCaretPosition();
			},

			saveCaretPosition: function () {
				if (window.getSelection && document.createRange) {
					var sel = window.getSelection && window.getSelection();
					if (sel && sel.rangeCount > 0) {
						window.activityCaretPosition = sel.getRangeAt(0);
					}
				} else {
					window.activityCaretPosition = document.selection.createRange();
				}
			},

			scrapURL: function ( urlText ) {
				var urlString = '';
				var activity_URL_preview = this.$el.closest( '#whats-new' ).data( 'activity-url-preview' );

				if ( urlText === null && activity_URL_preview === undefined ) {
					return;
				}

				//Remove mentioned members Link
				var tempNode = $( '<div></div>' ).html( urlText );
				tempNode.find( 'a.bp-suggestions-mention' ).remove();
				urlText = tempNode.html();

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
					if ( BP_Nouveau.activity.params.excluded_hosts.indexOf( hostname ) !== -1 ) {
						urlString = '';
					}
				}

				if ( '' !== urlString ) {
					this.loadURLPreview( urlString );
				} else if( activity_URL_preview !== undefined ){
					this.loadURLPreview( activity_URL_preview );
				}
			},

			getURL: function ( prefix, urlText ) {
				var urlString   = '';
				urlText         = urlText.replace( /&nbsp;/g, '' );
				var startIndex  = urlText.indexOf( prefix );
				var responseUrl = '';

				if ( ! _.isUndefined( $( $.parseHTML( urlText ) ).attr( 'href' ) ) ) {
					urlString = $( urlText ).attr( 'href' );
				} else {
					for ( var i = startIndex; i < urlText.length; i++ ) {
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
				var self = this;

				var regexp = /^(http:\/\/www\.|https:\/\/www\.|http:\/\/|https:\/\/)?[a-z0-9]+([\-\.]{1}[a-z0-9]+)*\.[a-z]{2,24}(:[0-9]{1,5})?(\/.*)?$/;
				url        = $.trim( url );
				if ( regexp.test( url ) ) {
					if ( ( ! _.isUndefined( self.options.activity.get( 'link_success' ) ) && self.options.activity.get( 'link_success' ) == true ) && self.options.activity.get( 'link_url' ) === url ) {
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
								if ( urlObj.url == url ) {
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
						urlImagesIndex =  parseInt( self.options.activity.get( 'link_image_index' ) );
					}

					var prev_activity_preview_url = this.$el.closest( '#whats-new' ).data( 'activity-url-preview' );
					var link_image_index_save     = self.options.activity.get( 'link_image_index_save' );
					if ( '' !== prev_activity_preview_url && prev_activity_preview_url !== url ) {

						// Reset older preview data
						urlImagesIndex        = 0;
						link_image_index_save = 0;
						this.$el.closest( '#whats-new' ).data( 'activity-url-preview', url );

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

					$( '#whats-new-attachments' ).removeClass( 'empty' ).closest( '#whats-new-form' ).addClass( 'focus-in--attm' );

					if ( $( '#whats-new-attachments' ).hasClass( 'activity-video-preview' ) ) {
						$( '#whats-new-attachments' ).removeClass( 'activity-video-preview' );
					}

					if ( $( '#whats-new-attachments' ).hasClass( 'activity-link-preview' ) ) {
						$( '#whats-new-attachments' ).removeClass( 'activity-link-preview' );
					}

					if ( $( '.activity-media-container' ).length ) {
						if ( ( 'undefined' !== typeof response.description && response.description.indexOf( 'iframe' ) > -1 ) || ( ! _.isUndefined( response.wp_embed ) && response.wp_embed ) ) {
							$( '#whats-new-attachments' ).addClass( 'activity-video-preview' );
						} else {
							$( '#whats-new-attachments' ).addClass( 'activity-link-preview' );
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

					$( '#whats-new' ).each(
						function () {
							var $this           = $( this );
							var whatsnewcontent = $this.closest( '#whats-new-form' ).find( '#editor-toolbar' )[ 0 ];

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
											placeholderText: BP_Nouveau.anchorPlaceholderText,
											linkValidation: true
										}
									}
								);

								window.activity_editor.subscribe( 'editablePaste', function ( e ) {
									setTimeout( function() {
										// Wrap all target <li> elements in a single <ul>
										var targetLiElements = $(e.target).find('li').filter(function() {
											return !$(this).parent().is('ul') && !$(this).parent().is('ol');
										});
										if (targetLiElements.length > 0) {
											targetLiElements.wrapAll('<ul></ul>');
										}
									}, 0 );
								});
							}
						}
					);

					$( document ).on ( 'keyup', '.activity-form .medium-editor-toolbar-input', function( event ) {

						var URL = event.target.value;

						if ( bp.Nouveau.isURL( URL ) ) {
							$( event.target ).removeClass('isNotValid').addClass('isValid');
						} else {
							$( event.target ).removeClass('isValid').addClass('isNotValid');
						}

					});

					// check for mentions in the url, if set any then focus to editor.
					var mention = bp.Nouveau.getLinkParams( null, 'r' ) || null;

					// Check for mention.
					if ( ! _.isNull( mention ) ) {
						$( '#message_content' ).focus();
					}

				} else if ( ! _.isUndefined( tinymce ) ) {
					tinymce.EditorManager.execCommand( 'mceAddEditor', true, 'whats-new' ); // jshint ignore:line
				}
			}
		}
	);

	bp.Views.WhatsNewPostIn = bp.View.extend(
		{
			tagName: 'select',
			id: 'whats-new-post-in',

			attributes: {
				name: 'whats-new-post-in',
				'aria-label': BP_Nouveau.activity.strings.whatsnewpostinLabel
			},

			events: {
				change: 'change'
			},

			keys: [],

			initialize: function () {
				this.model = new Backbone.Model();

				this.filters = this.options.filters || {};

				// Build `<option>` elements.
				this.$el.html(
					_.chain( this.filters ).map(
						function ( filter, value ) {
							return {
								el: $( '<option></option>' ).val( value ).html( filter.text )[ 0 ],
								priority: filter.priority || 50
							};
						},
						this
					).sortBy( 'priority' ).pluck( 'el' ).value()
				);
			},

			change: function () {
				var filter = this.filters[ this.el.value ];
				if ( filter ) {
					this.model.set( { 'selected': this.el.value, 'placeholder': filter.autocomplete_placeholder } );
				}
			}
		}
	);

	bp.Views.ActivityPrivacy = bp.View.extend(
		{
			tagName: 'div',
			id: 'activity-post-form-privacy',
			template: bp.template( 'activity-post-form-privacy' ),

			initialize: function () {
				this.model = new bp.Models.Activity();
			},
		}
	);

	bp.Views.Item = bp.View.extend(
		{
			tagName: 'div',
			className: 'bp-activity-object',
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

				var whats_new_form = $( '#whats-new-form' );

				if ( true === this.model.get( 'selected' ) ) {
					return false;
				} else {
					whats_new_form.removeClass( 'focus-in--blank-group' );
					var $this = this;
					if ( $this.model.hasOwnProperty('attributes') &&
					     $this.model.attributes.hasOwnProperty('object_type') &&
					     'group' === $this.model.attributes.object_type ) {
						var previousSelected = _.find( this.model.collection.models, function ( model ) {
							return model !== $this.model && model.get( 'selected' );
						} );
						if ( previousSelected ) {
							previousSelected.set( 'selected', false );
						}
					}
					this.model.set( 'selected', true );
					var model_attributes = this.model.attributes;
					// check media is enable in groups or not.
					if ( typeof model_attributes.group_media !== 'undefined' && model_attributes.group_media === false ) {
						if ( 'undefined' === typeof bp.Nouveau.Activity.postForm.dropzone || null === bp.Nouveau.Activity.postForm.dropzone || 'activity-post-media-uploader' === bp.Nouveau.Activity.postForm.dropzone.element.id ) {
							$( '#whats-new-toolbar .post-media.media-support' ).removeClass( 'active' ).addClass( 'media-support-hide' );
							Backbone.trigger( 'activity_media_close' );
						}
					} else {
						$( '#whats-new-toolbar .post-media.media-support' ).removeClass('media-support-hide');
					}

					// check document is enable in groups or not.
					if ( typeof model_attributes.group_document !== 'undefined' && model_attributes.group_document === false ) {
						if ( 'undefined' === typeof bp.Nouveau.Activity.postForm.dropzone || null === bp.Nouveau.Activity.postForm.dropzone || 'activity-post-document-uploader' === bp.Nouveau.Activity.postForm.dropzone.element.id ) {
							$( '#whats-new-toolbar .post-media.document-support' ).removeClass( 'active' ).addClass( 'document-support-hide' );
							Backbone.trigger( 'activity_document_close' );
						}
					} else {
						$( '#whats-new-toolbar .post-media.document-support' ).removeClass('document-support-hide');
					}

					// check video is enable in groups or not.
					if ( typeof model_attributes.group_video !== 'undefined' && model_attributes.group_video === false ) {
						if ( 'undefined' === typeof bp.Nouveau.Activity.postForm.dropzone || null === bp.Nouveau.Activity.postForm.dropzone || 'activity-post-video-uploader' === bp.Nouveau.Activity.postForm.dropzone.element.id ) {
							$( '#whats-new-toolbar .post-video.video-support' ).removeClass( 'active' ).addClass( 'video-support-hide' );
							Backbone.trigger( 'activity_video_close' );
						}
					} else {
						$( '#whats-new-toolbar .post-video.video-support' ).removeClass('video-support-hide');
					}

				}
			}
		}
	);

	bp.Views.AutoComplete = bp.View.extend(
		{
			tagName: 'div',
			id: 'whats-new-post-in-box-items',
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
				autocomplete.$el.wrapAll( '<span class="activity-autocomplete-wrapper" />' ).after( '<span class="activity-autocomplete-clear"><i class="bb-icon-rl bb-icon-times"></i></span>' );
				this.$el.append( '<div id="bp-activity-group-ac-items"></div>' );

				this.on( 'ready', this.setFocus, this );
				if ( 'group' === this.options.type ) {
					var default_group_ac_list_item = BP_Nouveau.activity.params.objects.group_list;
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

					var group_total_page = BP_Nouveau.activity.params.objects.group_total_page;
					var group_count      = BP_Nouveau.activity.params.objects.group_count;
					if ( group_total_page > 1 && group_count > this.collection.models.length ) {
						var $this = this;
						this.$el.find( '#bp-activity-group-ac-items' ).addClass( 'group_scrolling load_more_data' );
						var $scrollable = this.$el.find( '#bp-activity-group-ac-items' );
						var currentPage = 1;
						$scrollable.on( 'scroll', function () {
							window.acScrollPosition = $scrollable.scrollTop();
							if ( $this.$el.find( '#bp-activity-group-ac-items' ).hasClass('load_more_data') ) {
								currentPage++;
								if ( currentPage > group_total_page ) {
									$this.$el.find( '#bp-activity-group-ac-items' ).removeClass( 'load_more_data' );
									currentPage = 1;
									return false;
								} else {
									$this.loadMoreData( $this, currentPage );
								}
							}
						} );
					}
				}
				this.collection.on( 'add', this.addItemView, this );
				this.collection.on( 'reset', this.cleanView, this );
			},

			setFocus: function () {
				this.$el.find( '#activity-autocomplete' ).focus();
				// After select any group it will scroll to particular selected group.
				if ( $( '#bp-activity-group-ac-items .bp-activity-object' ).length ) {
					var activityGroupAcItems = $( '#bp-activity-group-ac-items' );
					$( '.bp-activity-object' ).each( function () {
						if ( $( this ).hasClass( 'selected' ) ) {
							activityGroupAcItems.scrollTop( window.acScrollPosition );
							activityGroupAcItems.on( 'scroll', function () {
								window.acScrollPosition = $( this ).scrollTop();
							} );
						}
					} );
				}
			},

			addItemView: function ( item ) {
				var group_ac_list_item = new bp.Views.Item( { model: item } );
				this.$el.find( '#bp-activity-group-ac-items' ).append( group_ac_list_item.render().$el );
			},

			autoComplete: function () {
				var $this  = this;
				var search = $( '#activity-autocomplete' ).val();
				var whats_new_form = $this.$el.closest( '#whats-new-form' );

				if ( 0 === parseInt( search.length ) ) {
					this.autoCompleteCollectionData( $this, search );
					$this.$el.find( '#bp-activity-group-ac-items' ).addClass( 'load_more_data' );
					$this.$el.removeClass( 'activity-is-autocomplete' );

					// Disable privacy status submit button if groups search filter is cleared
					whats_new_form.addClass( 'focus-in--blank-group' );
				} else {
					$this.$el.addClass( 'activity-is-autocomplete' );

					$( '#whats-new-post-in-box-items .activity-autocomplete-clear' ).on( 'click', function () {
						$( '#activity-autocomplete' ).val('').keyup();

						// Disable privacy status submit button if groups search filter is cleared
						whats_new_form.addClass( 'focus-in--blank-group' );
					});
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

				if ( 'group' === this.options.type ) {
					this.$el.find( '#bp-activity-group-ac-items' ).html( '<div class="groups-selection groups-selection--finding"><i class="dashicons dashicons-update animate-spin"></i><span class="groups-selection__label">' + BP_Nouveau.activity.params.objects.group.finding_group_placeholder + '</span></div>' );
					this.$el.find( '#bp-activity-group-ac-items' ).addClass( 'group_scrolling--revive' );
				} else {
					this.$el.find( '#bp-activity-group-ac-items' ).html( '<i class="dashicons dashicons-update animate-spin"></i>' );
				}

				var attrData = {
					type: this.options.type,
					nonce: BP_Nouveau.nonces.activity
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
				if ( 'group' === optionType ) {
					this.$el.find( '#bp-activity-group-ac-items' ).find( '.groups-selection--finding' ).remove();
					this.$el.find( '#bp-activity-group-ac-items' ).removeClass( 'group_scrolling--revive' );
				} else {
					this.$el.find( '#bp-activity-group-ac-items' ).find( 'i.dashicons' ).remove();
				}
			},

			cleanView: function ( optionType ) {
				if ( 'group' === optionType ) {
					this.$el.find( '#bp-activity-group-ac-items' ).html( '<span class="groups-selection groups-selection--no-groups">' + BP_Nouveau.activity.params.objects.group.no_groups_found + '</span>' );
				} else {
					this.$el.find( '#bp-activity-group-ac-items' ).html( '' );
				}
				_.each(
					this.views._views[ '' ],
					function ( view ) {
						view.remove();
					}
				);
			},

			loadMoreData: function ( $this, currentPage ) {
				if ( ! this.$el.find( '#bp-activity-group-ac-items .groups-selection--loading' ).length ) {
					this.$el.find( '#bp-activity-group-ac-items .bp-activity-object:last' ).after( '<div class="groups-selection groups-selection--loading"><i class="dashicons dashicons-update animate-spin"></i><span class="groups-selection__label">' + BP_Nouveau.activity.params.objects.group.loading_group_placeholder + '</span></div>' );
				}
				var checkSucessData = false;
				var fetchGroup      = new bp.Collections.fetchCollection();
				fetchGroup.fetch(
					{
						type: 'POST',
						data: {
							type: $this.options.type,
							nonce: BP_Nouveau.nonces.activity,
							page: currentPage,
							action: 'bp_nouveau_get_activity_objects'
						},
						success: function ( collection, object ) {
							if ( true === object.success ) {
								$this.collection.add( object.data );
								$( '#bp-activity-group-ac-items .groups-selection--loading' ).remove();
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
			id: 'user-status-huddle',
			className: 'bp-activity-huddle',

			initialize: function() {
				this.views.add( new bp.Views.CaseAvatar( { model: this.model } ) );
				this.views.add( new bp.Views.CaseHeading( { model: this.model } ) );
				this.views.add( new bp.Views.CasePrivacy( { model: this.model } ) );

				$( '#whats-new-heading, #whats-new-status' ).wrapAll( '<div class="activity-post-name-status" />' );
				setTimeout(
					function () {
						$( '.activity-singular #whats-new-heading, .activity-singular #whats-new-status' ).wrapAll( '<div class="activity-post-name-status" />' );
					},
					1000
				);
			},
		}
	);

	bp.Views.CaseAvatar = bp.View.extend(
		{
			tagName: 'div',
			id: 'whats-new-avatar',
			template: bp.template( 'activity-post-case-avatar' ),

			initialize: function () {
				this.model = new Backbone.Model(
					_.pick(
						BP_Nouveau.activity.params,
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
			id: 'whats-new-heading',
			template: bp.template( 'activity-post-case-heading' ),

			initialize: function () {
				this.model = new Backbone.Model(
					_.pick(
						BP_Nouveau.activity.params,
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
			id: 'whats-new-status',
			template: bp.template( 'activity-post-case-privacy' ),
			events: {
				'click #bp-activity-privacy-point': 'privacyTarget'
			},

			initialize: function () {
				this.listenTo(Backbone, 'privacy:updatestatus', this.updateStatus);
				this.model.on( 'change:privacy', this.render, this );
			},

			render: function () {
				this.$el.html( this.template( this.model.toJSON() ) );

				if ( ! _.isUndefined( BP_Nouveau.activity.params.object ) && 'group' === BP_Nouveau.activity.params.object && 'group' === BP_Nouveau.activity.params.object ) {
					this.model.set( 'item_name', BP_Nouveau.activity.params.item_name );
					this.model.set( 'privacy', 'group' );

					var group_name     = BP_Nouveau.activity.params.item_name;
					var whats_new_form = $( '#whats-new-form' );
					whats_new_form.find( '.bp-activity-privacy-status' ).text( group_name );

					this.$el.find( '#bp-activity-privacy-point' ).removeClass().addClass( 'group bp-activity-focus-group-active' );
					// Display image of the group.
					if ( BP_Nouveau.activity.params.group_avatar && false === BP_Nouveau.activity.params.group_avatar.includes( 'mystery-group' ) ) {
						this.$el.find( '#bp-activity-privacy-point span.privacy-point-icon' ).removeClass( 'privacy-point-icon' ).addClass( 'group-privacy-point-icon' ).html( '<img src="' + BP_Nouveau.activity.params.group_avatar + '" alt=""/>' );
					} else {
						this.$el.find( '#bp-activity-privacy-point span.group-privacy-point-icon img' ).remove();
						this.$el.find( '#bp-activity-privacy-point span.group-privacy-point-icon' ).removeClass( 'group-privacy-point-icon' ).addClass( 'privacy-point-icon' );
					}

					bp.draft_activity.data.item_id            = BP_Nouveau.activity.params.item_id;
					bp.draft_activity.data.group_name         = BP_Nouveau.activity.params.item_name;
					bp.draft_activity.data.group_image        = BP_Nouveau.activity.params.group_avatar;
					bp.draft_activity.data.item_name          = BP_Nouveau.activity.params.item_name;
					bp.draft_activity.data.privacy            = 'group';
					bp.draft_activity.data[ 'group-privacy' ] = 'bp-item-opt-' + BP_Nouveau.activity.params.item_id;

					localStorage.setItem( bp.draft_activity.data_key, JSON.stringify( bp.draft_activity ) );
				}

				if ( ! _.isUndefined( bp.draft_activity ) && '' !== bp.draft_activity.object && 'group' === bp.draft_activity.object && bp.draft_activity.data && '' !== bp.draft_activity.data ) {
					this.model.set( 'item_name', bp.draft_activity.data.item_name );
					this.model.set( 'privacy', 'group' );

					$( '#whats-new-form' ).find( '.bp-activity-privacy-status' ).text( bp.draft_activity.data.item_name );

					this.$el.find( '#bp-activity-privacy-point' ).removeClass().addClass( 'group bp-activity-focus-group-active' );
					// display image of the group.
					if ( bp.draft_activity.data.group_image && false === bp.draft_activity.data.group_image.includes( 'mystery-group' ) ) {
						this.$el.find( '#bp-activity-privacy-point span.privacy-point-icon' ).removeClass( 'privacy-point-icon' ).addClass( 'group-privacy-point-icon' ).html( '<img src="' + bp.draft_activity.data.group_image + '" alt=""/>' );
					} else {
						this.$el.find( '#bp-activity-privacy-point span.group-privacy-point-icon img' ).remove();
						this.$el.find( '#bp-activity-privacy-point span.group-privacy-point-icon' ).removeClass( 'group-privacy-point-icon' ).addClass( 'privacy-point-icon' );
					}
				}

				return this;
			},

			updateStatus: function() {
				this.model.get( 'privacy' );
			},

			privacyTarget: function ( e ) {
				if ( this.$el.find( '#bp-activity-privacy-point' ).hasClass('bp-activity-edit-group') || ( ! _.isUndefined( BP_Nouveau.activity.params.object ) && 'group' === BP_Nouveau.activity.params.object ) || ! bp.privacyEditable ) {
					return false;
				}
				e.preventDefault();
				$( '#activity-post-form-privacy' ).show();
				$( '#whats-new-form' ).addClass( 'focus-in--privacy' );
				Backbone.trigger('privacy:headerupdate');
				if ( $( '#whats-new-form' ).hasClass( 'bp-activity-edit' ) ) {
					this.model.set( 'privacy', this.$el.closest( '#whats-new-form' ).find( '.bp-activity-privacy__input:checked' ).val() );
				}
			}
		}
	);

	bp.Views.PrivacyStage = bp.View.extend(
		{
			tagName: 'div',
			id: 'whats-new-privacy-stage',
			className: 'bp-activity-privacy-stage',
			events: {
				'click #privacy-status-submit': 'privacyStatusSubmit',
				'click #privacy-status-back': 'backPrivacySelector',
				'click #privacy-status-group-back': 'backGroupSelector',
				'click input.bp-activity-privacy__input': 'privacySelector'
			},

			initialize: function() {
				if ( ( ! _.isUndefined( BP_Nouveau.activity.params.objects ) && 1 < _.keys( BP_Nouveau.activity.params.objects ).length ) || ( ! _.isUndefined( BP_Nouveau.activity.params.object ) && 'user' === BP_Nouveau.activity.params.object ) ) {
					var privacy_body = new bp.Views.PrivacyStageBody( { model: this.model } );
					this.views.add( privacy_body );
				}

				this.views.add( new bp.Views.PrivacyStageFooter( { model: this.model } ) );
			},

			privacyStatusSubmit: function ( e ) {
				e.preventDefault();

				var selected_privacy = this.$el.find( '.bp-activity-privacy__input:checked' ).val();
				this.model.set( 'privacy', selected_privacy );
				this.model.set( 'privacy_modal', 'general' );

				if ( ! _.isUndefined( BP_Nouveau.media ) ) {
					bp.Nouveau.Activity.postForm.postGifProfile = new bp.Views.PostGifProfile( { model: this.model } );
				}

				var whats_new_form = $( '#whats-new-form' );
				whats_new_form.removeClass( 'focus-in--privacy focus-in--group' );

				Backbone.trigger( 'privacy:updatestatus' );

				var group_item_id = this.model.attributes.item_id;
				if ( selected_privacy === 'group' ) {
					var group_name = whats_new_form.find( '#bp-item-opt-' + group_item_id ).data( 'title' );
					whats_new_form.find( '.bp-activity-privacy-status' ).text( group_name );
					this.model.set( 'item_name', group_name );
					// display image of the group.
					if ( this.model.attributes.group_image && false === this.model.attributes.group_image.includes( 'mystery-group' ) ) {
						whats_new_form.find( '#bp-activity-privacy-point span.privacy-point-icon' ).removeClass( 'privacy-point-icon' ).addClass( 'group-privacy-point-icon' );
						whats_new_form.find( '#bp-activity-privacy-point span.group-privacy-point-icon' ).html( '<img src="' + this.model.attributes.group_image + '" alt=""/>' );
					} else {
						whats_new_form.find( '#bp-activity-privacy-point span.group-privacy-point-icon img' ).remove();
						whats_new_form.find( '#bp-activity-privacy-point span.group-privacy-point-icon' ).removeClass( 'group-privacy-point-icon' ).addClass( 'privacy-point-icon' );
					}
					if ( ! _.isUndefined( BP_Nouveau.media ) ) {
						bp.Nouveau.Activity.postForm.postGifGroup = new bp.Views.PostGifGroup( { model: this.model } );
					}
				} else {
					var privacy       = this.model.attributes.privacy;
					var privacy_label = whats_new_form.find( '#' + privacy ).data( 'title' );
					whats_new_form.find( '#bp-activity-privacy-point' ).removeClass().addClass( privacy );
					whats_new_form.find( '.bp-activity-privacy-status' ).text( privacy_label );
					whats_new_form.find( '.bp-activity-privacy__input#' + privacy ).prop( 'checked', true );

					whats_new_form.find( '#bp-activity-privacy-point span.group-privacy-point-icon img' ).remove();
					whats_new_form.find( '#bp-activity-privacy-point span.group-privacy-point-icon' ).removeClass( 'group-privacy-point-icon' ).addClass( 'privacy-point-icon' );

					this.model.set( 'item_id', 0 );
					this.model.set( 'item_name', '' );
					this.model.set( 'group_name', '' );
					this.model.set( 'group_image', '' );
					this.model.set( 'group-privacy', '' );

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
				var privacyStatus = this.model.get( 'privacy' );
				$( '#whats-new-form' ).removeClass( 'focus-in--privacy focus-in--group' );
				this.model.set( 'privacy_modal', 'general' );
				this.$el.find( 'input#' + privacyStatus ).prop( 'checked', true );
				if ( $( '#whats-new-form' ).hasClass( 'bp-activity-edit' ) ) {
					this.model.set( 'privacy', this.$el.find( '.bp-activity-privacy__input:checked' ).val() );
				}
			},

			backGroupSelector: function ( e ) {
				e.preventDefault();
				var whats_new_form = $( '#whats-new-form' );
				this.model.set( 'privacy_modal', 'profile' );
				whats_new_form.removeClass( 'focus-in--group' );
				var privacyStatus = this.model.get( 'privacy' );
				this.$el.find( 'input#' + privacyStatus ).prop( 'checked', true );
				$( '#activity-post-form-privacy' ).show();

				// Enable save button
				whats_new_form.removeClass( 'focus-in--blank-group' );
			},

			privacySelector: function ( e ) {
				var whats_new_form = $( '#whats-new-form' );
				if ( $( e.currentTarget ).val() === 'group' ) {
					$( e.currentTarget ).closest( '#whats-new-privacy-stage' ).find( '#whats-new-post-in' ).val( 'group' ).trigger('change');
					whats_new_form.addClass( 'focus-in--group' );
					this.model.set( 'privacy_modal', 'group' );
					// First time when we open group selector and select any one group and close it
					// and then back again on the same screen then object should be group to display the same view screen
					this.model.set( 'object', $( e.currentTarget ).val() );
					$( '#activity-post-form-privacy' ).hide();

					// Disable save button if no group selected
					if ( this.model.attributes.item_id === 0 ) {
						whats_new_form.addClass( 'focus-in--blank-group' );
					}
				} else {
					$( '#privacy-status-submit' ).click();
					this.model.set( 'object', 'user' );

					// Update multi media options dependent on profile/group view
					Backbone.trigger('mediaprivacytoolbar');
				}
			}
		}
	);

	bp.Views.PrivacyStageBody = bp.View.extend(
		{
			tagName: 'div',
			id: 'whats-new-privacy-stage-body',
			className: 'privacy-status-form-body',

			initialize: function () {
				// activity privacy options for profile.
				if ( ( ! _.isUndefined( BP_Nouveau.activity.params.objects ) && 1 < _.keys( BP_Nouveau.activity.params.objects ).length ) || ( ! _.isUndefined( BP_Nouveau.activity.params.object ) && 'user' === BP_Nouveau.activity.params.object ) ) {
					var privacy = new bp.Views.ActivityPrivacy( { model: this.model } );
					this.views.add( privacy );
				}

				if ( _.isUndefined( BP_Nouveau.activity.params.objects ) && 'user' === BP_Nouveau.activity.params.object ) {
					this.$el.find( '.bp-activity-privacy__label-group' ).hide().find( 'input#group' ).attr( 'disabled', true ); // disable group visibility level.
				}

				// Select box for the object.
				if ( ! _.isUndefined( BP_Nouveau.activity.params.objects ) && 1 < _.keys( BP_Nouveau.activity.params.objects ).length && ( bp.Nouveau.Activity.postForm.editActivityData === false || _.isUndefined( bp.Nouveau.Activity.postForm.editActivityData ) ) ) {
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
			id: 'whats-new-privacy-stage-footer',
			className: 'privacy-status-form-footer',
			template: bp.template( 'activity-post-privacy-stage-footer' )
		}
	);

	bp.Views.FormContent = bp.View.extend(
		{
			tagName: 'div',
			id: 'whats-new-content',
			events: {
				'click .medium-editor-toolbar-actions': 'focusEditor',
				'input #whats-new': 'focusEditorOnChange',
				'click .medium-editor-toolbar li.close-btn': 'hideToolbarSelector',
			},

			initialize: function () {
				this.$el.html( $( '<div></div>' ).prop( 'id', 'whats-new-textarea' ) );
				this.$el.append( '<input type="hidden" name="id" id="bp-activity-id" value="0"/>' );
				this.views.set( '#whats-new-textarea', new bp.Views.WhatsNew( { activity: this.options.activity } ) );
			},

			hideToolbarSelector: function ( e ) {
				e.preventDefault();
				var medium_editor = $( e.currentTarget ).closest( '#whats-new-form' ).find( '.medium-editor-toolbar' );
				medium_editor.removeClass( 'active' );
			},

			focusEditor: function ( e ) {
				if ( window.activity_editor.exportSelection() === null ) {
					$( e.currentTarget ).closest( '#whats-new-form' ).find( '#whats-new-textarea > div' ).focus();
				}
				e.preventDefault();
			},
			focusEditorOnChange: function ( e ) { // Fix issue of Editor loose focus when formatting is opened after selecting text.
				var medium_editor = $( e.currentTarget ).closest( '#whats-new-form' ).find( '.medium-editor-toolbar' );
				setTimeout(
					function () {
						medium_editor.addClass( 'medium-editor-toolbar-active' );
						$( e.currentTarget ).closest( '#whats-new-form' ).find( '#whats-new-textarea > div' ).focus();
					},
					0
				);
			}
		}
	);

	bp.Views.FormOptions = bp.View.extend(
		{
			tagName: 'div',
			id: 'whats-new-options',
			template: bp.template( 'activity-post-form-options' )
		}
	);

	bp.Views.FormTarget = bp.View.extend(
		{
			tagName: 'div',
			id: 'whats-new-post-in-box',
			className: 'in-profile',

			initialize: function () {
				var select = new bp.Views.WhatsNewPostIn( { filters: BP_Nouveau.activity.params.objects } );
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
						new bp.Views.AutoComplete(
							{
								collection: bp.Nouveau.Activity.postForm.ActivityObjects,
								type: model.get( 'selected' ),
								placeholder: model.get( 'placeholder' )
							}
						)
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
					this.views.remove('#whats-new-post-in-box-items');
					this.views.add(
						new bp.Views.AutoComplete(
							{
								collection: bp.Nouveau.Activity.postForm.ActivityObjects,
								type: this.model.get( 'object' ),
								placeholder: BP_Nouveau.activity.params.objects.group.autocomplete_placeholder,
							}
						)
					);
					// Set the object type.
					this.model.set( 'object', this.model.get( 'object' ) );
					this.model.set( 'group_name', model.get( 'name' ) );
					this.model.set( 'group_image', model.get( 'avatar_url' ) );
				} else {
					this.views.set( '#whats-new-post-in-box-items', new bp.Views.Item( { model: model } ) );
				}
			},

			updateDisplay: function () {
				if ( 'user' !== this.model.get( 'object' ) ) {
					this.$el.removeClass();

					$( '#activity-post-form-privacy' ).hide();
				} else if ( ! this.$el.hasClass( 'in-profile' ) ) {
					this.$el.addClass( 'in-profile' );

					$( '#activity-post-form-privacy' ).show();
				}
			},

			toggleMultiMediaOptions: function () {
				if ( ! _.isUndefined( BP_Nouveau.media ) ) {

					if ( 'user' !== this.model.get( 'object' ) ) {

						// check media is enable in groups or not.
						if ( BP_Nouveau.media.group_media === false ) {
							if ( 'undefined' === typeof bp.Nouveau.Activity.postForm.dropzone || null === bp.Nouveau.Activity.postForm.dropzone || 'activity-post-media-uploader' === bp.Nouveau.Activity.postForm.dropzone.element.id ) {
								$( '#whats-new-toolbar .post-media.media-support' ).removeClass( 'active' ).addClass( 'media-support-hide' );
								Backbone.trigger( 'activity_media_close' );
							}
						} else {
							$( '#whats-new-toolbar .post-media.media-support' ).removeClass('media-support-hide');
						}

						// check document is enable in groups or not.
						if ( BP_Nouveau.media.group_document === false ) {
							if ( 'undefined' === typeof bp.Nouveau.Activity.postForm.dropzone || null === bp.Nouveau.Activity.postForm.dropzone || 'activity-post-document-uploader' === bp.Nouveau.Activity.postForm.dropzone.element.id ) {
								$( '#whats-new-toolbar .post-media.document-support' ).removeClass( 'active' ).addClass( 'document-support-hide' );
								Backbone.trigger( 'activity_document_close' );
							}
						} else {
							$( '#whats-new-toolbar .post-media.document-support' ).removeClass('document-support-hide');
						}

						// check video is enable in groups or not.
						if ( BP_Nouveau.video.group_video === false ) {
							if ( 'undefined' === typeof bp.Nouveau.Activity.postForm.dropzone || null === bp.Nouveau.Activity.postForm.dropzone || 'activity-post-video-uploader' === bp.Nouveau.Activity.postForm.dropzone.element.id ) {
								$( '#whats-new-toolbar .post-video.video-support' ).removeClass( 'active' ).addClass( 'video-support-hide' );
								Backbone.trigger( 'activity_video_close' );
							}
						} else {
							$( '#whats-new-toolbar .post-video.video-support' ).removeClass('video-support-hide');
						}

						bp.Nouveau.Activity.postForm.postGifGroup = new bp.Views.PostGifGroup( { model: this.model } );

						// check emoji is enable in groups or not.
						if ( BP_Nouveau.media.emoji.groups === false ) {
							$( '#whats-new-textarea' ).find( 'img.emojioneemoji' ).remove();
							$( '#editor-toolbar .post-emoji' ).addClass('post-emoji-hide');
						} else {
							$( '#editor-toolbar .post-emoji' ).removeClass('post-emoji-hide');
						}
					} else {

						// check media is enable in profile or not.
						if ( BP_Nouveau.media.profile_media === false ) {
							if ( 'undefined' === typeof bp.Nouveau.Activity.postForm.dropzone || null === bp.Nouveau.Activity.postForm.dropzone || 'activity-post-media-uploader' === bp.Nouveau.Activity.postForm.dropzone.element.id ) {
								$( '#whats-new-toolbar .post-media.media-support' ).removeClass( 'active' ).addClass( 'media-support-hide' );
								Backbone.trigger( 'activity_media_close' );
							}
						} else {
							$( '#whats-new-toolbar .post-media.media-support' ).removeClass('media-support-hide');
						}

						// check document is enable in profile or not.
						if ( BP_Nouveau.media.profile_document === false ) {
							if ( 'undefined' === typeof bp.Nouveau.Activity.postForm.dropzone || null === bp.Nouveau.Activity.postForm.dropzone || 'activity-post-document-uploader' === bp.Nouveau.Activity.postForm.dropzone.element.id ) {
								$( '#whats-new-toolbar .post-media.document-support' ).removeClass( 'active' ).addClass( 'document-support-hide' );
								Backbone.trigger( 'activity_document_close' );
							}
						} else {
							$( '#whats-new-toolbar .post-media.document-support' ).removeClass('document-support-hide');
						}

						// check video is enable in profile or not.
						if ( BP_Nouveau.video.profile_video === false ) {
							if ( 'undefined' === typeof bp.Nouveau.Activity.postForm.dropzone || null === bp.Nouveau.Activity.postForm.dropzone || 'activity-post-video-uploader' === bp.Nouveau.Activity.postForm.dropzone.element.id ) {
								$( '#whats-new-toolbar .post-video.video-support' ).removeClass( 'active' ).addClass( 'video-support-hide' );
								Backbone.trigger( 'activity_video_close' );
							}
						} else {
							$( '#whats-new-toolbar .post-video.video-support' ).removeClass('video-support-hide');
						}

						bp.Nouveau.Activity.postForm.postGifProfile = new bp.Views.PostGifProfile( { model: this.model } );

						// check emoji is enable in profile or not.
						if ( BP_Nouveau.media.emoji.profile === false ) {
							$( '#editor-toolbar .post-emoji' ).addClass('post-emoji-hide');
							$( '#whats-new-textarea' ).find( 'img.emojioneemoji' ).remove();
						} else {
							$( '#editor-toolbar .post-emoji' ).removeClass('post-emoji-hide');
						}
					}
					$( '.medium-editor-toolbar' ).removeClass( 'active medium-editor-toolbar-active' );
					$( '#show-toolbar-button' ).removeClass( 'active' );
					$( '#show-toolbar-button' ).parent( '.show-toolbar' ).attr( 'data-bp-tooltip', $( '#show-toolbar-button' ).parent( '.show-toolbar' ).attr( 'data-bp-tooltip-show' ) );
				}
			}
		}
	);

	bp.Views.EditorToolbar = bp.View.extend(
		{
			tagName: 'div',
			id: 'editor-toolbar',
			template: bp.template( 'editor-toolbar' ),
			events: {
				'click .show-toolbar': 'toggleToolbarSelector',
				'click .post-mention': 'triggerMention'
			},

			toggleToolbarSelector: function ( e ) {
				e.preventDefault();
				var medium_editor = $( e.currentTarget ).closest( '#whats-new-form' ).find( '.medium-editor-toolbar' );
				if( !medium_editor.hasClass( 'active' ) ) { // Check only when opening toolbar
					bp.Nouveau.mediumEditorButtonsWarp( medium_editor );
				}
				$( e.currentTarget ).find( '.toolbar-button' ).toggleClass( 'active' );
				if ( $( e.currentTarget ).find( '.toolbar-button' ).hasClass( 'active' ) ) {
					$( e.currentTarget ).attr( 'data-bp-tooltip', jQuery( e.currentTarget ).attr( 'data-bp-tooltip-hide' ) );
					if ( window.activity_editor.exportSelection() != null ) {
						medium_editor.addClass( 'medium-editor-toolbar-active' );
					}
				} else {
					$( e.currentTarget ).attr( 'data-bp-tooltip', jQuery( e.currentTarget ).attr( 'data-bp-tooltip-show' ) );
					if ( window.activity_editor.exportSelection() === null ) {
						medium_editor.removeClass( 'medium-editor-toolbar-active' );
					}
					medium_editor.find( 'li.medium-editor-action-more').removeClass( 'active' );
				}
				$( window.activity_editor.elements[0] ).focus();
				medium_editor.toggleClass( 'medium-editor-toolbar-active active' );
			},

			triggerMention: function ( e ) {
				e.preventDefault();
				var $this = this.$el;
				var editor = $this.closest( '.activity-update-form' ).find( '#whats-new' );

				var scrollPostion = $this.closest( '.whats-new-scroll-view' ).scrollTop();

				setTimeout( function () {
					editor.focus();

					//Restore caret position start
					if( window.activityCaretPosition ) {
						if (window.getSelection && document.createRange) {
							var range = document.createRange();
							range.setStart(window.activityCaretPosition.startContainer, window.activityCaretPosition.startOffset);
							range.setEnd(window.activityCaretPosition.endContainer, window.activityCaretPosition.endOffset);
							var sel = window.getSelection();
							sel.removeAllRanges();
							sel.addRange(range);
						} else {
							var textRange = document.body.createTextRange();
							textRange.moveToElementText(editor[0]);
							textRange.setStart(window.activityCaretPosition.startContainer, window.activityCaretPosition.startOffset);
							textRange.setEnd(window.activityCaretPosition.endContainer, window.activityCaretPosition.endOffset);
							textRange.select();
						}
					}
					//Restore caret position end

					// Get character before cursor start
					var currentRange = window.getSelection().getRangeAt(0).cloneRange();
					currentRange.collapse(true);
					currentRange.setStart(editor[0], 0);
					var precedingChar = currentRange.toString().slice(-1);
					// Get character before cursor end

					if( !$( currentRange.endContainer.parentElement ).hasClass( 'atwho-inserted' ) ) { // Do nothing if mention '@' is already inserted

						if( precedingChar.trim() === '') { // Check if there's space or add one
							document.execCommand('insertText', false, '@');
						} else if( precedingChar !== '@' ){
							document.execCommand('insertText', false, ' @');
						}

					}
					editor.trigger( 'keyup' );
					setTimeout( function () {
						editor.trigger( 'keyup' );
						$this.closest( '.whats-new-scroll-view' ).scrollTop(scrollPostion);
					},0);
				},0);

			}

		}
	);

	bp.Views.ActivityToolbar = bp.View.extend(
		{
			tagName: 'div',
			id: 'whats-new-toolbar',
			template: bp.template( 'whats-new-toolbar' ),
			events: {
				'click .post-elements-buttons-item.disable .toolbar-button': 'disabledButton',
				'click #activity-link-preview-button': 'toggleURLInput',
				'click #activity-gif-button': 'toggleGifSelector',
				'click #activity-media-button': 'toggleMediaSelector',
				'click #activity-document-button': 'toggleDocumentSelector',
				'click #activity-video-button': 'toggleVideoSelector',
				'click .post-elements-buttons-item:not( .post-gif ):not( .post-media ):not( .post-video )': 'activeButton',
				'click .post-elements-buttons-item.post-gif:not(.disable)': 'activeMediaButton',
				'click .post-elements-buttons-item.post-media:not(.disable)': 'activeMediaButton',
				'click .post-elements-buttons-item.post-video:not(.disable)': 'activeVideoButton',
				'click .post-elements-buttons-item:not(.post-gif):not(.active)': 'scrollToMedia',
			},
			gifMediaSearchDropdownView: false,

			initialize: function () {
				document.addEventListener( 'keydown', _.bind( this.closePickersOnEsc, this ) );
				$( document ).on( 'click', _.bind( this.closePickersOnClick, this ) );
			},

			render: function () {
				this.$el.html( this.template( this.model.attributes ) );
				this.$self          = this.$el.find( '#activity-gif-button' );
				this.$gifPickerEl   = this.$el.find( '.gif-media-search-dropdown' );
				this.$emojiPickerEl = $( '#whats-new' );
				this.$el.removeClass( 'hidden' );
				setTimeout( function() {
					var $thisEl = $('.activity-form #whats-new-toolbar');
					if( $thisEl ) {
						if( $thisEl.children(':visible').length === 0 ) {
							$thisEl.addClass( 'hidden' );
						} else {
							$thisEl.removeClass( 'hidden' );
						}
					}
				},0);

				return this;
			},

			toggleURLInput: function ( e ) {
				var event;
				e.preventDefault();
				this.closeMediaSelector();
				this.closeGifSelector();
				this.closeDocumentSelector();
				this.closeVideoSelector();

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

				var parentElement = $( e.currentTarget ).closest( '.post-elements-buttons-item' );
				if( parentElement.hasClass( 'no-click' ) || parentElement.hasClass( 'disable' ) ) {
					return;
				}

				this.closeMediaSelector();
				this.closeDocumentSelector();
				this.closeVideoSelector();

				if ( this.$gifPickerEl.is( ':empty' ) ) {
					this.gifMediaSearchDropdownView = new bp.Views.GifMediaSearchDropdown( { model: this.model } );
					this.$gifPickerEl.html( this.gifMediaSearchDropdownView.render().el );
				}

				var gif_box = $( e.currentTarget ).parents( '#whats-new-form' ).find( '#whats-new-attachments .activity-attached-gif-container' );
				if ( this.$self.hasClass( 'open' ) && gif_box.length && $.trim( gif_box.html() ) == '' ) {
					this.$self.removeClass( 'open' );
				} else {
					this.$self.addClass( 'open' );
				}
				if ( e.type !== 'bp_activity_edit' ) {
					this.$gifPickerEl.toggleClass( 'open' );
				}
			},

			closeGifSelector: function () {
				Backbone.trigger( 'activity_gif_close' );
			},

			toggleMediaSelector: function ( e ) {
				e.preventDefault();
				var parentElement = $( e.currentTarget ).closest( '.post-elements-buttons-item' );
				if( !$( '.activity-form' ).hasClass( 'focus-in' ) || parentElement.hasClass( 'no-click' ) || parentElement.hasClass( 'disable' ) ) {
					return;
				}

				this.closeGifSelector();
				this.closeDocumentSelector();
				this.closeVideoSelector();

				Backbone.trigger( 'activity_media_toggle' );
			},

			toggleDocumentSelector: function ( e ) {
				e.preventDefault();

				var parentElement = $( e.currentTarget ).closest( '.post-elements-buttons-item' );
				if( !$( '.activity-form' ).hasClass( 'focus-in' ) || parentElement.hasClass( 'no-click' ) || parentElement.hasClass( 'disable' )) {
					return;
				}

				this.closeGifSelector();
				this.closeMediaSelector();
				this.closeVideoSelector();

				Backbone.trigger( 'activity_document_toggle' );
			},

			toggleVideoSelector: function ( e ) {
				e.preventDefault();
				var parentElement = $( e.currentTarget ).closest( '.post-elements-buttons-item' );
				if( !$( '.activity-form' ).hasClass( 'focus-in' ) || parentElement.hasClass( 'no-click' ) || parentElement.hasClass( 'disable' )) {
					return;
				}
				this.closeMediaSelector();
				this.closeDocumentSelector();
				this.closeGifSelector();

				Backbone.trigger( 'activity_video_toggle' );
			},

			closeMediaSelector: function () {
				Backbone.trigger( 'activity_media_close' );
			},

			closeDocumentSelector: function () {
				Backbone.trigger( 'activity_document_close' );
			},

			closeVideoSelector: function () {
				Backbone.trigger( 'activity_video_close' );
			},

			closePickersOnEsc: function ( event ) {
				if ( event.key === 'Escape' || event.keyCode === 27 ) {
					if ( ! _.isUndefined( BP_Nouveau.media ) && ! _.isUndefined( BP_Nouveau.media.gif_api_key ) ) {
						this.$self.removeClass( 'open' );
						this.$gifPickerEl.removeClass( 'open' );
					}
				}
			},

			closePickersOnClick: function ( event ) {
				var $targetEl = $( event.target );

				if ( ! _.isUndefined( BP_Nouveau.media ) && ! _.isUndefined( BP_Nouveau.media.gif_api_key ) &&
				     ! $targetEl.closest( '.post-gif' ).length ) {

					var gif_box = $targetEl.parents( '#whats-new-form' ).find( '#whats-new-attachments .activity-attached-gif-container' );
					if ( gif_box.length && $.trim( gif_box.html() ) !== '' ) {
						this.$self.addClass( 'open' );
					} else {
						this.$self.removeClass( 'open' );
					}

					this.$gifPickerEl.removeClass( 'open' );
				}

			},

			activeButton: function ( event ) {
				if ( $( event.currentTarget ).hasClass( 'active' ) ) {
					this.$el.find( '.post-elements-buttons-item:not( .post-gif ):not( .post-media ):not( .post-video )' ).removeClass( 'active' );
				} else {
					this.$el.find( '.post-elements-buttons-item:not( .post-gif ):not( .post-media ):not( .post-video )' ).removeClass( 'active' );
					event.currentTarget.classList.add( 'active' );
				}

				var gif_box = $( event.currentTarget ).parents( '#whats-new-form' ).find( '#whats-new-attachments .activity-attached-gif-container' );
				if ( gif_box.length && $.trim( gif_box.html() ) == '' ) {
					this.$self.removeClass( 'open' );
				}
			},

			activeMediaButton: function ( event ) {
				if ( $( event.currentTarget ).hasClass( 'active' ) ) {
					this.$el.find( '.post-elements-buttons-item.post-gif, .post-elements-buttons-item.post-media, .post-elements-buttons-item.post-video' ).removeClass( 'active' );
				} else {
					this.$el.find( '.post-elements-buttons-item.post-gif, .post-elements-buttons-item.post-media, .post-elements-buttons-item.post-video' ).removeClass( 'active' );
					event.currentTarget.classList.add( 'active' );
				}
			},

			activeVideoButton: function ( event ) {
				this.$el.find( '.post-elements-buttons-item.post-gif, .post-elements-buttons-item.post-media' ).removeClass( 'active' );

				if ( $( event.currentTarget ).hasClass( 'active' ) ) {
					event.currentTarget.classList.remove( 'active' );
				} else {
					event.currentTarget.classList.add( 'active' );
				}
			},

			disabledButton: function () {
				Backbone.trigger( 'onError', BP_Nouveau.activity.params.errors.media_fail, 'info noMediaError' );
			},

			scrollToMedia: function () {
				var whatNewForm = this.$el.closest( '#whats-new-form' );
				var whatNewScroll = whatNewForm.find( '.whats-new-scroll-view' );

				whatNewScroll.stop().animate({
					scrollTop: whatNewScroll[0].scrollHeight
				}, 300);
			}
		}
	);

	bp.Views.ActivityAttachments = bp.View.extend(
		{
			tagName: 'div',
			id: 'whats-new-attachments',
			activityLinkPreview: null,
			activityAttachedGifPreview: null,
			activityMedia: null,
			activityDocument: null,
			activityVideo: null,
			className: 'empty',
			initialize: function () {
				if ( ! _.isUndefined( BP_Nouveau.activity.params.link_preview ) ) {
					this.activityLinkPreview = new bp.Views.ActivityLinkPreview( { model: this.model } );
					this.views.add( this.activityLinkPreview );
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
					bp.draft_activity.display_post = '';
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
				this.views.add( new bp.View( { tagName: 'ul', id: 'whats-new-buttons' } ) );

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
				this.views.add( '#whats-new-buttons', new bp.Views.FormButton( { model: button } ) );
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
						this.views._views[ '#whats-new-buttons' ],
						function ( view ) {
							if ( view.model.get( 'id' ) !== button.get( 'id' ) ) {
								// Silently update the model.
								view.model.set( 'active', false, { silent: true } );

								// Remove the active class.
								view.$el.removeClass( 'active' );

								// Trigger an even to let Buttons reset
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
			className: 'whats-new-button',
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
						id: 'aw-whats-new-reset',
						className: 'text-button small',
						value: BP_Nouveau.activity.strings.cancelButton
					}
				);

				var buttomText = BP_Nouveau.activity.strings.postUpdateButton;
				if ( $( '#whats-new-form' ).hasClass( 'bp-activity-edit' ) ) {
					buttomText = BP_Nouveau.activity.strings.updatePostButton;
				}
				this.submit = new bp.Views.ActivityInput(
					{
						model: this.model,
						type: 'submit',
						id: 'aw-whats-new-submit',
						className: 'button',
						name: 'aw-whats-new-submit',
						value: buttomText
					}
				);

				this.discard = new bp.Views.ActivityInput(
					{
						model: this.model,
						type: 'button',
						id: 'discard-draft-activity',
						className: 'button outline',
						name: 'discard-draft-activity',
						value: BP_Nouveau.activity.strings.discardButton
					}
				);

				this.views.set( [ this.submit, this.reset, this.discard ] );

				this.model.on( 'change:object', this.updateDisplay, this );
				this.model.on( 'change:posting', this.updateStatus, this );
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
			id: 'activity-form-submit-wrapper',
			initialize: function () {
				$( '#whats-new-form' ).addClass( 'focus-in' ).parent().addClass( 'modal-popup' ).closest( 'body' ).addClass( 'activity-modal-open' ); // add some class to form so that DOM knows about focus.

				//Show placeholder form
				$( '#bp-nouveau-activity-form-placeholder' ).show();

				this.views.add( new bp.Views.FormSubmit( { model: this.model } ) );
			}
		}
	);

	bp.Views.PostForm = bp.View.extend(
		{
			tagName: 'form',
			className: 'activity-form',
			id: 'whats-new-form',

			attributes: {
				name: 'whats-new-form',
				method: 'post'
			},

			events: {
				'focus #whats-new': 'displayFull',
				'input #whats-new': 'postValidate',
				'reset': 'resetForm',
				'submit': 'postUpdate',
				'keydown': 'postUpdate',
				'click #whats-new-toolbar': 'triggerDisplayFull',
				'change .medium-editor-toolbar-input': 'mediumLink',
				'click #discard-draft-activity': 'discardDraftActivity',
			},

			initialize: function () {
				this.model = new bp.Models.Activity(
					_.pick(
						BP_Nouveau.activity.params,
						[ 'user_id', 'item_id', 'object' ]
					)
				);

				this.listenTo(Backbone, 'mediaprivacy', this.updateMultiMediaOptions);
				this.listenTo(Backbone, 'mediaprivacytoolbar', this.updateMultiMediaToolbar);

				this.listenTo(Backbone, 'onError', this.onError);
				this.listenTo(Backbone, 'cleanFeedBack', this.cleanFeedback);

				if ( 'user' === BP_Nouveau.activity.params.object ) {
					if ( ! BP_Nouveau.activity.params.access_control_settings.can_create_activity ) {
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
						new bp.Views.ActivityToolbar( { model: this.model } ) //Add Toolbar to show in default view
					]
				);

				this.model.on( 'change:errors', this.displayFeedback, this );

				var $this = this;
				$( document ).ready( function ( event ) {
					$( '#whats-new-form' ).closest( 'body' ).addClass( 'initial-post-form-open' );
					if ( $( 'body' ).hasClass( 'initial-post-form-open' ) ) {
						$this.displayFull( event );
						$this.$el.closest( '.activity-update-form' ).find( '#aw-whats-new-reset' ).trigger( 'click' ); //Trigger reset
					}
					if ( ! _.isUndefined( BP_Nouveau.media ) &&
					     ! _.isUndefined( BP_Nouveau.media.emoji ) &&
					     (
						     ( ! _.isUndefined( BP_Nouveau.media.emoji.profile ) && BP_Nouveau.media.emoji.profile ) ||
						     ( ! _.isUndefined( BP_Nouveau.media.emoji.groups ) && BP_Nouveau.media.emoji.groups )
					     )
					) {
						$( '#whats-new' ).emojioneArea(
							{
								standalone: true,
								hideSource: false,
								container: '#editor-toolbar > .post-emoji',
								autocomplete: false,
								pickerPosition: 'bottom',
								hidePickerOnBlur: true,
								useInternalCDN: false,
								events: {
									emojibtn_click: function () {
										$( '#whats-new' )[0].emojioneArea.hidePicker();
										if ( window.getSelection && document.createRange ) { //Get caret position when user adds emoji
											var sel = window.getSelection && window.getSelection();
											if ( sel && sel.rangeCount > 0 ) {
												window.activityCaretPosition = sel.getRangeAt( 0 );
											}
										} else {
											window.activityCaretPosition = document.selection.createRange();
										}

										// Enable post submit button
										$( '#whats-new-form' ).removeClass( 'focus-in--empty' );
									},

									picker_show: function () {
										$( this.button[0] ).closest( '.post-emoji' ).addClass('active');
									},

									picker_hide: function () {
										$( this.button[0] ).closest( '.post-emoji' ).removeClass('active');
									},
								}
							}
						);
					}
				} );
			},

			postValidate: function () {
				var $whatsNew = this.$el.find( '#whats-new' );
				var content = $.trim( $whatsNew[0].innerHTML.replace( /<div>/gi, '\n' ).replace( /<\/div>/gi, '' ) );
				content     = content.replace( /&nbsp;/g, ' ' );

				if ( $( $.parseHTML( content ) ).text().trim() !== '' || ( ! _.isUndefined( this.model.get( 'link_success' ) ) && true === this.model.get( 'link_success' ) ) || ( ! _.isUndefined( this.model.get( 'video' ) ) && 0 !== this.model.get('video').length ) || ( ! _.isUndefined( this.model.get( 'document' ) ) && 0 !== this.model.get('document').length ) || ( ! _.isUndefined( this.model.get( 'media' ) ) && 0 !== this.model.get('media').length ) || ( ! _.isUndefined( this.model.get( 'gif_data' ) ) && ! _.isEmpty( this.model.get( 'gif_data' ) ) ) ) {
					this.$el.removeClass( 'focus-in--empty' );
				} else {
					this.$el.addClass( 'focus-in--empty' );
				}
			},

			mediumLink: function () {
				var value = $( '.medium-editor-toolbar-input' ).val();

				if ( value !== '' ) {
					$( '#whats-new-form' ).removeClass( 'focus-in--empty' );
				}
			},

			displayFull: function ( event ) {

				// Remove post update notice before opening a modal
				if ( 6 !== this.views._views[ '' ].length && $( this.views._views[ '' ][6].$el ).hasClass('updated') ) {
					this.cleanFeedback();
					$( '#whats-new-form' ).removeClass( 'bottom-notice' );
				}

				if ( 6 !== this.views._views[ '' ].length ) {
					return;
				}

				if ( 'focusin' === event.type ) {
					$( '#whats-new-form' ).closest( 'body' ).removeClass( 'initial-post-form-open' ).addClass( event.type + '-post-form-open' );
				}
				this.model.on( 'change:video change:document change:media change:gif_data change:privacy, change:link_success', this.postValidate, this );

				// Remove feedback.
				var self = this;
				_.each(
					this.views._views[ '' ],
					function ( view ) {
						if ( 'message-feedabck' === view.$el.prop( 'id' ) && ! view.$el.hasClass( 'noMediaError' ) ) { // Do not remove Media error message.
							self.cleanFeedback();
							self.$el.removeClass( 'has-feedback' );
						}
					}
				);

				_.each(
					this.views._views[ '' ],
					function( view, index ) {
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
				if ( true === BP_Nouveau.activity.params.backcompat ) {
					this.views.add( new bp.Views.FormOptions( { model: this.model } ) );
				}

				// Attach buttons.
				if ( ! _.isUndefined( BP_Nouveau.activity.params.buttons ) ) {
					// Global.
					bp.Nouveau.Activity.postForm.buttons.set( BP_Nouveau.activity.params.buttons );
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

				// Wrap Toolbar and submit Wrapper into footer.
				if ( $( 'body' ).hasClass( event.type + '-post-form-open' ) ) {
					$( '.activity-update-form #whats-new-form' ).append( '<div class="whats-new-form-footer"></div>' );
					$( '.activity-update-form #whats-new-form' ).find( '#whats-new-toolbar' ).appendTo( '.whats-new-form-footer' );
					$( '.activity-update-form #whats-new-form' ).find( '#activity-form-submit-wrapper' ).appendTo( '.whats-new-form-footer' );
				}

				if ( $( '.activity-update-form .whats-new-scroll-view' ).length ) {
					$( '.activity-update-form  #whats-new-attachments' ).appendTo( '.activity-update-form .whats-new-scroll-view' );
				} else {
					$( '.activity-update-form .whats-new-form-header, .activity-update-form  #whats-new-attachments' ).wrapAll( '<div class="whats-new-scroll-view"></div>' );
					$( '.whats-new-scroll-view' ).on(
						'scroll',
						function() {
							if ( ! ( /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test( navigator.userAgent ) ) ) {
								$( '.atwho-container #atwho-ground-whats-new .atwho-view' ).hide();
							}
						}
					);

					// Hide mention dropdown while window resized.
					$( window ).on(
						'resize',
						function() {
							$( '.atwho-container #atwho-ground-whats-new .atwho-view:visible' ).hide();
						}
					);
				}
				this.updateMultiMediaOptions();

				// Trigger Media click.
				if ( window.activityMediaAction !== null ) {
					$( '.activity-update-form.modal-popup' ).find( '#' + window.activityMediaAction ).trigger( 'click' );
					window.activityMediaAction = null;
				}
				// Add Overlay.
				if ( $( '.activity-update-form .activity-update-form-overlay' ).length === 0 ) {
					$( '.activity-update-form.modal-popup' ).prepend( '<div class="activity-update-form-overlay"></div>' );
				}
				this.activityHideModalEvent();

				if ( $( 'body' ).hasClass( event.type + '-post-form-open' ) && ! $( '#whats-new-form' ).hasClass( 'bp-activity-edit' ) ) {

					if ( ! bp.draft_local_interval ) {
						bp.draft_local_interval = setInterval(
							function() {
								bp.Nouveau.Activity.postForm.storeDraftActivity();
							},
							3000
						);
					}

					if ( ! bp.draft_ajax_interval ) {
						bp.draft_ajax_interval = setInterval(
							function() {
								bp.Nouveau.Activity.postForm.postDraftActivity( false, false );
							},
							20000
						);
					}

					// Display draft activity.
					bp.Nouveau.Activity.postForm.displayDraftActivity();
				}

				$('a.bp-suggestions-mention:empty').remove();
			},

			activityHideModalEvent: function () {

				$( document ).on(
					'keyup',
					function ( event ) {
						if ( event.keyCode === 27 && false === event.ctrlKey ) {
							setTimeout( function() {
								$( '.activity-update-form.modal-popup #whats-new' ).blur();
								$( '.activity-update-form.modal-popup #aw-whats-new-reset' ).trigger( 'click' );
								// Post activity hide modal
								var $singleActivityFormWrap = $( '#bp-nouveau-single-activity-edit-form-wrap' );
								if ( $singleActivityFormWrap.length ) {
									$singleActivityFormWrap.hide();
								}
							},0);
						}
					}
				);

			},

			triggerDisplayFull: function ( event ) {
				event.preventDefault();

				//Check for media click
				if( $( event.target ).hasClass( 'toolbar-button' ) || $( event.target ).parent().hasClass( 'toolbar-button' ) ) {
					window.activityMediaAction = $( event.target ).parent().attr( 'id' );
					if ( 'undefined' === typeof window.activityMediaAction ) {
						window.activityMediaAction = $( event.target ).attr( 'id' );
					}
				}
				if( !this.$el.hasClass( 'focus-in' ) ){
					//Set focus on "#whats-new" to trigger 'displayFull'
					var element = this.$el.find( '#whats-new' )[0];
					var element_selection = window.getSelection();
					var element_range = document.createRange();
					element_range.setStart( element, 0);
					element_range.setEnd( element, 0);
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

				$( '#whats-new' ).css(
					{
						resize: 'none',
						height: '50px'
					}
				);

				$( '#whats-new-form' ).removeClass( 'focus-in focus-in--privacy focus-in--group focus-in--scroll has-draft' ).parent().removeClass( 'modal-popup' ).closest( 'body' ).removeClass( 'activity-modal-open' ); // remove class when reset.

				//Hide placeholder form
				$( '#bp-nouveau-activity-form-placeholder' ).hide();

				$( '#whats-new-content' ).find( '#bp-activity-id' ).val( '' ); // reset activity id if in edit mode.
				bp.Nouveau.Activity.postForm.postForm.$el.removeClass( 'bp-activity-edit' );

				if ( ! _.isUndefined( BP_Nouveau.activity.params.objects ) ) {
					bp.Nouveau.Activity.postForm.postForm.$el.find('.bp-activity-privacy__label-group').show().find( 'input#group' ).attr( 'disabled', false ); // enable back group visibility level.
				}

				this.model.set( 'edit_activity', false );
				bp.Nouveau.Activity.postForm.editActivityData = false;

				if ( 'user' === BP_Nouveau.activity.params.object ) {
					if ( ! BP_Nouveau.activity.params.access_control_settings.can_create_activity ) {
						this.$el.addClass( 'bp-hide' );
					} else {
						this.$el.removeClass( 'bp-hide' );
					}
				}

				// Reset the model.
				this.model.clear();
				this.model.set( this.resetModel.attributes );

				var whats_new_form = $( '#whats-new-form' );

				whats_new_form.find( '#public.bp-activity-privacy__input' ).prop( 'checked', true );
				whats_new_form.find( '#bp-activity-group-ac-items .bp-activity-object' ).removeClass( 'selected' );
				whats_new_form.find( '#bp-activity-group-ac-items .bp-activity-object__radio' ).prop( 'checked', false );

				$( '.medium-editor-toolbar' ).removeClass( 'active medium-editor-toolbar-active' );
				$( '#show-toolbar-button' ).removeClass( 'active' );
				$( '.medium-editor-action' ).removeClass( 'medium-editor-button-active' );
				$( '.medium-editor-toolbar-actions' ).show();
				$( '.medium-editor-toolbar-form' ).removeClass( 'medium-editor-toolbar-form-active' );
				$( '#show-toolbar-button' ).parent( '.show-toolbar' ).attr( 'data-bp-tooltip', $( '#show-toolbar-button' ).parent( '.show-toolbar' ).attr( 'data-bp-tooltip-show' ) );

				//Add Toolbar to show in default view
				bp.Nouveau.Activity.postForm.activityToolbar = new bp.Views.ActivityToolbar( { model: this.model } );
				this.views.add( bp.Nouveau.Activity.postForm.activityToolbar );

				//Reset activity link preview
				$( '#whats-new' ).removeData( 'activity-url-preview' );

				// Remove footer wrapper
				this.$el.find( '.whats-new-form-footer' ).remove();

				this.updateMultiMediaOptions();
			},

			cleanFeedback: function () {
				_.each(
					this.views._views[ '' ],
					function ( view ) {
						if ( 'message-feedabck' === view.$el.prop( 'id' ) ) {
							view.remove();
							$( '#whats-new-form #activity-header' ).css( { 'margin-bottom': 0 } );
						}
					}
				);
			},

			displayFeedback: function ( model ) {
				if ( _.isUndefined( this.model.get( 'errors' ) ) ) {
					this.cleanFeedback();
					this.$el.removeClass( 'has-feedback' );
				} else {
					this.cleanFeedback(); //Clean if there's any error already displayed.
					this.views.add( new bp.Views.activityFeedback( model.get( 'errors' ) ) );
					this.$el.addClass( 'has-feedback' );
					var errorHeight = this.$el.find( '#message-feedabck' ).outerHeight( true );
					this.$el.find( '#activity-header' ).css( { 'margin-bottom': errorHeight + 'px' } );
				}
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
						if ( -1 === _.indexOf( [ 'aw-whats-new-submit', 'whats-new-post-in' ], pair.name ) ) {
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
				var $whatsNew = self.$el.find( '#whats-new' );

				var atwho_query = $whatsNew.find( 'span.atwho-query' );
				for ( var i = 0; i < atwho_query.length; i++ ) {
					$( atwho_query[ i ] ).replaceWith( atwho_query[ i ].innerText );
				}

				// transform other emoji into emojionearea emoji.
				$whatsNew.find( 'img.emoji' ).each(
					function( index, Obj) {
						$( Obj ).addClass( 'emojioneemoji' );
						var emojis = $( Obj ).attr( 'alt' );
						$( Obj ).attr( 'data-emoji-char', emojis );
						$( Obj ).removeClass( 'emoji' );
					}
				);

				// Transform emoji image into emoji unicode.
				$whatsNew.find( 'img.emojioneemoji' ).replaceWith(
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

				var medias = self.model.get( 'media' );
				if ( 'group' == self.model.get( 'object' ) && ! _.isUndefined( medias ) && medias.length ) {
					for ( var k = 0; k < medias.length; k++ ) {
						medias[ k ].group_id = self.model.get( 'item_id' );
					}
					self.model.set( 'media', medias );
				}

				var document = self.model.get( 'document' );
				if ( 'group' == self.model.get( 'object' ) && ! _.isUndefined( document ) && document.length ) {
					for ( var d = 0; d < document.length; d++ ) {
						document[ d ].group_id = self.model.get( 'item_id' );
					}
					self.model.set( 'document', document );
				}

				var video = self.model.get( 'video' );
				if ( 'group' == self.model.get( 'object' ) && ! _.isUndefined( video ) && video.length ) {
					for ( var v = 0; v < video.length; v++ ) {
						video[ v ].group_id = self.model.get( 'item_id' );
					}
					self.model.set( 'video', video );
				}

				// validation for content editor.
				if ( $( $.parseHTML( content ) ).text().trim() === '' && ( ! _.isUndefined( this.model.get( 'link_success' ) ) && true !== this.model.get( 'link_success' ) ) && ( ( ! _.isUndefined( self.model.get( 'video' ) ) && ! self.model.get( 'video' ).length ) && ( ! _.isUndefined( self.model.get( 'document' ) ) && ! self.model.get( 'document' ).length ) && ( ! _.isUndefined( self.model.get( 'media' ) ) && ! self.model.get( 'media' ).length ) && ( ! _.isUndefined( self.model.get( 'gif_data' ) ) && ! Object.keys( self.model.get( 'gif_data' ) ).length ) ) ) {
					self.model.set(
						'errors',
						{
							type: 'error',
							value: BP_Nouveau.activity.params.errors.empty_post_update
						}
					);
					return false;
				}

				// update posting status true.
				self.model.set( 'posting', true );

				var data = {
					'_wpnonce_post_update': BP_Nouveau.activity.params.post_nonce
				};

				// Add the Akismet nonce if it exists.
				if ( $( '#_bp_as_nonce' ).val() ) {
					data._bp_as_nonce = $( '#_bp_as_nonce' ).val();
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
						'posting'
					]
				);

				// Form link preview data to pass in request if available.
				if ( self.model.get( 'link_success' ) ) {
					var images = self.model.get( 'link_images' ),
						index  = self.model.get( 'link_image_index' ),
						indexConfirm  = self.model.get( 'link_image_index_save' );
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

						var medias = self.model.get( 'media' );
						if ( ! _.isUndefined( medias ) && medias.length ) {
							for ( var k = 0; k < medias.length; k++ ) {
								medias[ k ].saved = true;
							}
							self.model.set( 'media', medias );
						}

						var link_embed = false;
						if ( self.model.get( 'link_embed' ) == true ) {
							link_embed = true;
						}

						var documents = self.model.get( 'document' );
						if ( ! _.isUndefined( documents ) && documents.length ) {
							for ( var d = 0; d < documents.length; d++ ) {
								documents[ d ].saved = true;
							}
							self.model.set( 'document', documents );
						}

						var videos = self.model.get( 'video' );
						if ( ! _.isUndefined( videos ) && videos.length ) {
							for ( var v = 0; v < videos.length; v++ ) {
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

						// Display a successful feedback if the acticity is not consistent with the displayed stream.
						if ( ! toPrepend ) {

							self.views.add(
								new bp.Views.activityFeedback(
									{
										value: response.message,
										type: 'updated'
									}
								)
							);
							$( '#whats-new-form' ).addClass( 'bottom-notice' );

							// Edit activity.
						} else if ( edit ) {
							$( '#activity-' + response.id ).replaceWith( response.activity );

							// Inject the activity into the stream only if it hasn't been done already (HeartBeat).
						} else if ( ! $( '#activity-' + response.id ).length ) {

							// It's the very first activity, let's make sure the container can welcome it!
							if ( ! $( '#activity-stream ul.activity-list' ).length ) {
								$( '#activity-stream' ).html( $( '<ul></ul>' ).addClass( 'activity-list item-list bp-list' ) );
							}

							// Prepend the activity.
							bp.Nouveau.inject( '#activity-stream ul.activity-list', response.activity, 'prepend' );

							// replace dummy image with original image by faking scroll event.
							jQuery( window ).scroll();

							if ( link_embed ) {
								if ( ! _.isUndefined( window.instgrm ) ) {
									window.instgrm.Embeds.process();
								}
								if ( ! _.isUndefined( window.FB ) && ! _.isUndefined( window.FB.XFBML ) ) {
									window.FB.XFBML.parse( $( document ).find( '#activity-' + response.id ).get( 0 ) );
								}
							}
						}

					}
				).fail(
					function ( response ) {
						self.model.set( 'posting', false );
						self.model.set(
							'errors',
							{
								type: 'error',
								value:  undefined === response.message ? BP_Nouveau.activity.params.errors.post_fail : response.message
							}
						);
					}
				);
			},

			updateMultiMediaOptions: function () {

				if ( ! _.isUndefined( BP_Nouveau.media ) ) {
					if ( 'user' !== this.model.get( 'object' ) ) {
						// check media is enable in groups or not.
						if ( BP_Nouveau.media.group_media === false ) {
							$( '#whats-new-toolbar .post-media.media-support' ).removeClass( 'active' ).addClass( 'media-support-hide' );
							Backbone.trigger( 'activity_media_close' );
						} else {
							$( '#whats-new-toolbar .post-media.media-support' ).removeClass('media-support-hide');
						}

						// check document is enable in groups or not.
						if ( BP_Nouveau.media.group_document === false ) {
							$( '#whats-new-toolbar .post-media.document-support' ).removeClass( 'active' ).addClass( 'document-support-hide' );
							Backbone.trigger( 'activity_document_close' );
						} else {
							$( '#whats-new-toolbar .post-media.document-support' ).removeClass('document-support-hide');
						}

						// check video is enable in groups or not.
						if ( BP_Nouveau.video.group_video === false ) {
							$( '#whats-new-toolbar .post-video.video-support' ).removeClass( 'active' ).addClass( 'video-support-hide' );
							Backbone.trigger( 'activity_video_close' );
						} else {
							$( '#whats-new-toolbar .post-video.video-support' ).removeClass('video-support-hide');
						}

						bp.Nouveau.Activity.postForm.postGifGroup = new bp.Views.PostGifGroup( { model: this.model } );

						// check emoji is enable in groups or not.
						if ( BP_Nouveau.media.emoji.groups === false ) {
							$( '#whats-new-textarea' ).find( 'img.emojioneemoji' ).remove();
							$( '#editor-toolbar .post-emoji' ).addClass('post-emoji-hide');
						} else {
							$( '#editor-toolbar .post-emoji' ).removeClass('post-emoji-hide');
						}
					} else {
						// check media is enable in profile or not.
						if ( BP_Nouveau.media.profile_media === false ) {
							$( '#whats-new-toolbar .post-media.media-support' ).removeClass( 'active' ).addClass( 'media-support-hide' );
							Backbone.trigger( 'activity_media_close' );
						} else {
							$( '#whats-new-toolbar .post-media.media-support' ).removeClass('media-support-hide');
						}

						// check media is enable in profile or not.
						if ( BP_Nouveau.media.profile_document === false ) {
							$( '#whats-new-toolbar .post-media.document-support' ).removeClass( 'active' ).addClass( 'document-support-hide' );
							Backbone.trigger( 'activity_document_close' );
						} else {
							$( '#whats-new-toolbar .post-media.document-support' ).removeClass('document-support-hide');
						}

						// check video is enable in groups or not.
						if ( BP_Nouveau.video.profile_video === false ) {
							$( '#whats-new-toolbar .post-video.video-support' ).removeClass( 'active' ).addClass( 'video-support-hide' );
							Backbone.trigger( 'activity_video_close' );
						} else {
							$( '#whats-new-toolbar .post-video.video-support' ).removeClass('video-support-hide');
						}

						bp.Nouveau.Activity.postForm.postGifProfile = new bp.Views.PostGifProfile( { model: this.model } );

						// check emoji is enable in profile or not.
						if ( BP_Nouveau.media.emoji.profile === false ) {
							$( '#whats-new-textarea' ).find( 'img.emojioneemoji' ).remove();
							$( '#editor-toolbar .post-emoji' ).addClass('post-emoji-hide');
						} else {
							$( '#editor-toolbar .post-emoji' ).removeClass('post-emoji-hide');
						}
					}
					$( '.medium-editor-toolbar' ).removeClass( 'active medium-editor-toolbar-active' );
				}
			},

			updateMultiMediaToolbar: function () {

				if ( ! _.isUndefined( BP_Nouveau.media ) ) {

					if ( 'user' !== this.model.get( 'object' ) ) {

						// check media is enable in groups or not.
						if ( BP_Nouveau.media.group_media === false ) {
							$( '#whats-new-toolbar .post-media.media-support' ).removeClass( 'active' ).addClass( 'media-support-hide' );
							$( '#whats-new-attachments .dropzone.media-dropzone' ).removeClass( 'open dz-clickable' ).addClass( 'closed' );
						} else {
							$( '#whats-new-toolbar .post-media.media-support' ).removeClass('media-support-hide');
						}

						// check document is enable in groups or not.
						if ( BP_Nouveau.media.group_document === false ) {
							$( '#whats-new-toolbar .post-media.document-support' ).removeClass( 'active' ).addClass( 'document-support-hide' );
							$( '#whats-new-attachments .dropzone.document-dropzone' ).removeClass( 'open dz-clickable' ).addClass( 'closed' );
						} else {
							$( '#whats-new-toolbar .post-media.document-support' ).removeClass('document-support-hide');
						}

						// check video is enable in groups or not.
						if ( BP_Nouveau.video.group_video === false ) {
							$( '#whats-new-toolbar .post-video.video-support' ).removeClass( 'active' ).addClass( 'video-support-hide' );
							$( '#whats-new-attachments .dropzone.video-dropzone' ).removeClass( 'open dz-clickable' ).addClass( 'closed' );
						} else {
							$( '#whats-new-toolbar .post-video.video-support' ).removeClass('video-support-hide');
						}

						bp.Nouveau.Activity.postForm.postGifGroup = new bp.Views.PostGifGroup( { model: this.model } );

						// check emoji is enable in groups or not.
						if ( BP_Nouveau.media.emoji.groups === false ) {
							$( '#whats-new-textarea' ).find( 'img.emojioneemoji' ).remove();
							$( '#editor-toolbar .post-emoji' ).addClass('post-emoji-hide');
						} else {
							$( '#editor-toolbar .post-emoji' ).removeClass('post-emoji-hide');
						}
					} else {

						// check media is enable in profile or not.
						if ( BP_Nouveau.media.profile_media === false ) {
							$( '#whats-new-toolbar .post-media.media-support' ).removeClass( 'active' ).addClass( 'media-support-hide' );
							$( '#whats-new-attachments .dropzone.media-dropzone' ).removeClass( 'open dz-clickable' ).addClass( 'closed' );
						} else {
							$( '#whats-new-toolbar .post-media.media-support' ).removeClass('media-support-hide');
						}

						// check media is enable in profile or not.
						if ( BP_Nouveau.media.profile_document === false ) {
							$( '#whats-new-toolbar .post-media.document-support' ).removeClass( 'active' ).addClass( 'document-support-hide' );
							$( '#whats-new-attachments .dropzone.document-dropzone' ).removeClass( 'open dz-clickable' ).addClass( 'closed' );
						} else {
							$( '#whats-new-toolbar .post-media.document-support' ).removeClass('document-support-hide');
						}

						// check video is enable in groups or not.
						if ( BP_Nouveau.video.profile_video === false ) {
							$( '#whats-new-toolbar .post-video.video-support' ).removeClass( 'active' ).addClass( 'video-support-hide' );
							$( '#whats-new-attachments .dropzone.video-dropzone' ).removeClass( 'open dz-clickable' ).addClass( 'closed' );
						} else {
							$( '#whats-new-toolbar .post-video.video-support' ).removeClass('video-support-hide');
						}

						bp.Nouveau.Activity.postForm.postGifProfile = new bp.Views.PostGifProfile( { model: this.model } );

						// check emoji is enable in profile or not.
						if ( BP_Nouveau.media.emoji.profile === false ) {
							$( '#editor-toolbar .post-emoji' ).addClass('post-emoji-hide');
							$( '#whats-new-textarea' ).find( 'img.emojioneemoji' ).remove();
						} else {
							$( '#editor-toolbar .post-emoji' ).removeClass('post-emoji-hide');
						}
					}
					$( '.medium-editor-toolbar' ).removeClass( 'active medium-editor-toolbar-active' );
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

			discardDraftActivity: function() {

				// Reset view data.
				_.each(
					this.views._views[ '' ],
					function ( view, index ) {
						if ( index > 4 ) {
							view.close();
						}
					}
				);

				$( '#whats-new' ).css(
					{
						resize: 'none',
						height: '50px'
					}
				);

				// Hide placeholder form.
				$( '#bp-nouveau-activity-form-placeholder' ).hide();

				$( '#whats-new-content' ).find( '#bp-activity-id' ).val( '' ); // reset activity id if in edit mode.
				bp.Nouveau.Activity.postForm.postForm.$el.removeClass( 'bp-activity-edit' );

				if ( ! _.isUndefined( BP_Nouveau.activity.params.objects ) ) {
					// enable back group visibility level.
					bp.Nouveau.Activity.postForm.postForm.$el.find( '.bp-activity-privacy__label-group' ).show().find( 'input#group' ).attr( 'disabled', false );
				}

				this.model.set( 'edit_activity', false );
				bp.Nouveau.Activity.postForm.editActivityData = false;

				if ( 'user' === BP_Nouveau.activity.params.object ) {
					if ( ! BP_Nouveau.activity.params.access_control_settings.can_create_activity ) {
						this.$el.addClass( 'bp-hide' );
					} else {
						this.$el.removeClass( 'bp-hide' );
					}
				}

				// Reset the model.
				this.model.clear();
				this.model.set( this.resetModel.attributes );

				// Remove footer wrapper.
				this.$el.find( '.whats-new-form-footer' ).remove();

				// Reset view.
				var whats_new_form = $( '#whats-new-form' );

				whats_new_form.find( '#public.bp-activity-privacy__input' ).prop( 'checked', true );
				whats_new_form.find( '#bp-activity-group-ac-items .bp-activity-object__radio' ).prop( 'checked', false ).removeAttr( 'checked' );
				whats_new_form.find( '#bp-activity-group-ac-items .bb-radio-style.selected' ).removeClass( 'selected' );

				$( '.medium-editor-toolbar' ).removeClass( 'active medium-editor-toolbar-active' );
				$( '#show-toolbar-button' ).removeClass( 'active' );
				$( 'medium-editor-action' ).removeClass( 'medium-editor-button-active' );
				$( '.medium-editor-toolbar-actions' ).show();
				$( '.medium-editor-toolbar-form' ).removeClass( 'medium-editor-toolbar-form-active' );
				$( '#show-toolbar-button' ).parent( '.show-toolbar' ).attr( 'data-bp-tooltip', $( '#show-toolbar-button' ).parent( '.show-toolbar' ).attr( 'data-bp-tooltip-show' ) );

				// Add Toolbar to show in default view.
				bp.Nouveau.Activity.postForm.activityAttachments = new bp.Views.ActivityAttachments( { model: this.model } );
				this.views.add( bp.Nouveau.Activity.postForm.activityAttachments );
				bp.Nouveau.Activity.postForm.activityToolbar = new bp.Views.ActivityToolbar( { model: this.model } );
				this.views.add( bp.Nouveau.Activity.postForm.activityToolbar );
				this.views.add( new bp.Views.FormSubmitWrapper( { model: this.model } ) );

				// Wrap Toolbar and submit Wrapper into footer.
				if ( $( 'body' ).hasClass( 'focusin-post-form-open' ) ) {
					$( '.activity-update-form #whats-new-form' ).append( '<div class="whats-new-form-footer"></div>' );
					$( '.activity-update-form #whats-new-form' ).find( '#whats-new-toolbar' ).appendTo( '.whats-new-form-footer' );
					$( '.activity-update-form #whats-new-form' ).find( '#activity-form-submit-wrapper' ).appendTo( '.whats-new-form-footer' );
				}

				if ( $( '.activity-update-form .whats-new-scroll-view' ).length ) {
					$( '.activity-update-form  #whats-new-attachments' ).appendTo( '.activity-update-form .whats-new-scroll-view' );
				} else {
					$( '.activity-update-form .whats-new-form-header, .activity-update-form  #whats-new-attachments' ).wrapAll( '<div class="whats-new-scroll-view"></div>' );
					$( '.whats-new-scroll-view' ).on(
						'scroll',
						function() {
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
						function() {
							$( '.atwho-container #atwho-ground-whats-new .atwho-view:visible' ).hide();
						}
					);
				}

				this.updateMultiMediaOptions();

				// Delete the activity from the database.
				bp.Nouveau.Activity.postForm.resetDraftActivity( true );
			},
		}
	);

	bp.Views.PostFormPlaceholder = bp.View.extend(
		{
			tagName: 'form',
			className: 'activity-form-placeholder',
			id: 'whats-new-form-placeholder',

			initialize: function () {
				this.model = new bp.Models.Activity(
					_.pick(
						BP_Nouveau.activity.params,
						[ 'user_id', 'item_id', 'object' ]
					)
				);

				// Clone the model to set the resetted one.
				this.resetModel = this.model.clone();

				this.views.set(
					[
						new bp.Views.UserStatusHuddle( { model: this.model } ),
						new bp.Views.FormPlaceholderContent( { activity: this.model, model: this.model } ),
						new bp.Views.ActivityToolbar( { model: this.model } ) //Add Toolbar to show in default view
					]
				);

			},

		}
	);

	bp.Views.FormPlaceholderContent = bp.View.extend(
		{
			tagName: 'div',
			id: 'whats-new-content-placeholder',

			initialize: function () {
				this.$el.html( $( '<div></div>' ).prop( 'id', 'whats-new-textarea-placeholder' ) );
				this.views.set( '#whats-new-textarea-placeholder', new bp.Views.WhatsNewPlaceholder() );
			},
		}
	);

	bp.Views.WhatsNewPlaceholder = bp.View.extend(
		{
			tagName: 'div',
			className: 'bp-suggestions-placehoder',
			id: 'whats-new-placeholder',
			attributes: {
				name: 'whats-new-placeholder',
				cols: '50',
				rows: '4',
				placeholder: BP_Nouveau.activity.strings.whatsnewPlaceholder,
				'aria-label': BP_Nouveau.activity.strings.whatsnewLabel,
				contenteditable: true,
			},
		}
	);

	bp.Views.PostGifProfile = bp.View.extend(
		{
			initialize: function () {
				// check gif is enable in profile or not.
				if ( ( ! _.isUndefined( BP_Nouveau.media.gif.profile ) && BP_Nouveau.media.gif.profile === false ) || BP_Nouveau.media.gif_api_key === '' ) {
					$( '#whats-new-toolbar .post-gif' ).removeClass( 'active' ).addClass( 'post-gif-hide' );
				} else {
					$( '#whats-new-toolbar .post-gif' ).removeClass( 'post-gif-hide' );
				}
			},
		}
	);

	bp.Views.PostGifGroup = bp.View.extend(
		{
			initialize: function () {
				// check gif is enable in groups or not.
				if ( ( ! _.isUndefined( BP_Nouveau.media.gif.groups ) && BP_Nouveau.media.gif.groups === false ) || BP_Nouveau.media.gif_api_key === '' ) {
					$( '#whats-new-toolbar .post-gif' ).removeClass( 'active' ).addClass( 'post-gif-hide' );
				} else {
					$( '#whats-new-toolbar .post-gif' ).removeClass( 'post-gif-hide' );
				}
			},
		}
	);

	bp.Nouveau.Activity.postForm.start();

} )( bp, jQuery );
