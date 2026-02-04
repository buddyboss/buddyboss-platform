/**
 * BuddyBoss Admin Settings 2.0 - Feature Settings Screen
 *
 * Handles the new hierarchy: Feature → Side Panels → Sections → Fields
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss 3.0.0
 */

import { useState, useEffect, useRef } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Spinner } from '@wordpress/components';
import { getCachedFeatureData, setCachedFeatureData, invalidateFeatureCache } from '../utils/featureCache';
import { SettingsForm } from '../components/SettingsForm';
import { SideNavigation } from './SideNavigation';
import { Toast } from '../components/Toast';
import { debounce } from '../../utils/api';

/**
 * AJAX request helper for fetching feature data.
 *
 * @param {string} action AJAX action name.
 * @param {Object} data   Additional data.
 * @returns {Promise} Promise resolving to response data.
 */
const ajaxFetch = (action, data = {}) => {
	const formData = new FormData();
	formData.append('action', action);
	formData.append('nonce', window.bbAdminData?.ajaxNonce || '');

	Object.keys(data).forEach((key) => {
		formData.append(key, data[key]);
	});

	return fetch(window.bbAdminData?.ajaxUrl || '/wp-admin/admin-ajax.php', {
		method: 'POST',
		credentials: 'same-origin',
		body: formData,
	}).then((response) => response.json());
};

/**
 * Feature Settings Screen Component
 *
 * Hierarchy: Feature → Side Panels → Sections → Fields
 *
 * @param {Object} props Component props
 * @param {string} props.featureId   Feature ID (from URL tab param)
 * @param {string} props.sidePanelId Optional side panel ID for deep linking (from URL sidepanel param)
 * @param {Function} props.onNavigate Navigation callback
 * @returns {JSX.Element} Feature settings screen
 */
