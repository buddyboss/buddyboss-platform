/* jshint browser: true */
/* global bp, BP_Nouveau, Dropzone, videojs, bp_media_dropzone */
/* @version [BBVERSION] */
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
			this.just_posted    = []; // Init just posted activities.
			this.current_page   = 1; // Init current page.
			this.mentions_count = Number( $( bp.Nouveau.objectNavParent + ' [data-bp-scope="mentions"]' ).find( 'a span' ).html() ) || 0; // Init mentions count.

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
					url                         : BP_Nouveau.ajaxurl,
					timeout                     : 3 * 60 * 60 * 1000,
					dictFileTooBig              : BP_Nouveau.media.dictFileTooBig,
					dictDefaultMessage          : BP_Nouveau.media.dropzone_media_message,
					acceptedFiles               : 'image/*',
					autoProcessQueue            : true,
					addRemoveLinks              : true,
					uploadMultiple              : false,
					maxFiles                    : typeof BP_Nouveau.media.maxFiles !== 'undefined' ? BP_Nouveau.media.maxFiles : 10,
					maxFilesize                 : typeof BP_Nouveau.media.max_upload_size !== 'undefined' ? BP_Nouveau.media.max_upload_size : 2,
					dictMaxFilesExceeded        : BP_Nouveau.media.media_dict_file_exceeded,
					dictCancelUploadConfirmation: BP_Nouveau.media.dictCancelUploadConfirmation,
					maxThumbnailFilesize        : typeof BP_Nouveau.media.max_upload_size !== 'undefined' ? BP_Nouveau.media.max_upload_size : 2,
				};

				// if defined, add custom dropzone options.
				if ( typeof BP_Nouveau.media.dropzone_options !== 'undefined' ) {
					Object.assign( this.dropzone_options, BP_Nouveau.media.dropzone_options );
				}
			}

			this.dropzone_obj          = null;
			this.dropzone_media        = [];
			this.dropzone_document_obj = null;
			this.dropzone_document     = [];
			this.dropzone_video_obj    = null;
			this.dropzone_video        = [];

			this.models                = [];
			this.InitiatedCommentForms = [];
			this.activityHasUpdates    = false; // Flag to track any activity updates
			this.currentActivityId     = null; // Store the ID of the updated activity
			this.activityPinHasUpdates = false; // Flag to track activity pin updates
		},

		/**
		 * [addListeners description]
		 */
		addListeners: function() {
			var $body           = $( 'body' );
			var $bpElem         = $( '#buddypress' );
			var $document       = $( document );
			var $activityStream = $( '#activity-modal > .bb-modal-activity-body' );

			// HeartBeat listeners.
			if ( ! $body.hasClass( 'activity-singular' ) ) {
				$bpElem.on( 'bp_heartbeat_send', this.heartbeatSend.bind( this ) );
			}
			$bpElem.on( 'bp_heartbeat_tick', this.heartbeatTick.bind( this ) );

			// Inject Activities.
			$bpElem.find( '[data-bp-list="activity"]:not( #bb-schedule-posts_modal [data-bp-list="activity"] )' ).on( 'click', 'li.load-newest, li.bb-rl-load-more', this.injectActivities.bind( this ) );

			// Highlight new activities & clean up the stream.
			$bpElem.on( 'bp_ajax_request', '[data-bp-list="activity"]', this.scopeLoaded.bind( this ) );

			// Activity comments effect.
			$( '#bb-rl-activity-stream' ).on( 'click', '.acomments-view-more', this.showActivity );
			$body.on( 'click', '.bb-close-action-popup', this.closeActivity );

			$document.on( 'activityModalOpened', function( event, data ) {
				var activityId = data.activityId;

				$document.on( 'click', function( event ) {
					if (
						$( '#activity-modal:visible' ).length > 0 &&
						0 === $( '#bb-rl-nouveau-activity-form-placeholder:visible' ).length &&
						! $( event.target ).closest( '#activity-modal' ).length &&
						! $( event.target ).closest( '.bb-rl-gif-media-search-dropdown-standalone' ).length &&
						! $( event.target ).closest( '.emojionearea-theatre' ).length &&
						! $( event.target ).hasClass( 'dz-hidden-input' ) // Dropzone file input for media upload which is outside modal.
					) {
						this.closeActivity( event );
						this.activitySyncOnModalClose( event, activityId );
					}
				}.bind( this ) );
			}.bind( this ) );

			// Activity actions.
			var activityParentSelectors = '[data-bp-list="activity"], #activity-modal, #bb-media-model-container .bb-rl-activity-list';
			$bpElem.find( activityParentSelectors ).on( 'click', '.bb-rl-activity-item', bp.Nouveau, this.activityActions.bind( this ) );
			$bpElem.find( activityParentSelectors ).on( 'click', '.bb-rl-activity-privacy>li.bb-edit-privacy a', bp.Nouveau, this.activityPrivacyRedirect.bind( this ) );
			$bpElem.find( activityParentSelectors ).on( 'click', '.bb-rl-activity-privacy>li:not(.bb-edit-privacy)', bp.Nouveau, this.activityPrivacyChange.bind( this ) );
			$bpElem.find( activityParentSelectors ).on( 'click', 'span.privacy', bp.Nouveau, this.togglePrivacyDropdown.bind( this ) );

			$( '#bb-media-model-container .bb-rl-activity-list' ).on( 'click', '.bb-rl-activity-item', bp.Nouveau, this.activityActions.bind( this ) );
			$( '.bb-activity-model-wrapper' ).on( 'click', '.ac-form-placeholder', bp.Nouveau, this.activityRootComment.bind( this ) );
			$document.keydown( this.commentFormAction );
			$document.click( this.togglePopupDropdown );

			// forums.
			var forumSelectors       = '.ac-reply-media-button, .ac-reply-document-button, .ac-reply-video-button, .ac-reply-gif-button';
			var forumParentSelectors = '[data-bp-list="activity"], #bb-media-model-container .bb-rl-activity-list, #activity-modal .bb-rl-activity-list, .bb-modal-activity-footer';
			$bpElem.find( forumParentSelectors ).on( 'click', forumSelectors, function ( event ) {
				if ( $( event.target ).hasClass( 'ac-reply-media-button' ) ) {
					this.openCommentsMediaUploader( event );
				} else if ( $( event.target ).hasClass( 'ac-reply-document-button' ) ) {
					this.openCommentsDocumentUploader( event );
				} else if ( $( event.target ).hasClass( 'ac-reply-video-button' ) ) {
					this.openCommentsVideoUploader( event );
				} else if ( $( event.target ).hasClass( 'ac-reply-gif-button' ) ) {
					this.openGifPicker( event );
				}
			}.bind( this ) );

			// Reaction actions.
			$document.on( 'click', '.activity-state-popup_overlay', bp.Nouveau, this.closeActivityState.bind( this ) );
			$document.on( 'click', '.activity-state-popup .activity-state-popup_tab_panel a', this.ReactionStatePopupTab );

			// Activity autoload.
			if ( ! _.isUndefined( BP_Nouveau.activity.params.autoload ) ) {
				$( window ).scroll( this.loadMoreActivities );
			}

			$( '.bb-activity-model-wrapper, .bb-media-model-wrapper' ).on( 'click', '.acomments-view-more', this.viewMoreComments.bind( this ) );
			$document.on( 'click', '#bb-rl-activity-stream .bb-rl-activity-comments .view-more-comments, #bb-rl-activity-stream .activity-state-comments > .comments-count', function ( e ) {
				e.preventDefault();
				$( this ).parents( 'li.bb-rl-activity-item' ).find( '.bb-rl-activity-comments > ul > li.acomments-view-more, .bb-rl-activity-comments > .activity-actions > ul > li.acomments-view-more' ).trigger( 'click' );
			} );

			$activityStream.on( 'scroll', this.autoloadMoreComments.bind( this ) );
			$activityStream.on( 'scroll', this.discardGifEmojiPicker.bind( this ) );

			$( '.bb-activity-model-wrapper .bb-model-close-button' ).on( 'click', this.activitySyncOnModalClose.bind( this ) );

			// Validate media access for comment forms.
			var initializeForms = function () {
				$( '.ac-form.not-initialized' ).each( function () {
					var form   = $( this );
					var target = form.find( '.ac-textarea' );
					bp.Nouveau.Activity.toggleMultiMediaOptions( form, target );
				} );
			};

			if ( BP_Nouveau.is_send_ajax_request === '1' ) {
				$bpElem.on( 'bp_ajax_request', '[data-bp-list="activity"]', function () {
					setTimeout( initializeForms, 1000 );
				} );
			} else {
				setTimeout( initializeForms, 1000 );
			}
		},

		/**
		 * [heartbeatSend description]
		 *
		 * @return {[type]}       [description]
		 * @param event
		 * @param data
		 */
		heartbeatSend: function( event, data ) {
			var $allActivities      = $( '#buddypress [data-bp-list] [data-bp-activity-id]' );
			var $unPinnedActivities = $allActivities.not( '.bb-pinned' );

			// First recorded timestamp: unpinned items.
			var $firstUnpinned                 = $unPinnedActivities.first();
			this.heartbeat_data.first_recorded = $firstUnpinned.data( 'bp-timestamp' ) || 0;

			// Handle the first item is already latest and pinned.
			var firstActivityTimestamp = $allActivities.first().data( 'bp-timestamp' ) || 0;
			if ( firstActivityTimestamp > this.heartbeat_data.first_recorded ) {
				this.heartbeat_data.first_recorded = firstActivityTimestamp;
			}

			if ( 0 === this.heartbeat_data.last_recorded || this.heartbeat_data.first_recorded > this.heartbeat_data.last_recorded ) {
				this.heartbeat_data.last_recorded = this.heartbeat_data.first_recorded;
			}

			data.bp_activity_last_recorded = this.heartbeat_data.last_recorded;

			var $searchInput = $( '#buddypress .dir-search input[type=search]' );
			if ( $searchInput.length ) {
				data.bp_activity_last_recorded_search_terms = $searchInput.val();
			}

			$.extend( data, { bp_heartbeat: bp.Nouveau.getStorage( 'bp-activity' ) } );
		},

		/**
		 * [heartbeatTick description]
		 *
		 * @return {[type]}                [description]
		 * @param event
		 * @param data
		 */
		heartbeatTick: function( event, data ) {
			var newestActivitiesCount, newestActivities, objects = bp.Nouveau.objects,
				scope = bp.Nouveau.getStorage( 'bp-activity', 'scope' ), self = this;

			// Only proceed if we have newest activities.
			if ( undefined === data || ! data.bp_activity_newest_activities ) {
				return;
			}

			var activitiesData = data.bp_activity_newest_activities;

			this.heartbeat_data.newest        = $.trim( activitiesData.activities ) + this.heartbeat_data.newest;
			this.heartbeat_data.last_recorded = Number( activitiesData.last_recorded );

			// Parse activities.
			newestActivities = $( this.heartbeat_data.newest ).filter( '.bb-rl-activity-item' );

			// Count them.
			newestActivitiesCount = Number( newestActivities.length );

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
					newestActivities,
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
				$( bp.Nouveau.objectNavParent + ' [data-bp-scope="all"]' ).find( 'a span' ).html( newestActivitiesCount );

				// Set all activities to be highlighted for the current scope.
			} else {
				// Init the array of highlighted activities.
				this.heartbeat_data.highlights[ scope ] = [];

				$.each(
					newestActivities,
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
			$( document ).prop( 'title', '(' + newestActivitiesCount + ') ' + this.heartbeat_data.document_title );

			// Update the Load Newest li if it already exists.
			var $bpElemList = $( '#buddypress [data-bp-list="activity"]' );
			var $aElem       = $bpElemList.find( '.load-newest a' );
			if ( $bpElemList.first().hasClass( 'load-newest' ) ) {
				var newest_link = $aElem.html();
				$aElem.html( newest_link.replace( /([0-9]+)/, newestActivitiesCount ) );

				// Otherwise add it.
			} else {
				$bpElemList.find( 'ul.bb-rl-activity-list' ).prepend( '<li class="load-newest"><a href="#newest">' + BP_Nouveau.newest + ' (' + newestActivitiesCount + ')</a></li>' );
			}

			$bpElemList.find( 'li.load-newest' ).trigger( 'click' );

			/**
			 * Finally trigger a pending event containing the activity heartbeat data
			 */
			$bpElemList.trigger( 'bp_heartbeat_pending', this.heartbeat_data );

			if ( typeof bp.Nouveau !== 'undefined' ) {
				bp.Nouveau.reportPopUp();
			}
		},

		/**
		 * [injectQuery description]
		 *
		 * @return {[type]}       [description]
		 * @param event
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
							var $elem = $( '#' + $( activity ).prop( 'id' ) );
							if ( $elem.length ) {
								$elem.remove();
							}
						}

					}
				);

				var $activityList = $( event.delegateTarget ).find( '.bb-rl-activity-list' );
				var firstActivity = $activityList.find( '.bb-rl-activity-item' ).first();
				if ( firstActivity.length > 0 && firstActivity.hasClass( 'bb-pinned' ) ) {

					// Add after pinned post.
					$( firstActivity ).after( this.heartbeat_data.newest ).find( 'li.bb-rl-activity-item' ).each( bp.Nouveau.hideSingleUrl ).trigger( 'bp_heartbeat_prepend', this.heartbeat_data );

				} else {

					// Now the stream is cleaned, prepend newest.
					$activityList.prepend( this.heartbeat_data.newest ).find( 'li.bb-rl-activity-item' ).each( bp.Nouveau.hideSingleUrl ).trigger( 'bp_heartbeat_prepend', this.heartbeat_data );
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
			} else if ( $( event.currentTarget ).hasClass( 'bb-rl-load-more' ) ) {
				var nextPage = ( Number( this.current_page ) ) + 1, self = this, searchTerms = '';

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

				var $searchElem = $( '#buddypress .dir-search input[type=search]' );
				if ( $searchElem.length ) {
					searchTerms = $searchElem.val();
				}

				bp.Nouveau.objectRequest(
					{
						object              : 'activity',
						scope               : scope,
						filter              : filter,
						search_terms        : searchTerms,
						page                : nextPage,
						method              : 'append',
						exclude_just_posted : this.just_posted.join( ',' ),
						target              : '#buddypress [data-bp-list]:not( #bb-schedule-posts_modal [data-bp-list="activity"] ) ul.bb-rl-list'
					}
				).done(
					function( response ) {
						if ( true === response.success ) {
							targetEl.remove();

							// Update the current page.
							self.current_page = nextPage;

							// replace dummy image with original image by faking scroll event to call bp.Nouveau.lazyLoad.
							jQuery( window ).scroll();
						}
					}
				);
			}

			$( '.bb-rl-activity-item.bb-closed-comments' ).find( '.edit-activity, .acomment-edit' ).parents( '.generic-button' ).hide();
		},

		/**
		 * [truncateComments description]
		 *
		 * @return {[type]}       [description]
		 * @param event
		 */
		hideComments: function( event ) {
			var comments = $( event.target ).find( '.bb-rl-activity-comments' ),
				activityItem, commentItems, commentCount, commentParents;

			if ( ! comments.length ) {
				return;
			}

			comments.each(
				function( c, comment ) {
					commentParents = $( comment ).children( 'ul' ).not( '.conflict-activity-ul-li-comment' );
					commentItems   = $( commentParents ).find( 'li' ).not( $( '.document-action-class, .media-action-class, .video-action-class' ) );

					if ( ! commentItems.length ) {
						return;
					}

					// Check if URL has specific comment to show.
					if ( $( 'body' ).hasClass( 'activity-singular' ) && '' !== window.location.hash && $( window.location.hash ).length && 0 !== $( window.location.hash ).closest( '.bb-rl-activity-comments' ).length ) {
						return;
					}

					// Get the activity id.
					activityItem = $( comment ).closest( '.bb-rl-activity-item' );

					// Get the comment count.
					commentCount = $( '#acomment-comment-' + activityItem.data( 'bp-activity-id' ) + ' span.comment-count' ).html() || ' ';

					// Keep latest 5 comments.
					commentItems.each(
						function( i, item ) {
							if ( i < commentItems.length - 4 ) {

								// Prepend a link to display all.
								if ( ! i ) {
									$( item ).parent( 'ul' ).before( '<div class="show-all"><button class="text-button" type="button" data-bp-show-comments-id="#' + activityItem.prop( 'id' ) + '/show-all/">' + BP_Nouveau.show_x_comments + '</button></div>' );
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
					if ( $( commentParents ).children( '.bp-hidden' ).length === $( commentParents ).children( 'li' ).length - 1 && $( commentParents ).find( 'li.show-all' ).length ) {
						$( commentParents ).children( 'li:not(.show-all)' ).removeClass( 'bp-hidden' ).toggle();
					}
				}
			);
		},

		/**
		 * [showActivity description]
		 *
		 * @return {[type]}       [description]
		 * @param event
		 */
		showActivity: function( event ) {
			event.preventDefault();
			var currentTargetList = $( event.currentTarget ).parent(),
				parentId = currentTargetList.data( 'parent_comment_id' ),
				activityId = $( currentTargetList ).data( 'activity_id' );

			$( document ).trigger( 'activityModalOpened', { activityId: activityId } );

			$( event.currentTarget ).parents( '.bb-rl-activity-comments' ).find( '.ac-form' ).each( function () {
				var form = $( this );
				var commentsList = $( this ).closest( '.bb-rl-activity-comments' );
				var commentItem = $( this ).closest( '.comment-item' );
				// Reset emojionearea
				form.find( '.bb-rl-post-elements-buttons-item.bb-rl-post-emoji' ).removeClass( 'active' ).empty( '' );

				bp.Nouveau.Activity.resetActivityCommentForm( form, 'hardReset' );
				commentsList.append( form );
				commentItem.find( '.bb-rl-acomment-display' ).removeClass( 'display-focus' );
				commentItem.removeClass( 'comment-item-focus' );
			} );

			bp.Nouveau.Activity.launchActivityPopup( activityId, parentId );
		},

		closeActivity: function ( event ) {
			event.preventDefault();
			var target     = $( event.target ),
			    modal      = target.closest( '.bb-activity-model-wrapper' ),
			    footer     = modal.find( '.bb-modal-activity-footer' ),
			    activityId = modal.find( '.bb-rl-activity-item' ).data( 'bp-activity-id' ),
			    form       = modal.find( '#ac-form-' + activityId );

			if ( form.length ) {
				bp.Nouveau.Activity.reinitializeActivityCommentForm( form );
			}

			if ( !_.isUndefined( BP_Nouveau.media ) && !_.isUndefined( BP_Nouveau.media.emoji ) ) {
				bp.Nouveau.Activity.initializeEmojioneArea( false, '', activityId );
			}

			modal.find( '#activity-modal' ).removeClass( 'bb-closed-comments' );

			modal.closest( 'body' ).removeClass( 'acomments-modal-open' );
			modal.hide();
			modal.find( 'ul.bb-rl-activity-list' ).empty();
			footer.removeClass( 'active' );
			footer.find( 'form.ac-form' ).remove();
		},

		/**
		 * [scopeLoaded description]
		 *
		 * @return {[type]}       [description]
		 * @param event
		 * @param data
		 */
		scopeLoaded: function ( event, data ) {
			// Reset the pagination for the scope.
			this.current_page = 1;

			var $activityStream = $( '#buddypress #bb-rl-activity-stream' );
			// Mentions are specific.
			if ( 'mentions' === data.scope && undefined !== data.response.new_mentions ) {
				$.each(
					data.response.new_mentions,
					function( i, id ) {
						$activityStream.find( '[data-bp-activity-id="' + id + '"]' ).addClass( 'newest_mentions_activity' );
					}
				);

				// Reset mentions count.
				this.mentions_count = 0;
			} else if ( undefined !== this.heartbeat_data.highlights[data.scope] && this.heartbeat_data.highlights[data.scope].length ) {
				$.each(
					this.heartbeat_data.highlights[data.scope],
					function( i, id ) {
						if ( $activityStream.find( '[data-bp-activity-id="' + id + '"]' ).length ) {
							$activityStream.find( '[data-bp-activity-id="' + id + '"]' ).addClass( 'newest_' + data.scope + '_activity' );
						}
					}
				);
			}

			// Reset the newest activities now they're displayed.
			this.heartbeat_data.newest = '';
			$.each(
				$( bp.Nouveau.objectNavParent + ' [data-bp-scope]' ).find( 'a span' ),
				function( s, count ) {
					var countText = $( count ).html();
					if ( '0' === countText ) {
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
					$( '#buddypress #bb-rl-activity-stream .bb-rl-activity-item' ).removeClass( 'newest_' + data.scope + '_activity' );
				},
				3000
			);

			if ( 'undefined' !== typeof window.instgrm ) {
				window.instgrm.Embeds.process();
			}
			if ( 'undefined' !== typeof window.FB && 'undefined' !== typeof window.FB.XFBML ) {
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
				var activity_item = $( '#bb-rl-activity-' + BP_Nouveau.activity.params.is_activity_edit );
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
			var parent        = event.data,
			    target        = $( event.target ),
			    activityItem = $( event.currentTarget ).closest( '.bb-rl-activity-item' ),
			    activity_id   = activityItem.data( 'bp-activity-id' );

			// Stop event propagation.
			event.preventDefault();

			var privacyValue = target.data( 'value' );
			if ( 'undefined' === typeof privacyValue || '' === $.trim( privacyValue ) ) { // Ensure that 'privacyValue' exists and isn't an empty string.
				return false;
			}

			activityItem.find( '.privacy' ).addClass( 'loading' );

			parent.ajax( { action: 'activity_update_privacy', 'id': activity_id, 'privacy': privacyValue }, 'activity' ).done(
				function( response ) {
					activityItem.find( '.privacy' ).removeClass( 'loading' );

					if ( true === response.success ) {
						var $privacy = activityItem.find( '.privacy' );
						activityItem.find( '.bb-rl-activity-privacy li' ).removeClass( 'selected' );
						activityItem.find( '.bb-rl-privacy-wrap' ).attr( 'data-bp-tooltip', target.text() );
						target.addClass( 'selected' );
						$privacy.removeClass( 'public loggedin onlyme friends' ).addClass( privacyValue );

						if ( typeof response !== 'undefined' && typeof response.data !== 'undefined' && typeof response.data.video_symlink !== 'undefined' ) {

							// Cache selectors
							var $documentDescriptionWrap = $( '.document-description-wrap' );
							var $documentTheatre         = $documentDescriptionWrap.find( '.bb-open-document-theatre' );
							var $documentDetailWrap      = $( '.document-detail-wrap.document-detail-wrap-description-popup' );

							// Update attributes for document theatre
							if ( $documentDescriptionWrap.length && $documentTheatre.length ) {
								$documentTheatre.attr( {
									'data-video-preview': response.data.video_symlink,
									'data-extension'    : response.data.extension
								} );
							}

							// Update attributes for document detail wrap
							if ( $documentDescriptionWrap.length && $documentDetailWrap.length ) {
								$documentDetailWrap.attr( {
									'data-video-preview': response.data.video_symlink,
									'data-extension'    : response.data.extension
								} );
							}

							if ( typeof videojs !== 'undefined' && response.data.video_js_id ) {
								var myPlayer = videojs( response.data.video_js_id );
								myPlayer.src( {
									type: response.data.video_extension,
									src : response.data.video_symlink
								} );
							}
						}

						bp.Nouveau.Activity.activityHasUpdates = true;
						bp.Nouveau.Activity.currentActivityId = activity_id;
					}
				}
			);
		},

		activityPrivacyRedirect: function ( event ) {
			var target = $( event.target );

			// Stop event propagation.
			event.preventDefault();
			var privacyUrl = target.data( 'value' );
			if ( 'undefined' === typeof privacyUrl || '' === $.trim( privacyUrl ) ) {
				return false;
			} else {
				window.location.href = privacyUrl;
			}
		},

		/* jshint ignore:start */
		togglePrivacyDropdown: function ( event ) {

			var activityItem = $( event.currentTarget ).closest( '.bb-rl-activity-item' );

			// Stop event propagation.
			event.preventDefault();

			var privacyElement = activityItem.find( '.bb-rl-activity-privacy' );

			$( 'ul.bb-rl-activity-privacy' ).not( privacyElement ).removeClass( 'bb-open' ); // close other dropdowns.

			privacyElement.toggleClass( 'bb-open' );
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
					parent_el   = target.parents( '.bb-rl-acomment-display' ).first(),
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
					main_el = target.parents( '.bb-rl-activity-item' );
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
											if ( 0 < main_el.find( '.bb-rl-activity-content .activity-state-reactions' ).length ) {
												main_el.find( '.bb-rl-activity-content  .activity-state-reactions' ).replaceWith( response.data.reaction_count );
											} else {
												main_el.find( '.bb-rl-activity-content .activity-state' ).prepend( response.data.reaction_count );
											}

											// Added has-likes class if activity has any reactions.
											if ( response.data.reaction_count !== '' ) {
												activity_state.addClass( 'has-likes' );
											} else {
												activity_state.removeClass( 'has-likes' );
											}

										} else {
											if ( 0 < main_el.find( '#bb-rl-acomment-display-' + item_id + ' .bb-rl-comment-reactions .activity-state-reactions' ).length ) {
												main_el.find( '#bb-rl-acomment-display-' + item_id + ' .bb-rl-comment-reactions .activity-state-reactions' ).replaceWith( response.data.reaction_count );
											} else {
												main_el.find( '#bb-rl-acomment-display-' + item_id + ' .bb-rl-comment-reactions' ).prepend( response.data.reaction_count );
											}
										}
									}

									// Update reacted button.
									if ( response.data.reaction_button ) {
										if ( is_activity ) {
											main_el.find( '.bp-generic-meta a.bp-like-button:first' ).replaceWith( response.data.reaction_button );
										} else {
											main_el.find( '#bb-rl-acomment-display-' + item_id + ' .bp-generic-meta a.bp-like-button' ).replaceWith( response.data.reaction_button );
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

							// Add flag for ajax load for getting reactions.
							if ( 'activity_comment' === item_type ) {
								$( '.activity-comment[data-bp-activity-comment-id=' + item_id + '] > .bb-rl-acomment-display > .bb-rl-acomment_inner' ).find( '.activity-state-reactions' ).parent().addClass( 'bb-has-reaction_update' );
							} else if ( 'activity' === item_type ) {
								$( '.activity[data-bp-activity-id=' + item_id + '] > .bb-rl-activity-content' ).find( '.activity-state-reactions' ).parent().addClass( 'bb-has-reaction_update' );
							}
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

				commentsList = target.closest( '.bb-rl-activity-comments' );
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
								$( 'body:not(.activity-singular) #buddypress #bb-rl-activity-stream ul.bb-rl-activity-list li#bb-rl-activity-' + response.data.parent_activity_id ).replaceWith( response.data.activity );
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

				if ( $( content ).hasClass( 'bb-rl-activity-inner' ) ) {
					item_id = activity_id;
				} else if ( $( content ).hasClass( 'bb-rl-acomment-content' ) ) {
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
							if ( $( content ).children( '.bb-poll-view' ).length ) {
								// Make sure to replace content but not .bb-poll-view.
								$( content ).children( ':not(.bb-poll-view)' ).remove();
								$( content ).prepend( response.data.contents ).slideDown( 300 );

							} else {
								$( content ).html( response.data.contents ).slideDown( 300 );
							}

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
				target.closest( '.bb-rl-activity-item' ).find( '.acomment-reply' ).eq( 0 ).trigger( 'click' );
			}

			// Displaying the comment form.
			if (
				target.hasClass( 'activity-state-comments' ) ||
				target.hasClass( 'acomment-reply' ) ||
				target.parent().hasClass( 'acomment-reply' ) ||
				target.hasClass( 'acomment-edit' )
			) {
				if ( target.parents( '.bb-rl-activity-item' ).hasClass( 'bb-closed-comments' ) ) {
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
					$activity_comments = $( '[data-bp-activity-id="' + item_id + '"] .bb-rl-activity-comments' );
					hasParentModal = '';
				}
				var activity_comment_data = false;

				if ( target.closest( '.bb-media-model-container' ).length ) {
					form               = target.closest( '.bb-media-model-container' ).find( '#ac-form-' + activity_id );
					$activity_comments = target.closest( '.bb-media-model-container' ).find( '[data-bp-activity-id="' + item_id + '"] .bb-rl-activity-comments' );
				}

				// Show comment form on activity item when it is hidden initially.
				if ( ! target.closest( '.bb-rl-activity-item' ).hasClass( 'has-comments' ) ) {
					target.closest( '.bb-rl-activity-item' ).addClass( 'has-comments' );
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
				$( '.ac-form' ).find( '.ac-input:not(.emojionearea)' ).html( '' );

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
					acomment.find( '#bb-rl-acomment-display-' + item_id ).addClass( 'bp-hide' );
					acomment.find( '#bb-rl-acomment-edit-form-' + item_id ).append( form );
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
							$( '#activity-modal' ).find( '.bb-rl-acomment-display' ).removeClass( 'display-focus' );
							$( '#activity-modal' ).find( '.comment-item' ).removeClass( 'comment-item-focus' );
						}

						$activity_comments.append( form );
						form.addClass( 'root' );
						$activity_comments.find( '.bb-rl-acomment-display' ).removeClass( 'display-focus' );
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

				var emojiPosition = form.find( '.bb-rl-post-elements-buttons-item.bb-rl-post-emoji' ).prevAll().not( ':hidden' ).length + 1;
				form.find( '.bb-rl-post-elements-buttons-item.bb-rl-post-emoji' ).attr( 'data-nth-child', emojiPosition );

				var gifPosition = form.find( '.bb-rl-post-elements-buttons-item.bb-rl-post-gif' ).prevAll().not( ':hidden' ).length + 1;
				form.find( '.bb-rl-post-elements-buttons-item.bb-rl-post-gif' ).attr( 'data-nth-child', gifPosition );

				/* Stop past image from clipboard */
				var ce = form.find( '.ac-input[contenteditable]' );
				bp.Nouveau.Activity.listenCommentInput( ce );

				// change the aria state from false to true.
				target.attr( 'aria-expanded', 'true' );
				target.closest( '.bb-rl-activity-comments' ).find( '.bb-rl-acomment-display' ).removeClass( 'display-focus' );
				target.closest( '.bb-rl-activity-comments' ).find( '.comment-item' ).removeClass( 'comment-item-focus' );
				target.closest( '.bb-rl-acomment-display' ).addClass( 'display-focus' );
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

				['media', 'document'].forEach( function ( type ) {
					self.destroyUploader( type, activity_id );
				} );

				// If form is edit activity comment, then reset it.
				self.resetActivityCommentForm( $form );

				// Stop event propagation.
				event.preventDefault();
			}

			// Submitting comments and replies.
			if ( 'ac_form_submit' === target.prop( 'name' ) ) {
				target.prop( 'disabled', true );

				var comment_content, comment_data;

				commentsList = target.closest( '.bb-rl-activity-comments' );
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
						$( Obj ).addClass( 'bb-rl-emojioneemoji' );
						var emojis = $( Obj ).attr( 'alt' );
						$( Obj ).attr( 'data-emoji-char', emojis );
						$( Obj ).removeClass( 'emoji' );
					}
				);

				// Transform emoji image into emoji unicode.
				comment_content.find( 'img.bb-rl-emojioneemoji' ).replaceWith(
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
							var isElementorWidget = target.closest( '.elementor-bb-rl-activity-item' ).length > 0;
							var isCommentElementorWidgetForm = form.prev().hasClass( 'activity-actions' );
							var activity_comments;

							if (isElementorWidget && isCommentElementorWidgetForm) {
								activity_comments = form.parent().find( '.activity-actions' );
							} else {
								activity_comments = form.parent();
							}
							var the_comment       = $.trim( response.data.contents );

							activity_comments.find( '.bb-rl-acomment-display' ).removeClass('display-focus');
							activity_comments.find( '.comment-item' ).removeClass( 'comment-item-focus' );
							activity_comments.addClass( 'has-child-comments' );

							var form_activity_id = form.find( 'input[name="comment_form_id"]' ).val();
							if ( isInsideModal ) {
								$('#activity-modal').find( '.bb-modal-activity-footer' ).append( form ).addClass( 'active' );
								form.removeClass( 'has-content' ).addClass( 'root' );
							} else {
								form.addClass( 'not-initialized' ).removeClass( 'has-content has-gif has-media' );
								form.closest( '.bb-rl-activity-comments' ).append( form );
							}
							form.find( '#ac-input-' + form_activity_id ).html( '' );

							if ( form.hasClass( 'acomment-edit' ) ) {
								var form_item_id = form.attr( 'data-item-id' );
								form.closest( '.bb-rl-activity-comments' ).append( form );
								if ( isInsideModal ) {
									$( '#activity-modal' ).find( 'li#bb-rl-acomment-' + form_item_id ).replaceWith( the_comment );
								} else {
									$( 'li#bb-rl-acomment-' + form_item_id ).replaceWith( the_comment );
								}
							} else {
								if ( 0 === activity_comments.children( 'ul' ).length ) {
									if ( activity_comments.hasClass( 'bb-rl-activity-comments' ) ) {
										activity_comments.prepend( '<ul></ul>' );
									} else {
										activity_comments.append( '<ul></ul>' );
									}
								}

								if ( isFooterForm ) {
									form.closest( '#activity-modal' ).find( '.bb-modal-activity-body .bb-rl-activity-comments, .bb-modal-activity-body .bb-rl-activity-comments .activity-actions' ).children( 'ul' ).append( $( the_comment ) );
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
								tool_box_comment.find( '.ac-reply-toolbar .ac-reply-media-button' ).parents( '.bb-rl-post-elements-buttons-item' ).removeClass( 'disable no-click' );
							}
							if ( tool_box_comment.find( '.ac-reply-toolbar .ac-reply-document-button' ).length > 0 ) {
								tool_box_comment.find( '.ac-reply-toolbar .ac-reply-document-button' ).parents( '.bb-rl-post-elements-buttons-item' ).removeClass( 'disable no-click' );
							}
							if ( tool_box_comment.find( '.ac-reply-toolbar .ac-reply-video-button' ).length > 0 ) {
								tool_box_comment.find( '.ac-reply-toolbar .ac-reply-video-button' ).parents( '.bb-rl-post-elements-buttons-item' ).removeClass( 'disable no-click' );
							}
							if ( tool_box_comment.find( '.ac-reply-toolbar .ac-reply-gif-button' ).length > 0 ) {
								tool_box_comment.find( '.ac-reply-toolbar .ac-reply-gif-button' ).removeClass( 'active ' );
								tool_box_comment.find( '.ac-reply-toolbar .ac-reply-gif-button' ).parents( '.bb-rl-post-elements-buttons-item' ).removeClass( 'disable no-click' );
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
							$( '#ac-reply-post-gif-' + activity_id ).find( '.bb-rl-activity-attached-gif-container' ).removeAttr( 'style' );
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
						$( '#bb-rl-nouveau-activity-form' ).addClass( 'bb-rl-group-activity' );
					} else {
						$( '#bb-rl-nouveau-activity-form' ).removeClass( 'bb-rl-group-activity' );
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

				target.closest( '.bb-rl-activity-item' ).addClass( 'loading-pin' );

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
						target.closest( '.bb-rl-activity-item' ).removeClass( 'loading-pin' );

						// Check for JSON output.
						if ( 'object' !== typeof response ) {
							response = JSON.parse( response );
						}
						if ( 'undefined' !== typeof response.data && 'undefined' !== typeof response.data.feedback ) {
							var activity_list   = target.closest( 'ul.bb-rl-activity-list' );
							var activity_stream;
							if ( isInsideModal ) {
								activity_stream = target.closest( '.buddypress-wrap' ).find( '#bb-rl-activity-stream' );
							} else {
								activity_stream = target.closest( '#bb-rl-activity-stream' );
							}


							if ( response.success ) {

								var scope = bp.Nouveau.getStorage( 'bp-activity', 'scope' );
								var update_pinned_icon = false;
								var is_group_activity  = false;
								var activity_group_id  = '';

								if ( target.closest( 'li.bb-rl-activity-item' ).hasClass('groups') ) {
									is_group_activity = true;
									activity_group_id = target.closest( 'li.bb-rl-activity-item' ).attr('class').match(/group-\d+/);
									activity_group_id = activity_group_id[0].replace( 'group-', '' );
								}

								if ( activity_stream.hasClass( 'single-user' ) ) {
									update_pinned_icon = false;
								} else if (
									activity_stream.hasClass( 'bb-rl-activity' ) &&
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
										activity_list.find( 'li.bb-rl-activity-item' ).removeClass( 'bb-pinned' );
									}

									var update_pin_actions = 'li.bb-rl-activity-item:not(.groups)';
									if( is_group_activity && ! activity_stream.hasClass( 'single-group' ) ) {
										update_pin_actions = 'li.bb-rl-activity-item.group-' + activity_group_id;
									} else if( is_group_activity && activity_stream.hasClass( 'single-group' ) ) {
										update_pin_actions = 'li.bb-rl-activity-item';
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
										target.closest( 'li.bb-rl-activity-item' ).addClass( 'bb-pinned' );
									}

									target.addClass( 'unpin-activity' );
									target.removeClass( 'pin-activity' );

									if ( target.closest( 'li.bb-rl-activity-item' ).hasClass('groups') ) {
										target.find('span').html( BP_Nouveau.activity.strings.unpinGroupPost );
									} else {
										target.find('span').html( BP_Nouveau.activity.strings.unpinPost );
									}
								} else if ( 'unpin' === pin_action ) {
									target.closest( 'li.bb-rl-activity-item' ).removeClass( 'bb-pinned' );
									target.addClass( 'pin-activity' );
									target.removeClass( 'unpin-activity' );
									if ( target.closest( 'li.bb-rl-activity-item' ).hasClass('groups') ) {
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
						target.closest( '.bb-rl-activity-item' ).removeClass( 'loading-pin' );
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
				target.closest( '.bb-rl-activity-item' ).addClass( 'loading-mute' );

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
						target.closest( '.bb-rl-activity-item' ).removeClass( 'loading-mute' );

						// Check for JSON output.
						if ( 'object' !== typeof response ) {
							response = JSON.parse( response );
						}

						if ( 'undefined' !== typeof response.data && 'undefined' !== typeof response.data.feedback ) {

							if ( response.success ) {
								// Change the muted class and label.
								if ( 'mute' === notification_toggle_action ) {
									target.closest( 'li.bb-rl-activity-item' ).addClass( 'bb-muted' );
									target.removeClass( 'bb-icon-bell-slash' );
									target.addClass( 'bb-icon-bell' );
									target.attr( 'title', BP_Nouveau.activity.strings.unmuteNotification );
									target.find( 'span' ).html( BP_Nouveau.activity.strings.unmuteNotification );
								} else if ( 'unmute' === notification_toggle_action ) {
									target.closest( 'li.bb-rl-activity-item' ).removeClass( 'bb-muted' );
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
						target.closest( '.bb-rl-activity-item' ).removeClass( 'loading-pin' );
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

				target.closest( '.bb-rl-activity-item' ).addClass( 'loading-pin' );

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
						target.closest( '.bb-rl-activity-item' ).removeClass( 'loading-pin' );

						// Check for JSON output.
						if ( 'object' !== typeof response ) {
							response = JSON.parse( response );
						}
						if ( 'undefined' !== typeof response.data && 'undefined' !== typeof response.data.feedback ) {
							if ( response.success ) {
								var $media_parent = $( '#bb-rl-activity-stream > .bb-rl-activity-list' ).find( '[data-bp-activity-id=' + activity_id + ']' );
								target.closest( '.bb-rl-activity-item' ).find( '.bb-rl-activity-closed-comments-notice' ).remove();
								// Change the close comments related class and label.
								if ( 'close_comments' === close_comments_action ) {
									target.closest( 'li.bb-rl-activity-item' ).addClass( 'bb-closed-comments' );
									if ( target.closest( '#activity-modal' ).length > 0 ) {
										target.closest( '#activity-modal' ).addClass( 'bb-closed-comments' );
									}
									target.addClass( 'unclose-activity-comment' );
									target.removeClass( 'close-activity-comment' );
									target.find( 'span' ).html( BP_Nouveau.activity.strings.uncloseComments );
									target.closest( 'li.bb-rl-activity-item.bb-closed-comments' ).find( '.edit-activity, .acomment-edit' ).parents( '.generic-button' ).hide();
									target.closest( '.bb-rl-activity-item' ).find( '.bb-rl-activity-comments' ).before( '<div class="bb-rl-activity-closed-comments-notice">' + response.data.feedback + '</div>' );

									// Handle event from media theatre.
									if ( target.parents( '.bb-media-model-wrapper' ).length > 0 ) {
										if ( $media_parent.length > 0 ) {
											$media_parent.addClass( 'bb-closed-comments' );
											$media_parent.find( '.bb-activity-more-options .close-activity-comment span' ).html( BP_Nouveau.activity.strings.uncloseComments );
											$media_parent.find( '.bb-activity-more-options .close-activity-comment' ).addClass( 'unclose-activity-comment' ).removeClass( 'close-activity-comment' );
											$media_parent.find( '.edit-activity, .acomment-edit' ).parents( '.generic-button' ).hide();
											$media_parent.find( '.bb-rl-activity-comments' ).before( '<div class="bb-rl-activity-closed-comments-notice">' + response.data.feedback + '</div>' );
										}
									}
								} else if ( 'unclose_comments' === close_comments_action ) {
									target.closest( 'li.bb-rl-activity-item.bb-closed-comments' ).find( '.edit-activity, .acomment-edit' ).parents( '.generic-button' ).show();
									target.closest( 'li.bb-rl-activity-item' ).removeClass( 'bb-closed-comments' );
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
											$media_parent.find( '.bb-rl-activity-closed-comments-notice' ).html( '' );
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
						target.closest( '.bb-rl-activity-item' ).removeClass( 'loading-pin' );
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

			element = event.target || event.srcElement;

			if ( 3 === element.nodeType ) {
				element = element.parentNode;
			}

			if ( true === event.altKey || true === event.metaKey ) {
				return event;
			}

			// Not in a comment textarea, return.
			if ( 'TEXTAREA' !== element.tagName || ! $( element ).hasClass( 'ac-input' ) ) {
				return event;
			}

			keyCode = ( event.keyCode) ? event.keyCode : event.which;

			if ( 27 === keyCode && false === event.ctrlKey ) {
				$( element ).closest( 'form' ).slideUp( 200 );
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

			element = event.target || event.srcElement;

			if ( 3 === element.nodeType ) {
				element = element.parentNode;
			}

			if ( true === event.altKey || true === event.metaKey ) {
				return event;
			}

			// if privacy dropdown items, return.
			if ( $( element ).closest( '.bb-rl-privacy-wrap' ).length ) {
				return event;
			}

			$( 'ul.bb-rl-activity-privacy' ).removeClass( 'bb-open' );
		},

		// activity autoload.
		loadMoreActivities: function () {
			var $load_more_btn = $( '.bb-rl-load-more:visible' ).last(),
			    $window        = $( window );

			if ( 0 === $load_more_btn.length || ! $load_more_btn.closest( '.bb-rl-activity-list' ).length ) {
				return;
			}

			if ( true === $load_more_btn.data( 'bp-autoloaded' ) ) {
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
									tool_box.find( '.ac-reply-media-button' ).parents( '.bb-rl-post-elements-buttons-item' ).addClass( 'no-click' ).find( '.bb-rl-toolbar-button' ).addClass( 'active' );
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
								tool_box.find( '.ac-reply-document-button' ).parents( '.bb-rl-post-elements-buttons-item' ).addClass( 'disable' );
							}
							if ( tool_box.find( '.ac-reply-video-button' ) ) {
								tool_box.find( '.ac-reply-video-button' ).parents( '.bb-rl-post-elements-buttons-item' ).addClass( 'disable' );
							}
							if ( tool_box.find( '.ac-reply-gif-button' ) ) {
								tool_box.find( '.ac-reply-gif-button' ).parents( '.bb-rl-post-elements-buttons-item' ).addClass( 'disable' );
							}
							if ( tool_box.find( '.ac-reply-media-button' ) ) {
								tool_box.find( '.ac-reply-media-button' ).parents( '.bb-rl-post-elements-buttons-item' ).addClass( 'no-click' ).find( '.bb-rl-toolbar-button' ).addClass( 'active' );
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
									tool_box.find( '.ac-reply-document-button' ).parents( '.bb-rl-post-elements-buttons-item' ).removeClass( 'disable' );
								}
								if ( tool_box.find( '.ac-reply-video-button' ) ) {
									tool_box.find( '.ac-reply-video-button' ).parents( '.bb-rl-post-elements-buttons-item' ).removeClass( 'disable' );
								}
								if ( tool_box.find( '.ac-reply-gif-button' ) ) {
									tool_box.find( '.ac-reply-gif-button' ).parents( '.bb-rl-post-elements-buttons-item' ).removeClass( 'disable' );
								}
								if ( tool_box.find( '.ac-reply-media-button' ) ) {
									tool_box.find( '.ac-reply-media-button' ).parents( '.bb-rl-post-elements-buttons-item' ).removeClass( 'no-click' ).find( '.bb-rl-toolbar-button' ).removeClass( 'active' );
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
			['document', 'video'].forEach( function ( type ) {
				self.destroyUploader( type, activityID );
			} );

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
								tool_box.find( '.ac-reply-document-button' ).parents( '.bb-rl-post-elements-buttons-item' ).addClass( 'no-click' ).find( '.bb-rl-toolbar-button' ).addClass( 'active' );
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
								tool_box.find( '.ac-reply-media-button' ).parents( '.bb-rl-post-elements-buttons-item' ).addClass( 'disable' );
							}
							if ( tool_box.find( '.ac-reply-video-button' ) ) {
								tool_box.find( '.ac-reply-video-button' ).parents( '.bb-rl-post-elements-buttons-item' ).addClass( 'disable' );
							}
							if ( tool_box.find( '.ac-reply-gif-button' ) ) {
								tool_box.find( '.ac-reply-gif-button' ).parents( '.bb-rl-post-elements-buttons-item' ).addClass( 'disable' );
							}
							if ( tool_box.find( '.ac-reply-document-button' ) ) {
								tool_box.find( '.ac-reply-document-button' ).parents( '.bb-rl-post-elements-buttons-item' ).addClass( 'no-click' ).find( '.bb-rl-toolbar-button' ).addClass( 'active' );
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
									tool_box.find( '.ac-reply-media-button' ).parents( '.bb-rl-post-elements-buttons-item' ).removeClass( 'disable' );
								}
								if ( tool_box.find( '.ac-reply-video-button' ) ) {
									tool_box.find( '.ac-reply-video-button' ).parents( '.bb-rl-post-elements-buttons-item' ).removeClass( 'disable' );
								}
								if ( tool_box.find( '.ac-reply-gif-button' ) ) {
									tool_box.find( '.ac-reply-gif-button' ).parents( '.bb-rl-post-elements-buttons-item' ).removeClass( 'disable' );
								}
								if ( tool_box.find( '.ac-reply-document-button' ) ) {
									tool_box.find( '.ac-reply-document-button' ).parents( '.bb-rl-post-elements-buttons-item' ).removeClass( 'no-click' ).find( '.bb-rl-toolbar-button' ).removeClass( 'active' );
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
			['media', 'video'].forEach( function ( type ) {
				self.destroyUploader( type, activityID );
			} );

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
								tool_box.find( '.ac-reply-media-button' ).parents( '.bb-rl-post-elements-buttons-item' ).addClass( 'disable' );
							}
							if ( tool_box.find( '.ac-reply-document-button' ) ) {
								tool_box.find( '.ac-reply-document-button' ).parents( '.bb-rl-post-elements-buttons-item' ).addClass( 'disable' );
							}
							if ( tool_box.find( '.ac-reply-gif-button' ) ) {
								tool_box.find( '.ac-reply-gif-button' ).parents( '.bb-rl-post-elements-buttons-item' ).addClass( 'disable' );
							}
							if ( tool_box.find( '.ac-reply-video-button' ) ) {
								tool_box.find( '.ac-reply-video-button' ).parents( '.bb-rl-post-elements-buttons-item' ).addClass( 'no-click' ).find( '.bb-rl-toolbar-button' ).addClass( 'active' );
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
									tool_box.find( '.ac-reply-video-button' ).parents( '.bb-rl-post-elements-buttons-item' ).addClass( 'no-click' ).find( '.bb-rl-toolbar-button' ).addClass( 'active' );
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
									tool_box.find( '.ac-reply-media-button' ).parents( '.bb-rl-post-elements-buttons-item' ).removeClass( 'disable' );
								}
								if ( tool_box.find( '.ac-reply-document-button' ) ) {
									tool_box.find( '.ac-reply-document-button' ).parents( '.bb-rl-post-elements-buttons-item' ).removeClass( 'disable' );
								}
								if ( tool_box.find( '.ac-reply-gif-button' ) ) {
									tool_box.find( '.ac-reply-gif-button' ).parents( '.bb-rl-post-elements-buttons-item' ).removeClass( 'disable' );
								}
								if ( tool_box.find( '.ac-reply-video-button' ) ) {
									tool_box.find( '.ac-reply-video-button' ).parents( '.bb-rl-post-elements-buttons-item' ).removeClass( 'no-click' ).find( '.bb-rl-toolbar-button' ).removeClass( 'active' );
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
			['media', 'video'].forEach( function ( type ) {
				self.destroyUploader( type, activityID );
			} );

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
				pickerContainer = isInsideModal ? $( '.bb-rl-gif-media-search-dropdown-standalone' ) : $( currentTarget ).next(),
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

			var gif_box = $( currentTarget ).parents( '.ac-textarea ' ).find( '.ac-reply-attachments .bb-rl-activity-attached-gif-container' );
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

			if ( $( currentTarget ).closest( '.bb-rl-post-elements-buttons-item' ).index() > 1 ) {
				pickerLeftPosition = leftPosition + $gifPickerEl.width() - 180;
			}
			var transformValue = 'translate(' + ( pickerLeftPosition ) + 'px, ' + ( topPosition - scrollTop - 5 ) + 'px)  translate(-100%, -100%)';
			if ( isInsideModal ) {
				$gifPickerEl.css( 'transform', transformValue );
			}
			var self = this;
			['media', 'document', 'video'].forEach( function ( type ) {
				self.destroyUploader( type, activityID );
			} );
		},

		toggleMultiMediaOptions: function( form, target, placeholder ) {
			if ( ! _.isUndefined( BP_Nouveau.media ) ) {

				var parent_activity = '',
					activity_data = '';

				if ( placeholder ) {
					target = target ? $( target ) : $( placeholder );
					parent_activity = target.closest( '.activity-modal' ).find( '.bb-rl-activity-item' );
					activity_data = target.closest( '.activity-modal' ).find( '.bb-rl-activity-item' ).data( 'bp-activity' );
					form = $( placeholder );
				} else {
					parent_activity = target.closest( '.bb-rl-activity-item' );
					activity_data = target.closest( '.bb-rl-activity-item' ).data( 'bp-activity' );
				}

				if ( target.closest( 'li' ).data( 'bp-activity-comment' ) ) {
					activity_data = target.closest( 'li' ).data( 'bp-activity-comment' );
				}

				// Use ternary operators to reduce repetitive code and ensure clear checks.
				var toggleMedia = function ( selector, condition ) {
					if ( false === condition ) {
						form.find( selector ).hide().parent( '.ac-reply-toolbar' ).addClass( 'bb-rl-post-media-disabled' );
					} else {
						form.find( selector ).show().parent( '.ac-reply-toolbar' ).removeClass( 'bb-rl-post-media-disabled' );
					}
				};

				var mediaSettings = function ( mediaType, fallbackValue ) {
					if ( activity_data && ! _.isUndefined( activity_data[mediaType] ) ) {
						return activity_data[mediaType];
					} else if ( false === BP_Nouveau.media[mediaType] ) {
						return BP_Nouveau.media[mediaType];
					}
					return fallbackValue;
				};

				if (
					target.closest( 'li' ).hasClass( 'groups' ) ||
					parent_activity.hasClass( 'groups' )
				) {
					var groupMediaSettings = {
						group_media   : mediaSettings( 'group_media', false ),
						group_document: mediaSettings( 'group_document', false ),
						group_video   : mediaSettings( 'group_video', false ),
						gif           : BP_Nouveau.media.gif.groups,
						emoji         : BP_Nouveau.media.emoji.groups
					};

					this.applyMediaSettings( form, groupMediaSettings );
				} else { // Check for profile media
					var profileMediaSettings = {
						profile_media   : mediaSettings( 'profile_media', false ),
						profile_document: mediaSettings( 'profile_document', false ),
						profile_video   : mediaSettings( 'profile_video', false ),
						gif             : BP_Nouveau.media.gif.profile,
						emoji           : BP_Nouveau.media.emoji.profile
					};

					this.applyMediaSettings( form, profileMediaSettings );
				}
			}
		},

		applyMediaSettings: function ( form, settings ) {
			// Handle media visibility based on the settings.
			form.find( '.ac-reply-toolbar .bb-rl-post-media.bb-rl-media-support' ).toggle( settings.group_media || settings.profile_media ).parent( '.ac-reply-toolbar' ).toggleClass( 'bb-rl-post-media-disabled', ! (
				settings.group_media || settings.profile_media
			) );

			form.find( '.ac-reply-toolbar .bb-rl-post-media.bb-rl-document-support' ).toggle( settings.group_document || settings.profile_document ).parent( '.ac-reply-toolbar' ).toggleClass( 'bb-rl-post-media-disabled', ! (
				settings.group_document || settings.profile_document
			) );

			form.find( '.ac-reply-toolbar .bb-rl-post-video.bb-rl-video-support' ).toggle( settings.group_video || settings.profile_video ).parent( '.ac-reply-toolbar' ).toggleClass( 'post-video-disabled', ! (
				settings.group_video || settings.profile_video
			) );

			form.find( '.ac-reply-toolbar .bb-rl-post-gif' ).toggle( settings.gif !== false ).parent( '.ac-reply-toolbar' ).toggleClass( 'post-gif-disabled', settings.gif === false );

			form.find( '.ac-reply-toolbar .bb-rl-post-emoji' ).toggle( settings.emoji !== false ).parent( '.ac-reply-toolbar' ).toggleClass( 'post-emoji-disabled', settings.emoji === false );
		},

		fixAtWhoActivity: function() {
			$( '.bb-rl-acomment-content, .bb-rl-activity-content' ).each(
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
					var hash = window.location.hash;
					if ( hash ) {
						var $adminBar = $( '#wpadminbar' );
						var id       = hash;
						var adminBar = $adminBar.length !== 0 ? $adminBar.innerHeight() : 0;
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
			var formActivityId = form.find( 'input[name="comment_form_id"]' ).val(),
			    toolbarDiv      = form.find( '#ac-reply-toolbar-' + formActivityId ),
			    formSubmitBtn  = form.find( 'input[name="ac_form_submit"]' ),
			    self             = this;

			form.find( '#ac-input-' + formActivityId ).html( activity_comment_data.content );

			var form_submit_btn_attr_val = formSubmitBtn.attr( 'data-add-edit-label' );
			formSubmitBtn.attr( 'data-add-edit-label', formSubmitBtn.val() ).val( form_submit_btn_attr_val );

			var uploaderButtons = [];
			// Inject medias.
			if (
				'undefined' !== typeof activity_comment_data.media &&
				0 < activity_comment_data.media.length
			) {
				// Trigger the button click for the specific file type.
				toolbarDiv.find( '.ac-reply-media-button' ).trigger( { type: 'click', isCustomEvent: true } );

				uploaderButtons = [
					'.ac-reply-document-button',
					'.ac-reply-video-button',
					'.ac-reply-gif-button'
				];
				uploaderButtons.forEach( function ( buttonClass ) {
					self.disabledCommentUploader( toolbarDiv, buttonClass );
				} );

				self.injectFiles( {
					toolbarDiv : toolbarDiv,
					commonData : activity_comment_data.media,
					id         : activity_comment_data.id,
					self       : this,
					fileType   : 'media',
					buttonClass: '.ac-reply-media-button',
					dropzoneObj: self.dropzone_obj,
				} );
			}

			// Inject Documents.
			if (
				'undefined' !== typeof activity_comment_data.document &&
				0 < activity_comment_data.document.length
			) {
				// Trigger the button click for the specific file type.
				toolbarDiv.find( '.ac-reply-document-button' ).trigger( { type: 'click', isCustomEvent: true } );

				uploaderButtons = [
					'.ac-reply-media-button',
					'.ac-reply-video-button',
					'.ac-reply-gif-button'
				];
				uploaderButtons.forEach( function ( buttonClass ) {
					self.disabledCommentUploader( toolbarDiv, buttonClass );
				} );

				self.injectFiles( {
					toolbarDiv : toolbarDiv,
					commonData : activity_comment_data.document,
					id         : activity_comment_data.id,
					self       : this,
					fileType   : 'document',
					buttonClass: '.ac-reply-document-button',
					dropzoneObj: self.dropzone_document_obj,
				} );
			}

			// Inject Videos.
			if (
				'undefined' !== typeof activity_comment_data.video &&
				0 < activity_comment_data.video.length
			) {
				// Trigger the button click for the specific file type.
				toolbarDiv.find( '.ac-reply-video-button' ).trigger( { type: 'click', isCustomEvent: true } );

				uploaderButtons = [
					'.ac-reply-media-button',
					'.ac-reply-document-button',
					'.ac-reply-gif-button'
				];
				uploaderButtons.forEach( function ( buttonClass ) {
					self.disabledCommentUploader( toolbarDiv, buttonClass );
				} );

				self.injectFiles( {
					toolbarDiv : toolbarDiv,
					commonData : activity_comment_data.video,
					id         : activity_comment_data.id,
					self       : this,
					fileType   : 'video',
					buttonClass: '.ac-reply-video-button',
					dropzoneObj: self.dropzone_video_obj,
				} );
			}

			// Inject GIF.
			if (
				'undefined' !== typeof activity_comment_data.gif &&
				0 < Object.keys( activity_comment_data.gif ).length
			) {
				var $gifPickerEl     = toolbarDiv.find( '.ac-reply-gif-button' ).next(),
				    isInsideModal    = form.closest( '#activity-modal' ).length > 0,
				    hasParentModal   = isInsideModal ? '#activity-modal ' : '',
				    $gifAttachmentEl = $( hasParentModal + '#ac-reply-post-gif-' + formActivityId );

				toolbarDiv.find( '.ac-reply-gif-button' ).trigger( 'click' );

				uploaderButtons = [
					'.ac-reply-media-button',
					'.ac-reply-document-button',
					'.ac-reply-video-button'
				];
				uploaderButtons.forEach( function ( buttonClass ) {
					self.disabledCommentUploader( toolbarDiv, buttonClass );
				} );

				var model                      = new bp.Models.ACReply(),
				    gifMediaSearchDropdownView = new bp.Views.GifMediaSearchDropdown( { model: model } ),
				    activityAttachedGifPreview = new bp.Views.ActivityAttachedGifPreview( { model: model } );

				gifMediaSearchDropdownView.model.set( 'gif_data', activity_comment_data.gif );
				$gifPickerEl.html( gifMediaSearchDropdownView.render().el );
				$gifAttachmentEl.html( activityAttachedGifPreview.render().el );

				this.models[formActivityId] = model;
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

			var formActivityId = form.find( 'input[name="comment_form_id"]' ).val(),
			    formItemId     = form.attr( 'data-item-id' ),
			    formAcomment   = $( '[data-bp-activity-comment-id="' + formItemId + '"]' ),
			    formSubmitBtn  = form.find( 'input[name="ac_form_submit"]' );

			formAcomment.find( '#bb-rl-acomment-display-' + formItemId ).removeClass( 'bp-hide' );
			form.removeClass( 'acomment-edit' ).removeAttr( 'data-item-id' );

			var form_submit_btn_attr_val = formSubmitBtn.attr( 'data-add-edit-label' );
			formSubmitBtn.attr( 'data-add-edit-label', formSubmitBtn.val() ).val( form_submit_btn_attr_val );

			form.find( '.bb-rl-post-elements-buttons-item' ).removeClass( 'disable' );
			form.find( '.bb-rl-post-elements-buttons-item .bb-rl-toolbar-button' ).removeClass( 'active' );

			form.find( '#ac-input-' + formActivityId ).html( '' );
			form.removeClass( 'has-content has-gif has-media' );
			var self = this;
			['media', 'document', 'video', 'gif'].forEach( function ( type ) {
				self.destroyUploader( 'gif', formActivityId );
			} );
		},

		// Reinitialize reply/edit comment form and append in activity modal footer
		reinitializeActivityCommentForm: function ( form ) {

			var formActivityId = form.find( 'input[name="comment_form_id"]' ).val(),
			    formSubmitBtn  = form.find( 'input[name="ac_form_submit"]' );

			if ( form.hasClass( 'acomment-edit' ) ) {
				var formItemId   = form.attr( 'data-item-id' );
				var formAcomment = $( '[data-bp-activity-comment-id="' + formItemId + '"]' );

				formAcomment.find( '#bb-rl-acomment-display-' + formItemId ).removeClass( 'bp-hide' );
				form.removeClass( 'acomment-edit' ).removeAttr( 'data-item-id' );
			}

			var form_submit_btn_attr_val = formSubmitBtn.attr( 'data-add-edit-label' );
			formSubmitBtn.attr( 'data-add-edit-label', formSubmitBtn.val() ).val( form_submit_btn_attr_val );

			form.find( '#ac-input-' + formActivityId ).html( '' );
			form.removeClass( 'has-content has-gif has-media' );
			$( '.bb-modal-activity-footer' ).addClass( 'active' ).append( form );
			var self = this;
			['media', 'document', 'video', 'gif'].forEach( function ( type ) {
				self.destroyUploader( type, formActivityId );
			} );
		},

		disabledCommentUploader: function ( toolbar, buttonClass ) {
			var button = toolbar.find( buttonClass );

			if ( button.length > 0 ) {
				button.parents( '.bb-rl-post-elements-buttons-item' ).addClass( 'disable' );
			}
		},

		validateCommentContent: function ( input ) {
			var $activity_comment_content = input.html();

			var content = $.trim( $activity_comment_content.replace( /<div>/gi, '\n' ).replace( /<\/div>/gi, '' ) );
			content     = content.replace( /&nbsp;/g, ' ' );

			var content_text = input.text().trim();
			var form         = input.closest( 'form' );
			if ( '' !== content_text || content.indexOf( 'bb-rl-emojioneemoji' ) >= 0 ) {
				form.addClass( 'has-content' );
			} else {
				if ( form.hasClass( 'acomment-edit' ) ) {
					form.addClass( 'has-content' );
				} else {
					form.removeClass( 'has-content' );
				}
			}
		},

		ReactionStatePopupTab: function ( event ) {
			event.preventDefault();
			var popup = $( this ).closest( '.activity-state-popup' );
			popup.find( '.activity-state-popup_tab_panel li a' ).removeClass( 'active' );
			$( this ).addClass( 'active' );
			popup.find( '.activity-state-popup_tab_content .activity-state-popup_tab_item' ).removeClass( 'active' );
			popup.find( '.' + $( this ).data( 'tab' ) ).addClass( 'active' );
		},

		/**
		 * [closeActivityState description]
		 *
		 * @return {[type]}       [description]
		 */
		closeActivityState: function() {
			$( '.activity-state-popup' ).hide().removeClass( 'active' );
		},

		listenCommentInput: function ( input ) {
			if ( input.length > 0 ) {
				var divEditor = input.get( 0 );
				var commentID  = $( divEditor ).attr( 'id' ) + (
					$( divEditor ).closest( '.bb-media-model-inner' ).length ? '-theater' : ''
				);

				// Comment block is moved from theater and needs to be initiated.
				if ( $.inArray( commentID, this.InitiatedCommentForms ) !== - 1 && ! $( divEditor ).closest( 'form' ).hasClass( 'events-initiated' ) ) {
					var index = this.InitiatedCommentForms.indexOf( commentID );
					this.InitiatedCommentForms.splice( index, 1 );
				}

				if ( $.inArray( commentID, this.InitiatedCommentForms ) === - 1 && ! $( divEditor ).closest( 'form' ).hasClass( 'events-initiated' ) ) {
					// Check if a comment form has already paste event initiated.
					divEditor.addEventListener(
						'paste',
						function ( e ) {
							e.preventDefault();
							var text = e.clipboardData.getData( 'text/plain' );
							document.execCommand( 'insertText', false, text );
						}
					);

					// Register keyup event.
					divEditor.addEventListener(
						'input',
						function ( e ) {
							var $activityCommentContent = $( e.currentTarget ).html();
							var content;

							content = $.trim( $activityCommentContent.replace( /<div>/gi, '\n' ).replace( /<\/div>/gi, '' ) );
							content = content.replace( /&nbsp;/g, ' ' );

							var content_text = $( e.currentTarget ).text().trim();
							if ( '' !== content_text || content.indexOf( 'bb-rl-emojioneemoji' ) >= 0 ) {
								$( e.currentTarget ).closest( 'form' ).addClass( 'has-content' );
							} else {
								$( e.currentTarget ).closest( 'form' ).removeClass( 'has-content' );
							}
						}
					);
					$( divEditor ).closest( 'form' ).addClass( 'events-initiated' );
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
			    modal         = currentTarget.closest( '#activity-modal' ),
			    activityId    = modal.find( '.bb-rl-activity-item' ).data( 'bp-activity-id' ),
			    form          = modal.find( '#ac-form-' + activityId );

			bp.Nouveau.Activity.resetActivityCommentForm( form, 'hardReset' );

			modal.find( '.bb-rl-acomment-display' ).removeClass( 'display-focus' );
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
			var feedback = form.find( '.bp-ac-form-container .bp-feedback' );
			if ( feedback.length ) {
				feedback.remove();
			}
		},

		/**
		 * [launchActivityPopup description]
		 *
		 * @return {[type]}       [description]
		 */
		launchActivityPopup: function ( activityID, parentID ) {
			var activityItem    = $( '#bb-rl-activity-' + activityID );
			var modal           = $( '.bb-activity-model-wrapper' );
			var activityContent = activityItem[0].outerHTML;
			var selector        = '[data-parent_comment_id="' + parentID + '"]';
			var activityTitle   = activityItem.data( 'activity-popup-title' );

			// Reset to default activity updates and id global variables
			bp.Nouveau.Activity.activityHasUpdates    = false;
			bp.Nouveau.Activity.currentActivityId     = null;
			bp.Nouveau.Activity.activityPinHasUpdates = false;

			modal.closest( 'body' ).addClass( 'acomments-modal-open' );
			modal.show();
			modal.find( 'ul.bb-rl-activity-list' ).html( activityContent );
			modal.find( '.bb-modal-activity-header h2' ).text( activityTitle );

			// Reload video
			var videoItems = modal.find( '.bb-activity-video-elem' );
			videoItems.each( function ( index, elem ) {
				var videoContainer = $( elem );
				var videos         = videoContainer.find( 'video' );
				videos.each( function ( index, video ) {
					var videoElement   = $( video );
					var videoElementId = videoElement.attr( 'id' ) + Math.floor( Math.random() * 10000 );
					videoElement.attr( 'id', videoElementId );

					var videoActionWrap = videoContainer.find( '.video-action-wrap' );
					videoElement.insertAfter( videoActionWrap );
					videoContainer.find( '.video-js' ).remove();
					videoElement.addClass( 'video-js' );

					videojs( videoElementId, {
						'controls'        : true,
						'aspectRatio'     : '16:9',
						'fluid'           : true,
						'playbackRates'   : [0.5, 1, 1.5, 2],
						'fullscreenToggle': false,
					} );
				} );
			} );

			if ( activityItem.hasClass( 'bb-closed-comments' ) ) {
				modal.find( '#activity-modal' ).addClass( 'bb-closed-comments' );
			}

			var form = modal.find( '#ac-form-' + activityID );
			modal.find( '.bb-rl-acomment-display' ).removeClass( 'display-focus' );
			modal.find( '.comment-item' ).removeClass( 'comment-item-focus' );
			modal.find( '.bb-modal-activity-footer' ).addClass( 'active' ).append( form );
			form.removeClass( 'not-initialized' ).addClass( 'root' ).find( '#ac-input-' + activityID ).focus();

			bp.Nouveau.Activity.clearFeedbackNotice( form );
			form.removeClass( 'events-initiated' );
			var ce = modal.find( '.bb-modal-activity-footer' ).find( '.ac-input[contenteditable]' );
			bp.Nouveau.Activity.listenCommentInput( ce );

			modal.find( '.bb-activity-more-options-wrap .bb-activity-more-options-action, .bb-rl-pin-action_button, .bb-rl-mute-action_button' ).attr( 'data-balloon-pos', 'left' );
			modal.find( '.bb-rl-privacy-wrap' ).attr( 'data-bp-tooltip-pos', 'right' );
			modal.find( selector ).children( '.acomments-view-more' ).first().trigger( 'click' );

			if ( ! _.isUndefined( BP_Nouveau.media ) && ! _.isUndefined( BP_Nouveau.media.emoji ) ) {
				bp.Nouveau.Activity.initializeEmojioneArea( true, '#activity-modal ', activityID );
			}

			if ( 'undefined' !== typeof bp.Nouveau ) {
				bp.Nouveau.reportPopUp();
			}

			bp.Nouveau.Activity.toggleMultiMediaOptions( form, '', '.bb-modal-activity-footer' );
		},

		viewMoreComments: function ( e ) {
			e.preventDefault();

			var $target              = $( e.currentTarget ),
			    currentTargetList    = $target.parent(),
			    activityId           = $( currentTargetList ).data( 'activity_id' ),
			    commentsList         = $target.closest( '.activity-comments' ),
			    commentsActivityItem = $target.closest( '.activity-item' ),
			    parentCommentId      = $( currentTargetList ).data( 'parent_comment_id' ),
			    lastCommentTimeStamp = '',
			    addAfterListItemId   = '';

			var skeleton =
				'<div id="bb-rl-ajax-loader">' +
				'<div class="bb-rl-activity-placeholder bb-activity-tiny-placeholder">' +
				'<div class="bb-rl-activity-placeholder_head">' +
				'<div class="bb-rl-activity-placeholder_avatar bb-rl-bg-animation bb-rl-loading-bg"></div>' +
				'<div class="bb-rl-activity-placeholder_details">' +
				'<div class="bb-rl-activity-placeholder_title bb-rl-bg-animation bb-rl-loading-bg"></div>' +
				'<div class="bb-rl-activity-placeholder_description bb-rl-bg-animation bb-rl-loading-bg"></div>' +
				'</div>' +
				'</div>' +
				'</div>' +
				'</div>';

			$target.addClass( 'loading' ).removeClass( 'acomments-view-more--hide' );
			commentsList.addClass( 'active' );
			commentsActivityItem.addClass( 'active' );
			$target.html( skeleton );

			var data = {
				action               : 'activity_loadmore_comments',
				activity_id          : activityId,
				parent_comment_id    : parentCommentId,
				offset               : $target.parents( '.activity-comments' ).find( 'ul[data-parent_comment_id ="' + parentCommentId + '"] > li.comment-item:not(.bb-recent-comment)' ).length,
				activity_type_is_blog: $target.parents( '.entry-content' ).length > 1,
			};

			if ( $target.prev( 'li.activity-comment' ).length > 0 ) {
				// Load more in the current thread.
				lastCommentTimeStamp        = $target.prev( 'li.activity-comment' ).data( 'bp-timestamp' );
				data.last_comment_timestamp = lastCommentTimeStamp;
				addAfterListItemId          = $target.prev( 'li.activity-comment' ).data( 'bp-activity-comment-id' );
				data.last_comment_id        = addAfterListItemId;
			}

			bp.Nouveau.ajax( data, 'activity' ).done(
				function ( response ) {
					if ( false === response.success ) {
						$target.html( '<p class=\'error\'>' + response.data.message + '</p>' ).removeClass( 'acomments-view-more--hide' );
						commentsList.removeClass( 'active' );
						commentsActivityItem.removeClass( 'active' );
						return;
					}
					if ( 'undefined' !== typeof response.data && 'undefined' !== typeof response.data.comments ) {
						// success
						var $targetList  = $( '.bb-internal-model .bb-rl-activity-comments' ).find( '[data-activity_id=\'' + activityId + '\'][data-parent_comment_id=\'' + parentCommentId + '\']' );
						var $newComments = $( $.parseHTML( response.data.comments ) );
						if ( $targetList.length > 0 && $newComments.length > 0 ) {

							// Iterate through new comments to handle duplicates
							$newComments.each( function () {
								if ( 'LI' === this.nodeName && 'undefined' !== this.id && '' !== this.id ) {
									var newCommentId     = this.id;
									// Check if this comment ID already exists within the target list
									var $existingComment = $targetList.children( '#' + newCommentId );
									if ( $existingComment.length > 0 ) {
										$existingComment.remove(); // If it exists, remove the existing comment.
									}
								}
							} );

							if ( 'undefined' !== typeof addAfterListItemId && '' !== addAfterListItemId ) {
								var $addAfterElement = $targetList.find( 'li.activity-comment[data-bp-activity-comment-id=\'' + addAfterListItemId + '\']' );
								if ( $addAfterElement.length > 0 ) {
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
						$target.remove();
						commentsList.removeClass( 'active' );
						commentsActivityItem.removeClass( 'active' );

						var scrollOptions = {
							offset: 0,
							easing: 'swing'
						};

						if ( ! $target.hasClass( 'acomments-view-more--root' ) ) {
							$( '.bb-modal-activity-body' ).scrollTo( '#bb-rl-acomment-' + parentCommentId, 500, scrollOptions );
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
					$target.html( '<p class=\'error\'>' + $xhr.statusText + '</p>' ).removeClass( 'acomments-view-more--hide' );
					commentsList.removeClass( 'active' );
					commentsActivityItem.removeClass( 'active' );
				}
			);
		},

		autoloadMoreComments: function () {
			var activityWrapper = $( '.bb-activity-model-wrapper' );

			if ( activityWrapper.length > 0 && 'none' !== activityWrapper.css( 'display' ) ) {
				var element      = $( '.bb-modal-activity-body .bb-rl-activity-comments > ul > li.acomments-view-more:not(.loading), .bb-modal-activity-body .bb-rl-activity-comments .activity-actions > ul > li.acomments-view-more:not(.loading)' ),
				    container    = activityWrapper.find( '.bb-modal-activity-body' ),
				    commentsList = container.find( '.bb-rl-activity-comments:not(.active)' );
				if ( element.length > 0 && container.length > 0 && commentsList.length > 0 ) {
					var elementTop = $( element ).offset().top, containerTop = $( container ).scrollTop(),
					    containerBottom                                      = containerTop + $( container ).height();

					// Adjust elementTop based on the container's current scroll position
					// This translates the element's position to be relative to the container, not the whole document
					var elementRelativeTop = elementTop - $( container ).offset().top + containerTop;
					if ( elementRelativeTop < containerBottom && (
						elementRelativeTop + $( element ).outerHeight()
					) > containerTop ) {
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
			var currentTarget = $( e.currentTarget );

			if ( currentTarget.is( document ) ) {
				currentTargetModal = $( '.bb-activity-model-wrapper' );
			} else {
				currentTargetModal = currentTarget.parents( '.bb-activity-model-wrapper' );
			}

			var $activityListItem     = currentTargetModal.find( 'ul.bb-rl-activity-list > li' ),
			    activityListItemId    = $activityListItem.data( 'bp-activity-id' ),
			    activityId            = undefined !== activityID ? activityID : activityListItemId,
			    $pageActivitylistItem = $( '#bb-rl-activity-stream li.bb-rl-activity-item[data-bp-activity-id=' + activityId + ']' );

			if ( $pageActivitylistItem.length > 0 && bp.Nouveau.Activity.activityHasUpdates ) {
				$pageActivitylistItem.addClass( 'activity-sync' );

				var data = {
					action     : 'activity_sync_from_modal',
					activity_id: activityId,
				};

				bp.Nouveau.ajax( data, 'activity' ).done(
					function ( response ) {
						if ( false === response.success ) {
							return;
						}
						if ( 'undefined' !== typeof response.data && 'undefined' !== typeof response.data.activity ) {
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
						console.error( 'Request failed:', $xhr );
					}
				);
			}

			bp.Nouveau.Activity.activityHasUpdates = false;
			bp.Nouveau.Activity.currentActivityId  = null;
		},

		discardGifEmojiPicker: function () {
			var activityModal = $( '#activity-modal' );
			var activityId    = activityModal.find( '.bb-modal-activity-body .bb-rl-activity-item' ).data( 'bp-activity-id' );
			if ( activityModal.length > 0 && $( '.emojionearea-theatre.show' ).length > 0 ) {
				$( '.bb-activity-model-wrapper #ac-input-' + activityId ).data( 'emojioneArea' ).hidePicker();
			}

			var gifPicker = $( '.bb-rl-gif-media-search-dropdown-standalone.open' );
			if ( activityModal.length > 0 && gifPicker.length > 0 ) {
				gifPicker.removeClass( 'open' );
				activityModal.find( '.ac-reply-gif-button' ).removeClass( 'active' );
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

							if ( content_text !== '' || content.indexOf( 'bb-rl-emojioneemoji' ) >= 0 ) {
								$activity_comment_input.closest( 'form' ).addClass( 'has-content' );
							} else {
								$activity_comment_input.closest( 'form' ).removeClass( 'has-content' );
							}
						},

						picker_show: function () {
							$( this.button[ 0 ] ).closest( '.bb-rl-post-emoji' ).addClass( 'active' );
							$( '.emojionearea-theatre' ).removeClass( 'hide' ).addClass( 'show' );
						},

						picker_hide: function () {
							$( this.button[ 0 ] ).closest( '.bb-rl-post-emoji' ).removeClass( 'active' );
							$( '.emojionearea-theatre' ).removeClass( 'show' ).addClass( 'hide' );
						},
					},
				}
			);
		},

		injectFiles: function ( data ) {
			var commonData  = data.commonData,
			    id          = data.id,
			    self        = data.self,
			    fileType    = data.fileType, // 'media', 'document', or 'video'
			    dropzoneObj = data.dropzoneObj;

			// Iterate through the files and inject them.
			commonData.forEach( function ( file, index ) {
				var editData = {};
				if ( 0 < parseInt( id, 10 ) ) {
					editData = {
						id        : file.attachment_id || file.doc_id || file.vid_id,
						name      : file.name || file.full_name,
						saved     : true,
						group_id  : file.group_id || 0,
						menu_order: file.menu_order || 0,
						uuid      : file.attachment_id || file.doc_id || file.vid_id,
						url       : file.url,
						type      : fileType,
					};
					if ( 'media' === fileType ) {
						editData.media_id = file.id;
						editData.thumb    = file.thumb || '';
						editData.album_id = file.album_id || 0;
					} else if ( 'document' === fileType ) {
						editData.document_id = file.id;
						editData.size        = file.size || 0;
						editData.full_name   = file.full_name || file.name;
						editData.folder_id   = file.folder_id || 0;
						editData.svg_icon    = file.svg_icon || '';
					} else if ( 'video' === fileType ) {
						editData.video_id = file.id;
						editData.thumb    = file.thumb || '';
						editData.size     = file.size || 0;
						editData.album_id = file.album_id || 0;
					}
				} else {
					editData = {
						id        : file.id || file.doc_id || file.vid_id,
						name      : file.name || file.full_name,
						saved     : false,
						group_id  : file.group_id || 0,
						menu_order: file.menu_order || 0,
						uuid      : file.id || file.doc_id || file.vid_id,
						url       : file.url,
						type      : fileType,
					};

					if ( 'media' === fileType ) {
						editData.thumb    = file.thumb || '';
						editData.album_id = file.album_id || 0;
					} else if ( 'document' === fileType ) {
						editData.size      = file.size || 0;
						editData.full_name = file.full_name || file.name;
						editData.folder_id = file.folder_id || 0;
						editData.svg_icon  = file.svg_icon || '';
					} else if ( 'video' === fileType ) {
						editData.thumb    = file.thumb || '';
						editData.album_id = file.album_id || 0;
						editData.size     = file.size || 0;
					}
				}

				var mockFile = {
					name    : file.name || file.full_name,
					size    : file.size || 0,
					accepted: true,
					kind    : 'media' === fileType ? 'image' : 'file',
					upload  : {
						filename: file.name || file.full_name,
						uuid    : file.attachment_id || file.doc_id || file.vid_id,
					},
					dataURL : file.url,
					id      : file.attachment_id || file.doc_id || file.vid_id,
				};

				if ( 'media' === fileType ) {
					mockFile.media_edit_data = editData;
				} else if ( 'document' === fileType ) {
					mockFile.document_edit_data = editData;
					mockFile.svg_icon           = ! _.isUndefined( file.svg_icon ) ? file.svg_icon : '';
				} else if ( 'video' === fileType ) {
					mockFile.video_edit_data = editData;
				}

				if ( dropzoneObj ) {
					dropzoneObj.files.push( mockFile );
					dropzoneObj.emit( 'addedfile', mockFile );

					// Handle thumbnails for media files.
					if ( 'media' === fileType ) {
						if ( 'undefined' !== typeof BP_Nouveau.is_as3cf_active && '1' === BP_Nouveau.is_as3cf_active ) {
							$( dropzoneObj.files[index].previewElement ).find( 'img' ).attr( 'src', file.thumb );
							dropzoneObj.emit( 'thumbnail', file.thumb );
						} else {
							self.createThumbnailFromUrl( mockFile );
						}
					}

					dropzoneObj.emit( 'complete', mockFile );
				}
			} );
		},

		destroyUploader: function ( type, comment_id ) {
			var self = this;

			// Map uploader types to their respective properties and IDs.
			var uploaderConfig = {
				media   : {
					obj          : self.dropzone_obj,
					dropzone     : self.dropzone_media,
					uploaderId   : '#ac-reply-post-media-uploader-',
					buttonId     : '#ac-reply-media-button-',
					additionalIds: ['#ac-reply-post-media-uploader-1-']
				},
				document: {
					obj       : self.dropzone_document_obj,
					dropzone  : self.dropzone_document,
					uploaderId: '#ac-reply-post-document-uploader-',
					buttonId  : '#ac-reply-document-button-'
				},
				video   : {
					obj       : self.dropzone_video_obj,
					dropzone  : self.dropzone_video,
					uploaderId: '#ac-reply-post-video-uploader-',
					buttonId  : '#ac-reply-video-button-'
				},
				gif     : {
					buttonId       : '#ac-reply-gif-button-',
					dropdownClass  : '.gif-media-search-dropdown',
					standaloneClass: '.gif-media-search-dropdown-standalone',
					modelProperty  : 'gif_data',
					containerId    : '#ac-reply-post-gif-'
				}
			};

			// Get the configuration for the current type.
			var config = uploaderConfig[type];
			if ( ! config ) {
				return;
			}

			// Handle Dropzone destroy and cleanup.
			if ( 'gif' !== type ) {
				var dropzoneObj = config.obj;
				if ( ! _.isNull( dropzoneObj ) ) {
					dropzoneObj.destroy();
					$( config.uploaderId + comment_id ).html( '' );
					if ( config.additionalIds ) {
						config.additionalIds.forEach( function ( id ) {
							$( id + comment_id ).html( '' );
						} );
					}
				}
				config.dropzone = [];
				$( config.uploaderId + comment_id ).removeClass( 'open' ).addClass( 'closed' );
			}
			$( config.buttonId + comment_id ).removeClass( 'active' );

			// Handle GIF-specific logic.
			if ( 'gif' === type ) {
				$( config.buttonId + comment_id ).closest( '.post-gif' ).find( config.dropdownClass ).removeClass( 'open' ).empty();
				$( config.standaloneClass ).removeClass( 'open' ).empty();

				if ( ! _.isUndefined( this.models[comment_id] ) ) {
					var model = this.models[comment_id];
					model.set( config.modelProperty, {} );
					$( config.containerId + comment_id ).find( '.activity-attached-gif-container' ).removeAttr( 'style' );
				}
			}
		},
	};

	// Launch BP Nouveau Activity.
	bp.Nouveau.Activity.start();

} )( bp, jQuery );

