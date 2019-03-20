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

			this.mount('div#media-uploader');

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

		},

		/**
		 * [addListeners description]
		 */
		addListeners: function() {

		},

		/**
		 * [mount description]
		 * @param  {[type]} element_id [description]
		 * @return {[type]}      [description]
		 */
		mount: function( element_id ) {

			if ( typeof window.Dropzone !== 'undefined' && $(element_id).length ) {

				var dropzone = new Dropzone(element_id, this.options );

				dropzone.on('sending', function(file, xhr, formData) {
					formData.append('action', 'media_upload');
					formData.append('_wpnonce', BP_Nouveau.nonces.media);
				});

				dropzone.on('success', function(file, response) {
					if ( response.data.id ) {
						file.id = response.id;
						response.uuid = file.upload.uuid;
						response.menu_order = self.dropzone_media.length;
						self.dropzone_media.push( response.data );
					}
				});

				dropzone.on('removedfile', function(file) {
					if ( self.dropzone_media.length ) {
						for ( var i in self.dropzone_media ) {
							if ( file.id == self.dropzone_media[i].id ) {
								self.dropzone_media.splice( i, 1 );
							}
						}
					}
				});

				return dropzone;
			}

		}
	};

	// Launch BP Nouveau Media
	bp.Nouveau.Media.start();

} )( bp, jQuery );
