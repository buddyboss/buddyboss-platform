<?php
/**
 * ReadyLaunch MemberPress Courses Helper Class
 *
 * @package BuddyBoss\Core
 * @since   BuddyBoss 2.9.00
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

use memberpress\courses\models;
use memberpress\courses\helpers;
use memberpress\courses\lib;

/**
 * ReadyLaunch MemberPress Courses Helper Class
 *
 * This class provides helper functions for MemberPress Courses integration
 * when using ReadyLaunch templates without the BuddyBoss theme.
 *
 * @since BuddyBoss 2.9.00
 */
class BB_Readylaunch_Memberpress_Courses_Helper {

	/**
	 * The single instance of the class.
	 *
	 * @since BuddyBoss 2.9.00
	 *
	 * @var BB_Readylaunch_Memberpress_Courses_Helper
	 */
	protected static $instance = null;

	/**
	 * Main BB_Readylaunch_Memberpress_Courses_Helper Instance.
	 *
	 * Ensures only one instance of BB_Readylaunch_Memberpress_Courses_Helper is loaded or can be loaded.
	 *
	 * @since  BuddyBoss 2.9.00
	 * @static
	 *
	 * @return BB_Readylaunch_Memberpress_Courses_Helper - Main instance.
	 */
	public static function instance(): BB_Readylaunch_Memberpress_Courses_Helper {
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
		// Constructor can be used for initialization if needed.

		if ( bb_is_readylaunch_enabled() && ! empty( bp_get_option( 'bb_rl_enabled_pages' )['courses'] ) ) {

			if ( helpers\App::is_classroom() ) {
				// Remove MemberPress admin bar hiding hook.
				$this->bb_rl_remove_memberpress_admin_bar_hook();
			}

			if ( function_exists( 'bb_meprlms_override_template' ) ) {
				remove_filter( 'template_include', 'bb_meprlms_override_template', PHP_INT_MAX );
			}
			if ( function_exists( 'bb_meprlms_template_paths' ) ) {
				remove_filter( 'mepr_view_paths', 'bb_meprlms_template_paths', PHP_INT_MAX );
			}
			if ( function_exists( 'bb_meprlms_quizzes_template_paths' ) ) {
				remove_filter( 'mepr_mpcs_quizzes_view_paths', 'bb_meprlms_quizzes_template_paths', PHP_INT_MAX );
			}
			if ( function_exists( 'bb_meprlms_gradebook_template_paths' ) ) {
				remove_filter( 'mepr_mpcs_gradebook_view_paths', 'bb_meprlms_gradebook_template_paths', PHP_INT_MAX );
			}

			// Add MemberPress template path filters for ReadyLaunch.
			add_filter( 'mepr_view_paths', array( $this, 'bb_rl_mpcs_add_template_paths' ), PHP_INT_MAX );
			add_filter( 'mepr_mpcs_gradebook_view_paths', array( $this, 'bb_rl_mpcs_add_assignments_template_paths' ), PHP_INT_MAX );
			add_filter( 'mpcs_gradebook_view_paths', array( $this, 'bb_rl_mpcs_add_gradebook_template_paths' ), PHP_INT_MAX );
			add_filter( 'mepr_mpcs_quizzes_view_paths', array( $this, 'bb_rl_mpcs_add_quizzes_template_paths' ), PHP_INT_MAX );

			add_action( 'bb_rl_layout_before_loop', array( $this, 'bb_rl_mpcs_before_loop' ) );
			add_action( 'bb_rl_layout_after_loop', array( $this, 'bb_rl_mpcs_after_loop' ) );
			add_action( 'bb_rl_layout_no_posts', array( $this, 'bb_rl_mpcs_no_posts' ) );

			// Use wp_footer hook after all scripts are registered.
			add_action( 'wp_footer', array( $this, 'bb_rl_meprlms_add_script' ), 10 );
			add_filter( 'the_content', array( $this, 'bb_rl_meprlms_add_course_description' ), 9 );
			add_filter( 'mpcs_classroom_style_handles', array( $this, 'bb_rl_mpcs_override_readylaunch_styles' ) );

			// Add "Back to Course" button to lesson sidebar menu.
			add_action( 'mpcs_classroom_start_sidebar', array( $this, 'bb_rl_mpcs_add_back_to_course_button' ), 10 );

			// Add rewrite rules for lesson comments pagination.
			add_action( 'init', array( $this, 'bb_rl_mpcs_add_rewrite_rules' ) );
			add_filter( 'query_vars', array( $this, 'bb_rl_mpcs_add_query_vars' ) );

			add_filter( 'comments_template', array( $this, 'bb_rl_mpcs_add_comments_template' ), PHP_INT_MAX, 1 );
		}
	}

