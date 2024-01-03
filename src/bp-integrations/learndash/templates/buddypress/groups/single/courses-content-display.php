<?php

$ld_group_id = bp_ld_sync( 'buddypress' )->helpers->getLearndashGroupId( bp_get_current_group_id() );

if ( $ld_group_id ) {
	$post_label_prefix = 'group';
	$meta              = learndash_get_setting( $ld_group_id );
	$post_price_type   = ( isset( $meta[ $post_label_prefix . '_price_type' ] ) ) ? $meta[ $post_label_prefix . '_price_type' ] : '';
	$post_price        = ( isset( $meta[ $post_label_prefix . '_price' ] ) ) ? $meta[ $post_label_prefix . '_price' ] : '';
	// format the Course price to be proper XXX.YY no leading dollar signs or other values.
	if ( ( 'paynow' === $post_price_type ) || ( 'subscribe' === $post_price_type ) ) {
		if ( '' !== $post_price ) {
			$post_price = preg_replace( '/[^0-9.]/', '', $post_price );
			$post_price = number_format( floatval( $post_price ), 2, '.', '' );
		}
	}
	if ( ! empty( $post_price ) && ! learndash_is_user_in_group( bp_loggedin_user_id(), $ld_group_id ) ) {
		?>
		<div class="bp-feedback error">
			<span class="bp-icon" aria-hidden="true"></span>
			<p><?php echo esc_html__( 'You are not allowed to access group courses. Please purchase membership and try again.', 'buddyboss' ); ?></p>
		</div>
		<?php
		return;
	}
}


global $courses_new, $course_id, $course;

if ( is_user_logged_in() ) {
	$user_id = get_current_user_id();
} else {
	$user_id = 0;
}

$course_id                  = $courses_new[0]->ID;
$course                     = $courses_new[0];
$course_settings            = learndash_get_setting( $course );
$lesson_progression_enabled = learndash_lesson_progression_enabled( $course_id );
$courses_options            = learndash_get_option( 'sfwd-courses' );
$lessons_options            = learndash_get_option( 'sfwd-lessons' );
$quizzes_options            = learndash_get_option( 'sfwd-quiz' );
$course_status              = learndash_course_status( $course_id, null );
$has_access                 = sfwd_lms_has_access( $course_id, $user_id );
$has_topics                 = false;

$content = apply_filters( 'the_content', $courses_new[0]->post_content );
// $content = do_shortcode( $courses_new[0]->post_content );

$logged_in = ! empty( $user_id );
$materials = '';
if ( ( 'on' === $course_settings['course_materials_enabled'] ) && ( ! empty( $course_settings['course_materials'] ) ) ) {
	$materials = wp_specialchars_decode( $course_settings['course_materials'], ENT_QUOTES );
	if ( ! empty( $materials ) ) {
		$materials = do_shortcode( $materials );
	}
}

$lessons                    = learndash_get_course_lessons_list( $course, $user_id );
$quizzes                    = learndash_get_course_quiz_list( $course );
$has_course_content         = ( ! empty( $lessons ) || ! empty( $quizzes ) );
$lesson_query_args          = learndash_focus_mode_lesson_query_args( $course_id );
$lessons                    = learndash_30_get_course_navigation( $course_id, array(), $lesson_query_args );
$has_access                 = sfwd_lms_has_access( $course_id );
$lesson_progression_enabled = learndash_lesson_progression_enabled( $course_id );
$lesson_topics              = array();

if ( ! empty( $lessons ) ) {
	foreach ( $lessons as $lesson ) {

		$all_topics = learndash_topic_dots( $lesson['post']->ID, false, 'array', null, $course_id );

		$topic_pager_args = apply_filters(
			'ld30_ajax_topic_pager_args',
			array(
				'course_id' => $course_id,
				'lesson_id' => $lesson['post']->ID,
			)
		);

		$lesson_topics[ $lesson['post']->ID ] = learndash_process_lesson_topics_pager( $all_topics, $topic_pager_args );

		if ( ! empty( $lesson_topics[ $lesson['post']->ID ] ) ) {
			$has_topics = true;
		}
	}
}

