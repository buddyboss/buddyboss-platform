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

import { createForum, uploadForumImage } from '../../utils/ajax';
import { toSlug } from '../../utils/format';
import { safeUrl } from '../../utils/sanitize';
import { AsyncSelectField } from '../fields/AsyncSelectField';
import { RichTextEditor, forceRemoveEditor } from '../common/RichTextEditor';

/**
 * Forum Create Modal Component
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {Object}   props                Component props.
 * @param {boolean}  props.isOpen         Whether the modal is open.
 * @param {Function} props.onClose        Close handler.
 * @param {Function} props.onCreated      Success handler (receives forum_id).
 * @param {string}   props.forumBaseSlug  Forum base slug from _bbp_forum_slug option.
 * @returns {JSX.Element|null} Modal component or null.
 */
export function ForumCreateModal( { isOpen, onClose, onCreated, forumBaseSlug } ) {
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

	var forumTypeState = useState( 'forum' );
	var forumType = forumTypeState[ 0 ];
	var setForumType = forumTypeState[ 1 ];

	var parentIdState = useState( 0 );
	var parentId = parentIdState[ 0 ];
	var setParentId = parentIdState[ 1 ];

	var orderState = useState( 0 );
	var order = orderState[ 0 ];
	var setOrder = orderState[ 1 ];

	var imageIdState = useState( 0 );
	var imageId = imageIdState[ 0 ];
	var setImageId = imageIdState[ 1 ];

	var imageUrlState = useState( '' );
	var imageUrl = imageUrlState[ 0 ];
	var setImageUrl = imageUrlState[ 1 ];

	var isUploadingState = useState( false );
	var isUploading = isUploadingState[ 0 ];
	var setIsUploading = isUploadingState[ 1 ];

	var isSavingState = useState( false );
	var isSaving = isSavingState[ 0 ];
	var setIsSaving = isSavingState[ 1 ];

	var errorState = useState( '' );
	var error = errorState[ 0 ];
	var setError = errorState[ 1 ];

	var fileInputRef = useRef( null );
	var uploadAbortRef = useRef( null );

	// Track mounted state.
	var isMountedRef = useRef( true );
	useEffect( function () {
		isMountedRef.current = true;
		return function () {
			isMountedRef.current = false;
			if ( uploadAbortRef.current ) {
				uploadAbortRef.current.abort();
			}
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
	 * Trigger hidden file input for image selection.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	var triggerFileInput = function () {
		if ( fileInputRef.current ) {
			fileInputRef.current.value = '';
			fileInputRef.current.click();
		}
	};

	/**
	 * Handle file selection and upload via AJAX.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {Event} e File input change event.
	 */
	var handleFileSelect = function ( e ) {
		var file = e.target.files && e.target.files[ 0 ];
		if ( ! file ) {
			return;
		}

		var allowedTypes = [ 'image/jpeg', 'image/png', 'image/gif', 'image/webp' ];
		if ( -1 === allowedTypes.indexOf( file.type ) ) {
			setError( __( 'Invalid file type. Only JPEG, PNG, GIF, and WebP images are allowed.', 'buddyboss' ) );
			return;
		}

		// 10MB limit.
		if ( file.size > 10 * 1024 * 1024 ) {
			setError( __( 'File size exceeds the maximum allowed size of 10MB.', 'buddyboss' ) );
			return;
		}

		if ( uploadAbortRef.current ) {
			uploadAbortRef.current.abort();
		}
		uploadAbortRef.current = new AbortController();

		setIsUploading( true );
		setError( '' );

		uploadForumImage( file, uploadAbortRef.current.signal ).then( function ( response ) {
			if ( ! isMountedRef.current ) {
				return;
			}
			setIsUploading( false );
			if ( response.success && response.data ) {
				setImageId( response.data.attachment_id );
				setImageUrl( response.data.url );
			} else {
				setError( ( response.data && response.data.message ) || __( 'Failed to upload image.', 'buddyboss' ) );
			}
		} ).catch( function ( err ) {
			if ( ! isMountedRef.current ) {
				return;
			}
			setIsUploading( false );
			if ( 'AbortError' !== err.name ) {
				setError( __( 'An error occurred while uploading. Please try again.', 'buddyboss' ) );
			}
		} );
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
			forum_type: forumType,
			parent_id: parentId,
			order: order,
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
		setForumType( 'forum' );
		setParentId( 0 );
		setOrder( 0 );
		setImageId( 0 );
		setImageUrl( '' );
		setIsUploading( false );
		setError( '' );
		if ( uploadAbortRef.current ) {
			uploadAbortRef.current.abort();
		}

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

	var forumTypeOptions = [
		{ value: 'forum', label: __( 'Forum', 'buddyboss' ) },
		{ value: 'category', label: __( 'Category', 'buddyboss' ) },
	];

	var siteUrl = ( window.bbAdminData && window.bbAdminData.siteUrl ) || '';

	return (
		<Modal
			title={ __( 'Create New Forum', 'buddyboss' ) }
			onRequestClose={ handleClose }
			className="bb-forum-modal bb-forum-create-modal bb-admin-settings-modal"
			shouldCloseOnClickOutside={ false }
		>
			<div className="bb-forum-modal__body bb-forum-create-modal__body bb-admin-settings-modal__body">
				{ error && (
					<p className="bb-forum-modal__error">{ error }</p>
				) }

				<TextControl
					label={ __( 'Forum Name', 'buddyboss' ) }
					value={ name }
					onChange={ handleNameChange }
					placeholder={ __( 'Enter forum name', 'buddyboss' ) }
					__nextHasNoMarginBottom
				/>

				<div className="bb-forum-modal__permalink-field">
					<TextControl
						label={ __( 'Permalink', 'buddyboss' ) }
						value={ permalink }
						onChange={ handlePermalinkChange }
						placeholder={ __( 'forum-slug', 'buddyboss' ) }
						__nextHasNoMarginBottom
					/>
					{ permalink && siteUrl && (
						<p className="bb-forum-create-modal__permalink-preview">
							{ siteUrl + '/' + ( forumBaseSlug || 'forum' ) + '/' + permalink + '/' }
						</p>
					) }
				</div>

				<div className="bb-forum-modal__row--separator">
					<RichTextEditor
						id="bb-forum-create-description"
						label={ __( 'Forum Description (Optional)', 'buddyboss' ) }
						value={ description }
						onChange={ setDescription }
					/>
				</div>

				<div className="bb-admin-settings-modal__row bb-admin-settings-modal__row--separator">
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
					<SelectControl
						label={ __( 'Type', 'buddyboss' ) }
						value={ forumType }
						options={ forumTypeOptions }
						onChange={ setForumType }
						__nextHasNoMarginBottom
					/>
				</div>

				<div className="bb-forum-create-modal__row bb-forum-modal__row--separator">
					<div className="components-base-control" style={ { flex: 1 } }>
						<label className="components-base-control__label" htmlFor="bb-forum-create-parent">
							{ __( 'Parent Forum', 'buddyboss' ) }
						</label>
						<AsyncSelectField
							id="bb-forum-create-parent"
							value={ String( parentId ) }
							onChange={ function ( val ) {
								setParentId( parseInt( val, 10 ) || 0 );
							} }
							asyncAction="bb_admin_forum_autocomplete"
							placeholder={ __( 'None', 'buddyboss' ) }
						/>
					</div>
					<TextControl
						label={ __( 'Order', 'buddyboss' ) }
						type="number"
						value={ order }
						onChange={ function ( val ) {
							setOrder( parseInt( val, 10 ) || 0 );
						} }
						min={ 0 }
						__nextHasNoMarginBottom
					/>
				</div>

				<div className="bb-forum-modal__image-field bb-forum-create-modal__image-field">
					<label className="components-base-control__label" htmlFor="bb-forum-create-image">
						{ __( 'Feature Image (Optional)', 'buddyboss' ) }
					</label>
					<input
						type="file"
						ref={ fileInputRef }
						accept="image/jpeg,image/png,image/gif,image/webp"
						onChange={ handleFileSelect }
						style={ { display: 'none' } }
					/>
					{ imageUrl ? (
						<div className="bb-forum-modal__image-preview">
							<img src={ safeUrl( imageUrl ) } alt="" />
							<div className="bb-forum-modal__image-actions">
								<Button
									variant="secondary"
									onClick={ triggerFileInput }
									className="bb-forum-modal__replace-image"
									disabled={ isUploading }
								>
									{ __( 'Replace', 'buddyboss' ) }
								</Button>
								<Button
									variant="secondary"
									isDestructive
									onClick={ handleRemoveImage }
									className="bb-forum-modal__remove-image"
									disabled={ isUploading }
								>
									{ __( 'Reset', 'buddyboss' ) }
								</Button>
							</div>
						</div>
					) : (
						<button
							type="button"
							onClick={ triggerFileInput }
							className={ 'bb-forum-create-modal__upload-zone' + ( isUploading ? ' bb-forum-create-modal__upload-zone--uploading' : '' ) }
							disabled={ isUploading }
						>
							{ isUploading ? (
								<span className="bb-forum-create-modal__upload-spinner"></span>
							) : (
								<span className="bb-forum-create-modal__upload-icon"><i className="bb-icons-rl-plus"></i></span>
							) }
						</button>
					) }
					<p className="bb-forum-create-modal__image-help">
						{ __( 'For best results, use an image at least 1500px by 300px or higher.', 'buddyboss' ) }
					</p>
				</div>
			</div>

			<div className="bb-forum-modal__footer bb-admin-settings-modal__footer">
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
					disabled={ isSaving || isUploading || ! name.trim() }
				>
					{ __( 'Save', 'buddyboss' ) }
				</Button>
			</div>
		</Modal>
	);
}
