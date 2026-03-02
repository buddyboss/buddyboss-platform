/**
 * BuddyBoss Admin Settings 2.0 - Delete Field Set Modal
 *
 * Confirmation modal for deleting a profile field group (field set)
 * with checkbox acknowledgment.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

import { useState } from '@wordpress/element';
import {
	CheckboxControl,
	Button,
	Spinner,
	Modal,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { decodeEntities } from '@wordpress/html-entities';
import { deleteFieldGroup } from '../../utils/ajax';

/**
 * Delete Field Set Modal Component
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {Object}   props            Component props.
 * @param {Object}   props.fieldSet   Field set data with .id and .name.
 * @param {Function} props.onClose    Close callback.
 * @param {Function} props.onDeleted  Delete success callback.
 * @param {Function} props.setToast   Toast setter.
 * @returns {JSX.Element} Delete confirmation modal.
 */
export function DeleteFieldSetModal( { fieldSet, onClose, onDeleted, setToast } ) {

	var confirmedState = useState( false );
	var isConfirmed = confirmedState[ 0 ];
	var setIsConfirmed = confirmedState[ 1 ];

	var isDeletingState = useState( false );
	var isDeleting = isDeletingState[ 0 ];
	var setIsDeleting = isDeletingState[ 1 ];

	/**
	 * Handle delete confirmation.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	function handleDelete() {
		if ( ! isConfirmed ) {
			return;
		}

		setIsDeleting( true );

		deleteFieldGroup( fieldSet.id )
			.then( function ( response ) {
				setIsDeleting( false );
				if ( response.success ) {
					setToast( {
						status: 'success',
						message: response.data.message || __( 'Field set deleted.', 'buddyboss' ),
					} );
					onDeleted();
				} else {
					setToast( {
						status: 'error',
						message: response.data?.message || __( 'Failed to delete field set.', 'buddyboss' ),
					} );
				}
			} )
			.catch( function ( error ) {
				setIsDeleting( false );
				setToast( { status: 'error', message: error.message || __( 'Failed to delete field set.', 'buddyboss' ) } );
			} );
	}

	return wp.element.createElement(
		Modal,
		{
			title: __( 'Delete Field Set', 'buddyboss' ),
			onRequestClose: onClose,
			className: 'bb-pf-delete-fieldset-modal',
			shouldCloseOnClickOutside: false,
		},

		wp.element.createElement(
			'div',
			{ className: 'bb-pf-delete-warning' },
			wp.element.createElement( 'i', { className: 'bb-icons-rl-warning bb-pf-warning-icon' } ),
			wp.element.createElement(
				'div',
				{ className: 'bb-pf-delete-warning-text' },
				wp.element.createElement( 'p', null,
					wp.element.createElement( 'strong', null, __( 'Warning:', 'buddyboss' ) ),
					' ',
					/* translators: %s: field set name */
					wp.element.sprintf(
						__( 'Deleting "%s" will permanently remove the field set and all the fields within it.', 'buddyboss' ),
						decodeEntities( fieldSet.name || '' )
					)
				),
				wp.element.createElement( 'p', null,
					__( 'Any user data stored in these fields will also be permanently deleted. This action cannot be undone.', 'buddyboss' )
				)
			)
		),

		wp.element.createElement( CheckboxControl, {
			label: __( 'I understand this deletes the field set and all its fields.', 'buddyboss' ),
			checked: isConfirmed,
			onChange: setIsConfirmed,
			className: 'bb-pf-delete-confirm-checkbox',
		} ),

		wp.element.createElement(
			'div',
			{ className: 'bb-pf-modal-footer' },
			wp.element.createElement(
				Button,
				{
					variant: 'secondary',
					onClick: onClose,
					disabled: isDeleting,
				},
				__( 'Cancel', 'buddyboss' )
			),
			wp.element.createElement(
				Button,
				{
					variant: 'primary',
					isDestructive: true,
					onClick: handleDelete,
					isBusy: isDeleting,
					disabled: ! isConfirmed || isDeleting,
				},
				isDeleting
					? wp.element.createElement( Spinner, null )
					: __( 'Delete Field Set', 'buddyboss' )
			)
		)
	);
}
