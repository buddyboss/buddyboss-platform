/* global bp */
/**
 * Represents a Forum, Topic, and Reply Draft manager.
 *
 * This function provides functionality to manage and interact with a forum, topic reply draft.
 * It allows users to create, update, and retrieve draft data.
 *
 * @constructor
 * @param {jQuery} currentForm - The jQuery object representing the current form element.
 */

window.bp = window.bp || {};
( function ( exports, $ ) {

	// Bail if not set.
	if ( typeof BP_Nouveau === 'undefined' ) {
		return;
	}

	bp.Nouveau       = bp.Nouveau || {};
	bp.Nouveau.Media = bp.Nouveau.Media || {};

	bp.Nouveau.TopicReplyDraft = function(currentForm) {
		if ( ! currentForm ) {
			currentForm = $( '#new-post' );
		}

		this.currentForm = currentForm;

		/**
		 * Start the TopicReplyDraft and initialize event listeners and global variables.
		 *
		 * @function start
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @return {void}
		 */
		this.start = function() {
			// Check the user is logged or not.
			if ( 'undefined' === typeof BP_Nouveau.forums.params.bb_current_user_id || 0 === parseInt( BP_Nouveau.forums.params.bb_current_user_id ) ) {
				return;
			}

			this.setupGlobals();
			this.snapshotInitialContent();
			this.addListeners();
			this.setupPasteImageGuard();

			// The read failed and the input lock was just released: say so now,
			// not at the first autosave tick 20 s later, when the member has
			// already typed into a form whose saves will be refused (N-2).
			if ( true === BP_Nouveau.forums.draft_fetch_failed ) {
				this.showDraftFeedback( BP_Nouveau.forums.draft_fetch_failed_message || '' );
			}
		};

		/**
		 * Record what the form already contained before the member touched it.
		 *
		 * hasMemberTypedContent() has to distinguish "the member typed while the
		 * draft fetch was in flight" from "this form arrived pre-filled". A
		 * reply-to-reply, a quote, or a mention prefix is content the member did
		 * NOT type, and treating it as typed suppresses a legitimate restore
		 *.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @return {void}
		 */
		this.snapshotInitialContent = function () {
			var $form    = this.draftNoticeForm(),
				captured = $form.length ? $form.data( 'bbDraftInitialSnapshot' ) : undefined;

			// On the lazy-fetch path start() runs inside .always(), i.e. AFTER
			// the request resolved, so snapshotting here would already contain
			// anything the member typed while it was in flight -
			// hasMemberTypedContent() would then compare equal, conclude nothing
			// was typed, and the restore would overwrite their text. That is the
			// one window this guard exists for, so the capture taken before the
			// request went out is authoritative when present.
			this.initial_content_snapshot = ( 'undefined' !== typeof captured ) ? captured : this.currentContentSnapshot();
		};

		/**
		 * Current editable content of this form, as one comparable string.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @return {string}
		 */
		this.currentContentSnapshot = function () {
			return bbDraftFormSnapshot( this.draftNoticeForm() );
		};

		/**
		 * Set up global variables and data for the TopicReplyDraft instance.
		 *
		 * @function setupGlobals
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @return {void}
		 */
		this.setupGlobals = function() {
			// Draft variables.
			this.bbp_forum_id               = false;
			this.bbp_topic_id               = false;
			this.bbp_reply_to               = false;
			this.is_bb_theme                = $( 'body' ).hasClass( 'buddyboss-theme' );
			this.topic_reply_local_interval = false;
			this.topic_reply_ajax_interval  = false;
			this.draft_ajax_request         = null;
			this.draft_inflight             = null;
			this.is_topic_reply_form_submit = false;
			this.draft_content_changed      = false;
			this.all_draft_data             = {};
			this.draft_base_map             = {};
			this.draft_pending_keys         = {};
			this.draft_conflict_keys        = {};
			this.draft_blocked_keys         = {};
			this.draft_discard_copies       = {};
			this.bbp_lead_topic_tags        = '';
			this.bp_nouveau_forums_data     = ( 'undefined' !== typeof BP_Nouveau.forums.draft ) ? BP_Nouveau.forums.draft : {};
			this.topic_reply_draft          = {
				object: false,
				data_key: false,
				data: false,
				post_action: 'update',
				is_content_valid: false,
			};

			// Set object and key for draft.
			var newPostEvent = new Event( this.currentForm );
			this.setupTopicReplyDraftKeys( newPostEvent );
			this.getTopicReplyDraftData();
			this.syncTopicReplyDraftData();
		};

		/**
		 * Add event listeners for managing topic reply drafts.
		 *
		 * @function addListeners
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @return {void}
		 */
		this.addListeners = function() {
			var self = this;
			// Set up the draft keys/intervals/display data when BuddyBoss theme is enabled.
			if ( this.is_bb_theme ) {
				this.currentForm.closest( '.bbp-topic-form' ).on(
					'bbp_after_load_topic_form',
					function () {
						self.setupOnOpenTopicReplyModal();
					}
				);

				this.currentForm.closest( '.bbp-reply-form' ).on(
					'bbp_after_load_reply_form',
					function () {
						self.setupOnOpenTopicReplyModal();
					}
				);

				this.currentForm.closest( '.bbp-reply-form' ).on(
					'bbp_after_load_inline_reply_form',
					function () {
						self.setupOnOpenTopicReplyModal();
					}
				);

				this.currentForm.find( '.js-modal-close' ).on(
					'bbp_after_close_topic_reply_form',
					function () {
						self.clearOnCloseTopicReplyModal();
					}
				);

				$( document ).on(
					'bbp_after_close_topic_reply_form_on_overlay',
					function () {
						self.clearOnCloseTopicReplyModal();
					}
				);

				// start() is deferred behind the lazy draft fetch, so on a slow
				// connection the member can already have the composer OPEN by
				// the time the handlers above exist. jQuery does not replay an
				// `bbp_after_load_reply_form` that has already fired, so the
				// draft was never offered and the autosave intervals never
				// started for the rest of that modal session: an empty composer
				// over a draft the member does have, and nothing they typed
				// saved either. Running the same setup once, now, is what the
				// missed event would have done - and displayTopicReplyDraft()
				// still refuses to clobber anything typed in the meantime, via
				// hasMemberTypedContent() (Q12).
				if ( $( '.bb-modal-box' ).hasClass( 'bb-modal-open' ) ) {
					self.setupOnOpenTopicReplyModal();
				}
			} else {
				// The lazy draft fetch defers start() into an AJAX callback, and
				// jQuery does not replay an already-fired `load`. When the fetch
				// loses that race the intervals would never start and nothing the
				// member typed would be saved for the whole page load, so bind
				// only while `load` is still pending (H3).
				if ( 'complete' === document.readyState ) {
					self.setupTopicReplyDraftIntervals();
				} else {
					$( window ).on(
						'load',
						function () {
							self.setupTopicReplyDraftIntervals();
						}
					);
				}

				self.displayTopicReplyDraft();
			}

			if ( ! $( 'body' ).hasClass( 'activity' ) ) {
				// This will work only for Chrome.
				window.onbeforeunload = function ( event ) {
					if ( 'undefined' !== typeof event ) {
						self.setupOnReloadWindow();
					}
				};

				// This will work only for other browsers.
				window.unload = function ( event ) {
					if ( 'undefined' !== typeof event ) {
						self.setupOnReloadWindow();
					}
				};
			}

			// Submit the topic form.
			this.currentForm.on(
				'click',
				'#bbp_topic_submit',
				function () {
					self.submitTopicReplyDraftForm();
				}
			);

			// Submit the reply form.
			this.currentForm.on(
				'click',
				'#bbp_reply_submit',
				function () {
					self.submitTopicReplyDraftForm();
				}
			);

			this.currentForm.on(
				'click',
				'.bb_discard_topic_reply_draft',
				function () {
					self.discardTopicReplyDraftForm();
				}
			);
		};

		/**
		 * Set up necessary actions when opening the topic reply modal.
		 *
		 * @function setupOnOpenTopicReplyModal
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @return {void}
		 */
		this.setupOnOpenTopicReplyModal = function () {
			this.setupTopicReplyDraftKeys();
			this.getTopicReplyDraftData();
			this.syncTopicReplyDraftData();
			this.setupTopicReplyDraftIntervals();
			this.displayTopicReplyDraft();

			// Saving stopped earlier in this page view (conflict / not loaded):
			// closing took the notice down, so say it again on every open, even
			// with an empty composer - new text here is not saved either.
			if ( this.draftBlockMessage() ) {
				this.showDraftFeedback( this.draftBlockMessage() );
			}
		};

		/**
		 * Set up the keys and data for managing topic reply drafts.
		 *
		 * @function setupTopicReplyDraftKeys
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @return {void}
		 */
		this.setupTopicReplyDraftKeys = function () {
			if ( !this.currentForm ) {
				return;
			}

			if ( this.currentForm.find( '#bbp_forum_id' ).length > 0 ) {
				this.bbp_forum_id               = parseInt( this.currentForm.find( '#bbp_forum_id' ).val() );
				this.topic_reply_draft.object   = 'topic';
				this.topic_reply_draft.data_key = 'draft_topic';

				if ( 0 < this.bbp_forum_id ) {
					this.topic_reply_draft.data_key = 'draft_discussion_' + this.bbp_forum_id;
				}
			} else if ( this.currentForm.find( '#bbp_topic_id' ).length > 0 ) {
				this.bbp_topic_id               = parseInt( this.currentForm.find( '#bbp_topic_id' ).val() );
				this.bbp_reply_to               = parseInt( this.currentForm.find( '#bbp_reply_to' ).val() );
				this.topic_reply_draft.object   = 'reply';
				this.topic_reply_draft.data_key = 'draft_reply';

				if ( 0 < this.bbp_topic_id && 0 === this.bbp_reply_to ) {
					this.topic_reply_draft.data_key = 'draft_reply_' + this.bbp_topic_id;
				} else if ( 0 < this.bbp_topic_id && 0 < this.bbp_reply_to ) {
					this.topic_reply_draft.data_key = 'draft_reply_' + this.bbp_topic_id + '_' + this.bbp_reply_to;
				}
			}

			// Remove draft button for new reply if class already added on form.
			if ( this.currentForm.hasClass( 'has-draft' ) ) {
				this.currentForm.removeClass( 'has-draft' );
			}

			// Get the lead discussion tags.
			this.updateLeadDiscussionTagsInForm();

			if ( this.bbp_topic_id && forms.length > 1 ) {
				this.updateSubscriptionCheckboxes();
			}
		};

		/**
		 * Sync the topic and reply subscription checkboxes with the restored draft state.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @return {void}
		 */
		this.updateSubscriptionCheckboxes = function() {
			// Change the subscribe checkbox id and label for to make it workable for multiple forms and topics on a single page.
			var bbp_topic_subscription_id = this.currentForm.find( '#bbp_topic_subscription' );
			bbp_topic_subscription_id.prop( 'id', 'bbp_topic_subscription_' + this.bbp_topic_id );
			bbp_topic_subscription_id.siblings( 'label' ).prop( 'for', 'bbp_topic_subscription_' + this.bbp_topic_id );
		};

		/**
		 * Updates the attached lead discussion tags in the form.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @return {void}
		 */
		this.updateLeadDiscussionTagsInForm = function() {

			if ( !this.currentForm ) {
				return;
			}

			var topic_tags   = [];
			var tagSelector  = this.is_bb_theme ? '.item-tags ul li' : '.bbp-topic-tags p a';
			this.currentForm.closest( '#bbpress-forums' ).find( tagSelector ).each( function() {
				topic_tags.push( $(this).text().trim() );
			} );

			if ( topic_tags.length > 0 ) {
				this.bbp_lead_topic_tags = topic_tags.join( ',' );
			} else {
				this.bbp_lead_topic_tags = '';
			}

			this.currentForm.find( '#bbp_topic_tags' ).val( this.bbp_lead_topic_tags );
			var tags_element = this.currentForm.find( '#bbp_topic_tags_dropdown' );

			if ( '' !== this.bbp_lead_topic_tags ) {
				tags_element.find( 'option' ).remove();
				_.each(
					this.bbp_lead_topic_tags.split( ',' ),
					function( val ) {
						tags_element.append( new Option( val, val, false, true ) );
					}
				);
			}

			tags_element.trigger( 'change' );
		};

		/**
		 * Load the draft for the active form's key from localStorage into the working copy.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @return {Object} The current topic/reply draft object.
		 */
		this.getTopicReplyDraftData = function() {
			// After a conflict the local copy is the newer STORED draft
			// (handleDraftConflict()), not what the member wrote. Their text lives
			// only in all_draft_data (collectTopicReplyDraftActivity() on close),
			// so reading the local copy here put the other version in the
			// composer on reopen and the member's text was gone.
			if ( this.draft_conflict_keys[ this.topic_reply_draft.data_key ] ) {
				return this.topic_reply_draft;
			}

			if ( ! this.topic_reply_draft.data_key || '' !== this.topic_reply_draft.data_key ) {
				var draft_data = localStorage.getItem( this.topic_reply_draft.data_key );
				if ( ! _.isUndefined( draft_data ) && null !== draft_data && 0 < draft_data.length ) {
					// Parse data with JSON.
					var draft_activity_local_data = JSON.parse( draft_data );

					// The localStorage key is not scoped per user, so on a
					// shared/kiosk browser it can hold a DIFFERENT member's forum
					// draft. Server storage is per-user, but this cache is
					// per-browser - restoring it would leak one member's draft into
					// another's editor. Drop it when the stored owner is not the
					// current member (privacy, #7 - matches the activity packs).
					var stored_owner  = ( 'undefined' !== typeof draft_activity_local_data.bb_draft_owner ) ? parseInt( draft_activity_local_data.bb_draft_owner, 10 ) : 0;
					var current_owner = parseInt( BP_Nouveau.forums.params.bb_current_user_id, 10 );

					// Only a copy stamped with ANOTHER member is foreign. An ownerless
					// copy was written by the release client, which never stamped an
					// owner; dropping it deleted the member's unsynced forum draft on
					// the first load after the update (F-05). It is restored, as the
					// release client did and as the activity packs do, and is stamped
					// with this member on its next local save.
					if ( stored_owner && current_owner && stored_owner !== current_owner ) {
						localStorage.removeItem( this.topic_reply_draft.data_key );
					} else {
						this.topic_reply_draft.data                          = draft_activity_local_data.data;
						this.topic_reply_draft._draft_rev                    = draft_activity_local_data._draft_rev;
						this.all_draft_data[this.topic_reply_draft.data_key] = draft_activity_local_data.data;
						this.draft_base_map[this.topic_reply_draft.data_key] = bbDraftBaseFrom( draft_activity_local_data );

						// A handed-back or refused copy stays pending until a
						// save is confirmed (V11).
						if ( true === draft_activity_local_data.bb_draft_pending_sync ) {
							this.draft_pending_keys[this.topic_reply_draft.data_key] = true;
						}
					}
				}
			}

			return this.topic_reply_draft;
		};

		/**
		 * Persist the working draft to localStorage without letting a quota
		 * throw abort the caller.
		 *
		 * A media/video-heavy forum draft can exceed the localStorage quota, and
		 * a bare setItem() throws QuotaExceededError - which used to abort the
		 * composer-close / autosave handler before the server save ran, dropping
		 * BOTH the local and the server copy (H4). Store the full draft; on a
		 * quota error shed the video poster frames (kept only to regenerate a
		 * thumbnail on publish) and retry; if it still will not fit, skip the
		 * local copy silently - the periodic server save is the durable path.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @return {void}
		 */
		this.storeTopicReplyDraftLocal = function () {
			// Stamp the owner so a restore on a shared browser can reject another
			// member's draft (#7).
			// After a conflict the local copy is the newer STORED draft; the
			// composer's text must not replace it.
			if ( this.draft_conflict_keys[ this.topic_reply_draft.data_key ] ) {
				return;
			}

			this.topic_reply_draft.bb_draft_owner = parseInt( BP_Nouveau.forums.params.bb_current_user_id, 10 );
			// Text typed while the stored draft was still loading is based on
			// nothing; recording the stored revision let the next page view save
			// it over that draft without a conflict (D-02).
			this.topic_reply_draft._draft_base    = this.draft_blocked_keys[ this.topic_reply_draft.data_key ] ? [ 'none' ] : this.draftBaseFor( this.topic_reply_draft.data_key );
			this.topic_reply_draft._draft_local_at = Math.floor( Date.now() / 1000 );

			// When the content last changed, which the page-unload save does not
			// move: bbDraftHandbackDecision() compares it with a handed-back draft.
			// A first copy read from the server takes the server's save time.
			var bb_previous_copy = null;

			try {
				bb_previous_copy = JSON.parse( localStorage.getItem( this.topic_reply_draft.data_key ) || 'null' );
			} catch ( e ) {
				bb_previous_copy = null;
			}

			if ( bb_previous_copy && 'object' === typeof bb_previous_copy ) {
				this.topic_reply_draft._draft_edit_at = _.isEqual( bb_previous_copy.data, this.topic_reply_draft.data ) ?
					( parseInt( bb_previous_copy._draft_edit_at, 10 ) || 0 ) :
					Math.floor( Date.now() / 1000 );
			} else {
				this.topic_reply_draft._draft_edit_at = parseInt( this.topic_reply_draft._draft_saved_at, 10 ) || Math.floor( Date.now() / 1000 );
			}

			if ( this.draft_pending_keys[ this.topic_reply_draft.data_key ] ) {
				this.topic_reply_draft.bb_draft_pending_sync = true;
			} else {
				delete this.topic_reply_draft.bb_draft_pending_sync;
			}

			// Declared outside the try so the quota retry below can reuse the
			// stripped copy rather than re-serialising the base64 original.
			var draft_to_store = this.topic_reply_draft;

			try {
				// Strip inline base64 data: URLs before storing. The paste guard
				// converts pasted images to uploads, but a drag-and-drop drops a raw
				// base64 <img> straight into the editor, outside that guard - a single
				// dropped photo can be several MB and blow the per-origin localStorage
				// quota, whose failure the catch below silently swallows, disabling
				// local persistence for every draft on the origin. The server strips
				// these on save too, so neither store persists the blob (M19).
				var content_keys = [ 'bbp_topic_content', 'bbp_reply_content' ];

				for ( var c = 0; c < content_keys.length; c++ ) {
					var content_key = content_keys[c];

					if ( draft_to_store.data && 'string' === typeof draft_to_store.data[content_key] && /data:[^"']*base64/i.test( draft_to_store.data[content_key] ) ) {
						if ( draft_to_store === this.topic_reply_draft ) {
							draft_to_store = JSON.parse( JSON.stringify( this.topic_reply_draft ) );
						}

						draft_to_store.data[content_key] = draft_to_store.data[content_key].replace(
							/(\s(?:src|href)\s*=\s*)(["'])data:[^"']*base64[^"']*\2/gi,
							'$1$2$2'
						);
					}
				}

				localStorage.setItem( this.topic_reply_draft.data_key, JSON.stringify( draft_to_store ) );
			} catch ( quota_error ) {
				try {
					var slim = JSON.parse( JSON.stringify( draft_to_store ) );

					if (
						slim.data &&
						'undefined' !== typeof slim.data.bbp_video &&
						'' !== slim.data.bbp_video &&
						'[]' !== slim.data.bbp_video
					) {
						var videos = JSON.parse( slim.data.bbp_video );

						if ( _.isArray( videos ) ) {
							videos = _.map(
								videos,
								function ( item ) {
									return _.omit( item, 'js_preview' );
								}
							);
							slim.data.bbp_video = JSON.stringify( videos );
						}
					}

					localStorage.setItem( this.topic_reply_draft.data_key, JSON.stringify( slim ) );
				} catch ( retry_error ) {
					// Still over quota - skip the local cache this round; the
					// server save keeps the draft.
				}
			}
		};

		/**
		 * Seed the working draft from the server copy when localStorage holds nothing for the key.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @return {void}
		 */
		this.syncTopicReplyDraftData = function() {
			if ( this.draft_conflict_keys[ this.topic_reply_draft.data_key ] ) {
				return;
			}

			if (
				'undefined' === typeof this.bp_nouveau_forums_data ||
				'undefined' === typeof this.bp_nouveau_forums_data[this.topic_reply_draft.data_key]
			) {
				return;
			}

			// The local copy outranks the server copy only when it actually
			// holds something. Testing merely whether the key was DEFINED let an
			// empty local copy block the server copy unconditionally: the
			// composer opened blank over a live server draft, has-draft was
			// applied anyway, and the next autosave wrote that emptiness back
			// (Q12).
			//
			// A local copy WITH content still wins, exactly as before - the
			// member's most recent typing lives there and is not on the server
			// yet. No clock comparison is involved in either direction, so this
			// cannot mistake a skewed browser clock for a newer draft.
			if (
				'undefined' !== typeof this.all_draft_data[this.topic_reply_draft.data_key] &&
				bbDraftDataHasPayload( this.all_draft_data[this.topic_reply_draft.data_key] )
			) {
				return;
			}

			this.topic_reply_draft                               = this.bp_nouveau_forums_data[this.topic_reply_draft.data_key];
			this.all_draft_data[this.topic_reply_draft.data_key] = this.bp_nouveau_forums_data[this.topic_reply_draft.data_key].data;
			this.draft_base_map[this.topic_reply_draft.data_key] = [ this.topic_reply_draft._draft_rev || '' ];
			this.storeTopicReplyDraftLocal();
		};

		/**
		 * Start the periodic local and server autosave intervals for the open composer.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @return {void}
		 */
		this.setupTopicReplyDraftIntervals = function() {
			if ( this.is_bb_theme && $( '.bb-modal-box' ).hasClass( 'bb-modal-open' ) ) {
				if ( ! window.topic_reply_local_interval ) {
					window.topic_reply_local_interval = setInterval(
						function() {
							this.collectTopicReplyDraftActivity();
						}.bind( this ),
						3000
					);
				}

				if ( ! window.topic_reply_ajax_interval ) {
					window.topic_reply_ajax_interval = setInterval(
						function() {
							this.postTopicReplyDraft( false, false, false );
						}.bind( this ),
						20000
					);
				}
			} else if ( ! this.is_bb_theme ) {
				if ( ! window.topic_reply_local_interval ) {
					window.topic_reply_local_interval = setInterval(
						function() {
							this.collectTopicReplyDraftActivity();
						}.bind( this ),
						3000
					);
				}

				if ( ! window.topic_reply_ajax_interval ) {
					window.topic_reply_ajax_interval = setInterval(
						function() {
							this.postTopicReplyDraft( false, false, false );
						}.bind( this ),
						20000
					);
				}
			}
		};

		/**
		 * Clear the autosave intervals and reset transient state when the composer modal closes.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @return {void}
		 */
		this.clearOnCloseTopicReplyModal = function() {
			bp.Nouveau.Media.reply_topic_display_post = '';

			if ( ! this.is_topic_reply_form_submit ) {
				this.collectTopicReplyDraftActivity();
				this.postTopicReplyDraft( false, true, false );
			}

			this.clearTopicReplyDraftIntervals();
			setTimeout(
				function() {
					this.resetTopicReplyDraftLinkPreview();
					this.resetTopicReplyDraftPostForm();
				}.bind( this ),
				500
			);

			bp.Nouveau.Media.reply_topic_display_post = '';
			this.is_topic_reply_form_submit           = false;
		};

		/**
		 * Stop and clear the local and server autosave intervals.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @return {void}
		 */
		this.clearTopicReplyDraftIntervals = function() {
			clearInterval( window.topic_reply_local_interval );
			window.topic_reply_local_interval = false;

			clearInterval( window.topic_reply_ajax_interval );
			window.topic_reply_ajax_interval = false;
		};

		/**
		 * Drop the working draft and remove its localStorage copy.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @return {void}
		 */
		this.resetLocalTopicReplyDraft = function() {
			bp.Nouveau.Media.reply_topic_display_post = '';
			this.is_topic_reply_form_submit           = true;

			if ( 'undefined' !== typeof this.all_draft_data[this.topic_reply_draft.data_key] ) {
				delete this.all_draft_data[this.topic_reply_draft.data_key];
			}
			if ( 'undefined' !== typeof this.bp_nouveau_forums_data[this.topic_reply_draft.data_key] ) {
				delete this.bp_nouveau_forums_data[this.topic_reply_draft.data_key];
			}

			this.topic_reply_draft.data = false;
			localStorage.removeItem( this.topic_reply_draft.data_key );
			bp.Nouveau.Media.reply_topic_display_post = 'edit';

			var currentForm = this.currentForm ? this.currentForm : $( 'form#new-post' );

			// Remove the link preview for the draft too.
			if ( $( currentForm ).find( '#bb_link_url' ).length > 0 ) {
				$( currentForm ).find( '#bb_link_url' ).remove();
			}

			// Remove class to display draft.
			currentForm.removeClass( 'has-draft has-content has-media has-gif has-link-preview' );
		};

		/**
		 * Reset the topic/reply post form fields to their empty state.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @return {void}
		 */
		this.resetTopicReplyDraftPostForm = function() {
			// Discard and close both land here, so the notices come down with the
			// rest of the composer rather than outliving the draft they describe.
			this.clearDraftNotices();

			var target                      = this.currentForm ? this.currentForm : $( 'form#new-post' );
			var editor_key                  = target.find( '.bbp-the-content:not(.emojionearea)' ).data( 'key' ),
				$editor,
				$medium_editor,
				media_dropzone_container    = target.find( '#forums-post-media-uploader' ),
				document_dropzone_container = target.find( '#forums-post-document-uploader' ),
				video_dropzone_container    = target.find( '#forums-post-video-uploader' ),
				gif_attached_container      = target.find( '#whats-new-attachments .forums-attached-gif-container' );

			// Reset editor.
			if ( 'undefined' !== typeof window.MediumEditor ) {
				if ( 'topic' === this.topic_reply_draft.object ) {
					if ( window.forums_medium_topic_editor ) {
						$medium_editor = window.forums_medium_topic_editor[editor_key];
						$medium_editor.setContent( '' );
					}
					$editor = target.find( '#bbp_editor_topic_content_' + editor_key );
					$editor.removeClass( 'error' );
					target.find( '#bbp_topic_content' ).val( '' );
					target.find( '#bbp_topic_title' ).val( '' );
					target.removeClass( 'has-title' );
				} else if ( 'reply' === this.topic_reply_draft.object ) {
					if ( window.forums_medium_reply_editor ) {
						$medium_editor = window.forums_medium_reply_editor[editor_key];
						$medium_editor.setContent( '' );
					}
					$editor = target.find( '#bbp_editor_reply_content_' + editor_key );
					$editor.removeClass( 'error' );
					$editor = target.find( '#bbp_editor_reply_content_' + editor_key );
					target.find( '#bbp_reply_content' ).val( '' );
					setTimeout(
						function () {
							$editor.removeClass( 'error' );
						},
						300
					);
				}
			} else {
				target.find( '.bbp-the-content:not(.emojionearea)' ).empty();
				target.find( '#bbp_reply_content' ).val( '' );
			}

			// Reset topic subscription.
			var bbp_topic_subscription_id = ( forms.length > 1 && this.bbp_topic_id ) ? '#bbp_topic_subscription_' + this.bbp_topic_id : '#bbp_topic_subscription';
			if ( 'topic' === this.topic_reply_draft.object && ! $( '#subscribe-' + this.bbp_forum_id ).hasClass( 'is-subscribed' ) ) {
				target.find( bbp_topic_subscription_id ).prop( 'checked', false );
			} else if ( 'reply' === this.topic_reply_draft.object && ! $( '#subscribe-' + this.bbp_topic_id ).hasClass( 'is-subscribed' ) ) {
				target.find( bbp_topic_subscription_id ).prop( 'checked', false );
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
			target.removeClass( 'has-content' );
		};

		/**
		 * Clear the link-preview data held on the draft and its form.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @return {void}
		 */
		this.resetTopicReplyDraftLinkPreview = function() {
			var currentTargetForm = this.currentForm ? this.currentForm : $( 'form#new-post' );

			// Clear the linkPreviews object for the form.
			$( currentTargetForm ).find( '#bb-close-link-suggestion' ).trigger( 'click' );

			// Reset link preview data and attachments.
			if ( $( currentTargetForm ).find( '#link_preview_data' ).length > 0 ) {
				$( currentTargetForm ).find( '#link_preview_data' ).val( '' );
				$( currentTargetForm ).find( '#whats-new-attachments' ).val( '' );
			}

			$( currentTargetForm ).find( '.bb-url-scrapper-container' ).remove();
			$( currentTargetForm ).find( '#bb_link_url' ).remove();
		};

		/**
		 * Gather the current form values into the working draft for saving.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @return {void}
		 */
		this.collectTopicReplyDraftActivity = function() {
			var form = this.currentForm ? this.currentForm : $( '#new-post' ), meta = {};

			_.each(
				form.serializeArray(),
				function ( pair ) {
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

			if ( 'undefined' !== typeof meta.bbp_topic_title && '' !== meta.bbp_topic_title ) {
				var bbp_topic_title = meta.bbp_topic_title;

				// Unescape characters.
				bbp_topic_title = bbp_topic_title.replace( /\\(.)/g, '$1' );

				// Remove JavaScript patterns (basic filtering).
				var forbiddenPatterns = [
					/javascript:/gi, // Remove "javascript:" keyword.
					/on\w+=".*?"/gi, // Remove inline event handlers like onload, onclick.
					/<script.*?>.*?<\/script>/gi, // Remove <script> tags.
					/\b(alert|confirm|prompt|eval|setTimeout|setInterval)\b\(.*?\)/gi, // Block common JS methods.
				];

				forbiddenPatterns.forEach( function ( pattern ) {
					bbp_topic_title = bbp_topic_title.replace( pattern, '' );
				} );

				// Trim spaces.
				bbp_topic_title = bbp_topic_title.trim();

				meta.bbp_topic_title = bbp_topic_title;
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
			
			// Check if GIF support is enabled (GIF button exists and is not disabled)
			var gif_support_enabled = form.find( '#forums-gif-button' ).length > 0 && ! form.find( '#forums-gif-button' ).parents( '.post-elements-buttons-item' ).hasClass( 'disable' );
			
			if ( gif_support_enabled && 'undefined' !== typeof meta.bbp_media_gif && ( '' !== meta.bbp_media_gif && '[]' !== meta.bbp_media_gif ) ) {
				media_valid = true;
			}
			if ( 'undefined' !== typeof meta.link_preview_data && ( '' !== meta.link_preview_data && '[]' !== meta.link_preview_data ) ) {
				media_valid = true;

				var link_preview_data = JSON.parse( meta.link_preview_data );

				var preview_data                   = {};
				preview_data.embed                 = ( false === link_preview_data.link_embed ) ? 0 : 1;
				preview_data.url                   = link_preview_data.link_url;
				preview_data.link_image_index_save = 0;
				meta.bb_link_url                   = JSON.stringify( preview_data );
			}

			// Does the form being serialized RIGHT NOW carry anything of its
			// own? Decided here, from `meta` alone, because the two
			// "still available in older draft" checks below deliberately judge
			// the STORED copy instead - they keep a draft alive across a
			// momentarily empty form (closing the modal resets it before the
			// unload save runs). What they must never do is make an EMPTY
			// composer look like something worth writing: the write at the end
			// of this function replaces the stored entry with `meta`, so an
			// empty one destroyed the member's saved text and dropped their
			// attachment references - locally AND on the server - while
			// leaving is_content_valid true, so the composer still showed a
			// draft indicator over an empty box (Q12).
			var payload_is_empty = false;

			if ( ! media_valid ) {
				if ( 'topic' === this.topic_reply_draft.object ) {
					payload_is_empty = (
						( 'undefined' === typeof meta.bbp_topic_title || '' === $.trim( meta.bbp_topic_title ) ) &&
						( 'undefined' === typeof meta.bbp_topic_content || '' === $( $.parseHTML( meta.bbp_topic_content ) ).text().trim() )
					);
				} else if ( 'reply' === this.topic_reply_draft.object ) {
					payload_is_empty = ( 'undefined' === typeof meta.bbp_reply_content || '' === $( $.parseHTML( meta.bbp_reply_content ) ).text().trim() );
				}
			}

			// Check if the media, videos or documents still available in older draft so we need to be update the draft again.
			if ( ! media_valid && 'undefined' !== typeof this.topic_reply_draft.data && false !== this.topic_reply_draft.data ) {
				if (
					'undefined' !== typeof this.topic_reply_draft.data.bbp_media &&
					'' !== this.topic_reply_draft.data.bbp_media &&
					'[]' !== this.topic_reply_draft.data.bbp_media
				) {
					media_valid = true;
				}

				if (
					'undefined' !== typeof this.topic_reply_draft.data.bbp_video &&
					'' !== this.topic_reply_draft.data.bbp_video &&
					'[]' !== this.topic_reply_draft.data.bbp_video
				) {
					media_valid = true;
				}

				if (
					'undefined' !== typeof this.topic_reply_draft.data.bbp_document &&
					'' !== this.topic_reply_draft.data.bbp_document &&
					'[]' !== this.topic_reply_draft.data.bbp_document
				) {
					media_valid = true;
				}
			}

			var content_valid = true;
			if ( 'topic' === this.topic_reply_draft.object && 'undefined' !== typeof meta.bbp_topic_content && '' === $( $.parseHTML( meta.bbp_topic_content ) ).text().trim() && ! media_valid ) {
				content_valid = false;
			} else if ( 'reply' === this.topic_reply_draft.object && 'undefined' !== typeof meta.bbp_reply_content && '' === $( $.parseHTML( meta.bbp_reply_content ) ).text().trim() && ! media_valid ) {
				content_valid = false;
			}

			// Check if the content still available in older draft so we need to be update the draft again.
			if ( ! content_valid && 'undefined' !== typeof this.topic_reply_draft.data && false !== this.topic_reply_draft.data ) {
				
				if ( 'topic' === this.topic_reply_draft.object ) {

					// Check if the topic title still available in older draft so we need to be update the draft again.
					if ( 'undefined' !== typeof this.topic_reply_draft.data.bbp_topic_title && '' !== this.topic_reply_draft.data.bbp_topic_title.trim() ) {
						content_valid = true;
					}

					// Check if the topic content still available in older draft so we need to be update the draft again.
					if ( 'undefined' !== typeof this.topic_reply_draft.data.bbp_topic_content && '' !== this.topic_reply_draft.data.bbp_topic_content.trim() ) {
						content_valid = true;
					}
				}

				if ( 'reply' === this.topic_reply_draft.object && 'undefined' !== typeof this.topic_reply_draft.data.bbp_reply_content && '' !== this.topic_reply_draft.data.bbp_reply_content.trim() ) {
					content_valid = true;
				}
			}

			if ( content_valid ) {

				// The composer is empty and the only thing still making this
				// count as a draft is the copy already stored. Leave BOTH
				// copies exactly as they are: `meta` carries nothing to save,
				// and persisting it is the data loss described above. The
				// member's deliberate route to removing a draft is the
				// Discard Draft button, which deletes it on both sides
				// (Q12).
				if ( payload_is_empty ) {
					return;
				}

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

				this.checkedTopicReplyDataChanged( old_draft_data, meta );

				this.topic_reply_draft.data                          = meta;
				this.all_draft_data[this.topic_reply_draft.data_key] = meta;
				this.topic_reply_draft.is_content_valid              = true;

				this.storeTopicReplyDraftLocal();
			}
		};

		/**
		 * Flag the draft as changed when any tracked field differs between the two snapshots.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param {Object} old_data The previously stored draft field set.
		 * @param {Object} new_data The freshly collected draft field set.
		 *
		 * @return {void}
		 */
		this.checkedTopicReplyDataChanged = function ( old_data, new_data ) {
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

			var self = this;

			_.each(
				draft_data_keys,
				function ( pair ) {
					if ( 'undefined' !== typeof old_data[ pair ] && 'undefined' === typeof new_data[ pair ] ) {
						self.draft_content_changed = true;
					} else if ( 'undefined' === typeof old_data[ pair ] && 'undefined' !== typeof new_data[ pair ] ) {
						self.draft_content_changed = true;
					} else if ( 'undefined' !== typeof old_data[ pair ] && 'undefined' !== typeof new_data[ pair ] ) {
						if ( - 1 !== _.indexOf(
							[
								'bbp_topic_content',
								'bbp_reply_content',
							],
							pair
						) ) {
							if ( $( $.parseHTML( old_data[ pair ] ) ).text().trim() !== $( $.parseHTML( new_data[ pair ] ) ).text().trim() ) {
								self.draft_content_changed = true;
							}
						} else if ( old_data[ pair ] !== new_data[ pair ] ) {
							self.draft_content_changed = true;
						}
					}
				}
			);
		};

		// Re-attempt the one-time lazy read that failed at page load, so a
		// transient blip does not latch draft_fetch_failed for the whole page
		// view. Called from the autosave tick in postTopicReplyDraft(). On success
		// it ONLY clears the flag (unblocking saves) - it deliberately does NOT
		// re-run bbInitTopicReplyDrafts() (that would double-bind listeners) or
		// restore over the already-open composer; the member's current content is
		// what saves from here (M12). A shared in-flight flag prevents overlapping
		// retries across forms.
		this.retryDraftFetch = function () {
			// Ceiling on the per-tick re-attempt so a DURABLE failure (an expired
			// nonce that never recovers on a long-open tab) does not fire a request
			// every autosave tick forever. A success clears the flag and resets the
			// counter, so a later fresh failure gets its own budget (M16).
			if (
				'undefined' === typeof BP_Nouveau.forums ||
				true === BP_Nouveau.forums.draft_fetch_retrying ||
				( BP_Nouveau.forums.draft_retry_attempts || 0 ) >= 5
			) {
				return;
			}
			BP_Nouveau.forums.draft_fetch_retrying = true;
			BP_Nouveau.forums.draft_retry_attempts = ( BP_Nouveau.forums.draft_retry_attempts || 0 ) + 1;

			// Report stored hand-backs here too, so the server stops holding
			// them without waiting for the next save.
			var retry_acks = bbDraftGetAcks(),
				retry_self = this;

			$.post(
				BP_Nouveau.ajaxurl,
				{
					action: 'bb_get_topic_reply_drafts',
					_wpnonce_post_topic_reply_draft: BP_Nouveau.forums.nonces.post_topic_reply_draft,
					prefixes: bbDraftKeyPrefixes().join( ',' ),
					bb_draft_evicted_ack: retry_acks.join( ',' )
				}
			).done(
				function ( response ) {
					// Only `success` decides read-vs-rejected, mirroring the initial
					// fetch: an expired nonce answers success:false at HTTP 200 and
					// must NOT clear the guard.
					if ( response && response.success ) {
						BP_Nouveau.forums.draft_fetch_failed   = false;
						BP_Nouveau.forums.draft_retry_attempts = 0;
						bbDraftClearAcks( retry_acks );

						if ( response.data && response.data.evicted_drafts ) {
							// The open form already holds what the member wrote after
							// the failed read. A hand-back for its key stays held on
							// the server and is not saved over in this page view.
							var retry_key = retry_self.topic_reply_draft ? retry_self.topic_reply_draft.data_key : '';

							if ( retry_key && retry_self.draft_content_changed && ( response.data.evicted_drafts[ 'bb_user_topic_reply_draft:' + retry_key ] || response.data.evicted_drafts[ retry_key ] ) ) {
								window.bbDraftHandbackSkipKeys              = window.bbDraftHandbackSkipKeys || {};
								window.bbDraftHandbackSkipKeys[ retry_key ] = true;
								retry_self.draft_blocked_keys[ retry_key ]  = true;
							}

							bbDraftStoreHandback( response.data.evicted_drafts );
						}
					}
				}
			).always(
				function () {
					BP_Nouveau.forums.draft_fetch_retrying = false;
				}
			);
		};

		/**
		 * Persist the collected draft to the server.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param {boolean} is_force_saved Whether the save was forced rather than interval-driven.
		 * @param {boolean} is_reload_window Whether the save is running from the page-unload handler.
		 * @param {boolean} is_send_all_data Whether the full draft payload (not just the delta) is sent.
		 *
		 * @return {void}
		 */
		this.postTopicReplyDraft = function ( is_force_saved, is_reload_window, is_send_all_data ) {
			// Captured synchronously up front: discardTopicReplyDraftForm()
			// resets post_action back to 'update' before any async response
			// lands, and a DISCARD must be EXEMPT from the fetch-failed guard
			// below - blocking it left the member's SERVER draft undeletable for
			// the whole page view (the local UI reset, but the stored row
			// survived).
			var is_discard_request = ( 'delete' === this.topic_reply_draft.post_action );

			// The member HAS a stored draft that we failed to read. Writing now
			// would replace content they were never shown with whatever is in
			// the box - a transient failure would silently destroy the draft it
			// prevented us from loading. A discard is exempt (it only removes
			// the key). The network may have recovered since the initial read
			// failed, so re-attempt the read on this tick rather than latching
			// the failure for the whole page view: a success clears the flag and
			// the next tick saves normally (M12).
			if (
				! is_discard_request &&
				'undefined' !== typeof BP_Nouveau.forums &&
				true === BP_Nouveau.forums.draft_fetch_failed
			) {
				this.retryDraftFetch();
				this.showDraftFeedback( BP_Nouveau.forums.draft_fetch_failed_message || '' );

				return;
			}
			if ( ! is_force_saved && 'undefined' === typeof this.all_draft_data[this.topic_reply_draft.data_key] ) {
				return;
			}

			// Checked the content changed or not.
			if ( ! is_force_saved && ! this.draft_content_changed ) {
				return;
			}

			// A conflict or a "not loaded" outcome stops saving this key for the
			// page view (bb_draft_base).
			if ( this.draft_conflict_keys[ this.topic_reply_draft.data_key ] || this.draft_blocked_keys[ this.topic_reply_draft.data_key ] ) {
				return;
			}

			this.topic_reply_draft.data = this.all_draft_data[this.topic_reply_draft.data_key];

			// Each save carries a new revision id and the revisions it was built
			// on; the server refuses to replace any other stored version.
			var draft_key  = this.topic_reply_draft.data_key,
				draft_rev  = bbDraftNewRev(),
				draft_base = this.draftBaseFor( draft_key ),
				draft_acks = bbDraftGetAcks();

			// A save of this key still in flight may be stored even when the
			// next one cancels it - PHP finishes a request it already received -
			// so keep its revision an acceptable stored version. Otherwise this
			// save is refused as a conflict with the member's own earlier save
			// (D-01).
			if ( this.draft_inflight && this.draft_inflight.key === draft_key ) {
				draft_base                       = _.uniq( draft_base.concat( [ this.draft_inflight.rev ] ) ).slice( -8 );
				this.draft_base_map[ draft_key ] = draft_base.slice( 0 );
			}

			if ( ! is_discard_request ) {
				this.topic_reply_draft._draft_rev = draft_rev;
			}

			var self = this,
				draft_payload = _.omit( this.topic_reply_draft, [ '_draft_base', '_draft_local_at', '_draft_edit_at', 'bb_draft_pending_sync', 'bb_draft_owner' ] );

			// The server strips inline base64 images anyway; stripping them here
			// stops every autosave tick uploading them (V27).
			if ( draft_payload.data && 'object' === typeof draft_payload.data ) {
				_.each(
					[ 'bbp_topic_content', 'bbp_reply_content' ],
					function ( content_key ) {
						if ( 'string' === typeof draft_payload.data[ content_key ] && /data:[^"']*base64/i.test( draft_payload.data[ content_key ] ) ) {
							draft_payload.data = _.extend( {}, draft_payload.data );
							draft_payload.data[ content_key ] = draft_payload.data[ content_key ].replace(
								/(\s(?:src|href)\s*=\s*)(["'])data:[^"']*base64[^"']*\2/gi,
								'$1$2$2'
							);
						}
					}
				);
			}

			// A delete needs only the key, never the content - the server
			// disposes from its own stored row. Slimming the payload also keeps
			// the unload delete under the browser's ~64KB sendBeacon quota,
			// which a full near-cap draft exceeds (the request would silently
			// never be sent and the server row would survive the discard).
			if ( is_discard_request ) {
				draft_payload = _.omit( draft_payload, 'data' );
			}

			if ( ! is_reload_window ) {
				if ( this.draft_ajax_request ) {
					this.draft_ajax_request.abort();
				}

				var draft_data = {
					_wpnonce_post_topic_reply_draft: BP_Nouveau.forums.nonces.post_topic_reply_draft,
					action: 'post_topic_reply_draft',
					draft_topic_reply: draft_payload,
					bb_draft_base: draft_base.join( ',' ),
					bb_draft_evicted_ack: draft_acks.join( ',' )
				};

				var draft_inflight  = is_discard_request ? null : { key: draft_key, rev: draft_rev };
				this.draft_inflight = draft_inflight;

				// Send data to server.
				this.draft_ajax_request = $.ajax(
					{
						type: 'POST',
						url: BP_Nouveau.ajaxurl,
						data: draft_data,
						success: function( response ) {
							// admin-ajax returns HTTP 200 for wp_send_json_error, so
							// every guardrail rejection surfaces here - silently
							// dropping one reads as saved while the server copy
							// quietly stops updating.
							//
							// `! response.success` ALONE decides, which is the
							// predicate the fetch path already uses. Testing
							// `response.data.message` as well sent every rejection
							// that carries no message into the else branch - i.e. the
							// SUCCESS branch. A bare wp_send_json_error() emits
							// {"success":false} with no `data` key at all, and that is
							// exactly what the nonce check and the whole
							// authorization gate send. So an expired nonce, or a
							// moderator making the forum private mid-session, left the
							// composer looking completely normal while nothing was
							// saved - and cleared any standing warning as it went
							// (H1).
							if ( ! response || ! response.success ) {
								// A budget refusal (after the row write) may have
								// evicted older drafts; drop their local copies so the
								// UI does not keep listing drafts that are gone (F2).
								// No-op when the response carries no evicted keys; the
								// failure message below then overwrites the eviction
								// notice, since the save itself failed.
								self.handleEvictedDrafts( response );

								// A newer version is stored: keep it, keep the
								// member's text on screen, and stop saving this key.
								if ( response && response.data && response.data.conflict ) {
									self.handleDraftConflict( draft_key, response.data.draft_activity, response.data.message );

									return;
								}

								// A refused save was not stored; keeping its revision in
								// the base is harmless and matches the lost-response
								// path below.
								if ( ! is_discard_request ) {
									self.draft_base_map[ draft_key ] = _.uniq( draft_base.concat( [ draft_rev ] ) ).slice( -8 );
								}

								self.showDraftFeedback(
									( response && response.data && response.data.message ) ?
										response.data.message :
										( is_discard_request ?
											( BP_Nouveau.forums.draft_discard_failed_message || '' ) :
											( BP_Nouveau.forums.draft_save_failed_message || '' ) )
								);

								// A refused discard leaves the server row in place, so
								// the reload the notice asks for must offer it again.
								if ( is_discard_request ) {
									self.undoFailedDiscard( draft_key );
								}

								return;
							}

							self.showDraftFeedback( '' );
							self.handleEvictedDrafts( response );

							self.draft_base_map[ draft_key ] = is_discard_request ? [ 'none' ] : [ draft_rev ];
							bbDraftClearAcks( draft_acks );

							// Confirmed on the server: the local copy is an ordinary
							// cache again.
							if ( ! is_discard_request && self.draft_pending_keys[ draft_key ] ) {
								delete self.draft_pending_keys[ draft_key ];
								if ( draft_key === self.topic_reply_draft.data_key && null !== localStorage.getItem( draft_key ) ) {
									self.storeTopicReplyDraftLocal();
								}
							}

							// A successful DISCARD round-trip proves the connection and
							// nonce are fine, so clear a latched fetch-failure and its M16
							// retry budget - a transient blip at page load would otherwise
							// keep autosave blocked for the whole page view even though we
							// just reached the server (M18).
							if ( is_discard_request && 'undefined' !== typeof BP_Nouveau.forums ) {
								BP_Nouveau.forums.draft_fetch_failed   = false;
								BP_Nouveau.forums.draft_retry_attempts = 0;
							}

							// A stored draft exists again (or the discard has
							// landed), so the reload guard has served its purpose.
							bp.Nouveau.TopicReplyDraft.markDiscarded( draft_payload.data_key, false );

							if ( is_discard_request ) {
								delete self.draft_discard_copies[ draft_key ];
							}
						},
						error: function ( jqXHR, textStatus ) {
							// The response was lost, not necessarily the save: keep this
							// revision acceptable as the stored version (NEW-2).
							if ( ! is_discard_request && 'abort' !== textStatus ) {
								self.draft_base_map[ draft_key ] = _.uniq( draft_base.concat( [ draft_rev ] ) ).slice( -8 );
							}

							// A transport failure - network drop, 500, timeout -
							// never reached success:, so the member saw NOTHING: the
							// composer looked normal while the server copy silently
							// stopped updating. The activity pack surfaces this
							// (M3); this pack did not.
							//
							// An abort is excluded because this pack aborts its own
							// in-flight autosave before each new save (above) and
							// before the unload beacon, so treating it as a failure
							// would warn about data loss on a completely normal path.
							//
							// `readyState === 0` is deliberately NOT part of this test. It is true
							// for a real abort AND for any request the client kills before it
							// completes - an ad blocker or privacy extension targeting
							// admin-ajax.php, a corporate proxy, a connection dropping mid-save.
							// Those are genuine failures, reported as `statusText: 'error'`, and
							// swallowing them left the member typing into a composer that had
							// silently stopped saving: the exact regression this notice exists to
							// prevent. jQuery sets BOTH textStatus and statusText to 'abort' on a
							// real abort, so the checks below already cover that case.
							if (
								'abort' === textStatus ||
								( jqXHR && 'abort' === jqXHR.statusText )
							) {
								return;
							}

							// The outcome of a lost discard is unknown; keeping the
							// draft is the side that cannot lose it.
							if ( is_discard_request ) {
								self.undoFailedDiscard( draft_key );
							}

							self.showDraftFeedback(
								is_discard_request ?
									( BP_Nouveau.forums.draft_discard_failed_message || '' ) :
									( BP_Nouveau.forums.draft_save_failed_message || '' )
							);
						}
					}
				);

				// Settled, or cancelled after its revision was read above.
				this.draft_ajax_request.always(
					function () {
						if ( self.draft_inflight === draft_inflight ) {
							self.draft_inflight = null;
						}
					}
				);

			} else {

				// If current screen is not edit screen then send request.
				var formData = new FormData();
				formData.append( '_wpnonce_post_topic_reply_draft', BP_Nouveau.forums.nonces.post_topic_reply_draft );
				formData.append( 'action', 'post_topic_reply_draft' );

				// The server never evicts on a beacon save, because this
				// response is never read and an evicted draft could not be
				// handed back. It refuses instead; the local copies stay.
				formData.append( 'bb_draft_beacon', '1' );
				formData.append( 'bb_draft_base', draft_base.join( ',' ) );
				formData.append( 'bb_draft_evicted_ack', draft_acks.join( ',' ) );
				formData.append( 'draft_topic_reply', JSON.stringify( draft_payload ) );

				// The beacon's response is never read, so either outcome is an
				// acceptable stored version later: its revision, or the old one.
				if ( ! is_discard_request ) {
					this.draft_base_map[ draft_key ] = _.uniq( draft_base.concat( [ draft_rev ] ) ).slice( -8 );
					this.storeTopicReplyDraftLocal();
				}

				if ( is_send_all_data ) {
					var all_base = {},
						all_rev  = {};

					// Each replayed sibling may only replace the version this tab
					// held, and gets its own new revision id.
					_.each(
						_.keys( this.all_draft_data ),
						function ( sibling_key ) {
							if ( sibling_key === draft_key ) {
								return;
							}

							all_base[ sibling_key ] = self.draftBaseFor( sibling_key ).join( ',' );
							all_rev[ sibling_key ]  = bbDraftNewRev();
							bbDraftAddBaseToLocal( sibling_key, all_rev[ sibling_key ] );
						}
					);

					formData.append( 'all_data', JSON.stringify( this.all_draft_data ) );
					formData.append( 'all_base', JSON.stringify( all_base ) );
					formData.append( 'all_rev', JSON.stringify( all_rev ) );
				}

				// Known limitation: browsers cap sendBeacon payloads (~64KB). When
				// the combined drafts exceed that, this unload sync is silently
				// skipped; the periodic in-page saves remain the durable path.
				navigator.sendBeacon( BP_Nouveau.ajaxurl, formData );
			}

			// Set false after sending the request to the server.
			this.draft_content_changed = false;
		};

		// The draft row is fetched over AJAX before start() runs, so
		// the member can type into an already-rendered form while that request is
		// in flight. Restoring on top of that would destroy text which was never
		// saved anywhere - the change listeners are not bound yet either.
		/**
		 * Whether the member has typed into the composer since it opened.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @return {boolean} True when the current content differs from the initial snapshot.
		 */
		this.hasMemberTypedContent = function () {
			var $form = this.draftNoticeForm();

			if ( ! $form.length ) {
				return false;
			}

			var current = this.currentContentSnapshot();

			// Only a CHANGE from what the form arrived with counts as typing. A
			// pre-filled editor (reply-to-reply, quote, mention prefix) is not
			// the member's unsaved work and must not block their draft.
			if ( 'undefined' === typeof this.initial_content_snapshot ) {
				return '' !== current.replace( /\u0000/g, '' );
			}

			return current !== this.initial_content_snapshot;
		};

		/**
		 * Tell the member their stored draft was left alone, and why.
		 *
		 * The restore is all-or-nothing: one typed character before the fetch
		 * resolves and appendTopicDraftData()/appendReplyDraftData() bail, so
		 * has-draft is never applied and no affordance offers the draft for the
		 * rest of the page load. Silence there reads as a lost draft
		 *.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @return {void}
		 */
		this.announceSuppressedDraftRestore = function () {
			var message = ( 'undefined' !== typeof BP_Nouveau.forums ) ? BP_Nouveau.forums.draft_not_restored_message : '';

			// What the member typed is not based on the stored draft, so it must
			// not be saved over it in this page view (V02).
			if ( this.topic_reply_draft.data_key ) {
				this.draft_blocked_keys[ this.topic_reply_draft.data_key ] = true;
			}

			if ( message ) {
				this.showDraftFeedback( message );
			}
		};

		/**
		 * Keep drafts the server evicted from the draft budget in localStorage.
		 *
		 * Forum inner keys ("meta_key:inner_key") are stored in this
		 * composer's shape, activity keys in the activity composer's shape.
		 * Each copy is marked pending, because the server no longer holds it;
		 * the activity composer reads that marker. A copy this browser already
		 * holds is kept, replaced or merged with the hand-back
		 * ({@see bbDraftHandbackDecision}).
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param {Object} drafts Evicted entries keyed like `evicted_draft_keys`.
		 *
		 * @return {void}
		 */
		this.storeEvictedDrafts = function ( drafts ) {
			bbDraftStoreHandback( drafts );
		};

		/**
		 * The revisions the composer's copy of a draft key was built on.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param {string} key Inner draft key.
		 *
		 * @return {Array}
		 */
		this.draftBaseFor = function ( key ) {
			if ( this.draft_base_map[ key ] ) {
				return this.draft_base_map[ key ].slice( 0 );
			}

			if ( this.bp_nouveau_forums_data && this.bp_nouveau_forums_data[ key ] ) {
				return [ this.bp_nouveau_forums_data[ key ]._draft_rev || '' ];
			}

			return [ 'none' ];
		};

		/**
		 * Keep the newer stored draft after the server refused a save over it.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param {string} key     Inner draft key.
		 * @param {Object} stored  The stored entry the server returned.
		 * @param {string} message The server's message.
		 *
		 * @return {void}
		 */
		this.handleDraftConflict = function ( key, stored, message ) {
			this.draft_conflict_keys[ key ] = true;
			delete this.draft_pending_keys[ key ];
			this.clearTopicReplyDraftIntervals();

			try {
				if ( stored && 'object' === typeof stored ) {
					localStorage.setItem( key, JSON.stringify( _.extend( {}, stored, { bb_draft_owner: parseInt( BP_Nouveau.forums.params.bb_current_user_id, 10 ), _draft_local_at: Math.floor( Date.now() / 1000 ) } ) ) );
				}
			} catch ( e ) {
				// Over quota: the server still holds the stored draft.
			}

			this.showDraftFeedback( BP_Nouveau.forums.draft_conflict_message || message || '' );
		};

		/**
		 * Tell the member the server evicted older drafts from the draft budget.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param {Object} response The autosave AJAX response, carrying any evicted draft keys.
		 *
		 * @return {void}
		 */
		this.handleEvictedDrafts = function ( response ) {
			var keys = ( response && response.data && response.data.evicted_draft_keys ) ? response.data.evicted_draft_keys : [];

			// Store every hand-back, including ones re-sent because an earlier
			// response was lost; announce only this request's evictions.
			if ( response && response.data && response.data.evicted_drafts ) {
				bbDraftStoreHandback( response.data.evicted_drafts );
			}

			if ( ! keys || ! keys.length ) {
				return;
			}

			var self = this;

			// The server evicted older drafts to keep this member under the
			// draft budget and handed their entries back. Their localStorage
			// copies are deliberately KEPT, and a draft this browser did not
			// hold yet is stored from the hand-back: it is now the only copy.
			// Reopening that composer restores it, and an edit there saves it
			// again.
			//
			// They are dropped from the in-memory working set, though. The
			// unload beacon replays that whole set as `all_data`, which would
			// re-upload an evicted draft the member did not touch and evict
			// the draft just saved in its place. The open box's own key is
			// never dropped - the member is writing in it. Inner forum drafts
			// arrive as "meta_key:inner_key".
			_.each(
				keys,
				function ( key ) {
					var inner_key = String( key ).split( ':' ).pop();

					if ( inner_key !== self.topic_reply_draft.data_key ) {
						delete self.all_draft_data[ inner_key ];
					}
				}
			);

			this.showDraftFeedback( BP_Nouveau.forums.draft_evicted_message );
		};

		// One fallback for every draft-notice helper. These were split between
		// $( 'form#new-post' ) and $( 'form[name="new-post"]' ).first(), which
		// only agree while the topic and reply forms are the same element
		//.
		/**
		 * Resolve the form element the draft notice should attach to.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @return {jQuery} The target form element.
		 */
		this.draftNoticeForm = function () {
			return ( this.currentForm && this.currentForm.length ) ? this.currentForm : $( 'form[name="new-post"]' ).first();
		};

		/**
		 * Put a draft notice at the top of the composer BODY, below the header.
		 *
		 * Not `$form.prepend()`, which placed the notice above the modal title and
		 * outside the modal's rounded top edge. Core solves the same problem by
		 * absolutely positioning `#message-feedabck` at the header's height;
		 * inserting after the header in normal flow reaches the same spot without
		 * pinning to a hard-coded 58px.
		 *
		 * The header's depth differs by composer, so both levels are searched. In
		 * the activity composers it is a direct child of the form; in every forum
		 * composer it is a grandchild wrapped in `fieldset.bbp-form` - a `legend`
		 * on the default pack, `.bb-rl-forum-modal-header` on ReadyLaunch. An
		 * earlier revision of this docblock claimed forum composers had no header
		 * at all; they do, and `.children()` alone could never see it.
		 *
		 * The fallback still matters: a composer rendered without a header, or one
		 * a theme has restructured, prepends as before.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param {jQuery} $form The composer form.
		 * @param {jQuery} $node The notice node to insert.
		 *
		 * @return {void}
		 */
		this.insertDraftNotice = function ( $form, $node ) {
			var headerSelector = '#activity-header, #bb-rl-activity-header, .bb-model-header, .bb-rl-bb-model-header',
				forumSelector  = 'fieldset.bbp-form > legend, fieldset.bbp-form > .bb-rl-forum-modal-header, fieldset.bbp-form > .bbp_topic_title_wrapper',
				$header        = $form.children( headerSelector ).first();

			if ( ! $header.length ) {
				$header = $form.find( forumSelector ).first();
			}

			if ( $header.length ) {
				$node.insertAfter( $header );
			} else {
				$form.prepend( $node );
			}
		};

		/**
		 * The refusal notice that must stay on screen while saving is stopped.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @return {string} The message, or '' when saving is not stopped for this key.
		 */
		this.draftBlockMessage = function () {
			var key    = this.topic_reply_draft ? this.topic_reply_draft.data_key : '',
				forums = ( 'undefined' !== typeof BP_Nouveau.forums ) ? BP_Nouveau.forums : {};

			if ( key && this.draft_conflict_keys[ key ] ) {
				return forums.draft_conflict_message || '';
			}

			if ( key && this.draft_blocked_keys[ key ] ) {
				return forums.draft_not_restored_message || '';
			}

			return '';
		};

		/**
		 * Show the draft-retention notice on the composer.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @return {void}
		 */
		this.showDraftRetentionNotice = function ( show ) {
			var $form   = this.draftNoticeForm(),
				message = ( 'undefined' !== typeof BP_Nouveau.forums ) ? BP_Nouveau.forums.draft_retention_message : '',
				$note;

			if ( ! $form.length ) {
				return;
			}

			$note = $form.find( '.bb-draft-retention-note' );

			// Hiding, or expiry disabled (empty message) - say nothing, and take
			// down any notice already on screen. Without this branch the function
			// could only ever ADD the note: discarding a forum draft emptied the
			// composer and hid its discard button while "Drafts are kept for 30
			// days." stayed on screen, describing a draft that no longer existed,
			// until the next page load. The activity packs have had this branch
			// from the start; the forum packs shipped show-only.
			// Saving is stopped: the refusal notice is the one notice, never
			// "Drafts are kept for N days." over text that is not being kept.
			if ( false !== show && this.draftBlockMessage() ) {
				$note.remove();
				this.showDraftFeedback( this.draftBlockMessage() );

				return;
			}

			if ( false === show || ! message ) {
				$note.remove();

				return;
			}

			if ( $note.length ) {
				$note.find( 'p' ).text( message );

				return;
			}

			// Text set before insertion, and placed BELOW any refusal notice, so
			// the two notices keep one fixed order whichever is created first.
			$note = $( '<div class="bb-draft-retention-note bp-messages bp-feedback info"></div>' )
				.append( $( '<span class="bp-icon" aria-hidden="true"></span>' ) )
				.append( $( '<p></p>' ).text( message ) );

			var $feedback = $form.find( '.bb-draft-save-feedback' );

			if ( $feedback.length ) {
				$note.insertAfter( $feedback );
			} else {
				this.insertDraftNotice( $form, $note );
			}
		};

		// Interim guard, mirroring the activity composer: a pasted
		// bitmap becomes an inline base64 image of 1MB+, which the draft and
		// publish pipelines strip anyway. Refuse it at the moment of intent
		// instead of silently losing it.
		/**
		 * Block pure-image clipboard pastes into the editor; embedded data URLs are stripped server-side.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @return {void}
		 */
		this.setupPasteImageGuard = function () {
			var self  = this,
				$form = this.currentForm ? this.currentForm : $( 'form[name="new-post"]' ).first();

			if ( ! $form.length ) {
				return;
			}

			$form.on( 'paste.bbDraftImageGuard', '[contenteditable="true"]', function ( event ) {
				// Office-suite copies put a bitmap rendition NEXT TO the text -
				// the member is pasting text, so let it through (embedded data:
				// URLs are stripped server-side). Only a paste with no text is
				// refused, including a browser "Copy image", which carries the
				// image as HTML too ({@see bbDraftClipboardIsImageOnly}).
				if ( bbDraftClipboardIsImageOnly( event.originalEvent ? event.originalEvent.clipboardData : null ) ) {
					event.preventDefault();
					self.showDraftFeedback( BP_Nouveau.forums.paste_image_blocked_message );
				}
			} );

			// Remember which composer a keyboard paste comes from: MediumEditor
			// moves it into a body-level paste bin, outside this form.
			$form.on( 'focusin.bbDraftImageGuard', '[contenteditable="true"]', function () {
				bbDraftPasteComposer = self;
			} );

			bbDraftBindPasteBinGuard();

			// A keyboard Cmd/Ctrl+V is routed through MediumEditor's body-level
			// paste bin, which the listener above never sees. The editor refuses
			// the image there and reports it with this event (V03).
			$form.on( 'bb-image-paste-blocked.bbDraftImageGuard', function () {
				self.showDraftFeedback( BP_Nouveau.forums.paste_image_blocked_message );
			} );
		};

		/**
		 * Display a transient feedback message on the draft composer.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param {string} message The feedback message to display.
		 *
		 * @return {void}
		 */
		this.showDraftFeedback = function ( message ) {
			var $form = this.draftNoticeForm();

			if ( ! $form.length ) {
				return;
			}

			var $notice = $form.find( '.bb-draft-save-feedback' );

			if ( ! message ) {
				$notice.remove();
				return;
			}

			if ( $notice.length ) {
				$notice.find( 'p' ).text( message );

				return;
			}

			// Built with its text already in place: an empty role="alert" node
			// inserted first and filled afterwards is not announced by several
			// screen readers, and this is the only signal a member gets that a
			// save was refused.
			this.insertDraftNotice(
				$form,
				$( '<div class="bb-draft-save-feedback bp-messages bp-feedback error" role="alert"></div>' )
					.append( $( '<span class="bp-icon" aria-hidden="true"></span>' ) )
					.append( $( '<p></p>' ).text( message ) )
			);
		};

		/**
		 * Tear down both draft notices.
		 *
		 * The notices belong to the OPEN composer, not to the stored draft, so
		 * every path that resets the form has to take them down with it -
		 * discard and close both funnel through resetTopicReplyDraftPostForm().
		 *
		 * Both calls are no-ops when the nodes are absent.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @return {void}
		 */
		this.clearDraftNotices = function () {
			this.showDraftRetentionNotice( false );
			this.showDraftFeedback( '' );
		};

		/**
		 * Strip scriptable markup from a restored draft's content.
		 *
		 * The localStorage copy of a forum draft is member-editable storage
		 * that never passes through the server's kses sanitization, so a
		 * tampered local copy could otherwise inject script-capable markup into
		 * the editor when the draft is restored. The content is parsed in an
		 * inert document, so nothing executes or loads during the cleanup;
		 * normal editor markup passes through untouched.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param {string} content Draft HTML content.
		 *
		 * @return {string} The content with scriptable markup removed.
		 */
		this.sanitizeDraftContent = function ( content ) {
			if ( ! content || 'string' !== typeof content ) {
				return content;
			}

			var doc            = document.implementation.createHTMLDocument( '' );
			doc.body.innerHTML = content;

			// Elements that can execute script, restyle the page, or hijack the host form.
			var blocked = doc.body.querySelectorAll( 'script, style, iframe, frame, frameset, object, embed, applet, form, input, button, textarea, select, link, meta, base, template, noscript, svg, math' );
			for ( var i = 0; i < blocked.length; i++ ) {
				if ( blocked[ i ].parentNode ) {
					blocked[ i ].parentNode.removeChild( blocked[ i ] );
				}
			}

			var nodes = doc.body.querySelectorAll( '*' );
			for ( var j = 0; j < nodes.length; j++ ) {
				var attrs = nodes[ j ].attributes;
				for ( var k = attrs.length - 1; 0 <= k; k-- ) {
					var attr_name = attrs[ k ].name.toLowerCase();

					// Drop non-printable characters so schemes like "java\nscript:" can't hide from the test below.
					var attr_value = attrs[ k ].value.replace( /[^\x21-\x7E]/g, '' ).toLowerCase();

					if (
						0 === attr_name.indexOf( 'on' ) ||
						// A style attribute can position an overlay for UI-redress and load
						// remote URLs via url(...), so it is dropped like the <style> element.
						'style' === attr_name ||
						(
							-1 !== [ 'href', 'src', 'srcset', 'poster' ].indexOf( attr_name ) &&
							/(^|,)(javascript|vbscript|data):/.test( attr_value )
						)
					) {
						nodes[ j ].removeAttribute( attrs[ k ].name );
					}
				}
			}

			return doc.body.innerHTML;
		};

		/**
		 * Restore the saved draft into the appropriate topic or reply form.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @return {void}
		 */
		this.displayTopicReplyDraft = function () {
			bp.Nouveau.Media.reply_topic_allow_delete_media = true;
			if ( _.isUndefined( this.topic_reply_draft ) ) {
				return;
			}

			if ( 'topic' === this.topic_reply_draft.object ) {
				this.appendTopicDraftData();
			} else {
				this.appendReplyDraftData();
			}
		};

		/**
		 * Populate the new-topic form from the restored draft.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @return {void}
		 */
		this.appendTopicDraftData = function() {
			// Never clobber content the member typed while the draft fetch was
			// in flight.
			if ( this.hasMemberTypedContent() ) {
				this.announceSuppressedDraftRestore();

				return;
			}

			this.getTopicReplyDraftData();

			var $form         = this.currentForm ? this.currentForm : $('form#new-post'),
				activity_data = {},
				editor_key    = $form.find( '.bbp-the-content:not(.emojionearea)' ).data( 'key' ),
				$editor       = $form.find('#bbp_editor_topic_content_' + editor_key);

			if ( 'undefined' !== typeof this.all_draft_data[this.topic_reply_draft.data_key] ) {
				activity_data = this.all_draft_data[this.topic_reply_draft.data_key];
			}

			// The local copy of the draft is member-editable storage - scrub
			// scriptable markup before it reaches the editor.
			if ( activity_data && activity_data.bbp_topic_content ) {
				activity_data.bbp_topic_content = this.sanitizeDraftContent( activity_data.bbp_topic_content );
			}

			if (
				(
					'undefined' === typeof activity_data.bbp_topic_title &&
					'undefined' === typeof activity_data.bbp_topic_content
				) ||
				(
					'' === activity_data.bbp_topic_title &&
					'' === activity_data.bbp_topic_content &&
					(
						'undefined' === typeof activity_data.bbp_media ||
						'' === activity_data.bbp_media ||
						'[]' === activity_data.bbp_media
					) &&
					(
						'undefined' === typeof activity_data.bbp_document ||
						'' === activity_data.bbp_document ||
						'[]' === activity_data.bbp_document
					) &&
					(
						'undefined' === typeof activity_data.bbp_video ||
						'' === activity_data.bbp_video ||
						'[]' === activity_data.bbp_video
					)
				)
			) {
				return;
			}

			// Add class to display draft.
			$form.addClass( 'has-draft' );
			this.showDraftRetentionNotice();

			// Title.
			if (
				'undefined' !== typeof activity_data.bbp_topic_title &&
				'' !== activity_data.bbp_topic_title
			) {
				$form.find( '#bbp_topic_title' ).val( activity_data.bbp_topic_title );
				$form.addClass( 'has-title' );
			}

			// Content.
			if (
				'undefined' !== typeof activity_data.bbp_topic_content &&
				'' !== activity_data.bbp_topic_content
			) {
				var element = $editor.get( 0 );
				var $meditor = window.MediumEditor ? window.MediumEditor.getEditorFromElement( element ) : null;
				if ( $meditor !== null ) {
					$meditor.setContent( activity_data.bbp_topic_content );
					if ( $( element ).text() !== '' ) {
						$form.addClass( 'has-content' );
					}
				} else {
					$editor.html( activity_data.bbp_topic_content );
					$form.find( '#bbp_topic_content' ).focus();
					$form.find( '#bbp_topic_content' ).val( activity_data.bbp_topic_content );
					if ( $( element ).val() !== '' ) {
						$form.addClass( 'has-content' );
					}
				}
			}

			// Stick topic.
			$form.find( '#bbp_stick_topic_select option[value="' + activity_data.bbp_stick_topic + '"]' ).prop( 'selected', true );

			// Subscribe notify.
			var bbp_topic_subscription_id = ( forms.length > 1 && this.bbp_topic_id ) ? '#bbp_topic_subscription_' + this.bbp_topic_id : '#bbp_topic_subscription';

			if ( 'undefined' !== typeof activity_data.bbp_topic_subscription && '' !== activity_data.bbp_topic_subscription ) {
				$form.find( bbp_topic_subscription_id ).prop( 'checked', true );
			} else if ( 'undefined' !== typeof activity_data.bbp_topic_subscription && '' === activity_data.bbp_topic_subscription ) {
				$form.find( bbp_topic_subscription_id ).prop( 'checked', false );
			} else if ( 0 < this.bbp_forum_id && ! $( '#subscribe-' + this.bbp_forum_id ).hasClass( 'is-subscribed' ) ) {
				$form.find( bbp_topic_subscription_id ).prop( 'checked', false );
			}

			// Tags.
			if ( 'undefined' !== typeof activity_data.bbp_topic_tags && null !== activity_data.bbp_topic_tags ) {

				$form.find( '#bbp_topic_tags' ).val( activity_data.bbp_topic_tags );

				var tags_element = $form.find( '#bbp_topic_tags_dropdown' );
				tags_element.val( '' );

				if ( '' !== activity_data.bbp_topic_tags ) {
					_.each(
						activity_data.bbp_topic_tags.split( ',' ),
						function( val ) {
							tags_element.append( new Option( val, val, false, true ) );
						}
					);
				}

				tags_element.trigger( 'change' );
			}

			// Link preview.
			if (
				'undefined' !== typeof activity_data.bb_link_url &&
				'' !== activity_data.bb_link_url
			) {
				if ( $form.find( '#bb_link_url' ).length > 0 ) {
					$form.find( '#bb_link_url' ).remove();
				}

				$( '<input>' ).attr( {
					type: 'hidden',
					id: 'bb_link_url',
					name: 'bb_link_url',
					value: activity_data.bb_link_url,
				} ).appendTo( $form );
			}

			this.previewDraftMedia( $form, activity_data );
		};

		/**
		 * Populate the reply form from the restored draft.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @return {void}
		 */
		this.appendReplyDraftData = function() {
			// Never clobber content the member typed while the draft fetch was
			// in flight.
			if ( this.hasMemberTypedContent() ) {
				this.announceSuppressedDraftRestore();

				return;
			}

			this.getTopicReplyDraftData();

			var $form         = this.currentForm ? this.currentForm : $( 'form#new-post' ),
				activity_data = {},
				editor_key    = $form.find( '.bbp-the-content:not(.emojionearea)' ).data( 'key' ),
				$editor       = $form.find( '#bbp_editor_reply_content_' + editor_key );

			if ( 'undefined' !== typeof this.all_draft_data[this.topic_reply_draft.data_key] ) {
				activity_data = this.all_draft_data[this.topic_reply_draft.data_key];
			}

			// The local copy of the draft is member-editable storage - scrub
			// scriptable markup before it reaches the editor.
			if ( activity_data && activity_data.bbp_reply_content ) {
				activity_data.bbp_reply_content = this.sanitizeDraftContent( activity_data.bbp_reply_content );
			}

			if (
				(
					'undefined' === typeof activity_data.bbp_reply_content &&
					'undefined' === typeof activity_data.bb_link_url
				) ||
				(
					'' === activity_data.bbp_reply_content &&
					(
						'undefined' === typeof activity_data.bb_link_url ||
						 '' === activity_data.bb_link_url
					) &&
					(
						'undefined' === typeof activity_data.bbp_media ||
						'' === activity_data.bbp_media ||
						'[]' === activity_data.bbp_media
					) &&
					(
						'undefined' === typeof activity_data.bbp_document ||
						'' === activity_data.bbp_document ||
						'[]' === activity_data.bbp_document
					) &&
					(
						'undefined' === typeof activity_data.bbp_video ||
						'' === activity_data.bbp_video ||
						'[]' === activity_data.bbp_video
					)
				)
			) {
				return;
			}

			// Add class to display draft.
			$form.addClass( 'has-draft' );
			this.showDraftRetentionNotice();

			// Content.
			if (
				'undefined' !== typeof activity_data.bbp_reply_content &&
				'' !== activity_data.bbp_reply_content
			) {
				var element = $editor.get( 0 );
				var $meditor = window.MediumEditor ? window.MediumEditor.getEditorFromElement( element ) : null;
				if ( $meditor !== null ) {
					$meditor.setContent( activity_data.bbp_reply_content );
					if ( $( element ).text() !== '' ) {
						$form.addClass( 'has-content' );
					}
				} else {
					$editor.html( activity_data.bbp_reply_content );
					$form.find( '#bbp_reply_content' ).focus();
					$form.find( '#bbp_reply_content' ).val( activity_data.bbp_reply_content );
					if ( $( element ).val() !== '' ) {
						$form.addClass( 'has-content' );
					}
				}
			}

			// Subscribe notify.
			var bbp_topic_subscription_id = ( forms.length > 1 && this.bbp_topic_id ) ? '#bbp_topic_subscription_' + this.bbp_topic_id : '#bbp_topic_subscription';

			if ( 'undefined' !== typeof activity_data.bbp_topic_subscription && '' !== activity_data.bbp_topic_subscription ) {
				$form.find( bbp_topic_subscription_id ).prop( 'checked', true );
			} else if ( 'undefined' !== typeof activity_data.bbp_topic_subscription && '' === activity_data.bbp_topic_subscription ) {
				$form.find( bbp_topic_subscription_id ).prop( 'checked', false );
			} else if ( 0 < this.bbp_topic_id && ! $( '#subscribe-' + this.bbp_topic_id ).hasClass( 'is-subscribed' ) ) {
				$form.find( bbp_topic_subscription_id ).prop( 'checked', false );
			}

			// Tags.
			if ( 'undefined' !== typeof activity_data.bbp_topic_tags && null !== activity_data.bbp_topic_tags ) {

				$form.find( '#bbp_topic_tags' ).val( activity_data.bbp_topic_tags );

				var tags_element = $form.find( '#bbp_topic_tags_dropdown' );
				tags_element.val( '' );

				if ( '' !== activity_data.bbp_topic_tags ) {
					_.each(
						activity_data.bbp_topic_tags.split( ',' ),
						function( val ) {
							tags_element.append( new Option( val, val, false, true ) );
						}
					);
				}

				tags_element.trigger( 'change' );
			}

			// Link preview.
			if ( 'undefined' !== typeof activity_data.bb_link_url && '' !== activity_data.bb_link_url ) {
				$form.find( '#bb_link_url' ).remove();
				$('<input>').attr({
					type: 'hidden',
					id: 'bb_link_url',
					name: 'bb_link_url',
					value: activity_data.bb_link_url
				}).appendTo( $form );
			}

			this.previewDraftMedia( $form, activity_data );
		};

		/**
		 * Render the restored draft's media, document, video and gif previews on the form.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param {jQuery} $form The draft form to render previews into.
		 * @param {Object} activity_data The restored draft data.
		 *
		 * @return {void}
		 */
		this.previewDraftMedia = function( $form, activity_data ) {
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

					$form.addClass( 'has-media' );

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
								'saved': false,
								'svg_icon': ! _.isUndefined( draft_documents[ d ].svg_icon ) ? draft_documents[ d ].svg_icon : ''
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
							id: draft_documents[ d ].id,
							svg_icon: ! _.isUndefined( draft_documents[ d ].svg_icon ) ? draft_documents[ d ].svg_icon : ''
						};

						self.dropzone_obj[ d_dropzone_obj_key ].files.push( d_mock_file );
						self.dropzone_obj[ d_dropzone_obj_key ].emit( 'addedfile', d_mock_file );
						// A draft keeps the upload response's formatted size ("14 KB"), which
						// Dropzone's numeric formatter shows as "0 b"; show the stored text.
						if ( d_mock_file.previewElement && d_mock_file.size && ! _.isFinite( Number( d_mock_file.size ) ) ) {
							$( d_mock_file.previewElement ).find( '[data-dz-size]' ).text( String( d_mock_file.size ) );
						}
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

					$form.addClass( 'has-media' );

				}
			}

			// Video.
			if ( 'undefined' !== typeof dropzone_video_container.length && 0 < dropzone_video_container.length && 'undefined' !== typeof activity_data.bbp_video && '' !== activity_data.bbp_video ) {
				$form.find( '#bbp_video' ).val( activity_data.bbp_video );
				var draft_videos = JSON.parse( activity_data.bbp_video );

				if ( draft_videos.length ) {
					$form.find( 'a#forums-video-button' ).trigger( 'click' );
					$form.addClass( 'media-uploading draft-video-uploading' );

					var v_mock_file        = false,
						v_dropzone_obj_key = dropzone_video_container.data( 'key' );

					// Object to track processed video count.
					var processed_videos_counter = { count: 0 };

					/**
					 * Internal function to check and update video thumbnails.
					 *
					 * @param {object} mock_file The mock file object.
					 * @param {object} video     The video data object.
					 * @param {object} form      The jQuery form object.
					 * @param {Array}  videos    Array of all draft videos.
					 * @param {object} counter   Object tracking processed videos count.
					 */
					var checkAndSaveVideoThumbnail = function( mock_file, video, form, videos, counter ) {
						var thumbnail_check = setInterval( function() {
							var $preview_element = $( mock_file.previewElement ).closest( '.dz-preview' );

							if ( $preview_element.hasClass( 'dz-has-no-thumbnail' ) || $preview_element.hasClass( 'dz-has-thumbnail' ) ) {
								video.js_preview = $preview_element.find( '.dz-video-thumbnail img' ).attr( 'src' );
								clearInterval( thumbnail_check );

								counter.count++;

								// Only update the form field and dropzone media when all videos are processed.
								if ( counter.count >= videos.length ) {
									form.find( '#bbp_video' ).val( JSON.stringify( videos ) );
									form.removeClass( 'media-uploading draft-video-uploading' );

									// Update the videos in self.dropzone_media.
									var video_dropzone_container = form.find( '#forums-post-video-uploader' );

									// Ensure the container exists before proceeding.
									if ( 0 < video_dropzone_container.length ) {
										var video_dropzone_obj_key = video_dropzone_container.data( 'key' );

										// Validate that the key exists and self.dropzone_media contains the key.
										if (
											video_dropzone_obj_key && 
											self.dropzone_media && 
											typeof self.dropzone_media === 'object' && 
											Object.prototype.hasOwnProperty.call( self.dropzone_media, video_dropzone_obj_key )
										) {
											var dropzone_videos = self.dropzone_media[ video_dropzone_obj_key ];

											// Ensure dropzone_videos and videos are arrays before iterating.
											if ( Array.isArray( dropzone_videos ) && Array.isArray( videos ) ) {
												for ( var i = 0; i < dropzone_videos.length; i++ ) {
													if ( ! dropzone_videos[i] || ! dropzone_videos[i].id ) {
														continue;
													}

													for ( var j = 0; j < videos.length; j++ ) {
														if ( ! videos[j] || ! videos[j].id ) {
															continue;
														}

														if ( dropzone_videos[i].id === videos[j].id ) {
															if ( 'undefined' !== typeof videos[j].js_preview && videos[j].js_preview ) {
																dropzone_videos[i].js_preview = videos[j].js_preview;
															}
															break;
														}
													}
												}
											}
										}
									}
								}
							}
						}, 100 );
					};

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

						// Call function to check for thumbnail generation and save it.
						checkAndSaveVideoThumbnail( v_mock_file, draft_videos[ v ], $form, draft_videos, processed_videos_counter );

						self.dropzone_obj[ v_dropzone_obj_key ].emit( 'dz-success', v_mock_file );
						self.dropzone_obj[ v_dropzone_obj_key ].emit( 'complete', v_mock_file );
					}

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

					$form.addClass( 'has-media' );

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
						$form.addClass( 'has-gif' );
					}
				}
			}
		};

		/**
		 * Handle the composer submit: persist the draft, then let the post proceed.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @return {void}
		 */
		this.submitTopicReplyDraftForm = function () {
			this.topic_reply_draft.post_action = 'delete';
			this.clearTopicReplyDraftIntervals();
			this.resetLocalTopicReplyDraft();
		};

		/**
		 * Discard the current draft and clear its stored copies.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @return {void}
		 */
		this.discardTopicReplyDraftForm = function () {

			// After a conflict or a "not loaded" outcome no request is sent for
			// this key (postTopicReplyDraft() stops there), so clearing the form
			// and local copy here only hid a draft the server still holds, and
			// it came back on the next visit. Keep everything and say so (F-09).
			if ( this.draft_conflict_keys[ this.topic_reply_draft.data_key ] || this.draft_blocked_keys[ this.topic_reply_draft.data_key ] ) {
				this.showDraftFeedback( ( 'undefined' !== typeof BP_Nouveau.forums && BP_Nouveau.forums.draft_discard_failed_message ) ? BP_Nouveau.forums.draft_discard_failed_message : '' );

				return;
			}

			var forum_topic = this.currentForm.find( 'a[data-modal-id]' ),
				forum_reply = this.currentForm.find( '.bbp-reply-to-link' );

			forum_topic.css( 'pointer-events', 'none' );
			forum_reply.css( 'pointer-events', 'none' );

			this.topic_reply_draft.post_action = 'delete';
			// Kept until the server answers: resetLocalTopicReplyDraft() below
			// removes the local copy, and a failed discard puts it back (N-1).
			this.draft_discard_copies[ this.topic_reply_draft.data_key ] = localStorage.getItem( this.topic_reply_draft.data_key );
			// Marked before the request goes out: a reload racing the discard
			// must not be offered this draft again.
			bp.Nouveau.TopicReplyDraft.markDiscarded( this.topic_reply_draft.data_key, true );
			// In-page discard goes over XHR, not sendBeacon - the beacon
			// transport is reserved for unload, and its quota failure mode
			// must never decide whether a deliberate discard reaches the
			// server.
			this.postTopicReplyDraft( true, false, false );
			this.clearTopicReplyDraftIntervals();
			this.resetLocalTopicReplyDraft();
			this.resetTopicReplyDraftPostForm();
			this.resetTopicReplyDraftLinkPreview();
			this.topic_reply_draft.post_action = 'update';
			this.setupTopicReplyDraftIntervals();
			this.is_topic_reply_form_submit = false;

			forum_topic.css( 'pointer-events', '' );
			forum_reply.css( 'pointer-events', '' );
		};

		/**
		 * Undo the local half of a discard the server did not confirm.
		 *
		 * Clears the reload guard and puts the local copy back, so reopening the
		 * composer or reloading offers the draft and its Discard button again.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param {string} draft_key The discarded draft's key.
		 *
		 * @return {void}
		 */
		this.undoFailedDiscard = function ( draft_key ) {
			var copy = this.draft_discard_copies[ draft_key ];

			delete this.draft_discard_copies[ draft_key ];
			bp.Nouveau.TopicReplyDraft.markDiscarded( draft_key, false );

			// Never over a copy the member has written since the discard.
			if ( copy && null === localStorage.getItem( draft_key ) ) {
				try {
					localStorage.setItem( draft_key, copy );
				} catch ( e ) {
					// Full storage: the server copy still comes back on reload.
				}
			}
		};

		/**
		 * Persist the draft on page unload so an accidental reload does not lose it.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @return {void}
		 */
		this.setupOnReloadWindow = function () {
			if ( 'update' === this.topic_reply_draft.post_action ) {

				// Clear intervals.
				this.clearTopicReplyDraftIntervals();
				// Collect draft data.
				this.collectTopicReplyDraftActivity();
				// Send the latest draft data.
				this.postTopicReplyDraft( false, true, true );
			}
		};
	};

	// A discard clears localStorage immediately while its XHR is still in
	// flight. A reload landing in that window re-renders has_draft as true and
	// the lazy fetch hands the just-discarded draft straight back. The activity
	// composer guards this with a cookie; the forum pack has no cookie
	// dependency, so the marker lives in sessionStorage - same tab, survives the
	// reload, gone when the tab closes.
	var BB_DRAFT_DISCARDED_KEY = 'bb_forum_drafts_discarded';

	var bbDraftDiscardedKeys = function () {
		try {
			var raw = window.sessionStorage.getItem( BB_DRAFT_DISCARDED_KEY );

			return raw ? ( JSON.parse( raw ) || [] ) : [];
		} catch ( e ) {
			// Private browsing, disabled storage, or malformed JSON - the guard
			// is an optimisation, never a correctness requirement.
			return [];
		}
	};

	var bbDraftMarkDiscarded = function ( dataKey, discarded ) {
		if ( ! dataKey ) {
			return;
		}

		try {
			var keys  = bbDraftDiscardedKeys(),
				index = keys.indexOf( dataKey );

			if ( discarded && -1 === index ) {
				keys.push( dataKey );
			} else if ( ! discarded && -1 !== index ) {
				keys.splice( index, 1 );
			} else {
				return;
			}

			window.sessionStorage.setItem( BB_DRAFT_DISCARDED_KEY, JSON.stringify( keys ) );
		} catch ( e ) {
			// See bbDraftDiscardedKeys().
		}
	};

	bp.Nouveau.TopicReplyDraft.markDiscarded = bbDraftMarkDiscarded;

	// Module-level on purpose: instance methods reference `forms` (multi-form
	// subscription IDs) from this closure.
	var forms = $( 'form[name="new-post"]' );


	/**
	 * A new random revision id for one draft save.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return {string}
	 */
	var bbDraftNewRev = function () {
		var chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789',
			rev   = '',
			i;

		for ( i = 0; i < 16; i++ ) {
			rev += chars.charAt( Math.floor( Math.random() * chars.length ) );
		}

		return rev;
	};

	/**
	 * The revisions a stored or local draft copy was built on.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {Object} copy Draft entry or local copy.
	 * @return {Array}
	 */
	var bbDraftBaseFrom = function ( copy ) {
		if ( copy && _.isArray( copy._draft_base ) && copy._draft_base.length ) {
			return copy._draft_base.slice( -8 );
		}

		return [ ( copy && copy._draft_rev ) ? String( copy._draft_rev ) : '' ];
	};

	/**
	 * Add a revision to the base of a local copy (after a beacon replayed it).
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {string} key Inner draft key.
	 * @param {string} rev Revision the replay sent.
	 * @return {void}
	 */
	var bbDraftAddBaseToLocal = function ( key, rev ) {
		try {
			var copy = JSON.parse( localStorage.getItem( key ) || 'null' );

			if ( copy && 'object' === typeof copy ) {
				copy._draft_base = _.uniq( bbDraftBaseFrom( copy ).concat( [ rev ] ) ).slice( -8 );
				localStorage.setItem( key, JSON.stringify( copy ) );
			}
		} catch ( e ) {
			// Without it the next save from that copy is refused, never overwrites.
		}
	};

	/**
	 * Hand-back keys this browser stored and has not reported to the server yet.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return {Array}
	 */
	var bbDraftGetAcks = function () {
		try {
			return _.compact( String( localStorage.getItem( bbDraftAckStorageKey() ) || '' ).split( ',' ) );
		} catch ( e ) {
			return [];
		}
	};

	/**
	 * Forget acknowledgements a request delivered.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {Array} sent Keys the request carried.
	 * @return {void}
	 */
	var bbDraftClearAcks = function ( sent ) {
		try {
			var left = _.difference( bbDraftGetAcks(), sent || [] );

			if ( left.length ) {
				localStorage.setItem( bbDraftAckStorageKey(), left.join( ',' ) );
			} else {
				localStorage.removeItem( bbDraftAckStorageKey() );
			}
		} catch ( e ) {
			// Harmless: the server drops entries it already holds again.
		}
	};

	/**
	 * What to keep when a draft is handed back for a key this browser may
	 * already hold: 'local', 'entry' or 'merge'.
	 *
	 * The handed-back entry is acknowledged right after it is stored, and the
	 * server then drops its held copy, so neither the handed-back draft nor the
	 * member's local text may be lost:
	 * 1. no local copy, or one stamped with another member (bb_draft_owner on a
	 *    forum copy, data.user_id on an activity copy - a shared browser): the
	 *    entry;
	 * 2. the entry carries a revision and the copy is that revision or was
	 *    built on it - a revision is unique to this member's stored draft, so
	 *    this proves the copy is theirs even without an owner stamp: the copy;
	 * 3. an ownerless copy: the entry - text whose owner is unproven is never
	 *    merged into this member's draft;
	 * 4. the same content: the copy;
	 * 5. the copy is unsynced (pending) or was edited after the entry was
	 *    saved: both, merged ({@see bbDraftMergeHandback});
	 * 6. otherwise the copy is an older copy: the entry.
	 * Stored drafts carry no base list, so an entry never proves the copy is
	 * its ancestor beyond rule 6.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {Object|null} existing  Copy stored under the key, parsed.
	 * @param {Object}      entry     Handed-back entry.
	 * @param {string}      entry_rev Entry revision, or ''.
	 * @param {boolean}     is_forum  Whether the key is a forum inner key.
	 * @param {number}      owner_id  Current member ID.
	 * @return {string} 'local', 'entry' or 'merge'.
	 */
	var bbDraftHandbackDecision = function ( existing, entry, entry_rev, is_forum, owner_id ) {
		var existing_owner;

		if ( ! existing || 'object' !== typeof existing || ! owner_id ) {
			return 'entry';
		}

		if ( is_forum ) {
			existing_owner = existing.bb_draft_owner ? parseInt( existing.bb_draft_owner, 10 ) : 0;
		} else {
			existing_owner = ( existing.data && existing.data.user_id ) ? parseInt( existing.data.user_id, 10 ) : 0;
		}

		if ( existing_owner && existing_owner !== owner_id ) {
			return 'entry';
		}

		if ( entry_rev && ( String( existing._draft_rev || '' ) === entry_rev || -1 !== _.indexOf( bbDraftBaseFrom( existing ), entry_rev ) ) ) {
			return 'local';
		}

		if ( existing_owner !== owner_id ) {
			return 'entry';
		}

		if ( _.isEqual( existing.data, entry.data ) ) {
			return 'local';
		}

		// The last edit, not the last write: the page-unload save rewrites an
		// unchanged copy, which must not make a stale copy look newer.
		if ( true === existing.bb_draft_pending_sync || ( parseInt( existing._draft_edit_at, 10 ) || 0 ) > ( parseInt( entry._draft_saved_at, 10 ) || 0 ) ) {
			return 'merge';
		}

		return 'entry';
	};

	/**
	 * Whether draft HTML holds anything to keep (text or an image).
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {*} html Draft HTML.
	 * @return {boolean}
	 */
	var bbDraftHtmlHasContent = function ( html ) {
		return 'string' === typeof html && '' !== $.trim( html.replace( /<(?!img\b)[^>]*>/gi, '' ).replace( /&nbsp;| /g, ' ' ) );
	};

	/**
	 * Append the local text to the handed-back text, unless it adds nothing.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {*} base  Handed-back HTML.
	 * @param {*} extra Local HTML.
	 * @return {*} The merged HTML, or the handed-back value unchanged.
	 */
	var bbDraftAppendHtml = function ( base, extra ) {
		var base_html = 'string' === typeof base ? base : '';

		if ( ! bbDraftHtmlHasContent( extra ) || extra === base_html || -1 !== base_html.indexOf( extra ) ) {
			return base;
		}

		if ( ! bbDraftHtmlHasContent( base_html ) ) {
			return extra;
		}

		return base_html + ( /<\/(?:p|div|ul|ol|li|blockquote|pre|h[1-6]|table)>\s*$/i.test( base_html ) ? '' : '<br>' ) + extra;
	};

	/**
	 * Whether a draft field is empty (unset, blank, or an empty list).
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {*} value Field value.
	 * @return {boolean}
	 */
	var bbDraftIsBlankValue = function ( value ) {
		if ( _.isUndefined( value ) || null === value || false === value ) {
			return true;
		}

		if ( 'string' === typeof value ) {
			return -1 !== _.indexOf( [ '', '[]', '{}' ], $.trim( value ) );
		}

		return 'object' === typeof value && _.isEmpty( value );
	};

	/**
	 * Add the local copy's attachments to the handed-back ones, by ID.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {*}       base    Handed-back list.
	 * @param {*}       extra   Local list.
	 * @param {boolean} as_json Whether the lists are JSON strings (forum).
	 * @return {*} The merged list, or the handed-back value unchanged.
	 */
	var bbDraftUnionAttachments = function ( base, extra, as_json ) {
		var parse = function ( value ) {
				if ( as_json ) {
					if ( ! value ) {
						return [];
					}

					try {
						value = JSON.parse( value );
					} catch ( e ) {
						return null;
					}
				}

				if ( _.isArray( value ) ) {
					return value.slice( 0 );
				}

				return value ? null : [];
			},
			list  = parse( base ),
			more  = parse( extra ),
			ids   = [],
			added = false;

		if ( null === list || null === more || ! more.length ) {
			return base;
		}

		_.each(
			list,
			function ( item ) {
				if ( item && item.id ) {
					ids.push( String( item.id ) );
				}
			}
		);

		_.each(
			more,
			function ( item ) {
				var id = ( item && item.id ) ? String( item.id ) : '';

				if ( id && -1 === _.indexOf( ids, id ) ) {
					list.push( item );
					ids.push( id );
					added = true;
				}
			}
		);

		if ( ! added ) {
			return base;
		}

		return as_json ? JSON.stringify( list ) : list;
	};

	/**
	 * The handed-back draft with the member's local text and attachments added.
	 *
	 * The entry keeps its revision; the local text is appended after its text
	 * and the attachment lists are joined by ID. The result is marked pending
	 * and based on nothing (`_draft_base` ['none']): it matches no stored
	 * draft, so a save over a newer stored one is refused instead of replacing
	 * it.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {Object}  existing Copy stored under the key, parsed.
	 * @param {Object}  entry    Handed-back entry.
	 * @param {boolean} is_forum Whether the key is a forum inner key.
	 * @param {number}  owner_id Current member ID.
	 * @return {Object}
	 */
	var bbDraftMergeHandback = function ( existing, entry, is_forum, owner_id ) {
		var merged = $.extend( true, {}, entry ),
			local  = ( existing.data && 'object' === typeof existing.data ) ? existing.data : {},
			data   = ( merged.data && 'object' === typeof merged.data ) ? merged.data : {},
			now    = Math.floor( Date.now() / 1000 ),
			fields = is_forum ?
				{ html: [ 'bbp_topic_content', 'bbp_reply_content' ], fill: [ 'bbp_topic_title', 'bbp_topic_tags', 'bbp_media_gif' ], lists: [ 'bbp_media', 'bbp_document', 'bbp_video' ] } :
				{ html: [ 'content' ], fill: [ 'post_title', 'gif_data' ], lists: [ 'media', 'document', 'video' ] };

		_.each(
			fields.html,
			function ( field ) {
				var value = bbDraftAppendHtml( data[ field ], local[ field ] );

				if ( ! _.isUndefined( value ) ) {
					data[ field ] = value;
				}
			}
		);

		_.each(
			fields.fill,
			function ( field ) {
				if ( bbDraftIsBlankValue( data[ field ] ) && ! bbDraftIsBlankValue( local[ field ] ) ) {
					data[ field ] = local[ field ];
				}
			}
		);

		_.each(
			fields.lists,
			function ( field ) {
				var value = bbDraftUnionAttachments( data[ field ], local[ field ], is_forum );

				if ( ! _.isUndefined( value ) ) {
					data[ field ] = value;
				}
			}
		);

		if ( is_forum ) {
			merged.bb_draft_owner = owner_id;
		} else if ( owner_id ) {
			data.user_id = owner_id;
		}

		merged.data                  = data;
		merged.bb_draft_pending_sync = true;
		merged._draft_local_at       = now;
		merged._draft_edit_at        = now;
		merged._draft_base           = [ 'none' ];
		delete merged.bb_draft_interim;

		return merged;
	};

	/**
	 * Skip hand-backs for the keys of forms the member typed into before the
	 * draft fetch resolved.
	 *
	 * Stored there, the hand-back would be replaced by the form's text on the
	 * next local save, and its acknowledgement would let the server drop it.
	 * Left unacknowledged it stays held, the form's text is not saved over it
	 * (the not-restored state blocks the key), and the next visit merges the
	 * two ({@see bbDraftHandbackDecision}).
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {jQuery} $forms Forum forms on the page.
	 * @param {Object} drafts Evicted entries keyed like `evicted_draft_keys`.
	 * @return {void}
	 */
	var bbDraftSkipHandbackForTypedForms = function ( $forms, drafts ) {
		$forms.each(
			function () {
				var $form = $( this ),
					key   = bbDraftFormKey( $form );

				if ( ! key || ! drafts || ( ! drafts[ 'bb_user_topic_reply_draft:' + key ] && ! drafts[ key ] ) ) {
					return;
				}

				if ( bbDraftFormSnapshot( $form ) !== $form.data( 'bbDraftInitialSnapshot' ) ) {
					window.bbDraftHandbackSkipKeys        = window.bbDraftHandbackSkipKeys || {};
					window.bbDraftHandbackSkipKeys[ key ] = true;
				}
			}
		);
	};

	/**
	 * Keep drafts the server evicted from the draft budget in localStorage.
	 *
	 * Forum inner keys ("meta_key:inner_key") are stored in this composer's
	 * shape, activity keys in the activity composer's shape. Each copy is
	 * marked pending, because the server no longer holds it. A copy this
	 * browser already holds is kept, replaced or merged with the hand-back
	 * ({@see bbDraftHandbackDecision}); a kept copy is marked pending too, so
	 * no composer close wipes what is now the only copy. A key is acknowledged
	 * only once its copy is stored. A key the member is typing over unloaded
	 * is skipped and stays held on the server
	 * ({@see bbDraftSkipHandbackForTypedForms}).
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {Object} drafts Evicted entries keyed like `evicted_draft_keys`.
	 * @return {void}
	 */
	var bbDraftStoreHandback = function ( drafts ) {
		var owner_id = parseInt( BP_Nouveau.forums.params.bb_current_user_id, 10 ),
			acks     = bbDraftGetAcks();

		if ( ! drafts || 'object' !== typeof drafts ) {
			return;
		}

		_.each(
			drafts,
			function ( entry, key ) {
				var storage_key = String( key ).split( ':' ).pop(),
					copy;

				if ( ! entry || 'object' !== typeof entry || ! storage_key ) {
					return;
				}

				var entry_rev = entry._draft_rev ? String( entry._draft_rev ) : '',
					is_forum  = -1 !== String( key ).indexOf( ':' ),
					existing,
					decision;

				// The member is typing in this key's form over a draft that was
				// not loaded: left held on the server, unacknowledged
				// ({@see bbDraftSkipHandbackForTypedForms}).
				if ( window.bbDraftHandbackSkipKeys && window.bbDraftHandbackSkipKeys[ storage_key ] ) {
					return;
				}

				try {
					existing = JSON.parse( localStorage.getItem( storage_key ) || 'null' );
				} catch ( e ) {
					existing = null;
				}

				try {
					decision = bbDraftHandbackDecision( existing, entry, entry_rev, is_forum, owner_id );

					if ( 'local' === decision ) {
						existing.bb_draft_pending_sync = true;
						localStorage.setItem( storage_key, JSON.stringify( existing ) );
					} else if ( 'merge' === decision ) {
						localStorage.setItem( storage_key, JSON.stringify( bbDraftMergeHandback( existing, entry, is_forum, owner_id ) ) );
					} else {
						copy = _.extend( {}, entry, { bb_draft_pending_sync: true, _draft_local_at: Math.floor( Date.now() / 1000 ) } );
						if ( is_forum ) {
							copy.bb_draft_owner = owner_id;
						} else if ( owner_id && copy.data && 'object' === typeof copy.data && _.isUndefined( copy.data.user_id ) ) {
							copy.data = _.extend( {}, copy.data, { user_id: owner_id } );
						}

						localStorage.setItem( storage_key, JSON.stringify( copy ) );
					}
				} catch ( e ) {
					// Over quota: not acknowledged, so the server keeps holding
					// it and hands it back again on the next request.
					return;
				}

				// Acknowledge only a copy this browser actually stored.
				acks.push( String( key ) );
			}
		);

		try {
			localStorage.setItem( bbDraftAckStorageKey(), _.uniq( acks ).slice( -50 ).join( ',' ) );
		} catch ( e ) {
			// Without the acknowledgement the server simply sends it again.
		}
	};

	/**
	 * Draft key prefixes the forum forms on this page can use.
	 *
	 * A new-topic form drafts under draft_discussion_{forum}; a reply form
	 * under draft_reply_{topic} or draft_reply_{topic}_{reply}, depending on
	 * which reply is answered. The draft fetch asks only for these.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return {Array}
	 */
	var bbDraftKeyPrefixes = function () {
		var prefixes = [];

		$( 'form[name="new-post"]' ).each(
			function () {
				var forum_id = parseInt( $( this ).find( '#bbp_forum_id' ).val(), 10 ),
					topic_id = parseInt( $( this ).find( '#bbp_topic_id' ).val(), 10 );

				if ( $( this ).find( '#bbp_forum_id' ).length ) {
					prefixes.push( 0 < forum_id ? 'draft_discussion_' + forum_id : 'draft_topic' );
				} else if ( $( this ).find( '#bbp_topic_id' ).length ) {
					prefixes.push( 0 < topic_id ? 'draft_reply_' + topic_id : 'draft_reply' );
				}
			}
		);

		return _.uniq( prefixes );
	};

	/**
	 * Whether a stored draft, or a held hand-back, belongs to a form on this page.
	 *
	 * `has_draft` is site-wide, so on its own it sent the lazy fetch from every
	 * forum page once the member had a draft anywhere. A held hand-back is
	 * fetched from any page, because that fetch is also what delivers it. A page
	 * rendered without `draft_keys` (an older cached page) fetches as before.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return {boolean}
	 */
	var bbDraftPageHasDraft = function () {
		var keys = BP_Nouveau.forums.draft_keys,
			prefixes;

		if ( true === BP_Nouveau.forums.has_evicted_hold || ! _.isArray( keys ) ) {
			return true;
		}

		prefixes = bbDraftKeyPrefixes();

		return _.some(
			keys,
			function ( key ) {
				return _.some(
					prefixes,
					function ( prefix ) {
						return prefix === String( key ) || 0 === String( key ).indexOf( prefix + '_' );
					}
				);
			}
		);
	};

	/**
	 * Drop local draft copies older than the draft retention.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return {void}
	 */
	var bbDraftSweepExpired = function () {
		var days   = ( 'undefined' !== typeof BP_Nouveau.forums ) ? parseInt( BP_Nouveau.forums.draft_retention_days, 10 ) : 0,
			cutoff = Math.floor( Date.now() / 1000 ) - ( days * 86400 ),
			keys   = [],
			i;

		if ( ! days || 1 > days ) {
			return;
		}

		try {
			for ( i = 0; i < localStorage.length; i++ ) {
				if ( /^draft_/.test( localStorage.key( i ) ) ) {
					keys.push( localStorage.key( i ) );
				}
			}

			_.each(
				keys,
				function ( key ) {
					var copy = JSON.parse( localStorage.getItem( key ) || 'null' );

					if ( copy && copy._draft_local_at && parseInt( copy._draft_local_at, 10 ) < cutoff ) {
						localStorage.removeItem( key );
					}
				}
			);
		} catch ( e ) {
			// Storage unavailable or a corrupt copy: each composer repairs its own key.
		}
	};

	/**
	 * Snapshot one form's editable content without needing an instance.
	 *
	 * Shared with the instance method so the capture taken before the lazy fetch
	 * and the comparison made after it speak exactly the same string.
	 *
	 * @param {Object} $form jQuery-wrapped form.
	 * @return {string}
	 */
	var bbDraftFormSnapshot = function ( $form ) {
		var parts = [];

		if ( ! $form || ! $form.length ) {
			return '';
		}

		// `.bbp-the-content` and NOT `[contenteditable="true"]`. This snapshot is
		// taken twice - once at module scope before the draft fetch goes out,
		// and again after it resolves - and the two must count the same parts.
		// The editor is a plain <div class="bbp-the-content ..."> in server HTML
		// (bbp_get_the_content()'s `editor_class`); MediumEditor adds
		// contenteditable at jQuery(document).ready, which is AFTER this file's
		// module scope runs from the footer. So the old selector matched 0
		// elements at capture and 1 at comparison.
		//
		// On a reply form that was invisible, because both counts joined to ""
		// with nothing else in the list - which is why repeated A/B runs on the
		// reply form found nothing. On a TOPIC form the title input adds a
		// second part, so capture joined to "" while comparison joined to
		// "\u0000": hasMemberTypedContent() returned true unconditionally, the
		// topic draft was never restored, has-draft was never applied (leaving
		// the Discard button CSS-hidden) and a role="alert" told the member they
		// had "already started writing here" on a form they had not touched, on
		// every page load (H3).
		// `:not(.emojionearea)`: with forum emoji on, emojionearea wraps the
		// editor in a div that copies its classes, so a second
		// `.bbp-the-content` appears after this module captured the first
		// snapshot. Counting it made every form look typed-into, so no forum
		// draft was ever restored and the next autosave overwrote it (V01).
		$form.find( '.bbp-the-content:not(.emojionearea)' ).each(
			function () {
				parts.push( $.trim( $( this ).text() ) );
			}
		);

		$form.find( 'input[name="bbp_topic_title"]' ).each(
			function () {
				parts.push( $.trim( $( this ).val() || '' ) );
			}
		);

		return parts.join( '\u0000' );
	};

	/**
	 * Whether a draft's stored `data` holds anything worth restoring.
	 *
	 * Mirrors bb_draft_topic_reply_entry_has_payload() on the server, and draws
	 * the same line: member-authored text and attachments count, while the tags,
	 * subscription, sticky and topic/reply-ID fields that travel with every
	 * serialized form do not.
	 *
	 * A copy holding none of it has nothing to put in the composer, so it must
	 * never outrank one that does - an empty localStorage copy used to define
	 * the key unconditionally and block the fetched server draft, leaving the
	 * composer blank with the draft indicator still showing (Q12).
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {Object} data A draft entry's `data` object.
	 * @return {boolean} True when there is content to restore.
	 */
	var bbDraftDataHasPayload = function ( data ) {
		var i,
			key,
			lists = [ 'bbp_media', 'bbp_document', 'bbp_video', 'bbp_media_gif', 'link_preview_data', 'bb_link_url' ],
			texts = [ 'bbp_topic_title', 'bbp_topic_content', 'bbp_reply_content' ];

		if ( ! data || 'object' !== typeof data ) {
			return false;
		}

		for ( i = 0; i < lists.length; i++ ) {
			key = lists[ i ];

			if ( 'undefined' !== typeof data[ key ] && '' !== data[ key ] && '[]' !== data[ key ] ) {
				return true;
			}
		}

		for ( i = 0; i < texts.length; i++ ) {
			key = texts[ i ];

			if (
				'string' === typeof data[ key ] &&
				'' !== $( $.parseHTML( data[ key ] ) ).text().trim()
			) {
				return true;
			}
		}

		return false;
	};

	/**
	 * Draft key a form drafts under, the same rule setupTopicReplyDraftKeys() applies.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {jQuery} $form Topic or reply form.
	 * @return {string} The key, or '' for a form that drafts nothing.
	 */
	var bbDraftFormKey = function ( $form ) {
		var forum_id, topic_id, reply_to;

		if ( $form.find( '#bbp_forum_id' ).length ) {
			forum_id = parseInt( $form.find( '#bbp_forum_id' ).val(), 10 );

			return 0 < forum_id ? 'draft_discussion_' + forum_id : 'draft_topic';
		}

		if ( $form.find( '#bbp_topic_id' ).length ) {
			topic_id = parseInt( $form.find( '#bbp_topic_id' ).val(), 10 );
			reply_to = parseInt( $form.find( '#bbp_reply_to' ).val(), 10 );

			if ( 0 < topic_id && 0 < reply_to ) {
				return 'draft_reply_' + topic_id + '_' + reply_to;
			}

			return 0 < topic_id ? 'draft_reply_' + topic_id : 'draft_reply';
		}

		return '';
	};

	/**
	 * Whether a local copy is stamped with another member.
	 *
	 * Same rule as the restore (#7): an ownerless copy is the release client's
	 * and belongs to this member.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {Object} copy Parsed local copy.
	 * @return {boolean}
	 */
	var bbDraftCopyIsForeign = function ( copy ) {
		var stored_owner  = copy && copy.bb_draft_owner ? parseInt( copy.bb_draft_owner, 10 ) : 0,
			current_owner = parseInt( BP_Nouveau.forums.params.bb_current_user_id, 10 );

		return !! ( stored_owner && current_owner && stored_owner !== current_owner );
	};

	// Identifies the local copies written by bbDraftStashTypedDuringFetch() on
	// THIS page view, so the fetch can drop exactly those once it resolves.
	var bbDraftInterimToken = String( Math.floor( Date.now() / 1000 ) ) + '-' + Math.random().toString( 36 ).slice( 2, 10 );

	/**
	 * Keep in localStorage what the member types while the draft fetch is in flight.
	 *
	 * The forms start (and bind their local autosave and page-unload save) only
	 * once the fetch resolves, so text typed during a slow fetch was lost if
	 * the tab closed first (F-10). Until then this stores each form the member
	 * typed into, in the composer's own shape. The copy is based on nothing
	 * (`_draft_base` ['none']), the same as text typed while the stored draft
	 * was not loaded: after a reload it is restored into the form, and its next
	 * server save is refused as a conflict instead of replacing a stored draft
	 * it never saw. A form whose key has a local copy this page did not write
	 * is locked instead ({@see bbDraftLockFormsWithLocalCopy}).
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return {void}
	 */
	var bbDraftStashTypedDuringFetch = function () {
		var owner_id = parseInt( BP_Nouveau.forums.params.bb_current_user_id, 10 );

		if ( ! owner_id ) {
			return;
		}

		forms.each(
			function () {
				var $form = $( this ),
					key   = bbDraftFormKey( $form ),
					meta  = {},
					existing;

				if ( ! key || bbDraftFormSnapshot( $form ) === $form.data( 'bbDraftInitialSnapshot' ) ) {
					return;
				}

				try {
					existing = JSON.parse( localStorage.getItem( key ) || 'null' );
				} catch ( e ) {
					existing = null;
				}

				if ( existing && 'object' === typeof existing && bbDraftInterimToken !== existing.bb_draft_interim && ! bbDraftCopyIsForeign( existing ) ) {
					return;
				}

				_.each(
					$form.serializeArray(),
					function ( pair ) {
						var name = pair.name.replace( '[]', '' );

						if ( -1 !== _.indexOf( [ '_wpnonce', '_wp_http_referer', '_bbp_unfiltered_html_reply', 'redirect_to', '_bbp_unfiltered_html_topic' ], name ) ) {
							return;
						}

						if ( 'undefined' === typeof meta[ name ] ) {
							meta[ name ] = pair.value;
						} else {
							if ( ! _.isArray( meta[ name ] ) ) {
								meta[ name ] = [ meta[ name ] ];
							}

							meta[ name ].push( pair.value );
						}
					}
				);

				try {
					localStorage.setItem(
						key,
						JSON.stringify(
							{
								object: $form.find( '#bbp_forum_id' ).length ? 'topic' : 'reply',
								data_key: key,
								post_action: 'update',
								data: meta,
								bb_draft_owner: owner_id,
								_draft_base: [ 'none' ],
								_draft_local_at: Math.floor( Date.now() / 1000 ),
								// Real typing: a later hand-back for this key is
								// merged with it, not stored over it.
								_draft_edit_at: Math.floor( Date.now() / 1000 ),
								bb_draft_interim: bbDraftInterimToken
							}
						)
					);
				} catch ( e ) {
					// Over quota: nothing more this page can keep.
				}
			}
		);
	};

	/**
	 * Drop the copies bbDraftStashTypedDuringFetch() wrote on this page view.
	 *
	 * Once the fetch resolves the forms start with the typed text still on
	 * screen, and the existing restore logic decides what happens to it;
	 * a stash left in place would be read as the member's saved draft instead.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return {void}
	 */
	var bbDraftDropInterimStash = function () {
		forms.each(
			function () {
				var key = bbDraftFormKey( $( this ) ),
					existing;

				if ( ! key ) {
					return;
				}

				try {
					existing = JSON.parse( localStorage.getItem( key ) || 'null' );

					if ( existing && 'object' === typeof existing && bbDraftInterimToken === existing.bb_draft_interim ) {
						localStorage.removeItem( key );
					}
				} catch ( e ) {
					// Unreadable copy: leave it to the normal restore path.
				}
			}
		);
	};

	/**
	 * Keep forms that already have a local copy read-only while the fetch runs.
	 *
	 * bbDraftStashTypedDuringFetch() cannot store typing over a local copy it
	 * did not write: that copy may be the member's only one. Such a form is
	 * filled from its copy once the forms start, so text typed into it before
	 * then could not be kept anyway (F-10). Input is refused at the document,
	 * so it holds whenever the editor itself starts. The lock lasts until the
	 * fetch resolves, or until its retries fail and the forms start regardless.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {boolean} lock True to lock, false to release.
	 * @return {void}
	 */
	var bbDraftLockFormsWithLocalCopy = function ( lock ) {
		if ( ! lock ) {
			forms.removeData( 'bbDraftLocked' )
				.removeAttr( 'aria-busy' )
				.removeClass( 'bb-draft-loading' )
				.find( '.bb-draft-loading-note' ).remove();

			return;
		}

		forms.each(
			function () {
				var $form = $( this ),
					key   = bbDraftFormKey( $form ),
					existing;

				if ( ! key ) {
					return;
				}

				try {
					existing = JSON.parse( localStorage.getItem( key ) || 'null' );
				} catch ( e ) {
					existing = null;
				}

				// Another member's copy is dropped, not restored, when the form
				// starts (#7), so typing over it is kept: no lock (V-02).
				if ( existing && 'object' === typeof existing && bbDraftInterimToken !== existing.bb_draft_interim && ! bbDraftCopyIsForeign( existing ) ) {
					$form.data( 'bbDraftLocked', true )
						.attr( 'aria-busy', 'true' )
						.addClass( 'bb-draft-loading' );

					// Say why typing is refused. The constructor only binds
					// methods; start() is what wires the form up.
					if ( BP_Nouveau.forums.draft_loading_message && ! $form.find( '.bb-draft-loading-note' ).length ) {
						( new bp.Nouveau.TopicReplyDraft( $form ) ).insertDraftNotice(
							$form,
							$( '<div class="bb-draft-loading-note bp-messages bp-feedback info" role="status"></div>' )
								.append( $( '<span class="bp-icon" aria-hidden="true"></span>' ) )
								.append( $( '<p></p>' ).text( BP_Nouveau.forums.draft_loading_message ) )
						);
					}
				}
			}
		);

		if ( ! document.addEventListener || window.bbDraftLockBound ) {
			return;
		}

		window.bbDraftLockBound = true;

		// keydown too: a keyboard Cmd/Ctrl+V is moved into MediumEditor's paste
		// bin outside the form and inserted without a beforeinput event.
		_.each(
			[ 'beforeinput', 'keypress', 'keydown', 'paste', 'drop' ],
			function ( type ) {
				document.addEventListener(
					type,
					function ( event ) {
						if ( 'keydown' === type && ! ( ( event.metaKey || event.ctrlKey ) && 86 === event.keyCode ) ) {
							return;
						}

						if ( event.target && $( event.target ).closest( 'form' ).data( 'bbDraftLocked' ) ) {
							event.preventDefault();
							event.stopImmediatePropagation();
						}
					},
					true
				);
			}
		);
	};

	/**
	 * localStorage key of the hand-back keys this browser still has to
	 * acknowledge, per member: on a shared browser another member's pending
	 * acknowledgement must never be sent with this member's requests, or it
	 * ends this member's hold for the same key unseen.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return {string}
	 */
	function bbDraftAckStorageKey() {
		return 'bb_draft_evicted_ack_' + ( parseInt( BP_Nouveau.forums.params.bb_current_user_id, 10 ) || 0 );
	}

	/**
	 * Whether a clipboard holds an image and no text.
	 *
	 * A browser "Copy image" puts the image file next to an HTML copy that is
	 * only an <img>, so the presence of text/html alone does not mean text: the
	 * editor strips the <img> and inserted nothing, with no notice (F-06). The
	 * HTML is parsed into an inert document, never the page.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {DataTransfer|null} clipboard Paste event clipboard data.
	 * @return {boolean}
	 */
	var bbDraftClipboardIsImageOnly = function ( clipboard ) {
		var has_image = false,
			plain, html, i;

		if ( ! clipboard || ! clipboard.items ) {
			return false;
		}

		for ( i = 0; i < clipboard.items.length; i++ ) {
			if ( 'file' === clipboard.items[ i ].kind && 0 === clipboard.items[ i ].type.indexOf( 'image/' ) ) {
				has_image = true;
			}
		}

		if ( ! has_image ) {
			return false;
		}

		plain = clipboard.getData( 'text/plain' ) || '';

		if ( '' !== $.trim( plain ) ) {
			return false;
		}

		html = clipboard.getData( 'text/html' ) || '';

		if ( '' !== html && 'function' === typeof window.DOMParser ) {
			html = new window.DOMParser().parseFromString( html, 'text/html' ).body.textContent || '';
		}

		return '' === $.trim( html );
	};

	// The draft composer a keyboard paste belongs to; see setupPasteImageGuard().
	var bbDraftPasteComposer = null,
		bbDraftPasteBinGuardBound = false;

	/**
	 * Refuse an image-only keyboard paste in MediumEditor's paste bin.
	 *
	 * Cmd/Ctrl+V lands in a body-level paste bin, where the form's own paste
	 * listener never sees it. MediumEditor reports an image-only clipboard from
	 * there only when it has no HTML, so a "Copy image" paste inserted nothing
	 * and showed no notice (F-06). Listening in the capture phase lets the
	 * paste bin's handler see the paste as refused and clean up. Bound once.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return {void}
	 */
	var bbDraftBindPasteBinGuard = function () {
		if ( bbDraftPasteBinGuardBound || ! document.addEventListener ) {
			return;
		}

		bbDraftPasteBinGuardBound = true;

		// Focus in any other editor (another composer's, or one outside a
		// draft form) ends this composer's claim on the next keyboard paste.
		document.addEventListener(
			'focusin',
			function ( event ) {
				var target = event.target;

				if ( target && target.id && 0 === String( target.id ).indexOf( 'medium-editor-pastebin-' ) ) {
					return;
				}

				if ( ! $( target ).closest( 'form[name="new-post"]' ).length ) {
					bbDraftPasteComposer = null;
				}
			},
			true
		);

		document.addEventListener(
			'paste',
			function ( event ) {
				var target = event.target;

				if ( ! bbDraftPasteComposer || ! target || ! target.id || 0 !== String( target.id ).indexOf( 'medium-editor-pastebin-' ) ) {
					return;
				}

				if ( bbDraftClipboardIsImageOnly( event.clipboardData ) ) {
					event.preventDefault();
					bbDraftPasteComposer.showDraftFeedback( BP_Nouveau.forums.paste_image_blocked_message );
				}
			},
			true
		);
	};

	var bbInitTopicReplyDrafts = function () {
		forms.each( function () {
			var topicReplyDraft = new bp.Nouveau.TopicReplyDraft( $( this ) );
			topicReplyDraft.start();
		} );
	};


	// Called here, not where `forms` is declared: it is a function expression,
	// so it only exists once its `var` line has run (NEW-1).
	bbDraftSweepExpired();

	if (
		0 < forms.length &&
		'undefined' !== typeof BP_Nouveau.forums &&
		true === BP_Nouveau.forums.has_draft &&
		bbDraftPageHasDraft() &&
		$.isEmptyObject( BP_Nouveau.forums.draft )
	) {
		// Capture what each form arrived with BEFORE the request goes out.
		// start() runs inside .always(), so a snapshot taken there would already
		// include whatever the member typed while the fetch was in flight
		// (see snapshotInitialContent(),).
		forms.each(
			function () {
				$( this ).data( 'bbDraftInitialSnapshot', bbDraftFormSnapshot( $( this ) ) );
			}
		);

		// One bounded retry before giving up. A failed fetch used to fall straight
		// through .always() and initialise the forms with an empty draft map, so a
		// member with a stored draft saw an empty composer, no error and no retry -
		// and their next autosave overwrote the draft the fetch had failed to read
		//.
		var bbDraftFetchAttempts = 0;

		// Local save while the fetch is in flight (F-10). Stopped, and its
		// copies dropped, just before the forms start; start() binds the
		// composer's own local autosave and page-unload save.
		// A listener, not window.onbeforeunload, so another script's handler
		// is neither replaced nor cleared.
		var bbDraftInterimInterval = window.setInterval( bbDraftStashTypedDuringFetch, 3000 );

		window.addEventListener( 'beforeunload', bbDraftStashTypedDuringFetch );
		bbDraftLockFormsWithLocalCopy( true );

		var bbDraftStartForms = function () {
			window.clearInterval( bbDraftInterimInterval );
			window.removeEventListener( 'beforeunload', bbDraftStashTypedDuringFetch );
			bbDraftLockFormsWithLocalCopy( false );
			bbDraftDropInterimStash();
			bbInitTopicReplyDrafts();
		};

		// Shared by both ways the read can fail. A transport error lands in
		// .fail(), but a REJECTED read lands in .done(): bb_get_topic_reply_drafts()
		// answers an expired nonce or a dropped session with wp_send_json_error(),
		// which is HTTP 200 carrying success:false. Clearing the flag there treated
		// that as a successful empty read, so no retry ran, the composer opened
		// empty over a live server draft and the next autosave overwrote it - the
		// exact loss this retry exists to prevent, reached without any transport
		// error.
		var bbDraftFetchFailed = function () {
			if ( bbDraftFetchAttempts < 2 ) {
				window.setTimeout( bbRunDraftFetch, 2000 );

				return;
			}

			// Out of attempts. Initialise the forms so the composer still
			// works, but flag the failure so postTopicReplyDraft() refuses to
			// overwrite the draft we could not read, and tells the member why.
			BP_Nouveau.forums.draft_fetch_failed = true;
			bbDraftStartForms();
		};

		var bbRunDraftFetch = function () {
			bbDraftFetchAttempts++;

			// Hand-backs this browser already stored. Without them the server
			// keeps holding those drafts, and every forum page with a form
			// fetches again until the member next saves.
			var fetch_acks = bbDraftGetAcks();

			// Bounded, so a hung request reaches the retry and the forms start
			// instead of waiting on it for the whole page view (F-10).
			$.ajax(
				{
					type: 'POST',
					url: BP_Nouveau.ajaxurl,
					timeout: 15000,
					data: {
						action: 'bb_get_topic_reply_drafts',
						_wpnonce_post_topic_reply_draft: BP_Nouveau.forums.nonces.post_topic_reply_draft,
						prefixes: bbDraftKeyPrefixes().join( ',' ),
						bb_draft_evicted_ack: fetch_acks.join( ',' )
					}
				}
			).done(
				function ( response ) {
					// Only `success` decides read-vs-rejected; the endpoint always
					// sends `drafts` on success, so a missing/empty map is a member
					// with no drafts, not a failure, and must not arm the guard.
					if ( ! response || ! response.success ) {
						bbDraftFetchFailed();

						return;
					}

					// The server applied these before answering.
					bbDraftClearAcks( fetch_acks );

					// Evictions whose hand-back never reached this browser, except
					// for forms the member already typed into.
					if ( response.data && response.data.evicted_drafts ) {
						bbDraftSkipHandbackForTypedForms( forms, response.data.evicted_drafts );
						bbDraftStoreHandback( response.data.evicted_drafts );
					}

					if ( response.data && response.data.drafts ) {
						var drafts    = response.data.drafts,
							discarded = bbDraftDiscardedKeys();

						// Never re-offer a draft this tab has already discarded - the
						// delete may simply not have been processed yet.
						_.each(
							discarded,
							function ( key ) {
								delete drafts[ key ];
							}
						);

						BP_Nouveau.forums.draft = drafts;
					}

					BP_Nouveau.forums.draft_fetch_failed = false;
					bbDraftStartForms();
				}
			).fail( bbDraftFetchFailed );
		};

		bbRunDraftFetch();
	} else {
		bbInitTopicReplyDrafts();
	}

})( bp, jQuery );
