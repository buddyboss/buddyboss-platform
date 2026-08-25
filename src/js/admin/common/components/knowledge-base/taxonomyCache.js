/**
 * Module-level memoization of the full ht-kb-category taxonomy.
 *
 * KBLanding and KBCategory both consume the same dataset; without this,
 * switching between them would re-fetch ~115 terms across 2 pages every
 * time. The cache has a 10-minute TTL — after which stale data is served
 * immediately while a background refresh runs (stale-while-revalidate) so
 * the user never sees a loading state for a benign refresh.
 *
 * Critically, the in-flight fetch is NOT bound to any specific consumer's
 * AbortSignal. Consumer aborts only short-circuit their own await; the
 * underlying request runs to completion and populates the cache for the
 * next reader. Otherwise an admin who closes the modal mid-fetch would
 * poison the cache for the next admin who opens it.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

import { kbApi } from '../../utils/kbApi';

const TTL_MS = 10 * 60 * 1000;

let cached = null;
let cachedAt = 0;
let inFlight = null;

/**
 * Resolve a value while honoring the consumer's signal — synchronous-by-spec
 * for cached hits but rejects with `AbortError` if the signal is already
 * aborted, matching `fetch()` behavior.
 */
function abortableResolve( value, signal ) {
	if ( ! signal ) {
		return Promise.resolve( value );
	}
	if ( signal.aborted ) {
		return Promise.reject( new DOMException( 'Aborted', 'AbortError' ) );
	}
	return Promise.resolve( value );
}

/**
 * Race the consumer's signal against the underlying in-flight promise.
 * The promise itself runs to completion regardless of what this consumer
 * does — the abort is purely a local opt-out.
 */
function abortableRace( promise, signal ) {
	if ( ! signal ) {
		return promise;
	}
	return new Promise( ( resolve, reject ) => {
		const onAbort = () => {
			signal.removeEventListener( 'abort', onAbort );
			reject( new DOMException( 'Aborted', 'AbortError' ) );
		};
		if ( signal.aborted ) {
			onAbort();
			return;
		}
		signal.addEventListener( 'abort', onAbort, { once: true } );
		promise.then(
			( v ) => {
				signal.removeEventListener( 'abort', onAbort );
				resolve( v );
			},
			( e ) => {
				signal.removeEventListener( 'abort', onAbort );
				reject( e );
			}
		);
	} );
}

/**
 * Get the full taxonomy. Honors a 10-minute TTL with stale-while-revalidate
 * semantics; consumers that supply a signal can abort their own await
 * without affecting the underlying fetch.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {AbortSignal} [signal] Optional consumer abort signal.
 * @return {Promise<Array>} Flat array of taxonomy term objects.
 */
export function getTaxonomy( signal ) {
	const now = Date.now();
	const fresh = cached && now - cachedAt < TTL_MS;

	if ( fresh ) {
		return abortableResolve( cached, signal );
	}

	// Stale or empty — kick off a background revalidate if one isn't running.
	// CRITICAL: no signal here. Consumer aborts must not kill the shared
	// in-flight fetch.
	if ( ! inFlight ) {
		inFlight = kbApi
			.getAllCategories( {} )
			.then( ( terms ) => {
				cached = terms;
				cachedAt = Date.now();
				inFlight = null;
				return terms;
			} )
			.catch( ( err ) => {
				inFlight = null;
				throw err;
			} );
	}

	// Stale path: serve stale immediately, let the revalidate complete in the
	// background. The consumer never sees a loading state for a refresh.
	if ( cached ) {
		return abortableResolve( cached, signal );
	}

	// Cold path: must wait for the in-flight fetch.
	return abortableRace( inFlight, signal );
}

/**
 * Reset the module-level cache. Used by Retry buttons so a previously-
 * cached empty/error response doesn't get re-served as success.
 *
 * @since BuddyBoss [BBVERSION]
 */
export function clearTaxonomy() {
	cached = null;
	cachedAt = 0;
	inFlight = null;
}
