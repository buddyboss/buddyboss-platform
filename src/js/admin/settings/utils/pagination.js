/**
 * BuddyBoss Admin Settings 2.0 - Pagination Utilities
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

/**
 * Build pagination page numbers with ellipsis.
 *
 * Returns an array of page numbers and '...' strings for rendering
 * a pagination control. Shows all pages when totalPages <= 7, otherwise
 * shows first/last with a sliding window of up to 5 visible pages.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {number} currentPage The currently active page.
 * @param {number} totalPages  The total number of pages.
 * @returns {Array} Array of page numbers and '...' strings.
 */
export function getPageNumbers( currentPage, totalPages ) {
	var pages = [];
	var maxVisible = 5;

	if ( totalPages <= 7 ) {
		for ( var i = 1; i <= totalPages; i++ ) {
			pages.push( i );
		}
	} else {
		pages.push( 1 );

		if ( currentPage > maxVisible - 1 ) {
			pages.push( '...' );
		}

		var start = Math.max( 2, currentPage - 1 );
		var end = Math.min( totalPages - 1, currentPage + 1 );

		if ( currentPage <= 3 ) {
			end = Math.min( totalPages - 1, maxVisible );
		}
		if ( currentPage >= totalPages - 2 ) {
			start = Math.max( 2, totalPages - maxVisible + 1 );
		}

		for ( var j = start; j <= end; j++ ) {
			pages.push( j );
		}

		if ( currentPage < totalPages - ( maxVisible - 2 ) ) {
			pages.push( '...' );
		}

		pages.push( totalPages );
	}

	return pages;
}
