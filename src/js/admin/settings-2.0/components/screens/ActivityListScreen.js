/**
 * BuddyBoss Admin Settings 2.0 - Activity List Screen
 *
 * Matches Figma design: allActivities (node 2580:61785)
 * Includes sidebar navigation like feature settings
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss 3.0.0
 */

import { useState, useEffect, useRef } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Spinner, CheckboxControl } from '@wordpress/components';
import apiFetch from '@wordpress/api-fetch';
import { SideNavigation } from '../SideNavigation';

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
	}, [page, search, actionType]);

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
			orderby: 'date',
			order: 'DESC',
		});

		if (search) {
			params.append('search', search);
		}
		if (actionType) {
			params.append('type', actionType);
		}

		apiFetch({ path: `/buddyboss/v1/activity?${params.toString()}` })
			.then((response) => {
				setActivities(response.data || []);
				setTotal(response.pagination?.total || 0);
				setIsLoading(false);
			})
			.catch(() => {
				setActivities([]);
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

			// Delete each selected activity
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

	const handleBack = () => {
		onNavigate('/settings');
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
						{/* Section Title */}
						<div className="bb-admin-activity-list__section-title">
							<h2>{__('All Activities', 'buddyboss')}</h2>
						</div>

						{/* Feature Card */}
						<div className="bb-admin-activity-list__card">
							{/* Action Bar */}
							<div className="bb-admin-activity-list__action-bar">
								<div className="bb-admin-activity-list__action-bar-wrap">
									{/* Left: Bulk Actions */}
									<div className="bb-admin-activity-list__action-left">
										<div className="bb-admin-activity-list__filter">
											<select
												className="bb-admin-activity-list__select"
												value={bulkAction}
												onChange={(e) => setBulkAction(e.target.value)}
											>
												<option value="">{__('Bulk actions', 'buddyboss')}</option>
												<option value="delete">{__('Delete', 'buddyboss')}</option>
												<option value="mark_spam">{__('Mark as Spam', 'buddyboss')}</option>
											</select>
											<button
												className="bb-admin-activity-list__apply-btn"
												onClick={handleBulkApply}
												disabled={!bulkAction || selectedIds.length === 0}
											>
												{__('Apply', 'buddyboss')}
											</button>
										</div>
									</div>

									{/* Right: Filters */}
									<div className="bb-admin-activity-list__action-right">
										<select
											className="bb-admin-activity-list__select bb-admin-activity-list__select--count"
											value=""
											onChange={() => {}}
										>
											<option value="">{__('All', 'buddyboss')} ({total})</option>
											<option value="ham">{__('Not Spam', 'buddyboss')}</option>
											<option value="spam">{__('Spam', 'buddyboss')}</option>
										</select>

										<select
											className="bb-admin-activity-list__select bb-admin-activity-list__select--actions"
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

										<div className="bb-admin-activity-list__search-wrap">
											<input
												type="text"
												className="bb-admin-activity-list__search-input"
												placeholder={__('Search activities', 'buddyboss')}
												value={search}
												onChange={(e) => setSearch(e.target.value)}
											/>
											<span className="bb-admin-activity-list__search-icon dashicons dashicons-search"></span>
										</div>
									</div>
								</div>
							</div>

							{/* Table Header */}
							<div className="bb-admin-activity-list__table-header">
								<div className="bb-admin-activity-list__table-header-wrap">
									<div className="bb-admin-activity-list__col bb-admin-activity-list__col--author">
										<CheckboxControl
											checked={allSelected}
											onChange={handleSelectAll}
											__nextHasNoMarginBottom
										/>
										<span>{__('Author', 'buddyboss')}</span>
									</div>
									<div className="bb-admin-activity-list__col bb-admin-activity-list__col--activity">
										{__('Activity', 'buddyboss')}
									</div>
									<div className="bb-admin-activity-list__col bb-admin-activity-list__col--submitted">
										{__('Submitted', 'buddyboss')}
									</div>
								</div>
							</div>

							{/* Table Body */}
							<div className="bb-admin-activity-list__table-body">
								{isLoading ? (
									<div className="bb-admin-activity-list__loading">
										<Spinner />
									</div>
								) : activities.length === 0 ? (
									<div className="bb-admin-activity-list__empty">
										{__('No activities found.', 'buddyboss')}
									</div>
								) : (
									activities.map((activity) => (
										<div key={activity.id} className="bb-admin-activity-list__row">
											<div className="bb-admin-activity-list__row-wrap">
												{/* Author Column */}
												<div className="bb-admin-activity-list__col bb-admin-activity-list__col--author">
													<CheckboxControl
														checked={selectedIds.includes(activity.id)}
														onChange={(checked) => handleSelectItem(activity.id, checked)}
														__nextHasNoMarginBottom
													/>
													<div className="bb-admin-activity-list__user">
														<img
															src={activity.user_avatar || ''}
															alt=""
															className="bb-admin-activity-list__avatar"
														/>
														<a href="#" className="bb-admin-activity-list__username">
															{activity.user_name || __('Unknown', 'buddyboss')}
														</a>
													</div>
												</div>

												{/* Activity Column */}
												<div className="bb-admin-activity-list__col bb-admin-activity-list__col--activity">
													<div className="bb-admin-activity-list__action-text">
														<span className="bb-admin-activity-list__action-user">
															{activity.user_name}
														</span>
														<span className="bb-admin-activity-list__action-desc">
															{activity.action_text || activity.type?.replace('_', ' ')}
														</span>
													</div>
													{activity.group_name && (
														<div className="bb-admin-activity-list__activity-link">
															<a href="#">{activity.group_name}</a>
														</div>
													)}
													{activity.content && (
														<div
															className="bb-admin-activity-list__activity-content"
															dangerouslySetInnerHTML={{ __html: activity.content }}
														/>
													)}
												</div>

												{/* Submitted Column */}
												<div className="bb-admin-activity-list__col bb-admin-activity-list__col--submitted">
													<span className="dashicons dashicons-clock"></span>
													<span>{activity.date_recorded_formatted || activity.date_recorded}</span>
												</div>

												{/* Actions Menu */}
												<div className="bb-admin-activity-list__actions" ref={openMenuId === activity.id ? menuRef : null}>
													<button
														className="bb-admin-activity-list__ellipsis-btn"
														onClick={() => setOpenMenuId(openMenuId === activity.id ? null : activity.id)}
													>
														<span className="dashicons dashicons-ellipsis"></span>
													</button>
													{openMenuId === activity.id && (
														<div className="bb-admin-activity-list__menu">
															<button onClick={() => handleEdit(activity.id)}>
																{__('Edit', 'buddyboss')}
															</button>
															<button onClick={() => handleDelete(activity.id)}>
																{__('Delete', 'buddyboss')}
															</button>
															<button onClick={() => setOpenMenuId(null)}>
																{__('Mark as Spam', 'buddyboss')}
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
							<div className="bb-admin-activity-list__pagination">
								<div className="bb-admin-activity-list__pagination-info">
									{page} {__('of', 'buddyboss')} {totalPages}
								</div>
								<div className="bb-admin-activity-list__pagination-buttons">
									<button
										className="bb-admin-activity-list__pagination-btn"
										disabled={page === 1}
										onClick={() => setPage(1)}
									>
										<span className="dashicons dashicons-controls-skipback"></span>
									</button>
									<button
										className="bb-admin-activity-list__pagination-btn"
										disabled={page === 1}
										onClick={() => setPage(page - 1)}
									>
										<span className="dashicons dashicons-arrow-left-alt2"></span>
									</button>
									<button
										className="bb-admin-activity-list__pagination-btn"
										disabled={page >= totalPages}
										onClick={() => setPage(page + 1)}
									>
										<span className="dashicons dashicons-arrow-right-alt2"></span>
									</button>
									<button
										className="bb-admin-activity-list__pagination-btn"
										disabled={page >= totalPages}
										onClick={() => setPage(totalPages)}
									>
										<span className="dashicons dashicons-controls-skipforward"></span>
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
