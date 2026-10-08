<?php
/**
 * Tests for the activity draft AJAX handler's data_key allowlist (PROD-10561).
 *
 * The handler bb_nouveau_ajax_post_draft_activity() writes and deletes a usermeta
 * row named by the client's draft_activity[data_key]. Without an allowlist a member
 * could name wp_capabilities (or any other meta key) and overwrite their own role.
 * These tests pin the gate added in PROD-10561: only draft_user, draft_user_{id}
 * and draft_group_{id} are accepted; every other key is refused and nothing is written.
 *
 * @group activity
 * @group bb_activity_draft
 * @group PROD-10561
 */
class BB_Tests_Activity_Draft_Ajax extends BP_UnitTestCase {

	/**
	 * Captured JSON echoed by the handler before it called wp_die().
	 *
	 * @var string
	 */
	protected $last_response = '';

	/**
	 * Saved error_reporting level, restored on tear down.
	 *
	 * @var int
	 */
	protected $error_level = 0;

	/**
	 * Pretend to be an AJAX request and route wp_die() through a handler that
	 * throws instead of exiting, so the handler under test can be invoked in
	 * process and its JSON payload inspected.
	 */
	public function set_up() {
		parent::set_up();

		if ( ! defined( 'DOING_AJAX' ) ) {
			define( 'DOING_AJAX', true );
		}

		add_filter( 'wp_doing_ajax', '__return_true' );
		add_filter( 'wp_die_ajax_handler', array( $this, 'get_die_handler' ), 1 );

		// wp_send_json_* prints headers; suppress the "headers already sent" warning.
		$this->error_level = error_reporting();
		error_reporting( $this->error_level & ~E_WARNING );
	}

	/**
	 * Restore request globals, the wp_die() handler and error reporting.
	 */
	public function tear_down() {
		$_POST    = array();
		$_GET     = array();
		$_REQUEST = array();
		remove_filter( 'wp_doing_ajax', '__return_true' );
		remove_filter( 'wp_die_ajax_handler', array( $this, 'get_die_handler' ), 1 );
		error_reporting( $this->error_level );

		parent::tear_down();
	}

	/**
	 * Route wp_die() to the throwing handler below.
	 *
	 * @return array
	 */
	public function get_die_handler() {
		return array( $this, 'die_handler' );
	}

	/**
	 * Capture the echoed JSON and stop execution without exiting the process.
	 *
	 * @param string|WP_Error $message wp_die() message (unused).
	 *
	 * @throws WPAjaxDieContinueException Always, to unwind back to the test body.
	 */
	public function die_handler( $message ) {
		$this->last_response .= ob_get_clean();
		throw new WPAjaxDieContinueException( is_scalar( $message ) ? (string) $message : '' );
	}

	/**
	 * Build the request globals and invoke the draft handler, returning the
	 * decoded JSON response ( array with 'success' and optional 'data' ).
	 *
	 * @param int   $user_id Acting (logged-in) member.
	 * @param mixed $draft   draft_activity payload - encoded to JSON when not already a string.
	 *
	 * @return array|null
	 */
	protected function invoke_draft_handler( $user_id, $draft ) {
		self::set_current_user( $user_id );

		$payload = is_string( $draft ) ? $draft : wp_json_encode( $draft );

		$_POST['_wpnonce_post_draft'] = wp_create_nonce( 'post_draft_activity' );
		$_REQUEST['draft_activity']   = $payload;
		$_POST['draft_activity']      = $payload;

		$this->last_response = '';

		ob_start();
		try {
			bb_nouveau_ajax_post_draft_activity();
		} catch ( WPAjaxDieContinueException $e ) {
			// Expected: the handler ended via wp_send_json_*.
		} catch ( WPAjaxDieStopException $e ) {
			// A bare wp_send_json_error() with no body also stops here.
			if ( '' === $this->last_response && ob_get_level() > 0 ) {
				$this->last_response = ob_get_clean();
			}
		}

		if ( '' === $this->last_response && ob_get_level() > 0 ) {
			$this->last_response = ob_get_clean();
		}

		return json_decode( $this->last_response, true );
	}

	/**
	 * The capabilities usermeta key for this install (wp_capabilities in
	 * production, {prefix}capabilities in the test DB). Writing the attack
	 * payload here is what actually flips a role, so the escalation tests target
	 * this key to stay mutation-proof: revert the allowlist and the role changes.
	 *
	 * @return string
	 */
	protected function capabilities_meta_key() {
		global $wpdb;

		return $wpdb->get_blog_prefix() . 'capabilities';
	}

	/**
	 * The exact key from the Patchstack report is refused outright.
	 */
	public function test_update_rejects_literal_wp_capabilities_key() {
		$user_id = self::factory()->user->create( array( 'role' => 'subscriber' ) );

		$response = $this->invoke_draft_handler(
			$user_id,
			array(
				'data_key'      => 'wp_capabilities',
				'object'        => 'user',
				'post_action'   => 'update',
				'administrator' => true,
			)
		);

		$this->assertIsArray( $response );
		$this->assertFalse( $response['success'], 'The crafted draft save must be refused.' );
	}

