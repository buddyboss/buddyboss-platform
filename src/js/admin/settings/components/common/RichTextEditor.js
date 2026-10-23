/**
 * BuddyBoss Admin Settings 2.0 - Rich Text Editor (TinyMCE wrapper)
 *
 * Shared component for rendering a TinyMCE-based rich text editor field.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

import { useEffect, useRef } from '@wordpress/element';

/**
 * Forcefully remove any existing TinyMCE editor for the given ID.
 *
 * Cleans up TinyMCE, WordPress editor API, and QTags instances.
 * Shared between RichTextEditor and ActivityCommentModal.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {string} editorId Editor ID to remove.
 */
export function forceRemoveEditor( editorId ) {
	// Remove via TinyMCE directly.
	if ( window.tinymce ) {
		var existingEditor = window.tinymce.get( editorId );
		if ( existingEditor ) {
			existingEditor.remove();
		}
	}

	// Also remove via WordPress editor API.
	if ( window.wp && window.wp.editor ) {
		window.wp.editor.remove( editorId );
	}

	// Clean up quicktags instance.
	if ( window.QTags && window.QTags.instances ) {
		Object.keys( window.QTags.instances ).forEach( function ( key ) {
			if ( window.QTags.instances[ key ] && window.QTags.instances[ key ].id === editorId ) {
				delete window.QTags.instances[ key ];
			}
		} );
	}
}

/**
 * Convert TinyMCE HTML to the storage format used by plain-text fields.
 *
 * Mirrors what WordPress does on the editor's SaveContent event: `<br>` becomes
 * a line break and paragraphs become blank lines, while inline tags are kept.
 * Use for values that are rendered through wpautop() and edited elsewhere in a
 * plain textarea (e.g. group descriptions).
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {string} html Editor HTML.
 * @returns {string} Content with paragraphs and line breaks as newlines.
 */
export function removeEditorParagraphs( html ) {
	if ( window.wp && window.wp.editor && window.wp.editor.removep ) {
		return window.wp.editor.removep( html || '' );
	}
	return html;
}

/**
 * Convert stored plain-text content to editor HTML.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {string} text Stored content with newline-based formatting.
 * @returns {string} Content with paragraphs and `<br>` tags.
 */
function addEditorParagraphs( text ) {
	if ( window.wp && window.wp.editor && window.wp.editor.autop ) {
		return window.wp.editor.autop( text || '' );
	}
	return text;
}

/**
 * Rich Text Editor wrapper for TinyMCE.
 *
 * @param {Object}   props          Component props.
 * @param {string}   props.id       Editor ID.
 * @param {string}   props.label    Field label.
 * @param {string}   props.value    Current value.
 * @param {Function} props.onChange  Change handler.
 * @param {boolean}  props.autop    Store line breaks as newlines instead of `<p>`/`<br>` tags.
 * @returns {JSX.Element} Rich text editor.
 */
export function RichTextEditor( { id, label, value, onChange, autop } ) {
	var containerRef = useRef( null );
	var editorInitialized = useRef( false );

	// Store the initial value in a ref so TinyMCE init callback can access it.
	var initialValueRef = useRef( value );
	initialValueRef.current = value;

	// Keep onChange in a ref so the TinyMCE event handler always calls the latest callback.
	var onChangeRef = useRef( onChange );
	onChangeRef.current = onChange;

	var autopRef = useRef( autop );
	autopRef.current = autop;

	// Initialize TinyMCE on mount.
	useEffect( function () {
		if ( window.wp && window.wp.editor && ! editorInitialized.current ) {
			// Force-remove any stale editor instance for this ID first.
			forceRemoveEditor( id );

			// Small delay to ensure the textarea DOM element is ready.
			var timer = setTimeout( function () {
				var textarea = document.getElementById( id );
				if ( textarea ) {
					// Ensure textarea has the correct value before initializing.
					textarea.value = initialValueRef.current || '';

					window.wp.editor.initialize( id, {
						tinymce: {
							wpautop: true,
							toolbar1: 'formatselect,bold,italic,underline,blockquote,strikethrough,bullist,numlist,alignleft,aligncenter,alignright,undo,redo,link,fullscreen',
							toolbar2: '',
							height: 150,
							setup: function ( editor ) {
								// Explicitly set content when TinyMCE is fully ready.
								editor.on( 'init', function () {
									var initVal = initialValueRef.current || '';
									if ( autopRef.current ) {
										initVal = addEditorParagraphs( initVal );
									}
									if ( initVal !== editor.getContent() ) {
										editor.setContent( initVal );
									}
								} );

								editor.on( 'change keyup', function () {
									var content = editor.getContent();
									onChangeRef.current( autopRef.current ? removeEditorParagraphs( content ) : content );
								} );
							},
						},
						quicktags: {
							buttons: 'strong,em,link,block,del,ins,code',
						},
						mediaButtons: false,
					} );
					editorInitialized.current = true;
				}
			}, 100 );

			return function () {
				clearTimeout( timer );
			};
		}
	}, [ id ] );

	// Cleanup on unmount.
	useEffect( function () {
		var editorId = id;
		return function () {
			forceRemoveEditor( editorId );
			editorInitialized.current = false;
		};
	}, [ id ] );

	return (
		<div className="bb-admin-meta-field__editor-field" ref={ containerRef }>
			<label className="bb-admin-meta-field__label" htmlFor={ id }>
				{ label }
			</label>
			<div className="bb-admin-meta-field__editor-wrapper">
				<textarea
					id={ id }
					defaultValue={ value }
					rows={ 6 }
					className="bb-admin-meta-field__textarea"
				/>
			</div>
		</div>
	);
}
