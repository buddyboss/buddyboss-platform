<?php

class BP_Admin_Setting_Xprofile extends BP_Admin_Setting_tab {

	public function initialize() {
		$this->tab_label = __( 'Profiles', 'buddyboss' );
		$this->tab_name  = 'bp-xprofile';
		$this->tab_order = 10;
	}

	public function setting_save() {
		parent::setting_save();

        /**
         * sync bp-enable-member-dashboard with cutomizer settings.
         * @since BuddyBoss 3.1.1
         */
        $bp_nouveau_appearance = bp_get_option( 'bp_nouveau_appearance', array() );
        $bp_nouveau_appearance[ 'user_front_page' ] = isset( $_POST[ 'bp-enable-member-dashboard' ] ) ? $_POST[ 'bp-enable-member-dashboard' ] : 0;
        bp_update_option( 'bp_nouveau_appearance', $bp_nouveau_appearance );
	}

	public function register_fields() {
		$this->add_section( 'bp_xprofile', __( 'Profile Settings', 'buddyboss' ) );

		// Display name format.
		$this->add_field(
			'bp-display-name-format',
			__( 'Display Name Format', 'buddyboss' ),
			[ $this, 'callback_display_name_format']
		);

		// Avatars.
		$this->add_field( 'bp-disable-avatar-uploads', __( 'Profile Photo Uploads', 'buddyboss' ), 'bp_admin_setting_callback_avatar_uploads', 'intval' );

		// Cover images.
		if ( bp_is_active( 'xprofile', 'cover_image' ) ) {
			$this->add_field( 'bp-disable-cover-image-uploads', __( 'Cover Image Uploads', 'buddyboss' ), 'bp_admin_setting_callback_cover_image_uploads', 'intval' );
		}

		// Enable/Disable member dashboard.
		$this->add_field( 'bp-enable-member-dashboard', __( 'Member Dashboard', 'buddyboss' ), [$this, 'bp_admin_setting_callback_member_dashboard'], 'intval' );

        // Enable/Disable profile search.
		$this->add_field( 'bp-enable-profile-search', __( 'Advanced Search', 'buddyboss' ), [$this, 'bp_admin_setting_callback_profile_search'], 'intval' );
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

	public function callback_display_name_format() {
		$options = [
			'first_name'      => __( 'First Name', 'buddyboss' ),
			'first_last_name' => __( 'First Name &amp; Last Name', 'buddyboss' ),
			'nickname'        => __( 'Nickname', 'buddyboss' ),
		];

		$current_value = bp_get_option( 'bp-display-name-format' );

		printf( '<select name="%1$s" for="%1$s">', 'bp-display-name-format' );
			foreach ( $options as $key => $value ) {
				printf(
					'<option value="%s" %s>%s</option>',
					$key,
					$key == $current_value? 'selected' : '',
					$value
				);
			}
		printf( '</select>' );

		printf(
			'<p class="description">%s</p>',
			__( '', 'buddyboss' )
		);
	}

	/**
	 * Enable member profile search.
	 *
	 * @since BuddyBoss 3.1.1
	 *
	 */
	public function bp_admin_setting_callback_profile_search() {
		?>
			<input id="bp-enable-profile-search" name="bp-enable-profile-search" type="checkbox" value="1" <?php checked( ! bp_disable_advanced_profile_search() ); ?> />
			<label for="bp-enable-profile-search"><?php _e( 'Enable advanced profile search on the members directory.', 'buddyboss' ); ?></label>
		<?php
	}
}

return new BP_Admin_Setting_Xprofile;
