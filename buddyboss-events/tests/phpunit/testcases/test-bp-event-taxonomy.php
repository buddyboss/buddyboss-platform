<?php

/**
 * Tests for event taxonomy assignment.
 *
 * Covers requirements: TAX-01 (category assignment), TAX-02 (tag assignment).
 *
 * @package BuddyBoss\Events\Tests
 */

/**
 * Class BP_Events_Test_Taxonomy
 *
 * @group bp-events
 * @group event-taxonomy
 */
class BP_Events_Test_Taxonomy extends WP_UnitTestCase {

	/**
	 * Assign category to event via wp_set_object_terms(), retrieve with wp_get_object_terms().
	 *
	 * Covers TAX-01: category term stored against event object_id and readable back.
	 */
	public function test_event_category_assignment() {
		$this->markTestIncomplete( 'Implement in 04-02-PLAN.' );
	}

	/**
	 * Assign tag to event, verify retrieval.
	 *
	 * Covers TAX-02: tag term stored against event object_id and readable back.
	 */
	public function test_event_tag_assignment() {
		$this->markTestIncomplete( 'Implement in 04-02-PLAN.' );
	}

	/**
	 * Call bp_events_get_events(['category_id' => X]) and verify only matching events returned.
	 *
	 * Covers TAX-01: category_id filter in bp_events_get_events() narrows result set correctly.
	 */
	public function test_get_events_category_filter() {
		$this->markTestIncomplete( 'Implement in 04-02-PLAN.' );
	}

	/**
	 * Call bp_events_get_events(['tag_id' => X]) and verify only matching events returned.
	 *
	 * Covers TAX-02: tag_id filter in bp_events_get_events() narrows result set correctly.
	 */
	public function test_get_events_tag_filter() {
		$this->markTestIncomplete( 'Implement in 04-02-PLAN.' );
	}

	/**
	 * Store and retrieve _bb_event_cat_icon_id via update_term_meta()/get_term_meta().
	 *
	 * Covers TAX-01: category term meta roundtrip for icon attachment ID.
	 */
	public function test_category_icon_term_meta() {
		$this->markTestIncomplete( 'Implement in 04-02-PLAN.' );
	}
}
