/* global bp */
window.bp = window.bp || {};

( function ( exports, $ ) {

	// Bail if not set.
	if ( typeof BP_Nouveau === 'undefined' ) {
		return;
	}

	bp.Nouveau       = bp.Nouveau || {};
	bp.Nouveau.Media = bp.Nouveau.Media || {};

	bp.Nouveau.TopicReplyDraft = {
		start: function () {

			// Check the user is logged or not.
			if ( 'undefined' === typeof BP_Nouveau.forums.params.bb_current_user_id || 0 === parseInt( BP_Nouveau.forums.params.bb_current_user_id ) ) {
				return;
			}

			this.setupGlobals();
			this.addListeners();
		},

		/**
		 * [setupGlobals description]
		 *
		 * @return {[type]} [description]
		 */
		setupGlobals: function () {

			var bodySelector = $( 'body' );

			// Draft variables.
			this.bbp_forum_id               = false;
			this.bbp_topic_id               = false;
			this.bbp_reply_to               = false;
			this.is_bb_theme                = bodySelector.hasClass( 'buddyboss-theme' );
			this.topic_reply_local_interval = false;
			this.topic_reply_ajax_interval  = false;
			this.draft_ajax_request         = null;
			this.is_topic_reply_form_submit = false;
			this.draft_content_changed      = false;
			this.all_draft_data             = {};
			this.bp_nouveau_forums_data     = ( 'undefined' !== typeof BP_Nouveau.forums.draft ) ? BP_Nouveau.forums.draft : {};
			this.topic_reply_draft          = {
				object: false,
				data_key: false,
				data: false,
				post_action: 'update',
				is_content_valid: false
			};

			// Set object and key for draft.
			var newPostEvent = new Event( $( '#new-post' ) );
			bp.Nouveau.TopicReplyDraft.setupTopicReplyDraftKeys( newPostEvent );
			bp.Nouveau.TopicReplyDraft.getTopicReplyDraftData();
			bp.Nouveau.TopicReplyDraft.syncTopicReplyDraftData();
		},

		/**
		 * [addListeners description]
		 */
		addListeners: function () {

			// Set up the draft keys/intervals/display data when Buddyboss theme is enabled.
			if ( this.is_bb_theme ) {
				$( document ).on( 'bbp_after_load_topic_form', this.setupOnOpenTopicReplyModal.bind( this ) );
				$( document ).on( 'bbp_after_load_reply_form', this.setupOnOpenTopicReplyModal.bind( this ) );
				$( document ).on( 'bbp_after_load_inline_reply_form', this.setupOnOpenTopicReplyModal.bind( this ) );
				$( document ).on( 'bbp_after_close_topic_reply_form', this.clearOnCloseTopicReplyModal.bind( this ) );
			} else {
				// Set up the intervals.
				$( window ).on( 'load', this.setupTopicReplyDraftIntervals.bind( this ) );
				bp.Nouveau.TopicReplyDraft.displayTopicReplyDraft();
			}

			if ( ! $( 'body' ).hasClass( 'activity' ) ) {
				// This will work only for Chrome.
				window.onbeforeunload = function( event ) {
					if ( 'undefined' !== typeof event ) {
						bp.Nouveau.TopicReplyDraft.setupOnReloadWindow();
					}
				};

				// This will work only for other browsers.
				window.unload = function( event ) {
					if ( 'undefined' !== typeof event ) {
						bp.Nouveau.TopicReplyDraft.setupOnReloadWindow();
					}
				};
			}

			// Submit the topic form.
			$( document ).on( 'click', '#new-post #bbp_topic_submit', this.submitTopicReplyDraftForm.bind( this ) );
			// Submit the reply form.
			$( document ).on( 'click', '#new-post #bbp_reply_submit', this.submitTopicReplyDraftForm.bind( this ) );

			$( document ).on( 'click', '#new-post .bb_discard_topic_reply_draft', this.discardTopicReplyDraftForm.bind( this ) );
		},

		setupOnOpenTopicReplyModal: function() {
			bp.Nouveau.TopicReplyDraft.setupTopicReplyDraftKeys();
			bp.Nouveau.TopicReplyDraft.getTopicReplyDraftData();
			bp.Nouveau.TopicReplyDraft.syncTopicReplyDraftData();
			bp.Nouveau.TopicReplyDraft.setupTopicReplyDraftIntervals();
			bp.Nouveau.TopicReplyDraft.displayTopicReplyDraft();
		},

		setupTopicReplyDraftKeys: function() {

			if ( $( '#bbp_forum_id' ).length > 0 ) {
				this.bbp_forum_id               = parseInt( $( '#bbp_forum_id' ).val() );
				this.topic_reply_draft.object   = 'topic';
				this.topic_reply_draft.data_key = 'draft_topic';

				if ( 0 < this.bbp_forum_id ) {
					this.topic_reply_draft.data_key = 'draft_discussion_' + this.bbp_forum_id;
				}
			} else if ( $( '#bbp_topic_id' ).length > 0 ) {
				this.bbp_topic_id               = parseInt( $( '#bbp_topic_id' ).val() );
				this.bbp_reply_to               = parseInt( $( '#bbp_reply_to' ).val() );
				this.topic_reply_draft.object   = 'reply';
				this.topic_reply_draft.data_key = 'draft_reply';

				if ( 0 < this.bbp_topic_id && 0 === this.bbp_reply_to ) {
					this.topic_reply_draft.data_key = 'draft_reply_' + this.bbp_topic_id;
				} else if ( 0 < this.bbp_topic_id && 0 < this.bbp_reply_to ) {
					this.topic_reply_draft.data_key = 'draft_reply_' + this.bbp_topic_id + '_' + this.bbp_reply_to;
				}
			}
		},

		getTopicReplyDraftData: function() {

			if ( ! this.topic_reply_draft.data_key || '' !== this.topic_reply_draft.data_key ) {

				var draft_data = localStorage.getItem( this.topic_reply_draft.data_key );
				if ( ! _.isUndefined( draft_data ) && null !== draft_data && 0 < draft_data.length ) {

					// Parse data with JSON.
					var draft_activity_local_data                        = JSON.parse( draft_data );
					this.topic_reply_draft.data                          = draft_activity_local_data.data;
					this.all_draft_data[this.topic_reply_draft.data_key] = draft_activity_local_data.data;
				}
			}

			return this.topic_reply_draft;
		},

		syncTopicReplyDraftData: function() {
			if ( 'undefined' === typeof this.all_draft_data[this.topic_reply_draft.data_key] && 'undefined' !== typeof this.bp_nouveau_forums_data && 'undefined' !== typeof this.bp_nouveau_forums_data[this.topic_reply_draft.data_key] ) {
				this.topic_reply_draft                               = this.bp_nouveau_forums_data[this.topic_reply_draft.data_key];
				this.all_draft_data[this.topic_reply_draft.data_key] = this.bp_nouveau_forums_data[this.topic_reply_draft.data_key].data;
				localStorage.setItem( this.topic_reply_draft.data_key, JSON.stringify( this.topic_reply_draft ) );
			}
		},

		setupTopicReplyDraftIntervals: function() {

			if ( this.is_bb_theme && $( '.bb-modal-box' ).hasClass( 'bb-modal-open' ) ) {
				if ( ! window.topic_reply_local_interval ) {
					window.topic_reply_local_interval = setInterval(
						function() {
							bp.Nouveau.TopicReplyDraft.collectTopicReplyDraftActivity();
						},
						3000
					);
				}

				if ( ! window.topic_reply_ajax_interval ) {
					window.topic_reply_ajax_interval = setInterval(
						function() {
							bp.Nouveau.TopicReplyDraft.postTopicReplyDraft( false, false, false );
						},
						20000
					);
				}

			} else if ( ! this.is_bb_theme ) {
				if ( ! window.topic_reply_local_interval ) {
					window.topic_reply_local_interval = setInterval(
						function() {
							bp.Nouveau.TopicReplyDraft.collectTopicReplyDraftActivity();
						},
						3000
					);
				}

				if ( ! window.topic_reply_ajax_interval ) {
					window.topic_reply_ajax_interval = setInterval(
						function() {
							bp.Nouveau.TopicReplyDraft.postTopicReplyDraft( false, false, false );
						},
						20000
					);
				}
			}
		},

		clearOnCloseTopicReplyModal: function() {
			bp.Nouveau.Media.reply_topic_display_post = '';

			if ( ! bp.Nouveau.TopicReplyDraft.is_topic_reply_form_submit ) {
				bp.Nouveau.TopicReplyDraft.collectTopicReplyDraftActivity();
				bp.Nouveau.TopicReplyDraft.postTopicReplyDraft( false, true, false );
			}

			bp.Nouveau.TopicReplyDraft.clearTopicReplyDraftIntervals();
			setTimeout(
				function () {
					bp.Nouveau.TopicReplyDraft.resetTopicReplyDraftPostForm();
				},
				500
			);
			bp.Nouveau.Media.reply_topic_display_post             = '';
			bp.Nouveau.TopicReplyDraft.is_topic_reply_form_submit = false;
		},

		clearTopicReplyDraftIntervals: function() {
			clearInterval( window.topic_reply_local_interval );
			window.topic_reply_local_interval = false;

			clearInterval( window.topic_reply_ajax_interval );
			window.topic_reply_ajax_interval = false;
		},

		resetLocalTopicReplyDraft: function() {
			bp.Nouveau.Media.reply_topic_allow_delete_media       = false;
			bp.Nouveau.Media.reply_topic_display_post             = '';
			bp.Nouveau.TopicReplyDraft.is_topic_reply_form_submit = true;

			if ( 'undefined' !== typeof this.all_draft_data[this.topic_reply_draft.data_key] ) {
				delete this.all_draft_data[this.topic_reply_draft.data_key];
			}
			if ( 'undefined' !== typeof this.bp_nouveau_forums_data[this.topic_reply_draft.data_key] ) {
				delete this.bp_nouveau_forums_data[this.topic_reply_draft.data_key];
			}

			this.topic_reply_draft.data = false;
			localStorage.removeItem( this.topic_reply_draft.data_key );
			bp.Nouveau.Media.reply_topic_display_post = 'edit';

			// Remove class to display draft.
			$( '#new-post' ).removeClass( 'has-draft' );
		},

		resetTopicReplyDraftPostForm: function() {
			var target                      = $( 'form#new-post' ),
				editor_key                  = target.find( '.bbp-the-content' ).data( 'key' ),
				$editor,
				$medium_editor,
				media_dropzone_container    = target.find( '#forums-post-media-uploader' ),
				document_dropzone_container = target.find( '#forums-post-document-uploader' ),
				video_dropzone_container    = target.find( '#forums-post-video-uploader' ),
				gif_attached_container      = target.find( '#whats-new-attachments .forums-attached-gif-container' );

			// Reset editor.
			if ( 'topic' === this.topic_reply_draft.object ) {
				$medium_editor = window.forums_medium_topic_editor[editor_key];

				$editor = target.find( '#bbp_editor_topic_content_' + editor_key );
				$editor.removeClass( 'error' );
				$medium_editor.setContent( '' );
				target.find( '#bbp_topic_content' ).val( '' );
				target.find( '#bbp_topic_title' ).val( '' );
			} else if ( 'reply' === this.topic_reply_draft.object ) {
				$medium_editor = window.forums_medium_reply_editor[editor_key];

				$editor = target.find( '#bbp_editor_reply_content_' + editor_key );
				$editor.removeClass( 'error' );
				$medium_editor.setContent( '' );
				target.find( '#bbp_reply_content' ).val( '' );
				setTimeout(
					function () {
						$editor.removeClass( 'error' );
					},
					300
				);
			}

			// Reset topic subscription.
			if ( 'topic' === this.topic_reply_draft.object && ! $( '#subscribe-' + this.bbp_forum_id ).hasClass( 'is-subscribed' ) ) {
				target.find( '#bbp_topic_subscription' ).prop( 'checked', false );
			} else if ( 'reply' === this.topic_reply_draft.object && ! $( '#subscribe-' + this.bbp_topic_id ).hasClass( 'is-subscribed' ) ) {
				target.find( '#bbp_topic_subscription' ).prop( 'checked', false );
			}

			// Reset tags.
			target.find( '#bbp_topic_tags' ).val( '' );
			target.find( '#bbp_topic_tags_dropdown' ).val( '' );
			target.find( '#bbp_topic_tags_dropdown' ).trigger( 'change' );

			// Reset media.
			target.find( '#bbp_media' ).val( '' );
			if ( 'undefined' !== typeof media_dropzone_container.length && 0 < media_dropzone_container.length ) {
				var media_dropzone_obj_key = media_dropzone_container.data( 'key' );
				bp.Nouveau.Media.resetForumsMediaComponent( media_dropzone_obj_key );

				if ( target.find( '#forums-media-button' ) ) {
					target.find( '#forums-media-button' ).parents( '.post-elements-buttons-item' ).removeClass( 'disable no-click' );
				}
			}

			// Reset document.
			target.find( '#bbp_document' ).val( '' );
			if ( 'undefined' !== typeof document_dropzone_container.length && 0 < document_dropzone_container.length ) {
				var document_dropzone_obj_key = document_dropzone_container.data( 'key' );
				bp.Nouveau.Media.resetForumsDocumentComponent( document_dropzone_obj_key );

				if ( target.find( '#forums-document-button' ) ) {
					target.find( '#forums-document-button' ).parents( '.post-elements-buttons-item' ).removeClass( 'disable no-click' );
				}
			}

			// Reset video.
			target.find( '#bbp_video' ).val( '' );
			if ( 'undefined' !== typeof video_dropzone_container.length && 0 < video_dropzone_container.length ) {
				var video_dropzone_obj_key = video_dropzone_container.data( 'key' );
				bp.Nouveau.Media.resetForumsVideoComponent( video_dropzone_obj_key );

				if ( target.find( '#forums-video-button' ) ) {
					target.find( '#forums-video-button' ).parents( '.post-elements-buttons-item' ).removeClass( 'disable no-click' );
				}
			}

			// Reset GIF.
			if ( 'undefined' !== typeof document_dropzone_container.length && 0 < document_dropzone_container.length ) {
				target.find( '#whats-new-toolbar #forums-gif-button' ).removeClass( 'active' );
				target.find( '.gif-media-search-dropdown' ).removeClass( 'open' );
				if ( gif_attached_container.length ) {
					gif_attached_container.addClass( 'closed' );
					gif_attached_container.find( '.gif-image-container img' ).attr( 'src', '' );
					gif_attached_container[ 0 ].style = '';
					target.find( '#forums-gif-button' ).parents( '.post-elements-buttons-item' ).removeClass( 'disable no-click' );
				}

				if ( target.find( '#bbp_media_gif' ).length ) {
					target.find( '#bbp_media_gif' ).val( '' );
				}
			}

			this.topic_reply_draft.data               = false;
			bp.Nouveau.Media.reply_topic_display_post = 'edit';

			// Reset the form.
			target[0].reset();

			// Remove class to display draft.
			target.removeClass( 'has-draft' );
		},

		collectTopicReplyDraftActivity: function() {
			var form = $( '#new-post' ), meta = {};

			_.each(
				form.serializeArray(),
				function( pair ) {
					pair.name = pair.name.replace( '[]', '' );
					if ( - 1 === _.indexOf(
						[
						'_wpnonce',
						'_wp_http_referer',
						'_bbp_unfiltered_html_reply',
						'redirect_to',
						'_bbp_unfiltered_html_topic',
						],
						pair.name
					) ) {
						if ( 'undefined' === typeof meta[ pair.name ] ) {
							meta[ pair.name ] = pair.value;
						} else {
							if ( ! _.isArray( meta[ pair.name ] ) ) {
								meta[ pair.name ] = [meta[ pair.name ]];
							}

							meta[ pair.name ].push( pair.value );
						}
					}
				}
			);

			if ( 'undefined' === typeof meta.bbp_topic_subscription ) {
				meta.bbp_topic_subscription = '';
			}

			var media_valid = false;
			if ( 'undefined' !== typeof meta.bbp_media && ( '' !== meta.bbp_media && '[]' !== meta.bbp_media ) ) {
				media_valid = true;
			}
			if ( 'undefined' !== typeof meta.bbp_document && ( '' !== meta.bbp_document && '[]' !== meta.bbp_document ) ) {
				media_valid = true;
			}
			if ( 'undefined' !== typeof meta.bbp_video && ( '' !== meta.bbp_video && '[]' !== meta.bbp_video ) ) {
				media_valid = true;
			}
			if ( 'undefined' !== typeof meta.bbp_media_gif && ( '' !== meta.bbp_media_gif && '[]' !== meta.bbp_media_gif ) ) {
				media_valid = true;
			}

			var content_valid = true;
			if ( 'topic' === this.topic_reply_draft.object && 'undefined' !== typeof meta.bbp_topic_content && '' === $( $.parseHTML( meta.bbp_topic_content ) ).text().trim() && ! media_valid ) {
				content_valid = false;
			} else if ( 'reply' === this.topic_reply_draft.object && 'undefined' !== typeof meta.bbp_reply_content && '' === $( $.parseHTML( meta.bbp_reply_content ) ).text().trim() && ! media_valid ) {
				content_valid = false;
			}

			if ( content_valid ) {

				if ( 'undefined' !== typeof meta.bbp_video && '' !== meta.bbp_video ) {
					var new_videos = JSON.parse( meta.bbp_video );

					var filtered_new_videos = new_videos.filter(
						function ( item ) {
							if ( 'undefined' !== typeof item.js_preview ) {
								delete item.js_preview;
							}
							return item;
						}
					);

					meta.bbp_video = JSON.stringify( filtered_new_videos );
				}

				var old_draft_data = {};
				if ( 'undefined' !== typeof this.all_draft_data[this.topic_reply_draft.data_key] ) {
					old_draft_data = this.all_draft_data[this.topic_reply_draft.data_key];
				}

				bp.Nouveau.TopicReplyDraft.checkedTopicReplyDataChanged( old_draft_data, meta );

				this.topic_reply_draft.data                          = meta;
				this.all_draft_data[this.topic_reply_draft.data_key] = meta;
				this.topic_reply_draft.is_content_valid              = true;
				localStorage.setItem( this.topic_reply_draft.data_key, JSON.stringify( this.topic_reply_draft ) );
			} else {
				if ( 'undefined' !== typeof this.all_draft_data[this.topic_reply_draft.data_key] ) {
					delete this.all_draft_data[this.topic_reply_draft.data_key];
				}
				this.topic_reply_draft.data = false;
				localStorage.removeItem( this.topic_reply_draft.data_key );
			}
		},

		checkedTopicReplyDataChanged: function( old_data, new_data ) {
			var draft_data_keys = [
				'bbp_topic_title',
				'bbp_topic_content',
				'bbp_stick_topic',
				'bbp_topic_tags',
				'bbp_reply_content',
				'bbp_media',
				'bbp_document',
				'bbp_video',
				'bbp_media_gif',
				'link_embed',
				'link_description',
				'link_image',
				'link_title',
				'link_url'
			];

			_.each(
				draft_data_keys,
				function( pair ) {

					if ( 'undefined' !== typeof old_data[ pair ] && 'undefined' === typeof new_data[ pair ] ) {
						bp.Nouveau.TopicReplyDraft.draft_content_changed = true;
					} else if ( 'undefined' === typeof old_data[ pair ] && 'undefined' !== typeof new_data[ pair ] ) {
						bp.Nouveau.TopicReplyDraft.draft_content_changed = true;
					} else if ( 'undefined' !== typeof old_data[ pair ] && 'undefined' !== typeof new_data[ pair ] ) {
						if ( - 1 !== _.indexOf(
							[
								'bbp_topic_content',
								'bbp_reply_content',
							],
							pair
						) ) {

							if ( $( $.parseHTML( old_data[ pair ] ) ).text().trim() !== $( $.parseHTML( new_data[ pair ] ) ).text().trim() ) {
								bp.Nouveau.TopicReplyDraft.draft_content_changed = true;
							}
						} else if ( old_data[ pair ] !== new_data[ pair ] ) {
							bp.Nouveau.TopicReplyDraft.draft_content_changed = true;
						}

					}
				}
			);
		},

		postTopicReplyDraft: function( is_force_saved, is_reload_window, is_send_all_data ) {
			if ( ! is_force_saved && 'undefined' === typeof this.all_draft_data[this.topic_reply_draft.data_key] ) {
				return;
			}

			// Checked the content changed or not.
			if ( ! is_force_saved && ! bp.Nouveau.TopicReplyDraft.draft_content_changed ) {
				return;
			}

			this.topic_reply_draft.data = this.all_draft_data[this.topic_reply_draft.data_key];

			if ( ! is_reload_window ) {
				if ( this.draft_ajax_request ) {
					this.draft_ajax_request.abort();
				}

				var draft_data = {
					_wpnonce_post_topic_reply_draft: BP_Nouveau.forums.nonces.post_topic_reply_draft,
					action: 'post_topic_reply_draft',
					draft_topic_reply: this.topic_reply_draft
				};

				// Send data to server.
				this.draft_ajax_request = $.ajax(
					{
						type: 'POST',
						url: BP_Nouveau.ajaxurl,
						data: draft_data,
						async: false,
						success: function() {}
					}
				);

			} else {

				// If current screen is not edit screen then send request.
				var formData = new FormData();
				formData.append( '_wpnonce_post_topic_reply_draft', BP_Nouveau.forums.nonces.post_topic_reply_draft );
				formData.append( 'action', 'post_topic_reply_draft' );
				formData.append( 'draft_topic_reply', JSON.stringify( this.topic_reply_draft ) );

				if ( is_send_all_data ) {
					formData.append( 'all_data', JSON.stringify( this.all_draft_data ) );
				}

				navigator.sendBeacon( BP_Nouveau.ajaxurl, formData );
			}

			// Set false after send request to server.
			bp.Nouveau.TopicReplyDraft.draft_content_changed = false;
		},

		displayTopicReplyDraft: function() {
			bp.Nouveau.Media.reply_topic_allow_delete_media = true;
			if ( 'topic' === this.topic_reply_draft.object ) {
				bp.Nouveau.TopicReplyDraft.appendTopicDraftData();
			} else {
				bp.Nouveau.TopicReplyDraft.appendReplyDraftData();
			}
		},

		appendTopicDraftData: function() {
			bp.Nouveau.TopicReplyDraft.getTopicReplyDraftData();

			var $form         = $( 'form#new-post' ),
				activity_data = {},
				editor_key    = $form.find( '.bbp-the-content' ).data( 'key' ),
				$editor       = $form.find( '#bbp_editor_topic_content_' + editor_key );

			if ( 'undefined' !== typeof this.all_draft_data[this.topic_reply_draft.data_key] ) {
				activity_data = this.all_draft_data[this.topic_reply_draft.data_key];
			}

			if ( 'undefined' === typeof activity_data.bbp_topic_title && 'undefined' === typeof activity_data.bbp_topic_content ) {
				return;
			}

			// Add class to display draft.
			$form.addClass( 'has-draft' );

			// Title.
			if ( 'undefined' !== typeof activity_data.bbp_topic_title ) {
				$form.find( '#bbp_topic_title' ).val( activity_data.bbp_topic_title );
			}

			// Content.
			if ( 'undefined' !== typeof activity_data.bbp_topic_content ) {
				$editor.html( activity_data.bbp_topic_content );
				var element = $editor.get( 0 );
				element.focus();
				$form.find( '#bbp_topic_content' ).val( activity_data.bbp_topic_content );
			}

			// Stick topic.
			$form.find( '#bbp_stick_topic_select option[value="' + activity_data.bbp_stick_topic + '"]' ).prop( 'selected', true );

			// Subscribe notify.
			if ( 'undefined' !== typeof activity_data.bbp_topic_subscription && '' !== activity_data.bbp_topic_subscription ) {
				$form.find( '#bbp_topic_subscription' ).prop( 'checked', true );
			} else if ( 'undefined' !== typeof activity_data.bbp_topic_subscription && '' === activity_data.bbp_topic_subscription ) {
				$form.find( '#bbp_topic_subscription' ).prop( 'checked', false );
			} else if ( 0 < this.bbp_forum_id && ! $( '#subscribe-' + this.bbp_forum_id ).hasClass( 'is-subscribed' ) ) {
				$form.find( '#bbp_topic_subscription' ).prop( 'checked', false );
			}

			// Tags.
			if ( 'undefined' !== typeof activity_data.bbp_topic_tags && '' !== activity_data.bbp_topic_tags ) {

				$form.find( '#bbp_topic_tags' ).val( activity_data.bbp_topic_tags );

				var tags_element = $form.find( '#bbp_topic_tags_dropdown' );

				_.each(
					activity_data.bbp_topic_tags.split( ',' ),
					function( val ) {
						tags_element.append( new Option( val, val, false, true ) );
					}
				);

				tags_element.trigger( 'change' );
			}

			this.previewDraftMedia( $form, activity_data );
		},

		appendReplyDraftData: function() {
			bp.Nouveau.TopicReplyDraft.getTopicReplyDraftData();

			var $form         = $( 'form#new-post' ),
				activity_data = {},
				editor_key    = $form.find( '.bbp-the-content' ).data( 'key' ),
				$editor       = $form.find( '#bbp_editor_reply_content_' + editor_key );

			if ( 'undefined' !== typeof this.all_draft_data[this.topic_reply_draft.data_key] ) {
				activity_data = this.all_draft_data[this.topic_reply_draft.data_key];
			}

			if ( 'undefined' === typeof activity_data.bbp_reply_content ) {
				return;
			}

			// Add class to display draft.
			$form.addClass( 'has-draft' );

			// Content.
			if ( 'undefined' !== typeof activity_data.bbp_reply_content ) {
				$editor.html( activity_data.bbp_reply_content );
				var element = $editor.get( 0 );
				element.focus();
				$form.find( '#bbp_reply_content' ).val( activity_data.bbp_reply_content );
			}

			// Subscribe notify.
			if ( 'undefined' !== typeof activity_data.bbp_topic_subscription && '' !== activity_data.bbp_topic_subscription ) {
				$form.find( '#bbp_topic_subscription' ).prop( 'checked', true );
			} else if ( 'undefined' !== typeof activity_data.bbp_topic_subscription && '' === activity_data.bbp_topic_subscription ) {
				$form.find( '#bbp_topic_subscription' ).prop( 'checked', false );
			} else if ( 0 < this.bbp_topic_id && ! $( '#subscribe-' + this.bbp_topic_id ).hasClass( 'is-subscribed' ) ) {
				$form.find( '#bbp_topic_subscription' ).prop( 'checked', false );
			}

			// Tags.
			if ( 'undefined' !== typeof activity_data.bbp_topic_tags && '' !== activity_data.bbp_topic_tags ) {

				$form.find( '#bbp_topic_tags' ).val( activity_data.bbp_topic_tags );

				var tags_element = $form.find( '#bbp_topic_tags_dropdown' );

				_.each(
					activity_data.bbp_topic_tags.split( ',' ),
					function( val ) {
						tags_element.append( new Option( val, val, false, true ) );
					}
				);

				tags_element.trigger( 'change' );
			}

			bp.Nouveau.TopicReplyDraft.previewDraftMedia( $form, activity_data );
		},

		previewDraftMedia: function( $form, activity_data ) {
			var self                        = bp.Nouveau.Media,
				dropzone_media_container    = $form.find( '#forums-post-media-uploader' ),
				dropzone_document_container = $form.find( '#forums-post-document-uploader' ),
				dropzone_video_container    = $form.find( '#forums-post-video-uploader' ),
				gif_container               = $form.find( '#whats-new-attachments .forums-attached-gif-container' );

			// Media.
			if ( 'undefined' !== typeof dropzone_media_container.length && 0 < dropzone_media_container.length && 'undefined' !== typeof activity_data.bbp_media && '' !== activity_data.bbp_media ) {
				$form.find( '#bbp_media' ).val( activity_data.bbp_media );
				var draft_medias = JSON.parse( activity_data.bbp_media );

				if ( draft_medias.length ) {
					$form.find( 'a#forums-media-button' ).trigger( 'click' );

					var m_mock_file        = false,
						m_dropzone_obj_key = dropzone_media_container.data( 'key' );
					for ( var i = 0; i < draft_medias.length; i++ ) {
						m_mock_file = false;
						self.dropzone_media[ m_dropzone_obj_key ].push(
							{
								'id': draft_medias[ i ].id,
								'media_id': 0,
								'name': draft_medias[ i ].name,
								'thumb': draft_medias[ i ].thumb,
								'url': draft_medias[ i ].url,
								'uuid': draft_medias[ i ].uuid,
								'menu_order': draft_medias[ i ].menu_order,
								'saved': false
							}
						);

						m_mock_file = {
							name: draft_medias[ i ].name,
							accepted: true,
							kind: 'image',
							upload: {
								filename: draft_medias[ i ].name,
								uuid: draft_medias[ i ].uuid
							},
							dataURL: draft_medias[ i ].url,
							id: draft_medias[ i ].id
						};

						self.dropzone_obj[ m_dropzone_obj_key ].files.push( m_mock_file );
						self.dropzone_obj[ m_dropzone_obj_key ].emit( 'addedfile', m_mock_file );
						self.createThumbnailFromUrl( m_mock_file, dropzone_media_container );
						self.dropzone_obj[ m_dropzone_obj_key ].emit( 'dz-success', m_mock_file );
						self.dropzone_obj[ m_dropzone_obj_key ].emit( 'dz-complete', m_mock_file );
					}
					self.addMediaIdsToForumsForm( dropzone_media_container );

					// Disable other buttons( document/gif ).
					if ( ! _.isNull( self.dropzone_obj[ m_dropzone_obj_key ].files ) && self.dropzone_obj[ m_dropzone_obj_key ].files.length !== 0 ) {
						if ( $form.find( '#forums-document-button' ) ) {
							$form.find( '#forums-document-button' ).parents( '.post-elements-buttons-item' ).addClass( 'disable' );
						}
						if ( $form.find( '#forums-video-button' ) ) {
							$form.find( '#forums-video-button' ).parents( '.post-elements-buttons-item' ).addClass( 'disable' );
						}
						if ( $form.find( '#forums-gif-button' ) ) {
							$form.find( '#forums-gif-button' ).parents( '.post-elements-buttons-item' ).addClass( 'disable' );
						}
						if ( $form.find( '#forums-media-button' ) ) {
							$form.find( '#forums-media-button' ).parents( '.post-elements-buttons-item' ).addClass( 'no-click' );
						}
					}

				}
			}

			// Document.
			if ( 'undefined' !== typeof dropzone_document_container.length && 0 < dropzone_document_container.length && 'undefined' !== typeof activity_data.bbp_document && '' !== activity_data.bbp_document ) {
				$form.find( '#bbp_document' ).val( activity_data.bbp_document );
				var draft_documents = JSON.parse( activity_data.bbp_document );

				if ( draft_documents.length ) {
					$form.find( 'a#forums-document-button' ).trigger( 'click' );

					var d_mock_file        = false,
						d_dropzone_obj_key = dropzone_document_container.data( 'key' );
					for ( var d = 0; d < draft_documents.length; d++ ) {
						d_mock_file = false;
						self.dropzone_media[ d_dropzone_obj_key ].push(
							{
								'id': draft_documents[ d ].id,
								'document_id': 0,
								'name': draft_documents[ d ].full_name,
								'full_name': draft_documents[ d ].full_name,
								'type': 'document',
								'title': draft_documents[ d ].name,
								'size': draft_documents[ d ].size,
								'url': draft_documents[ d ].url,
								'uuid': draft_documents[ d ].uuid,
								'menu_order': draft_documents[ d ].menu_order,
								'saved': false
							}
						);

						d_mock_file = {
							name: draft_documents[ d ].full_name,
							size: draft_documents[ d ].size,
							accepted: true,
							kind: 'document',
							upload: {
								name: draft_documents[ d ].full_name,
								title: draft_documents[ d ].name,
								filename: draft_documents[ d ].full_name,
								size: draft_documents[ d ].size,
								uuid: draft_documents[ d ].uuid
							},
							dataURL: draft_documents[ d ].url,
							id: draft_documents[ d ].id
						};

						self.dropzone_obj[ d_dropzone_obj_key ].files.push( d_mock_file );
						self.dropzone_obj[ d_dropzone_obj_key ].emit( 'addedfile', d_mock_file );
						self.dropzone_obj[ d_dropzone_obj_key ].emit( 'dz-success', d_mock_file );
						self.dropzone_obj[ d_dropzone_obj_key ].emit( 'complete', d_mock_file );
					}
					self.addDocumentIdsToForumsForm( dropzone_document_container );

					// Disable other buttons( media/gif ).
					if ( ! _.isNull( self.dropzone_obj[ d_dropzone_obj_key ].files ) && self.dropzone_obj[ d_dropzone_obj_key ].files.length !== 0 ) {
						if ( $form.find( '#forums-media-button' ) ) {
							$form.find( '#forums-media-button' ).parents( '.post-elements-buttons-item' ).addClass( 'disable' );
						}
						if ( $form.find( '#forums-video-button' ) ) {
							$form.find( '#forums-video-button' ).parents( '.post-elements-buttons-item' ).addClass( 'disable' );
						}
						if ( $form.find( '#forums-gif-button' ) ) {
							$form.find( '#forums-gif-button' ).parents( '.post-elements-buttons-item' ).addClass( 'disable' );
						}
						if ( $form.find( '#forums-document-button' ) ) {
							$form.find( '#forums-document-button' ).parents( '.post-elements-buttons-item' ).addClass( 'no-click' );
						}
					}

				}
			}

			// Video.
			if ( 'undefined' !== typeof dropzone_video_container.length && 0 < dropzone_video_container.length && 'undefined' !== typeof activity_data.bbp_video && '' !== activity_data.bbp_video ) {
				$form.find( '#bbp_video' ).val( activity_data.bbp_video );
				var draft_videos = JSON.parse( activity_data.bbp_video );

				if ( draft_videos.length ) {
					$form.find( 'a#forums-video-button' ).trigger( 'click' );

					var v_mock_file        = false,
						v_dropzone_obj_key = dropzone_video_container.data( 'key' );
					for ( var v = 0; v < draft_videos.length; v++ ) {
						v_mock_file = false;
						self.dropzone_media[ v_dropzone_obj_key ].push(
							{
								'id': draft_videos[ v ].id,
								'video_id': 0,
								'name': draft_videos[ v ].name,
								'type': 'video',
								'title': draft_videos[ v ].name,
								'size': draft_videos[ v ].size,
								'url': draft_videos[ v ].url,
								'uuid': draft_videos[ v ].uuid,
								'thumb': draft_videos[ v ].thumb,
								'menu_order': draft_videos[ v ].menu_order,
								'saved': false,
							}
						);

						v_mock_file = {
							name: draft_videos[ v ].name,
							size: draft_videos[ v ].size,
							accepted: true,
							kind: 'video',
							upload: {
								name: draft_videos[ v ].name,
								title: draft_videos[ v ].name,
								size: draft_videos[ v ].size,
								uuid: draft_videos[ v ].uuid
							},
							dataURL: draft_videos[ v ].url,
							dataThumb: draft_videos[ v ].thumb,
							id: draft_videos[ v ].id
						};

						self.dropzone_obj[ v_dropzone_obj_key ].files.push( v_mock_file );
						self.dropzone_obj[ v_dropzone_obj_key ].emit( 'addedfile', v_mock_file );
						self.dropzone_obj[ v_dropzone_obj_key ].emit( 'dz-success', v_mock_file );
						self.dropzone_obj[ v_dropzone_obj_key ].emit( 'complete', v_mock_file );
					}
					self.addVideoIdsToForumsForm( dropzone_video_container );

					// Disable other buttons( media/gif ).
					if ( ! _.isNull( self.dropzone_obj[ v_dropzone_obj_key ].files ) && self.dropzone_obj[ v_dropzone_obj_key ].files.length !== 0 ) {
						if ( $form.find( '#forums-media-button' ) ) {
							$form.find( '#forums-media-button' ).parents( '.post-elements-buttons-item' ).addClass( 'disable' );
						}
						if ( $form.find( '#forums-gif-button' ) ) {
							$form.find( '#forums-gif-button' ).parents( '.post-elements-buttons-item' ).addClass( 'disable' );
						}
						if ( $form.find( '#forums-document-button' ) ) {
							$form.find( '#forums-document-button' ).parents( '.post-elements-buttons-item' ).addClass( 'disable' );
						}
						if ( $form.find( '#forums-video-button' ) ) {
							$form.find( '#forums-video-button' ).parents( '.post-elements-buttons-item' ).addClass( 'no-click' );
						}
					}

				}
			}

			// GIF.
			if ( 'undefined' !== typeof gif_container.length && 0 < gif_container.length && 'undefined' !== typeof activity_data.bbp_media_gif && '' !== activity_data.bbp_media_gif ) {
				var draft_gif = JSON.parse( activity_data.bbp_media_gif );

				if ( 'undefined' !== typeof draft_gif.images ) {
					$form.find( 'a#forums-gif-button' ).trigger( 'click' );
					gif_container[ 0 ].style.backgroundImage = 'url(' + draft_gif.images.fixed_width.url + ')';
					gif_container[ 0 ].style.backgroundSize  = 'contain';
					gif_container[ 0 ].style.height          = draft_gif.images.original.height + 'px';
					gif_container[ 0 ].style.width           = draft_gif.images.original.width + 'px';
					gif_container.find( '.gif-image-container img' ).attr( 'src', draft_gif.images.original.url );
					gif_container.removeClass( 'closed' );
					if ( $( '#bbp_media_gif' ).length ) {
						$( '#bbp_media_gif' ).val( JSON.stringify( draft_gif ) );
						if ( $form.find( '#forums-document-button' ) ) {
							$form.find( '#forums-document-button' ).parents( '.post-elements-buttons-item' ).addClass( 'disable' );
						}
						if ( $form.find( '#forums-video-button' ) ) {
							$form.find( '#forums-video-button' ).parents( '.post-elements-buttons-item' ).addClass( 'disable' );
						}
						if ( $form.find( '#forums-gif-button' ) ) {
							$form.find( '#forums-gif-button' ).parents( '.post-elements-buttons-item' ).addClass( 'no-click' );
						}
						if ( $form.find( '#forums-media-button' ) ) {
							$form.find( '#forums-media-button' ).parents( '.post-elements-buttons-item' ).addClass( 'disable' );
						}
					}
				}
			}

		},

		submitTopicReplyDraftForm: function() {
			this.topic_reply_draft.post_action = 'delete';
			this.clearTopicReplyDraftIntervals();
			this.resetLocalTopicReplyDraft();
		},

		discardTopicReplyDraftForm: function() {
			var forum_topic = $( 'a[data-modal-id]' ),
				forum_reply = $( '.bbp-reply-to-link' );

			forum_topic.css( 'pointer-events', 'none' );
			forum_reply.css( 'pointer-events', 'none' );

			this.topic_reply_draft.post_action = 'delete';
			this.postTopicReplyDraft( true, true, false );
			this.clearTopicReplyDraftIntervals();
			this.resetLocalTopicReplyDraft();
			this.resetTopicReplyDraftPostForm();
			this.topic_reply_draft.post_action = 'update';
			bp.Nouveau.TopicReplyDraft.setupTopicReplyDraftIntervals();
			bp.Nouveau.TopicReplyDraft.is_topic_reply_form_submit = false;

			forum_topic.css( 'pointer-events', '' );
			forum_reply.css( 'pointer-events', '' );
		},

		setupOnReloadWindow: function() {
			if ( 'update' === bp.Nouveau.TopicReplyDraft.topic_reply_draft.post_action ) {
				// Clear intervals.
				bp.Nouveau.TopicReplyDraft.clearTopicReplyDraftIntervals();
				// Collect draft data.
				bp.Nouveau.TopicReplyDraft.collectTopicReplyDraftActivity();
				// Send latest draft data.
				bp.Nouveau.TopicReplyDraft.postTopicReplyDraft( false, true, true );
			}
		}
	};

	// Launch BP Nouveau Media.
	bp.Nouveau.TopicReplyDraft.start();

} )( bp, jQuery );
