<?php

/**
 * Tests for the BuddyBoss Support Access feature.
 *
 * Covers the member-query exclusion plus the security-critical paths: token
 * lifecycle (enable -> login gate -> disable), expiry enforcement (lazy + cron),
 * the support-user collision guard, ticket persistence semantics, and the
 * support-system notify rate-limit lifecycle.
 *
 * @group core
 * @group support_access
 */
class BB_Tests_Support_Access extends BP_UnitTestCase {

	/**
	 * Singleton under test.
	 *
	 * @var BB_Support_Access
	 */
	protected $sa;

	/**
	 * Ensure the BB_Support_Access singleton (and its query filter) is loaded.
	 */
	public function set_up() {
		parent::set_up();

		if ( ! class_exists( 'BB_Support_Access' ) ) {
			require_once buddypress()->plugin_dir . 'bp-core/admin/classes/class-bb-support-access.php';
		}

		// Self-boots and registers the bp_user_query_uid_clauses filter.
		$this->sa = BB_Support_Access::instance();
	}

	/**
	 * Clean up request state and HTTP/veto filters between tests.
	 */
	public function tear_down() {
		unset( $_GET[ BB_Support_Access::QUERY_VAR ] );
		remove_all_filters( 'pre_http_request' );
		remove_all_filters( 'wp_redirect' );
		remove_all_filters( 'bb_support_access_allow_login' );
		bp_delete_option( BB_Support_Access::OPTION );

		parent::tear_down();
	}

	/* Helpers ---------------------------------------------------------------- */

	/**
	 * Create a stand-in support account flagged the same way the real one is.
	 *
	 * @return int Support user ID.
	 */
	protected function create_support_user() {
		$user_id = self::factory()->user->create(
			array(
				'user_login'   => BB_Support_Access::USER_LOGIN,
				'user_email'   => BB_Support_Access::USER_EMAIL,
				'display_name' => 'BuddyBoss Support',
				'role'         => 'administrator',
			)
		);

		update_user_meta( $user_id, '_bb_support_access_user', 1 );

		return (int) $user_id;
	}

	/**
	 * Extract the raw token from a login URL.
	 *
	 * @param string $url Login URL.
	 * @return string Raw token, or '' if absent.
	 */
	protected function token_from_url( $url ) {
		$query = (string) wp_parse_url( $url, PHP_URL_QUERY );
		parse_str( $query, $args );

		return isset( $args[ BB_Support_Access::QUERY_VAR ] ) ? (string) $args[ BB_Support_Access::QUERY_VAR ] : '';
	}

	/**
	 * Force the active grant's expiry into the past.
	 */
	protected function expire_grant_now() {
		$data            = $this->sa->get_data();
		$data['expires'] = time() - HOUR_IN_SECONDS;
		bp_update_option( BB_Support_Access::OPTION, $data );
	}

	/**
	 * Mock all outbound HTTP with a successful support-system response.
	 */
	protected function mock_http_success() {
		add_filter(
			'pre_http_request',
			static function () {
				return array(
					'response' => array( 'code' => 201 ),
					'body'     => wp_json_encode( array( 'success' => true, 'thread_id' => 'thr_1' ) ),
				);
			},
			10,
			3
		);
	}

	/**
	 * Mock all outbound HTTP with a transport failure.
	 */
	protected function mock_http_failure() {
		add_filter(
			'pre_http_request',
			static function () {
				return new WP_Error( 'http_request_failed', 'mocked transport failure' );
			},
			10,
			3
		);
	}

	/* Member-query exclusion ------------------------------------------------- */

	/**
	 * The support account must not appear in a BP_User_Query result set.
	 */
	public function test_support_user_excluded_from_user_query() {
		$support_id = $this->create_support_user();
		$regular_id = self::factory()->user->create();

		$q       = new BP_User_Query();
		$results = is_array( $q->results ) ? array_values( $q->results ) : array();
		$ids     = wp_list_pluck( $results, 'ID' );

		$this->assertNotContains( $support_id, $ids, 'Support account should be hidden from member queries.' );
		$this->assertContains( $regular_id, $ids, 'Regular members must still be returned.' );
	}

	/**
	 * Excluding the support account must not drop everyone when none exists.
	 */
	public function test_no_support_user_does_not_alter_results() {
		$regular_id = self::factory()->user->create();

		$q       = new BP_User_Query();
		$results = is_array( $q->results ) ? array_values( $q->results ) : array();
		$ids     = wp_list_pluck( $results, 'ID' );

		$this->assertContains( $regular_id, $ids );
	}

