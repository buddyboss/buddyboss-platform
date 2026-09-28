/**
 * BuddyBoss ReadyLaunch Onboarding — Sidebar shape helpers.
 *
 * `bb_rl_activity_sidebars` / `bb_rl_groups_sidebars` /
 * `bb_rl_member_profile_sidebars` options persist as an associative map of
 * `{ widget_id: boolean }` once the Settings 2.0 admin has saved them — the
 * shape consumed by the frontend `readylaunch/sidebar/right-sidebar.php`
 * templates.
 *
 * The onboarding wizard's own `draggable` fields push a sequential
 * `[ widget_id, widget_id ]` array during the step flow. Before a save has
 * ever occurred, the preview panes see the array shape; after a save they
 * see the map. Use `bbRlSidebarIncludes()` on both preview and save paths so
 * a widget renders the same regardless of which shape the form currently
 * carries.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

/**
 * Check whether a sidebar widget ID is enabled in the given value.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {Array|Object|undefined} value     Raw sidebar value from form state or
 *                                           the persisted option. Undefined ->
 *                                           fall through so callers can default
 *                                           to "show everything".
 * @param {string}                 widgetId  Widget ID to test (e.g. 'complete_profile').
 * @returns {boolean} True when the widget is enabled, false otherwise.
 */
export function bbRlSidebarIncludes( value, widgetId ) {
	if ( Array.isArray( value ) ) {
		return value.indexOf( widgetId ) !== -1;
	}

	if ( value && typeof value === 'object' ) {
		return !! value[ widgetId ];
	}

	return false;
}
