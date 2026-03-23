/**
 * BuddyBoss Admin Settings 2.0 - Dashboard Screen
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss 3.0.0
 */

import { useState, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Button, Card, CardBody, CardHeader, Spinner } from '@wordpress/components';
import apiFetch from '@wordpress/api-fetch';

/**
 * Dashboard Screen Component
 *
 * @returns {JSX.Element} Dashboard screen
 */
export function DashboardScreen() {
	const [installs, setInstalls] = useState(null);
	const [analytics, setAnalytics] = useState(null);
	const [scheduledPosts, setScheduledPosts] = useState([]);
	const [recommendations, setRecommendations] = useState([]);
	const [isLoading, setIsLoading] = useState(true);

	useEffect(() => {
		// Load dashboard data
		Promise.all([
			apiFetch({ path: '/buddyboss/v1/dashboard/installs' }).catch(() => null),
			apiFetch({ path: '/buddyboss/v1/dashboard/analytics' }).catch(() => null),
			apiFetch({ path: '/buddyboss/v1/dashboard/scheduled-posts?per_page=5' }).catch(() => []),
			apiFetch({ path: '/buddyboss/v1/dashboard/recommendations' }).catch(() => ({ plugins: [], integrations: [] })),
		])
			.then(([installsData, analyticsData, scheduledData, recommendationsData]) => {
				if (installsData) {
					setInstalls(installsData.data);
				}
				if (analyticsData) {
					setAnalytics(analyticsData.data);
				}
				if (scheduledData) {
					setScheduledPosts(scheduledData.data || []);
				}
				if (recommendationsData) {
					setRecommendations(recommendationsData.data || { plugins: [], integrations: [] });
				}
				setIsLoading(false);
			})
			.catch(() => {
				setIsLoading(false);
			});
	}, []);

	if (isLoading) {
		return (
			<div className="bb-admin-dashboard bb-admin-loading">
				<Spinner />
			</div>
		);
	}

	return (
		<div className="bb-admin-dashboard">
			<div className="bb-admin-dashboard__container">
				{/* Left Column */}
				<div className="bb-admin-dashboard__left">
					{/* Welcome Panel */}
					<Card className="bb-admin-dashboard__welcome">
						<CardHeader>
							<h2>{__('Welcome to BuddyBoss', 'buddyboss')}</h2>
						</CardHeader>
						<CardBody>
							<div className="bb-admin-dashboard__video">
								{/* YouTube embed placeholder */}
								<iframe
									width="100%"
									height="315"
									src="https://www.youtube.com/embed/dQw4w9WgXcQ"
									title={__('Getting Started with BuddyBoss', 'buddyboss')}
									frameBorder="0"
									allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
									allowFullScreen
								></iframe>
							</div>
							<div className="bb-admin-dashboard__quick-actions">
								<Button variant="primary" href="#/groups/create">
									{__('Create Group', 'buddyboss')}
								</Button>
								<Button variant="secondary" href="#/settings">
									{__('Configure Settings', 'buddyboss')}
								</Button>
							</div>
						</CardBody>
					</Card>

					{/* BuddyBoss Installs */}
					{installs && (
						<Card className="bb-admin-dashboard__installs">
							<CardHeader>
								<h3>{__('BuddyBoss Installs', 'buddyboss')}</h3>
							</CardHeader>
							<CardBody>
								<div className="bb-admin-dashboard__install-item">
									<strong>{__('Platform', 'buddyboss')}:</strong>{' '}
									<span>{installs.platform?.version || 'N/A'}</span>
									{installs.platform?.update_available && (
										<span className="bb-admin-dashboard__update-badge">
											{__('Update Available', 'buddyboss')}
										</span>
									)}
								</div>
								{installs.pro && (
									<div className="bb-admin-dashboard__install-item">
										<strong>{__('Platform Pro', 'buddyboss')}:</strong>{' '}
										<span>{installs.pro.version || 'N/A'}</span>
										{installs.pro.update_available && (
											<span className="bb-admin-dashboard__update-badge">
												{__('Update Available', 'buddyboss')}
											</span>
										)}
									</div>
								)}
							</CardBody>
						</Card>
					)}

					{/* Community Analytics */}
					{analytics && (
						<Card className="bb-admin-dashboard__analytics">
							<CardHeader>
								<h3>{__('Community Analytics', 'buddyboss')}</h3>
							</CardHeader>
							<CardBody>
								<div className="bb-admin-dashboard__analytics-grid">
									<div className="bb-admin-dashboard__analytics-item">
										<div className="bb-admin-dashboard__analytics-label">
											{__('Total Users', 'buddyboss')}
										</div>
										<div className="bb-admin-dashboard__analytics-value">
											{analytics.total_users || 0}
										</div>
									</div>
									<div className="bb-admin-dashboard__analytics-item">
										<div className="bb-admin-dashboard__analytics-label">
											{__('Active Users', 'buddyboss')}
										</div>
										<div className="bb-admin-dashboard__analytics-value">
											{analytics.active_users || 0}
										</div>
									</div>
									<div className="bb-admin-dashboard__analytics-item">
										<div className="bb-admin-dashboard__analytics-label">
											{__('New This Month', 'buddyboss')}
										</div>
										<div className="bb-admin-dashboard__analytics-value">
											{analytics.new_users_this_month || 0}
										</div>
									</div>
								</div>
							</CardBody>
						</Card>
					)}

					{/* Scheduled Posts */}
					{scheduledPosts.length > 0 && (
						<Card className="bb-admin-dashboard__scheduled-posts">
							<CardHeader>
								<h3>{__('Scheduled Posts', 'buddyboss')}</h3>
							</CardHeader>
							<CardBody>
								<ul className="bb-admin-dashboard__scheduled-list">
									{scheduledPosts.map((post) => (
										<li key={post.id}>
											<a href={`#/activity/all?id=${post.id}`}>{post.title}</a>
											<span className="bb-admin-dashboard__scheduled-date">
												{post.scheduled_date}
											</span>
										</li>
									))}
								</ul>
							</CardBody>
						</Card>
					)}

					{/* Recommendations */}
					{recommendations.plugins && recommendations.plugins.length > 0 && (
						<Card className="bb-admin-dashboard__recommendations">
							<CardHeader>
								<h3>{__('Optimize your Community further', 'buddyboss')}</h3>
							</CardHeader>
							<CardBody>
								<div className="bb-admin-dashboard__recommendations-list">
									{recommendations.plugins.map((plugin) => (
										<div key={plugin.id} className="bb-admin-dashboard__recommendation-item">
											<h4>{plugin.name}</h4>
											<p>{plugin.description}</p>
											<Button variant="secondary" href={plugin.url} target="_blank">
												{__('Learn More', 'buddyboss')}
											</Button>
										</div>
									))}
								</div>
							</CardBody>
						</Card>
					)}
				</div>

				{/* Right Column */}
				<div className="bb-admin-dashboard__right">
					{/* Setup Guide */}
					<Card className="bb-admin-dashboard__setup-guide">
						<CardHeader>
							<h3>{__('Setup Guide', 'buddyboss')}</h3>
						</CardHeader>
						<CardBody>
							<ul className="bb-admin-dashboard__setup-list">
								<li>
									<a href="#/settings/activity">{__('Configure Activity Settings', 'buddyboss')}</a>
								</li>
								<li>
									<a href="#/settings/groups">{__('Configure Groups Settings', 'buddyboss')}</a>
								</li>
								<li>
									<a href="#/settings">{__('Review All Settings', 'buddyboss')}</a>
								</li>
							</ul>
						</CardBody>
					</Card>

					{/* Quick Links */}
					<Card className="bb-admin-dashboard__quick-links">
						<CardHeader>
							<h3>{__('Quick Links', 'buddyboss')}</h3>
						</CardHeader>
						<CardBody>
							<ul className="bb-admin-dashboard__links-list">
								<li>
									<a href="#/activity/all">{__('All Activity', 'buddyboss')}</a>
								</li>
								<li>
									<a href="#/groups/all">{__('All Groups', 'buddyboss')}</a>
								</li>
								<li>
									<a href="https://www.buddyboss.com/resources/docs/" target="_blank" rel="noopener noreferrer">
										{__('Documentation', 'buddyboss')}
									</a>
								</li>
							</ul>
						</CardBody>
					</Card>
				</div>
			</div>
		</div>
	);
}
