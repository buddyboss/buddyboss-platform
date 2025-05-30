<?php
/**
 * ReadyLaunch LearnDash Helper Functions
 *
 * @package BuddyBoss\Core
 * @since BuddyBoss [BBVERSION]
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * ReadyLaunch LearnDash Helper Class
 *
 * This class provides helper functions for LearnDash integration
 * when using ReadyLaunch templates without BuddyBoss theme.
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
		// Add pre_get_posts filter for course filtering.
		add_action( 'pre_get_posts', array( $this, 'bb_rl_filter_courses_query' ) );
	}

	/**
	 * Get all the URLs of current course (lesson, topic, quiz) for pagination.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param int   $course_id The course ID.
	 * @param array $lesson_list Array of lesson objects.
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
	 * @param array  $url_arr Array of URLs for navigation.
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
			$current_url = $protocol . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
		}

		// Normalize current URL.
		$current_url = trailingslashit( $current_url );
		if ( ! parse_url( $current_url, PHP_URL_QUERY ) ) {
			$current_url = trailingslashit( $current_url );
		}

		// Find current URL in the array.
		$key = array_search( urldecode( $current_url ), $url_arr );

		if ( false === $key ) {
			return array(
				'next' => '',
				'prev' => '',
			);
		}

		$url_result        = array();
		$keys              = array_keys( $url_arr );
		$current_key_index = array_search( $key, $keys );

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
				'<a href="%s" class="next-link" rel="next">%s<i class="bb-icons-rl-caret-right"></i></a>',
				esc_url( $next ),
				esc_html__( 'Next', 'buddyboss' ),
				esc_attr__( 'Next', 'buddyboss' )
			);
		}

		// Build previous link.
		$url_result['prev'] = '';
		if ( ! empty( $prev ) && $last_element !== $prev ) {
			$url_result['prev'] = sprintf(
				'<a href="%s" class="prev-link" rel="prev"><i class="bb-icons-rl-caret-left"></i> %s</a>',
				esc_url( $prev ),
				esc_attr__( 'Previous', 'buddyboss' ),
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
	 * @param int   $course_id The course ID.
	 * @param array $lesson_list Array of lesson objects.
	 * @param array $course_quizzes_list Optional. Array of course quizzes.
	 *
	 * @return array Array of navigation URLs with completion status.
	 */
	public function bb_rl_ld_custom_continue_url_arr( $course_id, $lesson_list, $course_quizzes_list = array() ) {
		$user_id = get_current_user_id();

		// Check course access for closed courses.
		if ( function_exists( 'learndash_get_course_meta_setting' ) ) {
			$course_price_type = learndash_get_course_meta_setting( $course_id, 'course_price_type' );
			if ( 'closed' === $course_price_type ) {
				if ( function_exists( 'learndash_user_get_enrolled_courses' ) ) {
					$user_courses = learndash_user_get_enrolled_courses( $user_id );
					if ( ! in_array( $course_id, $user_courses ) ) {
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
	 * @param int   $course_id The course ID.
	 * @param array $lesson_list Array of lesson objects.
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
	 * @param array  $url_arr Array of quiz URLs.
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
	 * @param WP_Query $query The WP_Query instance.
	 */
	public function bb_rl_filter_courses_query( $query ) {
		// Only modify the main query for course archive.
		if ( ! is_admin() && $query->is_main_query() && is_post_type_archive( 'sfwd-courses' ) ) {

			// Get filter values.
			$orderby    = isset( $_GET['orderby'] ) ? sanitize_text_field( wp_unslash( $_GET['orderby'] ) ) : '';
			$category   = isset( $_GET['categories'] ) ? sanitize_text_field( wp_unslash( $_GET['categories'] ) ) : '';
			$instructor = isset( $_GET['instructors'] ) ? sanitize_text_field( wp_unslash( $_GET['instructors'] ) ) : '';

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
}

BB_Readylaunch_Learndash_Helper::instance();
