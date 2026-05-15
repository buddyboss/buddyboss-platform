/**
 * BuddyBoss Admin Settings 2.0 - Modify Duration Modal
 *
 * Opened from the "Modify Duration" button on the Support Access screen.
 * Lets the admin extend the support-access expiry window.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

import { useState, useEffect } from '@wordpress/element';
import { SelectControl, Button, Modal } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Duration options for the "Extend support access by" select.
 *
 * @since BuddyBoss [BBVERSION]
 */
var DURATION_OPTIONS = [
	{ label: __( '1 day', 'buddyboss' ), value: '1' },
	{ label: __( '3 days', 'buddyboss' ), value: '3' },
	{ label: __( '5 days', 'buddyboss' ), value: '5' },
	{ label: __( '7 days', 'buddyboss' ), value: '7' },
	{ label: __( '14 days', 'buddyboss' ), value: '14' },
	{ label: __( '30 days', 'buddyboss' ), value: '30' },
];

/**
 * Modify Duration Modal Component
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {Object}   props          Component props.
 * @param {boolean}  props.isOpen   Whether the modal is open.
 * @param {string}   props.value    Current duration value (days as string).
 * @param {Function} props.onClose  Close handler.
 * @param {Function} props.onSave   Save handler — receives the selected value.
 * @returns {JSX.Element|null} Modal element or null.
 */
export function ModifyDurationModal( { isOpen, value, onClose, onSave } ) {
	var durationState = useState( value || '5' );
	var duration = durationState[ 0 ];
	var setDuration = durationState[ 1 ];

	useEffect( function () {
		if ( isOpen ) {
			setDuration( value || '5' );
		}
	}, [ isOpen, value ] );

	if ( ! isOpen ) {
		return null;
	}

	var handleSave = function () {
		if ( 'function' === typeof onSave ) {
			onSave( duration );
		}
	};

	return (
		<Modal
			title={ __( 'Modify Access Duration', 'buddyboss' ) }
			onRequestClose={ onClose }
			className="bb-admin-modify-duration-modal bb-admin-settings-modal"
			shouldCloseOnClickOutside={ false }
		>
			<div className="bb-admin-settings-modal__body">
				<SelectControl
					label={ __( 'Extend support access by:', 'buddyboss' ) }
					value={ duration }
					options={ DURATION_OPTIONS }
					onChange={ function ( val ) { setDuration( val ); } }
					__nextHasNoMarginBottom
				/>
			</div>

			<div className="bb-admin-settings-modal__footer">
				<Button variant="secondary" onClick={ onClose }>
					{ __( 'Cancel', 'buddyboss' ) }
				</Button>
				<Button variant="primary" onClick={ handleSave }>
					{ __( 'Save', 'buddyboss' ) }
				</Button>
			</div>
		</Modal>
	);
}

export default ModifyDurationModal;
