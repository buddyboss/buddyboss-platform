/**
 * BuddyBoss Admin Settings 2.0 - Activity List Screen
 *
 * Matches Figma design: allActivities (node 2580:61804)
 * Includes sidebar navigation like feature settings
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss 3.0.0
 */

import { useState, useEffect, useRef } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Spinner } from '@wordpress/components';
import apiFetch from '@wordpress/api-fetch';
import { SideNavigation } from '../SideNavigation';

// SVG Icons matching Figma design
const ClockIcon = () => (
	<svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
		<path d="M8 14.5C11.5899 14.5 14.5 11.5899 14.5 8C14.5 4.41015 11.5899 1.5 8 1.5C4.41015 1.5 1.5 4.41015 1.5 8C1.5 11.5899 4.41015 14.5 8 14.5Z" stroke="#666666" strokeLinecap="round" strokeLinejoin="round"/>
		<path d="M8 4.5V8L10.5 9.5" stroke="#666666" strokeLinecap="round" strokeLinejoin="round"/>
	</svg>
);

const EllipsisIcon = () => (
	<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
		<circle cx="12" cy="12" r="1.5" fill="#2F2F2F"/>
		<circle cx="12" cy="6" r="1.5" fill="#2F2F2F"/>
		<circle cx="12" cy="18" r="1.5" fill="#2F2F2F"/>
	</svg>
);

const SearchIcon = () => (
	<svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
		<path d="M9.0625 15.625C12.6869 15.625 15.625 12.6869 15.625 9.0625C15.625 5.43813 12.6869 2.5 9.0625 2.5C5.43813 2.5 2.5 5.43813 2.5 9.0625C2.5 12.6869 5.43813 15.625 9.0625 15.625Z" stroke="#3D3D3D" strokeLinecap="round" strokeLinejoin="round"/>
		<path d="M13.7031 13.7031L17.5 17.5" stroke="#3D3D3D" strokeLinecap="round" strokeLinejoin="round"/>
	</svg>
);

const ChevronDownIcon = () => (
	<svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
		<path d="M13.3535 5.35378L8.35354 10.3538C8.3071 10.4003 8.25196 10.4372 8.19126 10.4623C8.13056 10.4875 8.0655 10.5004 7.99979 10.5004C7.93408 10.5004 7.86902 10.4875 7.80832 10.4623C7.74762 10.4372 7.69248 10.4003 7.64604 10.3538L2.64604 5.35378C2.55222 5.25996 2.49951 5.13272 2.49951 5.00003C2.49951 4.86735 2.55222 4.7401 2.64604 4.64628C2.73986 4.55246 2.86711 4.49976 2.99979 4.49976C3.13247 4.49976 3.25972 4.55246 3.35354 4.64628L7.99979 9.29316L12.646 4.64628C12.6925 4.59983 12.7476 4.56298 12.8083 4.53784C12.869 4.5127 12.9341 4.49976 12.9998 4.49976C13.0655 4.49976 13.1305 4.5127 13.1912 4.53784C13.2519 4.56298 13.3071 4.59983 13.3535 4.64628C13.4 4.69274 13.4368 4.74789 13.462 4.80859C13.4871 4.86928 13.5001 4.93434 13.5001 5.00003C13.5001 5.06573 13.4871 5.13079 13.462 5.19148C13.4368 5.25218 13.4 5.30733 13.3535 5.35378Z" fill="#666666"/>
	</svg>
);

// Checkbox component matching Figma design
const Checkbox = ({ checked, onChange }) => (
	<button
		type="button"
		className={`bb-admin-activity-list__checkbox ${checked ? 'bb-admin-activity-list__checkbox--checked' : ''}`}
		onClick={() => onChange(!checked)}
	>
		{checked ? (
			<svg width="18" height="18" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg">
				<rect width="18" height="18" rx="4" fill="#EF5D33"/>
				<path d="M13.5 5.25L7.125 11.625L4.5 9" stroke="white" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round"/>
			</svg>
		) : (
			<svg width="18" height="18" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg">
				<rect x="0.5" y="0.5" width="17" height="17" rx="3.5" stroke="#999999"/>
			</svg>
		)}
	</button>
);

