/* jshint browser: true */
/* global bp, BP_Nouveau */
/* @version 1.0.0 */
window.bp = window.bp || {};

( function( exports, $ ) {

	/**
	 * [Media description]
	 * @type {Object}
	 */
	bp.Dropzone = {

		/**
		 * [start description]
		 * @return {[type]} [description]
		 */
		start: function() {
			this.setupGlobals();
		},

		/**
		 * [setupGlobals description]
		 * @return {[type]} [description]
		 */
		setupGlobals: function() {
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
		 * [mount description]
		 * @param  {[type]} element_id [description]
		 * @return {[type]}      [description]
		 */
		mount: function( element_id ) {

			if ( typeof window.Dropzone !== 'undefined' && $(element_id).length ) {

				console.log(this.options);

				var dropzone = new Dropzone(element_id, this.options );

				dropzone.on('sending', function(file, xhr, formData) {
					formData.append('action', 'media_upload');
					formData.append('_wpnonce', BP_Nouveau.nonces.media);
				});

				return dropzone;
			}

		}

	};

	bp.Dropzone.start();

} )( bp, jQuery );
