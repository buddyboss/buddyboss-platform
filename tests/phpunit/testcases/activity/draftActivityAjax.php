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

		// Filter rather than define( 'DOING_AJAX' ): a constant would leak into every later test.
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
		return $this->invoke_handler( 'bb_nouveau_ajax_post_draft_activity', 'draft_activity', '_wpnonce_post_draft', 'post_draft_activity', $user_id, $draft );
	}

	/**
	 * Invoke the forum topic/reply draft handler.
	 *
	 * @param int   $user_id Acting (logged-in) member.
	 * @param mixed $draft   draft_topic_reply payload.
	 *
	 * @return array|null
	 */
	protected function invoke_forum_draft_handler( $user_id, $draft ) {
		return $this->invoke_handler( 'bb_post_topic_reply_draft', 'draft_topic_reply', '_wpnonce_post_topic_reply_draft', 'post_topic_reply_draft_data', $user_id, $draft );
	}

	/**
	 * Build the request globals for a draft handler, run it and decode its JSON.
	 *
	 * @param callable $handler      Handler function.
	 * @param string   $field        Request field carrying the draft JSON.
	 * @param string   $nonce_field  Request field carrying the nonce.
	 * @param string   $nonce_action Nonce action.
	 * @param int      $user_id      Acting (logged-in) member.
	 * @param mixed    $draft        Draft payload - encoded to JSON when not already a string.
	 *
	 * @return array|null
	 */
	protected function invoke_handler( $handler, $field, $nonce_field, $nonce_action, $user_id, $draft ) {
		self::set_current_user( $user_id );

		$payload = is_string( $draft ) ? $draft : wp_json_encode( $draft );

		// Real requests arrive slashed (wp_magic_quotes); both handlers stripslashes() the JSON.
		$_POST    = array(
			$nonce_field => wp_create_nonce( $nonce_action ),
			$field       => wp_slash( $payload ),
		);
		$_REQUEST = $_POST;

		$this->last_response = '';

		ob_start();
		try {
			call_user_func( $handler );
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

	/**
	 * Create an attachment uploaded by the given user.
	 *
	 * @param int $author_id Uploader.
	 *
	 * @return int Attachment ID.
	 */
	protected function create_attachment_for( $author_id ) {
		return self::factory()->attachment->create(
			array(
				'post_author'    => $author_id,
				'post_mime_type' => 'image/jpeg',
				'file'           => 'draft-test.jpg',
			)
		);
	}

	/**
	 * The ownership helper accepts only attachments the user uploaded.
	 */
	public function test_user_owns_draft_attachment_helper() {
		$owner    = self::factory()->user->create( array( 'role' => 'subscriber' ) );
		$other    = self::factory()->user->create( array( 'role' => 'subscriber' ) );
		$attached = $this->create_attachment_for( $owner );
		$post_id  = self::factory()->post->create( array( 'post_author' => $owner ) );

		$this->assertTrue( bb_user_owns_draft_attachment( $attached, $owner ) );
		$this->assertFalse( bb_user_owns_draft_attachment( $attached, $other ), 'Another user does not own it.' );
		$this->assertFalse( bb_user_owns_draft_attachment( $post_id, $owner ), 'A non-attachment post is never a draft attachment.' );
		$this->assertFalse( bb_user_owns_draft_attachment( 0, $owner ) );
		$this->assertFalse( bb_user_owns_draft_attachment( -1 * $attached, $owner ), 'Negative IDs are not normalised to a real ID.' );
		$this->assertFalse( bb_user_owns_draft_attachment( array( $attached ), $owner ) );
	}

	/**
	 * Discarding an activity draft cannot delete another user's attachments.
	 *
	 * Before the ownership check, any member could name arbitrary attachment IDs in
	 * data.media/document/video and have them permanently deleted.
	 */
	public function test_discard_does_not_delete_foreign_attachments() {
		$victim   = self::factory()->user->create( array( 'role' => 'administrator' ) );
		$attacker = self::factory()->user->create( array( 'role' => 'subscriber' ) );

		$foreign_media    = $this->create_attachment_for( $victim );
		$foreign_document = $this->create_attachment_for( $victim );
		$foreign_video    = $this->create_attachment_for( $victim );
		$foreign_image    = $this->create_attachment_for( $victim );

		$response = $this->invoke_draft_handler(
			$attacker,
			array(
				'data_key'                        => 'draft_user',
				'object'                          => 'user',
				'post_action'                     => 'discard',
				'delete_media'                    => 'true',
				'allow_delete_post_feature_image' => true,
				'data'                            => array(
					'media'                          => array( array( 'id' => $foreign_media ) ),
					'document'                       => array( array( 'id' => $foreign_document ) ),
					'video'                          => array( array( 'id' => $foreign_video ) ),
					'bb_activity_post_feature_image' => array( 'id' => $foreign_image ),
				),
			)
		);

		$this->assertIsArray( $response );
		foreach ( array( $foreign_media, $foreign_document, $foreign_video, $foreign_image ) as $attachment_id ) {
			$this->assertInstanceOf( 'WP_Post', get_post( $attachment_id ), "Attachment {$attachment_id} must survive." );
		}
	}

	/**
	 * Discarding still deletes the member's own uploads - the normal composer path.
	 */
	public function test_discard_deletes_own_attachments() {
		$user_id = self::factory()->user->create( array( 'role' => 'subscriber' ) );
		$own     = $this->create_attachment_for( $user_id );

		$response = $this->invoke_draft_handler(
			$user_id,
			array(
				'data_key'     => 'draft_user',
				'object'       => 'user',
				'post_action'  => 'discard',
				'delete_media' => 'true',
				'data'         => array( 'media' => array( array( 'id' => $own ) ) ),
			)
		);

		$this->assertTrue( $response['success'] );
		$this->assertNull( get_post( $own ), 'The member\'s own draft upload is cleaned up.' );
	}

	/**
	 * Saving a draft only flags the member's own uploads as draft media.
	 */
	public function test_update_flags_only_own_attachments_as_draft() {
		$victim  = self::factory()->user->create( array( 'role' => 'subscriber' ) );
		$user_id = self::factory()->user->create( array( 'role' => 'subscriber' ) );
		$own     = $this->create_attachment_for( $user_id );
		$foreign = $this->create_attachment_for( $victim );

		$this->invoke_draft_handler(
			$user_id,
			array(
				'data_key'    => 'draft_user',
				'object'      => 'user',
				'post_action' => 'update',
				'data'        => array( 'media' => array( array( 'id' => $own ), array( 'id' => $foreign ) ) ),
			)
		);

		$this->assertSame( '1', get_post_meta( $own, 'bb_media_draft', true ) );
		$this->assertSame( '', get_post_meta( $foreign, 'bb_media_draft', true ), 'Foreign attachments are not tampered with.' );
	}

	/**
	 * The forum draft handler deletes attachments named in the previously stored
	 * draft whenever the same data_key is saved again. Those IDs came from the
	 * client on the earlier request, so a two-step request could delete any file.
	 */
	public function test_forum_draft_resave_does_not_delete_foreign_attachments() {
		if ( ! function_exists( 'bb_post_topic_reply_draft' ) ) {
			$this->markTestSkipped( 'Forums component is not loaded.' );
		}

		$victim   = self::factory()->user->create( array( 'role' => 'administrator' ) );
		$attacker = self::factory()->user->create( array( 'role' => 'subscriber' ) );
		$foreign  = $this->create_attachment_for( $victim );
		$own      = $this->create_attachment_for( $attacker );

		// The whole entry is stored as sent, and the re-save delete loop reads
		// bbp_media from its top level - so that is where an attacker puts it.
		$draft = array(
			'data_key'    => 'draft_topic',
			'object'      => 'topic',
			'post_action' => 'update',
			'bbp_media'   => wp_json_encode(
				array(
					array( 'id' => $foreign ),
					array( 'id' => $own ),
				)
			),
		);

		// Step 1 plants the IDs in the stored draft; step 2 re-saves the same key.
		$this->invoke_forum_draft_handler( $attacker, $draft );
		$this->invoke_forum_draft_handler( $attacker, $draft );

		$this->assertInstanceOf( 'WP_Post', get_post( $foreign ), 'The victim\'s attachment must survive.' );
		$this->assertNull( get_post( $own ), 'The member\'s own stored upload is still cleaned up.' );
	}
}
