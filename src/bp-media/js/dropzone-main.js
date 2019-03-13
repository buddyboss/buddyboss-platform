jQuery(document).ready( function() {

	if ( typeof window.Dropzone !== 'undefined' ) {
		var dropzone = new Dropzone('div#media-uploader', dropzone_options );

		dropzone.on('sending', function(file, xhr, formData) {
			formData.append('action', 'media_upload');
			formData.append('_wpnonce', BP_Nouveau.nonces.media);
		});

		dropzone.on('success', function(file, response) {
			if ( response.data.id ) {
				file.id = response.id;
				response.uuid = file.upload.uuid;
				response.menu_order = self.media.length;
				self.media.push( response.data );
				self.model.set( 'media', self.media );
			}
		});

		dropzone.on('removedfile', function(file) {
			var self = this;
			if ( self.media.length ) {
				for ( var i in self.media ) {
					if ( file.id == self.media[i].id ) {
						self.media.splice( i, 1 );
						self.model.set( 'media', self.media );
					}
				}
			}
		});
	}

});