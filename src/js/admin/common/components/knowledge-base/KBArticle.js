/**
 * BuddyBoss Admin Settings 2.0 - Knowledge Base Article Renderer
 *
 * Right-pane reader rendered when the user picks an article from the KB
 * sidebar. The component owns a dual-tier cache strategy:
 *
 *   1. localStorage (`kbCache`) — fastest path. On mount/slug-change, a
 *      cache hit short-circuits the render to `ready` with no network
 *      request issued.
 *   2. Direct cross-origin fetch via `kbApi.getArticle()` against
 *      `https://buddyboss.com/wp-json/wp/v2/ht-kb`. On success, the body
 *      is written back to localStorage so subsequent visits skip the
 *      network entirely.
 *
 * Sanitization is mandatory. The fetched HTML is run through
 * `sanitizeKbArticle()` (DOMParser + allowlist walker, project-style — the
 * existing `sanitize.js` pattern, not a DOMPurify dependency) before being
 * injected via React's documented raw-HTML escape hatch
 * (`dangerouslySetInnerHTML`). This is the only file in the KB feature that
 * uses that escape hatch; sanitization runs upstream of the prop and is
 * memoized on `article.html` to avoid re-parsing on unrelated re-renders.
 *
 * Five status branches are handled:
 *
 *   - `idle`     — no slug yet (initial render or cleared selection).
 *   - `loading`  — request in flight; renders an `aria-busy` skeleton.
 *   - `ready`    — article loaded; renders title, optional image, and the
 *                  sanitized body. An empty `html` field falls through to
 *                  an "article is empty" message with a link out to
 *                  buddyboss.com/docs/{slug}/.
 *   - `error`    — fetch failed (network error, non-2xx other than 404);
 *                  renders a `role="alert"` notice with a fallback link.
 *   - `notfound` — fetch returned 404, or the response array was empty;
 *                  renders a "moved or removed" notice with a link to the
 *                  docs index.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

import { useEffect, useMemo, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { kbApi } from '../../utils/kbApi';
import { kbCache } from '../../utils/kbCache';
import { sanitizeKbArticle, safeImageUrl } from '../../utils/sanitizeKbArticle';
import { getDocsBaseUrl } from './urls';

// Filterable docs base URL — used for fallback "open on buddyboss.com" links
// rendered in error/empty/notfound states. Resolved once at module load so
// the value is identity-stable across renders.
const docsBaseUrl = getDocsBaseUrl();

/**
 * Knowledge Base article reader.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {Object} props      Component props.
 * @param {string} props.slug Article slug to load. Falsy slugs render nothing.
 * @returns {JSX.Element|null} Article markup, status placeholder, or null.
 */
