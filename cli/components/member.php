<?php
namespace Buddypress\CLI\Command;

if ( ! class_exists( 'User_Command' ) ) {
	require_once( WP_CLI_ROOT . '/php/commands/user.php' );
}

/**
 * Manage BuddyPress Members
 *
 * @since 1.0.0
 */
class Member extends BuddypressCommand {

	/**
	 * Generate BuddyPress members. See documentation for `wp_user_generate`.
	 *
	 * This is a kludge workaround for setting last activity. Should fix.
	 *
	 * ## OPTIONS
	 *
	 * [--count=<number>]
	 * : How many members to generate.
	 * ---
	 * default: 100
	 * ---
	 *
	 * ## EXAMPLE
	 *
	 *     $ wp bp member generate --count=50
	 */
	public function generate( $args, $assoc_args ) {
		add_action( 'user_register', array( __CLASS__, 'update_user_last_activity_random' ) );
		User_Command::generate( $args, $assoc_args );
	}

	/**
	 * Update the last user activity with a random date.
	 *
	 * @since 1.0
	 *
	 * @param int $user_id User ID.
	 */
	public static function update_user_last_activity_random( $user_id ) {
		$time = date( 'Y-m-d H:i:s', rand( 0, time() ) );
		bp_update_user_last_activity( $user_id, $time );
	}
}
