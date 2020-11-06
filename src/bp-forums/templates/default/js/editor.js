jQuery( document ).ready(
	function() {

		if ( typeof window.MediumEditor !== 'undefined' ) {

			  var toolbarOptions = {
					buttons: ['bold', 'italic', 'unorderedlist','orderedlist', 'quote', 'anchor', 'pre' ],
					relativeContainer: document.getElementById('whats-new-toolbar'),
					static: true,
					updateOnEmptySelection: true
			};
			if ( jQuery( '.bbp_editor_forum_content' ).length ) {
				window.forums_medium_forum_editor = [];
				jQuery( '.bbp_editor_forum_content' ).each(function(i,element){
					var key = jQuery(element).data('key');
					window.forums_medium_forum_editor[key] = new window.MediumEditor(
						element,
						{
							placeholder: {
								text: window.bbpEditorJsStrs.description,
								hideOnClick: true
							},
							toolbar: toolbarOptions,
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
								cleanTags: [ 'meta', 'div', 'main', 'section', 'article', 'aside', 'button', 'svg', 'canvas', 'figure', 'input', 'textarea', 'select', 'label', 'form', 'table', 'thead', 'tfooter', 'colgroup', 'col', 'tr', 'td', 'th', 'dl', 'dd', 'center', 'caption', 'nav' ],
								unwrapTags: [ 'ul', 'ol', 'li' ]
							},
							imageDragging: false
						}
					);

					window.forums_medium_forum_editor[key].subscribe(
						'editableInput',
						function ( event ) {
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

				//Add Click event to show / hide text formatting Toolbar
				jQuery( 'body' ).on('click', '.bbp-forum-form #whats-new-toolbar .show-toolbar', function(e) {
					e.preventDefault();
					var key = jQuery(e.currentTarget).closest('.bbp-forum-form').find('.bbp_editor_forum_content').data('key');
					var medium_editor = jQuery(e.currentTarget).closest('.bbp-form').find('.medium-editor-toolbar');
					jQuery(e.currentTarget).find('.toolbar-button').toggleClass('active');
					if( jQuery(e.currentTarget).find('.toolbar-button').hasClass('active') ) {
						jQuery(e.currentTarget).attr('data-bp-tooltip',jQuery(e.currentTarget).attr('data-bp-tooltip-hide'));
						if( window.forums_medium_forum_editor[key].exportSelection() !== null ){
							medium_editor.addClass('medium-editor-toolbar-active');
						}
					} else {
						jQuery(e.currentTarget).attr('data-bp-tooltip',jQuery(e.currentTarget).attr('data-bp-tooltip-show'));
						if( window.forums_medium_forum_editor[key].exportSelection() === null ) {
							medium_editor.removeClass('medium-editor-toolbar-active');
						}
					}
					jQuery(window.forums_medium_forum_editor[key].elements[0]).focus();
					medium_editor.toggleClass('active');

				});
			}
			if ( jQuery( '.bbp_editor_reply_content' ).length ) {
				window.forums_medium_reply_editor = [];
				jQuery( '.bbp_editor_reply_content' ).each(function(i,element){
					var key = jQuery(element).data('key');
					window.forums_medium_reply_editor[key] = new window.MediumEditor(
						element,
						{
							placeholder: {
								text: window.bbpEditorJsStrs.type_reply,
								hideOnClick: true
							},
							toolbar: toolbarOptions,
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
								cleanTags: [ 'meta', 'div', 'main', 'section', 'article', 'aside', 'button', 'svg', 'canvas', 'figure', 'input', 'textarea', 'select', 'label', 'form', 'table', 'thead', 'tfooter', 'colgroup', 'col', 'tr', 'td', 'th', 'dl', 'dd', 'center', 'caption', 'nav' ],
								unwrapTags: [ 'ul', 'ol', 'li' ]
							},
							imageDragging: false
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
						}
					);

					//Add Click event to show / hide text formatting Toolbar
					jQuery( 'body' ).on('click', '.bbp-reply-form #whats-new-toolbar .show-toolbar', function(e) {
						e.preventDefault();
						var key = jQuery(e.currentTarget).closest('.bbp-reply-form').find('.bbp_editor_reply_content').data('key');
						var medium_editor = jQuery(e.currentTarget).closest('.bbp-form').find('.medium-editor-toolbar');
						jQuery(e.currentTarget).find('.toolbar-button').toggleClass('active');
						if( jQuery(e.currentTarget).find('.toolbar-button').hasClass('active') ) {
							jQuery(e.currentTarget).attr('data-bp-tooltip',jQuery(e.currentTarget).attr('data-bp-tooltip-hide'));
							if( window.forums_medium_reply_editor[key].exportSelection() !== null ){
								medium_editor.addClass('medium-editor-toolbar-active');
							}
						} else {
							jQuery(e.currentTarget).attr('data-bp-tooltip',jQuery(e.currentTarget).attr('data-bp-tooltip-show'));
							if( window.forums_medium_reply_editor[key].exportSelection() === null ) {
								medium_editor.removeClass('medium-editor-toolbar-active');
							}
						}
						jQuery(window.forums_medium_reply_editor[key].elements[0]).focus();
						medium_editor.toggleClass('active');

					});
				});
			}
			if ( jQuery( '.bbp_editor_topic_content' ).length ) {
				window.forums_medium_topic_editor = [];
				jQuery( '.bbp_editor_topic_content' ).each(function(i,element){
					var key = jQuery(element).data('key');
					window.forums_medium_topic_editor[key] = new window.MediumEditor(
						element,
						{
							placeholder: {
								text: window.bbpEditorJsStrs.type_topic,
								hideOnClick: true
							},
							toolbar: toolbarOptions,
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
								cleanTags: [ 'meta', 'div', 'main', 'section', 'article', 'aside', 'button', 'svg', 'canvas', 'figure', 'input', 'textarea', 'select', 'label', 'form', 'table', 'thead', 'tfooter', 'colgroup', 'col', 'tr', 'td', 'th', 'dl', 'dd', 'center', 'caption', 'nav' ],
								unwrapTags: [ 'ul', 'ol', 'li' ]
							},
							imageDragging: false
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

					//Add Click event to show / hide text formatting Toolbar
					jQuery( 'body' ).on('click', '.bbp-topic-form #whats-new-toolbar .show-toolbar', function(e) {
						e.preventDefault();
						var key = jQuery(e.currentTarget).closest('.bbp-topic-form').find('.bbp_editor_topic_content').data('key');
						var medium_editor = jQuery(e.currentTarget).closest('.bbp-form').find('.medium-editor-toolbar');
						jQuery(e.currentTarget).find('.toolbar-button').toggleClass('active');
						if( jQuery(e.currentTarget).find('.toolbar-button').hasClass('active') ) {
							jQuery(e.currentTarget).attr('data-bp-tooltip',jQuery(e.currentTarget).attr('data-bp-tooltip-hide'));
							if( window.forums_medium_topic_editor[key].exportSelection() !== null ){
								medium_editor.addClass('medium-editor-toolbar-active');
							}
						} else {
							jQuery(e.currentTarget).attr('data-bp-tooltip',jQuery(e.currentTarget).attr('data-bp-tooltip-show'));
							if( window.forums_medium_topic_editor[key].exportSelection() === null ) {
								medium_editor.removeClass('medium-editor-toolbar-active');
							}
						}
						jQuery(window.forums_medium_topic_editor[key].elements[0]).focus();
						medium_editor.toggleClass('active');

					});
				});
			}
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
	}
);
