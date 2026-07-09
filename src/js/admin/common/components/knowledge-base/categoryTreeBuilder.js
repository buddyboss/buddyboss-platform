/**
 * BuddyBoss Knowledge Base — pure tree-building helper.
 *
 * Extracted from KBCategory.js so the dedup + intermediate-node logic is
 * unit-testable without standing up the modal. Given the flat taxonomy and
 * a flat article list, returns the React-renderable tree shape expected by
 * KBSidebar.
 *
 * Two correctness rules baked in:
 *
 *   1. Each article appears EXACTLY ONCE across the tree. Heroic KB tags
 *      the same article on multiple sibling leaves, so naive grouping
 *      duplicates the article (and triggers React duplicate-key warnings).
 *      We pick the FIRST matching host id from our subtree as the article's
 *      home; all others are dropped. Articles whose tags fall outside our
 *      subtree are discarded as orphans.
 *
 *   2. Articles attached directly to intermediate nodes (a category that
 *      also has sub-categories AND its own articles, e.g. "BuddyBoss App
 *      FAQs") are surfaced under that intermediate. The legacy "leaves
 *      only" approach silently dropped them. The host list now includes
 *      any term whose own count > 0, in addition to structural leaves.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

import { decodeEntities } from '../../utils/kbApi';
import { getDocCategoriesBaseUrl } from './urls';

/**
 * Hard depth cap for tree-walk recursion. Real Heroic KB taxonomies are
 * 3-4 levels deep; this is belt-and-braces in addition to the per-walk
 * visited-Set guard to short-circuit pathological inputs (a buggy upstream
 * that ships a parent-chain cycle would otherwise infinite-recurse and
 * hang the modal).
 *
 * @since BuddyBoss [BBVERSION]
 */
const MAX_TREE_DEPTH = 20;

/**
 * Build a recursive subcategory tree node and report which host-term IDs
 * sit underneath it (used by the article-fetch query string).
 *
 * Guarded by a per-walk visited-Set + depth cap so a malformed taxonomy
 * with circular parent chains terminates instead of hanging.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {Object}              term            Current term.
 * @param {Map<number, Array>}  kidsByParent    Parent-id → child terms.
 * @param {Map<number, Array>}  articlesByTerm  Articles keyed by host term id.
 * @param {Set<number>}         [visited]       Visited term ids for this walk.
 * @param {number}              [depth]         Current depth (0-based).
 * @return {{node: Object, leafIds: number[]}}
 */
function buildNode( term, kidsByParent, articlesByTerm, visited, depth ) {
	const visitedSet = visited || new Set();
	const currentDepth = depth || 0;

	const ownArticles = ( articlesByTerm.get( term.id ) || [] ).map( ( a ) => ( {
		id:    a.id,
		slug:  a.slug,
		title: decodeEntities( a.title?.rendered || '' ),
	} ) );

	// Cycle short-circuit: if we've already visited this term in this walk,
	// or we've blown past the hard depth cap, treat as a leaf.
	if ( visitedSet.has( term.id ) || currentDepth >= MAX_TREE_DEPTH ) {
		return {
			node: {
				id:       term.id,
				slug:     term.slug,
				name:     decodeEntities( term.name || '' ),
				count:    ownArticles.length,
				children: [],
				articles: ownArticles,
			},
			leafIds: [ term.id ],
		};
	}
	visitedSet.add( term.id );

	const kids = kidsByParent.get( term.id ) || [];
	const childNodes = [];
	const leafIds = [];

	if ( kids.length === 0 ) {
		// Structural leaf — its own id always counts.
		leafIds.push( term.id );
	}
	for ( const child of kids ) {
		const built = buildNode( child, kidsByParent, articlesByTerm, visitedSet, currentDepth + 1 );
		childNodes.push( built.node );
		for ( const id of built.leafIds ) {
			leafIds.push( id );
		}
	}

	const aggregated =
		ownArticles.length +
		childNodes.reduce( ( acc, c ) => acc + ( c.count || 0 ), 0 );

	return {
		node: {
			id:       term.id,
			slug:     term.slug,
			name:     decodeEntities( term.name || '' ),
			count:    aggregated,
			children: childNodes,
			articles: ownArticles,
		},
		leafIds,
	};
}

