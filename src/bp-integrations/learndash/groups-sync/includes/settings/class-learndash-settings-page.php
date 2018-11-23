<?php

class LearnDash_Settings_Page_BuddyPress_Groups_Sync extends LearnDash_Settings_Page
{
    public function __construct()
    {
        $this->parent_menu_page_url  = 'admin.php?page=learndash_lms_settings';
        $this->menu_page_capability  = LEARNDASH_ADMIN_CAPABILITY_CHECK;
        $this->settings_page_id      = 'learndash_lms_settings_buddypress_groups_sync';
        $this->settings_page_title   = esc_html_x('BuddyPress Groups Sync', 'Settings Tab Label', 'ld_bp_groups_sync');
        $this->settings_tab_title    = esc_html_x('BuddyPress Groups Sync', 'Settings Page Title', 'ld_bp_groups_sync');
        $this->settings_tab_priority = 35;

        add_action('learndash-settings-page-load', [$this, 'add_custom_scripts']);

        parent::__construct();
    }

    public function add_custom_scripts($screen_id)
    {
        if ($screen_id !== $this->settings_screen_id) {
            return;
        }

        wp_enqueue_script(
            'ld_bp_groups_sync-settings',
            bp_learndash_url('groups-sync/assets/js/admin/ld_bp_groups_sync-settings.js'),
            [],
            filemtime(bp_learndash_path('groups-sync/assets/js/admin/ld_bp_groups_sync-settings.js')),
            true
        );
    }
}
