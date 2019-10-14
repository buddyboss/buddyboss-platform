jQuery( document ).ready(
	function() {

			var $tagsSelect   = jQuery( 'body' ).find( '#bbp_topic_tags_dropdown' );
			var tagsArrayData = [];

	if ( $tagsSelect.length ) {
		$tagsSelect.select2({
			placeholder: $tagsSelect.attr('placeholder'),
			minimumInputLength: 1,
			tags: true,
                        dropdownCssClass: 'bb-select-dropdown',
                        containerCssClass: 'bb-select-container',
			tokenSeparators: [',', ' '],
			ajax: {
				url: bbpCommonJsData.ajax_url,
				dataType: 'json',
				delay: 250,
				data: function (params) {
					return jQuery.extend({}, params, {
						_wpnonce: bbpCommonJsData.nonce,
						action: 'search_tags',
					});
				},
				cache: true,
				processResults: function (data) {

					// Removed the element from results if already selected.
					if (false === jQuery.isEmptyObject(tagsArrayData)) {
						jQuery.each(tagsArrayData, function (index, value) {
							for (var i = 0; i < data.data.results.length; i++) {
								if (data.data.results[i].id === value) {
									data.data.results.splice(i, 1);
								}
							}
						});
					}

					return {
						results: data && data.success ? data.data.results : []
					};
				}
			}
		});

		// Add element into the Arrdata array.
		$tagsSelect.on('select2:select', function (e) {
			var data = e.params.data;
			tagsArrayData.push(data.id);
			var tags = tagsArrayData.join(',');
			jQuery('body #bbp_topic_tags').val(tags);
		});

		// Remove element into the Arrdata array.
		$tagsSelect.on('select2:unselect', function (e) {
			var data = e.params.data;
			tagsArrayData = jQuery.grep(tagsArrayData, function (value) {
				return value !== data.id;
			});
			var tags = tagsArrayData.join(',');
			jQuery('body #bbp_topic_tags').val(tags);
			if (tags.length === 0) {
				jQuery(window).scrollTop(jQuery(window).scrollTop() + 1);
			}
		});
	}
	// "remove all tags" button event listener
	jQuery( 'body' ).on('click', '.js-modal-close', function() {
		$tagsSelect.val('');
		$tagsSelect.trigger( 'change' ); // Notify any JS components that the value changed
		jQuery( 'body' ).removeClass( 'popup-modal-reply' );
	});

			var topicReplyButton = jQuery( 'body .bbp-topic-reply-link' );
		if ( topicReplyButton.length ) {
			topicReplyButton.click(
				function () {
						jQuery( 'body' ).addClass( 'popup-modal-reply' );
						$tagsSelect.val( '' );
						$tagsSelect.trigger( 'change' ); // Notify any JS components that the value changed
				}
			);
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
			if (jQuery( bbp_editor_content_elem ).length && typeof jQuery.prototype.emojioneArea !== 'undefined' ) {
				jQuery( bbp_editor_content_elem ).emojioneArea(
					{
						standalone: true,
						hideSource: false,
						container: jQuery( '#whats-new-toolbar > .post-emoji' ),
						autocomplete: false,
						pickerPosition: 'bottom',
						hidePickerOnBlur: true,
						useInternalCDN: false,
						events: {
							ready: function () {
								if (typeof window.forums_medium_topic_editor !== 'undefined') {
									window.forums_medium_topic_editor.setContent( jQuery( '#bbp_topic_content' ).val() );
								}
								if (typeof window.forums_medium_reply_editor !== 'undefined') {
									window.forums_medium_reply_editor.setContent( jQuery( '#bbp_reply_content' ).val() );
								}
								if (typeof window.forums_medium_forum_editor !== 'undefined') {
									window.forums_medium_forum_editor.setContent( jQuery( '#bbp_forum_content' ).val() );
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
								jQuery( bbp_editor_content_elem )[0].emojioneArea.hidePicker();
							},
						}
						}
				);
			}
		}
	}
);
