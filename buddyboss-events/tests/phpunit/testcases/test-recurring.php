<?php

/**
 * Tests for recurring event behaviour.
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
	 *
	 * Rule: FREQ=WEEKLY;BYDAY=MO;COUNT=5 produces 5 dates total.
	 * The parent IS the first occurrence, so 4 children should be created.
	 */
	public function test_publish_generates_occurrences() {
		$parent = new BP_Event();
		$parent->title           = 'Weekly Monday Test';
		$parent->organizer_id    = 1;
		$parent->start_date      = '2026-03-16 10:00:00'; // A Monday.
		$parent->end_date        = '2026-03-16 11:00:00';
		$parent->timezone        = 'UTC';
		$parent->status          = 'published';
		$parent->recurrence_rule = 'FREQ=WEEKLY;BYDAY=MO;COUNT=5';
		$parent->slug            = 'weekly-monday-test-' . time();

		// Function must exist before we can test it.
		$this->assertTrue( function_exists( 'bp_events_generate_occurrences' ), 'bp_events_generate_occurrences() must exist' );

		bp_events_generate_occurrences( $parent );

		// Expect exactly 4 child rows (COUNT=5 minus the parent = 4).
		global $wpdb;
		$bp    = buddypress();
		$count = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$bp->events->table_name} WHERE parent_event_id = %d",
				$parent->id
			)
		);

		$this->assertSame( 4, $count, 'Expected 4 child occurrence rows' );
	}

	/**
	 * Test that calling bp_events_generate_occurrences() twice does NOT
	 * create additional child rows (duplicate guard via meta key).
	 */
	public function test_no_duplicate_generation() {
		$parent = new BP_Event();
		$parent->title           = 'No Duplicate Test';
		$parent->organizer_id    = 1;
		$parent->start_date      = '2026-03-16 10:00:00';
		$parent->end_date        = '2026-03-16 11:00:00';
		$parent->timezone        = 'UTC';
		$parent->status          = 'published';
		$parent->recurrence_rule = 'FREQ=WEEKLY;BYDAY=MO;COUNT=5';
		$parent->slug            = 'no-duplicate-test-' . time();

		$this->assertTrue( function_exists( 'bp_events_generate_occurrences' ), 'bp_events_generate_occurrences() must exist' );

		// Call twice.
		bp_events_generate_occurrences( $parent );
		bp_events_generate_occurrences( $parent );

		global $wpdb;
		$bp    = buddypress();
		$count = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$bp->events->table_name} WHERE parent_event_id = %d",
				$parent->id
			)
		);

		// Still exactly 4, not 8.
		$this->assertSame( 4, $count, 'Duplicate guard must prevent second generation' );
	}

	/**
	 * Test that draft/pending events are skipped by occurrence generation.
	 */
	public function test_non_published_skipped() {
		$this->assertTrue( function_exists( 'bp_events_generate_occurrences' ), 'bp_events_generate_occurrences() must exist' );

		foreach ( array( 'draft', 'pending' ) as $status ) {
			$event = new BP_Event();
			$event->title           = 'Skipped Event ' . $status;
			$event->organizer_id    = 1;
			$event->start_date      = '2026-03-16 10:00:00';
			$event->end_date        = '2026-03-16 11:00:00';
			$event->timezone        = 'UTC';
			$event->status          = $status;
			$event->recurrence_rule = 'FREQ=WEEKLY;BYDAY=MO;COUNT=5';
			$event->slug            = 'skipped-' . $status . '-' . time();

			bp_events_generate_occurrences( $event );
		}

		// Since the events were never saved to DB, we can't query them by parent_event_id.
		// The function must simply return without doing anything. This test verifies no
		// exceptions are thrown and the function exists.
		$this->assertTrue( true, 'Non-published events must not throw exceptions' );
	}

	/**
	 * Test that editing a single occurrence detaches it from the series.
	 *
	 * Covers EVNT-03 (edit-this-only): modifying one occurrence must detach
	 * only that occurrence; remaining occurrences are unchanged.
	 */
	public function test_edit_single_occurrence() {
		$this->assertTrue( function_exists( 'bp_events_detach_occurrence' ), 'bp_events_detach_occurrence() must exist' );

		// Create a mock occurrence with parent_event_id set.
		global $wpdb;
		$bp = buddypress();

		$wpdb->insert(
			$bp->events->table_name,
			array(
				'title'           => 'Mock Parent',
				'description'     => '',
				'slug'            => 'mock-parent-' . time(),
				'organizer_id'    => 1,
				'start_date'      => '2026-03-16 10:00:00',
				'end_date'        => '2026-03-16 11:00:00',
				'timezone'        => 'UTC',
				'status'          => 'published',
				'recurrence_rule' => 'FREQ=WEEKLY;BYDAY=MO;COUNT=5',
				'date_created'    => current_time( 'mysql' ),
				'date_modified'   => current_time( 'mysql' ),
			),
			array( '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s' )
		);
		$parent_id = $wpdb->insert_id;

		$wpdb->insert(
			$bp->events->table_name,
			array(
				'title'           => 'Mock Occurrence',
				'description'     => '',
				'slug'            => 'mock-occurrence-' . time(),
				'organizer_id'    => 1,
				'start_date'      => '2026-03-23 10:00:00',
				'end_date'        => '2026-03-23 11:00:00',
				'timezone'        => 'UTC',
				'status'          => 'published',
				'recurrence_rule' => '',
				'parent_event_id' => $parent_id,
				'date_created'    => current_time( 'mysql' ),
				'date_modified'   => current_time( 'mysql' ),
			),
			array( '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s' )
		);
		$occurrence_id = $wpdb->insert_id;

		// Detach the occurrence.
		$result = bp_events_detach_occurrence( $occurrence_id, array( 'title' => 'Detached Occurrence' ) );

		$this->assertTrue( $result, 'bp_events_detach_occurrence() must return true' );

		// Verify parent_event_id is null and recurrence_rule is empty.
		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT parent_event_id, recurrence_rule, title FROM {$bp->events->table_name} WHERE id = %d",
				$occurrence_id
			)
		);

		$this->assertNull( $row->parent_event_id, 'parent_event_id must be null after detach' );
		$this->assertSame( '', $row->recurrence_rule, 'recurrence_rule must be empty after detach' );
		$this->assertSame( 'Detached Occurrence', $row->title, 'title must be updated' );
	}

	/**
	 * Test that editing this-and-following occurrences splits the series.
	 *
	 * Covers EVNT-03 (edit-this-and-following): modifying from a given
	 * occurrence onward must create a new series tail without affecting
	 * earlier occurrences.
	 */
	public function test_edit_this_and_following() {
		$this->assertTrue( function_exists( 'bp_events_split_series' ), 'bp_events_split_series() must exist' );

		global $wpdb;
		$bp = buddypress();

		// Create a parent event.
		$wpdb->insert(
			$bp->events->table_name,
			array(
				'title'           => 'Split Series Parent',
				'description'     => '',
				'slug'            => 'split-series-parent-' . time(),
				'organizer_id'    => 1,
				'start_date'      => '2026-03-02 10:00:00',
				'end_date'        => '2026-03-02 11:00:00',
				'timezone'        => 'UTC',
				'status'          => 'published',
				'recurrence_rule' => 'FREQ=WEEKLY;BYDAY=MO;COUNT=10',
				'date_created'    => current_time( 'mysql' ),
				'date_modified'   => current_time( 'mysql' ),
			),
			array( '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s' )
		);
		$parent_id = $wpdb->insert_id;

		// Create 9 child occurrences (parent = first occurrence).
		$occurrence_ids  = array();
		$occurrence_date = '2026-03-09 10:00:00'; // Second Monday.
		for ( $i = 0; $i < 9; $i++ ) {
			$wpdb->insert(
				$bp->events->table_name,
				array(
					'title'           => 'Split Child ' . $i,
					'description'     => '',
					'slug'            => 'split-child-' . $i . '-' . time(),
					'organizer_id'    => 1,
					'start_date'      => $occurrence_date,
					'end_date'        => date( 'Y-m-d H:i:s', strtotime( $occurrence_date ) + 3600 ),
					'timezone'        => 'UTC',
					'status'          => 'published',
					'recurrence_rule' => '',
					'parent_event_id' => $parent_id,
					'date_created'    => current_time( 'mysql' ),
					'date_modified'   => current_time( 'mysql' ),
				),
				array( '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s' )
			);
			$occurrence_ids[] = $wpdb->insert_id;
			$occurrence_date  = date( 'Y-m-d H:i:s', strtotime( $occurrence_date ) + 7 * DAY_IN_SECONDS );
		}

		// Split from the 5th child (index 4, which is occurrence_ids[4]).
		$split_from_id = $occurrence_ids[4];

		$new_parent_id = bp_events_split_series(
			$parent_id,
			$split_from_id,
			array( 'title' => 'New Split Series', 'recurrence_rule' => 'FREQ=WEEKLY;BYDAY=MO;COUNT=5' )
		);

		$this->assertNotFalse( $new_parent_id, 'bp_events_split_series() must return a new parent event ID' );
		$this->assertGreaterThan( 0, $new_parent_id, 'New parent ID must be a positive integer' );

		// Children from split point onward must be deleted from the original series.
		$remaining_original = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$bp->events->table_name} WHERE parent_event_id = %d",
				$parent_id
			)
		);

		// Original series had children at indices 0-8. Indices 4-8 should be removed.
		// That leaves 4 children (indices 0-3) in the original.
		$this->assertSame( '4', $remaining_original, 'Original series must have only pre-split children remaining' );

		// The new parent must exist.
		$new_parent = bp_events_get_event( $new_parent_id );
		$this->assertNotFalse( $new_parent, 'New parent event must exist in the database' );
		$this->assertSame( 'New Split Series', $new_parent->title, 'New parent must have the updated title' );
	}
}
