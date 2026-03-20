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

	fields.forEach( function ( field ) {
		if ( 'half' === field.layout || 'third' === field.layout ) {
			// Flush when layout type changes (e.g. third -> half).
			if ( bufferLayout && bufferLayout !== field.layout ) {
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
