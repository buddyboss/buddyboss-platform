/**
 * BuddyBoss Admin Settings 2.0 - Forum Create Modal
 *
 * Uses BB_Admin_Meta_Field_Registry for field rendering via RegisteredMetaField.
 * Featured image remains a custom section (not in registry).
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

import { useState, useRef, useEffect, useCallback } from '@wordpress/element';
import {
	Modal,
	Button,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';

import { createForum } from '../../utils/ajax';
import { toSlug, groupFieldsWithLayout, buildRegisteredFieldPayload, getVisibleFields, needsSeparator } from '../../utils/format';
import { safeUrl } from '../../utils/sanitize';
import { useMediaFrame } from '../../hooks/useMediaFrame';
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

	// Track whether permalink has been manually edited (ref to avoid stale closure in useCallback).
	var permalinkEditedRef = useRef( false );

	// Featured image state (not in registry).
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

	// Shared WordPress media frame opener (handles frame reuse + unmount teardown).
	var openMediaFrame = useMediaFrame();

	// Track mounted state.
	var isMountedRef = useRef( true );
	useEffect( function () {
		isMountedRef.current = true;
		return function () {
			isMountedRef.current = false;
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
			permalinkEditedRef.current = false;
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
			var next = Object.assign( {}, prev );
			next[ fieldId ] = val;

			// Auto-generate slug from name when not manually edited.
			if ( 'name' === fieldId && ! permalinkEditedRef.current ) {
				next.slug = toSlug( val );
			}

			// Mark permalink as manually edited when slug field changes directly.
			if ( 'slug' === fieldId ) {
				permalinkEditedRef.current = true;
			}

			return next;
		} );
	}, [] );

	if ( ! isOpen ) {
		return null;
	}

	/**
	 * Open the WordPress Media Library to select or upload the feature image.
	 *
	 * Replaces the previous direct-upload flow: selecting an existing Library
	 * item reuses its attachment (no duplicate), and uploads made inside the
	 * media frame route through WordPress core. The chosen attachment ID is
	 * stored for set_post_thumbnail() on save. Frame behaviour is centralized
	 * in the shared useMediaFrame hook (mirrors WP's native "Featured image").
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	var openMediaLibrary = function () {
		var opened = openMediaFrame( function ( attachment ) {
			setImageId( attachment.id );
			setImageUrl( attachment.url );
			setError( '' );
		} );

		if ( ! opened ) {
			setError( __( 'The WordPress Media Library is not available.', 'buddyboss' ) );
		}
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

		// buildRegisteredFieldPayload emits both plain keys and registered_field_* keys automatically.
		var payload = Object.assign(
			buildRegisteredFieldPayload( fields, registeredValues, 0 ),
			{
				name: nameVal.trim(), // Override with trimmed value.
				image_id: imageId,    // Custom section, not in registry.
			}
		);

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
		permalinkEditedRef.current = false;
		setImageId( 0 );
		setImageUrl( '' );
		setError( '' );

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
	var visibleFields = getVisibleFields( fields, registeredValues );

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
					var hasSeparator = needsSeparator( item, grouped[ idx + 1 ] );

					if ( 'row' === item.type ) {
						return (
							<div key={ 'row-' + idx } className={ 'bb-admin-meta-field__row bb-admin-settings-modal__row' + ( hasSeparator ? ' bb-admin-settings-modal__row--separator' : '' ) }>
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
						<div key={ item.field.id } className={ 'components-base-control ' + ( hasSeparator ? 'bb-admin-settings-modal__row--separator' : '' ) }>
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
					{ imageUrl ? (
						<div className="bb-forum-modal__image-preview">
							<img src={ safeUrl( imageUrl ) } alt="" />
							<div className="bb-forum-modal__image-actions">
								<Button
									variant="secondary"
									onClick={ openMediaLibrary }
									className="bb-forum-modal__replace-image"
								>
									{ __( 'Replace', 'buddyboss' ) }
								</Button>
								<Button
									variant="secondary"
									isDestructive
									onClick={ handleRemoveImage }
									className="bb-forum-modal__remove-image"
								>
									{ __( 'Reset', 'buddyboss' ) }
								</Button>
							</div>
						</div>
					) : (
						<button
							type="button"
							onClick={ openMediaLibrary }
							className="bb-forum-create-modal__upload-zone"
						>
							<span className="bb-forum-create-modal__upload-icon"><i className="bb-icons-rl-plus"></i></span>
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
					disabled={ isSaving || ! ( registeredValues.name || '' ).trim() }
				>
					{ __( 'Save', 'buddyboss' ) }
				</Button>
			</div>
		</Modal>
	);
}
