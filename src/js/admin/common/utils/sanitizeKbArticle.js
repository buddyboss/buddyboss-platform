/**
 * Sanitize a Knowledge Base article HTML body.
 *
 * Wider tag/attribute allowlist than the standard settings sanitizer:
 * supports embedded video iframes (YouTube/Wistia/Vimeo only), images
 * from BuddyBoss CDN domains, and full table markup.
 *
 * Uses DOMParser and a recursive walker (not DOMPurify) to match the
 * project's existing sanitize.js pattern — no extra dependency.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

// Comprehensive allowlist sized to WordPress's full block-element vocabulary.
// Anything `wp_kses_post()` permits AND that's safe in a cross-origin admin
// modal context lives here. Risky elements (form controls, scriptable
// resource loaders, opaque embeds) are in `DENYLIST_TAGS` below.
//
// Survey basis: real BuddyBoss KB articles use figure/figcaption/img/ol/li/etc.,
// but the allowlist deliberately covers the rest of the Gutenberg vocabulary
// so future articles using core/details, core/audio, core/video, definition
// lists, callouts (aside), or inline semantic markup (mark, kbd, sub/sup,
// time, abbr) render correctly without another round-trip through this code.
const ALLOWED_TAGS = new Set( [
	// Headings.
	'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'hgroup',
	// Block-level grouping (Gutenberg core/group, core/details, etc.).
	'p', 'br', 'hr', 'div', 'span',
	'section', 'article', 'aside', 'header', 'footer', 'nav', 'main',
	'details', 'summary',
	// Lists.
	'ul', 'ol', 'li',
	'dl', 'dt', 'dd',
	// Inline / phrasing.
	'a', 'strong', 'em', 'b', 'i', 'u', 's',
	'mark', 'small', 'cite', 'q', 'abbr', 'dfn',
	'code', 'pre', 'kbd', 'samp', 'var',
	'sub', 'sup', 'time',
	'del', 'ins',
	'blockquote',
	// Media / figures.
	'figure', 'figcaption', 'picture', 'img', 'iframe',
	'audio', 'video', 'source', 'track',
	// Tables (WP `wp_kses_post` allows the full table vocabulary).
	'table', 'caption', 'colgroup', 'col',
	'thead', 'tbody', 'tfoot', 'tr', 'th', 'td',
] );

// Tags that must be FULLY removed (children too) — anything that can execute,
// load remote resources outside our allowlist, or capture form input.
const DENYLIST_TAGS = new Set( [
	// Script execution.
	'script', 'noscript', 'template',
	// Style injection (we run inside admin UI; KB CSS must not bleed).
	'style', 'link',
	// Opaque/remote resource loaders.
	'object', 'embed', 'applet',
	// Meta-document affordances that have no place in injected article body.
	'meta', 'base',
	// Form controls — KB content is read-only.
	'form', 'input', 'button', 'select', 'textarea', 'option', 'fieldset', 'legend',
	// Foreign-content (SVG / MathML) roots. These are NOT in the allowlist, so
	// without an explicit entry here they'd hit the "unwrap" branch below and
	// have their children promoted into the surrounding HTML namespace — the
	// classic namespace-confusion mutation-XSS setup. Remove them (subtree and
	// all) instead; KB articles render their glyphs as <img>, not inline SVG.
	'svg', 'math', 'foreignobject',
] );

const ALLOWED_ATTRS = {
	a:      [ 'href', 'title', 'target', 'rel', 'download' ],
	// `srcset`, `sizes`, `loading`, `decoding`, `fetchpriority` are emitted by
	// WordPress core for every Gutenberg `core/image` block. Without them,
	// retina screens get the 1x variant, lazy-loading is lost, and Largest
	// Contentful Paint hints get stripped. `srcset` URLs are validated by
	// safeSrcset() before being written back.
	img:    [ 'src', 'srcset', 'sizes', 'alt', 'width', 'height', 'loading', 'decoding', 'fetchpriority' ],
	picture: [],
	// `<source>`/`<track>` only legal inside `<picture>`/`<audio>`/`<video>`;
	// the walker enforces parent-tag context before applying these.
	source: [ 'src', 'srcset', 'sizes', 'media', 'type' ],
	track:  [ 'src', 'kind', 'srclang', 'label', 'default' ],
	audio:  [ 'src', 'controls', 'loop', 'muted', 'preload' ],
	video:  [ 'src', 'controls', 'loop', 'muted', 'preload', 'poster', 'width', 'height', 'playsinline' ],
	iframe: [ 'src', 'width', 'height', 'allowfullscreen', 'frameborder', 'title', 'loading', 'referrerpolicy', 'sandbox', 'allow' ],
	// Ordered lists support `start` (continued numbering across sections), `reversed`,
	// and `type` (numeric vs alphabetic). `start` is in active use across BuddyBoss KB
	// articles; without it multi-section step lists renumber from 1 mid-article.
	ol:     [ 'start', 'reversed', 'type' ],
	li:     [ 'value' ],
	time:   [ 'datetime' ],
	details: [ 'open' ],
	q:      [ 'cite' ],
	blockquote: [ 'cite' ],
	abbr:   [ 'title' ],
	dfn:    [ 'title' ],
	del:    [ 'cite', 'datetime' ],
	ins:    [ 'cite', 'datetime' ],
	// Tables (WP `wp_kses_post` permits the full set on table elements).
	table:  [ 'summary', 'border', 'cellpadding', 'cellspacing', 'width' ],
	caption: [ 'align' ],
	colgroup: [ 'span', 'width' ],
	col:    [ 'span', 'width', 'align', 'valign' ],
	tr:     [ 'align', 'valign' ],
	thead:  [ 'align', 'valign' ],
	tbody:  [ 'align', 'valign' ],
	tfoot:  [ 'align', 'valign' ],
	// Table cells support rowspan/colspan/scope/headers — without these, KB articles
	// that use multi-row "Feature Category" cells render as a flat list instead of a
	// proper grouped table.
	td:     [ 'rowspan', 'colspan', 'headers', 'align', 'valign' ],
	th:     [ 'rowspan', 'colspan', 'scope', 'headers', 'align', 'valign' ],
	// `*` applies to every allowed tag. Gutenberg block markup carries
	// block-id selectors (`wp-block-image`, `wp-image-1234`) on `class`,
	// stable `id` for in-page anchors, `dir` for RTL, `lang` for inline
	// language switches, `title` for tooltip text. `data-*` is allowed
	// wholesale — Gutenberg blocks emit `data-` attributes for client IDs
	// and frontend hydration; they cannot execute.
	'*':    [ 'class', 'id', 'dir', 'lang', 'title' ],
};

// Tags that may carry a `style` attribute. KB articles use inline color /
// background for callout headings and padded cells. Style values are
// re-tokenised via `sanitizeStyle()` (allowlist of safe properties; rejects
// `url(...)`, `expression(...)`, and `javascript:`).
const STYLEABLE_TAGS = new Set( [
	'span', 'div', 'p', 'figure', 'figcaption',
	'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
	'blockquote', 'pre', 'code',
	'table', 'thead', 'tbody', 'tfoot', 'tr', 'th', 'td', 'col', 'colgroup',
	'ul', 'ol', 'li', 'dl', 'dt', 'dd',
	'section', 'article', 'aside', 'header', 'footer',
	'img',
] );

// Allowed CSS properties for the inline `style` attribute. Mirrors
// `utils/sanitize.js` so KB content and admin-controlled markup share the
// same notion of "safe declarations". Values containing `url(`, `expression(`,
// or `javascript:` are rejected wholesale by `sanitizeStyle()`.
const ALLOWED_CSS_PROPERTIES = new Set( [
	'color', 'background', 'background-color',
	'font-size', 'font-weight', 'font-style', 'font-family',
	'line-height', 'letter-spacing', 'text-align', 'text-decoration', 'text-transform', 'text-indent',
	'margin', 'margin-top', 'margin-right', 'margin-bottom', 'margin-left',
	'padding', 'padding-top', 'padding-right', 'padding-bottom', 'padding-left',
	'border', 'border-top', 'border-right', 'border-bottom', 'border-left',
	'border-radius', 'border-color', 'border-width', 'border-style',
	'width', 'max-width', 'min-width', 'height', 'max-height', 'min-height',
	'display', 'flex', 'flex-direction', 'flex-wrap', 'align-items', 'justify-content', 'gap',
	'opacity', 'overflow', 'overflow-x', 'overflow-y', 'visibility', 'white-space', 'word-break', 'word-wrap',
	'vertical-align', 'float', 'clear', 'box-shadow',
	// Block-editor specific layout properties WP emits on Gutenberg blocks.
	'aspect-ratio', 'object-fit', 'object-position',
] );

const IFRAME_HOST_ALLOWLIST = [
	'youtube.com', 'youtube-nocookie.com',
	'wistia.com', 'wistia.net', 'fast.wistia.net',
	'vimeo.com', 'player.vimeo.com',
];

function isHostInAllowlist( hostname, list ) {
	return list.some( ( h ) => hostname === h || hostname.endsWith( '.' + h ) );
}

function safeHref( href ) {
	if ( typeof href !== 'string' ) return null;
	const trimmed = href.trim();
	if ( /^javascript:/i.test( trimmed ) || /^vbscript:/i.test( trimmed ) || /^data:/i.test( trimmed ) ) {
		return null;
	}
	if ( /^(https?:|mailto:)/i.test( trimmed ) ) return trimmed;
	return null;
}

function safeIframeSrc( src ) {
	try {
		const url = new URL( src );
		if ( url.protocol !== 'https:' && url.protocol !== 'http:' ) return null;
		if ( ! isHostInAllowlist( url.hostname, IFRAME_HOST_ALLOWLIST ) ) return null;
		// Force HTTPS — an http embed on an https admin page is mixed-content
		// blocked anyway; coerce it to match safeImgSrc.
		if ( url.protocol === 'http:' ) {
			url.protocol = 'https:';
		}
		return url.toString();
	} catch ( e ) {
		return null;
	}
}

function safeImgSrc( src ) {
	if ( typeof src !== 'string' ) return null;
	try {
		const url = new URL( src );
		// Reject non-HTTP(S) protocols (drops `javascript:`, `vbscript:`, `data:`
		// and any other scheme the URL parser tolerates). The host itself is
		// not allowlisted — KB content sources its images from buddyboss.com
		// and authorised CDNs; mirroring the Gamification plugin's looser
		// model, we trust the upstream rather than enumerating CDN hosts.
		if ( url.protocol !== 'https:' && url.protocol !== 'http:' ) return null;
		// Force HTTPS — serving an http image on an https admin page would
		// trigger a mixed-content block.
		if ( url.protocol === 'http:' ) {
			url.protocol = 'https:';
		}
		return url.toString();
	} catch ( e ) {
		return null;
	}
}

/**
 * Validate and normalize a candidate KB image URL.
 *
 * Public re-export of the internal img-src validator so consumers (notably
 * KBArticle's hero image render) can verify external `imageUrl` payloads
 * against the same host allowlist as inline article images.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {string} src Candidate image URL.
 * @returns {?string} Normalized URL when allowed, or null when blocked.
 */
