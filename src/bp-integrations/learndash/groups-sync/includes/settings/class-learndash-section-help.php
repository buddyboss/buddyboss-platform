<?php

class LearnDash_Settings_Section_BuddyPress_Groups_Sync_Help extends LearnDash_Settings_Section
{
    public function __construct()
    {
        $this->settings_page_id       = 'learndash_lms_settings_buddypress_groups_sync';
        $this->setting_option_key     = 'submitdiv';
        $this->settings_section_label = esc_html__('Help', 'ld_bp_groups_sync');
        $this->metabox_context        = 'side';

        parent::__construct();
    }

    public function show_meta_box()
    {
        ld_bp_groups_sync()->load('templates/admin/learndash-settings-help.php');
    }

    public function load_settings_fields()
    {
        // don't do anything
    }
}
