<?php
/**
 * Tests for the delayed-notification digest cron scheduling and the digest producer (PROD-10519).
 *
 * @package BuddyBoss\Messages
 */

/**
 * Saving the Messages panel in Settings 2.0 must leave the digest cron scheduled, and the digest
 * producer must type each recipient's email by that recipient's own unread count.
 *
 * @group messages
 * @group bb_digest
 */
class BB_Tests_Messages_Digest_Email_Cron extends BP_UnitTestCase_Emails {

	/**
	 * Load the Settings 2.0 messages callbacks (only loaded for admin/AJAX requests at boot).
	 */
	public function setUp(): void {
		parent::setUp();

		if ( ! function_exists( 'bb_messages_reschedule_cron_after_save' ) ) {
			require_once buddypress()->plugin_dir . 'bp-core/admin/settings/messages/callbacks.php';
		}

		$timestamp = wp_next_scheduled( 'bb_digest_email_notifications_hook' );
		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, 'bb_digest_email_notifications_hook' );
		}

		$this->declared_crons = bp_core_cron()->crons;
		bp_core_cron()->crons = array();
	}

	/**
	 * Crons declared to BP_Core_Cron before the test.
	 *
	 * @var array
	 */
	protected $declared_crons = array();

	/**
	 * Leave no digest event behind.
	 */
	public function tearDown(): void {
		wp_clear_scheduled_hook( 'bb_digest_email_notifications_hook' );
		bp_core_cron()->crons = $this->declared_crons;
		unset( $_POST['time_delay_email_notification'] );
		remove_filter( 'bb_enable_legacy_notification_preference', '__return_true' );
		remove_filter( 'bp_get_root_blog_id', array( $this, 'other_root_blog_id' ) );
		remove_filter( 'bp_core_cron_schedule_bb_digest_email_notifications_hook', array( $this, 'veto_digest_event' ) );
		remove_filter( 'bb_delay_email_notifications_enabled', '__return_false' );
		remove_action( 'bp_send_email', array( $this, 'capture_email' ), 10 );
		parent::tearDown();
	}

	/**
	 * Run the load-time repair the way a request does (`bp_init` priority 11).
	 */
	protected function run_load_time_schedule() {
		bb_messages_maybe_schedule_digest_email_notifications();
	}

	/**
	 * Veto the digest event through the BP_Core_Cron per-hook filter.
	 *
	 * @return bool
	 */
	public function veto_digest_event() {
		return false;
	}

	/**
	 * Count scheduled digest events.
	 *
	 * @return int
	 */
	protected function count_digest_events() {
		$count = 0;
		foreach ( (array) _get_cron_array() as $hooks ) {
			if ( isset( $hooks['bb_digest_email_notifications_hook'] ) ) {
				$count += count( $hooks['bb_digest_email_notifications_hook'] );
			}
		}

		return $count;
	}

	/**
	 * Repair — runs on every load, after `bp_init` priority 10 where the BuddyBoss cron intervals
	 * (bb_schedule_15min, …) are registered; earlier, wp_schedule_event() rejects the recurrence
	 * and the repair would silently do nothing on a real site (the test process has the
	 * intervals registered already, so only the priority can be pinned here).
	 */
	public function test_digest_repair_runs_after_the_cron_intervals_are_registered() {
		$priority = has_action( 'bp_init', 'bb_messages_maybe_schedule_digest_email_notifications' );
		$this->assertIsInt( $priority );
		$this->assertGreaterThan( 10, $priority );
	}

	/**
	 * Repair — it schedules the event itself and declares nothing to BP_Core_Cron, so the cron
	 * singleton is not built earlier than on release (that would start scheduling unrelated
	 * declared crons, e.g. the media/document/video symlink deleters).
	 */
	public function test_digest_repair_does_not_declare_crons_to_bp_core_cron() {
		bp_update_option( 'delay_email_notification', 1 );
		bp_update_option( 'time_delay_email_notification', 15 );

		$this->run_load_time_schedule();

		$this->assertNotFalse( wp_next_scheduled( 'bb_digest_email_notifications_hook' ), 'precondition: the repair ran' );
		$this->assertSame( array(), bp_core_cron()->crons, 'nothing declared to BP_Core_Cron' );
	}

	/**
	 * Repair — honours the same per-hook veto BP_Core_Cron::schedule() applies.
	 */
	public function test_no_digest_event_on_load_when_the_cron_filter_vetoes_it() {
		bp_update_option( 'delay_email_notification', 1 );
		bp_update_option( 'time_delay_email_notification', 15 );
		add_filter( 'bp_core_cron_schedule_bb_digest_email_notifications_hook', array( $this, 'veto_digest_event' ) );

		$this->run_load_time_schedule();

		remove_filter( 'bp_core_cron_schedule_bb_digest_email_notifications_hook', array( $this, 'veto_digest_event' ) );
		$this->assertFalse( wp_next_scheduled( 'bb_digest_email_notifications_hook' ) );
	}

	/**
	 * Repair — a site that lost the event (e.g. a Settings 2.0 save from 3.0.0 until [BBVERSION]) gets it back
	 * on the next load, with the saved interval.
	 */
	public function test_missing_digest_event_is_recreated_on_load() {
		bp_update_option( 'delay_email_notification', 1 );
		bp_update_option( 'time_delay_email_notification', 30 );
		$this->assertFalse( wp_next_scheduled( 'bb_digest_email_notifications_hook' ), 'precondition: event missing' );

		$this->run_load_time_schedule();

		$this->assertNotFalse( wp_next_scheduled( 'bb_digest_email_notifications_hook' ) );
		$this->assertSame( 'bb_schedule_30min', wp_get_schedule( 'bb_digest_email_notifications_hook' ) );
		$this->assertSame( 1, $this->count_digest_events() );
	}

	/**
	 * Repair — an existing event is left exactly as it is (no second event, no new time).
	 */
	public function test_existing_digest_event_is_not_changed_on_load() {
		bp_update_option( 'delay_email_notification', 1 );
		bp_update_option( 'time_delay_email_notification', 30 );
		$when = time() + 600;
		wp_schedule_event( $when, 'bb_schedule_15min', 'bb_digest_email_notifications_hook' );

		$this->run_load_time_schedule();
		$this->run_load_time_schedule();

		$this->assertSame( $when, wp_next_scheduled( 'bb_digest_email_notifications_hook' ) );
		$this->assertSame( 'bb_schedule_15min', wp_get_schedule( 'bb_digest_email_notifications_hook' ) );
		$this->assertSame( 1, $this->count_digest_events() );
	}

	/**
	 * Repair — no event when delayed emails are off: the immediate emails are sent then, so a
	 * digest event has nothing to deliver.
	 */
	public function test_no_digest_event_on_load_when_delay_is_disabled() {
		bp_update_option( 'delay_email_notification', 0 );
		bp_update_option( 'time_delay_email_notification', 15 );

		$this->run_load_time_schedule();

		$this->assertFalse( wp_next_scheduled( 'bb_digest_email_notifications_hook' ) );
	}

	/**
	 * Repair — no event with the legacy notification preferences (delayed emails do not apply).
	 */
	public function test_no_digest_event_on_load_with_legacy_notification_preferences() {
		bp_update_option( 'delay_email_notification', 1 );
		bp_update_option( 'time_delay_email_notification', 15 );
		add_filter( 'bb_enable_legacy_notification_preference', '__return_true' );

		$this->run_load_time_schedule();

		$this->assertFalse( wp_next_scheduled( 'bb_digest_email_notifications_hook' ) );
	}

	/**
	 * Repair — during a legacy settings save the stored option is stale (the save runs later),
	 * so the declaration must leave that request to bb_schedule_event_on_update_notification_settings().
	 * Without this, turning the delay off through the legacy form would re-create the event.
	 */
	public function test_no_digest_event_on_load_during_a_legacy_settings_save() {
		bp_update_option( 'delay_email_notification', 1 );
		bp_update_option( 'time_delay_email_notification', 15 );
		$_POST['time_delay_email_notification'] = '15';

		$this->run_load_time_schedule();

		$this->assertFalse( wp_next_scheduled( 'bb_digest_email_notifications_hook' ) );
	}

	/**
	 * S2 — after the Settings 2.0 save callback runs with the delay enabled, the digest event
	 * must be scheduled in the same request (release: unscheduled, never rescheduled).
	 */
	public function test_settings_2_0_save_schedules_the_digest_event() {
		bp_update_option( 'delay_email_notification', 1 );
		bp_update_option( 'time_delay_email_notification', 30 );

		$this->assertFalse( wp_next_scheduled( 'bb_digest_email_notifications_hook' ), 'precondition: not scheduled' );

		bb_messages_reschedule_cron_after_save( 'messages', array(), array( 'time_delay_email_notification' => 30 ) );

		$next = wp_next_scheduled( 'bb_digest_email_notifications_hook' );
		$this->assertNotFalse( $next, 'digest event must be scheduled by the save callback itself' );
		$this->assertLessThanOrEqual( time() + 5, $next, 'first run is now, so wp-cron picks it up on the next request' );
		$this->assertSame( 'bb_schedule_30min', wp_get_schedule( 'bb_digest_email_notifications_hook' ) );
	}

	/**
	 * S2 — a second save with a new interval replaces the schedule instead of stacking events.
	 */
	public function test_settings_2_0_save_replaces_the_digest_schedule() {
		bp_update_option( 'delay_email_notification', 1 );
		bp_update_option( 'time_delay_email_notification', 15 );
		bb_messages_reschedule_cron_after_save( 'messages', array(), array( 'time_delay_email_notification' => 15 ) );
		$this->assertSame( 'bb_schedule_15min', wp_get_schedule( 'bb_digest_email_notifications_hook' ) );

		bp_update_option( 'time_delay_email_notification', 60 );
		bb_messages_reschedule_cron_after_save( 'messages', array(), array( 'time_delay_email_notification' => 60 ) );
		$this->assertSame( 'bb_schedule_1hour', wp_get_schedule( 'bb_digest_email_notifications_hook' ) );

		$events = _get_cron_array();
		$count  = 0;
		foreach ( $events as $hooks ) {
			if ( isset( $hooks['bb_digest_email_notifications_hook'] ) ) {
				$count += count( $hooks['bb_digest_email_notifications_hook'] );
			}
		}
		$this->assertSame( 1, $count, 'exactly one digest event' );
	}

	/**
	 * S2 — disabling the delay unschedules the event (negative case).
	 */
	public function test_settings_2_0_save_with_delay_disabled_unschedules_the_digest_event() {
		bp_update_option( 'delay_email_notification', 1 );
		bp_update_option( 'time_delay_email_notification', 15 );
		bb_messages_reschedule_cron_after_save( 'messages', array(), array( 'time_delay_email_notification' => 15 ) );
		$this->assertNotFalse( wp_next_scheduled( 'bb_digest_email_notifications_hook' ) );

		bp_update_option( 'delay_email_notification', 0 );
		bb_messages_reschedule_cron_after_save( 'messages', array(), array( 'delay_email_notification' => 0 ) );
		$this->assertFalse( wp_next_scheduled( 'bb_digest_email_notifications_hook' ) );
	}

	/**
	 * A root blog ID that is not the current blog (a subsite request on a network-activated
	 * multisite, where BP reads its settings from the main site).
	 *
	 * @return int
	 */
	public function other_root_blog_id() {
		return get_current_blog_id() + 1;
	}

	/**
	 * Repair — only the root blog gets the event. The save paths and the component toggle only
	 * remove it there, so a subsite event would keep sending digests after the delay is turned
	 * off, on top of the immediate emails.
	 */
	public function test_no_digest_event_on_load_outside_the_root_blog() {
		bp_update_option( 'delay_email_notification', 1 );
		bp_update_option( 'time_delay_email_notification', 15 );
		add_filter( 'bp_get_root_blog_id', array( $this, 'other_root_blog_id' ) );
		$this->assertFalse( bp_is_root_blog(), 'precondition: this request is not on the root blog' );

		$this->run_load_time_schedule();

		$this->assertFalse( wp_next_scheduled( 'bb_digest_email_notifications_hook' ) );
	}

	/**
	 * Settings 2.0 save — no event while delayed emails are not in effect, even though the
	 * stored option is on. The immediate message emails stand down only when
	 * bb_check_delay_email_notification() is true (it is false, or undefined, with the
	 * Notifications component off), so an event here would email every message twice.
	 */
	public function test_settings_2_0_save_does_not_schedule_when_delay_is_not_in_effect() {
		bp_update_option( 'delay_email_notification', 1 );
		bp_update_option( 'time_delay_email_notification', 15 );

		// Positive control: the same save schedules while delay is in effect.
		bb_messages_reschedule_cron_after_save( 'messages', array(), array( 'delay_email_notification' => 1 ) );
		$this->assertNotFalse( wp_next_scheduled( 'bb_digest_email_notifications_hook' ), 'precondition: the save schedules while delay is in effect' );

		add_filter( 'bb_delay_email_notifications_enabled', '__return_false' );
		$this->assertFalse( bb_check_delay_email_notification(), 'precondition: delay is not in effect' );

		bb_messages_reschedule_cron_after_save( 'messages', array(), array( 'delay_email_notification' => 1 ) );

		$this->assertFalse( wp_next_scheduled( 'bb_digest_email_notifications_hook' ) );
	}

	/**
	 * Emails captured on the `bp_send_email` action.
	 *
	 * @var array
	 */
	protected $sent = array();

	/**
	 * Capture an email handed to `bp_send_email`.
	 *
	 * @param BP_Email $email      Email object.
	 * @param string   $email_type Email type.
	 * @param mixed    $to         Recipient.
	 */
	public function capture_email( $email, $email_type, $to ) {
		$this->sent[] = array( $email_type, $to instanceof WP_User ? (int) $to->ID : (int) $to );
	}

	/**
	 * Digest producer — an event left scheduled from any source (a component toggle, a 2.x
	 * install, a third-party scheduler) must not email messages whose immediate email already
	 * went out because delayed emails are not in effect.
	 */
	public function test_digest_sends_nothing_when_delay_is_not_in_effect() {
		bp_update_option( 'delay_email_notification', 1 );
		bp_update_option( 'time_delay_email_notification', 15 );

		$sender    = self::factory()->user->create();
		$recipient = self::factory()->user->create();

		$this->sent = array();
		add_action( 'bp_send_email', array( $this, 'capture_email' ), 10, 3 );

		// While the delay is in effect the send is suppressed and the message waits for the digest.
		messages_new_message(
			array(
				'sender_id'  => $sender,
				'recipients' => array( $recipient ),
				'subject'    => 'S',
				'content'    => 'one',
			)
		);
		$this->assertSame( array(), $this->sent, 'precondition: no immediate email while delay is in effect' );

		// Positive control: the digest delivers it.
		bb_digest_message_email_notifications();
		$this->assertSame( array( array( 'messages-unread', $recipient ) ), $this->sent, 'precondition: the digest emails the waiting message' );

		// Delay is no longer in effect, so the next message is emailed at once.
		add_filter( 'bb_delay_email_notifications_enabled', '__return_false' );
		$this->sent = array();
		$thread_id  = messages_new_message(
			array(
				'sender_id'  => $sender,
				'recipients' => array( $recipient ),
				'subject'    => 'S',
				'content'    => 'two',
			)
		);
		$this->assertSame( array( array( 'messages-unread', $recipient ) ), $this->sent, 'precondition: the immediate email went out' );
		$message_id = (int) BP_Messages_Thread::get_last_message( (int) $thread_id )->id;

		$this->sent = array();
		bb_digest_message_email_notifications();

		$this->assertSame( array(), $this->sent, 'the digest must not email it a second time' );
		$this->assertEmpty( bp_messages_get_meta( $message_id, 'bb_sent_digest_email' ), 'the message is left untouched' );
	}
}
