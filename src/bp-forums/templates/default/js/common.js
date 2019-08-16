jQuery(document).ready( function() {

	jQuery( '.form-control' ).select2({
		tags: true,
		placeholder: bbpTagsSelect.description,
		tokenSeparators: [',']
	});

    if (typeof BP_Nouveau !== 'undefined' && typeof BP_Nouveau.media !== 'undefined' && typeof BP_Nouveau.media.emoji !== 'undefined' ) {
        var bbp_editor_content_elem = false;
        if ( jQuery( '#bbp_editor_topic_content' ).length ) {
            bbp_editor_content_elem = '#bbp_editor_topic_content';
        } else if ( jQuery( '#bbp_editor_reply_content' ).length ) {
            bbp_editor_content_elem = '#bbp_editor_reply_content';
        } else if ( jQuery( '#bbp_editor_forum_content' ).length ) {
            bbp_editor_content_elem = '#bbp_editor_forum_content';
        } else if ( jQuery( '#bbp_topic_content' ).length ) {
            bbp_editor_content_elem = '#bbp_topic_content';
        } else if ( jQuery( '#bbp_reply_content' ).length ) {
            bbp_editor_content_elem = '#bbp_reply_content';
        } else if ( jQuery( '#bbp_forum_content' ).length ) {
            bbp_editor_content_elem = '#bbp_forum_content';
        }
        if (jQuery(bbp_editor_content_elem).length && typeof jQuery.prototype.emojioneArea !== 'undefined' ) {
            jQuery(bbp_editor_content_elem).emojioneArea({
                standalone: true,
                hideSource: false,
                container: jQuery('#whats-new-toolbar > .post-emoji'),
                autocomplete: false,
                pickerPosition: 'bottom',
                hidePickerOnBlur: true,
                useInternalCDN: false,
                events: {
                    ready: function () {
                        if (typeof window.forums_medium_topic_editor !== 'undefined') {
                            window.forums_medium_topic_editor.setContent(jQuery('#bbp_topic_content').val());
                        }
                        if (typeof window.forums_medium_reply_editor !== 'undefined') {
                            window.forums_medium_reply_editor.setContent(jQuery('#bbp_reply_content').val());
                        }
                        if (typeof window.forums_medium_forum_editor !== 'undefined') {
                            window.forums_medium_forum_editor.setContent(jQuery('#bbp_forum_content').val());
                        }
                    },
                    emojibtn_click: function () {
                        if (typeof window.forums_medium_topic_editor !== 'undefined') {
                            window.forums_medium_topic_editor.checkContentChanged();
                        }
                        if (typeof window.forums_medium_reply_editor !== 'undefined') {
                            window.forums_medium_reply_editor.checkContentChanged();
                        }
                        if (typeof window.forums_medium_forum_editor !== 'undefined') {
                            window.forums_medium_forum_editor.checkContentChanged();
                        }
                        jQuery(bbp_editor_content_elem)[0].emojioneArea.hidePicker();
                    },
                }
            });
        }
    }
});
