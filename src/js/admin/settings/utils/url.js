/**
 * BuddyBoss Admin URL Utilities
 *
 * URL format: admin.php?page=bb-settings&tab={feature}&panel={panel_id}
 * Hierarchy: Feature (tab) → Side Panel (panel) → Sections → Fields
 *
 * @package BuddyBoss
 * @since BuddyBoss [BBVERSION]
 */

/**
 * Get the base admin URL for settings
 *
 * @return {string} Base admin URL
 */
export function getAdminBaseUrl() {
	return window.bbAdminData?.adminUrl || '/wp-admin/admin.php';
}

/**
 * Convert internal route to URL query parameters
 *
 * Examples:
 * - '/settings' -> 'admin.php?page=bb-settings'
 * - '/settings/reactions' -> 'admin.php?page=bb-settings&tab=reactions'
 * - '/settings/activity/posts' -> 'admin.php?page=bb-settings&tab=activity&panel=posts'
 *
 * @param {string} route Internal route (e.g., '/settings/reactions')
 * @return {string} Full URL with query parameters
 */
export function routeToUrl(route) {
	const baseUrl = getAdminBaseUrl();
	const routeParts = route.split('/').filter(Boolean);
	const mainRoute = routeParts[0] || 'settings';

	const params = new URLSearchParams();
	params.set('page', 'bb-settings');

	if ( 'settings' === mainRoute && routeParts[1] ) {
		params.set('tab', routeParts[1]);
		if (routeParts[2]) {
			params.set('panel', routeParts[2]);
		}
	}

	return `${baseUrl}?${params.toString()}`;
}

/**
 * Convert URL to internal route
 *
 * Examples:
 * - 'admin.php?page=bb-settings' -> '/settings'
 * - 'admin.php?page=bb-settings&tab=reactions' -> '/settings/reactions'
 * - 'admin.php?page=bb-settings&tab=activity&panel=posts' -> '/settings/activity/posts'
 *
 * @param {string} url URL with query parameters
 * @return {string} Internal route
 */
export function urlToRoute(url) {
	let searchString = url;

	// Handle full URLs
	if (url.includes('?')) {
		searchString = url.split('?')[1];
	}

	const params = new URLSearchParams(searchString);
	const page = params.get('page');
	const tab = params.get('tab');
	const panel = params.get('panel');

	if (page !== 'bb-settings') {
		return '/settings';
	}

	let route = '/settings';
	if (tab) {
		route += `/${tab}`;
		if (panel) {
			route += `/${panel}`;
		}
	}

	return route;
}

/**
 * Get URL for feature settings
 *
 * @param {string} featureId   Feature ID (e.g., 'reactions', 'activity')
 * @param {string} sidePanelId Optional side panel ID
 * @return {string} Full URL for feature settings
 */
export function getFeatureSettingsUrl(featureId, sidePanelId = null) {
	const baseUrl = getAdminBaseUrl();
	const params = new URLSearchParams();
	params.set('page', 'bb-settings');
	params.set('tab', featureId);

	if (sidePanelId) {
		params.set('panel', sidePanelId);
	}

	return `${baseUrl}?${params.toString()}`;
}

/**
 * Get URL for main settings page (Features grid)
 *
 * @return {string} URL for main settings page
 */
export function getSettingsUrl() {
	const baseUrl = getAdminBaseUrl();
	return `${baseUrl}?page=bb-settings`;
}

/**
 * Extract feature ID from route
 *
 * @param {string} route Internal route (e.g., '/settings/reactions')
 * @return {string|null} Feature ID or null
 */
export function getFeatureIdFromRoute(route) {
	const routeParts = route.split('/').filter(Boolean);
	if (routeParts[0] === 'settings' && routeParts[1]) {
		return routeParts[1];
	}
	return null;
}

/**
 * Extract side panel ID from route
 *
 * @param {string} route Internal route (e.g., '/settings/activity/posts')
 * @return {string|null} Side panel ID or null
 */
export function getSidePanelIdFromRoute(route) {
	const routeParts = route.split('/').filter(Boolean);
	if (routeParts[0] === 'settings' && routeParts[2]) {
		return routeParts[2];
	}
	return null;
}

/**
 * Build internal route from feature and side panel IDs
 *
 * @param {string} featureId   Feature ID
 * @param {string} sidePanelId Optional side panel ID
 * @return {string} Internal route
 */
export function buildRoute(featureId, sidePanelId = null) {
	if (!featureId) {
		return '/settings';
	}

	let route = `/settings/${featureId}`;
	if (sidePanelId) {
		route += `/${sidePanelId}`;
	}

	return route;
}

/**
 * Check if current URL matches a route
 *
 * @param {string} route Internal route to check
 * @return {boolean} True if URL matches route
 */
export function isCurrentRoute(route) {
	const currentRoute = urlToRoute(window.location.search);
	return currentRoute === route;
}
