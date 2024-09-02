/* jshint browser: true */
/* global bp, BP_Nouveau, Dropzone, videojs, bp_media_dropzone */
/* @version 3.1.0 */
window.bp = window.bp || {};

( function( exports, $ ) {

	// Bail if not set.
	if ( typeof BP_Nouveau === 'undefined' ) {
		return;
	}

	bp.Nouveau = bp.Nouveau || {};
	bp.Models  = bp.Models || {};

	bp.Models.ACReply = Backbone.Model.extend(
		{
			defaults: {
				gif_data: {}
			}
		}
	);

	/**
	 * [Activity description]
	 *
	 * @type {Object}
	 */
	bp.Nouveau.Activity = {

		/**
		 * [start description]
		 *
		 * @return {[type]} [description]
		 */
		start: function() {
			this.setupGlobals();

			// Listen to events ("Add hooks!").
			this.addListeners();
		},

		/**
		 * [setupGlobals description]
		 *
		 * @return {[type]} [description]
		 */
		setupGlobals: function() {
			// Init just posted activities.
			this.just_posted = [];

			// Init current page.
			this.current_page = 1;

			// Init mentions count.
			this.mentions_count = Number( $( bp.Nouveau.objectNavParent + ' [data-bp-scope="mentions"]' ).find( 'a span' ).html() ) || 0;

			// HeartBeat Globals.
			this.heartbeat_data = {
				newest         : '',
				highlights     : {},
				last_recorded  : 0,
				first_recorded : 0,
				document_title : $( document ).prop( 'title' )
			};

			if ( typeof window.Dropzone !== 'undefined' && typeof BP_Nouveau.media !== 'undefined' ) {

				// set up dropzones auto discover to false so it does not automatically set dropzones.
				window.Dropzone.autoDiscover = false;

				this.dropzone_options = {
					url                 		: BP_Nouveau.ajaxurl,
					timeout             		: 3 * 60 * 60 * 1000,
					dictFileTooBig      		: BP_Nouveau.media.dictFileTooBig,
					dictDefaultMessage  		: BP_Nouveau.media.dropzone_media_message,
					acceptedFiles       		: 'image/*',
					autoProcessQueue    		: true,
					addRemoveLinks      		: true,
					uploadMultiple      		: false,
					maxFiles            		: typeof BP_Nouveau.media.maxFiles !== 'undefined' ? BP_Nouveau.media.maxFiles : 10,
					maxFilesize         		: typeof BP_Nouveau.media.max_upload_size !== 'undefined' ? BP_Nouveau.media.max_upload_size : 2,
					dictMaxFilesExceeded		: BP_Nouveau.media.media_dict_file_exceeded,
					dictCancelUploadConfirmation: BP_Nouveau.media.dictCancelUploadConfirmation,
					maxThumbnailFilesize    : typeof BP_Nouveau.media.max_upload_size !== 'undefined' ? BP_Nouveau.media.max_upload_size : 2,
				};

				// if defined, add custom dropzone options.
				if ( typeof BP_Nouveau.media.dropzone_options !== 'undefined' ) {
					Object.assign( this.dropzone_options, BP_Nouveau.media.dropzone_options );
				}
			}

			this.dropzone_obj   = null;
			this.dropzone_media = [];

			this.dropzone_document_obj = null;
			this.dropzone_document     = [];

			this.dropzone_video_obj = null;
			this.dropzone_video     = [];

			this.models = [];

			this.InitiatedCommentForms = [];

			// Flag to track any activity updates
			this.activityHasUpdates = false;

			// Store the ID of the updated activity
			this.currentActivityId = null;

			// Flag to track activity pin updates
			this.activityPinHasUpdates = false;
		},

		/**
		 * [addListeners description]
		 */
		addListeners: function() {
			// HeartBeat listeners.
			if ( ! $( 'body' ).hasClass( 'activity-singular' ) ) {
				$( '#buddypress' ).on( 'bp_heartbeat_send', this.heartbeatSend.bind( this ) );
			}
			$( '#buddypress' ).on( 'bp_heartbeat_tick', this.heartbeatTick.bind( this ) );

			// Inject Activities.
			$( '#buddypress [data-bp-list="activity"]:not( #bb-schedule-posts_modal [data-bp-list="activity"] )' ).on( 'click', 'li.load-newest, li.load-more', this.injectActivities.bind( this ) );

			// Highlight new activities & clean up the stream.
			$( '#buddypress' ).on( 'bp_ajax_request', '[data-bp-list="activity"]', this.scopeLoaded.bind( this ) );

			// Activity comments effect.
			$( '#activity-stream' ).on( 'click', '.acomments-view-more', this.showActivity );
			$( 'body' ).on( 'click', '.bb-close-action-popup', this.closeActivity );

			$( document ).on( 'activityModalOpened', function( event, data ) {
				var activityId = data.activityId;
		
				$( document ).on( 'click', function( event ) {
					if (
						$( '#activity-modal:visible' ).length > 0 &&
						0 === $( '#bp-nouveau-activity-form-placeholder:visible' ).length &&
						! $( event.target ).closest( '#activity-modal' ).length &&
						! $( event.target ).closest( '.gif-media-search-dropdown-standalone' ).length &&
						! $( event.target ).closest( '.emojionearea-theatre' ).length
					) {
						this.closeActivity( event );
						this.activitySyncOnModalClose( event, activityId );
					}
				}.bind( this ) );
			}.bind( this ) );

			// Activity actions.
			$( '#buddypress [data-bp-list="activity"], #activity-modal' ).on( 'click', '.activity-item', bp.Nouveau, this.activityActions.bind( this ) );
			$( '#buddypress [data-bp-list="activity"], #activity-modal' ).on( 'click', '.activity-privacy>li.bb-edit-privacy a', bp.Nouveau, this.activityPrivacyRedirect.bind( this ) );
			$( '#buddypress [data-bp-list="activity"], #activity-modal' ).on( 'click', '.activity-privacy>li:not(.bb-edit-privacy)', bp.Nouveau, this.activityPrivacyChange.bind( this ) );
			$( '#buddypress [data-bp-list="activity"], #bb-media-model-container .activity-list, #activity-modal' ).on( 'click', 'span.privacy', bp.Nouveau, this.togglePrivacyDropdown.bind( this ) );
			$( '#bb-media-model-container .activity-list' ).on( 'click', '.activity-item', bp.Nouveau, this.activityActions.bind( this ) );
			$( '.bb-activity-model-wrapper' ).on( 'click', '.ac-form-placeholder', bp.Nouveau, this.activityRootComment.bind( this ) );
			$( document ).keydown( this.commentFormAction );
			$( document ).click( this.togglePopupDropdown );

			// forums.
			$( '#buddypress [data-bp-list="activity"], #bb-media-model-container .activity-list, #activity-modal .activity-list, .bb-modal-activity-footer' ).on( 'click', '.ac-reply-media-button', this.openCommentsMediaUploader.bind( this ) );
			$( '#buddypress [data-bp-list="activity"], #bb-media-model-container .activity-list, #activity-modal .activity-list, .bb-modal-activity-footer' ).on( 'click', '.ac-reply-document-button', this.openCommentsDocumentUploader.bind( this ) );
			$( '#buddypress [data-bp-list="activity"], #bb-media-model-container .activity-list, #activity-modal .activity-list, .bb-modal-activity-footer' ).on( 'click', '.ac-reply-video-button', this.openCommentsVideoUploader.bind( this ) );
			$( '#buddypress [data-bp-list="activity"], #bb-media-model-container .activity-list, #activity-modal .activity-list, .bb-modal-activity-footer' ).on( 'click', '.ac-reply-gif-button', this.openGifPicker.bind( this ) );

			// Reaction actions.
			$( document ).on( 'click', '.activity-state-popup_overlay', bp.Nouveau, this.closeActivityState.bind( this ) );
			$( document ).on( 'click', '.activity-state-popup .activity-state-popup_tab_panel a', this.ReactionStatePopupTab );

			// Activity autoload.
			if ( ! _.isUndefined( BP_Nouveau.activity.params.autoload ) ) {
				$( window ).scroll( this.loadMoreActivities );
			}

			$( '.bb-activity-model-wrapper, .bb-media-model-wrapper' ).on( 'click', '.acomments-view-more', this.viewMoreComments.bind( this ) );
			$( document ).on( 'click', '#activity-stream .activity-comments .view-more-comments, #activity-stream .activity-state-comments > .comments-count', function ( e ) {
				e.preventDefault();
				$( this ).parents( 'li.activity-item' ).find( '.activity-comments > ul > li.acomments-view-more, .activity-comments > .activity-actions > ul > li.acomments-view-more' ).trigger( 'click' );
			} );

			$( '#activity-modal > .bb-modal-activity-body' ).on( 'scroll', this.autoloadMoreComments.bind( this ) );
			$( '#activity-modal > .bb-modal-activity-body' ).on( 'scroll', this.discardGifEmojiPicker.bind( this ) );

			$( '.bb-activity-model-wrapper .bb-model-close-button' ).on( 'click', this.activitySyncOnModalClose.bind( this ) );

			// Validate media access for comment forms.
			if( BP_Nouveau.is_send_ajax_request !== undefined && BP_Nouveau.is_send_ajax_request === '1' ) {
				$( '#buddypress' ).on( 'bp_ajax_request', '[data-bp-list="activity"]', function() {
					setTimeout( function() {
						$( '.ac-form.not-initialized' ).each( function() {
							var form = $( this );
							var target = form.find( '.ac-textarea' );
							bp.Nouveau.Activity.toggleMultiMediaOptions( form, target );
						});
					}, 1000 );
				} );
			} else {
				setTimeout( function() {
					$( '.ac-form.not-initialized' ).each( function() {
						var form = $( this );
						var target = form.find( '.ac-textarea' );
						bp.Nouveau.Activity.toggleMultiMediaOptions( form, target );
					});
				}, 1000 );
			}
		},

		/**
		 * [heartbeatSend description]
		 *
		 * @param  {[type]} event [description]
		 * @param  {[type]} data  [description]
		 * @return {[type]}       [description]
		 */
		heartbeatSend: function( event, data ) {
			this.heartbeat_data.first_recorded = $( '#buddypress [data-bp-list] [data-bp-activity-id]:not(.bb-pinned)' ).first().data( 'bp-timestamp' ) || 0;

			// Handle the first item is already latest and pinned.
			var first_activity_timestamp = $( '#buddypress [data-bp-list] [data-bp-activity-id]' ).first().data( 'bp-timestamp' ) || 0;
			if ( first_activity_timestamp > this.heartbeat_data.first_recorded ) {
				this.heartbeat_data.first_recorded = first_activity_timestamp;
			}

			if ( 0 === this.heartbeat_data.last_recorded || this.heartbeat_data.first_recorded > this.heartbeat_data.last_recorded ) {
				this.heartbeat_data.last_recorded = this.heartbeat_data.first_recorded;
			}

			data.bp_activity_last_recorded = this.heartbeat_data.last_recorded;

			if ( $( '#buddypress .dir-search input[type=search]' ).length ) {
				data.bp_activity_last_recorded_search_terms = $( '#buddypress .dir-search input[type=search]' ).val();
			}

			$.extend( data, { bp_heartbeat: bp.Nouveau.getStorage( 'bp-activity' ) } );
		},

		/**
		 * [heartbeatTick description]
		 *
		 * @param  {[type]} event          [description]
		 * @param  {[type]} data           [description]
		 * @return {[type]}                [description]
		 */
		heartbeatTick: function( event, data ) {
			var newest_activities_count, newest_activities, objects = bp.Nouveau.objects,
				scope = bp.Nouveau.getStorage( 'bp-activity', 'scope' ), self = this;

			// Only proceed if we have newest activities.
			if ( undefined === data || ! data.bp_activity_newest_activities ) {
				return;
			}

			this.heartbeat_data.newest        = $.trim( data.bp_activity_newest_activities.activities ) + this.heartbeat_data.newest;
			this.heartbeat_data.last_recorded = Number( data.bp_activity_newest_activities.last_recorded );

			// Parse activities.
			newest_activities = $( this.heartbeat_data.newest ).filter( '.activity-item' );

			// Count them.
			newest_activities_count = Number( newest_activities.length );

			/**
			 * It's not a regular object but we need it!
			 * so let's add it temporarly..
			 */
			objects.push( 'mentions' );

			/**
			 * On the All Members tab, we need to know what these activities are about
			 * in order to update all the other tabs dynamic span
			 */
			if ( 'all' === scope ) {

				$.each(
					newest_activities,
					function( a, activity ) {
						activity = $( activity );

						$.each(
							objects,
							function( o, object ) {
								if ( -1 !== $.inArray( 'bp-my-' + object, activity.get( 0 ).classList ) ) {
									if ( undefined === self.heartbeat_data.highlights[ object ] ) {
										self.heartbeat_data.highlights[ object ] = [ activity.data( 'bp-activity-id' ) ];
									} else if ( -1 === $.inArray( activity.data( 'bp-activity-id' ), self.heartbeat_data.highlights[ object ] ) ) {
										self.heartbeat_data.highlights[ object ].push( activity.data( 'bp-activity-id' ) );
									}
								}
							}
						);
					}
				);

				// Remove the specific classes to count highligthts.
				var regexp                 = new RegExp( 'bp-my-(' + objects.join( '|' ) + ')', 'g' );
				this.heartbeat_data.newest = this.heartbeat_data.newest.replace( regexp, '' );

				/**
				 * Deal with the 'All Members' dynamic span from here as HeartBeat is working even when
				 * the user is not logged in
				 */
				$( bp.Nouveau.objectNavParent + ' [data-bp-scope="all"]' ).find( 'a span' ).html( newest_activities_count );

				// Set all activities to be highlighted for the current scope.
			} else {
				// Init the array of highlighted activities.
				this.heartbeat_data.highlights[ scope ] = [];

				$.each(
					newest_activities,
					function( a, activity ) {
						self.heartbeat_data.highlights[ scope ].push( $( activity ).data( 'bp-activity-id' ) );
					}
				);
			}

			$.each(
				objects,
				function( o, object ) {
					if ( undefined !== self.heartbeat_data.highlights[ object ] && self.heartbeat_data.highlights[ object ].length ) {
						var count = 0;

						if ( 'mentions' === object ) {
							count = self.mentions_count;
						}

						$( bp.Nouveau.objectNavParent + ' [data-bp-scope="' + object + '"]' ).find( 'a span' ).html( Number( self.heartbeat_data.highlights[ object ].length ) + count );
					}
				}
			);

			/**
			 * Let's remove the mentions from objects!
			 */
			objects.pop();

			// Add an information about the number of newest activities inside the document's title.
			$( document ).prop( 'title', '(' + newest_activities_count + ') ' + this.heartbeat_data.document_title );

			// Update the Load Newest li if it already exists.
			if ( $( '#buddypress [data-bp-list="activity"]' ).first().hasClass( 'load-newest' ) ) {
				var newest_link = $( '#buddypress [data-bp-list="activity"] .load-newest a' ).html();
				$( '#buddypress [data-bp-list="activity"] .load-newest a' ).html( newest_link.replace( /([0-9]+)/, newest_activities_count ) );

				// Otherwise add it.
			} else {
				$( '#buddypress [data-bp-list="activity"] ul.activity-list' ).prepend( '<li class="load-newest"><a href="#newest">' + BP_Nouveau.newest + ' (' + newest_activities_count + ')</a></li>' );
			}

			$( '#buddypress [data-bp-list="activity"] li.load-newest' ).trigger( 'click' );

			/**
			 * Finally trigger a pending event containing the activity heartbeat data
			 */
			$( '#buddypress [data-bp-list="activity"]' ).trigger( 'bp_heartbeat_pending', this.heartbeat_data );

			if ( typeof bp.Nouveau !== 'undefined' ) {
				bp.Nouveau.reportPopUp();
			}
		},

		/**
		 * [injectQuery description]
		 *
		 * @param  {[type]} event [description]
		 * @return {[type]}       [description]
		 */
		injectActivities: function( event ) {
			var store = bp.Nouveau.getStorage( 'bp-activity' ),
				scope = store.scope || null, filter = store.filter || null;

			// Load newest activities.
			if ( $( event.currentTarget ).hasClass( 'load-newest' ) ) {
				// Stop event propagation.
				event.preventDefault();

				$( event.currentTarget ).remove();

				/**
				 * If a plugin is updating the recorded_date of an activity
				 * it will be loaded as a new one. We need to look in the
				 * stream and eventually remove similar ids to avoid "double".
				 */
				var activities = $.parseHTML( this.heartbeat_data.newest );

				$.each(
					activities,
					function( a, activity ){
						if ( 'LI' === activity.nodeName && $( activity ).hasClass( 'just-posted' ) ) {
							if ( $( '#' + $( activity ).prop( 'id' ) ).length ) {
								$( '#' + $( activity ).prop( 'id' ) ).remove();
							}
						}

					}
				);

				var first_activity = $( event.delegateTarget ).find( '.activity-list .activity-item' ).first();
				if ( first_activity.length > 0 && first_activity.hasClass( 'bb-pinned' ) ) {

					// Add after pinned post.
					$( first_activity ).after( this.heartbeat_data.newest ).find( 'li.activity-item' ).each( bp.Nouveau.hideSingleUrl ).trigger( 'bp_heartbeat_prepend', this.heartbeat_data );

				} else {

					// Now the stream is cleaned, prepend newest.
					$( event.delegateTarget ).find( '.activity-list' ).prepend( this.heartbeat_data.newest ).find( 'li.activity-item' ).each( bp.Nouveau.hideSingleUrl ).trigger( 'bp_heartbeat_prepend', this.heartbeat_data );
				}

				// Reset the newest activities now they're displayed.
				this.heartbeat_data.newest = '';

				// Reset the All members tab dynamic span id it's the current one.
				if ( 'all' === scope ) {
					$( bp.Nouveau.objectNavParent + ' [data-bp-scope="all"]' ).find( 'a span' ).html( '' );
				}

				// Specific to mentions.
				if ( 'mentions' === scope ) {
					// Now mentions are displayed, remove the user_metas.
					bp.Nouveau.ajax( { action: 'activity_clear_new_mentions' }, 'activity' );
					this.mentions_count = 0;
				}

				// Activities are now displayed, clear the newest count for the scope.
				$( bp.Nouveau.objectNavParent + ' [data-bp-scope="' + scope + '"]' ).find( 'a span' ).html( '' );

				// Activities are now displayed, clear the highlighted activities for the scope.
				if ( undefined !== this.heartbeat_data.highlights[ scope ] ) {
					this.heartbeat_data.highlights[ scope ] = [];
				}

				// Remove highlighted for the current scope.
				setTimeout(
					function () {
						$( event.delegateTarget ).find( '[data-bp-activity-id]' ).removeClass( 'newest_' + scope + '_activity' );
					},
					3000
				);

				// Reset the document title.
				$( document ).prop( 'title', this.heartbeat_data.document_title );

				// replace dummy image with original image by faking scroll event to call bp.Nouveau.lazyLoad.
				jQuery( window ).scroll();

				// Load more activities.
			} else if ( $( event.currentTarget ).hasClass( 'load-more' ) ) {
				var next_page = ( Number( this.current_page ) * 1 ) + 1, self = this, search_terms = '';

				// Stop event propagation.
				event.preventDefault();

				var targetEl = $( event.currentTarget );
				targetEl.find( 'a' ).first().addClass( 'loading' );

				// reset the just posted.
				this.just_posted = [];

				// Now set it.
				$( event.delegateTarget ).children( '.just-posted' ).each(
					function() {
						self.just_posted.push( $( this ).data( 'bp-activity-id' ) );
					}
				);

				if ( $( '#buddypress .dir-search input[type=search]' ).length ) {
					search_terms = $( '#buddypress .dir-search input[type=search]' ).val();
				}

				bp.Nouveau.objectRequest(
					{
						object              : 'activity',
						scope               : scope,
						filter              : filter,
						search_terms        : search_terms,
						page                : next_page,
						method              : 'append',
						exclude_just_posted : this.just_posted.join( ',' ),
						target              : '#buddypress [data-bp-list]:not( #bb-schedule-posts_modal [data-bp-list="activity"] ) ul.bp-list'
					}
				).done(
					function( response ) {
						if ( true === response.success ) {
							targetEl.remove();

							// Update the current page.
							self.current_page = next_page;

							// replace dummy image with original image by faking scroll event to call bp.Nouveau.lazyLoad.
							jQuery( window ).scroll();
						}
					}
				);
			}

			$( '.activity-item.bb-closed-comments' ).find( '.edit-activity, .acomment-edit' ).parents( '.generic-button' ).hide();
		},

		/**
		 * [truncateComments description]
		 *
		 * @param  {[type]} event [description]
		 * @return {[type]}       [description]
		 */
		hideComments: function( event ) {
			var comments = $( event.target ).find( '.activity-comments' ),
				activity_item, comment_items, comment_count, comment_parents;

			if ( ! comments.length ) {
				return;
			}

			comments.each(
				function( c, comment ) {
					comment_parents = $( comment ).children( 'ul' ).not( '.conflict-activity-ul-li-comment' );
					comment_items   = $( comment_parents ).find( 'li' ).not( $( '.document-action-class, .media-action-class, .video-action-class' ) );

					if ( ! comment_items.length ) {
						return;
					}

					// Check if URL has specific comment to show.
					if ( $( 'body' ).hasClass( 'activity-singular' ) && window.location.hash !== '' && $( window.location.hash ).length && $( window.location.hash ).closest( '.activity-comments' ).length !== 0 ) {
						return;
					}

					// Get the activity id.
					activity_item = $( comment ).closest( '.activity-item' );

					// Get the comment count.
					comment_count = $( '#acomment-comment-' + activity_item.data( 'bp-activity-id' ) + ' span.comment-count' ).html() || ' ';

					// Keep latest 5 comments.
					comment_items.each(
						function( i, item ) {
							if ( i < comment_items.length - 4 ) {

								// Prepend a link to display all.
								if ( ! i ) {
									$( item ).parent( 'ul' ).before( '<div class="show-all"><button class="text-button" type="button" data-bp-show-comments-id="#' + activity_item.prop( 'id' ) + '/show-all/">' + BP_Nouveau.show_x_comments + '</button></div>' );
								}

								// stop hiding elements if the id from hash url for specific comment matches.
								if ( window.location.hash && '#' + $( item ).attr( 'id' ) === window.location.hash ) {

									// in case it's a reply from comment, show hidden parent elements for it to show.
									$( item ).parents( 'li.comment-item' ).show();

									return false;
								}

								$( item ).addClass( 'bp-hidden' ).hide();
							}
						}
					);

					// If all parents are hidden, reveal at least one. It seems very risky to manipulate the DOM to keep exactly 5 comments!
					if ( $( comment_parents ).children( '.bp-hidden' ).length === $( comment_parents ).children( 'li' ).length - 1 && $( comment_parents ).find( 'li.show-all' ).length ) {
						$( comment_parents ).children( 'li:not(.show-all)' ).removeClass( 'bp-hidden' ).toggle();
					}
				}
			);
		},

		/**
		 * [showActivity description]
		 *
		 * @param  {[type]} event [description]
		 * @return {[type]}       [description]
		 */
		showActivity: function( event ) {
			event.preventDefault();
			var currentTargetList = $( event.currentTarget ).parent(),
				parentId = currentTargetList.data( 'parent_comment_id' ),
				activityId = $( currentTargetList ).data( 'activity_id' );

			$( document ).trigger( 'activityModalOpened', { activityId: activityId } );

			$( event.currentTarget ).parents( '.activity-comments' ).find( '.ac-form' ).each( function () {
				var form = $( this );
				var commentsList = $( this ).closest( '.activity-comments' );
				var commentItem = $( this ).closest( '.comment-item' );
				// Reset emojionearea
				form.find( '.post-elements-buttons-item.post-emoji' ).removeClass( 'active' ).empty( '' );

				bp.Nouveau.Activity.resetActivityCommentForm( form, 'hardReset' );
				commentsList.append( form );
				commentItem.find( '.acomment-display' ).removeClass( 'display-focus' );
				commentItem.removeClass( 'comment-item-focus' );
			} );

			bp.Nouveau.Activity.launchActivityPopup( activityId, parentId );
		},

		closeActivity: function ( event ) {
			event.preventDefault();
			var target = $( event.target ), modal = target.closest( '.bb-activity-model-wrapper' ), footer = modal.find( '.bb-modal-activity-footer' );
			var activityId = modal.find( '.activity-item' ).data( 'bp-activity-id' );
			var form = modal.find( '#ac-form-' + activityId );

			bp.Nouveau.Activity.reinitializeActivityCommentForm( form );

			if ( !_.isUndefined( BP_Nouveau.media ) && !_.isUndefined( BP_Nouveau.media.emoji ) ) {
				bp.Nouveau.Activity.initializeEmojioneArea( false, '', activityId );
			}

			modal.find( '#activity-modal' ).removeClass( 'bb-closed-comments' );

			modal.closest( 'body' ).removeClass( 'acomments-modal-open' );
			modal.hide();
			modal.find( 'ul.activity-list' ).empty();
			footer.removeClass( 'active' );
			footer.find( 'form.ac-form' ).remove();
		},

		/**
		 * [scopeLoaded description]
		 *
		 * @param  {[type]} event [description]
		 * @param  {[type]} data  [description]
		 * @return {[type]}       [description]
		 */
		scopeLoaded: function ( event, data ) {
			// Reset the pagination for the scope.
			this.current_page = 1;

			// Mentions are specific.
			if ( 'mentions' === data.scope && undefined !== data.response.new_mentions ) {
				$.each(
					data.response.new_mentions,
					function( i, id ) {
						$( '#buddypress #activity-stream' ).find( '[data-bp-activity-id="' + id + '"]' ).addClass( 'newest_mentions_activity' );
					}
				);

				// Reset mentions count.
				this.mentions_count = 0;
			} else if ( undefined !== this.heartbeat_data.highlights[data.scope] && this.heartbeat_data.highlights[data.scope].length ) {
				$.each(
					this.heartbeat_data.highlights[data.scope],
					function( i, id ) {
						if ( $( '#buddypress #activity-stream' ).find( '[data-bp-activity-id="' + id + '"]' ).length ) {
							$( '#buddypress #activity-stream' ).find( '[data-bp-activity-id="' + id + '"]' ).addClass( 'newest_' + data.scope + '_activity' );
						}
					}
				);
			}

			// Reset the newest activities now they're displayed.
			this.heartbeat_data.newest = '';
			$.each(
				$( bp.Nouveau.objectNavParent + ' [data-bp-scope]' ).find( 'a span' ),
				function( s, count ) {
					if ( 0 === parseInt( $( count ).html(), 10 ) ) {
						$( count ).html( '' );
					}
				}
			);

			// Activities are now loaded, clear the highlighted activities for the scope.
			if ( undefined !== this.heartbeat_data.highlights[ data.scope ] ) {
				this.heartbeat_data.highlights[ data.scope ] = [];
			}

			// Reset the document title.
			$( document ).prop( 'title', this.heartbeat_data.document_title );

			setTimeout(
				function () {
					$( '#buddypress #activity-stream .activity-item' ).removeClass( 'newest_' + data.scope + '_activity' );
				},
				3000
			);

			if (typeof window.instgrm !== 'undefined') {
				window.instgrm.Embeds.process();
			}
			if (typeof window.FB !== 'undefined' && typeof window.FB.XFBML !== 'undefined') {
				window.FB.XFBML.parse();
			}

			// Fix comments atwho query elements.
			this.fixAtWhoActivity();

			// Edit Activity Loader.
			this.openEditActivityPopup();

			// Navigate to specific comment when there's e.g. #acomment123 in url.
			this.navigateToSpecificComment();

			// replace dummy image with original image by faking scroll event to call bp.Nouveau.lazyLoad.
			setTimeout(
				function() {
					jQuery( window ).scroll();
				},
				200
			);
		},

		openEditActivityPopup: function() {
			if ( ! _.isUndefined( BP_Nouveau.activity.params.is_activity_edit ) && 0 < BP_Nouveau.activity.params.is_activity_edit ) {
				var activity_item = $( '#activity-' + BP_Nouveau.activity.params.is_activity_edit );
				if ( activity_item.length ) {
					var activity_data        = activity_item.data( 'bp-activity' );
					var activity_URL_preview = ( activity_item.data( 'link-url' ) ) !== '' ? activity_item.data( 'link-url' ) : null;

					if ( ! _.isUndefined( activity_data ) ) {
						bp.Nouveau.Activity.postForm.displayEditActivityForm( activity_data, activity_URL_preview );
					}
				}
			}
		},

		activityPrivacyChange: function( event ) {
			var parent      = event.data, target = $( event.target ), activity_item = $( event.currentTarget ).closest( '.activity-item' ),
				activity_id = activity_item.data( 'bp-activity-id' );

			// Stop event propagation.
			event.preventDefault();

			if ( typeof target.data( 'value' ) === 'undefined' || $.trim( target.data( 'value' ) ) == '' ) {
				return false;
			}

			activity_item.find( '.privacy' ).addClass( 'loading' );

			parent.ajax( { action: 'activity_update_privacy', 'id': activity_id, 'privacy': target.data( 'value' ) }, 'activity' ).done(
				function( response ) {
					activity_item.find( '.privacy' ).removeClass( 'loading' );

					if ( true === response.success ) {
						activity_item.find( '.activity-privacy li' ).removeClass( 'selected' );
						activity_item.find( '.privacy-wrap' ).attr( 'data-bp-tooltip', target.text() );
						target.addClass( 'selected' );
						activity_item.find( '.privacy' ).removeClass( 'public' ).removeClass( 'loggedin' ).removeClass( 'onlyme' ).removeClass( 'friends' );
						activity_item.find( '.privacy' ).addClass( target.data( 'value' ) );

						if ( typeof response !== 'undefined' && typeof response.data !== 'undefined' && typeof response.data.video_symlink !== 'undefined' ) {

							// Update the document video file src on privacy update in activity feed.
							if ( $( '.document-description-wrap' ).length && $( '.document-description-wrap .bb-open-document-theatre' ).length ) {
								$( '.document-description-wrap .bb-open-document-theatre' ).attr( 'data-video-preview', response.data.video_symlink );
								$( '.document-description-wrap .bb-open-document-theatre' ).attr( 'data-extension', response.data.extension );
							}

							// Update the document video file src on privacy update in activity feed.
							if ( $( '.document-description-wrap' ).length && $( '.document-detail-wrap.document-detail-wrap-description-popup' ).length ) {
								$( '.document-detail-wrap.document-detail-wrap-description-popup' ).attr( 'data-video-preview', response.data.video_symlink );
								$( '.document-detail-wrap.document-detail-wrap-description-popup' ).attr( 'data-extension', response.data.extension );
							}

							var myPlayer = videojs( response.data.video_js_id );
							myPlayer.src(
								{
									type: response.data.video_extension,
									src: response.data.video_symlink
								}
							);
						}

						bp.Nouveau.Activity.activityHasUpdates = true;
						bp.Nouveau.Activity.currentActivityId = activity_id;
					}
				}
			);
		},

		activityPrivacyRedirect: function( event ) {
			var target = $( event.target );

			// Stop event propagation.
			event.preventDefault();
			if ( typeof target.data( 'value' ) === 'undefined' || $.trim( target.data( 'value' ) ) == '' ) {
				return false;
			} else {
				window.location.href = target.data( 'value' );
			}
		},

		/* jshint ignore:start */
		togglePrivacyDropdown: function( event ) {

			var parent      = event.data, target = $( event.target ), activity_item = $( event.currentTarget ).closest( '.activity-item' ),
				activity_id = activity_item.data( 'bp-activity-id' );

			// Stop event propagation.
			event.preventDefault();

			// close other dropdowns.
			$( 'ul.activity-privacy' ).not( activity_item.find( '.activity-privacy' ) ).removeClass( 'bb-open' );

			activity_item.find( '.activity-privacy' ).toggleClass( 'bb-open' );

		},
		/* jshint ignore:end */

		/**
		 * [activityActions description]
		 *
		 * @param  {[type]} event [description]
		 * @return {[type]}       [description]
		 */
		activityActions: function( event ) {
			var parent                     = event.data, target = $( event.target ), activity_item = $( event.currentTarget ),
				activity_id                = activity_item.data( 'bp-activity-id' ), stream = $( event.delegateTarget ),
				activity_state             = activity_item.find( '.activity-state' ),
				comments_text              = activity_item.find( '.comments-count' ),
				item_id, form, model, self = this, commentsList;

			// Check if target is inside #activity-modal or media theatre
			var isInsideModal = target.closest( '#activity-modal' ).length > 0;
			var isInsideMediaTheatre = target.closest( '.bb-internal-model' ).length > 0;

			if (isInsideModal) {
				activity_state = activity_item.closest( '#activity-modal' ).find( '.activity-state' );
				comments_text = activity_item.closest( '#activity-modal' ).find( '.comments-count' );
			}

			// In case the target is set to a span or i tag inside the link.
			if (
				$( target ).is( 'span' ) ||
				$( target ).is( 'i' ) ||
				$( target ).is( 'img' )
			) {
				target = $( target ).closest( 'a' );
			}

			// If emotion item exists then take reaction id and update the target.
			var reaction_id = 0;
			if (
				target.parent( '.ac-emotion_btn' ) &&
				! ( target.hasClass( 'fav' ) || target.hasClass( 'unfav' ) )
				) {
				reaction_id = target.parents( '.ac-emotion_item' ).attr( 'data-reaction-id' );
			}

			// Favorite and unfavorite logic.
			if ( target.hasClass( 'fav' ) || target.hasClass( 'unfav' ) || reaction_id > 0 ) {
				// Stop event propagation.
				event.preventDefault();

				// Do not trigger click event directly on the button when it's mobile and reaction is active.
				if ( $( 'body' ).hasClass( 'bb-is-mobile' ) && $( 'body' ).hasClass( 'bb-reactions-mode' ) && target.closest( '.ac-emotion_btn' ).length === 0 && event.customTriggered !== true ) {
					return;
				}

				if ( ! $( target ).is( 'a' ) ) {
					target = $( target ).closest( 'a' );
				}

				if ( target.hasClass( 'loading' ) ) {
					return;
				}

				target.addClass( 'loading' );

				var type        = target.hasClass( 'fav' ) ? 'fav' : 'unfav',
					is_activity = true,
					item_type   = 'activity',
					parent_el   = target.parents( '.acomment-display' ).first(),
					reacted_id  = target.attr( 'data-reacted-id' ),
					main_el;

				if ( reaction_id > 0 ) {
					type = 'fav';
				}

				// Return when same reaction ID found.
				if ( target.parent( '.ac-emotion_btn' ) ) {
					reacted_id = target.parents( '.bp-generic-meta' ).find( '.unfav' ).attr( 'data-reacted-id' );
				}

				if ( 'fav' === type && parseInt( reaction_id ) === parseInt( reacted_id ) ) {
					target.removeClass( 'loading' );
					return;
				}

				if ( 0 < parent_el.length ) {
					is_activity = false;
					item_type   = 'activity_comment';
				}

				if ( ! is_activity ) {
					main_el = target.parents( '.activity-comment' ).first();
					item_id = main_el.data( 'bp-activity-comment-id' );
				} else {
					main_el = target.parents( '.activity-item' );
					item_id = main_el.data( 'bp-activity-id' );
				}

				var data = {
					action: 'activity_mark_' + type,
					reaction_id: reaction_id,
					item_id: item_id,
					item_type: item_type,
				};

				parent.ajax( data, 'activity' ).done(
					function( response ) {

						if ( false === response.success ) {
							target.removeClass( 'loading' );
							alert( response.data );
							return;
						} else {
							target.fadeOut(
								200,
								function() {

									if ('false' === $( this ).attr( 'aria-pressed' ) ) {
										$( this ).attr( 'aria-pressed', 'true' );
									} else {
										$( this ).attr( 'aria-pressed', 'false' );
									}

									// Update reacted user name and counts.
									if ( 'undefined' !== typeof response.data.reaction_count ) {
										if ( is_activity ) {
											if ( 0 < main_el.find( '.activity-content .activity-state-reactions' ).length ) {
												main_el.find( '.activity-content  .activity-state-reactions' ).replaceWith( response.data.reaction_count );
											} else {
												main_el.find( '.activity-content .activity-state' ).prepend( response.data.reaction_count );
											}

											// Added has-likes class if activity has any reactions.
											if ( response.data.reaction_count !== '' ) {
												activity_state.addClass( 'has-likes' );
											} else {
												activity_state.removeClass( 'has-likes' );
											}

										} else {
											if ( 0 < main_el.find( '#acomment-display-' + item_id + ' .comment-reactions .activity-state-reactions' ).length ) {
												main_el.find( '#acomment-display-' + item_id + ' .comment-reactions .activity-state-reactions' ).replaceWith( response.data.reaction_count );
											} else {
												main_el.find( '#acomment-display-' + item_id + ' .comment-reactions' ).prepend( response.data.reaction_count );
											}
										}
									}

									// Update reacted button.
									if ( response.data.reaction_button ) {
										if ( is_activity ) {
											main_el.find( '.bp-generic-meta a.bp-like-button:first' ).replaceWith( response.data.reaction_button );
										} else {
											main_el.find( '#acomment-display-' + item_id + ' .bp-generic-meta a.bp-like-button' ).replaceWith( response.data.reaction_button );
										}
									}

									// Hide Reactions popup.
									main_el.find( '.ac-emotions_list' ).removeClass( 'active' );

									bp.Nouveau.Activity.activityHasUpdates = true;
									bp.Nouveau.Activity.currentActivityId = item_id;

									$( this ).fadeIn( 200 );
									target.removeClass( 'loading' );
								}
							);
						}

						// Add "Likes/Emotions" menu item on activity directory nav menu.
						if ( 'fav' === type ) {
							if (
								typeof response.data.directory_tab !== 'undefined' &&
								response.data.directory_tab !== '' &&
								! $( parent.objectNavParent + ' [data-bp-scope="favorites"]' ).length
							) {
								$( parent.objectNavParent + ' [data-bp-scope="all"]' ).after( response.data.directory_tab );
							}

						} else if ( 'unfav' === type ) {
							var favoriteScope = $( '[data-bp-user-scope="favorites"]' ).hasClass( 'selected' ) || $( parent.objectNavParent + ' [data-bp-scope="favorites"]' ).hasClass( 'selected' );

							// If on user's profile or on the favorites directory tab, remove the entry.
							if ( favoriteScope ) {
								activity_item.remove();
							}

							if ( undefined !== response.data.no_favorite ) {
								// Remove the tab when on activity directory but not on the favorites tabs.
								if ( $( parent.objectNavParent + ' [data-bp-scope="all"]' ).length && $( parent.objectNavParent + ' [data-bp-scope="all"]' ).hasClass( 'selected' ) ) {
									$( parent.objectNavParent + ' [data-bp-scope="favorites"]' ).remove();

									// In all the other cases, append a message to the empty stream.
								} else if ( favoriteScope ) {
									stream.append( response.data.no_favorite );
								}
							}
						}
					}
				).fail(
					function() {
						target.removeClass( 'loading' );
					}
				);
			}

			// Deleting or spamming.
			if ( target.hasClass( 'delete-activity' ) || target.hasClass( 'acomment-delete' ) || target.hasClass( 'spam-activity' ) || target.hasClass( 'spam-activity-comment' ) ) {
				var activity_comment_li = target.closest( '[data-bp-activity-comment-id]' ),
					activity_comment_id = activity_comment_li.data( 'bp-activity-comment-id' ),
					li_parent, comment_count_span, comment_count, show_all_a, deleted_comments_count = 0;

				commentsList = target.closest( '.activity-comments' );
				commentsList.addClass( 'active' );

				// Stop event propagation.
				event.preventDefault();

				if ( undefined !== BP_Nouveau.confirm && false === window.confirm( BP_Nouveau.confirm ) ) {
					return false;
				}

				target.addClass( 'loading' );

				var ajaxData = {
					action      : 'delete_activity',
					'id'        : activity_id,
					'_wpnonce'  : parent.getLinkParams( target.prop( 'href' ), '_wpnonce' ),
					'is_single' : target.closest( '[data-bp-single]' ).length
				};

				// Only the action changes when spamming an activity or a comment.
				if ( target.hasClass( 'spam-activity' ) || target.hasClass( 'spam-activity-comment' ) ) {
					ajaxData.action = 'bp_spam_activity';
				}

				// Set defaults parent li to activity container.
				li_parent = activity_item;

				// If it's a comment edit ajaxData.
				if ( activity_comment_id ) {
					delete ajaxData.is_single;

					// Set comment data.
					ajaxData.id         = activity_comment_id;
					ajaxData.is_comment = true;

					// Set parent li to activity comment container.
					li_parent = activity_comment_li;
				}

				parent.ajax( ajaxData, 'activity' ).done(
					function( response ) {
						target.removeClass( 'loading' );

						if ( false === response.success ) {
							li_parent.append( response.data.feedback );
							li_parent.find( '.bp-feedback' ).hide().fadeIn( 300 );
						} else {
							var closestParentElement = li_parent.closest( '.has-child-comments' );
							if ( li_parent.hasClass( 'has-child-comments' ) ) {
								var closestNestedParentElement = li_parent.closest('ul').closest( 'li' );
							}
							var closestList = closestParentElement.find( '> ul' );

							// Specific case of the single activity screen.
							if ( response.data.redirect ) {
								return window.location.href = response.data.redirect;
							}

							if ( response.data.parent_activity_id && response.data.activity ) {
								$( 'body:not(.activity-singular) #buddypress #activity-stream ul.activity-list li#activity-' + response.data.parent_activity_id ).replaceWith( response.data.activity );
							}

							if ( activity_comment_id ) {
								deleted_comments_count = 1;
								var hidden_comments_count = activity_comment_li.find( '.acomments-view-more' ).data( 'child-count' );

								// Move the form if needed.
								activity_item.append( activity_comment_li.find( 'form' ) );

								// Count child comments if there are some.
								$.each(
									activity_comment_li.find( 'li.comment-item' ),
									function() {
										deleted_comments_count += 1;
									}
								);

								deleted_comments_count += hidden_comments_count !== undefined ? parseFloat( hidden_comments_count ) : 0;

								// Update the button count.
								comment_count_span = activity_state.find( 'span.comments-count' );
								comment_count      = comment_count_span.text().length ? comment_count_span.text().match( /\d+/ )[0] : 0;
								comment_count      = Number( comment_count - deleted_comments_count );

								if ( comments_text.length ) {
									var label = comment_count > 1 ? BP_Nouveau.activity.strings.commentsLabel : BP_Nouveau.activity.strings.commentLabel;
									comments_text.text( label.replace( '%d', comment_count ) );
								} else {
									comment_count_span.parent( '.has-comments' ).removeClass( 'has-comments' );
								}

								// Update the show all count.
								show_all_a = activity_item.find( 'li.show-all a' );
								if ( show_all_a.length ) {
									show_all_a.html( BP_Nouveau.show_x_comments.replace( '%d', comment_count ) );
								}

								// Clean up the parent activity classes.
								if ( 0 === comment_count ) {
									activity_item.removeClass( 'has-comments' );
									activity_state.removeClass( 'has-comments' );
									comments_text.empty();
								}
							}

							// Remove the entry.
							li_parent.slideUp(
								300,
								function() {
									li_parent.remove();

									if ( closestList.find( 'li' ).length === 0 ) {
										closestParentElement.removeClass( 'has-child-comments' );
									}

									if ( typeof closestNestedParentElement !== 'undefined' ) {
										var closestParentElementList = closestNestedParentElement.find( '> ul' );
										var trimmedList = closestParentElementList.html().trim();
										if ( trimmedList === '' ) {
											closestNestedParentElement.removeClass( 'has-child-comments' );
										}
									}
								}
							);

							// reset vars to get newest activities when an activity is deleted.
							if ( ! activity_comment_id && activity_item.data( 'bp-timestamp' ) === parent.Activity.heartbeat_data.last_recorded ) {
								parent.Activity.heartbeat_data.newest        = '';
								parent.Activity.heartbeat_data.last_recorded = 0;
							}

							// Inform other scripts.
							$( document ).trigger( 'bp_activity_ajax_delete_request', $.extend( ajaxData, { response: response } ) );
							$( document ).trigger( 'bp_activity_ajax_delete_request_video', $.extend( ajaxData, { response: response } ) );

							bp.Nouveau.Activity.activityHasUpdates = true;
							bp.Nouveau.Activity.currentActivityId = activity_id;
						}

						commentsList.removeClass( 'active' );
					}
				);
			}

			// Reading more.
			if ( target.closest( 'span' ).hasClass( 'activity-read-more' ) ) {
				var content = target.closest( 'div' ), readMore = target.closest( 'span' );

				item_id = null;

				if ( $( content ).hasClass( 'activity-inner' ) ) {
					item_id = activity_id;
				} else if ( $( content ).hasClass( 'acomment-content' ) ) {
					item_id = target.closest( 'li' ).data( 'bp-activity-comment-id' );
				}

				if ( ! item_id ) {
					return event;
				}

				// Stop event propagation.
				event.preventDefault();

				$( readMore ).addClass( 'loading' );

				parent.ajax(
					{
						action : 'get_single_activity_content',
						id     : item_id
					},
					'activity'
				).done(
					function( response ) {

						// check for JSON output.
						if ( typeof response !== 'object' && target.closest( 'div' ).find( '.bb-activity-media-wrap' ).length > 0 ) {
							response = JSON.parse( response );
						}

						$( readMore ).removeClass( 'loading' );

						if ( content.parent().find( '.bp-feedback' ).length ) {
							content.parent().find( '.bp-feedback' ).remove();
						}

						if ( false === response.success ) {
							content.after( response.data.feedback );
							content.parent().find( '.bp-feedback' ).hide().fadeIn( 300 );
						} else {
							$( content ).html( response.data.contents ).slideDown( 300 );

							// replace dummy image with original image by faking scroll event to call bp.Nouveau.lazyLoad.
							jQuery( window ).scroll();

							if ( activity_item.hasClass( 'wp-link-embed' ) ) {
								if (typeof window.instgrm !== 'undefined') {
									window.instgrm.Embeds.process();
								}
								if (typeof window.FB !== 'undefined' && typeof window.FB.XFBML !== 'undefined') {
									window.FB.XFBML.parse( document.getElementById( 'activity-' + item_id ) );
								}
							}
						}
					}
				);
			}

			// Initiate Comment Form.
			if ( target.hasClass( 'ac-form' ) && target.hasClass( 'not-initialized' ) ) {
				target.closest( '.activity-item' ).find( '.acomment-reply' ).eq( 0 ).trigger( 'click' );
			}

			// Displaying the comment form.
			if (
				target.hasClass( 'activity-state-comments' ) ||
				target.hasClass( 'acomment-reply' ) ||
				target.parent().hasClass( 'acomment-reply' ) ||
				target.hasClass( 'acomment-edit' )
			) {
				if ( target.parents( '.activity-item' ).hasClass( 'bb-closed-comments' ) ) {
					event.preventDefault();
					return;
				}

				var comment_link          = target;
				item_id                   = activity_id;
				var hasParentModal;
				var $activity_comments;

				if ( isInsideModal ) {
					form = $( '#activity-modal' ).find( '#ac-form-' + activity_id );
					$activity_comments = $( '#activity-modal' ).find( '.bb-modal-activity-footer' );
					hasParentModal = '#activity-modal ';
				} else if ( isInsideMediaTheatre ) {
					form = $( '.bb-internal-model' ).find( '#ac-form-' + activity_id );
					hasParentModal = '.bb-internal-model ';
				} else {
					form = $( '#ac-form-' + activity_id );
					$activity_comments = $( '[data-bp-activity-id="' + item_id + '"] .activity-comments' );
					hasParentModal = '';
				}
				var activity_comment_data = false;

				if ( target.closest( '.bb-media-model-container' ).length ) {
					form               = target.closest( '.bb-media-model-container' ).find( '#ac-form-' + activity_id );
					$activity_comments = target.closest( '.bb-media-model-container' ).find( '[data-bp-activity-id="' + item_id + '"] .activity-comments' );
				}

				// Show comment form on activity item when it is hidden initially.
				if ( ! target.closest( '.activity-item' ).hasClass( 'has-comments' ) ) {
					target.closest( '.activity-item' ).addClass( 'has-comments' );
				}

				// Stop event propagation.
				event.preventDefault();

				// If form is edit activity comment, then reset it.
				self.resetActivityCommentForm( form, 'hardReset' );

				// If the comment count span inside the link is clicked.
				if ( target.parent().hasClass( 'acomment-reply' ) ) {
					comment_link = target.parent();
				}

				if ( target.closest( 'li' ).data( 'bp-activity-comment-id' ) ) {
					item_id = target.closest( 'li' ).data( 'bp-activity-comment-id' );
				}

				if ( target.closest( 'li' ).data( 'bp-activity-comment' ) ) {
					activity_comment_data = target.closest( 'li' ).data( 'bp-activity-comment' );
				}

				this.toggleMultiMediaOptions( form, target );

				form.removeClass( 'root' );
				$( '.ac-form' ).addClass( 'not-initialized' );
				$( '.ac-form' ).find( '.ac-input' ).html( '' );

				bp.Nouveau.Activity.clearFeedbackNotice( form );

				/* Remove any error messages */
				$.each(
					form.children( 'div' ),
					function( e, err ) {
						if ( $( err ).hasClass( 'error' ) ) {
							$( err ).remove();
						}
					}
				);

				if ( target.hasClass( 'acomment-edit' ) && ! _.isNull( activity_comment_data ) ) {
					var acomment = $( hasParentModal + '[data-bp-activity-comment-id="' + item_id + '"]' );
					acomment.find( '#acomment-display-' + item_id ).addClass( 'bp-hide' );
					acomment.find( '#acomment-edit-form-' + item_id ).append( form );
					form.addClass( 'acomment-edit' ).attr( 'data-item-id', item_id );

					self.validateCommentContent( form.find( '.ac-textarea' ).children( '.ac-input' ) );

					// Render activity comment edit data to form.
					self.editActivityCommentForm( form, activity_comment_data );

					if ( isInsideModal ) {
						$( '.bb-modal-activity-footer' ).removeClass( 'active' );
					}
				} else {
					// It's an activity we're commenting.
					if ( item_id === activity_id ) {
						if ( isInsideModal ) {
							$( '.bb-modal-activity-footer' ).addClass( 'active' );
							$( '#activity-modal' ).find( '.acomment-display' ).removeClass( 'display-focus' );
							$( '#activity-modal' ).find( '.comment-item' ).removeClass( 'comment-item-focus' );
						}

						$activity_comments.append( form );
						form.addClass( 'root' );
						$activity_comments.find( '.acomment-display' ).removeClass( 'display-focus' );
						$activity_comments.find( '.comment-item' ).removeClass( 'comment-item-focus' );

						// It's a comment we're replying to.
					} else {
						if ( isInsideModal ) {
							$( '.bb-modal-activity-footer' ).removeClass( 'active' );
							$( '#activity-modal' ).find( '[data-bp-activity-comment-id="' + item_id + '"]' ).append( form );
						} else if ( isInsideMediaTheatre ) {
							$( '.bb-internal-model' ).find( '[data-bp-activity-comment-id="' + item_id + '"]' ).append( form );
						} else {
							$( '[data-bp-activity-comment-id="' + item_id + '"]' ).append( form );
						}
					}
				}

				form.removeClass( 'not-initialized' );

				var emojiPosition = form.find( '.post-elements-buttons-item.post-emoji' ).prevAll().not( ':hidden' ).length + 1;
				form.find( '.post-elements-buttons-item.post-emoji' ).attr( 'data-nth-child', emojiPosition );

				var gifPosition = form.find( '.post-elements-buttons-item.post-gif' ).prevAll().not( ':hidden' ).length + 1;
				form.find( '.post-elements-buttons-item.post-gif' ).attr( 'data-nth-child', gifPosition );

				/* Stop past image from clipboard */
				var ce = form.find( '.ac-input[contenteditable]' );
				bp.Nouveau.Activity.listenCommentInput( ce );

				// change the aria state from false to true.
				target.attr( 'aria-expanded', 'true' );
				target.closest( '.activity-comments' ).find( '.acomment-display' ).removeClass( 'display-focus' );
				target.closest( '.activity-comments' ).find( '.comment-item' ).removeClass( 'comment-item-focus' );
				target.closest( '.acomment-display' ).addClass( 'display-focus' );
				target.closest( '.comment-item' ).addClass( 'comment-item-focus' );

				var activity_data_nickname;
				var activity_user_id;
				var current_user_id;

				if ( ! _.isNull( activity_comment_data ) ) {
					activity_data_nickname = activity_comment_data.nickname;
				}

				if ( ! _.isNull( activity_comment_data ) ) {
					activity_user_id = activity_comment_data.user_id;
				}

				var atWho = '<span class="atwho-inserted" data-atwho-at-query="@" contenteditable="false">@' + activity_data_nickname + '</span>&nbsp;';

				if ( ! _.isUndefined( BP_Nouveau.activity.params.user_id ) ) {
					current_user_id = BP_Nouveau.activity.params.user_id;
				}

				var peak_offset = ( $( window ).height() / 2 - 75 );

				var scrollOptions = {
					offset: -peak_offset,
					easing: 'swing'
				};

				var div_editor = ce.get( 0 );

				if ( ! jQuery( 'body' ).hasClass( 'bb-is-mobile' ) ) {
					if ( isInsideModal ) {
						$( '.bb-modal-activity-body' ).scrollTo( form, 500, scrollOptions );
					} else {
						$.scrollTo( form, 500, scrollOptions );
					}
				} else {
					setTimeout(
						function() {
							var scrollInt = jQuery( window ).height() > 300 ? 200 : 100;
							jQuery( 'html, body' ).animate( { scrollTop: jQuery( div_editor ).offset().top - scrollInt }, 500 );
						},
						500
					);
				}

				$( hasParentModal + '#ac-form-' + activity_id + ' #ac-input-' + activity_id ).focus();

				if ( ! _.isUndefined( BP_Nouveau.media ) && ! _.isUndefined( BP_Nouveau.media.emoji ) && 'undefined' == typeof $( hasParentModal + '#ac-input-' + activity_id ).data( 'emojioneArea' ) ) {
					// Store HTML data of editor.
					var editor_data = $( hasParentModal + '#ac-input-' + activity_id ).html();

					bp.Nouveau.Activity.initializeEmojioneArea( isInsideModal, hasParentModal, activity_id );

					// Restore HTML data of editor after emojioneArea intialized.
					if ( target.hasClass( 'acomment-edit' ) && ! _.isNull( activity_comment_data ) ) {
						$( hasParentModal + '#ac-input-' + activity_id ).html( editor_data );
					}
				}

				// Tag user on comment replies.
				if (
					! target.hasClass( 'acomment-edit' ) &&
					! target.hasClass( 'button' ) &&
					! target.hasClass( 'activity-state-comments' ) &&
					current_user_id !== activity_user_id
				) {
					$( hasParentModal + '#ac-input-' + activity_id ).html( atWho );
					form.addClass( 'has-content' );
				}

				// Place caret at the end of the content.
				if (
					'undefined' !== typeof window.getSelection &&
					'undefined' !== typeof document.createRange &&
					! _.isNull( activity_comment_data )
				) {
					var range = document.createRange();
					range.selectNodeContents( $( hasParentModal + '#ac-input-' + activity_id )[0] );
					range.collapse( false );
					var selection = window.getSelection();
					selection.removeAllRanges();
					selection.addRange( range );
				}

				if ( ! _.isUndefined( window.MediumEditor ) && ! $( hasParentModal + '#ac-input-' + activity_id ).hasClass( 'medium-editor-element' ) ) {
					window.activity_comment_editor = new window.MediumEditor(
						$( hasParentModal + '#ac-input-' + activity_id )[0],
						{
							placeholder: false,
							toolbar: false,
							paste: {
								forcePlainText: false,
								cleanPastedHTML: false
							},
							keyboardCommands: false,
							imageDragging: false,
							anchorPreview: false,
						}
					);
				}
			}

			if ( target.hasClass( 'activity-state-no-comments' ) ) {
				// Stop event propagation.
				event.preventDefault();
			}

			// Removing the form.
			if ( target.hasClass( 'ac-reply-cancel' ) ) {

				var $form = $( target ).closest( '.ac-form' );
				$form.addClass( 'not-initialized' );

				// Change the aria state back to false on comment cancel.
				$( '.acomment-reply' ).attr( 'aria-expanded', 'false' );

				self.destroyCommentMediaUploader( activity_id );
				self.destroyCommentDocumentUploader( activity_id );

				// If form is edit activity comment, then reset it.
				self.resetActivityCommentForm( $form );

				// Stop event propagation.
				event.preventDefault();
			}

			// Submitting comments and replies.
			if ( 'ac_form_submit' === target.prop( 'name' ) ) {
				target.prop( 'disabled', true );

				var comment_content, comment_data;

				commentsList = target.closest( '.activity-comments' );
				commentsList.addClass( 'active' );

				form    = target.closest( 'form' );
				item_id = activity_id;

				// Stop event propagation.
				event.preventDefault();

				if ( target.closest( 'li' ).data( 'bp-activity-comment-id' ) ) {
					item_id = target.closest( 'li' ).data( 'bp-activity-comment-id' );
				}

				comment_content = $( form ).find( '.ac-input' ).first();

				// replacing atwho query from the comment content to disable querying it in the requests.
				var atwho_query = comment_content.find( 'span.atwho-query' );
				for ( var i = 0; i < atwho_query.length; i++ ) {
					$( atwho_query[i] ).replaceWith( atwho_query[i].innerText );
				}

				// transform other emoji into emojionearea emoji.
				comment_content.find( 'img.emoji' ).each(
					function( index, Obj) {
						$( Obj ).addClass( 'emojioneemoji' );
						var emojis = $( Obj ).attr( 'alt' );
						$( Obj ).attr( 'data-emoji-char', emojis );
						$( Obj ).removeClass( 'emoji' );
					}
				);

				// Transform emoji image into emoji unicode.
				comment_content.find( 'img.emojioneemoji' ).replaceWith(
					function () {
						return this.dataset.emojiChar;
					}
				);

				if ( 'undefined' === typeof activity_id && target.parents('.bb-modal-activity-footer').length > 0 ) {
					activity_id = target.parents('form.ac-form').find('input[name=comment_form_id]').val();
					item_id     = activity_id;
				}

				target.parent().addClass( 'loading' ).prop( 'disabled', true );
				comment_content.addClass( 'loading' ).prop( 'disabled', true );
				var comment_value = comment_content[0].innerHTML.replace( /<div>/gi,'\n' ).replace( /<\/div>/gi,'' );

				comment_data = {
					action                        : 'new_activity_comment',
					_wpnonce_new_activity_comment : $( '#_wpnonce_new_activity_comment' ).val(),
					comment_id                    : item_id,
					form_id                       : activity_id,
					content                       : comment_value
				};

				// Add the Akismet nonce if it exists.
				if ( $( '#_bp_as_nonce_' + activity_id ).val() ) {
					comment_data['_bp_as_nonce_' + activity_id] = $( '#_bp_as_nonce_' + activity_id ).val();
				}

				// add media data if enabled or uploaded.
				if ( this.dropzone_media.length ) {
					comment_data.media = this.dropzone_media;
				}

				// add document data if enabled or uploaded.
				if ( this.dropzone_document.length ) {
					comment_data.document = this.dropzone_document;
				}

				// add video data if enabled or uploaded.
				if ( this.dropzone_video.length ) {
					comment_data.video = this.dropzone_video;

					if ( _.isEmpty( comment_data.content ) ) {
						comment_data.content = '&#8203;';
					}
				}

				// add gif data if enabled or uploaded.
				if ( ! _.isUndefined( this.models[activity_id] ) ) {
					model                 = this.models[activity_id];
					comment_data.gif_data = this.models[activity_id].get( 'gif_data' );
				}

				comment_data.content = comment_data.content.replace( /&nbsp;/g, ' ' );

				if ( form.hasClass( 'acomment-edit' ) ) {
					comment_data.edit_comment = true;
				}

				var isFooterForm = target.closest('.bb-modal-activity-footer').length > 0;

				parent.ajax( comment_data, 'activity' ).done(
					function( response ) {
						target.parent().removeClass( 'loading' );
						comment_content.removeClass( 'loading' );
						$( '.acomment-reply' ).attr( 'aria-expanded', 'false' );

						if ( false === response.success ) {
							form.append( $( response.data.feedback ).hide().fadeIn( 200 ) );
						} else {
							var isElementorWidget = target.closest( '.elementor-activity-item' ).length > 0;
							var isCommentElementorWidgetForm = form.prev().hasClass( 'activity-actions' );
							var activity_comments;

							if (isElementorWidget && isCommentElementorWidgetForm) {
								activity_comments = form.parent().find( '.activity-actions' );
							} else {
								activity_comments = form.parent();
							}
							var the_comment       = $.trim( response.data.contents );

							activity_comments.find( '.acomment-display' ).removeClass('display-focus');
							activity_comments.find( '.comment-item' ).removeClass( 'comment-item-focus' );
							activity_comments.addClass( 'has-child-comments' );

							var form_activity_id = form.find( 'input[name="comment_form_id"]' ).val();
							if ( isInsideModal ) {
								$('#activity-modal').find( '.bb-modal-activity-footer' ).append( form ).addClass( 'active' );
								form.removeClass( 'has-content' ).addClass( 'root' );
							} else {
								form.addClass( 'not-initialized' ).removeClass( 'has-content has-gif has-media' );
								form.closest( '.activity-comments' ).append( form );
							}
							form.find( '#ac-input-' + form_activity_id ).html( '' );

							if ( form.hasClass( 'acomment-edit' ) ) {
								var form_item_id = form.attr( 'data-item-id' );
								form.closest( '.activity-comments' ).append( form );
								if ( isInsideModal ) {
									$( '#activity-modal' ).find( 'li#acomment-' + form_item_id ).replaceWith( the_comment );
								} else {
									$( 'li#acomment-' + form_item_id ).replaceWith( the_comment );
								}
							} else {
								if ( 0 === activity_comments.children( 'ul' ).length ) {
									if ( activity_comments.hasClass( 'activity-comments' ) ) {
										activity_comments.prepend( '<ul></ul>' );
									} else {
										activity_comments.append( '<ul></ul>' );
									}
								}

								if ( isFooterForm ) {
									form.closest( '#activity-modal' ).find( '.bb-modal-activity-body .activity-comments, .bb-modal-activity-body .activity-comments .activity-actions' ).children( 'ul' ).append( $( the_comment ) );
								} else {
									activity_comments.children( 'ul' ).append( $( the_comment ).hide().fadeIn( 200 ) );
								}

								$( form ).find( '.ac-input' ).first().html( '' );

								activity_comments.parent().addClass( 'has-comments' );
								activity_comments.parent().addClass( 'comments-loaded' );
								activity_state.addClass( 'has-comments' );
								// replace dummy image with original image by faking scroll event to call bp.Nouveau.lazyLoad.
							}

							form.removeClass( 'acomment-edit' );

							var tool_box_comment = form.find( '.ac-reply-content' );
							if ( tool_box_comment.find( '.ac-reply-toolbar .ac-reply-media-button' ).length > 0 ) {
								tool_box_comment.find( '.ac-reply-toolbar .ac-reply-media-button' ).parents( '.post-elements-buttons-item' ).removeClass( 'disable no-click' );
							}
							if ( tool_box_comment.find( '.ac-reply-toolbar .ac-reply-document-button' ).length > 0 ) {
								tool_box_comment.find( '.ac-reply-toolbar .ac-reply-document-button' ).parents( '.post-elements-buttons-item' ).removeClass( 'disable no-click' );
							}
							if ( tool_box_comment.find( '.ac-reply-toolbar .ac-reply-video-button' ).length > 0 ) {
								tool_box_comment.find( '.ac-reply-toolbar .ac-reply-video-button' ).parents( '.post-elements-buttons-item' ).removeClass( 'disable no-click' );
							}
							if ( tool_box_comment.find( '.ac-reply-toolbar .ac-reply-gif-button' ).length > 0 ) {
								tool_box_comment.find( '.ac-reply-toolbar .ac-reply-gif-button' ).removeClass( 'active ' );
								tool_box_comment.find( '.ac-reply-toolbar .ac-reply-gif-button' ).parents( '.post-elements-buttons-item' ).removeClass( 'disable no-click' );
							}
							jQuery( window ).scroll();

							if ( ! form.hasClass( 'acomment-edit' ) ) {
								// Set the new count.
								comment_count_span = activity_state.find( 'span.comments-count' );
								comment_count      = comment_count_span.text().length ? comment_count_span.text().match( /\d+/ )[0] : 0;
								comment_count      = Number( comment_count ) + 1;

								if ( comments_text.length ) {
									var label = comment_count > 1 ? BP_Nouveau.activity.strings.commentsLabel : BP_Nouveau.activity.strings.commentLabel;
									comments_text.text( label.replace( '%d', comment_count || 1 ) );
								}

								comment_count_span.parent( ':not( .has-comments )' ).addClass( 'has-comments' );

								// Increment the 'Show all x comments' string, if present.
								show_all_a = $( activity_item ).find( '.show-all a' );
								if ( show_all_a ) {
									show_all_a.html( BP_Nouveau.show_x_comments.replace( '%d', comment_count ) );
								}
							}

							// keep the dropzone media saved so it wont remove its attachment when destroyed.
							if ( self.dropzone_media.length ) {
								for ( var l = 0; l < self.dropzone_media.length; l++ ) {
									self.dropzone_media[l].saved = true;
								}
							}

							// keep the dropzone document saved so it wont remove its attachment when destroyed.
							if ( self.dropzone_document.length ) {
								for ( var d = 0; d < self.dropzone_document.length; d++ ) {
									self.dropzone_document[d].saved = true;
								}
							}

							// keep the dropzone video saved so it wont remove its attachment when destroyed.
							if ( self.dropzone_video.length ) {
								for ( var v = 0; v < self.dropzone_video.length; v++ ) {
									self.dropzone_video[v].saved = true;
								}
							}

							bp.Nouveau.Activity.activityHasUpdates = true;
							bp.Nouveau.Activity.currentActivityId = activity_id;

						}

						if ( ! _.isUndefined( model ) ) {
							model.set( 'gif_data', {} );
							$( '#ac-reply-post-gif-' + activity_id ).find( '.activity-attached-gif-container' ).removeAttr( 'style' );
						}

						self.destroyCommentMediaUploader( activity_id );
						self.destroyCommentDocumentUploader( activity_id );
						self.destroyCommentVideoUploader( activity_id );

						target.prop( 'disabled', false );
						comment_content.prop( 'disabled', false );

						commentsList.removeClass( 'active' );

						bp.Nouveau.Activity.clearFeedbackNotice( form );
					}
				).fail(
					function( $xhr ) {
						target.parent().removeClass( 'loading' );
						target.prop( 'disabled', false );

						bp.Nouveau.Activity.clearFeedbackNotice( form );

						if ($xhr.readyState === 0) {
							// Network error
							form.find('.ac-reply-content').after('<div class="bp-feedback bp-messages error">' + BP_Nouveau.activity.strings.commentPostError + '</div>');
						} else {
							// Other types of errors
							var errorMessage = $xhr.responseJSON && $xhr.responseJSON.message ? $xhr.responseJSON.message : $xhr.statusText;
							form.find('.ac-reply-content').after('<div class="bp-feedback bp-messages error">' + errorMessage + '</div>');
						}
					}
				);
			}

			// Edit the activity.
			if ( target.hasClass( 'edit' ) && target.hasClass( 'edit-activity' ) ) {
				// Stop event propagation.
				event.preventDefault();

				var activity_data        = activity_item.data( 'bp-activity' );
				var activity_URL_preview = activity_item.data( 'link-url' ) !== '' ? activity_item.data( 'link-url' ) : null;

				if ( typeof activity_data !== 'undefined' ) {
					bp.Nouveau.Activity.postForm.displayEditActivityForm( activity_data, activity_URL_preview );

					// Check if it's a Group activity.
					if ( target.closest( 'li' ).hasClass( 'groups' ) ) {
						$( '#bp-nouveau-activity-form' ).addClass( 'group-activity' );
					} else {
						$( '#bp-nouveau-activity-form' ).removeClass( 'group-activity' );
					}

					// Close the Media/Document popup if someone click on Edit while on Media/Document popup.
					if ( typeof bp.Nouveau.Media !== 'undefined' && typeof bp.Nouveau.Media.Theatre !== 'undefined' && ( bp.Nouveau.Media.Theatre.is_open_media || bp.Nouveau.Media.Theatre.is_open_document ) ) {
						$( document ).find( '.bb-close-media-theatre' ).trigger( 'click' );
						$( document ).find( '.bb-close-document-theatre' ).trigger( 'click' );
					}

				}
			}

			if (
				isInsideModal &&
				(
					target.hasClass( 'bb-open-media-theatre' ) ||
					target.hasClass( 'bb-open-video-theatre' ) ||
					target.hasClass( 'bb-open-document-theatre' ) ||
					target.hasClass( 'document-detail-wrap-description-popup' )
				)
			) {
				// Stop event propagation.
				event.preventDefault();

				var modal = target.closest( '#activity-modal' ), closeButton = modal.find( '.bb-modal-activity-header .bb-close-action-popup' );

				closeButton.trigger( 'click' );
			}

			// Pin OR UnPin the activity.
			if ( target.hasClass( 'pin-activity' ) || target.hasClass( 'unpin-activity' ) ) {
				// Stop event propagation.
				event.preventDefault();

				if ( ! activity_id ) {
					return event;
				}

				target.closest( '.activity-item' ).addClass( 'loading-pin' );

				var pin_action = 'pin';
				if ( target.hasClass( 'unpin-activity' ) ) {
					pin_action = 'unpin';
				}

				parent.ajax(
					{
						action     : 'activity_update_pinned_post',
						id         : activity_id,
						pin_action : pin_action
					},
					'activity'
				).done(
					function( response ) {
						target.closest( '.activity-item' ).removeClass( 'loading-pin' );

						// Check for JSON output.
						if ( 'object' !== typeof response ) {
							response = JSON.parse( response );
						}
						if ( 'undefined' !== typeof response.data && 'undefined' !== typeof response.data.feedback ) {
							var activity_list   = target.closest( 'ul.activity-list' );
							var activity_stream;
							if ( isInsideModal ) {
								activity_stream = target.closest( '.buddypress-wrap' ).find( '#activity-stream' );
							} else {
								activity_stream = target.closest( '#activity-stream' );
							}


							if ( response.success ) {

								var scope = bp.Nouveau.getStorage( 'bp-activity', 'scope' );
								var update_pinned_icon = false;
								var is_group_activity  = false;
								var activity_group_id  = '';

								if ( target.closest( 'li.activity-item' ).hasClass('groups') ) {
									is_group_activity = true;
									activity_group_id = target.closest( 'li.activity-item' ).attr('class').match(/group-\d+/);
									activity_group_id = activity_group_id[0].replace( 'group-', '' );
								}

								if ( activity_stream.hasClass( 'single-user' ) ) {
									update_pinned_icon = false;
								} else if (
									activity_stream.hasClass( 'activity' ) &&
									'all' === scope &&
									! is_group_activity
								) {
									update_pinned_icon = true;
								} else if (  activity_stream.hasClass( 'single-group' ) ) {
									update_pinned_icon = true;
								}

								// Change the pinned class and label.
								if ( 'pin' === pin_action ) {

									// Remove class from all old pinned and update action labels and icons.
									if ( update_pinned_icon ) {
										activity_list.find( 'li.activity-item' ).removeClass( 'bb-pinned' );
									}

									var update_pin_actions = 'li.activity-item:not(.groups)';
									if( is_group_activity && ! activity_stream.hasClass( 'single-group' ) ) {
										update_pin_actions = 'li.activity-item.group-' + activity_group_id;
									} else if( is_group_activity && activity_stream.hasClass( 'single-group' ) ) {
										update_pin_actions = 'li.activity-item';
									}
									activity_list.find( update_pin_actions ).each( function() {
										var action = $( this ).find( '.unpin-activity' );
										action.removeClass( 'unpin-activity' ).addClass( 'pin-activity' );

										if ( is_group_activity ) {
											action.find('span').html( BP_Nouveau.activity.strings.pinGroupPost );
										} else {
											action.find('span').html( BP_Nouveau.activity.strings.pinPost );
										}
									});

									if ( update_pinned_icon ) {
										target.closest( 'li.activity-item' ).addClass( 'bb-pinned' );
									}

									target.addClass( 'unpin-activity' );
									target.removeClass( 'pin-activity' );

									if ( target.closest( 'li.activity-item' ).hasClass('groups') ) {
										target.find('span').html( BP_Nouveau.activity.strings.unpinGroupPost );
									} else {
										target.find('span').html( BP_Nouveau.activity.strings.unpinPost );
									}
								} else if ( 'unpin' === pin_action ) {
									target.closest( 'li.activity-item' ).removeClass( 'bb-pinned' );
									target.addClass( 'pin-activity' );
									target.removeClass( 'unpin-activity' );
									if ( target.closest( 'li.activity-item' ).hasClass('groups') ) {
										target.find('span').html( BP_Nouveau.activity.strings.pinGroupPost );
									} else {
										target.find('span').html( BP_Nouveau.activity.strings.pinPost );
									}
								}

								if ( 'all' === scope && update_pinned_icon ) {
									bp.Nouveau.Activity.heartbeat_data.last_recorded = 0;
									bp.Nouveau.refreshActivities();
								}
							}

							$( document ).trigger(
								'bb_trigger_toast_message',
								[
									'',
									'<div>' + response.data.feedback + '</div>',
									'success',
									null,
									true
								]
							);
						}
					}
				).fail(
					function() {
						target.closest( '.activity-item' ).removeClass( 'loading-pin' );
						$( document ).trigger(
							'bb_trigger_toast_message',
							[
								'',
								'<div>' + BP_Nouveau.activity.strings.pinPostError + '</div>',
								'error',
								null,
								true
							]
						);
					}
				);
			}

			if ( target.hasClass( 'bb-icon-bell-slash' ) || target.hasClass( 'bb-icon-bell' ) ) {
				// Stop event propagation.
				event.preventDefault();

				if ( ! activity_id ) {
					return event;
				}
				target.closest( '.activity-item' ).addClass( 'loading-mute' );

				var notification_toggle_action = 'mute';
				if ( target.hasClass( 'bb-icon-bell' ) ) {
					notification_toggle_action = 'unmute';
				}

				parent.ajax(
					{
						action                     : 'toggle_activity_notification_status',
						id                         : activity_id,
						notification_toggle_action : notification_toggle_action
					},
					'activity'
				).done(
					function( response ) {
						target.closest( '.activity-item' ).removeClass( 'loading-mute' );

						// Check for JSON output.
						if ( 'object' !== typeof response ) {
							response = JSON.parse( response );
						}

						if ( 'undefined' !== typeof response.data && 'undefined' !== typeof response.data.feedback ) {

							if ( response.success ) {
								// Change the muted class and label.
								if ( 'mute' === notification_toggle_action ) {
									target.closest( 'li.activity-item' ).addClass( 'bb-muted' );
									target.removeClass( 'bb-icon-bell-slash' );
									target.addClass( 'bb-icon-bell' );
									target.attr( 'title', BP_Nouveau.activity.strings.unmuteNotification );
									target.find( 'span' ).html( BP_Nouveau.activity.strings.unmuteNotification );
								} else if ( 'unmute' === notification_toggle_action ) {
									target.closest( 'li.activity-item' ).removeClass( 'bb-muted' );
									target.removeClass( 'bb-icon-bell' );
									target.addClass( 'bb-icon-bell-slash' );
									target.attr( 'title', BP_Nouveau.activity.strings.muteNotification );
									target.find( 'span' ).html( BP_Nouveau.activity.strings.muteNotification );
								}

								if ( 'undefined' !== typeof bp.Nouveau.Activity.activityHasUpdates ) {
									bp.Nouveau.Activity.activityHasUpdates = true;
								}
							}

							$( document ).trigger(
								'bb_trigger_toast_message',
								[
									'',
									'<div>' + response.data.feedback + '</div>',
									'success',
									null,
									true
								]
							);
						}

						if ( isInsideModal ) {
							bp.Nouveau.Activity.activityPinHasUpdates = true;
						}

						bp.Nouveau.Activity.activityHasUpdates = true;
						bp.Nouveau.Activity.currentActivityId = activity_id;
					}
				).fail(
					function() {
						target.closest( '.activity-item' ).removeClass( 'loading-pin' );
						$( document ).trigger(
							'bb_trigger_toast_message',
							[
								'',
								'<div>' + BP_Nouveau.activity.strings.pinPostError + '</div>',
								'error',
								null,
								true
							]
						);
					}
				);
			}

			// Close comment turn on/off.
			if ( target.hasClass( 'close-activity-comment' ) || target.hasClass( 'unclose-activity-comment' ) ) {
				// Stop event propagation.
				event.preventDefault();

				if ( ! activity_id ) {
					return event;
				}

				target.closest( '.activity-item' ).addClass( 'loading-pin' );

				var close_comments_action = 'close_comments';
				if ( target.hasClass( 'unclose-activity-comment' ) ) {
					close_comments_action = 'unclose_comments';
				}

				parent.ajax(
					{
						action                : 'activity_update_close_comments',
						id                    : activity_id,
						close_comments_action : close_comments_action
					},
					'activity'
				).done(
					function( response ) {
						target.closest( '.activity-item' ).removeClass( 'loading-pin' );

						// Check for JSON output.
						if ( 'object' !== typeof response ) {
							response = JSON.parse( response );
						}
						if ( 'undefined' !== typeof response.data && 'undefined' !== typeof response.data.feedback ) {
							if ( response.success ) {
								var $media_parent = $( '#activity-stream > .activity-list' ).find( '[data-bp-activity-id=' + activity_id + ']' );
								target.closest( '.activity-item' ).find( '.bb-activity-closed-comments-notice' ).remove();
								// Change the close comments related class and label.
								if ( 'close_comments' === close_comments_action ) {
									target.closest( 'li.activity-item' ).addClass( 'bb-closed-comments' );
									if ( target.closest( '#activity-modal' ).length > 0 ) {
										target.closest( '#activity-modal' ).addClass( 'bb-closed-comments' );
									}
									target.addClass( 'unclose-activity-comment' );
									target.removeClass( 'close-activity-comment' );
									target.find( 'span' ).html( BP_Nouveau.activity.strings.uncloseComments );
									target.closest( 'li.activity-item.bb-closed-comments' ).find( '.edit-activity, .acomment-edit' ).parents( '.generic-button' ).hide();
									target.closest( '.activity-item' ).find( '.activity-comments' ).before( '<div class="bb-activity-closed-comments-notice">' + response.data.feedback + '</div>' );

									// Handle event from media theatre.
									if ( target.parents( '.bb-media-model-wrapper' ).length > 0 ) {
										if ( $media_parent.length > 0 ) {
											$media_parent.addClass( 'bb-closed-comments' );
											$media_parent.find( '.bb-activity-more-options .close-activity-comment span' ).html( BP_Nouveau.activity.strings.uncloseComments );
											$media_parent.find( '.bb-activity-more-options .close-activity-comment' ).addClass( 'unclose-activity-comment' ).removeClass( 'close-activity-comment' );
											$media_parent.find( '.edit-activity, .acomment-edit' ).parents( '.generic-button' ).hide();
											$media_parent.find( '.activity-comments' ).before( '<div class="bb-activity-closed-comments-notice">' + response.data.feedback + '</div>' );
										}
									}
								} else if ( 'unclose_comments' === close_comments_action ) {
									target.closest( 'li.activity-item.bb-closed-comments' ).find( '.edit-activity, .acomment-edit' ).parents( '.generic-button' ).show();
									target.closest( 'li.activity-item' ).removeClass( 'bb-closed-comments' );
									if ( target.closest( '#activity-modal' ).length > 0 ) {
										target.closest( '#activity-modal' ).removeClass( 'bb-closed-comments' );
									}
									target.addClass( 'close-activity-comment' );
									target.removeClass( 'unclose-activity-comment' );
									target.find( 'span' ).html( BP_Nouveau.activity.strings.closeComments );

									// Handle event from media theatre.
									if ( target.parents( '.bb-media-model-wrapper' ).length > 0 ) {
										if ( $media_parent.length > 0 ) {
											$media_parent.find( '.edit-activity, .acomment-edit' ).parents( '.generic-button' ).show();
											$media_parent.removeClass( 'bb-closed-comments' );
											$media_parent.find( '.bb-activity-more-options .unclose-activity-comment span' ).html( BP_Nouveau.activity.strings.closeComments );
											$media_parent.find( '.bb-activity-more-options .unclose-activity-comment' ).addClass( 'close-activity-comment' ).removeClass( 'unclose-activity-comment' );
											$media_parent.find( '.bb-activity-closed-comments-notice' ).html( '' );
										}
									}
								}

								if ( 'undefined' !== typeof bp.Nouveau.Activity.activityHasUpdates ) {
									bp.Nouveau.Activity.activityHasUpdates = true;
								}
							}

							$( document ).trigger(
								'bb_trigger_toast_message',
								[
									'',
									'<div>' + response.data.feedback + '</div>',
									response.success ? 'success' : 'error',
									null,
									true
								]
							);
						}
					}
				).fail(
					function() {
						target.closest( '.activity-item' ).removeClass( 'loading-pin' );
						$( document ).trigger(
							'bb_trigger_toast_message',
							[
								'',
								'<div>' + BP_Nouveau.activity.strings.closeCommentsError + '</div>',
								'error',
								null,
								true
							]
						);
					}
				);
			}
		},

		/**
		 * [closeCommentForm description]
		 *
		 * @param  {[type]} event [description]
		 * @return {[type]}       [description]
		 */
		commentFormAction: function( event ) {
			var element, keyCode;

			event = event || window.event;

			if ( event.target ) {
				element = event.target;
			} else if ( event.srcElement) {
				element = event.srcElement;
			}

			if ( element.nodeType === 3 ) {
				element = element.parentNode;
			}

			if ( event.altKey === true || event.metaKey === true ) {
				return event;
			}

			// Not in a comment textarea, return.
			if ( element.tagName !== 'TEXTAREA' || ! $( element ).hasClass( 'ac-input' ) ) {
				return event;
			}

			keyCode = ( event.keyCode) ? event.keyCode : event.which;

			if ( 27 === keyCode && false === event.ctrlKey  ) {
				if ( element.tagName === 'TEXTAREA' ) {
					$( element ).closest( 'form' ).slideUp( 200 );
				}
			} else if ( event.ctrlKey && 13 === keyCode && $( element ).val() ) {
				$( element ).closest( 'form' ).find( '[type=submit]' ).first().trigger( 'click' );
			}
		},

		/**
		 * [togglePopupDropdown description]
		 *
		 * @param  {[type]} event [description]
		 * @return {[type]}       [description]
		 */
		togglePopupDropdown: function( event ) {
			var element;

			event = event || window.event;

			if ( event.target ) {
				element = event.target;
			} else if ( event.srcElement) {
				element = event.srcElement;
			}

			if ( element.nodeType === 3 ) {
				element = element.parentNode;
			}

			if ( event.altKey === true || event.metaKey === true ) {
				return event;
			}

			// if privacy dropdown items, return.
			if ( $( element ).hasClass( 'privacy-wrap' ) || $( element ).parent().hasClass( 'privacy-wrap' ) ) {
				return event;
			}

			$( 'ul.activity-privacy' ).removeClass( 'bb-open' );
		},

		// activity autoload.
		loadMoreActivities: function () {

			var $load_more_btn = $( '.load-more:visible' ).last(),
				$window        = $( window );

			if ( ! $load_more_btn.closest( '.activity-list' ).length ) {
				return;
			}

			if ( ! $load_more_btn.get( 0 ) || $load_more_btn.data( 'bp-autoloaded' ) ) {
				return;
			}

			var pos    = $load_more_btn.offset();
			var offset = pos.top - 50;

			if ( $window.scrollTop() + $window.height() > offset ) {
				$load_more_btn.data( 'bp-autoloaded', 1 );
				$load_more_btn.find( 'a' ).text( BP_Nouveau.activity.strings.loadingMore );
				$load_more_btn.find( 'a' ).trigger( 'click' );
			}
		},

		destroyCommentMediaUploader: function(comment_id) {
			var self = this;

			if ( ! _.isNull( self.dropzone_obj ) ) {
				self.dropzone_obj.destroy();
				$( '#ac-reply-post-media-uploader-' + comment_id ).html( '' );
				$( '#ac-reply-post-media-uploader-1-' + comment_id ).html( '' );
			}
			self.dropzone_media = [];
			$( '#ac-reply-post-media-uploader-' + comment_id ).removeClass( 'open' ).addClass( 'closed' );
			$( '#ac-reply-media-button-' + comment_id ).removeClass( 'active' );
		},

		destroyCommentDocumentUploader: function(comment_id) {
			var self = this;

			if ( ! _.isNull( self.dropzone_document_obj ) ) {
				self.dropzone_document_obj.destroy();
				$( '#ac-reply-post-document-uploader-' + comment_id ).html( '' );
			}
			self.dropzone_document = [];
			$( '#ac-reply-post-document-uploader-' + comment_id ).removeClass( 'open' ).addClass( 'closed' );
			$( '#ac-reply-document-button-' + comment_id ).removeClass( 'active' );
		},

		destroyCommentVideoUploader: function(comment_id) {
			var self = this;

			if ( ! _.isNull( self.dropzone_video_obj ) ) {
				self.dropzone_video_obj.destroy();
				$( '#ac-reply-post-video-uploader-' + comment_id ).html( '' );
			}
			self.dropzone_video = [];
			$( '#ac-reply-post-video-uploader-' + comment_id ).removeClass( 'open' ).addClass( 'closed' );
			$( '#ac-reply-video-button-' + comment_id ).removeClass( 'active' );
		},

		resetGifPicker: function(comment_id) {

			$( '#ac-reply-gif-button-' + comment_id ).closest( '.post-gif' ).find( '.gif-media-search-dropdown' ).removeClass( 'open' ).empty();
			$( '#ac-reply-gif-button-' + comment_id ).removeClass( 'active' );
			$( '.gif-media-search-dropdown-standalone' ).removeClass( 'open' ).empty();

			// add gif data if enabled or uploaded.
			if ( ! _.isUndefined( this.models[comment_id] ) ) {
				var model = this.models[comment_id];
				model.set( 'gif_data', {} );
				$( '#ac-reply-post-gif-' + comment_id ).find( '.activity-attached-gif-container' ).removeAttr( 'style' );
			}
		},

		openCommentsMediaUploader: function(event) {
			var self               = this,
				target             = $( event.currentTarget ),
				key                = target.data( 'ac-id' ),
				dropzone_container = target.closest( '.bp-ac-form-container' ).find( '#ac-reply-post-media-uploader-' + key );

			// Check if target is inside #activity-modal
			var isInsideModal = target.closest( '#activity-modal' ).length > 0;
			var hasParentModal = isInsideModal ? '#activity-modal ' : '';

			event.preventDefault();

			if ( dropzone_container.hasClass( 'open' ) && ! event.isCustomEvent ) {
				dropzone_container.trigger( 'click' );
				dropzone_container.removeClass( 'open' ).addClass( 'closed' );
				return;
			}

			var acCommentDefaultTemplate = document.getElementsByClassName( 'ac-reply-post-default-template' ).length ? document.getElementsByClassName( 'ac-reply-post-default-template' )[0].innerHTML : ''; // Check to avoid error if Node is missing.

			if ( typeof window.Dropzone !== 'undefined' && dropzone_container.length ) {

				if ( dropzone_container.hasClass( 'closed' ) ) {

					var dropzone_options = {
						url                         : BP_Nouveau.ajaxurl,
						timeout                     : 3 * 60 * 60 * 1000,
						dictFileTooBig              : BP_Nouveau.media.dictFileTooBig,
						dictInvalidFileType         : bp_media_dropzone.dictInvalidFileType,
						dictDefaultMessage          : BP_Nouveau.media.dropzone_media_message,
						acceptedFiles               : 'image/*',
						autoProcessQueue            : true,
						addRemoveLinks              : true,
						uploadMultiple              : false,
						maxFiles                    : typeof BP_Nouveau.media.maxFiles !== 'undefined' ? BP_Nouveau.media.maxFiles : 10,
						maxFilesize                 : typeof BP_Nouveau.media.max_upload_size !== 'undefined' ? BP_Nouveau.media.max_upload_size : 2,
						thumbnailWidth              : 140,
						thumbnailHeight             : 140,
						dictMaxFilesExceeded        : BP_Nouveau.media.media_dict_file_exceeded,
						previewTemplate             : acCommentDefaultTemplate,
						dictCancelUploadConfirmation: BP_Nouveau.media.dictCancelUploadConfirmation,
						maxThumbnailFilesize        : typeof BP_Nouveau.media.max_upload_size !== 'undefined' ? BP_Nouveau.media.max_upload_size : 2,
					};

					// If a Dropzone instance already exists, destroy it before creating a new one
					if ( self.dropzone_obj instanceof Dropzone ) {
						self.dropzone_obj.destroy();
					}

					// init dropzone.
					self.dropzone_obj = new Dropzone( hasParentModal +'#ac-reply-post-media-uploader-' + target.data( 'ac-id' ), dropzone_options );

					self.dropzone_obj.on(
						'addedfile',
						function ( file ) {
							// Set data from edit comment.
							if ( file.media_edit_data ) {
								self.dropzone_media.push( file.media_edit_data );
								var tool_box    = target.parents( '.ac-reply-toolbar' );
								if ( tool_box.find( '.ac-reply-media-button' ) ) {
									tool_box.find( '.ac-reply-media-button' ).parents( '.post-elements-buttons-item' ).addClass( 'no-click' ).find( '.toolbar-button' ).addClass( 'active' );
								}
							}
						}
					);

					self.dropzone_obj.on(
						'sending',
						function(file, xhr, formData) {
							formData.append( 'action', 'media_upload' );
							formData.append( '_wpnonce', BP_Nouveau.nonces.media );

							var tool_box    = target.parents( '.ac-reply-toolbar' );
							var commentForm = target.closest( '.ac-form' );
							if ( bp.Nouveau.dropZoneGlobalProgress ) {
								bp.Nouveau.dropZoneGlobalProgress( this );
							}
							commentForm.addClass( 'has-media' );
							if ( tool_box.find( '.ac-reply-document-button' ) ) {
								tool_box.find( '.ac-reply-document-button' ).parents( '.post-elements-buttons-item' ).addClass( 'disable' );
							}
							if ( tool_box.find( '.ac-reply-video-button' ) ) {
								tool_box.find( '.ac-reply-video-button' ).parents( '.post-elements-buttons-item' ).addClass( 'disable' );
							}
							if ( tool_box.find( '.ac-reply-gif-button' ) ) {
								tool_box.find( '.ac-reply-gif-button' ).parents( '.post-elements-buttons-item' ).addClass( 'disable' );
							}
							if ( tool_box.find( '.ac-reply-media-button' ) ) {
								tool_box.find( '.ac-reply-media-button' ).parents( '.post-elements-buttons-item' ).addClass( 'no-click' ).find( '.toolbar-button' ).addClass( 'active' );
							}
							this.element.classList.remove( 'files-uploaded' );
						}
					);

					self.dropzone_obj.on(
						'uploadprogress',
						function() {

							var commentForm = target.closest( '.ac-form' );
							commentForm.addClass( 'media-uploading' );
							if ( bp.Nouveau.dropZoneGlobalProgress ) {
								bp.Nouveau.dropZoneGlobalProgress( this );
							}
						}
					);

					self.dropzone_obj.on(
						'success',
						function(file, response) {
							if ( response.data.id ) {
								file.id                  = response.id;
								response.data.uuid       = file.upload.uuid;
								response.data.menu_order = $( file.previewElement ).closest( '.dropzone' ).find( file.previewElement ).index() - 1;
								response.data.album_id   = typeof BP_Nouveau.media !== 'undefined' && typeof BP_Nouveau.media.album_id !== 'undefined' ? BP_Nouveau.media.album_id : false;
								response.data.group_id   = typeof BP_Nouveau.media !== 'undefined' && typeof BP_Nouveau.media.group_id !== 'undefined' ? BP_Nouveau.media.group_id : false;
								response.data.saved      = false;
								self.dropzone_media.push( response.data );
								return file.previewElement.classList.add( 'dz-success' );
							} else {
								var node, _i, _len, _ref, _results;
								var message = response.data.feedback;
								file.previewElement.classList.add( 'dz-error' );
								_ref     = file.previewElement.querySelectorAll( '[data-dz-errormessage]' );
								_results = [];
								for ( _i = 0, _len = _ref.length; _i < _len; _i++ ) {
									node = _ref[_i];
									_results.push( node.textContent = message );
								}
								if ( ! _.isNull( self.dropzone_obj.files ) && self.dropzone_obj.files.length === 0 ) {
									$( self.dropzone_obj.element ).removeClass( 'files-uploaded dz-progress-view' ).find( '.dz-global-progress' ).remove();
								}
								return _results;
							}
						}
					);

					self.dropzone_obj.on(
						'accept',
						function( file, done ) {
							if (file.size == 0) {
								done( BP_Nouveau.media.empty_document_type );
							} else {
								done();
							}
						}
					);

					self.dropzone_obj.on(
						'error',
						function(file,response) {
							if ( file.accepted ) {
								if ( typeof response !== 'undefined' && typeof response.data !== 'undefined' && typeof response.data.feedback !== 'undefined' ) {
									$( file.previewElement ).find( '.dz-error-message span' ).text( response.data.feedback );
								} else if ( file.status == 'error' && ( file.xhr && file.xhr.status == 0 ) ) { // update server error text to user friendly.
									$( file.previewElement ).find( '.dz-error-message span' ).text( BP_Nouveau.media.connection_lost_error );
								}
							} else {
								var commentForm = target.closest( '.ac-form' );
								bp.Nouveau.Activity.clearFeedbackNotice( commentForm );
								commentForm.find( '.ac-reply-content' ).after( '<div class="bp-feedback bp-messages error">' + response + '</div>' );
								this.removeFile( file );
								commentForm.removeClass( 'media-uploading' );
							}
						}
					);

					self.dropzone_obj.on(
						'removedfile',
						function(file) {
							if ( self.dropzone_media.length ) {
								for ( var i in self.dropzone_media ) {
									if ( file.upload.uuid == self.dropzone_media[i].uuid ) {

										if ( typeof self.dropzone_media[i].saved !== 'undefined' && ! self.dropzone_media[i].saved ) {
											bp.Nouveau.Media.removeAttachment( self.dropzone_media[i].id );
										}

										self.dropzone_media.splice( i, 1 );
										break;
									}
								}
							}

							if ( ! _.isNull( self.dropzone_obj ) && ! _.isNull( self.dropzone_obj.files ) && self.dropzone_obj.files.length === 0 ) {
								var tool_box    = target.parents( '.ac-reply-toolbar' );
								var commentForm = target.closest( '.ac-form' );
								commentForm.removeClass( 'has-media' );
								if ( tool_box.find( '.ac-reply-document-button' ) ) {
									tool_box.find( '.ac-reply-document-button' ).parents( '.post-elements-buttons-item' ).removeClass( 'disable' );
								}
								if ( tool_box.find( '.ac-reply-video-button' ) ) {
									tool_box.find( '.ac-reply-video-button' ).parents( '.post-elements-buttons-item' ).removeClass( 'disable' );
								}
								if ( tool_box.find( '.ac-reply-gif-button' ) ) {
									tool_box.find( '.ac-reply-gif-button' ).parents( '.post-elements-buttons-item' ).removeClass( 'disable' );
								}
								if ( tool_box.find( '.ac-reply-media-button' ) ) {
									tool_box.find( '.ac-reply-media-button' ).parents( '.post-elements-buttons-item' ).removeClass( 'no-click' ).find( '.toolbar-button' ).removeClass( 'active' );
								}
								$( self.dropzone_obj.element ).removeClass( 'files-uploaded dz-progress-view' ).find( '.dz-global-progress' ).remove();
								self.validateCommentContent( commentForm.find( '.ac-textarea' ).children( '.ac-input' ) );
							} else {
								target.closest( '.ac-form' ).addClass( 'has-content' );
							}
						}
					);

					// Enable submit button when all medias are uploaded.
					self.dropzone_obj.on(
						'complete',
						function() {
							if ( this.getUploadingFiles().length === 0 && this.getQueuedFiles().length === 0 && this.files.length > 0 ) {
								var commentForm = target.closest( '.ac-form' );
								commentForm.removeClass( 'media-uploading' );
								this.element.classList.add( 'files-uploaded' );
							}
						}
					);

					// container class to open close.
					dropzone_container.removeClass( 'closed' ).addClass( 'open' );

				} else {
					if ( self.dropzone_obj && typeof self.dropzone_obj !== 'undefined') {
						self.dropzone_obj.destroy();
					}
					self.dropzone_media = [];
					dropzone_container.html( '' );
					dropzone_container.addClass( 'closed' ).removeClass( 'open' );
				}
			}

			var currentTarget = event.currentTarget, activityID = currentTarget.id.match( /\d+$/ )[0];
			this.destroyCommentDocumentUploader( activityID );
			this.destroyCommentVideoUploader( activityID );

			var c_id = $( event.currentTarget ).data( 'ac-id' );
			this.resetGifPicker( c_id );

			if ( ! event.isCustomEvent ) {
				$( target ).closest( '.bp-ac-form-container' ).find( '.dropzone.media-dropzone' ).trigger( 'click' );
			}
		},

		openCommentsDocumentUploader: function(event) {
			var self               = this,
				target             = $( event.currentTarget ),
				key                = target.data( 'ac-id' ),
				dropzone_container = target.closest( '.bp-ac-form-container' ).find( '#ac-reply-post-document-uploader-' + key );

			// Check if target is inside #activity-modal
			var isInsideModal = target.closest( '#activity-modal' ).length > 0;
			var hasParentModal = isInsideModal ? '#activity-modal ' : '';

			event.preventDefault();

			if ( ! $( event.currentTarget ).closest( '.ac-form' ).hasClass( 'acomment-edit' ) ) {
				$( event.currentTarget ).toggleClass( 'active' );
			} else {
				if ( dropzone_container.hasClass( 'open' ) && ! event.isCustomEvent ) {
					dropzone_container.trigger( 'click' );
					return;
				}
			}

			var acCommentDocumentTemplate = document.getElementsByClassName( 'ac-reply-post-document-template' ).length ? document.getElementsByClassName( 'ac-reply-post-document-template' )[0].innerHTML : ''; // Check to avoid error if Node is missing.

			if ( typeof window.Dropzone !== 'undefined' && dropzone_container.length ) {

				if ( dropzone_container.hasClass( 'closed' ) ) {

					var dropzone_options = {
						url                  		: BP_Nouveau.ajaxurl,
						timeout              		: 3 * 60 * 60 * 1000,
						dictFileTooBig       		: BP_Nouveau.media.dictFileTooBig,
						acceptedFiles        		: BP_Nouveau.media.document_type,
						createImageThumbnails		: false,
						dictDefaultMessage   		: BP_Nouveau.media.dropzone_document_message,
						autoProcessQueue     		: true,
						addRemoveLinks       		: true,
						uploadMultiple       		: false,
						maxFiles             		: typeof BP_Nouveau.document.maxFiles !== 'undefined' ? BP_Nouveau.document.maxFiles : 10,
						maxFilesize          		: typeof BP_Nouveau.document.max_upload_size !== 'undefined' ? BP_Nouveau.document.max_upload_size : 2,
						dictInvalidFileType  		: BP_Nouveau.document.dictInvalidFileType,
						dictMaxFilesExceeded 		: BP_Nouveau.media.document_dict_file_exceeded,
						previewTemplate		 		: acCommentDocumentTemplate,
						dictCancelUploadConfirmation: BP_Nouveau.media.dictCancelUploadConfirmation,
					};

					// init dropzone.
					self.dropzone_document_obj = new Dropzone( hasParentModal + '#ac-reply-post-document-uploader-' + target.data( 'ac-id' ), dropzone_options );

					self.dropzone_document_obj.on(
						'addedfile',
						function ( file ) {

							// Set data from edit comment.
							if ( file.document_edit_data ) {
								self.dropzone_document.push( file.document_edit_data );
							}

							var filename 	  = file.upload.filename;
							var fileExtension = filename.substr( ( filename.lastIndexOf( '.' ) + 1 ) );
							$( file.previewElement ).find( '.dz-details .dz-icon .bb-icon-file' ).removeClass( 'bb-icon-file' ).addClass( 'bb-icon-file-' + fileExtension );
							var tool_box    = target.parents( '.ac-reply-toolbar' );
							if ( tool_box.find( '.ac-reply-document-button' ) ) {
								tool_box.find( '.ac-reply-document-button' ).parents( '.post-elements-buttons-item' ).addClass( 'no-click' ).find( '.toolbar-button' ).addClass( 'active' );
							}
						}
					);

					self.dropzone_document_obj.on(
						'sending',
						function(file, xhr, formData) {
							formData.append( 'action', 'document_document_upload' );
							formData.append( '_wpnonce', BP_Nouveau.nonces.media );

							var tool_box    = target.parents( '.ac-reply-toolbar' );
							var commentForm = target.closest( '.ac-form' );
							commentForm.addClass( 'has-media' );
							if ( bp.Nouveau.dropZoneGlobalProgress ) {
								bp.Nouveau.dropZoneGlobalProgress( this );
							}
							if ( tool_box.find( '.ac-reply-media-button' ) ) {
								tool_box.find( '.ac-reply-media-button' ).parents( '.post-elements-buttons-item' ).addClass( 'disable' );
							}
							if ( tool_box.find( '.ac-reply-video-button' ) ) {
								tool_box.find( '.ac-reply-video-button' ).parents( '.post-elements-buttons-item' ).addClass( 'disable' );
							}
							if ( tool_box.find( '.ac-reply-gif-button' ) ) {
								tool_box.find( '.ac-reply-gif-button' ).parents( '.post-elements-buttons-item' ).addClass( 'disable' );
							}
							if ( tool_box.find( '.ac-reply-document-button' ) ) {
								tool_box.find( '.ac-reply-document-button' ).parents( '.post-elements-buttons-item' ).addClass( 'no-click' ).find( '.toolbar-button' ).addClass( 'active' );
							}
							this.element.classList.remove( 'files-uploaded' );
						}
					);

					self.dropzone_document_obj.on(
						'uploadprogress',
						function() {

							var commentForm = target.closest( '.ac-form' );
							commentForm.addClass( 'media-uploading' );

							if ( bp.Nouveau.dropZoneGlobalProgress ) {
								bp.Nouveau.dropZoneGlobalProgress( this );
							}
						}
					);

					self.dropzone_document_obj.on(
						'accept',
						function( file, done ) {
							if (file.size == 0) {
								done( BP_Nouveau.media.empty_document_type );
							} else {
								done();
							}
						}
					);

					self.dropzone_document_obj.on(
						'success',
						function(file, response) {
							if ( response.data.id ) {
								file.id                  = response.id;
								response.data.uuid       = file.upload.uuid;
								response.data.menu_order = $( file.previewElement ).closest( '.dropzone' ).find( file.previewElement ).index() - 1;
								response.data.album_id   = typeof BP_Nouveau.media !== 'undefined' && typeof BP_Nouveau.media.album_id !== 'undefined' ? BP_Nouveau.media.album_id : false;
								response.data.group_id   = typeof BP_Nouveau.media !== 'undefined' && typeof BP_Nouveau.media.group_id !== 'undefined' ? BP_Nouveau.media.group_id : false;
								response.data.saved      = false;
								self.dropzone_document.push( response.data );
								return file.previewElement.classList.add( 'dz-success' );
							} else {
								var node, _i, _len, _ref, _results;
								var message = response.data.feedback;
								file.previewElement.classList.add( 'dz-error' );
								_ref     = file.previewElement.querySelectorAll( '[data-dz-errormessage]' );
								_results = [];
								for ( _i = 0, _len = _ref.length; _i < _len; _i++ ) {
									node = _ref[_i];
									_results.push( node.textContent = message );
								}
								if ( ! _.isNull( self.dropzone_document_obj.files ) && self.dropzone_document_obj.files.length === 0 ) {
									$( self.dropzone_document_obj.element ).removeClass( 'files-uploaded dz-progress-view' ).find( '.dz-global-progress' ).remove();
								}
								return _results;
							}
						}
					);

					self.dropzone_document_obj.on(
						'error',
						function(file,response) {
							if ( file.accepted ) {
								if ( typeof response !== 'undefined' && typeof response.data !== 'undefined' && typeof response.data.feedback !== 'undefined' ) {
									$( file.previewElement ).find( '.dz-error-message span' ).text( response.data.feedback );
								} else if ( file.status == 'error' && ( file.xhr && file.xhr.status == 0 ) ) { // update server error text to user friendly.
									$( file.previewElement ).find( '.dz-error-message span' ).text( BP_Nouveau.media.connection_lost_error );
								}
							} else {
								var commentForm = target.closest( '.ac-form' );
								bp.Nouveau.Activity.clearFeedbackNotice( commentForm );
								commentForm.find( '.ac-reply-content' ).after( '<div class="bp-feedback bp-messages error">' + response + '</div>' );
								this.removeFile( file );
								commentForm.removeClass( 'media-uploading' );
							}
						}
					);

					self.dropzone_document_obj.on(
						'removedfile',
						function(file) {
							if ( self.dropzone_document.length ) {
								for ( var i in self.dropzone_document ) {
									if ( file.upload.uuid == self.dropzone_document[i].uuid ) {

										if ( typeof self.dropzone_document[i].saved !== 'undefined' && ! self.dropzone_document[i].saved ) {
											bp.Nouveau.Media.removeAttachment( self.dropzone_document[i].id );
										}

										self.dropzone_document.splice( i, 1 );
										break;
									}
								}
							}

							if ( ! _.isNull( self.dropzone_document_obj ) && ! _.isNull( self.dropzone_document_obj.files ) && self.dropzone_document_obj.files.length === 0 ) {
								var tool_box    = target.parents( '.ac-reply-toolbar' );
								var commentForm = target.closest( '.ac-form' );
								commentForm.removeClass( 'has-media' );
								if ( tool_box.find( '.ac-reply-media-button' ) ) {
									tool_box.find( '.ac-reply-media-button' ).parents( '.post-elements-buttons-item' ).removeClass( 'disable' );
								}
								if ( tool_box.find( '.ac-reply-video-button' ) ) {
									tool_box.find( '.ac-reply-video-button' ).parents( '.post-elements-buttons-item' ).removeClass( 'disable' );
								}
								if ( tool_box.find( '.ac-reply-gif-button' ) ) {
									tool_box.find( '.ac-reply-gif-button' ).parents( '.post-elements-buttons-item' ).removeClass( 'disable' );
								}
								if ( tool_box.find( '.ac-reply-document-button' ) ) {
									tool_box.find( '.ac-reply-document-button' ).parents( '.post-elements-buttons-item' ).removeClass( 'no-click' ).find( '.toolbar-button' ).removeClass( 'active' );
								}
								$( self.dropzone_document_obj.element ).removeClass( 'files-uploaded dz-progress-view' ).find( '.dz-global-progress' ).remove();
								self.validateCommentContent( commentForm.find( '.ac-textarea' ).children( '.ac-input' ) );
							} else {
								target.closest( '.ac-form' ).addClass( 'has-content' );
							}
						}
					);

					// Enable submit button when all medias are uploaded.
					self.dropzone_document_obj.on(
						'complete',
						function() {
							if ( this.getUploadingFiles().length === 0 && this.getQueuedFiles().length === 0 && this.files.length > 0 ) {
								var commentForm = target.closest( '.ac-form' );
								commentForm.removeClass( 'media-uploading' );
								this.element.classList.add( 'files-uploaded' );
							}
						}
					);

					// container class to open close.
					dropzone_container.removeClass( 'closed' ).addClass( 'open' );

				} else {
					if ( self.dropzone_document_obj ) {
						self.dropzone_document_obj.destroy();
					}
					self.dropzone_document = [];
					dropzone_container.html( '' );
					dropzone_container.addClass( 'closed' ).removeClass( 'open' );
				}
			}

			var currentTarget = event.currentTarget, activityID = currentTarget.id.match( /\d+$/ )[0];
			this.destroyCommentMediaUploader( activityID );
			this.destroyCommentVideoUploader( activityID );

			var c_id = $( event.currentTarget ).data( 'ac-id' );
			this.resetGifPicker( c_id );

			if ( ! event.isCustomEvent ) {
				$( target ).closest( '.bp-ac-form-container' ).find( '.dropzone.document-dropzone' ).trigger( 'click' );
			}
		},

		openCommentsVideoUploader: function(event) {
			var self               = this,
				target             = $( event.currentTarget ),
				key                = target.data( 'ac-id' ),
				dropzone_container = target.closest( '.bp-ac-form-container' ).find( '#ac-reply-post-video-uploader-' + key );

			// Check if target is inside #activity-modal
			var isInsideModal = target.closest( '#activity-modal' ).length > 0;
			var hasParentModal = isInsideModal ? '#activity-modal ' : '';

			event.preventDefault();

			if ( ! $( event.currentTarget ).closest( '.ac-form' ).hasClass( 'acomment-edit' ) ) {
				$( event.currentTarget ).toggleClass( 'active' );
			} else {
				if ( dropzone_container.hasClass( 'open' ) && ! event.isCustomEvent ) {
					dropzone_container.trigger( 'click' );
					return;
				}
			}

			var acCommentVideoTemplate = document.getElementsByClassName( 'ac-reply-post-video-template' ).length ? document.getElementsByClassName( 'ac-reply-post-video-template' )[0].innerHTML : ''; // Check to avoid error if Node is missing.

			if ( typeof window.Dropzone !== 'undefined' && dropzone_container.length ) {

				if ( dropzone_container.hasClass( 'closed' ) ) {

					var dropzone_options = {
						url                  		: BP_Nouveau.ajaxurl,
						timeout              		: 3 * 60 * 60 * 1000,
						dictFileTooBig       		: BP_Nouveau.video.dictFileTooBig,
						acceptedFiles        		: BP_Nouveau.video.video_type,
						createImageThumbnails		: false,
						dictDefaultMessage   		: BP_Nouveau.video.dropzone_video_message,
						autoProcessQueue     		: true,
						addRemoveLinks       		: true,
						uploadMultiple       		: false,
						maxFiles             		: typeof BP_Nouveau.video.maxFiles !== 'undefined' ? BP_Nouveau.video.maxFiles : 10,
						maxFilesize          		: typeof BP_Nouveau.video.max_upload_size !== 'undefined' ? BP_Nouveau.video.max_upload_size : 2,
						dictInvalidFileType  		: BP_Nouveau.video.dictInvalidFileType,
						dictMaxFilesExceeded 		: BP_Nouveau.video.video_dict_file_exceeded,
						previewTemplate		 		: acCommentVideoTemplate,
						dictCancelUploadConfirmation: BP_Nouveau.video.dictCancelUploadConfirmation,
					};

					// init dropzone.
					self.dropzone_video_obj = new Dropzone( hasParentModal + '#ac-reply-post-video-uploader-' + target.data( 'ac-id' ), dropzone_options );

					self.dropzone_video_obj.on(
						'sending',
						function(file, xhr, formData) {
							formData.append( 'action', 'video_upload' );
							formData.append( '_wpnonce', BP_Nouveau.nonces.video );

							var tool_box    = target.parents( '.ac-reply-toolbar' );
							var commentForm = target.closest( '.ac-form' );
							commentForm.addClass( 'has-media' );
							if ( bp.Nouveau.dropZoneGlobalProgress ) {
								bp.Nouveau.dropZoneGlobalProgress( this );
							}
							if ( tool_box.find( '.ac-reply-media-button' ) ) {
								tool_box.find( '.ac-reply-media-button' ).parents( '.post-elements-buttons-item' ).addClass( 'disable' );
							}
							if ( tool_box.find( '.ac-reply-document-button' ) ) {
								tool_box.find( '.ac-reply-document-button' ).parents( '.post-elements-buttons-item' ).addClass( 'disable' );
							}
							if ( tool_box.find( '.ac-reply-gif-button' ) ) {
								tool_box.find( '.ac-reply-gif-button' ).parents( '.post-elements-buttons-item' ).addClass( 'disable' );
							}
							if ( tool_box.find( '.ac-reply-video-button' ) ) {
								tool_box.find( '.ac-reply-video-button' ).parents( '.post-elements-buttons-item' ).addClass( 'no-click' ).find( '.toolbar-button' ).addClass( 'active' );
							}
							this.element.classList.remove( 'files-uploaded' );
						}
					);

					self.dropzone_video_obj.on(
						'accept',
						function( file, done ) {
							if (file.size == 0) {
								done( BP_Nouveau.video.empty_video_type );
							} else {
								done();
							}
						}
					);

					self.dropzone_video_obj.on(
						'addedfile',
						function ( file ) {

							// Set data from edit comment.
							if ( file.video_edit_data ) {
								self.dropzone_video.push( file.video_edit_data );
								var tool_box    = target.parents( '.ac-reply-toolbar' );
								if ( tool_box.find( '.ac-reply-video-button' ) ) {
									tool_box.find( '.ac-reply-video-button' ).parents( '.post-elements-buttons-item' ).addClass( 'no-click' ).find( '.toolbar-button' ).addClass( 'active' );
								}
							}

							if ( file.dataURL && file.video_edit_data.thumb.length ) {
								// Get Thumbnail image from response.
								$( file.previewElement ).find( '.dz-video-thumbnail' ).prepend( '<img src=" ' + file.video_edit_data.thumb + ' " />' );
								$( file.previewElement ).closest( '.dz-preview' ).addClass( 'dz-has-thumbnail' );
							} else {

								if ( bp.Nouveau.getVideoThumb ) {
									bp.Nouveau.getVideoThumb( file, '.dz-video-thumbnail' );
								}

							}
						}
					);

					self.dropzone_video_obj.on(
						'uploadprogress',
						function() {

							var commentForm = target.closest( '.ac-form' );
							commentForm.addClass( 'media-uploading' );

							if ( bp.Nouveau.dropZoneGlobalProgress ) {
								bp.Nouveau.dropZoneGlobalProgress( this );
							}
						}
					);

					self.dropzone_video_obj.on(
						'success',
						function(file, response) {

							if ( file.upload.progress === 100 ) {
								$( file.previewElement ).find( '.dz-progress-ring circle' )[0].style.strokeDashoffset = 0;
								$( file.previewElement ).find( '.dz-progress-count' ).text( '100% ' + BP_Nouveau.video.i18n_strings.video_uploaded_text );
								$( file.previewElement ).closest( '.dz-preview' ).addClass( 'dz-complete' );
							}

							if ( response.data.id ) {
								file.id                  = response.id;
								response.data.uuid       = file.upload.uuid;
								response.data.menu_order = $( file.previewElement ).closest( '.dropzone' ).find( file.previewElement ).index() - 1;
								response.data.album_id   = typeof BP_Nouveau.video !== 'undefined' && typeof BP_Nouveau.video.album_id !== 'undefined' ? BP_Nouveau.video.album_id : false;
								response.data.group_id   = typeof BP_Nouveau.video !== 'undefined' && typeof BP_Nouveau.video.group_id !== 'undefined' ? BP_Nouveau.video.group_id : false;
								response.data.saved      = false;
								response.data.js_preview = $( file.previewElement ).find( '.dz-video-thumbnail img' ).attr( 'src' );
								self.dropzone_video.push( response.data );
								return file.previewElement.classList.add( 'dz-success' );
							} else {
								var node, _i, _len, _ref, _results;
								var message = response.data.feedback;
								file.previewElement.classList.add( 'dz-error' );
								_ref     = file.previewElement.querySelectorAll( '[data-dz-errormessage]' );
								_results = [];
								for ( _i = 0, _len = _ref.length; _i < _len; _i++ ) {
									node = _ref[_i];
									_results.push( node.textContent = message );
								}
								if ( ! _.isNull( self.dropzone_video_obj.files ) && self.dropzone_video_obj.files.length === 0 ) {
									$( self.dropzone_video_obj.element ).removeClass( 'files-uploaded dz-progress-view' ).find( '.dz-global-progress' ).remove();
								}
								return _results;
							}
						}
					);

					self.dropzone_video_obj.on(
						'error',
						function(file,response) {
							if ( file.accepted ) {
								if ( typeof response !== 'undefined' && typeof response.data !== 'undefined' && typeof response.data.feedback !== 'undefined' ) {
									$( file.previewElement ).find( '.dz-error-message span' ).text( response.data.feedback );
								} else if ( file.status == 'error' && ( file.xhr && file.xhr.status == 0 ) ) { // update server error text to user friendly.
									$( file.previewElement ).find( '.dz-error-message span' ).text( BP_Nouveau.media.connection_lost_error );
								}
							} else {
								var commentForm = target.closest( '.ac-form' );
								bp.Nouveau.Activity.clearFeedbackNotice( commentForm );
								commentForm.find( '.ac-reply-content' ).after( '<div class="bp-feedback bp-messages error">' + response + '</div>' );
								this.removeFile( file );
								commentForm.removeClass( 'media-uploading' );
							}
						}
					);

					self.dropzone_video_obj.on(
						'removedfile',
						function(file) {
							if ( self.dropzone_video.length ) {
								for ( var i in self.dropzone_video ) {
									if ( file.upload.uuid == self.dropzone_video[i].uuid ) {

										if ( typeof self.dropzone_video[i].saved !== 'undefined' && ! self.dropzone_video[i].saved ) {
											bp.Nouveau.Media.removeAttachment( self.dropzone_video[i].id );
										}

										self.dropzone_video.splice( i, 1 );
										break;
									}
								}
							}

							if ( ! _.isNull( self.dropzone_video_obj ) && ! _.isNull( self.dropzone_video_obj.files ) && self.dropzone_video_obj.files.length === 0 ) {
								var tool_box    = target.parents( '.ac-reply-toolbar' );
								var commentForm = target.closest( '.ac-form' );
								commentForm.removeClass( 'has-media' );

								if ( tool_box.find( '.ac-reply-media-button' ) ) {
									tool_box.find( '.ac-reply-media-button' ).parents( '.post-elements-buttons-item' ).removeClass( 'disable' );
								}
								if ( tool_box.find( '.ac-reply-document-button' ) ) {
									tool_box.find( '.ac-reply-document-button' ).parents( '.post-elements-buttons-item' ).removeClass( 'disable' );
								}
								if ( tool_box.find( '.ac-reply-gif-button' ) ) {
									tool_box.find( '.ac-reply-gif-button' ).parents( '.post-elements-buttons-item' ).removeClass( 'disable' );
								}
								if ( tool_box.find( '.ac-reply-video-button' ) ) {
									tool_box.find( '.ac-reply-video-button' ).parents( '.post-elements-buttons-item' ).removeClass( 'no-click' ).find( '.toolbar-button' ).removeClass( 'active' );
								}
								$( self.dropzone_video_obj.element ).removeClass( 'files-uploaded dz-progress-view' ).find( '.dz-global-progress' ).remove();
								self.validateCommentContent( commentForm.find( '.ac-textarea' ).children( '.ac-input' ) );
							} else {
								target.closest( '.ac-form' ).addClass( 'has-content' );
							}
						}
					);

					// Enable submit button when all medias are uploaded.
					self.dropzone_video_obj.on(
						'complete',
						function() {
							if ( this.getUploadingFiles().length === 0 && this.getQueuedFiles().length === 0 && this.files.length > 0 ) {
								var commentForm = target.closest( '.ac-form' );
								commentForm.removeClass( 'media-uploading' );
								this.element.classList.add( 'files-uploaded' );
							}
						}
					);

					// container class to open close.
					dropzone_container.removeClass( 'closed' ).addClass( 'open' );

				} else {
					if ( self.dropzone_video_obj ) {
						self.dropzone_video_obj.destroy();
					}
					self.dropzone_video = [];
					dropzone_container.html( '' );
					dropzone_container.addClass( 'closed' ).removeClass( 'open' );
				}
			}

			var currentTarget = event.currentTarget, activityID = currentTarget.id.match( /\d+$/ )[0];
			this.destroyCommentMediaUploader( activityID );
			this.destroyCommentDocumentUploader( activityID );

			var c_id = $( event.currentTarget ).data( 'ac-id' );
			this.resetGifPicker( c_id );

			if ( ! event.isCustomEvent ) {
				$( target ).closest( '.bp-ac-form-container' ).find( '.dropzone.video-dropzone' ).trigger( 'click' );
			}
		},

		openGifPicker: function ( event ) {
			event.preventDefault();

			var currentTarget = event.currentTarget,
				isInsideModal = $( currentTarget ).closest( '#activity-modal' ).length > 0,
				hasParentModal = isInsideModal ? '#activity-modal ' : '',
				pickerContainer = isInsideModal ? $( '.gif-media-search-dropdown-standalone' ) : $( currentTarget ).next(),
				isStandalone = isInsideModal ? true : false,
				$gifPickerEl = pickerContainer,
				activityID = currentTarget.id.match( /\d+$/ )[ 0 ],
				$gifAttachmentEl = $( hasParentModal + '#ac-reply-post-gif-' + activityID );

			var scrollTop    = $( window ).scrollTop(),
				offset       = $( currentTarget ).offset(),
				topPosition  = Math.round( offset.top ),
				leftPosition = Math.round( offset.left );

			if ( $gifPickerEl.is( ':empty' ) ) {
				var model = new bp.Models.ACReply(),
					gifMediaSearchDropdownView = new bp.Views.GifMediaSearchDropdown( { model: model, standalone: isStandalone } ),
					activityAttachedGifPreview = new bp.Views.ActivityAttachedGifPreview( { model: model, standalone: isStandalone } );

				$gifPickerEl.html( gifMediaSearchDropdownView.render().el );
				$gifAttachmentEl.html( activityAttachedGifPreview.render().el );

				this.models[ activityID ] = model;
			}

			var gif_box = $( currentTarget ).parents( '.ac-textarea ' ).find( '.ac-reply-attachments .activity-attached-gif-container' );
			if ( $( currentTarget ).hasClass( 'active' ) && gif_box.length && $.trim( gif_box.html() ) == '' ) {
				$( currentTarget ).removeClass( 'active' );
			} else {
				$( currentTarget ).addClass( 'active' );
			}

			$gifPickerEl.toggleClass( 'open' );
			var pickerLeftPosition = leftPosition + $gifPickerEl.width() - 70;
			var commentLevel       = $( currentTarget ).parents( 'li' ).length;

			if ( commentLevel > 2 ) {
				pickerLeftPosition = leftPosition + $gifPickerEl.width() - 110;
			}

			if ( $( currentTarget ).closest( '.post-elements-buttons-item' ).index() > 1 ) {
				pickerLeftPosition = leftPosition + $gifPickerEl.width() - 180;
			}
			var transformValue = 'translate(' + ( pickerLeftPosition ) + 'px, ' + ( topPosition - scrollTop - 5 ) + 'px)  translate(-100%, -100%)';
			if ( isInsideModal ) {
				$gifPickerEl.css( 'transform', transformValue );
			}
			this.destroyCommentMediaUploader( activityID );
			this.destroyCommentDocumentUploader( activityID );
			this.destroyCommentVideoUploader( activityID );
		},

		toggleMultiMediaOptions: function( form, target, placeholder ) {
			if ( ! _.isUndefined( BP_Nouveau.media ) ) {

				var parent_activity = '',
					activity_data = '';

				if ( placeholder ) {
					target = target ? $( target ) : $( placeholder );
					parent_activity = target.closest( '.activity-modal' ).find( '.activity-item' );
					activity_data = target.closest( '.activity-modal' ).find( '.activity-item' ).data( 'bp-activity' );
					form = $( placeholder );
				} else {
					parent_activity = target.closest( '.activity-item' );
					activity_data = target.closest( '.activity-item' ).data( 'bp-activity' );
				}

				if ( target.closest( 'li' ).data( 'bp-activity-comment' ) ) {
					activity_data = target.closest( 'li' ).data( 'bp-activity-comment' );
				}

				if ( target.closest( 'li' ).hasClass( 'groups' ) || parent_activity.hasClass( 'groups' ) ) {

					// check media is enabled in groups or not.
					if ( ! _.isUndefined( activity_data.group_media ) ) {
						if ( activity_data.group_media === true ) {
							form.find( '.ac-reply-toolbar .post-media.media-support' ).show().parent( '.ac-reply-toolbar' ).removeClass( 'post-media-disabled' );
						} else {
							form.find( '.ac-reply-toolbar .post-media.media-support' ).hide().parent( '.ac-reply-toolbar' ).addClass( 'post-media-disabled' );
						}
					} else if ( BP_Nouveau.media.group_media === false ) {
						form.find( '.ac-reply-toolbar .post-media.media-support' ).hide().parent( '.ac-reply-toolbar' ).addClass( 'post-media-disabled' );
					} else {
						form.find( '.ac-reply-toolbar .post-media.media-support' ).show().parent( '.ac-reply-toolbar' ).removeClass( 'post-media-disabled' );
					}

					// check media is enabled in groups or not.
					if ( ! _.isUndefined( activity_data.group_document ) ) {
						if ( activity_data.group_document === true ) {
							form.find( '.ac-reply-toolbar .post-media.document-support' ).show().parent( '.ac-reply-toolbar' ).removeClass( 'post-media-disabled' );
						} else {
							form.find( '.ac-reply-toolbar .post-media.document-support' ).hide().parent( '.ac-reply-toolbar' ).addClass( 'post-media-disabled' );
						}
					} else if ( BP_Nouveau.media.group_document === false ) {
						form.find( '.ac-reply-toolbar .post-media.document-support' ).hide().parent( '.ac-reply-toolbar' ).addClass( 'post-media-disabled' );
					} else {
						form.find( '.ac-reply-toolbar .post-media.document-support' ).show().parent( '.ac-reply-toolbar' ).removeClass( 'post-media-disabled' );
					}

					// check video is enabled in groups or not.
					if ( ! _.isUndefined( activity_data.group_video ) ) {
						if ( activity_data.group_video === true ) {
							form.find( '.ac-reply-toolbar .post-video.video-support' ).show().parent( '.ac-reply-toolbar' ).removeClass( 'post-video-disabled' );
						} else {
							form.find( '.ac-reply-toolbar .post-video.video-support' ).hide().parent( '.ac-reply-toolbar' ).addClass( 'post-video-disabled' );
						}
					} else if ( BP_Nouveau.media.group_video === false ) {
						form.find( '.ac-reply-toolbar .post-video.video-support' ).hide().parent( '.ac-reply-toolbar' ).addClass( 'post-video-disabled' );
					} else {
						form.find( '.ac-reply-toolbar .post-video.video-support' ).show().parent( '.ac-reply-toolbar' ).removeClass( 'post-video-disabled' );
					}

					// check gif is enabled in groups or not.
					if ( BP_Nouveau.media.gif.groups === false ) {
						form.find( '.ac-reply-toolbar .post-gif' ).hide().parent( '.ac-reply-toolbar' ).addClass( 'post-gif-disabled' );
					} else {
						form.find( '.ac-reply-toolbar .post-gif' ).show().parent( '.ac-reply-toolbar' ).removeClass( 'post-gif-disabled' );
					}

					// check emoji is enabled in groups or not.
					if ( BP_Nouveau.media.emoji.groups === false ) {
						form.find( '.ac-reply-toolbar .post-emoji' ).hide().parent( '.ac-reply-toolbar' ).addClass( 'post-emoji-disabled' );
					} else {
						form.find( '.ac-reply-toolbar .post-emoji' ).show().parent( '.ac-reply-toolbar' ).removeClass( 'post-emoji-disabled' );
					}
				} else {

					// check media is enabled in groups or not.
					if ( ! _.isUndefined( activity_data ) && ! _.isNull( activity_data ) && ! _.isUndefined( activity_data.profile_media ) ) {
						if ( activity_data.profile_media === true ) {
							form.find( '.ac-reply-toolbar .post-media.media-support' ).show().parent( '.ac-reply-toolbar' ).removeClass( 'post-media-disabled' );
						} else {
							form.find( '.ac-reply-toolbar .post-media.media-support' ).hide().parent( '.ac-reply-toolbar' ).addClass( 'post-media-disabled' );
						}
					} else if ( BP_Nouveau.media.profile_media === false ) {
						form.find( '.ac-reply-toolbar .post-media.media-support' ).hide().parent( '.ac-reply-toolbar' ).addClass( 'post-media-disabled' );
					} else {
						form.find( '.ac-reply-toolbar .post-media.media-support' ).show().parent( '.ac-reply-toolbar' ).removeClass( 'post-media-disabled' );
					}

					// check document is enabled in groups or not.
					if ( ! _.isUndefined( activity_data ) && ! _.isNull( activity_data ) && ! _.isUndefined( activity_data.profile_document ) ) {
						if ( activity_data.profile_document === true ) {
							form.find( '.ac-reply-toolbar .post-media.document-support' ).show().parent( '.ac-reply-toolbar' ).removeClass( 'post-media-disabled' );
						} else {
							form.find( '.ac-reply-toolbar .post-media.document-support' ).hide().parent( '.ac-reply-toolbar' ).addClass( 'post-media-disabled' );
						}
					} else if ( BP_Nouveau.media.profile_document === false ) {
						form.find( '.ac-reply-toolbar .post-media.document-support' ).hide().parent( '.ac-reply-toolbar' ).addClass( 'post-media-disabled' );
					} else {
						form.find( '.ac-reply-toolbar .post-media.document-support' ).show().parent( '.ac-reply-toolbar' ).removeClass( 'post-media-disabled' );
					}

					// check video is enabled in profile or not.
					if ( ! _.isUndefined( activity_data ) && ! _.isNull( activity_data ) && ! _.isUndefined( activity_data.profile_video ) ) {
						if ( activity_data.profile_video === true ) {
							form.find( '.ac-reply-toolbar .post-video.video-support' ).show().parent( '.ac-reply-toolbar' ).removeClass( 'post-video-disabled' );
						} else {
							form.find( '.ac-reply-toolbar .post-video.video-support' ).hide().parent( '.ac-reply-toolbar' ).addClass( 'post-video-disabled' );
						}
					} else if ( BP_Nouveau.media.profile_video === false ) {
						form.find( '.ac-reply-toolbar .post-video.video-support' ).hide().parent( '.ac-reply-toolbar' ).addClass( 'post-video-disabled' );
					} else {
						form.find( '.ac-reply-toolbar .post-video.video-support' ).show().parent( '.ac-reply-toolbar' ).removeClass( 'post-video-disabled' );
					}

					// check gif is enabled sin groups or not.
					if ( BP_Nouveau.media.gif.profile === false ) {
						form.find( '.ac-reply-toolbar .post-gif' ).hide().parent( '.ac-reply-toolbar' ).addClass( 'post-gif-disabled' );
					} else {
						form.find( '.ac-reply-toolbar .post-gif' ).show().parent( '.ac-reply-toolbar' ).removeClass( 'post-gif-disabled' );
					}

					// check emoji is enabled in groups or not.
					if ( BP_Nouveau.media.emoji.profile === false ) {
						form.find( '.ac-reply-toolbar .post-emoji' ).hide().parent( '.ac-reply-toolbar' ).addClass( 'post-emoji-disabled' );
					} else {
						form.find( '.ac-reply-toolbar .post-emoji' ).show().parent( '.ac-reply-toolbar' ).removeClass( 'post-emoji-disabled' );
					}
				}
			}
		},

		fixAtWhoActivity: function() {
			$( '.acomment-content, .activity-content' ).each(
				function(){
					// replacing atwho query from the comment content to disable querying it in the requests.
					var atwho_query = $( this ).find( 'span.atwho-query' );
					for ( var i = 0; i < atwho_query.length; i++ ) {
						$( atwho_query[i] ).replaceWith( atwho_query[i].innerText );
					}
				}
			);
		},

		navigateToSpecificComment: function () {

			setTimeout(
				function () {

					if ( window.location.hash ) {

						var id       = window.location.hash;
						var adminBar = $( '#wpadminbar' ).length !== 0 ? $( '#wpadminbar' ).innerHeight() : 0;
						if ( $( id ).length > 0 ) {
							$( 'html, body' ).animate( { scrollTop: parseInt( $( id ).offset().top ) - (80 + adminBar) }, 0 );
						}
					}

				},
				200
			);
		},

		createThumbnailFromUrl: function ( mock_file ) {
			var self = this;
			self.dropzone_obj.createThumbnailFromUrl(
				mock_file,
				self.dropzone_obj.options.thumbnailWidth,
				self.dropzone_obj.options.thumbnailHeight,
				self.dropzone_obj.options.thumbnailMethod,
				true,
				function ( thumbnail ) {
					self.dropzone_obj.emit( 'thumbnail', mock_file, thumbnail );
					self.dropzone_obj.emit( 'complete', mock_file );
				}
			);
		},

		editActivityCommentForm: function ( form, activity_comment_data ) {
			var form_activity_id = form.find( 'input[name="comment_form_id"]' ).val(),
				toolbar_div      = form.find( '#ac-reply-toolbar-' + form_activity_id ),
				form_submit_btn  = form.find( 'input[name="ac_form_submit"]' ),
				self 			 = this;

			form.find( '#ac-input-' + form_activity_id ).html( activity_comment_data.content );

			var form_submit_btn_attr_val = form_submit_btn.attr( 'data-add-edit-label' );
			form_submit_btn.attr( 'data-add-edit-label', form_submit_btn.val() ).val( form_submit_btn_attr_val );

			// Inject medias.
			if (
				'undefined' !== typeof activity_comment_data.media &&
				0 < activity_comment_data.media.length
			) {
				toolbar_div.find( '.ac-reply-media-button' ).trigger( { type: 'click', isCustomEvent: true } );
				self.disabledCommentDocumentUploader( toolbar_div );
				self.disabledCommentVideoUploader( toolbar_div );
				self.disabledCommentGifPicker( toolbar_div );

				var mock_file    = false,
					media_length = activity_comment_data.media.length;

				for ( var i = 0; i < media_length; i++ ) {
					mock_file = false;

					var media_edit_data = {};
					if ( 0 < parseInt( activity_comment_data.id ) ) {
						media_edit_data = {
							'id': activity_comment_data.media[i].attachment_id,
							'media_id': activity_comment_data.media[i].id,
							'name': activity_comment_data.media[i].name,
							'thumb': activity_comment_data.media[i].thumb,
							'url': activity_comment_data.media[i].url,
							'uuid': activity_comment_data.media[i].attachment_id,
							'menu_order': activity_comment_data.media[i].menu_order,
							'album_id': activity_comment_data.media[i].album_id,
							'group_id': activity_comment_data.media[i].group_id,
							'saved': true
						};
					} else {
						media_edit_data = {
							'id': activity_comment_data.media[i].id,
							'name': activity_comment_data.media[i].name,
							'thumb': activity_comment_data.media[i].thumb,
							'url': activity_comment_data.media[i].url,
							'uuid': activity_comment_data.media[i].id,
							'menu_order': activity_comment_data.media[i].menu_order,
							'album_id': activity_comment_data.media[i].album_id,
							'group_id': activity_comment_data.media[i].group_id,
							'saved': false
						};
					}

					mock_file = {
						name: activity_comment_data.media[i].name,
						accepted: true,
						kind: 'image',
						upload: {
							filename: activity_comment_data.media[i].name,
							uuid: activity_comment_data.media[i].attachment_id
						},
						dataURL: activity_comment_data.media[i].url,
						id: activity_comment_data.media[i].attachment_id,
						media_edit_data: media_edit_data
					};

					if ( self.dropzone_obj ) {
						self.dropzone_obj.files.push( mock_file );
						self.dropzone_obj.emit( 'addedfile', mock_file );

						if ( undefined !== typeof BP_Nouveau.is_as3cf_active && '1' === BP_Nouveau.is_as3cf_active ) {
							$( self.dropzone_obj.files[i].previewElement ).find( 'img' ).attr( 'src', activity_comment_data.media[i].thumb );
							self.dropzone_obj.emit( 'thumbnail', activity_comment_data.media[i].thumb );
							self.dropzone_obj.emit( 'complete', mock_file );
						} else {
							self.createThumbnailFromUrl( mock_file );
						}

						self.dropzone_obj.emit( 'dz-success' );
						self.dropzone_obj.emit( 'dz-complete' );
					}
				}
			}

			// Inject Documents.
			if (
				'undefined' !== typeof activity_comment_data.document &&
				0 < activity_comment_data.document.length
			) {
				toolbar_div.find( '.ac-reply-document-button' ).trigger( { type: 'click', isCustomEvent: true } );
				self.disabledCommentMediaUploader( toolbar_div );
				self.disabledCommentVideoUploader( toolbar_div );
				self.disabledCommentGifPicker( toolbar_div );

				var doc_file   = false,
					doc_length = activity_comment_data.document.length;

				for ( var doci = 0; doci < doc_length; doci++ ) {
					doc_file = false;

					var document_edit_data = {};
					if ( 0 < parseInt( activity_comment_data.id ) ) {
						document_edit_data = {
							'id': activity_comment_data.document[ doci ].doc_id,
							'name': activity_comment_data.document[ doci ].full_name,
							'full_name': activity_comment_data.document[ doci ].full_name,
							'type': 'document',
							'url': activity_comment_data.document[ doci ].url,
							'size': activity_comment_data.document[ doci ].size,
							'uuid': activity_comment_data.document[ doci ].doc_id,
							'document_id': activity_comment_data.document[ doci ].id,
							'menu_order': activity_comment_data.document[ doci ].menu_order,
							'folder_id': activity_comment_data.document[ doci ].folder_id,
							'group_id': activity_comment_data.document[ doci ].group_id,
							'saved': true,
							'svg_icon': ! _.isUndefined( activity_comment_data.document[ doci ].svg_icon ) ? activity_comment_data.document[ doci ].svg_icon : ''
						};
					} else {
						document_edit_data = {
							'id': activity_comment_data.document[ doci ].id,
							'name': activity_comment_data.document[ doci ].full_name,
							'full_name': activity_comment_data.document[ doci ].full_name,
							'type': 'document',
							'url': activity_comment_data.document[ doci ].url,
							'size': activity_comment_data.document[ doci ].size,
							'uuid': activity_comment_data.document[ doci ].id,
							'menu_order': activity_comment_data.document[ doci ].menu_order,
							'folder_id': activity_comment_data.document[ doci ].folder_id,
							'group_id': activity_comment_data.document[ doci ].group_id,
							'saved': false,
							'svg_icon': ! _.isUndefined( activity_comment_data.document[ doci ].svg_icon ) ? activity_comment_data.document[ doci ].svg_icon : ''
						};
					}

					doc_file = {
						name: activity_comment_data.document[ doci ].full_name,
						size: activity_comment_data.document[ doci ].size,
						accepted: true,
						kind: 'file',
						upload: {
							filename: activity_comment_data.document[ doci ].full_name,
							uuid: activity_comment_data.document[ doci ].doc_id
						},
						dataURL: activity_comment_data.document[ doci ].url,
						id: activity_comment_data.document[ doci ].doc_id,
						document_edit_data: document_edit_data,
						svg_icon: ! _.isUndefined( activity_comment_data.document[ doci ].svg_icon ) ? activity_comment_data.document[ doci ].svg_icon : ''
					};

					if ( self.dropzone_document_obj ) {
						self.dropzone_document_obj.files.push( doc_file );
						self.dropzone_document_obj.emit( 'addedfile', doc_file );
						self.dropzone_document_obj.emit( 'complete', doc_file );
					}
				}
			}

			// Inject Videos.
			if (
				'undefined' !== typeof activity_comment_data.video &&
				0 < activity_comment_data.video.length
			) {
				toolbar_div.find( '.ac-reply-video-button' ).trigger( { type: 'click', isCustomEvent: true } );
				self.disabledCommentMediaUploader( toolbar_div );
				self.disabledCommentDocumentUploader( toolbar_div );
				self.disabledCommentGifPicker( toolbar_div );

				var video_file   = false,
					video_length = activity_comment_data.video.length;

				for ( var vidi = 0; vidi < video_length; vidi++ ) {
					video_file = false;

					var video_edit_data = {};
					if ( 0 < parseInt( activity_comment_data.id ) ) {
						video_edit_data = {
							'id': activity_comment_data.video[ vidi ].vid_id,
							'name': activity_comment_data.video[ vidi ].name,
							'type': 'video',
							'thumb': activity_comment_data.video[ vidi ].thumb,
							'url': activity_comment_data.video[ vidi ].url,
							'size': activity_comment_data.video[ vidi ].size,
							'uuid': activity_comment_data.video[ vidi ].vid_id,
							'video_id': activity_comment_data.video[ vidi ].id,
							'menu_order': activity_comment_data.video[ vidi ].menu_order,
							'album_id': activity_comment_data.video[ vidi ].album_id,
							'group_id': activity_comment_data.video[ vidi ].group_id,
							'saved': true
						};
					} else {
						video_edit_data = {
							'id': activity_comment_data.video[ vidi ].id,
							'name': activity_comment_data.video[ vidi ].name,
							'type': 'video',
							'thumb': activity_comment_data.video[ vidi ].thumb,
							'url': activity_comment_data.video[ vidi ].url,
							'size': activity_comment_data.video[ vidi ].size,
							'uuid': activity_comment_data.video[ vidi ].id,
							'menu_order': activity_comment_data.video[ vidi ].menu_order,
							'album_id': activity_comment_data.video[ vidi ].album_id,
							'group_id': activity_comment_data.video[ vidi ].group_id,
							'saved': false,
						};
					}

					video_file = {
						name: activity_comment_data.video[ vidi ].name,
						size: activity_comment_data.video[ vidi ].size,
						accepted: true,
						kind: 'file',
						upload: {
							filename: activity_comment_data.video[ vidi ].name,
							uuid: activity_comment_data.video[ vidi ].vid_id
						},
						dataURL: activity_comment_data.video[ vidi ].url,
						id: activity_comment_data.video[ vidi ].vid_id,
						video_edit_data: video_edit_data
					};
					console.log( video_file );
					console.log( self.dropzone_video_obj );

					if ( self.dropzone_video_obj ) {
						self.dropzone_video_obj.files.push( video_file );
						self.dropzone_video_obj.emit( 'addedfile', video_file );
						self.dropzone_video_obj.emit( 'complete', video_file );
					}
				}
			}

			// Inject GIF.
			if (
				'undefined' !== typeof activity_comment_data.gif &&
				0 < Object.keys( activity_comment_data.gif ).length
			) {
				var $gifPickerEl     = toolbar_div.find( '.ac-reply-gif-button' ).next(),
					isInsideModal 	 = form.closest( '#activity-modal' ).length > 0,
					hasParentModal 	 = isInsideModal ? '#activity-modal ' : '',
					$gifAttachmentEl = $( hasParentModal + '#ac-reply-post-gif-' + form_activity_id );

				toolbar_div.find( '.ac-reply-gif-button' ).trigger( 'click' );
				self.disabledCommentMediaUploader( toolbar_div );
				self.disabledCommentDocumentUploader( toolbar_div );
				self.disabledCommentVideoUploader( toolbar_div );

				var model                      = new bp.Models.ACReply(),
					gifMediaSearchDropdownView = new bp.Views.GifMediaSearchDropdown( {model: model} ),
					activityAttachedGifPreview = new bp.Views.ActivityAttachedGifPreview( {model: model} );

				gifMediaSearchDropdownView.model.set( 'gif_data', activity_comment_data.gif );
				$gifPickerEl.html( gifMediaSearchDropdownView.render().el );
				$gifAttachmentEl.html( activityAttachedGifPreview.render().el );

				this.models[form_activity_id] = model;
			}

			if (
				'undefined' === typeof activity_comment_data.media &&
				'undefined' === typeof activity_comment_data.document &&
				'undefined' === typeof activity_comment_data.video &&
				'undefined' === typeof activity_comment_data.gif
			) {
				form.find( '.ac-reply-toolbar .post-elements-buttons-item' ).removeClass( 'disable' );
			}

		},

		resetActivityCommentForm: function ( form, resetType ) {
			resetType = typeof resetType !== 'undefined' ? resetType : '';

			// Form is not edit activity comment form and not hardReset, then return.
			if ( ! form.hasClass( 'acomment-edit' ) && 'hardReset' !== resetType ) {
				return;
			}

			var form_activity_id = form.find( 'input[name="comment_form_id"]' ).val(),
				form_item_id     = form.attr( 'data-item-id' ),
				form_acomment    = $( '[data-bp-activity-comment-id="' + form_item_id + '"]' ),
				form_submit_btn  = form.find( 'input[name="ac_form_submit"]' );

			form_acomment.find( '#acomment-display-' + form_item_id ).removeClass( 'bp-hide' );
			form.removeClass( 'acomment-edit' ).removeAttr( 'data-item-id' );

			var form_submit_btn_attr_val = form_submit_btn.attr( 'data-add-edit-label' );
			form_submit_btn.attr( 'data-add-edit-label', form_submit_btn.val() ).val( form_submit_btn_attr_val );

			form.find( '.post-elements-buttons-item' ).removeClass( 'disable' );
			form.find( '.post-elements-buttons-item .toolbar-button' ).removeClass( 'active' );

			form.find( '#ac-input-' + form_activity_id ).html( '' );
			form.removeClass( 'has-content has-gif has-media' );
			this.destroyCommentMediaUploader( form_activity_id );
			this.destroyCommentDocumentUploader( form_activity_id );
			this.destroyCommentVideoUploader( form_activity_id );
			this.resetGifPicker( form_activity_id );
		},

		// Reinitialize reply/edit comment form and append in activity modal footer
		reinitializeActivityCommentForm: function ( form ) {

			var form_activity_id = form.find( 'input[name="comment_form_id"]' ).val(),
				form_submit_btn  = form.find( 'input[name="ac_form_submit"]' );

			if ( form.hasClass( 'acomment-edit' ) ) {
				var form_item_id = form.attr( 'data-item-id' );
				var form_acomment = $( '[data-bp-activity-comment-id="' + form_item_id + '"]' );

				form_acomment.find( '#acomment-display-' + form_item_id ).removeClass( 'bp-hide' );
				form.removeClass( 'acomment-edit' ).removeAttr( 'data-item-id' );
			}

			var form_submit_btn_attr_val = form_submit_btn.attr( 'data-add-edit-label' );
			form_submit_btn.attr( 'data-add-edit-label', form_submit_btn.val() ).val( form_submit_btn_attr_val );

			form.find( '#ac-input-' + form_activity_id ).html( '' );
			form.removeClass( 'has-content has-gif has-media' );
			$( '.bb-modal-activity-footer' ).addClass( 'active' ).append( form );
			this.destroyCommentMediaUploader( form_activity_id );
			this.destroyCommentDocumentUploader( form_activity_id );
			this.destroyCommentVideoUploader( form_activity_id );
			this.resetGifPicker( form_activity_id );
		},

		disabledCommentMediaUploader: function ( toolbar ) {
			if ( toolbar.find( '.ac-reply-media-button' ) ) {
				toolbar.find( '.ac-reply-media-button' ).parents( '.post-elements-buttons-item' ).addClass( 'disable' );
			}
		},

		disabledCommentDocumentUploader: function ( toolbar ) {
			if ( toolbar.find( '.ac-reply-document-button' ) ) {
				toolbar.find( '.ac-reply-document-button' ).parents( '.post-elements-buttons-item' ).addClass( 'disable' );
			}
		},

		disabledCommentVideoUploader: function ( toolbar ) {
			if ( toolbar.find( '.ac-reply-video-button' ) ) {
				toolbar.find( '.ac-reply-video-button' ).parents( '.post-elements-buttons-item' ).addClass( 'disable' );
			}
		},

		disabledCommentGifPicker: function ( toolbar ) {
			if ( toolbar.find( '.ac-reply-gif-button' ) ) {
				toolbar.find( '.ac-reply-gif-button' ).parents( '.post-elements-buttons-item' ).addClass( 'disable' );
			}
		},

		validateCommentContent: function ( input ) {
			var $activity_comment_content = input.html();

			var content = $.trim( $activity_comment_content.replace( /<div>/gi, '\n' ).replace( /<\/div>/gi, '' ) );
			content = content.replace( /&nbsp;/g, ' ' );

			var content_text = input.text().trim();
			if ( content_text !== '' || content.indexOf( 'emojioneemoji' ) >= 0 ) {
				input.closest( 'form' ).addClass( 'has-content' );
			} else {
				if ( input.closest( 'form' ).hasClass( 'acomment-edit' ) ) {
					input.closest( 'form' ).addClass( 'has-content' );
				} else {
					input.closest( 'form' ).removeClass( 'has-content' );
				}
			}
		},

		ReactionStatePopupTab: function( event ) {
			event.preventDefault();
			$( this ).closest( '.activity-state-popup' ).find( '.activity-state-popup_tab_panel li a' ).removeClass( 'active' );
			$( this ).addClass( 'active' );
			$( this ).closest( '.activity-state-popup' ).find( '.activity-state-popup_tab_content .activity-state-popup_tab_item' ).removeClass( 'active' );
			$( this ).closest( '.activity-state-popup' ).find( '.' + $( this ).data( 'tab' ) ).addClass( 'active' );

		},

		/**
		 * [closeActivityState description]
		 *
		 * @return {[type]}       [description]
		 */
		closeActivityState: function() {
			$( '.activity-state-popup' ).hide().removeClass( 'active' );
		},

		listenCommentInput: function( input ) {
			if ( input.length > 0 ) {
				var div_editor = input.get( 0 );
				var commentID  = $( div_editor ).attr( 'id' ) + ( $( div_editor ).closest( '.bb-media-model-inner' ).length ? '-theater' : '' );

				// Comment block is moved from theater and needs to be initiated.
				if ( $.inArray( commentID, this.InitiatedCommentForms ) !== -1 && ! $( div_editor ).closest( 'form' ).hasClass( 'events-initiated' ) ) {
					var index = this.InitiatedCommentForms.indexOf( commentID );
					this.InitiatedCommentForms.splice( index, 1 );
				}

				if ( $.inArray( commentID, this.InitiatedCommentForms ) == -1 && ! $( div_editor ).closest( 'form' ).hasClass( 'events-initiated' ) ) {
					// Check if a comment form has already paste event initiated.
					div_editor.addEventListener(
						'paste',
						function ( e ) {
							e.preventDefault();
							var text = e.clipboardData.getData( 'text/plain' );
							document.execCommand( 'insertText', false, text );
						}
					);

					// Register keyup event.
					div_editor.addEventListener(
						'input',
						function ( e ) {
							var $activity_comment_content = jQuery( e.currentTarget ).html();
							var content;

							content = $.trim( $activity_comment_content.replace( /<div>/gi, '\n' ).replace( /<\/div>/gi, '' ) );
							content = content.replace( /&nbsp;/g, ' ' );

							var content_text = jQuery( e.currentTarget ).text().trim();
							if ( '' !== content_text || content.indexOf( 'emojioneemoji' ) >= 0 ) {
								jQuery( e.currentTarget ).closest( 'form' ).addClass( 'has-content' );
							} else {
								jQuery( e.currentTarget ).closest( 'form' ).removeClass( 'has-content' );
							}
						}
					);
					$( div_editor ).closest( 'form' ).addClass( 'events-initiated' );
					this.InitiatedCommentForms.push( commentID ); // Add this Comment form in initiated comment form list.
				}
			}
		},

		/**
		 * [activityRootComment description]
		 *
		 * @return {[type]}       [description]
		 */
		activityRootComment: function ( e ) {
			var currentTarget = $( e.currentTarget ),
				modal = currentTarget.closest( '#activity-modal' ),
				activityId = modal.find( '.activity-item' ).data( 'bp-activity-id' ),
				form = modal.find( '#ac-form-' + activityId );

			bp.Nouveau.Activity.resetActivityCommentForm( form, 'hardReset' );

			modal.find( '.acomment-display' ).removeClass( 'display-focus' );
			modal.find( '.comment-item' ).removeClass( 'comment-item-focus' );
			modal.find( '.bb-modal-activity-footer' ).addClass( 'active' ).append( form );
			form.addClass( 'root' );
			form.find( '#ac-input-' + activityId ).focus();
			bp.Nouveau.Activity.clearFeedbackNotice( form );
		},

		/**
		 * [clearFeedbackNotice description]
		 *
		 * @return {[type]}       [description]
		 */
		clearFeedbackNotice: function ( form ) {
			if ( form.find( '.bp-ac-form-container' ).find( '.bp-feedback' ).length ) {
				form.find( '.bp-ac-form-container' ).find( '.bp-feedback' ).remove();
			}
		},

		/**
		 * [launchActivityPopup description]
		 *
		 * @return {[type]}       [description]
		 */
		launchActivityPopup: function ( activityID, parentID ) {
			var activity_item = $( '#activity-' + activityID );
			var modal = $( '.bb-activity-model-wrapper' );
			var activity_content = activity_item[ 0 ].outerHTML;
			var selector = '[data-parent_comment_id="' + parentID + '"]';
			var activityTitle = activity_item.data( 'activity-popup-title' );

			// Reset to default activity updates and id global variables
			bp.Nouveau.Activity.activityHasUpdates = false;
			bp.Nouveau.Activity.currentActivityId = null;
			bp.Nouveau.Activity.activityPinHasUpdates = false;

			modal.closest( 'body' ).addClass( 'acomments-modal-open' );
			modal.show();
			modal.find( 'ul.activity-list' ).html( activity_content );
			modal.find( '.bb-modal-activity-header h2' ).text( activityTitle );

			// Reload video
			var video_items = modal.find('.bb-activity-video-elem');
			video_items.each(function(index, elem) {
					var video_container = $(elem);
					var videos = video_container.find('video');
					videos.each(function(index, video) {
							var video_element = $(video);
							var video_element_id = video_element.attr('id') + Math.floor(Math.random() * 10000);
							video_element.attr('id', video_element_id);

							var video_action_wrap = video_container.find('.video-action-wrap');
							video_element.insertAfter(video_action_wrap);

							video_container.find('.video-js').remove();

							video_element.addClass('video-js');

							videojs(video_element_id, {
									'controls': true,
									'aspectRatio': '16:9',
									'fluid': true,
									'playbackRates': [0.5, 1, 1.5, 2],
									'fullscreenToggle': false,
							});
					});
			});

			if ( activity_item.hasClass( 'bb-closed-comments' ) ) {
				modal.find( '#activity-modal' ).addClass( 'bb-closed-comments' );
			}

			var form = modal.find( '#ac-form-' + activityID );
			modal.find( '.acomment-display' ).removeClass( 'display-focus' );
			modal.find( '.comment-item' ).removeClass( 'comment-item-focus' );
			modal.find( '.bb-modal-activity-footer' ).addClass( 'active' ).append( form );
			form.removeClass( 'not-initialized' ).addClass( 'root' );
			form.find( '#ac-input-' + activityID ).focus();

			bp.Nouveau.Activity.clearFeedbackNotice( form );

			form.removeClass( 'events-initiated' );
			var ce = modal.find( '.bb-modal-activity-footer' ).find( '.ac-input[contenteditable]' );
			bp.Nouveau.Activity.listenCommentInput( ce );

			var action_tooltips = modal.find('.bb-activity-more-options-wrap .bb-activity-more-options-action, .bb-pin-action_button, .bb-mute-action_button');
			action_tooltips.attr('data-balloon-pos', 'left');
			var privacy_wrap = modal.find( '.privacy-wrap' );
			privacy_wrap.attr( 'data-bp-tooltip-pos', 'right' );

			var viewMoreCommentsLink = modal.find( selector ).children( '.acomments-view-more' ).first();
			viewMoreCommentsLink.trigger( 'click' );

			if ( !_.isUndefined( BP_Nouveau.media ) && !_.isUndefined( BP_Nouveau.media.emoji ) ) {
				bp.Nouveau.Activity.initializeEmojioneArea( true, '#activity-modal ', activityID );
			}

			if ( typeof bp.Nouveau !== 'undefined' ) {
				bp.Nouveau.reportPopUp();
			}

			bp.Nouveau.Activity.toggleMultiMediaOptions( form, '', '.bb-modal-activity-footer' );
		},

		viewMoreComments: function ( e ) {
			e.preventDefault();

			var currentTargetList = $( e.currentTarget ).parent(),
				target = $( e.currentTarget ),
				activityId = $( currentTargetList ).data( 'activity_id' ),
				commentsList = $( e.currentTarget ).closest( '.activity-comments' ),
				commentsActivityItem = $( e.currentTarget ).closest( '.activity-item' ),
				parentCommentId = $( currentTargetList ).data( 'parent_comment_id' ),
				lastCommentTimeStamp = '',
				addAfterListItemId = '';

			var skeleton =
				'<div id="bp-ajax-loader">' +
				'<div class="bb-activity-placeholder bb-activity-tiny-placeholder">' +
				'<div class="bb-activity-placeholder_head">' +
				'<div class="bb-activity-placeholder_avatar bb-bg-animation bb-loading-bg"></div>' +
				'<div class="bb-activity-placeholder_details">' +
				'<div class="bb-activity-placeholder_title bb-bg-animation bb-loading-bg"></div>' +
				'<div class="bb-activity-placeholder_description bb-bg-animation bb-loading-bg"></div>' +
				'</div>' +
				'</div>' +
				'</div>' +
				'</div>';

			target.addClass( 'loading' ).removeClass( 'acomments-view-more--hide' );
			commentsList.addClass( 'active' );
			commentsActivityItem.addClass( 'active' );
			target.html( skeleton );

			var data = {
				action: 'activity_loadmore_comments',
				activity_id: activityId,
				parent_comment_id: parentCommentId,
				offset: $( e.currentTarget ).parents( '.activity-comments' ).find( 'ul[data-parent_comment_id ="' + parentCommentId + '"] > li.comment-item:not(.bb-recent-comment)' ).length,
				activity_type_is_blog: $( e.currentTarget ).parents( '.entry-content' ).length > 1 ? true : false,
			};

			if ( $( e.currentTarget ).prev( 'li.activity-comment' ).length > 0 ) {
				// Load more in the current thread.
				lastCommentTimeStamp = $( e.currentTarget ).prev( 'li.activity-comment' ).data( 'bp-timestamp' );
				data.last_comment_timestamp = lastCommentTimeStamp;
				addAfterListItemId = $( e.currentTarget ).prev( 'li.activity-comment' ).data( 'bp-activity-comment-id' );
				data.last_comment_id = addAfterListItemId;
			}

			bp.Nouveau.ajax( data, 'activity' ).done(
				function ( response ) {
					if ( false === response.success ) {
						target.html( '<p class=\'error\'>' + response.data.message + '</p>' ).removeClass( 'acomments-view-more--hide' );
						commentsList.removeClass( 'active' );
						commentsActivityItem.removeClass( 'active' );
						return;
					} else if ( 'undefined' !== typeof response.data && 'undefined' !== typeof response.data.comments ) {
						// success
						var $targetList = $( '.bb-internal-model .activity-comments' ).find( '[data-activity_id=\'' + activityId + '\'][data-parent_comment_id=\'' + parentCommentId + '\']' );
						var $newComments = $( $.parseHTML( response.data.comments ) );
						if ( $targetList.length > 0 && $newComments.length > 0 ) {

							// Iterate through new comments to handle duplicates
							$newComments.each( function () {

								if ( 'LI' === this.nodeName && 'undefined' !== this.id && '' !== this.id ) {
									var newCommentId = this.id;

									// Check if this comment ID already exists within the target list
									var $existingComment = $targetList.children( '#' + newCommentId );
									if ( $existingComment.length > 0 ) {
										// If it exists, remove the existing comment.
										$existingComment.remove();
									}
								}

							} );

							if ( 'undefined' !== typeof addAfterListItemId && '' !== addAfterListItemId ) {

								var $addAfterElement = $targetList.find( 'li.activity-comment[data-bp-activity-comment-id=\'' + addAfterListItemId + '\']' );
								if( $addAfterElement.length > 0 ) {
									$addAfterElement.after( $newComments );
								} else {
									$targetList.append( $newComments );
								}
							} else if ( $targetList.children( '.activity-comment.comment-item' ).length > 0 ) {
								// Already comments in the list.
								$targetList.children( '.activity-comment.comment-item' ).first().before( $newComments );
							} else {
								$targetList.html( $newComments );
							}

							// replace dummy image with original image by faking scroll event to call bp.Nouveau.lazyLoad.
							setTimeout(
								function () {
									jQuery( window ).scroll();
								},
								200
							);
						}
						target.remove();
						commentsList.removeClass( 'active' );
						commentsActivityItem.removeClass( 'active' );

						var scrollOptions = {
							offset: 0,
							easing: 'swing'
						};

						if ( ! target.hasClass( 'acomments-view-more--root' ) ) {
							$( '.bb-modal-activity-body' ).scrollTo( '#acomment-' + parentCommentId, 500, scrollOptions );
						}

						if ( typeof bp.Nouveau !== 'undefined' ) {
							bp.Nouveau.reportPopUp();
							bp.Nouveau.reportedPopup();
						}

						var action_tooltip = $targetList.find( '.bb-activity-more-options-wrap' ).find( '.bb-activity-more-options-action' );
						action_tooltip.attr( 'data-balloon-pos', 'left' );
					}

				}
			).fail(
				function ( $xhr ) {
					target.html( '<p class=\'error\'>' + $xhr.statusText + '</p>' ).removeClass( 'acomments-view-more--hide' );
					commentsList.removeClass( 'active' );
					commentsActivityItem.removeClass( 'active' );
				}
			);
		},

		autoloadMoreComments: function () {

			if ( $( '.bb-activity-model-wrapper' ).length > 0 && $( '.bb-activity-model-wrapper' ).css( 'display' ) !== 'none' ) {
				var element = $( '.bb-modal-activity-body .activity-comments > ul > li.acomments-view-more:not(.loading), .bb-modal-activity-body .activity-comments .activity-actions > ul > li.acomments-view-more:not(.loading)' ),
					container = $( '.bb-activity-model-wrapper .bb-modal-activity-body' ),
					commentsList = $( '.bb-activity-model-wrapper .bb-modal-activity-body' ).find( '.activity-comments:not(.active)' );
				if ( element.length > 0 && container.length > 0 && commentsList.length > 0 ) {
					var elementTop = $( element ).offset().top, containerTop = $( container ).scrollTop(),
						containerBottom = containerTop + $( container ).height();

					// Adjust elementTop based on the container's current scroll position
					// This translates the element's position to be relative to the container, not the whole document
					var elementRelativeTop = elementTop - $( container ).offset().top + containerTop;
					if ( elementRelativeTop < containerBottom && ( elementRelativeTop + $( element ).outerHeight() ) > containerTop ) {
						$( element ).trigger( 'click' ).addClass( 'loading' );
					}
				}

				// replace dummy image with original image by faking scroll event to call bp.Nouveau.lazyLoad.
				setTimeout(
					function () {
						jQuery( window ).scroll();
					},
					200
				);
			}
		},

		activitySyncOnModalClose: function ( e, activityID ) {
			e.preventDefault();

			var currentTargetModal;

			if ( $( e.currentTarget ).is( document ) ) {
				currentTargetModal = $( '.bb-activity-model-wrapper' );
			} else {
				currentTargetModal = $( e.currentTarget ).parents( '.bb-activity-model-wrapper' );
			}

			var $activityListItem = currentTargetModal.find( 'ul.activity-list > li' ),
				activityListItemId = $activityListItem.data( 'bp-activity-id' ),
				activityId = activityID !== undefined ? activityID : activityListItemId,
				$pageActivitylistItem = $( '#activity-stream li.activity-item[data-bp-activity-id=' + activityId + ']' );

			if ( $pageActivitylistItem.length > 0 && bp.Nouveau.Activity.activityHasUpdates ) {

				$pageActivitylistItem.addClass( 'activity-sync' );

				var data = {
					action: 'activity_sync_from_modal',
					activity_id: activityId,
				};

				bp.Nouveau.ajax( data, 'activity' ).done(
					function ( response ) {
						if ( false === response.success ) {
							return;
						} else if ( 'undefined' !== typeof response.data && 'undefined' !== typeof response.data.activity ) {
							// success
							$pageActivitylistItem.replaceWith( $.parseHTML( response.data.activity ) );
							// replace dummy image with original image by faking scroll event to call bp.Nouveau.lazyLoad.
							jQuery( window ).scroll();

							// Refresh activities after updating pin/unpin post status.
							if ( bp.Nouveau.Activity.activityPinHasUpdates ) {
								bp.Nouveau.refreshActivities();
							}
						}
					}
				).fail(
					function ( $xhr ) {
						console.error('Request failed:', $xhr);
					}
				);
			}

			bp.Nouveau.Activity.activityHasUpdates = false;
			bp.Nouveau.Activity.currentActivityId = null;
		},

		discardGifEmojiPicker: function () {
			var activityId = $( '#activity-modal > .bb-modal-activity-body .activity-item' ).data( 'bp-activity-id' );
			if ( $( '#activity-modal' ).length > 0 && $( '.emojionearea-theatre.show' ).length > 0 ) {
				$( '.bb-activity-model-wrapper #ac-input-' + activityId ).data( 'emojioneArea' ).hidePicker();
			}

			if ( $( '#activity-modal' ).length > 0 && $( '.gif-media-search-dropdown-standalone.open' ).length > 0 ) {
				$( '.gif-media-search-dropdown-standalone' ).removeClass( 'open' );
				$( '#activity-modal' ).find( '.ac-reply-gif-button' ).removeClass( 'active' );
			}
		},

		initializeEmojioneArea: function ( isModal, parentSelector, activityId ) {
			if( ! $.fn.emojioneArea ) {
				return;
			}
			$( parentSelector + '#ac-input-' + activityId ).emojioneArea(
				{
					standalone: true,
					hideSource: false,
					container: parentSelector + '#ac-reply-emoji-button-' + activityId,
					detachPicker: isModal ? true : false,
					containerPicker: isModal ? '.emojionearea-theatre' : null,
					autocomplete: false,
					pickerPosition: 'top',
					hidePickerOnBlur: true,
					useInternalCDN: false,
					events: {
						emojibtn_click: function () {
							$( parentSelector + '#ac-input-' + activityId )[ 0 ].emojioneArea.hidePicker();

							// Check if emoji is added then enable submit button.
							var $activity_comment_input = $( parentSelector + '#ac-form-' + activityId + ' #ac-input-' + activityId );
							var $activity_comment_content = $activity_comment_input.html();
							var content;

							content = $.trim( $activity_comment_content.replace( /<div>/gi, '\n' ).replace( /<\/div>/gi, '' ) );
							content = content.replace( /&nbsp;/g, ' ' );

							var content_text = $activity_comment_input.text();

							if ( content_text !== '' || content.indexOf( 'emojioneemoji' ) >= 0 ) {
								$activity_comment_input.closest( 'form' ).addClass( 'has-content' );
							} else {
								$activity_comment_input.closest( 'form' ).removeClass( 'has-content' );
							}
						},

						picker_show: function () {
							$( this.button[ 0 ] ).closest( '.post-emoji' ).addClass( 'active' );
							$( '.emojionearea-theatre' ).removeClass( 'hide' ).addClass( 'show' );
						},

						picker_hide: function () {
							$( this.button[ 0 ] ).closest( '.post-emoji' ).removeClass( 'active' );
							$( '.emojionearea-theatre' ).removeClass( 'show' ).addClass( 'hide' );
						},
					},
				}
			);
		}

	};

	// Launch BP Nouveau Activity.
	bp.Nouveau.Activity.start();

} )( bp, jQuery );

