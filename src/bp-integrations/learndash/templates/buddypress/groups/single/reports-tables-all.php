<div class="ld-report-no-data">
	<aside class="bp-feedback bp-template-notice info">
		<span class="bp-icon" aria-hidden="true"></span>
		<p><?php _e( 'Sorry, no report data was found.', 'buddyboss' ); ?></p>
	</aside>
</div>

<?php if ( groups_is_user_mod( bp_loggedin_user_id(), groups_get_current_group()->id ) || groups_is_user_admin( bp_loggedin_user_id(), groups_get_current_group()->id ) || bp_current_user_can( 'bp_moderate' ) ) { ?>
<div class="ld-report-user-stats">
	<div class="user-info">
		<div class="user-avatar">
			<img src="<?php echo esc_url( buddypress()->plugin_url . 'bp-core/images/mystery-group.png' ); ?>" class="avatar avatar-300 photo" width="300" height="300" alt="">
		</div>
		<div class="user-name">
			<h5 class="list-title member-name"><?php echo __( 'All Students', 'buddyboss' ); ?></h5>
		</div>
	</div>
</div>
<?php } ?>

<div class="bp_ld_report_table_wrapper">
	<?php
	$group_id  = bp_ld_sync( 'buddypress' )->helpers->getLearndashGroupId( groups_get_current_group()->id );
	$courseIds = learndash_group_enrolled_courses( $group_id );

	/**
	 * Filter to update course lists
	 */
	$courses = array_map( 'get_post', apply_filters( 'bp_ld_learndash_group_enrolled_courses', $courseIds, $group_id ) );
	if ( groups_is_user_mod( bp_loggedin_user_id(), groups_get_current_group()->id ) || groups_is_user_admin( bp_loggedin_user_id(), groups_get_current_group()->id ) || bp_current_user_can( 'bp_moderate' ) ) {
		if ( isset( $_GET ) && isset( $_GET['course'] ) && '' !==  $_GET['course'] ) {
			$courses = array( get_post( $_GET['course'] ) );
		}
		foreach ( $courses as $course ) {
			//learndash_get_user_course_attempts_time_spent()
			$course_users = learndash_get_groups_user_ids( $group_id );

			?>
			<h2><?php echo $course->post_title; ?></h2>
			<table id="admin-show-all" class="admin-show-all display" style="width:100%">
				<thead>
				<tr>
					<th><?php echo __( 'Student', 'buddyboss' ); ?></th>
					<th><?php echo __( 'Progress', 'buddyboss' ); ?></th>
					<th><?php echo __( 'Start  Date', 'buddyboss' ); ?></th>
					<th><?php echo __( 'Completion Date', 'buddyboss' ); ?></th>
					<th><?php echo __( 'Time Spent', 'buddyboss' ); ?></th>
					<th><?php echo __( 'Points Earned', 'buddyboss' ); ?></th>
				</tr>
				</thead>
				<tbody>
				<?php
				foreach ( $course_users as $user ) {
					//learndash_user_get_course_completed_date();
					//$user = $user;

					$progress = learndash_course_progress( array(
						'user_id'   => $user,
						'course_id' => $course->ID,
						'array'     => true
					) );

					$course_activity_args = array(
						'course_id'        => $course->ID,
						'user_id'          => $user,
						'post_id'          => $course->ID,
						'activity_type'    => 'course',
					);

					$course_activity = learndash_get_user_activity( $course_activity_args );

					if ( ( ! empty( $course_activity ) ) && ( is_object( $course_activity ) ) ) {
						if ( ( property_exists( $course_activity, 'activity_started' ) ) && ( ! empty( $course_activity->activity_started ) ) ) {
							$start_date = date_i18n( bp_get_option( 'date_format' ), intval( $course_activity->activity_started ) );
						}
					} else {
						$start_date        = '-';
					}

					$completed_date    = learndash_user_get_course_completed_date( $user, $course->ID )? date_i18n( bp_get_option( 'date_format' ), learndash_user_get_course_completed_date( $user, $course->ID ) ) : '-';

					$time_spent = '';
					if ( ( ! empty( $course_activity ) ) && ( is_object( $course_activity ) ) ) {
						if ( ( property_exists( $course_activity, 'activity_completed' ) ) && ( ! empty( $course_activity->activity_completed ) ) && ( property_exists( $course_activity, 'activity_started' ) ) && ( ! empty( $course_activity->activity_started ) ) ) {
							//  activity_completed - activity_started
							$complete = (int) $course_activity->activity_completed;
							$started = (int) $course_activity->activity_started;
							$time_spent = $complete - $started;
							if ( $time_spent > 0 ) {
								$time_spent = bp_ld_time_spent( $time_spent );
							} else {
								$time_spent        = '-';
							}
						}
					} else {
						$time_spent        = '-';
					}
					?>
					<tr>
						<td> <span><?php echo get_avatar( $user, 35 ); ?></span><span><a href="<?php echo bp_get_group_permalink() . 'reports/?user=' . $user .'&course=&step=all'; ?>"><?php echo bp_core_get_user_displayname( $user ); ?></a></span></td>
						<td><?php printf( esc_html_x( '%s%% Complete', 'Percentage of course complete', 'buddyboss' ), $progress['percentage'] ); ?></td>
						<td><?php echo $start_date; ?></td>
						<td><?php echo $completed_date; ?></td>
						<td><?php echo $time_spent; ?></td>
						<td><?php echo ''; ?></td>
					</tr>
					<?php
				}
				?>
				</tbody>

			</table> <?php
		}
	}
	?>
</div>


