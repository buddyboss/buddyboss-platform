/**
 * BuddyBoss Admin Settings 2.0 - HTML Sanitization Utilities
 *
 * Provides safe HTML rendering by stripping dangerous elements/attributes.
 * Uses the browser's DOMParser for reliable parsing.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

/**
 * Allowed HTML tags and their permitted attributes for sanitized output.
 * Similar to WordPress wp_kses_post() but for the browser.
 */
const ALLOWED_TAGS = {
	a: ['href', 'target', 'rel', 'class', 'id', 'title'],
	b: ['class'],
	br: [],
	button: ['type', 'class', 'id', 'disabled'],
	code: ['class'],
	div: ['class', 'id', 'style'],
	em: ['class'],
	h1: ['class', 'id'],
	h2: ['class', 'id'],
	h3: ['class', 'id'],
	h4: ['class', 'id'],
	h5: ['class', 'id'],
	h6: ['class', 'id'],
	i: ['class'],
	img: ['src', 'alt', 'width', 'height', 'class', 'style'],
	input: ['type', 'name', 'value', 'checked', 'disabled', 'class', 'id', 'placeholder'],
	label: ['for', 'class'],
	li: ['class'],
	ol: ['class'],
	p: ['class', 'id', 'style'],
	pre: ['class'],
	select: ['name', 'class', 'id'],
	option: ['value', 'selected'],
	small: ['class'],
	span: ['class', 'id', 'style'],
	strong: ['class'],
	sub: ['class'],
	sup: ['class'],
	table: ['class'],
	tbody: ['class'],
	td: ['class', 'colspan', 'rowspan'],
	th: ['class', 'colspan', 'rowspan'],
	thead: ['class'],
	tr: ['class'],
	ul: ['class'],
	// SVG support — used for inline brand glyphs (e.g. social network
	// provider icons) where no BB icon font glyph is available. The
	// allowed attribute list excludes anything event-bearing or scriptable.
	// `xmlnsXlink` is reserved for `<use href>`-style references and is
	// not included; URL-bearing attrs aren't whitelisted to keep this path
	// purely visual.
	svg: ['xmlns', 'viewBox', 'width', 'height', 'fill', 'class', 'aria-hidden', 'role', 'preserveAspectRatio'],
	path: ['d', 'fill', 'stroke', 'stroke-width', 'stroke-linecap', 'stroke-linejoin', 'fill-rule', 'clip-rule', 'transform', 'opacity'],
	g: ['fill', 'stroke', 'transform', 'opacity', 'class', 'clip-path', 'mask'],
	circle: ['cx', 'cy', 'r', 'fill', 'stroke', 'stroke-width', 'transform', 'opacity'],
	rect: ['x', 'y', 'width', 'height', 'rx', 'ry', 'fill', 'stroke', 'stroke-width', 'transform', 'opacity'],
	polygon: ['points', 'fill', 'stroke', 'stroke-width', 'transform', 'opacity'],
	polyline: ['points', 'fill', 'stroke', 'stroke-width', 'transform', 'opacity'],
	line: ['x1', 'y1', 'x2', 'y2', 'stroke', 'stroke-width', 'transform', 'opacity'],
	ellipse: ['cx', 'cy', 'rx', 'ry', 'fill', 'stroke', 'stroke-width', 'transform', 'opacity'],
	// Gradient and clipping support so SVGs with `<defs>` (e.g. Instagram's
	// multi-stop brand gradient when it's used as a fallback) render fully
	// instead of collapsing to a flat fill.
	//
	// Keys are lowercase because sanitizeNode looks them up via
	// `child.tagName.toLowerCase()`. The DOM preserves the camelCase form
	// (`linearGradient`, `clipPath`) on the element itself, so attribute
	// lookups using the camelCase form continue to work. The values are
	// the SVG-spec attribute names with their canonical case.
	defs: [],
	lineargradient: ['id', 'x1', 'y1', 'x2', 'y2', 'gradientUnits', 'gradientTransform'],
	radialgradient: ['id', 'cx', 'cy', 'r', 'fx', 'fy', 'gradientUnits', 'gradientTransform'],
	stop: ['offset', 'stop-color', 'stop-opacity'],
	clippath: ['id', 'clipPathUnits'],
	mask: ['id', 'maskUnits', 'maskContentUnits', 'x', 'y', 'width', 'height'],
	title: [],
	desc: [],
};

/**
 * Allowed URI schemes for href and src attributes.
 */
const ALLOWED_SCHEMES = ['http:', 'https:', 'mailto:'];

/**
 * Allowed CSS properties for style attributes.
 * Prevents CSS injection (e.g. background:url(javascript:...)) by only
 * permitting known-safe layout and visual properties.
 */
const ALLOWED_CSS_PROPERTIES = [
	'color', 'background-color', 'background',
	'font-size', 'font-weight', 'font-style', 'font-family',
	'line-height', 'letter-spacing', 'text-align', 'text-decoration', 'text-transform',
	'margin', 'margin-top', 'margin-right', 'margin-bottom', 'margin-left',
	'padding', 'padding-top', 'padding-right', 'padding-bottom', 'padding-left',
	'border', 'border-radius', 'border-color', 'border-width', 'border-style',
	'width', 'max-width', 'min-width', 'height', 'max-height', 'min-height',
	'display', 'flex', 'flex-direction', 'flex-wrap', 'align-items', 'justify-content', 'gap',
	'opacity', 'overflow', 'visibility', 'white-space', 'word-break',
];

/**
 * Sanitize an HTML string by removing dangerous elements and attributes.
 *
 * @param {string} html Raw HTML string.
 * @return {string} Sanitized HTML string safe for rendering.
 */
