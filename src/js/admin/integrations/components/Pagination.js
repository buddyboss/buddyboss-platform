/**
 * BuddyBoss Integrations marketplace — pagination control.
 *
 * Prev / page-indicator / Next. Server-side pagination, so total pages come from
 * the proxied `x-wp-totalpages` header.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

import { __, sprintf } from '@wordpress/i18n';

export function Pagination( { page, totalPages, onChange } ) {
	const goPrev = () => onChange( Math.max( 1, page - 1 ) );
	const goNext = () => onChange( Math.min( totalPages, page + 1 ) );

	return (
		<div className="bb-integrations__pagination">
			<button
				type="button"
				className="button button-secondary"
				onClick={ goPrev }
				disabled={ page <= 1 }
			>
				{ __( 'Previous', 'buddyboss' ) }
			</button>
			<span className="bb-integrations__pagination-info">
				{ sprintf(
					/* translators: 1: current page, 2: total pages */
					__( 'Page %1$d of %2$d', 'buddyboss' ),
					page,
					totalPages
				) }
			</span>
			<button
				type="button"
				className="button button-secondary"
				onClick={ goNext }
				disabled={ page >= totalPages }
			>
				{ __( 'Next', 'buddyboss' ) }
			</button>
		</div>
	);
}
