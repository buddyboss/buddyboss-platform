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
