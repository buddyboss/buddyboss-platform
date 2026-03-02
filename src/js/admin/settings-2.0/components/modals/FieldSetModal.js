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
	TextareaControl,
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

	var descriptionState = useState( isEditing ? ( fieldSet.description || '' ) : '' );
	var description = descriptionState[ 0 ];
	var setDescription = descriptionState[ 1 ];

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
			setToast( { status: 'error', message: __( 'Field set name is required.', 'buddyboss' ) } );
			return;
		}

		setIsSaving( true );

		var data = {
			name: name.trim(),
			description: description.trim(),
			is_repeater: isRepeater,
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
						message: response.data.message || __( 'Field set saved.', 'buddyboss' ),
					} );
					onSave();
				} else {
					setToast( {
						status: 'error',
						message: response.data?.message || __( 'Failed to save field set.', 'buddyboss' ),
					} );
				}
			} )
			.catch( function ( error ) {
				setIsSaving( false );
				setToast( { status: 'error', message: error.message || __( 'Failed to save field set.', 'buddyboss' ) } );
			} );
	}

	return wp.element.createElement(
		Modal,
		{
			title: isEditing ? __( 'Edit Field Set', 'buddyboss' ) : __( 'Add New Field Set', 'buddyboss' ),
			onRequestClose: onClose,
			className: 'bb-pf-fieldset-modal bb-admin-settings-modal',
			shouldCloseOnClickOutside: false,
		},

		wp.element.createElement(
			'div',
			{ className: 'bb-pf-modal-body' },

			wp.element.createElement( TextControl, {
				label: __( 'Name', 'buddyboss' ),
				value: name,
				onChange: setName,
				placeholder: __( 'Enter field set name', 'buddyboss' ),
				required: true,
			} ),

			wp.element.createElement( TextareaControl, {
				label: __( 'Description', 'buddyboss' ),
				value: description,
				onChange: setDescription,
				placeholder: __( 'Optional description', 'buddyboss' ),
			} ),

			wp.element.createElement( RadioControl, {
				label: __( 'Repeater Set', 'buddyboss' ),
				help: __( 'When enabled, users can add multiple sets of these fields to their profile.', 'buddyboss' ),
				selected: isRepeater,
				options: [
					{ label: __( 'Disabled', 'buddyboss' ), value: 'off' },
					{ label: __( 'Enabled', 'buddyboss' ), value: 'on' },
				],
				onChange: setIsRepeater,
			} )
		),

		wp.element.createElement(
			'div',
			{ className: 'bb-pf-modal-footer' },

			// Delete button (edit mode only, and only if can_delete).
			isEditing && fieldSet.can_delete && wp.element.createElement(
				Button,
				{
					variant: 'link',
					isDestructive: true,
					className: 'bb-pf-modal-delete-btn',
					onClick: function () {
						onDelete();
					},
				},
				__( 'Delete Field Set', 'buddyboss' )
			),

			wp.element.createElement(
				'div',
				{ className: 'bb-pf-modal-footer-right' },
				wp.element.createElement(
					Button,
					{
						variant: 'secondary',
						onClick: onClose,
						disabled: isSaving,
					},
					__( 'Cancel', 'buddyboss' )
				),
				wp.element.createElement(
					Button,
					{
						variant: 'primary',
						onClick: handleSave,
						isBusy: isSaving,
						disabled: isSaving || ! name.trim(),
					},
					isSaving
						? wp.element.createElement( Spinner, null )
						: ( isEditing ? __( 'Save Changes', 'buddyboss' ) : __( 'Create Field Set', 'buddyboss' ) )
				)
			)
		)
	);
}
