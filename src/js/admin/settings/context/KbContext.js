/**
 * Backward-compat shim — the Knowledge Base context moved into the shared
 * admin-common layer. Re-exported from its old settings path so existing
 * consumers (e.g. the Help/KB search screen) keep resolving and share the
 * single KB context instance from `@bb/admin-common` — importing the moved
 * module directly would bundle a duplicate context and break `useKb()`.
 *
 * New code should import from `@bb/admin-common` directly.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

export { KbProvider, useKb } from '@bb/admin-common';
