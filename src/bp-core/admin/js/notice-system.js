/**
 * BuddyBoss Notice System.
 *
 * @since BuddyBoss 1.0.0
 */

( function ( $ ) {

	'use strict';

	var BuddyBossNoticeSystem = {
		init: function () {
			this.showNotification();
		},

		showNotification: function () {
			$( document ).on( 'click', '#bb-notifications-button', function ( e ) {
                e.preventDefault();

                var $wrapper = $( this ).closest( '.bb-notifications-wrapepr' );
                
				$wrapper.toggleClass( 'active' );
            } );

			$( document ).on( 'click', '.close-panel-header', function ( e ) {
                e.preventDefault();

                var $wrapper = $( this ).closest( '.bb-notifications-wrapepr' );
                
				$wrapper.removeClass( 'active' );
            } );
		}
	};

	window.BuddyBossNoticeSystem = BuddyBossNoticeSystem;

	$( document ).on(
		'ready',
		function () {
			BuddyBossNoticeSystem.init();
		}
	);

} )( jQuery );