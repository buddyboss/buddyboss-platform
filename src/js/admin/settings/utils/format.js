/**
 * BuddyBoss Admin Settings 2.0 - Formatting Utilities
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

/**
 * Format a number with comma-separated thousands.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {number} num Number to format.
 * @returns {string} Formatted number string.
 */
export function formatNumber( num ) {
	return num.toString().replace( /\B(?=(\d{3})+(?!\d))/g, ',' );
}

/**
 * Sanitize a string into a URL-friendly slug.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {string} str Input string.
 * @returns {string} Slug.
 */
export function toSlug( str ) {
	return str
		.toLowerCase()
		.replace( /[^\w\u0080-\uFFFF\s-]/g, '' )
		.replace( /[\s]+/g, '-' )
		.replace( /-+/g, '-' )
		.replace( /^-|-$/g, '' );
}

/**
 * Group consecutive fields with layout='half' or 'third' into row wrappers.
 * Flushes the buffer when the layout type changes (e.g. third -> half)
 * so each row contains only fields of the same layout width.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {Array} fields Array of field objects with optional layout property.
 * @returns {Array} Array of { type: 'single', field } or { type: 'row', fields }.
 */
export function groupFieldsWithLayout( fields ) {
	var result = [];
	var buffer = [];
	var bufferLayout = null;

	var flush = function () {
		if ( buffer.length > 0 ) {
			result.push( { type: 'row', fields: buffer } );
			buffer = [];
			bufferLayout = null;
		}
	};

	// Maximum fields per row by layout type.
	var maxPerRow = { half: 2, third: 3 };

	fields.forEach( function ( field ) {
		if ( 'half' === field.layout || 'third' === field.layout ) {
			// Flush when layout type changes (e.g. third -> half).
			if ( bufferLayout && bufferLayout !== field.layout ) {
				flush();
			}

			// Flush when max per row reached (e.g. 2 for half, 3 for third).
			var max = maxPerRow[ field.layout ] || 2;
			if ( buffer.length >= max ) {
				flush();
			}

			buffer.push( field );
			bufferLayout = field.layout;
		} else {
			flush();
			result.push( { type: 'single', field: field } );
		}
	} );

	flush();

	return result;
}

/**
 * Check if a field's client-side conditional dependency is met.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {Object} field  Field definition with optional conditional property.
 * @param {Object} values Current form values keyed by field ID.
 * @returns {boolean} True if the field should be visible.
 */
export function isFieldConditionalMet( field, values ) {
	if ( ! field.conditional ) {
		return true;
	}

	var currentVal = values[ field.conditional.field ];
	var expectedVal = field.conditional.value;

	// Boolean comparison: handle '1'/'0'/true/false.
	if ( true === expectedVal || false === expectedVal ) {
		var isTruthy = !! currentVal && '0' !== currentVal && 0 !== currentVal;
		return isTruthy === expectedVal;
	}

	return String( currentVal ) === String( expectedVal );
}

/**
 * Filter fields by visibility and conditional dependencies.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {Array}  fields Array of field definitions.
 * @param {Object} values Current form values keyed by field ID.
 * @returns {Array} Filtered fields.
 */
export function getVisibleFields( fields, values ) {
	return fields.filter( function ( field ) {
		return field.visible && isFieldConditionalMet( field, values );
	} );
}

/**
 * Determine if a grouped item needs a bottom separator.
 *
 * Centralized separator logic used by all modals:
 * - Row groups: separator only when the NEXT item is also a row (not a conditional child).
 * - Single fields: separator after richtext, or before a non-conditional row group.
 * - Conditional child rows (e.g. Date/Time depending on Publish) get no separator from parent.
 * - Custom overrides via fieldId for modal-specific needs (e.g. reply_to, author_info).
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {Object}      item              Current grouped item ({ type, field } or { type, fields }).
 * @param {Object|null} nextItem          Next grouped item in the array, or null.
 * @param {Array}       separatorFieldIds Optional array of field IDs that always get separators.
 * @returns {boolean} True if the item should have a bottom separator.
 */
