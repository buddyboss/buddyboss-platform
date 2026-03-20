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
 * Build registered field payload for AJAX save.
 *
 * Iterates registered fields, pulls TinyMCE content for richtext fields,
 * and returns an object with `registered_field_{id}` keys. Used by all
 * create/edit modals to avoid duplicating this pattern.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {Array}  fields Array of field definitions from the registry.
 * @param {Object} values Current field values keyed by field ID.
 * @param {number} itemId Item ID (0 for create, post ID for edit).
 * @returns {Object} Payload with registered_field_* keys.
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

		payload[ 'registered_field_' + field.id ] = null !== val && undefined !== val ? val : '';
	} );

	return payload;
}
