/* jshint browser: true */
/* global bp, BP_Nouveau, Dropzone, videojs */
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

			this.video_dropzone_obj         = null;
			this.video_thumb_dropzone_obj   = [];
			this.dropzone_video             = [];
			this.dropzone_video_thumb       = [];
			this.video_album_id             = typeof BP_Nouveau.video.album_id !== 'undefined' ? BP_Nouveau.video.album_id : false;
			this.video_group_id             = typeof BP_Nouveau.video.group_id !== 'undefined' ? BP_Nouveau.video.group_id : false;
			this.current_tab                = bodySelector.hasClass( 'single-topic' ) || bodySelector.hasClass( 'single-forum' ) ? false : 'bp-video-dropzone-content';

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

			this.videoThumbnailOptions = {
				url: BP_Nouveau.ajaxurl,
				timeout: 3 * 60 * 60 * 1000,
				dictFileTooBig: BP_Nouveau.video.dictFileTooBig,
				dictDefaultMessage: BP_Nouveau.video.dropzone_video_thumbnail_message,
				acceptedFiles: 'image/jpeg,image/png',
				autoProcessQueue: true,
				addRemoveLinks: true,
				uploadMultiple: false,
				maxFiles: 1,
				maxFilesize: typeof BP_Nouveau.video.max_upload_size !== 'undefined' ? BP_Nouveau.video.max_upload_size : 2,
				dictMaxFilesExceeded: BP_Nouveau.video.video_dict_file_exceeded,
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
			var bpNouveau = $( '.bp-nouveau' );

			$( document ).on( 'click', '#bp-add-video', this.openUploader.bind( this ) );
			$( document ).on( 'click', '.ac-video-thumbnail-edit', this.openEditThumbnailUploader.bind( this ) );
			$( document ).on( 'click', '#bp-video-thumbnail-uploader-close', this.closeEditThumbnailUploader.bind( this ) );
			$( document ).on( 'click', '#bp-video-uploader-close', this.closeUploader.bind( this ) );
			$( document ).on( 'click', '#bp-video-submit', this.submitVideo.bind( this ) );
			$( document ).on( 'click', '.bb-activity-video-elem .video-action-wrap .video-action_more, #video-stream.video .bb-item-thumb .video-action-wrap .video-action_more, .bb-activity-video-elem .video-action-wrap .video-action_list li a', this.videoActivityActionButton.bind( this ) );
			$( document ).on( 'change', '.bb-video-check-wrap [name="bb-video-select"]', this.addSelectedClassToWrapper.bind( this ) );
			$( document ).on( 'click', '#bb-select-deselect-all-video', this.toggleSelectAllVideo.bind( this ) );
			$( document ).on( 'click', '.video-action_list .edit_video', this.editVideo.bind( this ) );
			$( document ).on( 'click', '.video-action_list .video-file-delete, #bb-delete-video', this.deleteVideo.bind( this ) );

			//Video Album, Video Directory.
			bpNouveau.on( 'click', '#bb-create-video-album', this.openCreateVideoAlbumModal.bind( this ) );
			bpNouveau.on( 'click', '#bp-video-create-album-close', this.closeCreateVideoAlbumModal.bind( this ) );
			$( document ).on( 'click', '#bp-video-create-album-submit', this.saveAlbum.bind( this ) );


		},

		/**
		 * Video Activity action Button
		 */
		videoActivityActionButton: function ( event ) {
			event.preventDefault();

			$( event.currentTarget ).closest( '.bb-activity-video-elem' ).toggleClass( 'is-visible' ).siblings().removeClass( 'is-visible' ).closest( '.activity-item' ).siblings().find( '.bb-activity-video-elem' ).removeClass( 'is-visible' );

			if ( $( event.currentTarget ).closest( '.bb-activity-video-elem' ).length < 1 ) {
				$( event.currentTarget ).closest( '.bb-item-thumb' ).toggleClass( 'is-visible' ).parent().siblings().find( '.bb-item-thumb' ).removeClass( 'is-visible' ).removeClass( 'is-visible' );
			}

			if ( event.currentTarget.tagName.toLowerCase() == 'a' && ( !$( event.currentTarget ).hasClass( 'video-action_more' ) ) ) {
				$( event.currentTarget ).closest( '.bb-activity-video-elem' ).removeClass( 'is-visible' );
				$( event.currentTarget ).closest( '.bb-item-thumb' ).removeClass( 'is-visible' );
			}
		},

		toggleSelectAllVideo: function ( event ) {
			event.preventDefault();

			if ( $( event.currentTarget ).hasClass( 'selected' ) ) {
				$( event.currentTarget ).data( 'bp-tooltip', BP_Nouveau.media.i18n_strings.selectall );
				this.deselectAllVideo( event );
			} else {
				$( event.currentTarget ).data( 'bp-tooltip', BP_Nouveau.media.i18n_strings.unselectall );
				this.selectAllVideo( event );
			}

			$( event.currentTarget ).toggleClass( 'selected' );
		},

		selectAllVideo: function ( event ) {
			event.preventDefault();

			$( '#buddypress' ).find( '#video-stream > li' ).find( '.bb-video-check-wrap [name="bb-video-select"]' ).each(
				function () {
					$( this ).prop( 'checked', true );
					$( this ).closest( '.bb-item-thumb' ).addClass( 'selected' );
					$( this ).closest( '.bb-video-check-wrap' ).find( '.bp-tooltip' ).attr( 'data-bp-tooltip', BP_Nouveau.media.i18n_strings.unselect );
				}
			);
		},

		deselectAllVideo: function ( event ) {
			event.preventDefault();

			$( '#buddypress' ).find( '#video-stream > li' ).find( '.bb-video-check-wrap [name="bb-video-select"]' ).each(
				function () {
					$( this ).prop( 'checked', false );
					$( this ).closest( '.bb-item-thumb' ).removeClass( 'selected' );
					$( this ).closest( '.bb-video-check-wrap' ).find( '.bp-tooltip' ).attr( 'data-bp-tooltip', BP_Nouveau.media.i18n_strings.select );
				}
			);
		},

		addSelectedClassToWrapper: function ( event ) {
			var target = event.currentTarget;
			if ( $( target ).is( ':checked' ) ) {
				$( target ).closest( '.bb-video-check-wrap' ).find( '.bp-tooltip' ).attr( 'data-bp-tooltip', BP_Nouveau.media.i18n_strings.unselect );
				$( target ).closest( '.bb-item-thumb' ).addClass( 'selected' );
			} else {
				$( target ).closest( '.bb-item-thumb' ).removeClass( 'selected' );
				$( target ).closest( '.bb-video-check-wrap' ).find( '.bp-tooltip' ).attr( 'data-bp-tooltip', BP_Nouveau.media.i18n_strings.select );

				var selectAllVideo = $( '.bp-nouveau #bb-select-deselect-all-video' );
				if ( selectAllVideo.hasClass( 'selected' ) ) {
					selectAllVideo.removeClass( 'selected' );
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
				data = {
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
								if ( !$( '#video-stream ul.video-list' ).length ) {
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
								if ( !$( '#video-stream ul.media-list' ).length ) {
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
			} else if ( !self.current_tab ) {
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
				closest_parent.find( '.location-folder-title' ).text( BP_Nouveau.video.target_text );
				closest_parent.find( '.location-folder-back' ).hide().closest( '.has-folderlocationUI' ).find( '.bb-folder-selected-id' ).val( '0' );
				closest_parent.find( '.ac_document_search_folder' ).val( '' );
				closest_parent.find( '.bb-model-header h4 span' ).text( '...' );
				closest_parent.find( '.ac_document_search_folder_list ul' ).html( '' ).parent().hide().siblings( '.location-folder-list-wrap' ).find( '.location-folder-list' ).show();
			}
		},

		openUploader: function ( event ) {
			var self = this;
			event.preventDefault();

			if ( typeof window.Dropzone !== 'undefined' && $( 'div.video-uploader-wrapper #video-uploader' ).length ) {

				$( '#bp-video-uploader' ).show();

				self.video_dropzone_obj = new Dropzone( 'div.video-uploader-wrapper #video-uploader', self.videoOptions );

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
							file.id = response.id;
							response.data.uuid = file.upload.uuid;
							response.data.menu_order = self.dropzone_video.length;
							response.data.album_id = self.video_album_id;
							response.data.group_id = self.video_group_id;
							response.data.saved = false;
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

		openEditThumbnailUploader: function ( event ) {
			var self = this;
			event.preventDefault();

			if ( typeof window.Dropzone !== 'undefined' && $( 'div.video-thumbnail-uploader-wrapper #video-thumbnail-uploader' ).length ) {


				var target              = $( event.currentTarget );
				var videoAttachmentId   = target.attr( 'data-video-attachment-id' );
				var videoId             = target.attr( 'data-video-id' );

				$( '#bp-video-thumbnail-uploader' ).show();

				self.video_thumb_dropzone_obj = new Dropzone( 'div.video-thumbnail-uploader-wrapper #video-thumbnail-uploader', self.videoThumbnailOptions );

				self.video_thumb_dropzone_obj.on(
					'sending',
					function ( file, xhr, formData ) {
						formData.append( 'action', 'video_thumbnail_upload' );
						formData.append( '_wpnonce', BP_Nouveau.nonces.video );
					}
				);

				self.video_thumb_dropzone_obj.on(
					'addedfile',
					function ( file ) {

						if ( file.video_thumbnail_edit_data ) {
							self.dropzone_video_thumb.push( file.video_thumbnail_edit_data );
							$( '#bp-video-thumbnail-uploader .video-thumbnail-uploader-wrapper .dropzone.dz-clickable').addClass( 'dz-max-files-reached' );
						} else {
							setTimeout(
								function () {
									if ( self.video_thumb_dropzone_obj.getAcceptedFiles().length ) {
										$( '#bp-video-thumbnail-uploader-modal-status-text' ).text( wp.i18n.sprintf( BP_Nouveau.video.i18n_strings.upload_status, self.dropzone_video_thumb.length, self.video_thumb_dropzone_obj.getAcceptedFiles().length ) ).show();
									}
								},
								1000
							);
						}
					}
				);

				self.video_thumb_dropzone_obj.on(
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

				self.video_thumb_dropzone_obj.on(
					'queuecomplete',
					function () {
						$( '#bp-video-thumbnail-uploader-modal-title' ).text( BP_Nouveau.video.i18n_strings.upload_thumb );
					}
				);

				self.video_thumb_dropzone_obj.on(
					'processing',
					function () {
						$( '#bp-video-thumbnail-uploader-modal-title' ).text( BP_Nouveau.video.i18n_strings.uploading + '...' );
					}
				);

				self.video_thumb_dropzone_obj.on(
					'success',
					function ( file, response ) {
						if ( response.data.id ) {
							file.id                     = response.id;
							response.data.uuid          = file.upload.uuid;
							response.data.menu_order    = self.dropzone_video.length;
							response.data.album_id      = self.video_album_id;
							response.data.group_id      = self.video_group_id;
							response.data.saved         = false;
							self.dropzone_video_thumb.push( response.data );
						} else {
							this.removeFile( file );
						}
						$( '#bp-video-thumbnail-submit' ).show();
						$( '#bp-video-thumbnail-uploader-modal-title' ).text( BP_Nouveau.video.i18n_strings.uploading + '...' );
						$( '#bp-video-thumbnail-uploader-modal-status-text' ).text( wp.i18n.sprintf( BP_Nouveau.video.i18n_strings.upload_status, self.dropzone_video_thumb.length, self.video_thumb_dropzone_obj.getAcceptedFiles().length ) ).show();
					}
				);

				self.video_thumb_dropzone_obj.on(
					'removedfile',
					function ( file ) {
						if ( self.dropzone_video_thumb.length ) {
							for ( var i in self.dropzone_video_thumb ) {
								if ( file.upload.uuid == self.dropzone_video_thumb[ i ].uuid ) {

									if ( typeof self.dropzone_video_thumb[ i ].saved !== 'undefined' && !self.dropzone_video_thumb[ i ].saved ) {
										self.removeVideoThumbnailAttachment( self.dropzone_video_thumb[ i ].id );
									}

									self.dropzone_video_thumb.splice( i, 1 );
									break;
								}
							}
						}
						if ( ! self.video_thumb_dropzone_obj.getAcceptedFiles().length ) {
							$( '#bp-video-thumbnail-uploader-modal-status-text' ).text( '' );
							$( '#bp-video-thumbnail-submit' ).hide();
						} else {
							$( '#bp-video-thumbnail-uploader-modal-status-text' ).text( wp.i18n.sprintf( BP_Nouveau.video.i18n_strings.upload_status, self.dropzone_video_thumb.length, self.video_thumb_dropzone_obj.getAcceptedFiles().length ) ).show();
						}
					}
				);

				var data = {
					'action': 'video_get_edit_thumbnail_data',
					'_wpnonce': BP_Nouveau.nonces.video,
					'attachment_id': videoAttachmentId,
					'video_id': videoId,
				};

				$.ajax(
					{
						type: 'POST',
						url: BP_Nouveau.ajaxurl,
						data: data,
						success: function ( response ) {
							if ( response.success ) {

								if ( response.data.default_images ) {
									var ulSelector = $( '#bp-video-thumbnail-uploader .bp-video-thumbnail-auto-generated ul.video-thumb-list' );
									ulSelector.html( '' );
									ulSelector.html( response.data.default_images );
								}

								if ( response.data.dropzone_edit ) {
									var mock_file = false;

									mock_file = false;
									mock_file = {
										name: response.data.dropzone_edit.name,
										accepted: true,
										kind: 'image',
										upload: {
											filename: response.data.dropzone_edit.name,
											uuid: response.data.dropzone_edit.attachment_id
										},
										dataURL: response.data.dropzone_edit.url,
										id: response.data.dropzone_edit.attachment_id,
										video_thumbnail_edit_data: {
											'id': response.data.dropzone_edit.attachment_id,
											'media_id': response.data.dropzone_edit.id,
											'name': response.data.dropzone_edit.name,
											'thumb': response.data.dropzone_edit.thumb,
											'url': response.data.dropzone_edit.url,
											'uuid': response.data.dropzone_edit.attachment_id,
											'menu_order': 0,
											'saved': true
										}
									};

									if ( self.video_thumb_dropzone_obj ) {
										self.video_thumb_dropzone_obj.files.push( mock_file );
										self.video_thumb_dropzone_obj.emit( 'addedfile', mock_file );
										//self.video_thumb_dropzone_obj.emit( 'maxfilesreached', mock_file );
										self.createThumbnailFromUrl( mock_file );

									}
								}
							}
						}
					}
				);
			}
		},

		createThumbnailFromUrl: function ( mock_file ) {
			var self = this;
			self.video_thumb_dropzone_obj.createThumbnailFromUrl(
				mock_file,
				self.video_thumb_dropzone_obj.options.thumbnailWidth,
				self.video_thumb_dropzone_obj.options.thumbnailHeight,
				self.video_thumb_dropzone_obj.options.thumbnailMethod,
				true,
				function ( thumbnail ) {
					self.video_thumb_dropzone_obj.emit( 'thumbnail', mock_file, thumbnail );
					self.video_thumb_dropzone_obj.emit( 'complete', mock_file );
				}
			);
		},

		closeEditThumbnailUploader: function ( event ) {
			event.preventDefault();
			$( '#bp-video-thumbnail-uploader' ).hide();
			$( '#bp-video-thumbnail-uploader-modal-title' ).text( BP_Nouveau.video.i18n_strings.upload_thumb );
			$( '#bp-video-thumbnail-uploader-modal-status-text' ).text( '' );
			this.video_thumb_dropzone_obj.destroy();
			this.dropzone_video_thumb = [];
		},

		openAlbumUploader: function ( event ) {
			var self = this;
			event.preventDefault();

			if ( typeof window.Dropzone !== 'undefined' && $( 'div#video-album-uploader' ).length ) {

				$( '#bp-video-uploader' ).show();

				self.video_dropzone_obj = new Dropzone( 'div#video-album-uploader', self.videoOptions );

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
							file.id = response.id;
							response.data.uuid = file.upload.uuid;
							response.data.menu_order = self.dropzone_video.length;
							response.data.album_id = self.video_album_id;
							response.data.group_id = self.video_group_id;
							response.data.saved = false;
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

									if ( typeof self.dropzone_video[ i ].saved !== 'undefined' && !self.dropzone_video[ i ].saved ) {
										self.removeAttachment( self.dropzone_video[ i ].id );
									}

									self.dropzone_video.splice( i, 1 );
									break;
								}
							}
						}
						if ( !self.video_dropzone_obj.getAcceptedFiles().length ) {
							$( '#bp-video-uploader-modal-status-text' ).text( '' );
							$( '#bp-video-submit' ).hide();
						} else {
							$( '#bp-video-uploader-modal-status-text' ).text( wp.i18n.sprintf( BP_Nouveau.video.i18n_strings.upload_status, self.dropzone_video.length, self.video_dropzone_obj.getAcceptedFiles().length ) ).show();
						}
					}
				);
			}
		},

		removeVideoThumbnailAttachment: function ( id ) {
			var data = {
				'action': 'video_thumbnail_delete_attachment',
				'_wpnonce': BP_Nouveau.nonces.video,
				'id': id
			};

			$.ajax(
				{
					type: 'POST',
					url: BP_Nouveau.ajaxurl,
					data: data
				}
			);
		},

		closeUploader: function ( event ) {
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

		editVideo: function ( e ) {
			e.preventDefault();

			// ToDo: Open Edit Popup here
			console.log( 'Open Edit Popup here' );
		},

		deleteVideo: function ( event ) {
			var target = $( event.currentTarget );
			event.preventDefault();

			var video = [];
			var buddyPressSelector = $( '#buddypress' );
			var type = target.attr( 'data-type' );
			var fromWhere = target.data( 'item-from' );
			var id = '';
			var activityId = '';

			if ( 'video' === type ) {
				if ( !confirm( BP_Nouveau.video.i18n_strings.video_delete_confirm ) ) {
					return false;
				}
			}

			if ( target.hasClass( 'bb-delete' ) ) {

				if ( !confirm( BP_Nouveau.video.i18n_strings.video_delete_confirm ) ) {
					return false;
				}

				buddyPressSelector.find( '.video-list:not(.existing-video-list)' ).find( '.bb-video-check-wrap [name="bb-video-select"]:checked' ).each(
					function () {
						$( this ).closest( '.bb-video-thumb' ).addClass( 'loading deleting' );
						video.push( $( this ).val() );
					}
				);

			}

			activityId = target.data( 'parent-activity-id' );
			if ( fromWhere && fromWhere.length && 'activity' === fromWhere && video.length == 0 ) {
				id = target.attr( 'data-item-id' );
				video.push( id );
			}

			if ( video.length == 0 ) {
				video.push( target.data( 'item-id' ) );
			}

			if ( video.length == 0 ) {
				return false;
			}

			target.prop( 'disabled', true );
			$( '#buddypress #video-stream.video .bp-feedback' ).remove();

			var data = {
				'action': 'video_delete',
				'_wpnonce': BP_Nouveau.nonces.video,
				'video': video,
				'activity_id': activityId,
				'from_where': fromWhere,
			};

			$.ajax(
				{
					type: 'POST',
					url: BP_Nouveau.ajaxurl,
					data: data,
					success: function ( response ) {
						var feedback = '';
						if ( fromWhere && fromWhere.length && 'activity' === fromWhere ) {
							if ( response.success ) {
								$.each(
									video,
									function ( index, value ) {
										if ( $( '#activity-stream ul.activity-list li.activity .activity-content .activity-inner .bb-activity-video-wrap div[data-id="' + value + '"]' ).length ) {
											$( '#activity-stream ul.activity-list li.activity .activity-content .activity-inner .bb-activity-video-wrap div[data-id="' + value + '"]' ).remove();
										}
										if ( $( 'body .bb-activity-video-elem.video-activity.' + value ).length ) {
											$( 'body .bb-activity-video-elem.video-activity.' + value ).remove();
										}
									}
								);

								$( '#activity-stream ul.activity-list li[data-bp-activity-id="' + activityId + '"] .activity-content .activity-inner .bb-activity-video-wrap' ).remove();
								$( '#activity-stream ul.activity-list li[data-bp-activity-id="' + activityId + '"] .activity-content .activity-inner' ).append( response.data.video_content );

								var length = $( '#activity-stream ul.activity-list li[data-bp-activity-id="' + activityId + '"] .activity-content .activity-inner .bb-activity-video-elem' ).length;
								if ( length == 0 ) {
									$( '#activity-stream ul.activity-list li[data-bp-activity-id="' + activityId + '"]' ).remove();
								}

								if ( false === response.data.delete_activity ) {
									$( 'body #buddypress .activity-list li#activity-' + activityId ).replaceWith( response.data.activity_content );
								}
							}
						} else if ( fromWhere && fromWhere.length && 'video' === fromWhere ) {
							if ( response.success ) {
								if ( 'yes' === BP_Nouveau.video.is_video_directory ) {
									var store = bp.Nouveau.getStorage( 'bp-video' );
									var scope = store.scope;
									if ( 'personal' === scope ) {
										$( document ).find( 'li#video-personal a' ).trigger( 'click' );
										$( document ).find( 'li#video-personal' ).trigger( 'click' );
									} else if ( 'groups' === scope ) {
										$( document ).find( 'li#video-groups a' ).trigger( 'click' );
										$( document ).find( 'li#video-groups' ).trigger( 'click' );
									} else {
										$( document ).find( 'li#video-all a' ).trigger( 'click' );
										$( document ).find( 'li#video-all' ).trigger( 'click' );
									}
								} else {
									if ( response.data.video_personal_count ) {
										$( '#buddypress' ).find( '.bp-wrap .users-nav ul li#video-personal-li a span.count' ).text( response.data.video_personal_count );
									}

									if ( response.data.video_group_count ) {
										$( '#buddypress' ).find( '.bp-wrap .groups-nav ul li#videos-groups-li a span.count' ).text( response.data.video_group_count );
									}
									$.each(
										video,
										function ( index, value ) {
											if ( $( '#video-stream ul.video-list li[data-id="' + value + '"]' ).length ) {
												$( '#video-stream ul.video-list li[data-id="' + value + '"]' ).remove();
											}
										}
									);
									if ( $( '#buddypress' ).find( '.video-list:not(.existing-video-list)' ).find( 'li:not(.load-more)' ).length == 0 ) {
										$( '.bb-videos-actions' ).hide();
										feedback = '<aside class="bp-feedback bp-messages info">\n' +
											'\t<span class="bp-icon" aria-hidden="true"></span>\n' +
											'\t<p>' + BP_Nouveau.video.i18n_strings.no_videos_found + '</p>\n' +
											'\t</aside>';
										$( '#buddypress [data-bp-list="video"]' ).html( feedback );
									}
								}
							}
						} else {
							setTimeout(
								function () {
									target.prop( 'disabled', false );
								},
								500
							);
							if ( response.success ) {
								buddyPressSelector.find( '.video-list:not(.existing-video-list)' ).find( '.bb-video-check-wrap [name="bb-video-select"]:checked' ).each(
									function () {
										$( this ).closest( 'li' ).remove();
									}
								);
								if ( $( '#buddypress' ).find( '.video-list:not(.existing-video-list)' ).find( 'li:not(.load-more)' ).length == 0 ) {
									$( '.bb-videos-actions' ).hide();
									feedback = '<aside class="bp-feedback bp-messages info">\n' +
										'\t<span class="bp-icon" aria-hidden="true"></span>\n' +
										'\t<p>' + BP_Nouveau.video.i18n_strings.no_videos_found + '</p>\n' +
										'\t</aside>';
									$( '#buddypress [data-bp-list="video"]' ).html( feedback );
								}
							} else {
								$( '#buddypress #video-stream.video' ).prepend( response.data.feedback );
							}
						}

						// replace dummy image with original image by faking scroll event to call bp.Nouveau.lazyLoad.
						jQuery( window ).scroll();

					}
				}
			);

		},

		//Video Directory

		openCreateVideoAlbumModal: function ( event ) {
			event.preventDefault();

			this.openAlbumUploader( event );
			$( '#bp-video-create-album' ).show();
		},

		closeCreateVideoAlbumModal: function ( event ) {
			event.preventDefault();

			this.closeUploader( event );
			$( '#bp-video-create-album' ).hide();
			$( '#bb-album-title' ).val( '' );

		},

		saveAlbum: function ( event ) {
			console.log( 'HelloWorld!' );
			var target = $( event.currentTarget ), self = this, title = $( '#bb-album-title' ),
				privacy = $( '#bb-album-privacy' );

			if ( target.hasClass( 'saving' ) ) {
				return false;
			}

			event.preventDefault();

			if ( $.trim( title.val() ) === '' ) {
				title.addClass( 'error' );
				return false;
			} else {
				title.removeClass( 'error' );
			}

			if ( !self.group_id && $.trim( privacy.val() ) === '' ) {
				privacy.addClass( 'error' );
				return false;
			} else {
				privacy.removeClass( 'error' );
			}

			target.addClass( 'saving' );
			target.attr( 'disabled', true );
			var data = {
				'action': 'video_album_save',
				'_wpnonce': BP_Nouveau.nonces.video,
				'title': title.val(),
				'videos': self.dropzone_video,
				'privacy': privacy.val()
			};

			if ( self.album_id ) {
				data.album_id = self.album_id;
			}

			if ( self.group_id ) {
				data.group_id = self.group_id;
			}

			// remove all feedback erros from the DOM.
			$( '#bp-media-single-album .bp-feedback' ).remove();
			$( '#boss-media-create-album-popup .bp-feedback' ).remove();

			$.ajax(
				{
					type: 'POST',
					url: BP_Nouveau.ajaxurl,
					data: data,
					success: function ( response ) {
						setTimeout(
							function () {
								target.removeClass( 'saving' );
								target.prop( 'disabled', false );
							},
							500
						);
						if ( response.success ) {
							if ( self.album_id ) {
								$( '#bp-single-album-title' ).text( title.val() );
								$( '#bb-album-privacy' ).val( privacy.val() );
								self.cancelEditAlbumTitle( event );
							} else {
								$( '#buddypress .bb-albums-list' ).prepend( response.data.album );
								window.location.href = response.data.redirect_url;
							}
						} else {
							if ( self.album_id ) {
								$( '#bp-media-single-album' ).prepend( response.data.feedback );
							} else {
								$( '#boss-media-create-album-popup .bb-model-header' ).after( response.data.feedback );
							}
						}
					}
				}
			);

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

			this.videos = [];
			this.current_video = false;
			this.current_video_index = 0;
			this.is_open_video = false;
			this.nextVideoLink = $( '.bb-next-media' );
			this.previousVideoLink = $( '.bb-prev-media' );
			this.activity_ajax = false;
			this.group_id = typeof BP_Nouveau.video.group_id !== 'undefined' ? BP_Nouveau.video.group_id : false;

		},

		/**
		 * [addListeners description]
		 */
		addListeners: function () {

			$( document ).on( 'click', '.bb-open-video-theatre', this.openTheatre.bind( this ) );
			$( document ).on( 'click', '.bb-prev-media', this.previous.bind( this ) );
			$( document ).on( 'click', '.bb-next-media', this.next.bind( this ) );

		},
		openTheatre: function ( event ) {
			event.preventDefault();
			var target = $( event.currentTarget ), id, self = this;

			// alert("openTheatre called");
			// alert(target);
			if ( target.closest( '#bp-existing-video-content' ).length ) {
				return false;
			}
			// alert("Not return");

			self.setupGlobals();
			self.setVideos( target );

			id = target.data( 'id' );
			self.setCurrentVideo( id );
			self.showVideo();
			self.navigationCommands();

			if ( typeof BP_Nouveau.activity !== 'undefined' && self.current_video && typeof self.current_video.activity_id !== 'undefined' && self.current_video.activity_id != 0 && !self.current_video.is_forum ) {
				self.getActivity();
			} else {
				self.getVideosDescription();
			}

			$( '.bb-media-model-wrapper.document' ).hide();
			$( '.bb-media-model-wrapper.video' ).show();
			self.is_open_video = true;

			// document.addEventListener( 'keyup', self.checkPressedKey.bind( self ) );
		},

		getVideosDescription: function () {
			var self = this;

			$( '.bb-media-info-section .activity-list' ).addClass( 'loading' ).html( '<i class="bb-icon-loader animate-spin"></i>' );

			if ( self.activity_ajax != false ) {
				self.activity_ajax.abort();
			}

			self.activity_ajax = $.ajax(
				{
					type: 'POST',
					url: BP_Nouveau.ajaxurl,
					data: {
						action: 'video_get_video_description',
						id: self.current_video.id,
						attachment_id: self.current_video.attachment_id,
						nonce: BP_Nouveau.nonces.video
					},
					success: function ( response ) {
						if ( response.success ) {
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

		setVideos: function ( target ) {
			var video_elements = $( '.bb-open-video-theatre' ), i = 0, self = this;
			// check if on activity page, load only activity video in theatre.
			if ( $( 'body' ).hasClass( 'activity' ) ) {
				video_elements = $( target ).closest( '.bb-activity-video-wrap' ).find( '.bb-open-video-theatre' );
			}

			if ( typeof video_elements !== 'undefined' ) {
				self.videos = [];
				for ( i = 0; i < video_elements.length; i++ ) {
					var video_element = $( video_elements[ i ] );
					if ( !video_element.closest( '#bp-existing-media-content' ).length ) {

						var m = {
							id: video_element.data( 'id' ),
							attachment: video_element.data( 'attachment-full' ),
							activity_id: video_element.data( 'activity-id' ),
							attachment_id: video_element.data( 'attachment-id' ),
							privacy: video_element.data( 'privacy' ),
							parent_activity_id: video_element.data( 'parent-activity-id' ),
							album_id: video_element.data( 'album-id' ),
							group_id: video_element.data( 'group-id' ),
							is_forum: false
						};

						if ( video_element.closest( '.forums-media-wrap' ).length ) {
							m.is_forum = true;
						}

						if ( typeof m.privacy !== 'undefined' && m.privacy == 'message' ) {
							m.is_message = true;
						} else {
							m.is_message = false;
						}

						self.videos.push( m );
					}
				}
			}
		},
		setCurrentVideo: function ( id ) {
			var self = this, i = 0;
			for ( i = 0; i < self.videos.length; i++ ) {
				if ( id === self.videos[ i ].id ) {
					self.current_video = self.videos[ i ];
					self.current_index = i;
					break;
				}
			}
		},
		showVideo: function () {
			var self = this;
			if ( typeof self.current_video === 'undefined' ) {
				return false;
			}
			// refresh video.
			$( '.bb-media-model-wrapper.video .bb-media-section' ).find( 'figure' ).addClass( 'loading' ).html( '<i class="bb-icon-loader animate-spin"></i>' );

			// privacy.
			var video_privacy_wrap = $( '.bb-media-section .bb-media-privacy-wrap' );

			if ( video_privacy_wrap.length ) {
				video_privacy_wrap.show();
				video_privacy_wrap.find( 'ul.media-privacy li' ).removeClass( 'selected' );
				video_privacy_wrap.find( '.bp-tooltip' ).attr( 'data-bp-tooltip', '' );
				var selected_video_privacy_elem = video_privacy_wrap.find( 'ul.media-privacy' ).find( 'li[data-value=' + self.current_video.privacy + ']' );
				selected_video_privacy_elem.addClass( 'selected' );
				video_privacy_wrap.find( '.bp-tooltip' ).attr( 'data-bp-tooltip', selected_video_privacy_elem.text() );
				video_privacy_wrap.find( '.privacy' ).removeClass( 'public' ).removeClass( 'loggedin' ).removeClass( 'onlyme' ).removeClass( 'friends' ).addClass( self.current_video.privacy );

				// hide privacy setting of video if activity is present.
				if ( ( typeof BP_Nouveau.activity !== 'undefined' &&
					typeof self.current_video.activity_id !== 'undefined' &&
					self.current_video.activity_id != 0 ) ||
					self.group_id ||
					self.current_video.is_forum ||
					self.current_video.group_id ||
					self.current_video.album_id ||
					self.current_video.is_message
				) {
					video_privacy_wrap.hide();
				}
			}

			// update navigation.
			self.navigationCommands();
		},
		navigationCommands: function () {
			var self = this;
			if ( self.current_index == 0 && self.current_index != ( self.videos.length - 1 ) ) {
				self.previousVideoLink.hide();
				self.nextVideoLink.show();
			} else if ( self.current_index == 0 && self.current_index == ( self.videos.length - 1 ) ) {
				self.previousVideoLink.hide();
				self.nextVideoLink.hide();
			} else if ( self.current_index == ( self.videos.length - 1 ) ) {
				self.previousVideoLink.show();
				self.nextVideoLink.hide();
			} else {
				self.previousVideoLink.show();
				self.nextVideoLink.show();
			}
		},
		next: function ( event ) {
			event.preventDefault();
			var self = this, activity_id;
			self.resetRemoveActivityCommentsData();
			if ( typeof self.videos[ self.current_index + 1 ] !== 'undefined' ) {
				self.current_index = self.current_index + 1;
				activity_id = self.current_video.activity_id;
				self.current_video = self.videos[ self.current_index ];
				self.showVideo();
				if ( activity_id != self.current_video.activity_id ) {
					self.getActivity();
				} else {
					self.getVideosDescription();
				}
			} else {
				self.nextLink.hide();
			}
		},

		previous: function ( event ) {
			event.preventDefault();
			var self = this, activity_id;
			self.resetRemoveActivityCommentsData();
			if ( typeof self.videos[ self.current_index - 1 ] !== 'undefined' ) {
				self.current_index = self.current_index - 1;
				activity_id = self.current_video.activity_id;
				self.current_video = self.videos[ self.current_index ];
				self.showVideo();
				if ( activity_id != self.current_video.activity_id ) {
					self.getActivity();
				} else {
					self.getVideosDescription();
				}
			} else {
				self.previousLink.hide();
			}
		},

		resetRemoveActivityCommentsData: function () {
			var self = this, activity_comments = false, activity_meta = false, activity_state = false, activity = false,
				html = false, classes = false;
			if ( self.current_video.parent_activity_comments ) {
				activity = $( '.bb-media-model-wrapper.video [data-bp-activity-id="' + self.current_video.activity_id + '"]' );
				activity_comments = activity.find( '.activity-comments' );
				if ( activity_comments.length ) {
					html = activity_comments.html();
					classes = activity_comments.attr( 'class' );
					activity_comments.remove();
					activity_comments = $( '[data-bp-activity-id="' + self.current_video.activity_id + '"] .activity-comments' );
					if ( activity_comments.length ) {
						activity_comments.html( html );
						activity_comments.attr( 'class', classes );
					}
				}
				activity_state = activity.find( '.activity-state' );
				if ( activity_state.length ) {
					html = activity_state.html();
					classes = activity_state.attr( 'class' );
					activity_state.remove();
					activity_state = $( '[data-bp-activity-id="' + self.current_video.activity_id + '"] .activity-state' );
					if ( activity_state.length ) {
						activity_state.html( html );
						activity_state.attr( 'class', classes );
					}
				}
				activity_meta = activity.find( '.activity-meta' );
				if ( activity_meta.length ) {
					html = activity_meta.html();
					classes = activity_meta.attr( 'class' );
					activity_meta.remove();
					activity_meta = $( '[data-bp-activity-id="' + self.current_video.activity_id + '"] .activity-meta' );
					if ( activity_meta.length ) {
						activity_meta.html( html );
						activity_meta.attr( 'class', classes );
					}
				}
				activity.remove();
			}
		},

		getActivity: function () {
			var self = this;

			$( '.bb-video-info-section .activity-list' ).addClass( 'loading' ).html( '<i class="bb-icon-loader animate-spin"></i>' );

			if ( typeof BP_Nouveau.activity !== 'undefined' &&
				self.current_video &&
				typeof self.current_video.activity_id !== 'undefined' &&
				self.current_video.activity_id != 0 &&
				!self.current_video.is_forum
			) {

				if ( self.activity_ajax != false ) {
					self.activity_ajax.abort();
				}

				$( '.bb-media-info-section.media' ).show();
				var on_page_activity_comments = $( '[data-bp-activity-id="' + self.current_video.activity_id + '"] .activity-comments' );
				if ( on_page_activity_comments.length ) {
					self.current_video.parent_activity_comments = true;
					on_page_activity_comments.html( '' );
				}

				self.activity_ajax = $.ajax(
					{
						type: 'POST',
						url: BP_Nouveau.ajaxurl,
						data: {
							action: 'video_get_activity',
							id: self.current_video.activity_id,
							group_id: !_.isUndefined( self.current_video.group_id ) ? self.current_video.group_id : 0,
							video_id: !_.isUndefined( self.current_video.id ) ? self.current_video.id : 0,
							nonce: BP_Nouveau.nonces.video
						},
						success: function ( response ) {
							if ( response.success ) {

								$( '.bb-media-model-wrapper.video .bb-media-section' ).find( 'figure' ).html( response.data.video_data );
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
	};

	// Launch BP Nouveau Video Theatre.
	bp.Nouveau.Video.Theatre.start();

	/**
	 * [Video Player description]
	 *
	 * @type {Object}
	 */
	bp.Nouveau.Video.Player = {

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

			// Video File Activity Preview.
			bp.Nouveau.Video.Player.openPlayer();

			$( window ).on(
				'scroll resize',
				function () {
					bp.Nouveau.Video.Player.openPlayer();
				}
			);

		},

		/**
		 * [addListeners description]
		 */
		addListeners: function () {

			$( document ).on( 'click', '.video-js', this.openPlayer.bind( this ) );

		},

		openPlayer: function () {

			$( '.video-js:not(.loaded)' ).each(
				function () {

					var self = this;
					var options = {};

					videojs(
						self,
						options,
						function onPlayerReady() {
							this.on(
								'ended',
								function () {
								}
							);
						}
					);

					$( this ).addClass( 'loaded' );
				}
			);

		},
	};

	// Launch BP Nouveau Video Player.
	bp.Nouveau.Video.Player.start();

} )( bp, jQuery );
