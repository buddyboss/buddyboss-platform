<?php
/**
 * Add admin Notification settings page in Dashboard->BuddyBoss->Settings
 *
 * @package BuddyBoss\Core
 *
 * @since BuddyBoss 1.7.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Main notification settings class.
 *
 * @since BuddyBoss 1.7.0
 */
class BB_Admin_Setting_Notifications extends BP_Admin_Setting_tab {

	/**
	 * Initial method for this class.
	 * 
	 * @since BuddyBoss 1.7.0
	 * 
	 * @return void
	 */
	public function initialize() {
		$this->tab_label = __( 'Notifications', 'buddyboss' );
		$this->tab_name  = 'bp-notifications';
		$this->tab_order = 40;
	}

	public function is_active() {
		return bp_is_active( 'notifications' );
	}

	/**
	 * Sore on-screen notification settings value.
	 * 
	 * @since BuddyBoss 1.7.0
	 * 
	 * @return void
	 */
	public function settings_save() {
		parent::settings_save();

        $enabel         = empty( $_POST['_bp_on_screen_notifications_enable'] ) ?         0  : sanitize_text_field( $_POST['_bp_on_screen_notifications_enable'] );
        $position       = empty( $_POST['_bp_on_screen_notifications_position'] ) ?       '' : sanitize_text_field( $_POST['_bp_on_screen_notifications_position'] );
        $mobile_support = empty( $_POST['_bp_on_screen_notifications_mobile_support'] ) ? 0  : sanitize_text_field( $_POST['_bp_on_screen_notifications_mobile_support'] );
        $visibility     = empty( $_POST['_bp_on_screen_notifications_visibility'] ) ?     '' : sanitize_text_field( $_POST['_bp_on_screen_notifications_visibility'] );
        $browser_tab    = empty( $_POST['_bp_on_screen_notifications_browser_tab'] ) ?    0  : sanitize_text_field( $_POST['_bp_on_screen_notifications_browser_tab'] );

        bp_update_option( '_bp_on_screen_notifications_enable', $enabel );
        bp_update_option( '_bp_on_screen_notifications_position', $position );
        bp_update_option( '_bp_on_screen_notifications_mobile_support', $mobile_support );
        bp_update_option( '_bp_on_screen_notifications_visibility', $visibility );
        bp_update_option( '_bp_on_screen_notifications_browser_tab', $browser_tab );

	}
	/**
	 * Register setting fields
	 * 
	 * @since BuddyBoss 1.7.0
	 * 
	 * @return void
	 */ 
	public function register_fields() {

		$this->add_section( 'bp_notifications', __( 'On-screen Notifications', 'buddyboss' ), '', 'bp_admin_on_screen_notification_setting_tutorial' );

        // Allow Activity edit setting.
		$this->add_field( '_bp_on_screen_notification_enable', __( 'On-screen notifications', 'buddyboss' ), 'bb_admin_setting_callback_on_screen_notifications_enable', 'intval' );
		$this->add_field( "_bp_on_screen_notification_position", __( 'Position on Screen', 'buddyboss' ), 'bb_admin_setting_callback_on_screen_notifications_position', 'intval' );
		$this->add_field( "_bp_on_screen_notification_mobile_support", __( 'Mobile Support', 'buddyboss' ), 'bb_admin_setting_callback_on_screen_notifications_mobile_support', 'intval' );
		$this->add_field( "_bp_on_screen_notification_visibility", __( 'Automatically Hide', 'buddyboss' ), 'bb_admin_setting_callback_on_screen_notifications_visibility', 'intval' );
		$this->add_field( "_bp_on_screen_notification_browser_tab", __( 'Show in Browser Tab', 'buddyboss' ), 'bb_admin_setting_callback_on_screen_notifications_browser_tab', 'intval' );

		/**
		 * Fires to register Notifications tab settings fields and section.
		 *
		 * @since BuddyBoss 1.7.0
		 *
		 * @param Object $this BB_Admin_Setting_Notifications.
		 */
		do_action( 'bb_admin_setting_notifications_register_fields', $this );
	}
}

// Class instance.
return new BB_Admin_Setting_Notifications();