export function FeatureSettingsScreen({ featureId, sidePanelId, onNavigate }) {
	const [feature, setFeature] = useState(null);
	const [sidePanels, setSidePanels] = useState([]);
	const [navItems, setNavItems] = useState([]);
	const [settings, setSettings] = useState({});
	const [originalSettings, setOriginalSettings] = useState({}); // Track original values
	const [isLoading, setIsLoading] = useState(true);
	const [activePanelId, setActivePanelId] = useState(sidePanelId || null);

	// Auto-save state.
	const [toast, setToast] = useState(null);
	const [changedFields, setChangedFields] = useState({});
	const [initialLoad, setInitialLoad] = useState(true);
	const debouncedSaveRef = useRef();

	// Load feature settings via AJAX - only when featureId changes
	// Uses caching to prevent re-fetching on navigation within the same feature
	useEffect(() => {
		// Check if we have cached data for this feature
		const cachedData = getCachedFeatureData(featureId);
		
		if (cachedData) {
			// Use cached data
			setFeature(cachedData);
			const loadedPanels = cachedData.side_panels || [];
			setSidePanels(loadedPanels);
			setNavItems(cachedData.navigation || []);
			const loadedSettings = cachedData.settings || {};
			setSettings(loadedSettings);
			setOriginalSettings(JSON.parse(JSON.stringify(loadedSettings)));
			
			// Set active panel from URL sidepanel param or default
			if (sidePanelId && loadedPanels.some(p => p.id === sidePanelId)) {
				setActivePanelId(sidePanelId);
			} else {
				const defaultPanel = loadedPanels.find(p => p.is_default) || loadedPanels[0];
				setActivePanelId(defaultPanel ? defaultPanel.id : null);
			}
			setIsLoading(false);
			setInitialLoad(false);
			return;
		}
		
		// No cache, fetch from server
		setIsLoading(true);
		ajaxFetch('bb_admin_get_feature_settings', { feature_id: featureId })
			.then((response) => {
				if (response.success && response.data) {
					// Cache the response data
					setCachedFeatureData(featureId, response.data);
					
					setFeature(response.data);
					const loadedPanels = response.data.side_panels || [];
					setSidePanels(loadedPanels);
					setNavItems(response.data.navigation || []);
					const loadedSettings = response.data.settings || {};
					setSettings(loadedSettings);
					setOriginalSettings(JSON.parse(JSON.stringify(loadedSettings))); // Deep copy
					
					// Set active panel: use sidePanelId from props, or first panel with is_default, or first panel
					if (sidePanelId && loadedPanels.some(p => p.id === sidePanelId)) {
						setActivePanelId(sidePanelId);
					} else {
						const defaultPanel = loadedPanels.find(p => p.is_default) || loadedPanels[0];
						setActivePanelId(defaultPanel ? defaultPanel.id : null);
					}
				}
				setIsLoading(false);
				setInitialLoad(false);
			})
			.catch(() => {
				setIsLoading(false);
				setInitialLoad(false);
			});
	}, [featureId]); // Only reload data when featureId changes, not on tab change

	// Sync active panel when sidePanelId prop changes (from URL) - no AJAX call needed
	useEffect(() => {
		if (sidePanelId && sidePanels.some(p => p.id === sidePanelId)) {
			setActivePanelId(sidePanelId);
		}
	}, [sidePanelId, sidePanels]);

	// Setup debounced save (auto-save on change)
	// Uses AJAX endpoint for feature settings.
	useEffect(() => {
		debouncedSaveRef.current = debounce((fieldsToSave) => {
			if (Object.keys(fieldsToSave).length === 0) {
				return;
			}

			// Use AJAX endpoint for feature settings
			ajaxFetch('bb_admin_save_feature_settings', {
				feature_id: featureId,
				settings: JSON.stringify(fieldsToSave),
			})
				.then((response) => {
					if (response.success) {
						setToast({
							status: 'success',
							message: __('Settings saved.', 'buddyboss'),
						});
						setChangedFields({});

						// Check if reaction_items were saved (need to refetch to get real IDs from server)
						const savedReactionItems = fieldsToSave.reaction_items !== undefined;

						// For reactions feature, refetch data when:
						// 1. reaction_items were saved (to get real DB IDs replacing react_key_ IDs)
						// 2. migration data is returned
						// This ensures delete checks work correctly (need real IDs for AJAX validation)
						if (featureId === 'reactions' && (savedReactionItems || response.data?.migration_data || response.data?.migration_status)) {
							ajaxFetch('bb_admin_get_feature_settings', { feature_id: featureId })
								.then((featureResponse) => {
									if (featureResponse.success && featureResponse.data) {
										// Update cache with fresh data
										setCachedFeatureData(featureId, featureResponse.data);
										setFeature(featureResponse.data);
										setSidePanels(featureResponse.data.side_panels || []);
										const freshSettings = featureResponse.data.settings || {};
										setSettings(freshSettings);
										setOriginalSettings(freshSettings);
									}
								});
						} else {
							// Update original settings
							setOriginalSettings((prev) => ({ ...prev, ...fieldsToSave }));
							// Update cache
							const cachedData = getCachedFeatureData(featureId);
							if (cachedData) {
								setCachedFeatureData(featureId, {
									...cachedData,
									settings: { ...cachedData.settings, ...fieldsToSave },
								});
							}
						}
					} else {
						setToast({
							status: 'error',
							message: response.data?.message || __('Something went wrong. Please try again.', 'buddyboss'),
						});
					}
				})
				.catch(() => {
					setToast({
						status: 'error',
						message: __('Something went wrong. Please try again.', 'buddyboss'),
					});
				});
		}, 1000);

		return () => {
			if (debouncedSaveRef.current?.cancel) {
				debouncedSaveRef.current.cancel();
			}
		};
	}, [featureId]);

	// Auto-trigger save when changedFields updates
	useEffect(() => {
		if (!initialLoad && Object.keys(changedFields).length > 0) {
			debouncedSaveRef.current(changedFields);
		}
	}, [changedFields, initialLoad]);

	// Auto-dismiss success toast after 3 seconds
	useEffect(() => {
		if (!toast) return;

		if (toast.status === 'success') {
			const timer = setTimeout(() => {
				setToast(null);
			}, 3000);
			return () => clearTimeout(timer);
		}
	}, [toast]);

	// Handle setting change - triggers auto-save
	const handleSettingChange = (fieldName, value) => {
		setToast({ status: 'saving', message: __('Saving changes...', 'buddyboss') });
		setSettings((prev) => ({
			...prev,
			[fieldName]: value,
		}));
		setChangedFields((prev) => ({ ...prev, [fieldName]: value }));
	};

	const handlePanelChange = (route) => {
		// Route from SideNavigation is already in full format: /settings/featureId/panelId
		// Extract the panel ID from the route
		const parts = route.split('/').filter(Boolean);
		const newPanelId = parts[2]; // /settings/featureId/panelId
		if (newPanelId) {
			setActivePanelId(newPanelId);
		}
	};

	const handleBack = () => {
		onNavigate('/settings');
	};

	if (isLoading) {
		return (
			<div className="bb-admin-feature-settings bb-admin-loading">
				<Spinner />
			</div>
		);
	}

	if (!feature) {
		return (
			<div className="bb-admin-feature-settings bb-admin-not-found">
				<h2>{__('Feature not found', 'buddyboss')}</h2>
				<p>{__('The requested feature could not be found.', 'buddyboss')}</p>
			</div>
		);
	}

	// Get the active side panel
	const activePanel = sidePanels.find(p => p.id === activePanelId);

	return (
		<div className="bb-admin-feature-settings">
			<div className="bb-admin-feature-settings__container">
				{/* Left Sidebar Navigation */}
				<aside className="bb-admin-feature-settings__sidebar">
					<SideNavigation
						featureId={featureId}
						sidePanels={sidePanels}
						navItems={navItems}
						currentPanel={activePanelId}
						onNavigate={onNavigate}
						onBack={handleBack}
					/>
				</aside>

				{/* Main Content */}
				<main className="bb-admin-feature-settings__main">
					{/* Content wrapper */}
					<div className="bb-admin-feature-settings__content-wrap">

						{/* Settings Form - Show sections for active side panel */}
						<div className="bb-admin-feature-settings__content">
							{activePanel ? (
								<>
									{/* Render all sections within the active side panel */}
									{(activePanel.sections || []).map((section) => (
										<div
											key={section.id}
											id={`section-${section.id}`}
											className="bb-admin-feature-settings__section"
										>
											{/* Section Header */}
											<div className="bb-admin-feature-settings__section-header">
												<h3 className="bb-admin-feature-settings__section-title">{section.title}</h3>
												{/* Help icon - links to side panel's help_url */}
												{activePanel.help_url ? (
													<a
														href={activePanel.help_url}
														target="_blank"
														rel="noopener noreferrer"
														className="help-icon"
														aria-label={__('Help', 'buddyboss')}
														title={__('View documentation', 'buddyboss')}
													>
														<span className="bb-icons-rl-question"></span>
													</a>
												) : (
													<button className="help-icon" aria-label={__('Help', 'buddyboss')}>
														<span className="bb-icons-rl-question"></span>
													</button>
												)}
											</div>
											{/* Section Body */}
											<div className="bb-admin-feature-settings__section-body">
												{section.description && (
													<p className="bb-admin-feature-settings__section-description" dangerouslySetInnerHTML={{ __html: section.description }} />
												)}
												<SettingsForm
													fields={section.fields || []}
													values={settings}
													onChange={handleSettingChange}
												/>
											</div>
										</div>
									))}
								</>
							) : (
								<div className="bb-admin-feature-settings__no-section">
									<p>{__('Please select a panel from the sidebar.', 'buddyboss')}</p>
								</div>
							)}

						</div>
					</div>
				</main>
			</div>

			{/* Toast notification for auto-save status - Fixed position at bottom-right */}
			{toast && (
				<div className="bb-toast-container">
					<Toast
						status={toast.status}
						message={toast.message}
						onDismiss={() => setToast(null)}
					/>
				</div>
			)}
		</div>
	);
}
