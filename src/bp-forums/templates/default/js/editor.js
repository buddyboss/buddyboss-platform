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

							if ( ! _.isUndefined( BP_Nouveau.activity.params.link_preview ) ) {
								if ( window.forums_medium_reply_editor[key].linkTimeout != null ) {
									clearTimeout( window.forums_medium_reply_editor[key].linkTimeout );
								}
	
								window.forums_medium_reply_editor[key].linkTimeout = setTimeout(
									function () {
										window.forums_medium_reply_editor[key].linkTimeout = null;
										linkPreviews.scrapURL( bbp_reply_content.val() );
									},
									500
								);
							}
						}
					);

				});
			}

			// Add Click event to show / hide text formatting Toolbar for reply form.
			jQuery( document ).on( 'click', '.bbp-reply-form #whats-new-toolbar .show-toolbar', function ( e ) {
				e.preventDefault();
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
						}
					);

				});
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
								}
							);
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
								}
							);
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
								}
							);
						});
					}
				}
			} );
		}
	}
);

const linkPreviews = {
	loadedURLs: [],
	loadURLAjax: null,
	options: new Map(),
	scrapURL: function ( urlText ) {
		var urlString = '';

		if ( urlText === null ) {
			return;
		}

		//Remove mentioned members Link
		var tempNode = $( '<div></div>' ).html( urlText );
		tempNode.find( 'a.bp-suggestions-mention' ).remove();
		urlText = tempNode.html();

		if ( urlText.indexOf( '<img' ) >= 0 ) {
			urlText = urlText.replace( /<img .*?>/g, '' );
		}

		if ( urlText.indexOf( 'http://' ) >= 0 ) {
			urlString = this.getURL( 'http://', urlText );
		} else if ( urlText.indexOf( 'https://' ) >= 0 ) {
			urlString = this.getURL( 'https://', urlText );
		} else if ( urlText.indexOf( 'www.' ) >= 0 ) {
			urlString = this.getURL( 'www', urlText );
		}

		if ( urlString !== '' ) {
			// check if the url of any of the excluded video oembeds.
			var url_a    = document.createElement( 'a' );
			url_a.href   = urlString;
			var hostname = url_a.hostname;
			if ( BP_Nouveau.activity.params.excluded_hosts.indexOf( hostname ) !== -1 ) {
				urlString = '';
			}
		}

		if ( '' !== urlString ) {
			this.loadURLPreview( urlString );
		}
	},

	getURL: function ( prefix, urlText ) {
		var urlString   = '';
		urlText         = urlText.replace( /&nbsp;/g, '' );
		var startIndex  = urlText.indexOf( prefix );
		var responseUrl = '';

		if ( ! _.isUndefined( $( $.parseHTML( urlText ) ).attr( 'href' ) ) ) {
			urlString = $( urlText ).attr( 'href' );
		} else {
			for ( var i = startIndex; i < urlText.length; i++ ) {
				if (
					urlText[ i ] === ' ' ||
					urlText[ i ] === '\n' ||
					( urlText[ i ] === '"' && urlText[ i + 1 ] === '>' ) ||
					( urlText[ i ] === '<' && urlText[ i + 1 ] === 'b' && urlText[ i + 2 ] === 'r' )
				) {
					break;
				} else {
					urlString += urlText[ i ];
				}
			}
			if ( prefix === 'www' ) {
				prefix    = 'http://';
				urlString = prefix + urlString;
			}
		}

		var div       = document.createElement( 'div' );
		div.innerHTML = urlString;
		var elements  = div.getElementsByTagName( '*' );

		while ( elements[ 0 ] ) {
			elements[ 0 ].parentNode.removeChild( elements[ 0 ] );
		}

		if ( div.innerHTML.length > 0 ) {
			responseUrl = div.innerHTML;
		}

		return responseUrl;
	},

	loadURLPreview: function ( url ) {
		var self = this;

		var regexp = /^(http:\/\/www\.|https:\/\/www\.|http:\/\/|https:\/\/)?[a-z0-9]+([\-\.]{1}[a-z0-9]+)*\.[a-z]{2,24}(:[0-9]{1,5})?(\/.*)?$/;
		url        = $.trim( url );
		if ( regexp.test( url ) ) {
			if ( ( ! _.isUndefined( self.options.get( 'link_success' ) ) && self.options.get( 'link_success' ) == true ) && self.options.get( 'link_url' ) === url ) {
				return false;
			}

			if ( url.includes( window.location.hostname ) && ( url.includes( 'download_document_file' ) || url.includes( 'download_media_file' ) || url.includes( 'download_video_file' ) ) ) {
				return false;
			}

			var urlResponse = false;
			if ( self.loadedURLs.length ) {
				$.each(
					self.loadedURLs,
					function ( index, urlObj ) {
						if ( urlObj.url == url ) {
							urlResponse = urlObj.response;
							return false;
						}
					}
				);
			}

			if ( self.loadURLAjax != null ) {
				self.loadURLAjax.abort();
			}

			self.options.set(
				{
					link_scrapping: true,
					link_loading: true,
					link_error: false,
					link_url: url,
					link_embed: false
				}
			);

			if ( ! urlResponse ) {
				self.loadURLAjax = $.post(
					ajaxurl,
					{
					  action: 'bp_activity_parse_url',
					  url: url
					},
					function( response ) {
					  // success callback
					  self.setURLResponse(response, url);
					}
				  ).always(function() {
					// always callback
				});
			} else {
				self.setURLResponse( urlResponse, url );
			}
		}
	},

	setURLResponse: function ( response, url ) {
		var self = this;

		self.options.set( 'link_loading', false );

		if ( response.title === '' && response.images === '' ) {
			self.options.set( 'link_scrapping', false );
			return;
		}

		if ( response.error === '' ) {
			var urlImages = response.images;
			if (
				true === self.options.get( 'edit_activity' ) && 'undefined' === typeof self.options.get( 'link_image_index_save' ) && '' === self.options.get( 'link_image_index_save' )
			) {
				urlImages = '';
			}
			var urlImagesIndex = '';
			if ( '' !== self.options.get( 'link_image_index' ) ) {
				urlImagesIndex =  parseInt( self.options.get( 'link_image_index' ) );
			}
			self.options.set(
				{
					link_success: true,
					link_title: response.title,
					link_description: response.description,
					link_images: urlImages,
					link_image_index: urlImagesIndex,
					link_image_index_save: self.options.get( 'link_image_index_save' ),
					link_embed: ! _.isUndefined( response.wp_embed ) && response.wp_embed
				}
			);

			self.loadedURLs.push( { 'url': url, 'response': response } );

		} else {
			self.options.set(
				{
					link_success: false,
					link_error: true,
					link_error_msg: response.error
				}
			);
		}
	},
};
	
