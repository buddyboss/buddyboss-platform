/**
 * BuddyBoss Admin Settings 2.0 - Reporting Category Modal
 *
 * Modal for creating and editing reporting categories (bpm_category taxonomy).
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

import { useState, useEffect, useCallback } from '@wordpress/element';
import {
	TextControl,
	TextareaControl,
	SelectControl,
	Button,
	Modal,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { decodeEntities } from '@wordpress/html-entities';
import { createReportingCategory, updateReportingCategory } from '../../utils/ajax';

/**
 * Default form data for a new reporting category.
 *
 * @since BuddyBoss [BBVERSION]
 */
var DEFAULT_FORM_DATA = {
	name: '',
	description: '',
	show_when_reporting: 'content',
};

/**
 * Reporting Category Modal Component
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {Object}   props                  Component props.
 * @param {boolean}  props.isOpen           Whether modal is open.
 * @param {Function} props.onClose          Close handler.
 * @param {Function} props.onSave           Save success handler.
 * @param {Object}   props.category         Category to edit (null for create).
 * @param {Array}    props.showWhenOptions   Select options for "Show When Reporting".
 * @returns {JSX.Element|null} Modal element or null.
 */
export function ReportingCategoryModal( { isOpen, onClose, onSave, category, showWhenOptions } ) {
	var formDataState = useState( DEFAULT_FORM_DATA );
	var formData = formDataState[ 0 ];
	var setFormData = formDataState[ 1 ];

	var isSavingState = useState( false );
	var isSaving = isSavingState[ 0 ];
	var setIsSaving = isSavingState[ 1 ];

	var errorState = useState( '' );
	var error = errorState[ 0 ];
	var setError = errorState[ 1 ];

	// Populate form data when editing.
	useEffect( function () {
		if ( ! isOpen ) {
			return;
		}

		if ( category ) {
			setFormData( {
				name: decodeEntities( category.name || '' ),
				description: decodeEntities( category.description || '' ),
				show_when_reporting: category.show_when_reporting || 'content',
			} );
		} else {
			setFormData( JSON.parse( JSON.stringify( DEFAULT_FORM_DATA ) ) );
		}

		setError( '' );
	}, [ isOpen, category ] );

	// Update a field in form data.
	var updateField = useCallback( function ( field, value ) {
		setFormData( function ( prev ) {
			var updated = Object.assign( {}, prev );
			updated[ field ] = value;
			return updated;
		} );
	}, [] );

	// Handle save.
	var handleSave = useCallback( function () {
		if ( isSaving ) {
			return;
		}

		if ( ! formData.name.trim() ) {
			setError( __( 'Category name is required.', 'buddyboss' ) );
			return;
		}

		setIsSaving( true );
		setError( '' );

		var data = {
			name: formData.name,
			description: formData.description,
			show_when_reporting: formData.show_when_reporting,
		};

		var savePromise;
		if ( category && category.id ) {
			savePromise = updateReportingCategory( category.id, data );
		} else {
			savePromise = createReportingCategory( data );
		}

		savePromise
			.then( function ( response ) {
				setIsSaving( false );
				if ( response.success ) {
					if ( 'function' === typeof onSave ) {
						onSave();
					}
				} else {
					setError( ( response.data && response.data.message ) || __( 'Failed to save category.', 'buddyboss' ) );
				}
			} )
			.catch( function () {
				setIsSaving( false );
				setError( __( 'Failed to save category.', 'buddyboss' ) );
			} );
	}, [ formData, category, onSave, isSaving ] );

	if ( ! isOpen ) {
		return null;
	}

	var isEditing = !! ( category && category.id );
	var modalTitle = isEditing
		? __( 'Edit Category', 'buddyboss' )
		: __( 'Add New Category', 'buddyboss' );

	// Build select options from server data or use defaults.
	// Server labels may contain HTML entities (e.g. &amp;) from esc_html__(), so decode them.
	var selectOptions = ( showWhenOptions && showWhenOptions.length > 0 )
		? showWhenOptions.map( function ( opt ) {
			return { label: decodeEntities( opt.label ), value: opt.value };
		} )
		: [
			{ label: __( 'Content', 'buddyboss' ), value: 'content' },
			{ label: __( 'Members', 'buddyboss' ), value: 'members' },
			{ label: __( 'Content & Members', 'buddyboss' ), value: 'content_members' },
		];

	return (
		<Modal
			title={ modalTitle }
			onRequestClose={ function () {
				if ( ! isSaving ) {
					onClose();
				}
			} }
			className="bb-admin-reporting-category-modal bb-admin-settings-modal"
			shouldCloseOnClickOutside={ false }
		>
			<div className="bb-admin-reporting-category-modal__body">
				{ error && (
					<div className="bb-admin-reporting-category-modal__error">
						{ error }
					</div>
				) }

				{/* Name */}
				<div className="bb-admin-reporting-category-modal__section">
					<TextControl
						label={ __( 'Name', 'buddyboss' ) }
						value={ formData.name }
						onChange={ function ( val ) { updateField( 'name', val ); } }
						required
					/>
				</div>

				{/* Description */}
				<div className="bb-admin-reporting-category-modal__section">
					<TextareaControl
						label={ __( 'Description', 'buddyboss' ) }
						value={ formData.description }
						onChange={ function ( val ) { updateField( 'description', val ); } }
						placeholder={ __( 'Enter category description...', 'buddyboss' ) }
					/>
				</div>

				{/* Show When Reporting */}
				<div className="bb-admin-reporting-category-modal__section">
					<SelectControl
						label={ __( 'Show When Reporting', 'buddyboss' ) }
						value={ formData.show_when_reporting }
						options={ selectOptions }
						onChange={ function ( val ) { updateField( 'show_when_reporting', val ); } }
					/>
				</div>
			</div>

			<div className="bb-admin-settings-modal__footer bb-admin-reporting-category-modal__footer">
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
					disabled={ isSaving || ! formData.name.trim() }
				>
					{ isSaving ? __( 'Saving...', 'buddyboss' ) : ( isEditing ? __( 'Update', 'buddyboss' ) : __( 'Save', 'buddyboss' ) ) }
				</Button>
			</div>
		</Modal>
	);
}

export default ReportingCategoryModal;
