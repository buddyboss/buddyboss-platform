<?php
/**
 * BuddyBoss LearnDash integration Core class.
 *
 * @package BuddyBoss\LearnDash
 * @since BuddyBoss 1.0.0
 */

namespace Buddyboss\LearndashIntegration\Learndash;

use Buddyboss\LearndashIntegration\Learndash\Sync;
use Buddyboss\LearndashIntegration\Learndash\Hooks;
use Buddyboss\LearndashIntegration\Learndash\Admin;
use Buddyboss\LearndashIntegration\Learndash\Group;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Core class for the learndash settings
 *
 * @since BuddyBoss 1.0.0
 */
#[\AllowDynamicProperties]
class Core {

	/**
	 * Constructor
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function __construct() {
		 $this->sync = new Sync();
		$this->hooks = new Hooks();
		$this->admin = new Admin();
		$this->group = new Group();

		add_action( 'bp_ld_sync/init', array( $this, 'init' ) );
	}

	/**
	 * Add actions once integration is ready
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function init() {
	}
}
