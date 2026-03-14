<?php

/**
 * Test stubs for event content moderation integration.
 *
 * Covers requirement: ADMN-04 (events are registered as a reportable content
 * type in the BuddyBoss moderation system).
 *
 * @package BuddyBoss\Events\Tests
 */

/**
 * Class BP_Events_Test_Moderation
 *
 * @group bp-events
 * @group moderation
 */
class BP_Events_Test_Moderation extends WP_UnitTestCase {

	/**
	 * Test that the events content type is registered with the moderation system.
	 *
	 * Covers ADMN-04: after instantiating BP_Moderation_Events and applying the
	 * 'bp_moderation_content_types' filter, the returned array contains the key
	 * 'events' with value 'Events'.
	 */
	public function test_event_report_registers_content_type() {
		// Ensure the class exists — instantiation in the hook may not have run yet.
		if ( ! class_exists( 'BP_Moderation_Events' ) ) {
			require_once BP_PLUGIN_DIR . '/src/bp-events/classes/class-bp-moderation-events.php';
		}

		// Instantiate directly to register the filter.
		$moderation = new BP_Moderation_Events();

		// Apply the filter — content types array should now contain 'events'.
		$content_types = apply_filters( 'bp_moderation_content_types', array() );

		$this->assertArrayHasKey(
			'events',
			$content_types,
			'Expected "events" key in bp_moderation_content_types filter output.'
		);

		$this->assertSame(
			'Events',
			$content_types['events'],
			'Expected "Events" label for the events content type.'
		);
	}
}
