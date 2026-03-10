<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Tag & CRM triggers (10 triggers) + Email invite + Forum discussion triggers.
 */
class BB_CRM_Tag_Triggers {

	public static function init() {
		// ── CRM Tag Triggers ────────────────────────────────────────────────
		BB_CRM_Auto_Triggers::register( 'crm_tag_added', array(
			'label'    => __( 'Tag Added to User', 'buddyboss-crm-automations' ),
			'category' => 'tag',
		) );
		BB_CRM_Auto_Triggers::register( 'crm_tag_added_specific', array(
			'label'    => __( 'Specific Tag Added to User', 'buddyboss-crm-automations' ),
			'category' => 'tag',
			'fields'   => array( 'tag_id' => __( 'Tag', 'buddyboss-crm-automations' ) ),
		) );
		BB_CRM_Auto_Triggers::register( 'crm_tag_removed', array(
			'label'    => __( 'Tag Removed from User', 'buddyboss-crm-automations' ),
			'category' => 'tag',
		) );
		BB_CRM_Auto_Triggers::register( 'crm_tag_removed_specific', array(
			'label'    => __( 'Specific Tag Removed from User', 'buddyboss-crm-automations' ),
			'category' => 'tag',
			'fields'   => array( 'tag_id' => __( 'Tag', 'buddyboss-crm-automations' ) ),
		) );
		BB_CRM_Auto_Triggers::register( 'crm_tag_expired', array(
			'label'    => __( 'User Tag Expires', 'buddyboss-crm-automations' ),
			'category' => 'tag',
		) );
		BB_CRM_Auto_Triggers::register( 'crm_list_added', array(
			'label'    => __( 'User Added to a List', 'buddyboss-crm-automations' ),
			'category' => 'tag',
		) );
		BB_CRM_Auto_Triggers::register( 'crm_list_removed', array(
			'label'    => __( 'User Removed from a List', 'buddyboss-crm-automations' ),
			'category' => 'tag',
		) );
		BB_CRM_Auto_Triggers::register( 'crm_multiple_tags', array(
			'label'    => __( 'User Has Multiple Specific Tags', 'buddyboss-crm-automations' ),
			'category' => 'tag',
		) );
		BB_CRM_Auto_Triggers::register( 'crm_tag_count_reached', array(
			'label'    => __( 'User Reaches Tag Count', 'buddyboss-crm-automations' ),
			'category' => 'tag',
			'fields'   => array( 'count' => __( 'Tag count', 'buddyboss-crm-automations' ) ),
		) );
		BB_CRM_Auto_Triggers::register( 'crm_engagement_score', array(
			'label'    => __( 'User Engagement Score Changes', 'buddyboss-crm-automations' ),
			'category' => 'tag',
		) );

		// ── Email Invite Triggers ───────────────────────────────────────────
		BB_CRM_Auto_Triggers::register( 'email_invite_sent', array(
			'label'    => __( 'User Sends an Email Invite', 'buddyboss-crm-automations' ),
			'category' => 'tag',
		) );
		BB_CRM_Auto_Triggers::register( 'email_invite_accepted', array(
			'label'    => __( 'Email Invite is Accepted (Invitee Registers)', 'buddyboss-crm-automations' ),
			'category' => 'tag',
		) );

		// ── Forum / bbPress Triggers ────────────────────────────────────────
		BB_CRM_Auto_Triggers::register( 'forum_topic_created', array(
			'label'    => __( 'User Creates a Forum Topic', 'buddyboss-crm-automations' ),
			'category' => 'tag',
		) );
		BB_CRM_Auto_Triggers::register( 'forum_reply_posted', array(
			'label'    => __( 'User Replies to a Forum Topic', 'buddyboss-crm-automations' ),
			'category' => 'tag',
		) );
		BB_CRM_Auto_Triggers::register( 'forum_topic_subscribed', array(
			'label'    => __( 'User Subscribes to a Forum Topic', 'buddyboss-crm-automations' ),
			'category' => 'tag',
		) );
		BB_CRM_Auto_Triggers::register( 'forum_first_post', array(
			'label'    => __( 'User Makes Their First Forum Post', 'buddyboss-crm-automations' ),
			'category' => 'tag',
		) );
		BB_CRM_Auto_Triggers::register( 'forum_topic_closed', array(
			'label'    => __( 'Forum Topic Closed', 'buddyboss-crm-automations' ),
			'category' => 'tag',
		) );
		BB_CRM_Auto_Triggers::register( 'forum_topic_marked_solved', array(
			'label'    => __( 'Forum Topic Marked as Solved', 'buddyboss-crm-automations' ),
			'category' => 'tag',
		) );

		// Hook into CRM tag actions.
		add_action( 'bb_crm_tag_assigned', array( __CLASS__, 'on_tag_assigned' ), 10, 3 );
		add_action( 'bb_crm_tag_removed',  array( __CLASS__, 'on_tag_removed' ), 10, 3 );
		add_action( 'bb_crm_tag_expired',  array( __CLASS__, 'on_tag_expired' ), 10, 2 );
		add_action( 'bb_crm_list_updated', array( __CLASS__, 'on_list_updated' ), 10, 2 );

		// Email invites.
		add_action( 'bp_core_sent_user_signup_email', array( __CLASS__, 'on_invite_sent' ), 10, 3 );
		add_action( 'bp_invites_invitation_accepted', array( __CLASS__, 'on_invite_accepted' ), 10, 2 );

		// bbPress / Forums.
		if ( function_exists( 'bbpress' ) || defined( 'bbp_get_version' ) ) {
			add_action( 'bbp_new_topic',  array( __CLASS__, 'on_forum_topic_created' ), 10, 4 );
			add_action( 'bbp_new_reply',  array( __CLASS__, 'on_forum_reply_posted' ), 10, 5 );
			add_action( 'bbp_close_topic', array( __CLASS__, 'on_forum_topic_closed' ), 10, 1 );
		}
	}

