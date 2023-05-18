<?php
/**
 * BuddyBoss LearnDash integration Core class.
 *
 * @package BuddyBoss\LearnDash
 * @since BuddyBoss 1.0.0
 */

namespace Buddyboss\LearndashIntegration\Core;

use Buddyboss\LearndashIntegration\Buddypress\Core as BuddypressCore;
use Buddyboss\LearndashIntegration\Core\Admin;
use Buddyboss\LearndashIntegration\Core\Dependencies;
use Buddyboss\LearndashIntegration\Core\Requirements;
use Buddyboss\LearndashIntegration\Core\Settings;
use Buddyboss\LearndashIntegration\Learndash\Core as LearndashCore;


// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

#[\AllowDynamicProperties]
/**
 * COre file of the plugin
 *
 * @since BuddyBoss 1.0.0
 */
class Core {

	/**
	 * Constructor
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function __construct() {
		$this->registerAutoloader();

		$this->admin        = new Admin();
		$this->dependencies = new Dependencies();
		$this->requirements = new Requirements();
		$this->buddypress   = new BuddypressCore();
		$this->learndash    = new LearndashCore();
		$this->settings     = new Settings();

		$this->pluginName = __( 'BuddyBoss LearnDash', 'buddyboss' );

		add_action( 'bp_ld_sync/requirements_checked', array( $this, 'init' ) );
		$this->registerCourseComponent();
	}

	/**
	 * Add Course tab in profile menu
	 *
	 * @since BuddyBoss 1.2.0
	 */
	public function registerCourseComponent() {
		if ( $this->settings->get( 'course.courses_visibility' ) ) {
			/**
			 * Load first
			 */
			add_action( 'bp_setup_nav', array( $this, 'setup_nav' ), 100 );

			/**
			 * Load second
			 */
			add_action( 'bp_setup_admin_bar', array( $this, 'setup_admin_bar' ), 75 );

			/**
			 * Load third
			 */
			add_action( 'buddyboss_theme_after_bb_groups_menu', array( $this, 'setup_user_profile_bar' ), 10 );

			add_filter( 'nav_menu_css_class', array( $this, 'bb_ld_active_class' ), PHP_INT_MAX, 2 );
		}
	}

	/**
	 * Add Menu in Profile section.
	 *
	 * @since BuddyBoss 1.2.0
	 */
	public function setup_user_profile_bar() {
		?>
		<li id="wp-admin-bar-my-account-<?php echo esc_attr( $this->course_slug ); ?>" class="menupop">
			<a class="ab-item" aria-haspopup="true" href="<?php echo esc_url( $this->adminbar_nav_link( $this->course_slug ) ); ?>">
				<i class="bb-icon-l bb-icon-course"></i>
				<span class="wp-admin-bar-arrow" aria-hidden="true"></span><?php echo esc_attr( $this->course_name ); ?>
			</a>

			<div class="ab-sub-wrapper">
				<ul id="wp-admin-bar-my-account-courses-default" class="ab-submenu">
					<li id="wp-admin-bar-my-account-<?php echo esc_attr( $this->my_courses_slug ); ?>">
						<a class="ab-item" href="<?php echo esc_url( $this->adminbar_nav_link( $this->course_slug ) ); ?>"><?php echo esc_attr( $this->my_courses_name ); ?></a>
					</li>
					<?php
					if ( $this->certificates_enables ) {
						?>
						<li id="wp-admin-bar-my-account-<?php echo esc_attr( $this->certificates_tab_slug ); ?>">
							<a class="ab-item" href="<?php echo esc_url( $this->adminbar_nav_link( $this->certificates_tab_slug, $this->course_slug ) ); ?>"><?php echo esc_html( $this->my_certificates_tab_name ); ?></a>
						</li>
						<?php
					}
					?>
				</ul>
			</div>
		</li>
		<?php
	}

