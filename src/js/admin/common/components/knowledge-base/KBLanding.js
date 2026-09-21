/**
 * BuddyBoss Admin Settings 2.0 - Knowledge Base Landing Grid
 *
 * Six-card grid rendered for `state.view === 'landing'`. Owns its own data
 * fetch via the module-level taxonomy memo (`getTaxonomy`), with three
 * render states:
 *
 *   - `loading` — skeleton placeholders (six cards) while the request is
 *     in flight.
 *   - `error`   — alert region with a retry button when the request fails
 *     for any reason other than an `AbortError` (those are silent — they
 *     fire when the modal closes mid-fetch).
 *   - `ready`   — the actual grid. The flat taxonomy is folded into a
 *     parent→children map; top-level (parent=0) entries become cards.
 *     Each card's article count is the recursive sum of all descendants.
 *     Curated icon/title/description from `curatedOverrides` is applied
 *     by slug; unknown top-levels render with the raw API name and a
 *     generic book icon.
 *
 * Card click dispatches `{ type: 'selectCategory', slug }` against the
 * shared `KbContext` reducer, which flips the modal into category view.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

import { useEffect, useMemo, useState } from '@wordpress/element';
import { __, sprintf, _n } from '@wordpress/i18n';
import { useKb } from '../../context/KbContext';
import { decodeEntities } from '../../utils/kbApi';
import { getTaxonomy, clearTaxonomy } from './taxonomyCache';
import { getCuratedOverrides } from './curatedOverrides';
import { resolveRootParentId } from './landingScope';

/**
 * Walk the parent→children map and sum descendant counts for the given
 * top-level term (own count + every descendant's own count, taxonomy-style).
 *
 * @param {number} parentId  Term ID to aggregate from.
 * @param {Map<number, Array>} childrenByParent Map keyed by parent id.
 * @param {Map<number, Object>} byId             Map of term-id → term.
 * @return {number} Aggregated article count.
 */
function aggregateCount( parentId, childrenByParent, byId ) {
	const own = byId.get( parentId );
	let total = own && typeof own.count === 'number' ? own.count : 0;
	const kids = childrenByParent.get( parentId ) || [];
	for ( const child of kids ) {
		total += aggregateCount( child.id, childrenByParent, byId );
	}
	return total;
}

/**
 * Knowledge Base landing grid.
 *
 * Self-contained — receives no props. Reads `dispatch` from `useKb()` so
 * card clicks can flip view state, and fetches the taxonomy on mount with
 * an AbortController so unmount mid-fetch doesn't leak the request or
 * trip the error branch.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @return {React.Element} Landing grid element.
 */
export default function KBLanding() {
	const { dispatch, state }       = useKb();
	const [ status, setStatus ]     = useState( 'loading' );
	const [ terms, setTerms ]       = useState( [] );
	const [ retryCount, setRetry ]  = useState( 0 );

	useEffect( () => {
		const controller = new AbortController();
		setStatus( 'loading' );
		getTaxonomy( controller.signal )
			.then( ( all ) => {
				if ( Array.isArray( all ) ) {
					setTerms( all );
					setStatus( 'ready' );
				} else {
					setStatus( 'error' );
				}
			} )
			.catch( ( err ) => {
				if ( err && err.name === 'AbortError' ) {
					return;
				}
				setStatus( 'error' );
			} );
		return () => controller.abort();
	}, [ retryCount ] );

	const cards = useMemo( () => {
		if ( ! Array.isArray( terms ) || terms.length === 0 ) {
			return [];
		}

		// Build lookups.
		const byId             = new Map();
		const childrenByParent = new Map();
		for ( const t of terms ) {
			byId.set( t.id, t );
			const list = childrenByParent.get( t.parent ) || [];
			list.push( t );
			childrenByParent.set( t.parent, list );
		}

		const overrides = getCuratedOverrides();
		const rootParentId = resolveRootParentId( terms, state.rootCategory );
		const topLevels    = childrenByParent.get( rootParentId ) || [];

		let built = topLevels.map( ( t ) => {
			const aggregated = aggregateCount( t.id, childrenByParent, byId );
			const curated    = overrides[ t.slug ] || null;
			return {
				id:          t.id,
				slug:        t.slug,
				name:        decodeEntities( curated && curated.title ? curated.title : ( t.name || '' ) ),
				description: decodeEntities( curated && curated.description ? curated.description : ( t.description || '' ) ),
				icon:        curated && curated.icon ? curated.icon : 'bb-icons-rl-book',
				order:       curated ? curated.order : 999,
				count:       aggregated,
			};
		} );

		// Sort: curated cards first by their `order`, then anything uncurated by name.
		built.sort( ( a, b ) => {
			if ( a.order !== b.order ) {
				return a.order - b.order;
			}
			return a.name.localeCompare( b.name );
		} );

		built = built.filter( item => item.count !== 0 );

		return built;
	}, [ terms, state.rootCategory ] );

	if ( status === 'loading' ) {
		return (
			<div className="bb-kb-landing">
				<div className="bb-kb-landing__skeleton" aria-busy="true" aria-live="polite">
					{ [ 0, 1, 2, 3, 4, 5 ].map( ( i ) => (
						<div key={ i } className="bb-kb-card bb-kb-card--skeleton" />
					) ) }
				</div>
			</div>
		);
	}

	if ( status === 'error' ) {
		return (
			<div className="bb-kb-landing">
				<div className="bb-kb-landing__error" role="alert">
					{ __( 'Couldn’t load documentation.', 'buddyboss' ) }
					<button
						type="button"
						className="components-button is-primary"
						onClick={ () => {
							// Clear the module-level memo BEFORE bumping the
							// retry counter — otherwise a previously-cached
							// empty/transient-failure response would be
							// re-served as success on the next fetch.
							clearTaxonomy();
							setRetry( ( c ) => c + 1 );
						} }
					>
						{ __( 'Retry', 'buddyboss' ) }
					</button>
				</div>
			</div>
		);
	}

	return (
		<div className="bb-kb-landing">
			<h2 className="bb-kb-landing__title">
				{ __( 'BuddyBoss Knowledge Base', 'buddyboss' ) }
			</h2>
			<ul className="bb-kb-landing__grid" role="list">
				{ cards.map( ( cat ) => (
					<li key={ cat.slug } className="bb-kb-landing__grid-item">
						<button
							type="button"
							className="bb-kb-card"
							onClick={ () => dispatch( { type: 'selectCategory', slug: cat.slug } ) }
						>
							<i className={ `bb-kb-card__icon ${ cat.icon }` } aria-hidden="true" />
							<h3 className="bb-kb-card__title">{ cat.name }</h3>
							<p className="bb-kb-card__description">{ cat.description }</p>
							<span className="bb-kb-card__count">
								{ sprintf(
									_n( '%d article', '%d articles', cat.count, 'buddyboss' ),
									cat.count
								) }
							</span>
						</button>
					</li>
				) ) }
			</ul>
		</div>
	);
}
