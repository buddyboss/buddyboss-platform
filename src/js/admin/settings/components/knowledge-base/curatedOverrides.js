/**
 * Backward-compat shim — the KB curated-overrides helper moved into the shared
 * admin-common layer. Re-exported from its old settings path so existing
 * consumers (e.g. the Help/KB search screen) keep resolving without bundling
 * a duplicate copy. New code should import from `@bb/admin-common` directly.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

export { getCuratedOverrides } from '@bb/admin-common';
