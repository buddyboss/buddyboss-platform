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

    }
}

return new LearnDash_BuddyPress_Groups_Sync_Admin;
