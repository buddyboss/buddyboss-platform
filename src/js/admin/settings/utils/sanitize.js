/**
 * Settings re-export shim for the shared sanitizer.
 *
 * The sanitizer now lives in the shared admin-common layer; this thin
 * re-export keeps the ~40 existing `../utils/sanitize` imports working while
 * the implementation ships once via @bb/admin-common (no per-bundle copy).
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

export { sanitizeHtml, safeUrl, sanitizeCustomColumns } from '@bb/admin-common';
