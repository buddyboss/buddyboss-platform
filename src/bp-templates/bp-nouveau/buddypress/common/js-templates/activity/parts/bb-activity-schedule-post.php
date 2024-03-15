<?php
/**
 * The template for displaying activity post form buttons
 *
 * This template can be overridden by copying it to yourtheme/buddypress/common/js-templates/activity/parts/bb-activity-schedule-post.php.
 *
 * @since   1.0.0
 * @version 1.0.0
 */

?>
<script type="text/html" id="tmpl-activity-schedule-post">
<?php
	if ( bp_is_active( 'activity' ) && bb_is_enabled_activity_schedule_posts() ) :
		?>
	<div class="bb-schedule-post_dropdown_section">
		<a href="#" class="bb-schedule-post_dropdown_button">
			<i class="bb-icon-f bb-icon-clock"></i>
			<i class="bb-icon-f bb-icon-caret-down"></i>
		</a>
		<div class="bb-schedule-post_dropdown_list">
			<ul>
				<li><a href="#" class="bb-schedule-post_action"><i class="bb-icon-l bb-icon-calendar"></i><?php echo esc_html__( 'Schedule Post', 'buddyboss' ); ?></a></li>
				<li><a href="#" class="bb-view-schedule-posts"><i class="bb-icon-l bb-icon-pencil"></i><?php echo esc_html__( 'View Schedule Posts', 'buddyboss' ); ?></a></li>
			</ul>
		</div>

		<div class="bb-schedule-post_modal">
			<div class="bb-action-popup" id="bb-schedule-post_form_modal" style="display: none">
				<transition name="modal">
					<div class="modal-mask bb-white bbm-model-wrap">
						<div class="modal-wrapper">
							<div class="modal-container">
								<header class="bb-model-header">
									<h4><span class="target_name"><?php echo esc_html__( 'Schedule post', 'buddyboss' ); ?></span></h4>
									<a class="bb-close-action-popup bb-model-close-button" href="#">
										<span class="bb-icon-l bb-icon-times"></span>
									</a>
								</header>
								<div class="bb-action-popup-content">
									<p class="schedule-date">July 12, 2023 at 3:27 pm</p>

									<label>Date</label>
									<div class="input-field">
										<input type="text" name="" id="" class="bb-schedule-activity-date-field">
										<i class="bb-icon-f bb-icon-calendar"></i>
									</div>

									<label>Time</label>
									<div class="input-field-inline">
										<div class="input-field bb-schedule-activity-time-wrap">
											<input type="text" name="" id="" class="bb-schedule-activity-time-field">
											<i class="bb-icon-f bb-icon-clock"></i>
										</div>
										<div class="input-field bb-schedule-activity-meridian-wrap">
											<label for="bb-schedule-activity-meridian-am">
												<input type="radio" value="am" id="bb-schedule-activity-meridian-am" name="bb-schedule-activity-meridian">
												<span class="bb-time-meridian">AM</span>
											</label>
											<label for="bb-schedule-activity-meridian-pm">
												<input type="radio" value="pm" id="bb-schedule-activity-meridian-pm" name="bb-schedule-activity-meridian" checked="checked">
												<span class="bb-time-meridian">PM</span>
											</label>
										</div>
									</div>

									<p><a href="#">View all scheduled posts <i class="bb-icon-f bb-icon-arrow-right"></i></a></p>
								</div>

								<footer class="bb-model-footer">
									<a href="#" class="button button-outline bb-schedule-activity-cancel">Back</a>
									<a class="button bb-schedule-activity" href="#">Next</a>
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
									<h4><span class="target_name"><?php echo esc_html__( 'Scheduled posts', 'buddyboss' ); ?></span></h4>
									<a class="bb-close-action-popup bb-model-close-button" href="#">
										<span class="bb-icon-l bb-icon-times"></span>
									</a>
								</header>
								<div class="bb-action-popup-content">
									<div class="schedule-posts-placeholder">
										<i class="bb-icon-f bb-icon-activity-slash"></i>
										<h2>No Scheduled Posts Found</h2>
										<p>You do not have any posts scheduled at the moment.</p>
									</div>
								</div>
							</div>
						</div>
					</div>
				</transition>
			</div> <!-- .bb-action-popup -->
		</div>
	</div>
	<?php endif; ?>
</script>
