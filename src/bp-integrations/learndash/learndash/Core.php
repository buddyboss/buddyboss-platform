<?php
/**
 * @todo add description
 * 
 * @package BuddyBoss\LearnDash
 * @since BuddyBoss 1.0.0
 */ 

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

namespace Buddyboss\LearndashIntegration\Learndash;

use Buddyboss\LearndashIntegration\Learndash\Sync;
use Buddyboss\LearndashIntegration\Learndash\Hooks;
use Buddyboss\LearndashIntegration\Learndash\Admin;
use Buddyboss\LearndashIntegration\Learndash\Group;

/**
 * 
 * 
 * @since BuddyBoss 1.0.0
 */
class Core
{
	/**
	 * 
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function __construct()
	{
		$this->sync    = new Sync;
		$this->hooks   = new Hooks;
		$this->admin   = new Admin;
		$this->group   = new Group;

		add_action('bp_ld_sync/init', [$this, 'init']);
	}

	/**
	 * 
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function init()
	{

	}
}