	/**
	 * A subscriber cannot grant themselves Administrator by naming the live
	 * capabilities key.
	 *
	 * Core PROD-10561 regression: reverting the allowlist lets the handler write
	 * the submitted payload to the capabilities row, after which
	 * user_can( $id, 'manage_options' ) is true and these assertions fail.
	 */
	public function test_update_cannot_escalate_role_via_capabilities_key() {
		$user_id = self::factory()->user->create( array( 'role' => 'subscriber' ) );

		$response = $this->invoke_draft_handler(
			$user_id,
			array(
				'data_key'      => $this->capabilities_meta_key(),
				'object'        => 'user',
				'post_action'   => 'update',
				'administrator' => true,
			)
		);

		$this->assertIsArray( $response );
		$this->assertFalse( $response['success'], 'The crafted draft save must be refused.' );

		// Role is unchanged: still a subscriber, never an administrator.
		$user = new WP_User( $user_id );
		$this->assertContains( 'subscriber', (array) $user->roles );
		$this->assertNotContains( 'administrator', (array) $user->roles );
		$this->assertFalse( user_can( $user_id, 'manage_options' ) );
	}

	/**
	 * The discard branch is gated too: a crafted delete cannot strip the member's
	 * own capabilities row (which would remove their role).
	 */
	public function test_discard_cannot_delete_capabilities_row() {
		$user_id = self::factory()->user->create( array( 'role' => 'subscriber' ) );

		$response = $this->invoke_draft_handler(
			$user_id,
			array(
				'data_key'    => $this->capabilities_meta_key(),
				'object'      => 'user',
				'post_action' => 'discard',
			)
		);

		$this->assertIsArray( $response );
		$this->assertFalse( $response['success'], 'The crafted draft discard must be refused.' );

		// The capabilities row still exists and still carries the subscriber role.
		$user = new WP_User( $user_id );
		$this->assertContains( 'subscriber', (array) $user->roles );
		$this->assertTrue( user_can( $user_id, 'read' ) );
	}

	/**
	 * A non-scalar data_key (crafted array) is rejected cleanly, with no write.
	 */
	public function test_update_rejects_non_scalar_key() {
		$user_id = self::factory()->user->create( array( 'role' => 'subscriber' ) );

		$response = $this->invoke_draft_handler(
			$user_id,
			array(
				'data_key'    => array( 'wp_capabilities' ),
				'object'      => 'user',
				'post_action' => 'update',
			)
		);

		$this->assertIsArray( $response );
		$this->assertFalse( $response['success'] );
		// Pin that the allowlist gate itself rejected it (not a coincidental downstream
		// failure): the crafted array key coerces to '' and is refused with the gate's
		// own message. user_can() here would be invariant, so assert the message instead.
		$this->assertSame( 'This draft could not be saved.', $response['data']['message'] ?? '' );
	}

	/**
	 * The legitimate draft_user key still saves - the allowlist does not break the
	 * composer's normal save path.
	 */
	public function test_update_allows_draft_user_key() {
		$user_id = self::factory()->user->create( array( 'role' => 'subscriber' ) );

		$response = $this->invoke_draft_handler(
			$user_id,
			array(
				'data_key'    => 'draft_user',
				'object'      => 'user',
				'post_action' => 'update',
				'data'        => array( 'content' => 'hello draft' ),
			)
		);

		$this->assertIsArray( $response );
		$this->assertTrue( $response['success'], 'A valid draft_user save must succeed.' );

		$stored = bp_get_user_meta( $user_id, 'draft_user', true );
		$this->assertIsArray( $stored );
		$this->assertSame( 'draft_user', $stored['data_key'] );
	}

	/**
	 * The legitimate draft_user_{id} key (profile composer on another member's
	 * wall) is accepted.
	 */
	public function test_update_allows_draft_user_suffixed_key() {
		$user_id = self::factory()->user->create( array( 'role' => 'subscriber' ) );

		$response = $this->invoke_draft_handler(
			$user_id,
			array(
				'data_key'    => 'draft_user_' . $user_id,
				'object'      => 'user',
				'post_action' => 'update',
				'data'        => array( 'content' => 'hello wall draft' ),
			)
		);

		$this->assertIsArray( $response );
		$this->assertTrue( $response['success'] );
		$this->assertIsArray( bp_get_user_meta( $user_id, 'draft_user_' . $user_id, true ) );
	}

	/**
	 * The legitimate draft_group_{id} key (group activity composer) is accepted.
	 *
	 * Pins the `group` arm of the allowlist: narrowing the regex to user-only keys
	 * would silently break group-activity drafts for every community, and this is
	 * the only test that would then go red.
	 */
	public function test_update_allows_draft_group_suffixed_key() {
		$user_id  = self::factory()->user->create( array( 'role' => 'subscriber' ) );
		$group_id = self::factory()->group->create( array( 'creator_id' => $user_id ) );

		$response = $this->invoke_draft_handler(
			$user_id,
			array(
				'data_key'    => 'draft_group_' . $group_id,
				'object'      => 'group',
				'post_action' => 'update',
				'data'        => array( 'content' => 'hello group draft' ),
			)
		);

		$this->assertIsArray( $response );
		$this->assertTrue( $response['success'], 'A valid draft_group_{id} save must succeed.' );
		$this->assertIsArray( bp_get_user_meta( $user_id, 'draft_group_' . $group_id, true ) );
	}
}
