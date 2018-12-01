<?php
/**
 * File include Courses menu that is going to be added in the BuddyPress Group
 *
 * @since 1.0.1
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'BP_Group_Extension' ) ) {
	/**
	 * Class LearnDash_BuddyPress_Groups_Reports_Extension to display courses menu in BuddyPress groups.
	 *
	 * @since 1.0.1
	 *
	 */
	class LearnDash_BuddyPress_Groups_Reports_Extension extends BP_Group_Extension {

		/**
		 * Here you can see more customization of the config options
		 *
		 * since 1.0.1
		 */
		function __construct() {

			$args = array(
				'slug'          => 'course-reports',
				'name'          => __( 'Reports', 'ld_bp_groups_reports' ),
				'displayed_nav' => false,
			);
			parent::init( $args );

			$this->setup();

		}

		/**
		 * update all the value in class
		 */
		private function setup() {
			$this->associated_ld_group = ld_bp_groups_sync_check_associated_ld_group( buddypress()->groups->current_group->id );

			$this->loggedin_user_id = bp_loggedin_user_id();

			$this->group_id = bp_get_current_group_id();

			$this->current_group = groups_get_group( $this->group_id );

			$this->group_link = bp_get_group_permalink( $this->current_group );

			$this->associated_ld_group = ld_bp_groups_sync_check_associated_ld_group( $this->group_id );

			add_filter( 'ld_bp_groups_sync_courses_submenu', array( $this, 'sub_menu' ), 10, 1 );

			add_filter( 'bp_nouveau_get_classes', array( $this, 'sub_menu_class' ), 10, 1 );

			$this->members = groups_get_group_members();

			$custom_label = new LearnDash_Custom_Label();

			$menu = array(
				'course'      => array(
					'slug'  => 'course',
					'label' => $custom_label::get_label( 'course' ),
					'menu'  => false,
				),
				'courses'     => array(
					'slug'  => 'courses',
					'label' => $custom_label::get_label( 'courses' ),
					'menu'  => true,
				),
				'lesson'      => array(
					'slug'  => 'lesson',
					'label' => $custom_label::get_label( 'lesson' ),
					'menu'  => false,
				),
				'lessons'     => array(
					'slug'  => 'lessons',
					'label' => $custom_label::get_label( 'lessons' ),
					'menu'  => true,
				),
				'topic'       => array(
					'slug'  => 'topic',
					'label' => $custom_label::get_label( 'topic' ),
					'menu'  => false,
				),
				'topics'      => array(
					'slug'  => 'topics',
					'label' => $custom_label::get_label( 'topics' ),
					'menu'  => true,
				),
				'quiz'        => array(
					'slug'  => 'quiz',
					'label' => $custom_label::get_label( 'quiz' ),
					'menu'  => false,
				),
				'quizzes'     => array(
					'slug'  => 'quizzes',
					'label' => $custom_label::get_label( 'quizzes' ),
					'menu'  => true,
				),
				'essays'      => array(
					'slug'  => 'essays',
					'label' => __( 'Essays', 'ld_bp_groups_reports' ),
					'menu'  => true,
				),
				'assignments' => array(
					'slug'  => 'assignments',
					'label' => __( 'Assignments', 'ld_bp_groups_reports' ),
					'menu'  => true,
				),
			);

			$this->group_forum_ids = 0;
			if ( $this->current_group->enable_forum ) {
				$this->group_forum_ids = bbp_get_group_forum_ids( $this->group_id );
				if ( ! empty( $this->group_forum_ids ) ) {
					$menu['forums'] = array(
						'slug'  => 'forums',
						'label' => __( 'Forums', 'ld_bp_groups_reports' ),
						'menu'  => true,
					);
				}
			}

			$this->menus = $menu;

			$this->current_tab = empty( $_GET['menu'] ) ? 'courses' : (string) $_GET['menu'];

			$this->current_tab_label = $this->menus[ $this->current_tab ]['label'];

			$this->ls_bp_member_id = empty( $_GET['student_id'] ) ? 0 : absint( $_GET['student_id'] );

			$this->ls_bp_courses_id = empty( $_GET['courses_id'] ) ? 0 : absint( $_GET['courses_id'] );

			$this->not_applicable = __( 'N/A', 'ld_bp_groups_reports' );

			$this->status = __( 'Status', 'ld_bp_groups_reports' );

			$this->is_ajax = false;


			add_action( 'wp_ajax_ls_bp_group_courses_export_csv', array( $this, 'export_csv' ) );
		}

		/**
		 * Export CSV
		 */
		public function export_csv() {
			$this->is_ajax = true;
			$csv           = empty( $_POST['csv'] ) ? false : json_decode( base64_decode( $_POST['csv'] ) );
			$count         = count( $csv );
			$header        = false;
			if ( $count > 1 ) {
				$header = end( $csv );
				unset( $csv[ $count - 1 ] );
			}

			$response = array(
				'status' => false,
			);

			if ( empty( $csv ) ) {
				wp_send_json( $response );
			}

			$response['status'] = true;

			// create a file pointer connected to the output stream
			$output = fopen( 'php://output', 'w' );

			if ( $header ) {
				fputcsv( $output, $header );
			}

			//Loop through the array and add to the csv
			foreach ( $csv as $row ) {
				fputcsv( $output, $row );
			}
			exit();
		}

		/**
		 * get all the group member
		 *
		 * @return bool
		 */
		function get_member() {
			return $this->members['members'];
		}

		function get_member_count() {
			return $this->members['count'];
		}

		/**
		 * Check if current used is member or not
		 *
		 * @return bool
		 */
		function is_member() {
			return $this->current_group->is_member;
		}

		/**
		 * Check if current used is admin or not
		 *
		 * @return bool
		 */
		function is_admin() {
			return in_array( $this->loggedin_user_id, wp_list_pluck( $this->current_group->admins, 'user_id' ) );
		}

		/**
		 * Check if current used is mods or not
		 *
		 * @return bool
		 */
		function is_mod() {
			return in_array( $this->loggedin_user_id, wp_list_pluck( $this->current_group->mods, 'user_id' ) );
		}

		function is_admin_or_mod() {
			if ( $this->is_mod() || $this->is_admin() ) {
				return true;
			}

			return false;
		}

		/**
		 * Adding report submenu
		 *
		 * @param $sub_menus
		 *
		 * @return array
		 */
		function sub_menu( $sub_menus ) {
			$sub_menus[] = array(
				'link'  => $this->group_link,
				'slug'  => $this->slug,
				'label' => __( 'Reports', 'ld_bp_groups_reports' ),
			);

			return $sub_menus;
		}

		/**
		 * Hidding Reports tab
		 *
		 * @param $classes
		 *
		 * @return $classes
		 */
		function sub_menu_class( $classes ) {
			$bp_nouveau = bp_nouveau();
			$nav_item   = $bp_nouveau->current_nav_item;

			if ( $nav_item['slug'] === $this->slug ) {
				$classes .= ' bp-hide';
			}

			return $classes;
		}

		/**
		 * @param null $group_id
		 *
		 * since 1.0.1
		 */
		function display( $group_id = null ) {

			wp_enqueue_script( 'ld-bp-courses-reports' );

			ld_bp_groups_sync_courses_sub_menu();

			$display = empty( $_GET['menu'] ) ? 'courses' : (string) $_GET['menu'];

			?>

            <div class="ls-bp-group-reports">
				<?php
				$this->student_drop_down();

				$this->courses_drop_down();
				?>
            </div>

            <div class="ls-bp-group-courses-menu">
				<?php
				$this->courses_menu();
				?>
            </div>

            <div class="ls-bp-group-courses-link">
				<?php
				$this->courses_links();
				?>
            </div>
			<?php

			if ( method_exists( $this, $display ) ) {
				$this->$display();
			}

			?>
            <div class="ls-bp-group-courses-export-csv">
                <a href="#" class="export-csv"
                   data-menu="<?php echo $this->current_tab; ?>"
                   data-member_id="<?php echo $this->ls_bp_member_id; ?>"
                   data-courses_id="<?php echo $this->ls_bp_courses_id; ?>"
                   data-group_id="<?php echo $this->group_id; ?>"
                   data-filename="<?php printf( '%s-export-member-id-%s--courses-id-%s', $this->current_tab, $this->ls_bp_member_id, $this->ls_bp_courses_id ) ?>">
					<?php _e( 'Export CSV', 'ld_bp_groups_reports' ); ?>
                    <a id="ls_bp_group_courses_export_csv_download"></a>
                </a>

				<?php
				printf( "<input type='hidden' name='csv' class='csv' value='%s'>", base64_encode( json_encode( $this->csv ) ) );
				?>
            </div>
			<?php
		}

		public function bbp_include_all_forums( $return ) {
			return true;
		}

		/**
		 * Display forums HTML
		 */
		public function forums() {
			$label = $this->menus['forums']['label'];

			$this->total_points    = 0;
			$this->last_steps_id   = '';
			$this->failed_step     = '';
			$this->incomplete_step = '';
			$this->completed_step  = '';
			$this->course_step     = 0;
			$this->user_progress   = 0;
			$this->csv             = array();
			$completed_step        = sprintf( __( '%s Answered', 'ld_bp_groups_reports' ), $label );
			$incomplete_step       = sprintf( __( '%s Unanswered', 'ld_bp_groups_reports' ), $label );

			// Move user uploaded Assignements to Trash.
			$args = array(
				'post_type'      => 'topic',
				'post_parent'    => $this->group_forum_ids,
				'posts_per_page' => - 1,
				'author'         => $this->ls_bp_member_id,
			);

			add_filter( 'bbp_include_all_forums', array( $this, 'bbp_include_all_forums' ) );
			$topics = new WP_Query( $args );
			remove_filter( 'bbp_include_all_forums', array( $this, 'bbp_include_all_forums' ) );

			if ( $topics->have_posts() ) {
				while ( $topics->have_posts() ) {
					$topics->the_post();
					global $post;

					$question_title = get_the_title( get_the_ID() );
					$last_reply_id  = get_post_meta( get_the_ID(), '_bbp_last_reply_id', true );

					$csv_post_content = $post_content = $this->not_applicable;
					if ( $last_reply_id ) {
						$post_content     = wp_trim_words( wp_strip_all_tags( get_post_field( 'post_content', $last_reply_id ) ), 10 );
						$csv_post_content = get_post_field( 'post_content', $last_reply_id );
					}

					$date = date( 'd/m/Y', strtotime( $post->post_date ) );

					ob_start();
					?>
                    <tr>
						<?php
						printf( '<td><a href="%s">%s</a></td>', get_the_permalink( get_the_ID() ), $question_title );

						if ( $last_reply_id ) {
							printf( '<td><a href="%s">%s</a></td>', get_the_permalink( get_the_ID() ), $post_content );
						} else {
							printf( '<td>%s</td>', $this->not_applicable );
						}

						printf( '<td>%s</td>', $date );
						?>
                    </tr>
					<?php

					$html = ob_get_contents();
					ob_end_clean();

					$this->course_step ++;
					$reply_count = get_post_meta( get_the_ID(), '_bbp_reply_count', true );
					if ( ! empty( $reply_count ) ) {
						$this->user_progress ++;
						$this->completed_step .= $html;
						$status               = $completed_step;
					} else {
						$this->incomplete_step .= $html;
						$status                = $incomplete_step;
					}

					$csv   = $this->csv;
					$csv[] = array(
						$status,
						$question_title,
						$csv_post_content,
						$date,
					);

					$this->csv = $csv;

				}
			}
			wp_reset_postdata();

			?>
            <div class="ls-bp-group-forums-report">
                <div class="ls_bp_step_completed">
                    <p>
                        <span><?php printf( __( '%s Answered', 'ld_bp_groups_reports' ), $label ); ?></span>
                        <span><?php printf( __( '%s of %s', 'ld_bp_groups_reports' ), $this->user_progress, $this->course_step ); ?></span>
                    </p>
                    <progress value="<?php echo $this->user_progress; ?>"
                              max="<?php echo $this->course_step; ?>"></progress>
                </div>
            </div>

            <div class="ls-bp-group-forums-completed-steps">
				<?php
				echo $completed_step;
				?>
                <table>
					<?php
					$this->forums_table_head( $label, true );
					?>

                    <tbody>
					<?php
					echo $this->completed_step;
					?>
                    </tbody>
                </table>
            </div>

            <div class="ls-bp-group-forums-incomplete-steps">
				<?php
				echo $incomplete_step;
				?>
                <table>
					<?php
					$this->forums_table_head( $label );
					?>

                    <tbody>
					<?php
					echo $this->incomplete_step;
					?>
                    </tbody>
                </table>
            </div>
			<?php

		}

		/**
		 * Display assignments HTML
		 */
		public function assignments() {
			$label                 = $this->menus['assignments']['label'];
			$this->total_points    = 0;
			$this->last_steps_id   = '';
			$this->failed_step     = '';
			$this->incomplete_step = '';
			$this->completed_step  = '';
			$this->course_step     = 0;
			$this->user_progress   = 0;
			$this->csv             = array();
			$completed_step        = sprintf( __( 'Approved %s', 'ld_bp_groups_reports' ), $label );
			$incomplete_step       = sprintf( __( 'Submitted %s', 'ld_bp_groups_reports' ), $label );

			// Move user uploaded Assignements to Trash.
			$user_assignements_query_args = array(
				'post_type'  => 'sfwd-assignment',
				'nopaging'   => true,
				'author'     => $this->ls_bp_member_id,
				'meta_query' => array(
					array(
						'key'     => 'course_id',
						'value'   => $this->ls_bp_courses_id,
						'compare' => '=',
					),
				),
			);

			$user_assignements_query = new WP_Query( $user_assignements_query_args );
			if ( $user_assignements_query->have_posts() ) {
				while ( $user_assignements_query->have_posts() ) {
					$user_assignements_query->the_post();
					global $post;

					$question_title = get_the_title( get_the_ID() );


					$lesson_id = get_post_meta( get_the_ID(), 'lesson_id', true );

					$lesson_title = empty( $lesson_id ) ? $this->not_applicable : get_the_title( $lesson_id );

					$date = date( 'M j, Y ' . get_option( 'time_format' ), strtotime( $post->post_date ) );

					$comment_count = empty( $post->comment_count ) ? $this->not_applicable : $post->comment_count;

					$point = absint( get_post_meta( get_the_ID(), 'points', true ) );
					$point = ( empty( $point ) ? $this->not_applicable : $point );

					ob_start();
					?>
                    <tr>
						<?php
						printf( '<td><a href="%s">%s</a></td>', get_the_permalink( get_the_ID() ), $question_title );

						if ( empty( $lesson_id ) ) {
							printf( '<td>%s</td>', $lesson_title );
						} else {
							printf( '<td><a href="%s">%s</a></td>', learndash_get_step_permalink( $lesson_id, $this->ls_bp_courses_id ), $lesson_title );
						}

						printf( '<td>%s</td>', $date );

						if ( empty( $post->comment_count ) ) {
							printf( '<td>%s</td>', $comment_count );
						} else {
							printf( '<td><a href="%s">%s</a></td>', get_the_permalink( get_the_ID() ), $comment_count );
						}

						printf( '<td>%s</td>', $point );
						?>
                    </tr>
					<?php

					$html = ob_get_contents();
					ob_end_clean();

					$this->course_step ++;
					if ( get_post_meta( get_the_ID(), 'approval_status', true ) ) {
						$this->user_progress ++;
						$this->completed_step .= $html;
						$this->total_points   = $this->total_points + $point;
						$status               = $completed_step;
					} else {
						$this->incomplete_step .= $html;
						$status                = $incomplete_step;
					}

					$csv   = $this->csv;
					$csv[] = array(
						$status,
						$question_title,
						$lesson_title,
						$date,
						$comment_count,
						$point,
					);

					$this->csv = $csv;

				}
			}
			wp_reset_postdata();

			?>
            <div class="ls-bp-group-assignments-report">
                <div class="ls_bp_step_completed">
                    <p>
                        <span><?php printf( __( '%s Approved', 'ld_bp_groups_reports' ), $label ); ?></span>
                        <span><?php printf( __( '%s of %s', 'ld_bp_groups_reports' ), $this->user_progress, $this->course_step ); ?></span>
                    </p>
                    <progress value="<?php echo $this->user_progress; ?>"
                              max="<?php echo $this->course_step; ?>"></progress>
                </div>

                <div class="ls_bp_point_earn">
                    <p>
                        <span><?php _e( 'Points Earned', 'ld_bp_groups_reports' ); ?></span>
                        <span><?php echo $this->total_points; ?></span>
                    </p>
                </div>
            </div>

            <div class="ls-bp-group-assignments-completed-steps">
				<?php
				echo $completed_step;
				?>
                <table>
					<?php
					$this->assignments_table_head( true );
					?>

                    <tbody>
					<?php
					echo $this->completed_step;
					?>
                    </tbody>
                </table>
            </div>

            <div class="ls-bp-group-assignments-incomplete-steps">
				<?php
				echo $incomplete_step;
				?>
                <table>
					<?php
					$this->assignments_table_head();
					?>

                    <tbody>
					<?php
					echo $this->incomplete_step;
					?>
                    </tbody>
                </table>
            </div>
			<?php
		}

		/**
		 * Display essays HTML
		 */
		public function essays() {
			$label                 = $this->menus['essays']['label'];
			$this->total_points    = 0;
			$this->last_steps_id   = '';
			$this->failed_step     = '';
			$this->incomplete_step = '';
			$this->completed_step  = '';
			$this->course_step     = 0;
			$this->user_progress   = 0;
			$this->csv             = array();
			$completed_step        = sprintf( __( 'Approved %s', 'ld_bp_groups_reports' ), $label );
			$incomplete_step       = sprintf( __( 'Submitted %s', 'ld_bp_groups_reports' ), $label );

			// Move user uploaded essays to Trash.
			$user_essays_query_args = array(
				'post_type'  => 'sfwd-essays',
				'nopaging'   => true,
				'author'     => $this->ls_bp_member_id,
				'meta_query' => array(
					array(
						'key'     => 'course_id',
						'value'   => $this->ls_bp_courses_id,
						'compare' => '=',
					),
				),
			);

			$user_essays_query = new WP_Query( $user_essays_query_args );
			if ( $user_essays_query->have_posts() ) {
				while ( $user_essays_query->have_posts() ) {
					$user_essays_query->the_post();
					global $post;

					$question_title = get_the_title( get_the_ID() );

					$lesson_id    = get_post_meta( get_the_ID(), 'lesson_id', true );
					$lesson_title = $this->not_applicable;
					if ( ! empty( $lesson_id ) ) {
						$lesson_title = get_the_title( $lesson_id );
					}

					$point = 0;

					$quiz_title   = $this->not_applicable;
					$quiz_id      = get_post_meta( get_the_ID(), 'quiz_id', true );
					$quiz_post_id = false;
					if ( ! empty( $quiz_id ) ) {
						$quiz_post_id = learndash_get_quiz_id_by_pro_quiz_id( $quiz_id );
						if ( ! empty( $quiz_post_id ) ) {
							$quiz_title = get_the_title( $quiz_post_id );
						}
					}

					$question_id = get_post_meta( get_the_ID(), 'question_id', true );

					if ( ! empty( $quiz_id ) ) {
						$questionMapper = new WpProQuiz_Model_QuestionMapper();
						$question       = $questionMapper->fetchById( intval( $question_id ), null );
						$point          = $question->getPoints();

					}

					$point         = ( empty( $point ) ? $this->not_applicable : $point );
					$date          = date( 'M j, Y ' . get_option( 'time_format' ), strtotime( $post->post_date ) );
					$comment_count = empty( $post->comment_count ) ? $this->not_applicable : $post->comment_count;
					ob_start();
					?>
                    <tr>
						<?php
						printf( '<td><a href="%s">%s</a></td>', get_the_permalink( get_the_ID() ), $question_title );


						if ( $lesson_id ) {
							printf( '<td><a href="%s">%s</a></td>', learndash_get_step_permalink( $lesson_id, $this->ls_bp_courses_id ), $lesson_title );
						} else {
							printf( '<td>%s</td>', $lesson_title );
						}

						if ( $quiz_post_id ) {
							printf( '<td><a href="%s">%s</a></td>', learndash_get_step_permalink( $quiz_post_id, $this->ls_bp_courses_id ), $quiz_title );
						} else {
							printf( '<td>%s</td>', $quiz_title );
						}
						printf( '<td>%s</td>', $date );


						if ( empty( $post->comment_count ) ) {
							printf( '<td>%s</td>', $comment_count );
						} else {
							printf( '<td><a href="%s">%s</a></td>', get_the_permalink( get_the_ID() ), $comment_count );
						}
						printf( '<td>%s</td>', $point );
						?>
                    </tr>
					<?php

					$html = ob_get_contents();
					ob_end_clean();

					$this->course_step ++;
					if ( ! empty( $post->post_status ) && 'graded' === $post->post_status ) {
						$this->user_progress ++;
						$this->completed_step .= $html;
						$this->total_points   = $this->total_points + $point;
						$status               = $completed_step;
					} else {
						$this->incomplete_step .= $html;
						$status                = $incomplete_step;
					}

					$csv   = $this->csv;
					$csv[] = array(
						$status,
						$question_title,
						$lesson_title,
						$quiz_title,
						$date,
						$comment_count,
						$point,
					);

					$this->csv = $csv;

				}
			}
			wp_reset_postdata();

			?>
            <div class="ls-bp-group-essays-report">
                <div class="ls_bp_step_completed">
                    <p>
                        <span><?php printf( __( '%s Approved', 'ld_bp_groups_reports' ), $label ); ?></span>
                        <span><?php printf( __( '%s of %s', 'ld_bp_groups_reports' ), $this->user_progress, $this->course_step ); ?></span>
                    </p>
                    <progress value="<?php echo $this->user_progress; ?>"
                              max="<?php echo $this->course_step; ?>"></progress>
                </div>

                <div class="ls_bp_point_earn">
                    <p>
                        <span><?php _e( 'Points Earned', 'ld_bp_groups_reports' ); ?></span>
                        <span><?php echo $this->total_points; ?></span>
                    </p>
                </div>
            </div>

            <div class="ls-bp-group-essays-completed-steps">
				<?php
				echo $completed_step;
				?>
                <table>
					<?php
					$this->essays_table_head( true );
					?>

                    <tbody>
					<?php
					echo $this->completed_step;
					?>
                    </tbody>
                </table>
            </div>

            <div class="ls-bp-group-essays-incomplete-steps">
				<?php
				echo $incomplete_step;
				?>
                <table>
					<?php
					$this->essays_table_head();
					?>

                    <tbody>
					<?php
					echo $this->incomplete_step;
					?>
                    </tbody>
                </table>
            </div>
			<?php
		}

		/**
		 * Display quizzes HTML
		 */
		public function quizzes() {
			$label     = $this->menus['quizzes']['label'];
			$new_label = $this->menus['quiz']['label'];


			$this->total_points          = 0;
			$this->last_steps_id         = '';
			$this->failed_step           = '';
			$this->incomplete_step       = '';
			$this->completed_step        = '';
			$this->course_step           = 0;
			$this->user_progress         = 0;
			$this->csv                   = array();
			$this->completed_step_label  = sprintf( __( 'Passed %s', 'ld_bp_groups_reports' ), $label );
			$this->failed_step_label     = sprintf( __( 'Failed %s', 'ld_bp_groups_reports' ), $label );
			$this->incomplete_step_label = sprintf( __( 'Incomplete %s', 'ld_bp_groups_reports' ), $label );

			$this->courses_table_body( $this->ld_course_steps_object->get_steps(), 'sfwd-quiz' );
			?>

            <div class="ls-bp-group-quizzes-report">
                <div class="ls_bp_step_completed">
                    <p>
                        <span><?php printf( __( '%s Passed', 'ld_bp_groups_reports' ), $label ); ?></span>
                        <span><?php printf( __( '%s of %s', 'ld_bp_groups_reports' ), $this->user_progress, $this->course_step ); ?></span>
                    </p>
                    <progress value="<?php echo $this->user_progress; ?>"
                              max="<?php echo $this->course_step; ?>"></progress>
                </div>

                <div class="ls_bp_point_earn">
                    <p>
                        <span><?php _e( 'Points Earned', 'ld_bp_groups_reports' ); ?></span>
                        <span><?php echo $this->total_points; ?></span>
                    </p>
                </div>
            </div>

            <div class="ls-bp-group-quizzes-completed-steps">
				<?php
				echo $this->completed_step_label;
				?>
                <table>
					<?php
					$this->quizzes_table_head( $new_label, true );
					?>

                    <tbody>
					<?php
					echo $this->completed_step;
					?>
                    </tbody>
                </table>
            </div>

            <div class="ls-bp-group-quizzes-failed-steps">
				<?php
				echo $this->failed_step_label;
				?>
                <table>
					<?php
					$this->quizzes_table_head( $new_label );
					?>

                    <tbody>
					<?php
					echo $this->failed_step;
					?>
                    </tbody>
                </table>
            </div>

            <div class="ls-bp-group-quizzes-incomplete-steps">
				<?php
				echo $this->incomplete_step_label;
				?>
                <table>
					<?php
					$this->quizzes_table_head( $new_label );
					?>

                    <tbody>
					<?php
					echo $this->incomplete_step;
					?>
                    </tbody>
                </table>
            </div>
			<?php
		}

		/**
		 * Display topics HTML
		 */
		public function topics() {
			$this->basic_html( 'topics', $this->menus['topics']['label'], 'sfwd-topic', 'topic_table_head' );
		}

		/**
		 * Display lessons HTML
		 */
		public function lessons() {

			$this->basic_html( 'lessons', $this->menus['lessons']['label'], 'sfwd-lessons' );
		}

		/**
		 * Display courses HTML
		 */
		public function courses() {
			$this->basic_html( 'courses', __( 'Steps', 'ld_bp_groups_reports' ) );
		}

		public function basic_html( $slug, $label, $display = 'all', $table_head = 'courses_table_head' ) {

			$this->incomplete_step       = '';
			$this->last_steps_id         = '';
			$this->completed_step        = '';
			$this->failed_step           = '';
			$this->course_step           = 0;
			$this->user_progress         = 0;
			$this->total_points          = 0;
			$this->csv                   = array();
			$this->completed_step_label  = sprintf( __( 'Completed %s', 'ld_bp_groups_reports' ), $label );
			$this->incomplete_step_label = sprintf( __( 'Incomplete %s', 'ld_bp_groups_reports' ), $label );

			$this->courses_table_body( $this->ld_course_steps_object->get_steps(), $display );
			?>
            <div class="ls-bp-group-<?php echo $slug; ?>-report">
                <div class="ls_bp_step_completed">
                    <p>
                        <span><?php printf( __( '%s Completed', 'ld_bp_groups_reports' ), $label ); ?></span>
                        <span><?php printf( __( '%s of %s', 'ld_bp_groups_reports' ), $this->user_progress, $this->course_step ); ?></span>
                    </p>
                    <progress value="<?php echo $this->user_progress; ?>"
                              max="<?php echo $this->course_step; ?>"></progress>
                </div>

                <div class="ls_bp_point_earn">
                    <p>
                        <span><?php _e( 'Points Earned', 'ld_bp_groups_reports' ); ?></span>
                        <span><?php echo $this->total_points; ?></span>
                    </p>
                </div>
            </div>

            <div class="ls-bp-group-<?php echo $slug; ?>-completed-steps">
				<?php
				echo $this->completed_step_label;
				?>
                <table>
					<?php
					$this->$table_head( $label, true );
					?>

                    <tbody>
					<?php
					echo $this->completed_step;
					?>
                    </tbody>
                </table>
            </div>

            <div class="ls-bp-group-<?php echo $slug; ?>-incomplete-steps">
				<?php
				echo $this->incomplete_step_label;
				?>
                <table>
					<?php
					$this->$table_head( $label );
					?>

                    <tbody>
					<?php
					echo $this->incomplete_step;
					?>
                    </tbody>
                </table>
            </div>
			<?php
		}

		function student_drop_down() {
			$selected_student      = $this->ls_bp_member_id;
			$selected_student_name = '';

			if ( $this->is_admin_or_mod() ) {

				if ( $this->get_member_count() < 1 ) {
					_e( 'No Member associated to the Group', 'ld_bp_groups_reports' );
				} else {

					$style = sprintf( 'style=display:%s;', ( 1 == $this->get_member() ) ? 'none' : 'block' );

					printf( '<label for="ls_bp_member_id" %s>%s</label>', $style, __( 'Select Student', 'ld_bp_groups_reports' ) );
					?>
                    <select name="student_id" class="ls_bp_member_id" id="ls_bp_member_id" <?php echo $style; ?>>
						<?php
						$count = 0;
						foreach ( $this->get_member() as $member ) {

							$selected = '';
							if ( empty( $count ) && empty( $selected_student ) ) {
								$selected              = 'selected';
								$selected_student      = $member->ID;
								$selected_student_name = $member->display_name;
								$count ++;
							} elseif ( ! empty( $selected_student ) && $member->ID == $selected_student ) {
								$selected              = 'selected';
								$selected_student      = $member->ID;
								$selected_student_name = $member->display_name;
							}
							printf( '<option value="%s" %s>%s</option>', $member->ID, $selected, $member->display_name );
							$count ++;
						}
						?>
                    </select>
					<?php
				}
			} else {
				printf( '<input type="hidden" value="%s" name="ls_bp_member_id" class="ls_bp_member_id" >', $this->loggedin_user_id );
				$selected_student      = $this->loggedin_user_id;
				$selected_student_name = get_user_meta( $this->loggedin_user_id, 'nickname', true );
			}

			$this->ls_bp_member_id   = $selected_student;
			$this->ls_bp_member_name = $selected_student_name;
		}

		function courses_drop_down() {

			$selected_course = $this->ls_bp_courses_id;

			$selected_course_name = '';

			$this->courses = learndash_group_enrolled_courses( $this->associated_ld_group, true );
			$total_courses = count( $this->courses );

			if ( $total_courses > 0 ) {
				$style = sprintf( 'style=display:%s;', ( 1 === $total_courses ) ? 'none' : 'block' );

				$select_course = sprintf( __( 'Select %s', 'ld_bp_groups_reports' ), $this->menus['course']['label'] );
				printf( '<label for="ls_bp_courses_id" %s>%s</label>', $style, $select_course );
				?>
                <select name="courses_id" class="ls_bp_courses_id" id="ls_bp_courses_id" <?php echo $style; ?>>
					<?php
					$count = 0;
					foreach ( $this->courses as $course ) {
						$title = get_the_title( $course );

						$selected = '';
						if ( empty( $count ) && empty( $selected_course ) ) {
							$selected             = 'selected';
							$selected_course      = $course;
							$selected_course_name = $title;
							$count ++;
						} elseif ( ! empty( $selected_course ) && $course == $selected_course ) {
							$selected             = 'selected';
							$selected_course      = $course;
							$selected_course_name = $title;
						}
						printf( '<option value="%s" %s>%s</option>', $course, $selected, $title );
					}
					?>
                </select>
				<?php
			} else {
				_e( 'No Courses associated to the Group', 'ld_bp_groups_reports' );
			}

			$this->ls_bp_courses_id       = $selected_course;
			$this->ls_bp_courses_name     = $selected_course_name;
			$this->ld_course_steps_object = LDLMS_Factory_Post::course_steps( $this->ls_bp_courses_id );
		}

		function courses_menu() {
			$link = $this->group_link . $this->slug;

			?>
            <nav class="bp-navs bp-subnavs ls-bp-courses-menu" id="ls-bp-courses-menu" role="navigation">
                <ul class="subnav">
					<?php
					foreach ( $this->menus as $menu ) {
						if ( $menu['menu'] ) {

							$selected = $menu['slug'] === $this->current_tab ? 'current selected' : '';

							$this->current_tab_slug = $menu['slug'];
							?>
                            <li class="<?php echo $selected; ?>">
								<?php
								printf( '<a href="%s?menu=%s&courses_id=%s&student_id=%s" url="%s?menu=%s">%s</a>', $link, $menu['slug'], $this->ls_bp_courses_id, $this->ls_bp_member_id, $link, $menu['slug'], $menu['label'] );
								?>
                            </li>
							<?php
						}
					}
					?>
                </ul>
            </nav>
			<?php
		}

		function courses_links() {
			if ( $this->is_admin_or_mod() ) {
				printf(
					'<p><span class="user_link"><a href="%s">%s</a> </span>: <span class="course_link"><a href="%s">%s</a> </span></p>',
					bp_core_get_userlink( $this->ls_bp_member_id, false, true ),
					$this->ls_bp_member_name,
					get_permalink( $this->ls_bp_courses_id ),
					$this->ls_bp_courses_name
				);
			} else {
				printf(
					'<p><span class="user_link">%s %s</span>: <span class="course_link"><a href="%s">%s</a> </span></p>',
					$this->current_tab_label,
					__( 'Progress', 'ld_bp_groups_reports' ),
					get_permalink( $this->ls_bp_courses_id ),
					$this->ls_bp_courses_name
				);
			}
		}

		function courses_table_body( $steps = array(), $display = 'all' ) {
			if ( ! empty( $steps ) ) {
				foreach ( $steps as $steps_type => $steps_items ) {

					if ( ! empty( $steps_items ) ) {
						foreach ( $steps_items as $steps_id => $steps_set ) {
							$current_csv_data = array();

							if ( 'sfwd-lessons' == $steps_type ) {
								$this->last_steps_id = $steps_id;
							}

							if ( ( $steps_type == $display ) || 'all' == $display ) {

								// We need to update the activity database records for this quiz_id
								$activity_query_args = array(
									'post_ids'      => $steps_id,
									'user_ids'      => $this->ls_bp_member_id,
									'course_ids'    => $this->ls_bp_courses_id,
									'orderby_order' => 'activity_id DESC',
								);

								$completed = true;
								if ( 'sfwd-lessons' === $steps_type ) {
									$completed = learndash_is_lesson_notcomplete( $this->ls_bp_member_id, array( $steps_id ), $this->ls_bp_courses_id );
								} elseif ( 'sfwd-quiz' === $steps_type ) {
									$completed = learndash_is_quiz_complete( $this->ls_bp_member_id, $steps_id, $this->ls_bp_courses_id );
								}

								if ( $completed ) {
									$activity_query_args['activity_status'] = array( 'COMPLETED' );
								}

								$activity            = learndash_reports_get_activity( $activity_query_args );
								$started_formatted   = empty( $activity['results'][0]->activity_started_formatted ) ? false : $activity['results'][0]->activity_started_formatted;
								$completed_formatted = empty( $activity['results'][0]->activity_completed_formatted ) ? false : $activity['results'][0]->activity_completed_formatted;

								$time_diff          = $this->not_applicable;
								$activity_completed = false;
								if ( ! empty( $activity['results'][0]->activity_started ) && ! empty( $activity['results'][0]->activity_completed ) && $completed ) {
									$time_diff          = human_time_diff( $activity['results'][0]->activity_started, $activity['results'][0]->activity_completed );
									$activity_completed = true;
								}

								$point      = 0;
								$percentage = 0;
								if ( 'sfwd-quiz' === $steps_type && ! empty( $activity['results'][0]->activity_meta ) ) {
									$point      = $point + $activity['results'][0]->activity_meta['points'];
									$percentage = $activity['results'][0]->activity_meta['percentage'];
								} elseif (
									'on' === learndash_get_setting( $steps_id, 'lesson_assignment_upload' )
									&& 'on' == learndash_get_setting( $steps_id, 'lesson_assignment_points_enabled' )
									&& 0 < absint( learndash_get_setting( $steps_id, 'lesson_assignment_points_amount' ) )
								) {
									$assignments = learndash_get_user_assignments( $steps_id, $this->ls_bp_member_id, $this->ls_bp_courses_id );
									foreach ( $assignments as $assignment ) {
										if ( get_post_meta( $assignment->ID, 'approval_status', true ) ) {
											$point = $point + absint( get_post_meta( $assignment->ID, 'points', true ) );
										}
									}
								}

								$completed_formatted = ( empty( $completed_formatted ) ? $this->not_applicable : $completed_formatted );
								$started_formatted   = ( empty( $started_formatted ) ? $this->not_applicable : $started_formatted );
								$point               = ( empty( $point ) ? $this->not_applicable : $point );

								ob_start();
								if ( 'sfwd-quiz' === $display ) {
									$quizzes_count = count( $activity['results'] );
									?>
                                    <tr>
										<?php
										$quiz_label    = sprintf( '%s %s', $this->menus['quiz']['label'], get_the_title( $steps_id ) );
										$quizzes_count = ( empty( $quizzes_count ) ? $this->not_applicable : $quizzes_count );
										$percentage    = ( empty( $percentage ) ? $this->not_applicable : sprintf( '%s%s', $percentage, '%' ) );

										printf( '<td><a href="%s">%s</a></td>', learndash_get_step_permalink( $steps_id, $this->ls_bp_courses_id ), $quiz_label );
										printf( '<td>%s</td>', $completed_formatted );
										printf( '<td>%s</td>', $quizzes_count );
										printf( '<td>%s</td>', $percentage );
										printf( '<td>%s</td>', $time_diff );
										printf( '<td>%s</td>', $point )
										?>
                                    </tr>
									<?php

									$current_csv_data = array(
										$quiz_label,
										$completed_formatted,
										$quizzes_count,
										$percentage,
										$time_diff,
										$point
									);
								} elseif ( 'sfwd-topic' === $display ) {
									$topic_label         = sprintf( '%s %s', $this->menus['topic']['label'], get_the_title( $steps_id ) );
									$last_steps_id_title = get_the_title( $this->last_steps_id );
									?>
                                    <tr>
										<?php
										printf( '<td><a href="%s">%s</a></td>', learndash_get_step_permalink( $steps_id, $this->ls_bp_courses_id ), $topic_label );
										printf( '<td><a href="%s">%s</a></td>', learndash_get_step_permalink( $this->last_steps_id, $this->ls_bp_courses_id ), $last_steps_id_title );
										printf( '<td>%s</td>', $completed_formatted );
										printf( '<td>%s</td>', $time_diff );
										printf( '<td>%s</td>', $point )
										?>
                                    </tr>
									<?php
									$current_csv_data = array(
										$topic_label,
										$last_steps_id_title,
										$completed_formatted,
										$time_diff,
										$point
									);
								} else {

									if ( 'sfwd-quiz' == $steps_type ) {
										$label = $this->menus['quiz']['label'];
									} elseif ( 'sfwd-topic' == $steps_type ) {
										$label = $this->menus['topic']['label'];
									} else {
										$label = $this->menus['lesson']['label'];
									}

									$label = sprintf( '%s %s', $label, get_the_title( $steps_id ) );
									?>
                                    <tr>
										<?php
										printf( '<td><a href="%s">%s</a></td>', learndash_get_step_permalink( $steps_id, $this->ls_bp_courses_id ), $label );
										printf( '<td>%s</td>', $started_formatted );
										printf( '<td>%s</td>', $completed_formatted );
										printf( '<td>%s</td>', $time_diff );
										printf( '<td>%s</td>', $point )
										?>
                                    </tr>
									<?php
									$current_csv_data = array(
										$label,
										$started_formatted,
										$completed_formatted,
										$time_diff,
										$point
									);
								}

								$html = ob_get_contents();
								ob_end_clean();

								$this->course_step ++;
								if ( empty( $activity_completed ) ) {
									if ( 'sfwd-quiz' === $display && isset( $activity['results'][0]->activity_meta['pass'] ) ) {
										$this->failed_step .= $html;
										$status            = $this->failed_step_label;
									} else {
										$this->incomplete_step .= $html;
										$status                = $this->incomplete_step_label;
									}
								} else {
									$this->user_progress ++;
									$this->completed_step .= $html;
									$this->total_points   = $this->total_points + $point;
									$status               = $this->completed_step_label;
								}
							}

							if ( ! empty( $current_csv_data ) ) {
								array_unshift( $current_csv_data, $status );
								$csv       = (array) $this->csv;
								$csv[]     = $current_csv_data;
								$this->csv = $csv;
							}

							if ( ! empty( $steps_set ) ) {
								$this->courses_table_body( $steps_set, $display, true );
							}
						}
					}

				}
			}
		}

		function courses_table_head( $label, $header = false ) {
			$columns2 = __( 'Start Date', 'ld_bp_groups_reports' );
			$columns3 = __( 'Completion Date', 'ld_bp_groups_reports' );
			$columns4 = __( 'Time Spent', 'ld_bp_groups_reports' );
			$columns5 = __( 'Points Earned', 'ld_bp_groups_reports' );
			?>
            <thead>
            <tr>
                <th><?php echo $label; ?></th>
                <th><?php _e( 'Start Date', 'ld_bp_groups_reports' ); ?></th>
                <th><?php _e( 'Completion Date', 'ld_bp_groups_reports' ); ?></th>
                <th><?php _e( 'Time Spent', 'ld_bp_groups_reports' ); ?></th>
                <th><?php _e( 'Points Earned', 'ld_bp_groups_reports' ); ?></th>
            </tr>
            </thead>
			<?php

			if ( $header ) {
				$csv       = $this->csv;
				$csv[]     = array(
					$this->status,
					$label,
					$columns2,
					$columns3,
					$columns4,
					$columns5,
				);
				$this->csv = $csv;
			}
		}

		function topic_table_head( $label, $header = false ) {
			$parent_label = $this->menus['lessons']['label'];
			$columns3     = __( 'Completion Date', 'ld_bp_groups_reports' );
			$columns4     = __( 'Time Spent', 'ld_bp_groups_reports' );
			$columns5     = __( 'Points Earned', 'ld_bp_groups_reports' );
			?>
            <thead>
            <tr>
                <th><?php echo $label; ?></th>
                <th><?php echo $parent_label; ?></th>
                <th><?php echo $columns3; ?></th>
                <th><?php echo $columns4; ?></th>
                <th><?php echo $columns5; ?></th>
            </tr>
            </thead>
			<?php
			if ( $header ) {
				$csv       = $this->csv;
				$csv[]     = array(
					$this->status,
					$label,
					$parent_label,
					$columns3,
					$columns4,
					$columns5,
				);
				$this->csv = $csv;
			}
		}

		function quizzes_table_head( $label, $header = false ) {
			$columns2 = __( 'Completion Date', 'ld_bp_groups_reports' );
			$columns3 = __( 'No. of Attempts', 'ld_bp_groups_reports' );
			$columns4 = __( 'Score', 'ld_bp_groups_reports' );
			$columns5 = __( 'Time Spent', 'ld_bp_groups_reports' );
			$columns6 = __( 'Points Earned', 'ld_bp_groups_reports' );
			?>
            <thead>
            <tr>
                <th><?php echo $label; ?></th>
                <th><?php echo $columns2; ?></th>
                <th><?php echo $columns3; ?></th>
                <th><?php echo $columns4; ?></th>
                <th><?php echo $columns5; ?></th>
                <th><?php echo $columns6; ?></th>
            </tr>
            </thead>
			<?php

			if ( $header ) {
				$csv       = $this->csv;
				$csv[]     = array(
					$this->status,
					$label,
					$columns2,
					$columns3,
					$columns4,
					$columns5,
					$columns6,
				);
				$this->csv = $csv;
			}
		}

		function essays_table_head( $header = false ) {
			$label        = $this->menus['essays']['label'];
			$lesson_label = $this->menus['lesson']['label'];
			$quiz_label   = $this->menus['quiz']['label'];

			$columns1 = sprintf( __( '%s Question', 'ld_bp_groups_reports' ), $label );
			$columns4 = __( 'Date Completed', 'ld_bp_groups_reports' );
			$columns5 = __( 'Comments', 'ld_bp_groups_reports' );
			$columns6 = __( 'Points Earned', 'ld_bp_groups_reports' );
			?>
            <thead>
            <tr>
                <th><?php echo $columns1; ?></th>
                <th><?php echo $lesson_label; ?></th>
                <th><?php echo $quiz_label; ?></th>
                <th><?php echo $columns4; ?></th>
                <th><?php echo $columns5; ?></th>
                <th><?php echo $columns6; ?></th>
            </tr>
            </thead>
			<?php

			if ( $header ) {
				$csv       = $this->csv;
				$csv[]     = array(
					$this->status,
					$columns1,
					$lesson_label,
					$quiz_label,
					$columns4,
					$columns5,
					$columns6,
				);
				$this->csv = $csv;
			}

		}

		function assignments_table_head( $header = false ) {
			$label        = $this->menus['assignments']['label'];
			$lesson_label = $this->menus['lesson']['label'];
			$columns3     = __( 'Updated', 'ld_bp_groups_reports' );
			$columns4     = __( 'Comments', 'ld_bp_groups_reports' );
			$columns5     = __( 'Points Earned', 'ld_bp_groups_reports' );
			?>
            <thead>
            <tr>
                <th><?php echo $label; ?></th>
                <th><?php echo $lesson_label; ?></th>
                <th><?php echo $columns3; ?></th>
                <th><?php echo $columns4; ?></th>
                <th><?php echo $columns5; ?></th>
            </tr>
            </thead>
			<?php

			if ( $header ) {
				$csv       = $this->csv;
				$csv[]     = array(
					$this->status,
					$label,
					$lesson_label,
					$columns3,
					$columns4,
					$columns5,
				);
				$this->csv = $csv;
			}
		}

		function forums_table_head( $label, $header = false ) {
			$columns1 = sprintf( __( '%s Topic', 'ld_bp_groups_reports' ), $label );
			$columns2 = __( 'Reply', 'ld_bp_groups_reports' );
			$columns3 = __( 'Date Posted', 'ld_bp_groups_reports' );
			?>
            <thead>
            <tr>
                <th><?php echo $columns1; ?></th>
                <th><?php echo $columns2; ?></th>
                <th><?php echo $columns3; ?></th>
            </tr>
            </thead>
			<?php

			if ( $header ) {
				$csv       = $this->csv;
				$csv[]     = array(
					$this->status,
					$columns1,
					$columns2,
					$columns3,
				);
				$this->csv = $csv;
			}

		}
	}

	/**
	 * Add Courses menu in BuddyPress Group Menu
	 *
	 * @since 1.0.1
	 */
	function ld_bp_groups_add_courses_reports_menu() {
		if (
			function_exists( 'bp_is_group' )
			&& bp_is_group()
			&& ld_bp_groups_sync_check_associated_ld_group( buddypress()->groups->current_group->id )
			&& ld_bp_groups_reports_get_settings( 'enable_group_reports' )
		) {
			$report_access = ld_bp_groups_reports_get_settings( 'report_access', false );

			$member_id    = bp_loggedin_user_id();
			$admin_member = wp_list_pluck( buddypress()->groups->current_group->admins, 'user_id' );
			$mods_member  = wp_list_pluck( buddypress()->groups->current_group->mods, 'user_id' );

			if (
				( current_user_can( 'administrator' ) )
				|| ( in_array( 'admin', $report_access ) && in_array( $member_id, $admin_member ) )
				|| ( in_array( 'moderator', $report_access ) && in_array( $member_id, $mods_member ) )
				|| ( in_array( 'member', $report_access ) && buddypress()->groups->current_group->is_member && ! in_array( $member_id, array_unique( array_merge( $admin_member, $mods_member ), SORT_REGULAR ) ) )
			) {
				bp_register_group_extension( 'LearnDash_BuddyPress_Groups_Reports_Extension' );
			}
		}
	}

	add_action( 'bp_init', 'ld_bp_groups_add_courses_reports_menu' );
}
