/**
 * BuddyBoss Admin Settings 2.0 - Group Type Settings Screen
 *
 * Displays group type settings with toggles and group types list.
 * Design based on Figma:
 * - Settings: https://www.figma.com/design/XS2Hf0smlEnhWfoKyks7ku/Backend-Settings-2.0?node-id=2611-123236
 * - List: https://www.figma.com/design/XS2Hf0smlEnhWfoKyks7ku/Backend-Settings-2.0?node-id=2611-123265
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss 3.0.0
 */

import { useState, useEffect, useRef } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Spinner, ToggleControl } from '@wordpress/components';
import { SideNavigation } from '../SideNavigation';
import GroupTypeModal from '../modals/GroupTypeModal';
import { ajaxFetch, getGroupTypes, deleteGroupType, getPlatformSettings, savePlatformSetting } from '../../utils/ajax';
import { getCachedFeatureData, setCachedFeatureData, getCachedSidebarData } from '../../utils/featureCache';

/**
 * Tag icon component for group types
 */
function TagIcon() {
	return (
		<svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
			<path d="M3.33329 10L9.16663 16.6667L16.6666 9.16667V3.33333H10.8333L3.33329 10Z" stroke="#666" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round"/>
			<circle cx="13.3333" cy="6.66667" r="0.833333" fill="#666"/>
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
 * Horizontal Ellipsis icon component
 */
function EllipsisIcon() {
	return (
		<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
			<circle cx="6" cy="12" r="1.5" fill="#2f2f2f"/>
			<circle cx="12" cy="12" r="1.5" fill="#2f2f2f"/>
			<circle cx="18" cy="12" r="1.5" fill="#2f2f2f"/>
		</svg>
	);
}

/**
 * Plus icon component
 */
function PlusIcon() {
	return (
		<svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
			<path d="M8 3.33337V12.6667M3.33337 8.00004H12.6667" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round"/>
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
 * Privacy badge icon component (Globe for public)
 */
function GlobeIcon() {
	return (
		<svg width="12" height="12" viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg">
			<path d="M6 1C3.24 1 1 3.24 1 6C1 8.76 3.24 11 6 11C8.76 11 11 8.76 11 6C11 3.24 8.76 1 6 1ZM9.94 5.5H8.06C7.97 4.32 7.63 3.22 7.09 2.31C8.48 2.82 9.59 4.02 9.94 5.5ZM6 9.94C5.31 9.18 4.81 8.26 4.56 7.25H7.44C7.19 8.26 6.69 9.18 6 9.94ZM4.42 6.5C4.39 6.17 4.37 5.84 4.37 5.5C4.37 5.16 4.39 4.83 4.42 4.5H7.58C7.61 4.83 7.63 5.16 7.63 5.5C7.63 5.84 7.61 6.17 7.58 6.5H4.42ZM2.06 5.5C2.41 4.02 3.52 2.82 4.91 2.31C4.37 3.22 4.03 4.32 3.94 5.5H2.06ZM3.94 6.5H2.06C2.41 7.98 3.52 9.18 4.91 9.69C4.37 8.78 4.03 7.68 3.94 6.5ZM7.09 9.69C7.63 8.78 7.97 7.68 8.06 6.5H9.94C9.59 7.98 8.48 9.18 7.09 9.69Z" fill="currentColor"/>
		</svg>
	);
}

/**
 * View icon component for dropdown menu (Eye)
 */
function ViewIcon() {
	return (
		<svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
			<path d="M10 6.25C7.65 6.25 5.5 7.5 3.75 10C5.5 12.5 7.65 13.75 10 13.75C12.35 13.75 14.5 12.5 16.25 10C14.5 7.5 12.35 6.25 10 6.25Z" stroke="#666" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round"/>
			<circle cx="10" cy="10" r="2.5" stroke="#666" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round"/>
		</svg>
	);
}

/**
 * Edit icon component for dropdown menu (Pencil)
 */
function EditIcon() {
	return (
		<svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
			<path d="M14.1667 2.5C14.3856 2.28113 14.6454 2.10752 14.9314 1.98906C15.2173 1.87061 15.5238 1.80963 15.8333 1.80963C16.1429 1.80963 16.4493 1.87061 16.7353 1.98906C17.0212 2.10752 17.281 2.28113 17.5 2.5C17.7189 2.71887 17.8925 2.97871 18.0109 3.26468C18.1294 3.55064 18.1904 3.85706 18.1904 4.16667C18.1904 4.47627 18.1294 4.78269 18.0109 5.06866C17.8925 5.35462 17.7189 5.61446 17.5 5.83333L6.25 17.0833L1.66667 18.3333L2.91667 13.75L14.1667 2.5Z" stroke="#666" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round"/>
		</svg>
	);
}

/**
 * Delete icon component for dropdown menu (Trash)
 */
function DeleteIcon() {
	return (
		<svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
			<path d="M2.5 5H4.16667M4.16667 5H17.5M4.16667 5V16.6667C4.16667 17.1087 4.34226 17.5326 4.65482 17.8452C4.96738 18.1577 5.39131 18.3333 5.83333 18.3333H14.1667C14.6087 18.3333 15.0326 18.1577 15.3452 17.8452C15.6577 17.5326 15.8333 17.1087 15.8333 16.6667V5H4.16667ZM6.66667 5V3.33333C6.66667 2.89131 6.84226 2.46738 7.15482 2.15482C7.46738 1.84226 7.89131 1.66667 8.33333 1.66667H11.6667C12.1087 1.66667 12.5326 1.84226 12.8452 2.15482C13.1577 2.46738 13.3333 2.89131 13.3333 3.33333V5" stroke="#666" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round"/>
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
	const [isModalOpen, setIsModalOpen] = useState(false);
	const [editingGroupType, setEditingGroupType] = useState(null);
	const menuRef = useRef(null);

	// Feature data for sidebar
	const [sidePanels, setSidePanels] = useState([]);
	const [navItems, setNavItems] = useState([]);
	const [sidebarLoading, setSidebarLoading] = useState(true);

	// Load sidebar data - use cache if available
	useEffect(() => {
		const featureId = 'groups';
		
		// Check cache first
		const cachedSidebar = getCachedSidebarData(featureId);
		if (cachedSidebar) {
			setSidePanels(cachedSidebar.sidePanels);
			setNavItems(cachedSidebar.navItems);
			setSidebarLoading(false);
			return;
		}
		
		// No cache, fetch from server via AJAX
		ajaxFetch('bb_admin_get_feature_settings', { feature_id: featureId })
			.then((response) => {
				if (response.success && response.data) {
					const data = response.data;
					setSidePanels(data.side_panels || []);
					setNavItems(data.navigation || []);
					
					// Cache the response for future use
					setCachedFeatureData(featureId, data);
				}
				setSidebarLoading(false);
			})
			.catch(() => {
				setSidebarLoading(false);
			});
	}, []);

	// Helper to convert various truthy values to boolean
	const toBool = (value) => {
		if (value === true || value === 1 || value === '1' || value === 'true') {
			return true;
		}
		return false;
	};

	// Load settings from WordPress options via AJAX
	useEffect(() => {
		setSettingsLoading(true);
		getPlatformSettings(['bp-disable-group-type-creation', 'bp-enable-group-auto-join'])
			.then((response) => {
				if (response.success && response.data) {
					const platformSettings = response.data.platform || {};
					// Note: Despite the misleading name, bp-disable-group-type-creation = true means ENABLED in BuddyBoss
					setEnableGroupTypes(toBool(platformSettings['bp-disable-group-type-creation']));
					setAutoMembershipApproval(toBool(platformSettings['bp-enable-group-auto-join']));
				}
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

		getGroupTypes()
			.then((response) => {
				if (response.success && response.data) {
					const types = response.data || [];
					setGroupTypes(Array.isArray(types) ? types : []);
				} else {
					setGroupTypes([]);
				}
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

		// Save to WordPress options via AJAX
		// Note: Despite the misleading name, bp-disable-group-type-creation = true means ENABLED in BuddyBoss
		savePlatformSetting('bp-disable-group-type-creation', checked)
			.then((response) => {
				if (response.success) {
					console.log('Group Types setting saved:', response);
				} else {
					console.error('Failed to save Group Types setting:', response);
					setEnableGroupTypes(!checked);
				}
			})
			.catch((error) => {
				console.error('Failed to save Group Types setting:', error);
				// Revert the state on error
				setEnableGroupTypes(!checked);
			});
	};

	const handleAutoMembershipApprovalChange = (checked) => {
		setAutoMembershipApproval(checked);

		// Save to WordPress options via AJAX
		savePlatformSetting('bp-enable-group-auto-join', checked)
			.then((response) => {
				if (response.success) {
					console.log('Auto Membership Approval setting saved:', response);
				} else {
					console.error('Failed to save Auto Membership Approval setting:', response);
					setAutoMembershipApproval(!checked);
				}
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

		deleteGroupType(typeId).then((response) => {
			if (response.success) {
				setOpenMenuId(null);
				loadGroupTypes();
			}
		});
	};

	const handleView = (typeId) => {
		setOpenMenuId(null);
		// Find the group type data
		const groupType = groupTypes.find(type => (type.name || type.id || type.ID) === typeId);
		setEditingGroupType(groupType);
		setIsModalOpen(true);
		// TODO: Add view-only mode to modal
	};

	const handleEdit = (typeId) => {
		setOpenMenuId(null);
		// Find the group type data
		const groupType = groupTypes.find(type => (type.name || type.id || type.ID) === typeId);
		setEditingGroupType(groupType);
		setIsModalOpen(true);
	};

	const handleAddNewType = () => {
		setEditingGroupType(null);
		setIsModalOpen(true);
	};

	const handleModalClose = () => {
		setIsModalOpen(false);
		setEditingGroupType(null);
	};

	const handleModalSave = (savedGroupType) => {
		// Reload group types after save
		loadGroupTypes();
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
						{/* Card 1: Group Type Settings */}
						<div className="bb-admin-group-types__card">
							<div className="bb-admin-group-types__card-header">
								<h2>{__('Group Type Settings', 'buddyboss')}</h2>
								<button className="bb-admin-group-types__help-btn" aria-label={__('Help', 'buddyboss')}>
									<HelpIcon />
								</button>
							</div>

							<div className="bb-admin-group-types__settings">
								{/* Group Types Toggle */}
								<div className="bb-admin-group-types__setting-row">
									<div className="bb-admin-group-types__setting-label">
										<span>{__('Group Types', 'buddyboss')}</span>
									</div>
									<div className="bb-admin-group-types__setting-control">
										<div className="bb-admin-group-types__toggle-wrap">
											<ToggleControl
												checked={enableGroupTypes}
												onChange={handleEnableGroupTypesChange}
												disabled={settingsLoading}
												__nextHasNoMarginBottom
											/>
											<span className="bb-admin-group-types__toggle-label">
												{__('Enable group types', 'buddyboss')}
											</span>
										</div>
										<p className="bb-admin-group-types__setting-desc">
											{__('When enabled,', 'buddyboss')}{' '}
											<span className="bb-admin-group-types__setting-link">{__('group types', 'buddyboss')}</span>
											{' '}{__('allow you to better organize groups.', 'buddyboss')}
										</p>
									</div>
								</div>

								{/* Auto Membership Approval Toggle */}
								<div className="bb-admin-group-types__setting-row bb-admin-group-types__setting-row--last">
									<div className="bb-admin-group-types__setting-label">
										<span>{__('Auto Membership Approval', 'buddyboss')}</span>
									</div>
									<div className="bb-admin-group-types__setting-control">
										<div className="bb-admin-group-types__toggle-wrap">
											<ToggleControl
												checked={autoMembershipApproval}
												onChange={handleAutoMembershipApprovalChange}
												disabled={settingsLoading}
												__nextHasNoMarginBottom
											/>
											<span className="bb-admin-group-types__toggle-label">
												{__('Allow selected profile types to automatically join groups', 'buddyboss')}
											</span>
										</div>
										<p className="bb-admin-group-types__setting-desc">
											{__('When a member requests to join a group their membership is automatically accepted.', 'buddyboss')}
										</p>
									</div>
								</div>
							</div>
						</div>

						{/* Card 2: Group Types List */}
						<div className="bb-admin-group-types__card bb-admin-group-types__card--list">
							<div className="bb-admin-group-types__list-header">
								<h2>{__('Group Types', 'buddyboss')}</h2>
								<button className="bb-admin-group-types__add-btn" onClick={handleAddNewType}>
									<PlusIcon />
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
										const typeId = type.name || type.id || type.ID;
										const typeName = type.labels?.name || type.name || type.post_title || '';
										const typeLabel = type.labels?.singular_name || type.slug || type.post_name || typeName;
										const groupCount = type.groups_count || type.count || 0;
										const privacy = type.privacy || 'public';

										return (
											<div key={typeId} className="bb-admin-group-types__item">
												{/* Name Column */}
												<div className="bb-admin-group-types__col bb-admin-group-types__col--name">
													<div className="bb-admin-group-types__item-icon">
														<TagIcon />
													</div>
													<span className="bb-admin-group-types__item-name">{typeName}</span>
												</div>

												{/* Label Column */}
												<div className="bb-admin-group-types__col bb-admin-group-types__col--label">
													<span className="bb-admin-group-types__badge">{typeLabel}</span>
												</div>

												{/* Groups Column */}
												<div className="bb-admin-group-types__col bb-admin-group-types__col--groups">
													<GroupsIcon />
													<a href={`#/groups/all?type=${typeId}`} className="bb-admin-group-types__groups-link">
														{groupCount} {groupCount === 1 ? __('group', 'buddyboss') : __('groups', 'buddyboss')}
													</a>
												</div>

												{/* Visibility Column */}
												<div className="bb-admin-group-types__col bb-admin-group-types__col--visibility">
													<span className="bb-admin-group-types__visibility-badge">
														<GlobeIcon />
														<span>{privacy === 'public' ? __('Public', 'buddyboss') : privacy === 'private' ? __('Private', 'buddyboss') : __('Hidden', 'buddyboss')}</span>
													</span>
												</div>

												{/* Actions Column */}
												<div className="bb-admin-group-types__col bb-admin-group-types__col--actions" ref={openMenuId === typeId ? menuRef : null}>
													<button
														className="bb-admin-group-types__ellipsis-btn"
														onClick={(e) => {
															e.stopPropagation();
															setOpenMenuId(openMenuId === typeId ? null : typeId);
														}}
													>
														<EllipsisIcon />
													</button>
													{openMenuId === typeId && (
														<div className="bb-admin-group-types__menu">
															<button
																onClick={(e) => {
																	e.stopPropagation();
																	handleView(typeId);
																}}
																className="bb-admin-group-types__menu-item"
															>
																<ViewIcon />
																<span>{__('View', 'buddyboss')}</span>
															</button>
															<button
																onClick={(e) => {
																	e.stopPropagation();
																	handleEdit(typeId);
																}}
																className="bb-admin-group-types__menu-item"
															>
																<EditIcon />
																<span>{__('Edit', 'buddyboss')}</span>
															</button>
															<button
																onClick={(e) => {
																	e.stopPropagation();
																	handleDelete(typeId);
																}}
																className="bb-admin-group-types__menu-item bb-admin-group-types__menu-delete"
															>
																<DeleteIcon />
																<span>{__('Delete', 'buddyboss')}</span>
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
				</main>
			</div>

			{/* Group Type Modal */}
			<GroupTypeModal
				isOpen={isModalOpen}
				onClose={handleModalClose}
				onSave={handleModalSave}
				groupType={editingGroupType}
			/>
		</div>
	);
}
