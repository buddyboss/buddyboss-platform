/**
 * BuddyBoss Admin Settings 2.0 — ColorPickerField Component
 *
 * Button + popover color picker, mirroring the ReadyLaunch onboarding UX
 * (see `admin/rl-onboarding/components/DynamicStepRenderer.js`) but styled
 * with Settings 2.0 tokens. Replaces the native `<input type="color">` used
 * previously by the `color` field type in `SettingsForm.js`.
 *
 * Behavior:
 *   - Button shows a color swatch + hex value.
 *   - Clicking the button opens a Popover with WP's ColorPicker + Apply button.
 *   - Changes are staged in local state so closing without Apply discards them
 *     (matches onboarding UX).
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import {
	Button,
	ColorIndicator,
	ColorPicker,
	Popover,
} from '@wordpress/components';

/**
 * Color picker button + popover field.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {Object}   props
 * @param {string}   props.value       Current color (hex string).
 * @param {Function} props.onChange    Called with the applied hex string.
 * @param {string}   [props.fallback]  Fallback color if value is empty.
 * @param {boolean}  [props.disabled]  Whether the field is disabled.
 * @returns {JSX.Element}
 */
export function ColorPickerField( { value, onChange, fallback = '#3E34FF', disabled = false } ) {
	var isOpenState    = useState( false );
	var isOpen         = isOpenState[ 0 ];
	var setIsOpen      = isOpenState[ 1 ];
	var tempColorState = useState( value );
	var tempColor      = tempColorState[ 0 ];
	var setTempColor   = tempColorState[ 1 ];

	var colorValue = value || fallback;

	function togglePicker() {
		if ( disabled ) {
			return;
		}
		setTempColor( colorValue ); // Reset staged color each open — cancel-on-close semantics.
		setIsOpen( ! isOpen );
	}

	function closePicker() {
		setIsOpen( false );
	}

	function applyColor() {
		onChange( tempColor || colorValue );
		closePicker();
	}

	return (
		<div className="bb-admin-settings-color-picker">
			<Button
				className="bb-admin-settings-color-picker__button"
				onClick={ togglePicker }
				aria-expanded={ isOpen }
				aria-label={ __( 'Select color', 'buddyboss' ) }
				disabled={ disabled }
			>
				<span className="bb-admin-settings-color-picker__swatch">
					<ColorIndicator colorValue={ colorValue } />
				</span>
				<span className="bb-admin-settings-color-picker__value">{ colorValue }</span>
			</Button>
			{ isOpen && (
				<Popover
					className="bb-admin-settings-color-picker__popover"
					onClose={ closePicker }
					position="bottom left"
				>
					<div className="bb-admin-settings-color-picker__popover-content">
						<ColorPicker
							color={ tempColor || colorValue }
							onChange={ setTempColor }
							enableAlpha={ false }
							copyFormat="hex"
						/>
						<div className="bb-admin-settings-color-picker__popover-footer">
							<Button
								className="bb-admin-settings-color-picker__apply"
								onClick={ applyColor }
							>
								{ __( 'Apply', 'buddyboss' ) }
							</Button>
						</div>
					</div>
				</Popover>
			) }
		</div>
	);
}
