/**
 * BuddyBoss Admin Settings 2.0 - Bulk Edit Modal
 *
 * Shared modal wrapper for bulk edit operations. Renders selected items
 * with unchecking, a children slot for entity-specific form fields,
 * and Cancel/Save footer buttons.
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
 * Bulk Edit Modal Component
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {Object}      props              Component props.
 * @param {boolean}     props.isOpen       Whether the modal is open.
 * @param {Array}       props.items        Selected items array. Each has `id` and `title` (or `name`).
 * @param {Function}    props.onRemoveItem Handler when item is unchecked. Receives item id.
 * @param {Function}    props.onConfirm    Save button click handler.
 * @param {Function}    props.onClose      Close/cancel handler.
 * @param {boolean}     props.confirmDisabled Whether the Save button should be disabled.
 * @param {string}      props.className    CSS class for the modal (e.g., "bb-forum-bulk-edit-modal").
 * @param {JSX.Element} props.children     Entity-specific form fields (Status, Visibility, Tags, etc.).
 * @returns {JSX.Element|null} Modal or null.
 */
export function BulkEditModal( {
	isOpen,
	items,
	onRemoveItem,
	onConfirm,
	onClose,
	confirmDisabled,
	className,
	children,
} ) {
	if ( ! isOpen || ! items || 0 === items.length ) {
		return null;
	}

	var modalClass = ( className || 'bb-bulk-edit-modal' ) + ' bb-admin-settings-modal';

	return (
		<Modal
			title={ __( 'Bulk Edit', 'buddyboss-platform' ) }
			onRequestClose={ onClose }
			className={ modalClass }
			shouldCloseOnClickOutside={ false }
		>
			<div className={ ( className || 'bb-bulk-edit-modal' ) + '__body' }>
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

				{ children }
			</div>
			<div className={ ( className || 'bb-bulk-edit-modal' ) + '__footer' }>
				<Button
					variant="secondary"
					onClick={ onClose }
				>
					{ __( 'Cancel', 'buddyboss-platform' ) }
				</Button>
				<Button
					variant="primary"
					onClick={ onConfirm }
					disabled={ confirmDisabled }
				>
					{ __( 'Save', 'buddyboss-platform' ) }
				</Button>
			</div>
		</Modal>
	);
}
