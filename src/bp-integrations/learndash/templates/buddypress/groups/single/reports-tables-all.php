<div class="ld-report-no-data">
	<aside class="bp-feedback bp-template-notice info">
		<span class="bp-icon" aria-hidden="true"></span>
		<p><?php _e( 'Sorry, no report data was found.', 'buddyboss' ); ?></p>
	</aside>
</div>


<div class="bp_ld_report_table_wrapper">
	<?php
	$group_id  = bp_ld_sync( 'buddypress' )->helpers->getLearndashGroupId( groups_get_current_group()->id );
	$courseIds = learndash_group_enrolled_courses( $group_id );

	/**
	 * Filter to update course lists
	 */
	$courses = array_map( 'get_post', apply_filters( 'bp_ld_learndash_group_enrolled_courses', $courseIds, $group_id ) );
	if ( groups_is_user_mod( bp_loggedin_user_id(), groups_get_current_group()->id ) || groups_is_user_admin( bp_loggedin_user_id(), groups_get_current_group()->id ) ) {
		foreach ( $courses as $course ) {
			//learndash_get_user_course_attempts_time_spent()
			if ( groups_is_user_mod( bp_loggedin_user_id(), groups_get_current_group()->id ) || groups_is_user_admin( bp_loggedin_user_id(), groups_get_current_group()->id ) ) {
				$course_users = learndash_get_groups_user_ids( $group_id );
			} else {
				$course_users = array( bp_loggedin_user_id() );
			}

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

					$start_date = learndash_user_get_course_completed_date( $user, $course->ID )?:'-';
					$end_date   = learndash_user_get_course_completed_date( $user, $course->ID )?:'-';
					?>
					<tr>
						<td><?php echo bp_core_get_user_displayname( $user ); ?></td>
						<td><?php printf( esc_html_x( '%s%% Complete', 'Percentage of course complete', 'buddyboss' ), $progress['percentage'] ); ?></td>
						<td><?php echo $start_date; ?></td>
						<td><?php echo $end_date; ?></td>
						<td><?php echo learndash_user_get_course_completed_date( $user, $course->ID ); ?></td>
						<td><?php echo learndash_user_get_course_completed_date( $user, $course->ID ); ?></td>
					</tr>
					<?php
				}
				?>
				</tbody>

			</table> <?php
		}
	} else {
		foreach ( $courses as $course ) {
			//learndash_get_user_course_attempts_time_spent()
			if ( groups_is_user_mod( bp_loggedin_user_id(), groups_get_current_group()->id ) || groups_is_user_admin( bp_loggedin_user_id(), groups_get_current_group()->id ) ) {
				$course_users = learndash_get_groups_user_ids( $group_id );
			} else {
				$course_users = array( bp_loggedin_user_id() );
			}

			?>
			<h2><?php echo $course->post_title; ?></h2>
			<table id="admin-show-all" class="admin-show-all display" style="width:100%">
				<thead>
				<tr>
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

					$start_date = learndash_user_get_course_completed_date( $user, $course->ID )?:'-';
					$end_date   = learndash_user_get_course_completed_date( $user, $course->ID )?:'-';
					?>
					<tr>
						<td><?php printf( esc_html_x( '%s%% Complete', 'Percentage of course complete', 'buddyboss' ), $progress['percentage'] ); ?></td>
						<td><?php echo $start_date; ?></td>
						<td><?php echo $end_date; ?></td>
						<td><?php echo learndash_user_get_course_completed_date( $user, $course->ID ); ?></td>
						<td><?php echo learndash_user_get_course_completed_date( $user, $course->ID ); ?></td>
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


