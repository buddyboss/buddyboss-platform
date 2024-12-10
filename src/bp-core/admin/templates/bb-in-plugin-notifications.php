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

// Combine both active and dismissed notifications with a marker.
$all_notifications = array();

$active_notifications = false;
if ( ! empty( $notifications['active'] ) ) {
	$active_notifications = true;
	foreach ( $notifications['active'] as $active_notification ) {
		$all_notifications[] = array_merge( $active_notification, array( 'status' => 'active' ) );
	}
}

$dismissed_notifications = false;
if ( ! empty( $notifications['dismissed'] ) ) {
	$dismissed_notifications = true;
	foreach ( $notifications['dismissed'] as $dismissed_notification ) {
		$all_notifications[] = array_merge( $dismissed_notification, array( 'status' => 'dismissed' ) );
	}
}

$total_notifications           = ! empty( $all_notifications ) ? count( $all_notifications ) : 0;
$total_active_notifications    = $active_notifications ? count( $notifications['active'] ) : 0;
$total_dismissed_notifications = $dismissed_notifications ? count( $notifications['dismissed'] ) : 0;
?>
<div class="bb-notice-header-wrapper">
	<div class="bb-admin-header">
		<div class="bb-admin-header__logo">
			<img alt="" class="gravatar" src="<?php echo esc_url( buddypress()->plugin_url . 'bp-core/images/admin/bb-logo.png' ); ?>" />
		</div>
		<div class="bb-admin-header__nav">
			<div class="bb-admin-nav">
				<div class="bb-notifications-wrapepr">
					<?php
					do_action( 'bb_admin_header_actions' );
					?>
					<a href="javascript:void(0);" class="bb-admin-nav__button bb-admin-nav__notice" id="bb-notifications-button">
						<i class="bb-icon-l bb-icon-bell"></i>
						<?php
						if ( $total_active_notifications > 0 ) {
							?>
							<span class="bb-notice-count">
								<?php echo esc_html( $total_active_notifications ); ?>
							</span>
							<?php
						}
						?>
					</a>
					<div class="bb-notifications-panel">
						<div class="bb-panel-header">
							<h4><?php esc_html_e( 'Notifications', 'buddyboss' ); ?></h4>
							<span class="close-panel-header"><i class="bb-icon-l bb-icon-times"></i></span>
						</div>
						<div class="bb-panel-nav">
							<div class="nav-list-container">
								<ul class="panel-nav-list">
									<li>
										<a href="#" id="show-all" class="switch-notices active" data-status="all">
											<?php esc_html_e( 'All', 'buddyboss' ); ?>
										</a>
									</li>
									<li>
										<a href="#" id="show-dismissed" class="switch-notices" data-status="dismissed">
											<?php esc_html_e( 'Read', 'buddyboss' ); ?>
										</a>
									</li>
									<li>
										<a href="#" id="show-active" class="switch-notices" data-status="unread">
											<?php esc_html_e( 'Unread', 'buddyboss' ); ?>
											<span class="count-active">
												<?php echo $total_active_notifications > 0 ? esc_html( '(' . $total_active_notifications . ')' ) : ''; ?>
											</span>
										</a>
									</li>
								</ul>
							</div>
							<?php
							if ( $active_notifications ) {
								?>
								<div class="panel-nav-check">
									<a href="#" class="panel-nav-dismiss-all"><?php esc_html_e( 'Mark all read', 'buddyboss' ); ?></a>
								</div>
								<?php
							}
							?>
						</div>
						<div class="bb-panel-body">
							<div class="bb-notices-blocks-container">
								<?php
								// Check if we have any notifications to display.
								if ( ! empty( $all_notifications ) ) {
									foreach ( $all_notifications as $notification ) {
										$notification_title   = ! empty( $notification['title'] ) ? sanitize_text_field( $notification['title'] ) : '';
										$notification_content = ! empty( $notification['content'] ) ? apply_filters( 'the_content', $notification['content'] ) : '';

										// Time difference logic.
										$time_diff        = ceil( ( time() - $notification['saved'] ) );
										$time_diff_string = '';
										if ( $time_diff < MINUTE_IN_SECONDS ) {
											$time_diff_string = sprintf(
											/* translators: %s: number of seconds */
												_n( '%s second ago', '%s seconds ago', $time_diff, 'buddyboss' ),
												$time_diff
											);
										} elseif ( $time_diff < HOUR_IN_SECONDS ) {
											$time_diff_string = sprintf(
											/* translators: %s: number of minutes */
												_n( '%s minute ago', '%s minutes ago', ceil( ( $time_diff / MINUTE_IN_SECONDS ) ), 'buddyboss' ),
												ceil( ( $time_diff / MINUTE_IN_SECONDS ) )
											);
										} elseif ( $time_diff < DAY_IN_SECONDS ) {
											$time_diff_string = sprintf(
											/* translators: %s: number of hours */
												_n( '%s hour ago', '%s hours ago', ceil( ( $time_diff / HOUR_IN_SECONDS ) ), 'buddyboss' ),
												ceil( ( $time_diff / HOUR_IN_SECONDS ) )
											);
										} elseif ( $time_diff < WEEK_IN_SECONDS ) {
											$time_diff_string = sprintf(
											/* translators: %s: number of days */
												_n( '%s day ago', '%s days ago', ceil( ( $time_diff / DAY_IN_SECONDS ) ), 'buddyboss' ),
												ceil( ( $time_diff / DAY_IN_SECONDS ) )
											);
										} elseif ( $time_diff < MONTH_IN_SECONDS ) {
											$time_diff_string = sprintf(
											/* translators: %s: number of weeks */
												_n( '%s week ago', '%s weeks ago', ceil( ( $time_diff / WEEK_IN_SECONDS ) ), 'buddyboss' ),
												ceil( ( $time_diff / WEEK_IN_SECONDS ) )
											);
										} elseif ( $time_diff < YEAR_IN_SECONDS ) {
											$time_diff_string = sprintf(
											/* translators: %s: number of months */
												_n( '%s month ago', '%s months ago', ceil( ( $time_diff / MONTH_IN_SECONDS ) ), 'buddyboss' ),
												ceil( ( $time_diff / MONTH_IN_SECONDS ) )
											);
										} else {
											$time_diff_string = sprintf(
											/* translators: %s: number of years */
												_n( '%s year ago', '%s years ago', ceil( ( $time_diff / YEAR_IN_SECONDS ) ), 'buddyboss' ),
												ceil( ( $time_diff / YEAR_IN_SECONDS ) )
											);
										}

										// Determine the class based on notification status.
										$notification_class = ( 'dismissed' === $notification['status'] ) ? 'dismissed' : 'unread';
										$icon_color         = ! empty( $notification['icon_color'] ) ? $notification['icon_color'] : '';
										?>
										<div id="bb-notifications-message-<?php echo esc_attr( $notification['id'] ); ?>" class="bb-notice-block bb-notice-block--<?php echo esc_attr( $notification['type'] ); ?> <?php echo esc_attr( $notification_class ); ?>" data-message-id="<?php echo esc_attr( $notification['id'] ); ?>">
											<div class="bb-notice-icon">
												<?php
												if ( ! empty( $notification['icon'] ) ) {
													?>
													<span class="notice-icon" style='background-color: <?php echo esc_attr( $icon_color ); ?>;'>
														<i class='<?php echo esc_attr( $notification['icon'] ); ?>' aria-hidden="true"></i>
													</span>
													<?php
												}
												?>
											</div>
											<div class="bb-notice-card">
												<div class="bb-notice-header">
													<div class="notice-header">
														<h5><?php echo esc_html( $notification_title ); ?></h5>
														<div class="notice-timestamp"><?php echo esc_html( $time_diff_string ); ?></div>
													</div>
													<?php
													if ( 'dismissed' !== $notification['status'] ) {
														?>
														<div class="bb-dismiss-notice">
															<i class="bb-icon-l bb-icon-times"></i>
														</div>
														<?php
													}
													?>
												</div>
												<div class="bb-notice-body">
													<div class="notice-content">
														<p><?php echo wp_kses_post( $notification_content ); ?></p>
													</div>
													<div class="notice-action">
														<?php
														if ( ! empty( $notification['buttons'] ) && is_array( $notification['buttons'] ) ) {
															foreach ( $notification['buttons'] as $button ) {
																if ( empty( $button['url'] ) || empty( $button['text'] ) ) {
																	continue;
																}
																$primary = isset( $button['type']['value'] ) ? $button['type']['value'] : 'primary';
																$label   = $button['text'] ?? '';
																?>
																<a href="<?php echo esc_url( $button['url'] ); ?>"
																class="button button-<?php echo esc_attr( $primary ); ?>">
																	<?php echo esc_html( $label ); ?>
																</a>
																<?php
															}
														}
														?>
													</div>
												</div>
											</div>
										</div>
										<?php
									}
								}
								?>
							</div>
							<div class="bb-notices-blocks-blank">
								<p><?php esc_html_e( 'There are no messages.', 'buddyboss' ); ?></p>
							</div>
						</div>
					</div>
				</div>
				<a href="<?php echo esc_url( bp_get_admin_url(
					add_query_arg(
						array(
							'page'    => 'bp-help',
						),
						'admin.php'
					)
				) ); ?>" class="bb-admin-nav__button bb-admin-nav__help"><i class="bb-icon-l bb-icon-question"></i></a>
			</div>
		</div>
	</div>

</div>