	/**
	 * The exclusion filter must be wired up by the singleton constructor.
	 */
	public function test_exclusion_filter_registered() {
		$this->assertNotFalse(
			has_filter( 'bp_user_query_uid_clauses', array( BB_Support_Access::instance(), 'exclude_from_user_query' ) ),
			'bp_user_query_uid_clauses exclusion filter should be registered.'
		);
	}

	/* Durations -------------------------------------------------------------- */

	/**
	 * Allowed durations are the short windows the UI offers.
	 */
	public function test_allowed_days_are_short_windows() {
		$this->assertSame( array( 1, 3, 5, 7 ), $this->sa->allowed_days() );
	}

	/**
	 * Enabling without a duration uses the default initial window (independent
	 * of the shorter extend increments in allowed_days()).
	 */
	public function test_enable_uses_default_duration_when_unspecified() {
		$before = time();
		$result = $this->sa->enable();

		$expected = $before + ( BB_Support_Access::DEFAULT_DAYS * DAY_IN_SECONDS );
		$this->assertEqualsWithDelta( $expected, $result['expires'], 5 );
	}

	/* Token lifecycle -------------------------------------------------------- */

	/**
	 * enable() creates a live grant and stores only the token hash, never the
	 * raw token.
	 */
	public function test_enable_creates_active_grant_storing_only_token_hash() {
		$result = $this->sa->enable( 7 );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'login_url', $result );
		$this->assertTrue( $this->sa->is_active(), 'Access should be active after enable().' );

		$token = $this->token_from_url( $result['login_url'] );
		$this->assertNotSame( '', $token );

