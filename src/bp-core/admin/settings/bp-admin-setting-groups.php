<?php

class BP_Admin_Setting_Groups extends BP_Admin_Setting_tab {

	public function initialize() {
		$this->tab_name      = 'Groups';
		$this->tab_slug      = 'bp-groups';
		$this->section_name  = 'bp_groups';
		$this->section_label = __( 'Groups Settings', 'buddyboss' );
	}

	protected function is_active() {
		return bp_is_active( 'groups' );
	}

	public function register_fields() {
		// Allow subscriptions setting.
		$this->add_field( 'bp_restrict_group_creation', __( 'Group Creation', 'buddyboss' ), 'bp_admin_setting_callback_group_creation', 'intval' );

		// Allow group avatars.
		$this->add_field( 'bp-disable-group-avatar-uploads', __( 'Group Photo Uploads', 'buddyboss' ), 'bp_admin_setting_callback_group_avatar_uploads', 'intval' );

		// Allow group cover images.
		if ( bp_is_active( 'groups', 'cover_image' ) ) {
			$this->add_field( 'bp-disable-group-cover-image-uploads', __( 'Group Cover Image Uploads', 'buddyboss' ), 'bp_admin_setting_callback_group_cover_image_uploads', 'intval' );
		}
	}

	public function bp_admin_setting_callback_group_creation() {
	?>
		<input id="bp_restrict_group_creation" name="bp_restrict_group_creation" type="checkbox" aria-describedby="bp_group_creation_description" value="1" <?php checked( !bp_restrict_group_creation( false ) ); ?> />
		<label for="bp_restrict_group_creation"><?php _e( 'Enable group creation for all users', 'buddyboss' ); ?></label>
		<p class="description" id="bp_group_creation_description"><?php _e( 'Administrators can always create groups, regardless of this setting.', 'buddyboss' ); ?></p>
	<?php
	}

	public function bp_admin_setting_callback_group_avatar_uploads() {
		$this->checkbox('bp-disable-group-avatar-uploads', __( 'Allow customizable avatars for groups', 'buddyboss' ), 'bp_disable_group_avatar_uploads');
	}

	public function bp_admin_setting_callback_group_cover_image_uploads() {
		$this->checkbox('bp-disable-group-cover-image-uploads', __( 'Allow customizable cover images for groups', 'buddyboss' ), 'bp_disable_group_cover_image_uploads');
	}
}

return new BP_Admin_Setting_Groups;
