/**
 * BuddyBoss Knowledge Base — same-origin proxy fetch helpers.
 *
 * Routes every KB read through `BB_REST_Help_Content_Endpoint::proxy_request`
 * at `/wp-json/buddyboss/v1/help-content/proxy`. Clients send a path-only
 * URL fragment (e.g. `/wp-json/wp/v2/ht-kb-category?per_page=100`); the
 * server prepends `https://buddyboss.com`, fetches via `wp_remote_get()`
 * with a 12h transient cache, and returns `{ body, headers, status }`.
 *
 * Why proxied (not direct cross-origin like gamification's KB helpers):
 *  - `manage_options` permission gate (no anonymous KB egress).
 *  - Server-side transient cache shared across all admins on a site.
 *  - Immune to CORS regressions on buddyboss.com (header-strip on cache replay).
 *  - SSRF-safe: the egress host is server-controlled, not client-controlled.
 *  - One cache flush (`?bb_clear_placeholder_cache=1`) sweeps every KB read.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

import { decodeEntities } from '@wordpress/html-entities';

import { applyFilters } from '@wordpress/hooks';

const DEFAULT_PATH_BASE = '/wp-json/wp/v2';

/**
 * Resolve the KB API path base. Filterable so a site can point at a
 * different upstream prefix (e.g. a staging mirror under `/wp-json/wp/v3`)
 * without forking the JS bundle. The host is NOT influenced by this filter
 * — that's hard-coded to `buddyboss.com` server-side, see
 * `bb_help_content_proxy_base` PHP filter for that knob.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @return {string} Path base under buddyboss.com, no trailing slash.
 */
function apiBase() {
	/**
	 * Filter the BuddyBoss KB REST path base.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {string} base The default `/wp-json/wp/v2` path base.
	 */
	return applyFilters( 'bb.admin.kb.apiBase', DEFAULT_PATH_BASE );
}

const TAXONOMY_PAGE_CAP = 8;   // ~800 terms max — current taxonomy is ~115
const ARTICLE_PAGE_CAP  = 12;  // ~1200 articles max per category — Customizations has 556

/**
 * Conservative slug regex — matches WP `sanitize_title()` output: lowercase
 * alphanumerics and hyphens. Length-bounded to defend against pathological
 * inputs reaching `encodeURIComponent`.
 */
const SLUG_RE = /^[a-z0-9-]{1,80}$/i;

/**
 * Read the localized admin data, from whichever admin app is hosting the KB
 * modal. The Settings app localizes `bbAdminData`; the Integrations app
 * localizes `bbIntegrationsData`. Both expose `apiUrl` + `nonce`, so the shared
 * KB layer reads whichever is present.
 *
 * @return {Object} Localized data, or an empty object.
 */
function kbAdminData() {
	if ( typeof window === 'undefined' ) {
		return {};
	}
	return window.bbAdminData || window.bbIntegrationsData || {};
}

/**
 * Resolve the same-origin help-content proxy URL.
 *
 * Reads `apiUrl` from the host app's localized data (set to
 * `rest_url( 'buddyboss/v1/' )`). Falls back to the WP-Admin REST root if the
 * localized data is missing — the controller registers under both
 * `buddyboss/v1` (with platform-api) and `bb/v1` (without), so the fallback
 * resolves on platform-only installs.
 *
 * @return {string} Proxy POST endpoint URL.
 */
function proxyEndpoint() {
	const root = kbAdminData().apiUrl || '/wp-json/buddyboss/v1/';
	const base = root.endsWith( '/' ) ? root : root + '/';
	return base + 'help-content/proxy';
}

/**
 * Read the WP REST nonce from the host app's localized data.
 *
 * @return {string} REST nonce, or empty string if not localized.
 */
function restNonce() {
	return kbAdminData().nonce || '';
}

/**
 * POST a path-only URL fragment to the proxy and unwrap the envelope.
 *
 * Returns the same `{ body, totalPages, total }` shape the previous
 * direct-cross-origin implementation returned, so all four `kbApi`
 * methods above the seam work unchanged. The pagination headers come
 * from the proxy's `headers` sidecar (server reads `x-wp-totalpages`
 * and `x-wp-total` off the upstream response and forwards them).
 *
 * @param {string} path Path-only URL under buddyboss.com (must start with `/`).
 * @param {AbortSignal} [signal] Optional fetch AbortSignal.
 * @return {Promise<{body: any, totalPages: number, total: number}>}
 */
async function jsonGet( path, signal ) {
	const res = await fetch( proxyEndpoint(), {
		method:      'POST',
		signal,
		credentials: 'same-origin',
		headers: {
			Accept:         'application/json',
			'Content-Type': 'application/json',
			'X-WP-Nonce':   restNonce(),
		},
		body: JSON.stringify( { url: path } ),
	} );
	if ( ! res.ok ) {
		const err = new Error( `HTTP ${ res.status }` );
		err.status = res.status;
		throw err;
	}
	const envelope        = await res.json();
	const headers         = ( envelope && typeof envelope === 'object' && envelope.headers && typeof envelope.headers === 'object' ) ? envelope.headers : {};
	const totalPagesHeader = headers[ 'x-wp-totalpages' ];
	if ( ! totalPagesHeader && kbAdminData().debug ) {
		// eslint-disable-next-line no-console
		console.warn( '[bb-kb] missing x-wp-totalpages header on', path );
	}
	return {
		body:       envelope && typeof envelope === 'object' ? envelope.body : null,
		totalPages: parseInt( totalPagesHeader || '1', 10 ),
		total:      parseInt( headers[ 'x-wp-total' ] || '0', 10 ),
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

// `decodeEntities` for KB title strings is re-exported from
// @wordpress/html-entities (imported above) — the WP-canonical decoder with
// full named-entity coverage, already used by the integration components — so
// the whole admin surface decodes entities one consistent way. Re-exported here
// so the existing KB consumers that import it from this module keep working.
export { decodeEntities };

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
