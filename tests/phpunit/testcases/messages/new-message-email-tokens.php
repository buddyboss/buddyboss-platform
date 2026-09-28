<?php
/**
 * Tests for the immediate new-message email producer tokens (PROD-10519).
 *
 * @package BuddyBoss\Messages
 */

/**
 * The immediate new-message producer must identify the sender in its tokens.
 *
 * @group messages
 * @group BP_Email
 */
class BB_Tests_Messages_New_Message_Email_Tokens extends BP_UnitTestCase_Emails {

	/**
	 * Captured bp_send_email calls: array of [ type, tokens ].
	 *
	 * @var array
	 */
	protected $sent = array();

	/**
	 * Capture sends.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sent = array();
		add_action( 'bp_send_email', array( $this, 'capture' ), 10, 4 );
	}

	/**
	 * Stop capturing.
	 */
	public function tearDown(): void {
		remove_action( 'bp_send_email', array( $this, 'capture' ), 10 );
		parent::tearDown();
	}

	/**
	 * Record the email type and raw tokens of every send.
	 *
	 * @param BP_Email $email      Email.
	 * @param string   $email_type Type.
	 * @param mixed    $to         Recipient.
	 * @param array    $args       Args passed to bp_send_email().
	 */
	public function capture( $email, $email_type, $to, $args ) {
		$this->sent[] = array( $email_type, isset( $args['tokens'] ) ? $args['tokens'] : array() );
	}

	/**
	 * S3 — the immediate (non-delayed) path passes `sender.id`, so the email identifies its
	 * sender without relying on the request-scoped hook priming.
	 */
	public function test_immediate_new_message_email_carries_sender_id_token() {
		$sender    = self::factory()->user->create( array( 'display_name' => 'Sender' ) );
		$recipient = self::factory()->user->create( array( 'display_name' => 'Recipient' ) );

		// Immediate emails only go out when the delay is off.
		add_filter( 'bb_delay_email_notifications_enabled', '__return_false' );

		$thread_id = messages_new_message(
			array(
				'sender_id'  => $sender,
				'recipients' => array( $recipient ),
				'subject'    => 'Subject',
				'content'    => 'Hello',
			)
		);

		remove_filter( 'bb_delay_email_notifications_enabled', '__return_false' );

		$this->assertNotEmpty( $thread_id );
		$unread = array_values(
			array_filter(
				$this->sent,
				function ( $s ) {
					return 'messages-unread' === $s[0];
				}
			)
		);
		$this->assertCount( 1, $unread, 'one immediate email to the recipient' );
		$this->assertArrayHasKey( 'sender.id', $unread[0][1], 'sender.id token must be present' );
		$this->assertSame( $sender, (int) $unread[0][1]['sender.id'], 'sender.id token must identify the sender' );
		$this->assertArrayHasKey( 'message_id', $unread[0][1] );
	}

	/**
	 * Negative: with the delay on, no immediate email is produced (the digest cron owns it).
	 */
	public function test_delayed_mode_sends_no_immediate_email() {
		$sender    = self::factory()->user->create();
		$recipient = self::factory()->user->create();

		add_filter( 'bb_delay_email_notifications_enabled', '__return_true' );
		messages_new_message(
			array(
				'sender_id'  => $sender,
				'recipients' => array( $recipient ),
				'subject'    => 'S',
				'content'    => 'C',
			)
		);
		remove_filter( 'bb_delay_email_notifications_enabled', '__return_true' );

		$this->assertSame(
			array(),
			array_filter(
				$this->sent,
				function ( $s ) {
					return 'messages-unread' === $s[0];
				}
			)
		);
	}
}
