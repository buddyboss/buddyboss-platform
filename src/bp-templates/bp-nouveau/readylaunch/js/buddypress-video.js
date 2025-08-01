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

	var bpNouveauLocal = BP_Nouveau,
		bbRlMedia      = bpNouveauLocal.media,
		bbRlAjaxUrl    = bpNouveauLocal.ajaxurl,
		bbRlVideo      = bpNouveauLocal.video,
		bbRlNonce      = bpNouveauLocal.nonces,
		bbRlActivity   = bpNouveauLocal.activity;

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
			var bodySelector              = $( 'body' );
			this.thumbnail_xhr            = null;
			this.thumbnail_interval       = null;
			this.thumbnail_max_interval   = 6;
			this.current_page             = 1;
			this.video_dropzone_obj       = null;
			this.video_thumb_dropzone_obj = [];
			this.dropzone_video           = [];
			this.dropzone_video_thumb     = [];
			this.video_album_id           = typeof bbRlVideo.album_id !== 'undefined' ? bbRlVideo.album_id : false;
			if ( ! this.video_album_id && parseInt( bbRlMedia.current_album ) > 0 ) {
				this.video_album_id = parseInt( bbRlMedia.current_album );
			}
			this.video_group_id = typeof bbRlVideo.group_id !== 'undefined' ? bbRlVideo.group_id : false;
			this.current_tab    = bodySelector.hasClass( 'single-topic' ) || bodySelector.hasClass( 'single-forum' ) ? false : 'bp-video-dropzone-content';
			// set up dropzones auto discover to false so it does not automatically set dropzones.
			if ( typeof window.Dropzone !== 'undefined' ) {
				window.Dropzone.autoDiscover = false;
			}
			var uploaderVideoTemplate = document.getElementsByClassName( 'uploader-post-video-template' ).length ? document.getElementsByClassName( 'uploader-post-video-template' )[0].innerHTML : ''; // Check to avoid error if Node is missing.
			this.videoOptions         = {
				url: bbRlAjaxUrl,
				timeout: 3 * 60 * 60 * 1000,
				dictFileTooBig: bbRlVideo.dictFileTooBig,
				acceptedFiles: bbRlVideo.video_type,
				createImageThumbnails: false,
				dictDefaultMessage: bbRlVideo.dropzone_video_message,
				autoProcessQueue: true,
				addRemoveLinks: true,
				uploadMultiple: false,
				maxFiles: typeof bbRlVideo.maxFiles !== 'undefined' ? bbRlVideo.maxFiles : 10,
				maxFilesize: typeof bbRlVideo.max_upload_size !== 'undefined' ? bbRlVideo.max_upload_size : 2,
				dictInvalidFileType: bbRlVideo.dictInvalidFileType,
				previewTemplate: uploaderVideoTemplate,
				dictCancelUploadConfirmation: bbRlVideo.dictCancelUploadConfirmation,
			};

			this.videoThumbnailOptions = {
				url: bbRlAjaxUrl,
				timeout: 3 * 60 * 60 * 1000,
				dictFileTooBig: bbRlVideo.dictFileTooBig,
				dictDefaultMessage: bbRlVideo.dropzone_video_thumbnail_message,
				acceptedFiles: 'image/jpeg,image/png',
				autoProcessQueue: true,
				addRemoveLinks: true,
				uploadMultiple: false,
				maxFiles: 1,
				thumbnailMethod: 'contain',
				thumbnailWidth: null,
				thumbnailHeight: '300',
				maxFilesize: typeof bbRlVideo.max_upload_size !== 'undefined' ? bbRlVideo.max_upload_size : 2,
				dictMaxFilesExceeded: bbRlVideo.thumb_dict_file_exceeded,
				dictCancelUploadConfirmation: bbRlVideo.dictCancelUploadConfirmation,
			};

			// if defined, add custom dropzone options.
			if ( typeof bbRlVideo.dropzone_options !== 'undefined' ) {
				Object.assign( this.options, bbRlVideo.dropzone_options );
			}
		},

		/**
		 * [addListeners description]
		 */
		addListeners: function () {
			var bpNouveau = $( '.bp-nouveau' ),
				$document = $( document );

			$document.on( 'click', '#bp-add-video', this.openUploader.bind( this ) );
			$document.on( 'click', '.bb-rl-video-thumbnail-submit', this.submitVideoThumbnail.bind( this ) );
			$document.on( 'click', '.bb-rl-ac-video-thumbnail-edit', this.openEditThumbnailUploader.bind( this ) );
			$document.on( 'click', '.bb-rl-video-thumbnail-uploader-close', this.closeEditThumbnailUploader.bind( this ) );
			$document.on( 'click', '#bp-video-uploader-close', this.closeUploader.bind( this ) );
			$document.on( 'click', '#bp-video-submit', this.submitVideo.bind( this ) );
			$document.on( 'click', '.bp-video-uploader .modal-container .bb-field-uploader-actions', this.uploadVideoNavigate.bind( this ) );
			$document.on( 'click', '.bb-rl-more_dropdown-wrap .bb_rl_more_dropdown__action, .bb-rl-activity-video-elem .bb-rl-more_dropdown-wrap .video-action_list li a, .bb-rl-media-model-container .bb-rl-activity-list .bb-rl-more_dropdown-wrap > a, .bb-rl-media-model-container .bb-rl-activity-list .bb-rl-more_dropdown-wrap .video-action_list li a', this.videoActivityActionButton.bind( this ) );
			$document.on( 'click', '.activity .bb-rl-video-move-activity, #media-stream .bb-rl-video-move-activity, #video-stream .bb-rl-video-move-activity', this.moveVideoIntoAlbum.bind( this ) );
			$document.on( 'click', '.bb-rl-video-open-create-popup-album', this.createAlbumInPopup.bind( this ) );
			$document.on( 'click', '.bb-rl-ac-video-close-button', this.closeVideoMove.bind( this ) );
			$document.on( 'click', '.bb-rl-ac-video-move', this.openVideoMove.bind( this ) );
			$document.on( 'change', '.bb-video-check-wrap [name="bb-video-select"]', this.addSelectedClassToWrapper.bind( this ) );
			$document.on( 'click', '#bb-select-deselect-all-video', this.toggleSelectAllVideo.bind( this ) );
			$document.on( 'click', '.video-action_list .bb-rl-video-file-delete, #bb-delete-video', this.deleteVideo.bind( this ) );
			$document.on( 'click', '.bb-rl-video-thumbnail-uploader.opened-edit-thumbnail .bb-rl-video-thumbnail-custom .bb-rl-close-thumbnail-custom', this.deleteVideoThumb.bind( this ) );

			if ( undefined !== BP_Nouveau.is_send_ajax_request && '1' === BP_Nouveau.is_send_ajax_request ) {
				$( '#buddypress [data-bp-list="video"]' ).on( 'bp_ajax_request', this.bp_ajax_video_request );
			} else {
				this.bb_video_after_load();
			}

			// Video Album, Video Directory.
			bpNouveau.on( 'click', '#bb-create-video-album', this.openCreateVideoAlbumModal.bind( this ) );
			bpNouveau.on( 'click', '#bp-video-create-album-close', this.closeCreateVideoAlbumModal.bind( this ) );
			$document.on( 'click', '#bp-video-create-album-submit', this.saveAlbum.bind( this ) );
			// Video Load More.
			$( '.bp-nouveau [data-bp-list="video"]' ).on( 'click', 'li.load-more', this.injectVideos.bind( this ) );

			// Create Album.
			$document.on( 'click', '.bb-rl-video-create-popup-album-submit', this.submitCreateAlbumInPopup.bind( this ) );

		},

		submitVideoThumbnail: function ( event ) {
			var self = this, target = $( event.currentTarget );
			event.preventDefault();

			if ( target.hasClass( 'saving' ) || target.hasClass( 'is-disabled' ) ) {
				return false;
			}

			target.addClass( 'saving' );

			var uploader                 = $( '.bb-rl-video-thumbnail-uploader.opened-edit-thumbnail' ),
				videoId                  = uploader.find( '.bb-rl-video-edit-thumbnail-hidden-video-id' ).val(),
				videoAttachmentId        = uploader.find( '.bb-rl-video-edit-thumbnail-hidden-attachment-id' ).val(),
				videoCheckedAttachmentId = uploader.find( 'input[type="radio"]:checked' ).val(),
				data                     = {
					'action'              : 'video_thumbnail_save',
					'_wpnonce'            : bbRlNonce.video,
					'video_thumbnail'     : self.dropzone_video_thumb,
					'video_id'            : videoId,
					'video_attachment_id' : videoAttachmentId,
					'video_default_id'    : videoCheckedAttachmentId,
			};

			$.ajax(
				{
					type    : 'POST',
					url     : bbRlAjaxUrl,
					data    : data,
					success : function ( response ) {
						if ( ! response.success ) {
							target.removeClass( 'saving' );
							return;
						}
						var thumbnailSrc = response.data.thumbnail,
							activityElem = $( '.bb-activity-video-elem a.bb-rl-video-cover-wrap[data-id="' + videoId + '"]' ),
							videoThumb   = $( '.bb-video-thumb a.bb-rl-video-cover-wrap[data-id="' + videoId + '"]' );

						if ( videoThumb.find( 'img' ).length ) {
							videoThumb.find( 'img' ).attr( 'src', thumbnailSrc );
						}

						if ( activityElem.find( 'img' ).length ) {
							activityElem.find( 'img' ).attr( 'src', thumbnailSrc );
						}

						var videoElem = $( '.bb-activity-video-elem .video-js[data-id="' + videoId + '"]' );
						if ( videoElem.length ) {
							videoElem.attr( 'poster', thumbnailSrc ).find( 'video' ).attr( 'poster', thumbnailSrc ).end().find( '.vjs-poster' ).css( 'background-image', 'url("' + thumbnailSrc + '")' ).find( 'img' ).attr( 'src', thumbnailSrc );
						}

						var theatreVideo = $( '#bb-rl-theatre-video-' + videoId );
						if ( theatreVideo.length ) {
							theatreVideo.attr( 'poster', thumbnailSrc ).find( 'video' ).attr( 'poster', thumbnailSrc ).end().find( '.vjs-poster' ).css( 'background-image', 'url("' + thumbnailSrc + '")' );
							videoThumb.find( 'img' ).attr( 'src', thumbnailSrc );
						}

						self.dropzone_video_thumb.forEach(
							function ( thumb ) {
								thumb.saved = true;
							}
						);

						if ( response.data.video_attachments ) {
							$( '.video-action_list .edit_thumbnail_video a[data-video-attachment-id="' + response.data.video_attachment_id + '"]' ).attr( 'data-video-attachments', response.data.video_attachments );
						}

						if ( -1 === thumbnailSrc.toLowerCase().indexOf( 'video-placeholder.jpg' ) ) {
							$( '.bb-activity-video-elem[data-id="' + videoId + '"]' ).removeClass( 'has-no-thumbnail' );
							$( 'a.bb-rl-video-cover-wrap[data-id="' + videoId + '"]' ).parent().removeClass( 'has-no-thumbnail' );
						}

						self.closeEditThumbnailUploader( event );
						target.removeClass( 'saving' );
					}
				}
			);
		},

		uploadVideoNavigate: function ( event ) {
			event.preventDefault();
			var target       = $( event.currentTarget ),
				currentPopup = $( target ).closest( '#bp-video-uploader' ),
				breadcrumb   = currentPopup.find( '.bb-rl-breadcrumbs-append-ul-li .breadcrumb' );

			if ( $( target ).hasClass( 'bb-field-uploader-next' ) ) {
				currentPopup.find( '.bb-field-steps-1' ).slideUp( 200 ).siblings( '.bb-field-steps' ).slideDown( 200 );
				currentPopup.find( '#bp-video-submit, #bp-video-prev, .bb-rl-video-open-create-popup-album.create-album, #bb-video-privacy' ).show();
				if ( Number( $( currentPopup ).find( '.bb-rl-album-selected-id' ) ) !== 0 && $( currentPopup ).find( '.location-album-list li.is_active' ).length ) {
					$( currentPopup ).find( '.location-album-list' ).scrollTop( $( currentPopup ).find( '.location-album-list li.is_active' ).offset().top - $( currentPopup ).find( '.location-album-list' ).offset().top );
				}
				breadcrumb.find( '.item span:not(.hidden)' ).each(
					function ( i ) {
						if ( i > 0 ) {
							if ( breadcrumb.find( '.item' ).width() > breadcrumb.width() ) {
								breadcrumb.find( '.item span.hidden' ).append( breadcrumb.find( '.item span' ).eq( 2 ) );
								if ( ! breadcrumb.find( '.item .more_options' ).length ) {
									$( '<span class="more_options">...</span>' ).insertAfter( breadcrumb.find( '.item span' ).eq( 0 ) );
								}
							}
						}
					}
				);
			} else {
				$( target ).hide();
				currentPopup.find( '#bp-video-prev, .bb-rl-video-open-create-popup-album' ).hide();
				currentPopup.find( '.bb-field-steps-2' ).slideUp( 200 ).siblings( '.bb-field-steps' ).slideDown( 200 );
				if ( currentPopup.closest( '#bp-media-single-folder' ).length ) {
					$( '#bb-video-privacy' ).hide();
				}
			}
		},

		/**
		 * Video Activity action Button
		 */
		videoActivityActionButton: function ( event ) {
			event.preventDefault();
			var target             = $( event.currentTarget ), $body = $( 'body' ),
				$activityVideoElem = target.closest( '.bb-rl-activity-video-elem' );

			$activityVideoElem.toggleClass( 'is-visible' ).siblings().removeClass( 'is-visible' ).closest( '.activity-item' ).siblings().find( '.bb-rl-activity-video-elem' ).removeClass( 'is-visible' );
			$activityVideoElem.find( '.bb_rl_more_dropdown' ).toggleClass( 'open' ).closest( '.activity-item' ).siblings().find( '.bb-rl-activity-video-elem .bb_rl_more_dropdown' ).removeClass( 'open' );
			$body.addClass( 'video_more_option_open' );

			if ( $activityVideoElem.length < 1 ) {
				var $videoThumb = target.closest( '.bb-video-thumb' );
				$videoThumb.toggleClass( 'is-visible' ).parent().siblings().find( '.bb-video-thumb' ).removeClass( 'is-visible' );
				$videoThumb.find( '.bb_rl_more_dropdown' ).toggleClass( 'open' ).closest( '.bb-video-thumb' ).parent().siblings().find( '.bb_rl_more_dropdown' ).removeClass( 'open' );
			}

			if ( target.closest( '.bb-rl-media-model-container' ).length ) {
				target.closest( '.bb-rl-more_dropdown-wrap' ).toggleClass( 'is-visible' ).find( '.bb_rl_more_dropdown' ).toggleClass( 'open' );
			}

			if ( event.currentTarget.tagName.toLowerCase() === 'a' && (
				! target.hasClass( 'bb_rl_more_dropdown__action' )
			) ) {
				$activityVideoElem.removeClass( 'is-visible' ).find( '.bb_rl_more_dropdown' ).removeClass( 'open' );
				target.closest( '.bb-item-thumb' ).removeClass( 'is-visible' );
				$body.removeClass( 'video_more_option_open' );
			}
		},

		toggleSelectAllVideo: function ( event ) {
			event.preventDefault();

			var $target     = $( event.currentTarget ),
				isSelecting = ! $target.hasClass( 'selected' );

			this.setVideoSelectionState( isSelecting );

			if ( $( '#bb-delete-video' ).length ) {
				if ( isSelecting ) {
					$( '#bb-delete-video' ).removeAttr( 'disabled' );
				} else {
					$( '#bb-delete-video' ).attr( 'disabled', 'disabled' );
				}
			}

			$target.toggleClass( 'selected', isSelecting ).data( 'bp-tooltip', isSelecting ? bbRlMedia.i18n_strings.unselectall : bbRlMedia.i18n_strings.selectall );
		},

		setVideoSelectionState: function ( select ) {
			var isSelecting = select === true;

			$( '#buddypress' ).find( '#video-stream li' ).find( '.bb-video-check-wrap [name="bb-video-select"]' ).each(
				function () {
					$( this ).prop( 'checked', isSelecting );
					$( this ).closest( '.bb-item-thumb' ).toggleClass( 'selected', isSelecting );
					$( this ).closest( '.bb-video-check-wrap' ).find( '.bp-tooltip' ).attr( 'data-bp-tooltip', isSelecting ? bbRlMedia.i18n_strings.unselect : bbRlMedia.i18n_strings.select );
				}
			);
		},

		addSelectedClassToWrapper: function ( event ) {
			var target = event.currentTarget;
			if ( $( target ).is( ':checked' ) ) {
				$( target ).closest( '.bb-video-check-wrap' ).find( '.bp-tooltip' ).attr( 'data-bp-tooltip', bbRlMedia.i18n_strings.unselect );
				$( target ).closest( '.bb-item-thumb' ).addClass( 'selected' );
				if ( $( '#bb-delete-video' ).length ) {
					$( '#bb-delete-video' ).removeAttr( 'disabled' );
				}
			} else {
				$( target ).closest( '.bb-item-thumb' ).removeClass( 'selected' );
				$( target ).closest( '.bb-video-check-wrap' ).find( '.bp-tooltip' ).attr( 'data-bp-tooltip', bbRlMedia.i18n_strings.select );

				var selectAllVideo = $( '.bp-nouveau #bb-select-deselect-all-video' );
				if ( selectAllVideo.hasClass( 'selected' ) ) {
					selectAllVideo.removeClass( 'selected' );
				}
				if ( $( '#bb-delete-video' ).length ) {
					$( '#bb-delete-video' ).attr( 'disabled', 'disabled' );
				}
			}
		},

		submitVideo: function ( event ) {
			var self = this, target = $( event.currentTarget ), data, privacy = $( '#bb-video-privacy' ), dir_label;
			event.preventDefault();

			if ( target.hasClass( 'saving' ) ) {
				return false;
			}

			target.addClass( 'saving' );

			if ( self.current_tab === 'bp-video-dropzone-content' ) {
				var post_content  = $( '#bp-video-post-content' ).val(),
					targetPopup   = $( event.currentTarget ).closest( '.open-popup' ),
					selectedAlbum = targetPopup.find( '.bb-rl-album-selected-id' ).val();
				if ( selectedAlbum.length && parseInt( selectedAlbum ) > 0 ) {
					var dropZoneLength = self.dropzone_video.length;
					for ( var i = 0; i < dropZoneLength; i++ ) {
						self.dropzone_video[ i ].album_id = selectedAlbum;
					}
				} else {
					selectedAlbum = self.album_id;
				}

				data = {
					'action': 'video_save',
					'_wpnonce': bbRlNonce.video,
					'videos': self.dropzone_video,
					'content': post_content,
					'album_id': selectedAlbum,
					'group_id': self.video_group_id,
					'privacy': privacy.val()
				};

				$( '#bp-video-dropzone-content .bp-feedback' ).remove();

				$.ajax(
					{
						type: 'POST',
						url: bbRlAjaxUrl,
						data: data,
						success: function ( response ) {
							if ( response.success ) {

								// It's the very first video, let's make sure the container can welcome it!
								var $videoStream = $( '#video-stream ul.video-list' );
								if ( ! $videoStream.length ) {
									location.reload( true );
								}

								if ( $( '#bp-media-single-album' ).length ) {
									// Prepend in Single Album.
									if ( ! $( '#media-stream ul.media-list' ).length ) {
										$( '#media-stream' ).html(
											$( '<ul></ul>' ).
												addClass( 'media-list item-list bp-list bb-photo-list grid' )
										);
										$( '.bb-videos-actions' ).show();
										var $photoActions = $( '.bb-photos-actions' );
										if ( $photoActions.length ) {
											$photoActions.show();
										}
									}
									// Prepend the activity.
									bp.Nouveau.inject( '#media-stream ul.media-list', response.data.video, 'prepend' );
								} else {
									// It's the very first media, let's make sure the container can welcome it!
									if ( ! $videoStream.length ) {
										$( '#video-stream .bb-rl-media-none' ).remove();
										$( '#video-stream' ).append( '<ul class="video-list item-list bp-list bb-video-list grid"></ul>' );
										$( '.bb-videos-actions' ).show();
									}
									// Prepend the activity.
									bp.Nouveau.inject( '#video-stream ul.video-list', response.data.video, 'prepend' );
								}

								var $buddypress = $( '#buddypress' );
								if ( response.data.video_personal_count ) {
									var spanCountTag = $buddypress.find( '.bp-wrap .users-nav ul li#video-personal-li a span.count' );
									if ( spanCountTag.length ) {
										spanCountTag.text( response.data.video_personal_count );
									} else {
										var videoPersonalSpanTag = document.createElement( 'span' );
										videoPersonalSpanTag.setAttribute( 'class', 'count' );
										var videoPersonalSpanTagTextNode = document.createTextNode( response.data.video_personal_count );
										videoPersonalSpanTag.appendChild( videoPersonalSpanTagTextNode );
										$buddypress.find( '.bp-wrap .users-nav ul li#video-personal-li a' ).append( videoPersonalSpanTag );
									}
								}

								if ( response.data.video_group_count ) {
									var groupSpanCountTag = $buddypress.find( '.bp-wrap .groups-nav ul li#videos-groups-li a span.count' );
									if ( $buddypress.find( '.bb-item-count' ).length > 0 && 'yes' !== BP_Nouveau.video.is_video_directory ) {
										dir_label = BP_Nouveau.dir_labels.hasOwnProperty( 'video' ) ?
										(
											1 === parseInt( response.data.video_group_count ) ?
											BP_Nouveau.dir_labels.video.singular : BP_Nouveau.dir_labels.video.plural
										)
										: '';
										$buddypress.find( '.bb-item-count' ).html( '<span class="bb-count">' + response.data.video_group_count + '</span> ' + dir_label );
									} else if ( groupSpanCountTag.length ) {
										groupSpanCountTag.text( response.data.video_group_count );
									} else {
										var videoGroupSpanTag = document.createElement( 'span' );
										videoGroupSpanTag.setAttribute( 'class', 'count' );
										var videoGroupSpanTagTextNode = document.createTextNode( response.data.video_group_count );
										videoGroupSpanTag.appendChild( videoGroupSpanTagTextNode );
										$buddypress.find( '.bp-wrap .groups-nav ul li#videos-groups-li a' ).append( videoGroupSpanTag );
									}
								}

								if ( 'yes' === bbRlVideo.is_video_directory ) {
									$buddypress.find( '.video-type-navs ul.video-nav li#video-all a span.count' ).text( response.data.video_all_count );
									$buddypress.find( '.video-type-navs ul.video-nav li#video-personal a span.count' ).text( response.data.video_personal_count );
									$buddypress.find( '.video-type-navs ul.video-nav li#video-groups a span.count' ).text( response.data.video_group_count );
								}
								var videoLength = self.dropzone_video.length;
								for ( var i = 0; i < videoLength; i++ ) {
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
					'_wpnonce': bbRlNonce.video,
					'medias': selected,
					'album_id': self.video_album_id,
					'group_id': self.video_group_id
				};

				$( '#bp-existing-video-content .bp-feedback' ).remove();

				$.ajax(
					{
						type: 'POST',
						url: bbRlAjaxUrl,
						data: data,
						success: function ( response ) {
							if ( response.success ) {

								// It's the very first media, let's make sure the container can welcome it!
								if ( ! $( '#video-stream ul.media-list' ).length ) {
									$( '#video-stream' ).html( $( '<ul></ul>' ).addClass( 'video-list item-list bp-list bb-video-list grid' ) );
								}

								var videoAction = $( '.bb-video-actions' );
								if ( videoAction.length > 0 ) {
									videoAction.show();
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
			var closest_parent = jQuery( event.currentTarget ).closest( '.bb-rl-has-folderlocationUI' );
			if ( closest_parent.length > 0 ) {
				closest_parent.find( '.bb-rl-location-album-list-wrap-main .bb-rl-location-album-list-wrap .location-album-list li' ).each(
					function () {
						jQuery( this ).removeClass( 'is_active' ).find( 'span.selected:not(.disabled)' ).removeClass( 'selected' );
						jQuery( this ).find( 'ul' ).hide();
					}
				);
				closest_parent.find( '.bb-rl-location-album-list-wrap-main .bb-rl-location-album-list-wrap .location-album-list li' ).show().children( 'span, i' ).show();
				closest_parent.find( '.location-folder-title' ).text( bbRlVideo.target_text );
				closest_parent.find( '.location-folder-back' ).hide().closest( '.bb-rl-has-folderlocationUI' ).find( '.bb-rl-folder-selected-id' ).val( '0' );
				closest_parent.find( '.ac_document_search_folder' ).val( '' );
				closest_parent.find( '.bb-model-header h4 span' ).text( '...' );
				closest_parent.find( '.bb_rl_ac_document_search_folder_list ul' ).html( '' ).parent().hide().siblings( '.bb-rl-location-album-list-wrap' ).find( '.location-album-list' ).show();
			}
		},

		openUploader : function ( event ) {
			var self      = this,
				currentTarget,
				parentsOpen,
				$document = $( document );
			event.preventDefault();

			this.moveToIdPopup   = bbRlVideo.move_to_id_popup;
			this.moveToTypePopup = bbRlVideo.current_type;

			if ( typeof window.Dropzone !== 'undefined' && $( 'div.video-uploader-wrapper #video-uploader' ).length ) {

				var videoUploader = $( '#bp-video-uploader' );
				videoUploader.show();
				if ( videoUploader.find( '.bb-field-steps.bb-field-steps-2' ).length ) {
					currentTarget       = '#bp-video-uploader.bp-video-uploader';
					var albumSelectedId = $( currentTarget ).find( '.bb-rl-album-selected-id' ).val();
					if ( Number( albumSelectedId ) !== 0 ) {
						parentsOpen = albumSelectedId;
						$( currentTarget ).find( '#bb-video-privacy' ).prop( 'disabled', true );
					} else {
						parentsOpen = 0;
					}
					if ( '' !== this.moveToIdPopup ) {
						$.ajax(
							{
								url        : bbRlAjaxUrl,
								type       : 'post',
								data       : {
									action : 'media_get_album_view',
									id     : this.moveToIdPopup,
									type   : this.moveToTypePopup,
								}, success : function ( response ) {
									$document.find( '.bb-rl-location-album-list-wrap h4 span.bb-rl-where-to-move-profile-or-group-media' ).html( response.data.first_span_text );
									if ( '' === response.data.html ) {
										$document.find( '.open-popup .bb-rl-location-album-list-wrap' ).hide();
										$document.find( '.open-popup .bb-rl-location-album-list-wrap-main span.bb-rl-no-album-exists' ).show();
									} else {
										$document.find( '.open-popup .bb-rl-location-album-list-wrap-main span.bb-rl-no-album-exists' ).hide();
										$document.find( '.open-popup .bb-rl-location-album-list-wrap' ).show();
									}
									if ( false === response.data.create_album ) {
										$document.find( '.open-popup .bb-rl-video-open-create-popup-album' ).removeClass( 'create-album' );
									} else {
										$document.find( '.open-popup .bb-rl-video-open-create-popup-album' ).addClass( 'create-album' );
									}
									$document.find( '.bb-rl-popup-on-fly-create-album .bb-rl-privacy-field-wrap-hide-show' ).show();
									$document.find( '.open-popup .bb-rl-album-create-from' ).val( 'profile' );
									$( currentTarget ).find( '.bb-rl-location-album-list-wrap .location-album-list' ).remove();
									$( currentTarget ).find( '.bb-rl-location-album-list-wrap' ).append( response.data.html );
									$( currentTarget ).find( 'ul.location-album-list span[data-id="' + parentsOpen + '"]' ).trigger( 'click' );
									$( currentTarget ).find( '.bb-rl-album-selected-id' ).val( parentsOpen );
								}
							}
						);
					}
				}

				$document.on(
					'click',
					currentTarget + ' .location-album-list li span',
					function ( e ) {
						e.preventDefault();
						var eventCurrentTarget        = $( e.currentTarget ),
							eventCurrentTargetClosest = eventCurrentTarget.closest( '.bb-rl-field-wrap' ),
							$itemSpanLastChild        = $( this ).closest( '.bb-rl-location-album-list-wrap' ).find( '.breadcrumb .item span:last-child' );
						if ( $( this ).parent().hasClass( 'is_active' ) ) {
							return;
						}
						if ( $itemSpanLastChild.data( 'id' ) !== 0 ) {
							$itemSpanLastChild.remove();
						}
						$( this ).closest( '.bb-rl-location-album-list-wrap' ).find( '.breadcrumb .item' ).append( '<span class="is-disabled" data-id="' + $( this ).attr( 'id' ) + '">' + $( this ).text() + '</span>' );
						$( this ).addClass( 'selected' ).parent().addClass( 'is_active' ).siblings().removeClass( 'is_active' ).children( 'span' ).removeClass( 'selected' );
						if ( parentsOpen === $( e.currentTarget ).data( 'id' ) ) {
							eventCurrentTargetClosest.find( '.bb-model-footer .bb-rl-media-move' ).addClass( 'is-disabled' );
						} else {
							eventCurrentTargetClosest.find( '.bb-model-footer .bb-rl-media-move' ).removeClass( 'is-disabled' );
						}
						if ( eventCurrentTargetClosest.find( '.bb-model-footer .bb-rl-media-move' ).hasClass( 'is-disabled' ) ) {
							return; // return if parent album is same.
						}
						eventCurrentTargetClosest.find( '.bb-rl-album-selected-id' ).val( $( e.currentTarget ).data( 'id' ) );
						var mediaPrivacy = eventCurrentTarget.closest( '#bp-video-uploader' ).find( '#bb-video-privacy' );
						if ( Number( eventCurrentTarget.data( 'id' ) ) !== 0 ) {
							mediaPrivacy.find( 'option' ).removeAttr( 'selected' );
							mediaPrivacy.val( eventCurrentTarget.parent().data( 'privacy' ) );
							mediaPrivacy.prop( 'disabled', true );
						} else {
							mediaPrivacy.find( 'option' ).removeAttr( 'selected' );
							mediaPrivacy.val( 'public' );
							mediaPrivacy.prop( 'disabled', false );
						}
					}
				);

				$document.on(
					'click',
					currentTarget + ' .breadcrumb .item > span',
					function ( e ) {
						if ( $( this ).hasClass( 'is-disabled' ) ) {
							return;
						}
						var eventCurrentTarget = $( e.currentTarget ),
							fieldWrapElem      = eventCurrentTarget.closest( '.bb-rl-field-wrap' ),
							spanLastChild      = (
								this
							).closest( '.bb-rl-location-album-list-wrap' ).find( '.breadcrumb .item span:last-child' );
						fieldWrapElem.find( '.bb-rl-album-selected-id' ).val( 0 );
						fieldWrapElem.find( '.location-album-list li span' ).removeClass( 'selected' ).parent().removeClass( 'is_active' );
						if ( spanLastChild.hasClass( 'is-disabled' ) ) {
							spanLastChild.remove();
						}
						if ( parentsOpen === eventCurrentTarget.data( 'id' ) ) {
							fieldWrapElem.find( '.bb-model-footer .bb-rl-media-move' ).addClass( 'is-disabled' );
						} else {
							fieldWrapElem.find( '.bb-model-footer .bb-rl-media-move' ).removeClass( 'is-disabled' );
						}
						var mediaPrivacy         = eventCurrentTarget.closest( '#bp-video-uploader' ).find( '#bb-video-privacy' );
						var selectedAlbumPrivacy = eventCurrentTarget.closest( '#bp-video-uploader' ).find( '.location-album-list li.is_active' ).data( 'privacy' );
						if ( Number( fieldWrapElem.find( '.bb-rl-album-selected-id' ).val() ) !== 0 ) {
							mediaPrivacy.find( 'option' ).removeAttr( 'selected' );
							mediaPrivacy.val( selectedAlbumPrivacy === undefined ? 'public' : selectedAlbumPrivacy );
							mediaPrivacy.prop( 'disabled', true );
						} else {
							mediaPrivacy.find( 'option' ).removeAttr( 'selected' );
							mediaPrivacy.val( 'public' );
							mediaPrivacy.prop( 'disabled', false );
						}
					}
				);

				self.video_dropzone_obj = new Dropzone( 'div.video-uploader-wrapper #video-uploader', self.videoOptions );

				self.video_dropzone_obj.on(
					'sending',
					function ( file, xhr, formData ) {
						formData.append( 'action', 'video_upload' );
						formData.append( '_wpnonce', bbRlNonce.video );
					}
				);

				self.video_dropzone_obj.on(
					'addedfile',
					function ( file ) {
						setTimeout(
							function () {
								if ( self.video_dropzone_obj.getAcceptedFiles().length ) {
									$( '#bp-video-uploader-modal-status-text' ).text( wp.i18n.sprintf( bbRlVideo.i18n_strings.upload_status, self.dropzone_video.length, self.video_dropzone_obj.getAcceptedFiles().length ) ).show();
								}
							},
							1000
						);

						if ( file.dataURL ) {
							// Get Thumbnail image from response.
						} else {

							if ( bp.Nouveau.getVideoThumb ) {
								bp.Nouveau.getVideoThumb( file, '.dz-video-thumbnail' );
							}

						}

					}
				);

				self.video_dropzone_obj.on(
					'error',
					function ( file, response ) {
						if ( file.accepted ) {
							if ( typeof response !== 'undefined' && typeof response.data !== 'undefined' && typeof response.data.feedback !== 'undefined' ) {
								$( file.previewElement ).find( '.dz-error-message span' ).text( response.data.feedback );
							} else if ( file.status === 'error' && ( file.xhr && file.xhr.status === 0 ) ) { // update server error text to user friendly.
								$( file.previewElement ).find( '.dz-error-message span' ).text( bbRlMedia.connection_lost_error );
							}
						} else {
							if ( ! jQuery( '.media-error-popup' ).length ) {
								$( 'body' ).append( '<div id="bp-media-create-folder" style="display: block;" class="open-popup media-error-popup"><transition name="modal"><div class="bb-rl-modal-mask bb-white bbm-model-wrap"><div class="bb-rl-modal-wrapper"><div id="bb-rl-media-create-album-popup" class="modal-container bb-rl-has-folderlocationUI"><header class="bb-model-header"><h4>' + bbRlVideo.invalid_video_type + '</h4><a class="bb-model-close-button errorPopup" href="#"><span class="dashicons dashicons-no-alt"></span></a></header><div class="bb-rl-field-wrap"><p>' + response + '</p></div></div></div></div></transition></div>' );
							}
							this.removeFile( file );
						}
					}
				);

				self.video_dropzone_obj.on(
					'queuecomplete',
					function () {
						$( '#bp-video-uploader-modal-title' ).text( bbRlVideo.i18n_strings.upload );
					}
				);

				self.video_dropzone_obj.on(
					'processing',
					function () {
						$( '#bp-video-uploader-modal-title' ).text( bbRlVideo.i18n_strings.uploading + '...' );
					}
				);

				self.video_dropzone_obj.on(
					'uploadprogress',
					function ( element ) {

						var circle        = $( element.previewElement ).find( '.dz-progress-ring circle' )[ 0 ];
						var radius        = circle.r.baseVal.value;
						var circumference = radius * 2 * Math.PI;

						circle.style.strokeDasharray = circumference + ' ' + circumference;
						var offset                   = circumference - element.upload.progress.toFixed( 0 ) / 100 * circumference;
						if ( element.upload.progress <= 99 ) {
							$( element.previewElement ).find( '.dz-progress-count' ).text( element.upload.progress.toFixed( 0 ) + '% ' + bbRlVideo.i18n_strings.video_uploaded_text );
							circle.style.strokeDashoffset = offset;
						} else if ( element.upload.progress === 100 ) {
							circle.style.strokeDashoffset = circumference - 0.99 * circumference;
							$( element.previewElement ).find( '.dz-progress-count' ).text( '99% ' + bbRlVideo.i18n_strings.video_uploaded_text );
						}
					}
				);

				self.video_dropzone_obj.on(
					'success',
					function ( file, response ) {

						if ( file.upload.progress === 100 ) {
							$( file.previewElement ).find( '.dz-progress-ring circle' )[ 0 ].style.strokeDashoffset = 0;
							$( file.previewElement ).find( '.dz-progress-count' ).text( '100% ' + bbRlVideo.i18n_strings.video_uploaded_text );
							$( file.previewElement ).closest( '.dz-preview' ).addClass( 'dz-complete' );
						}

						if ( response.data.id ) {
							file.id                  = response.id;
							response.data.uuid       = file.upload.uuid;
							response.data.menu_order = self.dropzone_video.length;
							response.data.album_id   = self.video_album_id;
							response.data.group_id   = self.video_group_id;
							response.data.js_preview = $( file.previewElement ).find( '.dz-video-thumbnail img' ).attr( 'src' );
							response.data.saved      = false;
							self.dropzone_video.push( response.data );
						} else {
							if ( ! jQuery( '.media-error-popup' ).length ) {
								$( 'body' ).append( '<div id="bp-video-create-folder" style="display: block;" class="open-popup media-error-popup"><transition name="modal"><div class="bb-rl-modal-mask bb-white bbm-model-wrap"><div class="bb-rl-modal-wrapper"><div id="bb-rl-media-create-album-popup" class="modal-container bb-rl-has-folderlocationUI"><header class="bb-model-header"><h4>' + bbRlMedia.invalid_media_type + '</h4><a class="bb-model-close-button errorPopup" href="#"><span class="dashicons dashicons-no-alt"></span></a></header><div class="bb-rl-field-wrap"><p>' + response.data.feedback + '</p></div></div></div></div></transition></div>' );
							}
							this.removeFile( file );
						}
						$( '.bb-field-steps-1 #bp-video-next, #bp-video-submit' ).show();
						$( '.modal-container' ).addClass( 'modal-container--alert' );
						$( '.bb-field-steps-1' ).addClass( 'controls-added' );
						$( '#bp-video-submit' ).show();
						$( '#bp-video-uploader-modal-title' ).text( bbRlVideo.i18n_strings.uploading + '...' );
						$( '#bp-video-uploader-modal-status-text' ).text( wp.i18n.sprintf( bbRlVideo.i18n_strings.upload_status, self.dropzone_video.length, self.video_dropzone_obj.getAcceptedFiles().length ) ).show();
					}
				);

				self.video_dropzone_obj.on(
					'removedfile',
					function ( file ) {

						if ( self.dropzone_video.length ) {
							for ( var i in self.dropzone_video ) {
								if ( file.upload.uuid === self.dropzone_video[ i ].uuid ) {

									if ( typeof self.dropzone_video[ i ].saved !== 'undefined' && ! self.dropzone_video[ i ].saved ) {
										self.removeVideoAttachment( self.dropzone_video[ i ].id );
									}

									self.dropzone_video.splice( i, 1 );
									break;
								}
							}
						}

						if ( ! self.video_dropzone_obj.getAcceptedFiles().length ) {
							$( '#bp-video-uploader-modal-status-text' ).text( '' );
							$( '#bp-video-next' ).hide();
							$( '.bb-field-steps-1' ).removeClass( 'controls-added' );
							$( '#bp-video-submit' ).hide();
							$( '.modal-container' ).removeClass( 'modal-container--alert' );
						} else {
							$( '#bp-video-uploader-modal-status-text' ).text( wp.i18n.sprintf( bbRlVideo.i18n_strings.upload_status, self.dropzone_video.length, self.video_dropzone_obj.getAcceptedFiles().length ) ).show();
						}
					}
				);

			}
		},

		openEditThumbnailUploader : function ( event ) {
			var self = this;
			event.preventDefault();

			var $document         = $( document ),
				target            = $( event.currentTarget ),
				parentActivityId  = target.attr( 'data-parent-activity-id' ),
				videoAttachmentId = target.attr( 'data-video-attachment-id' ),
				videoAttachments  = target.attr( 'data-video-attachments' ),
				videoId           = target.attr( 'data-video-id' ),
				popupSelector     = target.closest( '.bb-rl-activity-inner, #video-stream.video, #media-stream.media, .forums-video-wrap, .comment-item' );

			if ( ! popupSelector.length ) {
				var singleAlbum = $( '#bp-media-single-album' );
				if ( singleAlbum.length > 0 ) {
					popupSelector = singleAlbum.find( '#media-stream' ).parent();
				} else if ( target.closest( '#bb-rl-media-model-container' ).length ) {
					if ( target.closest( '.bb-video-container.bb-media-container.group-video' ).length > 0 ) {
						popupSelector = target.closest( '.bb-video-container.bb-media-container.group-video' );
					} else if ( target.closest( '.bb-rl-media-model-wrapper.bb-rl-video-theatre' ).siblings( '#video-stream' ).length > 0 ) {
						popupSelector = target.closest( '.bb-rl-media-model-wrapper.bb-rl-video-theatre' ).parent();
					} else {
						popupSelector = $( 'ul.bb-rl-activity-list li#activity-' + parentActivityId ).find( '.bb-rl-activity-inner' );
					}
				}
			}

			var uploader = popupSelector.find( '.bb-rl-video-thumbnail-uploader' );
			uploader.addClass( 'opened-edit-thumbnail' ).show().removeClass( 'no_generated_thumb' );

			$document.on(
				'click',
				'.bb-rl-video-thumbnail-uploader.opened-edit-thumbnail .bb-rl-video-thumbnail-auto-generated .bb-action-check-wrap',
				function () {
					$( this ).closest( '.bb-rl-video-thumbnail-uploader' ).find( '.bb-rl-video-thumbnail-submit' ).removeClass( 'is-disabled' );
				}
			);

			$document.on(
				'click',
				'.bb-rl-video-thumbnail-uploader.opened-edit-thumbnail:not(.generating_thumb) .bb-rl-video-thumb-list li',
				function ( e ) {
					e.preventDefault();
					var input = $( this ).find( 'input.bb-rl-custom-check' );
					if ( false === input.prop( 'checked' ) ) {
						input.prop( 'checked', true );
						$( this ).closest( '.bb-rl-video-thumbnail-uploader' ).find( '.bb-rl-video-thumbnail-submit' ).removeClass( 'is-disabled' );
					}
				}
			);

			$document.on(
				'click',
				'.bb-rl-video-thumbnail-uploader.opened-edit-thumbnail .bb-rl-video-thumbnail-dropzone-content',
				function ( e ) {
					var $this   = $( this ),
						$target = $( e.target );
					if ( $target.hasClass( 'bb-rl-custom-check' ) || $target.hasClass( 'bb-icon-l' ) || $target.hasClass( 'dz-remove' ) || $target.hasClass( 'dz-clickable' ) || $target.hasClass( 'bb-rl-close-thumbnail-custom' ) ) {
						return;
					}

					if ( $this.find( '.bb-rl-video-thumbnail-custom' ).hasClass( 'is_hidden' ) ) {
						return;
					}

					if ( ! $this.find( 'input.bb-rl-custom-check' ).prop( 'checked' ) && $target.closest( '.bb-rl-video-thumbnail-dropzone-content' ).hasClass( 'has_image' ) ) {
						$this.find( 'input.bb-rl-custom-check' ).prop( 'checked', true );
						$this.closest( '.bb-rl-video-thumbnail-uploader' ).find( '.bb-rl-video-thumbnail-submit' ).removeClass( 'is-disabled' );
					}

				}
			);

			$document.on(
				'click',
				'.bb-rl-video-thumbnail-uploader.opened-edit-thumbnail .bb-rl-video-thumbnail-custom .bb-rl-close-thumbnail-custom',
				function () {
					var $this           = $( this );
					var customThumbnail = $this.closest( '.bb-rl-video-thumbnail-custom' );
					$this.siblings( 'img' ).attr( 'src', '' ).parent().hide();
					customThumbnail.closest( '.bb-rl-video-thumbnail-content' ).find( '.bb-rl-video-thumbnail-uploader-wrapper' ).show();
					if ( customThumbnail.siblings( '.bb-action-check-wrap' ).find( 'input' ).prop( 'checked' ) ) {
						customThumbnail.siblings( '.bb-action-check-wrap' ).find( 'input' ).prop( 'checked', false );
						$this.closest( '.bb-rl-video-thumbnail-dropzone-content' ).find( '.bb-rl-video-thumbnail-submit' ).addClass( 'is-disabled' );
					}
					customThumbnail.addClass( 'is_hidden' );
					$this.closest( '.bb-rl-video-thumbnail-dropzone-content' ).removeClass( 'has_image' );
				}
			);

			if ( typeof window.Dropzone !== 'undefined' && $( 'div.bb-rl-video-thumbnail-uploader.opened-edit-thumbnail div.bb-rl-video-thumbnail-uploader-wrapper .bb-rl-video-thumbnail-uploader-dropzone-select' ).length ) {
				var uploaderSelector          = '.bb-rl-video-thumbnail-uploader.opened-edit-thumbnail',
					$videoThumbnailUploadeEle = $( '.bb-rl-video-thumbnail-uploader' );
				$( uploaderSelector + ' .bb-rl-video-edit-thumbnail-hidden-video-id' ).val( videoId );
				$( uploaderSelector + ' .bb-rl-video-edit-thumbnail-hidden-attachment-id' ).val( videoAttachmentId );

				// Check to avoid error if Node is missing.
				self.videoThumbnailOptions.previewTemplate = document.getElementsByClassName( 'bb-rl-uploader-post-video-thumbnail-template' ).length ? document.getElementsByClassName( 'bb-rl-uploader-post-video-thumbnail-template' )[ 0 ].innerHTML : '';
				self.videoThumbnailOptions.thumbnailMethod = 'contain';
				self.videoThumbnailOptions.thumbnailWidth  = null;
				self.videoThumbnailOptions.thumbnailHeight = '300';

				self.video_thumb_dropzone_obj = new Dropzone( uploaderSelector + ' .bb-rl-video-thumbnail-uploader-dropzone-select', self.videoThumbnailOptions );

				self.video_thumb_dropzone_obj.on(
					'sending',
					function ( file, xhr, formData ) {
						formData.append( 'action', 'video_thumbnail_upload' );
						formData.append( '_wpnonce', bbRlNonce.video );
					}
				);

				self.video_thumb_dropzone_obj.on(
					'addedfile',
					function ( file ) {
						if ( file.video_thumbnail_edit_data ) {
							self.dropzone_video_thumb.push( file.video_thumbnail_edit_data );
							$( uploaderSelector + ' .bb-rl-video-thumbnail-uploader-wrapper .dropzone.dz-clickable' ).addClass( 'dz-max-files-reached' );
							$( uploaderSelector + ' .bb-rl-video-thumbnail-dropzone-content .bb-action-check-wrap' ).show();
						} else {
							setTimeout(
								function () {
									if ( self.video_thumb_dropzone_obj.getAcceptedFiles().length ) {
										$( uploaderSelector + ' .bb-rl-video-thumbnail-uploader-modal-status-text' ).text( wp.i18n.sprintf( bbRlVideo.i18n_strings.upload_status, self.dropzone_video_thumb.length, self.video_thumb_dropzone_obj.getAcceptedFiles().length ) ).show();
									}
								},
								1000
							);
						}
					}
				);

				self.video_thumb_dropzone_obj.on(
					'uploadprogress',
					function ( element ) {
						var circle                    = $( element.previewElement ).find( '.dz-progress-ring circle' )[ 0 ],
							radius                    = circle.r.baseVal.value,
							circumference             = radius * 2 * Math.PI;
						circle.style.strokeDasharray  = circumference + ' ' + circumference;
						circle.style.strokeDashoffset = circumference;
						circle.style.strokeDashoffset = circumference - element.upload.progress.toFixed( 0 ) / 100 * circumference;
					}
				);

				self.video_thumb_dropzone_obj.on(
					'error',
					function ( file, response ) {
						if ( file.accepted ) {
							if ( typeof response !== 'undefined' && typeof response.data !== 'undefined' && typeof response.data.feedback !== 'undefined' ) {
								$( file.previewElement ).find( '.dz-error-message span' ).text( response.data.feedback );
							} else if ( file.status === 'error' && (
										file.xhr && file.xhr.status === 0
							) ) { // update server error text to user friendly.
								$( file.previewElement ).find( '.dz-error-message span' ).text( bbRlMedia.connection_lost_error );
							}
						} else {
							if ( ! jQuery( '.media-error-popup' ).length ) {
								$( 'body' ).append( '<div id="bb-rl-video-move-popup" style="display: block;" class="open-popup video-error-popup"><transition name="modal"><div class="bb-rl-modal-mask bb-white bbm-model-wrap"><div class="bb-rl-modal-wrapper"><div id="bb-rl-media-create-album-popup" class="modal-container bb-rl-has-folderlocationUI"><header class="bb-model-header"><h4>' + bbRlMedia.invalid_media_type + '</h4><a class="bb-model-close-button errorPopup" href="#"><span class="dashicons dashicons-no-alt"></span></a></header><div class="bb-rl-field-wrap"><p>' + response + '</p></div></div></div></div></transition></div>' );
							}
							this.removeFile( file );
							$( uploaderSelector + ' .bb-rl-video-thumbnail-dropzone-content .bb-action-check-wrap' ).hide();
						}
					}
				);

				self.video_thumb_dropzone_obj.on(
					'queuecomplete',
					function () {
						$( uploaderSelector + ' .bb-rl-video-thumbnail-uploader-modal-title' ).text( bbRlVideo.i18n_strings.upload_thumb );
					}
				);

				self.video_thumb_dropzone_obj.on(
					'success',
					function ( file, response ) {
						if ( response.data.id ) {
							file.id                  = response.id;
							response.data.uuid       = file.upload.uuid;
							response.data.menu_order = self.dropzone_video.length;
							response.data.album_id   = self.video_album_id;
							response.data.group_id   = self.video_group_id;
							response.data.saved      = false;
							response.data.js_preview = $( file.previewElement ).find( '.dz-video-thumbnail img' ).attr( 'src' );
							self.dropzone_video_thumb.push( response.data );
						} else {
							if ( ! jQuery( '.media-error-popup' ).length ) {
								$( 'body' ).append( '<div id="bb-rl-video-move-popup" style="display: block;" class="open-popup media-error-popup"><transition name="modal"><div class="bb-rl-modal-mask bb-white bbm-model-wrap"><div class="bb-rl-modal-wrapper"><div id="bb-rl-media-create-album-popup" class="modal-container bb-rl-has-folderlocationUI"><header class="bb-model-header"><h4>' + bbRlMedia.invalid_media_type + '</h4><a class="bb-model-close-button errorPopup" href="#"><span class="dashicons dashicons-no-alt"></span></a></header><div class="bb-rl-field-wrap"><p>' + response + '</p></div></div></div></div></transition></div>' );
							}
							this.removeFile( file );
						}
						$( uploaderSelector + ' .bb-rl-video-thumbnail-submit' ).removeClass( 'is-disabled' );
						$( uploaderSelector + ' .bb-rl-video-thumbnail-uploader-modal-title' ).text( bbRlVideo.i18n_strings.uploading + '...' );
						$( uploaderSelector + ' .bb-rl-video-thumbnail-uploader-modal-status-text' ).text( wp.i18n.sprintf( bbRlVideo.i18n_strings.upload_status, self.dropzone_video_thumb.length, self.video_thumb_dropzone_obj.getAcceptedFiles().length ) );
						$( uploaderSelector + ' .bb-rl-video-thumbnail-dropzone-content' ).addClass( 'has_image' );
						$( uploaderSelector + ' .bb-rl-video-thumbnail-dropzone-content .bb-rl-custom-check' ).prop( 'checked', true );
						$( uploaderSelector + ' .bb-rl-video-thumbnail-dropzone-content .bb-rl-video-thumbnail-custom.is_hidden' ).removeClass( 'is_hidden' );
					}
				);

				self.video_thumb_dropzone_obj.on(
					'removedfile',
					function ( file ) {
						if ( self.dropzone_video_thumb.length ) {
							for ( var i in self.dropzone_video_thumb ) {
								if ( file.upload.uuid === self.dropzone_video_thumb[ i ].uuid ) {
									if ( typeof self.dropzone_video_thumb[ i ].saved !== 'undefined' && ! self.dropzone_video_thumb[ i ].saved ) {
										self.removeVideoThumbnailAttachment( self.dropzone_video_thumb[ i ].id );
									}
									self.dropzone_video_thumb.splice( i, 1 );
									break;
								}
							}
						}
						if ( ! self.video_thumb_dropzone_obj.getAcceptedFiles().length ) {
							$( uploaderSelector + ' .bb-rl-video-thumbnail-uploader-modal-status-text' ).text( '' );
							$( uploaderSelector + ' .bb-rl-video-thumbnail-submit' ).addClass( 'is-disabled' );
							if ( $( '.bb-rl-video-thumbnail-dropzone-content .bb-action-check-wrap input:checked' ) ) {
								$( uploaderSelector + ' .bb-rl-video-thumbnail-dropzone-content .bb-action-check-wrap input' ).prop( 'checked', false );
								$( uploaderSelector + ' .bb-rl-video-thumbnail-dropzone-content' ).removeClass( 'has_image' );
								$( uploaderSelector + ' .bb-rl-video-thumbnail-dropzone-content .bb-action-check-wrap' ).hide();
								$( uploaderSelector + ' .bb-rl-video-thumbnail-dropzone-content .bb-rl-video-thumbnail-custom img' ).attr( 'src', '' ).parent().hide();
							}
						} else {
							$( uploaderSelector + ' .bb-rl-video-thumbnail-uploader-modal-status-text' ).text( wp.i18n.sprintf( bbRlVideo.i18n_strings.upload_status, self.dropzone_video_thumb.length, self.video_thumb_dropzone_obj.getAcceptedFiles().length ) ).show();
						}
					}
				);

				if ( typeof videoAttachments !== 'undefined' ) {
					var default_images_html = '';
					videoAttachments        = JSON.parse( videoAttachments );
					if ( typeof videoAttachments.default_images !== 'undefined' ) {
						$.each(
							videoAttachments.default_images,
							function ( key, value ) {
								var checked_str = '';
								if ( typeof videoAttachments.selected_id !== 'undefined' ) {
									if ( typeof videoAttachments.selected_id.id !== 'undefined' && value.id === videoAttachments.selected_id.id ) {
										checked_str = 'checked="checked"';
									}
								}
								if ( value.url !== undefined ) {
									default_images_html += '<li class="lg-grid-1-5 md-grid-1-3 sm-grid-1-3">';
									default_images_html += '<div class="">';
									default_images_html += '<input ' + checked_str + ' id="bb-video-' + value.id + '" class="bb-rl-custom-check" type="radio" value="' + value.id + '" name="bb-video-thumbnail-select" />';
									default_images_html += '<label class="bp-tooltip" data-bp-tooltip-pos="up" data-bp-tooltip="Select" for="bb-video-' + value.id + '"><span class="bb-icon-l bb-icon-check"></span></label>';
									default_images_html += '<a class="" href="#">';
									default_images_html += '<img src="' + value.url + '" class="" alt=""/>';
									default_images_html += '</a>';
									default_images_html += '</div>';
									default_images_html += '</li>';
								}
							}
						);
						if ( default_images_html !== '' ) {
							$( uploaderSelector + ' .bb-rl-video-thumbnail-auto-generated ul.bb-rl-video-thumb-list' ).removeClass( 'loading' );
							$( uploaderSelector + ' .bb-rl-video-thumbnail-auto-generated ul.bb-rl-video-thumb-list' ).html( default_images_html );
							$videoThumbnailUploadeEle.removeClass( 'generating_thumb' );
							if ( videoAttachments.default_images.length < 2 && bbRlVideo.is_ffpmeg_installed ) {
								$videoThumbnailUploadeEle.addClass( 'generating_thumb' );
								$( uploaderSelector + ' .bb-rl-video-thumbnail-auto-generated ul.bb-rl-video-thumb-list' ).append( '<li class="lg-grid-1-5 md-grid-1-3 sm-grid-1-3 bb_rl_thumb_loader"><div class="bb-rl-video-thumb-block"><i class="bb-icon-spinner bb-icon-l animate-spin"></i><span>' + bbRlVideo.generating_thumb + '</span></div></li>' );
								$( uploaderSelector + ' .bb-rl-video-thumbnail-auto-generated ul.bb-rl-video-thumb-list' ).append( '<li class="lg-grid-1-5 md-grid-1-3 sm-grid-1-3 bb_rl_thumb_loader"><div class="bb-rl-video-thumb-block"><i class="bb-icon-spinner bb-icon-l animate-spin"></i><span>' + bbRlVideo.generating_thumb + '</span></div></li>' );
							}
						}
					} else {
						$( popupSelector ).find( '.bb-rl-video-thumbnail-uploader' ).addClass( 'no_generated_thumb' );
					}

					if ( typeof videoAttachments.preview !== 'undefined' ) {
						if ( typeof videoAttachments.preview.dropzone !== 'undefined' && videoAttachments.preview.dropzone === true ) {
							$( uploaderSelector + ' .bb-rl-video-thumbnail-custom' ).show();
							$( uploaderSelector + ' .bb-rl-video-thumbnail-custom img' ).attr( 'src', videoAttachments.preview.url );
							$( uploaderSelector + ' .bb-rl-video-thumbnail-dropzone-content' ).addClass( 'has_image' );
							$( uploaderSelector + ' .bb-rl-video-thumbnail-dropzone-content' ).find( 'input' ).prop( 'checked', true );
							$( uploaderSelector + ' .bb-rl-video-thumbnail-uploader-wrapper' ).hide();
							var customImageEle = $( '#bb_rl_custom_image_ele' );
							customImageEle.find( 'input' ).attr( 'id', 'bb-video-' + videoAttachments.preview.attachment_id );
							customImageEle.find( 'label' ).attr( 'for', 'bb-video-' + videoAttachments.preview.attachment_id );
							customImageEle.find( 'input' ).val( videoAttachments.preview.attachment_id );
							if ( typeof videoAttachments.selected_id !== 'undefined' && videoAttachments.preview.attachment_id === videoAttachments.selected_id.id ) {
								customImageEle.find( 'input' ).attr( 'checked', 'checked' );
							}
						}
					}

					if ( typeof videoAttachments.selected_id !== 'undefined' ) {
						$( popupSelector ).find( '.bb-rl-video-thumbnail-uploader #bb-video-' + videoAttachments.selected_id.id ).prop( 'checked', true );
					}
				}

				var data = {
					'action'        : 'video_get_edit_thumbnail_data',
					'_wpnonce'      : bbRlNonce.video,
					'attachment_id' : videoAttachmentId,
					'video_id'      : videoId,
				};

				if ( bbRlVideo.is_ffpmeg_installed && (
					(
						typeof videoAttachments.default_images === 'undefined'
					) || videoAttachments.default_images.length < 2
				) ) {
					if ( this.thumbnail_xhr ) {
						this.thumbnail_xhr.abort();
					}
					$videoThumbnailUploadeEle.addClass( 'generating_thumb' ).removeClass( 'no_generated_thumb' );
					this.getEditVideoThumbnail( data );
					this.thumbnail_interval = setInterval( bp.Nouveau.Video.getEditVideoThumbnail, 6000, data );
				} else {
					$( uploaderSelector + ' .bb-rl-video-thumbnail-auto-generated ul.bb-rl-video-thumb-list' ).removeClass( 'loading' );
					$videoThumbnailUploadeEle.removeClass( 'generating_thumb' );
				}
			}
		},

		getEditVideoThumbnail: function ( data ) {
			// Check if a max interval exceeds then stop ajax request.
			if ( 0 === bp.Nouveau.Video.thumbnail_max_interval ) {
				clearTimeout( bp.Nouveau.Video.thumbnail_interval );
			}

			bp.Nouveau.Video.thumbnail_xhr = $.ajax(
				{
					type    : 'POST',
					url     : bbRlAjaxUrl,
					data    : data,
					cache   : false,
					success : function ( response ) {
						if ( response.success ) {

							bp.Nouveau.Video.thumbnail_max_interval--;

							// Check if thumbnail is generated then stop ajax request.
							if ( 'yes' === response.data.ffmpeg_generated ) {
								clearTimeout( bp.Nouveau.Video.thumbnail_interval );
							}

							var $thumbnailUploader = $( '.bb-rl-video-thumbnail-uploader.opened-edit-thumbnail' );
							var ulSelector         = $thumbnailUploader.find( '.bb-rl-video-thumbnail-auto-generated ul.bb-rl-video-thumb-list' );
							if ( response.data.default_images ) {
								ulSelector.html( '' );
								ulSelector.html( response.data.default_images );
							}

							if ( response.data.ffmpeg_generated && 'no' === response.data.ffmpeg_generated ) {
								ulSelector.html( '' );
							}

							var $thumbItems  = ulSelector.find( 'li' );
							var $loaderItems = ulSelector.find( 'li.bb_rl_thumb_loader' );
							if ( $thumbItems.find( 'li' ).length < 2 ) {
								$thumbnailUploader.addClass( 'generating_thumb' ).removeClass( 'no_generated_thumb' );
								if ( $loaderItems.length === 0 ) {
									ulSelector.append( '<li class="lg-grid-1-5 md-grid-1-3 sm-grid-1-3 bb_rl_thumb_loader"><div class="bb-rl-video-thumb-block"><i class="bb-icon-l bb-icon-spinner animate-spin"></i><span>' + bbRlVideo.generating_thumb + '</span></div></li>' );
									ulSelector.append( '<li class="lg-grid-1-5 md-grid-1-3 sm-grid-1-3 bb_rl_thumb_loader"><div class="bb-rl-video-thumb-block"><i class="bb-icon-l bb-icon-spinner animate-spin"></i><span>' + bbRlVideo.generating_thumb + '</span></div></li>' );
								}
							} else if ( $loaderItems.length === 0 ) {
								$thumbnailUploader.removeClass( 'generating_thumb no_generated_thumb' );
							}
						} else {
							// If found any error from the response then stop ajax request.
							clearTimeout( bp.Nouveau.Video.thumbnail_interval );
						}
					},
					error   : function () {
						// If found any error from server then stop ajax request.
						clearTimeout( bp.Nouveau.Video.thumbnail_interval );
					}
				}
			);
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

			var $uploader = $( '.bb-rl-video-thumbnail-uploader.opened-edit-thumbnail' );

			$uploader.find( '.bb-rl-video-thumbnail-uploader-modal-title' ).text( bbRlVideo.i18n_strings.upload_thumb );
			$( '.bb-rl-video-thumbnail-uploader-modal-status-text' ).text( '' );
			$( '.bb-rl-video-thumbnail-uploader-wrapper .bb-rl-video-thumbnail-uploader-dropzone-select' ).html( '' );
			if ( this.video_thumb_dropzone_obj ) {
				this.video_thumb_dropzone_obj.destroy();
				this.dropzone_video_thumb = [];
			}
			$uploader.hide().removeClass( 'opened-edit-thumbnail' );
			var $globalUploader = $( '.bb-rl-video-thumbnail-uploader' );
			$globalUploader.find( '.bb-rl-video-thumbnail-submit' ).addClass( 'is-disabled' );
			$( window ).scroll();

			// If close popup then stop ajax request.
			clearTimeout( bp.Nouveau.Video.thumbnail_interval );
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
						formData.append( '_wpnonce', bbRlNonce.video );
					}
				);

				self.video_dropzone_obj.on(
					'addedfile',
					function () {
						setTimeout(
							function () {
								if ( self.video_dropzone_obj.getAcceptedFiles().length ) {
									$( '#bp-video-uploader-modal-status-text' ).text( wp.i18n.sprintf( bbRlVideo.i18n_strings.upload_status, self.dropzone_video.length, self.video_dropzone_obj.getAcceptedFiles().length ) ).show();
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
							} else if ( file.status === 'error' && ( file.xhr && file.xhr.status === 0) ) { // update server error text to user friendly.
								$( file.previewElement ).find( '.dz-error-message span' ).text( bbRlMedia.connection_lost_error );
							}
						} else {
							if ( ! jQuery( '.media-error-popup' ).length ) {
								$( 'body' ).append( '<div id="bb-rl-video-move-popup" style="display: block;" class="open-popup media-error-popup"><transition name="modal"><div class="bb-rl-modal-mask bb-white bbm-model-wrap"><div class="bb-rl-modal-wrapper"><div id="bb-rl-media-create-album-popup" class="modal-container bb-rl-has-folderlocationUI"><header class="bb-model-header"><h4>' + bbRlMedia.invalid_media_type + '</h4><a class="bb-model-close-button errorPopup" href="#"><span class="dashicons dashicons-no-alt"></span></a></header><div class="bb-rl-field-wrap"><p>' + response + '</p></div></div></div></div></transition></div>' );
							}
							this.removeFile( file );
						}
					}
				);

				self.video_dropzone_obj.on(
					'queuecomplete',
					function () {
						$( '#bp-video-uploader-modal-title' ).text( bbRlVideo.i18n_strings.upload );
					}
				);

				self.video_dropzone_obj.on(
					'processing',
					function () {
						$( '#bp-video-uploader-modal-title' ).text( bbRlVideo.i18n_strings.uploading + '...' );
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
							response.data.js_preview = $( file.previewElement ).find( '.dz-video-thumbnail img' ).attr( 'src' );
							self.dropzone_video.push( response.data );
						} else {
							if ( ! jQuery( '.media-error-popup' ).length ) {
								$( 'body' ).append( '<div id="bb-rl-video-move-popup" style="display: block;" class="open-popup media-error-popup"><transition name="modal"><div class="bb-rl-modal-mask bb-white bbm-model-wrap"><div class="bb-rl-modal-wrapper"><div id="bb-rl-media-create-album-popup" class="modal-container bb-rl-has-folderlocationUI"><header class="bb-model-header"><h4>' + bbRlMedia.invalid_media_type + '</h4><a class="bb-model-close-button errorPopup" href="#"><span class="dashicons dashicons-no-alt"></span></a></header><div class="bb-rl-field-wrap"><p>' + response + '</p></div></div></div></div></transition></div>' );
							}
							this.removeFile( file );
						}
						$( '#bp-video-submit' ).show();
						$( '#bp-video-uploader-modal-title' ).text( bbRlVideo.i18n_strings.uploading + '...' );
						$( '#bp-video-uploader-modal-status-text' ).text( wp.i18n.sprintf( bbRlVideo.i18n_strings.upload_status, self.dropzone_video.length, self.video_dropzone_obj.getAcceptedFiles().length ) ).show();
					}
				);

				self.video_dropzone_obj.on(
					'removedfile',
					function ( file ) {
						if ( self.dropzone_video.length ) {
							for ( var i in self.dropzone_video ) {
								if ( file.upload.uuid === self.dropzone_video[ i ].uuid ) {
									if ( typeof self.dropzone_video[ i ].saved !== 'undefined' && ! self.dropzone_video[ i ].saved ) {
										self.removeVideoAttachment( self.dropzone_video[ i ].id );
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
							$( '#bp-video-uploader-modal-status-text' ).text( wp.i18n.sprintf( bbRlVideo.i18n_strings.upload_status, self.dropzone_video.length, self.video_dropzone_obj.getAcceptedFiles().length ) ).show();
						}
					}
				);
			}
		},

		removeVideoAttachment: function ( id ) {
			this.removeVideoAndThumbnailAttachment( id, 'media', bbRlNonce.media );
		},

		removeVideoThumbnailAttachment: function ( id ) {
			this.removeVideoAndThumbnailAttachment( id, 'video_thumbnail', bbRlNonce.video );
		},

		closeUploader: function ( event ) {
			event.preventDefault();
			$( '#bp-video-uploader' ).hide();
			$( '#bp-video-uploader-modal-title' ).text( bbRlVideo.i18n_strings.upload );
			$( '#bp-video-uploader-modal-status-text' ).text( '' );
			if ( this.video_dropzone_obj ) {
				this.video_dropzone_obj.destroy();
			}
			this.dropzone_video = [];
			$( '#bp-video-post-content' ).val( '' );
			$( '.bb-rl-close-create-popup-album' ).trigger( 'click' );
			$( '.bb-rl-close-create-popup-folder' ).trigger( 'click' );

			var currentPopup = $( event.target ).closest( '#bp-video-uploader' );
			currentPopup.find( '.bb-rl-breadcrumbs-append-ul-li .item span[data-id="0"]' ).trigger( 'click' );
			if ( currentPopup.find( '.bb-field-steps' ).length ) {
				currentPopup.find( '.bb-field-steps-1' ).show().siblings( '.bb-field-steps-2' ).hide();
				currentPopup.find( '.bb-field-steps-1 #bp-video-next' ).hide();
				currentPopup.find( '.bb-field-steps-1' ).removeClass( 'controls-added' );
				currentPopup.find( '#bp-video-prev, #bp-video-submit, .bb-rl-video-open-create-popup-album, .bb-rl-create-popup-album-wrap' ).hide();
			}
			this.clearFolderLocationUI( event );
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

		deleteVideo : function ( event ) {
			var self = this, target = $( event.currentTarget ), dir_label;
			event.preventDefault();

			var video              = [],
				buddyPressSelector = $( '#buddypress' ),
				type               = target.attr( 'data-type' ),
				fromWhere          = target.data( 'item-from' ),
				rootParentActivity = target.data( 'root-parent-activity-id' ),
				id                 = '',
				activityId         = '';

			if ( 'video' === type ) {
				if ( ! confirm( bbRlVideo.i18n_strings.video_delete_confirm ) ) {
					return false;
				}
			}

			if ( target.hasClass( 'bb-delete' ) ) {
				if ( ! confirm( bbRlVideo.i18n_strings.video_delete_confirm ) ) {
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
			if ( fromWhere && fromWhere.length && 'activity' === fromWhere && video.length === 0 ) {
				id = target.attr( 'data-item-id' );
				video.push( id );
			}

			if ( video.length === 0 ) {
				video.push( target.data( 'item-id' ) );
			}

			if ( 0 === video.length ) {
				return false;
			}

			target.prop( 'disabled', true );
			$( '#buddypress #video-stream.video .bp-feedback' ).remove();

			var data = {
				'action'      : 'video_delete',
				'_wpnonce'    : bbRlNonce.video,
				'video'       : video,
				'activity_id' : activityId,
				'from_where'  : fromWhere,
			};

			$.ajax(
				{
					type    : 'POST',
					url     : bbRlAjaxUrl,
					data    : data,
					success : function ( response ) {
						if ( fromWhere && fromWhere.length && 'activity' === fromWhere ) {
							if ( response.success ) {
								$.each(
									video,
									function ( index, value ) {
										var videoElem = $( '#activity-stream ul.bb-rl-activity-list li.activity .activity-content .activity-inner .bb-activity-video-wrap div[data-id="' + value + '"]' );
										if ( videoElem.length ) {
											videoElem.remove();
										}
										var activityElem = $( 'body .bb-activity-video-elem.' + value );
										if ( activityElem.length ) {
											activityElem.remove();
										}
									}
								);

								var length = $( '#activity-stream ul.bb-rl-activity-list li[data-bp-activity-id="' + activityId + '"] .activity-content .activity-inner .bb-activity-video-elem' ).length;
								if ( length === 0 ) {
									$( '#activity-stream ul.bb-rl-activity-list li[data-bp-activity-id="' + activityId + '"]' ).remove();
								}

								if ( true === response.data.delete_activity ) {
									$( 'body #buddypress .bb-rl-activity-list li#activity-' + activityId ).remove();
									$( 'body .bb-activity-video-elem.video-activity.' + id ).remove();
									$( 'body .activity-comments li#acomment-' + activityId ).remove();

									if ( rootParentActivity && $( '.bb-rl-activity-list' ).length ) {
										var liCount = $( '.bb-rl-activity-list li#activity-' + rootParentActivity + ' .activity-comments > ul > li' ).length;
										if ( 0 === liCount ) {
											$( '.bb-rl-activity-list li#activity-' + rootParentActivity + ' .activity-comments ul' ).remove();
											var act_comments_text = $( '.bb-rl-activity-list li#activity-' + rootParentActivity + ' .activity-state .activity-state-comments .comments-count' );
											if ( act_comments_text.length ) {
												var commentLabelSingle = bbRlActivity.strings.commentLabel;
												act_comments_text.text( commentLabelSingle.replace( '%d', 0 ) );
											}
											$( '.bb-rl-activity-list li#activity-' + rootParentActivity + ' .activity-content .activity-state' ).removeClass( 'has-comments' );
										} else {
											var totalLi         = parseInt( liCount ),
												actCommentsText = $( '.bb-rl-activity-list li#activity-' + rootParentActivity + ' .activity-state .activity-state-comments .comments-count' );
											if ( actCommentsText.length ) {
												var multipleCommentLabel = totalLi > 1 ? bbRlActivity.strings.commentsLabel : bbRlActivity.strings.commentLabel;
												actCommentsText.text( multipleCommentLabel.replace( '%d', totalLi ) );
											}
										}
									}
								} else {
									$( 'body #buddypress .bb-rl-activity-list li#activity-' + activityId ).replaceWith( response.data.activity_content );
								}
							}
						} else if ( fromWhere && fromWhere.length && 'video' === fromWhere ) {
							if ( response.success ) {
								if ( 'yes' === bbRlVideo.is_video_directory ) {
									var store     = bp.Nouveau.getStorage( 'bp-video' ),
										scope     = store.scope,
										$document = $( document );
									if ( scope === 'personal' ) {
										$document.find( '#bb-rl-video-scope-options option[data-bp-scope="personal"]' ).prop( 'selected', true );
										$document.find( '#bb-rl-video-scope-options' ).trigger( 'change' );
									} else if ( scope === 'groups' ) {
										$document.find( '#bb-rl-video-scope-options option[data-bp-scope="groups"]' ).prop( 'selected', true );
										$document.find( '#bb-rl-video-scope-options' ).trigger( 'change' );
									} else {
										$document.find( '#bb-rl-video-scope-options option[data-bp-scope="all"]' ).prop( 'selected', true );
										$document.find( '#bb-rl-video-scope-options' ).trigger( 'change' );
									}
								} else {
									if ( response.data.video_personal_count ) {
										buddyPressSelector.find( '.bp-wrap .users-nav ul li#video-personal-li a span.count' ).text( response.data.video_personal_count );
									}
									if (
										'undefined' !== typeof response.data &&
										'undefined' !== typeof response.data.video_group_count
									) {
										if ( $( '#buddypress .bb-item-count' ).length > 0 && 'yes' !== BP_Nouveau.video.is_video_directory ) {
											dir_label = BP_Nouveau.dir_labels.hasOwnProperty( 'video' ) ?
											(
												1 === parseInt( response.data.video_group_count ) ?
												BP_Nouveau.dir_labels.video.singular : BP_Nouveau.dir_labels.video.plural
											)
											: '';
											$( '#buddypress .bb-item-count' ).html( '<span class="bb-count">' + response.data.video_group_count + '</span> ' + dir_label );
										} else {
											$( '#buddypress' ).find( '.bp-wrap .groups-nav ul li#videos-groups-li a span.count' ).text( response.data.video_group_count );
										}
									}
									if ( 0 !== response.data.video_html_content.length ) {
										if ( 0 === parseInt( response.data.video_personal_count ) ) {
											$( '.bb-videos-actions' ).hide();
											$( '#video-stream' ).html( response.data.video_html_content );
										} else {
											buddyPressSelector.find( '.video-list:not(.existing-video-list)' ).html( response.data.video_html_content );
										}
									} else if ( 0 !== response.data.group_video_html_content.length ) {
										if ( 0 === parseInt( response.data.video_group_count ) ) {
											$( '.bb-videos-actions' ).hide();
											$( '#video-stream' ).html( response.data.group_video_html_content );
										} else {
											buddyPressSelector.find( '.video-list:not(.existing-video-list)' ).html( response.data.group_video_html_content );
										}
									} else {
										$.each(
											video,
											function ( index, value ) {
												var videoElem = $( '#video-stream ul.video-list li[data-id="' + value + '"]' );
												if ( videoElem.length ) {
													videoElem.remove();
												}

												// Remove video from the current album.
												var mediaStream = $( '#media-stream ul.media-list li[data-id="' + value + '"]' );
												if ( self.video_album_id && mediaStream.length ) {
													mediaStream.remove();
												}

											}
										);
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
								if (
									'undefined' !== typeof response.data &&
									'undefined' !== typeof response.data.video_personal_count
								) {
									buddyPressSelector.find( '.bp-wrap .users-nav ul li#video-personal-li a span.count' ).text( response.data.video_personal_count );
								}
								if (
									'undefined' !== typeof response.data &&
									'undefined' !== typeof response.data.video_group_count
								) {
									buddyPressSelector.find( '.bp-wrap .groups-nav ul li#videos-groups-li a span.count' ).text( response.data.video_group_count );
								}
								// inject video.
								if ( 0 !== response.data.video_html_content.length ) {
									if ( 0 === parseInt( response.data.video_personal_count ) ) {
										$( '.bb-videos-actions' ).hide();
										$( '#video-stream' ).html( response.data.video_html_content );
									} else {
										buddyPressSelector.find( '.video-list:not(.existing-video-list)' ).html( response.data.video_html_content );
									}
								} else if ( 0 !== response.data.group_video_html_content.length ) {
									if ( 0 === parseInt( response.data.video_group_count ) ) {
										$( '.bb-videos-actions' ).hide();
										$( '#video-stream' ).html( response.data.group_video_html_content );
									} else {
										buddyPressSelector.find( '.video-list:not(.existing-video-list)' ).html( response.data.group_video_html_content );
									}
								} else {
									buddyPressSelector.find( '.video-list:not(.existing-video-list)' ).find( '.bb-video-check-wrap [name="bb-video-select"]:checked' ).each(
										function () {
											$( this ).closest( 'li' ).remove();
										}
									);
								}
							} else {
								$( '#buddypress #video-stream.video' ).prepend( response.data.feedback );
							}
						}

						var selectAllMedia = $( '.bp-nouveau #bb-select-deselect-all-video' );
						if ( selectAllMedia.hasClass( 'selected' ) ) {
							selectAllMedia.removeClass( 'selected' );
						}

						// replace dummy image with original image by faking scroll event to call bp.Nouveau.lazyLoad.
						jQuery( window ).scroll();

					}
				}
			);

		},

		bp_ajax_video_request: function ( event, data ) {
			if ( 'undefined' !== typeof bbRlVideo.group_id && 'undefined' !== typeof data && 'undefined' !== typeof data.response.scopes.groups && 0 === parseInt( data.response.scopes.groups ) ) {
				$( '.bb-videos-actions' ).hide();
			} else if ( bbRlVideo.group_id && 'undefined' !== typeof data && 'undefined' !== typeof data.response.scopes.groups && 0 !== parseInt( data.response.scopes.groups ) ) {
				$( '.bb-videos-actions' ).show();
			} else if ( typeof data !== 'undefined' && typeof data.response.scopes.personal !== 'undefined' && 0 === parseInt( data.response.scopes.personal ) ) {
				$( '.bb-videos-actions' ).hide();
			} else if ( typeof data !== 'undefined' && typeof data.response.scopes.personal !== 'undefined' && 0 !== parseInt( data.response.scopes.personal ) ) {
				$( '.bb-videos-actions' ).show();
			}
		},

		deleteVideoThumb: function ( event ) {
			var target = $( event.currentTarget );
			event.preventDefault();

			// call ajax to remove attachment video_thumbnail_delete.
			var videoId                = $( '.bb-rl-video-edit-thumbnail-hidden-video-id' ).val(),
				videoAttachmentId      = $( '.bb-rl-video-edit-thumbnail-hidden-attachment-id' ).val(),
				thumbVideoAttachmentId = target.closest( '.bb-dropzone-wrap' ).find( 'input' ).val();

			$.ajax(
				{
					type: 'POST',
					url: bbRlAjaxUrl,
					data: {
						'action': 'video_thumbnail_delete',
						'_wpnonce': bbRlNonce.video,
						'video_id': videoId,
						'attachment_id': videoAttachmentId,
						'video_attachment_id': thumbVideoAttachmentId,
					},
					success: function ( response ) {
						if ( response.data.video_attachments ) {
							$( '.video-action_list .edit_thumbnail_video a[data-video-attachment-id="' + videoAttachmentId + '"]' ).attr( 'data-video-attachments', response.data.video_attachments );
						}
						if ( response.data.thumbnail_id && response.data.thumbnail_id !== 0 ) {
							var bbVideoElem = $( '#bb-video-' + response.data.thumbnail_id );
							if ( bbVideoElem.length ) {
								bbVideoElem.prop( 'checked', true );
								$( '.bb-rl-video-thumbnail-submit' ).removeClass( 'is-disabled' );
							}
						}
						if ( response.data.thumbnail ) {
							var $videoThumb = $( '.bb-video-thumb a.bb-rl-video-cover-wrap[data-id="' + videoId + '"]' );
							if ( $videoThumb.find( 'img' ).length ) {
								$videoThumb.find( 'img' ).attr( 'src', response.data.thumbnail );
							}
							var $activityVideoElem = $( '.bb-activity-video-elem a.bb-rl-video-cover-wrap[data-id="' + videoId + '"]' );
							if ( $activityVideoElem.find( 'img' ).length ) {
								$activityVideoElem.find( 'img' ).attr( 'src', response.data.thumbnail );
							}
							var $activityVideoJs = $( '.bb-activity-video-elem .video-js[data-id="' + videoId + '"]' );
							if ( $activityVideoJs.find( '.vjs-poster' ).length ) {
								$activityVideoJs.attr( 'poster', response.data.thumbnail ).find( 'video' ).attr( 'poster', response.data.thumbnail ).end().find( '.vjs-poster' ).css( 'background-image', 'url("' + response.data.thumbnail + '")' );
							}
							var $theatreVideo = $( '#bb-rl-theatre-video-' + videoId );
							if ( $theatreVideo.length ) {
								$theatreVideo.attr( 'poster', response.data.thumbnail ).find( 'video' ).attr( 'poster', response.data.thumbnail ).end().find( '.vjs-poster' ).css( 'background-image', 'url("' + response.data.thumbnail + '")' );
								$videoThumb.find( 'img' ).attr( 'src', response.data.thumbnail );
							}
						}
					}
				}
			);
		},

		// Video Directory.

		openCreateVideoAlbumModal: function ( event ) {
			event.preventDefault();

			this.openAlbumUploader( event );
			$( '#bp-video-create-album' ).show();
		},

		closeCreateVideoAlbumModal: function ( event ) {
			event.preventDefault();

			this.closeUploader( event );
			$( '#bp-video-create-album' ).hide();
			$( '#bb-album-title' ).val( '' ).removeClass( 'error' );
		},

		saveAlbum: function ( event ) {
			bp.Nouveau.Media.saveItem( event, 'album', 'video');
		},

		injectVideos: function ( event ) {
			bp.Nouveau.Media.injectAttachments( event, 'video' );
		},

		/**
		 * [openVideoMove description]
		 *
		 * @return {[type]} [description]
		 * @param event
		 */
		openVideoMove : function ( event ) {
			event.preventDefault();

			var video_move_popup,
			eventCurrentTarget = $( event.currentTarget ),
			$document          = $( document ),
			video_id           = eventCurrentTarget.closest( '.video-action-wrap' ).siblings( 'a, div.video-js' ).data( 'id' ),
			video_parent_id    = eventCurrentTarget.closest( '.video-action-wrap' ).siblings( 'a, div.video-js' ).data( 'album-id' );

			this.moveToIdPopup   = eventCurrentTarget.attr( 'id' );
			this.moveToTypePopup = eventCurrentTarget.attr( 'data-type' );

			video_move_popup = eventCurrentTarget.closest(
				'.bb-rl-activity-inner, #media-stream.media, #video-stream.video, .comment-item'
			);

			video_move_popup.find( '.bb-rl-video-move-file' ).addClass( 'open' ).show();
			video_move_popup.find( '.bb-rl-video-move' ).attr( 'id', video_id );
			video_move_popup.find( '.bb-rl-model-footer .bb-rl-video-move' ).addClass( 'is-disabled' );

			// For Activity Feed.
			var currentTarget = (eventCurrentTarget.closest( '.bb_rl_more_dropdown' ).closest( 'li.comment-item' ).length) ?
				'#' + eventCurrentTarget.closest( '.bb_rl_more_dropdown' ).closest( 'li' ).attr( 'id' ) + '.comment-item .bb-rl-video-move-file' :
				'#' + eventCurrentTarget.closest( 'li.activity-item' ).attr( 'id' ) + ' > .bb-rl-activity-content .bb-rl-video-move-file';

			$( currentTarget ).find( '.bb-rl-document-move' ).attr( 'id', eventCurrentTarget.closest( '.bb-rl-document-activity' ).attr( 'data-id' ) );

			// Change if this is not from Activity Page.
			if ( eventCurrentTarget.closest( '.media-list' ).length > 0 || eventCurrentTarget.closest( '.video-list' ).length > 0 ) {
				currentTarget = '.bb-rl-video-move-file';
			}

			if ( 'group' === this.moveToTypePopup ) {
				$document.find( '.bb-rl-location-album-list-wrap h4' ).show();
			} else {
				$document.find( '.bb-rl-location-album-list-wrap h4' ).hide();
			}

			$( currentTarget ).addClass( 'open-popup' );
			$( currentTarget ).find( '.bb-rl-location-album-list-wrap .location-album-list' ).remove();
			$( currentTarget ).find( '.bb-rl-location-album-list-wrap' ).append( '<ul class="location-album-list is-loading"><li><i class="bb-icon-l bb-icon-spinner animate-spin"></i></li></ul>' );

			var parentsOpen = video_parent_id;
			var getFrom     = this.moveToTypePopup;
			if ( '' !== this.moveToIdPopup ) {
				$.ajax(
					{
						url        : bbRlAjaxUrl,
						type       : 'post',
						data       : {
							action : 'media_get_album_view',
							id     : this.moveToIdPopup,
							type   : this.moveToTypePopup,
						}, success : function ( response ) {
							var $popup = $document.find( '.open-popup' );
							$document.find( '.bb-rl-location-album-list-wrap h4 span.bb-rl-where-to-move-profile-or-group-video' ).html( response.data.first_span_text );
							$popup.find( '.bb-rl-location-album-list-wrap' ).toggle( '' !== response.data.html );
							$popup.find( '.bb-rl-location-album-list-wrap-main span.bb-rl-no-album-exists' ).toggle( '' === response.data.html );
							if ( 'group' === getFrom ) {
								$document.find( '.bb-rl-popup-on-fly-create-album .bb-rl-privacy-field-wrap-hide-show' ).hide();
								$popup.find( '.bb-rl-album-create-from' ).val( 'group' );
							} else {
								$document.find( '.bb-rl-popup-on-fly-create-album .bb-rl-privacy-field-wrap-hide-show' ).show();
								$popup.find( '.bb-rl-album-create-from' ).val( 'profile' );
							}

							$popup.find( '.bb-rl-video-open-create-popup-album' ).toggleClass( 'create-album', response.data.create_album ).toggle( response.data.create_album );

							$( currentTarget ).find( '.bb-rl-location-album-list-wrap .location-album-list' ).remove();
							$( currentTarget ).find( '.bb-rl-location-album-list-wrap' ).append( response.data.html );
							$( currentTarget ).find( 'ul.location-album-list span#' + parentsOpen ).trigger( 'click' );
						}
					}
				);
			}

			$document.on(
				'click',
				currentTarget + ' .location-album-list li span',
				function ( e ) {
					e.preventDefault();
					var $this = $( e.currentTarget ), $eventTarget = $( e.currentTarget );
					if ( $this.parent().hasClass( 'is_active' ) ) {
						return;
					}

					var $spanLastChild = $this.closest( '.bb-rl-location-album-list-wrap' ).find( '.breadcrumb .item span:last-child' );
					if ( $spanLastChild.data( 'id' ) !== 0 ) {
						$spanLastChild.remove();
					}

					$this.closest( '.bb-rl-location-album-list-wrap' ).find( '.breadcrumb .item' ).append( '<span class="is-disabled" data-id="' + $this.attr( 'id' ) + '">' + $this.text() + '</span>' );

					$this.addClass( 'selected' ).parent().addClass( 'is_active' ).siblings().removeClass( 'is_active' ).children( 'span' ).removeClass( 'selected' );
					var parentsOpen        = $document.find( 'a.bb-open-video-theatre[data-id="' + video_id + '"]' ).data( 'album-id' ),
						$videoMoveFileElem = $eventTarget.closest( '.bb-rl-video-move-file' ),
						$videoMoveElem     = $videoMoveFileElem.find( '.bb-rl-model-footer .bb-rl-video-move' );
					if ( Number( parentsOpen ) === Number( $eventTarget.data( 'id' ) ) ) {
						$videoMoveElem.addClass( 'is-disabled' );
					} else {
						$videoMoveElem.removeClass( 'is-disabled' );
					}
					if ( $videoMoveElem.hasClass( 'is-disabled' ) ) {
						return; // return if parent album is same.
					}
					$videoMoveFileElem.find( '.bb-rl-album-selected-id' ).val( $eventTarget.data( 'id' ) );
				}
			);

			$document.on(
				'click',
				currentTarget + ' .breadcrumb .item > span',
				function ( e ) {
					var $eventTarget = $( e.currentTarget );
					if ( $eventTarget.hasClass( 'is-disabled' ) ) {
						return;
					}

					var $videoMoveFileElem = $eventTarget.closest( '.bb-rl-video-move-file' );
					$videoMoveFileElem.find( '.bb-rl-album-selected-id' ).val( 0 );
					$videoMoveFileElem.find( '.location-album-list li span' ).removeClass( 'selected' ).parent().removeClass( 'is_active' );

					var spanLastChild = $eventTarget.closest( '.bb-rl-location-album-list-wrap' ).find( '.breadcrumb .item span:last-child' );
					if ( spanLastChild.hasClass( 'is-disabled' ) ) {
						spanLastChild.remove();
					}
					var $videoMoveElem = $videoMoveFileElem.find( '.bb-rl-model-footer .bb-rl-video-move' );
					if ( parentsOpen === $eventTarget.data( 'id' ) ) {
						$videoMoveElem.addClass( 'is-disabled' );
					} else {
						$videoMoveElem.removeClass( 'is-disabled' );
					}

				}
			);

		},

		createAlbumInPopup: function ( event ) {
			event.preventDefault();
			bp.Nouveau.Media.createAlbumFolderInPopup( event, 'video', 'album' );
		},

		/**
		 * [closeVideoMove description]
		 *
		 * @return {[type]} [description]
		 * @param event
		 */
		closeVideoMove: function ( event ) {
			event.preventDefault();
			var eventCurrentTarget = $( event.currentTarget ),
				spanLastChild      = eventCurrentTarget.closest( '.bb-rl-video-move-file' ).find( '.bb-rl-location-album-list-wrap .breadcrumb .item span:last-child' );
			if ( spanLastChild.data( 'id' ) !== 0 ) {
				spanLastChild.remove();
			}
			eventCurrentTarget.closest( '.bb-rl-video-move-file' ).hide();
		},

		moveVideoIntoAlbum: function ( event ) {
			bp.Nouveau.Media.moveAttachments( event, 'video', 'album' );
		},

		submitCreateAlbumInPopup: function ( event ) {
			event.preventDefault();
			bp.Nouveau.Media.submitCreateFolderAlbumInPopup( event, 'video', 'album' );
		},

		bb_video_after_load: function () {
			if ( $( '.video-list.bb-video-list' ).children().length ) {
				$( '.bb-videos-actions' ).show();
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

			this.videos            = [];
			this.current_video     = false;
			this.is_open_video     = false;
			this.nextVideoLink     = $( '.bb-rl-next-media' );
			this.previousVideoLink = $( '.bb-rl-prev-media' );
			this.activity_ajax     = false;
			this.group_id          = typeof bbRlVideo.group_id !== 'undefined' ? bbRlVideo.group_id : false;

		},

		/**
		 * [addListeners description]
		 */
		addListeners: function () {

			var $document = $( document );
			$document.on( 'click', '.bb-open-video-theatre', this.openTheatre.bind( this ) );
			$document.on( 'click', '.bb-rl-close-media-theatre', this.closeTheatre.bind( this ) );
			$document.on( 'click', '.bb-rl-prev-media', this.previous.bind( this ) );
			$document.on( 'click', '.bb-rl-next-media', this.next.bind( this ) );
			$document.on( 'click', '.bp-add-video-activity-description', this.openVideoActivityDescription.bind( this ) );
			$document.on( 'click', '#bp-activity-description-new-reset', this.closeVideoActivityDescription.bind( this ) );
			$document.on( 'keyup', '.bp-edit-video-activity-description #add-activity-description', this.MediaActivityDescriptionUpdate.bind( this ) );
			$document.on( 'click', '#bp-activity-description-new-submit', this.submitVideoActivityDescription.bind( this ) );
			$document.on( 'bp_activity_ajax_delete_request_video', this.videoActivityDeleted.bind( this ) );
			$document.on( 'click', '.bb-rl-video-thumb', this.handleThumbnailClick.bind( this ) );
		},
		openTheatre: function ( event ) {
			event.preventDefault();
			var target = $( event.currentTarget ), id, self = this;

			if ( target.closest( '#bp-existing-video-content' ).length ) {
				return false;
			}
			// Store activity data to use for video thumbnail.
			this.current_activity_data = target.closest( '.activity-item' ).data( 'bp-activity' );
			var modalTitle = target.closest( '.activity-item' ).data( 'activity-popup-title' );

			self.setupGlobals();
			self.setVideos( target );

			id = target.data( 'id' );
			self.setCurrentVideo( id );
			self.showVideo();
			self.navigationCommands();

			if ( self.current_activity_data ) {
				self.generateAndDisplayVideoThumbnails( target ); // Generate thumbnails after setting up video.
			}

			$( '.bb-rl-media-model-wrapper .bb-rl-media-model-header h2' ).text( modalTitle );

			if (
				typeof bbRlActivity !== 'undefined' &&
				self.current_video &&
				typeof self.current_video.activity_id !== 'undefined' &&
				self.current_video.activity_id !== 0 &&
				! self.current_video.is_forum &&
				self.current_video.privacy !== 'comment'
			) {
				self.getActivity();
			} else {
				self.getVideosDescription();
			}
			$( '.bb-rl-media-model-wrapper.media' ).hide();
			$( '.bb-rl-media-model-wrapper.document' ).hide();
			$( '.bb-rl-media-model-wrapper.video' ).show();
			self.is_open_video = true;
		},

		closeTheatre: function ( event ) {
			event.preventDefault();
			var self         = this, target = $( event.currentTarget ),
				modelWrapper = target.closest( '.bb-rl-media-model-wrapper' );

			if (
				modelWrapper.hasClass( 'bb-rl-media-theatre' ) ||
				modelWrapper.hasClass( 'bb-rl-document-theatre' )
			) {
				return false;
			}

			var $videoElem = $( '.bb-rl-media-model-wrapper.video .bb-rl-media-section' ).find( 'video' );
			if ( $videoElem.length ) {
				videojs( $videoElem.attr( 'id' ) ).reset();
			}
			$( '.bb-rl-media-model-wrapper' ).hide();
			self.is_open_video = false;

			self.resetRemoveActivityCommentsData();

			// Remove class from video theatre for the activity directory, forum topic and reply, video directory.
			modelWrapper.find( 'figure' ).removeClass( 'has-no-thumbnail' );

			self.current_video = false;
		},

		getVideosDescription: function () {
			var self = this;

			$( '.bb-media-info-section .bb-rl-activity-list' ).addClass( 'loading' ).html( '<i class="bb-icon-l bb-icon-spinner animate-spin"></i>' );

			if ( false !== self.activity_ajax ) {
				self.activity_ajax.abort();
			}

			var on_page_activity_comments = $( '[data-bp-activity-id="' + self.current_video.activity_id + '"] .activity-comments' );
			if ( on_page_activity_comments.length ) {
				self.current_video.parent_activity_comments = true;
				on_page_activity_comments.html( '' );
			}

			if ( true === self.current_video.parent_activity_comments ) {
				$( '.bb-rl-media-model-wrapper:last' ).after( '<input type="hidden" value="' + self.current_video.activity_id + '" id="hidden_parent_id"/>' );
			}

			self.activity_ajax = $.ajax(
				{
					type: 'POST',
					url: bbRlAjaxUrl,
					data: {
						action: 'video_get_video_description',
						id: self.current_video.id,
						attachment_id: self.current_video.attachment_id,
						nonce: bbRlNonce.video
					},
					success: function ( response ) {
						var $mediaWrapper = $( '.bb-rl-media-model-wrapper.video' );
						if ( true === response.success && 0 < $mediaWrapper.filter( ':visible' ).length ) {
							var $mediaSection = $mediaWrapper.find( '.bb-rl-media-section' );
							$mediaSection.html( response.data.video_data );
							$mediaSection.find( 'video' ).attr( 'autoplay', true );
							$( '.bb-media-info-section:visible .bb-rl-activity-list' ).removeClass( 'loading' ).html( response.data.description );
							$( '.bb-media-info-section:visible' ).show();

							self.updateTheaterHeaderTitle(
								{
									wrapper : $mediaWrapper,
									action  : 'video'
								}
							);

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
				self.videos      = [];
				var videosLength = video_elements.length;
				for ( i = 0; i < videosLength; i++ ) {
					var video_element = $( video_elements[ i ] );
					if ( ! video_element.closest( '#bp-existing-media-content' ).length ) {

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

						if ( video_element.closest( '.forums-video-wrap' ).length ) {
							m.is_forum = true;
						}

						m.is_message = typeof m.privacy !== 'undefined' && m.privacy === 'message';

						m.thumbnail_class = '';
						// Add class to video theatre for the activity directory, Message, and forum topic and reply.
						if ( video_element.closest( '.bb-activity-video-elem' ).hasClass( 'has-no-thumbnail' ) ) {
							m.thumbnail_class = 'has-no-thumbnail';

							// Add class to video theatre for the video directory.
						} else if ( video_element.closest( '.bb-video-thumb' ).hasClass( 'has-no-thumbnail' ) ) {
							m.thumbnail_class = 'has-no-thumbnail';
						}

						self.videos.push( m );
					}
				}
			}
		},
		setCurrentVideo: function ( id ) {
			var self = this, i, videosLength = self.videos.length;
			for ( i = 0; i < videosLength; i++ ) {
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
			$( '.bb-rl-media-model-wrapper.video .bb-rl-media-section' ).find( 'figure' ).addClass( 'loading' ).html( '<i class="bb-rl-loader"></i>' );

			// privacy.
			var video_privacy_wrap = $( '.bb-rl-media-section .bb-media-privacy-wrap' );

			if ( video_privacy_wrap.length ) {
				video_privacy_wrap.show();
				video_privacy_wrap.find( 'ul.media-privacy li' ).removeClass( 'selected' );
				video_privacy_wrap.find( '.bp-tooltip' ).attr( 'data-bp-tooltip', '' );
				var selected_video_privacy_elem = video_privacy_wrap.find( 'ul.media-privacy' ).find( 'li[data-value=' + self.current_video.privacy + ']' );
				selected_video_privacy_elem.addClass( 'selected' );
				video_privacy_wrap.find( '.bp-tooltip' ).attr( 'data-bp-tooltip', selected_video_privacy_elem.text() );
				video_privacy_wrap.find( '.privacy' ).removeClass( 'public' ).removeClass( 'loggedin' ).removeClass( 'onlyme' ).removeClass( 'friends' ).addClass( self.current_video.privacy );

				// hide privacy setting of video if activity is present.
				if ( ( typeof bbRlActivity !== 'undefined' &&
						typeof self.current_video.activity_id !== 'undefined' &&
						self.current_video.activity_id !== 0 ) ||
					self.group_id ||
					self.current_video.is_forum ||
					self.current_video.group_id ||
					self.current_video.album_id ||
					self.current_video.is_message
				) {
					video_privacy_wrap.hide();
				}
			}

			$( '.bb-rl-media-model-wrapper.video' ).find( 'figure' ).removeClass( 'has-no-thumbnail' ).addClass( self.current_video.thumbnail_class );

			// update navigation.
			self.navigationCommands();
		},
		navigationCommands: function () {
			var self = this;
			if ( self.current_index === 0 && self.current_index !== ( self.videos.length - 1 ) ) {
				self.previousVideoLink.hide();
				self.nextVideoLink.show();
			} else if ( self.current_index === 0 && self.current_index === ( self.videos.length - 1 ) ) {
				self.previousVideoLink.hide();
				self.nextVideoLink.hide();
			} else if ( self.current_index === ( self.videos.length - 1 ) ) {
				self.previousVideoLink.show();
				self.nextVideoLink.hide();
			} else {
				self.previousVideoLink.show();
				self.nextVideoLink.show();
			}
		},
		next: function ( event ) {
			event.preventDefault();
			// If the target is a media-video, return false. It's for media and video for a message right panel.
			if ( $( event.currentTarget ).closest( '.bb-rl-internal-model' ).hasClass( 'media-video' ) ) {
				return false;
			}

			var self = this, activity_id;
			if ( self.current_activity_data && self.videos[ self.current_index + 1 ] ) {
				self.updateVideoState(
					self.videos[ self.current_index + 1 ],
					$( '.video.bb-rl-video-theatre .bb-rl-video-thumb' ).eq( self.current_index + 1 )
				);
			} else {
				if ( typeof self.videos[ self.current_index + 1 ] !== 'undefined' ) {
					self.current_index = self.current_index + 1;
					activity_id        = self.current_video.activity_id;
					self.current_video = self.videos[ self.current_index ];
					self.showVideo();
					if ( activity_id !== self.current_video.activity_id && self.current_video.privacy !== 'comment' ) {
						self.getActivity();
					} else {
						self.getVideosDescription();
					}
				} else {
					self.nextLink.hide();
				}
			}
		},

		previous: function ( event ) {
			event.preventDefault();
			// If the target is a media-video, return false. It's for media and video for a message right panel.
			if ( $( event.currentTarget ).closest( '.bb-rl-internal-model' ).hasClass( 'media-video' ) ) {
				return false;
			}
			var self = this, activity_id;
			if ( self.current_activity_data && self.videos[ self.current_index - 1 ] ) {
				self.updateVideoState(
					self.videos[ self.current_index - 1 ],
					$( '.video.bb-rl-video-theatre .bb-rl-video-thumb' ).eq( self.current_index - 1 )
				);
			} else {
				if ( typeof self.videos[ self.current_index - 1 ] !== 'undefined' ) {
					self.current_index = self.current_index - 1;
					activity_id        = self.current_video.activity_id;
					self.current_video = self.videos[ self.current_index ];
					self.showVideo();
					if ( activity_id !== self.current_video.activity_id && self.current_video.privacy !== 'comment' ) {
						self.getActivity();
					} else {
						self.getVideosDescription();
					}
				} else {
					self.previousLink.hide();
				}
			}
		},

		generateAndDisplayVideoThumbnails: function ( target ) {
			var self = this;

			// Store activity data to use for video thumbnail.
			self.current_activity_data = target.closest( '.activity-item' ).data( 'bp-activity' );

			if (
				! self.current_activity_data ||
				! self.current_activity_data.video ||
				self.current_activity_data.video.length <= 1
			) {
				return;
			}

			var thumbnailsHtml = self.current_activity_data.video.map( function ( video ) {
				// Get video thumbnail or use default placeholder.
				var thumbUrl = video.thumb || BP_Nouveau.activity.strings.video_default_url;

				return '<div class="bb-rl-video-thumb' + ( video.id === self.current_video.id ? ' active' : '' ) +
				       '" data-id="' + video.id +
				       '" data-vid-id="' + video.vid_id +
				       '" data-activity-id="' + video.activity_id + '">' +
				       '<img src="' + thumbUrl +
				       '" alt="' + (
					       video.name || ''
				       ) +
				       '" data-video-url="' + video.url + '"/>' +
				       '</div>';
			} ).join( '' );

			// Add thumbnails to theater.
			var $videoSection   = $( '.video.bb-rl-video-theatre .bb-rl-media-section' );
			var $existingThumbs = $videoSection.find( '.bb-rl-video-thumb-list' );

			if ( $existingThumbs.length ) {
				$existingThumbs.html( thumbnailsHtml );
			} else {
				$videoSection.append( '<div class="bb-rl-video-thumb-list">' + thumbnailsHtml + '</div>' );
			}
		},

		updateVideoState: function ( videoData, $thumbnail, skipVideoPlayer ) {
			var self = this;

			// If already active, skip.
			if ( $thumbnail.hasClass( 'active' ) ) {
				return;
			}

			// Show loader while video loads.
			var $videoSection = $( '.bb-rl-media-model-wrapper.video .bb-rl-media-section' ),
			    $figure       = $videoSection.find( 'figure' );

			// Hide current video and show loader.
			$figure.find( '.video-js' ).hide();
			$figure.addClass( 'loading' ).append( '<i class="bb-rl-loader"></i>' );

			// Get video ID - either from an object or directly.
			var videoId = typeof videoData === 'object' ? videoData.id : videoData;

			// Update the current video and index.
			self.current_index = self.videos.findIndex( function ( video ) {
				return video.id === videoId;
			} );

			// Update current video data.
			if ( typeof videoData === 'object' ) {
				self.current_video = videoData;
			} else {
				self.current_video = $.extend( {}, self.current_video, {
					id          : videoId,
					activity_id : $thumbnail.data( 'activity-id' )
				} );
			}

			// Update navigation visibility.
			if ( self.nextVideoLink && self.previousVideoLink ) {
				self.nextVideoLink.toggle( Boolean( self.videos[ self.current_index + 1 ] ) );
				self.previousVideoLink.toggle( Boolean( self.videos[ self.current_index - 1 ] ) );
			}

			// Update thumbnail active state.
			$( '.bb-rl-video-thumb' ).removeClass( 'active' );
			$thumbnail.addClass( 'active' );

			// Update activity if needed.
			if (
				typeof bbRlActivity !== 'undefined' &&
				self.current_video &&
				typeof self.current_video.activity_id !== 'undefined' &&
				self.current_video.activity_id !== 0 &&
				! self.current_video.is_forum &&
				self.current_video.privacy !== 'comment'
			) {
				self.getActivity();
			} else if ( ! skipVideoPlayer ) {
				self.getVideosDescription();
			}

			// Handle video loading state in getActivity or getVideosDescription success callback.
			var originalSuccess        = self.activity_ajax.success;
			self.activity_ajax.success = function ( response ) {
				if ( response.success ) {
					var $videoElem = $figure.find( '.video-js' );
					if ( $videoElem.length ) {
						$videoElem.one( 'loadeddata', function () {
							// Remove loader and show video once loaded.
							$figure.removeClass( 'loading' ).find( '.bb-rl-loader' ).remove();
							$videoElem.show();
						} ).one( 'error', function () {
							// Handle video load error.
							$figure.removeClass( 'loading' ).find( '.bb-rl-loader' ).remove();
						} );
					}
				}
				// Call original success handler.
				if ( originalSuccess ) {
					originalSuccess.apply( this, arguments );
				}
			};
		},

		handleThumbnailClick: function ( event ) {
			event.preventDefault();
			var $target = $( event.currentTarget );
			this.updateVideoState( $target.data( 'id' ), $target, true );
		},

		resetRemoveActivityCommentsData: function () {
			var self               = this,
				hiddenParentIdElem = $( '#hidden_parent_id' ),
				currentActivityId  = hiddenParentIdElem.val();
			if ( 'undefined' !== typeof currentActivityId ) {
				self.activity_ajax = $.ajax(
					{
						type: 'POST',
						url: bbRlAjaxUrl,
						data: {
							action: 'video_get_activity',
							reset_comment: true,
							id: currentActivityId,
							group_id: ! _.isUndefined( self.current_video.group_id ) ? self.current_video.group_id : 0,
							video_id: ! _.isUndefined( self.current_video.id ) ? self.current_video.id : 0,
							nonce: bbRlNonce.video
						},
						success: function ( response ) {
							if ( response.success ) {
								$( '#activity-stream #activity-' + currentActivityId + ' .activity-comments' ).html( response.data.activity );
								// For video initializing.
								jQuery( window ).scroll();
								// For report popup.
								bp.Nouveau.reportPopUp();
								// For reported popup.
								bp.Nouveau.reportedPopup();
							}
						}
					}
				);
				// For Like and comment - When open video module as popup and add like and comment.
				// When we close the video module, then we fetch like and count and put into the main feed.
				var activity_meta, activity_state, activity, html = false, classes = false;
				activity       = $( '.bb-rl-media-model-wrapper.video [data-bp-activity-id="' + currentActivityId + '"]' );
				activity_state = activity.find( '.activity-state' );
				if ( activity_state.length ) {
					html    = activity_state.html();
					classes = activity_state.attr( 'class' );
					activity_state.remove();
					activity_state = $( '[data-bp-activity-id="' + currentActivityId + '"] .activity-state' );
					if ( activity_state.length ) {
						activity_state.html( html );
						activity_state.attr( 'class', classes );
					}
				}
				activity_meta = activity.find( '.activity-meta' );
				if ( activity_meta.length ) {
					html    = activity_meta.html();
					classes = activity_meta.attr( 'class' );
					activity_meta.remove();
					activity_meta = $( '[data-bp-activity-id="' + currentActivityId + '"] > .activity-meta' );
					if ( activity_meta.length ) {
						activity_meta.html( html );
						activity_meta.attr( 'class', classes );
					}
				}
				activity.remove();
				if ( hiddenParentIdElem.length ) {
					hiddenParentIdElem.remove();
				}
			}
		},

		getActivity: function () {
			var self = this;

			$( '.bb-media-info-section .bb-rl-activity-list' ).addClass( 'loading' ).html( '<i class="bb-rl-loader"></i>' );

			if ( typeof bbRlActivity !== 'undefined' &&
				self.current_video &&
				typeof self.current_video.activity_id !== 'undefined' &&
				self.current_video.activity_id !== 0 &&
				! self.current_video.is_forum
			) {

				if ( self.activity_ajax !== false ) {
					self.activity_ajax.abort();
				}

				$( '.bb-media-info-section.media' ).show();
				var on_page_activity_comments = $( '[data-bp-activity-id="' + self.current_video.activity_id + '"] .activity-comments' );
				if ( on_page_activity_comments.length ) {
					self.current_video.parent_activity_comments = true;
					on_page_activity_comments.html( '' );
				}

				if ( true === self.current_video.parent_activity_comments ) {
					$( '.bb-rl-media-model-wrapper:last' ).after( '<input type="hidden" value="' + self.current_video.activity_id + '" id="hidden_parent_id"/>' );
				}
				self.activity_ajax = $.ajax(
					{
						type: 'POST',
						url: bbRlAjaxUrl,
						data: {
							action: 'video_get_activity',
							id: self.current_video.activity_id,
							group_id: ! _.isUndefined( self.current_video.group_id ) ? self.current_video.group_id : 0,
							video_id: ! _.isUndefined( self.current_video.id ) ? self.current_video.id : 0,
							nonce: bbRlNonce.video
						},
						success: function ( response ) {
							if ( response.success && $( '.bb-rl-media-model-wrapper.video:visible' ).length ) {
								var $figureElem = $( '.bb-rl-media-model-wrapper.video .bb-rl-media-section' ).find( 'figure' );
								$figureElem.html( response.data.video_data );
								$figureElem.find( 'video' ).attr( 'autoplay', true );
								$figureElem.find( 'video' ).addClass( 'popup-video' );
								$( '.bb-media-info-section:visible .bb-rl-activity-list' ).removeClass( 'loading' ).html( response.data.activity );
								if ( 'undefined' !== typeof response.data.comment_form && 'undefined' !== typeof bp.Nouveau.Activity ) {
									$( '.bb-media-info-section:visible .bb-rl-activity-list .bb-rl-activity-comments ul:first' ).after( response.data.comment_form );

									$( '.bb-media-info-section:visible' ).find( '#ac-form-' + self.current_video.activity_id ).removeClass( 'not-initialized' ).addClass( 'root events-initiated' ).find( '#ac-input-' + self.current_video.activity_id ).focus();
									var form = $( '.bb-media-info-section:visible' ).find( '#ac-form-' + self.current_video.activity_id );
									bp.Nouveau.Activity.clearFeedbackNotice( form );
									form.removeClass( 'events-initiated' );
									var ce = $( '.bb-media-info-section:visible' ).find( '.ac-form .ac-input[contenteditable]' );
									bp.Nouveau.Activity.listenCommentInput( ce );
									if ( ! _.isUndefined( bbRlMedia ) && ! _.isUndefined( bbRlMedia.emoji ) ) {
										bp.Nouveau.Activity.initializeEmojioneArea( true, '#bb-rl-activity-modal ', self.current_video.activity_id );
									}
								}
								$( '.bb-media-info-section:visible' ).show();

								$( '.bb-media-info-section:visible' ).find( '.bb-activity-more-options-action' ).attr( 'data-balloon-pos', 'left' );

								jQuery( window ).scroll();
								setTimeout(
									function () {
										// Waiting to load dummy image.
										bp.Nouveau.reportPopUp();
										bp.Nouveau.reportedPopup();
									},
									1000
								);
							}
						}
					}
				);
			} else {
				$( '.bb-media-info-section.media' ).hide();
			}
		},

		openVideoActivityDescription: function ( event ) {
			event.preventDefault();
			var target           = $( event.currentTarget ),
				$descriptionElem = target.parents( '.activity-video-description' ),
				editVideoDesc    = $descriptionElem.find( '.bp-edit-video-activity-description' );
			if ( editVideoDesc.length < 1 ) {
				return false;
			}

			editVideoDesc.show().addClass( 'open' );
			$descriptionElem.find( '.bp-video-activity-description' ).hide();
			target.hide();
		},

		closeVideoActivityDescription: function ( event ) {
			event.preventDefault();
			var target           = $( event.currentTarget ),
				$descriptionElem = target.parents( '.activity-video-description' );

			if ( $descriptionElem.length < 1 ) {
				return false;
			}

			var default_value = $descriptionElem.find( '#add-activity-description' ).get( 0 ).defaultValue;

			$descriptionElem.find( '.bp-add-video-activity-description' ).show();
			$descriptionElem.find( '.bp-video-activity-description' ).show();
			$descriptionElem.find( '#add-activity-description' ).val( default_value );
			$descriptionElem.find( '.bp-edit-video-activity-description' ).hide().removeClass( 'open' );
		},

		MediaActivityDescriptionUpdate: function ( event ) {
			var eventCurrentTarget = $( event.currentTarget );
			if ( eventCurrentTarget.val().trim() !== '' ) {
				eventCurrentTarget.closest( '.bp-edit-video-activity-description' ).addClass( 'has-content' );
			} else {
				eventCurrentTarget.closest( '.bp-edit-video-activity-description' ).removeClass( 'has-content' );
			}
		},

		submitVideoActivityDescription: function ( event ) {
			event.preventDefault();

			var target        = $( event.currentTarget ),
				parent_wrap   = target.parents( '.activity-video-description' ),
				$descInput    = parent_wrap.find( '#add-activity-description' ),
				description   = $descInput.val(),
				attachment_id = parent_wrap.find( '#bp-attachment-id' ).val();

			var data = {
				'action'        : 'video_description_save',
				'description'   : description,
				'attachment_id' : attachment_id,
				'_wpnonce'      : bbRlNonce.video,
			};

			$.ajax(
				{
					type    : 'POST',
					url     : bbRlAjaxUrl,
					data    : data,
					async   : false,
					success : function ( response ) {
						if ( response.success ) {
							var $addVideoDesc = parent_wrap.find( '.bp-add-video-activity-description' ),
								$videoDesc    = parent_wrap.find( '.bp-video-activity-description' );
							$videoDesc.html( response.data.description ).show();
							$addVideoDesc.show();
							$descInput.val( response.data.description );
							$descInput.get( 0 ).defaultValue = response.data.description;
							if ( response.data.description === '' ) {
								$addVideoDesc.removeClass( 'show-edit' ).addClass( 'show-add' );
							} else {
								$addVideoDesc.addClass( 'show-edit' ).removeClass( 'show-add' );
							}

							parent_wrap.find( '.bp-edit-video-activity-description' ).hide().removeClass( 'open' );
							$videoDesc.show();
							parent_wrap.find( '.bp-feedback.error' ).remove();
						} else {
							parent_wrap.prepend( response.data.feedback );
						}
					}
				}
			);
		},

		videoActivityDeleted: function ( event, data ) {
			var self = this, i = 0, $document = $( document );
			if ( self.is_open_video && typeof data !== 'undefined' && data.action === 'delete_activity' && self.current_video.activity_id === data.id ) {

				var $deleted_item             = $document.find( '[data-bp-list="video"] .bb-open-video-theatre[data-id="' + self.current_video.id + '"]' );
				var $deleted_item_parent_list = $deleted_item.parents( 'ul' );

				$deleted_item.closest( 'li' ).remove();

				if ( 0 === $deleted_item_parent_list.find( 'li:not(.load-more)' ).length ) {

					// No item.
					var $videosActions = $( '.bb-videos-actions' );
					if ( $videosActions.length > 0 ) {
						$videosActions.hide();
					}

					if ( 1 === $deleted_item_parent_list.find( 'li.load-more' ).length ) {
						location.reload();
					}
				}

				$document.find( '[data-bp-list="activity"] .bb-open-video-theatre[data-id="' + self.current_video.id + '"]' ).closest( '.bb-activity-video-elem' ).remove();

				var videosLength = self.videos.length;
				for ( i = 0; i < videosLength; i++ ) {
					if ( self.videos[ i ].activity_id === data.id ) {
						self.videos.splice( i, 1 );
						break;
					}
				}

				if ( self.current_index === 0 && self.current_index !== ( self.videos.length ) ) {
					self.current_index = -1;
					self.next( event );
				} else if ( self.current_index === 0 && self.current_index === ( self.videos.length ) ) {
					$document.find( '[data-bp-list="activity"] li.activity-item[data-bp-activity-id="' + self.current_video.activity_id + '"]' ).remove();
					self.closeTheatre( event );
				} else if ( self.current_index === ( self.videos.length ) ) {
					self.previous( event );
				} else {
					self.current_index = -1;
					self.next( event );
				}
			}
		},

		updateTheaterHeaderTitle : function ( data ) {
			var wrapper = data.wrapper,
			    action  = data.action;

			var activityHeaderElem = wrapper.find( '.activity-item' ),
			    modalTitle         = '';
			if ( activityHeaderElem.find( '.bb-rl-activity-header' ).length ) {
				// Extract username from the first link in the activity header.
				var usernameLink = activityHeaderElem.find( '.bb-rl-activity-header a' ).first();
				if ( usernameLink.length ) {
					modalTitle = usernameLink.text() + bbRlMedia.i18n_strings.theater_title;
					$( '.bb-rl-' + action + '-model-wrapper' + ' .bb-rl-media-model-header h2' ).text( modalTitle );
				}
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

			this.player     = [];
			this.playerTime = 0;
			this.playerID   = '';

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

			var player = this.player;

			$( '.video-js:not(.loaded)' ).each(
				function () {

					var self      = this;
					var options   = { 'controlBar' : { 'volumePanel' : { 'inline' : false } } };
					var player_id = $( this ).attr( 'id' );

					var videoIndex                   = $( this ).attr( 'id' );
					player[ $( this ).attr( 'id' ) ] = videojs(
						self,
						options,
						function onPlayerReady() {
							this.on(
								'ended',
								function () {
								}
							);
							this.on(
								'play',
								function () {
									$( '.video-js' ).each(
										function () {
											var $playerEl = $( this );
											var playerId = $playerEl.attr( 'id' );

											// Skip current video and error-state players
											if ( playerId === videoIndex || $playerEl.hasClass( 'vjs-error' ) ) {
												return;
											}

											// Safely access player instance
											var otherPlayer = videojs.getPlayer( playerId );
											if ( otherPlayer ) {
												otherPlayer.pause();
											}
										}
									);
								}
							);
						}
					);

					if ( player[ player_id ] !== undefined && ( $( this ).find( '.skip-back' ).length === 0 && $( this ).find( '.skip-forward' ).length === 0 ) && ! $( 'body' ).hasClass( 'messages' ) ) {
						player[ player_id ].seekButtons(
							{
								forward: 5,
								back: 5
							}
						);
						setTimeout(
							function () {
								var vjsBlock = $( self ).parent();
								vjsBlock.find( '.vjs-control-bar > .vjs-seek-button.skip-back, .vjs-control-bar > .vjs-seek-button.skip-forward' ).attr( 'data-balloon-pos', 'up' );
								vjsBlock.find( '.vjs-control-bar > .vjs-seek-button.skip-back' ).attr( 'data-balloon', bbRlVideo.i18n_strings.video_skip_back_text );
								vjsBlock.find( '.vjs-control-bar > .vjs-seek-button.skip-forward' ).attr( 'data-balloon', bbRlVideo.i18n_strings.video_skip_forward_text );
							},
							0
						);
					}
					// Check if Video has played before and has the same id.
					if ( bp.Nouveau.Video.Player.playerTime > 0 && $( this ).attr( 'id' ) === 'theatre-' + bp.Nouveau.Video.Player.playerID ) {
						player[ $( self ).parent().attr( 'id' ) ].currentTime( bp.Nouveau.Video.Player.playerTime );
						player[ $( self ).parent().attr( 'id' ) ].play();
					} else {
						bp.Nouveau.Video.Player.playerTime = 0;
						bp.Nouveau.Video.Player.playerID   = '';
					}

					if ( $( self ).hasClass( 'bb-rl-single-activity-video' ) || $( self ).hasClass( 'single-activity-video' ) ) {
						var ele_id     = $( this ).attr( 'id' );
						var cus_button = player[ $( this ).attr( 'id' ) ].controlBar.addChild( 'button' );
						cus_button.addClass( 'vjs-icon-square' );
						var fullscreen_btn = $( this ).find( '.vjs-icon-square' ).addClass( 'enlarge_button' );
						fullscreen_btn.attr( 'data-balloon-pos', 'left' );
						fullscreen_btn.attr( 'data-balloon', bbRlVideo.i18n_strings.video_enlarge_text );
						var error_block      = $( this ).find( '.vjs-error-display.vjs-modal-dialog' );
						var video_block_main = $( this );
						var eleIdElement     = $( '#' + ele_id );

						fullscreen_btn.on(
							'click touchstart',
							function () {
								// Set current time of video and id.
								if ( player[ele_id].currentTime() > 0 ) {
									bp.Nouveau.Video.Player.playerTime = player[ele_id].currentTime();
									bp.Nouveau.Video.Player.playerID   = eleIdElement.parent().find( '.video-js video' ).attr( 'id' );
								}
								player[ele_id].pause();
								var $videoTheatre = eleIdElement.parent().find( '.bb-open-video-theatre' );
								if ( $videoTheatre.length ) {
									$videoTheatre.trigger( 'click' );
								}
							}
						);

						error_block.on(
							'click',
							function () {
								eleIdElement.parent().find( '.bb-open-video-theatre' ).trigger( 'click' );
							}
						);

						video_block_main.on(
							'click',
							function (e) {

								if ( $( e.target ).hasClass( 'video-js' ) ) {

									if ( video_block_main.hasClass( 'vjs-paused' ) ) {
										player[ele_id].play();
									} else {
										player[ele_id].pause();
									}

								}

							}
						);

					}

					if ( $( self ).closest( '.bb-rl-video-theatre' ).length ) {
						var Enter_fullscreen_btn = $( this ).parent().find( '.vjs-fullscreen-control' );
						Enter_fullscreen_btn.attr( 'data-balloon-pos', 'up' );
						Enter_fullscreen_btn.attr( 'data-balloon', bbRlVideo.i18n_strings.video_fullscreen_text );
					}

					// Add video Picture in Picture notice.
					$( self ).parent().find( '.video-js' ).append( '<div class="pcture-in-picture-notice">' + bbRlVideo.i18n_strings.video_picture_in_text + '</div>' );

					// Add Tooltips to control buttons.
					var vjsallControlsButton = $( self ).parent().find( '.vjs-control-bar > button, .vjs-control-bar > div' );
					vjsallControlsButton.attr( 'data-balloon-pos', 'up' );

					var vjsBlock = $( self ).parent();
					vjsBlock.find( '.vjs-control-bar > .vjs-play-control' ).attr( 'data-balloon', bbRlVideo.i18n_strings.video_play_text );
					vjsBlock.find( '.vjs-control-bar > .vjs-play-control' ).attr( 'data-balloon-pause', bbRlVideo.i18n_strings.video_pause_text );
					vjsBlock.find( '.vjs-control-bar > .vjs-volume-panel' ).attr( 'data-balloon-pos', 'right' );
					vjsBlock.find( '.vjs-control-bar > .vjs-volume-panel' ).attr( 'data-balloon', bbRlVideo.i18n_strings.video_volume_text );
					vjsBlock.find( '.vjs-control-bar > .vjs-picture-in-picture-control' ).attr( 'data-balloon', bbRlVideo.i18n_strings.video_miniplayer_text );
					vjsBlock.find( '.vjs-control-bar > .vjs-playback-rate' ).attr( 'data-balloon-pos', 'left' );
					vjsBlock.find( '.vjs-control-bar > .vjs-playback-rate' ).attr( 'data-balloon', bbRlVideo.i18n_strings.video_speed_text );

					$( this ).addClass( 'loaded' );
				}
			);

		},

		removeVideoAndThumbnailAttachment: function ( id, action, nonce ) {
			var data = {
				'action'   : action + '_delete_attachment',
				'_wpnonce' : nonce,
				'id'       : id
			};

			$.ajax(
				{
					type : 'POST',
					url  : bbRlAjaxUrl,
					data : data
				}
			);
		},
	};

	// Launch BP Nouveau Video Player.
	bp.Nouveau.Video.Player.start();

} )( bp, jQuery );
