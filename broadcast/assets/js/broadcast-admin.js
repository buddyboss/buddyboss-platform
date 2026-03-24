/* global broadcastAdmin, wp */
(function ($) {
    'use strict';

    // -------------------------------------------------------
    // Enable/Disable Toggle
    // -------------------------------------------------------
    $(document).on('click', '.broadcast-toggle', function () {
        var $btn     = $(this);
        var id       = parseInt($btn.data('id'), 10);
        var current  = $btn.attr('aria-checked') === 'true';
        var newState = current ? 0 : 1;

        $btn.addClass('broadcast-toggle-saving');

        $.post(broadcastAdmin.ajaxUrl, {
            action:  'broadcast_toggle',
            nonce:   broadcastAdmin.toggleNonce,
            id:      id,
            enabled: newState
        }, function (response) {
            $btn.removeClass('broadcast-toggle-saving');
            if (response.success) {
                $btn.attr('aria-checked', newState ? 'true' : 'false');
                // Update status badge text (simple reload of status area is most reliable)
                // Full status re-derive requires server round-trip — reload row on next page load
            }
        }).fail(function () {
            $btn.removeClass('broadcast-toggle-saving');
        });
    });

    // -------------------------------------------------------
    // Delete Confirmation
    // -------------------------------------------------------
    $(document).on('click', '.broadcast-delete-link', function (e) {
        if (!window.confirm($(this).data('confirm') || 'Delete this announcement?')) {
            e.preventDefault();
        }
    });

    // -------------------------------------------------------
    // Media Uploader (image field on edit form)
    // -------------------------------------------------------
    var mediaFrame;

    $(document).on('click', '#broadcast-image-select', function (e) {
        e.preventDefault();

        if (mediaFrame) {
            mediaFrame.open();
            return;
        }

        mediaFrame = wp.media({
            title:    'Select Announcement Image',
            button:   { text: 'Use this image' },
            multiple: false
        });

        mediaFrame.on('select', function () {
            var attachment = mediaFrame.state().get('selection').first().toJSON();
            $('#broadcast_image_id').val(attachment.id);
            $('#broadcast-image-preview').attr('src', attachment.sizes.thumbnail ? attachment.sizes.thumbnail.url : attachment.url).show();
            $('#broadcast-image-remove').show();
        });

        mediaFrame.open();
    });

    $(document).on('click', '#broadcast-image-remove', function (e) {
        e.preventDefault();
        $('#broadcast_image_id').val('');
        $('#broadcast-image-preview').hide().attr('src', '');
        $(this).hide();
    });

    // -------------------------------------------------------
    // Button group: Type (popup / banner)
    // -------------------------------------------------------
    $(document).on('click', '.broadcast-btn-option', function () {
        var field = $(this).data('field');
        var value = $(this).data('value');
        $('#' + field).val(value);
        $(this).siblings('.broadcast-btn-option').removeClass('is-active');
        $(this).addClass('is-active');
        // Show matching hint
        $('.broadcast-type-hint').hide();
        $('#broadcast-type-hint-' + value).show();
        syncPositionForType(value);
    });

    // -------------------------------------------------------
    // Position grid buttons
    // -------------------------------------------------------
    $(document).on('click', '.broadcast-pos-btn', function () {
        $('.broadcast-pos-btn').removeClass('is-active');
        $(this).addClass('is-active');
        $('#broadcast_display_position').val($(this).data('pos'));
    });

    // -------------------------------------------------------
    // Hide "Middle" position when type = banner
    // -------------------------------------------------------
    function syncPositionForType(type) {
        var $middle = $('.broadcast-pos-middle-btn');
        if (type === 'banner') {
            $middle.hide();
            if ($('#broadcast_display_position').val() === 'middle') {
                $('#broadcast_display_position').val('top');
                $('.broadcast-pos-btn').removeClass('is-active');
                $('.broadcast-pos-btn[data-pos="top"]').addClass('is-active');
            }
        } else {
            $middle.show();
        }
    }

    // Run on page load
    syncPositionForType($('#broadcast_type').val());

	// -------------------------------------------------------
	// Group checkbox search filter
	// -------------------------------------------------------
	$(document).on('input', '.broadcast-group-search', function () {
		var search = $(this).val().toLowerCase();
		$(this).siblings('.broadcast-checkbox-list').find('label').each(function () {
			$(this).toggle($(this).text().toLowerCase().indexOf(search) !== -1);
		});
	});

}(jQuery));
