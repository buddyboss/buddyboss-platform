<?php

class BP_Admin_Setting_Xprofile extends BP_Admin_Setting_tab {

	public function initialize() {
		$this->tab_name      = 'Xprofile';
		$this->tab_slug      = 'bp-xprofile';
		$this->section_name  = 'bp_xprofile';
		$this->section_label = __( 'Profile Settings', 'buddyboss' );
	}

	protected function is_active() {
		return bp_is_active( 'xprofile' );
	}

	public function register_fields() {
		// Avatars.
		$this->add_field( 'bp-disable-avatar-uploads', __( 'Profile Photo Uploads', 'buddyboss' ), 'bp_admin_setting_callback_avatar_uploads', 'intval' );

		// Cover images.
		if ( bp_is_active( 'xprofile', 'cover_image' ) ) {
			$this->add_field( 'bp-disable-cover-image-uploads', __( 'Cover Image Uploads', 'buddyboss' ), 'bp_admin_setting_callback_cover_image_uploads', 'intval' );
		}

		// Profile sync setting.
		$this->add_field( 'bp-disable-profile-sync', __( 'Profile Syncing', 'buddyboss' ), 'bp_admin_setting_callback_profile_sync', 'intval' );

		// Enable/Disable member dashboard.
		$this->add_field( 'bp-enable-member-dashboard', __( 'Member Dashboard', 'buddyboss' ), 'bp_admin_setting_callback_member_dashboard', 'intval' );
	}

	/**
	 * Allow members to upload avatars field.
	 *
	 * @since BuddyPress 1.6.0
	 *
	 */
	public function bp_admin_setting_callback_avatar_uploads() {
		$this->checkbox('bp-disable-avatar-uploads', __( 'Allow registered members to upload avatars', 'buddyboss' ), 'bp_disable_avatar_uploads');
	}

	/**
	 * Allow members to upload cover images field.
	 *
	 * @since BuddyPress 2.4.0
	 */
	public function bp_admin_setting_callback_cover_image_uploads() {
		$this->checkbox('bp-disable-cover-image-uploads', __( 'Allow registered members to upload cover images', 'buddyboss' ), 'bp_disable_cover_image_uploads');
	}

	/**
	 * Enable BP->WP profile syncing field.
	 *
	 * @since BuddyPress 1.6.0
	 *
	 */
	public function bp_admin_setting_callback_profile_sync() {
		$this->checkbox('bp-disable-profile-sync', __( 'Enable BuddyBoss to WordPress profile syncing', 'buddyboss' ), 'bp_disable_profile_sync');
	}

	/**
	 * Enable member dashboard/front-page template.
	 *
	 * @since BuddyBoss 3.1.1
	 *
	 */
	public function bp_admin_setting_callback_member_dashboard() {
		?>
			<input id="bp-enable-member-dashboard" name="bp-enable-member-dashboard" type="checkbox" value="1" <?php checked( bp_nouveau_get_appearance_settings( 'user_front_page' ) ); ?> />
			<label for="bp-enable-member-dashboard"><?php _e( 'Enable Dashboard for member profiles', 'buddyboss' ); ?></label>
		<?php
	}
}

return new BP_Admin_Setting_Xprofile;
