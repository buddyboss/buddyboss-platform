<?php
/**
 * Login Information feedback tests.
 *
 * @package BuddyBoss\Tests
 */

/**
 * Login Information: feedback when a change is saved or not.
 *
 * A new password is only reported and notified as changed once it is saved,
 * a failed email verification is not reported as verified, and a
 * verification link with no pending change is ignored.
 *
 * @group settings
 */
class BP_Tests_Settings_BpSettingsLoginInformationFeedback extends BP_UnitTestCase {

	/**
	 * Password of the test members.
	 *
	 * @var string
	 */
	protected $password = 'password';

	/**
	 * Request method before the test.
	 *
	 * @var string|null
	 */
	protected $request_method;

	/**
	 * Remember the request method so tear_down() can restore it.
	 */
	public function set_up() {
		parent::set_up();

		$this->request_method = isset( $_SERVER['REQUEST_METHOD'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) : null;
	}

	/**
	 * Restore the request globals changed by the tests.
	 */
	public function tear_down() {
		$_POST = array();
		$_GET  = array();
		unset( $_REQUEST['_wpnonce'] );

		if ( null === $this->request_method ) {
			unset( $_SERVER['REQUEST_METHOD'] );
		} else {
			$_SERVER['REQUEST_METHOD'] = $this->request_method;
		}

		parent::tear_down();
	}

	/**
	 * When wp_update_user() returns a WP_Error the new password is not
	 * reported as saved, and no email or notification is sent.
	 */
	public function test_failed_password_update_is_reported_as_error() {
		$u     = $this->create_member( 'plain@example.com' );
		$login = get_userdata( $u )->user_login;
		$sent  = $this->record_emails();

		// wp_insert_user() rejects a disallowed username on updates too.
		$block = static function ( $logins ) use ( $login ) {
			$logins[] = $login;

			return $logins;
		};
		add_filter( 'illegal_user_logins', $block );

		$this->save_login_information(
			$u,
			array(
				'email' => 'plain@example.com',
				'pass1' => 'a-new-password',
				'pass2' => 'a-new-password',
			)
		);

		remove_filter( 'illegal_user_logins', $block );

		$user = get_userdata( $u );

		$this->assertFalse( wp_check_password( 'a-new-password', $user->user_pass, $u ) );
		$this->assertTrue( wp_check_password( $this->password, $user->user_pass, $u ) );
		$this->assertSame( 'Your password could not be updated. Please try again or contact the site administrator.', buddypress()->template_message );
		$this->assertSame( 'error', buddypress()->template_message_type );
		$this->assertCount( 0, $sent );
		$this->assertSame( 0, $this->count_password_notifications( $u ) );
	}

	/**
	 * A new password is not reported as changed when the email address on
	 * the same save has an error, because the password is not saved then.
	 */
	public function test_new_password_not_reported_when_email_has_error() {
		$this->create_member( 'taken@example.com' );

		$u    = $this->create_member( 'plain@example.com' );
		$sent = $this->record_emails();

		$this->save_login_information(
			$u,
			array(
				'email' => 'taken@example.com',
				'pass1' => 'a-new-password',
				'pass2' => 'a-new-password',
			)
		);

		$this->assertFalse( wp_check_password( 'a-new-password', get_userdata( $u )->user_pass, $u ) );
		$this->assertSame( 'That email address is already taken.', buddypress()->template_message );
		$this->assertSame( 'error', buddypress()->template_message_type );
		$this->assertCount( 0, $sent );
		$this->assertSame( 0, $this->count_password_notifications( $u ) );
	}

	/**
	 * A verification link is not reported as successful when the new address
	 * was taken by another account after the change was requested.
	 */
	public function test_verify_link_reports_error_when_address_was_taken() {
		$u = $this->create_member( 'plain@example.com' );

		$this->save_login_information( $u, array( 'email' => 'taken@example.com' ) );

		// Another account takes the address before the link is opened.
		$this->create_member( 'taken@example.com' );

		$this->verify_pending_email( $u );

		$this->assertSame( 'plain@example.com', get_userdata( $u )->user_email );
		$this->assertSame( 'error', buddypress()->template_message_type );
		$this->assertNotEmpty( bp_get_user_meta( $u, 'pending_email_change', true ) );
	}

	/**
	 * A saved password is reported as saved, and the member gets the
	 * password changed email and notification.
	 */
	public function test_saved_password_is_reported_and_notified() {
		$u    = $this->create_member( 'plain@example.com' );
		$sent = $this->record_emails();

		$this->save_login_information(
			$u,
			array(
				'email' => 'plain@example.com',
				'pass1' => 'a-new-password',
				'pass2' => 'a-new-password',
			)
		);

		$this->assertTrue( wp_check_password( 'a-new-password', get_userdata( $u )->user_pass, $u ) );
		$this->assertSame( 'Your settings have been saved.', buddypress()->template_message );
		$this->assertSame( 'success', buddypress()->template_message_type );
		$this->assertSame( array( 'settings-password-changed' ), $sent->getArrayCopy() );
		$this->assertSame( 1, $this->count_password_notifications( $u ) );
	}

	/**
	 * A verification link that was already used is ignored, because there is
	 * no pending change left to verify.
	 */
	public function test_used_verify_link_is_ignored() {
		$u = $this->create_member( 'plain@example.com' );

		$this->save_login_information( $u, array( 'email' => 'new@example.com' ) );

		$pending = bp_get_user_meta( $u, 'pending_email_change', true );

		$this->open_verify_link( $u, $pending['hash'] );
		$this->assertSame( 'new@example.com', get_userdata( $u )->user_email );

		// The member opens the same link again.
		$this->open_verify_link( $u, $pending['hash'] );

		$this->assertSame( 'new@example.com', get_userdata( $u )->user_email );
		$this->assertEmpty( buddypress()->template_message );
	}

	/**
	 * A verification link with an empty hash is ignored when there is no
	 * pending change, so the email address is kept.
	 */
	public function test_empty_verify_link_hash_is_ignored() {
		$u = $this->create_member( 'plain@example.com' );

		$this->open_verify_link( $u, '' );

		$this->assertSame( 'plain@example.com', get_userdata( $u )->user_email );
		$this->assertEmpty( buddypress()->template_message );
	}

	/**
	 * Create a member with the given email address.
	 *
	 * @param string $email Email address.
	 *
	 * @return int User ID.
	 */
	protected function create_member( $email ) {
		return self::factory()->user->create(
			array(
				'user_email' => $email,
				'user_pass'  => $this->password,
			)
		);
	}

	/**
	 * Open the Login Information screen of a member as that member.
	 *
	 * @param int $user_id Member whose screen is displayed.
	 */
	protected function open_login_information( $user_id ) {
		$this->set_current_user( $user_id );

		$bp                           = buddypress();
		$bp->displayed_user->id       = $user_id;
		$bp->displayed_user->userdata = bp_core_get_core_userdata( $user_id );
		$bp->displayed_user->domain   = bp_core_get_user_domain( $user_id );
		$bp->current_component        = 'settings';
		$bp->current_action           = 'general';

		// Each page load starts without a message.
		$bp->template_message      = '';
		$bp->template_message_type = '';
	}

	/**
	 * Submit the Login Information form the way a browser does.
	 *
	 * @param int   $user_id Member whose settings are saved.
	 * @param array $fields  Form fields. 'email' is the value shown in the field.
	 */
	protected function save_login_information( $user_id, $fields ) {
		$this->open_login_information( $user_id );

		$fields = array_merge(
			array(
				'submit' => 'Save Changes',
				'pwd'    => $this->password,
			),
			$fields
		);

		// WordPress adds slashes to $_POST.
		$_POST                     = wp_slash( $fields );
		$_REQUEST['_wpnonce']      = wp_create_nonce( 'bp_settings_general' );
		$_SERVER['REQUEST_METHOD'] = 'POST';

		bp_settings_action_general();
	}

	/**
	 * Record the type of every email sent through bp_send_email().
	 *
	 * The filter doesn't get the email type, so it's read from the tokens.
	 *
	 * @return ArrayObject Email types, filled while the test runs.
	 */
	protected function record_emails() {
		$sent = new ArrayObject();

		add_filter(
			'bp_before_send_email_parse_args',
			static function ( $args ) use ( $sent ) {
				if ( isset( $args['tokens']['verify.url'] ) ) {
					$sent[] = 'settings-verify-email-change';
				} elseif ( isset( $args['tokens']['reset.url'] ) ) {
					$sent[] = 'settings-password-changed';
				} else {
					$sent[] = 'other';
				}

				return $args;
			}
		);

		return $sent;
	}

	/**
	 * Count the password changed notifications of a member.
	 *
	 * @param int $user_id Member ID.
	 *
	 * @return int Number of notifications, read or unread.
	 */
	protected function count_password_notifications( $user_id ) {
		$notifications = BP_Notifications_Notification::get(
			array(
				'user_id'          => $user_id,
				'component_name'   => buddypress()->members->id,
				'component_action' => 'bb_account_password',
				'is_new'           => 'both',
			)
		);

		return count( $notifications );
	}

	/**
	 * Open the verification link of the pending email change.
	 *
	 * @param int $user_id Member opening their own link.
	 */
	protected function verify_pending_email( $user_id ) {
		$pending = bp_get_user_meta( $user_id, 'pending_email_change', true );

		$this->open_verify_link( $user_id, $pending['hash'] );
	}

	/**
	 * Open an email verification link.
	 *
	 * The handler redirects and dies. Throwing from `wp_redirect` (before the
	 * suite-wide `__return_false` callback) stops it before die().
	 *
	 * @param int    $user_id Member opening their own link.
	 * @param string $hash    Hash in the link.
	 */
	protected function open_verify_link( $user_id, $hash ) {
		$this->open_login_information( $user_id );

		$_GET['verify_email_change'] = $hash;

		add_filter(
			'wp_redirect',
			static function ( $location ) {
				throw new Exception( esc_url_raw( $location ) );
			},
			1
		);

		try {
			bp_settings_verify_email_change();
		} catch ( Exception $e ) {
			unset( $e );
		}
	}
}
