/* global bp */
window.bp = window.bp || {};

jQuery( document ).ready(
	function() {

		if ( typeof window.MediumEditor !== 'undefined' ) {

			window.forums_medium_forum_editor = [];
			window.forums_medium_reply_editor = [];
			window.forums_medium_topic_editor = [];

			var toolbarOptions = {
				buttons: ['bold', 'italic', 'unorderedlist','orderedlist', 'quote', 'anchor', 'pre' ],
				relativeContainer: document.getElementById('whats-new-toolbar'),
				static: true,
				updateOnEmptySelection: true
			};
			if ( jQuery( '.bbp_editor_forum_content' ).length ) {
				jQuery( '.bbp_editor_forum_content' ).each(function(i,element){

					// added support for shortcode in elementor popup.
					if ( jQuery( element ).parents( '.elementor-popup-modal' ).length > 0 ) {
						return;
					}

					var key = jQuery(element).data('key');
					window.forums_medium_forum_editor[key] = new window.MediumEditor(
						element,
						{
							placeholder: {
								text: window.bbpEditorJsStrs.description,
								hideOnClick: true
							},
							// toolbar: toolbarOptions,
							toolbar: Object.assign(toolbarOptions, { relativeContainer: jQuery( element ).closest( '.bbp-forum-form' ).closest( '.bbp-forum-form' ).find( '#whats-new-toolbar' )[0] } ),
							paste: {
								forcePlainText: false,
								cleanPastedHTML: true,
								cleanReplacements: [
									[new RegExp(/<div/gi), '<p'],
									[new RegExp(/<\/div/gi), '</p'],
									[new RegExp(/<h[1-6]/gi), '<b'],
									[new RegExp(/<\/h[1-6]/gi), '</b'],
								],
								cleanAttrs: ['class', 'style', 'dir', 'id'],
								cleanTags: [ 'meta', 'div', 'main', 'section', 'article', 'aside', 'button', 'svg', 'canvas', 'figure', 'input', 'textarea', 'select', 'label', 'form', 'table', 'thead', 'tfooter', 'colgroup', 'col', 'tr', 'td', 'th', 'dl', 'dd', 'center', 'caption', 'nav', 'img' ],
								unwrapTags: []
							},
							imageDragging: false,
							anchor: {
								placeholderText: BP_Nouveau.anchorPlaceholderText,
								linkValidation: true
							}
						}
					);

					window.forums_medium_forum_editor[key].subscribe(
						'editableInput',
						function () {
							var bbp_forum_content = jQuery(element).closest('form').find( '#bbp_forum_content' );
							var html = window.forums_medium_forum_editor[key].getContent();
							var dummy_element = document.createElement( 'div' );
							dummy_element.innerHTML = html;
							jQuery(dummy_element).find( 'span.atwho-query' ).replaceWith(
								function () {
									return this.innerText;
								}
							);

							// transform other emoji into emojionearea emoji.
							jQuery(dummy_element).find( 'img.emoji' ).each(function( index, Obj) {
								jQuery( Obj ).addClass( 'emojioneemoji' );
								var emojis = jQuery( Obj ).attr( 'alt' );
								jQuery( Obj ).attr( 'data-emoji-char', emojis );
								jQuery( Obj ).removeClass( 'emoji' );
							});

							// Transform emoji image into emoji unicode.
							jQuery(dummy_element).find( 'img.emojioneemoji' ).replaceWith(
								function () {
									return this.dataset.emojiChar;
								}
							);
							bbp_forum_content.val( jQuery(dummy_element).html() );

							// Enable submit button if content is available.
							var $reply_content   = jQuery( element ).html();

							$reply_content = jQuery.trim( $reply_content.replace( /<div>/gi, '\n' ).replace( /<\/div>/gi, '' ) );
							$reply_content = $reply_content.replace( /&nbsp;/g, ' ' );

							var content_text = jQuery( '<p>' + $reply_content + '</p>' ).text();
							if ( content_text !== '' || $reply_content.indexOf( 'emojioneemoji' ) >= 0 ) {
								jQuery( element ).closest( 'form' ).addClass( 'has-content' );
							} else {
								jQuery( element ).closest( 'form' ).removeClass( 'has-content' );
							}
						}
					);

				});
			}

			// Add Click event to show / hide text formatting Toolbar for forum form.
			jQuery( document ).on( 'click', '.bbp-forum-form #whats-new-toolbar .show-toolbar', function ( e ) {
				e.preventDefault();
				var key = jQuery( e.currentTarget ).closest( '.bbp-forum-form' ).find( '.bbp_editor_forum_content' ).data( 'key' );
				var medium_editor = jQuery( e.currentTarget ).closest( '.bbp-form' ).find( '.medium-editor-toolbar' );
				jQuery( e.currentTarget ).find( '.toolbar-button' ).toggleClass( 'active' );
				if ( jQuery( e.currentTarget ).find( '.toolbar-button' ).hasClass( 'active' ) ) {
					jQuery( e.currentTarget ).attr( 'data-bp-tooltip', jQuery( e.currentTarget ).attr( 'data-bp-tooltip-hide' ) );
					if ( window.forums_medium_forum_editor[ key ].exportSelection() !== null ) {
						medium_editor.addClass( 'medium-editor-toolbar-active' );
					}
				} else {
					jQuery( e.currentTarget ).attr( 'data-bp-tooltip', jQuery( e.currentTarget ).attr( 'data-bp-tooltip-show' ) );
					if ( window.forums_medium_forum_editor[ key ].exportSelection() === null ) {
						medium_editor.removeClass( 'medium-editor-toolbar-active' );
					}
				}
				jQuery( window.forums_medium_forum_editor[ key ].elements[ 0 ] ).focus();
				medium_editor.toggleClass( 'active' );
			} );

			if ( jQuery( '.bbp_editor_reply_content' ).length ) {
				jQuery( '.bbp_editor_reply_content' ).each(function(i,element){

					// added support for shortcode in elementor popup.
					if ( jQuery( element ).parents( '.elementor-popup-modal' ).length > 0 ) {
						return;
					}

					var key = jQuery(element).data('key');
					window.forums_medium_reply_editor[key] = new window.MediumEditor(
						element,
						{
							placeholder: {
								text: window.bbpEditorJsStrs.type_reply,
								hideOnClick: true
							},
							// toolbar: toolbarOptions,
							toolbar: Object.assign(toolbarOptions, { relativeContainer: jQuery( element ).closest( '.bbp-reply-form' ).closest( '.bbp-reply-form' ).find( '#whats-new-toolbar' )[0] } ),
							paste: {
								forcePlainText: false,
								cleanPastedHTML: true,
								cleanReplacements: [
									[new RegExp(/<div/gi), '<p'],
									[new RegExp(/<\/div/gi), '</p'],
									[new RegExp(/<h[1-6]/gi), '<b'],
									[new RegExp(/<\/h[1-6]/gi), '</b'],
								],
								cleanAttrs: ['class', 'style', 'dir', 'id'],
								cleanTags: [ 'meta', 'div', 'main', 'section', 'article', 'aside', 'button', 'svg', 'canvas', 'figure', 'input', 'textarea', 'select', 'label', 'form', 'table', 'thead', 'tfooter', 'colgroup', 'col', 'tr', 'td', 'th', 'dl', 'dd', 'center', 'caption', 'nav', 'img' ],
								unwrapTags: []
							},
							imageDragging: false,
							anchor: {
								placeholderText: BP_Nouveau.anchorPlaceholderText,
								linkValidation: true
							}
						}
					);

					window.forums_medium_reply_editor[key].subscribe(
						'editableInput',
						function () {
							var bbp_reply_content = jQuery(element).closest('form').find( '#bbp_reply_content' );
							var html = window.forums_medium_reply_editor[key].getContent();
							var dummy_element = document.createElement( 'div' );
							dummy_element.innerHTML = html;
							jQuery(dummy_element).find( 'span.atwho-query' ).replaceWith(
								function () {
									return this.innerText;
								}
							);

							// transform other emoji into emojionearea emoji.
							jQuery(dummy_element).find( 'img.emoji' ).each(function( index, Obj) {
								jQuery( Obj ).addClass( 'emojioneemoji' );
								var emojis = jQuery( Obj ).attr( 'alt' );
								jQuery( Obj ).attr( 'data-emoji-char', emojis );
								jQuery( Obj ).removeClass( 'emoji' );
							});

							// Transform emoji image into emoji unicode.
							jQuery(dummy_element).find( 'img.emojioneemoji' ).replaceWith(
								function () {
									return this.dataset.emojiChar;
								}
							);
							bbp_reply_content.val( jQuery(dummy_element).html() );

							// Enable submit button if content is available.
							var $reply_content   = jQuery( element ).html();

							$reply_content = jQuery.trim( $reply_content.replace( /<div>/gi, '\n' ).replace( /<\/div>/gi, '' ) );
							$reply_content = $reply_content.replace( /&nbsp;/g, ' ' );

							var content_text = jQuery( '<p>' + $reply_content + '</p>' ).text();
							if ( content_text !== '' || $reply_content.indexOf( 'emojioneemoji' ) >= 0 ) {
								jQuery( element ).closest( 'form' ).addClass( 'has-content' );
							} else {
								jQuery( element ).closest( 'form' ).removeClass( 'has-content' );
							}

							if ( ! _.isUndefined( BP_Nouveau.forums.params.link_preview ) && BP_Nouveau.forums.params.link_preview ) {
								if ( window.forums_medium_reply_editor[key].linkTimeout != null ) {
									clearTimeout( window.forums_medium_reply_editor[key].linkTimeout );
								}

								window.forums_medium_reply_editor[key].linkTimeout = setTimeout(
									function () {
										var form = jQuery(element).closest( 'form' );
										window.forums_medium_reply_editor[key].linkTimeout = null;
										bp.Nouveau.linkPreviews.currentTarget = window.forums_medium_reply_editor[key];
										bp.Nouveau.linkPreviews.scrapURL( bbp_reply_content.val(), form.find( '#whats-new-attachments' ), form.find( '#link_preview_data' ) );
									},
									500
								);
							}
						}
					);

					if ( ! _.isUndefined( BP_Nouveau.forums.params.link_preview ) && BP_Nouveau.forums.params.link_preview ) {
						var bbp_reply_content = jQuery(element).closest('form').find( '#bbp_reply_content' );
						var form = jQuery(element).closest( 'form' );
						bp.Nouveau.linkPreviews.scrapURL( bbp_reply_content.val(), form.find( '#whats-new-attachments' ), form.find( '#link_preview_data' ) );

						var link_preview_input = jQuery( element ).closest( 'form' ).find( '#link_preview_data' );
						if( link_preview_input.length > 0) {
							link_preview_input.on( 'change', function() {
								if( link_preview_input.val() !== '' ) {
									var link_preview_data = JSON.parse( link_preview_input.val() );
									if( link_preview_data && link_preview_data.link_url !== '' ) {
										jQuery( element ).closest( 'form' ).addClass( 'has-link-preview' );
									} else {
										jQuery( element ).closest( 'form' ).removeClass( 'has-link-preview' );
									}
								}
							});
						}
					}

					jQuery('a.bp-suggestions-mention:empty').remove();
					setTimeout(
						function () {
							jQuery('a.bp-suggestions-mention:empty').remove();
						},
						500
					);

				});

				if ( 'undefined' !== typeof bp.Nouveau.TopicReplyDraft ) {
					jQuery( 'form[name="new-post"]' ).each(
						function () {
							var topicReplyDraft = new bp.Nouveau.TopicReplyDraft( jQuery( this ) );
							topicReplyDraft.displayTopicReplyDraft();
						}
					);
				}
			}

			// Add Click event to show / hide text formatting Toolbar for reply form.
			jQuery( document ).on( 'click', '.bbp-reply-form #whats-new-toolbar .show-toolbar', function ( e ) {
				e.preventDefault();
				if( jQuery( this ).closest( '.bbpress-forums-activity.bb-quick-reply-form-wrap' ).length > 0) {
					return;
				}
				var key = jQuery( e.currentTarget ).closest( '.bbp-reply-form' ).find( '.bbp_editor_reply_content' ).data( 'key' );
				var medium_editor = jQuery( e.currentTarget ).closest( '.bbp-form' ).find( '.medium-editor-toolbar' );
				jQuery( e.currentTarget ).find( '.toolbar-button' ).toggleClass( 'active' );
				if ( jQuery( e.currentTarget ).find( '.toolbar-button' ).hasClass( 'active' ) ) {
					jQuery( e.currentTarget ).attr( 'data-bp-tooltip', jQuery( e.currentTarget ).attr( 'data-bp-tooltip-hide' ) );
					if ( window.forums_medium_reply_editor[ key ].exportSelection() !== null ) {
						medium_editor.addClass( 'medium-editor-toolbar-active' );
					}
				} else {
					jQuery( e.currentTarget ).attr( 'data-bp-tooltip', jQuery( e.currentTarget ).attr( 'data-bp-tooltip-show' ) );
					if ( window.forums_medium_reply_editor[ key ].exportSelection() === null ) {
						medium_editor.removeClass( 'medium-editor-toolbar-active' );
					}
				}
				jQuery( window.forums_medium_reply_editor[ key ].elements[ 0 ] ).focus();
				medium_editor.toggleClass( 'active' );
			} );

			if ( jQuery( '.bbp_editor_topic_content' ).length ) {
				jQuery( '.bbp_editor_topic_content' ).each(function(i,element){

					// added support for shortcode in elementor popup.
					if ( jQuery( element ).parents( '.elementor-location-popup' ).length > 0 ) {
						return;
					}

					var key = jQuery(element).data('key');
					window.forums_medium_topic_editor[key] = new window.MediumEditor(
						element,
						{
							placeholder: {
								text: window.bbpEditorJsStrs.type_topic,
								hideOnClick: true
							},
							// toolbar: toolbarOptions,
							toolbar: Object.assign(toolbarOptions, { relativeContainer: jQuery( element ).closest( '.bbp-topic-form ' ).find( '#whats-new-toolbar' )[0] } ),
							paste: {
								forcePlainText: false,
								cleanPastedHTML: true,
								cleanReplacements: [
									[new RegExp(/<div/gi), '<p'],
									[new RegExp(/<\/div/gi), '</p'],
									[new RegExp(/<h[1-6]/gi), '<b'],
									[new RegExp(/<\/h[1-6]/gi), '</b'],
								],
								cleanAttrs: ['class', 'style', 'dir', 'id'],
								cleanTags: [ 'meta', 'div', 'main', 'section', 'article', 'aside', 'button', 'svg', 'canvas', 'figure', 'input', 'textarea', 'select', 'label', 'form', 'table', 'thead', 'tfooter', 'colgroup', 'col', 'tr', 'td', 'th', 'dl', 'dd', 'center', 'caption', 'nav', 'img' ],
								unwrapTags: []
							},
							imageDragging: false,
							anchor: {
								placeholderText: BP_Nouveau.anchorPlaceholderText,
								linkValidation: true
							}
						}
					);

					window.forums_medium_topic_editor[key].subscribe(
						'editableInput',
						function () {
							jQuery(element).closest('form').find( '#bbp_topic_content' ).val( window.forums_medium_topic_editor[key].getContent() );
							var bbp_topic_content = jQuery(element).closest('form').find( '#bbp_topic_content' );

							var html = window.forums_medium_topic_editor[key].getContent();
							var dummy_element = document.createElement( 'div' );
							dummy_element.innerHTML = html;
							jQuery(dummy_element).find( 'span.atwho-query' ).replaceWith(
								function () {
									return this.innerText;
								}
							);

							// transform other emoji into emojionearea emoji.
							jQuery(dummy_element).find( 'img.emoji' ).each(function( index, Obj) {
								jQuery( Obj ).addClass( 'emojioneemoji' );
								var emojis = jQuery( Obj ).attr( 'alt' );
								jQuery( Obj ).attr( 'data-emoji-char', emojis );
								jQuery( Obj ).removeClass( 'emoji' );
							});

							// Transform emoji image into emoji unicode.
							jQuery(dummy_element).find( 'img.emojioneemoji' ).replaceWith(
								function () {
									return this.dataset.emojiChar;
								}
							);
							bbp_topic_content.val( jQuery(dummy_element).html() );

							// Enable submit button if content is available.
							var $reply_content   = jQuery( element ).html();

							$reply_content = jQuery.trim( $reply_content.replace( /<div>/gi, '\n' ).replace( /<\/div>/gi, '' ) );
							$reply_content = $reply_content.replace( /&nbsp;/g, ' ' );

							var content_text = jQuery( '<p>' + $reply_content + '</p>' ).text();
							if ( content_text !== '' || $reply_content.indexOf( 'emojioneemoji' ) >= 0 ) {
								jQuery( element ).closest( 'form' ).addClass( 'has-content' );
							} else {
								jQuery( element ).closest( 'form' ).removeClass( 'has-content' );
							}

							if ( ! _.isUndefined( BP_Nouveau.forums.params.link_preview ) && BP_Nouveau.forums.params.link_preview ) {
								if ( window.forums_medium_topic_editor[key].linkTimeout != null ) {
									clearTimeout( window.forums_medium_topic_editor[key].linkTimeout );
								}

								window.forums_medium_topic_editor[key].linkTimeout = setTimeout(
									function () {
										var form = jQuery(element).closest('form');
										window.forums_medium_topic_editor[key].linkTimeout = null;
										bp.Nouveau.linkPreviews.currentTarget = window.forums_medium_topic_editor[key];
										bp.Nouveau.linkPreviews.scrapURL( bbp_topic_content.val(), form.find( '#whats-new-attachments' ), form.find( '#link_preview_data' ) );
									},
									500
								);
							}
						}
					);

					if ( ! _.isUndefined( BP_Nouveau.forums.params.link_preview ) && BP_Nouveau.forums.params.link_preview ) {
						var bbp_topic_content = jQuery(element).closest('form').find( '#bbp_topic_content' );
						var form = jQuery(element).closest( 'form' );
						bp.Nouveau.linkPreviews.scrapURL( bbp_topic_content.val(), form.find( '#whats-new-attachments' ), form.find( '#link_preview_data' ) );

						var link_preview_input = jQuery( element ).closest( 'form' ).find( '#link_preview_data' );
						if( link_preview_input.length > 0) {
							link_preview_input.on( 'change', function() {
								if( link_preview_input.val() !== '' ) {
									var link_preview_data = JSON.parse( link_preview_input.val() );
									if( link_preview_data && link_preview_data.link_url !== '' ) {
										jQuery( element ).closest( 'form' ).addClass( 'has-link-preview' );
									} else {
										jQuery( element ).closest( 'form' ).removeClass( 'has-link-preview' );
									}
								}
							});
						}
					}

					jQuery('a.bp-suggestions-mention:empty').remove();
					setTimeout(
						function () {
							jQuery('a.bp-suggestions-mention:empty').remove();
						},
						500
					);

				});

				if ( 'undefined' !== typeof bp.Nouveau.TopicReplyDraft ) {
					jQuery( 'form[name="new-post"]' ).each(
						function () {
							var topicReplyDraft = new bp.Nouveau.TopicReplyDraft( jQuery( this ) );
							topicReplyDraft.displayTopicReplyDraft();
						}
					);
				}
			}

			// Add Click event to show / hide text formatting Toolbar for topic form.
			jQuery( document ).on( 'click', '.bbp-topic-form #whats-new-toolbar .show-toolbar', function ( e ) {
				e.preventDefault();
				var key = jQuery( e.currentTarget ).closest( '.bbp-topic-form' ).find( '.bbp_editor_topic_content' ).data( 'key' );
				var medium_editor = jQuery( e.currentTarget ).closest( '.bbp-form' ).find( '.medium-editor-toolbar' );
				jQuery( e.currentTarget ).find( '.toolbar-button' ).toggleClass( 'active' );
				if ( jQuery( e.currentTarget ).find( '.toolbar-button' ).hasClass( 'active' ) ) {
					jQuery( e.currentTarget ).attr( 'data-bp-tooltip', jQuery( e.currentTarget ).attr( 'data-bp-tooltip-hide' ) );
					if ( window.forums_medium_topic_editor[ key ].exportSelection() !== null ) {
						medium_editor.addClass( 'medium-editor-toolbar-active' );
					}
				} else {
					jQuery( e.currentTarget ).attr( 'data-bp-tooltip', jQuery( e.currentTarget ).attr( 'data-bp-tooltip-show' ) );
					if ( window.forums_medium_topic_editor[ key ].exportSelection() === null ) {
						medium_editor.removeClass( 'medium-editor-toolbar-active' );
					}
				}
				jQuery( window.forums_medium_topic_editor[ key ].elements[ 0 ] ).focus();
				medium_editor.toggleClass( 'active' );
			} );

			jQuery( document ).on ( 'keyup', '#bbpress-forums .medium-editor-toolbar-input', function( event ) {

				var URL = event.target.value;

				if ( bp.Nouveau.isURL( URL ) ) {
					jQuery( event.target ).removeClass('isNotValid').addClass('isValid');
				} else {
					jQuery( event.target ).removeClass('isValid').addClass('isNotValid');
				}

			});
		}

		/* Use backticks instead of <code> for the Code button in the editor */
		if ( typeof( edButtons ) !== 'undefined' ) {
			/*globals edButtons:false */
			edButtons[110] = new QTags.TagButton( 'code', 'code', '`', '`', 'c' );
			/*globals QTags:false */
			QTags._buttonsInit();
		}

		/* Tab from topic title */
		jQuery( '#bbp_topic_title' ).bind(
			'keydown.editor-focus',
			function(e) {
				if ( e.which !== 9 ) {
					return;
				}

				if ( ! e.ctrlKey && ! e.altKey && ! e.shiftKey ) {
					if ( typeof( tinymce ) !== 'undefined' ) {
						/*globals tinymce:false */
						if ( ! tinymce.activeEditor.isHidden() ) {
							var editor = tinymce.activeEditor.editorContainer;
							jQuery( '#' + editor + ' td.mceToolbar > a' ).focus();
						} else {
							jQuery( 'textarea.bbp-the-content' ).focus();
						}
					} else {
						jQuery( 'textarea.bbp-the-content' ).focus();
					}

					e.preventDefault();
				}
			}
		);

		/* Shift + tab from topic tags */
		jQuery( '#bbp_topic_tags' ).bind(
			'keydown.editor-focus',
			function(e) {
				if ( e.which !== 9 ) {
					return;
				}

				if ( e.shiftKey && ! e.ctrlKey && ! e.altKey ) {
					if ( typeof( tinymce ) !== 'undefined' ) {
						if ( ! tinymce.activeEditor.isHidden() ) {
							var editor = tinymce.activeEditor.editorContainer;
							jQuery( '#' + editor + ' td.mceToolbar > a' ).focus();
						} else {
							jQuery( 'textarea.bbp-the-content' ).focus();
						}
					} else {
						jQuery( 'textarea.bbp-the-content' ).focus();
					}

					e.preventDefault();
				}
			}
		);

		if ( window.elementorFrontend ) {
			jQuery( document ).on( 'elementor/popup/show', function () {
				if ( typeof window.MediumEditor !== 'undefined' ) {
					var toolbarOptions = {
						buttons: [ 'bold', 'italic', 'unorderedlist', 'orderedlist', 'quote', 'anchor', 'pre' ],
						relativeContainer: document.getElementById( 'whats-new-toolbar' ),
						static: true,
						updateOnEmptySelection: true
					};
					if ( jQuery( '.bbp_editor_forum_content' ).length ) {

						jQuery( '.bbp_editor_forum_content' ).each(function(i,element){

							// added support for shortcode in elementor popup.
							if ( jQuery( element ).parents( '.elementor-location-popup' ).length < 1 ) {
								return;
							}

							var key = jQuery(element).data('key');
							window.forums_medium_forum_editor[key] = new window.MediumEditor(
								element,
								{
									placeholder: {
										text: window.bbpEditorJsStrs.description,
										hideOnClick: true
									},
									// toolbar: toolbarOptions,
									toolbar: Object.assign(toolbarOptions, { relativeContainer: jQuery( element ).closest( '.bbp-forum-form' ).find( '#whats-new-toolbar' )[0] } ),
									paste: {
										forcePlainText: false,
										cleanPastedHTML: true,
										cleanReplacements: [
											[new RegExp(/<div/gi), '<p'],
											[new RegExp(/<\/div/gi), '</p'],
											[new RegExp(/<h[1-6]/gi), '<b'],
											[new RegExp(/<\/h[1-6]/gi), '</b'],
										],
										cleanAttrs: ['class', 'style', 'dir', 'id'],
										cleanTags: [ 'meta', 'div', 'main', 'section', 'article', 'aside', 'button', 'svg', 'canvas', 'figure', 'input', 'textarea', 'select', 'label', 'form', 'table', 'thead', 'tfooter', 'colgroup', 'col', 'tr', 'td', 'th', 'dl', 'dd', 'center', 'caption', 'nav', 'img' ],
										unwrapTags: []
									},
									imageDragging: false,
									anchor: {
										placeholderText: BP_Nouveau.anchorPlaceholderText,
										linkValidation: true
									}
								}
							);

							window.forums_medium_forum_editor[key].subscribe(
								'editableInput',
								function () {
									var bbp_forum_content = jQuery(element).closest('form').find( '#bbp_forum_content' );
									bbp_forum_content.val( window.forums_medium_forum_editor[key].getContent() );
									var atwho_query = bbp_forum_content.find( 'span.atwho-query' );
									for( var i = 0; i < atwho_query.length; i++ ) {
										jQuery(atwho_query[i]).replaceWith( atwho_query[i].innerText );
									}

									// Enable submit button if content is available.
									var $reply_content   = jQuery( element ).html();

									$reply_content = jQuery.trim( $reply_content.replace( /<div>/gi, '\n' ).replace( /<\/div>/gi, '' ) );
									$reply_content = $reply_content.replace( /&nbsp;/g, ' ' );

									var content_text = jQuery( '<p>' + $reply_content + '</p>' ).text();
									if ( content_text !== '' || $reply_content.indexOf( 'emojioneemoji' ) >= 0 ) {
										jQuery( element ).closest( 'form' ).addClass( 'has-content' );
									} else {
										jQuery( element ).closest( 'form' ).removeClass( 'has-content' );
									}

									if ( ! _.isUndefined( BP_Nouveau.forums.params.link_preview ) && BP_Nouveau.forums.params.link_preview ) {
										if ( window.forums_medium_forum_editor[key].linkTimeout != null ) {
											clearTimeout( window.forums_medium_forum_editor[key].linkTimeout );
										}

										window.forums_medium_forum_editor[key].linkTimeout = setTimeout(
											function () {
												var form = jQuery(element).closest('form');
												window.forums_medium_forum_editor[key].linkTimeout = null;
												bp.Nouveau.linkPreviews.currentTarget = window.forums_medium_forum_editor[key];
												bp.Nouveau.linkPreviews.scrapURL( bbp_forum_content.val(), form.find( '#whats-new-attachments' ), form.find( '#link_preview_data' ) );
											},
											500
										);
									}
								}
							);

							if ( ! _.isUndefined( BP_Nouveau.forums.params.link_preview ) && BP_Nouveau.forums.params.link_preview ) {
								var bbp_forum_content = jQuery(element).closest('form').find( '#bbp_forum_content' );
								var form = jQuery(element).closest( 'form' );
								bp.Nouveau.linkPreviews.scrapURL( bbp_forum_content.val(), form.find( '#whats-new-attachments' ), form.find( '#link_preview_data' ) );

								var link_preview_input = jQuery( element ).closest( 'form' ).find( '#link_preview_data' );
								if( link_preview_input.length > 0) {
									link_preview_input.on( 'change', function() {
										if( link_preview_input.val() !== '' ) {
											var link_preview_data = JSON.parse( link_preview_input.val() );
											if( link_preview_data && link_preview_data.link_url !== '' ) {
												jQuery( element ).closest( 'form' ).addClass( 'has-link-preview' );
											} else {
												jQuery( element ).closest( 'form' ).removeClass( 'has-link-preview' );
											}
										}
									});
								}
							}

						});
					}
					if ( jQuery( '.bbp_editor_reply_content' ).length ) {

						jQuery( '.bbp_editor_reply_content' ).each(function(i,element){

							// added support for shortcode in elementor popup.
							if ( jQuery( element ).parents( '.elementor-location-popup' ).length < 1 ) {
								return;
							}

							var key = jQuery(element).data('key');
							window.forums_medium_reply_editor[key] = new window.MediumEditor(
								element,
								{
									placeholder: {
										text: window.bbpEditorJsStrs.type_reply,
										hideOnClick: true
									},
									// toolbar: toolbarOptions,
									toolbar: Object.assign(toolbarOptions, { relativeContainer: jQuery( element ).closest( '.bbp-reply-form' ).find( '#whats-new-toolbar' )[0] } ),
									paste: {
										forcePlainText: false,
										cleanPastedHTML: true,
										cleanReplacements: [
											[new RegExp(/<div/gi), '<p'],
											[new RegExp(/<\/div/gi), '</p'],
											[new RegExp(/<h[1-6]/gi), '<b'],
											[new RegExp(/<\/h[1-6]/gi), '</b'],
										],
										cleanAttrs: ['class', 'style', 'dir', 'id'],
										cleanTags: [ 'meta', 'div', 'main', 'section', 'article', 'aside', 'button', 'svg', 'canvas', 'figure', 'input', 'textarea', 'select', 'label', 'form', 'table', 'thead', 'tfooter', 'colgroup', 'col', 'tr', 'td', 'th', 'dl', 'dd', 'center', 'caption', 'nav', 'img' ],
										unwrapTags: []
									},
									imageDragging: false,
									anchor: {
										placeholderText: BP_Nouveau.anchorPlaceholderText,
										linkValidation: true
									}
								}
							);

							window.forums_medium_reply_editor[key].subscribe(
								'editableInput',
								function () {
									var bbp_reply_content = jQuery(element).closest('form').find( '#bbp_reply_content' );
									bbp_reply_content.val( window.forums_medium_reply_editor[key].getContent() );
									var atwho_query = bbp_reply_content.find( 'span.atwho-query' );
									for( var i = 0; i < atwho_query.length; i++ ) {
										jQuery(atwho_query[i]).replaceWith( atwho_query[i].innerText );
									}

									// Enable submit button if content is available.
									var $reply_content   = jQuery( element ).html();

									$reply_content = jQuery.trim( $reply_content.replace( /<div>/gi, '\n' ).replace( /<\/div>/gi, '' ) );
									$reply_content = $reply_content.replace( /&nbsp;/g, ' ' );

									var content_text = jQuery( '<p>' + $reply_content + '</p>' ).text();
									if ( content_text !== '' || $reply_content.indexOf( 'emojioneemoji' ) >= 0 ) {
										jQuery( element ).closest( 'form' ).addClass( 'has-content' );
									} else {
										jQuery( element ).closest( 'form' ).removeClass( 'has-content' );
									}

									if ( ! _.isUndefined( BP_Nouveau.forums.params.link_preview ) && BP_Nouveau.forums.params.link_preview ) {
										if ( window.forums_medium_reply_editor[key].linkTimeout != null ) {
											clearTimeout( window.forums_medium_reply_editor[key].linkTimeout );
										}

										window.forums_medium_reply_editor[key].linkTimeout = setTimeout(
											function () {
												var form = jQuery(element).closest( 'form' );
												window.forums_medium_reply_editor[key].linkTimeout = null;
												bp.Nouveau.linkPreviews.currentTarget = window.forums_medium_reply_editor[key];
												bp.Nouveau.linkPreviews.scrapURL( bbp_reply_content.val(), form.find( '#whats-new-attachments' ), form.find( '#link_preview_data' ) );
											},
											500
										);
									}
								}
							);

							if ( ! _.isUndefined( BP_Nouveau.forums.params.link_preview ) && BP_Nouveau.forums.params.link_preview ) {
								var bbp_reply_content = jQuery(element).closest('form').find( '#bbp_reply_content' );
								var form = jQuery(element).closest( 'form' );
								bp.Nouveau.linkPreviews.scrapURL( bbp_reply_content.val(), form.find( '#whats-new-attachments' ), form.find( '#link_preview_data' ) );

								var link_preview_input = jQuery( element ).closest( 'form' ).find( '#link_preview_data' );
								if( link_preview_input.length > 0) {
									link_preview_input.on( 'change', function() {
										if( link_preview_input.val() !== '' ) {
											var link_preview_data = JSON.parse( link_preview_input.val() );
											if( link_preview_data && link_preview_data.link_url !== '' ) {
												jQuery( element ).closest( 'form' ).addClass( 'has-link-preview' );
											} else {
												jQuery( element ).closest( 'form' ).removeClass( 'has-link-preview' );
											}
										}
									});
								}
							}
						});
					}
					if ( jQuery( '.bbp_editor_topic_content' ).length ) {

						jQuery( '.bbp_editor_topic_content' ).each(function(i,element){

							// added support for shortcode in elementor popup.
							if ( jQuery( element ).parents( '.elementor-location-popup' ).length < 1 ) {
								return;
							}

							var key = jQuery(element).data('key');
							window.forums_medium_topic_editor[key] = new window.MediumEditor(
								element,
								{
									placeholder: {
										text: window.bbpEditorJsStrs.type_topic,
										hideOnClick: true
									},
									// toolbar: toolbarOptions,
									toolbar: Object.assign(toolbarOptions, { relativeContainer: jQuery( element ).closest( '.bbp-topic-form' ).find( '#whats-new-toolbar' )[0] } ),
									paste: {
										forcePlainText: false,
										cleanPastedHTML: true,
										cleanReplacements: [
											[new RegExp(/<div/gi), '<p'],
											[new RegExp(/<\/div/gi), '</p'],
											[new RegExp(/<h[1-6]/gi), '<b'],
											[new RegExp(/<\/h[1-6]/gi), '</b'],
										],
										cleanAttrs: ['class', 'style', 'dir', 'id'],
										cleanTags: [ 'meta', 'div', 'main', 'section', 'article', 'aside', 'button', 'svg', 'canvas', 'figure', 'input', 'textarea', 'select', 'label', 'form', 'table', 'thead', 'tfooter', 'colgroup', 'col', 'tr', 'td', 'th', 'dl', 'dd', 'center', 'caption', 'nav', 'img' ],
										unwrapTags: []
									},
									imageDragging: false,
									anchor: {
										placeholderText: BP_Nouveau.anchorPlaceholderText,
										linkValidation: true
									}
								}
							);

							window.forums_medium_topic_editor[key].subscribe(
								'editableInput',
								function () {
									jQuery(element).closest('form').find( '#bbp_topic_content' ).val( window.forums_medium_topic_editor[key].getContent() );
									var bbp_topic_content = jQuery(element).closest('form').find( '#bbp_topic_content' );
									bbp_topic_content.val( window.forums_medium_topic_editor[key].getContent() );
									var atwho_query = bbp_topic_content.find( 'span.atwho-query' );
									for( var i = 0; i < atwho_query.length; i++ ) {
										jQuery(atwho_query[i]).replaceWith( atwho_query[i].innerText );
									}

									// Enable submit button if content is available.
									var $reply_content   = jQuery( element ).html();

									$reply_content = jQuery.trim( $reply_content.replace( /<div>/gi, '\n' ).replace( /<\/div>/gi, '' ) );
									$reply_content = $reply_content.replace( /&nbsp;/g, ' ' );

									var content_text = jQuery( '<p>' + $reply_content + '</p>' ).text();
									if ( content_text !== '' || $reply_content.indexOf( 'emojioneemoji' ) >= 0 ) {
										jQuery( element ).closest( 'form' ).addClass( 'has-content' );
									} else {
										jQuery( element ).closest( 'form' ).removeClass( 'has-content' );
									}

									if ( ! _.isUndefined( BP_Nouveau.forums.params.link_preview ) && BP_Nouveau.forums.params.link_preview ) {
										if ( window.forums_medium_topic_editor[key].linkTimeout != null ) {
											clearTimeout( window.forums_medium_topic_editor[key].linkTimeout );
										}

										window.forums_medium_topic_editor[key].linkTimeout = setTimeout(
											function () {
												var form = jQuery(element).closest( 'form' );
												window.forums_medium_topic_editor[key].linkTimeout = null;
												bp.Nouveau.linkPreviews.currentTarget = window.forums_medium_topic_editor[key];
												bp.Nouveau.linkPreviews.scrapURL( bbp_topic_content.val(), form.find( '#whats-new-attachments' ), form.find( '#link_preview_data' ) );
											},
											500
										);
									}
								}
							);

							if ( ! _.isUndefined( BP_Nouveau.forums.params.link_preview ) && BP_Nouveau.forums.params.link_preview ) {
								var bbp_topic_content = jQuery(element).closest('form').find( '#bbp_topic_content' );
								var form = jQuery(element).closest( 'form' );
								bp.Nouveau.linkPreviews.scrapURL( bbp_topic_content.val(), form.find( '#whats-new-attachments' ), form.find( '#link_preview_data' ) );

								var link_preview_input = jQuery( element ).closest( 'form' ).find( '#link_preview_data' );
								if( link_preview_input.length > 0) {
									link_preview_input.on( 'change', function() {
										if( link_preview_input.val() !== '' ) {
											var link_preview_data = JSON.parse( link_preview_input.val() );
											if( link_preview_data && link_preview_data.link_url !== '' ) {
												jQuery( element ).closest( 'form' ).addClass( 'has-link-preview' );
											} else {
												jQuery( element ).closest( 'form' ).removeClass( 'has-link-preview' );
											}
										}
									});
								}
							}
						});
					}
				}
			} );
		}
	}
);
