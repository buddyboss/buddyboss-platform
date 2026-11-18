<?php
/**
 * Login Information feedback tests.
 *
 * @package BuddyBoss\Tests
 */

/**
 * Login Information: feedback when a change is not saved.
 *
 * A new password is only reported and notified as changed once it is saved,
 * and a failed email verification is not reported as verified.
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
	 * reported as saved and no email is sent.
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
	 * @return ArrayObject Email types, filled while the test runs.
	 */
	protected function record_emails() {
		$sent = new ArrayObject();

		add_filter(
			'bp_before_send_email_parse_args',
			static function ( $args ) use ( $sent ) {
				$sent[] = isset( $args['tokens']['verify.url'] ) ? 'settings-verify-email-change' : 'other';

				return $args;
			}
		);

		return $sent;
	}

	/**
	 * Open the verification link of the pending email change.
	 *
	 * The handler redirects and dies. Throwing from `wp_redirect` (before the
	 * suite-wide `__return_false` callback) stops it before die().
	 *
	 * @param int $user_id Member opening their own link.
	 */
	protected function verify_pending_email( $user_id ) {
		$pending = bp_get_user_meta( $user_id, 'pending_email_change', true );

		$this->open_login_information( $user_id );

		$_GET['verify_email_change'] = $pending['hash'];

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
