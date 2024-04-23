<?php
/**
 * The template for displaying schedule activity post form.
 *
 * This template can be overridden by copying it to yourtheme/buddypress/common/js-templates/activity/parts/bb-activity-schedule-post.php.
 *
 * @since   BuddyBoss [BBVERSION]
 * @version 1.0.0
 */

?>
<script type="text/html" id="tmpl-activity-schedule-post">
	<?php
	if ( bp_is_active( 'activity' ) && bb_is_enabled_activity_schedule_posts() && bb_can_user_schedule_activity() ) :
		?>
		<# if ( false === data.edit_activity || 'scheduled' === data.activity_action_type ) { #>
		<div class="bb-schedule-post_dropdown_section">
			<a href="#" class="bb-schedule-post_dropdown_button {{data.activity_action_type === 'scheduled' ? 'is_scheduled' : ''}}">
				<i class="bb-icon-f bb-icon-clock"></i>
				<i class="bb-icon-f bb-icon-caret-down"></i>
			</a>
			<div class="bb-schedule-post_dropdown_list">
				<ul>
					<li>
						<a href="#" class="bb-schedule-post_action"><i class="bb-icon-l bb-icon-calendar"></i><?php echo esc_html__( 'Schedule Post', 'buddyboss' ); ?>
						</a>
					</li>
					<li>
						<a href="#" id="bb-view-schedule-posts" class="bb-view-schedule-posts"><i class="bb-icon-l bb-icon-pencil"></i><?php echo esc_html__( 'View Schedule Posts', 'buddyboss' ); ?>
						</a>
					</li>
				</ul>
			</div>

			<div class="bb-schedule-post_modal">
				<div class="bb-action-popup" id="bb-schedule-post_form_modal" style="display: none">
					<transition name="modal">
						<div class="modal-mask bb-white bbm-model-wrap">
							<div class="modal-wrapper">
								<div class="modal-container">
									<header class="bb-model-header">
										<h4>
											<span class="target_name"><?php echo esc_html__( 'Schedule post', 'buddyboss' ); ?></span>
										</h4>
										<a class="bb-close-action-popup bb-model-close-button" href="#">
											<span class="bb-icon-l bb-icon-times"></span>
										</a>
									</header>
									<div class="bb-action-popup-content">
										<?php
										$formatted_date = wp_date( get_option( 'date_format' ) );
										$formatted_time = wp_date( get_option( 'time_format' ) );
										?>
										<p class="schedule-date"><?php echo esc_html( $formatted_date ); ?> <?php echo esc_html__( 'at', 'buddyboss' ); ?>
											<span class="bb-server-time"><?php echo esc_html( $formatted_time ); ?></span>
										</p>

										<label><?php echo esc_html__( 'Date', 'buddyboss' ); ?></label>
										<div class="input-field">
											<input type="text" name="bb-schedule-activity-date-field" class="bb-schedule-activity-date-field" placeholder="yy/mm/dd" value="{{data.activity_schedule_date_raw ? data.activity_schedule_date_raw : ''}}">
											<i class="bb-icon-f bb-icon-calendar"></i>
										</div>

										<label><?php echo esc_html__( 'Time', 'buddyboss' ); ?></label>
										<div class="input-field-inline">
											<div class="input-field bb-schedule-activity-time-wrap">
												<input type="text" name="bb-schedule-activity-time-field" class="bb-schedule-activity-time-field" placeholder="hh/mm" value="{{data.activity_schedule_time ? data.activity_schedule_time : ''}}">
												<i class="bb-icon-f bb-icon-clock"></i>
											</div>
											<div class="input-field bb-schedule-activity-meridian-wrap">
												<label for="bb-schedule-activity-meridian-am">
													<input type="radio" value="am" id="bb-schedule-activity-meridian-am" name="bb-schedule-activity-meridian">
													<span class="bb-time-meridian"><?php echo esc_html__( 'AM', 'buddyboss' ); ?></span>
												</label>
												<label for="bb-schedule-activity-meridian-pm">
													<input type="radio" value="pm" id="bb-schedule-activity-meridian-pm" name="bb-schedule-activity-meridian" checked="checked">
													<span class="bb-time-meridian"><?php echo esc_html__( 'PM', 'buddyboss' ); ?></span>
												</label>
											</div>
										</div>

										<p>
											<a href="#" class="bb-view-all-scheduled-posts"><?php echo esc_html__( 'View all scheduled posts', 'buddyboss' ); ?>
												<i class="bb-icon-f bb-icon-arrow-right"></i>
											</a>
										</p>
									</div>

									<footer class="bb-model-footer">
										<a href="#" class="button button-outline bb-schedule-activity-cancel"><?php echo esc_html__( 'Back', 'buddyboss' ); ?></a>
										<a class="button bb-schedule-activity" href="#" disabled><?php echo esc_html__( 'Next', 'buddyboss' ); ?></a>
									</footer>
								</div>
							</div>
						</div>
					</transition>
				</div> <!-- .bb-action-popup -->
			</div>

			<div class="bb-schedule-posts_modal">
				<div class="bb-action-popup" id="bb-schedule-posts_modal" style="display: none">
					<transition name="modal">
						<div class="modal-mask bb-white bbm-model-wrap">
							<div class="modal-wrapper">
								<div class="modal-container">
									<header class="bb-model-header">
										<h4>
											<span class="target_name"><?php echo esc_html__( 'Scheduled posts', 'buddyboss' ); ?></span>
										</h4>
										<a class="bb-close-action-popup bb-model-close-button" href="#">
											<span class="bb-icon-l bb-icon-times"></span>
										</a>
									</header>
									<div class="bb-action-popup-content">
										<div class="schedule-posts-placeholder_loader">
											<i class="bb-icon-f bb-icon-spinner animate-spin"></i>
										</div>
										<div class="schedule-posts-placeholder">
											<i class="bb-icon-f bb-icon-activity-slash"></i>
											<h2><?php echo esc_html__( 'No Scheduled Posts Found', 'buddyboss' ); ?></h2>
											<p><?php echo esc_html__( 'You do not have any posts scheduled at the moment.', 'buddyboss' ); ?></p>
										</div>
										<div class="schedule-posts-content"></div>
									</div>
								</div>
							</div>
						</div>
					</transition>
				</div> <!-- .bb-action-popup -->
			</div>
		</div>
		<# } #>
	<?php endif; ?>
</script>