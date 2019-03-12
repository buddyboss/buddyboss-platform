<?php
/**
 * @todo add description
 * 
 * @package BuddyBoss\LearnDash
 * @since BuddyBoss 1.0.0
 */ 

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * @todo add title/description
 * 
 * @since BuddyBoss 1.0.0
 */
class LearnDash_BuddyPress_Groups_Sync
{
    protected static $instance;

    /**
	 * @todo add title/description
	 *
	 * @since BuddyBoss 1.0.0
	 */
	protected function __construct()
    {
        $this->register_hooks();
    }

    /**
	 * @todo add title/description
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public static function instance()
    {
        if (! static::$instance) {
            static::$instance = new static;
        }

        return static::$instance;
    }

    /**
	 * @todo add title/description
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function register_hooks() {
    	global $bp_learndash_learndash;

        $bp_learndash_learndash    = require_once bp_learndash_path('groups-sync/includes/class-learndash.php');
    }
}
