/* jshint browser: true */
/* global bp, bp_select2, bbrlForumsCommonJsData */
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
			this.bb_rl_forums_quick_reply();
			this.bbp_quick_reply.init();
		},

		/**
		 * [addListeners description]
		 */
		addListeners: function () {
			var $document = $( document );

			$document.on( 'change', '#bb-rl-forum-scope-options', this.handleForumScopeChange );
			$document.on( 'click', '.bb-rl-forum-tabs-item a', this.handleForumTabsClick );
			$document.on( 'click', '.bb-rl-new-discussion-btn', this.openForumModal );
			$document.on( 'click', '.bbp-topic-reply-link, .bbp-reply-to-link', this.openReplyModal );
			$document.on( 'click', '.bb-rl-forum-modal-close, .bb-rl-forum-modal-overlay', this.closeForumModal );
			$document.on( 'click', '.bb-rl-forum-modal-overlay', this.closeForumModalOverlay );
			$document.on( 'click', '[id*="single-forum-description-popup"] .bb-close-action-popup', this.closeForumDescriptionPopup );

			window.addReply = {
				moveForm: function ( replyId, parentId, respondId, postId ) {
					$( '.bbp-reply-form' ).find( '#bbp_reply_to' ).val( parentId );
					var t = this, div, reply = t.I( replyId ), respond = t.I( respondId ),
						cancel = t.I( 'bbp-cancel-reply-to-link' ), parent = t.I( 'bbp_reply_to' ),
						post = t.I( 'bbp_topic_id' );

					if ( !reply || !respond || !cancel || !parent ) {
						return;
					}

					t.respondId = respondId;
					postId = postId || false;

					if ( !t.I( 'bbp-temp-form-div' ) ) {
						div = document.createElement( 'div' );
						div.id = 'bbp-temp-form-div';
						div.style.display = 'none';
						respond.parentNode.insertBefore( div, respond );
					}

					respond.classList.add( 'bb-rl-forum-modal-visible' );
					reply.parentNode.appendChild( respond );

					if ( post && postId ) {
						post.value = postId;
					}
					parent.value = parentId;
					cancel.style.display = '';

					try {
						t.I( 'bbp_reply_content' ).focus();
					} catch ( e ) {
					}

					var $reply = $( '.' + replyId + '.bb-rl-forum-reply-list-item' );
					var $reply_header = $reply.children( '.bb-rl-reply-header' );
					var $reply_header_title = $reply_header.find( '.bb-rl-reply-author-info h3' ).text();
					var $reply_header_excerpt = $reply.children( '.bb-rl-reply-content' ).text().trim().substring( 0, 50 );
					var $reply_header_avatar_url = $reply_header.find( '.avatar' ).attr( 'src' );

					$('.bbp-reply-form.bb-rl-forum-modal').find(  '.bb-rl-reply-header .bb-rl-reply-header-title').text( $reply_header_title );
					$( '.bbp-reply-form.bb-rl-forum-modal').find( '.bb-rl-reply-header .bb-rl-reply-header-excerpt' ).text( $reply_header_excerpt );
					$( '.bbp-reply-form.bb-rl-forum-modal').find( '.bb-rl-reply-header .bb-rl-reply-header-avatar img' ).attr( 'src', $reply_header_avatar_url );

					$( '.bbp-reply-form' ).trigger(
						'bbp_after_load_reply_form',
						{
							click_event: this,
						}
					);

					return false;
				},

				I: function ( e ) {
					return document.getElementById( e );
				}
			};

			if ( 'undefined' !== typeof bp.Nouveau ) {
				bp.Nouveau.reportPopUp();
				bp.Nouveau.reportActions();
			}
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

			if( $( this ).closest( '.bb-rl-reply-single-page' ).length > 0 ) {
				return;
			}

			e.preventDefault();
				
			$('.bbp-reply-form.bb-rl-forum-modal').addClass( 'bb-rl-forum-modal-visible' );

			var $reply_header_title = '';
			var $reply_header_excerpt = '';
			var $reply_header_avatar_url = '';

			if( $( this ).closest( '.bb-rl-forum-reply-list-item' ).length > 0 ) {
				var $reply = $( this ).closest( '.bb-rl-forum-reply-list-item' );
				var $reply_header = $reply.children( '.bb-rl-reply-header' );
				$reply_header_title = $reply_header.find( '.bb-rl-reply-author-info h3' ).text();
				$reply_header_excerpt = $reply.children( '.bb-rl-reply-content' ).text().trim().substring( 0, 50 );
				$reply_header_avatar_url = $reply_header.find( '.avatar' ).attr( 'src' );
			} else {
				var $header = $( this ).closest( '.bb-rl-topic-header'  );
				$reply_header_title = $header.find( '.bb-rl-topic-author-name' ).text();
				$reply_header_excerpt = $header.find( '#bbp_topic_excerpt' ).val();
				$reply_header_avatar_url = $header.find( '.avatar' ).attr( 'src' );
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

		closeForumDescriptionPopup: function ( e ) {
			e.preventDefault();
			$( this ).closest( '.bb-action-popup' ).hide();
		},

		initMediumEditor: function () {

			if ( typeof window.MediumEditor !== 'undefined' ) {

				window.bb_rl_forums_medium_forum_editor = [];
				window.bb_rl_forums_medium_reply_editor = [];
				window.bb_rl_forums_medium_topic_editor = [];
	
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
						window.bb_rl_forums_medium_forum_editor[key] = new window.MediumEditor(
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
	
						window.bb_rl_forums_medium_forum_editor[key].subscribe(
							'editableInput',
							function () {
								var bbp_forum_content = jQuery(element).closest('form').find( '#bbp_forum_content' );
								var html = window.bb_rl_forums_medium_forum_editor[key].getContent();
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
	
						window.bb_rl_forums_medium_forum_editor[key].subscribe( 'editablePaste', function ( e ) {
							// Wrap all target <li> elements in a single <ul>
							var targetLiElements = jQuery(e.target).find('li').filter(function() {
								return !jQuery(this).parent().is('ul') && !jQuery(this).parent().is('ol');
							});
							if (targetLiElements.length > 0) {
								targetLiElements.wrapAll('<ul></ul>');
								// Update content into input field
								jQuery( e.target ).closest( 'form' ).find( '#bbp_forum_content' ).val( window.bb_rl_forums_medium_forum_editor[key].getContent() );
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
						if ( window.bb_rl_forums_medium_forum_editor[ key ].exportSelection() !== null ) {
							medium_editor.addClass( 'medium-editor-toolbar-active' );
						}
					} else {
						jQuery( e.currentTarget ).attr( 'data-bp-tooltip', jQuery( e.currentTarget ).attr( 'data-bp-tooltip-show' ) );
						if ( window.bb_rl_forums_medium_forum_editor[ key ].exportSelection() === null ) {
							medium_editor.removeClass( 'medium-editor-toolbar-active' );
						}
					}
					jQuery( window.bb_rl_forums_medium_forum_editor[ key ].elements[ 0 ] ).focus();
					medium_editor.toggleClass( 'active' );
				} );
	
				if ( jQuery( '.bbp_editor_reply_content' ).length ) {
					jQuery( '.bbp_editor_reply_content' ).each(function(i,element){
	
						// added support for shortcode in elementor popup.
						if ( jQuery( element ).parents( '.elementor-popup-modal' ).length > 0 ) {
							return;
						}
	
						var key = jQuery(element).data('key');
						window.bb_rl_forums_medium_reply_editor[key] = new window.MediumEditor(
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
	
						window.bb_rl_forums_medium_reply_editor[key].subscribe(
							'editableInput',
							function () {
								var bbp_reply_content = jQuery(element).closest('form').find( '#bbp_reply_content' );
								var html = window.bb_rl_forums_medium_reply_editor[key].getContent();
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
									if ( window.bb_rl_forums_medium_reply_editor[key].linkTimeout != null ) {
										clearTimeout( window.bb_rl_forums_medium_reply_editor[key].linkTimeout );
									}
	
									window.bb_rl_forums_medium_reply_editor[key].linkTimeout = setTimeout(
										function () {
											var form = jQuery(element).closest( 'form' );
											window.bb_rl_forums_medium_reply_editor[key].linkTimeout = null;
											bp.Nouveau.linkPreviews.currentTarget = window.bb_rl_forums_medium_reply_editor[key];
											bp.Nouveau.linkPreviews.scrapURL( bbp_reply_content.val(), form.find( '#whats-new-attachments' ), form.find( '#link_preview_data' ) );
										},
										500
									);
								}
							}
						);
	
						window.bb_rl_forums_medium_reply_editor[key].subscribe( 'editablePaste', function ( e ) {
							// Wrap all target <li> elements in a single <ul>
							var targetLiElements = jQuery(e.target).find('li').filter(function() {
								return !jQuery(this).parent().is('ul') && !jQuery(this).parent().is('ol');
							});
							if (targetLiElements.length > 0) {
								targetLiElements.wrapAll('<ul></ul>');
								// Update content into input field
								jQuery( e.target ).closest( 'form' ).find( '#bbp_reply_content' ).val( window.bb_rl_forums_medium_reply_editor[key].getContent() );
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
						if ( window.bb_rl_forums_medium_reply_editor[ key ].exportSelection() !== null ) {
							medium_editor.addClass( 'medium-editor-toolbar-active' );
						}
					} else {
						jQuery( e.currentTarget ).attr( 'data-bp-tooltip', jQuery( e.currentTarget ).attr( 'data-bp-tooltip-show' ) );
						if ( window.bb_rl_forums_medium_reply_editor[ key ].exportSelection() === null ) {
							medium_editor.removeClass( 'medium-editor-toolbar-active' );
						}
					}
					jQuery( window.bb_rl_forums_medium_reply_editor[ key ].elements[ 0 ] ).focus();
					medium_editor.toggleClass( 'active' );
				} );
	
				if ( jQuery( '.bbp_editor_topic_content' ).length ) {
					jQuery( '.bbp_editor_topic_content' ).each(function(i,element){
	
						// added support for shortcode in elementor popup.
						if ( jQuery( element ).parents( '.elementor-location-popup' ).length > 0 ) {
							return;
						}
	
						var key = jQuery(element).data('key');
						window.bb_rl_forums_medium_topic_editor[key] = new window.MediumEditor(
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
	
						window.bb_rl_forums_medium_topic_editor[key].subscribe(
							'editableInput',
							function () {
								jQuery(element).closest('form').find( '#bbp_topic_content' ).val( window.bb_rl_forums_medium_topic_editor[key].getContent() );
								var bbp_topic_content = jQuery(element).closest('form').find( '#bbp_topic_content' );
	
								var html = window.bb_rl_forums_medium_topic_editor[key].getContent();
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
									if ( window.bb_rl_forums_medium_topic_editor[key].linkTimeout != null ) {
										clearTimeout( window.bb_rl_forums_medium_topic_editor[key].linkTimeout );
									}
	
									window.bb_rl_forums_medium_topic_editor[key].linkTimeout = setTimeout(
										function () {
											var form = jQuery(element).closest('form');
											window.bb_rl_forums_medium_topic_editor[key].linkTimeout = null;
											bp.Nouveau.linkPreviews.currentTarget = window.bb_rl_forums_medium_topic_editor[key];
											bp.Nouveau.linkPreviews.scrapURL( bbp_topic_content.val(), form.find( '#whats-new-attachments' ), form.find( '#link_preview_data' ) );
										},
										500
									);
								}
							}
						);
	
						window.bb_rl_forums_medium_topic_editor[key].subscribe( 'editablePaste', function ( e ) {
							// Wrap all target <li> elements in a single <ul>
							var targetLiElements = jQuery(e.target).find('li').filter(function() {
								return !jQuery(this).parent().is('ul') && !jQuery(this).parent().is('ol');
							});
							if (targetLiElements.length > 0) {
								targetLiElements.wrapAll('<ul></ul>');
								// Update content into input field
								jQuery( e.target ).closest( 'form' ).find( '#bbp_topic_content' ).val( window.bb_rl_forums_medium_topic_editor[key].getContent() );
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
						if ( window.bb_rl_forums_medium_topic_editor[ key ].exportSelection() !== null ) {
							medium_editor.addClass( 'medium-editor-toolbar-active' );
						}
					} else {
						jQuery( e.currentTarget ).attr( 'data-bp-tooltip', jQuery( e.currentTarget ).attr( 'data-bp-tooltip-show' ) );
						if ( window.bb_rl_forums_medium_topic_editor[ key ].exportSelection() === null ) {
							medium_editor.removeClass( 'medium-editor-toolbar-active' );
						}
					}
					jQuery( window.bb_rl_forums_medium_topic_editor[ key ].elements[ 0 ] ).focus();
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
										if ( typeof window.bb_rl_forums_medium_topic_editor !== 'undefined' && typeof window.bb_rl_forums_medium_topic_editor[ key ] !== 'undefined' ) {
											window.bb_rl_forums_medium_topic_editor[ key ].resetContent();
										}
										if ( typeof window.bb_rl_forums_medium_reply_editor !== 'undefined' && typeof window.bb_rl_forums_medium_reply_editor[ key ] !== 'undefined' ) {
											window.bb_rl_forums_medium_reply_editor[ key ].resetContent();
										}
										if ( typeof window.bb_rl_forums_medium_forum_editor !== 'undefined' && typeof window.bb_rl_forums_medium_forum_editor[ key ] !== 'undefined' ) {
											window.bb_rl_forums_medium_forum_editor[ key ].resetContent();
										}
									},
									emojibtn_click: function () {
										if ( typeof window.bb_rl_forums_medium_topic_editor !== 'undefined' && typeof window.bb_rl_forums_medium_topic_editor[ key ] !== 'undefined' ) {
											window.bb_rl_forums_medium_topic_editor[ key ].checkContentChanged();
										}
										if ( typeof window.bb_rl_forums_medium_reply_editor !== 'undefined' && typeof window.bb_rl_forums_medium_reply_editor[ key ] !== 'undefined' ) {
											window.bb_rl_forums_medium_reply_editor[ key ].checkContentChanged();
										}
										if ( typeof window.bb_rl_forums_medium_forum_editor !== 'undefined' && typeof window.bb_rl_forums_medium_forum_editor[ key ] !== 'undefined' ) {
											window.bb_rl_forums_medium_forum_editor[ key ].checkContentChanged();
										}
										if ( typeof window.bb_rl_forums_medium_topic_editor == 'undefined' ) {
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
		},

		bbp_reply_ajax_call: function( action, nonce, form_data, form ) {
			var $data = {
				action: action,
				nonce: nonce
			};
			$.each(
				form_data,
				function ( i, field ) {
					if ( field.name === 'action' ) {
						$data.bbp_reply_form_action = field.value;
					} else {
						$data[field.name] = field.value;
					}
				}
			);
			var $bbpress_forums_element = form.closest( '#bbpress-forums' );
			$.post(
				window.bbpReplyAjaxJS.bbp_ajaxurl,
				$data,
				function ( response ) {
					if ( response.success ) {
						$bbpress_forums_element.find( '.bbp-reply-form form' ).removeClass( 'submitting' );
						var reply_list_item = '';
						var replyForm      = $( '.bb-quick-reply-form-wrap' );
						if ( 'edit' === response.reply_type ) {
							reply_list_item = '<li class="highlight">' + response.content + '</li>';
							// in-place editing doesn't work yet, but could (and should) eventually.
							$( '#post-' + response.reply_id ).parent( 'li' ).replaceWith( reply_list_item ).find( 'li' ).each( function() { bp.Readylaunch.Forums.bbp_reply_hide_single_url( this, '.bb-rl-reply-content' ); } );
						} else {
							if ( window.bbpReplyAjaxJS.threaded_reply && response.reply_parent && response.reply_parent !== response.reply_id ) {
								// threaded comment.
								var $parent = null;
								var reply_list_item_depth = '1';
								if ( $( '#post-' + response.reply_parent ).parent( 'li' ).data( 'depth' ) == window.bbpReplyAjaxJS.threaded_reply_depth ) {
									var depth = parseInt( window.bbpReplyAjaxJS.threaded_reply_depth ) - 1;
									$parent = $( '#post-' + response.reply_parent ).closest( 'li.depth-' + depth );
									reply_list_item_depth = window.bbpReplyAjaxJS.threaded_reply_depth;
								} else {
									$parent = $( '#post-' + response.reply_parent ).parent( 'li' );
									reply_list_item_depth = parseInt( $parent.data( 'depth' ) ) + 1;
								}
								var list_type = 'ul';
								if ( $bbpress_forums_element.find( '.bb-rl-single-reply-list' ).is( 'ol' ) ) {
									list_type = 'ol';
								}
								if ( !$parent.find( '>' + list_type + '.bbp-threaded-replies' ).length ) {
									$parent.append( '<' + list_type + ' class="bbp-threaded-replies"></' + list_type + '>' );
								}
								reply_list_item = '<li class="highlight depth-' + reply_list_item_depth + '" data-depth="' + reply_list_item_depth + '">' + response.content + '</li>';
								$parent.find( '>' + list_type + '.bbp-threaded-replies' ).append( reply_list_item ).find( 'li' ).each( function() { bp.Readylaunch.Forums.bbp_reply_hide_single_url( this, '.bb-rl-reply-content' ); } );
							} else {
								/**
								* Redirect to last page when anyone reply from begging of the page.
								*/
								if ( response.current_page == response.total_pages ) {
									reply_list_item = '<li class="highlight depth-1" data-depth="1">' + response.content + '</li>';
									$bbpress_forums_element.find( '.bb-rl-single-reply-list' ).append( reply_list_item ).find( 'li' ).each( function() { bp.Readylaunch.Forums.bbp_reply_hide_single_url( this, '.bb-rl-reply-content' ); } );
								} else {
									var oldRedirectUrl = response.redirect_url;
									var newRedirectUrl = oldRedirectUrl.substring( 0, oldRedirectUrl.indexOf( '#' ) );
		
									// Prevent redirect for quick reply form for titmeline.
									if ( ! replyForm.length && ! replyForm.is(':visible') ) {
										window.location.href = newRedirectUrl;
									}
								}
								/**
								* Ended code for redirection to the last page
								*/
							}
							// replace dummy image with original image by faking scroll event to call bp.Nouveau.lazyLoad.
							jQuery( window ).scroll();
						}
						// Get all the tags without page reload.
						if ( typeof response.tags !== 'undefined' && response.tags !== null ) {
							var tagsDivSelector   = $bbpress_forums_element.find( '.item-tags' );
							var tagsDivUlSelector = $bbpress_forums_element.find( '.item-tags ul' );
							if ( tagsDivSelector.css( 'display' ) === 'none' && '' !== response.tags ) {
								tagsDivSelector.append( response.tags );
								tagsDivSelector.show();
							} else if ( '' !== response.tags ) {
								tagsDivUlSelector.remove();
								tagsDivSelector.append( response.tags );
							} else {
								tagsDivSelector.hide();
								tagsDivUlSelector.remove();
							}
						}

						if ( '' !== reply_list_item ) {

							if ( 0 < $( '#post-' + response.reply_id ).length ) {
								$( 'html, body' ).animate(
									{
										scrollTop: $( '#post-' + response.reply_id ).offset().top
									},
									500
								);
							}
							setTimeout(
								function () {
									$( reply_list_item ).removeClass( 'highlight' );
								},
								2000
							);
						}

						var media_element_key = $bbpress_forums_element.find( '.bbp-reply-form form' ).find( '#bb-rl-forums-post-media-uploader' ).data( 'key' );
						var media = false;
						if ( typeof bp !== 'undefined' &&
							typeof bp.Nouveau !== 'undefined' &&
							typeof bp.Nouveau.Media !== 'undefined' &&
							typeof bp.Nouveau.Media.dropzone_media !== 'undefined' &&
							typeof bp.Nouveau.Media.dropzone_media[media_element_key] !== 'undefined' &&
							bp.Nouveau.Media.dropzone_media[media_element_key].length
						) {
							media = true;
							for ( var media_index = 0; media_index < bp.Nouveau.Media.dropzone_media[media_element_key].length; media_index++ ) {
								bp.Nouveau.Media.dropzone_media[media_element_key][media_index].saved = true;
							}
						}
						var document_element_key = $bbpress_forums_element.find( '.bbp-reply-form form' ).find( '#bb-rl-forums-post-document-uploader' ).data( 'key' );
						var document = false;
						if ( typeof bp !== 'undefined' &&
							typeof bp.Nouveau !== 'undefined' &&
							typeof bp.Nouveau.Media !== 'undefined' &&
							typeof bp.Nouveau.Media.dropzone_media !== 'undefined' &&
							typeof bp.Nouveau.Media.dropzone_media[document_element_key] !== 'undefined' &&
							bp.Nouveau.Media.dropzone_media[document_element_key].length
						) {
							document = true;
							for ( var document_index = 0; document_index < bp.Nouveau.Media.dropzone_media[document_element_key].length; document_index++ ) {
								bp.Nouveau.Media.dropzone_media[document_element_key][document_index].saved = true;
							}
						}

						var video_element_key = $bbpress_forums_element.find( '.bbp-reply-form form' ).find( '#bb-rl-forums-post-video-uploader' ).data( 'key' );
						var video 			 = false;
						if ( typeof bp !== 'undefined' &&
							typeof bp.Nouveau !== 'undefined' &&
							typeof bp.Nouveau.Media !== 'undefined' &&
							typeof bp.Nouveau.Media.dropzone_media !== 'undefined' &&
							typeof bp.Nouveau.Media.dropzone_media[video_element_key] !== 'undefined' &&
							bp.Nouveau.Media.dropzone_media[video_element_key].length
						) {
							video = true;
							for ( var video_index = 0; video_index < bp.Nouveau.Media.dropzone_media[video_element_key].length; video_index++ ) {
								bp.Nouveau.Media.dropzone_media[video_element_key][video_index].saved = true;
							}
						}

						var editor_element_key = $bbpress_forums_element.find( '.bbp-reply-form form' ).find( '.bbp-the-content' ).data( 'key' );
						if ( typeof window.bb_rl_forums_medium_reply_editor !== 'undefined' && typeof window.bb_rl_forums_medium_reply_editor[editor_element_key] !== 'undefined' ) {
							window.bb_rl_forums_medium_reply_editor[editor_element_key].resetContent();
						}
						$bbpress_forums_element.find( '.bbp-reply-form form' ).find( '.bbp-the-content' ).removeClass( 'error' );
						if ( replyForm.length && replyForm.is(':visible') ) {
							$bbpress_forums_element.find('.bbp-reply-form').hide();
						} else {
							$bbpress_forums_element.find( '.bb-rl-forum-modal-close' ).trigger( 'click' );
						}

						$bbpress_forums_element.find( '.header-total-reply-count.bp-hide' ).removeClass( 'bp-hide' );
						if ( response.total_reply_count ) {
							$bbpress_forums_element.find( '.header-total-reply-count .topic-reply-count' ).html( response.total_reply_count );
							$bbpress_forums_element.find( '.topic-lead .bs-replies' ).html( response.total_reply_count );
							$( '.bb-rl-forums-items' ).removeClass( 'topic-list-no-replies' );
						}

						if ( $bbpress_forums_element.find( '.bb-rl-forums-container-inner .bp-feedback.info' ).length > 0 ) {
							$bbpress_forums_element.find( '.bb-rl-forums-container-inner .bp-feedback.info' ).remove();
						}

						$bbpress_forums_element.find( '#bbp_reply_content' ).val( '' );
						$bbpress_forums_element.find( '#link_preview_data' ).val( '' );
						bp.Nouveau.linkPreviews.options.link_url = null;
						bp.Nouveau.linkPreviews.options.link_image_index_save = 0;
						bp.Readylaunch.Forums.reset_reply_form( $bbpress_forums_element, media_element_key, media );
						bp.Readylaunch.Forums.reset_reply_form( $bbpress_forums_element, document_element_key, document );
						bp.Readylaunch.Forums.reset_reply_form( $bbpress_forums_element, video_element_key, video );
						
					} else {
						if ( typeof response.content !== 'undefined' ) {
							$bbpress_forums_element.find( '.bbp-reply-form form' ).find( '#bbp-template-notices' ).html( response.content );
						}
					}
					$bbpress_forums_element.find( '.bbp-reply-form form' ).removeClass( 'submitting' );

					$( '.bbp-reply-form' ).trigger( 'bbp_after_submit_reply_form', {
						response: response, 
						topic_id: $data.bbp_topic_id 
					} );
				}
			);
		},

		reset_reply_form: function( $element, media_element_key, media ) {
			// clear notices.
			$element.find( '.bbp-reply-form form' ).find( '#bbp-template-notices' ).html( '' );
			if (
				typeof bp !== 'undefined' &&
				typeof bp.Nouveau !== 'undefined' &&
				typeof bp.Nouveau.Media !== 'undefined'
			) {
				$element.find( '.bb-rl-gif-media-search-dropdown' ).removeClass( 'open' );
				$element.find( '#whats-new-toolbar .bb-rl-toolbar-button' ).removeClass( 'active disable' );
				var $forums_attached_gif_container = $element.find( '#whats-new-attachments .forums-attached-gif-container' );
				if ( $forums_attached_gif_container.length ) {
					$forums_attached_gif_container.addClass( 'closed' );
					$forums_attached_gif_container.find( '.gif-image-container img' ).attr( 'src', '' );
					$forums_attached_gif_container[0].style = '';
				}
				if ( $element.find( '#bbp_media_gif' ).length ) {
					$element.find( '#bbp_media_gif' ).val( '' );
				}
				if ( typeof media_element_key !== 'undefined' && media ) {
					if ( typeof bp.Nouveau.Media.dropzone_obj[media_element_key] !== 'undefined' ) {
						bp.Nouveau.Media.dropzone_obj[media_element_key].destroy();
						bp.Nouveau.Media.dropzone_obj.splice( media_element_key, 1 );
						bp.Nouveau.Media.dropzone_media.splice( media_element_key, 1 );
					}
					$element.find( 'div#bb-rl-forums-post-media-uploader[data-key="' + media_element_key + '"]' ).html( '' );
					$element.find( 'div#bb-rl-forums-post-media-uploader[data-key="' + media_element_key + '"]' ).addClass( 'closed' ).removeClass( 'open' );
					$element.find( 'div#bb-rl-forums-post-document-uploader[data-key="' + media_element_key + '"]' ).html( '' );
					$element.find( 'div#bb-rl-forums-post-document-uploader[data-key="' + media_element_key + '"]' ).addClass( 'closed' ).removeClass( 'open' );

					$element.find( 'div#bb-rl-forums-post-video-uploader[data-key="' + media_element_key + '"]' ).html( '' );
					$element.find( 'div#bb-rl-forums-post-video-uploader[data-key="' + media_element_key + '"]' ).addClass( 'closed' ).removeClass( 'open' );
				}
			}
		},
		
		bbp_reply_hide_single_url: function( container, selector ) {
			var _findtext  = $( container ).find( selector + ' > p' ).removeAttr( 'br' ).removeAttr( 'a' ).text();
			var _url       = '',
				newString  = '',
				startIndex = '',
				_is_exist  = 0;
			if ( 0 <= _findtext.indexOf( 'http://' ) ) {
				startIndex = _findtext.indexOf( 'http://' );
				_is_exist  = 1;
			} else if ( 0 <= _findtext.indexOf( 'https://' ) ) {
				startIndex = _findtext.indexOf( 'https://' );
				_is_exist  = 1;
			} else if ( 0 <= _findtext.indexOf( 'www.' ) ) {
				startIndex = _findtext.indexOf( 'www' );
				_is_exist  = 1;
			}
			if ( 1 === _is_exist ) {
				for ( var i = startIndex; i < _findtext.length; i++ ) {
					if ( _findtext[ i ] === ' ' || _findtext[ i ] === '\n' ) {
						break;
					} else {
						_url += _findtext[ i ];
					}
				}

				if ( _url !== '' ) {
					newString = $.trim( _findtext.replace( _url, '' ) );
				}

				if ( $.trim( newString ).length === 0 && $( container ).find( 'iframe' ).length !== 0 && _url !== '' ) {
					$( container ).find( selector + ' > p:first' ).hide();
				}
			}
		},

		bb_rl_forums_quick_reply: function() {
			if ( !$( 'body' ).hasClass( 'reply-edit' ) ) {
				$( document ).on(
					'submit',
					'.bbp-reply-form form',
					function ( e ) {
						e.preventDefault();
						if ( $( this ).hasClass( 'submitting' ) ) {
							return false;
						}
						$( this ).addClass( 'submitting' );
						var valid = true;
						var media_valid = true;
						var editor_key = $( e.target ).find( '.bbp-the-content' ).data( 'key' );
						var editor = false;
						if ( typeof window.bb_rl_forums_medium_reply_editor !== 'undefined' && typeof window.bb_rl_forums_medium_reply_editor[editor_key] !== 'undefined' ) {
							editor = window.bb_rl_forums_medium_reply_editor[editor_key];
						}

						// Check if GIF support is enabled (GIF button exists and is not disabled)
						var gif_support_enabled = $( this ).find( '#bb-rl-forums-gif-button' ).length > 0 && ! $( this ).find( '#bb-rl-forums-gif-button' ).parents( '.bb-rl-post-elements-buttons-item' ).hasClass( 'disable' );

						if (
						(
						$( this ).find( '#bbp_media' ).length > 0 &&
						$( this ).find( '#bbp_document' ).length > 0 &&
						$( this ).find( '#bbp_video' ).length > 0 &&
						gif_support_enabled &&
						$( this ).find( '#bbp_media_gif' ).length > 0 &&
						( $( this ).find( '#bbp_media' ).val() == '' || $( this ).find( '#bbp_media' ).val() == '[]' ) &&
						( $( this ).find( '#bbp_document' ).val() == '' || $( this ).find( '#bbp_document' ).val() == '[]' ) &&
						( $( this ).find( '#bbp_video' ).val() == '' || $( this ).find( '#bbp_video' ).val() == '[]' ) &&
						$( this ).find( '#bbp_media_gif' ).val() == ''
						) || (
						$( this ).find( '#bbp_media' ).length > 0 &&
						$( this ).find( '#bbp_document' ).length > 0 &&
						$( this ).find( '#bbp_video' ).length > 0 &&
						! gif_support_enabled &&
						( $( this ).find( '#bbp_media' ).val() == '' || $( this ).find( '#bbp_media' ).val() == '[]' ) &&
						( $( this ).find( '#bbp_video' ).val() == '' || $( this ).find( '#bbp_video' ).val() == '[]' ) &&
						( $( this ).find( '#bbp_document' ).val() == '' || $( this ).find( '#bbp_document' ).val() == '[]' )
						) || (
						gif_support_enabled &&
						$( this ).find( '#bbp_media_gif' ).length > 0 &&
						$( this ).find( '#bbp_media' ).length <= 0 &&
						$( this ).find( '#bbp_document' ).length <= 0 &&
						$( this ).find( '#bbp_video' ).length <= 0 &&
						$( this ).find( '#bbp_media_gif' ).val() == ''
						)
						) {
							media_valid = false;
						}
						if( $( this ).find( '#link_preview_data' ).length > 0 && $( this ).find( '#link_preview_data' ).val() !== '' ) {
							var link_preview_data = JSON.parse( $( this ).find( '#link_preview_data' ).val() );
							if( link_preview_data.link_url !== '' ) {
								media_valid = true;
							}
						}
						
						if ( editor ) {
							// Check raw editor content instead of processed content
							var editor_content = editor.getContent();
							var editor_text = $( $.parseHTML( editor_content ) ).text().trim();
							var has_mentions = editor_content.indexOf( 'atwho-inserted' ) >= 0 || editor_content.indexOf( 'bp-suggestions-mention' ) >= 0;

							if ( ( editor_text === '' && !has_mentions ) && media_valid == false ) {
								$( this ).find( '.bbp-the-content' ).addClass( 'error' );
								valid = false;
							} else {
								$( this ).find( '.bbp-the-content' ).removeClass( 'error' );
							}
						} else if (
							(
								!editor &&
								$.trim( $( this ).find( '#bbp_reply_content' ).val() ) === ''
							) &&
							media_valid == false
						) {
							$( this ).find( '#bbp_reply_content' ).addClass( 'error' );
							valid = false;
						} else {
							if ( editor ) {
								$( this ).find( '.bbp-the-content' ).removeClass( 'error' );
							}
							$( this ).find( '#bbp_reply_content' ).removeClass( 'error' );
						}

						if ( valid ) {
							// Use raw editor content instead of processed content to preserve mentions
							if ( editor ) {
								var raw_content = editor.getContent();
								$( this ).find( '#bbp_reply_content' ).val( raw_content );
							}
							
							bp.Readylaunch.Forums.bbp_reply_ajax_call( 'reply', window.bbpReplyAjaxJS.reply_nonce, $( this ).serializeArray(), $( this ) );
						} else {
							$( this ).removeClass( 'submitting' );
						}
					}
				);
			}

			if ( bp.Readylaunch.Forums.bb_rl_getUrlParameter( 'bbp_reply_to' ) ) {
				if ( bp.Readylaunch.Forums.bb_rl_getUrlParameter( 'bbp_reply_to' ) ) {
					if ( parseInt( bp.Readylaunch.Forums.bb_rl_getUrlParameter( 'bbp_reply_to' ) ) > 0 && $( document ).find( '.bb-rl-forum-reply-list-item.post-' + bp.Readylaunch.Forums.bb_rl_getUrlParameter( 'bbp_reply_to' ) ).length ) {
						$( window ).load( function () {
							$( '.bb-rl-forum-reply-list-item.post-' + bp.Readylaunch.Forums.bb_rl_getUrlParameter( 'bbp_reply_to' ) + ' .bbp-reply-to-link' ).trigger( 'click' );
						} );
					} else {
						$( '.bbp-topic-reply-link' ).trigger( 'click' );
					}
				}
			}
		},

		bbp_quick_reply : {
			init: function () {
				this.ajax_call();
				this.moveToReply();
			},

			// Quick Reply AJAX call
			ajax_call: function () {
				$( document ).on(
					'click',
					'a[data-btn-id="bbp-reply-form"]',
					function (e) {
						e.preventDefault();

						var curObj = $( this );
						var curActivity = curObj.closest('li');
						var topic_id = curObj.data('topic-id');
						var reply_exerpt = curActivity.find( '.activity-discussion-title-wrap a' ).text();
						var activity_data = curActivity.data('bp-activity');
						var group_id = activity_data.group_id ? activity_data.group_id : 0;
						var appendthis = ( '<div class="bb-modal-overlay js-modal-close"></div>' );
						if ( $('.bb-quick-reply-form-wrap').length ) {
							$('.bb-quick-reply-form-wrap').remove();
						}

						$( 'body' ).addClass( 'bb-modal-overlay-open' ).append( appendthis );
						$( '.bb-modal-overlay' ).fadeTo( 0, 1 );
						var $bbpress_forums_element = curObj.closest( '.bb-grid .content-area' );
						var loading_modal = '<div id="bbpress-forums" class="bbpress-forums-activity bb-quick-reply-form-wrap"><div class="bbp-reply-form bb-modal bb-modal-box"><form id="new-post" name="new-post" method="post" action=""><fieldset class="bbp-form"><legend>'+window.bbpReplyAjaxJS.reply_to_text+' <span id="bbp-reply-exerpt"> '+reply_exerpt+'...</span><a href="#" id="bbp-close-btn" class="js-modal-close"><i class="bb-icon-close"></i></a></legend><div><div class="bbp-the-content-wrapper"><div class="bbp-the-content bbp_editor_reply_content medium-editor-element" contenteditable="true" data-placeholder="'+window.bbpReplyAjaxJS.type_reply_here_text+'"></div></div></fieldset></form></div></div>';
						$bbpress_forums_element.append(loading_modal);
						$bbpress_forums_element.find( '.bb-quick-reply-form-wrap' ).show( 0 ).find( '.bbp-reply-form' ).addClass( 'bb-modal bb-modal-box' ).show( 0 );
						$bbpress_forums_element.find( '.bb-quick-reply-form-wrap .bbp-the-content-wrapper' ).addClass( 'loading' ).show( 0 );

						var data = {
							action: 'quick_reply_ajax',
							topic_id: topic_id,
							group_id: group_id,
							'bbp-ajax': 1,
						};

						$.post(
							ajaxurl,
							data,
							function (response) {
								$bbpress_forums_element.append(response);
								if ( $bbpress_forums_element.find('div.bb-quick-reply-form-wrap').length ) {
									var $quick_reply_wrap = $bbpress_forums_element.find('div.bb-quick-reply-form-wrap[data-component="activity"');
									$quick_reply_wrap.show();
									$quick_reply_wrap.not('[data-component="activity"]').hide();

									if ( $quick_reply_wrap.find('.bbp-reply-form').length ) {
										$quick_reply_wrap.find('.bbp-reply-form').addClass('bb-modal bb-modal-box');
										$quick_reply_wrap.find('.bbp-reply-form').show();

										$quick_reply_wrap.find('.bbp-reply-form').find( '#bbp-reply-exerpt' ).text( reply_exerpt + '...' );
										$quick_reply_wrap.find('.bbp-reply-form').find( '#bbp_topic_id' ).val( topic_id );

										bp.Readylaunch.Forums.addSelect2( $quick_reply_wrap );
										bp.Readylaunch.Forums.addEditor( $quick_reply_wrap );

										if ( typeof bp !== 'undefined' &&
											typeof bp.Nouveau !== 'undefined' &&
											typeof bp.Nouveau.Media !== 'undefined'
										) {
											if ( typeof bp.Nouveau.Media.options !== 'undefined' ) {
												var ForumMediaTemplate = $quick_reply_wrap.find('.bbp-reply-form').find('.forum-post-media-template').length ? $quick_reply_wrap.find('.bbp-reply-form').find('.forum-post-media-template')[0].innerHTML : '';
												bp.Nouveau.Media.options.previewTemplate = ForumMediaTemplate;
											}

											if ( typeof bp.Nouveau.Media.documentOptions !== 'undefined' ) {
												var ForumDocumentTemplates = $quick_reply_wrap.find('.bbp-reply-form').find('.forum-post-document-template').length ? $quick_reply_wrap.find('.bbp-reply-form').find('.forum-post-document-template')[0].innerHTML : '';
												bp.Nouveau.Media.documentOptions.previewTemplate = ForumDocumentTemplates;
											}

											if ( typeof bp.Nouveau.Media.videoOptions !== 'undefined' ) {
												var ForumVideoTemplate = $quick_reply_wrap.find('.bbp-reply-form').find('.forum-post-video-template').length ? $quick_reply_wrap.find('.bbp-reply-form').find('.forum-post-video-template')[0].innerHTML : '';
												bp.Nouveau.Media.videoOptions.previewTemplate = ForumVideoTemplate;
											}
										}
									}

									if ( $quick_reply_wrap.find('.bbp-no-reply').length ){
										$quick_reply_wrap.find('.bbp-no-reply').addClass( 'bb-modal bb-modal-box' );
										$quick_reply_wrap.find('.bbp-no-reply').show();
									}
								}
							}
						);
					}
				);
			},
			
			// When click on notification then move to particular reply.
			moveToReply: function () {
				if ( window.location.href.indexOf( '#post-' ) > 0 ) {
					var varUrl = window.location.href.split( '#post-' );
					var postID = varUrl && undefined !== varUrl[1] ? varUrl[1] : '';
					if ( !postID || $( '#post-' + postID ).length == 0 ) {
						return;
					}
					var scrollTop, admin_bar_height = 0;
			
					if ( $( '#wpadminbar' ).length > 0 ) {
						admin_bar_height = $( '#wpadminbar' ).innerHeight();
					}
			
					if ( $( 'body' ).hasClass( 'sticky-header' ) ) {
						scrollTop = ( $( '#post-' + postID ).parent().offset().top - $( '#masthead' ).innerHeight() - admin_bar_height );
					} else {
						scrollTop = ( $( '#post-' + postID ).parent().offset().top - admin_bar_height );
					}
					$( 'html, body' ).animate( {
						scrollTop: scrollTop
					}, 200 );
				}
			}

		},

		bb_rl_getUrlParameter: function( sParam ) {
			var sPageURL = window.location.search.substring( 1 ),
				sURLVariables = sPageURL.split( '&' ),
				sParameterName,
				i;
	
			for ( i = 0; i < sURLVariables.length; i++ ) {
				sParameterName = sURLVariables[ i ].split( '=' );
	
				if ( sParameterName[ 0 ] === sParam ) {
					return sParameterName[ 1 ] === undefined ? true : decodeURIComponent( sParameterName[ 1 ] );
				}
			}
		}
	};

	// Launch members.
	bp.Readylaunch.Forums.start();

} )( bp, jQuery );
