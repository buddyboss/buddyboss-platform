<?php
/**
 * LearnDash Single Lesson Template for ReadyLaunch
 *
 * @package BuddyBoss\Core
 * @since BuddyBoss [BBVERSION]
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Ensure LearnDash functions are available
if ( ! class_exists( 'SFWD_LMS' ) || ! function_exists( 'learndash_get_course_id' ) ) {
	// Fallback to default content if LearnDash functions aren't available
	?>
	<div class="bb-learndash-content-wrap">
		<main class="bb-learndash-content-area">
			<article id="post-<?php the_ID(); ?>" <?php post_class('bb-rl-learndash-lesson'); ?>>
				<header class="bb-rl-entry-header">
					<h1 class="bb-rl-entry-title"><?php the_title(); ?></h1>
				</header>
				<div class="bb-rl-entry-content">
					<?php the_content(); ?>
				</div>
			</article>
		</main>
	</div>
	<?php
	return;
}

$lesson_id = get_the_ID();
$user_id = get_current_user_id();
$course_id = function_exists( 'learndash_get_course_id' ) ? learndash_get_course_id( $lesson_id ) : 0;
$lesson = get_post( $lesson_id );
$lesson_progress = function_exists( 'learndash_lesson_progress' ) ? learndash_lesson_progress( array( 'lesson_id' => $lesson_id, 'user_id' => $user_id, 'array' => true ) ) : array();
$is_enrolled = function_exists( 'sfwd_lms_has_access' ) ? sfwd_lms_has_access( $course_id, $user_id ) : false;
$lesson_status = function_exists( 'learndash_lesson_status' ) ? learndash_lesson_status( $lesson_id, $user_id ) : '';
$topics = function_exists( 'learndash_get_topic_list' ) ? learndash_get_topic_list( $lesson_id, $user_id ) : array();
$prev_lesson = function_exists( 'learndash_get_previous_lesson' ) ? learndash_get_previous_lesson( $lesson_id ) : null;
$next_lesson = function_exists( 'learndash_get_next_lesson' ) ? learndash_get_next_lesson( $lesson_id ) : null;

$lesson_list = learndash_get_course_lessons_list( $course_id, null, array( 'num' => - 1 ) );
$lesson_list = array_column( $lesson_list, 'post' );
$lesson_topics_completed = learndash_lesson_topics_completed( $post->ID );
/* $content_urls            = BB_Readylaunch::instance()->learndash_helper()->bb_rl_ld_custom_pagination( $course_id, $lesson_list );
$pagination_urls         = BB_Readylaunch::instance()->learndash_helper()->bb_rl_custom_next_prev_url( $content_urls ); */

$lesson_no = 1;
foreach ( $lesson_list as $les ) {
    if ( $les->ID == $post->ID ) {
        break;
    }
    $lesson_no ++;
}

// Define variables for course-steps module compatibility
$logged_in = is_user_logged_in();
$course_settings = function_exists( 'learndash_get_setting' ) ? learndash_get_setting( $course_id ) : array();
$all_quizzes_completed = true; // Assume all quizzes are completed for now
$previous_lesson_completed = true;
if ( function_exists( 'learndash_is_lesson_accessable' ) ) {
    $previous_lesson_completed = learndash_is_lesson_accessable( $user_id, $post );
}
?>

