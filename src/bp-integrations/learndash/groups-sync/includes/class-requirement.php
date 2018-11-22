<?php

class LearnDash_BuddyPress_Groups_Sync_Requirement
{
    protected $valid = true;
    protected $error = '';

    public function __construct()
    {
        $this->check();
        $this->register_hooks();
    }

    public function check()
    {
        $requirements = [
            'learndash_activated',
            'buddypress_activated',
            'buddypress_group_enabled'
        ];

        foreach ($requirements as $requirement) {
            $validate = $this->{$requirement}();

            if ($validate !== true ) {
                $this->valid = false;
                $this->error = sprintf('<div class="notice notice-error">%s</div>', wpautop($validate));
                break;
            }
        }
    }

    public function register_hooks()
    {
        if (! is_admin()) {
            return;
        }

        add_action('admin_notices',  [$this, 'print_requirement_admin_notice']);
    }

    public function print_requirement_admin_notice()
    {
        if ($this->valid()) {
            return;
        }

        echo $this->error;
    }

    public function valid()
    {
        return $this->valid;
    }

    protected function learndash_activated()
    {
        if (defined('LEARNDASH_VERSION')) {
            return true;
        }

        return sprintf(
            __('%s requires the plugin %s to be activated.', 'ld_bp_groups_sync'),
            '<b>' . __('LearnDash BuddyPress Groups Sync', 'ld_bp_groups_sync') . '</b>',
            '<b>' . __('LearnDash LMS', 'ld_bp_groups_sync') . '</b>'
        );
    }

    protected function buddypress_activated()
    {
        if (function_exists('buddypress')) {
            return true;
        }

        return sprintf(
            __('%s requires the plugin %s to be activated.', 'ld_bp_groups_sync'),
            '<b>' . __('LearnDash BuddyPress Groups Sync', 'ld_bp_groups_sync') . '</b>',
            '<b>' . __('BuddyPress', 'ld_bp_groups_sync') . '</b>'
        );
    }

    protected function buddypress_group_enabled()
    {
        if (bp_is_active('groups')) {
            return true;
        }

        return sprintf(
            __('%s requires the BuddyPress component %s to be enabled.', 'ld_bp_groups_sync'),
            '<b>' . __('LearnDash BuddyPress Groups Sync', 'ld_bp_groups_sync') . '</b>',
            '<b>' . __('User Groups', 'buddypress') . '</b>'
        );
    }
}

return new LearnDash_BuddyPress_Groups_Sync_Requirement;

