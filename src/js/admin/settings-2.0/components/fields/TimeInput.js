/**
 * BuddyBoss Admin Settings 2.0 - Time Input
 *
 * Button-triggered time picker with hour/minute inputs in a Popover.
 * Adapted from BuddyBoss Gamification DateTimeInputs pattern.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

import { Button, Popover } from '@wordpress/components';
import { useState, useRef, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * Time Input Component
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {Object}   props             Component props.
 * @param {string}   props.label       Field label.
 * @param {string}   props.value       Current time value (HH:MM format).
 * @param {Function} props.onChange     Change handler receiving time string.
 * @param {string}   props.placeholder Placeholder text.
 * @param {boolean}  props.disabled    Whether the input is disabled.
 * @returns {JSX.Element} Time input component.
 */
export function TimeInput( props ) {
	var label = props.label || '';
	var value = props.value || '';
	var onChange = props.onChange;
	var placeholder = props.placeholder || __( 'hh:mm', 'buddyboss' );
	var disabled = props.disabled || false;

	var isOpenState = useState( false );
	var isOpen = isOpenState[ 0 ];
	var setIsOpen = isOpenState[ 1 ];

	// Display value — what shows on the button. Updated on "Set Time" click.
	var displayValueState = useState( value );
	var displayValue = displayValueState[ 0 ];
	var setDisplayValue = displayValueState[ 1 ];

	// Draft values — what's in the popover inputs. Not committed until "Set Time".
	var draftHoursState = useState( '00' );
	var draftHours = draftHoursState[ 0 ];
	var setDraftHours = draftHoursState[ 1 ];

	var draftMinutesState = useState( '00' );
	var draftMinutes = draftMinutesState[ 0 ];
	var setDraftMinutes = draftMinutesState[ 1 ];

	var buttonRef = useRef();

	// Sync external value prop to display and draft state.
	useEffect( function () {
		if ( value ) {
			setDisplayValue( value );
			var parts = value.split( ':' );
			if ( parts.length >= 2 ) {
				setDraftHours( String( parseInt( parts[ 0 ], 10 ) || 0 ).padStart( 2, '0' ) );
				setDraftMinutes( String( parseInt( parts[ 1 ], 10 ) || 0 ).padStart( 2, '0' ) );
			}
		}
	}, [ value ] );

	/**
	 * Handle "Set Time" button click — commit draft values.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	var handleSetTime = function () {
		var timeString = draftHours + ':' + draftMinutes;
		setDisplayValue( timeString );
		if ( onChange ) {
			onChange( timeString );
		}
		setIsOpen( false );
	};

	/**
	 * Handle hours change with clamping.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {Event} e Input change event.
	 */
	var handleHoursChange = function ( e ) {
		var val = parseInt( e.target.value, 10 );
		if ( isNaN( val ) ) {
			val = 0;
		}
		val = Math.max( 0, Math.min( 23, val ) );
		setDraftHours( String( val ).padStart( 2, '0' ) );
	};

	/**
	 * Handle minutes change with clamping.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {Event} e Input change event.
	 */
	var handleMinutesChange = function ( e ) {
		var val = parseInt( e.target.value, 10 );
		if ( isNaN( val ) ) {
			val = 0;
		}
		val = Math.max( 0, Math.min( 59, val ) );
		setDraftMinutes( String( val ).padStart( 2, '0' ) );
	};

	return (
		<div className="bb-admin-time-input">
			{ label && (
				<label className="bb-admin-meta-field__label">{ label }</label>
			) }
			<div className="bb-admin-time-input__wrapper">
				<Button
					ref={ buttonRef }
					className="bb-admin-time-input__button"
					onClick={ function () {
						if ( ! disabled ) {
							setIsOpen( ! isOpen );
						}
					} }
					disabled={ disabled }
				>
					<span className={ 'bb-admin-time-input__value' + ( ! displayValue ? ' bb-admin-time-input__value--placeholder' : '' ) }>
						{ displayValue || placeholder }
					</span>
					<span className="bb-icons-rl-clock bb-admin-time-input__icon"></span>
				</Button>

				{ isOpen && (
					<Popover
						anchor={ buttonRef.current }
						position="bottom left"
						onClose={ function () {
							setIsOpen( false );
						} }
						className="bb-admin-time-input__popover"
					>
						<div className="bb-admin-time-input__picker">
							<div className="bb-admin-time-input__fields">
								<div className="bb-admin-time-input__field-group">
									<label className="bb-admin-time-input__field-label">
										{ __( 'Hour', 'buddyboss' ) }
									</label>
									<input
										type="number"
										className="bb-admin-time-input__field"
										value={ draftHours }
										onChange={ handleHoursChange }
										min="0"
										max="23"
									/>
								</div>
								<div className="bb-admin-time-input__separator">:</div>
								<div className="bb-admin-time-input__field-group">
									<label className="bb-admin-time-input__field-label">
										{ __( 'Minute', 'buddyboss' ) }
									</label>
									<input
										type="number"
										className="bb-admin-time-input__field"
										value={ draftMinutes }
										onChange={ handleMinutesChange }
										min="0"
										max="59"
									/>
								</div>
							</div>
							<Button
								className="bb-admin-time-input__set-btn"
								variant="primary"
								onClick={ handleSetTime }
							>
								{ __( 'Set Time', 'buddyboss' ) }
							</Button>
						</div>
					</Popover>
				) }
			</div>
		</div>
	);
}
