<?php
/**
 * Older BuddyBoss Theme builds: document activity meta is hidden while Documents is not loaded.
 *
 * Theme builds before PROD-9995 build BP_Document objects from activity meta in the
 * Elementor Activity widget. bb_register_theme_document_meta_compat() hides those keys
 * on both read paths: get_metadata() (bp_activity_get_meta()) and the cached array
 * returned by bb_activity_get_metadata() once the activity loop primed the cache.
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

	public function tearDown(): void {
		remove_filter( 'get_activity_metadata', 'bb_theme_document_activity_meta_compat', 10 );
		remove_filter( 'bb_activity_get_metadata', 'bb_theme_document_activity_cached_meta_compat', 10 );

		parent::tearDown();
	}

	/**
	 * Cold read: the get_activity_metadata filter hides the document keys.
	 */
	public function test_cold_read_hides_document_keys() {
		wp_cache_delete( $this->activity_id, 'activity_meta' );

		$meta = bb_activity_get_metadata( $this->activity_id );

		$this->assertArrayNotHasKey( 'bp_document_ids', $meta );
		$this->assertArrayNotHasKey( 'bp_document_folder_activity', $meta );
		$this->assertSame( 'kept', $meta['bb_unrelated_key'][0] );
	}

	/**
	 * Warm read: after the activity loop primes the cache, the cached array is filtered too.
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
	 */
	public function test_single_key_read_is_empty() {
		$this->assertSame( '', bp_activity_get_meta( $this->activity_id, 'bp_document_ids', true ) );
		$this->assertSame( 'kept', bp_activity_get_meta( $this->activity_id, 'bb_unrelated_key', true ) );
	}

	/**
	 * The stored meta is untouched, so documents come back once Add-ons is active again.
	 */
	public function test_stored_meta_is_untouched() {
		remove_filter( 'get_activity_metadata', 'bb_theme_document_activity_meta_compat', 10 );

		$this->assertSame( '123', bp_activity_get_meta( $this->activity_id, 'bp_document_ids', true ) );
	}
}
