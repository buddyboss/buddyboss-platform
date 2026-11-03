<?php
/**
 * Presence and last-activity tests for View As (PROD-10627).
 *
 * Covers:
 * - the bb_pre_update_user_last_activity filter in bp_update_user_last_activity();
 * - the presence endpoints' user lookup, which used to trust the username in an
 *   unsigned auth cookie and an unsigned app JWT;
 * - the app (JWT) and web (old user cookie) View As detection that stops the
 *   presence endpoints from recording activity for the viewed-as member.
 *
 * @group core
 * @group presence
 */
class BB_Tests_Core_Presence extends BP_UnitTestCase {

	/**
	 * Cookies replaced by a test.
	 *
	 * @var array
	 */
	protected $old_cookie = array();

	/**
	 * Server values replaced by a test.
	 *
	 * @var array
	 */
	protected $old_server = array();

	/**
	 * App JWT secret used by the tests.
	 *
	 * @var string
	 */
	protected $secret = 'bb-presence-test-secret';

	public function set_up() {
		parent::set_up();

		if ( ! class_exists( 'BB_Presence' ) ) {
			require_once buddypress()->plugin_dir . 'bp-core/classes/class-bb-presence.php';
		}

		$this->old_cookie = $_COOKIE;
		$this->old_server = $_SERVER;

		BB_Presence::$is_switched_user = false;
		update_option( 'bbapp_jwt_secret', $this->secret );
	}

	public function tear_down() {
		$_COOKIE = $this->old_cookie;
		$_SERVER = $this->old_server;

		BB_Presence::$is_switched_user = false;
		delete_option( 'bbapp_jwt_secret' );

		parent::tear_down();
	}

	/**
	 * Base64url encode, as JWT does.
	 *
	 * @param string $data Raw data.
	 *
	 * @return string
	 */
	protected function b64url( $data ) {
		return rtrim( strtr( base64_encode( $data ), '+/', '-_' ), '=' );
	}

	/**
	 * Build an app access token the way the app's Firebase JWT encoder does.
	 *
	 * @param int    $user_id Token user.
	 * @param string $jti     Token ID.
	 * @param string $secret  Signing secret.
	 *
	 * @return string
	 */
	protected function make_jwt( $user_id, $jti, $secret ) {
		$header  = $this->b64url( wp_json_encode( array( 'typ' => 'JWT', 'alg' => 'HS256' ) ) );
		$payload = $this->b64url(
			wp_json_encode(
				array(
					'iss'  => home_url(),
					'exp'  => time() + HOUR_IN_SECONDS,
					'jti'  => $jti,
					'data' => array( 'user' => array( 'id' => $user_id ) ),
				)
			)
		);

		return $header . '.' . $payload . '.' . $this->b64url( hash_hmac( 'sha256', $header . '.' . $payload, $secret, true ) );
	}

	/**
	 * Mint a real logged_in cookie value backed by a session token.
	 *
	 * @param int $user_id User.
	 *
	 * @return string
	 */
	protected function make_logged_in_cookie( $user_id ) {
		$expiration = time() + HOUR_IN_SECONDS;
		$token      = WP_Session_Tokens::get_instance( $user_id )->create( $expiration );

		return wp_generate_auth_cookie( $user_id, $expiration, 'logged_in', $token );
	}

	public function test_pre_update_filter_skips_last_activity() {
		$u = self::factory()->user->create();
		bp_update_user_last_activity( $u, '2020-01-01 00:00:00' );

		add_filter( 'bb_pre_update_user_last_activity', '__return_true' );
		$result = bp_update_user_last_activity( $u, '2026-01-01 00:00:00' );
		remove_filter( 'bb_pre_update_user_last_activity', '__return_true' );

		$this->assertFalse( $result );
		$this->assertSame( '2020-01-01 00:00:00', bp_get_user_last_activity( $u ) );
	}

	public function test_pre_update_filter_default_records_last_activity() {
		$u = self::factory()->user->create();
		bp_update_user_last_activity( $u, '2020-01-01 00:00:00' );
		bp_update_user_last_activity( $u, '2026-01-01 00:00:00' );

		$this->assertSame( '2026-01-01 00:00:00', bp_get_user_last_activity( $u ) );
	}

