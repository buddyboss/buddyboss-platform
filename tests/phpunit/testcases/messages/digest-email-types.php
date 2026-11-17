<?php
/**
 * Tests for the per-recipient email type of the delayed-notification digest producer (PROD-10519).
 *
 * @package BuddyBoss\Messages
 */

/**
 * The digest producer must type each recipient's email by that recipient's own unread messages,
 * not by the previous recipient's.
 *
 * @group messages
 * @group bb_digest
 */
class BB_Tests_Messages_Digest_Email_Types extends BP_UnitTestCase_Emails {

	/**
	 * Captured sends: [ type, recipient user ID, tokens ].
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
		remove_filter( 'bp_disable_group_messages', '__return_true' );
		remove_filter( 'bb_delay_email_notifications_enabled', '__return_true' );
		$_POST = array();
		parent::tearDown();
	}

	/**
	 * Record type, recipient and tokens.
	 *
	 * @param BP_Email $email      Email.
	 * @param string   $email_type Type.
	 * @param mixed    $to         Recipient (WP_User here).
	 * @param array    $args       Args.
	 */
	public function capture( $email, $email_type, $to, $args ) {
		$user_id      = $to instanceof WP_User ? (int) $to->ID : (int) $to;
		$this->sent[] = array( $email_type, $user_id, isset( $args['tokens'] ) ? $args['tokens'] : array() );
	}

	/**
	 * Install the given email templates from the registered schema.
	 *
	 * @param string[] $types Email types.
	 */
	protected function install_emails( $types ) {
		$schema = bp_email_get_schema();
		foreach ( $types as $type ) {
			$this->assertArrayHasKey( $type, $schema, "{$type} is registered" );
			$post_id = self::factory()->post->create(
				array(
					'post_type'    => bp_get_email_post_type(),
					'post_status'  => 'publish',
					'post_title'   => $schema[ $type ]['post_title'],
					'post_content' => $schema[ $type ]['post_content'],
					'post_excerpt' => $schema[ $type ]['post_excerpt'],
				)
			);
			wp_set_object_terms( $post_id, $type, bp_get_email_tax_type() );
		}
	}

	/**
	 * S1 — in a three-party thread where A sent m1 and B sent m2 (B never read m1), the digest
	 * cron groups: B → [m1], R → [m1, m2], A → [m2]. B's single-message email must not turn R's
	 * two-message digest into a single-message email that drops m2.
	 */
	public function test_recipient_with_two_unread_gets_digest_after_a_single_message_recipient() {
		$a = self::factory()->user->create( array( 'display_name' => 'Sender A' ) );
		$b = self::factory()->user->create( array( 'display_name' => 'Sender B' ) );
		$r = self::factory()->user->create( array( 'display_name' => 'Recipient' ) );

		$thread_id = messages_new_message(
			array(
				'sender_id'  => $a,
				'recipients' => array( $b, $r ),
				'subject'    => 'S',
				'content'    => 'm1 from A',
			)
		);
		$this->assertNotEmpty( $thread_id );
		$m1 = (int) BP_Messages_Thread::get_last_message( (int) $thread_id )->id;

		$thread_id_2 = messages_new_message(
			array(
				'sender_id'  => $b,
				'recipients' => array( $a, $r ),
				'subject'    => 'S',
				'content'    => 'm2 from B',
			)
		);
		$this->assertSame( (int) $thread_id, (int) $thread_id_2, 'B\'s message joins the same thread' );
		$m2 = (int) BP_Messages_Thread::get_last_message( (int) $thread_id )->id;
		$this->assertNotSame( $m1, $m2 );

		$row = function ( $message_id, $sender_id, $recipient_id, $text ) use ( $thread_id ) {
			return array(
				'message_id'    => $message_id,
				'sender_id'     => $sender_id,
				'recipients_id' => $recipient_id,
				'message'       => $text,
				'subject'       => 'S',
				'thread_id'     => (int) $thread_id,
			);
		};

		// Same shape and order as bb_digest_message_email_notifications() builds it (ORDER BY m.id).
		$recipients = array(
			$b => array( $row( $m1, $a, $b, 'm1 from A' ) ),
			$r => array( $row( $m1, $a, $r, 'm1 from A' ), $row( $m2, $b, $r, 'm2 from B' ) ),
			$a => array( $row( $m2, $b, $a, 'm2 from B' ) ),
		);

		$this->sent = array();
		bb_render_digest_messages_template( $recipients, (int) $thread_id );

		$by_user = array();
		foreach ( $this->sent as $s ) {
			$by_user[ $s[1] ] = $s;
		}

		$this->assertArrayHasKey( $b, $by_user );
		$this->assertSame( 'messages-unread', $by_user[ $b ][0], 'B has one unread → single-message email' );

		$this->assertArrayHasKey( $r, $by_user );
		$this->assertSame( 'messages-unread-digest', $by_user[ $r ][0], 'R has two unread → digest email' );
		$this->assertSame( 2, (int) $by_user[ $r ][2]['unread.count'] );
		$this->assertCount( 2, $by_user[ $r ][2]['message'] );

		$this->assertArrayHasKey( $a, $by_user );
		$this->assertSame( 'messages-unread', $by_user[ $a ][0], 'A has one unread → single-message email' );
		$this->assertSame( $b, (int) $by_user[ $a ][2]['sender.id'] );
	}

