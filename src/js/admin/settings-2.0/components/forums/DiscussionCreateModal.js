/**
 * BuddyBoss Admin Settings 2.0 - Discussion Create Modal
 *
 * Modal with Title, Description, Forum, Type, Status, Visibility, Tags
 * fields for creating a discussion (topic).
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

import { useState, useRef, useEffect } from '@wordpress/element';
import {
	Modal,
	Button,
	TextControl,
	SelectControl,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';

import { createDiscussion } from '../../utils/ajax';
import { AsyncSelectField } from '../fields/AsyncSelectField';
import { RichTextEditor, forceRemoveEditor } from '../common/RichTextEditor';
import { TagsAutocomplete } from './TagsAutocomplete';

/**
 * Discussion Create Modal Component
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {Object}   props           Component props.
 * @param {boolean}  props.isOpen    Whether the modal is open.
 * @param {Function} props.onClose   Close handler.
 * @param {Function} props.onCreated Success handler (receives topic_id).
 * @returns {JSX.Element|null} Modal component or null.
 */
export function DiscussionCreateModal( { isOpen, onClose, onCreated } ) {
	var titleState = useState( '' );
	var title = titleState[ 0 ];
	var setTitle = titleState[ 1 ];

	var descriptionState = useState( '' );
	var description = descriptionState[ 0 ];
	var setDescription = descriptionState[ 1 ];

	var forumIdState = useState( 0 );
	var forumId = forumIdState[ 0 ];
	var setForumId = forumIdState[ 1 ];

	var typeState = useState( 'normal' );
	var type = typeState[ 0 ];
	var setType = typeState[ 1 ];

	var topicStatusState = useState( 'open' );
	var topicStatus = topicStatusState[ 0 ];
	var setTopicStatus = topicStatusState[ 1 ];

	var visibilityState = useState( 'publish' );
	var visibility = visibilityState[ 0 ];
	var setVisibility = visibilityState[ 1 ];

	var tagsState = useState( '' );
	var tags = tagsState[ 0 ];
	var setTags = tagsState[ 1 ];

	var isSavingState = useState( false );
	var isSaving = isSavingState[ 0 ];
	var setIsSaving = isSavingState[ 1 ];

	var errorState = useState( '' );
	var error = errorState[ 0 ];
	var setError = errorState[ 1 ];

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
	 * Handle discussion creation form submission.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	var handleCreate = function () {
		if ( ! title.trim() ) {
			setError( __( 'Discussion title is required.', 'buddyboss' ) );
			return;
		}

		if ( ! forumId ) {
			setError( __( 'Forum is required.', 'buddyboss' ) );
			return;
		}

		setIsSaving( true );
		setError( '' );

		createDiscussion( {
			title: title.trim(),
			description: description,
			forum_id: forumId,
			type: type,
			topic_status: topicStatus,
			visibility: visibility,
			tags: tags,
		} ).then( function ( response ) {
			if ( ! isMountedRef.current ) {
				return;
			}
			setIsSaving( false );
			if ( response.success ) {
				resetForm();
				if ( onCreated ) {
					onCreated( response.data.topic_id );
				}
			} else {
				setError( ( response.data && response.data.message ) || __( 'Failed to create discussion.', 'buddyboss' ) );
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
		setTitle( '' );
		setDescription( '' );
		setForumId( 0 );
		setType( 'normal' );
		setTopicStatus( 'open' );
		setVisibility( 'publish' );
		setTags( '' );
		setError( '' );

		// Reset TinyMCE editor content.
		if ( window.tinymce ) {
			var editor = window.tinymce.get( 'bb-discussion-create-description' );
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
		forceRemoveEditor( 'bb-discussion-create-description' );
		resetForm();
		onClose();
	};

	var typeOptions = [
		{ value: 'normal', label: __( 'Normal', 'buddyboss' ) },
		{ value: 'sticky', label: __( 'Sticky', 'buddyboss' ) },
	];

	var statusOptions = [
		{ value: 'open', label: __( 'Open', 'buddyboss' ) },
		{ value: 'closed', label: __( 'Closed', 'buddyboss' ) },
	];

	var visibilityOptions = [
		{ value: 'publish', label: __( 'Public', 'buddyboss' ) },
		{ value: 'private', label: __( 'Private', 'buddyboss' ) },
		{ value: 'hidden', label: __( 'Hidden', 'buddyboss' ) },
	];

	return (
		<Modal
			title={ __( 'Start New Discussion', 'buddyboss' ) }
			onRequestClose={ handleClose }
			className="bb-discussion-create-modal bb-admin-settings-modal"
			shouldCloseOnClickOutside={ false }
		>
			<div className="bb-discussion-create-modal__body">
				{ error && (
					<p className="bb-discussion-create-modal__error">{ error }</p>
				) }

				<TextControl
					label={ __( 'Title', 'buddyboss' ) }
					value={ title }
					onChange={ setTitle }
					placeholder={ __( 'Enter discussion title', 'buddyboss' ) }
					__nextHasNoMarginBottom
				/>

				<div className="bb-discussion-create-modal__row--separator">
					<RichTextEditor
						id="bb-discussion-create-description"
						label={ __( 'Description', 'buddyboss' ) }
						value={ description }
						onChange={ setDescription }
					/>
				</div>

				<div className="components-base-control">
					<label className="components-base-control__label">
						{ __( 'Forum', 'buddyboss' ) }
					</label>
					<AsyncSelectField
						value={ String( forumId ) }
						onChange={ function ( val ) {
							setForumId( parseInt( val, 10 ) || 0 );
						} }
						asyncAction="bb_admin_forum_autocomplete"
						placeholder={ __( 'Select Forum', 'buddyboss' ) }
					/>
				</div>

				<div className="bb-discussion-create-modal__row--separator">
					<SelectControl
						label={ __( 'Type', 'buddyboss' ) }
						value={ type }
						options={ typeOptions }
						onChange={ setType }
						__nextHasNoMarginBottom
					/>
				</div>

				<div className="bb-discussion-create-modal__row bb-discussion-create-modal__row--separator">
					<SelectControl
						label={ __( 'Status', 'buddyboss' ) }
						value={ topicStatus }
						options={ statusOptions }
						onChange={ setTopicStatus }
						__nextHasNoMarginBottom
					/>
					<SelectControl
						label={ __( 'Visibility', 'buddyboss' ) }
						value={ visibility }
						options={ visibilityOptions }
						onChange={ setVisibility }
						__nextHasNoMarginBottom
					/>
				</div>

				<TagsAutocomplete
					label={ __( 'Tags (Optional)', 'buddyboss' ) }
					value={ tags }
					onChange={ setTags }
					placeholder={ __( 'Enter tags, separated by commas', 'buddyboss' ) }
				/>
			</div>

			<div className="bb-discussion-create-modal__footer bb-admin-settings-modal__footer">
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
					disabled={ isSaving || ! title.trim() || ! forumId }
				>
					{ __( 'Save', 'buddyboss' ) }
				</Button>
			</div>
		</Modal>
	);
}
