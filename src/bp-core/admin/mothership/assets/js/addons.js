/**
 * BuddyBoss Platform Mothership Addons JavaScript
 */

jQuery( document ).ready(
	function ($) {
		'use strict';

		// Initialize addons functionality.
		initAddonsInterface();

		function initAddonsInterface() {
			// Add any additional addons functionality here.
			console.log( 'BuddyBoss Platform Mothership Addons initialized' );
		}

		// Handle addon action buttons.
		$( document ).on(
			'click',
			'.addon-action',
			function (e) {
				e.preventDefault();

				var $button = $( this );
				var $card   = $button.closest( '.bb-platform-addon-card' );
				var action  = $button.data( 'action' );

				// Show loading state.
				showCardLoading( $card );

				// Handle different actions.
				switch (action) {
					case 'install':
						handleInstallAddon( $button, $card );
						break;
					case 'activate':
						handleActivateAddon( $button, $card );
						break;
					case 'deactivate':
						handleDeactivateAddon( $button, $card );
						break;
				}
			}
		);

		function handleInstallAddon($button, $card) {
			var addonSlug = $button.data( 'slug' );
			var $message  = $card.find( '.addon-message' );

			$.ajax(
				{
					url: bbPlatformMothershipAddons.ajaxUrl,
					type: 'POST',
					data: {
						action: 'bb_platform_install_addon',
						addon_slug: addonSlug,
						nonce: bbPlatformMothershipAddons.nonce
					},
					success: function (response) {
						if (response.success) {
							showMessage( $message, response.data.message, 'success' );
							setTimeout(
								function () {
									location.reload();
								},
								2000
							);
						} else {
							showMessage( $message, response.data, 'error' );
						}
					},
					error: function () {
						showMessage( $message, bbPlatformMothershipAddons.strings.error, 'error' );
					},
					complete: function () {
						hideCardLoading( $card );
					}
				}
			);
		}

		function handleActivateAddon($button, $card) {
			var pluginFile = $button.data( 'plugin' );
			var $message   = $card.find( '.addon-message' );

			$.ajax(
				{
					url: bbPlatformMothershipAddons.ajaxUrl,
					type: 'POST',
					data: {
						action: 'bb_platform_activate_addon',
						plugin_file: pluginFile,
						nonce: bbPlatformMothershipAddons.nonce
					},
					success: function (response) {
						if (response.success) {
							showMessage( $message, response.data.message, 'success' );
							setTimeout(
								function () {
									location.reload();
								},
								2000
							);
						} else {
							showMessage( $message, response.data, 'error' );
						}
					},
					error: function () {
						showMessage( $message, bbPlatformMothershipAddons.strings.error, 'error' );
					},
					complete: function () {
						hideCardLoading( $card );
					}
				}
			);
		}

		function handleDeactivateAddon($button, $card) {
			var pluginFile = $button.data( 'plugin' );
			var $message   = $card.find( '.addon-message' );

			$.ajax(
				{
					url: bbPlatformMothershipAddons.ajaxUrl,
					type: 'POST',
					data: {
						action: 'bb_platform_deactivate_addon',
						plugin_file: pluginFile,
						nonce: bbPlatformMothershipAddons.nonce
					},
					success: function (response) {
						if (response.success) {
							showMessage( $message, response.data.message, 'success' );
							setTimeout(
								function () {
									location.reload();
								},
								2000
							);
						} else {
							showMessage( $message, response.data, 'error' );
						}
					},
					error: function () {
						showMessage( $message, bbPlatformMothershipAddons.strings.error, 'error' );
					},
					complete: function () {
						hideCardLoading( $card );
					}
				}
			);
		}

		function showCardLoading($card) {
			$card.addClass( 'addon-card-loading' );
			$card.find( '.addon-action' ).prop( 'disabled', true );
		}

		function hideCardLoading($card) {
			$card.removeClass( 'addon-card-loading' );
			$card.find( '.addon-action' ).prop( 'disabled', false );
		}

		function showMessage($container, message, type) {
			var noticeClass = type === 'success' ? 'notice-success' : 'notice-error';
			var html        = '<div class="notice ' + noticeClass + '"><p>' + message + '</p></div>';

			$container.html( html );

			// Auto-hide success messages after 5 seconds.
			if (type === 'success') {
				setTimeout(
					function () {
						$container.find( '.notice' ).fadeOut();
					},
					5000
				);
			}
		}

		// Handle notice dismissal.
		$( document ).on(
			'click',
			'.notice-dismiss',
			function () {
				var $notice = $( this ).closest( '.notice' );
				$notice.fadeOut();
			}
		);

		// Expose functions globally for use in templates.
		window.BBPlatformMothershipAddons = {
			showCardLoading: showCardLoading,
			hideCardLoading: hideCardLoading,
			showMessage: showMessage
		};
	}
);
