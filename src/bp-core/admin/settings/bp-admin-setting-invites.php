<?php
/**
 * Add admin Invites settings page in Dashboard->BuddyBoss->Settings
 *
 * @package BuddyBoss\Core
 *
 * @since BuddyBoss 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Main class.
 *
 * @since BuddyBoss 1.0.0
 */
class BP_Admin_Setting_Invites extends BP_Admin_Setting_tab {

	public function initialize() {
		$this->tab_label = __( 'Invites', 'buddyboss' );
		$this->tab_name  = 'bp-invites';
		$this->tab_order = 70;
	}

	// Check if invites are enabled
	public function is_active() {
		return bp_is_active( 'invites' );
	}

	// Register setting fields
	public function register_fields() {
		$this->add_section( 'bp_invites', __( 'Email Invites Settings', 'buddyboss' ), '', 'bp_email_invites_tutorial' );

		// Allow members to change the email subject.
		$this->add_field( 'bp-disable-invite-member-email-subject', __( 'Email Subject', 'buddyboss' ), 'bp_admin_setting_callback_member_invite_email_subject', 'intval' );

		// Allow members to change the email content.
		$this->add_field( 'bp-disable-invite-member-email-content', __( 'Email Content', 'buddyboss' ), 'bp_admin_setting_callback_member_invite_email_content', 'intval' );

		if ( true === bp_member_type_enable_disable() ) {

			// Allow members to invite profile type.
			$this->add_field( 'bp-disable-invite-member-type', __( 'Set Profile Type', 'buddyboss' ), 'bp_admin_setting_callback_member_invite_member_type', 'intval' );

			// Allowed Profile Types to Send Invites.
			$member_types = bp_get_active_member_types();
			if ( isset( $member_types ) && ! empty( $member_types ) ) {
				$is_first = true;
				foreach ( $member_types as $member_type_id ) {

					$type                     = array();
					$type_name                = bp_get_member_type_key( $member_type_id );
					$member_type_name         = get_post_meta( $member_type_id, '_bp_member_type_label_name', true );
					$class                    = ( true === $is_first ) ? 'child-no-padding-first' : 'child-no-padding';
					$type['member_type_name'] = $member_type_name;
					$type['name']             = $type_name;
					$type['class']            = $class;
					$type['description']      = ( true === $is_first ) ? true : false;

					$this->add_field( 'bp-enable-send-invite-member-type-' . $type_name, ( true === $is_first ) ? __( 'Allowed Profile Type', 'buddyboss' ) : '', 'bp_admin_setting_callback_enable_send_invite_member_type', 'intval', $type );
					$is_first = false;

				}
			}
		}

		/**
		 * Fires to register Invites tab settings fields and section.
		 *
		 * @since BuddyBoss 1.2.6
		 *
		 * @param Object $this BP_Admin_Setting_Invites.
		 */
		do_action( 'bp_admin_setting_invites_register_fields', $this );
	}
}

return new BP_Admin_Setting_Invites();
