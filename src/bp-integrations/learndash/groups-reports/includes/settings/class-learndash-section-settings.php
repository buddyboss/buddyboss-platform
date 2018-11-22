<?php

class LearnDash_Settings_Section_BuddyPress_Groups_Reports extends LearnDash_Settings_Section {
	public function __construct() {
		$this->settings_page_id       = 'learndash_lms_settings_buddypress_groups_reports';
		$this->setting_option_key     = 'learndash_settings_buddypress_groups_reports';
		$this->settings_section_key   = 'settings_buddypress_groups_reports';
		$this->settings_section_label = esc_html_x( 'Groups Reports Global Settings', 'Settings Section Label', 'ld_bp_groups_reports' );

		parent::__construct();
	}

	public function load_settings_fields() {
		$fields = [];

		$fields['enable_group_reports'] = array(
			'name'    => 'enable_group_reports',
			'type'    => 'toggle',
			'label'   => esc_html__( 'Group Reports', 'ld_bp_groups_reports' ),
			'value'   => $this->get_value( 'enable_group_reports', '0' ),
			'options' => array(
				'1' => esc_html__( 'Enable BuddyPress Group Report for LearnDash', 'ld_bp_groups_reports' ),
			)
		);

		$fields['report_access'] = array(
			'name'      => 'report_access',
			'type'      => 'mulpital_checkbox_checked',
			'label'     => esc_html__( 'Generated BuddyPress Group Privacy', 'ld_bp_groups_reports' ),
			'help_text' => esc_html__( 'When a BuddyPress group is generated, set the group privacy to...', 'ld_bp_groups_reports' ),
			'value'     => $this->get_value( 'report_access', array() ),
			'options'   => array(
				'admin'     => __( 'Admin', 'ld_bp_groups_reports' ),
				'moderator' => __( 'Moderators', 'ld_bp_groups_reports' ),
				'member'    => __( 'Members', 'ld_bp_groups_reports' )
			)
		);

		$this->setting_option_fields = apply_filters( 'learndash_settings_fields', $fields, $this->settings_section_key );

		parent::load_settings_fields();
	}

	public function load_settings_values() {
		$this->settings_values_loaded = true;
		$this->setting_option_values  = ld_bp_groups_reports_get_settings();
	}

	protected function sub_section_heading( $text ) {
		$style = 'letter-spacing: .025em; margin-top: 2.5em; margin-bottom: .5em; text-transform: uppercase;';

		return sprintf( '<h4 style="%s">%s</h4>', $style, $text );
	}

	protected function get_value( $key, $default = null ) {
		return isset( $this->setting_option_values[ $key ] ) ? $this->setting_option_values[ $key ] : $default;
	}
}
