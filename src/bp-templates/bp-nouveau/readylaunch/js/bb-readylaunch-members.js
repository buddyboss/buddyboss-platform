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
	bp.Readylaunch.Members = {
		/**
		 * [start description]
		 *
		 * @return {[type]} [description]
		 */
		start: function () {
			this.inviteMember();
		},

		/**
		 * [addListeners description]
		 */
		addListeners: function () {
		},

		inviteMember: function () {
			$( document ).on( 'click', '#bb-rl-invite-button',function ( e ) {
				e.preventDefault();

				var $modal= $( '#bb-rl-invite-modal' );
				
				if ( $modal.length ) {
					$modal.show();
				}
			} );

			$( document ).on( 'click', '.bb-rl-modal-close-button', function ( e ) {
				e.preventDefault();
				
				var $modal = $( this ).closest( '#bb-rl-invite-modal' );
				
				if ( $modal.length ) {
					$modal.hide();
				}
			} );
		},
	};

	// Launch members.
	bp.Readylaunch.Members.start();

} )( bp, jQuery );