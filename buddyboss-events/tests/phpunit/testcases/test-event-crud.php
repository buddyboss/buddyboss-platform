<?php

/**
 * Test stubs for event CRUD operations.
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
		$this->markTestIncomplete( 'TODO: implement after Plan 01 completes' );
	}

	/**
	 * Test that creating a virtual event persists virtual-specific fields.
	 *
	 * Covers EVNT-02: virtual_url and virtual_type are saved and retrievable
	 * after event creation.
	 */
	public function test_create_virtual_event() {
		$this->markTestIncomplete( 'TODO: implement after Plan 01 completes' );
	}

	/**
	 * Test that draft events are excluded from published-event queries.
	 *
	 * Covers EVNT-04: events with status=draft must not appear in queries
	 * that filter for published events only.
	 */
	public function test_draft_not_in_published_query() {
		$this->markTestIncomplete( 'TODO: implement after Plan 01 completes' );
	}
}
