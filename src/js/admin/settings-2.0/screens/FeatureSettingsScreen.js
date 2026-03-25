/**
 * BuddyBoss Admin Settings 2.0 - Feature Settings Screen
 *
 * Handles the new hierarchy: Feature → Side Panels → Sections → Fields
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

import { useState, useEffect, useRef, useCallback, useMemo, lazy, Suspense, RawHTML } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Spinner, ToggleControl } from '@wordpress/components';
import { ajaxFetch } from '../utils/ajax';
import { getCachedFeatureData, setCachedFeatureData, invalidateFeatureCache } from '../utils/featureCache';
import { BB_EVENTS } from '../utils/constants';
import { applyReactionPostSave } from '../components/reaction/applyReactionPostSave';
import { SettingsForm } from '../components/SettingsForm';
import { SideNavigation } from './SideNavigation';
import { Toast } from '../components/Toast';
import { debounce, fetchHelpContent, clearHelpContentCache } from '../../utils/api';
import { HelpIcon } from '../components/HelpIcon';
import { HelpSliderModal } from '../components/HelpSliderModal';
import { sanitizeHtml, safeUrl } from '../utils/sanitize';
import { useGroupNavSync } from '../components/groups/GroupNavSync';
import { useProfileNavSync } from '../components/members/ProfileNavSync';

// Lazy load custom panel screens.
const ActivityListScreen = lazy(() => import('./ActivityListScreen'));
const GroupsListScreen = lazy(() => import('./GroupsListScreen'));
const GroupTypeScreen = lazy(() => import('./GroupTypeScreen'));
const ProfileTypeScreen = lazy(() => import('./ProfileTypeScreen'));
const ProfileFieldsScreen = lazy(() => import('./ProfileFieldsScreen'));
const ProfileSearchScreen = lazy(() => import('./ProfileSearchScreen'));
const ForumsListScreen = lazy(() => import('./ForumsListScreen'));
const DiscussionsListScreen = lazy(() => import('./DiscussionsListScreen'));
const DiscussionTagsListScreen = lazy(() => import('./DiscussionTagsListScreen'));
const RepliesListScreen = lazy(() => import('./RepliesListScreen'));
const ReportingCategoriesScreen = lazy(() => import('./ReportingCategoriesScreen'));
const FlaggedMembersScreen = lazy(() => import('./FlaggedMembersScreen'));
const ReportedContentScreen = lazy(() => import('./ReportedContentScreen'));
const EmailTemplatesListScreen = lazy(() => import('./EmailTemplatesListScreen'));

/**
 * Map of feature + panel combinations that render custom screens instead of settings forms.
 */
