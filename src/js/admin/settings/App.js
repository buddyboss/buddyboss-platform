/**
 * BuddyBoss Admin Settings 2.0 - Main App Component
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

import { useState, useEffect, lazy, Suspense } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Router } from './Router';
import { KbProvider, useKb, BBAdminHeader } from '@bb/admin-common';
import { ajaxFetch } from './utils/ajax';
import { clearHelpContentCache } from '../utils/api';

// Register integration-specific hooks that extend the shared bb_verify_popup
// field. Side-effect import — the file calls wp.hooks.addFilter/addAction at
// load time. Keep imports here, not inside components, so they register once.
import './components/recaptcha/recaptcha-verify-hooks';
import './components/pusher/pusher-verify-hooks';

// Knowledge Base modal now lives in the shared layer (@bb/admin-common) so the
// Settings and Integrations apps present the same help experience. It is an
// external (the shared bundle is already enqueued as a dependency), so the lazy
// import resolves from the in-memory global rather than a separate network
// chunk; the Suspense wrapper is retained for parity.
const KnowledgeBaseModal = lazy( () =>
	import( '@bb/admin-common' ).then( ( module ) => ( { default: module.KnowledgeBaseModal } ) )
);

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
		members: [ 'profile_fields', 'profile_types', 'profile_search', 'profile_navigation' ],
		forums: [ 'all_forums', 'discussions', 'discussion_tags', 'replies' ],
		emails: [ 'all_emails' ],
	};

	var isListing = false;
	if ( listingPanels[ feature ] ) {
		isListing = -1 !== listingPanels[ feature ].indexOf( panel );
	}
	// Direct component routes like /activity/all or /groups/all.
	if ( 'settings' !== mainRoute && ( 'activity' === mainRoute || 'groups' === mainRoute || 'forums' === mainRoute ) ) {
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

		// Map feature names to legacy submenu page slugs where they differ.
		var slugMap = {
			members: 'bp-profile-setup',
		};
		var pageSlug = slugMap[ targetSlug ] || ( 'bp-' + targetSlug );

		// Match submenu items by page slug (e.g. page=bp-groups).
		if ( -1 !== href.indexOf( 'page=' + pageSlug ) ) {
			targetItem = li;
		}

		// Match Forums submenu by CPT URL (edit.php?post_type=forum).
		if ( ! targetItem && 'forums' === targetSlug && -1 !== href.indexOf( 'post_type=forum' ) ) {
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

/**
 * Outer App component — kept intentionally thin so the entire admin shell
 * sits inside the `KbProvider` context. `AppInner` does the real work and
 * can therefore call `useKb()` to wire the Header trigger and modal mount.
 *
 * @returns {JSX.Element}
 */
export function App() {
	return (
		<KbProvider>
			<AppInner />
		</KbProvider>
	);
}

function AppInner() {
	const { open: openKb, state: kbState } = useKb();
	const [currentRoute, setCurrentRoute] = useState('/settings');
	const [isLoading, setIsLoading] = useState(true);

	// One-shot localStorage flush for the help-content cache.
	//
	// Pairs with `bb_maybe_clear_placeholder_features_cache()` on the PHP
	// side: when an admin hits `?bb_clear_placeholder_cache=1`, that handler
	// drops every `bb_help_content_*` server transient AND raises a one-shot
	// transient that `bb-admin-settings-page.php` reads-and-deletes into
	// `bbAdminData.helpContentCacheFlushSignal`. Without this effect the
	// 3-day localStorage entries written by `fetchHelpContent()` would keep
	// shadowing the freshly-cleared server cache on the very next help
	// slider open.
	//
	// Empty deps + no cleanup is intentional: the signal is already
	// one-shot at the source (deleted server-side after read), so this
	// fires exactly once per page load when the signal is present.
	useEffect(() => {
		if ( typeof window !== 'undefined' && window.bbAdminData && window.bbAdminData.helpContentCacheFlushSignal ) {
			clearHelpContentCache();
		}
	}, []);

	// Fix admin sidebar menu highlighting on mount and route changes.
	useEffect(() => {
		fixAdminMenuHighlight( currentRoute );
	}, [currentRoute]);

	// Scroll to top on every route change. The Settings 2.0 router is
	// homegrown (string-based currentRoute + history.replaceState) and has
	// no built-in scroll restoration the way React Router does, so a user
	// scrolled halfway down the features grid would keep that scroll
	// position when clicking a Settings button — landing them in the
	// middle of the feature settings page instead of at its top. Reset
	// scroll on every transition so each panel starts from its first
	// section. We scroll the window because the WP admin shell
	// (#wpbody-content) owns the scrollbar; the React tree is mounted
	// inside it, not in its own scroll container.
	useEffect(() => {
		if ( 'undefined' !== typeof window && 'function' === typeof window.scrollTo ) {
			window.scrollTo( 0, 0 );
		}
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
			<a href="#bb-admin-settings-main" className="screen-reader-shortcut">
				{ __( 'Skip to settings content', 'buddyboss' ) }
			</a>
			<BBAdminHeader
				logoUrl={ ( typeof bbAdminData !== 'undefined' && bbAdminData.logoUrl ) || '' }
				ipnRootId={ ( typeof bbAdminData !== 'undefined' && bbAdminData.ipnRootId ) || '' }
				onSearch={ ( query, signal ) =>
					ajaxFetch( 'bb_admin_search_settings', { query }, { signal } ).then(
						( response ) => ( response.success ? ( response.data?.results || [] ) : [] )
					)
				}
				onSelectResult={ ( result ) => setCurrentRoute( result.route ) }
				onHelp={ openKb }
			/>
			<div id="bb-admin-settings-main" tabIndex="-1">
				<Router currentRoute={currentRoute} onNavigate={setCurrentRoute} />
			</div>
			{ kbState.isOpen && (
				<Suspense fallback={null}>
					<KnowledgeBaseModal />
				</Suspense>
			) }
		</div>
	);
}