	/**
	 * Remove MemberPress admin bar hiding hook to keep WordPress admin bar retain it's default behavior.
	 *
	 * This method removes the MemberPress Classroom controller's admin bar hiding hook
	 * when BuddyBoss ReadyLaunch is enabled, allowing the WordPress admin bar to retain it's default behavior.
	 *
	 * @since BuddyBoss 2.9.00
	 */
	private function bb_rl_remove_memberpress_admin_bar_hook() {

		// If the Classroom controller is instantiated, try to remove from the instance.
		if ( class_exists( 'memberpress\courses\controllers\Classroom' ) ) {
			global $wp_filter;

			// Check if the hook exists and remove it.
			if ( isset( $wp_filter['show_admin_bar'] ) ) {
				foreach ( $wp_filter['show_admin_bar']->callbacks as $priority => $callbacks ) {
					foreach ( $callbacks as $key => $callback ) {
						if (
							is_array( $callback['function'] ) &&
							is_object( $callback['function'][0] ) &&
							'memberpress\courses\controllers\Classroom' === get_class( $callback['function'][0] ) &&
							'maybe_hide_admin_bar' === $callback['function'][1]
						) {
							remove_filter( 'show_admin_bar', $callback['function'], $priority );
						}
					}
				}
			}
		}
	}

	/**
	 * Add the course description to the content.
	 *
	 * @since BuddyBoss 2.9.00
	 *
	 * @param string $content The content of the post.
	 *
	 * @return string The content of the post with the course description.
	 */
	public function bb_rl_meprlms_add_course_description( $content ) {
		global $post;
		if ( is_single() && ! empty( $post ) && is_a( $post, 'WP_Post' ) ) {
			if ( class_exists( 'memberpress\courses\models\Course' ) && models\Course::$cpt === $post->post_type ) {
				return '<div class="bb-rl-course-description"><h2>' . esc_html__( 'About course', 'buddyboss' ) . '</h2>' . $content . '</div>';
			}
		}
		return $content;
	}

	/**
	 * Add script and styles for MemberPress Courses integration.
	 *
	 * @since BuddyBoss 2.9.00
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

		// Handle single course pages.
		if ( class_exists( 'memberpress\courses\models\Course' ) && models\Course::$cpt === $post_type ) {
			$this->enqueue_classroom_assets();
		}

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
	 * @since BuddyBoss 2.9.00
	 */
	private function enqueue_classroom_assets() {
		// Check if the script is registered.
		if ( wp_script_is( 'mpcs-classroom-js', 'registered' ) ) {
			wp_enqueue_script( 'mpcs-classroom-js' );
		} else {
			// Register the script ourselves using MemberPress constants.
			if ( defined( 'memberpress\\courses\\JS_URL' ) && defined( 'memberpress\\courses\\VERSION' ) ) {
				$js_url  = memberpress\courses\JS_URL . '/classroom.js';
				$version = memberpress\courses\VERSION;

				wp_register_script( 'mpcs-classroom-js', $js_url, array( 'jquery' ), $version, true );
				wp_enqueue_script( 'mpcs-classroom-js' );
			}
		}
	}

