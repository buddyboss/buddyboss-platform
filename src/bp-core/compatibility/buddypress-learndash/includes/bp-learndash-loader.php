<?php
/**
 * @package WordPress
 * @subpackage BuddyPress for LearnDash
 */
// Exit if accessed directly
if (!defined('ABSPATH'))
    exit;

if( !class_exists('BuddyPress_LearnDash_Loader') ):

	/**
	 *
	 * BuddyPress_LearnDash_Loader
	 * ********************
	 *
	 *
	 */
    class BuddyPress_LearnDash_Loader{

        /**
         * empty constructor function to ensure a single instance
         */
        public function __construct(){
            // leave empty, see singleton below
        }

        public static function instance(){
            static $instance = null;

            if(null === $instance){
                $instance = new BuddyPress_LearnDash_Loader;
                $instance->setup();
            }
            return $instance;
        }

        /**
         * setup all
         */
        public function setup(){

            // check if learndash activated
            if ( class_exists( 'SFWD_LMS' ) ) {
                add_action( 'bp_init', array($this, 'bp_learndash_register_member_types') );
                add_action( 'bp_members_directory_member_types', array($this, 'bp_learndash_members_directory') );
                add_action( 'bp_pre_user_query_construct',  array( $this, 'bp_learndash_members_query' ), 1, 1 );
                //add_filter( 'bp_get_total_member_count',    array( $this, 'bp_learndash_members_count' ), 1, 1 );
                // create Courses page
                add_action( 'bp_setup_nav', array($this, 'bp_learndash_add_new_setup_nav'), 100 );
                add_action( 'bp_setup_admin_bar', array($this, 'bp_learndash_add_new_admin_bar'), 90 );
                // set member type while user register or role changed
                add_action( 'user_register', array($this, 'bp_learndash_registration_save'), 100, 1 );
                add_action( 'set_user_role', array($this, 'bp_learndash_user_role_change_save'), 100, 2 );

                // Course enrollment - Join group | Course unenrollment - Leave group
                add_action( 'ld_added_group_access', array( $this, 'add_user_group_access' ), 10, 2 );
                add_action( 'ld_removed_group_access', array( $this, 'remove_user_group_access' ), 10, 2 );
                add_action( 'ld_added_course_group_access', array( $this, 'add_user_course_groups_access' ), 10, 3 );
                add_action( 'ld_removed_course_group_access', array( $this, 'remove_user_course_group_access' ), 10, 2 );
                add_action( 'learndash_update_course_access', array( $this, 'user_update_course_access' ), 100, 4 );

				 // activity stream
				add_action( 'added_post_meta', array( $this, 'bp_learndash_create_lesson_activity' ), 100, 4 );
				add_action( 'added_post_meta', array( $this, 'bp_learndash_create_topic_activity' ), 120, 4 );
				add_action( 'learndash_topic_completed', array( $this, 'bp_learndash_user_topic_end_activity' ), 100, 1 );
				add_action( 'learndash_lesson_completed', array( $this, 'bp_learndash_user_lesson_end_activity' ), 100, 1 );
				add_action( 'learndash_course_completed', array( $this, 'bp_learndash_user_course_end_activity' ), 100, 1 );
				add_action( 'learndash_quiz_completed', array( $this, 'bp_learndash_complete_quiz_activity' ), 100 , 2 );
				add_action( 'wp_set_comment_status', array( $this, 'bp_learndash_lesson_comment' ), 100, 2 );//Backup in case comment moderation is on
				add_action( 'comment_post', array( $this, 'bp_learndash_topic_comment_approved' ), 100, 2 );
				add_action( 'comment_post', array( $this, 'bp_learndash_lesson_comment_approved' ), 100, 2 );
				add_action( 'comment_post', array( $this, 'bp_learndash_course_comment' ), 100, 2 );
            }
        }

		/**
         * lesson create activity
         */
        public function bp_learndash_create_lesson_activity( $mid, $object_id, $meta_key, $_meta_value ) {
            $post = get_post( $object_id );
            $user_id = $post->post_author;
            $lesson_id = $post->ID;

            // if post type not lesson, then return
            if( $post->post_type != 'sfwd-lessons' ) return;

			// return if this lesson is not attached to a course still
			$course_id = get_post_meta( $lesson_id, 'course_id', true );

			//fallback check into a lesson _sfwd-lessons meta
			if ( empty( $course_id ) ) {
				$course_id_obj = get_post_meta( $lesson_id, '_sfwd-lessons', true );
				$course_id = isset( $course_id_obj['sfwd-lessons_course'] ) ? $course_id_obj['sfwd-lessons_course'] : '0';
			}

            if( ( '0' == $course_id ) ) return;

            // if already displayed
            $attached_course_id = get_post_meta( $lesson_id, 'attached_course_id', true );

            if( $attached_course_id == $course_id ) return;

			$group_attached = get_post_meta( $course_id, 'bp_course_group', true );

			if ( empty( $group_attached ) ) {
				return;
			}
            if( !bp_learndash_group_activity_is_on( 'user_lesson_start', $group_attached ) ){
                return;
            }

			global $bp;
            $user_link = bp_core_get_userlink( $user_id );
            $lesson_title = get_the_title( $lesson_id );
            $lesson_link = get_permalink( $lesson_id );
            $lesson_link_html = '<a href="' . esc_url( $lesson_link ) . '">' . $lesson_title . '</a>';
            $course_title = get_the_title( $course_id );
            $course_link = get_permalink( $course_id );
            $course_link_html = '<a href="' . esc_url( $course_link ) . '">' . $course_title . '</a>';
            $args = array(
                'type' => 'created_lesson',
                'user_id' => $user_id,
                'action' => apply_filters( 'bp_learndash_create_lesson_activity',
                    sprintf( __( '%1$s added the %2$s %3$s to the %4$s %5$s', 'buddypress-learndash' ),
                        $user_link, LearnDash_Custom_Label::label_to_lower( 'lesson' ), $lesson_link_html, LearnDash_Custom_Label::label_to_lower( 'course' ), $course_link_html ), $user_id, $lesson_id ),
				'item_id' => $group_attached,
				'secondary_item_id' => $lesson_id,
				'component'	=> $bp->groups->id
            );

            $activity_recorded = bp_learndash_record_activity( $args );
            if($activity_recorded) {
                update_post_meta( $lesson_id, 'attached_course_id', $course_id );
				bp_activity_add_meta($activity_recorded, 'bp_learndash_group_activity_markup_courseid', $lesson_id );
            }
        }

		/**
         * topic create activity
         */
        public function bp_learndash_create_topic_activity( $mid, $object_id, $meta_key, $_meta_value ) {

            $post = get_post( $object_id );
            $user_id = $post->post_author;
            $topic_id = $post->ID;

            // if post type not topic, then return
            if( $post->post_type != 'sfwd-topic' ) return;

            // return if this topic is not attached to a lesson still
            $lesson_id_obj = get_post_meta( $topic_id, '_sfwd-topic', true );

            if( empty( $lesson_id_obj['sfwd-topic_lesson'] ) ) return;

			$lesson_id = $lesson_id_obj['sfwd-topic_lesson'];

			$course_id_obj = get_post_meta( $lesson_id, '_sfwd-lessons', true );

            if( empty( $course_id_obj['sfwd-lessons_course'] ) ) return;

            $course_id = $course_id_obj['sfwd-lessons_course'];

            if( ( '0' == $topic_id ) ) return;

            // if already displayed
            $attached_lesson_id = get_post_meta( $topic_id, 'attached_lesson_id', true );

            if( $attached_lesson_id == $lesson_id ) return;

			$group_attached = get_post_meta( $course_id, 'bp_course_group', true );

			if ( empty( $group_attached ) ) {
				return;
			}
            if( !bp_learndash_group_activity_is_on( 'user_topic_start', $group_attached ) ){
                return;
            }

			global $bp;
            $user_link = bp_core_get_userlink( $user_id );
            $topic_title = get_the_title( $topic_id );
            $topic_link = get_permalink( $topic_id );
            $topic_link_html = '<a href="' . esc_url( $topic_link ) . '">' . $topic_title . '</a>';
            $lesson_title = get_the_title( $lesson_id );
            $lesson_link = get_permalink( $lesson_id );
            $lesson_link_html = '<a href="' . esc_url( $lesson_link ) . '">' . $lesson_title . '</a>';
            $args = array(
                'type' => 'created_topic',
                'user_id' => $user_id,
                'action' => apply_filters( 'bp_learndash_create_topic_activity',
                    sprintf( __( '%1$s added the %2$s %3$s to the %4$s %5$s', 'buddypress-learndash' ),
                        $user_link, LearnDash_Custom_Label::label_to_lower( 'topic' ), $topic_link_html, LearnDash_Custom_Label::get_label( 'lesson' ), $lesson_link_html ), $user_id, $topic_id ),
				'item_id' => $group_attached,
				'secondary_item_id' => $topic_id,
				'component'	=> $bp->groups->id
            );

            $activity_recorded = bp_learndash_record_activity( $args );
            if($activity_recorded) {
                update_post_meta( $topic_id, 'attached_lesson_id', $lesson_id );
				bp_activity_add_meta($activity_recorded, 'bp_learndash_group_activity_markup_courseid', $topic_id );
            }
        }

		/**
         * user lesson end activity
         */
        public function bp_learndash_user_lesson_end_activity( $course_arr ) {
            global $bp;

			$user_id = $course_arr['user']->ID;
			$lesson_id = $course_arr['lesson']->ID;
			$course_id = $course_arr['course']->ID;

			$group_attached = get_post_meta( $course_id, 'bp_course_group', true );
			if ( empty( $group_attached ) ) {
				return;
			}
            if( !bp_learndash_group_activity_is_on( 'user_lesson_end', $group_attached ) ){
                return;
            }
                $user_link = bp_core_get_userlink( $user_id );
                $lesson_title = get_the_title( $lesson_id );
                $lesson_link = get_permalink( $lesson_id );
                $lesson_link_html = '<a href="' . esc_url( $lesson_link ) . '">' . $lesson_title . '</a>';
                $args = array(
                    'type' => 'completed_lesson',
                    'user_id' => $user_id,
                    'action' => apply_filters( 'bp_learndash_user_lesson_end_activity',
                        sprintf( __( '%1$s completed the %2$s %3$s', 'buddypress-learndash' ),
                            $user_link, LearnDash_Custom_Label::label_to_lower( 'lesson' ), $lesson_link_html ), $user_id, $lesson_id ),
                    'item_id' => $group_attached,
					'secondary_item_id' => $lesson_id,
					'component'	=> $bp->groups->id
                );
				$activity_recorded = bp_learndash_record_activity( $args );
				if($activity_recorded) {
					bp_activity_add_meta($activity_recorded, 'bp_learndash_group_activity_markup_courseid', $lesson_id );
				}
        }

		/**
         * user lesson end activity
         */
        public function bp_learndash_user_topic_end_activity( $course_arr ) {
            global $bp;

			$user_id = $course_arr['user']->ID;
			$topic_id = $course_arr['topic']->ID;
			$lesson_id = $course_arr['lesson']->ID;
			$course_id = $course_arr['course']->ID;

			$group_attached = get_post_meta( $course_id, 'bp_course_group', true );
			if ( empty( $group_attached ) ) {
				return;
			}
            if( !bp_learndash_group_activity_is_on( 'user_topic_end', $group_attached ) ){
                return;
            }
                $user_link = bp_core_get_userlink( $user_id );
                $topic_title = get_the_title( $topic_id );
                $topic_link = get_permalink( $topic_id );
                $topic_link_html = '<a href="' . esc_url( $topic_link ) . '">' . $topic_title . '</a>';
                $args = array(
                    'type' => 'completed_topic',
                    'user_id' => $user_id,
                    'action' => apply_filters( 'bp_learndash_user_topic_end_activity',
                        sprintf( __( '%1$s completed the %2$s %3$s', 'buddypress-learndash' ),
                            $user_link, LearnDash_Custom_Label::label_to_lower( 'topic' ), $topic_link_html ), $user_id, $lesson_id ),
                    'item_id' => $group_attached,
					'secondary_item_id' => $topic_id,
					'component'	=> $bp->groups->id
                );
				$activity_recorded = bp_learndash_record_activity( $args );
				if($activity_recorded) {
					bp_activity_add_meta($activity_recorded, 'bp_learndash_group_activity_markup_courseid', $topic_id );
				}
        }

		/**
         * User course end activity
         */
        public function bp_learndash_user_course_end_activity( $course_arr ) {
            global $bp;

			$user_id = $course_arr['user']->ID;
			$course_id = $course_arr['course']->ID;

			$group_attached = get_post_meta( $course_id, 'bp_course_group', true );
			if ( empty( $group_attached ) ) {
				return;
			}
            if( !bp_learndash_group_activity_is_on( 'user_course_end', $group_attached ) ){
                return;
            }

			$user_link = bp_core_get_userlink( $user_id );
			$course_title = get_the_title( $course_id );
			$course_link = get_permalink( $course_id );
			$course_link_html = '<a href="' . esc_url( $course_link ) . '">' . $course_title . '</a>';
			$args = array(
				'type' => 'completed_course',
				'user_id' => $user_id,
				'action' => apply_filters( 'bp_learndash_user_course_end_activity',
					sprintf( __( '%1$s completed the course %2$s', 'buddypress-learndash' ),
						$user_link, $course_link_html ), $user_id, $course_id ),
				'item_id' => $group_attached,
				'secondary_item_id' => $course_id,
				'component'	=> $bp->groups->id
			);
			$activity_recorded = bp_learndash_record_activity( $args );
			if($activity_recorded) {
				bp_activity_add_meta($activity_recorded, 'bp_learndash_group_activity_markup_courseid', $course_id );
			}
        }

		/**
		 * Record lesson comment
		 * @global type $bp
		 * @param type $comment_ID
		 * @param type $comment_status
		 */
		public function bp_learndash_lesson_comment( $comment_ID, $comment_status ) {

			$comment_obj = get_comment( $comment_ID );
			$post_id = $comment_obj->comment_post_ID;
			$post_type = get_post_type( $post_id );

			if ( 'sfwd-lessons' == $post_type && 'approve' == $comment_status ) {

				global $bp;
				$course_id = get_post_meta($post_id,'attached_course_id',true);
				$group_attached = get_post_meta( $course_id, 'bp_course_group', true );
				if ( empty( $group_attached ) ) {
					return;
				}
                if( !bp_learndash_group_activity_is_on( 'user_lesson_comment', $group_attached ) ){
                    return;
                }

				$user_link = bp_core_get_userlink( $comment_obj->user_id );
				$lesson_title = get_the_title( $post_id );
                $lesson_link = get_permalink( $post_id );
                $lesson_link_html = '<a href="' . esc_url( $lesson_link ) . '">' . $lesson_title . '</a>';
				$args = array(
					'type' => 'lesson_comment',
					'action' => apply_filters( 'bp_learndash_user_lesson_comment_activity', sprintf( __( '%1$s commented on %2$s %3$s', 'buddypress-learndash' ), $user_link, LearnDash_Custom_Label::label_to_lower( 'lesson' ), $lesson_link_html ), $comment_obj->user_id, $course_id ),
					'item_id' => $group_attached,
					'secondary_item_id' => $post_id,
					'component' => $bp->groups->id,
					'content' => $comment_obj->comment_content
				);
				$activity_recorded = bp_learndash_record_activity( $args );
				if($activity_recorded) {
					bp_activity_add_meta($activity_recorded, 'bp_learndash_group_activity_markup_courseid', $post_id );
				}
			}
		}

		/**
		 * Record topic comment preapproved
		 * @global type $bp
		 * @param type $comment_ID
		 * @param type $comment_status
		 */
		public function bp_learndash_topic_comment_approved( $comment_ID, $commentdata ) {

			$comment_obj = get_comment( $comment_ID );
			$post_id = $comment_obj->comment_post_ID;
			$post_type = get_post_type( $post_id );
			if ( 'sfwd-topic' == $post_type && $commentdata ) {
				global $bp;
				$course_id = get_post_meta($post_id,'course_id',true);
                $group_attached = get_post_meta( $course_id, 'bp_course_group', true );
                if ( empty( $group_attached ) ) {
					return;
				}
                if( !bp_learndash_group_activity_is_on( 'user_topic_comment', $group_attached ) ){
                    return;
                }

                $user_link = bp_core_get_userlink( $comment_obj->user_id );
				$lesson_title = get_the_title( $post_id );
                $lesson_link = get_permalink( $post_id );
                $lesson_link_html = '<a href="' . esc_url( $lesson_link ) . '">' . $lesson_title . '</a>';
				$args = array(
					'type' => 'activity_update',
					'action' => apply_filters( 'bp_learndash_user_lesson_comment_activity', sprintf( __( '%1$s commented on %2$s %3$s', 'buddypress-learndash' ), $user_link, LearnDash_Custom_Label::label_to_lower( 'topic' ),$lesson_link_html ), $comment_obj->user_id, $course_id ),
					'item_id' => $group_attached,
					'component' => $bp->groups->id,
					'content' => $comment_obj->comment_content
				);
				$activity_recorded = bp_learndash_record_activity( $args );
				if($activity_recorded) {
					bp_activity_add_meta($activity_recorded, 'bp_learndash_group_activity_markup_courseid', $post_id );
				}
			}
		}

		/**
		 * Record lesson comment preapproved
		 * @global type $bp
		 * @param type $comment_ID
		 * @param type $comment_status
		 */
		public function bp_learndash_lesson_comment_approved( $comment_ID, $commentdata ) {

			$comment_obj = get_comment( $comment_ID );
			$post_id = $comment_obj->comment_post_ID;
			$post_type = get_post_type( $post_id );

			if ( 'sfwd-lessons' == $post_type && $commentdata ) {

				global $bp;
				$course_id = get_post_meta($post_id,'attached_course_id',true);
				$group_attached = get_post_meta( $course_id, 'bp_course_group', true );
				if ( empty( $group_attached ) ) {
					return;
				}
                if( !bp_learndash_group_activity_is_on( 'user_lesson_comment', $group_attached ) ){
                    return;
                }

				$user_link = bp_core_get_userlink( $comment_obj->user_id );
				$lesson_title = get_the_title( $post_id );
                $lesson_link = get_permalink( $post_id );
                $lesson_link_html = '<a href="' . esc_url( $lesson_link ) . '">' . $lesson_title . '</a>';
				$args = array(
					'type' => 'lesson_comment',
					'action' => apply_filters( 'bp_learndash_user_lesson_comment_activity', sprintf( __( '%1$s commented on %2$s %3$s', 'buddypress-learndash' ), $user_link, LearnDash_Custom_Label::label_to_lower( 'lesson' ),  $lesson_link_html ), $comment_obj->user_id, $course_id ),
					'item_id' => $group_attached,
					'component' => $bp->groups->id,
					'secondary_item_id' => $post_id,
					'content' => $comment_obj->comment_content
				);
				$activity_recorded = bp_learndash_record_activity( $args );
				if($activity_recorded) {
					bp_activity_add_meta($activity_recorded, 'bp_learndash_group_activity_markup_courseid', $post_id );
				}
			}
		}

		/**
		 * Record course comment
		 * @global type $bp
		 * @param type $comment_ID
		 * @param type $commentdata
		 */
		public function bp_learndash_course_comment( $comment_ID, $commentdata ) {

			$comment_obj = get_comment( $comment_ID );
			$post_id = $course_id = $comment_obj->comment_post_ID;
			$post_type = get_post_type( $post_id );

			if ( 'sfwd-courses' == $post_type && $commentdata ) {

				global $bp;
				$group_attached = get_post_meta( $course_id, 'bp_course_group', true );
				if ( empty( $group_attached ) ) {
					return;
				}
                if( !bp_learndash_group_activity_is_on( 'user_course_comment', $group_attached ) ){
                    return;
                }

				$user_link = bp_core_get_userlink( $comment_obj->user_id );
				$course_title = get_the_title( $post_id );
                $course_link = get_permalink( $post_id );
                $course_link_html = '<a href="' . esc_url( $course_link ) . '">' . $course_title . '</a>';
				$args = array(
					'type' => 'course_comment',
					'action' => apply_filters( 'bp_learndash_course_comment_activity', sprintf( __( '%1$s commented on course %2$s', 'buddypress-learndash' ), $user_link, $course_link_html ), $comment_obj->user_id, $course_id ),
					'item_id' => $group_attached,
					'secondary_item_id' => $post_id,
					'component' => $bp->groups->id,
					'content' => $comment_obj->comment_content
				);
				$activity_recorded = bp_learndash_record_activity( $args );
				if($activity_recorded) {
					bp_activity_add_meta($activity_recorded, 'bp_learndash_group_activity_markup_courseid', $post_id );
				}
			}
		}

		/**
		 * Record quiz activity
		 * @global type $bp
		 */
		public function bp_learndash_complete_quiz_activity( $quizdata, $user ) {

			global $bp;

			$quiz_passesd = $quizdata['pass'];

			if ( $quiz_passesd != '1' ) return;

			$quiz_id = $quizdata['quiz']->ID;
			$quiz_grade = $quizdata['score'];
			$quiz_lesson_id = get_post_meta($quiz_id,'lesson_id',true);
			$course_id = get_post_meta($quiz_lesson_id,'attached_course_id',true);
			$group_attached = get_post_meta( $course_id, 'bp_course_group', true );
			if ( empty( $group_attached ) ) {
				return;
			}
            if( !bp_learndash_group_activity_is_on( 'user_quiz_pass', $group_attached ) ){
                return;
            }
				$user_link = bp_core_get_userlink( get_current_user_id() );
				$quiz_title = get_the_title( $quiz_id );
                $quiz_link = get_permalink( $quiz_id );
                $quiz_link_html = '<a href="' . esc_url( $quiz_link ) . '">' . $quiz_title . '</a>';
				$args = array(
					'type' => 'completed_quiz',
					'action' => apply_filters( 'bp_learndash_complete_quiz_activity', sprintf( __( '%1$s has passed the %2$s %3$s with score %4$s', 'buddypress-learndash' ), $user_link, $quiz_link_html, LearnDash_Custom_Label::label_to_lower( 'quiz' ), $quiz_grade ), get_current_user_id(), $quiz_lesson_id ),
					'item_id' => $group_attached,
					'secondary_item_id' => $quiz_id,
					'component' => $bp->groups->id,
				);
				$activity_recorded = bp_learndash_record_activity( $args );
				if($activity_recorded) {
					bp_activity_add_meta($activity_recorded, 'bp_learndash_group_activity_markup_courseid', $quiz_id );
				}
		}

        /**
         * Add users to buddypress groups on learndash group access update (Users > Edit)
         *
         * @param $user_id
         * @param $group_id
         */
        public function add_user_group_access( $user_id, $group_id ) {

            if ( !defined('IS_PROFILE_PAGE') ) {
                return;
            }

            $courses_ids = learndash_group_enrolled_courses( $group_id );
            foreach ( $courses_ids as $course_id ) {
                bp_learndash_user_course_access_update( $user_id, $course_id, false );
            }
        }

        /**
         * Remove users from buddypress groups on learndash group access update (Users > Edit)
         *
         * @param $user_id
         * @param $group_id
         */
        public function remove_user_group_access( $user_id, $group_id ) {

            $courses_ids = learndash_group_enrolled_courses( $group_id );
            foreach ( $courses_ids as $course_id ) {
                bp_learndash_user_course_access_update( $user_id, $course_id, true );
            }
        }

        /**
         * Add users to buddypress groups on learndash group update (Learndash > Groups > Edit)
         *
         * @param $course_id
         * @param $user_id
         * @param $remove]
         */
        public function add_user_course_groups_access( $course_id, $group_id ) {

            if ( !defined('IS_PROFILE_PAGE') ) {
                return;
            }

            $users_ids = learndash_get_groups_user_ids( $group_id );
            foreach ( $users_ids as $user_id ) {
                bp_learndash_user_course_access_update( $user_id, $course_id, false );
            }
        }

        /**
         * Remove users from buddypress groups on learndash group update (Learndash > Groups > Edit)
         *
         * @param $course_id
         * @param $user_id
         * @param $remove
         */
        public function remove_user_course_group_access( $course_id, $group_id ) {

            $users_ids = learndash_get_groups_user_ids( $group_id );
            foreach ( $users_ids as $user_id ) {
                bp_learndash_user_course_access_update( $user_id, $course_id, true );
            }
        }

        /**
         * user course start activity
         */
        public function user_update_course_access( $user_id, $course_id, $access_list, $remove ) {
            bp_learndash_user_course_access_update( $user_id, $course_id, $remove );
        }

        /**
         * Add new nav items
         */
        public function bp_learndash_add_new_setup_nav() {

            $courses_visibility = buddypress_learndash()->option('courses_visibility');
            $access = bp_core_can_edit_settings();

            //Override members course tab access
            if ( 'on' === $courses_visibility ) {
                $access = true;
            }

            $all_nav_items = array(
                array(
                    'name' => bp_learndash_profile_courses_name(),
                    'slug' => bp_learndash_profile_courses_slug(),
                    'screen' => 'bp_learndash_courses_page',
                    'default_subnav' => bp_learndash_profile_courses_slug(),
                    'show_for_displayed_user' => $access
                )
            );

            $all_subnav_items = array(
                array(
                    'name' => bp_learndash_profile_my_courses_name(),
                    'slug' => bp_learndash_profile_courses_slug(),
                    'parent_slug' => bp_learndash_profile_courses_slug(),
                    'screen' => 'bp_learndash_courses_page',
                    'user_has_access' => $access,
                )
            );
            // create nav item
            foreach($all_nav_items as $single){
                $this->bp_learndash_setup_nav($single['name'], $single['slug'], $single['screen'], $single['default_subnav'], $single['show_for_displayed_user'] );
            }
            // create subnav item
            foreach($all_subnav_items as $single){
                $this->bp_learndash_setup_subnav($single['name'], $single['slug'], $single['parent_slug'], $single['screen'], $single['user_has_access'] );
            }
        }

        public function bp_learndash_setup_nav($name, $slug, $screen, $default_subnav, $access ) {
            bp_core_new_nav_item( array(
                'name' => $name,
                'slug' => $slug,
                'screen_function' => $screen,
                'position' => 80,
                'default_subnav_slug' => $default_subnav,
                'show_for_displayed_user' => $access,
            ) );
        }

        public function bp_learndash_setup_subnav($name, $slug, $parent_slug, $screen, $access ) {
            $parent_nav_link = bp_learndash_get_nav_link( $parent_slug );
            bp_core_new_subnav_item( array(
                'name' => $name,
                'slug' => $slug,
                'parent_url' => $parent_nav_link,
                'parent_slug' => $parent_slug,
                'screen_function' => $screen,
                'position' => 80,
                'user_has_access' => $access,
            ) );
        }

        /**
         * add new admin bar items
         */
        public function bp_learndash_add_new_admin_bar(){
            $all_post_types = array(
                array(
                    'name' => bp_learndash_profile_courses_name(),
                    'slug' => bp_learndash_profile_courses_slug(),
                    'parent' => 'buddypress',
                    'nav_link' => bp_learndash_adminbar_nav_link(bp_learndash_profile_courses_slug()),
                ),
                array(
                    'name' => bp_learndash_profile_my_courses_name(),
                    'slug' => bp_learndash_profile_my_courses_slug(),
                    'parent' => bp_learndash_profile_courses_slug(),
                    'nav_link' => bp_learndash_adminbar_nav_link(bp_learndash_profile_courses_slug()),
                )
            );

			if( current_user_can( 'manage_options' ) ) {
				$all_post_types[] =
				array(
                    'name' => bp_learndash_profile_create_courses_name(),
                    'slug' => bp_learndash_profile_create_courses_slug(),
                    'parent' => bp_learndash_profile_courses_slug(),
					'nav_link' => admin_url().'post-new.php?post_type=sfwd-courses'
                );
			}

            foreach($all_post_types as $single){
                $this->bp_learndash_setup_admin_bar($single['name'], $single['slug'], $single['parent'], $single['nav_link']);
            }
        }

        public function bp_learndash_setup_admin_bar($name, $slug, $parent, $nav_link) {
            global $wp_admin_bar;

            $wp_admin_bar->add_menu( array(
                'parent' => 'my-account-'.$parent,
                'id'     => 'my-account-'.$slug,
                'title'  => $name,
                'href'   => $nav_link
            ) );
        }

        /**
         * Registering member type for learndash
         */
        public function bp_learndash_register_member_types() {
            bp_register_member_type( 'student', array(
                'labels' => array(
                    'name'          => __( 'Students', 'buddypress-learndash' ),
                    'singular_name' => __( 'Student', 'buddypress-learndash' ),
                ),
            ) );
            bp_register_member_type( 'group_leader', array(
                'labels' => array(
                    'name'          => __( 'Group Leaders', 'buddypress-learndash' ),
                    'singular_name' => __( 'Group Leader', 'buddypress-learndash' ),
                ),
            ) );
        }

        public function bp_learndash_members_directory(){
            ?>
            <li id="members-group_leader"><a href="<?php site_url(); ?>bpe-group_leader"><?php printf( __( 'Group Leaders <span>%s</span>', 'buddypress-learndash' ), bp_learndash_members_count_by_type('group_leader') ); ?></a></li>
            <li id="members-student"><a href="<?php site_url(); ?>bpe-student"><?php printf( __( 'Students <span>%s</span>', 'buddypress-learndash' ), bp_learndash_members_count_by_type('student') ); ?></a></li>
        <?php
        }

        public function bp_learndash_members_query( $query_array ){
            if( ( isset($_COOKIE['bp-members-scope']) && $_COOKIE['bp-members-scope'] == 'student') ||
                ( isset($_POST['scope']) && $_POST['scope'] == 'student' )
            ){
                $type_id = bp_learndash_sql_member_type_id('student');
                $user_ids = bp_learndash_sql_members_by_type($type_id);
                $all_users_ids = bp_learndash_get_all_users();
                if(!empty($user_ids)){
                    $query_array->query_vars['include'] = $user_ids;
                }else{
                    $query_array->query_vars['exclude'] = $all_users_ids;
                }
            }

            if( ( isset($_COOKIE['bp-members-scope']) && $_COOKIE['bp-members-scope'] == 'group_leader') ||
                ( isset($_POST['scope']) && $_POST['scope'] == 'group_leader' )
            ){
                $type_id = bp_learndash_sql_member_type_id('group_leader');
                $user_ids = bp_learndash_sql_members_by_type($type_id);
                $all_users_ids = bp_learndash_get_all_users();
                if(!empty($user_ids)){
                    $query_array->query_vars['include'] = $user_ids;
                }else{
                    $query_array->query_vars['exclude'] = $all_users_ids;
                }
            }
        }

        public function bp_learndash_members_count( $count ){
            if( ( isset($_COOKIE['bp-members-scope']) && $_COOKIE['bp-members-scope'] == 'student') ||
                ( isset($_POST['scope']) && $_POST['scope'] == 'student' )
            ){
                $type_id = bp_learndash_sql_member_type_id('student');
                $user_ids = bp_learndash_sql_members_by_type($type_id);
                $count = count($user_ids);
            }

            if( ( isset($_COOKIE['bp-members-scope']) && $_COOKIE['bp-members-scope'] == 'group_leader') ||
                ( isset($_POST['scope']) && $_POST['scope'] == 'group_leader' )
            ){
                $type_id = bp_learndash_sql_member_type_id('group_leader');
                $user_ids = bp_learndash_sql_members_by_type($type_id);
                $count = count($user_ids);
            }

            return $count;
        }

        public function bp_learndash_registration_save( $user_id ) {
            $user_data = get_userdata($user_id);
            $user_roles = $user_data->roles;
            $member_type = bp_get_member_type( $user_id );
            if(in_array('subscriber', $user_roles) && $member_type != 'student'){
                bp_set_member_type( $user_id, 'student' );
            }
            if(in_array('group_leader', $user_roles) && $member_type != 'group_leader'){
                bp_set_member_type( $user_id, 'group_leader' );
            }
        }

        public function bp_learndash_user_role_change_save( $user_id, $role ) {
            $member_type = bp_get_member_type( $user_id );
            if($role == 'subscriber' && $member_type != 'student'){
                bp_set_member_type( $user_id, 'student' );
            }
            if($role == 'group_leader' && $member_type != 'group_leader'){
                bp_set_member_type( $user_id, 'group_leader' );
            }
        }



    }
    BuddyPress_LearnDash_Loader::instance();

endif;
