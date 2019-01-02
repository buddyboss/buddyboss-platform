<?php
/**
 * File include Courses menu that is going to be added in the BuddyPress Group
 *
 * @since 1.0.1
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $bp_learndash_requirement;
if ( class_exists( 'BP_Group_Extension' ) && $bp_learndash_requirement->valid() ) {
	/**
	 * Class LearnDash_BuddyPress_Groups_Courses_Menu to display courses menu in BuddyPress groups.
	 *
	 * @since 1.0.1
	 *
	 */
	class LearnDash_BuddyPress_Groups_Courses_Extension extends BP_Group_Extension {
		/**
		 * Here you can see more customization of the config options
		 *
		 * since 1.0.1
		 */
		function __construct() {

			$args = array(
				'slug'              => 'courses',
				'name'              => __( 'Courses', 'buddyboss' ),
				'nav_item_position' => 65,
			);
			parent::init( $args );


			$this->associated_ld_group = bp_learndash_groups_sync_check_associated_ld_group( buddypress()->groups->current_group->id );


			$this->loggedin_user_id = bp_loggedin_user_id();

			$this->current_group = groups_get_group( bp_get_current_group_id() );

			$this->group_link = bp_get_group_permalink( $this->current_group );


			add_filter( 'bp_learndash_groups_sync_courses_submenu', array( $this, 'sub_menu' ), 10, 1 );

		}

		/**
		 * @param null $group_id
		 *
		 * since 1.0.1
		 */
		function display( $group_id = null ) {

			bp_learndash_groups_sync_courses_sub_menu();

			$this->course_html();
		}

		/**
		 * Display sub menu
		 *
		 * @param $group_id
		 */
		function sub_menu( $sub_menus ) {

			$sub_menus[] = array(
				'link'  => $this->group_link,
				'slug'  => $this->slug,
				'label' => __( 'Group Courses', 'buddyboss' ),
			);

			return $sub_menus;
		}

		/**
		 * @param $current_group
		 */
		function course_html() {
			$courses = learndash_group_enrolled_courses( $this->associated_ld_group, true );
			?>
            <div id="courses-group-list" class="group_courses dir-list" data-bp-list="group_courses"
                 style="display: block;">
				<?php
				if ( ! empty( $courses ) ) {
					?>
                    <ul id="courses-list" class="item-list courses-group-list bp-list">
						<?php
						foreach ( $courses as $course ) {
							$this->course_list( $course, $this->current_group, $this->loggedin_user_id );
						}
						?>
                    </ul>
					<?php
				} else {
				    printf( '<p>%s</p>', __( 'No Course associated to this Group', 'buddyboss' ) );
                }
				?>
            </div>
			<?php
		}

		/**
		 * Show course HTML
		 *
		 * @param bool $course_id
		 */
		function course_list( $course_id = false, $current_group ) {

			$post  = get_post( $course_id );
			$link  = get_permalink( $course_id );
			$title = $post->post_title;

			$thumbnail     = get_post_meta( $course_id, '_thumbnail_id', true );
			$thumbnail_url = empty( $thumbnail ) ? bp_learndash_url( '/groups-sync/assets/images/mystery-course.png' ) : wp_get_attachment_image_src( absint( $thumbnail ), 'medium' );
            $has_thumbnail = empty( $thumbnail ) ? 'no-photo' : '';

			if ( is_array( $thumbnail_url ) ) {
				$thumbnail_url = $thumbnail_url[0];
			}

			$is_user_member = $this->loggedin_user_id && empty( groups_is_user_admin( $this->loggedin_user_id, $current_group->id ) ) ? $this->loggedin_user_id : false;

			$course_step   = learndash_get_course_steps_count( $course_id );
			$group_members = groups_get_group_members();

			$total_members        = 0;
			$group_step_completed = 0;
			$user_progress        = 0;
			if ( isset( $group_members['members'] ) ) {
				$total_members = count( $group_members['members'] );
				foreach ( $group_members['members'] as $member ) {

					$progress             = learndash_course_get_completed_steps( $member->id, $course_id );
					$group_step_completed = $group_step_completed + $progress;
					if ( $is_user_member === $member->id ) {
						$user_progress = $progress;
					}
				}
			}
			$group_course_step = $course_step * $total_members;

			$group_percentage_completed = 0;
			if ( ! empty( $group_step_completed ) && ! empty( $group_course_step ) ) {
				$group_percentage_completed = ( 100 * $group_step_completed ) / $group_course_step;
			}
			?>
            <li class="item-entry odd is-online is-current-user">

                <div class="list-wrap">

                    <div class="item-avatar">
                        <a href="<?php echo $link; ?>">
                            <img src="<?php echo $thumbnail_url; ?>" class="photo <?php echo $has_thumbnail; ?>" width="300" height="300"
                                 alt="<?php _e( 'Course Picture', 'buddyboss' ); ?>">
                        </a>
                    </div>

                    <div class="item">
                        <div class="item-block">
                            <h3 class="course-name">
                                <a href="<?php echo $link; ?>">
									<?php echo $title; ?>
                                </a>
                            </h3>

							<?php
							$label = __( 'View Course', 'buddyboss' );
							if ( $is_user_member ) {

								$personal_percentage_completed = 0;
								if ( ! empty( $user_progress ) && ! empty( $course_step ) ) {
									$personal_percentage_completed = ( 100 * $user_progress ) / $course_step;
								}

								$this->progress_bar_html( __( 'My Progress', 'buddyboss' ), $personal_percentage_completed );

								$course_status = learndash_course_status( $course_id, $is_user_member, true );

//								if ( 'in-progress' === $course_status || ( $personal_percentage_completed > 0 && $personal_percentage_completed < 100 ) ) {
								if ( 'in-progress' === $course_status ) {
									$label = __( 'Continue', 'buddyboss' );
								} elseif ( 'completed' === $course_status ) {
									$label = __( 'View Course', 'buddyboss' );
								} else {
									$label = __( 'Start Course', 'buddyboss' );
								}
							}

							$this->progress_bar_html( __( 'Group Progress', 'buddyboss' ), $group_percentage_completed );
							?>

							<?php
							printf( '<div class="course-link"><a href="%s" class="button">%s</a></div>', $link, $label );
							?>

                        </div>
                    </div>
                </div><!-- // .list-wrap -->
            </li>
			<?php
		}

		/**
		 * Progress bar HTML
		 *
		 * @param $lable
		 * @param $percentage_completed
		 */
		function progress_bar_html( $lable, $percentage_completed ) {
			$percentage_completed = round( $percentage_completed );
			?>
            <div class="bp-learndash-progress-bar">
                <p class="bp-learndash-progress-bar-label"><?php echo $lable; ?></p>
                <progress value="<?php echo $percentage_completed; ?>" max="100"></progress>
				<?php
				if ( ! empty( $percentage_completed ) ) {
					?>
                    <span class="bp-learndash-progress-bar-percentage"><?php echo $percentage_completed; ?>% <?php _e( 'Complete', 'buddyboss' ); ?></span>
					<?php
				} else {
				    ?>
                    <span class="bp-learndash-progress-bar-percentage">0% <?php _e( 'Complete', 'buddyboss' ); ?></span>
					<?php
				}
				?>
            </div>
			<?php
		}

	}

	/**
	 * Add Courses menu in BuddyPress Group Menu
	 *
	 * @since 1.0.1
	 */
	function bp_learndash_groups_sync_add_courses_menu() {
		if ( ! bp_learndash_groups_sync_get_settings( 'display_bp_group_cources' ) ) {
			return;
		}

		if ( function_exists( 'bp_is_group' ) && bp_is_group() ) {
			// bp groups has no members
			if ( ! groups_get_group_members( ['exclude_admins_mods' => false ] )['count'] ) {
				return;
			}

			$ld_group = bp_learndash_groups_sync_check_associated_ld_group( buddypress()->groups->current_group->id );

			// no synced group
			if ( ! $ld_group ) {
				return;
			}

			// or synced group doesn't have courses
			if ( ! learndash_group_enrolled_courses($ld_group) ) {
				return;
			}

			bp_register_group_extension( 'LearnDash_BuddyPress_Groups_Courses_Extension' );
		}
	}

	add_action( 'bp_init', 'bp_learndash_groups_sync_add_courses_menu' );
}
