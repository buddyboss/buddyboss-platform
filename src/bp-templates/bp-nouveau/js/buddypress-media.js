/* jshint browser: true */
/* global bp, BP_Nouveau, JSON, Dropzone */
/* @version 1.0.0 */
window.bp = window.bp || {};

( function( exports, $ ) {

	// Bail if not set
	if ( typeof BP_Nouveau === 'undefined' ) {
		return;
	}

	bp.Nouveau = bp.Nouveau || {};

	/**
	 * [Media description]
	 * @type {Object}
	 */
	bp.Nouveau.Media = {

		/**
		 * [start description]
		 * @return {[type]} [description]
		 */
		start: function() {
			this.setupGlobals();

			// Listen to events ("Add hooks!")
			this.addListeners();

		},

		/**
		 * [setupGlobals description]
		 * @return {[type]} [description]
		 */
		setupGlobals: function() {

			// Init current page
			this.current_page   = 1;
			this.current_page_existing_media   = 1;
			this.current_page_albums   = 1;
			this.current_tab   = $('body').hasClass('single-topic') || $('body').hasClass('single-forum') ? false : 'bp-dropzone-content';

			// set up dropzones auto discover to false so it does not automatically set dropzones
			window.Dropzone.autoDiscover = false;

			this.options = {
				url: BP_Nouveau.ajaxurl,
				timeout: 3 * 60 * 60 * 1000,
				acceptedFiles: 'image/*',
				autoProcessQueue: true,
				addRemoveLinks: true,
				uploadMultiple: false,
				maxFilesize: typeof BP_Nouveau.media.max_upload_size !== 'undefined' ? BP_Nouveau.media.max_upload_size : 2
			};

			this.dropzone_obj = null;
			this.dropzone_media = [];
			this.album_id = typeof BP_Nouveau.media.album_id !== 'undefined' ? BP_Nouveau.media.album_id : false;
			this.group_id = typeof BP_Nouveau.media.group_id !== 'undefined' ? BP_Nouveau.media.group_id : false;

		},

		/**
		 * [addListeners description]
		 */
		addListeners: function() {

			$( '.bp-nouveau' ).on( 'click', '#bp-add-media', this.openUploader.bind( this ) );
			$( '.bp-nouveau' ).on( 'click', '#bp-media-submit', this.submitMedia.bind( this ) );
			$( '.bp-nouveau' ).on( 'click', '#bp-media-uploader-close', this.closeUploader.bind( this ) );
			$( '.bp-nouveau' ).on( 'click', '#bb-delete-media', this.deleteMedia.bind( this ) );
			$( '.bp-nouveau' ).on( 'click', '#bb-select-deselect-all-media', this.toggleSelectAllMedia.bind( this ) );
			$( '#buddypress [data-bp-list="media"]' ).on('bp_ajax_request',this.bp_ajax_media_request);

			// albums
			$( '.bp-nouveau' ).on( 'click', '#bb-create-album', this.openCreateAlbumModal.bind( this ) );
			$( '.bp-nouveau' ).on( 'click', '#bp-media-create-album-submit', this.saveAlbum.bind( this ) );
			$( '.bp-nouveau' ).on( 'click', '#bp-media-create-album-close', this.closeCreateAlbumModal.bind( this ) );

			$( '.bp-nouveau' ).on( 'click', '#bp-media-add-more', this.triggerDropzoneSelectFileDialog.bind( this ) );

			$( '#bp-media-uploader' ).on( 'click', '.bp-media-upload-tab', this.changeUploadModalTab.bind( this ) );

			// Fetch Media
			$( '.bp-nouveau [data-bp-list="media"]' ).on( 'click', 'li.load-more', this.injectMedias.bind( this ) );
			$( '.bp-nouveau #albums-dir-list' ).on( 'click', 'li.load-more', this.appendAlbums.bind( this ) );
			$( '.bp-existing-media-wrap' ).on( 'click', 'li.load-more', this.appendMedia.bind( this ) );
			$( '.bp-nouveau' ).on( 'change', '.bb-media-check-wrap [name="bb-media-select"]', this.addSelectedClassToWrapper.bind( this ) );
			$( '.bp-existing-media-wrap' ).on( 'change', '.bb-media-check-wrap [name="bb-media-select"]', this.toggleSubmitMediaButton.bind( this ) );

			//single album
			$( '.bp-nouveau' ).on( 'click', '#bp-edit-album-title', this.editAlbumTitle.bind( this ) );
			$( '.bp-nouveau' ).on( 'click', '#bp-cancel-edit-album-title', this.cancelEditAlbumTitle.bind( this ) );
			$( '.bp-nouveau' ).on( 'click', '#bp-save-album-title', this.saveAlbum.bind( this ) );
			$( '.bp-nouveau' ).on( 'change', '#bp-media-single-album select#bb-album-privacy', this.saveAlbum.bind( this ) );
			$( '.bp-nouveau' ).on( 'click', '#bb-delete-album', this.deleteAlbum.bind( this ) );

			//forums
			$( '.bbpress,.buddypress' ).on( 'click', '#forums-media-button', this.openForumsUploader.bind( this ) );

		},

		bp_ajax_media_request: function(event,data) {
			if ( typeof data !== 'undefined' && typeof data.response.scopes.personal !== 'undefined' && data.response.scopes.personal === 0 ) {
				$('.bb-photos-actions').hide();
			}
		},

		addSelectedClassToWrapper: function(event) {
			var target = event.currentTarget;
			if ( $(target).is(':checked') ) {
				$(target).closest('.bb-media-check-wrap').find('.bp-tooltip').attr('data-bp-tooltip',wp.i18n.__('Unselect','buddyboss'));
				$(target).closest('.bb-photo-thumb').addClass('selected');
			} else {
				$(target).closest('.bb-photo-thumb').removeClass('selected');
				$(target).closest('.bb-media-check-wrap').find('.bp-tooltip').attr('data-bp-tooltip',wp.i18n.__('Select','buddyboss'));

				if ( $( '.bp-nouveau #bb-select-deselect-all-media' ).hasClass('selected') ) {
					$( '.bp-nouveau #bb-select-deselect-all-media' ).removeClass('selected');
				}
			}
		},

		deleteMedia: function(event) {
			var target = $(event.currentTarget);
			event.preventDefault();

			var media = [];
			$('#buddypress').find('.media-list:not(.existing-media-list)').find('.bb-media-check-wrap [name="bb-media-select"]:checked').each(function(){
				$(this).closest('.bb-photo-thumb').addClass('loading deleting');
				media.push($(this).val());
			});

			if ( media.length == 0 ) {
				return false;
			}

			target.prop('disabled',true);
			$('#buddypress .media-list .bp-feedback').remove();

			var data = {
				'action': 'media_delete',
				'_wpnonce': BP_Nouveau.nonces.media,
				'media': media
			};

			$.ajax({
				type: 'POST',
				url: BP_Nouveau.ajaxurl,
				data: data,
				success: function (response) {
					setTimeout(function () {
						target.prop('disabled',false);
					},500);
					if (response.success) {

						$('#buddypress').find('.media-list:not(.existing-media-list)').find('.bb-media-check-wrap [name="bb-media-select"]:checked').each(function(){
							$(this).closest('li').remove();
						});

						if ( $('#buddypress').find('.media-list:not(.existing-media-list)').find('li:not(.load-more)').length == 0 ) {
							$('.bb-photos-actions').hide();
							var feedback = '<aside class="bp-feedback bp-messages info">\n' +
								'\t<span class="bp-icon" aria-hidden="true"></span>\n' +
								'\t<p>'+wp.i18n.__('Sorry, no photos were found.','buddyboss')+'</p>\n' +
								'\t</aside>';
							$('#buddypress [data-bp-list="media"]').html(feedback);
						}
					} else {
						$('#buddypress .media-list').prepend(response.data.feedback);
					}

				}
			});
		},

		toggleSelectAllMedia: function(event) {
			event.preventDefault();

			if ( $(event.currentTarget).hasClass('selected') ) {
				$(event.currentTarget).data('bp-tooltip',wp.i18n.__('Select All','buddyboss'));
				this.deselectAllMedia(event);
			} else {
				$(event.currentTarget).data('bp-tooltip',wp.i18n.__('Unselect All','buddyboss'));
				this.selectAllMedia(event);
			}

			$(event.currentTarget).toggleClass('selected');
		},

		selectAllMedia: function(event) {
			event.preventDefault();

			$('#buddypress').find('.media-list:not(.existing-media-list)').find('.bb-media-check-wrap [name="bb-media-select"]').each(function(){
				$(this).prop('checked',true);
				$(this).closest('.bb-photo-thumb').addClass('selected');
				$(this).closest('.bb-media-check-wrap').find('.bp-tooltip').attr('data-bp-tooltip',wp.i18n.__('Unselect','buddyboss'));
			});
		},

		deselectAllMedia: function(event) {
			event.preventDefault();

			$('#buddypress').find('.media-list:not(.existing-media-list)').find('.bb-media-check-wrap [name="bb-media-select"]').each(function(){
				$(this).prop('checked',false);
				$(this).closest('.bb-photo-thumb').removeClass('selected');
				$(this).closest('.bb-media-check-wrap').find('.bp-tooltip').attr('data-bp-tooltip',wp.i18n.__('Select','buddyboss'));
			});
		},

		editAlbumTitle: function(event) {
			event.preventDefault();

			$('#bb-album-title').show();
			$('#bp-save-album-title').show();
			$('#bp-cancel-edit-album-title').show();
			$('#bp-edit-album-title').hide();
			$('#bp-media-single-album #bp-single-album-title').hide();
		},

		cancelEditAlbumTitle: function(event) {
			event.preventDefault();

			$('#bb-album-title').hide();
			$('#bp-save-album-title').hide();
			$('#bp-cancel-edit-album-title').hide();
			$('#bp-edit-album-title').show();
			$('#bp-media-single-album #bp-single-album-title').show();
		},

		triggerDropzoneSelectFileDialog: function() {
			var self = this;

			self.dropzone_obj.hiddenFileInput.click();
		},

		closeUploader: function(event) {
			event.preventDefault();

			$('#bp-media-uploader').hide();
			$('#bp-media-add-more').hide();
			$('#bp-media-uploader-modal-title').text(wp.i18n.__( 'Upload', 'buddyboss' ));
			$('#bp-media-uploader-modal-status-text').text('');
			this.dropzone_obj.destroy();
			this.dropzone_media = [];

		},

		openForumsUploader: function(event) {
			var self = this, dropzone_container = $('div#forums-post-media-uploader');
			event.preventDefault();

			if ( typeof window.Dropzone !== 'undefined' && dropzone_container.length ) {

				if ( dropzone_container.hasClass('closed') ) {

					self.options.autoProcessQueue = true;

					// init dropzone
					self.dropzone_obj = new Dropzone('div#forums-post-media-uploader', self.options);

					self.dropzone_obj.on('sending', function(file, xhr, formData) {
						formData.append('action', 'media_upload');
						formData.append('_wpnonce', BP_Nouveau.nonces.media);
					});

					self.dropzone_obj.on('error', function(file,response) {
						if ( file.accepted ) {
							if ( typeof response !== 'undefined' && typeof response.data !== 'undefined' && typeof response.data.feedback !== 'undefined' ) {
								$(file.previewElement).find('.dz-error-message span').text(response.data.feedback);
							}
						} else {
							self.dropzone_obj.removeFile(file);
						}
					});

					self.dropzone_obj.on('success', function(file, response) {
						if ( response.data.id ) {
							file.id = response.id;
							response.data.uuid = file.upload.uuid;
							response.data.menu_order = self.dropzone_media.length;
							response.data.album_id = self.album_id;
							response.data.group_id = self.group_id;
							response.data.saved    = false;
							self.dropzone_media.push( response.data );
							self.addMediaIdsToForumsForm();
						}
					});

					self.dropzone_obj.on('removedfile', function(file) {
						if ( self.dropzone_media.length ) {
							for ( var i in self.dropzone_media ) {
								if ( file.upload.uuid == self.dropzone_media[i].uuid  ) {

									if ( typeof self.dropzone_media[i].saved !== 'undefined' && ! self.dropzone_media[i].saved ) {
										self.removeAttachment(self.dropzone_media[i].id);
									}

									self.dropzone_media.splice( i, 1 );
									break;
								}
							}
						}
					});

					// container class to open close
					dropzone_container.removeClass('closed').addClass('open');

				} else {
					if ( self.dropzone_obj ) {
						self.dropzone_obj.destroy();
					}
					self.dropzone_media = [];
					dropzone_container.html('');
					dropzone_container.addClass('closed').removeClass('open');
				}

			}

		},

		openUploader: function(event) {
			var self = this;
			event.preventDefault();

			if ( typeof window.Dropzone !== 'undefined' && $('div#media-uploader').length ) {

				if ( $( event.currentTarget ).attr('id') !== 'bb-create-album' ) {
					$('#bp-media-uploader').show();
				}

				self.dropzone_obj = new Dropzone('div#media-uploader', self.options );

				self.dropzone_obj.on('sending', function(file, xhr, formData) {
					formData.append('action', 'media_upload');
					formData.append('_wpnonce', BP_Nouveau.nonces.media);
				});

				self.dropzone_obj.on('addedfile', function() {
					setTimeout(function(){
						if ( self.dropzone_obj.getAcceptedFiles().length ) {
							$('#bp-media-uploader-modal-status-text').text(wp.i18n.sprintf(wp.i18n.__('%d out of %d uploaded', 'buddyboss'), self.dropzone_media.length, self.dropzone_obj.getAcceptedFiles().length)).show();
						}
					},1000);
				});

				self.dropzone_obj.on('error', function(file,response) {
					if ( file.accepted ) {
						if ( typeof response !== 'undefined' && typeof response.data !== 'undefined' && typeof response.data.feedback !== 'undefined' ) {
							$(file.previewElement).find('.dz-error-message span').text(response.data.feedback);
						}
					} else {
						self.dropzone_obj.removeFile(file);
					}
				});

				self.dropzone_obj.on('queuecomplete', function() {
					$('#bp-media-uploader-modal-title').text(wp.i18n.__( 'Upload', 'buddyboss' ));
				});

				self.dropzone_obj.on('processing', function() {
					$('#bp-media-uploader-modal-title').text(wp.i18n.__( 'Uploading', 'buddyboss' ) + '...');
				});

				self.dropzone_obj.on('success', function(file, response) {
					if ( response.data.id ) {
						file.id = response.id;
						response.data.uuid = file.upload.uuid;
						response.data.menu_order = self.dropzone_media.length;
						response.data.album_id = self.album_id;
						response.data.group_id = self.group_id;
						response.data.saved    = false;
						self.dropzone_media.push( response.data );
					}
					$('#bp-media-add-more').show();
					$('#bp-media-submit').show();
					$('#bp-media-uploader-modal-title').text(wp.i18n.__( 'Uploading', 'buddyboss' ) + '...');
					$('#bp-media-uploader-modal-status-text').text(wp.i18n.sprintf( wp.i18n.__( '%d out of %d uploaded', 'buddyboss' ), self.dropzone_media.length, self.dropzone_obj.getAcceptedFiles().length )).show();
				});

				self.dropzone_obj.on('removedfile', function(file) {
					if ( self.dropzone_media.length ) {
						for ( var i in self.dropzone_media ) {
							if ( file.upload.uuid == self.dropzone_media[i].uuid ) {

								if ( typeof self.dropzone_media[i].saved !== 'undefined' && ! self.dropzone_media[i].saved ) {
									self.removeAttachment(self.dropzone_media[i].id);
								}

								self.dropzone_media.splice( i, 1 );
								break;
							}
						}
					}
					if ( ! self.dropzone_obj.getAcceptedFiles().length ) {
						$('#bp-media-uploader-modal-status-text').text('');
						$('#bp-media-add-more').hide();
						$('#bp-media-submit').hide();
					} else {
						$('#bp-media-uploader-modal-status-text').text(wp.i18n.sprintf( wp.i18n.__( '%d out of %d uploaded', 'buddyboss' ), self.dropzone_media.length, self.dropzone_obj.getAcceptedFiles().length )).show();
					}
				});
			}
		},

		removeAttachment: function( id ) {
			var data = {
				'action': 'media_delete_attachment',
				'_wpnonce': BP_Nouveau.nonces.media,
				'id': id
			};

			$.ajax({
				type: 'POST',
				url: BP_Nouveau.ajaxurl,
				data: data
			});
		},

		changeUploadModalTab: function(event) {
			event.preventDefault();

			var content_tab = $(event.currentTarget).data('content');
			$('.bp-media-upload-tab-content').hide();
			$('#'+content_tab).show();
			this.current_tab = content_tab;
			$(event.currentTarget).closest('#bp-media-uploader').find('.bp-media-upload-tab').removeClass('selected');
			$(event.currentTarget).addClass('selected');
			this.toggleSubmitMediaButton();
		},

		openCreateAlbumModal: function(event){
			event.preventDefault();

			this.openUploader(event);
			$('#bp-media-create-album').show();
		},

		closeCreateAlbumModal: function(event){
			event.preventDefault();

			this.closeUploader(event);
			$('#bp-media-create-album').hide();
			$('#bb-album-title').val('');
		},

		submitMedia: function(event) {
			event.preventDefault();
			var self = this, data;

			if ( self.current_tab === 'bp-dropzone-content' ) {

				var post_content = $('#bp-media-post-content').val();
				data = {
					'action': 'media_save',
					'_wpnonce': BP_Nouveau.nonces.media,
					'medias': self.dropzone_media,
					'content' : post_content
				};

				$('#bp-dropzone-content .bp-feedback').remove();

				$.ajax({
					type: 'POST',
					url: BP_Nouveau.ajaxurl,
					data: data,
					success: function (response) {
						if (response.success) {

							// It's the very first media, let's make sure the container can welcome it!
							if (!$('#media-stream ul.media-list').length) {
								$('#media-stream').html($('<ul></ul>').addClass('media-list item-list bp-list bb-photo-list grid'));
								$('.bb-photos-actions').show();
							}

							// Prepend the activity.
							bp.Nouveau.inject('#media-stream ul.media-list', response.data.media, 'prepend');
							
							for( var i = 0; i < self.dropzone_media.length; i++ ) {
								self.dropzone_media[i].saved = true;
							}
							
							self.closeUploader(event);
						} else {
							$('#bp-dropzone-content').prepend(response.data.feedback);
						}
					}
				});

			} else if ( self.current_tab === 'bp-existing-media-content' ) {
				var selected = [];
				$('.bp-existing-media-wrap .bb-media-check-wrap [name="bb-media-select"]:checked').each(function() {
					selected.push($(this).val());
				});
				data = {
					'action': 'media_move_to_album',
					'_wpnonce': BP_Nouveau.nonces.media,
					'medias': selected,
					'album_id' : self.album_id,
					'group_id' : self.group_id
				};

				$('#bp-existing-media-content .bp-feedback').remove();

				$.ajax({
					type: 'POST',
					url: BP_Nouveau.ajaxurl,
					data: data,
					success: function (response) {
						if (response.success) {

							// It's the very first media, let's make sure the container can welcome it!
							if (!$('#media-stream ul.media-list').length) {
								$('#media-stream').html($('<ul></ul>').addClass('media-list item-list bp-list bb-photo-list grid'));
								$('.bb-photos-actions').show();
							}

							// Prepend the activity.
							bp.Nouveau.inject('#media-stream ul.media-list', response.data.media, 'prepend');

							// remove selected media from existing media list
							$('.bp-existing-media-wrap .bb-media-check-wrap [name="bb-media-select"]:checked').each(function() {
								if ( $(this).closest('li').data('id') === $(this).val() ) {
									$(this).closest('li').remove();
								}
							});

							self.closeUploader(event);
						} else {
							$('#bp-existing-media-content').prepend(response.data.feedback);
						}

					}
				});
			} else if ( ! self.current_tab ) {
				self.closeUploader(event);
			}

		},

		saveAlbum: function(event) {
			var target = $( event.currentTarget ), self = this, title = $('#bb-album-title'), privacy = $('#bb-album-privacy');
			event.preventDefault();

			if( $.trim(title.val()) === '' ) {
				title.addClass('error');
				return false;
			} else {
				title.removeClass('error');
			}

			if( ! self.group_id && $.trim(privacy.val()) === '' ) {
				privacy.addClass('error');
				return false;
			} else {
				privacy.removeClass('error');
			}

			target.prop('disabled',true);

			var data = {
				'action': 'media_album_save',
				'_wpnonce': BP_Nouveau.nonces.media,
				'title': title.val(),
				'medias': self.dropzone_media,
				'privacy': privacy.val()
			};

			if ( self.album_id ) {
				data.album_id = self.album_id;
			}

			if ( self.group_id ) {
				data.group_id = self.group_id;
			}

			//remove all feedback erros from the DOM
			$('.bb-single-album-header .bp-feedback').remove();
			$('#boss-media-create-album-popup .bp-feedback').remove();

			$.ajax({
				type: 'POST',
				url: BP_Nouveau.ajaxurl,
				data: data,
				success: function (response) {
					setTimeout(function () {
						target.prop('disabled',false);
					},500);
					if ( response.success ) {
						if ( self.album_id ) {
							$('#bp-single-album-title').text(title.val());
							$('#bb-album-privacy').val(privacy.val());
							self.cancelEditAlbumTitle(event);
						} else {
							$('#buddypress .bb-albums-list').prepend(response.data.album);
							//self.closeCreateAlbumModal(event);
							window.location.href = response.data.redirect_url;
						}
					} else {
						if ( self.album_id ) {
							$('#bp-media-single-album').prepend(response.data.feedback);
						} else {
							$('#boss-media-create-album-popup .bb-model-header').after(response.data.feedback);
						}
					}
				}
			});

		},

		deleteAlbum: function(event) {
			event.preventDefault();

			if ( ! this.album_id ) {
				return false;
			}

			if ( ! confirm( wp.i18n.__( 'Are you sure you want to delete this album? Photos in this album will also be deleted.', 'buddyboss' ) ) ) {
				return false;
			}

			$(event.currentTarget).prop('disabled',true);

			var data = {
				'action': 'media_album_delete',
				'_wpnonce': BP_Nouveau.nonces.media,
				'album_id': this.album_id,
				'group_id': this.group_id
			};

			$.ajax({
				type: 'POST',
				url: BP_Nouveau.ajaxurl,
				data: data,
				success: function (response) {
					if ( response.success ) {
						window.location.href = response.data.redirect_url;
					} else {
						alert( wp.i18n.__( 'There was a problem deleting the album.', 'buddyboss' ) );
						$(event.currentTarget).prop('disabled',false);
					}
				}
			});

		},

		addMediaIdsToForumsForm: function() {
			var self = this;
			if( $('#bbp_media').length ) {
				$('#bbp_media').val(JSON.stringify(self.dropzone_media));
			}
		},

		/**
		 * [injectQuery description]
		 * @param  {[type]} event [description]
		 * @return {[type]}       [description]
		 */
		injectMedias: function( event ) {
			var store = bp.Nouveau.getStorage( 'bp-media' ),
				scope = store.scope || null, filter = store.filter || null;

			if ( $( event.currentTarget ).hasClass( 'load-more' ) ) {
				var next_page = ( Number( this.current_page ) * 1 ) + 1, self = this, search_terms = '';

				// Stop event propagation
				event.preventDefault();

				$( event.currentTarget ).find( 'a' ).first().addClass( 'loading' );

				if ( $( '#buddypress .dir-search input[type=search]' ).length ) {
					search_terms = $( '#buddypress .dir-search input[type=search]' ).val();
				}

				bp.Nouveau.objectRequest( {
					object              : 'media',
					scope               : scope,
					filter              : filter,
					search_terms        : search_terms,
					page                : next_page,
					method              : 'append',
					target              : '#buddypress [data-bp-list] ul.bp-list'
				} ).done( function( response ) {
					if ( true === response.success ) {
						$( event.currentTarget ).remove();

						// Update the current page
						self.current_page = next_page;
					}
				} );
			}
		},

		/**
		 * [appendQuery description]
		 * @param  {[type]} event [description]
		 * @return {[type]}       [description]
		 */
		appendMedia: function( event ) {
			var store = bp.Nouveau.getStorage( 'bp-media' ),
				scope = store.scope || null, filter = store.filter || null;

			if ( $( event.currentTarget ).hasClass( 'load-more' ) ) {
				var next_page = ( Number( this.current_page_existing_media ) * 1 ) + 1, self = this, search_terms = '';

				// Stop event propagation
				event.preventDefault();

				$( event.currentTarget ).find( 'a' ).first().addClass( 'loading' );

				if ( $( '#buddypress .dir-search input[type=search]' ).length ) {
					search_terms = $( '#buddypress .dir-search input[type=search]' ).val();
				}

				bp.Nouveau.objectRequest( {
					object              : 'media',
					scope               : scope,
					filter              : filter,
					search_terms        : search_terms,
					page                : next_page,
					method              : 'append',
					caller              : 'bp-existing-media',
					target              : '.bp-existing-media-wrap ul.bp-list'
				} ).done( function( response ) {
					if ( true === response.success ) {
						$( event.currentTarget ).remove();

						// Update the current page
						self.current_page_existing_media = next_page;
					}
				} );
			}
		},

		/**
		 * [appendQuery description]
		 * @param  {[type]} event [description]
		 * @return {[type]}       [description]
		 */
		appendAlbums: function( event ) {
			var next_page = ( Number( this.current_page_albums ) * 1 ) + 1, self = this;

			// Stop event propagation
			event.preventDefault();

			$( event.currentTarget ).find( 'a' ).first().addClass( 'loading' );

			var data = {
				'action': 'media_albums_loader',
				'_wpnonce': BP_Nouveau.nonces.media,
				'page'      : next_page
			};

			$.ajax({
				type: 'POST',
				url: BP_Nouveau.ajaxurl,
				data: data,
				success: function (response) {
					if ( true === response.success ) {
						$( event.currentTarget ).remove();
						$( '#albums-dir-list ul.bb-albums-list' ).fadeOut( 100, function() {
							$( '#albums-dir-list ul.bb-albums-list' ).append( response.data.albums );
							$( this ).fadeIn( 100 );
						} );
						// Update the current page
						self.current_page_albums = next_page;
					}
				}
			});
		},

		toggleSubmitMediaButton: function() {
			var submit_media_button = $('#bp-media-submit'), add_more_button = $('#bp-media-add-more');
			if ( this.current_tab === 'bp-dropzone-content' ) {
				if ( this.dropzone_obj.getAcceptedFiles().length ) {
					submit_media_button.show();
					add_more_button.show();
				} else {
					submit_media_button.hide();
					add_more_button.hide();
				}
			} else if ( this.current_tab === 'bp-existing-media-content' ) {
				if ( $('.bp-existing-media-wrap .bb-media-check-wrap [name="bb-media-select"]:checked').length ) {
					submit_media_button.show();
				} else {
					submit_media_button.hide();
				}
				add_more_button.hide();
			}
		}
	};

	// Launch BP Nouveau Media
	bp.Nouveau.Media.start();

} )( bp, jQuery );
