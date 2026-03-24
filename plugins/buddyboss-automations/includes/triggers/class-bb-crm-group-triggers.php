<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Group triggers (20 triggers).
 */
class BB_CRM_Group_Triggers {

	public static function init() {
		BB_CRM_Auto_Triggers::register( 'group_joined', array(
			'label'    => __( 'User Joins a Group', 'buddyboss-automations' ),
			'category' => 'group',
		) );
		BB_CRM_Auto_Triggers::register( 'group_joined_specific', array(
			'label'    => __( 'User Joins a Specific Group', 'buddyboss-automations' ),
			'category' => 'group',
			'fields'   => array( 'group_id' => __( 'Group', 'buddyboss-automations' ) ),
		) );
		BB_CRM_Auto_Triggers::register( 'group_left', array(
			'label'    => __( 'User Leaves a Group', 'buddyboss-automations' ),
			'category' => 'group',
		) );
		BB_CRM_Auto_Triggers::register( 'group_left_specific', array(
			'label'    => __( 'User Leaves a Specific Group', 'buddyboss-automations' ),
			'category' => 'group',
			'fields'   => array( 'group_id' => __( 'Group', 'buddyboss-automations' ) ),
		) );
		BB_CRM_Auto_Triggers::register( 'group_invite_sent', array(
			'label'    => __( 'User Sends Group Invite', 'buddyboss-automations' ),
			'category' => 'group',
		) );
		BB_CRM_Auto_Triggers::register( 'group_invite_accepted', array(
			'label'    => __( 'User Accepts Group Invite', 'buddyboss-automations' ),
			'category' => 'group',
		) );
		BB_CRM_Auto_Triggers::register( 'group_invite_rejected', array(
			'label'    => __( 'User Rejects Group Invite', 'buddyboss-automations' ),
			'category' => 'group',
		) );
		BB_CRM_Auto_Triggers::register( 'group_request_sent', array(
			'label'    => __( 'User Requests to Join Group', 'buddyboss-automations' ),
			'category' => 'group',
		) );
		BB_CRM_Auto_Triggers::register( 'group_request_approved', array(
			'label'    => __( 'Join Request Approved', 'buddyboss-automations' ),
			'category' => 'group',
		) );
		BB_CRM_Auto_Triggers::register( 'group_request_rejected', array(
			'label'    => __( 'Join Request Rejected', 'buddyboss-automations' ),
			'category' => 'group',
		) );
		BB_CRM_Auto_Triggers::register( 'group_role_promoted', array(
			'label'    => __( 'User Promoted to Group Mod/Admin', 'buddyboss-automations' ),
			'category' => 'group',
		) );
		BB_CRM_Auto_Triggers::register( 'group_role_demoted', array(
			'label'    => __( 'User Demoted in Group', 'buddyboss-automations' ),
			'category' => 'group',
		) );
		BB_CRM_Auto_Triggers::register( 'group_banned', array(
			'label'    => __( 'User Banned from Group', 'buddyboss-automations' ),
			'category' => 'group',
		) );
		BB_CRM_Auto_Triggers::register( 'group_unbanned', array(
			'label'    => __( 'User Unbanned from Group', 'buddyboss-automations' ),
			'category' => 'group',
		) );
		BB_CRM_Auto_Triggers::register( 'group_created', array(
			'label'    => __( 'User Creates a Group', 'buddyboss-automations' ),
			'category' => 'group',
		) );
		BB_CRM_Auto_Triggers::register( 'group_post_created', array(
			'label'    => __( 'User Posts in a Group', 'buddyboss-automations' ),
			'category' => 'group',
		) );
		BB_CRM_Auto_Triggers::register( 'group_post_commented', array(
			'label'    => __( 'User Comments on Group Post', 'buddyboss-automations' ),
			'category' => 'group',
		) );
		BB_CRM_Auto_Triggers::register( 'group_media_uploaded', array(
			'label'    => __( 'User Uploads Media to Group', 'buddyboss-automations' ),
			'category' => 'group',
		) );
		BB_CRM_Auto_Triggers::register( 'group_document_uploaded', array(
			'label'    => __( 'User Uploads Document to Group', 'buddyboss-automations' ),
			'category' => 'group',
		) );
		BB_CRM_Auto_Triggers::register( 'group_first_join', array(
			'label'    => __( 'User Joins Their First Group', 'buddyboss-automations' ),
			'category' => 'group',
		) );

		if ( function_exists( 'buddypress' ) ) {
			add_action( 'groups_join_group',           array( __CLASS__, 'on_join_group' ), 10, 2 );
			add_action( 'groups_leave_group',          array( __CLASS__, 'on_leave_group' ), 10, 2 );
			add_action( 'groups_accept_invite',        array( __CLASS__, 'on_invite_accepted' ), 10, 2 );
			add_action( 'groups_reject_invite',        array( __CLASS__, 'on_invite_rejected' ), 10, 2 );
			add_action( 'groups_membership_requested', array( __CLASS__, 'on_request_sent' ), 10, 3 );
			add_action( 'groups_membership_accepted',  array( __CLASS__, 'on_request_approved' ), 10, 2 );
			add_action( 'groups_membership_rejected',  array( __CLASS__, 'on_request_rejected' ), 10, 2 );
			add_action( 'groups_promote_member',       array( __CLASS__, 'on_role_promoted' ), 10, 3 );
			add_action( 'groups_demote_member',        array( __CLASS__, 'on_role_demoted' ), 10, 2 );
			add_action( 'groups_ban_member',           array( __CLASS__, 'on_banned' ), 10, 2 );
			add_action( 'groups_unban_member',         array( __CLASS__, 'on_unbanned' ), 10, 2 );
			add_action( 'groups_group_create_complete',array( __CLASS__, 'on_group_created' ), 10, 1 );
		}
	}