	public static function on_tag_assigned( $user_id, $tag_id, $source ) {
		$data = array( 'tag_id' => $tag_id, 'source' => $source );
		do_action( 'bb_crm_auto_trigger', 'crm_tag_added', $user_id, $data );
		do_action( 'bb_crm_auto_trigger', 'crm_tag_added_specific', $user_id, $data );

		// Check tag count milestone.
		global $wpdb;
		$count = (int) $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM {$wpdb->prefix}bp_user_tags WHERE user_id = %d",
			$user_id
		) );
		do_action( 'bb_crm_auto_trigger', 'crm_tag_count_reached', $user_id, array_merge( $data, array( 'count' => $count ) ) );
	}

	public static function on_tag_removed( $user_id, $tag_id, $source ) {
		$data = array( 'tag_id' => $tag_id, 'source' => $source );
		do_action( 'bb_crm_auto_trigger', 'crm_tag_removed', $user_id, $data );
		do_action( 'bb_crm_auto_trigger', 'crm_tag_removed_specific', $user_id, $data );
	}

	public static function on_tag_expired( $user_id, $tag_id ) {
		do_action( 'bb_crm_auto_trigger', 'crm_tag_expired', $user_id, array( 'tag_id' => $tag_id ) );
	}

	public static function on_list_updated( $list_id, $action ) {
		// $action is 'added' or 'removed'.
		// We don't know user_id from just list_id — list update hook needs user context.
	}

	public static function on_invite_sent( $user_email, $key, $meta ) {
		$inviter_id = $meta['inviter_id'] ?? get_current_user_id();
		do_action( 'bb_crm_auto_trigger', 'email_invite_sent', $inviter_id, array( 'invited_email' => $user_email ) );
	}

	public static function on_invite_accepted( $user_id, $inviter_id ) {
		do_action( 'bb_crm_auto_trigger', 'email_invite_accepted', $inviter_id, array( 'new_user_id' => $user_id ) );
	}

	public static function on_forum_topic_created( $topic_id, $forum_id, $anonymous_data, $topic_author ) {
		$user_id = $topic_author ?: get_current_user_id();
		$data    = array( 'topic_id' => $topic_id, 'forum_id' => $forum_id );

		// First-ever forum post check.
		if ( function_exists( 'bbp_get_user_topic_count' ) ) {
			if ( (int) bbp_get_user_topic_count( $user_id ) === 1 ) {
				do_action( 'bb_crm_auto_trigger', 'forum_first_post', $user_id, $data );
			}
		}
		do_action( 'bb_crm_auto_trigger', 'forum_topic_created', $user_id, $data );
	}

	public static function on_forum_reply_posted( $reply_id, $topic_id, $forum_id, $anonymous_data, $reply_author ) {
		$user_id = $reply_author ?: get_current_user_id();
		do_action( 'bb_crm_auto_trigger', 'forum_reply_posted', $user_id, array(
			'reply_id' => $reply_id,
			'topic_id' => $topic_id,
			'forum_id' => $forum_id,
		) );
	}

	public static function on_forum_topic_closed( $topic_id ) {
		$user_id = get_current_user_id();
		do_action( 'bb_crm_auto_trigger', 'forum_topic_closed', $user_id, array( 'topic_id' => $topic_id ) );
	}
}

BB_CRM_Tag_Triggers::init();
