<?php
/**
 * BuddyBoss TutorLMS integration admin class.
 *
 * @package BuddyBoss\TutorLMS
 * @since BuddyBoss 1.0.0
 */

namespace Buddyboss\TutorLMSIntegration\Buddypress;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Class for all admin related functions
 *
 * @since BuddyBoss 1.0.0
 */
class Admin {

	/**
	 * Constructor
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function __construct() {
		 add_action( 'bb_tutorlms/init', array( $this, 'init' ) );
	}

	/**
	 * Add actions once integration is ready
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function init() {
		
	}

}
