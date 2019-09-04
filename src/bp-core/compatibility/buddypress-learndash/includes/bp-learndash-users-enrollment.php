<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Mass Member Enrollment Class
 *
 * Group Enrollment bulk buddypress group members subscription
 * @author      BuddyBoss
 */
class BuddyPress_Learndash_Users_Enrollment {

	protected static $_instance = null;

	//initialization
	function __construct() {
	    add_action( 'admin_enqueue_scripts',    array( $this, 'admin_enqueues' ) );
		add_action( 'wp_ajax_mass_group_join',  array( $this, 'mass_group_join' ) );
		add_action( 'edit_form_top',            array( $this, 'notice'), 10 );
		add_action( 'wp_ajax_get_enrollment_data', array( $this, 'get_enrollment_data_ajax_callback' ) );
	}

	function notice() {
        global $current_screen;

        // Check to make sure we're on a LearnDash Groups admin page
        if ( ! isset( $current_screen->id) || ! in_array(  $current_screen->id , array( 'sfwd-courses', 'groups') ) ) return;

        if ( $current_screen->action == 'add' ) return;
        ?>

        <div id="enrollment-notice" class="notice notice-info hidden">
            <p><span class="spinner"></span><?php _e( '<strong>BuddyPress for Learndash</strong> enrolling users to the course buddypress groups. This can take a while if you have many students(members). Do not navigate away from this page until this is done.', 'buddyboss' ); ?>
            </p>
        </div>
        <?php
    }

	/**
	 * Main Instance
	 *
	 * @static
	 * @return BuddyPress_Sensei_Member_Type_Migration Main instance
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	/**
	 * Enqueue the needed Javascript and CSS
	 * @param $hook_suffix
	 */
	function admin_enqueues( $hook_suffix ) {
        global $current_screen, $post;

        // Check to make sure we're on a LearnDash Groups admin page
        if ( ! isset( $current_screen->id ) || ! in_array(  $current_screen->id , array( 'sfwd-courses', 'groups') ) ) return;

        //if ( $current_screen->action == 'add' ) return;

        wp_enqueue_script( 'bp-ld-admin-script',  BUDDYPRESS_LEARNDASH_PLUGIN_URL .'assets/js/admin.min.js', array( 'jquery' ), BP_PLATFORM_VERSION );
        wp_enqueue_style( 'bp-ld-admin-style',  BUDDYPRESS_LEARNDASH_PLUGIN_URL .'assets/css/admin.min.css', array(), BP_PLATFORM_VERSION );

		$enrollment_data = $this->get_enrollment_data( $current_screen->post_type, $post->ID );
		$enrollment_data['screen_id'] = $current_screen->post_type;

		if ( 'sfwd-courses' === $current_screen->id ) {
			$group_attached = get_post_meta( $post->ID, 'bp_course_group', true );
			if ( ! empty( $_GET['message'] ) && ! empty( $group_attached ) ) {
				$enrollment_data['proceed_enrollment'] = true;
			}
		}

		wp_localize_script( 'bp-ld-admin-script', 'buddypress_learndash_vars', apply_filters( 'buddypress_learndash_vars', $enrollment_data ) );

	}

	function get_enrollment_data( $current_screen, $post_ID ) {

		// Learndah group edit screen
		if ( $current_screen == 'groups' ) {
			$courses = learndash_group_enrolled_courses( $post_ID );
			$users = learndash_get_groups_user_ids( $post_ID );
		}

		// Learndash course edit screen
		if ( $current_screen == 'sfwd-courses' ) {

			$courses            = array( $post_ID );
			$users              = array();
			$course_user_query  = learndash_get_users_for_course( $post_ID, array( 'count_total' => false ) );

			if ( is_object( $course_user_query ) && $course_user_query instanceof WP_User_Query ) {
				$users = $course_user_query->get_results();
			}
		}

		return array(
			'courses' => $courses,
			'users'	=> $users
		);
	}

	function get_enrollment_data_ajax_callback() {
		$current_screen = $_POST['screen_id'];
		$post_ID = $_POST['post_ID'];
		$enrollment_data = $this->get_enrollment_data( $current_screen, $post_ID );
		wp_send_json_success($enrollment_data);
	}

	/**
	 *  Process a single users batch
	 */
	function mass_group_join() {

		@error_reporting( 0 ); // Don't break the JSON result

		header( 'Content-type: application/json' );

		$learndash_group_users 			= isset($_POST['users'])? $_POST['users']:array(); //students
		$learndash_group_enroll_course 	= isset($_POST['courses'])? $_POST['courses']:array(); //courses

		//Add a user to enrolled groups
		foreach ( $learndash_group_enroll_course as $course_id ) {
			foreach ( $learndash_group_users as $user_id ) {
                bp_learndash_user_course_access_update( $user_id, $course_id, false );
			}
		}

		die( json_encode( array( 'success' => true ) ) );
	}
}

//Check the buddypress group component is active
$active_component = get_option('bp-active-components');

// Attempt to detect if the server supports PHP sessions
if( isset ( $active_component['groups'] ) ) {
    BuddyPress_Learndash_Users_Enrollment::instance();
}

?>