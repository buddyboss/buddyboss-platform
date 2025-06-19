/* jshint browser: true */
/* global bp */
/* @version 1.0.0 */
window.bp = window.bp || {};

( function ( exports, $ ) {

	/**
	 * [ReadLaunch description]
	 *
	 * @type {Object}
	 */
	bp.Readylaunch.Forums = {
		/**
		 * [start description]
		 *
		 * @return {[type]} [description]
		 */
		start: function () {
			this.addListeners();
			this.initMediumEditor();
			this.forumSelect2();
			this.forumEmoji();
		},

		/**
		 * [addListeners description]
		 */
		addListeners: function () {
			var $document = $( document );

			$document.on( 'change', '#bb-rl-forum-scope-options', this.handleForumScopeChange );
			$document.on( 'click', '.bb-rl-forum-tabs-item a', this.handleForumTabsClick );
			$document.on( 'click', '.bb-rl-new-discussion-btn', this.openForumModal );
			$document.on( 'click', '.bbp-topic-reply-link', this.openReplyModal );
			$document.on( 'click', '.bb-rl-forum-modal-close, .bb-rl-forum-modal-overlay', this.closeForumModal );
			$document.on( 'click', '.bb-rl-forum-modal-overlay', this.closeForumModalOverlay );
		},

		openForumModal: function ( e ) {
			e.preventDefault();
			$('.bbp-topic-form.bb-rl-forum-modal').addClass( 'bb-rl-forum-modal-visible' );
			$( '.bbp-topic-form' ).trigger(
				'bbp_after_load_topic_form',
				{
					click_event: this,
				}
			);
		},

		openReplyModal: function ( e ) {
			e.preventDefault();
			$('.bbp-reply-form.bb-rl-forum-modal').addClass( 'bb-rl-forum-modal-visible' );

			if( $( this ).closest( '.bb-rl-forum-reply-list-item' ).length > 0 ) {
				var $reply = $( this ).closest( '.bb-rl-forum-reply-list-item' );
				var $reply_header = $reply.children( '.bb-rl-reply-header' );
				var $reply_header_title = $reply_header.find( '.bb-rl-reply-author-info h3' ).text();
				var $reply_header_excerpt = $reply.children( '.bb-rl-reply-content' ).text().trim().substring( 0, 50 );
				var $reply_header_avatar_url = $reply_header.find( '.avatar' ).attr( 'src' );
			} else {
				var $header = $( this ).closest( '.bb-rl-topic-header'  );
				var $reply_header_title = $header.find( '.bb-rl-topic-author-name' ).text();
				var $reply_header_excerpt = $header.find( '#bbp_topic_excerpt' ).val();
				var $reply_header_avatar_url = $header.find( '.avatar' ).attr( 'src' );
			}

			$('.bbp-reply-form.bb-rl-forum-modal').find(  '.bb-rl-reply-header .bb-rl-reply-header-title').text( $reply_header_title );
			$( '.bbp-reply-form.bb-rl-forum-modal').find( '.bb-rl-reply-header .bb-rl-reply-header-excerpt' ).text( $reply_header_excerpt );
			$( '.bbp-reply-form.bb-rl-forum-modal').find( '.bb-rl-reply-header .bb-rl-reply-header-avatar img' ).attr( 'src', $reply_header_avatar_url );

			$( '.bbp-reply-form' ).trigger(
				'bbp_after_load_reply_form',
				{
					click_event: this,
				}
			);
		},

		closeForumModal: function ( e ) {
			e.preventDefault();

			$('.bb-rl-forum-modal').removeClass( 'bb-rl-forum-modal-visible' );

			$( this ).trigger(
				'bbp_after_close_topic_reply_form',
				{
					click_event: this,
				}
			);
		},

		closeForumModalOverlay: function ( e ) {
			e.preventDefault();

			$('.bb-rl-forum-modal').removeClass( 'bb-rl-forum-modal-visible' );

			$( document ).trigger(
				'bbp_after_close_topic_reply_form_on_overlay',
				{
					click_event: this,
				}
			);
		},

		handleForumScopeChange: function ( e ) {
			e.preventDefault();
			var $current = $( this ),
				$link    = $current.val();

			window.location.href = $link;

			return false;
		},

		handleForumTabsClick: function ( e ) {
			e.preventDefault();
			var $current = $( this ).parent(),
				$tab    = $current.data( 'id' );

			$('.bb-rl-forum-tabs-item').removeClass( 'selected' );
			$current.addClass( 'selected' );

			$('.bb-rl-forum-tabs-content').removeClass( 'selected' );
			$('#' + $tab).addClass( 'selected' );
		},

		initMediumEditor: function () {

			if ( typeof window.MediumEditor !== 'undefined' ) {

				window.forums_medium_forum_editor = [];
				window.forums_medium_reply_editor = [];
				window.forums_medium_topic_editor = [];
	
				var toolbarOptions = {
					buttons: ['bold', 'italic', 'unorderedlist','orderedlist', 'quote', 'anchor', 'pre' ],
					relativeContainer: document.getElementById( 'bb-rl-editor-toolbar' ),
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
									text: window.bbrlForumsEditorJsStrs.description,
									hideOnClick: true
								},
								// toolbar: toolbarOptions,
								toolbar: Object.assign(toolbarOptions, { relativeContainer: jQuery( element ).closest( 'form' ).find( '#bb-rl-editor-toolbar' )[0] } ),
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
	
						window.forums_medium_forum_editor[key].subscribe( 'editablePaste', function ( e ) {
							// Wrap all target <li> elements in a single <ul>
							var targetLiElements = jQuery(e.target).find('li').filter(function() {
								return !jQuery(this).parent().is('ul') && !jQuery(this).parent().is('ol');
							});
							if (targetLiElements.length > 0) {
								targetLiElements.wrapAll('<ul></ul>');
								// Update content into input field
								jQuery( e.target ).closest( 'form' ).find( '#bbp_forum_content' ).val( window.forums_medium_forum_editor[key].getContent() );
							}
						});
	
					});
				}
	
				// Add Click event to show / hide text formatting Toolbar for forum form.
				jQuery( document ).on( 'click', '.bbp-forum-form #whats-new-toolbar .show-toolbar', function ( e ) {
					e.preventDefault();
					var key = jQuery( e.currentTarget ).closest( '.bbp-forum-form' ).find( '.bbp_editor_forum_content' ).data( 'key' );
					var medium_editor = jQuery( e.currentTarget ).closest( '.bbp-form' ).find( '.medium-editor-toolbar' );
					jQuery( e.currentTarget ).find( '.bb-rl-toolbar-button' ).toggleClass( 'active' );
					if ( jQuery( e.currentTarget ).find( '.bb-rl-toolbar-button' ).hasClass( 'active' ) ) {
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
									text: window.bbrlForumsEditorJsStrs.type_reply,
									hideOnClick: true
								},
								// toolbar: toolbarOptions,
								toolbar: Object.assign(toolbarOptions, { relativeContainer: jQuery( element ).closest( 'form' ).find( '#bb-rl-editor-toolbar' )[0] } ),
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
	
						window.forums_medium_reply_editor[key].subscribe( 'editablePaste', function ( e ) {
							// Wrap all target <li> elements in a single <ul>
							var targetLiElements = jQuery(e.target).find('li').filter(function() {
								return !jQuery(this).parent().is('ul') && !jQuery(this).parent().is('ol');
							});
							if (targetLiElements.length > 0) {
								targetLiElements.wrapAll('<ul></ul>');
								// Update content into input field
								jQuery( e.target ).closest( 'form' ).find( '#bbp_reply_content' ).val( window.forums_medium_reply_editor[key].getContent() );
							}
						});
	
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
	
				}
	
				// Add Click event to show / hide text formatting Toolbar for reply form.
				jQuery( document ).on( 'click', '.bbp-reply-form #whats-new-toolbar .bb-rl-show-toolbar', function ( e ) {
					e.preventDefault();
					if( jQuery( this ).closest( '.bbpress-forums-activity.bb-quick-reply-form-wrap' ).length > 0) {
						return;
					}
					var key = jQuery( e.currentTarget ).closest( '.bbp-reply-form' ).find( '.bbp_editor_reply_content' ).data( 'key' );
					var medium_editor = jQuery( e.currentTarget ).closest( '.bbp-form' ).find( '.medium-editor-toolbar' );
					jQuery( e.currentTarget ).find( '.bb-rl-toolbar-button' ).toggleClass( 'active' );
					if ( jQuery( e.currentTarget ).find( '.bb-rl-toolbar-button' ).hasClass( 'active' ) ) {
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
									text: window.bbrlForumsEditorJsStrs.description,
									hideOnClick: true
								},
								// toolbar: toolbarOptions,
								toolbar: Object.assign(toolbarOptions, { relativeContainer: jQuery( element ).closest( 'form' ).find( '#bb-rl-editor-toolbar' )[0] } ),
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
	
						window.forums_medium_topic_editor[key].subscribe( 'editablePaste', function ( e ) {
							// Wrap all target <li> elements in a single <ul>
							var targetLiElements = jQuery(e.target).find('li').filter(function() {
								return !jQuery(this).parent().is('ul') && !jQuery(this).parent().is('ol');
							});
							if (targetLiElements.length > 0) {
								targetLiElements.wrapAll('<ul></ul>');
								// Update content into input field
								jQuery( e.target ).closest( 'form' ).find( '#bbp_topic_content' ).val( window.forums_medium_topic_editor[key].getContent() );
							}
						});
	
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
	
				}
	
				// Add Click event to show / hide text formatting Toolbar for topic form.
				jQuery( document ).on( 'click', '.bbp-topic-form #whats-new-toolbar .bb-rl-show-toolbar', function ( e ) {
					e.preventDefault();
					var key = jQuery( e.currentTarget ).closest( '.bbp-topic-form' ).find( '.bbp_editor_topic_content' ).data( 'key' );
					var medium_editor = jQuery( e.currentTarget ).closest( '.bbp-form' ).find( '.medium-editor-toolbar' );
					jQuery( e.currentTarget ).find( '.bb-rl-toolbar-button' ).toggleClass( 'active' );
					if ( jQuery( e.currentTarget ).find( '.bb-rl-toolbar-button' ).hasClass( 'active' ) ) {
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
			
		},

		forumSelect2: function () {
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
						tokenSeparators: [ ',' ],
						ajax: {
							url: bbrlForumsCommonJsData.ajax_url,
							dataType: 'json',
							delay: 1000,
							data: function ( params ) {
								return jQuery.extend( {}, params, {
									_wpnonce: bbrlForumsCommonJsData.nonce,
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

					// Apply CSS classes after initialization
					jQuery( element ).next( '.select2-container' ).find( '.select2-selection' ).addClass( 'bb-select-container' );
					
					// Add class to dropdown when it opens
					jQuery( element ).on( 'select2:open', function() {
						jQuery( '.select2-dropdown' ).addClass( 'bb-select-dropdown bb-tag-list-dropdown' );
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
		},

		forumEmoji: function () {
			if ( typeof BP_Nouveau !== 'undefined' && typeof BP_Nouveau.media !== 'undefined' && typeof BP_Nouveau.media.emoji !== 'undefined' ) {
				if ( jQuery( '.bbp-the-content' ).length && typeof jQuery.prototype.emojioneArea !== 'undefined' ) {
					jQuery( '.bbp-the-content' ).each( function ( i, element ) {
						var elem_id = jQuery( element ).attr( 'id' );
						var key = jQuery( element ).data( 'key' );
						jQuery( '#' + elem_id ).emojioneArea(
							{
								standalone: true,
								hideSource: false,
								container: jQuery( '#' + elem_id ).closest( 'form' ).find( '#whats-new-toolbar > .bb-rl-post-emoji' ),
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
										$( this.button[0] ).closest( '.bb-rl-post-emoji' ).addClass('active');
									},
	
									picker_hide: function () {
										$( this.button[0] ).closest( '.bb-rl-post-emoji' ).removeClass('active');
									},
								}
							}
						);
					} );
				}
			}
		}
	};

	// Launch members.
	bp.Readylaunch.Forums.start();

} )( bp, jQuery );