	/**
	 * Add Course tab in profile menu
	 *
	 * @since BuddyBoss 1.2.0
	 */
	public function setup_nav() {
		$this->course_name              = \LearnDash_Custom_Label::get_label( 'courses' );
		$this->my_courses_name          = sprintf( __( 'My %s', 'buddyboss' ), $this->course_name );
		$this->create_courses_name      = sprintf( __( 'Create a %s', 'buddyboss' ), $this->course_name );
		$this->create_courses_slug      = apply_filters( 'bp_learndash_profile_create_courses_slug', 'create-courses' );
		$this->course_slug              = bb_learndash_profile_courses_slug();
		$this->my_courses_slug          = apply_filters( 'bp_learndash_profile_courses_slug', 'my-courses' );
		$this->course_access            = bp_core_can_edit_settings();
		$this->certificates_enables     = bp_core_learndash_certificates_enables();
		$this->my_certificates_tab_name = apply_filters( 'bp_learndash_profile_certificates_tab_name', __( 'My Certificates', 'buddyboss' ) );
		$this->certificates_tab_name    = apply_filters( 'bp_learndash_profile_certificates_tab_name', __( 'Certificates', 'buddyboss' ) );
		$this->certificates_tab_slug    = apply_filters( 'bp_learndash_profile_certificates_slug', 'certificates' );

		$this->bp_displayed_user_id = bp_displayed_user_id();
		$this->bp_loggedin_user_id  = bp_loggedin_user_id();
		$this->user_same            = ( $this->bp_displayed_user_id == $this->bp_loggedin_user_id ? true : false );

		$atts         = apply_filters( 'bp_learndash_user_courses_atts', array() );
		$user_courses = apply_filters( 'bp_learndash_user_courses', ld_get_mycourses( $this->bp_displayed_user_id, $atts ) );

		$user_courses_count = is_array( $user_courses ) ? count( $user_courses ) : 0;

		// Only grab count if we're on a user page.
		if ( bp_is_user() ) {
			$class = ( 0 === $user_courses_count ) ? 'no-count' : 'count';

			$nav_name = sprintf(
			/* translators: %s: Group count for the current user */
				__( '%1$s %2$s', 'buddyboss' ),
				$this->course_name,
				sprintf(
					'<span class="%s">%s</span>',
					esc_attr( $class ),
					$user_courses_count
				)
			);
		} else {
			$nav_name = $this->course_name;
		}

		bp_core_new_nav_item(
			array(
				'name'                    => $nav_name,
				'slug'                    => $this->course_slug,
				'screen_function'         => array( $this, 'course_page' ),
				'position'                => 75,
				'default_subnav_slug'     => $this->my_courses_slug,
				'show_for_displayed_user' => $this->course_access,
			)
		);

		$all_subnav_items = array(
			array(
				'name'            => empty( $this->user_same ) ? $this->course_name : $this->my_courses_name,
				'slug'            => $this->my_courses_slug,
				'parent_url'      => $this->get_nav_link( $this->course_slug ),
				'parent_slug'     => $this->course_slug,
				'screen_function' => array( $this, 'course_page' ),
				'position'        => 75,
				'user_has_access' => $this->course_access,
			),
		);

		if ( $this->certificates_enables ) {
			$all_subnav_items[] = array(
				'name'            => empty( $this->user_same ) ? $this->certificates_tab_name : $this->my_certificates_tab_name,
				'slug'            => $this->certificates_tab_slug,
				'parent_url'      => $this->get_nav_link( $this->course_slug ),
				'parent_slug'     => $this->course_slug,
				'screen_function' => array( $this, 'certificates_page' ),
				'user_has_access' => $this->course_access,
			);
		}

		foreach ( $all_subnav_items as $all_subnav_item ) {
			bp_core_new_subnav_item( $all_subnav_item );
		}

	}

	/**
	 * Remove the active class on course & my course page when user is on certificate page.
	 *
	 * @since BuddyBoss 2.0.0
	 *
	 * @param array $classes List of classes.
	 * @param array $item    Menu item array.
	 *
	 * @return array List of classes.
	 */
	public function bb_ld_active_class( $classes, $item ) {

		if (
			bp_current_action() === $this->certificates_tab_slug &&
			in_array( $item->post_name, array( $this->course_slug, $this->my_courses_slug ), true )
		) {
			$key = array_search( 'current_page_item', $classes, true );
			if ( false !== $key ) {
				unset( $classes[ $key ] );
			}

			$key = array_search( 'current-menu-item', $classes, true );
			if ( false !== $key ) {
				unset( $classes[ $key ] );
			}
		}

		return $classes;
	}

