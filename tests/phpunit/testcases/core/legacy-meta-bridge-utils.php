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
 * not to the checked flags, after PHP casts the numeric keys to integers.
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
	public function setUp(): void {
		parent::setUp();

		if ( ! function_exists( 'bb_legacy_normalize_save_value' ) ) {
			require_once BP_PLUGIN_DIR . 'bp-core/admin/settings/legacy-meta-bridge-utils.php';
		}
	}

	/**
	 * Restore $_POST after each test; the bridge closures write into it.
	 */
	public function tearDown(): void {
		$_POST = array();
		remove_filter( 'bb_legacy_ajax_select_resolvers', array( $this, 'filter_add_test_resolver' ) );

		parent::tearDown();
	}

	/**
	 * Decode a JSON payload into a PHP array with integer-cast numeric keys —
	 * the same key shape PHP's request parser produces for the FormData
	 * bracket keys (`field[12]=1`) the Settings 2.0 save posts.
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

	/**
	 * PROD-10441 end to end through the CPT bridge: the registered
	 * `save_value` closure of a plain `<select multiple>` (Membership's Access
	 * Protection box) injects the CHECKED membership IDs into $_POST.
	 */
	public function test_cpt_bridge_toggle_list_save_value_injects_checked_ids() {
		$fields = $this->bridge_test_box();
		$field  = $this->find_bridged_field( $fields, 'bbtest_memberships' );

		$this->assertSame( 'toggle_list', $field['type'] );

		// Key shape PHP's request parser produces for `…[12]=1&…[15]=1&…[20]=0`.
		call_user_func( $field['save_value'], null, array( 12 => '1', 15 => '1', 20 => '0' ) );

		$this->assertSame( array( '12', '15' ), $_POST['bbtest_memberships'] );
	}

	/**
	 * The bridge must hand an AJAX picker's flat list to the normalizer as
	 * `ajax_multiselect`. Its captured input type is still `toggle_list`, so
	 * passing that instead would read the list positions as a checked map and
	 * inject the indexes (`['0','1']`) in place of the selected ids.
	 */
	public function test_cpt_bridge_ajax_multiselect_save_value_injects_selected_ids() {
		add_filter( 'bb_legacy_ajax_select_resolvers', array( $this, 'filter_add_test_resolver' ) );

		$fields = $this->bridge_test_box();
		$field  = $this->find_bridged_field( $fields, 'bbtest_tags' );

		$this->assertSame( 'ajax_multiselect', $field['type'] );

		call_user_func( $field['save_value'], null, array( '12', '15' ) );

		$this->assertSame( array( '12', '15' ), $_POST['bbtest_tags'] );
	}

	/**
	 * Register a test-only AJAX-select resolver matching the `bbtest-ajax` class.
	 *
	 * @param array $resolvers Registered resolvers.
	 * @return array
	 */
	public function filter_add_test_resolver( $resolvers ) {
		$resolvers['bbtest'] = array(
			'match'  => 'bbtest-ajax',
			'search' => '__return_empty_array',
		);

		return $resolvers;
	}

	/**
	 * Run bb_legacy_run_cpt_bridge_box() over a seeded metabox capture and
	 * return the fields it registered, keyed by field id.
	 *
	 * The captured inputs are seeded through the same transient the bridge
	 * reads, so no metabox HTML needs to be rendered.
	 *
	 * @return array
	 */
	protected function bridge_test_box() {
		$box  = array(
			'id'    => 'bbtest-box',
			'title' => 'Test Box',
		);
		$args = array(
			'post_type'      => 'forum',
			'request_param'  => 'forum_id',
			'canonical_keys' => array(),
			'tab'            => 'general',
		);

		$version   = defined( 'BP_PLATFORM_VERSION' ) ? BP_PLATFORM_VERSION : '0';
		$cache_key = 'bb_legacy_cpt_box_inputs_' . md5( $args['post_type'] . '|' . $box['id'] . '|' . $version . '|v12-multiselect-meta-read' );
		set_transient(
			$cache_key,
			array(
				array(
					'name'  => 'bbtest_memberships[]',
					'type'  => 'toggle_list',
					'label' => 'Memberships',
					'class' => '',
				),
				array(
					'name'  => 'bbtest_tags[]',
					'type'  => 'toggle_list',
					'label' => 'Tags',
					'class' => 'select4 bbtest-ajax',
				),
			)
		);

		$registry = new class() {
			/**
			 * Registered field args keyed by field id.
			 *
			 * @var array
			 */
			public $fields = array();

			/**
			 * Capture a registration.
			 *
			 * @param string $component Component.
			 * @param string $id        Field id.
			 * @param array  $args      Field args.
			 */
			public function register( $component, $id, $args ) {
				$this->fields[ $id ] = $args;
			}
		};

		$order = 0;
		bb_legacy_run_cpt_bridge_box( $registry, 'forums', $box, $order, array(), $args );

		delete_transient( $cache_key );

		return $registry->fields;
	}

	/**
	 * Find a bridged field by the base of its input name.
	 *
	 * @param array  $fields Registered fields keyed by id.
	 * @param string $base   Input name base (e.g. `bbtest_tags`).
	 * @return array
	 */
	protected function find_bridged_field( $fields, $base ) {
		foreach ( $fields as $id => $field ) {
			if ( false !== strpos( $id, $base ) ) {
				return $field;
			}
		}

		$this->fail( 'Bridged field not registered: ' . $base . ' (got: ' . implode( ', ', array_keys( $fields ) ) . ')' );
	}
}
