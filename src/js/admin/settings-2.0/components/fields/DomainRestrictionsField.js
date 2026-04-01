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

import { useState, useEffect, useRef } from '@wordpress/element';
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

	function assignKey( row ) {
		if ( ! row._key ) {
			row._key = 'dr-' + ( ++keyCounterRef.current );
		}
		return row;
	}

	var initialRows = Array.isArray( value ) && value.length > 0
		? value.map( assignKey )
		: [];

	var [ rows, setRows ] = useState( initialRows );

	// Sync rows when value prop changes (e.g., after settings reload).
	useEffect( function() {
		if ( Array.isArray( value ) ) {
			setRows( value.map( assignKey ) );
		}
	}, [ value ] );

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
		onChange( field.name, updated );
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
		onChange( field.name, updated );
	}

	return (
		<div className="bb-domain-restrictions">
			<div className="bb-domain-restrictions__rows">
				{ rows.map( function( row, index ) {
					return (
						<div key={ row._key } className="bb-domain-restrictions__row">
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
							<span className="bb-domain-restrictions__dot">.</span>
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