export default function KBArticle( { slug } ) {
	const [ status, setStatus ] = useState( 'idle' );
	const [ article, setArticle ] = useState( null );

	useEffect( () => {
		if ( ! slug ) {
			setArticle( null );
			setStatus( 'idle' );
			return undefined;
		}

		const cached = kbCache.get( slug );
		if ( cached ) {
			setArticle( cached );
			setStatus( 'ready' );
			return undefined;
		}

		// Mirroring the Gamification plugin's `fetchHelpContent`: do NOT abort
		// the underlying fetch on unmount/slug-change. Aborting was causing a
		// silent cache-miss bug — when an admin navigated away before the
		// fetch completed (modal close, category switch, fast clicks), the
		// `.then()` callback never ran, so `kbCache.set()` was skipped, and
		// the next visit re-fetched the same article instead of serving the
		// already-downloaded body. Letting the fetch run free and writing to
		// localStorage unconditionally means in-flight requests still warm
		// the cache even when the consumer is gone. The `isCurrent` flag
		// suppresses setState on a stale component without blocking the
		// cache write.
		let isCurrent = true;
		setStatus( 'loading' );
		kbApi
			.getArticle( slug )
			.then( ( res ) => {
				if ( res && res.id ) {
					const obj = {
						html:     res.content,
						title:    res.title,
						imageUrl: res.imageUrl,
					};
					// Always populate the cache, even if the consumer unmounted.
					// `kbCache` records its own timestamp/duration around `obj`;
					// no need to attach `fetchedAt` to the article payload itself.
					kbCache.set( slug, obj );
					if ( ! isCurrent ) return;
					setArticle( obj );
					setStatus( 'ready' );
				} else if ( isCurrent ) {
					// 2xx success but the response was empty / no article matched —
					// treat as not-found so the user gets the "moved or removed"
					// message rather than a generic error.
					setStatus( 'notfound' );
				}
			} )
			.catch( ( err ) => {
				if ( ! isCurrent ) return;
				// `jsonGet` rejects on non-2xx with an Error carrying `.status`.
				// 404 → not-found branch; anything else → error.
				if ( err && err.status === 404 ) {
					setStatus( 'notfound' );
					return;
				}
				setStatus( 'error' );
			} );

		return () => {
			isCurrent = false;
		};
	}, [ slug ] );

	const sanitizedHtml = useMemo(
		() => ( article && article.html ? sanitizeKbArticle( article.html ) : '' ),
		[ article && article.html ]
	);

	// Validate the hero image URL through the same host allowlist used for
	// inline article images. PHP esc_url_raw lets through any well-formed
	// http/https URL; without this gate, a malicious or compromised upstream
	// could point the hero `<img>` at any external host. Memoized on the raw
	// URL so re-renders that don't change the article skip the URL parse.
	const verifiedImageUrl = useMemo(
		() => ( article && article.imageUrl ? safeImageUrl( article.imageUrl ) : null ),
		[ article && article.imageUrl ]
	);

	if ( status === 'idle' ) return null;

	if ( status === 'loading' ) {
		return (
			<article className="bb-kb-article" aria-busy="true">
				<div className="bb-kb-article__skeleton" />
			</article>
		);
	}

	if ( status === 'error' ) {
		return (
			<article className="bb-kb-article">
				<div className="bb-kb-article__error" role="alert">
					{ __( 'Couldn’t load this article.', 'buddyboss' ) }{ ' ' }
					<a
						href={ `${ docsBaseUrl }${ slug }/` }
						target="_blank"
						rel="noopener noreferrer"
					>
						{ __( 'Open on BuddyBoss.com →', 'buddyboss' ) }
					</a>
				</div>
			</article>
		);
	}

	if ( status === 'notfound' ) {
		return (
			<article className="bb-kb-article">
				<div className="bb-kb-article__error" role="alert">
					{ __( 'This article was moved or removed.', 'buddyboss' ) }{ ' ' }
					<a
						href={ docsBaseUrl }
						target="_blank"
						rel="noopener noreferrer"
					>
						{ __( 'Browse all docs →', 'buddyboss' ) }
					</a>
				</div>
			</article>
		);
	}

	if ( ! article || article.html === '' ) {
		return (
			<article className="bb-kb-article">
				<p className="bb-kb-article__empty">
					{ __( 'This article is empty.', 'buddyboss' ) }{ ' ' }
					<a
						href={ `${ docsBaseUrl }${ slug }/` }
						target="_blank"
						rel="noopener noreferrer"
					>
						{ __( 'Open on BuddyBoss.com →', 'buddyboss' ) }
					</a>
				</p>
			</article>
		);
	}

	return (
		<article className="bb-kb-article">
			<h2 className="bb-kb-article__title">{ article.title }</h2>
			{ verifiedImageUrl && (
				<img
					className="bb-kb-article__image"
					src={ verifiedImageUrl }
					alt={ article.title || '' }
				/>
			) }
			{ /* eslint-disable-next-line react/no-danger -- output sanitized via sanitizeKbArticle */ }
			<div
				className="bb-kb-article__body"
				dangerouslySetInnerHTML={ { __html: sanitizedHtml } }
			/>
		</article>
	);
}
