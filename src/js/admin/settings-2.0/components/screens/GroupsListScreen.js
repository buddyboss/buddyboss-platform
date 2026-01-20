/**
 * BuddyBoss Admin Settings 2.0 - Groups List Screen
 *
 * Displays all groups in a table format with filtering, sorting, and actions.
 * Design based on Figma: https://www.figma.com/design/XS2Hf0smlEnhWfoKyks7ku/Backend-Settings-2.0?node-id=2611-123285
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss 3.0.0
 */

import { useState, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Spinner, CheckboxControl } from '@wordpress/components';
import apiFetch from '@wordpress/api-fetch';
import { SideNavigation } from '../SideNavigation';
import GroupModal from '../modals/GroupModal';
import { getCachedFeatureData, setCachedFeatureData, getCachedSidebarData } from '../../utils/featureCache';

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
 * Members icon component
 */
function MembersIcon() {
	return (
		<svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
			<path d="M8 8C9.65685 8 11 6.65685 11 5C11 3.34315 9.65685 2 8 2C6.34315 2 5 3.34315 5 5C5 6.65685 6.34315 8 8 8Z" stroke="#666" strokeWidth="1.2"/>
			<path d="M14 14C14 11.7909 11.3137 10 8 10C4.68629 10 2 11.7909 2 14" stroke="#666" strokeWidth="1.2" strokeLinecap="round"/>
		</svg>
	);
}

/**
 * Clock icon component
 */
