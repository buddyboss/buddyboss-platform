/**
 * BuddyBoss Knowledge Base — outbound URL helpers.
 *
 * Centralizes the two public buddyboss.com URLs the KB modal links out to
 * (full doc index for an article, full doc-category index for a category)
 * behind filters. A site can re-point either at a localized docs portal or
 * a self-hosted mirror without forking the JS bundle.
 *
 * Module-scoped helpers — call once per render via the result, not the
 * function reference, so React doesn't see a fresh string identity each
 * render.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

import { applyFilters } from '@wordpress/hooks';

const DEFAULT_DOCS_BASE_URL = 'https://buddyboss.com/docs/';
const DEFAULT_DOC_CATEGORIES_BASE_URL = 'https://buddyboss.com/doc-categories/';

/**
 * Get the public docs base URL — used for fallback "open on buddyboss.com"
 * links rendered when an article fails to load, is empty, or is missing.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @return {string} URL with trailing slash.
 */
export function getDocsBaseUrl() {
	/**
	 * Filter the public docs base URL.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {string} url The default `https://buddyboss.com/docs/` URL.
	 */
	return applyFilters( 'bb.admin.kb.docsBaseUrl', DEFAULT_DOCS_BASE_URL );
}

/**
 * Get the public doc-categories base URL — used for the sidebar "view all
 * articles in this category" link when the article count exceeds the page
 * cap.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @return {string} URL with trailing slash.
 */
export function getDocCategoriesBaseUrl() {
	/**
	 * Filter the public doc-categories base URL.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {string} url The default `https://buddyboss.com/doc-categories/` URL.
	 */
	return applyFilters( 'bb.admin.kb.docCategoriesBaseUrl', DEFAULT_DOC_CATEGORIES_BASE_URL );
}
