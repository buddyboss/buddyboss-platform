/* global bp, BP_Nouveau, _, Backbone, tinymce */
/* @version 3.1.0 */
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
	bp.privacyEditable = true;
	bp.album_id        = 0;
	bp.folder_id       = 0;
	bp.group_id        = 0;
	bp.privacy         = 'public';

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
		},

		postFormView: function () {
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
		},

		dropzoneView: function () {
			this.dropzone = null;

			// set up dropzones auto discover to false so it does not automatically set dropzones.
			window.Dropzone.autoDiscover = false;

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
				dictMaxFilesExceeded: BP_Nouveau.media.media_dict_file_exceeded,
				// previewTemplate : document.getElementsByClassName( 'activity-post-media-template' )[0].innerHTML.
			};

			// if defined, add custom dropzone options.
			if ( ! _.isUndefined( BP_Nouveau.media.dropzone_options ) ) {
				Object.assign( this.dropzone_options, BP_Nouveau.media.dropzone_options );
			}
		},

		displayEditActivity: function ( activity_data ) {
			var self = this;

			// reset post form before editing.
			self.postForm.$el.trigger( 'reset' );

			// set edit activity data.
			self.editActivityData = activity_data;

			self.postForm.$el.addClass( 'bp-activity-edit' ).addClass( 'loading' );
			self.postForm.$el.removeClass( 'bp-hide' );

			// add a pause to form to let it cool down a bit.
			setTimeout(
				function() {
					self.postForm.$el.find( '#whats-new' ).trigger( 'focus' );
					self.postForm.$el.find( '#whats-new' ).html( activity_data.content );
					self.postForm.$el.find( '#bp-activity-id' ).val( activity_data.id );

					var tool_box = $( '#whats-new-toolbar' );

					// set object of activity and item id when group activity.
					if ( ! _.isUndefined( activity_data.object ) && ! _.isUndefined( activity_data.item_id ) && 'groups' === activity_data.object ) {
						self.postForm.model.set( 'item_id', activity_data.item_id );
						self.postForm.model.set( 'object', 'group' );
						self.postForm.model.set( 'group_name', activity_data.group_name );
					}

					var bpActivityEvent = new Event( 'bp_activity_edit' );

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

					if ( ! _.isUndefined( activity_data.gif ) && Object.keys( activity_data.gif ).length ) {
						// close and destroy existing media instance.
						self.activityToolbar.toggleGifSelector( bpActivityEvent );
						self.activityToolbar.gifMediaSearchDropdownView.model.set( 'gif_data', activity_data.gif );

						// Make tool box button disable.
						if ( tool_box.find( '#activity-media-button' ) ) {
							tool_box.find( '#activity-media-button' ).parents( '.post-elements-buttons-item' ).addClass( 'no-click' );
						}
						if ( tool_box.find( '#activity-document-button' ) ) {
							tool_box.find( '#activity-document-button' ).parents( '.post-elements-buttons-item' ).addClass( 'no-click' );
						}
						if ( tool_box.find( '#activity-video-button' ) ) {
							tool_box.find( '#activity-video-button' ).parents( '.post-elements-buttons-item' ).addClass( 'no-click' );
						}
						if ( tool_box.find( '#activity-gif-button' ) ) {
							tool_box.find( '#activity-gif-button' ).parents( '.post-elements-buttons-item' ).addClass( 'active' );
						}
						// END Toolbox Button.
					}

					// Display media for editing.
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
							tool_box.find( '#activity-document-button' ).parents( '.post-elements-buttons-item' ).addClass( 'no-click' );
						}
						if ( tool_box.find( '#activity-video-button' ) ) {
							tool_box.find( '#activity-video-button' ).parents( '.post-elements-buttons-item' ).addClass( 'no-click' );
						}
						if ( tool_box.find( '#activity-gif-button' ) ) {
							tool_box.find( '#activity-gif-button' ).parents( '.post-elements-buttons-item' ).addClass( 'no-click' );
						}
						// END Toolbox Button.

						var mock_file = false;
						for ( var i = 0; i < activity_data.media.length; i++ ) {
							mock_file = false;

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
								media_edit_data: {
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
								}
							};

							if ( self.dropzone ) {
								self.dropzone.files.push( mock_file );
								self.dropzone.emit( 'addedfile', mock_file );
								self.createThumbnailFromUrl( mock_file );
							}
						}
					}

					if ( ! _.isUndefined( activity_data.document ) && activity_data.document.length ) {
						// open document uploader for editing document.

						if ( ! _.isUndefined( self.activityToolbar ) ) {
							self.activityToolbar.toggleDocumentSelector( bpActivityEvent );
						}

						// Make tool box button disable.
						if ( tool_box.find( '#activity-media-button' ) ) {
							tool_box.find( '#activity-media-button' ).parents( '.post-elements-buttons-item' ).addClass( 'no-click' );
						}
						if ( tool_box.find( '#activity-video-button' ) ) {
							tool_box.find( '#activity-video-button' ).parents( '.post-elements-buttons-item' ).addClass( 'no-click' );
						}
						if ( tool_box.find( '#activity-document-button' ) ) {
							tool_box.find( '#activity-document-button' ).parents( '.post-elements-buttons-item' ).addClass( 'active no-click' );
						}
						if ( tool_box.find( '#activity-gif-button' ) ) {
							tool_box.find( '#activity-gif-button' ).parents( '.post-elements-buttons-item' ).addClass( 'no-click' );
						}
						// END Toolbox Button.

						var doc_file = false;
						for ( var doci = 0; doci < activity_data.document.length; doci++ ) {
							doc_file = false;

							doc_file = {
								name: activity_data.document[ doci ].name,
								size: activity_data.document[ doci ].size,
								accepted: true,
								kind: 'file',
								upload: {
									filename: activity_data.document[ doci ].name,
									uuid: activity_data.document[ doci ].doc_id
								},
								dataURL: activity_data.document[ doci ].url,
								id: activity_data.document[ doci ].doc_id,
								document_edit_data: {
									'id': activity_data.document[ doci ].doc_id,
									'name': activity_data.document[ doci ].name,
									'type': 'document',
									'url': activity_data.document[ doci ].url,
									'size': activity_data.document[ doci ].size,
									'uuid': activity_data.document[ doci ].doc_id,
									'document_id': activity_data.document[ doci ].id,
									'menu_order': activity_data.document[ doci ].menu_order,
									'folder_id': activity_data.document[ doci ].folder_id,
									'group_id': activity_data.document[ doci ].group_id,
									'saved': true
								}
							};

							if ( self.dropzone ) {
								self.dropzone.files.push( doc_file );
								self.dropzone.emit( 'addedfile', doc_file );
								self.dropzone.emit( 'complete', doc_file );
							}
						}
					}

					/**
					 * Display Video for editing.
					 */
					if ( ! _.isUndefined( activity_data.video ) && activity_data.video.length ) {

						if ( ! _.isUndefined( self.activityToolbar ) ) {
							self.activityToolbar.toggleVideoSelector( bpActivityEvent );
						}

						// Make tool box button disable.
						if ( tool_box.find( '#activity-media-button' ) ) {
							tool_box.find( '#activity-media-button' ).parents( '.post-elements-buttons-item' ).addClass( 'no-click' );
						}
						if ( tool_box.find( '#activity-document-button' ) ) {
							tool_box.find( '#activity-document-button' ).parents( '.post-elements-buttons-item' ).addClass( 'no-click' );
						}
						if ( tool_box.find( '#activity-video-button' ) ) {
							tool_box.find( '#activity-video-button' ).parents( '.post-elements-buttons-item' ).addClass( 'active no-click' );
						}
						if ( tool_box.find( '#activity-gif-button' ) ) {
							tool_box.find( '#activity-gif-button' ).parents( '.post-elements-buttons-item' ).addClass( 'no-click' );
						}
						// END Toolbox Button.

						var video_file = false;
						for ( var vidi = 0; vidi < activity_data.video.length; vidi++ ) {
							video_file = false;

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
								video_edit_data: {
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
								}
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

					// Wrap content section for better scroll.
					$( '#whats-new-content, #whats-new-attachments' ).wrapAll( '<div class="edit-activity-content-wrap"></div>' );

					// Make selected current privacy.
					var $activityPrivacySelect = self.postForm.$el.find( '#bp-activity-privacy' );

					$activityPrivacySelect.val( activity_data.privacy );

					var privacy = $( '[data-bp-list="activity"] #activity-' + activity_data.id ).find( 'ul.activity-privacy li.selected' ).data( 'value' );
					if ( ! _.isUndefined( privacy ) ) {
						$activityPrivacySelect.val( privacy );
					}

					if ( ! _.isUndefined( activity_data ) ) {
						if ( ! _.isUndefined( activity_data.object ) && ! _.isUndefined( activity_data.item_id ) && 'groups' === activity_data.object ) {

							// check media is enable in groups or not.
							if ( ! _.isUndefined( activity_data.group_media ) && activity_data.group_media === false ) {
								$( '#whats-new-toolbar .post-media.media-support' ).removeClass( 'active' ).hide();
								$( '.edit-activity-content-wrap #whats-new-attachments .activity-media-container #activity-post-media-uploader .dz-default.dz-message' ).hide();
							} else {
								$( '#whats-new-toolbar .post-media.media-support' ).show();
							}

							// check document is enable in groups or not.
							if ( ! _.isUndefined( activity_data.group_document ) && activity_data.group_document === false ) {
								$( '#whats-new-toolbar .post-media.document-support' ).removeClass( 'active' ).hide();
								$( '.edit-activity-content-wrap #whats-new-attachments .activity-document-container #activity-post-document-uploader .dz-default.dz-message' ).hide();
							} else {
								$( '#whats-new-toolbar .post-media.document-support' ).show();
							}

							// check video is enable in groups or not.
							if ( ! _.isUndefined( activity_data.group_video ) && activity_data.group_video === false ) {
								$( '#whats-new-toolbar .post-video.video-support' ).removeClass( 'active' ).hide();
								$( '.edit-activity-content-wrap #whats-new-attachments .activity-video-container #activity-post-video-uploader .dz-default.dz-message' ).hide();
							} else {
								$( '#whats-new-toolbar .post-video.video-support' ).show();
							}

						} else {
							// check media is enable in profile or not.
							if ( ! _.isUndefined( activity_data.profile_media ) && activity_data.profile_media === false ) {
								$( '#whats-new-toolbar .post-media.media-support' ).removeClass( 'active' ).hide();
								$( '.activity-media-container #activity-post-media-uploader .dz-default.dz-message' ).hide();
								$( '.activity-media-container' ).css( 'pointer-events', 'none' );
							} else {
								$( '.activity-media-container' ).css( 'pointer-events', 'auto' );
								$( '#whats-new-toolbar .post-media.media-support' ).show();
							}

							// check document is enable in profile or not.
							if ( ! _.isUndefined( activity_data.profile_document ) && activity_data.profile_document === false ) {
								$( '#whats-new-toolbar .post-media.document-support' ).removeClass( 'active' ).hide();
								$( '.activity-document-container #activity-post-document-uploader .dz-default.dz-message' ).hide();
								$( '.activity-document-container' ).css( 'pointer-events', 'none' );
							} else {
								$( '.activity-document-container' ).css( 'pointer-events', 'auto' );
								$( '#whats-new-toolbar .post-media.document-support' ).show();
							}

							// check video is enable in profile or not.
							if ( ! _.isUndefined( activity_data.profile_video ) && activity_data.profile_video === false ) {
								$( '#whats-new-toolbar .post-video.video-support' ).removeClass( 'active' ).hide();
								$( '.activity-video-container #activity-post-video-uploader .dz-default.dz-message' ).hide();
								$( '.activity-video-container' ).css( 'pointer-events', 'none' );
							} else {
								$( '.activity-video-container' ).css( 'pointer-events', 'auto' );
								$( '#whats-new-toolbar .post-video.video-support' ).show();
							}
						}
					}

					// Do not allow the edit privacy if activity is belongs to any folder/album.
					if ( ! bp.privacyEditable ) {
						$activityPrivacySelect.parent().css( 'display', 'none' );
					} else {
						$activityPrivacySelect.parent().css( 'display', 'block' );
					}
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
		displayEditActivityForm : function( activity_data ) {
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
			self.displayEditActivity( activity_data );

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
					imageDragging: false
				}
			);

			// Now Show the Modal.
			$activityForm.addClass( 'modal-popup' );

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
						$( '.activity-update-form.modal-popup #aw-whats-new-reset' ).trigger( 'click' );
					}
				}
			);

			$( document ).on(
				'click',
				'.activity-update-form.modal-popup #aw-whats-new-reset',
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

			$( '.activity-update-form.modal-popup' ).removeClass( 'modal-popup group-activity' );

			var $activityFormPlaceholder = $( '#bp-nouveau-activity-form-placeholder' );
			var $singleActivityFormWrap  = $( '#bp-nouveau-single-activity-edit-form-wrap' );

			// unwrap hw wrapped content section.
			if ( $( '#whats-new-content' ).parent().is( '.edit-activity-content-wrap' ) ) {
				$( '#whats-new-content' ).unwrap();
			}

			$activityFormPlaceholder.hide();

			if ( $singleActivityFormWrap.length ) {
				$singleActivityFormWrap.hide();
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

	/** Views *****************************************************************/

	// Feedback messages.
	bp.Views.activityFeedback = bp.View.extend(
		{
			tagName: 'div',
			id: 'message',
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

				document.addEventListener( 'activity_media_toggle', this.toggle_media_uploader.bind( this ) );
				document.addEventListener( 'activity_media_close', this.destroy.bind( this ) );
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

				document.removeEventListener( 'activity_media_toggle', this.toggle_media_uploader.bind( this ) );
				document.removeEventListener( 'activity_media_close', this.destroy.bind( this ) );

				$( '#whats-new-attachments' ).addClass( 'empty' );
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
					dictMaxFilesExceeded: BP_Nouveau.media.media_dict_file_exceeded,
					previewTemplate : document.getElementsByClassName( 'activity-post-default-template' )[0].innerHTML
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
						} else {
							if ( ! jQuery( '.activity-media-error-popup' ).length) {
								$( 'body' ).append( '<div id="bp-media-create-folder" style="display: block;" class="open-popup activity-media-error-popup"><transition name="modal"><div class="modal-mask bb-white bbm-model-wrap"><div class="modal-wrapper"><div id="boss-media-create-album-popup" class="modal-container has-folderlocationUI"><header class="bb-model-header"><h4>' + BP_Nouveau.media.invalid_media_type + '</h4><a class="bb-model-close-button errorPopup" href="#"><span class="dashicons dashicons-no-alt"></span></a></header><div class="bb-field-wrap"><p>' + response.data.feedback + '</p></div></div></div></div></transition></div>' );
							}
							this.removeFile( file );
						}
					}
				);

				bp.Nouveau.Activity.postForm.dropzone.on(
					'error',
					function ( file, response ) {
						if ( file.accepted ) {
							if ( ! _.isUndefined( response ) && ! _.isUndefined( response.data ) && ! _.isUndefined( response.data.feedback ) ) {
								$( file.previewElement ).find( '.dz-error-message span' ).text( response.data.feedback );
							}
						} else {
							if ( ! jQuery( '.activity-media-error-popup' ).length) {
								$( 'body' ).append( '<div id="bp-media-create-folder" style="display: block;" class="open-popup activity-media-error-popup"><transition name="modal"><div class="modal-mask bb-white bbm-model-wrap"><div class="modal-wrapper"><div id="boss-media-create-album-popup" class="modal-container has-folderlocationUI"><header class="bb-model-header"><h4>' + BP_Nouveau.media.invalid_media_type + '</h4><a class="bb-model-close-button errorPopup" href="#"><span class="dashicons dashicons-no-alt"></span></a></header><div class="bb-field-wrap"><p>' + response + '</p></div></div></div></div></transition></div>' );
							}
							this.removeFile( file );
						}
					}
				);

				bp.Nouveau.Activity.postForm.dropzone.on(
					'removedfile',
					function ( file ) {
						if ( self.media.length ) {
							for ( var i in self.media ) {
								if ( file.id === self.media[ i ].id ) {
									if ( ! _.isUndefined( self.media[ i ].saved ) && ! self.media[ i ].saved ) {
										bp.Nouveau.Media.removeAttachment( file.id );
									}
									self.media.splice( i, 1 );
									self.model.set( 'media', self.media );
								}
							}
						}

						if ( ! _.isNull( bp.Nouveau.Activity.postForm.dropzone.files ) && bp.Nouveau.Activity.postForm.dropzone.files.length === 0 ) {
							var tool_box = self.$el.parents( '#whats-new-form' );
							if ( tool_box.find( '#activity-document-button' ) ) {
								tool_box.find( '#activity-document-button' ).parents( '.post-elements-buttons-item' ).removeClass( 'disable' );
							}
							if ( tool_box.find( '#activity-video-button' ) ) {
								tool_box.find( '#activity-video-button' ).parents( '.post-elements-buttons-item' ).removeClass( 'disable' );
							}
							if ( tool_box.find( '#activity-gif-button' ) ) {
								tool_box.find( '#activity-gif-button' ).parents( '.post-elements-buttons-item' ).removeClass( 'disable' );
							}
							if ( tool_box.find( '#activity-media-button' ) ) {
								tool_box.find( '#activity-media-button' ).parents( '.post-elements-buttons-item' ).removeClass( 'no-click' );
							}
						}
					}
				);

				self.$el.find( '#activity-post-media-uploader' ).addClass( 'open' ).removeClass( 'closed' );
				$( '#whats-new-attachments' ).removeClass( 'empty' );
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

				document.addEventListener( 'activity_document_toggle', this.toggle_document_uploader.bind( this ) );
				document.addEventListener( 'activity_document_close', this.destroyDocument.bind( this ) );
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

				document.removeEventListener( 'activity_document_toggle', this.toggle_document_uploader.bind( this ) );
				document.removeEventListener( 'activity_document_close', this.destroyDocument.bind( this ) );

				$( '#whats-new-attachments' ).addClass( 'empty' );
			},

			open_document_uploader: function () {
				var self = this;

				if ( self.$el.find( '#activity-post-document-uploader' ).hasClass( 'open' ) ) {
					return false;
				}
				self.destroyDocument();

				var dropzone_options = {
					url                  : BP_Nouveau.ajaxurl,
					timeout              : 3 * 60 * 60 * 1000,
					dictFileTooBig       : BP_Nouveau.media.dictFileTooBig,
					acceptedFiles        : BP_Nouveau.media.document_type,
					createImageThumbnails: false,
					dictDefaultMessage   : BP_Nouveau.media.dropzone_document_message,
					autoProcessQueue     : true,
					addRemoveLinks       : true,
					uploadMultiple       : false,
					maxFiles             : ! _.isUndefined( BP_Nouveau.document.maxFiles ) ? BP_Nouveau.document.maxFiles : 10,
					maxFilesize          : ! _.isUndefined( BP_Nouveau.document.max_upload_size ) ? BP_Nouveau.document.max_upload_size : 2,
					dictInvalidFileType  : BP_Nouveau.document.dictInvalidFileType,
					dictMaxFilesExceeded : BP_Nouveau.media.document_dict_file_exceeded,
					previewTemplate : document.getElementsByClassName( 'activity-post-document-template' )[0].innerHTML
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
							response.data.saved      = false;
							response.data.menu_order = $( file.previewElement ).closest( '.dropzone' ).find( file.previewElement ).index() - 1;
							self.document.push( response.data );
							self.model.set( 'document', self.document );
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
							}
						} else {
							if ( ! jQuery( '.document-error-popup' ).length) {
								$( 'body' ).append( '<div id="bp-media-create-folder" style="display: block;" class="open-popup document-error-popup"><transition name="modal"><div class="modal-mask bb-white bbm-model-wrap"><div class="modal-wrapper"><div id="boss-media-create-album-popup" class="modal-container has-folderlocationUI"><header class="bb-model-header"><h4>' + BP_Nouveau.media.invalid_file_type + '</h4><a class="bb-model-close-button errorPopup" href="#"><span class="dashicons dashicons-no-alt"></span></a></header><div class="bb-field-wrap"><p>' + response + '</p></div></div></div></div></transition></div>' );
							}
							this.removeFile( file );
						}
					}
				);

				bp.Nouveau.Activity.postForm.dropzone.on(
					'removedfile',
					function ( file ) {
						if ( self.document.length ) {
							for ( var i in self.document ) {
								if ( file.id === self.document[ i ].id ) {
									if ( ! _.isUndefined( self.document[ i ].saved ) && ! self.document[ i ].saved ) {
										bp.Nouveau.Media.removeAttachment( file.id );
									}
									self.document.splice( i, 1 );
									self.model.set( 'document', self.document );
								}
							}
						}

						if ( ! _.isNull( bp.Nouveau.Activity.postForm.dropzone.files ) && bp.Nouveau.Activity.postForm.dropzone.files.length === 0 ) {
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
								tool_box.find( '#activity-document-button' ).parents( '.post-elements-buttons-item' ).removeClass( 'disable active no-click' );
							}
						}
					}
				);

				self.$el.find( '#activity-post-document-uploader' ).addClass( 'open' ).removeClass( 'closed' );
				$( '#whats-new-attachments' ).removeClass( 'empty' );
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

				document.addEventListener( 'activity_video_toggle', this.toggle_video_uploader.bind( this ) );
				document.addEventListener( 'activity_video_close', this.destroyVideo.bind( this ) );
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

				document.removeEventListener( 'activity_video_toggle', this.toggle_video_uploader.bind( this ) );
				document.removeEventListener( 'activity_video_close', this.destroyVideo.bind( this ) );

				$( '#whats-new-attachments' ).addClass( 'empty' );
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
					previewTemplate : document.getElementsByClassName( 'activity-post-video-template' )[0].innerHTML
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
					function( element, file ) {

						$( element.previewElement ).find( '.dz-progress-count' ).text( element.upload.progress.toFixed( 0 ) + '% ' + BP_Nouveau.video.i18n_strings.video_uploaded_text );

						var circle        = $( element.previewElement ).find( '.dz-progress-ring circle' )[0];
						var radius        = circle.r.baseVal.value;
						var circumference = radius * 2 * Math.PI;

						circle.style.strokeDasharray  = circumference + ' ' + circumference;
						circle.style.strokeDashoffset = circumference;
						var offset                    = circumference - element.upload.progress.toFixed( 0 ) / 100 * circumference;
						circle.style.strokeDashoffset = offset;

						if ( element.upload.progress === 100 ) {
							$( file.previewElement ).closest( '.dz-preview' ).addClass( 'dz-complete' );
						}
					}
				);

				bp.Nouveau.Activity.postForm.dropzone.on(
					'success',
					function ( file, response ) {
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
							response.data.js_preview = $( file.previewElement ).find( '.dz-video-thumbnail img' ).attr( 'src' );
							response.data.menu_order = $( file.previewElement ).closest( '.dropzone' ).find( file.previewElement ).index() - 1;
							self.video.push( response.data );
							self.model.set( 'video', self.video );
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
							}
						} else {
							if ( ! jQuery( '.video-error-popup' ).length) {
								$( 'body' ).append( '<div id="bp-media-create-folder" style="display: block;" class="open-popup video-error-popup"><transition name="modal"><div class="modal-mask bb-white bbm-model-wrap"><div class="modal-wrapper"><div id="boss-media-create-album-popup" class="modal-container has-folderlocationUI"><header class="bb-model-header"><h4>' + BP_Nouveau.video.invalid_video_type + '</h4><a class="bb-model-close-button errorPopup" href="#"><span class="dashicons dashicons-no-alt"></span></a></header><div class="bb-field-wrap"><p>' + response + '</p></div></div></div></div></transition></div>' );
							}
							this.removeFile( file );
						}
					}
				);

				bp.Nouveau.Activity.postForm.dropzone.on(
					'removedfile',
					function ( file ) {
						if ( self.video.length ) {
							for ( var i in self.video ) {
								if ( file.id === self.video[ i ].id ) {
									if ( ! _.isUndefined( self.video[ i ].saved ) && ! self.video[ i ].saved ) {
										bp.Nouveau.Media.removeAttachment( file.id );
									}
									self.video.splice( i, 1 );
									self.model.set( 'video', self.video );
								}
							}
						}

						if ( ! _.isNull( bp.Nouveau.Activity.postForm.dropzone.files ) && bp.Nouveau.Activity.postForm.dropzone.files.length === 0 ) {
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
						}
					}
				);

				self.$el.find( '#activity-post-video-uploader' ).addClass( 'open' ).removeClass( 'closed' );
				$( '#whats-new-attachments' ).removeClass( 'empty' );
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
				'click #activity-link-preview-close-image': 'close',
				'click #activity-close-link-suggestion': 'destroy'
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
						link_image_index: 0
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
						link_embed: false
					}
				);
				document.removeEventListener( 'activity_link_preview_open', this.open.bind( this ) );
				document.removeEventListener( 'activity_link_preview_close', this.destroy.bind( this ) );

				$( '#whats-new-attachments' ).addClass( 'empty' );
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
				document.addEventListener( 'activity_gif_close', this.destroy.bind( this ) );
			},

			render: function () {
				this.$el.html( this.template( this.model.toJSON() ) );

				var gifData = this.model.get( 'gif_data' );
				if ( ! _.isEmpty( gifData ) ) {
					this.el.style.backgroundImage = 'url(' + gifData.images.fixed_width.url + ')';
					this.el.style.backgroundSize  = 'contain';
					this.el.style.height          = gifData.images.original.height + 'px';
					this.el.style.width           = gifData.images.original.width + 'px';
					$( '#whats-new-attachments' ).removeClass( 'empty' );
				}

				return this;
			},

			destroy: function () {
				this.model.set( 'gif_data', {} );
				this.el.style.backgroundImage = '';
				this.el.style.backgroundSize  = '';
				this.el.style.height          = '0px';
				this.el.style.width           = '0px';
				document.removeEventListener( 'activity_gif_close', this.destroy.bind( this ) );
				$( '#whats-new-attachments' ).addClass( 'empty' );
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
				'keyup .search-query-input': 'search',
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
				var self = this;

				if ( this.Timeout != null ) {
					clearTimeout( this.Timeout );
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

				var request = self.giphy.search(
					{
						q: q,
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
				'keyup': 'handleKeyUp'
			},
			attributes: {
				name: 'whats-new',
				cols: '50',
				rows: '4',
				placeholder: BP_Nouveau.activity.strings.whatsnewPlaceholder,
				'aria-label': BP_Nouveau.activity.strings.whatsnewLabel,
				contenteditable: true,
				'data-suggestions-group-id': ! _.isUndefined( BP_Nouveau.activity.params.object ) && 'group' === BP_Nouveau.activity.params.object ? BP_Nouveau.activity.params.item_id : false,
			},
			loadURLAjax: null,
			loadedURLs: [],

			initialize: function () {
				this.on( 'ready', this.adjustContent, this );
				this.on( 'ready', this.activateTinyMce, this );
				this.options.activity.on( 'change:content', this.resetContent, this );
				this.linkTimeout = null;

				if ( _.isUndefined( BP_Nouveau.activity.params.link_preview ) ) {
					this.$el.off( 'keyup' );
				}
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

			handlePaste: function ( event ) {
				// Get user's pasted data.
				var clipboardData = event.clipboardData || window.clipboardData || event.originalEvent.clipboardData,
					data          = clipboardData.getData( 'text/plain' );

				// Insert the filtered content.
				document.execCommand( 'insertHTML', false, data );

				// trigger keyup event of this view to handle changes.
				this.$el.trigger( 'keyup' );

				// Prevent the standard paste behavior.
				event.preventDefault();
			},

			handleKeyUp: function () {
				var self = this;
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
			},

			scrapURL: function ( urlText ) {
				var urlString = '';

				if ( urlText === null ) {
					return;
				}

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
				} else {
					$( '#activity-close-link-suggestion' ).click();
				}
			},

			getURL: function ( prefix, urlText ) {
				var urlString   = '';
				var startIndex  = urlText.indexOf( prefix );
				var responseUrl = '';

				if ( ! _.isUndefined( $( $.parseHTML( urlText ) ).attr( 'href' ) ) ) {
					urlString = $( urlText ).attr( 'href' );
				} else {
					for ( var i = startIndex; i < urlText.length; i++ ) {
						if ( urlText[ i ] === ' ' || urlText[ i ] === '\n' ) {
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
					self.options.activity.set(
						{
							link_success: true,
							link_title: response.title,
							link_description: response.description,
							link_images: response.images,
							link_image_index: 0,
							link_embed: ! _.isUndefined( response.wp_embed ) && response.wp_embed
						}
					);

					$( '#whats-new-attachments' ).removeClass( 'empty' );

					if ( $( '#whats-new-attachments' ).hasClass( 'activity-video-preview' ) ) {
						$( '#whats-new-attachments' ).removeClass( 'activity-video-preview' );
					}

					if ( $( '#whats-new-attachments' ).hasClass( 'activity-link-preview' ) ) {
						$( '#whats-new-attachments' ).removeClass( 'activity-link-preview' );
					}

					if ( $( '.activity-media-container' ).length ) {
						if ( response.description.indexOf( 'iframe' ) > -1 || ( ! _.isUndefined( response.wp_embed ) && response.wp_embed ) ) {
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
							var whatsnewcontent = $this.closest( '#whats-new-content' )[ 0 ];

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
											cleanPastedHTML: false,
											cleanReplacements: [
												[ new RegExp( /<div/gi ), '<p' ],
												[ new RegExp( /<\/div/gi ), '</p' ],
												[ new RegExp( /<h[1-6]/gi ), '<b' ],
												[ new RegExp( /<\/h[1-6]/gi ), '</b' ],
											],
											cleanAttrs: [ 'class', 'style', 'dir', 'id' ],
											cleanTags: [ 'meta', 'div', 'main', 'section', 'article', 'aside', 'button', 'svg', 'canvas', 'figure', 'input', 'textarea', 'select', 'label', 'form', 'table', 'thead', 'tfooter', 'colgroup', 'col', 'tr', 'td', 'th', 'dl', 'dd', 'center', 'caption', 'nav' ],
											unwrapTags: [ 'ul', 'ol', 'li' ]
										},
										imageDragging: false
									}
								);
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

			events: {
				'change #bp-activity-privacy': 'change',
			},

			initialize: function () {
				this.model.set( 'privacy', this.$el.find( '#bp-activity-privacy' ).val() );
			},

			change: function () {
				this.model.set( { 'selected': this.el.value } );
			}
		}
	);

	bp.Views.Item = bp.View.extend(
		{
			tagName: 'div',
			className: 'bp-activity-object',
			template: bp.template( 'activity-target-item' ),

			attributes: {
				role: 'checkbox'
			},

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

				if ( true === this.model.get( 'selected' ) ) {
					this.model.clear();
				} else {
					this.model.set( 'selected', true );
					var model_attributes = this.model.attributes;

					// check media is enable in groups or not.
					if ( typeof model_attributes.group_media !== 'undefined' && model_attributes.group_media === false ) {
						$( '#whats-new-toolbar .post-media.media-support' ).removeClass( 'active' ).hide();
						var mediaCloseEvent = new Event( 'activity_media_close' );
						document.dispatchEvent( mediaCloseEvent );
					} else {
						$( '#whats-new-toolbar .post-media.media-support' ).show();
					}

					// check document is enable in groups or not.
					if ( typeof model_attributes.group_document !== 'undefined' && model_attributes.group_document === false ) {
						$( '#whats-new-toolbar .post-media.document-support' ).removeClass( 'active' ).hide();
						var documentCloseEvent = new Event( 'activity_document_close' );
						document.dispatchEvent( documentCloseEvent );
					} else {
						$( '#whats-new-toolbar .post-media.document-support' ).show();
					}

					// check video is enable in groups or not.
					if ( typeof model_attributes.group_video !== 'undefined' && model_attributes.group_video === false ) {
						$( '#whats-new-toolbar .post-video.video-support' ).removeClass( 'active' ).hide();
						var videoCloseEvent = new Event( 'activity_video_close' );
						document.dispatchEvent( videoCloseEvent );
					} else {
						$( '#whats-new-toolbar .post-video.video-support' ).show();
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
				this.$el.append( '<div id="bp-activity-group-ac-items"></div>' );

				this.on( 'ready', this.setFocus, this );
				this.collection.on( 'add', this.addItemView, this );
				this.collection.on( 'reset', this.cleanView, this );
			},

			setFocus: function () {
				this.$el.find( '#activity-autocomplete' ).focus();
			},

			addItemView: function ( item ) {
				var group_ac_list_item = new bp.Views.Item( { model: item } );
				this.$el.find( '#bp-activity-group-ac-items' ).append( group_ac_list_item.render().$el );
			},

			autoComplete: function () {
				var search = $( '#activity-autocomplete' ).val();

				if ( 2 > search.length ) {
					return;
				}

				// Reset the collection before starting a new search.
				this.collection.reset();

				if ( this.ac_req ) {
					this.ac_req.abort();
				}

				this.$el.find( '#bp-activity-group-ac-items' ).html( '<i class="dashicons dashicons-update animate-spin"></i>' );

				this.ac_req = this.collection.fetch(
					{
						data: {
							type: this.options.type,
							search: search,
							nonce: BP_Nouveau.nonces.activity
						},
						success: _.bind( this.itemFetched, this ),
						error: _.bind( this.itemFetched, this )
					}
				);
			},

			itemFetched: function ( items ) {
				if ( ! items.length ) {
					this.cleanView();
				}
				this.$el.find( '#bp-activity-group-ac-items' ).find( 'i.dashicons' ).remove();
			},

			cleanView: function () {
				this.$el.find( '#bp-activity-group-ac-items' ).html( '' );
				_.each(
					this.views._views[ '' ],
					function ( view ) {
						view.remove();
					}
				);
			}
		}
	);

	bp.Views.FormAvatar = bp.View.extend(
		{
			tagName: 'div',
			id: 'whats-new-avatar',
			template: bp.template( 'activity-post-form-avatar' ),

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

				// Set the view to the selected object.
				this.views.set( '#whats-new-post-in-box-items', new bp.Views.Item( { model: model } ) );
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
							$( '#whats-new-toolbar .post-media.media-support' ).removeClass( 'active' ).hide();
							var mediaCloseEvent = new Event( 'activity_media_close' );
							document.dispatchEvent( mediaCloseEvent );
						} else {
							$( '#whats-new-toolbar .post-media.media-support' ).show();
						}

						// check document is enable in groups or not.
						if ( BP_Nouveau.media.group_document === false ) {
							$( '#whats-new-toolbar .post-media.document-support' ).removeClass( 'active' ).hide();
							var documentCloseEvent = new Event( 'activity_document_close' );
							document.dispatchEvent( documentCloseEvent );
						} else {
							$( '#whats-new-toolbar .post-media.document-support' ).show();
						}

						// check video is enable in groups or not.
						if ( BP_Nouveau.video.group_video === false ) {
							$( '#whats-new-toolbar .post-video.video-support' ).removeClass( 'active' ).hide();
							var videoCloseEvent = new Event( 'activity_video_close' );
							document.dispatchEvent( videoCloseEvent );
						} else {
							$( '#whats-new-toolbar .post-video.video-support' ).show();
						}

						// check gif is enable in groups or not.
						if ( BP_Nouveau.media.gif.groups === false ) {
							$( '#whats-new-toolbar .post-gif' ).removeClass( 'active' ).hide();
							var gifCloseEvent = new Event( 'activity_gif_close' );
							document.dispatchEvent( gifCloseEvent );
						} else {
							$( '#whats-new-toolbar .post-gif' ).show();
						}

						// check emoji is enable in groups or not.
						if ( BP_Nouveau.media.emoji.groups === false ) {
							$( '#whats-new-textarea' ).find( 'img.emojioneemoji' ).remove();
							$( '#whats-new-toolbar .post-emoji' ).hide();
						} else {
							$( '#whats-new-toolbar .post-emoji' ).show();
						}
					} else {

						// check media is enable in profile or not.
						if ( BP_Nouveau.media.profile_media === false ) {
							$( '#whats-new-toolbar .post-media.media-support' ).removeClass( 'active' ).hide();
							var event = new Event( 'activity_media_close' );
							document.dispatchEvent( event );
						} else {
							$( '#whats-new-toolbar .post-media.media-support' ).show();
						}

						// check document is enable in profile or not.
						if ( BP_Nouveau.media.profile_document === false ) {
							$( '#whats-new-toolbar .post-media.document-support' ).removeClass( 'active' ).hide();
							var documentEvent = new Event( 'activity_document_close' );
							document.dispatchEvent( documentEvent );
						} else {
							$( '#whats-new-toolbar .post-media.document-support' ).show();
						}

						// check video is enable in profile or not.
						if ( BP_Nouveau.video.profile_video === false ) {
							$( '#whats-new-toolbar .post-video.video-support' ).removeClass( 'active' ).hide();
							var videoEvent = new Event( 'activity_video_close' );
							document.dispatchEvent( videoEvent );
						} else {
							$( '#whats-new-toolbar .post-video.video-support' ).show();
						}

						// check gif is enable in profile or not.
						if ( BP_Nouveau.media.gif.profile === false ) {
							$( '#whats-new-toolbar .post-gif' ).removeClass( 'active' ).hide();
							var gifCloseEvent2 = new Event( 'activity_gif_close' );
							document.dispatchEvent( gifCloseEvent2 );
						} else {
							$( '#whats-new-toolbar .post-gif' ).show();
						}

						// check emoji is enable in profile or not.
						if ( BP_Nouveau.media.emoji.profile === false ) {
							$( '#whats-new-toolbar .post-emoji' ).hide();
							$( '#whats-new-textarea' ).find( 'img.emojioneemoji' ).remove();
						} else {
							$( '#whats-new-toolbar .post-emoji' ).show();
						}
					}
					$( '.medium-editor-toolbar' ).removeClass( 'active medium-editor-toolbar-active' );
				}
			}
		}
	);

	bp.Views.ActivityToolbar = bp.View.extend(
		{
			tagName: 'div',
			id: 'whats-new-toolbar',
			template: bp.template( 'whats-new-toolbar' ),
			events: {
				'click #activity-link-preview-button': 'toggleURLInput',
				'click #activity-gif-button': 'toggleGifSelector',
				'click #activity-media-button': 'toggleMediaSelector',
				'click #activity-document-button': 'toggleDocumentSelector',
				'click #activity-video-button': 'toggleVideoSelector',
				'click .post-elements-buttons-item:not( .post-gif ):not( .post-media ):not( .post-video )': 'activeButton',
				'click .post-elements-buttons-item.post-gif': 'activeMediaButton',
				'click .post-elements-buttons-item.post-media': 'activeMediaButton',
				'click .post-elements-buttons-item.post-video': 'activeVideoButton',
				'click .show-toolbar': 'toggleToolbarSelector'
			},
			gifMediaSearchDropdownView: false,

			initialize: function () {
				document.addEventListener( 'keydown', _.bind( this.closePickersOnEsc, this ) );
				$( document ).on( 'click', _.bind( this.closePickersOnClick, this ) );
			},

			render: function () {
				this.$el.html( this.template( this.model.toJSON() ) );
				this.$self          = this.$el.find( '#activity-gif-button' );
				this.$gifPickerEl   = this.$el.find( '.gif-media-search-dropdown' );
				this.$emojiPickerEl = $( '#whats-new' );
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
				var event = new Event( 'activity_gif_close' );
				document.dispatchEvent( event );
			},

			toggleMediaSelector: function ( e ) {
				e.preventDefault();
				this.closeGifSelector();
				this.closeDocumentSelector();
				this.closeVideoSelector();

				var event = new Event( 'activity_media_toggle' );
				document.dispatchEvent( event );
			},

			toggleDocumentSelector: function ( e ) {
				e.preventDefault();
				this.closeGifSelector();
				this.closeMediaSelector();
				this.closeVideoSelector();

				var event = new Event( 'activity_document_toggle' );
				document.dispatchEvent( event );
			},

			toggleVideoSelector: function ( e ) {
				e.preventDefault();

				this.closeMediaSelector();
				this.closeDocumentSelector();
				this.closeGifSelector();

				var event = new Event( 'activity_video_toggle' );
				document.dispatchEvent( event );
			},

			closeMediaSelector: function () {
				var event = new Event( 'activity_media_close' );
				document.dispatchEvent( event );
			},

			closeDocumentSelector: function () {
				var event = new Event( 'activity_document_close' );
				document.dispatchEvent( event );
			},

			closeVideoSelector: function () {
				var event = new Event( 'activity_video_close' );
				document.dispatchEvent( event );
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

			toggleToolbarSelector: function ( e ) {
				e.preventDefault();
				var medium_editor = $( e.currentTarget ).closest( '#whats-new-form' ).find( '.medium-editor-toolbar' );
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
				}
				$( window.activity_editor.elements[0] ).focus();
				medium_editor.toggleClass( 'medium-editor-toolbar-active active' );
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
				if ( ! _.isUndefined( window.Dropzone ) ) {
					this.activityMedia = new bp.Views.ActivityMedia( { model: this.model } );
					this.views.add( this.activityMedia );

					this.activityDocument = new bp.Views.ActivityDocument( { model: this.model } );
					this.views.add( this.activityDocument );

					this.activityVideo = new bp.Views.ActivityVideo( { model: this.model } );
					this.views.add( this.activityVideo );
				}

				if ( ! _.isUndefined( BP_Nouveau.activity.params.link_preview ) ) {
					this.activityLinkPreview = new bp.Views.ActivityLinkPreview( { model: this.model } );
					this.views.add( this.activityLinkPreview );
				}

				this.activityAttachedGifPreview = new bp.Views.ActivityAttachedGifPreview( { model: this.model } );
				this.views.add( this.activityAttachedGifPreview );
			},
			onClose: function () {
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

				this.views.set( [ this.submit, this.reset ] );

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
				// Select box for the object.
				if ( ! _.isUndefined( BP_Nouveau.activity.params.objects ) && 1 < _.keys( BP_Nouveau.activity.params.objects ).length && ( bp.Nouveau.Activity.postForm.editActivityData === false || _.isUndefined( bp.Nouveau.Activity.postForm.editActivityData ) ) ) {
					this.views.add( new bp.Views.FormTarget( { model: this.model } ) );

					// when editing activity, need to display which object is being edited.
				} else if ( bp.Nouveau.Activity.postForm.editActivityData !== false && ! _.isUndefined( bp.Nouveau.Activity.postForm.editActivityData ) ) {
					this.views.add( new bp.Views.EditActivityPostIn( { model: this.model } ) );
				}

				// activity privacy dropdown for profile.
				if ( ( ! _.isUndefined( BP_Nouveau.activity.params.objects ) && 1 < _.keys( BP_Nouveau.activity.params.objects ).length ) || ( ! _.isUndefined( BP_Nouveau.activity.params.object ) && 'user' === BP_Nouveau.activity.params.object ) ) {
					var privacy = new bp.Views.ActivityPrivacy( { model: this.model } );
					this.views.add( privacy );
				}

				$( '#whats-new-form' ).addClass( 'focus-in' ); // add some class to form so that DOM knows about focus.

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
				'reset': 'resetForm',
				'submit': 'postUpdate',
				'keydown': 'postUpdate'
			},

			initialize: function () {
				this.model = new bp.Models.Activity(
					_.pick(
						BP_Nouveau.activity.params,
						[ 'user_id', 'item_id', 'object' ]
					)
				);

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
						new bp.Views.FormAvatar(),
						new bp.Views.FormContent( { activity: this.model, model: this.model } )
					]
				);

				this.model.on( 'change:errors', this.displayFeedback, this );
			},

			displayFull: function ( event ) {

				// Remove feedback.
				this.cleanFeedback();

				if ( 2 !== this.views._views[ '' ].length ) {
					return;
				}

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
							container: '#whats-new-toolbar > .post-emoji',
							autocomplete: false,
							pickerPosition: 'bottom',
							hidePickerOnBlur: true,
							useInternalCDN: false,
							events: {
								emojibtn_click: function () {
									$( '#whats-new' )[ 0 ].emojioneArea.hidePicker();
								},
							}
						}
					);
				}

				this.updateMultiMediaOptions();
			},

			resetForm: function () {
				_.each(
					this.views._views[ '' ],
					function ( view, index ) {
						if ( index > 1 ) {
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

				$( '#whats-new-form' ).removeClass( 'focus-in' ); // remove class when reset.

				$( '#whats-new-content' ).find( '#bp-activity-id' ).val( '' ); // reset activity id if in edit mode.
				bp.Nouveau.Activity.postForm.postForm.$el.removeClass( 'bp-activity-edit' ); // remove edit class if in edit mode.
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

				$( '.medium-editor-toolbar' ).removeClass( 'active medium-editor-toolbar-active' );
				$( '#show-toolbar-button' ).removeClass( 'active' );
				$( 'medium-editor-action' ).removeClass( 'medium-editor-button-active' );
				$( '.medium-editor-toolbar-actions' ).show();
				$( '.medium-editor-toolbar-form' ).removeClass( 'medium-editor-toolbar-form-active' );
				$( '#show-toolbar-button' ).parent( '.show-toolbar' ).attr( 'data-bp-tooltip', $( '#show-toolbar-button' ).parent( '.show-toolbar' ).attr( 'data-bp-tooltip-show' ) );
			},

			cleanFeedback: function () {
				_.each(
					this.views._views[ '' ],
					function ( view ) {
						if ( 'message' === view.$el.prop( 'id' ) ) {
							view.remove();
						}
					}
				);
			},

			displayFeedback: function ( model ) {
				if ( _.isUndefined( this.model.get( 'errors' ) ) ) {
					this.cleanFeedback();
				} else {
					this.views.add( new bp.Views.activityFeedback( model.get( 'errors' ) ) );
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
				if ( $( $.parseHTML( content ) ).text().trim() === '' && ( ( ! _.isUndefined( self.model.get( 'video' ) ) && ! self.model.get( 'video' ).length ) && ( ! _.isUndefined( self.model.get( 'document' ) ) && ! self.model.get( 'document' ).length ) && ( ! _.isUndefined( self.model.get( 'media' ) ) && ! self.model.get( 'media' ).length ) && ( ! _.isUndefined( self.model.get( 'gif_data' ) ) && ! Object.keys( self.model.get( 'gif_data' ) ).length ) ) ) {
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
						index  = self.model.get( 'link_image_index' );
					if ( images && images.length ) {
						data = _.extend(
							data,
							{
								'link_image': images[ index ]
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
						 * In the Activity directory, we also need to check the active scope.
						 * eg: An update posted in a private group should only show when the
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
						self.model.set( 'errors', { type: 'error', value: response.message } );
					}
				);
			},

			updateMultiMediaOptions: function () {

				if ( ! _.isUndefined( BP_Nouveau.media ) ) {

					if ( 'user' !== this.model.get( 'object' ) ) {

						// check media is enable in groups or not.
						if ( BP_Nouveau.media.group_media === false ) {
							$( '#whats-new-toolbar .post-media.media-support' ).removeClass( 'active' ).hide();
							var mediaCloseEvent = new Event( 'activity_media_close' );
							document.dispatchEvent( mediaCloseEvent );
						} else {
							$( '#whats-new-toolbar .post-media.media-support' ).show();
						}

						// check document is enable in groups or not.
						if ( BP_Nouveau.media.group_document === false ) {
							$( '#whats-new-toolbar .post-media.document-support' ).removeClass( 'active' ).hide();
							var documentCloseEvent = new Event( 'activity_document_close' );
							document.dispatchEvent( documentCloseEvent );
						} else {
							$( '#whats-new-toolbar .post-media.document-support' ).show();
						}

						// check video is enable in groups or not.
						if ( BP_Nouveau.video.group_video === false ) {
							$( '#whats-new-toolbar .post-video.video-support' ).removeClass( 'active' ).hide();
							var videoCloseEvent = new Event( 'activity_video_close' );
							document.dispatchEvent( videoCloseEvent );
						} else {
							$( '#whats-new-toolbar .post-video.video-support' ).show();
						}

						// check gif is enable in groups or not.
						if ( BP_Nouveau.media.gif.groups === false ) {
							$( '#whats-new-toolbar .post-gif' ).removeClass( 'active' ).hide();
							var gifCloseEvent = new Event( 'activity_gif_close' );
							document.dispatchEvent( gifCloseEvent );
						} else {
							$( '#whats-new-toolbar .post-gif' ).show();
						}

						// check emoji is enable in groups or not.
						if ( BP_Nouveau.media.emoji.groups === false ) {
							$( '#whats-new-textarea' ).find( 'img.emojioneemoji' ).remove();
							$( '#whats-new-toolbar .post-emoji' ).hide();
						} else {
							$( '#whats-new-toolbar .post-emoji' ).show();
						}
					} else {

						// check media is enable in profile or not.
						if ( BP_Nouveau.media.profile_media === false ) {
							$( '#whats-new-toolbar .post-media.media-support' ).removeClass( 'active' ).hide();
							var event = new Event( 'activity_media_close' );
							document.dispatchEvent( event );
						} else {
							$( '#whats-new-toolbar .post-media.media-support' ).show();
						}

						// check media is enable in profile or not.
						if ( BP_Nouveau.media.profile_document === false ) {
							$( '#whats-new-toolbar .post-media.document-support' ).removeClass( 'active' ).hide();
							var documentEvent = new Event( 'activity_document_close' );
							document.dispatchEvent( documentEvent );
						} else {
							$( '#whats-new-toolbar .post-media.document-support' ).show();
						}

						// check video is enable in groups or not.
						if ( BP_Nouveau.video.profile_video === false ) {
							$( '#whats-new-toolbar .post-video.video-support' ).removeClass( 'active' ).hide();
							var videosCloseEvent = new Event( 'activity_video_close' );
							document.dispatchEvent( videosCloseEvent );
						} else {
							$( '#whats-new-toolbar .post-video.video-support' ).show();
						}

						// check gif is enable in profile or not.
						if ( BP_Nouveau.media.gif.profile === false ) {
							$( '#whats-new-toolbar .post-gif' ).removeClass( 'active' ).hide();
							var gifCloseEvent2 = new Event( 'activity_gif_close' );
							document.dispatchEvent( gifCloseEvent2 );
						} else {
							$( '#whats-new-toolbar .post-gif' ).show();
						}

						// check emoji is enable in profile or not.
						if ( BP_Nouveau.media.emoji.profile === false ) {
							$( '#whats-new-toolbar .post-emoji' ).hide();
							$( '#whats-new-textarea' ).find( 'img.emojioneemoji' ).remove();
						} else {
							$( '#whats-new-toolbar .post-emoji' ).show();
						}
					}
					$( '.medium-editor-toolbar' ).removeClass( 'active medium-editor-toolbar-active' );
				}
			}
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
						new bp.Views.FormAvatar(),
						new bp.Views.FormPlaceholderContent( { activity: this.model, model: this.model } )
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

	bp.Nouveau.Activity.postForm.start();

} )( bp, jQuery );
