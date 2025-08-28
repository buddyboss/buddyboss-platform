/**
 * BuddyBoss Platform Mothership Admin JavaScript
 */

jQuery( document ).ready(
	function ($) {
		'use strict';

		// Initialize admin functionality.
		initAdminInterface();

		function initAdminInterface() {
			// Add any additional admin functionality here.
			console.log( 'BuddyBoss Platform Mothership Admin initialized' );
		}

		// Handle license key input validation.
		$( '#bb-platform-license-key' ).on(
			'input',
			function () {
				var licenseKey = $( this ).val();
				var $button    = $( '#bb-platform-activate-license' );

				if (licenseKey.length > 0) {
					$button.prop( 'disabled', false );
				} else {
					$button.prop( 'disabled', true );
				}
			}
		);

		// Auto-submit on Enter key.
		$( '#bb-platform-license-key' ).on(
			'keypress',
			function (e) {
				if (e.which === 13) {
					e.preventDefault();
					$( '#bb-platform-activate-license' ).trigger( 'click' );
				}
			}
		);

		// Handle notice dismissal..
		$( document ).on(
			'click',
			'.notice-dismiss',
			function () {
				var $notice = $( this ).closest( '.notice' );
				$notice.fadeOut();
			}
		);

		// Add loading states..
		function showLoading($element) {
			$element.addClass( 'loading' ).prop( 'disabled', true );
		}

		function hideLoading($element) {
			$element.removeClass( 'loading' ).prop( 'disabled', false );
		}

		// Expose functions globally for use in templates.
		window.BBPlatformMothershipAdmin = {
			showLoading: showLoading,
			hideLoading: hideLoading
		};
	}
);
