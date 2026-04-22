/**
 * BuddyBoss Admin Settings 2.0 — Conditional Evaluation Utility
 *
 * Shared helper for evaluating `conditional` arg shapes against a flat values
 * object. Canonical evaluator — used by side-panel + section visibility
 * filters and by `SettingsForm.isFieldVisible` / `isFieldConditionallyDisabled`.
 *
 * Conditional shape:
 *   - Single:   { field: 'name', value: expected, operator?: '==' | '!=' }
 *   - Multiple: { conditions: [ { field, value, operator? } ], operator: 'AND' | 'OR' }
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

/**
 * Evaluate a single condition against a values map.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {Object} cond   Single condition: { field, value, operator? }.
 * @param {Object} values Flat values map (field name → current value).
 * @returns {boolean} Whether the condition is met.
 */
function evaluateSingleCondition( cond, values ) {
	if ( ! cond || ! cond.field ) {
		return true;
	}

	var condValue   = values ? values[ cond.field ] : undefined;
	var expected    = cond.value;
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
