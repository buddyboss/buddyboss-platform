import { __ } from '@wordpress/i18n';
import { applyFilters } from '@wordpress/hooks';

/**
 * Curated icon, title, description, and ordering for the six known top-level
 * KB categories. Used by KBLanding to render the Figma-defined cards 1:1 for
 * known slugs and fall back to a generic icon for any new top-level
 * categories buddyboss.com adds in the future.
 *
 * Filterable so a site can override card metadata (re-skin a card, add a
 * curated entry for a new top-level category, etc.) without forking the
 * bundle.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @return {Object<string, {icon: string, title: string, description: string, order: number}>}
 */
export function getCuratedOverrides() {
	const defaults = {
		'buddyboss-theme-and-platform': {
			icon:        'bb-icons-rl-browser',
			order:       1,
		},
		'buddyboss-platform': {
			icon:        'bb-icons-rl-app-window',
			title:       __( 'BuddyBoss Platform', 'buddyboss' ),
			description: __( 'Learn how to enable and configure the BuddyBoss Platform – including profiles, groups, activity, forums and more.', 'buddyboss' ),
			order:       1,
		},
		'buddyboss-theme': {
			icon:        'bb-icons-rl-palette',
			title:       __( 'BuddyBoss Theme', 'buddyboss' ),
			description: __( 'Learn how to setup and customize our premium BuddyBoss Theme to make everything look beautiful.', 'buddyboss' ),
			order:       2,
		},
		'buddyboss-app': {
			icon:        'bb-icons-rl-device-mobile',
			title:       __( 'BuddyBoss App', 'buddyboss' ),
			description: __( 'Learn how to set up the BuddyBoss App from scratch, including initial setup, branding, generating builds and publishing.', 'buddyboss' ),
			order:       3,
		},
		'integrations': {
			icon:        'bb-icons-rl-plug',
			title:       __( 'Integrations', 'buddyboss' ),
			description: __( 'LearnDash, Zoom, WooCommerce, Events, Jobs and more. Learn how BuddyBoss integrates with your favorite plugins and services.', 'buddyboss' ),
			order:       4,
		},
		'advanced-setup': {
			icon:        'bb-icons-rl-gear',
			title:       __( 'Advanced Setup', 'buddyboss' ),
			description: __( 'Articles for experienced developers and site administrators to optimize and extend their BuddyBoss sites.', 'buddyboss' ),
			order:       5,
		},
		'troubleshooting': {
			icon:        'bb-icons-rl-warning-circle',
			title:       __( 'Troubleshooting', 'buddyboss' ),
			description: __( 'Running into issues? Learn how to resolve the most common issues with BuddyBoss.', 'buddyboss' ),
			order:       6,
		},
	};

	/**
	 * Filter the curated icon/title/description/order map for the KB landing.
	 *
	 * Keyed by top-level category slug. A returned entry overrides the
	 * default; an entry for an unknown slug shows up as a curated card too.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {Object<string, {icon: string, title: string, description: string, order: number}>} defaults
	 *        The bundled curated map.
	 */
	return applyFilters( 'bb.admin.kb.curatedOverrides', defaults );
}
