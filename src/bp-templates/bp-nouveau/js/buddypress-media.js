/* jshint browser: true */
/* global bp, BP_Nouveau, JSON, Dropzone, videojs, bp_media_dropzone */
/* @version 1.0.0 */
window.bp = window.bp || {};

( function ( exports, $ ) {

	// Bail if not set.
	if ( typeof BP_Nouveau === 'undefined' ) {
		return;
	}

	bp.Nouveau = bp.Nouveau || {};

	/**
	 * [Media description]
	 *
	 * @type {Object}
	 */
	bp.Nouveau.Media = {

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

			// Init current page.
			this.current_page = 1;
			this.current_page_existing_media = 1;
			this.current_page_albums = 1;
			this.current_tab = bodySelector.hasClass( 'single-topic' ) || bodySelector.hasClass( 'single-forum' ) ? false : 'bp-dropzone-content';
			this.sort_by = '';
			this.order_by = '';
			this.currentTargetParent = BP_Nouveau.media.current_folder;
			this.moveToIdPopup = BP_Nouveau.media.move_to_id_popup;
			this.moveToTypePopup = BP_Nouveau.media.current_type;
			this.privacySelectorSelect = '';
			this.privacySelectorSpan = '';
			this.currentAlbum = BP_Nouveau.media.current_album;

			// set up dropzones auto discover to false so it does not automatically set dropzones.
			if ( typeof window.Dropzone !== 'undefined' ) {
				window.Dropzone.autoDiscover = false;
			}

			var ForumDocumentTemplates = document.getElementsByClassName('forum-post-document-template').length ? document.getElementsByClassName('forum-post-document-template')[0].innerHTML : ''; //Check to avoid error if Node is missing.

			this.documentOptions = {
				url: BP_Nouveau.ajaxurl,
				timeout: 3 * 60 * 60 * 1000,
				dictFileTooBig: BP_Nouveau.media.dictFileTooBig,
				acceptedFiles: BP_Nouveau.media.document_type,
				createImageThumbnails: false,
				dictDefaultMessage: BP_Nouveau.media.dropzone_document_message,
				autoProcessQueue: true,
				addRemoveLinks: true,
				uploadMultiple: false,
				maxFiles: typeof BP_Nouveau.document.maxFiles !== 'undefined' ? BP_Nouveau.document.maxFiles : 10,
				maxFilesize: typeof BP_Nouveau.document.max_upload_size !== 'undefined' ? BP_Nouveau.document.max_upload_size : 2,
				dictInvalidFileType: BP_Nouveau.document.dictInvalidFileType,
				dictMaxFilesExceeded: BP_Nouveau.media.document_dict_file_exceeded,
				previewTemplate: ForumDocumentTemplates,
				dictCancelUploadConfirmation: BP_Nouveau.media.dictCancelUploadConfirmation,
			};

			var ForumVideoTemplate = document.getElementsByClassName('forum-post-video-template').length ? document.getElementsByClassName('forum-post-video-template')[0].innerHTML : ''; //Check to avoid error if Node is missing.
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
				dictMaxFilesExceeded: BP_Nouveau.video.video_dict_file_exceeded,
				previewTemplate: ForumVideoTemplate,
				dictCancelUploadConfirmation: BP_Nouveau.video.dictCancelUploadConfirmation,
			};

			if ( $( '#bp-media-uploader' ).hasClass( 'bp-media-document-uploader' ) ) {
				var ForumDocumentTemplate = document.getElementsByClassName('forum-post-document-template').length ? document.getElementsByClassName('forum-post-document-template')[0].innerHTML : ''; //Check to avoid error if Node is missing.
				this.options = {
					url: BP_Nouveau.ajaxurl,
					timeout: 3 * 60 * 60 * 1000,
					dictFileTooBig: BP_Nouveau.media.dictFileTooBig,
					acceptedFiles: BP_Nouveau.media.document_type,
					createImageThumbnails: false,
					dictDefaultMessage: BP_Nouveau.media.dropzone_document_message,
					autoProcessQueue: true,
					addRemoveLinks: true,
					uploadMultiple: false,
					maxFiles: typeof BP_Nouveau.document.maxFiles !== 'undefined' ? BP_Nouveau.document.maxFiles : 10,
					maxFilesize: typeof BP_Nouveau.document.max_upload_size !== 'undefined' ? BP_Nouveau.document.max_upload_size : 2,
					dictInvalidFileType: bp_media_dropzone.dictInvalidFileType,
					dictMaxFilesExceeded: BP_Nouveau.media.document_dict_file_exceeded,
					previewTemplate: ForumDocumentTemplate,
					dictCancelUploadConfirmation: BP_Nouveau.media.dictCancelUploadConfirmation,
				};
			} else {
				var ForumMediaTemplate = document.getElementsByClassName('forum-post-media-template').length ? document.getElementsByClassName('forum-post-media-template')[0].innerHTML : ''; //Check to avoid error if Node is missing.
				this.options = {
					url: BP_Nouveau.ajaxurl,
					timeout: 3 * 60 * 60 * 1000,
					dictFileTooBig: BP_Nouveau.media.dictFileTooBig,
					dictDefaultMessage: BP_Nouveau.media.dropzone_media_message,
					acceptedFiles: 'image/*',
					autoProcessQueue: true,
					addRemoveLinks: true,
					uploadMultiple: false,
					maxFiles: typeof BP_Nouveau.media.maxFiles !== 'undefined' ? BP_Nouveau.media.maxFiles : 10,
					maxFilesize: typeof BP_Nouveau.media.max_upload_size !== 'undefined' ? BP_Nouveau.media.max_upload_size : 2,
					dictInvalidFileType: bp_media_dropzone.dictInvalidFileType,
					dictMaxFilesExceeded: BP_Nouveau.media.media_dict_file_exceeded,
					previewTemplate: ForumMediaTemplate,
					dictCancelUploadConfirmation: BP_Nouveau.media.dictCancelUploadConfirmation,
					maxThumbnailFilesize: typeof BP_Nouveau.media.max_upload_size !== 'undefined' ? BP_Nouveau.media.max_upload_size : 2,
				};
			}

			// if defined, add custom dropzone options.
			if ( typeof BP_Nouveau.media.dropzone_options !== 'undefined' ) {
				Object.assign( this.options, BP_Nouveau.media.dropzone_options );
			}

			this.dropzone_obj = [];
			this.dropzone_media = [];
			this.album_id = typeof BP_Nouveau.media.album_id !== 'undefined' ? BP_Nouveau.media.album_id : false;
			this.current_folder = typeof BP_Nouveau.media.current_folder !== 'undefined' ? BP_Nouveau.media.current_folder : false;
			this.current_group_id = typeof BP_Nouveau.media.current_group_id !== 'undefined' ? BP_Nouveau.media.current_group_id : false;
			this.group_id = typeof BP_Nouveau.media.group_id !== 'undefined' ? BP_Nouveau.media.group_id : false;
			this.bbp_is_reply_edit = typeof window.BP_Forums_Nouveau !== 'undefined' && typeof window.BP_Forums_Nouveau.media.bbp_is_reply_edit !== 'undefined' && window.BP_Forums_Nouveau.media.bbp_is_reply_edit;
			this.bbp_is_topic_edit = typeof window.BP_Forums_Nouveau !== 'undefined' && typeof window.BP_Forums_Nouveau.media.bbp_is_topic_edit !== 'undefined' && window.BP_Forums_Nouveau.media.bbp_is_topic_edit;
			this.bbp_is_forum_edit = typeof window.BP_Forums_Nouveau !== 'undefined' && typeof window.BP_Forums_Nouveau.media.bbp_is_forum_edit !== 'undefined' && window.BP_Forums_Nouveau.media.bbp_is_forum_edit;
			this.bbp_reply_edit_media = typeof window.BP_Forums_Nouveau !== 'undefined' && typeof window.BP_Forums_Nouveau.media.reply_edit_media !== 'undefined' ? window.BP_Forums_Nouveau.media.reply_edit_media : [];
			this.bbp_reply_edit_document = typeof window.BP_Forums_Nouveau !== 'undefined' && typeof window.BP_Forums_Nouveau.media.reply_edit_document !== 'undefined' ? window.BP_Forums_Nouveau.media.reply_edit_document : [];
			this.bbp_reply_edit_video = typeof window.BP_Forums_Nouveau !== 'undefined' && typeof window.BP_Forums_Nouveau.media.reply_edit_video !== 'undefined' ? window.BP_Forums_Nouveau.media.reply_edit_video : [];
			this.bbp_topic_edit_media = typeof window.BP_Forums_Nouveau !== 'undefined' && typeof window.BP_Forums_Nouveau.media.topic_edit_media !== 'undefined' ? window.BP_Forums_Nouveau.media.topic_edit_media : [];
			this.bbp_topic_edit_video = typeof window.BP_Forums_Nouveau !== 'undefined' && typeof window.BP_Forums_Nouveau.media.topic_edit_video !== 'undefined' ? window.BP_Forums_Nouveau.media.topic_edit_video : [];
			this.bbp_topic_edit_document = typeof window.BP_Forums_Nouveau !== 'undefined' && typeof window.BP_Forums_Nouveau.media.topic_edit_document !== 'undefined' ? window.BP_Forums_Nouveau.media.topic_edit_document : [];
			this.bbp_forum_edit_media = typeof window.BP_Forums_Nouveau !== 'undefined' && typeof window.BP_Forums_Nouveau.media.forum_edit_media !== 'undefined' ? window.BP_Forums_Nouveau.media.forum_edit_media : [];
			this.bbp_forum_edit_document = typeof window.BP_Forums_Nouveau !== 'undefined' && typeof window.BP_Forums_Nouveau.media.forum_edit_document !== 'undefined' ? window.BP_Forums_Nouveau.media.forum_edit_document : [];
			this.bbp_forum_edit_video = typeof window.BP_Forums_Nouveau !== 'undefined' && typeof window.BP_Forums_Nouveau.media.forum_edit_video !== 'undefined' ? window.BP_Forums_Nouveau.media.forum_edit_video : [];
			this.bbp_reply_edit_gif_data = typeof window.BP_Forums_Nouveau !== 'undefined' && typeof window.BP_Forums_Nouveau.media.reply_edit_gif_data !== 'undefined' ? window.BP_Forums_Nouveau.media.reply_edit_gif_data : [];
			this.bbp_topic_edit_gif_data = typeof window.BP_Forums_Nouveau !== 'undefined' && typeof window.BP_Forums_Nouveau.media.topic_edit_gif_data !== 'undefined' ? window.BP_Forums_Nouveau.media.topic_edit_gif_data : [];
			this.bbp_forum_edit_gif_data = typeof window.BP_Forums_Nouveau !== 'undefined' && typeof window.BP_Forums_Nouveau.media.forum_edit_gif_data !== 'undefined' ? window.BP_Forums_Nouveau.media.forum_edit_gif_data : [];

			this.giphy = null;
			this.gif_offset = 0;
			this.gif_q = null;
			this.gif_limit = 20;
			this.gif_requests = [];
			this.gif_data = [];
			this.gif_container_key = false;

			// Draft variables.
			this.reply_topic_allow_delete_media = false;
			this.reply_topic_display_post = 'edit';


			// Text File Activity Preview.
			bp.Nouveau.Media.documentCodeMirror();

			$( window ).on(
				'scroll resize',
				function () {
					bp.Nouveau.Media.documentCodeMirror();
				}
			);
		},

		/**
		 * [addListeners description]
		 */
		addListeners: function () {

			var bpNouveau = $( '.bp-nouveau' );
			var mediaWrap = $( '.bp-existing-media-wrap' );

			bpNouveau.on( 'click', '#bp-add-media', this.openUploader.bind( this ) );
			bpNouveau.on( 'click', '#bp-add-document', this.openDocumentUploader.bind( this ) );
			bpNouveau.on( 'click', '#bp-media-submit', this.submitMedia.bind( this ) );
			bpNouveau.on( 'click', '#bp-media-document-submit', this.submitDocumentMedia.bind( this ) );
			bpNouveau.on( 'click', '#bp-media-uploader-close', this.closeUploader.bind( this ) );
			bpNouveau.on( 'click', '#bb-delete-media', this.deleteMedia.bind( this ) );
			bpNouveau.on( 'click', '#bb-select-deselect-all-media', this.toggleSelectAllMedia.bind( this ) );
			$( '#buddypress [data-bp-list="media"]' ).on( 'bp_ajax_request', this.bp_ajax_media_request );

			// albums.
			bpNouveau.on( 'click', '#bb-create-album', this.openCreateAlbumModal.bind( this ) );
			$( document ).on( 'click', '#bb-create-folder', this.openCreateFolderModal.bind( this ) );
			$( document ).on( 'click', '#bb-create-folder-child', this.openCreateFolderChildModal.bind( this ) );
			$( document ).on( 'click', '#bp-edit-folder-open', this.openEditFolderChildModal.bind( this ) );

			$( document ).on( 'click', '#bp-media-create-album-submit', this.saveAlbum.bind( this ) );
			$( document ).on( 'click', '#bp-media-create-folder-submit', this.saveFolder.bind( this ) );
			$( document ).on( 'click', '#bp-media-create-child-folder-submit', this.saveChildFolder.bind( this ) );

			bpNouveau.on( 'click', '#bp-media-create-album-close', this.closeCreateAlbumModal.bind( this ) );
			$( document ).on( 'click', '#bp-media-create-folder-close', this.closeCreateFolderModal.bind( this ) );
			$( document ).on( 'click', '#bp-media-edit-folder-close', this.closeEditFolderModal.bind( this ) );
			$( document ).on( 'click', '.open-popup .errorPopup', this.closeErrorPopup.bind( this ) );

			bpNouveau.on( 'click', '#bp-media-add-more', this.triggerDropzoneSelectFileDialog.bind( this ) );

			$( '#bp-media-uploader' ).on( 'click', '.bp-media-upload-tab', this.changeUploadModalTab.bind( this ) );

			// Fetch Media.
			$( '.bp-nouveau [data-bp-list="media"]' ).on( 'click', 'li.load-more', this.injectMedias.bind( this ) );
			$( '.bp-nouveau #albums-dir-list' ).on( 'click', 'li.load-more', this.appendAlbums.bind( this ) );
			mediaWrap.on( 'click', 'li.load-more', this.appendMedia.bind( this ) );
			bpNouveau.on( 'change', '.bb-media-check-wrap [name="bb-media-select"]', this.addSelectedClassToWrapper.bind( this ) );
			mediaWrap.on( 'change', '.bb-media-check-wrap [name="bb-media-select"]', this.toggleSubmitMediaButton.bind( this ) );

			// single album.
			bpNouveau.on( 'click', '#bp-edit-album-title', this.editAlbumTitle.bind( this ) );
			$( document ).on( 'click', '#bp-edit-folder-title', this.editFolderTitle.bind( this ) );
			bpNouveau.on( 'click', '#bp-cancel-edit-album-title', this.cancelEditAlbumTitle.bind( this ) );
			bpNouveau.on( 'click', '#bp-save-album-title', this.saveAlbum.bind( this ) );
			$( document ).on( 'click', '#bp-save-folder-title', this.saveFolder.bind( this ) );
			bpNouveau.on( 'change', '#bp-media-single-album select#bb-album-privacy', this.saveAlbum.bind( this ) );
			bpNouveau.on( 'change', '#media-stream select#bb-folder-privacy', this.savePrivacy.bind( this ) );
			bpNouveau.on( 'click', '#bb-delete-album', this.deleteAlbum.bind( this ) );
			$( document ).on( 'click', '#bb-delete-folder', this.deleteFolder.bind( this ) );

			$( document ).on( 'click', 'ul.document-nav li', this.resetPageDocumentDirectory.bind( this ) );
			$( document ).on( 'click', 'ul.document-nav li a', this.resetPageDocumentDirectory.bind( this ) );

			// forums.
			$( document ).on( 'click', '#forums-media-button', this.openForumsUploader.bind( this ) );
			$( document ).on( 'click', '#forums-document-button', this.openForumsDocumentUploader.bind( this ) );
			$( document ).on( 'click', '#forums-video-button', this.openForumsVideoUploader.bind( this ) );
			$( document ).on( 'click', '#forums-gif-button', this.toggleGifSelector.bind( this ) );
			$( document ).find( 'form #whats-new-toolbar, .forum form #whats-new-toolbar' ).on( 'keydown', '.search-query-input', this.searchGif.bind( this ) );
			$( document ).on( 'click', '.bbpress-forums-activity #whats-new-toolbar .found-media-item', this.selectGif.bind( this ) );
			$( document ).find( 'form #whats-new-toolbar, .forum form #whats-new-toolbar' ).on( 'click', '.found-media-item', this.selectGif.bind( this ) );
			$( document ).find( 'form #whats-new-toolbar .gif-search-results, .forum form #whats-new-toolbar .gif-search-results' ).scroll( this.loadMoreGif.bind( this ) );
			if ( !$( '.buddypress.groups.messages' ).length ) {
				$( document ).find( 'form #whats-new-toolbar, .forum form #whats-new-toolbar' ).on( 'click', '.found-media-item', this.selectGif.bind( this ) );
			}
			$( document ).find( 'form #whats-new-attachments .forums-attached-gif-container .gif-search-results, .forum form #whats-new-attachments .forums-attached-gif-container .gif-search-results' ).scroll( this.loadMoreGif.bind( this ) );
			$( document ).find( 'form #whats-new-attachments .forums-attached-gif-container, .forum form #whats-new-attachments .forums-attached-gif-container' ).on( 'click', '.gif-image-remove', this.removeSelectedGif.bind( this ) );

			$( document ).on( 'click', '.gif-image-container', this.playVideo.bind( this ) );

			// Documents.
			$( document ).on( 'click', '.directory.document  .media-folder_action__anchor, .directory.document  .media-folder_action__anchor li a, .bb-media-container .media-folder_action__anchor, .bb-media-container  .media-folder_action__list li a', this.fileActionButton.bind( this ) );
			$( document ).on( 'click', '.bb-activity-media-elem .copy_download_file_url a, .media-folder_action__list .copy_download_file_url a, .media .bb-photo-thumb .copy_download_file_url a', this.copyDownloadLink.bind( this ) );
			$( document ).on( 'click', '.bb-activity-media-elem.media-activity .media-action-wrap .media-action_more, #media-stream.media .bb-photo-thumb .media-action-wrap .media-action_more, .bb-activity-media-elem.document-activity .document-action-wrap .document-action_more, .bb-activity-media-elem.document-activity .document-action-wrap .document-action_list li a', this.fileActivityActionButton.bind( this ) );
			$( document ).click( this.toggleFileActivityActionButton );
			$( document ).on( 'click', '.bb-activity-media-elem.document-activity .document-expand .document-expand-anchor, .bb-activity-media-elem.document-activity .document-action-wrap .document-action_collapse', this.toggleCodePreview.bind( this ) );
			$( document ).on( 'click', '.activity .bp-document-move-activity, #media-stream .bp-document-move-activity', this.moveDocumentIntoFolder.bind( this ) );
			$( document ).on( 'click', '.bp-nouveau [data-bp-list="document"] .pager .dt-more-container.load-more', this.injectDocuments.bind( this ) );
			$( document ).on( 'click', '.bp-nouveau [data-bp-list="document"] .data-head', this.sortDocuments.bind( this ) );
			$( document ).on( 'click', '.modal-container .bb-field-steps-actions', this.documentPopupNavigate.bind( this ) );
			$( document ).on( 'click', '.bp-media-document-uploader .modal-container .bb-field-uploader-actions', this.uploadDocumentNavigate.bind( this ) );
			$( document ).on( 'click', '.bp-media-photo-uploader .modal-container .bb-field-uploader-actions', this.uploadMediaNavigate.bind( this ) );
			$( document ).on( 'click', '.modal-container #bp-media-edit-child-folder-submit', this.renameChildFolder.bind( this ) );

			// Media
			$( document ).on( 'click', '.activity .bp-media-move-activity, #media-stream .bp-media-move-activity', this.moveMediaIntoAlbum.bind( this ) );

			// Document move option.
			var mediaStream = $( '#bb-media-model-container .activity-list, #media-stream' );
			$( '#buddypress .activity-list, #buddypress [data-bp-list="activity"], #bb-media-model-container .activity-list, #media-stream' ).on( 'click', '.ac-document-move, .ac-folder-move', this.openDocumentMove.bind( this ) );
			$( '#buddypress .activity-list, #buddypress [data-bp-list="activity"], #bb-media-model-container .activity-list, #media-stream, .group-media #media-stream' ).on( 'click', '.ac-media-move', this.openMediaMove.bind( this ) );
			$( '#buddypress .activity-list, #buddypress [data-bp-list="activity"], #bb-media-model-container .activity-list, #media-stream' ).on( 'click', '.ac-document-close-button, .ac-folder-close-button', this.closeDocumentMove.bind( this ) );
			$( '#buddypress .activity-list, #buddypress [data-bp-list="activity"], #bb-media-model-container .activity-list, #media-stream' ).on( 'click', '.ac-media-close-button', this.closeMediaMove.bind( this ) );
			mediaStream.on( 'click', '.ac-document-rename', this.renameDocument.bind( this ) );
			mediaStream.on( 'click', '.ac-document-privacy', this.editPrivacyDocument.bind( this ) );
			//mediaStream.on( 'mouseup', '#bb-folder-privacy', this.editPrivacyDocumentSubmit.bind( this ) );
			mediaStream.on( 'keyup', '.media-folder_name_edit', this.renameDocumentSubmit.bind( this ) );
			mediaStream.on( 'click', '.name_edit_cancel, .name_edit_save', this.renameDocumentSubmit.bind( this ) );

			// document delete.
			$( document ).on( 'click', '.document-file-delete', this.deleteDocument.bind( this ) );

			// Media Delete
			$( document ).on( 'click', '.media-file-delete', this.deleteMedia.bind( this ) );

			// Folder Move.
			$( document ).on( 'click', '.bp-folder-move', this.folderMove.bind( this ) );

			// Create Folder.
			$( document ).on( 'click', '.bp-document-open-create-popup-folder', this.createFolderInPopup.bind( this ) );
			$( document ).on( 'click', '.bp-media-open-create-popup-folder', this.createAlbumInPopup.bind( this ) );
			$( document ).on( 'click', '.close-create-popup-folder', this.closeCreateFolderInPopup.bind( this ) );
			$( document ).on( 'click', '.close-create-popup-album', this.closeCreateAlbumInPopup.bind( this ) );
			$( document ).on( 'click', '.bp-document-create-popup-folder-submit', this.submitCreateFolderInPopup.bind( this ) );

			// Create Album.
			$( document ).on( 'click', '.bp-media-create-popup-album-submit', this.submitCreateAlbumInPopup.bind( this ) );

			// Group Messages.
			var groupMessagesButtonSelector = $( '.buddypress.groups.messages' );
			var groupMessagesToolbarSelector = $( '.buddypress.groups.messages form#send_group_message_form #whats-new-toolbar' );
			var groupMessagesToolbarContainerResults = $( '.buddypress.groups.messages form#send_group_message_form #whats-new-attachments .bp-group-messages-attached-gif-container .gif-search-results' );
			var groupMessagesToolbarContainer = $( '.buddypress.groups.messages form#send_group_message_form #whats-new-attachments .bp-group-messages-attached-gif-container' );

			groupMessagesButtonSelector.on( 'click', '#bp-group-messages-media-button', this.openGroupMessagesUploader.bind( this ) );
			groupMessagesButtonSelector.on( 'click', '#bp-group-messages-document-button', this.openGroupMessagesDocumentUploader.bind( this ) );
			groupMessagesButtonSelector.on( 'click', '#bp-group-messages-video-button', this.openGroupMessagesVideoUploader.bind( this ) );
			groupMessagesButtonSelector.on( 'click', '#bp-group-messages-gif-button', this.toggleGroupMessagesGifSelector.bind( this ) );
			groupMessagesToolbarSelector.on( 'keyup', '.search-query-input', this.searchGroupMessagesGif.bind( this ) );
			groupMessagesToolbarSelector.on( 'click', '.found-media-item', this.selectGroupMessagesGif.bind( this ) );
			groupMessagesToolbarContainerResults.scroll( this.loadMoreGroupMessagesGif.bind( this ) );
			$( '.groups.messages form#send_group_message_form #whats-new-toolbar .bp-group-messages-attached-gif-container .gif-search-results' ).scroll( this.loadMoreGroupMessagesGif.bind( this ) );
			groupMessagesToolbarContainer.on( 'click', '.gif-image-remove', this.removeGroupMessagesSelectedGif.bind( this ) );

			$( '.bp-existing-media-wrap' ).on( 'scroll', this.loadExistingMedia.bind( this ) );

			document.addEventListener( 'keyup', this.closePopup.bind( this ) );
			document.addEventListener( 'keyup', this.submitPopup.bind( this ) );

			$( window ).bind( 'beforeunload', this.beforeunloadWindow.bind( this ) );

			// Gifs autoplay.
			if ( !_.isUndefined( BP_Nouveau.media.gif_api_key ) ) {
				window.addEventListener( 'scroll', this.autoPlayGifVideos, false );
				window.addEventListener( 'resize', this.autoPlayGifVideos, false );

				document.addEventListener( 'keydown', _.bind( this.closePickersOnEsc, this ) );
				$( document ).on( 'click', _.bind( this.closePickersOnClick, this ) );
			}

			if ( ( this.bbp_is_reply_edit || this.bbp_is_topic_edit || this.bbp_is_forum_edit ) &&
				( this.bbp_reply_edit_media.length || this.bbp_topic_edit_media.length || this.bbp_forum_edit_media.length ) ) {
				$( '#forums-media-button' ).trigger( 'click' );
			}

			if ( ( this.bbp_is_reply_edit || this.bbp_is_topic_edit || this.bbp_is_forum_edit ) &&
				( this.bbp_reply_edit_document.length || this.bbp_topic_edit_document.length || this.bbp_forum_edit_document.length ) ) {
				$( '#forums-document-button' ).trigger( 'click' );
			}

			if ( ( this.bbp_is_reply_edit || this.bbp_is_topic_edit || this.bbp_is_forum_edit ) &&
				( this.bbp_reply_edit_video.length || this.bbp_topic_edit_video.length || this.bbp_forum_edit_video.length ) ) {
				$( '#forums-video-button' ).trigger( 'click' );
			}

			if ( ( this.bbp_is_reply_edit || this.bbp_is_topic_edit || this.bbp_is_forum_edit ) &&
				( Object.keys( this.bbp_reply_edit_gif_data ).length || Object.keys( this.bbp_topic_edit_gif_data ).length || Object.keys( this.bbp_forum_edit_gif_data ).length ) ) {
				this.editGifPreview();

				// Disable other buttons( media/document ).
				var tool_box = jQuery( '#forums-gif-button' ).addClass( 'active' ).closest( 'form' );
				if ( tool_box.find( '#forums-document-button' ) ) {
					tool_box.find( '#forums-document-button' ).parents( '.post-elements-buttons-item' ).addClass( 'disable' );
				}
				if ( tool_box.find( '#forums-media-button' ) ) {
					tool_box.find( '#forums-media-button' ).parents( '.post-elements-buttons-item' ).addClass( 'disable' );
				}
				if ( tool_box.find( '#forums-video-button' ) ) {
					tool_box.find( '#forums-video-button' ).parents( '.post-elements-buttons-item' ).addClass( 'disable' );
				}
			}

			// Open edit folder popup if user redirected from activity edit folder privacy
			if ( window.location.hash == '#openEditFolder' && $( '#bp-media-edit-child-folder' ).length ) {
				history.pushState( null, null, window.location.href.split( '#' )[ 0 ] );
				$( '#bp-media-edit-child-folder' ).show();
			}

			if ( this.bbp_is_reply_edit || this.bbp_is_topic_edit || this.bbp_is_forum_edit ) {
				bp.Nouveau.Media.reply_topic_allow_delete_media = true;
			}
		},

		loadExistingMedia: function () {
			// replace dummy image with original image by faking scroll event to call bp.Nouveau.lazyLoad.
			$( window ).scroll();
		},

		resetPageDocumentDirectory: function ( event ) {
			event.preventDefault();
			this.current_page = 1;
		},

		submitCreateFolderInPopup: function ( event ) {
			event.preventDefault();

			var targetPopup = $( event.currentTarget ).closest( '.open-popup' );
			var currentAction = $( targetPopup ).find( '.bp-document-create-popup-folder-submit' );
			var hiddenValue = targetPopup.find( '.bb-folder-selected-id' ).val();
			var title = $.trim( $( event.currentTarget ).closest( '.modal-container' ).find( '.popup-on-fly-create-folder-title' ).val() );
			var titleSelector = $( event.currentTarget ).closest( '.modal-container' ).find( '.popup-on-fly-create-folder-title' );

			// if ( 0 === this.currentTargetParent && hiddenValue > 0 ) {
			// 	this.currentTargetParent = hiddenValue;
			// }

			var pattern = /[\\/?%*:|"<>]+/g; // regex to find not supported characters - \ / ? % * : | " < >
			var matches = pattern.exec( titleSelector.val() );
			var matchStatus = Boolean( matches );

			if ( $.trim( titleSelector.val() ) === '' || matchStatus ) {
				titleSelector.addClass( 'error' );
				return false;
			} else {
				titleSelector.removeClass( 'error' );
			}

			if ( '' === hiddenValue ) {
				hiddenValue = 0;
			}

			this.currentTargetParent = hiddenValue;

			var currentFolder = this.currentTargetParent;
			var groupId = 0;

			var privacy = '';
			var privacySelector = '';
			var newParent = 0;
			if ( 'group' === this.moveToTypePopup ) {
				privacy = 'grouponly';
				groupId = this.moveToIdPopup;
			} else {
				privacy = $( event.currentTarget ).closest( '.modal-container' ).find( '.popup-on-fly-create-folder #bb-folder-privacy' ).val();
				privacySelector = $( event.currentTarget ).closest( '.modal-container' ).find( '.popup-on-fly-create-folder #bb-folder-privacy' );
			}
			if ( '' === title ) {
				alert( BP_Nouveau.media.create_folder_error_title );
				return false;
			}

			currentAction.addClass( 'loading' );

			//Defer this code to run at last
			setTimeout( function () {
				var data = {
					'action': 'document_folder_save',
					'_wpnonce': BP_Nouveau.nonces.media,
					'title': title,
					'privacy': privacy,
					'parent': currentFolder,
					'group_id': groupId
				};
				$.ajax(
					{
						type: 'POST',
						url: BP_Nouveau.ajaxurl,
						data: data,
						async: false,
						success: function ( response ) {
							if ( response.success ) {
								if ( $( '.document-data-table-head' ).length ) {
									if ( parseInt( currentFolder ) === parseInt( BP_Nouveau.media.current_folder ) ) {
										// Prepend the activity if no parent.
										bp.Nouveau.inject( '#media-stream div#media-folder-document-data-table', response.data.document, 'prepend' );
										jQuery( window ).scroll();
									}
								} else {
									//location.reload( true );
								}

								targetPopup.find( '.location-folder-list-wrap .location-folder-list' ).remove();
								targetPopup.find( '.location-folder-list-wrap' ).append( response.data.tree_view );

								var targetPopupID = '#' + $( targetPopup ).attr( 'id' );

								if ( bp.Nouveau.Media.folderLocationUI ) {
									bp.Nouveau.Media.folderLocationUI( targetPopupID, response.data.folder_id );
								}
								newParent = response.data.folder_id;

								if ( '' === response.data.tree_view ) {
									targetPopup.find( '.location-folder-list-wrap' ).hide();
									targetPopup.find( '.location-folder-list-wrap-main span.no-folder-exists' ).show();
								} else {
									targetPopup.find( '.location-folder-list-wrap-main span.no-folder-exists' ).hide();
									targetPopup.find( '.location-folder-list-wrap' ).show();

								}

								targetPopup.find( 'ul.location-folder-list span#' + newParent ).trigger( 'click' );
								targetPopup.find( '.bb-model-footer' ).show();
								targetPopup.find( '.bb-model-footer, #bp-media-document-prev' ).show();
								targetPopup.find( '.bb-field-wrap-search' ).show();
								targetPopup.find( '.bp-document-open-create-popup-folder' ).show();
								targetPopup.find( '.location-folder-list-wrap-main' ).show();
								targetPopup.find( '.create-popup-folder-wrap' ).hide();
								targetPopup.find( '.bb-folder-selected-id' ).val();
								targetPopup.find( '.bb-folder-selected-id' ).val( newParent );
								targetPopup.find( '.bb-model-header' ).children().show();
								targetPopup.find( '.bb-model-header p' ).hide();
								titleSelector.val( '' );
								if ( '' !== privacySelector ) {
									privacySelector.val( 'public' );
								}
								$( targetPopup ).find( '.breadcrumbs-append-ul-li .breadcrumb .item span:not(.hidden)' ).each( function ( i ) {

									if ( i > 0 ) {
										if ( $( targetPopup ).find( '.breadcrumbs-append-ul-li .breadcrumb .item' ).width() > $( targetPopup ).find( '.breadcrumbs-append-ul-li .breadcrumb' ).width() ) {

											$( targetPopup ).find( '.breadcrumbs-append-ul-li .breadcrumb .item span.hidden' ).append( $( targetPopup ).find( '.breadcrumbs-append-ul-li .breadcrumb .item span' ).eq( 2 ) );

											if ( !$( targetPopup ).find( '.breadcrumbs-append-ul-li .breadcrumb .item .more_options' ).length ) {
												$( '<span class="more_options">...</span>' ).insertAfter( $( targetPopup ).find( '.breadcrumbs-append-ul-li .breadcrumb .item span' ).eq( 0 ) );
											}

										}
									}
								} );
								currentAction.removeClass( 'loading' );
								setTimeout( function () {
									var currentSelectedFolder = targetPopup.find( 'ul.location-folder-list span#' + newParent );
									currentSelectedFolder.trigger( 'click' );
									var mediaPrivacy = $( targetPopup ).find( '#bb-document-privacy' );

									if ( Number( currentSelectedFolder.data( 'id' ) ) !== 0 ) {
										mediaPrivacy.find( 'option' ).removeAttr( 'selected' );
										mediaPrivacy.val( currentSelectedFolder.parent().data( 'privacy' ) );
										mediaPrivacy.prop( 'disabled', true );
									} else {
										mediaPrivacy.find( 'option' ).removeAttr( 'selected' );
										mediaPrivacy.val( 'public' );
										mediaPrivacy.prop( 'disabled', false );
									}
								}, 200 );
							}
						}
					}
				);
				this.currentTargetParent = newParent;
				targetPopup.find( '.location-folder-list li.is_active' ).show().children( 'span, i' ).show().siblings( 'ul' ).hide();
				targetPopup.find( '.location-folder-list li.is_active' ).siblings( 'li' ).show().children( 'span, i' ).show().siblings( 'ul' ).hide();
				targetPopup.find( '.location-folder-list li span.selected' ).removeClass( 'selected' );
				targetPopup.find( '.location-folder-list li.is_active' ).children( 'span' ).addClass( 'selected' );
			}, 0 );
		},

		submitCreateAlbumInPopup: function ( event ) {
			event.preventDefault();


			var targetPopup = $( event.currentTarget ).closest( '.open-popup' );
			var currentAction = $( targetPopup ).find( '.bp-media-create-popup-album-submit' );
			var hiddenValue = targetPopup.find( '.bb-album-selected-id' ).val();
			if ( '' === hiddenValue ) {
				hiddenValue = 0;
			}

			this.currentTargetParent = hiddenValue;

			var currentAlbum = this.currentTargetParent;
			var groupId = 0;
			var title = $.trim( $( event.currentTarget ).closest( '.modal-container' ).find( '.popup-on-fly-create-album-title' ).val() );
			var titleSelector = $( event.currentTarget ).closest( '.modal-container' ).find( '.popup-on-fly-create-album-title' );
			var privacy = '';
			var privacySelector = '';
			var newParent = 0;
			if ( 'group' === this.moveToTypePopup ) {
				privacy = 'grouponly';
				groupId = this.moveToIdPopup;
			} else {
				privacy = $( event.currentTarget ).closest( '.modal-container' ).find( '.popup-on-fly-create-album #bb-album-privacy' ).val();
				privacySelector = $( event.currentTarget ).closest( '.modal-container' ).find( '.popup-on-fly-create-album #bb-album-privacy' );
			}
			if ( '' === title ) {
				alert( BP_Nouveau.media.create_album_error_title );
				return false;
			}

			currentAction.addClass( 'loading' );

			//Defer this code to run at last
			setTimeout( function () {
				var data = {
					'action': 'media_album_save',
					'_wpnonce': BP_Nouveau.nonces.media,
					'title': title,
					'privacy': privacy,
					'parent': currentAlbum,
					'group_id': groupId
				};
				$.ajax(
					{
						type: 'POST',
						url: BP_Nouveau.ajaxurl,
						data: data,
						async: false,
						success: function ( response ) {
							if ( response.success ) {
								targetPopup.find( '.location-album-list-wrap .location-album-list' ).remove();
								targetPopup.find( '.location-album-list-wrap' ).append( response.data.tree_view );
								if ( bp.Nouveau.Media.folderLocationUI ) {
									bp.Nouveau.Media.folderLocationUI( targetPopup, response.data.album_id );
								}
								newParent = response.data.album_id;

								if ( '' === response.data.tree_view ) {
									targetPopup.find( '.location-album-list-wrap' ).hide();
									targetPopup.find( '.location-album-list-wrap-main span.no-album-exists' ).show();
								} else {
									targetPopup.find( '.location-album-list-wrap-main span.no-album-exists' ).hide();
									targetPopup.find( '.location-album-list-wrap' ).show();

								}

								targetPopup.find( 'ul.location-album-list span#' + newParent ).trigger( 'click' );
								targetPopup.find( '.bb-model-footer' ).show();
								targetPopup.find( '.bb-field-wrap-search' ).show();
								targetPopup.find( '.bp-media-open-create-popup-album' ).show();
								targetPopup.find( '.location-album-list-wrap-main' ).show();
								targetPopup.find( '.create-popup-album-wrap' ).hide();
								targetPopup.find( '.bb-field-steps-2 #bp-media-prev' ).show();
								targetPopup.find( '.bb-album-selected-id' ).val();
								targetPopup.find( '.bb-album-selected-id' ).val( newParent );
								targetPopup.find( '.bb-model-header' ).children().show();
								targetPopup.find( '.bb-model-header p' ).hide();
								titleSelector.val( '' );
								if ( '' !== privacySelector ) {
									privacySelector.val( 'public' );
								}
								$( targetPopup ).find( '.breadcrumbs-append-ul-li .breadcrumb .item span:not(.hidden)' ).each( function ( i ) {

									if ( i > 0 ) {
										if ( $( targetPopup ).find( '.breadcrumbs-append-ul-li .breadcrumb .item' ).width() > $( targetPopup ).find( '.breadcrumbs-append-ul-li .breadcrumb' ).width() ) {

											$( targetPopup ).find( '.breadcrumbs-append-ul-li .breadcrumb .item span.hidden' ).append( $( targetPopup ).find( '.breadcrumbs-append-ul-li .breadcrumb .item span' ).eq( 2 ) );

											if ( !$( targetPopup ).find( '.breadcrumbs-append-ul-li .breadcrumb .item .more_options' ).length ) {
												$( '<span class="more_options">...</span>' ).insertAfter( $( targetPopup ).find( '.breadcrumbs-append-ul-li .breadcrumb .item span' ).eq( 0 ) );
											}

										}
									}
								} );

								currentAction.removeClass( 'loading' );
							} else {
								currentAction.removeClass( 'loading' );
							}
						}
					}
				);
				this.currentTargetParent = newParent;
				targetPopup.find( '.location-album-list li.is_active' ).show().children( 'span, i' ).show().siblings( 'ul' ).hide();
				targetPopup.find( '.location-album-list li.is_active' ).siblings( 'li' ).show().children( 'span, i' ).show().siblings( 'ul' ).hide();
				targetPopup.find( '.location-album-list li span.selected' ).removeClass( 'selected' );
				targetPopup.find( '.location-album-list li.is_active' ).children( 'span' ).addClass( 'selected' );
			}, 0 );
		},

		closeCreateFolderInPopup: function ( event ) {
			event.preventDefault();

			$( '.modal-container .bb-model-footer' ).show();
			$( '.bb-field-wrap-search' ).show();
			$( '.bp-document-open-create-popup-folder' ).show();
			$( '.location-folder-list-wrap-main' ).show();
			$( '#bp-media-document-prev' ).show();
			$( '.create-popup-folder-wrap' ).hide();
			$( event.currentTarget ).closest( '.has-folderlocationUI' ).find( '.bb-model-header' ).children().show();
			$( event.currentTarget ).closest( '.has-folderlocationUI' ).find( '.bb-model-header p' ).hide();
		},

		closeCreateAlbumInPopup: function ( event ) {
			event.preventDefault();

			$( '.modal-container .bb-model-footer' ).show();
			$( '.bb-field-wrap-search' ).show();
			$( '.bp-document-open-create-popup-folder' ).show();
			$( '.modal-container:visible .bp-video-open-create-popup-album' ).show();
			$( '.location-album-list-wrap-main' ).show();
			$( '.bb-field-steps-2 #bp-media-prev' ).show();
			$( '.bb-field-steps-2 #bp-video-next' ).show();
			$( '.create-popup-album-wrap' ).hide();
			$( '.bp-media-create-popup-album-submit.loading' ).removeClass( 'loading' );
			$( event.currentTarget ).closest( '.has-folderlocationUI' ).find( '.bb-model-header' ).children().show();
			$( event.currentTarget ).closest( '.has-folderlocationUI' ).find( '.bb-model-header p' ).hide();
		},

		createFolderInPopup: function ( event ) {
			event.preventDefault();

			var getParentFolderId = parseInt( $( document ).find( '.open-popup .bb-folder-selected-id' ).val() );
			var getCreateIn = $( document ).find( '.open-popup .bb-folder-create-from' ).val();
			if ( getParentFolderId > 0 ) {
				$( document ).find( '.open-popup .privacy-field-wrap-hide-show' ).hide();
			} else {
				$( document ).find( '.open-popup .privacy-field-wrap-hide-show' ).show();
			}

			if ( 'group' === getCreateIn ) {
				$( document ).find( '.popup-on-fly-create-folder .privacy-field-wrap-hide-show' ).hide();
			} else {
				if ( getParentFolderId > 0 ) {
					$( document ).find( '.popup-on-fly-create-folder .privacy-field-wrap-hide-show' ).hide();
				} else {
					$( document ).find( '.popup-on-fly-create-folder .privacy-field-wrap-hide-show' ).show();
				}
			}

			$( '.modal-container .bb-model-footer, .modal-container #bp-media-document-prev' ).hide();

			$( '.bb-field-wrap-search' ).hide();
			$( '.bp-document-open-create-popup-folder' ).hide();
			$( '.location-folder-list-wrap-main' ).hide();
			$( '.create-popup-folder-wrap' ).show();
			$( event.currentTarget ).closest( '.has-folderlocationUI' ).find( '.bb-model-header' ).children().hide();
			$( event.currentTarget ).closest( '.has-folderlocationUI' ).find( '.bb-model-header' ).append( '<p>' + BP_Nouveau.media.create_folder + '</p>' );
			$( '.modal-container #bb-folder-privacy' ).addClass( 'new-folder-create-privacy' );
			$( document ).find( '.open-popup .error' ).hide();
		},

		createAlbumInPopup: function ( event ) {
			event.preventDefault();

			var getParentFolderId = parseInt( $( document ).find( '.open-popup .bb-album-selected-id' ).val() );
			var getCreateIn = $( document ).find( '.open-popup .bb-album-create-from' ).val();
			if ( getParentFolderId > 0 ) {
				$( document ).find( '.open-popup .privacy-field-wrap-hide-show' ).hide();
			} else {
				$( document ).find( '.open-popup .privacy-field-wrap-hide-show' ).show();
			}

			if ( 'group' === getCreateIn ) {
				$( document ).find( '.popup-on-fly-create-album .privacy-field-wrap-hide-show' ).hide();
			} else {
				$( document ).find( '.popup-on-fly-create-album .privacy-field-wrap-hide-show' ).show();
			}

			$( '.modal-container .bb-model-footer' ).hide();
			$( '.bb-field-wrap-search' ).hide();
			$( '.bp-document-open-create-popup-folder' ).hide();
			$( '.location-album-list-wrap-main' ).hide();
			$( '.bb-field-steps-2 #bp-media-prev' ).hide();
			$( '.create-popup-album-wrap' ).show();
			$( event.currentTarget ).closest( '.has-folderlocationUI' ).find( '.bb-model-header' ).children().hide();
			$( event.currentTarget ).closest( '.has-folderlocationUI' ).find( '.bb-model-header' ).append( '<p>' + BP_Nouveau.media.create_album_title + '</p>' );
			$( '.modal-container #bb-folder-privacy' ).addClass( 'new-folder-create-privacy' );
			$( document ).find( '.open-popup .error' ).hide();
		},

		savePrivacy: function ( event ) {
			var target = $( event.currentTarget ), itemId = 0, type = '', value = '', text = '';
			event.preventDefault();

			if ( target.hasClass( 'new-folder-create-privacy' ) ) {
				return false;
			}

			itemId = parseInt( target.data( 'item-id' ) );
			type = target.data( 'item-type' );
			value = target.val();
			text = $( event.currentTarget ).find( 'option:selected' ).text();

			this.privacySelectorSelect.addClass( 'hide' );
			this.privacySelectorSpan.text( '' );
			this.privacySelectorSpan.text( text );
			this.privacySelectorSpan.show();

			if ( itemId > 0 ) {
				var data = {
					'action': 'document_save_privacy',
					'item_id': itemId,
					'type': type,
					'value': value,
					'_wpnonce': BP_Nouveau.nonces.media
				};

				$.ajax(
					{
						type: 'POST',
						url: BP_Nouveau.ajaxurl,
						data: data,
						success: function ( response ) {
							if ( response.success ) {
								$( document ).find( '#div-listing-' + itemId + ' li#' + itemId + ' a' ).attr( 'data-privacy', value );
								if ( response.data.document && response.data.document.video_symlink ) {
									$( document ).find( 'a.bb-open-document-theatre[data-id="' + itemId + '"]' ).attr( 'data-video-preview', response.data.document.video_symlink );
								}
							} else {
								target.find( 'option[value="' + target.attr( 'data-privacy' ) + '"]' ).attr( 'selected', 'selected' );
								target.siblings( 'span' ).text( target.find( 'option[value="' + target.attr( 'data-privacy' ) + '"]' ).text() );
								/* jshint ignore:start */
								alert( response.data.feedback.replace( '&#039;', '\'' ) );
								/* jshint ignore:end */
							}
						}
					}
				);
			}

		},

		folderMove: function ( event ) {
			var target = $( event.currentTarget );
			var self = this;
			event.preventDefault();

			var currentFolderId = target.attr( 'id' );
			var folderMoveToId = $( '#media-folder-document-data-table #bp-media-move-folder .modal-mask .modal-wrapper #boss-media-create-album-popup .bb-field-wrap .bb-folder-selected-id' ).val();

			if ( '' === currentFolderId || '' === folderMoveToId ) {
				alert( BP_Nouveau.media.i18n_strings.folder_move_error );
				return false;
			}

			target.addClass( 'loading' );

			var data = {
				'action': 'document_folder_move',
				'current_folder_id': currentFolderId,
				'folder_move_to_id': folderMoveToId,
				'group_id': self.group_id,
				'_wpnonce': BP_Nouveau.nonces.media,
			};

			$.ajax(
				{
					type: 'POST',
					url: BP_Nouveau.ajaxurl,
					data: data,
					success: function ( response ) {
						if ( response.success ) {
							if ( 'yes' === BP_Nouveau.media.is_document_directory ) {
								var store = bp.Nouveau.getStorage( 'bp-document' );
								var scope = store.scope;
								if ( 'personal' === scope ) {
									$( document ).find( 'li#document-personal a' ).trigger( 'click' );
									$( document ).find( 'li#document-personal' ).trigger( 'click' );
								} else {
									$( document ).find( 'li#document-all a' ).trigger( 'click' );
									$( document ).find( 'li#document-all' ).trigger( 'click' );
								}
							} else {
								var documentStream = $( '#media-stream' );
								documentStream.html( '' );
								documentStream.html( response.data.html );
								$( document ).find( '.open-popup .error' ).hide();
								$( document ).find( '.open-popup .error' ).html( '' );
								target.removeClass( 'loading' );
								$( document ).removeClass( 'open-popup' );
							}
						} else {
							$( document ).find( '.open-popup .error' ).show();
							$( document ).find( '.open-popup .error' ).html( response.data.feedback );
							target.removeClass( 'loading' );
							return false;
						}

					}
				}
			);
		},

		deleteDocument: function ( event ) {
			var target = $( event.currentTarget );
			event.preventDefault();

			var type = target.attr( 'data-type' );
			var id = target.attr( 'data-item-id' );
			var attachment_id = target.attr( 'data-item-attachment-id' );
			var preview_attachment_id = target.attr( 'data-item-preview-attachment-id' );
			var fromWhere = target.attr( 'data-item-from' );
			var data = [];

			if ( 'activity' !== fromWhere ) {
				if ( 'folder' === type ) {
					if ( !confirm( BP_Nouveau.media.i18n_strings.folder_delete_confirm ) ) {
						return false;
					}
				} else if ( 'document' === type ) {
					if ( !confirm( BP_Nouveau.media.i18n_strings.document_delete_confirm ) ) {
						return false;
					}
				}

				data = {
					'action': 'document_delete',
					'id': id,
					'preview_attachment_id': preview_attachment_id,
					'type': type,
					'attachment_id': attachment_id,
					'_wpnonce': BP_Nouveau.nonces.media,
				};

				if ( 'yes' === BP_Nouveau.media.is_document_directory ) {
					var store = bp.Nouveau.getStorage( 'bp-document' ),
						scope = store.scope;
					if ( '' === scope ) {
						data.scope = 'all';
					} else {
						data.scope = scope;
					}

				}

				$.ajax(
					{
						type: 'POST',
						url: BP_Nouveau.ajaxurl,
						asyc: false,
						data: data,
						success: function ( response ) {
							if ( response.success ) {
								var documentStream = $( '#media-stream' );
								documentStream.html( '' );
								documentStream.html( response.data.html );
							}
						}
					}
				);
				this.current_page = 1;
			} else {

				if ( !confirm( BP_Nouveau.media.i18n_strings.document_delete_confirm ) ) {
					return false;
				}

				var activityId = target.attr( 'data-item-activity-id' );

				data = {
					'action': 'document_activity_delete',
					'id': id,
					'preview_attachment_id': preview_attachment_id,
					'type': type,
					'activity_id': activityId,
					'attachment_id': attachment_id,
					'_wpnonce': BP_Nouveau.nonces.media,
				};

				$.ajax(
					{
						type: 'POST',
						url: BP_Nouveau.ajaxurl,
						data: data,
						success: function ( response ) {
							if ( response.success ) {
								$( 'body #buddypress .activity-list li#activity-' + activityId + ' .document-activity .activity-inner .bb-activity-media-wrap .document-activity.' + id ).remove();
								$( 'body #buddypress .activity-list .activity-comments .document-activity.' + id ).remove();
								if ( true === response.data.delete_activity ) {
									$( 'body #buddypress .activity-list li#activity-' + activityId ).remove();
									$( 'body .bb-activity-media-elem.document-activity.' + id ).remove();
									$( 'body .activity-comments li#acomment-' + activityId ).remove();
								} else {
									$( 'body #buddypress .activity-list li#activity-' + activityId ).replaceWith( response.data.activity_content );
								}
							}
						}
					}
				);
			}
		},

		bp_ajax_media_request: function ( event, data ) {
			if ( BP_Nouveau.media.group_id && typeof data !== 'undefined' && typeof data.response.scopes.groups !== 'undefined' && 0 === parseInt( data.response.scopes.groups ) ) {
				$( '.bb-photos-actions' ).hide();
			} else if ( BP_Nouveau.media.group_id && typeof data !== 'undefined' && typeof data.response.scopes.groups !== 'undefined' && 0 !== parseInt( data.response.scopes.groups ) ) {
				$( '.bb-photos-actions' ).show();
			} else if ( typeof data !== 'undefined' && typeof data.response.scopes.personal !== 'undefined' && 0 === parseInt( data.response.scopes.personal ) ) {
				$( '.bb-photos-actions' ).hide();
			} else if ( typeof data !== 'undefined' && typeof data.response.scopes.personal !== 'undefined' && 0 !== parseInt( data.response.scopes.personal ) ) {
				$( '.bb-photos-actions' ).show();
			}
		},

		addSelectedClassToWrapper: function ( event ) {
			var target = event.currentTarget;
			if ( $( target ).is( ':checked' ) ) {
				$( target ).closest( '.bb-media-check-wrap' ).find( '.bp-tooltip' ).attr( 'data-bp-tooltip', BP_Nouveau.media.i18n_strings.unselect );
				$( target ).closest( '.bb-item-thumb' ).addClass( 'selected' );
			} else {
				$( target ).closest( '.bb-item-thumb' ).removeClass( 'selected' );
				$( target ).closest( '.bb-media-check-wrap' ).find( '.bp-tooltip' ).attr( 'data-bp-tooltip', BP_Nouveau.media.i18n_strings.select );

				var selectAllMedia = $( '.bp-nouveau #bb-select-deselect-all-media' );
				if ( selectAllMedia.hasClass( 'selected' ) ) {
					selectAllMedia.removeClass( 'selected' );
				}
			}
		},

		moveDocumentIntoFolder: function ( event ) {
			var target = $( event.currentTarget );
			var self = this;
			event.preventDefault();

			var document_id = target.attr( 'id' );
			var folder_id = target.closest( '.bp-media-move-file' ).find( '.bb-folder-selected-id' ).val();

			if ( '' === document_id || '' === folder_id ) {
				target.closest( '.modal-container' ).find( '.location-folder-list' ).addClass( 'has-error' );
				return false;
			}

			if ('yes' !== BP_Nouveau.media.is_document_directory) {
				this.current_page = 1;
			}

			target.closest('.modal-container').find('.location-folder-list').removeClass('has-error');
			target.addClass('loading');

			var activityId = '';
			activityId = $( document ).find( 'a[data-media-id="' + document_id + '"]' ).attr( 'data-parent-activity-id' );

			var data = {
				'action': 'document_move',
				'_wpnonce': BP_Nouveau.nonces.media,
				'document_id': document_id,
				'folder_id': folder_id,
				'group_id': self.group_id,
				'activity_id': activityId
			};

			$.ajax(
				{
					type: 'POST',
					url: BP_Nouveau.ajaxurl,
					data: data,
					success: function ( response ) {
						if ( response.success ) {
							if ( 'yes' === BP_Nouveau.media.is_document_directory ) {
								var store = bp.Nouveau.getStorage( 'bp-document' );
								var scope = store.scope;
								if ( 'personal' === scope ) {
									$( document ).find( 'li#document-personal a' ).trigger( 'click' );
									$( document ).find( 'li#document-personal' ).trigger( 'click' );
								} else {
									$( document ).find( 'li#document-all a' ).trigger( 'click' );
									$( document ).find( 'li#document-all' ).trigger( 'click' );
								}
							} else {
								if (parseInt(BP_Nouveau.media.current_folder) > 0) {
									$('#document-stream ul.media-list li[data-id="' + document_id + '"]').remove();
								} else if ($('#activity-stream ul.activity-list li .activity-content .activity-inner .bb-activity-media-wrap div[data-id="' + document_id + '"]').length && !$('#activity-stream ul.activity-list li .activity-content .activity-inner .bb-activity-media-wrap div[data-id="' + document_id + '"]').parent().hasClass('bb-media-length-1')) {
									$('#activity-stream ul.activity-list li .activity-content .activity-inner .bb-activity-media-wrap div[data-id="' + document_id + '"]').remove();
									if (activityId && activityId.length) {
										$('#activity-stream ul.activity-list li[data-bp-activity-id="' + activityId + '"] .activity-content .activity-inner .bb-activity-media-wrap').remove();
										$('#activity-stream ul.activity-list li[data-bp-activity-id="' + activityId + '"] .activity-content .activity-inner').append(response.data.document_content);
									}
								}

								var documentStream = $( '#media-stream' );
								documentStream.html( '' );
								documentStream.html( response.data.html );
								$( document ).find( '.open-popup .error' ).hide();
								$( document ).find( '.open-popup .error' ).html( '' );
								target.removeClass( 'loading' );
								$( document ).removeClass( 'open-popup' );
							}
							target.closest( '.bp-media-move-file' ).find( '.ac-document-close-button' ).trigger( 'click' );
						} else {
							/* jshint ignore:start */
							alert( response.data.feedback.replace( '&#039;', '\'' ) );
							/* jshint ignore:end */
						}
					}
				}
			);
		},

		moveMediaIntoAlbum: function ( event ) {
			var target = $( event.currentTarget );
			var self = this;
			event.preventDefault();

			var media_id = target.attr( 'id' );
			var album_id = target.closest( '.bp-media-move-file' ).find( '.bb-album-selected-id' ).val();

			if ( '' === media_id || '' === album_id ) {
				target.closest( '.modal-container' ).find( '.location-album-list' ).addClass( 'has-error' );
				return false;
			}

			target.closest( '.modal-container' ).find( '.location-album-list' ).removeClass( 'has-error' );
			target.addClass( 'loading' );

			var activityId = '';
			activityId = $( document ).find( 'a[data-media-id="' + media_id + '"]' ).attr( 'data-parent-activity-id' );

			var groupId = parseInt( self.group_id );
			if ( !groupId ) {
				groupId = false;
				if ( 'group' === $( document ).find( 'a[data-media-id="' + media_id + '"]' ).attr( 'data-type' ) ) {
					groupId = $( document ).find( 'a[data-media-id="' + media_id + '"]' ).attr( 'id' );
				}
			}

			var data = {
				'action': 'media_move',
				'_wpnonce': BP_Nouveau.nonces.media,
				'media_id': media_id,
				'album_id': album_id,
				'group_id': groupId,
				'activity_id': activityId
			};

			$.ajax(
				{
					type: 'POST',
					url: BP_Nouveau.ajaxurl,
					data: data,
					success: function ( response ) {
						if ( response.success ) {
							if ( 'yes' === BP_Nouveau.media.is_media_directory ) {
								var store = bp.Nouveau.getStorage( 'bp-media' );
								var scope = store.scope;
								if ( 'personal' === scope ) {
									$( document ).find( 'li#media-personal a' ).trigger( 'click' );
									$( document ).find( 'li#media-personal' ).trigger( 'click' );
								} else {
									$( document ).find( 'li#media-all a' ).trigger( 'click' );
									$( document ).find( 'li#media-all' ).trigger( 'click' );
								}
							} else {
								if ( parseInt( BP_Nouveau.media.current_album ) > 0 ) {
									$( '#media-stream ul.media-list li[data-id="' + media_id + '"]' ).remove();
								} else if ( $( '#activity-stream ul.activity-list li .activity-content .activity-inner .bb-activity-media-wrap div[data-id="' + media_id + '"]' ).length && !$( '#activity-stream ul.activity-list li .activity-content .activity-inner .bb-activity-media-wrap div[data-id="' + media_id + '"]' ).parent().hasClass( 'bb-media-length-1' ) ) {
									$( '#activity-stream ul.activity-list li .activity-content .activity-inner .bb-activity-media-wrap div[data-id="' + media_id + '"]' ).remove();
									if ( activityId && activityId.length ) {
										$( '#activity-stream ul.activity-list li[data-bp-activity-id="' + activityId + '"] .activity-content .activity-inner .bb-activity-media-wrap' ).remove();
										$( '#activity-stream ul.activity-list li[data-bp-activity-id="' + activityId + '"] .activity-content .activity-inner' ).append( response.data.media_content );
										// replace dummy image with original image by faking scroll event to call bp.Nouveau.lazyLoad.
										jQuery( window ).scroll();
									}
								}
								$( document ).find( '.open-popup .error' ).hide();
								$( document ).find( '.open-popup .error' ).html( '' );
								target.removeClass( 'loading' );
								$( document ).removeClass( 'open-popup' );
							}
							target.closest('.bp-media-move-file').find('.ac-media-close-button').trigger('click');
							$(document).find('a.bb-open-media-theatre[data-id="' + media_id + '"]').data('album-id', album_id);

						} else {
							/* jshint ignore:start */
							alert( response.data.feedback.replace( '&#039;', '\'' ) );
							/* jshint ignore:end */
						}
					}
				}
			);
		},

		deleteMedia: function ( event ) {
			var target = $( event.currentTarget ), self = this;
			event.preventDefault();

			var media = [];
			var buddyPressSelector = $( '#buddypress' );
			var type = target.attr( 'data-type' );
			var fromWhere = target.data( 'item-from' );
			var id = '';
			var activityId = '';

			if ( 'album' === type ) {
				if ( !confirm( BP_Nouveau.media.i18n_strings.album_delete_confirm ) ) {
					return false;
				}
			} else if ( 'media' === type ) {
				if ( !confirm( BP_Nouveau.media.i18n_strings.media_delete_confirm ) ) {
					return false;
				}
			}

			if ( target.hasClass( 'bb-delete' ) ) {

				if ( !confirm( BP_Nouveau.media.i18n_strings.media_delete_confirm ) ) {
					return false;
				}

				var $media_list = buddyPressSelector.find( '.media-list:not(.existing-media-list)' );
				$media_list.find( '.bb-media-check-wrap [name="bb-media-select"]:checked' ).each(
					function () {
						$( this ).closest( '.bb-item-thumb' ).addClass( 'loading deleting' );
						media.push( $( this ).val() );
					}
				);

				if ( $media_list.parent().parent().hasClass( 'album-single-view' ) ) {
					$media_list.find( '.bb-video-check-wrap [name="bb-video-select"]:checked' ).each(
						function () {
							$( this ).closest( '.bb-item-thumb' ).addClass( 'loading deleting' );
							media.push( $( this ).val() );
						}
					);
				}

			}

			activityId = target.data( 'parent-activity-id' );
			if ( fromWhere && fromWhere.length && 'activity' === fromWhere && media.length == 0 ) {
				id = target.attr( 'data-item-id' );
				media.push( id );
			}

			if ( media.length == 0 ) {
				media.push( target.data( 'item-id' ) );
			}

			if ( media.length == 0 ) {
				return false;
			}

			target.prop( 'disabled', true );
			$( '#buddypress #media-stream.media .bp-feedback' ).remove();

			var data = {
				'action': 'media_delete',
				'_wpnonce': BP_Nouveau.nonces.media,
				'media': media,
				'activity_id': activityId,
				'from_where': fromWhere,
			};

			$.ajax(
				{
					type: 'POST',
					url: BP_Nouveau.ajaxurl,
					data: data,
					success: function ( response ) {
						self.current_page = 1;
						var feedback = '';
						if ( fromWhere && fromWhere.length && 'activity' === fromWhere ) {
							if ( response.success ) {
								$.each( media, function ( index, value ) {
									if ( $( '#activity-stream ul.activity-list li.activity .activity-content .activity-inner .bb-activity-media-wrap div[data-id="' + value + '"]' ).length ) {
										$( '#activity-stream ul.activity-list li.activity .activity-content .activity-inner .bb-activity-media-wrap div[data-id="' + value + '"]' ).remove();
									}
									if ( $( 'body .bb-activity-media-elem.media-activity.' + value ).length ) {
										$( 'body .bb-activity-media-elem.media-activity.' + value ).remove();
									}
								} );

								$( '#activity-stream ul.activity-list li[data-bp-activity-id="' + activityId + '"] .activity-content .activity-inner .bb-activity-media-wrap' ).remove();
								$( '#activity-stream ul.activity-list li[data-bp-activity-id="' + activityId + '"] .activity-content .activity-inner' ).append( response.data.media_content );

								var length = $( '#activity-stream ul.activity-list li[data-bp-activity-id="' + activityId + '"] .activity-content .activity-inner .bb-activity-media-elem' ).length;
								if ( length == 0 ) {
									$( '#activity-stream ul.activity-list li[data-bp-activity-id="' + activityId + '"]' ).remove();
								}

								if ( true === response.data.delete_activity ) {
									$( 'body #buddypress .activity-list li#activity-' + activityId ).remove();
									$( 'body .bb-activity-media-elem.media-activity.' + id ).remove();
									$( 'body .activity-comments li#acomment-' + activityId ).remove();
								} else {
									$( 'body #buddypress .activity-list li#activity-' + activityId ).replaceWith( response.data.activity_content );
								}
							}
						} else if ( fromWhere && fromWhere.length && 'media' === fromWhere ) {
							if ( response.success ) {
								if ( 'yes' === BP_Nouveau.media.is_media_directory ) {
									var store = bp.Nouveau.getStorage( 'bp-media' );
									var scope = store.scope;
									if ( 'personal' === scope ) {
										$( document ).find( 'li#media-personal a' ).trigger( 'click' );
										$( document ).find( 'li#media-personal' ).trigger( 'click' );
									} else if ( 'groups' === scope ) {
										$( document ).find( 'li#media-groups a' ).trigger( 'click' );
										$( document ).find( 'li#media-groups' ).trigger( 'click' );
									} else {
										$( document ).find( 'li#media-all a' ).trigger( 'click' );
										$( document ).find( 'li#media-all' ).trigger( 'click' );
									}
								} else {
									if ( response.data.media_personal_count ) {
										$( '#buddypress' ).find( '.bp-wrap .users-nav ul li#media-personal-li a span.count' ).text( response.data.media_personal_count );
									}

									if ( response.data.media_group_count ) {
										$( '#buddypress' ).find( '.bp-wrap .groups-nav ul li#photos-groups-li a span.count' ).text( response.data.media_group_count );
									}
									$.each( media, function ( index, value ) {
										if ( $( '#media-stream ul.media-list li[data-id="' + value + '"]' ).length ) {
											$( '#media-stream ul.media-list li[data-id="' + value + '"]' ).remove();
										}
									} );
									if ( $( '#buddypress' ).find( '.media-list:not(.existing-media-list)' ).find( 'li:not(.load-more)' ).length == 0 ) {
										$( '.bb-photos-actions' ).hide();
										feedback = '<aside class="bp-feedback bp-messages info">\n' +
											'\t<span class="bp-icon" aria-hidden="true"></span>\n' +
											'\t<p>' + BP_Nouveau.media.i18n_strings.no_photos_found + '</p>\n' +
											'\t</aside>';
										$( '#buddypress [data-bp-list="media"]' ).html( feedback );
									}
								}
							}
						} else {
							setTimeout(
								function () {
									target.prop( 'disabled', false );
								},
								500
							);
							if ( response.success ) {
								if (
									'undefined' !== typeof response.data &&
									'undefined' !== typeof response.data.media_personal_count
								) {
									$( '#buddypress' ).find( '.bp-wrap .users-nav ul li#media-personal-li a span.count' ).text( response.data.media_personal_count );
								}

								if (
									'undefined' !== typeof response.data &&
									'undefined' !== typeof response.data.media_group_count
								) {
									$( '#buddypress' ).find( '.bp-wrap .groups-nav ul li#photos-groups-li a span.count' ).text( response.data.media_group_count );
								}
								var $media_list = buddyPressSelector.find( '.media-list:not(.existing-media-list)' );
								$media_list.find( '.bb-media-check-wrap [name="bb-media-select"]:checked' ).each(
									function () {
										$( this ).closest( 'li' ).remove();
									}
								);
								if ( $media_list.parent().parent().hasClass( 'album-single-view' ) ) {
									$media_list.find( '.bb-video-check-wrap [name="bb-video-select"]:checked' ).each(
										function () {
											$( this ).closest( 'li' ).remove();
										}
									);
								}
								if ( $( '#buddypress' ).find( '.media-list:not(.existing-media-list)' ).find( 'li:not(.load-more)' ).length == 0 ) {
									$( '.bb-photos-actions' ).hide();
									feedback = '<aside class="bp-feedback bp-messages info">\n' +
										'\t<span class="bp-icon" aria-hidden="true"></span>\n' +
										'\t<p>' + BP_Nouveau.media.i18n_strings.no_photos_found + '</p>\n' +
										'\t</aside>';
									$( '#buddypress [data-bp-list="media"]' ).html( feedback );
								}
							} else {
								$( '#buddypress #media-stream.media' ).prepend( response.data.feedback );
							}
						}

						// replace dummy image with original image by faking scroll event to call bp.Nouveau.lazyLoad.
						jQuery( window ).scroll();

					}
				}
			);
		},

		toggleSelectAllMedia: function ( event ) {
			event.preventDefault();

			if ( $( event.currentTarget ).hasClass( 'selected' ) ) {
				$( event.currentTarget ).data( 'bp-tooltip', BP_Nouveau.media.i18n_strings.selectall );
				this.deselectAllMedia( event );
			} else {
				$( event.currentTarget ).data( 'bp-tooltip', BP_Nouveau.media.i18n_strings.unselectall );
				this.selectAllMedia( event );
			}

			$( event.currentTarget ).toggleClass( 'selected' );
		},

		selectAllMedia: function ( event ) {
			event.preventDefault();

			var $media_list = $( '#buddypress' ).find( '.media-list:not(.existing-media-list)' );
			$media_list.find( '.bb-media-check-wrap [name="bb-media-select"]' ).each(
				function () {
					$( this ).prop( 'checked', true );
					$( this ).closest( '.bb-item-thumb' ).addClass( 'selected' );
					$( this ).closest( '.bb-media-check-wrap' ).find( '.bp-tooltip' ).attr( 'data-bp-tooltip', BP_Nouveau.media.i18n_strings.unselect );
				}
			);

			if ( $media_list.parent().parent().hasClass( 'album-single-view' ) ) {
				$media_list.find( '.bb-video-check-wrap [name="bb-video-select"]' ).each(
					function () {
						$( this ).prop( 'checked', true );
						$( this ).closest( '.bb-item-thumb' ).addClass( 'selected' );
						$( this ).closest( '.bb-video-check-wrap' ).find( '.bp-tooltip' ).attr( 'data-bp-tooltip', BP_Nouveau.media.i18n_strings.unselect );
					}
				);
			}
		},

		deselectAllMedia: function ( event ) {
			event.preventDefault();

			var $media_list = $( '#buddypress' ).find( '.media-list:not(.existing-media-list)' );
			$media_list.find( '.bb-media-check-wrap [name="bb-media-select"]' ).each(
				function () {
					$( this ).prop( 'checked', false );
					$( this ).closest( '.bb-item-thumb' ).removeClass( 'selected' );
					$( this ).closest( '.bb-media-check-wrap' ).find( '.bp-tooltip' ).attr( 'data-bp-tooltip', BP_Nouveau.media.i18n_strings.select );
				}
			);

			if ( $media_list.parent().parent().hasClass( 'album-single-view' ) ) {
				$media_list.find( '.bb-video-check-wrap [name="bb-video-select"]' ).each(
					function () {
						$( this ).prop( 'checked', false );
						$( this ).closest( '.bb-item-thumb' ).removeClass( 'selected' );
						$( this ).closest( '.bb-video-check-wrap' ).find( '.bp-tooltip' ).attr( 'data-bp-tooltip', BP_Nouveau.media.i18n_strings.select );
					}
				);
			}
		},

		editAlbumTitle: function ( event ) {
			event.preventDefault();

			$( '#bb-album-title' ).show();
			$( '#bp-save-album-title' ).show();
			$( '#bp-cancel-edit-album-title' ).show();
			$( '#bp-edit-album-title' ).hide();
			$( '#bp-media-single-album #bp-single-album-title' ).hide();
		},

		editFolderTitle: function ( event ) {
			event.preventDefault();

			$( '#bb-album-title' ).show();
			$( '#bp-save-folder-title' ).show();
			$( '#bp-cancel-edit-album-title' ).show();
			$( '#bp-edit-folder-title' ).hide();
			$( '#bp-media-single-album #bp-single-album-title' ).hide();
		},

		cancelEditAlbumTitle: function ( event ) {
			event.preventDefault();

			$( '#bb-album-title' ).removeClass( 'error' ).hide();
			$( '#bp-save-album-title,#bp-save-folder-title' ).hide();
			$( '#bp-cancel-edit-album-title' ).hide();
			$( '#bp-edit-album-title,#bp-edit-folder-title' ).show();
			$( '#bp-media-single-album #bp-single-album-title' ).show();
		},

		triggerDropzoneSelectFileDialog: function () {
			var self = this;

			self.dropzone_obj.hiddenFileInput.click();
		},

		closeUploader: function ( event ) {
			event.preventDefault();
			$( '#bp-media-uploader' ).hide();
			$( '#bp-media-add-more' ).hide();
			$( '#bp-media-uploader-modal-title' ).text( BP_Nouveau.media.i18n_strings.upload );
			$( '#bp-media-uploader-modal-status-text' ).text( '' );
			$( '#bp-media-post-content' ).val( '' );
			this.dropzone_obj.element ? this.dropzone_obj.destroy() : '';
			this.dropzone_media = [];

			var currentPopup = $( event.currentTarget ).closest( '#bp-media-uploader' );

			$( '.close-create-popup-album' ).trigger( 'click' );
			$( '.close-create-popup-folder' ).trigger( 'click' );
			currentPopup.find( '.breadcrumbs-append-ul-li .item span[data-id="0"]' ).trigger( 'click' );

			if ( currentPopup.find( '.bb-field-steps' ).length ) {
				currentPopup.find( '.bb-field-steps-1' ).show().siblings( '.bb-field-steps-2' ).hide();
				currentPopup.find( '.bb-field-steps-1 #bp-media-photo-next, .bb-field-steps-1 #bp-media-document-next ' ).hide();
				currentPopup.find( '.bb-field-steps-1' ).removeClass( 'controls-added' );
				currentPopup.find( '#bp-media-document-prev, #bp-media-prev, #bp-media-document-submit, #bp-media-submit, .bp-media-open-create-popup-folder, .bp-document-open-create-popup-folder, .create-popup-folder-wrap, .create-popup-album-wrap, .bp-video-open-create-popup-album' ).hide();
			}

			this.clearFolderLocationUI( event );

		},

		closeChildFolderUploader: function ( event ) {
			event.preventDefault();
			$( document ).find( '.open-popup #bb-album-child-title' ).val( '' );

			$( document ).find( '.open-popup #bp-media-create-child-folder-submit' ).removeClass( 'loading' );
			$( document ).find( '#bp-media-create-child-folder' ).removeClass( 'open-popup' );
			$( document ).find( '#bp-media-create-child-folder' ).hide();

			$( document ).find( '.open-popup #bb-album-title' ).text( '' );
			$( document ).find( '.open-popup #bp-media-create-folder' ).hide();
			$( document ).find( '#bp-media-create-folder' ).removeClass( 'open-popup' );
		},

		closeFolderUploader: function ( event ) {
			event.preventDefault();
			$( document ).find( '.open-popup #bb-album-title' ).val( '' );
			$( document ).find( '.open-popup #bp-media-create-folder-submit' ).removeClass( 'loading' );
			$( document ).find( '.open-popup #bp-media-document-submit' ).hide();
			$( document ).find( '#bp-media-create-folder' ).removeClass( 'open-popup' );
			$( document ).find( '#bp-media-create-folder' ).hide();
		},

		loadMoreGif: function ( e ) {
			var el = e.target, self = this;

			var $forums_gif_container = $( e.target ).closest( 'form' ).find( '.forums-attached-gif-container' );
			var gif_container_key = $forums_gif_container.data( 'key' );
			self.gif_container_key = gif_container_key;

			if ( el.scrollTop + el.offsetHeight >= el.scrollHeight && !$forums_gif_container.hasClass( 'loading' ) ) {
				if ( self.gif_data[ gif_container_key ].total_count > 0 && self.gif_data[ gif_container_key ].offset <= self.gif_data[ gif_container_key ].total_count ) {
					var params = {
						offset: self.gif_data[ gif_container_key ].offset,
						fmt: 'json',
						limit: self.gif_data[ gif_container_key ].limit
					};

					$forums_gif_container.addClass( 'loading' );
					var request = null;
					if ( _.isNull( self.gif_data[ gif_container_key ].q ) ) {
						request = self.giphy.trending( params, _.bind( self.loadMoreGifResponse, self ) );
					} else {
						request = self.giphy.search( _.extend( { q: self.gif_data[ gif_container_key ].q }, params ), _.bind( self.loadMoreGifResponse, self ) );
					}

					self.gif_data[ gif_container_key ].requests.push( request );
					self.gif_data[ gif_container_key ].offset = self.gif_data[ gif_container_key ].offset + self.gif_data[ gif_container_key ].limit;
				}
			}
		},

		loadMoreGroupMessagesGif: function ( e ) {
			var el = e.target, self = this;

			var $group_messages_gif_container = $( e.target ).closest( 'form' ).find( '.bp-group-messages-attached-gif-container' );
			var gif_container_key = $group_messages_gif_container.data( 'key' );
			self.gif_container_key = gif_container_key;

			if ( el.scrollTop + el.offsetHeight >= el.scrollHeight && !$( e.target ).closest( '.bp-group-messages-attached-gif-container' ).hasClass( 'loading' ) ) {
				if ( self.gif_total_count > 0 && self.gif_offset <= self.gif_total_count ) {
					var params = {
						offset: self.gif_offset,
						fmt: 'json',
						limit: self.gif_limit
					};

					$( e.target ).closest( '.bp-group-messages-attached-gif-container' ).addClass( 'loading' );
					var request = null;
					if ( _.isNull( self.gif_q ) ) {
						request = self.giphy.trending( params, _.bind( self.loadMoreGroupMessagesGifResponse, self ) );
					} else {
						request = self.giphy.search( _.extend( { q: self.gif_q }, params ), _.bind( self.loadMoreGroupMessagesGifResponse, self ) );
					}

					self.gif_requests.push( request );
					self.gif_offset = self.gif_offset + self.gif_limit;
					self.gif_data[ gif_container_key ].requests.push( request );
					self.gif_data[ gif_container_key ].offset = self.gif_data[ gif_container_key ].offset + self.gif_data[ gif_container_key ].limit;
				}
			}
		},

		loadMoreGroupMessagesGifResponse: function ( response ) {
			var self = this, i = 0;
			$( '.bp-group-messages-attached-gif-container' ).removeClass( 'loading' );
			if ( typeof response.data !== 'undefined' && response.data.length ) {
				var li_html = '';
				for ( i = 0; i < response.data.length; i++ ) {
					var bgNo = Math.floor( Math.random() * ( 6 - 1 + 1 ) ) + 1;
					li_html += '<li class="bg' + bgNo + '" style="height: ' + response.data[ i ].images.fixed_width.height + 'px;">\n' +
						'\t<a class="found-media-item" href="' + response.data[ i ].images.original.url + '" data-id="' + response.data[ i ].id + '">\n' +
						'\t\t<img src="' + response.data[ i ].images.fixed_width.url + '">\n' +
						'\t</a>\n' +
						'</li>';
					response.data[ i ].saved = false;
					self.gif_data.push( response.data[ i ] );
				}

				$( '.bp-group-messages-attached-gif-container' ).find( '.gif-search-results-list' ).append( li_html );
			}

			if ( typeof response.pagination !== 'undefined' && typeof response.pagination.total_count !== 'undefined' ) {
				self.gif_total_count = response.pagination.total_count;
			}
		},

		loadMoreGifResponse: function ( response ) {
			var self = this, i = 0;
			$( 'div.forums-attached-gif-container[data-key="' + self.gif_container_key + '"]' ).removeClass( 'loading' );
			if ( typeof response.data !== 'undefined' && response.data.length ) {
				var li_html = '';
				for ( i = 0; i < response.data.length; i++ ) {
					var bgNo = Math.floor( Math.random() * ( 6 - 1 + 1 ) ) + 1;
					li_html += '<li class="bg' + bgNo + '" style="height: ' + response.data[ i ].images.fixed_width.height + 'px;">\n' +
						'\t<a class="found-media-item" href="' + response.data[ i ].images.original.url + '" data-id="' + response.data[ i ].id + '">\n' +
						'\t\t<img src="' + response.data[ i ].images.fixed_width.url + '">\n' +
						'\t</a>\n' +
						'</li>';
					response.data[ i ].saved = false;
					self.gif_data[ self.gif_container_key ].data.push( response.data[ i ] );
				}

				$( 'div.forums-attached-gif-container[data-key="' + self.gif_container_key + '"]' ).closest( 'form' ).find( '.gif-search-results-list' ).append( li_html );
			}

			if ( typeof response.pagination !== 'undefined' && typeof response.pagination.total_count !== 'undefined' ) {
				self.gif_data[ self.gif_container_key ].total_count = response.pagination.total_count;
			}
		},

		editGifPreview: function () {
			var self = this, gif_data = {};

			if ( self.bbp_is_reply_edit && Object.keys( self.bbp_reply_edit_gif_data ).length ) {
				gif_data = self.bbp_reply_edit_gif_data.gif_raw_data;
			} else if ( self.bbp_is_topic_edit && Object.keys( self.bbp_topic_edit_gif_data ).length ) {
				gif_data = self.bbp_topic_edit_gif_data.gif_raw_data;
			} else if ( self.bbp_is_forum_edit && Object.keys( self.bbp_forum_edit_gif_data ).length ) {
				gif_data = self.bbp_forum_edit_gif_data.gif_raw_data;
			}

			if ( typeof gif_data.images === 'undefined' ) {
				return false;
			}

			var forumGifContainer = $( '#whats-new-attachments .forums-attached-gif-container' );
			forumGifContainer[ 0 ].style.backgroundImage = 'url(' + gif_data.images.fixed_width.url + ')';
			forumGifContainer[ 0 ].style.backgroundSize = 'contain';
			forumGifContainer[ 0 ].style.height = gif_data.images.original.height + 'px';
			forumGifContainer[ 0 ].style.width = gif_data.images.original.width + 'px';
			forumGifContainer.find( '.gif-image-container img' ).attr( 'src', gif_data.images.original.url );
			forumGifContainer.removeClass( 'closed' );
			if ( $( '#bbp_media_gif' ).length ) {
				$( '#bbp_media_gif' ).val( JSON.stringify( gif_data ) );
			}
		},

		selectGif: function ( e ) {
			var self = this, i = 0, target = $( e.currentTarget ),
				gif_container = target.closest( 'form' ).find( '.forums-attached-gif-container' );
			e.preventDefault();

			gif_container.closest( 'form' ).find( '.gif-media-search-dropdown' ).removeClass( 'open' );
			var gif_container_key = gif_container.data( 'key' );
			if ( typeof self.gif_data[ gif_container_key ] !== 'undefined' && typeof self.gif_data[ gif_container_key ].data !== 'undefined' && self.gif_data[ gif_container_key ].data.length ) {
				for ( i = 0; i < self.gif_data[ gif_container_key ].data.length; i++ ) {
					if ( self.gif_data[ gif_container_key ].data[ i ].id == e.currentTarget.dataset.id ) {

						target.closest( 'form' ).find( '#whats-new-attachments .forums-attached-gif-container' )[ 0 ].style.backgroundImage = 'url(' + self.gif_data[ gif_container_key ].data[ i ].images.fixed_width.url + ')';
						target.closest( 'form' ).find( '#whats-new-attachments .forums-attached-gif-container' )[ 0 ].style.backgroundSize = 'contain';
						target.closest( 'form' ).find( '#whats-new-attachments .forums-attached-gif-container' )[ 0 ].style.height = self.gif_data[ gif_container_key ].data[ i ].images.original.height + 'px';
						target.closest( 'form' ).find( '#whats-new-attachments .forums-attached-gif-container' )[ 0 ].style.width = self.gif_data[ gif_container_key ].data[ i ].images.original.width + 'px';

						target.closest( 'form' ).find( '#whats-new-attachments .forums-attached-gif-container' ).find( '.gif-image-container img' ).attr( 'src', self.gif_data[ gif_container_key ].data[ i ].images.original.url );
						target.closest( 'form' ).find( '#whats-new-attachments .forums-attached-gif-container' ).removeClass( 'closed' );
						if ( target.closest( 'form' ).find( '#bbp_media_gif' ).length ) {
							target.closest( 'form' ).find( '#bbp_media_gif' ).val( JSON.stringify( self.gif_data[ gif_container_key ].data[ i ] ) );
						}
						break;
					}
				}

				var tool_box = target.closest( 'form' );
				tool_box.addClass( 'has-gif' );
				if ( tool_box.find( '#forums-document-button' ) ) {
					tool_box.find( '#forums-document-button' ).parents( '.post-elements-buttons-item' ).addClass( 'disable' );
				}
				if ( tool_box.find( '#forums-media-button' ) ) {
					tool_box.find( '#forums-media-button' ).parents( '.post-elements-buttons-item' ).addClass( 'disable' );
				}
				if ( tool_box.find( '#forums-video-button' ) ) {
					tool_box.find( '#forums-video-button' ).parents( '.post-elements-buttons-item' ).addClass( 'disable' );
				}
			}
		},

		selectGroupMessagesGif: function ( e ) {
			var self = this, i = 0;
			e.preventDefault();

			var containerAttachmentGif = $( '#whats-new-attachments .bp-group-messages-attached-gif-container' );
			var inputHiddenGif = $( '#bp_group_messages_gif' );

			$( '#whats-new-toolbar .bp-group-messages-attached-gif-container' ).parent().removeClass( 'open' );
			if ( self.gif_data.length ) {
				for ( i = 0; i < self.gif_data.length; i++ ) {
					if ( self.gif_data[ i ].id == e.currentTarget.dataset.id ) {

						containerAttachmentGif[ 0 ].style.backgroundImage = 'url(' + self.gif_data[ i ].images.fixed_width.url + ')';
						containerAttachmentGif[ 0 ].style.backgroundSize = 'contain';
						containerAttachmentGif[ 0 ].style.height = self.gif_data[ i ].images.original.height + 'px';
						containerAttachmentGif[ 0 ].style.width = self.gif_data[ i ].images.original.width + 'px';
						containerAttachmentGif.find( '.gif-image-container img' ).attr( 'src', self.gif_data[ i ].images.original.url );
						containerAttachmentGif.removeClass( 'closed' );
						if ( inputHiddenGif.length ) {
							inputHiddenGif.val( JSON.stringify( self.gif_data[ i ] ) );
						}
						break;
					}
				}

				var tool_box = $( '#send_group_message_form' );
				if ( tool_box.find( '#bp-group-messages-media-button' ) ) {
					tool_box.find( '#bp-group-messages-media-button' ).parents( '.post-elements-buttons-item' ).addClass( 'disable' );
				}
				if ( tool_box.find( '#bp-group-messages-document-button' ) ) {
					tool_box.find( '#bp-group-messages-document-button' ).parents( '.post-elements-buttons-item' ).addClass( 'disable' );
				}
				if ( tool_box.find( '#bp-group-messages-video-button' ) ) {
					tool_box.find( '#bp-group-messages-video-button' ).parents( '.post-elements-buttons-item' ).addClass( 'disable' );
				}
			}
		},

		removeSelectedGif: function ( e ) {
			e.preventDefault();
			this.resetForumsGifComponent( e );
		},

		removeGroupMessagesSelectedGif: function ( e ) {
			e.preventDefault();
			this.resetGroupMessagesGifComponent();
		},

		resetForumsGifComponent: function ( e ) {
			var target = $( e.target );
			target.closest( 'form' ).find( '#whats-new-toolbar #forums-gif-button' ).removeClass( 'active' );
			target.closest( 'form' ).find( '.gif-media-search-dropdown' ).removeClass( 'open' );
			var $forums_attached_gif_container = target.closest( 'form' ).find( '#whats-new-attachments .forums-attached-gif-container' );
			if ( $forums_attached_gif_container.length ) {
				$forums_attached_gif_container.addClass( 'closed' );
				$forums_attached_gif_container.find( '.gif-image-container img' ).attr( 'src', '' );
				$forums_attached_gif_container[ 0 ].style = '';
			}

			if ( target.closest( 'form' ).find( '#bbp_media_gif' ).length ) {
				target.closest( 'form' ).find( '#bbp_media_gif' ).val( '' );
			}

			var tool_box = target.closest( 'form' );
			tool_box.removeClass( 'has-gif' );
			if ( tool_box.find( '#forums-document-button' ) ) {
				tool_box.find( '#forums-document-button' ).parents( '.post-elements-buttons-item' ).removeClass( 'disable' );
			}
			if ( tool_box.find( '#forums-media-button' ) ) {
				tool_box.find( '#forums-media-button' ).parents( '.post-elements-buttons-item' ).removeClass( 'disable' );
			}
			if ( tool_box.find( '#forums-video-button' ) ) {
				tool_box.find( '#forums-video-button' ).parents( '.post-elements-buttons-item' ).removeClass( 'disable' );
			}
			if ( tool_box.find( '#forums-gif-button' ) ) {
				tool_box.find( '#forums-gif-button' ).parents( '.post-elements-buttons-item' ).removeClass( 'no-click' );
			}
		},

		resetGroupMessagesGifComponent: function () {

			var containerAttachment = $( '#whats-new-attachments .bp-group-messages-attached-gif-container' );
			var inputHiddenGif = $( '#bp_group_messages_gif' );

			$( '#whats-new-toolbar .bp-group-messages-attached-gif-container' ).parent().removeClass( 'open' );
			$( '#whats-new-toolbar #bp-group-messages-gif-button' ).removeClass( 'active' );
			containerAttachment.addClass( 'closed' );
			containerAttachment.find( '.gif-image-container img' ).attr( 'src', '' );
			containerAttachment[ 0 ].style = '';
			if ( inputHiddenGif.length ) {
				inputHiddenGif.val( '' );
			}

			var tool_box = $( '#send_group_message_form' );
			if ( tool_box.find( '#bp-group-messages-media-button' ) ) {
				tool_box.find( '#bp-group-messages-media-button' ).parents( '.post-elements-buttons-item' ).removeClass( 'disable' );
			}
			if ( tool_box.find( '#bp-group-messages-document-button' ) ) {
				tool_box.find( '#bp-group-messages-document-button' ).parents( '.post-elements-buttons-item' ).removeClass( 'disable' );
			}
			if ( tool_box.find( '#bp-group-messages-video-button' ) ) {
				tool_box.find( '#bp-group-messages-video-button' ).parents( '.post-elements-buttons-item' ).removeClass( 'disable' );
			}
		},

		searchGif: function ( e ) {
			// Prevent search dropdown from closing with enter key
			if ( e.key === 'Enter' || e.keyCode === 13 ) {
				e.preventDefault();
				return false;
			}

			var self = this;

			if ( self.gif_timeout != null ) {
				clearTimeout( this.gif_timeout );
			}

			self.gif_timeout = setTimeout(
				function () {
					self.gif_timeout = null;
					self.searchGifRequest( e, e.target.value );
				},
				1000
			);
		},

		searchGroupMessagesGif: function ( e ) {
			var self = this;

			if ( self.gif_timeout != null ) {
				clearTimeout( this.gif_timeout );
			}

			if ( '' === e.target.value ) {
				this.toggleGroupMessagesGifSelector( e );
				return;
			}

			self.gif_timeout = setTimeout(
				function () {
					self.gif_timeout = null;
					self.searchGroupMessagesGifRequest( e, e.target.value );
				},
				1000
			);
		},

		searchGroupMessagesGifRequest: function ( e ) {
			var self = this;
			self.gif_q = e.target.value;
			self.gif_offset = 0;
			var i = 0;

			self.clearGifRequests();
			$( e.target ).closest( '.bp-group-messages-attached-gif-container' ).addClass( 'loading' );
			$( e.target ).find( '.gif-no-results' ).removeClass( 'show' );
			$( e.target ).find( '.gif-no-connection' ).removeClass( 'show' );

			var request = self.giphy.search(
				{
					q: self.gif_q,
					offset: self.gif_offset,
					fmt: 'json',
					limit: self.gif_limit
				},
				function ( response ) {
					if ( undefined !== response.data.length && 0 === response.data.length ) {
						$( e.target ).find( '.gif-no-results' ).addClass( 'show' );
					}
					if ( undefined !== response.meta.status && 200 !== response.meta.status ) {
						$( e.target ).find( '.gif-no-connection' ).addClass( 'show' );
					}
					if ( typeof response.data !== 'undefined' && response.data.length ) {
						var li_html = '';
						for ( i = 0; i < response.data.length; i++ ) {
							var bgNo = Math.floor( Math.random() * ( 6 - 1 + 1 ) ) + 1;
							li_html += '<li class="bg' + bgNo + '" style="height: ' + response.data[ i ].images.fixed_width.height + 'px;">\n' +
								'\t<a class="found-media-item" href="' + response.data[ i ].images.original.url + '" data-id="' + response.data[ i ].id + '">\n' +
								'\t\t<img src="' + response.data[ i ].images.fixed_width.url + '">\n' +
								'\t</a>\n' +
								'</li>';
							response.data[ i ].saved = false;
							self.gif_data.push( response.data[ i ] );
						}

						$( e.target ).closest( '.bp-group-messages-attached-gif-container' ).find( '.gif-search-results-list' ).append( li_html );
					}

					if ( typeof response.pagination !== 'undefined' && typeof response.pagination.total_count !== 'undefined' ) {
						self.gif_total_count = response.pagination.total_count;
					}
					$( e.target ).closest( '.bp-group-messages-attached-gif-container' ).removeClass( 'loading' );
				},
				function () {
					$( e.target ).find( '.gif-no-connection' ).addClass( 'show' );
				}
			);

			self.gif_requests.push( request );
			self.gif_offset = self.gif_offset + self.gif_limit;
		},

		searchGifRequest: function ( e ) {
			var self = this, i = 0;

			var $forums_gif_container = $( e.target ).closest( 'form' ).find( '.forums-attached-gif-container' );
			$forums_gif_container.addClass( 'loading' );
			var gif_container_key = $forums_gif_container.data( 'key' );
			$( e.target ).closest( 'form' ).find( '.gif-no-results' ).removeClass( 'show' );
			$( e.target ).closest( 'form' ).find( '.gif-no-connection' ).removeClass( 'show' );

			self.clearGifRequests( gif_container_key );

			self.gif_data[ gif_container_key ].q = e.target.value;
			self.gif_data[ gif_container_key ].offset = 0;

			var request = self.giphy.search(
				{
					q: self.gif_data[ gif_container_key ].q,
					offset: self.gif_data[ gif_container_key ].offset,
					fmt: 'json',
					limit: self.gif_data[ gif_container_key ].limit
				},
				function ( response ) {
					if ( undefined !== response.data.length && 0 === response.data.length ) {
						$( e.target ).closest( 'form' ).find( '.gif-no-results' ).addClass( 'show' );
					}
					if ( undefined !== response.meta.status && 200 !== response.meta.status ) {
						$( e.target ).closest( 'form' ).find( '.gif-no-connection' ).addClass( 'show' );
					}
					if ( typeof response.data !== 'undefined' && response.data.length ) {
						var li_html = '';
						for ( i = 0; i < response.data.length; i++ ) {
							var bgNo = Math.floor( Math.random() * ( 6 - 1 + 1 ) ) + 1;
							li_html += '<li class="bg' + bgNo + '" style="height: ' + response.data[ i ].images.fixed_width.height + 'px;">\n' +
								'\t<a class="found-media-item" href="' + response.data[ i ].images.original.url + '" data-id="' + response.data[ i ].id + '">\n' +
								'\t\t<img src="' + response.data[ i ].images.fixed_width.url + '">\n' +
								'\t</a>\n' +
								'</li>';
							response.data[ i ].saved = false;
							self.gif_data[ gif_container_key ].data.push( response.data[ i ] );
						}

						$( e.target ).closest( '.gif-search-content' ).find( '.gif-search-results-list' ).append( li_html );
					}

					if ( typeof response.pagination !== 'undefined' && typeof response.pagination.total_count !== 'undefined' ) {
						self.gif_data[ gif_container_key ].total_count = response.pagination.total_count;
					}
					$forums_gif_container.removeClass( 'loading' );
				},
				function () {
					$( e.target ).closest( 'form' ).find( '.gif-no-connection' ).addClass( 'show' );
				}
			);

			self.gif_data[ gif_container_key ].requests.push( request );
			self.gif_data[ gif_container_key ].offset = self.gif_data[ gif_container_key ].offset + self.gif_data[ gif_container_key ].limit;
		},

		clearGifRequests: function ( gif_container_key ) {
			var self = this;

			if ( typeof self.gif_data[ gif_container_key ] !== 'undefined' && typeof self.gif_data[ gif_container_key ].requests !== 'undefined' ) {
				for ( var i = 0; i < self.gif_data[ gif_container_key ].requests.length; i++ ) {
					self.gif_data[ gif_container_key ].requests[ i ].abort();
				}

				$( '[data-key="' + gif_container_key + '"]' ).closest( 'form' ).find( '.gif-search-results-list li' ).remove();

				self.gif_data[ gif_container_key ].requests = [];
				self.gif_data[ gif_container_key ].data = [];
				self.gif_data.splice( gif_container_key, 1 );
			}
		},

		toggleGifSelector: function ( event ) {
			var self = this, target = $( event.currentTarget ),
				gif_search_dropdown = target.closest( 'form' ).find( '.gif-media-search-dropdown' ), i = 0;
			event.preventDefault();

			if ( typeof window.Giphy !== 'undefined' && typeof BP_Nouveau.media.gif_api_key !== 'undefined' ) {
				self.giphy = new window.Giphy( BP_Nouveau.media.gif_api_key );

				var $forums_attached_gif_container = target.closest( 'form' ).find( '.forums-attached-gif-container' );
				$forums_attached_gif_container.addClass( 'loading' );
				var gif_container_key = $forums_attached_gif_container.data( 'key' );

				self.clearGifRequests( gif_container_key );

				self.gif_data[ gif_container_key ] = {
					q: null,
					offset: 0,
					limit: 20,
					requests: [],
					total_count: 0,
					data: []
				};

				var request = self.giphy.trending(
					{
						offset: self.gif_data[ gif_container_key ].offset,
						fmt: 'json',
						limit: self.gif_data[ gif_container_key ].limit
					},
					function ( response ) {

						if ( typeof response.data !== 'undefined' && response.data.length ) {
							var li_html = '';
							for ( i = 0; i < response.data.length; i++ ) {
								var bgNo = Math.floor( Math.random() * ( 6 - 1 + 1 ) ) + 1;
								li_html += '<li class="bg' + bgNo + '" style="height: ' + response.data[ i ].images.fixed_width.height + 'px;">\n' +
									'\t<a class="found-media-item" href="' + response.data[ i ].images.original.url + '" data-id="' + response.data[ i ].id + '">\n' +
									'\t\t<img src="' + response.data[ i ].images.fixed_width.url + '">\n' +
									'\t</a>\n' +
									'</li>';
								response.data[ i ].saved = false;
								self.gif_data[ gif_container_key ].data.push( response.data[ i ] );
							}

							target.closest( 'form' ).find( '.gif-search-results-list' ).append( li_html );
						}

						if ( typeof response.pagination !== 'undefined' && typeof response.pagination.total_count !== 'undefined' ) {
							self.gif_data[ gif_container_key ].total_count = response.pagination.total_count;
						}

						$forums_attached_gif_container.removeClass( 'loading' );
					}
				);

				self.gif_data[ gif_container_key ].requests.push( request );
				self.gif_data[ gif_container_key ].offset = self.gif_data[ gif_container_key ].offset + self.gif_data[ gif_container_key ].limit;
			}

			gif_search_dropdown.toggleClass( 'open' );

			var gif_box = target.parents( 'form' ).find( '#whats-new-attachments .forums-attached-gif-container img' );
			if ( gif_box.length > 0 && gif_box.attr( 'src' ) != '' ) {
				target.addClass( 'active' );
			} else {
				target.toggleClass( 'active' );
			}
			// target.toggleClass( 'active' );

			var $forums_media_container = target.closest( 'form' ).find( '#forums-post-media-uploader' );
			if ( $forums_media_container.length ) {
				self.resetForumsMediaComponent( $forums_media_container.data( 'key' ) );
			}
			var $forums_document_container = target.closest( 'form' ).find( '#forums-post-document-uploader' );
			if ( $forums_document_container.length ) {
				self.resetForumsDocumentComponent( $forums_document_container.data( 'key' ) );
			}

			var $forums_video_container = target.closest( 'form' ).find( '#forums-post-video-uploader' );
			if ( $forums_video_container.length ) {
				self.resetForumsVideoComponent( $forums_video_container.data( 'key' ) );
			}
		},

		closePickersOnEsc: function ( event ) {
			var target = $( event.currentTarget );
			if ( event.key === 'Escape' || event.keyCode === 27 ) {
				if ( !_.isUndefined( BP_Nouveau.media ) && !_.isUndefined( BP_Nouveau.media.gif_api_key ) ) {
					target.find( 'form' ).find( '.gif-media-search-dropdown' ).removeClass( 'open' );
					target.find( '#bbpress-forums form' ).each( function () {
						var $this = jQuery( this );
						var gif_box = $this.find( '#whats-new-attachments .forums-attached-gif-container img' );
						if ( gif_box.length > 0 && gif_box.attr( 'src' ) != '' ) {
							$this.find( '#forums-gif-button' ).addClass( 'active' );
						} else {
							$this.find( '#forums-gif-button' ).removeClass( 'active' );
						}
					} );

					target.find( '#send_group_message_form' ).each( function () {
						var $this = jQuery( this );
						var gif_box_group_messaage = $this.find( '#whats-new-attachments .bp-group-messages-attached-gif-container img' );
						if ( gif_box_group_messaage.length > 0 && gif_box_group_messaage.attr( 'src' ) != '' ) {
							$this.find( '#bp-group-messages-gif-button' ).addClass( 'active' );
						} else {
							$this.find( '#bp-group-messages-gif-button' ).removeClass( 'active' );
						}
					} );

					target.find( '.activity-comments form' ).each( function () {
						var $this = jQuery( this );
						var gif_box_comment = $this.find( '.ac-textarea' ).find( '.ac-reply-attachments .activity-attached-gif-container' );
						if ( gif_box_comment.length && $.trim( gif_box_comment.html() ) != '' ) {
							$this.find( '.ac-reply-gif-button' ).addClass( 'active' );
						} else {
							$this.find( '.ac-reply-gif-button' ).removeClass( 'active' );
						}
					} );
				}
			}
		},

		closePickersOnClick: function ( event ) {
			var $targetEl = $( event.target );
			var target = $( event.currentTarget );

			if ( !_.isUndefined( BP_Nouveau.media ) && !_.isUndefined( BP_Nouveau.media.gif_api_key ) &&
				!$targetEl.closest( '.post-gif' ).length ) {
				target.find( 'form' ).find( '.gif-media-search-dropdown' ).removeClass( 'open' );
				target.find( '#bbpress-forums form' ).each( function () {
					var $this = jQuery( this );
					var gif_box = $this.find( '#whats-new-attachments .forums-attached-gif-container img' );
					if ( gif_box.length > 0 && gif_box.attr( 'src' ) != '' ) {
						$this.find( '#forums-gif-button' ).addClass( 'active' );
					} else {
						$this.find( '#forums-gif-button' ).removeClass( 'active' );
					}
				} );

				target.find( '#send_group_message_form' ).each( function () {
					var $this = jQuery( this );
					var gif_box_group_messaage = $this.find( '#whats-new-attachments .bp-group-messages-attached-gif-container img' );
					if ( gif_box_group_messaage.length > 0 && gif_box_group_messaage.attr( 'src' ) != '' ) {
						$this.find( '#bp-group-messages-gif-button' ).addClass( 'active' );
					} else {
						$this.find( '#bp-group-messages-gif-button' ).removeClass( 'active' );
					}
				} );

				target.find( '.activity-comments form' ).each( function () {
					var $this = jQuery( this );
					var gif_box_comment = $this.find( '.ac-textarea' ).find( '.ac-reply-attachments .activity-attached-gif-container' );
					if ( gif_box_comment.length && $.trim( gif_box_comment.html() ) != '' ) {
						$this.find( '.ac-reply-gif-button' ).addClass( 'active' );
					} else {
						$this.find( '.ac-reply-gif-button' ).removeClass( 'active' );
					}
				} );
			}
		},

		toggleGroupMessagesGifSelector: function ( event ) {
			var self = this, target = $( event.currentTarget ),
				gif_search_dropdown = target.closest( 'form' ).find( '.gif-media-search-dropdown' ), i = 0;
			event.preventDefault();

			if ( typeof window.Giphy !== 'undefined' && typeof BP_Nouveau.media.gif_api_key !== 'undefined' && self.giphy == null ) {
				self.giphy = new window.Giphy( BP_Nouveau.media.gif_api_key );
				self.gif_offset = 0;
				self.gif_q = null;
				self.gif_limit = 20;
				self.gif_requests = [];
				self.gif_data = [];
				self.clearGifRequests();
				$( '.gif-search-query' ).closest( '.bp-group-messages-attached-gif-container' ).addClass( 'loading' );

				var request = self.giphy.trending(
					{
						offset: self.gif_offset,
						fmt: 'json',
						limit: self.gif_limit
					},
					function ( response ) {

						if ( typeof response.data !== 'undefined' && response.data.length ) {
							var li_html = '';
							for ( i = 0; i < response.data.length; i++ ) {
								var bgNo = Math.floor( Math.random() * ( 6 - 1 + 1 ) ) + 1;
								li_html += '<li class="bg' + bgNo + '" style="height: ' + response.data[ i ].images.fixed_width.height + 'px;">\n' +
									'\t<a class="found-media-item" href="' + response.data[ i ].images.original.url + '" data-id="' + response.data[ i ].id + '">\n' +
									'\t\t<img src="' + response.data[ i ].images.fixed_width.url + '">\n' +
									'\t</a>\n' +
									'</li>';
								response.data[ i ].saved = false;
								self.gif_data.push( response.data[ i ] );
							}

							target.closest( 'form' ).find( '.gif-search-results-list' ).append( li_html );
						}

						if ( typeof response.pagination !== 'undefined' && typeof response.pagination.total_count !== 'undefined' ) {
							self.gif_total_count = response.pagination.total_count;
						}

						$( '.gif-search-query' ).closest( '.bp-group-messages-attached-gif-container' ).removeClass( 'loading' );
					}
				);

				self.gif_requests.push( request );
				self.gif_offset = self.gif_offset + self.gif_limit;
			}

			var gif_box = target.parents( 'form' ).find( '#whats-new-attachments .bp-group-messages-attached-gif-container img' );
			if ( gif_box.length > 0 && gif_box.attr( 'src' ) != '' ) {
				target.addClass( 'active' );
			} else {
				target.toggleClass( 'active' );
			}

			gif_search_dropdown.toggleClass( 'open' );
			self.resetGroupMessagesMediaComponent();
			self.resetGroupMessagesDocumentComponent();
			self.resetGroupMessagesVideoComponent();
		},

		resetGroupMessagesMediaComponent: function () {
			var self = this;

			if ( self.dropzone_obj && typeof self.dropzone_obj !== 'undefined' ) {
				self.dropzone_obj.destroy();
			}
			self.dropzone_media = [];
			$( 'div#bp-group-messages-post-media-uploader' ).html( '' );
			$( 'div#bp-group-messages-post-media-uploader' ).addClass( 'closed' ).removeClass( 'open' );
			$( '#bp-group-messages-media-button' ).removeClass( 'active' );
			$( '#item-body #group-messages-container .bb-groups-messages-right #send_group_message_form .bb-groups-messages-right-bottom #bp_group_messages_media' ).val( '' );
		},

		resetGroupMessagesDocumentComponent: function () {
			var self = this;
			if ( self.document_dropzone_obj && typeof self.document_dropzone_obj !== 'undefined' ) {
				self.document_dropzone_obj.destroy();
			}
			self.dropzone_media = [];
			$( 'div#bp-group-messages-post-document-uploader' ).html( '' );
			$( 'div#bp-group-messages-post-document-uploader' ).addClass( 'closed' ).removeClass( 'open' );
			$( '#bp-group-messages-document-button' ).removeClass( 'active' );
			$( '#item-body #group-messages-container .bb-groups-messages-right #send_group_message_form .bb-groups-messages-right-bottom #bp_group_messages_document' ).val( '' );
		},

		resetGroupMessagesVideoComponent: function () {
			var self = this;
			if ( self.video_dropzone_obj && typeof self.video_dropzone_obj !== 'undefined' ) {
				self.video_dropzone_obj.destroy();
			}
			self.dropzone_media = [];
			$( 'div#bp-group-messages-post-video-uploader' ).html( '' );
			$( 'div#bp-group-messages-post-video-uploader' ).addClass( 'closed' ).removeClass( 'open' );
			$( '#bp-group-messages-video-button' ).removeClass( 'active' );
			$( '#item-body #group-messages-container .bb-groups-messages-right #send_group_message_form .bb-groups-messages-right-bottom #bp_group_messages_video' ).val( '' );
		},

		resetForumsMediaComponent: function ( dropzone_container_key ) {
			var self = this;
			$( '#forums-media-button' ).removeClass( 'active' );

			if ( typeof dropzone_container_key !== 'undefined' ) {

				if ( typeof self.dropzone_obj[ dropzone_container_key ] !== 'undefined' ) {
					self.dropzone_obj[ dropzone_container_key ].destroy();
					self.dropzone_obj.splice( dropzone_container_key, 1 );
					self.dropzone_media.splice( dropzone_container_key, 1 );
				}

				var keySelector = $( 'div#forums-post-media-uploader' );
				keySelector.html( '' );
				keySelector.addClass( 'closed' ).removeClass( 'open' );
			}
		},

		resetForumsDocumentComponent: function ( dropzone_container_key ) {
			var self = this;

			$( '#forums-document-button' ).removeClass( 'active' );

			if ( typeof dropzone_container_key !== 'undefined' ) {

				if ( typeof self.dropzone_obj[ dropzone_container_key ] !== 'undefined' ) {
					self.dropzone_obj[ dropzone_container_key ].destroy();
					self.dropzone_obj.splice( dropzone_container_key, 1 );
					self.dropzone_media.splice( dropzone_container_key, 1 );
				}

				var keySelector = $( 'div#forums-post-document-uploader' );
				keySelector.html( '' );
				keySelector.addClass( 'closed' ).removeClass( 'open' );
			}
		},

		resetForumsVideoComponent: function ( dropzone_container_key ) {
			var self = this;

			$( '#forums-video-button' ).removeClass( 'active' );

			if ( typeof dropzone_container_key !== 'undefined' ) {

				if ( typeof self.dropzone_obj[ dropzone_container_key ] !== 'undefined' ) {
					self.dropzone_obj[ dropzone_container_key ].destroy();
					self.dropzone_obj.splice( dropzone_container_key, 1 );
					self.dropzone_media.splice( dropzone_container_key, 1 );
				}

				var keySelector = $( 'div#forums-post-video-uploader' );
				keySelector.html( '' );
				keySelector.addClass( 'closed' ).removeClass( 'open' );
			}
		},

		openForumsUploader: function ( event ) {
			var self = this, target = $( event.currentTarget ),
				dropzone_container = target.closest( 'form' ).find( '#forums-post-media-uploader' ),
				forum_dropzone_container = target.closest( 'form' ).find( '#forums-post-document-uploader' ),
				forum_video_dropzone_container = target.closest( 'form' ).find( '#forums-post-video-uploader' ),
				edit_medias = [];
			event.preventDefault();

			target.toggleClass( 'active' );

			var forum_dropzone_obj_key = forum_dropzone_container.data( 'key' );
			var forum_video_dropzone_obj_key = forum_video_dropzone_container.data( 'key' );
			self.resetForumsDocumentComponent( forum_dropzone_obj_key );
			self.resetForumsVideoComponent( forum_video_dropzone_obj_key );

			if ( typeof window.Dropzone !== 'undefined' && dropzone_container.length ) {

				var dropzone_obj_key = dropzone_container.data( 'key' );
				if ( dropzone_container.hasClass( 'closed' ) ) {

					// init dropzone.
					self.dropzone_obj[ dropzone_obj_key ] = new Dropzone( dropzone_container[ 0 ], self.options );
					self.dropzone_media[ dropzone_obj_key ] = [];

					self.dropzone_obj[ dropzone_obj_key ].on(
						'sending',
						function ( file, xhr, formData ) {
							formData.append( 'action', 'media_upload' );
							formData.append( '_wpnonce', BP_Nouveau.nonces.media );

							var tool_box = target.closest( 'form' );
							tool_box.addClass( 'has-media' );
							if ( tool_box.find( '#forums-document-button' ) ) {
								tool_box.find( '#forums-document-button' ).parents( '.post-elements-buttons-item' ).addClass( 'disable' );
							}
							if ( tool_box.find( '#forums-gif-button' ) ) {
								tool_box.find( '#forums-gif-button' ).parents( '.post-elements-buttons-item' ).addClass( 'disable' );
							}
							if ( tool_box.find( '#forums-video-button' ) ) {
								tool_box.find( '#forums-video-button' ).parents( '.post-elements-buttons-item' ).addClass( 'disable' );
							}
							if ( tool_box.find( '#forums-media-button' ) ) {
								tool_box.find( '#forums-media-button' ).parents( '.post-elements-buttons-item' ).addClass( 'no-click' );
							}
						}
					);

					self.dropzone_obj[ dropzone_obj_key ].on(
						'uploadprogress',
						function( element ) {
							var formElement = target.closest( 'form' );
							formElement.addClass( 'media-uploading' );
							var circle = $( element.previewElement ).find('.dz-progress-ring circle')[0];
							var radius = circle.r.baseVal.value;
							var circumference = radius * 2 * Math.PI;

							circle.style.strokeDasharray = circumference + ' ' + circumference;
							circle.style.strokeDashoffset = circumference;
							var offset = circumference - element.upload.progress.toFixed( 0 ) / 100 * circumference;
							circle.style.strokeDashoffset = offset;
						}
					);

					self.dropzone_obj[ dropzone_obj_key ].on(
						'error',
						function ( file, response ) {
							if ( file.accepted ) {
								if ( typeof response !== 'undefined' && typeof response.data !== 'undefined' && typeof response.data.feedback !== 'undefined' ) {
									$( file.previewElement ).find( '.dz-error-message span' ).text( response.data.feedback );
								} else if( file.status == 'error' && ( file.xhr && file.xhr.status == 0) ) { // update server error text to user friendly
									$( file.previewElement ).find( '.dz-error-message span' ).text( BP_Nouveau.media.connection_lost_error );
								}
							} else {
								if ( !jQuery( '.forum-document-error-popup' ).length ) {
									$( 'body' ).append( '<div id="bp-media-create-folder" style="display: block;" class="open-popup forum-document-error-popup"><transition name="modal"><div class="modal-mask bb-white bbm-model-wrap"><div class="modal-wrapper"><div id="boss-media-create-album-popup" class="modal-container has-folderlocationUI"><header class="bb-model-header"><h4>' + BP_Nouveau.media.invalid_media_type + '</h4><a class="bb-model-close-button errorPopup" href="#"><span class="dashicons dashicons-no-alt"></span></a></header><div class="bb-field-wrap"><p>' + response + '</p></div></div></div></div></transition></div>' );
								}
								this.removeFile( file );
								var formElement = target.closest( 'form' );
								formElement.removeClass( 'media-uploading' );
							}
						}
					);

					self.dropzone_obj[ dropzone_obj_key ].on(
						'success',
						function ( file, response ) {
							if ( response.data.id ) {
								file.id = response.id;
								response.data.uuid = file.upload.uuid;
								response.data.menu_order = $( file.previewElement ).closest( '.dropzone' ).find( file.previewElement ).index() - 1;
								response.data.album_id = self.album_id;
								response.data.group_id = self.group_id;
								response.data.saved = false;
								self.dropzone_media[ dropzone_obj_key ].push( response.data );
								self.addMediaIdsToForumsForm( dropzone_container );
							} else {
								if ( !jQuery( '.forum-media-error-popup' ).length ) {
									$( 'body' ).append( '<div id="bp-media-create-folder" style="display: block;" class="open-popup"><transition name="modal"><div class="modal-mask bb-white bbm-model-wrap"><div class="modal-wrapper"><div id="boss-media-create-album-popup" class="modal-container has-folderlocationUI"><header class="bb-model-header"><h4>' + BP_Nouveau.media.invalid_media_type + '</h4><a class="bb-model-close-button" id="bp-media-create-folder-close" href="#"><span class="dashicons dashicons-no-alt"></span></a></header><div class="bb-field-wrap"><p>' + response.data.feedback + '</p></div></div></div></div></transition></div>' );
								}
								this.removeFile( file );
							}
						}
					);

					self.dropzone_obj[ dropzone_obj_key ].on(
						'removedfile',
						function ( file ) {

							if ( true === bp.Nouveau.Media.reply_topic_allow_delete_media ) {

								if ( self.dropzone_media[ dropzone_obj_key ].length ) {
									for ( var i in self.dropzone_media[ dropzone_obj_key ] ) {
										if ( file.upload.uuid == self.dropzone_media[ dropzone_obj_key ][ i ].uuid ) {

											if ( (
											     ! this.bbp_is_reply_edit && ! this.bbp_is_topic_edit && ! this.bbp_is_forum_edit
											     ) && typeof self.dropzone_media[ dropzone_obj_key ][ i ].saved !== 'undefined' && ! self.dropzone_media[ dropzone_obj_key ][ i ].saved && 'edit' === bp.Nouveau.Media.reply_topic_display_post ) {
												self.removeAttachment( self.dropzone_media[ dropzone_obj_key ][ i ].id );
											}

											self.dropzone_media[ dropzone_obj_key ].splice( i, 1 );
											self.addMediaIdsToForumsForm( dropzone_container );
											break;
										}
									}
								}

								if ( ! _.isNull( self.dropzone_obj[ dropzone_obj_key ].files ) && self.dropzone_obj[ dropzone_obj_key ].files.length === 0 ) {
									var tool_box = target.closest( 'form' );
									if ( tool_box.find( '#forums-document-button' ) ) {
										tool_box.find( '#forums-document-button' ).parents( '.post-elements-buttons-item' ).removeClass( 'disable' );
									}
									if ( tool_box.find( '#forums-video-button' ) ) {
										tool_box.find( '#forums-video-button' ).parents( '.post-elements-buttons-item' ).removeClass( 'disable' );
									}
									if ( tool_box.find( '#forums-gif-button' ) ) {
										tool_box.find( '#forums-gif-button' ).parents( '.post-elements-buttons-item' ).removeClass( 'disable' );
									}
									if ( tool_box.find( '#forums-media-button' ) ) {
										tool_box.find( '#forums-media-button' ).parents( '.post-elements-buttons-item' ).removeClass( 'no-click' );
									}
								}

							}

							if ( ! _.isNull( self.dropzone_obj[ dropzone_obj_key ].files ) && self.dropzone_obj[ dropzone_obj_key ].files.length === 0 ) {
								var targetForm = target.closest( 'form' );
								targetForm.removeClass( 'has-media' );
							}
						}
					);

					// Enable submit button when all medias are uploaded
					self.dropzone_obj[ dropzone_obj_key ].on(
						'complete',
						function() {
							if ( this.getUploadingFiles().length === 0 && this.getQueuedFiles().length === 0 && this.files.length > 0 ) {
								var formElement = target.closest( 'form' );
								formElement.removeClass( 'media-uploading' );
							}
						}
					);

					// container class to open close.
					dropzone_container.removeClass( 'closed' ).addClass( 'open' );

					// reset gif component.
					self.resetForumsGifComponent( event );

					// Load media while edit forum/topic/reply.
					if ( ( this.bbp_is_reply_edit || this.bbp_is_topic_edit || this.bbp_is_forum_edit ) &&
						( this.bbp_reply_edit_media.length || this.bbp_topic_edit_media.length || this.bbp_forum_edit_media.length ) ) {

						if ( this.bbp_reply_edit_media.length ) {
							edit_medias = this.bbp_reply_edit_media;
						} else if ( this.bbp_topic_edit_media.length ) {
							edit_medias = this.bbp_topic_edit_media;
						} else if ( this.bbp_forum_edit_media.length ) {
							edit_medias = this.bbp_forum_edit_media;
						}

						if ( edit_medias.length ) {
							var mock_file = false;
							for ( var i = 0; i < edit_medias.length; i++ ) {
								mock_file = false;
								self.dropzone_media[ dropzone_obj_key ].push(
									{
										'id': edit_medias[ i ].attachment_id,
										'media_id': edit_medias[ i ].id,
										'name': edit_medias[ i ].title,
										'thumb': edit_medias[ i ].thumb,
										'url': edit_medias[ i ].full,
										'uuid': edit_medias[ i ].id,
										'menu_order': i,
										'saved': true
									}
								);

								mock_file = {
									name: edit_medias[ i ].title,
									accepted: true,
									kind: 'image',
									upload: {
										filename: edit_medias[ i ].title,
										uuid: edit_medias[ i ].id
									},
									dataURL: edit_medias[ i ].url,
									id: edit_medias[ i ].id
								};

								self.dropzone_obj[ dropzone_obj_key ].files.push( mock_file );
								self.dropzone_obj[ dropzone_obj_key ].emit( 'addedfile', mock_file );
								self.createThumbnailFromUrl( mock_file, dropzone_container );
							}
							self.addMediaIdsToForumsForm( dropzone_container );

							// Disable other buttons( document/gif ).
							if ( !_.isNull( self.dropzone_obj[ dropzone_obj_key ].files ) && self.dropzone_obj[ dropzone_obj_key ].files.length !== 0 ) {
								var tool_box = target.closest( 'form' );
								if ( tool_box.find( '#forums-document-button' ) ) {
									tool_box.find( '#forums-document-button' ).parents( '.post-elements-buttons-item' ).addClass( 'disable' );
								}
								if ( tool_box.find( '#forums-video-button' ) ) {
									tool_box.find( '#forums-video-button' ).parents( '.post-elements-buttons-item' ).addClass( 'disable' );
								}
								if ( tool_box.find( '#forums-gif-button' ) ) {
									tool_box.find( '#forums-gif-button' ).parents( '.post-elements-buttons-item' ).addClass( 'disable' );
								}
								if ( tool_box.find( '#forums-media-button' ) ) {
									tool_box.find( '#forums-media-button' ).parents( '.post-elements-buttons-item' ).addClass( 'no-click' );
								}
							}

						}
					}

				} else {
					self.resetForumsMediaComponent( dropzone_obj_key );
				}

			}

		},

		openGroupMessagesUploader: function ( event ) {
			var self = this, dropzone_container = $( 'div#bp-group-messages-post-media-uploader' ),
				target = $( event.currentTarget );
			event.preventDefault();

			target.toggleClass( 'active' );

			if ( typeof window.Dropzone !== 'undefined' && dropzone_container.length ) {

				if ( dropzone_container.hasClass( 'closed' ) ) {

					// init dropzone.
					self.dropzone_obj = new Dropzone( 'div#bp-group-messages-post-media-uploader', self.options );

					self.dropzone_obj.on(
						'sending',
						function ( file, xhr, formData ) {
							formData.append( 'action', 'media_upload' );
							formData.append( '_wpnonce', BP_Nouveau.nonces.media );

							var tool_box = $( '#send_group_message_form' );
							if ( tool_box.find( '#bp-group-messages-document-button' ) ) {
								tool_box.find( '#bp-group-messages-document-button' ).parents( '.post-elements-buttons-item' ).addClass( 'disable' );
							}
							if ( tool_box.find( '#bp-group-messages-video-button' ) ) {
								tool_box.find( '#bp-group-messages-video-button' ).parents( '.post-elements-buttons-item' ).addClass( 'disable' );
							}
							if ( tool_box.find( '#bp-group-messages-gif-button' ) ) {
								tool_box.find( '#bp-group-messages-gif-button' ).parents( '.post-elements-buttons-item' ).addClass( 'disable' );
							}
							if ( tool_box.find( '#bp-group-messages-media-button' ) ) {
								tool_box.find( '#bp-group-messages-media-button' ).parents( '.post-elements-buttons-item' ).addClass( 'no-click' );
							}
						}
					);

					self.dropzone_obj.on(
						'uploadprogress',
						function( element ) {
							var circle = $( element.previewElement ).find('.dz-progress-ring circle')[0];
							var radius = circle.r.baseVal.value;
							var circumference = radius * 2 * Math.PI;

							circle.style.strokeDasharray = circumference + ' ' + circumference;
							circle.style.strokeDashoffset = circumference;
							var offset = circumference - element.upload.progress.toFixed( 0 ) / 100 * circumference;
							circle.style.strokeDashoffset = offset;
						}
					);

					self.dropzone_obj.on(
						'error',
						function ( file, response ) {
							if ( file.accepted ) {
								if ( typeof response !== 'undefined' && typeof response.data !== 'undefined' && typeof response.data.feedback !== 'undefined' ) {
									$( file.previewElement ).find( '.dz-error-message span' ).text( response.data.feedback );
								} else if( file.status == 'error' && ( file.xhr && file.xhr.status == 0) ) { // update server error text to user friendly
									$( file.previewElement ).find( '.dz-error-message span' ).text( BP_Nouveau.media.connection_lost_error );
								}
							} else {
								if ( !jQuery( '.group-media-error-popup' ).length ) {
									$( 'body' ).append( '<div id="bp-media-create-folder" style="display: block;" class="open-popup group-media-error-popup"><transition name="modal"><div class="modal-mask bb-white bbm-model-wrap"><div class="modal-wrapper"><div id="boss-media-create-album-popup" class="modal-container has-folderlocationUI"><header class="bb-model-header"><h4>' + BP_Nouveau.media.invalid_media_type + '</h4><a class="bb-model-close-button errorPopup" href="#"><span class="dashicons dashicons-no-alt"></span></a></header><div class="bb-field-wrap"><p>' + response + '</p></div></div></div></div></transition></div>' );
								}
								this.removeFile( file );
							}
						}
					);

					self.dropzone_obj.on(
						'success',
						function ( file, response ) {
							if ( response.data.id ) {
								file.id = response.id;
								response.data.uuid = file.upload.uuid;
								response.data.menu_order = $( file.previewElement ).closest( '.dropzone' ).find( file.previewElement ).index() - 1;
								response.data.album_id = self.album_id;
								response.data.group_id = self.group_id;
								response.data.saved = false;
								self.dropzone_media.push( response.data );
								self.addMediaIdsToGroupMessagesForm();
							} else {
								if ( !jQuery( '.group-message-error-popup' ).length ) {
									$( 'body' ).append( '<div id="bp-media-create-folder" style="display: block;" class="open-popup group-message-error-popup"><transition name="modal"><div class="modal-mask bb-white bbm-model-wrap"><div class="modal-wrapper"><div id="boss-media-create-album-popup" class="modal-container has-folderlocationUI"><header class="bb-model-header"><h4>' + BP_Nouveau.media.invalid_media_type + '</h4><a class="bb-model-close-button errorPopup" href="#"><span class="dashicons dashicons-no-alt"></span></a></header><div class="bb-field-wrap"><p>' + response.data.feedback + '</p></div></div></div></div></transition></div>' );
								}
								this.removeFile( file );
							}
						}
					);

					self.dropzone_obj.on(
						'removedfile',
						function ( file ) {
							if ( self.dropzone_media.length ) {
								for ( var i in self.dropzone_media ) {
									if ( file.upload.uuid == self.dropzone_media[ i ].uuid ) {

										if ( typeof self.dropzone_media[ i ].saved !== 'undefined' && !self.dropzone_media[ i ].saved ) {
											self.removeAttachment( self.dropzone_media[ i ].id );
										}

										self.dropzone_media.splice( i, 1 );
										self.addMediaIdsToGroupMessagesForm();
										break;
									}
								}
							}

							if ( !_.isNull( self.dropzone_obj.files ) && self.dropzone_obj.files.length === 0 ) {
								var tool_box = $( '#send_group_message_form' );
								if ( tool_box.find( '#bp-group-messages-document-button' ) ) {
									tool_box.find( '#bp-group-messages-document-button' ).parents( '.post-elements-buttons-item' ).removeClass( 'disable' );
								}
								if ( tool_box.find( '#bp-group-messages-video-button' ) ) {
									tool_box.find( '#bp-group-messages-video-button' ).parents( '.post-elements-buttons-item' ).removeClass( 'disable' );
								}
								if ( tool_box.find( '#bp-group-messages-gif-button' ) ) {
									tool_box.find( '#bp-group-messages-gif-button' ).parents( '.post-elements-buttons-item' ).removeClass( 'disable' );
								}
								if ( tool_box.find( '#bp-group-messages-media-button' ) ) {
									tool_box.find( '#bp-group-messages-media-button' ).parents( '.post-elements-buttons-item' ).removeClass( 'no-click' );
								}
							}
						}
					);

					// container class to open close.
					dropzone_container.removeClass( 'closed' ).addClass( 'open' );

					// reset gif component.
					self.resetGroupMessagesGifComponent();
					self.resetGroupMessagesDocumentComponent();
					self.resetGroupMessagesVideoComponent();

				} else {
					self.resetGroupMessagesMediaComponent();
				}

			}

		},

		openGroupMessagesDocumentUploader: function ( event ) {
			var self = this, document_dropzone_container = $( 'div#bp-group-messages-post-document-uploader' ),
				target = $( event.currentTarget );
			event.preventDefault();

			target.toggleClass( 'active' );
			if ( typeof window.Dropzone !== 'undefined' && document_dropzone_container.length ) {
				if ( document_dropzone_container.hasClass( 'closed' ) ) {
					// init dropzone.
					self.document_dropzone_obj = new Dropzone( 'div#bp-group-messages-post-document-uploader', self.documentOptions );

					self.document_dropzone_obj.on(
						'addedfile',
						function () {
						}
					);

					self.document_dropzone_obj.on(
						'sending',
						function ( file, xhr, formData ) {
							formData.append( 'action', 'document_document_upload' );
							formData.append( '_wpnonce', BP_Nouveau.nonces.media );

							var tool_box = $( '#send_group_message_form' );
							if ( tool_box.find( '#bp-group-messages-media-button' ) ) {
								tool_box.find( '#bp-group-messages-media-button' ).parents( '.post-elements-buttons-item' ).addClass( 'disable' );
							}
							if ( tool_box.find( '#bp-group-messages-video-button' ) ) {
								tool_box.find( '#bp-group-messages-video-button' ).parents( '.post-elements-buttons-item' ).addClass( 'disable' );
							}
							if ( tool_box.find( '#bp-group-messages-gif-button' ) ) {
								tool_box.find( '#bp-group-messages-gif-button' ).parents( '.post-elements-buttons-item' ).addClass( 'disable' );
							}
							if ( tool_box.find( '#bp-group-messages-document-button' ) ) {
								tool_box.find( '#bp-group-messages-document-button' ).parents( '.post-elements-buttons-item' ).addClass( 'no-click' );
							}
						}
					);

					self.document_dropzone_obj.on(
						'uploadprogress',
						function( element ) {
							var circle = $( element.previewElement ).find('.dz-progress-ring circle')[0];
							var radius = circle.r.baseVal.value;
							var circumference = radius * 2 * Math.PI;

							circle.style.strokeDasharray = circumference + ' ' + circumference;
							circle.style.strokeDashoffset = circumference;
							var offset = circumference - element.upload.progress.toFixed( 0 ) / 100 * circumference;
							circle.style.strokeDashoffset = offset;
						}
					);

					self.document_dropzone_obj.on(
						'error',
						function ( file, response ) {
							if ( file.accepted ) {
								if ( typeof response !== 'undefined' && typeof response.data !== 'undefined' && typeof response.data.feedback !== 'undefined' ) {
									$( file.previewElement ).find( '.dz-error-message span' ).text( response.data.feedback );
								} else if( file.status == 'error' && ( file.xhr && file.xhr.status == 0) ) { // update server error text to user friendly
									$( file.previewElement ).find( '.dz-error-message span' ).text( BP_Nouveau.media.connection_lost_error );
								}
							} else {
								if ( !jQuery( '.group-document-error-popup' ).length ) {
									$( 'body' ).append( '<div id="bp-media-create-folder" style="display: block;" class="open-popup group-document-error-popup"><transition name="modal"><div class="modal-mask bb-white bbm-model-wrap"><div class="modal-wrapper"><div id="boss-media-create-album-popup" class="modal-container has-folderlocationUI"><header class="bb-model-header"><h4>' + BP_Nouveau.media.invalid_file_type + '</h4><a class="bb-model-close-button errorPopup" href="#"><span class="dashicons dashicons-no-alt"></span></a></header><div class="bb-field-wrap"><p>' + response + '</p></div></div></div></div></transition></div>' );
								}
								this.removeFile( file );
							}
						}
					);

					self.document_dropzone_obj.on(
						'accept',
						function ( file, done ) {
							if ( file.size == 0 ) {
								done( BP_Nouveau.media.empty_document_type );
							} else {
								done();
							}
						}
					);

					self.document_dropzone_obj.on(
						'success',
						function ( file, response ) {
							if ( response.data.id ) {
								file.id = response.id;
								response.data.uuid = file.upload.uuid;
								response.data.menu_order = $( file.previewElement ).closest( '.dropzone' ).find( file.previewElement ).index() - 1;
								response.data.folder_id = self.current_folder;
								response.data.group_id = self.current_group_id;
								response.data.saved = false;
								self.dropzone_media.push( response.data );
								self.addDocumentIdsToGroupMessagesForm();

								var filename = file.upload.filename;
								var fileExtension = filename.substr( ( filename.lastIndexOf( '.' ) + 1 ) );
								var file_icon = ( !_.isUndefined( response.data.svg_icon ) ? response.data.svg_icon : '' );
								var icon_class = !_.isEmpty( file_icon ) ? file_icon : 'bb-icon-file-' + fileExtension;

								if ( $( file.previewElement ).find( '.dz-details .dz-icon .bb-icon-file' ).length ) {
									$( file.previewElement ).find( '.dz-details .dz-icon .bb-icon-file' ).removeClass( 'bb-icon-file' ).addClass( icon_class );
								}
							} else {
								var node, _i, _len, _ref, _results;
								var message = response.data.feedback;
								file.previewElement.classList.add( 'dz-error' );
								_ref = file.previewElement.querySelectorAll( '[data-dz-errormessage]' );
								_results = [];
								for ( _i = 0, _len = _ref.length; _i < _len; _i++ ) {
									node = _ref[ _i ];
									_results.push( node.textContent = message );
								}
								return _results;
							}
						}
					);

					self.document_dropzone_obj.on(
						'removedfile',
						function ( file ) {
							if ( self.dropzone_media.length ) {
								for ( var j in self.dropzone_media ) {
									if ( file.upload.uuid == self.dropzone_media[ j ].uuid ) {

										if ( typeof self.dropzone_media[ j ].saved !== 'undefined' && !self.dropzone_media[ j ].saved ) {
											self.removeAttachment( self.dropzone_media[ j ].id );
										}

										self.dropzone_media.splice( j, 1 );
										self.addDocumentIdsToGroupMessagesForm();
										break;
									}
								}
							}

							if ( !_.isNull( self.document_dropzone_obj.files ) && self.document_dropzone_obj.files.length === 0 ) {
								var tool_box = $( '#send_group_message_form' );
								if ( tool_box.find( '#bp-group-messages-media-button' ) ) {
									tool_box.find( '#bp-group-messages-media-button' ).parents( '.post-elements-buttons-item' ).removeClass( 'disable' );
								}
								if ( tool_box.find( '#bp-group-messages-video-button' ) ) {
									tool_box.find( '#bp-group-messages-video-button' ).parents( '.post-elements-buttons-item' ).removeClass( 'disable' );
								}
								if ( tool_box.find( '#bp-group-messages-gif-button' ) ) {
									tool_box.find( '#bp-group-messages-gif-button' ).parents( '.post-elements-buttons-item' ).removeClass( 'disable' );
								}
								if ( tool_box.find( '#bp-group-messages-document-button' ) ) {
									tool_box.find( '#bp-group-messages-document-button' ).parents( '.post-elements-buttons-item' ).removeClass( 'no-click' );
								}
							}
						}
					);

					// container class to open close.
					document_dropzone_container.removeClass( 'closed' ).addClass( 'open' );

					// reset gif component.
					self.resetGroupMessagesGifComponent();
					self.resetGroupMessagesMediaComponent();
					self.resetGroupMessagesVideoComponent();

				} else {
					self.resetGroupMessagesDocumentComponent();
				}

			}
		},

		openGroupMessagesVideoUploader: function ( event ) {
			var self = this, video_dropzone_container = $( 'div#bp-group-messages-post-video-uploader' ),
				target = $( event.currentTarget );
			event.preventDefault();

			target.toggleClass( 'active' );

			if ( typeof window.Dropzone !== 'undefined' && video_dropzone_container.length ) {

				if ( video_dropzone_container.hasClass( 'closed' ) ) {

					// init dropzone.
					self.video_dropzone_obj = new Dropzone( 'div#bp-group-messages-post-video-uploader', self.videoOptions );

					self.video_dropzone_obj.on(
						'sending',
						function ( file, xhr, formData ) {
							formData.append( 'action', 'video_upload' );
							formData.append( '_wpnonce', BP_Nouveau.nonces.video );

							var tool_box = $( '#send_group_message_form' );
							if ( tool_box.find( '#bp-group-messages-document-button' ) ) {
								tool_box.find( '#bp-group-messages-document-button' ).parents( '.post-elements-buttons-item' ).addClass( 'disable' );
							}
							if ( tool_box.find( '#bp-group-messages-gif-button' ) ) {
								tool_box.find( '#bp-group-messages-gif-button' ).parents( '.post-elements-buttons-item' ).addClass( 'disable' );
							}
							if ( tool_box.find( '#bp-group-messages-media-button' ) ) {
								tool_box.find( '#bp-group-messages-media-button' ).parents( '.post-elements-buttons-item' ).addClass( 'disable' );
							}
							if ( tool_box.find( '#bp-group-messages-video-button' ) ) {
								tool_box.find( '#bp-group-messages-video-button' ).parents( '.post-elements-buttons-item' ).addClass( 'no-click' );
							}
						}
					);

					self.video_dropzone_obj.on(
						'error',
						function ( file, response ) {
							if ( file.accepted ) {
								if ( typeof response !== 'undefined' && typeof response.data !== 'undefined' && typeof response.data.feedback !== 'undefined' ) {
									$( file.previewElement ).find( '.dz-error-message span' ).text( response.data.feedback );
								} else if( file.status == 'error' && ( file.xhr && file.xhr.status == 0) ) { // update server error text to user friendly
									$( file.previewElement ).find( '.dz-error-message span' ).text( BP_Nouveau.media.connection_lost_error );
								}
							} else {
								$( 'body' ).append( '<div id="bp-video-create-album" style="display: block;" class="open-popup"><transition name="modal"><div class="modal-mask bb-white bbm-model-wrap"><div class="modal-wrapper"><div id="boss-video-create-album-popup" class="modal-container has-folderlocationUI"><header class="bb-model-header"><h4>' + BP_Nouveau.video.invalid_video_type + '</h4><a class="bb-model-close-button errorPopup" href="#"><span class="dashicons dashicons-no-alt"></span></a></header><div class="bb-field-wrap"><p>' + response + '</p></div></div></div></div></transition></div>' );
								this.removeFile( file );
							}
						}
					);

					self.video_dropzone_obj.on(
						'accept',
						function ( file, done ) {
							if ( file.size == 0 ) {
								done( BP_Nouveau.video.empty_video_type );
							} else {
								done();
							}
						}
					);

					self.video_dropzone_obj.on(
						'addedfile',
						function ( file ) {

							if(file.dataURL) {
								// Get Thumbnail image from response.
							} else {

								if( bp.Nouveau.getVideoThumb ) {
									bp.Nouveau.getVideoThumb( file, '.dz-video-thumbnail' );
								}

							}
						}

					);

					self.video_dropzone_obj.on(
						'uploadprogress',
						function( element ) {

							var circle = $( element.previewElement ).find('.dz-progress-ring circle')[0];
							var radius = circle.r.baseVal.value;
							var circumference = radius * 2 * Math.PI;

							circle.style.strokeDasharray = circumference + ' ' + circumference;
							var offset = circumference - element.upload.progress.toFixed( 0 ) / 100 * circumference;

							if ( element.upload.progress <= 99 ) {
								$( element.previewElement ).find( '.dz-progress-count' ).text( element.upload.progress.toFixed( 0 ) + '% ' + BP_Nouveau.video.i18n_strings.video_uploaded_text );
								circle.style.strokeDashoffset = offset;
							} else if ( element.upload.progress === 100 ) {
								circle.style.strokeDashoffset = circumference - 0.99 * circumference;
								$( element.previewElement ).find( '.dz-progress-count' ).text( '99% ' + BP_Nouveau.video.i18n_strings.video_uploaded_text );
							}
						}
					);

					self.video_dropzone_obj.on(
						'success',
						function ( file, response ) {

							if ( file.upload.progress === 100 ) {
								$( file.previewElement ).find( '.dz-progress-ring circle' )[0].style.strokeDashoffset = 0;
								$( file.previewElement ).find( '.dz-progress-count' ).text( '100% ' + BP_Nouveau.video.i18n_strings.video_uploaded_text );
								$( file.previewElement ).closest( '.dz-preview' ).addClass( 'dz-complete' );
							}

							if ( response.data.id ) {
								file.id = response.id;
								response.data.uuid = file.upload.uuid;
								response.data.menu_order = $( file.previewElement ).closest( '.dropzone' ).find( file.previewElement ).index() - 1;
								response.data.album_id = self.album_id;
								response.data.group_id = self.group_id;
								response.data.js_preview  = $( file.previewElement ).find( '.dz-video-thumbnail img' ).attr( 'src' );
								response.data.saved = false;
								self.dropzone_media.push( response.data );
								self.addVideoIdsToGroupMessagesForm();
							} else {
								var node, _i, _len, _ref, _results;
								var message = response.data.feedback;
								file.previewElement.classList.add( 'dz-error' );
								_ref = file.previewElement.querySelectorAll( '[data-dz-errormessage]' );
								_results = [];
								for ( _i = 0, _len = _ref.length; _i < _len; _i++ ) {
									node = _ref[ _i ];
									_results.push( node.textContent = message );
								}
								return _results;
							}
						}
					);

					self.video_dropzone_obj.on(
						'removedfile',
						function ( file ) {
							if ( self.dropzone_media.length ) {
								for ( var i in self.dropzone_media ) {
									if ( file.upload.uuid == self.dropzone_media[ i ].uuid ) {

										if ( typeof self.dropzone_media[ i ].saved !== 'undefined' && !self.dropzone_media[ i ].saved ) {
											self.removeAttachment( self.dropzone_media[ i ].id );
										}

										self.dropzone_media.splice( i, 1 );
										self.addVideoIdsToGroupMessagesForm();
										break;
									}
								}
							}

							if ( !_.isNull( self.video_dropzone_obj.files ) && self.video_dropzone_obj.files.length === 0 ) {
								var tool_box = $( '#send_group_message_form' );
								if ( tool_box.find( '#bp-group-messages-document-button' ) ) {
									tool_box.find( '#bp-group-messages-document-button' ).parents( '.post-elements-buttons-item' ).removeClass( 'disable' );
								}
								if ( tool_box.find( '#bp-group-messages-gif-button' ) ) {
									tool_box.find( '#bp-group-messages-gif-button' ).parents( '.post-elements-buttons-item' ).removeClass( 'disable' );
								}
								if ( tool_box.find( '#bp-group-messages-media-button' ) ) {
									tool_box.find( '#bp-group-messages-media-button' ).parents( '.post-elements-buttons-item' ).removeClass( 'disable' );
								}
								if ( tool_box.find( '#bp-group-messages-video-button' ) ) {
									tool_box.find( '#bp-group-messages-video-button' ).parents( '.post-elements-buttons-item' ).removeClass( 'no-click' );
								}
							}
						}
					);

					// container class to open close.
					video_dropzone_container.removeClass( 'closed' ).addClass( 'open' );

					// reset gif component.
					self.resetGroupMessagesMediaComponent();
					self.resetGroupMessagesGifComponent();
					self.resetGroupMessagesDocumentComponent();

				} else {
					self.resetGroupMessagesVideoComponent();
				}

			}

		},

		openForumsDocumentUploader: function ( event ) {
			var self = this, target = $( event.currentTarget ),
				dropzone_container = target.closest( 'form' ).find( '#forums-post-document-uploader' ),
				media_dropzone_container = target.closest( 'form' ).find( '#forums-post-media-uploader' ),
				video_dropzone_container = target.closest( 'form' ).find( '#forums-post-video-uploader' ),
				edit_documents = [];
			event.preventDefault();

			target.toggleClass( 'active' );

			var media_dropzone_obj_key = media_dropzone_container.data( 'key' );
			var video_dropzone_obj_key = video_dropzone_container.data( 'key' );
			self.resetForumsMediaComponent( media_dropzone_obj_key );
			self.resetForumsVideoComponent( video_dropzone_obj_key );

			if ( typeof window.Dropzone !== 'undefined' && dropzone_container.length ) {

				var dropzone_obj_key = dropzone_container.data( 'key' );

				if ( dropzone_container.hasClass( 'closed' ) ) {

					// init dropzone.
					self.dropzone_obj[ dropzone_obj_key ] = new Dropzone( dropzone_container[ 0 ], self.documentOptions );
					self.dropzone_media[ dropzone_obj_key ] = [];

					self.dropzone_obj[ dropzone_obj_key ].on(
						'addedfile',
						function () {
						}
					);

					self.dropzone_obj[ dropzone_obj_key ].on(
						'sending',
						function ( file, xhr, formData ) {
							formData.append( 'action', 'document_document_upload' );
							formData.append( '_wpnonce', BP_Nouveau.nonces.media );

							var tool_box = target.closest( 'form' );
							tool_box.addClass( 'has-media' );
							if ( tool_box.find( '#forums-media-button' ) ) {
								tool_box.find( '#forums-media-button' ).parents( '.post-elements-buttons-item' ).addClass( 'disable' );
							}
							if ( tool_box.find( '#forums-gif-button' ) ) {
								tool_box.find( '#forums-gif-button' ).parents( '.post-elements-buttons-item' ).addClass( 'disable' );
							}
							if ( tool_box.find( '#forums-video-button' ) ) {
								tool_box.find( '#forums-video-button' ).parents( '.post-elements-buttons-item' ).addClass( 'disable' );
							}
							if ( tool_box.find( '#forums-document-button' ) ) {
								tool_box.find( '#forums-document-button' ).parents( '.post-elements-buttons-item' ).addClass( 'no-click' );
							}
						}
					);

					self.dropzone_obj[ dropzone_obj_key ].on(
						'uploadprogress',
						function( element ) {
							var formElement = target.closest( 'form' );
							formElement.addClass( 'media-uploading' );
							var circle = $( element.previewElement ).find('.dz-progress-ring circle')[0];
							var radius = circle.r.baseVal.value;
							var circumference = radius * 2 * Math.PI;

							circle.style.strokeDasharray = circumference + ' ' + circumference;
							circle.style.strokeDashoffset = circumference;
							var offset = circumference - element.upload.progress.toFixed( 0 ) / 100 * circumference;
							circle.style.strokeDashoffset = offset;
						}
					);

					self.dropzone_obj[ dropzone_obj_key ].on(
						'error',
						function ( file, response ) {
							if ( file.accepted ) {
								if ( typeof response !== 'undefined' && typeof response.data !== 'undefined' && typeof response.data.feedback !== 'undefined' ) {
									$( file.previewElement ).find( '.dz-error-message span' ).text( response.data.feedback );
								} else if( file.status == 'error' && ( file.xhr && file.xhr.status == 0) ) { // update server error text to user friendly
									$( file.previewElement ).find( '.dz-error-message span' ).text( BP_Nouveau.media.connection_lost_error );
								}
							} else {
								if ( !jQuery( '.forum-document-error-popup' ).length ) {
									$( 'body' ).append( '<div id="bp-media-create-folder" style="display: block;" class="open-popup forum-document-error-popup"><transition name="modal"><div class="modal-mask bb-white bbm-model-wrap"><div class="modal-wrapper"><div id="boss-media-create-album-popup" class="modal-container has-folderlocationUI"><header class="bb-model-header"><h4>' + BP_Nouveau.media.invalid_file_type + '</h4><a class="bb-model-close-button errorPopup" href="#"><span class="dashicons dashicons-no-alt"></span></a></header><div class="bb-field-wrap"><p>' + response + '</p></div></div></div></div></transition></div>' );
								}
								this.removeFile( file );
								var formElement = target.closest( 'form' );
								formElement.removeClass( 'media-uploading' );
							}
						}
					);

					self.dropzone_obj[ dropzone_obj_key ].on(
						'accept',
						function ( file, done ) {
							if ( file.size == 0 ) {
								done( BP_Nouveau.media.empty_document_type );
							} else {
								done();
							}
						}
					);

					self.dropzone_obj[ dropzone_obj_key ].on(
						'success',
						function ( file, response ) {
							if ( response.data.id ) {
								file.id = response.id;
								response.data.uuid = file.upload.uuid;
								response.data.menu_order = $( file.previewElement ).closest( '.dropzone' ).find( file.previewElement ).index() - 1;
								response.data.folder_id = self.current_folder;
								response.data.group_id = self.current_group_id;
								response.data.saved = false;
								self.dropzone_media[ dropzone_obj_key ].push( response.data );
								self.addDocumentIdsToForumsForm( dropzone_container );

								var filename = file.upload.filename;
								var fileExtension = filename.substr( ( filename.lastIndexOf( '.' ) + 1 ) );
								var file_icon = ( !_.isUndefined( response.data.svg_icon ) ? response.data.svg_icon : '' );
								var icon_class = !_.isEmpty( file_icon ) ? file_icon : 'bb-icon-file-' + fileExtension;

								if ( $( file.previewElement ).find( '.dz-details .dz-icon .bb-icon-file' ).length ) {
									$( file.previewElement ).find( '.dz-details .dz-icon .bb-icon-file' ).removeClass( 'bb-icon-file' ).addClass( icon_class );
								}
							} else {
								var node, _i, _len, _ref, _results;
								var message = response.data.feedback;
								file.previewElement.classList.add( 'dz-error' );
								_ref = file.previewElement.querySelectorAll( '[data-dz-errormessage]' );
								_results = [];
								for ( _i = 0, _len = _ref.length; _i < _len; _i++ ) {
									node = _ref[ _i ];
									_results.push( node.textContent = message );
								}
								return _results;
							}
						}
					);

					self.dropzone_obj[ dropzone_obj_key ].on(
						'removedfile',
						function ( file ) {

							if ( true === bp.Nouveau.Media.reply_topic_allow_delete_media ) {

								if ( self.dropzone_media[ dropzone_obj_key ].length ) {
									for ( var i in self.dropzone_media[ dropzone_obj_key ] ) {
										if ( file.upload.uuid == self.dropzone_media[ dropzone_obj_key ][ i ].uuid ) {

											if ( (
											     ! this.bbp_is_reply_edit && ! this.bbp_is_topic_edit && ! this.bbp_is_forum_edit
											     ) && typeof self.dropzone_media[ dropzone_obj_key ][ i ].saved !== 'undefined' && ! self.dropzone_media[ dropzone_obj_key ][ i ].saved && 'edit' === bp.Nouveau.Media.reply_topic_display_post ) {
												self.removeAttachment( self.dropzone_media[ dropzone_obj_key ][ i ].id );
											}

											self.dropzone_media[ dropzone_obj_key ].splice( i, 1 );
											self.addDocumentIdsToForumsForm( dropzone_container );
											break;
										}
									}
								}

								if ( ! _.isNull( self.dropzone_obj[ dropzone_obj_key ].files ) && self.dropzone_obj[ dropzone_obj_key ].files.length === 0 ) {
									var tool_box = target.closest( 'form' );
									if ( tool_box.find( '#forums-media-button' ) ) {
										tool_box.find( '#forums-media-button' ).parents( '.post-elements-buttons-item' ).removeClass( 'disable' );
									}
									if ( tool_box.find( '#forums-video-button' ) ) {
										tool_box.find( '#forums-video-button' ).parents( '.post-elements-buttons-item' ).removeClass( 'disable' );
									}
									if ( tool_box.find( '#forums-gif-button' ) ) {
										tool_box.find( '#forums-gif-button' ).parents( '.post-elements-buttons-item' ).removeClass( 'disable' );
									}
									if ( tool_box.find( '#forums-document-button' ) ) {
										tool_box.find( '#forums-document-button' ).parents( '.post-elements-buttons-item' ).removeClass( 'no-click' );
									}
								}

							}

							if ( ! _.isNull( self.dropzone_obj[ dropzone_obj_key ].files ) && self.dropzone_obj[ dropzone_obj_key ].files.length === 0 ) {
								var targetForm = target.closest( 'form' );
								targetForm.removeClass( 'has-media' );
							}
						}
					);

					// Enable submit button when all documents are uploaded
					self.dropzone_obj[ dropzone_obj_key ].on(
						'complete',
						function( file ) {
							if ( this.getUploadingFiles().length === 0 && this.getQueuedFiles().length === 0 && this.files.length > 0 ) {
								var formElement = target.closest( 'form' );
								formElement.removeClass( 'media-uploading' );
							}

							var filename  = !_.isUndefined( file.name ) ? file.name : '';
							var fileExtension = filename.substr( ( filename.lastIndexOf( '.' ) + 1 ) );
							var file_icon     = ( ! _.isUndefined( file.svg_icon ) ? file.svg_icon : '' );
							var icon_class    = ! _.isEmpty( file_icon ) ? file_icon : 'bb-icon-file-' + fileExtension;

							if (
								$( file.previewElement ).find( '.dz-details .dz-icon .bb-icon-file' ).length  &&
								$( file.previewElement ).find( '.dz-details .dz-icon .bb-icon-file' ).hasClass( 'bb-icon-file' )
							) {
								$( file.previewElement ).find( '.dz-details .dz-icon .bb-icon-file' ).removeClass( 'bb-icon-file' ).addClass( icon_class );
							}
						}
					);

					// container class to open close.
					dropzone_container.removeClass( 'closed' ).addClass( 'open' );

					// reset gif component.
					self.resetForumsGifComponent( event );

					// Load documents while edit forum/topic/reply.
					if ( ( this.bbp_is_reply_edit || this.bbp_is_topic_edit || this.bbp_is_forum_edit ) &&
						( this.bbp_reply_edit_document.length || this.bbp_topic_edit_document.length || this.bbp_forum_edit_document.length ) ) {

						if ( this.bbp_reply_edit_document.length ) {
							edit_documents = this.bbp_reply_edit_document;
						} else if ( this.bbp_topic_edit_document.length ) {
							edit_documents = this.bbp_topic_edit_document;
						} else if ( this.bbp_forum_edit_document.length ) {
							edit_documents = this.bbp_forum_edit_document;
						}

						if ( edit_documents.length ) {
							var mock_file = false;
							for ( var d = 0; d < edit_documents.length; d++ ) {
								mock_file = false;
								self.dropzone_media[ dropzone_obj_key ].push(
									{
										'id': edit_documents[ d ].attachment_id,
										'document_id': edit_documents[ d ].id,
										'name': edit_documents[ d ].name,
										'type': 'document',
										'title': edit_documents[ d ].name,
										'size': edit_documents[ d ].size,
										'url': edit_documents[ d ].url,
										'uuid': edit_documents[ d ].id,
										'menu_order': d,
										'saved': true
									}
								);

								mock_file = {
									name: edit_documents[ d ].name,
									size: edit_documents[ d ].size,
									accepted: true,
									kind: 'document',
									upload: {
										name: edit_documents[ d ].name,
										filename: edit_documents[ d ].name,
										title: edit_documents[ d ].name,
										size: edit_documents[ d ].size,
										uuid: edit_documents[ d ].id
									},
									dataURL: edit_documents[ d ].url,
									id: edit_documents[ d ].id
								};

								self.dropzone_obj[ dropzone_obj_key ].files.push( mock_file );
								self.dropzone_obj[ dropzone_obj_key ].emit( 'addedfile', mock_file );
								self.dropzone_obj[ dropzone_obj_key ].emit( 'complete', mock_file );
							}
							self.addDocumentIdsToForumsForm( dropzone_container );

							// Disable other buttons( media/gif ).
							if ( !_.isNull( self.dropzone_obj[ dropzone_obj_key ].files ) && self.dropzone_obj[ dropzone_obj_key ].files.length !== 0 ) {
								var tool_box = target.closest( 'form' );
								if ( tool_box.find( '#forums-media-button' ) ) {
									tool_box.find( '#forums-media-button' ).parents( '.post-elements-buttons-item' ).addClass( 'disable' );
								}
								if ( tool_box.find( '#forums-video-button' ) ) {
									tool_box.find( '#forums-video-button' ).parents( '.post-elements-buttons-item' ).addClass( 'disable' );
								}
								if ( tool_box.find( '#forums-gif-button' ) ) {
									tool_box.find( '#forums-gif-button' ).parents( '.post-elements-buttons-item' ).addClass( 'disable' );
								}
								if ( tool_box.find( '#forums-document-button' ) ) {
									tool_box.find( '#forums-document-button' ).parents( '.post-elements-buttons-item' ).addClass( 'no-click' );
								}
							}

						}
					}

				} else {
					self.resetForumsDocumentComponent( dropzone_obj_key );
				}

			}

		},

		openForumsVideoUploader: function ( event ) {
			var self = this, target = $( event.currentTarget ),
				dropzone_container = target.closest( 'form' ).find( '#forums-post-video-uploader' ),
				forum_dropzone_container = target.closest( 'form' ).find( '#forums-post-media-uploader' ),
				forum_document_dropzone_container = target.closest( 'form' ).find( '#forums-post-document-uploader' ),
				edit_videos = [];
			event.preventDefault();

			target.toggleClass( 'active' );

			var media_dropzone_obj_key = forum_dropzone_container.data( 'key' );
			var document_dropzone_obj_key = forum_document_dropzone_container.data( 'key' );

			self.resetForumsMediaComponent( media_dropzone_obj_key );
			self.resetForumsDocumentComponent( document_dropzone_obj_key );

			if ( typeof window.Dropzone !== 'undefined' && dropzone_container.length ) {

				var dropzone_obj_key = dropzone_container.data( 'key' );

				if ( dropzone_container.hasClass( 'closed' ) ) {

					// init dropzone.
					self.dropzone_obj[ dropzone_obj_key ] = new Dropzone( dropzone_container[ 0 ], self.videoOptions );
					self.dropzone_media[ dropzone_obj_key ] = [];

					self.dropzone_obj[ dropzone_obj_key ].on(
						'sending',
						function ( file, xhr, formData ) {
							formData.append( 'action', 'video_upload' );
							formData.append( '_wpnonce', BP_Nouveau.nonces.video );

							var tool_box = target.closest( 'form' );
							tool_box.addClass( 'has-media' );
							if ( tool_box.find( '#forums-media-button' ) ) {
								tool_box.find( '#forums-media-button' ).parents( '.post-elements-buttons-item' ).addClass( 'disable' );
							}
							if ( tool_box.find( '#forums-gif-button' ) ) {
								tool_box.find( '#forums-gif-button' ).parents( '.post-elements-buttons-item' ).addClass( 'disable' );
							}
							if ( tool_box.find( '#forums-document-button' ) ) {
								tool_box.find( '#forums-document-button' ).parents( '.post-elements-buttons-item' ).addClass( 'disable' );
							}
							if ( tool_box.find( '#forums-video-button' ) ) {
								tool_box.find( '#forums-video-button' ).parents( '.post-elements-buttons-item' ).addClass( 'no-click' );
							}
						}
					);

					self.dropzone_obj[ dropzone_obj_key ].on(
						'error',
						function ( file, response ) {
							if ( file.accepted ) {
								if ( typeof response !== 'undefined' && typeof response.data !== 'undefined' && typeof response.data.feedback !== 'undefined' ) {
									$( file.previewElement ).find( '.dz-error-message span' ).text( response.data.feedback );
								} else if( file.status == 'error' && ( file.xhr && file.xhr.status == 0) ) { // update server error text to user friendly
									$( file.previewElement ).find( '.dz-error-message span' ).text( BP_Nouveau.media.connection_lost_error );
								}
							} else {
								if ( !jQuery( '.forum-video-error-popup' ).length ) {
									$( 'body' ).append( '<div id="bp-video-create-album" style="display: block;" class="open-popup forum-video-error-popup"><transition name="modal"><div class="modal-mask bb-white bbm-model-wrap"><div class="modal-wrapper"><div id="boss-video-create-album-popup" class="modal-container has-folderlocationUI"><header class="bb-model-header"><h4>' + BP_Nouveau.video.invalid_video_type + '</h4><a class="bb-model-close-button errorPopup" href="#"><span class="dashicons dashicons-no-alt"></span></a></header><div class="bb-field-wrap"><p>' + response + '</p></div></div></div></div></transition></div>' );
								}
								this.removeFile( file );
								var formElement = target.closest( 'form' );
								formElement.removeClass( 'media-uploading' );
							}
						}
					);

					self.dropzone_obj[ dropzone_obj_key ].on(
						'accept',
						function ( file, done ) {
							if ( file.size == 0 ) {
								done( BP_Nouveau.video.empty_video_type );
							} else {
								done();
							}
						}
					);

					self.dropzone_obj[ dropzone_obj_key ].on(
						'addedfile',
						function ( file ) {

							if( file.dataURL && file.dataThumb && file.dataThumb.length ) {
								// Get Thumbnail image from response.
								$( file.previewElement ).find( '.dz-video-thumbnail' ).prepend('<img src=" ' + file.dataThumb + ' " />');
							} else {

								if( bp.Nouveau.getVideoThumb ) {
									bp.Nouveau.getVideoThumb( file, '.dz-video-thumbnail' );
								}

							}
						}

					);

					self.dropzone_obj[ dropzone_obj_key ].on(
						'uploadprogress',
						function( element ) {
							var formElement = target.closest( 'form' );
							formElement.addClass( 'media-uploading' );
							var circle = $( element.previewElement ).find('.dz-progress-ring circle')[0];
							var radius = circle.r.baseVal.value;
							var circumference = radius * 2 * Math.PI;

							circle.style.strokeDasharray = circumference + ' ' + circumference;
							var offset = circumference - element.upload.progress.toFixed( 0 ) / 100 * circumference;
							if ( element.upload.progress <= 99 ) {
								$( element.previewElement ).find( '.dz-progress-count' ).text( element.upload.progress.toFixed( 0 ) + '% ' + BP_Nouveau.video.i18n_strings.video_uploaded_text );
								circle.style.strokeDashoffset = offset;
							} else if ( element.upload.progress === 100 ) {
								circle.style.strokeDashoffset = circumference - 0.99 * circumference;
								$( element.previewElement ).find( '.dz-progress-count' ).text( '99% ' + BP_Nouveau.video.i18n_strings.video_uploaded_text );
							}
						}
					);

					self.dropzone_obj[ dropzone_obj_key ].on(
						'success',
						function ( file, response ) {

							if ( file.upload.progress === 100 ) {
								$( file.previewElement ).find( '.dz-progress-ring circle' )[0].style.strokeDashoffset = 0;
								$( file.previewElement ).find( '.dz-progress-count' ).text( '100% ' + BP_Nouveau.video.i18n_strings.video_uploaded_text );
								$( file.previewElement ).closest( '.dz-preview' ).addClass( 'dz-complete' );
							}

							if ( response.data.id ) {
								file.id = response.id;
								response.data.uuid = file.upload.uuid;
								response.data.menu_order = $( file.previewElement ).closest( '.dropzone' ).find( file.previewElement ).index() - 1;
								response.data.album_id = self.album_id;
								response.data.group_id = self.group_id;
								response.data.js_preview  = $( file.previewElement ).find( '.dz-video-thumbnail img' ).attr( 'src' );
								response.data.saved = false;
								self.dropzone_media[ dropzone_obj_key ].push( response.data );
								self.addVideoIdsToForumsForm( dropzone_container );
							} else {
								var node, _i, _len, _ref, _results;
								var message = response.data.feedback;
								file.previewElement.classList.add( 'dz-error' );
								_ref = file.previewElement.querySelectorAll( '[data-dz-errormessage]' );
								_results = [];
								for ( _i = 0, _len = _ref.length; _i < _len; _i++ ) {
									node = _ref[ _i ];
									_results.push( node.textContent = message );
								}
								return _results;
							}
						}
					);

					self.dropzone_obj[ dropzone_obj_key ].on(
						'removedfile',
						function ( file ) {

							if ( true === bp.Nouveau.Media.reply_topic_allow_delete_media ) {

								if ( self.dropzone_media[ dropzone_obj_key ].length ) {
									for ( var i in self.dropzone_media[ dropzone_obj_key ] ) {
										if ( file.upload.uuid == self.dropzone_media[ dropzone_obj_key ][ i ].uuid ) {

											if ( (
											     ! this.bbp_is_reply_edit && ! this.bbp_is_topic_edit && ! this.bbp_is_forum_edit
											     ) && typeof self.dropzone_media[ dropzone_obj_key ][ i ].saved !== 'undefined' && ! self.dropzone_media[ dropzone_obj_key ][ i ].saved && 'edit' === bp.Nouveau.Media.reply_topic_display_post ) {
												self.removeAttachment( self.dropzone_media[ dropzone_obj_key ][ i ].id );
											}

											self.dropzone_media[ dropzone_obj_key ].splice( i, 1 );
											self.addVideoIdsToForumsForm( dropzone_container );
											break;
										}
									}
								}

								if ( ! _.isNull( self.dropzone_obj[ dropzone_obj_key ].files ) && self.dropzone_obj[ dropzone_obj_key ].files.length === 0 ) {
									var tool_box = target.closest( 'form' );
									if ( tool_box.find( '#forums-media-button' ) ) {
										tool_box.find( '#forums-media-button' ).parents( '.post-elements-buttons-item' ).removeClass( 'disable' );
									}
									if ( tool_box.find( '#forums-gif-button' ) ) {
										tool_box.find( '#forums-gif-button' ).parents( '.post-elements-buttons-item' ).removeClass( 'disable' );
									}
									if ( tool_box.find( '#forums-document-button' ) ) {
										tool_box.find( '#forums-document-button' ).parents( '.post-elements-buttons-item' ).removeClass( 'disable' );
									}
									if ( tool_box.find( '#forums-video-button' ) ) {
										tool_box.find( '#forums-video-button' ).parents( '.post-elements-buttons-item' ).removeClass( 'no-click' );
									}
								}

							}

							if ( ! _.isNull( self.dropzone_obj[ dropzone_obj_key ].files ) && self.dropzone_obj[ dropzone_obj_key ].files.length === 0 ) {
								var targetForm = target.closest( 'form' );
								targetForm.removeClass( 'has-media' );
							}
						}
					);

					// Enable submit button when all videos are uploaded
					self.dropzone_obj[ dropzone_obj_key ].on(
						'complete',
						function() {
							if ( this.getUploadingFiles().length === 0 && this.getQueuedFiles().length === 0 && this.files.length > 0 ) {
								var formElement = target.closest( 'form' );
								formElement.removeClass( 'media-uploading' );
							}
						}
					);

					// container class to open close.
					dropzone_container.removeClass( 'closed' ).addClass( 'open' );


					// reset gif component.
					self.resetForumsGifComponent( event );

					// Load video while edit forum/topic/reply.
					if ( ( this.bbp_is_reply_edit || this.bbp_is_topic_edit || this.bbp_is_forum_edit ) &&
						( this.bbp_reply_edit_video.length || this.bbp_topic_edit_video.length || this.bbp_forum_edit_video.length ) ) {

						if ( this.bbp_reply_edit_video.length ) {
							edit_videos = this.bbp_reply_edit_video;
						} else if ( this.bbp_topic_edit_video.length ) {
							edit_videos = this.bbp_topic_edit_video;
						} else if ( this.bbp_forum_edit_video.length ) {
							edit_videos = this.bbp_forum_edit_video;
						}

						if ( edit_videos.length ) {
							var mock_file = false;
							for ( var v = 0; v < edit_videos.length; v++ ) {
								mock_file = false;
								self.dropzone_media[ dropzone_obj_key ].push(
									{
										'id': edit_videos[ v ].attachment_id,
										'video_id': edit_videos[ v ].id,
										'name': edit_videos[ v ].name,
										'type': 'video',
										'title': edit_videos[ v ].name,
										'size': edit_videos[ v ].size,
										'url': edit_videos[ v ].url,
										'uuid': edit_videos[ v ].id,
										'thumb': edit_videos[ v ].thumb,
										'menu_order': v,
										'saved': true,
									}
								);

								mock_file = {
									name: edit_videos[ v ].name,
									size: edit_videos[ v ].size,
									accepted: true,
									kind: 'video',
									upload: {
										name: edit_videos[ v ].name,
										title: edit_videos[ v ].name,
										size: edit_videos[ v ].size,
										uuid: edit_videos[ v ].id
									},
									dataURL: edit_videos[ v ].url,
									dataThumb: edit_videos[ v ].thumb,
									id: edit_videos[ v ].id
								};

								self.dropzone_obj[ dropzone_obj_key ].files.push( mock_file );
								self.dropzone_obj[ dropzone_obj_key ].emit( 'addedfile', mock_file );
								self.dropzone_obj[ dropzone_obj_key ].emit( 'complete', mock_file );
							}
							self.addVideoIdsToForumsForm( dropzone_container );

							// Disable other buttons( media/gif ).
							if ( !_.isNull( self.dropzone_obj[ dropzone_obj_key ].files ) && self.dropzone_obj[ dropzone_obj_key ].files.length !== 0 ) {
								var tool_box = target.closest( 'form' );
								if ( tool_box.find( '#forums-media-button' ) ) {
									tool_box.find( '#forums-media-button' ).parents( '.post-elements-buttons-item' ).addClass( 'disable' );
								}
								if ( tool_box.find( '#forums-gif-button' ) ) {
									tool_box.find( '#forums-gif-button' ).parents( '.post-elements-buttons-item' ).addClass( 'disable' );
								}
								if ( tool_box.find( '#forums-document-button' ) ) {
									tool_box.find( '#forums-document-button' ).parents( '.post-elements-buttons-item' ).addClass( 'disable' );
								}
								if ( tool_box.find( '#forums-video-button' ) ) {
									tool_box.find( '#forums-video-button' ).parents( '.post-elements-buttons-item' ).addClass( 'no-click' );
								}
							}

						}
					}

				} else {
					self.resetForumsVideoComponent( dropzone_obj_key );
				}

			}

		},

		addMediaIdsToForumsForm: function ( dropzone_container ) {
			var self = this, dropzone_obj_key = dropzone_container.data( 'key' );
			if ( dropzone_container.closest( '#whats-new-attachments' ).find( '#bbp_media' ).length ) {
				dropzone_container.closest( '#whats-new-attachments' ).find( '#bbp_media' ).val( JSON.stringify( self.dropzone_media[ dropzone_obj_key ] ) );
			}
		},

		addDocumentIdsToForumsForm: function ( dropzone_container ) {
			var self = this, dropzone_obj_key = dropzone_container.data( 'key' );
			if ( dropzone_container.closest( '#whats-new-attachments' ).find( '#bbp_document' ).length ) {
				dropzone_container.closest( '#whats-new-attachments' ).find( '#bbp_document' ).val( JSON.stringify( self.dropzone_media[ dropzone_obj_key ] ) );
			}
		},

		addVideoIdsToForumsForm: function ( dropzone_container ) {
			var self = this, dropzone_obj_key = dropzone_container.data( 'key' );
			if ( dropzone_container.closest( '#whats-new-attachments' ).find( '#bbp_video' ).length ) {
				dropzone_container.closest( '#whats-new-attachments' ).find( '#bbp_video' ).val( JSON.stringify( self.dropzone_media[ dropzone_obj_key ] ) );
			}
		},

		createThumbnailFromUrl: function ( mock_file, dropzone_container ) {
			var self = this, dropzone_obj_key = dropzone_container.data( 'key' );
			self.dropzone_obj[ dropzone_obj_key ].createThumbnailFromUrl(
				mock_file,
				self.dropzone_obj[ dropzone_obj_key ].options.thumbnailWidth,
				self.dropzone_obj[ dropzone_obj_key ].options.thumbnailHeight,
				self.dropzone_obj[ dropzone_obj_key ].options.thumbnailMethod,
				true,
				function ( thumbnail ) {
					self.dropzone_obj[ dropzone_obj_key ].emit( 'thumbnail', mock_file, thumbnail );
					self.dropzone_obj[ dropzone_obj_key ].emit( 'complete', mock_file );
				}
			);
		},

		openUploader: function ( event ) {
			var self = this;
			event.preventDefault();
			var currentTarget, parentsOpen;

			if ( typeof window.Dropzone !== 'undefined' && $( 'div#media-uploader' ).length ) {

				$( '#bp-media-uploader' ).addClass( 'open-popup' ).show();

				if ( $( event.currentTarget ).closest( '#bp-media-single-album' ).length ) {
					$( '#bb-media-privacy' ).hide();
				}

				if ( $( '#bp-media-uploader' ).find( '.bb-field-steps.bb-field-steps-2' ).length ) {
					currentTarget = '#bp-media-uploader.bp-media-photo-uploader';
					if ( Number( $( currentTarget ).find( '.bb-album-selected-id' ).data( 'value' ) ) !== 0 ) {
						parentsOpen = $( currentTarget ).find( '.bb-album-selected-id' ).data( 'value' );
						$( currentTarget ).find( '#bb-document-privacy' ).prop( 'disabled', true );
					} else {
						parentsOpen = 0;
					}
					if ( '' !== this.moveToIdPopup ) {
						$.ajax(
							{
								url: BP_Nouveau.ajaxurl,
								type: 'post',
								data: {
									action: 'media_get_album_view',
									id: this.moveToIdPopup,
									type: this.moveToTypePopup,
								}, success: function ( response ) {
									$( document ).find( '.location-album-list-wrap h4 span.where-to-move-profile-or-group-media' ).html( response.data.first_span_text );
									if ( '' === response.data.html ) {
										$( document ).find( '.open-popup .location-album-list-wrap' ).hide();
										$( document ).find( '.open-popup .location-album-list-wrap-main span.no-album-exists' ).show();
									} else {
										$( document ).find( '.open-popup .location-album-list-wrap-main span.no-album-exists' ).hide();
										$( document ).find( '.open-popup .location-album-list-wrap' ).show();
									}

									if ( false === response.data.create_album ) {
										$( document ).find( '.open-popup .bp-media-open-create-popup-folder' ).removeClass( 'create-album' );
										$( document ).find( '.open-popup .bp-media-open-create-popup-folder' ).hide( );
									} else {
										$( document ).find( '.open-popup .bp-media-open-create-popup-folder' ).addClass( 'create-album' );
									}

									$( document ).find( '.popup-on-fly-create-album .privacy-field-wrap-hide-show' ).show();
									$( document ).find( '.open-popup .bb-album-create-from' ).val( 'profile' );

									$( currentTarget ).find( '.location-album-list-wrap .location-album-list' ).remove();
									$( currentTarget ).find( '.location-album-list-wrap' ).append( response.data.html );
									$( currentTarget ).find( 'ul.location-album-list span[data-id="' + parentsOpen + '"]' ).trigger( 'click' );
									$( currentTarget ).find( '.bb-album-selected-id' ).val( parentsOpen );
								}
							}
						);
					}
				}

				$( document ).on( 'click', currentTarget + ' .location-album-list li span', function ( e ) {
					e.preventDefault();
					if ( $( this ).parent().hasClass( 'is_active' ) ) {
						return;
					}
					if ( $( this ).closest( '.location-album-list-wrap' ).find( '.breadcrumb .item span:last-child' ).data( 'id' ) != 0 ) {
						$( this ).closest( '.location-album-list-wrap' ).find( '.breadcrumb .item span:last-child' ).remove();
					}
					$( this ).closest( '.location-album-list-wrap' ).find( '.breadcrumb .item' ).append( '<span class="is-disabled" data-id="' + $( this ).attr( 'id' ) + '">' + $( this ).text() + '</span>' );
					$( this ).addClass( 'selected' ).parent().addClass( 'is_active' ).siblings().removeClass( 'is_active' ).children( 'span' ).removeClass( 'selected' );
					if ( parentsOpen == $( e.currentTarget ).data( 'id' ) ) {
						$( e.currentTarget ).closest( '.bb-field-wrap' ).find( '.bb-model-footer .bp-media-move' ).addClass( 'is-disabled' );
					} else {
						$( e.currentTarget ).closest( '.bb-field-wrap' ).find( '.bb-model-footer .bp-media-move' ).removeClass( 'is-disabled' );
					}
					if ( $( e.currentTarget ).closest( '.bb-field-wrap' ).find( '.bb-model-footer .bp-media-move' ).hasClass( 'is-disabled' ) ) {
						return; //return if parent album is same.
					}
					$( e.currentTarget ).closest( '.bb-field-wrap' ).find( '.bb-album-selected-id' ).val( $( e.currentTarget ).data( 'id' ) );

					var mediaPrivacy = $( e.currentTarget ).closest( '#bp-media-uploader' ).find( '#bb-media-privacy' );

					if ( Number( $( e.currentTarget ).data( 'id' ) ) !== 0 ) {
						mediaPrivacy.find( 'option' ).removeAttr( 'selected' );
						mediaPrivacy.val( $( e.currentTarget ).parent().data( 'privacy' ) );
						mediaPrivacy.prop( 'disabled', true );
					} else {
						mediaPrivacy.find( 'option' ).removeAttr( 'selected' );
						mediaPrivacy.val( 'public' );
						mediaPrivacy.prop( 'disabled', false );
					}
				} );

				$( document ).on( 'click', currentTarget + ' .breadcrumb .item > span', function ( e ) {

					if ( $( this ).hasClass( 'is-disabled' ) ) {
						return;
					}

					$( e.currentTarget ).closest( '.bb-field-wrap' ).find( '.bb-album-selected-id' ).val( 0 );
					$( e.currentTarget ).closest( '.bb-field-wrap' ).find( '.location-album-list li span' ).removeClass( 'selected' ).parent().removeClass( 'is_active' );

					if ( $( this ).closest( '.location-album-list-wrap' ).find( '.breadcrumb .item span:last-child' ).hasClass( 'is-disabled' ) ) {
						$( this ).closest( '.location-album-list-wrap' ).find( '.breadcrumb .item span:last-child' ).remove();
					}

					if ( parentsOpen == $( e.currentTarget ).data( 'id' ) ) {
						$( e.currentTarget ).closest( '.bb-field-wrap' ).find( '.bb-model-footer .bp-media-move' ).addClass( 'is-disabled' );
					} else {
						$( e.currentTarget ).closest( '.bb-field-wrap' ).find( '.bb-model-footer .bp-media-move' ).removeClass( 'is-disabled' );
					}
					var mediaPrivacy = $( e.currentTarget ).closest( '#bp-media-uploader' ).find( '#bb-media-privacy' );
					var selectedAlbumPrivacy = $( e.currentTarget ).closest( '#bp-media-uploader' ).find( '.location-album-list li.is_active' ).data( 'privacy' );
					if ( Number( $( e.currentTarget ).closest( '.bb-field-wrap' ).find( '.bb-album-selected-id' ).val() ) !== 0 ) {
						mediaPrivacy.find( 'option' ).removeAttr( 'selected' );
						mediaPrivacy.val( selectedAlbumPrivacy === undefined ? 'public' : selectedAlbumPrivacy );
						mediaPrivacy.prop( 'disabled', true );
					} else {
						mediaPrivacy.find( 'option' ).removeAttr( 'selected' );
						mediaPrivacy.val( 'public' );
						mediaPrivacy.prop( 'disabled', false );
					}

				} );

				var uploaderMediaTemplate = document.getElementsByClassName('uploader-post-media-template').length ? document.getElementsByClassName('uploader-post-media-template')[0].innerHTML : ''; //Check to avoid error if Node is missing.

				self.options.previewTemplate = uploaderMediaTemplate;

				self.dropzone_obj = new Dropzone( 'div#media-uploader', self.options );

				self.dropzone_obj.on(
					'sending',
					function ( file, xhr, formData ) {
						formData.append( 'action', 'media_upload' );
						formData.append( '_wpnonce', BP_Nouveau.nonces.media );
					}
				);

				self.dropzone_obj.on(
					'uploadprogress',
					function( element ) {var circle = $( element.previewElement ).find('.dz-progress-ring circle')[0];
						var radius = circle.r.baseVal.value;
						var circumference = radius * 2 * Math.PI;

						circle.style.strokeDasharray = circumference + ' ' + circumference;
						circle.style.strokeDashoffset = circumference;
						var offset = circumference - element.upload.progress.toFixed( 0 ) / 100 * circumference;
						circle.style.strokeDashoffset = offset;
					}
				);

				self.dropzone_obj.on(
					'addedfile',
					function () {
						setTimeout(
							function () {
								if ( self.dropzone_obj.getAcceptedFiles().length ) {
									$( '#bp-media-uploader-modal-status-text' ).text( wp.i18n.sprintf( BP_Nouveau.media.i18n_strings.upload_status, self.dropzone_media.length, self.dropzone_obj.getAcceptedFiles().length ) ).show();
								}
							},
							1000
						);
					}
				);

				self.dropzone_obj.on(
					'error',
					function ( file, response ) {
						if ( file.accepted ) {
							if ( typeof response !== 'undefined' && typeof response.data !== 'undefined' && typeof response.data.feedback !== 'undefined' ) {
								$( file.previewElement ).find( '.dz-error-message span' ).text( response.data.feedback );
							} else if( file.status == 'error' && ( file.xhr && file.xhr.status == 0) ) { // update server error text to user friendly
								$( file.previewElement ).find( '.dz-error-message span' ).text( BP_Nouveau.media.connection_lost_error );
							}
						} else {
							if ( !jQuery( '.media-error-popup' ).length ) {
								$( 'body' ).append( '<div id="bp-media-create-folder" style="display: block;" class="open-popup media-error-popup"><transition name="modal"><div class="modal-mask bb-white bbm-model-wrap"><div class="modal-wrapper"><div id="boss-media-create-album-popup" class="modal-container has-folderlocationUI"><header class="bb-model-header"><h4>' + BP_Nouveau.media.invalid_media_type + '</h4><a class="bb-model-close-button errorPopup" href="#"><span class="dashicons dashicons-no-alt"></span></a></header><div class="bb-field-wrap"><p>' + response + '</p></div></div></div></div></transition></div>' );
							}
							this.removeFile( file );
						}
					}
				);

				self.dropzone_obj.on(
					'queuecomplete',
					function () {
						$( '#bp-media-uploader-modal-title' ).text( BP_Nouveau.media.i18n_strings.upload );
					}
				);

				self.dropzone_obj.on(
					'processing',
					function () {
						$( '#bp-media-uploader-modal-title' ).text( BP_Nouveau.media.i18n_strings.uploading + '...' );
					}
				);

				self.dropzone_obj.on(
					'success',
					function ( file, response ) {
						if ( response.data.id ) {
							file.id = response.id;
							response.data.uuid = file.upload.uuid;
							response.data.menu_order = self.dropzone_media.length;
							response.data.album_id = self.album_id;
							response.data.group_id = self.group_id;
							response.data.saved = false;
							self.dropzone_media.push( response.data );
						} else {
							if ( !jQuery( '.media-error-popup' ).length ) {
								$( 'body' ).append( '<div id="bp-media-create-folder" style="display: block;" class="open-popup media-error-popup"><transition name="modal"><div class="modal-mask bb-white bbm-model-wrap"><div class="modal-wrapper"><div id="boss-media-create-album-popup" class="modal-container has-folderlocationUI"><header class="bb-model-header"><h4>' + BP_Nouveau.media.invalid_media_type + '</h4><a class="bb-model-close-button errorPopup" href="#"><span class="dashicons dashicons-no-alt"></span></a></header><div class="bb-field-wrap"><p>' + response.data.feedback + '</p></div></div></div></div></transition></div>' );
							}
							this.removeFile( file );
						}

						$( '.bb-field-steps-1 #bp-media-photo-next, #bp-media-submit' ).show();
						$( '.modal-container' ).addClass( 'modal-container--alert' );
						$( '.bb-field-steps-1' ).addClass( 'controls-added' );
						$( '#bp-media-add-more' ).show();
						$( '#bp-media-uploader-modal-title' ).text( BP_Nouveau.media.i18n_strings.uploading + '...' );
						$( '#bp-media-uploader-modal-status-text' ).text( wp.i18n.sprintf( BP_Nouveau.media.i18n_strings.upload_status, self.dropzone_media.length, self.dropzone_obj.getAcceptedFiles().length ) ).show();
					}
				);

				self.dropzone_obj.on(
					'removedfile',
					function ( file ) {
						if ( self.dropzone_media.length ) {
							for ( var i in self.dropzone_media ) {
								if ( file.upload.uuid == self.dropzone_media[ i ].uuid ) {

									if ( typeof self.dropzone_media[ i ].saved !== 'undefined' && !self.dropzone_media[ i ].saved ) {
										self.removeAttachment( self.dropzone_media[ i ].id );
									}

									self.dropzone_media.splice( i, 1 );
									break;
								}
							}
						}
						if ( !self.dropzone_obj.getAcceptedFiles().length ) {
							$( '#bp-media-uploader-modal-status-text' ).text( '' );
							$( '#bp-media-add-more, #bp-media-photo-next' ).hide();
							$( '.bb-field-steps-1' ).removeClass( 'controls-added' );
							$( '#bp-media-submit' ).hide();
							$( '.modal-container' ).removeClass( 'modal-container--alert' );
						} else {
							$( '#bp-media-uploader-modal-status-text' ).text( wp.i18n.sprintf( BP_Nouveau.media.i18n_strings.upload_status, self.dropzone_media.length, self.dropzone_obj.getAcceptedFiles().length ) ).show();
						}
					}
				);
			}
		},

		openDocumentUploader: function ( event ) {
			var self = this;
			var currentTarget;
			event.preventDefault();

			if ( typeof window.Dropzone !== 'undefined' && $( 'div#media-uploader' ).length ) {

				if ( $( '#bp-media-uploader' ).hasClass( 'bp-media-document-uploader' ) ) {

					if ( !this.currentTargetParent ) {
						this.currentTargetParent = 0;
					}
				}

				if ( $( event.currentTarget ).closest( '#bp-media-single-folder' ).length ) {
					$( '#bb-document-privacy' ).hide();
				}

				$( document ).removeClass( 'open-popup' );
				$( '#bp-media-uploader' ).show();
				$( '#bp-media-uploader' ).addClass( 'open-popup' );

				if ( $( '#bp-media-uploader.bp-media-document-uploader' ).find( '.bb-field-steps.bb-field-steps-2' ).length ) {
					currentTarget = '#bp-media-uploader.bp-media-document-uploader';
					var parentsOpen;
					if ( Number( $( currentTarget ).find( '.bb-folder-selected-id' ).data( 'value' ) ) !== 0 ) {
						parentsOpen = $( currentTarget ).find( '.bb-folder-selected-id' ).data( 'value' );
						$( currentTarget ).find( '#bb-document-privacy' ).prop( 'disabled', true );
					} else {
						parentsOpen = 0;
					}
					if ( '' !== this.moveToIdPopup ) {
						$.ajax(
							{
								url: BP_Nouveau.ajaxurl,
								type: 'GET',
								data: {
									action: 'document_get_folder_view',
									id: this.moveToIdPopup,
									type: this.moveToTypePopup,
								}, success: function ( response ) {
									$( document ).find( '.bp-media-document-uploader .location-folder-list-wrap h4 span.where-to-move-profile-or-group-document' ).html( response.data.first_span_text );
									if ( '' === response.data.html ) {
										$( document ).find( '.bp-media-document-uploader.open-popup .location-folder-list-wrap' ).hide();
										$( document ).find( '.bp-media-document-uploader.open-popup .location-folder-list-wrap-main span.no-folder-exists' ).show();
									} else {
										$( document ).find( '.bp-media-document-uploader.open-popup .location-folder-list-wrap-main span.no-folder-exists' ).hide();
										$( document ).find( '.bp-media-document-uploader.open-popup .location-folder-list-wrap' ).show();
									}

									$( document ).find( '.bp-media-document-uploader .popup-on-fly-create-album .privacy-field-wrap-hide-show' ).show();
									$( document ).find( '.bp-media-document-uploader .open-popup .bb-folder-create-from' ).val( 'profile' );

									$( currentTarget ).find( '.location-folder-list-wrap .location-folder-list' ).remove();
									$( currentTarget ).find( '.location-folder-list-wrap' ).append( response.data.html );
									if ( bp.Nouveau.Media.folderLocationUI ) {
										bp.Nouveau.Media.folderLocationUI( currentTarget, parentsOpen );
									}
									$( currentTarget ).find( 'ul.location-folder-list span#' + parentsOpen ).trigger( 'click' );
									$( currentTarget ).find( '.bb-folder-selected-id' ).val( parentsOpen );
								}
							}
						);
					}
				}

				$( document ).on( 'click', currentTarget + ' .location-folder-list li span', function ( e ) {
					e.preventDefault();
					if ( $( this ).parent().hasClass( 'is_active' ) ) {
						return;
					}
					if ( $( this ).closest( '.location-folder-list-wrap' ).find( '.breadcrumb .item span:last-child' ).data( 'id' ) != 0 ) {
						$( this ).closest( '.location-folder-list-wrap' ).find( '.breadcrumb .item span:last-child' ).remove();
					}
					$( this ).closest( '.location-folder-list-wrap' ).find( '.breadcrumb .item' ).append( '<span class="is-disabled" data-id="' + $( this ).attr( 'id' ) + '">' + $( this ).text() + '</span>' );
					$( this ).addClass( 'selected' ).parent().addClass( 'is_active' ).siblings().removeClass( 'is_active' ).children( 'span' ).removeClass( 'selected' );
					if ( parentsOpen == $( e.currentTarget ).data( 'id' ) ) {
						$( e.currentTarget ).closest( '.bb-field-wrap' ).find( '.bb-model-footer .bp-media-move' ).addClass( 'is-disabled' );
					} else {
						$( e.currentTarget ).closest( '.bb-field-wrap' ).find( '.bb-model-footer .bp-media-move' ).removeClass( 'is-disabled' );
					}
					if ( $( e.currentTarget ).closest( '.bb-field-wrap' ).find( '.bb-model-footer .bp-media-move' ).hasClass( 'is-disabled' ) ) {
						return; //return if parent album is same.
					}
					$( e.currentTarget ).closest( '.bb-field-wrap' ).find( '.bb-folder-selected-id' ).val( $( e.currentTarget ).data( 'id' ) );

					var mediaPrivacy = $( e.currentTarget ).closest( '#bp-media-uploader' ).find( '#bb-document-privacy' );

					if ( Number( $( e.currentTarget ).data( 'id' ) ) !== 0 ) {
						mediaPrivacy.find( 'option' ).removeAttr( 'selected' );
						mediaPrivacy.val( $( e.currentTarget ).parent().data( 'privacy' ) );
						mediaPrivacy.prop( 'disabled', true );
					} else {
						mediaPrivacy.find( 'option' ).removeAttr( 'selected' );
						mediaPrivacy.val( 'public' );
						mediaPrivacy.prop( 'disabled', false );
					}

				} );

				$( document ).on( 'click', currentTarget + ' .breadcrumb .item > span', function ( e ) {

					if ( $( this ).hasClass( 'is-disabled' ) ) {
						return;
					}

					$( e.currentTarget ).closest( '.bb-field-wrap' ).find( '.bb-folder-selected-id' ).val( $( e.currentTarget ).data( 'id' ) );
					$( e.currentTarget ).closest( '.bb-field-wrap' ).find( '.location-folder-list li span' ).removeClass( 'selected' ).parent().removeClass( 'is_active' );
					$( e.currentTarget ).closest( '.bb-field-wrap' ).find( '.location-folder-list li[data-id="' + $( e.currentTarget ).data( 'id' ) + '"]' ).addClass( 'is_active' );
					if ( $( this ).closest( '.location-folder-list-wrap' ).find( '.breadcrumb .item span:last-child' ).hasClass( 'is-disabled' ) ) {
						$( this ).closest( '.location-folder-list-wrap' ).find( '.breadcrumb .item span:last-child' ).remove();
					}

					if ( parentsOpen == $( e.currentTarget ).data( 'id' ) ) {
						$( e.currentTarget ).closest( '.bb-field-wrap' ).find( '.bb-model-footer .bp-media-move' ).addClass( 'is-disabled' );
					} else {
						$( e.currentTarget ).closest( '.bb-field-wrap' ).find( '.bb-model-footer .bp-media-move' ).removeClass( 'is-disabled' );
					}
					var mediaPrivacy = $( e.currentTarget ).closest( '#bp-media-uploader' ).find( '#bb-document-privacy' );
					var selectedFolderPrivacy = $( e.currentTarget ).closest( '#bp-media-uploader' ).find( '.location-folder-list li.is_active' ).data( 'privacy' );
					if ( Number( $( e.currentTarget ).closest( '.bb-field-wrap' ).find( '.bb-folder-selected-id' ).val() ) !== 0 ) {
						mediaPrivacy.find( 'option' ).removeAttr( 'selected' );
						mediaPrivacy.val( selectedFolderPrivacy === undefined ? 'public' : selectedFolderPrivacy );
						mediaPrivacy.prop( 'disabled', true );
					} else {
						mediaPrivacy.find( 'option' ).removeAttr( 'selected' );
						mediaPrivacy.val( 'public' );
						mediaPrivacy.prop( 'disabled', false );
					}
				} );

				var uploaderdocumentTemplate = document.getElementsByClassName('uploader-post-document-template').length ? document.getElementsByClassName('uploader-post-document-template')[0].innerHTML : ''; //Check to avoid error if Node is missing.

				self.options.previewTemplate = uploaderdocumentTemplate;

				self.dropzone_obj = new Dropzone( 'div#media-uploader', self.options );

				self.dropzone_obj.on(
					'sending',
					function ( file, xhr, formData ) {
						formData.append( 'action', 'document_document_upload' );
						formData.append( '_wpnonce', BP_Nouveau.nonces.media );
					}
				);

				self.dropzone_obj.on(
					'uploadprogress',
					function( element ) {var circle = $( element.previewElement ).find('.dz-progress-ring circle')[0];
						var radius = circle.r.baseVal.value;
						var circumference = radius * 2 * Math.PI;

						circle.style.strokeDasharray = circumference + ' ' + circumference;
						circle.style.strokeDashoffset = circumference;
						var offset = circumference - element.upload.progress.toFixed( 0 ) / 100 * circumference;
						circle.style.strokeDashoffset = offset;
					}
				);

				self.dropzone_obj.on(
					'addedfile',
					function () {
						setTimeout(
							function () {
								if ( self.dropzone_obj.getAcceptedFiles().length ) {
									$( '#bp-media-uploader-modal-status-text' ).text( wp.i18n.sprintf( BP_Nouveau.media.i18n_strings.upload_status, self.dropzone_media.length, self.dropzone_obj.getAcceptedFiles().length ) ).show();
								}
							},
							1000
						);
					}
				);

				self.dropzone_obj.on(
					'error',
					function ( file, response ) {
						if ( file.accepted ) {
							if ( typeof response !== 'undefined' && typeof response.data !== 'undefined' && typeof response.data.feedback !== 'undefined' ) {
								$( file.previewElement ).find( '.dz-error-message span' ).text( response.data.feedback );
							} else if( file.status == 'error' && ( file.xhr && file.xhr.status == 0) ) { // update server error text to user friendly
								$( file.previewElement ).find( '.dz-error-message span' ).text( BP_Nouveau.media.connection_lost_error );
							}
						} else {
							if ( !jQuery( '.document-error-popup' ).length ) {
								$( 'body' ).append( '<div id="bp-media-create-album" style="display: block;" class="open-popup document-error-popup"><transition name="modal"><div class="modal-mask bb-white bbm-model-wrap"><div class="modal-wrapper"><div id="boss-media-create-album-popup" class="modal-container has-folderlocationUI"><header class="bb-model-header"><h4>' + BP_Nouveau.media.invalid_file_type + '</h4><a class="bb-model-close-button errorPopup" href="#"><span class="dashicons dashicons-no-alt"></span></a></header><div class="bb-field-wrap"><p>' + response + '</p></div></div></div></div></transition></div>' );
							}
							this.removeFile( file );
						}
					}
				);

				self.dropzone_obj.on(
					'accept',
					function ( file, done ) {
						if ( file.size == 0 ) {
							done( BP_Nouveau.media.empty_document_type );
						} else {
							done();
						}
					}
				);

				self.dropzone_obj.on(
					'queuecomplete',
					function () {
						$( '#bp-media-uploader-modal-title' ).text( BP_Nouveau.media.i18n_strings.upload );
					}
				);

				self.dropzone_obj.on(
					'processing',
					function () {
						$( '#bp-media-uploader-modal-title' ).text( BP_Nouveau.media.i18n_strings.uploading + '...' );
					}
				);

				self.dropzone_obj.on(
					'success',
					function ( file, response ) {
						if ( response.data.id ) {
							file.id = response.id;
							response.data.uuid = file.upload.uuid;
							response.data.menu_order = self.dropzone_media.length;
							response.data.folder_id = self.current_folder;
							response.data.group_id = self.current_group_id;
							response.data.saved = false;
							self.dropzone_media.push( response.data );

							var filename = file.upload.filename;
							var fileExtension = filename.substr( ( filename.lastIndexOf( '.' ) + 1 ) );
							var file_icon = ( !_.isUndefined( response.data.svg_icon ) ? response.data.svg_icon : '' );
							var icon_class = !_.isEmpty( file_icon ) ? file_icon : 'bb-icon-file-' + fileExtension;

							if ( $( file.previewElement ).find( '.dz-details .dz-icon .bb-icon-file' ).length ) {
								$( file.previewElement ).find( '.dz-details .dz-icon .bb-icon-file' ).removeClass( 'bb-icon-file' ).addClass( icon_class );
							}
						} else {
							var node, _i, _len, _ref, _results;
							var message = response.data.feedback;
							file.previewElement.classList.add( 'dz-error' );
							_ref = file.previewElement.querySelectorAll( '[data-dz-errormessage]' );
							_results = [];
							for ( _i = 0, _len = _ref.length; _i < _len; _i++ ) {
								node = _ref[ _i ];
								_results.push( node.textContent = message );
							}
							return _results;
						}

						$( '.bb-field-steps-1 #bp-media-document-next, #bp-media-document-submit' ).show();
						$( '.modal-container' ).addClass( 'modal-container--alert' );
						$( '.bb-field-steps-1' ).addClass( 'controls-added' );
						$( '#bp-media-uploader-modal-title' ).text( BP_Nouveau.media.i18n_strings.uploading + '...' );
						$( '#bp-media-uploader-modal-status-text' ).text( wp.i18n.sprintf( BP_Nouveau.media.i18n_strings.upload_status, self.dropzone_media.length, self.dropzone_obj.getAcceptedFiles().length ) ).show();
					}
				);

				self.dropzone_obj.on(
					'removedfile',
					function ( file ) {
						if ( self.dropzone_media.length ) {
							for ( var i in self.dropzone_media ) {
								if ( file.upload.uuid == self.dropzone_media[ i ].uuid ) {

									if ( typeof self.dropzone_media[ i ].saved !== 'undefined' && !self.dropzone_media[ i ].saved ) {
										self.removeAttachment( self.dropzone_media[ i ].id );
									}

									self.dropzone_media.splice( i, 1 );
									break;
								}
							}
						}
						if ( !self.dropzone_obj.getAcceptedFiles().length ) {
							$( '#bp-media-uploader-modal-status-text' ).text( '' );
							$( '#bp-media-document-submit' ).hide();
							$( '.modal-container' ).removeClass( 'modal-container--alert' );
						} else {
							$( '#bp-media-uploader-modal-status-text' ).text( wp.i18n.sprintf( BP_Nouveau.media.i18n_strings.upload_status, self.dropzone_media.length, self.dropzone_obj.getAcceptedFiles().length ) ).show();
						}
					}
				);
			}
		},

		/**
		 * [openMediaMove description]
		 *
		 * @param  {[type]} event [description]
		 * @return {[type]}       [description]
		 */
		openMediaMove: function ( event ) {
			event.preventDefault();

			var media_move_popup, media_parent_id, media_id, currentTarget;

			this.moveToIdPopup = $( event.currentTarget ).attr( 'id' );
			this.moveToTypePopup = $( event.currentTarget ).attr( 'data-type' );

			if ( $( event.currentTarget ).closest( '.activity-inner' ).length > 0 ) {
				media_move_popup = $( event.currentTarget ).closest( '.activity-inner' );
			} else if ( $( event.currentTarget ).closest( '#media-stream.media' ).length > 0 ) {
				media_move_popup = $( event.currentTarget ).closest( '#media-stream.media' );
			} else if ( $( event.currentTarget ).closest( '.comment-item' ).length > 0 ) {
				media_move_popup = $( event.currentTarget ).closest( '.comment-item' );
			}

			$( media_move_popup ).find( '.bp-media-move-file' ).addClass( 'open' ).show();
			media_id = $( event.currentTarget ).closest( '.media-action-wrap' ).siblings( 'a' ).data( 'id' );
			media_parent_id = $( event.currentTarget ).closest( '.media-action-wrap' ).siblings( 'a' ).data( 'album-id' );

			media_move_popup.find( '.bp-media-move' ).attr( 'id', media_id );
			media_move_popup.find( '.bb-model-footer .bp-media-move' ).addClass( 'is-disabled' );

			// For Activity Feed.
			if ( $( event.currentTarget ).closest( '.conflict-activity-ul-li-comment' ).closest( 'li.comment-item' ).length ) {
				currentTarget = '#' + $( event.currentTarget ).closest( '.conflict-activity-ul-li-comment' ).closest( 'li' ).attr( 'id' ) + '.comment-item .bp-media-move-file';
			} else {
				currentTarget = '#' + $( event.currentTarget ).closest( 'li.activity-item' ).attr( 'id' ) + ' > .activity-content .bp-media-move-file';
			}

			$( currentTarget ).find( '.bp-document-move' ).attr( 'id', $( event.currentTarget ).closest( '.document-activity' ).attr( 'data-id' ) );

			// Change if this is not from Activity Page.
			if ( $( event.currentTarget ).closest( '.media-list' ).length > 0 ) {
				currentTarget = '.bp-media-move-file';
			}

			if ( 'group' === this.moveToTypePopup ) {
				$( document ).find( '.location-album-list-wrap h4' ).show();
			} else {
				$( document ).find( '.location-album-list-wrap h4' ).hide();
			}

			$( currentTarget ).addClass( 'open-popup' );

			$( currentTarget ).find( '.location-album-list-wrap .location-album-list' ).remove();
			$( currentTarget ).find( '.location-album-list-wrap' ).append( '<ul class="location-album-list is-loading"><li><i class="bb-icon-l bb-icon-spinner animate-spin"></i></li></ul>' );

			var parentsOpen = media_parent_id;
			var getFrom = this.moveToTypePopup;
			if ( '' !== this.moveToIdPopup ) {
				$.ajax(
					{
						url: BP_Nouveau.ajaxurl,
						type: 'post',
						data: {
							action: 'media_get_album_view',
							id: this.moveToIdPopup,
							type: this.moveToTypePopup,
						}, success: function ( response ) {
							$( document ).find( '.location-album-list-wrap h4 span.where-to-move-profile-or-group-media' ).html( response.data.first_span_text );
							if ( '' === response.data.html ) {
								$( document ).find( '.open-popup .location-album-list-wrap' ).hide();
								$( document ).find( '.open-popup .location-album-list-wrap-main span.no-album-exists' ).show();
							} else {
								$( document ).find( '.open-popup .location-album-list-wrap-main span.no-album-exists' ).hide();
								$( document ).find( '.open-popup .location-album-list-wrap' ).show();
							}
							if ( 'group' === getFrom ) {
								$( document ).find( '.popup-on-fly-create-album .privacy-field-wrap-hide-show' ).hide();
								$( document ).find( '.open-popup .bb-album-create-from' ).val( 'group' );
							} else {
								$( document ).find( '.popup-on-fly-create-album .privacy-field-wrap-hide-show' ).show();
								$( document ).find( '.open-popup .bb-album-create-from' ).val( 'profile' );
							}

							if ( false === response.data.create_album ) {
								$( currentTarget + '.open-popup'  ).find( '.bp-media-open-create-popup-folder' ).removeClass( 'create-album' );
								$( currentTarget + '.open-popup' ).find( '.bp-media-open-create-popup-folder' ).hide();
							} else {
								$( currentTarget + '.open-popup' ).find( '.bp-media-open-create-popup-folder' ).addClass( 'create-album' );
								$( currentTarget + '.open-popup' ).find( '.bp-media-open-create-popup-folder' ).show();
							}

							$( currentTarget ).find( '.location-album-list-wrap .location-album-list' ).remove();
							$( currentTarget ).find( '.location-album-list-wrap' ).append( response.data.html );
							$( currentTarget ).find( 'ul.location-album-list span#' + parentsOpen ).trigger( 'click' );
						}
					}
				);
			}

			$( document ).on( 'click', currentTarget + ' .location-album-list li span', function ( e ) {
				e.preventDefault();
				if ( $( this ).parent().hasClass( 'is_active' ) ) {
					return;
				}

				if ( $( this ).closest( '.location-album-list-wrap' ).find( '.breadcrumb .item span:last-child' ).data( 'id' ) != 0 ) {
					$( this ).closest( '.location-album-list-wrap' ).find( '.breadcrumb .item span:last-child' ).remove();
				}

				$( this ).closest( '.location-album-list-wrap' ).find( '.breadcrumb .item' ).append( '<span class="is-disabled" data-id="' + $( this ).attr( 'id' ) + '">' + $( this ).text() + '</span>' );

				$(this).addClass('selected').parent().addClass('is_active').siblings().removeClass('is_active').children('span').removeClass('selected');
				var parentsOpen = $(document).find('a.bb-open-media-theatre[data-id="' + media_move_popup.find( '.bp-media-move' ).attr( 'id' ) + '"]').data('album-id');
				if ( Number(parentsOpen) == Number( $(e.currentTarget).data('id') ) ) {
					$(e.currentTarget).closest('.bp-media-move-file').find('.bb-model-footer .bp-media-move').addClass('is-disabled');
				} else {
					$( e.currentTarget ).closest( '.bp-media-move-file' ).find( '.bb-model-footer .bp-media-move' ).removeClass( 'is-disabled' );
				}
				if ( $( e.currentTarget ).closest( '.bp-media-move-file' ).find( '.bb-model-footer .bp-media-move' ).hasClass( 'is-disabled' ) ) {
					return; //return if parent album is same.
				}
				$( e.currentTarget ).closest( '.bp-media-move-file' ).find( '.bb-album-selected-id' ).val( $( e.currentTarget ).data( 'id' ) );
			} );

			$( document ).on( 'click', currentTarget + ' .breadcrumb .item > span', function ( e ) {

				if ( $( this ).hasClass( 'is-disabled' ) ) {
					return;
				}

				$( e.currentTarget ).closest( '.bp-media-move-file' ).find( '.bb-album-selected-id' ).val( 0 );
				$( e.currentTarget ).closest( '.bp-media-move-file' ).find( '.location-album-list li span' ).removeClass( 'selected' ).parent().removeClass( 'is_active' );

				if ( $( this ).closest( '.location-album-list-wrap' ).find( '.breadcrumb .item span:last-child' ).hasClass( 'is-disabled' ) ) {
					$( this ).closest( '.location-album-list-wrap' ).find( '.breadcrumb .item span:last-child' ).remove();
				}

				if ( parentsOpen == $( e.currentTarget ).data( 'id' ) ) {
					$( e.currentTarget ).closest( '.bp-media-move-file' ).find( '.bb-model-footer .bp-media-move' ).addClass( 'is-disabled' );
				} else {
					$( e.currentTarget ).closest( '.bp-media-move-file' ).find( '.bb-model-footer .bp-media-move' ).removeClass( 'is-disabled' );
				}

			} );

		},

		/**
		 * [openDocumentMove description]
		 *
		 * @param  {[type]} event [description]
		 * @return {[type]}       [description]
		 */
		openDocumentMove: function ( event ) {
			event.preventDefault();

			var currentTarget;
			/* jshint ignore:start */
			var currentTargetName = $( event.currentTarget ).closest( '.bb-activity-media-elem' ).find( '.document-title' ).text();
			/* jshint ignore:end */
			this.moveToIdPopup = $( event.currentTarget ).attr( 'id' );
			this.moveToTypePopup = $( event.currentTarget ).attr( 'data-type' );
			var action = $( event.currentTarget ).attr( 'data-action' );

			// For Activity Feed.
			if ( $( event.currentTarget ).closest( '.conflict-activity-ul-li-comment' ).closest( 'li.comment-item' ).length ) {
				currentTarget = '#' + $( event.currentTarget ).closest( '.conflict-activity-ul-li-comment' ).closest( 'li' ).attr( 'id' ) + '.comment-item .bp-media-move-file';
			} else {
				currentTarget = '#' + $( event.currentTarget ).closest( 'li.activity-item' ).attr( 'id' ) + ' > .activity-content .bp-media-move-file';
			}

			$( currentTarget ).find( '.bp-document-move' ).attr( 'id', $( event.currentTarget ).closest( '.document-activity' ).attr( 'data-id' ) );
			this.currentTargetParent = $( event.currentTarget ).closest( '.bb-activity-media-elem' ).attr( 'data-parent-id' );

			// Change if this is not from Activity Page.
			if ( $( event.currentTarget ).closest( '.media-folder_items' ).length > 0 ) {
				/* jshint ignore:start */
				currentTargetName = $( event.currentTarget ).closest( '.media-folder_items' ).find( '.media-folder_name' ).text();
				this.currentTargetParent = $( event.currentTarget ).closest( '.media-folder_items' ).attr( 'data-parent-id' );
				/* jshint ignore:end */
				if ( $( event.currentTarget ).hasClass( 'ac-document-move' ) ) { // Check if target is file or folder.
					currentTarget = '.bp-media-move-file';
					$( currentTarget ).find( '.bp-document-move' ).attr( 'id', $( event.currentTarget ).closest( '.media-folder_items' ).attr( 'data-id' ) );
				} else {
					currentTarget = '.bp-media-move-folder';
					$( currentTarget ).find( '.bp-folder-move' ).attr( 'id', $( event.currentTarget ).closest( '.media-folder_items' ).attr( 'data-id' ) );

				}
			}

			$( currentTarget ).find( '.location-folder-list-wrap .location-folder-list' ).remove();
			$( currentTarget ).find( '.location-folder-list-wrap' ).append( '<ul class="location-folder-list is-loading"><li><i class="bb-icon-l bb-icon-spinner animate-spin"></i></li></ul>' );
			if ( 'document' === action ) {
				$( currentTarget ).find( '.bb-model-header h4 .target_name' ).text( BP_Nouveau.media.move_to_file );
			} else {
				$( currentTarget ).find( '.bb-model-header h4 .target_name' ).text( BP_Nouveau.media.move_to_folder );
			}
			$( currentTarget ).show();
			$( currentTarget ).addClass( 'open-popup' );

			if ( 'group' === this.moveToTypePopup ) {
				$( document ).find( '.location-folder-list-wrap h4' ).show();
				$( currentTarget ).addClass( 'move-folder-popup-group' );
			} else {
				$( document ).find( '.location-folder-list-wrap h4' ).hide();
				$( '.move-folder-popup-group' ).removeClass( 'move-folder-popup-group' );
			}

			var parentsOpen = this.currentTargetParent;
			var getFrom = this.moveToTypePopup;

			if ( '' !== this.moveToIdPopup ) {
				$.ajax(
					{
						url: BP_Nouveau.ajaxurl,
						type: 'GET',
						data: {
							action: 'document_get_folder_view',
							id: this.moveToIdPopup,
							type: this.moveToTypePopup,
						}, success: function ( response ) {
							$( document ).find( '.location-folder-list-wrap h4 span.where-to-move-profile-or-group-document' ).html( response.data.first_span_text );
							if ( '' === response.data.html ) {
								$( document ).find( '.open-popup .location-folder-list-wrap' ).hide();
								$( document ).find( '.open-popup .location-folder-list-wrap-main span.no-folder-exists' ).show();
							} else {
								$( document ).find( '.open-popup .location-folder-list-wrap-main span.no-folder-exists' ).hide();
								$( document ).find( '.open-popup .location-folder-list-wrap' ).show();
							}
							if ( 'group' === getFrom ) {
								$( document ).find( '.popup-on-fly-create-folder .privacy-field-wrap-hide-show' ).hide();
								$( document ).find( '.open-popup .bb-folder-create-from' ).val( 'group' );
							} else {
								$( document ).find( '.popup-on-fly-create-folder .privacy-field-wrap-hide-show' ).show();
								$( document ).find( '.open-popup .bb-folder-create-from' ).val( 'profile' );
							}
							$( currentTarget ).find( '.location-folder-list-wrap .location-folder-list' ).remove();
							$( currentTarget ).find( '.location-folder-list-wrap' ).append( response.data.html );
							if ( bp.Nouveau.Media.folderLocationUI ) {
								bp.Nouveau.Media.folderLocationUI( currentTarget, parentsOpen );
								$( currentTarget ).find( 'ul.location-folder-list span#' + parentsOpen ).trigger( 'click' );
							}
						}
					}
				);
			}
		},

		/**
		 * [closeDocumentMove description]
		 *
		 * @param  {[type]} event [description]
		 * @return {[type]}       [description]
		 */
		closeMediaMove: function ( event ) {
			event.preventDefault();
			if ( $( event.currentTarget ).closest( '.bp-media-move-file' ).find( '.location-album-list-wrap .breadcrumb .item span:last-child' ).data( 'id' ) != 0 ) {
				$( event.currentTarget ).closest( '.bp-media-move-file' ).find( '.location-album-list-wrap .breadcrumb .item span:last-child' ).remove();
			}
			$( event.currentTarget ).closest( '.bp-media-move-file' ).hide();

		},

		/**
		 * [closeDocumentMove description]
		 *
		 * @param  {[type]} event [description]
		 * @return {[type]}       [description]
		 */
		closeDocumentMove: function ( event ) {
			event.preventDefault();
			var closest_parent = jQuery( event.currentTarget ).closest( '.has-folderlocationUI' );
			if ( $( event.currentTarget ).hasClass( 'ac-document-close-button' ) ) {
				$( event.currentTarget ).closest( '.bp-media-move-file' ).hide().find( '.bp-document-move' ).attr( 'id', '' );

			} else {
				$( event.currentTarget ).closest( '.bp-media-move-folder' ).hide().find( '.bp-folder-move' ).attr( 'id', '' );
			}

			closest_parent.find( '.bp-document-move.loading' ).removeClass( 'loading' );

			this.clearFolderLocationUI( event );

		},

		/**
		 * [renameDocument description]
		 *
		 * @param  {[type]} event [description]
		 * @return {[type]}       [description]
		 */
		renameDocument: function ( event ) {

			var current_name = $( event.currentTarget ).closest( '.media-folder_items' ).find( '.media-folder_name' );
			var current_name_text = current_name.children( 'span' ).text();

			current_name.hide().siblings( '.media-folder_name_edit_wrap' ).show().children( '.media-folder_name_edit' ).val( current_name_text ).focus().select();

			event.preventDefault();

		},

		/**
		 * [editPrivacyDocument description]
		 *
		 * @param  {[type]} event [description]
		 * @return {[type]}       [description]
		 */
		editPrivacyDocument: function ( event ) {
			event.preventDefault();

			// Reset all privacy dropdown.
			$( event.currentTarget ).closest( '#media-folder-document-data-table' ).find( '.media-folder_visibility .media-folder_details__bottom span' ).show().siblings( 'select' ).addClass( 'hide' );

			var current_privacy = $( event.currentTarget ).closest( '.media-folder_items' ).find( '.media-folder_visibility' );

			current_privacy.find( '.media-folder_details__bottom span' ).hide().siblings( 'select' ).removeClass( 'hide' );
			current_privacy.find( '.media-folder_details__bottom span' ).hide().siblings( 'select' ).val( $( event.currentTarget ).attr( 'data-privacy' ) );

			current_privacy.find( '.media-folder_details__bottom #bb-folder-privacy' ).attr( 'data-privacy', $( event.currentTarget ).attr( 'data-privacy' ) );

			this.privacySelectorSelect = current_privacy.find( '.media-folder_details__bottom span' ).hide().siblings( 'select' );
			this.privacySelectorSpan = current_privacy.find( '.media-folder_details__bottom span' );

		},

		/**
		 * [editPrivacyDocumentSubmit description]
		 *
		 * @param  {[type]} event [description]
		 * @return {[type]}       [description]
		 */
		editPrivacyDocumentSubmit: function ( event ) {

			var current_privacy_select = $( event.currentTarget );

			if ( current_privacy_select.attr( 'data-mouseup' ) == 'true' ) {

				current_privacy_select.attr( 'data-mouseup', 'false' );

				// Make ajax call and onSuccess add this.
				current_privacy_select.addClass( 'hide' ).siblings( 'span' ).show().text( current_privacy_select.find( 'option:selected' ).text() );

			} else {

				current_privacy_select.attr( 'data-mouseup', 'true' );

			}

		},

		/**
		 * [renameDocumentSubmit description]
		 *
		 * @param  {[type]} event [description]
		 * @return {[type]}       [description]
		 */
		renameDocumentSubmit: function ( event ) {

			var document_edit = $( event.currentTarget ).closest( '.media-folder_items' ).find( '.media-folder_name_edit' );
			var document_name = $( event.currentTarget ).closest( '.media-folder_items' ).find( '.media-folder_name > span' );
			var document_name_update_data = $( event.currentTarget ).closest( '.media-folder_items' ).find( '.media-folder_name' );
			var document_id = $( event.currentTarget ).closest( '.media-folder_items' ).find( '.media-folder_name > i.media-document-id' ).attr( 'data-item-id' );
			var attachment_document_id = $( event.currentTarget ).closest( '.media-folder_items' ).find( '.media-folder_name > i.media-document-attachment-id' ).attr( 'data-item-id' );
			var documentType = $( event.currentTarget ).closest( '.media-folder_items' ).find( '.media-folder_name > i.media-document-type' ).attr( 'data-item-id' );
			var document_name_val = document_edit.val().trim(); // trim to remove whitespace around name.
			var pattern = '';

			if ( $( event.currentTarget ).closest( '.ac-document-list' ).length ) {
				pattern = /[?\[\]=<>:;,'"&$#*()|~`!{}%+ \/]+/g; // regex to find not supported characters. ?[]/=<>:;,'"&$#*()|~`!{}%+ {space}
			} else if ( $( event.currentTarget ).closest( '.ac-folder-list' ).length ) {
				pattern = /[\\/?%*:|"<>]+/g; // regex to find not supported characters - \ / ? % * : | " < >
			}

			var matches = pattern.exec( document_name_val );
			var matchStatus = Boolean( matches );

			if ( !matchStatus ) { // If any not supported character found add error class.
				document_edit.removeClass( 'error' );
			} else {
				document_edit.addClass( 'error' );
			}

			if ( $( event.currentTarget ).closest( '.ac-document-list' ).length ) {

				if ( document_name_val.indexOf( '\\\\' ) != -1 || matchStatus ) { //Also check if filename has "\\"
					document_edit.addClass( 'error' );
				} else {
					document_edit.removeClass( 'error' );
				}

			}

			if ( $( event.currentTarget ).hasClass( 'name_edit_cancel' ) || event.keyCode == 27 ) {

				document_edit.removeClass( 'error' );
				document_edit.parent().hide().siblings( '.media-folder_name' ).show();

			}

			if ( $( event.currentTarget ).hasClass( 'name_edit_save' ) || event.keyCode == 13 ) {

				if ( matchStatus ) {
					return; // prevent user to add not supported characters.
				}

				document_edit.parent().addClass( 'submitting' ).append( '<i class="animate-spin bb-icon-l bb-icon-spinner"></i>' );

				// Make ajax call to save new file name here.
				// use variable 'document_name_val' as a new name while making an ajax call.
				$.ajax(
					{
						url: BP_Nouveau.ajaxurl,
						type: 'post',
						data: {
							action: 'document_update_file_name',
							document_id: document_id,
							attachment_document_id: attachment_document_id,
							document_type: documentType,
							name: document_name_val,
							_wpnonce: BP_Nouveau.nonces.media
						},
						success: function ( response ) {
							if ( response.success ) {
								if ( 'undefined' !== typeof response.data.document && 0 < $( response.data.document ).length ) {
									$( event.currentTarget ).closest( '.media-folder_items' ).html( $( response.data.document ).html() );
								} else {
									document_name_update_data.attr( 'data-document-title', response.data.response.title + '.' + document_name_update_data.data( 'extension' ) );
									document_name.html( response.data.response.title );
									document_edit.removeClass( 'submitting' );
									document_edit.parent().find( '.animate-spin' ).remove();
									document_edit.parent().hide().siblings( '.media-folder_name' ).show();
								}
							} else {
								document_edit.removeClass( 'submitting' );
								document_edit.parent().find( '.animate-spin' ).remove();
								document_edit.parent().hide().siblings( '.media-folder_name' ).show();
								/* jshint ignore:start */
								alert( response.data.feedback.replace( '&#039;', '\'' ) );
								/* jshint ignore:end */
							}
						},
					}
				);

			}

			event.preventDefault();

		},

		removeAttachment: function ( id ) {
			var data = {
				'action': 'media_delete_attachment',
				'_wpnonce': BP_Nouveau.nonces.media,
				'id': id
			};

			$.ajax(
				{
					type: 'POST',
					url: BP_Nouveau.ajaxurl,
					data: data
				}
			);
		},

		beforeunloadWindow: function() {
			if( $('body.messages').length > 0 ) {
				$.each( Dropzone.instances, function( index, value ) {
					value.removeAllFiles( true );
				});
			}
		},

		changeUploadModalTab: function ( event ) {
			event.preventDefault();

			var content_tab = $( event.currentTarget ).data( 'content' );
			var current_popup = $( event.currentTarget ).closest( '#bp-media-uploader' );
			$( '.bp-media-upload-tab-content' ).hide();
			$( '#' + content_tab ).show();
			this.current_tab = content_tab;
			current_popup.find( '.bp-media-upload-tab' ).removeClass( 'selected' );
			$( event.currentTarget ).addClass( 'selected' );
			this.toggleSubmitMediaButton();
			current_popup.find( '.bb-field-steps-2' ).slideUp( 200 );
			current_popup.find( '#bb-media-privacy' ).hide();
			current_popup.find( '.bp-media-open-create-popup-folder' ).hide();
			if ( content_tab === 'bp-dropzone-content' ) {
				current_popup.find( '.bb-field-steps-1' ).show();
				current_popup.find( '#bb-media-privacy' ).show();
				current_popup.find( '.bp-media-open-create-popup-folder, .bp-document-open-create-popup-folder, #bb-media-privacy' ).hide();
			}
			if( content_tab === 'bp-existing-media-content' ) {
				current_popup.find( '.bb-field-uploader-actions' ).hide();
			}
			jQuery( window ).scroll();
		},

		openCreateAlbumModal: function ( event ) {
			event.preventDefault();

			this.openUploader( event );
			$( '#bp-media-create-album' ).show();
			if( $( 'body' ).hasClass( 'directory' ) ) {
				$( '#bp-media-uploader' ).hide();
			}
		},

		openCreateFolderModal: function ( event ) {
			event.preventDefault();
			$( '#bp-media-create-folder' ).show();
			$( '#bp-media-create-folder' ).addClass( 'open-popup' );
			$( document ).find( '.open-popup #boss-media-create-album-popup #bb-album-title' ).show();
			$( document ).find( '.open-popup #boss-media-create-album-popup #bb-album-title' ).removeClass( 'error' );
		},

		openCreateFolderChildModal: function ( event ) {
			event.preventDefault();
			$( '#bp-media-create-child-folder' ).show();
			$( '#bp-media-create-child-folder' ).addClass( 'open-popup' );
			$( document ).find( '.open-popup #boss-media-create-album-popup #bb-album-child-title' ).show();
			$( document ).find( '.open-popup #boss-media-create-album-popup #bb-album-child-title' ).removeClass( 'error' );
		},

		openEditFolderChildModal: function ( event ) {
			event.preventDefault();

			var userId = BP_Nouveau.media.current_user_id;
			var groupId = BP_Nouveau.media.current_group_id;
			var type = BP_Nouveau.media.current_type;
			var id = 0;
			if ( 'group' === type ) {
				id = groupId;
				$( document ).find( '.location-folder-list-wrap h4' ).show();
			} else {
				id = userId;
				$( document ).find( '.location-folder-list-wrap h4' ).hide();
			}

			$.ajax(
				{
					url: BP_Nouveau.ajaxurl,
					type: 'GET',
					data: {
						action: 'document_get_folder_view',
						id: id,
						type: type,
					}, success: function ( response ) {
						$( document ).find( '.location-folder-list-wrap h4 span.where-to-move-profile-or-group-document' ).html( response.data.first_span_text );
						$( '.location-folder-list-wrap .location-folder-list' ).remove();
						$( '.location-folder-list-wrap' ).append( response.data.html );
						if ( bp.Nouveau.Media.folderLocationUI ) {
							bp.Nouveau.Media.folderLocationUI( '#bp-media-edit-child-folder', BP_Nouveau.media.current_folder );
							$( event.currentTarget ).closest( '#bp-media-single-folder' ).find( 'ul.location-folder-list span#' + BP_Nouveau.media.current_folder ).trigger( 'click' );
						}
						if ( 'group' === type ) {
							$( document ).find( '.popup-on-fly-create-folder .privacy-field-wrap-hide-show' ).hide();
							$( document ).find( '.open-popup .bb-folder-create-from' ).val( 'group' );
						} else {
							$( document ).find( '.popup-on-fly-create-folder .privacy-field-wrap-hide-show' ).show();
							$( document ).find( '.open-popup .bb-folder-create-from' ).val( 'profile' );
						}
					}
				}
			);

			// this.openDocumentFolderChildUploader(event);.
			$( '#bp-media-edit-child-folder' ).show();
		},

		folderLocationUI: function ( targetPopup, currentTargetParent ) {

			if ( $( targetPopup ).find( '.bb-folder-destination' ).length > 0 ) {

				if ( !$( targetPopup ).find( '.location-folder-list-wrap' ).hasClass( 'is_loaded' ) ) {

					$( document ).on(
						'click',
						targetPopup + ' .bb-folder-destination',
						function () {
							$( this ).parent().find( '.location-folder-list-wrap' ).slideToggle();
						}
					);

					$( targetPopup ).find( '.location-folder-list-wrap' ).addClass( 'is_loaded' );

					$( document ).on(
						'click',
						targetPopup + ' .location-folder-list span',
						function () {

							this.currentTargetParent = $( this ).attr( 'id' );

							var $this = $( this ),
								$bc = $( '<div class="item"></div>' );

							$this.parents( 'li' ).each( function ( n, li ) {
								var $a = $( li ).children( 'span' ).clone();
								$bc.prepend( '', $a );
							} );
							$( targetPopup ).find( '.breadcrumbs-append-ul-li .breadcrumb' ).html( $bc.prepend( '<span data-id="0">' + BP_Nouveau.media.target_text + '</span>' ) );

							if ( !$( targetPopup ).find( '.breadcrumbs-append-ul-li .breadcrumb .item span.hidden' ).length ) {
								$( targetPopup ).find( '.breadcrumbs-append-ul-li .breadcrumb' ).find( '.item' ).append( '<span class="hidden"></span>' );
							}

							$( targetPopup ).find( '.breadcrumbs-append-ul-li .breadcrumb .item span:not(.hidden)' ).each( function ( i ) {

								if ( i > 0 ) {
									if ( $( targetPopup ).find( '.breadcrumbs-append-ul-li .breadcrumb .item' ).width() > $( targetPopup ).find( '.breadcrumbs-append-ul-li .breadcrumb' ).width() ) {

										$( targetPopup ).find( '.breadcrumbs-append-ul-li .breadcrumb .item span.hidden' ).append( $( targetPopup ).find( '.breadcrumbs-append-ul-li .breadcrumb .item span' ).eq( 2 ) );

										if ( !$( targetPopup ).find( '.breadcrumbs-append-ul-li .breadcrumb .item .more_options' ).length ) {
											$( '<span class="more_options">...</span>' ).insertAfter( $( targetPopup ).find( '.breadcrumbs-append-ul-li .breadcrumb .item span' ).eq( 0 ) );
										}

									}
								}
							} );

							if ( $( this ).hasClass( 'selected' ) && !$( this ).hasClass( 'disabled' ) ) {
								$( this ).closest( '.location-folder-list-wrap-main' ).find( '.bb-folder-destination' ).val( '' );
								$( this ).closest( '.location-folder-list-wrap-main' ).find( '.bb-folder-selected-id' ).val( '0' );

								if ( $( targetPopup ).find( '.location-folder-list li.is_active' ).length ) {
									$( targetPopup ).find( '.bb-folder-selected-id' ).val( $( targetPopup ).find( '.location-folder-list li.is_active' ).attr( 'data-id' ) );
								} else {
									$( targetPopup ).find( '.bb-folder-selected-id' ).val( '0' );
								}

							} else {
								$( this ).closest( '.location-folder-list-wrap-main' ).find( '.location-folder-list li span' ).removeClass( 'selected' );
								$( this ).addClass( 'selected' );
								$( this ).closest( '.location-folder-list-wrap-main' ).find( '.bb-folder-destination' ).val( $( this ).text() );
								$( this ).closest( '.location-folder-list-wrap-main' ).find( '.bb-folder-selected-id' ).val( $( this ).parent().attr( 'data-id' ) );
							}

							$( this ).closest( '.location-folder-list-wrap' ).find( '.location-folder-title' ).text( $( this ).siblings( 'span' ).text() ).siblings( '.location-folder-back' ).css( 'display', 'inline-block' );
							$( this ).siblings( 'ul' ).show().siblings( 'span, i' ).hide().parent().siblings().hide();
							$( this ).siblings( 'ul' ).children( 'li' ).show().children( 'span,i' ).show();
							$( this ).closest( '.is_active' ).removeClass( 'is_active' );
							$( targetPopup ).find( 'li.is_active' ).removeClass( 'is_active' );
							$( this ).parent().addClass( 'is_active' );

							$( targetPopup ).find( '.bb-folder-selected-id' ).val( $( targetPopup ).find( '.location-folder-list li.is_active' ).attr( 'data-id' ) );
							$( targetPopup ).find( '.breadcrumbs-append-ul-li .breadcrumb .item span' ).each( function () {
								$( this ).show();
							} );

							if ( currentTargetParent === $( targetPopup ).find( '.breadcrumbs-append-ul-li .item > span:last-child' ).attr( 'data-id' ) && ( $( targetPopup ).hasClass( 'bp-media-move-file' ) || $( targetPopup ).hasClass( 'bp-media-move-folder' ) ) ) {
								$( targetPopup ).find( '.bp-document-move' ).addClass( 'is-disabled' );
								$( targetPopup ).find( '.bp-folder-move' ).addClass( 'is-disabled' );
							} else {
								$( targetPopup ).find( '.bp-document-move' ).removeClass( 'is-disabled' );
								$( targetPopup ).find( '.bp-folder-move' ).removeClass( 'is-disabled' );
							}

							//Disable move button if current folder is already a parent
							setTimeout( function () {

								var fileID = 0;

								if ( $( targetPopup ).find( '.breadcrumbs-append-ul-li .item > span:last-child' ).hasClass( 'hidden' ) ) {
									fileID = $( targetPopup ).find( '.breadcrumbs-append-ul-li .item > span:last-child' ).prev().attr( 'id' );
								} else {
									fileID = $( targetPopup ).find( '.breadcrumbs-append-ul-li .item > span:last-child' ).attr( 'id' );
								}
								if ( currentTargetParent === fileID && ( $( targetPopup ).hasClass( 'bp-media-move-file' ) || $( targetPopup ).hasClass( 'bp-media-move-folder' ) ) ) {
									$( targetPopup ).find( '.bp-document-move' ).addClass( 'is-disabled' );
									$( targetPopup ).find( '.bp-folder-move' ).addClass( 'is-disabled' );
								} else {
									$( targetPopup ).find( '.bp-document-move' ).removeClass( 'is-disabled' );
									$( targetPopup ).find( '.bp-folder-move' ).removeClass( 'is-disabled' );
								}

							}, 100 );

						}
					);

					$( document ).on(
						'click',
						targetPopup + ' .breadcrumbs-append-ul-li .item span',
						function ( event ) {

							if ( $( this ).parent().hasClass( 'is-disabled' ) || $( this ).hasClass( 'more_options' ) ) {
								return;
							}

							var currentLiID = $( event.currentTarget ).attr( 'data-id' );
							$( targetPopup ).find( '.location-folder-list-wrap' ).find( '.location-folder-title' ).text( $( targetPopup ).find( '.location-folder-list li.is_active' ).closest( '.has-ul' ).children( 'span' ).text() ).siblings( '.location-folder-back' ).css( 'display', 'inline-block' );
							$( targetPopup ).find( '.bb-folder-selected-id' ).val( currentLiID );
							$( targetPopup ).find( '.location-folder-list li' ).hide();
							$( targetPopup ).find( '.location-folder-list li.is_active' ).removeClass( 'is_active' );
							$( targetPopup ).find( '.location-folder-list li > span.selected' ).removeClass( 'selected' );
							$( targetPopup ).find( '.location-folder-list li[data-id="' + currentLiID + '"]' ).addClass( 'is_active' ).children( 'span' ).addClass( 'selected' );
							$( targetPopup ).find( '.location-folder-list li.is_active' ).parents( '.has-ul' ).show().children( 'ul' ).show().siblings( 'span,i' ).hide();

							if ( $( targetPopup ).find( '.location-folder-list li.is_active' ).children( 'ul' ).length && !$( targetPopup ).find( '.location-folder-list li.is_active' ).children( 'ul' ).hasClass( 'no-folder-list' ) ) {
								setTimeout( function () {
									$( targetPopup ).find( '.location-folder-list li.is_active' ).show().children( 'ul' ).show().children( 'li' ).show().children( 'span,i' ).show().closest( 'ul' ).siblings( 'span, i' ).hide();
								}, 100 );
							} else {

								if ( $( targetPopup ).find( '.location-folder-list li.is_active' ).hasClass( 'has-ul' ).length ) {
									$( targetPopup ).find( '.location-folder-list li.is_active' ).children( 'span,i' ).hide().parent().children( 'ul' ).show().children( 'li' ).show();
								} else {
									setTimeout( function () {
										$( targetPopup ).find( '.location-folder-list li.is_active' ).show().children( 'span' ).show().parent().siblings( 'li' ).show().children( 'span,i' ).show();
									}, 10 );
								}

							}

							if ( currentLiID === '0' ) {
								$( targetPopup ).find( '.location-folder-list' ).children( 'li' ).show().children( 'span,i' ).show();
								$( targetPopup ).find( '.location-folder-list-wrap' ).find( '.location-folder-title' ).text( BP_Nouveau.media.target_text );
								$( targetPopup ).find( '.location-folder-back' ).hide();
							}

							$( event.currentTarget ).nextAll().remove();

							if ( currentTargetParent === $( targetPopup ).find( '.breadcrumbs-append-ul-li .item > span:last-child' ).attr( 'data-id' ) && ( $( targetPopup ).hasClass( 'bp-media-move-file' ) || $( targetPopup ).hasClass( 'bp-media-move-folder' ) ) ) {
								$( targetPopup ).find( '.bp-document-move' ).addClass( 'is-disabled' );
								$( targetPopup ).find( '.bp-folder-move' ).addClass( 'is-disabled' );
							} else {
								$( targetPopup ).find( '.bp-document-move' ).removeClass( 'is-disabled' );
								$( targetPopup ).find( '.bp-folder-move' ).removeClass( 'is-disabled' );
							}

							var $this = $( targetPopup ).find( '.location-folder-list .is_active > span' ),
								$bc = $( '<div class="item"></div>' );

							$this.parents( 'li' ).each( function ( n, li ) {
								var $a = $( li ).children( 'span' ).clone();
								$bc.prepend( '', $a );
							} );
							$( targetPopup ).find( '.breadcrumbs-append-ul-li .breadcrumb' ).html( $bc.prepend( '<span data-id="0">' + BP_Nouveau.media.target_text + '</span>' ) );

							if ( $( targetPopup ).find( '.breadcrumbs-append-ul-li .breadcrumb .item > span[data-id="' + currentLiID + '"]' ).length === 0 ) {
								$( targetPopup ).find( '.breadcrumbs-append-ul-li .breadcrumb .item' ).append( $( targetPopup ).find( '.location-folder-list li[data-id="' + currentLiID + '"]' ).children( 'span' ).clone() );
							}

							if ( !$( targetPopup ).find( '.breadcrumbs-append-ul-li .breadcrumb .item span.hidden' ).length ) {
								$( targetPopup ).find( '.breadcrumbs-append-ul-li .breadcrumb' ).find( '.item' ).append( '<span class="hidden"></span>' );
							}

							$( targetPopup ).find( '.breadcrumbs-append-ul-li .breadcrumb .item span:not(.hidden)' ).each( function ( i ) {

								if ( i > 0 ) {
									if ( $( targetPopup ).find( '.breadcrumbs-append-ul-li .breadcrumb .item' ).width() > $( targetPopup ).find( '.breadcrumbs-append-ul-li .breadcrumb' ).width() ) {

										$( targetPopup ).find( '.breadcrumbs-append-ul-li .breadcrumb .item span.hidden' ).append( $( targetPopup ).find( '.breadcrumbs-append-ul-li .breadcrumb .item span' ).eq( 2 ) );

										if ( !$( targetPopup ).find( '.breadcrumbs-append-ul-li .breadcrumb .item .more_options' ).length ) {
											$( '<span class="more_options">...</span>' ).insertAfter( $( targetPopup ).find( '.breadcrumbs-append-ul-li .breadcrumb .item span' ).eq( 0 ) );
										}

									}
								}
							} );
							$( targetPopup ).find( '.bb-folder-selected-id' ).val( currentLiID );
						}
					);
				}

				$( targetPopup ).find( '.location-folder-list li' ).each(
					function () {
						$( this ).children( 'ul' ).parent().addClass( 'has-ul' ).append( '<i class="bb-icon-l bb-icon-angle-right sub-menu-anchor"></i>' );
					}
				);

				if ( $( targetPopup ).hasClass( 'bp-media-move-folder' ) ) {
					$( targetPopup ).find( '.location-folder-list li>span' ).removeClass( 'is-disabled' );
					$( targetPopup ).find( '.location-folder-list li>span[id="' + $( targetPopup ).find( '.bp-folder-move' ).attr( 'id' ) + '"]' ).parent().addClass( 'is-disabled' );
				}

				var currentMoveItemID = $( targetPopup ).find( '.bp-folder-move' ).attr( 'id' );

				if ( $( targetPopup ).find( '.location-folder-list li[data-id="' + currentMoveItemID + '"]' ).siblings().length == 0 ) {
					$( targetPopup ).find( '.location-folder-list li[data-id="' + currentMoveItemID + '"]' ).parent( 'ul' ).addClass( 'no-folder-list a' );
				}

				if ( currentTargetParent ) {

					$( targetPopup ).find( '.location-folder-list li' ).hide();
					$( targetPopup ).find( '.location-folder-list li.is_active' ).removeClass( 'is_active' );
					$( targetPopup ).find( '.location-folder-list li[data-id="' + currentTargetParent + '"]' ).addClass( 'is_active' );
					$( targetPopup ).find( '.location-folder-list li.is_active' ).parents( '.has-ul' ).show().children( 'ul' ).show().siblings( 'span,i' ).hide();

					if ( $( targetPopup ).find( '.location-folder-list li.is_active' ).children( 'ul' ).length && !$( targetPopup ).find( '.location-folder-list li.is_active' ).children( 'ul' ).hasClass( 'no-folder-list' ) ) {
						setTimeout( function () {
							$( targetPopup ).find( '.location-folder-list li.is_active' ).show().children( 'ul' ).show().children( 'li' ).show().children( 'span,i' ).show().closest( 'ul' ).siblings( 'span, i' ).hide();
						}, 100 );
					} else {

						if ( $( targetPopup ).find( '.location-folder-list li.is_active' ).hasClass( 'has-ul' ).length ) {
							$( targetPopup ).find( '.location-folder-list li.is_active' ).children( 'span,i' ).hide().parent().children( 'ul' ).show().children( 'li' ).show();
						} else {
							setTimeout( function () {
								$( targetPopup ).find( '.location-folder-list li.is_active' ).show().children( 'span' ).show().parent().siblings( 'li' ).show().children( 'span,i' ).show();
							}, 10 );
						}
					}
					$( targetPopup ).find( '.location-folder-list-wrap' ).find( '.location-folder-title' ).text( $( targetPopup ).find( '.location-folder-list li.is_active' ).closest( '.has-ul' ).children( 'span' ).text() ).siblings( '.location-folder-back' ).css( 'display', 'inline-block' );
					$( targetPopup ).find( '.bb-folder-selected-id' ).val( $( targetPopup ).find( '.location-folder-list li.is_active' ).attr( 'data-id' ) );
					$( targetPopup ).find( '.location-folder-list li[data-id="' + currentMoveItemID + '"]' ).children().hide();
				}

				if ( currentTargetParent === '0' ) {
					$( targetPopup ).find( '.location-folder-list' ).children( 'li' ).show();
					$( targetPopup ).find( '.location-folder-list-wrap' ).find( '.location-folder-title' ).text( BP_Nouveau.media.target_text );
					$( targetPopup ).find( '.location-folder-back' ).hide();
				}

				//Disable move button if current folder is already a parent
				setTimeout( function () {

					var fileID = 0;

					if ( $( targetPopup ).find( '.breadcrumbs-append-ul-li .item > span:last-child' ).hasClass( 'hidden' ) ) {
						fileID = $( targetPopup ).find( '.breadcrumbs-append-ul-li .item > span:last-child' ).prev().attr( 'id' );
					} else {
						fileID = $( targetPopup ).find( '.breadcrumbs-append-ul-li .item > span:last-child' ).attr( 'id' );
					}
					if ( currentTargetParent === fileID && ( $( targetPopup ).hasClass( 'bp-media-move-file' ) || $( targetPopup ).hasClass( 'bp-media-move-folder' ) ) ) {
						$( targetPopup ).find( '.bp-document-move' ).addClass( 'is-disabled' );
						$( targetPopup ).find( '.bp-folder-move' ).addClass( 'is-disabled' );
					} else {
						$( targetPopup ).find( '.bp-document-move' ).removeClass( 'is-disabled' );
						$( targetPopup ).find( '.bp-folder-move' ).removeClass( 'is-disabled' );
					}

				}, 100 );

				var $this = $( targetPopup ).find( '.location-folder-list .is_active > span' ),
					$bc = $( '<div class="item"></div>' );

				$this.parents( 'li' ).each( function ( n, li ) {
					var $a = $( li ).children( 'span' ).clone();
					$bc.prepend( '', $a );
				} );
				$( targetPopup ).find( '.breadcrumbs-append-ul-li .breadcrumb' ).html( $bc.prepend( '<span data-id="0">' + BP_Nouveau.media.target_text + '</span>' ) );

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

		closeCreateAlbumModal: function ( event ) {
			event.preventDefault();

			this.closeUploader( event );
			$( '#bp-media-create-album' ).hide();
			$( '#bb-album-title' ).val( '' ).removeClass( 'error' );

		},

		closeCreateFolderModal: function ( event ) {
			event.preventDefault();
			$( '#bp-media-create-folder, #bp-media-create-child-folder' ).hide();
			$( '#bb-album-title, #bb-album-child-title' ).val( '' );
			$( '#bp-media-create-child-folder-submit' ).removeClass( 'loading' );
		},

		closeEditFolderModal: function ( event ) {
			event.preventDefault();

			var currentPopup = $( event.currentTarget ).closest( '#bp-media-edit-child-folder' );

			$( '#bp-media-edit-child-folder' ).hide();
			currentPopup.find( '.bb-field-steps-1' ).show().siblings( '.bb-field-steps' ).hide();
			this.clearFolderLocationUI( event );
		},

		closeErrorPopup: function ( event ) {
			event.preventDefault();
			$( event.currentTarget ).closest( '.open-popup' ).remove();
		},

		submitMedia: function ( event ) {
			var self = this, target = $( event.currentTarget ), data, privacy = $( '#bb-media-privacy' );
			event.preventDefault();

			if ( target.hasClass( 'saving' ) ) {
				return false;
			}

			target.addClass( 'saving' );

			if ( self.current_tab === 'bp-dropzone-content' ) {

				var post_content = $( '#bp-media-post-content' ).val();

				var targetPopup = $( event.currentTarget ).closest( '.open-popup' );
				var selectedAlbum = targetPopup.find( '.bb-album-selected-id' ).val();
				if ( selectedAlbum.length && parseInt( selectedAlbum ) > 0 ) {
					selectedAlbum = selectedAlbum;
					for ( var i = 0; i < self.dropzone_media.length; i++ ) {
						self.dropzone_media[ i ].album_id = selectedAlbum;
					}

				} else {
					selectedAlbum = self.album_id;
				}

				data = {
					'action': 'media_save',
					'_wpnonce': BP_Nouveau.nonces.media,
					'medias': self.dropzone_media,
					'content': post_content,
					'album_id': selectedAlbum,
					'group_id': self.group_id,
					'privacy': privacy.val()
				};

				$( '#bp-dropzone-content .bp-feedback' ).remove();

				$.ajax(
					{
						type: 'POST',
						url: BP_Nouveau.ajaxurl,
						data: data,
						success: function ( response ) {
							if ( response.success ) {

								// It's the very first media, let's make sure the container can welcome it!
								if ( !$( '#media-stream ul.media-list' ).length ) {
									$( '#media-stream' ).html( $( '<ul></ul>' ).addClass( 'media-list item-list bp-list bb-photo-list grid' ) );
								}

								if ( $( '.bb-photos-actions' ).length > 0 ) {
									$( '.bb-photos-actions' ).show();
								}

								// Prepend the activity.
								bp.Nouveau.inject( '#media-stream ul.media-list', response.data.media, 'prepend' );

								if ( response.data.media_personal_count ) {
									if ( $( '#buddypress' ).find( '.bp-wrap .users-nav ul li#media-personal-li a span.count' ).length ) {
										$( '#buddypress' ).find( '.bp-wrap .users-nav ul li#media-personal-li a span.count' ).text( response.data.media_personal_count );
									} else {
										var mediaPersonalSpanTag = document.createElement( 'span' );
										mediaPersonalSpanTag.setAttribute( 'class', 'count' );
										var mediaPersonalSpanTagTextNode = document.createTextNode( response.data.media_personal_count );
										mediaPersonalSpanTag.appendChild( mediaPersonalSpanTagTextNode );
										$( '#buddypress' ).find( '.bp-wrap .users-nav ul li#media-personal-li a' ).append( mediaPersonalSpanTag );
									}
								}

								if ( response.data.media_group_count ) {
									if ( $( '#buddypress' ).find( '.bp-wrap .groups-nav ul li#photos-groups-li a span.count' ).length ) {
										$( '#buddypress' ).find( '.bp-wrap .groups-nav ul li#photos-groups-li a span.count' ).text( response.data.media_group_count );
									} else {
										var photoGroupSpanTag = document.createElement( 'span' );
										photoGroupSpanTag.setAttribute( 'class', 'count' );
										var photoGroupSpanTagTextNode = document.createTextNode( response.data.media_group_count );
										photoGroupSpanTag.appendChild( photoGroupSpanTagTextNode );
										$( '#buddypress' ).find( '.bp-wrap .users-nav ul li#photos-groups-li a' ).append( photoGroupSpanTag );
									}
								}

								if ( 'yes' === BP_Nouveau.media.is_media_directory ) {
									$( '#buddypress' ).find( '.media-type-navs ul.media-nav li#media-all a span.count' ).text( response.data.media_all_count );
									$( '#buddypress' ).find( '.media-type-navs ul.media-nav li#media-personal a span.count' ).text( response.data.media_personal_count );
									$( '#buddypress' ).find( '.media-type-navs ul.media-nav li#media-groups a span.count' ).text( response.data.media_group_count );
								}

								for ( var i = 0; i < self.dropzone_media.length; i++ ) {
									self.dropzone_media[ i ].saved = true;
								}

								// Reset the selector album_id
								targetPopup.find( '.bb-album-selected-id' ).val( 0 );

								self.closeUploader( event );

								// replace dummy image with original image by faking scroll event to call bp.Nouveau.lazyLoad.
								jQuery( window ).scroll();

							} else {
								$( '#bp-dropzone-content' ).prepend( response.data.feedback );
							}

							target.removeClass( 'saving' );
						}
					}
				);

			} else if ( self.current_tab === 'bp-existing-media-content' ) {
				var selected = [];
				$( '.bp-existing-media-wrap .bb-media-check-wrap [name="bb-media-select"]:checked' ).each(
					function () {
						selected.push( $( this ).val() );
					}
				);
				data = {
					'action': 'media_move_to_album',
					'_wpnonce': BP_Nouveau.nonces.media,
					'medias': selected,
					'album_id': self.album_id,
					'group_id': self.group_id
				};

				$( '#bp-existing-media-content .bp-feedback' ).remove();

				$.ajax(
					{
						type: 'POST',
						url: BP_Nouveau.ajaxurl,
						data: data,
						success: function ( response ) {
							if ( response.success ) {

								// It's the very first media, let's make sure the container can welcome it!
								if ( !$( '#media-stream ul.media-list' ).length ) {
									$( '#media-stream' ).html( $( '<ul></ul>' ).addClass( 'media-list item-list bp-list bb-photo-list grid' ) );
								}

								if ( $( '.bb-photos-actions' ).length > 0 ) {
									$( '.bb-photos-actions' ).show();
								}

								// Prepend the activity.
								bp.Nouveau.inject( '#media-stream ul.media-list', response.data.media, 'prepend' );

								// remove selected media from existing media list.
								$( '.bp-existing-media-wrap .bb-media-check-wrap [name="bb-media-select"]:checked' ).each(
									function () {
										if ( parseInt( $( this ).closest( 'li' ).data( 'id' ) ) === parseInt( $( this ).val() ) ) {
											$( this ).closest( 'li' ).remove();
										}
									}
								);

								jQuery( window ).scroll();

								self.closeUploader( event );
							} else {
								$( '#bp-existing-media-content' ).prepend( response.data.feedback );
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

		submitDocumentMedia: function ( event ) {
			var self = this, target = $( event.currentTarget ), data,
				currentPopup = $( event.currentTarget ).closest( '#bp-media-uploader' );
			event.preventDefault();

			if ( target.hasClass( 'saving' ) ) {
				return false;
			}

			target.addClass( 'saving' );

			if ( self.current_tab === 'bp-dropzone-content' ) {

				var post_content = $( '#bp-media-post-content' ).val();
				var privacy = $( '#bb-document-privacy' ).val();

				var targetPopup = $( event.currentTarget ).closest( '.open-popup' );
				var selectedAlbum = targetPopup.find( '.bb-folder-selected-id' ).val();
				var currentAlbum = targetPopup.find( '.bb-folder-selected-id' ).data( 'value' );
				var hasNotAlbum = true;
				if ( selectedAlbum.length && parseInt( selectedAlbum ) > 0 ) {

					if ( typeof currentAlbum !== 'undefined' && parseInt( selectedAlbum ) !== parseInt( currentAlbum ) ) {
						hasNotAlbum = false;
					}

					selectedAlbum = selectedAlbum;
					for ( var i = 0; i < self.dropzone_media.length; i++ ) {
						self.dropzone_media[ i ].folder_id = selectedAlbum;
					}

				} else {
					selectedAlbum = self.album_id;
				}

				data = {
					'action': 'document_document_save',
					'_wpnonce': BP_Nouveau.nonces.media,
					'documents': self.dropzone_media,
					'content': post_content,
					'privacy': privacy,
					'folder_id': self.current_folder,
					'group_id': self.current_group_id
				};

				$( '#bp-dropzone-content .bp-feedback' ).remove();

				$.ajax(
					{
						type: 'POST',
						url: BP_Nouveau.ajaxurl,
						data: data,
						success: function ( response ) {
							if ( response.success ) {

								// It's the very first media, let's make sure the container can welcome it!
								if ( !$( '#media-stream div#media-folder-document-data-table' ).length ) {
									$( '#media-stream' ).html( $( '<div></div>' ).addClass( 'display' ) );
									$( '#media-stream div' ).attr( 'id', 'media-folder-document-data-table' );
									$( '.bb-photos-actions' ).show();
								}

								// Reload the page if no document and adding first time.
								if ( $( '.document-data-table-head' ).length ) {
									if ( 'yes' === BP_Nouveau.media.is_document_directory ) {
										var store = bp.Nouveau.getStorage( 'bp-document' );
										var scope = store.scope;
										if ( 'groups' === scope ) {
											$( document ).find( 'li#document-personal a' ).trigger( 'click' );
											$( document ).find( 'li#document-personal' ).trigger( 'click' );
										} else {
											// Prepend the activity.
											hasNotAlbum ? bp.Nouveau.inject( '#media-stream div#media-folder-document-data-table', response.data.document, 'prepend' ) : '';

										}
									} else {
										// Prepend the activity.
										hasNotAlbum ? bp.Nouveau.inject('#media-stream div#media-folder-document-data-table', response.data.document, 'prepend') : '';
									}
								} else {
									location.reload( true );
								}

								$( '#bp-media-post-content' ).val( '' );

								for ( var i = 0; i < self.dropzone_media.length; i++ ) {
									self.dropzone_media[ i ].saved = true;
								}

								self.closeUploader( event );
								$( document ).removeClass( 'open-popup' );
								jQuery( window ).scroll();

							} else {
								$( document ).removeClass( 'open-popup' );
								$( '#bp-dropzone-content' ).prepend( response.data.feedback );
							}

							target.removeClass( 'saving' );

							currentPopup.find( '#bp-media-document-submit' ).hide();

						}
					}
				);

			} else if ( !self.current_tab ) {
				self.closeUploader( event );
				target.removeClass( 'saving' );
			}

		},

		saveAlbum: function ( event ) {
			var target = $( event.currentTarget ), self = this, title = $( '#bb-album-title' ),
				privacy = $( '#bb-album-privacy' );

			if ( target.hasClass( 'saving' ) ) {
				return false;
			}

			event.preventDefault();

			if ( $.trim( title.val() ) === '' ) {
				title.addClass( 'error' );
				return false;
			} else {
				title.removeClass( 'error' );
			}

			if ( !self.group_id && $.trim( privacy.val() ) === '' ) {
				privacy.addClass( 'error' );
				return false;
			} else {
				privacy.removeClass( 'error' );
			}

			target.addClass( 'saving' );
			target.attr( 'disabled', true );
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

			// remove all feedback erros from the DOM.
			$( '#bp-media-single-album .bp-feedback' ).remove();
			$( '#boss-media-create-album-popup .bp-feedback' ).remove();

			$.ajax(
				{
					type: 'POST',
					url: BP_Nouveau.ajaxurl,
					data: data,
					success: function ( response ) {
						setTimeout(
							function () {
								target.removeClass( 'saving' );
								target.prop( 'disabled', false );
							},
							500
						);
						if ( response.success ) {
							if ( self.album_id ) {
								$( '#bp-single-album-title' ).text( title.val() );
								$( '#bb-album-privacy' ).val( privacy.val() );
								self.cancelEditAlbumTitle( event );
							} else {
								$( '#buddypress .bb-albums-list' ).prepend( response.data.album );
								window.location.href = response.data.redirect_url;
							}
						} else {
							if ( self.album_id ) {
								$( '#bp-media-single-album' ).prepend( response.data.feedback );
							} else {
								$( '#boss-media-create-album-popup .bb-model-header' ).after( response.data.feedback );
							}
						}
					}
				}
			);

		},

		saveFolder: function ( event ) {
			var target = $( event.currentTarget ), self = this, title = $( '#bb-album-title' ),
				privacy = $( event.currentTarget ).parents().find( '.open-popup #bb-folder-privacy option:selected' );
			event.preventDefault();

			var pattern = /[\\/?%*:|"<>]+/g; // regex to find not supported characters - \ / ? % * : | " < >
			var matches = pattern.exec( title.val() );
			var matchStatus = Boolean( matches );

			if ( $.trim( title.val() ) === '' || matchStatus ) {
				title.addClass( 'error' );
				return false;
			} else {
				title.removeClass( 'error' );
			}

			if ( !self.group_id && $.trim( privacy.val() ) === '' ) {
				privacy.addClass( 'error' );
				return false;
			} else {
				privacy.removeClass( 'error' );
			}

			target.prop( 'disabled', true ).addClass( 'loading' );

			var data = {
				'action': 'document_folder_save',
				'_wpnonce': BP_Nouveau.nonces.media,
				'title': title.val().trim(),
				'privacy': privacy.val(),
				'album_id': self.current_folder,
				'group_id': self.current_group_id
			};

			// remove all feedback erros from the DOM.
			$( '.bb-single-album-header .bp-feedback' ).remove();
			$( '#boss-media-create-album-popup .bp-feedback' ).remove();

			$.ajax(
				{
					type: 'POST',
					url: BP_Nouveau.ajaxurl,
					data: data,
					success: function ( response ) {
						setTimeout(
							function () {
								target.prop( 'disabled', false );
							},
							500
						);
						if ( response.success ) {
							self.closeFolderUploader( event );
							if ( $( '.document-data-table-head' ).length ) {
								if ( 'yes' === BP_Nouveau.media.is_document_directory ) {
									var store = bp.Nouveau.getStorage( 'bp-document' );
									var scope = store.scope;
									if ( 'groups' === scope ) {
										$( document ).find( 'li#document-personal a' ).trigger( 'click' );
										$( document ).find( 'li#document-personal' ).trigger( 'click' );
									} else {
										// Prepend the activity if no parent.
										bp.Nouveau.inject( '#media-stream div#media-folder-document-data-table', response.data.document, 'prepend' );
										jQuery( window ).scroll();
									}
								} else {
									// Prepend the activity if no parent.
									bp.Nouveau.inject( '#media-stream div#media-folder-document-data-table', response.data.document, 'prepend' );
									jQuery( window ).scroll();
								}
							} else {
								location.reload( true );
							}
						} else {
							/* jshint ignore:start */
							alert( response.data.feedback.replace( '&#039;', '\'' ) );
							/* jshint ignore:end */
						}
					}
				}
			);

		},

		saveChildFolder: function ( event ) {
			var target = $( event.currentTarget ), self = this,
				title = $( '#bp-media-create-child-folder #bb-album-child-title' );
			event.preventDefault();

			var pattern = /[\\/?%*:|"<>]+/g; // regex to find not supported characters - \ / ? % * : | " < >
			var matches = pattern.exec( title.val() );
			var matchStatus = Boolean( matches );

			if ( $.trim( title.val() ) === '' || matchStatus ) {
				title.addClass( 'error' );
				return false;
			} else {
				title.removeClass( 'error' );
			}
			target.prop( 'disabled', true ).addClass( 'loading' );

			var data = {
				'action': 'document_child_folder_save',
				'_wpnonce': BP_Nouveau.nonces.media,
				'title': title.val(),
				'folder_id': self.current_folder,
				'group_id': self.current_group_id,
			};

			// remove all feedback erros from the DOM.
			$( '.bb-single-album-header .bp-feedback' ).remove();
			$( '#boss-media-create-album-popup .bp-feedback' ).remove();

			$.ajax(
				{
					type: 'POST',
					url: BP_Nouveau.ajaxurl,
					data: data,
					success: function ( response ) {
						if ( response.success ) {
							self.closeChildFolderUploader( event );
							//if ( $( '.document-data-table-head' ).length ) {
							// Prepend the activity if no parent.
							//bp.Nouveau.inject( '#media-stream div#media-folder-document-data-table', response.data.document, 'prepend' );
							//jQuery( window ).scroll();
							//} else {
							location.reload( true );
							//}
						} else {
							/* jshint ignore:start */
							alert( response.data.feedback.replace( '&#039;', '\'' ) );
							/* jshint ignore:end */
						}
					}
				}
			);

		},

		renameChildFolder: function ( event ) {
			event.preventDefault();
			var target = $( event.currentTarget ), self = this,
				title = $( '#bp-media-edit-child-folder #bb-album-child-title' ),
				privacy = $( '#bp-media-edit-child-folder #bb-folder-privacy' ),
				id = this.currentTargetParent;

			var pattern = /[\\/?%*:|"<>]+/g; // regex to find not supported characters - \ / ? % * : | " < >
			var matches = pattern.exec( title.val() );
			var matchStatus = Boolean( matches );

			if ( $.trim( title.val() ) === '' || matchStatus ) {
				title.addClass( 'error' );
				return false;
			} else {
				title.removeClass( 'error' );
			}

			target.prop( 'disabled', true ).addClass( 'loading' );

			var data = {
				'action': 'document_edit_folder',
				'_wpnonce': BP_Nouveau.nonces.media,
				'title': title.val(),
				'privacy': privacy.val(),
				'id': id,
				'group_id': self.current_group_id,
			};

			// remove all feedback erros from the DOM.
			$( '.bb-single-album-header .bp-feedback' ).remove();
			$( '#boss-media-create-album-popup .bp-feedback' ).remove();

			$.ajax(
				{
					type: 'POST',
					url: BP_Nouveau.ajaxurl,
					data: data,
					success: function ( response ) {
						setTimeout(
							function () {
								target.prop( 'disabled', false );
							},
							500
						);
						if ( response.success ) {
							window.location.reload( true );
						} else {
							if ( self.current_folder ) {
								$( '#bp-media-single-album' ).prepend( response.data.feedback );
							} else {
								$( '#boss-media-create-album-popup .bb-model-header' ).after( response.data.feedback );
							}
						}
					}
				}
			);

		},

		deleteAlbum: function ( event ) {
			event.preventDefault();

			if ( !this.album_id ) {
				return false;
			}

			if ( !confirm( BP_Nouveau.media.i18n_strings.album_delete_confirm ) ) {
				return false;
			}

			$( event.currentTarget ).prop( 'disabled', true );

			var data = {
				'action': 'media_album_delete',
				'_wpnonce': BP_Nouveau.nonces.media,
				'album_id': this.album_id,
				'group_id': this.group_id
			};

			$.ajax(
				{
					type: 'POST',
					url: BP_Nouveau.ajaxurl,
					data: data,
					success: function ( response ) {
						if ( response.success ) {
							window.location.href = response.data.redirect_url;
						} else {
							alert( BP_Nouveau.media.i18n_strings.album_delete_error );
							$( event.currentTarget ).prop( 'disabled', false );
						}
					}
				}
			);

		},

		deleteFolder: function ( event ) {
			event.preventDefault();

			if ( !BP_Nouveau.media.current_folder ) {
				return false;
			}

			if ( !confirm( BP_Nouveau.media.i18n_strings.folder_delete_confirm ) ) {
				return false;
			}

			$( event.currentTarget ).prop( 'disabled', true );

			var data = {
				'action': 'document_folder_delete',
				'_wpnonce': BP_Nouveau.nonces.media,
				'folder_id': BP_Nouveau.media.current_folder,
				'group_id': BP_Nouveau.media.current_group_id
			};

			$.ajax(
				{
					type: 'POST',
					url: BP_Nouveau.ajaxurl,
					data: data,
					success: function ( response ) {
						if ( response.success ) {
							window.location.href = response.data.redirect_url;
						} else {
							alert( BP_Nouveau.media.i18n_strings.folder_delete_error );
							$( event.currentTarget ).prop( 'disabled', false );
						}
					}
				}
			);

		},

		addMediaIdsToGroupMessagesForm: function () {
			var self = this;
			if ( $( '#bp_group_messages_media' ).length ) {
				$( '#bp_group_messages_media' ).val( JSON.stringify( self.dropzone_media ) );
			}
		},

		addDocumentIdsToGroupMessagesForm: function () {
			var self = this;
			if ( $( '#bp_group_messages_document' ).length ) {
				$( '#bp_group_messages_document' ).val( JSON.stringify( self.dropzone_media ) );
			}
		},

		addVideoIdsToGroupMessagesForm: function () {
			var self = this;
			if ( $( '#bp_group_messages_video' ).length ) {
				$( '#bp_group_messages_video' ).val( JSON.stringify( self.dropzone_media ) );
			}
		},

		/**
		 * [injectQuery description]
		 *
		 * @param  {[type]} event [description]
		 * @return {[type]}       [description]
		 */
		injectMedias: function ( event ) {
			var store = bp.Nouveau.getStorage( 'bp-media' ),
				scope = store.scope || null, filter = store.filter || null;

			if ( $( event.currentTarget ).hasClass( 'load-more' ) ) {
				var next_page = ( Number( this.current_page ) * 1 ) + 1, self = this, search_terms = '';

				// Stop event propagation.
				event.preventDefault();

				$( event.currentTarget ).find( 'a' ).first().addClass( 'loading' );

				if ( $( '#buddypress .dir-search input[type=search]' ).length ) {
					search_terms = $( '#buddypress .dir-search input[type=search]' ).val();
				}

				bp.Nouveau.objectRequest(
					{
						object: 'media',
						scope: scope,
						filter: filter,
						search_terms: search_terms,
						page: next_page,
						method: 'append',
						target: '#buddypress [data-bp-list] ul.bp-list'
					}
				).done(
					function ( response ) {
						if ( true === response.success ) {
							$( event.currentTarget ).remove();

							// Update the current page.
							self.current_page = next_page;

							jQuery( window ).scroll();
						}
					}
				);
			}
		},

		injectDocuments: function ( event ) {

			var store = bp.Nouveau.getStorage( 'bp-document' ), sort = '', order_by = '',
				scope = store.scope || null, filter = store.filter || null, currentTarget = $( event.currentTarget );

			if ( currentTarget.hasClass( 'load-more' ) ) {
				var next_page = ( Number( this.current_page ) * 1 ) + 1, self = this, search_terms = '';

				// Stop event propagation.
				event.preventDefault();

				currentTarget.find( 'a' ).first().addClass( 'loading' );

				if ( $( '#buddypress .dir-search input[type=search]' ).length ) {
					search_terms = $( '#buddypress .dir-search input[type=search]' ).val();
				}

				if ( this.order_by && this.sort_by ) {
					sort = this.sort_by;
					order_by = this.order_by;
				} else if ( undefined !== store.extras ) {
					sort = store.extras.sort;
					order_by = store.extras.orderby;
				} else {
					sort = 'ASC';
					order_by = 'title';
				}

				var queryData;
				queryData = {
					object: 'document',
					scope: scope,
					filter: filter,
					search_terms: search_terms,
					page: next_page,
					method: 'append',
					target: '#buddypress [data-bp-list] div#media-folder-document-data-table',
					order_by: order_by,
					sort: sort
				};

				bp.Nouveau.objectRequest(
					queryData
				).done(
					function ( response ) {
						if ( true === response.success ) {
							currentTarget.parent( '.pager' ).remove();

							// Update the current page.
							self.current_page = next_page;

							jQuery( window ).scroll();
						}
					}
				);
			}
		},

		/* jshint ignore:start */
		sortDocuments: function ( event ) {

			var sortTarget = $( event.currentTarget ), sortArg = sortTarget.data( 'target' ), search_terms = '',
				order_by = 'date_created', sort = '', next_page = 1;
			var currentFilter = sortTarget.attr( 'class' );
			switch ( sortArg ) {
				case 'name':
					order_by = 'title';
					break;
				case 'modified':
					order_by = 'date_modified';
					break;
				case 'visibility':
					order_by = 'privacy';
					break;
				case 'group':
					order_by = 'group_id';
					break;
			}

			sortTarget.hasClass( 'asce' ) ? sortTarget.removeClass( 'asce' ) : sortTarget.addClass( 'asce' );
			var sort = sortTarget.hasClass( 'asce' ) ? 'DESC' : 'ASC';
			var objectData = bp.Nouveau.getStorage( 'bp-document' );
			var extras = {};

			extras.orderby = order_by;
			extras.sort = sort;

			if ( 'group' !== order_by ) {
				bp.Nouveau.setStorage( 'bp-document', 'extras', extras );
			}

			var store = bp.Nouveau.getStorage( 'bp-document' ),
				scope = store.scope || null, filter = store.filter || null, currentTarget = $( event.currentTarget );

			if ( $( '#buddypress .bp-dir-search-form input[type=search]' ).length ) {
				search_terms = $( '#buddypress .bp-dir-search-form input[type=search]' ).val();
			}

			this.sort_by = sort;
			this.order_by = order_by;
			this.current_page = next_page;

			bp.Nouveau.objectRequest(
				{
					object: 'document',
					scope: scope,
					filter: filter,
					search_terms: search_terms,
					page: next_page,
					order_by: order_by,
					sort: sort,
					method: 'reset',
					target: '#buddypress [data-bp-list]'
				}
			).done();

		},
		/* jshint ignore:end */

		documentPopupNavigate: function ( event ) {

			event.preventDefault();

			var target = $( event.currentTarget ), currentSlide = target.closest( '.bb-field-steps' );

			// Check if this is documnet parent or child folder page.
			var titleField = target.closest( '.bp-document-listing' ).length == 0 ? '#bb-album-child-title' : '#bb-album-title';

			if ( target.closest( '.document-options' ).length ) { // Check if this is /document page.
				titleField = '#bb-album-title';
			}

			if ( target.hasClass( 'bb-field-steps-next' ) && currentSlide.find( titleField ).val().trim() == '' ) {
				currentSlide.find( titleField ).addClass( 'error' );
				return;
			} else {
				currentSlide.find( titleField ).removeClass( 'error' );
			}

			currentSlide.slideUp( 200 ).siblings( '.bb-field-steps' ).slideDown( 200 );
		},

		uploadDocumentNavigate: function ( event ) {

			event.preventDefault();

			var target = $( event.currentTarget ), currentPopup = $( target ).closest( '#bp-media-uploader' );

			if ( $( target ).hasClass( 'bb-field-uploader-next' ) ) {
				currentPopup.find( '.bb-field-steps-1' ).slideUp( 200 ).siblings( '.bb-field-steps' ).slideDown( 200 );
				currentPopup.find( '#bp-media-document-submit, #bp-media-document-prev, .bp-document-open-create-popup-folder, #bb-document-privacy' ).show();
				if ( Number( $( currentPopup ).find( '.bb-folder-selected-id' ) ) !== 0 && $( currentPopup ).find( '.location-folder-list li.is_active' ).length ) {
					$( currentPopup ).find( '.location-folder-list' ).scrollTop( $( currentPopup ).find( '.location-folder-list li.is_active' ).offset().top - $( currentPopup ).find( '.location-folder-list' ).offset().top );
				}
				$( currentPopup ).find( '.breadcrumbs-append-ul-li .breadcrumb .item span:not(.hidden)' ).each( function ( i ) {
					if ( i > 0 ) {
						if ( $( currentPopup ).find( '.breadcrumbs-append-ul-li .breadcrumb .item' ).width() > $( currentPopup ).find( '.breadcrumbs-append-ul-li .breadcrumb' ).width() ) {
							$( currentPopup ).find( '.breadcrumbs-append-ul-li .breadcrumb .item span.hidden' ).append( $( currentPopup ).find( '.breadcrumbs-append-ul-li .breadcrumb .item span' ).eq( 2 ) );

							if ( !$( currentPopup ).find( '.breadcrumbs-append-ul-li .breadcrumb .item .more_options' ).length ) {
								$( '<span class="more_options">...</span>' ).insertAfter( $( currentPopup ).find( '.breadcrumbs-append-ul-li .breadcrumb .item span' ).eq( 0 ) );
							}

						}
					}
				} );
			} else {
				$( target ).hide();
				currentPopup.find( '#bp-media-document-prev, .bp-document-open-create-popup-folder' ).hide();
				currentPopup.find( '.bb-field-steps-2' ).slideUp( 200 ).siblings( '.bb-field-steps' ).slideDown( 200 );
				if ( currentPopup.closest( '#bp-media-single-folder' ).length ) {
					$( '#bb-document-privacy' ).hide();
				}
			}

		},

		uploadMediaNavigate: function ( event ) {

			event.preventDefault();

			var target = $( event.currentTarget ), currentPopup = $( target ).closest( '.bp-media-photo-uploader' );

			if ( $( target ).hasClass( 'bb-field-uploader-next' ) ) {
				currentPopup.find( '.bb-field-steps-1' ).slideUp( 200 ).siblings( '.bb-field-steps' ).slideDown( 200 );
				currentPopup.find( '#bp-media-submit, #bp-media-prev, .bp-media-open-create-popup-folder.create-album' ).show();
				currentPopup.find( '#bb-media-privacy' ).show();
				if ( Number( $( currentPopup ).find( '.bb-album-selected-id' ) ) !== 0 && $( currentPopup ).find( '.location-album-list li.is_active' ).length ) {
					$( currentPopup ).find( '.location-album-list' ).scrollTop( $( currentPopup ).find( '.location-album-list li.is_active' ).offset().top - $( currentPopup ).find( '.location-album-list' ).offset().top );
				}
			} else {
				$( target ).hide();
				currentPopup.find( '#bp-media-prev, .bp-media-open-create-popup-folder' ).hide();
				currentPopup.find( '.bb-field-steps-2' ).slideUp( 200 ).siblings( '.bb-field-steps' ).slideDown( 200 );
				if ( currentPopup.closest( '#bp-media-single-album' ).length ) {
					$( '#bb-media-privacy' ).hide();
				}
			}

		},

		/**
		 * [appendQuery description]
		 *
		 * @param  {[type]} event [description]
		 * @return {[type]}       [description]
		 */
		appendMedia: function ( event ) {
			var store = bp.Nouveau.getStorage( 'bp-media' ),
				scope = store.scope || null, filter = store.filter || null;

			if ( $( event.currentTarget ).hasClass( 'load-more' ) ) {
				var next_page = ( Number( this.current_page_existing_media ) * 1 ) + 1, self = this, search_terms = '';

				// Stop event propagation.
				event.preventDefault();

				$( event.currentTarget ).find( 'a' ).first().addClass( 'loading' );

				if ( $( '#buddypress .dir-search input[type=search]' ).length ) {
					search_terms = $( '#buddypress .dir-search input[type=search]' ).val();
				}

				bp.Nouveau.objectRequest(
					{
						object: 'media',
						scope: scope,
						filter: filter,
						search_terms: search_terms,
						page: next_page,
						method: 'append',
						caller: 'bp-existing-media',
						target: '.bp-existing-media-wrap ul.bp-list'
					}
				).done(
					function ( response ) {
						if ( true === response.success ) {
							$( event.currentTarget ).remove();

							// Update the current page.
							self.current_page_existing_media = next_page;

							// replace dummy image with original image by faking scroll event to call bp.Nouveau.lazyLoad.
							jQuery( window ).scroll();
						}
					}
				);
			}
		},

		/**
		 * [appendQuery description]
		 *
		 * @param  {[type]} event [description]
		 * @return {[type]}       [description]
		 */
		appendAlbums: function ( event ) {
			var next_page = ( Number( this.current_page_albums ) * 1 ) + 1, self = this;

			// Stop event propagation.
			event.preventDefault();

			$( event.currentTarget ).find( 'a' ).first().addClass( 'loading' );

			var data = {
				'action': 'media_albums_loader',
				'_wpnonce': BP_Nouveau.nonces.media,
				'page': next_page
			};

			$.ajax(
				{
					type: 'POST',
					url: BP_Nouveau.ajaxurl,
					data: data,
					success: function ( response ) {
						if ( true === response.success ) {
							$( event.currentTarget ).remove();
							$( '#albums-dir-list ul.bb-albums-list' ).fadeOut(
								100,
								function () {
									$( '#albums-dir-list ul.bb-albums-list' ).append( response.data.albums );
									$( this ).fadeIn( 100 );
								}
							);
							// Update the current page.
							self.current_page_albums = next_page;
						}
					}
				}
			);
		},

		toggleSubmitMediaButton: function () {
			var submit_media_button = $( '#bp-media-submit' ), add_more_button = $( '#bp-media-add-more' );
			if ( this.current_tab === 'bp-dropzone-content' ) {
				if ( this.dropzone_obj.getAcceptedFiles().length ) {
					submit_media_button.show();
					add_more_button.show();
				} else {
					submit_media_button.hide();
					add_more_button.hide();
				}
			} else if ( this.current_tab === 'bp-existing-media-content' ) {
				if ( $( '.bp-existing-media-wrap .bb-media-check-wrap [name="bb-media-select"]:checked' ).length ) {
					submit_media_button.show();
				} else {
					submit_media_button.hide();
				}
				add_more_button.hide();
			}
		},

		// play gif.
		playVideo: function ( event ) {
			event.preventDefault();
			var video = $( event.currentTarget ).find( 'video' ).get( 0 ),
				$button = $( event.currentTarget ).find( '.gif-play-button' );
			if ( video.paused == true ) {
				// Play the video.
				video.play();

				// Update the button text to 'Pause'.
				$button.hide();
			} else {
				// Pause the video.
				video.pause();

				// Update the button text to 'Play'.
				$button.show();
			}
		},

		/**
		 * When the GIF comes into your screen it should auto play
		 */
		autoPlayGifVideos: function () {
			$( '.gif-player' ).each(
				function () {
					var video = $( this ).find( 'video' ).get( 0 ),
						$button = $( this ).find( '.gif-play-button' );

					if ( $( this ).is( ':in-viewport' ) ) {
						// Play the video.
						video.play();

						// Update the button text to 'Pause'.
						$button.hide();
					} else {
						// Pause the video
						video.pause();

						// Update the button text to 'Play'.
						$button.show();
					}
				}
			);
		},

		/**
		 * File action Button
		 */
		fileActionButton: function ( event ) {

			if ( $( event.currentTarget ).parent().hasClass( 'download_file' ) ) {
				return;
			}

			if ( $( event.currentTarget ).parent().hasClass( 'copy_download_file_url' ) ) {
				return;
			}
			if ( $( event.currentTarget ).parent().hasClass( 'redirect-activity-privacy-change' ) ) {
				return;
			}

			event.preventDefault();
			$( event.currentTarget ).closest( '.media-folder_items' ).toggleClass( 'is-visible' ).siblings( '.media-folder_items' ).removeClass( 'is-visible' );
		},

		/**
		 * File action Copy Download Link
		 */
		copyDownloadLink: function ( event ) {
			event.preventDefault();
			var currentTarget = event.currentTarget, currentTargetCopy = 'document_copy_link';
			$( 'body' ).append( '<textarea style="position:absolute;opacity:0;" id="' + currentTargetCopy + '"></textarea>' );
			var oldText = $( currentTarget ).text();
			$( currentTarget ).text( BP_Nouveau.media.copy_to_clip_board_text );
			$( '#' + currentTargetCopy ).val( $( currentTarget ).attr( 'href' ) );
			$( '#' + currentTargetCopy ).select();
			document.execCommand( 'copy' );

			setTimeout( function () {
				$( currentTarget ).text( oldText );
			}, 2000 );

			//$( '#' + currentTargetCopy ).remove();
			return false;
		},

		/**
		 * File Activity action Button
		 */
		fileActivityActionButton: function ( event ) {
			event.preventDefault();

			if ( $( event.currentTarget ).parent().hasClass( 'copy_download_file_url' ) ) {
				return;
			}

			$( event.currentTarget ).closest( '.bb-activity-media-elem' ).toggleClass( 'is-visible' ).siblings().removeClass( 'is-visible' ).closest( '.activity-item' ).siblings().find( '.bb-activity-media-elem' ).removeClass( 'is-visible' );

			if ( $( event.currentTarget ).closest( '.bb-activity-media-elem' ).length < 1 ) {
				$( event.currentTarget ).closest( '.bb-photo-thumb' ).toggleClass( 'is-visible' ).parent().siblings().find( '.bb-photo-thumb' ).removeClass( 'is-visible' ).removeClass( 'is-visible' );
			}

			if ( event.currentTarget.tagName.toLowerCase() == 'a' && ( !$( event.currentTarget ).hasClass( 'document-action_more' ) && !$( event.currentTarget ).hasClass( 'media-action_more' ) ) ) {
				$( event.currentTarget ).closest( '.bb-activity-media-elem' ).removeClass( 'is-visible' );
				$( event.currentTarget ).closest( '.bb-photo-thumb' ).removeClass( 'is-visible' );
			}
		},

		/**
		 * File Activity action Toggle
		 */
		toggleFileActivityActionButton: function ( event ) {
			var element;

			event = event || window.event;

			if ( event.target ) {
				element = event.target;
			} else if ( event.srcElement ) {
				element = event.srcElement;
			}

			if ( element.nodeType === 3 ) {
				element = element.parentNode;
			}

			if ( event.altKey === true || event.metaKey === true ) {
				return event;
			}

			if ( $( element ).hasClass( 'document-action_more' ) || $( element ).parent().hasClass( 'document-action_more' ) || $( element ).hasClass( 'media-folder_action__anchor' ) || $( element ).parent().hasClass( 'media-folder_action__anchor' ) || $( element ).hasClass( 'media-action_more' ) || $( element ).parent().hasClass( 'media-action_more' ) || $( element ).hasClass( 'video-action_more' ) || $( element ).parent().hasClass( 'video-action_more' ) ) {
				return event;
			}

			$( '.bb-activity-media-elem.is-visible' ).removeClass( 'is-visible' );
			$( '.media-folder_items.is-visible' ).removeClass( 'is-visible' );
			$( '.bb-photo-thumb.is-visible' ).removeClass( 'is-visible' );
			$( '.bb-item-thumb.is-visible' ).removeClass( 'is-visible' );
			$( '.bb-activity-video-elem.is-visible' ).removeClass( 'is-visible' );
			$( '.video-action-wrap.item-action-wrap.is-visible' ).removeClass( 'is-visible' );

		},

		/**
		 * Toggle Text File
		 */
		toggleCodePreview: function ( event ) {
			event.preventDefault();
			$( event.currentTarget ).closest( '.document-activity' ).toggleClass( 'code-full-view' );
		},

		/**
		 * Text File Activity Preview
		 */
		documentCodeMirror: function () {
			$( '.document-text:not(.loaded)' ).each(
				function () {
					var $this = $( this );
					var data_extension = $this.attr( 'data-extension' );
					var fileMode = $this.attr( 'data-extension' );
					if ( data_extension == 'html' || data_extension == 'htm' ) { // HTML file need specific mode.
						fileMode = 'text/html';
					}
					if ( data_extension == 'js' ) { // mode not needed for javascript file.
						/* jshint ignore:start */
						var myCodeMirror = CodeMirror(
							$this[ 0 ],
							{
								value: $this.find( '.document-text-file-data-hidden' ).val(),
								lineNumbers: true,
								theme: 'default',
								readOnly: true,
								lineWrapping: true,
							}
						);
						/* jshint ignore:end */
					} else {
						/* jshint ignore:start */
						var myCodeMirror = CodeMirror(
							$this[ 0 ],
							{
								value: $this.find( '.document-text-file-data-hidden' ).val(),
								mode: fileMode,
								lineNumbers: true,
								theme: 'default',
								readOnly: true,
								lineWrapping: true,
							}
						);
						/* jshint ignore:end */
					}

					$this.addClass( 'loaded' );
					if ( $this.parent().height() > 150 ) { // If file is bigger add controls to Expand/Collapse.
						$this.closest( '.document-text-wrap' ).addClass( 'is_large' );
						$this.closest( '.document-activity' ).addClass( 'is_large' );
					}

				}
			);
		},

		/**
		 * Close popup on ESC.
		 */
		closePopup: function ( event ) {
			//Close popup if it's open
			if ( event.keyCode == 27 ) {
				//Close Move popup
				$( '.bp-media-move-folder.open-popup .ac-folder-close-button:visible, .bp-media-move-file .ac-media-close-button:visible, .bp-media-move-folder.open-popup .close-create-popup-folder:visible,.bp-media-move-file.open-popup .ac-document-close-button:visible, .bp-media-move-file .close-create-popup-folder:visible, .bp-media-move-photo.open .close-create-popup-album:visible' ).trigger( 'click' );

				//Close create folder popup
				$( '#bp-media-create-folder #bp-media-create-folder-close:visible, #bp-media-create-child-folder #bp-media-create-folder-close:visible' ).trigger( 'click' );

				//Close document uploader popup
				$( '#bp-media-uploader #bp-media-uploader-close:visible' ).trigger( 'click' );

				//Close Edit Folder popup
				$( '#bp-media-edit-child-folder #bp-media-edit-folder-close:visible' ).trigger( 'click' );

				//Close create media album
				$( '#bp-media-create-album #bp-media-create-album-close:visible' ).trigger( 'click' );

				$( '.media-folder_visibility select#bb-folder-privacy:not(.hide)' ).each( function () {
					$( this ).attr( 'data-mouseup', 'false' ).addClass( 'hide' ).siblings( 'span' ).show().text( $( this ).find( 'option:selected' ).text() );
				} );

				//Close upload thumbnail popup
				$( '.bp-video-thumbnail-uploader .bp-video-thumbnail-uploader-close:visible').trigger( 'click' );

				// Close Action popup
				$( '.bb-action-popup .bb-close-action-popup:visible').trigger( 'click' );

			}
		},

		/**
		 * Submit popup on ENTER.
		 */
		submitPopup: function ( event ) {

			// return if modern is not visible.
			if ( $( document ).find( '.modal-wrapper:visible' ).length < 1 ) {
				return;
			}

			// Submit popup if it's open.
			if ( event.keyCode == 13 ) {
				// Submit Move popup.
				$( '.bp-media-move-folder.open-popup .bp-document-move:not(.is-disabled):visible, .bp-media-move-folder.open-popup  .bp-folder-move:not(.is-disabled):visible,.bp-media-move-file.open-popup .bp-document-move:not(.is-disabled):visible, .bp-media-move-file.open-popup .bp-document-create-popup-folder-submit:visible, .bp-media-move-folder.open-popup .bp-document-create-popup-folder-submit:visible, .bp-media-move-file.open .bp-media-move:not(.is-disabled):visible, .bp-media-move-file.open .bp-media-create-popup-album-submit:visible' ).trigger( 'click' );

				// Submit create folder popup.
				$( '#bp-media-create-folder #bp-media-create-folder-submit:visible, #bp-media-create-child-folder #bp-media-create-child-folder-submit:visible' ).trigger( 'click' );

				// Submit document uploader popup.
				$( '#bp-media-uploader #bp-media-document-submit:visible, #bp-media-uploader #bp-media-submit:visible' ).trigger( 'click' );

				// Submit Edit Folder popup.
				$( '#bp-media-edit-child-folder #bp-media-edit-child-folder-submit:visible' ).trigger( 'click' );

				// Submit create media album.
				$( '#bp-media-create-album #bp-media-create-album-submit:visible' ).trigger( 'click' );
			}
		}
	};

	/**
	 * [Media description]
	 *
	 * @type {Object}
	 */
	bp.Nouveau.Media.Theatre = {

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

			this.medias = [];
			this.documents = [];
			this.current_media = false;
			this.current_document = false;
			this.current_index = 0;
			this.current_document_index = 0;
			this.is_open_media = false;
			this.is_open_document = false;
			this.nextLink = $( '.bb-next-media' );
			this.nextDocumentLink = $( '.bb-next-document' );
			this.previousDocumentLink = $( '.bb-prev-document' );
			this.previousLink = $( '.bb-prev-media' );
			this.activity_ajax = false;
			this.group_id = typeof BP_Nouveau.media.group_id !== 'undefined' ? BP_Nouveau.media.group_id : false;
			this.manage_media = typeof BP_Nouveau.media.can_manage_media !== 'undefined' ? BP_Nouveau.media.can_manage_media : false;
			this.manage_document = typeof BP_Nouveau.media.can_manage_document !== 'undefined' ? BP_Nouveau.media.can_manage_document : false;
		},

		/**
		 * [addListeners description]
		 */
		addListeners: function () {

			$(document).on('click', '.bb-open-media-theatre', this.openTheatre.bind(this));
			$(document).on('click', '.bb-open-document-theatre', this.openDocumentTheatre.bind(this));
			$(document).on('click', '.document-detail-wrap-description-popup', this.openDocumentTheatre.bind(this));
			$(document).on('click', '.bb-close-media-theatre', this.closeTheatre.bind(this));
			$(document).on('click', '.bb-close-document-theatre', this.closeDocumentTheatre.bind(this));
			$(document).on('click', '.bb-prev-media', this.previous.bind(this));
			$(document).on('click', '.bb-next-media', this.next.bind(this));
			$(document).on('click', '.bb-prev-document', this.previousDocument.bind(this));
			$(document).on('click', '.bb-next-document', this.nextDocument.bind(this));
			$(document).on('bp_activity_ajax_delete_request', this.activityDeleted.bind(this));
			$(document).on('click', '#bb-media-model-container .media-privacy>li', this.mediaPrivacyChange.bind(this));
			$(document).on('click', '#bb-media-model-container .document-privacy>li', this.documentPrivacyChange.bind(this));
			$(document).on('click', '#bb-media-model-container .bb-media-section span.privacy', bp.Nouveau, this.togglePrivacyDropdown.bind(this));
			$(document).on('click', '#bb-media-model-container .bb-document-section span.privacy', bp.Nouveau, this.toggleDocumentPrivacyDropdown.bind(this));
			$(document).on('click', '.bp-add-media-activity-description', this.openMediaActivityDescription.bind(this));
			$(document).on('click', '#bp-activity-description-new-reset', this.closeMediaActivityDescription.bind(this));
			$(document).on('keyup', '.bp-edit-media-activity-description #add-activity-description', this.MediaActivityDescriptionUpdate.bind(this));
			$(document).on('click', '#bp-activity-description-new-submit', this.submitMediaActivityDescription.bind(this));
			$(document).click(this.togglePopupDropdown);

			document.addEventListener( 'keyup', this.checkPressedKeyDocuments.bind( this ) );
			document.addEventListener( 'keyup', this.checkPressedKey.bind( this ) );

		},

		checkPressedKey: function ( e ) {
			var self = this;
			e = e || window.event;

			if ( !self.is_open_media ) {
				return false;
			}

			var userIsEditing = $( e.target ).hasClass( 'ac-input' ) || $( e.target ).attr( 'id' ) === 'add-activity-description';

			switch ( e.keyCode ) {
				case 27: // escape key.
					self.closeTheatre( e );
					break;
				case 37: // left arrow key code.
					if ( typeof self.medias[ self.current_index - 1 ] === 'undefined' || userIsEditing ) {
						return false;
					}
					self.previous( e );
					break;
				case 39: // right arrow key code.
					if ( typeof self.medias[ self.current_index + 1 ] === 'undefined' || userIsEditing ) {
						return false;
					}
					self.next( e );
					break;
			}
		},

		checkPressedKeyDocuments: function ( e ) {
			e = e || window.event;
			var self = this;

			if ( !self.is_open_document ) {
				return false;
			}

			var userIsEditing = $( e.target ).hasClass( 'ac-input' ) || $( e.target ).attr( 'id' ) === 'add-activity-description';

			switch ( e.keyCode ) {
				case 27: // escape key.
					self.closeDocumentTheatre( e );
					break;
				case 37: // left arrow key code.
					if ( typeof self.documents[ self.current_document_index - 1 ] === 'undefined' || userIsEditing ) {
						return false;
					}
					self.previousDocument( e );
					break;
				case 39: // right arrow key code.
					if ( typeof self.documents[ self.current_document_index + 1 ] === 'undefined' || userIsEditing ) {
						return false;
					}
					self.nextDocument( e );
					break;
			}
		},

		openTheatre: function ( event ) {
			event.preventDefault();
			var target = $( event.currentTarget ), id, self = this;

			if ( target.closest( '#bp-existing-media-content' ).length ) {
				return false;
			}

			self.setupGlobals();
			self.setMedias( target );

			id = target.data( 'id' );
			self.setCurrentMedia( id );
			self.showMedia();
			self.navigationCommands();
			self.getParentActivityHtml( target );
			self.getMediasDescription();

			$( '.bb-media-model-wrapper.document' ).hide();
			var currentVideo =  document.getElementById( $( '.bb-media-model-wrapper.video video' ).attr('id') );
			if( currentVideo ) {
				currentVideo.pause();
			}
			$( '.bb-media-model-wrapper.video' ).hide();
			$( '.bb-media-model-wrapper.media' ).show();
			self.is_open_media = true;

			//document.addEventListener( 'keyup', self.checkPressedKey.bind( self ) );
		},

		getParentActivityHtml: function ( target ) {
			var parentActivityId         = $( '#hidden_parent_id' ).val();
			var parentActivityIdForModel = target.closest( '.bb-media-model-wrapper' ).find( '#bb-media-model-container .activity-list li.activity-item' ).data( 'bp-activity-id' );
			if ( parseInt( parentActivityId ) === parseInt( parentActivityIdForModel ) ) {
				var mainParentActivityData = $( '#bb-media-model-container [data-bp-activity-id="' + parentActivityId + '"]' );
				$( '[data-bp-activity-id="' + parentActivityId + '"] > .activity-state' ).html( $( mainParentActivityData ).find( '.activity-state' ).html() );
				$( '[data-bp-activity-id="' + parentActivityId + '"] > .activity-meta' ).html( $( mainParentActivityData ).find( '.activity-meta' ).html() );
				$( '[data-bp-activity-id="' + parentActivityId + '"] > .activity-comments' ).html( $( mainParentActivityData ).find( '.activity-comments' ).html() );
			}
			if ( $( '#hidden_parent_id' ).length ) {
				$( '#hidden_parent_id' ).remove();
			}
		},

		getMediasDescription: function () {
			var self = this;

			$( '.bb-media-info-section .activity-list' ).addClass( 'loading' ).html( '<i class="bb-icon-l bb-icon-spinner animate-spin"></i>' );

			if ( self.activity_ajax != false ) {
				self.activity_ajax.abort();
			}

			var on_page_activity_comments = $( '[data-bp-activity-id="' + self.current_media.activity_id + '"] .activity-comments' );
			if ( on_page_activity_comments.length ) {
				self.current_media.parent_activity_comments = true;
				on_page_activity_comments.html( '' );
			}

			if ( true === self.current_media.parent_activity_comments ) {
				$( '.bb-media-model-wrapper:last' ).after( '<input type="hidden" value="' + self.current_media.activity_id + '" id="hidden_parent_id"/>' );
			}

			self.activity_ajax = $.ajax(
				{
					type: 'POST',
					url: BP_Nouveau.ajaxurl,
					data: {
						action: 'media_get_media_description',
						id: self.current_media.id,
						attachment_id: self.current_media.attachment_id,
						nonce: BP_Nouveau.nonces.media
					},
					success: function ( response ) {
						if ( response.success ) {
							$( '.bb-media-info-section:visible .activity-list' ).removeClass( 'loading' ).html( response.data.description );
							$( '.bb-media-info-section:visible' ).show();
							$( window ).scroll();
							setTimeout(
								function () { // Waiting to load dummy image
									bp.Nouveau.reportPopUp();
									bp.Nouveau.reportedPopup();
								},
								10
							);
						} else {
							$( '.bb-media-info-section.media' ).hide();
						}
					}
				}
			);
		},

		openDocumentTheatre: function ( event ) {
			event.preventDefault();
			var target = $( event.currentTarget ), id, self = this;

			if ( target.closest( '#bp-existing-document-content' ).length ) {
				return false;
			}

			if ( target.closest( '.document.document-theatre' ).length ) {
				self.closeDocumentTheatre( event );
			}

			id = target.data( 'id' );
			self.setupGlobals();
			self.setDocuments( target );
			self.setCurrentDocument( id );
			self.showDocument();
			self.navigationDocumentCommands();
			self.getParentActivityHtml( target );
			self.getDocumentsDescription();

			//Stop audio if it is playing before opening theater
			if ( $.inArray( self.current_document.extension, BP_Nouveau.document.mp3_preview_extension.split( ',' ) ) !== -1 ) {
				if ( $( event.currentTarget ).closest( '.bb-activity-media-elem.document-activity' ).length && $( event.currentTarget ).closest( '.bb-activity-media-elem.document-activity' ).find( '.document-audio-wrap' ).length ) {
					$( event.currentTarget ).closest( '.bb-activity-media-elem.document-activity' ).find( '.document-audio-wrap audio' )[ 0 ].pause();
				}
			}

			$( '.bb-media-model-wrapper.media' ).hide();
			$( '.bb-media-model-wrapper.document' ).show();
			var currentVideo =  document.getElementById( $( '.bb-media-model-wrapper.video video' ).attr('id') );
			if( currentVideo ) {
				currentVideo.pause();
				currentVideo.src = '';
			}
			$( '.bb-media-model-wrapper.video' ).hide();
			self.is_open_document = true;
			//document.addEventListener( 'keyup', self.checkPressedKeyDocuments.bind( self ) );
		},

		resetRemoveActivityCommentsData: function () {
			var self = this, activity_comments = false, activity_meta = false, activity_state = false, activity = false,
				html = false, classes = false;
			if ( self.current_media.parent_activity_comments ) {
				activity = $( '.bb-media-model-wrapper.media [data-bp-activity-id="' + self.current_media.activity_id + '"]' );
				activity_comments = activity.find( '.activity-comments' );
				if ( activity_comments.length ) {
					html = activity_comments.html();
					classes = activity_comments.attr( 'class' );
					activity_comments.remove();
					activity_comments = $( '[data-bp-activity-id="' + self.current_media.activity_id + '"] .activity-comments' );
					if ( activity_comments.length ) {
						activity_comments.html( html );
						activity_comments.attr( 'class', classes );
						activity_comments.children( 'form' ).removeClass( 'events-initiated').hide();
					}
				}
				activity_state = activity.find( '.activity-state' );
				if ( activity_state.length ) {
					html = activity_state.html();
					classes = activity_state.attr( 'class' );
					activity_state.remove();
					activity_state = $( '[data-bp-activity-id="' + self.current_media.activity_id + '"] .activity-state' );
					if ( activity_state.length ) {
						activity_state.html( html );
						activity_state.attr( 'class', classes );
					}
				}
				activity_meta = activity.find( '.activity-meta' );
				if ( activity_meta.length ) {
					html = activity_meta.html();
					classes = activity_meta.attr( 'class' );
					activity_meta.remove();
					activity_meta = $( '[data-bp-activity-id="' + self.current_media.activity_id + '"] > .activity-meta' );
					if ( activity_meta.length ) {
						activity_meta.html( html );
						activity_meta.attr( 'class', classes );
					}
				}
				activity.remove();
			}
			if ( self.current_document.parent_activity_comments ) {
				activity = $( '.bb-media-model-wrapper.document [data-bp-activity-id="' + self.current_document.activity_id + '"]' );
				activity_comments = activity.find( '.activity-comments' );
				if ( activity_comments.length ) {
					html = activity_comments.html();
					classes = activity_comments.attr( 'class' );
					activity_comments.remove();
					activity_comments = $( '[data-bp-activity-id="' + self.current_document.activity_id + '"] .activity-comments' );
					if ( activity_comments.length ) {
						activity_comments.html( html );
						activity_comments.attr( 'class', classes );
						activity_comments.children( 'form' ).removeClass( 'events-initiated').hide();
						//Reset document text preview
						activity_comments.find( '.document-text.loaded' ).removeClass( 'loaded' ).find( '.CodeMirror' ).remove();
						jQuery( window ).scroll();
					}

				}
				activity_state = activity.find( '.activity-state' );
				if ( activity_state.length ) {
					html = activity_state.html();
					classes = activity_state.attr( 'class' );
					activity_state.remove();
					activity_state = $( '[data-bp-activity-id="' + self.current_document.activity_id + '"] .activity-state' );
					if ( activity_state.length ) {
						activity_state.html( html );
						activity_state.attr( 'class', classes );
					}
				}
				activity_meta = activity.find( '.activity-meta' );
				if ( activity_meta.length ) {
					html = activity_meta.html();
					classes = activity_meta.attr( 'class' );
					activity_meta.remove();
					activity_meta = $( '[data-bp-activity-id="' + self.current_document.activity_id + '"] > .activity-meta' );
					if ( activity_meta.length ) {
						activity_meta.html( html );
						activity_meta.attr( 'class', classes );
					}
				}
				activity.remove();
			}

			// Report content popup
			bp.Nouveau.reportPopUp();
			bp.Nouveau.reportActions();
		},

		closeTheatre: function ( event ) {
			event.preventDefault();
			var self   = this;
			var target = $( event.currentTarget );

			if ( $( target ).closest( '.bb-media-model-wrapper' ).hasClass( 'video-theatre' ) ) {
				return false;
			}

			$('.bb-media-model-wrapper.media .bb-media-section').find('img').attr('src', '');
			if( $('.bb-media-model-wrapper.video .bb-media-section').find('video').length ){
				videojs( $('.bb-media-model-wrapper.video .bb-media-section').find('video').attr('id') ).reset();
			}
			$('.bb-media-model-wrapper').hide();
			self.is_open_media = false;

			self.resetRemoveActivityCommentsData();

			self.current_media = false;
			self.getParentActivityHtml( target );
		},

		closeDocumentTheatre: function ( event ) {
			event.preventDefault();
			var self = this;
			var document_elements = $( document ).find( '.document-theatre' );
			document_elements.find( '.bb-media-section' ).removeClass( 'bb-media-no-preview' ).find( '.document-preview' ).html( '' );
			$( '.bb-media-info-section.document' ).show();
			document_elements.hide();
			self.is_open_document = false;

			self.resetRemoveActivityCommentsData();

			self.current_document = false;
			self.getParentActivityHtml( $( event.currentTarget ) );
		},

		setMedias: function ( target ) {
			var media_elements = $( '.bb-open-media-theatre' ), i = 0, self = this;

			// check if on activity page, load only activity media in theatre.
			if ( $( 'body' ).hasClass( 'activity' ) ) {
				media_elements = $( target ).closest( '.bb-activity-media-wrap' ).find( '.bb-open-media-theatre' );
			}

			if ( typeof media_elements !== 'undefined' ) {
				self.medias = [];
				for ( i = 0; i < media_elements.length; i++ ) {
					var media_element = $( media_elements[ i ] );
					if ( !media_element.closest( '#bp-existing-media-content' ).length ) {

						var m = {
							id                : media_element.data('id'),
							attachment        : media_element.data('attachment-full'),
							activity_id       : media_element.data('activity-id'),
							attachment_id     : media_element.data('attachment-id'),
							privacy           : media_element.data('privacy'),
							parent_activity_id: media_element.data('parent-activity-id'),
							album_id          : media_element.data('album-id'),
							group_id          : media_element.data('group-id'),
							can_edit          : media_element.data('can-edit'),
							is_forum          : false
						};

						if ( media_element.closest( '.forums-media-wrap' ).length ) {
							m.is_forum = true;
						}

						if ( typeof m.privacy !== 'undefined' && m.privacy == 'message' ) {
							m.is_message = true;
						} else {
							m.is_message = false;
						}

						self.medias.push( m );
					}
				}
			}
		},

		setDocuments: function ( target ) {
			var document_elements = $( '.bb-open-document-theatre' ), d = 0, self = this;

			// check if on activity page, load only activity media in theatre.
			if ( $( target ).closest( '.bp-search-ac-header' ).length ) {
				document_elements = $( target ).closest( '.bp-search-ac-header' ).find( '.bb-open-document-theatre' );
			} else if ( $( 'body' ).hasClass( 'activity' ) && $( target ).closest( '.search-document-list' ).length === 0 ) {
				document_elements = $( target ).closest( '.bb-activity-media-wrap' ).find( '.bb-open-document-theatre' );
			}

			if ( typeof document_elements !== 'undefined' ) {
				self.documents = [];
				for ( d = 0; d < document_elements.length; d++ ) {
					var document_element = $( document_elements[ d ] );
					if ( !document_elements.closest( '#bp-existing-document-content' ).length ) {
						var a = {
							id                : document_element.data('id'),
							attachment        : document_element.data('attachment-full'),
							activity_id       : document_element.data('activity-id'),
							attachment_id     : document_element.data('attachment-id'),
							privacy           : document_element.data('privacy'),
							parent_activity_id: document_element.data('parent-activity-id'),
							album_id          : document_element.data('album-id'),
							group_id          : document_element.data('group-id'),
							extension         : document_element.data('extension'),
							target_text       : document_element.data('document-title'),
							preview           : document_element.data('preview'),
							full_preview      : document_element.data('full-preview'),
							text_preview      : document_element.data('text-preview'),
							mirror_text       : document_element.data('mirror-text'),
							target_icon_class : document_element.data('icon-class'),
							author            : document_element.data('author'),
							download          : document_element.attr('href'),
							mp3               : document_element.data('mp3-preview'),
							can_edit          : document_element.data('can-edit'),
							video             : document_element.attr( 'data-video-preview' ),
							is_forum          : false
						};

						if ( document_element.closest( '.forums-media-wrap' ).length ) {
							a.is_forum = true;
						}

						if ( typeof a.privacy !== 'undefined' && a.privacy == 'message' ) {
							a.is_message = true;
						} else {
							a.is_message = false;
						}

						self.documents.push( a );
					}
				}
			}
		},

		setCurrentMedia: function ( id ) {
			var self = this, i = 0;
			for ( i = 0; i < self.medias.length; i++ ) {
				if ( id === self.medias[ i ].id ) {
					self.current_media = self.medias[ i ];
					self.current_index = i;
					break;
				}
			}
		},

		setCurrentDocument: function ( id ) {
			var self = this, d = 0;
			for ( d = 0; d < self.documents.length; d++ ) {
				if ( id === self.documents[ d ].id ) {
					self.current_document = self.documents[ d ];
					self.current_document_index = d;
					break;
				}
			}
		},

		showMedia: function () {
			var self = this;

			if ( typeof self.current_media === 'undefined' ) {
				return false;
			}

			// refresh img.
			$( '.bb-media-model-wrapper.media .bb-media-section' ).find( 'img' ).attr( 'src', self.current_media.attachment );

			// privacy.
			var media_privacy_wrap = $( '.bb-media-section .bb-media-privacy-wrap' );

			if ( media_privacy_wrap.length ) {
				media_privacy_wrap.show();
				media_privacy_wrap.find( 'ul.media-privacy li' ).removeClass( 'selected' );
				media_privacy_wrap.find( '.bp-tooltip' ).attr( 'data-bp-tooltip', '' );
				var selected_media_privacy_elem = media_privacy_wrap.find( 'ul.media-privacy' ).find( 'li[data-value=' + self.current_media.privacy + ']' );
				selected_media_privacy_elem.addClass( 'selected' );
				media_privacy_wrap.find( '.bp-tooltip' ).attr( 'data-bp-tooltip', selected_media_privacy_elem.text() );
				media_privacy_wrap.find( '.privacy' ).removeClass( 'public' ).removeClass( 'loggedin' ).removeClass( 'onlyme' ).removeClass( 'friends' ).addClass( self.current_media.privacy );

				// hide privacy setting of media if activity is present.
				if ( ( typeof BP_Nouveau.activity !== 'undefined' &&
					typeof self.current_media.activity_id !== 'undefined' &&
					self.current_media.activity_id != 0  ) ||
					self.group_id ||
					self.current_media.is_forum ||
					self.current_media.group_id ||
					self.current_media.album_id ||
					self.current_media.is_message ||
					! self.can_manage_media ||
					! self.current_media.can_edit
				) {
					media_privacy_wrap.hide();
				}
			}

			// update navigation.
			self.navigationCommands();
		},

		showDocument: function () {
			var self = this;

			if ( typeof self.current_document === 'undefined' ) {
				return false;
			}
			var target_text = self.current_document.target_text;
			var target_icon_class = self.current_document.target_icon_class;
			var document_elements = $( document ).find( '.document-theatre' );
			var extension = self.current_document.extension;
			var mirror_text_display = self.current_document.mirror_text;
			document_elements.find( '.bb-document-section' ).removeClass( 'bb-video-preview' );

			if ( $.inArray( self.current_document.extension, [ 'css', 'txt', 'js', 'html', 'htm', 'csv' ] ) !== -1 ) {
				document_elements.find( '.bb-document-section .document-preview' ).html( '<i class="bb-icon-l bb-icon-spinner animate-spin"></i>' );
				document_elements.find( '.bb-document-section' ).removeClass( 'bb-media-no-preview' );
				document_elements.find( '.bb-document-section .document-preview' ).html( '' );
				document_elements.find( '.bb-document-section .document-preview' ).html( '<h3>' + target_text + '</h3><div class="document-text"><textarea class="document-text-file-data-hidden"></textarea></div>' );
				document_elements.find( '.bb-document-section .document-preview .document-text' ).attr( 'data-extension', extension );
				document_elements.find( '.bb-document-section .document-preview .document-text textarea' ).html( mirror_text_display );

				setTimeout( function () {
					bp.Nouveau.Media.documentCodeMirror();
				}, 1000 );
			} else if ( $.inArray( self.current_document.extension, BP_Nouveau.document.mp3_preview_extension.split( ',' ) ) !== -1 ) {
				document_elements.find( '.bb-document-section .document-preview' ).html( '<i class="bb-icon-l bb-icon-spinner animate-spin"></i>' );
				document_elements.find( '.bb-document-section' ).removeClass( 'bb-media-no-preview' );
				document_elements.find( '.bb-document-section .document-preview' ).html( '' );
				document_elements.find( '.bb-document-section .document-preview' ).html( '<div class="img-section"><h3>' + target_text + '</h3><div class="document-audio"><audio src="' + self.current_document.mp3 + '" controls controlsList="nodownload"></audio></div></div>' );
			} else if ( $.inArray( '.' + self.current_document.extension, BP_Nouveau.video.video_type.split( ',' ) ) !== -1 ) {
				document_elements.find( '.bb-document-section' ).addClass( 'bb-video-preview' );
				document_elements.find( '.bb-document-section .document-preview' ).html( '<i class="bb-icon-l bb-icon-spinner animate-spin"></i>' );
				document_elements.find( '.bb-document-section' ).removeClass( 'bb-media-no-preview' );
				document_elements.find( '.bb-document-section .document-preview' ).html( '' );
				if ( 'mov' === self.current_document.extension || 'm4v' === self.current_document.extension ) {
					document_elements.find( '.bb-document-section .document-preview' ).html( '<video playsinline id="video-'+self.current_document.id+'" class="video-js video-loading" controls  data-setup=\'{"aspectRatio": "16:9", "fluid": true,"playbackRates": [0.5, 1, 1.5, 2] }\' ><source src="' + self.current_document.video + '" type="video/mp4" ></source></video><span class="video-loader"><i class="bb-icon-l bb-icon-spinner animate-spin"></i></span>' );
				} else {
					document_elements.find( '.bb-document-section .document-preview' ).html( '<video playsinline id="video-'+self.current_document.id+'" class="video-js video-loading" controls  data-setup=\'{"aspectRatio": "16:9", "fluid": true,"playbackRates": [0.5, 1, 1.5, 2] }\' ><source src="' + self.current_document.video + '" type="video/' + self.current_document.extension + '" ></source></video><span class="video-loader"><i class="bb-icon-l bb-icon-spinner animate-spin"></i></span>' );
				}

				//fake scroll event to call video bp.Nouveau.Video.Player.openPlayer();
				$( window ).scroll();

			} else {
				if ( self.current_document.full_preview ) {
					document_elements.find( '.bb-document-section' ).removeClass( 'bb-media-no-preview' );
					document_elements.find( '.bb-document-section .document-preview' ).html( '' );
					document_elements.find( '.bb-document-section .document-preview' ).html( '<h3>' + target_text + '</h3><div class="img-section"><div class="img-block-wrap"> <img src="' + self.current_document.full_preview + '" /></div></div>' );
				} else {
					document_elements.find( '.bb-document-section' ).addClass( 'bb-media-no-preview' );
					document_elements.find( '.bb-document-section .document-preview' ).html( '' );
					document_elements.find( '.bb-document-section .document-preview' ).html( '<div class="img-section"> <i class="' + target_icon_class + '"></i><p>' + target_text + '</p></div>' );
				}
			}

			// privacy.
			var document_privacy_wrap = $('.bb-document-section .bb-document-privacy-wrap');

			if ( document_privacy_wrap.length ) {
				document_privacy_wrap.show();
				document_privacy_wrap.parent().show();
				document_privacy_wrap.find('ul.document-privacy li').removeClass('selected');
				document_privacy_wrap.find('.bp-tooltip').attr('data-bp-tooltip', '');
				var selected_document_privacy_elem = document_privacy_wrap.find('ul.document-privacy').find('li[data-value=' + self.current_document.privacy + ']');
				selected_document_privacy_elem.addClass('selected');
				document_privacy_wrap.find('.bp-tooltip').attr('data-bp-tooltip', selected_document_privacy_elem.text());
				document_privacy_wrap.find('.privacy').removeClass('public').removeClass('loggedin').removeClass('onlyme').removeClass('friends').addClass(self.current_document.privacy);

				// hide privacy setting of media if activity is present.
				if ( ( typeof BP_Nouveau.activity !== 'undefined' &&
					typeof self.current_document.activity_id !== 'undefined' &&
					self.current_document.activity_id != 0 ) ||
					self.group_id ||
					self.current_document.is_forum ||
					self.current_document.group_id ||
					self.current_document.album_id ||
					! self.can_manage_document ||
					! self.current_document.can_edit ||
					self.current_document.is_message
				) {
					document_privacy_wrap.parent().hide();
				}
			}

			// update navigation.
			self.navigationDocumentCommands();
		},

		next: function ( event ) {
			event.preventDefault();
			var self = this, activity_id;
			self.resetRemoveActivityCommentsData();
			if ( typeof self.medias[ self.current_index + 1 ] !== 'undefined' ) {
				self.current_index = self.current_index + 1;
				activity_id = self.current_media.activity_id;
				self.current_media = self.medias[ self.current_index ];
				self.showMedia();
				self.getMediasDescription();
			} else {
				self.nextLink.hide();
			}
		},

		previous: function ( event ) {
			event.preventDefault();
			var self = this, activity_id;
			self.resetRemoveActivityCommentsData();
			if ( typeof self.medias[ self.current_index - 1 ] !== 'undefined' ) {
				self.current_index = self.current_index - 1;
				activity_id = self.current_media.activity_id;
				self.current_media = self.medias[ self.current_index ];
				self.showMedia();
				self.getMediasDescription();
			} else {
				self.previousLink.hide();
			}
		},

		nextDocument: function ( event ) {
			event.preventDefault();

			var self = this, activity_id;
			self.resetRemoveActivityCommentsData();
			if ( typeof self.documents[ self.current_document_index + 1 ] !== 'undefined' ) {
				self.current_document_index = self.current_document_index + 1;
				activity_id = self.current_document.activity_id;
				self.current_document = self.documents[ self.current_document_index ];
				self.showDocument();
				self.getDocumentsDescription();
			} else {
				self.nextDocumentLink.hide();
			}
		},

		previousDocument: function ( event ) {
			event.preventDefault();
			var self = this, activity_id;
			self.resetRemoveActivityCommentsData();
			if ( typeof self.documents[ self.current_document_index - 1 ] !== 'undefined' ) {
				self.current_document_index = self.current_document_index - 1;
				activity_id = self.current_document.activity_id;
				self.current_document = self.documents[ self.current_document_index ];
				self.showDocument();
				self.getDocumentsDescription();
			} else {
				self.previousDocumentLink.hide();
			}
		},

		navigationCommands: function () {
			var self = this;
			if ( self.current_index == 0 && self.current_index != ( self.medias.length - 1 ) ) {
				self.previousLink.hide();
				self.nextLink.show();
			} else if ( self.current_index == 0 && self.current_index == ( self.medias.length - 1 ) ) {
				self.previousLink.hide();
				self.nextLink.hide();
			} else if ( self.current_index == ( self.medias.length - 1 ) ) {
				self.previousLink.show();
				self.nextLink.hide();
			} else {
				self.previousLink.show();
				self.nextLink.show();
			}
		},

		navigationDocumentCommands: function () {
			var self = this;
			if ( self.current_document_index == 0 && self.current_document_index != ( self.documents.length - 1 ) ) {
				self.previousDocumentLink.hide();
				self.nextDocumentLink.show();
			} else if ( self.current_document_index == 0 && self.current_document_index == ( self.documents.length - 1 ) ) {
				self.previousDocumentLink.hide();
				self.nextDocumentLink.hide();
			} else if ( self.current_document_index == ( self.documents.length - 1 ) ) {
				self.previousDocumentLink.show();
				self.nextDocumentLink.hide();
			} else {
				self.previousDocumentLink.show();
				self.nextDocumentLink.show();
			}
		},

		getActivity: function () {
			var self = this;

			$( '.bb-media-info-section .activity-list' ).addClass( 'loading' ).html( '<i class="bb-icon-l bb-icon-spinner"></i>' );

			if ( typeof BP_Nouveau.activity !== 'undefined' &&
				self.current_media &&
				typeof self.current_media.activity_id !== 'undefined' &&
				self.current_media.activity_id != 0 &&
				!self.current_media.is_forum
			) {

				if ( self.activity_ajax != false ) {
					self.activity_ajax.abort();
				}

				var on_page_activity_comments = $( '[data-bp-activity-id="' + self.current_media.activity_id + '"] .activity-comments' );
				if ( on_page_activity_comments.length ) {
					self.current_media.parent_activity_comments = true;
					on_page_activity_comments.html( '' );
				}
				if ( true === self.current_media.parent_activity_comments ) {
					$( '.bb-media-model-wrapper:last' ).after( '<input type="hidden" value="' + self.current_media.activity_id + '" id="hidden_parent_id"/>' );
				}

				self.activity_ajax = $.ajax(
					{
						type: 'POST',
						url: BP_Nouveau.ajaxurl,
						data: {
							action: 'media_get_activity',
							id: self.current_media.activity_id,
							group_id: !_.isUndefined( self.current_media.group_id ) ? self.current_media.group_id : 0,
							nonce: BP_Nouveau.nonces.media
						},
						success: function ( response ) {
							if ( response.success ) {
								$( '.bb-media-info-section:visible .activity-list' ).removeClass( 'loading' ).html( response.data.activity );
								$( '.bb-media-info-section:visible' ).show();

								jQuery(window).scroll();
								setTimeout(
									function () { // Waiting to load dummy image
										bp.Nouveau.reportPopUp();
										bp.Nouveau.reportedPopup();
									},
									10
								);
							}
						}
					}
				);
			} else {
				$( '.bb-media-info-section.media' ).hide();
			}
		},

		getDocumentsActivity: function () {
			var self = this;

			$( '.bb-media-info-section .activity-list' ).addClass( 'loading' ).html( '<i class="bb-icon-l bb-icon-spinner animate-spin"></i>' );

			if ( typeof BP_Nouveau.activity !== 'undefined' &&
				self.current_document &&
				typeof self.current_document.activity_id !== 'undefined' &&
				self.current_document.activity_id != 0 &&
				!self.current_document.is_forum
			) {

				if ( self.activity_ajax != false ) {
					self.activity_ajax.abort();
				}

				var on_page_activity_comments = $( '[data-bp-activity-id="' + self.current_document.activity_id + '"] .activity-comments' );
				if ( on_page_activity_comments.length ) {
					self.current_document.parent_activity_comments = true;
					on_page_activity_comments.html( '' );
				}
				if ( true === self.current_document.parent_activity_comments ) {
					$( '.bb-media-model-wrapper:last' ).after( '<input type="hidden" value="' + self.current_document.activity_id + '" id="hidden_parent_id"/>' );
				}

				self.activity_ajax = $.ajax(
					{
						type: 'POST',
						url: BP_Nouveau.ajaxurl,
						data: {
							action: 'document_get_activity',
							id: self.current_document.activity_id,
							group_id: !_.isUndefined( self.current_document.group_id ) ? self.current_document.group_id : 0,
							nonce: BP_Nouveau.nonces.media
						},
						success: function ( response ) {
							if ( response.success ) {
								$( '.bb-media-info-section:visible .activity-list' ).removeClass( 'loading' ).html( response.data.activity );
								$( '.bb-media-info-section:visible' ).show();

								jQuery(window).scroll();
								setTimeout(
									function () { // Waiting to load dummy image
										bp.Nouveau.reportPopUp();
										bp.Nouveau.reportedPopup();
									},
									10
								);
							}
						}
					}
				);
			} else {
				$( '.bb-media-info-section.document' ).hide();
			}
		},

		getDocumentsDescription: function () {
			var self = this;

			$( '.bb-media-info-section .activity-list' ).addClass( 'loading' ).html( '<i class="bb-icon-l bb-icon-spinner animate-spin"></i>' );

			if ( self.activity_ajax != false ) {
				self.activity_ajax.abort();
			}

			var on_page_activity_comments = $( '[data-bp-activity-id="' + self.current_document.activity_id + '"] .activity-comments' );
			if ( on_page_activity_comments.length ) {
				self.current_document.parent_activity_comments = true;
				on_page_activity_comments.html( '' );
			}

			if ( true === self.current_document.parent_activity_comments ) {
				$( '.bb-media-model-wrapper:last' ).after( '<input type="hidden" value="' + self.current_document.activity_id + '" id="hidden_parent_id"/>' );
			}

			self.activity_ajax = $.ajax(
				{
					type: 'POST',
					url: BP_Nouveau.ajaxurl,
					data: {
						action: 'document_get_document_description',
						id: self.current_document.id,
						attachment_id: self.current_document.attachment_id,
						nonce: BP_Nouveau.nonces.media
					},
					success: function ( response ) {
						if ( response.success ) {
							$( '.bb-media-info-section:visible .activity-list' ).removeClass( 'loading' ).html( response.data.description );
							$( '.bb-media-info-section:visible' ).show();
							$( window ).scroll();
							setTimeout(
								function () { // Waiting to load dummy image
									bp.Nouveau.reportPopUp();
									bp.Nouveau.reportedPopup();
								},
								10
							);
						} else {
							$( '.bb-media-info-section.document' ).hide();
						}
					}
				}
			);
		},

		activityDeleted: function ( event, data ) {
			var self = this, i = 0;
			if ( self.is_open_media && typeof data !== 'undefined' && data.action === 'delete_activity' && self.current_media.activity_id == data.id ) {

				var $deleted_item = $( document ).find( '[data-bp-list="media"] .bb-open-media-theatre[data-id="' + self.current_media.id + '"]' );
				var $deleted_item_parent_list = $deleted_item.parents( 'ul' );

				$deleted_item.closest( 'li' ).remove();

				if ( 0 === $deleted_item_parent_list.find( 'li:not(.load-more)' ).length ) {

					// No item.
					if ( $( '.bb-photos-actions' ).length > 0 ) {
						$( '.bb-photos-actions' ).hide();
					}

					if ( 1 === $deleted_item_parent_list.find( 'li.load-more' ).length ) {
						location.reload();
					}
				}
				$( document ).find( '[data-bp-list="activity"] .bb-open-media-theatre[data-id="' + self.current_media.id + '"]' ).closest( '.bb-activity-media-elem' ).remove();

				for ( i = 0; i < self.medias.length; i++ ) {
					if ( self.medias[ i ].activity_id == data.id ) {
						self.medias.splice( i, 1 );
						break;
					}
				}

				if ( self.current_index == 0 && self.current_index != ( self.medias.length ) ) {
					self.current_index = -1;
					self.next( event );
				} else if ( self.current_index == 0 && self.current_index == ( self.medias.length ) ) {
					$( document ).find( '[data-bp-list="activity"] li.activity-item[data-bp-activity-id="' + self.current_media.activity_id + '"]' ).remove();
					self.closeTheatre( event );
				} else if ( self.current_index == ( self.medias.length ) ) {
					self.previous( event );
				} else {
					self.current_index = -1;
					self.next( event );
				}
			}
			if ( self.is_open_document && typeof data !== 'undefined' && data.action === 'delete_activity' && self.current_document.activity_id == data.id ) {

				$( document ).find( '[data-bp-list="document"] .bb-open-document-theatre[data-id="' + self.current_document.id + '"]' ).closest( 'div.ac-document-list[data-activity-id="' + self.current_document.activity_id + '"]' ).remove();
				$( document ).find( '[data-bp-list="activity"] .bb-open-document-theatre[data-id="' + self.current_document.id + '"]' ).closest( '.bb-activity-media-elem' ).remove();

				for ( i = 0; i < self.documents.length; i++ ) {
					if ( self.documents[ i ].activity_id == data.id ) {
						self.documents.splice( i, 1 );
						break;
					}
				}

				if ( self.current_document_index == 0 && self.current_document_index != ( self.documents.length ) ) {
					self.current_document_index = -1;
					self.nextDocument( event );
				} else if ( self.current_document_index == 0 && self.current_document_index == ( self.documents.length ) ) {
					$( document ).find( '[data-bp-list="activity"] li.activity-item[data-bp-activity-id="' + self.current_document.activity_id + '"]' ).remove();
					self.closeDocumentTheatre( event );
				} else if ( self.current_document_index == ( self.documents.length ) ) {
					self.previousDocument( event );
				} else {
					self.current_document_index = -1;
					self.nextDocument( event );
				}
			}
		},

		/**
		 * [togglePopupDropdown description]
		 *
		 * @param  {[type]} event [description]
		 * @return {[type]}       [description]
		 */
		togglePopupDropdown: function ( event ) {
			var element;

			event = event || window.event;

			if ( event.target ) {
				element = event.target;
			} else if ( event.srcElement ) {
				element = event.srcElement;
			}

			if ( element.nodeType === 3 ) {
				element = element.parentNode;
			}

			if ( event.altKey === true || event.metaKey === true ) {
				return event;
			}

			if ( $( element ).hasClass( 'privacy-wrap' ) || $( element ).parent().hasClass( 'privacy-wrap' ) ) {
				return event;
			}

			$('ul.media-privacy').removeClass('bb-open');
			$('ul.document-privacy').removeClass('bb-open');
		},

		togglePrivacyDropdown: function ( event ) {
			var target = $( event.target );

			// Stop event propagation.
			event.preventDefault();

			target.closest( '.bb-media-privacy-wrap' ).find( '.media-privacy' ).toggleClass( 'bb-open' );
		},

		mediaPrivacyChange: function ( event ) {
			var target = $( event.target ), self = this, privacy = target.data( 'value' ), older_privacy = 'public';

			event.preventDefault();

			if ( target.hasClass( 'selected' ) ) {
				return false;
			}

			target.closest( '.bb-media-privacy-wrap' ).find( '.privacy' ).addClass( 'loading' );
			older_privacy = target.closest( '.bb-media-privacy-wrap' ).find( 'ul.media-privacy li.selected' ).data( 'value' );
			target.closest( '.bb-media-privacy-wrap' ).find( 'ul.media-privacy li' ).removeClass( 'selected' );
			target.addClass( 'selected' );

			$.ajax(
				{
					type: 'POST',
					url: BP_Nouveau.ajaxurl,
					data: {
						action: 'media_update_privacy',
						id: self.current_media.id,
						_wpnonce: BP_Nouveau.nonces.media,
						privacy: privacy,
					},
					success: function () {
						target.closest( '.bb-media-privacy-wrap' ).find( '.privacy' ).removeClass( 'loading' ).removeClass( older_privacy );
						target.closest( '.bb-media-privacy-wrap' ).find( '.privacy' ).addClass( privacy );
						target.closest( '.bb-media-privacy-wrap' ).find( '.bp-tooltip' ).attr( 'data-bp-tooltip', target.text() );
					},
					error: function () {
						target.closest( '.bb-media-privacy-wrap' ).find( '.privacy' ).removeClass( 'loading' );
					}
				}
			);
		},

		openMediaActivityDescription: function ( event ) {
			event.preventDefault();
			var target = $( event.currentTarget );

			if ( target.parents( '.activity-media-description' ).find( '.bp-edit-media-activity-description' ).length < 1 ) {
				return false;
			}

			target.parents( '.activity-media-description' ).find( '.bp-edit-media-activity-description' ).show().addClass( 'open' );
			target.parents( '.activity-media-description' ).find( '.bp-media-activity-description' ).hide();
			target.hide();
		},

		closeMediaActivityDescription: function ( event ) {
			event.preventDefault();
			var target = $( event.currentTarget );

			if ( target.parents( '.activity-media-description' ).length < 1 ) {
				return false;
			}

			var default_value = target.parents( '.activity-media-description' ).find( '#add-activity-description' ).get( 0 ).defaultValue;

			target.parents( '.activity-media-description' ).find( '.bp-add-media-activity-description' ).show();
			target.parents( '.activity-media-description' ).find( '.bp-media-activity-description' ).show();
			target.parents( '.activity-media-description' ).find( '#add-activity-description' ).val( default_value );
			target.parents( '.activity-media-description' ).find( '.bp-edit-media-activity-description' ).hide().removeClass( 'open' );
		},

		MediaActivityDescriptionUpdate: function( event ) {
			if( $( event.currentTarget ).val().trim() !== '' ) {
				$( event.currentTarget ).closest( '.bp-edit-media-activity-description' ).addClass( 'has-content' );
			} else {
				$( event.currentTarget ).closest( '.bp-edit-media-activity-description' ).removeClass( 'has-content' );
			}
		},

		submitMediaActivityDescription: function ( event ) {
			event.preventDefault();

			var target = $( event.currentTarget ),
				parent_wrap = target.parents( '.activity-media-description' ),
				description = parent_wrap.find( '#add-activity-description' ).val(),
				attachment_id = parent_wrap.find( '#bp-attachment-id' ).val();

			var data = {
				'action': 'media_description_save',
				'description': description,
				'attachment_id': attachment_id,
				'_wpnonce': BP_Nouveau.nonces.media,
			};

			$.ajax(
				{
					type: 'POST',
					url: BP_Nouveau.ajaxurl,
					data: data,
					async: false,
					success: function ( response ) {
						if ( response.success ) {
							target.parents( '.activity-media-description' ).find( '.bp-media-activity-description' ).html( response.data.description ).show();
							target.parents( '.activity-media-description' ).find( '.bp-add-media-activity-description' ).show();
							parent_wrap.find( '#add-activity-description' ).val( response.data.description );
							parent_wrap.find( '#add-activity-description' ).get( 0 ).defaultValue = response.data.description;
							if ( response.data.description == '' ) {
								target.parents( '.activity-media-description' ).find( '.bp-add-media-activity-description' ).removeClass( 'show-edit' ).addClass( 'show-add' );
							} else {
								target.parents( '.activity-media-description' ).find( '.bp-add-media-activity-description' ).addClass( 'show-edit' ).removeClass( 'show-add' );
							}

							target.parents( '.activity-media-description' ).find( '.bp-edit-media-activity-description' ).hide().removeClass( 'open' );
							target.parents( '.activity-media-description' ).find( '.bp-media-activity-description' ).show();
							target.parents( '.activity-media-description' ).find( '.bp-feedback.error' ).remove();
						} else {
							target.parents( '.activity-media-description' ).prepend( response.data.feedback );
						}
					}
				}
			);
		},

		toggleDocumentPrivacyDropdown: function (event) {
			var target = $(event.target);

			// Stop event propagation.
			event.preventDefault();

			target.closest('.bb-document-privacy-wrap').find('.document-privacy').toggleClass('bb-open');
		},

		documentPrivacyChange: function (event) {
			var target = $(event.target), self = this, privacy = target.data('value'), older_privacy = 'public';

			event.preventDefault();

			if (target.hasClass('selected')) {
				return false;
			}

			target.closest('.bb-document-privacy-wrap').find('.privacy').addClass('loading');
			older_privacy = target.closest('.bb-document-privacy-wrap').find('ul.document-privacy li.selected').data('value');
			target.closest('.bb-document-privacy-wrap').find('ul.document-privacy li').removeClass('selected');
			target.addClass('selected');

			$.ajax(
				{
					type   : 'POST',
					url    : BP_Nouveau.ajaxurl,
					data   : {
						action  : 'document_save_privacy',
						item_id : self.current_document.id,
						_wpnonce: BP_Nouveau.nonces.media,
						value   : privacy,
						type    : 'document',
					},
					success: function () {
						target.closest('.bb-document-privacy-wrap').find('.privacy').removeClass('loading').removeClass(older_privacy);
						target.closest('.bb-document-privacy-wrap').find('.privacy').addClass(privacy);
						target.closest('.bb-document-privacy-wrap').find('.bp-tooltip').attr('data-bp-tooltip', target.text());
					},
					error  : function () {
						target.closest('.bb-document-privacy-wrap').find('.privacy').removeClass('loading');
					}
				}
			);
		},
	};

	// Launch BP Nouveau Media.
	bp.Nouveau.Media.start();

	// Launch BP Nouveau Media Theatre.
	bp.Nouveau.Media.Theatre.start();

} )( bp, jQuery );
