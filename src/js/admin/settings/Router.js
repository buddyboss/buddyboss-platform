/**
 * BuddyBoss Admin Settings 2.0 - Router Component
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

import { lazy, Suspense, useState, useEffect, useRef } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import { getCachedFeatures } from './utils/ajax';
import { SettingsScreen } from './screens/SettingsScreen';
import { FeatureSettingsScreen } from './screens/FeatureSettingsScreen';
import { HelpScreen } from './screens/HelpScreen';
import { SupportAccessScreen } from './screens/SupportAccessScreen';

// Lazy load feature-specific screens (code splitting)
const ActivityListScreen = lazy(() => import('./screens/ActivityListScreen'));
const GroupsListScreen = lazy(() => import('./screens/GroupsListScreen'));
const ForumsListScreen = lazy(() => import('./screens/ForumsListScreen'));

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
	
	return 'active' === feature.status;
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

	// Track whether features have been loaded at least once so subsequent
	// route changes do not flash a loading spinner (which unmounts children).
	const featuresLoadedRef = useRef(false);

	// Track whether the URL-sync effect has run before. The first run reflects
	// the route the page was loaded with — that URL is already in the address
	// bar, so we replace (not push) to avoid creating a synthetic history
	// entry. Subsequent runs are user-driven navigation and need pushState so
	// the browser Back button can traverse them.
	const hasSyncedUrlRef = useRef(false);

	// Parse route
	const routeParts = currentRoute.split('/').filter(Boolean);
	const mainRoute = routeParts[0] || 'dashboard';

	// Check if current route needs feature verification
	const needsFeatureCheck = 'settings' === mainRoute || 'activity' === mainRoute || 'groups' === mainRoute || 'forums' === mainRoute;

	// Load features on mount AND when navigating to routes that need feature check.
	// Only show the loading spinner on the initial load (before features are fetched
	// for the first time). On subsequent route changes we still refresh the features
	// list, but keep FeatureSettingsScreen mounted to avoid an unmount/remount cycle.
	useEffect(() => {
		if (needsFeatureCheck) {
			if (!featuresLoadedRef.current) {
				setIsCheckingFeatures(true);
			}
			// Always get fresh data from cache (which is updated by SettingsScreen)
			getCachedFeatures().then((data) => {
				featuresLoadedRef.current = true;
				setFeatures(data);
				setIsCheckingFeatures(false);
			}).catch(() => {
				setIsCheckingFeatures(false);
			});
		} else {
			setIsCheckingFeatures(false);
		}
	}, [currentRoute, needsFeatureCheck]);

	// Update URL with query parameters instead of hash.
	// Format: admin.php?page=bb-settings&tab=feature&panel=panel_id
	// Hierarchy: Feature (tab) → Side Panel (panel) → Sections → Fields
	// Note: routeParts/mainRoute are derived from currentRoute, so only
	// currentRoute is needed in the dependency array.
	useEffect(() => {
		var parts = currentRoute.split('/').filter(Boolean);
		var main = parts[0] || 'dashboard';

		const urlParams = new URLSearchParams(window.location.search);
		const currentTab = urlParams.get('tab');
		const currentPanel = urlParams.get('panel');

		// First run reflects the URL the page was loaded with, so any URL
		// rewrite here is a normalization (e.g. stripping an empty hash) and
		// must use replaceState. Subsequent runs are user-driven navigation
		// and must use pushState so the browser Back button can traverse the
		// in-app history. The popstate listener in App.js maps those entries
		// back to currentRoute.
		//
		// Exception: when the feature (tab) hasn't changed and only the panel
		// is being filled in, this is the FeatureSettingsScreen auto-selecting
		// its first side panel after data loads. That's a redirect, not a
		// navigation — pushing a history entry for it would trap the user in
		// a Back-button loop (Back → /settings/reactions → auto-redirects
		// forward to /settings/reactions/general again).
		const writeUrl = ( url, isAutoRedirect ) => {
			if ( hasSyncedUrlRef.current && ! isAutoRedirect ) {
				window.history.pushState({}, '', url);
			} else {
				window.history.replaceState({}, '', url);
			}
		};

		if ( '/settings' === currentRoute ) {
			// Remove tab and panel params when on main settings page.
			if (currentTab || currentPanel || window.location.hash) {
				urlParams.delete('tab');
				urlParams.delete('panel');
				const paramString = urlParams.toString();
				const cleanUrl = window.location.pathname + (paramString ? '?' + paramString : '');
				writeUrl(cleanUrl, false);
			}
		} else if ( 'settings' === main && parts[1] ) {
			// Update URL with tab parameter for feature settings.
			const newTab = parts[1];
			const newPanel = parts[2] || null;

			if (currentTab !== newTab || currentPanel !== newPanel || window.location.hash) {
				const isPanelAutoSelect = currentTab === newTab && ! currentPanel && !! newPanel;

				urlParams.set('tab', newTab);
				if (newPanel) {
					urlParams.set('panel', newPanel);
				} else {
					urlParams.delete('panel');
				}
				// Use query params (no hash).
				const newUrl = window.location.pathname + '?' + urlParams.toString();
				writeUrl(newUrl, isPanelAutoSelect);
			}
		}

		hasSyncedUrlRef.current = true;
	}, [currentRoute]);

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

			// Help is not a registered feature — it's a standalone Settings 2.0
			// page that replaces the legacy `?page=bp-help` admin screen.
			// Intercept before the feature lookup so it doesn't fall through to
			// FeatureSettingsScreen (which would request bb_admin_get_feature_settings
			// for an unknown feature ID).
			if ( 'help' === featureId ) {
				// Sub-route: /settings/help/support-access opens the dedicated
				// Support Access management page from the Open Access card.
				if ( 'support-access' === sidePanelId ) {
					return <SupportAccessScreen onNavigate={onNavigate} />;
				}
				return <HelpScreen onNavigate={onNavigate} />;
			}

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

		case 'forums':
			// Check if forums feature is enabled
			if (isCheckingFeatures) {
				return <LoadingSpinner />;
			}

			if (!isFeatureEnabled(features, 'forums')) {
				return (
					<FeatureDisabled
						featureId="forums"
						featureLabel={getFeatureLabel('forums')}
						onNavigate={onNavigate}
					/>
				);
			}

			if (routeParts[1] === 'all') {
				return (
					<Suspense fallback={<LoadingSpinner />}>
						<ForumsListScreen onNavigate={onNavigate} />
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