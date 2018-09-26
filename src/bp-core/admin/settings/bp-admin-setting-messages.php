<?php

class BP_Admin_Setting_Messages extends BP_Admin_Setting_tab {

	public function initialize() {
		$this->tab_name      = 'Messages';
		$this->tab_slug      = 'bp-messages';
		$this->section_name  = 'bp_messages';
		$this->section_label = __( 'Messages Settings', 'buddyboss' );
	}

	protected function is_active() {
		return bp_is_active( 'messages' );
	}

	public function register_fields() {
		// $this->add_field( 'bp-disable-group-cover-image-uploads', __( 'Group Cover Image Uploads', 'buddyboss' ), 'bp_admin_setting_callback_group_cover_image_uploads', 'intval' );
	}

	// public function bp_admin_setting_callback_group_cover_image_uploads() {
	// 	$this->checkbox('bp-disable-group-cover-image-uploads', __( 'Allow customizable cover images for groups', 'buddyboss' ), 'bp_disable_group_cover_image_uploads');
	// }
}

return new BP_Admin_Setting_Messages;
