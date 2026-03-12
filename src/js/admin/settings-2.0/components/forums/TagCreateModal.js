/**
 * BuddyBoss Admin Settings 2.0 - Tag Create/Edit Modal
 *
 * Modal with Name, Slug, Description fields for creating/editing
 * a discussion tag (topic tag taxonomy term).
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

import { useState, useRef, useEffect } from '@wordpress/element';
import {
	Modal,
	Button,
	Spinner,
	TextControl,
	TextareaControl,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';

import { createTopicTag, saveTopicTag } from '../../utils/ajax';

/**
 * Tag Create/Edit Modal Component
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {Object}   props             Component props.
 * @param {boolean}  props.isOpen      Whether the modal is open.
 * @param {Function} props.onClose     Close handler.
 * @param {Function} props.onSaved     Success handler (receives term data).
 * @param {Object}   props.editTag     Tag object to edit (null for create mode).
 * @param {boolean}  props.isLoading   Whether tag data is being fetched.
 * @returns {JSX.Element|null} Modal component or null.
 */
export function TagCreateModal( { isOpen, onClose, onSaved, editTag, isLoading } ) {
	var nameState = useState( '' );
	var name = nameState[ 0 ];
	var setName = nameState[ 1 ];

	var slugState = useState( '' );
	var slug = slugState[ 0 ];
	var setSlug = slugState[ 1 ];

	var descriptionState = useState( '' );
	var description = descriptionState[ 0 ];
	var setDescription = descriptionState[ 1 ];

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

	// Populate fields when editing.
	useEffect( function () {
		if ( editTag ) {
			setName( editTag.name || '' );
			setSlug( editTag.slug || '' );
			setDescription( editTag.description || '' );
		} else {
			setName( '' );
			setSlug( '' );
			setDescription( '' );
		}
		setError( '' );
	}, [ editTag ] );

	if ( ! isOpen ) {
		return null;
	}

	/**
	 * Handle tag creation/update form submission.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	var handleSave = function () {
		if ( ! name.trim() ) {
			setError( __( 'Tag name is required.', 'buddyboss' ) );
			return;
		}

		setIsSaving( true );
		setError( '' );

		var data = {
			name: name.trim(),
			slug: slug.trim(),
			description: description.trim(),
		};

		var savePromise;
		if ( editTag ) {
			data.term_id = editTag.id;
			savePromise = saveTopicTag( data );
		} else {
			savePromise = createTopicTag( data );
		}

		savePromise.then( function ( response ) {
			if ( ! isMountedRef.current ) {
				return;
			}
			setIsSaving( false );
			if ( response.success ) {
				resetForm();
				if ( onSaved ) {
					onSaved( response.data );
				}
			} else {
				setError( ( response.data && response.data.message ) || __( 'Failed to save tag.', 'buddyboss' ) );
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
		setSlug( '' );
		setDescription( '' );
		setError( '' );
	};

	/**
	 * Handle modal close and reset form state.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	var handleClose = function () {
		resetForm();
		onClose();
	};

	var modalTitle = (editTag || isLoading)
		? __( 'Edit Tag', 'buddyboss' )
		: __( 'Add New Tag', 'buddyboss' );

	return (
		<Modal
			title={ modalTitle }
			onRequestClose={ handleClose }
			className="bb-tag-create-modal bb-admin-settings-modal"
			shouldCloseOnClickOutside={ false }
		>
			{ isLoading ? (
				<div className="bb-tag-create-modal__loading">
					<Spinner />
				</div>
			) : (
				<>
					<div className="bb-tag-create-modal__body">
						{ error && (
							<p className="bb-tag-create-modal__error">{ error }</p>
						) }

						<TextControl
							label={ __( 'Name', 'buddyboss' ) }
							value={ name }
							onChange={ setName }
							help={ __( 'This name is how it appears on your site.', 'buddyboss' ) }
							__nextHasNoMarginBottom
						/>

						<TextControl
							label={ __( 'Slug', 'buddyboss' ) }
							value={ slug }
							onChange={ setSlug }
							help={ __( 'The "slug" is the URL-friendly version of the name. It is usually all lowercase and contains only letters, numbers, and hyphens.', 'buddyboss' ) }
							__nextHasNoMarginBottom
						/>

						<TextareaControl
							label={ __( 'Description (Optional)', 'buddyboss' ) }
							value={ description }
							onChange={ setDescription }
							placeholder={ __( 'Enter description', 'buddyboss' ) }
							__nextHasNoMarginBottom
						/>
					</div>

					<div className="bb-tag-create-modal__footer bb-admin-settings-modal__footer">
						<Button
							variant="secondary"
							onClick={ handleClose }
							disabled={ isSaving }
						>
							{ __( 'Cancel', 'buddyboss' ) }
						</Button>
						<Button
							variant="primary"
							onClick={ handleSave }
							isBusy={ isSaving }
							disabled={ isSaving || ! name.trim() }
						>
							{ __( 'Save', 'buddyboss' ) }
						</Button>
					</div>
				</>
			) }
		</Modal>
	);
}
