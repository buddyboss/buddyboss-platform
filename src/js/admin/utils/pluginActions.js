/**
 * BuddyBoss Integrations marketplace — plugin install/activate helpers.
 *
 * - Slug derivation from acf.plugin_link (only wordpress.org URLs are installable).
 * - install: WordPress core's wp.updates (its own nonce + install_plugins cap).
 * - activate / deactivate: POST to our nonce + capability-guarded admin-ajax
 *   handlers (BB_Admin_Integrations_Ajax), which resolve the plugin file from the
 *   slug server-side.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

/**
 * Localized plugin data: { installed, canInstall, canActivate, nonce, ajaxUrl }.
 *
 * @returns {Object} The localized object, or an empty object when absent (e.g.
 *                   the current user can neither install nor activate plugins).
 */
export const getPluginsData = () =>
	( typeof window !== 'undefined' && window.bbIntegrationsPlugins ) || {};

/**
 * Extract a wordpress.org plugin slug from a plugin link.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {string} link A plugin link (acf.plugin_link).
 * @returns {string|null} The slug, or null when the link is not a
 *                        wordpress.org/plugins/<slug>/ URL (not installable).
 */
export const wporgSlug = ( link ) => {
	if ( ! link || 'string' !== typeof link ) {
		return null;
	}
	const match = /^https?:\/\/wordpress\.org\/plugins\/([^/?#]+)/i.exec( link );
	return match ? match[ 1 ] : null;
};

/**
 * Install a wordpress.org plugin via core wp.updates.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {string} slug The wordpress.org plugin slug.
 * @returns {Promise<Object>} Resolves with the install response on success.
 */
export const installPlugin = ( slug ) =>
	new Promise( ( resolve, reject ) => {
		if ( ! window.wp || ! window.wp.updates || ! window.wp.updates.installPlugin ) {
			reject( new Error( 'WordPress updater is unavailable.' ) );
			return;
		}
		window.wp.updates.installPlugin( { slug, success: resolve, error: reject } );
	} );

/**
 * POST to one of our plugin-action AJAX endpoints.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {string} action admin-ajax action name.
 * @param {string} slug   The wordpress.org plugin slug.
 * @returns {Promise<Object>} Resolves with response.data ({ file, active }).
 */
const postAction = async ( action, slug ) => {
	const data = getPluginsData();
	const body = new FormData();
	body.append( 'action', action );
	body.append( 'nonce', data.nonce || '' );
	body.append( 'slug', slug );

	const response = await fetch( data.ajaxUrl, {
		method: 'POST',
		credentials: 'same-origin',
		body,
	} );
	const json = await response.json().catch( () => null );
	if ( ! json || ! json.success ) {
		throw new Error( ( json && json.data && json.data.message ) || 'Plugin action failed.' );
	}
	return json.data;
};

/**
 * Activate an installed plugin by slug.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {string} slug The wordpress.org plugin slug.
 * @returns {Promise<Object>} Resolves with { file, active: true }.
 */
export const activatePlugin = ( slug ) => postAction( 'bb_integrations_activate_plugin', slug );

/**
 * Deactivate an installed plugin by slug.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {string} slug The wordpress.org plugin slug.
 * @returns {Promise<Object>} Resolves with { file, active: false }.
 */
export const deactivatePlugin = ( slug ) => postAction( 'bb_integrations_deactivate_plugin', slug );
