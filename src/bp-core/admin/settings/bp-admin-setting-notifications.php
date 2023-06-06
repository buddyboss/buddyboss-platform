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

		add_action( 'admin_notices', array( $this, 'bb_admin_legacy_notification_notice' ) );
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

		// All preferences registered.
		$notification_preferences = bb_register_notification_preferences();
		$preferences              = array();
		if ( ! empty( $notification_preferences ) ) {
			foreach ( $notification_preferences as $group => $group_data ) {

				if ( ! empty( $group_data['fields'] ) ) {
					$keys = array_filter(
						array_map(
							function ( $fields ) {
								if (
									isset( $fields['notification_read_only'] ) &&
									true === (bool) $fields['notification_read_only']
								) {
									return array(
										'key'     => $fields['key'],
										'default' => $fields['default'],
									);
								}
							},
							$group_data['fields']
						)
					);

					if ( ! empty( $keys ) ) {
						$preferences = array_merge( $keys, $preferences );
					}
				}
			}
		}

		if ( ! empty( $preferences ) ) {
			foreach ( $preferences as $preference ) {

				if ( isset( $preference['key'] ) && isset( $preference['default'] ) ) {
					if ( isset( $enabled_notification[ $preference['key'] ] ) && 'yes' === $preference['default'] ) {
						$enabled_notification[ $preference['key'] ]['main'] = $preference['default'];
					} else {
						unset( $enabled_notification[ $preference['key'] ] );
					}
				}
			}
		}

		if ( ! bb_enabled_legacy_email_preference() ) {
			$hide_message_notification     = isset( $_POST['hide_message_notification'] ) ? sanitize_text_field( $_POST['hide_message_notification'] ) : 0;
			$delay_email_notification      = isset( $_POST['delay_email_notification'] ) ? sanitize_text_field( $_POST['delay_email_notification'] ) : 0;
			$time_delay_email_notification = isset( $_POST['time_delay_email_notification'] ) ? sanitize_text_field( $_POST['time_delay_email_notification'] ) : 15;

			bp_update_option( 'hide_message_notification', (int) $hide_message_notification );
			bp_update_option( 'delay_email_notification', (int) $delay_email_notification );
			bp_update_option( 'time_delay_email_notification', (int) $time_delay_email_notification );
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

	/**
	 * Added admin notice when legacy mode of notification has been enabled.
	 *
	 * @since BuddyBoss 2.1.5.1
	 *
	 * @return void
	 */
	public function bb_admin_legacy_notification_notice() {
		if (
			$this->tab_name !== $this->get_active_tab() ||
			true !== bb_enabled_legacy_email_preference()
		) {
			return;
		}

		?>
		<div class="notice notice-info">
			<p>
				<?php
				printf(
					wp_kses_post(
					/* translators: Tutorial link. */
						__( 'Your site is currently using the legacy notifications system. To disable, please %s.', 'buddyboss' )
					),
					'<a href="https://www.buddyboss.com/resources/dev-docs/web-development/enabling-legacy-mode-for-notifications-api/" target="_blank">' . esc_html__( 'review this tutorial', 'buddyboss' ) . '</a>'
				);
				?>
			</p>
		</div>
		<?php
	}
}

// Class instance.
return new BB_Admin_Setting_Notifications();
