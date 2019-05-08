jQuery(document).ready( function() {

	if ( typeof window.Quill !== 'undefined' ) {
		var toolbarOptions = [
			['bold', 'italic'],        // toggled buttons
			[{ 'list': 'ordered'}, { 'list': 'bullet' }],
			['blockquote','link']
		];
		if ( jQuery( '#bbp_editor_forum_content' ).length ) {
			var forums_quill_forum_editor = new window.Quill('#bbp_editor_forum_content', {
				modules: {
					toolbar: toolbarOptions
				},
				theme: 'bubble',
				placeholder: wp.i18n.__('Description', 'buddyboss')
			});

			forums_quill_forum_editor.on('text-change', function() {
				jQuery('#bbp_forum_content').val(forums_quill_forum_editor.container.firstChild.innerHTML);
			});
		}
		if ( jQuery( '#bbp_editor_reply_content' ).length ) {
			var forums_quill_reply_editor = new window.Quill('#bbp_editor_reply_content', {
				modules: {
					toolbar: toolbarOptions
				},
				theme: 'bubble',
				placeholder: wp.i18n.__('Type your reply here', 'buddyboss')
			});

			forums_quill_reply_editor.on('text-change', function() {
				jQuery('#bbp_reply_content').val(forums_quill_reply_editor.container.firstChild.innerHTML);
			});
		}
		if ( jQuery( '#bbp_editor_topic_content' ).length ) {
			var forums_quill_topic_editor = new window.Quill('#bbp_editor_topic_content', {
				modules: {
					toolbar: toolbarOptions
				},
				theme: 'bubble',
				placeholder: wp.i18n.__('type your discussion here', 'buddyboss')
			});

			forums_quill_topic_editor.on('text-change', function() {
				jQuery('#bbp_topic_content').val(forums_quill_topic_editor.container.firstChild.innerHTML);
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
