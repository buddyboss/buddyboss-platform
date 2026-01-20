/**
 * BuddyBoss Admin Settings 2.0 - Group Navigation Screen
 *
 * Manages group navigation order and settings.
 * Design based on Figma:
 * - Settings: https://www.figma.com/design/XS2Hf0smlEnhWfoKyks7ku/Backend-Settings-2.0?node-id=2611-123384
 * - Navigation Order: https://www.figma.com/design/XS2Hf0smlEnhWfoKyks7ku/Backend-Settings-2.0?node-id=2611-123357
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss 3.0.0
 */

import { useState, useEffect, useCallback } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Spinner, ToggleControl, SelectControl } from '@wordpress/components';
import apiFetch from '@wordpress/api-fetch';
import { SideNavigation } from '../SideNavigation';
import { ajaxFetch } from '../../utils/ajax';
import { getCachedFeatureData, setCachedFeatureData, getCachedSidebarData } from '../../utils/featureCache';

/**
 * Drag handle icon component
 */
function DragHandleIcon() {
	return (
		<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
			<path d="M4 8H20M4 16H20" stroke="#666" strokeWidth="1.5" strokeLinecap="round"/>
		</svg>
	);
}

/**
 * Help icon component
 */
function HelpIcon() {
	return (
		<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
			<path d="M12 2.25C6.62 2.25 2.25 6.62 2.25 12C2.25 17.38 6.62 21.75 12 21.75C17.38 21.75 21.75 17.38 21.75 12C21.75 6.62 17.38 2.25 12 2.25ZM12 20.25C7.45 20.25 3.75 16.55 3.75 12C3.75 7.45 7.45 3.75 12 3.75C16.55 3.75 20.25 7.45 20.25 12C20.25 16.55 16.55 20.25 12 20.25Z" fill="#666"/>
			<path d="M12 17.25C12.4142 17.25 12.75 16.9142 12.75 16.5C12.75 16.0858 12.4142 15.75 12 15.75C11.5858 15.75 11.25 16.0858 11.25 16.5C11.25 16.9142 11.5858 17.25 12 17.25Z" fill="#666"/>
			<path d="M12 6.75C10.21 6.75 8.75 8.21 8.75 10C8.75 10.41 9.09 10.75 9.5 10.75C9.91 10.75 10.25 10.41 10.25 10C10.25 9.04 11.04 8.25 12 8.25C12.96 8.25 13.75 9.04 13.75 10C13.75 10.96 12.96 11.75 12 11.75C11.59 11.75 11.25 12.09 11.25 12.5V14C11.25 14.41 11.59 14.75 12 14.75C12.41 14.75 12.75 14.41 12.75 14V13.17C14.14 12.82 15.25 11.53 15.25 10C15.25 8.21 13.79 6.75 12 6.75Z" fill="#666"/>
		</svg>
	);
}

/**
 * Default navigation items with their metadata
 */
const DEFAULT_NAV_ITEMS = [
	{ slug: 'members', label: __('Members', 'buddyboss'), enabled: true, hidden: false },
	{ slug: 'activity', label: __('Feed', 'buddyboss'), enabled: true, hidden: false },
	{ slug: 'photos', label: __('Photos', 'buddyboss'), enabled: true, hidden: false },
	{ slug: 'videos', label: __('Videos', 'buddyboss'), enabled: true, hidden: false },
	{ slug: 'albums', label: __('Albums', 'buddyboss'), enabled: true, hidden: false },
	{ slug: 'documents', label: __('Documents', 'buddyboss'), enabled: true, hidden: false },
	{ slug: 'discussions', label: __('Discussions', 'buddyboss'), enabled: true, hidden: false },
	{ slug: 'zoom', label: __('Zoom', 'buddyboss'), enabled: true, hidden: false },
	{ slug: 'messages', label: __('Send Messages', 'buddyboss'), enabled: true, hidden: false },
	{ slug: 'invite', label: __('Send Invites', 'buddyboss'), enabled: true, hidden: false },
	{ slug: 'subgroups', label: __('Subgroups', 'buddyboss'), enabled: true, hidden: false },
	{ slug: 'admin', label: __('Manage', 'buddyboss'), enabled: true, hidden: false },
];

/**
 * Group Navigation Screen Component
 *
 * @param {Object} props Component props
 * @param {Function} props.onNavigate Navigation callback
 * @returns {JSX.Element} Group navigation screen
 */
