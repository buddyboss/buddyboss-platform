<?php
/**
 * Template for section lesson lists for memberpress courses.
 *
 * This template can be overridden by copying it to yourtheme/memberpress/courses/courses_section_lesson_list.php.
 *
 * @since 2.6.30
 *
 * @package BuddyBoss\MemberpressLMS
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

use memberpress\courses\models as models;

$total_sections = count( $sections );
?>

<?php
foreach ( $sections as $section ) :
	$section_class = '';
	if ( 0 === $section->section_order ) {
		$section_class .= 'first_section';
	}
	if ( $section->section_order + 1 === $total_sections ) {
		$section_class .= ' last_section';
	}
	?>
	<div id="section<?php echo( (int) $section->section_order + 1 ); ?>"
		class="mpcs-section mpcs-section-lessons <?php echo esc_attr( $section_class ); ?>">
		<div class="mpcs-section-header active">
			<div class="mpcs-section-title">
				<span class="mpcs-section-title-text"><?php echo esc_html( $section->title ); ?></span>
			</div>
			<?php if ( ! empty( $section->description ) ) : ?>
				<div class="mpcs-section-description"><?php echo esc_html( $section->description ); ?></div>
			<?php endif; ?>
		</div> <!-- mpcs-section-header -->
		<div class="mpcs-lessons">
			<?php foreach ( $section->lessons( true, false ) as $lesson_index => $lesson ) : ?>
				<?php
				$lesson_available     = $lesson->is_available();
				$has_completed_lesson = is_user_logged_in() && models\UserProgress::has_completed_lesson( $current_user_id, $lesson->ID );

				if ( models\Lesson::$cpt === $lesson->post_type ) {
					?>
					<div id="mpcs-lesson-<?php echo esc_attr( $lesson->ID ); ?>"
						class="mpcs-lesson 
							<?php
							if ( ! $lesson_available ) {
								echo 'locked ';
							}
							?>
						">
						<?php if ( $lesson_available ) : ?>
						<a href="<?php echo esc_url( get_permalink( $lesson->ID ) ); ?>" class="mpcs-lesson-row-link">
							<?php else : ?>
							<span class="mpcs-lesson-row-link">
				<?php endif; ?>
					<div class="mpcs-lesson-progress">
						<?php if ( $has_completed_lesson ) : ?>
							<span class="mpcs-lesson-complete"><i class="mpcs-ok-circled"></i></span>
						<?php elseif ( $lesson_available ) : ?>
							<span class="mpcs-lesson-not-complete"><i class="mpcs-circle-regular"></i></span>
						<?php else : ?>
							<span class="mpcs-lesson-locked"><i class="mpcs-circle-regular"></i></span>
						<?php endif; ?>
					</div>
					<div class="mpcs-lesson-link <?php echo esc_attr( $lesson->post_type ); ?>">
						<?php echo esc_html( $lesson->post_title ); ?>
						<?php do_action( 'mpcs_section_lesson_title_suffix', $lesson, $has_completed_lesson ); ?>
					</div>
					<div class="mpcs-lesson-button">
						<span class="mpcs-button" href="<?php echo esc_url( get_permalink( $lesson->ID ) ); ?>">
						<?php if ( $has_completed_lesson ) : ?>
							<span class="mpcs-btn-secondary" href="<?php echo esc_url( get_permalink( $lesson->ID ) ); ?>">
							<?php esc_html_e( 'View', 'buddyboss-pro' ); ?>
							</span>
						<?php elseif ( $lesson_available ) : ?>
							<span class="mpcs-btn" href="<?php echo esc_url( get_permalink( $lesson->ID ) ); ?>">
							<?php esc_html_e( 'Start', 'buddyboss-pro' ); ?>
							</span>
						<?php endif; ?>
						</span>
					</div>
					<?php if ( $lesson_available ) : ?>
						</a>
					<?php else : ?>
						</span>
						<span class="mpcs-lesson-locked-tooltip"><?php esc_html_e( 'Lesson unavailable. You must complete all previous lessons and quizzes before you start this lesson.', 'buddyboss-pro' ); ?></span>
					<?php endif; ?>
					</div>
					<?php
				} else {
					do_action( 'mpcs_section_lesson_row', $lesson, $lesson_available, $has_completed_lesson, $is_sidebar, $show_bookmark, $next_lesson );
				}
				?>

			<?php endforeach; ?>
		</div> <!-- mpcs-lessons -->

	</div> <!-- mpcs-section -->
<?php endforeach; ?>
