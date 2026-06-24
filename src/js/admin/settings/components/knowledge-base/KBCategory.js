/**
 * BuddyBoss Admin Settings 2.0 - Knowledge Base Category View
 *
 * Composes the category detail view: breadcrumb, sidebar of
 * sub-categories + grouped articles, and the article reading pane.
 *
 * Replaces the legacy PHP-proxy fetch with a two-step direct cross-origin
 * flow against `https://buddyboss.com/wp-json/wp/v2/`:
 *
 *   1. `getTaxonomy()` — full ht-kb-category list (memoized at the module
 *      level so KBLanding and KBCategory share one fetch).
 *   2. `kbApi.getCategoryArticles( leafIds )` — articles for every host in
 *      the active category's sub-tree, in a single query via the
 *      `ht-kb-category[]=` array filter. "Hosts" are leaves AND any
 *      intermediate term whose own count > 0.
 *
 * Articles are then grouped via the pure `buildCategoryTree` helper, which
 * handles the dedup pass (each article assigned to exactly one host node)
 * and produces the `{id, slug, name, count, children, articles}` shape
 * KBSidebar consumes — so KBSidebar doesn't change.
 *
 * Auto-select via `findFirstArticle` DFS — UNCHANGED.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

import { useEffect, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { useKb } from '../../context/KbContext';
import { kbApi } from '../../utils/kbApi';
import { getTaxonomy } from './taxonomyCache';
import { buildCategoryTree, getParentAndHosts } from './categoryTreeBuilder';
import KBBreadcrumb from './KBBreadcrumb';
import KBSidebar from './KBSidebar';

// Module-level memo of the built category-detail tree, keyed by the top-level
// category slug. Without this, every modal close+reopen would re-issue the
// `wp/v2/ht-kb?ht-kb-category[]=<leaf>` (paginated, often 1-2 round trips per
// category) and rebuild the tree on the client. With this 10-minute TTL we
// match `taxonomyCache` semantics — fresh hits skip the network entirely;
// stale entries fall through to a refetch.
const detailCache = new Map();
const DETAIL_TTL_MS = 10 * 60 * 1000;
import KBArticle from './KBArticle';

/**
 * DFS walk the recursive tree to find the first article anywhere in the
 * hierarchy. For each node we recurse into children FIRST (matching the
 * sidebar's render order — children appear above own articles), then fall
 * back to the node's own articles.
 *
 * Returns `{ path, article }` where `path` is the list of ancestor slugs
 * (top-level first) leading to the article's parent node, so callers can
 * dispatch `expandSubcategory` for each ancestor to expand the full chain
 * idempotently — never collapsing an already-open ancestor.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {Array} nodes Subcategory tree nodes.
 * @return {?{path: string[], article: Object}} Match or null when the tree is empty.
 */
function findFirstArticle( nodes ) {
	if ( ! Array.isArray( nodes ) ) {
		return null;
	}
	for ( const node of nodes ) {
		// Recurse into children before considering own articles, mirroring the
		// sidebar's render order (children render above own articles).
		if ( Array.isArray( node.children ) && node.children.length > 0 ) {
			const childMatch = findFirstArticle( node.children );
			if ( childMatch ) {
				return {
					path: [ node.slug, ...childMatch.path ],
					article: childMatch.article,
				};
			}
		}
		if ( Array.isArray( node.articles ) && node.articles.length > 0 ) {
			return {
				path: [ node.slug ],
				article: node.articles[ 0 ],
			};
		}
	}
	return null;
}

/**
 * Knowledge Base category view.
 *
 * Owns the taxonomy + articles fetch sequence and renders breadcrumb +
 * sidebar + article columns. Auto-select dispatches fire AFTER tree state
 * is set, so the sidebar opens to the first non-empty sub-category and the
 * article pane immediately displays the first article in that group.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @return {React.Element} Category view element.
 */
