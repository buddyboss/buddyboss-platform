<?php
/**
 * Activity edit duration normalisation (PROD-10522).
 *
 * @group activity
 * @group activity_edit_time
 */
class BP_Tests_Activity_Edit_Time extends BP_UnitTestCase {

	/**
	 * Every stored value the normaliser has to resolve, and what it must resolve to.
	 *
	 * @return array
	 */
	public function provider_stored_values() {
		return array(
			// Invalid or unrepresentable → registered default.
			'empty string'     => array( '', 600 ),
			'non numeric'      => array( 'abc', 600 ),
			'out of set'       => array( '999', 600 ),
			'zero string'      => array( '0', 600 ),
			'zero int'         => array( 0, 600 ),
			'null'             => array( null, 600 ),
			'false'            => array( false, 600 ),
			'true'             => array( true, 600 ),
			'array'            => array( array(), 600 ),
			'exponent'         => array( '1e3', 600 ),
			// Allowed set, as string and as int, passes through unchanged.
			'forever string'   => array( '-1', -1 ),
			'forever int'      => array( -1, -1 ),
			'10 minutes'       => array( '600', 600 ),
			'10 minutes int'   => array( 600, 600 ),
			'1 hour'           => array( '3600', 3600 ),
			'1 day'            => array( 86400, 86400 ),
			'7 days'           => array( '604800', 604800 ),
			'30 days'          => array( 2592000, 2592000 ),
		);
	}

	/**
	 * @dataProvider provider_stored_values
	 */
	public function test_normalize_edit_time( $stored, $expected ) {
		$this->assertSame( $expected, bb_activity_normalize_edit_time( $stored ) );
	}

	/**
	 * The fallback is validated against the same set, so the return type contract holds.
	 */
	public function test_default_must_be_an_allowed_duration() {
		$this->assertSame( 600, bb_activity_normalize_edit_time( '', 999999 ) );
		$this->assertSame( 600, bb_activity_normalize_edit_time( '', 'abc' ) );
		$this->assertSame( -1, bb_activity_normalize_edit_time( '', -1 ) );
		$this->assertSame( 3600, bb_activity_normalize_edit_time( 'abc', '3600' ) );
	}

	/**
	 * Read and write must agree on every input.
	 *
	 * @dataProvider provider_stored_values
	 */
	public function test_sanitizer_matches_normalizer( $stored, $expected ) {
		$callbacks = buddypress()->plugin_dir . 'bp-core/admin/settings/activity/callbacks.php';
		if ( ! function_exists( 'bb_activity_sanitize_edit_time' ) && file_exists( $callbacks ) ) {
			require_once $callbacks;
		}
		if ( ! function_exists( 'bb_activity_sanitize_edit_time' ) ) {
			$this->markTestSkipped( 'Admin settings callbacks are not loadable in this context.' );
		}

		$this->assertSame( $expected, bb_activity_sanitize_edit_time( $stored ) );
	}

	/**
	 * The getters normalise the stored option; an absent row resolves to the default.
	 */
	public function test_getters_normalize_stored_option() {
		$original = bp_get_option( '_bp_activity_edit_time', null );

		bp_update_option( '_bp_activity_edit_time', '' );
		$this->assertSame( 600, bp_get_activity_edit_time() );

		bp_update_option( '_bp_activity_edit_time', '-1' );
		$this->assertSame( -1, bp_get_activity_edit_time() );

		bp_delete_option( '_bp_activity_edit_time' );
		$this->assertSame( 600, bp_get_activity_edit_time() );
		$this->assertSame( -1, bp_get_activity_edit_time( -1 ) );
		$this->assertSame( 600, bp_get_activity_edit_time( 999999 ) );

		if ( null !== $original ) {
			bp_update_option( '_bp_activity_edit_time', $original );
		}
	}

	/**
	 * The allowed list follows the filterable option set on every call (no per-request memo).
	 */
	public function test_allowed_list_follows_filter() {
		$add = function ( $times ) {
			$times['one_week_plus'] = array( 'value' => 700000, 'label' => 'Custom' );
			return $times;
		};
		$this->assertNotContains( 700000, bb_activity_get_allowed_edit_times() );

		add_filter( 'bp_activity_edit_times', $add );
		$this->assertContains( 700000, bb_activity_get_allowed_edit_times() );
		$this->assertSame( 700000, bb_activity_normalize_edit_time( '700000' ) );
		remove_filter( 'bp_activity_edit_times', $add );

		$this->assertNotContains( 700000, bb_activity_get_allowed_edit_times() );
	}
}
