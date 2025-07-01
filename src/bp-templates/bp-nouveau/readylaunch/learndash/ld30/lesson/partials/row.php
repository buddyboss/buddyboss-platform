<?php
/**
 * LearnDash LD30 Displays a single lesson row that appears in the course content listing
 *
 * Available Variables:
 * WIP
 *
 * @since 3.0.0
 * @version 4.20.2
 *
 * @package LearnDash\Templates\LD30
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Populate a list of topics and quizzes for this lesson
 *
 * @var $topics [array]
 * @var $quizzes [array]
 * @since 3.0.0
 */
$topics        = ! empty( $lesson_topics ) && ! empty( $lesson_topics[ $lesson['post']->ID ] ) ? $lesson_topics[ $lesson['post']->ID ] : '';
$quizzes       = learndash_get_lesson_quiz_list( $lesson['post']->ID, $user_id, $course_id );
$attributes    = learndash_get_course_step_attributes( $lesson['post']->ID, $course_id, $user_id );
$content_count = learndash_get_lesson_content_count( $lesson, $course_id );

// Fallbacks.
$count    = ( isset( $count ) ? $count : 0 );
$sections = ( isset( $sections ) ? $sections : array() );

/**
 * Filters lesson row attributes. Used while displaying lesson lists in a course.
 *
 * @since 3.0.0
 *
 * @param string $attribute Lesson row attribute. The value is data-ld-tooltip if a user does not have access to the course otherwise an empty string.
 */
$atts = apply_filters( 'learndash_lesson_row_atts', ( isset( $has_access ) && ! $has_access && 'is_not_sample' === $lesson['sample'] ? 'data-ld-tooltip="' . esc_html__( "You don't currently have access to this content", 'buddyboss' ) . '"' : '' ), $lesson['post']->ID, $course_id, $user_id );

/**
 * New logic to override sample lessons access LEARNDASH-3854
 */
if ( ( empty( $atts ) ) && ( ! is_user_logged_in() ) ) {
	if ( 'is_sample' === $lesson['sample'] ) {
		/** This filter is documented in themes/ld30/includes/helpers.php */
		if ( true !== (bool) apply_filters( 'learndash_lesson_sample_access', true, $lesson['post']->ID, $course_id, $user_id ) ) {
			/**
			 * Filters lesson row attributes if the access to sample lesson is not allowed to a user.
			 *
			 * @since 3.1.4
			 *
			 * @param string $attribute Lesson row attribute. The attribute value to show if the sample lesson is not accessible.
			 * @param int    $lesson_id Lesson ID.
			 * @param int    $course_id Course ID.
			 * @param int    $user_id   User ID.
			 */
			$atts = apply_filters( 'learndash_lesson_row_atts_sample_no_access', 'data-ld-tooltip="' . esc_html__( 'Please login to view sample content', 'buddyboss' ) . '"', $lesson['post']->ID, $course_id, $user_id );
		}
	}
}

/**
 * Fires before a lesson row.
 *
 * @since 3.0.0
 *
 * @param int $lesson_id Lesson ID.
 * @param int $course_id Course ID.
 * @param int $user_id   User ID.
 */
do_action( 'learndash-lesson-row-before', $lesson['post']->ID, $course_id, $user_id );

if ( isset( $sections[ $lesson['post']->ID ] ) ) :

	learndash_get_template_part(
		'lesson/partials/section.php',
		array(
			'section'   => $sections[ $lesson['post']->ID ],
			'course_id' => $course_id,
			'user_id'   => $user_id,
		),
		true
	);

endif; ?>

