/**
 * BuddyBoss Knowledge Base — direct cross-origin fetch helpers.
 *
 * Mirrors the data-flow pattern used by the buddyboss-gamification plugin: each
 * method calls `https://buddyboss.com/wp-json/wp/v2/...` directly from the
 * admin browser. No PHP proxy, no shared server cache. Each admin's browser
 * fetches fresh on first modal-open and caches subsequent reads in
 * localStorage (see kbCache.js for article-body cache).
 *
 * Trade-offs vs server-side proxy:
 *  - Faster cold first paint (one fewer hop, no PHP boot).
 *  - No shared cache across admins on the same site.
 *  - Depends on buddyboss.com CORS staying permissive on /wp-json/wp/v2/*.
 *  - Admin IP/UA leaks to buddyboss.com (KB content is public, low concern).
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

import { applyFilters } from '@wordpress/hooks';

const DEFAULT_BASE = 'https://buddyboss.com/wp-json/wp/v2';

/**
 * Resolve the KB API base URL. Filterable so a site can point at a staging
 * REST endpoint or a self-hosted mirror without forking the JS bundle.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @return {string} REST base, no trailing slash.
 */
function apiBase() {
	/**
	 * Filter the BuddyBoss KB REST API base URL.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {string} base The default `https://buddyboss.com/wp-json/wp/v2` base.
	 */
	return applyFilters( 'bb.admin.kb.apiBase', DEFAULT_BASE );
}

const TAXONOMY_PAGE_CAP = 8;   // ~800 terms max — current taxonomy is ~115
const ARTICLE_PAGE_CAP  = 12;  // ~1200 articles max per category — Customizations has 556

/**
 * Conservative slug regex — matches WP `sanitize_title()` output: lowercase
 * alphanumerics and hyphens. Length-bounded to defend against pathological
 * inputs reaching `encodeURIComponent`.
 */
const SLUG_RE = /^[a-z0-9-]{1,80}$/i;

async function jsonGet( url, signal ) {
	const res = await fetch( url, { signal, credentials: 'omit' } );
	if ( ! res.ok ) {
		const err = new Error( `HTTP ${ res.status }` );
		err.status = res.status;
		throw err;
	}
	const totalPagesHeader = res.headers.get( 'x-wp-totalpages' );
	if ( ! totalPagesHeader && typeof window !== 'undefined' && window.bbAdminData?.debug ) {
		// eslint-disable-next-line no-console
		console.warn( '[bb-kb] missing x-wp-totalpages header on', url );
	}
	return {
		body: await res.json(),
		totalPages: parseInt( totalPagesHeader || '1', 10 ),
		total: parseInt( res.headers.get( 'x-wp-total' ) || '0', 10 ),
	};
}

export const kbApi = {
	/**
	 * Fetch the full ht-kb-category taxonomy paginated. Returns the flat
	 * array; consumers (KBLanding/KBCategory) build the parent→children tree
	 * and aggregate counts.
	 *
	 * @param {{signal?: AbortSignal}} options
	 * @return {Promise<Array>} Array of { id, parent, slug, name, count, description }.
	 */
	async getAllCategories( options = {} ) {
		const all = [];
		let page = 1;
		for ( ; page <= TAXONOMY_PAGE_CAP; page++ ) {
			const url = `${ apiBase() }/ht-kb-category?per_page=100&page=${ page }&_fields=id,parent,slug,name,count,description`;
			const { body, totalPages } = await jsonGet( url, options.signal );
			if ( Array.isArray( body ) ) {
				all.push( ...body );
			}
			if ( page >= totalPages ) {
				break;
			}
		}
		return all;
	},

	/**
	 * Fetch articles for a list of host-category IDs (paginated). Used by
	 * KBCategory after the descendant tree is built — passes every host id
	 * via the `ht-kb-category[]=` array filter so a single query returns the
	 * full set across the whole sub-tree. "Hosts" are leaves plus any
	 * intermediate term whose own count > 0.
	 *
	 * @param {number[]} leafIds Host taxonomy IDs (must be > 0).
	 * @param {{signal?: AbortSignal}} options
	 * @return {Promise<{articles: Array, total: number, truncated: boolean}>}
	 */
	async getCategoryArticles( leafIds, options = {} ) {
		if ( ! Array.isArray( leafIds ) || leafIds.length === 0 ) {
			return { articles: [], total: 0, truncated: false };
		}
		const filterQs = leafIds.map( ( id ) => `ht-kb-category%5B%5D=${ id }` ).join( '&' );
		const articles = [];
		let total = 0;
		let totalPages = 0;
		for ( let page = 1; page <= ARTICLE_PAGE_CAP; page++ ) {
			const url = `${ apiBase() }/ht-kb?per_page=100&page=${ page }&_fields=id,slug,title,ht-kb-category&${ filterQs }`;
			const { body, totalPages: pageTotalPages, total: pageTotal } = await jsonGet( url, options.signal );
			totalPages = pageTotalPages;
			total = pageTotal;
			if ( Array.isArray( body ) ) {
				articles.push( ...body );
			}
			if ( page >= totalPages ) {
				break;
			}
		}
		// Direct comparison against the cap is more accurate than relying on
		// the post-loop counter or comparing total vs articles.length (the
		// latter is misleading after the dedup grouping in KBCategory).
		const truncated = totalPages > ARTICLE_PAGE_CAP;
		return { articles, total, truncated };
	},

	/**
	 * Fetch a single article body by slug. Returns the first match envelope
	 * normalized to `{id, title, content, imageUrl}`. Caller is responsible
	 * for sanitizing `content` before render and for caching the result.
	 *
	 * Defensive slug-validation rejects malformed input (anything outside
	 * lowercase alphanumerics + hyphens, max 80 chars) before reaching the
	 * network. Short-circuits to `null` so the calling component lands on
	 * its `notfound` branch instead of issuing a guaranteed-bad request.
	 *
	 * @param {string} slug Article slug.
	 * @param {{signal?: AbortSignal}} options
	 * @return {Promise<?{id, title, content, imageUrl}>} null when no match or invalid slug.
	 */
	async getArticle( slug, options = {} ) {
		if ( typeof slug !== 'string' || ! SLUG_RE.test( slug ) ) {
			return null;
		}
		const url = `${ apiBase() }/ht-kb/?slug=${ encodeURIComponent( slug ) }&_fields=id,title,content,acf`;
		const { body } = await jsonGet( url, options.signal );
		if ( ! Array.isArray( body ) || ! body[ 0 ] ) {
			return null;
		}
		const row = body[ 0 ];
		return {
			id:       row.id || 0,
			title:    decodeEntities( row.title?.rendered || '' ),
			content:  row.content?.rendered || '',
			imageUrl: forceHttps( row.acf?.featured_image || '' ),
		};
	},
};

