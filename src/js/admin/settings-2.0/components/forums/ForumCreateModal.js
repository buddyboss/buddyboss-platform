/**
 * BuddyBoss Admin Settings 2.0 - Forum Create Modal
 *
 * Modal with Name, Permalink, Description, Status, Visibility, Parent Forum, Feature Image
 * fields for creating a forum.
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

import { createForum } from '../../utils/ajax';
import { AsyncSelectField } from '../fields/AsyncSelectField';
import { RichTextEditor, forceRemoveEditor } from '../common/RichTextEditor';

/**
 * Sanitize a string into a URL-friendly slug.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {string} str Input string.
 * @returns {string} Slug.
 */
function toSlug( str ) {
	return str
		.toLowerCase()
		.replace( /[^a-z0-9\s-]/g, '' )
		.replace( /[\s]+/g, '-' )
		.replace( /-+/g, '-' )
		.replace( /^-|-$/g, '' );
}

/**
 * Forum Create Modal Component
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {Object}   props              Component props.
 * @param {boolean}  props.isOpen       Whether the modal is open.
 * @param {Function} props.onClose      Close handler.
 * @param {Function} props.onCreated    Success handler (receives forum_id).
 * @returns {JSX.Element|null} Modal component or null.
 */
export function ForumCreateModal( { isOpen, onClose, onCreated } ) {
	var nameState = useState( '' );
	var name = nameState[ 0 ];
	var setName = nameState[ 1 ];

	var permalinkState = useState( '' );
	var permalink = permalinkState[ 0 ];
	var setPermalink = permalinkState[ 1 ];

	var permalinkEditedState = useState( false );
	var permalinkEdited = permalinkEditedState[ 0 ];
	var setPermalinkEdited = permalinkEditedState[ 1 ];

	var descriptionState = useState( '' );
	var description = descriptionState[ 0 ];
	var setDescription = descriptionState[ 1 ];

	var forumStatusState = useState( 'open' );
	var forumStatus = forumStatusState[ 0 ];
	var setForumStatus = forumStatusState[ 1 ];

	var visibilityState = useState( 'publish' );
	var visibility = visibilityState[ 0 ];
	var setVisibility = visibilityState[ 1 ];

	var parentIdState = useState( 0 );
	var parentId = parentIdState[ 0 ];
	var setParentId = parentIdState[ 1 ];

	var imageIdState = useState( 0 );
	var imageId = imageIdState[ 0 ];
	var setImageId = imageIdState[ 1 ];

	var imageUrlState = useState( '' );
	var imageUrl = imageUrlState[ 0 ];
	var setImageUrl = imageUrlState[ 1 ];

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
	 * Handle name change — auto-generate permalink if not manually edited.
	 *
	 * @param {string} val New name value.
	 */
	var handleNameChange = function ( val ) {
		setName( val );
		if ( ! permalinkEdited ) {
			setPermalink( toSlug( val ) );
		}
	};

	/**
	 * Handle permalink change — mark as manually edited.
	 *
	 * @param {string} val New permalink value.
	 */
	var handlePermalinkChange = function ( val ) {
		setPermalink( toSlug( val ) );
		setPermalinkEdited( true );
	};

	/**
	 * Open WordPress media picker for featured image.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	var handleSelectImage = function () {
		if ( ! window.wp || ! window.wp.media ) {
			return;
		}

		var frame = window.wp.media( {
			title: __( 'Select Feature Image', 'buddyboss' ),
			button: { text: __( 'Use Image', 'buddyboss' ) },
			multiple: false,
			library: { type: 'image' },
		} );

		frame.on( 'select', function () {
			var attachment = frame.state().get( 'selection' ).first().toJSON();
			setImageId( attachment.id );
			setImageUrl( attachment.url );
		} );

		frame.open();
	};

	/**
	 * Remove selected featured image.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	var handleRemoveImage = function () {
		setImageId( 0 );
		setImageUrl( '' );
	};

	/**
	 * Handle forum creation form submission.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	var handleCreate = function () {
		if ( ! name.trim() ) {
			setError( __( 'Forum name is required.', 'buddyboss' ) );
			return;
		}

		setIsSaving( true );
		setError( '' );

		createForum( {
			name: name.trim(),
			slug: permalink,
			description: description,
			visibility: visibility,
			forum_status: forumStatus,
			parent_id: parentId,
			image_id: imageId,
		} ).then( function ( response ) {
			if ( ! isMountedRef.current ) {
				return;
			}
			setIsSaving( false );
			if ( response.success ) {
				resetForm();
				if ( onCreated ) {
					onCreated( response.data.forum_id );
				}
			} else {
				setError( ( response.data && response.data.message ) || __( 'Failed to create forum.', 'buddyboss' ) );
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
		setName( '' );
		setPermalink( '' );
		setPermalinkEdited( false );
		setDescription( '' );
		setForumStatus( 'open' );
		setVisibility( 'publish' );
		setParentId( 0 );
		setImageId( 0 );
		setImageUrl( '' );
		setError( '' );

		// Reset TinyMCE editor content.
		if ( window.tinymce ) {
			var editor = window.tinymce.get( 'bb-forum-create-description' );
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
		forceRemoveEditor( 'bb-forum-create-description' );
		resetForm();
		onClose();
	};

	var statusOptions = [
		{ value: 'open', label: __( 'Open', 'buddyboss' ) },
		{ value: 'closed', label: __( 'Closed', 'buddyboss' ) },
	];

	var visibilityOptions = [
		{ value: 'publish', label: __( 'Public', 'buddyboss' ) },
		{ value: 'private', label: __( 'Private', 'buddyboss' ) },
		{ value: 'hidden', label: __( 'Hidden', 'buddyboss' ) },
	];

	var siteUrl = ( window.bbAdminData && window.bbAdminData.siteUrl ) || '';

	return (
		<Modal
			title={ __( 'Create New Forum', 'buddyboss' ) }
			onRequestClose={ handleClose }
			className="bb-forum-create-modal bb-admin-settings-modal"
			shouldCloseOnClickOutside={ false }
		>
			<div className="bb-forum-create-modal__body">
				{ error && (
					<p className="bb-forum-create-modal__error">{ error }</p>
				) }

				<TextControl
					label={ __( 'Forum Name', 'buddyboss' ) }
					value={ name }
					onChange={ handleNameChange }
					placeholder={ __( 'Enter forum name', 'buddyboss' ) }
					__nextHasNoMarginBottom
				/>

				<div className="bb-forum-create-modal__permalink-field">
					<TextControl
						label={ __( 'Permalink', 'buddyboss' ) }
						value={ permalink }
						onChange={ handlePermalinkChange }
						placeholder={ __( 'forum-slug', 'buddyboss' ) }
						__nextHasNoMarginBottom
					/>
					{ permalink && siteUrl && (
						<p className="bb-forum-create-modal__permalink-preview">
							{ siteUrl + '/forum/' + permalink + '/' }
						</p>
					) }
				</div>

				<div className="bb-forum-create-modal__row--separator">
					<RichTextEditor
						id="bb-forum-create-description"
						label={ __( 'Forum Description (Optional)', 'buddyboss' ) }
						value={ description }
						onChange={ setDescription }
					/>
				</div>

				<div className="bb-forum-create-modal__row bb-forum-create-modal__row--separator">
					<SelectControl
						label={ __( 'Status', 'buddyboss' ) }
						value={ forumStatus }
						options={ statusOptions }
						onChange={ setForumStatus }
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

				<div className="components-base-control bb-forum-create-modal__row--separator">
					<label className="components-base-control__label">
						{ __( 'Parent Forum', 'buddyboss' ) }
					</label>
					<AsyncSelectField
						value={ String( parentId ) }
						onChange={ function ( val ) {
							setParentId( parseInt( val, 10 ) || 0 );
						} }
						asyncAction="bb_admin_forum_autocomplete"
						placeholder={ __( 'None', 'buddyboss' ) }
					/>
				</div>

				<div className="bb-forum-create-modal__image-field">
					<label className="components-base-control__label">
						{ __( 'Feature Image (Optional)', 'buddyboss' ) }
					</label>
					{ imageUrl ? (
						<div className="bb-forum-create-modal__image-preview">
							<img src={ imageUrl } alt="" />
							<div className="bb-forum-create-modal__image-actions">
								<Button
									variant="secondary"
									onClick={ handleSelectImage }
									className="bb-forum-create-modal__replace-image"
								>
									{ __( 'Replace', 'buddyboss' ) }
								</Button>
								<Button
									variant="secondary"
									isDestructive
									onClick={ handleRemoveImage }
									className="bb-forum-create-modal__remove-image"
								>
									{ __( 'Reset', 'buddyboss' ) }
								</Button>
							</div>
						</div>
					) : (
						<button
							type="button"
							onClick={ handleSelectImage }
							className="bb-forum-create-modal__upload-zone"
						>
							<span className="bb-forum-create-modal__upload-icon"><i className="bb-icons-rl-plus"></i></span>
						</button>
					) }
					<p className="bb-forum-create-modal__image-help">
						{ __( 'For best results, use an image at least 1200px by 300px or higher.', 'buddyboss' ) }
					</p>
				</div>
			</div>

			<div className="bb-forum-create-modal__footer bb-admin-settings-modal__footer">
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
					disabled={ isSaving || ! name.trim() }
				>
					{ __( 'Save', 'buddyboss' ) }
				</Button>
			</div>
		</Modal>
	);
}
