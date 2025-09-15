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

	var bpNouveauLocal        = BP_Nouveau,
		bbRlMedia             = bpNouveauLocal.media,
		bbRlDocument          = bpNouveauLocal.document,
		bbRlAjaxUrl           = bpNouveauLocal.ajaxurl,
		bbRlVideo             = bpNouveauLocal.video,
		bbRlIsSendAjaxRequest = bpNouveauLocal.is_send_ajax_request,
		bbRlNonce             = bpNouveauLocal.nonces,
		bbRlActivity          = bpNouveauLocal.activity,
		forumMedia            = typeof window.BP_Forums_Nouveau !== 'undefined' && typeof window.BP_Forums_Nouveau.media ? window.BP_Forums_Nouveau.media : {};

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
			this.current_page                = 1;
			this.current_page_existing_media = 1;
			this.current_page_albums         = 1;
			this.current_tab                 = bodySelector.hasClass( 'single-topic' ) || bodySelector.hasClass( 'single-forum' ) ? false : 'bp-dropzone-content';
			this.sort_by                     = '';
			this.order_by                    = '';
			this.currentTargetParent         = bbRlMedia.current_folder;
			this.moveToIdPopup               = bbRlMedia.move_to_id_popup;
			this.moveToTypePopup             = bbRlMedia.current_type;
			this.privacySelectorSelect       = '';
			this.privacySelectorSpan         = '';

			// set up dropzones auto discover to false so it does not automatically set dropzones.
			if ( typeof window.Dropzone !== 'undefined' ) {
				window.Dropzone.autoDiscover = false;
			}

			var ForumDocumentTemplates = document.getElementsByClassName( 'forum-post-document-template' ).length ? document.getElementsByClassName( 'forum-post-document-template' )[0].innerHTML : ''; // Check to avoid error if Node is missing.

			this.documentOptions = bp.Readylaunch.Utilities.createDropzoneOptions(
				{
					dictFileTooBig               : bbRlMedia.dictFileTooBig,
					acceptedFiles                : bbRlMedia.document_type,
					createImageThumbnails        : false,
					dictDefaultMessage           : bbRlMedia.dropzone_document_message,
					maxFiles                     : typeof bbRlDocument.maxFiles !== 'undefined' ? bbRlDocument.maxFiles : 10,
					maxFilesize                  : typeof bbRlDocument.max_upload_size !== 'undefined' ? bbRlDocument.max_upload_size : 2,
					dictInvalidFileType          : bbRlDocument.dictInvalidFileType,
					dictMaxFilesExceeded         : bbRlMedia.document_dict_file_exceeded,
					previewTemplate              : ForumDocumentTemplates,
					dictCancelUploadConfirmation : bbRlMedia.dictCancelUploadConfirmation,
				}
			);

			var ForumVideoTemplate = document.getElementsByClassName( 'forum-post-video-template' ).length ? document.getElementsByClassName( 'forum-post-video-template' )[0].innerHTML : ''; // Check to avoid error if Node is missing.
			this.videoOptions      = bp.Readylaunch.Utilities.createDropzoneOptions(
				{
					dictFileTooBig               : bbRlVideo.dictFileTooBig,
					acceptedFiles                : bbRlVideo.video_type,
					createImageThumbnails        : false,
					dictDefaultMessage           : bbRlVideo.dropzone_video_message,
					maxFiles                     : typeof bbRlVideo.maxFiles !== 'undefined' ? bbRlVideo.maxFiles : 10,
					maxFilesize                  : typeof bbRlVideo.max_upload_size !== 'undefined' ? bbRlVideo.max_upload_size : 2,
					dictInvalidFileType          : bbRlVideo.dictInvalidFileType,
					dictMaxFilesExceeded         : bbRlVideo.video_dict_file_exceeded,
					previewTemplate              : ForumVideoTemplate,
					dictCancelUploadConfirmation : bbRlVideo.dictCancelUploadConfirmation,
				}
			);

			if ( $( '#bp-media-uploader' ).hasClass( 'bp-media-document-uploader' ) ) {
				var ForumDocumentTemplate = document.getElementsByClassName( 'forum-post-document-template' ).length ? document.getElementsByClassName( 'forum-post-document-template' )[0].innerHTML : ''; // Check to avoid error if Node is missing.
				this.options              = bp.Readylaunch.Utilities.createDropzoneOptions(
					{
						dictFileTooBig               : bbRlMedia.dictFileTooBig,
						acceptedFiles                : bbRlMedia.document_type,
						createImageThumbnails        : false,
						dictDefaultMessage           : bbRlMedia.dropzone_document_message,
						maxFiles                     : typeof bbRlDocument.maxFiles !== 'undefined' ? bbRlDocument.maxFiles : 10,
						maxFilesize                  : typeof bbRlDocument.max_upload_size !== 'undefined' ? bbRlDocument.max_upload_size : 2,
						dictInvalidFileType          : bp_media_dropzone.dictInvalidFileType,
						dictMaxFilesExceeded         : bbRlMedia.document_dict_file_exceeded,
						previewTemplate              : ForumDocumentTemplate,
						dictCancelUploadConfirmation : bbRlMedia.dictCancelUploadConfirmation,
					}
				);
			} else {
				var ForumMediaTemplate = document.getElementsByClassName( 'forum-post-media-template' ).length ? document.getElementsByClassName( 'forum-post-media-template' )[0].innerHTML : ''; // Check to avoid error if Node is missing.
				this.options           = bp.Readylaunch.Utilities.createDropzoneOptions(
					{
						dictFileTooBig               : bbRlMedia.dictFileTooBig,
						dictDefaultMessage           : bbRlMedia.dropzone_media_message,
						acceptedFiles                : 'image/*',
						maxFiles                     : typeof bbRlMedia.maxFiles !== 'undefined' ? bbRlMedia.maxFiles : 10,
						maxFilesize                  : typeof bbRlMedia.max_upload_size !== 'undefined' ? bbRlMedia.max_upload_size : 2,
						dictInvalidFileType          : bp_media_dropzone.dictInvalidFileType,
						dictMaxFilesExceeded         : bbRlMedia.media_dict_file_exceeded,
						previewTemplate              : ForumMediaTemplate,
						dictCancelUploadConfirmation : bbRlMedia.dictCancelUploadConfirmation,
						maxThumbnailFilesize         : typeof bbRlMedia.max_upload_size !== 'undefined' ? bbRlMedia.max_upload_size : 2,
					}
				);
			}

			// if defined, add custom dropzone options.
			if ( typeof bbRlMedia.dropzone_options !== 'undefined' ) {
				Object.assign( this.options, bbRlMedia.dropzone_options );
			}

			this.dropzone_obj            = [];
			this.dropzone_media          = [];
			this.album_id                = typeof bbRlMedia.album_id !== 'undefined' ? bbRlMedia.album_id : false;
			this.current_folder          = typeof bbRlMedia.current_folder !== 'undefined' ? bbRlMedia.current_folder : false;
			this.current_group_id        = typeof bbRlMedia.current_group_id !== 'undefined' ? bbRlMedia.current_group_id : false;
			this.group_id                = typeof bbRlMedia.group_id !== 'undefined' ? bbRlMedia.group_id : false;
			this.bbp_is_reply_edit       = typeof forumMedia.bbp_is_reply_edit !== 'undefined' && forumMedia.bbp_is_reply_edit;
			this.bbp_is_topic_edit       = typeof forumMedia.bbp_is_topic_edit !== 'undefined' && forumMedia.bbp_is_topic_edit;
			this.bbp_is_forum_edit       = typeof forumMedia.bbp_is_forum_edit !== 'undefined' && forumMedia.bbp_is_forum_edit;
			this.bbp_reply_edit_media    = typeof forumMedia.reply_edit_media !== 'undefined' ? forumMedia.reply_edit_media : [];
			this.bbp_reply_edit_document = typeof forumMedia.reply_edit_document !== 'undefined' ? forumMedia.reply_edit_document : [];
			this.bbp_reply_edit_video    = typeof forumMedia.reply_edit_video !== 'undefined' ? forumMedia.reply_edit_video : [];
			this.bbp_topic_edit_media    = typeof forumMedia.topic_edit_media !== 'undefined' ? forumMedia.topic_edit_media : [];
			this.bbp_topic_edit_video    = typeof forumMedia.topic_edit_video !== 'undefined' ? forumMedia.topic_edit_video : [];
			this.bbp_topic_edit_document = typeof forumMedia.topic_edit_document !== 'undefined' ? forumMedia.topic_edit_document : [];
			this.bbp_forum_edit_media    = typeof forumMedia.forum_edit_media !== 'undefined' ? forumMedia.forum_edit_media : [];
			this.bbp_forum_edit_document = typeof forumMedia.forum_edit_document !== 'undefined' ? forumMedia.forum_edit_document : [];
			this.bbp_forum_edit_video    = typeof forumMedia.forum_edit_video !== 'undefined' ? forumMedia.forum_edit_video : [];
			this.bbp_reply_edit_gif_data = typeof forumMedia.reply_edit_gif_data !== 'undefined' ? forumMedia.reply_edit_gif_data : [];
			this.bbp_topic_edit_gif_data = typeof forumMedia.topic_edit_gif_data !== 'undefined' ? forumMedia.topic_edit_gif_data : [];
			this.bbp_forum_edit_gif_data = typeof forumMedia.forum_edit_gif_data !== 'undefined' ? forumMedia.forum_edit_gif_data : [];

			this.giphy             = null;
			this.gif_offset        = 0;
			this.gif_q             = null;
			this.gif_limit         = 20;
			this.gif_requests      = [];
			this.gif_data          = [];
			this.gif_container_key = false;

			// Draft variables.
			this.reply_topic_allow_delete_media = false;
			this.reply_topic_display_post       = 'edit';

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

			var bpNouveau                   = $( '.bp-nouveau' ),
				mediaWrap                   = $( '.bp-existing-media-wrap' ),
				$document                   = $( document ),
				groupMessagesButtonSelector = $( '.buddypress.groups.messages' );

			bpNouveau.on( 'click', '#bp-add-media', this.openUploader.bind( this ) );
			bpNouveau.on( 'click', '#bp-add-document', this.openDocumentUploader.bind( this ) );
			bpNouveau.on( 'click', '#bp-media-submit', this.submitMedia.bind( this ) );
			bpNouveau.on( 'click', '#bp-media-document-submit', this.submitDocumentMedia.bind( this ) );
			bpNouveau.on( 'click', '#bp-media-uploader-close', this.closeUploader.bind( this ) );
			bpNouveau.on( 'click', '#bb-delete-media', this.deleteMedia.bind( this ) );
			bpNouveau.on( 'click', '#bb-select-deselect-all-media', this.toggleSelectAllMedia.bind( this ) );
			if ( undefined !== bbRlIsSendAjaxRequest && '1' === bbRlIsSendAjaxRequest ) {
				$( '#buddypress [data-bp-list="media"]' ).on( 'bp_ajax_request', this.bp_ajax_media_request );
			} else {
				this.bp_media_after_load();
			}

			// albums.
			bpNouveau.on( 'click', '#bb-create-album', this.openCreateAlbumModal.bind( this ) );
			$document.on( 'click', '#bb-create-folder', this.openCreateFolderModal.bind( this ) );
			$document.on( 'click', '#bb-create-folder-child', this.openCreateFolderChildModal.bind( this ) );
			$document.on( 'click', '#bp-edit-folder-open', this.openEditFolderChildModal.bind( this ) );

			$document.on( 'click', '#bp-media-create-album-submit', this.saveAlbum.bind( this ) );
			$document.on( 'click', '#bp-media-create-folder-submit', this.saveFolder.bind( this ) );
			$document.on( 'click', '#bp-media-create-child-folder-submit', this.saveChildFolder.bind( this ) );

			bpNouveau.on( 'click', '#bp-media-create-album-close', this.closeCreateAlbumModal.bind( this ) );
			$document.on( 'click', '.bb-rl-media-create-folder-close', this.closeCreateFolderModal.bind( this ) );
			$document.on( 'click', '#bp-media-edit-folder-close', this.closeEditFolderModal.bind( this ) );
			$document.on( 'click', '.open-popup .errorPopup', this.closeErrorPopup.bind( this ) );

			bpNouveau.on( 'click', '#bp-media-add-more', this.triggerDropzoneSelectFileDialog.bind( this ) );

			$( '#bp-media-uploader' ).on( 'click', '.bp-media-upload-tab', this.changeUploadModalTab.bind( this ) );

			// Fetch Media.
			$( '.bp-nouveau [data-bp-list="media"]' ).on( 'click', 'li.load-more', this.injectMedias.bind( this ) );
			$( '.bp-nouveau #albums-dir-list' ).on( 'click', 'li.load-more', this.appendAlbums.bind( this ) );
			mediaWrap.on( 'click', 'li.load-more', this.appendMedia.bind( this ) );
			bpNouveau.on( 'change', '.bb-media-check-wrap [name="bb-media-select"]', this.addSelectedClassToWrapper.bind( this ) );
			mediaWrap.on( 'change', '.bb-media-check-wrap [name="bb-media-select"]', this.toggleSubmitMediaButton.bind( this ) );

			// single album.
			$document.on( 'click', '#bp-edit-folder-title', this.editFolderTitle.bind( this ) );
			bpNouveau.on( 'click', '#bp-cancel-edit-album-title', this.cancelEditAlbumTitle.bind( this ) );
			bpNouveau.on( 'click', '#bp-save-album-title', this.saveAlbum.bind( this ) );
			$document.on( 'click', '#bp-save-folder-title', this.saveFolder.bind( this ) );
			bpNouveau.on( 'change', '#bp-media-single-album select#bb-rl-album-privacy', this.saveAlbum.bind( this ) );
			bpNouveau.on( 'change', '#media-stream select#bb-rl-folder-privacy', this.savePrivacy.bind( this ) );
			bpNouveau.on( 'click', '#bb-delete-album', this.deleteAlbum.bind( this ) );
			$document.on( 'click', '#bb-delete-folder', this.deleteFolder.bind( this ) );
			$document.on( 'click', '.bb-rl-edit-album', this.editAlbum.bind( this ) );
			$document.on( 'click', '.bb-rl-media-edit-album-close', this.closeEditAlbumModal.bind( this ) );
			//$document.on( 'click', '#bp-save-album-title', this.editAlbumSubmit.bind( this ) );

			$document.on( 'click', 'ul.document-nav li', this.resetPageDocumentDirectory.bind( this ) );
			$document.on( 'click', 'ul.document-nav li a', this.resetPageDocumentDirectory.bind( this ) );

			// forums.
			$document.on( 'click', '#bb-rl-forums-media-button', this.openForumsUploader.bind( this ) );
			$document.on( 'click', '#bb-rl-forums-document-button', this.openForumsDocumentUploader.bind( this ) );
			$document.on( 'click', '#bb-rl-forums-video-button', this.openForumsVideoUploader.bind( this ) );
			$document.on( 'click', '#bb-rl-forums-gif-button', this.toggleGifSelector.bind( this ) );
			$document.find( 'form #whats-new-toolbar, .forum form #whats-new-toolbar' ).on( 'keydown', '.search-query-input', this.searchGif.bind( this ) );
			$document.on( 'click', '.bbpress-forums-activity #whats-new-toolbar .found-media-item', this.selectGif.bind( this ) );
			$document.find( 'form #whats-new-toolbar, .forum form #whats-new-toolbar' ).on( 'click', '.found-media-item', this.selectGif.bind( this ) );
			$document.find( 'form #whats-new-toolbar .gif-search-results, .forum form #whats-new-toolbar .gif-search-results' ).scroll( this.loadMoreGif.bind( this ) );
			if ( ! groupMessagesButtonSelector.length ) {
				$document.find( 'form #whats-new-toolbar, .forum form #whats-new-toolbar' ).on( 'click', '.found-media-item', this.selectGif.bind( this ) );
			}
			$document.find( 'form #whats-new-attachments .forums-attached-gif-container .gif-search-results, .forum form #whats-new-attachments .forums-attached-gif-container .gif-search-results' ).scroll( this.loadMoreGif.bind( this ) );
			$document.find( 'form #whats-new-attachments .forums-attached-gif-container, .forum form #whats-new-attachments .forums-attached-gif-container' ).on( 'click', '.gif-image-remove', this.removeSelectedGif.bind( this ) );

			$document.on( 'click', '.gif-image-container', this.playVideo.bind( this ) );

			// Documents.
			$document.on( 'click', '.directory.document  .media-folder_action__anchor, .directory.document  .media-folder_action__anchor li a, .bb-media-container .media-folder_action__anchor, .bb-media-container  .media-folder_action__list li a', this.fileActionButton.bind( this ) );
			$document.on( 'click', '.bb-rl-activity-media-elem .bb_rl_copy_download_file_url a, .media-folder_action__list .bb_rl_copy_download_file_url a, .media .bb-photo-thumb .bb_rl_copy_download_file_url a', this.copyDownloadLink.bind( this ) );
			$document.on( 'click', '.bb-rl-activity-media-elem.bb-rl-media-activity .bb-rl-more_dropdown-wrap .bb_rl_more_dropdown__action, #media-stream.media .bb-photo-thumb .bb-rl-more_dropdown-wrap .bb_rl_more_dropdown__action, .bb-rl-activity-media-elem.bb-rl-document-activity .bb-rl-document-action-wrap .bb-rl-document-action_more, .bb-rl-activity-media-elem.bb-rl-document-activity .bb-rl-document-action-wrap .bb_rl_more_dropdown li a', this.fileActivityActionButton.bind( this ) );
			$document.click( this.toggleFileActivityActionButton );
			$document.on( 'click', '.bb-rl-activity-media-elem.bb-rl-document-activity .bb-rl-document-expand .bb-rl-document-expand-anchor, .bb-rl-activity-media-elem.bb-rl-document-activity .document-expand .document-expand-anchor, .bb-rl-activity-media-elem.bb-rl-document-activity .bb-rl-document-action-wrap .bb-rl-document-action_collapse', this.toggleCodePreview.bind( this ) );
			$document.on( 'click', '.activity .bb-rl-document-move-activity, #media-stream .bb-rl-document-move-activity', this.moveDocumentIntoFolder.bind( this ) );
			$document.on( 'click', '.bp-nouveau [data-bp-list="document"] .pager .dt-more-container.load-more', this.injectDocuments.bind( this ) );
			$document.on( 'click', '.bp-nouveau [data-bp-list="document"] .data-head', this.sortDocuments.bind( this ) );
			$document.on( 'click', '.modal-container .bb-field-steps-actions', this.documentPopupNavigate.bind( this ) );
			$document.on( 'click', '.bp-media-document-uploader .modal-container .bb-field-uploader-actions', this.uploadDocumentNavigate.bind( this ) );
			$document.on( 'click', '.bp-media-photo-uploader .modal-container .bb-field-uploader-actions', this.uploadMediaNavigate.bind( this ) );
			$document.on( 'click', '.modal-container #bp-media-edit-child-folder-submit', this.renameChildFolder.bind( this ) );

			// Media.
			$document.on( 'click', '.activity .bb-rl-media-move-activity, #media-stream .bb-rl-media-move-activity', this.moveMediaIntoAlbum.bind( this ) );

			// Document move option.
			var $activityElements = $( '#buddypress .bb-rl-activity-list, #buddypress [data-bp-list="activity"], #bb-rl-media-model-container .bb-rl-activity-list, #media-stream' );
			var $groupMediaStream = $( '.group-media #media-stream' );
			$activityElements.on( 'click', '.ac-document-move, .ac-folder-move', this.openDocumentMove.bind( this ) );
			$activityElements.add( $groupMediaStream ).on( 'click', '.ac-media-move', this.openMediaMove.bind( this ) );
			$activityElements.on( 'click', '.bb-rl-ac-document-close-button, .bb-rl-ac-folder-close-button', this.closeDocumentMove.bind( this ) );
			$activityElements.on( 'click', '.bb-rl-ac-media-close-button', this.closeMediaMove.bind( this ) );
			var mediaStream = $( '#bb-rl-media-model-container .bb-rl-activity-list, #media-stream' );
			mediaStream.on( 'click', '.ac-document-rename', this.renameDocument.bind( this ) );
			mediaStream.on( 'click', '.ac-document-edit', this.editDocument.bind( this ) );
			mediaStream.on( 'click', '.bb-rl-media-edit-document-close', this.closeEditDocumentModal.bind( this ) );
			// mediaStream.on( 'click', '.ac-document-privacy', this.editPrivacyDocument.bind( this ) );
			/* mediaStream.on( 'keyup', '.media-folder_name_edit', this.renameDocumentSubmit.bind( this ) );
			mediaStream.on( 'click', '.name_edit_cancel, .name_edit_save', this.renameDocumentSubmit.bind( this ) ); */
			mediaStream.on( 'click', '#bp-media-edit-document-submit', this.editDocumentSubmit.bind( this ) );
			$document.on( 'click', '#bp-media-edit-album-submit', this.editAlbumSubmit.bind( this ) );

			// document delete.
			$document.on( 'click', '.bb-rl-document-file-delete', this.deleteDocument.bind( this ) );

			// Media Delete.
			$document.on( 'click', '.bb-rl-media-file-delete', this.deleteMedia.bind( this ) );

			// Folder Move.
			$document.on( 'click', '.bb-rl-folder-move', this.folderMove.bind( this ) );

			// Create Folder.
			$document.on( 'click', '.bb-rl-document-open-create-popup-folder', this.createFolderInPopup.bind( this ) );
			$document.on( 'click', '.bb-rl-media-open-create-popup-folder', this.createAlbumInPopup.bind( this ) );
			$document.on( 'click', '.bb-rl-close-create-popup-folder', this.closeCreateFolderInPopup.bind( this ) );
			$document.on( 'click', '.bb-rl-close-create-popup-album', this.closeCreateAlbumInPopup.bind( this ) );
			$document.on( 'click', '.bb-rl-document-create-popup-folder-submit', this.submitCreateFolderInPopup.bind( this ) );

			// Create Album.
			$document.on( 'click', '.bb-rl-media-create-popup-album-submit', this.submitCreateAlbumInPopup.bind( this ) );

			// Group Messages.
			var groupMessagesToolbarSelector         = $( '.buddypress.groups.messages form#send_group_message_form #whats-new-toolbar' );
			var groupMessagesToolbarContainerResults = $( '.buddypress.groups.messages form#send_group_message_form #whats-new-attachments .bp-group-messages-attached-gif-container .gif-search-results' );
			var groupMessagesToolbarContainer        = $( '.buddypress.groups.messages form#send_group_message_form #whats-new-attachments .bp-group-messages-attached-gif-container' );

			groupMessagesButtonSelector.on( 'click', '#bp-group-messages-media-button', this.openGroupMessagesUploader.bind( this ) );
			groupMessagesButtonSelector.on( 'click', '#bp-group-messages-document-button', this.openGroupMessagesDocumentUploader.bind( this ) );
			groupMessagesButtonSelector.on( 'click', '#bp-group-messages-video-button', this.openGroupMessagesVideoUploader.bind( this ) );
			groupMessagesButtonSelector.on( 'click', '#bp-group-messages-gif-button', this.toggleGroupMessagesGifSelector.bind( this ) );
			groupMessagesToolbarSelector.on( 'keyup', '.search-query-input', this.searchGroupMessagesGif.bind( this ) );
			groupMessagesToolbarSelector.on( 'click', '.found-media-item', this.selectGroupMessagesGif.bind( this ) );
			groupMessagesToolbarContainerResults.scroll( this.loadMoreGroupMessagesGif.bind( this ) );
			$( '.groups.messages form#send_group_message_form #whats-new-toolbar .bp-group-messages-attached-gif-container .gif-search-results' ).scroll( this.loadMoreGroupMessagesGif.bind( this ) );
			groupMessagesToolbarContainer.on( 'click', '.gif-image-remove', this.removeGroupMessagesSelectedGif.bind( this ) );

			mediaWrap.on( 'scroll', this.loadExistingMedia.bind( this ) );

			document.addEventListener( 'keyup', this.closePopup.bind( this ) );
			document.addEventListener( 'keyup', this.submitPopup.bind( this ) );

			$( window ).bind( 'beforeunload', this.beforeunloadWindow.bind( this ) );

			// Gifs autoplay.
			if ( ! _.isUndefined( bbRlMedia.gif_api_key ) ) {
				window.addEventListener( 'scroll', this.autoPlayGifVideos, false );
				window.addEventListener( 'resize', this.autoPlayGifVideos, false );

				document.addEventListener( 'keydown', _.bind( this.closePickersOnEsc, this ) );
				$document.on( 'click', _.bind( this.closePickersOnClick, this ) );
			}

			if ( ( this.bbp_is_reply_edit || this.bbp_is_topic_edit || this.bbp_is_forum_edit ) &&
				( this.bbp_reply_edit_media.length || this.bbp_topic_edit_media.length || this.bbp_forum_edit_media.length ) ) {
				$( '#bb-rl-forums-media-button' ).trigger( 'click' );
			}

			if ( ( this.bbp_is_reply_edit || this.bbp_is_topic_edit || this.bbp_is_forum_edit ) &&
				( this.bbp_reply_edit_document.length || this.bbp_topic_edit_document.length || this.bbp_forum_edit_document.length ) ) {
				$( '#bb-rl-forums-document-button' ).trigger( 'click' );
			}

			if ( ( this.bbp_is_reply_edit || this.bbp_is_topic_edit || this.bbp_is_forum_edit ) &&
				( this.bbp_reply_edit_video.length || this.bbp_topic_edit_video.length || this.bbp_forum_edit_video.length ) ) {
				$( '#bb-rl-forums-video-button' ).trigger( 'click' );
			}

			if ( ( this.bbp_is_reply_edit || this.bbp_is_topic_edit || this.bbp_is_forum_edit ) &&
				( Object.keys( this.bbp_reply_edit_gif_data ).length || Object.keys( this.bbp_topic_edit_gif_data ).length || Object.keys( this.bbp_forum_edit_gif_data ).length ) ) {
				this.editGifPreview();

				// Disable other buttons( media/document ).
				var tool_box = jQuery( '#bb-rl-forums-gif-button' ).addClass( 'active' ).closest( 'form' );
				bp.Nouveau.Media.disableButtonsInToolBox(
					tool_box,
					[
						'#bb-rl-forums-media-button',
						'#bb-rl-forums-document-button',
						'#bb-rl-forums-video-button'
					]
				);
			}

			// Open edit folder popup if user redirected from activity edit folder privacy.
			var childFolderElem = $( '#bp-media-edit-child-folder' );
			if ( window.location.hash === '#openEditFolder' && childFolderElem.length ) {
				history.pushState( null, null, window.location.href.split( '#' )[ 0 ] );
				childFolderElem.show();
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
			this.submitCreateFolderAlbumInPopup( event, 'document', 'folder' );
		},

		submitCreateAlbumInPopup: function ( event ) {
			event.preventDefault();
			this.submitCreateFolderAlbumInPopup( event, 'media', 'album' );
		},

		/**
		 * [editDocument description]
		 *
		 * @return {[type]} [description]
		 * @param event
		 */
		editAlbum: function ( event ) {
			event.preventDefault();

			var $editAlbumModal = $( '#bb-rl-media-edit-album' ),
				album_item = $( event.currentTarget ).closest( '#bp-media-single-album' ),
				current_name = album_item.find( '#bp-single-album-title .title-wrap' ).text();

			if (
				$( event.currentTarget ).attr( 'data-privacy' ) &&
				$editAlbumModal.find( '#bb-album-privacy' ).length > 0
			) {
				var current_privacy = $( event.currentTarget ).attr( 'data-privacy' );
				if ( current_privacy === 'grouponly' ) {
					$editAlbumModal.find( '#bb-album-privacy' ).addClass( 'bp-hide' );
				} else {
					$editAlbumModal.find( '#bb-album-privacy' ).val( current_privacy ).change().removeClass( 'bp-hide' );
				}
			} else if ( $editAlbumModal.find( '#bb-album-privacy' ).length > 0 ) {
				$editAlbumModal.find( '#bb-album-privacy' ).addClass( 'bp-hide' );
			}

			$editAlbumModal.show();
			$editAlbumModal.addClass( 'open-popup' );

			$editAlbumModal.find( '#bb-album-title' ).val( current_name ).focus().select();
			$editAlbumModal.attr( 'data-id', album_item.attr('data-id') );
		},

		closeEditAlbumModal: function ( event ) {
			event.preventDefault();
			var $editAlbumModal = $( '#bb-rl-media-edit-album' );

			$editAlbumModal.hide();
			$editAlbumModal.removeClass( 'open-popup' );
		},

		closeCreateFolderInPopup: function ( event ) {
			event.preventDefault();
			this.closeCreateFolderAlbumInPopup( event, 'folder' );
		},

		closeCreateAlbumInPopup: function ( event ) {
			event.preventDefault();
			this.closeCreateFolderAlbumInPopup( event, 'album' );
		},

		createFolderInPopup: function ( event ) {
			event.preventDefault();
			this.createAlbumFolderInPopup( event, 'document', 'folder' );
		},

		createAlbumInPopup: function ( event ) {
			event.preventDefault();
			this.createAlbumFolderInPopup( event, 'media', 'album' );
		},

		savePrivacy: function ( event ) {
			var target = $( event.currentTarget ), itemId = 0, type = '', value = '', text = '';
			event.preventDefault();

			if ( target.hasClass( 'new-folder-create-privacy' ) ) {
				return false;
			}

			itemId = parseInt( target.data( 'item-id' ) );
			type   = target.data( 'item-type' );
			value  = target.val();
			text   = target.find( 'option:selected' ).text();

			this.privacySelectorSelect.addClass( 'hide' );
			this.privacySelectorSpan.text( '' );
			this.privacySelectorSpan.text( text );
			this.privacySelectorSpan.show();

			if ( itemId > 0 ) {
				var data = {
					'action'   : 'document_save_privacy',
					'item_id'  : itemId,
					'type'     : type,
					'value'    : value,
					'_wpnonce' : bbRlNonce.media
				};

				$.ajax(
					{
						type    : 'POST',
						url     : bbRlAjaxUrl,
						data    : data,
						success : function ( response ) {
							if ( response.success ) {
								var $document = $( document );
								$document.find( '#div-listing-' + itemId + ' li#' + itemId + ' a' ).attr( 'data-privacy', value );
								if ( response.data.document && response.data.document.video_symlink ) {
									$document.find( 'a.bb-rl-open-document-theatre[data-id="' + itemId + '"]' ).attr( 'data-video-preview', response.data.document.video_symlink );
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

		deleteDocument: function ( event ) {
			event.preventDefault();

			var target                = $( event.currentTarget ),
				type                  = target.attr( 'data-type' ),
				id                    = target.attr( 'data-item-id' ),
				attachment_id         = target.attr( 'data-item-attachment-id' ),
				preview_attachment_id = target.attr( 'data-item-preview-attachment-id' ),
				fromWhere             = target.attr( 'data-item-from' ),
				data                  = [];

			if ( 'activity' !== fromWhere ) {
				if ( 'folder' === type ) {
					if ( ! confirm( bbRlMedia.i18n_strings.folder_delete_confirm ) ) {
						return false;
					}
				} else if ( 'document' === type ) {
					if ( ! confirm( bbRlMedia.i18n_strings.document_delete_confirm ) ) {
						return false;
					}
				}

				data = {
					'action'                : 'document_delete',
					'id'                    : id,
					'preview_attachment_id' : preview_attachment_id,
					'type'                  : type,
					'attachment_id'         : attachment_id,
					'_wpnonce'              : bbRlNonce.media,
				};

				if ( 'yes' === bbRlMedia.is_document_directory ) {
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
						type    : 'POST',
						url     : bbRlAjaxUrl,
						asyc    : false,
						data    : data,
						success : function ( response ) {
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

				if ( ! confirm( bbRlMedia.i18n_strings.document_delete_confirm ) ) {
					return false;
				}

				var activityId = target.attr( 'data-item-activity-id' );

				data = {
					'action'                : 'document_activity_delete',
					'id'                    : id,
					'preview_attachment_id' : preview_attachment_id,
					'type'                  : type,
					'activity_id'           : activityId,
					'attachment_id'         : attachment_id,
					'_wpnonce'              : bbRlNonce.media,
				};

				$.ajax(
					{
						type    : 'POST',
						url     : bbRlAjaxUrl,
						data    : data,
						success : function ( response ) {
							if ( response.success ) {
								$( 'body #buddypress .bb-rl-activity-list li#activity-' + activityId + ' .bb-rl-document-activity .bb-rl-activity-inner .bb-activity-media-wrap .bb-rl-document-activity.' + id ).remove();
								$( 'body #buddypress .bb-rl-activity-list .bb-rl-activity-comments .bb-rl-document-activity.' + id ).remove();
								if ( true === response.data.delete_activity ) {
									$( 'body #buddypress .bb-rl-activity-list li#activity-' + activityId ).remove();
									$( 'body .bb-rl-activity-media-elem.bb-rl-document-activity.' + id ).remove();
									$( 'body .bb-rl-activity-comments li#acomment-' + activityId ).remove();
								} else {
									$( 'body #buddypress .bb-rl-activity-list li#activity-' + activityId ).replaceWith( response.data.activity_content );
								}
							}
						}
					}
				);
			}
		},

		bp_ajax_media_request: function ( event, data ) {
			if ( bbRlMedia.group_id && typeof data !== 'undefined' && typeof data.response.scopes.groups !== 'undefined' && 0 === parseInt( data.response.scopes.groups ) ) {
				$( '.bb-photos-actions' ).hide();
			} else if ( bbRlMedia.group_id && typeof data !== 'undefined' && typeof data.response.scopes.groups !== 'undefined' && 0 !== parseInt( data.response.scopes.groups ) ) {
				$( '.bb-photos-actions' ).show();
			} else if ( typeof data !== 'undefined' && typeof data.response.scopes.personal !== 'undefined' && 0 === parseInt( data.response.scopes.personal ) ) {
				$( '.bb-photos-actions' ).hide();
			} else if ( typeof data !== 'undefined' && typeof data.response.scopes.personal !== 'undefined' && 0 !== parseInt( data.response.scopes.personal ) ) {
				$( '.bb-photos-actions' ).show();
			}
		},

		bp_media_after_load: function () {
			if ( $( '.media-list.bb-photo-list' ).children().length ) {
				$( '.bb-photos-actions' ).show();
			}
		},

		addSelectedClassToWrapper: function ( event ) {
			var target = event.currentTarget;
			if ( $( target ).is( ':checked' ) ) {
				$( target ).closest( '.bb-media-check-wrap' ).find( '.bp-tooltip' ).attr( 'data-bp-tooltip', bbRlMedia.i18n_strings.unselect );
				$( target ).closest( '.bb-item-thumb' ).addClass( 'selected' );
				if ( $( '#bb-delete-media' ).length ) {
					$( '#bb-delete-media' ).removeAttr( 'disabled' );
				}
			} else {
				$( target ).closest( '.bb-item-thumb' ).removeClass( 'selected' );
				$( target ).closest( '.bb-media-check-wrap' ).find( '.bp-tooltip' ).attr( 'data-bp-tooltip', bbRlMedia.i18n_strings.select );

				var selectAllMedia = $( '.bp-nouveau #bb-select-deselect-all-media' );
				if ( selectAllMedia.hasClass( 'selected' ) ) {
					selectAllMedia.removeClass( 'selected' );
				}
				if ( $( '#bb-delete-media' ).length ) {
					$( '#bb-delete-media' ).attr( 'disabled', 'disabled' );
				}
			}
		},

		moveDocumentIntoFolder: function ( event ) {
			this.moveAttachments( event, 'document', 'folder' );
		},

		moveMediaIntoAlbum: function ( event ) {
			this.moveAttachments( event, 'media', 'album' );
		},

		folderMove: function ( event ) {
			this.moveAttachments( event, 'document_folder', 'folder' );
		},

		deleteMedia: function ( event ) {
			var target = $( event.currentTarget ), self = this, dir_label;
			event.preventDefault();

			if ( target.attr( 'disabled' ) ) {
				return false;
			}

			var media              = [],
				buddyPressSelector = $( '#buddypress' ),
				type               = target.attr( 'data-type' ),
				fromWhere          = target.data( 'item-from' ),
				id                 = '',
				activityId         = '',
				$document          = $( document );
			if ( 'album' === type ) {
				if ( ! confirm( bbRlMedia.i18n_strings.album_delete_confirm ) ) {
					return false;
				}
			} else if ( 'media' === type ) {
				if ( ! confirm( bbRlMedia.i18n_strings.media_delete_confirm ) ) {
					return false;
				}
			}

			if ( target.hasClass( 'bb-delete' ) ) {
				if ( ! confirm( bbRlMedia.i18n_strings.media_delete_confirm ) ) {
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
			if ( fromWhere && fromWhere.length && 'activity' === fromWhere && media.length === 0 ) {
				id = target.attr( 'data-item-id' );
				media.push( id );
			}

			if ( media.length === 0 ) {
				media.push( target.data( 'item-id' ) );
			}

			if ( media.length === 0 ) {
				return false;
			}

			target.prop( 'disabled', true );
			$( '#buddypress #media-stream.media .bp-feedback' ).remove();

			var data = {
				'action': 'media_delete',
				'_wpnonce': bbRlNonce.media,
				'media': media,
				'activity_id': activityId,
				'from_where': fromWhere,
			};

			$.ajax(
				{
					type: 'POST',
					url: bbRlAjaxUrl,
					data: data,
					success: function ( response ) {
						self.current_page = 1;
						if ( fromWhere && fromWhere.length && 'activity' === fromWhere ) {
							if ( response.success ) {
								$.each(
									media,
									function ( index, value ) {
										var $elem = $( '#bb-rl-activity-stream ul.bb-rl-activity-list li.activity .bb-rl-activity-content .bb-rl-activity-inner .bb-activity-media-wrap div[data-id="' + value + '"]' );
										if ( $elem.length ) {
											$elem.remove();
										}
										var $elem2 = $( 'body .bb-rl-activity-media-elem.bb-rl-media-activity.' + value );
										if ( $elem2.length ) {
											$elem2.remove();
										}
									}
								);

								$( '#bb-rl-activity-stream ul.bb-rl-activity-list li[data-bp-activity-id="' + activityId + '"] .bb-rl-activity-content .bb-rl-activity-inner .bb-activity-media-wrap' ).remove();
								$( '#bb-rl-activity-stream ul.bb-rl-activity-list li[data-bp-activity-id="' + activityId + '"] .bb-rl-activity-content .bb-rl-activity-inner' ).append( response.data.media_content );

								var length = $( '#bb-rl-activity-stream ul.bb-rl-activity-list li[data-bp-activity-id="' + activityId + '"] .bb-rl-activity-content .bb-rl-activity-inner .bb-rl-activity-media-elem' ).length;
								if ( length === 0 ) {
									$( '#bb-rl-activity-stream ul.bb-rl-activity-list li[data-bp-activity-id="' + activityId + '"]' ).remove();
								}

								if ( true === response.data.delete_activity ) {
									$( 'body #buddypress .bb-rl-activity-list li#activity-' + activityId ).remove();
									$( 'body .bb-rl-activity-media-elem.bb-rl-media-activity.' + id ).remove();
									$( 'body .bb-rl-activity-comments li#acomment-' + activityId ).remove();
								} else {
									$( 'body #buddypress .bb-rl-activity-list li#activity-' + activityId ).replaceWith( response.data.activity_content );
								}
							}
						} else if ( fromWhere && fromWhere.length && 'media' === fromWhere ) {
							if ( response.success ) {
								if ( 'yes' === bbRlMedia.is_media_directory ) {
									var store = bp.Nouveau.getStorage( 'bp-media' );
									var scope = store.scope;
									if ( 'personal' === scope ) {
										$document.find( '#bb-rl-media-scope-options option[data-bp-scope="personal"]' ).prop('selected', true);
										$document.find( '#bb-rl-media-scope-options' ).trigger( 'change' );
									} else if ( 'groups' === scope ) {
										$document.find( '#bb-rl-media-scope-options option[data-bp-scope="groups"]' ).prop('selected', true);
										$document.find( '#bb-rl-media-scope-options' ).trigger( 'change' );
									} else {
										$document.find( '#bb-rl-media-scope-options option[data-bp-scope="all"]' ).prop('selected', true);
										$document.find( '#bb-rl-media-scope-options' ).trigger( 'change' );
									}
								} else {
									if ( response.data.media_personal_count ) {
										$( '#buddypress' ).find( '.bp-wrap .users-nav ul li#media-personal-li a span.count' ).text( response.data.media_personal_count );
									}

									if (
										'undefined' !== typeof response.data &&
										'undefined' !== typeof response.data.media_group_count
									) {
										if ( $( '#buddypress .bb-item-count' ).length > 0 && 'yes' !== BP_Nouveau.media.is_media_directory ) {
											dir_label = BP_Nouveau.dir_labels.hasOwnProperty( 'media' ) ?
											(
												1 === parseInt( response.data.media_group_count ) ?
												BP_Nouveau.dir_labels.media.singular : BP_Nouveau.dir_labels.media.plural
											)
											: '';
											$( '#buddypress .bb-item-count' ).html( '<span class="bb-count">' + response.data.media_group_count + '</span> ' + dir_label );
										} else {
											$( '#buddypress' ).find( '.bp-wrap .groups-nav ul li#photos-groups-li a span.count' ).text( response.data.media_group_count );
										}
									}

									if ( 0 !== response.data.media_html_content.length ) {
										if ( 0 === parseInt( response.data.media_personal_count ) ) {
											$( '.bb-photos-actions' ).hide();
											$( '#media-stream' ).html( response.data.media_html_content );
										} else {
											buddyPressSelector.find( '.media-list:not(.existing-media-list)' ).html( response.data.media_html_content );
										}
									} else if ( 0 !== response.data.group_media_html_content.length ) {
										if ( 0 === parseInt( response.data.media_group_count ) ) {
											$( '.bb-photos-actions' ).hide();
											$( '#media-stream' ).html( response.data.group_media_html_content );
										} else {
											buddyPressSelector.find( '.media-list:not(.existing-media-list)' ).html( response.data.group_media_html_content );
										}
									} else {
										$.each(
											media,
											function ( index, value ) {
												var $elem = $( '#media-stream ul.media-list li[data-id="' + value + '"]' );
												if ( $elem.length ) {
													$elem.remove();
												}
											}
										);
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

								// inject media.
								if ( 0 !== response.data.media_html_content.length ) {
									if ( 0 === parseInt( response.data.media_personal_count ) ) {
										$( '.bb-photos-actions' ).hide();
										$( '#media-stream' ).html( response.data.media_html_content );
									} else {
										$media_list.html( response.data.media_html_content );
									}
								} else if ( 0 !== response.data.group_media_html_content.length ) {
									if ( 0 === parseInt( response.data.media_group_count ) ) {
										$( '.bb-photos-actions' ).hide();
										$( '#media-stream' ).html( response.data.group_media_html_content );
									} else {
										$media_list.html( response.data.group_media_html_content );
									}
								} else {
									$.each(
										media,
										function ( index, value ) {
											var $elem = $( '#media-stream ul.media-list li[data-id="' + value + '"]' );
											if ( $elem.length ) {
												$elem.remove();
											}
										}
									);
								}
							} else {
								$( '#buddypress #media-stream.media' ).prepend( response.data.feedback );
							}
						}

						var selectAllMedia = $( '.bp-nouveau #bb-select-deselect-all-media' );
						if ( selectAllMedia.hasClass( 'selected' ) ) {
							selectAllMedia.removeClass( 'selected' );
						}

						// replace dummy image with original image by faking scroll event to call bp.Nouveau.lazyLoad.
						jQuery( window ).scroll();

					}
				}
			);
		},

		toggleSelectAllMedia: function ( event ) {
			event.preventDefault();

			var $target     = $( event.currentTarget ),
				isSelecting = ! $target.hasClass( 'selected' );

			this.setMediaSelectionState( isSelecting );

			if ( $( '#bb-delete-media' ).length ) {
				if ( isSelecting ) {
					$( '#bb-delete-media' ).removeAttr( 'disabled' );
				} else {
					$( '#bb-delete-media' ).attr( 'disabled', 'disabled' );
				}
			}

			$target.toggleClass( 'selected', isSelecting ).data( 'bp-tooltip', isSelecting ? BP_Nouveau.media.i18n_strings.unselectall : BP_Nouveau.media.i18n_strings.selectall );
		},

		editFolderTitle: function ( event ) {
			event.preventDefault();
			this.editAlbumFolderTitle( event, 'document' );
		},

		cancelEditAlbumTitle: function ( event ) {
			event.preventDefault();

			$( '#bb-album-title' ).removeClass( 'error' );
			$( '#bp-save-folder-title' ).hide();
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
			$( '#bp-media-uploader-modal-title' ).text( bbRlMedia.i18n_strings.upload );
			$( '#bp-media-uploader-modal-status-text' ).text( '' );
			$( '#bp-media-post-content' ).val( '' );
			this.dropzone_obj.element ? this.dropzone_obj.destroy() : '';
			this.dropzone_media = [];

			var currentPopup = $( event.currentTarget ).closest( '#bp-media-uploader' );

			$( '.bb-rl-close-create-popup-album' ).trigger( 'click' );
			$( '.bb-rl-close-create-popup-folder' ).trigger( 'click' );
			currentPopup.find( '.bb-rl-breadcrumbs-append-ul-li .item span[data-id="0"]' ).trigger( 'click' );

			if ( currentPopup.find( '.bb-field-steps' ).length ) {
				currentPopup.find( '.bb-field-steps-1' ).show().siblings( '.bb-field-steps-2' ).hide();
				currentPopup.find( '.bb-field-steps-1 #bp-media-photo-next, .bb-field-steps-1 #bp-media-document-next ' ).hide();
				currentPopup.find( '.bb-field-steps-1' ).removeClass( 'controls-added' );
				currentPopup.find( '#bp-media-document-prev, #bp-media-prev, #bp-media-document-submit, #bp-media-submit, .bb-rl-media-open-create-popup-folder, .bb-rl-document-open-create-popup-folder, .bb-rl-create-popup-folder-wrap, .bb-rl-create-popup-album-wrap, .bb-rl-video-open-create-popup-album' ).hide();
			}

			this.clearFolderLocationUI( event );
		},

		closeChildFolderUploader: function ( event ) {
			event.preventDefault();
			var $document = $( document );
			$document.find( '.open-popup #bb-album-child-title' ).val( '' );

			$document.find( '.open-popup #bp-media-create-child-folder-submit' ).removeClass( 'loading' );
			$document.find( '#bp-media-create-child-folder' ).removeClass( 'open-popup' );
			$document.find( '#bp-media-create-child-folder' ).hide();

			$document.find( '.open-popup #bb-album-title' ).text( '' );
			$document.find( '.open-popup #bp-media-create-folder' ).hide();
			$document.find( '#bp-media-create-folder' ).removeClass( 'open-popup' );
		},

		closeFolderUploader: function ( event ) {
			event.preventDefault();
			var $document = $( document );
			$document.find( '.open-popup #bb-album-title' ).val( '' );
			$document.find( '.open-popup #bp-media-create-folder-submit' ).removeClass( 'loading' );
			$document.find( '.open-popup #bp-media-document-submit' ).hide();
			$document.find( '#bp-media-create-folder' ).removeClass( 'open-popup' );
			$document.find( '#bp-media-create-folder' ).hide();
		},

		loadMoreGif: function ( e ) {
			var el = e.target, self = this;

			var $forums_gif_container = $( e.target ).closest( 'form' ).find( '.forums-attached-gif-container' );
			var gif_container_key     = $forums_gif_container.data( 'key' );
			self.gif_container_key    = gif_container_key;

			if ( el.scrollTop + el.offsetHeight >= el.scrollHeight && ! $forums_gif_container.hasClass( 'loading' ) ) {
				if ( self.gif_data[ gif_container_key ].total_count > 0 && self.gif_data[ gif_container_key ].offset <= self.gif_data[ gif_container_key ].total_count ) {
					var params = {
						offset: self.gif_data[ gif_container_key ].offset,
						fmt: 'json',
						limit: self.gif_data[ gif_container_key ].limit
					};

					$forums_gif_container.addClass( 'loading' );
					var request;
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
			var gif_container_key             = $group_messages_gif_container.data( 'key' );
			self.gif_container_key            = gif_container_key;

			if ( el.scrollTop + el.offsetHeight >= el.scrollHeight && ! $( e.target ).closest( '.bp-group-messages-attached-gif-container' ).hasClass( 'loading' ) ) {
				if ( self.gif_total_count > 0 && self.gif_offset <= self.gif_total_count ) {
					var params = {
						offset: self.gif_offset,
						fmt: 'json',
						limit: self.gif_limit
					};

					$( e.target ).closest( '.bp-group-messages-attached-gif-container' ).addClass( 'loading' );
					var request;
					if ( _.isNull( self.gif_q ) ) {
						request = self.giphy.trending( params, _.bind( self.loadMoreGroupMessagesGifResponse, self ) );
					} else {
						request = self.giphy.search( _.extend( { q : self.gif_q }, params ), _.bind( self.loadMoreGroupMessagesGifResponse, self ) );
					}

					self.gif_requests.push( request );
					self.gif_offset = self.gif_offset + self.gif_limit;
					self.gif_data[ gif_container_key ].requests.push( request );
					self.gif_data[ gif_container_key ].offset = self.gif_data[ gif_container_key ].offset + self.gif_data[ gif_container_key ].limit;
				}
			}
		},

		loadMoreGroupMessagesGifResponse: function ( response ) {
			var self          = this, i = 0,
				$gifContainer = $( '.bp-group-messages-attached-gif-container' );
			$gifContainer.removeClass( 'loading' );
			if ( typeof response.data !== 'undefined' && response.data.length ) {
				var li_html = '', responseDataLength = response.data.length;
				for ( i = 0; i < responseDataLength; i++ ) {
					var bgNo                 = Math.floor( Math.random() * ( 6 - 1 + 1 ) ) + 1;
					var strictWidth          = window.innerWidth > 768 ? 140 : 130;
					var originalWidth        = response.data[ i ].images.original.width;
					var originalHeight       = response.data[ i ].images.original.height;
					var relativeHeight       = (strictWidth * originalHeight) / originalWidth;
					li_html                 += '<li class="bg' + bgNo + '" style="height: ' + relativeHeight + 'px;">\n' +
						'\t<a class="found-media-item" href="' + response.data[ i ].images.original.url + '" data-id="' + response.data[ i ].id + '">\n' +
						'\t\t<img src="' + response.data[ i ].images.fixed_width.url + '" alt="">\n' +
						'\t</a>\n' +
						'</li>';
					response.data[ i ].saved = false;
					self.gif_data.push( response.data[ i ] );
				}

				$gifContainer.find( '.gif-search-results-list' ).append( li_html );
			}

			if ( typeof response.pagination !== 'undefined' && typeof response.pagination.total_count !== 'undefined' ) {
				self.gif_total_count = response.pagination.total_count;
			}
		},

		loadMoreGifResponse: function ( response ) {
			var self          = this, i = 0,
				$gifContainer = $( 'div.forums-attached-gif-container[data-key="' + self.gif_container_key + '"]' );
			$gifContainer.removeClass( 'loading' );
			if ( typeof response.data !== 'undefined' && response.data.length ) {
				var li_html = '', responseDataLength = response.data.length;
				for ( i = 0; i < responseDataLength; i++ ) {
					var bgNo                 = Math.floor( Math.random() * ( 6 - 1 + 1 ) ) + 1;
					var strictWidth          = window.innerWidth > 768 ? 140 : 130;
					var originalWidth        = response.data[ i ].images.original.width;
					var originalHeight       = response.data[ i ].images.original.height;
					var relativeHeight       = (strictWidth * originalHeight) / originalWidth;
					li_html                 += '<li class="bg' + bgNo + '" style="height: ' + relativeHeight + 'px;">\n' +
						'\t<a class="found-media-item" href="' + response.data[ i ].images.original.url + '" data-id="' + response.data[ i ].id + '">\n' +
						'\t\t<img src="' + response.data[ i ].images.fixed_width.url + '" alt="">\n' +
						'\t</a>\n' +
						'</li>';
					response.data[ i ].saved = false;
					self.gif_data[ self.gif_container_key ].data.push( response.data[ i ] );
				}

				$gifContainer.closest( 'form' ).find( '.gif-search-results-list' ).append( li_html );
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

			var forumGifContainer                        = $( '#whats-new-attachments .forums-attached-gif-container' );
			forumGifContainer[ 0 ].style.backgroundImage = 'url(' + gif_data.images.fixed_width.url + ')';
			forumGifContainer[ 0 ].style.backgroundSize  = 'contain';
			forumGifContainer[ 0 ].style.height          = gif_data.images.original.height + 'px';
			forumGifContainer[ 0 ].style.width           = gif_data.images.original.width + 'px';
			forumGifContainer.find( '.gif-image-container img' ).attr( 'src', gif_data.images.original.url );
			forumGifContainer.removeClass( 'closed' );
			var bbpMediaGifElem = $( '#bbp_media_gif' );
			if ( bbpMediaGifElem.length ) {
				bbpMediaGifElem.val( JSON.stringify( gif_data ) );
			}
		},

		selectGif: function ( e ) {
			var self          = this, i = 0, target = $( e.currentTarget ),
				gif_container = target.closest( 'form' ).find( '.forums-attached-gif-container' );
			e.preventDefault();

			gif_container.closest( 'form' ).find( '.bb-rl-gif-media-search-dropdown' ).removeClass( 'open' );
			var gif_container_key = gif_container.data( 'key' );
			if ( typeof self.gif_data[ gif_container_key ] !== 'undefined' && typeof self.gif_data[ gif_container_key ].data !== 'undefined' && self.gif_data[ gif_container_key ].data.length ) {
				var gifDataLength = self.gif_data[ gif_container_key ].data.length;
				for ( i = 0; i < gifDataLength; i++ ) {
					if ( self.gif_data[ gif_container_key ].data[ i ].id === e.currentTarget.dataset.id ) {

						target.closest( 'form' ).find( '#whats-new-attachments .forums-attached-gif-container' )[ 0 ].style.backgroundImage = 'url(' + self.gif_data[ gif_container_key ].data[ i ].images.fixed_width.url + ')';
						target.closest( 'form' ).find( '#whats-new-attachments .forums-attached-gif-container' )[ 0 ].style.backgroundSize  = 'contain';
						target.closest( 'form' ).find( '#whats-new-attachments .forums-attached-gif-container' )[ 0 ].style.height          = self.gif_data[ gif_container_key ].data[ i ].images.original.height + 'px';
						target.closest( 'form' ).find( '#whats-new-attachments .forums-attached-gif-container' )[ 0 ].style.width           = self.gif_data[ gif_container_key ].data[ i ].images.original.width + 'px';

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
				bp.Nouveau.Media.disableButtonsInToolBox(
					tool_box,
					[
						'#bb-rl-forums-media-button',
						'#bb-rl-forums-document-button',
						'#bb-rl-forums-video-button'
					]
				);
			}
		},

		selectGroupMessagesGif: function ( e ) {
			var self = this, i = 0;
			e.preventDefault();

			var containerAttachmentGif = $( '#whats-new-attachments .bp-group-messages-attached-gif-container' );
			var inputHiddenGif         = $( '#bp_group_messages_gif' );

			$( '#whats-new-toolbar .bp-group-messages-attached-gif-container' ).parent().removeClass( 'open' );
			if ( self.gif_data.length ) {
				var gifDataLength = self.gif_data.length;
				for ( i = 0; i < gifDataLength; i++ ) {
					if ( self.gif_data[ i ].id === e.currentTarget.dataset.id ) {

						containerAttachmentGif[ 0 ].style.backgroundImage = 'url(' + self.gif_data[ i ].images.fixed_width.url + ')';
						containerAttachmentGif[ 0 ].style.backgroundSize  = 'contain';
						containerAttachmentGif[ 0 ].style.height          = self.gif_data[ i ].images.original.height + 'px';
						containerAttachmentGif[ 0 ].style.width           = self.gif_data[ i ].images.original.width + 'px';
						containerAttachmentGif.find( '.gif-image-container img' ).attr( 'src', self.gif_data[ i ].images.original.url );
						containerAttachmentGif.removeClass( 'closed' );
						if ( inputHiddenGif.length ) {
							inputHiddenGif.val( JSON.stringify( self.gif_data[ i ] ) );
						}
						break;
					}
				}

				var tool_box = $( '#send_group_message_form' );
				bp.Nouveau.Media.disableButtonsInToolBox(
					tool_box,
					[
						'#bp-group-messages-media-button',
						'#bp-group-messages-document-button',
						'#bp-group-messages-video-button'
					]
				);
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
			target.closest( 'form' ).find( '#whats-new-toolbar #bb-rl-forums-gif-button' ).removeClass( 'active' );
			target.closest( 'form' ).find( '.bb-rl-gif-media-search-dropdown' ).removeClass( 'open' );
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
			['media', 'document', 'video', 'gif'].forEach(
				function ( type ) {
					if ( tool_box.find( '#bb-rl-forums-' + type + '-button' ) ) {
						tool_box.find( '#bb-rl-forums-' + type + '-button' ).parents( '.bb-rl-post-elements-buttons-item' ).removeClass( 'disable no-click' );
					}
				}
			);
		},

		resetGroupMessagesGifComponent: function () {

			var containerAttachment = $( '#whats-new-attachments .bp-group-messages-attached-gif-container' ),
				inputHiddenGif      = $( '#bp_group_messages_gif' );

			$( '#whats-new-toolbar .bp-group-messages-attached-gif-container' ).parent().removeClass( 'open' );
			$( '#whats-new-toolbar #bp-group-messages-gif-button' ).removeClass( 'active' );
			containerAttachment.addClass( 'closed' );
			containerAttachment.find( '.gif-image-container img' ).attr( 'src', '' );
			containerAttachment[ 0 ].style = '';
			if ( inputHiddenGif.length ) {
				inputHiddenGif.val( '' );
			}

			var tool_box = $( '#send_group_message_form' );
			['media', 'document', 'video'].forEach(
				function ( type ) {
					if ( tool_box.find( '#bp-group-messages-' + type + '-button' ) ) {
						tool_box.find( '#bp-group-messages-' + type + '-button' ).parents( '.bb-rl-post-elements-buttons-item' ).removeClass( 'disable' );
					}
				}
			);
		},

		searchGif: function ( e ) {
			// Prevent search dropdown from closing with enter key.
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
			var self        = this;
			self.gif_q      = e.target.value;
			self.gif_offset = 0;
			var i           = 0;

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
						var li_html = '', responseDataLength = response.data.length;
						for ( i = 0; i < responseDataLength; i++ ) {
							var bgNo                 = Math.floor( Math.random() * ( 6 - 1 + 1 ) ) + 1;
							var strictWidth          = window.innerWidth > 768 ? 140 : 130;
							var originalWidth        = response.data[ i ].images.original.width;
							var originalHeight       = response.data[ i ].images.original.height;
							var relativeHeight       = (strictWidth * originalHeight) / originalWidth;
							li_html                 += '<li class="bg' + bgNo + '" style="height: ' + relativeHeight + 'px;">\n' +
								'\t<a class="found-media-item" href="' + response.data[ i ].images.original.url + '" data-id="' + response.data[ i ].id + '">\n' +
								'\t\t<img src="' + response.data[ i ].images.fixed_width.url + '" alt="" alt="">\n' +
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

			self.gif_data[ gif_container_key ].q      = e.target.value;
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
						var li_html            = '',
							responseDataLength = response.data.length;
						for ( i = 0; i < responseDataLength; i++ ) {
							var bgNo                 = Math.floor( Math.random() * ( 6 - 1 + 1 ) ) + 1;
							var strictWidth          = window.innerWidth > 768 ? 140 : 130;
							var originalWidth        = response.data[ i ].images.original.width;
							var originalHeight       = response.data[ i ].images.original.height;
							var relativeHeight       = (strictWidth * originalHeight) / originalWidth;
							li_html                 += '<li class="bg' + bgNo + '" style="height: ' + relativeHeight + 'px;">\n' +
								'\t<a class="found-media-item" href="' + response.data[ i ].images.original.url + '" data-id="' + response.data[ i ].id + '">\n' +
								'\t\t<img src="' + response.data[ i ].images.fixed_width.url + '" alt="">\n' +
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
				var gifDataLength = self.gif_data[ gif_container_key ].requests.length;
				for ( var i = 0; i < gifDataLength; i++ ) {
					self.gif_data[ gif_container_key ].requests[ i ].abort();
				}

				$( '[data-key="' + gif_container_key + '"]' ).closest( 'form' ).find( '.gif-search-results-list li' ).remove();

				self.gif_data[ gif_container_key ].requests = [];
				self.gif_data[ gif_container_key ].data     = [];
				self.gif_data.splice( gif_container_key, 1 );
			}
		},

		toggleGifSelector: function ( event ) {
			var self                = this, target = $( event.currentTarget ),
				gif_search_dropdown = target.closest( 'form' ).find( '.bb-rl-gif-media-search-dropdown' ), i = 0;
			event.preventDefault();

			if ( typeof window.Giphy !== 'undefined' && typeof bbRlMedia.gif_api_key !== 'undefined' ) {
				self.giphy = new window.Giphy( bbRlMedia.gif_api_key );

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
							var li_html            = '',
								responseDataLength = response.data.length;
							for ( i = 0; i < responseDataLength; i++ ) {
								var bgNo                 = Math.floor( Math.random() * ( 6 - 1 + 1 ) ) + 1;
								var strictWidth          = window.innerWidth > 768 ? 140 : 130;
								var originalWidth        = response.data[ i ].images.original.width;
								var originalHeight       = response.data[ i ].images.original.height;
								var relativeHeight       = (strictWidth * originalHeight) / originalWidth;
								li_html                 += '<li class="bg' + bgNo + '" style="height: ' + relativeHeight + 'px;">\n' +
									'\t<a class="found-media-item" href="' + response.data[ i ].images.original.url + '" data-id="' + response.data[ i ].id + '">\n' +
									'\t\t<img src="' + response.data[ i ].images.fixed_width.url + '" alt="">\n' +
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
			if ( gif_box.length > 0 && gif_box.attr( 'src' ) !== '' ) {
				target.addClass( 'active' );
			} else {
				target.toggleClass( 'active' );
			}

			var $forums_media_container = target.closest( 'form' ).find( '#bb-rl-forums-post-media-uploader' );
			if ( $forums_media_container.length ) {
				self.resetForumsMediaComponent( $forums_media_container.data( 'key' ) );
			}
			var $forums_document_container = target.closest( 'form' ).find( '#bb-rl-forums-post-document-uploader' );
			if ( $forums_document_container.length ) {
				self.resetForumsDocumentComponent( $forums_document_container.data( 'key' ) );
			}

			var $forums_video_container = target.closest( 'form' ).find( '#bb-rl-forums-post-video-uploader' );
			if ( $forums_video_container.length ) {
				self.resetForumsVideoComponent( $forums_video_container.data( 'key' ) );
			}

			// Scroll down to show GIF picker in full size.
			if ( $( window ).width() <= 544 ) {
				var FormPopup = target.closest( '.bb-modal' );
				jQuery( FormPopup ).scrollTop( FormPopup[0].scrollHeight );
			}
		},

		closePickersOnEsc: function ( event ) {
			var target = $( event.currentTarget );
			if ( event.key === 'Escape' || event.keyCode === 27 ) {
				if ( ! _.isUndefined( bbRlMedia ) && ! _.isUndefined( bbRlMedia.gif_api_key ) ) {
					target.find( 'form' ).find( '.bb-rl-gif-media-search-dropdown' ).removeClass( 'open' );
					if ( $( '.bb-rl-gif-media-search-dropdown-standalone.open' ).length > 0 ) {
						target.find( '.bb-rl-gif-media-search-dropdown-standalone' ).removeClass( 'open' );
					}
					target.find( '#bbpress-forums form' ).each(
						function () {
							var $this   = jQuery( this );
							var gif_box = $this.find( '#whats-new-attachments .forums-attached-gif-container img' );
							if ( gif_box.length > 0 && gif_box.attr( 'src' ) !== '' ) {
									$this.find( '#bb-rl-forums-gif-button' ).addClass( 'active' );
							} else {
								$this.find( '#bb-rl-forums-gif-button' ).removeClass( 'active' );
							}
						}
					);

					target.find( '#send_group_message_form' ).each(
						function () {
							var $this                  = jQuery( this );
							var gif_box_group_messaage = $this.find( '#whats-new-attachments .bp-group-messages-attached-gif-container img' );
							if ( gif_box_group_messaage.length > 0 && gif_box_group_messaage.attr( 'src' ) !== '' ) {
									$this.find( '#bp-group-messages-gif-button' ).addClass( 'active' );
							} else {
								$this.find( '#bp-group-messages-gif-button' ).removeClass( 'active' );
							}
						}
					);

					target.find( '.bb-rl-activity-comments form' ).each(
						function () {
							var $this           = jQuery( this );
							var gif_box_comment = $this.find( '.ac-textarea' ).find( '.bb-rl-ac-reply-attachments .activity-attached-gif-container' );
							if ( gif_box_comment.length && $.trim( gif_box_comment.html() ) !== '' ) {
									$this.find( '.bb-rl-ac-reply-gif-button' ).addClass( 'active' );
							} else {
								$this.find( '.bb-rl-ac-reply-gif-button' ).removeClass( 'active' );
							}
						}
					);
				}
			}
		},

		closePickersOnClick: function ( event ) {
			var $targetEl = $( event.target );
			var target    = $( event.currentTarget );

			if ( ! _.isUndefined( bbRlMedia ) && ! _.isUndefined( bbRlMedia.gif_api_key ) &&
				! $targetEl.closest( '.bb-rl-post-gif' ).length ) {
				if ( $targetEl.closest( '.bb-rl-gif-media-search-dropdown' ).length ) {
					return;
				}
				target.find( 'form' ).find( '.bb-rl-gif-media-search-dropdown' ).removeClass( 'open' );
				if ( $( '.bb-rl-gif-media-search-dropdown-standalone.open' ).length > 0 ) {
					target.find( '.bb-rl-gif-media-search-dropdown-standalone' ).removeClass( 'open' );
				}
				target.find( '#bbpress-forums form' ).each(
					function () {
						var $this   = jQuery( this );
						var gif_box = $this.find( '#whats-new-attachments .forums-attached-gif-container img' );
						if ( gif_box.length > 0 && gif_box.attr( 'src' ) !== '' ) {
								$this.find( '#bb-rl-forums-gif-button' ).addClass( 'active' );
						} else {
							$this.find( '#bb-rl-forums-gif-button' ).removeClass( 'active' );
						}
					}
				);

				target.find( '#send_group_message_form' ).each(
					function () {
						var $this                  = jQuery( this );
						var gif_box_group_messaage = $this.find( '#whats-new-attachments .bp-group-messages-attached-gif-container img' );
						if ( gif_box_group_messaage.length > 0 && gif_box_group_messaage.attr( 'src' ) !== '' ) {
								$this.find( '#bp-group-messages-gif-button' ).addClass( 'active' );
						} else {
							$this.find( '#bp-group-messages-gif-button' ).removeClass( 'active' );
						}
					}
				);

				target.find( '.bb-rl-activity-modal form' ).each(
					function () {
						var $this           = jQuery( this );
						var gif_box_comment = $this.find( '.bb-rl-ac-reply-attachments .bb-rl-activity-attached-gif-container' );
						if ( gif_box_comment.length && $.trim( gif_box_comment.html() ) !== '' ) {
								$this.find( '.bb-rl-ac-reply-gif-button' ).addClass( 'active' );
						} else {
							$this.find( '.bb-rl-ac-reply-gif-button' ).removeClass( 'active' );
						}
					}
				);
			}
		},

		toggleGroupMessagesGifSelector: function ( event ) {
			var self                = this, target = $( event.currentTarget ),
				gif_search_dropdown = target.closest( 'form' ).find( '.bb-rl-gif-media-search-dropdown' ), i = 0;
			event.preventDefault();

			if ( typeof window.Giphy !== 'undefined' && typeof bbRlMedia.gif_api_key !== 'undefined' && self.giphy == null ) {
				self.giphy        = new window.Giphy( bbRlMedia.gif_api_key );
				self.gif_offset   = 0;
				self.gif_q        = null;
				self.gif_limit    = 20;
				self.gif_requests = [];
				self.gif_data     = [];
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
							var li_html            = '',
								responseDataLength = response.data.length;
							for ( i = 0; i < responseDataLength; i++ ) {
								var bgNo                 = Math.floor( Math.random() * ( 6 - 1 + 1 ) ) + 1;
								var strictWidth          = window.innerWidth > 768 ? 140 : 130;
								var originalWidth        = response.data[ i ].images.original.width;
								var originalHeight       = response.data[ i ].images.original.height;
								var relativeHeight       = (strictWidth * originalHeight) / originalWidth;
								li_html                 += '<li class="bg' + bgNo + '" style="height: ' + relativeHeight + 'px;">\n' +
									'\t<a class="found-media-item" href="' + response.data[ i ].images.original.url + '" data-id="' + response.data[ i ].id + '">\n' +
									'\t\t<img src="' + response.data[ i ].images.fixed_width.url + '" alt="">\n' +
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
			if ( gif_box.length > 0 && gif_box.attr( 'src' ) !== '' ) {
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
			self.resetGroupMessagesAttachmentComponent(
				{
					dropzoneObj  : self.dropzone_obj,
					dropzoneData : self.dropzone_media,
					type         : 'media',
				}
			);
		},

		resetGroupMessagesDocumentComponent: function () {
			var self = this;
			self.resetGroupMessagesAttachmentComponent(
				{
					dropzoneObj  : self.document_dropzone_obj,
					dropzoneData : self.dropzone_media,
					type         : 'document',
				}
			);
		},

		resetGroupMessagesVideoComponent: function () {
			var self = this;
			self.resetGroupMessagesAttachmentComponent(
				{
					dropzoneObj  : self.video_dropzone_obj,
					dropzoneData : self.dropzone_media,
					type         : 'video',
				}
			);
		},

		resetForumsMediaComponent: function ( dropzone_container_key ) {
			var self = this;
			self.resetForumsAttachmentComponent(
				{
					dropzoneObj       : self.dropzone_obj,
					dropzoneData      : self.dropzone_media,
					type              : 'media',
					dropzoneContainer : dropzone_container_key
				}
			);
		},

		resetForumsDocumentComponent: function ( dropzone_container_key ) {
			var self = this;
			self.resetForumsAttachmentComponent(
				{
					dropzoneObj       : self.dropzone_obj,
					dropzoneData      : self.dropzone_media,
					type              : 'document',
					dropzoneContainer : dropzone_container_key
				}
			);
		},

		resetForumsVideoComponent: function ( dropzone_container_key ) {
			var self = this;
			self.resetForumsAttachmentComponent(
				{
					dropzoneObj       : self.dropzone_obj,
					dropzoneData      : self.dropzone_media,
					type              : 'video',
					dropzoneContainer : dropzone_container_key
				}
			);
		},

		openForumsUploader: function ( event ) {
			var self                           = this, target = $( event.currentTarget ),
				dropzone_container             = target.closest( 'form' ).find( '#bb-rl-forums-post-media-uploader' ),
				forum_dropzone_container       = target.closest( 'form' ).find( '#bb-rl-forums-post-document-uploader' ),
				forum_video_dropzone_container = target.closest( 'form' ).find( '#bb-rl-forums-post-video-uploader' ),
				edit_medias                    = [];
			event.preventDefault();

			target.toggleClass( 'active' );

			var forum_dropzone_obj_key       = forum_dropzone_container.data( 'key' );
			var forum_video_dropzone_obj_key = forum_video_dropzone_container.data( 'key' );
			self.resetForumsDocumentComponent( forum_dropzone_obj_key );
			self.resetForumsVideoComponent( forum_video_dropzone_obj_key );

			if ( typeof window.Dropzone !== 'undefined' && dropzone_container.length ) {

				var dropzone_obj_key = dropzone_container.data( 'key' );
				if ( dropzone_container.hasClass( 'closed' ) ) {

					// init dropzone.
					self.dropzone_obj[ dropzone_obj_key ]   = new Dropzone( dropzone_container[ 0 ], self.options );
					self.dropzone_media[ dropzone_obj_key ] = [];

					self.setupForumDropzoneEvents(
						{
							self              : self,
							dropzoneObj       : self.dropzone_obj[ dropzone_obj_key ],
							dropzoneDataObj   : self.dropzone_media[ dropzone_obj_key ],
							target            : target,
							type              : 'media',
							dropzoneContainer : dropzone_container
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
							var replyTopicId = parseInt( $( '#new-post' ).find( '#bbp_reply_id' ).val() );
							bp.Readylaunch.Utilities.injectFiles(
								{
									commonData        : edit_medias,
									id                : replyTopicId,
									self              : this,
									fileType          : 'media',
									dropzoneObj       : self.dropzone_obj[ dropzone_obj_key ],
									dropzoneData      : self.dropzone_media[ dropzone_obj_key ],
									dropzoneContainer : dropzone_container,
								}
							);
							self.addAttachmentIdsToForumsForm( dropzone_container, self.dropzone_media[ dropzone_obj_key ], 'media' );

							// Disable other buttons( document/gif ).
							if ( ! _.isNull( self.dropzone_obj[ dropzone_obj_key ].files ) && self.dropzone_obj[ dropzone_obj_key ].files.length !== 0 ) {
								var tool_box = target.closest( 'form' );
								['media', 'document', 'video', 'gif'].forEach(
									function ( type ) {
										var $button = tool_box.find( '#bb-rl-forums-' + type + '-button' );
										if ( $button ) {
											if ( 'media' === type ) {
												$button.removeClass( 'no-click' );
											} else {
												$button.parents( '.bb-rl-post-elements-buttons-item' ).addClass( 'disable' );
											}
										}
									}
								);
							}

						}
					}

				} else {
					self.resetForumsMediaComponent( dropzone_obj_key );
				}

			}

		},

		openForumsDocumentUploader: function ( event ) {
			var self                     = this, target = $( event.currentTarget ),
				dropzone_container       = target.closest( 'form' ).find( '#bb-rl-forums-post-document-uploader' ),
				media_dropzone_container = target.closest( 'form' ).find( '#bb-rl-forums-post-media-uploader' ),
				video_dropzone_container = target.closest( 'form' ).find( '#bb-rl-forums-post-video-uploader' ),
				edit_documents           = [];
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
					self.dropzone_obj[ dropzone_obj_key ]   = new Dropzone( dropzone_container[ 0 ], self.documentOptions );
					self.dropzone_media[ dropzone_obj_key ] = [];

					self.setupForumDropzoneEvents(
						{
							self              : self,
							dropzoneObj       : self.dropzone_obj[ dropzone_obj_key ],
							dropzoneDataObj   : self.dropzone_media[ dropzone_obj_key ],
							target            : target,
							type              : 'document',
							dropzoneContainer : dropzone_container
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
							var replyTopicId = parseInt( $( '#new-post' ).find( '#bbp_reply_id' ).val() );
							bp.Readylaunch.Utilities.injectFiles(
								{
									commonData        : edit_documents,
									id                : replyTopicId,
									self              : this,
									fileType          : 'document',
									dropzoneObj       : self.dropzone_obj[ dropzone_obj_key ],
									dropzoneData      : self.dropzone_media[ dropzone_obj_key ],
									dropzoneContainer : dropzone_container,
								}
							);
							self.addAttachmentIdsToForumsForm( dropzone_container, self.dropzone_media[ dropzone_obj_key ], 'document' );

							// Disable other buttons( media/gif ).
							if ( ! _.isNull( self.dropzone_obj[ dropzone_obj_key ].files ) && self.dropzone_obj[ dropzone_obj_key ].files.length !== 0 ) {
								var tool_box = target.closest( 'form' );
								['media', 'document', 'video', 'gif'].forEach(
									function ( type ) {
										var $button = tool_box.find( '#bb-rl-forums-' + type + '-button' );
										if ( $button ) {
											if ( 'document' === type ) {
												$button.removeClass( 'no-click' );
											} else {
												$button.parents( '.bb-rl-post-elements-buttons-item' ).addClass( 'disable' );
											}
										}
									}
								);
							}

						}
					}

				} else {
					self.resetForumsDocumentComponent( dropzone_obj_key );
				}

			}

		},

		openForumsVideoUploader: function ( event ) {
			var self                              = this, target = $( event.currentTarget ),
				dropzone_container                = target.closest( 'form' ).find( '#bb-rl-forums-post-video-uploader' ),
				forum_dropzone_container          = target.closest( 'form' ).find( '#bb-rl-forums-post-media-uploader' ),
				forum_document_dropzone_container = target.closest( 'form' ).find( '#bb-rl-forums-post-document-uploader' ),
				edit_videos                       = [];
			event.preventDefault();

			target.toggleClass( 'active' );

			var media_dropzone_obj_key    = forum_dropzone_container.data( 'key' );
			var document_dropzone_obj_key = forum_document_dropzone_container.data( 'key' );

			self.resetForumsMediaComponent( media_dropzone_obj_key );
			self.resetForumsDocumentComponent( document_dropzone_obj_key );

			if ( typeof window.Dropzone !== 'undefined' && dropzone_container.length ) {

				var dropzone_obj_key = dropzone_container.data( 'key' );

				if ( dropzone_container.hasClass( 'closed' ) ) {

					// init dropzone.
					self.dropzone_obj[ dropzone_obj_key ]   = new Dropzone( dropzone_container[ 0 ], self.videoOptions );
					self.dropzone_media[ dropzone_obj_key ] = [];

					self.setupForumDropzoneEvents(
						{
							self              : self,
							dropzoneObj       : self.dropzone_obj[ dropzone_obj_key ],
							dropzoneDataObj   : self.dropzone_media[ dropzone_obj_key ],
							target            : target,
							type              : 'video',
							dropzoneContainer : dropzone_container
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
							var replyTopicId = parseInt( $( '#new-post' ).find( '#bbp_reply_id' ).val() );
							bp.Readylaunch.Utilities.injectFiles(
								{
									commonData        : edit_videos,
									id                : replyTopicId,
									self              : this,
									fileType          : 'video',
									dropzoneObj       : self.dropzone_obj[ dropzone_obj_key ],
									dropzoneData      : self.dropzone_media[ dropzone_obj_key ],
									dropzoneContainer : dropzone_container,
								}
							);
							self.addAttachmentIdsToForumsForm( dropzone_container, self.dropzone_media[ dropzone_obj_key ], 'video' );

							// Disable other buttons( media/gif ).
							if ( ! _.isNull( self.dropzone_obj[ dropzone_obj_key ].files ) && self.dropzone_obj[ dropzone_obj_key ].files.length !== 0 ) {
								var tool_box = target.closest( 'form' );
								['media', 'document', 'video', 'gif'].forEach(
									function ( type ) {
										var $button = tool_box.find( '#bb-rl-forums-' + type + '-button' );
										if ( $button ) {
											if ( 'video' === type ) {
												$button.removeClass( 'no-click' );
											} else {
												$button.parents( '.bb-rl-post-elements-buttons-item' ).addClass( 'disable' );
											}
										}
									}
								);
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

		openGroupMessagesUploader: function ( event ) {
			var self   = this, dropzone_container = $( 'div#bp-group-messages-post-media-uploader' ),
				target = $( event.currentTarget );
			event.preventDefault();

			target.toggleClass( 'active' );

			if ( typeof window.Dropzone !== 'undefined' && dropzone_container.length ) {

				if ( dropzone_container.hasClass( 'closed' ) ) {

					// init dropzone.
					self.dropzone_obj = new Dropzone( 'div#bp-group-messages-post-media-uploader', self.options );

					self.setupGroupMessageDropzoneEvents(
						{
							self              : self,
							dropzoneObj       : self.dropzone_obj,
							dropzoneDataObj   : self.dropzone_media,
							target            : target,
							type              : 'media',
							dropzoneContainer : dropzone_container
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
			var self   = this, document_dropzone_container = $( 'div#bp-group-messages-post-document-uploader' ),
				target = $( event.currentTarget );
			event.preventDefault();

			target.toggleClass( 'active' );
			if ( typeof window.Dropzone !== 'undefined' && document_dropzone_container.length ) {
				if ( document_dropzone_container.hasClass( 'closed' ) ) {
					// init dropzone.
					self.document_dropzone_obj = new Dropzone( 'div#bp-group-messages-post-document-uploader', self.documentOptions );

					self.setupGroupMessageDropzoneEvents(
						{
							self              : self,
							dropzoneObj       : self.document_dropzone_obj,
							dropzoneDataObj   : self.dropzone_media,
							target            : target,
							type              : 'document',
							dropzoneContainer : document_dropzone_container
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
			var self   = this, video_dropzone_container = $( 'div#bp-group-messages-post-video-uploader' ),
				target = $( event.currentTarget );
			event.preventDefault();

			target.toggleClass( 'active' );

			if ( typeof window.Dropzone !== 'undefined' && video_dropzone_container.length ) {

				if ( video_dropzone_container.hasClass( 'closed' ) ) {

					// init dropzone.
					self.video_dropzone_obj = new Dropzone( 'div#bp-group-messages-post-video-uploader', self.videoOptions );

					self.setupGroupMessageDropzoneEvents(
						{
							self              : self,
							dropzoneObj       : self.video_dropzone_obj,
							dropzoneDataObj   : self.dropzone_media,
							target            : target,
							type              : 'video',
							dropzoneContainer : video_dropzone_container
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

		addAttachmentIdsToForumsForm : function ( dropzoneContainer, dropzoneDataObj, type ) {
			if ( dropzoneContainer.closest( '#whats-new-attachments' ).find( '#bbp_' + type ).length ) {
				dropzoneContainer.closest( '#whats-new-attachments' ).find( '#bbp_' + type ).val( JSON.stringify( dropzoneDataObj ) );
			}
		},

		openUploader: function ( event ) {
			var self = this;
			event.preventDefault();
			var currentTarget, parentsOpen,
				$document     = $( document ),
				mediaUploader = $( '#bp-media-uploader' );
			if ( typeof window.Dropzone !== 'undefined' && $( 'div#media-uploader' ).length ) {

				mediaUploader.addClass( 'open-popup' ).show();

				if ( $( event.currentTarget ).closest( '#bp-media-single-album' ).length ) {
					$( '#bb-media-privacy' ).hide();
				}

				if ( mediaUploader.find( '.bb-field-steps.bb-field-steps-2' ).length ) {
					currentTarget = '#bp-media-uploader.bp-media-photo-uploader';
					if ( Number( $( currentTarget ).find( '.bb-rl-album-selected-id' ).data( 'value' ) ) !== 0 ) {
						parentsOpen = $( currentTarget ).find( '.bb-rl-album-selected-id' ).data( 'value' );
						$( currentTarget ).find( '#bb-document-privacy' ).prop( 'disabled', true );
					} else {
						parentsOpen = 0;
					}
					if ( '' !== this.moveToIdPopup ) {
						$.ajax(
							{
								url: bbRlAjaxUrl,
								type: 'post',
								data: {
									action: 'media_get_album_view',
									id: this.moveToIdPopup,
									type: this.moveToTypePopup,
								}, success: function ( response ) {
									$document.find( '.bb-rl-location-album-list-wrap h4 span.bb-rl-where-to-move-profile-or-group-media' ).html( response.data.first_span_text );
									if ( '' === response.data.html ) {
										$document.find( '.open-popup .bb-rl-location-album-list-wrap' ).hide();
										$document.find( '.open-popup .bb-rl-location-album-list-wrap-main span.bb-rl-no-album-exists' ).show();
									} else {
										$document.find( '.open-popup .bb-rl-location-album-list-wrap-main span.bb-rl-no-album-exists' ).hide();
										$document.find( '.open-popup .bb-rl-location-album-list-wrap' ).show();
									}

									if ( false === response.data.create_album ) {
										$document.find( '.open-popup .bb-rl-media-open-create-popup-folder' ).removeClass( 'create-album' );
										$document.find( '.open-popup .bb-rl-media-open-create-popup-folder' ).hide();
									} else {
										$document.find( '.open-popup .bb-rl-media-open-create-popup-folder' ).addClass( 'create-album' );
									}

									$document.find( '.bb-rl-popup-on-fly-create-album .bb-rl-privacy-field-wrap-hide-show' ).show();
									$document.find( '.open-popup .bb-rl-album-create-from' ).val( 'profile' );

									$( currentTarget ).find( '.bb-rl-location-album-list-wrap .location-album-list' ).remove();
									$( currentTarget ).find( '.bb-rl-location-album-list-wrap' ).append( response.data.html );
									$( currentTarget ).find( 'ul.location-album-list span[data-id="' + parentsOpen + '"]' ).trigger( 'click' );
									$( currentTarget ).find( '.bb-rl-album-selected-id' ).val( parentsOpen );
								}
							}
						);
					}
				}

				$document.on(
					'click',
					currentTarget + ' .location-album-list li span',
					function ( e ) {
						e.preventDefault();
						if ( $( this ).parent().hasClass( 'is_active' ) ) {
							return;
						}
						if ( $( this ).closest( '.bb-rl-location-album-list-wrap' ).find( '.breadcrumb .item span:last-child' ).data( 'id' ) !== 0 ) {
							$( this ).closest( '.bb-rl-location-album-list-wrap' ).find( '.breadcrumb .item span:last-child' ).remove();
						}
						$( this ).closest( '.bb-rl-location-album-list-wrap' ).find( '.breadcrumb .item' ).append( '<span class="is-disabled" data-id="' + $( this ).attr( 'id' ) + '">' + $( this ).text() + '</span>' );
						$( this ).addClass( 'selected' ).parent().addClass( 'is_active' ).siblings().removeClass( 'is_active' ).children( 'span' ).removeClass( 'selected' );
						if ( parentsOpen === $( e.currentTarget ).data( 'id' ) ) {
							$( e.currentTarget ).closest( '.bb-rl-field-wrap' ).find( '.bb-model-footer .bb-rl-media-move' ).addClass( 'is-disabled' );
						} else {
							$( e.currentTarget ).closest( '.bb-rl-field-wrap' ).find( '.bb-model-footer .bb-rl-media-move' ).removeClass( 'is-disabled' );
						}
						if ( $( e.currentTarget ).closest( '.bb-rl-field-wrap' ).find( '.bb-model-footer .bb-rl-media-move' ).hasClass( 'is-disabled' ) ) {
							return; // return if parent album is same.
						}
						$( e.currentTarget ).closest( '.bb-rl-field-wrap' ).find( '.bb-rl-album-selected-id' ).val( $( e.currentTarget ).data( 'id' ) );

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
					}
				);

				$document.on(
					'click',
					currentTarget + ' .breadcrumb .item > span',
					function ( e ) {

						if ( $( this ).hasClass( 'is-disabled' ) ) {
							return;
						}

						$( e.currentTarget ).closest( '.bb-rl-field-wrap' ).find( '.bb-rl-album-selected-id' ).val( 0 );
						$( e.currentTarget ).closest( '.bb-rl-field-wrap' ).find( '.location-album-list li span' ).removeClass( 'selected' ).parent().removeClass( 'is_active' );

						if ( $( this ).closest( '.bb-rl-location-album-list-wrap' ).find( '.breadcrumb .item span:last-child' ).hasClass( 'is-disabled' ) ) {
							$( this ).closest( '.bb-rl-location-album-list-wrap' ).find( '.breadcrumb .item span:last-child' ).remove();
						}

						if ( parentsOpen === $( e.currentTarget ).data( 'id' ) ) {
							$( e.currentTarget ).closest( '.bb-rl-field-wrap' ).find( '.bb-model-footer .bb-rl-media-move' ).addClass( 'is-disabled' );
						} else {
							$( e.currentTarget ).closest( '.bb-rl-field-wrap' ).find( '.bb-model-footer .bb-rl-media-move' ).removeClass( 'is-disabled' );
						}
						var mediaPrivacy         = $( e.currentTarget ).closest( '#bp-media-uploader' ).find( '#bb-media-privacy' );
						var selectedAlbumPrivacy = $( e.currentTarget ).closest( '#bp-media-uploader' ).find( '.location-album-list li.is_active' ).data( 'privacy' );
						if ( Number( $( e.currentTarget ).closest( '.bb-rl-field-wrap' ).find( '.bb-rl-album-selected-id' ).val() ) !== 0 ) {
							mediaPrivacy.find( 'option' ).removeAttr( 'selected' );
							mediaPrivacy.val( selectedAlbumPrivacy === undefined ? 'public' : selectedAlbumPrivacy );
							mediaPrivacy.prop( 'disabled', true );
						} else {
							mediaPrivacy.find( 'option' ).removeAttr( 'selected' );
							mediaPrivacy.val( 'public' );
							mediaPrivacy.prop( 'disabled', false );
						}

					}
				);

				self.options.previewTemplate = document.getElementsByClassName( 'uploader-post-media-template' ).length ? document.getElementsByClassName( 'uploader-post-media-template' )[ 0 ].innerHTML : '';

				self.dropzone_obj = new Dropzone( 'div#media-uploader', self.options );

				self.dropzone_obj.on(
					'sending',
					function ( file, xhr, formData ) {
						formData.append( 'action', 'media_upload' );
						formData.append( '_wpnonce', bbRlNonce.media );
					}
				);

				self.dropzone_obj.on(
					'uploadprogress',
					function ( element ) {
						var circle        = $( element.previewElement ).find( '.dz-progress-ring circle' )[0];
						var radius        = circle.r.baseVal.value;
						var circumference = radius * 2 * Math.PI;

						circle.style.strokeDasharray  = circumference + ' ' + circumference;
						circle.style.strokeDashoffset = circumference;
						circle.style.strokeDashoffset = circumference - element.upload.progress.toFixed( 0 ) / 100 * circumference;
					}
				);

				self.dropzone_obj.on(
					'addedfile',
					function () {
						setTimeout(
							function () {
								if ( self.dropzone_obj.getAcceptedFiles().length ) {
									$( '#bp-media-uploader-modal-status-text' ).text( wp.i18n.sprintf( bbRlMedia.i18n_strings.upload_status, self.dropzone_media.length, self.dropzone_obj.getAcceptedFiles().length ) ).show();
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
							} else if ( file.status === 'error' && ( file.xhr && file.xhr.status === 0) ) { // update server error text to user friendly.
								$( file.previewElement ).find( '.dz-error-message span' ).text( bbRlMedia.connection_lost_error );
							}
						} else {
							if ( ! jQuery( '.media-error-popup' ).length ) {
								$( 'body' ).append( '<div id="bp-media-create-folder" style="display: block;" class="open-popup media-error-popup"><transition name="modal"><div class="bb-rl-modal-mask bb-white bbm-model-wrap"><div class="bb-rl-modal-wrapper"><div id="bb-rl-media-create-album-popup" class="modal-container bb-rl-has-folderlocationUI"><header class="bb-model-header"><h4>' + bbRlMedia.invalid_media_type + '</h4><a class="bb-model-close-button errorPopup" href="#"><span class="bb-icon-l bb-icon-times"></span></a></header><div class="bb-rl-field-wrap"><p>' + response + '</p></div></div></div></div></transition></div>' );
							}
							this.removeFile( file );
						}
					}
				);

				self.dropzone_obj.on(
					'queuecomplete',
					function () {
						$( '#bp-media-uploader-modal-title' ).text( bbRlMedia.i18n_strings.upload );
					}
				);

				self.dropzone_obj.on(
					'processing',
					function () {
						$( '#bp-media-uploader-modal-title' ).text( bbRlMedia.i18n_strings.uploading + '...' );
					}
				);

				self.dropzone_obj.on(
					'success',
					function ( file, response ) {
						if ( response.data.id ) {
							file.id                  = response.id;
							response.data.uuid       = file.upload.uuid;
							response.data.menu_order = self.dropzone_media.length;
							response.data.album_id   = self.album_id;
							response.data.group_id   = self.group_id;
							response.data.saved      = false;
							self.dropzone_media.push( response.data );
						} else {
							if ( ! jQuery( '.media-error-popup' ).length ) {
								$( 'body' ).append( '<div id="bp-media-create-folder" style="display: block;" class="open-popup media-error-popup"><transition name="modal"><div class="bb-rl-modal-mask bb-white bbm-model-wrap"><div class="bb-rl-modal-wrapper"><div id="bb-rl-media-create-album-popup" class="modal-container bb-rl-has-folderlocationUI"><header class="bb-model-header"><h4>' + bbRlMedia.invalid_media_type + '</h4><a class="bb-model-close-button errorPopup" href="#"><span class="bb-icon-l bb-icon-times"></span></a></header><div class="bb-rl-field-wrap"><p>' + response.data.feedback + '</p></div></div></div></div></transition></div>' );
							}
							this.removeFile( file );
						}

						$( '.bb-field-steps-1 #bp-media-photo-next, #bp-media-submit' ).show();
						$( '.modal-container' ).addClass( 'modal-container--alert' );
						$( '.bb-field-steps-1' ).addClass( 'controls-added' );
						$( '#bp-media-add-more' ).show();
						$( '#bp-media-uploader-modal-title' ).text( bbRlMedia.i18n_strings.uploading + '...' );
						$( '#bp-media-uploader-modal-status-text' ).text( wp.i18n.sprintf( bbRlMedia.i18n_strings.upload_status, self.dropzone_media.length, self.dropzone_obj.getAcceptedFiles().length ) ).show();
					}
				);

				self.dropzone_obj.on(
					'removedfile',
					function ( file ) {
						if ( self.dropzone_media.length ) {
							for ( var i in self.dropzone_media ) {
								if ( file.upload.uuid === self.dropzone_media[ i ].uuid ) {

									if ( typeof self.dropzone_media[ i ].saved !== 'undefined' && ! self.dropzone_media[ i ].saved ) {
										self.removeAttachment( self.dropzone_media[ i ].id );
									}

									self.dropzone_media.splice( i, 1 );
									break;
								}
							}
						}
						if ( ! self.dropzone_obj.getAcceptedFiles().length ) {
							$( '#bp-media-uploader-modal-status-text' ).text( '' );
							$( '#bp-media-add-more, #bp-media-photo-next' ).hide();
							$( '.bb-field-steps-1' ).removeClass( 'controls-added' );
							$( '#bp-media-submit' ).hide();
							$( '.modal-container' ).removeClass( 'modal-container--alert' );
						} else {
							$( '#bp-media-uploader-modal-status-text' ).text( wp.i18n.sprintf( bbRlMedia.i18n_strings.upload_status, self.dropzone_media.length, self.dropzone_obj.getAcceptedFiles().length ) ).show();
						}
					}
				);
			}
		},

		openDocumentUploader: function ( event ) {
			var self = this;
			var currentTarget;
			event.preventDefault();
			var $document     = $( document ),
				mediaUploader = $( '#bp-media-uploader' );
			if ( typeof window.Dropzone !== 'undefined' && $( 'div#media-uploader' ).length ) {

				if ( mediaUploader.hasClass( 'bp-media-document-uploader' ) ) {

					if ( ! this.currentTargetParent ) {
						this.currentTargetParent = 0;
					}
				}

				if ( $( event.currentTarget ).closest( '#bp-media-single-folder' ).length ) {
					$( '#bb-document-privacy' ).hide();
				}

				$document.removeClass( 'open-popup' );
				mediaUploader.show();
				mediaUploader.addClass( 'open-popup' );

				if ( $( '#bp-media-uploader.bp-media-document-uploader' ).find( '.bb-field-steps.bb-field-steps-2' ).length ) {
					currentTarget = '#bp-media-uploader.bp-media-document-uploader';
					var parentsOpen;
					if ( Number( $( currentTarget ).find( '.bb-rl-folder-selected-id' ).data( 'value' ) ) !== 0 ) {
						parentsOpen = $( currentTarget ).find( '.bb-rl-folder-selected-id' ).data( 'value' );
						$( currentTarget ).find( '#bb-document-privacy' ).prop( 'disabled', true );
					} else {
						parentsOpen = 0;
					}
					if ( '' !== this.moveToIdPopup ) {
						$.ajax(
							{
								url: bbRlAjaxUrl,
								type: 'GET',
								data: {
									action: 'document_get_folder_view',
									id: this.moveToIdPopup,
									type: this.moveToTypePopup,
								}, success: function ( response ) {
									$document.find( '.bp-media-document-uploader .bb-rl-location-folder-list-wrap h4 span.bb-rl-where-to-move-profile-or-group-document' ).html( response.data.first_span_text );
									if ( '' === response.data.html ) {
										$document.find( '.bp-media-document-uploader.open-popup .bb-rl-location-folder-list-wrap' ).hide();
										$document.find( '.bp-media-document-uploader.open-popup .bb-rl-location-folder-list-wrap-main span.bb-rl-no-folder-exists' ).show();
									} else {
										$document.find( '.bp-media-document-uploader.open-popup .bb-rl-location-folder-list-wrap-main span.bb-rl-no-folder-exists' ).hide();
										$document.find( '.bp-media-document-uploader.open-popup .bb-rl-location-folder-list-wrap' ).show();
									}

									$document.find( '.bp-media-document-uploader .bb-rl-popup-on-fly-create-album .bb-rl-privacy-field-wrap-hide-show' ).show();
									$document.find( '.bp-media-document-uploader .open-popup .bb-rl-folder-create-from' ).val( 'profile' );

									$( currentTarget ).find( '.bb-rl-location-folder-list-wrap .location-folder-list' ).remove();
									$( currentTarget ).find( '.bb-rl-location-folder-list-wrap' ).append( response.data.html );
									if ( bp.Nouveau.Media.folderLocationUI ) {
										bp.Nouveau.Media.folderLocationUI( currentTarget, parentsOpen );
									}
									$( currentTarget ).find( 'ul.location-folder-list span#' + parentsOpen ).trigger( 'click' );
									$( currentTarget ).find( '.bb-rl-folder-selected-id' ).val( parentsOpen );
								}
							}
						);
					}
				}

				$document.on(
					'click',
					currentTarget + ' .location-folder-list li span',
					function ( e ) {
						e.preventDefault();
						if ( $( this ).parent().hasClass( 'is_active' ) ) {
							return;
						}
						if ( $( this ).closest( '.bb-rl-location-folder-list-wrap' ).find( '.breadcrumb .item span:last-child' ).data( 'id' ) !== 0 ) {
							$( this ).closest( '.bb-rl-location-folder-list-wrap' ).find( '.breadcrumb .item span:last-child' ).remove();
						}
						$( this ).closest( '.bb-rl-location-folder-list-wrap' ).find( '.breadcrumb .item' ).append( '<span class="is-disabled" data-id="' + $( this ).attr( 'id' ) + '">' + $( this ).text() + '</span>' );
						$( this ).addClass( 'selected' ).parent().addClass( 'is_active' ).siblings().removeClass( 'is_active' ).children( 'span' ).removeClass( 'selected' );
						if ( parentsOpen === $( e.currentTarget ).data( 'id' ) ) {
							$( e.currentTarget ).closest( '.bb-rl-field-wrap' ).find( '.bb-model-footer .bb-rl-media-move' ).addClass( 'is-disabled' );
						} else {
							$( e.currentTarget ).closest( '.bb-rl-field-wrap' ).find( '.bb-model-footer .bb-rl-media-move' ).removeClass( 'is-disabled' );
						}
						if ( $( e.currentTarget ).closest( '.bb-rl-field-wrap' ).find( '.bb-model-footer .bb-rl-media-move' ).hasClass( 'is-disabled' ) ) {
							return; // return if parent album is same.
						}
						$( e.currentTarget ).closest( '.bb-rl-field-wrap' ).find( '.bb-rl-folder-selected-id' ).val( $( e.currentTarget ).data( 'id' ) );

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

					}
				);

				$document.on(
					'click',
					currentTarget + ' .breadcrumb .item > span',
					function ( e ) {

						if ( $( this ).hasClass( 'is-disabled' ) ) {
							return;
						}

						$( e.currentTarget ).closest( '.bb-rl-field-wrap' ).find( '.bb-rl-folder-selected-id' ).val( $( e.currentTarget ).data( 'id' ) );
						$( e.currentTarget ).closest( '.bb-rl-field-wrap' ).find( '.location-folder-list li span' ).removeClass( 'selected' ).parent().removeClass( 'is_active' );
						$( e.currentTarget ).closest( '.bb-rl-field-wrap' ).find( '.location-folder-list li[data-id="' + $( e.currentTarget ).data( 'id' ) + '"]' ).addClass( 'is_active' );
						if ( $( this ).closest( '.bb-rl-location-folder-list-wrap' ).find( '.breadcrumb .item span:last-child' ).hasClass( 'is-disabled' ) ) {
							$( this ).closest( '.bb-rl-location-folder-list-wrap' ).find( '.breadcrumb .item span:last-child' ).remove();
						}

						if ( parentsOpen === $( e.currentTarget ).data( 'id' ) ) {
							$( e.currentTarget ).closest( '.bb-rl-field-wrap' ).find( '.bb-model-footer .bb-rl-media-move' ).addClass( 'is-disabled' );
						} else {
							$( e.currentTarget ).closest( '.bb-rl-field-wrap' ).find( '.bb-model-footer .bb-rl-media-move' ).removeClass( 'is-disabled' );
						}
						var mediaPrivacy          = $( e.currentTarget ).closest( '#bp-media-uploader' ).find( '#bb-document-privacy' );
						var selectedFolderPrivacy = $( e.currentTarget ).closest( '#bp-media-uploader' ).find( '.location-folder-list li.is_active' ).data( 'privacy' );
						if ( Number( $( e.currentTarget ).closest( '.bb-rl-field-wrap' ).find( '.bb-rl-folder-selected-id' ).val() ) !== 0 ) {
							mediaPrivacy.find( 'option' ).removeAttr( 'selected' );
							mediaPrivacy.val( selectedFolderPrivacy === undefined ? 'public' : selectedFolderPrivacy );
							mediaPrivacy.prop( 'disabled', true );
						} else {
							mediaPrivacy.find( 'option' ).removeAttr( 'selected' );
							mediaPrivacy.val( 'public' );
							mediaPrivacy.prop( 'disabled', false );
						}
					}
				);

				self.options.previewTemplate = document.getElementsByClassName( 'uploader-post-document-template' ).length ? document.getElementsByClassName( 'uploader-post-document-template' )[ 0 ].innerHTML : '';

				self.dropzone_obj = new Dropzone( 'div#media-uploader', self.options );

				self.dropzone_obj.on(
					'sending',
					function ( file, xhr, formData ) {
						formData.append( 'action', 'document_document_upload' );
						formData.append( '_wpnonce', bbRlNonce.media );
					}
				);

				self.dropzone_obj.on(
					'uploadprogress',
					function ( element ) {
						var circle        = $( element.previewElement ).find( '.dz-progress-ring circle' )[0];
						var radius        = circle.r.baseVal.value;
						var circumference = radius * 2 * Math.PI;

						circle.style.strokeDasharray  = circumference + ' ' + circumference;
						circle.style.strokeDashoffset = circumference;
						circle.style.strokeDashoffset = circumference - element.upload.progress.toFixed( 0 ) / 100 * circumference;
					}
				);

				self.dropzone_obj.on(
					'addedfile',
					function () {
						setTimeout(
							function () {
								if ( self.dropzone_obj.getAcceptedFiles().length ) {
									$( '#bp-media-uploader-modal-status-text' ).text( wp.i18n.sprintf( bbRlMedia.i18n_strings.upload_status, self.dropzone_media.length, self.dropzone_obj.getAcceptedFiles().length ) ).show();
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
							} else if ( file.status === 'error' && ( file.xhr && file.xhr.status === 0) ) { // update server error text to user friendly.
								$( file.previewElement ).find( '.dz-error-message span' ).text( bbRlMedia.connection_lost_error );
							}
						} else {
							if ( ! jQuery( '.document-error-popup' ).length ) {
								$( 'body' ).append( '<div id="bp-media-create-album" style="display: block;" class="open-popup document-error-popup"><transition name="modal"><div class="bb-rl-modal-mask bb-white bbm-model-wrap"><div class="bb-rl-modal-wrapper"><div id="bb-rl-media-create-album-popup" class="modal-container bb-rl-has-folderlocationUI bb-rl-has-warning"><header class="bb-model-header"><h4>' + bbRlMedia.invalid_file_type + '</h4><a class="bb-model-close-button errorPopup" href="#"><span class="bb-icon-l bb-icon-times bbb"></span></a></header><div class="bb-rl-field-wrap"><p>' + response + '</p></div></div></div></div></transition></div>' );
							}
							this.removeFile( file );
						}
					}
				);

				self.dropzone_obj.on(
					'accept',
					function ( file, done ) {
						if ( file.size === 0 ) {
							done( bbRlMedia.empty_document_type );
						} else {
							done();
						}
					}
				);

				self.dropzone_obj.on(
					'queuecomplete',
					function () {
						$( '#bp-media-uploader-modal-title' ).text( bbRlMedia.i18n_strings.upload );
					}
				);

				self.dropzone_obj.on(
					'processing',
					function () {
						$( '#bp-media-uploader-modal-title' ).text( bbRlMedia.i18n_strings.uploading + '...' );
					}
				);

				self.dropzone_obj.on(
					'success',
					function ( file, response ) {
						if ( response.data.id ) {
							file.id                  = response.id;
							response.data.uuid       = file.upload.uuid;
							response.data.menu_order = self.dropzone_media.length;
							response.data.folder_id  = self.current_folder;
							response.data.group_id   = self.current_group_id;
							response.data.saved      = false;
							self.dropzone_media.push( response.data );

							var filename      = file.upload.filename;
							var fileExtension = filename.substr( ( filename.lastIndexOf( '.' ) + 1 ) );
							var file_icon     = ( ! _.isUndefined( response.data.svg_icon ) ? response.data.svg_icon : '' );
							var icon_class    = ! _.isEmpty( file_icon ) ? file_icon : 'bb-icon-file-' + fileExtension;

							if ( $( file.previewElement ).find( '.dz-details .dz-icon .bb-icon-file' ).length ) {
								$( file.previewElement ).find( '.dz-details .dz-icon .bb-icon-file' ).removeClass( 'bb-icon-file' ).addClass( icon_class );
							}
						} else {
							var node, _i, _len, _ref, _results;
							var message = response.data.feedback;
							file.previewElement.classList.add( 'dz-error' );
							_ref     = file.previewElement.querySelectorAll( '[data-dz-errormessage]' );
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
						$( '#bp-media-uploader-modal-title' ).text( bbRlMedia.i18n_strings.uploading + '...' );
						$( '#bp-media-uploader-modal-status-text' ).text( wp.i18n.sprintf( bbRlMedia.i18n_strings.upload_status, self.dropzone_media.length, self.dropzone_obj.getAcceptedFiles().length ) ).show();
					}
				);

				self.dropzone_obj.on(
					'removedfile',
					function ( file ) {
						if ( self.dropzone_media.length ) {
							for ( var i in self.dropzone_media ) {
								if ( file.upload.uuid === self.dropzone_media[ i ].uuid ) {

									if ( typeof self.dropzone_media[ i ].saved !== 'undefined' && ! self.dropzone_media[ i ].saved ) {
										self.removeAttachment( self.dropzone_media[ i ].id );
									}

									self.dropzone_media.splice( i, 1 );
									break;
								}
							}
						}
						if ( ! self.dropzone_obj.getAcceptedFiles().length ) {
							$( '#bp-media-uploader-modal-status-text' ).text( '' );
							$( '#bp-media-document-submit' ).hide();
							$( '.modal-container' ).removeClass( 'modal-container--alert' );
						} else {
							$( '#bp-media-uploader-modal-status-text' ).text( wp.i18n.sprintf( bbRlMedia.i18n_strings.upload_status, self.dropzone_media.length, self.dropzone_obj.getAcceptedFiles().length ) ).show();
						}
					}
				);
			}
		},

		/**
		 * [openMediaMove description]
		 *
		 * @return {[type]} [description]
		 * @param event
		 */
		openMediaMove: function ( event ) {
			event.preventDefault();
			var $document        = $( document ),
				media_move_popup, media_parent_id, media_id, currentTarget,
				eventTarget      = $( event.currentTarget );
			this.moveToIdPopup   = eventTarget.attr( 'id' );
			this.moveToTypePopup = eventTarget.attr( 'data-type' );

			if ( eventTarget.closest( '.bb-rl-activity-inner' ).length > 0 ) {
				media_move_popup = eventTarget.closest( '.bb-rl-activity-inner' );
			} else if ( eventTarget.closest( '#media-stream.media' ).length > 0 ) {
				media_move_popup = eventTarget.closest( '#media-stream.media' );
			} else if ( eventTarget.closest( '.comment-item' ).length > 0 ) {
				media_move_popup = eventTarget.closest( '.comment-item' );
			}

			$( media_move_popup ).find( '.bb-rl-media-move-file' ).addClass( 'open' ).show();
			media_id        = eventTarget.closest( '.media-action-wrap' ).siblings( 'a' ).data( 'id' );
			media_parent_id = eventTarget.closest( '.media-action-wrap' ).siblings( 'a' ).data( 'album-id' );

			media_move_popup.find( '.bb-rl-media-move' ).attr( 'id', media_id );
			media_move_popup.find( '.bb-rl-model-footer .bb-rl-media-move' ).addClass( 'is-disabled' );

			// For Activity Feed.
			if ( eventTarget.closest( '.bb-rl-conflict-activity-ul-li-comment' ).closest( 'li.comment-item' ).length ) {
				currentTarget = '#' + eventTarget.closest( '.bb-rl-conflict-activity-ul-li-comment' ).closest( 'li' ).attr( 'id' ) + '.comment-item .bb-rl-media-move-file';
			} else {
				currentTarget = '#' + eventTarget.closest( 'li.activity-item' ).attr( 'id' ) + ' > .bb-rl-activity-content .bb-rl-media-move-file';
			}

			$( currentTarget ).find( '.bb-rl-document-move' ).attr( 'id', eventTarget.closest( '.bb-rl-document-activity' ).attr( 'data-id' ) );

			// Change if this is not from Activity Page.
			if ( eventTarget.closest( '.media-list' ).length > 0 ) {
				currentTarget = '.bb-rl-media-move-file';
			}

			if ( 'group' === this.moveToTypePopup ) {
				$document.find( '.bb-rl-location-album-list-wrap h4' ).show();
			} else {
				$document.find( '.bb-rl-location-album-list-wrap h4' ).hide();
			}

			$( currentTarget ).addClass( 'open-popup' );

			$( currentTarget ).find( '.bb-rl-location-album-list-wrap .location-album-list' ).remove();
			$( currentTarget ).find( '.bb-rl-location-album-list-wrap' ).append( '<ul class="location-album-list is-loading"><li><i class="bb-icon-l bb-icon-spinner animate-spin"></i></li></ul>' );

			var parentsOpen = media_parent_id;
			var getFrom     = this.moveToTypePopup;
			if ( '' !== this.moveToIdPopup ) {
				$.ajax(
					{
						url: bbRlAjaxUrl,
						type: 'post',
						data: {
							action: 'media_get_album_view',
							id: this.moveToIdPopup,
							type: this.moveToTypePopup,
						}, success: function ( response ) {
							$document.find( '.bb-rl-location-album-list-wrap h4 span.bb-rl-where-to-move-profile-or-group-media' ).html( response.data.first_span_text );
							if ( '' === response.data.html ) {
								$document.find( '.open-popup .bb-rl-location-album-list-wrap' ).hide();
								$document.find( '.open-popup .bb-rl-location-album-list-wrap-main span.bb-rl-no-album-exists' ).show();
							} else {
								$document.find( '.open-popup .bb-rl-location-album-list-wrap-main span.bb-rl-no-album-exists' ).hide();
								$document.find( '.open-popup .bb-rl-location-album-list-wrap' ).show();
							}
							if ( 'group' === getFrom ) {
								$document.find( '.bb-rl-popup-on-fly-create-album .bb-rl-privacy-field-wrap-hide-show' ).hide();
								$document.find( '.open-popup .bb-rl-album-create-from' ).val( 'group' );
							} else {
								$document.find( '.bb-rl-popup-on-fly-create-album .bb-rl-privacy-field-wrap-hide-show' ).show();
								$document.find( '.open-popup .bb-rl-album-create-from' ).val( 'profile' );
							}

							if ( false === response.data.create_album ) {
								$( currentTarget + '.open-popup' ).find( '.bb-rl-media-open-create-popup-folder' ).removeClass( 'create-album' );
								$( currentTarget + '.open-popup' ).find( '.bb-rl-media-open-create-popup-folder' ).hide();
							} else {
								$( currentTarget + '.open-popup' ).find( '.bb-rl-media-open-create-popup-folder' ).addClass( 'create-album' );
								$( currentTarget + '.open-popup' ).find( '.bb-rl-media-open-create-popup-folder' ).show();
							}

							$( currentTarget ).find( '.bb-rl-location-album-list-wrap .location-album-list' ).remove();
							$( currentTarget ).find( '.bb-rl-location-album-list-wrap' ).append( response.data.html );
							$( currentTarget ).find( 'ul.location-album-list span#' + parentsOpen ).trigger( 'click' );
						}
					}
				);
			}

			$document.on(
				'click',
				currentTarget + ' .location-album-list li span',
				function ( e ) {
					e.preventDefault();
					if ( $( this ).parent().hasClass( 'is_active' ) ) {
						return;
					}

					if ( $( this ).closest( '.bb-rl-location-album-list-wrap' ).find( '.breadcrumb .item span:last-child' ).data( 'id' ) !== 0 ) {
						$( this ).closest( '.bb-rl-location-album-list-wrap' ).find( '.breadcrumb .item span:last-child' ).remove();
					}

					$( this ).closest( '.bb-rl-location-album-list-wrap' ).find( '.breadcrumb .item' ).append( '<span class="is-disabled" data-id="' + $( this ).attr( 'id' ) + '">' + $( this ).text() + '</span>' );

					$( this ).addClass( 'selected' ).parent().addClass( 'is_active' ).siblings().removeClass( 'is_active' ).children( 'span' ).removeClass( 'selected' );
					var parentsOpen = $( document ).find( 'a.bb-rl-open-media-theatre[data-id="' + media_move_popup.find( '.bb-rl-media-move' ).attr( 'id' ) + '"]' ).data( 'album-id' );
					if ( Number( parentsOpen ) === Number( $( e.currentTarget ).data( 'id' ) ) ) {
						$( e.currentTarget ).closest( '.bb-rl-media-move-file' ).find( '.bb-rl-model-footer .bb-rl-media-move' ).addClass( 'is-disabled' );
					} else {
						$( e.currentTarget ).closest( '.bb-rl-media-move-file' ).find( '.bb-rl-model-footer .bb-rl-media-move' ).removeClass( 'is-disabled' );
					}
					if ( $( e.currentTarget ).closest( '.bb-rl-media-move-file' ).find( '.bb-rl-model-footer .bb-rl-media-move' ).hasClass( 'is-disabled' ) ) {
						return; // return if parent album is same.
					}
					$( e.currentTarget ).closest( '.bb-rl-media-move-file' ).find( '.bb-rl-album-selected-id' ).val( $( e.currentTarget ).data( 'id' ) );
				}
			);

			$document.on(
				'click',
				currentTarget + ' .breadcrumb .item > span',
				function ( e ) {

					if ( $( this ).hasClass( 'is-disabled' ) ) {
						return;
					}

					$( e.currentTarget ).closest( '.bb-rl-media-move-file' ).find( '.bb-rl-album-selected-id' ).val( 0 );
					$( e.currentTarget ).closest( '.bb-rl-media-move-file' ).find( '.location-album-list li span' ).removeClass( 'selected' ).parent().removeClass( 'is_active' );

					if ( $( this ).closest( '.bb-rl-location-album-list-wrap' ).find( '.breadcrumb .item span:last-child' ).hasClass( 'is-disabled' ) ) {
						$( this ).closest( '.bb-rl-location-album-list-wrap' ).find( '.breadcrumb .item span:last-child' ).remove();
					}

					if ( parentsOpen === $( e.currentTarget ).data( 'id' ) ) {
						$( e.currentTarget ).closest( '.bb-rl-media-move-file' ).find( '.bb-rl-model-footer .bb-rl-media-move' ).addClass( 'is-disabled' );
					} else {
						$( e.currentTarget ).closest( '.bb-rl-media-move-file' ).find( '.bb-rl-model-footer .bb-rl-media-move' ).removeClass( 'is-disabled' );
					}

				}
			);

		},

		/**
		 * [openDocumentMove description]
		 *
		 * @return {[type]} [description]
		 * @param event
		 */
		openDocumentMove: function ( event ) {
			event.preventDefault();
			var $document        = $( document ),
				currentTarget,
				eventTarget      = $( event.currentTarget );
			this.moveToIdPopup   = eventTarget.attr( 'id' );
			this.moveToTypePopup = eventTarget.attr( 'data-type' );
			var action           = eventTarget.attr( 'data-action' );

			// For Activity Feed.
			if ( eventTarget.closest( '.bb-rl-conflict-activity-ul-li-comment' ).closest( 'li.comment-item' ).length ) {
				currentTarget = '#' + eventTarget.closest( '.bb-rl-conflict-activity-ul-li-comment' ).closest( 'li' ).attr( 'id' ) + '.comment-item .bb-rl-media-move-file';
			} else {
				currentTarget = '#' + eventTarget.closest( 'li.activity-item' ).attr( 'id' ) + ' > .bb-rl-activity-content .bb-rl-media-move-file';
			}

			$( currentTarget ).find( '.bb-rl-document-move' ).attr( 'id', eventTarget.closest( '.bb-rl-document-activity' ).attr( 'data-id' ) );
			this.currentTargetParent = eventTarget.closest( '.bb-rl-activity-media-elem' ).attr( 'data-parent-id' );

			// Change if this is not from Activity Page.
			if ( eventTarget.closest( '.media-folder_items' ).length > 0 ) {
				/* jshint ignore:start */
				this.currentTargetParent = eventTarget.closest( '.media-folder_items' ).attr( 'data-parent-id' );
				/* jshint ignore:end */
				if ( eventTarget.hasClass( 'ac-document-move' ) ) { // Check if target is file or folder.
					currentTarget = '.bb-rl-media-move-file';
					$( currentTarget ).find( '.bb-rl-document-move' ).attr( 'id', eventTarget.closest( '.media-folder_items' ).attr( 'data-id' ) );
				} else {
					currentTarget = '.bb-rl-media-move-folder';
					$( currentTarget ).find( '.bb-rl-folder-move' ).attr( 'id', eventTarget.closest( '.media-folder_items' ).attr( 'data-id' ) );

				}
			}

			$( currentTarget ).find( '.bb-rl-location-folder-list-wrap .location-folder-list' ).remove();
			$( currentTarget ).find( '.bb-rl-location-folder-list-wrap' ).append( '<ul class="location-folder-list is-loading"><li><i class="bb-icon-l bb-icon-spinner animate-spin"></i></li></ul>' );
			if ( 'document' === action ) {
				$( currentTarget ).find( '.bb-rl-modal-header h4 .target_name' ).text( bbRlMedia.move_to_file );
			} else {
				$( currentTarget ).find( '.bb-rl-modal-header h4 .target_name' ).text( bbRlMedia.move_to_folder );
			}
			$( currentTarget ).show();
			$( currentTarget ).addClass( 'open-popup' );

			if ( 'group' === this.moveToTypePopup ) {
				$document.find( '.bb-rl-location-folder-list-wrap h4' ).show();
				$( currentTarget ).addClass( 'move-folder-popup-group' );
			} else {
				$document.find( '.bb-rl-location-folder-list-wrap h4' ).hide();
				$( '.move-folder-popup-group' ).removeClass( 'move-folder-popup-group' );
			}

			var parentsOpen = this.currentTargetParent;
			var getFrom     = this.moveToTypePopup;

			if ( '' !== this.moveToIdPopup ) {
				$.ajax(
					{
						url: bbRlAjaxUrl,
						type: 'GET',
						data: {
							action: 'document_get_folder_view',
							id: this.moveToIdPopup,
							type: this.moveToTypePopup,
						}, success: function ( response ) {
							$document.find( '.bb-rl-location-folder-list-wrap h4 span.bb-rl-where-to-move-profile-or-group-document' ).html( response.data.first_span_text );
							if ( '' === response.data.html ) {
								$document.find( '.open-popup .bb-rl-location-folder-list-wrap' ).hide();
								$document.find( '.open-popup .bb-rl-location-folder-list-wrap-main span.bb-rl-no-folder-exists' ).show();
							} else {
								$document.find( '.open-popup .bb-rl-location-folder-list-wrap-main span.bb-rl-no-folder-exists' ).hide();
								$document.find( '.open-popup .bb-rl-location-folder-list-wrap' ).show();
							}
							if ( 'group' === getFrom ) {
								$document.find( '.bb-rl-popup-on-fly-create-folder .bb-rl-privacy-field-wrap-hide-show' ).hide();
								$document.find( '.open-popup .bb-rl-folder-create-from' ).val( 'group' );
							} else {
								$document.find( '.bb-rl-popup-on-fly-create-folder .bb-rl-privacy-field-wrap-hide-show' ).show();
								$document.find( '.open-popup .bb-rl-folder-create-from' ).val( 'profile' );
							}
							$( currentTarget ).find( '.bb-rl-location-folder-list-wrap .location-folder-list' ).remove();
							$( currentTarget ).find( '.bb-rl-location-folder-list-wrap' ).append( response.data.html );
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
		 * @return {[type]} [description]
		 * @param event
		 */
		closeMediaMove: function ( event ) {
			event.preventDefault();
			var eventTarget = $( event.currentTarget );
			if ( eventTarget.closest( '.bb-rl-media-move-file' ).find( '.bb-rl-location-album-list-wrap .breadcrumb .item span:last-child' ).data( 'id' ) !== 0 ) {
				eventTarget.closest( '.bb-rl-media-move-file' ).find( '.bb-rl-location-album-list-wrap .breadcrumb .item span:last-child' ).remove();
			}
			eventTarget.closest( '.bb-rl-media-move-file' ).hide();

		},

		/**
		 * [closeDocumentMove description]
		 *
		 * @return {[type]} [description]
		 * @param event
		 */
		closeDocumentMove: function ( event ) {
			event.preventDefault();
			var eventTarget    = $( event.currentTarget ),
				closest_parent = jQuery( event.currentTarget ).closest( '.bb-rl-has-folderlocationUI' );
			if ( eventTarget.hasClass( 'bb-rl-ac-document-close-button' ) ) {
				eventTarget.closest( '.bb-rl-media-move-file' ).hide().find( '.bb-rl-document-move' ).attr( 'id', '' );
			} else {
				eventTarget.closest( '.bb-rl-media-move-folder' ).hide().find( '.bb-rl-folder-move' ).attr( 'id', '' );
			}

			closest_parent.find( '.bb-rl-document-move.loading' ).removeClass( 'loading' );

			this.clearFolderLocationUI( event );
		},

		/**
		 * [renameDocument description]
		 *
		 * @return {[type]} [description]
		 * @param event
		 */
		renameDocument: function ( event ) {
			var current_name      = $( event.currentTarget ).closest( '.media-folder_items' ).find( '.media-folder_name' ),
				current_name_text = current_name.children( 'span' ).text();

			current_name.hide().siblings( '.media-folder_name_edit_wrap' ).show().children( '.media-folder_name_edit' ).val( current_name_text ).focus().select();

			event.preventDefault();
		},

		/**
		 * [editDocument description]
		 *
		 * @return {[type]} [description]
		 * @param event
		 */
		editDocument: function ( event ) {
			event.preventDefault();

			var $document = $( document ),
				$editFileModal = $( '#bb-rl-media-edit-file' ),
				media_item = $( event.currentTarget ).closest( '.media-folder_items' ),
				current_name = media_item.find( '.media-folder_name' ),
				current_name_text = current_name.children( 'span' ).text(),
				document_id = media_item.data( 'id' ),
				document_attachment_id = media_item.find( '.media-folder_name' ).data( 'attachment-id' ),
				document_privacy = media_item.find( '.media-folder_name' ).data( 'privacy' );

			if (
				$( event.currentTarget ).attr( 'data-privacy' ) &&
				$editFileModal.find( '#bb-rl-folder-privacy-select' ).length > 0
			) {
				var current_privacy = $( event.currentTarget ).attr( 'data-privacy' );
				if ( current_privacy === 'grouponly' ) {
					$editFileModal.find( '#bb-rl-folder-privacy-select' ).addClass( 'bp-hide' );
				} else {
					$editFileModal.find( '#bb-rl-folder-privacy-select' ).val( current_privacy ).change().removeClass( 'bp-hide' );
				}
			} else if ( $editFileModal.find( '#bb-rl-folder-privacy-select' ).length > 0 ) {
				$editFileModal.find( '#bb-rl-folder-privacy-select' ).addClass( 'bp-hide' );
			}

			$editFileModal.show();
			$editFileModal.addClass( 'open-popup' );

			$editFileModal.find( '#bb-document-title' ).val( current_name_text ).focus().select();
			$editFileModal.attr( 'data-id', document_id );
			$editFileModal.attr( 'data-attachment-id', document_attachment_id );
			$editFileModal.attr( 'data-privacy', document_privacy );
			$editFileModal.find( '#bb-rl-folder-privacy' ).val( document_privacy );

			$document.find( '.open-popup #bb-rl-media-create-album-popup #bb-album-title' ).show();
			$document.find( '.open-popup #bb-rl-media-create-album-popup #bb-album-title' ).removeClass( 'error' );
		},

		/**
		 * [renameDocumentSubmit description]
		 *
		 * @return {[type]} [description]
		 * @param event
		 */
		renameDocumentSubmit: function ( event ) {
			var eventTarget               = $( event.currentTarget ),
				document_edit             = eventTarget.closest( '.media-folder_items' ).find( '.media-folder_name_edit' ),
				document_name             = eventTarget.closest( '.media-folder_items' ).find( '.media-folder_name > span' ),
				document_name_update_data = eventTarget.closest( '.media-folder_items' ).find( '.media-folder_name' ),
				document_id               = eventTarget.closest( '.media-folder_items' ).find( '.media-folder_name > i.media-document-id' ).attr( 'data-item-id' ),
				attachment_document_id    = eventTarget.closest( '.media-folder_items' ).find( '.media-folder_name > i.media-document-attachment-id' ).attr( 'data-item-id' ),
				documentType              = eventTarget.closest( '.media-folder_items' ).find( '.media-folder_name > i.media-document-type' ).attr( 'data-item-id' ),
				document_name_val         = document_edit.val().trim(),
				pattern                   = '';

			if ( eventTarget.closest( '.ac-document-list' ).length ) {
				pattern = /[?\[\]=<>:;,'"&$#*()|~`!{}%+ \/]+/g; // regex to find not supported characters. ?[]/=<>:;,'"&$#*()|~`!{}%+ {space}.
			} else if ( eventTarget.closest( '.ac-folder-list' ).length ) {
				pattern = /[\\/?%*:|"<>]+/g; // regex to find not supported characters - \ / ? % * : | " < >
			}

			var matches     = pattern.exec( document_name_val ),
				matchStatus = Boolean( matches );

			if ( ! matchStatus ) { // If any not supported character found add error class.
				document_edit.removeClass( 'error' );
			} else {
				document_edit.addClass( 'error' );
			}

			if ( eventTarget.closest( '.ac-document-list' ).length ) {
				if ( document_name_val.indexOf( '\\\\' ) !== -1 || matchStatus ) { // Also check if filename has "\\".
					document_edit.addClass( 'error' );
				} else {
					document_edit.removeClass( 'error' );
				}
			}

			if ( eventTarget.hasClass( 'name_edit_cancel' ) || event.keyCode === 27 ) {
				document_edit.removeClass( 'error' );
				document_edit.parent().hide().siblings( '.media-folder_name' ).show();
			}

			if ( eventTarget.hasClass( 'name_edit_save' ) || event.keyCode === 13 ) {
				if ( matchStatus ) {
					return; // prevent user to add not supported characters.
				}
				document_edit.parent().addClass( 'submitting' ).append( '<i class="animate-spin bb-icon-l bb-icon-spinner"></i>' );

				// Make ajax call to save new file name here.
				// use variable 'document_name_val' as a new name while making an ajax call.
				$.ajax(
					{
						url: bbRlAjaxUrl,
						type: 'post',
						data: {
							action: 'document_update_file_name',
							document_id: document_id,
							attachment_document_id: attachment_document_id,
							document_type: documentType,
							name: document_name_val,
							_wpnonce: bbRlNonce.media
						},
						success: function ( response ) {
							if ( response.success ) {
								if ( 'undefined' !== typeof response.data.document && 0 < $( response.data.document ).length ) {
									eventTarget.closest( '.media-folder_items' ).html( $( response.data.document ).html() );
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

		closeEditDocumentModal: function ( event ) {
			event.preventDefault();

			var $modal = $( event.target ).closest( '#bb-rl-media-edit-file' );

			// Reset modal data.
			$modal.find( '#bb-document-title' ).val( '' );
			$modal.attr('data-activity-id', '');
    		$modal.attr('data-id', '');
			$modal.attr('data-attachment-id', '');
			$modal.attr('data-privacy', '');
			$modal.removeClass( 'open-popup' ).hide();
		},

		/**
		 * [editPrivacyDocument description]
		 *
		 * @return {[type]} [description]
		 * @param event
		 */
		editPrivacyDocument: function ( event ) {
			event.preventDefault();
			var eventTarget = $( event.currentTarget );
			// Reset all privacy dropdown.
			eventTarget.closest( '#media-folder-document-data-table' ).find( '.media-folder_visibility .media-folder_details__bottom span' ).show().siblings( 'select' ).addClass( 'hide' );

			var current_privacy = eventTarget.closest( '.media-folder_items' ).find( '.media-folder_visibility' );
			current_privacy.find( '.media-folder_details__bottom span' ).hide().siblings( 'select' ).removeClass( 'hide' );
			current_privacy.find( '.media-folder_details__bottom span' ).hide().siblings( 'select' ).val( eventTarget.attr( 'data-privacy' ) );
			current_privacy.find( '.media-folder_details__bottom #bb-rl-folder-privacy' ).attr( 'data-privacy', eventTarget.attr( 'data-privacy' ) );

			this.privacySelectorSelect = current_privacy.find( '.media-folder_details__bottom span' ).hide().siblings( 'select' );
			this.privacySelectorSpan   = current_privacy.find( '.media-folder_details__bottom span' );
		},

		/**
		 * [editDocumentSubmit description]
		 *
		 * @return {[type]} [description]
		 * @param event
		 */
		editDocumentSubmit: function ( event ) {
			var eventTarget               = $( event.currentTarget ),
				$modal 					  = eventTarget.closest( '#bb-rl-media-edit-file' ),
				$documentDataId 		  = $modal.attr( 'data-id' ),
				$mediaItem                = $( '.media-folder_items[data-id="' + $documentDataId + '"]' ),
				document_edit             = $modal.find( '#bb-document-title' ),
				document_name             = $mediaItem.find( '.media-folder_name > span' ),
				document_name_update_data = $mediaItem.find( '.media-folder_name' ),
				document_id               = $mediaItem.find( '.media-folder_name > i.media-document-id' ).attr( 'data-item-id' ),
				attachment_document_id    = $mediaItem.find( '.media-folder_name > i.media-document-attachment-id' ).attr( 'data-item-id' ),
				documentType              = $mediaItem.find( '.media-folder_name > i.media-document-type' ).attr( 'data-item-id' ),
				document_name_val         = document_edit.val().trim(),
				document_privacy = ( $modal.find( '#bb-rl-folder-privacy-select' ).length > 0 ) ? $modal.find( '#bb-rl-folder-privacy-select' ).val() : '',
				pattern                   = '';

			if ( $mediaItem.length ) {
				pattern = /[?\[\]=<>:;,'"&$#*()|~`!{}%+ \/]+/g; // regex to find not supported characters. ?[]/=<>:;,'"&$#*()|~`!{}%+ {space}.
			} else if ( eventTarget.closest( '.ac-folder-list' ).length ) {
				pattern = /[\\/?%*:|"<>]+/g; // regex to find not supported characters - \ / ? % * : | " < >
			}

			var matches     = pattern.exec( document_name_val ),
				matchStatus = Boolean( matches );

			if ( ! matchStatus ) { // If any not supported character found add error class.
				document_edit.removeClass( 'error' );
			} else {
				document_edit.addClass( 'error' );
			}

			if ( $mediaItem.length ) {
				if ( document_name_val.indexOf( '\\\\' ) !== -1 || matchStatus ) { // Also check if filename has "\\".
					document_edit.addClass( 'error' );
				} else {
					document_edit.removeClass( 'error' );
				}
			}

			if ( matchStatus ) {
				return; // prevent user to add not supported characters.
			}

			eventTarget.addClass( 'saving' );

			// Make ajax call to save new file name here.
			// use variable 'document_name_val' as a new name while making an ajax call.
			$.ajax(
				{
					url: bbRlAjaxUrl,
					type: 'post',
					data: {
						action: 'document_update_file_name',
						document_id: document_id,
						attachment_document_id: attachment_document_id,
						document_type: documentType,
						document_privacy: document_privacy,
						name: document_name_val,
						_wpnonce: bbRlNonce.media
					},
					success: function ( response ) {
						if ( response.success ) {
							if ( 'undefined' !== typeof response.data.document && 0 < $( response.data.document ).length ) {
								$mediaItem.html( $( response.data.document ).html() );
								eventTarget.removeClass( 'saving' );
							} else {
								document_name_update_data.attr( 'data-document-title', response.data.response.title + '.' + document_name_update_data.data( 'extension' ) );
								document_name.html( response.data.response.title );

								if (
									'undefined' !== typeof response.data.response.privacy_label &&
									$mediaItem.find( '.media-folder_details__bottom .bb-rl-privacy-label' ).length > 0
								) {
									$mediaItem.find( '.media-folder_details__bottom .bb-rl-privacy-label' ).html( response.data.response.privacy_label );
								}

								if (
									'undefined' !== typeof response.data.response.privacy &&
									$mediaItem.find( '.bb_more_options .ac-document-edit' ).length > 0
								) {
									$mediaItem.find( '.bb_more_options .ac-document-edit' ).attr( 'data-privacy', response.data.response.privacy );
								}

								eventTarget.removeClass( 'saving' );
							}
						} else {
							eventTarget.removeClass( 'saving' );
							/* jshint ignore:start */
							alert( response.data.feedback.replace( '&#039;', '\'' ) );
							/* jshint ignore:end */
						}

						// Trigger the close modal function
						$modal.find( '#bp-media-edit-document-close' ).trigger( 'click' );
					},
				}
			);
			event.preventDefault();
		},

		/**
		 * [editAlbumSubmit description]
		 *
		 * @return {[type]} [description]
		 * @param event
		 */
		editAlbumSubmit: function ( event ) {
			var eventTarget = $( event.currentTarget ),
				$modal = eventTarget.closest( '#bb-rl-media-edit-album' ),
				$album_id = $modal.attr( 'data-id' ),
				$album = $( '.bb-rl-media-single-album[data-id="' + $album_id + '"]' ),
				$group_id = $album.attr( 'data-group' ) || 0,
				album_name = $modal.find( '#bb-album-title' ).val().trim(),
				album_privacy = ( $modal.find( '#bb-album-privacy' ).length > 0 ) ? $modal.find( '#bb-album-privacy' ).val() : '',
				album_privacy_label = album_privacy ? $modal.find( '#bb-album-privacy option[value="'  + album_privacy + '"]' ).text() : '';

			eventTarget.addClass( 'saving' );

			// Make ajax call to save new file name here.
			$.ajax(
				{
					url: bbRlAjaxUrl,
					type: 'post',
					data: {
						action: 'media_album_save',
						'_wpnonce': bbRlNonce.media,
						album_id: $album_id,
						group_id: $group_id,
						title: album_name,
						privacy: album_privacy
					},
					success: function ( response ) {
						if ( response.success ) {

							if ( album_name ) {
								$album.find( '#bp-single-album-title .title-wrap' ).html( album_name );
							}

							if ( album_privacy ) {
								$album.find( '.bb-media-privacy-wrap .bb-media-privacy-icon' ).attr( 'class', 'bb-media-privacy-icon privacy' ).addClass( album_privacy );
								$album.find( '.bb-rl-edit-album' ).attr( 'data-privacy', album_privacy );
							}

							if ( album_privacy_label ) {
								$album.find( '.bb-media-privacy-wrap .bb-media-privacy-text' ).html( album_privacy_label );
							}

							eventTarget.removeClass( 'saving' );
						} else {
							eventTarget.removeClass( 'saving' );
							/* jshint ignore:start */
							alert( response.data.feedback.replace( '&#039;', '\'' ) );
							/* jshint ignore:end */
						}

						// Trigger the close modal function
						$modal.find( '#bp-media-edit-album-close' ).trigger( 'click' );
					},
				}
			);
			event.preventDefault();
		},

		removeAttachment: function ( id ) {
			var data = {
				'action'   : 'media_delete_attachment',
				'_wpnonce' : bbRlNonce.media,
				'id'       : id
			};

			$.ajax(
				{
					type : 'POST',
					url  : bbRlAjaxUrl,
					data : data
				}
			);
		},

		beforeunloadWindow: function () {
			if ( $( 'body.messages' ).length > 0 ) {
				$.each(
					Dropzone.instances,
					function ( index, value ) {
						value.removeAllFiles( true );
					}
				);
			}
		},

		changeUploadModalTab: function ( event ) {
			event.preventDefault();
			var eventTarget   = $( event.currentTarget ),
				content_tab   = eventTarget.data( 'content' ),
				current_popup = eventTarget.closest( '#bp-media-uploader' );
			$( '.bp-media-upload-tab-content' ).hide();
			$( '#' + content_tab ).show();
			this.current_tab = content_tab;
			current_popup.find( '.bp-media-upload-tab' ).removeClass( 'selected' );
			eventTarget.addClass( 'selected' );
			this.toggleSubmitMediaButton();
			current_popup.find( '.bb-field-steps-2' ).slideUp( 200 );
			current_popup.find( '#bb-media-privacy' ).hide();
			current_popup.find( '.bb-rl-media-open-create-popup-folder' ).hide();
			if ( content_tab === 'bp-dropzone-content' ) {
				current_popup.find( '.bb-field-steps-1' ).show();
				current_popup.find( '#bb-media-privacy' ).show();
				current_popup.find( '.bb-rl-media-open-create-popup-folder, .bb-rl-document-open-create-popup-folder, #bb-media-privacy' ).hide();
			}
			if ( content_tab === 'bp-existing-media-content' ) {
				current_popup.find( '.bb-field-uploader-actions' ).hide();
			}
			jQuery( window ).scroll();
		},

		openCreateAlbumModal: function ( event ) {
			event.preventDefault();

			this.openUploader( event );
			$( '#bp-media-create-album' ).show();
			if ( $( 'body' ).hasClass( 'directory' ) ) {
				$( '#bp-media-uploader' ).hide();
			}
		},

		openCreateFolderModal: function ( event ) {
			event.preventDefault();
			var $document = $( document ), $createFolder = $( '#bp-media-create-folder' );
			$createFolder.show();
			$createFolder.addClass( 'open-popup' );
			$document.find( '.open-popup #bb-rl-media-create-album-popup #bb-album-title' ).show();
			$document.find( '.open-popup #bb-rl-media-create-album-popup #bb-album-title' ).removeClass( 'error' );
		},

		openCreateFolderChildModal: function ( event ) {
			event.preventDefault();
			var $document = $( document ), $mediaCreateFolder = $( '#bp-media-create-child-folder' );
			$mediaCreateFolder.show();
			$mediaCreateFolder.addClass( 'open-popup' );
			$document.find( '.open-popup #bb-rl-media-create-album-popup #bb-album-child-title' ).show();
			$document.find( '.open-popup #bb-rl-media-create-album-popup #bb-album-child-title' ).removeClass( 'error' );
		},

		openEditFolderChildModal: function ( event ) {
			event.preventDefault();
			var $document = $( document ), userId = bbRlMedia.current_user_id,
				groupId   = bbRlMedia.current_group_id, type = bbRlMedia.current_type,
				id;
			if ( 'group' === type ) {
				id = groupId;
				$document.find( '.bb-rl-location-folder-list-wrap h4' ).show();
			} else {
				id = userId;
				$document.find( '.bb-rl-location-folder-list-wrap h4' ).hide();
			}

			$.ajax(
				{
					url: bbRlAjaxUrl,
					type: 'GET',
					data: {
						action: 'document_get_folder_view',
						id: id,
						type: type,
					}, success: function ( response ) {
						$document.find( '.bb-rl-location-folder-list-wrap h4 span.bb-rl-where-to-move-profile-or-group-document' ).html( response.data.first_span_text );
						$( '.bb-rl-location-folder-list-wrap .location-folder-list' ).remove();
						$( '.bb-rl-location-folder-list-wrap' ).append( response.data.html );
						if ( bp.Nouveau.Media.folderLocationUI ) {
							bp.Nouveau.Media.folderLocationUI( '#bp-media-edit-child-folder', bbRlMedia.current_folder );
							$( event.currentTarget ).closest( '#bp-media-single-folder' ).find( 'ul.location-folder-list span#' + bbRlMedia.current_folder ).trigger( 'click' );
						}
						if ( 'group' === type ) {
							$document.find( '.bb-rl-popup-on-fly-create-folder .bb-rl-privacy-field-wrap-hide-show' ).hide();
							$document.find( '.open-popup .bb-rl-folder-create-from' ).val( 'group' );
						} else {
							$document.find( '.bb-rl-popup-on-fly-create-folder .bb-rl-privacy-field-wrap-hide-show' ).show();
							$document.find( '.open-popup .bb-rl-folder-create-from' ).val( 'profile' );
						}
					}
				}
			);

			$( '#bp-media-edit-child-folder' ).show();
		},

		folderLocationUI: function ( targetPopup, currentTargetParent ) {

			if ( $( targetPopup ).find( '.bb-rl-folder-destination' ).length > 0 ) {
				var $document = $( document );
				if ( ! $( targetPopup ).find( '.bb-rl-location-folder-list-wrap' ).hasClass( 'is_loaded' ) ) {
					$document.on(
						'click',
						targetPopup + ' .bb-rl-folder-destination',
						function () {
							$( this ).parent().find( '.bb-rl-location-folder-list-wrap' ).slideToggle();
						}
					);

					$( targetPopup ).find( '.bb-rl-location-folder-list-wrap' ).addClass( 'is_loaded' );

					$document.on(
						'click',
						targetPopup + ' .location-folder-list span',
						function () {

							this.currentTargetParent = $( this ).attr( 'id' );

							var $this = $( this ),
								$bc   = $( '<div class="item"></div>' );

							$this.parents( 'li' ).each(
								function ( n, li ) {
									var $a = $( li ).children( 'span' ).clone();
									$bc.prepend( '', $a );
								}
							);
							$( targetPopup ).find( '.bb-rl-breadcrumbs-append-ul-li .breadcrumb' ).html( $bc.prepend( '<span data-id="0">' + bbRlMedia.target_text + '</span>' ) );

							if ( ! $( targetPopup ).find( '.bb-rl-breadcrumbs-append-ul-li .breadcrumb .item span.hidden' ).length ) {
								$( targetPopup ).find( '.bb-rl-breadcrumbs-append-ul-li .breadcrumb' ).find( '.item' ).append( '<span class="hidden"></span>' );
							}

							$( targetPopup ).find( '.bb-rl-breadcrumbs-append-ul-li .breadcrumb .item span:not(.hidden)' ).each(
								function ( i ) {

									if ( i > 0 ) {
										if ( $( targetPopup ).find( '.bb-rl-breadcrumbs-append-ul-li .breadcrumb .item' ).width() > $( targetPopup ).find( '.bb-rl-breadcrumbs-append-ul-li .breadcrumb' ).width() ) {

											$( targetPopup ).find( '.bb-rl-breadcrumbs-append-ul-li .breadcrumb .item span.hidden' ).append( $( targetPopup ).find( '.bb-rl-breadcrumbs-append-ul-li .breadcrumb .item span' ).eq( 2 ) );

											if ( ! $( targetPopup ).find( '.bb-rl-breadcrumbs-append-ul-li .breadcrumb .item .more_options' ).length ) {
													$( '<span class="more_options">...</span>' ).insertAfter( $( targetPopup ).find( '.bb-rl-breadcrumbs-append-ul-li .breadcrumb .item span' ).eq( 0 ) );
											}

										}
									}
								}
							);

							if ( $( this ).hasClass( 'selected' ) && ! $( this ).hasClass( 'disabled' ) ) {
								$( this ).closest( '.bb-rl-location-folder-list-wrap-main' ).find( '.bb-rl-folder-destination' ).val( '' );
								$( this ).closest( '.bb-rl-location-folder-list-wrap-main' ).find( '.bb-rl-folder-selected-id' ).val( '0' );

								if ( $( targetPopup ).find( '.location-folder-list li.is_active' ).length ) {
									$( targetPopup ).find( '.bb-rl-folder-selected-id' ).val( $( targetPopup ).find( '.location-folder-list li.is_active' ).attr( 'data-id' ) );
								} else {
									$( targetPopup ).find( '.bb-rl-folder-selected-id' ).val( '0' );
								}

							} else {
								$( this ).closest( '.bb-rl-location-folder-list-wrap-main' ).find( '.location-folder-list li span' ).removeClass( 'selected' );
								$( this ).addClass( 'selected' );
								$( this ).closest( '.bb-rl-location-folder-list-wrap-main' ).find( '.bb-rl-folder-destination' ).val( $( this ).text() );
								$( this ).closest( '.bb-rl-location-folder-list-wrap-main' ).find( '.bb-rl-folder-selected-id' ).val( $( this ).parent().attr( 'data-id' ) );
							}

							$( this ).closest( '.bb-rl-location-folder-list-wrap' ).find( '.location-folder-title' ).text( $( this ).siblings( 'span' ).text() ).siblings( '.location-folder-back' ).css( 'display', 'inline-block' );
							$( this ).siblings( 'ul' ).show().siblings( 'span, i' ).hide().parent().siblings().hide();
							$( this ).siblings( 'ul' ).children( 'li' ).show().children( 'span,i' ).show();
							$( this ).closest( '.is_active' ).removeClass( 'is_active' );
							$( targetPopup ).find( 'li.is_active' ).removeClass( 'is_active' );
							$( this ).parent().addClass( 'is_active' );

							$( targetPopup ).find( '.bb-rl-folder-selected-id' ).val( $( targetPopup ).find( '.location-folder-list li.is_active' ).attr( 'data-id' ) );
							$( targetPopup ).find( '.bb-rl-breadcrumbs-append-ul-li .breadcrumb .item span' ).each(
								function () {
									$( this ).show();
								}
							);

							if ( currentTargetParent === $( targetPopup ).find( '.bb-rl-breadcrumbs-append-ul-li .item > span:last-child' ).attr( 'data-id' ) && ( $( targetPopup ).hasClass( 'bb-rl-media-move-file' ) || $( targetPopup ).hasClass( 'bb-rl-media-move-folder' ) ) ) {
								$( targetPopup ).find( '.bb-rl-document-move' ).addClass( 'is-disabled' );
								$( targetPopup ).find( '.bb-rl-folder-move' ).addClass( 'is-disabled' );
							} else {
								$( targetPopup ).find( '.bb-rl-document-move' ).removeClass( 'is-disabled' );
								$( targetPopup ).find( '.bb-rl-folder-move' ).removeClass( 'is-disabled' );
							}

							// Disable move button if current folder is already a parent.
							setTimeout(
								function () {

									var fileID;

									if ( $( targetPopup ).find( '.bb-rl-breadcrumbs-append-ul-li .item > span:last-child' ).hasClass( 'hidden' ) ) {
											fileID = $( targetPopup ).find( '.bb-rl-breadcrumbs-append-ul-li .item > span:last-child' ).prev().attr( 'id' );
									} else {
										fileID = $( targetPopup ).find( '.bb-rl-breadcrumbs-append-ul-li .item > span:last-child' ).attr( 'id' );
									}
									if ( currentTargetParent === fileID && ( $( targetPopup ).hasClass( 'bb-rl-media-move-file' ) || $( targetPopup ).hasClass( 'bb-rl-media-move-folder' ) ) ) {
										$( targetPopup ).find( '.bb-rl-document-move' ).addClass( 'is-disabled' );
										$( targetPopup ).find( '.bb-rl-folder-move' ).addClass( 'is-disabled' );
									} else {
										$( targetPopup ).find( '.bb-rl-document-move' ).removeClass( 'is-disabled' );
										$( targetPopup ).find( '.bb-rl-folder-move' ).removeClass( 'is-disabled' );
									}

								},
								100
							);

						}
					);

					$document.on(
						'click',
						targetPopup + ' .bb-rl-breadcrumbs-append-ul-li .item span',
						function ( event ) {

							if ( $( this ).parent().hasClass( 'is-disabled' ) || $( this ).hasClass( 'more_options' ) ) {
								return;
							}

							var currentLiID = $( event.currentTarget ).attr( 'data-id' );
							$( targetPopup ).find( '.bb-rl-location-folder-list-wrap' ).find( '.location-folder-title' ).text( $( targetPopup ).find( '.location-folder-list li.is_active' ).closest( '.has-ul' ).children( 'span' ).text() ).siblings( '.location-folder-back' ).css( 'display', 'inline-block' );
							$( targetPopup ).find( '.bb-rl-folder-selected-id' ).val( currentLiID );
							$( targetPopup ).find( '.location-folder-list li' ).hide();
							$( targetPopup ).find( '.location-folder-list li.is_active' ).removeClass( 'is_active' );
							$( targetPopup ).find( '.location-folder-list li > span.selected' ).removeClass( 'selected' );
							$( targetPopup ).find( '.location-folder-list li[data-id="' + currentLiID + '"]' ).addClass( 'is_active' ).children( 'span' ).addClass( 'selected' );
							$( targetPopup ).find( '.location-folder-list li.is_active' ).parents( '.has-ul' ).show().children( 'ul' ).show().siblings( 'span,i' ).hide();

							if ( $( targetPopup ).find( '.location-folder-list li.is_active' ).children( 'ul' ).length && ! $( targetPopup ).find( '.location-folder-list li.is_active' ).children( 'ul' ).hasClass( 'no-folder-list' ) ) {
								setTimeout(
									function () {
										$( targetPopup ).find( '.location-folder-list li.is_active' ).show().children( 'ul' ).show().children( 'li' ).show().children( 'span,i' ).show().closest( 'ul' ).siblings( 'span, i' ).hide();
									},
									100
								);
							} else {

								if ( $( targetPopup ).find( '.location-folder-list li.is_active' ).hasClass( 'has-ul' ).length ) {
									$( targetPopup ).find( '.location-folder-list li.is_active' ).children( 'span,i' ).hide().parent().children( 'ul' ).show().children( 'li' ).show();
								} else {
									setTimeout(
										function () {
											$( targetPopup ).find( '.location-folder-list li.is_active' ).show().children( 'span' ).show().parent().siblings( 'li' ).show().children( 'span,i' ).show();
										},
										10
									);
								}

							}

							if ( currentLiID === '0' ) {
								$( targetPopup ).find( '.location-folder-list' ).children( 'li' ).show().children( 'span,i' ).show();
								$( targetPopup ).find( '.bb-rl-location-folder-list-wrap' ).find( '.location-folder-title' ).text( bbRlMedia.target_text );
								$( targetPopup ).find( '.location-folder-back' ).hide();
							}

							$( event.currentTarget ).nextAll().remove();

							if ( currentTargetParent === $( targetPopup ).find( '.bb-rl-breadcrumbs-append-ul-li .item > span:last-child' ).attr( 'data-id' ) && ( $( targetPopup ).hasClass( 'bb-rl-media-move-file' ) || $( targetPopup ).hasClass( 'bb-rl-media-move-folder' ) ) ) {
								$( targetPopup ).find( '.bb-rl-document-move' ).addClass( 'is-disabled' );
								$( targetPopup ).find( '.bb-rl-folder-move' ).addClass( 'is-disabled' );
							} else {
								$( targetPopup ).find( '.bb-rl-document-move' ).removeClass( 'is-disabled' );
								$( targetPopup ).find( '.bb-rl-folder-move' ).removeClass( 'is-disabled' );
							}

							var $this = $( targetPopup ).find( '.location-folder-list .is_active > span' ),
								$bc   = $( '<div class="item"></div>' );

							$this.parents( 'li' ).each(
								function ( n, li ) {
									var $a = $( li ).children( 'span' ).clone();
									$bc.prepend( '', $a );
								}
							);
							$( targetPopup ).find( '.bb-rl-breadcrumbs-append-ul-li .breadcrumb' ).html( $bc.prepend( '<span data-id="0">' + bbRlMedia.target_text + '</span>' ) );

							if ( $( targetPopup ).find( '.bb-rl-breadcrumbs-append-ul-li .breadcrumb .item > span[data-id="' + currentLiID + '"]' ).length === 0 ) {
								$( targetPopup ).find( '.bb-rl-breadcrumbs-append-ul-li .breadcrumb .item' ).append( $( targetPopup ).find( '.location-folder-list li[data-id="' + currentLiID + '"]' ).children( 'span' ).clone() );
							}

							if ( ! $( targetPopup ).find( '.bb-rl-breadcrumbs-append-ul-li .breadcrumb .item span.hidden' ).length ) {
								$( targetPopup ).find( '.bb-rl-breadcrumbs-append-ul-li .breadcrumb' ).find( '.item' ).append( '<span class="hidden"></span>' );
							}

							$( targetPopup ).find( '.bb-rl-breadcrumbs-append-ul-li .breadcrumb .item span:not(.hidden)' ).each(
								function ( i ) {

									if ( i > 0 ) {
										if ( $( targetPopup ).find( '.bb-rl-breadcrumbs-append-ul-li .breadcrumb .item' ).width() > $( targetPopup ).find( '.bb-rl-breadcrumbs-append-ul-li .breadcrumb' ).width() ) {

											$( targetPopup ).find( '.bb-rl-breadcrumbs-append-ul-li .breadcrumb .item span.hidden' ).append( $( targetPopup ).find( '.bb-rl-breadcrumbs-append-ul-li .breadcrumb .item span' ).eq( 2 ) );

											if ( ! $( targetPopup ).find( '.bb-rl-breadcrumbs-append-ul-li .breadcrumb .item .more_options' ).length ) {
													$( '<span class="more_options">...</span>' ).insertAfter( $( targetPopup ).find( '.bb-rl-breadcrumbs-append-ul-li .breadcrumb .item span' ).eq( 0 ) );
											}

										}
									}
								}
							);
							$( targetPopup ).find( '.bb-rl-folder-selected-id' ).val( currentLiID );
						}
					);
				}

				$( targetPopup ).find( '.location-folder-list li' ).each(
					function () {
						$( this ).children( 'ul' ).parent().addClass( 'has-ul' ).append( '<i class="bb-icon-l bb-icon-angle-right sub-menu-anchor"></i>' );
					}
				);

				if ( $( targetPopup ).hasClass( 'bb-rl-media-move-folder' ) ) {
					$( targetPopup ).find( '.location-folder-list li>span' ).removeClass( 'is-disabled' );
					$( targetPopup ).find( '.location-folder-list li>span[id="' + $( targetPopup ).find( '.bb-rl-folder-move' ).attr( 'id' ) + '"]' ).parent().addClass( 'is-disabled' );
				}

				var currentMoveItemID = $( targetPopup ).find( '.bb-rl-folder-move' ).attr( 'id' );

				if ( $( targetPopup ).find( '.location-folder-list li[data-id="' + currentMoveItemID + '"]' ).siblings().length === 0 ) {
					$( targetPopup ).find( '.location-folder-list li[data-id="' + currentMoveItemID + '"]' ).parent( 'ul' ).addClass( 'no-folder-list a' );
				}

				if ( currentTargetParent ) {

					$( targetPopup ).find( '.location-folder-list li' ).hide();
					$( targetPopup ).find( '.location-folder-list li.is_active' ).removeClass( 'is_active' );
					$( targetPopup ).find( '.location-folder-list li[data-id="' + currentTargetParent + '"]' ).addClass( 'is_active' );
					$( targetPopup ).find( '.location-folder-list li.is_active' ).parents( '.has-ul' ).show().children( 'ul' ).show().siblings( 'span,i' ).hide();

					if ( $( targetPopup ).find( '.location-folder-list li.is_active' ).children( 'ul' ).length && ! $( targetPopup ).find( '.location-folder-list li.is_active' ).children( 'ul' ).hasClass( 'no-folder-list' ) ) {
						setTimeout(
							function () {
								$( targetPopup ).find( '.location-folder-list li.is_active' ).show().children( 'ul' ).show().children( 'li' ).show().children( 'span,i' ).show().closest( 'ul' ).siblings( 'span, i' ).hide();
							},
							100
						);
					} else {

						if ( $( targetPopup ).find( '.location-folder-list li.is_active' ).hasClass( 'has-ul' ).length ) {
							$( targetPopup ).find( '.location-folder-list li.is_active' ).children( 'span,i' ).hide().parent().children( 'ul' ).show().children( 'li' ).show();
						} else {
							setTimeout(
								function () {
									$( targetPopup ).find( '.location-folder-list li.is_active' ).show().children( 'span' ).show().parent().siblings( 'li' ).show().children( 'span,i' ).show();
								},
								10
							);
						}
					}
					$( targetPopup ).find( '.bb-rl-location-folder-list-wrap' ).find( '.location-folder-title' ).text( $( targetPopup ).find( '.location-folder-list li.is_active' ).closest( '.has-ul' ).children( 'span' ).text() ).siblings( '.location-folder-back' ).css( 'display', 'inline-block' );
					$( targetPopup ).find( '.bb-rl-folder-selected-id' ).val( $( targetPopup ).find( '.location-folder-list li.is_active' ).attr( 'data-id' ) );
					$( targetPopup ).find( '.location-folder-list li[data-id="' + currentMoveItemID + '"]' ).children().hide();
				}

				if ( currentTargetParent === '0' ) {
					$( targetPopup ).find( '.location-folder-list' ).children( 'li' ).show();
					$( targetPopup ).find( '.bb-rl-location-folder-list-wrap' ).find( '.location-folder-title' ).text( bbRlMedia.target_text );
					$( targetPopup ).find( '.location-folder-back' ).hide();
				}

				// Disable move button if current folder is already a parent.
				setTimeout(
					function () {

						var fileID;

						if ( $( targetPopup ).find( '.bb-rl-breadcrumbs-append-ul-li .item > span:last-child' ).hasClass( 'hidden' ) ) {
								fileID = $( targetPopup ).find( '.bb-rl-breadcrumbs-append-ul-li .item > span:last-child' ).prev().attr( 'id' );
						} else {
							fileID = $( targetPopup ).find( '.bb-rl-breadcrumbs-append-ul-li .item > span:last-child' ).attr( 'id' );
						}
						if ( currentTargetParent === fileID && ( $( targetPopup ).hasClass( 'bb-rl-media-move-file' ) || $( targetPopup ).hasClass( 'bb-rl-media-move-folder' ) ) ) {
							$( targetPopup ).find( '.bb-rl-document-move' ).addClass( 'is-disabled' );
							$( targetPopup ).find( '.bb-rl-folder-move' ).addClass( 'is-disabled' );
						} else {
							$( targetPopup ).find( '.bb-rl-document-move' ).removeClass( 'is-disabled' );
							$( targetPopup ).find( '.bb-rl-folder-move' ).removeClass( 'is-disabled' );
						}

					},
					100
				);

				var $this = $( targetPopup ).find( '.location-folder-list .is_active > span' ),
					$bc   = $( '<div class="item"></div>' );

				$this.parents( 'li' ).each(
					function ( n, li ) {
						var $a = $( li ).children( 'span' ).clone();
						$bc.prepend( '', $a );
					}
				);
				$( targetPopup ).find( '.bb-rl-breadcrumbs-append-ul-li .breadcrumb' ).html( $bc.prepend( '<span data-id="0">' + bbRlMedia.target_text + '</span>' ) );

			}
		},

		clearFolderLocationUI: function ( event ) {

			var closest_parent = jQuery( event.currentTarget ).closest( '.bb-rl-has-folderlocationUI' );
			if ( closest_parent.length > 0 ) {

				closest_parent.find( '.bb-rl-location-folder-list-wrap-main .bb-rl-location-folder-list-wrap .location-folder-list li' ).each(
					function () {
						jQuery( this ).removeClass( 'is_active' ).find( 'span.selected:not(.disabled)' ).removeClass( 'selected' );
						jQuery( this ).find( 'ul' ).hide();
					}
				);

				closest_parent.find( '.bb-rl-location-folder-list-wrap-main .bb-rl-location-folder-list-wrap .location-folder-list li' ).show().children( 'span, i' ).show();
				closest_parent.find( '.location-folder-title' ).text( bbRlMedia.target_text );
				closest_parent.find( '.location-folder-back' ).hide().closest( '.bb-rl-has-folderlocationUI' ).find( '.bb-rl-folder-selected-id' ).val( '0' );
				closest_parent.find( '.ac_document_search_folder' ).val( '' );
				closest_parent.find( '.bb-model-header h4 span' ).text( '...' );
				closest_parent.find( '.bb_rl_ac_document_search_folder_list ul' ).html( '' ).parent().hide().siblings( '.bb-rl-location-folder-list-wrap' ).find( '.location-folder-list' ).show();
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
			var self = this, target = $( event.currentTarget ), data, privacy = $( '#bb-media-privacy' ), dir_label;
			event.preventDefault();

			if ( target.hasClass( 'saving' ) ) {
				return false;
			}

			target.addClass( 'saving' );

			if ( self.current_tab === 'bp-dropzone-content' ) {

				var post_content = $( '#bp-media-post-content' ).val();

				var targetPopup   = $( event.currentTarget ).closest( '.open-popup' );
				var selectedAlbum = targetPopup.find( '.bb-rl-album-selected-id' ).val();
				if ( selectedAlbum.length && parseInt( selectedAlbum ) > 0 ) {
					var $dropZoneMediaLength = self.dropzone_media.length;
					for ( var i = 0; i < $dropZoneMediaLength; i++ ) {
						self.dropzone_media[ i ].album_id = selectedAlbum;
					}

				} else {
					selectedAlbum = self.album_id;
				}

				data = {
					'action': 'media_save',
					'_wpnonce': bbRlNonce.media,
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
						url: bbRlAjaxUrl,
						data: data,
						success: function ( response ) {
							if ( response.success ) {

								// It's the very first media, let's make sure the container can welcome it!
								if ( ! $( '#media-stream ul.media-list' ).length ) {
									location.reload( true );
								}

								var $photoSection   = $( '.bb-photos-actions' ),
									$buddypressElem = $( '#buddypress' );
								if ( $photoSection.length > 0 ) {
									$photoSection.show();
								}

								// Prepend the activity.
								bp.Nouveau.inject( '#media-stream ul.media-list', response.data.media, 'prepend' );

								if ( response.data.media_personal_count ) {
									if ( $buddypressElem.find( '.bp-wrap .users-nav ul li#media-personal-li a span.count' ).length ) {
										$buddypressElem.find( '.bp-wrap .users-nav ul li#media-personal-li a span.count' ).text( response.data.media_personal_count );
									} else {
										var mediaPersonalSpanTag = document.createElement( 'span' );
										mediaPersonalSpanTag.setAttribute( 'class', 'count' );
										var mediaPersonalSpanTagTextNode = document.createTextNode( response.data.media_personal_count );
										mediaPersonalSpanTag.appendChild( mediaPersonalSpanTagTextNode );
										$buddypressElem.find( '.bp-wrap .users-nav ul li#media-personal-li a' ).append( mediaPersonalSpanTag );
									}
								}

								if ( response.data.media_group_count ) {
									if ( $buddypressElem.find( '.bb-item-count' ).length > 0 && 'yes' !== BP_Nouveau.media.is_media_directory ) {
										dir_label = BP_Nouveau.dir_labels.hasOwnProperty( 'media' ) ?
										(
											1 === parseInt( response.data.media_group_count ) ?
											BP_Nouveau.dir_labels.media.singular : BP_Nouveau.dir_labels.media.plural
										)
										: '';
										$buddypressElem.find( '.bb-item-count' ).html( '<span class="bb-count">' + response.data.media_group_count + '</span> ' + dir_label );
									} else if ( $buddypressElem.find( '.bp-wrap .groups-nav ul li#photos-groups-li a span.count' ).length ) {
										$buddypressElem.find( '.bp-wrap .groups-nav ul li#photos-groups-li a span.count' ).text( response.data.media_group_count );
									} else {
										var photoGroupSpanTag = document.createElement( 'span' );
										photoGroupSpanTag.setAttribute( 'class', 'count' );
										var photoGroupSpanTagTextNode = document.createTextNode( response.data.media_group_count );
										photoGroupSpanTag.appendChild( photoGroupSpanTagTextNode );
										$buddypressElem.find( '.bp-wrap .users-nav ul li#photos-groups-li a' ).append( photoGroupSpanTag );
									}
								}

								if ( 'yes' === bbRlMedia.is_media_directory ) {
									$buddypressElem.find( '.media-type-navs ul.media-nav li#media-all a span.count' ).text( response.data.media_all_count );
									$buddypressElem.find( '.media-type-navs ul.media-nav li#media-personal a span.count' ).text( response.data.media_personal_count );
									$buddypressElem.find( '.media-type-navs ul.media-nav li#media-groups a span.count' ).text( response.data.media_group_count );
								}

								var $dropZoneMediaLength = self.dropzone_media.length;
								for ( var i = 0; i < $dropZoneMediaLength; i++ ) {
									self.dropzone_media[ i ].saved = true;
								}

								// Reset the selector album_id.
								targetPopup.find( '.bb-rl-album-selected-id' ).val( 0 );

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
					'_wpnonce': bbRlNonce.media,
					'medias': selected,
					'album_id': self.album_id,
					'group_id': self.group_id
				};

				$( '#bp-existing-media-content .bp-feedback' ).remove();

				$.ajax(
					{
						type: 'POST',
						url: bbRlAjaxUrl,
						data: data,
						success: function ( response ) {
							if ( response.success ) {

								// It's the very first media, let's make sure the container can welcome it!
								if ( ! $( '#media-stream ul.media-list' ).length ) {
									$( '#media-stream' ).html( $( '<ul></ul>' ).addClass( 'media-list item-list bp-list bb-photo-list grid' ) );
								}

								var $photosSection = $( '.bb-photos-actions' );
								if ( $photosSection.length > 0 ) {
									$photosSection.show();
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
			} else if ( ! self.current_tab ) {
				self.closeUploader( event );
				target.removeClass( 'saving' );
			}

		},

		submitDocumentMedia: function ( event ) {
			var self         = this, target = $( event.currentTarget ), data,
				currentPopup = $( event.currentTarget ).closest( '#bp-media-uploader' );
			event.preventDefault();

			if ( target.hasClass( 'saving' ) ) {
				return false;
			}
			var $document = $( document );
			target.addClass( 'saving' );

			if ( self.current_tab === 'bp-dropzone-content' ) {

				var post_content = $( '#bp-media-post-content' ).val();
				var privacy      = $( '#bb-document-privacy' ).val();

				var targetPopup   = $( event.currentTarget ).closest( '.open-popup' );
				var selectedAlbum = targetPopup.find( '.bb-rl-folder-selected-id' ).val();
				var currentAlbum  = targetPopup.find( '.bb-rl-folder-selected-id' ).data( 'value' );
				var hasNotAlbum   = true;
				if ( selectedAlbum.length && parseInt( selectedAlbum ) > 0 ) {

					if ( typeof currentAlbum !== 'undefined' && parseInt( selectedAlbum ) !== parseInt( currentAlbum ) ) {
						hasNotAlbum = false;
					}

					var $dropZoneMediaLength = self.dropzone_media.length;
					for ( var i = 0; i < $dropZoneMediaLength; i++ ) {
						self.dropzone_media[ i ].folder_id = selectedAlbum;
					}

				}

				data = {
					'action': 'document_document_save',
					'_wpnonce': bbRlNonce.media,
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
						url: bbRlAjaxUrl,
						data: data,
						success: function ( response ) {
							if ( response.success ) {

								// It's the very first media, let's make sure the container can welcome it!
								if ( ! $( '#media-stream div#media-folder-document-data-table' ).length ) {
									$( '#media-stream' ).html( $( '<div></div>' ).addClass( 'display' ) );
									$( '#media-stream div' ).attr( 'id', 'media-folder-document-data-table' );
									$( '.bb-photos-actions' ).show();
								}

								// Reload the page if no document and adding first time.
								if ( $( '.document-data-table-head' ).length ) {
									if ( 'yes' === bbRlMedia.is_document_directory ) {
										var store = bp.Nouveau.getStorage( 'bp-document' );
										var scope = store.scope;
										if ( 'groups' === scope ) {
											$document.find( 'li#document-personal a' ).trigger( 'click' );
											$document.find( 'li#document-personal' ).trigger( 'click' );
										} else {
											// Prepend the activity.
											hasNotAlbum ? bp.Nouveau.inject( '#media-stream div#media-folder-document-data-table', response.data.document, 'prepend' ) : '';

										}
									} else {
										// Prepend the activity.
										hasNotAlbum ? bp.Nouveau.inject( '#media-stream div#media-folder-document-data-table', response.data.document, 'prepend' ) : '';
									}
								} else {
									location.reload( true );
								}

								$( '#bp-media-post-content' ).val( '' );

								var $dropZoneMediaLength = self.dropzone_media.length;
								for ( var i = 0; i < $dropZoneMediaLength; i++ ) {
									self.dropzone_media[ i ].saved = true;
								}

								self.closeUploader( event );
								$document.removeClass( 'open-popup' );
								jQuery( window ).scroll();

							} else {
								$document.removeClass( 'open-popup' );
								$( '#bp-dropzone-content' ).prepend( response.data.feedback );
							}

							target.removeClass( 'saving' );

							currentPopup.find( '#bp-media-document-submit' ).hide();

						}
					}
				);

			} else if ( ! self.current_tab ) {
				self.closeUploader( event );
				target.removeClass( 'saving' );
			}

		},

		saveAlbum: function ( event ) {
			this.saveItem( event, 'album', 'media' );
		},

		saveFolder: function ( event ) {
			this.saveItem( event, 'folder', 'document' );
		},

		saveChildFolder: function ( event ) {
			this.saveItem( event, 'child_folder', 'document' );
		},

		renameChildFolder: function ( event ) {
			event.preventDefault();
			var target  = $( event.currentTarget ), self = this,
				title   = $( '#bp-media-edit-child-folder #bb-album-child-title' ),
				privacy = $( '#bp-media-edit-child-folder #bb-rl-folder-privacy' ),
				id      = this.currentTargetParent;

			var pattern     = /[\\/?%*:|"<>]+/g; // regex to find not supported characters - \ / ? % * : | " < >
			var matches     = pattern.exec( title.val() ),
				matchStatus = Boolean( matches );

			if ( $.trim( title.val() ) === '' || matchStatus ) {
				title.addClass( 'error' );
				return false;
			} else {
				title.removeClass( 'error' );
			}

			target.prop( 'disabled', true ).addClass( 'loading' );

			var data = {
				'action': 'document_edit_folder',
				'_wpnonce': bbRlNonce.media,
				'title': title.val(),
				'privacy': privacy.val(),
				'id': id,
				'group_id': self.current_group_id,
			};

			// remove all feedback erros from the DOM.
			$( '.bb-single-album-header .bp-feedback' ).remove();
			$( '#bb-rl-media-create-album-popup .bp-feedback' ).remove();

			$.ajax(
				{
					type: 'POST',
					url: bbRlAjaxUrl,
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
								$( '#bb-rl-media-create-album-popup .bb-model-header' ).after( response.data.feedback );
							}
						}
					}
				}
			);

		},

		deleteAlbum: function ( event ) {
			event.preventDefault();
			this.deleteAttachment( event, 'media', 'album' );
		},

		deleteFolder: function ( event ) {
			event.preventDefault();
			this.deleteAttachment( event, 'document', 'folder' );
		},

		/**
		 * [injectQuery description]
		 *
		 * @return {[type]} [description]
		 * @param event
		 */
		injectMedias: function ( event ) {
			this.injectAttachments( event, 'media' );
		},

		injectDocuments: function ( event ) {
			this.injectAttachments( event, 'document' );
		},

		/* jshint ignore:start */
		sortDocuments: function ( event ) {

			var sortTarget = $( event.currentTarget ), sortArg = sortTarget.data( 'target' ), search_terms = '',
				order_by   = 'date_created', next_page = 1;
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
			var sort   = sortTarget.hasClass( 'asce' ) ? 'DESC' : 'ASC',
				extras = {};

			extras.orderby = order_by;
			extras.sort    = sort;

			if ( 'group' !== order_by ) {
				bp.Nouveau.setStorage( 'bp-document', 'extras', extras );
			}

			var store      = bp.Nouveau.getStorage( 'bp-document' ),
				scope      = store.scope || null, filter = store.filter || null,
				searchElem = $( '#buddypress .bp-dir-search-form input[type=search]' );
			if ( searchElem.length ) {
				search_terms = searchElem.val();
			}

			this.sort_by      = sort;
			this.order_by     = order_by;
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
			var titleField = target.closest( '.bp-document-listing' ).length === 0 ? '#bb-album-child-title' : '#bb-album-title';

			if ( target.closest( '.document-options' ).length ) { // Check if this is /document page.
				titleField = '#bb-album-title';
			}

			if ( target.hasClass( 'bb-field-steps-next' ) && currentSlide.find( titleField ).val().trim() === '' ) {
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
				currentPopup.find( '#bp-media-document-submit, #bp-media-document-prev, .bb-rl-document-open-create-popup-folder, #bb-document-privacy' ).show();
				if ( Number( $( currentPopup ).find( '.bb-rl-folder-selected-id' ) ) !== 0 && $( currentPopup ).find( '.location-folder-list li.is_active' ).length ) {
					$( currentPopup ).find( '.location-folder-list' ).scrollTop( $( currentPopup ).find( '.location-folder-list li.is_active' ).offset().top - $( currentPopup ).find( '.location-folder-list' ).offset().top );
				}
				$( currentPopup ).find( '.bb-rl-breadcrumbs-append-ul-li .breadcrumb .item span:not(.hidden)' ).each(
					function ( i ) {
						if ( i > 0 ) {
							if ( $( currentPopup ).find( '.bb-rl-breadcrumbs-append-ul-li .breadcrumb .item' ).width() > $( currentPopup ).find( '.bb-rl-breadcrumbs-append-ul-li .breadcrumb' ).width() ) {
								$( currentPopup ).find( '.bb-rl-breadcrumbs-append-ul-li .breadcrumb .item span.hidden' ).append( $( currentPopup ).find( '.bb-rl-breadcrumbs-append-ul-li .breadcrumb .item span' ).eq( 2 ) );

								if ( ! $( currentPopup ).find( '.bb-rl-breadcrumbs-append-ul-li .breadcrumb .item .more_options' ).length ) {
										$( '<span class="more_options">...</span>' ).insertAfter( $( currentPopup ).find( '.bb-rl-breadcrumbs-append-ul-li .breadcrumb .item span' ).eq( 0 ) );
								}

							}
						}
					}
				);
			} else {
				$( target ).hide();
				currentPopup.find( '#bp-media-document-prev, .bb-rl-document-open-create-popup-folder' ).hide();
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
				currentPopup.find( '#bp-media-submit, #bp-media-prev, .bb-rl-media-open-create-popup-folder.create-album' ).show();
				currentPopup.find( '#bb-media-privacy' ).show();
				if ( Number( $( currentPopup ).find( '.bb-rl-album-selected-id' ) ) !== 0 && $( currentPopup ).find( '.location-album-list li.is_active' ).length ) {
					$( currentPopup ).find( '.location-album-list' ).scrollTop( $( currentPopup ).find( '.location-album-list li.is_active' ).offset().top - $( currentPopup ).find( '.location-album-list' ).offset().top );
				}
			} else {
				$( target ).hide();
				currentPopup.find( '#bp-media-prev, .bb-rl-media-open-create-popup-folder' ).hide();
				currentPopup.find( '.bb-field-steps-2' ).slideUp( 200 ).siblings( '.bb-field-steps' ).slideDown( 200 );
				if ( currentPopup.closest( '#bp-media-single-album' ).length ) {
					$( '#bb-media-privacy' ).hide();
				}
			}

		},

		/**
		 * [appendQuery description]
		 *
		 * @return {[type]} [description]
		 * @param event
		 */
		appendMedia: function ( event ) {
			var store       = bp.Nouveau.getStorage( 'bp-media' ),
				scope       = store.scope || null, filter = store.filter || null;
			var eventTarget = $( event.currentTarget );
			if ( eventTarget.hasClass( 'load-more' ) ) {
				var next_page = Number( this.current_page_existing_media ) + 1, self = this, search_terms = '';

				// Stop event propagation.
				event.preventDefault();

				eventTarget.find( 'a' ).first().addClass( 'loading' );

				var searchElem = $( '#buddypress .dir-search input[type=search]' );
				if ( searchElem.length ) {
					search_terms = searchElem.val();
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
							eventTarget.remove();

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
		 * @return {[type]} [description]
		 * @param event
		 */
		appendAlbums: function ( event ) {
			var next_page = Number( this.current_page_albums ) + 1, self = this;

			// Stop event propagation.
			event.preventDefault();

			$( event.currentTarget ).find( 'a' ).first().addClass( 'loading' );

			var data = {
				'action': 'media_albums_loader',
				'_wpnonce': bbRlNonce.media,
				'page': next_page
			};

			$.ajax(
				{
					type: 'POST',
					url: bbRlAjaxUrl,
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
			var eventTarget = $( event.currentTarget );
			var video       = eventTarget.find( 'video' ).get( 0 ),
				$button     = eventTarget.find( '.gif-play-button' );
			if ( video.paused === true ) {
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
					var video   = $( this ).find( 'video' ).get( 0 ),
						$button = $( this ).find( '.gif-play-button' );

					if ( $( this ).is( ':in-viewport' ) ) {
						video.play(); // Play the video.

						$button.hide(); // Update the button text to 'Pause'.
					} else {
						video.pause(); // Pause the video.

						$button.show(); // Update the button text to 'Play'.
					}
				}
			);
		},

		/**
		 * File action Button
		 */
		fileActionButton: function ( event ) {
			var eventTarget = $( event.currentTarget ), parent = eventTarget.parent();
			if (
				parent.hasClass( 'download_file' ) ||
				parent.hasClass( 'bb_rl_copy_download_file_url' ) ||
				parent.hasClass( 'redirect-activity-privacy-change' )
			) {
				return;
			}

			event.preventDefault();
			eventTarget.closest( '.media-folder_items' ).toggleClass( 'is-visible' ).siblings( '.media-folder_items' ).removeClass( 'is-visible' );
			eventTarget.closest( '.media-folder_items' ).find( '.media-folder_action__list.bb_rl_more_dropdown' ).toggleClass( 'open' ).closest( '.media-folder_items' ).siblings( '.media-folder_items' ).find( '.media-folder_action__list.bb_rl_more_dropdown' ).removeClass( 'open' );
			$( 'body' ).addClass( 'document_more_option_open' );
		},

		/**
		 * File action Copy Download Link
		 */
		copyDownloadLink: function ( event ) {
			event.preventDefault();
			var currentTarget = event.currentTarget, currentTargetCopy = 'document_copy_link';
			$( 'body' ).append( '<textarea style="position:absolute;opacity:0;" id="' + currentTargetCopy + '"></textarea>' );
			var oldText = $( currentTarget ).text();
			$( currentTarget ).text( bbRlMedia.copy_to_clip_board_text );
			var currentTargetCopyElem = $( '#' + currentTargetCopy );
			currentTargetCopyElem.val( $( currentTarget ).attr( 'href' ) );
			currentTargetCopyElem.select();
			document.execCommand( 'copy' );

			setTimeout(
				function () {
					$( currentTarget ).text( oldText );
				},
				2000
			);

			// $( '#' + currentTargetCopy ).remove();
			return false;
		},

		/**
		 * File Activity action Button
		 */
		fileActivityActionButton: function ( event ) {
			event.preventDefault();
			var eventTarget = $( event.currentTarget );
			if ( eventTarget.parent().hasClass( 'bb_rl_copy_download_file_url' ) ) {
				return;
			}

			eventTarget.closest( '.bb-rl-activity-media-elem' ).toggleClass( 'is-visible' ).siblings().removeClass( 'is-visible' ).closest( '.activity-item' ).siblings().find( '.bb-rl-activity-media-elem' ).removeClass( 'is-visible' );
			eventTarget.closest( '.bb-rl-activity-media-elem' ).find( '.bb_rl_more_dropdown' ).toggleClass( 'open' ).closest( '.bb-rl-activity-media-elem' ).siblings().find( '.bb_rl_more_dropdown' ).removeClass( 'open' ).closest( '.activity-item' ).siblings().find( '.bb-rl-activity-media-elem .bb_rl_more_dropdown' ).removeClass( 'open' );
			$( 'body' ).addClass( 'document_more_option_open' );

			if ( eventTarget.closest( '.bb-rl-activity-media-elem' ).length < 1 ) {
				eventTarget.closest( '.bb-photo-thumb' ).toggleClass( 'is-visible' ).parent().siblings().find( '.bb-photo-thumb' ).removeClass( 'is-visible' ).removeClass( 'is-visible' );
				eventTarget.closest( '.bb-photo-thumb' ).find( '.bb_rl_more_dropdown' ).toggleClass( 'open' ).closest( '.bb-photo-thumb' ).parent().siblings().find( '.bb-photo-thumb .bb_rl_more_dropdown' ).removeClass( 'open' );
			}

			if ( event.currentTarget.tagName.toLowerCase() === 'a' && ( ! eventTarget.hasClass( 'bb-rl-document-action_more' ) && ! eventTarget.hasClass( 'bb_rl_more_dropdown__action' ) ) ) {
				eventTarget.closest( '.bb-rl-activity-media-elem' ).removeClass( 'is-visible' ).find( '.bb_rl_more_dropdown' ).removeClass( 'open' );
				eventTarget.closest( '.bb-photo-thumb' ).removeClass( 'is-visible' ).find( '.bb_rl_more_dropdown' ).removeClass( 'open' );
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

			if ( $( element ).hasClass( 'bb-rl-document-action_more' ) || $( element ).parent().hasClass( 'bb-rl-document-action_more' ) || $( element ).hasClass( 'media-folder_action__anchor' ) || $( element ).parent().hasClass( 'media-folder_action__anchor' ) || $( element ).hasClass( 'bb_rl_more_dropdown__action' ) || $( element ).parent().hasClass( 'bb_rl_more_dropdown__action' ) ) {
				return event;
			}

			$( '.bb-rl-activity-media-elem.is-visible' ).removeClass( 'is-visible' );
			$( '.media-folder_items.is-visible' ).removeClass( 'is-visible' );
			$( '.bb-photo-thumb.is-visible' ).removeClass( 'is-visible' );
			$( '.bb-item-thumb.is-visible' ).removeClass( 'is-visible' );
			$( '.bb-rl-activity-video-elem.is-visible' ).removeClass( 'is-visible' );
			$( '.bb-rl-more_dropdown-wrap.is-visible' ).removeClass( 'is-visible' );
			$( '.bb-rl-more_dropdown-wrap .bb_rl_more_dropdown' ).removeClass( 'open' );
			$( '.bb-rl-activity-media-elem .bb_rl_more_dropdown' ).removeClass( 'open' );
			$( '.bb-photo-thumb .bb_rl_more_dropdown' ).removeClass( 'open' );
			$( '.media-folder_items .bb_rl_more_dropdown' ).removeClass( 'open' );
			$( 'body' ).removeClass( 'document_more_option_open video_more_option_open item_more_option_open' );

		},

		/**
		 * Toggle Text File
		 */
		toggleCodePreview: function ( event ) {
			event.preventDefault();
			$( event.currentTarget ).closest( '.bb-rl-document-activity' ).toggleClass( 'code-full-view' );
		},

		/**
		 * Text File Activity Preview
		 */
		documentCodeMirror: function () {
			$( '.bb-rl-document-text:not(.loaded), .document-text:not(.loaded)' ).each(
				function () {
					var $this          = $( this );
					var data_extension = $this.attr( 'data-extension' );
					var fileMode       = $this.attr( 'data-extension' );
					if ( data_extension === 'html' || data_extension === 'htm' ) { // HTML file need specific mode.
						fileMode = 'text/html';
					}
					if ( data_extension === 'js' ) { // mode not needed for javascript file.
						/* jshint ignore:start */
						var myCodeMirror = CodeMirror(
							$this[ 0 ],
							{
								value: $this.find( '.bb-rl-document-text-file-data-hidden' ).length > 0 ? $this.find( '.bb-rl-document-text-file-data-hidden' ).val() : $this.find( '.document-text-file-data-hidden' ).val(),
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
								value: $this.find( '.bb-rl-document-text-file-data-hidden' ).length > 0 ? $this.find( '.bb-rl-document-text-file-data-hidden' ).val() : $this.find( '.document-text-file-data-hidden' ).val(),
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
						$this.closest( '.bb-rl-document-text-wrap, .document-text-wrap' ).addClass( 'is_large' );
						$this.closest( '.bb-rl-document-activity' ).addClass( 'is_large' );
					}

				}
			);
		},

		/**
		 * Close popup on ESC.
		 */
		closePopup: function ( event ) {
			// Close popup if it's open.
			if ( event.keyCode === 27 ) {
				// Close Move popup.
				$( '.bb-rl-media-move-folder.open-popup .bb-rl-ac-folder-close-button:visible, .bb-rl-media-move-file .bb-rl-ac-media-close-button:visible, .bb-rl-media-move-folder.open-popup .bb-rl-close-create-popup-folder:visible,.bb-rl-media-move-file.open-popup .bb-rl-ac-document-close-button:visible, .bb-rl-media-move-file .bb-rl-close-create-popup-folder:visible, .bb-rl-media-move-photo.open .bb-rl-close-create-popup-album:visible' ).trigger( 'click' );

				// Close create folder popup.
				$( '#bp-media-create-folder #bp-media-create-folder-close:visible, #bp-media-create-child-folder #bp-media-create-folder-close:visible' ).trigger( 'click' );

				// Close document uploader popup.
				$( '#bp-media-uploader #bp-media-uploader-close:visible' ).trigger( 'click' );

				// Close Edit Folder popup.
				$( '#bp-media-edit-child-folder #bp-media-edit-folder-close:visible' ).trigger( 'click' );

				// Close create media album.
				$( '#bp-media-create-album #bp-media-create-album-close:visible' ).trigger( 'click' );

				$( '.media-folder_visibility select#bb-rl-folder-privacy:not(.hide)' ).each(
					function () {
						$( this ).attr( 'data-mouseup', 'false' ).addClass( 'hide' ).siblings( 'span' ).show().text( $( this ).find( 'option:selected' ).text() );
					}
				);

				// Close upload thumbnail popup.
				$( '.bb-rl-video-thumbnail-uploader .bb-rl-video-thumbnail-uploader-close:visible' ).trigger( 'click' );

				// Close Action popup.
				$( '.bb-action-popup .bb-close-action-popup:visible' ).trigger( 'click' );

			}
		},

		/**
		 * Submit popup on ENTER.
		 */
		submitPopup: function ( event ) {

			// return if modern is not visible.
			if ( $( document ).find( '.bb-rl-modal-wrapper:visible' ).length < 1 ) {
				return;
			}

			// Submit popup if it's open.
			if ( event.keyCode === 13 ) {
				// Submit Move popup.
				$( '.bb-rl-media-move-folder.open-popup .bb-rl-document-move:not(.is-disabled):visible, .bb-rl-media-move-folder.open-popup  .bb-rl-folder-move:not(.is-disabled):visible,.bb-rl-media-move-file.open-popup .bb-rl-document-move:not(.is-disabled):visible, .bb-rl-media-move-file.open-popup .bb-rl-document-create-popup-folder-submit:visible, .bb-rl-media-move-folder.open-popup .bb-rl-document-create-popup-folder-submit:visible, .bb-rl-media-move-file.open .bb-rl-media-move:not(.is-disabled):visible, .bb-rl-media-move-file.open .bb-rl-media-create-popup-album-submit:visible' ).trigger( 'click' );

				// Submit create folder popup.
				$( '#bp-media-create-folder #bp-media-create-folder-submit:visible, #bp-media-create-child-folder #bp-media-create-child-folder-submit:visible' ).trigger( 'click' );

				// Submit document uploader popup.
				$( '#bp-media-uploader #bp-media-document-submit:visible, #bp-media-uploader #bp-media-submit:visible' ).trigger( 'click' );

				// Submit Edit Folder popup.
				$( '#bp-media-edit-child-folder #bp-media-edit-child-folder-submit:visible' ).trigger( 'click' );

				// Submit create media album.
				$( '#bp-media-create-album #bp-media-create-album-submit:visible' ).trigger( 'click' );
			}
		},

		/**
		 * Update album nav count on create new album.
		 */
		updateAlbumNavCount: function ( count ) {
			var nav = $( '.single-screen-navs.albums-nav' ).find( 'ul li#albums-li' );
			if ( nav.length < 1 ) {
				return;
			}

			if ( $( nav ).find( 'span.count' ).length > 0 ) {
				$( nav ).find( 'span.count' ).text( count );
			} else {
				$( nav ).find( 'a#albums' ).append( '<span class="count">' + count + '</span>' );
			}
		},

		setupForumDropzoneEvents: function ( args ) {
			var self              = args.self,
				dropzoneObj       = args.dropzoneObj,
				target            = args.target,
				type              = args.type,
				dropzoneDataObj   = args.dropzoneDataObj,
				dropzoneContainer = args.dropzoneContainer;

			if ( 'video' === type ) {
				dropzoneObj.on(
					'addedfile',
					function ( file ) {
						if ( file.dataURL && file.dataThumb && file.dataThumb.length ) {
							// Get Thumbnail image from response.
							$( file.previewElement ).find( '.dz-video-thumbnail' ).prepend( '<img src=" ' + file.dataThumb + ' " alt=""/>' );
						} else {
							if ( bp.Nouveau.getVideoThumb ) {
								bp.Nouveau.getVideoThumb( file, '.dz-image' );
							}
						}
					}
				);
			}

			dropzoneObj.on(
				'sending',
				function ( file, xhr, formData ) {
					var action = 'document' === type ? type + '_' + type + '_upload' : type + '_upload';
					formData.append( 'action', action );
					var nonce = 'document' === type ? 'media' : type;
					formData.append( '_wpnonce', bbRlNonce[ nonce ] );
					var tool_box = target.closest( 'form' );
					tool_box.addClass( 'has-media' );
					['media', 'document', 'video', 'gif'].forEach(
						function ( subType ) {
							var dropZoneButton = tool_box.find( '#bb-rl-forums-' + subType + '-button' );
							if ( dropZoneButton ) {
									var buttonItems = dropZoneButton.parents( '.bb-rl-post-elements-buttons-item' );
								if ( type === subType ) {
									buttonItems.addClass( 'no-click' );
								} else {
									buttonItems.addClass( 'disable' );
								}
							}
						}
					);
				}
			);

			dropzoneObj.on(
				'uploadprogress',
				function ( element ) {
					var formElement = target.closest( 'form' );
					formElement.addClass( 'media-uploading' );
					var circle        = $( element.previewElement ).find( '.dz-progress-ring circle' )[ 0 ],
						radius        = circle.r.baseVal.value,
						circumference = radius * 2 * Math.PI;

					circle.style.strokeDasharray  = circumference + ' ' + circumference;
					var offset                    = circumference - element.upload.progress.toFixed( 0 ) / 100 * circumference;
					circle.style.strokeDashoffset = offset;
					if ( 'video' === type ) {
						if ( element.upload.progress <= 99 ) {
							$( element.previewElement ).find( '.dz-progress-count' ).text( element.upload.progress.toFixed( 0 ) + '% ' + bbRlVideo.i18n_strings.video_uploaded_text );
							circle.style.strokeDashoffset = offset;
						} else if ( element.upload.progress === 100 ) {
							circle.style.strokeDashoffset = circumference - 0.99 * circumference;
							$( element.previewElement ).find( '.dz-progress-count' ).text( '99% ' + bbRlVideo.i18n_strings.video_uploaded_text );
						}
					}
				}
			);

			dropzoneObj.on(
				'error',
				function ( file, response ) {
					if ( file.accepted ) {
						if ( typeof response !== 'undefined' && typeof response.data !== 'undefined' && typeof response.data.feedback !== 'undefined' ) {
							$( file.previewElement ).find( '.dz-error-message span' ).text( response.data.feedback );
						} else if ( file.status === 'error' && ( file.xhr && file.xhr.status === 0) ) { // update server error text to user friendly.
							$( file.previewElement ).find( '.dz-error-message span' ).text( bbRlMedia.connection_lost_error );
						}
					} else {
						var subType = 'media' === type ? 'document' : type;
						if ( ! jQuery( '.forum-' + subType + '-error-popup' ).length ) {
							if ( 'media' === type ) {
								$( 'body' ).append( '<div id="bp-media-create-folder" style="display: block;" class="open-popup forum-document-error-popup"><transition name="modal"><div class="bb-rl-modal-mask bb-white bbm-model-wrap"><div class="bb-rl-modal-wrapper"><div id="bb-rl-media-create-album-popup" class="modal-container bb-rl-has-folderlocationUI"><header class="bb-model-header"><h4>' + bbRlMedia.invalid_media_type + '</h4><a class="bb-model-close-button errorPopup" href="#"><span class="bb-icon-l bb-icon-times"></span></a></header><div class="bb-rl-field-wrap"><p>' + response + '</p></div></div></div></div></transition></div>' );
							} else if ( 'document' === type ) {
								$( 'body' ).append( '<div id="bp-media-create-folder" style="display: block;" class="open-popup forum-document-error-popup"><transition name="modal"><div class="bb-rl-modal-mask bb-white bbm-model-wrap"><div class="bb-rl-modal-wrapper"><div id="bb-rl-media-create-album-popup" class="modal-container bb-rl-has-folderlocationUI"><header class="bb-model-header"><h4>' + bbRlMedia.invalid_file_type + '</h4><a class="bb-model-close-button errorPopup" href="#"><span class="bb-icon-l bb-icon-times"></span></a></header><div class="bb-rl-field-wrap"><p>' + response + '</p></div></div></div></div></transition></div>' );
							} else if ( 'video' === type ) {
								$( 'body' ).append( '<div id="bp-video-create-album" style="display: block;" class="open-popup forum-video-error-popup"><transition name="modal"><div class="bb-rl-modal-mask bb-white bbm-model-wrap"><div class="bb-rl-modal-wrapper"><div id="bb-rl-video-create-album-popup" class="modal-container bb-rl-has-folderlocationUI"><header class="bb-model-header"><h4>' + bbRlVideo.invalid_video_type + '</h4><a class="bb-model-close-button errorPopup" href="#"><span class="bb-icon-l bb-icon-times"></span></a></header><div class="bb-rl-field-wrap"><p>' + response + '</p></div></div></div></div></transition></div>' );
							}
						}

						this.removeFile( file );
						var formElement = target.closest( 'form' );
						formElement.removeClass( 'media-uploading' );
					}
				}
			);

			if ( 'media' !== type ) {
				dropzoneObj.on(
					'accept',
					function ( file, done ) {
						if ( file.size === 0 ) {
							done( bbRlMedia[ 'empty_' + type + '_type' ] );
						} else {
							done();
						}
					}
				);
			}

			dropzoneObj.on(
				'success',
				function ( file, response ) {
					if ( 'video' === type && file.upload.progress === 100 ) {
						$( file.previewElement ).find( '.dz-progress-ring circle' )[ 0 ].style.strokeDashoffset = 0;
						$( file.previewElement ).find( '.dz-progress-count' ).text( '100% ' + bbRlVideo.i18n_strings.video_uploaded_text );
						$( file.previewElement ).closest( '.dz-preview' ).addClass( 'dz-complete' );
					}
					if ( response.data.id ) {
						file.id                  = response.id;
						response.data.uuid       = file.upload.uuid;
						response.data.menu_order = $( file.previewElement ).closest( '.dropzone' ).find( file.previewElement ).index() - 1;
						response.data.group_id   = self.group_id;
						response.data.saved      = false;

						if ( 'media' === type ) {
							response.data.album_id = self.album_id;
						} else if ( 'document' === type ) {
							response.data.folder_id = self.current_folder;
						} else if ( 'video' === type ) {
							response.data.album_id   = self.album_id;
							response.data.js_preview = $( file.previewElement ).find( '.dz-image img' ).attr( 'src' );
						}
						dropzoneDataObj.push( response.data );
						if ( dropzoneContainer.closest( '#whats-new-attachments' ).find( '#bbp_' + type ).length ) {
							dropzoneContainer.closest( '#whats-new-attachments' ).find( '#bbp_' + type ).val( JSON.stringify( dropzoneDataObj ) );
						}

						if ( 'document' === type ) {
							var filename      = file.upload.filename;
							var fileExtension = filename.substr(
								(
								filename.lastIndexOf( '.' ) + 1
								)
							);
							var file_icon     = (
								! _.isUndefined( response.data.svg_icon ) ? response.data.svg_icon : ''
							);
							var icon_class    = ! _.isEmpty( file_icon ) ? file_icon : 'bb-icon-file-' + fileExtension;

							if ( $( file.previewElement ).find( '.dz-details .dz-icon .bb-icons-rl-file' ).length ) {
								$( file.previewElement ).find( '.dz-details .dz-icon .bb-icons-rl-file' ).removeClass( 'bb-icon-file' ).addClass( icon_class );
							}
						}
					} else {
						if ( 'media' === type ) {
							if ( ! jQuery( '.forum-' + type + '-error-popup' ).length ) {
								$( 'body' ).append( '<div id="bp-media-create-folder" style="display: block;" class="open-popup"><transition name="modal"><div class="bb-rl-modal-mask bb-white bbm-model-wrap"><div class="bb-rl-modal-wrapper"><div id="bb-rl-media-create-album-popup" class="modal-container bb-rl-has-folderlocationUI"><header class="bb-model-header"><h4>' + bbRlMedia.invalid_media_type + '</h4><a class="bb-model-close-button" id="bp-media-create-folder-close" href="#"><span class="bb-icon-l bb-icon-times"></span></a></header><div class="bb-rl-field-wrap"><p>' + response.data.feedback + '</p></div></div></div></div></transition></div>' );
							}
							this.removeFile( file );
						} else if ( 'document' === type || 'video' === type ) {
							var node, _i, _len, _ref, _results;
							var message = response.data.feedback;
							file.previewElement.classList.add( 'dz-error' );
							_ref     = file.previewElement.querySelectorAll( '[data-dz-errormessage]' );
							_results = [];
							for ( _i = 0, _len = _ref.length; _i < _len; _i++ ) {
								node = _ref[ _i ];
								_results.push( node.textContent = message );
							}
							return _results;
						}
					}
				}
			);

			dropzoneObj.on(
				'removedfile',
				function ( file ) {
					if ( true === bp.Nouveau.Media.reply_topic_allow_delete_media ) {
						if ( dropzoneDataObj.length ) {
							for ( var i in dropzoneDataObj ) {
								// Compare using media_edit_data.id which contains the actual media ID
								// that matches dropzoneDataObj[i].id (media attachment ID)
								var fileMediaId = null;
								if( file.media_edit_data ) {
									fileMediaId = file.media_edit_data.id;
								} else if( file.document_edit_data ) {
									fileMediaId = file.document_edit_data.id;
								} else if( file.video_edit_data ) {
									fileMediaId = file.video_edit_data.id;
								} else {
									fileMediaId = file.id;
								}

								if ( fileMediaId === dropzoneDataObj[ i ].id ) {
									if ( (
										! this.bbp_is_reply_edit && ! this.bbp_is_topic_edit && ! this.bbp_is_forum_edit
										) && typeof dropzoneDataObj[ i ].saved !== 'undefined' && ! dropzoneDataObj[ i ].saved && 'edit' === bp.Nouveau.Media.reply_topic_display_post ) {
										self.removeAttachment( dropzoneDataObj[ i ].id );
									}
									dropzoneDataObj.splice( i, 1 );
									if ( dropzoneContainer.closest( '#whats-new-attachments' ).find( '#bbp_' + type ).length ) {
										dropzoneContainer.closest( '#whats-new-attachments' ).find( '#bbp_' + type ).val( JSON.stringify( dropzoneDataObj ) );
									}
									break;
								}
							}
						}
						if ( ! _.isNull( dropzoneObj.files ) && dropzoneObj.files.length === 0 ) {
							var tool_box = target.closest( 'form' );
							['media', 'document', 'video', 'gif'].forEach(
								function ( subType ) {
									var dropZoneButton = tool_box.find( '#bb-rl-forums-' + subType + '-button' );
									if ( dropZoneButton ) {
											var buttonItems = dropZoneButton.parents( '.bb-rl-post-elements-buttons-item' );
										if ( type === subType ) {
											buttonItems.removeClass( 'no-click' );
										} else {
											buttonItems.removeClass( 'disable' );
										}
									}
								}
							);
						}
					}
					if ( ! _.isNull( dropzoneObj.files ) && dropzoneObj.files.length === 0 ) {
						var targetForm = target.closest( 'form' );
						targetForm.removeClass( 'has-media' );
					}
				}
			);

			dropzoneObj.on(
				'complete',
				function ( file ) {
					if ( this.getUploadingFiles().length === 0 && this.getQueuedFiles().length === 0 && this.files.length > 0 ) {
						var formElement = target.closest( 'form' );
						formElement.removeClass( 'media-uploading' );
					}
					if ( 'document' === type ) {
						var filename      = ! _.isUndefined( file.name ) ? file.name : '';
						var fileExtension = filename.substr(
							(
							filename.lastIndexOf( '.' ) + 1
							)
						);
						var file_icon     = (
							! _.isUndefined( file.svg_icon ) ? file.svg_icon : ''
						);
						var icon_class    = ! _.isEmpty( file_icon ) ? file_icon : 'bb-icon-file-' + fileExtension;
						if (
							$( file.previewElement ).find( '.dz-details .dz-icon .bb-icon-file' ).length &&
							$( file.previewElement ).find( '.dz-details .dz-icon .bb-icon-file' ).hasClass( 'bb-icon-file' )
						) {
							$( file.previewElement ).find( '.dz-details .dz-icon .bb-icon-file' ).removeClass( 'bb-icon-file' ).addClass( icon_class );
						}
					}
				}
			);
		},

		setupGroupMessageDropzoneEvents: function ( args ) {
			var self            = args.self,
				dropzoneObj     = args.dropzoneObj,
				target          = args.target,
				type            = args.type,
				dropzoneDataObj = args.dropzoneDataObj;

			if ( 'video' === type ) {
				dropzoneObj.on(
					'addedfile',
					function ( file ) {
						if ( file.dataURL && file.dataThumb && file.dataThumb.length ) {
							// Get Thumbnail image from response.
							$( file.previewElement ).find( '.dz-image' ).prepend( '<img src=" ' + file.dataThumb + ' " alt=""/>' );
						} else {
							if ( bp.Nouveau.getVideoThumb ) {
								bp.Nouveau.getVideoThumb( file, '.dz-image' );
							}
						}
					}
				);
			}

			dropzoneObj.on(
				'sending',
				function ( file, xhr, formData ) {
					var action = 'document' === type ? type + '_' + type + '_upload' : type + '_upload';
					formData.append( 'action', action );
					var nonce = 'document' === type ? 'media' : type;
					formData.append( '_wpnonce', bbRlNonce[ nonce ] );
					var tool_box = target.closest( '#send_group_message_form' );
					['media', 'document', 'video', 'gif'].forEach(
						function ( subType ) {
							var dropZoneButton = tool_box.find( '#bp-group-messages-' + subType + '-button' );
							if ( dropZoneButton ) {
									var buttonItems = dropZoneButton.parents( '.post-elements-buttons-item' );
								if ( type === subType ) {
									buttonItems.addClass( 'no-click' );
								} else {
									buttonItems.addClass( 'disable' );
								}
							}
						}
					);
				}
			);

			dropzoneObj.on(
				'uploadprogress',
				function ( element ) {
					var circle        = $( element.previewElement ).find( '.dz-progress-ring circle' )[ 0 ],
						radius        = circle.r.baseVal.value,
						circumference = radius * 2 * Math.PI;

					circle.style.strokeDasharray  = circumference + ' ' + circumference;
					var offset                    = circumference - element.upload.progress.toFixed( 0 ) / 100 * circumference;
					circle.style.strokeDashoffset = offset;
					if ( 'video' === type ) {
						if ( element.upload.progress <= 99 ) {
							$( element.previewElement ).find( '.dz-progress-count' ).text( element.upload.progress.toFixed( 0 ) + '% ' + bbRlVideo.i18n_strings.video_uploaded_text );
							circle.style.strokeDashoffset = offset;
						} else if ( element.upload.progress === 100 ) {
							circle.style.strokeDashoffset = circumference - 0.99 * circumference;
							$( element.previewElement ).find( '.dz-progress-count' ).text( '99% ' + bbRlVideo.i18n_strings.video_uploaded_text );
						}
					}
				}
			);

			dropzoneObj.on(
				'error',
				function ( file, response ) {
					if ( file.accepted ) {
						if ( typeof response !== 'undefined' && typeof response.data !== 'undefined' && typeof response.data.feedback !== 'undefined' ) {
							$( file.previewElement ).find( '.dz-error-message span' ).text( response.data.feedback );
						} else if ( file.status === 'error' && ( file.xhr && file.xhr.status === 0) ) { // update server error text to user friendly.
							$( file.previewElement ).find( '.dz-error-message span' ).text( bbRlMedia.connection_lost_error );
						}
					} else {
						var subType = 'media' === type ? 'document' : type;
						if ( 'media' === type || 'document' === type ) {
							if ( ! jQuery( '.group-' + subType + '-error-popup' ).length ) {
								if ( 'document' === type ) {
									$( 'body' ).append( '<div id="bp-media-create-folder" style="display: block;" class="open-popup group-document-error-popup"><transition name="modal"><div class="bb-rl-modal-mask bb-white bbm-model-wrap"><div class="bb-rl-modal-wrapper"><div id="bb-rl-media-create-album-popup" class="modal-container bb-rl-has-folderlocationUI"><header class="bb-model-header"><h4>' + bbRlMedia.invalid_file_type + '</h4><a class="bb-model-close-button errorPopup" href="#"><span class="bb-icon-l bb-icon-times"></span></a></header><div class="bb-rl-field-wrap"><p>' + response + '</p></div></div></div></div></transition></div>' );
								} else {
									$( 'body' ).append( '<div id="bp-media-create-folder" style="display: block;" class="open-popup forum-document-error-popup"><transition name="modal"><div class="bb-rl-modal-mask bb-white bbm-model-wrap"><div class="bb-rl-modal-wrapper"><div id="bb-rl-media-create-album-popup" class="modal-container bb-rl-has-folderlocationUI"><header class="bb-model-header"><h4>' + bbRlMedia.invalid_media_type + '</h4><a class="bb-model-close-button errorPopup" href="#"><span class="bb-icon-l bb-icon-times"></span></a></header><div class="bb-rl-field-wrap"><p>' + response + '</p></div></div></div></div></transition></div>' );
								}
							}
						} else {
							$( 'body' ).append( '<div id="bp-video-create-album" style="display: block;" class="open-popup"><transition name="modal"><div class="bb-rl-modal-mask bb-white bbm-model-wrap"><div class="bb-rl-modal-wrapper"><div id="bb-rl-video-create-album-popup" class="modal-container bb-rl-has-folderlocationUI"><header class="bb-model-header"><h4>' + bbRlVideo.invalid_video_type + '</h4><a class="bb-model-close-button errorPopup" href="#"><span class="bb-icon-l bb-icon-times"></span></a></header><div class="bb-rl-field-wrap"><p>' + response + '</p></div></div></div></div></transition></div>' );
						}
						this.removeFile( file );
					}
				}
			);

			if ( 'media' !== type ) {
				dropzoneObj.on(
					'accept',
					function ( file, done ) {
						if ( file.size === 0 ) {
							done( bbRlMedia[ 'empty_' + type + '_type' ] );
						} else {
							done();
						}
					}
				);
			}

			dropzoneObj.on(
				'success',
				function ( file, response ) {
					if ( 'video' === type && file.upload.progress === 100 ) {
						$( file.previewElement ).find( '.dz-progress-ring circle' )[ 0 ].style.strokeDashoffset = 0;
						$( file.previewElement ).find( '.dz-progress-count' ).text( '100% ' + bbRlVideo.i18n_strings.video_uploaded_text );
						$( file.previewElement ).closest( '.dz-preview' ).addClass( 'dz-complete' );
					}
					if ( response.data.id ) {
						file.id                  = response.id;
						response.data.uuid       = file.upload.uuid;
						response.data.menu_order = $( file.previewElement ).closest( '.dropzone' ).find( file.previewElement ).index() - 1;
						response.data.group_id   = self.group_id;
						response.data.saved      = false;

						if ( 'media' === type ) {
							response.data.album_id = self.album_id;
						} else if ( 'document' === type ) {
							response.data.folder_id = self.current_folder;
						} else if ( 'video' === type ) {
							response.data.album_id   = self.album_id;
							response.data.js_preview = $( file.previewElement ).find( '.dz-video-thumbnail img' ).attr( 'src' );
						}
						dropzoneDataObj.push( response.data );
						var groupMessagesMedia = $( '#bp_group_messages_' + type );
						if ( groupMessagesMedia.length ) {
							groupMessagesMedia.val( JSON.stringify( dropzoneDataObj ) );
						}
						if ( 'document' === type ) {
							var filename      = file.upload.filename,
								fileExtension = filename.substr(
									(
									filename.lastIndexOf( '.' ) + 1
									)
								),
								file_icon     = (
									! _.isUndefined( response.data.svg_icon ) ? response.data.svg_icon : ''
								),
								icon_class    = ! _.isEmpty( file_icon ) ? file_icon : 'bb-icons-rl-' + fileExtension;
							if ( $( file.previewElement ).find( '.dz-details .dz-icon .bb-icons-rl' ).length ) {
								$( file.previewElement ).find( '.dz-details .dz-icon .bb-icons-rl' ).removeClass( 'bb-icon-file' ).addClass( icon_class );
							}
						}
					} else {
						if ( 'media' === type ) {
							if ( ! jQuery( '.group-message-error-popup' ).length ) {
								$( 'body' ).append( '<div id="bp-media-create-folder" style="display: block;" class="open-popup group-message-error-popup"><transition name="modal"><div class="bb-rl-modal-mask bb-white bbm-model-wrap"><div class="bb-rl-modal-wrapper"><div id="bb-rl-media-create-album-popup" class="modal-container bb-rl-has-folderlocationUI"><header class="bb-model-header"><h4>' + bbRlMedia.invalid_media_type + '</h4><a class="bb-model-close-button errorPopup" href="#"><span class="bb-icon-l bb-icon-times"></span></a></header><div class="bb-rl-field-wrap"><p>' + response.data.feedback + '</p></div></div></div></div></transition></div>' );
							}
							this.removeFile( file );
						} else if ( 'document' === type || 'video' === type ) {
							var node, _i, _len, _ref, _results;
							var message = response.data.feedback;
							file.previewElement.classList.add( 'dz-error' );
							_ref     = file.previewElement.querySelectorAll( '[data-dz-errormessage]' );
							_results = [];
							for ( _i = 0, _len = _ref.length; _i < _len; _i++ ) {
								node = _ref[ _i ];
								_results.push( node.textContent = message );
							}
							return _results;
						}
					}
				}
			);

			dropzoneObj.on(
				'removedfile',
				function ( file ) {
					if ( dropzoneDataObj.length ) {
						for ( var i in dropzoneDataObj ) {
							if ( file.upload.uuid === dropzoneDataObj[ i ].uuid ) {
								if ( typeof dropzoneDataObj[ i ].saved !== 'undefined' && ! dropzoneDataObj[ i ].saved ) {
									self.removeAttachment( dropzoneDataObj[ i ].id );
								}
								dropzoneDataObj.splice( i, 1 );
								var groupMessagesMedia = $( '#bp_group_messages_' + type );
								if ( groupMessagesMedia.length ) {
									groupMessagesMedia.val( JSON.stringify( dropzoneDataObj ) );
								}
								break;
							}
						}
					}
					if ( ! _.isNull( dropzoneObj.files ) && dropzoneObj.files.length === 0 ) {
						var tool_box = $( '#send_group_message_form' );
						['media', 'document', 'video', 'gif'].forEach(
							function ( subType ) {
								var dropZoneButton = tool_box.find( '#bp-group-messages-' + subType + '-button' );
								if ( dropZoneButton ) {
										var buttonItems = dropZoneButton.parents( '.post-elements-buttons-item' );
									if ( type === subType ) {
										buttonItems.removeClass( 'no-click' );
									} else {
										buttonItems.removeClass( 'disable' );
									}
								}
							}
						);
					}
				}
			);
		},

		moveAttachments: function ( event, actionType, folderOrAlbum ) {

			var target = $( event.currentTarget );
			var self   = this;
			event.preventDefault();

			var itemId, destinationId, containerClass, selectedIdClass,
				updateActionType    = 'document_folder' === actionType ? 'document' : actionType,
				nameDocumentAsMedia = 'document' === actionType ? 'media' : actionType;

			if ( 'document_folder' === actionType ) {
				itemId        = target.attr( 'id' );
				destinationId = $( '#media-folder-document-data-table #bb-rl-media-move-folder .bb-rl-modal-mask .bb-rl-modal-wrapper #bb-rl-media-create-album-popup .bb-rl-field-wrap .bb-rl-folder-selected-id' ).val();
			} else {
				itemId          = target.attr( 'id' );
				containerClass  = 'document' === actionType ? '.location-folder-list' : '.location-album-list';
				selectedIdClass = 'document' === actionType ? '.bb-rl-folder-selected-id' : '.bb-rl-album-selected-id';
				destinationId   = target.closest( '.bb-rl-' + nameDocumentAsMedia + '-move-file' ).find( selectedIdClass ).val();
			}

			if ( '' === itemId || '' === destinationId ) {
				if ( 'document_folder' === actionType ) {
					alert( bbRlMedia.i18n_strings.folder_move_error );
				} else {
					target.closest( '.bb-rl-modal-container' ).find( containerClass ).addClass( 'has-error' );
				}
				return false;
			}

			if ( 'document' === actionType && 'yes' !== bbRlMedia.is_document_directory ) {
				this.current_page = 1;
			}

			if ( 'document_folder' !== actionType ) {
				target.closest( '.bb-rl-modal-container' ).find( containerClass ).removeClass( 'has-error' );
			}
			target.addClass( 'loading' );

			var attrSelector = 'video' === actionType ? 'li.bb_rl_move_video a[data-video-id="' + itemId + '"]' : 'a[data-media-id="' + itemId + '"]',
				activityId   = $( document ).find( attrSelector ).attr( 'data-parent-activity-id' ),
				groupId      = self.group_id;
			if ( 'media' === actionType && ! groupId ) {
				groupId = false;
				if ( 'group' === $( document ).find( attrSelector ).attr( 'data-type' ) ) {
					groupId = $( document ).find( attrSelector ).attr( 'id' );
				}
			}

			var data = {
				'action'      : actionType + '_move',
				'_wpnonce'    : bbRlNonce.media,
				'group_id'    : groupId,
				'activity_id' : activityId
			};

			// Add properties dynamically.
			if ( 'folder' === folderOrAlbum ) {
				if ( 'document' === actionType ) {
					data.document_id = itemId;
					data.folder_id   = destinationId;
				} else if ( 'document_folder' === actionType ) {
					data.current_folder_id = itemId;
					data.folder_move_to_id = destinationId;
				}
			} else {
				if ( 'media' === actionType ) {
					data.media_id = itemId;
				} else if ( 'video' === actionType ) {
					data.video_id = itemId;
					data._wpnonce = bbRlNonce.video;
				}
				data.album_id = destinationId;
			}

			$.ajax(
				{
					type: 'POST',
					url: bbRlAjaxUrl,
					data: data,
					success: function ( response ) {
						if ( response.success ) {
							var isDir = 'is_' + updateActionType + '_directory';
							if ( 'yes' === bbRlMedia[ isDir ] ) {
								var store = bp.Nouveau.getStorage( 'bp-' + updateActionType );
								var scope = store.scope, selector;
								if ( 'personal' === scope ) {
									selector = 'li#' + updateActionType + '-personal';
								} else {
									selector = 'li#' + updateActionType + '-all';
								}
								$( document ).find( selector ).trigger( 'click' );
								$( document ).find( selector ).trigger( 'click' );
							} else {
								if ( 'document_folder' !== actionType ) {
									var currentFolderAlbum, responseContent;
									if ( 'folder' === folderOrAlbum ) {
										currentFolderAlbum = bbRlMedia.current_folder;
										responseContent    = response.data.document_content;
									} else {
										if ( 'media' === actionType ) {
											currentFolderAlbum = bbRlMedia.current_album;
											responseContent    = response.data.media_content;
										} else if ( 'video' === actionType ) {
											currentFolderAlbum = bbRlVideo.current_album;
											responseContent    = response.data.video_content;
										}
									}
									if ( parseInt( currentFolderAlbum ) > 0 ) {
										$( '#' + updateActionType + '-stream ul.' + nameDocumentAsMedia + '-list li[data-id="' + itemId + '"]' ).remove();
										if ( 'video' === actionType ) {
											$( '#media-stream ul.media-list li[data-id="' + itemId + '"]' ).remove();
										}
									} else if ( $( '#bb-rl-activity-stream ul.bb-rl-bb-rl-activity-list li .bb-rl-activity-content .bb-rl-activity-inner .bb-activity-' + nameDocumentAsMedia + '-wrap div[data-id="' + destinationId + '"]' ).length && ! $( '#bb-rl-activity-stream ul.bb-rl-activity-list li .bb-rl-activity-content .activity-inner .bb-activity-' + nameDocumentAsMedia + '-wrap div[data-id="' + destinationId + '"]' ).parent().hasClass( 'bb-' + nameDocumentAsMedia + '-length-1' ) ) {
										$( '#bb-rl-activity-stream ul.bb-rl-activity-list li .bb-rl-activity-content .bb-rl-activity-inner .bb-activity-' + nameDocumentAsMedia + '-wrap div[data-id="' + destinationId + '"]' ).remove();
										if ( activityId && activityId.length ) {
											$( '#bb-rl-activity-stream ul.bb-rl-activity-list li[data-bp-activity-id="' + activityId + '"] .bb-rl-activity-content .bb-rl-activity-inner .bb-activity-' + nameDocumentAsMedia + '-wrap' ).remove();
											$( '#bb-rl-activity-stream ul.bb-rl-activity-list li[data-bp-activity-id="' + activityId + '"] .bb-rl-activity-content .bb-rl-activity-inner' ).append( responseContent );
											if ( 'video' === actionType ) {
												jQuery( window ).scroll();
											}
										}
									}
								}
								if ( 'document' === actionType || 'document_folder' === actionType ) {
									var documentStream = $( '#media-stream' );
									documentStream.html( '' );
									documentStream.html( response.data.html );
								}
								$( document ).find( '.open-popup .error' ).hide();
								$( document ).find( '.open-popup .error' ).html( '' );
								target.removeClass( 'loading' );
								$( document ).removeClass( 'open-popup' );
							}
							if ( 'document_folder' !== actionType ) {
								target.closest( '.bb-rl-' + nameDocumentAsMedia + '-move-file' ).find( '.bb-rl-ac-' + updateActionType + '-close-button' ).trigger( 'click' );
								if ( 'media' === actionType ) {
									$( document ).find( 'a.bb-rl-open-' + nameDocumentAsMedia + '-theatre[data-id="' + itemId + '"]' ).data( 'album-id', destinationId );
								}
							}
						} else {
							if ( 'document_folder' === actionType ) {
								$( document ).find( '.open-popup .error' ).show();
								$( document ).find( '.open-popup .error' ).html( response.data.feedback );
								target.removeClass( 'loading' );
								return false;
							} else {
								/* jshint ignore:start */
								alert( response.data.feedback.replace( '&#039;', '\'' ) );
								/* jshint ignore:end */
							}
						}
					}
				}
			);
		},

		editAlbumFolderTitle: function ( event, type ) {
			$( '#bb-album-title' ).show();
			$( '#bp-cancel-edit-album-title' ).show();
			$( '#bp-media-single-album #bp-single-album-title' ).hide();
			if ( 'document' === type ) {
				$( '#bp-save-folder-title' ).show();
				$( '#bp-edit-folder-title' ).hide();
			} else if ( 'media' === type ) {
				$( '#bp-save-album-title' ).show();
				$( '#bp-edit-album-title' ).hide();
			}
		},

		createAlbumFolderInPopup: function ( event, actionType, folderORAlbum ) {
			var $document         = $( document ),
				$openPopup        = $document.find( '.open-popup' ),
				getParentFolderId = parseInt( $openPopup.find( '.bb-rl-' + folderORAlbum + '-selected-id' ).val() ),
				getCreateIn       = $openPopup.find( '.bb-rl-' + folderORAlbum + '-create-from' ).val();
			if ( getParentFolderId > 0 ) {
				$openPopup.find( '.bb-rl-privacy-field-wrap-hide-show' ).hide();
			} else {
				$openPopup.find( '.bb-rl-privacy-field-wrap-hide-show' ).show();
			}

			if ( 'group' === getCreateIn ) {
				$document.find( '.bb-rl-popup-on-fly-create-' + folderORAlbum + ' .bb-rl-privacy-field-wrap-hide-show' ).hide();
			} else {
				if ( 'folder' === folderORAlbum && getParentFolderId > 0 ) {
					$document.find( '.bb-rl-popup-on-fly-create-' + folderORAlbum + ' .bb-rl-privacy-field-wrap-hide-show' ).hide();
				} else {
					$document.find( '.bb-rl-popup-on-fly-create-' + folderORAlbum + ' .bb-rl-privacy-field-wrap-hide-show' ).show();
				}
			}

			$( '.bb-rl-modal-container .bb-rl-model-footer, .modal-container .bb-model-footer, .bb-rl-modal-container #bp-media-document-prev' ).hide();
			$( '.bb-field-wrap-search' ).hide();
			var changedActionType = 'media' === actionType ? 'document' : actionType;
			$( '.bb-rl-' + changedActionType + '-open-create-popup-folder' ).hide();
			$( '.bb-rl-location-' + folderORAlbum + '-list-wrap-main' ).hide();
			if ( 'album' === folderORAlbum ) {
				$( '.bb-field-steps-2 #bp-media-prev' ).hide();
			}
			$( '.bb-rl-create-popup-' + folderORAlbum + '-wrap' ).show();
			var eventTarget     = $( event.currentTarget ),
				$folderLocation = eventTarget.closest( '.bb-rl-has-folderlocationUI' ),
				popupTitle      = 'folder' === folderORAlbum ? bbRlMedia.create_folder : bbRlMedia.create_album_title;
			$folderLocation.find( '.bb-rl-modal-header' ).children().hide();
			$folderLocation.find( '.bb-rl-modal-header' ).append( '<p>' + popupTitle + '</p>' );
			$( '.bb-rl-modal-container #bb-rl-folder-privacy' ).addClass( 'new-folder-create-privacy' );
			$document.find( '.open-popup .error' ).hide();
		},

		closeCreateFolderAlbumInPopup : function ( event, actionType ) {
			$( '.bb-rl-modal-container .bb-rl-model-footer, .modal-container .bb-model-footer' ).show();
			$( '.bb-field-wrap-search' ).show();
			$( '.bb-rl-document-open-create-popup-folder' ).show();
			if ( 'album' === actionType ) {
				$( '.bb-rl-modal-container:visible .bb-rl-video-open-create-popup-album' ).show();
			}
			$( '.bb-rl-location-' + actionType + '-list-wrap-main' ).show();
			if ( 'folder' === actionType ) {
				$( '#bp-media-document-prev' ).show();
			} else if ( 'album' === actionType ) {
				var $bbFieldSteps = $( '.bb-field-steps-2' );
				$bbFieldSteps.find( '#bp-media-prev' ).show();
				$bbFieldSteps.find( '#bp-video-next' ).show();
			}
			$( '.bb-rl-create-popup-' + actionType + '-wrap' ).hide();
			if ( 'album' === actionType ) {
				$( '.bb-rl-media-create-popup-album-submit.loading' ).removeClass( 'loading' );
			}
			var $folderLocationUi = $( event.currentTarget ).closest( '.bb-rl-has-folderlocationUI' );
			$folderLocationUi.find( '.bb-rl-modal-header' ).children().show();
			$folderLocationUi.find( '.bb-rl-modal-header p' ).hide();
		},

		submitCreateFolderAlbumInPopup : function ( event, actionType, folderOrAlbum ) {
			event.preventDefault();
			var self               = this,
				eventCurrentTarget = $( event.currentTarget ),
				targetPopup        = eventCurrentTarget.closest( '.open-popup' ),
				currentAction      = $( targetPopup ).find( '.bb-rl-' + actionType + '-create-popup-' + folderOrAlbum + '-submit' ),
				hiddenValue        = targetPopup.find( '.bb-rl-' + folderOrAlbum + '-selected-id' ).val(),
				titleSelector      = eventCurrentTarget.closest( '.modal-container, .bb-rl-modal-container' ).find( '.bb-rl-popup-on-fly-create-' + folderOrAlbum + '-title' ),
				title              = $.trim( titleSelector.val() );
			if ( 'document' === actionType ) {
				var pattern     = /[\\/?%*:|"<>]+/g; // regex to find not supported characters - \ / ? % * : | " < >
				var matches     = pattern.exec( title );
				var matchStatus = Boolean( matches );
				if ( title === '' || matchStatus ) {
					titleSelector.addClass( 'error' );
					return false;
				} else {
					titleSelector.removeClass( 'error' );
				}
			}

			if ( '' === hiddenValue ) {
				hiddenValue = 0;
			}
			this.currentTargetParent = hiddenValue;

			var currentAlbumFolder = this.currentTargetParent, groupId = 0, privacy = '', privacySelector = '',
				newParent          = 0;
			if ( 'group' === this.moveToTypePopup ) {
				privacy = 'grouponly';
				groupId = this.moveToIdPopup;
			} else {
				privacy         = eventCurrentTarget.closest( '.bb-rl-modal-container' ).find( '.bb-rl-popup-on-fly-create-' + folderOrAlbum + ' #bb-rl-' + folderOrAlbum + '-privacy' ).val();
				privacySelector = eventCurrentTarget.closest( '.bb-rl-modal-container' ).find( '.bb-rl-popup-on-fly-create-' + folderOrAlbum + ' #bb-rl-' + folderOrAlbum + '-privacy' );
			}

			if ( '' === title ) {
				var errorTitle = 'document' === actionType ? bbRlMedia.create_folder_error_title : bbRlMedia.create_album_error_title;
				alert( errorTitle );
				return false;
			}

			currentAction.addClass( 'loading' );

			var mediaActionForVideo = 'video' === actionType ? 'media' : actionType,
				ajaxAction          = mediaActionForVideo + '_' + folderOrAlbum + '_save',
				listClass           = 'document' === actionType ? '.location-' + folderOrAlbum + '-list' : '.location-' + folderOrAlbum + '-list';
			setTimeout(
				function () {
					var data = {
						'action'   : ajaxAction,
						'_wpnonce' : bbRlNonce.media,
						'title'    : title,
						'privacy'  : privacy,
						'parent'   : currentAlbumFolder,
						'group_id' : groupId
					};
					$.ajax(
						{
							type    : 'POST',
							url     : bbRlAjaxUrl,
							data    : data,
							async   : false,
							success : function ( response ) {
								if ( response.success ) {
									if ( 'document' === actionType && $( '.document-data-table-head' ).length ) {
										if ( parseInt( currentAlbumFolder ) === parseInt( bbRlMedia.current_folder ) ) {
											// Prepend the activity if no parent.
											bp.Nouveau.inject( '#media-stream div#media-folder-document-data-table', response.data.document, 'prepend' );
											jQuery( window ).scroll();
										}
									} else {
									}

									targetPopup.find( '.bb-rl-location-' + folderOrAlbum + '-list-wrap ' + listClass ).remove();
									targetPopup.find( '.bb-rl-location-' + folderOrAlbum + '-list-wrap' ).append( response.data.tree_view );
									var targetPopupID = 'document' === actionType ? '#' + $( targetPopup ).attr( 'id' ) : targetPopup,
									responseDataID    = 'document' === actionType ? response.data.folder_id : response.data.album_id;
									if ( bp.Nouveau.Media.folderLocationUI ) {
										bp.Nouveau.Media.folderLocationUI( targetPopupID, responseDataID );
									}
									newParent = responseDataID;

									if ( '' === response.data.tree_view ) {
										targetPopup.find( '.bb-rl-location-' + folderOrAlbum + '-list-wrap' ).hide();
										targetPopup.find( '.bb-rl-location-' + folderOrAlbum + '-list-wrap-main span.bb-rl-no-' + folderOrAlbum + '-exists' ).show();
									} else {
										targetPopup.find( '.bb-rl-location-' + folderOrAlbum + '-list-wrap-main span.bb-rl-no-' + folderOrAlbum + '-exists' ).hide();
										targetPopup.find( '.bb-rl-location-' + folderOrAlbum + '-list-wrap' ).show();
									}

									targetPopup.find( 'ul' + listClass + ' span#' + newParent ).trigger( 'click' );
									targetPopup.find( '.bb-rl-model-footer, .bb-model-footer' ).show();
									if ( 'document' === actionType ) {
										targetPopup.find( '.bb-rl-model-footer, .bb-model-footer, #bp-media-' + actionType + '-prev' ).show();
									}
									targetPopup.find( '.bb-field-wrap-search' ).show();
									targetPopup.find( '.bb-rl-' + actionType + '-open-create-popup-' + folderOrAlbum ).show();
									targetPopup.find( '.bb-rl-location-' + folderOrAlbum + '-list-wrap-main' ).show();
									targetPopup.find( '.bb-rl-create-popup-' + folderOrAlbum + '-wrap' ).hide();
									if ( 'media' === actionType ) {
										targetPopup.find( '.bb-field-steps-2 #bp-media-prev' ).show();
									}
									targetPopup.find( '.bb-rl-' + folderOrAlbum + '-selected-id' ).val();
									targetPopup.find( '.bb-rl' + folderOrAlbum + '-selected-id' ).val( newParent );
									targetPopup.find( '.bb-rl-modal-header' ).children().show();
									targetPopup.find( '.bb-rl-modal-header p' ).hide();
									titleSelector.val( '' );

									if ( '' !== privacySelector ) {
										privacySelector.val( 'public' );
									}

									$( targetPopup ).find( '.bb-rl-breadcrumbs-append-ul-li .breadcrumb .item span:not(.hidden)' ).each(
										function ( i ) {
											if ( i > 0 ) {
												if ( $( targetPopup ).find( '.bb-rl-breadcrumbs-append-ul-li .breadcrumb .item' ).width() > $( targetPopup ).find( '.bb-rl-breadcrumbs-append-ul-li .breadcrumb' ).width() ) {
													$( targetPopup ).find( '.bb-rl-breadcrumbs-append-ul-li .breadcrumb .item span.hidden' ).append( $( targetPopup ).find( '.bb-rl-breadcrumbs-append-ul-li .breadcrumb .item span' ).eq( 2 ) );
													if ( ! $( targetPopup ).find( '.bb-rl-breadcrumbs-append-ul-li .breadcrumb .item .more_options' ).length ) {
														$( '<span class="more_options">...</span>' ).insertAfter( $( targetPopup ).find( '.bb-rl-breadcrumbs-append-ul-li .breadcrumb .item span' ).eq( 0 ) );
													}
												}
											}
										}
									);

									if ( 'media' === actionType ) {
											self.updateAlbumNavCount( response.data.album_count );
									}
									currentAction.removeClass( 'loading' );
									if ( 'document' === actionType ) {
											setTimeout(
												function () {
													var currentSelectedFolder = targetPopup.find( 'ul' + listClass + ' span#' + newParent );
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
												},
												200
											);
									}
								} else {
									currentAction.removeClass( 'loading' );
								}
							}
						}
					);
					this.currentTargetParent = newParent;
					targetPopup.find( listClass + ' li.is_active' ).show().children( 'span, i' ).show().siblings( 'ul' ).hide();
					targetPopup.find( listClass + ' li.is_active' ).siblings( 'li' ).show().children( 'span, i' ).show().siblings( 'ul' ).hide();
					targetPopup.find( listClass + ' li span.selected' ).removeClass( 'selected' );
					targetPopup.find( listClass + ' li.is_active' ).children( 'span' ).addClass( 'selected' );
				},
				0
			);
		},

		setMediaSelectionState : function ( select ) {
			var $mediaList  = $( '#buddypress' ).find( '.media-list:not(.existing-media-list)' ),
				isSelecting = select === true,
				actions     = ['media'];

			if ( $mediaList.closest( '.album-single-view' ).length > 0 ) {
				actions.push( 'video' );
			}
			actions.forEach(
				function ( actionType ) {
					$mediaList.find( '.bb-' + actionType + '-check-wrap [name="bb-' + actionType + '-select"]' ).each(
						function () {
							var $this = $( this );
							$this.prop( 'checked', isSelecting );
							$this.closest( '.bb-item-thumb' ).toggleClass( 'selected', isSelecting );
							$this.closest( '.bb-' + actionType + '-check-wrap' ).find( '.bp-tooltip' ).attr( 'data-bp-tooltip', isSelecting ? BP_Nouveau.media.i18n_strings.unselect : BP_Nouveau.media.i18n_strings.select );
						}
					);
				}
			);
		},

		resetGroupMessagesAttachmentComponent: function ( args ) {
			var dropzoneObj = args.dropzoneObj;
			if ( dropzoneObj && typeof dropzoneObj.destroy === 'function' ) {
				dropzoneObj.destroy();
			}
			args.dropzoneData = [];
			var keySelector   = $( 'div#bp-group-messages-post-' + args.type + '-uploader' );
			keySelector.html( '' );
			keySelector.addClass( 'closed' ).removeClass( 'open' );
			$( '#bp-group-messages-' + args.type + '-button' ).removeClass( 'active' );
			$( '#item-body #group-messages-container .bb-groups-messages-right #send_group_message_form .bb-groups-messages-right-bottom #bp_group_messages_' + args.type ).val( '' );
		},

		resetForumsAttachmentComponent: function ( args ) {
			$( '#bb-rl-forums-' + args.type + '-button' ).removeClass( 'active' );
			var dropzoneObj       = args.dropzoneObj,
				dropzoneData      = args.dropzoneData,
				dropzoneContainer = args.dropzoneContainer;
			if ( typeof dropzoneContainer !== 'undefined' ) {
				if ( typeof dropzoneObj[ dropzoneContainer ] !== 'undefined' ) {
					dropzoneObj[ dropzoneContainer ].destroy();
					dropzoneObj.splice( dropzoneContainer, 1 );
					dropzoneData.splice( dropzoneContainer, 1 );
				}
				var keySelector = $( 'div#bb-rl-forums-post-' + args.type + '-uploader' );
				keySelector.html( '' );
				keySelector.addClass( 'closed' ).removeClass( 'open' );
			}
		},

		injectAttachments : function ( event, action ) {
			var store       = bp.Nouveau.getStorage( 'bp-' + action ),
				scope       = store.scope || null,
				filter      = store.filter || null,
				eventTarget = $( event.currentTarget );
			if ( eventTarget.hasClass( 'load-more' ) ) {
				var next_page    = Number( this.current_page ) + 1,
					self         = this,
					search_terms = '';

				// Stop event propagation.
				event.preventDefault();

				eventTarget.find( 'a' ).first().addClass( 'loading' );

				var searchElem = $( '#buddypress .dir-search input[type=search]' );
				if ( searchElem.length ) {
					search_terms = searchElem.val();
				}

				var queryData = {
					object       : action,
					scope        : scope,
					filter       : filter,
					search_terms : search_terms,
					page         : next_page,
					method       : 'append',
				};

				if ( 'document' === action ) {
					var sort, order_by;
					if ( this.order_by && this.sort_by ) {
						sort     = this.sort_by;
						order_by = this.order_by;
					} else if ( undefined !== store.extras ) {
						sort     = store.extras.sort;
						order_by = store.extras.orderby;
					} else {
						sort     = 'ASC';
						order_by = 'title';
					}

					queryData.target   = '#buddypress [data-bp-list] div#media-folder-document-data-table';
					queryData.order_by = order_by;
					queryData.sort     = sort;
				} else if ( 'media' === action || 'video' === action ) {
					queryData.target = '#buddypress [data-bp-list] ul.bp-list';
				}

				bp.Nouveau.objectRequest(
					queryData
				).done(
					function ( response ) {
						if ( true === response.success ) {
							if ( 'document' === action ) {
								eventTarget.parent( '.pager' ).remove();
							} else if ( 'media' === action || 'video' === action ) {
								eventTarget.remove();
							}
							// Update the current page.
							self.current_page = next_page;

							jQuery( window ).scroll();
						}
					}
				);
			}
		},

		deleteAttachment: function ( event, actionType, folderOrAlbum ) {
			if ( 'album' === folderOrAlbum && ! this.album_id ) {
				return false;
			}
			var confirmMessage = 'album' === folderOrAlbum ? bbRlMedia.i18n_strings.album_delete_confirm : bbRlMedia.i18n_strings.attachment_delete_confirm;
			if ( ! confirm( confirmMessage ) ) {
				return false;
			}
			$( event.currentTarget ).prop( 'disabled', true );
			var data = {
				'action'   : actionType + '_' + folderOrAlbum + '_delete',
				'_wpnonce' : bbRlNonce.media,
			};
			if ( 'album' === folderOrAlbum ) {
				data.album_id = this.album_id;
				data.group_id = this.group_id;
			} else if ( 'folder' === folderOrAlbum ) {
				data.folder_id = bbRlMedia.current_folder;
				data.group_id  = bbRlMedia.current_group_id;
			}
			$.ajax(
				{
					type: 'POST',
					url: BP_Nouveau.ajaxurl,
					data: data,
					success: function ( response ) {
						if ( response.success ) {
							window.location.href = response.data.redirect_url;
						} else {
							var errorMessage = 'album' === folderOrAlbum ? bbRlMedia.i18n_strings.album_delete_error : bbRlMedia.i18n_strings.folder_delete_error;
							alert( errorMessage );
							$( event.currentTarget ).prop( 'disabled', false );
						}
					}
				}
			);
		},

		saveItem: function ( event, actionType, folderOrAlbum, dropZoneData ) {
			var target        = $( event.currentTarget ),
				isAlbum       = 'album' === actionType,
				isFolder      = 'folder' === actionType,
				isChildFolder = 'child_folder' === actionType,
				$modal 		  = target.closest( '#bb-rl-media-edit-album' ),
				self          = this,
				title         = isChildFolder ? $( '#bp-media-create-child-folder #bb-album-child-title' ) : $( '#bb-album-title' ),
				nonce = BP_Nouveau.nonces.media,
				privacy;
			if ( isAlbum ) {
				privacy = $( '#bb-rl-album-privacy' );
				if ( 'video' === folderOrAlbum ) {
					nonce = BP_Nouveau.nonces.video;
				}
			} else if ( isFolder ) {
				privacy = target.parents().find( '.open-popup #bb-rl-folder-privacy option:selected' );
			}

			if ( isAlbum && target.hasClass( 'saving' ) ) {
				return false;
			}

			event.preventDefault();

			var pattern = /[\\/?%*:|"<>]+/g,
				matchStatus = ( isFolder || isChildFolder ) && pattern.test( title.val() );

			if ( $.trim( title.val() ) === '' || matchStatus ) {
				title.addClass( 'error' );
				return false;
			} else {
				title.removeClass( 'error' );
			}

			if ( isAlbum || isFolder ) {
				if ( ! self.group_id && $.trim( privacy.val() ) === '' ) {
					privacy.addClass( 'error' );
					return false;
				} else {
					privacy.removeClass( 'error' );
				}
			}

			target.prop( 'disabled', true ).toggleClass( isAlbum ? 'saving' : 'loading' );

			var data = {
				'action'   : folderOrAlbum + '_' + actionType + '_save',
				'_wpnonce' : nonce,
				'title'    : title.val()
			};
			if ( isAlbum ) {
				if ( 'media' === folderOrAlbum ) {
					data.medias = self.dropzone_media;
				} else if ( 'video' === folderOrAlbum ) {
					data.videos = dropZoneData;
				}
				data.privacy = privacy.val();
				if ( self.album_id ) {
					data.album_id = self.album_id;
				}
				if ( self.group_id ) {
					data.group_id = self.group_id;
				}
			} else if ( isFolder ) {
				data.privacy  = privacy.val();
				data.album_id = self.current_folder;
				data.group_id = self.current_group_id;
			} else if ( isChildFolder ) {
				data.folder_id = self.current_folder;
				data.group_id  = self.current_group_id;
			}
			$( '.bb-single-album-header .bp-feedback, #bb-rl-media-create-album-popup .bp-feedback' ).remove();

			$.ajax(
				{
					type    : 'POST',
					url     : BP_Nouveau.ajaxurl,
					data    : data,
					success : function ( response ) {
						if ( isAlbum || isFolder ) {
							setTimeout(
								function () {
									if ( isAlbum ) {
										target.removeClass( 'saving' );
									}
									target.prop( 'disabled', false );
								},
								500
							);
						}
						if ( response.success ) {
							if ( isAlbum ) {
								if ( self.album_id ) {
									$( '#bp-single-album-title .title-wrap' ).text( title.val() );
									$( '#bb-rl-album-privacy' ).val( privacy.val() );
									self.cancelEditAlbumTitle( event );
									$modal.find( '#bp-media-edit-album-close' ).trigger( 'click' );
								} else {
									$( '#buddypress .bb-albums-list' ).prepend( response.data.album );
									window.location.href = response.data.redirect_url;
								}
							} else if ( isFolder ) {
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
							} else if ( isChildFolder ) {
								self.closeChildFolderUploader( event );
								location.reload( true );
							}
						} else {
							if ( isAlbum ) {
								var feedbackTarget = self.album_id ? '#bp-media-single-album' : '#bb-rl-media-create-album-popup .bb-model-header';
								$( feedbackTarget ).prepend( response.data.feedback );
							} else {
								alert( response.data.feedback.replace( '&#039;', '\'' ) );
							}
						}
					}
				}
			);
		},

		disableButtonsInToolBox: function ( toolBox, selectors ) {
			selectors.forEach(
				function ( selector ) {
					var button = toolBox.find( selector );
					if ( button.length ) {
							button.parents( '.bb-rl-post-elements-buttons-item' ).addClass( 'disable' );
					}
				}
			);
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

			this.medias                 = [];
			this.documents              = [];
			this.current_media          = false;
			this.current_document       = false;
			this.current_index          = 0;
			this.current_document_index = 0;
			this.is_open_media          = false;
			this.is_open_document       = false;
			this.nextLink               = $( '.bb-rl-next-media' );
			this.nextDocumentLink       = $( '.bb-rl-next-document' );
			this.previousDocumentLink   = $( '.bb-rl-prev-document' );
			this.previousLink           = $( '.bb-rl-prev-media' );
			this.activity_ajax          = false;
			this.group_id               = typeof bbRlMedia.group_id !== 'undefined' ? bbRlMedia.group_id : false;
		},

		/**
		 * [addListeners description]
		 */
		addListeners: function () {
			var $document = $( document );
			$document.on( 'click', '.bb-rl-open-media-theatre', this.openTheatre.bind( this ) );
			$document.on( 'click', '.bb-rl-open-document-theatre', this.openDocumentTheatre.bind( this ) );
			$document.on( 'click', '.bb-rl-document-detail-wrap-description-popup', this.openDocumentTheatre.bind( this ) );
			$document.on( 'click', '.bb-rl-close-media-theatre', this.closeTheatre.bind( this ) );
			$document.on( 'click', '.bb-close-document-theatre', this.closeDocumentTheatre.bind( this ) );
			$document.on( 'click', '.bb-icons-rl-sidebar-simple', this.ToggleTheatreSidebar.bind( this ) );
			$document.on( 'click', '.bb-rl-prev-media', this.previous.bind( this ) );
			$document.on( 'click', '.bb-rl-next-media', this.next.bind( this ) );
			$document.on( 'click', '.bb-rl-prev-document', this.previousDocument.bind( this ) );
			$document.on( 'click', '.bb-rl-next-document', this.nextDocument.bind( this ) );
			$document.on( 'bp_activity_ajax_delete_request', this.activityDeleted.bind( this ) );
			$document.on( 'click', '#bb-rl-media-model-container .media-privacy>li', this.mediaPrivacyChange.bind( this ) );
			$document.on( 'click', '#bb-rl-media-model-container .document-privacy>li', this.documentPrivacyChange.bind( this ) );
			$document.on( 'click', '#bb-rl-media-model-container .bb-rl-media-section span.privacy', bp.Nouveau, this.togglePrivacyDropdown.bind( this ) );
			$document.on( 'click', '#bb-rl-media-model-container .bb-rl-document-section span.privacy', bp.Nouveau, this.toggleDocumentPrivacyDropdown.bind( this ) );
			$document.on( 'click', '.bp-add-media-activity-description', this.openMediaActivityDescription.bind( this ) );
			$document.on( 'click', '#bp-activity-description-new-reset', this.closeMediaActivityDescription.bind( this ) );
			$document.on( 'keyup', '.bp-edit-media-activity-description #add-activity-description', this.MediaActivityDescriptionUpdate.bind( this ) );
			$document.on( 'click', '#bp-activity-description-new-submit', this.submitMediaActivityDescription.bind( this ) );
			$document.on( 'click', '.bb-rl-media-thumb', this.handleThumbnailClick.bind( this ) );
			$document.click( this.togglePopupDropdown );

			document.addEventListener( 'keyup', this.checkPressedKeyDocuments.bind( this ) );
			document.addEventListener( 'keyup', this.checkPressedKey.bind( this ) );

		},

		checkPressedKey: function ( e ) {
			var self = this;
			e        = e || window.event;

			if ( ! self.is_open_media ) {
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
			e        = e || window.event;
			var self = this;

			if ( ! self.is_open_document ) {
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

		handleOpenTheatre: function ( data ) {
			var target            = data.target,
			    self              = data.self || this,
			    modalWrapperClass = data.modalWrapper,
			    mediaType         = data.mediaType,
			    action            = data.action;

			self.setupGlobals();
			self.setMedias( {
				target       : target,
				modalWrapper : modalWrapperClass,
				mediaType    : mediaType,
				action       : action
			} );

			var id = target.data( 'id' );
			self.setCurrentMedia( id );
			self.showMedia( {
				modalWrapper : modalWrapperClass,
				mediaType    : mediaType,
				action       : action
			} );
			self.navigationCommands();
			if ( self.current_activity_data ) {
				self.generateAndDisplayMediaThumbnails( {
					target       : target,
					modalWrapper : modalWrapperClass,
					mediaType    : mediaType,
					action       : action
				} );  // Generate thumbnails after setting up media.
			}
			self.getParentActivityHtml( {
				target       : target,
				modalWrapper : modalWrapperClass,
				mediaType    : mediaType,
				action       : action
			} );
			self.getMediasDescription(
				{
					modalWrapper : modalWrapperClass,
					mediaType    : mediaType,
					action       : action
				}
			);

			var modalTitle = target.closest( '.activity-item' ).data( 'activity-popup-title' );
			$( modalWrapperClass + ' .bb-rl-media-model-header h2' ).text( modalTitle );

			$( modalWrapperClass + '.document' ).hide();
			var currentVideo = document.getElementById( $( modalWrapperClass + '.video video' ).attr( 'id' ) );
			if ( currentVideo ) {
				currentVideo.pause();
			}
			if ( $( modalWrapperClass + '.video' ).length ) {
				$( modalWrapperClass + '.video' ).hide();
			}
			if ( $( modalWrapperClass + '.' + action ).length ) {
				$( modalWrapperClass + '.' + action ).show();
			}
			self.is_open_media = true;
		},

		openTheatre: function ( event ) {
			event.preventDefault();
			var target                 = $( event.currentTarget ), self = this;
			// Store activity data to use for media thumbnail.
			this.current_activity_data = target.closest( '.activity-item' ).data( 'bp-activity' );

			if ( target.closest( '#bp-existing-media-content' ).length ) {
				return false;
			}

			self.handleOpenTheatre(
				{
					self         : self,
					target       : target,
					event        : event,
					modalWrapper : '.bb-rl-media-model-wrapper',
					action       : 'media'
				}
			);
		},

		getParentActivityHtml: function ( data ) {
			var target = data.target,
			    action = data.action;

			if ( 'comment' === target.data( 'privacy' ) ) {
				return false;
			}

			var hiddenParentIdElem       = $( '#hidden_parent_id' ),
			    parentActivityId         = hiddenParentIdElem.val(),
			    parentActivityIdForModel = target.closest( '.bb-rl-' + action + '-model-wrapper' ).find( '#bb-rl-' + action + '-model-container .bb-rl-activity-list li.activity-item' ).data( 'bp-activity-id' );
			if ( parseInt( parentActivityId ) === parseInt( parentActivityIdForModel ) ) {
				var mainParentActivityData = $( '#bb-rl-' + action + '-model-container [data-bp-activity-id="' + parentActivityId + '"]' );
				$( '[data-bp-activity-id="' + parentActivityId + '"] > .activity-state' ).html( $( mainParentActivityData ).find( '.activity-state' ).html() );
				$( '[data-bp-activity-id="' + parentActivityId + '"] > .activity-meta' ).html( $( mainParentActivityData ).find( '.activity-meta' ).html() );
				$( '[data-bp-activity-id="' + parentActivityId + '"] > .bb-rl-activity-comments' ).html( $( mainParentActivityData ).find( '.bb-rl-activity-comments' ).html() );
			}
			if ( hiddenParentIdElem.length ) {
				hiddenParentIdElem.remove();
			}
		},

		getMediasDescription: function ( data ) {
			var action = data.action;

			this.fetchMediaDescription( this.current_media, $( '.bb-rl-' + action + '-model-wrapper' ), data );
		},

		openDocumentTheatre: function ( event ) {
			event.preventDefault();
			var target = $( event.currentTarget ), id, self = this;

			if ( target.closest( '#bp-existing-document-content' ).length ) {
				return false;
			}

			if ( target.closest( '.document.bb-rl-document-theatre' ).length ) {
				self.closeDocumentTheatre( event );
			}

			id = target.data( 'id' );
			self.setupGlobals();
			self.setDocuments( target );
			self.setCurrentDocument( id );
			self.showDocument();
			self.navigationDocumentCommands();
			self.getParentActivityHtml( {
				target : target,
				action : 'media'
			} );
			self.getDocumentsDescription();

			var modalTitle = target.closest( '.activity-item' ).data( 'activity-popup-title' );
			$( '.bb-rl-media-model-wrapper .bb-rl-media-model-header h2' ).text( modalTitle );

			// Stop audio if it is playing before opening theater.
			if ( $.inArray( self.current_document.extension, bbRlDocument.mp3_preview_extension.split( ',' ) ) !== -1 ) {
				if ( $( event.currentTarget ).closest( '.bb-rl-activity-media-elem.bb-rl-document-activity' ).length && $( event.currentTarget ).closest( '.bb-rl-activity-media-elem.bb-rl-document-activity' ).find( '.bb-rl-document-audio-wrap' ).length ) {
					$( event.currentTarget ).closest( '.bb-rl-activity-media-elem.bb-rl-document-activity' ).find( '.bb-rl-document-audio-wrap audio' )[ 0 ].pause();
				}
			}

			$( '.bb-rl-media-model-wrapper.media' ).hide();
			$( '.bb-rl-media-model-wrapper.document' ).show();
			var currentVideo = document.getElementById( $( '.bb-rl-media-model-wrapper.video video' ).attr( 'id' ) );
			if ( currentVideo ) {
				currentVideo.pause();
				currentVideo.src = '';
			}
			$( '.bb-rl-media-model-wrapper.video' ).hide();
			self.is_open_document = true;
		},

		resetRemoveActivityCommentsData: function ( action ) {
			var self = this, activity_comments = false, activity_meta = false, activity_state = false, activity = false,
			    html                                                                                            = false, classes = false, form;
			if ( self.current_media.parent_activity_comments ) {
				activity          = $( '.bb-rl-' + action + '-model-wrapper.media [data-bp-activity-id="' + self.current_media.activity_id + '"]' );
				activity_comments = activity.find( '.bb-rl-activity-comments' );
				if ( activity_comments.length ) {
					form = activity_comments.find( '#ac-form-' + self.current_media.activity_id );
					this.purgeEditActivityForm( form );
					form.find( '#ac-input-' + self.current_media.activity_id ).html( '' );
					form.removeClass( 'has-content has-gif has-media' ).addClass( 'root' );
					activity_comments.append( form );
					form.find( '.post-elements-buttons-item.post-emoji' ).removeClass( 'active' ).empty( '' ); // Reset emojionearea.
					this.resetActivityMedia( self.current_media.activity_id );
					activity_comments.find( '.acomment-display' ).removeClass( 'display-focus' );
					activity_comments.find( '.comment-item' ).removeClass( 'comment-item-focus' );

					html    = activity_comments.html();
					classes = activity_comments.attr( 'class' );
					activity_comments.remove();
					activity_comments = $( '[data-bp-activity-id="' + self.current_media.activity_id + '"] .bb-rl-activity-comments' );
					if ( activity_comments.length ) {
						activity_comments.attr( 'class', classes );
						if ( activity_comments.find( '#ac-form-' + self.current_media.activity_id ).length === 0 ) {
							activity_comments.append( form );
							activity_comments.children( 'form' ).removeClass( 'events-initiated' ).addClass( 'not-initialized' );
						}
					}
				}
				activity_state = activity.find( '.activity-state' );
				if ( activity_state.length ) {
					html    = activity_state.html();
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
					html    = activity_meta.html();
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
				activity          = $( '.bb-rl-' + action + '-model-wrapper.document [data-bp-activity-id="' + self.current_document.activity_id + '"]' );
				activity_comments = activity.find( '.bb-rl-activity-comments' );

				if ( activity_comments.length ) {
					form = activity_comments.find( '#ac-form-' + self.current_document.activity_id );
					this.purgeEditActivityForm( form );
					form.find( '#ac-input-' + self.current_document.activity_id ).html( '' );
					form.removeClass( 'has-content has-gif has-media' ).addClass( 'root' );
					activity_comments.append( form );
					form.find( '.post-elements-buttons-item.post-emoji' ).removeClass( 'active' ).empty( '' ); // Reset emojionearea.
					this.resetActivityMedia( self.current_document.activity_id );
					activity_comments.find( '.acomment-display' ).removeClass( 'display-focus' );
					activity_comments.find( '.comment-item' ).removeClass( 'comment-item-focus' );

					html    = activity_comments.html();
					classes = activity_comments.attr( 'class' );
					activity_comments.remove();
					activity_comments = $( '[data-bp-activity-id="' + self.current_document.activity_id + '"] .bb-rl-activity-comments' );
					if ( activity_comments.length ) {
						activity_comments.attr( 'class', classes );
						if ( activity_comments.find( '#ac-form-' + self.current_document.activity_id ).length === 0 ) {
							activity_comments.append( form );
							activity_comments.children( 'form' ).removeClass( 'events-initiated' ).addClass( 'not-initialized' );
						}
						// Reset document text preview.
						activity_comments.find( '.bb-rl-document-text.loaded' ).removeClass( 'loaded' ).find( '.CodeMirror' ).remove();
						jQuery( window ).scroll();
					}

				}
				activity_state = activity.find( '.activity-state' );
				if ( activity_state.length ) {
					html    = activity_state.html();
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
					html    = activity_meta.html();
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

			// Report content popup.
			bp.Nouveau.reportPopUp();
			bp.Nouveau.reportActions();
		},

		closeTheatre: function ( event ) {
			event.preventDefault();
			var self = this, target = $( event.currentTarget ), action = '';
			if ( target.closest( '.bb-rl-internal-model' ).hasClass( 'media-video' ) ) {
				action = 'media-video';
			} else if ( target.closest( '.bb-rl-internal-model' ).hasClass( 'media' ) ) {
				action = 'media';
			} else if ( target.closest( '.bb-rl-internal-model' ).hasClass( 'video' ) ) {
				action = 'video';
			}

			if ( $( target ).closest( '.bb-rl-' + action + '-model-wrapper' ).hasClass( 'bb-rl-video-theatre' ) ) {
				return false;
			}

			var $mediaSection = $( '.bb-rl-' + action + '-model-wrapper.media .bb-rl-media-section' );
			$mediaSection.find( 'img' ).attr( 'src', '' );
			var $videoSection = $mediaSection.find( 'video' );
			if ( $videoSection.length ) {
				videojs( $videoSection.attr( 'id' ) ).reset();
			}
			$( '.bb-rl-' + action + '-model-wrapper' ).hide();
			self.is_open_media = false;

			self.resetRemoveActivityCommentsData( action );

			self.current_media = false;
			self.getParentActivityHtml( {
				target : target,
				action : action
			} );
		},

		closeDocumentTheatre: function ( event ) {
			event.preventDefault();
			var self              = this;
			var document_elements = $( document ).find( '.bb-rl-document-theatre' );
			document_elements.find( '.bb-rl-media-section' ).removeClass( 'bb-media-no-preview' ).find( '.document-preview' ).html( '' );
			$( '.bb-media-info-section.document' ).show();
			document_elements.hide();
			self.is_open_document = false;

			self.resetRemoveActivityCommentsData( 'media' );

			self.current_document = false;
			self.getParentActivityHtml( {
				target : $(event.currentTarget),
				action : 'media'
			} );
		},

		ToggleTheatreSidebar: function ( event ) {
			event.preventDefault();
			var $this = $( event.currentTarget );
			$this.closest( '.bb-rl-media-model-container' ).toggleClass( 'bb-rl-media-toggle-sidebar' );
		},

		setMedias: function ( data ) {
			var target = data.target,
			    action = data.action;

			var media_elements = $( target ), i = 0, self = this;

			if ( 'media-video' === action ) {
				media_elements = $( '.bb-rl-open-' + action + '-theatre' );
			}

			// check if on activity page, load only activity media in theatre.
			if ( $( 'body' ).hasClass( 'activity' ) ) {
				media_elements = $( target ).closest( '.bb-activity-media-wrap' ).find( '.bb-rl-open-' + action + '-theatre' );
			}

			if ( typeof media_elements !== 'undefined' ) {
				self.medias         = [];
				var mediaElemLength = media_elements.length;
				for ( i = 0; i < mediaElemLength; i++ ) {
					var media_element = $( media_elements[ i ] );
					if ( ! media_element.closest( '#bp-existing-media-content' ).length ) {

						var m = {
							id                 : media_element.data( 'id' ),
							attachment         : media_element.data( 'attachment-full' ),
							activity_id        : media_element.data( 'activity-id' ),
							attachment_id      : media_element.data( 'attachment-id' ),
							privacy            : media_element.data( 'privacy' ),
							parent_activity_id : media_element.data( 'parent-activity-id' ),
							album_id           : media_element.data( 'album-id' ),
							group_id           : media_element.data( 'group-id' ),
							can_edit           : media_element.data( 'can-edit' ),
							is_forum           : false,
							type               : media_element.data( 'type' ) || 'media'
						};

						if ( media_element.closest( '.forums-media-wrap' ).length ) {
							m.is_forum = true;
						}

						m.is_message = typeof m.privacy !== 'undefined' && m.privacy === 'message';

						self.medias.push( m );
					}
				}
			}
		},

		setDocuments: function ( target ) {
			var document_elements = $( '.bb-rl-open-document-theatre' ), d = 0, self = this;

			// check if on activity page, load only activity media in theatre.
			if ( $( target ).closest( '.bp-search-ac-header' ).length ) {
				document_elements = $( target ).closest( '.bp-search-ac-header' ).find( '.bb-rl-open-document-theatre' );
			} else if ( $( 'body' ).hasClass( 'activity' ) && $( target ).closest( '.search-document-list' ).length === 0 ) {
				document_elements = $( target ).closest( '.bb-activity-media-wrap' ).find( '.bb-rl-open-document-theatre' );
			}

			if ( typeof document_elements !== 'undefined' ) {
				self.documents          = [];
				var documentsElemLength = document_elements.length;
				for ( d = 0; d < documentsElemLength; d++ ) {
					var document_element = $( document_elements[ d ] );
					if ( ! document_elements.closest( '#bp-existing-document-content' ).length ) {
						var a = {
							id                : document_element.data( 'id' ),
							attachment        : document_element.data( 'attachment-full' ),
							activity_id       : document_element.data( 'activity-id' ),
							attachment_id     : document_element.data( 'attachment-id' ),
							privacy           : document_element.data( 'privacy' ),
							parent_activity_id: document_element.data( 'parent-activity-id' ),
							album_id          : document_element.data( 'album-id' ),
							group_id          : document_element.data( 'group-id' ),
							extension         : document_element.data( 'extension' ),
							target_text       : document_element.data( 'document-title' ),
							preview           : document_element.data( 'preview' ),
							full_preview      : document_element.data( 'full-preview' ),
							text_preview      : document_element.data( 'text-preview' ),
							mirror_text       : document_element.data( 'mirror-text' ),
							target_icon_class : document_element.data( 'icon-class' ),
							author            : document_element.data( 'author' ),
							download          : document_element.attr( 'href' ),
							mp3               : document_element.data( 'mp3-preview' ),
							can_edit          : document_element.data( 'can-edit' ),
							video             : document_element.attr( 'data-video-preview' ),
							is_forum          : false
						};

						if ( document_element.closest( '.forums-media-wrap' ).length ) {
							a.is_forum = true;
						}

						a.is_message = typeof a.privacy !== 'undefined' && a.privacy === 'message';

						self.documents.push( a );
					}
				}
			}
		},

		setCurrentMedia: function ( id ) {
			var self = this, i, mediaLength = self.medias.length;
			for ( i = 0; i < mediaLength; i++ ) {
				if ( id === self.medias[ i ].id ) {
					self.current_media = self.medias[ i ];
					self.current_index = i;
					break;
				}
			}
		},

		setCurrentDocument: function ( id ) {
			var self = this, d, documentsLength = self.documents.length;
			for ( d = 0; d < documentsLength; d++ ) {
				if ( id === self.documents[ d ].id ) {
					self.current_document       = self.documents[ d ];
					self.current_document_index = d;
					break;
				}
			}
		},

		showMedia: function ( data ) {
			var self         = this,
			    modalWrapper = data.modalWrapper,
			    mediaType    = data.mediaType,
			    action       = data.action;

			if ( typeof self.current_media === 'undefined' ) {
				return false;
			}

			if ( 'media-video' === action ) {
				var $mediaSectionFigure = $( modalWrapper + ' .bb-rl-media-section figure' );
				if ( 'video' === mediaType ) {
					$( modalWrapper + ' .bb-rl-media-section' ).find( 'figure' ).addClass( 'loading' ).html( '<i class="bb-rl-loader"></i>' );
				} else {
					if ( ! $( modalWrapper + ' .bb-rl-media-section' ).children( 'img' ).length ) {
						$mediaSectionFigure.html( '<img src="' + self.current_media.attachment + '" alt="" />' );
					} else {
						$mediaSectionFigure.find( 'img' ).attr( 'src', self.current_media.attachment );
					}
				}
			} else {
				// refresh img.
				$( modalWrapper + ' .bb-rl-media-section' ).find( 'img' ).attr( 'src', self.current_media.attachment );
			}

			// privacy.
			var media_privacy_wrap = $( '.bb-rl-media-section .bb-media-privacy-wrap' );

			if ( media_privacy_wrap.length ) {
				media_privacy_wrap.show();
				media_privacy_wrap.find( 'ul.media-privacy li' ).removeClass( 'selected' );
				media_privacy_wrap.find( '.bp-tooltip' ).attr( 'data-bp-tooltip', '' );
				var selected_media_privacy_elem = media_privacy_wrap.find( 'ul.media-privacy' ).find( 'li[data-value=' + self.current_media.privacy + ']' );
				selected_media_privacy_elem.addClass( 'selected' );
				media_privacy_wrap.find( '.bp-tooltip' ).attr( 'data-bp-tooltip', selected_media_privacy_elem.text() );
				media_privacy_wrap.find( '.privacy' ).removeClass( 'public' ).removeClass( 'loggedin' ).removeClass( 'onlyme' ).removeClass( 'friends' ).addClass( self.current_media.privacy );

				// hide the privacy setting of media if activity is present.
				if ( (
					     typeof bbRlActivity !== 'undefined' &&
					     typeof self.current_media.activity_id !== 'undefined' &&
					     self.current_media.activity_id !== 0
				     ) ||
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
			var target_text         = self.current_document.target_text;
			var target_icon_class   = self.current_document.target_icon_class;
			var document_elements   = $( document ).find( '.bb-rl-document-theatre' );
			var extension           = self.current_document.extension;
			var mirror_text_display = self.current_document.mirror_text;
			document_elements.find( '.bb-rl-document-section' ).removeClass( 'bb-video-preview' );

			if ( $.inArray( self.current_document.extension, [ 'css', 'txt', 'js', 'html', 'htm', 'csv' ] ) !== -1 ) {
				document_elements.find( '.bb-rl-document-section .document-preview' ).html( '<i class="bb-icon-l bb-icon-spinner animate-spin"></i>' );
				document_elements.find( '.bb-rl-document-section' ).removeClass( 'bb-media-no-preview' );
				document_elements.find( '.bb-rl-document-section .document-preview' ).html( '' );
				document_elements.find( '.bb-rl-document-section .document-preview' ).html( '<h3>' + target_text + '</h3><div class="bb-rl-document-text"><textarea class="bb-rl-document-text-file-data-hidden"></textarea></div>' );
				document_elements.find( '.bb-rl-document-section .document-preview .bb-rl-document-text' ).attr( 'data-extension', extension );

				var $textarea = document_elements.find( '.bb-rl-document-section .document-preview .bb-rl-document-text textarea' );

				// Special handling for HTML files
				if ( 'html' === extension || 'htm' === extension ) {
					$textarea.val( mirror_text_display );
				} else {
					// Default behavior for other file types.
					$textarea.html( mirror_text_display );
				}
				setTimeout(
					function () {
						bp.Nouveau.Media.documentCodeMirror();
					},
					1000
				);
			} else if ( $.inArray( self.current_document.extension, bbRlDocument.mp3_preview_extension.split( ',' ) ) !== -1 ) {
				document_elements.find( '.bb-rl-document-section .document-preview' ).html( '<i class="bb-icon-l bb-icon-spinner animate-spin"></i>' );
				document_elements.find( '.bb-rl-document-section' ).removeClass( 'bb-media-no-preview' );
				document_elements.find( '.bb-rl-document-section .document-preview' ).html( '' );
				document_elements.find( '.bb-rl-document-section .document-preview' ).html( '<div class="img-section"><h3>' + target_text + '</h3><div class="document-audio"><audio src="' + self.current_document.mp3 + '" controls controlsList="nodownload"></audio></div></div>' );
			} else if ( $.inArray( '.' + self.current_document.extension, bbRlVideo.video_type.split( ',' ) ) !== -1 ) {
				document_elements.find( '.bb-rl-document-section' ).addClass( 'bb-video-preview' );
				document_elements.find( '.bb-rl-document-section .document-preview' ).html( '<i class="bb-icon-l bb-icon-spinner animate-spin"></i>' );
				document_elements.find( '.bb-rl-document-section' ).removeClass( 'bb-media-no-preview' );
				document_elements.find( '.bb-rl-document-section .document-preview' ).html( '' );
				if ( 'mov' === self.current_document.extension || 'm4v' === self.current_document.extension ) {
					document_elements.find( '.bb-rl-document-section .document-preview' ).html( '<video playsinline id="video-' + self.current_document.id + '" class="video-js video-loading" controls  data-setup=\'{"aspectRatio": "16:9", "fluid": true,"playbackRates": [0.5, 1, 1.5, 2] }\' ><source src="' + self.current_document.video + '" type="video/mp4" ></video><span class="video-loader"><i class="bb-icon-l bb-icon-spinner animate-spin"></i></span>' );
				} else {
					document_elements.find( '.bb-rl-document-section .document-preview' ).html( '<video playsinline id="video-' + self.current_document.id + '" class="video-js video-loading" controls  data-setup=\'{"aspectRatio": "16:9", "fluid": true,"playbackRates": [0.5, 1, 1.5, 2] }\' ><source src="' + self.current_document.video + '" type="video/' + self.current_document.extension + '" ></video><span class="video-loader"><i class="bb-icon-l bb-icon-spinner animate-spin"></i></span>' );
				}

				// fake scroll event to call video bp.Nouveau.Video.Player.openPlayer();.
				$( window ).scroll();

			} else {
				if ( self.current_document.full_preview ) {
					document_elements.find( '.bb-rl-document-section' ).removeClass( 'bb-media-no-preview' );
					document_elements.find( '.bb-rl-document-section .document-preview' ).html( '' );
					document_elements.find( '.bb-rl-document-section .document-preview' ).html( '<h3>' + target_text + '</h3><div class="img-section"><div class="img-block-wrap"> <img src="' + self.current_document.full_preview + '" alt=""/></div></div>' );
				} else {
					document_elements.find( '.bb-rl-document-section' ).addClass( 'bb-media-no-preview' );
					document_elements.find( '.bb-rl-document-section .document-preview' ).html( '' );
					document_elements.find( '.bb-rl-document-section .document-preview' ).html( '<div class="img-section"> <i class="' + target_icon_class + '"></i><p>' + target_text + '</p></div>' );
				}
			}

			// privacy.
			var document_privacy_wrap = $( '.bb-rl-document-section .bb-document-privacy-wrap' );

			if ( document_privacy_wrap.length ) {
				document_privacy_wrap.show();
				document_privacy_wrap.parent().show();
				document_privacy_wrap.find( 'ul.document-privacy li' ).removeClass( 'selected' );
				document_privacy_wrap.find( '.bp-tooltip' ).attr( 'data-bp-tooltip', '' );
				var selected_document_privacy_elem = document_privacy_wrap.find( 'ul.document-privacy' ).find( 'li[data-value=' + self.current_document.privacy + ']' );
				selected_document_privacy_elem.addClass( 'selected' );
				document_privacy_wrap.find( '.bp-tooltip' ).attr( 'data-bp-tooltip', selected_document_privacy_elem.text() );
				document_privacy_wrap.find( '.privacy' ).removeClass( 'public' ).removeClass( 'loggedin' ).removeClass( 'onlyme' ).removeClass( 'friends' ).addClass( self.current_document.privacy );

				// hide privacy setting of media if activity is present.
				if ( ( typeof bbRlActivity !== 'undefined' &&
						typeof self.current_document.activity_id !== 'undefined' &&
						self.current_document.activity_id !== 0 ) ||
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

		generateAndDisplayMediaThumbnails: function ( data ) {
			var target = data.target,
				action = data.action;

			var self = this;

			// Store activity data to use for media thumbnail.
			self.current_activity_data = target.closest( '.activity-item' ).data( 'bp-activity' );

			if (
				! self.current_activity_data ||
				! self.current_activity_data.media ||
				self.current_activity_data.media.length <= 1
			) {
				return;
			}

			var thumbnailsHtml = self.current_activity_data.media.map( function ( media ) {
				return '<div class="bb-rl-media-thumb' + ( media.id === self.current_media.id ? ' active' : '' ) +
				       '" data-id="' + media.id +
				       '" data-attachment-id="' + media.attachment_id +
				       '" data-activity-id="' + self.current_media.activity_id + '">' +
				       '<img src="' + media.thumb +
				       '" alt="' + ( media.name || '' ) +
				       '" data-full-img="' + media.url + '"/>' +
				       '</div>';
			} ).join( '' );

			// Add or update thumbnail container.
			var $mediaSection = $( '.' + action + '.bb-rl-' + action + '-theatre .bb-rl-media-section' ),
			    $thumbnails   = $mediaSection.find( '.bb-rl-media-thumbnails' );

			if ( ! $thumbnails.length ) {
				$mediaSection.append( '<div class="bb-rl-media-thumbnails">' + thumbnailsHtml + '</div>' );
			} else {
				$thumbnails.html( thumbnailsHtml );
			}
		},

		handleThumbnailClick: function ( event ) {
			var self = this, $clicked = $( event.currentTarget );

			if ( $clicked.hasClass( 'active' ) ) {
				return;
			}

			var $figure       = $( '.bb-rl-media-model-wrapper.media .bb-rl-media-section' ).find( 'figure' ),
			    $currentImage = $figure.find( 'img' );

			$currentImage.hide();
			$figure.addClass( 'loading' ).append( '<i class="bb-rl-loader"></i>' );

			var mediaId   = $clicked.data( 'id' ),
			    mediaData = self.medias.find( function ( media ) {
				    return media.id === mediaId;
			    } );

			if ( mediaData ) {
				var action = '';
				if ( $clicked.closest( '.bb-rl-internal-model' ).hasClass( 'media-video' ) ) {
					action = 'media-video';
				} else if ( $clicked.closest( '.bb-rl-internal-model' ).hasClass( 'media' ) ) {
					action = 'media';
				} else if ( $clicked.closest( '.bb-rl-internal-model' ).hasClass( 'video' ) ) {
					action = 'video';
				}
				var modalWrapper = '.bb-rl-' + action + '-model-wrapper',
				    data         = {
					    modalWrapper : modalWrapper, action : action
				    };
				self.updateMedia( mediaData, $clicked, data );
			}
		},

		next: function ( event ) {
			event.preventDefault();
			var target = $( event.currentTarget ),
			    action = '';
			if ( target.closest( '.bb-rl-internal-model' ).hasClass( 'media-video' ) ) {
				action = 'media-video';
			} else if ( target.closest( '.bb-rl-internal-model' ).hasClass( 'media' ) ) {
				action = 'media';
			} else if ( target.closest( '.bb-rl-internal-model' ).hasClass( 'video' ) ) {
				action = 'video';
			}
			var modalWrapper = '.bb-rl-' + action + '-model-wrapper',
			    data         = {
				    modalWrapper : modalWrapper,
				    action       : action
			    };
			var self         = this;
			if ( self.current_activity_data && self.medias[ self.current_index + 1 ] ) {
				self.updateMedia(
					self.medias[ self.current_index + 1 ],
					$( '.' + action + '.bb-rl-' + action + '-theatre .bb-rl-media-thumb' ).eq( self.current_index + 1 ),
					data
				);
			} else {
				self.resetRemoveActivityCommentsData( action );
				if ( typeof self.medias[ self.current_index + 1 ] !== 'undefined' ) {
					self.current_index = self.current_index + 1;
					self.current_media = self.medias[ self.current_index ];
					data.mediaType     = self.medias[ self.current_index ].type || '';
					self.showMedia( data );
					self.getMediasDescription( data );
				} else {
					self.nextLink.hide();
				}
			}
		},

		previous: function ( event ) {
			event.preventDefault();
			var target = $( event.currentTarget ),
			    action = '';
			if ( target.closest( '.bb-rl-internal-model' ).hasClass( 'media-video' ) ) {
				action = 'media-video';
			} else if ( target.closest( '.bb-rl-internal-model' ).hasClass( 'media' ) ) {
				action = 'media';
			} else if ( target.closest( '.bb-rl-internal-model' ).hasClass( 'video' ) ) {
				action = 'video';
			}
			var modalWrapper = '.bb-rl-' + action + '-model-wrapper',
			    data         = {
				    modalWrapper : modalWrapper,
				    action       : action
			    };
			var self         = this;

			if ( self.current_activity_data && self.medias[ self.current_index - 1 ] ) {
				self.updateMedia(
					self.medias[ self.current_index - 1 ],
					$( '.' + action + '.bb-rl-' + action + '-theatre .bb-rl-media-thumb' ).eq( self.current_index - 1 ),
					data
				);
			} else {
				self.resetRemoveActivityCommentsData( action );
				if ( typeof self.medias[ self.current_index - 1 ] !== 'undefined' ) {
					self.current_index = self.current_index - 1;
					self.current_media = self.medias[ self.current_index ];
					data.mediaType     = self.medias[ self.current_index ].type || '';
					self.showMedia( data );
					self.getMediasDescription( data );
				} else {
					self.previousLink.hide();
				}
			}
		},

		updateMedia: function ( mediaData, $thumbnail, data ) {
			var action   = data.action;
			var self     = this,
			    $theatre = $( '.' + action + '.bb-rl-' + action + '-theatre' ),
			    $figure  = $theatre.find( '.bb-rl-media-section figure' ),
			    $image   = $figure.find( 'img' );

			// Update image source and show when loaded.
			$image.one( 'load', function () {
				$figure.removeClass( 'loading' ).find( '.bb-rl-loader' ).remove();
				$image.show();
			} ).one( 'error', function () {
				$figure.removeClass( 'loading' ).find( '.bb-rl-loader' ).remove();
			} ).attr( 'src', mediaData.attachment );

			// Update thumbnail active state.
			$theatre.find( '.bb-rl-media-thumb' ).removeClass( 'active' );
			$thumbnail.addClass( 'active' );

			// Update current media and index.
			self.current_index = self.medias.findIndex( function ( media ) {
				return media.id === mediaData.id;
			} );
			self.current_media = mediaData;

			// Update navigation visibility.
			self.nextLink.toggle( Boolean( self.medias[ self.current_index + 1 ] ) );
			self.previousLink.toggle( Boolean( self.medias[ self.current_index - 1 ] ) );

			// Cancel existing AJAX request if any.
			if ( self.activity_ajax ) {
				self.activity_ajax.abort();
			}

			// Fetch new description using common function.
			self.fetchMediaDescription( mediaData, $theatre, data );
		},

		fetchMediaDescription: function ( mediaData, $container, args ) {
			var mediaType = args.mediaType,
			    action    = args.action;

			var self = this;

			// Abort any existing AJAX request.
			if ( self.activity_ajax !== false ) {
				self.activity_ajax.abort();
			}

			// Handle activity comments if present.
			var on_page_activity_comments = $( '[data-bp-activity-id="' + mediaData.activity_id + '"] .bb-rl-activity-comments' );
			if ( on_page_activity_comments.length ) {
				var form                           = on_page_activity_comments.find( '#ac-form-' + mediaData.activity_id );
				mediaData.parent_activity_comments = true;
				form.remove();
			}

			// Add hidden input for parent activity if needed.
			if ( mediaData.parent_activity_comments === true ) {
				$( '.bb-rl-' + action + '-model-wrapper:last' ).after( '<input type="hidden" value="' + mediaData.activity_id + '" id="hidden_parent_id"/>' );
			}

			var data = {
				id            : mediaData.id,
				attachment_id : mediaData.attachment_id,
				activity_id   : mediaData.activity_id,
			};
			if ( 'media-video' === action ) {
				if ( 'video' === mediaType ) {
					data.action = 'video_get_video_description';
					data.nonce  = bbRlNonce.video;
				} else {
					data.action = 'media_get_media_description';
					data.nonce  = bbRlNonce.media;
				}
			} else {
				data.action = 'media_get_media_description';
				data.nonce  = bbRlNonce.media;
			}

			// Make AJAX request.
			self.activity_ajax = $.ajax( {
				type       : 'POST',
				url        : bbRlAjaxUrl,
				data       : data,
				beforeSend : function () {
					$container.find( '.bb-media-info-section .bb-rl-activity-list' ).addClass( 'loading' ).html( '<i class="bb-rl-loader"></i>' );
				},
				success    : function ( response ) {
					if ( response.success ) {
						var $infoSection  = $container.find( '.bb-media-info-section:visible' ),
						    $mediaWrapper = $( '.bb-rl-' + action + '-model-wrapper' );
						if ( 'media-video' === action && 'video' === mediaType ) {
							var $mediaSection = $mediaWrapper.find( '.bb-rl-media-section figure' );
							$mediaSection.html( response.data.video_data );
							$mediaSection.find( 'video' ).attr( 'autoplay', true );
						}
						$infoSection.find( '.bb-rl-activity-list' ).removeClass( 'loading' ).html( response.data.description );

						if ( 'undefined' !== typeof response.data.comment_form && 'undefined' !== typeof bp.Nouveau.Activity ) {
							$infoSection.find( '.bb-rl-activity-list .bb-rl-activity-comments ul:first' ).after( response.data.comment_form );

							$infoSection.find( '#ac-form-' + mediaData.activity_id ).removeClass( 'not-initialized' ).addClass( 'root events-initiated' ).find( '#ac-input-' + mediaData.activity_id ).focus();
							var form = $infoSection.find( '#ac-form-' + mediaData.activity_id );
							bp.Nouveau.Activity.clearFeedbackNotice( form );
							form.removeClass( 'events-initiated' );
							var ce = $infoSection.find( '.ac-form .ac-input[contenteditable]' );
							bp.Nouveau.Activity.listenCommentInput( ce );
							if ( ! _.isUndefined( bbRlMedia ) && ! _.isUndefined( bbRlMedia.emoji ) ) {
								bp.Nouveau.Activity.initializeEmojioneArea( true, '#bb-rl-activity-modal ', mediaData.activity_id );
							}
						}
						$infoSection.show();

						$infoSection.find( '.bb-activity-more-options-action' ).attr( 'data-balloon-pos', 'left' );

						self.updateTheaterHeaderTitle(
							{
								wrapper : $mediaWrapper,
								action  : action
							}
						);

						// Batch UI updates in a single animation frame.
						requestAnimationFrame( function () {
							$( window ).scroll();
							bp.Nouveau.reportPopUp();
							bp.Nouveau.reportedPopup();
						} );
					} else {
						$container.find( '.bb-media-info-section.media' ).hide();
					}
				}
			} );
		},

		nextDocument: function ( event ) {
			event.preventDefault();

			var self = this;
			self.resetRemoveActivityCommentsData( 'media' );
			if ( typeof self.documents[ self.current_document_index + 1 ] !== 'undefined' ) {
				self.current_document_index = self.current_document_index + 1;
				self.current_document       = self.documents[ self.current_document_index ];
				self.showDocument();
				self.getDocumentsDescription();
			} else {
				self.nextDocumentLink.hide();
			}
		},

		previousDocument: function ( event ) {
			event.preventDefault();
			var self = this;
			self.resetRemoveActivityCommentsData( 'media' );
			if ( typeof self.documents[ self.current_document_index - 1 ] !== 'undefined' ) {
				self.current_document_index = self.current_document_index - 1;
				self.current_document       = self.documents[ self.current_document_index ];
				self.showDocument();
				self.getDocumentsDescription();
			} else {
				self.previousDocumentLink.hide();
			}
		},

		navigationCommands: function () {
			var self = this;
			if ( 0 === self.current_index && self.current_index !== ( self.medias.length - 1 ) ) {
				self.previousLink.hide();
				self.nextLink.show();
			} else if ( 0 === self.current_index && self.current_index === ( self.medias.length - 1 ) ) {
				self.previousLink.hide();
				self.nextLink.hide();
			} else if ( self.current_index === ( self.medias.length - 1 ) ) {
				self.previousLink.show();
				self.nextLink.hide();
			} else {
				self.previousLink.show();
				self.nextLink.show();
			}
		},

		navigationDocumentCommands: function () {
			var self = this;
			if ( 0 === self.current_document_index && self.current_document_index !== ( self.documents.length - 1 ) ) {
				self.previousDocumentLink.hide();
				self.nextDocumentLink.show();
			} else if ( 0 === self.current_document_index && self.current_document_index === ( self.documents.length - 1 ) ) {
				self.previousDocumentLink.hide();
				self.nextDocumentLink.hide();
			} else if ( self.current_document_index === ( self.documents.length - 1 ) ) {
				self.previousDocumentLink.show();
				self.nextDocumentLink.hide();
			} else {
				self.previousDocumentLink.show();
				self.nextDocumentLink.show();
			}
		},

		getDocumentsDescription: function () {
			var self = this;

			$( '.bb-media-info-section .bb-rl-activity-list' ).addClass( 'loading' ).html( '<i class="bb-icon-l bb-icon-spinner animate-spin"></i>' );

			if ( self.activity_ajax !== false ) {
				self.activity_ajax.abort();
			}

			var on_page_activity_comments = $( '[data-bp-activity-id="' + self.current_document.activity_id + '"] .bb-rl-activity-comments' );
			if ( on_page_activity_comments.length ) {
				var form                                       = on_page_activity_comments.find( '#ac-form-' + self.current_document.activity_id );
				self.current_document.parent_activity_comments = true;
				form.remove();
			}

			if ( true === self.current_document.parent_activity_comments ) {
				$( '.bb-rl-media-model-wrapper:last' ).after( '<input type="hidden" value="' + self.current_document.activity_id + '" id="hidden_parent_id"/>' );
			}

			self.activity_ajax = $.ajax(
				{
					type: 'POST',
					url: bbRlAjaxUrl,
					data: {
						action: 'document_get_document_description',
						id: self.current_document.id,
						attachment_id: self.current_document.attachment_id,
						nonce: bbRlNonce.media
					},
					success: function ( response ) {
						if ( response.success ) {
							$( '.bb-media-info-section:visible .bb-rl-activity-list' ).removeClass( 'loading' ).html( response.data.description );

							if ( 'undefined' !== typeof response.data.comment_form && 'undefined' !== typeof bp.Nouveau.Activity ) {
								$( '.bb-media-info-section:visible .bb-rl-activity-list .bb-rl-activity-comments ul:first' ).after( response.data.comment_form );

								$( '.bb-media-info-section:visible' ).find( '#ac-form-' + self.current_document.activity_id ).removeClass( 'not-initialized' ).addClass( 'root events-initiated' ).find( '#ac-input-' + self.current_document.activity_id ).focus();
								var form = $( '.bb-media-info-section:visible' ).find( '#ac-form-' + self.current_document.activity_id );
								bp.Nouveau.Activity.clearFeedbackNotice( form );
								form.removeClass( 'events-initiated' );
								var ce = $( '.bb-media-info-section:visible' ).find( '.ac-form .ac-input[contenteditable]' );
								bp.Nouveau.Activity.listenCommentInput( ce );
								if ( ! _.isUndefined( bbRlMedia ) && ! _.isUndefined( bbRlMedia.emoji ) ) {
									bp.Nouveau.Activity.initializeEmojioneArea( true, '#bb-rl-activity-modal ', self.current_document.activity_id );
								}
							}
							$( '.bb-media-info-section:visible' ).show();

							self.updateTheaterHeaderTitle(
								{
									wrapper : $( '.bb-rl-media-model-wrapper' ),
									action  : 'media'
								}
							);
							$( window ).scroll();
							setTimeout(
								function () {
									// Waiting to load dummy image.
									bp.Nouveau.reportPopUp();
									bp.Nouveau.reportedPopup();
								},
								10
							);

							$( '.bb-media-info-section:visible' ).find( '.bb-activity-more-options-action' ).attr( 'data-balloon-pos', 'left' );

						} else {
							$( '.bb-media-info-section.document' ).hide();
						}
					}
				}
			);
		},

		activityDeleted: function ( event, data ) {
			var self = this, i = 0;
			if ( self.is_open_media && typeof data !== 'undefined' && data.action === 'delete_activity' && self.current_media.activity_id === data.id ) {

				var $deleted_item             = $( document ).find( '[data-bp-list="media"] .bb-rl-open-media-theatre[data-id="' + self.current_media.id + '"]' );
				var $deleted_item_parent_list = $deleted_item.parents( 'ul' );

				$deleted_item.closest( 'li' ).remove();

				if ( 0 === $deleted_item_parent_list.find( 'li:not(.load-more)' ).length ) {

					// No item.
					var $photosActions = $( '.bb-photos-actions' );
					if ( $photosActions.length > 0 ) {
						$photosActions.hide();
					}

					if ( 1 === $deleted_item_parent_list.find( 'li.load-more' ).length ) {
						location.reload();
					}
				}
				$( document ).find( '[data-bp-list="activity"] .bb-rl-open-media-theatre[data-id="' + self.current_media.id + '"]' ).closest( '.bb-rl-activity-media-elem' ).remove();

				var mediasLength = self.medias.length;
				for ( i = 0; i < mediasLength; i++ ) {
					if ( self.medias[ i ].activity_id === data.id ) {
						self.medias.splice( i, 1 );
						break;
					}
				}

				if ( 0 === self.current_index && self.current_index !== ( self.medias.length ) ) {
					self.current_index = -1;
					self.next( event );
				} else if ( 0 === self.current_index && self.current_index === ( self.medias.length ) ) {
					$( document ).find( '[data-bp-list="activity"] li.activity-item[data-bp-activity-id="' + self.current_media.activity_id + '"]' ).remove();
					self.closeTheatre( event );
				} else if ( self.current_index === ( self.medias.length ) ) {
					self.previous( event );
				} else {
					self.current_index = -1;
					self.next( event );
				}
			}
			if ( self.is_open_document && typeof data !== 'undefined' && data.action === 'delete_activity' && self.current_document.activity_id === data.id ) {

				$( document ).find( '[data-bp-list="document"] .bb-rl-open-document-theatre[data-id="' + self.current_document.id + '"]' ).closest( 'div.ac-document-list[data-activity-id="' + self.current_document.activity_id + '"]' ).remove();
				$( document ).find( '[data-bp-list="activity"] .bb-rl-open-document-theatre[data-id="' + self.current_document.id + '"]' ).closest( '.bb-rl-activity-media-elem' ).remove();

				var documentsLength = self.documents.length;
				for ( i = 0; i < documentsLength; i++ ) {
					if ( self.documents[ i ].activity_id === data.id ) {
						self.documents.splice( i, 1 );
						break;
					}
				}

				if ( 0 === self.current_document_index && self.current_document_index !== ( self.documents.length ) ) {
					self.current_document_index = -1;
					self.nextDocument( event );
				} else if ( 0 === self.current_document_index && self.current_document_index === ( self.documents.length ) ) {
					$( document ).find( '[data-bp-list="activity"] li.activity-item[data-bp-activity-id="' + self.current_document.activity_id + '"]' ).remove();
					self.closeDocumentTheatre( event );
				} else if ( self.current_document_index === ( self.documents.length ) ) {
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
		 * @return {[type]} [description]
		 * @param event
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

			$( 'ul.media-privacy' ).removeClass( 'bb-open' );
			$( 'ul.document-privacy' ).removeClass( 'bb-open' );
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
					url: bbRlAjaxUrl,
					data: {
						action: 'media_update_privacy',
						id: self.current_media.id,
						_wpnonce: bbRlNonce.media,
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

		MediaActivityDescriptionUpdate: function ( event ) {
			var eventTarget = $( event.currentTarget );
			if ( eventTarget.val().trim() !== '' ) {
				eventTarget.closest( '.bp-edit-media-activity-description' ).addClass( 'has-content' );
			} else {
				eventTarget.closest( '.bp-edit-media-activity-description' ).removeClass( 'has-content' );
			}
		},

		submitMediaActivityDescription: function ( event ) {
			event.preventDefault();

			var target        = $( event.currentTarget ),
				parent_wrap   = target.parents( '.activity-media-description' ),
				description   = parent_wrap.find( '#add-activity-description' ).val(),
				attachment_id = parent_wrap.find( '#bp-attachment-id' ).val();

			var data = {
				'action': 'media_description_save',
				'description': description,
				'attachment_id': attachment_id,
				'_wpnonce': bbRlNonce.media,
			};

			$.ajax(
				{
					type: 'POST',
					url: bbRlAjaxUrl,
					data: data,
					async: false,
					success: function ( response ) {
						if ( response.success ) {
							target.parents( '.activity-media-description' ).find( '.bp-media-activity-description' ).html( response.data.description ).show();
							target.parents( '.activity-media-description' ).find( '.bp-add-media-activity-description' ).show();
							parent_wrap.find( '#add-activity-description' ).val( response.data.description );
							parent_wrap.find( '#add-activity-description' ).get( 0 ).defaultValue = response.data.description;
							if ( response.data.description === '' ) {
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
			var target = $( event.target );

			// Stop event propagation.
			event.preventDefault();

			target.closest( '.bb-document-privacy-wrap' ).find( '.document-privacy' ).toggleClass( 'bb-open' );
		},

		documentPrivacyChange: function (event) {
			var target = $( event.target ), self = this, privacy = target.data( 'value' ), older_privacy = 'public';

			event.preventDefault();

			if (target.hasClass( 'selected' )) {
				return false;
			}

			target.closest( '.bb-document-privacy-wrap' ).find( '.privacy' ).addClass( 'loading' );
			older_privacy = target.closest( '.bb-document-privacy-wrap' ).find( 'ul.document-privacy li.selected' ).data( 'value' );
			target.closest( '.bb-document-privacy-wrap' ).find( 'ul.document-privacy li' ).removeClass( 'selected' );
			target.addClass( 'selected' );

			$.ajax(
				{
					type   : 'POST',
					url    : bbRlAjaxUrl,
					data   : {
						action  : 'document_save_privacy',
						item_id : self.current_document.id,
						_wpnonce: bbRlNonce.media,
						value   : privacy,
						type    : 'document',
					},
					success: function () {
						target.closest( '.bb-document-privacy-wrap' ).find( '.privacy' ).removeClass( 'loading' ).removeClass( older_privacy );
						target.closest( '.bb-document-privacy-wrap' ).find( '.privacy' ).addClass( privacy );
						target.closest( '.bb-document-privacy-wrap' ).find( '.bp-tooltip' ).attr( 'data-bp-tooltip', target.text() );
					},
					error  : function () {
						target.closest( '.bb-document-privacy-wrap' ).find( '.privacy' ).removeClass( 'loading' );
					}
				}
			);
		},

		resetActivityMedia: function ( activityId ) {
			['media', 'document', 'video', 'gif'].forEach(
				function ( type ) {
					bp.Nouveau.Activity.destroyUploader( type, activityId );
				}
			);
		},

		purgeEditActivityForm: function ( form ) {

			if ( form.hasClass( 'acomment-edit' ) ) {
				var form_item_id       = form.attr( 'data-item-id' );
				var form_acomment      = $( '[data-bp-activity-comment-id="' + form_item_id + '"]' );
				var form_acomment_edit = form_acomment.find( '#acomment-edit-form-' + form_item_id );

				form_acomment.find( '#acomment-display-' + form_item_id ).removeClass( 'bp-hide' );
				form.removeClass( 'acomment-edit' ).removeAttr( 'data-item-id' );
				form_acomment_edit.empty();
			}

		},

		updateTheaterHeaderTitle : function ( data ) {
			var wrapper = data.wrapper, action = data.action;

			var activityHeaderElem = wrapper.find( '.activity-item' ), modalTitle = '';
			if ( activityHeaderElem.find( '.bb-rl-activity-header' ).length ) {
				// Extract username from the first link in the activity header.
				var usernameLink = activityHeaderElem.find( '.bb-rl-activity-header a' ).first();
				if ( usernameLink.length ) {
					modalTitle = usernameLink.text() + bbRlMedia.i18n_strings.theater_title;
					$( '.bb-rl-' + action + '-model-wrapper' + ' .bb-rl-media-model-header h2' ).text( modalTitle );
				}
			}
		}
	};

	// Launch BP Nouveau Media.
	bp.Nouveau.Media.start();

	// Launch BP Nouveau Media Theatre.
	bp.Nouveau.Media.Theatre.start();

} )( bp, jQuery );
