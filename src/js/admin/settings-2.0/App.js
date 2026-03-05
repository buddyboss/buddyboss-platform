/**
 * BuddyBoss Admin Settings 2.0 - Main App Component
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

import { useState, useEffect } from '@wordpress/element';
import { Header } from './components/Header';
import { Router } from './Router';

/**
 * Main App Component
 *
 * @returns {JSX.Element} App component
 */
/**
 * Fix WordPress admin sidebar menu highlighting for Settings 2.0.
 *
 * Listing panels (All Groups, Group Types, Group Navigation, All Activities)
 * highlight the corresponding component menu (Groups, Activity).
 * Settings panels highlight the legacy "Settings" menu item.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {string} route Current React route (e.g. '/settings/groups/all_groups').
 */
function fixAdminMenuHighlight( route ) {
	var bbMenu = document.getElementById( 'toplevel_page_buddyboss-platform' );
	if ( ! bbMenu ) {
		return;
	}

	// Parse route: /settings/groups/all_groups → ['settings','groups','all_groups'].
	var parts = route.replace( /^\//, '' ).split( '/' );
	var mainRoute = parts[0] || '';
	var feature = ( 'settings' === mainRoute ) ? ( parts[1] || '' ) : mainRoute;
	var panel = ( 'settings' === mainRoute ) ? ( parts[2] || '' ) : ( parts[1] || '' );

	// Panels that represent listings → highlight the component menu item.
	// Settings panels → highlight "Settings" menu item.
	var listingPanels = {
		groups: [ 'all_groups', 'group_types', 'group_navigation' ],
		activity: [ 'all_activities' ],
	};

	var isListing = false;
	if ( listingPanels[ feature ] ) {
		isListing = -1 !== listingPanels[ feature ].indexOf( panel );
	}
	// Direct component routes like /activity/all or /groups/all.
	if ( 'settings' !== mainRoute && ( 'activity' === mainRoute || 'groups' === mainRoute ) ) {
		isListing = true;
	}

	// Determine which slug to target: component menu or Settings.
	var targetSlug = isListing ? feature : 'settings';

	var submenuItems = bbMenu.querySelectorAll( 'ul.wp-submenu li' );
	var targetItem = null;
	var settingsItem = null;
	var settings20Item = null;

	submenuItems.forEach( function( li ) {
		var a = li.querySelector( 'a' );
		if ( ! a ) {
			return;
		}
		var href = a.getAttribute( 'href' ) || '';

		// Track legacy Settings (bp-settings) and Settings 2.0 (bb-settings).
		if ( -1 !== href.indexOf( 'page=bp-settings' ) ) {
			settingsItem = li;
		}
		if ( 'admin.php?page=bb-settings' === href ) {
			settings20Item = li;
		}

		if ( 'settings' === targetSlug ) {
			return;
		}

		// Match submenu items by page slug (e.g. page=bp-groups).
		if ( -1 !== href.indexOf( 'page=bp-' + targetSlug ) ) {
			targetItem = li;
		}

		// Match submenu items using Settings 2.0 URL with tab param (e.g. tab=activity).
		if ( ! targetItem && -1 !== href.indexOf( 'tab=' + targetSlug ) ) {
			targetItem = li;
		}
	} );

	// For settings panels, prefer legacy "Settings" menu item (visible near top).
	if ( 'settings' === targetSlug ) {
		targetItem = settingsItem || settings20Item;
	}

	// Fallback to Settings 2.0 if nothing matched.
	if ( ! targetItem ) {
		targetItem = settings20Item || settingsItem;
	}

	if ( ! targetItem ) {
		return;
	}

	// Clear current from all, set on target.
	submenuItems.forEach( function( li ) {
		li.classList.remove( 'current' );
		var a = li.querySelector( 'a' );
		if ( a ) {
			a.classList.remove( 'current' );
			a.removeAttribute( 'aria-current' );
		}
	} );

	targetItem.classList.add( 'current' );
	var targetLink = targetItem.querySelector( 'a' );
	if ( targetLink ) {
		targetLink.classList.add( 'current' );
		targetLink.setAttribute( 'aria-current', 'page' );
	}
}

export function App() {
	const [currentRoute, setCurrentRoute] = useState('/settings');
	const [isLoading, setIsLoading] = useState(true);

	// Fix admin sidebar menu highlighting on mount and route changes.
	useEffect(() => {
		fixAdminMenuHighlight( currentRoute );
	}, [currentRoute]);

	useEffect(() => {
		// Handle legacy URL mapping on mount
		const urlParams = new URLSearchParams(window.location.search);
		const page = urlParams.get('page');
		const tab = urlParams.get('tab');
		const panel = urlParams.get('panel');
		const field = urlParams.get('field');

		// Default route for bb-settings page is the Features grid
		let route = '/settings';

		// New Settings 2.0 page - default to Features grid
		// URL format: admin.php?page=bb-settings&tab=feature&panel=panel_id
		// Hierarchy: Feature (tab) → Side Panel (panel) → Sections → Fields
		if ( 'bb-settings' === page ) {
			// Prioritize query params for settings routes
			if (tab) {
				// Support ?page=bb-settings&tab=reactions&panel=general format
				route = `/settings/${tab}`;
				if (panel) {
					route += `/${panel}`;
				}
			} else {
				// Check hash for backward compatibility
				const hash = window.location.hash.replace('#', '');
				if (hash) {
					route = hash;
				} else {
					route = '/settings';
				}
			}
		} else if ( 'bp-settings' === page && tab ) {
			// Legacy URL mapping for old settings pages
			const tabMap = {
				'bp-activity': 'activity',
				'bp-groups': 'groups',
				'bp-messages': 'messages',
				'bp-media': 'media',
				'bp-video': 'video',
				'bp-document': 'document',
				'bp-forums': 'forums',
				'bp-friends': 'friends',
				'bp-notifications': 'notifications',
				'bp-invites': 'invites',
				'bp-moderation': 'moderation',
				'bp-search': 'search',
				'bp-xprofile': 'xprofile',
				'bp-registration': 'registration',
				'bp-performance': 'performance',
				'bp-general': 'general',
				'bp-credit': 'credit',
				'bp-labs': 'labs',
			};

			const featureId = tabMap[tab] || tab.replace('bp-', '');
			route = `/settings/${featureId}`;

			if (panel) {
				route += `/${panel}`;
			}

			// Redirect to new URL format with query params
			if (window.history.replaceState) {
				const newParams = new URLSearchParams();
				newParams.set('page', 'bb-settings');
				newParams.set('tab', featureId);
				if (panel) {
					newParams.set('panel', panel);
				}
				const newUrl = window.location.pathname + '?' + newParams.toString();
				window.history.replaceState({}, '', newUrl);
			}
		} else if ( 'bp-activity' === page ) {
			route = '/activity/all';
		} else if ( 'bp-groups' === page ) {
			const gid = urlParams.get('gid');
			const action = urlParams.get('action');
			if ( gid && 'edit' === action ) {
				route = `/groups/${gid}/edit`;
			} else {
				route = '/groups/all';
			}
		} else if ( 'bp-components' === page ) {
			route = '/settings';
		} else if ( 'bp-integrations' === page ) {
			route = '/settings';
		} else {
			// Check hash routing
			const hash = window.location.hash.replace('#', '');
			if (hash) {
				route = hash;
			}
		}

		setCurrentRoute(route);
		setIsLoading(false);

		// Listen for browser back/forward navigation
		const handlePopState = () => {
			const params = new URLSearchParams(window.location.search);
			const currentPage = params.get('page');
			const currentTab = params.get('tab');
			const currentPanel = params.get('panel');

			if ( 'bb-settings' === currentPage ) {
				if (currentTab) {
					let newRoute = `/settings/${currentTab}`;
					if (currentPanel) {
						newRoute += `/${currentPanel}`;
					}
					setCurrentRoute(newRoute);
				} else {
					// Check hash for backward compatibility
					const hash = window.location.hash.replace('#', '');
					if (hash) {
						setCurrentRoute(hash);
					} else {
						setCurrentRoute('/settings');
					}
				}
			}
		};

		// Also listen for hash changes for backward compatibility
		const handleHashChange = () => {
			const hash = window.location.hash.replace('#', '');
			if (hash) {
				setCurrentRoute(hash);
			}
		};

		window.addEventListener('popstate', handlePopState);
		window.addEventListener('hashchange', handleHashChange);

		return () => {
			window.removeEventListener('popstate', handlePopState);
			window.removeEventListener('hashchange', handleHashChange);
		};
	}, []);

	if (isLoading) {
		return (
			<div className="bb-admin-loading">
				<span className="spinner is-active"></span>
			</div>
		);
	}

	return (
		<div className="bb-admin-app">
			<Header onNavigate={setCurrentRoute} />
			<Router currentRoute={currentRoute} onNavigate={setCurrentRoute} />
		</div>
	);
}