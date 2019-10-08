
<h3 class="ld-report-course-name">
	<a href="<?php echo get_permalink( $course->ID ); ?>" target="_blank">
		<?php echo $course->post_title; ?>
	</a>
</h3>

<?php if ( groups_is_user_mod( bp_loggedin_user_id(), groups_get_current_group()->id ) || groups_is_user_admin( bp_loggedin_user_id(), groups_get_current_group()->id ) || bp_current_user_can( 'bp_moderate' ) || $courseHasPoints ) { ?>
	<div class="ld-report-course-stats">
		<?php if ( groups_is_user_mod( bp_loggedin_user_id(),
				groups_get_current_group()->id ) || groups_is_user_admin( bp_loggedin_user_id(),
				groups_get_current_group()->id ) || bp_current_user_can( 'bp_moderate' ) ) { ?>
			<div class="course-completed">
				<p>
					<?php
					printf( __( '<b>%1$d out of %2$d</b> %3$s completed', 'buddyboss' ),
						count( $ldGroupUsersCompleted ),
						$totalStudents = count( $ldGroupUsers ),
						_n( 'student', 'students', $totalStudents, 'buddyboss' ) );
					?>
				</p>
			</div>
		<?php } ?>

		<?php if ( $courseHasPoints ) : ?>
			<div class="course-average-points">
				<p>
					<?php
					printf( __( '<b>%d</b> average points', 'buddyboss' ),
						$averagePoints );
					?>
				</p>
			</div>
		<?php endif; ?>
	</div>
	<?php
}
