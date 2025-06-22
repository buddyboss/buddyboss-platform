<?php
/**
 * ReadyLaunch Memberpress Courses Integration Class
 *
 * @package BuddyBoss\Core
 * @since   BuddyBoss [BBVERSION]
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

use memberpress\courses as base;
use memberpress\courses\models as models;
use memberpress\courses\helpers as helpers;

/**
 * ReadyLaunch Memberpress Courses Integration Class
 *
 * This class provides helper functions for Memberpress Courses integration
 * when using ReadyLaunch templates without BuddyBoss theme.
 *
 * @since BuddyBoss [BBVERSION]
 */
class BB_Readylaunch_Memberpress_Courses_Integration {

	/**
	 * The single instance of the class.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @var BB_Readylaunch_Memberpress_Courses_Integration
	 */
	protected static $_instance = null;

	/**
	 * Main BB_Readylaunch_Memberpress_Courses_Integration Instance.
	 *
	 * Ensures only one instance of BB_Readylaunch_Memberpress_Courses_Integration is loaded or can be loaded.
	 *
	 * @since  BuddyBoss [BBVERSION]
	 * @static
	 *
	 * @return BB_Readylaunch_Memberpress_Courses_Integration - Main instance.
	 */
	public static function instance(): BB_Readylaunch_Memberpress_Courses_Integration {
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
		// Constructor can be used for initialization if needed.
		if ( bb_is_readylaunch_enabled() && function_exists( 'bb_meprlms_override_template' ) ) {
			remove_filter( 'template_include', 'bb_meprlms_override_template', PHP_INT_MAX );
		}

		// Use wp_footer hook after all scripts are registered
		add_action( 'wp_footer', array( $this, 'bb_rl_meprlms_add_script' ), 10 );

		add_filter( 'the_content', array( $this, 'bb_rl_meprlms_add_course_description' ), 9 );
	}

	/**
	 * Add the course description to the content.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param string $content The content of the post.
	 *
	 * @return string The content of the post with the course description.
	 */
	public function bb_rl_meprlms_add_course_description( $content ) {
		global $post;
		if ( is_single() && ! empty( $post ) && is_a( $post, 'WP_Post' ) ) {
			if ( class_exists( 'memberpress\courses\models\Course' ) && models\Course::$cpt === $post->post_type ) {
				return '<div class="bb-rl-course-description"><h2>' . esc_html__( 'About this course', 'buddyboss' ) . '</h2>' . $content . '</div>';
			}
		}
		return $content;
	}

	/**
	 * Get the template path for MemberPress Courses.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return string|false The full path to the template if it exists, or false if not.
	 */
	public static function bb_rl_meprlms_get_template() {
		global $post;

		$custom_template = false;

		if ( bb_is_readylaunch_enabled() && class_exists( 'memberpress\courses\helpers\App' ) && ! helpers\App::is_classroom() ) {
			// Handle course archive pages using MemberPress's reliable detection method.
			if ( class_exists( 'memberpress\courses\helpers\Courses' ) && helpers\Courses::is_course_archive() ) {
				$custom_template = self::bb_rl_meprlms_get_template_path( 'archive-mpcs-courses.php' );
			} elseif ( is_single() && ! empty( $post ) && is_a( $post, 'WP_Post' ) ) {
				$post_type = $post->post_type;

				if ( class_exists( 'memberpress\courses\models\Course' ) && models\Course::$cpt === $post_type ) {
					// Handle single course pages.
					$custom_template = self::bb_rl_meprlms_get_template_path( 'single-mpcs-course.php' );
				} elseif ( class_exists( 'memberpress\courses\models\Lesson' ) && models\Lesson::$cpt === $post_type ) {
					// Handle single lesson pages.
					$custom_template = self::bb_rl_meprlms_get_template_path( 'single-mpcs-lesson.php' );
				} elseif ( class_exists( 'memberpress\assignments\models\Assignment' ) && memberpress\assignments\models\Assignment::$cpt === $post_type ) {
					// Handle single assignment pages.
					$custom_template = self::bb_rl_meprlms_get_template_path( 'single-mpcs-assignment.php', 'assignments' );
				} elseif ( class_exists( 'memberpress\quizzes\models\Quiz' ) && memberpress\quizzes\models\Quiz::$cpt === $post_type ) {
					// Handle single quiz pages.
					$custom_template = self::bb_rl_meprlms_get_template_path( 'single-mpcs-quiz.php', 'quizzes' );
				}
			}
		}

		return $custom_template;
	}

	/**
	 * Get the correct template path if it exists.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param string $template_name The name of the template file.
	 * @param string $relative_path Optional. Relative path name. Default 'courses'.
	 *
	 * @return string|false The full path to the template if it exists, or false if not.
	 */
	public static function bb_rl_meprlms_get_template_path( $template_name, $relative_path = 'courses' ) {
		// $stylesheet_path = get_stylesheet_directory();
		// $template_path   = get_template_directory();
		// $is_child_theme  = is_child_theme();
		$template_paths  = array();

		// if ( $is_child_theme ) {
		// 	$template_paths[] = $template_path . '/memberpress/' . $relative_path . '/' . $template_name;
		// }

		// $template_paths[] = $stylesheet_path . '/memberpress/' . $relative_path . '/' . $template_name;
		$template_paths[] = self::bb_rl_meprlms_integration_path( '/memberpress/' . $relative_path . '/' . $template_name );

		// Return the first valid template path found.
		foreach ( $template_paths as $path ) {
			if ( $path && file_exists( $path ) ) {
				return $path;
			}
		}

		return false;
	}

