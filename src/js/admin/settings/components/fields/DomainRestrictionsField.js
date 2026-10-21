/**
 * BuddyBoss Admin Settings 2.0 - Domain Restrictions Field
 *
 * Renders a repeater for domain restrictions with per-row:
 * - Drag handle (future)
 * - Domain name text input
 * - "." static separator
 * - Extension text input
 * - Condition select (Select Condition / Always Allow / Never Allow / Only Allow)
 * - Delete (X) button
 * - "+ Add Domain" button at bottom
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

import { useState, useEffect, useRef, useCallback } from '@wordpress/element';
import { Button, SelectControl, TextControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Condition options for domain restrictions.
 *
 * "always_allow" and "only_allow" are mutually exclusive —
 * when one is selected anywhere, the other is disabled.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @type {Array}
 */
var conditionOptions = [
	{ value: '', label: __( 'Select Condition', 'buddyboss' ) },
	{ value: 'always_allow', label: __( 'Always Allow', 'buddyboss' ) },
	{ value: 'never_allow', label: __( 'Never Allow', 'buddyboss' ) },
	{ value: 'only_allow', label: __( 'Only Allow', 'buddyboss' ) },
];

/**
 * Domain Restrictions Field Component
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {Object}   props          Component props.
 * @param {Object}   props.field    Field definition.
 * @param {Array}    props.value    Current value array of {domain, tld, condition} objects.
 * @param {Function} props.onChange Change handler (fieldName, newValue).
 * @param {boolean}  props.disabled Whether the field is disabled.
 *
 * @returns {JSX.Element} Domain restrictions repeater.
 */
export function DomainRestrictionsField( { field, value, onChange, disabled } ) {
	var keyCounterRef = useRef( 0 );
	var debounceTimerRef = useRef( null );
	var lastSentValueRef = useRef( null );

	function assignKey( row ) {
		if ( row._key ) {
			return row;
		}
		return Object.assign( {}, row, { _key: 'dr-' + ( ++keyCounterRef.current ) } );
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
	var dragIndexRef = useRef( null );
	var dragOverIndexRef = useRef( null );
	var [ dragOverIdx, setDragOverIdx ] = useState( null );

	// Sync rows when value prop changes (e.g., after settings reload).
	// Skip sync when the value matches what we last sent — that's just our own save echoing back.
	useEffect( function() {
		if ( ! Array.isArray( value ) ) {
			return;
		}
		// If the incoming value is our own save response, ignore it — local state is already correct.
		if ( lastSentValueRef.current && lastSentValueRef.current === value ) {
			return;
		}
		// Only sync from server when it's genuinely new data (e.g., panel switch, initial load).
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
	 * Determine which conditions are mutually exclusive.
	 * If any row uses "always_allow", disable "only_allow" everywhere, and vice versa.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	var hasAlwaysAllow = rows.some( function( row ) {
		return 'always_allow' === row.condition;
	} );
	var hasOnlyAllow = rows.some( function( row ) {
		return 'only_allow' === row.condition;
	} );

	/**
	 * Get condition options with mutual exclusivity applied.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {string} currentValue The current row's condition value.
	 * @return {Array} Options with disabled flag.
	 */
	function getConditionOptions( currentValue ) {
		return conditionOptions.map( function( opt ) {
			var isDisabled = false;

			if ( 'always_allow' === opt.value && hasOnlyAllow && 'always_allow' !== currentValue ) {
				isDisabled = true;
			}
			if ( 'only_allow' === opt.value && hasAlwaysAllow && 'only_allow' !== currentValue ) {
				isDisabled = true;
			}

			return {
				value: opt.value,
				label: opt.label,
				disabled: isDisabled,
			};
		} );
	}

	/**
	 * Update a row and propagate change.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {number} index Row index.
	 * @param {string} key   Field key (domain, tld, condition).
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
		var updated = rows.concat( [ assignKey( { domain: '', tld: '', condition: '' } ) ] );
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

	/**
	 * Handle drag start — store dragged row index.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {number} index Row index.
	 */
	var handleDragStart = useCallback( function( index ) {
		dragIndexRef.current = index;
	}, [] );

	/**
	 * Handle drag over — highlight the row being hovered.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {Event}  e     Drag event.
	 * @param {number} index Row index.
	 */
	var handleDragOver = useCallback( function( e, index ) {
		e.preventDefault();
		dragOverIndexRef.current = index;
		setDragOverIdx( index );
	}, [] );

	/**
	 * Handle drop — reorder rows and save.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	var handleDrop = useCallback( function() {
		var fromIndex = dragIndexRef.current;
		var toIndex = dragOverIndexRef.current;

		dragIndexRef.current = null;
		dragOverIndexRef.current = null;
		setDragOverIdx( null );

		if ( null === fromIndex || null === toIndex || fromIndex === toIndex ) {
			return;
		}

		var updated = rows.slice();
		var moved = updated.splice( fromIndex, 1 )[ 0 ];
		updated.splice( toIndex, 0, moved );

		setRows( updated );
		onChange( field.name, updated );
	}, [ rows, field.name, onChange ] );

	/**
	 * Handle drag end — reset visual state.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	var handleDragEnd = useCallback( function() {
		dragIndexRef.current = null;
		dragOverIndexRef.current = null;
		setDragOverIdx( null );
	}, [] );

	return (
		<div className="bb-domain-restrictions">
			<div className="bb-domain-restrictions__rows">
				{ rows.map( function( row, index ) {
					return (
						<div
							key={ row._key }
							className={ 'bb-domain-restrictions__row' + ( dragOverIdx === index && dragIndexRef.current !== index ? ' bb-domain-restrictions__row--drag-over' : '' ) }
							draggable={ ! disabled }
							onDragStart={ function() {
								handleDragStart( index );
							} }
							onDragOver={ function( e ) {
								handleDragOver( e, index );
							} }
							onDrop={ handleDrop }
							onDragEnd={ handleDragEnd }
						>
							<span className="bb-domain-restrictions__drag-handle">
								<i className="bb-icons-rl bb-icons-rl-list" />
							</span>
							<div className="bb-domain-restrictions__domain">
								<TextControl
									value={ row.domain || '' }
									onChange={ function( val ) {
										updateRow( index, 'domain', val );
									} }
									placeholder={ __( 'Domain name', 'buddyboss' ) }
									disabled={ disabled }
									__nextHasNoMarginBottom
								/>
							</div>
							<span className="bb-domain-restrictions__dot"></span>
							<div className="bb-domain-restrictions__tld">
								<TextControl
									value={ row.tld || '' }
									onChange={ function( val ) {
										updateRow( index, 'tld', val );
									} }
									placeholder={ __( 'Extension', 'buddyboss' ) }
									disabled={ disabled }
									__nextHasNoMarginBottom
								/>
							</div>
							<div className="bb-domain-restrictions__condition">
								<SelectControl
									value={ row.condition || '' }
									options={ getConditionOptions( row.condition ) }
									onChange={ function( val ) {
										updateRow( index, 'condition', val );
									} }
									disabled={ disabled }
									__nextHasNoMarginBottom
								/>
							</div>
							<button
								type="button"
								className="bb-domain-restrictions__remove"
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
				className="bb-domain-restrictions__add"
				onClick={ addRow }
				disabled={ disabled }
			>
				<i className="bb-icons-rl-plus"></i>
				{ __( 'Add Domain', 'buddyboss' ) }
			</Button>
		</div>
	);
}
