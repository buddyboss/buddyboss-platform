/**
 * Broadcast Email Admin — method field toggling, test email AJAX, token panel.
 */
(function($) {
	'use strict';

	/**
	 * Show/hide method-specific fieldsets based on the selected radio button.
	 */
	function toggleMethodFields() {
		var method = $('#broadcast_method').val() || 'none';
		$('.broadcast-method-fields').hide();
		$('.broadcast-method-' + method).show();
	}

	$(document).ready(function() {

		// Initial toggle on page load.
		toggleMethodFields();

		// Toggle on method radio change.
		$('#broadcast_method').on('change', toggleMethodFields);

		// Test email AJAX.
		$('#broadcast-test-email-btn').on('click', function() {
			var $btn    = $(this);
			var $result = $('#broadcast-test-result');
			var to      = $('#broadcast-test-email-to').val() || '';

			$btn.prop('disabled', true);
			$result.text('Sending...').css('color', '#666');

			$.post(broadcastEmail.ajaxUrl, {
				action: 'broadcast_test_email',
				nonce:  broadcastEmail.testNonce,
				to:     to
			}, function(response) {
				if (response.success) {
					$result.text(response.data.message).css('color', '#00a32a');
				} else {
					$result.text((response.data && response.data.message) || 'Send failed.').css('color', '#d63638');
				}
			}).fail(function() {
				$result.text('Request failed. Check your connection.').css('color', '#d63638');
			}).always(function() {
				$btn.prop('disabled', false);
			});
		});

		// Tokens panel toggle (slideToggle).
		$(document).on('click', '.broadcast-tokens-toggle', function(e) {
			e.preventDefault();
			$(this).closest('.broadcast-tokens-panel').find('.broadcast-tokens-list').slideToggle();
		});

	});

})(jQuery);