	/**
	 * Get the integration path.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param string $path The path to append to the integration directory.
	 *
	 * @return string Template path.
	 */
	public static function bb_rl_meprlms_integration_path( $path ) {
		return trailingslashit( buddypress()->themes_dir ) . 'bp-nouveau/readylaunch/' . trim( $path, '/\\' );
	}

	/**
	 * Add script and styles for MemberPress Courses integration.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	public function bb_rl_meprlms_add_script() {
		global $post;

		if ( ! bb_is_readylaunch_enabled() ) {
			return;
		}

		// Handle course archive pages.
		if ( class_exists( 'memberpress\courses\helpers\Courses' ) && helpers\Courses::is_course_archive() ) {
			$this->enqueue_classroom_assets();
			return;
		}

		// Fallback: Handle course archive pages using WordPress method.
		if ( is_post_type_archive( models\Course::$cpt ) ) {
			$this->enqueue_classroom_assets();
			return;
		}

		// Handle single post pages.
		if ( ! is_single() || empty( $post ) || ! is_a( $post, 'WP_Post' ) ) {
			return;
		}

		$post_type = $post->post_type;

		// Handle lesson posts.
		if ( class_exists( 'memberpress\courses\models\Lesson' ) && in_array( $post_type, models\Lesson::lesson_cpts(), true ) ) {
			$this->enqueue_lesson_assets();
		}

		// Handle course and lesson posts for common assets.
		if ( models\Course::$cpt === $post_type || models\Lesson::$cpt === $post_type ) {
			$this->enqueue_course_lesson_common_assets();
		}

		// Handle assignment posts.
		if ( class_exists( 'memberpress\assignments\models\Assignment' ) && memberpress\assignments\models\Assignment::$cpt === $post_type ) {
			$this->enqueue_assignment_assets( $post->ID );
		}

		// Handle quiz posts.
		if ( class_exists( 'memberpress\quizzes\models\Quiz' ) && memberpress\quizzes\models\Quiz::$cpt === $post_type ) {
			$this->enqueue_quiz_assets( $post->ID );
		}
	}

	/**
	 * Enqueue classroom assets.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	private function enqueue_classroom_assets() {
		// Check if the script is registered
		if ( wp_script_is( 'mpcs-classroom-js', 'registered' ) ) {
			wp_enqueue_script( 'mpcs-classroom-js' );
		} else {
			// Register the script ourselves using MemberPress constants
			if ( defined( 'memberpress\\courses\\JS_URL' ) && defined( 'memberpress\\courses\\VERSION' ) ) {
				$js_url = memberpress\courses\JS_URL . '/classroom.js';
				$version = memberpress\courses\VERSION;
				
				wp_register_script( 'mpcs-classroom-js', $js_url, array( 'jquery' ), $version, true );
				wp_enqueue_script( 'mpcs-classroom-js' );
			}
		}
	}

	/**
	 * Enqueue lesson-specific assets.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	private function enqueue_lesson_assets() {
		wp_enqueue_style( 'mpcs-lesson-css' );
		wp_enqueue_script( 'mpcs-lesson' );
	}

	/**
	 * Enqueue common assets for courses and lessons.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	private function enqueue_course_lesson_common_assets() {
		// Enqueue clipboard functionality.
		wp_enqueue_script( 'mpcs-clipboard-js' );

		// Enqueue tooltipster styles and scripts.
		wp_enqueue_style( 'mpcs-tooltipster' );
		wp_enqueue_style( 'mpcs-tooltipster-borderless' );
		wp_enqueue_script( 'mpcs-tooltipster' );

		// Enqueue progress assets.
		wp_enqueue_style( 'mpcs-progress' );
		wp_enqueue_script( 'mpcs-progress-js' );

		// Enqueue classroom and fontello assets.
		wp_enqueue_script( 'mpcs-classroom-js' );
		wp_enqueue_style( 'mpcs-fontello-styles' );

		// Enqueue WordPress block gallery styles.
		wp_enqueue_style( 'wp-block-gallery' );
	}

	/**
	 * Enqueue assignment-specific assets.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param int $post_id The assignment post ID.
	 */
	private function enqueue_assignment_assets( $post_id ) {
		$assignment = memberpress\assignments\models\Assignment::find( $post_id );

		if ( ! $assignment instanceof memberpress\assignments\models\Assignment ) {
			return;
		}

		wp_enqueue_style( 'mpcs-assignment' );
		wp_enqueue_script( 'mpcs-assignment' );
	}

	/**
	 * Enqueue quiz-specific assets.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param int $post_id The quiz post ID.
	 */
	private function enqueue_quiz_assets( $post_id ) {
		$quiz = memberpress\quizzes\models\Quiz::find( $post_id );

		if ( ! $quiz instanceof memberpress\quizzes\models\Quiz ) {
			return;
		}

		wp_enqueue_style( 'mpcs-quiz' );
		wp_enqueue_script( 'mpcs-quiz' );
		wp_enqueue_script( 'jquery-scrollto' );
		wp_enqueue_script( 'sortablejs' );
	}

	/**
	 * Dequeue frontend styles and scripts from pro plugin.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	public function dequeue_pro_frontend_styles() {
		// Only dequeue if ReadyLaunch is enabled.
		if ( bb_is_readylaunch_enabled() ) {
			wp_dequeue_style( 'bb-meprlms-frontend' );
			wp_dequeue_script( 'bb-meprlms-frontend' );
		}
	}
}
