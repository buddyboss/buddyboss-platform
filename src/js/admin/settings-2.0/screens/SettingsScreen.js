/**
 * BuddyBoss Admin Settings 2.0 - Settings Grid Screen
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

import { useState, useEffect, useRef } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Button, Spinner, ToggleControl } from '@wordpress/components';
import { getCachedFeatures, toggleFeature, updateFeatureInCache } from '../utils/ajax';
import { invalidateFeatureCache } from '../utils/featureCache';
import { urlToRoute } from '../utils/url';
import { Toast } from '../components/Toast';
import { UpgradeModal } from '../components/modals/UpgradeModal';

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
	const [filteredFeatures, setFilteredFeatures] = useState([]);
	const [isLoading, setIsLoading] = useState(true);
	const [activeFilter, setActiveFilter] = useState('all'); // 'all', 'active', 'inactive'
	const [selectedCategory, setSelectedCategory] = useState(''); // 'community', 'add-ons', 'integrations'
	const [searchQuery, setSearchQuery] = useState('');
	const [toast, setToast] = useState(null);
	const [upgradeModal, setUpgradeModal] = useState(null); // { feature } or null

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
					setFilteredFeatures(realFeatures);
				} else {
					setFeatures([]);
					setPlaceholderFeatures([]);
					setFilteredFeatures([]);
				}
				setIsLoading(false);
			})
			.catch((error) => {
				console.error('Failed to load features:', error);
				setFeatures([]);
				setPlaceholderFeatures([]);
				setFilteredFeatures([]);
				setIsLoading(false);
			});
	}, []);

	// Filter features — placeholders excluded from Active/Inactive tabs and search.
	useEffect(() => {
		var filtered = [].concat(features);
		var showPlaceholders = true;

		// Filter by status — placeholders never appear in Active/Inactive.
		if (activeFilter !== 'all') {
			filtered = filtered.filter(function( feature ) { return feature.status === activeFilter; });
			showPlaceholders = false;
		}

		// Filter by category.
		if (selectedCategory) {
			filtered = filtered.filter(function( feature ) { return feature.category === selectedCategory; });
		}

		// Filter by search — placeholders excluded from search results.
		if (searchQuery && searchQuery.length >= 2) {
			var queryLower = searchQuery.toLowerCase();
			filtered = filtered.filter(function( feature ) {
				return feature.label.toLowerCase().indexOf(queryLower) !== -1 ||
					(feature.description && feature.description.toLowerCase().indexOf(queryLower) !== -1);
			});
			showPlaceholders = false;
		}

		// Append placeholders for "All" and category views (not Active/Inactive/Search).
		if (showPlaceholders) {
			var filteredPlaceholders = [].concat(placeholderFeatures);
			if (selectedCategory) {
				filteredPlaceholders = filteredPlaceholders.filter(function( feature ) {
					return feature.category === selectedCategory;
				});
			}
			filtered = filtered.concat(filteredPlaceholders);
		}

		setFilteredFeatures(filtered);
	}, [features, placeholderFeatures, activeFilter, selectedCategory, searchQuery]);

	// Group features by category
	const groupedFeatures = filteredFeatures.reduce((acc, feature) => {
		const category = feature.category || 'community';
		if (!acc[category]) {
			acc[category] = [];
		}
		acc[category].push(feature);
		return acc;
	}, {});

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

		// 3. Fire AJAX in the background.
		toggleFeature(featureId, checked, { signal: controller.signal })
			.then((response) => {
				// Clean up ref.
				if (toggleControllers.current[featureId] === controller) {
					delete toggleControllers.current[featureId];
				}

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
						? __('%s has been enabled.', 'buddyboss').replace('%s', featureLabel)
						: __('%s has been disabled.', 'buddyboss').replace('%s', featureLabel);
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
				// Aborted by a newer click — do nothing, the newer request owns the UI.
				if ( 'AbortError' === error.name ) {
					return;
				}

				// Clean up ref.
				if (toggleControllers.current[featureId] === controller) {
					delete toggleControllers.current[featureId];
				}

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
					{Object.entries(groupedFeatures).map(([category, categoryFeatures]) => (
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
																const url = feature.icon.url || iconData.url || iconData.data_uri || (iconData.data && iconData.data.url) || (iconData.data && iconData.data.data_uri);
																if (url) {
																	return <img src={url} alt={feature.label} className="bb-admin-settings__feature-icon-img" />;
																}
															}
															
															if ( 'image' === iconType ) {
																const url = feature.icon.url || iconData.url || iconData.path || (iconData.data && iconData.data.url) || (iconData.data && iconData.data.path);
																if (url) {
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
												{ feature.is_placeholder && 'not_installed' === feature.plugin_status && feature.plugin_action_url ? (
													<Button
														variant="secondary"
														className="bb-admin-settings__feature-settings-btn"
														onClick={() => { window.location.href = feature.plugin_action_url; }}
													>
														{__('Install & Activate', 'buddyboss')}
													</Button>
												) : feature.is_placeholder && 'installed_inactive' === feature.plugin_status && feature.plugin_action_url ? (
													<Button
														variant="secondary"
														className="bb-admin-settings__feature-settings-btn"
														onClick={() => { window.location.href = feature.plugin_action_url; }}
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
													<Button
														variant="secondary"
														className={`bb-admin-settings__feature-settings-btn ${feature.status !== 'active' || feature.is_drm_locked ? 'bb-admin-settings__feature-settings-btn--disabled' : ''}`}
														onClick={() => {
															if ( feature.is_drm_locked ) {
																return;
															}
															// External URL (add-on plugins with own settings page, not bb-settings).
															if ( feature.settings_route && feature.settings_route.startsWith( 'http' ) && ! feature.settings_route.includes( 'page=bb-settings' ) ) {
																window.location.href = feature.settings_route;
															} else {
																onNavigate( urlToRoute( feature.settings_route ) );
															}
														}}
														disabled={feature.status !== 'active' || !!feature.is_drm_locked}
													>
														<i className="bb-icon-settings"></i>
														{__('Settings', 'buddyboss')}
													</Button>
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
		</div>
	);
}
