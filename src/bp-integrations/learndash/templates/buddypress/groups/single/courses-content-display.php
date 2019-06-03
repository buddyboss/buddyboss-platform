<div class="single-course-content">
	<?php
	global $courses_new;
	//echo do_shortcode( '[learndash_course_progress user_id="'.get_current_user_id().'" course_id="'.$courses_new[0]->ID.'"]' );
	//echo apply_filters( 'the_content', $courses_new[0]->post_content );
	//echo do_shortcode( '[course_content course_id="'.$courses_new[0]->ID.'"]' );
	?>
</div>

<?php
if ( is_user_logged_in() )
	$user_id = get_current_user_id();
else
	$user_id = 0;


global $course_id;
global $course;

$course_id                  = $courses_new[0]->ID;
$course                     = $courses_new[0];
$course_settings            = learndash_get_setting( $course );
$lesson_progression_enabled = learndash_lesson_progression_enabled( $course_id );
$courses_options            = learndash_get_option( 'sfwd-courses' );
$lessons_options            = learndash_get_option( 'sfwd-lessons' );
$quizzes_options            = learndash_get_option( 'sfwd-quiz' );
$course_status              = learndash_course_status( $course_id, null );
$has_access                 = sfwd_lms_has_access( $course_id, $user_id );

$content = apply_filters( 'the_content', $courses_new[0]->post_content );
//$content = do_shortcode( $courses_new[0]->post_content );

$logged_in = ! empty( $user_id );
$materials = '';
if ( ( 'on' === $course_settings['course_materials_enabled'] ) && ( ! empty( $course_settings['course_materials'] ) ) ) {
	$materials = wp_specialchars_decode( $course_settings['course_materials'], ENT_QUOTES );
	if ( ! empty( $materials ) ) {
		$materials = do_shortcode( $materials );
	}
}

$lessons = learndash_get_course_lessons_list( $course, $user_id );
$quizzes = learndash_get_course_quiz_list( $course );
$has_course_content = ( ! empty( $lessons ) || ! empty( $quizzes ) );

$lesson_query_args = learndash_focus_mode_lesson_query_args( $course_id );
$lessons           = learndash_30_get_course_navigation( $course_id, array(), $lesson_query_args );
$has_access 	   = sfwd_lms_has_access($course_id);
$lesson_progression_enabled = learndash_lesson_progression_enabled( $course_id );
$lesson_topics = array();

if ( ! empty( $lessons ) ) {
	foreach ( $lessons as $lesson ) {

		$all_topics = learndash_topic_dots( $lesson['post']->ID, false, 'array', null, $course_id );

		$topic_pager_args = apply_filters( 'ld30_ajax_topic_pager_args', array(
			'course_id' => $course_id,
			'lesson_id' => $lesson['post']->ID
		) );

		$lesson_topics[ $lesson['post']->ID ] = learndash_process_lesson_topics_pager( $all_topics, $topic_pager_args );

		if ( ! empty( $lesson_topics[ $lesson['post']->ID ] ) ) {
			$has_topics = true;
		}

	}
}

$course_certficate_link     = learndash_get_course_certificate_link( $course_id, $user_id );
$course_meta = get_post_meta( $course_id, '_sfwd-courses', true );

$template_args = array(
'course_id' => $course_id,
'course' => $course,
'course_settings' => $course_settings,
'courses_options' => $courses_options,
'lessons_options' => $lessons_options,
'quizzes_options' => $quizzes_options,
'user_id' => $user_id,
'logged_in' => $logged_in,
'current_user' => wp_get_current_user(),
'course_status' => $course_status,
'has_access' => $has_access,
'materials' => $materials,
'has_course_content' => $has_course_content,
'lessons' => $lessons,
'quizzes' => $quizzes,
'lesson_progression_enabled' => $lesson_progression_enabled,
'has_topics' => $has_topics,
'lesson_topics' => $lesson_topics,
);

