<?php
/**
 * Tests for the per-email sender resolution in BP_Email_Tokens (PROD-10519).
 *
 * @package BuddyBoss\Core
 */

/**
 * Message emails rendered in one request must each show their own sender.
 *
 * @group core
 * @group BP_Email
 * @group BP_Email_Tokens
 */
class BB_Tests_Email_Tokens_Message_Sender extends BP_UnitTestCase_Emails {

	/**
	 * Sender A user ID.
	 *
	 * @var int
	 */
	protected $sender_a;

	/**
	 * Sender B user ID.
	 *
	 * @var int
	 */
	protected $sender_b;

	/**
	 * Recipient user ID.
	 *
	 * @var int
	 */
	protected $recipient;

	/**
	 * Emails captured on the `bp_send_email` action, in send order.
	 *
	 * @var BP_Email[]
	 */
	protected $sent = array();

	/**
	 * Set up users and capture hooks.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->sender_a  = self::factory()->user->create(
			array(
				'display_name' => 'Sender Alpha',
				'user_email'   => 'sender-a@example.com',
			)
		);
		$this->sender_b  = self::factory()->user->create(
			array(
				'display_name' => 'Sender Bravo',
				'user_email'   => 'sender-b@example.com',
			)
		);
		$this->recipient = self::factory()->user->create(
			array(
				'display_name' => 'Recipient',
				'user_email'   => 'recipient@example.com',
			)
		);

		$this->sent = array();
		add_action( 'bp_send_email', array( $this, 'capture_email' ), 10, 4 );

		// Make avatar URLs user-specific so the avatar of the sender block can be asserted.
		add_filter( 'bp_core_fetch_avatar_url', array( $this, 'avatar_url_per_user' ), 10, 2 );
	}

	/**
	 * Remove capture hooks.
	 */
	public function tearDown(): void {
		remove_action( 'bp_send_email', array( $this, 'capture_email' ), 10 );
		remove_filter( 'bp_core_fetch_avatar_url', array( $this, 'avatar_url_per_user' ), 10 );
		parent::tearDown();
	}

	/**
	 * Capture every email handed to `bp_send_email`.
	 *
	 * @param BP_Email $email Email being sent.
	 */
	public function capture_email( $email ) {
		$this->sent[] = $email;
	}

	/**
	 * Make user avatar URLs depend on the user ID.
	 *
	 * @param string $url    Avatar URL.
	 * @param array  $params Avatar params.
	 *
	 * @return string
	 */
	public function avatar_url_per_user( $url, $params ) {
		if ( ! empty( $params['item_id'] ) && ( empty( $params['object'] ) || 'user' === $params['object'] ) ) {
			return 'https://example.com/avatar/' . (int) $params['item_id'] . '.png';
		}
		return $url;
	}

	/**
	 * Avatar URL the filter above produces for a user.
	 *
	 * @param int $user_id User ID.
	 *
	 * @return string
	 */
	protected function avatar_of( $user_id ) {
		return 'https://example.com/avatar/' . (int) $user_id . '.png';
	}

	/**
	 * Find the request-wide BP_Email_Tokens instance (it is created without a handle in
	 * bp_setup_core_email_tokens()) and prime its sender the way `messages_message_sent` does.
	 *
	 * @param int $sender_id Sender user ID to prime.
	 */
	protected function prime_hook_sender( $sender_id ) {
		global $wp_filter;
		$this->assertArrayHasKey( 'messages_message_sent', $wp_filter );
		foreach ( $wp_filter['messages_message_sent']->callbacks as $callbacks ) {
			foreach ( $callbacks as $cb ) {
				if ( is_array( $cb['function'] ) && $cb['function'][0] instanceof BP_Email_Tokens ) {
					$cb['function'][0]->messages_message_sent( (object) array( 'sender_id' => $sender_id ) );
					return;
				}
			}
		}
		$this->fail( 'BP_Email_Tokens::messages_message_sent is not hooked.' );
	}

