/* global bp, bbReadyLaunchFront, BP_Nouveau */
/* @version 1.0.0 */

window.bp = window.bp || {};

(
	function ( exports, $ ) {

		var bpNouveauLocal    = BP_Nouveau,
			bbRlIsAs3cfActive = bpNouveauLocal.bbRlIsAs3cfActive,
			bbRlMedia         = bpNouveauLocal.media,
			bbRlAjaxUrl       = bpNouveauLocal.ajaxurl,
			bbRlNonce         = bpNouveauLocal.nonces;

		/**
		 * [ReadLaunch description]
		 *
		 * @type {Object}
		 */
		bp.Readylaunch = {
			/**
			 * [start description]
			 *
			 * @return {[type]} [description]
			 */
			start : function () {
				this.deletedNotifications     = [];
				this.markAsReadNotifications  = [];
				this.notificationIconSelector = $( '.bb-icons-rl-bell-simple' );
				// Listen to events ("Add hooks!").
				this.addListeners();
				this.mobileSubMenu();

				// Initialize select2 filters with retry mechanism
				this.initSelect2Filters();

				this.styledSelect();

				this.bbReloadWindow();
				this.initBBNavOverflow();
			},

			/**
			 * [addListeners description]
			 */
			addListeners : function () {
				var $document = $( document );
				$( '.bb-nouveau-list' ).on( 'scroll', this.bbScrollHeaderDropDown.bind( this ) );
				$document.on( 'click', '.notification-link, .notification-header-tab-action, .bb-rl-header-container .bb-rl-load-more a', this.bbHandleLoadMore.bind( this ) );
				$document.on( 'heartbeat-send', this.bbHeartbeatSend.bind( this ) );
				$document.on( 'heartbeat-tick', this.bbHeartbeatTick.bind( this ) );
				$document.on( 'click', '.bb-rl-option-wrap__action', this.openMoreOption.bind( this ) );
				$document.on( 'click', this.closeMoreOption.bind( this ) );
				$document.on( 'click', '#bb-rl-profile-theme-light, #bb-rl-profile-theme-dark', this.ToggleDarkMode.bind( this ) );
				$document.on( 'click', '.header-aside div.menu-item-has-children > a', this.showHeaderNotifications.bind( this ) );
				$document.on( 'click', '.bb-rl-left-panel-mobile, .bb-rl-close-panel-mobile', this.toggleMobileMenu.bind( this ) );
				$document.on( 'click', '.bb-rl-left-panel-widget .bb-rl-list > h2', this.toggleLeftPanelWidget.bind( this ) );
				$document.on( 'click', '.action-unread', this.markNotificationRead.bind( this ) );
				$document.on( 'click', '.action-delete', this.markNotificationDelete.bind( this ) );
				$document.on( 'click', '.bb-rl-header-container .header-aside .user-link', this.profileNav.bind( this ) );
				$document.on( 'click', '.bb-rl-header-search', this.searchModelToggle.bind( this ) );
			},

			profileNav: function ( e ) {
				e.preventDefault();

				$( e.currentTarget ).closest( '.user-wrap' ).toggleClass( 'active' );
			},

			searchModelToggle: function ( e ) {
				e.preventDefault();

				$( '#bb-rl-network-search-modal' ).removeClass( 'bp-hide' );
			},

			/**
			 * [scrollHeaderDropDown description]
			 *
			 * @param e
			 */
			bbScrollHeaderDropDown: function ( e ) {
				var el = e.target;
				if ( 'notification-list' === el.id ) {
					var scrollThreshold = 30; // pixels from bottom
					var bottomReached   = (el.scrollTop + el.offsetHeight + scrollThreshold) >= el.scrollHeight;

					if (bottomReached && ! el.classList.contains( 'loading' ) ) {
						var load_more = $( el ).find( '.bb-rl-load-more' );
						if ( load_more.length ) {
							el.classList.add( 'loading' );
							load_more.find( 'a' ).trigger( 'click' );
						}
					}
				}
			},

			// Add Mobile menu toggle button.
			mobileSubMenu : function () {
				$( '.bb-readylaunch-mobile-menu .sub-menu, .bb-readylaunchpanel-menu .sub-menu' ).each(
					function () {
						$( this ).closest( 'li.menu-item-has-children' ).find( 'a:first' ).append( '<i class="bb-icons-rl-caret-down submenu-toggle"></i>' );
					}
				);

				$( document ).on(
					'click',
					'.submenu-toggle',
					function ( e ) {
						e.preventDefault();
						$( this ).closest( '.menu-item-has-children' ).toggleClass( 'open-parent' );
					}
				);
			},

			initSelect2Filters: function () {
				var self = this;
				var maxRetries = 3;
				var retryCount = 0;

				function tryInitSelect2() {
					// Check if document is ready
					if ( document.readyState !== 'complete' ) {
						if ( retryCount < maxRetries ) {
							retryCount++;
							setTimeout( tryInitSelect2, 500 );
						}
						return;
					}

					// Check if select2 library is available
					if ( typeof $.fn.select2 === 'undefined' ) {
						if ( retryCount < maxRetries ) {
							retryCount++;
							setTimeout( tryInitSelect2, 500 );
						}
						return;
					}

					// Check if elements exist
					var $selects = $( '.bb-rl-filter select' );
					if ( $selects.length === 0 ) {
						if ( retryCount < maxRetries ) {
							retryCount++;
							setTimeout( tryInitSelect2, 500 );
						}
						return;
					}

					// Initialize select2
					self.gridListFilter();
				}

				// Start the initialization process
				$( document ).ready( function () {
					tryInitSelect2();
				} );
			},

			gridListFilter: function () {
				$( '.bb-rl-filter select' ).each(
					function () {
						var $this   = $( this ),
						customClass = '';

						if ( $this.data( 'bb-caret' ) ) {
								customClass += ' bb-rl-caret-icon ';
						}

						if ( $this.data( 'bb-icon' ) ) {
							customClass += ' bb-rl-has-icon ';
							customClass += ' ' + $this.data( 'bb-icon' ) + ' ';
						}

						if ( $this.data( 'bb-border' ) === 'rounded' ) {
							customClass += ' bb-rl-rounded-border ';
						}

						if ( $this.data( 'dropdown-align' ) ) {
							customClass += ' bb-rl-align-adaptive ';
						}

						$this.select2(
							{
								theme: 'rl',
								dropdownParent: $this.parent()
							}
						);

						// Apply CSS classes after initialization
						$this.next( '.select2-container' ).find( '.select2-selection' ).addClass( 'bb-rl-select2-container' + customClass );

						// Add class to dropdown when it opens
						$this.on(
							'select2:open',
							function () {
								var $this   = $( this ),
								customDropDownClass = '';

								if ( $this.data( 'dropdown-align' ) ) {
									customDropDownClass += ' bb-rl-dropdown-align-adaptive ';
								}

								$( '.select2-dropdown' ).addClass( 'bb-rl-select2-dropdown' );
								// Ensure dropdown alignment adapts when there's insufficient space on the right side of the screen.
								// The '.bb-rl-dropdown-align-adaptive' class enables responsive positioning of Select2 dropdowns.
								$this.closest( '.bb-rl-filter' ).find( '.bb-rl-select2-dropdown' ).addClass( customDropDownClass );
							}
						);
					}
				);
			},

			styledSelect: function () {
				$( '.bb-rl-styled-select select' ).each(
					function () {
						var $this   = $( this ),
						customClass = '';

						// Check if parent container has specific class
						var $parent = $this.closest( '.bb-rl-styled-select' );
						if ( $parent.hasClass( 'bb-rl-styled-select--default' ) ) {
								customClass += ' bb-rl-select-default';
						}

						$this.select2(
							{
								theme: 'bb-rl-select2',
								containerCssClass: 'bb-rl-select2-container ' + customClass,
								dropdownCssClass: 'bb-rl-select2-dropdown',
								dropdownParent: $this.parent()
							}
						);
					}
				);
			},

			/**
			 * Handles "Load More" click or dropdown open
			 *
			 * @param {Object} e Event object
			 */
			bbHandleLoadMore: function ( e ) {
				e.preventDefault();

				// Identify the clicked element.
				var $target = $( e.target ).closest( '.notification-link, .notification-header-tab-action, .bb-rl-load-more a' );
				if ( ! $target.length ) {
					return;
				}

				// Locate the top-level container.
				var $container = $target.closest( '.notification-wrap' );
				if ( ! $container.length ) {
					return;
				}

				// Check if there's already a request in progress.
				if ( $container.data( 'loading' ) ) {
					return; // Exit if an AJAX request is already in progress.
				}

				// If it's the notification bell but dropdown is already open (toggle off), don't make AJAX request.
				if ( $target.hasClass( 'notification-link' ) && $container.hasClass( 'selected' ) ) {
					return;
				}

				// Set the 'loading' flag to true.
				$container.data( 'loading', true );

				// Get the container ID.
				var containerId = $container.attr( 'id' );

				var isMessage = $container.hasClass( 'bb-message-dropdown-notification' );
				var page      = $target.data( 'page' ) ? $target.data( 'page' ) + 1 : 1;

				this.bbPerformAjaxRequest(
					e,
					{
						action      : isMessage ? 'bb_fetch_header_messages' : 'bb_fetch_header_notifications',
						page        : page,
						isMessage   : isMessage,
						containerId : containerId,
					}
				);
			},

			/**
			 * Common AJAX handler for loading notifications
			 *
			 * @param {Object} e Event object
			 * @param {Object} options Options for AJAX request
			 */
			bbPerformAjaxRequest : function ( e, options ) {
				e.preventDefault();

				var defaults        = {
					action      : '',
					page        : 1,
					isMessage   : false,
					containerId : '',
				};
				var settings        = $.extend( defaults, options ),
					mainContainerID = $( '#' + settings.containerId ); // Show a loading indicator.
				if ( settings.page > 1 ) {
					mainContainerID.find( '.bb-rl-load-more' ).before( '<i class="bb-rl-loader"></i>' );
				} else {
					mainContainerID.find( '.notification-list' ).html( '<i class="bb-rl-loader"></i>' );
				}

				var data = {
					action : settings.action,
					nonce  : bbReadyLaunchFront.nonce,
					page   : settings.page,
				};

				$.ajax(
					{
						type    : 'POST',
						url     : bbReadyLaunchFront.ajax_url,
						data    : data,
						success : function ( response ) {
							// Reset the 'loading' flag once the request completes.
							mainContainerID.data( 'loading', false );

							if ( response.success && response.data ) {
								var container = mainContainerID.find( '.notification-list' );
								if ( container.find( '.bb-rl-loader' ).has( '.bb-rl-loader' ) ) {
									container.find( '.bb-rl-loader' ).remove( '.bb-rl-loader' );
								}
								if ( settings.page > 1 ) {
									container.find( '.bb-rl-load-more' ).replaceWith( response.data );
								} else {
									container.html( response.data );
								}
							}
						},
						error   : function () {
							// Reset the 'loading' flag in case of error.
							mainContainerID.data( 'loading', false );
							mainContainerID.find( '.notification-list' ).html( '<p>Failed to load data. Please try again.</p>' );
						},
						complete : function () {
							// Always reset the loading state, regardless of success or error.
							mainContainerID.data( 'loading', false );
							mainContainerID.find( '.notification-list' ).removeClass( 'loading' );
						}
					}
				);
			},

			/**
			 * [bbHeartbeatSend description] - Handles the request to the server.
			 *
			 * @param e
			 * @param data
			 */
			bbHeartbeatSend : function ( e, data ) {
				data.bb_fetch_header_notifications = true;

				// Include the markAsReadNotifications if there are any pending.
				if ( bp.Readylaunch.markAsReadNotifications.length > 0 ) {
					data.mark_as_read_notifications = bp.Readylaunch.markAsReadNotifications.join( ',' );
				}

				// Include the markAsDeleteNotifications if there are any pending.
				if ( bp.Readylaunch.deletedNotifications.length > 0 ) {
					data.mark_as_delete_notifications = bp.Readylaunch.deletedNotifications.join( ',' );
				}
			},

			/**
			 * [bbHeartbeatTick description] - Handles the response from the server.
			 *
			 * @param e
			 * @param data
			 */
			bbHeartbeatTick : function ( e, data ) {
				this.bbpInjectNotifications( e, data );

				// Check if markAsReadNotifications were processed.
				if ( data.mark_as_read_processed ) {
					bp.Readylaunch.markAsReadNotifications = []; // Clear the array.
				}

				// Check if markAsDeleteNotifications were processed.
				if ( data.mark_as_delete_processed ) {
					bp.Readylaunch.deletedNotifications = []; // Clear the array.
				}
			},

			/**
			 * [bbpInjectNotifications description]
			 *
			 * @param event
			 * @param data
			 */
			bbpInjectNotifications : function ( event, data ) {
				if ( typeof data.unread_notifications !== 'undefined' && data.unread_notifications !== '' ) {
					$( '#header-notifications-dropdown-elem .notification-dropdown .notification-list' ).empty().html( data.unread_notifications );
				}

				var notif_icons = bp.Readylaunch.notificationIconSelector.parent().children( '.count' );
				if ( typeof data.total_notifications !== 'undefined' && data.total_notifications > 0 ) {
					$( '.notification-header .mark-read-all' ).show();

					if ( notif_icons.length > 0 ) {
						$( notif_icons ).text( data.total_notifications );
					}
				} else {
					$( notif_icons ).remove();
					$( '.notification-header .mark-read-all' ).fadeOut();
				}
			},

			/**
			 * [openMoreOption Open more options dropdown]
			 *
			 * @param e
			 */
			openMoreOption : function ( e ) {
				e.preventDefault();

				// Close all other open dropdowns.
				$( '.bb-rl-option-wrap' ).not( $( e.currentTarget ).closest( '.bb-rl-option-wrap' ) ).removeClass( 'active' );

				// Toggle the current one.
				$( e.currentTarget ).closest( '.bb-rl-option-wrap' ).toggleClass( 'active' );
			},

			/**
			 * [closeMoreOption close more options dropdown]
			 *
			 * @param e
			 */
			closeMoreOption : function ( e ) {
				if ( ! $( e.target ).closest( '.bb-rl-option-wrap' ).length ) {
					$( '.bb-rl-option-wrap' ).removeClass( 'active' );
				}

				var container = $( '.header-aside div.menu-item-has-children *' );
				if ( ! container.is( e.target ) ) {
					$( '.header-aside div.menu-item-has-children' ).removeClass( 'selected' );
				}

				// Close profile dropdown when clicking outside.
				if ( ! $( e.target ).closest( '.user-wrap' ).length &&
					! $( e.target ).closest( '.bb-rl-profile-dropdown' ).length ) {
					$( '.user-wrap' ).removeClass( 'active' );
				}

				// Close search modal when clicking outside.
				var search_element = $(
					'#bb-rl-network-search-modal .bp-search-form-wrapper *, ' +
					'.bb-rl-header-search, ' +
					'.bb-rl-header-search *, ' +
					'.select2-container, ' +
					'.select2-container *, ' +
					'#bb-rl-network-search-modal .bp-search-form-wrapper, ' +
					'#bb-rl-network-search-modal .bp-search-form-wrapper *, ' +
					'.bb-rl-network-search-clear'
				);
				if ( ! search_element.is( e.target ) ) {
					$( '#bb-rl-network-search-modal' ).addClass( 'bp-hide' );
				}
			},

			/**
			 * [ToggleDarkMode Toggle dark mode]
			 *
			 * @param e
			 */
			ToggleDarkMode : function ( e ) {
				e.preventDefault();

				var $body = $( 'body' );
				$body.toggleClass( 'bb-rl-dark-mode' );

				if ( $body.hasClass( 'bb-rl-dark-mode' ) ) {
					$.cookie( 'bb-rl-dark-mode', 'true', { expires: 365, path: '/' } );
				} else {
					$.cookie( 'bb-rl-dark-mode', 'false', { expires: 365, path: '/' } );
				}
			},

			/**
			 * Show header notification dropdowns
			 *
			 * @param e
			 */
			showHeaderNotifications : function ( e ) {
				e.preventDefault();
				var current = $( e.currentTarget ).closest( 'div.menu-item-has-children' );
				current.siblings( '.selected' ).removeClass( 'selected' );
				current.toggleClass( 'selected' );
				if (
					'header-notifications-dropdown-elem' === current.attr( 'id' ) &&
					! current.hasClass( 'selected' ) &&
					( bp.Readylaunch.markAsReadNotifications.length > 0 || bp.Readylaunch.deletedNotifications.length > 0 )
				) {
					bp.Readylaunch.beforeUnload();
				}
			},

			toggleMobileMenu : function ( e ) {
				e.preventDefault();

				$( 'body' ).toggleClass( 'bb-mobile-menu-open' );
			},

			toggleLeftPanelWidget : function ( e ) {
				e.preventDefault();

				if ( $( window ).width() < 993 ) {
					$( e.currentTarget ).closest( '.bb-rl-left-panel-widget' ).toggleClass( 'is-open' );
				}
			},

			/**
			 * Mark notification as read.
			 *
			 * @param e
			 */
			markNotificationRead : function ( e ) {
				e.preventDefault();

				var $this                  = $( e.currentTarget ),
					notificationId         = $this.data( 'notification-id' ),
					notificationsIconCount = bp.Readylaunch.notificationIconSelector.parent().children( '.count' );
				if ( 'all' !== notificationId ) {
					$this.closest( '.read-item' ).fadeOut();
					notificationsIconCount.html( parseInt( notificationsIconCount.html() ) - 1 );
					bp.Readylaunch.markAsReadNotifications.push( notificationId );
				}

				if ( 'all' === notificationId ) {
					var mainContainerID = $this.closest( '.notification-wrap' );
					mainContainerID.find( '.header-ajax-container .notification-list' ).addClass( 'loading' );

					$.ajax(
						{
							type    : 'POST',
							url     : bbReadyLaunchFront.ajax_url,
							data    : {
								action                : 'bb_mark_notification_read',
								nonce                 : bbReadyLaunchFront.nonce,
								read_notification_ids : notificationId,
							},
							success : function ( response ) {
								if ( response.success && response.data ) {
									mainContainerID.find( '.header-ajax-container .notification-list' ).removeClass( 'loading' );
									if ( response.success && response.data && response.data.contents ) {
										mainContainerID.find( '.header-ajax-container .notification-list' ).html( response.data.contents );
									}
									if ( typeof response.data.total_notifications !== 'undefined' ) {
										if ( notificationsIconCount.length > 0 ) {
											if ( response.data.total_notifications > 0 ) {
												$( notificationsIconCount ).text( response.data.total_notifications );
											} else {
												$( notificationsIconCount ).remove();
											}
										}
									}
								}
							},
						}
					);
				}
			},

			/**
			 * Delete notification.
			 *
			 * @param e
			 */
			markNotificationDelete : function ( e ) {
				e.preventDefault();

				var $this                  = $( e.currentTarget ),
					notificationId         = $this.data( 'notification-id' ),
					notificationsIconCount = bp.Readylaunch.notificationIconSelector.parent().children( '.count' );
				if ( 'all' !== notificationId ) {
					$this.closest( '.read-item' ).fadeOut();
					notificationsIconCount.html( parseInt( notificationsIconCount.html() ) - 1 );
					bp.Readylaunch.deletedNotifications.push( notificationId );
				}
			},

			/**
			 * [beforeUnload description]
			 *
			 * @return {boolean} [description]
			 */
			beforeUnload : function () {
				if (
					0 === bp.Readylaunch.markAsReadNotifications.length &&
					0 === bp.Readylaunch.deletedNotifications.length
				) {
					return false; // Exit early without making AJAX call when no notifications to process.
				}

				$.ajax(
					{
						type    : 'POST',
						url     : bbReadyLaunchFront.ajax_url,
						data    : {
							action                   : 'bb_mark_notification_read',
							nonce                    : bbReadyLaunchFront.nonce,
							read_notification_ids    : bp.Readylaunch.markAsReadNotifications.join( ',' ),
							deleted_notification_ids : bp.Readylaunch.deletedNotifications.join( ',' ),
						},
						success : function ( response ) {
							if ( response.success ) {
								var notificationsIconCount = bp.Readylaunch.notificationIconSelector.parent().children( '.count' );
								if ( typeof response.data.total_notifications !== 'undefined' && response.data.total_notifications > 0 && notificationsIconCount.length > 0 ) {
									$( notificationsIconCount ).text( response.data.total_notifications );
								}
							}
						},
						error   : function () {

						},
					}
				);

				// Clear the array after processing.
				bp.Readylaunch.markAsReadNotifications = [];
				bp.Readylaunch.deletedNotifications    = [];
			},

			Utilities : {
				createDropzoneOptions : function ( options ) {
					return _.extend(
						{
							url              : bbRlAjaxUrl,
							timeout          : 3 * 60 * 60 * 1000,
							autoProcessQueue : true,
							addRemoveLinks   : true,
							uploadMultiple   : false
						},
						options
					);
				},

				setupDropzoneEventHandlers : function ( view, dropzone, config ) {
					var defaultConfig = {
						mediaType            : 'media',
						otherButtonSelectors : [],
					};

					var configExtended           = _.extend( defaultConfig, config ),
						modelKey                 = configExtended.modelKey,
						uploaderSelector         = configExtended.uploaderSelector,
						actionName               = configExtended.actionName,
						nonceName                = configExtended.nonceName,
						mediaType                = configExtended.mediaType,
						otherButtonSelectors     = configExtended.otherButtonSelectors,
						parentSelector           = configExtended.parentSelector,
						parentAttachmentSelector = configExtended.parentAttachmentSelector,
						ActiveComponent          = configExtended.ActiveComponent;

					// Common event handlers.
					dropzone.on(
						'addedfile',
						function ( file ) {
							if ( file[ mediaType + '_edit_data' ] ) {
								view[ modelKey ].push( file[ mediaType + '_edit_data' ] );
								view.model.set( modelKey, view[ modelKey ] );
							}

							if ( 'video' === mediaType ) {
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

					dropzone.on(
						'uploadprogress',
						function ( element ) {
							view.$el.closest( parentSelector ).addClass( 'media-uploading' );

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

					dropzone.on(
						'sending',
						function ( file, xhr, formData ) {
							formData.append( 'action', actionName );
							formData.append( '_wpnonce', bbRlNonce[ nonceName ] );

							var toolBox = view.$el.parents( parentSelector );
							otherButtonSelectors.forEach(
								function ( selector ) {
									var $button = toolBox.find( selector );
									if ( $button.length ) {
										$button.parents( '.bb-rl-post-elements-buttons-item' ).addClass( 'disable' );
									}
								}
							);
						}
					);

					dropzone.on(
						'success',
						function ( file, response ) {
							if ( response.data.id ) {
								if ( 'activity' === ActiveComponent ) {
									// Privacy and metadata handling.
									if ( ! bp.privacyEditable ) {
										response.data.group_id = bp.group_id;
										response.data.privacy  = bp.privacy;
									}
								}

								file.id                  = response.data.id;
								response.data.uuid       = file.upload.uuid;
								response.data.saved      = false;
								response.data.menu_order = $( file.previewElement ).closest( '.dropzone' ).find( file.previewElement ).index() - 1;

								if ( 'video' === mediaType ) {
									var thumbnailCheck = setInterval(
										function () {
											if ( $( file.previewElement ).closest( '.dz-preview' ).hasClass( 'dz-has-no-thumbnail' ) || $( file.previewElement ).closest( '.dz-preview' ).hasClass( 'dz-has-thumbnail' ) ) {
													response.data.js_preview = $( file.previewElement ).find( '.dz-image img' ).attr( 'src' );
													clearInterval( thumbnailCheck );
											}
										}
									);
								}

								view[ modelKey ].push( response.data );
								view.model.set( modelKey, view[ modelKey ] );
							}

							if ( 'document' === mediaType ) {
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

							if ( 'activity' === ActiveComponent ) {
								bp.draft_content_changed = true;
							}
						}
					);

					dropzone.on(
						'error',
						function ( file, response ) {
							if ( file.accepted ) {
								var errorMessage = response && response.data && response.data.feedback || bbRlMedia.connection_lost_error;
								$( file.previewElement ).find( '.dz-error-message span' ).text( errorMessage );
							} else {
								var bbRlErrorMessage = bbRlMedia.invalid_media_type + '. ' + ( response || '' );
								if ( config.errorMessage ) {
									bbRlErrorMessage = config.errorMessage;
								}
								Backbone.trigger(
									'onError',
									'<div>' + bbRlErrorMessage + '<div>'
								);
								dropzone.removeFile( file );
								view.$el.closest( parentSelector ).removeClass( 'media-uploading' );
							}
						}
					);

					dropzone.on(
						'removedfile',
						function ( file ) {
							var toolBox;

							var handleEmptyDropzone = function () {
								if ( dropzone.files.length === 0 ) {
									view.$el.closest( parentSelector ).removeClass( 'media-uploading' );

									// Re-enable buttons.
									toolBox = view.$el.parents( parentSelector );
									otherButtonSelectors.forEach(
										function ( selector ) {
											var $button = toolBox.find( selector );
											if ( $button.length ) {
												$button.parents( '.bb-rl-post-elements-buttons-item' ).removeClass( 'disable active no-click' );
											}
										}
									);

									view.model.unset( modelKey );
								}
							};

							if ( 'activity' === ActiveComponent ) {
								if ( bp.draft_activity.allow_delete_media ) {
									// Remove logic for media items.
									view[ modelKey ] = view[ modelKey ].filter(
										function ( mediaItem ) {
											return file.id !== mediaItem.id &&
													(
														! file[ mediaType + '_edit_data' ] || file[ mediaType + '_edit_data' ].id !== mediaItem.id
													);
										}
									);
									view.model.set( modelKey, view[ modelKey ] );

									// Set draft content changed flag.
									bp.draft_content_changed = true;

									handleEmptyDropzone();
								}
							} else {
								handleEmptyDropzone();
							}
						}
					);

					dropzone.on(
						'complete',
						function () {
							if ( 0 === dropzone.getUploadingFiles().length &&
								0 === dropzone.getQueuedFiles().length &&
								dropzone.files.length > 0 ) {
								view.$el.closest( parentSelector ).removeClass( 'media-uploading' );
							}
						}
					);

					// Open uploader.
					view.$el.find( uploaderSelector ).addClass( 'open' ).removeClass( 'closed' );
					$( parentAttachmentSelector ).removeClass( 'empty' ).closest( parentSelector ).addClass( 'focus-in--attm' );
				},

				injectFiles: function ( data ) {
					var commonData   = data.commonData,
						id           = data.id,
						fileType     = data.fileType, // 'media', 'document', or 'video'
						dropzoneObj  = data.dropzoneObj,
						draftData    = data.draftData || false,
						dropzoneData = data.dropzoneData || null;

					// Iterate through the files and inject them.
					commonData.forEach(
						function ( file, index ) {
							var editData;
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
							if ( ! _.isNull( dropzoneData ) ) {
								dropzoneData.push( editData );
							}

							var mockFile = {
								name    : file.name || file.full_name || file.title,
								size    : file.size || 0,
								accepted: true,
								kind    : 'media' === fileType ? 'image' : 'file',
								upload  : {
									filename: file.name || file.full_name || file.title,
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
								mockFile.dataThumb       = ! _.isUndefined( file.thumb ) ? file.thumb : '';
							}

							if ( dropzoneObj ) {
								dropzoneObj.files.push( mockFile );
								dropzoneObj.emit( 'addedfile', mockFile );

								// Handle thumbnails for media files.
								if ( 'media' === fileType ) {
									if ( 'undefined' !== typeof bbRlIsAs3cfActive && '1' === bbRlIsAs3cfActive ) {
										$( dropzoneObj.files[index].previewElement ).find( 'img' ).attr( 'src', file.thumb );
										dropzoneObj.emit( 'thumbnail', file.thumb );
									} else {
										bp.Readylaunch.Utilities.createThumbnailFromUrl( mockFile, dropzoneObj );
									}
								}

								dropzoneObj.emit( 'complete', mockFile );

								if ( 'media' === fileType && true === draftData ) {
									dropzoneObj.emit( 'dz-success' );
									dropzoneObj.emit( 'dz-complete' );
								}
							}
						}
					);
				},

				createThumbnailFromUrl : function ( mock_file, dropzoneObj, dropzone_container ) {
					var self             = this,
						dropzone_obj_key = dropzone_container && dropzone_container.data ? dropzone_container.data( 'key' ) : '',
						dropzoneObjData  = dropzoneObj || self.dropzone_obj;

					if ( dropzone_obj_key && dropzoneObjData[ dropzone_obj_key ] ) {
						dropzoneObjData = dropzoneObjData[ dropzone_obj_key ];
					}

					if ( ! dropzoneObjData || 'function' !== typeof dropzoneObjData.createThumbnailFromUrl ) {
						return;
					}
					try {
						dropzoneObjData.createThumbnailFromUrl(
							mock_file,
							dropzoneObjData.options.thumbnailWidth,
							dropzoneObjData.options.thumbnailHeight,
							dropzoneObjData.options.thumbnailMethod,
							true,
							function ( thumbnail ) {
								dropzoneObjData.emit( 'thumbnail', mock_file, thumbnail );
								dropzoneObjData.emit( 'complete', mock_file );
							}
						);
					} catch ( error ) {
						console.error( 'Error creating thumbnail:', error );
					}
				},
			},

			bbReloadWindow : function () {
				var fetchDataHandler = function ( event ) {
					if ( 'undefined' !== typeof event ) {
						bp.Readylaunch.beforeUnload();
					}
				};

				// This will work only for Chrome.
				window.onbeforeunload = fetchDataHandler;
				// This will work only for other browsers.
				window.unload = fetchDataHandler;
			},

			// Initializes navigation overflow handling on page load and resize.
			initBBNavOverflow: function () {
				var self = this;

				// Initialize overflow navigation for a specific selector
				function initSelector( selector, reduceWidth ) {
					$( selector ).each(
						function () {
							self.bbNavOverflow( this, reduceWidth );
						}
					);

					// Add resize and load event listeners
					window.addEventListener(
						'resize',
						function () {
							$( selector ).each(
								function () {
									self.bbNavOverflow( this, reduceWidth );
								}
							);
						}
					);

					window.addEventListener(
						'load',
						function () {
							$( selector ).each(
								function () {
									self.bbNavOverflow( this, reduceWidth );
								}
							);
						}
					);
				}

				initSelector( '#object-nav > ul', 100 );
				initSelector( '.bb-rl-header .bb-readylaunch-menu', 200 );

				$( document ).on(
					'click',
					'.bb-rl-nav-more',
					function ( e ) {
						e.preventDefault();
						$( this ).toggleClass( 'active open' ).next().toggleClass( 'active open' );
					}
				);

				$( document ).on(
					'click',
					'.bb-rl-hideshow .bb-rl-sub-menu a',
					function () {
						// e.preventDefault();
						$( 'body' ).trigger( 'click' );

						// add 'current' and 'selected' class
						var currentLI = $( this ).parent();
						currentLI.parent( '.bb-rl-sub-menu' ).find( 'li' ).removeClass( 'current selected' );
						currentLI.addClass( 'current selected' );
					}
				);

				$( document ).click(
					function ( e ) {
						var container = $( '.bb-rl-nav-more, .bb-rl-sub-menu' );
						if ( ! container.is( e.target ) && container.has( e.target ).length === 0 ) {
							$( '.bb-rl-nav-more' ).removeClass( 'active open' ).next().removeClass( 'active open' );
						}
					}
				);
			},

			// Handle navigation overflow with a dropdown.
			bbNavOverflow: function ( elem, reduceWidth ) {
				var $elem = $( elem );

				function run_alignMenu() {
					$elem.append( $( $elem.children( 'li.bb-rl-hideshow' ).children( 'ul' ) ).html() );
					$elem.children( 'li.bb-rl-hideshow' ).remove();
					alignMenu( elem );
				}

				function alignMenu( obj ) {
					var self     = $( obj ),
						w        = 0,
						i        = -1,
						menuhtml = '',
						mw       = self.width() - reduceWidth;

					$.each(
						self.children( 'li' ),
						function () {
							i++;
							w += $( this ).outerWidth( true );
							if ( mw < w ) {
								menuhtml += $( '<div>' ).append( $( this ).clone() ).html();
								$( this ).remove();
							}
						}
					);

					self.append(
						'<li class="bb-rl-hideshow menu-item-has-children1" data-no-dynamic-translation>' +
						'<a class="bb-rl-nav-more" href="#">' + bbReadyLaunchFront.more_nav + '<i class="bb-icons-rl-caret-down"></i></a>' +
						'<ul class="bb-rl-sub-menu" data-no-dynamic-translation>' + menuhtml + '</ul>' +
						'</li>'
					);

					if ( self.find( 'li.bb-rl-hideshow' ).find( 'li' ).length > 0 ) {
						self.find( 'li.bb-rl-hideshow' ).show();
					} else {
						self.find( 'li.bb-rl-hideshow' ).hide();
					}
				}

				run_alignMenu();
			},
		};

		// Launch BP ReadyLaunch.
		bp.Readylaunch.start();

	}
)( bp, jQuery );
