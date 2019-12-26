/* jshint browser: true */
/* global bp, BP_Nouveau, JSON, Dropzone */
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

			// Init current page
			this.current_page   = 1;
			this.current_page_existing_media   = 1;
			this.current_page_albums   = 1;
			this.current_tab   = $('body').hasClass('single-topic') || $('body').hasClass('single-forum') ? false : 'bp-dropzone-content';

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

			if ( $( '#bp-media-uploader' ).hasClass( 'bp-media-document-uploader' ) ) {
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

			this.dropzone_obj = [];
			this.dropzone_media = [];
			this.album_id = typeof BP_Nouveau.media.album_id !== 'undefined' ? BP_Nouveau.media.album_id : false;
			this.group_id = typeof BP_Nouveau.media.group_id !== 'undefined' ? BP_Nouveau.media.group_id : false;
			this.bbp_is_reply_edit = typeof window.BP_Forums_Nouveau !== 'undefined' && typeof window.BP_Forums_Nouveau.media.bbp_is_reply_edit !== 'undefined' && window.BP_Forums_Nouveau.media.bbp_is_reply_edit;
			this.bbp_is_topic_edit = typeof window.BP_Forums_Nouveau !== 'undefined' && typeof window.BP_Forums_Nouveau.media.bbp_is_topic_edit !== 'undefined' && window.BP_Forums_Nouveau.media.bbp_is_topic_edit;
			this.bbp_is_forum_edit = typeof window.BP_Forums_Nouveau !== 'undefined' && typeof window.BP_Forums_Nouveau.media.bbp_is_forum_edit !== 'undefined' && window.BP_Forums_Nouveau.media.bbp_is_forum_edit;
			this.bbp_reply_edit_media = typeof window.BP_Forums_Nouveau !== 'undefined' && typeof window.BP_Forums_Nouveau.media.reply_edit_media !== 'undefined' ? window.BP_Forums_Nouveau.media.reply_edit_media : [];
			this.bbp_reply_edit_document = typeof window.BP_Forums_Nouveau !== 'undefined' && typeof window.BP_Forums_Nouveau.media.reply_edit_document !== 'undefined' ? window.BP_Forums_Nouveau.media.reply_edit_document : [];
			this.bbp_topic_edit_media = typeof window.BP_Forums_Nouveau !== 'undefined' && typeof window.BP_Forums_Nouveau.media.topic_edit_media !== 'undefined' ? window.BP_Forums_Nouveau.media.topic_edit_media : [];
			this.bbp_topic_edit_document = typeof window.BP_Forums_Nouveau !== 'undefined' && typeof window.BP_Forums_Nouveau.media.topic_edit_document !== 'undefined' ? window.BP_Forums_Nouveau.media.topic_edit_document : [];
			this.bbp_forum_edit_media = typeof window.BP_Forums_Nouveau !== 'undefined' && typeof window.BP_Forums_Nouveau.media.forum_edit_media !== 'undefined' ? window.BP_Forums_Nouveau.media.forum_edit_media : [];
			this.bbp_forum_edit_document = typeof window.BP_Forums_Nouveau !== 'undefined' && typeof window.BP_Forums_Nouveau.media.forum_edit_document !== 'undefined' ? window.BP_Forums_Nouveau.media.forum_edit_document : [];
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
		},

		/**
		 * [addListeners description]
		 */
		addListeners: function() {

			$( '.bp-nouveau' ).on( 'click', '#bp-add-media', this.openUploader.bind( this ) );
			$( '.bp-nouveau' ).on( 'click', '#bp-add-document', this.openDocumentUploader.bind( this ) );
			$( '.bp-nouveau' ).on( 'click', '#bp-media-submit', this.submitMedia.bind( this ) );
			$( '.bp-nouveau' ).on( 'click', '#bp-media-document-submit', this.submitDocumentMedia.bind( this ) );
			$( '.bp-nouveau' ).on( 'click', '#bp-media-uploader-close', this.closeUploader.bind( this ) );
			$( '.bp-nouveau' ).on( 'click', '#bb-delete-media', this.deleteMedia.bind( this ) );
			$( '.bp-nouveau' ).on( 'click', '#bb-select-deselect-all-media', this.toggleSelectAllMedia.bind( this ) );
			$( '#buddypress [data-bp-list="media"]' ).on('bp_ajax_request',this.bp_ajax_media_request);

			$('#media-folder-document-data-table').DataTable();

			// albums
			$( '.bp-nouveau' ).on( 'click', '#bb-create-album', this.openCreateAlbumModal.bind( this ) );
			$( '.bp-nouveau' ).on( 'click', '#bb-create-folder', this.openCreateFolderModal.bind( this ) );
			$( '.bp-nouveau' ).on( 'click', '#bb-create-folder-child', this.openCreateFolderChildModal.bind( this ) );
			$( '.bp-nouveau' ).on( 'click', '#bp-edit-folder-open', this.openEditFolderChildModal.bind( this ) );
			
			$( '.bp-nouveau' ).on( 'click', '#bp-media-create-album-submit', this.saveAlbum.bind( this ) );
			$( '.bp-nouveau' ).on( 'click', '#bp-media-create-folder-submit', this.saveFolder.bind( this ) );
			$( '.bp-nouveau' ).on( 'click', '#bp-media-create-child-folder-submit', this.saveChildFolder.bind( this ) );
			$( '.bp-nouveau' ).on( 'click', '#bp-media-create-album-close', this.closeCreateAlbumModal.bind( this ) );
			$( '.bp-nouveau' ).on( 'click', '#bp-media-create-folder-close', this.closeCreateFolderModal.bind( this ) );
			$( '.bp-nouveau' ).on( 'click', '#bp-media-edit-folder-close', this.closeEditFolderModal.bind( this ) );

			$( '.bp-nouveau' ).on( 'click', '#bp-media-add-more', this.triggerDropzoneSelectFileDialog.bind( this ) );
			$( '.bp-nouveau' ).on( 'click', '#bp-media-document-add-more', this.triggerDropzoneSelectFileDialog.bind( this ) );

			$( '#bp-media-uploader' ).on( 'click', '.bp-media-upload-tab', this.changeUploadModalTab.bind( this ) );

			// Fetch Media
			$( '.bp-nouveau [data-bp-list="media"]' ).on( 'click', 'li.load-more', this.injectMedias.bind( this ) );
			$( '.bp-nouveau [data-bp-media-type="document"]' ).on( 'click', '.dt-more-container.load-more', this.injectDocuments.bind( this ) );
			$( '.bp-nouveau #albums-dir-list' ).on( 'click', 'li.load-more', this.appendAlbums.bind( this ) );
			$( '.bp-existing-media-wrap' ).on( 'click', 'li.load-more', this.appendMedia.bind( this ) );
			$( '.bp-nouveau' ).on( 'change', '.bb-media-check-wrap [name="bb-media-select"]', this.addSelectedClassToWrapper.bind( this ) );
			$( '.bp-existing-media-wrap' ).on( 'change', '.bb-media-check-wrap [name="bb-media-select"]', this.toggleSubmitMediaButton.bind( this ) );

			//single album
			$( '.bp-nouveau' ).on( 'click', '#bp-edit-album-title', this.editAlbumTitle.bind( this ) );
			$( '.bp-nouveau' ).on( 'click', '#bp-edit-folder-title', this.editFolderTitle.bind( this ) );
			$( '.bp-nouveau' ).on( 'click', '#bp-cancel-edit-album-title', this.cancelEditAlbumTitle.bind( this ) );
			$( '.bp-nouveau' ).on( 'click', '#bp-save-album-title', this.saveAlbum.bind( this ) );
			$( '.bp-nouveau' ).on( 'click', '#bp-save-folder-title', this.saveFolder.bind( this ) );
			$( '.bp-nouveau' ).on( 'change', '#bp-media-single-album select#bb-album-privacy', this.saveAlbum.bind( this ) );
			$( '.bp-nouveau' ).on( 'change', '#bp-media-single-folder select#bb-folder-privacy', this.saveFolder.bind( this ) );
			$( '.bp-nouveau' ).on( 'click', '#bb-delete-album', this.deleteAlbum.bind( this ) );
			$( '.bp-nouveau' ).on( 'click', '#bb-delete-folder', this.deleteFolder.bind( this ) );

			//forums
			$( document ).on( 'click', '#forums-media-button', this.openForumsUploader.bind( this ) );
			$( document ).on( 'click', '#forums-document-button', this.openForumsDocumentUploader.bind( this ) );
			$( document ).on( 'click', '#forums-gif-button', this.toggleGifSelector.bind( this ) );
			$( document ).find( 'form #whats-new-toolbar, .forum form #whats-new-toolbar' ).on( 'keyup', '.search-query-input', this.searchGif.bind( this ) );
			$( document ).find( 'form #whats-new-toolbar, .forum form #whats-new-toolbar' ).on( 'click', '.found-media-item', this.selectGif.bind( this ) );
			$( document ).find( 'form #whats-new-toolbar .gif-search-results, .forum form #whats-new-toolbar .gif-search-results' ).scroll( this.loadMoreGif.bind( this ) );
			$( document ).find( 'form #whats-new-attachments .forums-attached-gif-container, .forum form #whats-new-attachments .forums-attached-gif-container' ).on( 'click', '.gif-image-remove', this.removeSelectedGif.bind( this ) );

			$(document).on('click', '.gif-image-container', this.playVideo.bind( this ) );

			//Documents
			$( document ).on( 'click', '.bb-media-container .media-folder_action__anchor, .bb-media-container  .media-folder_action__list li a', this.fileActionButton.bind( this ) );
			$( document ).on( 'click', '.bb-activity-media-elem.document-activity .document-action-wrap .document-action_more', this.fileActivityActionButton.bind( this ) );


			// Gifs autoplay
			if ( !_.isUndefined( BP_Nouveau.media.gif_api_key ) ) {
				window.addEventListener( 'scroll', this.autoPlayGifVideos, false );
				window.addEventListener( 'resize', this.autoPlayGifVideos, false );
			}

			if ( ( this.bbp_is_reply_edit || this.bbp_is_topic_edit || this.bbp_is_forum_edit ) &&
				( this.bbp_reply_edit_media.length || this.bbp_topic_edit_media.length || this.bbp_forum_edit_media.length ) ) {
				$('#forums-media-button').trigger('click');
			}

			if ( ( this.bbp_is_reply_edit || this.bbp_is_topic_edit || this.bbp_is_forum_edit ) &&
				( this.bbp_reply_edit_document.length || this.bbp_topic_edit_document.length || this.bbp_forum_edit_document.length ) ) {
				$('#forums-document-button').trigger('click');
			}

			if ( ( this.bbp_is_reply_edit || this.bbp_is_topic_edit || this.bbp_is_forum_edit ) &&
				( Object.keys( this.bbp_reply_edit_gif_data ).length || Object.keys( this.bbp_topic_edit_gif_data ).length || Object.keys( this.bbp_forum_edit_gif_data ).length ) ) {
				this.editGifPreview();
			}

		},

		bp_ajax_media_request: function(event,data) {
			if ( typeof data !== 'undefined' && typeof data.response.scopes.personal !== 'undefined' && data.response.scopes.personal === 0 ) {
				$('.bb-photos-actions').hide();
			}
		},

		addSelectedClassToWrapper: function(event) {
			var target = event.currentTarget;
			if ( $(target).is(':checked') ) {
				$(target).closest('.bb-media-check-wrap').find('.bp-tooltip').attr('data-bp-tooltip',BP_Nouveau.media.i18n_strings.unselect);
				$(target).closest('.bb-photo-thumb').addClass('selected');
			} else {
				$(target).closest('.bb-photo-thumb').removeClass('selected');
				$(target).closest('.bb-media-check-wrap').find('.bp-tooltip').attr('data-bp-tooltip',BP_Nouveau.media.i18n_strings.select);

				if ( $( '.bp-nouveau #bb-select-deselect-all-media' ).hasClass('selected') ) {
					$( '.bp-nouveau #bb-select-deselect-all-media' ).removeClass('selected');
				}
			}
		},

		deleteMedia: function(event) {
			var target = $(event.currentTarget);
			event.preventDefault();

			var media = [];
			$('#buddypress').find('.media-list:not(.existing-media-list)').find('.bb-media-check-wrap [name="bb-media-select"]:checked').each(function(){
				$(this).closest('.bb-photo-thumb').addClass('loading deleting');
				media.push($(this).val());
			});

			if ( media.length == 0 ) {
				return false;
			}

			target.prop('disabled',true);
			$('#buddypress .media-list .bp-feedback').remove();

			var data = {
				'action': 'media_delete',
				'_wpnonce': BP_Nouveau.nonces.media,
				'media': media
			};

			$.ajax({
				type: 'POST',
				url: BP_Nouveau.ajaxurl,
				data: data,
				success: function (response) {
					setTimeout(function () {
						target.prop('disabled',false);
					},500);
					if (response.success) {

						$('#buddypress').find('.media-list:not(.existing-media-list)').find('.bb-media-check-wrap [name="bb-media-select"]:checked').each(function(){
							$(this).closest('li').remove();
						});

						if ( $('#buddypress').find('.media-list:not(.existing-media-list)').find('li:not(.load-more)').length == 0 ) {
							$('.bb-photos-actions').hide();
							var feedback = '<aside class="bp-feedback bp-messages info">\n' +
								'\t<span class="bp-icon" aria-hidden="true"></span>\n' +
								'\t<p>'+BP_Nouveau.media.i18n_strings.no_photos_found+'</p>\n' +
								'\t</aside>';
							$('#buddypress [data-bp-list="media"]').html(feedback);
						}
					} else {
						$('#buddypress .media-list').prepend(response.data.feedback);
					}

				}
			});
		},

		toggleSelectAllMedia: function(event) {
			event.preventDefault();

			if ( $(event.currentTarget).hasClass('selected') ) {
				$(event.currentTarget).data('bp-tooltip',BP_Nouveau.media.i18n_strings.selectall);
				this.deselectAllMedia(event);
			} else {
				$(event.currentTarget).data('bp-tooltip',BP_Nouveau.media.i18n_strings.unselectall);
				this.selectAllMedia(event);
			}

			$(event.currentTarget).toggleClass('selected');
		},

		selectAllMedia: function(event) {
			event.preventDefault();

			$('#buddypress').find('.media-list:not(.existing-media-list)').find('.bb-media-check-wrap [name="bb-media-select"]').each(function(){
				$(this).prop('checked',true);
				$(this).closest('.bb-photo-thumb').addClass('selected');
				$(this).closest('.bb-media-check-wrap').find('.bp-tooltip').attr('data-bp-tooltip',BP_Nouveau.media.i18n_strings.unselect);
			});
		},

		deselectAllMedia: function(event) {
			event.preventDefault();

			$('#buddypress').find('.media-list:not(.existing-media-list)').find('.bb-media-check-wrap [name="bb-media-select"]').each(function(){
				$(this).prop('checked',false);
				$(this).closest('.bb-photo-thumb').removeClass('selected');
				$(this).closest('.bb-media-check-wrap').find('.bp-tooltip').attr('data-bp-tooltip',BP_Nouveau.media.i18n_strings.select);
			});
		},

		editAlbumTitle: function(event) {
			event.preventDefault();

			$('#bb-album-title').show();
			$('#bp-save-album-title').show();
			$('#bp-cancel-edit-album-title').show();
			$('#bp-edit-album-title').hide();
			$('#bp-media-single-album #bp-single-album-title').hide();
		},

		editFolderTitle: function(event) {
			event.preventDefault();

			$('#bb-album-title').show();
			$('#bp-save-folder-title').show();
			$('#bp-cancel-edit-album-title').show();
			$('#bp-edit-folder-title').hide();
			$('#bp-media-single-album #bp-single-album-title').hide();
		},

		cancelEditAlbumTitle: function(event) {
			event.preventDefault();

			$('#bb-album-title').hide();
			$('#bp-save-album-title,#bp-save-folder-title').hide();
			$('#bp-cancel-edit-album-title').hide();
			$('#bp-edit-album-title,#bp-edit-folder-title').show();
			$('#bp-media-single-album #bp-single-album-title').show();
		},

		triggerDropzoneSelectFileDialog: function() {
			var self = this;

			self.dropzone_obj.hiddenFileInput.click();
		},

		closeUploader: function(event) {
			event.preventDefault();

			$('#bp-media-uploader').hide();
			$('#bp-media-add-more').hide();
			$('#bp-media-uploader-modal-title').text(BP_Nouveau.media.i18n_strings.upload);
			$('#bp-media-uploader-modal-status-text').text('');
			this.dropzone_obj.destroy();
			this.dropzone_media = [];

		},

		loadMoreGif: function(e) {
			var el = e.target, self = this;

			var $forums_gif_container = $(e.target).closest('form').find('.forums-attached-gif-container');
			var gif_container_key = $forums_gif_container.data('key');
			self.gif_container_key = gif_container_key;

			if ( el.scrollTop + el.offsetHeight >= el.scrollHeight &&  ! $forums_gif_container.hasClass('loading') ) {
				if ( self.gif_data[gif_container_key].total_count > 0 && self.gif_data[gif_container_key].offset <= self.gif_data[gif_container_key].total_count ) {
					var params = {
							offset: self.gif_data[gif_container_key].offset,
							fmt: 'json',
							limit: self.gif_data[gif_container_key].limit
						};

					$forums_gif_container.addClass('loading');
					var request = null;
					if ( _.isNull( self.gif_data[gif_container_key].q ) ) {
						request = self.giphy.trending( params, _.bind( self.loadMoreGifResponse, self ) );
					} else {
						request = self.giphy.search( _.extend( { q: self.gif_data[gif_container_key].q }, params ), _.bind( self.loadMoreGifResponse, self ) );
					}

					self.gif_data[gif_container_key].requests.push( request );
					self.gif_data[gif_container_key].offset = self.gif_data[gif_container_key].offset + self.gif_data[gif_container_key].limit;
				}
			}
		},

		loadMoreGifResponse: function( response ) {
			var self = this, i = 0;
			$('div.forums-attached-gif-container[data-key="' + self.gif_container_key + '"]').removeClass('loading');
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
					self.gif_data[self.gif_container_key].data.push(response.data[i]);
				}

				$('div.forums-attached-gif-container[data-key="' + self.gif_container_key + '"]').closest('form').find('.gif-search-results-list').append(li_html);
			}

			if ( typeof response.pagination !== 'undefined' && typeof response.pagination.total_count !== 'undefined' ) {
				self.gif_data[self.gif_container_key].total_count = response.pagination.total_count;
			}
		},

		editGifPreview: function() {
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

			$('#whats-new-attachments .forums-attached-gif-container')[0].style.backgroundImage = 'url(' + gif_data.images.fixed_width.url + ')';
			$('#whats-new-attachments .forums-attached-gif-container')[0].style.backgroundSize = 'contain';
			$('#whats-new-attachments .forums-attached-gif-container')[0].style.height = gif_data.images.original.height + 'px';
			$('#whats-new-attachments .forums-attached-gif-container')[0].style.width = gif_data.images.original.width + 'px';
			$('#whats-new-attachments .forums-attached-gif-container').find('.gif-image-container img').attr('src',gif_data.images.original.url);

			$('#whats-new-attachments .forums-attached-gif-container').removeClass('closed');
			if( $('#bbp_media_gif').length ) {
				$('#bbp_media_gif').val(JSON.stringify(gif_data));
			}
		},

		selectGif: function(e) {
			var self = this, i = 0, target = $( e.currentTarget ), gif_container = target.closest('form').find('.forums-attached-gif-container');
			e.preventDefault();

			gif_container.parent().removeClass( 'open' );
			var gif_container_key = gif_container.data( 'key' );
			if ( typeof self.gif_data[gif_container_key] !== 'undefined' && typeof self.gif_data[gif_container_key].data !== 'undefined' && self.gif_data[gif_container_key].data.length ) {
				for( i = 0; i < self.gif_data[gif_container_key].data.length; i++ ) {
					if ( self.gif_data[gif_container_key].data[i].id == e.currentTarget.dataset.id ) {

						target.closest('form').find('#whats-new-attachments .forums-attached-gif-container')[0].style.backgroundImage = 'url(' + self.gif_data[gif_container_key].data[i].images.fixed_width.url + ')';
						target.closest('form').find('#whats-new-attachments .forums-attached-gif-container')[0].style.backgroundSize = 'contain';
						target.closest('form').find('#whats-new-attachments .forums-attached-gif-container')[0].style.height = self.gif_data[gif_container_key].data[i].images.original.height + 'px';
						target.closest('form').find('#whats-new-attachments .forums-attached-gif-container')[0].style.width = self.gif_data[gif_container_key].data[i].images.original.width + 'px';

						target.closest('form').find('#whats-new-attachments .forums-attached-gif-container').find('.gif-image-container img').attr('src',self.gif_data[gif_container_key].data[i].images.original.url);
						target.closest('form').find('#whats-new-attachments .forums-attached-gif-container').removeClass('closed');
						if( target.closest('form').find('#bbp_media_gif').length ) {
							target.closest('form').find('#bbp_media_gif').val(JSON.stringify(self.gif_data[gif_container_key].data[i]));
						}
						break;
					}
				}
			}
		},

		removeSelectedGif: function(e) {
			e.preventDefault();
			this.resetForumsGifComponent(e);
		},

		resetForumsGifComponent: function(e) {
			var self = this, target = $( e.target );
			target.closest('form').find('#whats-new-toolbar .forums-attached-gif-container').parent().removeClass( 'open' );
			target.closest('form').find('#whats-new-toolbar #forums-gif-button').removeClass('active');

			var $forums_attached_gif_container = target.closest('form').find('#whats-new-attachments .forums-attached-gif-container');
			if ( $forums_attached_gif_container ) {
				$forums_attached_gif_container.addClass('closed');
				$forums_attached_gif_container.find('.gif-image-container img').attr('src', '');
				$forums_attached_gif_container[0].style = '';
			}

			if( target.closest('form').find('#bbp_media_gif').length ) {
				target.closest('form').find('#bbp_media_gif').val('');
			}
		},

		searchGif: function(e) {
			var self = this;

			if ( self.gif_timeout != null ) {
				clearTimeout( this.gif_timeout );
			}

			self.gif_timeout = setTimeout( function() {
				self.gif_timeout = null;
				self.searchGifRequest( e, e.target.value );
			}, 1000 );
		},

		searchGifRequest: function( e ) {
			var self = this, i = 0;

			var $forums_gif_container = $(e.target).closest('form').find('.forums-attached-gif-container');
			$forums_gif_container.addClass('loading');
			var gif_container_key = $forums_gif_container.data( 'key' );

			self.clearGifRequests( gif_container_key );

			self.gif_data[gif_container_key].q = e.target.value;
			self.gif_data[gif_container_key].offset = 0;

			var request = self.giphy.search( {
					q: self.gif_data[gif_container_key].q,
					offset: self.gif_data[gif_container_key].offset,
					fmt: 'json',
					limit: self.gif_data[gif_container_key].limit
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
							self.gif_data[gif_container_key].data.push(response.data[i]);
						}

						$(e.target).closest('.forums-attached-gif-container').find('.gif-search-results-list').append(li_html);
					}

					if ( typeof response.pagination !== 'undefined' && typeof response.pagination.total_count !== 'undefined' ) {
						self.gif_data[gif_container_key].total_count = response.pagination.total_count;
					}
					$(e.target).closest('.forums-attached-gif-container').removeClass('loading');
				}
			);

			self.gif_data[gif_container_key].requests.push( request );
			self.gif_data[gif_container_key].offset = self.gif_data[gif_container_key].offset + self.gif_data[gif_container_key].limit;
		},

		clearGifRequests: function(gif_container_key) {
			var self = this;

			if ( typeof self.gif_data[gif_container_key] !== 'undefined' && typeof self.gif_data[gif_container_key].requests !== 'undefined' ) {
				for ( var i = 0; i < self.gif_data[gif_container_key].requests.length; i++ ) {
					self.gif_data[gif_container_key].requests[i].abort();
				}

				self.gif_data[gif_container_key].requests = [];
				self.gif_data.splice( gif_container_key, 1 );
			}
		},

		toggleGifSelector: function( event ) {
			var self = this, target = $(event.currentTarget), gif_search_dropdown = target.closest('form').find('.gif-media-search-dropdown'), i = 0;
			event.preventDefault();

			if ( typeof window.Giphy !== 'undefined' && typeof BP_Nouveau.media.gif_api_key !== 'undefined' ) {
				self.giphy = new window.Giphy(BP_Nouveau.media.gif_api_key);

				var $forums_attached_gif_container = target.closest('form').find('.forums-attached-gif-container');
				$forums_attached_gif_container.addClass('loading');
				var gif_container_key = $forums_attached_gif_container.data( 'key' );

				self.clearGifRequests(gif_container_key);

				self.gif_data[gif_container_key] = {
					q : null,
					offset : 0,
					limit : 20,
					requests : [],
					total_count : 0,
					data : []
				};

				var request = self.giphy.trending( {
					offset: self.gif_data[gif_container_key].offset,
					fmt: 'json',
					limit: self.gif_data[gif_container_key].limit
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
							self.gif_data[gif_container_key].data.push(response.data[i]);
						}

						target.closest('form').find('.gif-search-results-list').append(li_html);
					}

					if ( typeof response.pagination !== 'undefined' && typeof response.pagination.total_count !== 'undefined' ) {
						self.gif_data[gif_container_key].total_count = response.pagination.total_count;
					}

					target.closest('form').find('.forums-attached-gif-container').removeClass('loading');
				});

				self.gif_data[gif_container_key].requests.push( request );
				self.gif_data[gif_container_key].offset = self.gif_data[gif_container_key].offset + self.gif_data[gif_container_key].limit;
			}

			gif_search_dropdown.toggleClass('open');
			target.toggleClass('active');
			var $forums_media_container = target.closest('form').find( '#forums-post-media-uploader' );
			if ( $forums_media_container.length ) {
				self.resetForumsMediaComponent($forums_media_container.data('key'));
			}
		},

		resetForumsMediaComponent: function( dropzone_container_key ) {
			var self = this;

			if ( typeof dropzone_container_key !== 'undefined' ) {

				if (typeof self.dropzone_obj[dropzone_container_key] !== 'undefined') {
					self.dropzone_obj[dropzone_container_key].destroy();
					self.dropzone_obj.splice(dropzone_container_key, 1);
					self.dropzone_media.splice(dropzone_container_key, 1);
				}

				$('div#forums-post-media-uploader[data-key="' + dropzone_container_key + '"]').html('');
				$('div#forums-post-media-uploader[data-key="' + dropzone_container_key + '"]').addClass('closed').removeClass('open');
			}
		},

		resetForumsDocumentComponent: function( dropzone_container_key ) {
			var self = this;

			if ( typeof dropzone_container_key !== 'undefined' ) {

				if (typeof self.dropzone_obj[dropzone_container_key] !== 'undefined') {
					self.dropzone_obj[dropzone_container_key].destroy();
					self.dropzone_obj.splice(dropzone_container_key, 1);
					self.dropzone_media.splice(dropzone_container_key, 1);
				}

				$('div#forums-post-document-uploader[data-key="' + dropzone_container_key + '"]').html('');
				$('div#forums-post-document-uploader[data-key="' + dropzone_container_key + '"]').addClass('closed').removeClass('open');
			}
		},

		openForumsUploader: function(event) {
			var self = this, target = $( event.currentTarget ), dropzone_container = target.closest( 'form' ).find( '#forums-post-media-uploader' ), edit_medias = [];
			event.preventDefault();

			if ( typeof window.Dropzone !== 'undefined' && dropzone_container.length ) {

				var dropzone_obj_key = dropzone_container.data('key');

				if ( dropzone_container.hasClass('closed') ) {

					// init dropzone
					self.dropzone_obj[dropzone_obj_key] = new Dropzone(dropzone_container[0], self.options);
					self.dropzone_media[dropzone_obj_key] = [];

					self.dropzone_obj[dropzone_obj_key].on('sending', function(file, xhr, formData) {
						formData.append('action', 'media_upload');
						formData.append('_wpnonce', BP_Nouveau.nonces.media);
					});

					self.dropzone_obj[dropzone_obj_key].on('error', function(file,response) {
						if ( file.accepted ) {
							if ( typeof response !== 'undefined' && typeof response.data !== 'undefined' && typeof response.data.feedback !== 'undefined' ) {
								$(file.previewElement).find('.dz-error-message span').text(response.data.feedback);
							}
						} else {
							self.dropzone_obj[dropzone_obj_key].removeFile(file);
						}
					});

					self.dropzone_obj[dropzone_obj_key].on('success', function(file, response) {
						if ( response.data.id ) {
							file.id = response.id;
							response.data.uuid = file.upload.uuid;
							response.data.menu_order = $(file.previewElement).closest('.dropzone').find(file.previewElement).index() - 1;
							response.data.album_id = self.album_id;
							response.data.group_id = self.group_id;
							response.data.saved    = false;
							self.dropzone_media[dropzone_obj_key].push( response.data );
							self.addMediaIdsToForumsForm(dropzone_container);
						}
					});

					self.dropzone_obj[dropzone_obj_key].on('removedfile', function(file) {
						if ( self.dropzone_media[dropzone_obj_key].length ) {
							for ( var i in self.dropzone_media[dropzone_obj_key] ) {
								if ( file.upload.uuid == self.dropzone_media[dropzone_obj_key][i].uuid  ) {

									if ( ( ! this.bbp_is_reply_edit && ! this.bbp_is_topic_edit && ! this.bbp_is_forum_edit ) && typeof self.dropzone_media[dropzone_obj_key][i].saved !== 'undefined' && ! self.dropzone_media[dropzone_obj_key][i].saved ) {
										self.removeAttachment(self.dropzone_media[dropzone_obj_key][i].id);
									}

									self.dropzone_media[dropzone_obj_key].splice( i, 1 );
									self.addMediaIdsToForumsForm(dropzone_container);
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
								self.dropzone_media[dropzone_obj_key].push({
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

								self.dropzone_obj[dropzone_obj_key].files.push(mock_file);
								self.dropzone_obj[dropzone_obj_key].emit('addedfile', mock_file);
								self.createThumbnailFromUrl(mock_file,dropzone_container);
							}
							self.addMediaIdsToForumsForm(dropzone_container);
						}
					}

					// container class to open close
					dropzone_container.removeClass('closed').addClass('open');

					// reset gif component
					self.resetForumsGifComponent(event);

				} else {
					self.resetForumsMediaComponent( dropzone_obj_key );
				}

			}

		},

		openForumsDocumentUploader: function(event) {
			var self = this, target = $( event.currentTarget ), dropzone_container = target.closest( 'form' ).find( '#forums-post-document-uploader' ), edit_documents = [];
			event.preventDefault();

			if ( typeof window.Dropzone !== 'undefined' && dropzone_container.length ) {

				var dropzone_obj_key = dropzone_container.data('key');

				if ( dropzone_container.hasClass('closed') ) {

					// init dropzone
					self.dropzone_obj[dropzone_obj_key] = new Dropzone(dropzone_container[0], self.documentOptions);
					self.dropzone_media[dropzone_obj_key] = [];

					self.dropzone_obj[dropzone_obj_key].on('sending', function(file, xhr, formData) {
						formData.append('action', 'media_document_upload');
						formData.append('_wpnonce', BP_Nouveau.nonces.media);
					});

					self.dropzone_obj[dropzone_obj_key].on('error', function(file,response) {
						if ( file.accepted ) {
							if ( typeof response !== 'undefined' && typeof response.data !== 'undefined' && typeof response.data.feedback !== 'undefined' ) {
								$(file.previewElement).find('.dz-error-message span').text(response.data.feedback);
							}
						} else {
							self.dropzone_obj[dropzone_obj_key].removeFile(file);
						}
					});

					self.dropzone_obj[dropzone_obj_key].on('success', function(file, response) {
						if ( response.data.id ) {
							file.id = response.id;
							response.data.uuid = file.upload.uuid;
							response.data.menu_order = $(file.previewElement).closest('.dropzone').find(file.previewElement).index() - 1;
							response.data.album_id = self.album_id;
							response.data.group_id = self.group_id;
							response.data.saved    = false;
							self.dropzone_media[dropzone_obj_key].push( response.data );
							self.addDocumentIdsToForumsForm(dropzone_container);
						}
					});

					self.dropzone_obj[dropzone_obj_key].on('removedfile', function(file) {
						if ( self.dropzone_media[dropzone_obj_key].length ) {
							for ( var i in self.dropzone_media[dropzone_obj_key] ) {
								if ( file.upload.uuid == self.dropzone_media[dropzone_obj_key][i].uuid  ) {

									if ( ( ! this.bbp_is_reply_edit && ! this.bbp_is_topic_edit && ! this.bbp_is_forum_edit ) && typeof self.dropzone_media[dropzone_obj_key][i].saved !== 'undefined' && ! self.dropzone_media[dropzone_obj_key][i].saved ) {
										self.removeAttachment(self.dropzone_media[dropzone_obj_key][i].id);
									}

									self.dropzone_media[dropzone_obj_key].splice( i, 1 );
									self.addDocumentIdsToForumsForm(dropzone_container);
									break;
								}
							}
						}
					});

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
							for( var d = 0; d < edit_documents.length; d++ ) {
								mock_file = false;
								self.dropzone_media[dropzone_obj_key].push({
									'id': edit_documents[d].attachment_id,
									'media_id': edit_documents[d].id,
									'name': edit_documents[d].name,
									'title': edit_documents[d].name,
									'size': edit_documents[d].size,
									//'thumb': edit_documents[d].thumb,
									'url': edit_documents[d].url,
									'uuid': edit_documents[d].id,
									'menu_order': d,
									'saved': true
								});

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

								self.dropzone_obj[dropzone_obj_key].files.push(mock_file);
								self.dropzone_obj[dropzone_obj_key].emit('addedfile', mock_file);
								self.dropzone_obj[dropzone_obj_key].emit('complete', mock_file);
								//self.createDocumentThumbnailFromUrl(mock_file,dropzone_container);
							}
							self.addDocumentIdsToForumsForm(dropzone_container);
						}
					}

					// container class to open close
					dropzone_container.removeClass('closed').addClass('open');

					// reset gif component
					self.resetForumsGifComponent(event);

				} else {
					self.resetForumsDocumentComponent( dropzone_obj_key );
				}

			}

		},

		addMediaIdsToForumsForm: function(dropzone_container) {
			var self = this, dropzone_obj_key = dropzone_container.data('key');
			if( dropzone_container.closest('#whats-new-attachments').find('#bbp_media').length ) {
				dropzone_container.closest('#whats-new-attachments').find('#bbp_media').val(JSON.stringify(self.dropzone_media[dropzone_obj_key]));
			}
		},

		addDocumentIdsToForumsForm: function(dropzone_container) {
			var self = this, dropzone_obj_key = dropzone_container.data('key');
			if( dropzone_container.closest('#whats-new-attachments').find('#bbp_document').length ) {
				dropzone_container.closest('#whats-new-attachments').find('#bbp_document').val(JSON.stringify(self.dropzone_media[dropzone_obj_key]));
			}
		},

		createThumbnailFromUrl: function(mock_file,dropzone_container) {
			var self = this, dropzone_obj_key = dropzone_container.data('key');
			self.dropzone_obj[dropzone_obj_key].createThumbnailFromUrl(
				mock_file,
				self.dropzone_obj[dropzone_obj_key].options.thumbnailWidth,
				self.dropzone_obj[dropzone_obj_key].options.thumbnailHeight,
				self.dropzone_obj[dropzone_obj_key].options.thumbnailMethod,
				true,
				function(thumbnail) {
					self.dropzone_obj[dropzone_obj_key].emit('thumbnail', mock_file, thumbnail);
					self.dropzone_obj[dropzone_obj_key].emit('complete', mock_file);
				}
			);
		},

		openUploader: function(event) {
			var self = this;
			event.preventDefault();

			if ( typeof window.Dropzone !== 'undefined' && $('div#media-uploader').length ) {

				$('#bp-media-uploader').show();

				self.dropzone_obj = new Dropzone('div#media-uploader', self.options );

				self.dropzone_obj.on('sending', function(file, xhr, formData) {
					formData.append('action', 'media_upload');
					formData.append('_wpnonce', BP_Nouveau.nonces.media);
				});

				self.dropzone_obj.on('addedfile', function() {
					setTimeout(function(){
						if ( self.dropzone_obj.getAcceptedFiles().length ) {
							$('#bp-media-uploader-modal-status-text').text(wp.i18n.sprintf(BP_Nouveau.media.i18n_strings.upload_status, self.dropzone_media.length, self.dropzone_obj.getAcceptedFiles().length)).show();
						}
					},1000);
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

				self.dropzone_obj.on('queuecomplete', function() {
					$('#bp-media-uploader-modal-title').text(BP_Nouveau.media.i18n_strings.upload);
				});

				self.dropzone_obj.on('processing', function() {
					$('#bp-media-uploader-modal-title').text(BP_Nouveau.media.i18n_strings.uploading + '...');
				});

				self.dropzone_obj.on('success', function(file, response) {
					if ( response.data.id ) {
						file.id = response.id;
						response.data.uuid = file.upload.uuid;
						response.data.menu_order = self.dropzone_media.length;
						response.data.album_id = self.album_id;
						response.data.group_id = self.group_id;
						response.data.saved    = false;
						self.dropzone_media.push( response.data );
					}
					$('#bp-media-add-more').show();
					$('#bp-media-submit').show();
					$('#bp-media-uploader-modal-title').text(BP_Nouveau.media.i18n_strings.uploading + '...');
					$('#bp-media-uploader-modal-status-text').text(wp.i18n.sprintf( BP_Nouveau.media.i18n_strings.upload_status, self.dropzone_media.length, self.dropzone_obj.getAcceptedFiles().length )).show();
				});

				self.dropzone_obj.on('removedfile', function(file) {
					if ( self.dropzone_media.length ) {
						for ( var i in self.dropzone_media ) {
							if ( file.upload.uuid == self.dropzone_media[i].uuid ) {

								if ( typeof self.dropzone_media[i].saved !== 'undefined' && ! self.dropzone_media[i].saved ) {
									self.removeAttachment(self.dropzone_media[i].id);
								}

								self.dropzone_media.splice( i, 1 );
								break;
							}
						}
					}
					if ( ! self.dropzone_obj.getAcceptedFiles().length ) {
						$('#bp-media-uploader-modal-status-text').text('');
						$('#bp-media-add-more').hide();
						$('#bp-media-submit').hide();
					} else {
						$('#bp-media-uploader-modal-status-text').text(wp.i18n.sprintf( BP_Nouveau.media.i18n_strings.upload_status, self.dropzone_media.length, self.dropzone_obj.getAcceptedFiles().length )).show();
					}
				});
			}
		},

		openDocumentUploader: function(event) {
			var self = this;
			event.preventDefault();

			if ( typeof window.Dropzone !== 'undefined' && $('div#media-uploader').length ) {

				$('#bp-media-uploader').show();

				self.dropzone_obj = new Dropzone('div#media-uploader', self.options );

				self.dropzone_obj.on('sending', function(file, xhr, formData) {
					formData.append('action', 'media_document_upload');
					formData.append('_wpnonce', BP_Nouveau.nonces.media);
				});

				self.dropzone_obj.on('addedfile', function() {
					setTimeout(function(){
						if ( self.dropzone_obj.getAcceptedFiles().length ) {
							$('#bp-media-uploader-modal-status-text').text(wp.i18n.sprintf(BP_Nouveau.media.i18n_strings.upload_status, self.dropzone_media.length, self.dropzone_obj.getAcceptedFiles().length)).show();
						}
					},1000);
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

				self.dropzone_obj.on('queuecomplete', function() {
					$('#bp-media-uploader-modal-title').text(BP_Nouveau.media.i18n_strings.upload);
				});

				self.dropzone_obj.on('processing', function() {
					$('#bp-media-uploader-modal-title').text(BP_Nouveau.media.i18n_strings.uploading + '...');
				});

				self.dropzone_obj.on('success', function(file, response) {
					if ( response.data.id ) {
						file.id = response.id;
						response.data.uuid = file.upload.uuid;
						response.data.menu_order = self.dropzone_media.length;
						response.data.album_id = self.album_id;
						response.data.group_id = self.group_id;
						response.data.saved    = false;
						self.dropzone_media.push( response.data );
					}
					$('#bp-media-document-add-more').show();
					$('#bp-media-document-submit').show();
					$('#bp-media-uploader-modal-title').text(BP_Nouveau.media.i18n_strings.uploading + '...');
					$('#bp-media-uploader-modal-status-text').text(wp.i18n.sprintf( BP_Nouveau.media.i18n_strings.upload_status, self.dropzone_media.length, self.dropzone_obj.getAcceptedFiles().length )).show();
				});

				self.dropzone_obj.on('removedfile', function(file) {
					if ( self.dropzone_media.length ) {
						for ( var i in self.dropzone_media ) {
							if ( file.upload.uuid == self.dropzone_media[i].uuid ) {

								if ( typeof self.dropzone_media[i].saved !== 'undefined' && ! self.dropzone_media[i].saved ) {
									self.removeAttachment(self.dropzone_media[i].id);
								}

								self.dropzone_media.splice( i, 1 );
								break;
							}
						}
					}
					if ( ! self.dropzone_obj.getAcceptedFiles().length ) {
						$('#bp-media-uploader-modal-status-text').text('');
						$('#bp-media-document-add-more').hide();
						$('#bp-media-document-submit').hide();
					} else {
						$('#bp-media-uploader-modal-status-text').text(wp.i18n.sprintf( BP_Nouveau.media.i18n_strings.upload_status, self.dropzone_media.length, self.dropzone_obj.getAcceptedFiles().length )).show();
					}
				});
			}
		},

		openDocumentFolderUploader: function(event) {
			var self = this;
			event.preventDefault();

			if ( typeof window.Dropzone !== 'undefined' && $('div#media-uploader').length ) {

				$('#bp-media-uploader').show();

				self.dropzone_obj = new Dropzone('div#media-uploader-folder', self.options );

				self.dropzone_obj.on('sending', function(file, xhr, formData) {
					formData.append('action', 'media_document_upload');
					formData.append('_wpnonce', BP_Nouveau.nonces.media);
				});

				self.dropzone_obj.on('addedfile', function() {
					setTimeout(function(){
						if ( self.dropzone_obj.getAcceptedFiles().length ) {
							$('#bp-media-uploader-modal-status-text').text(wp.i18n.sprintf(BP_Nouveau.media.i18n_strings.upload_status, self.dropzone_media.length, self.dropzone_obj.getAcceptedFiles().length)).show();
						}
					},1000);
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

				self.dropzone_obj.on('queuecomplete', function() {
					$('#bp-media-uploader-modal-title').text(BP_Nouveau.media.i18n_strings.upload);
				});

				self.dropzone_obj.on('processing', function() {
					$('#bp-media-uploader-modal-title').text(BP_Nouveau.media.i18n_strings.uploading + '...');
				});

				self.dropzone_obj.on('success', function(file, response) {
					if ( response.data.id ) {
						file.id = response.id;
						response.data.uuid = file.upload.uuid;
						response.data.menu_order = self.dropzone_media.length;
						response.data.album_id = self.album_id;
						response.data.group_id = self.group_id;
						response.data.saved    = false;
						self.dropzone_media.push( response.data );
					}
					$('#bp-media-document-add-more').show();
					$('#bp-media-document-submit').show();
					$('#bp-media-uploader-modal-title').text(BP_Nouveau.media.i18n_strings.uploading + '...');
					$('#bp-media-uploader-modal-status-text').text(wp.i18n.sprintf( BP_Nouveau.media.i18n_strings.upload_status, self.dropzone_media.length, self.dropzone_obj.getAcceptedFiles().length )).show();
				});

				self.dropzone_obj.on('removedfile', function(file) {
					if ( self.dropzone_media.length ) {
						for ( var i in self.dropzone_media ) {
							if ( file.upload.uuid == self.dropzone_media[i].uuid ) {

								if ( typeof self.dropzone_media[i].saved !== 'undefined' && ! self.dropzone_media[i].saved ) {
									self.removeAttachment(self.dropzone_media[i].id);
								}

								self.dropzone_media.splice( i, 1 );
								break;
							}
						}
					}
					if ( ! self.dropzone_obj.getAcceptedFiles().length ) {
						$('#bp-media-uploader-modal-status-text').text('');
						$('#bp-media-document-add-more').hide();
						$('#bp-media-document-submit').hide();
					} else {
						$('#bp-media-uploader-modal-status-text').text(wp.i18n.sprintf( BP_Nouveau.media.i18n_strings.upload_status, self.dropzone_media.length, self.dropzone_obj.getAcceptedFiles().length )).show();
					}
				});
			}
		},

		openDocumentFolderChildUploader: function(event) {
			var self = this;
			event.preventDefault();

			if ( typeof window.Dropzone !== 'undefined' && $('div#bp-media-create-child-folder').length ) {

				$('#bp-media-create-child-folder').show();

				self.dropzone_obj = new Dropzone('div#media-uploader-child-folder', self.options );

				self.dropzone_obj.on('sending', function(file, xhr, formData) {
					formData.append('action', 'media_document_upload');
					formData.append('_wpnonce', BP_Nouveau.nonces.media);
				});

				self.dropzone_obj.on('addedfile', function() {
					setTimeout(function(){
						if ( self.dropzone_obj.getAcceptedFiles().length ) {
							$('#bp-media-uploader-modal-status-text').text(wp.i18n.sprintf(BP_Nouveau.media.i18n_strings.upload_status, self.dropzone_media.length, self.dropzone_obj.getAcceptedFiles().length)).show();
						}
					},1000);
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

				self.dropzone_obj.on('queuecomplete', function() {
					$('#bp-media-uploader-modal-title').text(BP_Nouveau.media.i18n_strings.upload);
				});

				self.dropzone_obj.on('processing', function() {
					$('#bp-media-uploader-modal-title').text(BP_Nouveau.media.i18n_strings.uploading + '...');
				});

				self.dropzone_obj.on('success', function(file, response) {
					if ( response.data.id ) {
						file.id = response.id;
						response.data.uuid = file.upload.uuid;
						response.data.menu_order = self.dropzone_media.length;
						response.data.album_id = self.album_id;
						response.data.group_id = self.group_id;
						response.data.saved    = false;
						self.dropzone_media.push( response.data );
					}
					$('#bp-media-document-add-more').show();
					$('#bp-media-document-submit').show();
					$('#bp-media-uploader-modal-title').text(BP_Nouveau.media.i18n_strings.uploading + '...');
					$('#bp-media-uploader-modal-status-text').text(wp.i18n.sprintf( BP_Nouveau.media.i18n_strings.upload_status, self.dropzone_media.length, self.dropzone_obj.getAcceptedFiles().length )).show();
				});

				self.dropzone_obj.on('removedfile', function(file) {
					if ( self.dropzone_media.length ) {
						for ( var i in self.dropzone_media ) {
							if ( file.upload.uuid == self.dropzone_media[i].uuid ) {

								if ( typeof self.dropzone_media[i].saved !== 'undefined' && ! self.dropzone_media[i].saved ) {
									self.removeAttachment(self.dropzone_media[i].id);
								}

								self.dropzone_media.splice( i, 1 );
								break;
							}
						}
					}
					if ( ! self.dropzone_obj.getAcceptedFiles().length ) {
						$('#bp-media-uploader-modal-status-text').text('');
						$('#bp-media-document-add-more').hide();
						$('#bp-media-document-submit').hide();
					} else {
						$('#bp-media-uploader-modal-status-text').text(wp.i18n.sprintf( BP_Nouveau.media.i18n_strings.upload_status, self.dropzone_media.length, self.dropzone_obj.getAcceptedFiles().length )).show();
					}
				});
			}
		},

		removeAttachment: function( id ) {
			var data = {
				'action': 'media_delete_attachment',
				'_wpnonce': BP_Nouveau.nonces.media,
				'id': id
			};

			$.ajax({
				type: 'POST',
				url: BP_Nouveau.ajaxurl,
				data: data
			});
		},

		changeUploadModalTab: function(event) {
			event.preventDefault();

			var content_tab = $(event.currentTarget).data('content');
			$('.bp-media-upload-tab-content').hide();
			$('#'+content_tab).show();
			this.current_tab = content_tab;
			$(event.currentTarget).closest('#bp-media-uploader').find('.bp-media-upload-tab').removeClass('selected');
			$(event.currentTarget).addClass('selected');
			this.toggleSubmitMediaButton();

			//replace dummy image with original image by faking scroll event to call bp.Nouveau.lazyLoad
			jQuery(window).scroll();
		},

		openCreateAlbumModal: function(event){
			event.preventDefault();

			this.openUploader(event);
			$('#bp-media-create-album').show();
		},

		openCreateFolderModal: function(event){
			event.preventDefault();

			this.openDocumentFolderUploader(event);
			this.folderLocationUI('#bp-media-create-folder');
			$('#bp-media-create-folder').show();			
		},

		openCreateFolderChildModal: function(event){
			event.preventDefault();

			this.openDocumentFolderChildUploader(event);
			this.folderLocationUI('#bp-media-create-child-folder');
			$('#bp-media-create-child-folder').show();
		},

		openEditFolderChildModal: function(event){
			event.preventDefault();

			// this.openDocumentFolderChildUploader(event);
			this.folderLocationUI('#bp-media-edit-child-folder');
			$('#bp-media-edit-child-folder').show();
		},
		
		folderLocationUI: function(a){
			if($(a).find('#bb-folder-location').length > 0){
				if($(a).find('#bb-folder-location').parent('.bb-dropdown-wrap').find('.bb-folder-location-select').length == 0){
					var bb_folder_location = '';
					$(a).find('#bb-folder-location option').each(function(){
						bb_folder_location += '<li class="'+ $(this).attr('class') +'">'+ $(this).text() +'</li>';
					});
					$(a).find('#bb-folder-location').parent().append('<div class="bb-folder-location-select"><span class="bb-folder-location-selected">'+$(a).find('#bb-folder-location option:first-child').text()+'</span><ul class="bb-folder-location-select-list">'+bb_folder_location+'</select>');

					$(document).on('click',a+' .bb-folder-location-select li',function(){
						var selected_option = $(a).find('#bb-folder-location option:nth-child('+($(this).index()+1)+')');
						$(this).addClass('selected').siblings().removeClass('selected');
						$(a).find('#bb-folder-location option').removeAttr('selected');
						selected_option.attr('selected','selected');
						$(a).find('.bb-folder-location-select .bb-folder-location-selected').text(selected_option.text());
						$(a).find('.bb-folder-location-select-list').slideUp(300);
					});
					$(document).on('click',a+' .bb-folder-location-select .bb-folder-location-selected',function(index){
						$(a).find('.bb-folder-location-select-list').slideToggle(300);
					});
				}
			}			
		},

		closeCreateAlbumModal: function(event){
			event.preventDefault();

			this.closeUploader(event);
			$('#bp-media-create-album').hide();
			$('#bb-album-title').val('');
		},

		closeCreateFolderModal: function(event){
			event.preventDefault();

			this.closeUploader(event);
			$('#bp-media-create-folder, #bp-media-create-child-folder').hide();
			$('#bb-album-title').val('');
		},

		closeEditFolderModal: function(event){
			event.preventDefault();

			$('#bp-media-edit-child-folder').hide();
		},

		submitMedia: function(event) {
			var self = this, target = $( event.currentTarget ), data;
			event.preventDefault();

			if ( target.hasClass( 'saving' ) ) {
				return false;
			}

			target.addClass( 'saving' );

			if ( self.current_tab === 'bp-dropzone-content' ) {

				var post_content = $('#bp-media-post-content').val();
				data = {
					'action': 'media_save',
					'_wpnonce': BP_Nouveau.nonces.media,
					'medias': self.dropzone_media,
					'content' : post_content,
					'album_id' : self.album_id,
					'group_id' : self.group_id
				};

				$('#bp-dropzone-content .bp-feedback').remove();

				$.ajax({
					type: 'POST',
					url: BP_Nouveau.ajaxurl,
					data: data,
					success: function (response) {
						if (response.success) {

							// It's the very first media, let's make sure the container can welcome it!
							if (!$('#media-stream ul.media-list').length) {
								$('#media-stream').html($('<ul></ul>').addClass('media-list item-list bp-list bb-photo-list grid'));
								$('.bb-photos-actions').show();
							}

							// Prepend the activity.
							bp.Nouveau.inject('#media-stream ul.media-list', response.data.media, 'prepend');

							for( var i = 0; i < self.dropzone_media.length; i++ ) {
								self.dropzone_media[i].saved = true;
							}

							self.closeUploader(event);

							//replace dummy image with original image by faking scroll event to call bp.Nouveau.lazyLoad
							jQuery(window).scroll();

						} else {
							$('#bp-dropzone-content').prepend(response.data.feedback);
						}

						target.removeClass('saving');
					}
				});

			} else if ( self.current_tab === 'bp-existing-media-content' ) {
				var selected = [];
				$('.bp-existing-media-wrap .bb-media-check-wrap [name="bb-media-select"]:checked').each(function() {
					selected.push($(this).val());
				});
				data = {
					'action': 'media_move_to_album',
					'_wpnonce': BP_Nouveau.nonces.media,
					'medias': selected,
					'album_id' : self.album_id,
					'group_id' : self.group_id
				};

				$('#bp-existing-media-content .bp-feedback').remove();

				$.ajax({
					type: 'POST',
					url: BP_Nouveau.ajaxurl,
					data: data,
					success: function (response) {
						if (response.success) {

							// It's the very first media, let's make sure the container can welcome it!
							if (!$('#media-stream ul.media-list').length) {
								$('#media-stream').html($('<ul></ul>').addClass('media-list item-list bp-list bb-photo-list grid'));
								$('.bb-photos-actions').show();
							}

							// Prepend the activity.
							bp.Nouveau.inject('#media-stream ul.media-list', response.data.media, 'prepend');

							// remove selected media from existing media list
							$('.bp-existing-media-wrap .bb-media-check-wrap [name="bb-media-select"]:checked').each(function() {
								if ( $(this).closest('li').data('id') === $(this).val() ) {
									$(this).closest('li').remove();
								}
							});

							//replace dummy image with original image by faking scroll event to call bp.Nouveau.lazyLoad
							jQuery(window).scroll();

							self.closeUploader(event);
						} else {
							$('#bp-existing-media-content').prepend(response.data.feedback);
						}

						target.removeClass('saving');
					}
				});
			} else if ( ! self.current_tab ) {
				self.closeUploader(event);
				target.removeClass('saving');
			}

		},

		submitDocumentMedia: function(event) {
			var self = this, target = $( event.currentTarget ), data;
			event.preventDefault();

			if ( target.hasClass( 'saving' ) ) {
				return false;
			}

			target.addClass( 'saving' );

			if ( self.current_tab === 'bp-dropzone-content' ) {

				var post_content = $('#bp-media-post-content').val();
				data = {
					'action': 'media_document_save',
					'_wpnonce': BP_Nouveau.nonces.media,
					'medias': self.dropzone_media,
					'content' : post_content,
					'album_id' : self.album_id,
					'group_id' : self.group_id
				};

				$('#bp-dropzone-content .bp-feedback').remove();

				$.ajax({
					type: 'POST',
					url: BP_Nouveau.ajaxurl,
					data: data,
					success: function (response) {
						if (response.success) {

							// It's the very first media, let's make sure the container can welcome it!
							if (!$('#media-stream div#media-folder-document-data-table').length) {
								$('#media-stream').html($('<div></div>').addClass( 'display' ) );
								$('#media-stream div').attr( 'id', 'media-folder-document-data-table' );
								$('.bb-photos-actions').show();
							}

							// Prepend the activity.
							bp.Nouveau.inject('#media-stream div#media-folder-document-data-table', response.data.media, 'prepend');

							for( var i = 0; i < self.dropzone_media.length; i++ ) {
								self.dropzone_media[i].saved = true;
							}

							self.closeUploader(event);

							//replace dummy image with original image by faking scroll event to call bp.Nouveau.lazyLoad
							jQuery(window).scroll();

						} else {
							$('#bp-dropzone-content').prepend(response.data.feedback);
						}

						target.removeClass('saving');
					}
				});

			} else if ( self.current_tab === 'bp-existing-media-content' ) {
				var selected = [];
				$('.bp-existing-media-wrap .bb-media-check-wrap [name="bb-media-select"]:checked').each(function() {
					selected.push($(this).val());
				});
				data = {
					'action': 'media_move_to_album',
					'_wpnonce': BP_Nouveau.nonces.media,
					'medias': selected,
					'album_id' : self.album_id,
					'group_id' : self.group_id
				};

				$('#bp-existing-media-content .bp-feedback').remove();

				$.ajax({
					type: 'POST',
					url: BP_Nouveau.ajaxurl,
					data: data,
					success: function (response) {
						if (response.success) {

							// It's the very first media, let's make sure the container can welcome it!
							if (!$('#media-stream ul.media-list').length) {
								$('#media-stream').html($('<ul></ul>').addClass('media-list item-list bp-list bb-photo-list grid'));
								$('.bb-photos-actions').show();
							}

							// Prepend the activity.
							bp.Nouveau.inject('#media-stream ul.media-list', response.data.media, 'prepend');

							// remove selected media from existing media list
							$('.bp-existing-media-wrap .bb-media-check-wrap [name="bb-media-select"]:checked').each(function() {
								if ( $(this).closest('li').data('id') === $(this).val() ) {
									$(this).closest('li').remove();
								}
							});

							//replace dummy image with original image by faking scroll event to call bp.Nouveau.lazyLoad
							jQuery(window).scroll();

							self.closeUploader(event);
						} else {
							$('#bp-existing-media-content').prepend(response.data.feedback);
						}

						target.removeClass('saving');
					}
				});
			} else if ( ! self.current_tab ) {
				self.closeUploader(event);
				target.removeClass('saving');
			}

		},

		saveAlbum: function(event) {
			var target = $( event.currentTarget ), self = this, title = $('#bb-album-title'), privacy = $('#bb-album-privacy');
			event.preventDefault();

			if( $.trim(title.val()) === '' ) {
				title.addClass('error');
				return false;
			} else {
				title.removeClass('error');
			}

			if( ! self.group_id && $.trim(privacy.val()) === '' ) {
				privacy.addClass('error');
				return false;
			} else {
				privacy.removeClass('error');
			}

			target.prop('disabled',true);

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

			//remove all feedback erros from the DOM
			$('.bb-single-album-header .bp-feedback').remove();
			$('#boss-media-create-album-popup .bp-feedback').remove();

			$.ajax({
				type: 'POST',
				url: BP_Nouveau.ajaxurl,
				data: data,
				success: function (response) {
					setTimeout(function () {
						target.prop('disabled',false);
					},500);
					if ( response.success ) {
						if ( self.album_id ) {
							$('#bp-single-album-title').text(title.val());
							$('#bb-album-privacy').val(privacy.val());
							self.cancelEditAlbumTitle(event);
						} else {
							$('#buddypress .bb-albums-list').prepend(response.data.album);
							//self.closeCreateAlbumModal(event);
							window.location.href = response.data.redirect_url;
						}
					} else {
						if ( self.album_id ) {
							$('#bp-media-single-album').prepend(response.data.feedback);
						} else {
							$('#boss-media-create-album-popup .bb-model-header').after(response.data.feedback);
						}
					}
				}
			});

		},

		saveFolder: function(event) {
			var target = $( event.currentTarget ), self = this, title = $('#bb-album-title'), privacy = $('#bb-folder-privacy');
			event.preventDefault();

			if( $.trim(title.val()) === '' ) {
				title.addClass('error');
				return false;
			} else {
				title.removeClass('error');
			}

			if( ! self.group_id && $.trim(privacy.val()) === '' ) {
				privacy.addClass('error');
				return false;
			} else {
				privacy.removeClass('error');
			}

			target.prop('disabled',true);

			var data = {
				'action': 'media_folder_save',
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

			//remove all feedback erros from the DOM
			$('.bb-single-album-header .bp-feedback').remove();
			$('#boss-media-create-album-popup .bp-feedback').remove();

			$.ajax({
				type: 'POST',
				url: BP_Nouveau.ajaxurl,
				data: data,
				success: function (response) {
					setTimeout(function () {
						target.prop('disabled',false);
					},500);
					if ( response.success ) {
						if ( self.album_id ) {
							$('#bp-single-album-title').text(title.val());
							$('#bb-folder-privacy').val(privacy.val());
							self.cancelEditAlbumTitle(event);
						} else {
							$('#buddypress .bb-albums-list').prepend(response.data.album);
							//self.closeCreateAlbumModal(event);
							window.location.href = response.data.redirect_url;
						}
					} else {
						if ( self.album_id ) {
							$('#bp-media-single-album').prepend(response.data.feedback);
						} else {
							$('#boss-media-create-album-popup .bb-model-header').after(response.data.feedback);
						}
					}
				}
			});

		},

		saveChildFolder: function(event) {
			var target = $( event.currentTarget ), self = this, title = $('#bb-album-child-title'), privacy = $('#bb-folder-child-privacy'), parent = $('#parent_id');
			event.preventDefault();

			if( $.trim(title.val()) === '' ) {
				title.addClass('error');
				return false;
			} else {
				title.removeClass('error');
			}

			if( ! self.group_id && $.trim(privacy.val()) === '' ) {
				privacy.addClass('error');
				return false;
			} else {
				privacy.removeClass('error');
			}

			target.prop('disabled',true);

			var data = {
				'action'	: 'media_folder_save',
				'_wpnonce'	: BP_Nouveau.nonces.media,
				'title'		: title.val(),
				'medias'	: self.dropzone_media,
				'privacy'	: privacy.val(),
				'parent'	: parent.val()
			};

			if ( self.album_id ) {
				data.album_id = self.album_id;
			}

			if ( self.group_id ) {
				data.group_id = self.group_id;
			}

			//remove all feedback erros from the DOM
			$('.bb-single-album-header .bp-feedback').remove();
			$('#boss-media-create-album-popup .bp-feedback').remove();

			$.ajax({
				type: 'POST',
				url: BP_Nouveau.ajaxurl,
				data: data,
				success: function (response) {
					setTimeout(function () {
						target.prop('disabled',false);
					},500);
					if ( response.success ) {
						window.location.href = response.data.redirect_url;
					} else {
						if ( self.album_id ) {
							$('#bp-media-single-album').prepend(response.data.feedback);
						} else {
							$('#boss-media-create-album-popup .bb-model-header').after(response.data.feedback);
						}
					}
				}
			});

		},

		deleteAlbum: function(event) {
			event.preventDefault();

			if ( ! this.album_id ) {
				return false;
			}

			if ( ! confirm( BP_Nouveau.media.i18n_strings.album_delete_confirm ) ) {
				return false;
			}

			$(event.currentTarget).prop('disabled',true);

			var data = {
				'action': 'media_album_delete',
				'_wpnonce': BP_Nouveau.nonces.media,
				'album_id': this.album_id,
				'group_id': this.group_id
			};

			$.ajax({
				type: 'POST',
				url: BP_Nouveau.ajaxurl,
				data: data,
				success: function (response) {
					if ( response.success ) {
						window.location.href = response.data.redirect_url;
					} else {
						alert( BP_Nouveau.media.i18n_strings.album_delete_error );
						$(event.currentTarget).prop('disabled',false);
					}
				}
			});

		},

		deleteFolder: function(event) {
			event.preventDefault();
			if ( ! this.album_id ) {
				return false;
			}

			if ( ! confirm( BP_Nouveau.media.i18n_strings.folder_delete_confirm ) ) {
				return false;
			}

			$(event.currentTarget).prop('disabled',true);

			var data = {
				'action': 'media_folder_delete',
				'_wpnonce': BP_Nouveau.nonces.media,
				'album_id': this.album_id,
				'group_id': this.group_id
			};

			$.ajax({
				type: 'POST',
				url: BP_Nouveau.ajaxurl,
				data: data,
				success: function (response) {
					if ( response.success ) {
						window.location.href = response.data.redirect_url;
					} else {
						alert( BP_Nouveau.media.i18n_strings.folder_delete_error );
						$(event.currentTarget).prop('disabled',false);
					}
				}
			});

		},

		/**
		 * [injectQuery description]
		 * @param  {[type]} event [description]
		 * @return {[type]}       [description]
		 */
		injectMedias: function( event ) {
			var store = bp.Nouveau.getStorage( 'bp-media' ),
				scope = store.scope || null, filter = store.filter || null;

			if ( $( event.currentTarget ).hasClass( 'load-more' ) ) {
				var next_page = ( Number( this.current_page ) * 1 ) + 1, self = this, search_terms = '';

				// Stop event propagation
				event.preventDefault();

				$( event.currentTarget ).find( 'a' ).first().addClass( 'loading' );

				if ( $( '#buddypress .dir-search input[type=search]' ).length ) {
					search_terms = $( '#buddypress .dir-search input[type=search]' ).val();
				}

				bp.Nouveau.objectRequest( {
					object              : 'media',
					scope               : scope,
					filter              : filter,
					search_terms        : search_terms,
					page                : next_page,
					method              : 'append',
					target              : '#buddypress [data-bp-list] ul.bp-list'
				} ).done( function( response ) {
					if ( true === response.success ) {
						$( event.currentTarget ).remove();

						// Update the current page
						self.current_page = next_page;

						//replace dummy image with original image by faking scroll event to call bp.Nouveau.lazyLoad
						jQuery(window).scroll();
					}
				} );
			}
		},

		injectDocuments: function( event ) {
			var store = bp.Nouveau.getStorage( 'bp-media' ),
				scope = store.scope || null, filter = store.filter || null;

			if ( $( event.currentTarget ).hasClass( 'load-more' ) ) {
				var next_page = ( Number( this.current_page ) * 1 ) + 1, self = this, search_terms = '';

				// Stop event propagation
				event.preventDefault();

				$( event.currentTarget ).find( 'a' ).first().addClass( 'loading' );

				if ( $( '#buddypress .dir-search input[type=search]' ).length ) {
					search_terms = $( '#buddypress .dir-search input[type=search]' ).val();
				}

				bp.Nouveau.objectRequest( {
					object              : 'media',
					scope               : scope,
					filter              : filter,
					search_terms        : search_terms,
					page                : next_page,
					method              : 'append',
					target              : '#buddypress [data-bp-media-type] div#media-folder-document-data-table'
				} ).done( function( response ) {
					if ( true === response.success ) {
						$( event.currentTarget ).parent( '.pager' ).remove();

						// Update the current page
						self.current_page = next_page;

						//replace dummy image with original image by faking scroll event to call bp.Nouveau.lazyLoad
						jQuery(window).scroll();
					}
				} );
			}
		},

		/**
		 * [appendQuery description]
		 * @param  {[type]} event [description]
		 * @return {[type]}       [description]
		 */
		appendMedia: function( event ) {
			var store = bp.Nouveau.getStorage( 'bp-media' ),
				scope = store.scope || null, filter = store.filter || null;

			if ( $( event.currentTarget ).hasClass( 'load-more' ) ) {
				var next_page = ( Number( this.current_page_existing_media ) * 1 ) + 1, self = this, search_terms = '';

				// Stop event propagation
				event.preventDefault();

				$( event.currentTarget ).find( 'a' ).first().addClass( 'loading' );

				if ( $( '#buddypress .dir-search input[type=search]' ).length ) {
					search_terms = $( '#buddypress .dir-search input[type=search]' ).val();
				}

				bp.Nouveau.objectRequest( {
					object              : 'media',
					scope               : scope,
					filter              : filter,
					search_terms        : search_terms,
					page                : next_page,
					method              : 'append',
					caller              : 'bp-existing-media',
					target              : '.bp-existing-media-wrap ul.bp-list'
				} ).done( function( response ) {
					if ( true === response.success ) {
						$( event.currentTarget ).remove();

						// Update the current page
						self.current_page_existing_media = next_page;
					}
				} );
			}
		},

		/**
		 * [appendQuery description]
		 * @param  {[type]} event [description]
		 * @return {[type]}       [description]
		 */
		appendAlbums: function( event ) {
			var next_page = ( Number( this.current_page_albums ) * 1 ) + 1, self = this;

			// Stop event propagation
			event.preventDefault();

			$( event.currentTarget ).find( 'a' ).first().addClass( 'loading' );

			var data = {
				'action': 'media_albums_loader',
				'_wpnonce': BP_Nouveau.nonces.media,
				'page'      : next_page
			};

			$.ajax({
				type: 'POST',
				url: BP_Nouveau.ajaxurl,
				data: data,
				success: function (response) {
					if ( true === response.success ) {
						$( event.currentTarget ).remove();
						$( '#albums-dir-list ul.bb-albums-list' ).fadeOut( 100, function() {
							$( '#albums-dir-list ul.bb-albums-list' ).append( response.data.albums );
							$( this ).fadeIn( 100 );
						} );
						// Update the current page
						self.current_page_albums = next_page;
					}
				}
			});
		},

		toggleSubmitMediaButton: function() {
			var submit_media_button = $('#bp-media-submit'), add_more_button = $('#bp-media-add-more');
			if ( this.current_tab === 'bp-dropzone-content' ) {
				if ( this.dropzone_obj.getAcceptedFiles().length ) {
					submit_media_button.show();
					add_more_button.show();
				} else {
					submit_media_button.hide();
					add_more_button.hide();
				}
			} else if ( this.current_tab === 'bp-existing-media-content' ) {
				if ( $('.bp-existing-media-wrap .bb-media-check-wrap [name="bb-media-select"]:checked').length ) {
					submit_media_button.show();
				} else {
					submit_media_button.hide();
				}
				add_more_button.hide();
			}
		},

		// play gif
		playVideo: function(event) {
			event.preventDefault();
			var video = $(event.currentTarget).find('video').get(0),
				$button = $(event.currentTarget).find('.gif-play-button');
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
			$( '.gif-player' ).each( function () {
				var video = $( this ).find( 'video' ).get( 0 ),
					$button = $( this ).find( '.gif-play-button' );

				if ( $( this ).is( ':in-viewport' ) ) {
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
			} );
		},

		/**
		 * File action Button
		 */
		fileActionButton: function (event) {
			event.preventDefault();
			$(event.currentTarget).closest('.media-folder_items').toggleClass('is-visible').siblings('.media-folder_items').removeClass('is-visible');
		},

		/**
		 * File Activity action Button
		 */
		fileActivityActionButton: function (event) {
			event.preventDefault();
			$(event.currentTarget).closest('.bb-activity-media-elem').toggleClass('is-visible');
			if(!$('.bb-activity-media-elem.document-activity').closest('.activity-inner').hasClass('documemt-activity')){
				$('.bb-activity-media-elem.document-activity').closest('.activity-content').addClass('documemt-activity');
			}
		}
	};

	/**
	 * [Media description]
	 * @type {Object}
	 */
	bp.Nouveau.Media.Theatre = {

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

			this.medias = [];
			this.current_media = false;
			this.current_index = 0;
			this.is_open = false;
			this.nextLink = $('.bb-next-media');
			this.previousLink = $('.bb-prev-media');
			this.activity_ajax = false;

		},

		/**
		 * [addListeners description]
		 */
		addListeners: function() {

			$( document ).on( 'click', '.bb-open-media-theatre',    this.openTheatre.bind( this ) );
			$( document ).on( 'click', '.bb-close-media-theatre',   this.closeTheatre.bind( this ) );
			$( document ).on( 'click', '.bb-prev-media',            this.previous.bind( this ) );
			$( document ).on( 'click', '.bb-next-media',            this.next.bind( this ) );
			$( document ).on( 'bp_activity_ajax_delete_request',    this.activityDeleted.bind( this ) );

		},

		documentClick: function( e ) {
			var self = this;
			if ( self.is_open ) {
				var target = e.target;
				var model = document.getElementById('bb-media-model-container');
				if (model != null && !model.contains(target) && document.body.contains(target)) {
					self.closeTheatre(e);
				}
			}
		},

		checkPressedKey: function( e ) {
			var self = this;
			e = e || window.event;
			switch ( e.keyCode ) {
				case 27: // escape key
					self.closeTheatre(e);
					break;
				case 37: // left arrow key code
					self.previous(e);
					break;
				case 39: // right arrow key code
					self.next(e);
					break;
			}
		},

		openTheatre: function(event) {
			event.preventDefault();
			var target = $(event.currentTarget), id, self = this;

			if ( target.closest('#bp-existing-media-content').length ) {
				return false;
			}

			self.setupGlobals();
			self.setMedias(target);

			id = target.data('id');
			self.setCurrentMedia( id );
			self.showMedia();
			self.navigationCommands();
			self.getActivity();

			$('.bb-media-model-wrapper').show();
			self.is_open = true;

			document.addEventListener( 'keyup', self.checkPressedKey.bind(self) );
			//document.addEventListener( 'click', self.documentClick.bind(self) );
		},

		closeTheatre: function(event) {
			event.preventDefault();
			var self = this;

			$('.bb-media-model-wrapper').hide();
			self.is_open = false;

			document.removeEventListener( 'keyup', self.checkPressedKey.bind(self) );
			//document.removeEventListener( 'click', self.documentClick.bind(self) );
		},

		setMedias: function(target) {
			var media_elements = $('.bb-open-media-theatre'), i = 0, self = this;

			//check if on activity page, load only activity media in theatre
			if ( $('body').hasClass('activity') ) {
				media_elements = $(target).closest('.bb-activity-media-wrap').find('.bb-open-media-theatre');
			}

			if ( typeof media_elements !== 'undefined' ) {
				self.medias = [];
				for( i = 0; i < media_elements.length; i++ ) {
					var media_element = $(media_elements[i]);
					if ( ! media_element.closest('#bp-existing-media-content').length ) {

						var m = {
							id: media_element.data('id'),
							attachment: media_element.data('attachment-full'),
							activity_id: media_element.data('activity-id'),
							is_forum: false
						};

						if ( media_element.closest('.forums-media-wrap').length ) {
							m.is_forum = true;
						}

						self.medias.push(m);
					}
				}
			}
		},

		setCurrentMedia: function( id ) {
			var self = this, i = 0;
			for( i = 0; i < self.medias.length; i++ ) {
				if ( id === self.medias[i].id ) {
					self.current_media = self.medias[i];
					self.current_index = i;
					break;
				}
			}
		},

		showMedia: function() {
			var self = this;
			$('.bb-media-model-wrapper .bb-media-section').find('img').attr('src',self.current_media.attachment+'?'+new Date().getTime());
			self.navigationCommands();
		},

		next: function(event) {
			event.preventDefault();
			var self = this, activity_id;
			if ( typeof self.medias[self.current_index + 1] !== 'undefined' ) {
				self.current_index = self.current_index + 1;
				activity_id = self.current_media.activity_id;
				self.current_media = self.medias[self.current_index];
				self.showMedia();
				if ( activity_id != self.current_media.activity_id ) {
					self.getActivity();
				}
			} else {
				self.nextLink.hide();
			}
		},

		previous: function(event) {
			event.preventDefault();
			var self = this, activity_id;
			if ( typeof self.medias[self.current_index - 1] !== 'undefined' ) {
				self.current_index = self.current_index - 1;
				activity_id = self.current_media.activity_id;
				self.current_media = self.medias[self.current_index];
				self.showMedia();
				if ( activity_id != self.current_media.activity_id ) {
					self.getActivity();
				}
			} else {
				self.previousLink.hide();
			}
		},

		navigationCommands: function() {
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

		getActivity: function() {
			var self = this;

			$('.bb-media-info-section .activity-list').addClass('loading').html('<i class="dashicons dashicons-update animate-spin"></i>');

			if ( typeof BP_Nouveau.activity !== 'undefined' &&
				self.current_media &&
				typeof self.current_media.activity_id !== 'undefined' &&
				self.current_media.activity_id != 0 &&
				! self.current_media.is_forum
			) {

				if ( self.activity_ajax != false ) {
					self.activity_ajax.abort();
				}

				self.activity_ajax = $.ajax({
					type: 'POST',
					url: BP_Nouveau.ajaxurl,
					data: {
						action: 'media_get_activity',
						id: self.current_media.activity_id,
						nonce: BP_Nouveau.nonces.media
					},
					success: function (response) {
						if (response.success) {
							$('.bb-media-info-section .activity-list').removeClass('loading').html(response.data.activity);
							$('.bb-media-info-section').show();

							//replace dummy image with original image by faking scroll event to call bp.Nouveau.lazyLoad
							jQuery(window).scroll();
						}
					}
				});
			} else {
				$('.bb-media-info-section').hide();
			}
		},

		activityDeleted: function(event,data) {
			var self = this, i = 0;
			if (self.is_open && typeof data !== 'undefined' && data.action === 'delete_activity' && self.current_media.activity_id == data.id) {

				$(document).find('[data-bp-list="media"] .bb-open-media-theatre[data-id="' + self.current_media.id + '"]').closest('li').remove();
				$(document).find('[data-bp-list="activity"] .bb-open-media-theatre[data-id="' + self.current_media.id + '"]').closest('.bb-activity-media-elem').remove();

				for (i = 0; i < self.medias.length; i++) {
					if (self.medias[i].activity_id == data.id) {
						self.medias.splice(i, 1);
						break;
					}
				}

				if (self.current_index == 0 && self.current_index != (self.medias.length)) {
					self.current_index = -1;
					self.next(event);
				} else if (self.current_index == 0 && self.current_index == (self.medias.length)) {
					self.closeTheatre(event);
				} else if (self.current_index == (self.medias.length)) {
					self.previous(event);
				} else {
					self.current_index = -1;
					self.next(event);
				}
			}
		}
	};

	// Launch BP Nouveau Media
	bp.Nouveau.Media.start();

	// Launch BP Nouveau Media Theatre
	bp.Nouveau.Media.Theatre.start();

} )( bp, jQuery );
