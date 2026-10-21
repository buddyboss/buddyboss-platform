/**
 * BuddyBoss Admin Settings 2.0 - Email Template Bulk Delete Modal
 *
 * Confirmation modal for permanently deleting email templates.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

import { useState, useRef, useEffect } from '@wordpress/element';
import {
	Modal,
	Button,
	CheckboxControl,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { decodeEntities } from '@wordpress/html-entities';
import { deleteEmailTemplates } from '../../utils/ajax';

/**
 * Email Template Bulk Delete Modal Component
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {Object}   props               Component props.
 * @param {boolean}  props.isOpen        Whether the modal is open.
 * @param {Array}    props.selectedItems Selected items [{id, title}].
 * @param {Function} props.onRemoveItem  Handler when an item is unchecked. Receives item id.
 * @param {Function} props.onClose       Close handler.
 * @param {Function} props.onDeleted     Success handler.
 * @param {Function} props.setToast      Toast notification setter.
 * @returns {JSX.Element|null} Modal or null.
 */
export function EmailTemplateBulkDeleteModal( { isOpen, selectedItems, onRemoveItem, onClose, onDeleted, setToast } ) {
	var confirmedState = useState( false );
	var isConfirmed = confirmedState[0];
	var setIsConfirmed = confirmedState[1];

	var deletingState = useState( false );
	var isDeleting = deletingState[0];
	var setIsDeleting = deletingState[1];

	var isMountedRef = useRef( true );

	useEffect( function () {
		isMountedRef.current = true;
		return function () {
			isMountedRef.current = false;
		};
	}, [] );

	if ( ! isOpen ) {
		return null;
	}

	var handleClose = function () {
		setIsConfirmed( false );
		onClose();
	};

	var handleDelete = function () {
		if ( ! isConfirmed || isDeleting ) {
			return;
		}

		setIsDeleting( true );

		var ids = selectedItems.map( function ( item ) {
			return item.id;
		} );

		deleteEmailTemplates( ids ).then( function ( response ) {
			if ( ! isMountedRef.current ) {
				return;
			}
			setIsDeleting( false );
			if ( response.success ) {
				setIsConfirmed( false );
				if ( setToast ) {
					setToast( { status: 'success', message: response.data.message } );
				}
				if ( onDeleted ) {
					onDeleted();
				}
			} else {
				if ( setToast ) {
					setToast( {
						status: 'error',
						message: ( response.data && response.data.message ) || __( 'Failed to delete.', 'buddyboss' ),
					} );
				}
			}
		} ).catch( function () {
			if ( ! isMountedRef.current ) {
				return;
			}
			setIsDeleting( false );
			if ( setToast ) {
				setToast( { status: 'error', message: __( 'An error occurred.', 'buddyboss' ) } );
			}
		} );
	};

	return (
		<Modal
			title={ __( 'Bulk Delete', 'buddyboss' ) }
			onRequestClose={ handleClose }
			className="bb-admin-settings-modal bb-email-template-modal bb-email-template-modal--bulk-delete"
			shouldCloseOnClickOutside={ false }
		>
			<div className="bb-admin-settings-modal__body bb-email-template-modal__body">
				{/* Selected items */}
				<div className="bb-admin-bulk-modal__selected-items">
					{ selectedItems.map( function ( item ) {
						return (
							<div key={ item.id } className="bb-admin-bulk-modal__selected-item">
								<CheckboxControl
									checked={ true }
									onChange={ function () {
										if ( onRemoveItem ) {
											onRemoveItem( item.id );
										}
									} }
									__nextHasNoMarginBottom
								/>
								<span className="bb-admin-bulk-modal__selected-item-name">
									{ decodeEntities( item.title ) }
								</span>
							</div>
						);
					} ) }
				</div>

				{/* Warning */}
				<div className="bb-email-template-modal__delete-warning">
					<i className="bb-icons-rl bb-icons-rl-warning-circle" />
					<div>
						<p><strong>{ __( 'Warning', 'buddyboss' ) }</strong></p>
						<p>{ __( 'This permanently deletes email templates and cannot be undone.', 'buddyboss' ) }</p>
					</div>
				</div>

				<p className="bb-email-template-modal__delete-description">
					{ __( 'Deleting the email template will remove it from the list and automatically unlink it from any associated situations.', 'buddyboss' ) }
				</p>

				{/* Confirmation checkbox */}
				<div className="bb-email-template-modal__confirm-checkbox">
					<CheckboxControl
						label={ __( 'I understand that this deletes the email templates.', 'buddyboss' ) }
						checked={ isConfirmed }
						onChange={ setIsConfirmed }
						__nextHasNoMarginBottom
					/>
				</div>
			</div>

			<div className="bb-admin-settings-modal__footer bb-email-template-modal__footer">
				<Button variant="secondary" onClick={ handleClose } disabled={ isDeleting }>
					{ __( 'Cancel', 'buddyboss' ) }
				</Button>
				<Button
					className="bb-admin-button-danger"
					onClick={ handleDelete }
					isBusy={ isDeleting }
					disabled={ ! isConfirmed || isDeleting }
				>
					{ isDeleting ? __( 'Deleting...', 'buddyboss' ) : __( 'Delete', 'buddyboss' ) }
				</Button>
			</div>
		</Modal>
	);
}
