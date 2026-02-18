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
};

/**
 * Allowed URI schemes for href and src attributes.
 */
const ALLOWED_SCHEMES = ['http:', 'https:', 'mailto:'];

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
		if (!ALLOWED_TAGS.hasOwnProperty(tagName)) {
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
 * Check if a URL uses an allowed scheme.
 *
 * @param {string} url URL to validate.
 * @return {boolean} True if the URL scheme is allowed.
 */
function isAllowedUrl(url) {
	if (!url) {
		return false;
	}

	// Allow relative URLs
	if (url.startsWith('/') || url.startsWith('#') || url.startsWith('?')) {
		return true;
	}

	try {
		const parsed = new URL(url, window.location.origin);
		return ALLOWED_SCHEMES.includes(parsed.protocol);
	} catch (e) {
		return false;
	}
}