	/**
	 * Create one private message from a sender to the recipient.
	 *
	 * @param int    $sender_id Sender user ID.
	 * @param string $content   Message body.
	 *
	 * @return int Message ID.
	 */
	protected function create_message( $sender_id, $content = 'Hello' ) {
		// messages_new_message() returns the thread ID; resolve the message ID from the thread.
		$thread_id = messages_new_message(
			array(
				'sender_id'  => $sender_id,
				'recipients' => array( $this->recipient ),
				'subject'    => 'Subject',
				'content'    => $content,
			)
		);
		$this->assertNotEmpty( $thread_id, 'messages_new_message() should create the thread' );
		$last = BP_Messages_Thread::get_last_message( (int) $thread_id );
		$this->assertNotEmpty( $last->id, 'thread should have a message' );
		$this->assertSame( (int) $sender_id, (int) $last->sender_id );
		return (int) $last->id;
	}

	/**
	 * Append `{{{sender.url}}}` to the installed messages-unread template so the token is rendered.
	 */
	protected function add_sender_url_to_template() {
		$posts = get_posts(
			array(
				'post_type'   => bp_get_email_post_type(),
				'post_status' => 'any',
				'numberposts' => 1,
				'tax_query'   => array(
					array(
						'taxonomy' => bp_get_email_tax_type(),
						'field'    => 'slug',
						'terms'    => 'messages-unread',
					),
				),
			)
		);
		$this->assertCount( 1, $posts, 'messages-unread email template should be installed' );
		wp_update_post(
			array(
				'ID'           => $posts[0]->ID,
				'post_content' => $posts[0]->post_content . "\n{{{sender.url}}}",
			)
		);
	}

	/**
	 * Read one formatted token off a captured email.
	 *
	 * @param BP_Email $email Captured email.
	 * @param string   $key   Token key.
	 *
	 * @return string|null
	 */
	protected function token_of( $email, $key ) {
		$tokens = $email->get_tokens();
		return isset( $tokens[ $key ] ) ? $tokens[ $key ] : null;
	}

	/**
	 * Send a `messages-unread` email to the recipient and return its rendered HTML.
	 *
	 * @param array $tokens Tokens for this email; the usual producer tokens are filled in.
	 *
	 * @return string Rendered HTML content.
	 */
	protected function send_unread( array $tokens ) {
		$tokens = wp_parse_args(
			$tokens,
			array(
				'usermessage'      => 'Hello',
				'usersubject'      => 'Subject',
				'message.url'      => home_url( '/' ),
				'receiver-user.id' => $this->recipient,
			)
		);
		$result = bp_send_email( 'messages-unread', $this->recipient, array( 'tokens' => $tokens ) );
		$this->assertTrue( $result, 'bp_send_email() should succeed' );
		return end( $this->sent )->get_content_html( 'replace-tokens' );
	}

	/**
	 * Two message emails with different `sender.id` rendered in one request: each must show
	 * its own sender's avatar and profile link (FreeScout #158713 — digest cron path).
	 */
	public function test_second_message_email_in_same_request_uses_its_own_sender() {
		$m_a = $this->create_message( $this->sender_a, 'Hello from A' );
		$m_b = $this->create_message( $this->sender_b, 'Hello from B' );

		// The digest cron renders one email per thread, each with its own sender.id + message_id.
		$html_a = $this->send_unread(
			array(
				'sender.id'   => $this->sender_a,
				'sender.name' => 'Sender Alpha',
				'message_id'  => $m_a,
			)
		);
		$html_b = $this->send_unread(
			array(
				'sender.id'   => $this->sender_b,
				'sender.name' => 'Sender Bravo',
				'message_id'  => $m_b,
			)
		);

		$this->assertStringContainsString( bp_core_get_user_domain( $this->sender_a ), $html_a );
		$this->assertStringContainsString( $this->avatar_of( $this->sender_a ), $html_a );

		$this->assertStringContainsString( bp_core_get_user_domain( $this->sender_b ), $html_b, 'second email must link to its own sender' );
		$this->assertStringContainsString( $this->avatar_of( $this->sender_b ), $html_b, 'second email must show its own sender avatar' );
		$this->assertStringNotContainsString( bp_core_get_user_domain( $this->sender_a ), $html_b, 'second email must not link to the first sender' );
		$this->assertStringNotContainsString( $this->avatar_of( $this->sender_a ), $html_b, 'second email must not show the first sender avatar' );
	}

