/**
 * BuddyBoss Admin Settings 2.0 - Email Restrictions Field
 *
 * Renders a repeater for email restrictions with per-row:
 * - Email text input
 * - Condition select (Select Condition / Always Allow / Never Allow)
 * - Delete (X) button
 * - "+ Add Email" button at bottom
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

import { useState, useEffect, useRef } from '@wordpress/element';
import { Button, SelectControl, TextControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Condition options for email restrictions.
 * Note: No "Only Allow" option (unlike domain restrictions).
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @type {Array}
 */
var conditionOptions = [
	{ value: '', label: __( 'Select Condition', 'buddyboss' ) },
	{ value: 'always_allow', label: __( 'Always Allow', 'buddyboss' ) },
	{ value: 'never_allow', label: __( 'Never Allow', 'buddyboss' ) },
];

/**
 * Email Restrictions Field Component
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {Object}   props          Component props.
 * @param {Object}   props.field    Field definition.
 * @param {Array}    props.value    Current value array of {address, condition} objects.
 * @param {Function} props.onChange Change handler (fieldName, newValue).
 * @param {boolean}  props.disabled Whether the field is disabled.
 *
 * @returns {JSX.Element} Email restrictions repeater.
 */
export function EmailRestrictionsField( { field, value, onChange, disabled } ) {
	var keyCounterRef = useRef( 0 );
	var debounceTimerRef = useRef( null );
	var lastSentValueRef = useRef( null );

	function assignKey( row ) {
		if ( row._key ) {
			return row;
		}
		return Object.assign( {}, row, { _key: 'er-' + ( ++keyCounterRef.current ) } );
	}

	/**
	 * Debounced onChange — batches rapid edits into one save call.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {string} fieldName Field name.
	 * @param {Array}  newRows   Updated rows.
	 */
	function debouncedOnChange( fieldName, newRows ) {
		if ( debounceTimerRef.current ) {
			clearTimeout( debounceTimerRef.current );
		}
		debounceTimerRef.current = setTimeout( function () {
			lastSentValueRef.current = newRows;
			onChange( fieldName, newRows );
		}, 800 );
	}

	var initialRows = Array.isArray( value ) && value.length > 0
		? value.map( assignKey )
		: [];

	var [ rows, setRows ] = useState( initialRows );

	// Sync rows when value prop changes (e.g., after settings reload).
	// Skip sync when the value matches what we last sent — that's just our own save echoing back.
	useEffect( function() {
		if ( ! Array.isArray( value ) ) {
			return;
		}
		if ( lastSentValueRef.current && lastSentValueRef.current === value ) {
			return;
		}
		if ( null === lastSentValueRef.current ) {
			setRows( value.map( assignKey ) );
		}
	}, [ value ] );

	// Cleanup debounce timer on unmount.
	useEffect( function () {
		return function () {
			if ( debounceTimerRef.current ) {
				clearTimeout( debounceTimerRef.current );
			}
		};
	}, [] );

	/**
	 * Update a row and propagate change.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {number} index Row index.
	 * @param {string} key   Field key (address, condition).
	 * @param {string} val   New value.
	 */
	function updateRow( index, key, val ) {
		var updated = rows.map( function( row, i ) {
			if ( i !== index ) {
				return row;
			}
			var newRow = Object.assign( {}, row );
			newRow[ key ] = val;
			return newRow;
		} );
		setRows( updated );
		debouncedOnChange( field.name, updated );
	}

	/**
	 * Add a new empty row.
	 * Does NOT trigger onChange (auto-save) — the empty row is local-only
	 * until the user fills in data via updateRow().
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	function addRow() {
		var updated = rows.concat( [ assignKey( { address: '', condition: '' } ) ] );
		setRows( updated );
	}

	/**
	 * Remove a row by index.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {number} index Row index to remove.
	 */
	function removeRow( index ) {
		var updated = rows.filter( function( _, i ) {
			return i !== index;
		} );
		setRows( updated );
		debouncedOnChange( field.name, updated );
	}

	return (
		<div className="bb-email-restrictions">
			<div className="bb-email-restrictions__rows">
				{ rows.map( function( row, index ) {
					return (
						<div key={ row._key } className="bb-email-restrictions__row">
							<div className="bb-email-restrictions__address">
								<TextControl
									type="email"
									value={ row.address || '' }
									onChange={ function( val ) {
										updateRow( index, 'address', val );
									} }
									placeholder={ __( 'Email address', 'buddyboss' ) }
									disabled={ disabled }
									__nextHasNoMarginBottom
								/>
							</div>
							<div className="bb-email-restrictions__condition">
								<SelectControl
									value={ row.condition || '' }
									options={ conditionOptions }
									onChange={ function( val ) {
										updateRow( index, 'condition', val );
									} }
									disabled={ disabled }
									__nextHasNoMarginBottom
								/>
							</div>
							<button
								type="button"
								className="bb-email-restrictions__remove"
								onClick={ function() {
									removeRow( index );
								} }
								disabled={ disabled }
								aria-label={ __( 'Remove Rule', 'buddyboss' ) }
							>
								<i className="bb-icons-rl bb-icons-rl-x" />
							</button>
						</div>
					);
				} ) }
			</div>
			<Button
				variant="secondary"
				className="bb-email-restrictions__add"
				onClick={ addRow }
				disabled={ disabled }
			>
				<i className="bb-icons-rl-plus"></i>
				{ __( 'Add Email', 'buddyboss' ) }
			</Button>
		</div>
	);
}
