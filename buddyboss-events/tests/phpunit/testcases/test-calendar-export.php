<?php
/**
 * Tests for calendar export (iCal).
 *
 * Covers ATTN-02: REST GET /events/{id}/ical response body begins with BEGIN:VCALENDAR.
 *
 * @package BuddyBoss\Events\Tests
 * @since   1.0.0
 */
class BP_Events_Test_Calendar_Export extends WP_UnitTestCase {

	/**
	 * REST GET /events/{id}/ical response body begins with BEGIN:VCALENDAR.
	 *
	 * @covers ATTN-02
	 */
	public function test_ical_endpoint_returns_valid_ics() {
		// Create a test event.
		$event_id = bp_events_create_event(
			array(
				'title'      => 'Test iCal Event',
				'start_date' => '2026-06-01 10:00:00',
				'end_date'   => '2026-06-01 11:00:00',
				'timezone'   => 'UTC',
				'type'       => 'virtual',
				'status'     => 'publish',
				'user_id'    => 1,
			)
		);
		$this->assertGreaterThan( 0, $event_id, 'Event should be created successfully' );

		// Build a REST request to the iCal endpoint.
		$request  = new WP_REST_Request( 'GET', '/buddyboss/v1/events/' . $event_id . '/ical' );
		$response = rest_do_request( $request );

		// The iCal endpoint returns a WP_REST_Response with a custom Content-Type.
		$this->assertNotInstanceOf( 'WP_Error', $response );
		$this->assertEquals( 200, $response->get_status(), 'iCal endpoint should return 200' );

		$data = $response->get_data();

		// Response body (string) must start with BEGIN:VCALENDAR.
		$this->assertIsString( $data, 'iCal response data should be a string' );
		$this->assertStringStartsWith( 'BEGIN:VCALENDAR', $data, 'iCal response must begin with BEGIN:VCALENDAR' );

		// Content-Type header must contain text/calendar.
		$headers = $response->get_headers();
		$this->assertArrayHasKey( 'Content-Type', $headers, 'Response must include Content-Type header' );
		$this->assertStringContainsString( 'text/calendar', $headers['Content-Type'], 'Content-Type must be text/calendar' );
	}
}