	/**
	 * S1, group branch — in an open group message to all members, A sent m1 and B replied with m2
	 * (B never read m1). The digest must type each recipient from the group digest type:
	 * B → [m1] group single, R → [m1, m2] group digest, A → [m2] group single. Neither a
	 * single-message recipient's type nor the private-thread digest type may leak into R's email.
	 */
	public function test_group_thread_recipient_with_two_unread_gets_group_digest() {
		// A site with group messaging and delayed emails on registers the group message
		// notification + email types at boot; the harness boots with group messaging off.
		add_filter( 'bp_disable_group_messages', '__return_true' );
		add_filter( 'bb_delay_email_notifications_enabled', '__return_true' );
		BP_Groups_Notification::instance()->register_notification_for_group_user_messages();
		$this->install_emails( array( 'group-message-email', 'group-message-digest' ) );

		$a        = self::factory()->user->create( array( 'display_name' => 'Sender A' ) );
		$b        = self::factory()->user->create( array( 'display_name' => 'Sender B' ) );
		$r        = self::factory()->user->create( array( 'display_name' => 'Recipient' ) );
		$group_id = self::factory()->group->create( array( 'creator_id' => $a ) );

		// Each member has group message emails on in their notification settings. The site
		// preference list is built once per request, before the types above were registered.
		$type_key = bb_enabled_legacy_email_preference() ? 'notification_group_messages_new_message' : bb_get_prefences_key( 'legacy', 'notification_group_messages_new_message' );
		foreach ( array( $a, $b, $r ) as $user_id ) {
			bp_update_user_meta( $user_id, $type_key, 'yes' );
		}

		// Group ▸ Send Message ▸ all members, open (same POST fields the group message form sends).
		$_POST = array(
			'group' => (string) $group_id,
			'users' => 'all',
			'type'  => 'open',
		);

		$thread_id = messages_new_message(
			array(
				'sender_id'  => $a,
				'recipients' => array( $b, $r ),
				'subject'    => 'G',
				'content'    => 'm1 from A',
			)
		);
		$this->assertNotEmpty( $thread_id );
		$m1 = (int) BP_Messages_Thread::get_last_message( (int) $thread_id )->id;

		// B replies in the thread (no group fields posted; the thread's group meta is inherited).
		$_POST = array();

		$thread_id_2 = messages_new_message(
			array(
				'sender_id'  => $b,
				'recipients' => array( $a, $r ),
				'subject'    => 'G',
				'content'    => 'm2 from B',
			)
		);
		$this->assertSame( (int) $thread_id, (int) $thread_id_2, 'B\'s message joins the same thread' );
		$m2 = (int) BP_Messages_Thread::get_last_message( (int) $thread_id )->id;
		$this->assertNotSame( $m1, $m2 );

		// The digest detects the group thread from the first message's meta.
		$this->assertSame( $m1, (int) BP_Messages_Thread::get_first_message( (int) $thread_id )->id );
		$this->assertSame( $group_id, (int) bp_messages_get_meta( $m1, 'group_id', true ) );

		$row = function ( $message_id, $sender_id, $recipient_id, $text ) use ( $thread_id ) {
			return array(
				'message_id'    => $message_id,
				'sender_id'     => $sender_id,
				'recipients_id' => $recipient_id,
				'message'       => $text,
				'subject'       => 'G',
				'thread_id'     => (int) $thread_id,
			);
		};

		$recipients = array(
			$b => array( $row( $m1, $a, $b, 'm1 from A' ) ),
			$r => array( $row( $m1, $a, $r, 'm1 from A' ), $row( $m2, $b, $r, 'm2 from B' ) ),
			$a => array( $row( $m2, $b, $a, 'm2 from B' ) ),
		);

		$this->sent = array();
		bb_render_digest_messages_template( $recipients, (int) $thread_id );

		$by_user = array();
		foreach ( $this->sent as $s ) {
			$by_user[ $s[1] ] = $s;
		}

		$this->assertArrayHasKey( $b, $by_user );
		$this->assertSame( 'group-message-email', $by_user[ $b ][0], 'B has one unread → group single-message email' );

		$this->assertArrayHasKey( $r, $by_user );
		$this->assertSame( 'group-message-digest', $by_user[ $r ][0], 'R has two unread → group digest email' );
		$this->assertSame( 2, (int) $by_user[ $r ][2]['unread.count'] );
		$this->assertCount( 2, $by_user[ $r ][2]['message'] );
		$this->assertSame( $group_id, (int) $by_user[ $r ][2]['group.id'] );

		$this->assertArrayHasKey( $a, $by_user );
		$this->assertSame( 'group-message-email', $by_user[ $a ][0], 'A has one unread → group single-message email' );
		$this->assertSame( $b, (int) $by_user[ $a ][2]['sender.id'] );
	}
}