	/**
	 * `{{{sender.url}}}` must follow each email's sender too.
	 */
	public function test_sender_url_token_follows_each_email() {
		$this->add_sender_url_to_template();
		$m_a = $this->create_message( $this->sender_a );
		$m_b = $this->create_message( $this->sender_b );

		// B's message was created last, so the hook-primed sender is B; A's email must still say A.
		$this->send_unread(
			array(
				'sender.id'   => $this->sender_a,
				'sender.name' => 'Sender Alpha',
				'message_id'  => $m_a,
			)
		);
		$email_a = end( $this->sent );
		$this->assertSame( bp_core_get_user_domain( $this->sender_a ), $this->token_of( $email_a, 'sender.url' ) );

		$this->send_unread(
			array(
				'sender.id'   => $this->sender_b,
				'sender.name' => 'Sender Bravo',
				'message_id'  => $m_b,
			)
		);
		$email_b = end( $this->sent );
		$this->assertSame( bp_core_get_user_domain( $this->sender_b ), $this->token_of( $email_b, 'sender.url' ) );
	}

	/**
	 * Back-compat: a producer that passes no `sender.id` and a `message_id` that resolves to
	 * no stored message still gets the sender primed by `messages_message_sent` (the
	 * immediate send path relies on that priming).
	 */
	public function test_hook_primed_sender_is_used_when_no_token_identifies_the_sender() {
		$this->prime_hook_sender( $this->sender_a );
		// No sender.id, and a message_id that resolves to nothing.
		$html = $this->send_unread(
			array(
				'sender.name' => 'Sender Alpha',
				'message_id'  => 999999999,
			)
		);

		$this->assertStringContainsString( bp_core_get_user_domain( $this->sender_a ), $html );
		$this->assertStringContainsString( $this->avatar_of( $this->sender_a ), $html );
	}

	/**
	 * `message_id` identifies the sender when `sender.id` is absent, and beats a stale
	 * hook-primed sender from an earlier message in the same request.
	 */
	public function test_message_id_resolves_sender_over_stale_hook_sender() {
		$message_id = $this->create_message( $this->sender_a, 'Hello from A' );

		// Another message from B was sent later in the same request.
		$this->prime_hook_sender( $this->sender_b );

		$html = $this->send_unread(
			array(
				'message_id'  => $message_id,
				'sender.name' => 'Sender Alpha',
			)
		);

		$this->assertStringContainsString( bp_core_get_user_domain( $this->sender_a ), $html );
		$this->assertStringContainsString( $this->avatar_of( $this->sender_a ), $html );
		$this->assertStringNotContainsString( bp_core_get_user_domain( $this->sender_b ), $html );
	}

	/**
	 * `sender.id` takes precedence over the sender stored for `message_id` (producers that pass
	 * both are authoritative about who the email is from).
	 */
	public function test_sender_id_token_takes_precedence_over_message_id() {
		$message_id = $this->create_message( $this->sender_a, 'Stored as A' );

		$html = $this->send_unread(
			array(
				'sender.id'   => $this->sender_b,
				'message_id'  => $message_id,
				'sender.name' => 'Sender Bravo',
			)
		);

		$this->assertStringContainsString( bp_core_get_user_domain( $this->sender_b ), $html );
		$this->assertStringContainsString( $this->avatar_of( $this->sender_b ), $html );
		$this->assertStringNotContainsString( $this->avatar_of( $this->sender_a ), $html, 'the stored sender must not override sender.id' );
	}
}
