/**
 * BuddyBoss Notice System.
 *
 * @since BuddyBoss 1.0.0
 */

( function ( $ ) {

	'use strict';

	var BuddyBossNoticeSystem = {
		init: function () {
			this.showNotifications();
			this.switchNotices();
			this.dismissNotice();
		},

		showNotifications: function () {
			$( document ).on( 'click', '#bb-notifications-button', function ( e ) {
				e.preventDefault();

				var $wrapper = $( this ).closest( '.bb-notifications-wrapepr' );

				$wrapper.toggleClass( 'active' );

				BuddyBossNoticeSystem.checkNoticesCount();
			} );

			$( document ).on( 'click', '.close-panel-header', function ( e ) {
				e.preventDefault();

				var $wrapper = $( this ).closest( '.bb-notifications-wrapepr' );

				$wrapper.removeClass( 'active' );
			} );
		},

		switchNotices: function () {
			$( document ).on( 'click', '.panel-nav-list .switch-notices', function ( e ) {
				e.preventDefault();

				var $this = $( this );
				var status = $this.data( 'status' );
				var $notificationsPanel = $this.closest( '.bb-notifications-panel' );
				var $notificationsList = $notificationsPanel.find( '.bb-panel-body' );

				$this.closest( 'li' ).siblings().find( '.switch-notices' ).removeClass('active');
				$this.addClass( 'active' );
				$notificationsList.removeClass( 'all dismissed active' );
				$notificationsList.addClass( status );

				BuddyBossNoticeSystem.checkNoticesStatus();
			} );

			$( document ).on( 'click', '.panel-nav-dismiss-all', function ( e ) {
				e.preventDefault();

				var $this = $( this );
				var $notificationsPanel = $this.closest( '.bb-notifications-panel' );

				$notificationsPanel.find( '.bb-notice-block' ).removeClass( 'dismissed active' );
				$notificationsPanel.find( '.bb-notice-block' ).addClass( 'dismissed' );

				BuddyBossNoticeSystem.checkNoticesStatus();
				BuddyBossNoticeSystem.checkNoticesCount();
			} );

			BuddyBossNoticeSystem.checkNoticesStatus();
		},

		dismissNotice: function () {
			$( document ).on( 'click', '.bb-dismiss-notice', function ( e ) {
				e.preventDefault();

				var $this = $( this );
				var $noticeContainer = $this.closest( '.bb-notice-block' );

				$noticeContainer.removeClass( 'dismissed active' );
				$noticeContainer.addClass( 'dismissed' );

				BuddyBossNoticeSystem.checkNoticesStatus();
				BuddyBossNoticeSystem.checkNoticesCount();
			} );
		},

		checkNoticesStatus: function () {
			var $noticesContainer = $( '.bb-notices-blocks-container' );
			var $noticeBlocks = $noticesContainer.find( '.bb-notice-block' );

			if ( $noticesContainer.find( '.bb-notice-block' ).length === 0 ) {
				$noticesContainer.closest( '.bb-panel-body' ).addClass( 'empty' );
			} else {
				$noticesContainer.closest( '.bb-panel-body' ).removeClass( 'empty' );
			}

			var allDismissed = true;
			$noticeBlocks.each( function () {
				if ( !$( this ).hasClass( 'dismissed' ) ) {
					allDismissed = false;
					return false;
				}
			} );

			if ( allDismissed ) {
				$noticesContainer.closest( '.bb-panel-body' ).addClass( 'all-dismissed' );
			} else {
				$noticesContainer.closest( '.bb-panel-body' ).removeClass( 'all-dismissed' );
			}
		},

		checkNoticesCount: function () {
			var $noticesContainer = $( '.bb-notices-blocks-container' );
			var $noticeBlocks = $noticesContainer.find( '.bb-notice-block' );
			var $noticesCount = $( '#show-active .count-active' );

			var activeCount = 0;

			if ( $noticeBlocks.length === 0 ) {
				activeCount = 0;
			} else {
				$noticeBlocks.each( function () {
					if ( $( this ).hasClass( 'active' ) ) {
						activeCount++;
					}
				} );
			}

			$noticesCount.text( '(' + activeCount + ')' );
			
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