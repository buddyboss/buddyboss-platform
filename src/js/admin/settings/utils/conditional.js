/**
 * BuddyBoss Admin Settings 2.0 — Conditional Evaluation Utility
 *
 * Shared helper for evaluating `conditional` arg shapes against a flat values
 * object. Canonical evaluator — used by side-panel + section visibility
 * filters and by `SettingsForm.isFieldVisible` / `isFieldConditionallyDisabled`.
 *
 * Conditional shape:
 *   - Single:   { field: 'name', value: expected, operator?: '==' | '!=', source?: 'bbAdminData' }
 *   - Multiple: { conditions: [ { field, value, operator?, source? } ], operator: 'AND' | 'OR' }
 *
 * `source` defaults to the per-feature settings map. Pass `'bbAdminData'` to
 * read from `window.bbAdminData` instead — useful for cross-feature flags
 * that are mirrored from PHP (`wp_localize_script`) and refreshed from save
 * responses (`response.data.bbAdminDataUpdates`).
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

/**
 * Resolve the values map a single condition should read from.
 *
 * Defaults to the per-feature settings map (the `values` argument). When a
 * condition opts into a different source via `cond.source`, look it up on the
 * appropriate global instead. Today the only non-default source is
 * `'bbAdminData'`, used to evaluate cross-feature flags (e.g. Profile Type
 * Redirects hides based on `window.bbAdminData.isProfileTypesEnabled` even
 * though the controlling toggle lives in the Members feature). Falls back to
 * an empty object if the requested global is missing — the condition then
 * reads `undefined` and behaves as "not met".
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {Object} cond   Single condition (may carry an optional `source`).
 * @param {Object} values Default per-feature values map.
 * @returns {Object} The map to read the field value from.
 */
function resolveConditionSource( cond, values ) {
	if ( cond && 'bbAdminData' === cond.source ) {
		return ( typeof window !== 'undefined' && window.bbAdminData ) ? window.bbAdminData : {};
	}
	return values || {};
}

/**
 * Evaluate a single condition against a values map.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {Object} cond   Single condition: { field, value, operator?, source? }.
 * @param {Object} values Flat values map (field name → current value).
 * @returns {boolean} Whether the condition is met.
 */
function evaluateSingleCondition( cond, values ) {
	if ( ! cond || ! cond.field ) {
		return true;
	}

	var sourceMap = resolveConditionSource( cond, values );

	// Support dot-notation field paths so conditionals can read nested
	// values (e.g. a toggle_list item — `bb_recaptcha_enabled_for.bb_login`).
	// Plain field names without dots resolve to a single key as before.
	var fieldPath = String( cond.field ).split( '.' );
	var condValue = sourceMap;
	for ( var pi = 0; pi < fieldPath.length; pi++ ) {
		if ( condValue && 'object' === typeof condValue ) {
			condValue = condValue[ fieldPath[ pi ] ];
		} else {
			condValue = undefined;
			break;
		}
	}

	var expected = cond.value;
	var operator    = cond.operator || '==';
	var matched;

	// Boolean expected: compare via truthiness (DB can hold 0/1/"0"/"1"/false/true).
	if ( true === expected || false === expected ) {
		var isTruthy = !! condValue && '0' !== condValue && 0 !== condValue;
		matched = isTruthy === expected;
	} else if ( Array.isArray( expected ) ) {
		matched = expected.indexOf( condValue ) !== -1;
	} else {
		// Loose equality so '1' === 1 etc. (matches PHP-side option storage variability).
		// eslint-disable-next-line eqeqeq
		matched = condValue == expected;
	}

	if ( '!=' === operator ) {
		return ! matched;
	}
	return matched;
}

/**
 * Evaluate a `conditional` arg (single or multi) against a values map.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {Object|null|undefined} conditional Conditional arg, may be falsy.
 * @param {Object}                values      Flat values map.
 * @returns {boolean} True when no conditional is set OR when it evaluates to true.
 */
export function evaluateConditional( conditional, values ) {
	if ( ! conditional ) {
		return true;
	}

	// Multiple conditions with AND/OR operator.
	if ( Array.isArray( conditional.conditions ) ) {
		var op = ( conditional.operator || 'AND' ).toUpperCase();
		var conditions = conditional.conditions;

		if ( 'OR' === op ) {
			return conditions.some( function ( c ) {
				return evaluateSingleCondition( c, values );
			} );
		}
		return conditions.every( function ( c ) {
			return evaluateSingleCondition( c, values );
		} );
	}

	return evaluateSingleCondition( conditional, values );
}
