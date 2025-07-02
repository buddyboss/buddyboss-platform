<?php
/**
 * Template for displaying course author in ReadyLaunch
 *
 * @since BuddyBoss 2.9.00
 * @package BuddyBoss\ReadyLaunch
 */

defined( 'ABSPATH' ) || exit;



$course = llms_get_post( get_the_ID() );
if ( ! $course || ! is_a( $course, 'LLMS_Course' ) ) {
	return;
}

$instructors = $course->get_instructors();
if ( ! $instructors ) {
	return;
}
?>

<div class="bb-rl-lifterlms-course-author">
	<h4 class="bb-rl-lifterlms-author-title"><?php _e( 'Course Instructor', 'buddyboss' ); ?></h4>
	
	<?php foreach ( $instructors as $instructor ) : ?>
		<div class="bb-rl-lifterlms-author-item">
			<?php if ( ! empty( $instructor['avatar'] ) ) : ?>
				<div class="bb-rl-lifterlms-author-avatar">
					<img src="<?php echo esc_url( $instructor['avatar'] ); ?>" alt="<?php echo esc_attr( $instructor['name'] ?? '' ); ?>" />
				</div>
			<?php endif; ?>
			
			<div class="bb-rl-lifterlms-author-info">
				<h5 class="bb-rl-lifterlms-author-name"><?php echo esc_html( $instructor['name'] ?? '' ); ?></h5>
				<?php if ( ! empty( $instructor['bio'] ) ) : ?>
					<div class="bb-rl-lifterlms-author-bio">
						<?php echo wp_kses_post( $instructor['bio'] ); ?>
					</div>
				<?php endif; ?>
			</div>
		</div>
	<?php endforeach; ?>
</div> 