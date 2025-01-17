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
			$( '.bb-nouveau-list' ).scroll( 'scroll', this.scrollHeaderDropDown.bind( this ) );
			$( document ).on( 'click', '.notification-link, .load-more a', this.handleLoadMore.bind( this ) );
		},

		/**
		 * [scrollHeaderDropDown description]
		 *
		 * @param e
		 */
		scrollHeaderDropDown: function ( e ) {
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
		handleLoadMore: function ( e ) {
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

			// Get the container ID.
			var containerId = $container.attr( 'id' );

			var isMessage = $container.hasClass( 'bb-message-dropdown-notification' );
			var page      = $target.data( 'page' ) ? $target.data( 'page' ) + 1 : 1;

			this.performAjaxRequest( e, {
				action     : isMessage ? 'bb_fetch_header_messages' : 'bb_fetch_header_notifications',
				page       : page,
				isMessage  : isMessage,
				containerId: containerId,
			} );

			// Update page data if applicable
			//if ( $this.data( 'page' ) ) {
			//	$this.data( 'page', page );
			//}
		},

		/**
		 * Common AJAX handler for loading notifications
		 *
		 * @param {Object} e Event object
		 * @param {Object} options Options for AJAX request
		 */
		performAjaxRequest: function ( e, options ) {
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
					mainContainerID.find( '.notification-list' ).html( '<p>Failed to load data. Please try again.</p>' );
				},
			} );
		},
	};

	// Launch BP Zoom.
	bp.Readylaunch.start();

} )( bp, jQuery );