	/**
	 * Add Course tab in admin menu
	 *
	 * @since BuddyBoss 1.2.0
	 */
	public function setup_admin_bar() {

		$all_post_types = array(
			array(
				'name'     => $this->course_name,
				'slug'     => $this->course_slug,
				'parent'   => 'buddypress',
				'nav_link' => $this->adminbar_nav_link( $this->course_slug ),
				'position' => 1,
			),
			array(
				'name'     => $this->my_courses_name,
				'slug'     => $this->my_courses_slug,
				'parent'   => $this->course_slug,
				'nav_link' => $this->adminbar_nav_link( $this->course_slug ),
				'position' => 1,
			),
		);

		if ( $this->certificates_enables ) {
			$all_post_types[] = array(
				'name'     => $this->my_certificates_tab_name,
				'slug'     => $this->certificates_tab_slug,
				'parent'   => $this->course_slug,
				'nav_link' => $this->adminbar_nav_link( $this->certificates_tab_slug, $this->course_slug ),
				'position' => 2,
			);
		}

		global $wp_admin_bar;
		foreach ( $all_post_types as $single ) {
			$wp_admin_bar->add_menu(
				array(
					'parent'   => 'my-account-' . $single['parent'],
					'id'       => 'my-account-' . $single['slug'],
					'title'    => $single['name'],
					'href'     => $single['nav_link'],
					'position' => $single['position'],
				)
			);
		}
	}

	/**
	 * Add Menu and Sub menu navigation link for profile menu
	 *
	 * @since BuddyBoss 1.2.0
	 *
	 * @param string $slug        Slug of the nav link.
	 * @param string $parent_slug Parent item slug.
	 *
	 * @return string
	 */
	public function get_nav_link( $slug, $parent_slug = '' ) {
		$displayed_user_id = bp_displayed_user_id();
		$user_domain       = ( ! empty( $displayed_user_id ) ) ? bp_displayed_user_domain() : bp_loggedin_user_domain();
		if ( ! empty( $parent_slug ) ) {
			$nav_link = trailingslashit( $user_domain . $parent_slug . '/' . $slug );
		} else {
			$nav_link = trailingslashit( $user_domain . $slug );
		}

		return $nav_link;
	}

	/**
	 * Add Menu and Sub menu navigation link for admin menu
	 *
	 * @since BuddyBoss 1.2.0
	 *
	 * @param string $slug        Slug of the nav link.
	 * @param string $parent_slug Parent item slug.
	 *
	 * @return string
	 */
	public function adminbar_nav_link( $slug, $parent_slug = '' ) {
		$user_domain = bp_loggedin_user_domain();
		if ( ! empty( $parent_slug ) ) {
			$nav_link = trailingslashit( $user_domain . $parent_slug . '/' . $slug );
		} else {
			$nav_link = trailingslashit( $user_domain . $slug );
		}

		return $nav_link;
	}

	/**
	 * Display Certificates Page Content in Profile course menu
	 *
	 * @since BuddyBoss 1.2.0
	 */
	public function certificates_page() {
		add_action( 'bp_template_content', array( $this, 'certificates_page_content' ) );
		bp_core_load_template( apply_filters( 'bp_core_template_plugin', 'members/single/plugins' ) );
	}

	/**
	 * Display Certificates Page Content
	 *
	 * @since BuddyBoss 1.2.0
	 */
	public function certificates_page_content() {
		do_action( 'template_notices' );
		do_action( 'bp_learndash_before_certificates_page_content' );
		bp_get_template_part( 'members/single/courses/certificates' );
	}

	/**
	 * Display Course Page Content in Profile course menu
	 *
	 * @since BuddyBoss 1.2.0
	 */
	public function course_page() {
		add_action( 'bp_template_content', array( $this, 'courses_page_content' ) );
		bp_core_load_template( apply_filters( 'bp_core_template_plugin', 'members/single/plugins' ) );
	}

