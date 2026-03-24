<?php
/**
 * Tests for waitlist notification broadcasts.
 *
 * Covers ATTN-01: cancelling a registered RSVP or increasing capacity
 * triggers bp_events_notify_waitlist.
 *
 * @package BuddyBoss\Events\Tests
 * @since   1.0.0
 */
class BP_Events_Test_Waitlist extends WP_UnitTestCase {

	/**
	 * Cancelling a registered RSVP calls bp_events_notify_waitlist.
	 *
	 * Verifies ATTN-01 by asserting that after a registered RSVP is cancelled,
	 * at least one bp_notifications row exists for each waitlisted user with
	 * component_action='waitlist_spot_open'.
	 *
	 * @covers ATTN-01
	 */
	public function test_cancel_rsvp_triggers_waitlist_notification() {
		global $wpdb;
		$bp = buddypress();

		// Create an event with capacity 1.
		$organizer_id = $this->factory->user->create();
		wp_set_current_user( $organizer_id );

		$event_id = bp_events_create_event( array(
			'title'      => 'Waitlist Notification Test',
			'start_date' => '2027-04-01 10:00:00',
			'end_date'   => '2027-04-01 12:00:00',
			'status'     => 'published',
			'capacity'   => 1,
		) );

		$this->assertNotEmpty( $event_id, 'Event should be created.' );

		// User 1 fills the only spot.
		$user1_id = $this->factory->user->create();
		$status1  = bp_events_rsvp_event( $event_id, $user1_id );
		$this->assertSame( 'registered', $status1, 'User 1 should be registered.' );

		// User 2 is waitlisted.
		$user2_id = $this->factory->user->create();
		$status2  = bp_events_rsvp_event( $event_id, $user2_id );
		$this->assertSame( 'waitlisted', $status2, 'User 2 should be waitlisted.' );

		// User 1 cancels — this should trigger waitlist notification.
		$cancelled = bp_events_cancel_rsvp( $event_id, $user1_id );
		$this->assertTrue( $cancelled, 'Cancel RSVP should return true.' );

		// Check that bp_notifications table has a row for user 2.
		$notifications_table = $wpdb->prefix . 'bp_notifications';
		$notification_count  = (int) $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM {$notifications_table}
			 WHERE user_id = %d
			   AND item_id = %d
			   AND component_name = 'events'
			   AND component_action = 'waitlist_spot_open'",
			$user2_id,
			$event_id
		) );

		$this->assertGreaterThan( 0, $notification_count, 'Waitlist notification should be created for user 2 when a spot opens.' );
	}

	/**
	 * Increasing event capacity calls bp_events_notify_waitlist.
	 *
	 * Verifies ATTN-01: when bp_events_update_capacity() is called with a new
	 * capacity that is greater than the current registered count and there are
	 * waitlisted users, bp_events_notify_waitlist() fires and creates a
	 * bp_notifications row for each waitlisted user.
	 *
	 * @covers ATTN-01
	 */
	public function test_capacity_increase_triggers_waitlist_notification() {
		global $wpdb;
		$bp = buddypress();

		// Create an event with capacity 1.
		$organizer_id = $this->factory->user->create();
		wp_set_current_user( $organizer_id );

		$event_id = bp_events_create_event( array(
			'title'      => 'Capacity Increase Waitlist Test',
			'start_date' => '2027-05-01 10:00:00',
			'end_date'   => '2027-05-01 12:00:00',
			'status'     => 'published',
			'capacity'   => 1,
		) );

		$this->assertNotEmpty( $event_id, 'Event should be created.' );

		// User 1 fills the only spot.
		$user1_id = $this->factory->user->create();
		$status1  = bp_events_rsvp_event( $event_id, $user1_id );
		$this->assertSame( 'registered', $status1, 'User 1 should be registered.' );

		// User 2 is waitlisted.
		$user2_id = $this->factory->user->create();
		$status2  = bp_events_rsvp_event( $event_id, $user2_id );
		$this->assertSame( 'waitlisted', $status2, 'User 2 should be waitlisted.' );

		// Organizer increases capacity to 2 — a spot opens.
		$result = bp_events_update_capacity( $event_id, 2 );
		$this->assertTrue( $result, 'bp_events_update_capacity should return true.' );

		// Check that bp_notifications table has a row for user 2.
		$notifications_table = $wpdb->prefix . 'bp_notifications';
		$notification_count  = (int) $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM {$notifications_table}
			 WHERE user_id = %d
			   AND item_id = %d
			   AND component_name = 'events'
			   AND component_action = 'waitlist_spot_open'",
			$user2_id,
			$event_id
		) );

		$this->assertGreaterThan( 0, $notification_count, 'Waitlist notification should be created for user 2 when capacity increases.' );
	}

	/**
	 * Decreasing capacity does NOT trigger waitlist notification.
	 *
	 * @covers ATTN-01
	 */
	public function test_capacity_decrease_does_not_trigger_waitlist_notification() {
		global $wpdb;

		// Create an event with capacity 5.
		$organizer_id = $this->factory->user->create();
		wp_set_current_user( $organizer_id );

		$event_id = bp_events_create_event( array(
			'title'      => 'Capacity Decrease No Notify Test',
			'start_date' => '2027-06-01 10:00:00',
			'end_date'   => '2027-06-01 12:00:00',
			'status'     => 'published',
			'capacity'   => 5,
		) );

		$this->assertNotEmpty( $event_id, 'Event should be created.' );

		// User 1 registers (only 1 of 5 spots taken, capacity still open).
		$user1_id = $this->factory->user->create();
		bp_events_rsvp_event( $event_id, $user1_id );

		// Reduce capacity to 3 (still > 1 registered, no waitlist).
		$result = bp_events_update_capacity( $event_id, 3 );
		$this->assertTrue( $result, 'bp_events_update_capacity should return true.' );

		// No notifications should be created — there are no waitlisted users.
		$notifications_table = $wpdb->prefix . 'bp_notifications';
		$notification_count  = (int) $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM {$notifications_table}
			 WHERE item_id = %d
			   AND component_name = 'events'
			   AND component_action = 'waitlist_spot_open'",
			$event_id
		) );

		$this->assertSame( 0, $notification_count, 'No notifications should be created when no waitlisted users exist.' );
	}

	/**
	 * Setting capacity to NULL (unlimited) triggers waitlist notification
	 * when waitlisted users exist.
	 *
	 * @covers ATTN-01
	 */
	public function test_capacity_set_to_null_triggers_waitlist_notification() {
		global $wpdb;

		// Create an event with capacity 1.
		$organizer_id = $this->factory->user->create();
		wp_set_current_user( $organizer_id );

		$event_id = bp_events_create_event( array(
			'title'      => 'Unlimited Capacity Waitlist Test',
			'start_date' => '2027-07-01 10:00:00',
			'end_date'   => '2027-07-01 12:00:00',
			'status'     => 'published',
			'capacity'   => 1,
		) );

		$this->assertNotEmpty( $event_id, 'Event should be created.' );

		// Fill the spot and add a waitlisted user.
		$user1_id = $this->factory->user->create();
		bp_events_rsvp_event( $event_id, $user1_id );

		$user2_id = $this->factory->user->create();
		bp_events_rsvp_event( $event_id, $user2_id );

		// Setting capacity to NULL (unlimited) should open all spots.
		$result = bp_events_update_capacity( $event_id, null );
		$this->assertTrue( $result, 'bp_events_update_capacity should return true.' );

		// Waitlisted user should be notified.
		$notifications_table = $wpdb->prefix . 'bp_notifications';
		$notification_count  = (int) $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM {$notifications_table}
			 WHERE user_id = %d
			   AND item_id = %d
			   AND component_name = 'events'
			   AND component_action = 'waitlist_spot_open'",
			$user2_id,
			$event_id
		) );

		$this->assertGreaterThan( 0, $notification_count, 'Waitlist notification should be created when capacity set to unlimited.' );
	}
}
