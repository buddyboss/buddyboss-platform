<?php

/**
 * Tests for taxonomy archive privacy filtering.
 *
 * Covers requirements: TAX-03 (private/hidden group events excluded from taxonomy archives).
 *
 * @package BuddyBoss\Events\Tests
 */

/**
 * Class BP_Events_Test_Taxonomy_Privacy
 *
 * @group bp-events
 * @group event-taxonomy-privacy
 */
class BP_Events_Test_Taxonomy_Privacy extends WP_UnitTestCase {

	/**
	 * Private group event in a category is excluded from archive query.
	 *
	 * Covers TAX-03: pre_get_posts filter removes events belonging to private groups.
	 */
	public function test_privacy_filter_excludes_private() {
		$this->markTestIncomplete( 'Implement in 04-01-PLAN.' );
	}

	/**
	 * Hidden group event in a category is excluded from archive query.
	 *
	 * Covers TAX-03: pre_get_posts filter removes events belonging to hidden groups.
	 */
	public function test_privacy_filter_excludes_hidden() {
		$this->markTestIncomplete( 'Implement in 04-01-PLAN.' );
	}

	/**
	 * Public group event in a category is included in archive query.
	 *
	 * Covers TAX-03: pre_get_posts filter does NOT remove events in public groups.
	 */
	public function test_privacy_filter_allows_public() {
		$this->markTestIncomplete( 'Implement in 04-01-PLAN.' );
	}

	/**
	 * Full integration: logged-out user visiting category archive sees zero private group events.
	 *
	 * Covers TAX-03: end-to-end — unauthenticated request to taxonomy archive returns only
	 * public events when the result set contains a mix of public and private group events.
	 */
	public function test_taxonomy_archive_privacy() {
		$this->markTestIncomplete( 'Implement in 04-01-PLAN.' );
	}
}
