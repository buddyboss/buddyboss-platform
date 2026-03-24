<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Profile & Connection triggers (12 triggers).
 */
class BB_CRM_Profile_Triggers {

	public static function init() {
		BB_CRM_Auto_Triggers::register( 'profile_field_updated', array(
			'label'    => __( 'Profile Field Updated', 'buddyboss-automations' ),
			'category' => 'profile',
			'fields'   => array( 'field_name' => __( 'Field Name', 'buddyboss-automations' ) ),
		) );
		BB_CRM_Auto_Triggers::register( 'profile_type_changed', array(
			'label'    => __( 'Profile Type Changed', 'buddyboss-automations' ),
			'category' => 'profile',
		) );
		BB_CRM_Auto_Triggers::register( 'profile_type_assigned', array(
			'label'    => __( 'User Assigned a Specific Profile Type', 'buddyboss-automations' ),
			'category' => 'profile',
			'fields'   => array( 'profile_type' => __( 'Profile Type', 'buddyboss-automations' ) ),
		) );
		BB_CRM_Auto_Triggers::register( 'friend_request_sent', array(
			'label'    => __( 'User Sends a Friend Request', 'buddyboss-automations' ),
			'category' => 'profile',
		) );
		BB_CRM_Auto_Triggers::register( 'friend_request_accepted', array(
			'label'    => __( 'User Accepts a Friend Request', 'buddyboss-automations' ),
			'category' => 'profile',
		) );
		BB_CRM_Auto_Triggers::register( 'friendship_removed', array(
			'label'    => __( 'User Removes a Friend', 'buddyboss-automations' ),
			'category' => 'profile',
		) );
		BB_CRM_Auto_Triggers::register( 'message_sent', array(
			'label'    => __( 'User Sends a Private Message', 'buddyboss-automations' ),
			'category' => 'profile',
		) );
		BB_CRM_Auto_Triggers::register( 'user_followed', array(
			'label'    => __( 'User Follows Another User', 'buddyboss-automations' ),
			'category' => 'profile',
		) );
		BB_CRM_Auto_Triggers::register( 'user_unfollowed', array(
			'label'    => __( 'User Unfollows Another User', 'buddyboss-automations' ),
			'category' => 'profile',
		) );
		BB_CRM_Auto_Triggers::register( 'notification_received', array(
			'label'    => __( 'User Receives a Notification', 'buddyboss-automations' ),
			'category' => 'profile',
		) );
		BB_CRM_Auto_Triggers::register( 'profile_visibility_changed', array(
			'label'    => __( 'Profile Visibility Changed', 'buddyboss-automations' ),
			'category' => 'profile',
		) );
		BB_CRM_Auto_Triggers::register( 'user_suspended', array(
			'label'    => __( 'User Account Suspended', 'buddyboss-automations' ),
			'category' => 'profile',
		) );

		if ( function_exists( 'buddypress' ) ) {
			add_action( 'xprofile_updated_profile',        array( __CLASS__, 'on_profile_updated' ), 10, 5 );
			add_action( 'bp_set_member_type',              array( __CLASS__, 'on_profile_type_changed' ), 10, 3 );
			add_action( 'friends_friendship_requested',    array( __CLASS__, 'on_friend_request_sent' ), 10, 3 );
			add_action( 'friends_friendship_accepted',     array( __CLASS__, 'on_friend_accepted' ), 10, 3 );
			add_action( 'friends_friendship_deleted',      array( __CLASS__, 'on_friendship_removed' ), 10, 3 );
			add_action( 'messages_message_sent',           array( __CLASS__, 'on_message_sent' ), 10, 1 );
		}

		// BuddyBoss follow hooks.
		add_action( 'bp_follow_start_following', array( __CLASS__, 'on_user_followed' ), 10, 1 );
		add_action( 'bp_follow_stop_following',  array( __CLASS__, 'on_user_unfollowed' ), 10, 1 );

		// BuddyBoss moderation hooks.
		add_action( 'bb_moderation_after_save',  array( __CLASS__, 'on_moderation_event' ), 10, 1 );
	}

	public static function on_profile_updated( $user_id, $posted_field_ids, $errors, $old_values, $new_values ) {
		do_action( 'bb_crm_auto_trigger', 'profile_field_updated', $user_id, array(
			'field_ids' => $posted_field_ids,
		) );
	}

	public static function on_profile_type_changed( $user_id, $member_types, $append ) {
		$data = array( 'profile_types' => $member_types );
		do_action( 'bb_crm_auto_trigger', 'profile_type_changed', $user_id, $data );
		do_action( 'bb_crm_auto_trigger', 'profile_type_assigned', $user_id, $data );
	}

	public static function on_friend_request_sent( $friendship_id, $initiator_user_id, $friend_user_id ) {
		do_action( 'bb_crm_auto_trigger', 'friend_request_sent', $initiator_user_id, array( 'friend_id' => $friend_user_id ) );
	}

	public static function on_friend_accepted( $friendship_id, $initiator_user_id, $friend_user_id ) {
		do_action( 'bb_crm_auto_trigger', 'friend_request_accepted', $friend_user_id, array( 'initiator_id' => $initiator_user_id ) );
		do_action( 'bb_crm_auto_trigger', 'friend_request_accepted', $initiator_user_id, array( 'friend_id' => $friend_user_id ) );
	}

	public static function on_friendship_removed( $friendship_id, $initiator_user_id, $friend_user_id ) {
		do_action( 'bb_crm_auto_trigger', 'friendship_removed', $initiator_user_id, array( 'friend_id' => $friend_user_id ) );
	}

	public static function on_message_sent( $message ) {
		$user_id = is_object( $message ) ? $message->sender_id : get_current_user_id();
		do_action( 'bb_crm_auto_trigger', 'message_sent', $user_id, array() );
	}

	public static function on_user_followed( $args ) {
		$user_id   = $args['follower_id'] ?? 0;
		$following = $args['leader_id'] ?? 0;
		if ( $user_id ) {
			do_action( 'bb_crm_auto_trigger', 'user_followed', $user_id, array( 'following_id' => $following ) );
		}
	}

	public static function on_user_unfollowed( $args ) {
		$user_id   = $args['follower_id'] ?? 0;
		$following = $args['leader_id'] ?? 0;
		if ( $user_id ) {
			do_action( 'bb_crm_auto_trigger', 'user_unfollowed', $user_id, array( 'unfollowed_id' => $following ) );
		}
	}

	public static function on_moderation_event( $moderation ) {
		if ( ! is_object( $moderation ) ) return;
		$user_id = $moderation->user_id ?? 0;
		$type    = $moderation->type ?? '';
		if ( ! $user_id || ! $type ) return;

		// Fire a generic moderation trigger that automations can listen to.
		do_action( 'bb_crm_auto_trigger', 'moderation_event', $user_id, array(
			'moderation_type' => $type,
			'item_id'         => $moderation->item_id ?? 0,
		) );

		if ( $type === 'user' || $type === 'suspended_member' ) {
			do_action( 'bb_crm_auto_trigger', 'user_suspended', $user_id, array() );
		}
	}
}

BB_CRM_Profile_Triggers::init();
