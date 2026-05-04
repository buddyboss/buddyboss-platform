/**
 * BuddyBoss Admin Settings 2.0 - Settings Grid Screen
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

import { useState, useEffect, useRef, useMemo } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import { Button, Spinner, ToggleControl } from '@wordpress/components';
import { getCachedFeatures, toggleFeature, updateFeatureInCache } from '../utils/ajax';
import { invalidateFeatureCache } from '../utils/featureCache';
import { urlToRoute } from '../utils/url';
import { safeUrl } from '../utils/sanitize';
import { Toast } from '../components/Toast';
import { UpgradeModal } from '../components/modals/UpgradeModal';
import { ConfirmToggleModal } from '../components/modals/ConfirmToggleModal';

/**
 * Settings Screen Component
 *
 * @param {Object} props Component props
 * @param {Function} props.onNavigate Navigation callback
 * @returns {JSX.Element} Settings screen
 */
export function SettingsScreen({ onNavigate }) {
	const [features, setFeatures] = useState([]);
	const [placeholderFeatures, setPlaceholderFeatures] = useState([]);
	const [isLoading, setIsLoading] = useState(true);
	const [activeFilter, setActiveFilter] = useState('all'); // 'all', 'active', 'inactive'
	const [selectedCategory, setSelectedCategory] = useState(''); // 'community', 'add-ons', 'integrations'
	const [searchQuery, setSearchQuery] = useState('');
	const [toast, setToast] = useState(null);
	const [upgradeModal, setUpgradeModal] = useState(null); // { feature } or null
	// Pending feature toggle awaiting confirmation. Populated when the admin
	// tries to disable a feature whose registration includes
	// confirm_off_message; cleared on either confirm (after the real toggle
	// runs) or cancel. Holding { feature, checked } lets the same modal
	// pattern accommodate a future confirm_on_* flow without restructuring.
	const [pendingToggle, setPendingToggle] = useState(null);

	// Set of feature IDs whose toggle AJAX is currently in flight. Surfaced to
	// the feature card so the "Settings" button is disabled (with a spinner)
	// while activation is still propagating server-side. Without this, an
	// admin who clicks Settings immediately after enabling a feature can land
	// on the feature page before its BB_Feature_Loader has registered any
	// admin panels — leaving an empty sidebar and the misleading
	// "Please select a panel from the sidebar." message. Tracked as a Set so
	// concurrent toggles on different features don't trample each other.
	const [togglingFeatureIds, setTogglingFeatureIds] = useState(() => new Set());

	useEffect(() => {
		// Load features via shared cache (prevents duplicate AJAX calls with Router)
		getCachedFeatures()
			.then((data) => {
				if (Array.isArray(data)) {
					// Separate real features from placeholder features.
					var realFeatures = data.filter(function( item ) { return ! item.is_placeholder; });
					var placeholders = data.filter(function( item ) { return !! item.is_placeholder; });
					setFeatures(realFeatures);
					setPlaceholderFeatures(placeholders);
				} else {
					setFeatures([]);
					setPlaceholderFeatures([]);
				}
				setIsLoading(false);
			})
			.catch(function () {
				setFeatures([]);
				setPlaceholderFeatures([]);
				setIsLoading(false);
			});
	}, []);

	// Derive filtered features via useMemo (avoids double-render from useEffect + setState).
	var filteredFeatures = useMemo( function () {
		var filtered = features.slice();
		var showPlaceholders = true;

		// Filter by status — placeholders never appear in Active/Inactive.
		if ( 'all' !== activeFilter ) {
			filtered = filtered.filter( function ( feature ) {
				return feature.status === activeFilter;
			} );
			showPlaceholders = false;
		}

		// Filter by category.
		if ( selectedCategory ) {
			filtered = filtered.filter( function ( feature ) {
				return feature.category === selectedCategory;
			} );
		}

		// Filter by search — placeholders excluded from search results.
		if ( searchQuery && searchQuery.length >= 2 ) {
			var queryLower = searchQuery.toLowerCase();
			filtered = filtered.filter( function ( feature ) {
				return feature.label.toLowerCase().indexOf( queryLower ) !== -1 ||
				       ( feature.description && feature.description.toLowerCase().indexOf( queryLower ) !== -1 );
			} );
			showPlaceholders = false;
		}

		// Append placeholders for "All" and category views (not Active/Inactive/Search).
		if ( showPlaceholders ) {
			var filteredPlaceholders = placeholderFeatures.slice();

			if ( selectedCategory ) {
				filteredPlaceholders = filteredPlaceholders.filter( function ( feature ) {
					return feature.category === selectedCategory;
				} );
			}

			filtered = filtered.concat( filteredPlaceholders );
		}

		// Re-sort the merged list by (order, label) so placeholders interleave
		// with registered features by their declared `order` value within
		// each category. Without this, the concat above would leave every
		// placeholder at the end of its category — making, for example,
		// Offload Media (registered, order 20) appear before Gamification
		// (placeholder, order 10) inside the Add-ons section. PHP already
		// runs an identical sort in bb_admin_sort_features_response, but
		// splitting the response into real-vs-placeholder buckets on the
		// client (so we can hide placeholders on Active/Inactive/Search
		// tabs) clobbers that order. We sort by `order` only; category
		// grouping happens just below in groupedFeatures.
		filtered.sort( function ( a, b ) {
			var aOrder = ( 'number' === typeof a.order ) ? a.order : 100;
			var bOrder = ( 'number' === typeof b.order ) ? b.order : 100;
			if ( aOrder !== bOrder ) {
				return aOrder - bOrder;
			}
			var aLabel = a.label || '';
			var bLabel = b.label || '';
			return aLabel.localeCompare( bLabel );
		} );

		return filtered;
	}, [ features, placeholderFeatures, activeFilter, selectedCategory, searchQuery ] );

	// Group features by category with defined display order.
	const categoryOrder = [ 'community', 'add-ons', 'integrations' ];
	const groupedFeatures = filteredFeatures.reduce((acc, feature) => {
		const category = feature.category || 'community';
		if (!acc[category]) {
			acc[category] = [];
		}
		acc[category].push(feature);
		return acc;
	}, {});

	// Sort categories into the defined display order.
	const sortedGroupedFeatures = {};
	categoryOrder.forEach(function( cat ) {
		if ( groupedFeatures[ cat ] ) {
			sortedGroupedFeatures[ cat ] = groupedFeatures[ cat ];
		}
	});
	// Append any categories not in the predefined order.
	Object.keys( groupedFeatures ).forEach(function( cat ) {
		if ( ! sortedGroupedFeatures[ cat ] ) {
			sortedGroupedFeatures[ cat ] = groupedFeatures[ cat ];
		}
	});

	// Get filter counts — only real features count (not placeholders).
	var allCount = features.length + placeholderFeatures.length;
	const filterCounts = {
		all: allCount,
		active: features.filter(function( item ) { return 'active' === item.status; }).length,
		inactive: features.filter(function( item ) { return 'inactive' === item.status; }).length,
	};

	// Get category counts — include placeholders so their categories appear in the dropdown.
	var allForCategories = [].concat(features, placeholderFeatures);
	const categoryCounts = allForCategories.reduce(function( acc, feature ) {
		var category = feature.category || 'community';
		acc[category] = (acc[category] || 0) + 1;
		return acc;
	}, {});

	// Auto-dismiss success toast after 3 seconds.
	useEffect(() => {
		if (!toast) return;

		if ('success' === toast.status) {
			const timer = setTimeout(() => {
				setToast(null);
			}, 3000);
			return () => clearTimeout(timer);
		}
	}, [toast]);

	// Track in-flight toggle requests per feature for abort on rapid clicks.
	const toggleControllers = useRef({});

	const handleFeatureToggle = (featureId, checked) => {
		// Guard: never toggle placeholder or DRM-locked features.
		var isPlaceholder = placeholderFeatures.some(function( item ) { return item.id === featureId; });
		if ( isPlaceholder ) {
			return;
		}
		var isDrmLocked = features.some(function( item ) { return item.id === featureId && item.is_drm_locked; });
		if ( isDrmLocked ) {
			return;
		}

		// Confirm-on-disable intercept. Any feature can opt in by registering
		// `confirm_off_message` (and optional confirm_off_title / _ok / _cancel
		// / _destructive) on its bb_register_feature() call. When the admin
		// flips the toggle off we stash the intent in pendingToggle and let
		// the ConfirmToggleModal render below; the actual optimistic update +
		// AJAX runs from runFeatureToggle once the admin confirms.
		if ( ! checked ) {
			var featureForConfirm = features.find( function ( item ) { return item.id === featureId; } );
			if ( featureForConfirm && featureForConfirm.confirm_off_message ) {
				setPendingToggle( { feature: featureForConfirm, checked: checked } );
				return;
			}
		}

		runFeatureToggle( featureId, checked );
	};

	// Real toggle work — extracted from handleFeatureToggle so the
	// ConfirmToggleModal "Disable" button can re-enter the same flow without
	// re-running the placeholder/DRM guards or the confirm-intercept above.
	const runFeatureToggle = (featureId, checked) => {
		const newStatus = checked ? 'active' : 'inactive';
		const prevStatus = checked ? 'inactive' : 'active';

		// Get feature label for toast message.
		const currentFeature = features.find((item) => item.id === featureId);
		const featureLabel = currentFeature?.label || featureId;

		// Show saving toast.
		setToast({ status: 'saving', message: __('Saving changes...', 'buddyboss') });

		// 1. Optimistic update — instant UI feedback.
		setFeatures((prev) =>
			prev.map((item) => (item.id === featureId ? { ...item, status: newStatus } : item))
		);
		updateFeatureInCache(featureId, { status: newStatus });

		// 2. Abort any in-flight request for this feature.
		if (toggleControllers.current[featureId]) {
			toggleControllers.current[featureId].abort();
		}
		const controller = new AbortController();
		toggleControllers.current[featureId] = controller;

		// Mark this feature as toggling so the card's "Settings" button is
		// disabled until the AJAX settles. Removed in every completion path
		// below — success, error, and abort.
		setTogglingFeatureIds((prev) => {
			const next = new Set(prev);
			next.add(featureId);
			return next;
		});
		const clearToggling = () => {
			setTogglingFeatureIds((prev) => {
				if ( ! prev.has(featureId) ) {
					return prev;
				}
				const next = new Set(prev);
				next.delete(featureId);
				return next;
			});
		};

		// 3. Fire AJAX in the background.
		toggleFeature(featureId, checked, { signal: controller.signal })
			.then((response) => {
				// Clean up ref.
				if (toggleControllers.current[featureId] === controller) {
					delete toggleControllers.current[featureId];
				}
				clearToggling();

				if (response.success) {
					// Confirm with server data.
					const updatedFeature = response.data?.data;
					const deactivatedDependents = response.data?.deactivated_dependents || [];
					const reactivatableDependents = response.data?.reactivatable_dependents || [];

					setFeatures((prev) =>
						prev.map((item) => {
							if (item.id === featureId) {
								return { ...item, ...updatedFeature };
							}
							// Cascade: mark all dependent features as unavailable (greyed out).
							// Only force 'inactive' if they were active; already-inactive ones just get greyed out.
							if (deactivatedDependents.indexOf(item.id) !== -1) {
								return { ...item, status: 'active' === item.status ? 'inactive' : item.status, available: false };
							}
							// Re-activation: dependents become available again (but stay inactive).
							if (reactivatableDependents.indexOf(item.id) !== -1) {
								return { ...item, available: true };
							}
							return item;
						})
					);
					updateFeatureInCache(featureId, updatedFeature);
					deactivatedDependents.forEach(function (depId) {
						updateFeatureInCache(depId, { available: false });
					});
					reactivatableDependents.forEach(function (depId) {
						updateFeatureInCache(depId, { available: true });
					});

					// Invalidate all feature settings caches so dependent features
					// (e.g. Reactions depends on Activity) fetch fresh data.
					invalidateFeatureCache();

					// Toggle admin submenu visibility for add-on features with their own admin page.
					// Only for external pages (page != bb-settings), not internal Settings 2.0 routes.
					if ( currentFeature && currentFeature.settings_route && currentFeature.settings_route.startsWith( 'http' ) ) {
						try {
							var routeUrl = new URL( currentFeature.settings_route );
							var pageSlug = routeUrl.searchParams.get( 'page' );
							if ( pageSlug && 'bb-settings' !== pageSlug ) {
								var menuLink = document.querySelector( '#adminmenu a[href*="page=' + CSS.escape( pageSlug ) + '"]' );
								if ( menuLink ) {
									// Menu item exists in DOM — toggle visibility.
									var menuItem = menuLink.closest( 'li' );
									if ( menuItem ) {
										menuItem.style.display = checked ? '' : 'none';
									}
								} else if ( checked ) {
									// Menu item was never rendered server-side — reload
									// so WordPress registers it properly with correct
									// classes, position, and label.
									window.location.reload();
								}
							}
						} catch ( e ) {
							// Ignore URL parsing errors.
						}
					}

					// Show success toast.
					const successMessage = checked
						? sprintf( __( '%s has been enabled.', 'buddyboss' ), featureLabel )
						: sprintf( __( '%s has been disabled.', 'buddyboss' ), featureLabel );
					setToast({ status: 'success', message: successMessage });
				} else {
					// Server rejected — revert.
					setFeatures((prev) =>
						prev.map((item) =>
							item.id === featureId ? { ...item, status: prevStatus } : item
						)
					);
					updateFeatureInCache(featureId, { status: prevStatus });

					// Show error toast.
					setToast({
						status: 'error',
						message: response.data?.message || __('Failed to update feature. Please try again.', 'buddyboss'),
					});
				}
			})
			.catch((error) => {
				// Aborted by a newer click — the newer request still owns the
				// "toggling" state (it added itself before this catch fires),
				// so we don't clear it here. The other completion paths handle it.
				if ( 'AbortError' === error.name ) {
					return;
				}

				// Clean up ref.
				if (toggleControllers.current[featureId] === controller) {
					delete toggleControllers.current[featureId];
				}
				clearToggling();

				// Network/other error — revert.
				setFeatures((prev) =>
					prev.map((item) =>
						item.id === featureId ? { ...item, status: prevStatus } : item
					)
				);
				updateFeatureInCache(featureId, { status: prevStatus });

				// Show error toast.
				setToast({
					status: 'error',
					message: __('Failed to update feature. Please try again.', 'buddyboss'),
				});
			});
	};

	/**
	 * Handle addon install/activate via mothership AJAX.
	 *
	 * @param {Object} feature  Placeholder feature object with plugin_slug.
	 * @param {string} action   'mosh_addon_install' or 'mosh_addon_activate'.
	 */
	const handleAddonAction = (feature, action) => {
		if ( ! feature.plugin_slug || ! window.bbAdminData.addonNonce ) {
			return;
		}

		var label = feature.label || feature.id;
		var isInstall = 'mosh_addon_install' === action;

		setToast({
			status: 'saving',
			message: isInstall
				? sprintf( __( 'Installing & activating %s...', 'buddyboss' ), label )
				: sprintf( __( 'Activating %s...', 'buddyboss' ), label ),
		});

		var formData = new FormData();
		formData.append('action', action);
		formData.append('_ajax_nonce', window.bbAdminData.addonNonce);
		formData.append('slug', feature.plugin_slug);
		formData.append('extension_type', 'plugin');

		fetch(window.bbAdminData.ajaxUrl, { method: 'POST', body: formData })
			.then(function( response ) { return response.json(); })
			.then(function( response ) {
				if ( response && response.success ) {
					setToast({
						status: 'success',
						message: isInstall
							? sprintf( __( '%s has been installed and activated.', 'buddyboss' ), label )
							: sprintf( __( '%s has been activated.', 'buddyboss' ), label ),
					});
					// Reload to show the real feature card.
					setTimeout(function() { window.location.reload(); }, 1500);
				} else {
					var errorMsg = ( response && response.data && response.data.message )
						? response.data.message
						: __('Failed to process. Please try again.', 'buddyboss');
					setToast({ status: 'error', message: errorMsg });
				}
			})
			.catch(function() {
				setToast({
					status: 'error',
					message: __('Failed to process. Please try again.', 'buddyboss'),
				});
			});
	};

	if (isLoading) {
		return (
			<div className="bb-admin-settings bb-admin-loading">
				<Spinner />
			</div>
		);
	}

	return (
		<div className="bb-admin-settings">
			<div className="bb-admin-settings__container">
				{/* Filter Bar */}
				<div className="bb-admin-settings__filters">
					<div className="bb-admin-settings__filter-tabs">
						<button
							className={`bb-admin-settings__filter-tab ${ 'all' === activeFilter ? 'bb-admin-settings__filter-tab--active' : '' }`}
							onClick={() => setActiveFilter('all')}
						>
							{__('All', 'buddyboss')} ({filterCounts.all})
						</button>
						<button
							className={`bb-admin-settings__filter-tab ${ 'active' === activeFilter ? 'bb-admin-settings__filter-tab--active' : '' }`}
							onClick={() => setActiveFilter('active')}
						>
							{__('Active', 'buddyboss')} ({filterCounts.active})
						</button>
						<button
							className={`bb-admin-settings__filter-tab ${ 'inactive' === activeFilter ? 'bb-admin-settings__filter-tab--active' : '' }`}
							onClick={() => setActiveFilter('inactive')}
						>
							{__('Inactive', 'buddyboss')} ({filterCounts.inactive})
						</button>
					</div>

					<div className="bb-admin-settings__filter-right">
						<select
							className="bb-admin-settings__select bb-admin-settings__filter-select"
							value={selectedCategory}
							onChange={(e) => setSelectedCategory(e.target.value)}
						>
							<option value="">{__('Category', 'buddyboss')}</option>
							{Object.keys(categoryCounts).map((category) => (
								<option key={category} value={category}>
									{ 'community' === category
										? __('Community', 'buddyboss')
										: 'add-ons' === category
										? __('Add-ons', 'buddyboss')
										: __('Integrations', 'buddyboss') }
								</option>
							))}
						</select>
					</div>
				</div>

				{/* Feature Grid */}
				<div className="bb-admin-settings__grid">
					{Object.entries(sortedGroupedFeatures).map(([category, categoryFeatures]) => (
						<div key={category} className="bb-admin-settings__category">
							{/* Category Divider */}
							<div className={ 'community' === category ? 'bb-admin-settings__category-divider' : 'bb-admin-settings__category-divider bb-admin-settings__category-divider--with-line' }>
								<h2 className="bb-admin-settings__category-title">
									{ 'community' === category
										? __('BUDDYBOSS COMMUNITY SETTINGS', 'buddyboss')
										: 'add-ons' === category
										? __('BUDDYBOSS ADD-ONS', 'buddyboss')
										: __('BUDDYBOSS INTEGRATIONS', 'buddyboss') }
								</h2>
							</div>

							{/* Features Grid */}
							<div className="bb-admin-settings__features-grid">
								{categoryFeatures.map((feature) => (
									<div
										key={feature.id}
										className={`bb-admin-settings__feature-card bb-admin-settings__feature-card--${feature.status}${!feature.available && !feature.is_placeholder && !feature.is_drm_locked ? ' bb-admin-settings__feature-card--unavailable' : ''}${feature.is_placeholder ? ' bb-admin-settings__feature-card--placeholder' : ''}${feature.is_drm_locked ? ' bb-admin-settings__feature-card--drm-locked' : ''}`}
									>
										{/* Plan Badge — only for upgrade (not_in_plan) or DRM-locked features */}
										{((feature.is_placeholder && 'not_in_plan' === feature.plugin_status) || feature.is_drm_locked) && feature.upgrade_tier && (
											<button
												className={`bb-admin-settings__plan-badge bb-admin-settings__plan-badge--${feature.upgrade_tier}`}
												onClick={() => setUpgradeModal({ feature: feature })}
												type="button"
											>
												<i className="bb-icons-rl bb-icons-rl-crown-simple"></i>
												{'plus' === feature.upgrade_tier
													? __('UPGRADE PLUS', 'buddyboss')
													: __('UPGRADE PRO', 'buddyboss')}
											</button>
										)}
										{/* Card Body */}
										<div className="bb-admin-settings__feature-body">
											{/* Top Section: Icon + Title */}
											<div className="bb-admin-settings__feature-top">
												<div className="bb-admin-settings__feature-name">
													<div className="bb-admin-settings__feature-icon-frame">
														{(() => {
															// Handle icon data structure from REST API
															if (!feature.icon) {
																return <span className="dashicons dashicons-admin-generic"></span>;
															}

															// If icon has nested data (from registered icons)
															const iconData = feature.icon.data || feature.icon;
															const iconType = feature.icon.type || iconData.type;

															if ( 'dashicon' === iconType ) {
																const slug = feature.icon.slug || iconData.slug || 'dashicons-admin-generic';
																return <span className={`dashicons ${slug}`}></span>;
															}

															if ( 'svg' === iconType ) {
																const url = safeUrl( feature.icon.url || iconData.url || iconData.data_uri || (iconData.data && iconData.data.url) || (iconData.data && iconData.data.data_uri) || '' );
																if (url && '#' !== url) {
																	return <img src={url} alt={feature.label} className="bb-admin-settings__feature-icon-img" />;
																}
															}

															if ( 'image' === iconType ) {
																const url = safeUrl( feature.icon.url || iconData.url || iconData.path || (iconData.data && iconData.data.url) || (iconData.data && iconData.data.path) || '' );
																if (url && '#' !== url) {
																	return <img src={url} alt={feature.label} className="bb-admin-settings__feature-icon-img" />;
																}
															}

															if ( 'font' === iconType ) {
																const className = feature.icon.class || iconData.class || (iconData.data && iconData.data.class);
																if (className) {
																	return <span className={className}></span>;
																}
															}

															// Fallback
															return <span className="dashicons dashicons-admin-generic"></span>;
														})()}
													</div>
													<h3 className="bb-admin-settings__feature-title">{feature.label}</h3>
												</div>
											</div>

											{/* Description */}
											<p className="bb-admin-settings__feature-description">
												{feature.description || __('No description available.', 'buddyboss')}
											</p>
										</div>

										{/* Bottom Section: Settings Button + Toggle */}
										<div className="bb-admin-settings__feature-bottom">
											<div className="bb-admin-settings__feature-left">
												{ feature.is_placeholder && 'not_installed' === feature.plugin_status && feature.plugin_slug ? (
													<Button
														variant="secondary"
														className="bb-admin-settings__feature-settings-btn"
														onClick={() => handleAddonAction(feature, 'mosh_addon_install')}
													>
														{__('Install & Activate', 'buddyboss')}
													</Button>
												) : feature.is_placeholder && 'installed_inactive' === feature.plugin_status && feature.plugin_slug ? (
													<Button
														variant="secondary"
														className="bb-admin-settings__feature-settings-btn"
														onClick={() => handleAddonAction(feature, 'mosh_addon_activate')}
													>
														{__('Activate', 'buddyboss')}
													</Button>
												) : feature.is_placeholder ? (
													<Button
														variant="secondary"
														className="bb-admin-settings__feature-settings-btn bb-admin-settings__feature-settings-btn--disabled bb-admin-settings__feature-settings-btn--placeholder"
														disabled
													>
														<i className="bb-icon-settings"></i>
														{__('Settings', 'buddyboss')}
													</Button>
												) : feature.settings_route ? (
													( () => {
														// While the toggle AJAX is in flight, disable the
														// Settings button — clicking through too early
														// races BB_Feature_Loader and lands the admin on a
														// feature page with no registered side panels.
														var isTogglingThis = togglingFeatureIds.has(feature.id);
														var isDisabled = feature.status !== 'active' || !!feature.is_drm_locked || isTogglingThis;
														// Direction is read from the optimistically-updated
														// `feature.status` — by the time this renders the
														// optimistic flip has already applied, so 'active'
														// means we're transitioning ON and 'inactive' means
														// we're transitioning OFF.
														var isDeactivating = isTogglingThis && 'active' !== feature.status;
														return (
															<Button
																variant="secondary"
																className={`bb-admin-settings__feature-settings-btn${isDisabled ? ' bb-admin-settings__feature-settings-btn--disabled' : ''}${isTogglingThis ? ' bb-admin-settings__feature-settings-btn--activating' : ''}`}
																onClick={() => {
																	if ( feature.is_drm_locked || isTogglingThis ) {
																		return;
																	}
																	// External URL (add-on plugins with own settings page, not bb-settings).
																	if ( feature.settings_route && feature.settings_route.startsWith( 'http' ) && ! feature.settings_route.includes( 'page=bb-settings' ) ) {
																		window.location.href = feature.settings_route;
																	} else {
																		onNavigate( urlToRoute( feature.settings_route ) );
																	}
																}}
																disabled={isDisabled}
																aria-busy={isTogglingThis ? 'true' : undefined}
															>
																{ isTogglingThis ? (
																	<>
																		<Spinner />
																		{ isDeactivating
																			? __('Deactivating…', 'buddyboss')
																			: __('Activating…', 'buddyboss')
																		}
																	</>
																) : (
																	<>
																		<i className="bb-icon-settings"></i>
																		{__('Settings', 'buddyboss')}
																	</>
																) }
															</Button>
														);
													} )()
												) : null }
											</div>
											<div className="bb-admin-settings__feature-right">
												<ToggleControl
													className={`components-form-toggle--is-big${feature.is_placeholder ? ' bb-admin-settings__toggle--placeholder' : ''}${feature.is_drm_locked ? ' bb-admin-settings__toggle--drm-locked' : ''}`}
													checked={ 'active' === feature.status }
													onChange={(checked) => handleFeatureToggle(feature.id, checked)}
													disabled={!feature.available || feature.required || !!feature.is_placeholder || !!feature.is_drm_locked}
													__nextHasNoMarginBottom
												/>
												<span className="screen-reader-text">
													{ sprintf(
														/* translators: %s: feature label */
														__( 'Toggle %s', 'buddyboss' ),
														feature.label
													) }
												</span>
											</div>
										</div>
									</div>
								))}
							</div>
						</div>
					))}
				</div>

				{filteredFeatures.length === 0 && (
					<div className="bb-admin-settings__empty">
						<p>{__('No features found matching your filters.', 'buddyboss')}</p>
					</div>
				)}
			</div>

			{/* Toast notification for feature toggle status */}
			{toast && (
				<div className="bb-toast-container">
					<Toast
						status={toast.status}
						message={toast.message}
						onDismiss={() => setToast(null)}
					/>
				</div>
			)}

			{/* Upgrade Modal for placeholder features */}
			{upgradeModal && (
				<UpgradeModal
					feature={upgradeModal.feature}
					onClose={() => setUpgradeModal(null)}
				/>
			)}

			{/* Confirm-on-disable modal for features that registered confirm_off_message */}
			{pendingToggle && pendingToggle.feature && (
				<ConfirmToggleModal
					isOpen={true}
					title={pendingToggle.feature.confirm_off_title}
					message={pendingToggle.feature.confirm_off_message}
					messageIsHtml={!!pendingToggle.feature.confirm_off_message_is_html}
					confirmLabel={pendingToggle.feature.confirm_off_ok}
					cancelLabel={pendingToggle.feature.confirm_off_cancel}
					isDestructive={
						undefined === pendingToggle.feature.confirm_off_destructive
							? true
							: !!pendingToggle.feature.confirm_off_destructive
					}
					onConfirm={() => {
						const pending = pendingToggle;
						setPendingToggle(null);
						runFeatureToggle(pending.feature.id, pending.checked);
					}}
					onCancel={() => setPendingToggle(null)}
				/>
			)}
		</div>
	);
}
