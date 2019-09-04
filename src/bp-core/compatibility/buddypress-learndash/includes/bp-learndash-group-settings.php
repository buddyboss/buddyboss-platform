<?php
/**
 * Group Course settings tab
 * The class_exists() check is recommended, to prevent problems during upgrade
 * or when the Groups component is disabled
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( class_exists( 'BP_Group_Extension' ) ) :

	class Group_Extension_Course_Settings extends BP_Group_Extension {

		/**
		 * Your __construct() method will contain configuration options for
		 * your extension, and will pass them to parent::init()
		 */
		function __construct() {
			$args = array(
				'slug' => 'group-course-settings',
				'name' => sprintf( __('%s Settings','buddypress-learndash'), LearnDash_Custom_Label::get_label( 'course' ) ),
				'enable_nav_item'	=> false
			);
			parent::init( $args );
		}

		function display( $group_id = null ) {
		}

		/**
		 * settings_screen() is the catch-all method for displaying the content
		 * of the edit, create, and Dashboard admin panels
		 */
		function settings_screen( $group_id = NULL ) {
			$group_status = groups_get_groupmeta( $group_id, 'bp_course_attached', true );
			$courses = get_posts( array(
				'post_type' => 'sfwd-courses',
				'posts_per_page' => 9999,
				'post_status'	=> 'publish'
			) );

			if ( !empty($courses) ) { ?>
				<div class="bp-learndash-group-course">
					<h4><?php printf( __('Group %s','buddypress-learndash'), LearnDash_Custom_Label::get_label( 'course' ) ); ?></h4>
					<select name="bp_group_course" id="bp-group-course">
						<option value="-1"><?php _e( '--Select--', 'buddypress-learndash' ); ?></option>
						<?php
						foreach ( $courses as $course ) {
							$group_attached = get_post_meta( $course->ID, 'bp_course_group', true );
							if ( !empty( $group_attached ) && ( '-1' != $group_attached ) && $course->ID != $group_status ) {
								continue;
							}
							?><option value="<?php echo $course->ID; ?>" <?php echo (( $course->ID == $group_status )) ? 'selected' : ''; ?>><?php echo $course->post_title; ?></option><?php
						}
						?>
					</select>
				</div><br><br/><br/><?php
			}

			if ( !empty($group_status) && ( '-1' != $group_status )  ) {
				$bp_learndash_course_activity = groups_get_groupmeta( $group_id, 'group_extension_course_setting_activities' );
				if ( empty($bp_learndash_course_activity) ) {
					$bp_learndash_course_activity = array();
				}
				?>
				<div class="bp-learndash-course-activity-checkbox">
					<input type="hidden" name="activity-checkbox-enable" value="1" />
					<h4><?php printf( __( '%s Activity','buddypress-learndash' ), LearnDash_Custom_Label::get_label( 'course' ) ); ?></h4>
					<p><?php printf( __('Which %s activity should be displayed in this group?','buddypress-learndash'), LearnDash_Custom_Label::label_to_lower( 'course' ) ); ?></p><br/>
					<input type="checkbox" name="user_course_start" value="true" <?php echo $this->bp_is_checked( 'user_course_start', $bp_learndash_course_activity ); ?>><?php printf( __('User starts a %s','buddypress-learndash'), LearnDash_Custom_Label::label_to_lower( 'course' ) ); ?><br>
					<input type="checkbox" name="user_course_end" value="true" <?php echo $this->bp_is_checked( 'user_course_end', $bp_learndash_course_activity ); ?> ><?php printf( __('User completes a %s','buddypress-learndash'), LearnDash_Custom_Label::label_to_lower( 'course' )  ); ?><br>
					<input type="checkbox" name="user_lesson_start" value="true" <?php echo $this->bp_is_checked( 'user_lesson_start', $bp_learndash_course_activity ); ?> ><?php printf( __('User creates a %s','buddypress-learndash'), LearnDash_Custom_Label::label_to_lower( 'lesson' ) ); ?><br>
					<input type="checkbox" name="user_lesson_end" value="true" <?php echo $this->bp_is_checked( 'user_lesson_end', $bp_learndash_course_activity ); ?> ><?php printf( __('User completes a %s','buddypress-learndash'), LearnDash_Custom_Label::label_to_lower( 'lesson' )  ); ?><br>
					<input type="checkbox" name="user_topic_start" value="true" <?php echo $this->bp_is_checked( 'user_topic_start', $bp_learndash_course_activity ); ?> ><?php printf( __('User creates a %s','buddypress-learndash'), LearnDash_Custom_Label::label_to_lower( 'topic' ) ); ?><br>
					<input type="checkbox" name="user_topic_end" value="true" <?php echo $this->bp_is_checked( 'user_topic_end', $bp_learndash_course_activity ); ?> ><?php printf( __('User completes a %s','buddypress-learndash'), LearnDash_Custom_Label::label_to_lower( 'topic' ) ); ?><br>
					<input type="checkbox" name="user_quiz_pass" value="true" <?php echo $this->bp_is_checked( 'user_quiz_pass', $bp_learndash_course_activity ); ?> ><?php printf( __('User passes a %s','buddypress-learndash'), LearnDash_Custom_Label::label_to_lower( 'quiz' ) ); ?><br>
					<input type="checkbox" name="user_topic_comment" value="true" <?php echo $this->bp_is_checked( 'user_topic_comment', $bp_learndash_course_activity ); ?> ><?php printf( __('User comments on single %s page','buddypress-learndash'), LearnDash_Custom_Label::label_to_lower( 'topic' ) ); ?><br>
					<input type="checkbox" name="user_lesson_comment" value="true" <?php echo $this->bp_is_checked( 'user_lesson_comment', $bp_learndash_course_activity ); ?> ><?php printf( __('User comments on single %s page','buddypress-learndash'), LearnDash_Custom_Label::label_to_lower( 'lesson' ) ); ?><br>
					<input type="checkbox" name="user_course_comment" value="true" <?php echo $this->bp_is_checked( 'user_course_comment', $bp_learndash_course_activity ); ?> ><?php printf( __('User comments on single %s page','buddypress-learndash'), LearnDash_Custom_Label::label_to_lower( 'course' ) ); ?><br>
				</div><br/>
				<?php
			}
		}

		/**
		 * settings_screen_save() contains the catch-all logic for saving
		 * settings from the edit, create, and Dashboard admin panels
		 */
		function settings_screen_save( $group_id = NULL ) {

			$bp_learndash_course_activity = array();
			$old_course_id = groups_get_groupmeta( $group_id, 'bp_course_attached', true );

			if ( isset( $_POST[ 'bp_group_course' ] )  && ( $_POST[ 'bp_group_course' ] ) != '-1' ) {

				if ( ! empty( $old_course_id ) && $old_course_id != $_POST['bp_group_course'] ) {
					delete_post_meta($old_course_id, 'bp_course_group');
					groups_delete_groupmeta( $group_id, 'bp_course_attached' );
					bp_learndash_remove_members_group( $old_course_id, $group_id );
				}

				update_post_meta( $_POST[ 'bp_group_course' ], 'bp_course_group', $group_id );
				groups_add_groupmeta( $group_id, 'bp_course_attached', $_POST[ 'bp_group_course' ] );

				bp_learndash_attach_forum($group_id);

				//Updating visibility of group if course is not open or free
				$course_price_type = learndash_get_course_meta_setting( $_POST[ 'bp_group_course' ], 'course_price_type' );

				if ( 'open' !== $course_price_type && 'free' !== $course_price_type ) {
					$group = groups_get_group( array( 'group_id' => $group_id ) );
					if ( 'public' == $group->status ) {
						$group->status = 'private';
					} elseif ( 'hidden' == $group->status ) {
						$group->status = 'hidden';
					}
					$group->save();
				}

				//Updating group avatar
				bp_learndash_update_group_avatar( $_POST[ 'bp_group_course' ], $group_id );
				//Add memebrs to group
				bp_learndash_add_members_group($_POST[ 'bp_group_course' ], $group_id);
				//Adding teacher as admin of group
				bp_learndash_course_teacher_group_admin($_POST[ 'bp_group_course' ], $group_id );

			} else {
				delete_post_meta($old_course_id, 'bp_course_group');
				groups_delete_groupmeta( $group_id, 'bp_course_attached' );
			}

			if ( !isset($_POST['activity-checkbox-enable'] ) ) {
				$bp_learndash_course_activity = array(
					'user_course_start'	=> 'true',
					'user_course_end'	=> 'true',
					'user_lesson_start'	=> 'true',
					'user_lesson_end'	=> 'true',
					'user_topic_start'	=> 'true',
					'user_topic_end'	=> 'true',
					'user_quiz_pass'	=> 'true',
					'user_topic_comment'	=> 'true',
					'user_lesson_comment'	=> 'true',
					'user_course_comment'	=> 'true'
				);
			}

			if ( isset( $_POST[ 'user_course_start' ] ) ) {
				$bp_learndash_course_activity['user_course_start'] = $_POST[ 'user_course_start' ];
			}
			if ( isset( $_POST[ 'user_course_end' ] ) ) {
				$bp_learndash_course_activity['user_course_end'] = $_POST[ 'user_course_end' ];
			}
			if ( isset( $_POST[ 'user_lesson_start' ] ) ) {
				$bp_learndash_course_activity['user_lesson_start'] = $_POST[ 'user_lesson_start' ];
			}
			if ( isset( $_POST[ 'user_lesson_end' ] ) ) {
				$bp_learndash_course_activity['user_lesson_end'] = $_POST[ 'user_lesson_end' ];
			}
			if ( isset( $_POST[ 'user_topic_start' ] ) ) {
				$bp_learndash_course_activity['user_topic_start'] = $_POST[ 'user_topic_start' ];
			}
			if ( isset( $_POST[ 'user_topic_end' ] ) ) {
				$bp_learndash_course_activity['user_topic_end'] = $_POST[ 'user_topic_end' ];
			}
			if ( isset( $_POST[ 'user_quiz_pass' ] ) ) {
				$bp_learndash_course_activity['user_quiz_pass'] = $_POST[ 'user_quiz_pass' ];
			}
			if ( isset( $_POST[ 'user_topic_comment' ] ) ) {
				$bp_learndash_course_activity['user_topic_comment'] = $_POST[ 'user_topic_comment' ];
			}
			if ( isset( $_POST[ 'user_lesson_comment' ] ) ) {
				$bp_learndash_course_activity['user_lesson_comment'] = $_POST[ 'user_lesson_comment' ];
			}
			if ( isset( $_POST[ 'user_course_comment' ] ) ) {
				$bp_learndash_course_activity['user_course_comment'] = $_POST[ 'user_course_comment' ];
			}

			groups_update_groupmeta( $group_id, 'group_extension_course_setting_activities', $bp_learndash_course_activity );
		}

		public function bp_is_checked( $value , $array ) {
			if ( array_key_exists( $value, $array ) ) {
					$checked = 'checked';
			}
			else {
				$checked = '';
			}
			return $checked;
		}

	}

endif; // if ( class_exists( 'BP_Group_Extension' ) )
