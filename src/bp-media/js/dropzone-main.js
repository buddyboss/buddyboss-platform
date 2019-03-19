jQuery(document).ready( function() {

	if ( typeof window.Dropzone !== 'undefined' ) {

		var dropzone_media = [];
		var dropzone = new Dropzone('div#media-uploader', dropzone_options );

		dropzone.on('sending', function(file, xhr, formData) {
			formData.append('action', 'media_upload');
			formData.append('_wpnonce', BP_Nouveau.nonces.media);
		});

		dropzone.on('success', function(file, response) {
			if ( response.data.id ) {
				file.id = response.id;
				response.uuid = file.upload.uuid;
				response.menu_order = dropzone_media.length;
				dropzone_media.push( response.data );
			}
		});

		dropzone.on('removedfile', function(file) {
			if ( dropzone_media.length ) {
				for ( var i in dropzone_media ) {
					if ( file.id == dropzone_media[i].id ) {
						dropzone_media.splice( i, 1 );
					}
				}
			}
		});
	}

});