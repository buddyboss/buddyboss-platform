<?php
/**
 * ReadyLaunch Memberpress Courses Helper Class
 *
 * @package BuddyBoss\Core
 * @since   BuddyBoss [BBVERSION]
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

use memberpress\courses as base;
use memberpress\courses\models as models;
use memberpress\courses\helpers as helpers;
use memberpress\courses\lib;

/**
 * ReadyLaunch Memberpress Courses Helper Class
 *
 * This class provides helper functions for Memberpress Courses integration
 * when using ReadyLaunch templates without BuddyBoss theme.
 *
 * @since BuddyBoss [BBVERSION]
 */
class BB_Readylaunch_Memberpress_Courses_Helper {

	/**
	 * The single instance of the class.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @var BB_Readylaunch_Memberpress_Courses_Helper
	 */
	protected static $instance = null;

	/**
	 * Main BB_Readylaunch_Memberpress_Courses_Helper Instance.
	 *
	 * Ensures only one instance of BB_Readylaunch_Memberpress_Courses_Helper is loaded or can be loaded.
	 *
	 * @since  BuddyBoss [BBVERSION]
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
	 * @since BuddyBoss [BBVERSION]
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
		}
	}

	/**
	 * Remove MemberPress admin bar hiding hook to keep WordPress admin bar retain it's default behavior.
	 *
	 * This method removes the MemberPress Classroom controller's admin bar hiding hook
	 * when BuddyBoss ReadyLaunch is enabled, allowing the WordPress admin bar to retain it's default behavior.
	 *
	 * @since BuddyBoss [BBVERSION]
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
	 * @since BuddyBoss [BBVERSION]
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

	/**
	 * Add MemberPress template path filters for ReadyLaunch
	 *
	 * @since BuddyBoss [BBVERSION]
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
	 * @since BuddyBoss [BBVERSION]
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
	 * @since BuddyBoss [BBVERSION]
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
	 * @since BuddyBoss [BBVERSION]
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
	 * @since BuddyBoss [BBVERSION]
	 */
	public function bb_rl_mpcs_before_loop() {
		if ( memberpress\courses\helpers\Courses::is_course_archive() ) {
			global $wp_query, $wp;
			$search          = isset( $_GET['s'] ) ? esc_attr( $_GET['s'] ) : '';  // phpcs:ignore
			$category        = isset( $_GET['category'] ) ? esc_attr( $_GET['category'] ) : ''; // phpcs:ignore
			$author          = isset( $_GET['author'] ) ? esc_attr( $_GET['author'] ) : ''; // phpcs:ignore
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
										<?php esc_html_e( 'Category', 'buddyboss-pro' ); ?> <span></span><i class="bb-icons-rl-caret-down"></i>
									</a>
									<ul class="menu">
										<?php
										$terms = get_terms( 'mpcs-course-categories' ); // Get all terms of a taxonomy.

										printf( '<li><input type="text" class="form-input mpcs-dropdown-search" placeholder="%s" id="mpmcSearchCategory"></li>', esc_html__( 'Search', 'buddyboss-pro' ) );

										printf( '<li class="%s"><a href="%s">%s</a></li>', esc_attr( '' === $category ? 'active' : 'noactive' ), esc_url( add_query_arg( 'category', '', $filter_base_url ) ), esc_html__( 'All', 'buddyboss-pro' ) );
										foreach ( $terms as $term ) {
											printf( '<li class="%s"><a href="%s">%s</a></li>', esc_attr( $category === $term->slug ? 'active' : 'noactive' ), esc_url( add_query_arg( 'category', $term->slug, $filter_base_url ) ), esc_html( $term->name ) );
										}
										?>
									</ul>
								</div>

								<div class="dropdown">
									<a href="#" class="btn btn-link dropdown-toggle" tabindex="0">
										<?php esc_html_e( 'Author', 'buddyboss-pro' ); ?> <span></span><i class="bb-icons-rl-caret-down"></i>
									</a>
									<!-- menu component -->
									<ul class="menu">
										<?php
										$post_authors = models\Course::post_authors();

										printf( '<li><input type="text" class="form-input mpcs-dropdown-search" placeholder="%s" id="mpmcSearchCourses"></li>', esc_html__( 'Search', 'buddyboss-pro' ) );

										printf( '<li class="%s"><a href="%s">%s</a></li>', esc_attr( empty( $author ) ? 'active' : 'noactive' ), esc_url( add_query_arg( 'author', '', $filter_base_url ) ), esc_html__( 'All', 'buddyboss-pro' ) );

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
												placeholder="<?php esc_html_e( 'Find a course', 'buddyboss-pro' ); ?>"
												value="<?php echo esc_attr( $search ); ?>">
										<button class="btn input-group-btn"><i class="bb-icons-rl-magnifying-glass"></i></button>
									</div>
								</form>

							</div>
						</div>
					</div>
				</div>
			</div>
			<div class="bb-rl-container-inner bb-rl-meprlms-content-wrap">	
				<div class="bb-rl-courses-grid grid bb-rl-courses-grid--mbprlms">
				<?php
		}
	}

	/**
	 * Fires after the loop ends.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	public function bb_rl_mpcs_after_loop() {
		if ( memberpress\courses\helpers\Courses::is_course_archive() ) {
			?>
				</div>
			</div>
			<div class="pagination">
				<?php echo wp_kses_post( memberpress\courses\helpers\Courses::archive_navigation() ); ?>
			</div>
			<?php
		}
	}

	/**
	 * Fires when no posts are found.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	public function bb_rl_mpcs_no_posts() {
		echo '<p>' . esc_html__( 'No Course found', 'buddyboss' ) . '</p>';
	}

	/**
	 * Override MemberPress classroom style removal to preserve all ReadyLaunch styles.
	 *
	 * @since BuddyBoss [BBVERSION]
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
}