/**
 * Collect the IDs of every term in the subtree that should host articles —
 * structural leaves AND intermediate nodes whose own count > 0.
 *
 * Guarded by a per-walk visited-Set + depth cap so a malformed taxonomy
 * with circular parent chains terminates instead of hanging.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {number}              termId        Root term id.
 * @param {Map<number, Array>}  kidsByParent  Parent-id → child terms.
 * @param {Map<number, Object>} termsById     Term-id → term lookup.
 * @param {Set<number>}         [visited]     Visited term ids for this walk.
 * @param {number}              [depth]       Current depth (0-based).
 * @return {number[]} Flat list of host term ids in the subtree.
 */
function collectArticleHostsRec( termId, kidsByParent, termsById, visited, depth ) {
	const visitedSet = visited || new Set();
	const currentDepth = depth || 0;

	// Cycle short-circuit + depth cap. Treat as a leaf — push the id once and
	// return without descending. The Set lives PER WALK, not module-level.
	if ( visitedSet.has( termId ) || currentDepth >= MAX_TREE_DEPTH ) {
		return [ termId ];
	}
	visitedSet.add( termId );

	const out = [];
	const term = termsById.get( termId );
	const kids = kidsByParent.get( termId ) || [];
	const hasOwnCount = term && ( term.count || 0 ) > 0;
	const isStructuralLeaf = kids.length === 0;
	// Hosts are intermediates whose own count > 0, plus structural leaves
	// (which we always include regardless of count, matching the legacy
	// PHP-proxy behavior).
	if ( hasOwnCount || isStructuralLeaf ) {
		out.push( termId );
	}
	for ( const k of kids ) {
		for ( const id of collectArticleHostsRec( k.id, kidsByParent, termsById, visitedSet, currentDepth + 1 ) ) {
			out.push( id );
		}
	}
	return out;
}

/**
 * Index a flat taxonomy into the structures needed by the tree builders.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {Array} taxonomyTerms Flat ht-kb-category list from REST.
 * @return {{kidsByParent: Map<number, Array>, termsById: Map<number, Object>}}
 */
export function indexTaxonomy( taxonomyTerms ) {
	const kidsByParent = new Map();
	const termsById = new Map();
	if ( ! Array.isArray( taxonomyTerms ) ) {
		return { kidsByParent, termsById };
	}
	for ( const t of taxonomyTerms ) {
		termsById.set( t.id, t );
		const list = kidsByParent.get( t.parent ) || [];
		list.push( t );
		kidsByParent.set( t.parent, list );
	}
	return { kidsByParent, termsById };
}

/**
 * Locate the parent term and compute its article-host list. Used by
 * KBCategory before the article fetch so the right `ht-kb-category[]=`
 * filter values can be passed to `getCategoryArticles`.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {Array}  taxonomyTerms Flat ht-kb-category list from REST.
 * @param {string} parentSlug    Slug of the top-level category.
 * @return {?{parentTerm: Object, leafIds: number[]}} null when slug isn't found.
 */
export function getParentAndHosts( taxonomyTerms, parentSlug ) {
	const { kidsByParent, termsById } = indexTaxonomy( taxonomyTerms );
	let parentTerm = null;
	for ( const t of termsById.values() ) {
		if ( t.slug === parentSlug ) {
			parentTerm = t;
			break;
		}
	}
	if ( ! parentTerm ) {
		return null;
	}
	const leafIds = collectArticleHostsRec( parentTerm.id, kidsByParent, termsById );
	return { parentTerm, leafIds };
}

