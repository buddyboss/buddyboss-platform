<?php
/**
 * BuddyBoss TutorLMS integration Core class.
 *
 * @package BuddyBoss\TutorLMS
 * @since BuddyBoss 1.0.0
 */

namespace Buddyboss\TutorLMSIntegration\Core;

use Buddyboss\TutorLMSIntegration\Buddypress\Core as BuddypressCore;
use Buddyboss\TutorLMSIntegration\Core\Admin;
use Buddyboss\TutorLMSIntegration\Core\Dependencies;
use Buddyboss\TutorLMSIntegration\Core\Requirements;
use Buddyboss\TutorLMSIntegration\Core\Settings;


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
		$this->settings     = new Settings();

		$this->pluginName = __( 'BuddyBoss TutorLMS', 'buddyboss' );

		add_action( 'bb_tutorlms/requirements_checked', array( $this, 'init' ) );
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

			add_filter( 'nav_menu_css_class', array( $this, 'bb_tutorlms_active_class' ), PHP_INT_MAX, 2 );
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
		$this->course_name              = \TutorLMS_Custom_Label::get_label( 'courses' );
		$this->my_courses_name          = sprintf( __( 'My %s', 'buddyboss' ), $this->course_name );
		$this->create_courses_name      = sprintf( __( 'Create a %s', 'buddyboss' ), $this->course_name );
		$this->create_courses_slug      = apply_filters( 'bp_tutorlms_profile_create_courses_slug', 'create-courses' );
		$this->course_slug              = '';//bb_tutorlms_profile_courses_slug();
		$this->my_courses_slug          = apply_filters( 'bb_tutorlms_profile_courses_slug', 'my-courses' );
		$this->course_access            = bp_core_can_edit_settings();
		$this->certificates_enables     = bb_core_tutorlms_certificates_enables();
		$this->my_certificates_tab_name = apply_filters( 'bp_tutorlms_profile_certificates_tab_name', __( 'My Certificates', 'buddyboss' ) );
		$this->certificates_tab_name    = apply_filters( 'bp_tutorlms_profile_certificates_tab_name', __( 'Certificates', 'buddyboss' ) );
		$this->certificates_tab_slug    = apply_filters( 'bp_tutorlms_profile_certificates_slug', 'certificates' );

		$this->bp_displayed_user_id = bp_displayed_user_id();
		$this->bp_loggedin_user_id  = bp_loggedin_user_id();
		$this->user_same            = ( $this->bp_displayed_user_id == $this->bp_loggedin_user_id ? true : false );

		$atts         = apply_filters( 'bp_tutorlms_user_courses_atts', array() );
		$user_courses = apply_filters( 'bp_tutorlms_user_courses', ld_get_mycourses( $this->bp_displayed_user_id, $atts ) );

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
	public function bb_tutorlms_active_class( $classes, $item ) {

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
		do_action( 'bp_tutorlms_before_certificates_page_content' );
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

		do_action( 'bp_tutorlms_before_courses_page_content' );

		bp_get_template_part( 'members/single/courses/courses' );
	}

	/**
	 * Sub action once dependencies and requirements are checked
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function init() {
		do_action( 'bb_tutorlms/init' );
	}

	/**
	 * Get absolute path from the integration folder
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function path( $path = '' ) {
		return bb_tutorlms_path( trim( $path, '/\\' ) );
	}

	/**
	 * Get url path from the integration folder
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function url( $uri = '' ) {
		return bb_tutorlms_url( trim( $uri, '/\\' ) );
	}

	/**
	 * Load template from the integration folder
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function template( $path = '' ) {
		return bb_tutorlms_path( 'templates/' . trim( $path, '/\\' ) );
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
					'Buddyboss\TutorLMSIntegration\Core'       => 'core',
					'Buddyboss\TutorLMSIntegration\Library'    => 'library',
					'Buddyboss\TutorLMSIntegration\Buddypress' => 'buddypress',
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

	public function bb_get_course_members( $course_id ) {
		$post = get_post( $course_id );

		if ( empty( $post ) ) {
			return array();
		}

		$access_list = tutor_utils()->get_students_data_by_course_id( $post->ID, 'ID' );

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

}


global $bb_tutorlms;
$bb_tutorlms = new Core();
