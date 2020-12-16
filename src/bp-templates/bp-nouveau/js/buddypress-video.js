/* jshint browser: true */
/* global bp, BP_Nouveau, Dropzone */
/* @version 1.0.0 */
window.bp = window.bp || {};

( function ( exports, $ ) {

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
			if ( typeof window.Dropzone !== 'undefined' ) {
				window.Dropzone.autoDiscover = false;
			}

			this.videoOptions = {
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
			$( document ).on( 'click', '#bp-video-uploader-close', this.closeUploader.bind( this ) );
			$( document ).on( 'click', '#bp-video-submit', this.submitVideo.bind( this ) );
			$(document).on('click', '.bb-activity-video-elem .video-action-wrap .video-action_more, #video-stream.video .bb-item-thumb .video-action-wrap .video-action_more, .bb-activity-video-elem .video-action-wrap .video-action_list li a', this.videoActivityActionButton.bind(this));
			$(document).on('change', '.bb-video-check-wrap [name="bb-video-select"]', this.addSelectedClassToWrapper.bind(this));
			$(document).on('click', '#bb-select-deselect-all-video', this.toggleSelectAllVideo.bind(this));
		},

		
		/**
		 * Video Activity action Button
		 */
		videoActivityActionButton: function (event) {
			event.preventDefault();

			$(event.currentTarget).closest('.bb-activity-video-elem').toggleClass('is-visible').siblings().removeClass('is-visible').closest('.activity-item').siblings().find('.bb-activity-video-elem').removeClass('is-visible');

			if ($(event.currentTarget).closest('.bb-activity-video-elem').length < 1) {
				$(event.currentTarget).closest('.bb-item-thumb').toggleClass('is-visible').parent().siblings().find('.bb-item-thumb').removeClass('is-visible').removeClass('is-visible');
			}

			if ( event.currentTarget.tagName.toLowerCase() == 'a' && ( !$(event.currentTarget).hasClass('video-action_more') ) ) {
				$(event.currentTarget).closest('.bb-activity-video-elem').removeClass('is-visible');
				$(event.currentTarget).closest('.bb-item-thumb').removeClass('is-visible');
			}
		},

		toggleSelectAllVideo: function (event) {
			event.preventDefault();

			if ($(event.currentTarget).hasClass('selected')) {
				$(event.currentTarget).data('bp-tooltip', BP_Nouveau.media.i18n_strings.selectall);
				this.deselectAllVideo(event);
			} else {
				$(event.currentTarget).data('bp-tooltip', BP_Nouveau.media.i18n_strings.unselectall);
				this.selectAllVideo(event);
			}

			$(event.currentTarget).toggleClass('selected');
		},

		selectAllVideo: function (event) {
			event.preventDefault();

			$('#buddypress').find('.video-list:not(.existing-video-list)').find('.bb-video-check-wrap [name="bb-video-select"]').each(
				function () {
					$(this).prop('checked', true);
					$(this).closest('.bb-item-thumb').addClass('selected');
					$(this).closest('.bb-video-check-wrap').find('.bp-tooltip').attr('data-bp-tooltip', BP_Nouveau.media.i18n_strings.unselect);
				}
			);
		},

		deselectAllVideo: function (event) {
			event.preventDefault();

			$('#buddypress').find('.video-list:not(.existing-video-list)').find('.bb-video-check-wrap [name="bb-video-select"]').each(
				function () {
					$(this).prop('checked', false);
					$(this).closest('.bb-item-thumb').removeClass('selected');
					$(this).closest('.bb-video-check-wrap').find('.bp-tooltip').attr('data-bp-tooltip', BP_Nouveau.media.i18n_strings.select);
				}
			);
		},

		addSelectedClassToWrapper: function (event) {
			var target = event.currentTarget;
			if ($(target).is(':checked')) {
				$(target).closest('.bb-video-check-wrap').find('.bp-tooltip').attr('data-bp-tooltip', BP_Nouveau.media.i18n_strings.unselect);
				$(target).closest('.bb-item-thumb').addClass('selected');
			} else {
				$(target).closest('.bb-item-thumb').removeClass('selected');
				$(target).closest('.bb-video-check-wrap').find('.bp-tooltip').attr('data-bp-tooltip', BP_Nouveau.media.i18n_strings.select);

				var selectAllVideo = $('.bp-nouveau #bb-select-deselect-all-video');
				if (selectAllVideo.hasClass('selected')) {
					selectAllVideo.removeClass('selected');
				}
			}
		},

		submitVideo: function ( event ) {
			var self = this, target = $( event.currentTarget ), data, privacy = $( '#bb-video-privacy' );
			event.preventDefault();

			if ( target.hasClass( 'saving' ) ) {
				return false;
			}

			target.addClass( 'saving' );

			if ( self.current_tab === 'bp-video-dropzone-content' ) {

				var post_content = $( '#bp-video-post-content' ).val();
				data             = {
					'action': 'video_save',
					'_wpnonce': BP_Nouveau.nonces.video,
					'videos': self.dropzone_video,
					'content': post_content,
					'album_id': self.video_album_id,
					'group_id': self.video_group_id,
					'privacy': privacy.val()
				};

				$( '#bp-video-dropzone-content .bp-feedback' ).remove();

				$.ajax(
					{
						type: 'POST',
						url: BP_Nouveau.ajaxurl,
						data: data,
						success: function ( response ) {
							if ( response.success ) {

								// It's the very first media, let's make sure the container can welcome it!
								if ( ! $( '#video-stream ul.video-list' ).length ) {
									$( '#video-stream' ).html( $( '<ul></ul>' ).addClass( 'video-list item-list bp-list bb-video-list grid' ) );
									$( '.bb-videos-actions' ).show();
								}

								// Prepend the activity.
								bp.Nouveau.inject( '#video-stream ul.video-list', response.data.video, 'prepend' );

								for ( var i = 0; i < self.dropzone_video.length; i++ ) {
									self.dropzone_video[ i ].saved = true;
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

			} else if ( self.current_tab === 'bp-existing-video-content' ) {
				var selected = [];
				$( '.bp-existing-video-wrap .bb-video-check-wrap [name="bb-video-select"]:checked' ).each(
					function () {
						selected.push( $( this ).val() );
					}
				);
				data = {
					'action': 'video_move_to_album',
					'_wpnonce': BP_Nouveau.nonces.video,
					'medias': selected,
					'album_id': self.video_album_id,
					'group_id': self.video_group_id
				};

				$( '#bp-existing-video-content .bp-feedback' ).remove();

				$.ajax(
					{
						type: 'POST',
						url: BP_Nouveau.ajaxurl,
						data: data,
						success: function ( response ) {
							if ( response.success ) {

								// It's the very first media, let's make sure the container can welcome it!
								if ( ! $( '#video-stream ul.media-list' ).length ) {
									$( '#video-stream' ).html( $( '<ul></ul>' ).addClass( 'video-list item-list bp-list bb-video-list grid' ) );
									$( '.bb-video-actions' ).show();
								}

								// Prepend the activity.
								bp.Nouveau.inject( '#video-stream ul.video-list', response.data.video, 'prepend' );

								// remove selected media from existing media list.
								$( '.bp-existing-video-wrap .bb-video-check-wrap [name="bb-video-select"]:checked' ).each(
									function () {
										if ( $( this ).closest( 'li' ).data( 'id' ) === $( this ).val() ) {
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
			} else if ( ! self.current_tab ) {
				self.closeUploader( event );
				target.removeClass( 'saving' );
			}

		},

		clearFolderLocationUI: function ( event ) {

			var closest_parent = jQuery( event.currentTarget ).closest( '.has-folderlocationUI' );
			if ( closest_parent.length > 0 ) {

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

		openUploader: function ( event ) {
			var self = this;
			event.preventDefault();

			if ( typeof window.Dropzone !== 'undefined' && $( 'div#video-uploader' ).length ) {

				$( '#bp-video-uploader' ).show();

				self.video_dropzone_obj = new Dropzone( 'div#video-uploader', self.videoOptions );

				self.video_dropzone_obj.on(
					'sending',
					function ( file, xhr, formData ) {
						formData.append( 'action', 'video_upload' );
						formData.append( '_wpnonce', BP_Nouveau.nonces.video );
					}
				);

				self.video_dropzone_obj.on(
					'addedfile',
					function () {
						setTimeout(
							function () {
								if ( self.video_dropzone_obj.getAcceptedFiles().length ) {
									$( '#bp-video-uploader-modal-status-text' ).text( wp.i18n.sprintf( BP_Nouveau.video.i18n_strings.upload_status, self.dropzone_video.length, self.video_dropzone_obj.getAcceptedFiles().length ) ).show();
								}
							},
							1000
						);
					}
				);

				self.video_dropzone_obj.on(
					'error',
					function ( file, response ) {
						if ( file.accepted ) {
							if ( typeof response !== 'undefined' && typeof response.data !== 'undefined' && typeof response.data.feedback !== 'undefined' ) {
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
					function ( file, response ) {
						if ( response.data.id ) {
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
					function ( file ) {
						if ( self.dropzone_video.length ) {
							for ( var i in self.dropzone_video ) {
								if ( file.upload.uuid == self.dropzone_video[ i ].uuid ) {

									if ( typeof self.dropzone_video[ i ].saved !== 'undefined' && ! self.dropzone_video[ i ].saved ) {
										self.removeAttachment( self.dropzone_video[ i ].id );
									}

									self.dropzone_video.splice( i, 1 );
									break;
								}
							}
						}
						if ( ! self.video_dropzone_obj.getAcceptedFiles().length ) {
							$( '#bp-video-uploader-modal-status-text' ).text( '' );
							$( '#bp-video-submit' ).hide();
						} else {
							$( '#bp-video-uploader-modal-status-text' ).text( wp.i18n.sprintf( BP_Nouveau.video.i18n_strings.upload_status, self.dropzone_video.length, self.video_dropzone_obj.getAcceptedFiles().length ) ).show();
						}
					}
				);
			}
		},
		
		closeUploader: function (event) {
			event.preventDefault();
			$( '#bp-video-uploader' ).hide();
			$( '#bp-video-uploader-modal-title' ).text( BP_Nouveau.video.i18n_strings.upload );
			$( '#bp-video-uploader-modal-status-text' ).text( '' );
			this.video_dropzone_obj.destroy();
			this.dropzone_video = [];
		},

		toggle_video_uploader: function () {
			var self = this;

			self.open_video_uploader();

		},

		open_video_uploader: function () {
			var self = this;
			if ( self.$el.find( '#activity-post-media-uploader' ).hasClass( 'open' ) ) {
				return false;
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
		openTheatre: function ( event ) {
			event.preventDefault();
			var target = $( event.currentTarget ), id, self = this;

			if ( target.closest( '#bp-existing-media-content' ).length ) {
				return false;
			}

			self.setupGlobals();
			self.setMedias( target );

			id = target.data( 'id' );
			self.setCurrentMedia( id );
			self.showMedia();
			self.navigationCommands();

			if ( typeof BP_Nouveau.activity !== 'undefined' && self.current_media && typeof self.current_media.activity_id !== 'undefined' && self.current_media.activity_id != 0 && ! self.current_media.is_forum ) {
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
