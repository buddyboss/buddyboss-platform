/**
 * BuddyBoss Admin Settings 2.0 - Activity List Screen
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss 3.0.0
 */

import { useState, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Button, Card, CardBody, CardHeader, Spinner, TextControl, SelectControl } from '@wordpress/components';
import apiFetch from '@wordpress/api-fetch';

/**
 * Activity List Screen Component
 *
 * @returns {JSX.Element} Activity list screen
 */
export default function ActivityListScreen() {
	const [activities, setActivities] = useState([]);
	const [isLoading, setIsLoading] = useState(true);
	const [total, setTotal] = useState(0);
	const [page, setPage] = useState(1);
	const [perPage] = useState(20);
	const [search, setSearch] = useState('');
	const [filters, setFilters] = useState({
		type: '',
		component: '',
		status: '',
		orderby: 'date',
		order: 'DESC',
	});

	useEffect(() => {
		loadActivities();
	}, [page, search, filters]);

	const loadActivities = () => {
		setIsLoading(true);

		const params = new URLSearchParams({
			page: page.toString(),
			per_page: perPage.toString(),
			orderby: filters.orderby,
			order: filters.order,
		});

		if (search) {
			params.append('search', search);
		}
		if (filters.type) {
			params.append('type', filters.type);
		}
		if (filters.component) {
			params.append('component', filters.component);
		}
		if (filters.status) {
			params.append('status', filters.status);
		}

		apiFetch({ path: `/buddyboss/v1/activity?${params.toString()}` })
			.then((response) => {
				setActivities(response.data || []);
				setTotal(response.pagination?.total || 0);
				setIsLoading(false);
			})
			.catch(() => {
				setIsLoading(false);
			});
	};

	const handleDelete = (activityId) => {
		if (!confirm(__('Are you sure you want to delete this activity?', 'buddyboss'))) {
			return;
		}

		const nonce = bbAdminData?.nonce || '';

		apiFetch({
			path: `/buddyboss/v1/activity/${activityId}`,
			method: 'DELETE',
			headers: {
				'X-WP-Nonce': nonce,
			},
		})
			.then(() => {
				loadActivities();
			})
			.catch((error) => {
				console.error('Failed to delete activity:', error);
			});
	};

	const totalPages = Math.ceil(total / perPage);

	if (isLoading && activities.length === 0) {
		return (
			<div className="bb-admin-activity-list bb-admin-loading">
				<Spinner />
			</div>
		);
	}

	return (
		<div className="bb-admin-activity-list">
			<div className="bb-admin-activity-list__header">
				<h1>{__('All Activity', 'buddyboss')}</h1>
			</div>

			<div className="bb-admin-activity-list__filters">
				<TextControl
					value={search}
					onChange={setSearch}
					placeholder={__('Search activities...', 'buddyboss')}
					className="bb-admin-activity-list__search"
				/>
				<SelectControl
					label={__('Type', 'buddyboss')}
					value={filters.type}
					options={[
						{ label: __('All Types', 'buddyboss'), value: '' },
						{ label: __('Activity Update', 'buddyboss'), value: 'activity_update' },
						{ label: __('Activity Comment', 'buddyboss'), value: 'activity_comment' },
					]}
					onChange={(value) => setFilters({ ...filters, type: value })}
				/>
				<SelectControl
					label={__('Component', 'buddyboss')}
					value={filters.component}
					options={[
						{ label: __('All Components', 'buddyboss'), value: '' },
						{ label: __('Activity', 'buddyboss'), value: 'activity' },
						{ label: __('Groups', 'buddyboss'), value: 'groups' },
					]}
					onChange={(value) => setFilters({ ...filters, component: value })}
				/>
				<SelectControl
					label={__('Order By', 'buddyboss')}
					value={filters.orderby}
					options={[
						{ label: __('Date', 'buddyboss'), value: 'date' },
						{ label: __('ID', 'buddyboss'), value: 'id' },
					]}
					onChange={(value) => setFilters({ ...filters, orderby: value })}
				/>
			</div>

			<div className="bb-admin-activity-list__table">
				<table className="wp-list-table widefat fixed striped">
					<thead>
						<tr>
							<th>{__('ID', 'buddyboss')}</th>
							<th>{__('User', 'buddyboss')}</th>
							<th>{__('Content', 'buddyboss')}</th>
							<th>{__('Type', 'buddyboss')}</th>
							<th>{__('Date', 'buddyboss')}</th>
							<th>{__('Actions', 'buddyboss')}</th>
						</tr>
					</thead>
					<tbody>
						{activities.map((activity) => (
							<tr key={activity.id}>
								<td>{activity.id}</td>
								<td>
									<div className="bb-admin-activity-list__user">
										<img src={activity.user_avatar} alt="" className="bb-admin-activity-list__avatar" />
										{activity.user_name}
									</div>
								</td>
								<td>
									<div className="bb-admin-activity-list__content" dangerouslySetInnerHTML={{ __html: activity.content }} />
								</td>
								<td>{activity.type}</td>
								<td>{activity.date_recorded_formatted}</td>
								<td>
									<Button
										variant="link"
										isDestructive
										onClick={() => handleDelete(activity.id)}
									>
										{__('Delete', 'buddyboss')}
									</Button>
								</td>
							</tr>
						))}
					</tbody>
				</table>
			</div>

			{totalPages > 1 && (
				<div className="bb-admin-activity-list__pagination">
					<Button
						variant="secondary"
						disabled={page === 1}
						onClick={() => setPage(page - 1)}
					>
						{__('Previous', 'buddyboss')}
					</Button>
					<span className="bb-admin-activity-list__page-info">
						{__('Page', 'buddyboss')} {page} {__('of', 'buddyboss')} {totalPages} ({total} {__('total', 'buddyboss')})
					</span>
					<Button
						variant="secondary"
						disabled={page >= totalPages}
						onClick={() => setPage(page + 1)}
					>
						{__('Next', 'buddyboss')}
					</Button>
				</div>
			)}
		</div>
	);
}