export function sanitizeHtml(html) {
	if (!html || typeof html !== 'string') {
		return '';
	}

	const parser = new DOMParser();
	const doc = parser.parseFromString(html, 'text/html');

	sanitizeNode(doc.body);

	return doc.body.innerHTML;
}

/**
 * Recursively sanitize a DOM node and its children.
 *
 * @param {Node} node DOM node to sanitize.
 */
function sanitizeNode(node) {
	const children = Array.from(node.childNodes);

	for (const child of children) {
		if (child.nodeType === Node.TEXT_NODE) {
			continue;
		}

		if (child.nodeType !== Node.ELEMENT_NODE) {
			child.remove();
			continue;
		}

		const tagName = child.tagName.toLowerCase();

		// Remove disallowed tags entirely (script, style, iframe, object, embed, etc.)
		if (!Object.prototype.hasOwnProperty.call(ALLOWED_TAGS, tagName)) {
			child.remove();
			continue;
		}

		// Remove disallowed attributes
		const allowedAttrs = ALLOWED_TAGS[tagName];
		const attrs = Array.from(child.attributes);

		for (const attr of attrs) {
			if (!allowedAttrs.includes(attr.name)) {
				child.removeAttribute(attr.name);
				continue;
			}

			// Validate URL attributes
			if ('href' === attr.name || 'src' === attr.name) {
				if (!isAllowedUrl(attr.value)) {
					child.removeAttribute(attr.name);
				}
			}

			// Sanitize style attribute to prevent CSS injection.
			if ('style' === attr.name) {
				const safe = sanitizeStyle(attr.value);
				if (safe) {
					child.setAttribute('style', safe);
				} else {
					child.removeAttribute('style');
				}
			}

			// Strip event handler attributes (extra safety)
			if (attr.name.startsWith('on')) {
				child.removeAttribute(attr.name);
			}
		}

		// Enforce rel="noopener noreferrer" on links with target="_blank"
		if ('a' === tagName && '_blank' === child.getAttribute('target')) {
			child.setAttribute('rel', 'noopener noreferrer');
		}

		// Recurse into children
		sanitizeNode(child);
	}
}

/**
 * Sanitize a CSS style string by removing disallowed properties.
 *
 * Splits the style value by semicolon, keeps only properties whose name
 * appears in ALLOWED_CSS_PROPERTIES, and rejects values containing
 * url(), expression(), or javascript: to block injection vectors.
 *
 * @param {string} style Raw style attribute value.
 * @return {string} Sanitized style string (empty if nothing is safe).
 */
function sanitizeStyle(style) {
	if (!style || typeof style !== 'string') {
		return '';
	}

	const safe = [];

	for (const decl of style.split(';')) {
		const trimmed = decl.trim();
		if (!trimmed) {
			continue;
		}

		const colonIdx = trimmed.indexOf(':');
		if (-1 === colonIdx) {
			continue;
		}

		const prop = trimmed.substring(0, colonIdx).trim().toLowerCase();
		const val = trimmed.substring(colonIdx + 1).trim();

		// Only allow known-safe CSS properties.
		if (-1 === ALLOWED_CSS_PROPERTIES.indexOf(prop)) {
			continue;
		}

		// Block dangerous CSS values (url(), expression(), javascript:).
		const valLower = val.toLowerCase();
		if (/url\s*\(/.test(valLower) || /expression\s*\(/.test(valLower) || valLower.indexOf('javascript:') !== -1) {
			continue;
		}

		safe.push(prop + ': ' + val);
	}

	return safe.join('; ');
}

/**
 * Check if a URL uses an allowed scheme.
 *
 * @param {string} url URL to validate.
 * @return {boolean} True if the URL scheme is allowed.
 */
function isAllowedUrl(url) {
	if (!url) {
		return false;
	}

	// Allow root-relative, fragment and query URLs. A single leading slash is
	// relative; a protocol-relative '//host/path' must NOT short-circuit here —
	// it navigates cross-origin, so let it fall through to the scheme allowlist.
	if (
		( url.startsWith('/') && ! url.startsWith('//') ) ||
		url.startsWith('#') ||
		url.startsWith('?')
	) {
		return true;
	}

	try {
		const parsed = new URL(url, window.location.origin);
		return ALLOWED_SCHEMES.includes(parsed.protocol);
	} catch (e) {
		return false;
	}
}

/**
 * Sanitize a URL for use in an href attribute.
 *
 * Returns the URL unchanged if its scheme is allowed (http, https, mailto,
 * or relative). Returns '#' for any disallowed or malformed URL.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {string} url URL to sanitize.
 * @return {string} Safe URL string.
 */
export function safeUrl(url) {
	if (!url || typeof url !== 'string') {
		return '#';
	}
	return isAllowedUrl(url) ? url : '#';
}

/**
 * Sanitize custom column HTML for a list of items.
 *
 * Each item may contain a `custom_columns` object whose values are raw HTML
 * from server-registered column callbacks. This function sanitizes each value
 * via sanitizeHtml() and returns a new array with sanitized columns.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {Array} items Array of item objects (forums, discussions, replies, etc.).
 * @returns {Array} New array with sanitized custom_columns.
 */
export function sanitizeCustomColumns( items ) {
	return items.map( function ( item ) {
		if ( ! item.custom_columns ) {
			return item;
		}
		const sanitizedCols = {};
		Object.keys( item.custom_columns ).forEach( function ( key ) {
			sanitizedCols[ key ] = sanitizeHtml( item.custom_columns[ key ] );
		} );
		return Object.assign( {}, item, { custom_columns: sanitizedCols } );
	} );
}
