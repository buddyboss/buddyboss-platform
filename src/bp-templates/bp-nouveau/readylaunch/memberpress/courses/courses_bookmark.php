<?php
/**
 * Template for course archive page for memberpress courses.
 *
 * This template can be overridden by copying it to yourtheme/memberpress/courses/archive-mpcs-courses.php.
 *
 * @since 2.6.30
 *
 * @package BuddyBoss\MemberpressLMS
 */

defined( 'ABSPATH' ) || exit; ?>

<h2><?php esc_html_e( 'Course Curriculum', 'buddyboss-platform' ); ?></h2>

<div id="bookmark" class="course-curriculum">
	<?php if ( isset( $bookmark_url ) ) : ?>
		<a href="<?php echo esc_url( $bookmark_url ); ?>">
			<span><?php esc_html_e( 'Start Next Lesson', 'buddyboss-platform' ); ?></span>
			<i class="mpcs-angle-right"></i>
		</a>
	<?php endif; ?>

	<span class="mpcs-bookmark-link-title hide-md">
		<?php echo ! empty( $lesson ) ? esc_html( $lesson->post_title ) : ''; ?>
	</span>
</div>
