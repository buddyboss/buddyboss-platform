jQuery( document ).ready(
	function () {

		var $tagsSelect = jQuery( 'body' ).find( '.bbp_topic_tags_dropdown' );
		var tagsArrayData = [];

		if ( $tagsSelect.length ) {

			$tagsSelect.each( function ( i, element ) {

				// added support for shortcode in elementor popup.
				if ( jQuery( element ).parents( '.elementor-location-popup' ).length > 0 ) {
					return;
				}

				jQuery( element ).select2( {
					placeholder: jQuery( element ).attr( 'placeholder' ),
					minimumInputLength: 1,
					closeOnSelect: true,
					tags: true,
					language: ( typeof bp_select2 !== 'undefined' && typeof bp_select2.lang !== 'undefined' ) ? bp_select2.lang : 'en',
					dropdownCssClass: 'bb-select-dropdown',
					containerCssClass: 'bb-select-container',
					tokenSeparators: [ ',', ' ' ],
					ajax: {
						url: bbpCommonJsData.ajax_url,
						dataType: 'json',
						delay: 1000,
						data: function ( params ) {
							return jQuery.extend( {}, params, {
								_wpnonce: bbpCommonJsData.nonce,
								action: 'search_tags',
							} );
						},
						cache: true,
						processResults: function ( data ) {

							// Removed the element from results if already selected.
							if ( false === jQuery.isEmptyObject( tagsArrayData ) ) {
								jQuery.each( tagsArrayData, function ( index, value ) {
									for ( var i = 0; i < data.data.results.length; i++ ) {
										if ( data.data.results[ i ].id === value ) {
											data.data.results.splice( i, 1 );
										}
									}
								} );
							}

							return {
								results: data && data.success ? data.data.results : []
							};
						}
					}
				} );

				// Add element into the Arrdata array.
				jQuery( element ).on( 'select2:select', function ( e ) {
					var select_options = jQuery( 'body #bbp_topic_tags_dropdown option' );
					var tagsArrayData = jQuery.map( select_options, function ( option ) {
						return option.text;
					} );
					var tags = tagsArrayData.join( ',' );
					jQuery( 'body #bbp_topic_tags' ).val( tags );

					jQuery( 'body .select2-search__field' ).trigger( 'click' );
					jQuery( 'body .select2-search__field' ).trigger( 'click' );
				} );

				// Remove element into the Arrdata array.
				jQuery( element ).on( 'select2:unselect', function ( e ) {
					var data = e.params.data;
					jQuery( 'body #bbp_topic_tags_dropdown option[value="' + data.id + '"]' ).remove();
					var select_options = jQuery( 'body #bbp_topic_tags_dropdown option' );
					var tagsArrayData = jQuery.map( select_options, function ( option ) {
						return option.text;
					} );
					tagsArrayData = jQuery.grep( tagsArrayData, function ( value ) {
						return value !== data.id;
					} );
					var tags = tagsArrayData.join( ',' );
					jQuery( 'body #bbp_topic_tags' ).val( tags );
					if ( tags.length === 0 ) {
						jQuery( window ).scrollTop( jQuery( window ).scrollTop() + 1 );
					}
				} );
			} );

		}

		// "remove all tags" button event listener
		jQuery( 'body' ).on( 'click', '.js-modal-close', function () {
			$tagsSelect.val( '' );
			$tagsSelect.trigger( 'change' ); // Notify any JS components that the value changed
			jQuery( 'body' ).removeClass( 'popup-modal-reply' );
			jQuery( '.medium-editor-toolbar' ).removeClass( 'active medium-editor-toolbar-active' );
			jQuery( '#show-toolbar-button' ).removeClass( 'active' );
			jQuery( 'medium-editor-action' ).removeClass( 'medium-editor-button-active' );
			jQuery( '.medium-editor-toolbar-actions' ).show();
			jQuery( '.medium-editor-toolbar-form' ).removeClass( 'medium-editor-toolbar-form-active' );
		} );

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

		if ( typeof BP_Nouveau !== 'undefined' && typeof BP_Nouveau.media !== 'undefined' && typeof BP_Nouveau.media.emoji !== 'undefined' ) {
			if ( jQuery( '.bbp-the-content' ).length && typeof jQuery.prototype.emojioneArea !== 'undefined' ) {
				jQuery( '.bbp-the-content' ).each( function ( i, element ) {
					var elem_id = jQuery( element ).attr( 'id' );
					var key = jQuery( element ).data( 'key' );
					jQuery( '#' + elem_id ).emojioneArea(
						{
							standalone: true,
							hideSource: false,
							container: jQuery( '#' + elem_id ).closest( 'form' ).find( '#whats-new-toolbar > .post-emoji' ),
							autocomplete: false,
							pickerPosition: 'bottom',
							hidePickerOnBlur: true,
							useInternalCDN: false,
							events: {
								ready: function () {
									if ( typeof window.forums_medium_topic_editor !== 'undefined' && typeof window.forums_medium_topic_editor[ key ] !== 'undefined' ) {
										window.forums_medium_topic_editor[ key ].resetContent();
									}
									if ( typeof window.forums_medium_reply_editor !== 'undefined' && typeof window.forums_medium_reply_editor[ key ] !== 'undefined' ) {
										window.forums_medium_reply_editor[ key ].resetContent();
									}
									if ( typeof window.forums_medium_forum_editor !== 'undefined' && typeof window.forums_medium_forum_editor[ key ] !== 'undefined' ) {
										window.forums_medium_forum_editor[ key ].resetContent();
									}
								},
								emojibtn_click: function () {
									if ( typeof window.forums_medium_topic_editor !== 'undefined' && typeof window.forums_medium_topic_editor[ key ] !== 'undefined' ) {
										window.forums_medium_topic_editor[ key ].checkContentChanged();
									}
									if ( typeof window.forums_medium_reply_editor !== 'undefined' && typeof window.forums_medium_reply_editor[ key ] !== 'undefined' ) {
										window.forums_medium_reply_editor[ key ].checkContentChanged();
									}
									if ( typeof window.forums_medium_forum_editor !== 'undefined' && typeof window.forums_medium_forum_editor[ key ] !== 'undefined' ) {
										window.forums_medium_forum_editor[ key ].checkContentChanged();
									}
									jQuery( '#' + elem_id )[ 0 ].emojioneArea.hidePicker();
								},
							}
						}
					);
				} );
			}
		}

		// Added support for elementor popup.
		if ( window.elementorFrontend ) {
			jQuery( document ).on( 'elementor/popup/show', function () {
				var $tagsSelect = jQuery( 'body' ).find( 'div.elementor-location-popup' ).find( '.bbp_topic_tags_dropdown' );
				if ( $tagsSelect.length ) {
					$tagsSelect.select2( {
						placeholder: $tagsSelect.attr( 'placeholder' ),
						minimumInputLength: 1,
						closeOnSelect: true,
						tags: true,
						language: ( typeof bp_select2 !== 'undefined' && typeof bp_select2.lang !== 'undefined' ) ? bp_select2.lang : 'en',
						dropdownCssClass: 'bb-select-dropdown',
						containerCssClass: 'bb-select-container',
						tokenSeparators: [ ',', ' ' ],
						ajax: {
							url: bbpCommonJsData.ajax_url,
							dataType: 'json',
							delay: 1000,
							data: function ( params ) {
								return jQuery.extend( {}, params, {
									_wpnonce: bbpCommonJsData.nonce,
									action: 'search_tags',
								} );
							},
							cache: true,
							processResults: function ( data ) {

								// Removed the element from results if already selected.
								if ( false === jQuery.isEmptyObject( tagsArrayData ) ) {
									jQuery.each( tagsArrayData, function ( index, value ) {
										for ( var i = 0; i < data.data.results.length; i++ ) {
											if ( data.data.results[ i ].id === value ) {
												data.data.results.splice( i, 1 );
											}
										}
									} );
								}

								return {
									results: data && data.success ? data.data.results : []
								};
							}
						}
					} );

					// Add element into the Arrdata array.
					$tagsSelect.on( 'select2:select', function ( e ) {
						var data = e.params.data;
						tagsArrayData.push( data.id );
						var tags = tagsArrayData.join( ',' );
						jQuery( 'body #bbp_topic_tags' ).val( tags );

						jQuery( 'body .select2-search__field' ).trigger( 'click' );
						jQuery( 'body .select2-search__field' ).trigger( 'click' );
					} );

					// Remove element into the Arrdata array.
					$tagsSelect.on( 'select2:unselect', function ( e ) {
						var data = e.params.data;
						tagsArrayData = jQuery.grep( tagsArrayData, function ( value ) {
							return value !== data.id;
						} );
						var tags = tagsArrayData.join( ',' );
						jQuery( 'body #bbp_topic_tags' ).val( tags );
						if ( tags.length === 0 ) {
							jQuery( window ).scrollTop( jQuery( window ).scrollTop() + 1 );
						}
					} );

				}
			} );
		}
	}
);
