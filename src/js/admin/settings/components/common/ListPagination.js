/**
 * BuddyBoss Admin Settings 2.0 - List Pagination
 *
 * Shared pagination footer for all list screens. Renders item count,
 * previous/next buttons, and page number buttons with ellipsis.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

import { Button } from '@wordpress/components';
import { __, _n, sprintf } from '@wordpress/i18n';
import { getPageNumbers } from '../../utils/pagination';

/**
 * List Pagination Component
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {Object}   props             Component props.
 * @param {number}   props.currentPage Current page number.
 * @param {number}   props.totalPages  Total number of pages.
 * @param {number}   props.total       Total number of items.
 * @param {Function} props.onPageChange Page change handler.
 * @param {string}   props.className   CSS class prefix for the footer wrapper (e.g., 'bb-forums-list').
 * @returns {JSX.Element|null} Pagination footer or null if no items.
 */
export function ListPagination( { currentPage, totalPages, total, onPageChange, className } ) {
	var p = 'bb-admin-pagination';

	if ( ! total || total <= 0 ) {
		return null;
	}

	return (
		<div className={ className + '__footer' }>
			<span className={ className + '__item-count' }>
				{ sprintf(
					/* translators: %s: total number of items. */
					_n( '%s item', '%s items', total, 'buddyboss-platform' ),
					total
				) }
			</span>

			{ totalPages > 1 && (
				<div className={ p + '__pagination' }>
					<Button
						variant="secondary"
						disabled={ 1 === currentPage }
						onClick={ function () {
							onPageChange( Math.max( 1, currentPage - 1 ) );
						} }
						className={ p + '__pagination-btn ' + p + '__pagination-btn--previous' }
					>
						&lsaquo;
					</Button>

					{ getPageNumbers( currentPage, totalPages ).map( function ( page, index ) {
						if ( '...' === page ) {
							return (
								<span key={ 'ellipsis-' + index } className={ p + '__pagination-ellipsis' }>
									&hellip;
								</span>
							);
						}
						return (
							<Button
								key={ page }
								variant={ currentPage === page ? 'primary' : 'secondary' }
								onClick={ function () {
									onPageChange( page );
								} }
								className={ p + '__pagination-btn' + ( currentPage === page ? ' ' + p + '__pagination-btn--current' : '' ) }
							>
								{ page }
							</Button>
						);
					} ) }

					<Button
						variant="secondary"
						disabled={ currentPage >= totalPages }
						onClick={ function () {
							onPageChange( Math.min( totalPages, currentPage + 1 ) );
						} }
						className={ p + '__pagination-btn ' + p + '__pagination-btn--next' }
					>
						&rsaquo;
					</Button>
				</div>
			) }
		</div>
	);
}
