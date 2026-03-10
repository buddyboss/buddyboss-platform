<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Activity stream triggers (15 triggers).
 */
class BB_CRM_Activity_Triggers {

	public static function init() {
		BB_CRM_Auto_Triggers::register( 'activity_posted', array(
			'label'    => __( 'User Posts an Activity Update', 'buddyboss-crm-automations' ),
			'category' => 'activity',
		) );
		BB_CRM_Auto_Triggers::register( 'activity_comment_posted', array(
			'label'    => __( 'User Comments on Activity', 'buddyboss-crm-automations' ),
			'category' => 'activity',
		) );
		BB_CRM_Auto_Triggers::register( 'activity_liked', array(
			'label'    => __( 'User Likes an Activity', 'buddyboss-crm-automations' ),
			'category' => 'activity',
		) );
		BB_CRM_Auto_Triggers::register( 'activity_mentioned', array(
			'label'    => __( 'User is Mentioned in Activity', 'buddyboss-crm-automations' ),
			'category' => 'activity',
		) );
		BB_CRM_Auto_Triggers::register( 'activity_reaction_added', array(
			'label'    => __( 'User Reacts to Activity', 'buddyboss-crm-automations' ),
			'category' => 'activity',
		) );
		BB_CRM_Auto_Triggers::register( 'activity_first_post', array(
			'label'    => __( 'User Creates Their First Post', 'buddyboss-crm-automations' ),
			'category' => 'activity',
		) );
		BB_CRM_Auto_Triggers::register( 'activity_post_count', array(
			'label'    => __( 'User Reaches Post Milestone', 'buddyboss-crm-automations' ),
			'category' => 'activity',
			'fields'   => array( 'count' => __( 'Number of posts', 'buddyboss-crm-automations' ) ),
		) );
		BB_CRM_Auto_Triggers::register( 'activity_media_uploaded', array(
			'label'    => __( 'User Uploads Media', 'buddyboss-crm-automations' ),
			'category' => 'activity',
		) );
		BB_CRM_Auto_Triggers::register( 'activity_video_uploaded', array(
			'label'    => __( 'User Uploads Video', 'buddyboss-crm-automations' ),
			'category' => 'activity',
		) );
		BB_CRM_Auto_Triggers::register( 'activity_document_uploaded', array(
			'label'    => __( 'User Uploads Document', 'buddyboss-crm-automations' ),
			'category' => 'activity',
		) );
		BB_CRM_Auto_Triggers::register( 'activity_deleted', array(
			'label'    => __( 'User Deletes an Activity Post', 'buddyboss-crm-automations' ),
			'category' => 'activity',
		) );
		BB_CRM_Auto_Triggers::register( 'activity_pinned', array(
			'label'    => __( 'Activity is Pinned', 'buddyboss-crm-automations' ),
			'category' => 'activity',
		) );
		BB_CRM_Auto_Triggers::register( 'activity_shared', array(
			'label'    => __( 'User Shares an Activity', 'buddyboss-crm-automations' ),
			'category' => 'activity',
		) );
		BB_CRM_Auto_Triggers::register( 'activity_reported', array(
			'label'    => __( 'Activity is Reported', 'buddyboss-crm-automations' ),
			'category' => 'activity',
		) );
		BB_CRM_Auto_Triggers::register( 'activity_poll_voted', array(
			'label'    => __( 'User Votes in a Poll', 'buddyboss-crm-automations' ),
			'category' => 'activity',
		) );

		if ( function_exists( 'buddypress' ) ) {
			add_action( 'bp_activity_posted_update',         array( __CLASS__, 'on_activity_posted' ), 10, 3 );
			add_action( 'bp_activity_comment_posted',        array( __CLASS__, 'on_comment_posted' ), 10, 3 );
			add_action( 'bp_activity_add_user_favorite',     array( __CLASS__, 'on_activity_liked' ), 10, 2 );
			add_action( 'bp_activity_at_name_send_emails',   array( __CLASS__, 'on_mentioned' ), 10, 2 );
			add_action( 'bp_activity_after_save',            array( __CLASS__, 'on_activity_saved' ), 10, 1 );
		}
	}

	public static function on_activity_posted( $content, $user_id, $activity_id ) {
		$data = array( 'activity_id' => $activity_id, 'content' => wp_trim_words( $content, 20 ) );

		// First post milestone check.
		if ( function_exists( 'bp_activity_get_user_activity_count' ) ) {
			$count = bp_activity_get_user_activity_count( $user_id );
			if ( (int) $count === 1 ) {
				do_action( 'bb_crm_auto_trigger', 'activity_first_post', $user_id, $data );
			}
			do_action( 'bb_crm_auto_trigger', 'activity_post_count', $user_id, array_merge( $data, array( 'count' => $count ) ) );
		}

		do_action( 'bb_crm_auto_trigger', 'activity_posted', $user_id, $data );
	}

	public static function on_comment_posted( $comment_id, $params, $activity_id ) {
		$user_id = $params['user_id'] ?? get_current_user_id();
		do_action( 'bb_crm_auto_trigger', 'activity_comment_posted', $user_id, array( 'activity_id' => $activity_id, 'comment_id' => $comment_id ) );
	}

	public static function on_activity_liked( $activity_id, $user_id ) {
		do_action( 'bb_crm_auto_trigger', 'activity_liked', $user_id, array( 'activity_id' => $activity_id ) );
	}

	public static function on_mentioned( $activity_id, $mentioned_users ) {
		if ( is_array( $mentioned_users ) ) {
			foreach ( $mentioned_users as $user_id ) {
				do_action( 'bb_crm_auto_trigger', 'activity_mentioned', $user_id, array( 'activity_id' => $activity_id ) );
			}
		}
	}

	public static function on_activity_saved( $activity ) {
		if ( ! is_object( $activity ) ) return;
		if ( $activity->type === 'activity_update' && $activity->component === 'activity' ) {
			// Handled by on_activity_posted.
		}
	}
}

BB_CRM_Activity_Triggers::init();
