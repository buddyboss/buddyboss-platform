<?php
namespace Buddypress\CLI\Command;

use WP_CLI;

/**
 * Manage BuddyPress Tools.
 *
 * @since BuddyPress 1.5.0
 */
class Tool extends BuddypressCommand {

	/**
	 * Repair.
	 *
	 * ## OPTIONS
	 *
	 * <type>
	 * : Name of the repair tool.
	 * ---
	 * options:
	 *   - friend-count
	 *   - group-count
	 *   - blog-records
	 *   - count-members
	 *   - last-activity
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     $ wp bp tool repair friend-count
	 *     $ wp bp tool fix friend-count
	 *     Success: Counting the number of connections for each user. Complete!
	 *
	 * @alias fix
	 */
	public function repair( $args, $assoc_args ) {
		$repair = 'bp_admin_repair_' . $this->sanitize_string( $args[0] );

		if ( ! function_exists( $repair ) ) {
			WP_CLI::error( 'There is no repair tool with that name.' );
		}

		$result = $repair();

		if ( 0 === $result[0] ) {
			WP_CLI::success( $result[1] );
		} else {
			WP_CLI::error( $result[1] );
		}
	}

	/**
	 * Display BuddyPress version currently installed.
	 *
	 * ## EXAMPLE
	 *
	 *     $ wp bp tool version
	 *     BuddyPress: 3.0.0
	 */
	public function version() {
		WP_CLI::line( 'BuddyPress: ' . bp_get_version() );
	}
}
