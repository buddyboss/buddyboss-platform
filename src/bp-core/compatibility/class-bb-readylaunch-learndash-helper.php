<?php
/**
 * ReadyLaunch LearnDash Helper Functions
 *
 * @since   BuddyBoss 2.9.00
 * @package BuddyBoss\Core
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

// Prevent duplicate class declarations.
if ( class_exists( 'BB_Readylaunch_Learndash_Helper' ) ) {
	return;
}

/**
 * ReadyLaunch LearnDash Helper Class
 *
 * This class provides helper functions for LearnDash integration
 * when using ReadyLaunch templates without BuddyBoss theme.
 *
 * @since BuddyBoss 2.9.00
 */
if ( ! class_exists( 'BB_Readylaunch_Learndash_Helper' ) ) {

	/**
	 * LearnDash helper class.
	 *
	 * @since BuddyBoss 2.9.00
	 */
	class BB_Readylaunch_Learndash_Helper {

		/**
		 * The single instance of the class.
		 *
		 * @since BuddyBoss 2.9.00
		 * @var BB_Readylaunch_Learndash_Helper
		 */
		protected static $instance = null;

		/**
		 * Main BB_Readylaunch_Learndash_Helper Instance.
		 *
		 * Ensures only one instance of BB_Readylaunch_Learndash_Helper is loaded or can be loaded.
		 *
		 * @since BuddyBoss 2.9.00
		 * @static
		 * @return BB_Readylaunch_Learndash_Helper - Main instance.
		 */
		public static function instance() {
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		/**
		 * Constructor.
		 *
		 * @since BuddyBoss 2.9.00
		 */
		public function __construct() {
			remove_all_filters( 'learndash_template' );
			add_filter( 'learndash_template', array( $this, 'bb_rl_override_learndash_template_path' ), 99999, 5 );

			// LearnDash stylesheets.
			add_action( 'wp_enqueue_scripts', array( $this, 'bb_readylaunch_learndash_enqueue_styles' ), 10 );

			// Add actions for archive template.
			add_action( 'bb_rl_layout_before', array( $this, 'bb_rl_learndash_layout_before' ) );
			add_action( 'bb_rl_layout_after', array( $this, 'bb_rl_learndash_layout_after' ) );
			add_action( 'bb_rl_layout_before_loop', array( $this, 'bb_rl_learndash_before_loop' ) );
			add_action( 'bb_rl_layout_after_loop', array( $this, 'bb_rl_learndash_after_loop' ) );
			add_action( 'bb_rl_layout_no_posts', array( $this, 'bb_rl_learndash_no_posts' ) );

			// Add pre_get_posts filter for course filtering.
			add_action( 'pre_get_posts', array( $this, 'bb_rl_filter_courses_query' ) );
			add_filter( 'learndash_lesson_row_class', array( $this, 'bb_rl_learndash_lesson_row_class' ), 10, 2 );
			add_filter( 'learndash-topic-row-class', array( $this, 'bb_rl_learndash_topic_row_class' ), 10, 2 );
			add_filter( 'learndash_quiz_row_classes', array( $this, 'bb_rl_learndash_quiz_row_classes' ), 10, 2 );

			add_filter( 'buddyboss_learndash_content', array( $this, 'bb_rl_learndash_content' ), 10, 2 );
			add_action( 'learndash_update_user_activity', array( $this, 'bb_rl_flush_ld_courses_progress_cache' ), 9999, 1 );
			add_filter( 'learndash_content_tabs', array( $this, 'bb_rl_learndash_content_tabs' ), 10, 4 );

			remove_action( 'learndash_course_reviews_review_reply', 'bb_output_review_reply_template', 9, 1 );
			add_action( 'learndash_course_reviews_review_reply', array( $this, 'bb_rl_output_review_reply_template' ), 9, 1 );

			add_action( 'add_meta_boxes', array( $this, 'bb_rl_learndash_add_meta_boxes' ), 30 );
			add_action( 'save_post', array( $this, 'bb_rl_learndash_save_meta_boxes' ), 10, 2 );

			add_action( 'wp_ajax_bb_rl_lms_save_view', array( $this, 'bb_rl_lms_save_view' ) );
			add_action( 'wp_ajax_nopriv_bb_rl_lms_save_view', array( $this, 'bb_rl_lms_save_view' ) );
		}

		/**
		 * Override LearnDash template path to use ReadyLaunch templates
		 *
		 * @since BuddyBoss 2.9.00
		 *
		 * @param string     $filepath         Template file path.
		 * @param string     $name             Template name.
		 * @param array|null $args             Template data.
		 * @param bool|null  $out              Whether to echo the template output or not.
		 * @param bool       $return_file_path Whether to return file or path or not.
		 *
		 * @return string Modified template path
		 */
		public function bb_rl_override_learndash_template_path( $filepath, $name, $args, $out, $return_file_path ) {
			if (
				bp_is_active( 'groups' ) &&
				function_exists( 'bp_is_group_single' ) &&
				bp_is_group_single() &&
				function_exists( 'bp_current_action' ) &&
				'courses' === bp_current_action()
			) {
				return $filepath;
			}

			if (
				function_exists( 'bp_is_user' ) &&
				bp_is_user() &&
				function_exists( 'bp_current_action' ) &&
				(
					'my-courses' === bp_current_action() ||
					'certificates' === bp_current_action()
				)
			) {
				return $filepath;
			}

			// Map special LearnDash template names to ReadyLaunch template paths.
			$special_templates = array(
				'lesson/partials/row.php'  => 'learndash/ld30/lesson/partials/row.php',
				'quiz/partials/row.php'    => 'learndash/ld30/quiz/partials/row.php',
				'topic/partials/row.php'   => 'learndash/ld30/topic/partials/row.php',
				'modules/course-steps.php' => 'learndash/ld30/modules/course-steps.php',
			);

			if ( isset( $special_templates[ $name ] ) ) {
				$template = bp_locate_template( array( $special_templates[ $name ] ) );
				if ( $template ) {
					return $template;
				}
			}

			// Fallback: Try to load template using bp_get_template_part.
			$template_name = str_replace( '.php', '', basename( $name ) );
			$template      = bp_locate_template( array( "learndash/ld30/{$template_name}.php" ) );
			if ( $template ) {
				return $template;
			}

			return $filepath;
		}

		/**
		 * Enqueue LearnDash styles for ReadyLaunch.
		 *
		 * @since BuddyBoss 2.9.00
		 */
		public function bb_readylaunch_learndash_enqueue_styles() {
			if ( ! bb_is_readylaunch_enabled() || ! class_exists( 'SFWD_LMS' ) ) {
				return;
			}

			// Enqueue LearnDash ReadyLaunch styles.
			wp_enqueue_style(
				'bb-readylaunch-learndash',
				buddypress()->plugin_url . 'bp-templates/bp-nouveau/readylaunch/css/courses.css',
				array(),
				bp_get_version()
			);

			// Enqueue our LearnDash helper JavaScript.
			wp_enqueue_script(
				'bb-readylaunch-learndash-js',
				buddypress()->plugin_url . 'bp-templates/bp-nouveau/readylaunch/js/bb-readylaunch-learndash.js',
				array( 'jquery' ),
				bp_get_version(),
				true
			);

			wp_localize_script(
				'bb-readylaunch-learndash-js',
				'bbReadylaunchLearnDash',
				array(
					'courses_url'     => home_url( '/' . LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Section_Permalinks', 'courses' ) ),
					'ajaxurl'         => admin_url( 'admin-ajax.php' ),
					'nonce_list_grid' => wp_create_nonce( 'list-grid-settings' ),
				)
			);
		}

		/**
		 * Fires before the layout.
		 *
		 * @since BuddyBoss 2.9.00
		 */
		public function bb_rl_learndash_layout_before() {
			$is_ld_course_archive      = is_post_type_archive( learndash_get_post_type_slug( 'course' ) );
			$is_ld_topic_archive       = is_post_type_archive( learndash_get_post_type_slug( 'topic' ) );
			$is_ld_lesson_archive      = is_post_type_archive( learndash_get_post_type_slug( 'lesson' ) );
			$is_ld_quiz_archive        = is_post_type_archive( learndash_get_post_type_slug( 'quiz' ) );
			$is_ld_group_archive       = is_post_type_archive( learndash_get_post_type_slug( 'group' ) );
			$is_ld_registration_page   = bb_load_readylaunch()->bb_rl_is_learndash_registration_page();
			$is_ld_reset_password_page = bb_load_readylaunch()->bb_rl_is_learndash_reset_password_page();

			if ( $is_ld_course_archive ) {
				bp_get_template_part( 'learndash/ld30/archive-course-header' );
			}

			if (
				$is_ld_topic_archive ||
				$is_ld_lesson_archive ||
				$is_ld_quiz_archive ||
				$is_ld_group_archive ||
				$is_ld_registration_page ||
				$is_ld_reset_password_page
			) {
				$page_title = '';
				if ( 'page' === get_post_type() ) {
					$page_title = get_the_title();
				} else {
					$post_type_obj = get_post_type_object( get_post_type() );
					if ( $post_type_obj && ! empty( $post_type_obj->labels->name ) ) {
						$page_title = $post_type_obj->labels->name;
					}
				}
				if ( ! empty( $page_title ) ) {
					?>
					<div class="bb-rl-lms-page-title">
						<h1 class="bb-rl-lms-page-title-text">
							<?php echo esc_html( $page_title ); ?>
						</h1>
					</div>
					<?php
				}
			}
		}

		/**
		 * Fires after the layout.
		 *
		 * @since BuddyBoss 2.9.00
		 */
		public function bb_rl_learndash_layout_after() {
			if ( is_post_type_archive( learndash_get_post_type_slug( 'course' ) ) ) {
				bp_get_template_part( 'learndash/ld30/archive-course-footer' );
			}
		}

		/**
		 * Fires before the loop starts.
		 *
		 * @since BuddyBoss 2.9.00
		 */
		public function bb_rl_learndash_before_loop() {
			$is_ld_course_archive      = is_post_type_archive( learndash_get_post_type_slug( 'course' ) );
			$is_ld_topic_archive       = is_post_type_archive( learndash_get_post_type_slug( 'topic' ) );
			$is_ld_lesson_archive      = is_post_type_archive( learndash_get_post_type_slug( 'lesson' ) );
			$is_ld_quiz_archive        = is_post_type_archive( learndash_get_post_type_slug( 'quiz' ) );
			$is_ld_group_archive       = is_post_type_archive( learndash_get_post_type_slug( 'group' ) );
			$is_ld_group_single        = is_singular( learndash_get_post_type_slug( 'group' ) );
			$is_ld_registration_page   = bb_load_readylaunch()->bb_rl_is_learndash_registration_page();
			$is_ld_reset_password_page = bb_load_readylaunch()->bb_rl_is_learndash_reset_password_page();
			if ( $is_ld_course_archive ) {
				?>
				<div class="bb-rl-courses-grid grid bb-rl-courses-grid--ldlms">
				<?php
			}
			if (
				$is_ld_topic_archive ||
				$is_ld_lesson_archive ||
				$is_ld_quiz_archive ||
				$is_ld_group_archive ||
				$is_ld_group_single ||
				$is_ld_registration_page ||
				$is_ld_reset_password_page
			) {
				$page_class = 'archive';
				if ( $is_ld_group_single ) {
					$page_class = 'single';
				} elseif ( $is_ld_registration_page ) {
					$page_class = 'registration';
				} elseif ( $is_ld_reset_password_page ) {
					$page_class = 'reset-password';
				}
				?>
				<div class="bb-rl-lms-default-page bb-rl-lms-inner-block bb-rl-lms-inner-block--ld-<?php echo esc_attr( $page_class ); ?>">
				<?php
			}
		}

		/**
		 * Fires after the loop ends.
		 *
		 * @since BuddyBoss 2.9.00
		 */
		public function bb_rl_learndash_after_loop() {
			$is_ld_course_archive      = is_post_type_archive( learndash_get_post_type_slug( 'course' ) );
			$is_ld_topic_archive       = is_post_type_archive( learndash_get_post_type_slug( 'topic' ) );
			$is_ld_lesson_archive      = is_post_type_archive( learndash_get_post_type_slug( 'lesson' ) );
			$is_ld_quiz_archive        = is_post_type_archive( learndash_get_post_type_slug( 'quiz' ) );
			$is_ld_group_archive       = is_post_type_archive( learndash_get_post_type_slug( 'group' ) );
			$is_ld_group_single        = is_singular( learndash_get_post_type_slug( 'group' ) );
			$is_ld_registration_page   = bb_load_readylaunch()->bb_rl_is_learndash_registration_page();
			$is_ld_reset_password_page = bb_load_readylaunch()->bb_rl_is_learndash_reset_password_page();
			if ( $is_ld_course_archive ) {
				echo '</div>';
				bp_get_template_part( 'learndash/ld30/archive-course-pagination' );
			}

			if (
				$is_ld_topic_archive ||
				$is_ld_lesson_archive ||
				$is_ld_quiz_archive ||
				$is_ld_group_archive ||
				$is_ld_group_single ||
				$is_ld_registration_page ||
				$is_ld_reset_password_page
			) {
				if (
					$is_ld_group_single &&
					(
						comments_open() ||
						get_comments_number()
					)
				) {
					?>
					<div class="bb-rl-lms-content-comments bb-rl-course-content-comments">
						<?php
						bp_get_template_part( 'learndash/ld30/comments' );
						?>
					</div>
					<?php
				}
				echo '</div>';
			}
		}

		/**
		 * Fires when no posts are found.
		 *
		 * @since BuddyBoss 2.9.00
		 */
		public function bb_rl_learndash_no_posts() {
			bp_get_template_part( 'learndash/ld30/archive-no-course' );
		}

		/**
		 * Get all the URLs of current course (lesson, topic, quiz) for pagination.
		 *
		 * @since BuddyBoss 2.9.00
		 *
		 * @param int   $course_id           The course ID.
		 * @param array $lesson_list         Array of lesson objects.
		 * @param array $course_quizzes_list Optional. Array of course quizzes.
		 *
		 * @return array Array of navigation URLs.
		 */
		public function bb_rl_ld_custom_pagination( $course_id, $lesson_list, $course_quizzes_list = array() ) {
			$navigation_urls = array();

			if ( ! empty( $lesson_list ) ) {
				foreach ( $lesson_list as $lesson ) {
					if ( ! is_object( $lesson ) || ! isset( $lesson->ID ) ) {
						continue;
					}

					// Add lesson URL.
					$navigation_urls[] = urldecode( trailingslashit( get_permalink( $lesson->ID ) ) );

					// Get lesson topics.
					if ( function_exists( 'learndash_get_topic_list' ) ) {
						$lesson_topics = learndash_get_topic_list( $lesson->ID );

						if ( ! empty( $lesson_topics ) ) {
							foreach ( $lesson_topics as $lesson_topic ) {
								if ( ! is_object( $lesson_topic ) || ! isset( $lesson_topic->ID ) ) {
									continue;
								}

								// Add topic URL.
								$navigation_urls[] = urldecode( trailingslashit( get_permalink( $lesson_topic->ID ) ) );

								// Get topic quizzes.
								if ( function_exists( 'learndash_get_lesson_quiz_list' ) ) {
									$topic_quizzes = learndash_get_lesson_quiz_list( $lesson_topic->ID );

									if ( ! empty( $topic_quizzes ) ) {
										foreach ( $topic_quizzes as $topic_quiz ) {
											if ( ! isset( $topic_quiz['post'] ) || ! is_object( $topic_quiz['post'] ) || ! isset( $topic_quiz['post']->ID ) ) {
												continue;
											}
											$navigation_urls[] = urldecode( trailingslashit( get_permalink( $topic_quiz['post']->ID ) ) );
										}
									}
								}
							}
						}
					}

					// Get lesson quizzes.
					if ( function_exists( 'learndash_get_lesson_quiz_list' ) ) {
						$lesson_quizzes = learndash_get_lesson_quiz_list( $lesson->ID );

						if ( ! empty( $lesson_quizzes ) ) {
							foreach ( $lesson_quizzes as $lesson_quiz ) {
								if ( ! isset( $lesson_quiz['post'] ) || ! is_object( $lesson_quiz['post'] ) || ! isset( $lesson_quiz['post']->ID ) ) {
									continue;
								}
								$navigation_urls[] = urldecode( trailingslashit( get_permalink( $lesson_quiz['post']->ID ) ) );
							}
						}
					}
				}
			}

			// Get course quizzes.
			if ( function_exists( 'learndash_get_course_quiz_list' ) ) {
				$course_quizzes = learndash_get_course_quiz_list( $course_id );
				if ( ! empty( $course_quizzes ) ) {
					foreach ( $course_quizzes as $course_quiz ) {
						if ( ! isset( $course_quiz['post'] ) || ! is_object( $course_quiz['post'] ) || ! isset( $course_quiz['post']->ID ) ) {
							continue;
						}
						$navigation_urls[] = urldecode( trailingslashit( get_permalink( $course_quiz['post']->ID ) ) );
					}
				}
			}

			return $navigation_urls;
		}

		/**
		 * Return the next and previous URL based on the course current URL.
		 *
		 * @since BuddyBoss 2.9.00
		 *
		 * @param array  $url_arr     Array of URLs for navigation.
		 * @param string $current_url Optional. Current URL (auto-detected if empty).
		 *
		 * @return array Array with 'next' and 'prev' HTML links.
		 */
		public function bb_rl_custom_next_prev_url( $url_arr = array(), $current_url = '' ) {
			if ( empty( $url_arr ) ) {
				return array(
					'next' => '',
					'prev' => '',
				);
			}

			// Get current URL if not provided.
			if ( empty( $current_url ) ) {
				$protocol    = is_ssl() ? 'https' : 'http';
				$current_url = isset( $_SERVER['HTTP_HOST'] ) && isset( $_SERVER['REQUEST_URI'] ) ? $protocol . '://' . wp_unslash( $_SERVER['HTTP_HOST'] ) . wp_unslash( $_SERVER['REQUEST_URI'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Data is only used for conditional and not further processed.
			}

			// Normalize current URL.
			$current_url = trailingslashit( $current_url );
			if ( ! wp_parse_url( $current_url, PHP_URL_QUERY ) ) {
				$current_url = trailingslashit( $current_url );
			}

			// Find current URL in the array.
			$key = array_search( urldecode( $current_url ), $url_arr, true );

			if ( false === $key ) {
				return array(
					'next' => '',
					'prev' => '',
				);
			}

			$url_result        = array();
			$keys              = array_keys( $url_arr );
			$current_key_index = array_search( $key, $keys, true );

			// Get next URL.
			$next = null;
			if ( isset( $keys[ $current_key_index + 1 ] ) ) {
				$next_key = $keys[ $current_key_index + 1 ];
				$next     = $url_arr[ $next_key ];
			}

			// Get previous URL.
			$prev = null;
			if ( isset( $keys[ $current_key_index - 1 ] ) ) {
				$prev_key = $keys[ $current_key_index - 1 ];
				$prev     = $url_arr[ $prev_key ];
			}

			$last_element = end( $url_arr );

			// Build next link.
			$url_result['next'] = '';
			if ( ! empty( $next ) && $last_element !== $current_url ) {
				$url_result['next'] = sprintf(
				/* translators: 1: Next URL, 2: Next text */
					'<a href="%s" class="next-link" rel="next">%s<i class="bb-icons-rl-caret-right"></i></a>',
					esc_url( $next ),
					esc_html__( 'Next', 'buddyboss' )
				);
			}

			// Build previous link.
			$url_result['prev'] = '';
			if ( ! empty( $prev ) && $last_element !== $prev ) {
				$url_result['prev'] = sprintf(
				/* translators: 1: Previous URL, 2: Previous text */
					'<a href="%s" class="prev-link" rel="prev"><i class="bb-icons-rl-caret-left"></i> %s</a>',
					esc_url( $prev ),
					esc_html__( 'Previous', 'buddyboss' )
				);
			}

			return $url_result;
		}

		/**
		 * Get all the URLs of current course (lesson, topic, quiz) with completion status.
		 *
		 * @since BuddyBoss 2.9.00
		 *
		 * @param int   $course_id           The course ID.
		 * @param array $lesson_list         Array of lesson objects.
		 * @param array $course_quizzes_list Optional. Array of course quizzes.
		 *
		 * @return false|string Array of navigation URLs with completion status.
		 */
		public function bb_rl_ld_custom_continue_url_arr( $course_id, $lesson_list, $course_quizzes_list = array() ) {
			$user_id = get_current_user_id();

			// Check course access for closed courses.
			if ( function_exists( 'learndash_get_course_meta_setting' ) ) {
				$course_price_type = learndash_get_course_meta_setting( $course_id, 'course_price_type' );
				if ( 'closed' === $course_price_type ) {
					if ( function_exists( 'learndash_user_get_enrolled_courses' ) ) {
						$user_courses = learndash_user_get_enrolled_courses( $user_id );
						if ( ! in_array( $course_id, $user_courses, true ) ) {
							return get_permalink( $course_id );
						}
					}
				}
			}

			$navigation_urls = array();
			$course_progress = get_user_meta( $user_id, '_sfwd-course_progress', true );

			if ( ! empty( $lesson_list ) ) {
				foreach ( $lesson_list as $lesson ) {
					if ( ! is_object( $lesson ) || ! isset( $lesson->ID ) ) {
						continue;
					}

					// Check lesson completion.
					$lesson_completed = ! empty( $course_progress[ $course_id ]['lessons'][ $lesson->ID ] ) && 1 === $course_progress[ $course_id ]['lessons'][ $lesson->ID ];

					$lesson_url = function_exists( 'learndash_get_step_permalink' )
						? learndash_get_step_permalink( $lesson->ID, $course_id )
						: get_permalink( $lesson->ID );

					$navigation_urls[] = array(
						'url'      => $lesson_url,
						'complete' => $lesson_completed ? 'yes' : 'no',
					);

					// Get lesson topics.
					if ( function_exists( 'learndash_get_topic_list' ) ) {
						$lesson_topics = learndash_get_topic_list( $lesson->ID );

						if ( ! empty( $lesson_topics ) ) {
							foreach ( $lesson_topics as $lesson_topic ) {
								if ( ! is_object( $lesson_topic ) || ! isset( $lesson_topic->ID ) ) {
									continue;
								}

								// Check topic completion.
								$topic_completed = ! empty( $course_progress[ $course_id ]['topics'][ $lesson->ID ][ $lesson_topic->ID ] ) && 1 === $course_progress[ $course_id ]['topics'][ $lesson->ID ][ $lesson_topic->ID ];

								$topic_url = function_exists( 'learndash_get_step_permalink' )
									? learndash_get_step_permalink( $lesson_topic->ID, $course_id )
									: get_permalink( $lesson_topic->ID );

								$navigation_urls[] = array(
									'url'      => $topic_url,
									'complete' => $topic_completed ? 'yes' : 'no',
								);

								// Get topic quizzes.
								if ( function_exists( 'learndash_get_lesson_quiz_list' ) ) {
									$topic_quizzes = learndash_get_lesson_quiz_list( $lesson_topic->ID );

									if ( ! empty( $topic_quizzes ) ) {
										foreach ( $topic_quizzes as $topic_quiz ) {
											if ( ! isset( $topic_quiz['post'] ) || ! is_object( $topic_quiz['post'] ) || ! isset( $topic_quiz['post']->ID ) ) {
												continue;
											}

											$quiz_completed = function_exists( 'learndash_is_quiz_complete' )
												? learndash_is_quiz_complete( $user_id, $topic_quiz['post']->ID, $course_id )
												: false;

											$quiz_url = function_exists( 'learndash_get_step_permalink' )
												? learndash_get_step_permalink( $topic_quiz['post']->ID, $course_id )
												: get_permalink( $topic_quiz['post']->ID );

											$navigation_urls[] = array(
												'url'      => $quiz_url,
												'complete' => $quiz_completed ? 'yes' : 'no',
											);
										}
									}
								}
							}
						}
					}

					// Get lesson quizzes.
					if ( function_exists( 'learndash_get_lesson_quiz_list' ) ) {
						$lesson_quizzes = learndash_get_lesson_quiz_list( $lesson->ID );

						if ( ! empty( $lesson_quizzes ) ) {
							foreach ( $lesson_quizzes as $lesson_quiz ) {
								if ( ! isset( $lesson_quiz['post'] ) || ! is_object( $lesson_quiz['post'] ) || ! isset( $lesson_quiz['post']->ID ) ) {
									continue;
								}

								$quiz_completed = function_exists( 'learndash_is_quiz_complete' )
									? learndash_is_quiz_complete( $user_id, $lesson_quiz['post']->ID, $course_id )
									: false;

								$quiz_url = function_exists( 'learndash_get_step_permalink' )
									? learndash_get_step_permalink( $lesson_quiz['post']->ID, $course_id )
									: get_permalink( $lesson_quiz['post']->ID );

								$navigation_urls[] = array(
									'url'      => $quiz_url,
									'complete' => $quiz_completed ? 'yes' : 'no',
								);
							}
						}
					}
				}
			}

			// Get course quizzes.
			if ( function_exists( 'learndash_get_course_quiz_list' ) ) {
				$course_quizzes = learndash_get_course_quiz_list( $course_id );
				if ( ! empty( $course_quizzes ) ) {
					foreach ( $course_quizzes as $course_quiz ) {
						if ( ! isset( $course_quiz['post'] ) || ! is_object( $course_quiz['post'] ) || ! isset( $course_quiz['post']->ID ) ) {
							continue;
						}

						$quiz_completed = function_exists( 'learndash_is_quiz_complete' )
							? learndash_is_quiz_complete( $user_id, $course_quiz['post']->ID, $course_id )
							: false;

						$quiz_url = function_exists( 'learndash_get_step_permalink' )
							? learndash_get_step_permalink( $course_quiz['post']->ID, $course_id )
							: get_permalink( $course_quiz['post']->ID );

						$navigation_urls[] = array(
							'url'      => $quiz_url,
							'complete' => $quiz_completed ? 'yes' : 'no',
						);
					}
				}
			}

			// Find first incomplete step.
			$incomplete_steps = array_filter(
				$navigation_urls,
				function ( $step ) {
					return 'no' === $step['complete'];
				}
			);

			if ( ! empty( $incomplete_steps ) ) {
				$first_incomplete = reset( $incomplete_steps );

				return $first_incomplete['url'];
			}

			return '';
		}

		/**
		 * Get all quiz URLs from the current course.
		 *
		 * @since BuddyBoss 2.9.00
		 *
		 * @param int   $course_id           The course ID.
		 * @param array $lesson_list         Array of lesson objects.
		 * @param array $course_quizzes_list Optional. Array of course quizzes.
		 *
		 * @return array Array of quiz URLs.
		 */
		public function bb_rl_ld_custom_quiz_count( $course_id, $lesson_list, $course_quizzes_list = array() ) {
			$quiz_urls = array();

			if ( ! empty( $lesson_list ) ) {
				foreach ( $lesson_list as $lesson ) {
					if ( ! is_object( $lesson ) || ! isset( $lesson->ID ) ) {
						continue;
					}

					// Get lesson topics.
					if ( function_exists( 'learndash_get_topic_list' ) ) {
						$lesson_topics = learndash_get_topic_list( $lesson->ID );

						if ( ! empty( $lesson_topics ) ) {
							foreach ( $lesson_topics as $lesson_topic ) {
								if ( ! is_object( $lesson_topic ) || ! isset( $lesson_topic->ID ) ) {
									continue;
								}

								// Get topic quizzes.
								if ( function_exists( 'learndash_get_lesson_quiz_list' ) ) {
									$topic_quizzes = learndash_get_lesson_quiz_list( $lesson_topic->ID );

									if ( ! empty( $topic_quizzes ) ) {
										foreach ( $topic_quizzes as $topic_quiz ) {
											if ( ! isset( $topic_quiz['post'] ) || ! is_object( $topic_quiz['post'] ) || ! isset( $topic_quiz['post']->ID ) ) {
												continue;
											}
											$quiz_urls[] = get_permalink( $topic_quiz['post']->ID );
										}
									}
								}
							}
						}
					}

					// Get lesson quizzes.
					if ( function_exists( 'learndash_get_lesson_quiz_list' ) ) {
						$lesson_quizzes = learndash_get_lesson_quiz_list( $lesson->ID );

						if ( ! empty( $lesson_quizzes ) ) {
							foreach ( $lesson_quizzes as $lesson_quiz ) {
								if ( ! isset( $lesson_quiz['post'] ) || ! is_object( $lesson_quiz['post'] ) || ! isset( $lesson_quiz['post']->ID ) ) {
									continue;
								}
								$quiz_urls[] = get_permalink( $lesson_quiz['post']->ID );
							}
						}
					}
				}
			}

			// Get course quizzes.
			if ( function_exists( 'learndash_get_course_quiz_list' ) ) {
				$course_quizzes = learndash_get_course_quiz_list( $course_id );
				if ( ! empty( $course_quizzes ) ) {
					foreach ( $course_quizzes as $course_quiz ) {
						if ( ! isset( $course_quiz['post'] ) || ! is_object( $course_quiz['post'] ) || ! isset( $course_quiz['post']->ID ) ) {
							continue;
						}
						$quiz_urls[] = get_permalink( $course_quiz['post']->ID );
					}
				}
			}

			return $quiz_urls;
		}

		/**
		 * Return the current quiz number based on URL array.
		 *
		 * @since BuddyBoss 2.9.00
		 *
		 * @param array  $url_arr     Array of quiz URLs.
		 * @param string $current_url Optional. Current URL (auto-detected if empty).
		 *
		 * @return int Current quiz number (1-based index).
		 */
		public function bb_rl_ld_custom_quiz_key( $url_arr = array(), $current_url = '' ) {
			if ( empty( $url_arr ) ) {
				return 0;
			}

			// Get current URL if not provided.
			if ( empty( $current_url ) && isset( $_SERVER['HTTP_HOST'] ) && isset( $_SERVER['REQUEST_URI'] ) ) {
				$protocol    = is_ssl() ? 'https' : 'http';
				$current_url = $protocol . '://' . sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) ) . sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) );
			}

			// Normalize current URL.
			$current_url = trailingslashit( $current_url );

			$key = array_search( $current_url, $url_arr, true );

			return false !== $key ? $key + 1 : 0;
		}

		/**
		 * Filter the main query for courses based on filters
		 *
		 * @since BuddyBoss 2.9.00
		 *
		 * @param WP_Query $query The query object.
		 *
		 * @return void
		 */
		public function bb_rl_filter_courses_query( $query ) {
			// Only modify the main query for course archive.
			if ( ! is_admin() && $query->is_main_query() && is_post_type_archive( 'sfwd-courses' ) ) {

				// Get filter values.
				$orderby_data = $this->bb_rl_get_orderby_data();
				$orderby      = $orderby_data['current_orderby'];
				$category     = $orderby_data['current_category'];
				$instructor   = $orderby_data['current_instructor'];

				// Ensure we get the total count.
				$query->set( 'no_found_rows', false );

				// Handle ordering.
				switch ( $orderby ) {
					case 'alphabetical':
						$query->set( 'orderby', 'title' );
						$query->set( 'order', 'ASC' );
						break;
					case 'recent':
						$query->set( 'orderby', 'date' );
						$query->set( 'order', 'DESC' );
						break;
					case 'my-progress':
						if ( is_user_logged_in() ) {
							$user_id = get_current_user_id();
							// Get user's course progress.
							$user_courses = learndash_user_get_enrolled_courses( $user_id );
							if ( ! empty( $user_courses ) ) {
								$query->set( 'post__in', $user_courses );
							}
						}
						break;
				}

				// Handle category filter.
				if ( ! empty( $category ) && 'all' !== $category ) {
					$tax_query = array(
						array(
							'taxonomy' => 'ld_course_category',
							'field'    => 'slug',
							'terms'    => $category,
						),
					);
					$query->set( 'tax_query', $tax_query );
				}

				// Handle instructor filter.
				if ( ! empty( $instructor ) && 'all' !== $instructor ) {
					$query->set( 'author', $instructor );
				}
			}
		}

		/**
		 * Filter the main query for courses based on filters
		 *
		 * @since BuddyBoss 2.9.00
		 *
		 * @param array $args Optional. Array of arguments.
		 *
		 * @return array Array of enrolled users.
		 */
		public function bb_rl_ld_get_enrolled_users_data( $args ) {
			$defaults = array(
				'course_id' => 0,
				'limit'     => 10,
				'count'     => false,
				'data'      => false,
			);
			$args     = bp_parse_args( $args, $defaults );

			$enrolled_users = array();
			if ( function_exists( 'learndash_get_users_for_course' ) ) {
				$all_enrolled_users = learndash_get_users_for_course( $args['course_id'], array(), false );

				// Handle both array and WP_User_Query object returns.
				if ( is_array( $all_enrolled_users ) ) {
					$enrolled_users_count = count( $all_enrolled_users );
				} elseif ( is_object( $all_enrolled_users ) && method_exists( $all_enrolled_users, 'get_total' ) ) {
					$enrolled_users_count = $all_enrolled_users->get_total();
				} else {
					$enrolled_users_count = 0;
				}

				if ( ! empty( $args['data'] ) ) {
					$enrolled_users_query = learndash_get_users_for_course( $args['course_id'], array( 'number' => $args['limit'] ), false );
					if ( $enrolled_users_query instanceof WP_User_Query && ! empty( $enrolled_users_query->get_results() ) ) {
						$enrolled_users = $enrolled_users_query->get_results();
					} elseif ( is_array( $enrolled_users_query ) ) {
						$enrolled_users = array_slice( $enrolled_users_query, 0, $args['limit'] );
					}
				}
			}

			$retval = array();

			if ( ! empty( $args['data'] ) ) {
				$retval['enrolled_users'] = $enrolled_users;
			}
			if ( ! empty( $args['count'] ) ) {
				$retval['count'] = $enrolled_users_count;
			}

			return $retval;
		}

		/**
		 * Get enrolled users for a course.
		 *
		 * @since BuddyBoss 2.9.00
		 *
		 * @param array $args Optional. Array of arguments.
		 */
		public function bb_rl_ld_get_enrolled_users_html( $args ) {

			$defaults = array(
				'course_id' => 0,
				'limit'     => 10,
			);
			$args     = bp_parse_args( $args, $defaults );

			$course_id = $args['course_id'];
			$limit     = $args['limit'];
			$action    = $args['action'];

			$enrolled_users = $this->bb_rl_ld_get_enrolled_users_data( $args );

			$enrolled_users_count = $enrolled_users['count'];
			$enrolled_users       = $enrolled_users['enrolled_users'];

			if ( ! empty( $enrolled_users ) ) {
				// Sort by enrollment date (most recent first).
				$user_enrollments = array();
				foreach ( $enrolled_users as $user_id ) {
					$enrolled_date = get_user_meta( $user_id, 'course_' . $course_id . '_access_from', true );
					if ( empty( $enrolled_date ) ) {
						$enrolled_date = time(); // Fallback to current time if no enrollment date.
					}
					$user_enrollments[] = array(
						'user_id'       => $user_id,
						'enrolled_date' => $enrolled_date,
					);
				}

				// Sort by enrollment date (newest first).
				usort(
					$user_enrollments,
					function ( $a, $b ) {
						return $b['enrolled_date'] - $a['enrolled_date'];
					}
				);

				// Limit to 5 most recent enrollments.
				$recent_enrollments = array_slice( $user_enrollments, 0, $limit );

				if ( ! empty( $recent_enrollments ) ) {
					$enrolled_count = isset( $enrolled_users_count ) ? $enrolled_users_count : count( $recent_enrollments );
					$wrapper_class  = 'bb-rl-recent-enrolled-members';
					if ( 'header' === $action ) {
						$wrapper_class = 'bb-rl-enrolled-members-bar';
					}
					?>
					<div class="<?php echo esc_attr( $wrapper_class ); ?>">
						<?php
						foreach ( $recent_enrollments as $enrollment ) {
							$user_id   = $enrollment['user_id'];
							$user_data = get_userdata( $user_id );

							if ( $user_data ) {
								$user_link    = function_exists( 'bp_core_get_user_domain' ) ? bp_core_get_user_domain( $user_id ) : get_author_posts_url( $user_id );
								$display_name = function_exists( 'bp_core_get_user_displayname' ) ? bp_core_get_user_displayname( $user_id ) : $user_data->display_name;
								?>
								<div class="bb-rl-enrolled-member-item">
									<a href="<?php echo esc_url( $user_link ); ?>" title="<?php echo esc_attr( $display_name ); ?>" data-balloon-pos="up" data-balloon="<?php echo esc_attr( $display_name ); ?>">
										<?php
										// Use bp_core_fetch_avatar with proper parameters.
										if ( function_exists( 'bp_core_fetch_avatar' ) ) {
											echo wp_kses_post(
												bp_core_fetch_avatar(
													array(
														'item_id' => $user_id,
														'width'   => 48,
														'height'  => 48,
														'type'    => 'full',
														'html'    => true,
													)
												)
											);
										}
										if ( function_exists( 'bb_user_presence_html' ) ) {
											bb_user_presence_html( $user_id );
										}
										?>
									</a>
								</div>
								<?php
							}
						}
						if ( 'header' === $action ) {
							?>
							<span class="bb-rl-enrolled-count">
								<?php
								printf(
								// translators: %d is the number of enrolled users.
									esc_html__( '%d+ Student enrolled', 'buddyboss' ),
									intval( $enrolled_count )
								);
								?>
							</span>
							<?php
						}
						?>
					</div>
					<?php
				}
			} else {
				?>
				<p>
					<?php esc_html_e( 'No members enrolled yet.', 'buddyboss' ); ?>
				</p>
				<?php
			}
		}

		/**
		 * Get enrolled users for a course.
		 *
		 * @since BuddyBoss 2.9.00
		 *
		 * @param array $args Optional. Array of arguments.
		 */
		public function bb_rl_ld_get_enrolled_users( $args ) {

			$defaults = array(
				'course_id' => 0,
				'limit'     => 10,
			);
			$args     = bp_parse_args( $args, $defaults );

			$course_id = $args['course_id'];
			$limit     = $args['limit'];
			$action    = $args['action'];

			$enrolled_users = array();
			if ( function_exists( 'learndash_get_users_for_course' ) ) {
				$all_enrolled_users = learndash_get_users_for_course( $course_id, array(), true );

				// Handle both array and WP_User_Query object returns.
				if ( is_array( $all_enrolled_users ) ) {
					$enrolled_users_count = count( $all_enrolled_users );
				} elseif ( is_object( $all_enrolled_users ) && method_exists( $all_enrolled_users, 'get_total' ) ) {
					$enrolled_users_count = $all_enrolled_users->get_total();
				} else {
					$enrolled_users_count = 0;
				}

				$enrolled_users_query = learndash_get_users_for_course( $course_id, array( 'number' => $limit ), false );

				if ( $enrolled_users_query instanceof WP_User_Query && ! empty( $enrolled_users_query->get_results() ) ) {
					$enrolled_users = $enrolled_users_query->get_results();
				} elseif ( is_array( $enrolled_users_query ) ) {
					$enrolled_users = array_slice( $enrolled_users_query, 0, $limit );
				}
			}

			if ( ! empty( $enrolled_users ) ) {
				// Sort by enrollment date (most recent first).
				$user_enrollments = array();
				foreach ( $enrolled_users as $user_id ) {
					$enrolled_date = get_user_meta( $user_id, 'course_' . $course_id . '_access_from', true );
					if ( empty( $enrolled_date ) ) {
						$enrolled_date = time(); // Fallback to current time if no enrollment date.
					}
					$user_enrollments[] = array(
						'user_id'       => $user_id,
						'enrolled_date' => $enrolled_date,
					);
				}

				// Sort by enrollment date (newest first).
				usort(
					$user_enrollments,
					function ( $a, $b ) {
						return $b['enrolled_date'] - $a['enrolled_date'];
					}
				);

				// Limit to 5 most recent enrollments.
				$recent_enrollments = array_slice( $user_enrollments, 0, $limit );

				if ( ! empty( $recent_enrollments ) ) {
					$enrolled_count = isset( $enrolled_users_count ) ? $enrolled_users_count : count( $recent_enrollments );
					$wrapper_class  = 'bb-rl-recent-enrolled-members';
					if ( 'header' === $action ) {
						$wrapper_class = 'bb-rl-enrolled-members-bar';
					}
					?>
					<div class="<?php echo esc_attr( $wrapper_class ); ?>">
						<?php
						foreach ( $recent_enrollments as $enrollment ) {
							$user_id   = $enrollment['user_id'];
							$user_data = get_userdata( $user_id );

							if ( $user_data ) {
								$user_link    = function_exists( 'bp_core_get_user_domain' ) ? bp_core_get_user_domain( $user_id ) : get_author_posts_url( $user_id );
								$display_name = function_exists( 'bp_core_get_user_displayname' ) ? bp_core_get_user_displayname( $user_id ) : $user_data->display_name;
								?>
								<div class="bb-rl-enrolled-member-item">
									<a href="<?php echo esc_url( $user_link ); ?>" title="<?php echo esc_attr( $display_name ); ?>" data-balloon-pos="up" data-balloon="<?php echo esc_attr( $display_name ); ?>">
										<?php
										// Use bp_core_fetch_avatar with proper parameters.
										if ( function_exists( 'bp_core_fetch_avatar' ) ) {
											echo wp_kses_post(
												bp_core_fetch_avatar(
													array(
														'item_id' => $user_id,
														'width'   => 48,
														'height'  => 48,
														'type'    => 'full',
														'html'    => true,
													)
												)
											);
										}
										if ( function_exists( 'bb_user_presence_html' ) ) {
											bb_user_presence_html( $user_id );
										}
										?>
									</a>
								</div>
								<?php
							}
						}
						if ( 'header' === $action ) {
							if ( $enrolled_count > $limit ) {
								$remaining_count = $enrolled_count - $limit;
								?>
								<span class="bb-rl-enrolled-count">
									<?php
									printf(
									/* translators: %d is the number of enrolled users. */
										esc_html__( '%d+ Student enrolled', 'buddyboss' ),
										intval( $remaining_count )
									);
									?>
								</span>
								<?php
							} else {
								?>
								<span class="bb-rl-enrolled-count">
									<?php
									printf(
									/* translators: %d is the number of enrolled users. */
										esc_html__( '%d Student enrolled', 'buddyboss' ),
										intval( $enrolled_count )
									);
									?>
								</span>
								<?php
							}
						}
						?>
					</div>
					<?php
				}
			} else {
				?>
				<p>
					<?php esc_html_e( 'No members enrolled yet.', 'buddyboss' ); ?>
				</p>
				<?php
			}
		}

		/**
		 * Add custom classes to lesson rows in LearnDash sidebar.
		 * Adds bb-rl-current-lesson class for current lesson or parent lesson of current topic.
		 *
		 * @since BuddyBoss 2.9.00
		 *
		 * @param string $lesson_class The existing lesson classes.
		 * @param array  $lesson       The lesson object.
		 *
		 * @return string Modified lesson classes.
		 */
		public function bb_rl_learndash_lesson_row_class( $lesson_class, $lesson ) {
			$current_post = get_post();

			if ( ! $current_post || ! isset( $lesson['post']->ID ) ) {
				return $lesson_class;
			}

			$add_current_class = false;

			switch ( $current_post->post_type ) {
				case 'sfwd-topic':
					// If viewing a topic, check if this lesson is its parent.
					$topic_lesson_id = learndash_get_setting( $current_post->ID, 'lesson' );
					if ( isset( $lesson['post']->ID ) && (int) $lesson['post']->ID === (int) $topic_lesson_id ) {
						$add_current_class = true;
					}
					break;

				case 'sfwd-lessons':
					// If viewing a lesson, check if this is the current lesson.
					if ( isset( $lesson['post']->ID ) && (int) $lesson['post']->ID === (int) $current_post->ID ) {
						$add_current_class = true;
					}
					break;

				case 'sfwd-quiz':
					$quiz_lesson_id = learndash_get_setting( $current_post->ID, 'lesson' );
					$quiz_topic_id  = learndash_get_setting( $current_post->ID, 'topic' );

					// If quiz is directly associated with a lesson.
					if ( ! empty( $quiz_lesson_id ) && 'sfwd-lessons' === get_post_type( $quiz_lesson_id ) ) {
						if ( isset( $lesson['post']->ID ) && (int) $lesson['post']->ID === (int) $quiz_lesson_id ) {
							$add_current_class = true;
						}
					} else {
						$topic_id = 0;
						if ( ! empty( $quiz_topic_id ) ) {
							$topic_id = $quiz_topic_id;
						} elseif ( ! empty( $quiz_lesson_id ) && 'sfwd-topic' === get_post_type( $quiz_lesson_id ) ) {
							$topic_id = $quiz_lesson_id;
						}
						if ( $topic_id ) {
							$topic_lesson_id = learndash_get_setting( $topic_id, 'lesson' );
							if ( ! empty( $topic_lesson_id ) && isset( $lesson['post']->ID ) && (int) $lesson['post']->ID === (int) $topic_lesson_id ) {
								$add_current_class = true;
							}
						}
					}
					break;
			}

			if ( $add_current_class ) {
				$lesson_class .= ' bb-rl-current-lesson';
			}

			return $lesson_class;
		}

		/**
		 * Add custom classes to topic rows in LearnDash sidebar.
		 * Adds bb-rl-current-topic class for current topic.
		 *
		 * @since BuddyBoss 2.9.00
		 *
		 * @param string $topic_class The existing topic classes.
		 * @param object $topic       The topic object.
		 *
		 * @return string Modified topic classes.
		 */
		public function bb_rl_learndash_topic_row_class( $topic_class, $topic ) {
			$current_post = get_post();
			if ( ! $current_post || ! isset( $topic->ID ) ) {
				return $topic_class;
			}

			if ( (int) $topic->ID === (int) $current_post->ID ) {
				$topic_class .= ' bb-rl-current-topic-anchor';
			}

			return $topic_class;
		}

		/**
		 * Add custom classes to quiz rows in LearnDash sidebar.
		 * Adds bb-rl-current-step class for current quiz.
		 *
		 * @since BuddyBoss 2.9.00
		 *
		 * @param array $classes The existing quiz classes.
		 * @param array $quiz    The quiz object.
		 *
		 * @return array Modified quiz classes.
		 */
		public function bb_rl_learndash_quiz_row_classes( $classes, $quiz ) {
			$current_post = get_post();

			if ( ! $current_post || ! isset( $quiz['post']->ID ) ) {
				return $classes;
			}

			if ( (int) $quiz['post']->ID === (int) $current_post->ID ) {
				$classes['wrapper'] .= ' bb-rl-current-quiz-wrapper';
				$classes['anchor']  .= ' bb-rl-current-quiz-anchor';
			}
			return $classes;
		}

		/**
		 * Get the orderby data.
		 *
		 * @since BuddyBoss 2.9.00
		 *
		 * @return array The orderby data.
		 */
		public function bb_rl_get_orderby_data() {
			$current_orderby    = isset( $_GET['orderby'] ) ? sanitize_text_field( wp_unslash( $_GET['orderby'] ) ) : 'alphabetical'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$current_category   = isset( $_GET['categories'] ) ? sanitize_text_field( wp_unslash( $_GET['categories'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$current_instructor = isset( $_GET['instructors'] ) ? sanitize_text_field( wp_unslash( $_GET['instructors'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

			return array(
				'current_orderby'    => $current_orderby,
				'current_category'   => $current_category,
				'current_instructor' => $current_instructor,
			);
		}

		/**
		 * Get course instructors including main author and shared instructors.
		 *
		 * @since BuddyBoss 2.9.00
		 *
		 * @param int $course_id Course ID.
		 *
		 * @return array Array of instructor IDs.
		 */
		public function bb_rl_get_course_instructor( $course_id ) {
			$shared_instructor_list = get_post_meta( $course_id, 'ir_shared_instructor_ids', 1 );
			$shared_instructor_ids  = ! empty( $shared_instructor_list ) ? explode( ',', $shared_instructor_list ) : array();

			$main_author_id = get_post_field( 'post_author', $course_id );
			if ( ! empty( $main_author_id ) ) {
				$shared_instructor_ids[] = $main_author_id;
			}

			return array_filter( array_unique( $shared_instructor_ids ) );
		}

		/**
		 * Filter the content of the lesson.
		 *
		 * @since BuddyBoss 2.9.00
		 *
		 * @param string $content The content of the lesson.
		 * @param object $post    The post object.
		 *
		 * @return string The filtered content.
		 */
		public function bb_rl_learndash_content( $content, $post ) {
			if ( empty( $post->post_type ) ) {
				return $content;
			}

			$course_id = learndash_get_course_id( $post );
			if ( empty( $course_id ) ) {
				return $content;
			}

			if ( 'sfwd-lessons' === $post->post_type ) {
				$lesson_id = $post->ID;
			} elseif ( 'sfwd-topic' === $post->post_type || 'sfwd-quiz' === $post->post_type ) {
				$topic_id = $post->ID;
				if ( LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Courses_Builder', 'shared_steps' ) === 'yes' ) {
					$lesson_id = learndash_course_get_single_parent_step( $course_id, $post->ID );
				} else {
					$lesson_id = learndash_get_setting( $post, 'lesson' );
				}
			} else {
				return $content;
			}

			if ( is_user_logged_in() ) {
				$user_id = get_current_user_id();
			} else {
				return $content;
			}

			if ( learndash_is_admin_user( $user_id ) ) {
				$bypass_course_limits_admin_users = LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Section_General_Admin_User', 'bypass_course_limits_admin_users' );
				if ( 'yes' === $bypass_course_limits_admin_users ) {
					$bypass_course_limits_admin_users = true;
				} else {
					$bypass_course_limits_admin_users = false;
				}
			} else {
				$bypass_course_limits_admin_users = false;
			}

			// For logged in users to allow an override filter.
			$bypass_course_limits_admin_users = apply_filters( 'learndash_prerequities_bypass', $bypass_course_limits_admin_users, $user_id, $post->ID, $post );

			$lesson_access_from = learndash_course_step_available_date( $post->ID, $course_id, get_current_user_id(), true );
			if ( ( empty( $lesson_access_from ) ) || ( $bypass_course_limits_admin_users ) ) {
				return $content;
			} else {

				$context = learndash_get_post_type_key( $post->post_type );

				if ( learndash_get_post_type_slug( 'lesson' ) === $post->post_type ) {
					$lesson_id = $post->ID;
				} else {
					$lesson_id = 0;
				}

				$content = SFWD_LMS::get_template(
					'learndash_course_lesson_not_available',
					array(
						'user_id'                 => get_current_user_id(),
						'course_id'               => learndash_get_course_id( $post->ID ),
						'step_id'                 => $post->ID,
						'lesson_id'               => $lesson_id,
						'lesson_access_from_int'  => $lesson_access_from,
						'lesson_access_from_date' => learndash_adjust_date_time_display( $lesson_access_from ),
						'context'                 => $context,
					),
					false
				);

				return $content;

			}

			return $content;
		}

		/**
		 * Get the courses progress.
		 *
		 * @since BuddyBoss 2.9.00
		 *
		 * @param int    $user_id The user ID.
		 * @param string $sort_order The sort order.
		 *
		 * @return array The courses progress.
		 */
		public function bb_rl_get_courses_progress( $user_id, $sort_order = 'desc' ) {
			$course_completion_percentage = wp_cache_get( $user_id, 'ld_courses_progress' );
			if ( ! $course_completion_percentage ) {
				$course_completion_percentage = array();

				$course_progress = get_user_meta( $user_id, '_sfwd-course_progress', true );

				if ( ! empty( $course_progress ) && is_array( $course_progress ) ) {

					foreach ( $course_progress as $course_id => $coursep ) {
						// We take default progress value as 1 % rather than 0%.
						$course_completion_percentage[ $course_id ] = 1;
						if ( 0 === (int) $coursep['total'] ) {
							continue;
						}

						$course_steps_count     = learndash_get_course_steps_count( $course_id );
						$course_steps_completed = learndash_course_get_completed_steps( $user_id, $course_id, $coursep );

						$completed_on = get_user_meta( $user_id, 'course_completed_' . $course_id, true );
						if ( ! empty( $completed_on ) ) {

							$coursep['completed'] = $course_steps_count;
							$coursep['total']     = $course_steps_count;

						} else {
							$coursep['total']     = $course_steps_count;
							$coursep['completed'] = $course_steps_completed;

							if ( $coursep['completed'] > $coursep['total'] ) {
								$coursep['completed'] = $coursep['total'];
							}
						}

						// Cannot divide by 0.
						if ( 0 === (int) $coursep['total'] ) {
							$course_completion_percentage[ $course_id ] = 0;
						} else {
							$course_completion_percentage[ $course_id ] = ceil( ( $coursep['completed'] * 100 ) / $coursep['total'] );
						}
					}
				}

				// Avoid running the queries multiple times if user's course progress is empty.
				$course_completion_percentage = ! empty( $course_completion_percentage ) ? $course_completion_percentage : 'empty';

				wp_cache_set( $user_id, $course_completion_percentage, 'ld_courses_progress' );
			}

			$course_completion_percentage = 'empty' !== $course_completion_percentage ? $course_completion_percentage : array();

			if ( ! empty( $course_completion_percentage ) ) {
				// Sort.
				if ( 'asc' === $sort_order ) {
					asort( $course_completion_percentage );
				} else {
					arsort( $course_completion_percentage );
				}
			}

			return $course_completion_percentage;
		}

		/**
		 * Reset object cache for ld_courses_progress.
		 *
		 * @since BuddyBoss 2.9.00
		 *
		 * @param array $args Details of the learndash activity.
		 */
		public function bb_rl_flush_ld_courses_progress_cache( $args ) {
			if ( ! empty( $args['user_id'] ) ) {
				wp_cache_delete( absint( $args['user_id'] ), 'ld_courses_progress' );
			}
		}

		/**
		 * Filter the content tabs.
		 *
		 * @since BuddyBoss 2.9.00
		 *
		 * @param array  $tabs The content tabs.
		 * @param string $context The context.
		 * @param int    $course_id The course ID.
		 * @param int    $user_id The user ID.
		 *
		 * @return array The filtered content tabs.
		 */
		public function bb_rl_learndash_content_tabs( $tabs, $context, $course_id, $user_id ) {
			if ( 'course' === $context ) {
				$tabs[0]['label'] = __( 'About course', 'buddyboss' );
				if ( empty( $tabs[0]['content'] ) ) {
					unset( $tabs[0] );
				}
			}
			return $tabs;
		}

		/**
		 * Output the review reply template.
		 *
		 * @since BuddyBoss 2.9.00
		 *
		 * @param int $course_id The course ID.
		 */
		public function bb_rl_output_review_reply_template( $course_id ) {
			bb_remove_class_action( 'learndash_course_reviews_review_reply', 'LearnDash_Course_Reviews_Loader', 'output_review_reply_template' )
			?>
			<div id="learndash-course-reviews-reply" class="bb-rl-learndash-course-reviews-reply" style="display: none">
				<h3 id="learndash-course-reviews-reply-heading" class="learndash-course-reviews-heading">
					<?php esc_html_e( 'Leave a reply', 'buddyboss' ); ?>
					<small>
						<a rel="nofollow" id="cancel-comment-reply-link" href="#">
							<?php esc_html_e( 'Cancel reply', 'buddyboss' ); ?>
						</a>
					</small>
				</h3>
				<form action="" method="post" name="">
					<div class="grid-container full">
						<div class="grid-x">
							<div class="small-12 cell">
								<label for="learndash-course-reviews-review">
									<?php esc_html_e( 'Comment', 'buddyboss' ); ?> <span class="required">*</span>
								</label>
								<textarea
									id="learndash-course-reviews-reply"
									name="learndash-course-reviews-reply"
									cols="45"
									rows="8"
									aria-required="true"
									required="required"
								></textarea>
							</div>
						</div>
						<div class="grid-x">
							<div class="small-12 cell">
								<input
									type="submit"
									class="button primary expanded"
									value="<?php esc_attr_e( 'Post Reply', 'buddyboss' ); ?>"
								/>
							</div>
						</div>
					</div>
				</form>
			</div>
			<?php
		}

		/**
		 * Check if the current page is a LearnDash lesson, topic, or quiz page.
		 *
		 * @since BuddyBoss 2.9.00
		 * @return bool True if on lesson, topic, or quiz page, false otherwise.
		 */
		public function bb_rl_is_learndash_inner_page() {
			if ( ! class_exists( 'SFWD_LMS' ) ) {
				return false;
			}

			$courses_integration = bp_get_option( 'bb_rl_enabled_pages' );
			$courses_integration = isset( $courses_integration['courses'] ) ? $courses_integration['courses'] : false;
			if ( ! $courses_integration ) {
				return false;
			}

			global $post, $wp_query;

			// Multiple ways to get the post type.
			$post_type = '';

			// Get post type.
			if ( function_exists( 'get_post_type' ) ) {
				$post_type = get_post_type();
			}

			// Check global $post.
			if ( empty( $post_type ) && isset( $post->post_type ) ) {
				$post_type = $post->post_type;
			}

			// Check queried object.
			if ( empty( $post_type ) && is_object( $wp_query ) ) {
				$queried_object = get_queried_object();
				if ( $queried_object && isset( $queried_object->post_type ) ) {
					$post_type = $queried_object->post_type;
				}
			}

			// Check query vars.
			if ( empty( $post_type ) && is_object( $wp_query ) && isset( $wp_query->query_vars['post_type'] ) ) {
				$post_type = $wp_query->query_vars['post_type'];
			}

			// LearnDash lesson, topic, and quiz post types only.
			$ld_lesson_topic_quiz_types = array(
				learndash_get_post_type_slug( 'lesson' ),
				learndash_get_post_type_slug( 'topic' ),
				learndash_get_post_type_slug( 'quiz' ),
			);

			// Check if it's a lesson, topic, or quiz archive page then not display the sidebar.
			if (
				is_post_type_archive( learndash_get_post_type_slug( 'lesson' ) ) ||
				is_post_type_archive( learndash_get_post_type_slug( 'topic' ) ) ||
				is_post_type_archive( learndash_get_post_type_slug( 'quiz' ) )
			) {
				return false;
			}

			// Check if it's a singular lesson, topic, or quiz page.
			if ( is_singular( $ld_lesson_topic_quiz_types ) ) {
				return true;
			}

			// Check if post type matches LearnDash lesson, topic, or quiz types.
			if ( ! empty( $post_type ) && in_array( $post_type, $ld_lesson_topic_quiz_types, true ) ) {
				return true;
			}

			return false;
		}

		/**
		 * Add meta boxes to the course post type.
		 *
		 * @since BuddyBoss 2.9.00
		 */
		public function bb_rl_learndash_add_meta_boxes() {
			// Get existing meta boxes to check for duplicates.
			global $wp_meta_boxes;
			$existing_meta_boxes = isset( $wp_meta_boxes['sfwd-courses'] ) ? $wp_meta_boxes['sfwd-courses'] : array();

			// Check if 'postexcerpt' meta box already exists.
			$excerpt_exists = false;
			if ( ! empty( $existing_meta_boxes['normal']['high'] ) ) {
				foreach ( $existing_meta_boxes['normal']['high'] as $meta_box ) {
					if ( isset( $meta_box['id'] ) && 'postexcerpt' === $meta_box['id'] ) {
						$excerpt_exists = true;
						break;
					}
				}
			}

			// Check if 'post_price_box' meta box already exists.
			$price_box_exists = false;
			if ( ! empty( $existing_meta_boxes['normal']['low'] ) ) {
				foreach ( $existing_meta_boxes['normal']['low'] as $meta_box ) {
					if ( isset( $meta_box['id'] ) && 'post_price_box' === $meta_box['id'] ) {
						$price_box_exists = true;
						break;
					}
				}
			}

			// Add 'postexcerpt' meta box only if it doesn't exist.
			if ( ! $excerpt_exists ) {
				add_meta_box(
					'postexcerpt',
					__( 'Course Short Description', 'buddyboss' ),
					array( $this, 'bb_rl_learndash_course_short_description_output' ),
					'sfwd-courses',
					'normal',
					'high'
				);
			}

			// Add 'post_price_box' meta box only if it doesn't exist.
			if ( ! $price_box_exists ) {
				add_meta_box(
					'post_price_box',
					__( 'Course Video Preview', 'buddyboss' ),
					array( $this, 'bb_rl_learndash_course_video_preview_output' ),
					'sfwd-courses',
					'normal',
					'low'
				);
			}
		}

		/**
		 * Output the course short description.
		 *
		 * @since BuddyBoss 2.9.00
		 *
		 * @param object $post The post object.
		 */
		public function bb_rl_learndash_course_short_description_output( $post ) {
			$settings = array(
				'textarea_name' => 'excerpt',
				'quicktags'     => array( 'buttons' => 'em,strong,link' ),
				'tinymce'       => array(
					'theme_advanced_buttons1' => 'bold,italic,strikethrough,separator,bullist,numlist,separator,blockquote,separator,justifyleft,justifycenter,justifyright,separator,link,unlink,separator,undo,redo,separator',
					'theme_advanced_buttons2' => '',
				),
				'editor_css'    => '<style>#wp-excerpt-editor-container .wp-editor-area{height:175px; width:100%;}</style>',
			);

			wp_editor( htmlspecialchars_decode( $post->post_excerpt, ENT_QUOTES ), 'excerpt', $settings );
		}

		/**
		 * Output the course video preview.
		 *
		 * @since BuddyBoss 2.9.00
		 *
		 * @param object $post The post object.
		 */
		public function bb_rl_learndash_course_video_preview_output( $post ) {
			?>
			<div class="sfwd sfwd_options sfwd-courses_settings">
				<div class="sfwd_input">
					<span class="sfwd_option_label">
						<a class="sfwd_help_text_link" style="cursor:pointer;" title="<?php esc_attr_e( 'Click for Help!', 'buddyboss' ); ?>" onclick="toggleVisibility('sfwd-courses_course_video_url_tip');">
							<img alt="" src="<?php echo esc_url( buddypress()->plugin_url . 'bp-templates/bp-nouveau/readylaunch/images/question.png' ); ?>"/>
							<label for="buddyboss_lms_course_video" class="sfwd_label buddyboss_lms_course_video_label">
								<?php esc_html_e( 'Preview Video URL', 'buddyboss' ); ?>
							</label>
						</a>
					</span>
					<span class="sfwd_option_input">
						<div class="sfwd_option_div">
							<?php
							// Add a nonce field so we can check for it later.
							wp_nonce_field( 'bb_rl_learndash_course_video_meta_box', 'bb_rl_learndash_course_video_meta_box_nonce' );

							/*
							 * Use get_post_meta() to retrieve an existing value
							 * from the database and use the value for the form.
							 */
							$value = get_post_meta( $post->ID, '_buddyboss_lms_course_video', true );
							echo '<textarea id="buddyboss_lms_course_video" name="buddyboss_lms_course_video" rows="2" style="width:100%;">' . esc_attr( $value ) . '</textarea>';
							?>
						</div>
						<div class="sfwd_help_text_div" style="display:none" id="sfwd-courses_course_video_url_tip">
							<label class="sfwd_help_text">
								<?php esc_html_e( 'Enter preview video URL for the course. The video will be added on single course price box.', 'buddyboss' ); ?>
							</label>
						</div>
					</span>
					<p style="clear:left"></p>
				</div>
				<div class="sfwd_input">
					<span class="sfwd_option_label">
						<a class="sfwd_help_text_link" style="cursor:pointer;" title="<?php esc_attr_e( 'Click for Help!', 'buddyboss' ); ?>" onclick="toggleVisibility('sfwd-courses_course_video_duration_tip');">
							<img alt="" src="<?php echo esc_url( buddypress()->plugin_url . 'bp-templates/bp-nouveau/readylaunch/images/question.png' ); ?>"/>
							<label for="buddyboss_lms_course_video_duration" class="sfwd_label buddyboss_lms_course_video_duration_label">
								<?php esc_html_e( 'Video Duration', 'buddyboss' ); ?>
							</label>
						</a>
					</span>
					<span class="sfwd_option_input">
						<div class="sfwd_option_div">
							<?php
							$video_duration = get_post_meta( $post->ID, '_buddyboss_lms_course_video_duration', true );
							echo '<input type="text" id="buddyboss_lms_course_video_duration" name="buddyboss_lms_course_video_duration" value="' . esc_attr( $video_duration ) . '" style="width:100%;" />';
							?>
						</div>
						<div class="sfwd_help_text_div" style="display:none" id="sfwd-courses_course_video_duration_tip">
							<label class="sfwd_help_text">
								<?php esc_html_e( 'Enter the video duration to display on the preview. e.g., 0:45 or 1:20:30.', 'buddyboss' ); ?>
							</label>
						</div>
					</span>
					<p style="clear:left"></p>
				</div>
			</div>
			<?php
		}

		/**
		 * When the post is saved, saves our custom data.
		 *
		 * @since BuddyBoss 2.9.00
		 *
		 * @param int    $post_id The ID of the post being saved.
		 * @param object $post The post object.
		 */
		public function bb_rl_learndash_save_meta_boxes( $post_id, $post ) {
			/*
			 * We need to verify this came from our screen and with proper authorization,
			 * because the save_post action can be triggered at other times.
			 */

			// Check if our nonce is set.
			if (
				! isset( $_POST['bb_rl_learndash_course_video_meta_box_nonce'] ) ||
				! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['bb_rl_learndash_course_video_meta_box_nonce'] ) ), 'bb_rl_learndash_course_video_meta_box' )
			) {
				return;
			}

			// If this is an autosave, our form has not been submitted, so we don't want to do anything.
			if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
				return;
			}

			// Check the user's permissions.
			if ( isset( $_POST['post_type'] ) && 'page' === $_POST['post_type'] ) {

				if ( ! current_user_can( 'edit_page', $post_id ) ) {
					return;
				}
			} elseif ( ! current_user_can( 'edit_post', $post_id ) ) {
					return;
			}

			/* OK, it's safe for us to save the data now. */

			// Make sure that video url is set.
			if ( isset( $_POST['buddyboss_lms_course_video'] ) ) {
				// Sanitize user input.
				$data = sanitize_text_field( wp_unslash( $_POST['buddyboss_lms_course_video'] ) );

				// Update the meta field in the database.
				update_post_meta( $post_id, '_buddyboss_lms_course_video', $data );
			}

			// Make sure that video duration is set.
			if ( isset( $_POST['buddyboss_lms_course_video_duration'] ) ) {
				// Sanitize user input.
				$video_duration_data = sanitize_text_field( wp_unslash( $_POST['buddyboss_lms_course_video_duration'] ) );

				// Update the meta field in the database.
				update_post_meta( $post_id, '_buddyboss_lms_course_video_duration', $video_duration_data );
			}
		}

		/**
		 * Output the comment.
		 *
		 * @since BuddyBoss 2.9.00
		 *
		 * @param object $comment The comment object.
		 * @param array  $args    The arguments array.
		 * @param int    $depth   The depth of the comment.
		 */
		public function bb_rl_learndash_comment( $comment, $args, $depth ) {
			// phpcs:ignore WordPress.PHP.StrictComparisons.LooseComparison
			if ( 'div' === $args['style'] ) {
				$tag       = 'div';
				$add_below = 'comment';
			} else {
				$tag       = 'li';
				$add_below = 'div-comment';
			}
			?>

			<<?php echo esc_attr( $tag ); ?> <?php comment_class( $args['has_children'] ? 'parent' : '', $comment ); ?> id="comment-<?php comment_ID(); ?>">

			<article id="div-comment-<?php comment_ID(); ?>" class="comment-body">

				<?php
				if ( 0 !== (int) $args['avatar_size'] ) {
					if ( function_exists( 'bp_core_get_user_domain' ) ) {
						$user_link = bp_core_get_user_domain( $comment->user_id );
					} else {
						$user_link = get_comment_author_url( $comment );
					}
					?>
					<div class="comment-author vcard">
						<a href="<?php echo ! empty( $user_link ) ? esc_url( $user_link ) : ''; ?>">
							<?php echo get_avatar( $comment, $args['avatar_size'] ); ?>
						</a>
					</div>
				<?php } ?>

				<div class="comment-content-wrap">
					<div class="comment-meta comment-metadata">
						<?php
						printf(
						/* translators: %s: Author related metas. */
							__( '%s', 'buddyboss' ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped, WordPress.WP.I18n.NoEmptyStrings
							sprintf(
								'<cite class="fn comment-author"><a href="%s" rel="external nofollow ugc" class="url">%s</a></cite>',
								empty( $user_link ) ? '' : esc_url( $user_link ),
								get_comment_author_link( $comment )
							)
						);
						?>
						<a class="comment-date" href="<?php echo esc_url( get_comment_link( $comment, $args ) ); ?>">
							<?php
							printf(
							/* translators: %s: Author comment date. */
								__( '%1$s', 'buddyboss' ),  // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped, WordPress.WP.I18n.NoEmptyStrings
								get_comment_date( '', $comment ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped, WordPress.WP.I18n.NoEmptyStrings
								get_comment_time() // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped, WordPress.WP.I18n.NoEmptyStrings
							);
							?>
						</a>
					</div>

					<?php if ( '0' === (string) $comment->comment_approved ) { ?>
						<p>
							<em class="comment-awaiting-moderation"><?php esc_html_e( 'Your comment is awaiting moderation.', 'buddyboss' ); ?></em>
						</p>
					<?php } ?>

					<div class="comment-text">
						<?php
						comment_text(
							$comment,
							array_merge(
								$args,
								array(
									'add_below' => $add_below,
									'depth'     => $depth,
									'max_depth' => $args['max_depth'],
								)
							)
						);
						?>
					</div>

					<footer class="comment-footer">
						<?php
						comment_reply_link(
							array_merge(
								$args,
								array(
									'reply_text' => esc_html__( 'Reply', 'buddyboss' ),
									'add_below'  => $add_below,
									'depth'      => $depth,
									'max_depth'  => $args['max_depth'],
									'before'     => '',
									'after'      => '',
								)
							)
						);
						?>

						<?php edit_comment_link( esc_html__( 'Edit', 'buddyboss' ), '', '' ); ?>
					</footer>
				</div>
			</article>
			<?php
		}

		/**
		 * Get the latest modified date for all course content types.
		 *
		 * This function retrieves the modified date for:
		 * - Course itself.
		 * - All lessons in the course.
		 * - All topics under each lesson.
		 * - All quizzes (course-level, lesson-level, and topic-level).
		 *
		 * @since BuddyBoss 2.9.00
		 *
		 * @param int    $course_id The course ID.
		 * @param string $format    Optional. Date format. Defaults to WordPress date format option. Use 'raw' for MySQL format.
		 *
		 * @return string|false The latest modified date formatted according to WordPress options or false if no content found.
		 */
		public function bb_rl_get_course_latest_modified_date( $course_id, $format = '' ) {
			// Validate course ID.
			$course_id = absint( $course_id );
			if ( empty( $course_id ) ) {
				return false;
			}

			// Verify the course exists.
			$course_post = get_post( $course_id );
			if ( ! $course_post || learndash_get_post_type_slug( 'course' ) !== $course_post->post_type ) {
				return false;
			}

			$dates = array();

			// Add course modified date.
			$dates[] = get_post_field( 'post_modified', $course_id );

			// Get all lessons in the course.
			$lessons = learndash_get_course_lessons_list( $course_id, null, array( 'num' => 0 ) );

			if ( ! empty( $lessons ) && is_array( $lessons ) ) {
				foreach ( $lessons as $lesson ) {
					// Add lesson modified date.
					$dates[] = get_post_field( 'post_modified', $lesson['post']->ID );

					// Get topics under this lesson.
					$topics = learndash_get_topic_list( $lesson['post']->ID, $course_id );

					if ( ! empty( $topics ) && is_array( $topics ) ) {
						foreach ( $topics as $topic ) {
							// Add topic modified date.
							$dates[] = get_post_field( 'post_modified', $topic->ID );

							// Get quizzes under this topic.
							$topic_quizzes = learndash_get_lesson_quiz_list( $topic->ID, null, $course_id );

							if ( ! empty( $topic_quizzes ) && is_array( $topic_quizzes ) ) {
								foreach ( $topic_quizzes as $quiz ) {
									$dates[] = get_post_field( 'post_modified', $quiz['post']->ID );
								}
							}
						}
					}

					// Get quizzes under this lesson.
					$lesson_quizzes = learndash_get_lesson_quiz_list( $lesson['post']->ID, null, $course_id );

					if ( ! empty( $lesson_quizzes ) && is_array( $lesson_quizzes ) ) {
						foreach ( $lesson_quizzes as $quiz ) {
							$dates[] = get_post_field( 'post_modified', $quiz['post']->ID );
						}
					}
				}
			}

			// Get course-level quizzes.
			$course_quizzes = learndash_get_course_quiz_list( $course_id );

			if ( ! empty( $course_quizzes ) && is_array( $course_quizzes ) ) {
				foreach ( $course_quizzes as $quiz ) {
					$dates[] = get_post_field( 'post_modified', $quiz['post']->ID );
				}
			}

			// Filter out empty dates and return the latest.
			$dates = array_filter( $dates );

			if ( empty( $dates ) ) {
				return false;
			}

			$latest_date = max( $dates );

			// Return raw date if format is 'raw' or empty.
			if ( 'raw' === $format || empty( $format ) ) {
				return $latest_date;
			}

			// Use WordPress date format if no custom format provided.
			if ( 'default' === $format ) {
				$format = get_option( 'date_format' );
			}

			// Format the date according to WordPress standards.
			return date_i18n( $format, strtotime( $latest_date ) );
		}

		/**
		 * Format course expiration time in a human-readable format.
		 *
		 * This function automatically formats course expiration time based on the value:
		 * - Less than 1 minute = seconds
		 * - Less than 1 hour = minutes
		 * - Less than 1 day = hours
		 * - 1 day = 24 hours
		 * - More than 1 day = days
		 *
		 * @since BuddyBoss 2.9.00
		 *
		 * @param int $course_id The course ID.
		 *
		 * @return string|false Formatted time string or false if no expiration set.
		 */
		public function bb_rl_format_course_expiration_time( $course_id ) {
			// Validate course ID.
			$course_id = absint( $course_id );
			if ( empty( $course_id ) ) {
				return false;
			}

			// Get expiration days from course settings.
			$expiration_in_days = learndash_get_setting( $course_id, 'expire_access_days' );
			if ( empty( $expiration_in_days ) ) {
				return false;
			}

			// Convert days to seconds for more granular calculations.
			$total_seconds = (float) $expiration_in_days * 24 * 60 * 60;

			// Less than 1 minute (60 seconds).
			if ( $total_seconds < 60 ) {
				$seconds = (int) $total_seconds;
				return sprintf(
					// translators: %d = Number of seconds.
					esc_html( _n( '%d Second', '%d Seconds', $seconds, 'buddyboss' ) ),
					esc_html( $seconds )
				);
			}

			// Less than 1 hour (3600 seconds).
			if ( $total_seconds < 3600 ) {
				$minutes = (int) ( $total_seconds / 60 );
				return sprintf(
					// translators: %d = Number of minutes.
					esc_html( _n( '%d Minute', '%d Minutes', $minutes, 'buddyboss' ) ),
					esc_html( $minutes )
				);
			}

			// Less than 1 day (86400 seconds).
			if ( $total_seconds < 86400 ) {
				$hours = (int) ( $total_seconds / 3600 );
				return sprintf(
					// translators: %d = Number of hours.
					esc_html( _n( '%d Hour', '%d Hours', $hours, 'buddyboss' ) ),
					esc_html( $hours )
				);
			}

			// Exactly 1 day.
			if ( 1 === (int) $expiration_in_days ) {
				$hours = 24;
				return sprintf(
					// translators: %d = Number of hours.
					esc_html( _n( '%d Hour', '%d Hours', $hours, 'buddyboss' ) ),
					esc_html( $hours )
				);
			}

			// More than 1 day.
			$days = (int) $expiration_in_days;
			return sprintf(
				// translators: %d = Number of days.
				esc_html( _n( '%d Day', '%d Days', $days, 'buddyboss' ) ),
				esc_html( $days )
			);
		}

		/**
		 * Save the view type for the course.
		 *
		 * @since BuddyBoss 2.9.00
		 */
		public function bb_rl_lms_save_view() {
			$nonce = isset( $_REQUEST['nonce'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['nonce'] ) ) : '';
			if ( ! wp_verify_nonce( $nonce, 'list-grid-settings' ) ) {
				wp_send_json_error(
					array(
						'message' => __( 'Invalid request.', 'buddyboss' ),
					)
				);
				wp_die();
			}

			$object = isset( $_REQUEST['object'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['object'] ) ) : '';
			if ( empty( $object ) || 'ld-course' !== $object ) {
				wp_send_json_error(
					array(
						'message' => __( 'Not a valid object', 'buddyboss' ),
					)
				);
				wp_die();
			}

			$option_name = isset( $_REQUEST['option'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['option'] ) ) : '';
			if ( empty( $option_name ) || 'bb_layout_view' !== $option_name ) {
				wp_send_json_error(
					array(
						'message' => __( 'Not a valid option', 'buddyboss' ),
					)
				);
				wp_die();
			}

			// phpcs:ignore WordPress.Security.NonceVerification
			$option_value = isset( $_REQUEST['type'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['type'] ) ) : '';
			if ( ! in_array( $option_value, array( 'grid', 'list' ), true ) ) {
				wp_send_json_error(
					array(
						'message' => __( 'Not a valid value', 'buddyboss' ),
					)
				);
				wp_die();
			}

			if ( is_user_logged_in() ) {
				$existing_layout = get_user_meta( get_current_user_id(), $option_name, true );
				$existing_layout = ! empty( $existing_layout ) ? $existing_layout : array();
				// Store layout option in the db.
				$existing_layout[ $object ] = $option_value;
				update_user_meta( get_current_user_id(), $option_name, $existing_layout );
			} else {
				$existing_layout = ! empty( $_COOKIE[ $option_name ] ) ? json_decode( rawurldecode( sanitize_text_field( wp_unslash( $_COOKIE[ $option_name ] ) ) ), true ) : array();
				// Store layout option in the cookie.
				$existing_layout[ $object ] = $option_value;
				setcookie( $option_name, rawurlencode( wp_json_encode( $existing_layout ) ), time() + 31556926, '/', COOKIE_DOMAIN, false, false );
			}

			wp_send_json_success( array( 'html' => 'success' ) );
			wp_die();
		}
	}


	BB_Readylaunch_Learndash_Helper::instance();
}
