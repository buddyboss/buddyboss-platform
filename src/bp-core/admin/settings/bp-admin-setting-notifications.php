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

		$enabel               = empty( $_POST['_bp_on_screen_notifications_enable'] ) ? 0 : sanitize_text_field( $_POST['_bp_on_screen_notifications_enable'] );
		$position             = empty( $_POST['_bp_on_screen_notifications_position'] ) ? '' : sanitize_text_field( $_POST['_bp_on_screen_notifications_position'] );
		$mobile_support       = empty( $_POST['_bp_on_screen_notifications_mobile_support'] ) ? 0 : sanitize_text_field( $_POST['_bp_on_screen_notifications_mobile_support'] );
		$visibility           = empty( $_POST['_bp_on_screen_notifications_visibility'] ) ? '' : sanitize_text_field( $_POST['_bp_on_screen_notifications_visibility'] );
		$browser_tab          = empty( $_POST['_bp_on_screen_notifications_browser_tab'] ) ? 0 : sanitize_text_field( $_POST['_bp_on_screen_notifications_browser_tab'] );
		$enabled_notification = empty( $_POST['bb_enabled_notification'] ) ? array() : $_POST['bb_enabled_notification'];

		if ( ! bb_enabled_legacy_email_preference() ) {
			$hide_message_notification = isset( $_POST['hide_message_notification'] ) ? sanitize_text_field( $_POST['hide_message_notification'] ) : 1;
			bp_update_option( 'hide_message_notification', (int) $hide_message_notification );
		}

		bp_update_option( '_bp_on_screen_notifications_enable', $enabel );
		bp_update_option( '_bp_on_screen_notifications_position', $position );
		bp_update_option( '_bp_on_screen_notifications_mobile_support', $mobile_support );
		bp_update_option( '_bp_on_screen_notifications_visibility', $visibility );
		bp_update_option( '_bp_on_screen_notifications_browser_tab', $browser_tab );
		bp_update_option( 'bb_enabled_notification', $enabled_notification );
	}

	/**
	 * Register setting fields
	 *
	 * @since BuddyBoss 1.7.0
	 *
	 * @return void
	 */
	public function register_fields() {

		$sections = bb_notification_get_settings_sections();

		if ( ! empty( $sections ) ) {
			foreach ( (array) $sections as $section_id => $section ) {

				// Only add section and fields if section has fields.
				$fields = bb_notification_get_settings_fields_for_section( $section_id );

				if ( empty( $fields ) ) {
					continue;
				}

				$section_title     = ! empty( $section['title'] ) ? $section['title'] : '';
				$section_callback  = ! empty( $section['callback'] ) ? $section['callback'] : false;
				$tutorial_callback = ! empty( $section['tutorial_callback'] ) ? $section['tutorial_callback'] : false;
				$notice            = ! empty( $section['notice'] ) ? $section['notice'] : false;

				// Add the section.
				$this->add_section( $section_id, $section_title, $section_callback, $tutorial_callback, $notice );

				// Loop through fields for this section.
				foreach ( (array) $fields as $field_id => $field ) {

					$field['args'] = isset( $field['args'] ) ? $field['args'] : array();

					if ( ! empty( $field['callback'] ) ) {
						$sanitize_callback = isset( $field['sanitize_callback'] ) ? $field['sanitize_callback'] : array();
						$this->add_field( $field_id, $field['title'], $field['callback'], $sanitize_callback, $field['args'] );
					}
				}
			}
		}

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
