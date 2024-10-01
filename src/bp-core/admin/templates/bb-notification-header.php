<?php
/**
 * BuddyBoss Notification System Header.
 *
 * Header notifies customers about major releases, significant changes, or special offers.
 *
 * @since   BuddyBoss [BBVERSION]
 * @package BuddyBoss
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

?>
<div class="bb-notice-header-wrapper">

	<div class="bb-admin-header">
		<div class="bb-admin-header__logo">
			<img alt="" class="gravatar" src="<?php echo buddypress()->plugin_url; ?>bp-core/images/admin/bb-logo.png" />
		</div>
		<div class="bb-admin-header__nav">
			<div class="bb-admin-nav">
				<div class="bb-notifications-wrapepr">
					<a href="" class="bb-admin-nav__button bb-admin-nav__notice" id="bb-notifications-button">
						<i class="bb-icon-l bb-icon-bell"></i>
						<span class="bb-notice-count">123</span>
					</a>
					<div class="bb-notifications-panel">
						<div class="bb-panel-header">
							<h4>Notifications</h4>
							<span class="close-panel-header"><i class="bb-icon-l bb-icon-times"></i></span>
						</div>
						<div class="bb-panel-nav">
							<div class="nav-list-container">
								<ul class="panel-nav-list">
									<li>
										<a href="#" id="show-all" class="switch-notices active" data-status="all">All</a>
									</li>
									<li><a href="#" id="show-dismissed" class="switch-notices" data-status="dismissed">Read</a>
									</li>
									<li><a href="#" id="show-active" class="switch-notices" data-status="unread">Unread
											<span class="count-active">(2)</span></a></li>
								</ul>
							</div>
							<div class="panel-nav-check">
								<a href="#" class="panel-nav-dismiss-all">Mark all read</a>
							</div>
						</div>
						<div class="bb-panel-body">
							<div class="bb-notices-blocks-container">

								<div class="bb-notice-block bb-notice-block--alert dismissed">
									<div class="bb-notice-icon"><span class="notice-icon"></span></div>
									<div class="bb-notice-card">
										<div class="bb-notice-header">
											<div class="notice-header">
												<h5>Unread notification title (Alert)</h5>
												<div class="notice-timestamp">Today at 9:00 AM</div>
											</div>
											<div class="bb-dismiss-notice"><i class="bb-icon-l bb-icon-times"></i></div>
										</div>
										<div class="bb-notice-body">
											<div class="notice-content">
												<p>You must create access groups to apply restrictions to roles and view
													them listed here.</p>
											</div>
											<div class="notice-action">
												<a href="" class="button button-primary bb-notice-action-button">Activate
													License Now</a>
											</div>
										</div>
									</div>
								</div>

								<div class="bb-notice-block bb-notice-block--info unread">
									<div class="bb-notice-icon"><span class="notice-icon"></span></div>
									<div class="bb-notice-card">
										<div class="bb-notice-header">
											<div class="notice-header">
												<h5>Unread notification title (New Feature)</h5>
												<div class="notice-timestamp">Yesterday at 4:00 PM</div>
											</div>
											<div class="bb-dismiss-notice"><i class="bb-icon-l bb-icon-times"></i></div>
										</div>
										<div class="bb-notice-body">
											<div class="notice-content">
												<p>You must create access groups to apply restrictions to roles and view
													them listed here.</p>
											</div>
											<div class="notice-action">
												<a href="" class="button button-primary bb-notice-action-button">Learn
													More</a>
											</div>
										</div>
									</div>
								</div>

								<div class="bb-notice-block bb-notice-block--warning unread">
									<div class="bb-notice-icon"><span class="notice-icon"></span></div>
									<div class="bb-notice-card">
										<div class="bb-notice-header">
											<div class="notice-header">
												<h5>Read notification title (Status Alert)</h5>
												<div class="notice-timestamp">Yesterday at 10:00 AM</div>
											</div>
											<div class="bb-dismiss-notice"><i class="bb-icon-l bb-icon-times"></i></div>
										</div>
										<div class="bb-notice-body">
											<div class="notice-content">
												<p>You must create access groups to apply restrictions to roles and view
													them listed here.</p>
												<ul>
													<li>You must create access groups to apply restrictions to roles
													</li>
													<li>View them listed here</li>
												</ul>
											</div>
											<div class="notice-action">
												<a href="" class="button button-primary bb-notice-action-button">Learn
													More</a>
											</div>
										</div>
									</div>
								</div>

								<div class="bb-notice-block bb-notice-block--promo unread">
									<div class="bb-notice-icon"><span class="notice-icon"></span></div>
									<div class="bb-notice-card">
										<div class="bb-notice-header">
											<div class="notice-header">
												<h5>Read notification title (Promo)</h5>
												<div class="notice-timestamp">Yesterday at 10:00 AM</div>
											</div>
											<div class="bb-dismiss-notice"><i class="bb-icon-l bb-icon-times"></i></div>
										</div>
										<div class="bb-notice-body">
											<div class="notice-content">
												<p>You must create access groups to apply restrictions to roles and view
													them listed here.</p>
											</div>
											<div class="notice-action">
												<a href="" class="button button-primary bb-notice-action-button">Grab
													the deal</a>
											</div>
										</div>
									</div>
								</div>

							</div>
							<div class="bb-notices-blocks-blank">
								<p>There are no messages.</p>
							</div>
						</div>
					</div>
				</div>
				<a href="" class="bb-admin-nav__button bb-admin-nav__help"><i class="bb-icon-l bb-icon-question"></i></a>
			</div>
		</div>
	</div>

</div>
