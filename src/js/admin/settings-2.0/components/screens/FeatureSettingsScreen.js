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
	const [isLoading, setIsLoading] = useState(true);
	const [isSaving, setIsSaving] = useState(false);
	const [isDirty, setIsDirty] = useState(false);
	const [saveError, setSaveError] = useState(null);
	const [saveSuccess, setSaveSuccess] = useState(false);
	const [activePanelId, setActivePanelId] = useState(sectionId || null);

	useEffect(() => {
		// Load feature settings
		apiFetch({ path: `/buddyboss/v1/features/${featureId}/settings` })
			.then((response) => {
				setFeature(response.data);
				const loadedPanels = response.data.side_panels || [];
				setSidePanels(loadedPanels);
				setNavItems(response.data.navigation || []);
				setSettings(response.data.settings || {});
				setIsLoading(false);
				
				// Set active panel: use sectionId from props, or first panel with is_default, or first panel
				if (sectionId && loadedPanels.some(p => p.id === sectionId)) {
					setActivePanelId(sectionId);
				} else {
					const defaultPanel = loadedPanels.find(p => p.is_default) || loadedPanels[0];
					setActivePanelId(defaultPanel ? defaultPanel.id : null);
				}
			})
			.catch(() => {
				setIsLoading(false);
			});
	}, [featureId, sectionId]);

	// Sync active panel when sectionId prop changes (from URL)
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

	const handleSave = () => {
		setIsSaving(true);
		setSaveError(null);
		setSaveSuccess(false);

		const nonce = bbAdminData.nonce;

		apiFetch({
			path: `/buddyboss/v1/features/${featureId}/settings`,
			method: 'POST',
			headers: {
				'X-WP-Nonce': nonce,
				'Content-Type': 'application/json',
			},
			data: settings,
		})
			.then((response) => {
				setIsDirty(false);
				setSaveSuccess(true);
				setIsSaving(false);

				// Clear success message after 3 seconds
				setTimeout(() => {
					setSaveSuccess(false);
				}, 3000);
			})
			.catch((error) => {
				setSaveError(error.message || __('Failed to save settings.', 'buddyboss'));
				setIsSaving(false);
			});
	};

	const handleDiscard = () => {
		// Reload settings from server
		apiFetch({ path: `/buddyboss/v1/features/${featureId}/settings` })
			.then((response) => {
				setSettings(response.data.settings || {});
				setIsDirty(false);
				setSaveError(null);
				setSaveSuccess(false);
			});
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
