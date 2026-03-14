<?php
/**
 * Tests for RSVP functionality.
 *
 * Covers TKET-02: RSVP below capacity, at capacity (waitlist), and cancel.
 *
 * @package BuddyBoss\Events\Tests
 * @since   1.0.0
 */
class BP_Events_Test_RSVP extends WP_UnitTestCase {

	/**
	 * RSVP below capacity writes status='registered'.
	 *
	 * @covers TKET-02
	 */
	public function test_rsvp_creates_registered_row() {
		global $wpdb;
		$bp = buddypress();

		// Create a user and log them in.
		$user_id = $this->factory->user->create();
		wp_set_current_user( $user_id );

		// Create an event with capacity 10 (plenty of room).
		$event_id = bp_events_create_event( array(
			'title'      => 'RSVP Test Event',
			'start_date' => '2027-01-01 10:00:00',
			'end_date'   => '2027-01-01 12:00:00',
			'status'     => 'published',
			'capacity'   => 10,
		) );

		$this->assertNotEmpty( $event_id, 'Event should be created.' );

		$result = bp_events_rsvp_event( $event_id, $user_id );

		$this->assertSame( 'registered', $result, 'RSVP under capacity should return registered.' );

		// Verify the row exists in DB.
		$row = $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM {$bp->events->table_name_attendees} WHERE event_id = %d AND user_id = %d",
			$event_id,
			$user_id
		) );

		$this->assertNotNull( $row, 'Attendee row should exist.' );
		$this->assertSame( 'registered', $row->status, 'Row status should be registered.' );
	}

	/**
	 * RSVP when event is at capacity writes status='waitlisted'.
	 *
	 * @covers TKET-02
	 */
	public function test_rsvp_at_capacity_creates_waitlist_row() {
		global $wpdb;
		$bp = buddypress();

		// Create an event with capacity 1.
		$event_id = bp_events_create_event( array(
			'title'      => 'RSVP Capacity Test Event',
			'start_date' => '2027-02-01 10:00:00',
			'end_date'   => '2027-02-01 12:00:00',
			'status'     => 'published',
			'capacity'   => 1,
		) );

		$this->assertNotEmpty( $event_id, 'Event should be created.' );

		// Fill the capacity with one user.
		$user1_id = $this->factory->user->create();
		$result1  = bp_events_rsvp_event( $event_id, $user1_id );
		$this->assertSame( 'registered', $result1, 'First RSVP should be registered.' );

		// Second user should be waitlisted.
		$user2_id = $this->factory->user->create();
		wp_set_current_user( $user2_id );
		$result2 = bp_events_rsvp_event( $event_id, $user2_id );

		$this->assertSame( 'waitlisted', $result2, 'RSVP at capacity should return waitlisted.' );

		// Verify row in DB.
		$row = $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM {$bp->events->table_name_attendees} WHERE event_id = %d AND user_id = %d",
			$event_id,
			$user2_id
		) );

		$this->assertNotNull( $row, 'Waitlisted attendee row should exist.' );
		$this->assertSame( 'waitlisted', $row->status, 'Row status should be waitlisted.' );
	}

	/**
	 * Cancelling an RSVP deletes the attendee row.
	 *
	 * @covers TKET-02
	 */
	public function test_cancel_rsvp_removes_row() {
		global $wpdb;
		$bp = buddypress();

		$user_id = $this->factory->user->create();
		wp_set_current_user( $user_id );

		$event_id = bp_events_create_event( array(
			'title'      => 'RSVP Cancel Test Event',
			'start_date' => '2027-03-01 10:00:00',
			'end_date'   => '2027-03-01 12:00:00',
			'status'     => 'published',
			'capacity'   => 10,
		) );

		$this->assertNotEmpty( $event_id, 'Event should be created.' );

		// First RSVP.
		bp_events_rsvp_event( $event_id, $user_id );

		// Now cancel.
		$result = bp_events_cancel_rsvp( $event_id, $user_id );
		$this->assertTrue( $result, 'bp_events_cancel_rsvp() should return true.' );

		// Verify row is gone.
		$row = $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM {$bp->events->table_name_attendees} WHERE event_id = %d AND user_id = %d",
			$event_id,
			$user_id
		) );

		$this->assertNull( $row, 'Attendee row should be deleted after cancel.' );
	}
}