$has_lesson_quizzes = learndash_30_has_lesson_quizzes( $course_id, $lessons ); ?>

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
	 * Certificate link
	 *
	 *
	 */

	if( $course_certficate_link && !empty($course_certficate_link) ):

		learndash_get_template_part( 'modules/alert.php', array(
			'type'      =>  'success ld-alert-certificate',
			'icon'      =>  'certificate',
			'message'   =>  __( 'You\'ve earned a certificate!', 'buddyboss-theme' ),
			'button'    =>  array(
				'url'   =>  $course_certficate_link,
				'icon'  =>  'download',
				'label' =>  __( 'Download Certificate', 'buddyboss-theme' )
			)
		), true );

	endif;

	/**
	 * Action to add custom content after the course certificate link
	 *
	 * @since 3.0
	 */
	do_action( 'learndash-course-certificate-link-after', $course_id, $user_id );

//	learndash_get_template_part( 'template-banner.php', array(
//		'context'       => 'course',
//		'course_id'     => $course_id,
//		'user_id'       => $user_id
//	), true );
	?>

	<div class="bb-grid">

		<div class="bb-learndash-content-wrap">

			<?php
			/**
			 * Course info bar
			 *
			 */
			learndash_get_template_part( 'modules/infobar.php', array(
				'context'       => 'course',
				'course_id'     => $course_id,
				'user_id'       => $user_id,
				'has_access'    => $has_access,
				'course_status' => $course_status,
				'post'          => $post
			), true ); ?>

			<?php
			/**
			 * Filter to add custom content after the Course Status section of the Course template output.
			 *
			 * @since 2.3
			 * See https://bitbucket.org/snippets/learndash/7oe9K for example use of this filter.
			 */
			echo apply_filters( 'ld_after_course_status_template_container', '', learndash_course_status_idx( $course_status ), $course_id, $user_id );

			/**
			 * Content tabs
			 *
			 */
			echo '<div class="bb-ld-tabs">';
			echo '<div id="learndash-course-content"></div>';
			learndash_get_template_part( 'modules/tabs.php', array(
				'course_id' => $course_id,
				'post_id'   => $course_id,
				'user_id'   => $user_id,
				'content'   => $content,
				'materials' => $materials,
				'context'   => 'course'
			), true );
			echo '</div>';

			/**
			 * Identify if we should show the course content listing
			 * @var $show_course_content [bool]
			 */
			$show_course_content = ( !$has_access && 'on' === $course_meta['sfwd-courses_course_disable_content_table'] ? false : true );

			if( $has_course_content && $show_course_content ): ?>

				<div class="ld-item-list ld-lesson-list">
					<div class="ld-section-heading">

						<?php
						/**
						 * Action to add custom content before the course heading
						 *
						 * @since 3.0
						 */
						do_action( 'learndash-course-heading-before', $course_id, $user_id ); ?>

						<h2><?php printf( esc_html_x( '%s Content', 'Course Content Label', 'buddyboss-theme' ), esc_attr( LearnDash_Custom_Label::get_label( 'course' ) ) ); ?></h2>

						<?php
						/**
						 * Action to add custom content after the course heading
						 *
						 * @since 3.0
						 */
						do_action( 'learndash-course-heading-after', $course_id, $user_id ); ?>

						<div class="ld-item-list-actions" data-ld-expand-list="true">

							<?php
							/**
							 * Action to add custom content after the course content progress bar
							 *
							 * @since 3.0
							 */
							do_action( 'learndash-course-expand-before', $course_id, $user_id ); ?>

							<?php
							// Only display if there is something to expand
							if( $has_topics || $has_lesson_quizzes ): ?>
								<div class="ld-expand-button ld-primary-background" id="<?php echo esc_attr( 'ld-expand-button-' . $course_id ); ?>" data-ld-expands="<?php echo esc_attr( 'ld-item-list-' . $course_id ); ?>" data-ld-expand-text="<?php echo esc_attr_e( 'Expand All', 'buddyboss-theme' ); ?>" data-ld-collapse-text="<?php echo esc_attr_e( 'Collapse All', 'buddyboss-theme' ); ?>">
									<span class="ld-icon-arrow-down ld-icon"></span>
									<span class="ld-text"><?php echo esc_html_e( 'Expand All', 'buddyboss-theme' ); ?></span>
								</div> <!--/.ld-expand-button-->
							<?php
							// TODO @37designs Need to test this
							if ( apply_filters( 'learndash_course_steps_expand_all', false, $course_id, 'course_lessons_listing_main' ) ): ?>
								<script>
									jQuery(document).ready(function(){
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
							do_action( 'learndash-course-expand-after', $course_id, $user_id ); ?>

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

					learndash_get_template_part( 'course/listing.php', array(
						'course_id'     => $course_id,
						'user_id'       => $user_id,
						'lessons'       => $lessons,
						'lesson_topics' => @$lesson_topics,
						'quizzes'       => $quizzes,
						'has_access'    => $has_access,
						'course_pager_results' =>  $course_pager_results,
						'lesson_progression_enabled' => $lesson_progression_enabled,
					), true );

					/**
					 * Action to add custom content before the course content listing
					 *
					 * @since 3.0
					 */
					do_action( 'learndash-course-content-list-after', $course_id, $user_id ); ?>

				</div> <!--/.ld-item-list-->

			<?php
			endif;

			learndash_get_template_part( 'template-course-author-details.php', array(
				'context'       => 'course',
				'course_id'     => $course_id,
				'user_id'       => $user_id
			), true );



			?>



		</div>

		<?php


		$is_enrolled         = false;
		$current_user_id     = get_current_user_id();
		$course_members      = bp_ld_sync()->bp_get_course_members( $course_id );
		$course_price        = learndash_get_course_meta_setting( $course_id, 'course_price' );
		$course_price_type   = learndash_get_course_meta_setting( $course_id, 'course_price_type' );
		$course_button_url   = learndash_get_course_meta_setting( $course_id, 'custom_button_url' );
		$paypal_settings     = LearnDash_Settings_Section::get_section_settings_all( 'LearnDash_Settings_Section_PayPal' );
		$course_video_embed  = get_post_meta( $course_id, '_buddyboss_lms_course_video', true );
		$course_certificate  = learndash_get_course_meta_setting( $course_id, 'certificate' );
		$courses_progress    = bp_ld_sync()->bp_get_courses_progress( $current_user_id );
		$course_progress     = isset( $courses_progress[ $course_id ] ) ? $courses_progress[ $course_id ] : null;
		$course_progress_new = bp_ld_sync()->bp_ld_get_progress_course_percentage( get_current_user_id(), $course_id );
		$admin_enrolled      = LearnDash_Settings_Section::get_section_setting('LearnDash_Settings_Section_General_Admin_User', 'courses_autoenroll_admin_users' );
		$course_pricing      = learndash_get_course_price( $course_id );
		$has_access          = sfwd_lms_has_access( $course_id, $current_user_id );

		if ( '' != $course_video_embed ) {
			$thumb_mode = 'thumbnail-container-vid';
		} else {
			$thumb_mode = 'thumbnail-container-img';
		}

		if ( sfwd_lms_has_access( $course->ID, $current_user_id ) ) {
			$is_enrolled         = true;
		} else {
			$is_enrolled         = false;
		}

		?>
		<div class="bb-single-course-sidebar bb-preview-wrap">
			<div class="widget bb-enroll-widget">
				<?php //if ( has_post_thumbnail() || ( '' != $course_video_embed ) ) { ?>
				<div class="bb-enroll-widget flex-1 push-right">
					<div class="bb-course-preview-wrap bb-thumbnail-preview">
						<div class="bb-preview-course-link-wrap">
							<div class="thumbnail-container <?php echo $thumb_mode; ?>">
								<div class="bb-course-video-overlay">
									<div>
										<span class="bb-course-play-btn"></span>
										<div><?php _e( 'Preview this', 'buddyboss-theme' ); ?> <?php echo LearnDash_Custom_Label::get_label( 'course' ) ?></div>
									</div>
								</div>
								<?php echo get_the_post_thumbnail($courses_new[0]->ID); ?>
							</div>
						</div>
					</div>
				</div>
				<?php //} ?>

				<div class="bb-course-preview-content">
					<?php if( ! empty( $course_members ) ) { ?>
						<div class="bb-course-member-wrap flex align-items-center">
							<?php $count = 0; ?>
							<span class="bb-course-members">
					<?php foreach( $course_members as $course_member ) : ?>
						<?php if ( $count > 2 ) { break; } ?>
						<img class="round" src="<?php echo get_avatar_url( $course_member->ID, array( 'size' => 96 ) ); ?>" alt="" />
						<?php $count++; endforeach; ?>
					</span>

							<?php if( sizeof( $course_members ) > 3 ) { ?><span class="members"><span class="members-count-g">+<?php echo sizeof( $course_members ) - 3; ?></span><?php } ?> <?php _e( 'enrolled', 'buddyboss-theme' ); ?></span>
						</div>
					<?php } ?>

					<div class="bb-course-status-wrap">

						<?php do_action( 'learndash-course-infobar-status-cell-before', get_post_type(), $course_id, $current_user_id ); ?>

						<?php
						$progress = learndash_course_progress( array(
							'user_id'   => $current_user_id,
							'course_id' => $course_id,
							'array'     => true
						) );

						$status = ( $progress['percentage'] == 100 ) ? 'completed' : 'notcompleted';

						if( $progress['percentage'] > 0 && $progress['percentage'] !== 100 ) {
							$status = 'progress';
						}

						if( is_user_logged_in() && isset($has_access) && $has_access ) { ?>

							<div class="bb-course-status-content">
								<?php learndash_status_bubble($status); ?>
							</div>

						<?php } elseif ( $course_pricing['type'] !== 'open' ) {

							echo '<div class="bb-course-status-content">';
							echo '<div class="ld-status ld-status-incomplete ld-third-background">' . __( 'Not Enrolled', 'buddyboss-theme' ) . '</div>';
							echo '</div>';

						}
						?>

						<?php do_action( 'learndash-course-infobar-status-cell-after', get_post_type(), $course_id, $current_user_id ); ?>

					</div>

					<div class="bb-button-wrap">
						<?php


						$resume_link               = '';

						if ( empty( $course_progress ) && $course_progress < 100 ) {
							$btn_advance_class = 'btn-advance-start';
							$btn_advance_label = __( 'Start Course', 'buddyboss-theme' );
							$resume_link       = bp_ld_sync()->bp_course_resume( $course_id );
						} elseif ( $course_progress == 100 ) {
							$btn_advance_class = 'btn-advance-completed';
							$btn_advance_label = __( 'Completed', 'buddyboss-theme' );
						} else {
							$btn_advance_class = 'btn-advance-continue';
							$btn_advance_label = __( 'Continue', 'buddyboss-theme' );
							$resume_link       = bp_ld_sync()->bp_course_resume( $course_id );
						}

						$login_model = LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Theme_LD30', 'login_mode_enabled' );
						$login_url   = apply_filters( 'learndash_login_url', ( $login_model === 'yes' ? '#login' : wp_login_url( get_the_permalink( $course_id ) ) ) );

						if ( $course_price_type == 'open' || $course_price_type == 'free' ) {

							if( apply_filters( 'learndash_login_modal', true, $course_id, $current_user_id ) && !is_user_logged_in() ):

								?>
								<div class="learndash_join_button <?php echo $btn_advance_class; ?>">
									<a href="<?php echo esc_url( $login_url ); ?>" class="btn-advance"><?php echo __( 'Login to Enroll', 'buddyboss-theme' ); ?></a>
								</div>
							<?php

							else:

								if ( $course_price_type == 'free' && false === $is_enrolled ) {

									$button_text = LearnDash_Custom_Label::get_label( 'button_take_this_course' );
									?>
								<div class="learndash_join_button <?php echo $btn_advance_class; ?>">
									<form method="post">
										<input type="hidden" value="<?php echo $course_id; ?>" name="course_id" />
										<input type="hidden" name="course_join" value="<?php echo wp_create_nonce( 'course_join_'. get_current_user_id() .'_'. $course_id ); ?>" />
										<input type="submit" value="<?php echo $button_text; ?>" class="btn-join" id="btn-join" />
									</form>
									</div><?php

								} else {

									?>
									<div class="learndash_join_button <?php echo $btn_advance_class; ?>">
										<a href="<?php echo esc_url( $resume_link ); ?>" class="btn-advance"><?php echo $btn_advance_label; ?></a>
									</div>
									<?php

								}

							endif;

							if ( $course_price_type == 'open' ) {
								?><span class="bb-course-type bb-course-type-open"><?php _e( 'Open Registration', 'buddyboss-theme' ); ?></span><?php
							} else {
								?><span class="bb-course-type bb-course-type-free"><?php _e( 'Free', 'buddyboss-theme' ); ?></span><?php
							}

						} elseif ( $course_price_type == 'closed' ) {
							$learndash_payment_buttons = learndash_payment_buttons( $course );
							if( empty($learndash_payment_buttons) ):
								echo '<span class="ld-status ld-status-incomplete ld-third-background ld-text">' . __( 'This course is currently closed', 'buddyboss-theme' ) . '</span>';
							else:
								?>
							<div class="learndash_join_button <?php echo 'btn-advance-continue '; ?>"> <?php
								echo $learndash_payment_buttons; ?>
								</div><?php
							endif;

						} elseif ( $course_price_type == 'paynow' || $course_price_type == 'subscribe' ) {

							if ( false === $is_enrolled ) {

								$meta = get_post_meta( $course_id, '_sfwd-courses', true );
								$course_price_type = @$meta['sfwd-courses_course_price_type'];
								$course_price = @$meta['sfwd-courses_course_price'];
								$course_no_of_cycles = @$meta['sfwd-courses_course_no_of_cycles'];
								$course_price = @$meta['sfwd-courses_course_price'];
								$custom_button_url = @$meta['sfwd-courses_custom_button_url'];
								$custom_button_label = @$meta['sfwd-courses_custom_button_label'];

								if ( $course_price_type == 'subscribe' && $course_price == '') {

									if ( empty( $custom_button_label ) ) {
										$button_text = LearnDash_Custom_Label::get_label( 'button_take_this_course' );
									} else {
										$button_text = esc_attr( $custom_button_label );
									}

									$join_button = '<div class="learndash_join_button"><form method="post">
							<input type="hidden" value="'. $course->ID .'" name="course_id" />
							<input type="hidden" name="course_join" value="'. wp_create_nonce( 'course_join_'. get_current_user_id() .'_'. $course->ID ) .'" />
							<input type="submit" value="'.$button_text.'" class="btn-join" id="btn-join" />
						</form></div>';

									echo $join_button;
								} else {
									echo learndash_payment_buttons( $course );
								}
							} else {
								?>
							<div class="learndash_join_button <?php echo $btn_advance_class; ?>">
								<a href="<?php echo esc_url( $resume_link );?>" class="btn-advance"><?php echo $btn_advance_label; ?></a>
								</div><?php
							}

							if( apply_filters( 'learndash_login_modal', true, $course_id, $user_id ) && !is_user_logged_in() ):
								echo '<span class="ld-status">' . __( 'or ', 'buddyboss-theme' ) . '<a class="ld-login-text" href="' . esc_attr($login_url) . '">' . __( 'Login', 'buddyboss-theme' ) . '</a></span>';
							endif;

							if ( $course_price_type == 'paynow' ) {

								?><span class="bb-course-type bb-course-type-paynow"><?php echo bp_ld_sync()->bp_ld_prepare_price_str( array( 'code' => $paypal_settings['paypal_currency'], 'value' => $course_price ) ); ?></span><?php

							} else {

								$course_price_billing_p3 = get_post_meta( $course_id, 'course_price_billing_p3',  true );
								$course_price_billing_t3 = get_post_meta( $course_id, 'course_price_billing_t3',  true );
								if ( $course_price_billing_t3 == 'D' ) {
									$course_price_billing_t3 = 'day(s)';
								} elseif ( $course_price_billing_t3 == 'W' ) {
									$course_price_billing_t3 = 'week(s)';
								} elseif ( $course_price_billing_t3 == 'M' ) {
									$course_price_billing_t3 = 'month(s)';
								} elseif ( $course_price_billing_t3 == 'Y' ) {
									$course_price_billing_t3 = 'year(s)';
								}

								?>
								<span class="bb-course-type bb-course-type-subscribe">
						    <?php
						    if ( '' === $course_price && $course_price_type == 'subscribe' ) {
							    ?>
							    <span class="bb-course-type bb-course-type-subscribe"><?php _e( 'Free', 'buddyboss-theme' ); ?></span>
							    <?php
						    } else {
							    echo bp_ld_sync()->bp_ld_prepare_price_str( array( 'code'  => $paypal_settings['paypal_currency'],
							                                                                          'value' => $course_price
							    ) );
						    }


						    $recuring = ( '' === $course_price_billing_p3 ) ? 0 : $course_price_billing_p3;

						    //if ( !empty( $course_price_billing_p3 ) ) { ?>
                                <span class="course-bill-cycle"> / <?php echo $recuring . ' ' . $course_price_billing_t3; ?> </span><?php
									//} ?>
					    </span>
								<?php

							}

						} ?>
					</div>

					<?php
					$topics_count = 0;
					foreach ( $lesson_topics as $topics ) {
						if ( $topics ) {
							$topics_count += sizeof($topics);
						}
					}

					//course quizzes
					$course_quizzes = learndash_get_course_quiz_list( $course_id );
					$course_quizzes_count = sizeof($course_quizzes);

					//lessons quizzes
					$quizzes_count = 0;
					foreach ( $lessons as $lesson ) {
						$lesson_quizzes = learndash_get_lesson_quiz_list( $lesson['post']->ID );
						$course_quizzes_count += sizeof($lesson_quizzes);
					}

					//topics quizzes
					if (is_array($lesson_topics) || is_object($lesson_topics)) {
						foreach ( $lesson_topics as $lesson_topic ) {
							if (is_array($lesson_topic) || is_object($lesson_topic)) {
								foreach( $lesson_topic as $topic ){
									$topics_quizzes = learndash_get_lesson_quiz_list( $topic->ID );
									$course_quizzes_count += sizeof($topics_quizzes);
								}
							}
						}
					}
					?>
					<?php if ( sizeof($lessons) > 0 || $topics_count > 0 || $course_quizzes_count > 0 || $course_certificate ) { ?>
						<div class="bb-course-volume">
							<h4><?php echo LearnDash_Custom_Label::get_label( 'course' ); ?> <?php _e( 'Includes', 'buddyboss-theme' ); ?></h4>
							<ul class="bb-course-volume-list">
								<?php if ( sizeof($lessons) > 0 ) { ?>
									<li><i class="bb-icons bb-icon-book"></i><?php echo sizeof($lessons); ?> <?php echo sizeof($lessons) > 1 ? LearnDash_Custom_Label::get_label( 'lessons' ) : LearnDash_Custom_Label::get_label( 'lesson' ); ?></li>
								<?php } ?>
								<?php if ( $topics_count > 0 ) { ?>
									<li><i class="bb-icons bb-icon-text"></i><?php echo $topics_count; ?> <?php echo $topics_count != 1 ? LearnDash_Custom_Label::get_label( 'topics' ) : LearnDash_Custom_Label::get_label( 'topic' ); ?></li>
								<?php } ?>
								<?php if ( $course_quizzes_count > 0 ) { ?>
									<li><i class="bb-icons bb-icon-question-thin"></i><?php echo $course_quizzes_count; ?> <?php echo $course_quizzes_count != 1 ? LearnDash_Custom_Label::get_label( 'quizzes' ) : LearnDash_Custom_Label::get_label( 'quiz' ); ?></li>
								<?php } ?>
								<?php if ( $course_certificate ) { ?>
									<li><i class="bb-icons bb-icon-badge"></i><?php echo LearnDash_Custom_Label::get_label( 'course' ); ?> <?php _e( 'Certificate', 'buddyboss-theme' ); ?></li>
								<?php } ?>
							</ul>
						</div>
					<?php } ?>
				</div>
			</div>
		</div>

		<div class="bb-modal bb_course_video_details mfp-hide">
			<?php if ( '' != $course_video_embed ) {
				echo wp_oembed_get($course_video_embed);
			} ?>
		</div>
	</div>

	<?php

	/**
	 * Action to add custom content before the topic
	 *
	 * @since 3.0
	 */
	do_action( 'learndash-course-after', $course_id, $course_id, $user_id );

	learndash_get_template_part( 'modules/login-modal.php', array(), true ); ?>

</div>

