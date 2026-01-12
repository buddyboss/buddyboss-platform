/**
 * BuddyBoss Admin Settings 2.0 - Groups List Screen
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss 3.0.0
 */

import { useState, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Button, Card, CardBody, CardHeader, Spinner, TextControl, SelectControl } from '@wordpress/components';
import apiFetch from '@wordpress/api-fetch';

/**
 * Groups List Screen Component
 *
 * @returns {JSX.Element} Groups list screen
 */
export default function GroupsListScreen() {
	const [groups, setGroups] = useState([]);
	const [isLoading, setIsLoading] = useState(true);
	const [total, setTotal] = useState(0);
	const [page, setPage] = useState(1);
	const [perPage] = useState(20);
	const [search, setSearch] = useState('');
	const [filters, setFilters] = useState({
		status: '',
		type: '',
		orderby: 'date_created',
		order: 'DESC',
	});

	useEffect(() => {
		loadGroups();
	}, [page, search, filters]);

	const loadGroups = () => {
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
		if (filters.status) {
			params.append('status', filters.status);
		}
		if (filters.type) {
			params.append('type', filters.type);
		}

		apiFetch({ path: `/buddyboss/v1/groups?${params.toString()}` })
			.then((response) => {
				setGroups(response.data || []);
				setTotal(response.pagination?.total || 0);
				setIsLoading(false);
			})
			.catch(() => {
				setIsLoading(false);
			});
	};

	const handleDelete = (groupId) => {
		if (!confirm(__('Are you sure you want to delete this group?', 'buddyboss'))) {
			return;
		}

		const nonce = bbAdminData?.nonce || '';

		apiFetch({
			path: `/buddyboss/v1/groups/${groupId}`,
			method: 'DELETE',
			headers: {
				'X-WP-Nonce': nonce,
			},
		})
			.then(() => {
				loadGroups();
			})
			.catch((error) => {
				console.error('Failed to delete group:', error);
			});
	};

	const totalPages = Math.ceil(total / perPage);

	if (isLoading && groups.length === 0) {
		return (
			<div className="bb-admin-groups-list bb-admin-loading">
				<Spinner />
			</div>
		);
	}

	return (
		<div className="bb-admin-groups-list">
			<div className="bb-admin-groups-list__header">
				<h1>{__('All Groups', 'buddyboss')}</h1>
				<Button
					variant="primary"
					onClick={() => {
						window.location.hash = '#/groups/create';
					}}
				>
					{__('Create Group', 'buddyboss')}
				</Button>
			</div>

			<div className="bb-admin-groups-list__filters">
				<TextControl
					value={search}
					onChange={setSearch}
					placeholder={__('Search groups...', 'buddyboss')}
					className="bb-admin-groups-list__search"
				/>
				<SelectControl
					label={__('Status', 'buddyboss')}
					value={filters.status}
					options={[
						{ label: __('All Statuses', 'buddyboss'), value: '' },
						{ label: __('Public', 'buddyboss'), value: 'public' },
						{ label: __('Private', 'buddyboss'), value: 'private' },
						{ label: __('Hidden', 'buddyboss'), value: 'hidden' },
					]}
					onChange={(value) => setFilters({ ...filters, status: value })}
				/>
				<SelectControl
					label={__('Order By', 'buddyboss')}
					value={filters.orderby}
					options={[
						{ label: __('Date Created', 'buddyboss'), value: 'date_created' },
						{ label: __('Name', 'buddyboss'), value: 'name' },
						{ label: __('Last Activity', 'buddyboss'), value: 'last_activity' },
						{ label: __('Member Count', 'buddyboss'), value: 'total_member_count' },
					]}
					onChange={(value) => setFilters({ ...filters, orderby: value })}
				/>
			</div>

			<div className="bb-admin-groups-list__table">
				<table className="wp-list-table widefat fixed striped">
					<thead>
						<tr>
							<th>{__('ID', 'buddyboss')}</th>
							<th>{__('Name', 'buddyboss')}</th>
							<th>{__('Status', 'buddyboss')}</th>
							<th>{__('Members', 'buddyboss')}</th>
							<th>{__('Created', 'buddyboss')}</th>
							<th>{__('Actions', 'buddyboss')}</th>
						</tr>
					</thead>
					<tbody>
						{groups.map((group) => (
							<tr key={group.id}>
								<td>{group.id}</td>
								<td>
									<div className="bb-admin-groups-list__group">
										<img src={group.avatar} alt="" className="bb-admin-groups-list__avatar" />
										<strong>{group.name}</strong>
									</div>
								</td>
								<td>{group.status}</td>
								<td>{group.member_count}</td>
								<td>{group.date_created_formatted}</td>
								<td>
									<Button
										variant="link"
										onClick={() => {
											window.location.hash = `#/groups/${group.id}/edit`;
										}}
									>
										{__('Edit', 'buddyboss')}
									</Button>
									<Button
										variant="link"
										isDestructive
										onClick={() => handleDelete(group.id)}
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
				<div className="bb-admin-groups-list__pagination">
					<Button
						variant="secondary"
						disabled={page === 1}
						onClick={() => setPage(page - 1)}
					>
						{__('Previous', 'buddyboss')}
					</Button>
					<span className="bb-admin-groups-list__page-info">
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
