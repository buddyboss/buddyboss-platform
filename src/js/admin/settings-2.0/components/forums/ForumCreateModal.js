/**
 * BuddyBoss Admin Settings 2.0 - Forum Create Modal
 *
 * Uses BB_Admin_Meta_Field_Registry for field rendering via RegisteredMetaField.
 * Featured image remains a custom section (not in registry).
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

import { useState, useRef, useEffect, useCallback, useMemo } from '@wordpress/element';
import {
	Modal,
	Button,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';

import { createForum, uploadForumImage } from '../../utils/ajax';
import { toSlug, groupFieldsWithLayout } from '../../utils/format';
import { safeUrl } from '../../utils/sanitize';
import { RegisteredMetaField } from '../common/RegisteredMetaField';
import { forceRemoveEditor } from '../common/RichTextEditor';

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
 * @param {Array}    props.createFields   Registered field definitions from server.
 * @returns {JSX.Element|null} Modal component or null.
 */
export function ForumCreateModal( { isOpen, onClose, onCreated, forumBaseSlug, createFields } ) {
	// All registered field values keyed by field ID.
	var registeredValuesState = useState( {} );
	var registeredValues = registeredValuesState[ 0 ];
	var setRegisteredValues = registeredValuesState[ 1 ];

	// Track whether permalink has been manually edited.
	var permalinkEditedState = useState( false );
	var permalinkEdited = permalinkEditedState[ 0 ];
	var setPermalinkEdited = permalinkEditedState[ 1 ];

	// Featured image state (not in registry).
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

	// Initialize registered values from create field defaults when modal opens.
	useEffect( function () {
		if ( isOpen && createFields && Array.isArray( createFields ) ) {
			var initialValues = {};
			createFields.forEach( function ( field ) {
				initialValues[ field.id ] = field.value;
			} );
			setRegisteredValues( initialValues );
			setPermalinkEdited( false );
		}
	}, [ isOpen, createFields ] );

	// Get the fields to render (from props or empty array).
	var fields = createFields && Array.isArray( createFields ) ? createFields : [];

	/**
	 * Handle change for a registered field.
	 * Auto-generates permalink from name if not manually edited.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {string} fieldId Field ID.
	 * @param {*}      val     New value.
	 */
	var handleFieldChange = useCallback( function ( fieldId, val ) {
		setRegisteredValues( function ( prev ) {
			var next = {};
			Object.keys( prev ).forEach( function ( k ) {
				next[ k ] = prev[ k ];
			} );
			next[ fieldId ] = val;

			// Auto-generate slug from name when not manually edited.
			if ( 'name' === fieldId && ! permalinkEdited ) {
				next.slug = toSlug( val );
			}

			// Mark permalink as manually edited when slug field changes directly.
			if ( 'slug' === fieldId ) {
				setPermalinkEdited( true );
			}

			return next;
		} );
	}, [ permalinkEdited ] );

	if ( ! isOpen ) {
		return null;
	}

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
		var nameVal = registeredValues.name || '';
		if ( ! nameVal.trim() ) {
			setError( __( 'Forum name is required.', 'buddyboss' ) );
			return;
		}

		setIsSaving( true );
		setError( '' );

		var payload = {
			name: nameVal.trim(),
			slug: registeredValues.slug || '',
			description: registeredValues.description || '',
			visibility: registeredValues.visibility || 'publish',
			forum_status: registeredValues.forum_status || 'open',
			forum_type: registeredValues.forum_type || 'forum',
			parent_id: registeredValues.parent_id || 0,
			order: registeredValues.order || 0,
			image_id: imageId,
		};

		// Include registered field values for extension fields (Pro/third-party).
		fields.forEach( function ( field ) {
			if ( field.readonly ) {
				return;
			}

			var val = registeredValues[ field.id ];

			// For richtext fields, pull latest content from TinyMCE.
			if ( 'richtext' === field.type && window.tinymce ) {
				var editorInstance = window.tinymce.get( 'bb-admin-edit-' + field.id + '-0' );
				if ( editorInstance ) {
					val = editorInstance.getContent();
				}
			}

			payload[ 'registered_field_' + field.id ] = null !== val && undefined !== val ? val : '';
		} );

		createForum( payload ).then( function ( response ) {
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
		var initialValues = {};
		if ( createFields && Array.isArray( createFields ) ) {
			createFields.forEach( function ( field ) {
				initialValues[ field.id ] = field.value;
			} );
		}
		setRegisteredValues( initialValues );
		setPermalinkEdited( false );
		setImageId( 0 );
		setImageUrl( '' );
		setIsUploading( false );
		setError( '' );
		if ( uploadAbortRef.current ) {
			uploadAbortRef.current.abort();
		}

		// Reset TinyMCE editors for richtext fields.
		if ( window.tinymce ) {
			fields.forEach( function ( field ) {
				if ( 'richtext' === field.type ) {
					var editor = window.tinymce.get( 'bb-admin-edit-' + field.id + '-0' );
					if ( editor ) {
						editor.setContent( '' );
					}
				}
			} );
		}
	};

	/**
	 * Handle modal close and reset form state.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	var handleClose = function () {
		// Force remove richtext editors.
		fields.forEach( function ( field ) {
			if ( 'richtext' === field.type ) {
				forceRemoveEditor( 'bb-admin-edit-' + field.id + '-0' );
			}
		} );
		resetForm();
		onClose();
	};

	// Render visible fields.
	var visibleFields = fields.filter( function ( field ) {
		return field.visible;
	} );

	var grouped = groupFieldsWithLayout( visibleFields );

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

				{ grouped.map( function ( item, idx ) {
					// Add separator when the next item is a row group (e.g. after description).
					var nextIsRow = grouped[ idx + 1 ] && 'row' === grouped[ idx + 1 ].type;

					if ( 'row' === item.type ) {
						return (
							<div key={ 'row-' + idx } className="bb-admin-meta-field__row bb-admin-settings-modal__row bb-admin-settings-modal__row--separator">
								{ item.fields.map( function ( field ) {
									return (
										<RegisteredMetaField
											key={ field.id }
											field={ field }
											value={ registeredValues[ field.id ] }
											onChange={ function ( val ) {
												handleFieldChange( field.id, val );
											} }
											itemId={ 0 }
										/>
									);
								} ) }
							</div>
						);
					}
					return (
						<div key={ item.field.id } className={ nextIsRow ? 'bb-admin-settings-modal__row--separator' : '' }>
							<RegisteredMetaField
								field={ item.field }
								value={ registeredValues[ item.field.id ] }
								onChange={ function ( val ) {
									handleFieldChange( item.field.id, val );
								} }
								itemId={ 0 }
							/>
						</div>
					);
				} ) }

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
					disabled={ isSaving || isUploading || ! ( registeredValues.name || '' ).trim() }
				>
					{ __( 'Save', 'buddyboss' ) }
				</Button>
			</div>
		</Modal>
	);
}