export default function GroupNavigationScreen({ onNavigate }) {
	const [isLoading, setIsLoading] = useState(true);
	const [isSaving, setIsSaving] = useState(false);
	const [navItems, setNavItems] = useState([]);
	const [verticalNav, setVerticalNav] = useState(false);
	const [defaultTab, setDefaultTab] = useState('members');
	const [defaultTabOptions, setDefaultTabOptions] = useState([]);
	const [draggedItem, setDraggedItem] = useState(null);

	// Feature data for sidebar
	const [sidePanels, setSidePanels] = useState([]);
	const [navMenuItems, setNavMenuItems] = useState([]);
	const [sidebarLoading, setSidebarLoading] = useState(true);

	// Load sidebar data - use cache if available
	useEffect(() => {
		const featureId = 'groups';
		
		// Check cache first
		const cachedSidebar = getCachedSidebarData(featureId);
		if (cachedSidebar) {
			setSidePanels(cachedSidebar.sidePanels);
			setNavMenuItems(cachedSidebar.navItems);
			setSidebarLoading(false);
			return;
		}
		
		// No cache, fetch from server via AJAX
		ajaxFetch('bb_admin_get_feature_settings', { feature_id: featureId })
			.then((response) => {
				if (response.success && response.data) {
					const data = response.data;
					setSidePanels(data.side_panels || []);
					setNavMenuItems(data.navigation || []);
					
					// Cache the response for future use
					setCachedFeatureData(featureId, data);
				}
				setSidebarLoading(false);
			})
			.catch(() => {
				setSidebarLoading(false);
			});
	}, []);

	// Load settings
	useEffect(() => {
		loadSettings();
	}, []);

	const loadSettings = async () => {
		setIsLoading(true);
		try {
			// Fetch the bp_nouveau_appearance option via the appearance endpoint
			const response = await apiFetch({ path: `/buddyboss/v1/settings/appearance` });
			const appearanceSettings = response.data || {};

			// Set vertical navigation
			setVerticalNav(!!appearanceSettings.group_nav_display);

			// Set default tab
			setDefaultTab(appearanceSettings.group_default_tab || 'members');

			// Build nav items from saved order and available items
			const savedOrder = appearanceSettings.group_nav_order || [];
			const hiddenItems = appearanceSettings.group_nav_hide || [];

			// Merge saved order with defaults
			let orderedItems = [];

			if (savedOrder.length > 0) {
				// Use saved order
				savedOrder.forEach((slug) => {
					const defaultItem = DEFAULT_NAV_ITEMS.find((item) => item.slug === slug);
					if (defaultItem) {
						orderedItems.push({
							...defaultItem,
							enabled: !hiddenItems.includes(slug),
							hidden: hiddenItems.includes(slug),
						});
					}
				});

				// Add any items not in saved order
				DEFAULT_NAV_ITEMS.forEach((item) => {
					if (!savedOrder.includes(item.slug)) {
						orderedItems.push({
							...item,
							enabled: !hiddenItems.includes(item.slug),
							hidden: hiddenItems.includes(item.slug),
						});
					}
				});
			} else {
				// Use default order
				orderedItems = DEFAULT_NAV_ITEMS.map((item) => ({
					...item,
					enabled: !hiddenItems.includes(item.slug),
					hidden: hiddenItems.includes(item.slug),
				}));
			}

			setNavItems(orderedItems);

			// Build default tab options (only enabled items)
			updateDefaultTabOptions(orderedItems);

		} catch (error) {
			console.error('Failed to load group navigation settings:', error);
			setNavItems(DEFAULT_NAV_ITEMS);
		} finally {
			setIsLoading(false);
		}
	};

	const updateDefaultTabOptions = (items) => {
		const options = items
			.filter((item) => item.enabled && ['members', 'activity', 'photos', 'videos', 'albums', 'documents'].includes(item.slug))
			.map((item) => ({
				value: item.slug,
				label: item.label,
			}));
		setDefaultTabOptions(options);
	};

	const handleVerticalNavChange = async (checked) => {
		setVerticalNav(checked);
		await saveSettings({ group_nav_display: checked ? 1 : 0 });
	};

	const handleDefaultTabChange = async (value) => {
		setDefaultTab(value);
		await saveSettings({ group_default_tab: value });
	};

	const handleToggleItem = async (slug) => {
		const updatedItems = navItems.map((item) => {
			if (item.slug === slug) {
				return { ...item, enabled: !item.enabled, hidden: item.enabled };
			}
			return item;
		});
		setNavItems(updatedItems);
		updateDefaultTabOptions(updatedItems);

		// Save hidden items
		const hiddenItems = updatedItems.filter((item) => !item.enabled).map((item) => item.slug);
		await saveSettings({ group_nav_hide: hiddenItems });
	};

	const handleDragStart = (e, index) => {
		setDraggedItem(index);
		e.dataTransfer.effectAllowed = 'move';
		e.dataTransfer.setData('text/html', e.target.outerHTML);
	};

	const handleDragOver = (e, index) => {
		e.preventDefault();
		e.dataTransfer.dropEffect = 'move';
	};

	const handleDrop = async (e, targetIndex) => {
		e.preventDefault();

		if (draggedItem === null || draggedItem === targetIndex) {
			setDraggedItem(null);
			return;
		}

		const newItems = [...navItems];
		const [movedItem] = newItems.splice(draggedItem, 1);
		newItems.splice(targetIndex, 0, movedItem);

		setNavItems(newItems);
		setDraggedItem(null);

		// Save new order
		const newOrder = newItems.map((item) => item.slug);
		await saveSettings({ group_nav_order: newOrder });
	};

	const handleDragEnd = () => {
		setDraggedItem(null);
	};

	const saveSettings = async (settingsToSave) => {
		setIsSaving(true);
		try {
			await apiFetch({
				path: `/buddyboss/v1/settings/appearance`,
				method: 'POST',
				data: settingsToSave,
			});
		} catch (error) {
			console.error('Failed to save group navigation settings:', error);
		} finally {
			setIsSaving(false);
		}
	};

	const handleBack = () => {
		onNavigate('/settings/groups');
	};

	return (
		<div className="bb-admin-feature-settings">
			<div className="bb-admin-feature-settings__container">
				{/* Left Sidebar Navigation */}
				<aside className="bb-admin-feature-settings__sidebar">
					{sidebarLoading ? (
						<div className="bb-admin-loading">
							<Spinner />
						</div>
					) : (
						<SideNavigation
							featureId="groups"
							sidePanels={sidePanels}
							navItems={navMenuItems}
							currentPanel="group_navigation"
							onNavigate={onNavigate}
							onBack={handleBack}
						/>
					)}
				</aside>

				{/* Main Content */}
				<main className="bb-admin-feature-settings__main">
					<div className="bb-admin-group-navigation">
						{isLoading ? (
							<div className="bb-admin-group-navigation__loading">
								<Spinner />
							</div>
						) : (
							<>
								{/* Card 1: Group Navigation Settings */}
								<div className="bb-admin-group-navigation__card">
									<div className="bb-admin-group-navigation__card-header">
										<h2>{__('Group Navigation', 'buddyboss')}</h2>
										<button className="bb-admin-group-navigation__help-btn" aria-label={__('Help', 'buddyboss')}>
											<HelpIcon />
										</button>
									</div>

									<div className="bb-admin-group-navigation__settings">
										{/* Layout Toggle */}
										<div className="bb-admin-group-navigation__setting-row">
											<div className="bb-admin-group-navigation__setting-label">
												<span>{__('Layout', 'buddyboss')}</span>
											</div>
											<div className="bb-admin-group-navigation__setting-control">
												<div className="bb-admin-group-navigation__toggle-wrap">
													<ToggleControl
														checked={verticalNav}
														onChange={handleVerticalNavChange}
														disabled={isSaving}
														__nextHasNoMarginBottom
													/>
													<span className="bb-admin-group-navigation__toggle-label">
														{__('Display the group navigation vertically', 'buddyboss')}
													</span>
												</div>
											</div>
										</div>

										{/* Default Tab Select */}
										<div className="bb-admin-group-navigation__setting-row bb-admin-group-navigation__setting-row--last">
											<div className="bb-admin-group-navigation__setting-label">
												<span>{__('Default Tab', 'buddyboss')}</span>
											</div>
											<div className="bb-admin-group-navigation__setting-control">
												<div className="bb-admin-group-navigation__select-wrap">
													<SelectControl
														value={defaultTab}
														options={defaultTabOptions}
														onChange={handleDefaultTabChange}
														disabled={isSaving}
														__nextHasNoMarginBottom
													/>
												</div>
												<p className="bb-admin-group-navigation__setting-desc">
													{__('The dropdown only shows tabs that are available to all groups.', 'buddyboss')}
												</p>
											</div>
										</div>
									</div>
								</div>

								{/* Card 2: Navigation Order */}
								<div className="bb-admin-group-navigation__card">
									<div className="bb-admin-group-navigation__card-header">
										<h2>{__('Navigation Order', 'buddyboss')}</h2>
									</div>

									<div className="bb-admin-group-navigation__list">
										{navItems.map((item, index) => (
											<div
												key={item.slug}
												className={`bb-admin-group-navigation__item ${draggedItem === index ? 'bb-admin-group-navigation__item--dragging' : ''}`}
												draggable
												onDragStart={(e) => handleDragStart(e, index)}
												onDragOver={(e) => handleDragOver(e, index)}
												onDrop={(e) => handleDrop(e, index)}
												onDragEnd={handleDragEnd}
											>
												<div className="bb-admin-group-navigation__item-left">
													<div className="bb-admin-group-navigation__drag-handle">
														<DragHandleIcon />
													</div>
													<div className="bb-admin-group-navigation__item-info">
														<span className="bb-admin-group-navigation__item-name">{item.label}</span>
														{!item.enabled && (
															<span className="bb-admin-group-navigation__item-badge">
																{__('Hidden', 'buddyboss')}
															</span>
														)}
													</div>
												</div>
												<div className="bb-admin-group-navigation__item-right">
													<ToggleControl
														checked={item.enabled}
														onChange={() => handleToggleItem(item.slug)}
														disabled={isSaving}
														__nextHasNoMarginBottom
													/>
												</div>
											</div>
										))}
									</div>
								</div>
							</>
						)}
					</div>
				</main>
			</div>
		</div>
	);
}