	/**
	 * Display Courses in My Course Profile Page
	 *
	 * @since BuddyBoss 1.2.0
	 */
	public function courses_page_content() {

		do_action( 'template_notices' );

		do_action( 'bp_learndash_before_courses_page_content' );

		bp_get_template_part( 'members/single/courses/courses' );
	}

	/**
	 * Sub action once dependencies and requirements are checked
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function init() {
		do_action( 'bp_ld_sync/init' );
	}

	/**
	 * Get absolute path from the integration folder
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function path( $path = '' ) {
		return bp_learndash_path( trim( $path, '/\\' ) );
	}

	/**
	 * Get url path from the integration folder
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function url( $uri = '' ) {
		return bp_learndash_url( trim( $uri, '/\\' ) );
	}

	/**
	 * Load template from the integration folder
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function template( $path = '' ) {
		return bp_learndash_path( 'templates/' . trim( $path, '/\\' ) );
	}

	/**
	 * Get the request from $_POST, $_GET, or $_REQUEST with default fallback
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function getRequest( $key = '*', $default = null, $type = null ) {
		if ( $type ) {
			return $key == '*' ? ${$type} : ( isset( ${$type[ $key ]} ) ? ${$type[ $key ]} : $default );
		}

		$merged = array_merge( $_GET, $_POST, $_REQUEST );

		return $key == '*' ? $merged : ( isset( $merged[ $key ] ) ? $merged[ $key ] : $default );
	}

	/**
	 * Check if the given request isset
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function isRequestExists( $key, $default = null, $type = null ) {
		if ( $type ) {
			return isset( ${$type[ $key ]} );
		}

		$merged = array_merge( $_GET, $_POST, $_REQUEST );

		return isset( $merged[ $key ] );
	}

	/**
	 * Register psr4 autoloader manually
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function registerAutoloader() {
		spl_autoload_register(
			function ( $class ) {
				$psr4 = array(
					'Buddyboss\LearndashIntegration\Core'                  => 'core',
					'Buddyboss\LearndashIntegration\Library'               => 'library',
					'Buddyboss\LearndashIntegration\Buddypress'            => 'buddypress',
					'Buddyboss\LearndashIntegration\Buddypress\Generators' => 'buddypress/generators',
					'Buddyboss\LearndashIntegration\Buddypress\Components' => 'buddypress/components',
					'Buddyboss\LearndashIntegration\Learndash'             => 'learndash',
				);

				$segments  = explode( '\\', $class );
				$className = array_pop( $segments );
				$namespace = implode( '\\', $segments );

				if ( array_key_exists( $namespace, $psr4 ) ) {
					require_once $this->path( "/{$psr4[$namespace]}/{$className}.php" );
				}
			}
		);
	}

	public function bp_get_course_members( $course_id ) {
		$post = get_post( $course_id );

		if ( empty( $post ) ) {
			return array();
		}

		$access_list = learndash_get_course_meta_setting( $post->ID, 'course_access_list' );

		if ( ! is_array( $access_list ) ) {
			$access_list = array();
		}

		$result = array();
		if ( ! empty( $access_list ) ) {
			$result = array();
			foreach ( $access_list as $user_id ) {
				$user = get_userdata( (int) $user_id );
				if ( empty( $user ) || ! $user->exists() ) {
					continue;
				}
				if ( is_multisite() && ! is_user_member_of_blog( $user->ID ) ) {
					continue;
				}
				$result[] = $user;
			}
		}

		return $result;
	}

	public function bp_get_courses_progress( $user_id, $sort_order = 'desc' ) {
		$course_completion_percentage = array();

		if ( ! $course_completion_percentage = wp_cache_get( $user_id, 'ld_courses_progress' ) ) {
			$course_progress = get_user_meta( $user_id, '_sfwd-course_progress', true );

			if ( ! empty( $course_progress ) ) {

				foreach ( $course_progress as $course_id => $coursep ) {
					// We take default progress value as 1 % rather than 0%.
					$course_completion_percentage[ $course_id ] = 1;
					if ( $coursep['total'] == 0 ) {
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

					// cannot divide by 0.
					if ( $coursep['total'] == 0 ) {
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
			if ( 'asc' == $sort_order ) {
				asort( $course_completion_percentage );
			} else {
				arsort( $course_completion_percentage );
			}
		}

		return $course_completion_percentage;
	}

	public function bp_ld_get_progress_course_percentage( $user_id, $course_id ) {

		if ( empty( $user_id ) ) {
			// $current_user = wp_get_current_user();
			if ( is_user_logged_in() ) {
				$user_id = get_current_user_id();
			} else {
				$user_id = 0;
			}
		}

		if ( empty( $course_id ) ) {
			$course_id = learndash_get_course_id();
		}

		if ( empty( $course_id ) ) {
			return '';
		}

		$completed = 0;
		$total     = false;

		if ( ! empty( $user_id ) ) {

			$course_progress = get_user_meta( $user_id, '_sfwd-course_progress', true );

			$percentage = 0;
			$message    = '';

			if ( ( ! empty( $course_progress ) ) && ( isset( $course_progress[ $course_id ] ) ) && ( ! empty( $course_progress[ $course_id ] ) ) ) {
				if ( isset( $course_progress[ $course_id ]['completed'] ) ) {
					$completed = absint( $course_progress[ $course_id ]['completed'] );
				}

				if ( isset( $course_progress[ $course_id ]['total'] ) ) {
					$total = absint( $course_progress[ $course_id ]['total'] );
				}
			} else {
				$total = 0;
			}
		}

		// If $total is still false we calculate the total from course steps.
		if ( false === $total ) {
			$total = learndash_get_course_steps_count( $course_id );
		}

		if ( $total > 0 ) {
			$percentage = intval( $completed * 100 / $total );
			$percentage = ( $percentage > 100 ) ? 100 : $percentage;
		} else {
			$percentage = 0;
		}

		return $percentage;

	}

	/**
	 * Return resume URL of the course.
	 *
	 * @param int $course_id Course ID.
	 *
	 * @return array|false|string|void|\WP_Error
	 */
	public function bp_course_resume( $course_id ) {

		if ( is_user_logged_in() ) {
			if ( ! empty( $course_id ) ) {
				$user           = wp_get_current_user();
				$step_course_id = $course_id;
				$course         = get_post( $step_course_id );

				$lession_list = learndash_get_lesson_list( $course_id );
				$url          = bp_ld_sync()->bp_ld_custom_continue_url_arr( $course_id, $lession_list );

				if ( isset( $course ) && 'sfwd-courses' === $course->post_type ) {
					// $last_know_step = get_user_meta( $user->ID, 'learndash_last_known_course_' . $step_course_id, true );
					$last_know_step = '';

					// User has not hit a LD module yet.
					if ( empty( $last_know_step ) ) {

						if ( isset( $url ) && '' !== $url ) {
							return $url;
						} else {
							return '';
						}
					}

					// $step_course_id = 0;
					// Sanity Check
					if ( absint( $last_know_step ) ) {
						$step_id = $last_know_step;
					} else {
						if ( isset( $url ) && '' !== $url ) {
							return $url;
						} else {
							return '';
						}
					}

					$last_know_post_object = get_post( $step_id );

					// Make sure the post exists and that the user hit a page that was a post
					// if $last_know_page_id returns '' then get post will return current pages post object
					// so we need to make sure first that the $last_know_page_id is returning something and
					// that the something is a valid post.
					if ( null !== $last_know_post_object ) {

						$post_type        = $last_know_post_object->post_type; // getting post_type of last page.
						$label            = get_post_type_object( $post_type ); // getting Labels of the post type.
						$title            = $last_know_post_object->post_title;
						$resume_link_text = __( 'RESUME', 'buddyboss' );

						if ( function_exists( 'learndash_get_step_permalink' ) ) {
							$permalink = learndash_get_step_permalink( $step_id, $step_course_id );
						} else {
							$permalink = get_permalink( $step_id );
						}

						return $permalink;
					}
				}
			}
		} else {
			$course_price_type = learndash_get_course_meta_setting( $course_id, 'course_price_type' );
			if ( $course_price_type == 'open' ) {

				$lession_list = learndash_get_lesson_list( $course_id );
				$url          = bp_ld_sync()->bp_ld_custom_continue_url_arr( $course_id, $lession_list );

				return $url;
			}
		}

		return '';
	}

