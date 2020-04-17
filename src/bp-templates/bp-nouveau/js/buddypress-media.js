/* jshint browser: true */
/* global bp, BP_Nouveau, JSON, Dropzone */
/* @version 1.0.0 */
window.bp = window.bp || {};

(function (exports, $) {

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

			var bodySelector = $( 'body' );

			// Init current page
			this.current_page                = 1;
			this.current_page_existing_media = 1;
			this.current_page_albums         = 1;
			this.current_tab                 = bodySelector.hasClass( 'single-topic' ) || bodySelector.hasClass( 'single-forum' ) ? false : 'bp-dropzone-content';

			// set up dropzones auto discover to false so it does not automatically set dropzones
			if ( typeof window.Dropzone !== 'undefined' ) {
				window.Dropzone.autoDiscover = false;
			}

			this.documentOptions = {
				url: BP_Nouveau.ajaxurl,
				timeout: 3 * 60 * 60 * 1000,
				acceptedFiles: BP_Nouveau.media.document_type,
				autoProcessQueue: true,
				addRemoveLinks: true,
				uploadMultiple: false,
				maxFilesize: typeof BP_Nouveau.media.max_upload_size !== 'undefined' ? BP_Nouveau.media.max_upload_size : 2
			};

			if ($( '#bp-media-uploader' ).hasClass( 'bp-media-document-uploader' )) {
				this.options = {
					url: BP_Nouveau.ajaxurl,
					timeout: 3 * 60 * 60 * 1000,
					acceptedFiles: BP_Nouveau.media.document_type,
					autoProcessQueue: true,
					addRemoveLinks: true,
					uploadMultiple: false,
					maxFilesize: typeof BP_Nouveau.media.max_upload_size !== 'undefined' ? BP_Nouveau.media.max_upload_size : 2
				};
			} else {
				this.options = {
					url: BP_Nouveau.ajaxurl,
					timeout: 3 * 60 * 60 * 1000,
					acceptedFiles: 'image/*',
					autoProcessQueue: true,
					addRemoveLinks: true,
					uploadMultiple: false,
					maxFilesize: typeof BP_Nouveau.media.max_upload_size !== 'undefined' ? BP_Nouveau.media.max_upload_size : 2
				};
			}

			//  if defined, add custom dropzone options
			if ( typeof BP_Nouveau.media.dropzone_options !== 'undefined' ) {
				Object.assign(this.options, BP_Nouveau.media.dropzone_options);
			}

			this.dropzone_obj            = [];
			this.dropzone_media          = [];
			this.album_id                = typeof BP_Nouveau.media.album_id !== 'undefined' ? BP_Nouveau.media.album_id : false;
			this.group_id                = typeof BP_Nouveau.media.group_id !== 'undefined' ? BP_Nouveau.media.group_id : false;
			this.bbp_is_reply_edit       = typeof window.BP_Forums_Nouveau !== 'undefined' && typeof window.BP_Forums_Nouveau.media.bbp_is_reply_edit !== 'undefined' && window.BP_Forums_Nouveau.media.bbp_is_reply_edit;
			this.bbp_is_topic_edit       = typeof window.BP_Forums_Nouveau !== 'undefined' && typeof window.BP_Forums_Nouveau.media.bbp_is_topic_edit !== 'undefined' && window.BP_Forums_Nouveau.media.bbp_is_topic_edit;
			this.bbp_is_forum_edit       = typeof window.BP_Forums_Nouveau !== 'undefined' && typeof window.BP_Forums_Nouveau.media.bbp_is_forum_edit !== 'undefined' && window.BP_Forums_Nouveau.media.bbp_is_forum_edit;
			this.bbp_reply_edit_media    = typeof window.BP_Forums_Nouveau !== 'undefined' && typeof window.BP_Forums_Nouveau.media.reply_edit_media !== 'undefined' ? window.BP_Forums_Nouveau.media.reply_edit_media : [];
			this.bbp_reply_edit_document = typeof window.BP_Forums_Nouveau !== 'undefined' && typeof window.BP_Forums_Nouveau.media.reply_edit_document !== 'undefined' ? window.BP_Forums_Nouveau.media.reply_edit_document : [];
			this.bbp_topic_edit_media    = typeof window.BP_Forums_Nouveau !== 'undefined' && typeof window.BP_Forums_Nouveau.media.topic_edit_media !== 'undefined' ? window.BP_Forums_Nouveau.media.topic_edit_media : [];
			this.bbp_topic_edit_document = typeof window.BP_Forums_Nouveau !== 'undefined' && typeof window.BP_Forums_Nouveau.media.topic_edit_document !== 'undefined' ? window.BP_Forums_Nouveau.media.topic_edit_document : [];
			this.bbp_forum_edit_media    = typeof window.BP_Forums_Nouveau !== 'undefined' && typeof window.BP_Forums_Nouveau.media.forum_edit_media !== 'undefined' ? window.BP_Forums_Nouveau.media.forum_edit_media : [];
			this.bbp_forum_edit_document = typeof window.BP_Forums_Nouveau !== 'undefined' && typeof window.BP_Forums_Nouveau.media.forum_edit_document !== 'undefined' ? window.BP_Forums_Nouveau.media.forum_edit_document : [];
			this.bbp_reply_edit_gif_data = typeof window.BP_Forums_Nouveau !== 'undefined' && typeof window.BP_Forums_Nouveau.media.reply_edit_gif_data !== 'undefined' ? window.BP_Forums_Nouveau.media.reply_edit_gif_data : [];
			this.bbp_topic_edit_gif_data = typeof window.BP_Forums_Nouveau !== 'undefined' && typeof window.BP_Forums_Nouveau.media.topic_edit_gif_data !== 'undefined' ? window.BP_Forums_Nouveau.media.topic_edit_gif_data : [];
			this.bbp_forum_edit_gif_data = typeof window.BP_Forums_Nouveau !== 'undefined' && typeof window.BP_Forums_Nouveau.media.forum_edit_gif_data !== 'undefined' ? window.BP_Forums_Nouveau.media.forum_edit_gif_data : [];

			this.giphy             = null;
			this.gif_offset        = 0;
			this.gif_q             = null;
			this.gif_limit         = 20;
			this.gif_requests      = [];
			this.gif_data          = [];
			this.gif_container_key = false;

			//Text File Activity Preview
			bp.Nouveau.Media.documentCodeMirror();

			$( window ).on( 'scroll resize',function(){
				bp.Nouveau.Media.documentCodeMirror();
			});
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
			// $( '#buddypress [data-bp-list="document"]' ).on('bp_ajax_request',this.bp_ajax_media_request);

			// albums
			bpNouveau.on( 'click', '#bb-create-album', this.openCreateAlbumModal.bind( this ) );
			bpNouveau.on( 'click', '#bb-create-folder', this.openCreateFolderModal.bind( this ) );
			bpNouveau.on( 'click', '#bb-create-folder-child', this.openCreateFolderChildModal.bind( this ) );
			bpNouveau.on( 'click', '#bp-edit-folder-open', this.openEditFolderChildModal.bind( this ) );

			bpNouveau.on( 'click', '#bp-media-create-album-submit', this.saveAlbum.bind( this ) );
			bpNouveau.on( 'click', '#bp-media-create-folder-submit', this.saveFolder.bind( this ) );
			bpNouveau.on( 'click', '#bp-media-create-child-folder-submit', this.saveChildFolder.bind( this ) );

			bpNouveau.on( 'click', '#bp-media-create-album-close', this.closeCreateAlbumModal.bind( this ) );
			bpNouveau.on( 'click', '#bp-media-create-folder-close', this.closeCreateFolderModal.bind( this ) );
			bpNouveau.on( 'click', '#bp-media-edit-folder-close', this.closeEditFolderModal.bind( this ) );

			bpNouveau.on( 'click', '#bp-media-add-more', this.triggerDropzoneSelectFileDialog.bind( this ) );
			bpNouveau.on( 'click', '#bp-media-document-add-more', this.triggerDropzoneSelectFileDialog.bind( this ) );

			$( '#bp-media-uploader' ).on( 'click', '.bp-media-upload-tab', this.changeUploadModalTab.bind( this ) );

			// Fetch Media
			$( '.bp-nouveau [data-bp-list="media"]' ).on( 'click', 'li.load-more', this.injectMedias.bind( this ) );
			$( '.bp-nouveau #albums-dir-list' ).on( 'click', 'li.load-more', this.appendAlbums.bind( this ) );
			mediaWrap.on( 'click', 'li.load-more', this.appendMedia.bind( this ) );
			bpNouveau.on( 'change', '.bb-media-check-wrap [name="bb-media-select"]', this.addSelectedClassToWrapper.bind( this ) );
			mediaWrap.on( 'change', '.bb-media-check-wrap [name="bb-media-select"]', this.toggleSubmitMediaButton.bind( this ) );

			// single album
			bpNouveau.on( 'click', '#bp-edit-album-title', this.editAlbumTitle.bind( this ) );
			bpNouveau.on( 'click', '#bp-edit-folder-title', this.editFolderTitle.bind( this ) );
			bpNouveau.on( 'click', '#bp-cancel-edit-album-title', this.cancelEditAlbumTitle.bind( this ) );
			bpNouveau.on( 'click', '#bp-save-album-title', this.saveAlbum.bind( this ) );
			bpNouveau.on( 'click', '#bp-save-folder-title', this.saveFolder.bind( this ) );
			bpNouveau.on( 'change', '#bp-media-single-album select#bb-album-privacy', this.saveAlbum.bind( this ) );
			bpNouveau.on( 'change', '#bp-media-single-folder select#bb-folder-privacy', this.saveFolder.bind( this ) );
			bpNouveau.on( 'click', '#bb-delete-album', this.deleteAlbum.bind( this ) );
			bpNouveau.on( 'click', '#bb-delete-folder', this.deleteFolder.bind( this ) );

			// forums
			$( document ).on( 'click', '#forums-media-button', this.openForumsUploader.bind( this ) );
			$( document ).on( 'click', '#forums-document-button', this.openForumsDocumentUploader.bind( this ) );
			$( document ).on( 'click', '#forums-gif-button', this.toggleGifSelector.bind( this ) );
			$( document ).find( 'form #whats-new-toolbar, .forum form #whats-new-toolbar' ).on( 'keyup', '.search-query-input', this.searchGif.bind( this ) );
			$( document ).find( 'form #whats-new-toolbar, .forum form #whats-new-toolbar' ).on( 'click', '.found-media-item', this.selectGif.bind( this ) );
			$( document ).find( 'form #whats-new-toolbar .gif-search-results, .forum form #whats-new-toolbar .gif-search-results' ).scroll( this.loadMoreGif.bind( this ) );
			if ( ! $( '.buddypress.groups.messages' ).length ) {
				$(document).find('form #whats-new-toolbar, .forum form #whats-new-toolbar').on('click', '.found-media-item', this.selectGif.bind(this));
			}
			$( document ).find( 'form #whats-new-attachments .forums-attached-gif-container .gif-search-results, .forum form #whats-new-attachments .forums-attached-gif-container .gif-search-results' ).scroll( this.loadMoreGif.bind( this ) );
			$( document ).find( 'form #whats-new-attachments .forums-attached-gif-container, .forum form #whats-new-attachments .forums-attached-gif-container' ).on( 'click', '.gif-image-remove', this.removeSelectedGif.bind( this ) );

			$( document ).on( 'click', '.gif-image-container', this.playVideo.bind( this ) );

			// Documents
			$( document ).on( 'click', '.directory.document  .media-folder_action__anchor, .directory.document  .media-folder_action__anchor li a, .bb-media-container .media-folder_action__anchor, .bb-media-container  .media-folder_action__list li a', this.fileActionButton.bind( this ) );
			$( document ).on( 'click', '.media-folder_action__list .copy_download_file_url a', this.copyDownloadLink.bind( this ) );
			$( document ).on( 'click', '.bb-activity-media-elem.document-activity .document-action-wrap .document-action_more, .bb-activity-media-elem.document-activity .document-action-wrap .document-action_list li a', this.fileActivityActionButton.bind( this ) );
			$( document ).click( this.toggleFileActivityActionButton );
			$( document ).on( 'click', '.bb-activity-media-elem.document-activity .document-expand .document-expand-anchor', this.expandCodePreview.bind( this ) );
			$( document ).on( 'click', '.bb-activity-media-elem.document-activity .document-action-wrap .document-action_collapse', this.collapseCodePreview.bind( this ) );
			$( document ).on( 'click', '.activity .bp-document-move-activity, #media-stream .bp-document-move-activity', this.moveDocumentIntoFolder.bind( this ) );
			$( document ).on( 'click', '.bp-nouveau [data-bp-list="document"] .pager .dt-more-container.load-more', this.injectDocuments.bind( this ) );
			$( document ).on( 'click', '.bp-nouveau [data-bp-list="document"] .data-head', this.sortDocuments.bind( this ) );
			$( document ).on( 'click', '.modal-container .bb-field-steps-actions', this.documentPopupNavigate.bind( this ) );
			$( document ).on( 'click', '.modal-container .bb-field-uploader-actions', this.uploadDocumentNavigate.bind( this ) );
			$( document ).on( 'click', '.modal-container #bp-media-edit-child-folder-submit', this.renameChildFolder.bind( this ) );
			//Document move option
			var mediaStream = $( '#bb-media-model-container .activity-list, #media-stream' );
			$( '#buddypress .activity-list, #buddypress [data-bp-list="activity"], #bb-media-model-container .activity-list, #media-stream' ).on( 'click', '.ac-document-move, .ac-folder-move', this.openDocumentMove.bind( this ) );
			$( '#buddypress .activity-list, #buddypress [data-bp-list="activity"], #bb-media-model-container .activity-list, #media-stream' ).on( 'click', '.ac-document-close-button, .ac-folder-close-button', this.closeDocumentMove.bind( this ) );
			mediaStream.on( 'click', '.ac-document-rename', this.renameDocument.bind( this ) );
			mediaStream.on( 'click', '.ac-document-privacy', this.editPrivacyDocument.bind( this ) );
			mediaStream.on( 'mouseup', '#bb-folder-privacy', this.editPrivacyDocumentSubmit.bind( this ) );
			mediaStream.on( 'keyup', '.media-folder_name_edit', this.renameDocumentSubmit.bind( this ) );
			mediaStream.on( 'click', '.name_edit_cancel, .name_edit_save', this.renameDocumentSubmit.bind( this ) );

			// document delete
			$( document ).on( 'click', '.document-file-delete', this.deleteDocument.bind( this ) );

			// Folder Move
			$( document ).on( 'click', '.bp-folder-move', this.folderMove.bind( this ) );

			// Group Messages
			var groupMessagesButtonSelector 		 = $( '.buddypress.groups.messages' );
			var groupMessagesToolbarSelector 		 = $( '.buddypress.groups.messages form#send_group_message_form #whats-new-toolbar' );
			var groupMessagesToolbarContainerResults = $( '.buddypress.groups.messages form#send_group_message_form #whats-new-attachments .bp-group-messages-attached-gif-container .gif-search-results' );
			var groupMessagesToolbarContainer 		 = $( '.buddypress.groups.messages form#send_group_message_form #whats-new-attachments .bp-group-messages-attached-gif-container' );

			groupMessagesButtonSelector.on( 'click', '#bp-group-messages-media-button', this.openGroupMessagesUploader.bind( this ) );
			groupMessagesButtonSelector.on( 'click', '#bp-group-messages-gif-button', this.toggleGroupMessagesGifSelector.bind( this ) );
			groupMessagesToolbarSelector.on( 'keyup', '.search-query-input', this.searchGroupMessagesGif.bind( this ) );
			groupMessagesToolbarSelector.on( 'click', '.found-media-item', this.selectGroupMessagesGif.bind( this ) );
			groupMessagesToolbarContainerResults.scroll( this.loadMoreGroupMessagesGif.bind( this ) );
			$( '.groups.messages form#send_group_message_form #whats-new-toolbar .bp-group-messages-attached-gif-container .gif-search-results' ).scroll( this.loadMoreGroupMessagesGif.bind( this ) );
			groupMessagesToolbarContainer.on( 'click', '.gif-image-remove', this.removeGroupMessagesSelectedGif.bind( this ) );


			$(document).on('click', '.gif-image-container', this.playVideo.bind( this ) );
			// Gifs autoplay
			if ( ! _.isUndefined( BP_Nouveau.media.gif_api_key )) {
				window.addEventListener( 'scroll', this.autoPlayGifVideos, false );
				window.addEventListener( 'resize', this.autoPlayGifVideos, false );
			}

			if ((this.bbp_is_reply_edit || this.bbp_is_topic_edit || this.bbp_is_forum_edit) &&
				(this.bbp_reply_edit_media.length || this.bbp_topic_edit_media.length || this.bbp_forum_edit_media.length)) {
				$( '#forums-media-button' ).trigger( 'click' );
			}

			if ((this.bbp_is_reply_edit || this.bbp_is_topic_edit || this.bbp_is_forum_edit) &&
				(this.bbp_reply_edit_document.length || this.bbp_topic_edit_document.length || this.bbp_forum_edit_document.length)) {
				$( '#forums-document-button' ).trigger( 'click' );
			}

			if ((this.bbp_is_reply_edit || this.bbp_is_topic_edit || this.bbp_is_forum_edit) &&
				(Object.keys( this.bbp_reply_edit_gif_data ).length || Object.keys( this.bbp_topic_edit_gif_data ).length || Object.keys( this.bbp_forum_edit_gif_data ).length)) {
				this.editGifPreview();
			}

		},

		folderMove: function( event ) {
			var target = $( event.currentTarget );
			event.preventDefault();

			var currentFolderId = target.attr( 'id' );
			var folderMoveToId  = $( '#media-folder-document-data-table #bp-media-move-folder .modal-mask .modal-wrapper #boss-media-create-album-popup .bb-field-wrap .bb-folder-selected-id' ).val();

			if ( '' === currentFolderId || '' === folderMoveToId ){
				alert( BP_Nouveau.media.i18n_strings.folder_move_error );
				return false;
			}

			var data = {
				'action': 'document_folder_move',
				'currentFolderId': currentFolderId,
				'folderMoveToId': folderMoveToId
			};

			$.ajax(
				{
					type: 'POST',
					url: BP_Nouveau.ajaxurl,
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
		},

		deleteDocument: function (event) {
			var target = $( event.currentTarget );
			event.preventDefault();

			var type 				  = target.attr( 'data-type' );
			var id 					  = target.attr( 'data-item-id' );
			var attachment_id 		  = target.attr( 'data-item-attachment-id' );
			var preview_attachment_id = target.attr( 'data-item-preview-attachment-id' );

			if ( 'folder' === type ) {
				if ( ! confirm( BP_Nouveau.media.i18n_strings.folder_delete_confirm )) {
					return false;
				}
			}

			var data = {
				'action': 'document_delete',
				'id': id,
				'preview_attachment_id': preview_attachment_id,
				'type': type,
				'attachment_id': attachment_id
			};

			$.ajax(
				{
					type: 'POST',
					url: BP_Nouveau.ajaxurl,
					data: data,
					success: function (response) {
						if (response.success) {
							var documentStream = $( '#media-stream' );
							documentStream.html( '' );
							documentStream.html( response.data.html );
						}
					}
				}
			);

		},

		bp_ajax_media_request: function (event, data) {
			if (typeof data !== 'undefined' && typeof data.response.scopes.personal !== 'undefined' && data.response.scopes.personal === 0) {
				$( '.bb-photos-actions' ).hide();
			}
		},

		addSelectedClassToWrapper: function (event) {
			var target = event.currentTarget;
			if ($( target ).is( ':checked' )) {
				$( target ).closest( '.bb-media-check-wrap' ).find( '.bp-tooltip' ).attr( 'data-bp-tooltip', BP_Nouveau.media.i18n_strings.unselect );
				$( target ).closest( '.bb-photo-thumb' ).addClass( 'selected' );
			} else {
				$( target ).closest( '.bb-photo-thumb' ).removeClass( 'selected' );
				$( target ).closest( '.bb-media-check-wrap' ).find( '.bp-tooltip' ).attr( 'data-bp-tooltip', BP_Nouveau.media.i18n_strings.select );

				var selectAllMedia = $( '.bp-nouveau #bb-select-deselect-all-media' );
				if ( selectAllMedia.hasClass( 'selected' ) ) {
					selectAllMedia.removeClass( 'selected' );
				}
			}
		},

		moveDocumentIntoFolder: function (event) {
			var target = $( event.currentTarget );
			event.preventDefault();

			var document_id = target.attr( 'id' );
			var folder_id   = target.closest( '.bp-media-move-file' ).find( '.bb-folder-selected-id' ).val();

			if ('' === document_id || '' === folder_id) {
				target.closest( '.modal-container' ).find( '.location-folder-list' ).addClass( 'has-error' );
				return false;
			}

			target.closest( '.modal-container' ).find( '.location-folder-list' ).removeClass( 'has-error' );
			target.addClass( 'loading' );

			var data = {
				'action': 'document_move',
				'_wpnonce': BP_Nouveau.nonces.media,
				'document_id': document_id,
				'folder_id': folder_id
			};

			$.ajax(
				{
					type: 'POST',
					url: BP_Nouveau.ajaxurl,
					data: data,
					success: function (response) {
						if (response.success) {
							var documentStream = $( '#media-stream' );
							documentStream.html( '' );
							documentStream.html( response.data.html );

							target.closest( '.bp-media-move-file' ).find( '.ac-document-close-button' ).trigger( 'click' );
						}
					}
				}
			);
		},

		deleteMedia: function (event) {
			var target = $( event.currentTarget );
			event.preventDefault();

			var media 			   = [];
			var buddyPressSelector = $( '#buddypress' );

			buddyPressSelector.find( '.media-list:not(.existing-media-list)' ).find( '.bb-media-check-wrap [name="bb-media-select"]:checked' ).each(
				function () {
					$( this ).closest( '.bb-photo-thumb' ).addClass( 'loading deleting' );
					media.push( $( this ).val() );
				}
			);

			if (media.length == 0) {
				return false;
			}

			target.prop( 'disabled', true );
			$( '#buddypress .media-list .bp-feedback' ).remove();

			var data = {
				'action': 'media_delete',
				'_wpnonce': BP_Nouveau.nonces.media,
				'media': media
			};

			$.ajax(
				{
					type: 'POST',
					url: BP_Nouveau.ajaxurl,
					data: data,
					success: function (response) {
						setTimeout(
							function () {
								target.prop( 'disabled', false );
							},
							500
						);
						if (response.success) {

							buddyPressSelector.find( '.media-list:not(.existing-media-list)' ).find( '.bb-media-check-wrap [name="bb-media-select"]:checked' ).each(
								function () {
									$( this ).closest( 'li' ).remove();
								}
							);

							if ($( '#buddypress' ).find( '.media-list:not(.existing-media-list)' ).find( 'li:not(.load-more)' ).length == 0) {
								$( '.bb-photos-actions' ).hide();
								var feedback = '<aside class="bp-feedback bp-messages info">\n' +
									'\t<span class="bp-icon" aria-hidden="true"></span>\n' +
									'\t<p>' + BP_Nouveau.media.i18n_strings.no_photos_found + '</p>\n' +
									'\t</aside>';
								$( '#buddypress [data-bp-list="media"]' ).html( feedback );
							}
						} else {
							$( '#buddypress .media-list' ).prepend( response.data.feedback );
						}

					}
				}
			);
		},

		toggleSelectAllMedia: function (event) {
			event.preventDefault();

			if ($( event.currentTarget ).hasClass( 'selected' )) {
				$( event.currentTarget ).data( 'bp-tooltip', BP_Nouveau.media.i18n_strings.selectall );
				this.deselectAllMedia( event );
			} else {
				$( event.currentTarget ).data( 'bp-tooltip', BP_Nouveau.media.i18n_strings.unselectall );
				this.selectAllMedia( event );
			}

			$( event.currentTarget ).toggleClass( 'selected' );
		},

		selectAllMedia: function (event) {
			event.preventDefault();

			$( '#buddypress' ).find( '.media-list:not(.existing-media-list)' ).find( '.bb-media-check-wrap [name="bb-media-select"]' ).each(
				function () {
					$( this ).prop( 'checked', true );
					$( this ).closest( '.bb-photo-thumb' ).addClass( 'selected' );
					$( this ).closest( '.bb-media-check-wrap' ).find( '.bp-tooltip' ).attr( 'data-bp-tooltip', BP_Nouveau.media.i18n_strings.unselect );
				}
			);
		},

		deselectAllMedia: function (event) {
			event.preventDefault();

			$( '#buddypress' ).find( '.media-list:not(.existing-media-list)' ).find( '.bb-media-check-wrap [name="bb-media-select"]' ).each(
				function () {
					$( this ).prop( 'checked', false );
					$( this ).closest( '.bb-photo-thumb' ).removeClass( 'selected' );
					$( this ).closest( '.bb-media-check-wrap' ).find( '.bp-tooltip' ).attr( 'data-bp-tooltip', BP_Nouveau.media.i18n_strings.select );
				}
			);
		},

		editAlbumTitle: function (event) {
			event.preventDefault();

			$( '#bb-album-title' ).show();
			$( '#bp-save-album-title' ).show();
			$( '#bp-cancel-edit-album-title' ).show();
			$( '#bp-edit-album-title' ).hide();
			$( '#bp-media-single-album #bp-single-album-title' ).hide();
		},

		editFolderTitle: function (event) {
			event.preventDefault();

			$( '#bb-album-title' ).show();
			$( '#bp-save-folder-title' ).show();
			$( '#bp-cancel-edit-album-title' ).show();
			$( '#bp-edit-folder-title' ).hide();
			$( '#bp-media-single-album #bp-single-album-title' ).hide();
		},

		cancelEditAlbumTitle: function (event) {
			event.preventDefault();

			$( '#bb-album-title' ).hide();
			$( '#bp-save-album-title,#bp-save-folder-title' ).hide();
			$( '#bp-cancel-edit-album-title' ).hide();
			$( '#bp-edit-album-title,#bp-edit-folder-title' ).show();
			$( '#bp-media-single-album #bp-single-album-title' ).show();
		},

		triggerDropzoneSelectFileDialog: function () {
			var self = this;

			self.dropzone_obj.hiddenFileInput.click();
		},

		closeUploader: function (event) {
			event.preventDefault();

			$( '#bp-media-uploader' ).hide();
			$( '#bp-media-add-more' ).hide();
			$( '#bp-media-uploader-modal-title' ).text( BP_Nouveau.media.i18n_strings.upload );
			$( '#bp-media-uploader-modal-status-text' ).text( '' );
			this.dropzone_obj.destroy();
			this.dropzone_media = [];

			var currentPopup = $( event.currentTarget ).closest( '#bp-media-uploader' );

			if (currentPopup.find( '.bb-field-steps' ).length) {
				currentPopup.find( '.bb-field-steps-1' ).show().siblings( '.bb-field-steps-2' ).hide();
				currentPopup.find( '#bp-media-document-prev, #bp-media-document-submit' ).hide();
			}

			this.clearFolderLocationUI( event );

		},

		loadMoreGif: function (e) {
			var el = e.target, self = this;

			var $forums_gif_container = $( e.target ).closest( 'form' ).find( '.forums-attached-gif-container' );
			var gif_container_key     = $forums_gif_container.data( 'key' );
			self.gif_container_key    = gif_container_key;

			if (el.scrollTop + el.offsetHeight >= el.scrollHeight && ! $forums_gif_container.hasClass( 'loading' )) {
				if (self.gif_data[gif_container_key].total_count > 0 && self.gif_data[gif_container_key].offset <= self.gif_data[gif_container_key].total_count) {
					var params = {
						offset: self.gif_data[gif_container_key].offset,
						fmt: 'json',
						limit: self.gif_data[gif_container_key].limit
					};

					$forums_gif_container.addClass( 'loading' );
					var request = null;
					if (_.isNull( self.gif_data[gif_container_key].q )) {
						request = self.giphy.trending( params, _.bind( self.loadMoreGifResponse, self ) );
					} else {
						request = self.giphy.search( _.extend( {q: self.gif_data[gif_container_key].q}, params ), _.bind( self.loadMoreGifResponse, self ) );
					}

					self.gif_data[gif_container_key].requests.push( request );
					self.gif_data[gif_container_key].offset = self.gif_data[gif_container_key].offset + self.gif_data[gif_container_key].limit;
				}
			}
		},

		loadMoreGroupMessagesGif: function(e) {
			var el = e.target, self = this;

			var $group_messages_gif_container = $(e.target).closest('form').find('.bp-group-messages-attached-gif-container');
			var gif_container_key = $group_messages_gif_container.data('key');
			self.gif_container_key = gif_container_key;

			if ( el.scrollTop + el.offsetHeight >= el.scrollHeight &&  ! $(e.target).closest('.bp-group-messages-attached-gif-container').hasClass('loading') ) {
				if ( self.gif_total_count > 0 && self.gif_offset <= self.gif_total_count ) {
					var params = {
							offset: self.gif_offset,
							fmt: 'json',
							limit: self.gif_limit
						};

					$(e.target).closest('.bp-group-messages-attached-gif-container').addClass('loading');
					var request = null;
					if ( _.isNull( self.gif_q ) ) {
						request = self.giphy.trending( params, _.bind( self.loadMoreGroupMessagesGifResponse, self ) );
					} else {
						request = self.giphy.search( _.extend( { q: self.gif_q }, params ), _.bind( self.loadMoreGroupMessagesGifResponse, self ) );
					}

					self.gif_requests.push( request );
					self.gif_offset = self.gif_offset + self.gif_limit;
					self.gif_data[gif_container_key].requests.push( request );
					self.gif_data[gif_container_key].offset = self.gif_data[gif_container_key].offset + self.gif_data[gif_container_key].limit;
				}
			}
		},

		loadMoreGroupMessagesGifResponse: function( response ) {
			var self = this, i = 0;
			$( '.bp-group-messages-attached-gif-container' ).removeClass( 'loading' );
			if ( typeof response.data !== 'undefined' && response.data.length ) {
				var li_html = '';
				for( i = 0; i < response.data.length; i++ ) {
					var bgNo = Math.floor( Math.random() * (6 - 1 + 1) ) + 1;
					li_html += '<li class="bg'+bgNo+'" style="height: '+response.data[i].images.fixed_width.height+'px;">\n' +
						'\t<a class="found-media-item" href="'+response.data[i].images.original.url+'" data-id="'+response.data[i].id+'">\n' +
						'\t\t<img src="'+response.data[i].images.fixed_width.url+'">\n' +
						'\t</a>\n' +
						'</li>';
					response.data[i].saved = false;
					self.gif_data.push(response.data[i]);
				}

				$('.bp-group-messages-attached-gif-container').find('.gif-search-results-list').append(li_html);
			}

			if ( typeof response.pagination !== 'undefined' && typeof response.pagination.total_count !== 'undefined' ) {
				self.gif_total_count = response.pagination.total_count;
			}
		},

		loadMoreGifResponse: function( response ) {
			var self = this, i = 0;
			$( 'div.forums-attached-gif-container[data-key="' + self.gif_container_key + '"]' ).removeClass( 'loading' );
			if (typeof response.data !== 'undefined' && response.data.length) {
				var li_html = '';
				for (i = 0; i < response.data.length; i++) {
					var bgNo               = Math.floor( Math.random() * (6 - 1 + 1) ) + 1;
					li_html               += '<li class="bg' + bgNo + '" style="height: ' + response.data[i].images.fixed_width.height + 'px;">\n' +
						'\t<a class="found-media-item" href="' + response.data[i].images.original.url + '" data-id="' + response.data[i].id + '">\n' +
						'\t\t<img src="' + response.data[i].images.fixed_width.url + '">\n' +
						'\t</a>\n' +
						'</li>';
					response.data[i].saved = false;
					self.gif_data[self.gif_container_key].data.push( response.data[i] );
				}

				$( 'div.forums-attached-gif-container[data-key="' + self.gif_container_key + '"]' ).closest( 'form' ).find( '.gif-search-results-list' ).append( li_html );
			}

			if (typeof response.pagination !== 'undefined' && typeof response.pagination.total_count !== 'undefined') {
				self.gif_data[self.gif_container_key].total_count = response.pagination.total_count;
			}
		},

		editGifPreview: function () {
			var self = this, gif_data = {};

			if (self.bbp_is_reply_edit && Object.keys( self.bbp_reply_edit_gif_data ).length) {
				gif_data = self.bbp_reply_edit_gif_data.gif_raw_data;
			} else if (self.bbp_is_topic_edit && Object.keys( self.bbp_topic_edit_gif_data ).length) {
				gif_data = self.bbp_topic_edit_gif_data.gif_raw_data;
			} else if (self.bbp_is_forum_edit && Object.keys( self.bbp_forum_edit_gif_data ).length) {
				gif_data = self.bbp_forum_edit_gif_data.gif_raw_data;
			}

			if (typeof gif_data.images === 'undefined') {
				return false;
			}

			var forumGifContainer = $( '#whats-new-attachments .forums-attached-gif-container' );
			forumGifContainer[0].style.backgroundImage = 'url(' + gif_data.images.fixed_width.url + ')';
			forumGifContainer[0].style.backgroundSize  = 'contain';
			forumGifContainer[0].style.height          = gif_data.images.original.height + 'px';
			forumGifContainer[0].style.width           = gif_data.images.original.width + 'px';
			forumGifContainer.find( '.gif-image-container img' ).attr( 'src', gif_data.images.original.url );
			forumGifContainer.removeClass( 'closed' );
			if ($( '#bbp_media_gif' ).length) {
				$( '#bbp_media_gif' ).val( JSON.stringify( gif_data ) );
			}
		},

		selectGif: function (e) {
			var self          = this, i = 0, target = $( e.currentTarget ),
				gif_container = target.closest( 'form' ).find( '.forums-attached-gif-container' );
			e.preventDefault();

			gif_container.closest( 'form' ).find( '.gif-media-search-dropdown' ).removeClass( 'open' );
			var gif_container_key = gif_container.data( 'key' );
			if (typeof self.gif_data[gif_container_key] !== 'undefined' && typeof self.gif_data[gif_container_key].data !== 'undefined' && self.gif_data[gif_container_key].data.length) {
				for (i = 0; i < self.gif_data[gif_container_key].data.length; i++) {
					if (self.gif_data[gif_container_key].data[i].id == e.currentTarget.dataset.id) {

						target.closest( 'form' ).find( '#whats-new-attachments .forums-attached-gif-container' )[0].style.backgroundImage = 'url(' + self.gif_data[gif_container_key].data[i].images.fixed_width.url + ')';
						target.closest( 'form' ).find( '#whats-new-attachments .forums-attached-gif-container' )[0].style.backgroundSize  = 'contain';
						target.closest( 'form' ).find( '#whats-new-attachments .forums-attached-gif-container' )[0].style.height          = self.gif_data[gif_container_key].data[i].images.original.height + 'px';
						target.closest( 'form' ).find( '#whats-new-attachments .forums-attached-gif-container' )[0].style.width           = self.gif_data[gif_container_key].data[i].images.original.width + 'px';

						target.closest( 'form' ).find( '#whats-new-attachments .forums-attached-gif-container' ).find( '.gif-image-container img' ).attr( 'src', self.gif_data[gif_container_key].data[i].images.original.url );
						target.closest( 'form' ).find( '#whats-new-attachments .forums-attached-gif-container' ).removeClass( 'closed' );
						if (target.closest( 'form' ).find( '#bbp_media_gif' ).length) {
							target.closest( 'form' ).find( '#bbp_media_gif' ).val( JSON.stringify( self.gif_data[gif_container_key].data[i] ) );
						}
						break;
					}
				}
			}
		},

		selectGroupMessagesGif: function(e) {
			var self = this, i = 0;
			e.preventDefault();

			var containerAttachmentGif = $('#whats-new-attachments .bp-group-messages-attached-gif-container');
			var inputHiddenGif 		   = $( '#bp_group_messages_gif' );

			$( '#whats-new-toolbar .bp-group-messages-attached-gif-container' ).parent().removeClass( 'open' );
			if ( self.gif_data.length ) {
				for( i = 0; i < self.gif_data.length; i++ ) {
					if ( self.gif_data[i].id == e.currentTarget.dataset.id ) {

						containerAttachmentGif[0].style.backgroundImage = 'url(' + self.gif_data[i].images.fixed_width.url + ')';
						containerAttachmentGif[0].style.backgroundSize = 'contain';
						containerAttachmentGif[0].style.height = self.gif_data[i].images.original.height + 'px';
						containerAttachmentGif[0].style.width = self.gif_data[i].images.original.width + 'px';
						containerAttachmentGif.find( '.gif-image-container img' ).attr( 'src' ,self.gif_data[i].images.original.url );
						containerAttachmentGif.removeClass( 'closed' );
						if( inputHiddenGif.length ) {
							inputHiddenGif.val( JSON.stringify( self.gif_data[i] ) );
						}
						break;
					}
				}
			}
		},

		removeSelectedGif: function (e) {
			e.preventDefault();
			this.resetForumsGifComponent( e );
		},

		removeGroupMessagesSelectedGif: function(e) {
			e.preventDefault();
			this.resetGroupMessagesGifComponent();
		},

		resetForumsGifComponent: function(e) {
			var target = $( e.target );
			target.closest( 'form' ).find( '.gif-media-search-dropdown' ).removeClass( 'open' );
			target.closest( 'form' ).find( '#whats-new-toolbar #forums-gif-button' ).removeClass( 'active' );

			var $forums_attached_gif_container = target.closest( 'form' ).find( '#whats-new-attachments .forums-attached-gif-container' );
			if ($forums_attached_gif_container) {
				$forums_attached_gif_container.addClass( 'closed' );
				$forums_attached_gif_container.find( '.gif-image-container img' ).attr( 'src', '' );
				$forums_attached_gif_container[0].style = '';
			}

			if (target.closest( 'form' ).find( '#bbp_media_gif' ).length) {
				target.closest( 'form' ).find( '#bbp_media_gif' ).val( '' );
			}
		},

		resetGroupMessagesGifComponent: function() {

			var containerAttachment = $( '#whats-new-attachments .bp-group-messages-attached-gif-container' );
			var inputHiddenGif 		= $( '#bp_group_messages_gif' );

			$('#whats-new-toolbar .bp-group-messages-attached-gif-container').parent().removeClass( 'open' );
			$('#whats-new-toolbar #bp-group-messages-gif-button').removeClass('active');
			containerAttachment.addClass('closed');
			containerAttachment.find('.gif-image-container img').attr('src','');
			containerAttachment[0].style = '';
			if( inputHiddenGif.length ) {
				inputHiddenGif.val('');
			}
		},

		searchGif: function(e) {
			var self = this;

			if (self.gif_timeout != null) {
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

		searchGroupMessagesGif: function(e) {
			var self = this;

			if ( self.gif_timeout != null ) {
				clearTimeout( this.gif_timeout );
			}

			self.gif_timeout = setTimeout( function() {
				self.gif_timeout = null;
				self.searchGroupMessagesGifRequest( e, e.target.value );
			}, 1000 );
		},

		searchGroupMessagesGifRequest: function( e ) {
			var self = this;
			self.gif_q = e.target.value;
			self.gif_offset = 0;
			var i = 0;

			self.clearGifRequests();
			$(e.target).closest('.bp-group-messages-attached-gif-container').addClass('loading');

			var request = self.giphy.search( {
					q: self.gif_q,
					offset: self.gif_offset,
					fmt: 'json',
					limit: self.gif_limit
				},
				function( response ) {
					if ( typeof response.data !== 'undefined' && response.data.length ) {
						var li_html = '';
						for( i = 0; i < response.data.length; i++ ) {
							var bgNo = Math.floor( Math.random() * (6 - 1 + 1) ) + 1;
							li_html += '<li class="bg'+bgNo+'" style="height: '+response.data[i].images.fixed_width.height+'px;">\n' +
								'\t<a class="found-media-item" href="'+response.data[i].images.original.url+'" data-id="'+response.data[i].id+'">\n' +
								'\t\t<img src="'+response.data[i].images.fixed_width.url+'">\n' +
								'\t</a>\n' +
								'</li>';
							response.data[i].saved = false;
							self.gif_data.push(response.data[i]);
						}

						$(e.target).closest('.bp-group-messages-attached-gif-container').find('.gif-search-results-list').append(li_html);
					}

					if ( typeof response.pagination !== 'undefined' && typeof response.pagination.total_count !== 'undefined' ) {
						self.gif_total_count = response.pagination.total_count;
					}
					$(e.target).closest('.bp-group-messages-attached-gif-container').removeClass('loading');
				}
			);

			self.gif_requests.push( request );
			self.gif_offset = self.gif_offset + self.gif_limit;
		},

		searchGifRequest: function( e ) {
			var self = this, i = 0;

			var $forums_gif_container = $( e.target ).closest( 'form' ).find( '.forums-attached-gif-container' );
			$forums_gif_container.addClass( 'loading' );
			var gif_container_key = $forums_gif_container.data( 'key' );

			self.clearGifRequests( gif_container_key );

			self.gif_data[gif_container_key].q      = e.target.value;
			self.gif_data[gif_container_key].offset = 0;

			var request = self.giphy.search(
				{
					q: self.gif_data[gif_container_key].q,
					offset: self.gif_data[gif_container_key].offset,
					fmt: 'json',
					limit: self.gif_data[gif_container_key].limit
				},
				function (response) {
					if (typeof response.data !== 'undefined' && response.data.length) {
						var li_html = '';
						for (i = 0; i < response.data.length; i++) {
							var bgNo               = Math.floor( Math.random() * (6 - 1 + 1) ) + 1;
							li_html               += '<li class="bg' + bgNo + '" style="height: ' + response.data[i].images.fixed_width.height + 'px;">\n' +
								'\t<a class="found-media-item" href="' + response.data[i].images.original.url + '" data-id="' + response.data[i].id + '">\n' +
								'\t\t<img src="' + response.data[i].images.fixed_width.url + '">\n' +
								'\t</a>\n' +
								'</li>';
							response.data[i].saved = false;
							self.gif_data[gif_container_key].data.push( response.data[i] );
						}

						$( e.target ).closest( '.gif-search-content' ).find( '.gif-search-results-list' ).append( li_html );
					}

					if (typeof response.pagination !== 'undefined' && typeof response.pagination.total_count !== 'undefined') {
						self.gif_data[gif_container_key].total_count = response.pagination.total_count;
					}
					$forums_gif_container.removeClass( 'loading' );
				}
			);

			self.gif_data[gif_container_key].requests.push( request );
			self.gif_data[gif_container_key].offset = self.gif_data[gif_container_key].offset + self.gif_data[gif_container_key].limit;
		},

		clearGifRequests: function (gif_container_key) {
			var self = this;

			if (typeof self.gif_data[gif_container_key] !== 'undefined' && typeof self.gif_data[gif_container_key].requests !== 'undefined') {
				for (var i = 0; i < self.gif_data[gif_container_key].requests.length; i++) {
					self.gif_data[gif_container_key].requests[i].abort();
				}

				$( '[data-key="' + gif_container_key + '"]' ).closest( 'form' ).find( '.gif-search-results-list li' ).remove();

				self.gif_data[gif_container_key].requests = [];
				self.gif_data[gif_container_key].data     = [];
				self.gif_data.splice( gif_container_key, 1 );
			}
		},

		toggleGifSelector: function (event) {
			var self                = this, target = $( event.currentTarget ),
				gif_search_dropdown = target.closest( 'form' ).find( '.gif-media-search-dropdown' ), i = 0;
			event.preventDefault();

			if (typeof window.Giphy !== 'undefined' && typeof BP_Nouveau.media.gif_api_key !== 'undefined') {
				self.giphy = new window.Giphy( BP_Nouveau.media.gif_api_key );

				var $forums_attached_gif_container = target.closest( 'form' ).find( '.forums-attached-gif-container' );
				$forums_attached_gif_container.addClass( 'loading' );
				var gif_container_key = $forums_attached_gif_container.data( 'key' );

				self.clearGifRequests( gif_container_key );

				self.gif_data[gif_container_key] = {
					q: null,
					offset: 0,
					limit: 20,
					requests: [],
					total_count: 0,
					data: []
				};

				var request = self.giphy.trending(
					{
						offset: self.gif_data[gif_container_key].offset,
						fmt: 'json',
						limit: self.gif_data[gif_container_key].limit
					},
					function (response) {

						if (typeof response.data !== 'undefined' && response.data.length) {
							var li_html = '';
							for (i = 0; i < response.data.length; i++) {
								var bgNo               = Math.floor( Math.random() * (6 - 1 + 1) ) + 1;
								li_html               += '<li class="bg' + bgNo + '" style="height: ' + response.data[i].images.fixed_width.height + 'px;">\n' +
								'\t<a class="found-media-item" href="' + response.data[i].images.original.url + '" data-id="' + response.data[i].id + '">\n' +
								'\t\t<img src="' + response.data[i].images.fixed_width.url + '">\n' +
								'\t</a>\n' +
								'</li>';
								response.data[i].saved = false;
								self.gif_data[gif_container_key].data.push( response.data[i] );
							}

							target.closest( 'form' ).find( '.gif-search-results-list' ).append( li_html );
						}

						if (typeof response.pagination !== 'undefined' && typeof response.pagination.total_count !== 'undefined') {
							self.gif_data[gif_container_key].total_count = response.pagination.total_count;
						}

						$forums_attached_gif_container.removeClass( 'loading' );
					}
				);

				self.gif_data[gif_container_key].requests.push( request );
				self.gif_data[gif_container_key].offset = self.gif_data[gif_container_key].offset + self.gif_data[gif_container_key].limit;
			}

			gif_search_dropdown.toggleClass( 'open' );
			target.toggleClass( 'active' );
			var $forums_media_container = target.closest( 'form' ).find( '#forums-post-media-uploader' );
			if ($forums_media_container.length) {
				self.resetForumsMediaComponent( $forums_media_container.data( 'key' ) );
			}
		},

		toggleGroupMessagesGifSelector: function( event ) {
			var self = this, target = $(event.currentTarget), gif_search_dropdown = target.closest('form').find('.gif-media-search-dropdown'), i = 0;
			event.preventDefault();

			if ( typeof window.Giphy !== 'undefined' && typeof BP_Nouveau.media.gif_api_key !== 'undefined' && self.giphy == null ) {
				self.giphy = new window.Giphy(BP_Nouveau.media.gif_api_key);
				self.gif_offset = 0;
				self.gif_q = null;
				self.gif_limit = 20;
				self.gif_requests = [];
				self.gif_data = [];
				self.clearGifRequests();
				$('.gif-search-query').closest('.bp-group-messages-attached-gif-container').addClass('loading');

				var request = self.giphy.trending( {
					offset: self.gif_offset,
					fmt: 'json',
					limit: self.gif_limit
				}, function( response ) {

					if ( typeof response.data !== 'undefined' && response.data.length ) {
						var li_html = '';
						for( i = 0; i < response.data.length; i++ ) {
							var bgNo = Math.floor( Math.random() * (6 - 1 + 1) ) + 1;
							li_html += '<li class="bg'+bgNo+'" style="height: '+response.data[i].images.fixed_width.height+'px;">\n' +
								'\t<a class="found-media-item" href="'+response.data[i].images.original.url+'" data-id="'+response.data[i].id+'">\n' +
								'\t\t<img src="'+response.data[i].images.fixed_width.url+'">\n' +
								'\t</a>\n' +
								'</li>';
							response.data[i].saved = false;
							self.gif_data.push(response.data[i]);
						}

						target.closest('form').find('.gif-search-results-list').append(li_html);
					}

					if ( typeof response.pagination !== 'undefined' && typeof response.pagination.total_count !== 'undefined' ) {
						self.gif_total_count = response.pagination.total_count;
					}

					$('.gif-search-query').closest('.bp-group-messages-attached-gif-container').removeClass('loading');
				});

				self.gif_requests.push( request );
				self.gif_offset = self.gif_offset + self.gif_limit;
			}

			gif_search_dropdown.toggleClass('open');
			target.toggleClass('active');
			self.resetGroupMessagesMediaComponent();
		},

		resetGroupMessagesMediaComponent: function() {
			var self = this;
			if ( self.dropzone_obj ) {
				self.dropzone_obj.destroy();
			}
			self.dropzone_media = [];
			$( 'div#bp-group-messages-post-media-uploader' ).html('');
			$( 'div#bp-group-messages-post-media-uploader' ).addClass( 'closed' ).removeClass( 'open' );
		},

		resetForumsMediaComponent: function( dropzone_container_key ) {
			var self = this;

			if (typeof dropzone_container_key !== 'undefined') {

				if (typeof self.dropzone_obj[dropzone_container_key] !== 'undefined') {
					self.dropzone_obj[dropzone_container_key].destroy();
					self.dropzone_obj.splice( dropzone_container_key, 1 );
					self.dropzone_media.splice( dropzone_container_key, 1 );
				}

				var keySelector = $( 'div#forums-post-media-uploader[data-key="' + dropzone_container_key + '"]' );
				keySelector.html( '' );
				keySelector.addClass( 'closed' ).removeClass( 'open' );
			}
		},

		resetForumsDocumentComponent: function (dropzone_container_key) {
			var self = this;

			if (typeof dropzone_container_key !== 'undefined') {

				if (typeof self.dropzone_obj[dropzone_container_key] !== 'undefined') {
					self.dropzone_obj[dropzone_container_key].destroy();
					self.dropzone_obj.splice( dropzone_container_key, 1 );
					self.dropzone_media.splice( dropzone_container_key, 1 );
				}

				var keySelector = $( 'div#forums-post-document-uploader[data-key="' + dropzone_container_key + '"]' );
				keySelector.html( '' );
				keySelector.addClass( 'closed' ).removeClass( 'open' );
			}
		},

		openForumsUploader: function (event) {
			var self               = this, target = $( event.currentTarget ),
				dropzone_container = target.closest( 'form' ).find( '#forums-post-media-uploader' ), edit_medias = [];
			event.preventDefault();

			if (typeof window.Dropzone !== 'undefined' && dropzone_container.length) {

				var dropzone_obj_key = dropzone_container.data( 'key' );

				if (dropzone_container.hasClass( 'closed' )) {

					// init dropzone
					self.dropzone_obj[dropzone_obj_key]   = new Dropzone( dropzone_container[0], self.options );
					self.dropzone_media[dropzone_obj_key] = [];

					self.dropzone_obj[dropzone_obj_key].on(
						'sending',
						function (file, xhr, formData) {
							formData.append( 'action', 'media_upload' );
							formData.append( '_wpnonce', BP_Nouveau.nonces.media );
						}
					);

					self.dropzone_obj[dropzone_obj_key].on(
						'error',
						function (file, response) {
							if (file.accepted) {
								if (typeof response !== 'undefined' && typeof response.data !== 'undefined' && typeof response.data.feedback !== 'undefined') {
									$( file.previewElement ).find( '.dz-error-message span' ).text( response.data.feedback );
								}
							} else {
								self.dropzone_obj[dropzone_obj_key].removeFile( file );
							}
						}
					);

					self.dropzone_obj[dropzone_obj_key].on(
						'success',
						function (file, response) {
							if (response.data.id) {
								file.id                  = response.id;
								response.data.uuid       = file.upload.uuid;
								response.data.menu_order = $( file.previewElement ).closest( '.dropzone' ).find( file.previewElement ).index() - 1;
								response.data.album_id   = self.album_id;
								response.data.group_id   = self.group_id;
								response.data.saved      = false;
								self.dropzone_media[dropzone_obj_key].push( response.data );
								self.addMediaIdsToForumsForm( dropzone_container );
							}
						}
					);

					self.dropzone_obj[dropzone_obj_key].on(
						'removedfile',
						function (file) {
							if (self.dropzone_media[dropzone_obj_key].length) {
								for (var i in self.dropzone_media[dropzone_obj_key]) {
									if (file.upload.uuid == self.dropzone_media[dropzone_obj_key][i].uuid) {

										if (( ! this.bbp_is_reply_edit && ! this.bbp_is_topic_edit && ! this.bbp_is_forum_edit) && typeof self.dropzone_media[dropzone_obj_key][i].saved !== 'undefined' && ! self.dropzone_media[dropzone_obj_key][i].saved) {
											self.removeAttachment( self.dropzone_media[dropzone_obj_key][i].id );
										}

										self.dropzone_media[dropzone_obj_key].splice( i, 1 );
										self.addMediaIdsToForumsForm( dropzone_container );
										break;
									}
								}
							}
						}
					);

					if ((this.bbp_is_reply_edit || this.bbp_is_topic_edit || this.bbp_is_forum_edit) &&
						(this.bbp_reply_edit_media.length || this.bbp_topic_edit_media.length || this.bbp_forum_edit_media.length)) {

						if (this.bbp_reply_edit_media.length) {
							edit_medias = this.bbp_reply_edit_media;
						} else if (this.bbp_topic_edit_media.length) {
							edit_medias = this.bbp_topic_edit_media;
						} else if (this.bbp_forum_edit_media.length) {
							edit_medias = this.bbp_forum_edit_media;
						}

						if (edit_medias.length) {
							var mock_file = false;
							for (var i = 0; i < edit_medias.length; i++) {
								mock_file = false;
								self.dropzone_media[dropzone_obj_key].push(
									{
										'id': edit_medias[i].attachment_id,
										'media_id': edit_medias[i].id,
										'name': edit_medias[i].title,
										'thumb': edit_medias[i].thumb,
										'url': edit_medias[i].full,
										'uuid': edit_medias[i].id,
										'menu_order': i,
										'saved': true
									}
								);

								mock_file = {
									name: edit_medias[i].title,
									accepted: true,
									kind: 'image',
									upload: {
										filename: edit_medias[i].title,
										uuid: edit_medias[i].id
									},
									dataURL: edit_medias[i].url,
									id: edit_medias[i].id
								};

								self.dropzone_obj[dropzone_obj_key].files.push( mock_file );
								self.dropzone_obj[dropzone_obj_key].emit( 'addedfile', mock_file );
								self.createThumbnailFromUrl( mock_file, dropzone_container );
							}
							self.addMediaIdsToForumsForm( dropzone_container );
						}
					}

					// container class to open close
					dropzone_container.removeClass( 'closed' ).addClass( 'open' );

					// reset gif component
					self.resetForumsGifComponent( event );

				} else {
					self.resetForumsMediaComponent( dropzone_obj_key );
				}

			}

		},

		openGroupMessagesUploader: function(event) {
			var self = this, dropzone_container = $('div#bp-group-messages-post-media-uploader'), edit_medias = [];
			event.preventDefault();

			if ( typeof window.Dropzone !== 'undefined' && dropzone_container.length ) {

				if ( dropzone_container.hasClass('closed') ) {

					// init dropzone
					self.dropzone_obj = new Dropzone('div#bp-group-messages-post-media-uploader', self.options);

					self.dropzone_obj.on('sending', function(file, xhr, formData) {
						formData.append('action', 'media_upload');
						formData.append('_wpnonce', BP_Nouveau.nonces.media);
					});

					self.dropzone_obj.on('error', function(file,response) {
						if ( file.accepted ) {
							if ( typeof response !== 'undefined' && typeof response.data !== 'undefined' && typeof response.data.feedback !== 'undefined' ) {
								$(file.previewElement).find('.dz-error-message span').text(response.data.feedback);
							}
						} else {
							self.dropzone_obj.removeFile(file);
						}
					});

					self.dropzone_obj.on('success', function(file, response) {
						if ( response.data.id ) {
							file.id 				 = response.id;
							response.data.uuid 		 = file.upload.uuid;
							response.data.menu_order = $(file.previewElement).closest('.dropzone').find(file.previewElement).index() - 1;
							response.data.album_id 	 = self.album_id;
							response.data.group_id 	 = self.group_id;
							response.data.saved    	 = false;
							self.dropzone_media.push( response.data );
							self.addMediaIdsToGroupMessagesForm();
						}
					});

					self.dropzone_obj.on('removedfile', function(file) {
						if ( self.dropzone_media.length ) {
							for ( var i in self.dropzone_media ) {
								if ( file.upload.uuid == self.dropzone_media[i].uuid  ) {

									if ( typeof self.dropzone_media[i].saved !== 'undefined' && ! self.dropzone_media[i].saved ) {
										self.removeAttachment(self.dropzone_media[i].id);
									}

									self.dropzone_media.splice( i, 1 );
									self.addMediaIdsToGroupMessagesForm();
									break;
								}
							}
						}
					});

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
							for( var i = 0; i < edit_medias.length; i++ ) {
								mock_file = false;
								self.dropzone_media.push({
									'id': edit_medias[i].attachment_id,
									'media_id': edit_medias[i].id,
									'name': edit_medias[i].title,
									'thumb': edit_medias[i].thumb,
									'url': edit_medias[i].full,
									'uuid': edit_medias[i].id,
									'menu_order': i,
									'saved': true
								});

								mock_file = {
									name: edit_medias[i].title,
									accepted: true,
									kind: 'image',
									upload: {
										filename: edit_medias[i].title,
										uuid: edit_medias[i].id
									},
									dataURL: edit_medias[i].url,
									id: edit_medias[i].id
								};

								self.dropzone_obj.files.push(mock_file);
								self.dropzone_obj.emit('addedfile', mock_file);
								self.createThumbnailFromUrl(mock_file);
							}
							self.addMediaIdsToGroupMessagesForm();
						}
					}

					// container class to open close
					dropzone_container.removeClass('closed').addClass('open');

					// reset gif component
					self.resetGroupMessagesGifComponent();

				} else {
					self.resetGroupMessagesMediaComponent();
				}

			}

		},

		openForumsDocumentUploader: function (event) {
			var self               = this, target = $( event.currentTarget ),
				dropzone_container = target.closest( 'form' ).find( '#forums-post-document-uploader' ), edit_documents = [];
			event.preventDefault();

			if (typeof window.Dropzone !== 'undefined' && dropzone_container.length) {

				var dropzone_obj_key = dropzone_container.data( 'key' );

				if (dropzone_container.hasClass( 'closed' )) {

					// init dropzone
					self.dropzone_obj[dropzone_obj_key]   = new Dropzone( dropzone_container[0], self.documentOptions );
					self.dropzone_media[dropzone_obj_key] = [];

					self.dropzone_obj[dropzone_obj_key].on(
						'sending',
						function (file, xhr, formData) {
							formData.append( 'action', 'document_document_upload' );
							formData.append( '_wpnonce', BP_Nouveau.nonces.media );
						}
					);

					self.dropzone_obj[dropzone_obj_key].on(
						'error',
						function (file, response) {
							if (file.accepted) {
								if (typeof response !== 'undefined' && typeof response.data !== 'undefined' && typeof response.data.feedback !== 'undefined') {
									$( file.previewElement ).find( '.dz-error-message span' ).text( response.data.feedback );
								}
							} else {
								self.dropzone_obj[dropzone_obj_key].removeFile( file );
							}
						}
					);

					self.dropzone_obj[dropzone_obj_key].on(
						'success',
						function (file, response) {
							if (response.data.id) {
								file.id                  = response.id;
								response.data.uuid       = file.upload.uuid;
								response.data.menu_order = $( file.previewElement ).closest( '.dropzone' ).find( file.previewElement ).index() - 1;
								response.data.album_id   = self.album_id;
								response.data.group_id   = self.group_id;
								response.data.saved      = false;
								self.dropzone_media[dropzone_obj_key].push( response.data );
								self.addDocumentIdsToForumsForm( dropzone_container );
							}
						}
					);

					self.dropzone_obj[dropzone_obj_key].on(
						'removedfile',
						function (file) {
							if (self.dropzone_media[dropzone_obj_key].length) {
								for (var i in self.dropzone_media[dropzone_obj_key]) {
									if (file.upload.uuid == self.dropzone_media[dropzone_obj_key][i].uuid) {

										if (( ! this.bbp_is_reply_edit && ! this.bbp_is_topic_edit && ! this.bbp_is_forum_edit) && typeof self.dropzone_media[dropzone_obj_key][i].saved !== 'undefined' && ! self.dropzone_media[dropzone_obj_key][i].saved) {
											self.removeAttachment( self.dropzone_media[dropzone_obj_key][i].id );
										}

										self.dropzone_media[dropzone_obj_key].splice( i, 1 );
										self.addDocumentIdsToForumsForm( dropzone_container );
										break;
									}
								}
							}
						}
					);

					if ((this.bbp_is_reply_edit || this.bbp_is_topic_edit || this.bbp_is_forum_edit) &&
						(this.bbp_reply_edit_document.length || this.bbp_topic_edit_document.length || this.bbp_forum_edit_document.length)) {

						if (this.bbp_reply_edit_document.length) {
							edit_documents = this.bbp_reply_edit_document;
						} else if (this.bbp_topic_edit_document.length) {
							edit_documents = this.bbp_topic_edit_document;
						} else if (this.bbp_forum_edit_document.length) {
							edit_documents = this.bbp_forum_edit_document;
						}

						if (edit_documents.length) {
							var mock_file = false;
							for (var d = 0; d < edit_documents.length; d++) {
								mock_file = false;
								self.dropzone_media[dropzone_obj_key].push(
									{
										'id': edit_documents[d].attachment_id,
										'media_id': edit_documents[d].id,
										'name': edit_documents[d].name,
										'title': edit_documents[d].name,
										'size': edit_documents[d].size,
										// 'thumb': edit_documents[d].thumb,
										'url': edit_documents[d].url,
										'uuid': edit_documents[d].id,
										'menu_order': d,
										'saved': true
									}
								);

								mock_file = {
									name: edit_documents[d].name,
									size: edit_documents[d].size,
									accepted: true,
									kind: 'document',
									upload: {
										name: edit_documents[d].name,
										title: edit_documents[d].name,
										size: edit_documents[d].size,
										uuid: edit_documents[d].id
									},
									dataURL: edit_documents[d].url,
									id: edit_documents[d].id
								};

								self.dropzone_obj[dropzone_obj_key].files.push( mock_file );
								self.dropzone_obj[dropzone_obj_key].emit( 'addedfile', mock_file );
								self.dropzone_obj[dropzone_obj_key].emit( 'complete', mock_file );
								// self.createDocumentThumbnailFromUrl(mock_file,dropzone_container);
							}
							self.addDocumentIdsToForumsForm( dropzone_container );
						}
					}

					// container class to open close
					dropzone_container.removeClass( 'closed' ).addClass( 'open' );

					// reset gif component
					self.resetForumsGifComponent( event );

				} else {
					self.resetForumsDocumentComponent( dropzone_obj_key );
				}

			}

		},

		addMediaIdsToForumsForm: function (dropzone_container) {
			var self = this, dropzone_obj_key = dropzone_container.data( 'key' );
			if (dropzone_container.closest( '#whats-new-attachments' ).find( '#bbp_media' ).length) {
				dropzone_container.closest( '#whats-new-attachments' ).find( '#bbp_media' ).val( JSON.stringify( self.dropzone_media[dropzone_obj_key] ) );
			}
		},

		addDocumentIdsToForumsForm: function (dropzone_container) {
			var self = this, dropzone_obj_key = dropzone_container.data( 'key' );
			if (dropzone_container.closest( '#whats-new-attachments' ).find( '#bbp_document' ).length) {
				dropzone_container.closest( '#whats-new-attachments' ).find( '#bbp_document' ).val( JSON.stringify( self.dropzone_media[dropzone_obj_key] ) );
			}
		},

		createThumbnailFromUrl: function (mock_file, dropzone_container) {
			var self = this, dropzone_obj_key = dropzone_container.data( 'key' );
			self.dropzone_obj[dropzone_obj_key].createThumbnailFromUrl(
				mock_file,
				self.dropzone_obj[dropzone_obj_key].options.thumbnailWidth,
				self.dropzone_obj[dropzone_obj_key].options.thumbnailHeight,
				self.dropzone_obj[dropzone_obj_key].options.thumbnailMethod,
				true,
				function (thumbnail) {
					self.dropzone_obj[dropzone_obj_key].emit( 'thumbnail', mock_file, thumbnail );
					self.dropzone_obj[dropzone_obj_key].emit( 'complete', mock_file );
				}
			);
		},

		openUploader: function (event) {
			var self = this;
			event.preventDefault();

			if (typeof window.Dropzone !== 'undefined' && $( 'div#media-uploader' ).length) {

				$( '#bp-media-uploader' ).show();

				self.dropzone_obj = new Dropzone( 'div#media-uploader', self.options );

				self.dropzone_obj.on(
					'sending',
					function (file, xhr, formData) {
						formData.append( 'action', 'media_upload' );
						formData.append( '_wpnonce', BP_Nouveau.nonces.media );
					}
				);

				self.dropzone_obj.on(
					'addedfile',
					function () {
						setTimeout(
							function () {
								if (self.dropzone_obj.getAcceptedFiles().length) {
									$( '#bp-media-uploader-modal-status-text' ).text( wp.i18n.sprintf( BP_Nouveau.media.i18n_strings.upload_status, self.dropzone_media.length, self.dropzone_obj.getAcceptedFiles().length ) ).show();
								}
							},
							1000
						);
					}
				);

				self.dropzone_obj.on(
					'error',
					function (file, response) {
						if (file.accepted) {
							if (typeof response !== 'undefined' && typeof response.data !== 'undefined' && typeof response.data.feedback !== 'undefined') {
								$( file.previewElement ).find( '.dz-error-message span' ).text( response.data.feedback );
							}
						} else {
							self.dropzone_obj.removeFile( file );
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
					function (file, response) {
						if (response.data.id) {
							file.id                  = response.id;
							response.data.uuid       = file.upload.uuid;
							response.data.menu_order = self.dropzone_media.length;
							response.data.album_id   = self.album_id;
							response.data.group_id   = self.group_id;
							response.data.saved      = false;
							self.dropzone_media.push( response.data );
						}
						$( '#bp-media-add-more' ).show();
						$( '#bp-media-submit' ).show();
						$( '#bp-media-uploader-modal-title' ).text( BP_Nouveau.media.i18n_strings.uploading + '...' );
						$( '#bp-media-uploader-modal-status-text' ).text( wp.i18n.sprintf( BP_Nouveau.media.i18n_strings.upload_status, self.dropzone_media.length, self.dropzone_obj.getAcceptedFiles().length ) ).show();
					}
				);

				self.dropzone_obj.on(
					'removedfile',
					function (file) {
						if (self.dropzone_media.length) {
							for (var i in self.dropzone_media) {
								if (file.upload.uuid == self.dropzone_media[i].uuid) {

									if (typeof self.dropzone_media[i].saved !== 'undefined' && ! self.dropzone_media[i].saved) {
										self.removeAttachment( self.dropzone_media[i].id );
									}

									self.dropzone_media.splice( i, 1 );
									break;
								}
							}
						}
						if ( ! self.dropzone_obj.getAcceptedFiles().length) {
							$( '#bp-media-uploader-modal-status-text' ).text( '' );
							$( '#bp-media-add-more' ).hide();
							$( '#bp-media-submit' ).hide();
						} else {
							$( '#bp-media-uploader-modal-status-text' ).text( wp.i18n.sprintf( BP_Nouveau.media.i18n_strings.upload_status, self.dropzone_media.length, self.dropzone_obj.getAcceptedFiles().length ) ).show();
						}
					}
				);
			}
		},

		openDocumentUploader: function (event) {
			var self = this;
			event.preventDefault();

			if (typeof window.Dropzone !== 'undefined' && $( 'div#media-uploader' ).length) {

				if ($( '#bp-media-uploader' ).hasClass( 'bp-media-document-uploader' )) {
					this.folderLocationUI( '#bp-media-uploader' );
				}

				$( '#bp-media-uploader' ).show();

				self.dropzone_obj = new Dropzone( 'div#media-uploader', self.options );

				self.dropzone_obj.on(
					'sending',
					function (file, xhr, formData) {
						formData.append( 'action', 'document_document_upload' );
						formData.append( '_wpnonce', BP_Nouveau.nonces.media );
					}
				);

				self.dropzone_obj.on(
					'addedfile',
					function () {
						setTimeout(
							function () {
								if (self.dropzone_obj.getAcceptedFiles().length) {
									$( '#bp-media-uploader-modal-status-text' ).text( wp.i18n.sprintf( BP_Nouveau.media.i18n_strings.upload_status, self.dropzone_media.length, self.dropzone_obj.getAcceptedFiles().length ) ).show();
								}
							},
							1000
						);
					}
				);

				self.dropzone_obj.on(
					'error',
					function (file, response) {
						if (file.accepted) {
							if (typeof response !== 'undefined' && typeof response.data !== 'undefined' && typeof response.data.feedback !== 'undefined') {
								$( file.previewElement ).find( '.dz-error-message span' ).text( response.data.feedback );
							}
						} else {
							self.dropzone_obj.removeFile( file );
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
					function (file, response) {
						if (response.data.id) {
							file.id                  = response.id;
							response.data.uuid       = file.upload.uuid;
							response.data.menu_order = self.dropzone_media.length;
							response.data.folder_id  = self.album_id;
							response.data.group_id   = self.group_id;
							response.data.saved      = false;
							self.dropzone_media.push( response.data );
						}
						$( '#bp-media-document-add-more' ).show();
						$( '#bp-media-document-next' ).show();
						$( '#bp-media-uploader-modal-title' ).text( BP_Nouveau.media.i18n_strings.uploading + '...' );
						$( '#bp-media-uploader-modal-status-text' ).text( wp.i18n.sprintf( BP_Nouveau.media.i18n_strings.upload_status, self.dropzone_media.length, self.dropzone_obj.getAcceptedFiles().length ) ).show();
					}
				);

				self.dropzone_obj.on(
					'removedfile',
					function (file) {
						if (self.dropzone_media.length) {
							for (var i in self.dropzone_media) {
								if (file.upload.uuid == self.dropzone_media[i].uuid) {

									if (typeof self.dropzone_media[i].saved !== 'undefined' && ! self.dropzone_media[i].saved) {
										self.removeAttachment( self.dropzone_media[i].id );
									}

									self.dropzone_media.splice( i, 1 );
									break;
								}
							}
						}
						if ( ! self.dropzone_obj.getAcceptedFiles().length) {
							$( '#bp-media-uploader-modal-status-text' ).text( '' );
							$( '#bp-media-document-add-more' ).hide();
							$( '#bp-media-document-next' ).hide();
						} else {
							$( '#bp-media-uploader-modal-status-text' ).text( wp.i18n.sprintf( BP_Nouveau.media.i18n_strings.upload_status, self.dropzone_media.length, self.dropzone_obj.getAcceptedFiles().length ) ).show();
						}
					}
				);
			}
		},

		openDocumentFolderUploader: function (event) {
			var self = this;
			event.preventDefault();

			if (typeof window.Dropzone !== 'undefined' && $( 'div#bp-media-create-folder' ).length) {

				$( '#bp-media-create-folder' ).show();

				self.dropzone_obj = new Dropzone( 'div#media-uploader-folder', self.options );

				self.dropzone_obj.on(
					'sending',
					function (file, xhr, formData) {
						formData.append( 'action', 'document_document_upload' );
						formData.append( '_wpnonce', BP_Nouveau.nonces.media );
					}
				);

				self.dropzone_obj.on(
					'addedfile',
					function () {
						setTimeout(
							function () {
								if (self.dropzone_obj.getAcceptedFiles().length) {
									$( '#bp-media-uploader-modal-status-text' ).text( wp.i18n.sprintf( BP_Nouveau.media.i18n_strings.upload_status, self.dropzone_media.length, self.dropzone_obj.getAcceptedFiles().length ) ).show();
								}
							},
							1000
						);
					}
				);

				self.dropzone_obj.on(
					'error',
					function (file, response) {
						if (file.accepted) {
							if (typeof response !== 'undefined' && typeof response.data !== 'undefined' && typeof response.data.feedback !== 'undefined') {
								$( file.previewElement ).find( '.dz-error-message span' ).text( response.data.feedback );
							}
						} else {
							self.dropzone_obj.removeFile( file );
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
					function (file, response) {
						if (response.data.id) {
							file.id                  = response.id;
							response.data.uuid       = file.upload.uuid;
							response.data.menu_order = self.dropzone_media.length;
							response.data.folder_id  = self.album_id;
							response.data.group_id   = self.group_id;
							response.data.saved      = false;
							self.dropzone_media.push( response.data );
						}
						$( '#bp-media-document-add-more' ).show();
						$( '#bp-media-document-submit' ).show();
						$( '#bp-media-uploader-modal-title' ).text( BP_Nouveau.media.i18n_strings.uploading + '...' );
						$( '#bp-media-uploader-modal-status-text' ).text( wp.i18n.sprintf( BP_Nouveau.media.i18n_strings.upload_status, self.dropzone_media.length, self.dropzone_obj.getAcceptedFiles().length ) ).show();
					}
				);

				self.dropzone_obj.on(
					'removedfile',
					function (file) {
						if (self.dropzone_media.length) {
							for (var i in self.dropzone_media) {
								if (file.upload.uuid == self.dropzone_media[i].uuid) {

									if (typeof self.dropzone_media[i].saved !== 'undefined' && ! self.dropzone_media[i].saved) {
										self.removeAttachment( self.dropzone_media[i].id );
									}

									self.dropzone_media.splice( i, 1 );
									break;
								}
							}
						}
						if ( ! self.dropzone_obj.getAcceptedFiles().length) {
							$( '#bp-media-uploader-modal-status-text' ).text( '' );
							$( '#bp-media-document-add-more' ).hide();
							$( '#bp-media-document-submit' ).hide();
						} else {
							$( '#bp-media-uploader-modal-status-text' ).text( wp.i18n.sprintf( BP_Nouveau.media.i18n_strings.upload_status, self.dropzone_media.length, self.dropzone_obj.getAcceptedFiles().length ) ).show();
						}
					}
				);
			}
		},

		openDocumentFolderChildUploader: function (event) {
			var self = this;
			event.preventDefault();

			if (typeof window.Dropzone !== 'undefined' && $( 'div#bp-media-create-child-folder' ).length) {

				$( '#bp-media-create-child-folder' ).show();

				self.dropzone_obj = new Dropzone( 'div#media-uploader-child-folder', self.options );

				self.dropzone_obj.on(
					'sending',
					function (file, xhr, formData) {
						formData.append( 'action', 'document_document_upload' );
						formData.append( '_wpnonce', BP_Nouveau.nonces.media );
					}
				);

				self.dropzone_obj.on(
					'addedfile',
					function () {
						setTimeout(
							function () {
								if (self.dropzone_obj.getAcceptedFiles().length) {
									$( '#bp-media-uploader-modal-status-text' ).text( wp.i18n.sprintf( BP_Nouveau.media.i18n_strings.upload_status, self.dropzone_media.length, self.dropzone_obj.getAcceptedFiles().length ) ).show();
								}
							},
							1000
						);
					}
				);

				self.dropzone_obj.on(
					'error',
					function (file, response) {
						if (file.accepted) {
							if (typeof response !== 'undefined' && typeof response.data !== 'undefined' && typeof response.data.feedback !== 'undefined') {
								$( file.previewElement ).find( '.dz-error-message span' ).text( response.data.feedback );
							}
						} else {
							self.dropzone_obj.removeFile( file );
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
					function (file, response) {
						if (response.data.id) {
							file.id                  = response.id;
							response.data.uuid       = file.upload.uuid;
							response.data.menu_order = self.dropzone_media.length;
							response.data.folder_id  = self.album_id;
							response.data.group_id   = self.group_id;
							response.data.saved      = false;
							self.dropzone_media.push( response.data );
						}
						$( '#bp-media-document-add-more' ).show();
						$( '#bp-media-document-submit' ).show();
						$( '#bp-media-uploader-modal-title' ).text( BP_Nouveau.media.i18n_strings.uploading + '...' );
						$( '#bp-media-uploader-modal-status-text' ).text( wp.i18n.sprintf( BP_Nouveau.media.i18n_strings.upload_status, self.dropzone_media.length, self.dropzone_obj.getAcceptedFiles().length ) ).show();
					}
				);

				self.dropzone_obj.on(
					'removedfile',
					function (file) {
						if (self.dropzone_media.length) {
							for (var i in self.dropzone_media) {
								if (file.upload.uuid == self.dropzone_media[i].uuid) {

									if (typeof self.dropzone_media[i].saved !== 'undefined' && ! self.dropzone_media[i].saved) {
										self.removeAttachment( self.dropzone_media[i].id );
									}

									self.dropzone_media.splice( i, 1 );
									break;
								}
							}
						}
						if ( ! self.dropzone_obj.getAcceptedFiles().length) {
							$( '#bp-media-uploader-modal-status-text' ).text( '' );
							$( '#bp-media-document-add-more' ).hide();
							$( '#bp-media-document-submit' ).hide();
						} else {
							$( '#bp-media-uploader-modal-status-text' ).text( wp.i18n.sprintf( BP_Nouveau.media.i18n_strings.upload_status, self.dropzone_media.length, self.dropzone_obj.getAcceptedFiles().length ) ).show();
						}
					}
				);
			}
		},

		/**
		 * [openDocumentMove description]
		 * @param  {[type]} event [description]
		 * @return {[type]}       [description]
		 */
		openDocumentMove: function( event ) {
			event.preventDefault();

			var currentTarget;
			var currentTargetName = $(event.currentTarget).closest('.bb-activity-media-elem').find('.document-title').text();

			// For Activity Feed
			currentTarget = '#'+$(event.currentTarget).closest('li.activity-item').attr('id') + ' .bp-media-move-file';
			$(currentTarget).find('.bp-document-move').attr('id',$(event.currentTarget).closest('.document-activity').attr('data-id') );

			// Change if this is not from Activity Page
			if($(event.currentTarget).closest('.media-folder_items').length > 0) {
				/* jshint ignore:start */
				var currentTargetName = $(event.currentTarget).closest('.media-folder_items').find('.media-folder_name').text();
				/* jshint ignore:end */
				if($(event.currentTarget).hasClass('ac-document-move')){ // Check if target is file or folder
					currentTarget = '.'+$(event.currentTarget).closest('#media-folder-document-data-table').find('.bp-media-move-file').attr('class');
					$(currentTarget).find('.bp-document-move').attr('id',$(event.currentTarget).closest('.media-folder_items').attr('data-id') );
				}else{
					currentTarget = '.'+$(event.currentTarget).closest('#media-folder-document-data-table').find('.bp-media-move-folder').attr('class');
					$(currentTarget).find('.bp-folder-move').attr('id',$(event.currentTarget).closest('.media-folder_items').attr('data-id') );

				}
			}
			if(this.folderLocationUI){
				this.folderLocationUI(currentTarget);
			}

			$(currentTarget).find('.bb-model-header h4 .target_name').text(currentTargetName);

			$(currentTarget).show();
		},

		/**
		 * [closeDocumentMove description]
		 * @param  {[type]} event [description]
		 * @return {[type]}       [description]
		 */
		closeDocumentMove: function( event ) {
			event.preventDefault();
			var closest_parent = jQuery(event.currentTarget).closest('.has-folderlocationUI');
			if($(event.currentTarget).hasClass('ac-document-close-button')){
				$(event.currentTarget).closest('.bp-media-move-file').hide().find('.bp-document-move').attr('id','');

			}else{
				$(event.currentTarget).closest('.bp-media-move-folder').hide().find('.bp-folder-move').attr('id','');
			}

			closest_parent.find('.bp-document-move.loading').removeClass('loading');

			this.clearFolderLocationUI(event);

		},

		/**
		 * [renameDocument description]
		 * @param  {[type]} event [description]
		 * @return {[type]}       [description]
		 */
		renameDocument: function( event ) {

			var current_name = $(event.currentTarget).closest('.media-folder_items').find('.media-folder_name');
			var current_name_text = current_name.children('span').text();

			current_name.hide().siblings('.media-folder_name_edit_wrap').show().children('.media-folder_name_edit').val(current_name_text).focus().select();

		},

		/**
		 * [editPrivacyDocument description]
		 * @param  {[type]} event [description]
		 * @return {[type]}       [description]
		 */
		editPrivacyDocument: function( event ) {

			var current_privacy = $(event.currentTarget).closest('.media-folder_items').find('.media-folder_visibility');

			current_privacy.find('.media-folder_details__bottom span').hide().siblings('select').removeClass('hide');

		},

		/**
		 * [editPrivacyDocumentSubmit description]
		 * @param  {[type]} event [description]
		 * @return {[type]}       [description]
		 */
		editPrivacyDocumentSubmit: function( event ) {

			var current_privacy_select = $(event.currentTarget);

			if( current_privacy_select.data('mouseup') == 'true' ) {

				current_privacy_select.data('mouseup','false');

				//Make ajax call and onSuccess add this
				current_privacy_select.addClass('hide').siblings('span').show().text( current_privacy_select.find('option:selected').text() );

			} else {

				current_privacy_select.data('mouseup','true');

			}

		},

		/**
		 * [renameDocumentSubmit description]
		 * @param  {[type]} event [description]
		 * @return {[type]}       [description]
		 */
		renameDocumentSubmit: function( event ) {

			var document_edit 		   = $(event.currentTarget).closest('.media-folder_items').find('.media-folder_name_edit');
			var document_name 		   = $(event.currentTarget).closest('.media-folder_items').find('.media-folder_name > span');
			var document_id   		   = $(event.currentTarget).closest('.media-folder_items').find('.media-folder_name > i.media-document-id').attr( 'data-item-id' );
			var attachment_document_id = $(event.currentTarget).closest('.media-folder_items').find('.media-folder_name > i.media-document-attachment-id').attr( 'data-item-id' );
			var documentType		   = $(event.currentTarget).closest('.media-folder_items').find('.media-folder_name > i.media-document-type').attr( 'data-item-id' );
			var document_name_val 	   =  document_edit.val().trim(); // trim to remove whitespace around name
			var pattern 			   = /^[-\w^&'@{}[\],$=!#().%+~]+$/; // regex to find not supported characters
			var matches 			   = pattern.exec(document_name_val);
			var matchStatus 		   = Boolean(matches);

			if( matchStatus ){ // If any not supported character found add error class
				document_edit.removeClass('error');
			} else {
				document_edit.addClass('error');
			}

			if( $( event.currentTarget ).hasClass('name_edit_cancel') || event.keyCode == 27 ){

				document_edit.removeClass('error');
				document_edit.parent().hide().siblings('.media-folder_name').show();

			}

			if( $( event.currentTarget ).hasClass('name_edit_save') || event.keyCode == 13 ) {

				if( !matchStatus ){
					return; // prevent user to add not supported characters
				}

				document_edit.parent().addClass('submitting').append('<i class="animate-spin bb-icon-loader"></i>');

				// Make ajax call to save new file name here.
				//use variable 'document_name_val' as a new name while making an ajax call.
				$.ajax( {
					url : BP_Nouveau.ajaxurl,
					type : 'post',
					data : {
						action: 'document_update_file_name',
						document_id: document_id,
						attachment_document_id: attachment_document_id,
						document_type: documentType,
						name: document_name_val
					},success : function( response ) {
						document_name.text( response.data.response.title );
						document_edit.removeClass('submitting');
						document_edit.parent().find('.animate-spin').remove();
						document_edit.parent().hide().siblings('.media-folder_name').show();
					}
				});

			}

			event.preventDefault();

		},

		removeAttachment: function (id) {
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

		changeUploadModalTab: function (event) {
			event.preventDefault();

			var content_tab = $( event.currentTarget ).data( 'content' );
			$( '.bp-media-upload-tab-content' ).hide();
			$( '#' + content_tab ).show();
			this.current_tab = content_tab;
			$( event.currentTarget ).closest( '#bp-media-uploader' ).find( '.bp-media-upload-tab' ).removeClass( 'selected' );
			$( event.currentTarget ).addClass( 'selected' );
			this.toggleSubmitMediaButton();

			// replace dummy image with original image by faking scroll event to call bp.Nouveau.lazyLoad
			jQuery( window ).scroll();
		},

		openCreateAlbumModal: function (event) {
			event.preventDefault();

			this.openUploader( event );
			$( '#bp-media-create-album' ).show();
		},

		openCreateFolderModal: function (event) {
			event.preventDefault();
			this.openDocumentFolderUploader( event );
			this.folderLocationUI( '#bp-media-create-folder' );
			$( '#bp-media-create-folder' ).show();
		},

		openCreateFolderChildModal: function (event) {
			event.preventDefault();

			this.openDocumentFolderChildUploader( event );
			this.folderLocationUI( '#bp-media-create-child-folder' );
			$( '#bp-media-create-child-folder' ).show();
		},

		openEditFolderChildModal: function (event) {
			event.preventDefault();

			// this.openDocumentFolderChildUploader(event);
			this.folderLocationUI( '#bp-media-edit-child-folder' );
			$( '#bp-media-edit-child-folder' ).show();
		},

		folderLocationUI: function ( targetPopup ) {

			if ($( targetPopup ).find( '.bb-folder-destination' ).length > 0) {

				if ( ! $( targetPopup ).find( '.location-folder-list-wrap' ).hasClass( 'is_loaded' )) {

					$( document ).on(
						'click',
						targetPopup + ' .bb-folder-destination',
						function () {
							$( this ).parent().find( '.location-folder-list-wrap' ).slideToggle();
						}
					);

					$( targetPopup ).find( '.location-folder-list-wrap' ).addClass( 'is_loaded' );

					$( targetPopup ).find( '.location-folder-list li' ).each(
						function () {
							$( this ).children( 'ul' ).parent().addClass( 'has-ul' ).append( '<i class="bb-icon-angle-right sub-menu-anchor"></i>' );
						}
					);

					$( document ).on(
						'click',
						targetPopup + ' .location-folder-list li i',
						function () {
							$( this ).closest( '.location-folder-list-wrap' ).find( '.location-folder-title' ).text( $( this ).siblings( 'span' ).text() ).siblings( '.location-folder-back' ).css( 'display', 'inline-block' );
							$( this ).siblings( 'ul' ).show().siblings( 'span, i' ).hide().parent().siblings().hide();
							$( this ).closest( '.is_active' ).removeClass( 'is_active' );
							$( this ).parent().addClass( 'is_active' );
						}
					);

					$( document ).on(
						'click',
						targetPopup + ' .location-folder-back',
						function () {

							if ($( this ).siblings( '.location-folder-list' ).find( 'li.is_active' ).parent().hasClass( 'location-folder-list' )) {
								$( this ).siblings( '.location-folder-list' ).find( 'li.is_active' ).find( 'ul' ).hide().siblings( 'span, i' ).show().parent().removeClass( 'is_active' ).siblings().show();
								$( this ).siblings( '.location-folder-title' ).text( 'Documents' );
								$( this ).hide();
							} else {
								$( this ).siblings( '.location-folder-list' ).find( 'li.is_active' ).find( 'ul' ).hide().siblings( 'span, i' ).show().parent().removeClass( 'is_active' ).addClass( 'is_active_old' ).parent().parent().closest( '.has-ul' ).addClass( 'is_active' );
								$( this ).siblings( '.location-folder-list' ).find( 'li.is_active_old' ).siblings().show();
								$( this ).siblings( '.location-folder-list' ).find( 'li.is_active.is_active_old' ).removeClass( 'is_active is_active_old' );
								$( this ).siblings( '.location-folder-title' ).text( $( this ).parent().find( 'li.is_active>span' ).text() );
							}

							$( this ).closest( '.has-folderlocationUI' ).find( '.ac_document_search_folder' ).val( '' ).trigger( 'change' );

						}
					);

					$( targetPopup ).on(
						'click',
						' .location-folder-list li span',
						function () {

							if ($( this ).hasClass( 'selected' ) && !$( this ).hasClass( 'disabled' )) {
								$( this ).removeClass( 'selected' );
								$( this ).closest( '.has-folderlocationUI' ).find( '.bb-model-header h4 span.target_folder' ).text( '...' );
								$( this ).closest( '.location-folder-list-wrap-main' ).find( '.bb-folder-destination' ).val( '' );
								$( this ).closest( '.location-folder-list-wrap-main' ).find( '.bb-folder-selected-id' ).val( '' );
							} else {
								$( this ).closest( '.location-folder-list-wrap-main' ).find( '.location-folder-list li span' ).removeClass( 'selected' );
								$( this ).addClass( 'selected' );
								$( this ).closest( '.location-folder-list-wrap-main' ).find( '.bb-folder-destination' ).val( $( this ).text() );
								$( this ).closest( '.location-folder-list-wrap-main' ).find( '.bb-folder-selected-id' ).val( $( this ).parent().attr( 'data-id' ) );
								$( this ).closest( '.has-folderlocationUI' ).find( '.bb-model-header h4 span.target_folder' ).text( ' ' + $( this ).text() );
							}

						}
					);

					$( document ).on(
						'keyup change',
						targetPopup + ' .ac_document_search_folder',
						function () {

							var keyword = $( this ).val();
							if (keyword == '') {

								$( this ).closest( '.has-folderlocationUI' ).find( '.location-folder-list-wrap .location-folder-list' ).show().parent().siblings( '.ac_document_search_folder_list' ).hide();

							} else {
								$( this ).closest( '.has-folderlocationUI' ).find( '.ac_document_search_folder_list ul' ).html( ' ' );

								var find_folder_selector = '';
								if ($( this ).closest( '.has-folderlocationUI' ).find( '.location-folder-list-wrap ul.location-folder-list li.is_active' ).length > 0) {
									find_folder_selector = '.is_active li';
								}

								$( this ).closest( '.has-folderlocationUI' ).find( '.location-folder-list-wrap ul.location-folder-list li' + find_folder_selector ).each(
									function () {
										/* jshint ignore:start */
										if ($( this ).children( 'span' ).text().search( new RegExp( keyword, "i" ) ) >= 0) {
											$( this ).closest( '.has-folderlocationUI' ).find( '.ac_document_search_folder_list ul' ).append( '<li data-id="' + $( this ).attr( 'data-id' ) + '"><span>' + $( this ).children( 'span' ).text() + '</span></li>' );
										}
										/* jshint ignore:end */
									}
								);

								$( this ).closest( '.has-folderlocationUI' ).find( '.location-folder-list-wrap .location-folder-list' ).hide().parent().siblings( '.ac_document_search_folder_list' ).show();

								keyword = '';
							}

						}
					);

					if ( $( targetPopup ).find( '.location-folder-list li > span.disabled' ).length ) {
						$( targetPopup ).find('.target_folder').text( $( targetPopup ).find( '.location-folder-list li > span.disabled' ).text() );
					}

				}

			}
		},

		clearFolderLocationUI: function (event) {

			var closest_parent = jQuery( event.currentTarget ).closest( '.has-folderlocationUI' );
			if (closest_parent.length > 0) {

				closest_parent.find( '.location-folder-list-wrap-main .location-folder-list-wrap .location-folder-list li' ).each(
					function () {
						jQuery( this ).removeClass( 'is_active' ).find( 'span.selected:not(.disabled)' ).removeClass( 'selected' );
						jQuery( this ).find( 'ul' ).hide();
					}
				);

				closest_parent.find( '.location-folder-list-wrap-main .location-folder-list-wrap .location-folder-list li' ).show().children( 'span, i' ).show();
				closest_parent.find( '.location-folder-title' ).text( 'Documents' );
				closest_parent.find( '.location-folder-back' ).hide().closest( '.has-folderlocationUI' ).find( '.bb-folder-selected-id' ).val( '' );
				closest_parent.find( '.ac_document_search_folder' ).val( '' );
				closest_parent.find( '.bb-model-header h4 span' ).text( '...' );
				closest_parent.find( '.ac_document_search_folder_list ul' ).html( '' ).parent().hide().siblings( '.location-folder-list-wrap' ).find( '.location-folder-list' ).show();
			}
		},

		closeCreateAlbumModal: function (event) {
			event.preventDefault();

			this.closeUploader( event );
			$( '#bp-media-create-album' ).hide();
			$( '#bb-album-title' ).val( '' );
		},

		closeCreateFolderModal: function (event) {
			event.preventDefault();

			this.closeUploader( event );
			$( '#bp-media-create-folder, #bp-media-create-child-folder' ).hide();
			$( '#bb-album-title, #bb-album-child-title' ).val( '' );
			$( '#bp-media-create-folder .bb-folder-selected-id' ).val( '' );
			$( '#bp-media-create-folder .bb-field-steps-1, #bp-media-create-child-folder .bb-field-steps-1' ).show().siblings( '.bb-field-steps' ).hide();

		},

		closeEditFolderModal: function (event) {
			event.preventDefault();

			var currentPopup = $( event.currentTarget ).closest( '#bp-media-edit-child-folder' );

			$( '#bp-media-edit-child-folder' ).hide();
			currentPopup.find( '.bb-field-steps-1' ).show().siblings( '.bb-field-steps' ).hide();
			this.clearFolderLocationUI( event );

		},

		submitMedia: function (event) {
			var self = this, target = $( event.currentTarget ), data, privacy = $( '#bb-media-privacy' );
			event.preventDefault();

			if (target.hasClass( 'saving' )) {
				return false;
			}

			target.addClass( 'saving' );

			if (self.current_tab === 'bp-dropzone-content') {

				var post_content = $( '#bp-media-post-content' ).val();
				data             = {
					'action': 'media_save',
					'_wpnonce': BP_Nouveau.nonces.media,
					'medias': self.dropzone_media,
					'content': post_content,
					'album_id': self.album_id,
					'group_id': self.group_id,
					'privacy': privacy.val()
				};

				$( '#bp-dropzone-content .bp-feedback' ).remove();

				$.ajax(
					{
						type: 'POST',
						url: BP_Nouveau.ajaxurl,
						data: data,
						success: function (response) {
							if (response.success) {

								// It's the very first media, let's make sure the container can welcome it!
								if ( ! $( '#media-stream ul.media-list' ).length) {
									$( '#media-stream' ).html( $( '<ul></ul>' ).addClass( 'media-list item-list bp-list bb-photo-list grid' ) );
									$( '.bb-photos-actions' ).show();
								}

								// Prepend the activity.
								bp.Nouveau.inject( '#media-stream ul.media-list', response.data.media, 'prepend' );

								for (var i = 0; i < self.dropzone_media.length; i++) {
									self.dropzone_media[i].saved = true;
								}

								self.closeUploader( event );

								// replace dummy image with original image by faking scroll event to call bp.Nouveau.lazyLoad
								jQuery( window ).scroll();

							} else {
								$( '#bp-dropzone-content' ).prepend( response.data.feedback );
							}

							target.removeClass( 'saving' );
						}
					}
				);

			} else if (self.current_tab === 'bp-existing-media-content') {
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
						success: function (response) {
							if (response.success) {

								// It's the very first media, let's make sure the container can welcome it!
								if ( ! $( '#media-stream ul.media-list' ).length) {
									$( '#media-stream' ).html( $( '<ul></ul>' ).addClass( 'media-list item-list bp-list bb-photo-list grid' ) );
									$( '.bb-photos-actions' ).show();
								}

								// Prepend the activity.
								bp.Nouveau.inject( '#media-stream ul.media-list', response.data.media, 'prepend' );

								// remove selected media from existing media list
								$( '.bp-existing-media-wrap .bb-media-check-wrap [name="bb-media-select"]:checked' ).each(
									function () {
										if ($( this ).closest( 'li' ).data( 'id' ) === $( this ).val()) {
											$( this ).closest( 'li' ).remove();
										}
									}
								);

								// replace dummy image with original image by faking scroll event to call bp.Nouveau.lazyLoad
								jQuery( window ).scroll();

								self.closeUploader( event );
							} else {
								$( '#bp-existing-media-content' ).prepend( response.data.feedback );
							}

							target.removeClass( 'saving' );
						}
					}
				);
			} else if ( ! self.current_tab) {
				self.closeUploader( event );
				target.removeClass( 'saving' );
			}

		},

		submitDocumentMedia: function (event) {
			var self         = this, target = $( event.currentTarget ), data,
				currentPopup = $( event.currentTarget ).closest( '#bp-media-uploader' );
			event.preventDefault();

			if (target.hasClass( 'saving' )) {
				return false;
			}

			target.addClass( 'saving' );

			if (self.current_tab === 'bp-dropzone-content') {

				var post_content = $( '#bp-media-post-content' ).val();
				data             = {
					'action': 'document_document_save',
					'_wpnonce': BP_Nouveau.nonces.media,
					'medias': self.dropzone_media,
					'content': post_content,
					'folder_id': self.album_id,
					'group_id': self.group_id
				};

				$( '#bp-dropzone-content .bp-feedback' ).remove();

				$.ajax(
					{
						type: 'POST',
						url: BP_Nouveau.ajaxurl,
						data: data,
						success: function (response) {
							if (response.success) {

								// It's the very first media, let's make sure the container can welcome it!
								if ( ! $( '#media-stream div#media-folder-document-data-table' ).length) {
									$( '#media-stream' ).html( $( '<div></div>' ).addClass( 'display' ) );
									$( '#media-stream div' ).attr( 'id', 'media-folder-document-data-table' );
									$( '.bb-photos-actions' ).show();
								}

								// Prepend the activity.
								bp.Nouveau.inject( '#media-stream div#media-folder-document-data-table', response.data.media, 'prepend' );

								for (var i = 0; i < self.dropzone_media.length; i++) {
									self.dropzone_media[i].saved = true;
								}

								self.closeUploader( event );

								// replace dummy image with original image by faking scroll event to call bp.Nouveau.lazyLoad
								jQuery( window ).scroll();

							} else {
								$( '#bp-dropzone-content' ).prepend( response.data.feedback );
							}

							target.removeClass( 'saving' );

							if (currentPopup.find( '.bb-field-steps' ).length) {
								currentPopup.find( '.bb-field-steps-1' ).show().siblings( '.bb-field-steps-2' ).hide();
								currentPopup.find( '#bp-media-document-prev, #bp-media-document-submit' ).hide();
							}

						}
					}
				);

			} else if (self.current_tab === 'bp-existing-media-content') {
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
						success: function (response) {
							if (response.success) {

								// It's the very first media, let's make sure the container can welcome it!
								if ( ! $( '#media-stream ul.media-list' ).length) {
									$( '#media-stream' ).html( $( '<ul></ul>' ).addClass( 'media-list item-list bp-list bb-photo-list grid' ) );
									$( '.bb-photos-actions' ).show();
								}

								// Prepend the activity.
								bp.Nouveau.inject( '#media-stream ul.media-list', response.data.media, 'prepend' );

								// remove selected media from existing media list
								$( '.bp-existing-media-wrap .bb-media-check-wrap [name="bb-media-select"]:checked' ).each(
									function () {
										if ($( this ).closest( 'li' ).data( 'id' ) === $( this ).val()) {
											$( this ).closest( 'li' ).remove();
										}
									}
								);

								// replace dummy image with original image by faking scroll event to call bp.Nouveau.lazyLoad
								jQuery( window ).scroll();

								self.closeUploader( event );
							} else {
								$( '#bp-existing-media-content' ).prepend( response.data.feedback );
							}

							target.removeClass( 'saving' );

							if (currentPopup.find( '.bb-field-steps' ).length) {
								currentPopup.find( '.bb-field-steps-1' ).show().siblings( '.bb-field-steps-2' ).hide();
								currentPopup.find( '#bp-media-document-prev, #bp-media-document-submit' ).hide();
							}

						}
					}
				);
			} else if ( ! self.current_tab) {
				self.closeUploader( event );
				target.removeClass( 'saving' );
			}

		},

		saveAlbum: function (event) {
			var target  = $( event.currentTarget ), self = this, title = $( '#bb-album-title' ),
				privacy = $( '#bb-album-privacy' );
			event.preventDefault();

			if ($.trim( title.val() ) === '') {
				title.addClass( 'error' );
				return false;
			} else {
				title.removeClass( 'error' );
			}

			if ( ! self.group_id && $.trim( privacy.val() ) === '') {
				privacy.addClass( 'error' );
				return false;
			} else {
				privacy.removeClass( 'error' );
			}

			target.prop( 'disabled', true );

			var data = {
				'action': 'media_album_save',
				'_wpnonce': BP_Nouveau.nonces.media,
				'title': title.val(),
				'medias': self.dropzone_media,
				'privacy': privacy.val()
			};

			if (self.album_id) {
				data.album_id = self.album_id;
			}

			if (self.group_id) {
				data.group_id = self.group_id;
			}

			// remove all feedback erros from the DOM
			$( '.bb-single-album-header .bp-feedback' ).remove();
			$( '#boss-media-create-album-popup .bp-feedback' ).remove();

			$.ajax(
				{
					type: 'POST',
					url: BP_Nouveau.ajaxurl,
					data: data,
					success: function (response) {
						setTimeout(
							function () {
								target.prop( 'disabled', false );
							},
							500
						);
						if (response.success) {
							if (self.album_id) {
								$( '#bp-single-album-title' ).text( title.val() );
								$( '#bb-album-privacy' ).val( privacy.val() );
								self.cancelEditAlbumTitle( event );
							} else {
								$( '#buddypress .bb-albums-list' ).prepend( response.data.album );
								// self.closeCreateAlbumModal(event);
								window.location.href = response.data.redirect_url;
							}
						} else {
							if (self.album_id) {
								$( '#bp-media-single-album' ).prepend( response.data.feedback );
							} else {
								$( '#boss-media-create-album-popup .bb-model-header' ).after( response.data.feedback );
							}
						}
					}
				}
			);

		},

		saveFolder: function (event) {
			var target  = $( event.currentTarget ), self = this, title = $( '#bb-album-title' ),
				privacy = $( '#bb-folder-privacy' );
			event.preventDefault();

			if ($.trim( title.val() ) === '') {
				title.addClass( 'error' );
				return false;
			} else {
				title.removeClass( 'error' );
			}

			var parent = $( '#boss-media-create-album-popup .bb-folder-selected-id' ).val();

			if ( ! self.group_id && $.trim( privacy.val() ) === '') {
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
				'medias': self.dropzone_media,
				'privacy': privacy.val(),
				'parent': parent
			};

			if (self.album_id) {
				data.album_id = self.album_id;
			}

			if (self.group_id) {
				data.group_id = self.group_id;
			}

			// remove all feedback erros from the DOM
			$( '.bb-single-album-header .bp-feedback' ).remove();
			$( '#boss-media-create-album-popup .bp-feedback' ).remove();

			$.ajax(
				{
					type: 'POST',
					url: BP_Nouveau.ajaxurl,
					data: data,
					success: function (response) {
						setTimeout(
							function () {
								target.prop( 'disabled', false );
							},
							500
						);
						if (response.success) {
							if (self.album_id) {
								$( '#bp-single-album-title' ).text( title.val() );
								$( '#bb-folder-privacy' ).val( privacy.val() );
								self.cancelEditAlbumTitle( event );
							} else {
								$( '#buddypress .bb-albums-list' ).prepend( response.data.album );
								// self.closeCreateAlbumModal(event);
								window.location.href = response.data.redirect_url;
							}
						} else {
							if (self.album_id) {
								$( '#bp-media-single-album' ).prepend( response.data.feedback );
							} else {
								$( '#boss-media-create-album-popup .bb-model-header' ).after( response.data.feedback );
							}
						}
					}
				}
			);

		},

		saveChildFolder: function (event) {
			var target  = $( event.currentTarget ), self = this, title = $( '#bb-album-child-title' ),
				privacy = $( '#bb-folder-child-privacy' ), parent = $( '#parent_id' );
			event.preventDefault();

			if ($.trim( title.val() ) === '') {
				title.addClass( 'error' );
				return false;
			} else {
				title.removeClass( 'error' );
			}

			if ( ! self.group_id && $.trim( privacy.val() ) === '') {
				privacy.addClass( 'error' );
				return false;
			} else {
				privacy.removeClass( 'error' );
			}

			target.prop( 'disabled', true );

			var data = {
				'action': 'document_folder_save',
				'_wpnonce': BP_Nouveau.nonces.media,
				'title': title.val(),
				'medias': self.dropzone_media,
				'privacy': privacy.val(),
				'parent': parent.val()
			};

			if (self.album_id) {
				data.folder_id = self.album_id;
			}

			if (self.group_id) {
				data.group_id = self.group_id;
			}

			// remove all feedback erros from the DOM
			$( '.bb-single-album-header .bp-feedback' ).remove();
			$( '#boss-media-create-album-popup .bp-feedback' ).remove();

			$.ajax(
				{
					type: 'POST',
					url: BP_Nouveau.ajaxurl,
					data: data,
					success: function (response) {
						setTimeout(
							function () {
								target.prop( 'disabled', false );
							},
							500
						);
						if (response.success) {
							window.location.href = response.data.redirect_url;
						} else {
							if (self.album_id) {
								$( '#bp-media-single-album' ).prepend( response.data.feedback );
							} else {
								$( '#boss-media-create-album-popup .bb-model-header' ).after( response.data.feedback );
							}
						}
					}
				}
			);

		},

		renameChildFolder: function (event) {

			var target  = $( event.currentTarget ), self = this,
				title   = $( '#bp-media-edit-child-folder #bb-album-child-title' ),
				privacy = $( '#bp-media-edit-child-folder #bb-folder-privacy' ),
				parent  = $( '#bp-media-edit-child-folder #parent_id' ),
				moveTo  = $( '#bp-media-edit-child-folder .bb-folder-selected-id' );
			// event.preventDefault();

			if ($.trim( title.val() ) === '') {
				title.addClass( 'error' );
				return false;
			} else {
				title.removeClass( 'error' );
			}

			if ( ! self.group_id && $.trim( privacy.val() ) === '') {
				privacy.addClass( 'error' );
				return false;
			} else {
				privacy.removeClass( 'error' );
			}

			target.prop( 'disabled', true );

			var data = {
				'action': 'document_edit_folder',
				'_wpnonce': BP_Nouveau.nonces.media,
				'title': title.val(),
				'privacy': privacy.val(),
				'parent': parent.val(),
				'moveTo': moveTo.val()
			};

			if (self.album_id) {
				data.folder_id = self.album_id;
			}

			if (self.group_id) {
				data.group_id = self.group_id;
			}

			// remove all feedback erros from the DOM
			$( '.bb-single-album-header .bp-feedback' ).remove();
			$( '#boss-media-create-album-popup .bp-feedback' ).remove();

			$.ajax(
				{
					type: 'POST',
					url: BP_Nouveau.ajaxurl,
					data: data,
					success: function (response) {
						setTimeout(
							function () {
								target.prop( 'disabled', false );
							},
							500
						);
						if (response.success) {
							window.location.href = response.data.redirect_url;
						} else {
							if (self.album_id) {
								$( '#bp-media-single-album' ).prepend( response.data.feedback );
							} else {
								$( '#boss-media-create-album-popup .bb-model-header' ).after( response.data.feedback );
							}
						}
					}
				}
			);

			console.log( 'click 1 1' );

		},

		deleteAlbum: function (event) {
			event.preventDefault();

			if ( ! this.album_id) {
				return false;
			}

			if ( ! confirm( BP_Nouveau.media.i18n_strings.album_delete_confirm )) {
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
					success: function (response) {
						if (response.success) {
							window.location.href = response.data.redirect_url;
						} else {
							alert( BP_Nouveau.media.i18n_strings.album_delete_error );
							$( event.currentTarget ).prop( 'disabled', false );
						}
					}
				}
			);

		},

		deleteFolder: function (event) {
			event.preventDefault();
			if ( ! this.album_id) {
				return false;
			}

			if ( ! confirm( BP_Nouveau.media.i18n_strings.folder_delete_confirm )) {
				return false;
			}

			$( event.currentTarget ).prop( 'disabled', true );

			var data = {
				'action': 'media_folder_delete',
				'_wpnonce': BP_Nouveau.nonces.media,
				'album_id': this.album_id,
				'group_id': this.group_id
			};

			$.ajax(
				{
					type: 'POST',
					url: BP_Nouveau.ajaxurl,
					data: data,
					success: function (response) {
						if (response.success) {
							window.location.href = response.data.redirect_url;
						} else {
							alert( BP_Nouveau.media.i18n_strings.folder_delete_error );
							$( event.currentTarget ).prop( 'disabled', false );
						}
					}
				}
			);

		},

		addMediaIdsToGroupMessagesForm: function() {
			var self = this;
			if( $( '#bp_group_messages_media' ).length ) {
				$( '#bp_group_messages_media' ).val( JSON.stringify( self.dropzone_media ) );
			}
		},

		/**
		 * [injectQuery description]
		 *
		 * @param  {[type]} event [description]
		 * @return {[type]}       [description]
		 */
		injectMedias: function (event) {
			var store = bp.Nouveau.getStorage( 'bp-media' ),
				scope = store.scope || null, filter = store.filter || null;

			if ($( event.currentTarget ).hasClass( 'load-more' )) {
				var next_page = (Number( this.current_page ) * 1) + 1, self = this, search_terms = '';

				// Stop event propagation
				event.preventDefault();

				$( event.currentTarget ).find( 'a' ).first().addClass( 'loading' );

				if ($( '#buddypress .dir-search input[type=search]' ).length) {
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
					function (response) {
						if (true === response.success) {
							$( event.currentTarget ).remove();

							// Update the current page
							self.current_page = next_page;

							// replace dummy image with original image by faking scroll event to call bp.Nouveau.lazyLoad
							jQuery( window ).scroll();
						}
					}
				);
			}
		},

		injectDocuments: function (event) {

			var store = bp.Nouveau.getStorage( 'bp-media' ),
				scope = store.scope || null, filter = store.filter || null, currentTarget = $( event.currentTarget );

			if (currentTarget.hasClass( 'load-more' )) {
				var next_page = (Number( this.current_page ) * 1) + 1, self = this, search_terms = '';

				// Stop event propagation
				event.preventDefault();

				currentTarget.find( 'a' ).first().addClass( 'loading' );

				if ($( '#buddypress .dir-search input[type=search]' ).length) {
					search_terms = $( '#buddypress .dir-search input[type=search]' ).val();
				}

				bp.Nouveau.objectRequest(
					{
						object: 'document',
						scope: scope,
						filter: filter,
						search_terms: search_terms,
						page: next_page,
						method: 'append',
						target: '#buddypress [data-bp-list] div#media-folder-document-data-table'
					}
				).done(
					function (response) {
						if (true === response.success) {
							currentTarget.parent( '.pager' ).remove();

							// Update the current page
							self.current_page = next_page;

							// replace dummy image with original image by faking scroll event to call bp.Nouveau.lazyLoad
							jQuery( window ).scroll();
						}
					}
				);
			}
		},

		/* jshint ignore:start */
		sortDocuments: function (event) {

			var sortTarget = $( event.currentTarget ), sortArg = sortTarget.data( 'target' ), search_terms = '', order_by = 'date_created', sort = '', next_page = 1;
			var currentFilter = sortTarget.attr('class');
			switch (sortArg) {
				case 'name':
					order_by = 'title';
					break;
				case 'modified':
					order_by = 'date_created';
					break;
				case 'visibility':
					order_by = 'privacy';
					break;
			}

			sortTarget.hasClass( 'asce' ) ? sortTarget.removeClass( 'asce' ) : sortTarget.addClass( 'asce' );
			var sort = sortTarget.hasClass( 'asce' ) ? 'DESC' : 'ASC';


			var store = bp.Nouveau.getStorage( 'bp-document' ),
				scope = store.scope || null, filter = store.filter || null, currentTarget = $( event.currentTarget );


			if ($( '#buddypress .dir-search input[type=search]' ).length) {
				search_terms = $( '#buddypress .dir-search input[type=search]' ).val();
			}

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
			).done(
				function (response) {
					setTimeout( function(){
						currentFilterTarget = '.'+currentFilter.replace(' ','.');
						$( currentFilterTarget ).hasClass('asce') ? $( currentFilterTarget ).removeClass('asce') : $( currentFilterTarget ).addClass('asce');
					},300);
					
				}
			);

		},
		/* jshint ignore:end */

		documentPopupNavigate: function (event) {

			event.preventDefault();

			var target = $( event.currentTarget ), currentSlide = target.closest( '.bb-field-steps' );

			// Check if this is documnet parent or child folder page
			var titleField = target.closest( '.bp-document-listing' ).length == 0 ? '#bb-album-child-title' : '#bb-album-title';

			if (target.closest( '.document-options' ).length) { // Check if this is /document page
				titleField = '#bb-album-title';
			}

			if (target.hasClass( 'bb-field-steps-next' ) && currentSlide.find( titleField ).val().trim() == '') {
				currentSlide.find( titleField ).addClass( 'error' );
				return;
			} else {
				currentSlide.find( titleField ).removeClass( 'error' );
			}

			currentSlide.slideUp( 200 ).siblings( '.bb-field-steps' ).slideDown( 200 );
		},

		uploadDocumentNavigate: function (event) {

			event.preventDefault();

			var target = $( event.currentTarget ), currentPopup = $( target ).closest( '#bp-media-uploader' );

			if ($( target ).hasClass( 'bb-field-uploader-next' )) {
				currentPopup.find( '.bb-field-steps-1' ).slideUp( 200 ).siblings( '.bb-field-steps' ).slideDown( 200 );
				currentPopup.find( '#bp-media-document-submit, #bp-media-document-prev' ).show();
				currentPopup.find( '#bp-media-document-add-more' ).hide();
			} else {
				$( target ).hide();
				currentPopup.find( '#bp-media-document-submit, #bp-media-document-prev' ).hide();
				currentPopup.find( '#bp-media-document-add-more' ).show();
				currentPopup.find( '.bb-field-steps-2' ).slideUp( 200 ).siblings( '.bb-field-steps' ).slideDown( 200 );
			}

		},

		/**
		 * [appendQuery description]
		 *
		 * @param  {[type]} event [description]
		 * @return {[type]}       [description]
		 */
		appendMedia: function (event) {
			var store = bp.Nouveau.getStorage( 'bp-media' ),
				scope = store.scope || null, filter = store.filter || null;

			if ($( event.currentTarget ).hasClass( 'load-more' )) {
				var next_page = (Number( this.current_page_existing_media ) * 1) + 1, self = this, search_terms = '';

				// Stop event propagation
				event.preventDefault();

				$( event.currentTarget ).find( 'a' ).first().addClass( 'loading' );

				if ($( '#buddypress .dir-search input[type=search]' ).length) {
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
					function (response) {
						if (true === response.success) {
							$( event.currentTarget ).remove();

							// Update the current page
							self.current_page_existing_media = next_page;
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
		appendAlbums: function (event) {
			var next_page = (Number( this.current_page_albums ) * 1) + 1, self = this;

			// Stop event propagation
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
					success: function (response) {
						if (true === response.success) {
							$( event.currentTarget ).remove();
							$( '#albums-dir-list ul.bb-albums-list' ).fadeOut(
								100,
								function () {
									$( '#albums-dir-list ul.bb-albums-list' ).append( response.data.albums );
									$( this ).fadeIn( 100 );
								}
							);
							// Update the current page
							self.current_page_albums = next_page;
						}
					}
				}
			);
		},

		toggleSubmitMediaButton: function () {
			var submit_media_button = $( '#bp-media-submit' ), add_more_button = $( '#bp-media-add-more' );
			if (this.current_tab === 'bp-dropzone-content') {
				if (this.dropzone_obj.getAcceptedFiles().length) {
					submit_media_button.show();
					add_more_button.show();
				} else {
					submit_media_button.hide();
					add_more_button.hide();
				}
			} else if (this.current_tab === 'bp-existing-media-content') {
				if ($( '.bp-existing-media-wrap .bb-media-check-wrap [name="bb-media-select"]:checked' ).length) {
					submit_media_button.show();
				} else {
					submit_media_button.hide();
				}
				add_more_button.hide();
			}
		},

		// play gif
		playVideo: function (event) {
			event.preventDefault();
			var video   = $( event.currentTarget ).find( 'video' ).get( 0 ),
				$button = $( event.currentTarget ).find( '.gif-play-button' );
			if (video.paused == true) {
				// Play the video
				video.play();

				// Update the button text to 'Pause'
				$button.hide();
			} else {
				// Pause the video
				video.pause();

				// Update the button text to 'Play'
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
					$button   = $( this ).find( '.gif-play-button' );

					if ($( this ).is( ':in-viewport' )) {
						// Play the video
						video.play();

						// Update the button text to 'Pause'
						$button.hide();
					} else {
						// Pause the video
						video.pause();

						// Update the button text to 'Play'
						$button.show();
					}
				}
			);
		},

		/**
		 * File action Button
		 */
		fileActionButton: function (event) {

			if ($( event.currentTarget ).parent().hasClass( 'download_file' )) {
				return;
			}

			event.preventDefault();
			$( event.currentTarget ).closest( '.media-folder_items' ).toggleClass( 'is-visible' ).siblings( '.media-folder_items' ).removeClass( 'is-visible' );
		},
		
		/**
		 * File action Copy Download Link
		 */
		copyDownloadLink: function (event) {

			var currentTarget = event.currentTarget, currentTargetCopy = 'document_copy_link';
			$('body').append('<textarea style="position:absolute;opacity:0;" id="' + currentTargetCopy + '"></textarea>');
			$('#'+currentTargetCopy).val( $(currentTarget).attr('href') );
			$('#'+currentTargetCopy).select();
			document.execCommand('copy');
			$('#'+currentTargetCopy).remove();

			event.preventDefault();
		},

		/**
		 * File Activity action Button
		 */
		fileActivityActionButton: function (event) {
			event.preventDefault();

			$( event.currentTarget ).closest( '.bb-activity-media-elem' ).toggleClass( 'is-visible' ).siblings().removeClass( 'is-visible' ).closest( '.activity-item' ).siblings().find( '.bb-activity-media-elem' ).removeClass( 'is-visible' );
			if (event.currentTarget.tagName.toLowerCase() == 'a' && ! $( event.currentTarget ).hasClass( 'document-action_more' )) {
				$( event.currentTarget ).closest( '.bb-activity-media-elem' ).removeClass( 'is-visible' );
			}
		},

		/**
		 * File Activity action Toggle
		 */
		toggleFileActivityActionButton: function (event) {
			var element;

			event = event || window.event;

			if (event.target) {
				element = event.target;
			} else if (event.srcElement) {
				element = event.srcElement;
			}

			if (element.nodeType === 3) {
				element = element.parentNode;
			}

			if (event.altKey === true || event.metaKey === true) {
				return event;
			}

			// if privacy dropdown items, return
			if ($( element ).hasClass( 'document-action_more' ) || $( element ).parent().hasClass( 'document-action_more' ) || $( element ).hasClass( 'media-folder_action__anchor' ) || $( element ).parent().hasClass( 'media-folder_action__anchor' )) {
				return event;
			}

			$( '.bb-activity-media-elem.is-visible' ).removeClass( 'is-visible' );
			$( '.media-folder_items.is-visible' ).removeClass( 'is-visible' );

		},

		/**
		 * Text File Expand
		 */
		expandCodePreview: function (event) {
			event.preventDefault();
			$( event.currentTarget ).closest( '.document-activity' ).addClass( 'code-full-view' );
		},

		/**
		 * Text File Collapse
		 */
		collapseCodePreview: function (event) {
			event.preventDefault();
			$( event.currentTarget ).closest( '.document-activity' ).removeClass( 'code-full-view' );
		},

		/**
		 * Text File Activity Preview
		 */
		documentCodeMirror: function(){
			$('.document-text:not(.loaded)').each(function(){
				var $this = $(this);
				var data_extension = $this.attr('data-extension');
				var fileMode = $this.attr('data-extension');
				if(data_extension == 'html' || data_extension == 'htm'){ // HTML file need specific mode.
					fileMode = 'text/html';
				}
				if(data_extension == 'js'){ //mode not needed for javascript file.
					/* jshint ignore:start */
					var myCodeMirror = CodeMirror($this[0], {
						value: $this.find('.document-text-file-data-hidden').val(),
						lineNumbers: true,
						theme: 'default',
						readOnly: true,
						lineWrapping: true,
					});
					/* jshint ignore:end */
				}else{
					/* jshint ignore:start */
					var myCodeMirror = CodeMirror($this[0], {
						value: $this.find('.document-text-file-data-hidden').val(),
						mode:  fileMode,
						lineNumbers: true,
						theme: 'default',
						readOnly: true,
						lineWrapping: true,
					});
					/* jshint ignore:end */
				}


				$this.addClass('loaded');
				if($this.parent().height() > 150){ //If file is bigger add controls to Expand/Collapse.
					$this.closest('.document-text-wrap').addClass('is_large');
				}

			});
			if(!$('.bb-activity-media-elem.document-activity').closest('.activity-inner').hasClass('documemt-activity')){
				$('.bb-activity-media-elem.document-activity').closest('.activity-content').addClass('documemt-activity');
			}
		},
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

			// Listen to events ("Add hooks!")
			this.addListeners();

		},

		/**
		 * [setupGlobals description]
		 *
		 * @return {[type]} [description]
		 */
		setupGlobals: function () {

			this.medias        = [];
			this.current_media = false;
			this.current_index = 0;
			this.is_open       = false;
			this.nextLink      = $( '.bb-next-media' );
			this.previousLink  = $( '.bb-prev-media' );
			this.activity_ajax = false;
			this.group_id      = typeof BP_Nouveau.media.group_id !== 'undefined' ? BP_Nouveau.media.group_id : false;

		},

		/**
		 * [addListeners description]
		 */
		addListeners: function () {

			$( document ).on( 'click', '.bb-open-media-theatre', this.openTheatre.bind( this ) );
			$( document ).on( 'click', '.bb-open-document-theatre', this.openDocumentTheatre.bind( this ) );
			$( document ).on( 'click', '.bb-close-media-theatre', this.closeTheatre.bind( this ) );
			$( document ).on( 'click', '.bb-close-document-theatre', this.closeDocumentTheatre.bind( this ) );
			$( document ).on( 'click', '.bb-prev-media', this.previous.bind( this ) );
			$( document ).on( 'click', '.bb-next-media', this.next.bind( this ) );
			$( document ).on( 'bp_activity_ajax_delete_request', this.activityDeleted.bind( this ) );
			$( document ).on( 'click', '#bb-media-model-container .media-privacy>li', this.mediaPrivacyChange.bind( this ) );
			$( document ).on( 'click', '#bb-media-model-container .bb-media-section span.privacy', bp.Nouveau, this.togglePrivacyDropdown.bind( this ) );
			$( document ).click( this.togglePopupDropdown );

		},

		documentClick: function (e) {
			var self = this;
			if (self.is_open) {
				var target = e.target;
				var model  = document.getElementById( 'bb-media-model-container' );
				if (model != null && ! model.contains( target ) && document.body.contains( target )) {
					self.closeTheatre( e );
				}
			}
		},

		checkPressedKey: function (e) {
			var self = this;
			e        = e || window.event;
			switch (e.keyCode) {
				case 27: // escape key
					self.closeTheatre( e );
					break;
				case 37: // left arrow key code
					self.previous( e );
					break;
				case 39: // right arrow key code
					self.next( e );
					break;
			}
		},

		checkPressedKeyDocuments: function (e) {
			e = e || window.event;	
			if( e.keyCode == 27 ) {	
				bp.Nouveau.Media.Theatre.closeDocumentTheatre( e );	
			}	
		},

		openTheatre: function (event) {
			event.preventDefault();
			var target = $( event.currentTarget ), id, self = this;

			if (target.closest( '#bp-existing-media-content' ).length) {
				return false;
			}

			self.setupGlobals();
			self.setMedias( target );

			id = target.data( 'id' );
			self.setCurrentMedia( id );
			self.showMedia();
			self.navigationCommands();
			self.getActivity();

			$( '.bb-media-model-wrapper' ).show();
			self.is_open = true;

			document.addEventListener( 'keyup', self.checkPressedKey.bind( self ) );
			// document.addEventListener( 'click', self.documentClick.bind(self) );
		},

		openDocumentTheatre: function (event) {	
			event.preventDefault();	
			var target = $( event.currentTarget );	
			var self = this;	
			var media_elements = $( target ).closest( '.bb-media-container' ).length ?  $( target ).closest( '.bb-media-container' ).find( '.document-theatre' ) : $( target ).closest( '.directory.document' ).find( '.document-theatre' );
			if( target.attr('data-extension') == 'css' || target.attr('data-extension') == 'txt' || target.attr('data-extension') == 'js' || target.attr('data-extension') == 'html' || target.attr('data-extension') == 'htm' || target.attr('data-extension') == 'csv' ) {	
				//Show Document	
				$.get(target.attr('data-text-preview'), function(data) {	
					media_elements.find( '.bb-media-section' ).html( '<h3>'+ target.text() +'</h3><div class="document-text"><textarea class="document-text-file-data-hidden"></textarea></div>' );	
					media_elements.find( '.bb-media-section .document-text' ).attr( 'data-extension', target.attr( 'data-extension' ) );	
					media_elements.find( '.bb-media-section .document-text textarea' ).html( data.replace('n','' ) );	
						
					bp.Nouveau.Media.documentCodeMirror();	
				});	
			} else {	
					
				media_elements.find( '.bb-media-section' ).html( '<h3>'+ target.text() +'</h3><div class="img-section"> <img src="'+target.attr('data-preview')+'" /> </div>' );	
			}	
			var id = target.closest('.media-folder_items').data( 'activity-id' );
			bp.Nouveau.Media.Theatre.getDocumentActivity(id);	
				
			self.is_open = true;	
				
			$( '.document-theatre' ).show();	
			document.addEventListener( 'keyup', bp.Nouveau.Media.Theatre.checkPressedKeyDocuments( event ) );	
		},

		getDocumentActivity: function (activity_id) {

			if ( !activity_id ) {
				$('.bb-document-theater .bb-media-info-section').hide();
				return;
			}

			$('.bb-document-theater .bb-media-info-section').show();

			var self = this;

			$( '.bb-media-info-section .activity-list' ).addClass( 'loading' ).html( '<i class="dashicons dashicons-update animate-spin"></i>' );
			
			self.activity_ajax = $.ajax(
				{
					type: 'POST',
					url: BP_Nouveau.ajaxurl,
					data: {
						action: 'media_get_activity',
						id: activity_id,
						nonce: BP_Nouveau.nonces.media
					},
					success: function (response) {
						if (response.success) {
							$( '.bb-media-info-section .activity-list' ).removeClass( 'loading' ).html( response.data.activity );
							$( '.bb-media-info-section' ).show();
							// replace dummy image with original image by faking scroll event to call bp.Nouveau.lazyLoad
							jQuery( window ).scroll();
						}
					}
				}
			);
		},

		closeTheatre: function (event) {
			event.preventDefault();
			var self = this;

			$( '.bb-media-model-wrapper' ).hide();
			self.is_open = false;

			document.removeEventListener( 'keyup', self.checkPressedKey.bind( self ) );
			// document.removeEventListener( 'click', self.documentClick.bind(self) );
		},

		closeDocumentTheatre: function (event) {	
			event.preventDefault();	
			var self = this;	
			var target = $( event.currentTarget );	
				
			var media_elements = $( target ).closest( '.bb-media-container' ).find( '.document-theatre' );	
			media_elements.find('.bb-media-section').html('');	
			media_elements.hide();	
			self.is_open = false;	
			document.removeEventListener( 'keyup', bp.Nouveau.Media.Theatre.checkPressedKeyDocuments( event ) );	
		},

		setMedias: function (target) {
			var media_elements = $( '.bb-open-media-theatre' ), i = 0, self = this;

			// check if on activity page, load only activity media in theatre
			if ($( 'body' ).hasClass( 'activity' )) {
				media_elements = $( target ).closest( '.bb-activity-media-wrap' ).find( '.bb-open-media-theatre' );
			}

			if (typeof media_elements !== 'undefined') {
				self.medias = [];
				for (i = 0; i < media_elements.length; i++) {
					var media_element = $( media_elements[i] );
					if ( ! media_element.closest( '#bp-existing-media-content' ).length) {

						var m = {
							id: media_element.data( 'id' ),
							attachment: media_element.data( 'attachment-full' ),
							activity_id: media_element.data( 'activity-id' ),
							privacy: media_element.data( 'privacy' ),
							parent_activity_id: media_element.data( 'parent-activity-id' ),
							album_id: media_element.data( 'album-id' ),
							group_id: media_element.data( 'group-id' ),
							is_forum: false
						};

						if (media_element.closest( '.forums-media-wrap' ).length) {
							m.is_forum = true;
						}

						if (typeof m.privacy !== 'undefined' && m.privacy == 'message') {
							m.is_message = true;
						} else {
							m.is_message = false;
						}

						self.medias.push( m );
					}
				}
			}
		},

		setCurrentMedia: function (id) {
			var self = this, i = 0;
			for (i = 0; i < self.medias.length; i++) {
				if (id === self.medias[i].id) {
					self.current_media = self.medias[i];
					self.current_index = i;
					break;
				}
			}
		},

		showMedia: function () {
			var self = this;

			if (typeof self.current_media === 'undefined') {
				return false;
			}

			// refresh img
			$( '.bb-media-model-wrapper .bb-media-section' ).find( 'img' ).attr( 'src', self.current_media.attachment + '?' + new Date().getTime() );

			// privacy
			var media_privacy_wrap = $( '.bb-media-section .bb-media-privacy-wrap' );

			if (media_privacy_wrap.length) {
				media_privacy_wrap.show();
				media_privacy_wrap.find( 'ul.media-privacy li' ).removeClass( 'selected' );
				media_privacy_wrap.find( '.bp-tooltip' ).attr( 'data-bp-tooltip', '' );
				var selected_media_privacy_elem = media_privacy_wrap.find( 'ul.media-privacy' ).find( 'li[data-value=' + self.current_media.privacy + ']' );
				selected_media_privacy_elem.addClass( 'selected' );
				media_privacy_wrap.find( '.bp-tooltip' ).attr( 'data-bp-tooltip', selected_media_privacy_elem.text() );
				media_privacy_wrap.find( '.privacy' ).removeClass( 'public' ).removeClass( 'loggedin' ).removeClass( 'onlyme' ).removeClass( 'friends' ).addClass( self.current_media.privacy );

				// hide privacy setting of media if activity is present
				if ((typeof BP_Nouveau.activity !== 'undefined' &&
					typeof self.current_media.activity_id !== 'undefined' &&
					self.current_media.activity_id != 0) ||
					self.group_id ||
					self.current_media.is_forum ||
					self.current_media.group_id ||
					self.current_media.album_id ||
					self.current_media.is_message
				) {
					media_privacy_wrap.hide();
				}
			}

			// update navigation
			self.navigationCommands();
		},

		next: function (event) {
			event.preventDefault();
			var self = this, activity_id;
			if (typeof self.medias[self.current_index + 1] !== 'undefined') {
				self.current_index = self.current_index + 1;
				activity_id        = self.current_media.activity_id;
				self.current_media = self.medias[self.current_index];
				self.showMedia();
				if (activity_id != self.current_media.activity_id) {
					self.getActivity();
				}
			} else {
				self.nextLink.hide();
			}
		},

		previous: function (event) {
			event.preventDefault();
			var self = this, activity_id;
			if (typeof self.medias[self.current_index - 1] !== 'undefined') {
				self.current_index = self.current_index - 1;
				activity_id        = self.current_media.activity_id;
				self.current_media = self.medias[self.current_index];
				self.showMedia();
				if (activity_id != self.current_media.activity_id) {
					self.getActivity();
				}
			} else {
				self.previousLink.hide();
			}
		},

		navigationCommands: function () {
			var self = this;
			if (self.current_index == 0 && self.current_index != (self.medias.length - 1)) {
				self.previousLink.hide();
				self.nextLink.show();
			} else if (self.current_index == 0 && self.current_index == (self.medias.length - 1)) {
				self.previousLink.hide();
				self.nextLink.hide();
			} else if (self.current_index == (self.medias.length - 1)) {
				self.previousLink.show();
				self.nextLink.hide();
			} else {
				self.previousLink.show();
				self.nextLink.show();
			}
		},

		getActivity: function () {
			var self = this;

			$( '.bb-media-info-section .activity-list' ).addClass( 'loading' ).html( '<i class="dashicons dashicons-update animate-spin"></i>' );

			if (typeof BP_Nouveau.activity !== 'undefined' &&
				self.current_media &&
				typeof self.current_media.activity_id !== 'undefined' &&
				self.current_media.activity_id != 0 &&
				! self.current_media.is_forum
			) {

				if (self.activity_ajax != false) {
					self.activity_ajax.abort();
				}

				self.activity_ajax = $.ajax(
					{
						type: 'POST',
						url: BP_Nouveau.ajaxurl,
						data: {
							action: 'media_get_activity',
							id: self.current_media.activity_id,
							nonce: BP_Nouveau.nonces.media
						},
						success: function (response) {
							if (response.success) {
								$( '.bb-media-info-section .activity-list' ).removeClass( 'loading' ).html( response.data.activity );
								$( '.bb-media-info-section' ).show();

								// replace dummy image with original image by faking scroll event to call bp.Nouveau.lazyLoad
								jQuery( window ).scroll();
							}
						}
					}
				);
			} else {
				$( '.bb-media-info-section' ).hide();
			}
		},

		activityDeleted: function (event, data) {
			var self = this, i = 0;
			if (self.is_open && typeof data !== 'undefined' && data.action === 'delete_activity' && self.current_media.activity_id == data.id) {

				$( document ).find( '[data-bp-list="media"] .bb-open-media-theatre[data-id="' + self.current_media.id + '"]' ).closest( 'li' ).remove();
				$( document ).find( '[data-bp-list="activity"] .bb-open-media-theatre[data-id="' + self.current_media.id + '"]' ).closest( '.bb-activity-media-elem' ).remove();

				for (i = 0; i < self.medias.length; i++) {
					if (self.medias[i].activity_id == data.id) {
						self.medias.splice( i, 1 );
						break;
					}
				}

				if (self.current_index == 0 && self.current_index != (self.medias.length)) {
					self.current_index = -1;
					self.next( event );
				} else if (self.current_index == 0 && self.current_index == (self.medias.length)) {
					self.closeTheatre( event );
				} else if (self.current_index == (self.medias.length)) {
					self.previous( event );
				} else {
					self.current_index = -1;
					self.next( event );
				}
			}
		},

		/**
		 * [togglePopupDropdown description]
		 *
		 * @param  {[type]} event [description]
		 * @return {[type]}       [description]
		 */
		togglePopupDropdown: function (event) {
			var element;

			event = event || window.event;

			if (event.target) {
				element = event.target;
			} else if (event.srcElement) {
				element = event.srcElement;
			}

			if (element.nodeType === 3) {
				element = element.parentNode;
			}

			if (event.altKey === true || event.metaKey === true) {
				return event;
			}

			// if privacy dropdown items, return
			if ($( element ).hasClass( 'privacy-wrap' ) || $( element ).parent().hasClass( 'privacy-wrap' )) {
				return event;
			}

			$( 'ul.media-privacy' ).removeClass( 'bb-open' );
		},

		togglePrivacyDropdown: function (event) {
			var target = $( event.target );

			// Stop event propagation
			event.preventDefault();

			target.closest( '.bb-media-privacy-wrap' ).find( '.media-privacy' ).toggleClass( 'bb-open' );
		},

		mediaPrivacyChange: function (event) {
			var target = $( event.target ), self = this, privacy = target.data( 'value' ), older_privacy = 'public';

			event.preventDefault();

			if (target.hasClass( 'selected' )) {
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
	};

	// Launch BP Nouveau Media
	bp.Nouveau.Media.start();

	// Launch BP Nouveau Media Theatre
	bp.Nouveau.Media.Theatre.start();

})( bp, jQuery );