/**
 * Decode the small set of HTML entities WordPress emits in `title.rendered`
 * (mirrors PHP's `wp_specialchars_decode` default ENT_NOQUOTES + named-entity
 * coverage). Title strings are short, ASCII-only entities, and never injected
 * as HTML — the result is rendered as a React text child only — so a small
 * regex table is both safer (no DOM-API innerHTML write at all) and adequate.
 * For numeric entities (`&#039;`, `&#x27;`) we also handle the common forms.
 *
 * Numeric code points are bounds-checked against the Unicode range
 * (0..0x10FFFF) before being passed to `String.fromCodePoint`, which throws
 * on out-of-range inputs. Out-of-range entities pass through verbatim.
 *
 * @param {string} str Raw `title.rendered` from the WP REST envelope.
 * @return {string} Decoded text.
 */
export function decodeEntities( str ) {
	if ( typeof str !== 'string' || str === '' ) {
		return '';
	}
	const named = {
		'&amp;':   '&',
		'&lt;':    '<',
		'&gt;':    '>',
		'&quot;':  '"',
		'&#039;':  '\'',
		'&#39;':   '\'',
		'&apos;':  '\'',
		'&nbsp;':  ' ',
		'&hellip;': '…',
		'&ndash;': '–',
		'&mdash;': '—',
		'&lsquo;': '‘',
		'&rsquo;': '’',
		'&ldquo;': '“',
		'&rdquo;': '”',
	};
	let out = str.replace( /&(?:amp|lt|gt|quot|#0?39|apos|nbsp|hellip|ndash|mdash|lsquo|rsquo|ldquo|rdquo);/g, ( m ) => named[ m ] || m );
	// Numeric decimal entities: &#NN;
	out = out.replace( /&#(\d+);/g, ( _m, code ) => {
		const n = parseInt( code, 10 );
		if ( ! Number.isFinite( n ) || n < 0 || n > 0x10FFFF ) {
			return _m;
		}
		try {
			return String.fromCodePoint( n );
		} catch ( e ) {
			return _m;
		}
	} );
	// Numeric hex entities: &#xNN;
	out = out.replace( /&#x([0-9a-fA-F]+);/g, ( _m, code ) => {
		const n = parseInt( code, 16 );
		if ( ! Number.isFinite( n ) || n < 0 || n > 0x10FFFF ) {
			return _m;
		}
		try {
			return String.fromCodePoint( n );
		} catch ( e ) {
			return _m;
		}
	} );
	return out;
}

/**
 * Coerce http: → https: on a buddyboss.com URL to avoid mixed-content
 * blocks on https admin pages. (Matches the K4 PHP-side fix that's now gone.)
 */
function forceHttps( url ) {
	if ( typeof url !== 'string' || url === '' ) {
		return '';
	}
	return url.replace( /^http:\/\//i, 'https://' );
}
