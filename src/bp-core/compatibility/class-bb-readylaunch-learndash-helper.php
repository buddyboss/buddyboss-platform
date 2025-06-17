<?php
/**
 * ReadyLaunch LearnDash Helper Functions
 *
 * @since   BuddyBoss [BBVERSION]
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
 * @since BuddyBoss [BBVERSION]
 */
if ( ! class_exists( 'BB_Readylaunch_Learndash_Helper' ) ) {

	/**
	 * LearnDash helper class.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	class BB_Readylaunch_Learndash_Helper {

		/**
		 * The single instance of the class.
		 *
		 * @since BuddyBoss [BBVERSION]
		 * @var BB_Readylaunch_Learndash_Helper
		 */
		protected static $_instance = null;

		/**
		 * Main BB_Readylaunch_Learndash_Helper Instance.
		 *
		 * Ensures only one instance of BB_Readylaunch_Learndash_Helper is loaded or can be loaded.
		 *
		 * @since BuddyBoss [BBVERSION]
		 * @static
		 * @return BB_Readylaunch_Learndash_Helper - Main instance.
		 */
		public static function instance() {
			if ( is_null( self::$_instance ) ) {
				self::$_instance = new self();
			}

			return self::$_instance;
		}

		/**
		 * Constructor.
		 *
		 * @since BuddyBoss [BBVERSION]
		 */
		public function __construct() {
			remove_all_filters( 'learndash_template' );
			add_filter( 'learndash_template', array( $this, 'bb_rl_override_learndash_template_path' ), 99999, 5 );

			// Add actions for archive template.
			add_action( 'bb_rl_layout_before', array( $this, 'bb_rl_learndash_layout_before' ) );
			add_action( 'bb_rl_layout_after', array( $this, 'bb_rl_learndash_layout_after' ) );
			add_action( 'bb_rl_layout_before_loop', array( $this, 'bb_rl_learndash_before_loop' ) );
			add_action( 'bb_rl_layout_after_loop', array( $this, 'bb_rl_learndash_after_loop' ) );
			add_action( 'bb_rl_layout_no_posts', array( $this, 'bb_rl_learndash_no_posts' ) );

			// Add pre_get_posts filter for course filtering.
			add_action( 'pre_get_posts', array( $this, 'bb_rl_filter_courses_query' ) );
			add_filter( 'learndash_lesson_row_class', array( $this, 'bb_rl_learndash_lesson_row_class' ), 10, 2 );

			add_filter( 'buddyboss_learndash_content', array( $this, 'bb_rl_learndash_content' ), 10, 2 );
			add_action( 'learndash_update_user_activity', array( $this, 'bb_rl_flush_ld_courses_progress_cache' ), 9999, 1 );
		}

		/**
		 * Override LearnDash template path to use ReadyLaunch templates
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param string     $filepath         Template file path.
		 * @param string     $name             Template name.
		 * @param array|null $args             Template data.
		 * @param bool|null  $echo             Whether to echo the template output or not.
		 * @param bool       $return_file_path Whether to return file or path or not.
		 *
		 * @return string Modified template path
		 */
		public function bb_rl_override_learndash_template_path( $filepath, $name, $args, $echo, $return_file_path ) {

			// Get template name without extension.
			$template_name = str_replace( '.php', '', basename( $name ) );

			// Try to load template using bp_get_template_part.
			$template = bp_locate_template(
				array(
					"learndash/ld30/{$template_name}.php",
				)
			);

			if ( $template ) {
				return $template;
			}

			return $filepath;
		}

		/**
		 * Fires before the layout.
		 *
		 * @since BuddyBoss [BBVERSION]
		 */
		public function bb_rl_learndash_layout_before() {
			if ( is_post_type_archive( learndash_get_post_type_slug( 'course' ) ) ) {
				bp_get_template_part( 'learndash/ld30/archive-course-header' );
			}
		}

		/**
		 * Fires after the layout.
		 *
		 * @since BuddyBoss [BBVERSION]
		 */
		public function bb_rl_learndash_layout_after() {
			if ( is_post_type_archive( learndash_get_post_type_slug( 'course' ) ) ) {
				bp_get_template_part( 'learndash/ld30/archive-course-footer' );
			}
		}

		/**
		 * Fires before the loop starts.
		 *
		 * @since BuddyBoss [BBVERSION]
		 */
		public function bb_rl_learndash_before_loop() {
			if ( is_post_type_archive( learndash_get_post_type_slug( 'course' ) ) ) {
				echo '<div class="bb-rl-courses-grid grid bb-rl-courses-grid--ldlms">';
			}
		}

		/**
		 * Fires after the loop ends.
		 *
		 * @since BuddyBoss [BBVERSION]
		 */
		public function bb_rl_learndash_after_loop() {
			if ( is_post_type_archive( learndash_get_post_type_slug( 'course' ) ) ) {
				echo '</div>';
				bp_get_template_part( 'learndash/ld30/archive-course-pagination' );
			}
		}

		/**
		 * Fires when no posts are found.
		 *
		 * @since BuddyBoss [BBVERSION]
		 */
		public function bb_rl_learndash_no_posts() {
			bp_get_template_part( 'learndash/ld30/archive-no-course' );
		}

		/**
		 * Get all the URLs of current course (lesson, topic, quiz) for pagination.
		 *
		 * @since BuddyBoss [BBVERSION]
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
		 * @since BuddyBoss [BBVERSION]
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
		 * @since BuddyBoss [BBVERSION]
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
		 * @since BuddyBoss [BBVERSION]
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
		 * @since BuddyBoss [BBVERSION]
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
			if ( empty( $current_url ) ) {
				$protocol    = is_ssl() ? 'https' : 'http';
				$current_url = $protocol . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
			}

			// Normalize current URL.
			$current_url = trailingslashit( $current_url );

			$key = array_search( $current_url, $url_arr );

			return false !== $key ? $key + 1 : 0;
		}

		/**
		 * Filter the main query for courses based on filters
		 *
		 * @since BuddyBoss [BBVERSION]
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
		 * @since BuddyBoss [BBVERSION]
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
		 * @since BuddyBoss [BBVERSION]
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
		 * @since BuddyBoss [BBVERSION]
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
							?>
							<span class="bb-rl-enrolled-count">
								<?php
								printf(
								/* translators: %d is the number of enrolled users. */
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
		 * Add custom classes to lesson rows in LearnDash sidebar.
		 * Adds bb-rl-current-lesson class for current lesson or parent lesson of current topic.
		 *
		 * @since BuddyBoss [BBVERSION]
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

					if ( $quiz_lesson_id ) {
						if ( isset( $lesson['post']->ID ) && (int) $lesson['post']->ID === (int) $quiz_lesson_id ) {
							$add_current_class = true;
						}
					} elseif ( $quiz_topic_id ) {
						$topic_lesson_id = learndash_get_setting( $quiz_topic_id, 'lesson' );
						if ( isset( $lesson['post']->ID ) && (int) $lesson['post']->ID === (int) $topic_lesson_id ) {
							$add_current_class = true;
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
		 * Get the orderby data.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @return array The orderby data.
		 */
		public function bb_rl_get_orderby_data() {
			$current_orderby    = isset( $_GET['orderby'] ) ? sanitize_text_field( wp_unslash( $_GET['orderby'] ) ) : 'alphabetical';
			$current_category   = isset( $_GET['categories'] ) ? sanitize_text_field( wp_unslash( $_GET['categories'] ) ) : '';
			$current_instructor = isset( $_GET['instructors'] ) ? sanitize_text_field( wp_unslash( $_GET['instructors'] ) ) : '';

			return array(
				'current_orderby'    => $current_orderby,
				'current_category'   => $current_category,
				'current_instructor' => $current_instructor,
			);
		}

		/**
		 * Get course instructors including main author and shared instructors.
		 *
		 * @since BuddyBoss [BBVERSION]
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
		 * @since BuddyBoss [BBVERSION]
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
				if ( LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Courses_Builder', 'shared_steps' ) == 'yes' ) {
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
		 * @since BuddyBoss [BBVERSION]
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
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param array $args Details of the learndash activity.
		 */
		public function bb_rl_flush_ld_courses_progress_cache( $args ) {
			if ( ! empty( $args['user_id'] ) ) {
				wp_cache_delete( absint( $args['user_id'] ), 'ld_courses_progress' );
			}
		}
	}


	BB_Readylaunch_Learndash_Helper::instance();
}
