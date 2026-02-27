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
