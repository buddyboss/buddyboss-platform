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
	}

	/**
	 * Leave no digest event behind.
	 */
	public function tearDown(): void {
		wp_clear_scheduled_hook( 'bb_digest_email_notifications_hook' );
		parent::tearDown();
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
}
