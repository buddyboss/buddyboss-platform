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
	 * Make the member the logged-in user on their own Login Information screen.
	 *
	 * @param int $user_id Member.
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
	 * Submit the Login Information form as the member, the way a browser does.
	 *
	 * @param int   $user_id Member saving their own settings.
	 * @param array $fields  Form fields. 'email' is the value shown in the field.
	 */
	protected function save_login_information( $user_id, $fields ) {
		$this->open_login_information( $user_id );

		$fields = array_merge(
			array(
				'pwd'    => $this->password,
				'submit' => 'Save Changes',
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
	 * WordPress saves "&" as "&amp;", the field shows "&".
	 */
	public function test_unchanged_email_with_ampersand_is_not_a_change() {
		$u = $this->create_member( 'qa&test@example.com' );

		$this->save_login_information( $u, array( 'email' => 'qa&test@example.com' ) );

		/*
		 * Only the pending change is checked. wp_update_user() rewrites a saved
		 * "&amp;" address by itself, see https://core.trac.wordpress.org/ticket/16788.
		 */
		$this->assertSame( '', bp_get_user_meta( $u, 'pending_email_change', true ) );
		$this->assertSame( 'No changes were made to your account.', buddypress()->template_message );
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

		$this->save_login_information( $u, array( 'email' => 'plain.new@example.com' ) );

		$pending = bp_get_user_meta( $u, 'pending_email_change', true );

		$this->assertSame( 'plain.new@example.com', $pending['newemail'] );
		$this->assertSame( 'plain@example.com', get_userdata( $u )->user_email );
		$this->assertSame( 'Your settings have been saved.', buddypress()->template_message );
	}

	/**
	 * Only the password is sent to wp_update_user(), so the other fields of
	 * the member are saved back exactly as they were.
	 */
	public function test_other_user_fields_are_left_alone() {
		$u = $this->create_member( 'plain@example.com' );

		wp_update_user(
			array(
				'ID'           => $u,
				'display_name' => wp_slash( 'AC\DC' ),
			)
		);

		$this->save_login_information( $u, array( 'email' => 'plain@example.com' ) );

		$this->assertSame( 'AC\DC', get_userdata( $u )->display_name );
	}
}
