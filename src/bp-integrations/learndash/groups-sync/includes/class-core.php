<?php

class LearnDash_BuddyPress_Groups_Sync
{
    protected static $instance;

    protected function __construct()
    {
        $this->register_hooks();
    }

    public static function instance()
    {
        if (! static::$instance) {
            static::$instance = new static;
        }

        return static::$instance;
    }

    public function register_hooks() {
    	global $bp_learndash_admin, $bp_learndash_requirement, $bp_learndash_learndash, $bp_learndash_buddypress, $bp_learndash_groups;

        $bp_learndash_admin        = require_once bp_learndash_path('groups-sync/includes/class-admin.php');
        $bp_learndash_requirement  = require_once bp_learndash_path('groups-sync/includes/class-requirement.php');
        $bp_learndash_learndash    = require_once bp_learndash_path('groups-sync/includes/class-learndash.php');
        $bp_learndash_buddypress   = require_once bp_learndash_path('groups-sync/includes/class-buddypress.php');
        $bp_learndash_groups       = require_once bp_learndash_path('groups-sync/includes/class-groups-courses.php');
    }
}
