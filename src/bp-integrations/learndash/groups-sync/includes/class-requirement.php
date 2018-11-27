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
            __('%s requires the plugin %s to be activated.', 'buddyboss'),
            '<b>' . __('LearnDash BuddyBoss Groups Sync', 'buddyboss') . '</b>',
            '<b>' . __('LearnDash LMS', 'buddyboss') . '</b>'
        );
    }

    protected function buddypress_activated()
    {
        if (function_exists('buddypress')) {
            return true;
        }

        return sprintf(
            __('%s requires the plugin %s to be activated.', 'buddyboss'),
            '<b>' . __('LearnDash BuddyBoss Groups Sync', 'buddyboss') . '</b>',
            '<b>' . __('BuddyBoss', 'buddyboss') . '</b>'
        );
    }

    protected function buddypress_group_enabled()
    {
        if (bp_is_active('groups')) {
            return true;
        }

        return sprintf(
            __('%s requires the BuddyBoss component %s to be enabled.', 'buddyboss'),
            '<b>' . __('LearnDash BuddyBoss Groups Sync', 'buddyboss') . '</b>',
            '<b>' . __('User Groups', 'buddypress') . '</b>'
        );
    }
}

return new LearnDash_BuddyPress_Groups_Sync_Requirement;

