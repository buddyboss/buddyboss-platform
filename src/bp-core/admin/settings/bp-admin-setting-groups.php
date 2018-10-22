<?php

class BP_Admin_Setting_Groups extends BP_Admin_Setting_tab {

	public function initialize() {
		$this->tab_label = __( 'Groups', 'buddyboss' );
		$this->tab_name  = 'bp-groups';
		$this->tab_order = 20;
	}

	public function is_active() {
		return bp_is_active( 'groups' );
	}

	public function register_fields() {
		$this->add_section( 'bp_groups', __( 'Groups Settings', 'buddyboss' ) );

		// Allow subscriptions setting.
		$this->add_field( 'bp_restrict_group_creation', __( 'Group Creation', 'buddyboss' ), 'bp_admin_setting_callback_group_creation', 'intval' );

		// Allow group avatars.
		$this->add_field( 'bp-disable-group-avatar-uploads', __( 'Group Photo Uploads', 'buddyboss' ), 'bp_admin_setting_callback_group_avatar_uploads', 'intval' );

		// Allow group cover images.
		if ( bp_is_active( 'groups', 'cover_image' ) ) {
			$this->add_field( 'bp-disable-group-cover-image-uploads', __( 'Group Cover Image Uploads', 'buddyboss' ), 'bp_admin_setting_callback_group_cover_image_uploads', 'intval' );
		}
	}
}

return new BP_Admin_Setting_Groups;
