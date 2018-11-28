<?php

class BP_Admin_Setting_Groups extends BP_Admin_Setting_tab {

	public function initialize() {
		$this->tab_label = __( 'Social Groups', 'buddyboss' );
		$this->tab_name  = 'bp-groups';
		$this->tab_order = 20;
	}

	public function is_active() {
		return bp_is_active( 'groups' );
	}

	public function register_fields() {
		$this->add_section( 'bp_groups', __( 'Group Settings', 'buddyboss' ) );

		// Allow subscriptions setting.
		$this->add_field( 'bp_restrict_group_creation', __( 'Group Creation', 'buddyboss' ), 'bp_admin_setting_callback_group_creation', 'intval' );

		// Allow group avatars.
		$this->add_field( 'bp-disable-group-avatar-uploads', __( 'Group Photo Uploads', 'buddyboss' ), 'bp_admin_setting_callback_group_avatar_uploads', 'intval' );

		// Allow group cover images.
		if ( bp_is_active( 'groups', 'cover_image' ) ) {
			$this->add_field( 'bp-disable-group-cover-image-uploads', __( 'Group Cover Image Uploads', 'buddyboss' ), 'bp_admin_setting_callback_group_cover_image_uploads', 'intval' );
		}

		// Register Group Types sections.
		$this->add_section( 'bp_groups_types', __( 'Group Types', 'buddyboss' ) );

		// enable or disable group types.
		$this->add_field( 'bp-disable-group-type-creation', __( 'Group Types', 'buddyboss' ), 'bp_admin_setting_callback_group_type_creation', 'intval' );


		// enable or disable group types.
		$this->add_field( 'bp-enable-group-hierarchies', __( 'Group Hierarchies', 'buddyboss' ), 'bp_admin_setting_callback_group_hierarchies', 'intval' );

	}
}

return new BP_Admin_Setting_Groups;
