/**
 * BuddyBoss Admin Settings 2.0 - Reply Create Modal
 *
 * Modal with Description, Forum, Discussion, Reply to, Visibility
 * fields for creating a reply.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

import { useState, useRef, useEffect } from '@wordpress/element';
import {
	Modal,
	Button,
	SelectControl,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';

import { createReply } from '../../utils/ajax';
import { AsyncSelectField } from '../fields/AsyncSelectField';
import { RichTextEditor, forceRemoveEditor } from '../common/RichTextEditor';

/**
 * Reply Create Modal Component
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {Object}   props           Component props.
 * @param {boolean}  props.isOpen    Whether the modal is open.
 * @param {Function} props.onClose   Close handler.
 * @param {Function} props.onCreated Success handler (receives reply_id).
 * @returns {JSX.Element|null} Modal component or null.
 */
export function ReplyCreateModal( { isOpen, onClose, onCreated } ) {
	var contentState = useState( '' );
	var content = contentState[ 0 ];
	var setContent = contentState[ 1 ];

	var forumIdState = useState( 0 );
	var forumId = forumIdState[ 0 ];
	var setForumId = forumIdState[ 1 ];

	var topicIdState = useState( 0 );
	var topicId = topicIdState[ 0 ];
	var setTopicId = topicIdState[ 1 ];

	var replyToState = useState( 0 );
	var replyTo = replyToState[ 0 ];
	var setReplyTo = replyToState[ 1 ];

	var visibilityState = useState( 'publish' );
	var visibility = visibilityState[ 0 ];
	var setVisibility = visibilityState[ 1 ];

	var isSavingState = useState( false );
	var isSaving = isSavingState[ 0 ];
	var setIsSaving = isSavingState[ 1 ];

	var errorState = useState( '' );
	var error = errorState[ 0 ];
	var setError = errorState[ 1 ];

	// Reset keys to force AsyncSelectField re-mount when forum/topic changes.
	var discussionKeyState = useState( 0 );
	var discussionKey = discussionKeyState[ 0 ];
	var setDiscussionKey = discussionKeyState[ 1 ];

	var replyToKeyState = useState( 0 );
	var replyToKey = replyToKeyState[ 0 ];
	var setReplyToKey = replyToKeyState[ 1 ];

	// Track mounted state.
	var isMountedRef = useRef( true );
	useEffect( function () {
		isMountedRef.current = true;
		return function () {
			isMountedRef.current = false;
		};
	}, [] );

	if ( ! isOpen ) {
		return null;
	}

	/**
	 * Handle reply creation form submission.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	var handleCreate = function () {
		if ( ! content.trim() ) {
			setError( __( 'Description is required.', 'buddyboss' ) );
			return;
		}

		if ( ! topicId ) {
			setError( __( 'Discussion is required.', 'buddyboss' ) );
			return;
		}

		setIsSaving( true );
		setError( '' );

		createReply( {
			content: content,
			forum_id: forumId,
			topic_id: topicId,
			reply_to: replyTo,
			visibility: visibility,
		} ).then( function ( response ) {
			if ( ! isMountedRef.current ) {
				return;
			}
			setIsSaving( false );
			if ( response.success ) {
				resetForm();
				if ( onCreated ) {
					onCreated( response.data.reply_id );
				}
			} else {
				setError( ( response.data && response.data.message ) || __( 'Failed to create reply.', 'buddyboss' ) );
			}
		} ).catch( function () {
			if ( ! isMountedRef.current ) {
				return;
			}
			setIsSaving( false );
			setError( __( 'An error occurred. Please try again.', 'buddyboss' ) );
		} );
	};

	/**
	 * Reset all form fields.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	var resetForm = function () {
		setContent( '' );
		setForumId( 0 );
		setTopicId( 0 );
		setReplyTo( 0 );
		setVisibility( 'publish' );
		setError( '' );

		// Reset TinyMCE editor content.
		if ( window.tinymce ) {
			var editor = window.tinymce.get( 'bb-reply-create-description' );
			if ( editor ) {
				editor.setContent( '' );
			}
		}
	};

	/**
	 * Handle modal close and reset form state.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	var handleClose = function () {
		forceRemoveEditor( 'bb-reply-create-description' );
		resetForm();
		onClose();
	};

	/**
	 * Handle forum change — reset discussion and reply-to.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {string} val Selected forum ID.
	 */
	var handleForumChange = function ( val ) {
		var newForumId = parseInt( val, 10 ) || 0;
		setForumId( newForumId );
		setTopicId( 0 );
		setReplyTo( 0 );
		setDiscussionKey( function ( prev ) {
			return prev + 1;
		} );
		setReplyToKey( function ( prev ) {
			return prev + 1;
		} );
	};

	/**
	 * Handle discussion change — reset reply-to.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {string} val Selected topic ID.
	 */
	var handleTopicChange = function ( val ) {
		var newTopicId = parseInt( val, 10 ) || 0;
		setTopicId( newTopicId );
		setReplyTo( 0 );
		setReplyToKey( function ( prev ) {
			return prev + 1;
		} );
	};

	var visibilityOptions = [
		{ value: 'publish', label: __( 'Public', 'buddyboss' ) },
		{ value: 'private', label: __( 'Private', 'buddyboss' ) },
		{ value: 'hidden', label: __( 'Hidden', 'buddyboss' ) },
	];

	return (
		<Modal
			title={ __( 'Create New Reply', 'buddyboss' ) }
			onRequestClose={ handleClose }
			className="bb-reply-modal bb-reply-create-modal bb-admin-settings-modal"
			shouldCloseOnClickOutside={ false }
		>
			<div className="bb-reply-modal__body">
				{ error && (
					<p className="bb-reply-modal__error">{ error }</p>
				) }

				<div className="bb-reply-modal__row--separator">
					<RichTextEditor
						id="bb-reply-create-description"
						label={ __( 'Description', 'buddyboss' ) }
						value={ content }
						onChange={ setContent }
					/>
				</div>

				<div className="components-base-control">
					<label className="components-base-control__label">
						{ __( 'Forum', 'buddyboss' ) }
					</label>
					<AsyncSelectField
						value={ String( forumId ) }
						onChange={ handleForumChange }
						asyncAction="bb_admin_forum_autocomplete"
						placeholder={ __( 'Select Forum', 'buddyboss' ) }
					/>
				</div>

				<div className="components-base-control">
					<label className="components-base-control__label">
						{ __( 'Discussion', 'buddyboss' ) }
					</label>
					<AsyncSelectField
						key={ 'discussion-' + discussionKey }
						value={ String( topicId ) }
						onChange={ handleTopicChange }
						asyncAction="bb_admin_discussion_autocomplete"
						asyncExtraParams={ forumId ? { forum_id: forumId } : {} }
						placeholder={ __( 'Select Discussion', 'buddyboss' ) }
					/>
				</div>

				<div className="components-base-control bb-reply-modal__row--separator">
					<label className="components-base-control__label">
						{ __( 'Reply to', 'buddyboss' ) }
					</label>
					<AsyncSelectField
						key={ 'reply-to-' + replyToKey }
						value={ String( replyTo ) }
						onChange={ function ( val ) {
							setReplyTo( parseInt( val, 10 ) || 0 );
						} }
						asyncAction="bb_admin_reply_autocomplete"
						asyncExtraParams={ topicId ? { topic_id: topicId } : {} }
						placeholder={ __( 'Select Reply', 'buddyboss' ) }
					/>
				</div>

				<SelectControl
					label={ __( 'Visibility', 'buddyboss' ) }
					value={ visibility }
					options={ visibilityOptions }
					onChange={ setVisibility }
					__nextHasNoMarginBottom
				/>
			</div>

			<div className="bb-reply-modal__footer bb-admin-settings-modal__footer">
				<Button
					variant="secondary"
					onClick={ handleClose }
					disabled={ isSaving }
				>
					{ __( 'Cancel', 'buddyboss' ) }
				</Button>
				<Button
					variant="primary"
					onClick={ handleCreate }
					isBusy={ isSaving }
					disabled={ isSaving || ! content.trim() || ! topicId }
				>
					{ __( 'Save', 'buddyboss' ) }
				</Button>
			</div>
		</Modal>
	);
}
