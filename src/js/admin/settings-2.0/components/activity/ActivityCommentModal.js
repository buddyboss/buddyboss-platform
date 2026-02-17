/**
 * BuddyBoss Admin Settings 2.0 - Activity Comment Modal
 *
 * Modal for posting a comment on an activity from the admin activity list.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

import { useState, useEffect, useRef } from '@wordpress/element';
import {
	Modal,
	Button,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Activity Comment Modal Component
 *
 * @param {Object}   props           Component props.
 * @param {boolean}  props.isOpen    Whether the modal is open.
 * @param {Object}   props.activity  Activity object to comment on.
 * @param {Function} props.onClose   Close handler.
 * @param {Function} props.onSave    Save handler (receives { activity_id, content }).
 * @param {boolean}  props.isSaving  Whether save is in progress.
 * @returns {JSX.Element|null} Modal component or null.
 */
export function ActivityCommentModal( { isOpen, activity, onClose, onSave, isSaving } ) {
	var contentState = useState( '' );
	var content = contentState[ 0 ];
	var setContent = contentState[ 1 ];

	var errorState = useState( '' );
	var error = errorState[ 0 ];
	var setError = errorState[ 1 ];

	var editorInitialized = useRef( false );
	var editorId = activity ? 'bb-admin-comment-' + activity.id : 'bb-admin-comment-0';

	/**
	 * Forcefully remove any existing TinyMCE editor for the given ID.
	 *
	 * @param {string} id Editor ID to remove.
	 */
	var forceRemoveEditor = function ( id ) {
		if ( window.tinymce ) {
			var existingEditor = window.tinymce.get( id );
			if ( existingEditor ) {
				existingEditor.remove();
			}
		}

		if ( window.wp && window.wp.editor ) {
			window.wp.editor.remove( id );
		}

		if ( window.QTags && window.QTags.instances ) {
			Object.keys( window.QTags.instances ).forEach( function ( key ) {
				if ( window.QTags.instances[ key ] && window.QTags.instances[ key ].id === id ) {
					delete window.QTags.instances[ key ];
				}
			} );
		}
	};

	// Reset form when activity changes.
	useEffect( function () {
		if ( isOpen && activity ) {
			setContent( '' );
			setError( '' );
		}
	}, [ isOpen, activity ] );

	// Initialize TinyMCE when modal opens.
	useEffect( function () {
		if ( isOpen && activity && window.wp && window.wp.editor && ! editorInitialized.current ) {
			forceRemoveEditor( editorId );

			var timer = setTimeout( function () {
				var textarea = document.getElementById( editorId );
				if ( textarea ) {
					textarea.value = '';

					window.wp.editor.initialize( editorId, {
						tinymce: {
							wpautop: true,
							toolbar1: 'bold,italic,bullist,numlist,blockquote,link,unlink,code',
							toolbar2: '',
							height: 150,
							setup: function ( editor ) {
								editor.on( 'change keyup', function () {
									setContent( editor.getContent() );
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
	}, [ isOpen, activity, editorId ] );

	// Cleanup on unmount or close.
	useEffect( function () {
		var currentEditorId = editorId;
		return function () {
			forceRemoveEditor( currentEditorId );
			editorInitialized.current = false;
		};
	}, [ editorId ] );

	if ( ! isOpen || ! activity ) {
		return null;
	}

	var handleSave = function () {
		// Pull latest content from TinyMCE.
		var latestContent = content;
		if ( window.tinymce ) {
			var editorInstance = window.tinymce.get( editorId );
			if ( editorInstance ) {
				latestContent = editorInstance.getContent();
			}
		}

		if ( ! latestContent || ! latestContent.trim() ) {
			setError( __( 'Please enter a comment.', 'buddyboss' ) );
			return;
		}

		setError( '' );
		onSave( {
			activity_id: activity.id,
			content: latestContent,
		} );
	};

	return (
		<Modal
			title={ __( 'Comment on Activity', 'buddyboss' ) + ' (ID #' + activity.id + ')' }
			onRequestClose={ onClose }
			className="bb-activity-comment-modal bb-admin-settings-modal"
			shouldCloseOnClickOutside={ false }
		>
			<div className="bb-activity-comment-modal__body">
				{ error && (
					<p className="bb-activity-comment-modal__error">{ error }</p>
				) }

				<div className="bb-admin-meta-field__editor-field">
					<label className="bb-admin-meta-field__label" htmlFor={ editorId }>
						{ __( 'Comment', 'buddyboss' ) }
					</label>
					<div className="bb-admin-meta-field__editor-wrapper">
						<textarea
							id={ editorId }
							defaultValue=""
							rows={ 6 }
							className="bb-admin-meta-field__textarea"
						/>
					</div>
				</div>
			</div>

			<div className="bb-activity-comment-modal__footer bb-admin-settings-modal__footer">
				<div className="bb-activity-comment-modal__footer-left">
					{ ( activity.permalink || activity.primary_link ) && (
						<a
							href={ activity.permalink || activity.primary_link }
							target="_blank"
							rel="noopener noreferrer"
							className="bb-activity-comment-modal__view-link"
						>
							{ __( 'View Activity', 'buddyboss' ) }
							<i className="bb-icons-rl bb-icons-rl-external-link" style={ { marginLeft: '4px', fontSize: '14px' } }></i>
						</a>
					) }
				</div>
				<div className="bb-activity-comment-modal__footer-right">
					<Button
						variant="secondary"
						onClick={ onClose }
						disabled={ isSaving }
					>
						{ __( 'Cancel', 'buddyboss' ) }
					</Button>
					<Button
						variant="primary"
						onClick={ handleSave }
						isBusy={ isSaving }
						disabled={ isSaving }
					>
						{ __( 'Save', 'buddyboss' ) }
					</Button>
				</div>
			</div>
		</Modal>
	);
}
