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

	/**
	 * Initialize class.
	 */
	public function initialize() {
		$this->tab_label = __( 'Profiles', 'buddyboss' );
		$this->tab_name  = 'bp-xprofile';
		$this->tab_order = 10;

		$this->active_tab = bp_core_get_admin_active_tab();

		// Profile Avatar.
		add_filter( 'bp_attachment_avatar_script_data', 'bb_admin_setting_profile_group_add_script_data', 10, 2 );

		// Profile Cover.
		add_filter( 'bp_attachments_cover_image_upload_dir', 'bb_default_custom_profile_group_cover_image_upload_dir', 10, 1 );
		add_filter( 'bb_attachments_get_attachment_dir', 'bb_attachments_get_profile_group_attachment_dir', 10, 4 );
		add_filter( 'bb_attachments_get_attachment_sub_dir', 'bb_attachments_get_profile_group_attachment_sub_dir', 10, 4 );
	}

	/**
	 * Save options.
	 */
	public function settings_save() {
		$if_disabled_before_saving                 = bp_disable_advanced_profile_search();
		$profile_avatar_type_before_saving         = bb_get_profile_avatar_type();
		$bp_disable_avatar_uploads_before_saving   = bp_disable_avatar_uploads();
		$default_profile_avatar_type_before_saving = bb_get_default_profile_avatar_type();
		$bp_enable_profile_gravatar_before_saving  = bp_enable_profile_gravatar();
		$profile_cover_type_before_saving          = bb_get_default_profile_cover_type();
		$profile_slug_format_before_saving         = bb_get_profile_slug_format();

		parent::settings_save();

		$if_disabled_after_saving                 = bp_disable_advanced_profile_search();
		$profile_avatar_type_after_saving         = bb_get_profile_avatar_type();
		$bp_disable_avatar_uploads_after_saving   = bp_disable_avatar_uploads();
		$default_profile_avatar_type_after_saving = bb_get_default_profile_avatar_type();
		$bp_enable_profile_gravatar_after_saving  = bp_enable_profile_gravatar();
		$profile_cover_type_after_saving          = bb_get_default_profile_cover_type();
		$profile_slug_format_after_saving         = bb_get_profile_slug_format();

		/**
		 * Sync bp-enable-member-dashboard with customizer settings.
		 *
		 * @since BuddyBoss 1.0.0
		 */
		$bp_nouveau_appearance                             = bp_get_option( 'bp_nouveau_appearance', array() );
		$bp_nouveau_appearance['user_front_page']          = filter_input( INPUT_POST, 'bp-enable-member-dashboard', FILTER_VALIDATE_INT );
		$bp_nouveau_appearance['user_front_page_redirect'] = filter_input( INPUT_POST, 'bp-enable-member-dashboard-redirect', FILTER_VALIDATE_INT );

		bp_update_option( 'bp_nouveau_appearance', $bp_nouveau_appearance );

		$bb_display_name_format = bb_filter_input_string( INPUT_POST, 'bp-display-name-format' );

		// Set requirement for last name based on display format.
		if ( isset( $bb_display_name_format ) ) {
			if (
				'first_last_name' === $bb_display_name_format ||
				'first_name' === $bb_display_name_format
			) {
				$firstname_field_id = bp_xprofile_firstname_field_id();
				bp_xprofile_update_field_meta( $firstname_field_id, 'default_visibility', 'public' );
				bp_xprofile_update_field_meta( $firstname_field_id, 'allow_custom_visibility', 'disabled' );

				// Make the first name field to required if not in required list.
				$field              = xprofile_get_field( $firstname_field_id );
				$field->is_required = true;
				$field->save();

				if ( 'first_last_name' === $bb_display_name_format ) {
					$lastname_field_id = bp_xprofile_lastname_field_id();
					bp_xprofile_update_field_meta( $lastname_field_id, 'default_visibility', 'public' );
				}
			} elseif ( 'nickname' === $bb_display_name_format ) {
				$nickname_field_id = bp_xprofile_nickname_field_id();
				bp_xprofile_update_field_meta( $nickname_field_id, 'default_visibility', 'public' );
				bp_xprofile_update_field_meta( $nickname_field_id, 'allow_custom_visibility', 'disabled' );
			}
		}

		$bb_profile_avatar_type           = bb_filter_input_string( INPUT_POST, 'bp-profile-avatar-type' );
		$bb_default_custom_profile_avatar = bb_filter_input_string( INPUT_POST, 'bp-default-custom-profile-avatar' );
		$bb_default_custom_profile_cover  = bb_filter_input_string( INPUT_POST, 'bp-default-custom-profile-cover' );

		/**
		 * Enable Gravatar's set disable if Profile Avatars is WordPress.
		 *
		 * @since BuddyBoss 1.8.6
		 */
		if ( isset( $bb_profile_avatar_type ) && 'WordPress' === sanitize_text_field( $bb_profile_avatar_type ) ) {
			bp_update_option( 'bp-enable-profile-gravatar', '' );
		}

		/**
		 * Validate custom option for profile avatar and cover.
		 *
		 * @since BuddyBoss 1.8.6
		 */
		if ( ! isset( $bb_default_custom_profile_avatar ) || ( empty( $bb_default_custom_profile_avatar ) && 'custom' === $default_profile_avatar_type_after_saving ) ) {

			$bp_disable_avatar_uploads_before_saving  = $bp_disable_avatar_uploads_after_saving;
			$bp_enable_profile_gravatar_before_saving = $bp_enable_profile_gravatar_after_saving;

			if ( 'WordPress' === $profile_avatar_type_before_saving ) {
				$bp_disable_avatar_uploads_before_saving   = '1';
				$default_profile_avatar_type_before_saving = 'buddyboss';
				$bp_enable_profile_gravatar_before_saving  = '';
			} elseif ( 'custom' === $default_profile_avatar_type_before_saving ) {
				$default_profile_avatar_type_before_saving = 'buddyboss';
			}

			bp_update_option( 'bp-profile-avatar-type', $profile_avatar_type_before_saving );
			bp_update_option( 'bp-disable-avatar-uploads', $bp_disable_avatar_uploads_before_saving );
			bp_update_option( 'bp-default-profile-avatar-type', $default_profile_avatar_type_before_saving );
			bp_update_option( 'bp-enable-profile-gravatar', $bp_enable_profile_gravatar_before_saving );
		}

		if ( ! isset( $bb_default_custom_profile_cover ) || ( isset( $bb_default_custom_profile_cover ) && empty( $bb_default_custom_profile_cover ) && 'custom' === $profile_cover_type_after_saving ) ) {

			if ( 'custom' === $profile_cover_type_before_saving ) {
				$profile_cover_type_before_saving = 'buddyboss';
			}

			bp_update_option( 'bp-default-profile-cover-type', $profile_cover_type_before_saving );
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
			wp_safe_redirect( bp_get_admin_url( 'admin.php?page=bp-settings&tab=bp-xprofile&updated=true' ) );
			exit();
		}

		if ( $profile_slug_format_before_saving !== $profile_slug_format_after_saving ) {
			wp_cache_flush();

			// Purge all the cache for API.
			if ( class_exists( 'BuddyBoss\Performance\Cache' ) ) {
				BuddyBoss\Performance\Cache::instance()->purge_all();
			}
		}

	}

	/**
	 * Register setting fields.
	 */
	public function register_fields() {

		$pro_class = bb_get_pro_fields_class();

		// Section for Profile Names.
		$this->add_section( 'bp_xprofile', __( 'Profile Names', 'buddyboss' ), '', 'bp_profile_names_tutorial' );

		// Display name format.
		$this->add_field(
			'bp-display-name-format',
			__( 'Display Name Format', 'buddyboss' ),
			array( $this, 'callback_display_name_format' )
		);

		// Hide Last Name.
		$args          = array();
		$args['class'] = 'first-name-options display-options';
		$this->add_field( 'bp-hide-last-name', __( 'Display Name Fields', 'buddyboss' ), 'bp_admin_setting_display_name_first_name', 'intval', $args );

		// Hide Nothing.
		$args          = array();
		$args['class'] = 'first-last-name-options display-options';
		$this->add_field( 'bp-hide-nothing', __( 'Display Name Fields', 'buddyboss' ), 'bp_admin_setting_display_name_first_last_name', 'intval', $args );

		// Hide First Name.
		$args          = array();
		$args['class'] = 'nick-name-options display-options';
		$this->add_field( 'bp-hide-nickname-first-name', __( 'Display Name Fields', 'buddyboss' ), 'bp_admin_setting_callback_nickname_hide_first_name', 'intval', $args );

		// Hide Last Name.
		$args          = array();
		$args['class'] = 'nick-name-options display-options';
		$this->add_field( 'bp-hide-nickname-last-name', '', 'bp_admin_setting_callback_nickname_hide_last_name', 'intval', $args );

		// Section for Profile Links.
		$this->add_section( 'bb_profile_slug_settings', __( 'Profile Links', 'buddyboss' ), '', 'bb_profile_slug_tutorial' );

		// Display name format.
		$this->add_field(
			'bb_profile_slug_format',
			__( 'Link Format', 'buddyboss' ),
			array( $this, 'bb_profile_slug_format_callback' )
		);

		// Profile Avatar.
		$avatar_type         = bb_get_profile_avatar_type();
		$default_avatar_type = bb_get_default_profile_avatar_type();

		// Profile Cover.
		$is_disabled_cover  = bp_disable_cover_image_uploads();
		$default_cover_type = bb_get_default_profile_cover_type();

		// Section for Profile Photos.
		$this->add_section( 'bp_member_avatar_settings', __( 'Profile Images', 'buddyboss' ), '', 'bp_profile_photos_tutorial' );

		// Profile Avatar type.
		$args          = array();
		$args['class'] = 'profile-avatars-field';
		$this->add_field( 'bp-profile-avatar-type', esc_html__( 'Profile Avatars', 'buddyboss' ), 'bp_admin_setting_callback_profile_avatar_type', 'string', $args );

		// Avatars.
		$args          = array();
		$args['class'] = 'upload-avatars-field';
		$this->add_field( 'bp-disable-avatar-uploads', esc_html__( 'Upload Avatars', 'buddyboss' ), 'bp_admin_setting_callback_avatar_uploads', 'intval', $args );

		$args          = array();
		$args['class'] = 'profile-avatar-options avatar-options default-profile-avatar-type' . ( 'WordPress' === $avatar_type ? ' bp-hide' : '' );
		$this->add_field( 'bp-default-profile-avatar-type', esc_html__( 'Default Profile Avatar', 'buddyboss' ), 'bp_admin_setting_callback_default_profile_avatar_type', 'string', $args );

		$args          = array();
		$args['class'] = 'profile-avatar-options avatar-options default-profile-avatar-custom' . ( 'BuddyBoss' === $avatar_type && 'custom' === $default_avatar_type ? '' : ' bp-hide' );
		$this->add_field( 'bp-default-custom-profile-avatar', esc_html__( 'Upload Custom Avatar', 'buddyboss' ), 'bp_admin_setting_callback_default_profile_custom_avatar', 'string', $args );

		// Gravatars.
		$args          = array();
		$args['class'] = 'enable-profile-gravatar-field' . ( 'WordPress' === $avatar_type ? ' bp-hide' : '' );
		$this->add_field( 'bp-enable-profile-gravatar', esc_html__( 'Enable Gravatars', 'buddyboss' ), 'bp_admin_setting_callback_enable_profile_gravatar', 'intval', $args );

		// cover photos.
		if ( bp_is_active( 'xprofile', 'cover_image' ) ) {
			$this->add_field( 'bp-disable-cover-image-uploads', esc_html__( 'Profile Cover Images', 'buddyboss' ), 'bp_admin_setting_callback_cover_image_uploads', 'string' );

			$args          = array();
			$args['class'] = 'profile-cover-options avatar-options default-profile-cover-type' . ( $is_disabled_cover ? ' bp-hide' : '' );
			$this->add_field( 'bp-default-profile-cover-type', esc_html__( 'Default Profile Cover Image', 'buddyboss' ), 'bp_admin_setting_callback_default_profile_cover_type', 'string', $args );

			$args          = array();
			$args['class'] = 'profile-cover-options avatar-options default-profile-cover-custom' . ( ( ! $is_disabled_cover && 'custom' === $default_cover_type ) ? '' : ' bp-hide' );
			$this->add_field( 'bp-default-custom-profile-cover', esc_html__( 'Upload Custom Cover Image', 'buddyboss' ), 'bp_admin_setting_callback_default_profile_custom_cover', 'string', $args );

			$args          = array();
			$args['class'] = 'profile-cover-options avatar-options ' . esc_attr( $pro_class ) . ( $is_disabled_cover ? ' bp-hide' : '' );
			$this->add_field( 'bb-default-profile-cover-size', esc_html__( 'Cover Image Sizes', 'buddyboss' ) . bb_get_pro_label_notice(), 'bb_admin_setting_callback_default_profile_cover_size', 'string', $args );

			$args          = array();
			$args['class'] = 'profile-cover-options preview-avatar-cover-image' . ( $is_disabled_cover ? ' bp-hide' : '' );
			$this->add_field( 'bp-preview-profile-avatar-cover', esc_html__( 'Preview Cover Image', 'buddyboss' ), 'bp_admin_setting_callback_preview_profile_avatar_cover', 'string', $args );
		}

		// Section for Profile Headers.
		$this->add_section( 'bp_profile_headers_settings', esc_html__( 'Profile Headers', 'buddyboss' ), '', 'bb_profile_headers_tutorial' );

		// Profile headers style.
		$args          = array();
		$args['class'] = 'profile-header-style profile-header-layout-options ' . esc_attr( $pro_class );
		$this->add_field( 'bb-profile-headers-layout-style', esc_html__( 'Header Style', 'buddyboss' ) . bb_get_pro_label_notice(), 'bb_admin_setting_profile_headers_style', 'radio', $args );

		// Profile elements.
		$args             = array();
		$args['class']    = 'profile-header-elements ' . esc_attr( $pro_class );
		$args['elements'] = function_exists( 'bb_get_profile_header_elements' ) ? bb_get_profile_header_elements() : array();
		$this->add_field( 'bb-profile-headers-layout-elements', esc_html__( 'Elements', 'buddyboss' ) . bb_get_pro_label_notice(), 'bb_admin_setting_profile_header_elements', 'checkbox', $args );

		// @todo will use this later on
		// Section for profile dashboard.
		// $this->add_section( 'bp_profile_dashboard_settings', __( 'Profile Dashboard', 'buddyboss' ) );

		// @todo will use this later on
		// Enable/Disable profile dashboard.
		// $this->add_field( 'bp-enable-member-dashboard', __( 'Profile Dashboard', 'buddyboss' ), [$this, 'bp_admin_setting_callback_member_dashboard'], 'intval' );

		// @todo will use this later on
		// $this->add_field( 'bp-enable-member-dashboard-redirect', __( 'Redirect on Login', 'buddyboss' ), [$this, 'bp_admin_setting_callback_member_dashboard_redirect'], 'intval' );

		// Section for profile list.
		$this->add_section( 'bp_profile_list_settings', esc_html__( 'Member Directories', 'buddyboss' ), '', array( $this, 'bp_profile_directories_tutorial' ) );

		// Admin Settings for Settings > Profile > Profile Directories > Enabled Views.
		$this->add_field(
			'bp-profile-layout-format',
			esc_html__( 'Enabled View(s)', 'buddyboss' ),
			array( $this, 'bp_admin_setting_profile_layout_type_format' )
		);

		// Admin Settings for Settings > Profiles > Profile Directories > Default View.
		$args          = array();
		$args['class'] = 'profile-default-layout profile-layout-options';
		$this->add_field( 'bp-profile-layout-default-format', esc_html__( 'Default View', 'buddyboss' ), array( $this, 'bp_admin_setting_profile_layout_default_option' ), 'radio', $args );

		// Member directory elements.
		$args             = array();
		$args['class']    = 'member-directory-elements ' . esc_attr( $pro_class );
		$args['elements'] = function_exists( 'bb_get_member_directory_elements' ) ? bb_get_member_directory_elements() : array();
		$this->add_field( 'bb-member-directory-elements', esc_html__( 'Elements', 'buddyboss' ) . bb_get_pro_label_notice(), 'bb_admin_setting_member_directory_elements', 'string', $args );

		// Member profile actions.
		$profile_actions          = function_exists( 'bb_get_member_directory_profile_actions' ) ? bb_get_member_directory_profile_actions() : array();
		$selected_profile_actions = function_exists( 'bb_get_enabled_member_directory_profile_actions' ) ? bb_get_enabled_member_directory_profile_actions() : array();

		if ( ! empty( $profile_actions ) ) {
			$args             = array();
			$args['class']    = 'member-directory-profile-actions ' . esc_attr( $pro_class );
			$args['elements'] = $profile_actions;
			$this->add_field( 'bb-member-profile-actions', esc_html__( 'Profile Actions', 'buddyboss' ) . bb_get_pro_label_notice(), 'bb_admin_setting_member_profile_actions', 'string', $args );

			$profile_primary_action_class = '';
			if ( empty( $selected_profile_actions ) ) {
				$profile_primary_action_class = ' bp-hide';
			}

			// Member profile primary action.
			$args                      = array();
			$args['class']             = 'member-directory-profile-primary-action ' . esc_attr( $pro_class ) . $profile_primary_action_class;
			$args['elements']          = $profile_actions;
			$args['selected_elements'] = $selected_profile_actions;
			$this->add_field( 'bb-member-profile-primary-action', esc_html__( 'Primary Action', 'buddyboss' ) . bb_get_pro_label_notice(), 'bb_admin_setting_member_profile_primary_action', 'string', $args );
		}

		// Section for profile types.
		$this->add_section( 'bp_member_type_settings', __( 'Profile Types', 'buddyboss' ), '', array( $this, 'bp_profile_types_tutorial' ) );

		// Enable/Disable profile types.
		$this->add_field( 'bp-member-type-enable-disable', __( 'Profile Types', 'buddyboss' ), array( $this, 'bp_admin_setting_callback_member_type_enable_disable' ), 'intval' );

		// Profile Type enabled then display profile types.
		if ( true === bp_member_type_enable_disable() ) {
			// Enable/Disable Display on profiles.
			$this->add_field( 'bp-member-type-display-on-profile', __( 'Display Profile Types', 'buddyboss' ), array( $this, 'bp_admin_setting_callback_member_type_display_on_profile' ), 'intval' );
		}

		// Default profile type on registration.
		if ( true === bp_member_type_enable_disable() ) {
			$this->add_field( 'bp-member-type-default-on-registration', __( 'Default Profile Type', 'buddyboss' ), array( $this, 'bp_admin_setting_callback_member_type_default_on_registration' ) );
		}

		// Section for profile search.
		$this->add_section( 'bp_profile_search_settings', __( 'Profile Search', 'buddyboss' ), '', array( $this, 'bp_profile_search_tutorial' ) );

		// Enable/Disable profile search.
		$this->add_field( 'bp-enable-profile-search', __( 'Profile Search', 'buddyboss' ), array( $this, 'bp_admin_setting_callback_profile_search' ), 'intval' );

		/**
		 * Fires to register xProfile tab settings fields and section.
		 *
		 * @since BuddyBoss 1.2.6
		 *
		 * @param Object $this BP_Admin_Setting_Xprofile.
		 */
		do_action( 'bp_admin_setting_xprofile_register_fields', $this );
	}

	/**
	 * Enable profile dashboard template.
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function bp_admin_setting_callback_member_dashboard() {
		?>
			<input id="bp-enable-member-dashboard" name="bp-enable-member-dashboard" type="checkbox" value="1" <?php checked( bp_nouveau_get_appearance_settings( 'user_front_page' ) ); ?> />
			<label for="bp-enable-member-dashboard"><?php esc_html_e( 'Use a WordPress page as each user\'s personal Profile Dashboard', 'buddyboss' ); ?></label>
		<?php
			printf(
				'<p class="description">%s</p>',
				sprintf(
					__( 'This page is only accessible to logged-in users. Create a WordPress page and assign it in the <a href="%s">Pages</a> settings.', 'buddyboss' ),
					add_query_arg(
						array(
							'page' => 'bp-pages',
						),
						admin_url( 'admin.php' )
					)
				)
			);
	}

	/**
	 * Enable profile dashboard template.
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function bp_admin_setting_callback_member_dashboard_redirect() {
		?>
		<input id="bp-enable-member-dashboard-redirect" name="bp-enable-member-dashboard-redirect" type="checkbox" value="1" <?php checked( bp_nouveau_get_appearance_settings( 'user_front_page_redirect' ) ); ?> />
		<label for="bp-enable-member-dashboard-redirect"><?php esc_html_e( 'Redirect users to their Profile Dashboard on login', 'buddyboss' ); ?></label>
		<?php
	}

	/**
	 * Display name format.
	 */
	public function callback_display_name_format() {
		$options = array(
			'first_name'      => __( 'First Name', 'buddyboss' ),
			'first_last_name' => __( 'First Name &amp; Last Name', 'buddyboss' ),
			'nickname'        => __( 'Nickname', 'buddyboss' ),
		);

		$current_value = bp_core_display_name_format();

		printf( '<select name="%1$s" for="%1$s">', 'bp-display-name-format' );
		foreach ( $options as $key => $value ) {
			printf(
				'<option value="%s" %s>%s</option>',
				$key,
				$key == $current_value ? 'selected' : '',
				$value
			);
		}
		printf( '</select>' );

		printf(
			'<p class="description">%s</p>',
			sprintf(
				__( 'After the format has been updated, remember to run <a href="%s">Repair Community</a> tools to update all the users.', 'buddyboss' ),
				add_query_arg(
					array(
						'page' => 'bp-repair-community',
						'tab'  => 'bp-repair-community',
						'tool' => 'bp-wordpress-update-display-name',
					),
					admin_url( 'admin.php' )
				)
			)
		);
	}

	/**
	 * Enable profile types.
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function bp_admin_setting_callback_member_type_enable_disable() {
		?>
		<input id="bp-member-type-enable-disable" name="bp-member-type-enable-disable" type="checkbox" value="1" <?php checked( bp_member_type_enable_disable() ); ?> />
		<?php
		if ( true === bp_member_type_enable_disable() ) {
			printf(
				'<label for="bp-member-type-enable-disable">%s</label>',
				sprintf(
					__( 'Enable <a href="%s">profile types</a> to give members unique profile fields and permissions', 'buddyboss' ),
					add_query_arg(
						array(
							'post_type' => bp_get_member_type_post_type(),
						),
						admin_url( 'edit.php' )
					)
				)
			);
		} else {
			?>
				<label for="bp-member-type-enable-disable"><?php esc_html_e( 'Enable profile types to give members unique profile fields and permissions', 'buddyboss' ); ?></label>
				<?php
		}
	}

	/**
	 * Enable display of profile type on member profile page.
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function bp_admin_setting_callback_member_type_display_on_profile() {
		?>
		<input id="bp-member-type-display-on-profile" name="bp-member-type-display-on-profile" type="checkbox" value="1" <?php checked( bp_member_type_display_on_profile() ); ?> />
		<label for="bp-member-type-display-on-profile"><?php esc_html_e( 'Display each member\'s profile type on their profile page', 'buddyboss' ); ?></label>
		<?php
	}

	/**
	 * Default profile type on registration.
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function bp_admin_setting_callback_member_type_default_on_registration() {

		$member_types      = bp_get_active_member_types();
		$existing_selected = bp_member_type_default_on_registration();

		if ( empty( $member_types ) ) {
			printf(
				'<p class="description">%s</p>',
				sprintf(
					__(
						'You first need to create some <a href="%s">Profile Types</a>.',
						'buddyboss'
					),
					add_query_arg(
						array(
							'post_type' => bp_get_member_type_post_type(),
						),
						admin_url( 'edit.php' )
					)
				)
			);
		} else {
			?>
			<select name="bp-member-type-default-on-registration" id="bp-member-type-default-on-registration">
				<option value=""><?php esc_html_e( '----', 'buddyboss' ); ?></option>
					<?php
					foreach ( $member_types as $member_type_id ) {
						$type_name = bp_get_member_type_key( $member_type_id );
						// $type_id = bp_member_type_term_taxonomy_id( $type_name );
						$member_type_name = get_post_meta( $member_type_id, '_bp_member_type_label_name', true );
						// if ( ! empty( $type_id ) ) {
						?>
						<option
								<?php
								selected(
									$existing_selected,
									$type_name
								);
								?>
							 value="<?php echo $type_name; ?>">
								<?php printf( esc_html__( '%s', 'buddyboss' ), $member_type_name ); ?>
							</option>
						<?php
					}
					?>
			</select>
			<?php
			printf(
				'<p class="description">%s</p>',
				sprintf(
					__(
						'Select a default profile type to be auto-assigned to users during registration. After the profile type has been selected, you can run <a href="%s">Repair Community</a> tools to assign the profile type to existing users.',
						'buddyboss'
					),
					add_query_arg(
						array(
							'page' => 'bp-tools',
							'tab'  => 'bp-tools',
							'tool' => 'bp-assign-member-type',
						),
						admin_url( 'admin.php' )
					)
				)
			);
		}
	}

	/**
	 * Link to Profile Types tutorial
	 *
	 * @since BuddyBoss 1.0.0
	 */
	function bp_profile_types_tutorial() {
		?>

		<p>
			<a class="button" href="
			<?php
			echo bp_get_admin_url(
				add_query_arg(
					array(
						'page'    => 'bp-help',
						'article' => 62802,
					),
					'admin.php'
				)
			);
			?>
			"><?php esc_html_e( 'View Tutorial', 'buddyboss' ); ?></a>
		</p>

		<?php
	}

	/**
	 * Enable member profile search.
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function bp_admin_setting_callback_profile_search() {
		?>
			<input id="bp-enable-profile-search" name="bp-enable-profile-search" type="checkbox" value="1" <?php checked( ! bp_disable_advanced_profile_search() ); ?> />
			<?php
			if ( false === bp_disable_advanced_profile_search() ) {
				printf(
					'<label for="bp-enable-profile-search">%s</label>',
					sprintf(
						__( 'Enable <a href="%s">advanced profile search</a> on the members directory.', 'buddyboss' ),
						add_query_arg(
							array(
								'post_type' => 'bp_ps_form',
							),
							admin_url( 'edit.php' )
						)
					)
				);
			} else {
				?>
				<label for="bp-enable-profile-search"><?php esc_html_e( 'Enable advanced profile search on the members directory', 'buddyboss' ); ?></label>
				<?php
			}
	}

	/**
	 * Link to Profile Search tutorial
	 *
	 * @since BuddyBoss 1.0.0
	 */
	function bp_profile_search_tutorial() {
		?>

		<p>
			<a class="button" href="
			<?php
			echo bp_get_admin_url(
				add_query_arg(
					array(
						'page'    => 'bp-help',
						'article' => 62803,
					),
					'admin.php'
				)
			);
			?>
			"><?php esc_html_e( 'View Tutorial', 'buddyboss' ); ?></a>
		</p>

		<?php
	}

	/**
	 * Admin Settings for Settings > Profiles > Profile Directories > Default Format
	 *
	 * @since BuddyBoss 1.2.0
	 */
	public function bp_admin_setting_profile_layout_type_format() {
		$options = array(
			'list_grid' => __( 'Grid and List', 'buddyboss' ),
			'grid'      => __( 'Grid', 'buddyboss' ),
			'list'      => __( 'List', 'buddyboss' ),
		);

		$current_value = bp_get_option( 'bp-profile-layout-format' );

		printf( '<select name="%1$s" for="%1$s">', 'bp-profile-layout-format' );
		foreach ( $options as $key => $value ) {
			printf(
				'<option value="%s" %s>%s</option>',
				$key,
				$key == $current_value ? 'selected' : '',
				$value
			);
		}
		printf( '</select>' );

		?>
		<p class="description"><?php esc_html_e( 'Display member directories in grid view, list view, or allow toggling between both views.', 'buddyboss' ); ?></p>
		<?php
	}

	/**
	 * Admin Settings for Settings > Profiles > Profile Directories > Default Format
	 *
	 * @since BuddyBoss 1.2.0
	 */
	public function bp_admin_setting_profile_layout_default_option() {
		$selected = bp_profile_layout_default_format( 'grid' );

		$options = array(
			'grid' => __( 'Grid', 'buddyboss' ),
			'list' => __( 'List', 'buddyboss' ),
		);

		printf( '<select name="%1$s" for="%1$s">', 'bp-profile-layout-default-format' );
		foreach ( $options as $key => $value ) {
			printf(
				'<option value="%s" %s>%s</option>',
				$key,
				$key == $selected ? 'selected' : '',
				$value
			);
		}
		printf( '</select>' );
	}

	/**
	 * Link to Profile Directories tutorial
	 *
	 * @since BuddyBoss 1.2.0
	 */
	public function bp_profile_directories_tutorial() {
		?>

		<p>
			<a class="button" href="
			<?php
			echo bp_get_admin_url(
				add_query_arg(
					array(
						'page'    => 'bp-help',
						'article' => '125308',
					),
					'admin.php'
				)
			);
			?>
			"><?php esc_html_e( 'View Tutorial', 'buddyboss' ); ?></a>
		</p>

		<?php
	}

	/**
	 * Setup default custom avatar upload directory.
	 *
	 * @since BuddyBoss 1.8.6
	 *
	 * @param array $upload_dir The original Uploads dir.
	 * @return array Array containing the path, URL, and other helpful settings.
	 */
	public function bb_xprofile_default_custom_profile_avatar_upload_dir( $upload_dir = array() ) {
		$bp_params = array();

		if ( isset( $_POST['bp_params'] ) && ! empty( $_POST['bp_params'] ) ) {
			$bp_params = array_map( 'sanitize_text_field', $_POST['bp_params'] );
		}

		if ( ! is_admin() || empty( $bp_params ) || ! isset( $bp_params['object'] ) || ! isset( $bp_params['item_id'] ) ) {
			return $upload_dir;
		}

		$item_id = $bp_params['item_id'];
		$object  = $bp_params['object'];

		if ( ! is_admin() || ( 0 < $item_id && 'user' === $object ) || ( 'user' !== $object ) ) {
			return $upload_dir;
		}

		$directory = 'avatars';

		$path      = bp_core_avatar_upload_path() . '/' . $directory . '/custom';
		$newbdir   = $path;
		$newurl    = bp_core_avatar_url() . '/' . $directory . '/custom';
		$newburl   = $newurl;
		$newsubdir = '/' . $directory . '/custom';

		/**
		 * Filters default custom avatar upload directory.
		 *
		 * @since BuddyBoss 1.8.6
		 *
		 * @param array $value Array containing the path, URL, and other helpful settings.
		 */
		return apply_filters(
			'bb_xprofile_default_custom_profile_avatar_upload_dir',
			array(
				'path'    => $path,
				'url'     => $newurl,
				'subdir'  => $newsubdir,
				'basedir' => $newbdir,
				'baseurl' => $newburl,
				'error'   => false,
			),
			$upload_dir
		);
	}

	/**
	 * Display profile slug format.
	 *
	 * @since BuddyBoss 2.3.1
	 */
	public function bb_profile_slug_format_callback() {
		$options = array(
			'username'          => esc_html__( 'Username', 'buddyboss' ),
			'unique_identifier' => esc_html__( 'Unique Identifier', 'buddyboss' ),
		);

		$current_value = bb_get_profile_slug_format();

		printf( '<select name="%1$s" for="%2$s">', 'bb_profile_slug_format', 'bb_profile_slug_format' );
		foreach ( $options as $key => $value ) {
			printf(
				'<option value="%1$s" %2$s>%3$s</option>',
				esc_attr( $key ),
				selected( $key === $current_value, true, false ),
				esc_attr( $value )
			);
		}
		printf( '</select>' );

		printf(
			'<p class="description">%s</p>',
			esc_html__( 'Select the format of your member’s profile links (i.e. /members/username). Both formats will open the member’s profile, so you can safely change without breaking previously shared links.', 'buddyboss' )
		);
	}
}

return new BP_Admin_Setting_Xprofile();
