/**
 * BuddyBoss Admin Settings 2.0 - Profile Search Field Modal
 *
 * Modal for adding and editing profile search form fields.
 * Allows selecting a field, setting a custom label, description,
 * and search mode.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

import { useState, useEffect, useMemo } from '@wordpress/element';
import {
	TextControl,
	SelectControl,
	Button,
	Modal,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { decodeEntities } from '@wordpress/html-entities';
import { saveProfileSearchField } from '../../utils/ajax';

/**
 * Profile Search Field Modal Component
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {Object}   props                Component props.
 * @param {Object}   props.field          Field data (null for new field).
 * @param {Array}    props.availableFields Available fields grouped by group name.
 * @param {Array}    props.existingFields  Currently saved fields (for duplicate check).
 * @param {Function} props.onClose        Close modal handler.
 * @param {Function} props.onSave         Save success handler.
 * @param {Function} props.setToast       Toast notification handler.
 */
export function ProfileSearchFieldModal( { field, availableFields, existingFields, onClose, onSave, setToast } ) {

	var isEditing = field && field.code;

	var fieldCodeState = useState( isEditing ? field.code : '' );
	var fieldCode = fieldCodeState[ 0 ];
	var setFieldCode = fieldCodeState[ 1 ];

	var fieldLabelState = useState( isEditing ? ( field.label || '' ) : '' );
	var fieldLabel = fieldLabelState[ 0 ];
	var setFieldLabel = fieldLabelState[ 1 ];

	var fieldDescState = useState( isEditing ? ( field.description || '' ) : '' );
	var fieldDesc = fieldDescState[ 0 ];
	var setFieldDesc = fieldDescState[ 1 ];

	var fieldModeState = useState( isEditing ? ( field.search_mode || '' ) : '' );
	var fieldMode = fieldModeState[ 0 ];
	var setFieldMode = fieldModeState[ 1 ];

	var isSavingState = useState( false );
	var isSaving = isSavingState[ 0 ];
	var setIsSaving = isSavingState[ 1 ];

	/**
	 * Build field select options with optgroups.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	var fieldOptions = useMemo( function () {
		var options = [ { label: __( '— Select a field —', 'buddyboss' ), value: '', disabled: true } ];

		if ( ! availableFields || ! availableFields.length ) {
			return options;
		}

		availableFields.forEach( function ( group ) {
			// Add group header as disabled option.
			options.push( {
				label: '— ' + decodeEntities( group.label ) + ' —',
				value: '__group_' + group.label,
				disabled: true,
			} );

			group.fields.forEach( function ( f ) {
				options.push( {
					label: '    ' + decodeEntities( f.name ),
					value: f.code,
				} );
			} );
		} );

		return options;
	}, [ availableFields ] );

	/**
	 * Get the selected field object from available fields.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {string} code Field code.
	 * @returns {Object|null} Field object.
	 */
	function getFieldByCode( code ) {
		if ( ! code || ! availableFields ) {
			return null;
		}
		for ( var i = 0; i < availableFields.length; i++ ) {
			var group = availableFields[ i ];
			for ( var j = 0; j < group.fields.length; j++ ) {
				if ( group.fields[ j ].code === code ) {
					return group.fields[ j ];
				}
			}
		}
		return null;
	}

	/**
	 * Get available search modes for the selected field.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	var availableModes = useMemo( function () {
		if ( ! fieldCode ) {
			return [];
		}

		// When editing, use the field's stored modes.
		if ( isEditing && field && field.available_modes ) {
			// If field code hasn't changed from original, use stored modes.
			if ( fieldCode === field.code ) {
				return field.available_modes;
			}
		}

		// Look up from available fields.
		var fieldObj = getFieldByCode( fieldCode );
		return fieldObj && fieldObj.available_modes ? fieldObj.available_modes : [];
	}, [ fieldCode, availableFields, field, isEditing ] );

	// When field code changes (add mode), auto-set defaults.
	useEffect( function () {
		if ( isEditing ) {
			return;
		}
		if ( ! fieldCode ) {
			return;
		}
		var fieldObj = getFieldByCode( fieldCode );
		if ( fieldObj ) {
			// Auto-populate label placeholder (don't overwrite if already typed).
			// Auto-set first available mode.
			if ( fieldObj.available_modes && fieldObj.available_modes.length > 0 ) {
				setFieldMode( fieldObj.available_modes[ 0 ].value );
			}
		}
	}, [ fieldCode ] ); // eslint-disable-line react-hooks/exhaustive-deps

	/**
	 * Handle save.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	function handleSave() {
		if ( ! fieldCode ) {
			setToast( { status: 'error', message: __( 'Please select a field.', 'buddyboss' ) } );
			return;
		}

		setIsSaving( true );

		var data = {
			field_code: fieldCode,
			field_label: fieldLabel,
			field_desc: fieldDesc,
			field_mode: fieldMode,
		};

		// If editing, pass the field index.
		if ( isEditing && null !== field.id && undefined !== field.id ) {
			data.field_index = field.id;
		}

		saveProfileSearchField( data )
			.then( function ( response ) {
				setIsSaving( false );
				if ( response.success ) {
					setToast( { status: 'success', message: response.data.message || __( 'Field saved.', 'buddyboss' ) } );
					onSave();
				} else {
					setToast( { status: 'error', message: ( response.data && response.data.message ) || __( 'Failed to save field.', 'buddyboss' ) } );
				}
			} )
			.catch( function ( error ) {
				setIsSaving( false );
				setToast( { status: 'error', message: error.message || __( 'Failed to save field.', 'buddyboss' ) } );
			} );
	}

	// Get placeholder text for label.
	var selectedField = getFieldByCode( fieldCode );
	var labelPlaceholder = selectedField ? decodeEntities( selectedField.name ) : __( 'Field label', 'buddyboss' );

	// Build search mode options.
	var modeOptions = availableModes.map( function ( mode ) {
		return {
			label: decodeEntities( mode.label ),
			value: mode.value,
		};
	} );

	return (
		<Modal
			title={ isEditing ? __( 'Edit Search Field', 'buddyboss' ) : __( 'Add Search Field', 'buddyboss' ) }
			onRequestClose={ onClose }
			shouldCloseOnClickOutside={ false }
			className="bb-ps-field-modal bb-admin-settings-modal"
		>

			<div className="bb-admin-settings-modal__body">

				{/* Field Select. */}
					<SelectControl
						label={ __( 'Select Field', 'buddyboss' ) }
						value={ fieldCode }
						options={ fieldOptions }
						onChange={ function ( val ) {
							// Don't allow selecting disabled group headers.
							if ( val && 0 === val.indexOf( '__group_' ) ) {
								return;
							}
							setFieldCode( val );
						} }
					/>

					{/* Label. */}
					<TextControl
						label={ __( 'Label', 'buddyboss' ) }
						value={ fieldLabel }
						placeholder={ labelPlaceholder }
						onChange={ setFieldLabel }
					/>

					{/* Description. */}
					<TextControl
						label={ __( 'Description', 'buddyboss' ) }
						value={ fieldDesc }
						onChange={ setFieldDesc }
					/>

					{/* Search Mode (only if modes are available and not heading). */}
					{ 'heading' !== fieldCode && modeOptions.length > 0 && (
						<SelectControl
							label={ __( 'Search Mode', 'buddyboss' ) }
							value={ fieldMode }
							options={ modeOptions }
							onChange={ setFieldMode }
						/>
					) }
				</div>

			{/* Footer buttons. */}
			<div className="bb-admin-settings-modal__footer">
				<Button
					variant="secondary"
					onClick={ onClose }
				>
					{ __( 'Cancel', 'buddyboss' ) }
				</Button>
				<Button
					variant="primary"
					isBusy={ isSaving }
					disabled={ ! fieldCode || isSaving }
					onClick={ handleSave }
				>
					{ isEditing ? __( 'Save', 'buddyboss' ) : __( 'Add Field', 'buddyboss' ) }
				</Button>
			</div>

		</Modal>
	);
}