$course_certficate_link = learndash_get_course_certificate_link( $course_id, $user_id );
$course_meta            = get_post_meta( $course_id, '_sfwd-courses', true );

$template_args = array(
	'course_id'                  => $course_id,
	'course'                     => $course,
	'course_settings'            => $course_settings,
	'courses_options'            => $courses_options,
	'lessons_options'            => $lessons_options,
	'quizzes_options'            => $quizzes_options,
	'user_id'                    => $user_id,
	'logged_in'                  => $logged_in,
	'current_user'               => wp_get_current_user(),
	'course_status'              => $course_status,
	'has_access'                 => $has_access,
	'materials'                  => $materials,
	'has_course_content'         => $has_course_content,
	'lessons'                    => $lessons,
	'quizzes'                    => $quizzes,
	'lesson_progression_enabled' => $lesson_progression_enabled,
	'has_topics'                 => isset( $has_topics ) ? $has_topics : false,
	'lesson_topics'              => $lesson_topics,
);

$has_lesson_quizzes = learndash_30_has_lesson_quizzes( $course_id, $lessons );
?>



<div class="<?php echo esc_attr( learndash_the_wrapper_class() ); ?>">

	<?php
	global $course_pager_results;

	/**
	 * Action to add custom content before the topic
	 *
	 * @since 3.0
	 */
	do_action( 'learndash-course-before', $course_id, $course_id, $user_id );

	/**
	 * Action to add custom content before the course certificate link
	 *
	 * @since 3.0
	 */
	do_action( 'learndash-course-certificate-link-before', $course_id, $user_id );

	/**
	 * Action to add custom content after the course certificate link
	 *
	 * @since 3.0
	 */
	do_action( 'learndash-course-certificate-link-after', $course_id, $user_id );
	?>

	<div class="bb-grid">

		<div class="bb-learndash-content-wrap">

			<h1 class="entry-title"><?php echo get_the_title( $course_id ); ?></h1>

			<?php
			/**
			 * Certificate link
			 */
			if ( $course_certficate_link && ! empty( $course_certficate_link ) ) :

				learndash_get_template_part(
					'modules/alert.php',
					array(
						'type'    => 'success ld-alert-certificate',
						'icon'    => 'certificate',
						'message' => __( 'You\'ve earned a certificate!', 'buddyboss' ),
						'button'  => array(
							'url'   => $course_certficate_link,
							'icon'  => 'download',
							'label' => __( 'Download Certificate', 'buddyboss' ),
						),
					),
					true
				);
			endif;

			if ( has_excerpt( $course_id ) ) {
				?>
				<div class="bb-course-excerpt">
					<?php echo get_the_excerpt( $course_id ); ?>
				</div>
				<?php
			}

			/**
			 * Course info bar
			 */
			learndash_get_template_part(
				'modules/infobar.php',
				array(
					'context'       => 'course',
					'course_id'     => $course_id,
					'user_id'       => $user_id,
					'has_access'    => $has_access,
					'course_status' => $course_status,
					'post'          => $post,
				),
				true
			);
			?>

			<?php
			/**
			 * Filter to add custom content after the Course Status section of the Course template output.
			 *
			 * @since 2.3
			 * See https://bitbucket.org/snippets/learndash/7oe9K for example use of this filter.
			 */
			echo apply_filters(
				'ld_after_course_status_template_container',
				'',
				learndash_course_status_idx( $course_status ),
				$course_id,
				$user_id
			);

			/**
			 * Content tabs
			 */
			echo '<div class="bb-ld-tabs">';
			echo '<div id="learndash-course-content"></div>';
			learndash_get_template_part(
				'modules/tabs.php',
				array(
					'course_id' => $course_id,
					'post_id'   => $course_id,
					'user_id'   => $user_id,
					'content'   => $content,
					'materials' => $materials,
					'context'   => 'course',
				),
				true
			);
			echo '</div>';

			/**
			 * Identify if we should show the course content listing
			 *
			 * @var $show_course_content [bool]
			 */
			$show_course_content = ( ! $has_access && 'on' === $course_meta['sfwd-courses_course_disable_content_table'] ? false : true );

			if ( $has_course_content && $show_course_content ) :
				?>

				<div class="ld-item-list ld-lesson-list">
					<div class="ld-section-heading">

						<?php
						/**
						 * Action to add custom content before the course heading
						 *
						 * @since 3.0
						 */
						do_action( 'learndash-course-heading-before', $course_id, $user_id );
						?>

						<h2>
						<?php
						printf(
							esc_html_x( '%s Content', 'Course Content Label', 'buddyboss' ),
							esc_attr( LearnDash_Custom_Label::get_label( 'course' ) )
						);
						?>
							</h2>

						<?php
						/**
						 * Action to add custom content after the course heading
						 *
						 * @since 3.0
						 */
						do_action( 'learndash-course-heading-after', $course_id, $user_id );
						?>

						<div class="ld-item-list-actions" data-ld-expand-list="true">

							<?php
							/**
							 * Action to add custom content after the course content progress bar
							 *
							 * @since 3.0
							 */
							do_action( 'learndash-course-expand-before', $course_id, $user_id );
							?>

							<?php
							// Only display if there is something to expand.
							if ( $has_topics || $has_lesson_quizzes ) :
								?>
								<div class="ld-expand-button ld-primary-background"
									 id="<?php echo esc_attr( 'ld-expand-button-' . $course_id ); ?>"
									 data-ld-expands="<?php echo esc_attr( 'ld-item-list-' . $course_id ); ?>"
									 data-ld-expand-text="<?php esc_attr_e( 'Expand All', 'buddyboss' ); ?>"
									 data-ld-collapse-text="<?php esc_attr_e( 'Collapse All', 'buddyboss' ); ?>">

									<span class="ld-icon-arrow-down ld-icon"></span>
									<span class="ld-text"><?php esc_html_e( 'Expand All', 'buddyboss' ); ?></span>
								</div> <!--/.ld-expand-button-->
								<?php
								// TODO @37designs Need to test this
								if ( apply_filters(
									'learndash_course_steps_expand_all',
									false,
									$course_id,
									'course_lessons_listing_main'
								) ) :
									?>
								<script>
									jQuery(document).ready(function () {
										jQuery("<?php echo '#ld-expand-button-' . $course_id; ?>").click();
									});
								</script>
									<?php
							endif;

							endif;

							/**
							 * Action to add custom content after the course content expand button
							 *
							 * @since 3.0
							 */
							do_action( 'learndash-course-expand-after', $course_id, $user_id );
							?>

						</div> <!--/.ld-item-list-actions-->
					</div> <!--/.ld-section-heading-->

					<?php
					/**
					 * Action to add custom content before the course content listing
					 *
					 * @since 3.0
					 */
					do_action( 'learndash-course-content-list-before', $course_id, $user_id );

					/**
					 * Content content listing
					 *
					 * @since 3.0
					 *
					 * ('listing.php');
					 */

					learndash_get_template_part(
						'course/listing.php',
						array(
							'course_id'                  => $course_id,
							'user_id'                    => $user_id,
							'lessons'                    => $lessons,
							'lesson_topics'              => @$lesson_topics,
							'quizzes'                    => $quizzes,
							'has_access'                 => $has_access,
							'course_pager_results'       => $course_pager_results,
							'lesson_progression_enabled' => $lesson_progression_enabled,
						),
						true
					);

					/**
					 * Action to add custom content before the course content listing
					 *
					 * @since 3.0
					 */
					do_action( 'learndash-course-content-list-after', $course_id, $user_id );
					?>

				</div> <!--/.ld-item-list-->

				<?php
			endif;
			$post = $courses_new[0];
			learndash_get_template_part(
				'template-course-author-details.php',
				array(
					'context'   => 'course',
					'course_id' => $course_id,
					'user_id'   => $user_id,
				),
				true
			);


			?>


		</div>

		<?php
		// Single course sidebar
		$post = $courses_new[0];
		learndash_get_template_part( 'template-single-course-sidebar.php', $template_args, true );
		?>
	</div>

	<?php

	/**
	 * Action to add custom content before the topic
	 *
	 * @since 3.0
	 */
	do_action( 'learndash-course-after', $course_id, $course_id, $user_id );

	learndash_get_template_part( 'modules/login-modal.php', array(), true );
	?>

</div>

