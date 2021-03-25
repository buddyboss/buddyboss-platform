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
	}

	// Check if groups are enabled
	public function is_active() {
		return bp_is_active( 'groups' );
	}

	// Register setting fields
	public function register_fields() {
		$this->add_section( 'bp_groups', __( 'Group Settings', 'buddyboss' ), '', 'bp_group_setting_tutorial' );

		// Allow subscriptions setting.
		$this->add_field( 'bp_restrict_group_creation', __( 'Group Creation', 'buddyboss' ), 'bp_admin_setting_callback_group_creation', 'intval' );

		// Allow group avatars.
		$this->add_field( 'bp-disable-group-avatar-uploads', __( 'Group Avatars', 'buddyboss' ), 'bp_admin_setting_callback_group_avatar_uploads', 'intval' );

		// Allow group cover photos.
		if ( bp_is_active( 'groups', 'cover_image' ) ) {
			$this->add_field( 'bp-disable-group-cover-image-uploads', __( 'Group Cover Images', 'buddyboss' ), 'bp_admin_setting_callback_group_cover_image_uploads', 'intval' );
		}

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
		$args = array();
		$args['class'] = 'group-default-layout group-layout-options';
		$this->add_field( 'bp-group-layout-default-format', __( 'Default View', 'buddyboss' ), 'bp_admin_setting_group_layout_default_option',  'radio', $args );

		/**
		 * Fires to register Groups tab settings fields and section.
		 *
		 * @since BuddyBoss 1.2.6
		 *
		 * @param Object $this BP_Admin_Setting_Groups.
		 */
		do_action( 'bp_admin_setting_groups_register_fields', $this );
	}
}

return new BP_Admin_Setting_Groups();
