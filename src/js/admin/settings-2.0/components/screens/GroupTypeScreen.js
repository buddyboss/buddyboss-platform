/**
 * BuddyBoss Admin Settings 2.0 - Group Type Settings Screen
 *
 * Displays group type settings with toggles and group types list.
 * Design based on Figma: https://www.figma.com/design/XS2Hf0smlEnhWfoKyks7ku/Backend-Settings-2.0?node-id=2611-123217
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss 3.0.0
 */

import { useState, useEffect, useRef } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Spinner, ToggleControl } from '@wordpress/components';
import apiFetch from '@wordpress/api-fetch';
import { SideNavigation } from '../SideNavigation';

/**
 * Tag icon component for group types
 */
function TagIcon() {
	return (
		<svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
			<path d="M2.66663 7.99996L7.33329 13.3333L13.3333 7.33329V2.66663H8.66663L2.66663 7.99996Z" stroke="#666" strokeWidth="1.2" strokeLinecap="round" strokeLinejoin="round"/>
			<circle cx="10.6666" cy="5.33329" r="0.666667" fill="#666"/>
		</svg>
	);
}

/**
 * Groups icon component
 */
function GroupsIcon() {
	return (
		<svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
			<path d="M8 8C9.65685 8 11 6.65685 11 5C11 3.34315 9.65685 2 8 2C6.34315 2 5 3.34315 5 5C5 6.65685 6.34315 8 8 8Z" stroke="#666" strokeWidth="1.2"/>
			<path d="M14 14C14 11.7909 11.3137 10 8 10C4.68629 10 2 11.7909 2 14" stroke="#666" strokeWidth="1.2" strokeLinecap="round"/>
		</svg>
	);
}

/**
 * Ellipsis icon component
 */
function EllipsisIcon() {
	return (
		<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
			<circle cx="12" cy="6" r="1.5" fill="#2f2f2f"/>
			<circle cx="12" cy="12" r="1.5" fill="#2f2f2f"/>
			<circle cx="12" cy="18" r="1.5" fill="#2f2f2f"/>
		</svg>
	);
}

/**
 * Privacy badge icon component
 */
function PrivacyIcon({ status }) {
	if (status === 'public') {
		return (
			<svg width="12" height="12" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
				<path d="M8 1.33337C4.32 1.33337 1.33337 4.32004 1.33337 8.00004C1.33337 11.68 4.32 14.6667 8 14.6667C11.68 14.6667 14.6667 11.68 14.6667 8.00004C14.6667 4.32004 11.68 1.33337 8 1.33337ZM8 13.3334C5.05337 13.3334 2.66671 10.9467 2.66671 8.00004C2.66671 5.05337 5.05337 2.66671 8 2.66671C10.9467 2.66671 13.3334 5.05337 13.3334 8.00004C13.3334 10.9467 10.9467 13.3334 8 13.3334Z" fill="currentColor"/>
			</svg>
		);
	}
	if (status === 'private') {
		return (
			<svg width="12" height="12" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
				<path d="M12.6667 5.33337H11.3334V4.00004C11.3334 2.16004 9.84004 0.666707 8.00004 0.666707C6.16004 0.666707 4.66671 2.16004 4.66671 4.00004V5.33337H3.33337C2.60004 5.33337 2.00004 5.93337 2.00004 6.66671V13.3334C2.00004 14.0667 2.60004 14.6667 3.33337 14.6667H12.6667C13.4 14.6667 14 14.0667 14 13.3334V6.66671C14 5.93337 13.4 5.33337 12.6667 5.33337ZM8.00004 11.3334C7.26671 11.3334 6.66671 10.7334 6.66671 10C6.66671 9.26671 7.26671 8.66671 8.00004 8.66671C8.73337 8.66671 9.33337 9.26671 9.33337 10C9.33337 10.7334 8.73337 11.3334 8.00004 11.3334ZM10.0667 5.33337H5.93337V4.00004C5.93337 2.86004 6.86004 1.93337 8.00004 1.93337C9.14004 1.93337 10.0667 2.86004 10.0667 4.00004V5.33337Z" fill="currentColor"/>
			</svg>
		);
	}
	// Hidden
	return (
		<svg width="12" height="12" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
			<path d="M8 3.33337C4.66667 3.33337 1.82 5.34004 0.666672 8.00004C1.82 10.66 4.66667 12.6667 8 12.6667C11.3333 12.6667 14.18 10.66 15.3333 8.00004C14.18 5.34004 11.3333 3.33337 8 3.33337ZM8 11C6.34667 11 5 9.65337 5 8.00004C5 6.34671 6.34667 5.00004 8 5.00004C9.65333 5.00004 11 6.34671 11 8.00004C11 9.65337 9.65333 11 8 11ZM8 6.33337C7.08 6.33337 6.33333 7.08004 6.33333 8.00004C6.33333 8.92004 7.08 9.66671 8 9.66671C8.92 9.66671 9.66667 8.92004 9.66667 8.00004C9.66667 7.08004 8.92 6.33337 8 6.33337Z" fill="currentColor"/>
			<line x1="2" y1="14" x2="14" y2="2" stroke="currentColor" strokeWidth="1.5"/>
		</svg>
	);
}