	/**
	 * Get all the URLs of current course ( lesson, topic, quiz )
	 *
	 * @param int    $course_id           Course id.
	 * @param array  $lession_list        Lesson lists.
	 * @param string $course_quizzes_list Course quizzes list.
	 *
	 * @return array | string
	 */
	public function bp_ld_custom_continue_url_arr( $course_id, $lession_list, $course_quizzes_list = '' ) {
		global $post;

		$course_price_type = learndash_get_course_meta_setting( $course_id, 'course_price_type' );
		if ( $course_price_type == 'closed' ) {
			$courses_progress = bp_ld_sync()->bp_get_courses_progress( get_current_user_id() );
			$user_courses     = learndash_user_get_enrolled_courses( get_current_user_id() );
			$course_progress  = isset( $courses_progress[ $course_id ] ) ? $courses_progress[ $course_id ] : null;
			if ( $course_progress <= 0 && ! in_array( $course_id, $user_courses ) ) {
				return get_the_permalink( $course_id );
			}
		}

		$navigation_urls = array();
		if ( ! empty( $lession_list ) ) :

			foreach ( $lession_list as $lesson ) {

				$lesson_topics = learndash_get_topic_list( $lesson->ID );

				$course_progress = get_user_meta( get_current_user_id(), '_sfwd-course_progress', true );
				$completed       = ! empty( $course_progress[ $course_id ]['lessons'][ $lesson->ID ] ) && 1 === $course_progress[ $course_id ]['lessons'][ $lesson->ID ];

				$navigation_urls[] = array(
					'url'      => get_permalink( $lesson->ID ),
					'complete' => $completed ? 'yes' : 'no',
				);

				if ( ! empty( $lesson_topics ) ) :
					foreach ( $lesson_topics as $lesson_topic ) {

						$completed = ! empty( $course_progress[ $course_id ]['topics'][ $lesson->ID ][ $lesson_topic->ID ] ) && 1 === $course_progress[ $course_id ]['topics'][ $lesson->ID ][ $lesson_topic->ID ];

						$navigation_urls[] = array(
							'url'      => get_permalink( $lesson_topic->ID ),
							'complete' => $completed ? 'yes' : 'no',
						);

						$topic_quizzes = learndash_get_lesson_quiz_list( $lesson_topic->ID );

						if ( ! empty( $topic_quizzes ) ) :
							foreach ( $topic_quizzes as $topic_quiz ) {
								$navigation_urls[] = array(
									'url'      => get_permalink( $topic_quiz['post']->ID ),
									'complete' => learndash_is_quiz_complete( get_current_user_id(), $topic_quiz['post']->ID ) ? 'yes' : 'no',
								);
							}
						endif;

					}
				endif;

				$lesson_quizzes = learndash_get_lesson_quiz_list( $lesson->ID );

				if ( ! empty( $lesson_quizzes ) ) :
					foreach ( $lesson_quizzes as $lesson_quiz ) {
						$navigation_urls[] = array(
							'url'      => get_permalink( $lesson_quiz['post']->ID ),
							'complete' => learndash_is_quiz_complete( get_current_user_id(), $lesson_quiz['post']->ID ) ? 'yes' : 'no',
						);
					}
				endif;
			}

		endif;

		$course_quizzes = learndash_get_course_quiz_list( $course_id );
		if ( ! empty( $course_quizzes ) ) :
			foreach ( $course_quizzes as $course_quiz ) {
				$navigation_urls[] = array(
					'url'      => get_permalink( $course_quiz['post']->ID ),
					'complete' => learndash_is_quiz_complete( get_current_user_id(), $course_quiz['post']->ID ) ? 'yes' : 'no',
				);
			}
		endif;

		$key = array_search( 'no', array_column( $navigation_urls, 'complete' ) );
		if ( '' !== $key && isset( $navigation_urls[ $key ] ) ) {
			return $navigation_urls[ $key ]['url'];
		}

		return '';
	}

	public function bp_ld_prepare_price_str( $price ) {
		return learndash_integration_prepare_price_str( $price );
	}

}


global $bp_ld_sync;
$bp_ld_sync = new Core();
