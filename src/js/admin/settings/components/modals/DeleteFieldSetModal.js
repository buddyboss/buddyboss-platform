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
	Modal,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
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
						message: response.data.message || __( 'Field set deleted.', 'buddyboss-platform' ),
					} );
					onDeleted();
				} else {
					setToast( {
						status: 'error',
						message: ( response.data && response.data.message ) || __( 'Failed to delete field set.', 'buddyboss-platform' ),
					} );
				}
			} )
			.catch( function ( error ) {
				setIsDeleting( false );
				setToast( { status: 'error', message: error.message || __( 'Failed to delete field set.', 'buddyboss-platform' ) } );
			} );
	}

	return (
		<Modal
			title={ __( 'Delete field set?', 'buddyboss-platform' ) }
			onRequestClose={ onClose }
			className="bb-pf-delete-fieldset-modal bb-admin-settings-modal"
			shouldCloseOnClickOutside={ false }
		>
			<div className="bb-pf-delete-fieldset-modal__body bb-admin-settings-modal__body">
				<div className="bb-admin-delete__warning">
					<i className="bb-icons-rl bb-icons-rl-warning-circle"></i>
					<div className="bb-admin-delete__warning-text">
						<span className="bb-admin-delete__warning-title">
							{ __( 'Warning', 'buddyboss-platform' ) }
						</span>
						<span className="bb-admin-delete__warning-desc">
							{ __( 'This permanently deletes the field set and all the fields within it. This action cannot be undone.', 'buddyboss-platform' ) }
						</span>
					</div>
				</div>
				<CheckboxControl
					label={ __( 'I understand this deletes the field set and all its fields.', 'buddyboss-platform' ) }
					checked={ isConfirmed }
					onChange={ setIsConfirmed }
					__nextHasNoMarginBottom
				/>
			</div>

			<div className="bb-pf-delete-fieldset-modal__footer bb-admin-settings-modal__footer">
				<Button
					variant="secondary"
					onClick={ onClose }
					disabled={ isDeleting }
				>
					{ __( 'Cancel', 'buddyboss-platform' ) }
				</Button>
				<Button
					onClick={ handleDelete }
					isBusy={ isDeleting }
					disabled={ ! isConfirmed || isDeleting }
					className="bb-admin-button-danger"
				>
					{ __( 'Delete Field Set', 'buddyboss-platform' ) }
				</Button>
			</div>
		</Modal>
	);
}
