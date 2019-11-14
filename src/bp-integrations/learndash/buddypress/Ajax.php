<?php
/**
 * BuddyBoss LearnDash integration ajax class.
 *
 * @package BuddyBoss\LearnDash
 * @since BuddyBoss 1.0.0
 */

namespace Buddyboss\LearndashIntegration\Buddypress;

use Buddyboss\LearndashIntegration\Buddypress\ReportsGenerator;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Class for all ajax related functions
 *
 * @since BuddyBoss 1.0.0
 */
class Ajax {

	protected $bpGroup = null;
	protected $ldGroup = null;

	/**
	 * Constructor
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function __construct() {
		 add_action( 'bp_ld_sync/init', array( $this, 'init' ) );
	}

	/**
	 * Add actions once integration is ready
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function init() {
		add_action( 'wp_ajax_bp_ld_group_export_reports', array( $this, 'ajaxExportReports' ) );
		add_action( 'wp_ajax_bp_ld_group_get_reports', array( $this, 'ajaxGetReports' ) );
		add_action( 'wp_ajax_download_bp_ld_reports', array( $this, 'ajaxDownloadReport' ) );
		add_action( 'bp_ld_sync/ajax/post_fetch_reports', array( $this, 'ajaxGetExports' ) );
		add_action( 'bp_ld_sync/report_columns', array( $this, 'removeIdsOnNonExport' ), 10, 2 );
		add_action( 'bp_ld_sync/reports_generator_args', array( $this, 'unsetCompletionOnExport' ) );
	}

	public function ajaxExportReports() {
		$hash = md5( microtime() );
		if ( groups_is_user_mod( bp_loggedin_user_id(),
				bp_get_current_group_id() ) || groups_is_user_admin( bp_loggedin_user_id(),
				bp_get_current_group_id() ) || bp_current_user_can( 'bp_moderate' ) ) {
			if ( empty( $_REQUEST['course'] ) && empty( $_REQUEST['user'] ) ) {
				set_transient( "{$hash}_info",
					array(
						'filename' => 'learndash-report-export-group-' . $_REQUEST['group'] . '.csv',
						'columns'  => array(
							'user_id'       => array(
								'label'     => 'User ID',
								'sortable'  => '',
								'order_key' => '',
							),
							'course_name'   => array(
								'label'     => 'Course',
								'sortable'  => '',
								'order_key' => '',
							),
							'student_name'  => array(
								'label'     => 'Student',
								'sortable'  => '',
								'order_key' => '',
							),
							'progress'      => array(
								'label'     => 'Progress',
								'sortable'  => '',
								'order_key' => '',
							),
							'start_date'    => array(
								'label'     => 'Start Date',
								'sortable'  => '',
								'order_key' => '',
							),
							'complete_date' => array(
								'label'     => 'Completion Date',
								'sortable'  => '',
								'order_key' => '',
							),
							'time_spent'    => array(
								'label'     => 'Time Spent',
								'sortable'  => '',
								'order_key' => '',
							),
							'points_earned' => array(
								'label'     => 'Points Earned',
								'sortable'  => '',
								'order_key' => '',
							),
						),
					),
					HOUR_IN_SECONDS );
				$group_id  = bp_ld_sync( 'buddypress' )->helpers->getLearndashGroupId( $_REQUEST['group'] );
				$courseIds = learndash_group_enrolled_courses( $group_id );
				$courses   = array_map( 'get_post',
					apply_filters( 'bp_ld_learndash_group_enrolled_courses', $courseIds, $group_id ) );
				if ( groups_is_user_mod( bp_loggedin_user_id(),
						groups_get_current_group()->id ) || groups_is_user_admin( bp_loggedin_user_id(),
						groups_get_current_group()->id ) || bp_current_user_can( 'bp_moderate' ) ) {
					if ( isset( $_REQUEST ) && isset( $_REQUEST['course'] ) && '' !== $_REQUEST['course'] ) {
						$courses = array( get_post( $_REQUEST['course'] ) );
					}

					$exports = array();
					foreach ( $courses as $course ) {
						$course_users = learndash_get_groups_user_ids( $group_id );
						foreach ( $course_users as $user ) {
							$progress             = learndash_course_progress( array(
								'user_id'   => $user,
								'course_id' => $course->ID,
								'array'     => true,
							) );
							$course_activity_args = array(
								'course_id'     => $course->ID,
								'user_id'       => $user,
								'post_id'       => $course->ID,
								'activity_type' => 'course',
							);

							$course_activity = learndash_get_user_activity( $course_activity_args );
							if ( ( ! empty( $course_activity ) ) && ( is_object( $course_activity ) ) ) {
								if ( ( property_exists( $course_activity,
										'activity_started' ) ) && ( ! empty( $course_activity->activity_started ) ) ) {
									$start_date = date_i18n( bp_get_option( 'date_format' ),
										intval( $course_activity->activity_started ) );
								}
							} else {
								$start_date = '-';
							}

							$completed_date = learndash_user_get_course_completed_date( $user,
								$course->ID ) ? date_i18n( bp_get_option( 'date_format' ),
								learndash_user_get_course_completed_date( $user, $course->ID ) ) : '-';
							$time_spent     = '';
							$time_spent     = bp_ld_time_spent( $course_activity );

							$exports[] = array(
								'user_id'       => $user,
								'course_name'   => $course->post_title,
								'student_name'  => bp_core_get_user_displayname( $user ),
								'progress'      => $progress['percentage'],
								'start_date'    => $start_date,
								'complete_date' => $completed_date,
								'time_spent'    => $time_spent,
								'points_earned' => bp_ld_course_points_earned( $course->ID, $user ),
							);
						}
					}
				}
				set_transient( $hash, $exports, HOUR_IN_SECONDS );
			} elseif ( ! empty( $_REQUEST['course'] ) && is_string( $_REQUEST['course'] ) && empty( $_REQUEST['user'] ) ) {
				set_transient( "{$hash}_info",
					array(
						'filename' => 'learndash-report-export-group-' . $_REQUEST['group'] . '.csv',
						'columns'  => array(
							'user_id'       => array(
								'label'     => 'User ID',
								'sortable'  => '',
								'order_key' => '',
							),
							'course_name'   => array(
								'label'     => 'Course',
								'sortable'  => '',
								'order_key' => '',
							),
							'student_name'  => array(
								'label'     => 'Student',
								'sortable'  => '',
								'order_key' => '',
							),
							'progress'      => array(
								'label'     => 'Progress',
								'sortable'  => '',
								'order_key' => '',
							),
							'start_date'    => array(
								'label'     => 'Start Date',
								'sortable'  => '',
								'order_key' => '',
							),
							'complete_date' => array(
								'label'     => 'Completion Date',
								'sortable'  => '',
								'order_key' => '',
							),
							'time_spent'    => array(
								'label'     => 'Time Spent',
								'sortable'  => '',
								'order_key' => '',
							),
							'points_earned' => array(
								'label'     => 'Points Earned',
								'sortable'  => '',
								'order_key' => '',
							),
						),
					),
					HOUR_IN_SECONDS );
				$group_id  = bp_ld_sync( 'buddypress' )->helpers->getLearndashGroupId( $_REQUEST['group'] );
				$courseIds = learndash_group_enrolled_courses( $group_id );
				$courses   = array_map( 'get_post',
					apply_filters( 'bp_ld_learndash_group_enrolled_courses', $courseIds, $group_id ) );
				if ( groups_is_user_mod( bp_loggedin_user_id(),
						groups_get_current_group()->id ) || groups_is_user_admin( bp_loggedin_user_id(),
						groups_get_current_group()->id ) || bp_current_user_can( 'bp_moderate' ) ) {
					if ( isset( $_REQUEST ) && isset( $_REQUEST['course'] ) && '' !== $_REQUEST['course'] ) {
						$courses = array( get_post( $_REQUEST['course'] ) );
					}

					$exports = array();
					foreach ( $courses as $course ) {
						$course_users = learndash_get_groups_user_ids( $group_id );
						foreach ( $course_users as $user ) {
							$progress             = learndash_course_progress( array(
								'user_id'   => $user,
								'course_id' => $course->ID,
								'array'     => true,
							) );
							$course_activity_args = array(
								'course_id'     => $course->ID,
								'user_id'       => $user,
								'post_id'       => $course->ID,
								'activity_type' => 'course',
							);

							$course_activity = learndash_get_user_activity( $course_activity_args );
							if ( ( ! empty( $course_activity ) ) && ( is_object( $course_activity ) ) ) {
								if ( ( property_exists( $course_activity,
										'activity_started' ) ) && ( ! empty( $course_activity->activity_started ) ) ) {
									$start_date = date_i18n( bp_get_option( 'date_format' ),
										intval( $course_activity->activity_started ) );
								}
							} else {
								$start_date = '-';
							}

							$completed_date = learndash_user_get_course_completed_date( $user,
								$course->ID ) ? date_i18n( bp_get_option( 'date_format' ),
								learndash_user_get_course_completed_date( $user, $course->ID ) ) : '-';
							$time_spent     = '';
							$time_spent     = bp_ld_time_spent( $course_activity );

							$exports[] = array(
								'user_id'       => $user,
								'course_name'   => $course->post_title,
								'student_name'  => bp_core_get_user_displayname( $user ),
								'progress'      => $progress['percentage'],
								'start_date'    => $start_date,
								'complete_date' => $completed_date,
								'time_spent'    => $time_spent,
								'points_earned' => bp_ld_course_points_earned( $course->ID, $user ),
							);
						}
					}
				}
				set_transient( $hash, $exports, HOUR_IN_SECONDS );
			} elseif ( empty( $_REQUEST['course'] ) && ! empty( $_REQUEST['user'] ) && ! empty( $_REQUEST['step'] ) && 'all' === $_REQUEST['step'] ) {
				set_transient( "{$hash}_info",
					array(
						'filename' => 'learndash-report-export-group-' . $_REQUEST['group'] . '.csv',
						'columns'  => array(
							'user_id'       => array(
								'label'     => 'User ID',
								'sortable'  => '',
								'order_key' => '',
							),
							'student_name'  => array(
								'label'     => 'Student',
								'sortable'  => '',
								'order_key' => '',
							),
							'course_name'   => array(
								'label'     => 'Course',
								'sortable'  => '',
								'order_key' => '',
							),
							'step'          => array(
								'label'     => 'Step',
								'sortable'  => '',
								'order_key' => '',
							),
							'start_date'    => array(
								'label'     => 'Start Date',
								'sortable'  => '',
								'order_key' => '',
							),
							'complete_date' => array(
								'label'     => 'Completion Date',
								'sortable'  => '',
								'order_key' => '',
							),
							'time_spent'    => array(
								'label'     => 'Time Spent',
								'sortable'  => '',
								'order_key' => '',
							),
							'points_earned' => array(
								'label'     => 'Points Earned',
								'sortable'  => '',
								'order_key' => '',
							),
						),
					),
					HOUR_IN_SECONDS );
				$user      = ( isset( $_REQUEST ) && isset( $_REQUEST['user'] ) && '' !== $_REQUEST['user'] ) ? $_REQUEST['user'] : bp_loggedin_user_id();
				$group_id  = bp_ld_sync( 'buddypress' )->helpers->getLearndashGroupId( $_REQUEST['group'] );
				$courseIds = learndash_group_enrolled_courses( $group_id );
				$courses   = array_map( 'get_post',
					apply_filters( 'bp_ld_learndash_group_enrolled_courses', $courseIds, $group_id ) );
				$exports   = array();
				foreach ( $courses as $course ) {
					$course_users = learndash_get_groups_user_ids( $group_id );
					if ( isset( $_REQUEST ) && isset( $_REQUEST['step'] ) && '' === $_REQUEST['step'] ) {
						$data  = bp_ld_get_course_all_steps( $course->ID, $user, 'all' );
						$steps = $data['steps'];
						$label = __( 'Step', 'buddyboss' );
					} elseif ( isset( $_REQUEST ) && isset( $_REQUEST['course'] ) && isset( $_REQUEST['step'] ) && 'all' === $_REQUEST['step'] ) {
						$data  = bp_ld_get_course_all_steps( $course->ID, $user, 'all' );
						$steps = $data['steps'];
						$label = __( 'Step', 'buddyboss' );
					} elseif ( isset( $_REQUEST ) && isset( $_REQUEST['course'] ) && isset( $_REQUEST['step'] ) && 'sfwd-lessons' === $_REQUEST['step'] ) {
						$data  = bp_ld_get_course_all_steps( $course->ID, $user, 'lesson' );
						$steps = $data['steps'];
						$label = \LearnDash_Custom_Label::get_label( 'lesson' );
					} elseif ( isset( $_REQUEST ) && isset( $_REQUEST['course'] ) && isset( $_REQUEST['step'] ) && 'sfwd-topic' === $_REQUEST['step'] ) {
						$data  = bp_ld_get_course_all_steps( $course->ID, $user, 'topic' );
						$steps = $data['steps'];
						$label = \LearnDash_Custom_Label::get_label( 'topic' );
					} elseif ( isset( $_REQUEST ) && isset( $_REQUEST['course'] ) && isset( $_REQUEST['step'] ) && 'sfwd-quiz' === $_REQUEST['step'] ) {
						$data  = bp_ld_get_course_all_steps( $course->ID, $user, 'quiz' );
						$steps = $data['steps'];
						$label = \LearnDash_Custom_Label::get_label( 'quiz' );
					} elseif ( isset( $_REQUEST ) && isset( $_REQUEST['course'] ) && isset( $_REQUEST['step'] ) && 'sfwd-assignment' === $_REQUEST['step'] ) {
						$data  = bp_ld_get_course_all_steps( $course->ID, $user, 'assignment' );
						$steps = $data['steps'];
						$label = __( 'ASSIGNMENT', 'buddyboss' );
					} else {
						$data  = bp_ld_get_course_all_steps( $course->ID, $user, 'all' );
						$steps = $data['steps'];
						$label = __( 'Step', 'buddyboss' );
					}
					foreach ( $steps as $step ) {
						if ( is_null( $step['activity'] ) ) {
							$exports[] = array(
								'user_id'       => $user,
								'student_name'  => bp_core_get_user_displayname( $user ),
								'course_name'   => $course->post_title,
								'step'          => wp_strip_all_tags( $step['title'] ),
								'start_date'    => '-',
								'complete_date' => '-',
								'time_spent'    => '-',
								'points_earned' => '-',
							);
						} else {
							$time_spent = bp_ld_time_spent( $step['activity'] );
							$start_date = date_i18n( bp_get_option( 'date_format' ),
								intval( $step['activity']->activity_started ) );
							$points     = bpLdCoursePointsEarned( $step['activity'] );
							if ( is_null( $step['activity'] ) ) {
								$end_date = '';
							} else {
								$end_date = ( $step['activity']->activity_completed ) ? date_i18n( bp_get_option( 'date_format' ),
									intval( $step['activity']->activity_completed ) ) : '-';
							}
							$exports[] = array(
								'user_id'       => $user,
								'student_name'  => bp_core_get_user_displayname( $user ),
								'course_name'   => $course->post_title,
								'step'          => wp_strip_all_tags( $step['title'] ),
								'start_date'    => $start_date,
								'complete_date' => $end_date,
								'time_spent'    => $time_spent,
								'points_earned' => $points,
							);
						}
					}
				}
				set_transient( $hash, $exports, HOUR_IN_SECONDS );
			} elseif ( ! empty( $_REQUEST['course'] ) && ! empty( $_REQUEST['user'] ) && ! empty( $_REQUEST['step'] ) ) {
				$group_id = bp_ld_sync( 'buddypress' )->helpers->getLearndashGroupId( $_REQUEST['group'] );
				if ( isset( $_REQUEST['course'] ) && '' !== $_REQUEST['course'] ) {
					$courseIds = array( $_REQUEST['course'] );
				} else {
					$courseIds = learndash_group_enrolled_courses( $group_id );
				}
				$user    = ( isset( $_REQUEST ) && isset( $_REQUEST['user'] ) && '' !== $_REQUEST['user'] ) ? $_REQUEST['user'] : bp_loggedin_user_id();
				$label   = 'Step';
				$courses = array_map( 'get_post',
					apply_filters( 'bp_ld_learndash_group_enrolled_courses', $courseIds, $group_id ) );
				foreach ( $courses as $course ) {
					$course_users = learndash_get_groups_user_ids( $group_id );
					if ( isset( $_REQUEST ) && isset( $_REQUEST['step'] ) && '' === $_REQUEST['step'] ) {
						$data  = bp_ld_get_course_all_steps( $course->ID, $user, 'all' );
						$steps = $data['steps'];
						$label = __( 'Step', 'buddyboss' );
						set_transient( "{$hash}_info",
							array(
								'filename' => 'learndash-report-export-group-' . $_REQUEST['group'] . '.csv',
								'columns'  => array(
									'user_id'       => array(
										'label'     => 'User ID',
										'sortable'  => '',
										'order_key' => '',
									),
									'student_name'  => array(
										'label'     => 'Student',
										'sortable'  => '',
										'order_key' => '',
									),
									'course_name'   => array(
										'label'     => 'Course',
										'sortable'  => '',
										'order_key' => '',
									),
									'step'          => array(
										'label'     => 'Step',
										'sortable'  => '',
										'order_key' => '',
									),
									'start_date'    => array(
										'label'     => 'Start Date',
										'sortable'  => '',
										'order_key' => '',
									),
									'complete_date' => array(
										'label'     => 'Completion Date',
										'sortable'  => '',
										'order_key' => '',
									),
									'time_spent'    => array(
										'label'     => 'Time Spent',
										'sortable'  => '',
										'order_key' => '',
									),
									'points_earned' => array(
										'label'     => 'Points Earned',
										'sortable'  => '',
										'order_key' => '',
									),
								),
							),
							HOUR_IN_SECONDS );
						foreach ( $steps as $step ) {
							if ( is_null( $step['activity'] ) ) {
								$exports[] = array(
									'user_id'       => $user,
									'student_name'  => bp_core_get_user_displayname( $user ),
									'course_name'   => $course->post_title,
									'step'          => wp_strip_all_tags( $step['title'] ),
									'start_date'    => '-',
									'complete_date' => '-',
									'time_spent'    => '-',
									'points_earned' => '-',
								);
							} else {
								$time_spent = bp_ld_time_spent( $step['activity'] );
								$start_date = date_i18n( bp_get_option( 'date_format' ),
									intval( $step['activity']->activity_started ) );
								$points     = bpLdCoursePointsEarned( $step['activity'] );
								if ( is_null( $step['activity'] ) ) {
									$end_date = '';
								} else {
									$end_date = ( $step['activity']->activity_completed ) ? date_i18n( bp_get_option( 'date_format' ),
										intval( $step['activity']->activity_completed ) ) : '-';
								}
								$exports[] = array(
									'user_id'       => $user,
									'student_name'  => bp_core_get_user_displayname( $user ),
									'course_name'   => $course->post_title,
									'step'          => wp_strip_all_tags( $step['title'] ),
									'start_date'    => $start_date,
									'complete_date' => $end_date,
									'time_spent'    => $time_spent,
									'points_earned' => $points,
								);
							}
						}
						set_transient( $hash, $exports, HOUR_IN_SECONDS );
					} elseif ( isset( $_REQUEST ) && isset( $_REQUEST['course'] ) && isset( $_REQUEST['step'] ) && 'all' === $_REQUEST['step'] ) {
						$label = __( 'Step', 'buddyboss' );
						$data  = bp_ld_get_course_all_steps( $course->ID, $user, 'all' );
						$steps = $data['steps'];
						set_transient( "{$hash}_info",
							array(
								'filename' => 'learndash-report-export-group-' . $_REQUEST['group'] . '.csv',
								'columns'  => array(
									'user_id'       => array(
										'label'     => 'User ID',
										'sortable'  => '',
										'order_key' => '',
									),
									'student_name'  => array(
										'label'     => 'Student',
										'sortable'  => '',
										'order_key' => '',
									),
									'course_name'   => array(
										'label'     => 'Course',
										'sortable'  => '',
										'order_key' => '',
									),
									'step'          => array(
										'label'     => 'Step',
										'sortable'  => '',
										'order_key' => '',
									),
									'start_date'    => array(
										'label'     => 'Start Date',
										'sortable'  => '',
										'order_key' => '',
									),
									'complete_date' => array(
										'label'     => 'Completion Date',
										'sortable'  => '',
										'order_key' => '',
									),
									'time_spent'    => array(
										'label'     => 'Time Spent',
										'sortable'  => '',
										'order_key' => '',
									),
									'points_earned' => array(
										'label'     => 'Points Earned',
										'sortable'  => '',
										'order_key' => '',
									),
								),
							),
							HOUR_IN_SECONDS );
						foreach ( $steps as $step ) {
							if ( is_null( $step['activity'] ) ) {
								$exports[] = array(
									'user_id'       => $user,
									'student_name'  => bp_core_get_user_displayname( $user ),
									'course_name'   => $course->post_title,
									'step'          => wp_strip_all_tags( $step['title'] ),
									'start_date'    => '-',
									'complete_date' => '-',
									'time_spent'    => '-',
									'points_earned' => '-',
								);
							} else {
								$time_spent = bp_ld_time_spent( $step['activity'] );
								$start_date = date_i18n( bp_get_option( 'date_format' ),
									intval( $step['activity']->activity_started ) );
								$points     = bpLdCoursePointsEarned( $step['activity'] );
								if ( is_null( $step['activity'] ) ) {
									$end_date = '';
								} else {
									$end_date = ( $step['activity']->activity_completed ) ? date_i18n( bp_get_option( 'date_format' ),
										intval( $step['activity']->activity_completed ) ) : '-';
								}
								$exports[] = array(
									'user_id'       => $user,
									'student_name'  => bp_core_get_user_displayname( $user ),
									'course_name'   => $course->post_title,
									'step'          => wp_strip_all_tags( $step['title'] ),
									'start_date'    => $start_date,
									'complete_date' => $end_date,
									'time_spent'    => $time_spent,
									'points_earned' => $points,
								);
							}
						}
						set_transient( $hash, $exports, HOUR_IN_SECONDS );
					} elseif ( isset( $_REQUEST ) && isset( $_REQUEST['course'] ) && isset( $_REQUEST['step'] ) && 'sfwd-lessons' === $_REQUEST['step'] ) {
						$label = \LearnDash_Custom_Label::get_label( 'lesson' );
						$data  = bp_ld_get_course_all_steps( $course->ID, $user, 'lesson' );
						$steps = $data['steps'];
						$key   = str_replace( ' ', '_', strtolower( $label ) );
						set_transient( "{$hash}_info",
							array(
								'filename' => 'learndash-report-export-group-' . $_REQUEST['group'] . '.csv',
								'columns'  => array(
									'user_id'       => array(
										'label'     => 'User ID',
										'sortable'  => '',
										'order_key' => '',
									),
									'student_name'  => array(
										'label'     => 'Student',
										'sortable'  => '',
										'order_key' => '',
									),
									'course_name'   => array(
										'label'     => 'Course',
										'sortable'  => '',
										'order_key' => '',
									),
									$key            => array(
										'label'     => $label,
										'sortable'  => '',
										'order_key' => '',
									),
									'start_date'    => array(
										'label'     => 'Start Date',
										'sortable'  => '',
										'order_key' => '',
									),
									'complete_date' => array(
										'label'     => 'Completion Date',
										'sortable'  => '',
										'order_key' => '',
									),
									'time_spent'    => array(
										'label'     => 'Time Spent',
										'sortable'  => '',
										'order_key' => '',
									),
									'points_earned' => array(
										'label'     => 'Points Earned',
										'sortable'  => '',
										'order_key' => '',
									),
								),
							),
							HOUR_IN_SECONDS );
						foreach ( $steps as $step ) {
							if ( is_null( $step['activity'] ) ) {
								$exports[] = array(
									'user_id'       => $user,
									'student_name'  => bp_core_get_user_displayname( $user ),
									'course_name'   => $course->post_title,
									$key            => wp_strip_all_tags( $step['title'] ),
									'start_date'    => '-',
									'complete_date' => '-',
									'time_spent'    => '-',
									'points_earned' => '-',
								);
							} else {
								$time_spent = bp_ld_time_spent( $step['activity'] );
								$start_date = date_i18n( bp_get_option( 'date_format' ),
									intval( $step['activity']->activity_started ) );
								$points     = bpLdCoursePointsEarned( $step['activity'] );
								if ( is_null( $step['activity'] ) ) {
									$end_date = '';
								} else {
									$end_date = ( $step['activity']->activity_completed ) ? date_i18n( bp_get_option( 'date_format' ),
										intval( $step['activity']->activity_completed ) ) : '-';
								}
								$exports[] = array(
									'user_id'       => $user,
									'student_name'  => bp_core_get_user_displayname( $user ),
									'course_name'   => $course->post_title,
									$key            => wp_strip_all_tags( $step['title'] ),
									'start_date'    => $start_date,
									'complete_date' => $end_date,
									'time_spent'    => $time_spent,
									'points_earned' => $points,
								);
							}
						}
						set_transient( $hash, $exports, HOUR_IN_SECONDS );
					} elseif ( isset( $_REQUEST ) && isset( $_REQUEST['course'] ) && isset( $_REQUEST['step'] ) && 'sfwd-topic' === $_REQUEST['step'] ) {
						$label = \LearnDash_Custom_Label::get_label( 'topic' );
						$data  = bp_ld_get_course_all_steps( $course->ID, $user, 'topic' );
						$steps = $data['steps'];
						$key   = str_replace( ' ', '_', strtolower( $label ) );
						set_transient( "{$hash}_info",
							array(
								'filename' => 'learndash-report-export-group-' . $_REQUEST['group'] . '.csv',
								'columns'  => array(
									'user_id'       => array(
										'label'     => 'User ID',
										'sortable'  => '',
										'order_key' => '',
									),
									'student_name'  => array(
										'label'     => 'Student',
										'sortable'  => '',
										'order_key' => '',
									),
									'course_name'   => array(
										'label'     => 'Course',
										'sortable'  => '',
										'order_key' => '',
									),
									$key            => array(
										'label'     => $label,
										'sortable'  => '',
										'order_key' => '',
									),
									'start_date'    => array(
										'label'     => 'Start Date',
										'sortable'  => '',
										'order_key' => '',
									),
									'complete_date' => array(
										'label'     => 'Completion Date',
										'sortable'  => '',
										'order_key' => '',
									),
									'time_spent'    => array(
										'label'     => 'Time Spent',
										'sortable'  => '',
										'order_key' => '',
									),
									'points_earned' => array(
										'label'     => 'Points Earned',
										'sortable'  => '',
										'order_key' => '',
									),
								),
							),
							HOUR_IN_SECONDS );
						foreach ( $steps as $step ) {
							if ( is_null( $step['activity'] ) ) {
								$exports[] = array(
									'user_id'       => $user,
									'student_name'  => bp_core_get_user_displayname( $user ),
									'course_name'   => $course->post_title,
									$key            => wp_strip_all_tags( $step['title'] ),
									'start_date'    => '-',
									'complete_date' => '-',
									'time_spent'    => '-',
									'points_earned' => '-',
								);
							} else {
								$time_spent = bp_ld_time_spent( $step['activity'] );
								$start_date = date_i18n( bp_get_option( 'date_format' ),
									intval( $step['activity']->activity_started ) );
								$points     = bpLdCoursePointsEarned( $step['activity'] );
								if ( is_null( $step['activity'] ) ) {
									$end_date = '';
								} else {
									$end_date = ( $step['activity']->activity_completed ) ? date_i18n( bp_get_option( 'date_format' ),
										intval( $step['activity']->activity_completed ) ) : '-';
								}
								$exports[] = array(
									'user_id'       => $user,
									'student_name'  => bp_core_get_user_displayname( $user ),
									'course_name'   => $course->post_title,
									$key            => wp_strip_all_tags( $step['title'] ),
									'start_date'    => $start_date,
									'complete_date' => $end_date,
									'time_spent'    => $time_spent,
									'points_earned' => $points,
								);
							}
						}
						set_transient( $hash, $exports, HOUR_IN_SECONDS );
					} elseif ( isset( $_REQUEST ) && isset( $_REQUEST['course'] ) && isset( $_REQUEST['step'] ) && 'sfwd-quiz' === $_REQUEST['step'] ) {
						$label = \LearnDash_Custom_Label::get_label( 'quiz' );
						$data  = bp_ld_get_course_all_steps( $course->ID, $user, 'quiz' );
						$steps = $data['steps'];
						$key   = str_replace( ' ', '_', strtolower( $label ) );
						set_transient( "{$hash}_info",
							array(
								'filename' => 'learndash-report-export-group-' . $_REQUEST['group'] . '.csv',
								'columns'  => array(
									'user_id'       => array(
										'label'     => 'User ID',
										'sortable'  => '',
										'order_key' => '',
									),
									'student_name'  => array(
										'label'     => 'Student',
										'sortable'  => '',
										'order_key' => '',
									),
									'course_name'   => array(
										'label'     => 'Course',
										'sortable'  => '',
										'order_key' => '',
									),
									$key            => array(
										'label'     => $label,
										'sortable'  => '',
										'order_key' => '',
									),
									'start_date'    => array(
										'label'     => 'Start Date',
										'sortable'  => '',
										'order_key' => '',
									),
									'complete_date' => array(
										'label'     => 'Completion Date',
										'sortable'  => '',
										'order_key' => '',
									),
									'score'         => array(
										'label'     => 'Score',
										'sortable'  => '',
										'order_key' => '',
									),
									'time_spent'    => array(
										'label'     => 'Time Spent',
										'sortable'  => '',
										'order_key' => '',
									),
									'points_earned' => array(
										'label'     => 'Points Earned',
										'sortable'  => '',
										'order_key' => '',
									),
									'attempts'      => array(
										'label'     => 'Attempts',
										'sortable'  => '',
										'order_key' => '',
									),
								),
							),
							HOUR_IN_SECONDS );
						foreach ( $steps as $step ) {
							if ( is_null( $step['activity'] ) ) {
								$exports[] = array(
									'user_id'       => $user,
									'student_name'  => bp_core_get_user_displayname( $user ),
									'course_name'   => $course->post_title,
									$key            => wp_strip_all_tags( $step['title'] ),
									'start_date'    => '-',
									'complete_date' => '-',
									'score'         => $step['score'],
									'time_spent'    => '-',
									'points_earned' => '-',
									'attempts'      => $step['attempt'],
								);
							} else {
								$time_spent = bp_ld_time_spent( $step['activity'] );
								$start_date = date_i18n( bp_get_option( 'date_format' ),
									intval( $step['activity']->activity_started ) );
								$points     = bpLdCoursePointsEarned( $step['activity'] );
								if ( is_null( $step['activity'] ) ) {
									$end_date = '';
								} else {
									$end_date = ( $step['activity']->activity_completed ) ? date_i18n( bp_get_option( 'date_format' ),
										intval( $step['activity']->activity_completed ) ) : '-';
								}
								$exports[] = array(
									'user_id'       => $user,
									'student_name'  => bp_core_get_user_displayname( $user ),
									'course_name'   => $course->post_title,
									$key            => wp_strip_all_tags( $step['title'] ),
									'start_date'    => $start_date,
									'complete_date' => $end_date,
									'score'         => $step['score'],
									'time_spent'    => $time_spent,
									'points_earned' => $points,
									'attempts'      => $step['attempt'],
								);
							}
						}
						set_transient( $hash, $exports, HOUR_IN_SECONDS );
					} elseif ( isset( $_REQUEST ) && isset( $_REQUEST['course'] ) && isset( $_REQUEST['step'] ) && 'sfwd-assignment' === $_REQUEST['step'] ) {
						$label = __( 'ASSIGNMENT', 'buddyboss' );
						$data  = bp_ld_get_course_all_steps( $course->ID, $user, 'assignment' );
						$steps = $data['steps'];
						set_transient( "{$hash}_info",
							array(
								'filename' => 'learndash-report-export-group-' . $_REQUEST['group'] . '.csv',
								'columns'  => array(
									'user_id'      => array(
										'label'     => 'User ID',
										'sortable'  => '',
										'order_key' => '',
									),
									'student_name' => array(
										'label'     => 'Student',
										'sortable'  => '',
										'order_key' => '',
									),
									'course_name'  => array(
										'label'     => 'Course',
										'sortable'  => '',
										'order_key' => '',
									),
									'assignment'   => array(
										'label'     => 'Assignment',
										'sortable'  => '',
										'order_key' => '',
									),
									'graded_date'  => array(
										'label'     => 'Graded Date',
										'sortable'  => '',
										'order_key' => '',
									),
									'score'        => array(
										'label'     => 'Score',
										'sortable'  => '',
										'order_key' => '',
									),
								),
							),
							HOUR_IN_SECONDS );
						foreach ( $steps as $step ) {
							$exports[] = array(
								'user_id'      => $user,
								'student_name' => bp_core_get_user_displayname( $user ),
								'course_name'  => $course->post_title,
								'assignment'   => wp_strip_all_tags( $step['title'] ),
								'graded_date'  => $step['graded'],
								'score'        => $step['score'],
							);
						}
						set_transient( $hash, $exports, HOUR_IN_SECONDS );
					} else {
						$label = __( 'Step', 'buddyboss' );
						$data  = bp_ld_get_course_all_steps( $course->ID, $user, 'all' );
						$steps = $data['steps'];
						set_transient( "{$hash}_info",
							array(
								'filename' => 'learndash-report-export-group-' . $_REQUEST['group'] . '.csv',
								'columns'  => array(
									'user_id'       => array(
										'label'     => 'User ID',
										'sortable'  => '',
										'order_key' => '',
									),
									'student_name'  => array(
										'label'     => 'Student',
										'sortable'  => '',
										'order_key' => '',
									),
									'course_name'   => array(
										'label'     => 'Course',
										'sortable'  => '',
										'order_key' => '',
									),
									'step'          => array(
										'label'     => 'Step',
										'sortable'  => '',
										'order_key' => '',
									),
									'start_date'    => array(
										'label'     => 'Start Date',
										'sortable'  => '',
										'order_key' => '',
									),
									'complete_date' => array(
										'label'     => 'Completion Date',
										'sortable'  => '',
										'order_key' => '',
									),
									'time_spent'    => array(
										'label'     => 'Time Spent',
										'sortable'  => '',
										'order_key' => '',
									),
									'points_earned' => array(
										'label'     => 'Points Earned',
										'sortable'  => '',
										'order_key' => '',
									),
								),
							),
							HOUR_IN_SECONDS );
						foreach ( $steps as $step ) {
							if ( is_null( $step['activity'] ) ) {
								$exports[] = array(
									'user_id'       => $user,
									'student_name'  => bp_core_get_user_displayname( $user ),
									'course_name'   => $course->post_title,
									'step'          => wp_strip_all_tags( $step['title'] ),
									'start_date'    => '-',
									'complete_date' => '-',
									'time_spent'    => '-',
									'points_earned' => '-',
								);
							} else {
								$time_spent = bp_ld_time_spent( $step['activity'] );
								$start_date = date_i18n( bp_get_option( 'date_format' ),
									intval( $step['activity']->activity_started ) );
								$points     = bpLdCoursePointsEarned( $step['activity'] );
								if ( is_null( $step['activity'] ) ) {
									$end_date = '';
								} else {
									$end_date = ( $step['activity']->activity_completed ) ? date_i18n( bp_get_option( 'date_format' ),
										intval( $step['activity']->activity_completed ) ) : '-';
								}
								$exports[] = array(
									'user_id'       => $user,
									'student_name'  => bp_core_get_user_displayname( $user ),
									'course_name'   => $course->post_title,
									'step'          => wp_strip_all_tags( $step['title'] ),
									'start_date'    => $start_date,
									'complete_date' => $end_date,
									'time_spent'    => $time_spent,
									'points_earned' => $points,
								);
							}
						}
						set_transient( $hash, $exports, HOUR_IN_SECONDS );
					}
				}
			} elseif ( empty( $_REQUEST['course'] ) && ! empty( $_REQUEST['user'] ) && ! empty( $_REQUEST['step'] ) ) {
				set_transient( "{$hash}_info",
					array(
						'filename' => 'learndash-report-export-group-' . $_REQUEST['group'] . '.csv',
						'columns'  => array(
							'user_id'       => array(
								'label'     => 'User ID',
								'sortable'  => '',
								'order_key' => '',
							),
							'student_name'  => array(
								'label'     => 'Student',
								'sortable'  => '',
								'order_key' => '',
							),
							'course_name'   => array(
								'label'     => 'Course',
								'sortable'  => '',
								'order_key' => '',
							),
							'step'          => array(
								'label'     => 'Step',
								'sortable'  => '',
								'order_key' => '',
							),
							'start_date'    => array(
								'label'     => 'Start Date',
								'sortable'  => '',
								'order_key' => '',
							),
							'complete_date' => array(
								'label'     => 'Completion Date',
								'sortable'  => '',
								'order_key' => '',
							),
							'time_spent'    => array(
								'label'     => 'Time Spent',
								'sortable'  => '',
								'order_key' => '',
							),
							'points_earned' => array(
								'label'     => 'Points Earned',
								'sortable'  => '',
								'order_key' => '',
							),
						),
					),
					HOUR_IN_SECONDS );
				$user      = ( isset( $_REQUEST ) && isset( $_REQUEST['user'] ) && '' !== $_REQUEST['user'] ) ? $_REQUEST['user'] : bp_loggedin_user_id();
				$group_id  = bp_ld_sync( 'buddypress' )->helpers->getLearndashGroupId( $_REQUEST['group'] );
				$courseIds = learndash_group_enrolled_courses( $group_id );
				$courses   = array_map( 'get_post',
					apply_filters( 'bp_ld_learndash_group_enrolled_courses', $courseIds, $group_id ) );
				$exports   = array();
				foreach ( $courses as $course ) {
					$course_users = learndash_get_groups_user_ids( $group_id );
					if ( isset( $_REQUEST ) && isset( $_REQUEST['step'] ) && '' === $_REQUEST['step'] ) {
						$data  = bp_ld_get_course_all_steps( $course->ID, $user, 'all' );
						$steps = $data['steps'];
						$label = __( 'Step', 'buddyboss' );
					} elseif ( isset( $_REQUEST ) && isset( $_REQUEST['course'] ) && isset( $_REQUEST['step'] ) && 'all' === $_REQUEST['step'] ) {
						$data  = bp_ld_get_course_all_steps( $course->ID, $user, 'all' );
						$steps = $data['steps'];
						$label = __( 'Step', 'buddyboss' );
					} elseif ( isset( $_REQUEST ) && isset( $_REQUEST['course'] ) && isset( $_REQUEST['step'] ) && 'sfwd-lessons' === $_REQUEST['step'] ) {
						$data  = bp_ld_get_course_all_steps( $course->ID, $user, 'lesson' );
						$steps = $data['steps'];
						$label = \LearnDash_Custom_Label::get_label( 'lesson' );
					} elseif ( isset( $_REQUEST ) && isset( $_REQUEST['course'] ) && isset( $_REQUEST['step'] ) && 'sfwd-topic' === $_REQUEST['step'] ) {
						$data  = bp_ld_get_course_all_steps( $course->ID, $user, 'topic' );
						$steps = $data['steps'];
						$label = \LearnDash_Custom_Label::get_label( 'topic' );
					} elseif ( isset( $_REQUEST ) && isset( $_REQUEST['course'] ) && isset( $_REQUEST['step'] ) && 'sfwd-quiz' === $_REQUEST['step'] ) {
						$data  = bp_ld_get_course_all_steps( $course->ID, $user, 'quiz' );
						$steps = $data['steps'];
						$label = \LearnDash_Custom_Label::get_label( 'quiz' );
					} elseif ( isset( $_REQUEST ) && isset( $_REQUEST['course'] ) && isset( $_REQUEST['step'] ) && 'sfwd-assignment' === $_REQUEST['step'] ) {
						$data  = bp_ld_get_course_all_steps( $course->ID, $user, 'assignment' );
						$steps = $data['steps'];
						$label = __( 'ASSIGNMENT', 'buddyboss' );
					} else {
						$data  = bp_ld_get_course_all_steps( $course->ID, $user, 'all' );
						$steps = $data['steps'];
						$label = __( 'Step', 'buddyboss' );
					}
					foreach ( $steps as $step ) {
						if ( is_null( $step['activity'] ) ) {
							$exports[] = array(
								'user_id'       => $user,
								'student_name'  => bp_core_get_user_displayname( $user ),
								'course_name'   => $course->post_title,
								'step'          => $step['title'],
								'start_date'    => '-',
								'complete_date' => '-',
								'time_spent'    => '-',
								'points_earned' => '-',
							);
						} else {
							$time_spent = bp_ld_time_spent( $step['activity'] );
							$start_date = date_i18n( bp_get_option( 'date_format' ),
								intval( $step['activity']->activity_started ) );
							$points     = bpLdCoursePointsEarned( $step['activity'] );
							if ( is_null( $step['activity'] ) ) {
								$end_date = '';
							} else {
								$end_date = ( $step['activity']->activity_completed ) ? date_i18n( bp_get_option( 'date_format' ),
									intval( $step['activity']->activity_completed ) ) : '-';
							}
							$exports[] = array(
								'user_id'       => $user,
								'student_name'  => bp_core_get_user_displayname( $user ),
								'course_name'   => $course->post_title,
								'step'          => $step['title'],
								'start_date'    => $start_date,
								'complete_date' => $end_date,
								'time_spent'    => $time_spent,
								'points_earned' => $points,
							);
						}
					}
				}
				set_transient( $hash, $exports, HOUR_IN_SECONDS );
			}
		} else {
			if ( empty( $_REQUEST['course'] ) && empty( $_REQUEST['step'] ) ) {
				$user      = ( isset( $_REQUEST ) && isset( $_REQUEST['user'] ) && '' !== $_REQUEST['user'] ) ? $_REQUEST['user'] : bp_loggedin_user_id();
				$group_id  = bp_ld_sync( 'buddypress' )->helpers->getLearndashGroupId( $_REQUEST['group'] );
				$courseIds = learndash_group_enrolled_courses( $group_id );
				$label     = 'Step';
				$courses   = array_map( 'get_post',
					apply_filters( 'bp_ld_learndash_group_enrolled_courses', $courseIds, $group_id ) );
				$exports   = array();
				foreach ( $courses as $course ) {
					$course_users = learndash_get_groups_user_ids( $group_id );

					if ( isset( $_REQUEST ) && isset( $_REQUEST['step'] ) && '' === $_REQUEST['step'] ) {
						$data  = bp_ld_get_course_all_steps( $course->ID, $user, 'all' );
						$steps = $data['steps'];
						$label = __( 'Step', 'buddyboss' );
					} elseif ( isset( $_REQUEST ) && isset( $_REQUEST['course'] ) && isset( $_REQUEST['step'] ) && 'all' === $_REQUEST['step'] ) {
						$data  = bp_ld_get_course_all_steps( $course->ID, $user, 'all' );
						$steps = $data['steps'];
						$label = __( 'Step', 'buddyboss' );
					} elseif ( isset( $_REQUEST ) && isset( $_REQUEST['course'] ) && isset( $_REQUEST['step'] ) && 'sfwd-lessons' === $_REQUEST['step'] ) {
						$data  = bp_ld_get_course_all_steps( $course->ID, $user, 'lesson' );
						$steps = $data['steps'];
						$label = \LearnDash_Custom_Label::get_label( 'lesson' );
					} elseif ( isset( $_REQUEST ) && isset( $_REQUEST['course'] ) && isset( $_REQUEST['step'] ) && 'sfwd-topic' === $_REQUEST['step'] ) {
						$data  = bp_ld_get_course_all_steps( $course->ID, $user, 'topic' );
						$steps = $data['steps'];
						$label = \LearnDash_Custom_Label::get_label( 'topic' );
					} elseif ( isset( $_REQUEST ) && isset( $_REQUEST['course'] ) && isset( $_REQUEST['step'] ) && 'sfwd-quiz' === $_REQUEST['step'] ) {
						$data  = bp_ld_get_course_all_steps( $course->ID, $user, 'quiz' );
						$steps = $data['steps'];
						$label = \LearnDash_Custom_Label::get_label( 'quiz' );
					} elseif ( isset( $_REQUEST ) && isset( $_REQUEST['course'] ) && isset( $_REQUEST['step'] ) && 'sfwd-assignment' === $_REQUEST['step'] ) {
						$data  = bp_ld_get_course_all_steps( $course->ID, $user, 'assignment' );
						$steps = $data['steps'];
						$label = __( 'ASSIGNMENT', 'buddyboss' );
					} else {
						$data  = bp_ld_get_course_all_steps( $course->ID, $user, 'all' );
						$steps = $data['steps'];
						$label = __( 'Step', 'buddyboss' );
					}

					$key = str_replace( ' ', '_', strtolower( $label ) );
					set_transient( "{$hash}_info",
						array(
							'filename' => 'learndash-report-export-group-' . $_REQUEST['group'] . '.csv',
							'columns'  => array(
								'user_id'       => array(
									'label'     => 'User ID',
									'sortable'  => '',
									'order_key' => '',
								),
								'student_name'  => array(
									'label'     => 'Student',
									'sortable'  => '',
									'order_key' => '',
								),
								'course_name'   => array(
									'label'     => 'Course',
									'sortable'  => '',
									'order_key' => '',
								),
								$key            => array(
									'label'     => $label,
									'sortable'  => '',
									'order_key' => '',
								),
								'start_date'    => array(
									'label'     => 'Start Date',
									'sortable'  => '',
									'order_key' => '',
								),
								'complete_date' => array(
									'label'     => 'Completion Date',
									'sortable'  => '',
									'order_key' => '',
								),
								'time_spent'    => array(
									'label'     => 'Time Spent',
									'sortable'  => '',
									'order_key' => '',
								),
								'points_earned' => array(
									'label'     => 'Points Earned',
									'sortable'  => '',
									'order_key' => '',
								),
							),
						),
						HOUR_IN_SECONDS );
					foreach ( $steps as $step ) {
						if ( is_null( $step['activity'] ) ) {
							$exports[] = array(
								'user_id'       => $user,
								'student_name'  => bp_core_get_user_displayname( $user ),
								'course_name'   => $course->post_title,
								$key            => wp_strip_all_tags( $step['title'] ),
								'start_date'    => '-',
								'complete_date' => '-',
								'time_spent'    => '-',
								'points_earned' => '-',
							);
						} else {
							$time_spent = bp_ld_time_spent( $step['activity'] );
							$start_date = date_i18n( bp_get_option( 'date_format' ),
								intval( $step['activity']->activity_started ) );
							$points     = bpLdCoursePointsEarned( $step['activity'] );
							if ( is_null( $step['activity'] ) ) {
								$end_date = '';
							} else {
								$end_date = ( $step['activity']->activity_completed ) ? date_i18n( bp_get_option( 'date_format' ),
									intval( $step['activity']->activity_completed ) ) : '-';
							}
							$exports[] = array(
								'user_id'       => $user,
								'student_name'  => bp_core_get_user_displayname( $user ),
								'course_name'   => $course->post_title,
								$key            => wp_strip_all_tags( $step['title'] ),
								'start_date'    => $start_date,
								'complete_date' => $end_date,
								'time_spent'    => $time_spent,
								'points_earned' => $points,
							);
						}
					}
				}
				set_transient( $hash, $exports, HOUR_IN_SECONDS );
			} else {
				if ( isset( $_REQUEST['step'] ) && 'all' != $_REQUEST['step'] && isset( $_REQUEST['course'] ) && '' === $_REQUEST['course'] ) {
					$user      = ( isset( $_REQUEST ) && isset( $_REQUEST['user'] ) && '' !== $_REQUEST['user'] ) ? $_REQUEST['user'] : bp_loggedin_user_id();
					$group_id  = bp_ld_sync( 'buddypress' )->helpers->getLearndashGroupId( $_REQUEST['group'] );
					$courseIds = learndash_group_enrolled_courses( $group_id );
					$label     = 'Step';
					$exports   = array();
					$courses   = array_map( 'get_post',
						apply_filters( 'bp_ld_learndash_group_enrolled_courses', $courseIds, $group_id ) );
					foreach ( $courses as $course ) {
						$course_users = learndash_get_groups_user_ids( $group_id );

						if ( isset( $_REQUEST ) && isset( $_REQUEST['step'] ) && '' === $_REQUEST['step'] ) {
							$data  = bp_ld_get_course_all_steps( $course->ID, $user, 'all' );
							$steps = $data['steps'];
							$label = __( 'Step', 'buddyboss' );
						} elseif ( isset( $_REQUEST ) && isset( $_REQUEST['course'] ) && isset( $_REQUEST['step'] ) && 'all' === $_REQUEST['step'] ) {
							$data  = bp_ld_get_course_all_steps( $course->ID, $user, 'all' );
							$steps = $data['steps'];
							$label = __( 'Step', 'buddyboss' );
						} elseif ( isset( $_REQUEST ) && isset( $_REQUEST['course'] ) && isset( $_REQUEST['step'] ) && 'sfwd-lessons' === $_REQUEST['step'] ) {
							$data  = bp_ld_get_course_all_steps( $course->ID, $user, 'lesson' );
							$steps = $data['steps'];
							$label = \LearnDash_Custom_Label::get_label( 'lesson' );
						} elseif ( isset( $_REQUEST ) && isset( $_REQUEST['course'] ) && isset( $_REQUEST['step'] ) && 'sfwd-topic' === $_REQUEST['step'] ) {
							$data  = bp_ld_get_course_all_steps( $course->ID, $user, 'topic' );
							$steps = $data['steps'];
							$label = \LearnDash_Custom_Label::get_label( 'topic' );
						} elseif ( isset( $_REQUEST ) && isset( $_REQUEST['course'] ) && isset( $_REQUEST['step'] ) && 'sfwd-quiz' === $_REQUEST['step'] ) {
							$data  = bp_ld_get_course_all_steps( $course->ID, $user, 'quiz' );
							$steps = $data['steps'];
							$label = \LearnDash_Custom_Label::get_label( 'quiz' );
						} elseif ( isset( $_REQUEST ) && isset( $_REQUEST['course'] ) && isset( $_REQUEST['step'] ) && 'sfwd-assignment' === $_REQUEST['step'] ) {
							$data  = bp_ld_get_course_all_steps( $course->ID, $user, 'assignment' );
							$steps = $data['steps'];
							$label = __( 'ASSIGNMENT', 'buddyboss' );
						} else {
							$data  = bp_ld_get_course_all_steps( $course->ID, $user, 'all' );
							$steps = $data['steps'];
							$label = __( 'Step', 'buddyboss' );
						}

						$key = str_replace( ' ', '_', strtolower( $label ) );
						set_transient( "{$hash}_info",
							array(
								'filename' => 'learndash-report-export-group-' . $_REQUEST['group'] . '.csv',
								'columns'  => array(
									'user_id'       => array(
										'label'     => 'User ID',
										'sortable'  => '',
										'order_key' => '',
									),
									'student_name'  => array(
										'label'     => 'Student',
										'sortable'  => '',
										'order_key' => '',
									),
									'course_name'   => array(
										'label'     => 'Course',
										'sortable'  => '',
										'order_key' => '',
									),
									$key            => array(
										'label'     => $label,
										'sortable'  => '',
										'order_key' => '',
									),
									'start_date'    => array(
										'label'     => 'Start Date',
										'sortable'  => '',
										'order_key' => '',
									),
									'complete_date' => array(
										'label'     => 'Completion Date',
										'sortable'  => '',
										'order_key' => '',
									),
									'time_spent'    => array(
										'label'     => 'Time Spent',
										'sortable'  => '',
										'order_key' => '',
									),
									'points_earned' => array(
										'label'     => 'Points Earned',
										'sortable'  => '',
										'order_key' => '',
									),
								),
							),
							HOUR_IN_SECONDS );
						foreach ( $steps as $step ) {
							if ( is_null( $step['activity'] ) ) {
								$exports[] = array(
									'user_id'       => $user,
									'student_name'  => bp_core_get_user_displayname( $user ),
									'course_name'   => $course->post_title,
									$key            => wp_strip_all_tags( $step['title'] ),
									'start_date'    => '-',
									'complete_date' => '-',
									'time_spent'    => '-',
									'points_earned' => '-',
								);
							} else {
								$time_spent = bp_ld_time_spent( $step['activity'] );
								$start_date = date_i18n( bp_get_option( 'date_format' ),
									intval( $step['activity']->activity_started ) );
								$points     = bpLdCoursePointsEarned( $step['activity'] );
								if ( is_null( $step['activity'] ) ) {
									$end_date = '';
								} else {
									$end_date = ( $step['activity']->activity_completed ) ? date_i18n( bp_get_option( 'date_format' ),
										intval( $step['activity']->activity_completed ) ) : '-';
								}
								$exports[] = array(
									'user_id'       => $user,
									'student_name'  => bp_core_get_user_displayname( $user ),
									'course_name'   => $course->post_title,
									$key            => wp_strip_all_tags( $step['title'] ),
									'start_date'    => $start_date,
									'complete_date' => $end_date,
									'time_spent'    => $time_spent,
									'points_earned' => $points,
								);
							}
						}
					}
					set_transient( $hash, $exports, HOUR_IN_SECONDS );
				} elseif ( isset( $_REQUEST['step'] ) && isset( $_REQUEST['course'] ) && '' === $_REQUEST['course'] && '' === $_REQUEST['step'] ) {
					$user      = ( isset( $_REQUEST ) && isset( $_REQUEST['user'] ) && '' !== $_REQUEST['user'] ) ? $_REQUEST['user'] : bp_loggedin_user_id();
					$group_id  = bp_ld_sync( 'buddypress' )->helpers->getLearndashGroupId( $_REQUEST['group'] );
					$courseIds = learndash_group_enrolled_courses( $group_id );
					$label     = 'Step';
					$courses   = array_map( 'get_post',
						apply_filters( 'bp_ld_learndash_group_enrolled_courses', $courseIds, $group_id ) );
					$exports   = array();
					foreach ( $courses as $course ) {
						$course_users = learndash_get_groups_user_ids( $group_id );

						if ( isset( $_REQUEST ) && isset( $_REQUEST['step'] ) && '' === $_REQUEST['step'] ) {
							$data  = bp_ld_get_course_all_steps( $course->ID, $user, 'all' );
							$steps = $data['steps'];
							$label = __( 'Step', 'buddyboss' );
						} elseif ( isset( $_REQUEST ) && isset( $_REQUEST['course'] ) && isset( $_REQUEST['step'] ) && 'all' === $_REQUEST['step'] ) {
							$data  = bp_ld_get_course_all_steps( $course->ID, $user, 'all' );
							$steps = $data['steps'];
							$label = __( 'Step', 'buddyboss' );
						} elseif ( isset( $_REQUEST ) && isset( $_REQUEST['course'] ) && isset( $_REQUEST['step'] ) && 'sfwd-lessons' === $_REQUEST['step'] ) {
							$data  = bp_ld_get_course_all_steps( $course->ID, $user, 'lesson' );
							$steps = $data['steps'];
							$label = \LearnDash_Custom_Label::get_label( 'lesson' );
						} elseif ( isset( $_REQUEST ) && isset( $_REQUEST['course'] ) && isset( $_REQUEST['step'] ) && 'sfwd-topic' === $_REQUEST['step'] ) {
							$data  = bp_ld_get_course_all_steps( $course->ID, $user, 'topic' );
							$steps = $data['steps'];
							$label = \LearnDash_Custom_Label::get_label( 'topic' );
						} elseif ( isset( $_REQUEST ) && isset( $_REQUEST['course'] ) && isset( $_REQUEST['step'] ) && 'sfwd-quiz' === $_REQUEST['step'] ) {
							$data  = bp_ld_get_course_all_steps( $course->ID, $user, 'quiz' );
							$steps = $data['steps'];
							$label = \LearnDash_Custom_Label::get_label( 'quiz' );
						} elseif ( isset( $_REQUEST ) && isset( $_REQUEST['course'] ) && isset( $_REQUEST['step'] ) && 'sfwd-assignment' === $_REQUEST['step'] ) {
							$data  = bp_ld_get_course_all_steps( $course->ID, $user, 'assignment' );
							$steps = $data['steps'];
							$label = __( 'ASSIGNMENT', 'buddyboss' );
						} else {
							$data  = bp_ld_get_course_all_steps( $course->ID, $user, 'all' );
							$steps = $data['steps'];
							$label = __( 'Step', 'buddyboss' );
						}

						$key = str_replace( ' ', '_', strtolower( $label ) );
						set_transient( "{$hash}_info",
							array(
								'filename' => 'learndash-report-export-group-' . $_REQUEST['group'] . '.csv',
								'columns'  => array(
									'user_id'       => array(
										'label'     => 'User ID',
										'sortable'  => '',
										'order_key' => '',
									),
									'student_name'  => array(
										'label'     => 'Student',
										'sortable'  => '',
										'order_key' => '',
									),
									'course_name'   => array(
										'label'     => 'Course',
										'sortable'  => '',
										'order_key' => '',
									),
									$key            => array(
										'label'     => $label,
										'sortable'  => '',
										'order_key' => '',
									),
									'start_date'    => array(
										'label'     => 'Start Date',
										'sortable'  => '',
										'order_key' => '',
									),
									'complete_date' => array(
										'label'     => 'Completion Date',
										'sortable'  => '',
										'order_key' => '',
									),
									'time_spent'    => array(
										'label'     => 'Time Spent',
										'sortable'  => '',
										'order_key' => '',
									),
									'points_earned' => array(
										'label'     => 'Points Earned',
										'sortable'  => '',
										'order_key' => '',
									),
								),
							),
							HOUR_IN_SECONDS );
						foreach ( $steps as $step ) {
							if ( is_null( $step['activity'] ) ) {
								$exports[] = array(
									'user_id'       => $user,
									'student_name'  => bp_core_get_user_displayname( $user ),
									'course_name'   => $course->post_title,
									$key            => wp_strip_all_tags( $step['title'] ),
									'start_date'    => '-',
									'complete_date' => '-',
									'time_spent'    => '-',
									'points_earned' => '-',
								);
							} else {
								$time_spent = bp_ld_time_spent( $step['activity'] );
								$start_date = date_i18n( bp_get_option( 'date_format' ),
									intval( $step['activity']->activity_started ) );
								$points     = bpLdCoursePointsEarned( $step['activity'] );
								if ( is_null( $step['activity'] ) ) {
									$end_date = '';
								} else {
									$end_date = ( $step['activity']->activity_completed ) ? date_i18n( bp_get_option( 'date_format' ),
										intval( $step['activity']->activity_completed ) ) : '-';
								}
								$exports[] = array(
									'user_id'       => $user,
									'student_name'  => bp_core_get_user_displayname( $user ),
									'course_name'   => $course->post_title,
									$key            => wp_strip_all_tags( $step['title'] ),
									'start_date'    => $start_date,
									'complete_date' => $end_date,
									'time_spent'    => $time_spent,
									'points_earned' => $points,
								);
							}
						}
					}
					set_transient( $hash, $exports, HOUR_IN_SECONDS );
				} elseif ( isset( $_REQUEST['step'] ) && isset( $_REQUEST['course'] ) && '' === $_REQUEST['course'] && 'all' === $_REQUEST['step'] ) {
					$user      = ( isset( $_REQUEST ) && isset( $_REQUEST['user'] ) && '' !== $_REQUEST['user'] ) ? $_REQUEST['user'] : bp_loggedin_user_id();
					$group_id  = bp_ld_sync( 'buddypress' )->helpers->getLearndashGroupId( $_REQUEST['group'] );
					$courseIds = learndash_group_enrolled_courses( $group_id );
					$label     = 'Step';
					$courses   = array_map( 'get_post',
						apply_filters( 'bp_ld_learndash_group_enrolled_courses', $courseIds, $group_id ) );
					$exports   = array();
					foreach ( $courses as $course ) {
						$course_users = learndash_get_groups_user_ids( $group_id );

						if ( isset( $_REQUEST ) && isset( $_REQUEST['step'] ) && '' === $_REQUEST['step'] ) {
							$data  = bp_ld_get_course_all_steps( $course->ID, $user, 'all' );
							$steps = $data['steps'];
							$label = __( 'Step', 'buddyboss' );
						} elseif ( isset( $_REQUEST ) && isset( $_REQUEST['course'] ) && isset( $_REQUEST['step'] ) && 'all' === $_REQUEST['step'] ) {
							$data  = bp_ld_get_course_all_steps( $course->ID, $user, 'all' );
							$steps = $data['steps'];
							$label = __( 'Step', 'buddyboss' );
						} elseif ( isset( $_REQUEST ) && isset( $_REQUEST['course'] ) && isset( $_REQUEST['step'] ) && 'sfwd-lessons' === $_REQUEST['step'] ) {
							$data  = bp_ld_get_course_all_steps( $course->ID, $user, 'lesson' );
							$steps = $data['steps'];
							$label = \LearnDash_Custom_Label::get_label( 'lesson' );
						} elseif ( isset( $_REQUEST ) && isset( $_REQUEST['course'] ) && isset( $_REQUEST['step'] ) && 'sfwd-topic' === $_REQUEST['step'] ) {
							$data  = bp_ld_get_course_all_steps( $course->ID, $user, 'topic' );
							$steps = $data['steps'];
							$label = \LearnDash_Custom_Label::get_label( 'topic' );
						} elseif ( isset( $_REQUEST ) && isset( $_REQUEST['course'] ) && isset( $_REQUEST['step'] ) && 'sfwd-quiz' === $_REQUEST['step'] ) {
							$data  = bp_ld_get_course_all_steps( $course->ID, $user, 'quiz' );
							$steps = $data['steps'];
							$label = \LearnDash_Custom_Label::get_label( 'quiz' );
						} elseif ( isset( $_REQUEST ) && isset( $_REQUEST['course'] ) && isset( $_REQUEST['step'] ) && 'sfwd-assignment' === $_REQUEST['step'] ) {
							$data  = bp_ld_get_course_all_steps( $course->ID, $user, 'assignment' );
							$steps = $data['steps'];
							$label = __( 'ASSIGNMENT', 'buddyboss' );
						} else {
							$data  = bp_ld_get_course_all_steps( $course->ID, $user, 'all' );
							$steps = $data['steps'];
							$label = __( 'Step', 'buddyboss' );
						}

						$key = str_replace( ' ', '_', strtolower( $label ) );
						set_transient( "{$hash}_info",
							array(
								'filename' => 'learndash-report-export-group-' . $_REQUEST['group'] . '.csv',
								'columns'  => array(
									'user_id'       => array(
										'label'     => 'User ID',
										'sortable'  => '',
										'order_key' => '',
									),
									'student_name'  => array(
										'label'     => 'Student',
										'sortable'  => '',
										'order_key' => '',
									),
									'course_name'   => array(
										'label'     => 'Course',
										'sortable'  => '',
										'order_key' => '',
									),
									$key            => array(
										'label'     => $label,
										'sortable'  => '',
										'order_key' => '',
									),
									'start_date'    => array(
										'label'     => 'Start Date',
										'sortable'  => '',
										'order_key' => '',
									),
									'complete_date' => array(
										'label'     => 'Completion Date',
										'sortable'  => '',
										'order_key' => '',
									),
									'time_spent'    => array(
										'label'     => 'Time Spent',
										'sortable'  => '',
										'order_key' => '',
									),
									'points_earned' => array(
										'label'     => 'Points Earned',
										'sortable'  => '',
										'order_key' => '',
									),
								),
							),
							HOUR_IN_SECONDS );
						foreach ( $steps as $step ) {
							if ( is_null( $step['activity'] ) ) {
								$exports[] = array(
									'user_id'       => $user,
									'student_name'  => bp_core_get_user_displayname( $user ),
									'course_name'   => $course->post_title,
									$key            => wp_strip_all_tags( $step['title'] ),
									'start_date'    => '-',
									'complete_date' => '-',
									'time_spent'    => '-',
									'points_earned' => '-',
								);
							} else {
								$time_spent = bp_ld_time_spent( $step['activity'] );
								$start_date = date_i18n( bp_get_option( 'date_format' ),
									intval( $step['activity']->activity_started ) );
								$points     = bpLdCoursePointsEarned( $step['activity'] );
								if ( is_null( $step['activity'] ) ) {
									$end_date = '';
								} else {
									$end_date = ( $step['activity']->activity_completed ) ? date_i18n( bp_get_option( 'date_format' ),
										intval( $step['activity']->activity_completed ) ) : '-';
								}
								$exports[] = array(
									'user_id'       => $user,
									'student_name'  => bp_core_get_user_displayname( $user ),
									'course_name'   => $course->post_title,
									$key            => wp_strip_all_tags( $step['title'] ),
									'start_date'    => $start_date,
									'complete_date' => $end_date,
									'time_spent'    => $time_spent,
									'points_earned' => $points,
								);
							}
						}
					}
					set_transient( $hash, $exports, HOUR_IN_SECONDS );
				} elseif ( isset( $_REQUEST['step'] ) && isset( $_REQUEST['course'] ) && '' !== $_REQUEST['course'] ) {
//					require bp_locate_template( 'groups/single/reports-single-user-single-courses.php', false, false );
					$user      = ( isset( $_REQUEST ) && isset( $_REQUEST['user'] ) && '' !== $_REQUEST['user'] ) ? $_REQUEST['user'] : bp_loggedin_user_id();
					$group_id  = bp_ld_sync( 'buddypress' )->helpers->getLearndashGroupId( $_REQUEST['group'] );
					$courseIds = array( $_REQUEST['course'] );
					$label     = 'Step';
					$courses   = array_map( 'get_post',
						apply_filters( 'bp_ld_learndash_group_enrolled_courses', $courseIds, $group_id ) );
					foreach ( $courses as $course ) {
						$course_users = learndash_get_groups_user_ids( $group_id );
						if ( isset( $_REQUEST ) && isset( $_REQUEST['user'] ) ) {
							$data  = bp_ld_get_course_all_steps( $course->ID, $_REQUEST['user'], 'all' );
							$steps = $data['steps'];
							$label = __( 'Step', 'buddyboss' );
							$key   = str_replace( ' ', '_', strtolower( $label ) );
							set_transient( "{$hash}_info",
								array(
									'filename' => 'learndash-report-export-group-' . $_REQUEST['group'] . '.csv',
									'columns'  => array(
										'user_id'       => array(
											'label'     => 'User ID',
											'sortable'  => '',
											'order_key' => '',
										),
										'student_name'  => array(
											'label'     => 'Student',
											'sortable'  => '',
											'order_key' => '',
										),
										'course_name'   => array(
											'label'     => 'Course',
											'sortable'  => '',
											'order_key' => '',
										),
										$key            => array(
											'label'     => $label,
											'sortable'  => '',
											'order_key' => '',
										),
										'start_date'    => array(
											'label'     => 'Start Date',
											'sortable'  => '',
											'order_key' => '',
										),
										'complete_date' => array(
											'label'     => 'Completion Date',
											'sortable'  => '',
											'order_key' => '',
										),
										'time_spent'    => array(
											'label'     => 'Time Spent',
											'sortable'  => '',
											'order_key' => '',
										),
										'points_earned' => array(
											'label'     => 'Points Earned',
											'sortable'  => '',
											'order_key' => '',
										),
									),
								),
								HOUR_IN_SECONDS );
							foreach ( $steps as $step ) {
								if ( is_null( $step['activity'] ) ) {
									$exports[] = array(
										'user_id'       => $user,
										'student_name'  => bp_core_get_user_displayname( $user ),
										'course_name'   => $course->post_title,
										$key            => wp_strip_all_tags( $step['title'] ),
										'start_date'    => '-',
										'complete_date' => '-',
										'time_spent'    => '-',
										'points_earned' => '-',
									);
								} else {
									$time_spent = bp_ld_time_spent( $step['activity'] );
									$start_date = date_i18n( bp_get_option( 'date_format' ),
										intval( $step['activity']->activity_started ) );
									$points     = bpLdCoursePointsEarned( $step['activity'] );
									if ( is_null( $step['activity'] ) ) {
										$end_date = '';
									} else {
										$end_date = ( $step['activity']->activity_completed ) ? date_i18n( bp_get_option( 'date_format' ),
											intval( $step['activity']->activity_completed ) ) : '-';
									}
									$exports[] = array(
										'user_id'       => $user,
										'student_name'  => bp_core_get_user_displayname( $user ),
										'course_name'   => $course->post_title,
										$key            => wp_strip_all_tags( $step['title'] ),
										'start_date'    => $start_date,
										'complete_date' => $end_date,
										'time_spent'    => $time_spent,
										'points_earned' => $points,
									);
								}
							}
							set_transient( $hash, $exports, HOUR_IN_SECONDS );
						} else {
							if ( isset( $_REQUEST ) && isset( $_REQUEST['step'] ) && '' === $_REQUEST['step'] ) {
								$user      = ( isset( $_REQUEST ) && isset( $_REQUEST['user'] ) && '' !== $_REQUEST['user'] ) ? $_REQUEST['user'] : bp_loggedin_user_id();
								$data  = bp_ld_get_course_all_steps( $course->ID, bp_loggedin_user_id(), 'all' );
								$steps = $data['steps'];
								$label = __( 'Step', 'buddyboss' );
								$key   = str_replace( ' ', '_', strtolower( $label ) );
								set_transient( "{$hash}_info",
									array(
										'filename' => 'learndash-report-export-group-' . $_REQUEST['group'] . '.csv',
										'columns'  => array(
											'user_id'       => array(
												'label'     => 'User ID',
												'sortable'  => '',
												'order_key' => '',
											),
											'student_name'  => array(
												'label'     => 'Student',
												'sortable'  => '',
												'order_key' => '',
											),
											'course_name'   => array(
												'label'     => 'Course',
												'sortable'  => '',
												'order_key' => '',
											),
											$key            => array(
												'label'     => $label,
												'sortable'  => '',
												'order_key' => '',
											),
											'start_date'    => array(
												'label'     => 'Start Date',
												'sortable'  => '',
												'order_key' => '',
											),
											'complete_date' => array(
												'label'     => 'Completion Date',
												'sortable'  => '',
												'order_key' => '',
											),
											'time_spent'    => array(
												'label'     => 'Time Spent',
												'sortable'  => '',
												'order_key' => '',
											),
											'points_earned' => array(
												'label'     => 'Points Earned',
												'sortable'  => '',
												'order_key' => '',
											),
										),
									),
									HOUR_IN_SECONDS );
								foreach ( $steps as $step ) {
									if ( is_null( $step['activity'] ) ) {
										$exports[] = array(
											'user_id'       => $user,
											'student_name'  => bp_core_get_user_displayname( $user ),
											'course_name'   => $course->post_title,
											$key            => wp_strip_all_tags( $step['title'] ),
											'start_date'    => '-',
											'complete_date' => '-',
											'time_spent'    => '-',
											'points_earned' => '-',
										);
									} else {
										$time_spent = bp_ld_time_spent( $step['activity'] );
										$start_date = date_i18n( bp_get_option( 'date_format' ),
											intval( $step['activity']->activity_started ) );
										$points     = bpLdCoursePointsEarned( $step['activity'] );
										if ( is_null( $step['activity'] ) ) {
											$end_date = '';
										} else {
											$end_date = ( $step['activity']->activity_completed ) ? date_i18n( bp_get_option( 'date_format' ),
												intval( $step['activity']->activity_completed ) ) : '-';
										}
										$exports[] = array(
											'user_id'       => $user,
											'student_name'  => bp_core_get_user_displayname( $user ),
											'course_name'   => $course->post_title,
											$key            => wp_strip_all_tags( $step['title'] ),
											'start_date'    => $start_date,
											'complete_date' => $end_date,
											'time_spent'    => $time_spent,
											'points_earned' => $points,
										);
									}
								}
								set_transient( $hash, $exports, HOUR_IN_SECONDS );
							} elseif ( isset( $_REQUEST ) && isset( $_REQUEST['course'] ) && isset( $_REQUEST['step'] ) && 'all' === $_REQUEST['step'] ) {
								$user      = ( isset( $_REQUEST ) && isset( $_REQUEST['user'] ) && '' !== $_REQUEST['user'] ) ? $_REQUEST['user'] : bp_loggedin_user_id();
								$label = __( 'Step', 'buddyboss' );
								$data  = bp_ld_get_course_all_steps( $course->ID, bp_loggedin_user_id(), 'all' );
								$steps = $data['steps'];
								$key   = str_replace( ' ', '_', strtolower( $label ) );
								set_transient( "{$hash}_info",
									array(
										'filename' => 'learndash-report-export-group-' . $_REQUEST['group'] . '.csv',
										'columns'  => array(
											'user_id'       => array(
												'label'     => 'User ID',
												'sortable'  => '',
												'order_key' => '',
											),
											'student_name'  => array(
												'label'     => 'Student',
												'sortable'  => '',
												'order_key' => '',
											),
											'course_name'   => array(
												'label'     => 'Course',
												'sortable'  => '',
												'order_key' => '',
											),
											$key            => array(
												'label'     => $label,
												'sortable'  => '',
												'order_key' => '',
											),
											'start_date'    => array(
												'label'     => 'Start Date',
												'sortable'  => '',
												'order_key' => '',
											),
											'complete_date' => array(
												'label'     => 'Completion Date',
												'sortable'  => '',
												'order_key' => '',
											),
											'time_spent'    => array(
												'label'     => 'Time Spent',
												'sortable'  => '',
												'order_key' => '',
											),
											'points_earned' => array(
												'label'     => 'Points Earned',
												'sortable'  => '',
												'order_key' => '',
											),
										),
									),
									HOUR_IN_SECONDS );
								foreach ( $steps as $step ) {
									if ( is_null( $step['activity'] ) ) {
										$exports[] = array(
											'user_id'       => $user,
											'student_name'  => bp_core_get_user_displayname( $user ),
											'course_name'   => $course->post_title,
											$key            => wp_strip_all_tags( $step['title'] ),
											'start_date'    => '-',
											'complete_date' => '-',
											'time_spent'    => '-',
											'points_earned' => '-',
										);
									} else {
										$time_spent = bp_ld_time_spent( $step['activity'] );
										$start_date = date_i18n( bp_get_option( 'date_format' ),
											intval( $step['activity']->activity_started ) );
										$points     = bpLdCoursePointsEarned( $step['activity'] );
										if ( is_null( $step['activity'] ) ) {
											$end_date = '';
										} else {
											$end_date = ( $step['activity']->activity_completed ) ? date_i18n( bp_get_option( 'date_format' ),
												intval( $step['activity']->activity_completed ) ) : '-';
										}
										$exports[] = array(
											'user_id'       => $user,
											'student_name'  => bp_core_get_user_displayname( $user ),
											'course_name'   => $course->post_title,
											$key            => wp_strip_all_tags( $step['title'] ),
											'start_date'    => $start_date,
											'complete_date' => $end_date,
											'time_spent'    => $time_spent,
											'points_earned' => $points,
										);
									}
								}
								set_transient( $hash, $exports, HOUR_IN_SECONDS );
							} elseif ( isset( $_REQUEST ) && isset( $_REQUEST['course'] ) && isset( $_REQUEST['step'] ) && 'sfwd-lessons' === $_REQUEST['step'] ) {
								$user      = ( isset( $_REQUEST ) && isset( $_REQUEST['user'] ) && '' !== $_REQUEST['user'] ) ? $_REQUEST['user'] : bp_loggedin_user_id();
								$label = \LearnDash_Custom_Label::get_label( 'lesson' );
								$data  = bp_ld_get_course_all_steps( $course->ID, bp_loggedin_user_id(), 'lesson' );
								$steps = $data['steps'];
								$key   = str_replace( ' ', '_', strtolower( $label ) );
								set_transient( "{$hash}_info",
									array(
										'filename' => 'learndash-report-export-group-' . $_REQUEST['group'] . '.csv',
										'columns'  => array(
											'user_id'       => array(
												'label'     => 'User ID',
												'sortable'  => '',
												'order_key' => '',
											),
											'student_name'  => array(
												'label'     => 'Student',
												'sortable'  => '',
												'order_key' => '',
											),
											'course_name'   => array(
												'label'     => 'Course',
												'sortable'  => '',
												'order_key' => '',
											),
											$key            => array(
												'label'     => $label,
												'sortable'  => '',
												'order_key' => '',
											),
											'start_date'    => array(
												'label'     => 'Start Date',
												'sortable'  => '',
												'order_key' => '',
											),
											'complete_date' => array(
												'label'     => 'Completion Date',
												'sortable'  => '',
												'order_key' => '',
											),
											'time_spent'    => array(
												'label'     => 'Time Spent',
												'sortable'  => '',
												'order_key' => '',
											),
											'points_earned' => array(
												'label'     => 'Points Earned',
												'sortable'  => '',
												'order_key' => '',
											),
										),
									),
									HOUR_IN_SECONDS );
								foreach ( $steps as $step ) {
									if ( is_null( $step['activity'] ) ) {
										$exports[] = array(
											'user_id'       => $user,
											'student_name'  => bp_core_get_user_displayname( $user ),
											'course_name'   => $course->post_title,
											$key            => wp_strip_all_tags( $step['title'] ),
											'start_date'    => '-',
											'complete_date' => '-',
											'time_spent'    => '-',
											'points_earned' => '-',
										);
									} else {
										$time_spent = bp_ld_time_spent( $step['activity'] );
										$start_date = date_i18n( bp_get_option( 'date_format' ),
											intval( $step['activity']->activity_started ) );
										$points     = bpLdCoursePointsEarned( $step['activity'] );
										if ( is_null( $step['activity'] ) ) {
											$end_date = '';
										} else {
											$end_date = ( $step['activity']->activity_completed ) ? date_i18n( bp_get_option( 'date_format' ),
												intval( $step['activity']->activity_completed ) ) : '-';
										}
										$exports[] = array(
											'user_id'       => $user,
											'student_name'  => bp_core_get_user_displayname( $user ),
											'course_name'   => $course->post_title,
											$key            => wp_strip_all_tags( $step['title'] ),
											'start_date'    => $start_date,
											'complete_date' => $end_date,
											'time_spent'    => $time_spent,
											'points_earned' => $points,
										);
									}
								}
								set_transient( $hash, $exports, HOUR_IN_SECONDS );
							} elseif ( isset( $_REQUEST ) && isset( $_REQUEST['course'] ) && isset( $_REQUEST['step'] ) && 'sfwd-topic' === $_REQUEST['step'] ) {
								$user      = ( isset( $_REQUEST ) && isset( $_REQUEST['user'] ) && '' !== $_REQUEST['user'] ) ? $_REQUEST['user'] : bp_loggedin_user_id();
								$label = \LearnDash_Custom_Label::get_label( 'topic' );
								$data  = bp_ld_get_course_all_steps( $course->ID, bp_loggedin_user_id(), 'topic' );
								$steps = $data['steps'];
								$key   = str_replace( ' ', '_', strtolower( $label ) );
								set_transient( "{$hash}_info",
									array(
										'filename' => 'learndash-report-export-group-' . $_REQUEST['group'] . '.csv',
										'columns'  => array(
											'user_id'       => array(
												'label'     => 'User ID',
												'sortable'  => '',
												'order_key' => '',
											),
											'student_name'  => array(
												'label'     => 'Student',
												'sortable'  => '',
												'order_key' => '',
											),
											'course_name'   => array(
												'label'     => 'Course',
												'sortable'  => '',
												'order_key' => '',
											),
											$key            => array(
												'label'     => $label,
												'sortable'  => '',
												'order_key' => '',
											),
											'start_date'    => array(
												'label'     => 'Start Date',
												'sortable'  => '',
												'order_key' => '',
											),
											'complete_date' => array(
												'label'     => 'Completion Date',
												'sortable'  => '',
												'order_key' => '',
											),
											'time_spent'    => array(
												'label'     => 'Time Spent',
												'sortable'  => '',
												'order_key' => '',
											),
											'points_earned' => array(
												'label'     => 'Points Earned',
												'sortable'  => '',
												'order_key' => '',
											),
										),
									),
									HOUR_IN_SECONDS );
								foreach ( $steps as $step ) {
									if ( is_null( $step['activity'] ) ) {
										$exports[] = array(
											'user_id'       => $user,
											'student_name'  => bp_core_get_user_displayname( $user ),
											'course_name'   => $course->post_title,
											$key            => wp_strip_all_tags( $step['title'] ),
											'start_date'    => '-',
											'complete_date' => '-',
											'time_spent'    => '-',
											'points_earned' => '-',
										);
									} else {
										$time_spent = bp_ld_time_spent( $step['activity'] );
										$start_date = date_i18n( bp_get_option( 'date_format' ),
											intval( $step['activity']->activity_started ) );
										$points     = bpLdCoursePointsEarned( $step['activity'] );
										if ( is_null( $step['activity'] ) ) {
											$end_date = '';
										} else {
											$end_date = ( $step['activity']->activity_completed ) ? date_i18n( bp_get_option( 'date_format' ),
												intval( $step['activity']->activity_completed ) ) : '-';
										}
										$exports[] = array(
											'user_id'       => $user,
											'student_name'  => bp_core_get_user_displayname( $user ),
											'course_name'   => $course->post_title,
											$key            => wp_strip_all_tags( $step['title'] ),
											'start_date'    => $start_date,
											'complete_date' => $end_date,
											'time_spent'    => $time_spent,
											'points_earned' => $points,
										);
									}
								}
								set_transient( $hash, $exports, HOUR_IN_SECONDS );
							} elseif ( isset( $_REQUEST ) && isset( $_REQUEST['course'] ) && isset( $_REQUEST['step'] ) && 'sfwd-quiz' === $_REQUEST['step'] ) {
								$user      = ( isset( $_REQUEST ) && isset( $_REQUEST['user'] ) && '' !== $_REQUEST['user'] ) ? $_REQUEST['user'] : bp_loggedin_user_id();
								$label = \LearnDash_Custom_Label::get_label( 'quiz' );
								$data  = bp_ld_get_course_all_steps( $course->ID, bp_loggedin_user_id(), 'quiz' );
								$steps = $data['steps'];
								$key   = str_replace( ' ', '_', strtolower( $label ) );
								set_transient( "{$hash}_info",
									array(
										'filename' => 'learndash-report-export-group-' . $_REQUEST['group'] . '.csv',
										'columns'  => array(
											'user_id'       => array(
												'label'     => 'User ID',
												'sortable'  => '',
												'order_key' => '',
											),
											'student_name'  => array(
												'label'     => 'Student',
												'sortable'  => '',
												'order_key' => '',
											),
											'course_name'   => array(
												'label'     => 'Course',
												'sortable'  => '',
												'order_key' => '',
											),
											$key            => array(
												'label'     => $label,
												'sortable'  => '',
												'order_key' => '',
											),
											'start_date'    => array(
												'label'     => 'Start Date',
												'sortable'  => '',
												'order_key' => '',
											),
											'complete_date' => array(
												'label'     => 'Completion Date',
												'sortable'  => '',
												'order_key' => '',
											),
											'score'         => array(
												'label'     => 'Score',
												'sortable'  => '',
												'order_key' => '',
											),
											'time_spent'    => array(
												'label'     => 'Time Spent',
												'sortable'  => '',
												'order_key' => '',
											),
											'points_earned' => array(
												'label'     => 'Points Earned',
												'sortable'  => '',
												'order_key' => '',
											),
											'attempts'      => array(
												'label'     => 'Attempts',
												'sortable'  => '',
												'order_key' => '',
											),
										),
									),
									HOUR_IN_SECONDS );
								foreach ( $steps as $step ) {
									if ( is_null( $step['activity'] ) ) {
										$exports[] = array(
											'user_id'       => $user,
											'student_name'  => bp_core_get_user_displayname( $user ),
											'course_name'   => $course->post_title,
											$key            => wp_strip_all_tags( $step['title'] ),
											'start_date'    => '-',
											'complete_date' => '-',
											'score'         => $step['score'],
											'time_spent'    => '-',
											'points_earned' => '-',
											'attempts'      => $step['attempt'],
										);
									} else {
										$time_spent = bp_ld_time_spent( $step['activity'] );
										$start_date = date_i18n( bp_get_option( 'date_format' ),
											intval( $step['activity']->activity_started ) );
										$points     = bpLdCoursePointsEarned( $step['activity'] );
										if ( is_null( $step['activity'] ) ) {
											$end_date = '';
										} else {
											$end_date = ( $step['activity']->activity_completed ) ? date_i18n( bp_get_option( 'date_format' ),
												intval( $step['activity']->activity_completed ) ) : '-';
										}
										$exports[] = array(
											'user_id'       => $user,
											'student_name'  => bp_core_get_user_displayname( $user ),
											'course_name'   => $course->post_title,
											$key            => wp_strip_all_tags( $step['title'] ),
											'start_date'    => $start_date,
											'complete_date' => $end_date,
											'score'         => $step['score'],
											'time_spent'    => $time_spent,
											'points_earned' => $points,
											'attempts'      => $step['attempt'],
										);
									}
								}
								set_transient( $hash, $exports, HOUR_IN_SECONDS );
							} elseif ( isset( $_REQUEST ) && isset( $_REQUEST['course'] ) && isset( $_REQUEST['step'] ) && 'sfwd-assignment' === $_REQUEST['step'] ) {
								$user      = ( isset( $_REQUEST ) && isset( $_REQUEST['user'] ) && '' !== $_REQUEST['user'] ) ? $_REQUEST['user'] : bp_loggedin_user_id();
								$label = __( 'ASSIGNMENT', 'buddyboss' );
								$data  = bp_ld_get_course_all_steps( $course->ID, bp_loggedin_user_id(), 'assignment' );
								$steps = $data['steps'];
								$key   = str_replace( ' ', '_', strtolower( $label ) );
								set_transient( "{$hash}_info",
									array(
										'filename' => 'learndash-report-export-group-' . $_REQUEST['group'] . '.csv',
										'columns'  => array(
											'user_id'      => array(
												'label'     => 'User ID',
												'sortable'  => '',
												'order_key' => '',
											),
											'student_name' => array(
												'label'     => 'Student',
												'sortable'  => '',
												'order_key' => '',
											),
											'course_name'  => array(
												'label'     => 'Course',
												'sortable'  => '',
												'order_key' => '',
											),
											$key           => array(
												'label'     => $label,
												'sortable'  => '',
												'order_key' => '',
											),
											'graded'       => array(
												'label'     => 'Graded Date',
												'sortable'  => '',
												'order_key' => '',
											),
											'score'        => array(
												'label'     => 'Score',
												'sortable'  => '',
												'order_key' => '',
											),
										),
									),
									HOUR_IN_SECONDS );
								foreach ( $steps as $step ) {
									$exports[] = array(
										'user_id'      => $user,
										'student_name' => bp_core_get_user_displayname( $user ),
										'course_name'  => $course->post_title,
										$key           => wp_strip_all_tags( $step['title'] ),
										'graded'       => $step['graded'],
										'score'        => $step['score'],
									);
								}
								set_transient( $hash, $exports, HOUR_IN_SECONDS );
							} else {
								$user      = ( isset( $_REQUEST ) && isset( $_REQUEST['user'] ) && '' !== $_REQUEST['user'] ) ? $_REQUEST['user'] : bp_loggedin_user_id();
								$label = __( 'Step', 'buddyboss' );
								$data  = bp_ld_get_course_all_steps( $course->ID, bp_loggedin_user_id(), 'all' );
								$steps = $data['steps'];
								$key   = str_replace( ' ', '_', strtolower( $label ) );
								set_transient( "{$hash}_info",
									array(
										'filename' => 'learndash-report-export-group-' . $_REQUEST['group'] . '.csv',
										'columns'  => array(
											'user_id'       => array(
												'label'     => 'User ID',
												'sortable'  => '',
												'order_key' => '',
											),
											'student_name'  => array(
												'label'     => 'Student',
												'sortable'  => '',
												'order_key' => '',
											),
											'course_name'   => array(
												'label'     => 'Course',
												'sortable'  => '',
												'order_key' => '',
											),
											$key            => array(
												'label'     => $label,
												'sortable'  => '',
												'order_key' => '',
											),
											'start_date'    => array(
												'label'     => 'Start Date',
												'sortable'  => '',
												'order_key' => '',
											),
											'complete_date' => array(
												'label'     => 'Completion Date',
												'sortable'  => '',
												'order_key' => '',
											),
											'score'         => array(
												'label'     => 'Score',
												'sortable'  => '',
												'order_key' => '',
											),
											'time_spent'    => array(
												'label'     => 'Time Spent',
												'sortable'  => '',
												'order_key' => '',
											),
											'points_earned' => array(
												'label'     => 'Points Earned',
												'sortable'  => '',
												'order_key' => '',
											),
											'attempts'      => array(
												'label'     => 'Attempts',
												'sortable'  => '',
												'order_key' => '',
											),
										),
									),
									HOUR_IN_SECONDS );
								foreach ( $steps as $step ) {
									if ( is_null( $step['activity'] ) ) {
										$exports[] = array(
											'user_id'       => $user,
											'student_name'  => bp_core_get_user_displayname( $user ),
											'course_name'   => $course->post_title,
											$key            => wp_strip_all_tags( $step['title'] ),
											'start_date'    => '-',
											'complete_date' => '-',
											'time_spent'    => '-',
											'points_earned' => '-',
										);
									} else {
										$time_spent = bp_ld_time_spent( $step['activity'] );
										$start_date = date_i18n( bp_get_option( 'date_format' ),
											intval( $step['activity']->activity_started ) );
										$points     = bpLdCoursePointsEarned( $step['activity'] );
										if ( is_null( $step['activity'] ) ) {
											$end_date = '';
										} else {
											$end_date = ( $step['activity']->activity_completed ) ? date_i18n( bp_get_option( 'date_format' ),
												intval( $step['activity']->activity_completed ) ) : '-';
										}
										$exports[] = array(
											'user_id'       => $user,
											'student_name'  => bp_core_get_user_displayname( $user ),
											'course_name'   => $course->post_title,
											$key            => wp_strip_all_tags( $step['title'] ),
											'start_date'    => $start_date,
											'complete_date' => $end_date,
											'time_spent'    => $time_spent,
											'points_earned' => $points,
										);
									}
								}
							}
						}
					}
				}
			}
		}

		wp_send_json_success( array(
				'url'  => add_query_arg( array(
					'hash'   => $hash,
					'action' => 'download_bp_ld_reports',
				),
					admin_url( 'admin-ajax.php' ) ),
				'hash' => $hash,
			) );

	}

	/**
	 * Get reports
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function ajaxGetReports() {
		$this->enableDebugOnDev();
		$this->validateRequest();

		$generator = $this->getGenerator();

		/**
		 * Hook before the data is fetched, in cause of overwriting the post value
		 *
		 * @since BuddyBoss 1.0.0
		 */
		do_action( 'bp_ld_sync/ajax/pre_fetch_reports', $generator );