/**
 * Group Type Settings Screen Component
 *
 * @param {Object} props Component props
 * @param {Function} props.onNavigate Navigation callback
 * @returns {JSX.Element} Group type settings screen
 */
export default function GroupTypeScreen({ onNavigate }) {
	const [groupTypes, setGroupTypes] = useState([]);
	const [isLoading, setIsLoading] = useState(true);
	const [enableGroupTypes, setEnableGroupTypes] = useState(true);
	const [autoMembershipApproval, setAutoMembershipApproval] = useState(true);
	const [openMenuId, setOpenMenuId] = useState(null);
	const [settingsLoading, setSettingsLoading] = useState(true);
	const menuRef = useRef(null);

	// Feature data for sidebar
	const [sidePanels, setSidePanels] = useState([]);
	const [navItems, setNavItems] = useState([]);
	const [sidebarLoading, setSidebarLoading] = useState(true);

	// Load sidebar data
	useEffect(() => {
		apiFetch({ path: `/buddyboss/v1/features/groups/settings` })
			.then((response) => {
				setSidePanels(response.data?.side_panels || []);
				setNavItems(response.data?.navigation || []);
				setSidebarLoading(false);
			})
			.catch(() => {
				setSidebarLoading(false);
			});
	}, []);

	// Load settings from WordPress options
	useEffect(() => {
		setSettingsLoading(true);
		apiFetch({ path: `/buddyboss/v1/settings` })
			.then((response) => {
				const platformSettings = response.platform || {};
				// Note: Despite the misleading name, bp-disable-group-type-creation = true means ENABLED
				setEnableGroupTypes(!!platformSettings['bp-disable-group-type-creation']);
				setAutoMembershipApproval(!!platformSettings['bp-enable-group-auto-join']);
				setSettingsLoading(false);
			})
			.catch((error) => {
				console.error('Failed to load group type settings:', error);
				setSettingsLoading(false);
			});
	}, []);

	// Load group types
	useEffect(() => {
		loadGroupTypes();
	}, []);

	// Close menu when clicking outside
	useEffect(() => {
		const handleClickOutside = (event) => {
			if (menuRef.current && !menuRef.current.contains(event.target)) {
				setOpenMenuId(null);
			}
		};
		document.addEventListener('mousedown', handleClickOutside);
		return () => document.removeEventListener('mousedown', handleClickOutside);
	}, []);

	const loadGroupTypes = () => {
		setIsLoading(true);

		apiFetch({ path: `/buddyboss/v1/groups/types` })
			.then((response) => {
				console.log('Group Types API Response:', response);
				const types = response.data || response || [];
				setGroupTypes(Array.isArray(types) ? types : []);
				setIsLoading(false);
			})
			.catch((error) => {
				console.error('Group Types API Error:', error);
				setGroupTypes([]);
				setIsLoading(false);
			});
	};

	const handleEnableGroupTypesChange = (checked) => {
		setEnableGroupTypes(checked);

		// Save to WordPress options
		// Note: Despite the misleading name, bp-disable-group-type-creation = true means ENABLED
		const nonce = bbAdminData?.nonce || '';
		apiFetch({
			path: `/buddyboss/v1/settings`,
			method: 'POST',
			headers: {
				'X-WP-Nonce': nonce,
			},
			data: {
				'bp-disable-group-type-creation': checked,
			},
		})
			.then((response) => {
				console.log('Group Types setting saved:', response);
			})
			.catch((error) => {
				console.error('Failed to save Group Types setting:', error);
				// Revert the state on error
				setEnableGroupTypes(!checked);
			});
	};

	const handleAutoMembershipApprovalChange = (checked) => {
		setAutoMembershipApproval(checked);

		// Save to WordPress options
		const nonce = bbAdminData?.nonce || '';
		apiFetch({
			path: `/buddyboss/v1/settings`,
			method: 'POST',
			headers: {
				'X-WP-Nonce': nonce,
			},
			data: {
				'bp-enable-group-auto-join': checked,
			},
		})
			.then((response) => {
				console.log('Auto Membership Approval setting saved:', response);
			})
			.catch((error) => {
				console.error('Failed to save Auto Membership Approval setting:', error);
				// Revert the state on error
				setAutoMembershipApproval(!checked);
			});
	};

	const handleBack = () => {
		onNavigate('/settings/groups');
	};

	const handleDelete = (typeId) => {
		if (!confirm(__('Are you sure you want to delete this group type?', 'buddyboss'))) {
			return;
		}

		apiFetch({
			path: `/buddyboss/v1/groups/types/${typeId}`,
			method: 'DELETE',
			headers: { 'X-WP-Nonce': bbAdminData?.nonce || '' },
		}).then(() => {
			setOpenMenuId(null);
			loadGroupTypes();
		});
	};

	const handleEdit = (typeId) => {
		setOpenMenuId(null);
		onNavigate(`/settings/groups/types/${typeId}/edit`);
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
							navItems={navItems}
							currentPanel="group_types"
							onNavigate={onNavigate}
							onBack={handleBack}
						/>
					)}
				</aside>

				{/* Main Content */}
				<main className="bb-admin-feature-settings__main">
					<div className="bb-admin-group-types">
						{/* Section Title */}
						<div className="bb-admin-group-types__header">
							<h2>{__('Group Type Settings', 'buddyboss')}</h2>
							<button className="bb-admin-group-types__help-btn" aria-label={__('Help', 'buddyboss')}>
								<i className="bb-icon-l-question"></i>
							</button>
						</div>

						{/* Settings Card */}
						<div className="bb-admin-group-types__card">
							{/* Toggle Settings */}
							<div className="bb-admin-group-types__settings">
								<div className="bb-admin-group-types__setting-item">
									<div className="bb-admin-group-types__setting-content">
										<h3>{__('Group Types', 'buddyboss')}</h3>
										<p>{__('When enabled, group types allow you to better organize groups.', 'buddyboss')}</p>
									</div>
									<div className="bb-admin-group-types__setting-toggle">
										<ToggleControl
											checked={enableGroupTypes}
											onChange={handleEnableGroupTypesChange}
											disabled={settingsLoading}
											__nextHasNoMarginBottom
										/>
									</div>
								</div>

								<div className="bb-admin-group-types__setting-item">
									<div className="bb-admin-group-types__setting-content">
										<h3>{__('Auto Membership Approval', 'buddyboss')}</h3>
										<p>{__('When a member requests to join a group their membership is automatically accepted.', 'buddyboss')}</p>
									</div>
									<div className="bb-admin-group-types__setting-toggle">
										<ToggleControl
											checked={autoMembershipApproval}
											onChange={handleAutoMembershipApprovalChange}
											disabled={settingsLoading}
											__nextHasNoMarginBottom
										/>
									</div>
								</div>
							</div>

							{/* Group Types List */}
							<div className="bb-admin-group-types__list-section">
								<div className="bb-admin-group-types__list-header">
									<h3>{__('Group Types', 'buddyboss')}</h3>
									<button className="bb-admin-group-types__add-btn">
										<span>+</span>
										{__('Add New Group Type', 'buddyboss')}
									</button>
								</div>

								{isLoading ? (
									<div className="bb-admin-group-types__loading">
										<Spinner />
									</div>
								) : groupTypes.length === 0 ? (
									<div className="bb-admin-group-types__empty">
										{__('No group types found.', 'buddyboss')}
									</div>
								) : (
									<div className="bb-admin-group-types__list">
										{groupTypes.map((type) => {
											const typeId = type.id || type.ID;
											const typeName = type.name || type.post_title || '';
											const typeSlug = type.slug || type.post_name || '';
											const groupCount = type.groups_count || type.count || 0;
											const privacy = type.privacy || 'public';

											return (
												<div key={typeId} className="bb-admin-group-types__item">
													<div className="bb-admin-group-types__item-icon">
														<TagIcon />
													</div>
													<div className="bb-admin-group-types__item-name">
														{typeName}
													</div>
													<div className="bb-admin-group-types__item-slug">
														{typeSlug}
													</div>
													<div className="bb-admin-group-types__item-groups">
														<GroupsIcon />
														<a href={`#/groups?type=${typeSlug}`} className="bb-admin-group-types__item-link">
															{groupCount} {groupCount === 1 ? __('group', 'buddyboss') : __('groups', 'buddyboss')}
														</a>
													</div>
													<div className="bb-admin-group-types__item-privacy">
														<PrivacyIcon status={privacy} />
														<span>{privacy === 'public' ? __('Public', 'buddyboss') : privacy === 'private' ? __('Private', 'buddyboss') : __('Hidden', 'buddyboss')}</span>
													</div>
													<div className="bb-admin-group-types__item-actions" ref={openMenuId === typeId ? menuRef : null}>
														<button
															className="bb-admin-group-types__ellipsis-btn"
															onClick={() => setOpenMenuId(openMenuId === typeId ? null : typeId)}
														>
															<EllipsisIcon />
														</button>
														{openMenuId === typeId && (
															<div className="bb-admin-group-types__menu">
																<button onClick={() => handleEdit(typeId)}>
																	{__('Edit', 'buddyboss')}
																</button>
																<button onClick={() => handleDelete(typeId)}>
																	{__('Delete', 'buddyboss')}
																</button>
															</div>
														)}
													</div>
												</div>
											);
										})}
									</div>
								)}
							</div>
						</div>
					</div>
				</main>
			</div>
		</div>
	);
}
