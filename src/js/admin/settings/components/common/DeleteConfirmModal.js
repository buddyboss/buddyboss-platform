/**
 * BuddyBoss Admin Settings 2.0 - Delete Confirm Modal
 *
 * Shared delete confirmation modal with optional bulk item list,
 * warning banner, description, and "I understand" checkbox.
 * Supports single delete (no item list) and bulk delete (with item list).
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

import {
	Modal,
	Button,
	CheckboxControl,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { decodeEntities } from '@wordpress/html-entities';

/**
 * Delete Confirm Modal Component
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {Object}   props                Component props.
 * @param {boolean}  props.isOpen         Whether the modal is open.
 * @param {string}   props.singleTitle    Title for single-item delete (e.g., "Delete forum?").
 * @param {string}   props.bulkTitle      Title for bulk delete. Default "Bulk Delete".
 * @param {Array}    props.items          Array of items to delete. Each has `id` and `title` (or `name`).
 * @param {Function} props.onRemoveItem   Handler when an item is unchecked from the list. Receives item id.
 * @param {string}   props.warningText    Warning description text.
 * @param {string}   props.description    Full description text below warning.
 * @param {string}   props.confirmLabel   Checkbox label for "I understand..." confirmation.
 * @param {boolean}  props.confirmChecked Whether the confirm checkbox is checked.
 * @param {Function} props.onConfirmChange Confirm checkbox change handler.
 * @param {Function} props.onConfirm      Delete button click handler.
 * @param {Function} props.onClose        Close/cancel handler.
 * @param {string}   props.confirmText    Confirm button label. Default "Delete".
 * @param {boolean}  props.isProcessing   Whether delete is in progress.
 * @param {string}   props.className      CSS class for the modal (e.g., "bb-forum-delete-modal").
 * @returns {JSX.Element|null} Modal or null.
 */
export function DeleteConfirmModal( {
	isOpen,
	singleTitle,
	bulkTitle,
	items,
	onRemoveItem,
	warningText,
	description,
	confirmLabel,
	confirmChecked,
	onConfirmChange,
	onConfirm,
	onClose,
	confirmText = __( 'Delete', 'buddyboss-platform' ),
	isProcessing,
	className,
} ) {
	if ( ! isOpen || ! items || 0 === items.length ) {
		return null;
	}

	var isBulk = items.length > 1;
	var title = isBulk ? ( bulkTitle || __( 'Bulk Delete', 'buddyboss-platform' ) ) : ( singleTitle || __( 'Delete?', 'buddyboss-platform' ) );
	var modalClass = ( className || 'bb-delete-confirm-modal' ) + ' bb-admin-settings-modal';

	return (
		<Modal
			title={ title }
			onRequestClose={ onClose }
			className={ modalClass }
			shouldCloseOnClickOutside={ false }
		>
			<div className={ ( className || 'bb-delete-confirm-modal' ) + '__body' }>
				{ /* Bulk: show item list with checkboxes */ }
				{ isBulk && onRemoveItem && (
					<div className="bb-admin-bulk-modal__selected-items">
						{ items.map( function ( item ) {
							return (
								<div key={ item.id } className="bb-admin-bulk-modal__selected-item">
									<CheckboxControl
										checked={ true }
										onChange={ function () {
											onRemoveItem( item.id );
										} }
										__nextHasNoMarginBottom
									/>
									<span className="bb-admin-bulk-modal__selected-item-name">
										{ decodeEntities( item.title || item.name || '' ) }
									</span>
								</div>
							);
						} ) }
					</div>
				) }
				<div className="bb-admin-delete__warning">
					<i className="bb-icons-rl bb-icons-rl-warning-circle"></i>
					<div className="bb-admin-delete__warning-text">
						<span className="bb-admin-delete__warning-title">
							{ __( 'Warning', 'buddyboss-platform' ) }
						</span>
						<span className="bb-admin-delete__warning-desc">
							{ warningText }
						</span>
					</div>
				</div>
				{ description && (
					<p className={ ( className || 'bb-delete-confirm-modal' ) + '__description' }>
						{ description }
					</p>
				) }
				<CheckboxControl
					label={ confirmLabel }
					checked={ confirmChecked }
					onChange={ onConfirmChange }
					__nextHasNoMarginBottom
				/>
			</div>
			<div className={ ( className || 'bb-delete-confirm-modal' ) + '__footer' }>
				<Button
					variant="secondary"
					onClick={ onClose }
					disabled={ isProcessing }
				>
					{ __( 'Cancel', 'buddyboss-platform' ) }
				</Button>
				<Button
					variant="primary"
					isDestructive
					onClick={ onConfirm }
					isBusy={ isProcessing }
					disabled={ ! confirmChecked || isProcessing }
				>
					{ confirmText }
				</Button>
			</div>
		</Modal>
	);
}
