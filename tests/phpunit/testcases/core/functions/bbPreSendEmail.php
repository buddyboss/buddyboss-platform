<?php
/**
 * Tests for the `bb_pre_bp_send_email` filter in bp_send_email().
 *
 * @group core
 * @group BP_Email
 */
class BB_Tests_Core_Functions_BbPreSendEmail extends BP_UnitTestCase_Emails {

	/**
	 * Recipient user ID.
	 *
	 * @var int
	 */
	protected $u1;

	/**
	 * How many times core's wp_mail() reached `pre_wp_mail` during the test.
	 *
	 * @var int
	 */
	protected $pre_wp_mail_calls = 0;

	public function setUp(): void {
		parent::setUp();

		$this->u1 = self::factory()->user->create(
			array(
				'display_name' => 'Pre Send',
				'user_email'   => 'pre-send@example.com',
			)
		);

		// Hooks added here and in the tests are put back by WP_UnitTestCase after each test.
		$this->pre_wp_mail_calls = 0;
		add_filter( 'pre_wp_mail', array( $this, 'count_pre_wp_mail' ), 0 );
	}

	public function count_pre_wp_mail( $short_circuit ) {
		$this->pre_wp_mail_calls++;

		return $short_circuit;
	}

	protected function send() {
		return bp_send_email(
			'activity-comment',
			$this->u1,
			array(
				'tokens' => array(
					'comment.id'                => 123,
					'commenter.id'              => $this->u1,
					'usermessage'               => 'hello world',
					'original_activity.user_id' => $this->u1,
					'poster.name'               => 'name',
					'thread.url'                => 'http://example.com',
					'unsubscribe'               => 'http://example.com',
				),
			)
		);
	}

	public function test_capability_marker() {
		$this->assertTrue( function_exists( 'bb_email_supports_pre_send_filter' ) );
		$this->assertTrue( bb_email_supports_pre_send_filter() );
	}

	public function test_null_sends_as_usual_and_passes_the_email() {
		$seen = array();
		add_filter(
			'bb_pre_bp_send_email',
			function ( $pre, $email, $email_type, $to ) use ( &$seen ) {
				$seen = array( $email instanceof BP_Email, $email_type, $to );

				return $pre;
			},
			10,
			4
		);

		$this->assertTrue( $this->send() );
		$this->assertSame( array( true, 'activity-comment', $this->u1 ), $seen );
		$this->assertSame( 1, $this->pre_wp_mail_calls, 'wp_mail() should run when the filter returns null.' );
	}

	public function test_true_stops_the_send_before_wp_mail() {
		add_filter( 'bb_pre_bp_send_email', '__return_true' );

		$this->assertTrue( $this->send() );
		$this->assertSame( 0, $this->pre_wp_mail_calls, 'wp_mail() must not run when the filter returns true.' );
	}

	public function test_wp_error_is_returned_to_the_caller() {
		add_filter(
			'bb_pre_bp_send_email',
			function () {
				return new WP_Error( 'held', 'Held for testing.' );
			}
		);

		$result = $this->send();

		$this->assertWPError( $result );
		$this->assertSame( 'held', $result->get_error_code() );
		$this->assertSame( 0, $this->pre_wp_mail_calls );
	}

	public function test_true_stops_the_delivery_class_path_too() {
		add_filter( 'bp_email_use_bp_mail', '__return_true' );
		add_filter( 'bb_pre_bp_send_email', '__return_true' );

		$delivered = false;
		$record    = function () use ( &$delivered ) {
			$delivered = true;
		};
		add_action( 'bp_send_email_success', $record );
		add_action( 'bp_send_email_failure', $record );

		$result = $this->send();

		$this->assertTrue( $result );
		$this->assertFalse( $delivered, 'The delivery class must not run when the filter returns true.' );
		$this->assertSame( 0, $this->pre_wp_mail_calls );
	}

	public function test_not_applied_when_validation_fails() {
		$called = false;
		add_filter(
			'bb_pre_bp_send_email',
			function ( $pre ) use ( &$called ) {
				$called = true;

				return $pre;
			}
		);
		add_filter(
			'bp_email_validate',
			function () {
				return new WP_Error( 'missing_parameter', 'Invalid for testing.' );
			}
		);

		$result = $this->send();

		$this->assertWPError( $result );
		$this->assertSame( 'missing_parameter', $result->get_error_code() );
		$this->assertFalse( $called, 'Only a validated email reaches the filter.' );
	}
}