/**
 * Build the full category-view payload from the flat taxonomy and articles.
 *
 * Note: this helper is also responsible for the dedup pass (C1). Article
 * counts at intermediate/leaf nodes therefore reflect UNIQUE membership —
 * `truncated_remaining` is computed against the post-dedup count for the
 * same reason.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {Array}  taxonomyTerms Flat ht-kb-category list from REST.
 * @param {string} parentSlug    Slug of the top-level category to render.
 * @param {Array}  articles      Flat article list as returned by the REST envelope.
 * @param {Object} [opts]        Optional overrides.
 * @param {number} [opts.totalServerArticles] Server-reported total used to
 *                                            compute `truncated_remaining`.
 * @param {boolean}[opts.truncated]           When true, articles were paginated past
 *                                            the cap and `truncated_remaining` is
 *                                            relevant.
 * @return {?{
 *   category: {id:number, slug:string, name:string},
 *   subcategories: Array,
 *   truncated_remaining: number,
 *   docCategoriesBaseUrl: string,
 *   leafIds: number[]
 * }} null when `parentSlug` is not present in the taxonomy.
 */
export function buildCategoryTree( taxonomyTerms, parentSlug, articles, opts = {} ) {
	if ( ! Array.isArray( taxonomyTerms ) || taxonomyTerms.length === 0 ) {
		return null;
	}

	const { kidsByParent, termsById } = indexTaxonomy( taxonomyTerms );

	let parentTerm = null;
	for ( const t of termsById.values() ) {
		if ( t.slug === parentSlug ) {
			parentTerm = t;
			break;
		}
	}
	if ( ! parentTerm ) {
		return null;
	}

	const leafIds = collectArticleHostsRec( parentTerm.id, kidsByParent, termsById );

	// Group articles by their home term — first matching host id in our subtree.
	// Articles whose tags don't intersect our subtree are silently dropped
	// (orphans). This is the dedup pass (C1).
	const leafIdLookup = new Set( leafIds );
	const seen = new Set();
	const articlesByTerm = new Map();
	const inputArticles = Array.isArray( articles ) ? articles : [];

	for ( const a of inputArticles ) {
		if ( seen.has( a.id ) ) {
			continue;
		}
		const cats = Array.isArray( a[ 'ht-kb-category' ] ) ? a[ 'ht-kb-category' ] : [];
		let homeId = null;
		for ( const cid of cats ) {
			if ( leafIdLookup.has( cid ) ) {
				homeId = cid;
				break;
			}
		}
		if ( homeId === null ) {
			continue; // orphan — its tagged leaves aren't in our subtree.
		}
		seen.add( a.id );
		const list = articlesByTerm.get( homeId ) || [];
		list.push( a );
		articlesByTerm.set( homeId, list );
	}

	// Build the subcategory tree under the parent. Direct children of
	// parentTerm are the top-level sidebar entries; the parent itself is
	// represented by the breadcrumb / category header, not as a sidebar node.
	const directChildren = kidsByParent.get( parentTerm.id ) || [];
	const subcategories = directChildren
		.map( ( c ) => buildNode( c, kidsByParent, articlesByTerm ).node )
		// Prune direct-child nodes with zero aggregated articles (matches the
		// PHP proxy's pruning behavior).
		.filter( ( node ) => node.count > 0 );

	// `truncated_remaining` reflects how many articles the API said exist but
	// we couldn't render. After dedup, `seen.size` is the unique-rendered
	// count — server total minus that is the closest accurate approximation.
	// (Exact accuracy requires fetching every page even past the cap, which
	// is the very thing the cap exists to prevent.)
	const totalServer = typeof opts.totalServerArticles === 'number'
		? opts.totalServerArticles
		: 0;
	const truncatedRemaining =
		opts.truncated && totalServer > seen.size
			? Math.max( 0, totalServer - seen.size )
			: 0;

	return {
		category: {
			id:   parentTerm.id,
			slug: parentTerm.slug,
			name: decodeEntities( parentTerm.name || '' ),
		},
		subcategories,
		truncated_remaining: truncatedRemaining,
		docCategoriesBaseUrl: getDocCategoriesBaseUrl(),
		leafIds,
	};
}