		$generator->fetch();

		/**
		 * Hook after the data is fetched, in cause of overwriting results value
		 *
		 * @since BuddyBoss 1.0.0
		 */
		do_action( 'bp_ld_sync/ajax/post_fetch_reports', $generator );

		echo json_encode(
			array(
				'draw'            => (int) bp_ld_sync()->getRequest( 'draw' ),
				'recordsTotal'    => $generator->getPager()['total_items'],
				'recordsFiltered' => $generator->getPager()['total_items'],
				'data'            => $generator->getData(),
			)
		);

		//header('Content-Type: application/json; charset=' . get_option('blog_charset'));
		wp_die();
		// wp_send_json_success([
		// 'draw' => (int) bp_ld_sync()->getRequest('draw'),
		// 'results' => $generator->getData(),
		// 'pager'   => $generator->getPager(),
		// ]);
	}

	/**
	 * Unset the completed status when exporting
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function unsetCompletionOnExport( $args ) {
		if ( bp_ld_sync()->getRequest( 'export' ) ) {
			$args['completed'] = null;
		}

		return $args;
	}

	/**
	 * Remove the id fields when fetching for display only
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function removeIdsOnNonExport( $column, $args ) {
		if ( ! isset( $args['report'] ) ) {
			unset( $column['user_id'] );
			unset( $column['course_id'] );
		}

		return $column;
	}

	/**
	 * Get export data from report generator
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function ajaxGetExports( $generator ) {
		if ( ! bp_ld_sync()->getRequest( 'export' ) ) {
			return;
		}

		return $generator->export();
	}

	/**
	 * Output the export content to header buffer
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function ajaxDownloadReport() {
		$hash    = bp_ld_sync()->getRequest( 'hash' );
		$exports = get_transient( $hash );
		$info    = get_transient( "{$hash}_info" );

		if ( ! $hash || ! $exports ) {
			wp_die( __( 'Session has expired, please refresh and try again.', 'buddyboss' ) );
		}

		$file = fopen( 'php://output', 'w' );
		fputcsv( $file, wp_list_pluck( $info['columns'], 'label' ) );

		foreach ( $exports as $export ) {
			fputcsv( $file, $export );
		}

		header( 'Content-Encoding: ' . DB_CHARSET );
		header( 'Content-type: text/csv; charset=' . DB_CHARSET );
		header( 'Content-Disposition: attachment; filename=' . $info['filename'] );
		header( 'Pragma: no-cache' );
		header( 'Expires: 0' );
		fclose( $df );
		die();
	}

	/**
	 * Enable error reporting on local development (internal use only)
	 *
	 * @since BuddyBoss 1.0.0
	 */
	protected function enableDebugOnDev() {
		if ( strpos( get_bloginfo( 'url' ), '.test' ) === false ) {
			return;
		}

		error_reporting( E_ALL );
		ini_set( 'display_errors', 1 );
	}

	/**
	 * Validate the ajax request
	 *
	 * @since BuddyBoss 1.0.0
	 */
	protected function validateRequest() {
		if ( ! wp_verify_nonce( bp_ld_sync()->getRequest( 'nonce' ), 'bp_ld_report' ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Session has expired, please refresh and try again.', 'buddyboss' ),
				)
			);
		}

		if ( $this->setRequestGroups() && ( ! $this->bp_group || ! $this->ld_group ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Unable to find selected group.', 'buddyboss' ),
				)
			);
		}
	}

	/**
	 * Setup the current bp and ld groups on ajax request
	 *
	 * @since BuddyBoss 1.0.0
	 */
	protected function setRequestGroups() {
		if ( ! $groupId = bp_ld_sync()->getRequest( 'group' ) ) {
			return;
		}

		$bpGroup = groups_get_group( $groupId );

		if ( ! $bpGroup->id ) {
			return;
		}

		$this->bpGroup = $bpGroup;
		$this->ldGroup = get_post( bp_ld_sync( 'buddypress' )->helpers->getLearndashGroupId( $groupId ) );
	}

	/**
	 * Get the generator class based on the request
	 *
	 * @since BuddyBoss 1.0.0
	 */
	protected function getGenerator() {
		 $generators = bp_ld_sync( 'buddypress' )->reports->getGenerators();
		$type        = bp_ld_sync()->getRequest( 'step' );

		return ( new $generators[ $type ]['class']() );
	}
}
