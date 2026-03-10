<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Gamification triggers — GamiPress + LearnDash + Memberships + Courses (25+ triggers).
 */
class BB_CRM_Gamification_Triggers {

	public static function init() {
		// ── GamiPress ───────────────────────────────────────────────────────
		BB_CRM_Auto_Triggers::register( 'points_earned', array(
			'label'    => __( 'User Earns Points', 'buddyboss-crm-automations' ),
			'category' => 'gamification',
			'fields'   => array( 'points_type' => __( 'Points Type', 'buddyboss-crm-automations' ) ),
		) );
		BB_CRM_Auto_Triggers::register( 'points_threshold', array(
			'label'    => __( 'User Reaches Points Threshold', 'buddyboss-crm-automations' ),
			'category' => 'gamification',
			'fields'   => array( 'threshold' => __( 'Points amount', 'buddyboss-crm-automations' ) ),
		) );
		BB_CRM_Auto_Triggers::register( 'badge_awarded', array(
			'label'    => __( 'User Earns a Badge', 'buddyboss-crm-automations' ),
			'category' => 'gamification',
		) );
		BB_CRM_Auto_Triggers::register( 'badge_awarded_specific', array(
			'label'    => __( 'User Earns a Specific Badge', 'buddyboss-crm-automations' ),
			'category' => 'gamification',
			'fields'   => array( 'badge_id' => __( 'Badge', 'buddyboss-crm-automations' ) ),
		) );
		BB_CRM_Auto_Triggers::register( 'rank_achieved', array(
			'label'    => __( 'User Achieves a Rank', 'buddyboss-crm-automations' ),
			'category' => 'gamification',
		) );
		BB_CRM_Auto_Triggers::register( 'rank_achieved_specific', array(
			'label'    => __( 'User Achieves a Specific Rank', 'buddyboss-crm-automations' ),
			'category' => 'gamification',
			'fields'   => array( 'rank_id' => __( 'Rank', 'buddyboss-crm-automations' ) ),
		) );
		BB_CRM_Auto_Triggers::register( 'achievement_unlocked', array(
			'label'    => __( 'User Unlocks an Achievement', 'buddyboss-crm-automations' ),
			'category' => 'gamification',
		) );

		// ── LearnDash / Courses ──────────────────────────────────────────────
		BB_CRM_Auto_Triggers::register( 'course_enrolled', array(
			'label'    => __( 'User Enrolls in a Course', 'buddyboss-crm-automations' ),
			'category' => 'gamification',
		) );
		BB_CRM_Auto_Triggers::register( 'course_enrolled_specific', array(
			'label'    => __( 'User Enrolls in a Specific Course', 'buddyboss-crm-automations' ),
			'category' => 'gamification',
			'fields'   => array( 'course_id' => __( 'Course', 'buddyboss-crm-automations' ) ),
		) );
		BB_CRM_Auto_Triggers::register( 'course_completed', array(
			'label'    => __( 'User Completes a Course', 'buddyboss-crm-automations' ),
			'category' => 'gamification',
		) );
		BB_CRM_Auto_Triggers::register( 'course_completed_specific', array(
			'label'    => __( 'User Completes a Specific Course', 'buddyboss-crm-automations' ),
			'category' => 'gamification',
			'fields'   => array( 'course_id' => __( 'Course', 'buddyboss-crm-automations' ) ),
		) );
		BB_CRM_Auto_Triggers::register( 'lesson_completed', array(
			'label'    => __( 'User Completes a Lesson', 'buddyboss-crm-automations' ),
			'category' => 'gamification',
		) );
		BB_CRM_Auto_Triggers::register( 'quiz_passed', array(
			'label'    => __( 'User Passes a Quiz', 'buddyboss-crm-automations' ),
			'category' => 'gamification',
		) );
		BB_CRM_Auto_Triggers::register( 'quiz_failed', array(
			'label'    => __( 'User Fails a Quiz', 'buddyboss-crm-automations' ),
			'category' => 'gamification',
		) );
		BB_CRM_Auto_Triggers::register( 'assignment_submitted', array(
			'label'    => __( 'User Submits an Assignment', 'buddyboss-crm-automations' ),
			'category' => 'gamification',
		) );
		BB_CRM_Auto_Triggers::register( 'certificate_earned', array(
			'label'    => __( 'User Earns a Certificate', 'buddyboss-crm-automations' ),
			'category' => 'gamification',
		) );
		BB_CRM_Auto_Triggers::register( 'course_progress_percent', array(
			'label'    => __( 'User Reaches Course Progress %', 'buddyboss-crm-automations' ),
			'category' => 'gamification',
			'fields'   => array( 'percent' => __( 'Progress %', 'buddyboss-crm-automations' ) ),
		) );

		// ── Memberships ──────────────────────────────────────────────────────
		BB_CRM_Auto_Triggers::register( 'membership_started', array(
			'label'    => __( 'User Starts a Membership', 'buddyboss-crm-automations' ),
			'category' => 'gamification',
		) );
		BB_CRM_Auto_Triggers::register( 'membership_started_specific', array(
			'label'    => __( 'User Starts a Specific Membership', 'buddyboss-crm-automations' ),
			'category' => 'gamification',
			'fields'   => array( 'membership_id' => __( 'Membership', 'buddyboss-crm-automations' ) ),
		) );
		BB_CRM_Auto_Triggers::register( 'membership_cancelled', array(
			'label'    => __( 'User Cancels Membership', 'buddyboss-crm-automations' ),
			'category' => 'gamification',
		) );
		BB_CRM_Auto_Triggers::register( 'membership_expired', array(
			'label'    => __( 'Membership Expires', 'buddyboss-crm-automations' ),
			'category' => 'gamification',
		) );
		BB_CRM_Auto_Triggers::register( 'membership_renewed', array(
			'label'    => __( 'User Renews Membership', 'buddyboss-crm-automations' ),
			'category' => 'gamification',
		) );
		BB_CRM_Auto_Triggers::register( 'membership_upgraded', array(
			'label'    => __( 'User Upgrades Membership Level', 'buddyboss-crm-automations' ),
			'category' => 'gamification',
		) );
		BB_CRM_Auto_Triggers::register( 'membership_downgraded', array(
			'label'    => __( 'User Downgrades Membership Level', 'buddyboss-crm-automations' ),
			'category' => 'gamification',
		) );

		// Hook into GamiPress if active.
		if ( function_exists( 'gamipress_get_user_points' ) ) {
			add_action( 'gamipress_update_user_points',   array( __CLASS__, 'on_points_earned' ), 10, 6 );
			add_action( 'gamipress_award_achievement_to_user', array( __CLASS__, 'on_achievement_awarded' ), 10, 5 );
			add_action( 'gamipress_rank_updated',         array( __CLASS__, 'on_rank_achieved' ), 10, 3 );
		}

		// Hook into LearnDash if active.
		if ( function_exists( 'learndash_get_course_list' ) || defined( 'LEARNDASH_VERSION' ) ) {
			add_action( 'learndash_update_course_access',     array( __CLASS__, 'on_course_enrolled' ), 10, 4 );
			add_action( 'learndash_course_completed',         array( __CLASS__, 'on_course_completed' ), 10, 1 );
			add_action( 'learndash_lesson_completed',         array( __CLASS__, 'on_lesson_completed' ), 10, 1 );
			add_action( 'learndash_quiz_completed',           array( __CLASS__, 'on_quiz_completed' ), 10, 2 );
			add_action( 'learndash_assignment_uploaded',      array( __CLASS__, 'on_assignment_submitted' ), 10, 1 );
		}

		// Hook into MemberPress if active.
		if ( class_exists( 'MeprBaseModel' ) || defined( 'MEMBERPRESS_VERSION' ) ) {
			add_action( 'mepr-signup',          array( __CLASS__, 'on_membership_started' ), 10, 1 );
			add_action( 'mepr-event-member-added-to-product', array( __CLASS__, 'on_mepr_product_added' ), 10, 1 );
			add_action( 'mepr-subscription-expired', array( __CLASS__, 'on_membership_expired' ), 10, 1 );
			add_action( 'mepr-cancel-subscription', array( __CLASS__, 'on_membership_cancelled' ), 10, 1 );
		}

		// PMPro (Paid Memberships Pro).
		if ( function_exists( 'pmpro_getMembershipLevelForUser' ) ) {
			add_action( 'pmpro_after_change_membership_level', array( __CLASS__, 'on_pmpro_level_changed' ), 10, 2 );
		}
	}