const CUSTOM_PANEL_SCREENS = {
	'activity:all_activities': ActivityListScreen,
	'groups:all_groups': GroupsListScreen,
	'groups:group_types': GroupTypeScreen,
	'members:profile_types': ProfileTypeScreen,
	'members:profile_fields': ProfileFieldsScreen,
	'members:profile_search': ProfileSearchScreen,
	'forums:all_forums': ForumsListScreen,
	'forums:discussions': DiscussionsListScreen,
	'forums:discussion_tags': DiscussionTagsListScreen,
	'forums:replies': RepliesListScreen,
	'moderation:reporting_categories': ReportingCategoriesScreen,
	'moderation:flagged_members': FlaggedMembersScreen,
	'moderation:reported_content': ReportedContentScreen,
	'emails:all_emails': EmailTemplatesListScreen,
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

	// Help modal state.
	const [isHelpOpen, setHelpOpen] = useState(false);
	const [helpContent, setHelpContent] = useState(null);
	const [isHelpLoading, setHelpLoading] = useState(false);
	const [helpError, setHelpError] = useState(null);

	// Section status overrides (updated via custom events from input_button fields).
	const [sectionStatusOverrides, setSectionStatusOverrides] = useState({});

	// Auto-save state.
	const [toast, setToast] = useState(null);
	const [changedFields, setChangedFields] = useState({});
	const [initialLoad, setInitialLoad] = useState(true);
	const debouncedSaveRef = useRef();

	// Ref for latest settings so refetch (reactions) can update cache without replacing state.
	const settingsRef = useRef(settings);
	useEffect(() => {
		settingsRef.current = settings;
	}, [settings]);

	// Listen for section status updates from input_button fields (e.g. GIPHY connect/disconnect).
	useEffect( function() {
		function handleStatusUpdate( event ) {
			var detail = event.detail;
			if ( detail && detail.fieldName && detail.status ) {
				setSectionStatusOverrides( function( prev ) {
					var next = Object.assign( {}, prev );
					next[ detail.fieldName ] = detail.status;
					return next;
				} );
			}
		}
		window.addEventListener( BB_EVENTS.SECTION_STATUS_UPDATE, handleStatusUpdate );
		return function() {
			window.removeEventListener( BB_EVENTS.SECTION_STATUS_UPDATE, handleStatusUpdate );
		};
	}, [] );

	// Listen for toast events from child components (e.g. ProfileTypeRedirectsField save).
	useEffect( function() {
		function handleToast( event ) {
			var detail = event.detail;
			if ( detail && detail.status ) {
				setToast( { status: detail.status, message: detail.message || '' } );
			}
		}
		window.addEventListener( BB_EVENTS.TOAST, handleToast );
		return function() {
			window.removeEventListener( BB_EVENTS.TOAST, handleToast );
		};
	}, [] );

	// Load feature settings via AJAX - only when featureId changes
	// Uses caching to prevent re-fetching on navigation within the same feature
	// AbortController cancels stale requests when featureId changes rapidly
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
		const controller = new AbortController();
		setIsLoading(true);
		ajaxFetch('bb_admin_get_feature_settings', { feature_id: featureId }, { signal: controller.signal })
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
			.catch((err) => {
				// Ignore aborted requests
				if (err && 'AbortError' === err.name) {
					return;
				}
				setIsLoading(false);
				setInitialLoad(false);
				setToast({ status: 'error', message: __('Failed to load settings. Please refresh.', 'buddyboss') });
			});

		return () => controller.abort();
	}, [featureId]); // Only reload data when featureId changes, not on tab change

	// Sync active panel when sidePanelId prop changes (from URL) - no AJAX call needed
	useEffect(() => {
		if (sidePanelId && sidePanels.some(p => p.id === sidePanelId)) {
			setActivePanelId(sidePanelId);
		}
	}, [sidePanelId, sidePanels]);

	// Generic event listener for refetching feature data.
	// Refetch is used after dismiss/complete to refresh migration state (panels). For reactions,
	// we only need updated panels (migration_data); we must not replace settings or we overwrite
	// the user's mode (e.g. Likes) with stale server data.
	useEffect(() => {
		var refetchAbort = null;
		const handleRefetchFeature = () => {
			if ( refetchAbort ) {
				refetchAbort.abort();
			}
			refetchAbort = new AbortController();
			ajaxFetch('bb_admin_get_feature_settings', { feature_id: featureId }, { signal: refetchAbort.signal })
				.then((response) => {
					if (response.success && response.data) {
						if (featureId === 'reactions') {
							// Refresh panels only; preserve current settings so mode doesn't flip back.
							const currentSettings = settingsRef.current;
							setCachedFeatureData(featureId, { ...response.data, settings: currentSettings });
							setFeature(response.data);
							setSidePanels(response.data.side_panels || []);
							// Do not setSettings/setOriginalSettings – refetch is for migration state only.
						} else {
							const refreshedSettings = response.data.settings || {};
							setCachedFeatureData(featureId, response.data);
							setFeature(response.data);
							setSidePanels(response.data.side_panels || []);
							setSettings(refreshedSettings);
							setOriginalSettings(JSON.parse(JSON.stringify(refreshedSettings)));
						}
					}
				})
				.catch((err) => {
					if ( err && 'AbortError' === err.name ) {
						return;
					}
					setToast({ status: 'error', message: __('Failed to refresh settings. Please try again.', 'buddyboss') });
				});
		};

		window.addEventListener('bb-admin-refetch-feature', handleRefetchFeature);
		return () => {
			window.removeEventListener('bb-admin-refetch-feature', handleRefetchFeature);
			if ( refetchAbort ) {
				refetchAbort.abort();
			}
		};
	}, [featureId]);

	// Listen for field value updates dispatched by InputButtonField (e.g. after credential save).
	// Updates settings and side panel field defaults so notice fields reflect new connection status.
	useEffect( function() {
		function handleFieldValueUpdate( e ) {
			var updatedFields = e.detail && e.detail.fields;
			if ( ! updatedFields || 'object' !== typeof updatedFields ) {
				return;
			}

			// Update current settings state.
			setSettings( function( prev ) {
				return Object.assign( {}, prev, updatedFields );
			} );

			// Update field data in side panels so notice/other fields render updated content.
			// Notice fields render from `description`, other fields from `default`.
			setSidePanels( function( prevPanels ) {
				return prevPanels.map( function( panel ) {
					return Object.assign( {}, panel, {
						sections: ( panel.sections || [] ).map( function( section ) {
							return Object.assign( {}, section, {
								fields: ( section.fields || [] ).map( function( field ) {
									if ( undefined !== updatedFields[ field.name ] ) {
										var updates = { default: updatedFields[ field.name ] };
										// Notice fields render from description, so update that too.
										if ( 'notice' === field.type ) {
											updates.description = updatedFields[ field.name ];
										}
										// Update is_connected for input_button fields.
										if ( undefined !== e.detail.is_connected && 'input_button' === field.type ) {
											updates.is_connected = e.detail.is_connected;
										}
										return Object.assign( {}, field, updates );
									}
									return field;
								} ),
							} );
						} ),
					} );
				} );
			} );

			// Invalidate cache so next navigation fetches fresh data.
			invalidateFeatureCache();
		}

		window.addEventListener( BB_EVENTS.FIELD_VALUE_UPDATE, handleFieldValueUpdate );
		return function() {
			window.removeEventListener( BB_EVENTS.FIELD_VALUE_UPDATE, handleFieldValueUpdate );
		};
	}, [] );

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

						// Reactions: refetch when reaction_items saved, or inject migration data (handled in reaction module).
						if ( 'reactions' === featureId ) {
							applyReactionPostSave( response, fieldsToSave, featureId, {
								ajaxFetch,
								getCachedFeatureData,
								setCachedFeatureData,
								setFeature,
								setSidePanels,
								setSettings,
								setOriginalSettings,
							} );
						} else {
							// Use actual saved values from server response (may differ from
							// submitted values due to server-side validation/revert).
							var actualSaved = ( response.data && response.data.saved ) ? response.data.saved : fieldsToSave;
							setSettings((prev) => ({ ...prev, ...actualSaved }));
							setOriginalSettings((prev) => ({ ...prev, ...actualSaved }));
							const cachedData = getCachedFeatureData(featureId);
							if (cachedData) {
								setCachedFeatureData(featureId, {
									...cachedData,
									settings: { ...cachedData.settings, ...actualSaved },
								});
							}

							// If the server indicates panel visibility changed, refetch
							// feature data to update the side navigation (e.g. Discussion Tags toggle).
							if ( response.data && response.data.refresh_panels ) {
								invalidateFeatureCache();
								window.dispatchEvent( new Event( 'bb-admin-refetch-feature' ) );
							}
						}
					} else {
						setToast({
							status: 'error',
							message: ( response.data && response.data.message ) || __('Something went wrong. Please try again.', 'buddyboss'),
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
			if (debouncedSaveRef.current && debouncedSaveRef.current.cancel) {
				debouncedSaveRef.current.cancel();
			}
		};
	}, [featureId]);

	// Auto-trigger save when changedFields updates.
	// When a value is a sentinel (true), use current settings via settingsRef for that key (for functional updates from reaction picker).
	useEffect(() => {
		if ( ! initialLoad && Object.keys(changedFields).length > 0 ) {
			var currentSettings = settingsRef.current;
			const payload = Object.fromEntries(
				Object.keys(changedFields).map((k) => [
					k,
					changedFields[k] === true ? currentSettings[k] : changedFields[k],
				])
			);
			debouncedSaveRef.current(payload);
		}
	}, [changedFields, initialLoad]);

	// Auto-dismiss success toast after 3 seconds
	useEffect(() => {
		if (!toast) return;

		if ( 'success' === toast.status ) {
			const timer = setTimeout(() => {
				setToast(null);
			}, 3000);
			return () => clearTimeout(timer);
		}
	}, [toast]);

	// Pre-build lookup maps to avoid triple-nested loops in handleSettingChange.
	var buttonManagedFields = useMemo( function () {
		var managed = {};
		sidePanels.forEach( function ( panel ) {
			( panel.sections || [] ).forEach( function ( section ) {
				( section.fields || [] ).forEach( function ( field ) {
					if ( 'input_button' === field.type && Array.isArray( field.related_fields ) ) {
						field.related_fields.forEach( function ( rf ) {
							managed[ rf ] = true;
						} );
					}
				} );
			} );
		} );
		return managed;
	}, [ sidePanels ] );

	var parentChildMap = useMemo( function () {
		var map = {};
		sidePanels.forEach( function ( panel ) {
			( panel.sections || [] ).forEach( function ( section ) {
				( section.fields || [] ).forEach( function ( field ) {
					if ( field.parent_field ) {
						if ( ! map[ field.parent_field ] ) {
							map[ field.parent_field ] = [];
						}
						map[ field.parent_field ].push( field.name );
					}
				} );
			} );
		} );
		return map;
	}, [ sidePanels ] );

	// Handle setting change (all fields) - triggers auto-save.
	// Value may be a function (prevValue) => newValue for functional updates (avoids stale state when merging).
	// When a parent toggle is turned OFF, cascade to child fields (parent_field) and turn them OFF too.
	const handleSettingChange = useCallback((fieldName, value) => {
		// Check if this field is managed by an input_button (saved via its own AJAX, not auto-save).
		if ( buttonManagedFields[ fieldName ] ) {
			setSettings( function( prev ) {
				return Object.assign( {}, prev, { [fieldName]: value } );
			} );
			return;
		}

		setToast({ status: 'saving', message: __('Saving changes...', 'buddyboss') });

		// Collect child fields that depend on this field via parent_field.
		var childNames = [];
		var resolvedValue = value;
		if ( typeof resolvedValue !== 'function' && ! resolvedValue ) {
			childNames = parentChildMap[ fieldName ] || [];
		}

		setSettings((prev) => {
			var next = {
				...prev,
				[fieldName]: typeof value === 'function' ? value(prev[fieldName]) : value,
			};
			// Turn off child fields when parent is turned off.
			childNames.forEach( function( childName ) {
				next[childName] = 0;
			} );
			return next;
		});
		setChangedFields((prev) => {
			const next = { ...prev };
			// For functional updates we cannot know the new value here; set a sentinel so save uses latest settings
			if ( typeof value === 'function' ) {
				next[fieldName] = true;
			} else {
				next[fieldName] = value;
			}
			// Mark child fields as changed so they get saved too.
			childNames.forEach( function( childName ) {
				next[childName] = 0;
			} );
			return next;
		});
	}, [buttonManagedFields, parentChildMap]);

	// Sync Default Tab dropdown with Navigation Order toggles (groups feature only).
	useGroupNavSync( {
		featureId: featureId,
		settings: settings,
		settingsRef: settingsRef,
		initialLoad: initialLoad,
		setSidePanels: setSidePanels,
		setSettings: setSettings,
		handleSettingChange: handleSettingChange,
	} );

	// Sync Default Tab dropdown with Navigation Order toggles (members feature only).
	useProfileNavSync( {
		featureId: featureId,
		settings: settings,
		settingsRef: settingsRef,
		initialLoad: initialLoad,
		setSidePanels: setSidePanels,
		setSettings: setSettings,
		handleSettingChange: handleSettingChange,
	} );

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

	// Help modal handlers.
	const handleHelpClick = async (contentId) => {
		setHelpOpen(true);
		setHelpLoading(true);
		setHelpError(null);

		try {
			const content = await fetchHelpContent(contentId);
			setHelpContent(content);
		} catch (error) {
			setHelpError(__('Failed to load help content. Please try again later.', 'buddyboss'));
			clearHelpContentCache(contentId);
		} finally {
			setHelpLoading(false);
		}
	};

	const handleHelpClose = () => {
		setHelpOpen(false);
		setHelpContent(null);
		setHelpError(null);
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

	// Check if this panel has a custom screen (e.g., ActivityListScreen).
	const customScreenKey = featureId + ':' + activePanelId;
	const CustomScreen = CUSTOM_PANEL_SCREENS[customScreenKey] || null;

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
				<main className={ 'bb-admin-feature-settings__main' + ( CustomScreen ? ' bb-admin-feature-settings__main--custom-panel' : '' ) }>
					{/* Custom Panel Screen (e.g., All Activities, All Groups) */}
					{CustomScreen ? (
						<Suspense fallback={<div className="bb-admin-loading"><Spinner /></div>}>
							<CustomScreen onNavigate={onNavigate} helpUrl={activePanel ? activePanel.help_url : ''} onHelpClick={handleHelpClick} feature={feature} settings={settings} activePanelId={activePanelId} />
						</Suspense>
					) : (
					<>
					{/* Content wrapper */}
					<div className="bb-admin-feature-settings__content-wrap">

						{/* Settings Form - Show sections for active side panel */}
						<div className="bb-admin-feature-settings__content">
							{activePanel ? (
								<>
									{/* Render all sections within the active side panel */}
									{(activePanel.sections || []).map((section) => {
										// Check section-level conditional (hide or disable).
										var isSectionDisabled = false;
										var isSectionHidden = false;
										if ( section.conditional ) {
											var condVal = settings[section.conditional.field];
											var expected = section.conditional.value;
											var isTruthy = !!condVal && condVal !== '0' && condVal !== 0;
											var condMet = ( expected === true || expected === false ) ? isTruthy === expected : condVal === expected;
											if ( ! condMet ) {
												if ( 'disable' === section.conditional.action ) {
													isSectionDisabled = true;
												} else {
													isSectionHidden = true;
												}
											}
										}

										if ( isSectionHidden ) {
											return null;
										}

										// Section toggle: when present, controls whether all fields in this section are enabled.
									var sectionToggleKey = section.section_toggle || null;
									var isSectionToggleOff = false;
									if ( sectionToggleKey ) {
										var toggleVal = settings[ sectionToggleKey ];
										isSectionToggleOff = ! toggleVal || toggleVal === '0' || toggleVal === 0;
									}

									return (
										<div
											key={section.id}
											id={`section-${section.id}`}
											className={ 'bb-admin-feature-settings__section' + ( isSectionDisabled ? ' bb-admin-feature-settings__section--disabled' : '' ) }
										>
											{/* Section Header */}
											<div className="bb-admin-feature-settings__section-header">
												<div className="bb-admin-feature-settings__section-header-left">
													<h3 className="bb-admin-feature-settings__section-title">{section.title}</h3>
													{/* Section status badge (e.g. Connected/Not Connected) */}
													{( function() {
														// Check for overridden status from input_button events, falling back to section data.
														var sectionFields = section.fields || [];
														var statusOverride = null;
														for ( var fi = 0; fi < sectionFields.length; fi++ ) {
															if ( sectionStatusOverrides[ sectionFields[ fi ].name ] ) {
																statusOverride = sectionStatusOverrides[ sectionFields[ fi ].name ];
																break;
															}
														}
														var sectionStatus = statusOverride || section.status;
														if ( ! sectionStatus || ! sectionStatus.text ) {
															return null;
														}
														var statusIconClass = 'success' === sectionStatus.type
															? 'bb-icons-rl bb-icons-rl-check-circle'
															: 'bb-icons-rl bb-icons-rl-warning-circle';
														return (
															<span className={ 'bb-admin-feature-settings__section-status bb-admin-feature-settings__section-status--' + sectionStatus.type }>
																<i className={ 'bb-admin-feature-settings__section-status-icon ' + statusIconClass } />
																{ sectionStatus.text }
															</span>
														);
													} )()}
												</div>
												<div className="bb-admin-feature-settings__section-header-right">
													{/* Section-level PRO badge (e.g. UPGRADE PRO) */}
													{section.pro_notice && section.pro_notice.show && (
														<span className="bb-admin-feature-settings__section-pro-notice">
															{section.pro_notice.link_url ? (
																<a
																	href={safeUrl(section.pro_notice.link_url)}
																	target="_blank"
																	rel="noopener noreferrer"
																	className="bb-admin-feature-settings__section-pro-badge"
																>
																	<i className={section.pro_notice.badge_icon || 'bb-icons-rl-crown-simple'} />
																	<span>{section.pro_notice.badge_text || 'UPGRADE PRO'}</span>
																</a>
															) : (
																<span className="bb-admin-feature-settings__section-pro-badge">
																	<i className={section.pro_notice.badge_icon || 'bb-icons-rl-crown-simple'} />
																	<span>{section.pro_notice.badge_text || 'UPGRADE PRO'}</span>
																</span>
															)}
														</span>
													)}
													{/* Help icon - opens help slider modal */}
													{activePanel.help_url && (
														<HelpIcon
															onClick={handleHelpClick}
															contentId={activePanel.help_url}
														/>
													)}
													{/* Section toggle - enables/disables all fields in this section */}
													{sectionToggleKey && (
														<div className="bb-admin-feature-settings__section-toggle">
															<ToggleControl
																className="components-form-toggle--is-big"
																checked={ ! isSectionToggleOff }
																onChange={ function( newVal ) {
																	handleSettingChange( sectionToggleKey, newVal ? 1 : 0 );
																} }
															/>
														</div>
													)}
												</div>
											</div>
											{/* Section Body */}
											<div className={ 'bb-admin-feature-settings__section-body' + ( isSectionToggleOff ? ' bb-admin-feature-settings__section-body--disabled' : '' ) }>
												{section.description && (
													<RawHTML className="bb-admin-feature-settings__section-description">
														{sanitizeHtml( section.description )}
													</RawHTML>
												)}
												<SettingsForm
													fields={section.fields || []}
													values={settings}
													onChange={handleSettingChange}
												/>
											</div>
										</div>
									);
									})}
								</>
							) : (
								<div className="bb-admin-feature-settings__no-section">
									<p>{__('Please select a panel from the sidebar.', 'buddyboss')}</p>
								</div>
							)}

						</div>
					</div>
					</>
					)}
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

			{/* Help Slider Modal */}
			<HelpSliderModal
				isOpen={isHelpOpen}
				onClose={handleHelpClose}
				title={( helpContent && helpContent.title ) || __('Help', 'buddyboss')}
			>
				{isHelpLoading ? (
					<div className="help-content-loading">
						<Spinner />
						<p>{__('Loading help content...', 'buddyboss')}</p>
					</div>
				) : helpError ? (
					<div className="help-content-error">
						<p>{helpError}</p>
					</div>
				) : helpContent ? (
					<>
						{helpContent.videoId && /^[a-zA-Z0-9_-]+$/.test(helpContent.videoId) && (
							<div style={{ marginBottom: 16 }}>
								<iframe
									width="100%"
									height="315"
									src={`https://www.youtube.com/embed/${helpContent.videoId}`}
									title={__('Video tutorial', 'buddyboss')}
									frameBorder="0"
									allowFullScreen
								></iframe>
							</div>
						)}
						<div
							className="help-content"
							dangerouslySetInnerHTML={{ __html: sanitizeHtml( helpContent.content ) }}
						/>
						{helpContent.imageUrl && '#' !== safeUrl( helpContent.imageUrl ) && (
							<img
								src={ safeUrl( helpContent.imageUrl ) }
								alt={__('Help content illustration', 'buddyboss')}
								style={{ width: '100%', borderRadius: 8, marginBottom: 16 }}
							/>
						)}
					</>
				) : (
					<p>{__('No help content available.', 'buddyboss')}</p>
				)}
			</HelpSliderModal>
		</div>
	);
}
