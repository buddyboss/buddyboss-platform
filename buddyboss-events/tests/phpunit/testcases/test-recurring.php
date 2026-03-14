<?php

/**
 * Test stubs for recurring event behaviour.
 *
 * Covers requirement: EVNT-03 (recurring events — occurrences, single edit,
 * edit-this-and-following).
 *
 * @package BuddyBoss\Events\Tests
 */

/**
 * Class BP_Events_Test_Recurring
 *
 * @group bp-events
 * @group recurring
 */
class BP_Events_Test_Recurring extends WP_UnitTestCase {

	/**
	 * Test that publishing a recurring parent event generates child occurrence rows.
	 *
	 * Covers EVNT-03: publishing a parent event with an RRULE creates the
	 * expected child occurrence records in storage.
	 */
	public function test_publish_generates_occurrences() {
		$this->markTestIncomplete( 'TODO: implement after Plan 02 completes' );
	}

	/**
	 * Test that editing a single occurrence detaches it from the series.
	 *
	 * Covers EVNT-03 (edit-this-only): modifying one occurrence must detach
	 * only that occurrence; remaining occurrences are unchanged.
	 */
	public function test_edit_single_occurrence() {
		$this->markTestIncomplete( 'TODO: implement after Plan 02 completes' );
	}

	/**
	 * Test that editing this-and-following occurrences splits the series.
	 *
	 * Covers EVNT-03 (edit-this-and-following): modifying from a given
	 * occurrence onward must create a new series tail without affecting
	 * earlier occurrences.
	 */
	public function test_edit_this_and_following() {
		$this->markTestIncomplete( 'TODO: implement after Plan 02 completes' );
	}
}
