/**
 * BuddyBoss Admin Settings 2.0 - Feature Settings Screen
 *
 * Handles the new hierarchy: Feature → Side Panels → Sections → Fields
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss 3.0.0
 */

import { useState, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Button, Spinner, Notice } from '@wordpress/components';
import apiFetch from '@wordpress/api-fetch';
import { SideNavigation } from '../SideNavigation';
import { SettingsForm } from '../SettingsForm';

/**
 * AJAX request helper.
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
 * @param {Object} props Component props
 * @param {string} props.featureId Feature ID
 * @param {string} props.sectionId Optional side panel ID for deep linking
 * @param {Function} props.onNavigate Navigation callback
 * @returns {JSX.Element} Feature settings screen
 */
export function FeatureSettingsScreen({ featureId, sectionId, onNavigate }) {
	const [feature, setFeature] = useState(null);
	const [sidePanels, setSidePanels] = useState([]);
	const [navItems, setNavItems] = useState([]);
	const [settings, setSettings] = useState({});
	const [originalSettings, setOriginalSettings] = useState({}); // Track original values
	const [isLoading, setIsLoading] = useState(true);
	const [isSaving, setIsSaving] = useState(false);
	const [isDirty, setIsDirty] = useState(false);
	const [saveError, setSaveError] = useState(null);
	const [saveSuccess, setSaveSuccess] = useState(false);
	const [activePanelId, setActivePanelId] = useState(sectionId || null);

	// Load feature settings via AJAX - only when featureId changes
	useEffect(() => {
		setIsLoading(true);
		ajaxFetch('bb_admin_get_feature_settings', { feature_id: featureId })
			.then((response) => {
				if (response.success && response.data) {
					setFeature(response.data);
					const loadedPanels = response.data.side_panels || [];
					setSidePanels(loadedPanels);
					setNavItems(response.data.navigation || []);
					const loadedSettings = response.data.settings || {};
					setSettings(loadedSettings);
					setOriginalSettings(JSON.parse(JSON.stringify(loadedSettings))); // Deep copy
					
					// Set active panel: use sectionId from props, or first panel with is_default, or first panel
					if (sectionId && loadedPanels.some(p => p.id === sectionId)) {
						setActivePanelId(sectionId);
					} else {
						const defaultPanel = loadedPanels.find(p => p.is_default) || loadedPanels[0];
						setActivePanelId(defaultPanel ? defaultPanel.id : null);
					}
				}
				setIsLoading(false);
			})
			.catch(() => {
				setIsLoading(false);
			});
	}, [featureId]); // Only reload data when featureId changes, not on tab change

	// Sync active panel when sectionId prop changes (from URL) - no AJAX call needed
	useEffect(() => {
		if (sectionId && sidePanels.some(p => p.id === sectionId)) {
			setActivePanelId(sectionId);
		}
	}, [sectionId, sidePanels]);

	// Handle unsaved changes warning
	useEffect(() => {
		if (isDirty) {
			const handleBeforeUnload = (e) => {
				e.preventDefault();
				e.returnValue = '';
			};
			window.addEventListener('beforeunload', handleBeforeUnload);
			return () => {
				window.removeEventListener('beforeunload', handleBeforeUnload);
			};
		}
	}, [isDirty]);

	const handleSettingChange = (fieldName, value) => {
		setSettings((prev) => ({
			...prev,
			[fieldName]: value,
		}));
		setIsDirty(true);
		setSaveError(null);
		setSaveSuccess(false);
	};

	// Get only changed settings (compare with original)
	const getChangedSettings = () => {
		const changed = {};
		Object.keys(settings).forEach((key) => {
			const currentValue = settings[key];
			const originalValue = originalSettings[key];
			
			// Handle object comparison (for toggle_list fields)
			if (typeof currentValue === 'object' && currentValue !== null) {
				if (JSON.stringify(currentValue) !== JSON.stringify(originalValue)) {
					changed[key] = currentValue;
				}
			} else if (currentValue !== originalValue) {
				changed[key] = currentValue;
			}
		});
		return changed;
	};

	const handleSave = () => {
		setIsSaving(true);
		setSaveError(null);
		setSaveSuccess(false);

		// Only send changed values
		const changedSettings = getChangedSettings();
		
		if (Object.keys(changedSettings).length === 0) {
			setIsSaving(false);
			setSaveSuccess(true);
			setTimeout(() => setSaveSuccess(false), 3000);
			return;
		}

		ajaxFetch('bb_admin_save_feature_settings', {
			feature_id: featureId,
			settings: JSON.stringify(changedSettings),
		})
			.then((response) => {
				if (response.success) {
					// Update original settings with new values
					setOriginalSettings(JSON.parse(JSON.stringify(settings)));
					setIsDirty(false);
					setSaveSuccess(true);

					// Clear success message after 3 seconds
					setTimeout(() => {
						setSaveSuccess(false);
					}, 3000);
				} else {
					setSaveError(response.data?.message || __('Failed to save settings.', 'buddyboss'));
				}
				setIsSaving(false);
			})
			.catch((error) => {
				setSaveError(error.message || __('Failed to save settings.', 'buddyboss'));
				setIsSaving(false);
			});
	};

	const handleDiscard = () => {
		// Reset to original settings (no AJAX needed)
		setSettings(JSON.parse(JSON.stringify(originalSettings)));
		setIsDirty(false);
		setSaveError(null);
		setSaveSuccess(false);
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
							{/* Notices */}
							{saveSuccess && (
								<Notice status="success" isDismissible={true} onRemove={() => setSaveSuccess(false)}>
									{__('Settings saved successfully.', 'buddyboss')}
								</Notice>
							)}
							{saveError && (
								<Notice status="error" isDismissible={true} onRemove={() => setSaveError(null)}>
									{saveError}
								</Notice>
							)}

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
														<span className="dashicons dashicons-editor-help"></span>
													</a>
												) : (
													<button className="help-icon" aria-label={__('Help', 'buddyboss')}>
														<span className="dashicons dashicons-editor-help"></span>
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

							{/* Save Button */}
							<div className="bb-admin-feature-settings__actions">
								<Button
									variant="primary"
									isBusy={isSaving}
									onClick={handleSave}
									disabled={!isDirty}
								>
									{__('Save Changes', 'buddyboss')}
								</Button>
								{isDirty && (
									<Button variant="secondary" onClick={handleDiscard}>
										{__('Discard Changes', 'buddyboss')}
									</Button>
								)}
							</div>
						</div>
					</div>
				</main>
			</div>
		</div>
	);
}
