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
			$( document ).on( 'click', '.notification-link', this.openHeaderDropDown.bind( this ) );
		},

		/**
		 * [openHeaderDropDown description]
		 *
		 * @param  {[type]} e [description]
		 */
		openHeaderDropDown: function ( e ) {
			e.preventDefault();

			var $this       = $( e.target ).closest( '.notification-link' );
			var isMessage   = $this.parent().hasClass( 'bb-message-dropdown-notification' );
			var containerId = $this.parent().attr( 'id' );
			var action      = isMessage ? 'bb_fetch_header_messages' : 'bb_fetch_header_notifications';

			// Show a loading indicator.
			$( '#' + containerId ).find( '.notification-list' ).html( '<i class="bbrl-loader"></i>' );

			// Perform the AJAX request.
			$.ajax( {
				type   : 'GET',
				url    : bbReadyLaunchFront.ajax_url,
				data   : {
					action: action,
					nonce : bbReadyLaunchFront.nonce,
				},
				success: function ( response ) {
					if ( response.success && response.data ) {
						// Populate the dropdown content.
						$( '#' + containerId ).find( '.notification-list' ).html( response.data );
					}
				},
				error  : function () {
					$( '#' + containerId ).find( '.notification-list' ).html( '<p>Failed to load data. Please try again.</p>' );
				}
			} );
		},
	};

	// Launch BP Zoom.
	bp.Readylaunch.start();

} )( bp, jQuery );