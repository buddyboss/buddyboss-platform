<?php
/**
 * ReadyLaunch LifterLMS Helper Functions
 *
 * @since   BuddyBoss 2.9.00
 * @package BuddyBoss\Core
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

// Prevent duplicate class declarations.
if ( class_exists( 'BB_Readylaunch_LifterLMS_Helper' ) ) {
	return;
}

/**
 * ReadyLaunch LifterLMS Helper Class
 *
 * This class provides helper functions for LifterLMS integration
 * when using ReadyLaunch templates without BuddyBoss theme.
 *
 * @since BuddyBoss 2.9.00
 */
if ( ! class_exists( 'BB_Readylaunch_LifterLMS_Helper' ) ) {

	/**
	 * LifterLMS helper class.
	 *
	 * @since BuddyBoss 2.9.00
	 */
	class BB_Readylaunch_LifterLMS_Helper {

		/**
		 * The single instance of the class.
		 *
		 * @since BuddyBoss 2.9.00
		 * @var BB_Readylaunch_LifterLMS_Helper
		 */
		protected static $_instance = null;

		/**
		 * Main BB_Readylaunch_LifterLMS_Helper Instance.
		 *
		 * Ensures only one instance of BB_Readylaunch_LifterLMS_Helper is loaded or can be loaded.
		 *
		 * @since BuddyBoss 2.9.00
		 * @static
		 * @return BB_Readylaunch_LifterLMS_Helper - Main instance.
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
		 * @since BuddyBoss 2.9.00
		 */
		public function __construct() {
			// Add LifterLMS template path filters for ReadyLaunch.
			add_filter( 'lifterlms_theme_override_directories', array( $this, 'bb_rl_llms_add_template_paths' ), PHP_INT_MAX );

			// LifterLMS stylesheets.
			add_action( 'wp_enqueue_scripts', array( $this, 'bb_readylaunch_lifterlms_enqueue_styles' ), 10 );

			// Add actions for archive template.
			add_action( 'bb_rl_layout_before', array( $this, 'bb_rl_lifterlms_layout_before' ) );
			add_action( 'bb_rl_layout_after', array( $this, 'bb_rl_lifterlms_layout_after' ) );
			add_action( 'bb_rl_layout_before_loop', array( $this, 'bb_rl_lifterlms_before_loop' ) );
			add_action( 'bb_rl_layout_after_loop', array( $this, 'bb_rl_lifterlms_after_loop' ) );
			add_action( 'bb_rl_layout_no_posts', array( $this, 'bb_rl_lifterlms_no_posts' ) );

			// Add pre_get_posts filter for course filtering.
			add_action( 'pre_get_posts', array( $this, 'bb_rl_filter_courses_query' ) );

			// Add content filters for LifterLMS pages.
			add_filter( 'the_content', array( $this, 'bb_rl_lifterlms_content' ), 10, 2 );
		}

		/**
		 * Add ReadyLaunch template paths for LifterLMS.
		 *
		 * @since BuddyBoss 2.9.00
		 *
		 * @param array $dirs The current template override directories.
		 * @return array Modified template override directories.
		 */
		public function bb_rl_llms_add_template_paths( $dirs ) {
			if ( bb_is_readylaunch_enabled() && $this->bb_rl_is_page_enabled_for_integration( 'courses' ) ) {
				$readylaunch_dir = buddypress()->plugin_dir . 'bp-templates/bp-nouveau/readylaunch/lifterlms/';
				array_unshift( $dirs, $readylaunch_dir );
			}
			return $dirs;
		}







		/**
		 * Enqueue LifterLMS styles for ReadyLaunch.
		 *
		 * @since BuddyBoss 2.9.00
		 */
		public function bb_readylaunch_lifterlms_enqueue_styles() {
			if ( ! bb_is_readylaunch_enabled() || ! class_exists( 'LifterLMS' ) ) {
				return;
			}

			// Enqueue LifterLMS ReadyLaunch styles.
			wp_enqueue_style(
				'bb-readylaunch-lifterlms',
				buddypress()->plugin_url . 'bp-templates/bp-nouveau/readylaunch/css/courses.css',
				array(),
				bp_get_version()
			);

			// Enqueue our LifterLMS helper JavaScript.
			wp_enqueue_script(
				'bb-readylaunch-lifterlms-js',
				buddypress()->plugin_url . 'bp-templates/bp-nouveau/readylaunch/js/bb-readylaunch-lifterlms.js',
				array( 'jquery' ),
				bp_get_version(),
				true
			);

			wp_localize_script(
				'bb-readylaunch-lifterlms-js',
				'bbReadylaunchLifterLMS',
				array(
					'courses_url'     => home_url( '/courses/' ),
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
		public function bb_rl_lifterlms_layout_before() {
			if ( ! $this->bb_rl_is_lifterlms_page() ) {
				return;
			}

			// Add any necessary layout before content for LifterLMS pages.
			echo '<div class="bb-rl-lifterlms-wrapper">';
		}

		/**
		 * Fires after the layout.
		 *
		 * @since BuddyBoss 2.9.00
		 */
		public function bb_rl_lifterlms_layout_after() {
			if ( ! $this->bb_rl_is_lifterlms_page() ) {
				return;
			}

			// Close the LifterLMS wrapper.
			echo '</div>';
		}

		/**
		 * Fires before the loop.
		 *
		 * @since BuddyBoss 2.9.00
		 */
		public function bb_rl_lifterlms_before_loop() {
			if ( ! $this->bb_rl_is_lifterlms_page() ) {
				return;
			}

			// Add any necessary content before the loop for LifterLMS pages.
			if ( is_post_type_archive( 'course' ) ) {
				echo '<div class="bb-rl-lifterlms-courses-header">';
				echo '<h1>' . esc_html__( 'Courses', 'buddyboss' ) . '</h1>';
				echo '</div>';
			}
		}

		/**
		 * Fires after the loop.
		 *
		 * @since BuddyBoss 2.9.00
		 */
		public function bb_rl_lifterlms_after_loop() {
			if ( ! $this->bb_rl_is_lifterlms_page() ) {
				return;
			}

			// Add any necessary content after the loop for LifterLMS pages.
		}

		/**
		 * Fires when no posts are found.
		 *
		 * @since BuddyBoss 2.9.00
		 */
		public function bb_rl_lifterlms_no_posts() {
			if ( ! $this->bb_rl_is_lifterlms_page() ) {
				return;
			}

			echo '<div class="bb-rl-lifterlms-no-posts">';
			echo '<p>' . esc_html__( 'No courses found.', 'buddyboss' ) . '</p>';
			echo '</div>';
		}

		/**
		 * Filter courses query for LifterLMS.
		 *
		 * @since BuddyBoss 2.9.00
		 *
		 * @param WP_Query $query The query object.
		 */
		public function bb_rl_filter_courses_query( $query ) {
			if ( ! $this->bb_rl_is_lifterlms_page() || ! $query->is_main_query() ) {
				return;
			}

			// Add any custom query modifications for LifterLMS courses.
			if ( is_post_type_archive( 'course' ) ) {
				$query->set( 'posts_per_page', 12 );
				$query->set( 'orderby', 'date' );
				$query->set( 'order', 'DESC' );
			}
		}

		/**
		 * Modify content for LifterLMS pages.
		 *
		 * @since BuddyBoss 2.9.00
		 *
		 * @param string $content The content.
		 * @return string Modified content.
		 */
		public function bb_rl_lifterlms_content( $content ) {
			if ( ! $this->bb_rl_is_lifterlms_page() ) {
				return $content;
			}

			// Add any content modifications for LifterLMS pages.
			return $content;
		}

		/**
		 * Check if the current page is a LifterLMS page.
		 *
		 * @since BuddyBoss 2.9.00
		 *
		 * @return bool True if the current page is a LifterLMS page, false otherwise.
		 */
		public function bb_rl_is_lifterlms_page() {
			if ( ! class_exists( 'LifterLMS' ) || ! $this->bb_rl_is_page_enabled_for_integration( 'courses' ) ) {
				return false;
			}

			global $post, $wp_query;

			// Check for LifterLMS post types.
			$lifterlms_post_types = array(
				'course',
				'lesson',
				'llms_quiz',
				'llms_question',
				'llms_certificate',
				'llms_my_certificate',
				'llms_achievement',
				'llms_my_achievement',
				'llms_engagement',
				'llms_email',
				'llms_voucher',
				'llms_coupon',
				'llms_order',
				'llms_transaction',
				'llms_access_plan',
				'llms_membership',
			);

			// Check if current post type is a LifterLMS type.
			if ( isset( $post ) && is_a( $post, 'WP_Post' ) ) {
				if ( in_array( $post->post_type, $lifterlms_post_types, true ) ) {
					return true;
				}
			}

			// Check for LifterLMS archive pages.
			if ( is_post_type_archive( 'course' ) || is_post_type_archive( 'llms_membership' ) ) {
				return true;
			}

			// Check for LifterLMS taxonomy pages.
			$lifterlms_taxonomies = array(
				'course_cat',
				'course_tag',
				'course_track',
				'course_difficulty',
				'membership_cat',
				'membership_tag',
			);

			if ( is_tax( $lifterlms_taxonomies ) ) {
				return true;
			}

			// Check for LifterLMS specific URLs.
			$current_url = $_SERVER['REQUEST_URI'] ?? '';
			$lifterlms_patterns = array(
				'/courses/',
				'/memberships/',
				'/my-courses/',
				'/my-memberships/',
				'/certificates/',
				'/achievements/',
			);

			foreach ( $lifterlms_patterns as $pattern ) {
				if ( strpos( $current_url, $pattern ) !== false ) {
					return true;
				}
			}

			return false;
		}

		/**
		 * Check if the current page is enabled for integration.
		 *
		 * @since BuddyBoss 2.9.00
		 *
		 * @param string $page The page type to check.
		 * @return bool True if the page is enabled for integration, false otherwise.
		 */
		private function bb_rl_is_page_enabled_for_integration( $page ) {
			$enabled_pages = bp_get_option( 'bb_rl_enabled_pages' );

			return ! empty( $enabled_pages[ $page ] );
		}

		/**
		 * Get course instructor for LifterLMS.
		 *
		 * @since BuddyBoss 2.9.00
		 *
		 * @param int $course_id The course ID.
		 * @return array|false The instructor data or false if not found.
		 */
		public function bb_rl_get_course_instructor( $course_id ) {
			if ( ! class_exists( 'LifterLMS' ) ) {
				return false;
			}

			$course = llms_get_post( $course_id );
			if ( ! $course || ! is_a( $course, 'LLMS_Course' ) ) {
				return false;
			}

			$instructors = $course->get_instructors();
			if ( empty( $instructors ) ) {
				return false;
			}

			$instructor = $instructors[0];
			return array(
				'id'     => $instructor['id'],
				'name'   => $instructor['name'],
				'bio'    => $instructor['bio'],
				'avatar' => get_avatar_url( $instructor['id'] ),
			);
		}

		/**
		 * Get course progress for a user.
		 *
		 * @since BuddyBoss 2.9.00
		 *
		 * @param int $user_id The user ID.
		 * @param int $course_id The course ID.
		 * @return array The course progress data.
		 */
		public function bb_rl_get_course_progress( $user_id, $course_id ) {
			if ( ! class_exists( 'LifterLMS' ) ) {
				return array();
			}

			$course = llms_get_post( $course_id );
			if ( ! $course || ! is_a( $course, 'LLMS_Course' ) ) {
				return array();
			}

			$student = llms_get_student( $user_id );
			if ( ! $student ) {
				return array();
			}

			$progress = $student->get_progress( $course_id );
			return array(
				'percentage' => $progress['percentage'],
				'completed'  => $progress['completed'],
				'total'      => $progress['total'],
			);
		}

		/**
		 * Get course enrollment status for a user.
		 *
		 * @since BuddyBoss 2.9.00
		 *
		 * @param int $user_id The user ID.
		 * @param int $course_id The course ID.
		 * @return string The enrollment status.
		 */
		public function bb_rl_get_course_enrollment_status( $user_id, $course_id ) {
			if ( ! class_exists( 'LifterLMS' ) ) {
				return 'not_enrolled';
			}

			$student = llms_get_student( $user_id );
			if ( ! $student ) {
				return 'not_enrolled';
			}

			$status = $student->get_enrollment_status( $course_id );
			return $status ? $status : 'not_enrolled';
		}
	}
} 