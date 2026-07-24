/**
 * BuddyBoss Admin Settings 2.0 - Time Input
 *
 * Editable time input with scrollable time list dropdown in a Popover.
 * Users can type a time manually (HH:MM) or pick from hourly slots.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

import { Popover } from '@wordpress/components';
import { useState, useRef, useEffect, useCallback } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * Generate time slots at the given interval (in minutes).
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {number} interval Minutes between each slot (must be >= 1).
 * @returns {Array} Array of time strings in HH:MM format.
 */
function generateTimeSlots( interval ) {
	var safeInterval = ( interval && interval >= 1 ) ? Math.floor( interval ) : 60;
	var slots = [];
	for ( var minutes = 0; minutes < 24 * 60; minutes += safeInterval ) {
		var h = Math.floor( minutes / 60 );
		var m = minutes % 60;
		slots.push( String( h ).padStart( 2, '0' ) + ':' + String( m ).padStart( 2, '0' ) );
	}
	return slots;
}

/**
 * Normalize a manually typed time string to HH:MM format.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {string} raw Raw input string.
 * @returns {string} Normalized HH:MM string, or empty if invalid.
 */
function normalizeTime( raw ) {
	if ( ! raw ) {
		return '';
	}
	var parts = raw.replace( /[^\d:]/g, '' ).split( ':' );
	if ( parts.length < 2 ) {
		return '';
	}
	var h = parseInt( parts[ 0 ], 10 );
	var m = parseInt( parts[ 1 ], 10 );
	if ( isNaN( h ) || isNaN( m ) || h < 0 || h > 23 || m < 0 || m > 59 ) {
		return '';
	}
	return String( h ).padStart( 2, '0' ) + ':' + String( m ).padStart( 2, '0' );
}

/**
 * Time Input Component
 *
 * Renders an editable text input that also opens a Popover with a
 * scrollable list of time slots. Users can type HH:MM or pick from the list.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {Object}   props             Component props.
 * @param {string}   props.label       Field label.
 * @param {string}   props.value       Current time value (HH:MM format).
 * @param {Function} props.onChange     Change handler receiving time string.
 * @param {string}   props.placeholder Placeholder text.
 * @param {boolean}  props.disabled    Whether the input is disabled.
 * @param {number}   props.interval    Minutes between time slots (default: 60).
 * @returns {JSX.Element} Time input component.
 */
export function TimeInput( props ) {
	var label = props.label || '';
	var value = props.value || '';
	var onChange = props.onChange;
	var placeholder = props.placeholder || __( 'hh:mm', 'buddyboss-platform' );
	var disabled = props.disabled || false;
	var interval = props.interval || 60;

	var isOpenState = useState( false );
	var isOpen = isOpenState[ 0 ];
	var setIsOpen = isOpenState[ 1 ];

	var inputValueState = useState( value );
	var inputValue = inputValueState[ 0 ];
	var setInputValue = inputValueState[ 1 ];

	var wrapperRef = useRef();
	var inputRef = useRef();
	var listRef = useRef();
	var selectedItemRef = useRef();

	var timeSlots = generateTimeSlots( interval );

	// Sync external value prop to input state.
	useEffect( function () {
		setInputValue( value || '' );
	}, [ value ] );

	// Scroll to the selected/closest time when popover opens.
	useEffect( function () {
		if ( isOpen && selectedItemRef.current && listRef.current ) {
			selectedItemRef.current.scrollIntoView( {
				block: 'center',
				behavior: 'instant',
			} );
		}
	}, [ isOpen ] );

	/**
	 * Handle time slot selection from list.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {string} time Selected time string.
	 */
	var handleSelect = useCallback( function ( time ) {
		setInputValue( time );
		if ( onChange ) {
			onChange( time );
		}
		setIsOpen( false );
	}, [ onChange ] );

	/**
	 * Handle manual input change.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {Event} e Input change event.
	 */
	var handleInputChange = function ( e ) {
		setInputValue( e.target.value );
	};

	/**
	 * Commit the typed value on blur.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	var handleBlur = function ( e ) {
		// Don't close if clicking inside the popover.
		if ( wrapperRef.current && wrapperRef.current.contains( e.relatedTarget ) ) {
			return;
		}

		var trimmed = inputValue.trim();

		// Empty input — revert to last valid value.
		if ( ! trimmed ) {
			setInputValue( value || '' );
			setIsOpen( false );
			return;
		}

		var normalized = normalizeTime( trimmed );
		if ( normalized ) {
			setInputValue( normalized );
			if ( onChange && normalized !== value ) {
				onChange( normalized );
			}
		} else {
			// Invalid input — revert to last valid value.
			setInputValue( value || '' );
		}
		setIsOpen( false );
	};

	/**
	 * Handle keyboard events — Enter to commit, Escape to close.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {Event} e Keydown event.
	 */
	var handleKeyDown = function ( e ) {
		if ( 'Enter' === e.key ) {
			e.preventDefault();
			var normalized = normalizeTime( inputValue );
			if ( normalized ) {
				setInputValue( normalized );
				if ( onChange && normalized !== value ) {
					onChange( normalized );
				}
			}
			setIsOpen( false );
		} else if ( 'Escape' === e.key && isOpen ) {
			e.preventDefault();
			e.stopPropagation();
			setInputValue( value || '' );
			setIsOpen( false );
		}
	};

	// Pre-compute normalized value once for the list comparison.
	var normalizedInput = normalizeTime( inputValue ) || value;

	return (
		<div className="bb-admin-time-input" ref={ wrapperRef }>
			{ label && (
				<label className="bb-admin-meta-field__label">{ label }</label>
			) }
			<div className="bb-admin-time-input__wrapper">
				<div
					className="bb-admin-time-input__button"
					ref={ inputRef }
					onClick={ function () {
						if ( ! disabled && ! isOpen ) {
							setIsOpen( true );
						}
					} }
				>
					<input
						type="text"
						className="bb-admin-time-input__input"
						value={ inputValue }
						onChange={ handleInputChange }
						onFocus={ function () {
							if ( ! disabled ) {
								setIsOpen( true );
							}
						} }
						onBlur={ handleBlur }
						onKeyDown={ handleKeyDown }
						placeholder={ placeholder }
						disabled={ disabled }
						autoComplete="off"
					/>
					<span className="bb-icons-rl-clock bb-admin-time-input__icon"></span>
				</div>

				{ isOpen && (
					<Popover
						anchor={ inputRef.current }
						position="bottom left"
						onFocusOutside={ function () {
							// Let handleBlur manage close to avoid race conditions.
						} }
						className="bb-admin-time-input__popover"
					>
						<div
							className="bb-admin-time-input__list"
							ref={ listRef }
							role="listbox"
							aria-label={ __( 'Select time', 'buddyboss-platform' ) }
						>
							{ timeSlots.map( function ( time ) {
								var isSelected = time === normalizedInput;
								return (
									<button
										key={ time }
										ref={ isSelected ? selectedItemRef : null }
										className={ 'bb-admin-time-input__item' + ( isSelected ? ' bb-admin-time-input__item--selected' : '' ) }
										role="option"
										aria-selected={ isSelected }
										onMouseDown={ function ( e ) {
											// Prevent blur from firing before click.
											e.preventDefault();
										} }
										onClick={ function () {
											handleSelect( time );
										} }
									>
										{ time }
									</button>
								);
							} ) }
						</div>
					</Popover>
				) }
			</div>
		</div>
	);
}
