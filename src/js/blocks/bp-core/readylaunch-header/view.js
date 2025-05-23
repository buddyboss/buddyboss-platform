/* global bp, bbReadyLaunchFront, BP_Nouveau */
/* @version 1.0.0 */

window.bp = window.bp || {};

(
	function ( exports, $ ) {
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
				this.gridListFilter();
				this.styledSelect();
				this.initBBNavOverflow();
			},

			/**
			 * [addListeners description]
			 */
			addListeners : function () {
				var $document = $( document );
				$( '.bb-nouveau-list' ).on( 'scroll', this.bbScrollHeaderDropDown.bind( this ) );
				$document.on( 'click', '.notification-link, .notification-header-tab-action, .bb-rl-load-more a', this.bbHandleLoadMore.bind( this ) );
				$document.on( 'heartbeat-send', this.bbHeartbeatSend.bind( this ) );
				$document.on( 'heartbeat-tick', this.bbHeartbeatTick.bind( this ) );
				$document.on( 'click', '.bb-rl-option-wrap__action', this.openMoreOption.bind( this ) );
				$document.on( 'click', (e) => this.closeMoreOption(e) );
				$document.on( 'click', '#bb-rl-profile-theme-light, #bb-rl-profile-theme-dark', this.ToggleDarkMode.bind( this ) );
				$document.on( 'click', '.bb-rl-header-aside div.menu-item-has-children > a', this.showHeaderNotifications.bind( this ) );
				$document.on( 'click', '.bb-rl-left-panel-mobile, .bb-rl-close-panel-mobile', this.toggleMobileMenu.bind( this ) );
				$document.on( 'click', '.bb-rl-left-panel-widget .bb-rl-list > h2', this.toggleLeftPanelWidget.bind( this ) );
				$document.on( 'click', '.action-unread', this.markNotificationRead.bind( this ) );
				$document.on( 'click', '.action-delete', this.markNotificationDelete.bind( this ) );
				$document.on( 'click', '.bb-rl-header-container .bb-rl-header-aside .bb-rl-user-link', this.profileNav.bind( this ) );
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
					mainContainerID = $( '.bb-rl-header-block' ).find( '#' + settings.containerId ); // Show a loading indicator.
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

				var container = $( '.bb-rl-header-aside div.menu-item-has-children *' );
				if ( ! container.is( e.target ) ) {
					$( '.bb-rl-header-aside div.menu-item-has-children' ).removeClass( 'selected' );
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
								action : 'bb_mark_notification_read',
								nonce  : bbReadyLaunchFront.nonce,
								id     : notificationId,
							},
							success : function ( response ) {
								if ( response.success && response.data ) {
									mainContainerID.find( '.header-ajax-container .notification-list' ).removeClass( 'loading' );
									if ( response.success && response.data && response.data.contents ) {
										mainContainerID.find( '.header-ajax-container .notification-list' ).html( response.data.contents );
									}
									if ( typeof response.data.total_notifications !== 'undefined' && response.data.total_notifications > 0 && notificationsIconCount.length > 0 ) {
										$( notificationsIconCount ).text( response.data.total_notifications );
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
				initSelector( '#menu-readylaunch', 200 );

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
