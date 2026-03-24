<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * User / Member triggers (15 triggers).
 */
class BB_CRM_User_Triggers {

	public static function init() {
		// Register trigger definitions.
		BB_CRM_Auto_Triggers::register( 'user_registered', array(
			'label'       => __( 'User Registers', 'buddyboss-automations' ),
			'category'    => 'user',
			'description' => __( 'Fires when a new user registers on the site.', 'buddyboss-automations' ),
		) );
		BB_CRM_Auto_Triggers::register( 'user_login', array(
			'label'    => __( 'User Logs In', 'buddyboss-automations' ),
			'category' => 'user',
		) );
		BB_CRM_Auto_Triggers::register( 'user_first_login', array(
			'label'    => __( 'User Logs In For First Time', 'buddyboss-automations' ),
			'category' => 'user',
		) );
		BB_CRM_Auto_Triggers::register( 'user_role_changed', array(
			'label'    => __( 'User Role Changes', 'buddyboss-automations' ),
			'category' => 'user',
		) );
		BB_CRM_Auto_Triggers::register( 'user_role_added', array(
			'label'    => __( 'User Assigned a Specific Role', 'buddyboss-automations' ),
			'category' => 'user',
			'fields'   => array( 'role' => __( 'Role', 'buddyboss-automations' ) ),
		) );
		BB_CRM_Auto_Triggers::register( 'user_deleted', array(
			'label'    => __( 'User Account Deleted', 'buddyboss-automations' ),
			'category' => 'user',
		) );
		BB_CRM_Auto_Triggers::register( 'user_email_confirmed', array(
			'label'    => __( 'User Confirms Email Address', 'buddyboss-automations' ),
			'category' => 'user',
		) );
		BB_CRM_Auto_Triggers::register( 'user_profile_completed', array(
			'label'    => __( 'User Completes Profile', 'buddyboss-automations' ),
			'category' => 'user',
		) );
		BB_CRM_Auto_Triggers::register( 'user_membership_level_changed', array(
			'label'    => __( 'Membership Level Changes', 'buddyboss-automations' ),
			'category' => 'user',
		) );
		BB_CRM_Auto_Triggers::register( 'user_subscription_started', array(
			'label'    => __( 'User Starts Subscription', 'buddyboss-automations' ),
			'category' => 'user',
		) );
		BB_CRM_Auto_Triggers::register( 'user_subscription_cancelled', array(
			'label'    => __( 'User Cancels Subscription', 'buddyboss-automations' ),
			'category' => 'user',
		) );
		BB_CRM_Auto_Triggers::register( 'user_inactive_days', array(
			'label'    => __( 'User Inactive for X Days', 'buddyboss-automations' ),
			'category' => 'user',
			'fields'   => array( 'days' => __( 'Days of inactivity', 'buddyboss-automations' ) ),
		) );
		BB_CRM_Auto_Triggers::register( 'user_password_reset', array(
			'label'    => __( 'User Requests Password Reset', 'buddyboss-automations' ),
			'category' => 'user',
		) );
		BB_CRM_Auto_Triggers::register( 'user_avatar_uploaded', array(
			'label'    => __( 'User Uploads Avatar', 'buddyboss-automations' ),
			'category' => 'user',
		) );
		BB_CRM_Auto_Triggers::register( 'user_cover_photo_uploaded', array(
			'label'    => __( 'User Uploads Cover Photo', 'buddyboss-automations' ),
			'category' => 'user',
		) );

		// Attach WordPress / BuddyBoss hooks.
		add_action( 'user_register',          array( __CLASS__, 'on_user_registered' ), 10, 1 );
		add_action( 'wp_login',               array( __CLASS__, 'on_user_login' ), 10, 2 );
		add_action( 'set_user_role',          array( __CLASS__, 'on_role_changed' ), 10, 3 );
		add_action( 'add_user_role',          array( __CLASS__, 'on_role_added' ), 10, 2 );
		add_action( 'delete_user',            array( __CLASS__, 'on_user_deleted' ), 10, 1 );
		add_action( 'retrieve_password_key',  array( __CLASS__, 'on_password_reset' ), 10, 2 );
		add_action( 'xprofile_updated_profile', array( __CLASS__, 'on_profile_updated' ), 10, 1 );

		if ( function_exists( 'buddypress' ) ) {
			add_action( 'bp_core_signup_user',        array( __CLASS__, 'on_bp_signup' ), 10, 1 );
			add_action( 'bp_members_avatar_uploaded', array( __CLASS__, 'on_avatar_uploaded' ), 10, 1 );
			add_action( 'xprofile_cover_image_uploaded', array( __CLASS__, 'on_cover_photo_uploaded' ), 10, 1 );
		}
	}

	public static function on_user_registered( $user_id ) {
		do_action( 'bb_crm_auto_trigger', 'user_registered', $user_id, array( 'user_id' => $user_id ) );
	}

	public static function on_user_login( $user_login, $user ) {
		$user_id   = $user->ID;
		$login_key = "bb_crm_auto_first_login_{$user_id}";

		if ( ! get_user_meta( $user_id, $login_key, true ) ) {
			update_user_meta( $user_id, $login_key, 1 );
			do_action( 'bb_crm_auto_trigger', 'user_first_login', $user_id, array( 'user_login' => $user_login ) );
		}

		do_action( 'bb_crm_auto_trigger', 'user_login', $user_id, array( 'user_login' => $user_login ) );
	}

	public static function on_role_changed( $user_id, $role, $old_roles ) {
		do_action( 'bb_crm_auto_trigger', 'user_role_changed', $user_id, array( 'new_role' => $role, 'old_roles' => $old_roles ) );
	}

	public static function on_role_added( $user_id, $role ) {
		do_action( 'bb_crm_auto_trigger', 'user_role_added', $user_id, array( 'role' => $role ) );
	}

	public static function on_user_deleted( $user_id ) {
		do_action( 'bb_crm_auto_trigger', 'user_deleted', $user_id, array( 'user_id' => $user_id ) );
	}

	public static function on_password_reset( $user_login, $key ) {
		$user = get_user_by( 'login', $user_login );
		if ( $user ) {
			do_action( 'bb_crm_auto_trigger', 'user_password_reset', $user->ID, array() );
		}
	}

	public static function on_profile_updated( $user_id ) {
		do_action( 'bb_crm_auto_trigger', 'user_profile_completed', $user_id, array() );
	}

	public static function on_bp_signup( $user_id ) {
		do_action( 'bb_crm_auto_trigger', 'user_email_confirmed', $user_id, array() );
	}

	public static function on_avatar_uploaded( $user_id ) {
		do_action( 'bb_crm_auto_trigger', 'user_avatar_uploaded', $user_id, array() );
	}

	public static function on_cover_photo_uploaded( $user_id ) {
		do_action( 'bb_crm_auto_trigger', 'user_cover_photo_uploaded', $user_id, array() );
	}
}

BB_CRM_User_Triggers::init();
