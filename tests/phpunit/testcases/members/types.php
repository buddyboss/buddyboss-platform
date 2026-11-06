<?php

/**
 * @group members
 * @group member_types
 */
class BP_Tests_Members_Types extends BP_UnitTestCase {
	public function setUp() {
		parent::setUp();

		bboss()->members->types = array();
	}

	public function test_bp_register_member_type_should_fail_for_existing_member_type() {
		bboss_register_member_type( 'foo' );
		$this->assertWPError( bboss_register_member_type( 'foo' ) );
	}

	public function test_bp_register_member_type_should_return_type_object() {
		$this->assertInternalType( 'object', bboss_register_member_type( 'foo' ) );
	}

	/**
	 * @dataProvider illegal_names
	 * @ticket BP5192
	 */
	public function test_illegal_names( $name ) {
		$this->assertWPError( bboss_register_member_type( $name ) );
	}

	public function illegal_names() {
		return array(
			array( 'any' ),
			array( 'null' ),
			array( '_none' ),
		);
	}

	/**
	 * @ticket BP6139
	 */
	public function test_bp_register_member_type_should_sanitize_member_type_key() {
		$key = 'F//oo% -Bar';
		$sanitized_key = 'foo-bar';

		$object = bboss_register_member_type( $key );
		$this->assertSame( $sanitized_key, $object->name );
	}

	public function test_bp_register_member_type_should_store_member_type_string_as_name_property() {
		$object = bboss_register_member_type( 'foo' );
		$this->assertSame( 'foo', $object->name );
	}

	public function test_bp_register_member_type_should_fill_in_missing_labels_with_ucfirst_member_type() {
		$object = bboss_register_member_type( 'foo' );
		foreach ( $object->labels as $label ) {
			$this->assertSame( 'Foo', $label );
		}
	}

	/**
	 * @ticket BP6125
	 */
	public function test_bp_register_member_type_should_respect_custom_name_label() {
		$object = bboss_register_member_type( 'foo', array(
			'labels' => array(
				'name' => 'Bar',
			),
		) );

		// 'singular_name' falls back on 'name'.
		$this->assertSame( 'Bar', $object->labels['name'] );
		$this->assertSame( 'Bar', $object->labels['singular_name'] );
	}

	/**
	 * @ticket BP6125
	 */
	public function test_bp_register_member_type_should_respect_custom_singular_name_label() {
		$object = bboss_register_member_type( 'foo', array(
			'labels' => array(
				'singular_name' => 'Bar',
			),
		) );

		// 'name' is set to upper-case version of profile type name.
		$this->assertSame( 'Foo', $object->labels['name'] );
		$this->assertSame( 'Bar', $object->labels['singular_name'] );
	}

	/**
	 * @ticket BP6286
	 */
	public function test_bp_register_member_type_has_directory_should_default_to_true() {
		$object = bboss_register_member_type( 'foo', array(
			'has_directory' => true,
		) );

		$this->assertTrue( $object->has_directory );
		$this->assertSame( 'foo', $object->directory_slug );
	}

	/**
	 * @ticket BP6286
	 */
	public function test_bp_register_member_type_has_directory_true() {
		$object = bboss_register_member_type( 'foo', array(
			'has_directory' => true,
		) );

		$this->assertTrue( $object->has_directory );
		$this->assertSame( 'foo', $object->directory_slug );
	}

	/**
	 * @ticket BP6286
	 */
	public function test_bp_register_member_type_should_store_has_directory_false() {
		$object = bboss_register_member_type( 'foo', array(
			'has_directory' => false,
		) );

		$this->assertFalse( $object->has_directory );
		$this->assertSame( '', $object->directory_slug );
	}

	/**
	 * @ticket BP6286
	 */
	public function test_bp_register_member_type_should_store_has_directory_string() {
		$object = bboss_register_member_type( 'foo', array(
			'has_directory' => 'foos',
		) );

		$this->assertTrue( $object->has_directory );
		$this->assertSame( 'foos', $object->directory_slug );
	}

	public function test_bp_get_member_type_object_should_return_null_for_non_existent_member_type() {
		$this->assertSame( null, bboss_get_member_type_object( 'foo' ) );
	}

	public function test_bp_get_member_type_object_should_return_type_object() {
		bboss_register_member_type( 'foo' );
		$this->assertInternalType( 'object', bboss_get_member_type_object( 'foo' ) );
	}

	public function test_bp_set_member_type_should_return_false_for_invalid_member_type() {
		$this->assertFalse( bboss_set_member_type( 'foo', 1 ) );
	}

