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
 * Rich Text Editor wrapper for TinyMCE.
 *
 * @param {Object}   props          Component props.
 * @param {string}   props.id       Editor ID.
 * @param {string}   props.label    Field label.
 * @param {string}   props.value    Current value.
 * @param {Function} props.onChange  Change handler.
 * @returns {JSX.Element} Rich text editor.
 */
export function RichTextEditor( { id, label, value, onChange } ) {
	var containerRef = useRef( null );
	var editorInitialized = useRef( false );

	// Store the initial value in a ref so TinyMCE init callback can access it.
	var initialValueRef = useRef( value );
	initialValueRef.current = value;

	// Keep onChange in a ref so the TinyMCE event handler always calls the latest callback.
	var onChangeRef = useRef( onChange );
	onChangeRef.current = onChange;

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
							toolbar1: 'bold,italic,underline,blockquote,strikethrough,bullist,numlist,alignleft,aligncenter,alignright,undo,redo,link,fullscreen',
							toolbar2: '',
							height: 150,
							setup: function ( editor ) {
								// Explicitly set content when TinyMCE is fully ready.
								editor.on( 'init', function () {
									var initVal = initialValueRef.current || '';
									if ( initVal !== editor.getContent() ) {
										editor.setContent( initVal );
									}
								} );

								editor.on( 'change keyup', function () {
									onChangeRef.current( editor.getContent() );
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
