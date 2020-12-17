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

			this.video_dropzone_obj = [];
			this.dropzone_video = [];
			this.video_album_id = typeof BP_Nouveau.video.album_id !== 'undefined' ? BP_Nouveau.video.album_id : false;
			this.video_group_id = typeof BP_Nouveau.video.group_id !== 'undefined' ? BP_Nouveau.video.group_id : false;
			this.current_tab = bodySelector.hasClass( 'single-topic' ) || bodySelector.hasClass( 'single-forum' ) ? false : 'bp-video-dropzone-content';

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
			$( document ).on( 'click', '.bb-prev-media', this.previous.bind(this));
			$( document ).on( 'click', '.bb-next-media', this.next.bind(this));

		},
		openTheatre: function ( event ) {
			event.preventDefault();
			var target = $( event.currentTarget ), id, self = this;

			//alert("openTheatre called");
			//alert(target);
			if ( target.closest( '#bp-existing-video-content' ).length ) {
				return false;
			}
			//alert("Not return");

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

		setVideos: function (target) {
			var video_elements = $( '.bb-open-video-theatre' ), i = 0, self = this;
			// check if on activity page, load only activity video in theatre.
			if ($( 'body' ).hasClass( 'activity' )) {
				video_elements = $( target ).closest( '.bb-activity-video-wrap' ).find( '.bb-open-video-theatre' );
			}

			if (typeof video_elements !== 'undefined') {
				self.videos = [];
				for (i = 0; i < video_elements.length; i++) {
					var video_element = $( video_elements[i] );
					if ( ! video_element.closest( '#bp-existing-media-content' ).length) {

						var m = {
							id					: video_element.data( 'id' ),
							attachment			: video_element.data( 'attachment-full' ),
							activity_id			: video_element.data( 'activity-id' ),
							attachment_id		: video_element.data( 'attachment-id' ),
							privacy				: video_element.data( 'privacy' ),
							parent_activity_id	: video_element.data( 'parent-activity-id' ),
							album_id			: video_element.data( 'album-id' ),
							group_id			: video_element.data( 'group-id' ),
							is_forum			: false
						};

						if (video_element.closest( '.forums-media-wrap' ).length) {
							m.is_forum = true;
						}

						if (typeof m.privacy !== 'undefined' && m.privacy == 'message') {
							m.is_message = true;
						} else {
							m.is_message = false;
						}

						self.videos.push( m );
					}
				}
			}
		},
		setCurrentVideo: function (id) {
			var self = this, i = 0;
			for (i = 0; i < self.videos.length; i++) {
				if (id === self.videos[i].id) {
					self.current_video = self.videos[i];
					self.current_index = i;
					break;
				}
			}
		},
		showVideo: function () {
			var self = this;
			if (typeof self.current_video === 'undefined') {
				return false;
			}
			// refresh img.
			$( '.bb-media-model-wrapper.video .bb-media-section' ).find( 'img' ).attr( 'src', self.current_video.attachment );

			// privacy.
			var video_privacy_wrap = $( '.bb-media-section .bb-media-privacy-wrap' );

			if (video_privacy_wrap.length) {
				video_privacy_wrap.show();
				video_privacy_wrap.find( 'ul.media-privacy li' ).removeClass( 'selected' );
				video_privacy_wrap.find( '.bp-tooltip' ).attr( 'data-bp-tooltip', '' );
				var selected_video_privacy_elem = video_privacy_wrap.find( 'ul.media-privacy' ).find( 'li[data-value=' + self.current_video.privacy + ']' );
				selected_video_privacy_elem.addClass( 'selected' );
				video_privacy_wrap.find( '.bp-tooltip' ).attr( 'data-bp-tooltip', selected_video_privacy_elem.text() );
				video_privacy_wrap.find( '.privacy' ).removeClass( 'public' ).removeClass( 'loggedin' ).removeClass( 'onlyme' ).removeClass( 'friends' ).addClass( self.current_video.privacy );

				// hide privacy setting of video if activity is present.
				if ((typeof BP_Nouveau.activity !== 'undefined' &&
					typeof self.current_video.activity_id !== 'undefined' &&
					self.current_video.activity_id != 0) ||
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
			if (self.current_index == 0 && self.current_index != (self.videos.length - 1)) {
				self.previousVideoLink.hide();
				self.nextVideoLink.show();
			} else if (self.current_index == 0 && self.current_index == (self.videos.length - 1)) {
				self.previousVideoLink.hide();
				self.nextVideoLink.hide();
			} else if (self.current_index == (self.videos.length - 1)) {
				self.previousVideoLink.show();
				self.nextVideoLink.hide();
			} else {
				self.previousVideoLink.show();
				self.nextVideoLink.show();
			}
		},
		next: function (event) {
			event.preventDefault();
			var self = this, activity_id;
			self.resetRemoveActivityCommentsData();
			if (typeof self.videos[ self.current_index + 1 ] !== 'undefined') {
				self.current_index = self.current_index + 1;
				activity_id = self.current_video.activity_id;
				self.current_video = self.videos[ self.current_index ];
				self.showVideo();
				if (activity_id != self.current_video.activity_id) {
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
			if (typeof self.videos[ self.current_index - 1 ] !== 'undefined') {
				self.current_index = self.current_index - 1;
				activity_id = self.current_video.activity_id;
				self.current_video = self.videos[ self.current_index ];
				self.showVideo();
				if (activity_id != self.current_video.activity_id) {
					self.getActivity();
				} else {
					self.getMediasDescription();
				}
			} else {
				self.previousLink.hide();
			}
		},

		resetRemoveActivityCommentsData: function () {
			var self = this, activity_comments = false, activity_meta = false, activity_state = false, activity = false,
			    html = false, classes = false;
			if (self.current_video.parent_activity_comments) {
				activity = $('.bb-media-model-wrapper.video [data-bp-activity-id="' + self.current_video.activity_id + '"]');
				activity_comments = activity.find('.activity-comments');
				if (activity_comments.length) {
					html = activity_comments.html();
					classes = activity_comments.attr('class');
					activity_comments.remove();
					activity_comments = $('[data-bp-activity-id="' + self.current_video.activity_id + '"] .activity-comments');
					if (activity_comments.length) {
						activity_comments.html(html);
						activity_comments.attr('class', classes);
					}
				}
				activity_state = activity.find('.activity-state');
				if (activity_state.length) {
					html = activity_state.html();
					classes = activity_state.attr('class');
					activity_state.remove();
					activity_state = $('[data-bp-activity-id="' + self.current_video.activity_id + '"] .activity-state');
					if (activity_state.length) {
						activity_state.html(html);
						activity_state.attr('class', classes);
					}
				}
				activity_meta = activity.find('.activity-meta');
				if (activity_meta.length) {
					html = activity_meta.html();
					classes = activity_meta.attr('class');
					activity_meta.remove();
					activity_meta = $('[data-bp-activity-id="' + self.current_video.activity_id + '"] .activity-meta');
					if (activity_meta.length) {
						activity_meta.html(html);
						activity_meta.attr('class', classes);
					}
				}
				activity.remove();
			}
		},

		getActivity: function () {
			var self = this;

			$( '.bb-video-info-section .activity-list' ).addClass( 'loading' ).html( '<i class="bb-icon-loader animate-spin"></i>' );

			if (typeof BP_Nouveau.activity !== 'undefined' &&
				self.current_video &&
				typeof self.current_video.activity_id !== 'undefined' &&
				self.current_video.activity_id != 0 &&
				! self.current_video.is_forum
			) {

				if (self.activity_ajax != false) {
					self.activity_ajax.abort();
				}

				$( '.bb-media-info-section.media' ).show();
				var on_page_activity_comments = $( '[data-bp-activity-id="' + self.current_video.activity_id + '"] .activity-comments' );
				if ( on_page_activity_comments.length ) {
					self.current_video.parent_activity_comments = true;
					on_page_activity_comments.html('');
				}

				self.activity_ajax = $.ajax(
					{
						type: 'POST',
						url: BP_Nouveau.ajaxurl,
						data: {
							action: 'video_get_activity',
							id: self.current_video.activity_id,
							group_id: ! _.isUndefined( self.current_video.group_id ) ? self.current_video.group_id : 0,
							video_id: ! _.isUndefined( self.current_video.id ) ? self.current_video.id : 0,
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

					var self    = this;
					var options = {};

					videojs( self, options, function onPlayerReady() {
						this.on( 'ended', function () {
						} );
					} );

					$(this).addClass( 'loaded' );
				} );


		},
	};

	// Launch BP Nouveau Video Player.
	bp.Nouveau.Video.Player.start();

} )( bp, jQuery );
