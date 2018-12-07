<?php

class BP_Admin_Setting_Invites extends BP_Admin_Setting_tab {

	public function initialize() {
		$this->tab_label = __( 'User Invites', 'buddyboss' );
		$this->tab_name  = 'bp-invites';
		$this->tab_order = 15;
	}

	public function is_active() {
		return bp_is_active( 'invites' );
	}

	public function register_fields() {
		$this->add_section( 'bp-invites', __( 'User Invites Settings', 'buddyboss' ) );

		// Allow subscriptions setting.
		$this->add_field( 'bp-disable-invite-member-email-subject', __( 'Email Subject', 'buddyboss' ), 'bp_admin_setting_callback_member_invite_email_subject', 'intval' );

		// Allow group avatars.
		$this->add_field( 'bp-disable-invite-member-email-content', __( 'Email Content', 'buddyboss' ), 'bp_admin_setting_callback_member_invite_email_content', 'intval' );

	}
}

return new BP_Admin_Setting_Invites;