<div class="bb-learndash-content-wrap bb-learndash-content-wrap--lesson">
	<main class="bb-learndash-content-area">
		<article id="post-<?php the_ID(); ?>" <?php post_class('bb-rl-learndash-lesson'); ?>>
            <div class="bb-rl-lesson-block bb-rl-lms-inner-block">
                <header class="bb-rl-entry-header">
                    <div class="bb-rl-heading">
                        <div class="bb-rl-lesson-count bb-rl-lms-inner-count">
                            <span class="bb-pages"><?php echo LearnDash_Custom_Label::get_label( 'lesson' ); ?> <?php echo $lesson_no; ?> <span class="bb-total"><?php esc_html_e( 'of', 'buddyboss' ); ?> <?php echo count( $lesson_list ); ?></span></span>
                        </div>
                        <div class="bb-rl-lesson-title">
                            <h1 class="bb-rl-entry-title"><?php the_title(); ?></h1>
                        </div>
                    </div>

                    <div class="bb-rl-lesson-meta">
                        <?php if ( $is_enrolled ) : ?>
                            <div class="bb-rl-lesson-status">
                                <span class="bb-rl-status bb-rl-enrolled"><?php echo esc_html( $lesson_status ); ?></span>
                                <?php if ( ! empty( $lesson_progress ) ) : ?>
                                    <div class="bb-rl-lesson-progress">
                                        <div class="bb-rl-progress-bar">
                                            <div class="bb-rl-progress" style="width: <?php echo (int) $lesson_progress['percentage']; ?>%"></div>
                                        </div>
                                        <span class="bb-rl-percentage"><?php echo (int) $lesson_progress['percentage']; ?>% <?php esc_html_e( 'Complete', 'buddyboss' ); ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </header>

                <div class="bb-rl-entry-content">
                    <?php the_content(); ?>
                </div>

                <?php if ( ! empty( $topics ) ) : ?>
                    <div class="bb-rl-lesson-topics">
                        <h3><?php esc_html_e( 'Lesson Topics', 'buddyboss' ); ?></h3>
                        <ul class="bb-rl-topics-list">
                            <?php foreach ( $topics as $topic ) : ?>
                                <li class="bb-rl-topic-item">
                                    <a href="<?php echo esc_url( get_permalink( $topic->ID ) ); ?>" class="bb-rl-topic-link">
                                        <?php echo esc_html( $topic->post_title ); ?>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
            </div>

			<nav class="bb-rl-ld-module-footer bb-rl-lesson-footer">
                <div class="bb-rl-ld-module-actions bb-rl-lesson-actions">
                    <div class="bb-rl-course-steps">
                        <button type="submit" class="bb-rl-mark-complete-button bb-rl-button bb-rl-button--brandFill bb-rl-button--small"><?php esc_html_e( 'Mark Complete', 'buddyboss' ); ?></button>
                    </div>
                    <div class="bb-rl-ld-module-count bb-rl-lesson-count">
                        <span class="bb-pages"><?php echo LearnDash_Custom_Label::get_label( 'lesson' ); ?> <?php echo $lesson_no; ?> <span class="bb-total"><?php esc_html_e( 'of', 'buddyboss' ); ?> <?php echo count( $lesson_list ); ?></span></span>
                    </div>
                    <div class="learndash_next_prev_link">
                        <?php
                        if ( isset( $pagination_urls['prev'] ) && $pagination_urls['prev'] != '' ) {
                            echo $pagination_urls['prev'];
                        } else {
                            echo '<span class="prev-link empty-post"><i class="bb-icons-rl-caret-left"></i>' . esc_html__( 'Previous', 'buddyboss' ) . '</span>';
                        }
                        ?>
                        <?php
                        if (
                            (
                                isset( $pagination_urls['next'] ) &&
                                apply_filters( 'learndash_show_next_link', learndash_is_lesson_complete( $user_id, $post->ID ), $user_id, $post->ID ) &&
                                $pagination_urls['next'] != ''
                            ) ||
                            (
                                isset( $pagination_urls['next'] ) &&
                                $pagination_urls['next'] != '' &&
                                isset( $course_settings['course_disable_lesson_progression'] ) &&
                                $course_settings['course_disable_lesson_progression'] === 'on'
                            )
                        ) {
                            echo $pagination_urls['next'];
                        } else {
                            echo '<span class="next-link empty-post">' . esc_html__( 'Next Lesson', 'buddyboss' ) . '<i class="bb-icons-rl-caret-right"></i></span>';
                        }
                        ?>
                    </div>
                </div>
			</nav>
		</article>
	</main>
</div> 