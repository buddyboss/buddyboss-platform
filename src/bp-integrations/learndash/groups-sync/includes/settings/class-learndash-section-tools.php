<?php

class LearnDash_Settings_Section_BuddyPress_Groups_Sync_Tools extends LearnDash_Settings_Section
{
    public function __construct()
    {
        $this->settings_page_id         = 'learndash_lms_settings_buddypress_groups_sync';
        $this->setting_option_key       = 'learndash_settings_buddypress_groups_sync_tools';
        $this->settings_section_key     = 'settings_buddypress_groups_sync_tools';
        $this->settings_section_label   = esc_html_x('Groups Sync Tools', 'Tools Section Label', 'ld_bp_groups_sync');

        parent::__construct();
    }

    public function show_meta_box()
    {
        require ld_bp_groups_sync()->path('templates/admin/learndash-settings-tools.php');
    }
}
