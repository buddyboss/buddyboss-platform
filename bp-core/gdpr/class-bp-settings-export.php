<?php
/**
 * Export API: BP_Settings_Export class
 *
 * @package BuddyBoss\GDPR
 * @since BuddyBoss 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Class BP_Settings_Export
 */
final class BP_Settings_Export extends BP_Export {

	/**
	 * Get the instance of this class.
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @return Controller|null
	 */
	public static function instance() {
		static $instance = null;

		if ( null === $instance ) {
			$instance = new BP_Settings_Export();
			$instance->setup( 'bp_settings', __( 'Settings', 'buddyboss' ) );
		}

		return $instance;
	}


	/**
	 * Export member profile settings.
	 *
	 * @param $user
	 * @param $page
	 * @param bool $email_address
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @return array
	 */
	function process_data( $user, $page, $email_address = false ) {

		if ( ! $user || is_wp_error( $user ) ) {
			return $this->response( array(), true );
		}

		$export_items = array();
		$group_id     = 'bp_settings';
		$group_label  = __( 'Settings', 'buddyboss' );
		$item_id      = "{$this->exporter_name}-{$group_id}";

		/**
		 * Notification Preferences
		 */

		$notification_settings = $this->get_notification_settings();

		$notification_data = array();

		foreach ( $notification_settings as $noti_key => $notification_label ) {

			$value = bp_get_user_meta( $user->ID, $noti_key, true );

			if ( empty( $value ) || 'yes' === $value ) {
				if ( 'yes' === $value ) {
					$value = __( 'Yes', 'buddyboss' );
				} else {
					$value = __( 'Yes (Default)', 'buddyboss' );
				}
			} else {
				$value = __( 'No', 'buddyboss' );
			}

			$notification_data[] = array(
				'name'  => $notification_label,
				'value' => $value,
			);

		}

		$notification_data = apply_filters( 'buddyboss_bp_gdpr_notification_settings_after_data_prepare', $notification_data, $user );

		$data = bb_core_notification_preferences_data();

		$export_items[] = array(
			'group_id'    => $group_id . '_notification',
			'group_label' => $data['menu_title'],
			'item_id'     => 'bp_notification_settings',
			'data'        => $notification_data,
		);

		$export_items = apply_filters( 'buddyboss_bp_gdpr_additional_settings', $export_items, $user );

		$done = true;

		return $this->response( $export_items, $done );
	}


	/**
	 * Delete user profile settings.
	 *
	 * @param $user
	 * @param $page
	 * @param bool $email_address
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @return array
	 */
	function process_erase( $user, $page, $email_address ) {

		if ( ! $user || is_wp_error( $user ) ) {
			return $this->response_erase( array(), true );
		}

		$items_removed  = true;
		$items_retained = false;

		$notification_settings = $this->get_notification_settings();

		foreach ( $notification_settings as $noti_key => $notification_label ) {
			bp_delete_user_meta( $user->ID, $noti_key );
		}

		/**
		 * @todo add title/description
		 *
		 * @since BuddyBoss 1.0.0
		 */
		do_action( 'buddyboss_bp_gdpr_delete_additional_settings', $user );

		$done = true;

		return $this->response_erase( $items_removed, $done, array(), $items_retained );
	}


	/**
	 * Fetch user settings.
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @return array
	 */
	public function get_notification_settings() {
		$notification_settings = array();

		if ( bp_is_active( 'activity' ) ) {
			$notification_settings['notification_activity_new_mention'] = esc_html__( 'A member mentions you in an update using "@name"', 'buddyboss' );
			$notification_settings['notification_activity_new_reply']   = esc_html__( 'A member replies to an update or comment you\'ve posted', 'buddyboss' );
		}
		if ( bp_is_active( 'messages' ) ) {
			$notification_settings['notification_messages_new_message'] = esc_html__( 'A member sends you a new message	', 'buddyboss' );
		}
		if ( bp_is_active( 'friends' ) ) {
			$notification_settings['notification_friends_friendship_request']  = esc_html__( 'A member invites you to connect', 'buddyboss' );
			$notification_settings['notification_friends_friendship_accepted'] = esc_html__( 'A member accepts your connection request', 'buddyboss' );
		}
		if ( bp_is_active( 'groups' ) ) {
			$notification_settings['notification_groups_invite']                = esc_html__( 'A member invites you to join a group', 'buddyboss' );
			$notification_settings['notification_groups_group_updated']         = esc_html__( 'Group information is updated', 'buddyboss' );
			$notification_settings['notification_groups_admin_promotion']       = esc_html__( 'You are promoted to a group organizer or moderator', 'buddyboss' );
			$notification_settings['notification_groups_membership_request']    = esc_html__( 'A member requests to join a private group you organize', 'buddyboss' );
			$notification_settings['notification_membership_request_completed'] = esc_html__( 'Your request to join a group has been approved or denied', 'buddyboss' );
			$notification_settings['notification_group_messages_new_message']   = esc_html__( 'Message from Group Send Message', 'buddyboss' );
		}

		if ( bp_is_active( 'forums' ) ) {
			$notification_settings['notification_forums_following_reply'] = esc_html__( 'A member replies to a discussion you are subscribed to', 'buddyboss' );
			$notification_settings['notification_forums_following_topic'] = esc_html__( 'A member creates a discussion in a forum you are subscribed to', 'buddyboss' );
		}

		return $notification_settings;
	}

}
