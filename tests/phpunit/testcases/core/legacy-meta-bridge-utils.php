<?php
/**
 * Tests for the legacy meta-box bridge save-value normalizer.
 *
 * @package BuddyBoss\Tests\Core
 * @since   BuddyBoss [BBVERSION]
 */

/**
 * Regression coverage for PROD-10441: a `toggle_list` checked map whose
 * option values are numeric (post IDs) must normalize to the selected IDs,
 * not to the checked flags, after `json_decode( …, true )` casts the keys.
 *
 * @group core
 * @group admin
 * @group legacy_meta_bridge
 */
class BP_Tests_Core_Legacy_Meta_Bridge_Utils extends BP_UnitTestCase {

	/**
	 * Parsed-name descriptor for an append-array input (`name="foo[]"`).
	 *
	 * @var array
	 */
	protected $parsed = array(
		'base'     => 'bbms_lite_access_memberships',
		'segments' => array( '' ),
		'is_array' => true,
	);

	/**
	 * Load the bridge utilities; they are admin-only and not loaded by the test bootstrap.
	 */
	public function setUp() {
		parent::setUp();

		if ( ! function_exists( 'bb_legacy_normalize_save_value' ) ) {
			require_once BP_PLUGIN_DIR . 'bp-core/admin/settings/legacy-meta-bridge-utils.php';
		}
	}

	/**
	 * Decode a JSON payload exactly the way the Settings 2.0 AJAX save
	 * handler does, so numeric string keys become integer keys.
	 *
	 * @param string $json JSON payload.
	 * @return mixed Decoded value.
	 */
	protected function decode( $json ) {
		return json_decode( $json, true );
	}

	/**
	 * PROD-10441: numeric option values (membership post IDs) must survive.
	 */
	public function test_toggle_list_numeric_keys_return_checked_ids_not_flags() {
		$value = $this->decode( '{"12":1,"15":1,"20":0}' );

		// Sanity: PHP has cast the keys to integers, which is what broke the old heuristic.
		$this->assertSame( array( 12, 15, 20 ), array_keys( $value ) );

		$this->assertSame(
			array( '12', '15' ),
			bb_legacy_normalize_save_value( 'toggle_list', $this->parsed, $value )
		);
	}

	/**
	 * Toggle list string keys return checked keys.
	 */
	public function test_toggle_list_string_keys_return_checked_keys() {
		$value = $this->decode( '{"tag_a":1,"tag_b":0,"tag_c":"1"}' );

		$this->assertSame(
			array( 'tag_a', 'tag_c' ),
			bb_legacy_normalize_save_value( 'toggle_list', $this->parsed, $value )
		);
	}

	/**
	 * Option values 0..n-1 decode to a PHP list; a key-shape heuristic would
	 * misread this as an already-flat array. Type-driven normalization must not.
	 */
	public function test_toggle_list_sequential_numeric_keys_still_treated_as_map() {
		$value = $this->decode( '{"0":1,"1":0,"2":1}' );

		$this->assertSame(
			array( '0', '2' ),
			bb_legacy_normalize_save_value( 'toggle_list', $this->parsed, $value )
		);
	}

	/**
	 * Toggle list unchecked flags are dropped.
	 */
	public function test_toggle_list_unchecked_flags_are_dropped() {
		$value = $this->decode( '{"12":0,"15":"0","20":false,"25":null,"30":""}' );

		$this->assertSame(
			array(),
			bb_legacy_normalize_save_value( 'toggle_list', $this->parsed, $value )
		);
	}

	/**
	 * Toggle list empty map returns empty list.
	 */
	public function test_toggle_list_empty_map_returns_empty_list() {
		$this->assertSame(
			array(),
			bb_legacy_normalize_save_value( 'toggle_list', $this->parsed, $this->decode( '{}' ) )
		);
	}

	/**
	 * Ajax multiselect flat list passes through as strings.
	 */
	public function test_ajax_multiselect_flat_list_passes_through_as_strings() {
		$value = $this->decode( '["12","15"]' );

		$this->assertSame(
			array( '12', '15' ),
			bb_legacy_normalize_save_value( 'ajax_multiselect', $this->parsed, $value )
		);
	}

	/**
	 * Ajax multiselect drops empty entries and reindexes.
	 */
	public function test_ajax_multiselect_drops_empty_entries_and_reindexes() {
		$value = $this->decode( '[123,"",456,null]' );

		$this->assertSame(
			array( '123', '456' ),
			bb_legacy_normalize_save_value( 'ajax_multiselect', $this->parsed, $value )
		);
	}

	/**
	 * Ajax multiselect empty list returns empty list.
	 */
	public function test_ajax_multiselect_empty_list_returns_empty_list() {
		$this->assertSame(
			array(),
			bb_legacy_normalize_save_value( 'ajax_multiselect', $this->parsed, $this->decode( '[]' ) )
		);
	}

	/**
	 * Other field types pass through unchanged.
	 */
	public function test_other_field_types_pass_through_unchanged() {
		$this->assertSame( 'hello', bb_legacy_normalize_save_value( 'text', $this->parsed, 'hello' ) );
		$this->assertSame( '1', bb_legacy_normalize_save_value( 'checkbox', $this->parsed, '1' ) );
		$this->assertSame(
			array( 'a' => 1 ),
			bb_legacy_normalize_save_value( 'select', $this->parsed, array( 'a' => 1 ) )
		);
	}

	/**
	 * Non array toggle list value passes through unchanged.
	 */
	public function test_non_array_toggle_list_value_passes_through_unchanged() {
		$this->assertSame( 'scalar', bb_legacy_normalize_save_value( 'toggle_list', $this->parsed, 'scalar' ) );
		$this->assertNull( bb_legacy_normalize_save_value( 'toggle_list', $this->parsed, null ) );
	}
}
