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
		},

		/**
		 * [addListeners description]
		 */
		addListeners: function () {
			$( '.bb-nouveau-list' ).scroll( 'scroll', this.bbScrollHeaderDropDown.bind( this ) );
			$( document ).on( 'click', '.notification-link, .notification-header-tab-action, .load-more a', this.bbHandleLoadMore.bind( this ) );
			$( document ).on( 'heartbeat-send', this.bbHeartbeatSend.bind( this ) );
			$( document ).on( 'heartbeat-tick', this.bbHeartbeatTick.bind( this ) );
			$( document ).on( 'click', '.bbrl-option-wrap__action', this.openMoreOption.bind( this ) );
			$( document ).on( 'click', this.closeMoreOption.bind( this ) );
			$( document ).on( 'click', '.header-aside div.menu-item-has-children > a', this.showHeaderNotifications.bind( this ) );
			$( document ).on( 'click', '.bbrl-left-panel-mobile, .bbrl-close-panel-mobile', this.toggleMobileMenu.bind( this ) );
			$( document ).on( 'click', '.action-unread', this.markNotificationRead.bind( this ) );
			$( document ).on( 'click', '.action-delete', this.markNotificationDelete.bind( this ) );
			$( window ).on( 'beforeunload', this.beforeUnload.bind( this ) );
			$( '#buddypress [data-bp-list], #buddypress #item-header, #buddypress.bp-shortcode-wrap .dir-list, #buddypress .bp-messages-content' ).on( 'click', '[data-bp-btn-action]', this, this.buttonAction );
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
				mainContainerID.find( '.load-more' ).before( '<i class="bbrl-loader"></i>' );
			} else {
				mainContainerID.find( '.notification-list' ).html( '<i class="bbrl-loader"></i>' );
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
						if ( container.find( '.bbrl-loader' ).has( '.bbrl-loader' ) ) {
							container.find( '.bbrl-loader' ).remove( '.bbrl-loader' );
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

			$(  e.currentTarget ).closest( '.bbrl-option-wrap' ).toggleClass( 'active' );
		},

		/**
		 * [closeMoreOption close more options dropdown]
		 * @param event
		 */
		closeMoreOption: function ( e ) {
			if ( ! $( e.target ).closest( '.bbrl-option-wrap' ).length ) {
				$( '.bbrl-option-wrap' ).removeClass( 'active' );
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

		/**
		 * [buttonAction description]
		 *
		 * @param  {[type]} event [description]
		 * @return {[type]}       [description]
		 */
		buttonAction: function ( event ) {
			var self       = event.data, target = $( event.currentTarget ), action = target.data( 'bp-btn-action' ),
				nonceUrl   = target.data( 'bp-nonce' ),
				item       = target.closest( '[data-bp-item-id]' ), item_id = item.data( 'bp-item-id' ),
				item_inner = target.closest( '.list-wrap' ),
				object     = item.data( 'bp-item-component' ), nonce = '', component = item.data( 'bp-used-to-component' );

			// Simply let the event fire if we don't have needed values.
			if ( ! action || ! item_id || ! object ) {
				return event;
			}

			// Stop event propagation.
			event.preventDefault();

			// show popup if it is is_friend action.
			var remove_connection_popup = {};
			if ( $( target ).closest( '#item-header' ).length ) {
				remove_connection_popup = $( '#item-header .bb-remove-connection' );
			} else if ( $( target ).closest( '.members[data-bp-list="members"]' ).length ) {
				remove_connection_popup = $( '.members[data-bp-list="members"] .bb-remove-connection' );
			} else if ( $( target ).closest( '.group_members[data-bp-list="group_members"]' ).length ) {
				remove_connection_popup = $( '.group_members[data-bp-list="group_members"] .bb-remove-connection' );
			}
			var member__name            = $( target ).data( 'bb-user-name' );
			var member_link             = $( target ).data( 'bb-user-link' );
			if ( 'is_friend' === action && 'opened' !== $( target ).attr( 'data-popup-shown' ) ) {
				if ( remove_connection_popup.length ) {
					remove_connection_popup.find( '.bb-remove-connection-content .bb-user-name' ).html( '<a href="' + member_link + '">' + member__name + '</a>' );
					$( 'body' ).find( '[data-current-anchor="true"]' ).removeClass( 'bp-toggle-action-button bp-toggle-action-button-hover' ).addClass( 'bp-toggle-action-button-clicked' ); // Add clicked class manually to run function.
					remove_connection_popup.show();
					$( target ).attr( 'data-current-anchor', 'true' );
					$( target ).attr( 'data-popup-shown', 'opened' );
					return false;
				}
			} else {
				$( 'body' ).find( '[data-popup-shown="opened"]' ).attr( 'data-popup-shown' , 'closed' );
				$( 'body' ).find( '[data-current-anchor="true"]' ).attr( 'data-current-anchor' , 'false' );
				if ( remove_connection_popup.length ) {
					remove_connection_popup.find('.bb-remove-connection-content .bb-user-name').html('');
					remove_connection_popup.hide();
				}
			}

			// Find the required wpnonce string.
			// if  button element set we'll have our nonce set on a data attr.
			// Check the value & if exists split the string to obtain the nonce string.
			// if no value, i.e false, null then the href attr is used.
			if ( nonceUrl ) {
				nonce = self.getLinkParams( nonceUrl, '_wpnonce' );
			} else {
				if ( 'undefined' === typeof target.prop( 'href' ) ) {
					nonce = self.getLinkParams( target.attr( 'href' ), '_wpnonce' );
				} else {
					nonce = self.getLinkParams( target.prop( 'href' ), '_wpnonce' );
				}
			}

			// Unfortunately unlike groups.
			// Connections actions does not match the wpnonce.
			var friends_actions_map = {
				is_friend: 'remove_friend',
				not_friends: 'add_friend',
				pending: 'withdraw_friendship',
				accept_friendship: 'accept_friendship',
				reject_friendship: 'reject_friendship'
			};

			if ( 'members' === object && undefined !== friends_actions_map[ action ] ) {
				action = friends_actions_map[ action ];
				object = 'friends';
			}

			var follow_actions_map = {
				not_following: 'follow',
				following: 'unfollow'
			};

			if ( 'members' === object && undefined !== follow_actions_map[ action ] ) {
				action = follow_actions_map[ action ];
				object = 'follow';
			}

			// Add a pending class to prevent queries while we're processing the action.
			target.addClass( 'pending loading' );

			var current_page = '';
			if ( ( $( document.body ).hasClass( 'directory' ) && $( document.body ).hasClass( 'members' ) ) || $( document.body ).hasClass( 'group-members' ) ) {
				current_page = 'directory';
			} else if ( $( document.body ).hasClass( 'bp-user' ) ) {
				current_page = 'single';
			}

			var button_clicked  = 'primary';
			var button_activity = ( 'single' === current_page ) ? target.closest( '.header-dropdown' ) : target.closest( '.footer-button-wrap' );

			if ( typeof button_activity.length !== 'undefined' && button_activity.length > 0 ) {
				button_clicked = 'secondary';
			}

			component = 'undefined' === typeof component ? object : component;

			self.ajax(
				{
					action        : object + '_' + action,
					item_id       : item_id,
					current_page  : current_page,
					button_clicked: button_clicked,
					component     : component,
					_wpnonce      : nonce
				},
				object,
				true
			).done(
				function ( response ) {
					if ( false === response.success ) {
						item_inner.prepend( response.data.feedback );
						target.removeClass( 'pending loading' );

					} else {
						if ( 'follow' === object && item.find( '.followers-wrap' ).length > 0 && typeof response.data.count !== 'undefined' && response.data.count !== '' ) {
							item.find( '.followers-wrap' ).replaceWith( response.data.count );
						}

						target.parent().replaceWith( response.data.contents );
					}
				}
			).fail(
				function () {
					target.removeClass( 'pending loading' );
				}
			);
		},

		/**
		 * Common ajax function.
		 *
		 * @param  {[type]} post_data [description]
		 * @param  {[type]} object    [description]
		 * @param  {[type]} button    [description]
		 * @return {[type]}           [description]
		 */
		ajax: function ( post_data, object, button ) {

			if ( this.ajax_request && typeof button === 'undefined' && post_data.status !== 'scheduled') {
				this.ajax_request.abort();
			}

			// Extend posted data with stored data and object nonce.
			var postData = $.extend( {}, bp.Readylaunch.getStorage( 'bp-' + object ), { nonce: bbReadyLaunchFront.nonce }, post_data );

			this.ajax_request = $.post( bbReadyLaunchFront.ajax_url, postData, 'json' );

			return this.ajax_request;
		},

		/**
		 * [getLinkParams description]
		 *
		 * @param  {[type]} url   [description]
		 * @param  {[type]} param [description]
		 * @return {[type]}       [description]
		 */
		getLinkParams: function ( url, param ) {

			var qs;
			if ( url ) {
				qs = ( -1 !== url.indexOf( '?' ) ) ? '?' + url.split( '?' )[ 1 ] : '';
			} else {
				qs = document.location.search;
			}

			if ( ! qs ) {
				return null;
			}

			var params = qs.replace( /(^\?)/, '' ).split( '&' ).map(
				function ( n ) {
					return n = n.split( '=' ), this[ n[ 0 ] ] = n[ 1 ], this;
				}.bind( {} )
			)[ 0 ];

			if ( param ) {
				return params[ param ];
			}

			return params;
		},

		/**
		 * [getStorage description]
		 *
		 * @param  {[type]} type     [description]
		 * @param  {[type]} property [description]
		 * @return {[type]}          [description]
		 */
		getStorage: function ( type, property ) {

			var store = sessionStorage.getItem( type );

			if ( store ) {
				store = JSON.parse( store );
			} else {
				store = {};
			}

			if ( undefined !== property ) {
				return store[ property ] || false;
			}

			return store;
		},

		/**
		 * [setStorage description]
		 *
		 * @param {[type]} type     [description]
		 * @param {[type]} property [description]
		 * @param {[type]} value    [description]
		 */
		setStorage: function ( type, property, value ) {

			var store = this.getStorage( type );

			if ( undefined === value && undefined !== store[ property ] ) {
				delete store[ property ];
			} else {
				// Set property.
				store[ property ] = value;
			}

			sessionStorage.setItem( type, JSON.stringify( store ) );

			return sessionStorage.getItem( type ) !== null;
		},
	};

	// Launch BP Zoom.
	bp.Readylaunch.start();

} )( bp, jQuery );