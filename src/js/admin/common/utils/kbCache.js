/**
 * localStorage-backed cache for KB article bodies.
 *
 * Mirrors the BuddyBoss Gamification plugin's `getFromCache` / `saveToCache`
 * pattern verbatim — same `{timestamp, data, duration}` storage shape, same
 * single-key-per-slug layout, no index, no LRU bookkeeping, no soft cap. The
 * earlier richer cache had subtle bugs where index updates on read could
 * fail silently and make subsequent reads look like misses; this simpler
 * design has only one place where data lives, so a successful write is
 * always readable until either the TTL expires or the browser evicts it
 * under quota pressure.
 *
 * - Key: `bb_kb_help_content_<slug>` (per-origin localStorage scope).
 * - Default TTL: 3 days. TTL stored alongside data so it travels with the entry.
 * - Quiet-fail on Safari Private mode and any storage exception — read returns
 *   null, write is a no-op.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

const KEY_PREFIX       = 'bb_kb_help_content_';
const DEFAULT_DURATION = 3 * 24 * 60 * 60 * 1000; // 3 days in ms.

export const kbCache = {
	/**
	 * Read a cached article. Returns the stored `data` payload or null on
	 * miss / expiry / parse error.
	 *
	 * @param {string} slug Article slug.
	 * @returns {?Object}
	 */
	get( slug ) {
		try {
			const raw = window.localStorage.getItem( KEY_PREFIX + slug );
			if ( ! raw ) {
				return null;
			}
			const { timestamp, data, duration } = JSON.parse( raw );
			const ttl = typeof duration === 'number' ? duration : DEFAULT_DURATION;
			if ( typeof timestamp !== 'number' || Date.now() - timestamp >= ttl ) {
				return null;
			}
			return data;
		} catch ( e ) {
			return null;
		}
	},

	/**
	 * Write an article into the cache. Quiet-fails on storage exceptions.
	 *
	 * @param {string} slug     Article slug.
	 * @param {Object} data     Article payload to cache.
	 * @param {number} [duration] Override TTL in ms; defaults to 3 days.
	 */
	set( slug, data, duration = DEFAULT_DURATION ) {
		try {
			window.localStorage.setItem(
				KEY_PREFIX + slug,
				JSON.stringify( {
					timestamp: Date.now(),
					data,
					duration,
				} )
			);
		} catch ( e ) {
			// Quiet-fail (Safari Private mode, quota exceeded, etc.).
		}
	},

	/**
	 * Remove a single cached entry, or all entries when called without args.
	 *
	 * @param {string} [slug] Slug to drop. Omit to drop the whole cache.
	 */
	clear( slug ) {
		try {
			if ( slug ) {
				window.localStorage.removeItem( KEY_PREFIX + slug );
				return;
			}
			// Drop every key under our prefix.
			const drop = [];
			for ( let i = 0; i < window.localStorage.length; i++ ) {
				const key = window.localStorage.key( i );
				if ( key && key.indexOf( KEY_PREFIX ) === 0 ) {
					drop.push( key );
				}
			}
			drop.forEach( ( k ) => window.localStorage.removeItem( k ) );
		} catch ( e ) {
			// ignore
		}
	},
};