		$data = $this->sa->get_data();
		$this->assertSame( hash( 'sha256', $token ), $data['token_hash'], 'Stored hash must match SHA-256 of the issued token.' );
		$this->assertNotSame( $token, $data['token_hash'], 'The raw token must never be stored.' );
		$this->assertGreaterThan( time(), $data['expires'] );
	}

	/**
	 * An out-of-range duration falls back to the default (not the raw value).
	 */
	public function test_enable_invalid_days_falls_back_to_default_duration() {
		$before = time();
		$result = $this->sa->enable( 999 );

		$expected = $before + ( BB_Support_Access::DEFAULT_DAYS * DAY_IN_SECONDS );

		// Allow a few seconds of clock drift during the call.
		$this->assertEqualsWithDelta( $expected, $result['expires'], 5 );
	}

	/**
	 * disable() revokes the grant and deletes the stored token.
	 */
	public function test_disable_revokes_grant() {
		$this->sa->enable( 7 );
		$this->assertTrue( $this->sa->is_active() );

		$this->sa->disable();

		$this->assertFalse( $this->sa->is_active(), 'Access must be inactive after disable().' );
		$this->assertSame( '', $this->sa->get_data()['token_hash'], 'Token hash must be cleared on disable.' );
	}

	/* Support user collision guard ------------------------------------------ */

	/**
	 * get_support_user() must NOT adopt a pre-existing, unflagged account that
	 * merely shares the canonical support email/login — it returns a WP_Error.
	 */
	public function test_get_support_user_refuses_unflagged_collision() {
		// A real admin who happens to own the canonical support email.
		self::factory()->user->create(
			array(
				'user_login' => 'real-admin',
				'user_email' => BB_Support_Access::USER_EMAIL,
				'role'       => 'administrator',
			)
		);

		$result = $this->sa->get_support_user();

		$this->assertWPError( $result, 'A canonical email/login collision with an unflagged user must error, not adopt.' );
		$this->assertSame( 'bb_support_user_conflict', $result->get_error_code() );
	}

	/**
	 * get_support_user() reuses an account this plugin previously flagged.
	 */
	public function test_get_support_user_reuses_flagged_account() {
		$flagged = $this->create_support_user();

		$this->assertSame( $flagged, $this->sa->get_support_user() );
	}

	/* Login gate (handle_login) --------------------------------------------- */
	/*
	 * handle_login() exits on the success path, so these tests exercise it via
	 * the `bb_support_access_allow_login` veto gate — which runs only AFTER the
	 * token has been validated, expiry checked, and the support user loaded.
	 * Reaching the gate with the correct user therefore proves the security
	 * decision succeeded, without invoking wp_set_auth_cookie()/exit.
	 */

	/**
	 * A valid, live token passes every check and reaches the auth gate with the
	 * support user.
	 */
	public function test_handle_login_valid_token_reaches_auth_gate_with_support_user() {
		$result = $this->sa->enable( 7 );
		$token  = $this->token_from_url( $result['login_url'] );

		$gate_user = 0;
		add_filter(
			'bb_support_access_allow_login',
			static function ( $allow, $user ) use ( &$gate_user ) {
				$gate_user = (int) $user->ID;
				return false; // Abort cleanly before the cookie/redirect/exit.
			},
			10,
			3
		);

		$_GET[ BB_Support_Access::QUERY_VAR ] = $token;
		$this->sa->handle_login();

		$this->assertSame( $this->sa->get_data()['support_user_id'], $gate_user, 'Valid token must reach the gate with the support user.' );
		$this->assertNotSame( $gate_user, get_current_user_id(), 'Vetoed login must not authenticate.' );
	}

	/**
	 * An invalid token is rejected (no auth gate reached) and fires the failure
	 * action so monitoring can observe it.
	 */
	public function test_handle_login_rejects_invalid_token() {
		$this->sa->enable( 7 );

		$gate_reached = false;
		add_filter(
			'bb_support_access_allow_login',
			static function ( $allow ) use ( &$gate_reached ) {
				$gate_reached = true;
				return $allow;
			},
			10,
			3
		);

		$failed = false;
		add_action(
			'bb_support_access_login_failed',
			static function () use ( &$failed ) {
				$failed = true;
			}
		);

		$_GET[ BB_Support_Access::QUERY_VAR ] = 'not-the-real-token';
		$this->sa->handle_login();

		$this->assertFalse( $gate_reached, 'An invalid token must never reach the auth gate.' );
		$this->assertTrue( $failed, 'An invalid token must fire bb_support_access_login_failed.' );
		$this->assertTrue( $this->sa->is_active(), 'A failed attempt must not revoke a live grant.' );
	}

	/**
	 * An expired grant is revoked on login (lazy enforcement) and never reaches
	 * the auth gate.
	 */
	public function test_handle_login_revokes_expired_grant() {
		$result = $this->sa->enable( 7 );
		$token  = $this->token_from_url( $result['login_url'] );
		$this->expire_grant_now();

		$gate_reached = false;
		add_filter(
			'bb_support_access_allow_login',
			static function ( $allow ) use ( &$gate_reached ) {
				$gate_reached = true;
				return $allow;
			},
			10,
			3
		);

		$_GET[ BB_Support_Access::QUERY_VAR ] = $token;
		$this->sa->handle_login();

		$this->assertFalse( $gate_reached, 'An expired grant must not authenticate.' );
		$this->assertFalse( $this->sa->is_active(), 'An expired grant must be revoked on login.' );
	}

	/**
	 * Once disabled, the previously-issued login URL is dead.
	 */
	public function test_handle_login_dead_after_disable() {
		$result = $this->sa->enable( 7 );
		$token  = $this->token_from_url( $result['login_url'] );
		$this->sa->disable();

		$gate_reached = false;
		add_filter(
			'bb_support_access_allow_login',
			static function ( $allow ) use ( &$gate_reached ) {
				$gate_reached = true;
				return $allow;
			},
			10,
			3
		);

		$_GET[ BB_Support_Access::QUERY_VAR ] = $token;
		$this->sa->handle_login();

		$this->assertFalse( $gate_reached, 'A revoked token must not authenticate.' );
	}

	/* Cron expiry ------------------------------------------------------------ */

	/**
	 * The expiry cron revokes a grant whose window has passed.
	 */
	public function test_cron_expire_revokes_expired_grant() {
		$this->sa->enable( 7 );
		$this->expire_grant_now();

		$this->sa->cron_expire();

		$this->assertFalse( $this->sa->is_active(), 'cron_expire() must revoke an expired grant.' );
	}

	/**
	 * The expiry cron leaves a still-live grant untouched.
	 */
	public function test_cron_expire_keeps_live_grant() {
		$this->sa->enable( 7 );

		$this->sa->cron_expire();

		$this->assertTrue( $this->sa->is_active(), 'cron_expire() must not revoke a live grant.' );
	}

	/* Ticket persistence ----------------------------------------------------- */

	/**
	 * A ticket is persisted only when its support-system note posts
	 * successfully; a failed note leaves no orphaned ticket.
	 */
	public function test_add_ticket_persists_only_when_note_succeeds() {
		$this->sa->enable( 7 );

		// Successful note -> ticket persisted.
		$this->mock_http_success();
		$ok = $this->sa->add_ticket( '12345' );
		$this->assertTrue( $ok['added'] );
		$this->assertContains( '12345', $this->sa->get_data()['ticket_numbers'] );

		// Failed note -> ticket NOT persisted.
		remove_all_filters( 'pre_http_request' );
		$this->mock_http_failure();
		$fail = $this->sa->add_ticket( '67890' );
		$this->assertFalse( $fail['added'] );
		$this->assertWPError( $fail['notify'] );
		$this->assertNotContains( '67890', $this->sa->get_data()['ticket_numbers'], 'A failed note must not persist the ticket.' );
	}

	/* Notify rate-limit lifecycle ------------------------------------------- */

	/**
	 * A successful note arms the per-conversation rate window; a failed note
	 * releases it so a genuine failure can be retried immediately.
	 */
	public function test_notify_rate_limit_blocks_repeat_then_releases_on_failure() {
		// Successful note for conversation 100 arms the window.
		$this->mock_http_success();
		$first = $this->sa->notify_support_system( 100, 'note' );
		$this->assertIsArray( $first );
		$this->assertTrue( ! empty( $first['success'] ) );

		// Immediate repeat for the same conversation is rate-limited (no HTTP).
		$repeat = $this->sa->notify_support_system( 100, 'note' );
		$this->assertWPError( $repeat );
		$this->assertSame( 'rate_limited', $repeat->get_error_code() );

		// A failing note for a different conversation must release its window.
		remove_all_filters( 'pre_http_request' );
		$this->mock_http_failure();
		$failed = $this->sa->notify_support_system( 200, 'note' );
		$this->assertWPError( $failed );
		$this->assertFalse(
			get_transient( 'bb_sa_notify_200' ),
			'A failed note must release the rate-limit window for retry.'
		);
	}

	/* Multisite root-blog storage ------------------------------------------- */

	/**
	 * On multisite, a grant enabled while acting on a non-root blog must be
	 * stored on — and read back from — the root blog. The login/read path only
	 * ever looks at the root blog (get_data() -> bp_get_option() ->
	 * get_blog_option( bp_get_root_blog_id() )), so a write pinned to the current
	 * blog would land where nothing reads it and the grant would surface as
	 * inactive. Regression guard for the root-blog switch in save()/with_lock().
	 *
	 * @group multisite
	 */
	public function test_enable_from_non_root_blog_stores_grant_on_root_blog() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Test requires a multisite install.' );
		}

		$root_blog_id = bp_get_root_blog_id();
		$other_blog   = self::factory()->blog->create();
		$this->assertNotSame( $root_blog_id, $other_blog, 'The secondary blog must differ from the root blog.' );

		switch_to_blog( $other_blog );

		try {
			$this->assertSame( $other_blog, get_current_blog_id(), 'Precondition: acting on the non-root blog.' );

			$result = $this->sa->enable( 7 );
			$this->assertIsArray( $result );

			// The grant reads back as live: get_data() pulls from the root blog.
			$this->assertTrue(
				$this->sa->is_active(),
				'A grant enabled on a non-root blog must be active when read from the root blog.'
			);

			// It is physically stored on the root blog...
			$this->assertNotEmpty(
				get_blog_option( $root_blog_id, BB_Support_Access::OPTION ),
				'The grant must be persisted on the root blog.'
			);

			// ...and never leaks onto the current (non-root) blog.
			$this->assertFalse(
				get_blog_option( $other_blog, BB_Support_Access::OPTION ),
				'The grant must not be written to the non-root blog.'
			);

			// Revocation from the non-root blog must clear the root-blog grant.
			$this->sa->disable();
			$this->assertFalse(
				$this->sa->is_active(),
				'disable() on a non-root blog must revoke the root-blog grant.'
			);
			$this->assertFalse(
				get_blog_option( $root_blog_id, BB_Support_Access::OPTION ),
				'disable() must delete the grant from the root blog.'
			);
		} finally {
			restore_current_blog();
		}
	}
}