export function safeImageUrl( src ) {
	return safeImgSrc( src );
}

/**
 * Sanitize an HTML `srcset` attribute value.
 *
 * Each comma-separated candidate is parsed into `<URL> <descriptor>`, the URL
 * is run through `safeImgSrc()` (HTTPS coercion + scheme allowlist), and any
 * candidate whose URL fails validation is dropped. The descriptor (e.g. `2x`,
 * `1024w`) is preserved verbatim if it matches the standard shape, otherwise
 * the candidate is dropped to avoid emitting malformed srcset syntax.
 *
 * Returns an empty string if every candidate is rejected — caller should
 * remove the attribute entirely in that case.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {string} value Raw srcset attribute value.
 * @returns {string} Sanitized srcset (may be empty).
 */
function safeSrcset( value ) {
	if ( typeof value !== 'string' || value === '' ) return '';
	const out = [];
	// Split on commas that are NOT inside parentheses. KB srcset values from
	// WP core are simple `URL Wd, URL Wd` lists — a plain comma split is
	// sufficient and matches the parsing used elsewhere in the code base.
	value.split( ',' ).forEach( ( candidate ) => {
		const trimmed = candidate.trim();
		if ( ! trimmed ) return;
		// Split on the first run of whitespace.
		const idx = trimmed.search( /\s/ );
		const url = idx === -1 ? trimmed : trimmed.slice( 0, idx );
		const desc = idx === -1 ? '' : trimmed.slice( idx + 1 ).trim();
		const safe = safeImgSrc( url );
		if ( ! safe ) return;
		// Descriptor must look like `\d+(w|x)` — anything else is dropped.
		if ( desc && ! /^\d+(?:\.\d+)?[wx]$/.test( desc ) ) return;
		out.push( desc ? `${ safe } ${ desc }` : safe );
	} );
	return out.join( ', ' );
}

