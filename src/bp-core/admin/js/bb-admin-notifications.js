/**
 * BuddyBoss Notice System.
 *
 * @since BuddyBoss [BBVERSION]
 */
/* global BBInPluginAdminNotifications */

(
	function ( $ ) {

		'use strict';

		var BuddyBossNoticeSystem = {
			init: function () {
				this.showNotifications();
				this.switchNotices();
				this.dismissNotice();
			},

			showNotifications: function () {
				$( document ).on(
					'click',
					'#bb-notifications-button',
					function ( e ) {
						e.preventDefault();

						var $wrapper = $( this ).closest( '.bb-notifications-wrapepr' );

						$wrapper.toggleClass( 'expanded' );

						BuddyBossNoticeSystem.checkNoticesCount();
					}
				);

				$( document ).on(
					'click',
					'.close-panel-header',
					function ( e ) {
						e.preventDefault();

						var $wrapper = $( this ).closest( '.bb-notifications-wrapepr' );

						$wrapper.removeClass( 'expanded' );
					}
				);
			},

			switchNotices: function () {
				$( document ).on(
					'click',
					'.panel-nav-list .switch-notices',
					function ( e ) {
						e.preventDefault();

						var $this               = $( this );
						var status              = $this.data( 'status' );
						var $notificationsPanel = $this.closest( '.bb-notifications-panel' );

						$this.closest( 'li' ).siblings().find( '.switch-notices' ).removeClass( 'active' );
						$this.addClass( 'active' );
						$notificationsPanel.removeClass( 'all dismissed unread' );
						$notificationsPanel.addClass( status );

						BuddyBossNoticeSystem.checkNoticesStatus( status );
					}
				);

				$( document ).on(
					'click',
					'.panel-nav-dismiss-all',
					function ( event ) {

						event.preventDefault();

						var $this          = $( this );
						var countEl        = $( '.count-active' );
						var countData      = countEl.text().trim().match( /\d+/ );
						var count          = countData ? parseInt( countData[0], 10 ) : 0;
						var iconCountEl    = $( '.bb-notice-count' );
						var adminMenuCount = $( '#bb_in_plugin_admin_menu_unread_count' );
						var status         = $this.closest( '.bb-panel-nav' ).find( '.panel-nav-list li > a.active' ).data( 'status' );

						var data = {
							action: 'buddyboss_in_plugin_notification_dismiss',
							nonce : BBInPluginAdminNotifications.nonce,
							id    : 'all',
						};

						$this.prop( 'disabled', 'disabled' );

						$.post(
							BBInPluginAdminNotifications.ajax_url,
							data,
							function ( res ) {

								if ( ! res.success ) {
									console.log( res );
								} else {
									$this.closest( '.bb-notifications-panel' ).find( '.bb-notices-blocks-container .bb-notice-block' ).each(
										function () {
											$( this ).removeClass( 'unread' ).addClass( 'dismissed' );
										}
									);
									count = 0;
									if ( 0 === count ) {
										countEl.hide();
										$this.closest( '.panel-nav-check' ).hide();
										iconCountEl.hide();
										adminMenuCount.closest( '.awaiting-mod' ).remove();
									} else if ( count < 10 ) {
										countEl.addClass( 'single-digit' );
										countEl.html( '(' + count + ')' );
										iconCountEl.html( count );
										adminMenuCount.html( count );
									} else {
										countEl.html( '(' + count + ')' );
										iconCountEl.html( count );
										adminMenuCount.html( count );
									}

									BuddyBossNoticeSystem.checkNoticesStatus( status );
								}
							}
						).fail(
							function () {
								alert( 'Messages could not be dismissed.' );
							}
						);
					}
				);

				BuddyBossNoticeSystem.checkNoticesStatus();
			},

			dismissNotice: function () {
				$( document ).on(
					'click',
					'.bb-dismiss-notice',
					function ( event ) {

						event.preventDefault();

						var $this          = $( this );
						var messageId      = $this.closest( '.bb-notice-block' ).data( 'message-id' );
						var message        = $( '#bb-notifications-message-' + messageId );
						var countEl        = $( '.count-active' );
						var countData      = countEl.text().trim().match( /\d+/ );
						var count          = countData ? parseInt( countData[0], 10 ) : 0;
						var iconCountEl    = $( '.bb-notice-count' );
						var adminMenuCount = $( '#bb_in_plugin_admin_menu_unread_count' );
						var status         = $this.closest( '.bb-notifications-panel' ).find( '.panel-nav-list li > a.active' ).data( 'status' );

						var data = {
							action: 'buddyboss_in_plugin_notification_dismiss',
							nonce : BBInPluginAdminNotifications.nonce,
							id    : messageId,
						};

						$this.prop( 'disabled', 'disabled' );
						message.removeClass( 'unread' ).addClass( 'dismissed' );

						$.post(
							BBInPluginAdminNotifications.ajax_url,
							data,
							function ( res ) {
								if ( ! res.success ) {
									console.log( res );
								} else {
									count--;

									if ( count < 0 ) {
										count = 0;
										countEl.hide();
										iconCountEl.hide();
										adminMenuCount.closest( '.awaiting-mod' ).remove();
									} else if ( 0 === count ) {
										countEl.hide();
										$( '.buddyboss-notifications-none' ).show();
										$( '.dismiss-all' ).hide();
										iconCountEl.hide();
										adminMenuCount.closest( '.awaiting-mod' ).remove();
									} else if ( count < 10 ) {
										countEl.addClass( 'single-digit' );
										countEl.html( '(' + count + ')' );
										iconCountEl.html( count );
										adminMenuCount.html( count );
									} else {
										countEl.html( '(' + count + ')' );
										iconCountEl.html( count );
										adminMenuCount.html( count );
									}

									BuddyBossNoticeSystem.checkNoticesStatus( status );
								}

							}
						).fail(
							function () {
								alert( 'Message could not be dismissed.' );
							}
						);
					}
				);
			},

			checkNoticesStatus: function ( status ) {
				status                = _.isUndefined( status ) ? 'all' : status;
				var $noticesContainer = $( '.bb-notices-blocks-container' );
				var $noticeBlocks     = $noticesContainer.find( '.bb-notice-block' );

				var diffStatus = [];
				$noticeBlocks.each(
					function () {
						if ( 'all' !== status && ! $( this ).hasClass( status ) ) {
								diffStatus.push( false );
						} else {
							diffStatus.push( true );
						}
					}
				);
				if ( diffStatus.length > 0 && diffStatus.includes( true ) ) {
					$noticesContainer.removeClass( 'bp-hide' );
					$noticesContainer.closest( '.bb-panel-body' ).find( '.bb-notices-blocks-blank' ).hide();
					$noticesContainer.closest( '.bb-panel-body' ).removeClass( 'empty' );
				} else {
					$noticesContainer.closest( '.bb-panel-body' ).find( '.bb-notices-blocks-blank' ).show();
					$noticesContainer.closest( '.bb-panel-body' ).addClass( 'empty' );
					$noticesContainer.addClass( 'bp-hide' );
				}

				var allDismissed = true;
				$noticeBlocks.each(
					function () {
						if ( ! $( this ).hasClass( status ) ) {
								allDismissed = false;
								return false;
						}
					}
				);

				if ( allDismissed ) {
					$noticesContainer.closest( '.bb-panel-body' ).addClass( 'all-dismissed' );
				} else {
					$noticesContainer.closest( '.bb-panel-body' ).removeClass( 'all-dismissed' );
				}
			},

			checkNoticesCount: function () {
				var $noticesContainer = $( '.bb-notices-blocks-container' );
				var $noticeBlocks     = $noticesContainer.find( '.bb-notice-block' );
				var $noticesCount     = $( '#show-active .count-unread' );

				var activeCount = 0;

				if ( $noticeBlocks.length === 0 ) {
					activeCount = 0;
				} else {
					$noticeBlocks.each(
						function () {
							if ( $( this ).hasClass( 'unread' ) ) {
									activeCount++;
							}
						}
					);
				}

				$noticesCount.text( '(' + activeCount + ')' );

			}
		};

		BuddyBossNoticeSystem.init();

	}
)( jQuery );
