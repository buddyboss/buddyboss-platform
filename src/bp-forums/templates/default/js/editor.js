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
								cleanReplacements: [],
								cleanAttrs: ['class', 'style', 'dir'],
								cleanTags: ['meta'],
								unwrapTags: []
							}
						}
					);

					window.forums_medium_forum_editor[key].subscribe(
						'editableInput',
						function ( event ) {
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
									[new RegExp(/<div>/gi), '<p>'],
									[new RegExp(/<\/div>/gi), '</p>'],
									[new RegExp(/<h[1-6]/gi), '<b'],
									[new RegExp(/<\/h[1-6]>/gi), '</b>'],
								],
								cleanAttrs: ['class', 'style', 'dir'],
								cleanTags: ['meta'],
								unwrapTags: []
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
									[new RegExp(/<div>/gi), '<p>'],
									[new RegExp(/<\/div>/gi), '</p>'],
									[new RegExp(/<h[1-6]/gi), '<b'],
									[new RegExp(/<\/h[1-6]>/gi), '</b>'],
								],
								cleanAttrs: ['class', 'style', 'dir'],
								cleanTags: ['meta'],
								unwrapTags: []
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