export default function KBCategory() {
	const { state, dispatch } = useKb();
	const [ status, setStatus ] = useState( 'loading' );
	const [ detail, setDetail ] = useState( null );

	useEffect( () => {
		if ( ! state.activeCategorySlug ) {
			return undefined;
		}
		const slug = state.activeCategorySlug;
		const controller = new AbortController();

		// Cache hit — skip the network entirely on modal reopen / fast nav.
		const cached = detailCache.get( slug );
		if ( cached && Date.now() - cached.fetchedAt < DETAIL_TTL_MS ) {
			setDetail( cached.payload );
			setStatus( 'ready' );
			if ( ! state.activeArticleSlug ) {
				const match = findFirstArticle( cached.payload.subcategories );
				if ( match ) {
					match.path.forEach( ( s ) => {
						dispatch( { type: 'expandSubcategory', slug: s } );
					} );
					dispatch( { type: 'selectArticle', slug: match.article.slug } );
				}
			}
			return () => controller.abort();
		}

		setStatus( 'loading' );

		( async () => {
			try {
				const taxonomy = await getTaxonomy( controller.signal );

				const parentAndHosts = getParentAndHosts( taxonomy, slug );
				if ( ! parentAndHosts ) {
					setStatus( 'error' );
					return;
				}

				const { articles, total, truncated } = await kbApi.getCategoryArticles(
					parentAndHosts.leafIds,
					{ signal: controller.signal }
				);

				const payload = buildCategoryTree(
					taxonomy,
					slug,
					articles,
					{ totalServerArticles: total, truncated }
				);

				if ( ! payload ) {
					setStatus( 'error' );
					return;
				}

				detailCache.set( slug, { payload, fetchedAt: Date.now() } );

				setDetail( payload );
				setStatus( 'ready' );

				// Auto-select the first article — but ONLY when there's no
				// prior selection in this session. Otherwise, on every modal
				// reopen the auto-select would override the user's
				// previously-chosen article (e.g. they were reading article #5
				// when they closed the modal; reopening would snap them back
				// to article #1, defeating the per-session "where I left off"
				// behaviour and triggering an unnecessary cache-miss-looking
				// fetch for the first article instead of serving the cached
				// body of the article they actually want to see).
				if ( ! state.activeArticleSlug ) {
					const match = findFirstArticle( payload.subcategories );
					if ( match ) {
						// Expand every ancestor on the path so the user lands
						// directly inside the deepest accordion with the article
						// selected. Use `expandSubcategory` (idempotent add)
						// rather than `toggleSubcategory` so a re-fetch never
						// collapses an already-open ancestor.
						match.path.forEach( ( slug ) => {
							dispatch( { type: 'expandSubcategory', slug } );
						} );
						dispatch( { type: 'selectArticle', slug: match.article.slug } );
					}
				}
			} catch ( err ) {
				if ( err && err.name === 'AbortError' ) {
					return;
				}
				setStatus( 'error' );
			}
		} )();

		return () => controller.abort();
		// `dispatch` is stable; `activeCategorySlug` is the only meaningful dep.
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [ state.activeCategorySlug ] );

	if ( 'loading' === status ) {
		return (
			<div className="bb-kb-category bb-kb-category--loading" aria-busy="true">
				<div className="bb-kb-category__sidebar-skeleton" />
				<div className="bb-kb-category__article-skeleton" />
			</div>
		);
	}

	if ( 'error' === status || ! detail ) {
		return (
			<div className="bb-kb-category bb-kb-category--error" role="alert">
				{ __( 'Couldn’t load this category.', 'buddyboss' ) }
			</div>
		);
	}

	return (
		<div className="bb-kb-category">
			<KBBreadcrumb categoryName={ detail.category.name } />
			<div className="bb-kb-category__columns">
				<KBSidebar
					subcategories={ detail.subcategories }
					truncatedRemaining={ detail.truncated_remaining }
					categorySlug={ detail.category.slug }
					docCategoriesBaseUrl={ detail.docCategoriesBaseUrl }
				/>
				<main className="bb-kb-category__main">
					<KBArticle slug={ state.activeArticleSlug } />
				</main>
			</div>
		</div>
	);
}