	public static function on_join_group( $group_id, $user_id ) {
		$data = array( 'group_id' => $group_id );

		// Check if first group ever.
		if ( function_exists( 'groups_get_groups' ) ) {
			$user_groups = groups_get_groups( array( 'user_id' => $user_id, 'per_page' => 2 ) );
			if ( isset( $user_groups['total'] ) && (int) $user_groups['total'] === 1 ) {
				do_action( 'bb_crm_auto_trigger', 'group_first_join', $user_id, $data );
			}
		}

		do_action( 'bb_crm_auto_trigger', 'group_joined', $user_id, $data );
		do_action( 'bb_crm_auto_trigger', 'group_joined_specific', $user_id, $data );
	}

	public static function on_leave_group( $group_id, $user_id ) {
		$data = array( 'group_id' => $group_id );
		do_action( 'bb_crm_auto_trigger', 'group_left', $user_id, $data );
		do_action( 'bb_crm_auto_trigger', 'group_left_specific', $user_id, $data );
	}

	public static function on_invite_accepted( $user_id, $group_id ) {
		do_action( 'bb_crm_auto_trigger', 'group_invite_accepted', $user_id, array( 'group_id' => $group_id ) );
	}

	public static function on_invite_rejected( $user_id, $group_id ) {
		do_action( 'bb_crm_auto_trigger', 'group_invite_rejected', $user_id, array( 'group_id' => $group_id ) );
	}

	public static function on_request_sent( $requesting_user_id, $admins, $group_id ) {
		do_action( 'bb_crm_auto_trigger', 'group_request_sent', $requesting_user_id, array( 'group_id' => $group_id ) );
	}

	public static function on_request_approved( $user_id, $group_id ) {
		do_action( 'bb_crm_auto_trigger', 'group_request_approved', $user_id, array( 'group_id' => $group_id ) );
	}

	public static function on_request_rejected( $user_id, $group_id ) {
		do_action( 'bb_crm_auto_trigger', 'group_request_rejected', $user_id, array( 'group_id' => $group_id ) );
	}

	public static function on_role_promoted( $group_id, $user_id, $status ) {
		do_action( 'bb_crm_auto_trigger', 'group_role_promoted', $user_id, array( 'group_id' => $group_id, 'new_status' => $status ) );
	}

	public static function on_role_demoted( $group_id, $user_id ) {
		do_action( 'bb_crm_auto_trigger', 'group_role_demoted', $user_id, array( 'group_id' => $group_id ) );
	}

	public static function on_banned( $group_id, $user_id ) {
		do_action( 'bb_crm_auto_trigger', 'group_banned', $user_id, array( 'group_id' => $group_id ) );
	}

	public static function on_unbanned( $group_id, $user_id ) {
		do_action( 'bb_crm_auto_trigger', 'group_unbanned', $user_id, array( 'group_id' => $group_id ) );
	}

	public static function on_group_created( $group_id ) {
		$group   = function_exists( 'groups_get_group' ) ? groups_get_group( $group_id ) : null;
		$user_id = $group ? $group->creator_id : get_current_user_id();
		do_action( 'bb_crm_auto_trigger', 'group_created', $user_id, array( 'group_id' => $group_id ) );
	}
}

BB_CRM_Group_Triggers::init();