	/**
	 * Enqueue lesson-specific assets.
	 *
	 * @since BuddyBoss 2.9.00
	 */
	private function enqueue_lesson_assets() {
		wp_enqueue_style( 'mpcs-lesson-css' );
		wp_enqueue_script( 'mpcs-lesson' );
	}

	/**
	 * Enqueue common assets for courses and lessons.
	 *
	 * @since BuddyBoss 2.9.00
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
	 * @since BuddyBoss 2.9.00
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
	 * @since BuddyBoss 2.9.00
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
	 * @since BuddyBoss 2.9.00
	 */
	public function dequeue_pro_frontend_styles() {
		// Only dequeue if ReadyLaunch is enabled.
		if ( bb_is_readylaunch_enabled() ) {
			wp_dequeue_style( 'bb-meprlms-frontend' );
			wp_dequeue_script( 'bb-meprlms-frontend' );
		}
	}

	/**
	 * Add MemberPress template path filters for ReadyLaunch
	 *
	 * @since BuddyBoss 2.9.00
	 *
	 * @param array $paths The array of paths to add.
	 *
	 * @return array The modified array of paths.
	 */
	public function bb_rl_mpcs_add_template_paths( $paths ) {
		$readylaunch_path = trailingslashit( buddypress()->plugin_dir . 'bp-templates/bp-nouveau/readylaunch/memberpress' );
		array_unshift( $paths, $readylaunch_path );
		return $paths;
	}

	/**
	 * Add MemberPress assignments template path filters for ReadyLaunch
	 *
	 * @since BuddyBoss 2.9.00
	 *
	 * @param array $paths The array of paths to add.
	 *
	 * @return array The modified array of paths.
	 */
	public function bb_rl_mpcs_add_assignments_template_paths( $paths ) {
		$readylaunch_path = trailingslashit( buddypress()->plugin_dir . 'bp-templates/bp-nouveau/readylaunch/memberpress/assignments' );
		array_unshift( $paths, $readylaunch_path );
		return $paths;
	}

	/**
	 * Add MemberPress gradebook template path filters for ReadyLaunch
	 *
	 * @since BuddyBoss 2.9.00
	 *
	 * @param array $paths The array of paths to add.
	 *
	 * @return array The modified array of paths.
	 */
	public function bb_rl_mpcs_add_gradebook_template_paths( $paths ) {
		$readylaunch_path = trailingslashit( buddypress()->plugin_dir . 'bp-templates/bp-nouveau/readylaunch/memberpress/gradebook' );
		array_unshift( $paths, $readylaunch_path );
		return $paths;
	}

	/**
	 * Add MemberPress quizzes template path filters for ReadyLaunch.
	 *
	 * @since BuddyBoss 2.9.00
	 *
	 * @param array $paths The array of paths to add.
	 *
	 * @return array The modified array of paths.
	 */
	public function bb_rl_mpcs_add_quizzes_template_paths( $paths ) {
		$readylaunch_path = trailingslashit( buddypress()->plugin_dir . 'bp-templates/bp-nouveau/readylaunch/memberpress/quizzes' );
		array_unshift( $paths, $readylaunch_path );
		return $paths;
	}

	/**
	 * Fires before the loop starts.
	 *
	 * @since BuddyBoss 2.9.00
	 */
	public function bb_rl_mpcs_before_loop() {
		if ( memberpress\courses\helpers\Courses::is_course_archive() ) {
			// Output the secondary header.
			$this->bb_rl_mpcs_output_secondary_header();
			?>
			<div class="bb-rl-container-inner bb-rl-meprlms-content-wrap">
				<div class="bb-rl-courses-grid grid bb-rl-courses-grid--mbprlms">
				<?php
		}
	}

	/**
	 * Fires after the loop ends.
	 *
	 * @since BuddyBoss 2.9.00
	 */
	public function bb_rl_mpcs_after_loop() {
		if ( memberpress\courses\helpers\Courses::is_course_archive() ) {
			?>
				</div>
			</div>
			<div class="bb-rl-container-inner bb-rl-mbprlms-pagination">
				<div class="pagination">
					<?php echo wp_kses_post( memberpress\courses\helpers\Courses::archive_navigation() ); ?>
				</div>
			</div>
			<?php
		}
	}