	// ── GamiPress handlers ──────────────────────────────────────────────────

	public static function on_points_earned( $user_id, $new_points, $admin_id, $achievement_id, $action_id, $points_type ) {
		$data = array( 'points' => $new_points, 'points_type' => $points_type );
		do_action( 'bb_crm_auto_trigger', 'points_earned', $user_id, $data );
		do_action( 'bb_crm_auto_trigger', 'points_threshold', $user_id, $data );
	}

	public static function on_achievement_awarded( $user_id, $achievement_id, $trigger, $site_id, $args ) {
		$achievement = get_post( $achievement_id );
		$type        = $achievement ? $achievement->post_type : 'achievement';
		$data        = array( 'achievement_id' => $achievement_id, 'type' => $type );

		if ( $type === 'badges' || $type === 'badge' ) {
			do_action( 'bb_crm_auto_trigger', 'badge_awarded', $user_id, $data );
			do_action( 'bb_crm_auto_trigger', 'badge_awarded_specific', $user_id, $data );
		} else {
			do_action( 'bb_crm_auto_trigger', 'achievement_unlocked', $user_id, $data );
		}
	}

	public static function on_rank_achieved( $user_id, $rank_id, $old_rank_id ) {
		$data = array( 'rank_id' => $rank_id, 'old_rank_id' => $old_rank_id );
		do_action( 'bb_crm_auto_trigger', 'rank_achieved', $user_id, $data );
		do_action( 'bb_crm_auto_trigger', 'rank_achieved_specific', $user_id, $data );
	}