/**
 * Activity List Screen Component
 *
 * @param {Object} props Component props
 * @param {Function} props.onNavigate Navigation callback
 * @returns {JSX.Element} Activity list screen
 */
export default function ActivityListScreen({ onNavigate }) {
	const [activities, setActivities] = useState([]);
	const [isLoading, setIsLoading] = useState(true);
	const [total, setTotal] = useState(0);
	const [page, setPage] = useState(1);
	const [perPage] = useState(10);
	const [search, setSearch] = useState('');
	const [bulkAction, setBulkAction] = useState('');
	const [selectedIds, setSelectedIds] = useState([]);
	const [actionType, setActionType] = useState('');
	const [statusFilter, setStatusFilter] = useState('');
	const [openMenuId, setOpenMenuId] = useState(null);
	const menuRef = useRef(null);

	// Feature data for sidebar
	const [sidePanels, setSidePanels] = useState([]);
	const [navItems, setNavItems] = useState([]);
	const [sidebarLoading, setSidebarLoading] = useState(true);

	// Load sidebar data
	useEffect(() => {
		apiFetch({ path: `/buddyboss/v1/features/activity/settings` })
			.then((response) => {
				setSidePanels(response.data?.side_panels || []);
				setNavItems(response.data?.navigation || []);
				setSidebarLoading(false);
			})
			.catch(() => {
				setSidebarLoading(false);
			});
	}, []);

	useEffect(() => {
		loadActivities();
	}, [page, search, actionType, statusFilter]);

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

	const loadActivities = () => {
		setIsLoading(true);

		const params = new URLSearchParams({
			page: page.toString(),
			per_page: perPage.toString(),
			order: 'desc',
		});

		if (search) {
			params.append('search', search);
		}
		if (actionType) {
			params.append('type', actionType);
		}
		if (statusFilter) {
			params.append('spam', statusFilter);
		}

		// Use parse: false to access response headers for pagination
		apiFetch({ 
			path: `/buddyboss/v1/activity?${params.toString()}`,
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
				console.log('Activity API Response:', data);
				// Handle both array response and wrapped response
				const activities = Array.isArray(data) ? data : (data.data || data || []);
				console.log('Parsed activities:', activities, 'Total:', total);
				setActivities(Array.isArray(activities) ? activities : []);
				setIsLoading(false);
			})
			.catch((error) => {
				console.error('Activity API Error:', error);
				setActivities([]);
				setTotal(0);
				setIsLoading(false);
			});
	};

	const handleSelectAll = (checked) => {
		if (checked) {
			setSelectedIds(activities.map((a) => a.id));
		} else {
			setSelectedIds([]);
		}
	};

	const handleSelectItem = (id, checked) => {
		if (checked) {
			setSelectedIds([...selectedIds, id]);
		} else {
			setSelectedIds(selectedIds.filter((i) => i !== id));
		}
	};

	const handleBulkApply = () => {
		if (!bulkAction || selectedIds.length === 0) {
			return;
		}

		if (bulkAction === 'delete') {
			if (!confirm(__('Are you sure you want to delete selected activities?', 'buddyboss'))) {
				return;
			}

			Promise.all(
				selectedIds.map((id) =>
					apiFetch({
						path: `/buddyboss/v1/activity/${id}`,
						method: 'DELETE',
						headers: { 'X-WP-Nonce': bbAdminData?.nonce || '' },
					})
				)
			).then(() => {
				setSelectedIds([]);
				setBulkAction('');
				loadActivities();
			});
		}
	};

	const handleDelete = (activityId) => {
		if (!confirm(__('Are you sure you want to delete this activity?', 'buddyboss'))) {
			return;
		}

		apiFetch({
			path: `/buddyboss/v1/activity/${activityId}`,
			method: 'DELETE',
			headers: { 'X-WP-Nonce': bbAdminData?.nonce || '' },
		}).then(() => {
			setOpenMenuId(null);
			loadActivities();
		});
	};

	const handleEdit = (activityId) => {
		setOpenMenuId(null);
		onNavigate(`/activity/${activityId}/edit`);
	};

	const handleMarkSpam = (activityId) => {
		setOpenMenuId(null);
		// Implement mark as spam functionality
	};

	const handleBack = () => {
		onNavigate('/settings');
	};

	// Format date to "23 Oct, 15:13:05" format
	const formatDate = (dateString) => {
		if (!dateString) return '';
		const date = new Date(dateString);
		const day = date.getDate();
		const month = date.toLocaleString('en-US', { month: 'short' });
		const time = date.toLocaleTimeString('en-US', { hour12: false, hour: '2-digit', minute: '2-digit', second: '2-digit' });
		return `${day} ${month}, ${time}`;
	};

	const totalPages = Math.ceil(total / perPage);
	const allSelected = activities.length > 0 && selectedIds.length === activities.length;

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
							featureId="activity"
							sidePanels={sidePanels}
							navItems={navItems}
							currentPanel="all_activity"
							onNavigate={onNavigate}
							onBack={handleBack}
						/>
					)}
				</aside>

				{/* Main Content */}
				<main className="bb-admin-feature-settings__main">
					<div className="bb-admin-activity-list">
						{/* Feature Card with Title Section */}
						<div className="bb-admin-activity-list__card">
							{/* Section Title Header */}
							<div className="bb-admin-activity-list__section-header">
								<div className="bb-admin-activity-list__section-header-content">
									<h2>{__('Activities', 'buddyboss')}</h2>
								</div>
							</div>

							{/* Action Bar */}
							<div className="bb-admin-activity-list__action-bar">
								<div className="bb-admin-activity-list__action-bar-wrap">
									{/* Left: Bulk Actions */}
									<div className="bb-admin-activity-list__action-left">
										<div className="bb-admin-activity-list__filter">
											<div className="bb-admin-activity-list__select-wrap">
												<select
													className="bb-admin-activity-list__select"
													value={bulkAction}
													onChange={(e) => setBulkAction(e.target.value)}
												>
													<option value="">{__('Bulk actions', 'buddyboss')}</option>
													<option value="delete">{__('Delete', 'buddyboss')}</option>
													<option value="mark_spam">{__('Mark as Spam', 'buddyboss')}</option>
												</select>
											</div>
											<button
												className={`bb-admin-activity-list__apply-btn ${!bulkAction || selectedIds.length === 0 ? 'bb-admin-activity-list__apply-btn--disabled' : ''}`}
												onClick={handleBulkApply}
												disabled={!bulkAction || selectedIds.length === 0}
											>
												{__('Apply', 'buddyboss')}
											</button>
										</div>
									</div>

									{/* Right: Filters */}
									<div className="bb-admin-activity-list__action-right">
										<div className="bb-admin-activity-list__select-wrap bb-admin-activity-list__select-wrap--count">
											<select
												className="bb-admin-activity-list__select"
												value={statusFilter}
												onChange={(e) => setStatusFilter(e.target.value)}
											>
												<option value="">{__('All', 'buddyboss')} ({total})</option>
												<option value="ham">{__('Not Spam', 'buddyboss')}</option>
												<option value="spam">{__('Spam', 'buddyboss')}</option>
											</select>
										</div>

										<div className="bb-admin-activity-list__select-wrap bb-admin-activity-list__select-wrap--actions">
											<select
												className="bb-admin-activity-list__select"
												value={actionType}
												onChange={(e) => setActionType(e.target.value)}
											>
												<option value="">{__('All Actions', 'buddyboss')}</option>
												<option value="activity_update">{__('Activity Updates', 'buddyboss')}</option>
												<option value="activity_comment">{__('Activity Comments', 'buddyboss')}</option>
												<option value="new_member">{__('New Members', 'buddyboss')}</option>
												<option value="joined_group">{__('Joined Group', 'buddyboss')}</option>
												<option value="created_group">{__('Created Group', 'buddyboss')}</option>
											</select>
										</div>

										<div className="bb-admin-activity-list__search-wrap">
											<input
												type="text"
												className="bb-admin-activity-list__search-input"
												placeholder={__('Search activities', 'buddyboss')}
												value={search}
												onChange={(e) => setSearch(e.target.value)}
											/>
											<span className="bb-admin-activity-list__search-icon">
												<SearchIcon />
											</span>
										</div>
									</div>
								</div>
							</div>

							{/* Table Header */}
							<div className="bb-admin-activity-list__table-header">
								<div className="bb-admin-activity-list__table-header-wrap">
									<div className="bb-admin-activity-list__col bb-admin-activity-list__col--author">
										<Checkbox
											checked={allSelected}
											onChange={handleSelectAll}
										/>
										<span className="bb-admin-activity-list__header-label">{__('Author', 'buddyboss')}</span>
									</div>
									<div className="bb-admin-activity-list__col bb-admin-activity-list__col--activity">
										<span className="bb-admin-activity-list__header-label bb-admin-activity-list__header-label--muted">{__('Activity', 'buddyboss')}</span>
									</div>
									<div className="bb-admin-activity-list__col bb-admin-activity-list__col--submitted">
										<span className="bb-admin-activity-list__header-label bb-admin-activity-list__header-label--muted">{__('Submitted', 'buddyboss')}</span>
									</div>
								</div>
							</div>

							{/* Table Body / List */}
							<div className="bb-admin-activity-list__list">
								{isLoading ? (
									<div className="bb-admin-activity-list__loading">
										<Spinner />
									</div>
								) : activities.length === 0 ? (
									<div className="bb-admin-activity-list__empty">
										{__('No activities found.', 'buddyboss')}
									</div>
								) : (
									activities.map((activity) => {
										const activityId = activity.id || activity.ID;
										// Use 'name' field for user display name
										const userName = activity.name || activity.user_name || activity.display_name || __('Unknown', 'buddyboss');
										// Use user_avatar.thumb for avatar URL
										const userAvatar = activity.user_avatar?.thumb || activity.user_avatar?.full || (typeof activity.user_avatar === 'string' ? activity.user_avatar : '') || '';
										const userLink = activity.link || activity.user_link || activity.permalink || '#';
										
										// Parse the title to get action text (strip HTML tags for display)
										let actionText = '';
										if (activity.title) {
											// Strip HTML tags from title to get plain text action
											const tempDiv = document.createElement('div');
											tempDiv.innerHTML = activity.title;
											actionText = tempDiv.textContent || tempDiv.innerText || '';
											// Remove the username from the beginning if present
											if (actionText.startsWith(userName)) {
												actionText = actionText.substring(userName.length).trim();
											}
										} else {
											actionText = activity.action_text || activity.type?.replace(/_/g, ' ') || '';
										}
										
										const dateFormatted = formatDate(activity.date || activity.date_recorded);
										const isSpam = activity.is_spam || activity.status === 'spam' || false;
										const groupName = activity.activity_data?.group_name || activity.group_name || '';
										
										// Get content - can be object with 'rendered' or string
										// Strip HTML to get plain text for display
										let content = '';
										if (activity.content_stripped) {
											content = activity.content_stripped;
										} else if (activity.content?.rendered) {
											// Strip HTML from rendered content
											const contentDiv = document.createElement('div');
											contentDiv.innerHTML = activity.content.rendered;
											content = contentDiv.textContent || contentDiv.innerText || '';
										} else if (typeof activity.content === 'string') {
											// Strip HTML from string content
											const contentDiv = document.createElement('div');
											contentDiv.innerHTML = activity.content;
											content = contentDiv.textContent || contentDiv.innerText || '';
										}
										// Trim whitespace
										content = content.trim();

										return (
											<div key={activityId} className="bb-admin-activity-list__item">
												<div className="bb-admin-activity-list__item-wrap">
													{/* Author Column */}
													<div className="bb-admin-activity-list__col bb-admin-activity-list__col--author">
														<Checkbox
															checked={selectedIds.includes(activityId)}
															onChange={(checked) => handleSelectItem(activityId, checked)}
														/>
														<div className="bb-admin-activity-list__user">
															{userAvatar ? (
																<img
																	src={userAvatar}
																	alt={userName}
																	className="bb-admin-activity-list__avatar"
																/>
															) : (
																<div className="bb-admin-activity-list__avatar bb-admin-activity-list__avatar--placeholder">
																	<svg width="40" height="40" viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg">
																		<circle cx="20" cy="20" r="19.75" fill="#F6F6F6" stroke="#D9D9D9" strokeWidth="0.5"/>
																		<path d="M20 20C22.7614 20 25 17.7614 25 15C25 12.2386 22.7614 10 20 10C17.2386 10 15 12.2386 15 15C15 17.7614 17.2386 20 20 20Z" fill="#D9D9D9"/>
																		<path d="M26.25 25.9375C26.25 23.2119 23.4518 31 20 31C16.5482 31 13.75 23.2119 13.75 25.9375C13.75 28.6631 16.5482 25.625 20 25.625C23.4518 25.625 26.25 23.2119 26.25 25.9375Z" fill="#D9D9D9"/>
																	</svg>
																</div>
															)}
															<div className="bb-admin-activity-list__user-info">
																<a href={userLink} className="bb-admin-activity-list__username" target="_blank" rel="noopener noreferrer">
																	{userName}
																</a>
																{isSpam && (
																	<span className="bb-admin-activity-list__spam-badge">{__('Spam', 'buddyboss')}</span>
																)}
															</div>
														</div>
													</div>

													{/* Activity Column */}
													<div className="bb-admin-activity-list__col bb-admin-activity-list__col--activity">
														<div className="bb-admin-activity-list__action-row">
															<span className="bb-admin-activity-list__action-user">{userName}</span>
															<span className="bb-admin-activity-list__action-desc">{actionText}</span>
														</div>
														<div className="bb-admin-activity-list__content-row">
															{groupName ? (
																<a href={activity.link || '#'} className="bb-admin-activity-list__content-link" target="_blank" rel="noopener noreferrer">
																	{groupName}
																</a>
															) : content && content.length > 0 ? (
																<span className="bb-admin-activity-list__content-text">
																	{content.length > 100 ? content.substring(0, 100) + '...' : content}
																</span>
															) : null}
														</div>
													</div>

													{/* Submitted Column */}
													<div className="bb-admin-activity-list__col bb-admin-activity-list__col--submitted">
														<ClockIcon />
														<span className="bb-admin-activity-list__date">{dateFormatted}</span>
													</div>

													{/* Actions Menu */}
													<div className="bb-admin-activity-list__actions" ref={openMenuId === activityId ? menuRef : null}>
														<button
															className="bb-admin-activity-list__ellipsis-btn"
															onClick={() => setOpenMenuId(openMenuId === activityId ? null : activityId)}
														>
															<EllipsisIcon />
														</button>
														{openMenuId === activityId && (
															<div className="bb-admin-activity-list__menu">
																<button onClick={() => handleEdit(activityId)}>
																	{__('Edit', 'buddyboss')}
																</button>
																<button onClick={() => handleDelete(activityId)}>
																	{__('Delete', 'buddyboss')}
																</button>
																<button onClick={() => handleMarkSpam(activityId)}>
																	{__('Mark as Spam', 'buddyboss')}
																</button>
															</div>
														)}
													</div>
												</div>
											</div>
										);
									})
								)}
							</div>
						</div>

						{/* Pagination */}
						{totalPages > 1 && (
							<div className="bb-admin-activity-list__pagination">
								<div className="bb-admin-activity-list__pagination-info">
									{__('Page', 'buddyboss')} {page} {__('of', 'buddyboss')} {totalPages}
								</div>
								<div className="bb-admin-activity-list__pagination-buttons">
									<button
										className="bb-admin-activity-list__pagination-btn"
										disabled={page === 1}
										onClick={() => setPage(page - 1)}
									>
										{__('Previous', 'buddyboss')}
									</button>
									<button
										className="bb-admin-activity-list__pagination-btn"
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
		</div>
	);
}
