/**
 * BuddyBoss Admin Settings 2.0 - Field Set Modal
 *
 * Modal for creating and editing profile field groups (field sets).
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

import { useState } from '@wordpress/element';
import {
	TextControl,
	RadioControl,
	Button,
	Spinner,
	Modal,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { createFieldGroup, updateFieldGroup } from '../../utils/ajax';

/**
 * Field Set Modal Component
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {Object}   props           Component props.
 * @param {Object}   props.fieldSet  Field set data (empty object for new, has .id for edit).
 * @param {Function} props.onClose   Close callback.
 * @param {Function} props.onSave    Save success callback.
 * @param {Function} props.onDelete  Delete button callback.
 * @param {Function} props.setToast  Toast setter.
 * @returns {JSX.Element} Field set modal.
 */
export function FieldSetModal( { fieldSet, onClose, onSave, onDelete, setToast } ) {

	var isEditing = fieldSet && fieldSet.id;

	var nameState = useState( isEditing ? fieldSet.name : '' );
	var name = nameState[ 0 ];
	var setName = nameState[ 1 ];

	var isRepeaterState = useState( isEditing ? ( fieldSet.is_repeater ? 'on' : 'off' ) : 'off' );
	var isRepeater = isRepeaterState[ 0 ];
	var setIsRepeater = isRepeaterState[ 1 ];

	var isSavingState = useState( false );
	var isSaving = isSavingState[ 0 ];
	var setIsSaving = isSavingState[ 1 ];

	/**
	 * Handle form submit.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	function handleSave() {
		if ( ! name.trim() ) {
			setToast( { status: 'error', message: __( 'Field set name is required.', 'buddyboss-platform' ) } );
			return;
		}

		setIsSaving( true );

		var data = {
			name: name.trim(),
			group_is_repeater: isRepeater,
		};

		var savePromise;
		if ( isEditing ) {
			data.group_id = fieldSet.id;
			savePromise = updateFieldGroup( data );
		} else {
			savePromise = createFieldGroup( data );
		}

		savePromise
			.then( function ( response ) {
				setIsSaving( false );
				if ( response.success ) {
					setToast( {
						status: 'success',
						message: response.data.message || __( 'Field set saved.', 'buddyboss-platform' ),
					} );
					onSave();
				} else {
					setToast( {
						status: 'error',
						message: ( response.data && response.data.message ) || __( 'Failed to save field set.', 'buddyboss-platform' ),
					} );
				}
			} )
			.catch( function ( error ) {
				setIsSaving( false );
				setToast( { status: 'error', message: error.message || __( 'Failed to save field set.', 'buddyboss-platform' ) } );
			} );
	}

	return (
		<Modal
			title={ isEditing ? __( 'Edit Field Set', 'buddyboss-platform' ) : __( 'Add New Field Set', 'buddyboss-platform' ) }
			onRequestClose={ onClose }
			className="bb-pf-fieldset-modal bb-admin-settings-modal"
			shouldCloseOnClickOutside={ false }
		>
			<div className="bb-admin-settings-modal__body">
				<div className={(! isEditing || fieldSet.can_delete) ? 'bb-admin-settings-modal__row--separator' : ''}>
					<TextControl
						label={ __( 'Name', 'buddyboss-platform' ) }
						value={ name }
						onChange={ setName }
						placeholder={ __( 'Enter field set name', 'buddyboss-platform' ) }
						required
					/>
				</div>
				{ /* Repeater toggle: not available for base group (can_delete=false). */ }
				{ ( ! isEditing || fieldSet.can_delete ) && (
					<RadioControl
						label={ __( 'Repeater Set', 'buddyboss-platform' ) }
						help={ __( 'Allow the profile fields within this set to be repeated again and again, so the user can add multiple instances of their data.', 'buddyboss-platform' ) }
						selected={ isRepeater }
						options={ [
							{ label: __( 'Enabled', 'buddyboss-platform' ), value: 'on' },
							{ label: __( 'Disabled', 'buddyboss-platform' ), value: 'off' },
						] }
						onChange={ setIsRepeater }
					/>
				) }
			</div>

			<div className="bb-pf-modal-footer bb-admin-settings-modal__footer">
				{ /* Delete button (edit mode only, and only if can_delete). */ }
				{ isEditing && fieldSet.can_delete && (
					<Button
						variant="primary"
						isDestructive
						className="bb-pf-modal-delete-btn bb-admin-button-danger"
						onClick={ function () {
							onDelete();
						} }
					>
						{ __( 'Delete Field Set', 'buddyboss-platform' ) }
					</Button>
				) }

				<div className="bb-pf-modal-footer-right">
					<Button
						variant="secondary"
						onClick={ onClose }
						disabled={ isSaving }
					>
						{ __( 'Cancel', 'buddyboss-platform' ) }
					</Button>
					<Button
						variant="primary"
						onClick={ handleSave }
						isBusy={ isSaving }
						disabled={ isSaving || ! name.trim() }
					>
						{ __( 'Save', 'buddyboss-platform' ) }
					</Button>
				</div>
			</div>
		</Modal>
	);
}
