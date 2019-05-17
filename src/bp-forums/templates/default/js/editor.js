jQuery(document).ready( function() {

	if ( typeof window.MediumEditor !== 'undefined' ) {
		var toolbarOptions = {
			buttons: ['bold', 'italic', 'unorderedlist','orderedlist', 'quote', 'anchor' ]
		};
		if ( jQuery( '#bbp_editor_forum_content' ).length ) {
			var forums_medium_forum_editor = new MediumEditor('#bbp_editor_forum_content',{
				placeholder: {
					text: wp.i18n.__('Description', 'buddyboss'),
					hideOnClick: true
				},
				toolbar: toolbarOptions
			});

			forums_medium_forum_editor.subscribe('editableInput', function (event, editorElement) {
				jQuery('#bbp_forum_content').val(forums_medium_forum_editor.getContent());
			});
		}
		if ( jQuery( '#bbp_editor_reply_content' ).length ) {
			var forums_medium_reply_editor = new MediumEditor('#bbp_editor_reply_content',{
				placeholder: {
					text: wp.i18n.__('Type your reply here', 'buddyboss'),
					hideOnClick: true
				},
				toolbar: toolbarOptions
			});

			forums_medium_reply_editor.subscribe('editableInput', function (event, editorElement) {
				jQuery('#bbp_reply_content').val(forums_medium_reply_editor.getContent());
			});
		}
		if ( jQuery( '#bbp_editor_topic_content' ).length ) {
			var forums_medium_topic_editor = new MediumEditor('#bbp_editor_topic_content',{
				placeholder: {
					text: wp.i18n.__('Type your discussion here', 'buddyboss'),
					hideOnClick: true
				},
				toolbar: toolbarOptions
			});

			forums_medium_topic_editor.subscribe('editableInput', function (event, editorElement) {
				jQuery('#bbp_topic_content').val(forums_medium_topic_editor.getContent());
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
	jQuery( '#bbp_topic_title' ).bind( 'keydown.editor-focus', function(e) {
		if ( e.which !== 9 ) {
			return;
		}

		if ( !e.ctrlKey && !e.altKey && !e.shiftKey ) {
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
	});

	/* Shift + tab from topic tags */
	jQuery( '#bbp_topic_tags' ).bind( 'keydown.editor-focus', function(e) {
		if ( e.which !== 9 ) {
			return;
		}

		if ( e.shiftKey && !e.ctrlKey && !e.altKey ) {
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
	});
});
