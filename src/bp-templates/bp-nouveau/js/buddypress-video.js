/* jshint browser: true */
/* global bp, BP_Nouveau, Dropzone */
/* @version 1.0.0 */
window.bp = window.bp || {};

( function (exports, $) {

	// Bail if not set.
	if (typeof BP_Nouveau === 'undefined') {
		return;
	}

	bp.Nouveau = bp.Nouveau || {};

	/**
	 * [Video description]
	 *
	 * @type {Object}
	 */
	bp.Nouveau.Video = {

		/**
		 * [start description]
		 *
		 * @return {[type]} [description]
		 */
		start: function () {

			this.setupGlobals();

			// Listen to events ("Add hooks!").
			this.addListeners();

		},

		/**
		 * [setupGlobals description]
		 *
		 * @return {[type]} [description]
		 */
		setupGlobals: function () {

			var bodySelector = $( 'body' );

			this.video_dropzone_obj = [];
			this.dropzone_video     = [];
			this.video_album_id     = typeof BP_Nouveau.video.album_id !== 'undefined' ? BP_Nouveau.video.album_id : false;
			this.video_group_id     = typeof BP_Nouveau.video.group_id !== 'undefined' ? BP_Nouveau.video.group_id : false;
			this.current_tab        = bodySelector.hasClass( 'single-topic' ) || bodySelector.hasClass( 'single-forum' ) ? false : 'bp-video-dropzone-content';

			// set up dropzones auto discover to false so it does not automatically set dropzones.
			if (typeof window.Dropzone !== 'undefined') {
				window.Dropzone.autoDiscover = false;
			}

			this.videoOptions = {
				url                  : BP_Nouveau.ajaxurl,
				timeout              : 3 * 60 * 60 * 1000,
				dictFileTooBig       : BP_Nouveau.video.dictFileTooBig,
				acceptedFiles        : BP_Nouveau.video.video_type,
				createImageThumbnails: false,
				dictDefaultMessage   : BP_Nouveau.video.dropzone_video_message,
				autoProcessQueue     : true,
				addRemoveLinks       : true,
				uploadMultiple       : false,
				maxFiles             : typeof BP_Nouveau.video.maxFiles !== 'undefined' ? BP_Nouveau.video.maxFiles : 10,
				maxFilesize          : typeof BP_Nouveau.video.max_upload_size !== 'undefined' ? BP_Nouveau.video.max_upload_size : 2,
				dictInvalidFileType  : BP_Nouveau.video.dictInvalidFileType,
			};

			// if defined, add custom dropzone options.
			if (typeof BP_Nouveau.video.dropzone_options !== 'undefined') {
				Object.assign( this.options, BP_Nouveau.video.dropzone_options );
			}

		},

		/**
		 * [addListeners description]
		 */
		addListeners: function () {

			$( document ).on( 'click', '#bp-add-video', this.openUploader.bind( this ) );
			$( document ).on( 'click', '#bp-video-submit', this.submitVideo.bind( this ) );
		},

		submitVideo: function (event) {
			var self = this, target = $( event.currentTarget ), data, privacy = $( '#bb-video-privacy' );
			event.preventDefault();

			if (target.hasClass( 'saving' )) {
				return false;
			}

			target.addClass( 'saving' );

			if (self.current_tab === 'bp-video-dropzone-content') {

				var post_content = $( '#bp-video-post-content' ).val();
				data             = {
					'action'	: 'video_save',
					'_wpnonce'	: BP_Nouveau.nonces.video,
					'videos'	: self.dropzone_video,
					'content'	: post_content,
					'album_id'	: self.video_album_id,
					'group_id'	: self.video_group_id,
					'privacy'	: privacy.val()
				};

				$( '#bp-video-dropzone-content .bp-feedback' ).remove();

				$.ajax(
					{
						type	: 'POST',
						url		: BP_Nouveau.ajaxurl,
						data	: data,
						success: function (response) {
							if (response.success) {

								// It's the very first media, let's make sure the container can welcome it!
								if ( ! $( '#video-stream ul.video-list' ).length) {
									$( '#video-stream' ).html( $( '<ul></ul>' ).addClass( 'video-list item-list bp-list bb-video-list grid' ) );
									$( '.bb-videos-actions' ).show();
								}

								// Prepend the activity.
								bp.Nouveau.inject( '#video-stream ul.video-list', response.data.video, 'prepend' );

								for ( var i = 0; i < self.dropzone_video.length; i++ ) {
									self.dropzone_video[i].saved = true;
								}

								self.closeUploader( event );

								// replace dummy image with original image by faking scroll event to call bp.Nouveau.lazyLoad.
								jQuery( window ).scroll();

							} else {
								$( '#bp-video-dropzone-content' ).prepend( response.data.feedback );
							}

							target.removeClass( 'saving' );
						}
					}
				);

			} else if (self.current_tab === 'bp-existing-video-content') {
				var selected = [];
				$( '.bp-existing-video-wrap .bb-video-check-wrap [name="bb-video-select"]:checked' ).each(
					function () {
						selected.push( $( this ).val() );
					}
				);
				data = {
					'action'	: 'video_move_to_album',
					'_wpnonce'	: BP_Nouveau.nonces.video,
					'medias'	: selected,
					'album_id'	: self.video_album_id,
					'group_id'	: self.video_group_id
				};

				$( '#bp-existing-video-content .bp-feedback' ).remove();

				$.ajax(
					{
						type: 'POST',
						url: BP_Nouveau.ajaxurl,
						data: data,
						success: function (response) {
							if (response.success) {

								// It's the very first media, let's make sure the container can welcome it!
								if ( ! $( '#video-stream ul.media-list' ).length) {
									$( '#video-stream' ).html( $( '<ul></ul>' ).addClass( 'video-list item-list bp-list bb-video-list grid' ) );
									$( '.bb-video-actions' ).show();
								}

								// Prepend the activity.
								bp.Nouveau.inject( '#video-stream ul.video-list', response.data.video, 'prepend' );

								// remove selected media from existing media list.
								$( '.bp-existing-video-wrap .bb-video-check-wrap [name="bb-video-select"]:checked' ).each(
									function () {
										if ($( this ).closest( 'li' ).data( 'id' ) === $( this ).val()) {
											$( this ).closest( 'li' ).remove();
										}
									}
								);

								jQuery( window ).scroll();

								self.closeUploader( event );
							} else {
								$( '#bp-existing-video-content' ).prepend( response.data.feedback );
							}

							target.removeClass( 'saving' );
						}
					}
				);
			} else if ( ! self.current_tab) {
				self.closeUploader( event );
				target.removeClass( 'saving' );
			}

		},

		closeUploader: function (event) {
			event.preventDefault();
			$( '#bp-video-uploader' ).hide();
			$( '#bp-video-add-more' ).hide();
			$( '#bp-video-uploader-modal-title' ).text( BP_Nouveau.video.i18n_strings.upload );
			$( '#bp-video-uploader-modal-status-text' ).text( '' );
			this.video_dropzone_obj.destroy();
			this.dropzone_video = [];

			var currentPopup = $( event.currentTarget ).closest( '#bp-video-uploader' );

			if (currentPopup.find( '.bb-field-steps' ).length) {
				currentPopup.find( '.bb-field-steps-1' ).show().siblings( '.bb-field-steps-2' ).hide();
				currentPopup.find( '#bp-media-document-prev, #bp-media-document-submit, .bp-document-open-create-popup-folder' ).hide();
			}

			this.clearFolderLocationUI( event );

		},

		clearFolderLocationUI: function (event) {

			var closest_parent = jQuery( event.currentTarget ).closest( '.has-folderlocationUI' );
			if (closest_parent.length > 0) {

				closest_parent.find( '.location-folder-list-wrap-main .location-folder-list-wrap .location-folder-list li' ).each(
					function () {
						jQuery( this ).removeClass( 'is_active' ).find( 'span.selected:not(.disabled)' ).removeClass( 'selected' );
						jQuery( this ).find( 'ul' ).hide();
					}
				);

				closest_parent.find( '.location-folder-list-wrap-main .location-folder-list-wrap .location-folder-list li' ).show().children( 'span, i' ).show();
				closest_parent.find( '.location-folder-title' ).text( BP_Nouveau.media.target_text );
				closest_parent.find( '.location-folder-back' ).hide().closest( '.has-folderlocationUI' ).find( '.bb-folder-selected-id' ).val( '0' );
				closest_parent.find( '.ac_document_search_folder' ).val( '' );
				closest_parent.find( '.bb-model-header h4 span' ).text( '...' );
				closest_parent.find( '.ac_document_search_folder_list ul' ).html( '' ).parent().hide().siblings( '.location-folder-list-wrap' ).find( '.location-folder-list' ).show();
			}
		},

		openUploader: function (event) {
			var self = this;
			event.preventDefault();

			if (typeof window.Dropzone !== 'undefined' && $( 'div#video-uploader' ).length) {

				$( '#bp-video-uploader' ).show();

				self.video_dropzone_obj = new Dropzone( 'div#video-uploader', self.videoOptions );

				self.video_dropzone_obj.on(
					'sending',
					function (file, xhr, formData) {
						formData.append( 'action', 'video_upload' );
						formData.append( '_wpnonce', BP_Nouveau.nonces.video );
					}
				);

				self.video_dropzone_obj.on(
					'addedfile',
					function () {
						setTimeout(
							function () {
								if (self.video_dropzone_obj.getAcceptedFiles().length) {
									$( '#bp-video-uploader-modal-status-text' ).text( wp.i18n.sprintf( BP_Nouveau.video.i18n_strings.upload_status, self.dropzone_video.length, self.video_dropzone_obj.getAcceptedFiles().length ) ).show();
								}
							},
							1000
						);
					}
				);

				self.video_dropzone_obj.on(
					'error',
					function (file, response) {
						if (file.accepted) {
							if (typeof response !== 'undefined' && typeof response.data !== 'undefined' && typeof response.data.feedback !== 'undefined') {
								$( file.previewElement ).find( '.dz-error-message span' ).text( response.data.feedback );
							}
						} else {
							this.removeFile( file );
						}
					}
				);

				self.video_dropzone_obj.on(
					'queuecomplete',
					function () {
						$( '#bp-video-uploader-modal-title' ).text( BP_Nouveau.video.i18n_strings.upload );
					}
				);

				self.video_dropzone_obj.on(
					'processing',
					function () {
						$( '#bp-video-uploader-modal-title' ).text( BP_Nouveau.video.i18n_strings.uploading + '...' );
					}
				);

				self.video_dropzone_obj.on(
					'success',
					function (file, response) {
						if (response.data.id) {
							file.id                  = response.id;
							response.data.uuid       = file.upload.uuid;
							response.data.menu_order = self.dropzone_video.length;
							response.data.album_id   = self.video_album_id;
							response.data.group_id   = self.video_group_id;
							response.data.saved      = false;
							self.dropzone_video.push( response.data );
						} else {
							this.removeFile( file );
						}
						$( '#bp-video-submit' ).show();
						$( '#bp-video-uploader-modal-title' ).text( BP_Nouveau.video.i18n_strings.uploading + '...' );
						$( '#bp-video-uploader-modal-status-text' ).text( wp.i18n.sprintf( BP_Nouveau.video.i18n_strings.upload_status, self.dropzone_video.length, self.video_dropzone_obj.getAcceptedFiles().length ) ).show();
					}
				);

				self.video_dropzone_obj.on(
					'removedfile',
					function (file) {
						if (self.dropzone_video.length) {
							for (var i in self.dropzone_video) {
								if (file.upload.uuid == self.dropzone_video[ i ].uuid) {

									if (typeof self.dropzone_video[ i ].saved !== 'undefined' && ! self.dropzone_video[ i ].saved) {
										self.removeAttachment( self.dropzone_video[ i ].id );
									}

									self.dropzone_video.splice( i, 1 );
									break;
								}
							}
						}
						if ( ! self.video_dropzone_obj.getAcceptedFiles().length) {
							$( '#bp-video-uploader-modal-status-text' ).text( '' );
							$( '#bp-video-submit' ).hide();
						} else {
							$( '#bp-video-uploader-modal-status-text' ).text( wp.i18n.sprintf( BP_Nouveau.video.i18n_strings.upload_status, self.dropzone_video.length, self.video_dropzone_obj.getAcceptedFiles().length ) ).show();
						}
					}
				);
			}
		},

		toggle_video_uploader: function (){
			var self = this;

			self.open_video_uploader();

		},

		open_video_uploader: function(){
			var self = this;
			if ( self.$el.find( '#activity-post-media-uploader' ).hasClass( 'open' ) ) {
				return false;
			}

		},

	};


	//Activity Video

	bp.Views.ActivityVideo = bp.View.extend(
		{
			tagName		: 'div',
			className	: 'activity-video-container',
			template	: bp.template( 'activity-video' ),
			video	: [],
			videoDropzoneObj : null,

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
				if ( ! _.isNull( self.videoDropzoneObj ) ) {
					self.videoDropzoneObj.destroy();
					self.$el.find( '#activity-post-video-uploader' ).html( '' );
				}
				self.video = [];
				self.$el.find( '#activity-post-video-uploader' ).removeClass( 'open' ).addClass( 'closed' );

				document.removeEventListener( 'activity_video_toggle', this.toggle_video_uploader.bind( this ) );
				document.removeEventListener( 'activity_video_close', this.destroyVideo.bind( this ) );

				$( '#whats-new-attachments' ).addClass( 'empty' );
			},

			open_video_uploader: function() {
				var self = this;

				if ( self.$el.find( '#activity-post-video-uploader' ).hasClass( 'open' ) ) {
					return false;
				}
				self.destroyVideo();

				var dropzone_options = {
					url						: BP_Nouveau.ajaxurl,
					timeout					: 3 * 60 * 60 * 1000,
					dictFileTooBig          : BP_Nouveau.video.dictFileTooBig,
					acceptedFiles           : BP_Nouveau.video.video_type,
					createImageThumbnails 	: false,
					dictDefaultMessage      : BP_Nouveau.video.dropzone_video_message,
					autoProcessQueue		: true,
					addRemoveLinks			: true,
					uploadMultiple			: false,
					maxFiles                : typeof BP_Nouveau.video.maxFiles !== 'undefined' ? BP_Nouveau.video.maxFiles : 10,
					maxFilesize             : typeof BP_Nouveau.video.max_upload_size !== 'undefined' ? BP_Nouveau.video.max_upload_size : 2,
					dictInvalidFileType		: BP_Nouveau.video.dictInvalidFileType,
				};

				self.videoDropzoneObj = new window.Dropzone( '#activity-post-video-uploader', dropzone_options );


				self.videoDropzoneObj.on(
					'addedfile',
					function ( file ) {
						if ( file.document_edit_data ) {
							self.video.push( file.document_edit_data );
							self.model.set( 'document', self.video );
						}
					}
				);

				self.videoDropzoneObj.on(
					'sending',
					function(file, xhr, formData) {
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
							tool_box.find( '#activity-document-button' ).parents( '.post-elements-buttons-item' ).addClass( 'no-click' );
						}
					}
				);

				self.videoDropzoneObj.on(
					'success',
					function(file, response) {
						console.log( response);
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
							self.video.push( response.data );
							self.model.set( 'video', self.video );
						} else {
							var node, _i, _len, _ref, _results;
							var message = response.data.feedback;
							file.previewElement.classList.add( 'dz-error' );
							_ref     = file.previewElement.querySelectorAll( '[data-dz-errormessage]' );
							_results = [];
							for ( _i = 0, _len = _ref.length; _i < _len; _i++ ) {
								node                            = _ref[_i];
								_results.push( node.textContent = message );
							}
							return _results;
						}
					}
				);

				self.videoDropzoneObj.on(
					'accept',
					function( file, done ) {
						if (file.size == 0) {
							done( BP_Nouveau.media.empty_document_type );
						} else {
							done();
						}
					}
				);

				self.videoDropzoneObj.on(
					'error',
					function(file,response) {
						if ( file.accepted ) {
							if ( ! _.isUndefined( response ) && ! _.isUndefined( response.data ) && ! _.isUndefined( response.data.feedback ) ) {
								$( file.previewElement ).find( '.dz-error-message span' ).text( response.data.feedback );
							}
						} else {
							$( 'body' ).append( '<div id="bp-media-create-folder" style="display: block;" class="open-popup"><transition name="modal"><div class="modal-mask bb-white bbm-model-wrap"><div class="modal-wrapper"><div id="boss-media-create-album-popup" class="modal-container has-folderlocationUI"><header class="bb-model-header"><h4>' + BP_Nouveau.media.invalid_file_type + '</h4><a class="bb-model-close-button" id="bp-media-create-folder-close" href="#"><span class="dashicons dashicons-no-alt"></span></a></header><div class="bb-field-wrap"><p>' + response + '</p></div></div></div></div></transition></div>' );
							this.removeFile( file );
						}
					}
				);

				self.videoDropzoneObj.on(
					'removedfile',
					function(file) {
						if ( self.video.length ) {
							for ( var i in self.video ) {
								if ( file.id === self.video[i].id ) {
									if ( ! _.isUndefined( self.video[i].saved ) && ! self.video[i].saved ) {
										bp.Nouveau.Media.removeAttachment( file.id );
									}
									self.video.splice( i, 1 );
									self.model.set( 'video', self.video );
								}
							}
						}

						if ( ! _.isNull( self.videoDropzoneObj.files ) && self.videoDropzoneObj.files.length === 0 ) {
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
			}

		}
	);

	// Launch BP Nouveau Video.
	bp.Nouveau.Video.start();

	/**
	 * [Video description]
	 *
	 * @type {Object}
	 */
	bp.Nouveau.Video.Theatre = {

		/**
		 * [start description]
		 *
		 * @return {[type]} [description]
		 */
		start: function () {
			this.setupGlobals();

			// Listen to events ("Add hooks!").
			this.addListeners();

		},

		/**
		 * [setupGlobals description]
		 *
		 * @return {[type]} [description]
		 */
		setupGlobals: function () {

			this.videos              = [];
			this.current_video       = false;
			this.current_video_index = 0;
			this.is_open_video       = false;
			this.nextVideoLink       = $( '.bb-next-video' );
			this.previousVideoLink   = $( '.bb-prev-video' );
			this.activity_ajax       = false;
			this.group_id            = typeof BP_Nouveau.video.group_id !== 'undefined' ? BP_Nouveau.video.group_id : false;

		},

		/**
		 * [addListeners description]
		 */
		addListeners: function () {

			$( document ).on( 'click', '.bb-open-media-theatre', this.openTheatre.bind( this ) );

		},
		openTheatre : function (event) {
			event.preventDefault();
			var target = $( event.currentTarget ), id, self = this;

			if (target.closest( '#bp-existing-media-content' ).length) {
				return false;
			}

			self.setupGlobals();
			self.setMedias( target );

			id = target.data( 'id' );
			self.setCurrentMedia( id );
			self.showMedia();
			self.navigationCommands();

			if (typeof BP_Nouveau.activity !== 'undefined' && self.current_media && typeof self.current_media.activity_id !== 'undefined' && self.current_media.activity_id != 0 && ! self.current_media.is_forum) {
				self.getActivity();
			} else {
				self.getMediasDescription();
			}

			$( '.bb-media-model-wrapper.document' ).hide();
			$( '.bb-media-model-wrapper.media' ).show();
			self.is_open_media = true;

			// document.addEventListener( 'keyup', self.checkPressedKey.bind( self ) );
		},
	};

	// Launch BP Nouveau Video Theatre.
	bp.Nouveau.Video.Theatre.start();

} )( bp, jQuery );
