/**
 * BuddyBoss Admin Settings 2.0 - Feature Settings Screen
 *
 * Handles the new hierarchy: Feature → Side Panels → Sections → Fields
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

import { useState, useEffect, useRef, useCallback, useMemo, lazy, Suspense, RawHTML } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import { Button, Spinner, ToggleControl } from '@wordpress/components';
import { ajaxFetch } from '../utils/ajax';
import { getCachedFeatureData, setCachedFeatureData, invalidateFeatureCache } from '../utils/featureCache';
import { BB_EVENTS } from '../utils/constants';
import { applyReactionPostSave } from '../components/reaction/applyReactionPostSave';
import { SettingsForm } from '../components/SettingsForm';
import { UpgradeModal } from '../components/modals/UpgradeModal';
import { SideNavigation } from './SideNavigation';
import { Toast, useAutoDismissToast } from '../components/Toast';
import { debounce, fetchHelpContent, clearHelpContentCache } from '../../utils/api';
import { HelpIcon } from '../components/HelpIcon';
import { HelpSliderModal } from '../components/HelpSliderModal';
import { sanitizeHtml, safeUrl } from '../utils/sanitize';
// KB-article-specific sanitizer for the Help slider body. The generic
// `sanitizeHtml` allowlist excludes `<figure>` and several Gutenberg
// block wrappers, and its handler nukes unknown tags whole-subtree —
// so a `<figure class="wp-block-image"><img></figure>` from the KB
// REST proxy lost its image entirely. `sanitizeKbArticle` allows the
// full Gutenberg media vocabulary (figure/figcaption/picture/source,
// `<img srcset sizes loading decoding fetchpriority>`, host-gated
// iframes for YouTube/Wistia/Vimeo, full table markup) and unwraps
// unknown tags instead of removing them, so future KB blocks degrade
// gracefully. `safeImageUrl` mirrors the same scheme/HTTPS-coercion
// rules used inside the body for the standalone hero image.
import { sanitizeKbArticle, safeImageUrl } from '../utils/sanitizeKbArticle';
import { useGroupNavSync } from '../components/groups/GroupNavSync';
import { useProfileNavSync } from '../components/members/ProfileNavSync';
import { WelcomeBanner } from '../components/appearance/WelcomeBanner';
import { evaluateConditional } from '../utils/conditional';

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
const InvitesListScreen = lazy(() => import('./InvitesListScreen'));

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
	'invites:invites_list': InvitesListScreen,
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

	// Empty-panels recovery: when the AJAX returns success but `side_panels`
	// is empty, the feature was likely just activated and BB_Feature_Loader
	// hasn't yet included its admin settings file. Auto-retry up to
	// EMPTY_PANELS_MAX_RETRIES with a small delay; on the final failure the
	// empty-state message exposes a manual "Retry" button. State (not ref)
	// because the JSX needs to read the count to decide which empty-state
	// variant to render.
	const EMPTY_PANELS_MAX_RETRIES = 3;
	const EMPTY_PANELS_RETRY_DELAY_MS = 1500;
	const [emptyPanelsRetryCount, setEmptyPanelsRetryCount] = useState(0);

	// Upgrade modal state for `pro_only` fields and `pro_notice` sections.
	// Reuses the same UpgradeModal component used by placeholder feature
	// cards on the Settings home screen — payload is shaped to match that
	// component's expected `feature` prop so no rendering code is duplicated.
	const [proUpgradeModalPayload, setProUpgradeModalPayload] = useState(null);

	/**
	 * Map a `pro_notice.modal` payload into the shape UpgradeModal expects.
	 *
	 * UpgradeModal was originally designed for placeholder feature cards and
	 * reads `feature.label`, `feature.upgrade_title`, `feature.upgrade_description`,
	 * `feature.upgrade_image_url`, `feature.upgrade_url`, `feature.upgrade_tier`.
	 * The catalog already uses the same field names, so this is mostly a
	 * passthrough with a couple of fallbacks for older catalog entries that
	 * might omit the title/description.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {Object} modal           Modal payload from pro_notice.modal.
	 * @param {string} fallbackLabel   Field or section label to use when the
	 *                                 catalog doesn't provide a label.
	 * @param {string} fallbackBody    Field/section description to use when
	 *                                 upgrade_description is empty.
	 * @returns {Object} Shaped payload accepted by UpgradeModal.
	 */
	const buildProModalPayload = useCallback((modal, fallbackLabel, fallbackBody) => {
		if (!modal) {
			return null;
		}
		return {
			label: modal.label || fallbackLabel || '',
			upgrade_title: modal.title || '',
			upgrade_description: modal.description || fallbackBody || '',
			upgrade_image_url: modal.image_url || '',
			// PHP-built media payload: { type: 'youtube'|'vimeo'|'mp4'|'image'|'',
			// url, poster }. UpgradeModal switches on media.type to pick the
			// renderer (iframe / video / img). No URL sniffing on the client.
			upgrade_media: modal.media || null,
			upgrade_url: modal.url || 'https://www.buddyboss.com/pricing/',
			upgrade_tier: modal.tier || 'pro',
		};
	}, []);

	/**
	 * Handle a click on a field-level pro badge play button.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {Object} field Field object with pro_notice.modal payload.
	 */
	const handleFieldProClick = useCallback((field) => {
		const payload = buildProModalPayload(
			field?.pro_notice?.modal,
			field?.label,
			field?.description
		);
		if (payload) {
			setProUpgradeModalPayload(payload);
		}
	}, [buildProModalPayload]);

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
				setToast({ status: 'error', message: __('Failed to load settings. Please refresh.', 'buddyboss-platform') });
			});

		return () => controller.abort();
	}, [featureId]); // Only reload data when featureId changes, not on tab change

	// Sync active panel when sidePanelId prop changes (from URL) - no AJAX call needed
	useEffect(() => {
		if (sidePanelId && sidePanels.some(p => p.id === sidePanelId)) {
			setActivePanelId(sidePanelId);
		}
	}, [sidePanelId, sidePanels]);

	// Reset the empty-panels retry counter whenever the feature changes — a
	// fresh feature page starts the recovery sequence over from zero.
	useEffect(() => {
		setEmptyPanelsRetryCount(0);
	}, [featureId]);

	// Auto-retry the get_feature_settings AJAX when we have a successfully
	// loaded feature object but no panels — recovers from the race where the
	// admin enables a feature on the grid and immediately clicks Settings
	// before BB_Feature_Loader has registered the feature's admin settings
	// file. Stops after EMPTY_PANELS_MAX_RETRIES; the empty-state JSX below
	// then surfaces a manual "Retry" button.
	useEffect(() => {
		if ( isLoading || ! feature || sidePanels.length > 0 ) {
			return;
		}
		if ( emptyPanelsRetryCount >= EMPTY_PANELS_MAX_RETRIES ) {
			return;
		}

		var cancelled = false;
		var controller = new AbortController();
		var timer = setTimeout(function () {
			ajaxFetch(
				'bb_admin_get_feature_settings',
				{ feature_id: featureId },
				{ signal: controller.signal }
			)
				.then(function (response) {
					if ( cancelled ) {
						return;
					}
					if ( response && response.success && response.data ) {
						var loadedPanels = response.data.side_panels || [];
						if ( loadedPanels.length > 0 ) {
							// Feature loader caught up — hydrate fully.
							setCachedFeatureData(featureId, response.data);
							setFeature(response.data);
							setSidePanels(loadedPanels);
							setNavItems(response.data.navigation || []);
							var loadedSettings = response.data.settings || {};
							setSettings(loadedSettings);
							setOriginalSettings(JSON.parse(JSON.stringify(loadedSettings)));
							var matchedPanel = sidePanelId && loadedPanels.some(function (p) { return p.id === sidePanelId; })
								? sidePanelId
								: ( ( loadedPanels.find(function (p) { return p.is_default; }) || loadedPanels[0] ).id );
							setActivePanelId(matchedPanel);
							setEmptyPanelsRetryCount(0);
							return;
						}
					}
					// Still empty — bump the counter to schedule the next attempt
					// (or surface the manual retry button when the cap is reached).
					setEmptyPanelsRetryCount(function (n) { return n + 1; });
				})
				.catch(function (err) {
					if ( err && 'AbortError' === err.name ) {
						return;
					}
					setEmptyPanelsRetryCount(function (n) { return n + 1; });
				});
		}, EMPTY_PANELS_RETRY_DELAY_MS);

		return function () {
			cancelled = true;
			controller.abort();
			clearTimeout(timer);
		};
	}, [ isLoading, feature, sidePanels.length, featureId, sidePanelId, emptyPanelsRetryCount ]);

	// Manual retry handler — resets the counter so the auto-retry effect runs
	// a fresh batch of attempts. Used by the empty-state "Try again" button.
	const handleEmptyPanelsManualRetry = () => {
		setEmptyPanelsRetryCount(0);
	};

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
					setToast({ status: 'error', message: __('Failed to refresh settings. Please try again.', 'buddyboss-platform') });
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

	/**
	 * Update properties on specific fields across all side panels.
	 *
	 * @param {Array}  fieldNames Array of field name strings to match.
	 * @param {Object} props      Properties to merge into matched fields.
	 */
	function updateFieldProps( fieldNames, props ) {
		setSidePanels( function( prevPanels ) {
			return prevPanels.map( function( panel ) {
				return Object.assign( {}, panel, {
					sections: ( panel.sections || [] ).map( function( section ) {
						return Object.assign( {}, section, {
							fields: ( section.fields || [] ).map( function( field ) {
								if ( -1 !== fieldNames.indexOf( field.name ) ) {
									return Object.assign( {}, field, props );
								}
								return field;
							} ),
						} );
					} ),
				} );
			} );
		} );
	}

	// Listen for field value updates dispatched by InputButtonField (e.g. after credential save).
	// Updates settings and side panel field defaults so notice fields reflect new connection status.
	useEffect( function() {
		function handleFieldValueUpdate( e ) {
			var updatedFields  = e.detail && e.detail.fields;
			var updatedOptions = e.detail && e.detail.field_options;

			if ( ( ! updatedFields || 'object' !== typeof updatedFields ) &&
				( ! updatedOptions || 'object' !== typeof updatedOptions ) ) {
				return;
			}

			// Update current settings state (values).
			if ( updatedFields && 'object' === typeof updatedFields ) {
				setSettings( function( prev ) {
					return Object.assign( {}, prev, updatedFields );
				} );
			}

			// Update field data in side panels so notice/other fields render updated content.
			// Notice fields render from `description`, other fields from `default`.
			// When field_options is provided, replace the field's options array (used by select fields).
			setSidePanels( function( prevPanels ) {
				return prevPanels.map( function( panel ) {
					return Object.assign( {}, panel, {
						sections: ( panel.sections || [] ).map( function( section ) {
							return Object.assign( {}, section, {
								fields: ( section.fields || [] ).map( function( field ) {
									var updates  = null;
									var hasValue = updatedFields && undefined !== updatedFields[ field.name ];
									var hasOpts  = updatedOptions && Array.isArray( updatedOptions[ field.name ] );

									if ( hasValue ) {
										updates                 = updates || {};
										updates.default         = updatedFields[ field.name ];
										// Notice fields render from description, so update that too.
										if ( 'notice' === field.type ) {
											updates.description = updatedFields[ field.name ];
										}
										// Update is_connected for input_button and bb_verify_popup fields.
										if ( undefined !== e.detail.is_connected && ( 'input_button' === field.type || 'bb_verify_popup' === field.type ) ) {
											updates.is_connected = e.detail.is_connected;
										}
									}

									if ( hasOpts ) {
										updates         = updates || {};
										updates.options = updatedOptions[ field.name ];
									}

									return updates ? Object.assign( {}, field, updates ) : field;
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

	// Listen for field disabled state updates (e.g., SSO provider toggle disables additional data fields).
	useEffect( function() {
		function handleFieldDisabledUpdate( e ) {
			var fieldNames = e.detail && e.detail.fields;
			var isDisabled = !! ( e.detail && e.detail.disabled );

			if ( ! fieldNames || ! Array.isArray( fieldNames ) ) {
				return;
			}

			updateFieldProps( fieldNames, { disabled: isDisabled } );
		}

		window.addEventListener( BB_EVENTS.FIELD_DISABLED_UPDATE, handleFieldDisabledUpdate );
		return function() {
			window.removeEventListener( BB_EVENTS.FIELD_DISABLED_UPDATE, handleFieldDisabledUpdate );
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
							message: __('Settings saved.', 'buddyboss-platform'),
						});
						setChangedFields({});

						// Merge any cross-feature flags pushed by the server back into
						// window.bbAdminData so section-level conditionals reading from
						// the `bbAdminData` source re-evaluate against fresh values
						// without a page reload (e.g. Login Redirects → Profile Type
						// Redirects section reacts to the `bp-member-type-enable-disable`
						// toggle that lives in the Members feature).
						if (
							response.data
							&& response.data.bbAdminDataUpdates
							&& 'object' === typeof response.data.bbAdminDataUpdates
							&& 'undefined' !== typeof window
							&& window.bbAdminData
						) {
							Object.assign( window.bbAdminData, response.data.bbAdminDataUpdates );
						}

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
							message: ( response.data && response.data.message ) || __('Something went wrong. Please try again.', 'buddyboss-platform'),
						});
					}
				})
				.catch(() => {
					setToast({
						status: 'error',
						message: __('Something went wrong. Please try again.', 'buddyboss-platform'),
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

	useAutoDismissToast( toast, setToast );

	// Pre-build lookup maps to avoid triple-nested loops in handleSettingChange.
	var buttonManagedFields = useMemo( function () {
		var managed = {};
		sidePanels.forEach( function ( panel ) {
			( panel.sections || [] ).forEach( function ( section ) {
				( section.fields || [] ).forEach( function ( field ) {
					if ( ( 'input_button' === field.type || 'bb_verify_popup' === field.type ) && Array.isArray( field.related_fields ) ) {
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

		setToast({ status: 'saving', message: __('Saving changes...', 'buddyboss-platform') });

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
			setHelpError(__('Failed to load help content. Please try again later.', 'buddyboss-platform'));
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

	// Hidden-panel fallback redirect — if the requested panel is hidden (e.g.,
	// bb_rl_enabled flipped off while the user was on Branding), fall back to
	// the first visible panel so the right pane never renders orphan content.
	//
	// Declared BEFORE the early returns below because React enforces a stable
	// hook order across renders. When `isLoading` flips from true to false the
	// component would otherwise call one more hook on the second render and
	// crash with "Rendered more hooks than during the previous render."
	// Data-gate happens inside the effect body.
	useEffect( function () {
		if ( isLoading || ! feature || ! sidePanels || ! sidePanels.length ) {
			return;
		}

		var currentPanel = sidePanels.find( function ( p ) {
			if ( p.id !== activePanelId ) {
				return false;
			}
			if ( p.conditional && 'disable' !== p.conditional.action ) {
				return evaluateConditional( p.conditional, settings );
			}
			return true;
		} );

		if ( currentPanel ) {
			return;
		}

		var firstVisible = sidePanels.find( function ( p ) {
			if ( p.conditional && 'disable' !== p.conditional.action ) {
				return evaluateConditional( p.conditional, settings );
			}
			return true;
		} );

		// Re-entry guard — also check `sidePanelId` (the URL-param panel) so
		// we don't ping-pong with the URL-sync effect at line ~213 when the
		// URL still points at the hidden panel.
		if ( firstVisible && firstVisible.id !== activePanelId && firstVisible.id !== sidePanelId ) {
			setActivePanelId( firstVisible.id );
			// Keep the URL in sync with the redirected panel — without this,
			// `?sidepanel=branding` stays stale in the address bar, and the
			// URL-sync effect would re-target the hidden panel and bounce
			// back here, visually flickering.
			if ( 'function' === typeof onNavigate ) {
				onNavigate( `/settings/${featureId}/${firstVisible.id}` );
			}
		}
	}, [ isLoading, feature, sidePanels, activePanelId, sidePanelId, settings, featureId, onNavigate ] );

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
				<h2>{__('Feature not found', 'buddyboss-platform')}</h2>
				<p>{__('The requested feature could not be found.', 'buddyboss-platform')}</p>
			</div>
		);
	}

	// Get the active side panel — filtered to only panels whose `conditional`
	// currently evaluates true. Without this gate, toggling Site Layout off
	// while a user is parked on Branding/Menus would leave those panels'
	// sections rendering on the right pane even though the left nav hides
	// them.
	const activePanel = sidePanels.find( function ( p ) {
		if ( p.id !== activePanelId ) {
			return false;
		}
		if ( p.conditional && 'disable' !== p.conditional.action ) {
			return evaluateConditional( p.conditional, settings );
		}
		return true;
	} );

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
						formValues={settings}
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
							{/* Appearance → General welcome banner (intro card above Site Name).
							    Always rendered on the General panel regardless of Site Layout —
							    the banner picks ReadyLaunch vs BuddyBoss Theme variant by
							    reading the live draft value of `settings.bb_rl_enabled`, so
							    flipping the Site Layout radio swaps the banner before the
							    auto-save round-trip completes. The wizard's first step lets
							    the admin choose between BuddyBoss Theme and ReadyLaunch, so
							    admins running the WordPress theme layout still need access
							    to the Setup Wizard button to switch. */}
							{ 'appearance' === featureId && 'general' === activePanelId && (
								<WelcomeBanner
									settings={ settings }
									onFieldChange={ handleSettingChange }
								/>
							) }
							{activePanel ? (
								<>
									{/* Render all sections within the active side panel */}
									{(activePanel.sections || []).map((section) => {
										// Check section-level conditional (hide or disable) via the
										// shared `evaluateConditional` util — same evaluator the
										// side-panel filter, field visibility, and group-first/last
										// memo use. Keeps conditional semantics in one place.
										var isSectionDisabled = false;
										var isSectionHidden = false;
										if ( section.conditional ) {
											var condMet = evaluateConditional( section.conditional, settings );
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

										// When a section is conditionally disabled, also skip it
										// if every one of its fields is hidden — either hidden-type
										// (form-state only) or hidden by its own conditional. This
										// is scoped to disabled sections so it can't surprise any
										// feature that intentionally renders a section header with
										// no fields. Covers the case where an admin can't act on a
										// section anyway and every field has hidden itself for
										// other reasons (e.g. reCAPTCHA Design when version is v3 —
										// each design field hides itself by version, leaving an
										// empty greyed header that looks broken).
										if ( isSectionDisabled ) {
											var hasVisibleField = ( section.fields || [] ).some( function ( f ) {
												if ( 'hidden' === f.type ) {
													return false;
												}
												if ( ! f.conditional ) {
													return true;
												}
												if ( 'disable' === f.conditional.action ) {
													return true;
												}
												return evaluateConditional( f.conditional, settings );
											} );

											if ( ! hasVisibleField ) {
												return null;
											}
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
													{/* Section-level PRO badge (e.g. UPGRADE PRO).
													    Section badges always open the BuddyBoss pricing
													    page in a new tab — they do not trigger the
													    field-upgrades modal. Only field-level pro badges
													    open UpgradeModal in-page. */}
													{section.pro_notice && section.pro_notice.show && (
														<span className="bb-admin-feature-settings__section-pro-notice">
															<a
																href="https://www.buddyboss.com/pricing/"
																target="_blank"
																rel="noopener noreferrer"
																className="bb-admin-feature-settings__section-pro-badge"
															>
																<i className={section.pro_notice.badge_icon || 'bb-icons-rl-crown-simple'} />
																<span>{section.pro_notice.badge_text || 'UPGRADE PRO'}</span>
															</a>
														</span>
													)}
													{/* Help icon — section-level overrides panel-level when both are set. */}
													{( section.help_url || activePanel.help_url ) && (
														<HelpIcon
															onClick={handleHelpClick}
															contentId={section.help_url || activePanel.help_url}
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
												{/* Opt-in: skip rendering fields entirely when section toggle
												    is OFF and `hide_fields_when_off` is set. Default UX (dim at
												    50% + freeze) is preserved when this prop is absent. */}
												{ ! ( isSectionToggleOff && section.hide_fields_when_off ) && (
													<SettingsForm
														fields={section.fields || []}
														values={settings}
														onChange={handleSettingChange}
														onProBadgeClick={handleFieldProClick}
														disabled={isSectionDisabled}
													/>
												) }
											</div>
										</div>
									);
									})}
								</>
							) : (
								// Two distinct empty states share this slot:
								//
								// 1. sidePanels.length === 0 — feature loaded but the
								//    server returned no panels. Most common cause is the
								//    enable→click-Settings race where BB_Feature_Loader
								//    hasn't yet included the admin settings file. While
								//    we're under the retry cap the auto-retry effect
								//    above is silently re-fetching, so we show an
								//    "Activating…" spinner. After the cap, we surface a
								//    manual retry button.
								//
								// 2. sidePanels.length > 0 but no activePanelId selected
								//    — extremely rare in the current router (the load
								//    code always picks a default panel) but kept as a
								//    safety net so the slot is never blank.
								sidePanels.length === 0 ? (
									<div className="bb-admin-feature-settings__no-section bb-admin-feature-settings__no-section--activating">
										{ emptyPanelsRetryCount < EMPTY_PANELS_MAX_RETRIES ? (
											<>
												<Spinner />
												<p>
													{ feature && feature.label
														? sprintf(
															/* translators: %s: feature label being activated. */
															__( 'Activating %s… this should only take a moment.', 'buddyboss-platform' ),
															feature.label
														)
														: __( 'Activating feature… this should only take a moment.', 'buddyboss-platform' )
													}
												</p>
											</>
										) : (
											<>
												<p>
													{ __( 'Couldn\'t load settings. The feature may not be fully active yet.', 'buddyboss-platform' ) }
												</p>
												<Button
													variant="secondary"
													onClick={handleEmptyPanelsManualRetry}
												>
													{ __( 'Try again', 'buddyboss-platform' ) }
												</Button>
											</>
										) }
									</div>
								) : (
									<div className="bb-admin-feature-settings__no-section">
										<p>{__('Please select a panel from the sidebar.', 'buddyboss-platform')}</p>
									</div>
								)
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
				title={( helpContent && helpContent.title ) || __('Help', 'buddyboss-platform')}
			>
				{isHelpLoading ? (
					<div className="help-content-loading">
						<Spinner />
						<p>{__('Loading help content...', 'buddyboss-platform')}</p>
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
									title={__('Video tutorial', 'buddyboss-platform')}
									frameBorder="0"
									allowFullScreen
								></iframe>
							</div>
						)}
						<div
							className="help-content"
							dangerouslySetInnerHTML={{ __html: sanitizeKbArticle( helpContent.content ) }}
						/>
						{ ( () => {
							// `safeImageUrl()` returns null for any URL that fails the
							// scheme/HTTPS guard — falsy short-circuits the render. Using
							// the KB-aware validator (not the generic `safeUrl()` which
							// returns `'#'` on reject) keeps this hero image consistent
							// with the inline images inside the article body above.
							const heroSrc = helpContent.imageUrl ? safeImageUrl( helpContent.imageUrl ) : null;
							return heroSrc ? (
								<img
									src={ heroSrc }
									alt={__('Help content illustration', 'buddyboss-platform')}
									style={{ width: '100%', borderRadius: 8, marginBottom: 16 }}
								/>
							) : null;
						} )() }
					</>
				) : (
					<p>{__('No help content available.', 'buddyboss-platform')}</p>
				)}
			</HelpSliderModal>

			{/* Upgrade modal — opened by field- and section-level pro badges
			    when the field-upgrades catalog provides modal copy. */}
			{proUpgradeModalPayload && (
				<UpgradeModal
					feature={proUpgradeModalPayload}
					onClose={() => setProUpgradeModalPayload(null)}
				/>
			)}
		</div>
	);
}
