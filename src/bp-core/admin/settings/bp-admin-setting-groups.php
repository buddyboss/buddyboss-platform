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

	// Initialize class
	public function initialize() {
		$this->tab_label = __( 'Groups', 'buddyboss' );
		$this->tab_name  = 'bp-groups';
		$this->tab_order = 20;

		$this->active_tab = bp_core_get_admin_active_tab();

		add_action( 'bb_admin_settings_form_tag', array( $this, 'bb_admin_setting_group_add_enctype' ) );

		// Group Avatar.
		add_filter( 'bp_attachment_avatar_script_data', array( $this, 'bb_admin_setting_group_script_data' ), 10, 2 );
		add_filter( 'groups_avatar_upload_dir', array( $this, 'bb_group_default_custom_group_avatar_upload_dir' ), 10, 1 );
		add_filter( 'bb_core_avatar_crop_item_id_args', array( $this, 'bb_default_custom_group_avatar_crop_item_id_args' ), 10, 2 );
		add_filter( 'bp_core_fetch_gravatar_url_check', array( $this, 'bb_default_custom_group_avatar_url_check' ), 10, 2 );
	}

	// Check if groups are enabled
	public function is_active() {
		return bp_is_active( 'groups' );
	}

	// Register setting fields
	public function register_fields() {
		// Group avatar and cover.
		$this->add_section( 'bp_groups_avatar_settings', __( 'Group Photos', 'buddyboss' ), '', 'bp_group_avatar_tutorial' );

		// Allow group avatars.
		$this->add_field( 'bp-disable-group-avatar-uploads', __( 'Group Avatars', 'buddyboss' ), 'bp_admin_setting_callback_group_avatar_uploads', 'intval' );

		$args          = array();
		$args['class'] = 'group-avatar-options avatar-options default-group-avatar-type';
		$this->add_field( 'bp-default-group-avatar-type', __( 'Default Group Avatar', 'buddyboss' ), 'bp_admin_setting_callback_default_group_avatar_type', 'intval', $args );

		$args          = array();
		$args['class'] = 'group-avatar-options avatar-options default-group-avatar-custom';
		$this->add_field( 'bp-default-group-custom-avatar', __( 'Upload Custom Avatar', 'buddyboss' ), 'bp_admin_setting_callback_default_group_custom_avatar', 'string', $args );

		// Allow group cover photos.
		if ( bp_is_active( 'groups', 'cover_image' ) ) {
			$this->add_field( 'bp-disable-group-cover-image-uploads', __( 'Group Cover Images', 'buddyboss' ), 'bp_admin_setting_callback_group_cover_image_uploads', 'intval' );
		}

		// Group Settings.
		$this->add_section( 'bp_groups', __( 'Group Settings', 'buddyboss' ), '', 'bp_group_setting_tutorial' );

		// Allow subscriptions setting.
		$this->add_field( 'bp_restrict_group_creation', __( 'Group Creation', 'buddyboss' ), 'bp_admin_setting_callback_group_creation', 'intval' );

		// Allow Group Message.
		if ( bp_is_active( 'groups' ) && bp_is_active( 'messages' ) ) {
			$this->add_field( 'bp-disable-group-messages', __( 'Group Messages', 'buddyboss' ), 'bp_admin_setting_callback_group_messages', 'intval' );
		}

		// Register Group Types sections.
		$this->add_section( 'bp_groups_types', __( 'Group Types', 'buddyboss' ), '', 'bp_group_types_tutorial' );

		// enable or disable group types.
		$this->add_field( 'bp-disable-group-type-creation', __( 'Group Types', 'buddyboss' ), 'bp_admin_setting_callback_group_type_creation', 'intval' );

		// enable or disable group automatically approve memberships.
		$this->add_field( 'bp-enable-group-auto-join', __( 'Auto Membership Approval', 'buddyboss' ), 'bp_admin_setting_callback_group_auto_join', 'intval' );

		// Register Group Hierarchies sections.
		$this->add_section( 'bp_groups_hierarchies', __( 'Group Hierarchies', 'buddyboss' ), '', 'bp_group_hierarchies_tutorial' );

		// enable or disable group hierarchies.
		$type          = array();
		$type['class'] = 'bp-enable-group-hierarchies';
		$this->add_field( 'bp-enable-group-hierarchies', __( 'Hierarchies', 'buddyboss' ), 'bp_admin_setting_callback_group_hierarchies', 'intval', $type );

		// Hide subgroups from the main Groups Directory.
		$type          = array();
		$type['class'] = 'bp-enable-group-hide-subgroups';
		$this->add_field( 'bp-enable-group-hide-subgroups', __( 'Hide Subgroups', 'buddyboss' ), 'bp_admin_setting_callback_group_hide_subgroups', 'intval', $type );

		// enable or disable restrict invites to members who already in specific parent group.
		$type          = array();
		$type['class'] = 'bp-enable-group-restrict-invites';
		$this->add_field( 'bp-enable-group-restrict-invites', __( 'Restrict Invitations', 'buddyboss' ), 'bp_admin_setting_callback_group_restrict_invites', 'intval', $type );

		// Section for group list.
		$this->add_section( 'bp_group_list_settings', __( 'Group Directories', 'buddyboss' ), '', 'bp_group_directories_tutorial' );

		// Admin Settings for Settings > Groups > Group Directories > Enabled Views
		$this->add_field(
			'bp-group-layout-format',
			__( 'Enabled View(s)', 'buddyboss' ),
			'bp_admin_setting_callback_group_layout_type_format'
		);

		// Admin Settings for Settings > Groups > Group Directories > Default View
		$args          = array();
		$args['class'] = 'group-default-layout group-layout-options';
		$this->add_field( 'bp-group-layout-default-format', __( 'Default View', 'buddyboss' ), 'bp_admin_setting_group_layout_default_option', 'radio', $args );

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
	 * Add data encoding type for file uploading
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	public function bb_admin_setting_group_add_enctype() {
		echo ' enctype="multipart/form-data"';
	}

	/**
	 * The custom group avatar script data.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param array  $script_data The avatar script data.
	 * @param string $object      The object the avatar belongs to (eg: user or group).
	 */
	public function bb_admin_setting_group_script_data( $script_data, $object = '' ) {

		if ( $this->active_tab !== $this->tab_name ) {
			return $script_data;
		}

		$script_data['bp_params'] = array(
			'object'     => 'group',
			'item_id'    => 'custom',
			'has_avatar' => bb_has_default_custom_upload_group_avatar(),
			'nonces'     => array(
				'set'    => wp_create_nonce( 'bp_avatar_cropstore' ),
				'remove' => wp_create_nonce( 'bp_group_avatar_delete' ),
			),
		);

		// Set feedback messages.
		$script_data['feedback_messages'] = array(
			1 => __( 'There was a problem cropping custom group avatar.', 'buddyboss' ),
			2 => __( 'The custom group avatar was uploaded successfully.', 'buddyboss' ),
			3 => __( 'There was a problem deleting custom group avatar. Please try again.', 'buddyboss' ),
			4 => __( 'The custom group avatar was deleted successfully!', 'buddyboss' ),
		);

		return $script_data;
	}

	/**
	 * Setup default custom avatar upload directory.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param array $upload_dir The original Uploads dir.
	 * @return array Array containing the path, URL, and other helpful settings.
	 */
	public function bb_group_default_custom_group_avatar_upload_dir( $upload_dir = array() ) {
		$bp_params = array();

		if ( ! empty( $_POST['bp_params'] ) ) {
			$bp_params = $_POST['bp_params'];
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
		 * @since BuddyBoss [BBVERSION]
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

	/**
	 * Set item ID for image crop.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param int|string $item_id ID of the avatar item being requested.
	 * @param array      $args {
	 *     @type string     $original_file The source file (absolute path) for the Attachment.
	 *     @type string     $object        Avatar type being requested.
	 *     @type int|string $item_id       ID of the avatar item being requested.
	 *     @type string     $avatar_dir    Subdirectory where the requested avatar should be found.
	 * }
	 * @return int|string Actual item ID for upload custom avatar.
	 */
	public function bb_default_custom_group_avatar_crop_item_id_args( $item_id = 0, $args ) {
		if ( is_admin() && ( empty( $item_id ) || 0 == $item_id ) && 'group' === $args['object'] ) {
			return 'custom';
		}

		return $item_id;
	}

	/**
	 * Modify a gravatar avatar URL for custom uploaded avatar.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param string $gravatar URL for a gravatar.
	 * @param array  $params   Array of parameters for the request.
	 */
	public function bb_default_custom_group_avatar_url_check( $gravatar, $params ) {

		$item_id = $params['item_id'];
		$object  = $params['object'];

		if ( is_admin() && ( 'custom' === $item_id && 'group' === $object ) && ( isset( $_REQUEST['action'] ) && 'bp_avatar_delete' === $_REQUEST['action'] ) && false === strpos( $gravatar, 'custom' ) ) {
			return bb_get_default_custom_profile_group_avatar_upload_placeholder();
		}

		return $gravatar;
	}
}

return new BP_Admin_Setting_Groups();
