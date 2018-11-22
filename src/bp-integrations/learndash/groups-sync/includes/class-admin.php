<?php

class LearnDash_BuddyPress_Groups_Sync_Admin
{
    public function __construct()
    {
        $this->register_hooks();
    }

    public function register_hooks()
    {
        if (! is_admin()) {
            return;
        }

        add_filter('plugin_action_links_' . plugin_basename(ld_bp_groups_sync()->file()), [$this, 'plugin_add_settings_link']);
    }

    public function plugin_add_settings_link($actions)
    {
        $actions[] = sprintf(
            '<a href="%s">%s</a>',
            add_query_arg('page', 'learndash_lms_settings_buddypress_groups_sync', admin_url('admin.php')),
            __('Settings')
        );

        return $actions;
    }
}

return new LearnDash_BuddyPress_Groups_Sync_Admin;
