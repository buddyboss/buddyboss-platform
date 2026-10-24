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
 * Open WordPress's link modal for a TinyMCE editor.
 *
 * wpLink only treats a link as the one being edited when it carries
 * data-wplink-edit, which core's WP_Link command normally adds first.
 * Mark the link under the selection the same way, so editing a link opens
 * with its URL and "Open link in a new tab" filled in and keeps its target.
 * Without a link, wpLink inserts a new one around the selection.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {Object} editor TinyMCE editor instance.
 */
function openLinkModal( editor ) {
	var linkNode = editor.dom.getParent( editor.selection.getStart(), 'a[href]' );

	// Same as core's getSelectedLink(): the selection can wrap a whole link.
	if ( ! linkNode ) {
		var html = editor.selection.getContent( { format: 'raw' } );
		var href = html && -1 !== html.indexOf( '</a>' ) ? html.match( /href="([^">]+)"/ ) : null;

		if ( href && href[ 1 ] ) {
			linkNode = editor.$( 'a[href="' + href[ 1 ] + '"]', editor.selection.getStart() )[ 0 ];

			if ( linkNode ) {
				editor.selection.select( linkNode );
			}
		}
	}

	// Clear a mark left by an earlier open, then mark the link being edited.
	editor.$( 'a[data-wplink-edit]' ).attr( 'data-wplink-edit', null );

	if ( linkNode ) {
		editor.dom.setAttribs( linkNode, { 'data-wplink-edit': true } );
	}

	window.wpLink.open( editor.id );
}

/**
 * Open WordPress's link modal from an editor's link button and Ctrl/Cmd+K.
 *
 * The wplink plugin's inline link toolbar hides itself in these editors,
 * because it expects the editor toolbar above the text area and the Settings
 * screens show it below. Call from the TinyMCE setup callback; the command is
 * registered on init, after the wplink plugin has added its own WP_Link.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {Object} editor TinyMCE editor instance.
 */
export function addLinkModalCommand( editor ) {
	editor.on( 'init', function () {
		if ( window.wpLink ) {
			editor.addCommand( 'WP_Link', function () {
				openLinkModal( editor );
			} );
		}
	} );
}

/**
 * Get the HTML content of a TinyMCE editor, including Code tab edits.
 *
 * For content that is stored as HTML (activity posts and comments, discussion
 * descriptions, forum replies), because their front-end editors load the
 * stored value as it is, without autop. In the Code tab TinyMCE is hidden and
 * its content is stale, so load the textarea into it first, the same way
 * switching back to the Visual tab does, then read it.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {string} editorId Editor ID.
 * @param {string} fallback Value to return when the editor is not available.
 * @returns {string} Editor content.
 */
export function getRichTextEditorHtml( editorId, fallback ) {
	var editor = window.tinymce ? window.tinymce.get( editorId ) : null;

	if ( ! editor ) {
		return fallback;
	}

	if ( editor.isHidden() ) {
		editor.load();
	}

	return editor.getContent();
}

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
 * Get the saved content of a RichTextEditor instance.
 *
 * Uses wp.editor.getContent(), which calls editor.save() so WordPress's own
 * SaveContent conversion turns <p>/<br> back into line breaks, then returns
 * the textarea value. This also returns edits made in the Text tab, where
 * TinyMCE is hidden and its own getContent() is stale.
 *
 * When the editor is not mounted (for example, the modal switched to another
 * tab), the fallback is the last value the editor sent through onChange,
 * which is TinyMCE HTML. Run it through the same removep() conversion so
 * both paths return the same format.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {string} editorId Editor ID.
 * @param {string} fallback Value to return when the editor is not available.
 * @returns {string} Editor content.
 */
export function getRichTextEditorContent( editorId, fallback ) {
	if ( ! window.wp || ! window.wp.editor ) {
		return fallback;
	}

	if ( 'function' === typeof window.wp.editor.getContent ) {
		var content = window.wp.editor.getContent( editorId );
		if ( 'string' === typeof content ) {
			return content;
		}
	}

	if ( 'string' === typeof fallback && /<(p|br)\b/i.test( fallback ) && 'function' === typeof window.wp.editor.removep ) {
		return window.wp.editor.removep( fallback );
	}

	return fallback;
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
							toolbar1: 'formatselect,bold,italic,underline,blockquote,strikethrough,bullist,numlist,alignleft,aligncenter,alignright,undo,redo,link,fullscreen',
							toolbar2: '',
							height: 150,
							setup: function ( editor ) {
								// Explicitly set content when TinyMCE is fully ready.
								editor.on( 'init', function () {
									var initVal = initialValueRef.current || '';

									// The stored value is plain text with raw line breaks (no
									// <p>/<br> markup) — TinyMCE's native load-from-textarea
									// path runs it through the "wordpress" plugin's wpautop
									// conversion (see event.load in BeforeSetContent), which is
									// why line breaks display correctly right after init. But an
									// explicit setContent() call like this one is not treated as
									// a "load" event, so it skips that conversion and raw line
									// breaks collapse into whitespace once written back as HTML.
									// Run the same autop conversion here first, matching how
									// WordPress core does the same thing in wp-admin/js/widgets/
									// text-widgets.js (editor.setContent( wp.oldEditor.autop( ... ) )).
									var autopVal = ( window.wp.editor.autop ) ? window.wp.editor.autop( initVal ) : initVal;
									if ( autopVal !== editor.getContent() ) {
										editor.setContent( autopVal );
									}
								} );

								editor.on( 'change keyup', function () {
									onChangeRef.current( editor.getContent() );
								} );

								addLinkModalCommand( editor );
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
