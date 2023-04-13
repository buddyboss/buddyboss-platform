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

							// if ( ! _.isUndefined( BP_Nouveau.activity.params.link_preview ) ) {
							// 	if ( window.forums_medium_forum_editor[key].linkTimeout != null ) {
							// 		clearTimeout( window.forums_medium_forum_editor[key].linkTimeout );
							// 	}
	
							// 	window.forums_medium_forum_editor[key].linkTimeout = setTimeout(
							// 		function () {
							// 			window.forums_medium_forum_editor[key].linkTimeout = null;
							// 			linkPreviews.currentTarget = window.forums_medium_forum_editor[key];
							// 			linkPreviews.scrapURL( bbp_forum_content.val() );
							// 		},
							// 		500
							// 	);
							// }
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
										linkPreviews.currentTarget = window.forums_medium_reply_editor[key];
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

							if ( ! _.isUndefined( BP_Nouveau.activity.params.link_preview ) ) {
								if ( window.forums_medium_topic_editor[key].linkTimeout != null ) {
									clearTimeout( window.forums_medium_topic_editor[key].linkTimeout );
								}
	
								window.forums_medium_topic_editor[key].linkTimeout = setTimeout(
									function () {
										window.forums_medium_topic_editor[key].linkTimeout = null;
										linkPreviews.currentTarget = window.forums_medium_topic_editor[key];
										linkPreviews.scrapURL( bbp_topic_content.val() );
									},
									500
								);
							}
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
	currentTarget: null,
	currentTargetForm: null,
	loadedURLs: [],
	loadURLAjax: null,
	options: {},
	scrapURL: function ( urlText ) {
		var urlString = '';

		if ( urlText === null ) {
			return;
		}

		//Remove mentioned members Link
		var tempNode = jQuery( '<div></div>' ).html( urlText );
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

		if ( ! _.isUndefined( jQuery( $.parseHTML( urlText ) ).attr( 'href' ) ) ) {
			urlString = jQuery( urlText ).attr( 'href' );
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
			if ( ( ! _.isUndefined( self.options[ 'link_success' ] ) && self.options[ 'link_success' ] == true ) && self.options[ 'link_url' ] === url ) {
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

			Object.assign( self.options, {
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
					  action: 'bb_forums_parse_url',
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

		self.options['link_loading'] = false;

		if ( response.title === '' && response.images === '' ) {
			self.options['link_scrapping'] = false;
			return;
		}

		if ( response.error === '' ) {
			var urlImages = response.images;
			// if (
			// 	true === self.options[ 'edit_activity' ] && 'undefined' === typeof self.options[ 'link_image_index_save' ] && '' === self.options[ 'link_image_index_save' ]
			// ) {
			// 	urlImages = '';
			// }
			var urlImagesIndex = '';
			if ( '' !== self.options[ 'link_image_index' ] ) {
				urlImagesIndex =  parseInt( self.options[ 'link_image_index' ] );
			}
			Object.assign( self.options, {
					link_success: true,
					link_title: response.title,
					link_description: response.description,
					link_images: urlImages,
					link_image_index: urlImagesIndex,
					link_image_index_save: 0,
					link_embed: ! _.isUndefined( response.wp_embed ) && response.wp_embed
				}
			);

			self.loadedURLs.push( { 'url': url, 'response': response } );

			// Set form values for link preview.
			self.currentTargetForm = jQuery( self.currentTarget.elements[0] ).closest( 'form' );
			if ( jQuery( self.currentTargetForm ).find('#link_url').length > 0 ) {
				jQuery( self.currentTargetForm ).find('#link_url').val( url );
				jQuery( self.currentTargetForm ).find('#link_title').val( response.title );
				jQuery( self.currentTargetForm ).find('#link_description').val( response.description );
				jQuery( self.currentTargetForm ).find('#link_embed').val( self.options.link_embed );
				jQuery( self.currentTargetForm ).find('#link_image').val( urlImages[ self.options.link_image_index_save ] );
				jQuery( self.currentTargetForm ).find('#link_image_index_save').val( self.options.link_image_index_save );
			}
			

		} else {
			Object.assign( self.options, {
					link_success: false,
					link_error: true,
					link_error_msg: response.error
				}
			);
		}
	},

	// postUpdate: function ( event ) {
	// 	var self = this,
	// 		meta = {}, edit = false;

	// 	if ( event ) {
	// 		if ( 'keydown' === event.type && ( 13 !== event.keyCode || ! event.ctrlKey ) ) {
	// 			return event;
	// 		}

	// 		event.preventDefault();
	// 	}

	// 	// unset all errors before submit.
	// 	self.model.unset( 'errors' );

	// 	// Set the content and meta.
	// 	_.each(
	// 		self.$el.serializeArray(),
	// 		function ( pair ) {
	// 			pair.name = pair.name.replace( '[]', '' );
	// 			if ( -1 === _.indexOf( [ 'aw-whats-new-submit', 'whats-new-post-in' ], pair.name ) ) {
	// 				if ( _.isUndefined( meta[ pair.name ] ) ) {
	// 					meta[ pair.name ] = pair.value;
	// 				} else {
	// 					if ( ! _.isArray( meta[pair.name] ) ) {
	// 						meta[pair.name] = [ meta[pair.name] ];
	// 					}

	// 					meta[ pair.name ].push( pair.value );
	// 				}
	// 			}
	// 		}
	// 	);

	// 	// Post content.
	// 	var $whatsNew = self.$el.find( '#whats-new' );

	// 	var atwho_query = $whatsNew.find( 'span.atwho-query' );
	// 	for ( var i = 0; i < atwho_query.length; i++ ) {
	// 		jQuery( atwho_query[ i ] ).replaceWith( atwho_query[ i ].innerText );
	// 	}

	// 	// transform other emoji into emojionearea emoji.
	// 	$whatsNew.find( 'img.emoji' ).each(
	// 		function( index, Obj) {
	// 			jQuery( Obj ).addClass( 'emojioneemoji' );
	// 			var emojis = jQuery( Obj ).attr( 'alt' );
	// 			jQuery( Obj ).attr( 'data-emoji-char', emojis );
	// 			jQuery( Obj ).removeClass( 'emoji' );
	// 		}
	// 	);

	// 	// Add valid line breaks.
	// 	var content = $.trim( $whatsNew[0].innerHTML.replace( /<div>/gi, '\n' ).replace( /<\/div>/gi, '' ) );
	// 	content     = content.replace( /&nbsp;/g, ' ' );

	// 	self.model.set( 'content', content, { silent: true } );

	// 	// Silently add meta.
	// 	self.model.set( meta, { silent: true } );

	// 	var medias = self.model.get( 'media' );
	// 	if ( 'group' == self.model.get( 'object' ) && ! _.isUndefined( medias ) && medias.length ) {
	// 		for ( var k = 0; k < medias.length; k++ ) {
	// 			medias[ k ].group_id = self.model.get( 'item_id' );
	// 		}
	// 		self.model.set( 'media', medias );
	// 	}

	// 	var document = self.model.get( 'document' );
	// 	if ( 'group' == self.model.get( 'object' ) && ! _.isUndefined( document ) && document.length ) {
	// 		for ( var d = 0; d < document.length; d++ ) {
	// 			document[ d ].group_id = self.model.get( 'item_id' );
	// 		}
	// 		self.model.set( 'document', document );
	// 	}

	// 	var video = self.model.get( 'video' );
	// 	if ( 'group' == self.model.get( 'object' ) && ! _.isUndefined( video ) && video.length ) {
	// 		for ( var v = 0; v < video.length; v++ ) {
	// 			video[ v ].group_id = self.model.get( 'item_id' );
	// 		}
	// 		self.model.set( 'video', video );
	// 	}

	// 	// validation for content editor.
	// 	if ( jQuery( $.parseHTML( content ) ).text().trim() === '' && ( ! _.isUndefined( this.model.get( 'link_success' ) ) && true !== this.model.get( 'link_success' ) ) && ( ( ! _.isUndefined( self.model.get( 'video' ) ) && ! self.model.get( 'video' ).length ) && ( ! _.isUndefined( self.model.get( 'document' ) ) && ! self.model.get( 'document' ).length ) && ( ! _.isUndefined( self.model.get( 'media' ) ) && ! self.model.get( 'media' ).length ) && ( ! _.isUndefined( self.model.get( 'gif_data' ) ) && ! Object.keys( self.model.get( 'gif_data' ) ).length ) ) ) {
	// 		self.model.set(
	// 			'errors',
	// 			{
	// 				type: 'error',
	// 				value: BP_Nouveau.activity.params.errors.empty_post_update
	// 			}
	// 		);
	// 		return false;
	// 	}

	// 	// update posting status true.
	// 	self.model.set( 'posting', true );

	// 	var data = {
	// 		'_wpnonce_post_update': BP_Nouveau.activity.params.post_nonce
	// 	};

	// 	// Add the Akismet nonce if it exists.
	// 	if ( jQuery( '#_bp_as_nonce' ).val() ) {
	// 		data._bp_as_nonce = jQuery( '#_bp_as_nonce' ).val();
	// 	}

	// 	// Remove all unused model attribute.
	// 	data = _.omit(
	// 		_.extend( data, this.model.attributes ),
	// 		[
	// 			'link_images',
	// 			'link_image_index',
	// 			'link_image_index_save',
	// 			'link_success',
	// 			'link_error',
	// 			'link_error_msg',
	// 			'link_scrapping',
	// 			'link_loading',
	// 			'posting'
	// 		]
	// 	);

	// 	// Form link preview data to pass in request if available.
	// 	if ( self.model.get( 'link_success' ) ) {
	// 		var images = self.model.get( 'link_images' ),
	// 			index  = self.model.get( 'link_image_index' ),
	// 			indexConfirm  = self.model.get( 'link_image_index_save' );
	// 		if ( images && images.length ) {
	// 			data = _.extend(
	// 				data,
	// 				{
	// 					'link_image': images[ indexConfirm ],
	// 					'link_image_index': index,
	// 					'link_image_index_save' : indexConfirm
	// 				}
	// 			);
	// 		}

	// 	} else {
	// 		data = _.omit(
	// 			data,
	// 			[
	// 				'link_title',
	// 				'link_description',
	// 				'link_url'
	// 			]
	// 		);
	// 	}

	// 	// check if edit activity.
	// 	if ( self.model.get( 'id' ) > 0 ) {
	// 		edit      = true;
	// 		data.edit = 1;

	// 		if ( ! bp.privacyEditable ) {
	// 			data.privacy = bp.privacy;
	// 		}
	// 	}

	// 	bp.ajax.post( 'post_update', data ).done(
	// 		function ( response ) {

	// 			// check if edit activity then scroll up 1px so image will load automatically.
	// 			if ( self.model.get( 'id' ) > 0 ) {
	// 				jQuery( 'html, body' ).animate(
	// 					{
	// 						scrollTop: jQuery( window ).scrollTop() + 1
	// 					}
	// 				);
	// 			}

	// 			// At first, hide the modal.
	// 			bp.Nouveau.Activity.postForm.postActivityEditHideModal();

	// 			var store       = bp.Nouveau.getStorage( 'bp-activity' ),
	// 				searchTerms = jQuery( '[data-bp-search="activity"] input[type="search"]' ).val(), matches = {},
	// 				toPrepend   = false;

	// 			// Look for matches if the stream displays search results.
	// 			if ( searchTerms ) {
	// 				searchTerms = new RegExp( searchTerms, 'im' );
	// 				matches     = response.activity.match( searchTerms );
	// 			}

	// 			/**
	// 			 * Before injecting the activity into the stream, we need to check the filter
	// 			 * and search terms are consistent with it when posting from a single item or
	// 			 * from the Activity directory.
	// 			 */
	// 			if ( ( ! searchTerms || matches ) ) {
	// 				toPrepend = ! store.filter || 0 === parseInt( store.filter, 10 ) || 'activity_update' === store.filter;
	// 			}

	// 			/**
	// 			 * "My Groups" tab is active.
	// 			 */
	// 			if ( toPrepend && response.is_directory ) {
	// 				toPrepend = ( 'all' === store.scope && ( 'user' === self.model.get( 'object' ) || 'group' === self.model.get( 'object' ) ) ) || ( self.model.get( 'object' ) + 's' === store.scope );
	// 			}

	// 			/**
	// 			 * In the user activity timeline, user is posting on other user's timeline
	// 			 * it will not have activity to prepend/append because of scope and privacy.
	// 			 */
	// 			if ( '' === response.activity && response.is_user_activity && response.is_active_activity_tabs ) {
	// 				toPrepend = false;
	// 			}

	// 			var medias = self.model.get( 'media' );
	// 			if ( ! _.isUndefined( medias ) && medias.length ) {
	// 				for ( var k = 0; k < medias.length; k++ ) {
	// 					medias[ k ].saved = true;
	// 				}
	// 				self.model.set( 'media', medias );
	// 			}

	// 			var link_embed = false;
	// 			if ( self.model.get( 'link_embed' ) == true ) {
	// 				link_embed = true;
	// 			}

	// 			var documents = self.model.get( 'document' );
	// 			if ( ! _.isUndefined( documents ) && documents.length ) {
	// 				for ( var d = 0; d < documents.length; d++ ) {
	// 					documents[ d ].saved = true;
	// 				}
	// 				self.model.set( 'document', documents );
	// 			}

	// 			var videos = self.model.get( 'video' );
	// 			if ( ! _.isUndefined( videos ) && videos.length ) {
	// 				for ( var v = 0; v < videos.length; v++ ) {
	// 					videos[ v ].saved = true;
	// 				}
	// 				self.model.set( 'video', videos );
	// 			}

	// 			if ( '' === self.model.get( 'id' ) || 0 === parseInt( self.model.get( 'id' ) ) ) {
	// 				// Reset draft activity.
	// 				bp.Nouveau.Activity.postForm.resetDraftActivity( false );
	// 			}

	// 			// Reset the form.
	// 			self.resetForm();

	// 			// Display a successful feedback if the acticity is not consistent with the displayed stream.
	// 			if ( ! toPrepend ) {

	// 				self.views.add(
	// 					new bp.Views.activityFeedback(
	// 						{
	// 							value: response.message,
	// 							type: 'updated'
	// 						}
	// 					)
	// 				);
	// 				jQuery( '#whats-new-form' ).addClass( 'bottom-notice' );

	// 				// Edit activity.
	// 			} else if ( edit ) {
	// 				jQuery( '#activity-' + response.id ).replaceWith( response.activity );

	// 				// Inject the activity into the stream only if it hasn't been done already (HeartBeat).
	// 			} else if ( ! jQuery( '#activity-' + response.id ).length ) {

	// 				// It's the very first activity, let's make sure the container can welcome it!
	// 				if ( ! jQuery( '#activity-stream ul.activity-list' ).length ) {
	// 					jQuery( '#activity-stream' ).html( jQuery( '<ul></ul>' ).addClass( 'activity-list item-list bp-list' ) );
	// 				}

	// 				// Prepend the activity.
	// 				bp.Nouveau.inject( '#activity-stream ul.activity-list', response.activity, 'prepend' );

	// 				// replace dummy image with original image by faking scroll event.
	// 				jQuery( window ).scroll();

	// 				if ( link_embed ) {
	// 					if ( ! _.isUndefined( window.instgrm ) ) {
	// 						window.instgrm.Embeds.process();
	// 					}
	// 					if ( ! _.isUndefined( window.FB ) && ! _.isUndefined( window.FB.XFBML ) ) {
	// 						window.FB.XFBML.parse( jQuery( document ).find( '#activity-' + response.id ).get( 0 ) );
	// 					}
	// 				}
	// 			}

	// 		}
	// 	).fail(
	// 		function ( response ) {
	// 			self.model.set( 'posting', false );
	// 			self.model.set(
	// 				'errors',
	// 				{
	// 					type: 'error',
	// 					value:  undefined === response.message ? BP_Nouveau.activity.params.errors.post_fail : response.message
	// 				}
	// 			);
	// 		}
	// 	);
	// },
};
	