	public function test_bp_set_member_type_should_remove_member_type_when_passing_an_empty_value() {
		$u = self::factory()->user->create();
		bboss_register_member_type( 'foo' );
		bboss_set_member_type( $u, 'foo' );

		// Make sure it's set up.
		$this->assertSame( 'foo', bboss_get_member_type( $u ) );

		$this->assertSame( array(), bboss_set_member_type( $u, '' ) );
		$this->assertFalse( bboss_get_member_type( $u ) );
	}

	public function test_bp_set_member_type_success() {
		$u = self::factory()->user->create();
		bboss_register_member_type( 'foo' );

		$this->assertNotEmpty( bboss_set_member_type( $u, 'foo' ) );
	}

	public function test_bp_get_member_type_with_default_value_for_single() {
		$u = self::factory()->user->create();
		bboss_register_member_type( 'foo' );
		bboss_register_member_type( 'bar' );
		bboss_set_member_type( $u, 'foo' );
		bboss_set_member_type( $u, 'bar', true );

		$this->assertSame( 'foo', bboss_get_member_type( $u ) );
	}

	public function test_bp_get_member_type_with_single_true() {
		$u = self::factory()->user->create();
		bboss_register_member_type( 'foo' );
		bboss_register_member_type( 'bar' );
		bboss_set_member_type( $u, 'foo' );
		bboss_set_member_type( $u, 'bar', true );

		$this->assertSame( 'foo', bboss_get_member_type( $u, true ) );
	}

	public function test_bp_get_member_type_with_single_false() {
		$u = self::factory()->user->create();
		bboss_register_member_type( 'foo' );
		bboss_register_member_type( 'bar' );
		bboss_set_member_type( $u, 'foo' );
		bboss_set_member_type( $u, 'bar', true );

		$this->assertEqualSets( array( 'foo', 'bar' ), bboss_get_member_type( $u, false ) );
	}

	public function test_bp_get_member_type_should_return_false_when_no_value_is_found() {
		$u = self::factory()->user->create();
		bboss_register_member_type( 'foo' );

		$this->assertFalse( bboss_get_member_type( $u ) );
	}

	/**
	 * @group cache
	 */
	public function test_bp_get_member_type_should_hit_cache() {
		$u = self::factory()->user->create();
		bboss_register_member_type( 'foo' );
		bboss_set_member_type( $u, 'foo' );

		global $wpdb;

		// Initial query should hit the database.
		bboss_get_member_type( $u );
		$num_queries = $wpdb->num_queries;

		// Next query should not hit the database.
		bboss_get_member_type( $u );
		$this->assertSame( $num_queries, $wpdb->num_queries );
	}

	/**
	 * @group BP6193
	 */
	public function test_bp_members_prefetch_member_type_array_cache_set() {
		$u1 = self::factory()->user->create();
		$u2 = self::factory()->user->create();
		bboss_register_member_type( 'foo' );
		bboss_register_member_type( 'bar' );
		bboss_set_member_type( $u1, 'foo' );
		bboss_set_member_type( $u1, 'bar', true );

		// Get users so that the 'bp_user_query_populate_extras' is fired
		// and members type prefetched
		$users = bboss_core_get_users( array( 'include' => array( $u1, $u2 ) ) );

		// Get single profile type
		$this->assertSame( 'foo', bboss_get_member_type( $u1, true ) );
		$this->assertEmpty( bboss_get_member_type( $u2, true ) );

		// Get all profile types for the user
		$this->assertEqualSets( array( 'foo', 'bar' ), bboss_get_member_type( $u1, false ) );
		$this->assertEmpty( bboss_get_member_type( $u2, false ) );
	}

	/**
	 * @group cache
	 */
	public function test_bp_get_member_type_should_return_false_for_deleted_user() {
		$u = self::factory()->user->create();
		bboss_register_member_type( 'foo' );
		bboss_set_member_type( $u, 'foo' );

		// Prime the cache.
		bboss_get_member_type( $u );

		if ( is_multisite() ) {
			wpmu_delete_user( $u );
		} else {
			wp_delete_user( $u );
		}

		$this->assertFalse( wp_cache_get( $u, 'bp_member_type' ) );
		$this->assertFalse( bboss_get_member_type( $u ) );
	}

