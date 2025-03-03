
<h3 class="ld-report-course-name">
	<a href="<?php echo get_permalink( $course->ID ); ?>" target="_blank">
		<?php echo $course->post_title; ?>
	</a>
</h3>

<div class="ld-report-course-stats">
	<div class="course-completed">
		<p>
		<?php
		printf(
			__( '<b>%1$d out of %2$d</b> %3$s completed', 'buddyboss' ),
			count( $ldGroupUsersCompleted ),
			$totalStudents = count( $ldGroupUsers ),
			_n( 'student', 'students', $totalStudents, 'buddyboss' )
		);
		?>
		</p>
	</div>

	<?php if ( $courseHasPoints ) : ?>
		<div class="course-average-points">
			<p>
			<?php
			printf(
				__( '<b>%d</b> average points', 'buddyboss' ),
				$averagePoints
			);
			?>
			</p>
		</div>
	<?php endif; ?>
</div>
