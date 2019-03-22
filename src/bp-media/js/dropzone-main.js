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
	bp.Dropzone_Main = {

		/**
		 * [start description]
		 * @return {[type]} [description]
		 */
		start: function() {
			this.setupGlobals();
			var self = this;

			var dropzone = bp.Dropzone.mount('div#media-uploader');

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
		},

		/**
		 * [setupGlobals description]
		 * @return {[type]} [description]
		 */
		setupGlobals: function() {
			this.dropzone_media = [];
		},

	};

	bp.Dropzone_Main.start();

} )( bp, jQuery );