	// ── LearnDash handlers ──────────────────────────────────────────────────

	public static function on_course_enrolled( $user_id, $course_id, $access, $old_status ) {
		if ( $access ) {
			$data = array( 'course_id' => $course_id );
			do_action( 'bb_crm_auto_trigger', 'course_enrolled', $user_id, $data );
			do_action( 'bb_crm_auto_trigger', 'course_enrolled_specific', $user_id, $data );
		}
	}

	public static function on_course_completed( $data ) {
		$user_id   = $data['user']->ID ?? 0;
		$course_id = $data['course']->ID ?? 0;
		if ( ! $user_id ) return;
		$trigger_data = array( 'course_id' => $course_id );
		do_action( 'bb_crm_auto_trigger', 'course_completed', $user_id, $trigger_data );
		do_action( 'bb_crm_auto_trigger', 'course_completed_specific', $user_id, $trigger_data );
	}

	public static function on_lesson_completed( $data ) {
		$user_id   = $data['user']->ID ?? 0;
		$lesson_id = $data['lesson']->ID ?? 0;
		if ( ! $user_id ) return;
		do_action( 'bb_crm_auto_trigger', 'lesson_completed', $user_id, array( 'lesson_id' => $lesson_id ) );
	}

	public static function on_quiz_completed( $quiz_data, $user ) {
		$user_id = is_object( $user ) ? $user->ID : absint( $user );
		$passed  = ! empty( $quiz_data['pass'] );
		$trigger = $passed ? 'quiz_passed' : 'quiz_failed';
		do_action( 'bb_crm_auto_trigger', $trigger, $user_id, array( 'quiz_id' => $quiz_data['quiz'] ?? 0 ) );
	}

	public static function on_assignment_submitted( $assignment_post_id ) {
		$user_id = get_post_field( 'post_author', $assignment_post_id );
		if ( $user_id ) {
			do_action( 'bb_crm_auto_trigger', 'assignment_submitted', $user_id, array( 'assignment_id' => $assignment_post_id ) );
		}
	}

	// ── MemberPress handlers ────────────────────────────────────────────────

	public static function on_membership_started( $txn ) {
		$user_id = is_object( $txn ) ? $txn->user_id : 0;
		if ( ! $user_id ) return;
		$product_id = is_object( $txn ) ? $txn->product_id : 0;
		$data = array( 'membership_id' => $product_id );
		do_action( 'bb_crm_auto_trigger', 'membership_started', $user_id, $data );
		do_action( 'bb_crm_auto_trigger', 'membership_started_specific', $user_id, $data );
	}

	public static function on_mepr_product_added( $event ) {
		$user_id    = $event->get_data()->user_id ?? 0;
		$product_id = $event->get_data()->product_id ?? 0;
		if ( ! $user_id ) return;
		$data = array( 'membership_id' => $product_id );
		do_action( 'bb_crm_auto_trigger', 'membership_started', $user_id, $data );
		do_action( 'bb_crm_auto_trigger', 'membership_started_specific', $user_id, $data );
	}

	public static function on_membership_expired( $sub ) {
		$user_id = is_object( $sub ) ? $sub->user_id : 0;
		if ( $user_id ) {
			do_action( 'bb_crm_auto_trigger', 'membership_expired', $user_id, array() );
		}
	}

	public static function on_membership_cancelled( $sub ) {
		$user_id = is_object( $sub ) ? $sub->user_id : 0;
		if ( $user_id ) {
			do_action( 'bb_crm_auto_trigger', 'membership_cancelled', $user_id, array() );
		}
	}

	// ── PMPro handlers ──────────────────────────────────────────────────────

	public static function on_pmpro_level_changed( $level_id, $user_id ) {
		do_action( 'bb_crm_auto_trigger', 'membership_started', $user_id, array( 'membership_id' => $level_id ) );
		do_action( 'bb_crm_auto_trigger', 'membership_started_specific', $user_id, array( 'membership_id' => $level_id ) );
	}
}

BB_CRM_Gamification_Triggers::init();
