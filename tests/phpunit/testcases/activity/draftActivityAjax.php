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
	protected function invoke_draft_handler( $user_id, $draft, $extra = array() ) {
		return $this->invoke_handler( 'bb_nouveau_ajax_post_draft_activity', 'draft_activity', '_wpnonce_post_draft', 'post_draft_activity', $user_id, $draft, $extra );
	}

	/**
	 * Invoke the forum topic/reply draft handler.
	 *
	 * @param int   $user_id Acting (logged-in) member.
	 * @param mixed $draft   draft_topic_reply payload.
	 *
	 * @return array|null
	 */
	protected function invoke_forum_draft_handler( $user_id, $draft, $extra = array() ) {
		return $this->invoke_handler( 'bb_post_topic_reply_draft', 'draft_topic_reply', '_wpnonce_post_topic_reply_draft', 'post_topic_reply_draft_data', $user_id, $draft, $extra );
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
	 * @param array    $extra        Optional. Extra request fields (e.g. bb_draft_base).
	 *
	 * @return array|null
	 */
	protected function invoke_handler( $handler, $field, $nonce_field, $nonce_action, $user_id, $draft, $extra = array() ) {
		self::set_current_user( $user_id );

		$payload = is_string( $draft ) ? $draft : wp_json_encode( $draft );

		// Real requests arrive slashed (wp_magic_quotes); both handlers stripslashes() the JSON.
		$_POST    = array(
			$nonce_field => wp_create_nonce( $nonce_action ),
			$field       => wp_slash( $payload ),
		);
		$_POST    = array_merge( $_POST, wp_slash( $extra ) );
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
				// The composer always sends the group it posts to; the save path
				// checks the key against it and against the member's posting rights.
				'data'        => array(
					'content' => 'hello group draft',
					'item_id' => $group_id,
				),
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
	 * The draft attachment helper accepts only attachments the user uploaded.
	 */
	public function test_user_can_manage_draft_attachment_helper() {
		$owner    = self::factory()->user->create( array( 'role' => 'subscriber' ) );
		$other    = self::factory()->user->create( array( 'role' => 'subscriber' ) );
		$attached = $this->create_attachment_for( $owner );
		$post_id  = self::factory()->post->create( array( 'post_author' => $owner ) );

		$this->assertTrue( bb_user_can_manage_draft_attachment( $attached, false, $owner ) );
		$this->assertTrue( bb_user_can_manage_draft_attachment( $attached, true, $owner ), 'An unsaved own upload may be deleted.' );
		$this->assertFalse( bb_user_can_manage_draft_attachment( $attached, false, $other ), 'Another user does not own it.' );
		$this->assertFalse( bb_user_can_manage_draft_attachment( $post_id, false, $owner ), 'A non-attachment post is never a draft attachment.' );
		$this->assertFalse( bb_user_can_manage_draft_attachment( 0, false, $owner ) );
		$this->assertFalse( bb_user_can_manage_draft_attachment( -1 * $attached, false, $owner ), 'Negative IDs are not normalised to a real ID.' );
		$this->assertFalse( bb_user_can_manage_draft_attachment( array( $attached ), false, $owner ) );
	}

	/**
	 * An own upload already linked to a saved item may be flagged but never deleted.
	 *
	 * A stale draft can still name an upload after it was posted; discarding that
	 * draft must not delete the now-published file.
	 */
	public function test_user_can_manage_draft_attachment_refuses_delete_of_saved_uploads() {
		$owner = self::factory()->user->create( array( 'role' => 'subscriber' ) );

		foreach ( array( 'bp_media_id', 'bp_document_id', 'bp_video_id', 'bb_activity_id' ) as $meta_key ) {
			$attached = $this->create_attachment_for( $owner );
			update_post_meta( $attached, $meta_key, 123 );

			$this->assertTrue( bb_user_can_manage_draft_attachment( $attached, false, $owner ), "{$meta_key}: flagging is still allowed." );
			$this->assertFalse( bb_user_can_manage_draft_attachment( $attached, true, $owner ), "{$meta_key}: a saved upload is not deletable." );
		}
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
	 *
	 * The discard reads the attachment list from the STORED draft, never from the
	 * request, so the upload is saved into the draft first.
	 */
	public function test_discard_deletes_own_attachments() {
		$user_id = self::factory()->user->create( array( 'role' => 'subscriber' ) );
		$own     = $this->create_attachment_for( $user_id );

		$this->invoke_draft_handler(
			$user_id,
			array(
				'data_key'    => 'draft_user',
				'object'      => 'user',
				'post_action' => 'update',
				'data'        => array( 'media' => array( array( 'id' => $own ) ) ),
			)
		);

		$response = $this->invoke_draft_handler(
			$user_id,
			array(
				'data_key'     => 'draft_user',
				'object'       => 'user',
				'post_action'  => 'discard',
				'delete_media' => 'true',
			)
		);

		$this->assertTrue( $response['success'] );
		$this->assertNull( get_post( $own ), 'The member\'s own draft upload is cleaned up.' );
	}

	/**
	 * Discarding never deletes an own upload that is already linked to saved media.
	 *
	 * A stale draft can still list a file the member has since posted; deleting it
	 * would remove the photo from the published activity.
	 */
	public function test_discard_keeps_own_attachment_linked_to_saved_media() {
		$user_id = self::factory()->user->create( array( 'role' => 'subscriber' ) );
		$posted  = $this->create_attachment_for( $user_id );

		$this->invoke_draft_handler(
			$user_id,
			array(
				'data_key'    => 'draft_user',
				'object'      => 'user',
				'post_action' => 'update',
				'data'        => array( 'media' => array( array( 'id' => $posted ) ) ),
			)
		);

		update_post_meta( $posted, 'bp_media_id', 123 );

		$this->invoke_draft_handler(
			$user_id,
			array(
				'data_key'     => 'draft_user',
				'object'       => 'user',
				'post_action'  => 'discard',
				'delete_media' => 'true',
			)
		);

		$this->assertInstanceOf( 'WP_Post', get_post( $posted ), 'A posted attachment must survive the discard.' );
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
	 * Re-saving a forum draft must never delete or flag another member's file.
	 *
	 * The handler used to delete attachments named in the previously stored draft
	 * whenever the same data_key was saved again. Those IDs came from the client
	 * on the earlier request, so a two-step request could delete any file.
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
		$this->assertSame( '', get_post_meta( $foreign, 'bb_media_draft', true ), 'A foreign attachment is never flagged as draft media.' );

		// A re-save no longer hard-deletes anything: attachments a draft stops
		// referencing lose their draft stamp and the orphan cron reaps them.
		$this->assertInstanceOf( 'WP_Post', get_post( $own ), 'A re-save does not hard-delete the member\'s upload.' );
	}

	/**
	 * Save a draft_user activity draft with a given revision and base.
	 *
	 * @param int         $user_id Member.
	 * @param string      $content Draft text.
	 * @param string      $rev     Revision id the client generated.
	 * @param string|null $base    Base list, or null to send none.
	 * @return array|null
	 */
	protected function save_rev_draft( $user_id, $content, $rev, $base = null ) {
		return $this->invoke_draft_handler(
			$user_id,
			array(
				'data_key'    => 'draft_user',
				'object'      => 'user',
				'post_action' => 'update',
				'_draft_rev'  => $rev,
				'data'        => array( 'content' => $content ),
			),
			null === $base ? array() : array( 'bb_draft_base' => $base )
		);
	}

	/**
	 * A composer that never saw the stored draft cannot overwrite it (V02/V05).
	 */
	public function test_save_over_an_unseen_or_newer_draft_is_refused() {
		$user_id = self::factory()->user->create( array( 'role' => 'subscriber' ) );

		$this->assertTrue( $this->save_rev_draft( $user_id, 'original', 'revAAAAAAAA' )['success'], 'Premise: a client sending no base is not checked.' );

		foreach ( array( 'none', 'revOLDOLDOLD', 'none,revOLDOLDOLD' ) as $base ) {
			$response = $this->save_rev_draft( $user_id, 'overwrite ' . $base, 'revBBBBBBBB', $base );

			$this->assertFalse( $response['success'], "Base '{$base}' does not include the stored revision, so the save is refused." );
			$this->assertTrue( $response['data']['conflict'] );
			$this->assertSame( 'original', $response['data']['draft_activity']['data']['content'], 'The stored draft comes back so the browser can keep it.' );
		}

		$stored = bp_get_user_meta( $user_id, 'draft_user', true );
		$this->assertSame( 'original', $stored['data']['content'], 'The stored draft survives every refused save.' );

		$accepted = $this->save_rev_draft( $user_id, 'edited', 'revCCCCCCCC', 'revOLDOLDOLD,revAAAAAAAA' );
		$this->assertTrue( $accepted['success'], 'A base that includes the stored revision is accepted.' );

		$stored = bp_get_user_meta( $user_id, 'draft_user', true );
		$this->assertSame( 'edited', $stored['data']['content'] );
		$this->assertSame( 'revCCCCCCCC', $stored['_draft_rev'], 'The revision the client sent is stored.' );
	}

	/**
	 * A discard from a composer that never saw the stored draft keeps it (V08).
	 */
	public function test_discard_of_an_unseen_draft_is_refused() {
		$user_id = self::factory()->user->create( array( 'role' => 'subscriber' ) );

		$this->save_rev_draft( $user_id, 'keep me', 'revAAAAAAAA' );

		$discard = array(
			'data_key'    => 'draft_user',
			'object'      => 'user',
			'post_action' => 'delete',
		);

		$refused = $this->invoke_draft_handler( $user_id, $discard, array( 'bb_draft_base' => 'none' ) );
		$this->assertFalse( $refused['success'] );
		$this->assertTrue( metadata_exists( 'user', $user_id, 'draft_user' ), 'A discard that never saw the draft must not delete it.' );

		$accepted = $this->invoke_draft_handler( $user_id, $discard, array( 'bb_draft_base' => 'revAAAAAAAA' ) );
		$this->assertTrue( $accepted['success'] );
		$this->assertFalse( metadata_exists( 'user', $user_id, 'draft_user' ), 'A discard of the version the composer showed deletes it.' );
	}

	/**
	 * A forum composer cannot overwrite a stored draft it never saw.
	 */
	public function test_forum_save_over_an_unseen_draft_is_refused() {
		if ( ! function_exists( 'bb_post_topic_reply_draft' ) ) {
			$this->markTestSkipped( 'Forums component is not loaded.' );
		}

		$user_id = self::factory()->user->create( array( 'role' => 'subscriber' ) );
		$forum   = self::factory()->post->create( array( 'post_type' => bbp_get_forum_post_type() ) );
		$key     = 'draft_discussion_' . $forum;

		$entry = function ( $text, $rev ) use ( $key ) {
			return array(
				'data_key'    => $key,
				'object'      => 'topic',
				'post_action' => 'update',
				'_draft_rev'  => $rev,
				'data'        => array( 'bbp_topic_content' => $text ),
			);
		};

		$this->assertTrue( $this->invoke_forum_draft_handler( $user_id, $entry( 'original', 'revAAAAAAAA' ) )['success'] );

		$refused = $this->invoke_forum_draft_handler( $user_id, $entry( 'overwrite', 'revBBBBBBBB' ), array( 'bb_draft_base' => 'none' ) );
		$this->assertFalse( $refused['success'] );
		$this->assertTrue( $refused['data']['conflict'] );

		$row = bp_get_user_meta( $user_id, 'bb_user_topic_reply_draft', true );
		$this->assertSame( 'original', $row[ $key ]['data']['bbp_topic_content'] );

		$accepted = $this->invoke_forum_draft_handler( $user_id, $entry( 'edited', 'revCCCCCCCC' ), array( 'bb_draft_base' => 'revAAAAAAAA' ) );
		$this->assertTrue( $accepted['success'] );

		$row = bp_get_user_meta( $user_id, 'bb_user_topic_reply_draft', true );
		$this->assertSame( 'edited', $row[ $key ]['data']['bbp_topic_content'] );
		$this->assertSame( 'revCCCCCCCC', $row[ $key ]['_draft_rev'] );
	}

	/**
	 * An evicted draft is handed back again until the browser acknowledges it (V06).
	 */
	public function test_evicted_draft_is_held_until_acknowledged() {
		$user_id = self::factory()->user->create( array( 'role' => 'subscriber' ) );
		$group   = self::factory()->group->create( array( 'creator_id' => $user_id ) );
		$old_key = 'draft_group_' . $group;

		bp_update_user_meta(
			$user_id,
			$old_key,
			array(
				'data_key'        => $old_key,
				'object'          => 'group',
				'data'            => array( 'content' => str_repeat( 'o', 3000 ) ),
				'_draft_saved_at' => 100,
				'_draft_rev'      => 'revOLDOLDOLD',
			)
		);

		$cap = function () {
			return 4000;
		};
		add_filter( 'bb_draft_user_total_max_size', $cap );

		$first = $this->save_rev_draft( $user_id, str_repeat( 'n', 3000 ), 'revNEWNEWNEW' );

		remove_filter( 'bb_draft_user_total_max_size', $cap );

		$this->assertTrue( $first['success'] );
		$this->assertContains( $old_key, $first['data']['evicted_draft_keys'], 'Premise: the old draft was evicted.' );
		$this->assertArrayHasKey( $old_key, $first['data']['evicted_drafts'] );

		// The first response was lost: the next save hands it back again.
		$second = $this->save_rev_draft( $user_id, str_repeat( 'n', 3000 ) . ' more', 'revNEW2NEW2', 'revNEWNEWNEW' );
		$this->assertArrayHasKey( $old_key, $second['data']['evicted_drafts'], 'An unacknowledged hand-back is sent again.' );
		$this->assertSame( 'revOLDOLDOLD', $second['data']['evicted_drafts'][ $old_key ]['_draft_rev'] );

		// Acknowledged: no longer sent, and the hold is cleared.
		$third = $this->save_rev_draft( $user_id, str_repeat( 'n', 3000 ) . ' again', 'revNEW3NEW3', 'revNEW2NEW2' );
		$this->assertArrayHasKey( $old_key, $third['data']['evicted_drafts'], 'Premise: still owed until acknowledged.' );

		$_POST['bb_draft_evicted_ack'] = $old_key;
		$this->assertSame( array(), bb_draft_pending_evicted( $user_id, bb_draft_request_acked_evictions() ), 'An acknowledged hand-back is dropped from the hold.' );
		unset( $_POST['bb_draft_evicted_ack'] );
		$this->assertSame( array(), bb_draft_get_evicted_hold( $user_id ) );
	}

	/**
	 * The forum draft fetch returns only the drafts the page's forms can use (V14).
	 */
	public function test_forum_fetch_returns_only_requested_prefixes() {
		if ( ! function_exists( 'bb_get_topic_reply_drafts' ) ) {
			$this->markTestSkipped( 'Forums component is not loaded.' );
		}

		$user_id = self::factory()->user->create( array( 'role' => 'subscriber' ) );
		$row     = array();
		foreach ( array( 'draft_reply_12', 'draft_reply_12_7', 'draft_reply_123', 'draft_discussion_4' ) as $key ) {
			$row[ $key ] = array(
				'data_key' => $key,
				'data'     => array( 'bbp_reply_content' => 'text ' . $key ),
			);
		}
		bp_update_user_meta( $user_id, 'bb_user_topic_reply_draft', $row );

		$response = $this->invoke_handler( 'bb_get_topic_reply_drafts', 'unused', '_wpnonce_post_topic_reply_draft', 'post_topic_reply_draft_data', $user_id, '', array( 'prefixes' => 'draft_reply_12' ) );

		$this->assertTrue( $response['success'] );
		$this->assertSame( array( 'draft_reply_12', 'draft_reply_12_7' ), array_keys( $response['data']['drafts'] ), 'Only this topic\'s drafts come back - not draft_reply_123 or another forum\'s.' );

		$all = $this->invoke_handler( 'bb_get_topic_reply_drafts', 'unused', '_wpnonce_post_topic_reply_draft', 'post_topic_reply_draft_data', $user_id, '' );
		$this->assertCount( 4, $all['data']['drafts'], 'Without prefixes (an older page) every draft is returned.' );
	}

	/**
	 * An attachment list sent as an array instead of JSON is handled, not a fatal (V25).
	 */
	public function test_forum_save_with_an_array_attachment_list_does_not_fatal() {
		if ( ! function_exists( 'bb_post_topic_reply_draft' ) ) {
			$this->markTestSkipped( 'Forums component is not loaded.' );
		}

		$user_id = self::factory()->user->create( array( 'role' => 'subscriber' ) );
		$forum   = self::factory()->post->create( array( 'post_type' => bbp_get_forum_post_type() ) );

		$response = $this->invoke_forum_draft_handler(
			$user_id,
			array(
				'data_key'    => 'draft_discussion_' . $forum,
				'object'      => 'topic',
				'post_action' => 'update',
				'data'        => array(
					'bbp_topic_content' => 'hello',
					'bbp_media'         => array( array( 'id' => 0 ) ),
				),
			)
		);

		$this->assertIsArray( $response, 'The handler answers instead of dying with a TypeError.' );
	}

	/**
	 * A key whose hand-back was lost is restored by its own lazy fetch (V06).
	 */
	public function test_fetch_restores_a_held_evicted_draft() {
		$user_id = self::factory()->user->create( array( 'role' => 'subscriber' ) );
		$group   = self::factory()->group->create( array( 'creator_id' => $user_id ) );
		$key     = 'draft_group_' . $group;
		$entry   = array(
			'data_key'   => $key,
			'object'     => 'group',
			'_draft_rev' => 'revHELDHELD1',
			'data'       => array( 'content' => 'held text' ),
		);

		bb_draft_hold_evicted( $user_id, array( $key => $entry ) );
		$this->assertSame( '1', (string) get_user_meta( $user_id, 'bb_draft_has_evicted_hold', true ), 'The page-view flag is set with the hold.' );

		$response = $this->invoke_handler(
			'bb_nouveau_ajax_get_draft_activity',
			'unused',
			'_wpnonce_post_draft',
			'post_draft_activity',
			$user_id,
			'',
			array(
				'data_key' => $key,
				'object'   => 'group',
				'item_id'  => $group,
			)
		);

		$this->assertTrue( $response['success'] );
		$this->assertSame( 'held text', $response['data']['draft_activity']['data']['content'], 'Nothing is stored, so the held copy is restored.' );

		$_POST['bb_draft_evicted_ack'] = $key;
		bb_draft_pending_evicted( $user_id, bb_draft_request_acked_evictions() );
		unset( $_POST['bb_draft_evicted_ack'] );
		$this->assertSame( '', (string) get_user_meta( $user_id, 'bb_draft_has_evicted_hold', true ), 'The flag clears with the hold.' );
	}

	/**
	 * Document preview text is not part of the draft (C-05). Ten text-like
	 * attachments carry about 100 KB of it, which on its own filled the
	 * per-draft cap and refused a draft holding two letters of text.
	 */
	public function test_document_preview_text_does_not_count_toward_the_cap() {
		$user_id   = self::factory()->user->create( array( 'role' => 'subscriber' ) );
		$documents = array();

		for ( $i = 0; $i < 10; $i++ ) {
			$documents[] = array(
				'id'                => $this->create_attachment_for( $user_id ),
				'full_name'         => 'sheet-' . $i . '.csv',
				'svg_icon'          => 'bb-icon-file-csv',
				'svg_icon_download' => '<i class="bb-icon-download"></i>',
				'text'              => str_repeat( 'a,b,c' . "\n", 2000 ),
			);
		}

		$response = $this->invoke_draft_handler(
			$user_id,
			array(
				'data_key'    => 'draft_user',
				'object'      => 'user',
				'post_action' => 'update',
				'data'        => array(
					'content'  => 'hi',
					'document' => $documents,
				),
			)
		);

		$this->assertTrue( $response['success'], 'The draft saves: the preview text is not the member\'s content.' );

		$stored = bp_get_user_meta( $user_id, 'draft_user', true );
		$this->assertCount( 10, $stored['data']['document'], 'Every attachment is kept.' );
		$this->assertArrayNotHasKey( 'text', $stored['data']['document'][0], 'The preview text is not stored.' );
		$this->assertArrayNotHasKey( 'svg_icon_download', $stored['data']['document'][0] );
		$this->assertSame( 'bb-icon-file-csv', $stored['data']['document'][0]['svg_icon'], 'The icon a restore renders is kept.' );
	}

	/**
	 * The forum composer stores its documents as JSON; the same fields go.
	 */
	public function test_forum_document_preview_text_is_stripped() {
		$draft = bb_draft_strip_document_render_fields(
			array(
				'data' => array(
					'bbp_document' => wp_json_encode(
						array(
							array(
								'id'   => 5,
								'text' => str_repeat( 'x', 10000 ),
							),
						)
					),
				),
			)
		);

		$this->assertSame( array( array( 'id' => 5 ) ), json_decode( $draft['data']['bbp_document'], true ) );
	}

	/**
	 * A draft request from a member with nothing held must not read the hold
	 * option, or every drafting member adds a key to the site-wide
	 * `notoptions` cache item until it outgrows the object cache.
	 */
	public function test_handback_without_a_hold_leaves_notoptions_alone() {
		$user_id = self::factory()->user->create( array( 'role' => 'subscriber' ) );
		$option  = bb_draft_evicted_hold_option( $user_id );
		$read    = 0;

		$count = function ( $pre, $name ) use ( $option, &$read ) {
			if ( $name === $option ) {
				++$read;
			}

			return $pre;
		};
		add_filter( 'pre_option', $count, 10, 2 );
		add_filter( 'pre_site_option', $count, 10, 2 );

		$owed = bb_draft_handback( $user_id );

		remove_filter( 'pre_option', $count, 10 );
		remove_filter( 'pre_site_option', $count, 10 );

		$this->assertSame( array(), $owed );
		$this->assertSame( 0, $read, 'The hold option is not read when the member has no hold.' );

		$notoptions = is_multisite()
			? wp_cache_get( get_current_network_id() . ':notoptions', 'site-options' )
			: wp_cache_get( 'notoptions', 'options' );
		$this->assertArrayNotHasKey( $option, (array) $notoptions, 'No notoptions entry is added for the member.' );
	}
}
