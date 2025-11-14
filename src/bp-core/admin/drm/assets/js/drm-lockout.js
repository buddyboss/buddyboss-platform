/**
 * BuddyBoss DRM Lockout Screen JavaScript
 *
 * Handles AJAX license activation from the lockout screen.
 *
 * @package BuddyBoss\Core\Admin\DRM
 * @since 3.0.0
 */

(function($) {
	'use strict';

	/**
	 * DRM Lockout Handler
	 */
	var BBDrmLockout = {
		/**
		 * Initialize
		 */
		init: function() {
			this.bindEvents();
			this.focusInput();
		},

		/**
		 * Bind event handlers
		 */
		bindEvents: function() {
			$('#bb-drm-activate-btn').on('click', this.handleActivation.bind(this));
			$('#bb-drm-license-key').on('keypress', this.handleKeyPress.bind(this));
		},

		/**
		 * Focus license input on load
		 */
		focusInput: function() {
			setTimeout(function() {
				$('#bb-drm-license-key').focus();
			}, 500);
		},

		/**
		 * Handle Enter key press
		 */
		handleKeyPress: function(e) {
			if (e.which === 13) {
				e.preventDefault();
				$('#bb-drm-activate-btn').click();
			}
		},

		/**
		 * Handle license activation
		 */
		handleActivation: function(e) {
			e.preventDefault();

			var $btn = $('#bb-drm-activate-btn');
			var $input = $('#bb-drm-license-key');
			var $message = $('#bb-drm-activation-message');
			var licenseKey = $input.val().trim();

			// Validate input
			if (!licenseKey) {
				this.showMessage('error', bbDrmLockout.invalidKeyText);
				$input.focus();
				return;
			}

			// Disable form
			$btn.prop('disabled', true).addClass('bb-drm-loading');
			$input.prop('disabled', true);

			// Show activating message
			this.showMessage('info', bbDrmLockout.activatingText);

			// Send AJAX request
			$.ajax({
				url: bbDrmLockout.ajaxUrl,
				type: 'POST',
				data: {
					action: 'bb_drm_activate_license',
					nonce: bbDrmLockout.nonce,
					license_key: licenseKey
				},
				success: this.handleSuccess.bind(this),
				error: this.handleError.bind(this),
				complete: function() {
					$btn.prop('disabled', false).removeClass('bb-drm-loading');
					$input.prop('disabled', false);
				}
			});
		},

		/**
		 * Handle successful activation
		 */
		handleSuccess: function(response) {
			if (response.success) {
				this.showMessage('success', bbDrmLockout.successText);

				// Reload page after 2 seconds
				setTimeout(function() {
					window.location.reload();
				}, 2000);
			} else {
				var errorMsg = response.data && response.data.message
					? response.data.message
					: bbDrmLockout.errorText;
				this.showMessage('error', errorMsg);
				$('#bb-drm-license-key').focus().select();
			}
		},

		/**
		 * Handle AJAX error
		 */
		handleError: function(xhr, status, error) {
			console.error('DRM Activation Error:', error);
			this.showMessage('error', bbDrmLockout.networkErrorText);
			$('#bb-drm-license-key').focus();
		},

		/**
		 * Show message to user
		 */
		showMessage: function(type, text) {
			var $message = $('#bb-drm-activation-message');

			$message
				.removeClass('bb-drm-success bb-drm-error bb-drm-info bb-drm-visible')
				.addClass('bb-drm-' + type)
				.text(text);

			// Trigger reflow for animation
			$message[0].offsetHeight;

			$message.addClass('bb-drm-visible');
		}
	};

	/**
	 * Initialize on document ready
	 */
	$(document).ready(function() {
		if ($('#bb-drm-lockout-overlay').length) {
			BBDrmLockout.init();
		}
	});

})(jQuery);
