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

			this.video_dropzone_obj = [];
			this.dropzone_video     = [];
			this.video_album_id     = typeof BP_Nouveau.video.album_id !== 'undefined' ? BP_Nouveau.video.album_id : false;
			this.video_group_id     = typeof BP_Nouveau.video.group_id !== 'undefined' ? BP_Nouveau.video.group_id : false;

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
							response.data.menu_order = self.dropzone_media.length;
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

	};

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