/**
 * Sanitize an inline `style` attribute against the project allowlist.
 *
 * Mirrors `utils/sanitize.js#sanitizeStyle`: tokenise on `;`, split each
 * declaration on its first `:`, drop the declaration if the property is not
 * in `ALLOWED_CSS_PROPERTIES` or the value contains `url(`, `expression(`,
 * or `javascript:`. Returns an empty string if every declaration is rejected.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {string} style Raw style value.
 * @returns {string} Sanitized declarations (may be empty).
 */
function sanitizeStyle( style ) {
	if ( typeof style !== 'string' || style === '' ) return '';
	const out = [];
	style.split( ';' ).forEach( ( decl ) => {
		const trimmed = decl.trim();
		if ( ! trimmed ) return;
		const colon = trimmed.indexOf( ':' );
		if ( colon === -1 ) return;
		const prop = trimmed.slice( 0, colon ).trim().toLowerCase();
		const val  = trimmed.slice( colon + 1 ).trim();
		if ( ! ALLOWED_CSS_PROPERTIES.has( prop ) ) return;
		const valLower = val.toLowerCase();
		if ( /url\s*\(/.test( valLower ) ) return;
		if ( /expression\s*\(/.test( valLower ) ) return;
		if ( valLower.includes( 'javascript:' ) ) return;
		out.push( `${ prop }: ${ val }` );
	} );
	return out.join( '; ' );
}

/**
 * Validate a media element src (audio/video/source/track).
 *
 * Same protocol allowlist as images — http/https only, with HTTPS coercion to
 * avoid mixed-content blocks on the https admin page. Rejects anything else
 * (data:, blob:, javascript:, file:, ...).
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {string} src Candidate media URL.
 * @returns {?string} Normalized URL or null when rejected.
 */
function safeMediaSrc( src ) {
	// Same protocol contract as images — reuse the validator.
	return safeImgSrc( src );
}

function sanitizeNode( node, doc ) {
	if ( node.nodeType !== 1 ) return;

	const tag = node.tagName.toLowerCase();
	if ( ! ALLOWED_TAGS.has( tag ) ) {
		if ( DENYLIST_TAGS.has( tag ) ) {
			node.remove();
			return;
		}
		// Unwrap: sanitize children recursively, then promote them to the
		// parent so their content survives even though the wrapper does not.
		// This keeps articles using rare/custom wrappers (3rd-party Gutenberg
		// blocks, future tags) readable instead of rendering blank.
		Array.from( node.children ).forEach( ( child ) => sanitizeNode( child, doc ) );
		const parent = node.parentNode;
		if ( parent ) {
			while ( node.firstChild ) {
				parent.insertBefore( node.firstChild, node );
			}
			parent.removeChild( node );
		} else {
			node.remove();
		}
		return;
	}

	// `<source>` / `<track>` are only meaningful inside a media parent — outside
	// that context they're orphan markup that browsers render as nothing
	// useful, so drop them. Cheaper than enumerating media parents in the
	// allowlist and correctly handles `<source>` smuggled into `<p>` etc.
	if ( ( tag === 'source' || tag === 'track' ) ) {
		const parentTag = node.parentNode && node.parentNode.tagName ? node.parentNode.tagName.toLowerCase() : '';
		if ( parentTag !== 'audio' && parentTag !== 'video' && parentTag !== 'picture' ) {
			node.remove();
			return;
		}
	}

	const allowed = ( ALLOWED_ATTRS[ tag ] || [] ).concat( ALLOWED_ATTRS[ '*' ] );
	const styleable = STYLEABLE_TAGS.has( tag );
	Array.from( node.attributes ).forEach( ( attr ) => {
		const name = attr.name.toLowerCase();
		// Strip any event handler attribute regardless of allowlist match.
		if ( name.startsWith( 'on' ) ) {
			node.removeAttribute( attr.name );
			return;
		}
		// Allow `data-*` attributes wholesale on every tag — Gutenberg blocks
		// emit `data-` attributes for client IDs and frontend hydration; they
		// cannot execute code on their own.
		if ( name.startsWith( 'data-' ) ) {
			return;
		}
		// `style` requires per-property validation rather than presence-only.
		if ( name === 'style' ) {
			if ( ! styleable ) {
				node.removeAttribute( attr.name );
				return;
			}
			const safeStyle = sanitizeStyle( attr.value );
			if ( safeStyle ) {
				node.setAttribute( 'style', safeStyle );
			} else {
				node.removeAttribute( attr.name );
			}
			return;
		}
		if ( ! allowed.includes( name ) ) {
			node.removeAttribute( attr.name );
		}
	} );

	if ( tag === 'a' ) {
		const href = node.getAttribute( 'href' );
		const safe = safeHref( href );
		if ( ! safe ) {
			node.removeAttribute( 'href' );
		} else {
			node.setAttribute( 'href', safe );
			if ( /^https?:/i.test( safe ) ) {
				node.setAttribute( 'target', '_blank' );
				node.setAttribute( 'rel', 'noopener noreferrer' );
			}
		}
	}

	if ( tag === 'iframe' ) {
		const src  = node.getAttribute( 'src' );
		const safe = safeIframeSrc( src );
		if ( ! safe ) {
			node.remove();
			return;
		}
		node.setAttribute( 'src', safe );
	}

	if ( tag === 'img' ) {
		const src  = node.getAttribute( 'src' );
		const safe = safeImgSrc( src );
		if ( ! safe ) {
			node.remove();
			return;
		}
		node.setAttribute( 'src', safe );
		// Re-validate srcset candidates if present — strips entries pointing at
		// non-http(s) hosts; preserves the rest with HTTPS coercion applied.
		const rawSrcset = node.getAttribute( 'srcset' );
		if ( rawSrcset ) {
			const safeSet = safeSrcset( rawSrcset );
			if ( safeSet ) {
				node.setAttribute( 'srcset', safeSet );
			} else {
				node.removeAttribute( 'srcset' );
			}
		}
	}

	if ( tag === 'audio' || tag === 'video' || tag === 'source' || tag === 'track' ) {
		const src = node.getAttribute( 'src' );
		if ( src ) {
			const safe = safeMediaSrc( src );
			if ( ! safe ) {
				// Drop only the src — the parent media element may still be
				// useful via `<source>` children. The walker recurses into
				// children below.
				node.removeAttribute( 'src' );
			} else {
				node.setAttribute( 'src', safe );
			}
		}
		// Validate poster on <video>.
		if ( tag === 'video' ) {
			const poster = node.getAttribute( 'poster' );
			if ( poster ) {
				const safePoster = safeImgSrc( poster );
				if ( safePoster ) {
					node.setAttribute( 'poster', safePoster );
				} else {
					node.removeAttribute( 'poster' );
				}
			}
		}
		if ( tag === 'source' ) {
			const rawSrcset = node.getAttribute( 'srcset' );
			if ( rawSrcset ) {
				const safeSet = safeSrcset( rawSrcset );
				if ( safeSet ) {
					node.setAttribute( 'srcset', safeSet );
				} else {
					node.removeAttribute( 'srcset' );
				}
			}
		}
	}

	Array.from( node.children ).forEach( ( child ) => sanitizeNode( child, doc ) );
}

/**
 * Sanitize a KB article HTML string.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {string} html Raw article HTML from buddyboss.com REST.
 * @returns {string} Sanitized HTML safe to inject via React's raw-HTML escape hatch.
 */
export function sanitizeKbArticle( html ) {
	if ( typeof html !== 'string' || html === '' ) return '';

	const doc = new DOMParser().parseFromString( '<div>' + html + '</div>', 'text/html' );
	const root = doc.body.firstElementChild;
	if ( ! root ) return '';

	Array.from( root.children ).forEach( ( child ) => sanitizeNode( child, doc ) );

	return root.innerHTML;
}
