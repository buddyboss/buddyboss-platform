/* global bp_select2, bbpCommonJsData */
jQuery( document ).ready(
	function ( $ ) {

		var $tagsSelect = jQuery( 'body' ).find( '.bbp_topic_tags_dropdown' );
		var tagsArrayData = [];

		if ( $tagsSelect.length ) {

			$tagsSelect.each( function ( i, element ) {

				// added support for shortcode in elementor popup.
				if ( jQuery( element ).parents( '.elementor-location-popup' ).length > 0 ) {
					return;
				}

				jQuery( element ).select2( {
					dropdownParent: jQuery( element ).closest('form').parent(),
					placeholder: jQuery( element ).attr( 'placeholder' ),
					minimumInputLength: 1,
					closeOnSelect: true,
					tags: true,
					language: {
						errorLoading: function () {
							return bp_select2.i18n.errorLoading;
						},
						inputTooLong: function ( e ) {
							var n = e.input.length - e.maximum;
							return bp_select2.i18n.inputTooLong.replace( '%%', n );
						},
						inputTooShort: function ( e ) {
							return bp_select2.i18n.inputTooShort.replace( '%%', (e.minimum - e.input.length) );
						},
						loadingMore: function () {
							return bp_select2.i18n.loadingMore;
						},
						maximumSelected: function ( e ) {
							return bp_select2.i18n.maximumSelected.replace( '%%', e.maximum );
						},
						noResults: function () {
							return bp_select2.i18n.noResults;
						},
						searching: function () {
							return bp_select2.i18n.searching;
						},
						removeAllItems: function () {
							return bp_select2.i18n.removeAllItems;
						}
					},
					dropdownCssClass: 'bb-select-dropdown bb-tag-list-dropdown',
					containerCssClass: 'bb-select-container',
					tokenSeparators: [ ',' ],
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
				jQuery( element ).on(
					'select2:select',
					function ( e ) {
						var form = jQuery( element ).closest( 'form' ),
							bbp_topic_tags = form.find( '#bbp_topic_tags' ),
							existingTags   = bbp_topic_tags.val(),
							tagsArrayData  = existingTags && existingTags.length > 0 ? existingTags.split( ',' ) : [],
							data           = e.params.data;

						if ( ! tagsArrayData.includes( data.text ) ) {
							tagsArrayData.push( data.text );
						}
						var tags = tagsArrayData.join( ',' );
						bbp_topic_tags.val( tags );

						// Prevent duplicates local suggession tags.
						var tempTags = [];
						jQuery( element ).find( 'option' ).each( function () {
							var title = jQuery( this ).attr( 'value' );
							if ( tempTags.includes( title ) ) {
								jQuery( this ).remove();
							} else {
								tempTags.push( title );
							}
						} );

						form.find( '.select2-search__field' ).trigger( 'click' );
					}
				);

				// Remove element into the Arrdata array.
				jQuery( element ).on( 'select2:unselect', function ( e ) {
					var form = jQuery( element ).closest( 'form' );
					var data = e.params.data;

					form.find( '.bbp_topic_tags_dropdown option[value="' + data.id + '"]' ).remove();
					var existingTags = form.find( '#bbp_topic_tags' ).val();
					tagsArrayData    = existingTags && existingTags.length > 0 ? existingTags.split( ',' ) : [];
					tagsArrayData    = tagsArrayData.filter( function( item ) {
						return jQuery.trim( item ) !== data.text;
					});
					var tags = tagsArrayData.join( ',' );

					form.find( '#bbp_topic_tags' ).val( tags );

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
			jQuery( '#whats-new-attachments .bb-url-scrapper-container' ).remove();
		} );

		// Implement support for BFCache.
		window.addEventListener( 'unload', function () {
			var form = jQuery( '.bbp-topic-form form' );
			if ( form.length > 0 ) {
				form[ 0 ].reset();
			}
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
									if ( typeof window.forums_medium_topic_editor == 'undefined' ) {
										$( '#bbpress-forums .bbp-the-content' ).keyup();
									}
									jQuery( '#' + elem_id )[ 0 ].emojioneArea.hidePicker();
								},
								search_keypress: function() {
									var _this = this;
									var small = _this.search.val().toLowerCase();
									_this.search.val(small);
								},

								picker_show: function () {
									$( this.button[0] ).closest( '.post-emoji' ).addClass('active');
								},

								picker_hide: function () {
									$( this.button[0] ).closest( '.post-emoji' ).removeClass('active');
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

					$tagsSelect.each( function ( i, element ) {
						jQuery( element ).select2( {
							placeholder: jQuery( element ).attr( 'placeholder' ),
							dropdownParent: jQuery( element ).closest('form').parent(),
							minimumInputLength: 1,
							closeOnSelect: true,
							tags: true,
							language: {
								errorLoading: function () {
									return bp_select2.i18n.errorLoading;
								},
								inputTooLong: function ( e ) {
									var n = e.input.length - e.maximum;
									return bp_select2.i18n.inputTooLong.replace( '%%', n );
								},
								inputTooShort: function ( e ) {
									return bp_select2.i18n.inputTooShort.replace( '%%', (e.minimum - e.input.length) );
								},
								loadingMore: function () {
									return bp_select2.i18n.loadingMore;
								},
								maximumSelected: function ( e ) {
									return bp_select2.i18n.maximumSelected.replace( '%%', e.maximum );
								},
								noResults: function () {
									return bp_select2.i18n.noResults;
								},
								searching: function () {
									return bp_select2.i18n.searching;
								},
								removeAllItems: function () {
									return bp_select2.i18n.removeAllItems;
								}
							},
							dropdownCssClass: 'bb-select-dropdown bb-tag-list-dropdown',
							containerCssClass: 'bb-select-container',
							tokenSeparators: [ ',' ],
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
							var data = e.params.data;
							var form = jQuery( element ).closest( 'form' );
							var existingTags = form.find( '#bbp_topic_tags' ).val();
							tagsArrayData  = existingTags && existingTags.length > 0 ? existingTags.split( ',' ) : [];

							if ( ! tagsArrayData.includes( data.text ) ) {
								tagsArrayData.push( data.text );
							}

							var tags = tagsArrayData.join( ',' );
							form.find('#bbp_topic_tags' ).val( tags );

							// Prevent duplicates local suggession tags.
							var tempTags = [];
							jQuery( element ).find( 'option' ).each( function () {
								var title = jQuery( this ).attr( 'value' );
								if ( tempTags.includes( title ) ) {
									jQuery( this ).remove();
								} else {
									tempTags.push( title );
								}
							} );

							form.find( '.select2-search__field' ).trigger( 'click' );

						} );

						// Remove element into the Arrdata array.
						jQuery( element ).on( 'select2:unselect', function ( e ) {
							var data = e.params.data;
							var form = jQuery( element ).closest( 'form' );
							var existingTags = form.find( '#bbp_topic_tags' ).val();

							form.find( '.bbp_topic_tags_dropdown option[value="' + data.id + '"]' ).remove();
							tagsArrayData = existingTags && existingTags.length > 0 ? existingTags.split( ',' ) : [];
							tagsArrayData = tagsArrayData.filter( function( item ) {
								return item !== data.text;
							});

							var tags = tagsArrayData.join( ',' );
							form.find('#bbp_topic_tags' ).val( tags );

							if ( tags.length === 0 ) {
								jQuery( window ).scrollTop( jQuery( window ).scrollTop() + 1 );
							}
						} );
					} );
				}
			} );
		}

		jQuery( document ).on( 'input', '#bbp_topic_title', function ( e ) {
			if ( jQuery( e.currentTarget ).val().trim() !== '' ) {
				jQuery( e.currentTarget ).closest( 'form' ).addClass( 'has-title' );
			} else {
				jQuery( e.currentTarget ).closest( 'form' ).removeClass( 'has-title' );
			}
		} );

		if ( jQuery( 'textarea#bbp_topic_content' ).length !== 0 ) {
			// Enable submit button if content is available.
			jQuery( '#bbp_topic_content' ).on( 'keyup', function() {
				var $reply_content = jQuery( '#bbp_topic_content' ).val().trim();
				if ( $reply_content !== '' ) {
					jQuery( this ).closest( 'form' ).addClass( 'has-content' );
				} else {
					jQuery( this ).closest( 'form' ).removeClass( 'has-content' );
				}
			} );
		}

		if ( jQuery( 'textarea#bbp_reply_content' ).length !== 0 ) {
			// Enable submit button if content is available.
			jQuery( '#bbp_reply_content' ).on( 'keyup', function() {
				var $reply_content = jQuery( '#bbp_reply_content' ).val().trim();
				if ( $reply_content !== '' ) {
					jQuery( this ).closest( 'form' ).addClass( 'has-content' );
				} else {
					jQuery( this ).closest( 'form' ).removeClass( 'has-content' );
				}
			} );
		}

		jQuery( document ).on( 'input', '.bbp_editor_topic_content', function ( e ) {
			var content = jQuery( e.currentTarget )[ 0 ];
			if ( content.innerHTML.replace( /<p>/gi, '' ).replace( /<\/p>/gi, '' ).replace( /<br>/gi, '' ) === '' ) {
				var topic_content = '';
				content.innerHTML = topic_content;
				jQuery( '#bbp_topic_content' ).val( topic_content );
			}
		} );

		jQuery( document ).on( 'input', '.bbp_editor_reply_content', function ( e ) {
			var content = jQuery( e.currentTarget )[ 0 ];
			if ( content.innerHTML.replace( /<p>/gi, '' ).replace( /<\/p>/gi, '' ).replace( /<br>/gi, '' ) === '' ) {
				var reply_content = '';
				content.innerHTML = reply_content;
				jQuery( '#bbp_reply_content' ).val( reply_content );
			}
		} );

	}
);
