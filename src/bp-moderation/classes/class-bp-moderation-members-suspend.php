<?php
/**
 * BuddyBoss Moderation Groups Classes
 *
 * @package BuddyBoss\Moderation
 * @since   BuddyBoss 1.5.4
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Database interaction class for the BuddyBoss moderation Members.
 *
 * @since BuddyBoss 1.5.4
 */
class BP_Moderation_Members_Suspend {


	/**
	 * BP_Moderation_Group constructor.
	 *
	 * @since BuddyBoss 1.5.4
	 */
	public function __construct() {

		/**
		 * Moderation code should not add for WordPress backend
		 */
		if ( is_admin() && ! wp_doing_ajax() ) {
			return;
		}

		add_filter( 'authenticate', array( $this, 'boot_suspended_user' ), 30 );
		add_filter( 'bp_init', array( $this, 'bp_stop_live_suspended' ), 5 );
		add_action( 'login_form_bp-suspended', array( $this, 'bp_live_suspended_login_error' ) );
		add_filter( 'bp_init', array( $this, 'restrict_member_profile' ), 4 );
	}

	/**
	 * Prevent Suspended from logging in.
	 *
	 * When a user logs in, check if they have been marked as a Suspended. If yes
	 * then simply redirect them to the home page and stop them from logging in.
	 *
	 * @since BuddyBoss 1.5.4
	 *
	 * @param WP_User|WP_Error $user Either the WP_User object or the WP_Error
	 *                               object, as passed to the 'authenticate' filter.
	 *
	 * @return WP_User|WP_Error If the user is not a Suspended, return the WP_User
	 *                          object. Otherwise a new WP_Error object.
	 */
	public function boot_suspended_user( $user ) {
		// Check to see if the $user has already failed logging in, if so return $user as-is.
		if ( is_wp_error( $user ) || empty( $user ) ) {
			return $user;
		}

		// The user exists; now do a check to see if the user is a suspended
		if ( is_a( $user, 'WP_User' ) && bp_moderation_is_user_suspended( $user->id ) ) {
			return new WP_Error( 'invalid_username', __( '<strong>ERROR</strong>: Your account has been Suspended.', 'buddyboss' ) );
		}

		// User is good to go!
		return $user;
	}

	/**
	 * Stop a logged-in user who is marked as a suspended.
	 *
	 * When an admin marks a live user account as a suspended, that user can still surf
	 * around and cause havoc on the site until that person is logged out.
	 *
	 * This code checks to see if a logged-in user account is marked as a suspended.  If so,
	 * we redirect the user back to wp-login.php with the 'reauth' parameter.
	 *
	 * This clears the logged-in suspender's cookies and will ask the suspended to
	 * reauthenticate.
	 *
	 * Note: A suspender cannot log back in - {@see boot_suspended_user()}.
	 *
	 * Runs on 'bp_init' at priority 4 so the members component globals are setup
	 * before we do our spammer checks.
	 *
	 * This is important as the $bp->loggedin_user object is setup at priority 4.
	 *
	 * @since BuddyBoss 1.5.4
	 */
	public function bp_stop_live_suspended() {
		// If we're on the login page, stop now to prevent redirect loop.
		$is_login = false;
		if ( isset( $GLOBALS['pagenow'] ) && ( false !== strpos( $GLOBALS['pagenow'], 'wp-login.php' ) ) ) {
			$is_login = true;
		} elseif ( isset( $_SERVER['SCRIPT_NAME'] ) && false !== strpos( $_SERVER['SCRIPT_NAME'], 'wp-login.php' ) ) {
			$is_login = true;
		}

		if ( $is_login ) {
			return;
		}

		// User isn't logged in, so stop!
		if ( ! is_user_logged_in() ) {
			return;
		}

		$user_id            = bp_loggedin_user_id();
		if (  bp_moderation_is_user_suspended( $user_id ) ) {
			// Setup login args.
			$args = array(
				// Custom action used to throw an error message.
				'action' => 'bp-suspended',

				// Reauthorize user to login.
				'reauth' => 1,
			);

			/**
			 * Filters the url used for redirection for a logged in user marked as spam.
			 *
			 * @since BuddyPress 1.8.0
			 *
			 * @param string $value URL to redirect user to.
			 */
			$login_url = apply_filters( 'bp_live_suspend_redirect', add_query_arg( $args, wp_login_url() ) );

			// Redirect user to login page.
			wp_safe_redirect( $login_url );
			die();
		}
	}

	/**
	 * Show a custom error message when a logged-in user is marked as a suspended.
	 *
	 * @since BuddyBoss 1.5.4
	 */
	public function bp_live_suspended_login_error() {
		global $error;

		$error = __( '<strong>ERROR</strong>: Your account has been suspended.', 'buddyboss' );

		// Shake shake shake!
		add_action( 'login_head', 'wp_shake_js', 12 );
	}

	/**
	 * If the displayed user is marked as a suspended, Show 404.
	 *
	 * @since BuddyBoss 1.5.4
	 */
	public function restrict_member_profile() {
		$user_id            = bp_displayed_user_id();
		$hidden_members_ids = BP_Moderation_Members::get_sitewide_hidden_ids();
		if ( in_array( $user_id, $hidden_members_ids, true ) ) {
			buddypress()->displayed_user->id = 0;
			bp_do_404();

			return;
		}
	}
}
