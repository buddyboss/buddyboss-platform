/**
 * BuddyBoss Admin Settings 2.0 - Router Component
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss 3.0.0
 */

import { lazy, Suspense, useState, useEffect } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import { getCachedFeatures, invalidateFeaturesCache, updateFeatureInCache } from './utils/ajax';
import { SettingsScreen } from './screens/SettingsScreen';
import { FeatureSettingsScreen } from './screens/FeatureSettingsScreen';

// Lazy load feature-specific screens (code splitting)
const ActivityListScreen = lazy(() => import('./screens/ActivityListScreen'));
const GroupsListScreen = lazy(() => import('./screens/GroupsListScreen'));

// Re-export for consumers that import from Router
export { invalidateFeaturesCache, updateFeatureInCache };

/**
 * Check if a feature is enabled
 */
function isFeatureEnabled(features, featureId) {
	if (!features || !Array.isArray(features)) {
		return true; // Default to true if we can't check
	}
	
	const feature = features.find((f) => f.id === featureId);
	if (!feature) {
		return true; // Feature not found in registry, allow access
	}
	
	return feature.status === 'active';
}

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
 * Feature Disabled Component
 *
 * @param {Object} props Component props
 * @param {string} props.featureId Feature ID
 * @param {string} props.featureLabel Feature label
 * @param {Function} props.onNavigate Navigation callback
 * @returns {JSX.Element} Feature disabled message
 */
function FeatureDisabled({ featureId, featureLabel, onNavigate }) {
	return (
		<div className="bb-admin-feature-disabled">
			<div className="bb-admin-feature-disabled__icon">
				<svg width="64" height="64" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
					<circle cx="12" cy="12" r="10" stroke="#E5E7EB" strokeWidth="2"/>
					<path d="M12 8V12M12 16H12.01" stroke="#9CA3AF" strokeWidth="2" strokeLinecap="round"/>
				</svg>
			</div>
			<h2 className="bb-admin-feature-disabled__title">
				{__('Feature Not Enabled', 'buddyboss')}
			</h2>
			<p className="bb-admin-feature-disabled__description">
				{sprintf(
					__('The %s feature is currently disabled. Enable it to access these settings.', 'buddyboss'),
					featureLabel || featureId
				)}
			</p>
			<div className="bb-admin-feature-disabled__actions">
				<Button
					variant="primary"
					onClick={() => onNavigate('/settings')}
				>
					{__('Go to Features', 'buddyboss')}
				</Button>
			</div>
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
	const [features, setFeatures] = useState(null);
	const [isCheckingFeatures, setIsCheckingFeatures] = useState(true);

	// Parse route
	const routeParts = currentRoute.split('/').filter(Boolean);
	const mainRoute = routeParts[0] || 'dashboard';

	// Check if current route needs feature verification
	const needsFeatureCheck = mainRoute === 'settings' || mainRoute === 'activity' || mainRoute === 'groups';

	// Load features on mount AND when navigating to routes that need feature check
	useEffect(() => {
		if (needsFeatureCheck) {
			setIsCheckingFeatures(true);
			// Always get fresh data from cache (which is updated by SettingsScreen)
			getCachedFeatures().then((data) => {
				setFeatures(data);
				setIsCheckingFeatures(false);
			}).catch(() => {
				setIsCheckingFeatures(false);
			});
		} else {
			setIsCheckingFeatures(false);
		}
	}, [currentRoute, needsFeatureCheck]);

	// Update URL with query parameters instead of hash
	// Format: admin.php?page=bb-settings&tab=feature&panel=panel_id
	// Hierarchy: Feature (tab) → Side Panel (panel) → Sections → Fields
	const urlParams = new URLSearchParams(window.location.search);
	const currentTab = urlParams.get('tab');
	const currentPanel = urlParams.get('panel');
	
	if (currentRoute === '/settings') {
		// Remove tab and panel params when on main settings page
		if (currentTab || currentPanel || window.location.hash) {
			urlParams.delete('tab');
			urlParams.delete('panel');
			const paramString = urlParams.toString();
			const cleanUrl = window.location.pathname + (paramString ? '?' + paramString : '');
			window.history.replaceState({}, '', cleanUrl);
		}
	} else if (mainRoute === 'settings' && routeParts[1]) {
		// Update URL with tab parameter for feature settings
		const newTab = routeParts[1];
		const newPanel = routeParts[2] || null;
		
		if (currentTab !== newTab || currentPanel !== newPanel || window.location.hash) {
			urlParams.set('tab', newTab);
			if (newPanel) {
				urlParams.set('panel', newPanel);
			} else {
				urlParams.delete('panel');
			}
			// Use query params (no hash)
			const newUrl = window.location.pathname + '?' + urlParams.toString();
			window.history.replaceState({}, '', newUrl);
		}
	}

	// Helper to get feature label
	const getFeatureLabel = (featureId) => {
		if (!features) return featureId;
		const feature = features.find((f) => f.id === featureId);
		return feature?.label || featureId;
	};

	// Route matching
	switch (mainRoute) {

		case 'settings':
			// Hierarchy: Feature (tab) → Side Panel (sidepanel) → Sections → Fields
			const featureId = routeParts[1];
			const sidePanelId = routeParts[2];

			if (featureId) {
				// Check if feature is enabled (wait for features to load)
				if (isCheckingFeatures) {
					return <LoadingSpinner />;
				}

				if (!isFeatureEnabled(features, featureId)) {
					return (
						<FeatureDisabled
							featureId={featureId}
							featureLabel={getFeatureLabel(featureId)}
							onNavigate={onNavigate}
						/>
					);
				}

				return (
					<FeatureSettingsScreen
						featureId={featureId}
						sidePanelId={sidePanelId}
						onNavigate={onNavigate}
					/>
				);
			}

			return <SettingsScreen onNavigate={onNavigate} />;

		case 'activity':
			// Check if activity feature is enabled
			if (isCheckingFeatures) {
				return <LoadingSpinner />;
			}

			if (!isFeatureEnabled(features, 'activity')) {
				return (
					<FeatureDisabled
						featureId="activity"
						featureLabel={getFeatureLabel('activity')}
						onNavigate={onNavigate}
					/>
				);
			}

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
			// Check if groups feature is enabled
			if (isCheckingFeatures) {
				return <LoadingSpinner />;
			}

			if (!isFeatureEnabled(features, 'groups')) {
				return (
					<FeatureDisabled
						featureId="groups"
						featureLabel={getFeatureLabel('groups')}
						onNavigate={onNavigate}
					/>
				);
			}

			if (routeParts[1] === 'all') {
				return (
					<Suspense fallback={<LoadingSpinner />}>
						<GroupsListScreen onNavigate={onNavigate} />
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