<?php

class BP_Admin_Setting_Xprofile extends BP_Admin_Setting_tab {

	public function initialize() {
		$this->tab_label = __( 'Profiles', 'buddyboss' );
		$this->tab_name  = 'bp-xprofile';
		$this->tab_order = 10;
	}

	public function settings_save() {
		parent::settings_save();

        /**
         * sync bp-enable-member-dashboard with cutomizer settings.
         * @since BuddyBoss 3.1.1
         */
        $bp_nouveau_appearance = bp_get_option( 'bp_nouveau_appearance', array() );
        $bp_nouveau_appearance[ 'user_front_page' ] = isset( $_POST[ 'bp-enable-member-dashboard' ] ) ? $_POST[ 'bp-enable-member-dashboard' ] : 0;
        bp_update_option( 'bp_nouveau_appearance', $bp_nouveau_appearance );

        /**
         * Set requirement for last name based on display format
         */
        if ( isset( $_POST[ 'bp-display-name-format' ] ) && $_POST[ 'bp-display-name-format' ] == 'first_last_name' ) {
        	if ( $last_name_field = xprofile_get_field( bp_xprofile_lastname_field_id() ) ) {
        		$last_name_field->is_required = true;
        		$last_name_field->save();
        	}
        }
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

		// Enable/Disable profile dashboard.
		$this->add_field( 'bp-enable-member-dashboard', __( 'Profile Dashboard', 'buddyboss' ), [$this, 'bp_admin_setting_callback_member_dashboard'], 'intval' );

        // Enable/Disable profile search.
		$this->add_field( 'bp-enable-profile-search', __( 'Profile Search', 'buddyboss' ), [$this, 'bp_admin_setting_callback_profile_search'], 'intval' );

		// Section for member types.
		$this->add_section( 'bp_member_type_settings', __( 'Profile Types', 'buddyboss' ) );

		// Enable/Disable Member types.
		$this->add_field( 'bp-member-type-enable-disable', __( 'Profile Types', 'buddyboss' ), [$this, 'bp_admin_setting_callback_member_type_enable_disable'], 'intval' );

		// Enable/Disable Display on profiles.
		$this->add_field( 'bp-member-type-display-on-profile', __( 'Display on Profiles', 'buddyboss' ), [$this, 'bp_admin_setting_callback_member_type_display_on_profile'], 'intval' );

		// Member types import.
		$this->add_field( 'bp-member-type-import', __( 'Import Profile Types', 'buddyboss' ), [$this, 'bp_admin_setting_callback_member_type_import'], 'intval' );
	}

	/**
	 * Enable profile dashboard/front-page template.
	 *
	 * @since BuddyBoss 3.1.1
	 *
	 */
	public function bp_admin_setting_callback_member_dashboard() {
		?>
			<input id="bp-enable-member-dashboard" name="bp-enable-member-dashboard" type="checkbox" value="1" <?php checked( bp_nouveau_get_appearance_settings( 'user_front_page' ) ); ?> />
			<label for="bp-enable-member-dashboard"><?php _e( 'Enable a personal dashboard of widgets on member profiles', 'buddyboss' ); ?></label>
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
			sprintf(
				__( 'After the format has been updated, remember to run the <a href="%s">tool</a> to update all the users.', 'buddyboss' ),
				add_query_arg([
					'page' => 'bp-tools',
					'tool' => 'bp-wordpress-update-display-name'
				], admin_url( 'tools.php' ) )
			)
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
			<label for="bp-enable-profile-search"><?php _e( 'Enable advanced profile search on the members directory', 'buddyboss' ); ?></label>
		<?php
	}

	/**
	 * Provide link to access import setting.
	 *
	 * @since BuddyBoss 3.1.1
	 *
	 */
	public function bp_admin_setting_callback_member_type_import() {
		$import_url = admin_url().'users.php?page=bp-member-type-import';
		//echo '<a href="'. esc_url( $import_url ).'">Click here to go import page.</a>';
		printf(
			__( 'Click <a href="%s">here</a> to import existing profile types (or "member types" in BuddyPress)', 'buddyboss' ),
			esc_url( $import_url )
		);
	}

	/**
	 * Enable member type.
	 *
	 * @since BuddyBoss 3.1.1
	 *
	 */
	public function bp_admin_setting_callback_member_type_enable_disable() {
		?>
		<input id="bp-member-type-enable-disable" name="bp-member-type-enable-disable" type="checkbox" value="1" <?php checked( bp_member_type_enable_disable() ); ?> />
		<label for="bp-member-type-enable-disable"><?php _e( 'Enable profile types to give members unique profile fields and permissions', 'buddyboss' ); ?></label>
		<?php
	}

	/**
	 * Enable Display on profile?
	 *
	 * @since BuddyBoss 3.1.1
	 *
	 */
	public function bp_admin_setting_callback_member_type_display_on_profile() {
		?>
		<input id="bp-member-type-display-on-profile" name="bp-member-type-display-on-profile" type="checkbox" value="1" <?php checked( bp_member_type_display_on_profile() ); ?> />
		<label for="bp-member-type-display-on-profile"><?php _e( 'Display each member\'s profile type on their profile page', 'buddyboss' ); ?></label>
		<?php
	}
}

return new BP_Admin_Setting_Xprofile;
