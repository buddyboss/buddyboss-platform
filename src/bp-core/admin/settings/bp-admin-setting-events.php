<?php
/**
 * Add admin Events settings page in Dashboard → BuddyBoss → Settings
 *
 * @package BuddyBoss\Core
 * @since BuddyBoss Events 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Events Settings tab.
 *
 * @since BuddyBoss Events 1.0.0
 */
class BP_Admin_Setting_Events extends BP_Admin_Setting_tab {

	/**
	 * Initialize class.
	 */
	public function initialize() {
		$this->tab_label = __( 'Events', 'buddyboss' );
		$this->tab_name  = 'bp-events';
		$this->tab_order = 60;
	}

	/**
	 * Check if events component is active.
	 *
	 * @return bool
	 */
	public function is_active() {
		return bp_is_active( 'events' );
	}

	/**
	 * Register all settings fields.
	 */
	public function register_fields() {

		// Section: General.
		$this->add_section(
			'bp_events_general',
			__( 'General', 'buddyboss' ),
			'',
			'bp_events_general_tutorial'
		);

		$this->add_field(
			'bb-events-enabled',
			__( 'Events', 'buddyboss' ),
			'bp_events_admin_setting_callback_events_enabled',
			'intval'
		);

		$this->add_field(
			'bb_events_root_slug',
			__( 'Directory Slug', 'buddyboss' ),
			'bp_events_admin_setting_callback_root_slug',
			'sanitize_title'
		);

		$this->add_field(
			'bb_events_default_calendar_view',
			__( 'Default Calendar View', 'buddyboss' ),
			'bp_events_admin_setting_callback_default_calendar_view',
			'sanitize_text_field'
		);

		// Section: Permissions.
		$this->add_section(
			'bp_events_permissions',
			__( 'Permissions', 'buddyboss' ),
			'',
			'bp_events_permissions_tutorial'
		);

		$this->add_field(
			'bb_events_creation_permission',
			__( 'Event Creation', 'buddyboss' ),
			'bp_events_admin_setting_callback_creation_permission',
			'sanitize_text_field'
		);

		$this->add_field(
			'bb_events_moderation_enabled',
			__( 'Event Moderation', 'buddyboss' ),
			'bp_events_admin_setting_callback_moderation',
			'intval'
		);

		// Section: Calendar.
		$this->add_section(
			'bp_events_calendar',
			__( 'Calendar', 'buddyboss' ),
			'',
			'bp_events_calendar_tutorial'
		);

		$this->add_field(
			'bb_events_public_group_site_calendar',
			__( 'Site Calendar', 'buddyboss' ),
			'bp_events_admin_setting_callback_public_group_site_calendar',
			'intval'
		);
	}
}

return new BP_Admin_Setting_Events();
