var dropzone_options = {
	url:  BP_Nouveau.ajaxurl,
	timeout:3*60*60*1000,
	acceptedFiles: 'image/*',
	autoProcessQueue: true,
	addRemoveLinks: true,
	uploadMultiple: false,
	maxFilesize: typeof BP_Nouveau.media.max_upload_size !== 'undefined' ? BP_Nouveau.media.max_upload_size : 2,
};