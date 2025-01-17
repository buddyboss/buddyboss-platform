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

			// Listen to events ("Add hooks!")
			this.addListeners();
		},

		/**
		 * [addListeners description]
		 */
		addListeners: function () {
			$( '.bb-nouveau-list' ).scroll( 'scroll', this.bbScrollHeaderDropDown.bind( this ) );
			$( document ).on( 'click', '.notification-link, .load-more a', this.bbHandleLoadMore.bind( this ) );
			$( document ).on( 'heartbeat-send', this.bbHeartbeatSend.bind( this ) );
			$( document ).on( 'heartbeat-tick', this.bbHeartbeatTick.bind( this ) );
			$( document ).on( 'click', '.bbrl-option-wrap__action', this.openMoreOption.bind( this ) );
			$( document ).on( 'click', this.closeMoreOption.bind( this ) );
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
			var $target = $( e.target ).closest( '.notification-link, .load-more a' );
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
				type   : 'GET',
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
		 * [bbHeartbeatSend description]
		 * @param e
		 * @param data
		 */
		bbHeartbeatSend: function ( e, data ) {
			data.bb_fetch_header_notifications = true;
		},

		/**
		 * [bbHeartbeatTick description]
		 * @param e
		 * @param data
		 */
		bbHeartbeatTick: function ( e, data ) {
			this.bpInjectNotifications( e, data );
		},

		/**
		 * [bpInjectNotifications description]
		 * @param event
		 * @param data
		 */
		bpInjectNotifications: function ( event, data ) {
			if ( typeof data.unread_notifications !== 'undefined' && data.unread_notifications !== '' ) {
				$( '#header-notifications-dropdown-elem .notification-dropdown .notification-list' ).empty().html( data.unread_notifications );
			}

			var notifs      = $( '.bb-icon-bell' );
			var notif_icons = $( notifs ).parent().children( '.count' );

			if ( typeof data.total_notifications !== 'undefined' && data.total_notifications > 0 ) {
				$( '.notification-header .mark-read-all' ).show();

				if ( notif_icons.length > 0 ) {
					$( notif_icons ).text( data.total_notifications );
				} else {
					$( notifs ).parent( ':not(.group-subscription)' ).append( '<span class="count"> ' + data.total_notifications + ' </span>' );
				}
			} else {
				$( notif_icons ).remove();
				$( '.notification-header .mark-read-all' ).fadeOut();
			}
		},

		openMoreOption: function ( e ) {
			e.preventDefault();

			$(  e.currentTarget ).closest( '.bbrl-option-wrap' ).toggleClass( 'active' );
		},

		closeMoreOption: function ( e ) {
			if ( ! $( e.target ).closest( '.bbrl-option-wrap' ).length ) {
				$( '.bbrl-option-wrap' ).removeClass( 'active' );
			}
		}
	};

	// Launch BP Zoom.
	bp.Readylaunch.start();

} )( bp, jQuery );