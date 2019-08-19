jQuery(document).ready( function() {
    if ( typeof window.Tagify !== 'undefined' ) {
        var input  = document.querySelector('input[name=bbp_topic_tags_tagify]');

        if ( input != null ) {
            window.bbp_tagify = new window.Tagify(input);

			window.bbp_tagify.on('input', onInput);

            window.bbp_tagify.on('add', function () {
                var bbp_topic_tags = '';
                for( var i = 0 ; i < window.bbp_tagify.value.length; i++ ) {
                    bbp_topic_tags += window.bbp_tagify.value[i].value + ',';
                }
                jQuery('#bbp_topic_tags').val(bbp_topic_tags);
            }).on('remove', function () {
                var bbp_topic_tags = '';
                for( var i = 0 ; i < window.bbp_tagify.value.length; i++ ) {
                    bbp_topic_tags += window.bbp_tagify.value[i].value + ',';
                }
                jQuery('#bbp_topic_tags').val(bbp_topic_tags);
            });

            // "remove all tags" button event listener
            jQuery( 'body' ).on('click', '.js-modal-close', window.bbp_tagify.removeAllTags.bind(window.bbp_tagify));
        }
    }

	function onInput( e ){
		var value = e.detail.value;
		window.bbp_tagify.settings.whitelist.length = 0; // reset the whitelist

		var data = {
			'action': 'search_tags',
			'_wpnonce': Common_Data.nonce,
			'tag' : value
		};

		jQuery.ajax({
			type: 'GET',
			url: Common_Data.ajax_url,
			data: data,
			success: function ( response ) {
				if ( response.success ) {
					window.bbp_tagify.settings.whitelist = response.data.tags;
					window.bbp_tagify.dropdown.show.call(window.bbp_tagify, value); // render the suggestions dropdown
				}
			}
		});

	}

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
