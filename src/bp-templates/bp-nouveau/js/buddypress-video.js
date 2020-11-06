/* jshint browser: true */
/* global bp, BP_Nouveau, Dropzone */
/* @version 1.0.0 */
window.bp = window.bp || {};

(function (exports, $) {

	// Bail if not set.
	if ( typeof BP_Nouveau === 'undefined' ) {
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
		start: function() {


			this.setupGlobals();

			// Listen to events ("Add hooks!").
			this.addListeners();

		},

		/**
		 * [setupGlobals description]
		 *
		 * @return {[type]} [description]
		 */
		setupGlobals: function() {

			this.video_dropzone_obj = [];
			this.dropzone_video     = [];
			this.video_album_id     = typeof BP_Nouveau.video.album_id !== 'undefined' ? BP_Nouveau.video.album_id : false;
			this.video_group_id     = typeof BP_Nouveau.video.group_id !== 'undefined' ? BP_Nouveau.video.group_id : false;


			// set up dropzones auto discover to false so it does not automatically set dropzones.
			if ( typeof window.Dropzone !== 'undefined' ) {
				window.Dropzone.autoDiscover = false;
			}

			this.videoOptions = {
				url						: BP_Nouveau.ajaxurl,
				timeout					: 3 * 60 * 60 * 1000,
				dictFileTooBig			: BP_Nouveau.video.dictFileTooBig,
				acceptedFiles			: BP_Nouveau.video.video_type,
				createImageThumbnails 	: false,
				dictDefaultMessage 		: BP_Nouveau.video.dropzone_video_message,
				autoProcessQueue		: true,
				addRemoveLinks			: true,
				uploadMultiple			: false,
				maxFiles				: typeof BP_Nouveau.video.maxFiles !== 'undefined' ? BP_Nouveau.video.maxFiles : 10,
				maxFilesize				: typeof BP_Nouveau.video.max_upload_size !== 'undefined' ? BP_Nouveau.video.max_upload_size : 2,
				dictInvalidFileType		: BP_Nouveau.video.dictInvalidFileType,
			};

			// if defined, add custom dropzone options.
			if ( typeof BP_Nouveau.video.dropzone_options !== 'undefined' ) {
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
							this.removeFile(file);
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
							this.removeFile(file);
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
								if (file.upload.uuid == self.dropzone_video[i].uuid) {

									if (typeof self.dropzone_video[i].saved !== 'undefined' && ! self.dropzone_video[i].saved) {
										self.removeAttachment( self.dropzone_video[i].id );
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

			this.videos				= [];
			this.current_video 			= false;
			this.current_video_index 			= 0;
			this.is_open_video 			= false;
			this.nextVideoLink 				= $('.bb-next-video');
			this.previousVideoLink 			= $('.bb-prev-video');
			this.activity_ajax 			= false;
			this.group_id 				= typeof BP_Nouveau.video.group_id !== 'undefined' ? BP_Nouveau.video.group_id : false;

		},

		/**
		 * [addListeners description]
		 */
		addListeners: function () {

			$( document ).on( 'click', '.bb-open-media-theatre', this.openTheatre.bind( this ) );
			$( document ).on( 'click', '.bb-open-document-theatre', this.openDocumentTheatre.bind( this ) );
			$( document ).on( 'click', '.document-detail-wrap-description-popup', this.openDocumentTheatre.bind( this ) );
			$( document ).on( 'click', '.bb-close-media-theatre', this.closeTheatre.bind( this ) );
			$( document ).on( 'click', '.bb-close-document-theatre', this.closeDocumentTheatre.bind( this ) );
			$( document ).on( 'click', '.bb-prev-media', this.previous.bind( this ) );
			$( document ).on( 'click', '.bb-next-media', this.next.bind( this ) );
			$( document ).on( 'click', '.bb-prev-document', this.previousDocument.bind( this ) );
			$( document ).on( 'click', '.bb-next-document', this.nextDocument.bind( this ) );
			$( document ).on( 'bp_activity_ajax_delete_request', this.activityDeleted.bind( this ) );
			$( document ).on( 'click', '#bb-media-model-container .media-privacy>li', this.mediaPrivacyChange.bind( this ) );
			$( document ).on( 'click', '#bb-media-model-container .bb-media-section span.privacy', bp.Nouveau, this.togglePrivacyDropdown.bind( this ) );
			$( document ).on( 'click', '.bp-add-media-activity-description', this.openMediaActivityDescription.bind( this ) );
			$( document ).on( 'click', '#bp-activity-description-new-reset', this.closeMediaActivityDescription.bind( this ) );
			$( document ).on( 'click', '#bp-activity-description-new-submit', this.submitMediaActivityDescription.bind( this ) );
			$( document ).click( this.togglePopupDropdown );

			document.addEventListener( 'keyup', this.checkPressedKeyDocuments.bind( this ) );
			document.addEventListener( 'keyup', this.checkPressedKey.bind( this ) );


		},

		checkPressedKey: function (e) {
			var self = this;
			e        = e || window.event;

			if ( ! self.is_open_media ) {
				return false;
			}

			var userIsEditing = ( $('#add-activity-description').length && $('#add-activity-description').is(':focus') ) || ( $('.ac-reply-content .ac-textarea > .ac-input').length && $('.ac-reply-content .ac-textarea > .ac-input').hasClass('focus-visible') );

			switch (e.keyCode) {
				case 27: // escape key.
					self.closeTheatre( e );
					break;
				case 37: // left arrow key code.
					if (typeof self.medias[self.current_index - 1] === 'undefined' || userIsEditing ) {
						return false;
					}
					self.previous( e );
					break;
				case 39: // right arrow key code.
					if (typeof self.medias[self.current_index + 1] === 'undefined' || userIsEditing ) {
						return false;
					}
					self.next( e );
					break;
			}
		},

		checkPressedKeyDocuments: function (e) {
			e = e || window.event;
			var self = this;

			if ( ! self.is_open_document ) {
				return false;
			}

			var userIsEditing = ( $('#add-activity-description').length && $('#add-activity-description').is(':focus') ) || ( $('.ac-reply-content .ac-textarea > .ac-input').length && $('.ac-reply-content .ac-textarea > .ac-input').hasClass('focus-visible') );

			switch (e.keyCode) {
				case 27: // escape key.
					self.closeDocumentTheatre( e );
					break;
				case 37: // left arrow key code.
					if (typeof self.documents[self.current_document_index - 1] === 'undefined' || userIsEditing ) {
						return false;
					}
					self.previousDocument( e );
					break;
				case 39: // right arrow key code.
					if (typeof self.documents[self.current_document_index + 1] === 'undefined' || userIsEditing ) {
						return false;
					}
					self.nextDocument( e );
					break;
			}
		},

		openTheatre: function (event) {
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

			if (typeof BP_Nouveau.activity !== 'undefined' && self.current_media && typeof self.current_media.activity_id !== 'undefined' && self.current_media.activity_id != 0 && ! self.current_media.is_forum ) {
				self.getActivity();
			} else {
				self.getMediasDescription();
			}

			$( '.bb-media-model-wrapper.document' ).hide();
			$( '.bb-media-model-wrapper.media' ).show();
			self.is_open_media = true;

			//document.addEventListener( 'keyup', self.checkPressedKey.bind( self ) );
		},

		getMediasDescription: function () {
			var self = this;

			$( '.bb-media-info-section .activity-list' ).addClass( 'loading' ).html( '<i class="bb-icon-loader animate-spin"></i>' );

			if (self.activity_ajax != false) {
				self.activity_ajax.abort();
			}

			self.activity_ajax = $.ajax(
				{
					type	: 'POST',
					url		: BP_Nouveau.ajaxurl,
					data	: {
						action		        : 'media_get_media_description',
						id			          : self.current_media.id,
						attachment_id			: self.current_media.attachment_id,
						nonce		          : BP_Nouveau.nonces.media
					},
					success: function (response) {
						if (response.success) {
							$( '.bb-media-info-section:visible .activity-list' ).removeClass( 'loading' ).html( response.data.description );
							$( '.bb-media-info-section:visible' ).show();
							$( window ).scroll();
						} else {
							$( '.bb-media-info-section.media' ).hide();
						}
					}
				}
			);
		},

		openDocumentTheatre: function (event) {
			event.preventDefault();
			var target = $( event.currentTarget ), id, self = this;

			if (target.closest( '#bp-existing-document-content' ).length) {
				return false;
			}

			if( target.closest( '.document.document-theatre' ).length ) {
				self.closeDocumentTheatre( event );
			}

			id = target.data( 'id' );
			self.setupGlobals();
			self.setDocuments( target );
			self.setCurrentDocument( id );
			self.showDocument();
			self.navigationDocumentCommands();

			if ( typeof BP_Nouveau.activity !== 'undefined' && self.current_document && typeof self.current_document.activity_id !== 'undefined' && self.current_document.activity_id != 0 && ! self.current_document.is_forum ) {
				self.getDocumentsActivity();
			} else {
				self.getDocumentsDescription();
			}

			//Stop audio if it is playing before opening theater
			if( $.inArray( self.current_document.extension, BP_Nouveau.document.mp3_preview_extension.split(',') ) !== -1 ) {
				if( $( event.currentTarget ).closest( '.bb-activity-media-elem.document-activity' ).length &&  $( event.currentTarget ).closest( '.bb-activity-media-elem.document-activity' ).find( '.document-audio-wrap' ).length ) {
					$( event.currentTarget ).closest( '.bb-activity-media-elem.document-activity' ).find( '.document-audio-wrap audio' )[0].pause();
				}
			}

			$( '.bb-media-model-wrapper.media' ).hide();
			$( '.bb-media-model-wrapper.document' ).show();
			self.is_open_document = true;
			//document.addEventListener( 'keyup', self.checkPressedKeyDocuments.bind( self ) );
		},

		resetRemoveActivityCommentsData: function() {
			var self = this, activity_comments = false, activity_meta = false, activity_state = false, activity = false, html = false, classes = false;
			if ( self.current_media.parent_activity_comments ) {
				activity = $('.bb-media-model-wrapper.media [data-bp-activity-id="' + self.current_media.activity_id + '"]');
				activity_comments = activity.find('.activity-comments');
				if (activity_comments.length) {
					html = activity_comments.html();
					classes = activity_comments.attr('class');
					activity_comments.remove();
					activity_comments = $('[data-bp-activity-id="' + self.current_media.activity_id + '"] .activity-comments');
					if ( activity_comments.length ) {
						activity_comments.html(html);
						activity_comments.attr('class', classes);
					}
				}
				activity_state = activity.find('.activity-state');
				if (activity_state.length) {
					html = activity_state.html();
					classes = activity_state.attr('class');
					activity_state.remove();
					activity_state = $('[data-bp-activity-id="' + self.current_media.activity_id + '"] .activity-state');
					if( activity_state.length ) {
						activity_state.html(html);
						activity_state.attr('class', classes);
					}
				}
				activity_meta = activity.find('.activity-meta');
				if (activity_meta.length) {
					html = activity_meta.html();
					classes = activity_meta.attr('class');
					activity_meta.remove();
					activity_meta = $('[data-bp-activity-id="' + self.current_media.activity_id + '"] .activity-meta');
					if( activity_meta.length ) {
						activity_meta.html(html);
						activity_meta.attr('class', classes);
					}
				}
				activity.remove();
			}
			if ( self.current_document.parent_activity_comments ) {
				activity = $('.bb-media-model-wrapper.document [data-bp-activity-id="' + self.current_document.activity_id + '"]');
				activity_comments = activity.find('.activity-comments');
				if (activity_comments.length) {
					html = activity_comments.html();
					classes = activity_comments.attr('class');
					activity_comments.remove();
					activity_comments = $('[data-bp-activity-id="' + self.current_document.activity_id + '"] .activity-comments');
					if ( activity_comments.length ) {
						activity_comments.html(html);
						activity_comments.attr('class', classes);
						//Reset document text preview
						activity_comments.find('.document-text.loaded').removeClass('loaded').find('.CodeMirror').remove();
						jQuery( window ).scroll();
					}

				}
				activity_state = activity.find('.activity-state');
				if (activity_state.length) {
					html = activity_state.html();
					classes = activity_state.attr('class');
					activity_state.remove();
					activity_state = $('[data-bp-activity-id="' + self.current_document.activity_id + '"] .activity-state');
					if( activity_state.length ) {
						activity_state.html(html);
						activity_state.attr('class', classes);
					}
				}
				activity_meta = activity.find('.activity-meta');
				if (activity_meta.length) {
					html = activity_meta.html();
					classes = activity_meta.attr('class');
					activity_meta.remove();
					activity_meta = $('[data-bp-activity-id="' + self.current_document.activity_id + '"] .activity-meta');
					if( activity_meta.length ) {
						activity_meta.html(html);
						activity_meta.attr('class', classes);
					}
				}
				activity.remove();
			}
		},

		closeTheatre: function (event) {
			event.preventDefault();
			var self = this;

			$( '.bb-media-model-wrapper' ).hide();
			self.is_open_media = false;

			self.resetRemoveActivityCommentsData();

			self.current_media = false;
		},

		closeDocumentTheatre: function (event) {
			event.preventDefault();
			var self   = this;
			var document_elements = $( document ).find( '.document-theatre' );
			document_elements.find( '.bb-media-section' ).removeClass( 'bb-media-no-preview' ).find('.document-preview').html( '' );
			$( '.bb-media-info-section.document' ).show();
			document_elements.hide();
			self.is_open_document = false;

			self.resetRemoveActivityCommentsData();

			self.current_document = false;
		},

		setMedias: function (target) {
			var media_elements = $( '.bb-open-media-theatre' ), i = 0, self = this;

			// check if on activity page, load only activity media in theatre.
			if ($( 'body' ).hasClass( 'activity' )) {
				media_elements = $( target ).closest( '.bb-activity-media-wrap' ).find( '.bb-open-media-theatre' );
			}

			if (typeof media_elements !== 'undefined') {
				self.medias = [];
				for (i = 0; i < media_elements.length; i++) {
					var media_element = $( media_elements[i] );
					if ( ! media_element.closest( '#bp-existing-media-content' ).length) {

						var m = {
							id					: media_element.data( 'id' ),
							attachment			: media_element.data( 'attachment-full' ),
							activity_id			: media_element.data( 'activity-id' ),
							attachment_id		: media_element.data( 'attachment-id' ),
							privacy				: media_element.data( 'privacy' ),
							parent_activity_id	: media_element.data( 'parent-activity-id' ),
							album_id			: media_element.data( 'album-id' ),
							group_id			: media_element.data( 'group-id' ),
							is_forum			: false
						};

						if (media_element.closest( '.forums-media-wrap' ).length) {
							m.is_forum = true;
						}

						if (typeof m.privacy !== 'undefined' && m.privacy == 'message') {
							m.is_message = true;
						} else {
							m.is_message = false;
						}

						self.medias.push( m );
					}
				}
			}
		},

		setDocuments: function (target) {
			var document_elements = $( '.bb-open-document-theatre' ), d = 0, self = this;

			// check if on activity page, load only activity media in theatre.
			if ( $( target ).closest( '.bp-search-ac-header').length ) {
				document_elements = $( target ).closest( '.bp-search-ac-header' ).find( '.bb-open-document-theatre' );
			} else if ( $( 'body' ).hasClass( 'activity' ) && $( target ).closest('.search-document-list').length === 0 ) {
				document_elements = $( target ).closest( '.bb-activity-media-wrap' ).find( '.bb-open-document-theatre' );
			}

			if (typeof document_elements !== 'undefined') {
				self.documents = [];
				for (d = 0; d < document_elements.length; d++) {
					var document_element = $( document_elements[d] );
					if ( ! document_elements.closest( '#bp-existing-document-content' ).length) {
						var a = {
							id					: document_element.data( 'id' ),
							attachment			: document_element.data( 'attachment-full' ),
							activity_id			: document_element.data( 'activity-id' ),
							attachment_id		: document_element.data( 'attachment-id' ),
							privacy				: document_element.data( 'privacy' ),
							parent_activity_id	: document_element.data( 'parent-activity-id' ),
							album_id			: document_element.data( 'album-id' ),
							group_id			: document_element.data( 'group-id' ),
							extension			: document_element.data( 'extension' ),
							target_text			: document_element.data( 'document-title' ),
							preview				: document_element.data( 'preview' ),
							text_preview		: document_element.data( 'text-preview' ),
							mirror_text			: document_element.data( 'mirror-text' ),
							target_icon_class	: document_element.data( 'icon-class' ),
							author				: document_element.data( 'author' ),
							download			: document_element.attr( 'href' ),
							mp3					: document_element.data( 'mp3-preview' ),
							is_forum			: false
						};

						if (document_element.closest( '.forums-media-wrap' ).length) {
							a.is_forum = true;
						}

						if (typeof a.privacy !== 'undefined' && a.privacy == 'message') {
							a.is_message = true;
						} else {
							a.is_message = false;
						}

						self.documents.push( a );
					}
				}
			}
		},

		setCurrentMedia: function (id) {
			var self = this, i = 0;
			for (i = 0; i < self.medias.length; i++) {
				if (id === self.medias[i].id) {
					self.current_media = self.medias[i];
					self.current_index = i;
					break;
				}
			}
		},

		setCurrentDocument: function (id) {
			var self = this, d = 0;
			for (d = 0; d < self.documents.length; d++) {
				if (id === self.documents[d].id) {
					self.current_document = self.documents[d];
					self.current_document_index = d;
					break;
				}
			}
		},

		showMedia: function () {
			var self = this;

			if (typeof self.current_media === 'undefined') {
				return false;
			}

			// refresh img.
			$( '.bb-media-model-wrapper.media .bb-media-section' ).find( 'img' ).attr( 'src', self.current_media.attachment );

			// privacy.
			var media_privacy_wrap = $( '.bb-media-section .bb-media-privacy-wrap' );

			if (media_privacy_wrap.length) {
				media_privacy_wrap.show();
				media_privacy_wrap.find( 'ul.media-privacy li' ).removeClass( 'selected' );
				media_privacy_wrap.find( '.bp-tooltip' ).attr( 'data-bp-tooltip', '' );
				var selected_media_privacy_elem = media_privacy_wrap.find( 'ul.media-privacy' ).find( 'li[data-value=' + self.current_media.privacy + ']' );
				selected_media_privacy_elem.addClass( 'selected' );
				media_privacy_wrap.find( '.bp-tooltip' ).attr( 'data-bp-tooltip', selected_media_privacy_elem.text() );
				media_privacy_wrap.find( '.privacy' ).removeClass( 'public' ).removeClass( 'loggedin' ).removeClass( 'onlyme' ).removeClass( 'friends' ).addClass( self.current_media.privacy );

				// hide privacy setting of media if activity is present.
				if ((typeof BP_Nouveau.activity !== 'undefined' &&
					typeof self.current_media.activity_id !== 'undefined' &&
					self.current_media.activity_id != 0) ||
					self.group_id ||
					self.current_media.is_forum ||
					self.current_media.group_id ||
					self.current_media.album_id ||
					self.current_media.is_message
				) {
					media_privacy_wrap.hide();
				}
			}

			// update navigation.
			self.navigationCommands();
		},

		showDocument: function () {
			var self = this;

			if (typeof self.current_document === 'undefined') {
				return false;
			}
			var target_text	   		= self.current_document.target_text;
			var target_icon_class	= self.current_document.target_icon_class;
			var document_elements 	= $( document ).find( '.document-theatre' );
			var extension 			= self.current_document.extension;
			var mirror_text_display = self.current_document.mirror_text;
			if( $.inArray( self.current_document.extension, [ 'css', 'txt', 'js', 'html', 'htm', 'csv' ]) !== -1) {
				document_elements.find( '.bb-document-section .document-preview' ).html('<i class="bb-icon-loader animate-spin"></i>');
				document_elements.find( '.bb-document-section' ).removeClass( 'bb-media-no-preview' );
				document_elements.find( '.bb-document-section .document-preview' ).html( '' );
				document_elements.find( '.bb-document-section .document-preview' ).html( '<h3>' + target_text + '</h3><div class="document-text"><textarea class="document-text-file-data-hidden"></textarea></div>' );
				document_elements.find( '.bb-document-section .document-preview .document-text' ).attr( 'data-extension', extension );
				document_elements.find( '.bb-document-section .document-preview .document-text textarea' ).html( mirror_text_display.replace( 'n','' ) );

				setTimeout( function(){
					bp.Nouveau.Media.documentCodeMirror();
				}  , 1000 );
			} else if( $.inArray( self.current_document.extension, BP_Nouveau.document.mp3_preview_extension.split(',') ) !== -1) {
				document_elements.find( '.bb-document-section .document-preview' ).html('<i class="bb-icon-loader animate-spin"></i>');
				document_elements.find( '.bb-document-section' ).removeClass( 'bb-media-no-preview' );
				document_elements.find( '.bb-document-section .document-preview' ).html( '' );
				document_elements.find( '.bb-document-section .document-preview' ).html( '<div class="img-section"><h3>' + target_text + '</h3><div class="document-audio"><audio src="' + self.current_document.mp3 + '" controls controlsList="nodownload"></audio></div></div>' );
			} else {
				if ( self.current_document.preview ) {
					document_elements.find( '.bb-document-section' ).removeClass( 'bb-media-no-preview' );
					document_elements.find( '.bb-document-section .document-preview' ).html( '' );
					document_elements.find( '.bb-document-section .document-preview' ).html( '<h3>' + target_text + '</h3><div class="img-section"><div class="img-block-wrap"> <img src="' + self.current_document.preview + '" /></div></div>' );
				} else {
					document_elements.find( '.bb-document-section' ).addClass( 'bb-media-no-preview' );
					document_elements.find( '.bb-document-section .document-preview' ).html( '' );
					document_elements.find( '.bb-document-section .document-preview' ).html( '<div class="img-section"> <i class="' + target_icon_class + '"></i><p>' + target_text + '</p></div>' );
				}
			}

			// privacy.
			var document_privacy_wrap = $( '.bb-media-section .bb-media-privacy-wrap' );

			if (document_privacy_wrap.length) {
				document_privacy_wrap.show();
				document_privacy_wrap.find( 'ul.media-privacy li' ).removeClass( 'selected' );
				document_privacy_wrap.find( '.bp-tooltip' ).attr( 'data-bp-tooltip', '' );
				var selected_document_privacy_elem = document_privacy_wrap.find( 'ul.media-privacy' ).find( 'li[data-value=' + self.current_document.privacy + ']' );
				selected_document_privacy_elem.addClass( 'selected' );
				document_privacy_wrap.find( '.bp-tooltip' ).attr( 'data-bp-tooltip', selected_document_privacy_elem.text() );
				document_privacy_wrap.find( '.privacy' ).removeClass( 'public' ).removeClass( 'loggedin' ).removeClass( 'onlyme' ).removeClass( 'friends' ).addClass( self.current_document.privacy );

				// hide privacy setting of media if activity is present.
				if ((typeof BP_Nouveau.activity !== 'undefined' &&
					typeof self.current_document.activity_id !== 'undefined' &&
					self.current_document.activity_id != 0) ||
					self.group_id ||
					self.current_document.is_forum ||
					self.current_document.group_id ||
					self.current_document.album_id ||
					self.current_document.is_message
				) {
					document_privacy_wrap.hide();
				}
			}

			// update navigation.
			self.navigationDocumentCommands();
		},

		next: function (event) {
			event.preventDefault();
			var self = this, activity_id;
			self.resetRemoveActivityCommentsData();
			if (typeof self.medias[self.current_index + 1] !== 'undefined') {
				self.current_index = self.current_index + 1;
				activity_id        = self.current_media.activity_id;
				self.current_media = self.medias[self.current_index];
				self.showMedia();
				if (activity_id != self.current_media.activity_id) {
					self.getActivity();
				} else {
					self.getMediasDescription();
				}
			} else {
				self.nextLink.hide();
			}
		},

		previous: function (event) {
			event.preventDefault();
			var self = this, activity_id;
			self.resetRemoveActivityCommentsData();
			if (typeof self.medias[self.current_index - 1] !== 'undefined') {
				self.current_index = self.current_index - 1;
				activity_id        = self.current_media.activity_id;
				self.current_media = self.medias[self.current_index];
				self.showMedia();
				if (activity_id != self.current_media.activity_id) {
					self.getActivity();
				} else {
					self.getMediasDescription();
				}
			} else {
				self.previousLink.hide();
			}
		},

		nextDocument: function (event) {
			event.preventDefault();

			var self = this, activity_id;
			self.resetRemoveActivityCommentsData();
			if (typeof self.documents[self.current_document_index + 1] !== 'undefined') {
				self.current_document_index = self.current_document_index + 1;
				activity_id        			= self.current_document.activity_id;
				self.current_document 		= self.documents[self.current_document_index];
				self.showDocument();
				if (activity_id != self.current_document.activity_id) {
					self.getDocumentsActivity();
				} else {
					self.getDocumentsDescription();
				}
			} else {
				self.nextDocumentLink.hide();
			}
		},

		previousDocument: function (event) {
			event.preventDefault();
			var self = this, activity_id;
			self.resetRemoveActivityCommentsData();
			if (typeof self.documents[self.current_document_index - 1] !== 'undefined') {
				self.current_document_index = self.current_document_index - 1;
				activity_id        			= self.current_document.activity_id;
				self.current_document = self.documents[self.current_document_index];
				self.showDocument();
				if (activity_id != self.current_document.activity_id) {
					self.getDocumentsActivity();
				} else {
					self.getDocumentsDescription();
				}
			} else {
				self.previousDocumentLink.hide();
			}
		},

		navigationCommands: function () {
			var self = this;
			if (self.current_index == 0 && self.current_index != (self.medias.length - 1)) {
				self.previousLink.hide();
				self.nextLink.show();
			} else if (self.current_index == 0 && self.current_index == (self.medias.length - 1)) {
				self.previousLink.hide();
				self.nextLink.hide();
			} else if (self.current_index == (self.medias.length - 1)) {
				self.previousLink.show();
				self.nextLink.hide();
			} else {
				self.previousLink.show();
				self.nextLink.show();
			}
		},

		navigationDocumentCommands: function () {
			var self = this;
			if (self.current_document_index == 0 && self.current_document_index != (self.documents.length - 1)) {
				self.previousDocumentLink.hide();
				self.nextDocumentLink.show();
			} else if (self.current_document_index == 0 && self.current_document_index == (self.documents.length - 1)) {
				self.previousDocumentLink.hide();
				self.nextDocumentLink.hide();
			} else if (self.current_document_index == (self.documents.length - 1)) {
				self.previousDocumentLink.show();
				self.nextDocumentLink.hide();
			} else {
				self.previousDocumentLink.show();
				self.nextDocumentLink.show();
			}
		},

		getActivity: function () {
			var self = this;

			$( '.bb-media-info-section .activity-list' ).addClass( 'loading' ).html( '<i class="bb-icon-loader animate-spin"></i>' );

			if (typeof BP_Nouveau.activity !== 'undefined' &&
				self.current_media &&
				typeof self.current_media.activity_id !== 'undefined' &&
				self.current_media.activity_id != 0 &&
				! self.current_media.is_forum
			) {

				if (self.activity_ajax != false) {
					self.activity_ajax.abort();
				}

				var on_page_activity_comments = $( '[data-bp-activity-id="' + self.current_media.activity_id + '"] .activity-comments' );
				if ( on_page_activity_comments.length ) {
					self.current_media.parent_activity_comments = true;
					on_page_activity_comments.html('');
				}

				self.activity_ajax = $.ajax(
					{
						type: 'POST',
						url: BP_Nouveau.ajaxurl,
						data: {
							action: 'media_get_activity',
							id: self.current_media.activity_id,
							group_id: ! _.isUndefined( self.current_media.group_id ) ? self.current_media.group_id : 0,
							nonce: BP_Nouveau.nonces.media
						},
						success: function ( response ) {
							if ( response.success ) {
								$( '.bb-media-info-section:visible .activity-list' ).removeClass( 'loading' ).html( response.data.activity );
								$( '.bb-media-info-section:visible' ).show();

								jQuery( window ).scroll();
							}
						}
					}
				);
			} else {
				$( '.bb-media-info-section.media' ).hide();
			}
		},

		getDocumentsActivity: function () {
			var self = this;

			$( '.bb-media-info-section .activity-list' ).addClass( 'loading' ).html( '<i class="bb-icon-loader animate-spin"></i>' );

			if (typeof BP_Nouveau.activity !== 'undefined' &&
				self.current_document &&
				typeof self.current_document.activity_id !== 'undefined' &&
				self.current_document.activity_id != 0 &&
				! self.current_document.is_forum
			) {

				if (self.activity_ajax != false) {
					self.activity_ajax.abort();
				}

				var on_page_activity_comments = $( '[data-bp-activity-id="' + self.current_document.activity_id + '"] .activity-comments' );
				if ( on_page_activity_comments.length ) {
					self.current_document.parent_activity_comments = true;
					on_page_activity_comments.html('');
				}

				self.activity_ajax = $.ajax(
					{
						type	: 'POST',
						url		: BP_Nouveau.ajaxurl,
						data	: {
							action		: 'document_get_activity',
							id			: self.current_document.activity_id,
							group_id 	: ! _.isUndefined( self.current_document.group_id ) ? self.current_document.group_id : 0,
							nonce		: BP_Nouveau.nonces.media
						},
						success: function (response) {
							if (response.success) {
								$( '.bb-media-info-section:visible .activity-list' ).removeClass( 'loading' ).html( response.data.activity );
								$( '.bb-media-info-section:visible' ).show();

								jQuery( window ).scroll();
							}
						}
					}
				);
			} else {
				$( '.bb-media-info-section.document' ).hide();
			}
		},

		getDocumentsDescription: function () {
			var self = this;

			$( '.bb-media-info-section .activity-list' ).addClass( 'loading' ).html( '<i class="bb-icon-loader animate-spin"></i>' );

			if (self.activity_ajax != false) {
				self.activity_ajax.abort();
			}

			self.activity_ajax = $.ajax(
				{
					type	: 'POST',
					url		: BP_Nouveau.ajaxurl,
					data: {
						action: 'document_get_document_description',
						id: self.current_document.id,
						attachment_id: self.current_document.attachment_id,
						nonce: BP_Nouveau.nonces.media
					},
					success: function (response) {
						if (response.success) {
							$( '.bb-media-info-section:visible .activity-list' ).removeClass( 'loading' ).html( response.data.description );
							$( '.bb-media-info-section:visible' ).show();
							$( window ).scroll();
						} else {
							$( '.bb-media-info-section.document' ).hide();
						}
					}
				}
			);
		},

		activityDeleted: function (event, data) {
			var self = this, i = 0;
			if (self.is_open_media && typeof data !== 'undefined' && data.action === 'delete_activity' && self.current_media.activity_id == data.id) {

				$( document ).find( '[data-bp-list="media"] .bb-open-media-theatre[data-id="' + self.current_media.id + '"]' ).closest( 'li' ).remove();
				$( document ).find( '[data-bp-list="activity"] .bb-open-media-theatre[data-id="' + self.current_media.id + '"]' ).closest( '.bb-activity-media-elem' ).remove();

				for (i = 0; i < self.medias.length; i++) {
					if (self.medias[i].activity_id == data.id) {
						self.medias.splice( i, 1 );
						break;
					}
				}

				if (self.current_index == 0 && self.current_index != (self.medias.length)) {
					self.current_index = -1;
					self.next( event );
				} else if (self.current_index == 0 && self.current_index == (self.medias.length)) {
					$( document ).find( '[data-bp-list="activity"] li.activity-item[data-bp-activity-id="' + self.current_media.activity_id + '"]' ).remove();
					self.closeTheatre( event );
				} else if (self.current_index == (self.medias.length)) {
					self.previous( event );
				} else {
					self.current_index = -1;
					self.next( event );
				}
			}
			if (self.is_open_document && typeof data !== 'undefined' && data.action === 'delete_activity' && self.current_document.activity_id == data.id) {

				$( document ).find( '[data-bp-list="document"] .bb-open-document-theatre[data-id="' + self.current_document.id + '"]' ).closest( 'div.ac-document-list[data-activity-id="' + self.current_document.activity_id + '"]' ).remove();
				$( document ).find( '[data-bp-list="activity"] .bb-open-document-theatre[data-id="' + self.current_document.id + '"]' ).closest( '.bb-activity-media-elem' ).remove();

				for (i = 0; i < self.documents.length; i++) {
					if (self.documents[i].activity_id == data.id) {
						self.documents.splice( i, 1 );
						break;
					}
				}

				if (self.current_document_index == 0 && self.current_document_index != (self.documents.length)) {
					self.current_document_index = -1;
					self.nextDocument( event );
				} else if (self.current_document_index == 0 && self.current_document_index == (self.documents.length)) {
					$( document ).find( '[data-bp-list="activity"] li.activity-item[data-bp-activity-id="' + self.current_document.activity_id + '"]' ).remove();
					self.closeDocumentTheatre( event );
				} else if (self.current_document_index == (self.documents.length)) {
					self.previousDocument( event );
				} else {
					self.current_document_index = -1;
					self.nextDocument( event );
				}
			}
		},

		/**
		 * [togglePopupDropdown description]
		 *
		 * @param  {[type]} event [description]
		 * @return {[type]}       [description]
		 */
		togglePopupDropdown: function (event) {
			var element;

			event = event || window.event;

			if (event.target) {
				element = event.target;
			} else if (event.srcElement) {
				element = event.srcElement;
			}

			if (element.nodeType === 3) {
				element = element.parentNode;
			}

			if (event.altKey === true || event.metaKey === true) {
				return event;
			}

			if ($( element ).hasClass( 'privacy-wrap' ) || $( element ).parent().hasClass( 'privacy-wrap' )) {
				return event;
			}

			$( 'ul.media-privacy' ).removeClass( 'bb-open' );
		},

		togglePrivacyDropdown: function (event) {
			var target = $( event.target );

			// Stop event propagation.
			event.preventDefault();

			target.closest( '.bb-media-privacy-wrap' ).find( '.media-privacy' ).toggleClass( 'bb-open' );
		},

		mediaPrivacyChange: function (event) {
			var target = $( event.target ), self = this, privacy = target.data( 'value' ), older_privacy = 'public';

			event.preventDefault();

			if (target.hasClass( 'selected' )) {
				return false;
			}

			target.closest( '.bb-media-privacy-wrap' ).find( '.privacy' ).addClass( 'loading' );
			older_privacy = target.closest( '.bb-media-privacy-wrap' ).find( 'ul.media-privacy li.selected' ).data( 'value' );
			target.closest( '.bb-media-privacy-wrap' ).find( 'ul.media-privacy li' ).removeClass( 'selected' );
			target.addClass( 'selected' );

			$.ajax(
				{
					type: 'POST',
					url: BP_Nouveau.ajaxurl,
					data: {
						action: 'media_update_privacy',
						id: self.current_media.id,
						_wpnonce: BP_Nouveau.nonces.media,
						privacy: privacy,
					},
					success: function () {
						target.closest( '.bb-media-privacy-wrap' ).find( '.privacy' ).removeClass( 'loading' ).removeClass( older_privacy );
						target.closest( '.bb-media-privacy-wrap' ).find( '.privacy' ).addClass( privacy );
						target.closest( '.bb-media-privacy-wrap' ).find( '.bp-tooltip' ).attr( 'data-bp-tooltip', target.text() );
					},
					error: function () {
						target.closest( '.bb-media-privacy-wrap' ).find( '.privacy' ).removeClass( 'loading' );
					}
				}
			);
		},

		openMediaActivityDescription: function (event) {
			event.preventDefault();
			var target = $( event.currentTarget );

			if ( target.parents( '.activity-media-description' ).find( '.bp-edit-media-activity-description' ).length < 1 ) {
				return false;
			}

			target.parents( '.activity-media-description' ).find( '.bp-edit-media-activity-description' ).show().addClass( 'open' );
			target.parents( '.activity-media-description' ).find( '.bp-media-activity-description' ).hide();
			target.hide();
		},

		closeMediaActivityDescription: function (event) {
			event.preventDefault();
			var target = $( event.currentTarget );

			if ( target.parents( '.activity-media-description' ).length < 1 ) {
				return false;
			}

			var default_value = target.parents( '.activity-media-description' ).find( '#add-activity-description' ).get(0).defaultValue;

			target.parents( '.activity-media-description' ).find( '.bp-add-media-activity-description' ).show();
			target.parents( '.activity-media-description' ).find( '.bp-media-activity-description' ).show();
			target.parents( '.activity-media-description' ).find( '#add-activity-description' ).val( default_value );
			target.parents( '.activity-media-description' ).find( '.bp-edit-media-activity-description' ).hide().removeClass( 'open' );
		},

		submitMediaActivityDescription: function (event) {
			event.preventDefault();

			var target = $( event.currentTarget ),
				parent_wrap = target.parents( '.activity-media-description' ),
				description = parent_wrap.find( '#add-activity-description' ).val(),
				attachment_id = parent_wrap.find( '#bp-attachment-id' ).val();

			var data = {
				'action': 'media_description_save',
				'description': description,
				'attachment_id': attachment_id,
				'_wpnonce'	: BP_Nouveau.nonces.media,
			};

			$.ajax(
				{
					type: 'POST',
					url: BP_Nouveau.ajaxurl,
					data: data,
					async: false,
					success: function (response) {
						if (response.success) {
							target.parents( '.activity-media-description').find( '.bp-media-activity-description' ).html( response.data.description ).show();
							target.parents( '.activity-media-description' ).find( '.bp-add-media-activity-description' ).show();
							parent_wrap.find( '#add-activity-description' ).val( response.data.description );
							parent_wrap.find( '#add-activity-description' ).get(0).defaultValue = response.data.description;
							if ( response.data.description == '' ) {
								target.parents( '.activity-media-description' ).find( '.bp-add-media-activity-description' ).removeClass( 'show-edit' ).addClass( 'show-add' );
							} else {
								target.parents( '.activity-media-description' ).find( '.bp-add-media-activity-description' ).addClass( 'show-edit' ).removeClass( 'show-add' );
							}

							target.parents( '.activity-media-description' ).find( '.bp-edit-media-activity-description' ).hide().removeClass( 'open' );
							target.parents( '.activity-media-description' ).find( '.bp-media-activity-description' ).show();
							target.parents( '.activity-media-description' ).find( '.bp-feedback.error' ).remove();
						} else {
							target.parents( '.activity-media-description' ).prepend( response.data.feedback );
						}
					}
				}
			);
		},
	};



	// Launch BP Nouveau Video Theatre.
	bp.Nouveau.Video.Theatre.start();

})( bp, jQuery );