	/**
	 * @group BP6242
	 * @group cache
	 */
	public function test_bp_get_member_type_should_not_conflict_with_term_cache() {
		global $wpdb;

		// Offset IDs.
		$dummy_terms = self::factory()->tag->create_many( 7 );

		$u1 = self::factory()->user->create();
		bboss_register_member_type( 'foo' );
		bboss_set_member_type( $u1, 'foo' );

		// Fetch a term ID.
		$terms = get_terms( bboss_get_member_type_tax_name(), array( 'hide_empty' => false, 'fields' => 'all' ) );

		// Make sure the user's ID matches a term ID, to force a cache confusion.
		$u2 = self::factory()->user->create();
		$new_user_id = $terms[0]->term_id;
		$wpdb->update( $wpdb->users, array( 'ID' => $new_user_id ), array( 'ID' => $u2 ) );

		bboss_set_member_type( $new_user_id, 'foo' );

		// Reprime the taxonomy cache.
		$terms = get_terms( bboss_get_member_type_tax_name(), array( 'hide_empty' => false, 'fields' => 'all' ) );

		$this->assertSame( 'foo', bboss_get_member_type( $new_user_id, true ) );
	}

	/**
	 * @group BP6188
	 */
	public function test_bp_remove_member_type_should_return_false_when_member_type_is_empty() {
		$this->assertFalse( bboss_remove_member_type( 5, '' ) );
	}

	/**
	 * @group BP6188
	 */
	public function test_bp_remove_member_type_should_return_false_when_member_type_is_invalid() {
		$this->assertFalse( bboss_remove_member_type( 5, 'foo' ) );
	}

	/**
	 * @group BP6188
	 */
	public function test_bp_remove_member_type_should_return_false_when_member_is_not_of_provided_type() {
		$u1 = self::factory()->user->create();
		bboss_register_member_type( 'foo' );
		bboss_register_member_type( 'bar' );
		bboss_set_member_type( $u1, 'bar' );

		$this->assertFalse( bboss_remove_member_type( $u1, 'foo' ) );
		$types = bboss_get_member_type( $u1, false );
		$this->assertEquals( array( 'bar' ), $types );
	}

	/**
	 * @group BP6188
	 */
	public function test_bp_remove_member_type_should_return_true_for_successful_deletion() {
		$u1 = self::factory()->user->create();
		bboss_register_member_type( 'foo' );
		bboss_register_member_type( 'bar' );
		bboss_set_member_type( $u1, 'foo' );
		bboss_set_member_type( $u1, 'bar', true );

		$this->assertTrue( bboss_remove_member_type( $u1, 'foo' ) );
		$types = bboss_get_member_type( $u1, false );
		$this->assertEquals( array( 'bar' ), $types );
	}

	/**
	 * @group BP6138
	 */
	function test_bp_has_member_type_should_return_false_when_member_type_is_empty() {
		$this->assertFalse( bboss_has_member_type( 5, '' ) );
	}

	/**
	 * @group BP6138
	 */
	function test_bp_has_member_type_should_return_false_when_member_type_is_invalid() {
		$this->assertFalse( bboss_has_member_type( 5, 'foo' ) );
	}

	/**
	 * @group BP6138
	 */
	public function test_bp_has_member_type_should_return_false_when_member_id_is_empty() {
		bboss_register_member_type( 'foo' );

		$this->assertFalse( bboss_has_member_type( '', 'foo' ) );
	}

	/**
	 * @group BP6138
	 */
	public function test_bp_has_member_type_should_return_false_when_member_is_not_of_provided_type() {
		$u1 = self::factory()->user->create();
		bboss_register_member_type( 'foo' );
		bboss_register_member_type( 'bar' );
		bboss_set_member_type( $u1, 'bar' );

		$this->assertFalse( bboss_has_member_type( $u1, 'foo' ) );
	}

	/**
	 * @group BP6138
	 */
	public function test_bp_has_member_type_should_return_true_on_success() {
		$u1 = self::factory()->user->create();
		bboss_register_member_type( 'foo' );
		bboss_register_member_type( 'bar' );
		bboss_set_member_type( $u1, 'foo' );
		bboss_set_member_type( $u1, 'bar', true );

		$this->assertTrue( bboss_has_member_type( $u1, 'foo' ) );
		$types = bboss_get_member_type( $u1, false );
		$this->assertEqualSets( array( 'bar', 'foo' ), $types );
	}

	public function test_bp_get_member_type_should_not_return_unregistered_types() {
		$u1 = self::factory()->user->create();
		bboss_register_member_type( 'foo' );
		bboss_set_member_type( $u1, 'foo' );

		// Directly set a type that hasn't been registered.
		bboss_set_object_terms( $u1, 'ugh', bboss_get_member_type_tax_name(), true );

		$type = bboss_get_member_type( $u1, false );
		$this->assertEquals( array( 'foo' ), $type );
	}
}
