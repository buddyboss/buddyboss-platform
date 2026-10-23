<?php
/**
 * Tests for the document activity meta shim used by older BuddyBoss Theme builds.
 *
 * @package BuddyBoss\Tests
 * @since   BuddyBoss [BBVERSION]
 */

/**
 * Older BuddyBoss Theme builds: document activity meta is hidden while Documents is not loaded.
 *
 * Theme builds before PROD-9995 build BP_Document objects from activity meta in the
 * Elementor Activity widget. bb_register_theme_document_meta_compat() hides those keys
 * on single-key get_metadata() reads (bp_activity_get_meta()) and on the array returned
 * by bb_activity_get_metadata(), cold or primed, without caching a stripped copy.
 *
 * @package BuddyBoss
 * @group core
 * @group activity
 */
class BP_Tests_Theme_Document_Meta_Compat extends BP_UnitTestCase {

	/**
	 * Activity ID carrying document meta.
	 *
	 * @var int
	 */
	protected $activity_id;

	/**
	 * Create an activity with document meta and add the compatibility filters.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	public function setUp(): void {
		parent::setUp();

		if ( class_exists( 'BP_Document' ) ) {
			$this->markTestSkipped( 'BP_Document is loaded; the compatibility filters only act without it.' );
		}

		$this->activity_id = self::factory()->activity->create();
		bp_activity_update_meta( $this->activity_id, 'bp_document_ids', '123' );
		bp_activity_update_meta( $this->activity_id, 'bp_document_folder_activity', '45' );
		bp_activity_update_meta( $this->activity_id, 'bb_unrelated_key', 'kept' );

		// What bb_register_theme_document_meta_compat() adds for an older theme.
		add_filter( 'get_activity_metadata', 'bb_theme_document_activity_meta_compat', 10, 4 );
		add_filter( 'bb_activity_get_metadata', 'bb_theme_document_activity_cached_meta_compat', 10, 2 );
	}

	/**
	 * Remove the compatibility filters.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	public function tearDown(): void {
		remove_filter( 'get_activity_metadata', 'bb_theme_document_activity_meta_compat', 10 );
		remove_filter( 'bb_activity_get_metadata', 'bb_theme_document_activity_cached_meta_compat', 10 );

		parent::tearDown();
	}

	/**
	 * Cold read: the returned array hides the document keys, but the cache keeps the raw rows.
	 *
	 * A stripped array in a persistent object cache would hide the documents after Add-ons is back.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	public function test_cold_read_hides_document_keys() {
		wp_cache_delete( $this->activity_id, 'activity_meta' );

		$meta = bb_activity_get_metadata( $this->activity_id );

		$cached = wp_cache_get( $this->activity_id, 'activity_meta' );
		$this->assertArrayHasKey( 'bp_document_ids', $cached );

		$this->assertArrayNotHasKey( 'bp_document_ids', $meta );
		$this->assertArrayNotHasKey( 'bp_document_folder_activity', $meta );
		$this->assertSame( 'kept', $meta['bb_unrelated_key'][0] );
	}

	/**
	 * Warm read: after the activity loop primes the cache, the cached array is filtered too.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	public function test_primed_cache_read_hides_document_keys() {
		wp_cache_delete( $this->activity_id, 'activity_meta' );
		bp_activity_update_meta_cache( array( $this->activity_id ) ); // What bp_has_activities() runs.

		// The primed entry still holds the raw rows; only the read is filtered.
		$cached = wp_cache_get( $this->activity_id, 'activity_meta' );
		$this->assertArrayHasKey( 'bp_document_ids', $cached );

		$meta = bb_activity_get_metadata( $this->activity_id );

		$this->assertArrayNotHasKey( 'bp_document_ids', $meta );
		$this->assertArrayNotHasKey( 'bp_document_folder_activity', $meta );
		$this->assertSame( 'kept', $meta['bb_unrelated_key'][0] );
	}

	/**
	 * Single-key reads return the empty value, so the theme never builds a BP_Document.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	public function test_single_key_read_is_empty() {
		$this->assertSame( '', bp_activity_get_meta( $this->activity_id, 'bp_document_ids', true ) );
		$this->assertSame( 'kept', bp_activity_get_meta( $this->activity_id, 'bb_unrelated_key', true ) );
	}

	/**
	 * The stored meta is untouched, so documents come back once Add-ons is active again.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	public function test_stored_meta_is_untouched() {
		remove_filter( 'get_activity_metadata', 'bb_theme_document_activity_meta_compat', 10 );

		$this->assertSame( '123', bp_activity_get_meta( $this->activity_id, 'bp_document_ids', true ) );
	}
}
