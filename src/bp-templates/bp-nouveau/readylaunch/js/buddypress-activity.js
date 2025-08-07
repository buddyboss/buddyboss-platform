/* jshint browser: true */
/* global bp, BP_Nouveau, Dropzone, videojs, bp_media_dropzone */
/* @version [BBVERSION] */
window.bp = window.bp || {};

( function ( exports, $ ) {

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

	var bpNouveau             = BP_Nouveau,
	    bbRlAjaxUrl           = bpNouveau.ajaxurl,
	    bbRlMedia             = bpNouveau.media,
	    bbRlIsSendAjaxRequest = bpNouveau.is_send_ajax_request,
	    bbRlActivity          = bpNouveau.activity,
	    bbRlNewest            = bpNouveau.newest,
	    bbbRlShowXComments    = bpNouveau.show_x_comments,
	    bbRlConfirm           = bpNouveau.confirm;

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
		start: function () {
			bp.Nouveau.Activity.LocalizeActivityVars = bpNouveau;

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

			if ( 'undefined' !== typeof window.Dropzone && 'undefined' !== typeof bbRlMedia ) {

				// Set up dropzones auto discover to false so it does not automatically set dropzones.
				window.Dropzone.autoDiscover = false;

				this.dropzone_options = {
					url                         : bbRlAjaxUrl,
					timeout                     : 3 * 60 * 60 * 1000,
					dictFileTooBig              : bbRlMedia.dictFileTooBig,
					dictDefaultMessage          : bbRlMedia.dropzone_media_message,
					acceptedFiles               : 'image/*',
					autoProcessQueue            : true,
					addRemoveLinks              : true,
					uploadMultiple              : false,
					maxFiles                    : 'undefined' !== typeof bbRlMedia.maxFiles ? bbRlMedia.maxFiles : 10,
					maxFilesize                 : 'undefined' !== typeof bbRlMedia.max_upload_size ? bbRlMedia.max_upload_size : 2,
					dictMaxFilesExceeded        : bbRlMedia.media_dict_file_exceeded,
					dictCancelUploadConfirmation: bbRlMedia.dictCancelUploadConfirmation,
					maxThumbnailFilesize        : 'undefined' !== typeof bbRlMedia.max_upload_size ? bbRlMedia.max_upload_size : 2,
				};

				// if defined, add custom dropzone options.
				if ( 'undefined' !== typeof bbRlMedia.dropzone_options ) {
					Object.assign( this.dropzone_options, bbRlMedia.dropzone_options );
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
			this.activityHasUpdates    = false; // Flag to track any activity updates.
			this.activityPinHasUpdates = false; // Flag to track activity pin updates.

			// Member and Group activity topic filter wrapper.
			if( $( '.activity-topic-selector' ).length && $( '.activity-head-bar' ).length && ! $( '.activity-topic-selector' ).parent().hasClass( 'bb-rl-activity-filters-container') ) {
				$( '.activity-topic-selector, .activity-topic-selector + .subnav-filters, .activity-topic-selector + .subnav-filters + .activity-head-bar' ).wrapAll( '<div class="bb-rl-activity-filters-container"></div>' );
				$( '<div class="bb-rl-activity-filters-separator"></div>' ).insertAfter( '.activity-topic-selector' );
			}
		},

		/**
		 * [addListeners description]
		 */
		addListeners: function () {
			var $body           = $( 'body' );
			var $bpElem         = $( '#buddypress' );
			var $document       = $( document );
			var $activityStream = $( '#bb-rl-activity-modal > .bb-rl-modal-activity-body' );

			// HeartBeat listeners.
			if ( ! $body.hasClass( 'activity-singular' ) ) {
				$bpElem.on( 'bp_heartbeat_send', this.heartbeatSend.bind( this ) );
			}
			$bpElem.on( 'bp_heartbeat_tick', this.heartbeatTick.bind( this ) );

			// Inject Activities.
			$bpElem.find( '[data-bp-list="activity"]:not( #bb-schedule-posts_modal [data-bp-list="activity"] )' ).on( 'click', 'li.load-newest, li.bb-rl-load-more', this.injectActivities.bind( this ) );

			// Highlight new activities and clean up the stream.
			$bpElem.on( 'bp_ajax_request', '[data-bp-list="activity"]', this.scopeLoaded.bind( this ) );

			// Activity comments effect.
			$( '#bb-rl-activity-stream' ).on( 'click', '.acomments-view-more', this.showActivity );
			$body.on( 'click', '.bb-rl-close-action-popup', this.closeActivity );

			$document.on(
				'activityModalOpened',
				function ( event, data ) {
					var activityId = data.activityId;

					$document.on(
						'click',
						function ( event ) {
							if (
							$( '#bb-rl-activity-modal:visible' ).length > 0 &&
							0 === $( '#bb-rl-activity-form-placeholder:visible' ).length &&
							! $( event.target ).closest( '#bb-rl-activity-modal' ).length &&
							! $( event.target ).closest( '.bb-rl-gif-media-search-dropdown-standalone' ).length &&
							! $( event.target ).closest( '.bb-rl-emojionearea-theatre' ).length &&
							! $( event.target ).hasClass( 'dz-hidden-input' ) // Dropzone file input for media upload which is outside modal.
							) {
								this.closeActivity( event );
								this.activitySyncOnModalClose( event, activityId );
							}
						}.bind( this )
					);
				}.bind( this )
			);

			// Activity actions.
			var activityParentSelectors = '[data-bp-list="activity"], #bb-rl-activity-modal, #bb-rl-media-model-container .bb-rl-activity-list';
			$bpElem.find( activityParentSelectors ).on( 'click', '.activity-item', bp.Nouveau, this.activityActions.bind( this ) );
			$bpElem.find( activityParentSelectors ).on( 'click', '.activity-privacy>li.bb-edit-privacy a', bp.Nouveau, this.activityPrivacyRedirect.bind( this ) );
			$bpElem.find( activityParentSelectors ).on( 'click', '.activity-privacy>li:not(.bb-edit-privacy)', bp.Nouveau, this.activityPrivacyChange.bind( this ) );
			$bpElem.find( activityParentSelectors ).on( 'click', 'span.privacy', bp.Nouveau, this.togglePrivacyDropdown.bind( this ) );

			$( '#bb-rl-media-model-container .bb-rl-activity-list' ).on( 'click', '.activity-item', bp.Nouveau, this.activityActions.bind( this ) );
			$( '.bb-rl-activity-model-wrapper' ).on( 'click', '.bb-rl-ac-form-placeholder', bp.Nouveau, this.activityRootComment.bind( this ) );
			$document.keydown( this.commentFormAction );
			$document.click( this.togglePopupDropdown );

			// forums.
			$( '#buddypress [data-bp-list="activity"], #bb-rl-media-model-container .bb-rl-activity-list, #activity-modal .bb-rl-activity-list, .bb-rl-modal-activity-footer' ).on( 'click', '.ac-reply-media-button', this.openCommentsMediaUploader.bind( this ) );
			var forumSelectors       = '.bb-rl-ac-reply-media-button, .bb-rl-ac-reply-document-button, .bb-rl-ac-reply-video-button, .bb-rl-ac-reply-gif-button';
			var forumParentSelectors = '[data-bp-list="activity"], #bb-rl-media-model-container .bb-rl-activity-list, #bb-rl-activity-modal .bb-rl-activity-list, .bb-rl-modal-activity-footer';
			$bpElem.find( forumParentSelectors ).on(
				'click',
				forumSelectors,
				function ( event ) {
					var eventCurrentTarget = $( event.currentTarget );
					if ( eventCurrentTarget.hasClass( 'bb-rl-ac-reply-media-button' ) ) {
						this.openCommentsMediaUploader( event );
					} else if ( eventCurrentTarget.hasClass( 'bb-rl-ac-reply-document-button' ) ) {
						this.openCommentsDocumentUploader( event );
					} else if ( eventCurrentTarget.hasClass( 'bb-rl-ac-reply-video-button' ) ) {
						this.openCommentsVideoUploader( event );
					} else if ( eventCurrentTarget.hasClass( 'bb-rl-ac-reply-gif-button' ) ) {
						this.openGifPicker( event );
					}
				}.bind( this )
			);

			// Reaction actions.
			$document.on( 'click', '.activity-state-popup_overlay', bp.Nouveau, this.closeActivityState.bind( this ) );
			$document.on( 'click', '.activity-state-popup .activity-state-popup_tab_panel a', this.ReactionStatePopupTab );

			// Activity autoload.
			if ( ! _.isUndefined( bbRlActivity.params.autoload ) ) {
				$( window ).scroll( this.loadMoreActivities );
			}

			// Activity filter.
			$( document ).on( 'click', '.bb-subnav-filters-container .subnav-filters-opener', this.openActivityFilter.bind( this ) );
			$( document ).on( 'click', this.closeActivityFilter.bind( this ) );
			$( document ).on( 'click', '.bb-subnav-filters-container .subnav-filters-modal a', this.filterActivity.bind( this ) );

			$( '.bb-rl-activity-model-wrapper, .bb-rl-media-model-wrapper' ).on( 'click', '.acomments-view-more', this.viewMoreComments.bind( this ) );
			$document.on(
				'click',
				'#bb-rl-activity-stream .bb-rl-activity-comments .view-more-comments',
				function ( e ) {
					e.preventDefault();
					$( this ).parents( 'li.activity-item' ).find( '.bb-rl-activity-comments > ul > li.acomments-view-more, .bb-rl-activity-comments > .activity-actions > ul > li.acomments-view-more' ).trigger( 'click' );
				}
			);

			$document.on(
				'click',
				'#bb-rl-activity-stream .activity-state-comments > .comments-count, #bb-rl-activity-stream .activity-meta > .generic-button .acomment-reply',
				function ( e ) {
					e.preventDefault();
					var liElem     = $( this ).closest( '.activity-item' );
					var activityId = liElem.data('bp-activity-id');
					if ( '' === liElem.find( '.bb-rl-activity-comments > ul' ).html() ) {
						bp.Nouveau.Activity.launchActivityPopup( activityId, '' );
					}
				}
			);

			$activityStream.on( 'scroll', this.autoloadMoreComments.bind( this ) );
			$activityStream.on( 'scroll', this.discardGifEmojiPicker.bind( this ) );

			$( '.bb-rl-activity-model-wrapper .bb-rl-model-close-button' ).on( 'click', this.activitySyncOnModalClose.bind( this ) );

			// Validate media access for comment forms.
			var initializeForms = function () {
				$( '.ac-form.not-initialized' ).each(
					function () {
						var form   = $( this );
						var target = form.find( '.ac-textarea' );
						bp.Nouveau.Activity.toggleMultiMediaOptions( form, target );
					}
				);
			};

			if ( bbRlIsSendAjaxRequest === '1' ) {
				$bpElem.on(
					'bp_ajax_request',
					'[data-bp-list="activity"]',
					function () {
						setTimeout( initializeForms, 1000 );
					}
				);
			} else {
				setTimeout( initializeForms, 1000 );
			}

			// Wrap Activity Topics
			bp.Nouveau.wrapNavigation( '.activity-topic-selector ul', 120 );
		},

		openActivityFilter: function ( e ) {
			e.preventDefault();
			$( '.bb-subnav-filters-container:not(.bb-subnav-filters-search)' ).removeClass( 'active' ).find( '.subnav-filters-opener' ).attr( 'aria-expanded', 'false' );
			var $parent = $( e.currentTarget ).parent( '.bb-subnav-filters-container' );
			$parent.addClass( 'active' ).find( '.subnav-filters-opener' ).attr( 'aria-expanded', 'true' );

			if ( $parent.find( 'input[type="search"]' ).length ){
				$parent.find( 'input[type="search"]' ).focus();
			}
		},

		closeActivityFilter: function ( e ) {
			if ( ! $( e.target ).closest( '.bb-subnav-filters-container' ).length ) {
				$.each( $( '.bb-subnav-filters-container' ), function() {
					if ( $( this ).hasClass( 'bb-subnav-filters-search' ) ) {
						if( $( this ).find( 'input[name="activity_search"]' ).val() === '' ) {
							$( this ) .removeClass( 'active' ) .find( '.subnav-filters-opener' ) .attr( 'aria-expanded', 'false' );
						}
					} else {
						$( this ) .removeClass( 'active' ) .find( '.subnav-filters-opener' ) .attr( 'aria-expanded', 'false' );
					}
				});
			}
		},

		filterActivity: function ( e ) {
			e.preventDefault();
			var $this = $( e.currentTarget );
			var $parent = $this.closest( '.bb-subnav-filters-container' );
			$this.parent().addClass( 'selected' ).siblings().removeClass( 'selected' );
			$parent.removeClass( 'active' ).find( '.subnav-filters-opener' ).attr( 'aria-expanded', 'false' );
			$parent.find( '.subnav-filters-opener .selected' ).text( $this.text() );

			// Reset the pagination for the scope.
			bp.Nouveau.Activity.current_page = 1;

			// Filter activity with below selections
			var objectNavParent = bp.Nouveau.objectNavParent;
			var object          = 'activity';
			var filter          = $( '#buddypress' ).find( '[data-bp-filter="' + object + '"]' ).first().val();
			var scope           = '';
			var search_terms    = '';
			var order           = '';
			var extras          = '';
			var save_scope	    = false;
			var user_timeline   = false;

			if ( $( objectNavParent + ' [data-bp-object].selected' ).length ) {
				scope = $( objectNavParent + ' [data-bp-object].selected' ).data( 'bp-scope' );

				if( $this.closest( '#bb-subnav-filter-show' ).length ) {
					save_scope = true;

					if( $( 'body' ).hasClass( 'my-activity') ) {
						save_scope = false;
						user_timeline = true;
					}
				}
			}

			var searchElement = $( '#buddypress [data-bp-search="' + object + '"] input[type=search]' );
			if ( searchElement.length ) {
				search_terms = searchElement.val();
			}

			if ( $( objectNavParent + ' [data-bp-order]' ).length ) {
				order = $( objectNavParent + ' [data-bp-order="' + object + '"].selected' ).data( 'bp-orderby' );
			}

			var objectData = bp.Nouveau.getStorage( 'bp-' + object );

			// Notifications always need to start with Newest ones.
			if ( undefined !== objectData.extras && 'notifications' !== object ) {
				extras = objectData.extras;
			}

			// On filter update reset last_recorded.
			bp.Nouveau.Activity.heartbeat_data.last_recorded = 0;

			var queryData = {
				object: object,
				scope: scope,
				filter: filter,
				search_terms: search_terms,
				extras: extras,
				order_by: order,
				save_scope: save_scope,
				event_element: $this,
			};

			if( user_timeline ) {
				queryData.user_timeline = true;
			}

			bp.Nouveau.objectRequest( queryData );
		},

		/**
		 * [heartbeatSend description]
		 *
		 * @return {[type]}       [description]
		 * @param event
		 * @param data
		 */
		heartbeatSend: function ( event, data ) {
			var $allActivities      = $( '#buddypress [data-bp-list] [data-bp-activity-id]' );
			var $unPinnedActivities = $allActivities.not( '.bb-pinned' );

			// First recorded timestamp: unpinned items.
			var $firstUnpinned                 = $unPinnedActivities.first();
			this.heartbeat_data.first_recorded = $firstUnpinned.data( 'bp-timestamp' ) || 0;

			if ( $( bp.Nouveau.objectNavParent + ' [data-bp-orderby=date_updated].selected' ).length ) {
				var first_unpinned_activity = $( '#buddypress [data-bp-list] [data-bp-activity-id]:not(.bb-pinned)' ).first();
				if ( 'undefined' !== typeof first_unpinned_activity.data( 'bb-updated-timestamp' ) ) {
					this.heartbeat_data.first_recorded = first_unpinned_activity.data( 'bb-updated-timestamp' );
				} else if (
					first_unpinned_activity.length &&
					'undefined' !== typeof first_unpinned_activity.data( 'bp-activity' ).date_updated &&
					'' !== first_unpinned_activity.data( 'bp-activity' ).date_updated
				) {
					// convert to timestamp.
					var dateString = first_unpinned_activity.data( 'bp-activity' ).date_updated ;

					// Convert directly to a Date object in UTC by appending 'Z' (UTC designator).
					var utcDate = new Date( dateString.replace( ' ', 'T') + 'Z' );

					// Get the Unix timestamp in seconds.
					this.heartbeat_data.first_recorded = utcDate.getTime() / 1000;
				} else {
					this.heartbeat_data.first_recorded = first_unpinned_activity.data( 'bp-timestamp' ) || 0;
				}
			} else {
				this.heartbeat_data.first_recorded = $( '#buddypress [data-bp-list] [data-bp-activity-id]:not(.bb-pinned)' ).first().data( 'bp-timestamp' ) || 0;
			}

			// Handle the first item is already latest and pinned.
			var firstActivityTimestamp = $allActivities.first().data( 'bp-timestamp' ) || 0;
			if ( firstActivityTimestamp > this.heartbeat_data.first_recorded ) {
				this.heartbeat_data.first_recorded = firstActivityTimestamp;
			}

			if ( 0 === this.heartbeat_data.last_recorded || this.heartbeat_data.first_recorded > this.heartbeat_data.last_recorded ) {
				this.heartbeat_data.last_recorded = this.heartbeat_data.first_recorded;
			}

			data.bp_activity_last_recorded = this.heartbeat_data.last_recorded;

			var $searchInput = $( '#buddypress .activity-head-bar .activity-search input[type=search]' );
			if ( $searchInput.length ) {
				data.bp_activity_last_recorded_search_terms = $searchInput.val();
			}

			$.extend(data, {
				bp_heartbeat: (function() {
					var heartbeatData;

					// Check if the page is a user activity page.
					var $bodyElem = $( 'body.my-activity:not(.activity-singular)' );
					if ( $bodyElem.length ) {
						heartbeatData = bp.Nouveau.getStorage( 'bp-user-activity' ) || { scope: 'just-me' };
					} else {

						// Otherwise, retrieve the activity data.
						heartbeatData = bp.Nouveau.getStorage( 'bp-activity' ) || { scope: 'all' };

						// If the page is a single group activity page, set the scope to 'all'.
						if ( $( 'body.activity.buddypress.groups.single-item' ).length ) {
							heartbeatData.scope = 'all';
						}
					}

					if ( $( bp.Nouveau.objectNavParent + ' #bb-subnav-filter-show [data-bp-scope].selected' ).length ) {
						var scope = $( bp.Nouveau.objectNavParent + ' #bb-subnav-filter-show [data-bp-scope].selected' ).data( 'bp-scope' );

						// Heartbeat check the value from the available.
						if ( 'undefined' === typeof heartbeatData.scope || heartbeatData.scope !== scope ) {
							heartbeatData.scope = scope;

							if ( 'undefined' !== BP_Nouveau.is_send_ajax_request && '1' === BP_Nouveau.is_send_ajax_request ) {

								// Add to the storage if page request 2.
								if ( $bodyElem.length ) {
									bp.Nouveau.setStorage( 'bp-user-activity', 'scope', scope );
								} else {
									bp.Nouveau.setStorage( 'bp-activity', 'scope', scope );
								}
							}
						}
					}

					// Add `order_by` only if it's not already set.
					if ( $( bp.Nouveau.objectNavParent + ' [data-bp-order].selected' ).length ) {
						heartbeatData.order_by = $( bp.Nouveau.objectNavParent + ' [data-bp-order].selected' ).data( 'bp-orderby' );
					}

					// Get the current topic ID from storage.
					var topicId = bp.Nouveau.getStorage( 'bp-activity', 'topic_id' );
					if ( topicId ) {
						data.bp_heartbeat          = data.bp_heartbeat || {};
						data.bp_heartbeat.topic_id = topicId;
					}

					return heartbeatData;
				})()
			});
		},

		/**
		 * [heartbeatTick description]
		 *
		 * @return {[type]}                [description]
		 * @param event
		 * @param data
		 */
		heartbeatTick: function ( event, data ) {
			var newestActivitiesCount, newestActivities, objects = bp.Nouveau.objects,
				scope = bp.Nouveau.getStorage( 'bp-activity', 'scope' ), self = this,
				topicId = bp.Nouveau.getStorage( 'bp-activity', 'topic_id' );

			// Only proceed if we have the newest activities.
			if ( undefined === data || ! data.bp_activity_newest_activities ) {
				return;
			}

			var activitiesData = data.bp_activity_newest_activities;

			this.heartbeat_data.newest        = $.trim( activitiesData.activities ) + this.heartbeat_data.newest;
			this.heartbeat_data.last_recorded = Number( activitiesData.last_recorded );

			// Parse activities.
			newestActivities = $( this.heartbeat_data.newest ).filter( '.activity-item' );

			// If we have a topic filter active, only show activities matching that topic.
			if ( topicId ) {
				newestActivities       = newestActivities.filter( function () {
					var bpActivity      = this.dataset.bpActivity ? JSON.parse( this.dataset.bpActivity ) : null;
					var activityTopicId = bpActivity && typeof bpActivity.topic_id !== 'undefined' ? bpActivity.topic_id : null;
					return activityTopicId && parseInt( activityTopicId ) === parseInt( topicId );
				} );
				newestActivitiesCount = newestActivities.length;
			}

			// Count them.
			newestActivitiesCount = Number( newestActivities.length );

			/**
			 * It's not a regular object, but we need it!
			 * so let's add it temporary.
			 */
			objects.push( 'mentions' );

			/**
			 * On the All Members tab, we need to know what these activities are about
			 * in order to update all the other tabs dynamic span
			 */
			if ( 'all' === scope ) {

				$.each(
					newestActivities,
					function ( a, activity ) {
						activity = $( activity );

						$.each(
							objects,
							function ( o, object ) {
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
					function ( a, activity ) {
						self.heartbeat_data.highlights[ scope ].push( $( activity ).data( 'bp-activity-id' ) );
					}
				);
			}

			$.each(
				objects,
				function ( o, object ) {
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

			// Add information about the number of newest activities inside the document's title.
			$( document ).prop( 'title', '(' + newestActivitiesCount + ') ' + this.heartbeat_data.document_title );

			// Update the Load Newest li if it already exists.
			var $bpElemList = $( '#buddypress [data-bp-list="activity"]' );
			var $aElem      = $bpElemList.find( '.load-newest a' );
			if ( $bpElemList.first().hasClass( 'load-newest' ) ) {
				var newest_link = $aElem.html();
				$aElem.html( newest_link.replace( /([0-9]+)/, newestActivitiesCount ) );

				// Otherwise, add it.
			} else {
				$bpElemList.find( 'ul.bb-rl-activity-list' ).prepend( '<li class="load-newest"><a href="#newest">' + bbRlNewest + ' (' + newestActivitiesCount + ')</a></li>' );
			}

			$bpElemList.find( 'li.load-newest' ).trigger( 'click' );

			/**
			 * Finally, trigger a pending event containing the activity heartbeat data
			 */
			$bpElemList.trigger( 'bp_heartbeat_pending', this.heartbeat_data );

			if ( 'undefined' !== typeof bp.Nouveau ) {
				bp.Nouveau.reportPopUp();
			}
		},

		/**
		 * [injectQuery description]
		 *
		 * @return {[type]}       [description]
		 * @param event
		 */
		injectActivities: function ( event ) {
			var store = $( 'body.my-activity:not(.activity-singular)' ).length ? bp.Nouveau.getStorage( 'bp-user-activity' ) : bp.Nouveau.getStorage( 'bp-activity' ),
				scope = store.scope || null, filter = store.filter || null;

			// Load the newest activities.
			if ( $( event.currentTarget ).hasClass( 'load-newest' ) ) {
				// Stop event propagation.
				event.preventDefault();

				$( event.currentTarget ).remove();

				/**
				 * If a plugin is updating the recorded_date of an activity,
				 * it will be loaded as a new one. We need to look in the
				 * stream and eventually remove similar ids to avoid "double".
				 */
				var activities = $.parseHTML( this.heartbeat_data.newest );

				$.each(
					activities,
					function ( a, activity ) {
						if ( 'LI' === activity.nodeName && $( activity ).hasClass( 'just-posted' ) ) {
							var $elem = $( '#' + $( activity ).prop( 'id' ) );
							if ( $elem.length ) {
								$elem.remove();
							}
						}

					}
				);

				var $activityList = $( event.delegateTarget ).find( '.bb-rl-activity-list' );
				var firstActivity = $activityList.find( '.activity-item' ).first();
				if ( firstActivity.length > 0 && firstActivity.hasClass( 'bb-pinned' ) ) {

					// Add after pinned post.
					$( firstActivity ).after( this.heartbeat_data.newest ).find( 'li.activity-item' ).each( bp.Nouveau.hideSingleUrl ).trigger( 'bp_heartbeat_prepend', this.heartbeat_data );

				} else {

					// Now the stream is cleaned, prepend newest.
					$activityList.prepend( this.heartbeat_data.newest ).find( 'li.activity-item' ).each( bp.Nouveau.hideSingleUrl ).trigger( 'bp_heartbeat_prepend', this.heartbeat_data );
				}

				// Reset the newest activities now they're displayed.
				this.heartbeat_data.newest = '';

				// Reset the member tab dynamic span id it's the current one.
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

				// replace fake image with original image by faking scroll event to call bp.Nouveau.lazyLoad.
				jQuery( window ).scroll();

				// Load more activities.
			} else if ( $( event.currentTarget ).hasClass( 'bb-rl-load-more' ) ) {
				var nextPage = ( Number( this.current_page ) ) + 1, self = this, searchTerms = '';

				// Stop event propagation.
				event.preventDefault();

				var targetEl = $( event.currentTarget );
				targetEl.find( 'a' ).first().addClass( 'loading' );

				// Reset the just posted.
				this.just_posted = [];

				// Now set it.
				$( event.delegateTarget ).children( '.just-posted' ).each(
					function () {
						self.just_posted.push( $( this ).data( 'bp-activity-id' ) );
					}
				);

				var $searchElem = $( '#buddypress .activity-search.bp-search input[type=search]' );
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
					function ( response ) {
						if ( true === response.success ) {
							targetEl.remove();

							// Update the current page.
							self.current_page = nextPage;

							// replace fake image with original image by faking scroll event to call bp.Nouveau.lazyLoad.
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
		 * @return {[type]}       [description]
		 * @param event
		 */
		hideComments: function ( event ) {
			var comments = $( event.target ).find( '.bb-rl-activity-comments' ),
				activityItem, commentItems, commentCount, commentParents;

			if ( ! comments.length ) {
				return;
			}

			comments.each(
				function ( c, comment ) {
					commentParents = $( comment ).children( 'ul' ).not( '.bb-rl-conflict-activity-ul-li-comment' );
					commentItems   = $( commentParents ).find( 'li' ).not( $( '.bb-rl-document-action-class, .bb-rl-media-action-class, .bb-rl-video-action-class' ) );

					if ( ! commentItems.length ) {
						return;
					}

					// Check if the URL has specific comment to show.
					if ( $( 'body' ).hasClass( 'activity-singular' ) && '' !== window.location.hash && $( window.location.hash ).length && 0 !== $( window.location.hash ).closest( '.bb-rl-activity-comments' ).length ) {
						return;
					}

					// Get the activity id.
					activityItem = $( comment ).closest( '.activity-item' );

					// Get the comment count.
					commentCount = $( '#acomment-comment-' + activityItem.data( 'bp-activity-id' ) + ' span.comment-count' ).html() || ' ';

					// Keep latest 5 comments.
					commentItems.each(
						function ( i, item ) {
							if ( i < commentItems.length - 4 ) {

								// Prepend a link to display all.
								if ( ! i ) {
									$( item ).parent( 'ul' ).before( '<div class="show-all"><button class="text-button" type="button" data-bp-show-comments-id="#' + activityItem.prop( 'id' ) + '/show-all/">' + bbbRlShowXComments + '</button></div>' );
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
		showActivity: function ( event ) {
			event.preventDefault();
			var currentTargetList = $( event.currentTarget ).parent(),
				parentId          = currentTargetList.data( 'parent_comment_id' ),
				activityId        = $( currentTargetList ).data( 'activity_id' );

			$( document ).trigger( 'activityModalOpened', { activityId: activityId } );

			$( event.currentTarget ).parents( '.bb-rl-activity-comments' ).find( '.ac-form' ).each(
				function () {
					var form         = $( this );
					var commentsList = $( this ).closest( '.bb-rl-activity-comments' );
					var commentItem  = $( this ).closest( '.comment-item' );
					// Reset emojionearea.
					form.find( '.bb-rl-post-elements-buttons-item.bb-rl-post-emoji' ).removeClass( 'active' ).empty( '' );

					bp.Nouveau.Activity.resetActivityCommentForm( form, 'hardReset' );
					commentsList.append( form );
					commentItem.find( '.bb-rl-acomment-display' ).removeClass( 'bb-rl-display-focus' );
					commentItem.removeClass( 'bb-rl-comment-item-focus' );
				}
			);

			bp.Nouveau.Activity.launchActivityPopup( activityId, parentId );
		},

		closeActivity: function ( event ) {
			event.preventDefault();
			var target     = $( event.target ),
				modal      = target.closest( '.bb-rl-activity-model-wrapper' ),
				footer     = modal.find( '.bb-rl-modal-activity-footer' ),
				activityId = modal.find( '.activity-item' ).data( 'bp-activity-id' ),
				form       = modal.find( '#ac-form-' + activityId );

			if ( form.length ) {
				bp.Nouveau.Activity.reinitializeActivityCommentForm( form );
			}

			if ( ! _.isUndefined( bbRlMedia ) && ! _.isUndefined( bbRlMedia.emoji ) ) {
				bp.Nouveau.Activity.initializeEmojioneArea( false, '', activityId );
			}

			modal.find( '#bb-rl-activity-modal' ).removeClass( 'bb-closed-comments' );

			modal.closest( 'body' ).removeClass( 'acomments-modal-open' );
			modal.hide();
			modal.find( 'ul.bb-rl-activity-list' ).empty();
			footer.removeClass( 'active' );
			footer.find( 'form.ac-form' ).remove();

			$( 'li#bb-rl-activity-' + activityId ).removeClass( 'has-comments' );
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
					function ( i, id ) {
						$activityStream.find( '[data-bp-activity-id="' + id + '"]' ).addClass( 'newest_mentions_activity' );
					}
				);

				// Reset mentions count.
				this.mentions_count = 0;
			} else if ( undefined !== this.heartbeat_data.highlights[data.scope] && this.heartbeat_data.highlights[data.scope].length ) {
				$.each(
					this.heartbeat_data.highlights[data.scope],
					function ( i, id ) {
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
				function ( s, count ) {
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
					$( '#buddypress #bb-rl-activity-stream .activity-item' ).removeClass( 'newest_' + data.scope + '_activity' );
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

			// Navigate to specific comment when there's e.g., #acomment123 in url.
			this.navigateToSpecificComment();

			// replace fake image with original image by faking scroll event to call bp.Nouveau.lazyLoad.
			setTimeout(
				function () {
					jQuery( window ).scroll();
				},
				200
			);
		},

		openEditActivityPopup: function () {
			if ( ! _.isUndefined( bbRlActivity.params.is_activity_edit ) && 0 < bbRlActivity.params.is_activity_edit ) {
				var activity_item = $( '#bb-rl-activity-' + bbRlActivity.params.is_activity_edit );
				if ( activity_item.length ) {
					var activity_data        = activity_item.data( 'bp-activity' );
					var activity_URL_preview = ( activity_item.data( 'link-url' ) ) !== '' ? activity_item.data( 'link-url' ) : null;

					if ( ! _.isUndefined( activity_data ) ) {
						bp.Nouveau.Activity.postForm.displayEditActivityForm( activity_data, activity_URL_preview );
					}
				}
			}
		},

		activityPrivacyChange: function ( event ) {
			var parent       = event.data,
				target       = $( event.target ),
				activityItem = $( event.currentTarget ).closest( '.activity-item' ),
				activity_id  = activityItem.data( 'bp-activity-id' );

			// Stop event propagation.
			event.preventDefault();

			var privacyValue = target.data( 'value' );
			if ( 'undefined' === typeof privacyValue || '' === $.trim( privacyValue ) ) { // Ensure that 'privacyValue' exists and isn't an empty string.
				return false;
			}

			activityItem.find( '.privacy' ).addClass( 'loading' );

			parent.ajax( { action: 'activity_update_privacy', 'id': activity_id, 'privacy': privacyValue }, 'activity' ).done(
				function ( response ) {
					activityItem.find( '.privacy' ).removeClass( 'loading' );

					if ( true === response.success ) {
						var $privacy = activityItem.find( '.privacy' );
						activityItem.find( '.activity-privacy li' ).removeClass( 'selected' );
						activityItem.find( '.privacy-wrap' ).attr( 'data-bp-tooltip', target.text() );
						target.addClass( 'selected' );
						$privacy.removeClass( 'public loggedin onlyme friends' ).addClass( privacyValue );

						if ( 'undefined' !== typeof response && 'undefined' !== typeof response.data && 'undefined' !== typeof response.data.video_symlink ) {

							// Cache selectors.
							var $documentDescriptionWrap = $( '.bb-rl-document-description-wrap' );
							var $documentTheatre         = $documentDescriptionWrap.find( '.bb-rl-open-document-theatre' );
							var $documentDetailWrap      = $( '.bb-rl-document-detail-wrap.bb-rl-document-detail-wrap-description-popup' );

							// Update attributes for document theater.
							if ( $documentDescriptionWrap.length && $documentTheatre.length ) {
								$documentTheatre.attr(
									{
										'data-video-preview': response.data.video_symlink,
										'data-extension'    : response.data.extension
									}
								);
							}

							// Update attributes for document detail wrap.
							if ( $documentDescriptionWrap.length && $documentDetailWrap.length ) {
								$documentDetailWrap.attr(
									{
										'data-video-preview': response.data.video_symlink,
										'data-extension'    : response.data.extension
									}
								);
							}

							if ( 'undefined' !== typeof videojs && response.data.video_js_id ) {
								var myPlayer = videojs( response.data.video_js_id );
								myPlayer.src(
									{
										type: response.data.video_extension,
										src : response.data.video_symlink
									}
								);
							}
						}

						// Update the edited text.
						if (
							'undefined' !== typeof response &&
							'undefined' !== typeof response.data &&
							'undefined' !== typeof response.data.edited_text &&
							'' !== response.data.edited_text
						) {

							if ( activityItem.find( '.activity-date span.bb-activity-edited-text' ).length ) {
								// Completely remove and replace the edited text with new content.
								activityItem.find( '.activity-date span.bb-activity-edited-text' ).replaceWith( response.data.edited_text );
							} else {
								// Append the edited text to the activity date.
								activityItem.find( '.activity-date' ).append( response.data.edited_text );
							}
						}

						bp.Nouveau.Activity.activityHasUpdates = true;
						bp.Nouveau.Activity.currentActivityId  = activity_id;
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
			var activityItem = $( event.currentTarget ).closest( '.activity-item' );

			// Stop event propagation.
			event.preventDefault();

			var privacyElement = activityItem.find( '.activity-privacy' );

			$( 'ul.activity-privacy' ).not( privacyElement ).removeClass( 'bb-open' ); // close other dropdowns.

			privacyElement.toggleClass( 'bb-open' );
		},
		/* jshint ignore:end */

		/**
		 * [activityActions description]
		 *
		 * @return {[type]}       [description]
		 * @param event
		 */
		activityActions: function ( event ) {
			var parent        = event.data,
			    target        = $( event.target ),
			    activityItem  = $( event.currentTarget ),
			    activityId    = activityItem.data( 'bp-activity-id' ),
			    stream        = $( event.delegateTarget ),
			    activityState = activityItem.find( '.activity-state' ),
			    commentsText  = activityItem.find( '.comments-count' ),
			    repliesText   = '',
			    self          = this,
			    $body         = $( 'body' );

			// Check if target is inside #bb-rl-activity-modal or media theater.
			var isInsideModal        = target.closest( '#bb-rl-activity-modal' ).length > 0;
			var isInsideMediaTheatre = target.closest( '.bb-rl-internal-model' ).length > 0;

			if ( isInsideModal ) {
				activityState = activityItem.closest( '#bb-rl-activity-modal' ).find( '.activity-state' );
				commentsText  = activityItem.closest( '#bb-rl-activity-modal' ).find( '.comments-count' );
				repliesText   = activityItem.closest( '#bb-rl-activity-modal' ).find( '.acomments-count' );
			}

			// In case the target is set to a `span` or `i` tag inside the link.
			if (
				$( target ).is( 'span' ) ||
				$( target ).is( 'i' ) ||
				$( target ).is( 'img' )
			) {
				target = $( target ).closest( 'a' );
			}

			// If emotion item exists, then take reaction id and update the target.
			var reactionId = 0;
			if (
				target.parent( '.ac-emotion_btn' ) &&
				! (
					target.hasClass( 'fav' ) || target.hasClass( 'unfav' )
				)
			) {
				reactionId = target.parents( '.ac-emotion_item' ).attr( 'data-reaction-id' );
			}

			// Favorite and unfavorite logic.
			if ( target.hasClass( 'fav' ) || target.hasClass( 'unfav' ) || reactionId > 0 ) {
				self.handleFavoriteUnfavorite(
					{
						event        : event,
						parent       : parent,
						target       : target,
						reactionId   : reactionId,
						activityState: activityState,
						bodyElem     : $body,
						stream       : stream,
						activityItem : activityItem,
					}
				);
			}

			// Deleting or spamming.
			if ( target.hasClass( 'delete-activity' ) || target.hasClass( 'acomment-delete' ) || target.hasClass( 'spam-activity' ) || target.hasClass( 'spam-activity-comment' ) ) {
				self.deleteActivity(
					{
						event        : event,
						parent       : parent,
						target       : target,
						activityState: activityState,
						activityItem : activityItem,
						commentsText : commentsText,
						repliesText  : repliesText,
						activityId   : activityId,
					}
				);
			}

			// Reading more.
			if ( target.closest( 'span' ).hasClass( 'activity-read-more' ) ) {
				self.readMoreActivity(
					{
						event       : event,
						target      : target,
						activityId  : activityId,
						parent      : parent,
						activityItem: activityItem,
					}
				);
			}

			// Initiate Comment Form.
			if ( target.hasClass( 'ac-form' ) && target.hasClass( 'not-initialized' ) ) {
				target.closest( '.activity-item' ).find( '.acomment-reply' ).eq( 0 ).trigger( 'click' );
			}

			// Displaying the comment form.
			var activityStateComments = target.closest( '#bb-rl-activity-modal' ).find( '.activity-state-comments' );
			if (
				(
					target.hasClass('activity-state-comments') &&
					(
						activityStateComments.length > 0 ||
						'' !== target.closest( '.activity-item' ).find( '.bb-rl-activity-comments > ul' ).html()
					)
				) ||
				target.hasClass( 'acomment-reply' ) ||
				target.parent().hasClass( 'acomment-reply' ) ||
				target.hasClass( 'acomment-edit' )
			) {
				self.displayingTheCommentForm(
					{
						event               : event,
						target              : target,
						activityId          : activityId,
						isInsideModal       : isInsideModal,
						isInsideMediaTheatre: isInsideMediaTheatre,
						self                : self,
					}
				);
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

				['media', 'document'].forEach(
					function ( type ) {
						self.destroyUploader( type, activityId );
					}
				);

				// Rest the Comment Form.
				self.resetActivityCommentForm( $form, 'hardReset' );

				// Stop event propagation.
				event.preventDefault();
			}

			// Submitting comments and replies.
			if ( 'ac_form_submit' === target.prop( 'name' ) ) {

				// Prevent duplicate submissions.
				if ( target.prop( 'disabled' ) ) {
					event.preventDefault();
					return;
				}

				self.submitActivityComment(
					{
						event        : event,
						parent       : parent,
						target       : target,
						activityId   : activityId,
						isInsideModal: isInsideModal,
						activityState: activityState,
						commentsText : commentsText,
						activityItem : activityItem,
						self         : self,
					}
				);
			}

			// Edit the activity.
			if ( target.hasClass( 'edit' ) && target.hasClass( 'edit-activity' ) ) {
				// Stop event propagation.
				event.preventDefault();

				var activity_data        = activityItem.data( 'bp-activity' );
				var activity_URL_preview = activityItem.data( 'link-url' ) !== '' ? activityItem.data( 'link-url' ) : null;

				if ( 'undefined' !== typeof activity_data ) {
					bp.Nouveau.Activity.postForm.displayEditActivityForm( activity_data, activity_URL_preview );

					// Check if it's a Group activity.
					var $activityForm = $( '#bb-rl-activity-form' );
					$activityForm.toggleClass( 'group-activity', target.closest( 'li' ).hasClass( 'groups' ) );

					// Close the Media/Document popup if someone clicks on Edit while on Media/Document popup.
					if (
						'undefined' !== typeof bp.Nouveau.Media &&
						'undefined' !== typeof bp.Nouveau.Media.Theatre &&
						(
							bp.Nouveau.Media.Theatre.is_open_media || bp.Nouveau.Media.Theatre.is_open_document
						)
					) {
						$( '.bb-rl-close-media-theatre, .bb-close-document-theatre' ).trigger( 'click' );
					}
				}
			}

			if (
				isInsideModal &&
				(
					target.hasClass( 'bb-rl-open-media-theatre' ) ||
					target.hasClass( 'bb-open-video-theatre' ) ||
					target.hasClass( 'bb-rl-open-document-theatre' ) ||
					target.hasClass( 'bb-rl-document-detail-wrap-description-popup' ) ||
					target.hasClass( 'bb-rl-document-description-wrap' )
				)
			) {
				// Stop event propagation.
				event.preventDefault();

				var modal       = target.closest( '#bb-rl-activity-modal' ),
					closeButton = modal.find( '.bb-rl-modal-activity-header .bb-rl-close-action-popup' );

				closeButton.trigger( 'click' );
			}

			// Pin OR UnPin the activity.
			if ( target.hasClass( 'pin-activity' ) || target.hasClass( 'unpin-activity' ) ) {
				self.pinUnpinActivity(
					{
						event        : event,
						activityId   : activityId,
						parent       : parent,
						target       : target,
						isInsideModal: isInsideModal,
					}
				);
			}

			// Mute OR Unmute notifications for the activity.
			if ( target.hasClass( 'bb-icon-bell-slash' ) || target.hasClass( 'bb-icon-bell' ) ) {
				self.muteUnmuteNotifications(
					{
						event        : event,
						activityId   : activityId,
						parent       : parent,
						target       : target,
						isInsideModal: isInsideModal,
					}
				);
			}

			// Close comment turn on/off.
			if ( target.hasClass( 'close-activity-comment' ) || target.hasClass( 'unclose-activity-comment' ) ) {
				self.onOffComment(
					{
						event        : event,
						activityId   : activityId,
						parent       : parent,
						target       : target,
						isInsideModal: isInsideModal,
					}
				);
			}
		},

		/**
		 * [closeCommentForm description]
		 *
		 * @return {[type]}       [description]
		 * @param event
		 */
		commentFormAction: function ( event ) {
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
		 * @return {[type]}       [description]
		 * @param event
		 */
		togglePopupDropdown: function ( event ) {
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
			if ( $( element ).closest( '.privacy-wrap' ).length ) {
				return event;
			}

			$( 'ul.activity-privacy' ).removeClass( 'bb-open' );
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
				$load_more_btn.find( 'a' ).text( bbRlActivity.strings.loadingMore );
				$load_more_btn.find( 'a' ).trigger( 'click' );
			}
		},

		openCommentsMediaUploader: function (event) {
			var self               = this,
				target             = $( event.currentTarget ),
				key                = target.data( 'ac-id' ),
				dropzone_container = target.closest( '.bb-rl-ac-form-container' ).find( '#bb-rl-ac-reply-post-media-uploader-' + key );

			// Check if target is inside #bb-rl-activity-modal.
			var isInsideModal  = target.closest( '#bb-rl-activity-modal' ).length > 0;
			var hasParentModal = isInsideModal ? '#bb-rl-activity-modal ' : '';

			event.preventDefault();

			if ( dropzone_container.hasClass( 'open' ) && ! event.isCustomEvent ) {
				dropzone_container.trigger( 'click' );
				dropzone_container.removeClass( 'open' ).addClass( 'closed' );
				return;
			}

			var acCommentDefaultTemplate = document.getElementsByClassName( 'bb-rl-ac-reply-post-default-template' ).length ? document.getElementsByClassName( 'bb-rl-ac-reply-post-default-template' )[0].innerHTML : ''; // Check to avoid error if the Node is missing.

			if ( 'undefined' !== typeof window.Dropzone && dropzone_container.length ) {

				if ( dropzone_container.hasClass( 'closed' ) ) {

					var dropzone_options = bp.Readylaunch.Utilities.createDropzoneOptions(
						{
							dictFileTooBig               : BP_Nouveau.media.dictFileTooBig,
							dictInvalidFileType          : bp_media_dropzone.dictInvalidFileType,
							dictDefaultMessage           : '',
							acceptedFiles                : 'image/*',
							maxFiles                     : typeof BP_Nouveau.media.maxFiles !== 'undefined' ? BP_Nouveau.media.maxFiles : 10,
							maxFilesize                  : typeof BP_Nouveau.media.max_upload_size !== 'undefined' ? BP_Nouveau.media.max_upload_size : 2,
							thumbnailWidth               : 140,
							thumbnailHeight              : 140,
							dictMaxFilesExceeded         : BP_Nouveau.media.media_dict_file_exceeded,
							previewTemplate              : acCommentDefaultTemplate,
							maxThumbnailFilesize         : typeof BP_Nouveau.media.max_upload_size !== 'undefined' ? BP_Nouveau.media.max_upload_size : 2,
							dictCancelUploadConfirmation : BP_Nouveau.media.dictCancelUploadConfirmation,
						}
					);

					// If a Dropzone instance already exists, destroy it before creating a new one.
					if ( self.dropzone_obj instanceof Dropzone ) {
						self.dropzone_obj.destroy();
					}

					// init dropzone.
					self.dropzone_obj = new Dropzone( hasParentModal + '#bb-rl-ac-reply-post-media-uploader-' + target.data( 'ac-id' ), dropzone_options );

					self.setupDropzoneEventHandlers( {
						self            : self,
						dropzoneObj     : self.dropzone_obj,
						dropzoneDataObj : self.dropzone_media,
						target          : target,
						type            : 'media'
					} );

					// container class to open close.
					dropzone_container.removeClass( 'closed' ).addClass( 'open' );

				} else {
					if ( self.dropzone_obj && 'undefined' !== typeof self.dropzone_obj) {
						self.dropzone_obj.destroy();
					}
					self.dropzone_media = [];
					dropzone_container.html( '' );
					dropzone_container.addClass( 'closed' ).removeClass( 'open' );
				}
			}

			var currentTarget = event.currentTarget, activityID = currentTarget.id.match( /\d+$/ )[0];
			['document', 'video', 'gif'].forEach(
				function ( type ) {
					self.destroyUploader( type, activityID );
				}
			);

			if ( ! event.isCustomEvent ) {
				$( target ).closest( '.bb-rl-ac-form-container' ).find( '.dropzone.media-dropzone' ).trigger( 'click' );
			}
		},

		openCommentsDocumentUploader: function (event) {
			var self               = this,
				target             = $( event.currentTarget ),
				key                = target.data( 'ac-id' ),
				dropzone_container = target.closest( '.bb-rl-ac-form-container' ).find( '#bb-rl-ac-reply-post-document-uploader-' + key );

			// Check if target is inside #bb-rl-activity-modal.
			var isInsideModal  = target.closest( '#bb-rl-activity-modal' ).length > 0;
			var hasParentModal = isInsideModal ? '#bb-rl-activity-modal ' : '';

			event.preventDefault();

			if ( dropzone_container.hasClass( 'open' ) && ! event.isCustomEvent ) {
				dropzone_container.trigger( 'click' );
				dropzone_container.removeClass( 'open' ).addClass( 'closed' );
				return;
			}

			var acCommentDocumentTemplate = document.getElementsByClassName( 'bb-rl-ac-reply-post-document-template' ).length ? document.getElementsByClassName( 'bb-rl-ac-reply-post-document-template' )[0].innerHTML : ''; // Check to avoid error if the Node is missing.

			if ( 'undefined' !== typeof window.Dropzone && dropzone_container.length ) {

				if ( dropzone_container.hasClass( 'closed' ) ) {

					var dropzone_options = bp.Readylaunch.Utilities.createDropzoneOptions(
						{
							dictFileTooBig               : BP_Nouveau.media.dictFileTooBig,
							acceptedFiles                : BP_Nouveau.media.video_type,
							createImageThumbnails        : false,
							dictDefaultMessage           : '',
							maxFiles                     : typeof BP_Nouveau.document.maxFiles !== 'undefined' ? BP_Nouveau.document.maxFiles : 10,
							maxFilesize                  : typeof BP_Nouveau.document.max_upload_size !== 'undefined' ? BP_Nouveau.document.max_upload_size : 2,
							dictInvalidFileType          : BP_Nouveau.document.dictInvalidFileType,
							dictMaxFilesExceeded         : BP_Nouveau.media.document_dict_file_exceeded,
							previewTemplate              : acCommentDocumentTemplate,
							dictCancelUploadConfirmation : BP_Nouveau.media.dictCancelUploadConfirmation,
						}
					);

					// init dropzone.
					self.dropzone_document_obj = new Dropzone( hasParentModal + '#bb-rl-ac-reply-post-document-uploader-' + target.data( 'ac-id' ), dropzone_options );

					self.setupDropzoneEventHandlers( {
						self            : self,
						dropzoneObj     : self.dropzone_document_obj,
						dropzoneDataObj : self.dropzone_document,
						target          : target,
						type            : 'document'
					} );

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
			['media', 'video', 'gif'].forEach(
				function ( type ) {
					self.destroyUploader( type, activityID );
				}
			);

			if ( ! event.isCustomEvent ) {
				$( target ).closest( '.bb-rl-ac-form-container' ).find( '.dropzone.document-dropzone' ).trigger( 'click' );
			}
		},

		openCommentsVideoUploader: function (event) {
			var self               = this,
				target             = $( event.currentTarget ),
				key                = target.data( 'ac-id' ),
				dropzone_container = target.closest( '.bb-rl-ac-form-container' ).find( '#bb-rl-ac-reply-post-video-uploader-' + key );

			// Check if the target is inside #bb-rl-activity-modal.
			var isInsideModal  = target.closest( '#bb-rl-activity-modal' ).length > 0;
			var hasParentModal = isInsideModal ? '#bb-rl-activity-modal ' : '';

			event.preventDefault();

			if ( dropzone_container.hasClass( 'open' ) && ! event.isCustomEvent ) {
				dropzone_container.trigger( 'click' );
				dropzone_container.removeClass( 'open' ).addClass( 'closed' );
				return;
			}

			var acCommentVideoTemplate = document.getElementsByClassName( 'bb-rl-ac-reply-post-video-template' ).length ? document.getElementsByClassName( 'bb-rl-ac-reply-post-video-template' )[0].innerHTML : ''; // Check to avoid error if the Node is missing.

			if ( 'undefined' !== typeof window.Dropzone && dropzone_container.length ) {

				if ( dropzone_container.hasClass( 'closed' ) ) {

					var dropzone_options = bp.Readylaunch.Utilities.createDropzoneOptions(
						{
							dictFileTooBig               : BP_Nouveau.video.dictFileTooBig,
							acceptedFiles                : BP_Nouveau.video.video_type,
							createImageThumbnails        : false,
							dictDefaultMessage           : '',
							maxFiles                     : typeof BP_Nouveau.video.maxFiles !== 'undefined' ? BP_Nouveau.video.maxFiles : 10,
							maxFilesize                  : typeof BP_Nouveau.video.max_upload_size !== 'undefined' ? BP_Nouveau.video.max_upload_size : 2,
							dictInvalidFileType          : BP_Nouveau.video.dictInvalidFileType,
							dictMaxFilesExceeded         : BP_Nouveau.video.video_dict_file_exceeded,
							previewTemplate              : acCommentVideoTemplate,
							dictCancelUploadConfirmation : BP_Nouveau.media.dictCancelUploadConfirmation,
						}
					);

					// init dropzone.
					self.dropzone_video_obj = new Dropzone( hasParentModal + '#bb-rl-ac-reply-post-video-uploader-' + target.data( 'ac-id' ), dropzone_options );

					self.setupDropzoneEventHandlers( {
						self            : self,
						dropzoneObj     : self.dropzone_video_obj,
						dropzoneDataObj : self.dropzone_video,
						target          : target,
						type            : 'video'
					} );

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
			['media', 'document', 'gif'].forEach(
				function ( type ) {
					self.destroyUploader( type, activityID );
				}
			);

			if ( ! event.isCustomEvent ) {
				$( target ).closest( '.bb-rl-ac-form-container' ).find( '.dropzone.video-dropzone' ).trigger( 'click' );
			}
		},

		openGifPicker: function ( event ) {
			event.preventDefault();

			var currentTarget    = event.currentTarget,
				isInsideModal    = $( currentTarget ).closest( '#bb-rl-activity-modal' ).length > 0,
				hasParentModal   = isInsideModal ? '#bb-rl-activity-modal ' : '',
				pickerContainer  = isInsideModal ? $( '.bb-rl-gif-media-search-dropdown-standalone' ) : $( currentTarget ).next(),
				isStandalone     = isInsideModal,
				$gifPickerEl     = pickerContainer,
				activityID       = currentTarget.id.match( /\d+$/ )[ 0 ],
				$gifAttachmentEl = $( hasParentModal + '#bb-rl-ac-reply-post-gif-' + activityID );

			var scrollTop    = $( window ).scrollTop(),
				offset       = $( currentTarget ).offset(),
				topPosition  = Math.round( offset.top ),
				leftPosition = Math.round( offset.left );

			if ( $gifPickerEl.is( ':empty' ) ) {
				var model                      = new bp.Models.ACReply(),
					gifMediaSearchDropdownView = new bp.Views.GifMediaSearchDropdown( { model: model, standalone: isStandalone } ),
					activityAttachedGifPreview = new bp.Views.ActivityAttachedGifPreview( { model: model, standalone: isStandalone } );

				$gifPickerEl.html( gifMediaSearchDropdownView.render().el );
				$gifAttachmentEl.html( activityAttachedGifPreview.render().el );

				this.models[ activityID ] = model;
			}

			var gif_box = $( currentTarget ).parents( 'form' ).find( '.bb-rl-ac-reply-attachments .bb-rl-activity-attached-gif-container' );
			if ( $( currentTarget ).hasClass( 'active' ) && gif_box.length && $.trim( gif_box.html() ) === '' ) {
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
			['media', 'document', 'video'].forEach(
				function ( type ) {
					self.destroyUploader( type, activityID );
				}
			);
		},

		toggleMultiMediaOptions: function ( form, target, placeholder ) {
			if ( ! _.isUndefined( bbRlMedia ) ) {

				var parent_activity, activity_data;

				if ( placeholder ) {
					target          = target ? $( target ) : $( placeholder );
					parent_activity = target.closest( '.bb-rl-activity-modal' ).find( '.activity-item' );
					activity_data   = target.closest( '.bb-rl-activity-modal' ).find( '.activity-item' ).data( 'bp-activity' );
					form            = $( placeholder );
				} else {
					parent_activity = target.closest( '.activity-item' );
					activity_data   = target.closest( '.activity-item' ).data( 'bp-activity' );
				}

				var targetLi = target.closest( 'li' ).data( 'bp-activity-comment' );
				if ( targetLi ) {
					activity_data = targetLi;
				}

				var mediaSettings = function ( mediaType, fallbackValue ) {
					if ( activity_data && ! _.isUndefined( activity_data[mediaType] ) ) {
						return activity_data[mediaType];
					} else if ( false === bbRlMedia[mediaType] ) {
						return bbRlMedia[mediaType];
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
						gif           : bbRlMedia.gif.groups,
						emoji         : bbRlMedia.emoji.groups
					};

					this.applyMediaSettings( form, groupMediaSettings );
				} else { // Check for profile media.
					var profileMediaSettings = {
						profile_media   : mediaSettings( 'profile_media', false ),
						profile_document: mediaSettings( 'profile_document', false ),
						profile_video   : mediaSettings( 'profile_video', false ),
						gif             : bbRlMedia.gif.profile,
						emoji           : bbRlMedia.emoji.profile
					};

					this.applyMediaSettings( form, profileMediaSettings );
				}
			}
		},

		applyMediaSettings: function ( form, settings ) {
			// Handle media visibility based on the settings.
			form.find( '.bb-rl-ac-reply-toolbar .bb-rl-post-media.bb-rl-media-support' ).toggle( settings.group_media || settings.profile_media ).parent( '.bb-rl-ac-reply-toolbar' ).toggleClass(
				'bb-rl-post-media-disabled',
				! (
				settings.group_media || settings.profile_media
				)
			);

			form.find( '.bb-rl-ac-reply-toolbar .bb-rl-post-media.bb-rl-document-support' ).toggle( settings.group_document || settings.profile_document ).parent( '.bb-rl-ac-reply-toolbar' ).toggleClass(
				'bb-rl-post-media-disabled',
				! (
				settings.group_document || settings.profile_document
				)
			);

			form.find( '.bb-rl-ac-reply-toolbar .bb-rl-post-video.bb-rl-video-support' ).toggle( settings.group_video || settings.profile_video ).parent( '.bb-rl-ac-reply-toolbar' ).toggleClass(
				'post-video-disabled',
				! (
				settings.group_video || settings.profile_video
				)
			);

			form.find( '.bb-rl-ac-reply-toolbar .bb-rl-post-gif' ).toggle( settings.gif !== false ).parent( '.bb-rl-ac-reply-toolbar' ).toggleClass( 'post-gif-disabled', settings.gif === false );

			form.find( '.bb-rl-ac-reply-toolbar .bb-rl-post-emoji' ).toggle( settings.emoji !== false ).parent( '.bb-rl-ac-reply-toolbar' ).toggleClass( 'post-emoji-disabled', settings.emoji === false );
		},

		fixAtWhoActivity: function () {
			$( '.bb-rl-acomment-content, .bb-rl-activity-content' ).each(
				function () {
					// replacing atwho query from the comment content to disable querying it in the requests.
					var atwho_query      = $( this ).find( 'span.atwho-query' );
					var atwhoQueryLength = atwho_query.length;
					for ( var i = 0; i < atwhoQueryLength; i++ ) {
						$( atwho_query[ i ] ).replaceWith( atwho_query[ i ].innerText );
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
						var id        = hash;
						var adminBar  = $adminBar.length !== 0 ? $adminBar.innerHeight() : 0;
						if ( $( id ).length > 0 ) {
							$( 'html, body' ).animate( { scrollTop: parseInt( $( id ).offset().top ) - (80 + adminBar) }, 0 );
						}
					}
				},
				200
			);
		},

		editActivityCommentForm: function ( form, activity_comment_data ) {
			var formActivityId = form.find( 'input[name="comment_form_id"]' ).val(),
				toolbarDiv     = form.find( '#bb-rl-ac-reply-toolbar-' + formActivityId ),
				self           = this;

			form.find( '#ac-input-' + formActivityId ).html( activity_comment_data.content );

			var uploaderButtons = [];
			// Inject medias.
			if (
				'undefined' !== typeof activity_comment_data.media &&
				0 < activity_comment_data.media.length
			) {
				// Trigger the button click for the specific file type.
				toolbarDiv.find( '.bb-rl-ac-reply-media-button' ).trigger( { type: 'click', isCustomEvent: true } );

				uploaderButtons = [
					'.bb-rl-ac-reply-document-button',
					'.bb-rl-ac-reply-video-button',
					'.bb-rl-ac-reply-gif-button'
				];
				uploaderButtons.forEach(
					function ( buttonClass ) {
						self.disabledCommentUploader( toolbarDiv, buttonClass );
					}
				);

				bp.Readylaunch.Utilities.injectFiles(
					{
						toolbarDiv : toolbarDiv,
						commonData : activity_comment_data.media,
						id         : activity_comment_data.id,
						self       : this,
						fileType   : 'media',
						buttonClass: '.bb-rl-ac-reply-media-button',
						dropzoneObj: self.dropzone_obj,
					}
				);
			}

			// Inject Documents.
			if (
				'undefined' !== typeof activity_comment_data.document &&
				0 < activity_comment_data.document.length
			) {
				// Trigger the button click for the specific file type.
				toolbarDiv.find( '.bb-rl-ac-reply-document-button' ).trigger( { type: 'click', isCustomEvent: true } );

				uploaderButtons = [
					'.bb-rl-ac-reply-media-button',
					'.bb-rl-ac-reply-video-button',
					'.bb-rl-ac-reply-gif-button'
				];
				uploaderButtons.forEach(
					function ( buttonClass ) {
						self.disabledCommentUploader( toolbarDiv, buttonClass );
					}
				);

				bp.Readylaunch.Utilities.injectFiles(
					{
						toolbarDiv : toolbarDiv,
						commonData : activity_comment_data.document,
						id         : activity_comment_data.id,
						self       : this,
						fileType   : 'document',
						buttonClass: '.bb-rl-ac-reply-document-button',
						dropzoneObj: self.dropzone_document_obj,
					}
				);
			}

			// Inject Videos.
			if (
				'undefined' !== typeof activity_comment_data.video &&
				0 < activity_comment_data.video.length
			) {
				// Trigger the button click for the specific file type.
				toolbarDiv.find( '.bb-rl-ac-reply-video-button' ).trigger( { type: 'click', isCustomEvent: true } );

				uploaderButtons = [
					'.bb-rl-ac-reply-media-button',
					'.bb-rl-ac-reply-document-button',
					'.bb-rl-ac-reply-gif-button'
				];
				uploaderButtons.forEach(
					function ( buttonClass ) {
						self.disabledCommentUploader( toolbarDiv, buttonClass );
					}
				);

				bp.Readylaunch.Utilities.injectFiles(
					{
						toolbarDiv : toolbarDiv,
						commonData : activity_comment_data.video,
						id         : activity_comment_data.id,
						self       : this,
						fileType   : 'video',
						buttonClass: '.bb-rl-ac-reply-video-button',
						dropzoneObj: self.dropzone_video_obj,
					}
				);
			}

			// Inject GIF.
			if (
				'undefined' !== typeof activity_comment_data.gif &&
				0 < Object.keys( activity_comment_data.gif ).length
			) {
				var $gifPickerEl     = toolbarDiv.find( '.bb-rl-ac-reply-gif-button' ).next(),
					isInsideModal    = form.closest( '#bb-rl-activity-modal' ).length > 0,
					hasParentModal   = isInsideModal ? '#bb-rl-activity-modal ' : '',
					$gifAttachmentEl = $( hasParentModal + '#bb-rl-ac-reply-post-gif-' + formActivityId );

				toolbarDiv.find( '.bb-rl-ac-reply-gif-button' ).trigger( 'click' );

				uploaderButtons = [
					'.bb-rl-ac-reply-media-button',
					'.bb-rl-ac-reply-document-button',
					'.bb-rl-ac-reply-video-button'
				];
				uploaderButtons.forEach(
					function ( buttonClass ) {
						self.disabledCommentUploader( toolbarDiv, buttonClass );
					}
				);

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
				form.find( '.bb-rl-ac-reply-toolbar .bb-rl-post-elements-buttons-item' ).removeClass( 'disable' );
			}

		},

		resetActivityCommentForm: function ( form, resetType ) {
			resetType = 'undefined' !== typeof resetType ? resetType : '';

			// Form is not edit activity comment form and not hardReset, then return.
			if ( ! form.hasClass( 'acomment-edit' ) && 'hardReset' !== resetType ) {
				return;
			}

			var formActivityId = form.find( 'input[name="comment_form_id"]' ).val(),
				formItemId     = form.attr( 'data-item-id' ),
				formAcomment   = $( '[data-bp-activity-comment-id="' + formItemId + '"]' );

			formAcomment.find( '#bb-rl-acomment-display-' + formItemId ).removeClass( 'bp-hide' );
			form.removeClass( 'acomment-edit' ).removeAttr( 'data-item-id' );

			form.find( '.bb-rl-post-elements-buttons-item' ).removeClass( 'disable' );
			form.find( '.bb-rl-post-elements-buttons-item .bb-rl-toolbar-button' ).removeClass( 'active' );

			form.find( '#ac-input-' + formActivityId ).html( '' );
			form.removeClass( 'has-content has-gif has-media' );
			var self = this;
			['media', 'document', 'video', 'gif'].forEach(
				function ( type ) {
					self.destroyUploader( type, formActivityId );
				}
			);
		},

		// Reinitialize reply/edit comment form and append in activity modal footer.
		reinitializeActivityCommentForm: function ( form ) {

			var formActivityId = form.find( 'input[name="comment_form_id"]' ).val();

			if ( form.hasClass( 'acomment-edit' ) ) {
				var formItemId   = form.attr( 'data-item-id' );
				var formAcomment = $( '[data-bp-activity-comment-id="' + formItemId + '"]' );

				formAcomment.find( '#bb-rl-acomment-display-' + formItemId ).removeClass( 'bp-hide' );
				form.removeClass( 'acomment-edit' ).removeAttr( 'data-item-id' );
			}

			form.find( '#ac-input-' + formActivityId ).html( '' );
			form.removeClass( 'has-content has-gif has-media' );
			$( '.bb-rl-modal-activity-footer' ).addClass( 'active' ).append( form );
			var self = this;
			['media', 'document', 'video', 'gif'].forEach(
				function ( type ) {
					self.destroyUploader( type, formActivityId );
				}
			);
		},

		disabledCommentUploader : function ( toolbar, buttonClass, activeClass ) {
			activeClass = activeClass || '';
			var button  = toolbar.find( buttonClass );

			if ( button.length > 0 ) {
				var $btnElem = button.parents( '.bb-rl-post-elements-buttons-item' );
				if ( activeClass ) {
					$btnElem.addClass( activeClass );
				} else {
					$btnElem.addClass( 'disable' );
				}
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
		closeActivityState: function () {
			$( '.activity-state-popup' ).hide().removeClass( 'active' );
		},

		listenCommentInput: function ( input ) {
			if ( input.length > 0 ) {
				var divEditor = input.get( 0 );
				var commentID = $( divEditor ).attr( 'id' ) + (
					$( divEditor ).closest( '.bb-rl-media-model-inner' ).length ? '-theater' : ''
				);

				// The Comment block is moved from theater and needs to be initiated.
				if ( $.inArray( commentID, this.InitiatedCommentForms ) !== - 1 && ! $( divEditor ).closest( 'form' ).hasClass( 'events-initiated' ) ) {
					var index = this.InitiatedCommentForms.indexOf( commentID );
					this.InitiatedCommentForms.splice( index, 1 );
				}

				if ( $.inArray( commentID, this.InitiatedCommentForms ) === - 1 && ! $( divEditor ).closest( 'form' ).hasClass( 'events-initiated' ) ) {
					// Check if a comment form has already pasted event initiated.
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
					this.InitiatedCommentForms.push( commentID ); // Add this Comment form in an initiated comment form list.
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
				modal         = currentTarget.closest( '#bb-rl-activity-modal' ),
				activityId    = modal.find( '.activity-item' ).data( 'bp-activity-id' ),
				form          = modal.find( '#ac-form-' + activityId );

			bp.Nouveau.Activity.resetActivityCommentForm( form, 'hardReset' );

			modal.find( '.bb-rl-acomment-display' ).removeClass( 'bb-rl-display-focus' );
			modal.find( '.comment-item' ).removeClass( 'bb-rl-comment-item-focus' );
			modal.find( '.bb-rl-modal-activity-footer' ).addClass( 'active' ).append( form );
			form.addClass( 'root' );
			form.find('#ac-input-' + activityId).focus();
			form.find( '.bb-rl-ac-reply-content .ac-input' ).attr( 'data-placeholder', bbRlActivity.strings.commentPlaceholder );
			form.find( 'input[name="ac_form_submit"]' ).val( bbRlActivity.strings.commentButtonText );
			bp.Nouveau.Activity.clearFeedbackNotice( form );
		},

		/**
		 * [clearFeedbackNotice description]
		 *
		 * @return {[type]}       [description]
		 */
		clearFeedbackNotice: function ( form ) {
			var feedback = form.find( '.bb-rl-ac-form-container .bp-feedback' );
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
			var modal           = $( '.bb-rl-activity-model-wrapper' );
			var activityContent = activityItem[0].outerHTML;
			var selector        = '[data-parent_comment_id="' + parentID + '"]';
			var activityTitle   = activityItem.data( 'activity-popup-title' );

			// Reset to default activity updates and id global variables.
			bp.Nouveau.Activity.activityHasUpdates    = false;
			bp.Nouveau.Activity.currentActivityId     = null;
			bp.Nouveau.Activity.activityPinHasUpdates = false;

			modal.closest( 'body' ).addClass( 'acomments-modal-open' );
			modal.show();
			modal.find( 'ul.bb-rl-activity-list' ).html( activityContent );
			modal.find( '.bb-rl-modal-activity-header h2' ).text( activityTitle );

			if ( modal.find( '.activity-state-comments .comments-count' ).data( 'comments-count' ) > 0 ) {
				bp.Nouveau.Activity.initialLoadComment( {
					activityID : activityID,
					parentID   : activityID,
					modal      : modal
				} );
			} else {
				if ( ! modal.find( '#bb-rl-activity-' + activityID ).hasClass( 'has-comments' ) ) {
					modal.find( '#bb-rl-activity-' + activityID ).addClass( 'has-comments' );
				}
			}

			// Reload video.
			var videoItems = modal.find( '.bb-rl-activity-video-elem' );
			videoItems.each(
				function ( index, elem ) {
					var videoContainer = $( elem );
					var videos         = videoContainer.find( 'video' );
					videos.each(
						function ( index, video ) {
							var videoElement   = $( video );
							var videoElementId = videoElement.attr( 'id' ) + Math.floor( Math.random() * 10000 );
							videoElement.attr( 'id', videoElementId );

							var videoActionWrap = videoContainer.find( '.bb-rl-more_dropdown-wrap' );
							videoElement.insertAfter( videoActionWrap );
							videoContainer.find( '.video-js' ).remove();
							videoElement.addClass( 'video-js' );

							videojs(
								videoElementId,
								{
									'controls'        : true,
									'aspectRatio'     : '16:9',
									'fluid'           : true,
									'playbackRates'   : [0.5, 1, 1.5, 2],
									'fullscreenToggle': false,
								}
							);
						}
					);
				}
			);

			if ( activityItem.hasClass( 'bb-closed-comments' ) ) {
				modal.find( '#bb-rl-activity-modal' ).addClass( 'bb-closed-comments' );
			}

			var form = modal.find( '#ac-form-' + activityID );
			modal.find( '.bb-rl-acomment-display' ).removeClass( 'bb-rl-display-focus' );
			modal.find( '.comment-item' ).removeClass( 'bb-rl-comment-item-focus' );
			modal.find( '.bb-rl-modal-activity-footer' ).addClass( 'active' ).append( form );
			form.removeClass( 'not-initialized' ).addClass( 'root' ).find( '#ac-input-' + activityID ).focus();

			bp.Nouveau.Activity.clearFeedbackNotice( form );
			form.removeClass( 'events-initiated' );
			var ce = modal.find( '.bb-rl-modal-activity-footer .ac-form' ).find( '.ac-input[contenteditable]' );
			bp.Nouveau.Activity.listenCommentInput( ce );

			modal.find( '.bb-activity-more-options-wrap .bb-activity-more-options-action, .bb-rl-pin-action_button, .bb-rl-mute-action_button' ).attr( 'data-balloon-pos', 'left' );
			modal.find( '.privacy-wrap' ).attr( 'data-bp-tooltip-pos', 'right' );
			modal.find( selector ).children( '.acomments-view-more' ).first().trigger( 'click' );

			if ( ! _.isUndefined( bbRlMedia ) && ! _.isUndefined( bbRlMedia.emoji ) ) {
				bp.Nouveau.Activity.initializeEmojioneArea( true, '#bb-rl-activity-modal ', activityID );
			}

			if ( 'undefined' !== typeof bp.Nouveau ) {
				bp.Nouveau.reportPopUp();
			}

			bp.Nouveau.Activity.toggleMultiMediaOptions( form, '', '.bb-rl-modal-activity-footer' );

			if( ! modal.find( '.bb-rl-modal-activity-body' ).hasClass( 'bb-rl-modal-activity-body-scroll-event-initiated' ) ) {
				modal.find( '.bb-rl-modal-activity-body' ).on( 'scroll', function () {
					// check if .bb-rl-modal-activity-body has scrolled to bottom
					var $this = $(this);

					// Add a small buffer (1px) to account for potential floating point differences
					var scrollBuffer = 1;
					var scrolledToBottom = Math.ceil($this.scrollTop() + $this.innerHeight() + scrollBuffer) >= $this.prop('scrollHeight');

					if (scrolledToBottom) {
						modal.addClass('bb-rl-modal-activity-body-scrolled-to-bottom');
					} else {
						modal.removeClass('bb-rl-modal-activity-body-scrolled-to-bottom');
					}
				} );
				modal.find( '.bb-rl-modal-activity-body' ).addClass( 'bb-rl-modal-activity-body-scroll-event-initiated' );
			}
		},

		initialLoadComment: function ( args ) {
			this.loadMoreComments( {
				target          : args.modal.find( '.bb-rl-activity-comments' ),
				activityId      : args.activityID,
				parentCommentId : args.parentID || 0,
				isModal         : true
			} );
		},

		viewMoreComments: function ( e ) {
			e.preventDefault();
			var $target           = $( e.currentTarget );
			var currentTargetList = $target.parent();

			this.loadMoreComments( {
				target               : $target,
				currentTargetList    : $target.parent(),
				activityId           : $( currentTargetList ).data( 'activity_id' ),
				commentsList         : $target.closest( '.bb-rl-activity-comments' ),
				commentsActivityItem : $target.closest( '.activity-item' ),
				parentCommentId      : $( currentTargetList ).data( 'parent_comment_id' ),
				lastCommentTimestamp : $target.prev( 'li.activity-comment' ).data( 'bp-timestamp' ),
				addAfterListItemId   : '',
				lastCommentId        : $target.prev( 'li.activity-comment' ).data( 'bp-activity-comment-id' ),
				isModal              : false,
			} );
		},

		autoloadMoreComments: function () {
			var activityWrapper = $( '.bb-rl-activity-model-wrapper' );

			if ( activityWrapper.length > 0 && 'none' !== activityWrapper.css( 'display' ) ) {
				var element      = $( '.bb-rl-modal-activity-body .bb-rl-activity-comments > ul > li.acomments-view-more:not(.loading), .bb-rl-modal-activity-body .bb-rl-activity-comments .activity-actions > ul > li.acomments-view-more:not(.loading)' ),
					container    = activityWrapper.find( '.bb-rl-modal-activity-body' ),
					commentsList = container.find( '.bb-rl-activity-comments:not(.active)' );
				if ( element.length > 0 && container.length > 0 && commentsList.length > 0 ) {
					var elementTop      = $( element ).offset().top, containerTop = $( container ).scrollTop(),
						containerBottom = containerTop + $( container ).height();

					// Adjust elementTop based on the container's current scroll position.
					// This translates the element's position to be relative to the container, not the whole document.
					var elementRelativeTop = elementTop - $( container ).offset().top + containerTop;
					if ( elementRelativeTop < containerBottom && (
						elementRelativeTop + $( element ).outerHeight()
					) > containerTop ) {
						$( element ).trigger( 'click' ).addClass( 'loading' );
					}
				}

				// Replace fake image with original image by faking scroll event to call bp.Nouveau.lazyLoad.
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
				currentTargetModal = $( '.bb-rl-activity-model-wrapper' );
			} else {
				currentTargetModal = currentTarget.parents( '.bb-rl-activity-model-wrapper' );
			}

			var $activityListItem     = currentTargetModal.find( 'ul.bb-rl-activity-list > li' ),
				activityListItemId    = $activityListItem.data( 'bp-activity-id' ),
				activityId            = undefined !== activityID ? activityID : activityListItemId,
				$pageActivitylistItem = $( '#bb-rl-activity-stream li.activity-item[data-bp-activity-id=' + activityId + ']' );

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
							// Success.
							$pageActivitylistItem.replaceWith( $.parseHTML( response.data.activity ) );
							// Replace fake image with original image by faking scroll event to call bp.Nouveau.lazyLoad.
							jQuery( window ).scroll();

							// Refresh activities after updating pin/unpin `post` status.
							bp.Nouveau.Activity.heartbeat_data.last_recorded = 0;
							bp.Nouveau.refreshActivities();
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
			var activityModal = $( '#bb-rl-activity-modal' );
			var activityId    = activityModal.find( '.bb-rl-modal-activity-body .activity-item' ).data( 'bp-activity-id' );
			if ( activityModal.length > 0 && $( '.bb-rl-emojionearea-theatre.show' ).length > 0 ) {
				$( '.bb-rl-activity-model-wrapper #ac-input-' + activityId ).data( 'emojioneArea' ).hidePicker();
			}

			var gifPicker = $( '.bb-rl-gif-media-search-dropdown-standalone.open' );
			if ( activityModal.length > 0 && gifPicker.length > 0 ) {
				gifPicker.removeClass( 'open' );
				activityModal.find( '.bb-rl-ac-reply-gif-button' ).removeClass( 'active' );
			}
		},

		initializeEmojioneArea: function ( isModal, parentSelector, activityId ) {
			if ( ! $.fn.emojioneArea ) {
				return;
			}
			$( parentSelector + '#ac-input-' + activityId ).emojioneArea(
				{
					standalone: true,
					hideSource: false,
					container: parentSelector + '#bb-rl-ac-reply-emoji-button-' + activityId,
					detachPicker: ! ! isModal,
					containerPicker: isModal ? '.bb-rl-emojionearea-theatre' : null,
					autocomplete: false,
					pickerPosition: 'top',
					hidePickerOnBlur: true,
					useInternalCDN: false,
					events: {
						emojibtn_click: function () {
							$( parentSelector + '#ac-input-' + activityId )[ 0 ].emojioneArea.hidePicker();

							// Check if emoji is added then enable submit button.
							var $activity_comment_input   = $( parentSelector + '#ac-form-' + activityId + ' #ac-input-' + activityId );
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
							$( this.button[ 0 ] ).closest( '.bb-rl-post-emoji' ).addClass( 'active' );
							$( '.bb-rl-emojionearea-theatre' ).removeClass( 'hide' ).addClass( 'show' );
						},

						picker_hide: function () {
							$( this.button[ 0 ] ).closest( '.bb-rl-post-emoji' ).removeClass( 'active' );
							$( '.bb-rl-emojionearea-theatre' ).removeClass( 'show' ).addClass( 'hide' );
						},
					},
				}
			);
		},

		destroyUploader: function ( type, comment_id ) {
			var self = this;

			// Map uploader types to their respective properties and IDs.
			var uploaderConfig = {
				media   : {
					obj          : self.dropzone_obj,
					dropzone     : self.dropzone_media,
					uploaderId   : '#bb-rl-ac-reply-post-media-uploader-',
					buttonId     : '#bb-rl-ac-reply-media-button-',
					additionalIds: ['#bb-rl-ac-reply-post-media-uploader-1-']
				},
				document: {
					obj       : self.dropzone_document_obj,
					dropzone  : self.dropzone_document,
					uploaderId: '#bb-rl-ac-reply-post-document-uploader-',
					buttonId  : '#bb-rl-ac-reply-document-button-'
				},
				video   : {
					obj       : self.dropzone_video_obj,
					dropzone  : self.dropzone_video,
					uploaderId: '#bb-rl-ac-reply-post-video-uploader-',
					buttonId  : '#bb-rl-ac-reply-video-button-'
				},
				gif     : {
					buttonId       : '#bb-rl-ac-reply-gif-button-',
					dropdownClass  : '.bb-rl-gif-media-search-dropdown',
					standaloneClass: '.bb-rl-gif-media-search-dropdown-standalone',
					modelProperty  : 'gif_data',
					containerId    : '#bb-rl-ac-reply-post-gif-'
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
						config.additionalIds.forEach(
							function ( id ) {
								$( id + comment_id ).html( '' );
							}
						);
					}
				}
				config.dropzone = [];
				$( config.uploaderId + comment_id ).removeClass( 'open' ).addClass( 'closed' );
			}
			$( config.buttonId + comment_id ).removeClass( 'active' );

			// Handle GIF-specific logic.
			if ( 'gif' === type ) {
				$( config.buttonId + comment_id ).closest( '.bb-rl-post-gif' ).find( config.dropdownClass ).removeClass( 'open' ).empty();
				$( config.standaloneClass ).removeClass( 'open' ).empty();

				if ( ! _.isUndefined( this.models[comment_id] ) ) {
					var model = this.models[comment_id];
					model.set( config.modelProperty, {} );
					$( config.containerId + comment_id ).find( '.bb-rl-activity-attached-gif-container' ).removeAttr( 'style' );
				}
			}
		},

		handleFavoriteUnfavorite: function ( args ) {
			var event = args.event;
			event.preventDefault(); // Stop event propagation.

			var parent        = args.parent,
				target        = args.target,
				reactionId    = args.reactionId,
				activityState = args.activityState,
				$bodyElem     = args.bodyElem,
				stream        = args.stream,
				activityItem  = args.activityItem,
				itemId;

			// Do not trigger click event directly on the button when it's mobile and reaction is active.
			if ( $bodyElem.hasClass( 'bb-is-mobile' ) && $bodyElem.hasClass( 'bb-reactions-mode' ) && target.closest( '.ac-emotion_btn' ).length === 0 && event.customTriggered !== true ) {
				return;
			}

			if ( ! $( target ).is( 'a' ) ) {
				target = $( target ).closest( 'a' );
			}

			if ( target.hasClass( 'loading' ) ) {
				return;
			}

			target.addClass( 'loading' );

			var type       = target.hasClass( 'fav' ) ? 'fav' : 'unfav',
				isActivity = true,
				itemType   = 'activity',
				parent_el  = target.parents( '.bb-rl-acomment-display' ).first(),
				reacted_id = target.attr( 'data-reacted-id' ),
				mainEl;

			if ( reactionId > 0 ) {
				type = 'fav';
			}

			// Return when same reaction ID found.
			if ( target.parent( '.ac-emotion_btn' ) ) {
				reacted_id = target.parents( '.bp-generic-meta' ).find( '.unfav' ).attr( 'data-reacted-id' );
			}

			if ( 'fav' === type && parseInt( reactionId ) === parseInt( reacted_id ) ) {
				target.removeClass( 'loading' );
				return;
			}

			if ( 0 < parent_el.length ) {
				isActivity = false;
				itemType   = 'activity_comment';
			}

			mainEl = isActivity ? target.parents( '.activity-item' ) : target.parents( '.activity-comment' ).first();
			itemId = mainEl.data( isActivity ? 'bp-activity-id' : 'bp-activity-comment-id' );

			var data = {
				action     : 'activity_mark_' + type,
				reaction_id: reactionId,
				item_id    : itemId,
				item_type  : itemType,
			};

			parent.ajax( data, 'activity' ).done(
				function ( response ) {

					if ( false === response.success ) {
						target.removeClass( 'loading' );
						alert( response.data );
						return;
					} else {
						target.fadeOut(
							200,
							function () {
								var $this       = $( this );
								var ariaPressed = 'false' === $this.attr( 'data-pressed' ) ? 'true' : 'false';
								$this.attr( 'data-pressed', ariaPressed );

								// Update reacted username and counts.
								if ( 'undefined' !== typeof response.data.reaction_count ) {
									var reactionCountElem = isActivity ?
										mainEl.find( '.bb-rl-activity-content .activity-state-reactions' ) :
										mainEl.find( '#bb-rl-acomment-display-' + itemId + ' .bb-rl-comment-reactions .activity-state-reactions' );

									if ( reactionCountElem.length ) {
										reactionCountElem.replaceWith( response.data.reaction_count );
									} else {
										var parentElem = isActivity ?
											mainEl.find( '.bb-rl-activity-content .activity-state' ) :
											mainEl.find( '#bb-rl-acomment-display-' + itemId + ' .bb-rl-comment-reactions' );
										parentElem.prepend( response.data.reaction_count );
									}

									// Toggle 'has-likes' class based on reaction count.
									activityState.toggleClass( 'has-likes', response.data.reaction_count !== '' );
								}

								// Update reacted button.
								if ( response.data.reaction_button ) {
									var reactionButtonElem = isActivity ?
										mainEl.find( '.bp-generic-meta a.bp-like-button:first' ) :
										mainEl.find( '#bb-rl-acomment-display-' + itemId + ' .bp-generic-meta a.bp-like-button' );
									reactionButtonElem.replaceWith( response.data.reaction_button );
								}

								// Hide Reactions popup.
								mainEl.find( '.ac-emotions_list' ).removeClass( 'active' );

								bp.Nouveau.Activity.activityHasUpdates = true;
								bp.Nouveau.Activity.currentActivityId  = itemId;

								$this.fadeIn( 200 );
								target.removeClass( 'loading' );
							}
						);

						// Add a flag for an AJAX load for getting reactions.
						var reactionUpdateClass = 'bb-has-reaction_update';
						var reactionElem        = isActivity ?
							$( '.activity[data-bp-activity-id=' + itemId + '] > .bb-rl-activity-content' ).find( '.activity-state-reactions' ) :
							$( '.activity-comment[data-bp-activity-comment-id=' + itemId + '] > .bb-rl-acomment-display > .bb-rl-acomment_inner' ).find( '.activity-state-reactions' );
						reactionElem.parent().addClass( reactionUpdateClass );
					}

					// Add "Likes/Emotions" menu item on the activity directory nav menu.
					if ( 'fav' === type ) {
						if (
							'undefined' !== typeof response.data.directory_tab &&
							response.data.directory_tab !== '' &&
							! $( parent.objectNavParent + ' [data-bp-scope="favorites"]' ).length
						) {
							$( parent.objectNavParent + ' [data-bp-scope="all"]' ).after( response.data.directory_tab );
						}

					} else if ( 'unfav' === type ) {
						var favoriteScope = $( '[data-bp-user-scope="favorites"]' ).hasClass( 'selected' ) || $( parent.objectNavParent + ' [data-bp-scope="favorites"]' ).hasClass( 'selected' );

						// If on user's profile or on the favorite directory tab, remove the entry.
						if ( favoriteScope ) {
							activityItem.remove();
						}

						if ( undefined !== response.data.no_favorite ) {
							// Remove the tab when on activity directory but not on the favorite tabs.
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
				function () {
					target.removeClass( 'loading' );
				}
			);
		},

		deleteActivity: function ( args ) {
			var event = args.event;
			event.preventDefault(); // Stop event propagation.

			var parent                 = args.parent,
			    target                 = args.target,
			    activityState          = args.activityState,
			    activityItem           = args.activityItem,
			    commentsText           = args.commentsText,
			    activityId             = args.activityId,
			    activity_comment_li    = target.closest( '[data-bp-activity-comment-id]' ),
			    activity_comment_id    = activity_comment_li.data( 'bp-activity-comment-id' ),
			    li_parent,
			    comment_count_span,
			    comment_count,
			    show_all_a,
			    deleted_comments_count = 0;

			var commentsList = target.closest( '.bb-rl-activity-comments' );
			commentsList.addClass( 'active' );

			if ( undefined !== bbRlConfirm && false === window.confirm( bbRlConfirm ) ) {
				return false;
			}

			target.addClass( 'loading' );

			var ajaxData = {
				action     : 'delete_activity',
				'id'       : activityId,
				'_wpnonce' : parent.getLinkParams( target.prop( 'href' ), '_wpnonce' ),
				'is_single': target.closest( '[data-bp-single]' ).length
			};

			// Only the action changes when spamming an activity or a comment.
			if ( target.hasClass( 'spam-activity' ) || target.hasClass( 'spam-activity-comment' ) ) {
				ajaxData.action = 'bp_spam_activity';
			}

			// Set defaults parent li to activity container.
			li_parent = activityItem;

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
				function ( response ) {
					target.removeClass( 'loading' );

					if ( false === response.success ) {
						li_parent.append( response.data.feedback );
						li_parent.find( '.bp-feedback' ).hide().fadeIn( 300 );
					} else {
						var closestParentElement = li_parent.closest( '.has-child-comments' );
						var closestNestedParentElement;
						if ( li_parent.hasClass( 'has-child-comments' ) ) {
							closestNestedParentElement = li_parent.closest( 'ul' ).closest( 'li' );
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
							deleted_comments_count    = 1;
							var hidden_comments_count = activity_comment_li.find( '.acomments-view-more' ).data( 'child-count' );

							// Move the form if needed.
							activityItem.append( activity_comment_li.find( 'form' ) );

							// Count child comments if there are some.
							activity_comment_li.find( 'li.comment-item' ).each(
								function () {
									deleted_comments_count += 1;
								}
							);

							deleted_comments_count += hidden_comments_count !== undefined ? parseFloat( hidden_comments_count ) : 0;

							// Update the button count.
							comment_count_span = activityState.find( 'span.comments-count' );
							comment_count      = comment_count_span.text().length ? comment_count_span.text().match( /\d+/ )[0] : 0;
							comment_count      = Number( comment_count - deleted_comments_count );

							if ( commentsText.length ) {
								var label = comment_count > 1 ? bbRlActivity.strings.commentsLabel : bbRlActivity.strings.commentLabel;
								commentsText.text( label.replace( '%d', comment_count ) );
								comment_count_span.attr( 'data-comments-count', comment_count );
							} else {
								comment_count_span.parent( '.has-comments' ).removeClass( 'has-comments' );
							}

							// Update the reply count with parent replies count.
							var $parentComment        = activity_comment_li.closest( '.activity-comment' ),
							    processedIds          = {},
							    totalCommentsToDelete = 1; // Start with 1 for the current comment
							if ( activity_comment_li.hasClass( 'has-child-comments' ) ) {
								// Only search for children if we know they exist
								var childCount = activity_comment_li.find( '.acomments-view-more' ).data( 'child-count' ) || 0;
								totalCommentsToDelete += childCount;
								totalCommentsToDelete += activity_comment_li.find( '.activity-comment:visible' ).length;
							}

							var $currentParent = $parentComment;
							while ( $currentParent.length ) {
								var parentId = $currentParent.data( 'bp-activity-comment-id' );
								if ( processedIds[ parentId ] ) {
									break;
								}
								processedIds[ parentId ] = true;

								var $reactionArea = $currentParent.find( '.bb-rl-comment-reactions' ).first(),
								    $countElement = $reactionArea.find( '.acomments-count' );
								if ( $countElement.length ) {
									var currentCount = parseInt( $countElement.attr( 'data-comments-count' ) );
									if ( isNaN( currentCount ) ) {
										var matches  = $countElement.text().match( /\d+/ );
										currentCount = matches ? parseInt( matches[ 0 ] ) : 0;
									}

									var newCount = Math.max( 0, currentCount - totalCommentsToDelete );
									if ( 0 === newCount ) {
										$reactionArea.find( '.activity-state-comments' ).remove();
									} else {
										var replyLabel = newCount > 1 ? bbRlActivity.strings.repliesLabel : bbRlActivity.strings.replyLabel;
										var newText    = replyLabel.replace( '%d', newCount );
										$countElement.attr( 'data-comments-count', newCount ).text( newText );
									}
								}
								$currentParent = $currentParent.parent().closest( '.activity-comment' );
							}

							// Update the show all count.
							show_all_a = activityItem.find( 'li.show-all a' );
							if ( show_all_a.length ) {
								show_all_a.html( bbbRlShowXComments.replace( '%d', comment_count ) );
							}

							// Clean up the parent activity classes.
							if ( 0 === comment_count ) {
								activityItem.removeClass( 'has-comments' );
								activityState.removeClass( 'has-comments' );
								commentsText.empty();
							}
						}

						// Remove the entry.
						li_parent.slideUp(
							300,
							function () {
								li_parent.remove();

								if ( 0 === closestList.find( 'li' ).length ) {
									closestParentElement.removeClass( 'has-child-comments' );
								}

								if ( 'undefined' !== typeof closestNestedParentElement && closestNestedParentElement.length ) {
									var closestParentElementList = closestNestedParentElement.find( '> ul' );
									if ( closestParentElementList.length ) {
										var trimmedList = closestParentElementList.html();
										if ( trimmedList && '' === trimmedList.trim() ) {
											closestNestedParentElement.removeClass( 'has-child-comments' );
										}
									}
								}
							}
						);

						// reset vars to get the newest activities when an activity is deleted.
						if ( ! activity_comment_id && activityItem.data( 'bp-timestamp' ) === parent.Activity.heartbeat_data.last_recorded ) {
							parent.Activity.heartbeat_data.newest        = '';
							parent.Activity.heartbeat_data.last_recorded = 0;
						}

						// Inform other scripts.
						$( document ).trigger( 'bp_activity_ajax_delete_request', $.extend( ajaxData, { response: response } ) );
						$( document ).trigger( 'bp_activity_ajax_delete_request_video', $.extend( ajaxData, { response: response } ) );

						bp.Nouveau.Activity.activityHasUpdates = true;
						bp.Nouveau.Activity.currentActivityId  = activityId;
					}

					commentsList.removeClass( 'active' );
				}
			);
		},

		readMoreActivity: function ( args ) {
			var event = args.event;
			event.preventDefault(); // Stop event propagation.

			var target     = args.target,
				itemId     = null,
				activityId = args.activityId,
				content    = target.closest( 'div' );

			if ( $( content ).hasClass( 'bb-rl-activity-inner' ) ) {
				itemId = activityId;
			} else if ( $( content ).hasClass( 'bb-rl-acomment-content' ) ) {
				itemId = target.closest( 'li' ).data( 'bp-activity-comment-id' );
			}

			if ( ! itemId ) {
				return event;
			}

			var parent       = args.parent,
				activityItem = args.activityItem,
				readMore     = target.closest( 'span' );

			$( readMore ).addClass( 'loading' );

			parent.ajax(
				{
					action: 'get_single_activity_content',
					id    : itemId
				},
				'activity'
			).done(
				function ( response ) {

					// Check for JSON output.
					if ( 'object' !== typeof response && target.closest( 'div' ).find( '.bb-activity-media-wrap' ).length > 0 ) {
						response = JSON.parse( response );
					}

					$( readMore ).removeClass( 'loading' );

					var feedback = content.parent().find( '.bp-feedback' );
					if ( feedback.length ) {
						feedback.remove();
					}

					if ( false === response.success ) {
						content.after( response.data.feedback );
						content.parent().find( '.bp-feedback' ).hide().fadeIn( 300 );
					} else {
						var contentChildren = $( content ).children();
						if ( contentChildren.filter( '.bb-poll-view' ).length ) {
							contentChildren.not( '.bb-poll-view' ).remove();
							$( content ).prepend( response.data.contents ).slideDown( 300 );
						} else {
							$( content ).html( response.data.contents ).slideDown( 300 );
						}

						// replace fake image with original image by faking scroll event to call bp.Nouveau.lazyLoad.
						jQuery( window ).scroll();

						if ( activityItem.hasClass( 'wp-link-embed' ) ) {
							if ( 'undefined' !== typeof window.instgrm ) {
								window.instgrm.Embeds.process();
							}
							if ( 'undefined' !== typeof window.FB && 'undefined' !== typeof window.FB.XFBML ) {
								window.FB.XFBML.parse( document.getElementById( 'activity-' + itemId ) );
							}
						}
					}
				}
			);
		},

		displayingTheCommentForm: function ( args ) {
			var event  = args.event,
				target = args.target;

			if ( target.parents( '.activity-item' ).hasClass( 'bb-closed-comments' ) ) {
				event.preventDefault();
				return;
			}

			var activityId           = args.activityId,
				form,
				isInsideModal        = args.isInsideModal,
				isInsideMediaTheatre = args.isInsideMediaTheatre,
				itemId               = activityId,
				hasParentModal,
				$activity_comments,
				self                 = args.self;

			var $activityModal = $( '#bb-rl-activity-modal' );
			var $internalModel = $( '.bb-rl-internal-model' );

			if ( isInsideModal ) {
				form               = $activityModal.find( '#ac-form-' + activityId );
				$activity_comments = $activityModal.find( '.bb-rl-modal-activity-footer' );
				hasParentModal     = '#bb-rl-activity-modal ';
			} else if ( isInsideMediaTheatre ) {
				form           = $internalModel.find( '#ac-form-' + activityId );
				hasParentModal = '.bb-rl-internal-model ';
			} else {
				form               = $( '#ac-form-' + activityId );
				$activity_comments = $( '[data-bp-activity-id="' + itemId + '"] .bb-rl-activity-comments' );
				hasParentModal     = '';
			}
			var activity_comment_data = false;

			var $mediaModelContainer = target.closest( '.bb-rl-media-model-container' );
			if ( $mediaModelContainer.length ) {
				form               = $mediaModelContainer.find( '#ac-form-' + activityId );
				$activity_comments = $mediaModelContainer.find( '[data-bp-activity-id="' + itemId + '"] .bb-rl-activity-comments' );
			}

			// Show a comment form on activity item when it is hidden initially.
			var $activityItem = target.closest( '.activity-item' );
			if ( ! $activityItem.hasClass( 'has-comments' ) ) {
				$activityItem.addClass( 'has-comments' );
			}

			// Stop event propagation.
			event.preventDefault();

			// If form is edit activity comment, then reset it.
			self.resetActivityCommentForm( form, 'hardReset' );

			var $closestLi = target.closest( 'li' );
			if ( $closestLi.data( 'bp-activity-comment-id' ) ) {
				itemId = $closestLi.data( 'bp-activity-comment-id' );
			}
			if ( $closestLi.data( 'bp-activity-comment' ) ) {
				activity_comment_data = $closestLi.data( 'bp-activity-comment' );
			}

			this.toggleMultiMediaOptions( form, target );

			form.removeClass( 'root' );
			$( '.ac-form' ).addClass( 'not-initialized' ).find( '.ac-input:not(.emojionearea)' ).html( '' );

			bp.Nouveau.Activity.clearFeedbackNotice( form );

			/* Remove any error messages */
			form.children( 'div' ).each(
				function ( e, err ) {
					if ( $( err ).hasClass( 'error' ) ) {
							$( err ).remove();
					}
				}
			);

			if ( target.hasClass( 'acomment-edit' ) && ! _.isNull( activity_comment_data ) ) {
				var acomment = $( hasParentModal + '[data-bp-activity-comment-id="' + itemId + '"]' );
				acomment.find( '#bb-rl-acomment-display-' + itemId ).addClass( 'bp-hide' );
				acomment.find( '#bb-rl-acomment-edit-form-' + itemId ).append( form );
				form.addClass( 'acomment-edit' ).attr( 'data-item-id', itemId );

				self.validateCommentContent( form.find( '.ac-textarea' ).children( '.ac-input' ) );

				// Render activity comment edit data to form.
				self.editActivityCommentForm( form, activity_comment_data );

				if ( isInsideModal ) {
					$( '.bb-rl-modal-activity-footer' ).removeClass( 'active' );
				}
			} else {
				// It's an activity we're commenting.
				var $modalFooter = $( '.bb-rl-modal-activity-footer' );
				if ( itemId === activityId ) {
					if ( isInsideModal ) {
						$modalFooter.addClass( 'active' );
						$activityModal.find( '.bb-rl-acomment-display, .comment-item' ).removeClass( 'bb-rl-display-focus bb-rl-comment-item-focus' );
					}
					$activity_comments.append( form );
					form.addClass( 'root' );
					$activity_comments.find( '.bb-rl-acomment-display, .comment-item' ).removeClass( 'bb-rl-display-focus bb-rl-comment-item-focus' );
					// It's a comment we're replying to.
				} else {
					if ( isInsideModal ) {
						$modalFooter.removeClass( 'active' );
						$activityModal.find( '[data-bp-activity-comment-id="' + itemId + '"]' ).append( form );
					} else if ( isInsideMediaTheatre ) {
						$internalModel.find( '[data-bp-activity-comment-id="' + itemId + '"]' ).append( form );
					} else {
						$( '[data-bp-activity-comment-id="' + itemId + '"]' ).append( form );
					}
				}
			}


			// Change the button text to Reply or Comment.
			var formSubmitBtn = form.find('input[name="ac_form_submit"]'),
				isReply = false;
			if (
				(
					target.children( '.acomments-count' ).length > 0 ||
					target.hasClass( 'acomment-reply' )
				) &&
				activityId !== itemId
			) {
				isReply = true;
			} else if ( target.hasClass( 'acomment-edit' ) ) {
				activityId          = target.closest( '.comment-item' ).parent().data( 'activity_id' );
				var parentCommentId = target.closest( '.comment-item' ).parent().data( 'parent_comment_id' );

				isReply = activityId !== parentCommentId;
			}

			if ( isReply ) {
				formSubmitBtn.val( bbRlActivity.strings.replyButtonText );
				form.find( '.bb-rl-ac-reply-content .ac-input' ).attr( 'data-placeholder', bbRlActivity.strings.replyPlaceholder );
			} else {
				formSubmitBtn.val( bbRlActivity.strings.commentButtonText );
				form.find( '.bb-rl-ac-reply-content .ac-input' ).attr( 'data-placeholder', bbRlActivity.strings.commentPlaceholder );
			}

			form.removeClass( 'not-initialized' );

			var postEmojiELem = form.find( '.bb-rl-post-elements-buttons-item.post-emoji' ),
				emojiPosition = postEmojiELem.prevAll().not( ':hidden' ).length + 1;
			postEmojiELem.attr( 'data-nth-child', emojiPosition );

			var postGifELem = form.find( '.bb-rl-post-elements-buttons-item.bb-rl-post-gif' ),
				gifPosition = postGifELem.prevAll().not( ':hidden' ).length + 1;
			postGifELem.attr( 'data-nth-child', gifPosition );

			/* Stop past image from clipboard */
			var ce = form.find( '.ac-input[contenteditable]' );
			bp.Nouveau.Activity.listenCommentInput( ce );

			// change the aria state from false to true.
			target.attr( 'aria-expanded', 'true' );
			target.closest( '.bb-rl-activity-comments' ).find( '.bb-rl-acomment-display, .comment-item' ).removeClass( 'bb-rl-display-focus' ).removeClass( 'bb-rl-comment-item-focus' );
			target.closest( '.bb-rl-acomment-display' ).addClass( 'bb-rl-display-focus' );
			target.closest( '.comment-item' ).addClass( 'bb-rl-comment-item-focus' );

			var activity_data_nickname = ! _.isNull( activity_comment_data ) ? activity_comment_data.nickname : '',
			    activity_user_id       = ! _.isNull( activity_comment_data ) ? activity_comment_data.user_id : '';

			var atWho = '';
			if ( ! _.isUndefined( activity_data_nickname ) ) {
				atWho = '<span class="atwho-inserted" data-atwho-at-query="@" contenteditable="false">@' + activity_data_nickname + '</span>&nbsp;';
			}
			var current_user_id = ! _.isUndefined( bbRlActivity.params.user_id ) ? bbRlActivity.params.user_id : '',
			    peak_offset     = (
				    $( window ).height() / 2 - 75
			    ),
			    scrollOptions   = {
				    offset : -peak_offset,
				    easing : 'swing'
			    },
			    div_editor      = ce.get( 0 );

			if ( ! jQuery( 'body' ).hasClass( 'bb-is-mobile' ) ) {
				if ( isInsideModal ) {
					$( '.bb-rl-modal-activity-body' ).scrollTo( form, 500, scrollOptions );
				} else if ( isInsideMediaTheatre ) {
					// Scroll only the media info section container.
					$( '.bb-media-info-section' ).scrollTo( form, 500, scrollOptions );
				} else {
					$.scrollTo( form, 500, scrollOptions );
				}
			} else {
				setTimeout(
					function () {
						var scrollInt = jQuery( window ).height() > 300 ? 200 : 100;
						if ( isInsideMediaTheatre ) {
							// If inside the media theater, scroll the info section container.
							var $mediaInfoSection = $( '.bb-media-info-section' );
							var formOffset = jQuery( div_editor ).offset().top - $mediaInfoSection.offset().top;
							$mediaInfoSection.animate( { scrollTop : formOffset - scrollInt }, 500 );
						} else {
							jQuery( 'html, body' ).animate( { scrollTop : jQuery( div_editor ).offset().top - scrollInt }, 500 );
						}
					},
					500
				);
			}

			$( hasParentModal + '#ac-form-' + activityId + ' #ac-input-' + activityId ).focus();
			var acInputElem = $( hasParentModal + '#ac-input-' + activityId );
			if ( ! _.isUndefined( bbRlMedia ) && ! _.isUndefined( bbRlMedia.emoji ) && 'undefined' == typeof acInputElem.data( 'emojioneArea' ) ) {
				// Store HTML data of editor.
				var editor_data = acInputElem.html();

				bp.Nouveau.Activity.initializeEmojioneArea( isInsideModal, hasParentModal, activityId );

				// Restore HTML data of editor after emojioneArea intialized.
				if ( target.hasClass( 'acomment-edit' ) && ! _.isNull( activity_comment_data ) ) {
					acInputElem.html( editor_data );
				}
			}

			// Tag user on comment replies.
			if (
				'' !== atWho &&
				! target.hasClass( 'acomment-edit' ) &&
				! target.hasClass( 'button' ) &&
				! target.hasClass( 'activity-state-comments' ) &&
				current_user_id !== activity_user_id
			) {
				acInputElem.html( atWho );
				form.addClass( 'has-content' );
			}

			// Place caret at the end of the content.
			if (
				'undefined' !== typeof window.getSelection &&
				'undefined' !== typeof document.createRange &&
				! _.isNull( activity_comment_data )
			) {
				var range = document.createRange();
				range.selectNodeContents( acInputElem[0] );
				range.collapse( false );
				var selection = window.getSelection();
				selection.removeAllRanges();
				selection.addRange( range );
			}

			if ( ! _.isUndefined( window.MediumEditor ) && ! acInputElem.hasClass( 'medium-editor-element' ) ) {
				window.activity_comment_editor = new window.MediumEditor(
					acInputElem[0],
					{
						placeholder     : false,
						toolbar         : false,
						paste           : {
							forcePlainText : false,
							cleanPastedHTML: false
						},
						keyboardCommands: false,
						imageDragging   : false,
						anchorPreview   : false,
					}
				);
			}
		},

		submitActivityComment: function ( args ) {
			var event         = args.event,
				parent        = args.parent,
				target        = args.target,
				activityId    = args.activityId,
				isInsideModal = args.isInsideModal,
				activityState = args.activityState,
				commentsText  = args.commentsText,
				activityItem  = args.activityItem,
				self          = args.self,
				model;

			target.prop( 'disabled', true );

			var commentContent, commentData;

			var commentsList = target.closest( '.bb-rl-activity-comments' );
			commentsList.addClass( 'active' );

			var form   = target.closest( 'form' );
			var itemId = activityId;

			// Stop event propagation.
			event.preventDefault();

			var $closestLi = target.closest( 'li' );
			if ( $closestLi.data( 'bp-activity-comment-id' ) ) {
				itemId = $closestLi.data( 'bp-activity-comment-id' );
			}

			commentContent = $( form ).find( '.ac-input' ).first();

			// Replacing atwho query from the comment content to disable querying it in the requests.
			commentContent.find( 'span.atwho-query' ).each(
				function () {
					$( this ).replaceWith( this.innerText );
				}
			);

			// transform other emoji into emojionearea emoji.
			commentContent.find( 'img.emoji' ).each(
				function () {
					$( this ).addClass( 'emojioneemoji' ).attr( 'data-emoji-char', $( this ).attr( 'alt' ) ).removeClass( 'emoji' );
				}
			);

			// Transform emoji image into emoji unicode.
			commentContent.find( 'img.emojioneemoji' ).replaceWith(
				function () {
					return this.dataset.emojiChar;
				}
			);

			if ( 'undefined' === typeof activityId && target.parents( '.bb-rl-modal-activity-footer' ).length > 0 ) {
				activityId = target.parents( 'form.ac-form' ).find( 'input[name=comment_form_id]' ).val();
				itemId     = activityId;
			}

			target.parent().addClass( 'loading' ).prop( 'disabled', true );
			commentContent.addClass( 'loading' ).prop( 'disabled', true );
			var commentValue = commentContent[0].innerHTML.replace( /<div>/gi, '\n' ).replace( /<\/div>/gi, '' );

			commentData = {
				action                       : 'new_activity_comment',
				_wpnonce_new_activity_comment: $( '#_wpnonce_new_activity_comment' ).val(),
				comment_id                   : itemId,
				form_id                      : activityId,
				content                      : commentValue
			};

			// Add the Akismet nonce if it exists.
			var akismetNonce = $( '#_bp_as_nonce_' + activityId ).val();
			if ( akismetNonce ) {
				commentData['_bp_as_nonce_' + activityId] = akismetNonce;
			}

			if ( this.dropzone_media.length ) { // add media data if enabled or uploaded.
				commentData.media = this.dropzone_media;
			}
			if ( this.dropzone_document.length ) { // add document data if enabled or uploaded.
				commentData.document = this.dropzone_document;
			}
			if ( this.dropzone_video.length ) { // add video data if enabled or uploaded.
				commentData.video = this.dropzone_video;
				if ( _.isEmpty( commentData.content ) ) {
					commentData.content = '&#8203;';
				}
			}
			if ( ! _.isUndefined( this.models[activityId] ) ) { // add gif data if enabled or uploaded.
				model                = this.models[activityId];
				commentData.gif_data = this.models[activityId].get( 'gif_data' );
			}

			commentData.content = commentData.content.replace( /&nbsp;/g, ' ' );

			if ( form.hasClass( 'acomment-edit' ) ) {
				commentData.edit_comment = true;
			}

			var isFooterForm = target.closest( '.bb-rl-modal-activity-footer' ).length > 0;

			parent.ajax( commentData, 'activity' ).done(
				function ( response ) {
					target.parent().removeClass( 'loading' );
					commentContent.removeClass( 'loading' );
					$( '.acomment-reply' ).attr( 'aria-expanded', 'false' );

					if ( false === response.success ) {
						form.append( $( response.data.feedback ).hide().fadeIn( 200 ) );
					} else {
						var isElementorWidget            = target.closest( '.elementor-activity-item' ).length > 0;
						var isCommentElementorWidgetForm = form.prev().hasClass( 'activity-actions' );
						var activity_comments            = isElementorWidget && isCommentElementorWidgetForm ? form.parent().find( '.activity-actions' ) : form.parent();
						var the_comment                  = $.trim( response.data.contents );

						activity_comments.find( '.bb-rl-acomment-display' ).removeClass( 'bb-rl-display-focus' );
						activity_comments.find( '.comment-item' ).removeClass( 'bb-rl-comment-item-focus' );
						activity_comments.addClass( 'has-child-comments' );

						var form_activity_id = form.find( 'input[name="comment_form_id"]' ).val();
						if ( isInsideModal ) {
							$( '#bb-rl-activity-modal' ).find( '.bb-rl-modal-activity-footer' ).append( form ).addClass( 'active' );
							form.removeClass( 'has-content' ).addClass( 'root' );
						} else {
							form.addClass( 'not-initialized' ).removeClass( 'has-content has-gif has-media' );
							form.closest( '.bb-rl-activity-comments' ).append( form );
						}
						form.find( '#ac-input-' + form_activity_id ).html( '' );

						if ( form.hasClass( 'acomment-edit' ) ) {
							var form_item_id = form.attr( 'data-item-id' );
							form.closest( '.bb-rl-activity-comments' ).append( form );
							var $commentContainer = isInsideModal ? $( '#bb-rl-activity-modal' ).find( 'li#bb-rl-acomment-' + form_item_id ) : $( 'li#bb-rl-acomment-' + form_item_id );
							$commentContainer.replaceWith( the_comment );
						} else {
							if ( 0 === activity_comments.children( 'ul' ).length ) {
								activity_comments[activity_comments.hasClass( 'bb-rl-activity-comments' ) ? 'prepend' : 'append']( '<ul></ul>' );
							}

							var $commentList = isFooterForm ? form.closest( '#bb-rl-activity-modal' ).find( '.bb-rl-modal-activity-body .bb-rl-activity-comments, .bb-rl-modal-activity-body .bb-rl-activity-comments .activity-actions' ).children( 'ul' ) : activity_comments.children( 'ul' );
							$commentList.append( $( the_comment ).hide().fadeIn( 200 ) );

							$( form ).find( '.ac-input' ).first().html( '' );
							activity_comments.parent().addClass( 'has-comments comments-loaded' );
							activityState.addClass( 'has-comments' );
						}

						form.removeClass( 'acomment-edit' );

						var tool_box_comment = form.find( '.bb-rl-ac-reply-content' );
						var buttons          = [
							'.bb-rl-ac-reply-media-button',
							'.bb-rl-ac-reply-document-button',
							'.bb-rl-ac-reply-video-button',
							'.bb-rl-ac-reply-gif-button'
						];
						buttons.forEach(
							function ( button ) {
								var $button = tool_box_comment.find( '.bb-rl-ac-reply-toolbar ' + button );
								if ( $button.length > 0 ) {
									if ( 'bb-rl-ac-reply-gif-button' === button ) {
										$button.removeClass( 'active' );
									}
									$button.parents( '.bb-rl-post-elements-buttons-item' ).removeClass( 'disable no-click' );
								}
							}
						);
						jQuery( window ).scroll();

						if ( ! form.hasClass( 'acomment-edit' ) ) {
							// Set the new count.
							var comment_count_span = activityState.find( 'span.comments-count' );
							var comment_count      = comment_count_span.text().length ? comment_count_span.text().match( /\d+/ )[0] : 0;
							comment_count          = Number( comment_count ) + 1;

							if ( commentsText.length ) {
								var label = comment_count > 1 ? bbRlActivity.strings.commentsLabel : bbRlActivity.strings.commentLabel;
								commentsText.text( label.replace( '%d', comment_count || 1 ) );
								comment_count_span.attr( 'data-comments-count', comment_count );
							}

							// Update reply count with parent replies count.
							var parentCommentId = commentData.comment_id;
							if ( parentCommentId ) {
								var $parentComment = $( '#bb-rl-acomment-' + parentCommentId ),
								    processedIds   = {};

								while ( $parentComment.length ) {
									var parentId = $parentComment.data( 'bp-activity-comment-id' );
									if ( processedIds[ parentId ] ) { // Skip if already processed.
										break;
									}
									processedIds[ parentId ] = true;

									var $reactionArea = $parentComment.find( '.bb-rl-comment-reactions' ).first(),
									    $countElement = $reactionArea.find( '.acomments-count' ),
									    currentCount  = 0,
									    newCount;

									if ( $countElement.length ) { // Get current count.
										currentCount = parseInt( $countElement.attr( 'data-comments-count' ) );
										if ( isNaN( currentCount ) ) {
											var matches  = $countElement.text().match( /\d+/ );
											currentCount = matches ? parseInt( matches[ 0 ] ) : 0;
										}
									}

									newCount       = currentCount + 1;
									var replyLabel = newCount > 1 ? bbRlActivity.strings.repliesLabel : bbRlActivity.strings.replyLabel;

									// Update an or add count element.
									if ( $countElement.length ) {
										$countElement.attr( 'data-comments-count', newCount ).text( replyLabel.replace( '%d', newCount ) );
									} else {
										$reactionArea.append(
											'<a href="#" class="activity-state-comments has-comments">' +
											'<span class="acomments-count" data-comments-count="1">' +
											bbRlActivity.strings.replyLabel.replace( '%d', 1 ) +
											'</span>' +
											'</a>'
										);
									}

									// Add class and move to the next parent
									$parentComment.addClass( 'has-child-comments' );
									$parentComment = $parentComment.parent().closest( '.activity-comment' );
								}
							}

							comment_count_span.parent( ':not( .has-comments )' ).addClass( 'has-comments' );

							// Increment the 'Show all x comments' string, if present.
							var show_all_a = $( activityItem ).find( '.show-all a' );
							if ( show_all_a ) {
								show_all_a.html( bbbRlShowXComments.replace( '%d', comment_count ) );
							}
						}

						// Keep the dropzone media saved, so it won't remove its attachment when destroyed.
						[
							self.dropzone_media,
							self.dropzone_document,
							self.dropzone_video
						].forEach(
							function ( dropzone ) {
								if ( dropzone && dropzone.length > 0 ) {
										dropzone.forEach(
											function ( item ) {
												item.saved = true;
											}
										);
								}
							}
						);

						bp.Nouveau.Activity.activityHasUpdates = true;
						bp.Nouveau.Activity.currentActivityId  = activityId;

					}

					if ( ! _.isUndefined( model ) ) {
						model.set( 'gif_data', {} );
						$( '#bb-rl-ac-reply-post-gif-' + activityId ).find( '.bb-rl-activity-attached-gif-container' ).removeAttr( 'style' );
					}

					['media', 'document', 'video'].forEach(
						function ( type ) {
							self.destroyUploader( type, activityId );
						}
					);

					target.prop( 'disabled', false );
					commentContent.prop( 'disabled', false );

					commentsList.removeClass( 'active' );

					bp.Nouveau.Activity.clearFeedbackNotice( form );
				}
			).fail(
				function ( $xhr ) {
					target.parent().removeClass( 'loading' );
					target.prop( 'disabled', false );

					bp.Nouveau.Activity.clearFeedbackNotice( form );

					var errorMessage = $xhr.readyState === 0 ? bbRlActivity.strings.commentPostError : (
						$xhr.responseJSON && $xhr.responseJSON.message ? $xhr.responseJSON.message : $xhr.statusText
					);
					form.find( '.bb-rl-ac-reply-content' ).after( '<div class="bb-rl-notice bb-rl-notice--error">' + errorMessage + '</div>' );
				}
			);
		},

		pinUnpinActivity: function ( args ) {
			var event      = args.event,
				activityId = args.activityId;

			// Stop event propagation.
			event.preventDefault();

			if ( ! activityId ) {
				return event;
			}

			var parent        = args.parent,
				target        = args.target,
				isInsideModal = args.isInsideModal;

			var activityItem = target.closest( '.activity-item' );
			activityItem.addClass( 'loading-pin' );

			var pin_action = target.hasClass( 'unpin-activity' ) ? 'unpin' : 'pin';

			parent.ajax(
				{
					action    : 'activity_update_pinned_post',
					id        : activityId,
					pin_action: pin_action
				},
				'activity'
			).done(
				function ( response ) {
					activityItem.removeClass( 'loading-pin' );

					// Check for JSON output.
					if ( 'object' !== typeof response ) {
						response = JSON.parse( response );
					}
					if ( 'undefined' !== typeof response.data && 'undefined' !== typeof response.data.feedback ) {
						var activityList   = target.closest( 'ul.bb-rl-activity-list' );
						var activityStream = isInsideModal ? target.closest( '.bb-rl-wrap' ).find( '#bb-rl-activity-stream' ) : target.closest( '#bb-rl-activity-stream' );

						if ( response.success ) {
							var scope = bp.Nouveau.getStorage( 'bp-activity', 'scope' );
							if (
								(
									'' === scope ||
									false === scope ||
									(
										'undefined' !== BP_Nouveau.is_send_ajax_request &&
										'' === BP_Nouveau.is_send_ajax_request
									)
								) &&
								$( bp.Nouveau.objectNavParent + ' #bb-subnav-filter-show [data-bp-scope].selected' ).length
							) {
								// Get the filter selected.
								scope = $( bp.Nouveau.objectNavParent + ' #bb-subnav-filter-show [data-bp-scope].selected' ).data( 'bp-scope' );
							}
							var update_pinned_icon = false;
							var is_group_activity  = activityItem.hasClass( 'groups' );
							var activity_group_id  = '';

							if ( is_group_activity ) {
								var groupClass    = activityItem.attr( 'class' ).match( /group-\d+/ );
								activity_group_id = groupClass ? groupClass[0].replace( 'group-', '' ) : '';
							}

							if ( activityStream.hasClass( 'single-user' ) ) {
								update_pinned_icon = false;
							} else if (
								activityStream.hasClass( 'activity' ) &&
								'all' === scope &&
								! is_group_activity
							) {
								update_pinned_icon = true;
							} else if ( activityStream.hasClass( 'single-group' ) ) {
								update_pinned_icon = true;
							}

							// Change the pinned class and label.
							if ( 'pin' === pin_action ) {

								// Remove class from all old pinned and update action labels and icons.
								if ( update_pinned_icon ) {
									activityList.find( 'li.activity-item' ).removeClass( 'bb-pinned' );
								}

								var update_pin_actions = is_group_activity && ! activityStream.hasClass( 'single-group' ) ?
									'li.activity-item.group-' + activity_group_id :
									'li.activity-item';
								activityList.find( update_pin_actions ).each(
									function () {
										var action = $( this ).find( '.unpin-activity' );
										action.removeClass( 'unpin-activity' ).addClass( 'pin-activity' );

										var pinText = is_group_activity ? bbRlActivity.strings.pinGroupPost : bbRlActivity.strings.pinPost;
										action.find( 'span' ).html( pinText );
									}
								);

								if ( update_pinned_icon ) {
									activityItem.addClass( 'bb-pinned' );
								}

								target.addClass( 'unpin-activity' ).removeClass( 'pin-activity' );

								var unpinText = is_group_activity ? bbRlActivity.strings.unpinGroupPost : bbRlActivity.strings.unpinPost;
								target.find( 'span' ).html( unpinText );
							} else if ( 'unpin' === pin_action ) {
								activityItem.removeClass( 'bb-pinned' );
								target.addClass( 'pin-activity' ).removeClass( 'unpin-activity' );
								var pinText = is_group_activity ? bbRlActivity.strings.pinGroupPost : bbRlActivity.strings.pinPost;
								target.find( 'span' ).html( pinText );
							}

							if ( update_pinned_icon ) {
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

						if ( isInsideModal ) {
							if ( 'undefined' !== typeof bp.Nouveau.Activity.activityHasUpdates ) {
								bp.Nouveau.Activity.activityHasUpdates = true;
							}
							if ( 'undefined' !== typeof bp.Nouveau.Activity.activityPinHasUpdates ) {
								bp.Nouveau.Activity.activityPinHasUpdates = true;
							}
						}
					}
				}
			).fail(
				function () {
					target.closest( '.activity-item' ).removeClass( 'loading-pin' );
					$( document ).trigger(
						'bb_trigger_toast_message',
						[
							'',
							'<div>' + bbRlActivity.strings.pinPostError + '</div>',
							'error',
							null,
							true
						]
					);
				}
			);
		},

		muteUnmuteNotifications: function ( args ) {
			var event         = args.event,
				parent        = args.parent,
				target        = args.target,
				activityId    = args.activityId,
				isInsideModal = args.isInsideModal;

			if ( target.hasClass( 'bb-icon-bell-slash' ) || target.hasClass( 'bb-icon-bell' ) ) {
				// Stop event propagation.
				event.preventDefault();

				if ( ! activityId ) {
					return event;
				}
				var $activityItem = target.closest( '.activity-item' );
				$activityItem.addClass( 'loading-mute' );

				var notification_toggle_action = target.hasClass( 'bb-icon-bell' ) ? 'unmute' : 'mute';

				parent.ajax(
					{
						action                    : 'toggle_activity_notification_status',
						id                        : activityId,
						notification_toggle_action: notification_toggle_action
					},
					'activity'
				).done(
					function ( response ) {
						$activityItem.removeClass( 'loading-mute' );

						// Check for JSON output.
						if ( 'object' !== typeof response ) {
							response = JSON.parse( response );
						}

						if ( 'undefined' !== typeof response.data && 'undefined' !== typeof response.data.feedback ) {

							if ( response.success ) {
								// Change the muted class and label.
								if ( 'mute' === notification_toggle_action ) {
									var unmuteLabel = bbRlActivity.strings.unmuteNotification;
									$activityItem.addClass( 'bb-muted' );
									target.removeClass( 'bb-icon-bell-slash' );
									target.addClass( 'bb-icon-bell' );
									target.attr( 'title', unmuteLabel );
									target.find( 'span' ).html( unmuteLabel );
								} else if ( 'unmute' === notification_toggle_action ) {
									var muteLabel = bbRlActivity.strings.muteNotification;
									$activityItem.removeClass( 'bb-muted' );
									target.removeClass( 'bb-icon-bell' );
									target.addClass( 'bb-icon-bell-slash' );
									target.attr( 'title', muteLabel );
									target.find( 'span' ).html( muteLabel );
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
						bp.Nouveau.Activity.currentActivityId  = activityId;
					}
				).fail(
					function () {
						target.closest( '.activity-item' ).removeClass( 'loading-pin' );
						$( document ).trigger(
							'bb_trigger_toast_message',
							[
								'',
								'<div>' + bbRlActivity.strings.pinPostError + '</div>',
								'error',
								null,
								true
							]
						);
					}
				);
			}
		},

		onOffComment: function ( args ) {
			var event      = args.event,
				target     = args.target,
				parent     = args.parent,
				activityId = args.activityId;
			// Stop event propagation.
			event.preventDefault();

			if ( ! activityId ) {
				return event;
			}

			var $activityItem = target.closest( '.activity-item' );
			$activityItem.addClass( 'loading-pin' );

			var close_comments_action = target.hasClass( 'unclose-activity-comment' ) ? 'unclose_comments' : 'close_comments';

			parent.ajax(
				{
					action               : 'activity_update_close_comments',
					id                   : activityId,
					close_comments_action: close_comments_action
				},
				'activity'
			).done(
				function ( response ) {
					$activityItem.removeClass( 'loading-pin' );

					// Check for JSON output.
					if ( 'object' !== typeof response ) {
						response = JSON.parse( response );
					}
					if ( 'undefined' !== typeof response.data && 'undefined' !== typeof response.data.feedback ) {
						if ( response.success ) {
							var $media_parent = $( '#bb-rl-activity-stream > .bb-rl-activity-list' ).find( '[data-bp-activity-id=' + activityId + ']' );
							$activityItem.find( '.bb-rl-activity-closed-comments-notice' ).remove();
							// Change the close comments related class and label.
							if ( 'close_comments' === close_comments_action ) {
								$activityItem.addClass( 'bb-closed-comments' );
								if ( $activityItem.closest( '#bb-rl-activity-modal' ).length > 0 ) {
									$activityItem.closest( '#bb-rl-activity-modal' ).addClass( 'bb-closed-comments' );
								}
								target.addClass( 'unclose-activity-comment' ).removeClass( 'close-activity-comment' );
								target.find( 'span' ).html( bbRlActivity.strings.uncloseComments );
								$activityItem.find( '.edit-activity, .acomment-edit' ).parents( '.generic-button' ).hide();
								$activityItem.find( '.bb-rl-activity-comments' ).after( '<div class="bb-rl-activity-closed-comments-notice">' + response.data.feedback + '</div>' );
								// Handle event from media theater.
								if ( target.parents( '.bb-rl-media-model-wrapper' ).length > 0 && $media_parent.length > 0 ) {
									$media_parent.addClass( 'bb-closed-comments' );
									$media_parent.find( '.bb-activity-more-options .close-activity-comment span' ).html( bbRlActivity.strings.uncloseComments );
									$media_parent.find( '.bb-activity-more-options .close-activity-comment' ).addClass( 'unclose-activity-comment' ).removeClass( 'close-activity-comment' );
									$media_parent.find( '.edit-activity, .acomment-edit' ).parents( '.generic-button' ).hide();
									$media_parent.find( '.bb-rl-activity-comments' ).after( '<div class="bb-rl-activity-closed-comments-notice">' + response.data.feedback + '</div>' );
								}
							} else if ( 'unclose_comments' === close_comments_action ) {
								$activityItem.find( '.edit-activity, .acomment-edit' ).parents( '.generic-button' ).show();
								$activityItem.removeClass( 'bb-closed-comments' );
								if ( $activityItem.closest( '#bb-rl-activity-modal' ).length > 0 ) {
									$activityItem.closest( '#bb-rl-activity-modal' ).removeClass( 'bb-closed-comments' );
								}
								target.addClass( 'close-activity-comment' ).removeClass( 'unclose-activity-comment' );
								target.find( 'span' ).html( bbRlActivity.strings.closeComments );

								// Handle event from media theater.
								if ( target.parents( '.bb-rl-media-model-wrapper' ).length > 0 && $media_parent.length > 0 ) {
									$media_parent.find( '.edit-activity, .acomment-edit' ).parents( '.generic-button' ).show();
									$media_parent.removeClass( 'bb-closed-comments' );
									$media_parent.find( '.bb-activity-more-options .unclose-activity-comment span' ).html( bbRlActivity.strings.closeComments );
									$media_parent.find( '.bb-activity-more-options .unclose-activity-comment' ).addClass( 'close-activity-comment' ).removeClass( 'unclose-activity-comment' );
									$media_parent.find( '.bb-rl-activity-closed-comments-notice' ).html( '' );
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
				function () {
					target.closest( '.activity-item' ).removeClass( 'loading-pin' );
					$( document ).trigger(
						'bb_trigger_toast_message',
						[
							'',
							'<div>' + bbRlActivity.strings.closeCommentsError + '</div>',
							'error',
							null,
							true
						]
					);
				}
			);
		},

		setupDropzoneEventHandlers: function( args ) {
			var self            = args.self,
			    dropzoneObj     = args.dropzoneObj,
			    target          = args.target,
			    type            = args.type,
			    dropzoneDataObj = args.dropzoneDataObj;

			dropzoneObj.on(
				'addedfile',
				function ( file ) {
					// Set data from edit comment.
					if ( file[ type + '_edit_data' ] ) {
						dropzoneDataObj.push( file[ type + '_edit_data' ] );
						if ( 'document' === type ) {
							var filename      = file.upload.filename;
							var fileExtension = filename.substr( (
								filename.lastIndexOf( '.' ) + 1
							) );
							$( file.previewElement ).find( '.dz-details .dz-icon .bb-icon-file' ).removeClass( 'bb-icon-file' ).addClass( 'bb-icon-file-' + fileExtension );
						}
						var tool_box = target.parents( '.bb-rl-ac-reply-toolbar' );
						if ( tool_box.find( '.bb-rl-ac-reply-' + type + '-button' ) ) {
							tool_box.find( '.bb-rl-ac-reply-' + type + '-button' ).parents( '.bb-rl-post-elements-buttons-item' ).addClass( 'no-click' ).find( '.toolbar-button' ).addClass( 'active' );
						}
					}
					if ( 'video' === type ) {
						if ( file.dataURL && file.video_edit_data.thumb.length ) {
							// Get Thumbnail image from response.
							$( file.previewElement ).find( '.dz-image' ).prepend( '<img src=" ' + file.video_edit_data.thumb + ' "  alt=""/>' );
							$( file.previewElement ).closest( '.dz-preview' ).addClass( 'dz-has-thumbnail' );
						} else {
							if ( bp.Nouveau.getVideoThumb ) {
								bp.Nouveau.getVideoThumb( file, '.dz-image' );
							}

						}
					}
				}
			);

			dropzoneObj.on(
				'sending',
				function ( file, xhr, formData ) {
					var action = 'document' === type ? type + '_' + type + '_upload' : type + '_upload';
					formData.append( 'action', action );
					var nonce = 'document' === type ? 'media' : type;
					formData.append( '_wpnonce', BP_Nouveau.nonces[ nonce ] );

					var tool_box    = target.parents( '.bb-rl-ac-reply-toolbar' );
					var commentForm = target.closest( '.ac-form' );

					commentForm.addClass( 'has-media' );
					['media', 'document', 'video', 'gif'].forEach( function ( subType ) {
						if ( tool_box.find( '.bb-rl-ac-reply-' + subType + '-button' ) ) {
							var $buttonElement = tool_box.find( '.bb-rl-ac-reply-' + subType + '-button' ).parents( '.bb-rl-post-elements-buttons-item' );
							if ( type === subType ) {
								$buttonElement.addClass( 'no-click' ).find( '.toolbar-button' ).addClass( 'active' );
							} else {
								$buttonElement.addClass( 'disable' );
							}
						}
					} );
					this.element.classList.remove( 'files-uploaded' );
				}
			);

			dropzoneObj.on(
				'uploadprogress',
				function ( element ) {
					var commentForm = target.closest( '.ac-form' );
					commentForm.addClass( 'media-uploading' );

					var circle        = $( element.previewElement ).find( '.dz-progress-ring circle' )[ 0 ],
					radius        = circle.r.baseVal.value,
					circumference = radius * 2 * Math.PI;

					circle.style.strokeDasharray  = circumference + ' ' + circumference;
					circle.style.strokeDashoffset = circumference - (
						element.upload.progress.toFixed( 0 ) / 100 * circumference
					);
					$( element.previewElement ).find( '.dz-progress [data-dz-progress]' ).text( element.upload.progress.toFixed( 0 ) + '%' );
				}
			);

			dropzoneObj.on(
				'success',
				function ( file, response ) {
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
							icon_class    = ! _.isEmpty( file_icon ) ? file_icon : 'bb-icon-file-' + fileExtension;
						if ( $( file.previewElement ).find( '.dz-details .dz-icon .bb-icons-rl-file' ).length ) {
							$( file.previewElement ).find( '.dz-details .dz-icon .bb-icons-rl-file' ).removeClass( 'bb-icons-rl-file' ).addClass( icon_class );
						}
					}

					if ( response.data.id ) {
						file.id                  = response.id;
						response.data.uuid       = file.upload.uuid;
						response.data.menu_order = $( file.previewElement ).closest( '.dropzone' ).find( file.previewElement ).index() - 1;
						var subType = 'document' === type ? 'media' : type;
						response.data.album_id   = typeof BP_Nouveau[subType] !== 'undefined' && typeof BP_Nouveau[subType].album_id !== 'undefined' ? BP_Nouveau[subType].album_id : false;
						response.data.group_id   = typeof BP_Nouveau[subType] !== 'undefined' && typeof BP_Nouveau[subType].group_id !== 'undefined' ? BP_Nouveau[subType].group_id : false;
						response.data.saved      = false;
						dropzoneDataObj.push( response.data );
						return file.previewElement.classList.add( 'dz-success' );
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
						if ( ! _.isNull( dropzoneObj.files ) && dropzoneObj.files.length === 0 ) {
							$( dropzoneObj.element ).removeClass( 'files-uploaded dz-progress-view' ).find( '.dz-global-progress' ).remove();
						}
						return _results;
					}
				}
			);

			dropzoneObj.on(
				'accept',
				function ( file, done ) {
					if ( file.size === 0 ) {
						var subType   = 'document' === type ? 'media' : type;
						var emptyType = 'video' === type ? 'empty_' + type + '_type' : 'empty_document_type';
						done( BP_Nouveau[ subType ][ emptyType ] );
					} else {
						done();
					}
				}
			);

			dropzoneObj.on(
				'error',
				function ( file, response ) {
					if ( file.accepted ) {
						if ( typeof response !== 'undefined' && typeof response.data !== 'undefined' && typeof response.data.feedback !== 'undefined' ) {
							$( file.previewElement ).find( '.dz-error-message span' ).text( response.data.feedback );
						} else if ( file.status === 'error' && (
						            file.xhr && file.xhr.status === 0
						) ) { // update server error text to user friendly.
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

			dropzoneObj.on(
				'removedfile',
				function ( file ) {
					if ( dropzoneDataObj.length ) {
						for ( var i in dropzoneDataObj ) {
							if ( file.upload.uuid === dropzoneDataObj[ i ].uuid ) {
								if ( typeof dropzoneDataObj[ i ].saved !== 'undefined' && ! dropzoneDataObj[ i ].saved ) {
									bp.Nouveau.Media.removeAttachment( dropzoneDataObj[ i ].id );
								}
								dropzoneDataObj.splice( i, 1 );
								break;
							}
						}
					}
					if ( ! _.isNull( dropzoneObj ) && ! _.isNull( dropzoneObj.files ) && dropzoneObj.files.length === 0 ) {
						var tool_box    = target.parents( '.bb-rl-ac-reply-toolbar' );
						var commentForm = target.closest( '.ac-form' );
						commentForm.removeClass( 'has-media' );
						['media', 'document', 'video', 'gif'].forEach( function ( subType ) {
							if ( tool_box.find( '.bb-rl-ac-reply-' + subType + '-button' ) ) {
								var $buttonElement = tool_box.find( '.bb-rl-ac-reply-' + subType + '-button' ).parents( '.bb-rl-post-elements-buttons-item' );
								if ( type === subType ) {
									$buttonElement.removeClass( 'no-click' ).find( '.toolbar-button' ).removeClass( 'active' );
								} else {
									$buttonElement.removeClass( 'disable' );
								}
							}
						} );
						$( dropzoneObj.element ).removeClass( 'files-uploaded dz-progress-view' ).find( '.dz-global-progress' ).remove();
						self.validateCommentContent( commentForm.find( '.ac-textarea' ).children( '.ac-input' ) );
					} else {
						target.closest( '.ac-form' ).addClass( 'has-content' );
					}
				}
			);

			// Enable submit button when all medias are uploaded.
			dropzoneObj.on(
				'complete',
				function () {
					if ( this.getUploadingFiles().length === 0 && this.getQueuedFiles().length === 0 && this.files.length > 0 ) {
						var commentForm = target.closest( '.ac-form' );
						commentForm.removeClass( 'media-uploading' );
						this.element.classList.add( 'files-uploaded' );
					}
				}
			);
		},

		loadMoreComments : function ( options ) {
			var defaults = {
				target               : null,
				activityId           : null,
				parentCommentId      : 0,
				isModal              : false,
				lastCommentId        : '',
				lastCommentTimestamp : ''
			};

			var settings = $.extend( {}, defaults, options );
			var $target  = settings.target;

			// Common skeleton HTML
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

			// Common loading states
			$target.addClass( 'loading' );
			if ( ! settings.isModal ) {
				settings.commentsList.addClass( 'active' );
				settings.commentsActivityItem.addClass( 'active' );
			}
			$target.html( skeleton );

			// Prepare ajax data
			var data = {
				action                : settings.isModal ? 'bb_rl_activity_loadmore_comments' : 'activity_loadmore_comments',
				activity_id           : settings.activityId,
				parent_comment_id     : settings.parentCommentId,
				offset                : settings.isModal ? 0 : $target.parents( '.bb-rl-activity-comments' ).find( 'ul[data-parent_comment_id ="' + settings.parentCommentId + '"] > li.comment-item:not(.bb-recent-comment)' ).length,
				activity_type_is_blog : $target.parents( '.entry-content' ).length > 1
			};

			// Add timestamp and comment ID if available
			if ( settings.lastCommentTimestamp ) {
				data.last_comment_timestamp = settings.lastCommentTimestamp;
			}
			if ( settings.lastCommentId ) {
				data.last_comment_id = settings.lastCommentId;
			}

			// Make AJAX request
			bp.Nouveau.ajax( data, 'activity' ).done( function ( response ) {
				if ( false === response.success ) {
					$target.html( '<p class=\'error\'>' + response.data.message + '</p>' ).removeClass( 'acomments-view-more--hide' );
					if ( ! settings.isModal ) {
						settings.commentsList.removeClass( 'active' );
						settings.commentsActivityItem.removeClass( 'active' );
					}
					return;
				}

				if ( 'undefined' !== typeof response.data && 'undefined' !== typeof response.data.comments ) {
					var $activityItem = $target.closest( '.activity-item' );
					if ( ! $activityItem.hasClass( 'has-comments' ) ) {
						$activityItem.addClass( 'has-comments' );
					}

					// Get a target list based on modal or regular view.
					var activityCommentsElem = $( '.bb-rl-internal-model .bb-rl-activity-comments' );
					var $targetList = settings.isModal ? activityCommentsElem : activityCommentsElem.find( '[data-activity_id=\'' + settings.activityId + '\'][data-parent_comment_id=\'' + settings.parentCommentId + '\']' );

					var $newComments = $( $.parseHTML( response.data.comments ) );

					if ( $targetList.length > 0 && $newComments.length > 0 ) {
						// Handle duplicates
						$newComments.each( function () {
							if ( 'LI' === this.nodeName && 'undefined' !== this.id && '' !== this.id ) {
								var newCommentId     = this.id;
								var $existingComment = $targetList.children( '#' + newCommentId );
								if ( $existingComment.length > 0 ) {
									$existingComment.remove();
								}
							}
						} );

						// Insert comments based on context
						if ( settings.isModal ) {
							$targetList.html( $newComments );
						} else {
							if ( settings.lastCommentId ) {
								var $addAfterElement = $targetList.find( 'li.activity-comment[data-bp-activity-comment-id=\'' + settings.lastCommentId + '\']' );
								if ( $addAfterElement.length > 0 ) {
									$addAfterElement.after( $newComments );
								} else {
									$targetList.append( $newComments );
								}
							} else if ( $targetList.children( '.activity-comment.comment-item' ).length > 0 ) {
								$targetList.children( '.activity-comment.comment-item' ).first().before( $newComments );
							} else {
								$targetList.html( $newComments );
							}
						}

						// Common post-load operations
						setTimeout( function () {
							jQuery( window ).scroll();
						}, 200 );

						// Scroll to comment if needed
						if ( ! $target.hasClass( 'acomments-view-more--root' ) ) {
							$( '.bb-rl-modal-activity-body' ).scrollTo( '#bb-rl-acomment-' + settings.parentCommentId, 500, {
								offset : 0,
								easing : 'swing'
							} );
						}

						// Initialize popups
						if ( 'undefined' !== typeof bp.Nouveau ) {
							bp.Nouveau.reportPopUp();
							bp.Nouveau.reportedPopup();
						}

						// Set tooltip position
						var action_tooltip = $targetList.find( '.bb-activity-more-options-wrap' ).find( '.bb-activity-more-options-action' );
						action_tooltip.attr( 'data-balloon-pos', 'left' );
					}

					if ( ! settings.isModal ) {
						$target.remove();
						settings.commentsList.removeClass( 'active' );
						settings.commentsActivityItem.removeClass( 'active' );
					}

					// Handle comment form if present
					if ( 'undefined' !== typeof response.data.comment_form ) {
						var $activityComments = $( '.bb-rl-internal-model .bb-rl-modal-activity-footer' );
						$activityComments.find( '.bb-rl-ac-form-placeholder' ).after( response.data.comment_form );
						$activityComments.find( '#ac-form-' + settings.activityId ).removeClass( 'not-initialized' ).addClass( 'root events-initiated' ).find( '#ac-input-' + settings.activityId ).focus();

						var form = $activityComments.find( '#ac-form-' + settings.activityId );
						bp.Nouveau.Activity.clearFeedbackNotice( form );
						form.removeClass( 'events-initiated' );
						var ce = $activityComments.find( '.ac-form .ac-input[contenteditable]' );
						bp.Nouveau.Activity.listenCommentInput( ce );

						if ( ! _.isUndefined( bbRlMedia ) && ! _.isUndefined( bbRlMedia.emoji ) ) {
							bp.Nouveau.Activity.initializeEmojioneArea( true, '#bb-rl-activity-modal ', settings.activityId );
						}
					}
				}
			} ).fail( function ( $xhr ) {
				$target.html( '<p class=\'error\'>' + $xhr.statusText + '</p>' ).removeClass( 'acomments-view-more--hide' );
				if ( ! settings.isModal ) {
					settings.commentsList.removeClass( 'active' );
					settings.commentsActivityItem.removeClass( 'active' );
				}
			} );
		},
	};

	// Launch BP Nouveau Activity.
	bp.Nouveau.Activity.start();

} )( bp, jQuery );
