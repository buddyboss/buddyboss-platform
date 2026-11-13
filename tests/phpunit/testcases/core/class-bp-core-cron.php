<?php
/**
 * Tests for BP_Core_Cron scheduling (PROD-10182).
 *
 * @package BuddyBoss\Core
 */

/**
 * A cron declared through bp_core_schedule_cron() after bp_init has fired must still be scheduled,
 * once, and the per-hook schedule filter must still be able to veto it.
 *
 * @group core
 * @group cron
 */
class BB_Tests_Core_Cron extends BP_UnitTestCase {

	/**
	 * Hook name passed to bp_core_schedule_cron().
	 *
	 * @var string
	 */
	const HOOK = 'test_prod_10182';

	/**
	 * Event name BP_Core_Cron::add() builds from self::HOOK.
	 *
	 * @var string
	 */
	const EVENT = 'bb_test_prod_10182_hook';

	/**
	 * Crons declared to BP_Core_Cron before the test.
	 *
	 * @var array
	 */
	protected $declared_crons = array();

	/**
	 * Start from a clean declared-cron list and no scheduled test event.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->declared_crons = bp_core_cron()->crons;
		bp_core_cron()->crons = array();
		wp_clear_scheduled_hook( self::EVENT );
	}

	/**
	 * Leave no test event, callback or filter behind.
	 */
	public function tearDown(): void {
		wp_clear_scheduled_hook( self::EVENT );
		remove_all_actions( self::EVENT );
		remove_filter( 'bp_core_cron_schedule_' . self::EVENT, '__return_false' );
		bp_core_cron()->crons = $this->declared_crons;
		parent::tearDown();
	}

	/**
	 * Number of scheduled occurrences of the test event.
	 *
	 * @return int
	 */
	protected function count_test_events() {
		$count = 0;
		foreach ( (array) _get_cron_array() as $hooks ) {
			if ( isset( $hooks[ self::EVENT ] ) ) {
				$count += count( $hooks[ self::EVENT ] );
			}
		}

		return $count;
	}

	/**
	 * The test bootstrap has already run bp_init, as an AJAX/REST save handler would have.
	 * Before PROD-10182 the cron was only queued for schedule() and never reached the cron table.
	 */
	public function test_cron_declared_after_bp_init_is_scheduled() {
		$this->assertGreaterThan( 0, did_action( 'bp_init' ), 'Precondition: bp_init has fired.' );

		$this->assertTrue( bp_core_schedule_cron( self::HOOK, '__return_true', 'bb_schedule_15min' ) );

		$this->assertNotFalse( wp_next_scheduled( self::EVENT ) );
		$this->assertSame( 'bb_schedule_15min', wp_get_schedule( self::EVENT ) );
		$this->assertSame( 1, $this->count_test_events() );
	}

	/**
	 * An event that already exists is kept as it is: declaring the cron again on a later
	 * request must not add a second occurrence.
	 */
	public function test_cron_declared_after_bp_init_keeps_existing_event() {
		$existing = time() + HOUR_IN_SECONDS;
		wp_schedule_event( $existing, 'bb_schedule_15min', self::EVENT );

		bp_core_schedule_cron( self::HOOK, '__return_true', 'bb_schedule_15min' );
		bp_core_schedule_cron( self::HOOK, '__return_true', 'bb_schedule_15min' );

		$this->assertSame( 1, $this->count_test_events() );
		$this->assertSame( $existing, wp_next_scheduled( self::EVENT ) );
	}

	/**
	 * The bp_core_cron_schedule_{hook} filter still vetoes scheduling on the immediate path.
	 */
	public function test_cron_declared_after_bp_init_respects_schedule_filter() {
		add_filter( 'bp_core_cron_schedule_' . self::EVENT, '__return_false' );

		bp_core_schedule_cron( self::HOOK, '__return_true', 'bb_schedule_15min' );

		$this->assertFalse( wp_next_scheduled( self::EVENT ) );
	}

	/**
	 * An unknown recurrence is still rejected before anything is declared or scheduled.
	 */
	public function test_unknown_recurrence_is_not_scheduled() {
		$this->assertFalse( bp_core_schedule_cron( self::HOOK, '__return_true', 'not_a_bb_schedule' ) );

		$this->assertFalse( wp_next_scheduled( self::EVENT ) );
		$this->assertSame( array(), bp_core_cron()->crons );
	}
}
