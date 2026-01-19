/**
 * BuddyBoss Admin Settings 2.0 - Router Component
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss 3.0.0
 */

import { lazy, Suspense } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { DashboardScreen } from './screens/DashboardScreen';
import { SettingsScreen } from './screens/SettingsScreen';
import { FeatureSettingsScreen } from './screens/FeatureSettingsScreen';

// Lazy load feature-specific screens (code splitting)
const ActivityListScreen = lazy(() => import('./screens/ActivityListScreen'));
const GroupsListScreen = lazy(() => import('./screens/GroupsListScreen'));
const GroupEditScreen = lazy(() => import('./screens/GroupEditScreen'));
const GroupTypeScreen = lazy(() => import('./screens/GroupTypeScreen'));
const GroupNavigationScreen = lazy(() => import('./screens/GroupNavigationScreen'));

/**
 * Loading Spinner Component
 *
 * @returns {JSX.Element} Loading spinner
 */
function LoadingSpinner() {
	return (
		<div className="bb-admin-loading">
			<span className="spinner is-active"></span>
		</div>
	);
}

/**
 * Router Component
 *
 * @param {Object} props Component props
 * @param {string} props.currentRoute Current route
 * @param {Function} props.onNavigate Navigation callback
 * @returns {JSX.Element} Router component
 */
export function Router({ currentRoute, onNavigate }) {
	// Parse route
	const routeParts = currentRoute.split('/').filter(Boolean);
	const mainRoute = routeParts[0] || 'dashboard';

	// Update hash in URL
	if (window.location.hash !== `#${currentRoute}`) {
		window.history.replaceState({}, '', `#${currentRoute}`);
	}

	// Route matching
	switch (mainRoute) {
		case 'dashboard':
			return <DashboardScreen />;

		case 'settings':
			const featureId = routeParts[1];
			const sectionId = routeParts[2];

			// Debug: Log routing
			console.log('Router: settings route', { featureId, sectionId, routeParts, currentRoute });

			if (featureId) {
				return (
					<FeatureSettingsScreen
						featureId={featureId}
						sectionId={sectionId}
						onNavigate={onNavigate}
					/>
				);
			}

			console.log('Router: Rendering SettingsScreen');
			return <SettingsScreen onNavigate={onNavigate} />;

		case 'activity':
			if (routeParts[1] === 'all') {
				return (
					<Suspense fallback={<LoadingSpinner />}>
						<ActivityListScreen onNavigate={onNavigate} />
					</Suspense>
				);
			}
			// Edit activity route
			if (routeParts[1] && routeParts[2] === 'edit') {
				const activityId = routeParts[1];
				return (
					<div className="bb-admin-activity-edit">
						<h1>{__('Edit Activity', 'buddyboss')}</h1>
						<p>{__('Activity edit screen - ID:', 'buddyboss')} {activityId}</p>
					</div>
				);
			}
			return <LoadingSpinner />;

		case 'groups':
			if (routeParts[1] === 'all') {
				return (
					<Suspense fallback={<LoadingSpinner />}>
						<GroupsListScreen onNavigate={onNavigate} />
					</Suspense>
				);
			}
			if (routeParts[1] === 'create') {
				// Redirect to /groups/all but open the create modal
				return (
					<Suspense fallback={<LoadingSpinner />}>
						<GroupsListScreen onNavigate={onNavigate} openCreateModal={true} />
					</Suspense>
				);
			}
			if (routeParts[1] && routeParts[2] === 'edit') {
				const groupId = routeParts[1];
				return (
					<Suspense fallback={<LoadingSpinner />}>
						<GroupEditScreen mode="edit" groupId={groupId} />
					</Suspense>
				);
			}
			if (routeParts[1] === 'types') {
				return (
					<Suspense fallback={<LoadingSpinner />}>
						<GroupTypeScreen onNavigate={onNavigate} />
					</Suspense>
				);
			}
			if (routeParts[1] === 'navigation') {
				return (
					<Suspense fallback={<LoadingSpinner />}>
						<GroupNavigationScreen onNavigate={onNavigate} />
					</Suspense>
				);
			}
			return <LoadingSpinner />;

		default:
			return (
				<div className="bb-admin-not-found">
					<h2>{__('Page not found', 'buddyboss')}</h2>
					<p>{__('The requested page could not be found.', 'buddyboss')}</p>
				</div>
			);
	}
}
