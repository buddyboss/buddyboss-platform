/* jshint browser: true */
/* global bp, bbReadyLaunchFront */
/* @version 1.0.0 */
window.bp = window.bp || {};

( function ( exports, $ ) {

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
		start: function () {
			this.deletedNotifications     = [];
			this.markAsReadNotifications  = [];
			this.notificationIconSelector = $( '.bb-icons-rl-bell-simple' );
			// Listen to events ("Add hooks!")
			this.addListeners();
			this.mobileSubMenu();

			this.bbReloadWindow();
		},

		/**
		 * [addListeners description]
		 */
		addListeners: function () {
			$( '.bb-nouveau-list' ).scroll( 'scroll', this.bbScrollHeaderDropDown.bind( this ) );
			$( document ).on( 'click', '.notification-link, .notification-header-tab-action, .load-more a', this.bbHandleLoadMore.bind( this ) );
			$( document ).on( 'heartbeat-send', this.bbHeartbeatSend.bind( this ) );
			$( document ).on( 'heartbeat-tick', this.bbHeartbeatTick.bind( this ) );
			$( document ).on( 'click', '.bb-rl-option-wrap__action', this.openMoreOption.bind( this ) );
			$( document ).on( 'click', this.closeMoreOption.bind( this ) );
			$( document ).on( 'click', '.header-aside div.menu-item-has-children > a', this.showHeaderNotifications.bind( this ) );
			$( document ).on( 'click', '.bb-rl-left-panel-mobile, .bb-rl-close-panel-mobile', this.toggleMobileMenu.bind( this ) );
			$( document ).on( 'click', '.action-unread', this.markNotificationRead.bind( this ) );
			$( document ).on( 'click', '.action-delete', this.markNotificationDelete.bind( this ) );
		},
		/**
		 * [scrollHeaderDropDown description]
		 *
		 * @param e
		 */
		bbScrollHeaderDropDown: function ( e ) {
			var el = e.target;
			if ( 'notification-list' === el.id ) {
				if ( el.scrollTop + el.offsetHeight >= el.scrollHeight && ! el.classList.contains( 'loading' ) ) {
					var load_more = $( el ).find( '.load-more' );
					if ( load_more.length ) {
						el.classList.add( 'loading' );
						load_more.find( 'a' ).trigger( 'click' );
					}
				}
			}
		},

		// Add Mobile menu toggle button
		mobileSubMenu: function () {
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

		/**
		 * Handles "Load More" click or dropdown open
		 *
		 * @param {Object} e Event object
		 */
		bbHandleLoadMore: function ( e ) {
			e.preventDefault();

			// Identify the clicked element.
			var $target = $( e.target ).closest( '.notification-link, .notification-header-tab-action, .load-more a' );
			if ( ! $target.length ) {
				return;
			}

			// Locate the top-level container.
			var $container = $target.closest( '.notification-wrap' );
			if ( ! $container.length ) {
				return;
			}

			// If the Dropdown is going to be closed, then return.
			if ( $container.hasClass( 'selected' ) ) {
				return;
			}

			// Check if there's already a request in progress.
			if ( $container.data( 'loading' ) ) {
				return; // Exit if an AJAX request is already in progress.
			}

			// Set the 'loading' flag to true.
			$container.data( 'loading', true );

			// Get the container ID.
			var containerId = $container.attr( 'id' );

			var isMessage = $container.hasClass( 'bb-message-dropdown-notification' );
			var page      = $target.data( 'page' ) ? $target.data( 'page' ) + 1 : 1;

			this.bbPerformAjaxRequest( e, {
				action     : isMessage ? 'bb_fetch_header_messages' : 'bb_fetch_header_notifications',
				page       : page,
				isMessage  : isMessage,
				containerId: containerId,
			} );
		},

		/**
		 * Common AJAX handler for loading notifications
		 *
		 * @param {Object} e Event object
		 * @param {Object} options Options for AJAX request
		 */
		bbPerformAjaxRequest: function ( e, options ) {
			e.preventDefault();

			var defaults = {
				action     : '',
				page       : 1,
				isMessage  : false,
				containerId: '',
			};
			var settings = $.extend( defaults, options );

			// Show a loading indicator.
			var mainContainerID = $( '#' + settings.containerId );
			if ( settings.page > 1 ) {
				mainContainerID.find( '.load-more' ).before( '<i class="bb-rl-loader"></i>' );
			} else {
				mainContainerID.find( '.notification-list' ).html( '<i class="bb-rl-loader"></i>' );
			}

			var data = {
				action: settings.action,
				nonce : bbReadyLaunchFront.nonce,
				page  : settings.page,
			};

			$.ajax( {
				type   : 'POST',
				url    : bbReadyLaunchFront.ajax_url,
				data   : data,
				success: function ( response ) {
					// Reset the 'loading' flag once the request completes.
					mainContainerID.data( 'loading', false );

					if ( response.success && response.data ) {
						var container = mainContainerID.find( '.notification-list' );
						if ( container.find( '.bb-rl-loader' ).has( '.bb-rl-loader' ) ) {
							container.find( '.bb-rl-loader' ).remove( '.bb-rl-loader' );
						}
						if ( settings.page > 1 ) {
							container.find( '.load-more' ).replaceWith( response.data );
						} else {
							container.html( response.data );
						}
					}
				},
				error  : function () {
					// Reset the 'loading' flag in case of error.
					mainContainerID.data( 'loading', false );
					mainContainerID.find( '.notification-list' ).html( '<p>Failed to load data. Please try again.</p>' );
				},
			} );
		},

		/**
		 * [bbHeartbeatSend description] - Handles the request to the server.
		 * @param e
		 * @param data
		 */
		bbHeartbeatSend: function ( e, data ) {
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
		 * @param e
		 * @param data
		 */
		bbHeartbeatTick: function ( e, data ) {
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
		 * @param event
		 * @param data
		 */
		bbpInjectNotifications: function ( event, data ) {
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
		 * @param event
		 */
		openMoreOption: function ( e ) {
			e.preventDefault();

			$(  e.currentTarget ).closest( '.bb-rl-option-wrap' ).toggleClass( 'active' );
		},

		/**
		 * [closeMoreOption close more options dropdown]
		 * @param event
		 */
		closeMoreOption: function ( e ) {
			if ( ! $( e.target ).closest( '.bb-rl-option-wrap' ).length ) {
				$( '.bb-rl-option-wrap' ).removeClass( 'active' );
			}

			var container = $( '.header-aside div.menu-item-has-children *' );
			if ( ! container.is( e.target ) ) {
				$( '.header-aside div.menu-item-has-children' ).removeClass( 'selected' );
			}
		},

		/**
		 * Show header notification dropdowns
		 * @param event
		 */
		showHeaderNotifications: function ( e ) {
			e.preventDefault();
			var current = $( e.currentTarget ).closest( 'div.menu-item-has-children' );
			current.siblings( '.selected' ).removeClass( 'selected' );
			current.toggleClass( 'selected' );
			if (
				'header-notifications-dropdown-elem' === current.attr( 'id' ) &&
				! current.hasClass( 'selected' )
			) {
				bp.Readylaunch.beforeUnload();
			}
		},

		toggleMobileMenu: function( e ) {
			e.preventDefault();

			$( 'body' ).toggleClass( 'bb-mobile-menu-open' );
		},

		/**
		 * Mark notification as read.
		 * @param e
		 */
		markNotificationRead: function ( e ) {
			e.preventDefault();

			var $this                  = $( e.currentTarget );
			var notificationId         = $this.data( 'notification-id' );
			var notificationsIconCount = bp.Readylaunch.notificationIconSelector.parent().children( '.count' );
			if ( 'all' !== notificationId ) {
				$this.closest( '.read-item' ).fadeOut();
				notificationsIconCount.html( parseInt( notificationsIconCount.html() ) - 1 );
				bp.Readylaunch.markAsReadNotifications.push( notificationId );
			}

			if ( 'all' === notificationId ) {
				var mainContainerID = $this.closest( '.notification-wrap' );
				mainContainerID.find( '.header-ajax-container .notification-list' ).addClass( 'loading' );

				$.ajax( {
					type   : 'POST',
					url    : bbReadyLaunchFront.ajax_url,
					data   : {
						action: 'bb_mark_notification_read',
						nonce : bbReadyLaunchFront.nonce,
						id    : notificationId,
					},
					success: function ( response ) {
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
				} );
			}
		},

		/**
		 * Delete notification.
		 * @param e
		 */
		markNotificationDelete: function ( e ) {
			e.preventDefault();

			var $this                  = $( e.currentTarget );
			var notificationId         = $this.data( 'notification-id' );
			var notificationsIconCount = bp.Readylaunch.notificationIconSelector.parent().children( '.count' );
			if ( 'all' !== notificationId ) {
				$this.closest( '.read-item' ).fadeOut();
				notificationsIconCount.html( parseInt( notificationsIconCount.html() ) - 1 );
				bp.Readylaunch.deletedNotifications.push( notificationId );
			}
		},

		/**
		 * [beforeUnload description]
		 * @return {boolean} [description]
		 */
		beforeUnload: function () {
			if (
				0 === bp.Readylaunch.markAsReadNotifications.length &&
				0 === bp.Readylaunch.deletedNotifications.length
			) {
				return false;
			}

			$.ajax( {
				type   : 'POST',
				url    : bbReadyLaunchFront.ajax_url,
				data   : {
					action                  : 'bb_mark_notification_read',
					nonce                   : bbReadyLaunchFront.nonce,
					read_notification_ids   : bp.Readylaunch.markAsReadNotifications.join( ',' ),
					deleted_notification_ids: bp.Readylaunch.deletedNotifications.join( ',' ),
				},
				success: function ( response ) {
					if ( response.success ) {
						var notificationsIconCount = bp.Readylaunch.notificationIconSelector.parent().children( '.count' );
						if ( typeof response.data.total_notifications !== 'undefined' && response.data.total_notifications > 0 && notificationsIconCount.length > 0 ) {
							$( notificationsIconCount ).text( response.data.total_notifications );
						}
					}
				},
				error  : function () {

				},
			} );

			// Clear the array after processing.
			bp.Readylaunch.markAsReadNotifications = [];
			bp.Readylaunch.deletedNotifications    = [];
		},

		bbReloadWindow: function () {
			var fetchDataHandler = function( event ) {
				if ( 'undefined' !== typeof event ) {
					bp.Readylaunch.beforeUnload();
				}
			};

			// This will work only for Chrome.
			window.onbeforeunload = fetchDataHandler;
			// This will work only for other browsers.
			window.unload         = fetchDataHandler;
		}
	};

	// Launch BP Zoom.
	bp.Readylaunch.start();

} )( bp, jQuery );