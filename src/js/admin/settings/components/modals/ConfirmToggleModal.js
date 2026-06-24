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
 *       'confirm_message' => __( 'Warning message here.', 'buddyboss-platform' ),
 *       // Optional overrides:
 *       'confirm_title'   => __( 'Custom Title', 'buddyboss-platform' ),
 *       'confirm_ok'      => __( 'Enable', 'buddyboss-platform' ),
 *       'confirm_cancel'  => __( 'Go Back', 'buddyboss-platform' ),
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
import { sanitizeHtml } from '../../utils/sanitize';

/**
 * Confirm Toggle Modal
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {Object}   props                  Component props.
 * @param {boolean}  props.isOpen           Whether the modal is open.
 * @param {string}   props.message          Confirmation message to display.
 * @param {Function} props.onConfirm        Confirm handler.
 * @param {Function} props.onCancel         Cancel handler.
 * @param {string}   [props.title]          Modal title. Defaults to "Are you sure?".
 * @param {string}   [props.confirmLabel]   Confirm button label. Defaults to "OK".
 * @param {string}   [props.cancelLabel]    Cancel button label. Defaults to "Cancel".
 * @param {boolean}  [props.isDestructive]  Whether confirm button is destructive (red).
 * @param {boolean}  [props.messageIsHtml]  Render the message as DOMPurify-sanitised
 *                                          HTML instead of plain text. Use when the
 *                                          caller has structured copy with paragraphs,
 *                                          lists, or headings (e.g. the legacy
 *                                          Moderation deactivation warning). Defaults
 *                                          to false so existing single-line callers
 *                                          (Group Type, Profile Fields, etc.) stay
 *                                          unchanged.
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
	messageIsHtml,
} ) {
	if ( ! isOpen ) {
		return null;
	}

	return (
		<Modal
			title={ title || __( 'Are you sure?', 'buddyboss-platform' ) }
			onRequestClose={ onCancel }
			className="bb-confirm-toggle-modal bb-admin-settings-modal"
			shouldCloseOnClickOutside={ false }
		>
			<div className="bb-admin-settings-modal__body bb-confirm-toggle-modal__body">
				{ messageIsHtml ? (
					// PHP already gates the source via wp_kses_post() — we
					// double-sanitise via DOMPurify here to match the project's
					// established defence-in-depth pattern (see FeatureSettingsScreen
					// help drawer, ProfileTypeScreen help text, EmailTemplatesListScreen
					// custom columns, etc.). This is the only sanctioned way to
					// render server-supplied admin HTML in Settings 2.0.
					<div
						className="bb-confirm-toggle-modal__html-message"
						dangerouslySetInnerHTML={ { __html: sanitizeHtml( message ) } }
					/>
				) : (
					<p>{ message }</p>
				) }
			</div>

			<div className="bb-admin-settings-modal__footer bb-confirm-toggle-modal__footer">
				<Button
					variant="secondary"
					onClick={ onCancel }
				>
					{ cancelLabel || __( 'Cancel', 'buddyboss-platform' ) }
				</Button>
				<Button
					variant="primary"
					isDestructive={ !!isDestructive }
					onClick={ onConfirm }
				>
					{ confirmLabel || __( 'OK', 'buddyboss-platform' ) }
				</Button>
			</div>
		</Modal>
	);
}
