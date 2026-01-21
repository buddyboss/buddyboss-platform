/**
 * BuddyBoss Admin Settings 2.0 - Settings Grid Screen
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss 3.0.0
 */

import { useState, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Button, Spinner, ToggleControl } from '@wordpress/components';
import apiFetch from '@wordpress/api-fetch';
import { invalidateFeatureCache } from '../../utils/featureCache';

/**
 * AJAX request helper for features.
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
 * Settings Screen Component
 *
 * @param {Object} props Component props
 * @param {Function} props.onNavigate Navigation callback
 * @returns {JSX.Element} Settings screen
 */
export function SettingsScreen({ onNavigate }) {
	const [features, setFeatures] = useState([]);
	const [filteredFeatures, setFilteredFeatures] = useState([]);
	const [isLoading, setIsLoading] = useState(true);
	const [activeFilter, setActiveFilter] = useState('all'); // 'all', 'active', 'inactive'
	const [selectedCategory, setSelectedCategory] = useState(''); // 'community', 'add-ons', 'integrations'
	const [searchQuery, setSearchQuery] = useState('');

	useEffect(() => {
		// Load features via AJAX
		ajaxFetch('bb_admin_get_features')
			.then((response) => {
				console.log('Features AJAX response:', response);
				
				if (response.success && Array.isArray(response.data)) {
					setFeatures(response.data);
					setFilteredFeatures(response.data);
				} else {
					console.warn('No features returned from AJAX.');
					setFeatures([]);
					setFilteredFeatures([]);
				}
				setIsLoading(false);
			})
			.catch((error) => {
				console.error('Failed to load features:', error);
				setFeatures([]);
				setFilteredFeatures([]);
				setIsLoading(false);
			});
	}, []);

	// Filter features
	useEffect(() => {
		let filtered = [...features];

		// Filter by status
		if (activeFilter !== 'all') {
			filtered = filtered.filter((feature) => feature.status === activeFilter);
		}

		// Filter by category
		if (selectedCategory) {
			filtered = filtered.filter((feature) => feature.category === selectedCategory);
		}

		// Filter by search
		if (searchQuery && searchQuery.length >= 2) {
			const queryLower = searchQuery.toLowerCase();
			filtered = filtered.filter(
				(feature) =>
					feature.label.toLowerCase().includes(queryLower) ||
					(feature.description && feature.description.toLowerCase().includes(queryLower))
			);
		}

		setFilteredFeatures(filtered);
	}, [features, activeFilter, selectedCategory, searchQuery]);

	// Group features by category
	const groupedFeatures = filteredFeatures.reduce((acc, feature) => {
		const category = feature.category || 'community';
		if (!acc[category]) {
			acc[category] = [];
		}
		acc[category].push(feature);
		return acc;
	}, {});

	// Get filter counts
	const filterCounts = {
		all: features.length,
		active: features.filter((f) => f.status === 'active').length,
		inactive: features.filter((f) => f.status === 'inactive').length,
	};

	// Get category counts
	const categoryCounts = features.reduce((acc, feature) => {
		const category = feature.category || 'community';
		acc[category] = (acc[category] || 0) + 1;
		return acc;
	}, {});

	const handleFeatureToggle = (featureId, checked) => {
		const action = checked ? 'bb_admin_activate_feature' : 'bb_admin_deactivate_feature';

		ajaxFetch(action, { feature_id: featureId })
			.then((response) => {
				if (response.success) {
					// Update feature status from response data (matches REST API format)
					const updatedFeature = response.data?.data;
					setFeatures((prevFeatures) =>
						prevFeatures.map((feature) =>
							feature.id === featureId
								? { ...feature, ...updatedFeature }
								: feature
						)
					);

					// Invalidate cache for this feature so fresh data is fetched when accessing settings
					invalidateFeatureCache(featureId);
				} else {
					console.error('Failed to toggle feature:', response.data?.message);
					alert(response.data?.message || __('Failed to toggle feature.', 'buddyboss'));
				}
			})
			.catch((error) => {
				console.error('Failed to toggle feature:', error);
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
							className={`bb-admin-settings__filter-tab ${activeFilter === 'all' ? 'bb-admin-settings__filter-tab--active' : ''}`}
							onClick={() => setActiveFilter('all')}
						>
							{__('All', 'buddyboss')} ({filterCounts.all})
						</button>
						<button
							className={`bb-admin-settings__filter-tab ${activeFilter === 'active' ? 'bb-admin-settings__filter-tab--active' : ''}`}
							onClick={() => setActiveFilter('active')}
						>
							{__('Active', 'buddyboss')} ({filterCounts.active})
						</button>
						<button
							className={`bb-admin-settings__filter-tab ${activeFilter === 'inactive' ? 'bb-admin-settings__filter-tab--active' : ''}`}
							onClick={() => setActiveFilter('inactive')}
						>
							{__('Inactive', 'buddyboss')} ({filterCounts.inactive})
						</button>
					</div>

					<div className="bb-admin-settings__filter-right">
						<select
							className="bb-admin-settings__filter-select"
							value={selectedCategory}
							onChange={(e) => setSelectedCategory(e.target.value)}
						>
							<option value="">{__('Category', 'buddyboss')}</option>
							{Object.keys(categoryCounts).map((category) => (
								<option key={category} value={category}>
									{category === 'community'
										? __('Community', 'buddyboss')
										: category === 'add-ons'
										? __('Add-ons', 'buddyboss')
										: __('Integrations', 'buddyboss')}
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
							<div className="bb-admin-settings__category-divider">
								<h2 className="bb-admin-settings__category-title">
									{category === 'community'
										? __('BUDDYBOSS COMMUNITY SETTINGS', 'buddyboss')
										: category === 'add-ons'
										? __('BUDDYBOSS ADD-ONS', 'buddyboss')
										: __('BUDDYBOSS INTEGRATIONS', 'buddyboss')}
								</h2>
								<div className="bb-admin-settings__category-line"></div>
							</div>
							
							{/* Features Grid */}
							<div className="bb-admin-settings__features-grid">
								{categoryFeatures.map((feature) => (
									<div
										key={feature.id}
										className={`bb-admin-settings__feature-card bb-admin-settings__feature-card--${feature.status}`}
									>
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
															
															if (iconType === 'dashicon') {
																const slug = feature.icon.slug || iconData.slug || 'dashicons-admin-generic';
																return <span className={`dashicons ${slug}`}></span>;
															}
															
															if (iconType === 'svg') {
																const url = feature.icon.url || iconData.url || iconData.data_uri || (iconData.data && iconData.data.url) || (iconData.data && iconData.data.data_uri);
																if (url) {
																	return <img src={url} alt={feature.label} className="bb-admin-settings__feature-icon-img" />;
																}
															}
															
															if (iconType === 'image') {
																const url = feature.icon.url || iconData.url || iconData.path || (iconData.data && iconData.data.url) || (iconData.data && iconData.data.path);
																if (url) {
																	return <img src={url} alt={feature.label} className="bb-admin-settings__feature-icon-img" />;
																}
															}
															
															if (iconType === 'font') {
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
												<Button
													variant="secondary"
													className={`bb-admin-settings__feature-settings-btn ${feature.status !== 'active' ? 'bb-admin-settings__feature-settings-btn--disabled' : ''}`}
													onClick={() => onNavigate(feature.settings_route)}
													disabled={feature.status !== 'active'}
												>
													<i className="bb-icon-settings"></i>
													{__('Settings', 'buddyboss')}
												</Button>
											</div>
											<div className="bb-admin-settings__feature-right">
												<ToggleControl
													checked={feature.status === 'active'}
													onChange={(checked) => handleFeatureToggle(feature.id, checked)}
													disabled={!feature.available}
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
		</div>
	);
}