	public function test_cookie_support_rejects_forged_cookie() {
		$u = self::factory()->user->create( array( 'user_login' => 'presence_forged' ) );

		$_COOKIE[ LOGGED_IN_COOKIE ] = 'presence_forged|9999999999|x|x';

		$this->assertSame( 0, BB_Presence::instance()->bb_cookie_support( 0 ) );
		$this->assertNotSame( $u, BB_Presence::instance()->bb_cookie_support( 0 ) );
	}

	public function test_cookie_support_accepts_valid_cookie() {
		$u = self::factory()->user->create();

		$_COOKIE[ LOGGED_IN_COOKIE ] = $this->make_logged_in_cookie( $u );

		$this->assertSame( $u, BB_Presence::instance()->bb_cookie_support( 0 ) );
	}

	public function test_jwt_support_rejects_forged_signature() {
		$u   = self::factory()->user->create();
		$jti = 'jti-forged';
		update_user_meta( $u, '_bbapp_jwt_jti', array( $jti => array() ) );

		$_SERVER['HTTP_ACCESSTOKEN'] = $this->make_jwt( $u, $jti, 'not-the-secret' );

		$this->assertSame( 0, BB_Presence::instance()->bb_jwt_auth_support( 0 ) );
	}

	public function test_jwt_support_rejects_revoked_token() {
		$u = self::factory()->user->create();
		update_user_meta( $u, '_bbapp_jwt_jti', array( 'other-jti' => array() ) );

		$_SERVER['HTTP_ACCESSTOKEN'] = $this->make_jwt( $u, 'revoked-jti', $this->secret );

		$this->assertSame( 0, BB_Presence::instance()->bb_jwt_auth_support( 0 ) );
	}

	public function test_jwt_support_accepts_valid_token_without_switch() {
		$u   = self::factory()->user->create();
		$jti = 'jti-valid';
		update_user_meta( $u, '_bbapp_jwt_jti', array( $jti => array() ) );

		$_SERVER['HTTP_ACCESSTOKEN'] = $this->make_jwt( $u, $jti, $this->secret );

		$this->assertSame( $u, (int) BB_Presence::instance()->bb_jwt_auth_support( 0 ) );
		$this->assertFalse( BB_Presence::$is_switched_user );
	}

	public function test_jwt_support_flags_app_view_as() {
		$admin  = self::factory()->user->create( array( 'role' => 'administrator' ) );
		$member = self::factory()->user->create();
		$jti    = 'jti-switched';
		update_user_meta( $admin, '_bbapp_jwt_jti', array( $jti => array() ) );
		update_user_meta( $admin, '_bbapp_jwt_switch_user', array( $jti => $member ) );

		$_SERVER['HTTP_ACCESSTOKEN'] = $this->make_jwt( $admin, $jti, $this->secret );

		$this->assertSame( $member, BB_Presence::instance()->bb_jwt_auth_support( 0 ) );
		$this->assertTrue( BB_Presence::$is_switched_user );
	}

	public function test_web_member_switched_detection() {
		$admin = self::factory()->user->create( array( 'role' => 'administrator' ) );
		$name  = defined( 'BP_MEMBER_SWITCHING_OLDUSER_COOKIE' ) ? BP_MEMBER_SWITCHING_OLDUSER_COOKIE : 'buddyboss_user_sw_olduser_' . COOKIEHASH;

		$member = self::factory()->user->create();

		unset( $_COOKIE[ $name ] );
		$this->assertFalse( BB_Presence::instance()->bb_is_web_member_switched( $member ) );

		$_COOKIE[ $name ] = 'admin|9999999999|x|x';
		$this->assertFalse( BB_Presence::instance()->bb_is_web_member_switched( $member ) );

		$_COOKIE[ $name ] = $this->make_logged_in_cookie( $admin );
		$this->assertTrue( BB_Presence::instance()->bb_is_web_member_switched( $member ) );

		// A leftover old user cookie for the admin's own account is not a switch.
		$this->assertFalse( BB_Presence::instance()->bb_is_web_member_switched( $admin ) );
	}
}
