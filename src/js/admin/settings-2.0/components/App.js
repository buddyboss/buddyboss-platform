/**
 * BuddyBoss Admin Settings 2.0 - Main App Component
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss 3.0.0
 */

import { useState, useEffect } from '@wordpress/element';
import { Header } from './Header';
import { Router } from './Router';
import { ErrorBoundary } from './ErrorBoundary';

/**
 * Main App Component
 *
 * @returns {JSX.Element} App component
 */
export function App() {
	const [currentRoute, setCurrentRoute] = useState('/dashboard');
	const [isLoading, setIsLoading] = useState(true);

	useEffect(() => {
		// Handle legacy URL mapping on mount
		const urlParams = new URLSearchParams(window.location.search);
		const page = urlParams.get('page');
		const tab = urlParams.get('tab');
		const section = urlParams.get('section');
		const field = urlParams.get('field');

		// Default route for bb-settings page is the Features grid
		let route = '/settings';

		// New Settings 2.0 page - default to Features grid
		if (page === 'bb-settings') {
			// Check hash first for internal navigation
			const hash = window.location.hash.replace('#', '');
			if (hash) {
				route = hash;
			} else if (tab) {
				// Support ?page=bb-settings&tab=activity format
				route = `/settings/${tab}`;
				if (section) {
					route += `/${section}`;
				}
			} else {
				route = '/settings';
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

			if (section) {
				route += `/${section}`;
			}

			// Optionally update URL to clean format
			if (window.history.replaceState) {
				const newUrl =
					window.location.pathname +
					window.location.search
						.replace(/[?&]page=[^&]*/, '')
						.replace(/[?&]tab=[^&]*/, '')
						.replace(/[?&]section=[^&]*/, '')
						.replace(/[?&]field=[^&]*/, '') +
					`#${route}`;
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

		// Listen for hash changes
		const handleHashChange = () => {
			const hash = window.location.hash.replace('#', '');
			if (hash) {
				setCurrentRoute(hash);
			}
		};

		window.addEventListener('hashchange', handleHashChange);

		return () => {
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
		<ErrorBoundary>
			<div className="bb-admin-app">
				<Header onNavigate={setCurrentRoute} />
				<Router currentRoute={currentRoute} onNavigate={setCurrentRoute} />
			</div>
		</ErrorBoundary>
	);
}
