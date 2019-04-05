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

		},

		/**
		 * [addListeners description]
		 */
		addListeners: function() {

			$( '.bp-nouveau' ).on( 'click', '#bp-add-media', this.openUploader.bind( this ) );
			$( '.bp-nouveau' ).on( 'click', '#bp-media-submit', this.submitMedia.bind( this ) );
			$( '.bp-nouveau' ).on( 'click', '#bp-media-uploader-close', this.closeUploader.bind( this ) );
			$( '.bp-nouveau' ).on( 'click', '#bb-delete-media', this.deleteMedia.bind( this ) );
			$( '.bp-nouveau' ).on( 'click', '#bb-select-all-media', this.selectAllMedia.bind( this ) );
			$( '.bp-nouveau' ).on( 'click', '#bb-deselect-all-media', this.deselectAllMedia.bind( this ) );

			// albums
			$( '.bp-nouveau' ).on( 'click', '#bb-create-album', this.openCreateAlbumModal.bind( this ) );
			$( '.bp-nouveau' ).on( 'click', '#bp-media-create-album-submit', this.saveAlbum.bind( this ) );
			$( '.bp-nouveau' ).on( 'click', '#bp-media-create-album-close', this.closeCreateAlbumModal.bind( this ) );

			$( '.bp-nouveau' ).on( 'click', '#bp-media-add-more', this.triggerDropzoneSelectFileDialog.bind( this ) );

			$( '#bp-media-uploader' ).on( 'click', '.bp-media-upload-tab', this.changeUploadModalTab.bind( this ) );

			// Fetch Media
			$( '.bp-nouveau [data-bp-list="media"]' ).on( 'click', 'li.load-more', this.injectMedias.bind( this ) );
			$( '.bp-existing-media-wrap' ).on( 'click', 'li.load-more', this.appendMedia.bind( this ) );
			$( '.bp-existing-media-wrap' ).on( 'change', '.bb-media-check-wrap [name="bb-media-select"]', this.toggleSubmitMediaButton.bind( this ) );

			//single album
			$( '.bp-nouveau' ).on( 'click', '#bp-edit-album-title', this.editAlbumTitle.bind( this ) );
			$( '.bp-nouveau' ).on( 'click', '#bp-cancel-edit-album-title', this.cancelEditAlbumTitle.bind( this ) );
			$( '.bp-nouveau' ).on( 'click', '#bp-save-album-title', this.saveAlbum.bind( this ) );
			$( '.bp-nouveau' ).on( 'change', '#bp-media-single-album select#bb-album-privacy', this.saveAlbum.bind( this ) );
			$( '.bp-nouveau' ).on( 'click', '#bb-delete-album', this.deleteAlbum.bind( this ) );

		},

		deleteMedia: function(event) {
			event.preventDefault();

			var media = [];
			$('#buddypress').find('.media-list:not(.existing-media-list)').find('.bb-media-check-wrap [name="bb-media-select"]:checked').each(function(){
				media.push($(this).val());
			});

			if ( media.length == 0 ) {
				return false;
			}

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
					if (response.success) {

						$('#buddypress').find('.media-list:not(.existing-media-list)').find('.bb-media-check-wrap [name="bb-media-select"]:checked').each(function(){
							$(this).closest('li').remove();
						});
					}

				}
			});
		},

		selectAllMedia: function(event) {
			event.preventDefault();

			$('#buddypress').find('.media-list:not(.existing-media-list)').find('.bb-media-check-wrap [name="bb-media-select"]').each(function(){
				$(this).prop('checked',true);
			});
		},

		deselectAllMedia: function(event) {
			event.preventDefault();

			$('#buddypress').find('.media-list:not(.existing-media-list)').find('.bb-media-check-wrap [name="bb-media-select"]').each(function(){
				$(this).prop('checked',false);
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
			$('#bp-media-uploader-modal-title').text('Upload');
			$('#bp-media-uploader-modal-status-text').text('');
			this.dropzone_obj.destroy();
			this.dropzone_media = [];

		},

		openUploader: function(event) {
			event.preventDefault();
			var self = this;

			if ( typeof window.Dropzone !== 'undefined' && $('div#media-uploader').length ) {

				$('#bp-media-uploader').show();

				self.dropzone_obj = new Dropzone('div#media-uploader', self.options );

				self.dropzone_obj.on('sending', function(file, xhr, formData) {
					formData.append('action', 'media_upload');
					formData.append('_wpnonce', BP_Nouveau.nonces.media);
				});

				self.dropzone_obj.on('addedfile', function() {
					$('#bp-media-uploader-modal-title').text('Uploading...');
					$('#bp-media-uploader-modal-status-text').text(self.dropzone_media.length + ' out of ' + self.dropzone_obj.getAcceptedFiles().length + ' uploaded').show();
				});

				self.dropzone_obj.on('queuecomplete', function() {
					$('#bp-media-uploader-modal-title').text('Upload');
				});

				self.dropzone_obj.on('success', function(file, response) {
					if ( response.data.id ) {
						file.id = response.id;
						response.data.uuid = file.upload.uuid;
						response.data.menu_order = self.dropzone_media.length;
						response.data.album_id = self.album_id;
						self.dropzone_media.push( response.data );
						self.addMediaIdsToForumsForm();
					}
					$('#bp-media-add-more').show();
					$('#bp-media-submit').show();
					$('#bp-media-uploader-modal-title').text('Uploading...');
					$('#bp-media-uploader-modal-status-text').text(self.dropzone_media.length + ' out of ' + self.dropzone_obj.getAcceptedFiles().length + ' uploaded').show();
				});

				self.dropzone_obj.on('removedfile', function(file) {
					if ( self.dropzone_media.length ) {
						for ( var i in self.dropzone_media ) {
							if ( file.upload.uuid == self.dropzone_media[i].uuid ) {
								//self.removeAttachment(self.dropzone_media[i].id);
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
						$('#bp-media-uploader-modal-status-text').text(self.dropzone_media.length + ' out of ' + self.dropzone_obj.getAcceptedFiles().length + ' uploaded').show();
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
			this.toggleSubmitMediaButton();
		},

		openCreateAlbumModal: function(event){
			event.preventDefault();

			$('#bp-media-create-album').show();
		},

		closeCreateAlbumModal: function(event){
			event.preventDefault();

			$('#bp-media-create-album').hide();
			$('#bb-album-title').val('');
			$('#bb-album-description').val('');
		},

		submitMedia: function(event) {
			event.preventDefault();
			var self = this, data;

			if ( self.current_tab === 'bp-dropzone-content' ) {
				var post_content = $('#bp-media-post-content').val();
				if ( $.trim(post_content) === '' ) {
					$('#bp-media-post-content').addClass('error').focus();
					return false;
				} else {
					$('#bp-media-post-content').removeClass('error');
				}
				data = {
					'action': 'media_save',
					'_wpnonce': BP_Nouveau.nonces.media,
					'medias': self.dropzone_media,
					'content' : post_content
				};

				$.ajax({
					type: 'POST',
					url: BP_Nouveau.ajaxurl,
					data: data,
					success: function (response) {
						if (response.success) {

							// It's the very first media, let's make sure the container can welcome it!
							if (!$('#media-stream ul.media-list').length) {
								$('#media-stream').html($('<ul></ul>').addClass('media-list item-list bp-list bb-photo-list grid'));
							}

							// Prepend the activity.
							bp.Nouveau.inject('#media-stream ul.media-list', response.data.media, 'prepend');
							self.closeUploader(event);
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
					'album_id' : self.album_id
				};
				$.ajax({
					type: 'POST',
					url: BP_Nouveau.ajaxurl,
					data: data,
					success: function (response) {
						if (response.success) {

							// It's the very first media, let's make sure the container can welcome it!
							if (!$('#media-stream ul.media-list').length) {
								$('#media-stream').html($('<ul></ul>').addClass('media-list item-list bp-list bb-photo-list grid'));
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
						}

					}
				});
			} else if ( ! self.current_tab ) {
				self.closeUploader(event);
			}

		},

		saveAlbum: function(event) {
			event.preventDefault();
			var self = this, title = $('#bb-album-title'), privacy = $('#bb-album-privacy');

			if( $.trim(title.val()) === '' ) {
				title.addClass('error');
				return false;
			}

			if( $.trim(privacy.val()) === '' ) {
				privacy.addClass('error');
				return false;
			}

			var data = {
				'action': 'media_album_save',
				'_wpnonce': BP_Nouveau.nonces.media,
				'title': title.val(),
				'description': $('#bb-album-description').val(),
				'privacy': privacy.val()
			};

			if ( self.album_id ) {
				data['album_id'] = self.album_id;
			}

			$.ajax({
				type: 'POST',
				url: BP_Nouveau.ajaxurl,
				data: data,
				success: function (response) {
					if ( response.success ) {
						if ( self.album_id ) {
							$('#bp-single-album-title').text(title.val());
							$('#bb-album-privacy').val(privacy.val());
							self.cancelEditAlbumTitle(event);
						} else {
							$('#buddypress .bb-albums-list').prepend(response.data.album);
							self.closeCreateAlbumModal(event);
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

			var data = {
				'action': 'media_album_delete',
				'_wpnonce': BP_Nouveau.nonces.media,
				'album_id': this.album_id
			};

			$.ajax({
				type: 'POST',
				url: BP_Nouveau.ajaxurl,
				data: data,
				success: function (response) {
					if ( response.success ) {
						window.location.href = response.data.redirect_url;
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

		toggleSubmitMediaButton: function() {
			var submit_media_button = $('#bp-media-submit'), add_more_button = $('#bp-media-add-more');
			if ( this.current_tab === 'bp-dropzone-content' ) {
				if ( this.dropzone_media.length ) {
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
