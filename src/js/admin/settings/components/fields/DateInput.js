/**
 * BuddyBoss Admin Settings 2.0 - Date Input
 *
 * Button-triggered date picker using WP DatePicker in a Popover.
 * Adapted from BuddyBoss Gamification DateTimeInputs pattern.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

import { Button, DatePicker, Popover } from '@wordpress/components';
import { useState, useRef, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * Date Input Component
 *
 * Renders a button that opens a Popover with a WP DatePicker calendar.
 * Displays the selected date formatted as dd/mm/yyyy.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {Object}   props             Component props.
 * @param {string}   props.label       Field label.
 * @param {string}   props.value       Current date value (YYYY-MM-DD or ISO string).
 * @param {Function} props.onChange     Change handler receiving date string.
 * @param {string}   props.placeholder Placeholder text.
 * @param {boolean}  props.disabled    Whether the input is disabled.
 * @returns {JSX.Element} Date input component.
 */
export function DateInput( props ) {
	var label = props.label || '';
	var value = props.value || null;
	var onChange = props.onChange;
	var placeholder = props.placeholder || __( 'dd/mm/yyyy', 'buddyboss' );
	var disabled = props.disabled || false;

	var isOpenState = useState( false );
	var isOpen = isOpenState[ 0 ];
	var setIsOpen = isOpenState[ 1 ];

	var selectedDateState = useState( value );
	var selectedDate = selectedDateState[ 0 ];
	var setSelectedDate = selectedDateState[ 1 ];

	var buttonRef = useRef();

	// Sync external value with internal state.
	useEffect( function () {
		setSelectedDate( value );
	}, [ value ] );

	/**
	 * Format date for display (dd/mm/yyyy).
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {string|Date} date Date value.
	 * @returns {string} Formatted date string.
	 */
	var formatDate = function ( date ) {
		if ( ! date ) {
			return '';
		}
		var d = new Date( date );
		if ( isNaN( d.getTime() ) ) {
			return '';
		}
		var day = String( d.getDate() ).padStart( 2, '0' );
		var month = String( d.getMonth() + 1 ).padStart( 2, '0' );
		var year = d.getFullYear();
		return day + '/' + month + '/' + year;
	};

	/**
	 * Handle date selection from the calendar.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {string} newDate Selected date from DatePicker (ISO format).
	 */
	var handleDateChange = function ( newDate ) {
		// Extract YYYY-MM-DD from the ISO string.
		var dateOnly = newDate ? newDate.split( 'T' )[ 0 ] : '';
		setSelectedDate( dateOnly );
		if ( onChange ) {
			onChange( dateOnly );
		}
		setIsOpen( false );
	};

	var displayValue = selectedDate ? formatDate( selectedDate ) : '';

	return (
		<div className="bb-admin-date-input">
			{ label && (
				<label className="bb-admin-meta-field__label">{ label }</label>
			) }
			<div className="bb-admin-date-input__wrapper">
				<Button
					ref={ buttonRef }
					className="bb-admin-date-input__button"
					onClick={ function () {
						if ( ! disabled ) {
							setIsOpen( ! isOpen );
						}
					} }
					disabled={ disabled }
				>
					<span className={ 'bb-admin-date-input__value' + ( ! displayValue ? ' bb-admin-date-input__value--placeholder' : '' ) }>
						{ displayValue || placeholder }
					</span>
					<span className="bb-icons-rl-calendar-blank bb-admin-date-input__icon"></span>
				</Button>

				{ isOpen && (
					<Popover
						anchor={ buttonRef.current }
						position="bottom left"
						onClose={ function () {
							setIsOpen( false );
						} }
						className="bb-admin-date-input__popover"
					>
						<div className="bb-admin-date-input__picker">
							<DatePicker
								currentDate={ selectedDate || new Date().toISOString() }
								onChange={ handleDateChange }
							/>
						</div>
					</Popover>
				) }
			</div>
		</div>
	);
}
