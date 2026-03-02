<?php
/**
 * LearnDash Group Reports Course Stats Template
 *
 * @package BuddyBoss\Core
 * @subpackage BP_Integrations\LearnDash\Templates
 * @version 1.0.0
 * @since BuddyBoss 2.9.00
 */

defined( 'ABSPATH' ) || exit;

?>
<h3 class="ld-report-course-name">
	<a href="<?php echo esc_url( get_permalink( $course->ID ) ); ?>" target="_blank">
		<?php echo esc_html( $course->post_title ); ?>
	</a>
</h3>

<div class="ld-report-course-stats">
	<div class="course-completed">
		<p>
		<?php
		printf(
			/* translators: 1: Number of completed students, 2: Total number of students, 3: Student/students text */
			esc_html__( '%1$s out of %2$s %3$s completed', 'buddyboss' ),
			'<b>' . esc_html( count( $ldGroupUsersCompleted ) ) . '</b>',
			'<b>' . esc_html( $totalStudents = count( $ldGroupUsers ) ) . '</b>',
			esc_html( _n( 'student', 'students', $totalStudents, 'buddyboss' ) )
		);
		?>
		</p>
	</div>

	<?php if ( $courseHasPoints ) : ?>
		<div class="course-average-points">
			<p>
			<?php
			printf(
				/* translators: %d: Average points number */
				esc_html__( '%s average points', 'buddyboss' ),
				'<b>' . esc_html( $averagePoints ) . '</b>'
			);
			?>
			</p>
		</div>
	<?php endif; ?>
</div>
