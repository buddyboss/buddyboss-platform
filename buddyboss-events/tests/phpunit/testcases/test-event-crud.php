<?php

/**
 * Tests for event CRUD operations.
 *
 * Covers requirements: EVNT-01 (in-person events), EVNT-02 (virtual events),
 * EVNT-04 (draft/published status).
 *
 * @package BuddyBoss\Events\Tests
 */

/**
 * Class BP_Events_Test_CRUD
 *
 * @group bp-events
 * @group event-crud
 */
class BP_Events_Test_CRUD extends WP_UnitTestCase {

	/**
	 * Test that creating an in-person event persists venue fields.
	 *
	 * Covers EVNT-01: venue_name, venue_address, and capacity are saved
	 * and retrievable after event creation.
	 */
	public function test_create_in_person_event() {
		$event_id = bp_events_create_event(
			array(
				'title'         => 'In-Person Test Event',
				'type'          => 'in-person',
				'venue_name'    => 'Test Hall',
				'venue_address' => '123 Main St',
				'capacity'      => 50,
				'start_date'    => '2026-06-01 10:00:00',
				'end_date'      => '2026-06-01 12:00:00',
				'status'        => 'published',
			)
		);

		$this->assertNotEmpty( $event_id, 'bp_events_create_event() should return a non-zero ID.' );
		$this->assertGreaterThan( 0, $event_id, 'Event ID should be a positive integer.' );

		$event = bp_events_get_event( $event_id );

		$this->assertInstanceOf( 'BP_Event', $event, 'bp_events_get_event() should return a BP_Event object.' );
		$this->assertSame( 'Test Hall', $event->venue_name, 'venue_name should be persisted.' );
		$this->assertSame( '123 Main St', $event->venue_address, 'venue_address should be persisted.' );
		$this->assertSame( 50, (int) $event->capacity, 'capacity should be persisted.' );
		$this->assertSame( 'in-person', $event->type, 'event type should be in-person.' );
	}

	/**
	 * Test that creating a virtual event persists virtual-specific fields.
	 *
	 * Covers EVNT-02: virtual_url and virtual_type are saved and retrievable
	 * after event creation.
	 */
	public function test_create_virtual_event() {
		$event_id = bp_events_create_event(
			array(
				'title'        => 'Virtual Test Event',
				'type'         => 'virtual',
				'virtual_url'  => 'https://zoom.us/j/123',
				'virtual_type' => 'zoom',
				'start_date'   => '2026-06-15 14:00:00',
				'end_date'     => '2026-06-15 15:00:00',
				'status'       => 'published',
			)
		);

		$this->assertNotEmpty( $event_id, 'bp_events_create_event() should return a non-zero ID.' );
		$this->assertGreaterThan( 0, $event_id, 'Event ID should be a positive integer.' );

		$event = bp_events_get_event( $event_id );

		$this->assertInstanceOf( 'BP_Event', $event, 'bp_events_get_event() should return a BP_Event object.' );
		$this->assertSame( 'https://zoom.us/j/123', $event->virtual_url, 'virtual_url should be persisted.' );
		$this->assertSame( 'zoom', $event->virtual_type, 'virtual_type should be persisted.' );
		$this->assertSame( 'virtual', $event->type, 'event type should be virtual.' );
	}

	/**
	 * Test that draft events are excluded from published-event queries.
	 *
	 * Covers EVNT-04: events with status=draft must not appear in queries
	 * that filter for published events only.
	 */
	public function test_draft_not_in_published_query() {
		$draft_id = bp_events_create_event(
			array(
				'title'      => 'Draft Event',
				'start_date' => '2026-07-01 09:00:00',
				'end_date'   => '2026-07-01 10:00:00',
				'status'     => 'draft',
			)
		);

		$published_id = bp_events_create_event(
			array(
				'title'      => 'Published Event',
				'start_date' => '2026-07-01 11:00:00',
				'end_date'   => '2026-07-01 12:00:00',
				'status'     => 'published',
			)
		);

		$this->assertNotEmpty( $draft_id, 'Draft event should be created successfully.' );
		$this->assertNotEmpty( $published_id, 'Published event should be created successfully.' );

		$result         = bp_events_get_events( array( 'status' => 'published' ) );
		$returned_ids   = wp_list_pluck( $result['events'], 'id' );

		$this->assertContains( (string) $published_id, $returned_ids, 'Published event should appear in published query.' );
		$this->assertNotContains( (string) $draft_id, $returned_ids, 'Draft event must NOT appear in published query.' );
	}
}
