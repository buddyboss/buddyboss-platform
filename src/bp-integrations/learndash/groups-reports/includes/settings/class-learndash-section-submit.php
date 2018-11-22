<?php

class LearnDash_Settings_Section_BuddyPress_Groups_Reports_Submit extends LearnDash_Settings_Section {
	public function __construct() {
		$this->settings_page_id       = 'learndash_lms_settings_buddypress_groups_reports';
		$this->setting_option_key     = 'submitdiv';
		$this->settings_section_label = esc_html__( 'Save Options', 'ld_bp_groups_reports' );
		$this->metabox_context        = 'side';
		$this->metabox_priority       = 'high';

		parent::__construct();

		// We override the parent value set for $this->metabox_key because we want the div ID to match the details WordPress
		// value so it will be hidden.
		$this->metabox_key = 'submitdiv';
	}

	public function show_meta_box() {
		printf( '
            <div id="submitpost" class="submitbox">
                <div id="major-publishing-actions">
                    <div id="publishing-action">
                        <span class="spinner"></span> %s
                    </div>
                    <div class="clear"></div>
                </div>
            </div>',
			get_submit_button( __( 'Save', 'ld_bp_groups_reports' ), 'primary', 'submit', false )
		);
	}

	public function load_settings_fields() {
		// don't do anything
	}
}
