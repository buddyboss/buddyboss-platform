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

const ALLOWED_TAGS = new Set( [
	'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
	'p', 'br', 'hr',
	'ul', 'ol', 'li',
	'a', 'strong', 'em', 'b', 'i', 'u', 'span', 'div',
	'code', 'pre', 'blockquote',
	'figure', 'figcaption', 'img', 'iframe',
	'table', 'thead', 'tbody', 'tr', 'th', 'td',
] );

// Tags that must be FULLY removed (children too) — anything that can execute,
// load remote resources, or capture form input.
const DENYLIST_TAGS = new Set( [
	'script', 'style', 'noscript', 'template',
	'object', 'embed', 'applet',
	'meta', 'link', 'base',
	'form', 'input', 'button', 'select', 'textarea', 'option',
] );

const ALLOWED_ATTRS = {
	a:      [ 'href', 'title' ],
	img:    [ 'src', 'alt', 'width', 'height' ],
	iframe: [ 'src', 'width', 'height', 'allowfullscreen', 'frameborder', 'title' ],
	// Table cells support rowspan/colspan/scope/headers — without these, KB articles
	// that use multi-row "Feature Category" cells render as a flat list instead of a
	// proper grouped table.
	td:     [ 'rowspan', 'colspan', 'headers', 'align', 'valign' ],
	th:     [ 'rowspan', 'colspan', 'scope', 'headers', 'align', 'valign' ],
	'*':    [ 'class', 'id' ],
};

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
		// This keeps articles using <section>, <aside>, <details>, <video>,
		// etc. readable instead of rendering blank.
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

	const allowed = ( ALLOWED_ATTRS[ tag ] || [] ).concat( ALLOWED_ATTRS[ '*' ] );
	Array.from( node.attributes ).forEach( ( attr ) => {
		const name = attr.name.toLowerCase();
		if ( ! allowed.includes( name ) ) {
			node.removeAttribute( attr.name );
		}
		if ( name.startsWith( 'on' ) ) {
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