<div class="<?php learndash_lesson_row_class( $lesson, $has_access, $topics, $quizzes ); ?>" id="<?php echo esc_attr( 'ld-expand-' . $lesson['post']->ID ); ?>" data-ld-expand-id="<?php echo esc_attr( 'ld-expand-' . $lesson['post']->ID ); ?>" <?php echo wp_kses_post( $atts ); ?>>
	<div class="ld-item-list-item-preview">
		<?php
		/**
		 * Fires before a lesson title.
		 *
		 * @since 3.0.0
		 *
		 * @param int $lesson_id Lesson ID.
		 * @param int $course_id Course ID.
		 * @param int $user_id   User ID.
		 */
		do_action( 'learndash-lesson-row-title-before', $lesson['post']->ID, $course_id, $user_id );
		?>

		<a class="ld-item-name ld-primary-color-hover" href="<?php echo esc_attr( learndash_get_step_permalink( $lesson['post']->ID, $course_id ) ); ?>">
			<?php
			$lesson_progress = learndash_lesson_progress( $lesson['post'] );
			if ( is_array( $lesson_progress ) ) {
				$status = ( $lesson_progress['completed'] > 0 && 'completed' !== $lesson['status'] ? 'progress' : $lesson['status'] );
			} else {
				$status = $lesson['status'];
			}

			learndash_status_icon( $status, $lesson['post']->post_type, null, true );
			?>
			<div class="ld-item-title">
				<?php
                echo '<span class="bb-rl-item-title-plain">';
				// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
				echo wp_kses_post( apply_filters( 'the_title', $lesson['post']->post_title, $lesson['post']->ID ) );

                echo '</span>';
				/**
				 * Display content counts if the lesson has topics
				 */
				/**
				 * Filters whether to show lesson row attributes in lesson listing.
				 *
				 * @since 3.0.0
				 *
				 * @param boolean $show_row_attributes Whether to show lesson row attributes.
				 */
				if ( ! empty( $topics ) || ! empty( $quizzes ) || ! empty( $attributes ) || apply_filters( 'learndash-lesson-row-attributes', false ) ) :

					/**
					 * Fires after the lesson topic counts.
					 *
					 * @since 3.0.0
					 *
					 * @param int $lesson_id Lesson ID.
					 * @param int $course_id Course ID.
					 * @param int $user_id   User ID.
					 */
					do_action( 'learndash-lesson-row-topic-count-before', $lesson['post']->ID, $course_id, $user_id );
					?>

					<span class="ld-item-components">

						<?php
						/**
						 * Fires after the lesson topic counts.
						 *
						 * @since 3.0.0
						 *
						 * @param int $lesson_id Lesson ID.
						 * @param int $course_id Course ID.
						 * @param int $user_id   User ID.
						 */
						do_action( 'learndash-lesson-components-before', $lesson['post']->ID, $course_id, $user_id );

						if ( $content_count['topics'] > 0 ) :
							?>
							<span class="ld-item-component">
							<?php
							printf(
								// translators: placeholders: Topic Count, Topic/Topics Label.
								_nx(
									'%1$d %2$s',
									'%1$d %2$s',
									$content_count['topics'],
									'placeholders: Topic Count, Topic/Topics Label',
									'buddyboss'
								),
								$content_count['topics'],
								( $content_count['topics'] < 2 ? esc_attr( LearnDash_Custom_Label::get_label( 'topic' ) ) : esc_attr( LearnDash_Custom_Label::get_label( 'topics' ) ) ),
								number_format_i18n( $content_count['topics'] )
							);
							?>
							</span>
							<?php
						endif;

						if ( $content_count['topics'] > 0 && $content_count['quizzes'] > 0 ) {
							echo '<span class="ld-sep">|</span>';
						}

						if ( $content_count['quizzes'] > 0 ) :
							?>
							<span class="ld-item-component">
							<?php
							printf(
								// translators: placeholders: Quiz Count, Quiz/Quizzes Label.
								_nx(
									'%1$d %2$s',
									'%1$d %2$s',
									$content_count['quizzes'],
									'placeholders: Quiz Count, Quiz/Quizzes Label',
									'buddyboss'
								),
								$content_count['quizzes'],
								( $content_count['quizzes'] < 2 ? esc_attr( LearnDash_Custom_Label::get_label( 'quiz' ) ) : esc_attr( LearnDash_Custom_Label::get_label( 'quizzes' ) ) ),
								number_format_i18n( $content_count['quizzes'] )
							);
							?>
							</span>
							<?php
						endif;

						if ( ! empty( $attributes ) ) :
							foreach ( $attributes as $attribute ) :
								$ld_icon_class = isset( $attribute['icon'] ) && ! empty( $attribute['icon'] )
												? 'ld-icon ' . $attribute['icon']
												: '';
								?>
							<span class="<?php echo esc_attr( 'ld-status ' . ( $attribute['class'] ?? '' ) ); ?>">
								<span class="<?php echo esc_attr( $ld_icon_class ); ?>"></span>
								<?php echo esc_html( $attribute['label'] ); ?>
							</span>
								<?php
							endforeach;
						endif;

						/**
						 * Fires after the lesson topic counts.
						 *
						 * @since 3.0.0
						 *
						 * @param int $lesson_id Lesson ID.
						 * @param int $course_id Course ID.
						 * @param int $user_id   User ID.
						 */
						do_action( 'learndash-lesson-components-after', $lesson['post']->ID, $course_id, $user_id );
						?>

					</span> <!--/.ld-item-components-->
					<?php
					/**
					 * Fires after the lesson topic counts.
					 *
					 * @since 3.0.0
					 *
					 * @param int $lesson_id Lesson ID.
					 * @param int $course_id Course ID.
					 * @param int $user_id   User ID.
					 */
					do_action( 'learndash-lesson-preview-after', $lesson['post']->ID, $course_id, $user_id );
					?>
				<?php endif; ?>

			</div> <!--/.ld-item-title-->
		</a>

		<?php
		/**
		 * Fires after the lesson title.
		 *
		 * @since 3.0.0
		 *
		 * @param int $lesson_id Lesson ID.
		 * @param int $course_id Course ID.
		 * @param int $user_id   User ID.
		 */
		do_action( 'learndash-lesson-row-title-after', $lesson['post']->ID, $course_id, $user_id );
		?>

		<div class="ld-item-details">
			<?php

			/**
			 * Fires before the attribute bubbles.
			 *
			 * @since 3.0.0
			 *
			 * @param int $lesson_id Lesson ID.
			 * @param int $course_id Course ID.
			 * @param int $user_id   User ID.
			 */
			do_action( 'learndash-lesson-row-attributes-before', $lesson['post']->ID, $course_id, $user_id );

			/**
			 * If this lesson has topics or quizzes show an expand button
			 *
			 * @var [type]
			 */
			if ( ! empty( $topics ) || ! empty( $quizzes ) ) :
				/**
				 * Fires before the expand button.
				 *
				 * @since 3.0.0
				 *
				 * @param int $lesson_id Lesson ID.
				 * @param int $course_id Course ID.
				 * @param int $user_id   User ID.
				 */
				do_action( 'learndash-lesson-row-expand-before', $lesson['post']->ID, $course_id, $user_id );
				?>

				<button
					aria-controls="<?php echo esc_attr( 'ld-expand-' . $lesson['post']->ID . '-container' ); ?>"
					aria-expanded="false"
					class="ld-expand-button ld-button-alternate"
					data-ld-collapse-text="<?php esc_html_e( 'Collapse', 'buddyboss' ); ?>"
					data-ld-expand-text="<?php esc_html_e( 'Expand', 'buddyboss' ); ?>"
					data-ld-expands="<?php echo esc_attr( 'ld-expand-' . $lesson['post']->ID. '-container' ); ?>"
				>
					<span class="ld-icon-arrow-down ld-icon ld-primary-background"></span>
					<span class="ld-text ld-primary-color"><?php esc_html_e( 'Expand', 'buddyboss' ); ?></span>
				</button> <!--/.ld-expand-button-->

				<?php
				/**
				 * Fires after the lesson title.
				 *
				 * @since 3.0.0
				 *
				 * @param int $lesson_id Lesson ID.
				 * @param int $course_id Course ID.
				 * @param int $user_id   User ID.
				 */
				do_action( 'learndash-lesson-row-expand-after', $lesson['post']->ID, $course_id, $user_id );

			endif;
			?>
		</div> <!--/.ld-item-details-->

		<?php
		/**
		 * Fires after the attribute bubbles.
		 *
		 * @since 3.0.0
		 *
		 * @param int $lesson_id Lesson ID.
		 * @param int $course_id Course ID.
		 * @param int $user_id   User ID.
		 */
		do_action( 'learndash-lesson-row-attributes-after', $lesson['post']->ID, $course_id, $user_id );
		?>

	</div> <!--/.ld-item-list-item-preview-->
	<?php
	/**
	 * If the lesson has associated topics, display a list
	 *
	 * @var $topics [array]
	 * @since 3.0.0
	 */
	if ( ! empty( $topics ) || ! empty( $quizzes ) ) :
		?>
		<div
			class="ld-item-list-item-expanded"
			id="<?php echo esc_attr( 'ld-expand-' . $lesson['post']->ID . '-container' ); ?>"
		>
			<?php
			/**
			 * Fires before the topic/quiz list
			 *
			 * @since 3.0.0
			 *
			 * @param int $lesson_id Lesson ID.
			 * @param int $course_id Course ID.
			 * @param int $user_id   User ID.
			 */
			do_action( 'learndash-lesson-row-topic-list-before', $lesson['post']->ID, $course_id, $user_id );

			learndash_get_template_part(
				'lesson/listing.php',
				array(
					'lesson'               => $lesson,
					'topics'               => $topics,
					'quizzes'              => $quizzes,
					'course_id'            => $course_id,
					'user_id'              => $user_id,
					'course_pager_results' => $course_pager_results,
				),
				true
			);

			/**
			 * Fires after the topic/quiz list.
			 *
			 * @since 3.0.0
			 *
			 * @param int $lesson_id Lesson ID.
			 * @param int $course_id Course ID.
			 * @param int $user_id   User ID.
			 */
			do_action( 'learndash-lesson-row-topic-list-after', $lesson['post']->ID, $course_id, $user_id );
			?>
		</div> <!--/.ld-item-list-item-expanded-->
	<?php endif; ?>
</div> <!--/.ld-item-list-item-->
	<?php
	/**
	 * Fires after a lesson row.
	 *
	 * @since 3.0.0
	 *
	 * @param int $lesson_id Lesson ID.
	 * @param int $course_id Course ID.
	 * @param int $user_id   User ID.
	 */
	do_action( 'learndash-lesson-row-after', $lesson['post']->ID, $course_id, $user_id ); ?>
