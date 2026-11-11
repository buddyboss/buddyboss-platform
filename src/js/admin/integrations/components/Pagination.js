/**
 * BuddyBoss Integrations marketplace — pagination footer.
 *
 * Item count + numbered page buttons with ellipsis, matching the Settings 2.0
 * list pagination (`bb-admin-pagination` — see settings ListPagination). Server-
 * side pagination, so total / totalPages come from the proxied `x-wp-total` and
 * `x-wp-totalpages` headers. getPageNumbers is inlined so this standalone bundle
 * stays independent of the settings bundle.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

import { Button } from '@wordpress/components';
import { __, _n, sprintf } from '@wordpress/i18n';

/**
 * Build the page-number list with ellipsis: all pages when <= 7, otherwise
 * first/last plus a sliding window of up to 5 around the current page.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {number} currentPage Active page.
 * @param {number} totalPages  Total pages.
 * @returns {Array} Page numbers and '...' markers.
 */
function getPageNumbers( currentPage, totalPages ) {
	const pages = [];
	const maxVisible = 5;

	if ( totalPages <= 7 ) {
		for ( let i = 1; i <= totalPages; i++ ) {
			pages.push( i );
		}
		return pages;
	}

	pages.push( 1 );
	if ( currentPage > maxVisible - 1 ) {
		pages.push( '...' );
	}

	let start = Math.max( 2, currentPage - 1 );
	let end = Math.min( totalPages - 1, currentPage + 1 );
	if ( currentPage <= 3 ) {
		end = Math.min( totalPages - 1, maxVisible );
	}
	if ( currentPage >= totalPages - 2 ) {
		start = Math.max( 2, totalPages - maxVisible + 1 );
	}
	for ( let j = start; j <= end; j++ ) {
		pages.push( j );
	}

	if ( currentPage < totalPages - ( maxVisible - 2 ) ) {
		pages.push( '...' );
	}
	pages.push( totalPages );

	return pages;
}

export function Pagination( { page, totalPages, total, onChange } ) {
	if ( ! total || total <= 0 ) {
		return null;
	}

	const p = 'bb-admin-pagination';

	return (
		<div className="bb-integrations__footer">
			<span className="bb-integrations__item-count">
				{ sprintf(
					/* translators: %s: total number of integrations. */
					_n( '%s item', '%s items', total, 'buddyboss' ),
					total
				) }
			</span>

			{ totalPages > 1 && (
				<div className={ p + '__pagination' }>
					<Button
						variant="secondary"
						disabled={ page <= 1 }
						onClick={ () => onChange( Math.max( 1, page - 1 ) ) }
						className={ p + '__pagination-btn ' + p + '__pagination-btn--previous' }
						label={ __( 'Previous page', 'buddyboss' ) }
						aria-label={ __( 'Previous page', 'buddyboss' ) }
					>
						&lsaquo;
					</Button>

					{ getPageNumbers( page, totalPages ).map( ( pageNum, index ) =>
						'...' === pageNum ? (
							<span key={ 'ellipsis-' + index } className={ p + '__pagination-ellipsis' }>
								&hellip;
							</span>
						) : (
							<Button
								key={ pageNum }
								variant={ page === pageNum ? 'primary' : 'secondary' }
								onClick={ () => onChange( pageNum ) }
								aria-current={ page === pageNum ? 'page' : undefined }
								className={ p + '__pagination-btn' + ( page === pageNum ? ' ' + p + '__pagination-btn--current' : '' ) }
							>
								{ pageNum }
							</Button>
						)
					) }

					<Button
						variant="secondary"
						disabled={ page >= totalPages }
						onClick={ () => onChange( Math.min( totalPages, page + 1 ) ) }
						className={ p + '__pagination-btn ' + p + '__pagination-btn--next' }
						label={ __( 'Next page', 'buddyboss' ) }
						aria-label={ __( 'Next page', 'buddyboss' ) }
					>
						&rsaquo;
					</Button>
				</div>
			) }
		</div>
	);
}
