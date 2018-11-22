<?php

class LearnDash_Settings_Section_BuddyPress_Groups_Reports_Help extends LearnDash_Settings_Section {
	public function __construct() {
		$this->settings_page_id       = 'learndash_lms_settings_buddypress_groups_reports';
		$this->setting_option_key     = 'submitdiv';
		$this->settings_section_label = esc_html__( 'Help', 'ld_bp_groups_reports' );
		$this->metabox_context        = 'side';

		parent::__construct();
	}

	public function show_meta_box() {
		?>
        <div class="boss-support-area">
			<?php if ( false ): ?>
                <h3><?php _e( 'Here is a video tutorial to get you started:', 'ld_bp_groups_reports' ); ?></h3>

                <div class="boss-video-container">
                    <div class="boss-videoWrapper">
                        <iframe width="100%" height="auto" src="https://www.youtube.com/embed/IN9iJIFRyts"
                                frameborder="0" allowfullscreen></iframe>
                    </div>
                </div>
			<?php endif; ?>

            <h3><?php _e( 'Need more help?', 'ld_bp_groups_reports' ); ?></h3>

            <ul>
                <li><a href="http://www.buddyboss.com/faq/"
                       target="_blank"><?php _e( 'Frequently Asked Questions', 'ld_bp_groups_reports' ); ?></a></li>
                <li><a href="http://www.buddyboss.com/support-forums/"
                       target="_blank"><?php _e( 'Support Forums', 'ld_bp_groups_reports' ); ?></a></li>
                <li><a href="http://www.buddyboss.com/release-notes/"
                       target="_blank"><?php _e( 'Current Version &amp; Release Notes', 'ld_bp_groups_reports' ); ?></a>
                </li>
                <li><a href="http://www.buddyboss.com/updating/"
                       target="_blank"><?php _e( 'How to Update', 'ld_bp_groups_reports' ); ?></a></li>
            </ul>
        </div>

		<?php
	}

	public function load_settings_fields() {
		// don't do anything
	}
}
