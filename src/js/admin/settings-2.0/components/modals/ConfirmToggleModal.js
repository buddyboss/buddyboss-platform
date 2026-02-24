/**
 * BuddyBoss Admin Settings 2.0 - Confirm Toggle Modal
 *
 * Generic reusable confirmation modal for toggle fields and other actions.
 * Shows a message with OK/Cancel buttons. Can be triggered by adding
 * `confirm_message` to any toggle field's PHP registration.
 *
 * Usage (PHP):
 *   bb_register_feature_field( 'feature', 'panel', 'section', array(
 *       'name'            => 'my-setting',
 *       'type'            => 'toggle',
 *       'confirm_message' => __( 'Warning message here.', 'buddyboss' ),
 *       // Optional overrides:
 *       'confirm_title'   => __( 'Custom Title', 'buddyboss' ),
 *       'confirm_ok'      => __( 'Enable', 'buddyboss' ),
 *       'confirm_cancel'  => __( 'Go Back', 'buddyboss' ),
 *       'confirm_destructive' => true,
 *   ) );
 *
 * Usage (React standalone):
 *   <ConfirmToggleModal
 *       isOpen={true}
 *       message="Are you sure?"
 *       onConfirm={() => {}}
 *       onCancel={() => {}}
 *       title="Custom Title"
 *       confirmLabel="Enable"
 *       cancelLabel="Go Back"
 *       isDestructive={true}
 *   />
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

import { Modal, Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Confirm Toggle Modal
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {Object}   props                Component props.
 * @param {boolean}  props.isOpen         Whether the modal is open.
 * @param {string}   props.message        Confirmation message to display.
 * @param {Function} props.onConfirm      Confirm handler.
 * @param {Function} props.onCancel       Cancel handler.
 * @param {string}   [props.title]        Modal title. Defaults to "Are you sure?".
 * @param {string}   [props.confirmLabel] Confirm button label. Defaults to "OK".
 * @param {string}   [props.cancelLabel]  Cancel button label. Defaults to "Cancel".
 * @param {boolean}  [props.isDestructive] Whether confirm button is destructive (red).
 * @returns {JSX.Element|null} Modal component or null.
 */
export function ConfirmToggleModal( {
	isOpen,
	message,
	onConfirm,
	onCancel,
	title,
	confirmLabel,
	cancelLabel,
	isDestructive,
} ) {
	if ( ! isOpen ) {
		return null;
	}

	return (
		<Modal
			title={ title || __( 'Are you sure?', 'buddyboss' ) }
			onRequestClose={ onCancel }
			className="bb-confirm-toggle-modal bb-admin-settings-modal"
			shouldCloseOnClickOutside={ false }
		>
			<div className="bb-confirm-toggle-modal__body">
				<p>{ message }</p>
			</div>

			<div className="bb-confirm-toggle-modal__footer">
				<Button
					variant="secondary"
					onClick={ onCancel }
				>
					{ cancelLabel || __( 'Cancel', 'buddyboss' ) }
				</Button>
				<Button
					variant="primary"
					isDestructive={ !!isDestructive }
					onClick={ onConfirm }
				>
					{ confirmLabel || __( 'OK', 'buddyboss' ) }
				</Button>
			</div>
		</Modal>
	);
}
