<?php
/**
 * Zoom integration admin tab
 *
 * @since BuddyBoss 1.2.10
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Setup Zoom integration admin tab class.
 *
 * @since BuddyBoss 1.2.10
 */
class BP_Zoom_Admin_Integration_Tab extends BP_Admin_Integration_tab {
	protected $current_section;

	/**
	 * Initialize
	 *
	 * @since BuddyBoss 1.2.10
	 */
	public function initialize() {
		$this->tab_order       = 50;
		$this->current_section = 'bp_zoom-integration';
	}

	/**
	 * Zoom Integration is active?
	 *
	 * @since BuddyBoss 1.2.10
	 * @return bool
	 */
	public function is_active() {
		return (bool) apply_filters( 'bp_zoom_integration_is_active', true );
	}

	/**
	 * Register setting fields for zoom integration.
	 *
	 * @since BuddyBoss 1.2.10
	 */
	public function register_fields() {

		$sections = $this->get_settings_sections();

		foreach ( (array) $sections as $section_id => $section ) {

			// Only add section and fields if section has fields
			$fields = $this->get_settings_fields_for_section( $section_id );

			if ( empty( $fields ) ) {
				continue;
			}

			$section_title    = ! empty( $section['title'] ) ? $section['title'] : '';
			$section_callback = ! empty( $section['callback'] ) ? $section['callback'] : false;

			// Add the section
			$this->add_section( $section_id, $section_title, $section_callback );

			// Loop through fields for this section
			foreach ( (array) $fields as $field_id => $field ) {

				$field['args'] = isset( $field['args'] ) ? $field['args'] : array();

				if ( ! empty( $field['callback'] ) && ! empty( $field['title'] ) ) {
					$sanitize_callback = isset( $field['sanitize_callback'] ) ? $field['sanitize_callback'] : [];
					$this->add_field( $field_id, $field['title'], $field['callback'], $sanitize_callback, $field['args'] );
				}
			}
		}
	}

	/**
	 * Get setting sections for zoom integration.
	 *
	 * @since BuddyBoss 1.2.10
	 *
	 * @return array $settings Settings sections for zoom integration.
	 */
	public function get_settings_sections() {

		$settings = array(
			'bp_zoom_settings_section' => array(
				'page'  => 'zoom',
				'title' => __( 'Zoom Conference', 'buddyboss' ),
			),
			'bp_zoom_users_section' => array(
				'page'  => 'zoom',
				'title' => __( 'Zoom Users', 'buddyboss' ),
			),
		);

		return $settings;
	}

	/**
	 * Get setting fields for section in zoom integration.
	 *
	 * @since BuddyBoss 1.2.10
	 *
	 * @return array|false $fields setting fields for section in zoom integration false otherwise.
	 */
	public function get_settings_fields_for_section( $section_id = '' ) {

		// Bail if section is empty
		if ( empty( $section_id ) ) {
			return false;
		}

		$fields = $this->get_settings_fields();
		$fields = isset( $fields[ $section_id ] ) ? $fields[ $section_id ] : false;

		return $fields;
	}

	/**
	 * Register setting fields for zoom integration.
	 *
	 * @since BuddyBoss 1.2.10
	 *
	 * @return array $fields setting fields for zoom integration.
	 */
	public function get_settings_fields() {

		$fields = array();

		$fields['bp_zoom_settings_section'] = array(
			'bp-zoom-enable' => array(
				'title'             => __( 'Enable Zoom', 'buddyboss' ),
				'callback'          => 'bp_zoom_settings_callback_enable_field',
				'sanitize_callback' => 'string',
				'args'              => array(),
			)
		);

		if ( bp_zoom_is_zoom_enabled() ) {
			$fields['bp_zoom_settings_section']['bp-zoom-api-key'] = array(
				'title'             => __( 'Zoom API Key', 'buddyboss' ),
				'callback'          => 'bp_zoom_settings_callback_api_key_field',
				'sanitize_callback' => 'string',
				'args'              => array(),
			);

			$fields['bp_zoom_settings_section']['bp-zoom-api-secret'] = array(
				'title'             => __( 'Zoom API Secret', 'buddyboss' ),
				'callback'          => 'bp_zoom_settings_callback_api_secret_field',
				'sanitize_callback' => 'string',
				'args'              => array(),
			);

			$fields['bp_zoom_settings_section']['bp_zoom_api_check_connection'] = array(
				'title'    => __( '&#160;', 'buddyboss' ),
				'callback' => 'bp_zoom_api_check_connection_button',
			);

			$fields['bp_zoom_users_section'] = array(

				'bp-zoom-users-list' => array(
					'title'    => __( '&#160;', 'buddyboss' ),
					'callback' => 'bp_zoom_admin_users_list_callback',
				)

			);
		}

		return $fields;
	}

	public function settings_saved() {
		$this->db_install_zoom_meetings();
		parent::settings_saved();
	}

	/**
	 * Install database tables for the Groups zoom meetings.
	 *
	 * @since BuddyBoss 1.2.10
	 */
	public function db_install_zoom_meetings() {

		// check zoom enabled.
		if ( ! bp_zoom_is_zoom_enabled() ) {
			return;
		}

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		require_once buddypress()->plugin_dir . '/bp-core/admin/bp-core-admin-schema.php';
		$switched_to_root_blog = false;

		// Make sure the current blog is set to the root blog.
		if ( ! bp_is_root_blog() ) {
			switch_to_blog( bp_get_root_blog_id() );
			$switched_to_root_blog = true;
		}

		$sql             = array();
		$charset_collate = $GLOBALS['wpdb']->get_charset_collate();
		$bp_prefix       = bp_core_get_table_prefix();

		$sql[] = "CREATE TABLE {$bp_prefix}bp_zoom_meetings (
				id bigint(20) NOT NULL AUTO_INCREMENT PRIMARY KEY,
				group_id bigint(20) NOT NULL,
				title varchar(300) NOT NULL,
				user_id varchar(150) NOT NULL,
				start_date datetime NOT NULL,
				timezone varchar(150) NOT NULL,
				meeting_authentication bool default 0,
				password varchar(150) NOT NULL,
				duration int(11) NOT NULL,
				join_before_host bool default 0,
				host_video bool default 0,
				participants_video bool default 0,
				mute_participants bool default 0,
				waiting_room bool default 0,
				enforce_login bool default 0,
				auto_recording varchar(75) default 'none',
				alternative_host_ids text NULL,
				zoom_details text NOT NULL,
				zoom_start_url text NOT NULL,
				zoom_join_url text NOT NULL,
				zoom_meeting_id text NOT NULL,
				KEY group_id (group_id)
			) {$charset_collate};";

		dbDelta( $sql );

		if ( $switched_to_root_blog ) {
			restore_current_blog();
		}
	}
}
