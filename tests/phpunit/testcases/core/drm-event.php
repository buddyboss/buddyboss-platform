<?php
/**
 * Tests for the BB_DRM_Event::latest() per-request cache.
 *
 * @package BuddyBoss\Tests
 */

use BuddyBoss\Core\Admin\DRM\BB_DRM_Event;

/**
 * @group core
 * @group drm
 */
class BB_Tests_DRM_Event extends BP_UnitTestCase {

	/**
	 * Event name used by the tests.
	 *
	 * @var string
	 */
	private $event_name = 'addon-bb-drm-test';

	public function set_up() {
		parent::set_up();

		if ( ! class_exists( BB_DRM_Event::class ) ) {
			require_once buddypress()->plugin_dir . 'bp-core/admin/drm/autoload.php';
		}

		BB_DRM_Event::create_table();
		BB_DRM_Event::flush_latest_cache();
	}

	public function tear_down() {
		BB_DRM_Event::flush_latest_cache();

		parent::tear_down();
	}

	/**
	 * Count the queries that read the latest event row.
	 *
	 * @param callable $callback Code to run.
	 * @return int
	 */
	private function count_latest_queries( $callback ) {
		$count  = 0;
		$filter = function ( $query ) use ( &$count ) {
			if ( false !== strpos( $query, 'bb_drm_events' ) && false !== strpos( $query, 'ORDER BY id DESC LIMIT 1' ) ) {
				++$count;
			}
			return $query;
		};

		add_filter( 'query', $filter );
		$callback();
		remove_filter( 'query', $filter );

		return $count;
	}

	public function test_latest_queries_database_once_per_request() {
		BB_DRM_Event::record( $this->event_name, 1, BB_DRM_Event::$addon_str );

		$queries = $this->count_latest_queries(
			function () {
				for ( $i = 0; $i < 10; $i++ ) {
					BB_DRM_Event::latest( $this->event_name );
				}
			}
		);

		$this->assertSame( 1, $queries );
	}

	public function test_latest_caches_missing_row() {
		$queries = $this->count_latest_queries(
			function () {
				$this->assertNull( BB_DRM_Event::latest( $this->event_name ) );
				$this->assertNull( BB_DRM_Event::latest( $this->event_name ) );
			}
		);

		$this->assertSame( 1, $queries );
	}

	public function test_record_after_cached_missing_row_is_visible() {
		$this->assertNull( BB_DRM_Event::latest( $this->event_name ) );

		$id = BB_DRM_Event::record( $this->event_name, 1, BB_DRM_Event::$addon_str );

		$latest = BB_DRM_Event::latest( $this->event_name );
		$this->assertNotNull( $latest );
		$this->assertSame( (int) $id, (int) $latest->id );
	}

	public function test_store_update_refreshes_cached_args() {
		BB_DRM_Event::record( $this->event_name, 1, BB_DRM_Event::$addon_str, array( 'status' => 'low' ) );

		$event       = BB_DRM_Event::latest( $this->event_name );
		$event->args = wp_json_encode( array( 'status' => 'low', 'drm_locked_sent' => '2026-01-01 00:00:00' ) );
		$event->store();

		$args = BB_DRM_Event::latest( $this->event_name )->get_args();
		$this->assertSame( '2026-01-01 00:00:00', $args->drm_locked_sent );
	}

	public function test_direct_update_with_flush_refreshes_cached_args() {
		global $wpdb;

		BB_DRM_Event::record( $this->event_name, 1, BB_DRM_Event::$addon_str, array( 'status' => 'high' ) );
		$event = BB_DRM_Event::latest( $this->event_name );

		$wpdb->update(
			BB_DRM_Event::get_table_name(),
			array( 'args' => wp_json_encode( array( 'status' => 'high', 'drm_high_sent' => '2026-01-01 00:00:00' ) ) ),
			array( 'id' => $event->id )
		);
		BB_DRM_Event::flush_latest_cache( $this->event_name );

		$args = BB_DRM_Event::latest( $this->event_name )->get_args();
		$this->assertSame( '2026-01-01 00:00:00', $args->drm_high_sent );
	}

	public function test_destroy_clears_cached_event() {
		BB_DRM_Event::record( $this->event_name, 1, BB_DRM_Event::$addon_str );

		$this->assertTrue( BB_DRM_Event::latest( $this->event_name )->destroy() );
		$this->assertNull( BB_DRM_Event::latest( $this->event_name ) );
	}

	public function test_failed_read_is_not_cached() {
		global $wpdb;

		BB_DRM_Event::record( $this->event_name, 1, BB_DRM_Event::$addon_str );

		$prefix         = $wpdb->prefix;
		$wpdb->prefix   = $prefix . 'missing_';
		$suppress       = $wpdb->suppress_errors( true );
		$missing_result = BB_DRM_Event::latest( $this->event_name );
		$wpdb->suppress_errors( $suppress );
		$wpdb->prefix = $prefix;

		$this->assertNull( $missing_result );
		$this->assertNotNull( BB_DRM_Event::latest( $this->event_name ) );
	}
}
