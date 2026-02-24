<?php
/**
 * Add admin Social Groups settings page in Dashboard->BuddyBoss->Settings
 *
 * @package BuddyBoss\Core
 *
 * @since BuddyBoss 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Main Social Groups Settings class.
 *
 * @since BuddyBoss 1.0.0
 */
class BP_Admin_Setting_Groups extends BP_Admin_Setting_tab {

	/**
	 * Initialize class.
	 */
	public function initialize() {
		$this->tab_label = __( 'Groups', 'buddyboss' );
		$this->tab_name  = 'bp-groups';
		$this->tab_order = 20;

		$this->active_tab = bp_core_get_admin_active_tab();

		// Group Avatar.
		add_filter( 'bp_attachment_avatar_script_data', 'bb_admin_setting_profile_group_add_script_data', 10, 2 );

		// Group Cover.
		add_filter( 'bp_attachments_cover_image_upload_dir', 'bb_default_custom_profile_group_cover_image_upload_dir', 10, 1 );
		add_filter( 'bb_attachments_get_attachment_dir', 'bb_attachments_get_profile_group_attachment_dir', 10, 4 );
		add_filter( 'bb_attachments_get_attachment_sub_dir', 'bb_attachments_get_profile_group_attachment_sub_dir', 10, 4 );
	}

	/**
	 * Save options.
	 */
	public function settings_save() {
		$group_avatar_type_before_saving      = bb_get_default_group_avatar_type();
		$group_cover_type_before_saving       = bb_get_default_group_cover_type();
		$group_restrict_invites_before_saving = bp_enable_group_restrict_invites();

		parent::settings_save();

		$group_avatar_type_after_saving = bb_get_default_group_avatar_type();
		$group_cover_type_after_saving  = bb_get_default_group_cover_type();
		$bb_default_custom_group_avatar = bb_filter_input_string( INPUT_POST, 'bp-default-custom-group-avatar' );
		$bb_default_custom_group_cover  = bb_filter_input_string( INPUT_POST, 'bp-default-custom-group-cover' );

		/**
		 * Validate custom option for group avatar and cover.
		 *
		 * @since BuddyBoss 1.8.6
		 */
		if ( ! isset( $bb_default_custom_group_avatar ) || ( isset( $bb_default_custom_group_avatar ) && empty( $bb_default_custom_group_avatar ) && 'custom' === $group_avatar_type_after_saving ) ) {

			if ( 'custom' === $group_avatar_type_before_saving ) {
				$group_avatar_type_before_saving = 'buddyboss';
			}

			bp_update_option( 'bp-default-group-avatar-type', $group_avatar_type_before_saving );
		}

		if ( 'group-name' === $group_avatar_type_after_saving && empty( _wp_image_editor_choose() ) ) {

			if ( 'group-name' === $group_avatar_type_before_saving ) {
				$group_avatar_type_before_saving = 'buddyboss';
			}

			bp_update_option( 'bp-default-group-avatar-type', $group_avatar_type_before_saving );
		}

		if ( ! isset( $bb_default_custom_group_cover ) || ( isset( $bb_default_custom_group_cover ) && empty( $bb_default_custom_group_cover ) && 'custom' === $group_cover_type_after_saving ) ) {

			if ( 'custom' === $group_cover_type_before_saving ) {
				$group_cover_type_before_saving = 'buddyboss';
			}

			bp_update_option( 'bp-default-group-cover-type', $group_cover_type_before_saving );
		}

		/**
		 * Migrate the subgroups members if group restrict invites is enabled and member is not part of parent group.
		 *
		 * @since BuddyBoss 2.4.60
		 */
		if (
			true === bp_enable_group_hierarchies() &&
			empty( $group_restrict_invites_before_saving ) &&
			true === (bool) bp_enable_group_restrict_invites()
		) {
			bb_groups_migrate_subgroup_member();
		}
	}

	/**
	 * Check if groups are enabled.
	 *
	 * @return bool
	 */
	public function is_active() {
		return bp_is_active( 'groups' );
	}

	/**
	 * Register setting fields.
	 */
	public function register_fields() {

		/**
		 * Fires to register Groups tab settings fields and section.
		 *
		 * @since BuddyBoss 1.2.6
		 *
		 * @param Object $this BP_Admin_Setting_Groups.
		 */
		do_action( 'bp_admin_setting_groups_register_fields', $this );
	}

	/**
	 * Setup default custom avatar upload directory.
	 *
	 * @since BuddyBoss 1.8.6
	 *
	 * @param array $upload_dir The original Uploads dir.
	 * @return array Array containing the path, URL, and other helpful settings.
	 */
	public function bb_group_default_custom_group_avatar_upload_dir( $upload_dir = array() ) {
		$bp_params = array();

		if ( isset( $_POST['bp_params'] ) && ! empty( $_POST['bp_params'] ) ) {
			$bp_params = array_map( 'sanitize_text_field', $_POST['bp_params'] );
		}

		if ( ! is_admin() || empty( $bp_params ) || ! isset( $bp_params['object'] ) || ! isset( $bp_params['item_id'] ) ) {
			return $upload_dir;
		}

		$item_id = $bp_params['item_id'];
		$object  = $bp_params['object'];

		if ( ! is_admin() || ( 0 < $item_id && 'group' === $object ) || ( 'group' !== $object ) ) {
			return $upload_dir;
		}

		$directory = 'group-avatars';

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
			'bb_group_default_custom_group_avatar_upload_dir',
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

}

return new BP_Admin_Setting_Groups();
