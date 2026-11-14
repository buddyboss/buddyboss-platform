<?php
/**
 * Login Information: email address handling in bp_settings_action_general().
 *
 * An address containing an apostrophe or ampersand used to be run through
 * esc_html() before it was compared with the saved one, so it never matched
 * and every save created a pending email change to a mangled address.
 *
 * @group settings
 * @group PROD-10861
 */
class BP_Tests_Settings_BpSettingsActionGeneral extends BP_UnitTestCase {

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

		$this->request_method = isset( $_SERVER['REQUEST_METHOD'] ) ? $_SERVER['REQUEST_METHOD'] : null;
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
	 * Open the Login Information screen of a member.
	 *
	 * @param int      $user_id Member whose screen is displayed.
	 * @param int|null $actor   Logged-in user. Defaults to the member.
	 */
	protected function open_login_information( $user_id, $actor = null ) {
		$this->set_current_user( null === $actor ? $user_id : $actor );

		$bp                           = buddypress();
		$bp->displayed_user->id       = $user_id;
		$bp->displayed_user->userdata = bp_core_get_core_userdata( $user_id );
		$bp->displayed_user->domain   = bp_core_get_user_domain( $user_id );
		$bp->current_component        = 'settings';
		$bp->current_action           = 'general';
	}

	/**
	 * Submit the Login Information form, the way a browser does.
	 *
	 * @param int      $user_id Member whose settings are saved.
	 * @param array    $fields  Form fields. 'email' is the value shown in the field.
	 * @param int|null $actor   Logged-in user. Defaults to the member. An
	 *                          administrator's form has no current password field.
	 */
	protected function save_login_information( $user_id, $fields, $actor = null ) {
		$this->open_login_information( $user_id, $actor );

		$defaults = array( 'submit' => 'Save Changes' );
		if ( null === $actor ) {
			$defaults['pwd'] = $this->password;
		}

		$fields = array_merge( $defaults, $fields );

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
				throw new Exception( $location );
			},
			1
		);

		try {
			bp_settings_verify_email_change();
		} catch ( Exception $e ) {
			unset( $e );
		}
	}

	/**
	 * The reported bug: an untouched address with an apostrophe is not a change.
	 */
	public function test_unchanged_email_with_apostrophe_is_not_a_change() {
		$email = "qa'test@example.com";
		$u     = $this->create_member( $email );

		$notice = new MockAction();
		add_filter( 'send_email_change_email', array( $notice, 'filter' ) );

		$this->save_login_information(
			$u,
			array(
				'email' => $email,
				'pass1' => 'a-new-password',
				'pass2' => 'a-new-password',
			)
		);

		$user = get_userdata( $u );

		$this->assertSame( '', bp_get_user_meta( $u, 'pending_email_change', true ) );
		$this->assertSame( $email, $user->user_email );
		$this->assertTrue( wp_check_password( 'a-new-password', $user->user_pass, $u ) );

		// WordPress must not be told the address changed either.
		$this->assertSame( 0, $notice->get_call_count() );
	}

	/**
	 * Saving without touching anything reports that nothing changed.
	 */
	public function test_unchanged_email_with_apostrophe_reports_no_changes() {
		$email = "qa'test@example.com";
		$u     = $this->create_member( $email );

		$this->save_login_information( $u, array( 'email' => $email ) );

		$this->assertSame( '', bp_get_user_meta( $u, 'pending_email_change', true ) );
		$this->assertSame( 'No changes were made to your account.', buddypress()->template_message );
	}

	/**
	 * WordPress saves "&" as "&amp;", the field shows "&". Saving the form
	 * without a new password must leave the saved address exactly as it was.
	 */
	public function test_unchanged_email_with_ampersand_is_not_a_change() {
		$u      = $this->create_member( 'qa&test@example.com' );
		$stored = get_userdata( $u )->user_email;
		$sent   = $this->record_emails();

		$updated = new MockAction();
		add_action( 'profile_update', array( $updated, 'action' ) );

		// The ticket saves twice: the second save used to see the rewritten address.
		$this->save_login_information( $u, array( 'email' => 'qa&test@example.com' ) );
		$this->save_login_information( $u, array( 'email' => 'qa&test@example.com' ) );

		$this->assertSame( 'qa&amp;test@example.com', $stored );
		$this->assertSame( $stored, get_userdata( $u )->user_email );
		$this->assertSame( '', bp_get_user_meta( $u, 'pending_email_change', true ) );
		$this->assertSame( 'No changes were made to your account.', buddypress()->template_message );
		$this->assertCount( 0, $sent );

		// Nothing was written, so the user is not updated at all.
		$this->assertSame( 0, $updated->get_call_count() );
	}

	/**
	 * A new password is saved for a member whose address has "&", without a
	 * pending email change or a verification email.
	 *
	 * The saved address itself is not asserted: wp_update_user() re-encodes a
	 * saved "&amp;" on every update, see https://core.trac.wordpress.org/ticket/16788.
	 */
	public function test_new_password_with_ampersand_email_is_saved() {
		$u    = $this->create_member( 'qa&test@example.com' );
		$sent = $this->record_emails();

		$this->save_login_information(
			$u,
			array(
				'email' => 'qa&test@example.com',
				'pass1' => 'a-new-password',
				'pass2' => 'a-new-password',
			)
		);

		$this->assertTrue( wp_check_password( 'a-new-password', get_userdata( $u )->user_pass, $u ) );
		$this->assertSame( '', bp_get_user_meta( $u, 'pending_email_change', true ) );
		$this->assertSame( 'Your settings have been saved.', buddypress()->template_message );
		$this->assertNotContains( 'settings-verify-email-change', $sent->getArrayCopy() );
	}

	/**
	 * An administrator saving a member's Login Information without changes
	 * leaves the member's address alone.
	 */
	public function test_admin_saving_member_without_changes_keeps_email() {
		$admin = self::factory()->user->create( array( 'role' => 'administrator' ) );
		$this->grant_super_admin( $admin );

		$sent = $this->record_emails();

		foreach ( array( 'qa&test@example.com', "qa'test@example.com" ) as $email ) {
			$u      = $this->create_member( $email );
			$stored = get_userdata( $u )->user_email;

			$this->save_login_information( $u, array( 'email' => $email ), $admin );

			$this->assertSame( $stored, get_userdata( $u )->user_email, $email );
			$this->assertSame( '', bp_get_user_meta( $u, 'pending_email_change', true ), $email );
			$this->assertSame( 'No changes were made to this account.', buddypress()->template_message, $email );
		}

		$this->assertCount( 0, $sent );
	}

	/**
	 * A real change to an address with an apostrophe keeps the address as typed.
	 */
	public function test_new_email_with_apostrophe_is_saved_as_typed() {
		$u = $this->create_member( 'plain@example.com' );

		$this->save_login_information( $u, array( 'email' => "s.o'neil@example.com" ) );

		$pending = bp_get_user_meta( $u, 'pending_email_change', true );

		$this->assertSame( "s.o'neil@example.com", $pending['newemail'] );
		$this->assertSame( 'plain@example.com', get_userdata( $u )->user_email );

		$this->verify_pending_email( $u );

		$this->assertSame( "s.o'neil@example.com", get_userdata( $u )->user_email );
		$this->assertSame( '', bp_get_user_meta( $u, 'pending_email_change', true ) );
	}

	/**
	 * A regular address still goes through the pending change.
	 */
	public function test_new_email_is_still_pending_until_verified() {
		$u = $this->create_member( 'plain@example.com' );

		$sent = $this->record_emails();

		$this->save_login_information( $u, array( 'email' => 'plain.new@example.com' ) );

		$pending = bp_get_user_meta( $u, 'pending_email_change', true );

		$this->assertSame( 'plain.new@example.com', $pending['newemail'] );
		$this->assertSame( 'plain@example.com', get_userdata( $u )->user_email );
		$this->assertSame( 'Your settings have been saved.', buddypress()->template_message );
		$this->assertSame( array( 'settings-verify-email-change' ), $sent->getArrayCopy() );
	}

	/**
	 * Only the password is sent to wp_update_user(), so the other fields of
	 * the member are saved back exactly as they were. A new password is set
	 * so that wp_update_user() runs.
	 */
	public function test_other_user_fields_are_left_alone() {
		$u = $this->create_member( 'plain@example.com' );

		wp_update_user(
			array(
				'ID'           => $u,
				'display_name' => wp_slash( 'AC\DC' ),
			)
		);

		$this->save_login_information(
			$u,
			array(
				'email' => 'plain@example.com',
				'pass1' => 'a-new-password',
				'pass2' => 'a-new-password',
			)
		);

		$this->assertTrue( wp_check_password( 'a-new-password', get_userdata( $u )->user_pass, $u ) );
		$this->assertSame( 'AC\DC', get_userdata( $u )->display_name );
	}
}
