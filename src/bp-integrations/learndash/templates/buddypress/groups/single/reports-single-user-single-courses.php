<?php
$group_id       = bp_ld_sync( 'buddypress' )->helpers->getLearndashGroupId( groups_get_current_group()->id );
$courseIds      = array( $_REQUEST['course'] );
$label          = 'STEP';
$courses        = array_map( 'get_post', apply_filters( 'bp_ld_learndash_group_enrolled_courses', $courseIds, $group_id ) );

if ( ( groups_is_user_mod( bp_loggedin_user_id(), bp_get_current_group_id() ) || groups_is_user_admin( bp_loggedin_user_id(), bp_get_current_group_id() ) || bp_current_user_can( 'bp_moderate' ) ) && isset( $_GET ) && isset( $_GET['user'] ) && '' === $_GET['user'] ) { ?>
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
	<?php
} else {
	?>
	<?php if ( ! bp_current_user_can( 'bp_moderate' ) || ! groups_is_user_mod( bp_loggedin_user_id(), bp_get_current_group_id() ) || ! groups_is_user_admin( bp_loggedin_user_id(), bp_get_current_group_id() ) ) { ?>
		<?php if ( ! bp_current_user_can( 'bp_moderate' ) ) { ?>
		<div class="ld-report-user-stats">
			<div class="user-info">
				<div class="user-avatar">
					<a href="<?php echo bp_core_get_user_domain( bp_loggedin_user_id() ); ?>"><?php echo bp_core_fetch_avatar( array( 'item_id' => bp_loggedin_user_id() ) ); ?></a>
				</div>
				<div class="user-name">
					<h5 class="list-title member-name"><a href="<?php echo bp_core_get_user_domain( bp_loggedin_user_id() ); ?>"><?php echo bp_core_get_user_displayname( bp_loggedin_user_id() ); ?></a></h5>
					<p class="item-meta"><?php echo __( 'Student', 'buddyboss' ); ?></p>
				</div>
			</div>
		</div>
	<?php } ?>
	<?php } ?>
	<?php
}
?>
<div class="bp_ld_report_table_wrapper">
	<?php
	foreach ( $courses as $course ) {

		?>
		<h2><?php echo $course->post_title; ?></h2>
		<?php
		$course_users = learndash_get_groups_user_ids( $group_id );
		if ( isset( $_GET ) && isset( $_GET['user'] ) ) {
			$data  = bp_ld_get_course_all_steps( $course->ID, $_GET['user'], 'all' );
			$steps = $data['steps'];
			$label = __( 'STEP', 'buddyboss' );
			?>
			<table id="admin-show-all" class="admin-show-all display" style="width:100%">
				<thead>
				<tr>
					<th><?php echo $label; ?></th>
					<th><?php echo __( 'Start  Date', 'buddyboss' ); ?></th>
					<th><?php echo __( 'Completion Date', 'buddyboss' ); ?></th>
					<th><?php echo __( 'Time Spent', 'buddyboss' ); ?></th>
					<th><?php echo __( 'Points Earned', 'buddyboss' ); ?></th>
				</tr>
				</thead>
				<tbody>
				<?php
				foreach ( $steps as $step ) {

					?>
					<tr>
						<td><?php echo '<a href=" ' . get_the_permalink( $step['id'] ) . ' ">' . $step['title'] . '</a>'; ?></td>
						<?php
						if ( is_null( $step['activity'] ) ) {
							?>
							<td><?php echo '-'; ?></td>
							<td><?php echo '-'; ?></td>
							<td><?php echo '-'; ?></td>
							<td><?php echo '-'; ?></td>
							<?php
						} else {

							$time_spent = bp_ld_time_spent( $step['activity'] );
							$start_date = date_i18n( bp_get_option( 'date_format' ), intval( $step['activity']->activity_started ) );
							$points     = bpLdCoursePointsEarned( $step['activity'] );

							if ( is_null( $step['activity'] ) ) {
								$end_date = '';
							} else {
								$end_date = ( $step['activity']->activity_completed ) ? date_i18n( bp_get_option( 'date_format' ), intval( $step['activity']->activity_completed ) ) : '-';
							}

							?>
							<td><?php echo $start_date; ?></td>
							<td><?php echo $end_date; ?></td>
							<td><?php echo $time_spent; ?></td>
							<td><?php echo $points; ?></td>
							<?php
						}
						?>
					</tr>
					<?php
				}
				?>
				</tbody>

			</table>
			<?php
		} else {
			if ( isset( $_REQUEST ) && isset( $_REQUEST['step'] ) && '' === $_REQUEST['step'] ) {
				$data  = bp_ld_get_course_all_steps( $course->ID, bp_loggedin_user_id(), 'all' );
				$steps = $data['steps'];
				$label = __( 'STEP', 'buddyboss' );
				?>
				<table id="admin-show-all" class="admin-show-all display" style="width:100%">
					<thead>
					<tr>
						<th><?php echo $label; ?></th>
						<th><?php echo __( 'Start  Date', 'buddyboss' ); ?></th>
						<th><?php echo __( 'Completion Date', 'buddyboss' ); ?></th>
						<th><?php echo __( 'Time Spent', 'buddyboss' ); ?></th>
						<th><?php echo __( 'Points Earned', 'buddyboss' ); ?></th>
					</tr>
					</thead>
					<tbody>
					<?php
					foreach ( $steps as $step ) {

						?>
						<tr>
							<td><?php echo '<a href=" ' . get_the_permalink( $step['id'] ) . ' ">' . $step['title'] . '</a>'; ?></td>
							<?php
							if ( is_null( $step['activity'] ) ) {
								?>
								<td><?php echo '-'; ?></td>
								<td><?php echo '-'; ?></td>
								<td><?php echo '-'; ?></td>
								<td><?php echo '-'; ?></td>
								<?php
							} else {

								$time_spent = bp_ld_time_spent( $step['activity'] );
								$start_date = date_i18n( bp_get_option( 'date_format' ), intval( $step['activity']->activity_started ) );
								$points     = bpLdCoursePointsEarned( $step['activity'] );

								if ( is_null( $step['activity'] ) ) {
									$end_date = '';
								} else {
									$end_date = ( $step['activity']->activity_completed ) ? date_i18n( bp_get_option( 'date_format' ), intval( $step['activity']->activity_completed ) ) : '-';
								}

								?>
								<td><?php echo $start_date; ?></td>
								<td><?php echo $end_date; ?></td>
								<td><?php echo $time_spent; ?></td>
								<td><?php echo $points; ?></td>
								<?php
							}
							?>
						</tr>
						<?php
					}
					?>
					</tbody>

				</table>
				<?php
			} elseif ( isset( $_REQUEST ) && isset( $_REQUEST['course'] ) && isset( $_REQUEST['step'] ) && 'all' === $_REQUEST['step'] ) {
				$label = __( 'STEP', 'buddyboss' );
				$data  = bp_ld_get_course_all_steps( $course->ID, bp_loggedin_user_id(), 'all' );
				$steps = $data['steps'];
				?>
				<table id="admin-show-all" class="admin-show-all display" style="width:100%">
					<thead>
					<tr>
						<th><?php echo $label; ?></th>
						<th><?php echo __( 'Start  Date', 'buddyboss' ); ?></th>
						<th><?php echo __( 'Completion Date', 'buddyboss' ); ?></th>
						<th><?php echo __( 'Time Spent', 'buddyboss' ); ?></th>
						<th><?php echo __( 'Points Earned', 'buddyboss' ); ?></th>
					</tr>
					</thead>
					<tbody>
					<?php
					foreach ( $steps as $step ) {

						?>
						<tr>
							<td><?php echo '<a href=" ' . get_the_permalink( $step['id'] ) . ' ">' . $step['title'] . '</a>'; ?></td>
							<?php
							if ( is_null( $step['activity'] ) ) {
								?>
								<td><?php echo '-'; ?></td>
								<td><?php echo '-'; ?></td>
								<td><?php echo '-'; ?></td>
								<td><?php echo '-'; ?></td>
								<?php
							} else {

								$time_spent = bp_ld_time_spent( $step['activity'] );
								$start_date = date_i18n( bp_get_option( 'date_format' ), intval( $step['activity']->activity_started ) );
								$points     = bpLdCoursePointsEarned( $step['activity'] );

								if ( is_null( $step['activity'] ) ) {
									$end_date = '';
								} else {
									$end_date = ( $step['activity']->activity_completed ) ? date_i18n( bp_get_option( 'date_format' ), intval( $step['activity']->activity_completed ) ) : '-';
								}

								?>
								<td><?php echo $start_date; ?></td>
								<td><?php echo $end_date; ?></td>
								<td><?php echo $time_spent; ?></td>
								<td><?php echo $points; ?></td>
								<?php
							}
							?>
						</tr>
						<?php
					}
					?>
					</tbody>

				</table>
				<?php
			} elseif ( isset( $_REQUEST ) && isset( $_REQUEST['course'] ) && isset( $_REQUEST['step'] ) && 'sfwd-lessons' === $_REQUEST['step'] ) {
				$label = LearnDash_Custom_Label::get_label( 'lesson' );
				$data  = bp_ld_get_course_all_steps( $course->ID, bp_loggedin_user_id(), 'lesson' );
				$steps = $data['steps'];
				?>
				<table id="admin-show-all" class="admin-show-all display" style="width:100%">
					<thead>
					<tr>
						<th><?php echo $label; ?></th>
						<th><?php echo __( 'Start  Date', 'buddyboss' ); ?></th>
						<th><?php echo __( 'Completion Date', 'buddyboss' ); ?></th>
						<th><?php echo __( 'Time Spent', 'buddyboss' ); ?></th>
						<th><?php echo __( 'Points Earned', 'buddyboss' ); ?></th>
					</tr>
					</thead>
					<tbody>
					<?php
					foreach ( $steps as $step ) {

						?>
						<tr>
							<td><?php echo '<a href=" ' . get_the_permalink( $step['id'] ) . ' ">' . $step['title'] . '</a>'; ?></td>
							<?php
							if ( is_null( $step['activity'] ) ) {
								?>
								<td><?php echo '-'; ?></td>
								<td><?php echo '-'; ?></td>
								<td><?php echo '-'; ?></td>
								<td><?php echo '-'; ?></td>
								<?php
							} else {

								$time_spent = bp_ld_time_spent( $step['activity'] );
								$start_date = date_i18n( bp_get_option( 'date_format' ), intval( $step['activity']->activity_started ) );
								$points     = bpLdCoursePointsEarned( $step['activity'] );

								if ( is_null( $step['activity'] ) ) {
									$end_date = '';
								} else {
									$end_date = ( $step['activity']->activity_completed ) ? date_i18n( bp_get_option( 'date_format' ), intval( $step['activity']->activity_completed ) ) : '-';
								}

								?>
								<td><?php echo $start_date; ?></td>
								<td><?php echo $end_date; ?></td>
								<td><?php echo $time_spent; ?></td>
								<td><?php echo $points; ?></td>
								<?php
							}
							?>
						</tr>
						<?php
					}
					?>
					</tbody>

				</table>
				<?php
			} elseif ( isset( $_REQUEST ) && isset( $_REQUEST['course'] ) && isset( $_REQUEST['step'] ) && 'sfwd-topic' === $_REQUEST['step'] ) {
				$label = LearnDash_Custom_Label::get_label( 'topic' );
				$data  = bp_ld_get_course_all_steps( $course->ID, bp_loggedin_user_id(), 'topic' );
				$steps = $data['steps'];
				?>
				<table id="admin-show-all" class="admin-show-all display" style="width:100%">
					<thead>
					<tr>
						<th><?php echo $label; ?></th>
						<th><?php echo __( 'Start  Date', 'buddyboss' ); ?></th>
						<th><?php echo __( 'Completion Date', 'buddyboss' ); ?></th>
						<th><?php echo __( 'Time Spent', 'buddyboss' ); ?></th>
						<th><?php echo __( 'Points Earned', 'buddyboss' ); ?></th>
					</tr>
					</thead>
					<tbody>
					<?php
					foreach ( $steps as $step ) {

						?>
						<tr>
							<td><?php echo '<a href=" ' . get_the_permalink( $step['id'] ) . ' ">' . $step['title'] . '</a>'; ?></td>
							<?php
							if ( is_null( $step['activity'] ) ) {
								?>
								<td><?php echo '-'; ?></td>
								<td><?php echo '-'; ?></td>
								<td><?php echo '-'; ?></td>
								<td><?php echo '-'; ?></td>
								<?php
							} else {

								$time_spent = bp_ld_time_spent( $step['activity'] );
								$start_date = date_i18n( bp_get_option( 'date_format' ), intval( $step['activity']->activity_started ) );
								$points     = bpLdCoursePointsEarned( $step['activity'] );

								if ( is_null( $step['activity'] ) ) {
									$end_date = '';
								} else {
									$end_date = ( $step['activity']->activity_completed ) ? date_i18n( bp_get_option( 'date_format' ), intval( $step['activity']->activity_completed ) ) : '-';
								}

								?>
								<td><?php echo $start_date; ?></td>
								<td><?php echo $end_date; ?></td>
								<td><?php echo $time_spent; ?></td>
								<td><?php echo $points; ?></td>
								<?php
							}
							?>
						</tr>
						<?php
					}
					?>
					</tbody>

				</table>
				<?php
			} elseif ( isset( $_REQUEST ) && isset( $_REQUEST['course'] ) && isset( $_REQUEST['step'] ) && 'sfwd-quiz' === $_REQUEST['step'] ) {
				$label = LearnDash_Custom_Label::get_label( 'quiz' );
				$data  = bp_ld_get_course_all_steps( $course->ID, bp_loggedin_user_id(), 'quiz' );
				$steps = $data['steps'];
				?>
				<table id="admin-show-all" class="admin-show-all display" style="width:100%">
					<thead>
					<tr>
						<th><?php echo $label; ?></th>
						<th><?php echo __( 'Start  Date', 'buddyboss' ); ?></th>
						<th><?php echo __( 'Completion Date', 'buddyboss' ); ?></th>
						<th><?php echo __( 'Score', 'buddyboss' ); ?></th>
						<th><?php echo __( 'Time Spent', 'buddyboss' ); ?></th>
						<th><?php echo __( 'Points Earned', 'buddyboss' ); ?></th>
						<th><?php echo __( 'Attempts', 'buddyboss' ); ?></th>
					</tr>
					</thead>
					<tbody>
					<?php
					foreach ( $steps as $step ) {

						?>
						<tr>
							<td><?php echo '<a href=" ' . get_the_permalink( $step['id'] ) . ' ">' . $step['title'] . '</a>'; ?></td>
							<?php
							if ( is_null( $step['activity'] ) ) {
								?>
								<td><?php echo '-'; ?></td>
								<td><?php echo '-'; ?></td>
								<td><?php echo $step['score']; ?></td>
								<td><?php echo '-'; ?></td>
								<td><?php echo '-'; ?></td>
								<td><?php echo $step['attempt']; ?></td>
								<?php
							} else {

								$time_spent = bp_ld_time_spent( $step['activity'] );
								$start_date = date_i18n( bp_get_option( 'date_format' ), intval( $step['activity']->activity_started ) );
								$points     = bpLdCoursePointsEarned( $step['activity'] );

								if ( is_null( $step['activity'] ) ) {
									$end_date = '';
								} else {
									$end_date = ( $step['activity']->activity_completed ) ? date_i18n( bp_get_option( 'date_format' ), intval( $step['activity']->activity_completed ) ) : '-';
								}

								?>
								<td><?php echo $start_date; ?></td>
								<td><?php echo $end_date; ?></td>
								<td><?php echo $step['score']; ?></td>
								<td><?php echo $time_spent; ?></td>
								<td><?php echo $points; ?></td>
								<td><?php echo $step['attempt']; ?></td>
								<?php
							}
							?>
						</tr>
						<?php
					}
					?>
					</tbody>

				</table>
				<?php
			} elseif ( isset( $_REQUEST ) && isset( $_REQUEST['course'] ) && isset( $_REQUEST['step'] ) && 'sfwd-assignment' === $_REQUEST['step'] ) {
				$label = __( 'ASSIGNMENT', 'buddyboss' );
				$data  = bp_ld_get_course_all_steps( $course->ID, bp_loggedin_user_id(), 'assignment' );
				$steps = $data['steps'];
				?>
				<table id="admin-show-all" class="admin-show-all display" style="width:100%">
					<thead>
					<tr>
						<th><?php echo $label; ?></th>
						<th><?php echo __( 'Graded Date', 'buddyboss' ); ?></th>
						<th><?php echo __( 'Score', 'buddyboss' ); ?></th>
					</tr>
					</thead>
					<tbody>
					<?php
					foreach ( $steps as $step ) {

						?>
						<tr>
							<td><?php echo $step['title']; ?></td>
							<td><?php echo $step['graded']; ?></td>
							<td><?php echo $step['score']; ?></td>
						</tr>
						<?php
					}
					?>
					</tbody>

				</table>
				<?php
			} else {
				$label = __( 'STEP', 'buddyboss' );
				$data  = bp_ld_get_course_all_steps( $course->ID, bp_loggedin_user_id(), 'all' );
				$steps = $data['steps'];
				?>
				<table id="admin-show-all" class="admin-show-all display" style="width:100%">
					<thead>
					<tr>
						<th><?php echo $label; ?></th>
						<th><?php echo __( 'Start  Date', 'buddyboss' ); ?></th>
						<th><?php echo __( 'Completion Date', 'buddyboss' ); ?></th>
						<th><?php echo __( 'Time Spent', 'buddyboss' ); ?></th>
						<th><?php echo __( 'Points Earned', 'buddyboss' ); ?></th>
					</tr>
					</thead>
					<tbody>
					<?php
					foreach ( $steps as $step ) {

						?>
						<tr>
							<td><?php echo '<a href=" ' . get_the_permalink( $step['id'] ) . ' ">' . $step['title'] . '</a>'; ?></td>
							<?php
							if ( is_null( $step['activity'] ) ) {
								?>
								<td><?php echo '-'; ?></td>
								<td><?php echo '-'; ?></td>
								<td><?php echo '-'; ?></td>
								<td><?php echo '-'; ?></td>
								<?php
							} else {

								$time_spent = bp_ld_time_spent( $step['activity'] );
								$start_date = date_i18n( bp_get_option( 'date_format' ), intval( $step['activity']->activity_started ) );
								$points     = bpLdCoursePointsEarned( $step['activity'] );

								if ( is_null( $step['activity'] ) ) {
									$end_date = '';
								} else {
									$end_date = ( $step['activity']->activity_completed ) ? date_i18n( bp_get_option( 'date_format' ), intval( $step['activity']->activity_completed ) ) : '-';
								}

								?>
								<td><?php echo $start_date; ?></td>
								<td><?php echo $end_date; ?></td>
								<td><?php echo $time_spent; ?></td>
								<td><?php echo $points; ?></td>
								<?php
							}
							?>
						</tr>
						<?php
					}
					?>
					</tbody>

				</table>
				<?php
			}
		}
	}
	?>
</div>


