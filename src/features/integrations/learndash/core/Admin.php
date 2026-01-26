<?php
/**
 * BuddyBoss LearnDash integration Admin class.
 *
 * @package BuddyBoss\LearnDash
 * @since BuddyBoss 1.0.0
 */

namespace Buddyboss\LearndashIntegration\Core;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Admin related actions on the core plugin
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
		add_action( 'bp_ld_sync/depencencies_failed', array( $this, 'registerDependencyNotices' ) );
		add_action( 'bp_ld_sync/requirements_failed', array( $this, 'registerRequirementNotices' ) );
	}

	/**
	 * Add admin notice hook for dependencies
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function registerDependencyNotices() {
		add_action( 'admin_notices', array( $this, 'printDependencyAdminNotice' ) );
	}

	/**
	 * Add admin notice hook for requirements
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function registerRequirementNotices() {
		// Removed this action because we don't have to display the notice.
		// add_action('admin_notices',  [$this, 'printRequirementAdminNotice']);
	}

	/**
	 * Output the dependency notice html
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function printDependencyAdminNotice() {
		$missingDepencencies = bp_ld_sync()->dependencies->getMissingDepencencies();

		$message = sprintf(
			__( 'The following required plugins are missing: %s', 'buddyboss' ),
			'<b>' . implode( ',', $missingDepencencies ) . '</b>'
		);

		printf( '<div class="notice notice-error">%s</div>', wpautop( $message ) );
	}

	/**
	 * Output the requirement notice html
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function printRequirementAdminNotice() {
		foreach ( bp_ld_sync()->requirements->getMissingRequirements() as $requirement ) {
			printf( '<div class="notice notice-error">%s</div>', wpautop( $requirement['error'] ) );
		}
	}
}
