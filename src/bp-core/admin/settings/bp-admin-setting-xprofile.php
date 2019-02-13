<?php
/**
 * Add admin Profiles settings page in Dashboard->BuddyBoss->Settings
 *
 * @package BuddyBoss\Core
 *
 * @since BuddyBoss 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Main Profile Settings class.
 *
 * @since BuddyBoss 1.0.0
 */
class BP_Admin_Setting_Xprofile extends BP_Admin_Setting_tab {

	public function initialize() {
		$this->tab_label = __( 'Profiles', 'buddyboss' );
		$this->tab_name  = 'bp-xprofile';
		$this->tab_order = 10;
	}

	public function settings_save() {
        $if_disabled_before_saving = bp_disable_advanced_profile_search();
        
		parent::settings_save();
        
        $if_disabled_after_saving = bp_disable_advanced_profile_search();

        /**
         * sync bp-enable-member-dashboard with cutomizer settings.
		 *
         * @since BuddyBoss 1.0.0
         */
        $bp_nouveau_appearance = bp_get_option( 'bp_nouveau_appearance', array() );
        $bp_nouveau_appearance[ 'user_front_page' ] = isset( $_POST[ 'bp-enable-member-dashboard' ] ) ? $_POST[ 'bp-enable-member-dashboard' ] : 0;
        $bp_nouveau_appearance[ 'user_front_page_redirect' ] = isset( $_POST[ 'bp-enable-member-dashboard-redirect' ] ) ? $_POST[ 'bp-enable-member-dashboard-redirect' ] : 0;
        bp_update_option( 'bp_nouveau_appearance', $bp_nouveau_appearance );

        //Set requirement for last name based on display format
        if ( isset( $_POST[ 'bp-display-name-format' ] ) && $_POST[ 'bp-display-name-format' ] == 'first_last_name' ) {
        	if ( $last_name_field = xprofile_get_field( bp_xprofile_lastname_field_id() ) ) {
        		$last_name_field->is_required = true;
        		$last_name_field->save();
        	}
        }
        
        if ( $if_disabled_before_saving && ! $if_disabled_after_saving ) {
            /**
             * Advanced profile search was disabled before and is now enabled.
             * So ideally, the new 'profile search' menu should now be visible under users nav.
             * But that doesn't happen becuase by the time settings are updated, register_post_type hooks have already been executed.
             * So user doesn't see that untill next reload/request.
             * 
             * To avoid that, we'll need to do a force redirect.
             */
            wp_safe_redirect( bp_get_admin_url( 'admin.php?page=bp-settings&tab=bp-xprofile' ) );
            exit();
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

		// Section for profile dashboard.
		$this->add_section( 'bp_profile_dashboard_settings', __( 'Profile Dashboard', 'buddyboss' ) );

		// Enable/Disable profile dashboard.
		$this->add_field( 'bp-enable-member-dashboard', __( 'Profile Dashboard', 'buddyboss' ), [$this, 'bp_admin_setting_callback_member_dashboard'], 'intval' );

		$this->add_field( 'bp-enable-member-dashboard-redirect', __( 'Redirect on Login', 'buddyboss' ), [$this, 'bp_admin_setting_callback_member_dashboard_redirect'], 'intval' );

		// Section for profile search.
		$this->add_section( 'bp_profile_search_settings', __( 'Profile Search', 'buddyboss' ) );

        // Enable/Disable profile search.
		$this->add_field( 'bp-enable-profile-search', __( 'Profile Search', 'buddyboss' ), [$this, 'bp_admin_setting_callback_profile_search'], 'intval' );

		// Section for profile types.
		$this->add_section( 'bp_member_type_settings', __( 'Profile Types', 'buddyboss' ) );

		// Enable/Disable profile types.
		$this->add_field( 'bp-member-type-enable-disable', __( 'Profile Types', 'buddyboss' ), [$this, 'bp_admin_setting_callback_member_type_enable_disable'], 'intval' );

		// Enable/Disable Display on profiles.
		$this->add_field( 'bp-member-type-display-on-profile', __( 'Display on Profiles', 'buddyboss' ), [$this, 'bp_admin_setting_callback_member_type_display_on_profile'], 'intval' );

		// Profile types import.
		$this->add_field( 'bp-member-type-import', __( 'Import Profile Types', 'buddyboss' ), [$this, 'bp_admin_setting_callback_member_type_import'], 'intval' );
	}

	/**
	 * Enable profile dashboard template.
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 */
	public function bp_admin_setting_callback_member_dashboard() {
		?>
			<input id="bp-enable-member-dashboard" name="bp-enable-member-dashboard" type="checkbox" value="1" <?php checked( bp_nouveau_get_appearance_settings( 'user_front_page' ) ); ?> />
			<label for="bp-enable-member-dashboard"><?php _e( 'Use a WordPress page as each user\'s personal Profile Dashboard', 'buddyboss' ); ?></label>
			<p class="description"><?php _e( 'This page is only accessible to logged-in users. Set this page via Dashboard->BuddyBoss->Pages', 'buddyboss' ); ?></p>
		<?php
	}

	/**
	 * Enable profile dashboard template.
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 */
	public function bp_admin_setting_callback_member_dashboard_redirect() {
		?>
		<input id="bp-enable-member-dashboard-redirect" name="bp-enable-member-dashboard-redirect" type="checkbox" value="1" <?php checked( bp_nouveau_get_appearance_settings( 'user_front_page_redirect' ) ); ?> />
		<label for="bp-enable-member-dashboard-redirect"><?php _e( 'Redirect users to their Profile Dashboard on login', 'buddyboss' ); ?></label>
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
				__( 'After the format has been updated, remember to run the <a href="%s">Repair Tools</a> to update all the users.', 'buddyboss' ),
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
	 * @since BuddyBoss 1.0.0
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
	 * @since BuddyBoss 1.0.0
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
	 * Enable profile types.
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 */
	public function bp_admin_setting_callback_member_type_enable_disable() {
		?>
		<input id="bp-member-type-enable-disable" name="bp-member-type-enable-disable" type="checkbox" value="1" <?php checked( bp_member_type_enable_disable() ); ?> />
		<label for="bp-member-type-enable-disable"><?php _e( 'Enable profile types to give members unique profile fields and permissions', 'buddyboss' ); ?></label>
		<?php
	}

	/**
	 * Enable display of profile type on member profile page.
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 */
	public function bp_admin_setting_callback_member_type_display_on_profile() {
		?>
		<input id="bp-member-type-display-on-profile" name="bp-member-type-display-on-profile" type="checkbox" value="1" <?php checked( bp_member_type_display_on_profile() ); ?> />
		<label for="bp-member-type-display-on-profile"><?php _e( 'Display member profile type on their profile page', 'buddyboss' ); ?></label>
		<?php
	}
}

return new BP_Admin_Setting_Xprofile;
