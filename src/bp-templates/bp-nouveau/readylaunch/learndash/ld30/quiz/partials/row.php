<?php
/**
 * LearnDash LD30 Displays a single quiz row
 *
 * Available Variables:
 *
 * $user_id   :   The current user ID
 * $course_id :   The current course ID
 * $lesson    :   The current lesson
 * $topic     :   The current topic object
 * $quiz      :   The current quiz (array)
 *
 * @since 3.0.0
 *
 * @package LearnDash\Templates\LD30
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$quiz_classes = learndash_quiz_row_classes( $quiz, $context );
$is_sample    = ( isset( $lesson['sample'] ) ? $lesson['sample'] : false );

$attributes = learndash_get_course_step_attributes( $quiz['post']->ID, $course_id, $user_id );

/**
 * Filters quiz row attributes. Used while displaying a single quiz row.
 *
 * @since 3.0.0
 *
 * @param string $attribute Quiz row attribute. The value is data-ld-tooltip if a user does not have access to quiz otherwise empty string.
 */
$atts = apply_filters( 'learndash_quiz_row_atts', ( isset( $has_access ) && ! $has_access && ! $is_sample ? 'data-ld-tooltip="' . esc_html__( "You don't currently have access to this content", 'buddyboss' ) . '"' : '' ) );

$learndash_quiz_available_date = learndash_course_step_available_date( $quiz['post']->ID, $course_id, $user_id, true );
if ( ! empty( $learndash_quiz_available_date ) ) {
	$quiz_classes['wrapper'] .= ' learndash-not-available';
}

/**
 * Fires before the quiz row listing.
 *
 * @since 3.0.0
 *
 * @param int $quiz_id   Quiz ID.
 * @param int $course_id Course ID.
 * @param int $user_id   User ID.
 */
do_action( 'learndash-quiz-row-before', $quiz['post']->ID, $course_id, $user_id ); ?>
<div id="<?php echo esc_attr( 'ld-table-list-item-' . $quiz['post']->ID ); ?>" class="<?php echo esc_attr( $quiz_classes['wrapper'] ); ?> <?php echo esc_attr( 'ld-table-list-item-' . $quiz['post']->ID ); ?>" <?php echo wp_kses_post( $atts ); ?>>
	<div class="<?php echo esc_attr( $quiz_classes['preview'] ); ?>">
		<a class="<?php echo esc_attr( $quiz_classes['anchor'] ); ?>" href="<?php echo esc_url( learndash_get_step_permalink( $quiz['post']->ID, $course_id ) ); ?>">
			<?php
			/**
			 * Fires before the quiz row status.
			 *
			 * @since 3.0.0
			 *
			 * @param int $quiz_id   Post ID.
			 * @param int $course_id Course ID.
			 * @param int $user_id   User ID.
			 */
			do_action( 'learndash-quiz-row-status-before', $quiz['post']->ID, $course_id, $user_id );

			learndash_status_icon( $quiz['status'], 'sfwd-quiz', null, true );
			/**
			 * Fires before the quiz row title.
			 *
			 * @since 3.0.0
			 *
			 * @param int $quiz_id   Quiz ID.
			 * @param int $course_id Course ID.
			 * @param int $user_id   User ID.
			 */
			do_action( 'learndash-quiz-row-title-before', $quiz['post']->ID, $course_id, $user_id );
			?>

			<div class="ld-item-title">
				<span class="bb-rl-item-title-plain"><?php echo wp_kses_post( apply_filters( 'the_title', $quiz['post']->post_title, $quiz['post']->ID ) ); ?></span>
				<?php
				if ( ! empty( $attributes ) ) :
					foreach ( $attributes as $attribute ) :
						?>
					<span class="<?php echo esc_attr( 'ld-status ' . $attribute['class'] ); ?>">
						<span class="<?php echo esc_attr( 'ld-icon ' . $attribute['icon'] ); ?>"></span>
						<?php echo esc_html( $attribute['label'] ); ?>
					</span>
						<?php
					endforeach;
				endif;
				?>
			</div> <?php // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound ?>

			<?php
			/**
			 * Fires after the quiz row title.
			 *
			 * @since 3.0.0
			 *
			 * @param int $quiz_id   Quiz ID.
			 * @param int $course_id Course ID.
			 * @param int $user_id   User ID.
			 */
			do_action( 'learndash-quiz-row-title-after', $quiz['post']->ID, $course_id, $user_id );
			?>
		</a>
	</div> <!--/.list-item-preview-->
</div>
<?php
/**
 * Fires after the quiz row listing.
 *
 * @since 3.0.0
 *
 * @param int $quiz_id   Quiz ID.
 * @param int $course_id Course ID.
 * @param int $user_id   User ID.
 */
do_action( 'learndash-quiz-row-after', $quiz['post']->ID, $course_id, $user_id );
