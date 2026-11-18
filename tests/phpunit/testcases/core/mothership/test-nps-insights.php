<?php
/**
 * Tests for the GroundLevel Insights (NPS survey) wiring in BB_Mothership_Loader.
 *
 * @group core
 * @group mothership
 * @group insights
 */

use BuddyBoss\Core\Admin\Mothership\BB_Mothership_Loader;

class BP_Tests_Core_Mothership_NPS_Insights extends BP_UnitTestCase {

	/**
	 * Loader instance under test.
	 *
	 * @var BB_Mothership_Loader
	 */
	protected $loader;

	public function set_up() {
		parent::set_up();
		$this->loader = BB_Mothership_Loader::instance();
	}

	/**
	 * Read a private loader property.
	 *
	 * @param string $name Property name.
	 * @return mixed
	 */
	protected function get_private( $name ) {
		$prop = new ReflectionProperty( $this->loader, $name );
		$prop->setAccessible( true );
		return $prop->getValue( $this->loader );
	}

	/**
	 * Call a private loader method.
	 *
	 * @param string $name Method name.
	 * @param array  $args Arguments.
	 * @return mixed
	 */
	protected function call_private( $name, array $args = array() ) {
		$method = new ReflectionMethod( $this->loader, $name );
		$method->setAccessible( true );
		return $method->invokeArgs( $this->loader, $args );
	}

	/**
	 * Skip when the Insights provider did not register (package missing or container failed).
	 */
	protected function require_insights() {
		if ( ! $this->get_private( 'insights_registered' ) ) {
			$this->markTestSkipped( 'GroundLevel Insights provider is not registered in this vendor tree.' );
		}
	}

	public function test_sort_newest_first_orders_rows_and_keeps_cursor() {
		$store = array(
			'old'      => array( 'id' => 'old', 'publishesAt' => '2026-05-12T23:15:23+00:00' ),
			'__lastId' => 'old',
			'newer'    => array( 'id' => 'newer', 'publishes_at' => '2026-07-20 14:00:00' ),
			'newest'   => array( 'id' => 'newest', 'publishesAt' => '2026-10-02 11:46:42' ),
		);

		$sorted = $this->loader->sort_ipn_store_newest_first( $store );

		$this->assertSame( array( 'newest', 'newer', 'old', '__lastId' ), array_keys( $sorted ) );
		$this->assertSame( 'old', $sorted['__lastId'] );
		$this->assertSame( $store['newer'], $sorted['newer'] );
	}

	public function test_sort_newest_first_passes_through_non_array_and_single_row() {
		$this->assertSame( 'x', $this->loader->sort_ipn_store_newest_first( 'x' ) );
		$this->assertFalse( $this->loader->sort_ipn_store_newest_first( false ) );
		$single = array( 'a' => array( 'publishesAt' => '2026-01-01 00:00:00' ) );
		$this->assertSame( $single, $this->loader->sort_ipn_store_newest_first( $single ) );
	}

	public function test_sort_newest_first_treats_missing_dates_as_oldest() {
		$store  = array(
			'nodate' => array( 'id' => 'nodate' ),
			'dated'  => array( 'id' => 'dated', 'publishesAt' => '2026-01-01 00:00:00' ),
		);
		$sorted = $this->loader->sort_ipn_store_newest_first( $store );
		$this->assertSame( array( 'dated', 'nodate' ), array_keys( $sorted ) );
	}

	public function test_insights_prefix_is_fixed_and_hooks_are_bound() {
		$this->require_insights();

		$container = $this->get_private( 'container' );
		$this->assertSame( BB_Mothership_Loader::INSIGHTS_PREFIX, $container->get( 'insights.prefix' ) );
		$this->assertSame( BB_Mothership_Loader::INSIGHTS_REST_NAMESPACE, $container->get( 'insights.rest_namespace' ) );

		$this->assertSame( 5, has_action( BB_Mothership_Loader::INSIGHTS_NPS_CRON_HOOK, array( $this->loader, 'purge_expired_nps_survey' ) ) );
		$this->assertNotFalse( has_action( 'bp_deactivation', array( $this->loader, 'clear_scheduled_events' ) ) );

		$store_option = $this->call_private( 'get_ipn_prefixed_id', array( 'store' ) );
		$this->assertStringEndsWith( '_ipn_store', $store_option );
		$this->assertNotFalse( has_filter( 'option_' . $store_option, array( $this->loader, 'sort_ipn_store_newest_first' ) ) );
	}

	public function test_purge_expired_nps_survey_removes_only_the_expired_survey_row() {
		$this->require_insights();

		$store_option = $this->call_private( 'get_ipn_prefixed_id', array( 'store' ) );
		$yesterday    = gmdate( 'Y-m-d H:i:s', time() - DAY_IN_SECONDS );
		$last_month   = gmdate( 'Y-m-d H:i:s', time() - 30 * DAY_IN_SECONDS );
		$next_month   = gmdate( 'Y-m-d H:i:s', time() + 30 * DAY_IN_SECONDS );

		update_option(
			$store_option,
			array(
				'nps_survey' => array(
					'id'          => 'nps_survey',
					'subject'     => 'How are we doing?',
					'content'     => '',
					'icon'        => '',
					'read'        => false,
					'readAt'      => 0,
					'publishesAt' => $last_month,
					'expiresAt'   => $yesterday,
				),
				'other'      => array(
					'id'          => 'other',
					'subject'     => 'Keep me',
					'content'     => '',
					'icon'        => '',
					'read'        => false,
					'readAt'      => 0,
					'publishesAt' => $last_month,
					'expiresAt'   => $next_month,
				),
				'__lastId'   => 'other',
			),
			false
		);

		$this->loader->purge_expired_nps_survey();

		$after = get_option( $store_option );
		$this->assertArrayNotHasKey( 'nps_survey', $after );
		$this->assertArrayHasKey( 'other', $after );
		$this->assertSame( 'other', $after['__lastId'] );

		// An unexpired survey row must be left alone.
		$after['nps_survey'] = array_merge( $after['other'], array( 'id' => 'nps_survey', 'subject' => 'How are we doing?' ) );
		update_option( $store_option, $after, false );
		$this->loader->purge_expired_nps_survey();
		$this->assertArrayHasKey( 'nps_survey', get_option( $store_option ) );

		delete_option( $store_option );
	}

	public function test_clear_scheduled_events_unschedules_the_insights_cron() {
		$this->require_insights();

		$hook = BB_Mothership_Loader::INSIGHTS_NPS_CRON_HOOK;
		wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', $hook );
		$this->assertNotFalse( wp_next_scheduled( $hook ) );

		$this->loader->clear_scheduled_events();

		$this->assertFalse( wp_next_scheduled( $hook ) );
	}
}