	/**
	 * Fires when no posts are found.
	 *
	 * @since BuddyBoss 2.9.00
	 */
	public function bb_rl_mpcs_no_posts() {
		if ( memberpress\courses\helpers\Courses::is_course_archive() ) {
			// Output the secondary header.
			$this->bb_rl_mpcs_output_secondary_header();
			?>
			<div class="bb-rl-container-inner bb-rl-meprlms-no-courses">
				<p><?php esc_html_e( 'No courses found', 'buddyboss' ); ?></p>
			</div>
			<?php
		}
	}

	/**
	 * Output the secondary header for MemberPress courses archive.
	 *
	 * @since BuddyBoss 2.9.30
	 */
	private function bb_rl_mpcs_output_secondary_header() {
		global $wp_query, $wp;
		$search          = isset( $_GET['s'] ) ? esc_attr( $_GET['s'] ) : '';               // phpcs:ignore
		$category        = isset( $_GET['category'] ) ? esc_attr( $_GET['category'] ) : ''; // phpcs:ignore
		$author          = isset( $_GET['author'] ) ? esc_attr( $_GET['author'] ) : '';     // phpcs:ignore
		$filter_base_url = home_url( $wp->request );
		$pos             = strpos( $filter_base_url, '/page' );
		$courses_page    = get_home_url( null, helpers\Courses::get_permalink_base() );

		if ( $pos > 0 ) {
			$filter_base_url = substr( $filter_base_url, 0, $pos );
		}
		?>
		<div class="bb-rl-secondary-header flex items-center bb-rl-secondary-header--mbprlms">
			<div class="bb-rl-entry-heading">
				<h1 class="bb-rl-page-title bb-rl-base-heading">
					<?php
					if ( is_tax() ) {
						echo single_term_title( '', false );
					} else {
						esc_html_e( 'Courses', 'buddyboss' );
					}
					?>
					<span class="bb-rl-heading-count"><?php echo esc_html( $wp_query->found_posts ); ?></span>
				</h1>
			</div>

			<div class="bb-rl-course-filters bb-rl-sub-ctrls flex items-center">

				<div class="bb-rl-grid-filters flex items-center" data-view="ld-course">
					<a href="#" class="layout-view layout-view-course layout-grid-view bp-tooltip active" data-view="grid" data-bp-tooltip-pos="down" data-bp-tooltip="<?php esc_html_e( 'Grid View', 'buddyboss' ); ?>">
						<i class="bb-icons-rl-squares-four"></i>
					</a>
					<a href="#" class="layout-view layout-view-course layout-list-view bp-tooltip" data-view="list" data-bp-tooltip-pos="down" data-bp-tooltip="<?php esc_html_e( 'List View', 'buddyboss' ); ?>">
						<i class="bb-icons-rl-rows"></i>
					</a>
				</div>

				<div class="component-filters">
					<div class="mpcs-course-filter columns bb-rl-meprlms-course-filters">
						<div class="column col-sm-12">
							<div class="dropdown">
								<a href="#" class="btn btn-link dropdown-toggle" tabindex="0">
									<?php esc_html_e( 'Category', 'buddyboss' ); ?>
									<span></span><i class="bb-icons-rl-caret-down"></i>
								</a>
								<ul class="menu">
									<?php
									$terms = get_terms( 'mpcs-course-categories' ); // Get all terms of a taxonomy.

									printf( '<li><input type="text" class="form-input mpcs-dropdown-search" placeholder="%s" id="mpmcSearchCategory"></li>', esc_html__( 'Search', 'buddyboss' ) );

									printf( '<li class="%s"><a href="%s">%s</a></li>', esc_attr( '' === $category ? 'active' : 'noactive' ), esc_url( add_query_arg( 'category', '', $filter_base_url ) ), esc_html__( 'All', 'buddyboss' ) );
									foreach ( $terms as $term ) {
										printf( '<li class="%s"><a href="%s">%s</a></li>', esc_attr( $category === $term->slug ? 'active' : 'noactive' ), esc_url( add_query_arg( 'category', $term->slug, $filter_base_url ) ), esc_html( $term->name ) );
									}
									?>
								</ul>
							</div>

							<div class="dropdown">
								<a href="#" class="btn btn-link dropdown-toggle" tabindex="0">
									<?php esc_html_e( 'Author', 'buddyboss' ); ?>
									<span></span><i class="bb-icons-rl-caret-down"></i>
								</a>
								<!-- menu component -->
								<ul class="menu">
									<?php
									$post_authors = models\Course::post_authors();

									printf(
										/* translators: %s: search placeholder */
										'<li><input type="text" class="form-input mpcs-dropdown-search" placeholder="%s" id="mpmcSearchCourses"></li>',
										esc_html__( 'Search', 'buddyboss' )
									);

									printf(
										/* translators: %s: search placeholder */
										'<li class="%s"><a href="%s">%s</a></li>',
										esc_attr( empty( $author ) ? 'active' : 'noactive' ),
										esc_url( add_query_arg( 'author', '', $filter_base_url ) ),
										esc_html__( 'All', 'buddyboss' )
									);

									foreach ( $post_authors as $post_author ) {
										printf( '<li class="%s"><a href="%s">%s</a></li>', esc_attr( $author === $post_author->user_login ? 'active' : 'noactive' ), esc_url( add_query_arg( 'author', $post_author->user_login, $filter_base_url ) ), esc_html( lib\Utils::get_full_name( $post_author->ID ) ) );
									}
									?>
								</ul>
							</div>

							<div class="archives-authors-section">
								<ul>

								</ul>
							</div>
						</div>

						<div class="column col-sm-12">
							<form method="GET" class="" action="<?php echo esc_url( $courses_page ); ?>">
								<div class="input-group">
									<input type="text" name="s" class="form-input"
											placeholder="<?php esc_html_e( 'Find a course', 'buddyboss' ); ?>"
											value="<?php echo esc_attr( $search ); ?>">
									<button class="btn input-group-btn" aria-label="<?php esc_attr_e( 'Find a course', 'buddyboss' ); ?>"><i class="bb-icons-rl-magnifying-glass"></i></button>
								</div>
							</form>

						</div>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Override MemberPress classroom style removal to preserve all ReadyLaunch styles.
	 *
	 * @since BuddyBoss 2.9.00
	 *
	 * @param array $allow_handle Allowed handles.
	 *
	 * @return array
	 */
	public function bb_rl_mpcs_override_readylaunch_styles( $allow_handle ) {
		if ( class_exists( 'memberpress\courses\controllers\Classroom' ) && helpers\App::is_classroom() ) {
			// Add all currently enqueued styles to the allowed list.
			global $wp_styles;
			if ( isset( $wp_styles ) && ! empty( $wp_styles->queue ) ) {
				$allow_handle = array_merge( $allow_handle, $wp_styles->queue );
			}
		}

		return $allow_handle;
	}

	/**
	 * Add "Back to Course" button to lesson sidebar menu.
	 *
	 * @since BuddyBoss 2.9.00
	 */
	public function bb_rl_mpcs_add_back_to_course_button() {
		global $post;
		$lesson_models = models\Lesson::lesson_cpts( true );
		if ( array_key_exists( $post->post_type, $lesson_models ) ) {
			$lesson = new $lesson_models[ $post->post_type ]( $post->ID );
			$course = $lesson->course();

			// Get the course URL.
			$course_url = get_permalink( $course->ID );

			// Output the back to course button.
			?>
			<div class="mpcs-sidebar-back-to-course">
				<a class="tile bb-rl-button bb-rl-button--secondaryFill bb-rl-button--small" href="<?php echo esc_url( $course_url ); ?>">
					<i class="bb-icons-rl-caret-left"></i>
					<span>
						<?php esc_html_e( 'Back to Course', 'buddyboss' ); ?>
					</span>
				</a>
			</div>
			<?php
		}
	}

	/**
	 * Get the course update date including latest modification from course and all its content.
	 *
	 * @since BuddyBoss 2.9.00
	 *
	 * @param int|object $course Course ID or Course object.
	 * @param string     $format Date format. Default 'U' for timestamp.
	 *
	 * @return int|string The latest modification timestamp or formatted date.
	 */
	public static function bb_rl_mpcs_get_course_update_date( $course, $format = 'U' ) {
		// Ensure we have a course object.
		if ( is_numeric( $course ) ) {
			$course = new models\Course( $course );
		}

		if ( ! is_object( $course ) || ! $course->ID ) {
			return false;
		}

		// Get course content last modified date.
		$course_modified_date = get_post_modified_time( 'U', false, $course->ID );
		$latest_modified_date = $course_modified_date;

		// Get all lessons in the course includes assignments and quizzes.
		$lessons = $course->lessons();
		if ( ! empty( $lessons ) ) {

			// Check all lessons for the most recent modification date.
			foreach ( $lessons as $lesson ) {
				$lesson_modified_date = get_post_modified_time( 'U', false, $lesson->ID );
				if ( $lesson_modified_date > $latest_modified_date ) {
					$latest_modified_date = $lesson_modified_date;
				}
			}
		}

		// Return formatted date or timestamp based on format parameter.
		if ( 'U' === $format ) {
			return $latest_modified_date;
		} else {
			return date_i18n( $format, $latest_modified_date );
		}
	}

	/**
	 * Render the course tab menu HTML.
	 *
	 * @since BuddyBoss 2.9.00
	 *
	 * @return string The rendered course tab menu HTML.
	 */
	public static function bb_rl_mpcs_render_course_tab_menu() {
		global $post;
		if ( ! is_single() || ! is_a( $post, 'WP_Post' ) || ! helpers\Courses::is_a_course( $post ) ) {
			return '';
		}

		$course  = new models\Course( $post->ID );
		$user_id = get_current_user_id();

		ob_start();
		?>
		<div class="section bb-rl-lms-tab-menu">
			<a class="bb-rl-lms-tab <?php \MeprAccountHelper::active_nav( 'home', 'is-active' ); ?>" href="<?php echo esc_url( get_permalink() ); ?>">
				<div class="tile-content">
					<p class="tile-title m-0"><?php esc_html_e( 'Course Info', 'buddyboss' ); ?></p>
				</div>
			</a>
			<?php
			do_action( 'mpcs_classroom_sidebar_menu', $course, $post );
			if ( $course->has_resources() ) {
				?>
				<a class="bb-rl-lms-tab <?php \MeprAccountHelper::active_nav( 'resources', 'is-active' ); ?>" href="<?php echo esc_url( get_permalink() . '?action=resources' ); ?>">
					<div class="tile-content">
						<p class="tile-title m-0"><?php esc_html_e( 'Resources', 'buddyboss' ); ?></p>
					</div>
				</a>
				<?php
			}

			if ( $course->user_progress( $user_id ) >= 100 && 'enabled' === $course->certificates_enable ) {
				$cert_url   = admin_url( 'admin-ajax.php?action=mpcs-course-certificate' );
				$cert_url   = add_query_arg(
					array(
						'user'   => $user_id,
						'course' => $post->ID,
					),
					$cert_url
				);
				$share_link = add_query_arg(
					array(
						'shareable' => 'true',
					),
					$cert_url
				);
				?>
				<a target="_blank" class="bb-rl-lms-tab <?php \MeprAccountHelper::active_nav( 'certificate', 'is-active' ); ?>" href="<?php echo esc_url_raw( $cert_url ); ?>">
					<div class="tile-content">
						<p class="tile-title m-0">
							<?php
							esc_html_e( 'Certificate', 'buddyboss' );
							if ( 'enabled' === $course->certificates_share_link ) {
								?>
								<i title="<?php esc_attr_e( 'Copied Shareable Certificate Link', 'buddyboss' ); ?>" class="mpcs-share" data-clipboard-text="<?php echo esc_url( $share_link ); ?>" onclick="return false;"></i>
								<?php
							}
							?>
						</p>
					</div>
				</a>
				<?php
			}

			$options                = get_option( 'mpcs-options' );
			$remove_instructor_link = helpers\Options::val( $options, 'remove-instructor-link' );
			if ( empty( $remove_instructor_link ) ) {
				?>
				<a class="bb-rl-lms-tab <?php \MeprAccountHelper::active_nav( 'instructor', 'is-active' ); ?>" href="<?php echo esc_url( get_permalink() . '?action=instructor' ); ?>">
					<div class="tile-content">
						<p class="tile-title m-0"><?php esc_html_e( 'Your Instructor', 'buddyboss' ); ?></p>
					</div>
				</a>
				<?php
			}
			?>
		</div>
		<?php
		return ob_get_clean();
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
	public function bb_rl_mpcs_comment( $comment, $args, $depth ) {
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
	 * Add comments template for MemberPress courses and lessons.
	 *
	 * @since BuddyBoss 2.9.00
	 *
	 * @param string $template The template path.
	 *
	 * @return string The modified template path.
	 */
	public function bb_rl_mpcs_add_comments_template( $template ) {
		global $post;
		if ( ! function_exists( 'buddyboss_theme' ) && ! empty( $post ) && is_a( $post, 'WP_Post' ) ) {
			if (
				(
					(
						class_exists( 'memberpress\courses\models\Course' ) &&
						models\Course::$cpt === $post->post_type
					) ||
					(
						class_exists( 'memberpress\courses\models\Lesson' ) &&
						models\Lesson::$cpt === $post->post_type
					)
				)
			) {
				$template = buddypress()->plugin_dir . 'bp-templates/bp-nouveau/readylaunch/memberpress/courses/comments.php';
			}
		}
		return $template;
	}

	/**
	 * Add rewrite rules for lesson comments pagination.
	 *
	 * @since BuddyBoss 2.9.00
	 */
	public function bb_rl_mpcs_add_rewrite_rules() {
		if ( class_exists( 'memberpress\courses\models\Lesson' ) ) {

			// Get dynamic slugs from MemberPress configuration.
			$courses_slug = 'courses';
			$lessons_slug = 'lessons';

			// Check if custom slugs are configured.
			if ( class_exists( 'memberpress\courses\helpers\Courses' ) ) {
				$courses_slug = helpers\Courses::get_permalink_base();
			}
			if ( class_exists( 'memberpress\courses\helpers\Lessons' ) ) {
				$lessons_slug = helpers\Lessons::get_permalink_base();
			}

			// Build the rewrite rule pattern for lesson comments pagination using dynamic slugs.
			$regex       = '^' . $courses_slug . '/([^/]+)/' . $lessons_slug . '/([^/]+)/comment-page-([0-9]{1,})/?$';
			$replacement = 'index.php?post_type=' . models\Lesson::$cpt . '&name=$matches[2]&cpage=$matches[3]';

			// Get registered rewrite rules.
			$rules = get_option( 'rewrite_rules', array() );

			// Add the rewrite rule.
			add_rewrite_rule( $regex, $replacement, 'top' );

			// Maybe flush rewrite rules if it was not previously in the option.
			if ( ! isset( $rules[ $regex ] ) ) {
				flush_rewrite_rules();
			}
		}
	}

	/**
	 * Add query vars for lesson comments pagination.
	 *
	 * @since BuddyBoss 2.9.00
	 *
	 * @param array $query_vars The existing query vars.
	 *
	 * @return array The modified query vars.
	 */
	public function bb_rl_mpcs_add_query_vars( $query_vars ) {
		$query_vars[] = 'cpage';
		return $query_vars;
	}
}
