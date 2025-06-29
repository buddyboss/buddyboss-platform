<?php
/**
 * Template for bookmark for memberpress courses.
 *
 * This template can be overridden by copying it to yourtheme/memberpress/courses/courses_classroom_bookmark.php.
 *
 * @since 2.6.30
 *
 * @package BuddyBoss\MemberpressLMS
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
} ?>

<h2><?php esc_html_e( 'Course Curriculum', 'buddyboss-pro' ); ?></h2>

<div id="bookmark" class="course-curriculum">
	<?php if ( isset( $bookmark_url ) ) : ?>
		<a href="<?php echo esc_url( $bookmark_url ); ?>">
			<span><?php esc_html_e( 'Start Next Lesson', 'buddyboss-pro' ); ?></span>
			<i class="mpcs-angle-right"></i>
		</a>
	<?php endif; ?>

	<span class="mpcs-bookmark-link-title hide-md"><?php echo esc_html( $next_lesson_title ); ?></span>
</div>
