/**
 * BuddyBoss Admin Settings 2.0 - Add Ticket Number Modal
 *
 * Opened from the "Add Ticket Number" button on the Support Access screen.
 * Lets the admin attach a support ticket number to the current session.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

import { useState, useEffect } from '@wordpress/element';
import { TextControl, Button, Modal } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Add Ticket Modal Component
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {Object}   props         Component props.
 * @param {boolean}  props.isOpen  Whether the modal is open.
 * @param {string}   props.value   Current ticket number value.
 * @param {Function} props.onClose Close handler.
 * @param {Function} props.onSave  Save handler — receives the entered value.
 * @returns {JSX.Element|null} Modal element or null.
 */
export function AddTicketModal( { isOpen, value, onClose, onSave } ) {
	var ticketState = useState( value || '' );
	var ticket = ticketState[ 0 ];
	var setTicket = ticketState[ 1 ];

	useEffect( function () {
		if ( isOpen ) {
			setTicket( value || '' );
		}
	}, [ isOpen, value ] );

	if ( ! isOpen ) {
		return null;
	}

	// A ticket ID must be a positive integer — mirror the server-side check
	// (ctype_digit + > 0 in BB_Support_Access::ajax_set_ticket()) so the admin
	// gets immediate feedback instead of only a post-submit error toast.
	var trimmedTicket = ticket.trim();
	var isValidTicket = /^[0-9]+$/.test( trimmedTicket ) && parseInt( trimmedTicket, 10 ) > 0;

	var handleSave = function () {
		if ( ! isValidTicket ) {
			return;
		}
		if ( 'function' === typeof onSave ) {
			onSave( trimmedTicket );
		}
	};

	return (
		<Modal
			title={ __( 'Add Ticket Number', 'buddyboss' ) }
			onRequestClose={ onClose }
			className="bb-admin-add-ticket-modal bb-admin-settings-modal"
			shouldCloseOnClickOutside={ false }
		>
			<div className="bb-admin-settings-modal__body">
				<TextControl
					className="bb-admin-add-ticket-modal__input"
					label={ __( 'Ticket number', 'buddyboss' ) }
					hideLabelFromVision
					type="number"
					inputMode="numeric"
					min="1"
					placeholder={ __( 'e.g. 12345', 'buddyboss' ) }
					help={ __( 'Enter the numeric support ticket ID.', 'buddyboss' ) }
					value={ ticket }
					onChange={ function ( val ) { setTicket( val ); } }
					__nextHasNoMarginBottom
				/>
			</div>

			<div className="bb-admin-settings-modal__footer">
				<Button variant="secondary" onClick={ onClose }>
					{ __( 'Cancel', 'buddyboss' ) }
				</Button>
				<Button variant="primary" onClick={ handleSave } disabled={ ! isValidTicket }>
					{ __( 'Save', 'buddyboss' ) }
				</Button>
			</div>
		</Modal>
	);
}

export default AddTicketModal;