function ClockIcon() {
	return (
		<svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
			<circle cx="8" cy="8" r="6" stroke="#666" strokeWidth="1.2"/>
			<path d="M8 4.66669V8.00002L10 10" stroke="#666" strokeWidth="1.2" strokeLinecap="round"/>
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
 * Default group avatar icon component
 */
function DefaultGroupIcon() {
	return (
		<div className="bb-admin-groups-list__default-avatar">
			<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
				<path d="M12 12.75C8.83 12.75 6.25 10.17 6.25 7C6.25 3.83 8.83 1.25 12 1.25C15.17 1.25 17.75 3.83 17.75 7C17.75 10.17 15.17 12.75 12 12.75ZM12 2.75C9.66 2.75 7.75 4.66 7.75 7C7.75 9.34 9.66 11.25 12 11.25C14.34 11.25 16.25 9.34 16.25 7C16.25 4.66 14.34 2.75 12 2.75Z" fill="#666"/>
				<path d="M20.59 22.75C20.18 22.75 19.84 22.41 19.84 22C19.84 18.55 16.32 15.75 12 15.75C7.68 15.75 4.16 18.55 4.16 22C4.16 22.41 3.82 22.75 3.41 22.75C3 22.75 2.66 22.41 2.66 22C2.66 17.73 6.85 14.25 12 14.25C17.15 14.25 21.34 17.73 21.34 22C21.34 22.41 21 22.75 20.59 22.75Z" fill="#666"/>
			</svg>
		</div>
	);
}

/**
 * Search icon component
 */
function SearchIcon() {
	return (
		<svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
			<path d="M9.16667 15.8333C12.8486 15.8333 15.8333 12.8486 15.8333 9.16667C15.8333 5.48477 12.8486 2.5 9.16667 2.5C5.48477 2.5 2.5 5.48477 2.5 9.16667C2.5 12.8486 5.48477 15.8333 9.16667 15.8333Z" stroke="#3d3d3d" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round"/>
			<path d="M17.5 17.5L13.875 13.875" stroke="#3d3d3d" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round"/>
		</svg>
	);
}

/**
 * Groups List Screen Component
 *
 * @param {Object} props - Component props
 * @param {Function} props.onNavigate - Navigation callback
 * @param {boolean} props.openCreateModal - Whether to open create modal on mount
 * @returns {JSX.Element} Groups list screen
 */
export default function GroupsListScreen({ onNavigate, openCreateModal = false }) {
	const [groups, setGroups] = useState([]);
	const [groupTypes, setGroupTypes] = useState([]);
	const [isLoading, setIsLoading] = useState(true);
	const [total, setTotal] = useState(0);
	const [page, setPage] = useState(1);
	const [perPage] = useState(20);
	const [search, setSearch] = useState('');
	const [selectedGroups, setSelectedGroups] = useState([]);
	const [bulkAction, setBulkAction] = useState('');
	const [statusFilter, setStatusFilter] = useState('');
	const [orderBy, setOrderBy] = useState('last_activity');
	const [actionMenuOpen, setActionMenuOpen] = useState(null);
	const [isModalOpen, setIsModalOpen] = useState(false);
	const [editingGroup, setEditingGroup] = useState(null);

	// Sidebar data
	const [sidePanels, setSidePanels] = useState([]);
	const [navItems, setNavItems] = useState([]);

	// Load sidebar data only once on mount
	useEffect(() => {
		loadSidebarData();
	}, []);

	// Load groups when filters change
	useEffect(() => {
		loadGroups();
		loadGroupTypes();
	}, [page, search, statusFilter, orderBy]);

	// Handle openCreateModal prop
	useEffect(() => {
		if (openCreateModal) {
			setIsModalOpen(true);
			setEditingGroup(null);
			// Update URL to /groups/all
			if (window.location.hash !== '#/groups/all') {
				window.location.hash = '#/groups/all';
			}
		}
	}, [openCreateModal]);

	const loadSidebarData = () => {
		const featureId = 'groups';
		
		// Check cache first
		const cachedSidebar = getCachedSidebarData(featureId);
		if (cachedSidebar) {
			setSidePanels(cachedSidebar.sidePanels);
			setNavItems(cachedSidebar.navItems);
			return;
		}
		
		// No cache, fetch from server
		apiFetch({ path: '/buddyboss/v1/features/groups/settings' })
			.then((response) => {
				// Response is wrapped in BB_REST_Response::success() which adds a 'data' property
				const data = response.data || response;
				console.log('Groups sidebar data:', data);
				setSidePanels(data.side_panels || []);
				setNavItems(data.navigation || []);
				
				// Cache the response for future use
				setCachedFeatureData(featureId, data);
			})
			.catch((error) => {
				console.error('Error loading sidebar data:', error);
			});
	};

	const loadGroups = () => {
		setIsLoading(true);

		const params = new URLSearchParams({
			page: page.toString(),
			per_page: perPage.toString(),
			orderby: orderBy,
			order: 'desc',
			show_hidden: 'true', // Show hidden groups for admin
		});

		if (search) {
			params.append('search', search);
		}
		if (statusFilter) {
			params.append('status', statusFilter);
		}

		// Use parse: false to access response headers for pagination
		apiFetch({ 
			path: `/buddyboss/v1/groups?${params.toString()}`,
			parse: false 
		})
			.then((response) => {
				// Get total from response headers
				const totalItems = parseInt(response.headers.get('X-WP-Total') || '0', 10);
				setTotal(totalItems);
				
				// Parse JSON body
				return response.json();
			})
			.then((data) => {
				// BuddyPress API returns groups as direct array
				console.log('Groups data:', data);
				setGroups(Array.isArray(data) ? data : []);
				setIsLoading(false);
			})
			.catch((error) => {
				console.error('Error loading groups:', error);
				setIsLoading(false);
			});
	};

	const loadGroupTypes = () => {
		apiFetch({ path: '/buddyboss/v1/groups/types' })
			.then((response) => {
				setGroupTypes(response.data || []);
			})
			.catch(() => {});
	};

	const handleSelectAll = (checked) => {
		if (checked) {
			setSelectedGroups(groups.map((g) => g.id));
		} else {
			setSelectedGroups([]);
		}
	};

	const handleSelectGroup = (groupId, checked) => {
		if (checked) {
			setSelectedGroups([...selectedGroups, groupId]);
		} else {
			setSelectedGroups(selectedGroups.filter((id) => id !== groupId));
		}
	};

	const handleBulkApply = () => {
		if (!bulkAction || selectedGroups.length === 0) return;

		if (bulkAction === 'delete') {
			if (!confirm(__('Are you sure you want to delete the selected groups?', 'buddyboss'))) {
				return;
			}
			// Handle bulk delete
			Promise.all(
				selectedGroups.map((groupId) =>
					apiFetch({
						path: `/buddyboss/v1/groups/${groupId}`,
						method: 'DELETE',
					})
				)
			).then(() => {
				setSelectedGroups([]);
				loadGroups();
			});
		}
	};

	const handleDelete = (groupId) => {
		if (!confirm(__('Are you sure you want to delete this group?', 'buddyboss'))) {
			return;
		}

		apiFetch({
			path: `/buddyboss/v1/groups/${groupId}`,
			method: 'DELETE',
		})
			.then(() => {
				loadGroups();
			})
			.catch((error) => {
				console.error('Failed to delete group:', error);
			});
	};

	const formatDate = (dateString) => {
		if (!dateString) return __('No activity', 'buddyboss');
		
		// Handle ISO date format from API
		const date = new Date(dateString);
		if (isNaN(date.getTime())) {
			return dateString; // Return as-is if can't parse
		}
		
		// Calculate relative time
		const now = new Date();
		const diff = now - date;
		const days = Math.floor(diff / (1000 * 60 * 60 * 24));
		const hours = Math.floor(diff / (1000 * 60 * 60));
		const minutes = Math.floor(diff / (1000 * 60));
		
		if (minutes < 1) {
			return __('Just now', 'buddyboss');
		} else if (minutes < 60) {
			return minutes + ' ' + __('min ago', 'buddyboss');
		} else if (hours < 24) {
			return hours + ' ' + (hours === 1 ? __('hour ago', 'buddyboss') : __('hours ago', 'buddyboss'));
		} else if (days < 30) {
			return days + ' ' + (days === 1 ? __('day ago', 'buddyboss') : __('days ago', 'buddyboss'));
		} else {
			const options = { day: '2-digit', month: 'short', year: 'numeric' };
			return date.toLocaleDateString('en-US', options);
		}
	};

	const getPrivacyLabel = (status) => {
		const labels = {
			public: __('Public', 'buddyboss'),
			private: __('Private', 'buddyboss'),
			hidden: __('Hidden', 'buddyboss'),
		};
		return labels[status] || status;
	};

	const getGroupType = (group) => {
		// Check existing API format (group_type.group_type_label)
		if (group.group_type?.group_type_label) {
			return group.group_type.group_type_label;
		}
		// Check types array from existing API
		if (group.types && Array.isArray(group.types) && group.types.length > 0) {
			// Return the first type name
			return group.types[0];
		}
		// Check type_name for our custom API
		if (group.type_name) {
			return group.type_name;
		}
		return '';
	};
	
	const getGroupAvatar = (group) => {
		// Existing API format: avatar_urls.thumb
		if (group.avatar_urls?.thumb) {
			return group.avatar_urls.thumb;
		}
		// Our custom API format
		if (group.avatar) {
			return group.avatar;
		}
		return null;
	};
	
	const getMemberCount = (group) => {
		// Existing API format
		if (typeof group.total_member_count !== 'undefined') {
			return group.total_member_count;
		}
		// Our custom API format
		if (typeof group.member_count !== 'undefined') {
			return group.member_count;
		}
		return 0;
	};

	const handleSideNavigation = (route) => {
		if (onNavigate) {
			onNavigate(route);
		} else {
			window.location.hash = route;
		}
	};

	const totalPages = Math.ceil(total / perPage);

	const handleBackClick = () => {
		if (onNavigate) {
			onNavigate('/settings');
		} else {
			window.location.hash = '#/settings';
		}
	};

	const handleAddNewGroup = () => {
		setEditingGroup(null);
		setIsModalOpen(true);
	};

	const handleEditGroup = (group) => {
		setEditingGroup(group);
		setIsModalOpen(true);
	};

	const handleModalClose = () => {
		setIsModalOpen(false);
		setEditingGroup(null);
	};

	const handleModalSave = (savedGroup) => {
		// Reload groups list
		loadGroups();
	};

	return (
		<div className="bb-admin-feature-settings">
			<div className="bb-admin-feature-settings__container">
				{/* Left Sidebar Navigation */}
				<aside className="bb-admin-feature-settings__sidebar">
					<SideNavigation
						sidePanels={sidePanels}
						navItems={navItems}
						currentPanel="all_groups"
						featureId="groups"
						onNavigate={handleSideNavigation}
						onBack={handleBackClick}
					/>
				</aside>

				{/* Main Content */}
				<main className="bb-admin-feature-settings__main">
				<div className="bb-admin-groups-list">
					{/* Feature Card */}
					<div className="bb-admin-groups-list__card">
						{/* Section Title Header */}
						<div className="bb-admin-groups-list__section-header">
							<div className="bb-admin-groups-list__section-header-content">
								<h2 className="bb-admin-groups-list__section-title">{__('Groups', 'buddyboss')}</h2>
								<button
									className="bb-admin-groups-list__create-btn"
									onClick={handleAddNewGroup}
								>
									<svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
										<path d="M8 3.33337V12.6667M3.33337 8.00004H12.6667" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round"/>
									</svg>
									{__('Create New Group', 'buddyboss')}
								</button>
							</div>
						</div>

						{/* Action Bar */}
						<div className="bb-admin-groups-list__action-bar">
						<div className="bb-admin-groups-list__action-wrap">
							<div className="bb-admin-groups-list__action-left">
								<div className="bb-admin-groups-list__filter-group">
									<select
									value={bulkAction}
									onChange={(e) => setBulkAction(e.target.value)}
									className="bb-admin-groups-list__select"
								>
									<option value="">{__('Bulk actions', 'buddyboss')}</option>
									<option value="delete">{__('Delete', 'buddyboss')}</option>
								</select>
									<button
										className={`bb-admin-groups-list__apply-btn ${!bulkAction || selectedGroups.length === 0 ? 'bb-admin-groups-list__apply-btn--disabled' : ''}`}
										onClick={handleBulkApply}
										disabled={!bulkAction || selectedGroups.length === 0}
									>
										{__('Apply', 'buddyboss')}
									</button>
								</div>
							</div>

							<div className="bb-admin-groups-list__action-right">
								<select
									value={statusFilter}
									onChange={(e) => setStatusFilter(e.target.value)}
									className="bb-admin-groups-list__select"
								>
									<option value="">{__('All', 'buddyboss')} ({total})</option>
									<option value="public">{__('Public', 'buddyboss')}</option>
									<option value="private">{__('Private', 'buddyboss')}</option>
									<option value="hidden">{__('Hidden', 'buddyboss')}</option>
								</select>

								<select
									value={orderBy}
									onChange={(e) => setOrderBy(e.target.value)}
									className="bb-admin-groups-list__select"
								>
									<option value="last_activity">{__('Newest', 'buddyboss')}</option>
									<option value="date_created">{__('Date Created', 'buddyboss')}</option>
									<option value="name">{__('Name', 'buddyboss')}</option>
									<option value="total_member_count">{__('Member Count', 'buddyboss')}</option>
								</select>

								<div className="bb-admin-groups-list__search-wrap">
									<input
										type="text"
										value={search}
										onChange={(e) => setSearch(e.target.value)}
										placeholder={__('Search groups', 'buddyboss')}
										className="bb-admin-groups-list__search-input"
									/>
									<SearchIcon />
								</div>
							</div>
						</div>
					</div>

						{/* Table Header */}
						<div className="bb-admin-groups-list__table-header">
							<div className="bb-admin-groups-list__table-header-wrap">
								<div className="bb-admin-groups-list__col bb-admin-groups-list__col--name">
									<CheckboxControl
										checked={groups.length > 0 && selectedGroups.length === groups.length}
										onChange={handleSelectAll}
									/>
									<span>{__('Name', 'buddyboss')}</span>
								</div>
								<div className="bb-admin-groups-list__col bb-admin-groups-list__col--privacy">
									{__('Privacy', 'buddyboss')}
								</div>
								<div className="bb-admin-groups-list__col bb-admin-groups-list__col--members">
									{__('Members', 'buddyboss')}
								</div>
								<div className="bb-admin-groups-list__col bb-admin-groups-list__col--type">
									{__('Group Type', 'buddyboss')}
								</div>
								<div className="bb-admin-groups-list__col bb-admin-groups-list__col--activity">
									{__('Last Active', 'buddyboss')}
								</div>
							</div>
						</div>

						{/* Table Body */}
						<div className="bb-admin-groups-list__table-body">
						{isLoading ? (
							<div className="bb-admin-groups-list__loading">
								<Spinner />
							</div>
						) : groups.length === 0 ? (
							<div className="bb-admin-groups-list__empty">
								{__('No groups found.', 'buddyboss')}
							</div>
						) : (
							groups.map((group) => (
								<div key={group.id} className="bb-admin-groups-list__row">
									<div className="bb-admin-groups-list__row-wrap">
										{/* Name Column */}
										<div className="bb-admin-groups-list__col bb-admin-groups-list__col--name">
											<CheckboxControl
												checked={selectedGroups.includes(group.id)}
												onChange={(checked) => handleSelectGroup(group.id, checked)}
											/>
											{getGroupAvatar(group) ? (
												<img
													src={getGroupAvatar(group)}
													alt=""
													className="bb-admin-groups-list__avatar"
												/>
											) : (
												<DefaultGroupIcon />
											)}
											<a
												href={`#/groups/${group.id}/edit`}
												className="bb-admin-groups-list__name-link"
												title={group.name}
											>
												{group.name}
											</a>
										</div>

										{/* Privacy Column */}
										<div className="bb-admin-groups-list__col bb-admin-groups-list__col--privacy">
											<span className={`bb-admin-groups-list__privacy-badge bb-admin-groups-list__privacy-badge--${group.status}`}>
												<PrivacyIcon status={group.status} />
												<span>{getPrivacyLabel(group.status)}</span>
											</span>
										</div>

										{/* Members Column */}
										<div className="bb-admin-groups-list__col bb-admin-groups-list__col--members">
											<MembersIcon />
											<a href={`#/groups/${group.id}/members`} className="bb-admin-groups-list__members-link">
												{getMemberCount(group)}
											</a>
										</div>

										{/* Group Type Column */}
										<div className="bb-admin-groups-list__col bb-admin-groups-list__col--type">
											{getGroupType(group) && (
												<span className="bb-admin-groups-list__type-badge">
													{getGroupType(group)}
												</span>
											)}
										</div>

										{/* Last Active Column */}
										<div className="bb-admin-groups-list__col bb-admin-groups-list__col--activity">
											<ClockIcon />
											<span className="bb-admin-groups-list__activity-text">
												{formatDate(group.last_activity || group.date_created)}
											</span>
										</div>

										{/* Actions */}
										<div className="bb-admin-groups-list__col bb-admin-groups-list__col--actions">
											<button
												className="bb-admin-groups-list__action-btn"
												onClick={() => setActionMenuOpen(actionMenuOpen === group.id ? null : group.id)}
											>
												<EllipsisIcon />
											</button>
											{actionMenuOpen === group.id && (
												<div className="bb-admin-groups-list__action-menu">
													<button
														onClick={() => {
															handleEditGroup(group);
															setActionMenuOpen(null);
														}}
													>
														{__('Edit', 'buddyboss')}
													</button>
													<button
														onClick={() => {
															handleDelete(group.id);
															setActionMenuOpen(null);
														}}
														className="bb-admin-groups-list__action-menu-delete"
													>
														{__('Delete', 'buddyboss')}
													</button>
												</div>
											)}
										</div>
									</div>
								</div>
							))
							)}
						</div>
					</div>

					{/* Pagination */}
					{totalPages > 1 && (
						<div className="bb-admin-groups-list__pagination">
							<div className="bb-admin-groups-list__pagination-info">
								{__('Page', 'buddyboss')} {page} {__('of', 'buddyboss')} {totalPages}
							</div>
							<div className="bb-admin-groups-list__pagination-buttons">
								<button
									className="bb-admin-groups-list__pagination-btn"
									disabled={page === 1}
									onClick={() => setPage(page - 1)}
								>
									{__('Previous', 'buddyboss')}
								</button>
								<button
									className="bb-admin-groups-list__pagination-btn"
									disabled={page >= totalPages}
									onClick={() => setPage(page + 1)}
								>
									{__('Next', 'buddyboss')}
								</button>
							</div>
						</div>
					)}
				</div>
				</main>
			</div>

			{/* Group Modal */}
			<GroupModal
				isOpen={isModalOpen}
				onClose={handleModalClose}
				onSave={handleModalSave}
				group={editingGroup}
			/>
		</div>
	);
}
