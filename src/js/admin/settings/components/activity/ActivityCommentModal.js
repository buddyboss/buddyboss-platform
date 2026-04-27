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
import { safeUrl } from '../../utils/sanitize';
import { forceRemoveEditor } from '../common/RichTextEditor';

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

			// Wait for the WordPress Modal to finish rendering before TinyMCE targets the textarea.
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

	// Cleanup on unmount or editorId change.
	useEffect( function () {
		var currentEditorId = editorId;
		return function () {
			forceRemoveEditor( currentEditorId );
			editorInitialized.current = false;
		};
	}, [ editorId ] );

	// Reset editorInitialized when the modal closes so TinyMCE re-initialises
	// if the same activity is re-opened (editorId unchanged, so the effect above
	// would not fire again without this reset).
	useEffect( function () {
		if ( ! isOpen ) {
			editorInitialized.current = false;
		}
	}, [ isOpen ] );

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
					<p className="bb-activity-comment-modal__error" role="alert">{ error }</p>
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
							href={ safeUrl( activity.permalink || activity.primary_link ) }
							target="_blank"
							rel="noopener noreferrer"
							className="bb-activity-comment-modal__view-link"
						>
							{ __( 'View Activity', 'buddyboss' ) }
							<i className="bb-icons-rl bb-icons-rl-arrow-up-right"></i>
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
