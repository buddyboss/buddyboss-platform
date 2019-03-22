/* jshint browser: true */
/* global bp, BP_Nouveau */
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

			// set up dropzones auto discover to false so it does not automatically set dropzones
			window.Dropzone.autoDiscover = false;

			this.options = {
				url: BP_Nouveau.ajaxurl,
				timeout: 3 * 60 * 60 * 1000,
				acceptedFiles: 'image/*',
				autoProcessQueue: true,
				addRemoveLinks: true,
				uploadMultiple: false,
				maxFilesize: typeof BP_Nouveau.media.max_upload_size !== 'undefined' ? BP_Nouveau.media.max_upload_size : 2,
			};

			this.dropzone_obj = null;
			this.dropzone_media = [];
			this.album_id = typeof BP_Nouveau.media.album_id !== 'undefined' ? BP_Nouveau.media.album_id : false;

		},

		/**
		 * [addListeners description]
		 */
		addListeners: function() {

			$( '#buddypress' ).on( 'click', '#bp-add-media', this.openUploader.bind( this ) );
			$( '#buddypress' ).on( 'click', '#bp-media-submit', this.submitMedia.bind( this ) );
			$( '#buddypress' ).on( 'click', '#bp-media-uploader-close', this.closeUploader.bind( this ) );

			$( '#buddypress' ).on( 'click', '#bb-create-album', this.openCreateAlbumModal.bind( this ) );
			$( '#buddypress' ).on( 'click', '#bp-media-create-album-submit', this.submitAlbum.bind( this ) );
			$( '#buddypress' ).on( 'click', '#bp-media-create-album-close', this.closeCreateAlbumModal.bind( this ) );

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

				self.dropzone_obj.on('addedfile', function(file) {
					$('#bp-media-uploader-modal-title').text('Uploading...');
					$('#bp-media-uploader-modal-status-text').text(self.dropzone_media.length + ' out of ' + self.dropzone_obj.getAcceptedFiles().length + ' uploaded').show();
				});

				self.dropzone_obj.on('queuecomplete', function(file, xhr, formData) {
					$('#bp-media-uploader-modal-title').text('Upload');
				});

				self.dropzone_obj.on('success', function(file, response) {
					if ( response.data.id ) {
						file.id = response.id;
						response.data.uuid = file.upload.uuid;
						response.data.menu_order = self.dropzone_media.length;
						response.data.album_id = self.album_id;
						self.dropzone_media.push( response.data );
					}
					$('#bp-media-add-more').show();
					$('#bp-media-uploader-modal-title').text('Uploading...');
					$('#bp-media-uploader-modal-status-text').text(self.dropzone_media.length + ' out of ' + self.dropzone_obj.getAcceptedFiles().length + ' uploaded').show();
				});

				self.dropzone_obj.on('removedfile', function(file) {
					if ( self.dropzone_media.length ) {
						for ( var i in self.dropzone_media ) {
							if ( file.upload.uuid == self.dropzone_media[i].uuid ) {
								self.dropzone_media.splice( i, 1 );
								break;
							}
						}
					}
					if ( ! self.dropzone_obj.getAcceptedFiles().length ) {
						$('#bp-media-uploader-modal-status-text').text('');
					} else {
						$('#bp-media-uploader-modal-status-text').text(self.dropzone_media.length + ' out of ' + self.dropzone_obj.getAcceptedFiles().length + ' uploaded').show();
					}
				});
			}
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
			var self = this;
			var data = {
				'action': 'media_save',
				'_wpnonce': BP_Nouveau.nonces.media,
				'medias': self.dropzone_media,
			};

			$.ajax({
				type: "POST",
				url: BP_Nouveau.ajaxurl,
				data: data,
				success: function (response) {
					if ( response.success ) {
						$('.bb-photo-list').prepend(response.data.media);
						self.closeUploader(event);
					}

				},
			});
			
		},

		submitAlbum: function(event) {
			event.preventDefault();
			var self = this;
			var data = {
				'action': 'media_album_save',
				'_wpnonce': BP_Nouveau.nonces.media,
				'title': $('#bb-album-title').val(),
				'description': $('#bb-album-description').val(),
				'privacy': $('#bb-album-privacy').val(),
			};

			$.ajax({
				type: "POST",
				url: BP_Nouveau.ajaxurl,
				data: data,
				success: function (response) {
					if ( response.success ) {
						$('.bb-album-list').prepend(response.data.album);
						self.closeCreateAlbumModal(event);
					}
				},
			});

		},
	};

	// Launch BP Nouveau Media
	bp.Nouveau.Media.start();

} )( bp, jQuery );
