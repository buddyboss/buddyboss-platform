/**
 * BuddyBoss Admin Settings 2.0 - Main App Component
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss 3.0.0
 */

import { useState, useEffect } from '@wordpress/element';
import { Header } from './components/Header';
import { Router } from './Router';

/**
 * Main App Component
 *
 * @returns {JSX.Element} App component
 */
export function App() {
	const [currentRoute, setCurrentRoute] = useState('/settings');
	const [isLoading, setIsLoading] = useState(true);

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
		if (page === 'bb-settings') {
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
		} else if (page === 'bp-settings' && tab) {
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
		} else if (page === 'bp-activity') {
			route = '/activity/all';
		} else if (page === 'bp-groups') {
			const gid = urlParams.get('gid');
			const action = urlParams.get('action');
			if (gid && action === 'edit') {
				route = `/groups/${gid}/edit`;
			} else {
				route = '/groups/all';
			}
		} else if (page === 'bp-components') {
			route = '/settings';
		} else if (page === 'bp-integrations') {
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

			if (currentPage === 'bb-settings') {
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