export function needsSeparator( item, nextItem, separatorFieldIds ) {
	var fieldIds = separatorFieldIds || [];
	var nextIsRow = nextItem && 'row' === nextItem.type;

	if ( 'row' === item.type ) {
		// Row gets separator only when the NEXT item is also a row.
		return nextIsRow;
	}

	// Single field.
	var fieldId = item.field ? item.field.id : '';
	var fieldType = item.field ? item.field.type : '';

	// Always add separator for richtext fields or explicitly listed field IDs.
	if ( 'richtext' === fieldType || -1 !== fieldIds.indexOf( fieldId ) ) {
		return true;
	}

	// Add separator before a row group, UNLESS the row's fields are conditional children
	// of the current field (they belong to the same visual group — e.g. Publish → Date/Time).
	if ( nextIsRow && nextItem.fields[ 0 ] && nextItem.fields[ 0 ].conditional && nextItem.fields[ 0 ].conditional.field === fieldId ) {
		return false;
	}

	return nextIsRow;
}

/**
 * Split a field list into ordered runs separated by `field_group` boundaries.
 *
 * Fields with an empty `field_group` belong to the implicit "core" run that
 * always renders flat (no heading, no border). Fields whose `field_group` is
 * set form a contiguous bordered section headed by their `field_group_label`
 * — typically a third-party metabox bridged via `bb_legacy_register_cpt_meta_bridge`.
 *
 * Caller renders each returned segment in order: ungrouped segments render
 * fields directly; grouped segments render a heading + bordered container
 * with the fields inside.
 *
 * Backward compatibility: when no field on the input list carries a
 * non-empty `field_group`, the function returns a single ungrouped segment
 * — visually identical to the pre-grouping behavior. So existing modals
 * (Activity, Groups, Emails) that have not yet adopted this helper keep
 * rendering exactly as before.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {Array} fields Filtered, visible field list (already passed through
 *                       `getVisibleFields()` / conditional checks).
 * @returns {Array<{group: string, label: string, fields: Array}>} Ordered segments.
 */
export function splitFieldsByMetaboxGroup( fields ) {
	var segments = [];
	var current = null;

	fields.forEach( function ( field ) {
		var groupId = field.field_group ? String( field.field_group ) : '';
		var groupLabel = field.field_group_label ? String( field.field_group_label ) : '';

		if ( ! current || current.group !== groupId ) {
			current = { group: groupId, label: groupLabel, fields: [] };
			segments.push( current );
		}

		// First field's label wins for a group — later fields in the same
		// group can omit `field_group_label` to inherit it.
		if ( '' === current.label && '' !== groupLabel ) {
			current.label = groupLabel;
		}

		current.fields.push( field );
	} );

	return segments;
}

/**
 * Build registered field payload for AJAX save.
 *
 * Iterates registered fields, pulls TinyMCE content for richtext fields,
 * and returns an object with BOTH plain keys (for AJAX handler direct reads)
 * and `registered_field_{id}` keys (for registry save_fields_data).
 *
 * This means new fields added to meta-fields.php are automatically included
 * in the payload — no manual key addition needed in React modals.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {Array}  fields Array of field definitions from the registry.
 * @param {Object} values Current field values keyed by field ID.
 * @param {number} itemId Item ID (0 for create, post ID for edit).
 * @returns {Object} Payload with both plain and registered_field_* keys.
 */
export function buildRegisteredFieldPayload( fields, values, itemId ) {
	var payload = {};

	fields.forEach( function ( field ) {
		if ( field.readonly ) {
			return;
		}

		var val = values[ field.id ];

		// For richtext fields, pull latest content from TinyMCE.
		if ( 'richtext' === field.type && window.tinymce ) {
			var editor = window.tinymce.get( 'bb-admin-edit-' + field.id + '-' + itemId );
			if ( editor ) {
				val = editor.getContent();
			}
		}

		var safeVal = null !== val && undefined !== val ? val : '';

		// Plain key — read by AJAX handlers directly (e.g. $_POST['publish_mode']).
		payload[ field.id ] = safeVal;

		// Prefixed key — read by registry save_fields_data() for extension fields.
		payload[ 'registered_field_' + field.id ] = safeVal;
	} );

	return payload;
}
