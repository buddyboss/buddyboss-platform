<?php

/**
 * @group xprofile
 * @group functions
 */
class BP_Tests_XProfile_Functions extends BP_UnitTestCase {
	public function test_get_hidden_field_types_for_user_loggedout() {
		$duser = self::factory()->user->create();

		$old_current_user = bp_loggedin_user_id();
		$this->set_current_user( 0 );

		$this->assertEquals( array( 'friends', 'loggedin', 'adminsonly' ), bp_xprofile_get_hidden_field_types_for_user( $duser, bp_loggedin_user_id() ) );

		$this->set_current_user( $old_current_user );
	}

	public function test_get_hidden_field_types_for_user_loggedin() {
		$duser = self::factory()->user->create();
		$cuser = self::factory()->user->create();

		$old_current_user = bp_loggedin_user_id();
		$this->set_current_user( $cuser );

		$this->assertEquals( array( 'friends', 'adminsonly' ), bp_xprofile_get_hidden_field_types_for_user( $duser, bp_loggedin_user_id() ) );

		$this->set_current_user( $old_current_user );
	}

	public function test_get_hidden_field_types_for_user_friends() {
		$duser = self::factory()->user->create();
		$cuser = self::factory()->user->create();
		friends_add_friend( $duser, $cuser, true );

		$old_current_user = bp_loggedin_user_id();
		$this->set_current_user( $cuser );

		$this->assertEquals( array( 'adminsonly' ), bp_xprofile_get_hidden_field_types_for_user( $duser, bp_loggedin_user_id() ) );

		$this->set_current_user( $old_current_user );
	}

	public function test_get_hidden_field_types_for_user_admin() {
		$duser = self::factory()->user->create();
		$cuser = self::factory()->user->create();
		$this->grant_bp_moderate( $cuser );

		$old_current_user = bp_loggedin_user_id();
		$this->set_current_user( $cuser );

		$this->assertEquals( array(), bp_xprofile_get_hidden_field_types_for_user( $duser, bp_loggedin_user_id() ) );

		$this->revoke_bp_moderate( $cuser );
		$this->set_current_user( $old_current_user );
	}

	/**
	 * @group bp_xprofile_update_meta
	 * @ticket BP5180
	 */
	public function test_bp_xprofile_update_meta_with_line_breaks() {
		$g = self::factory()->xprofile_group->create();
		$f = self::factory()->xprofile_field->create( array(
			'field_group_id' => $g,
		) );

		$meta_value = 'Foo!

Bar!';
		bp_xprofile_update_meta( $f, 'field', 'linebreak_field', $meta_value );
		$this->assertEquals( $meta_value, bp_xprofile_get_meta( $f, 'field', 'linebreak_field' ) );
	}

	/**
	 * @group bp_xprofile_fullname_field_id
	 * @group cache
	 */
	public function test_bp_xprofile_fullname_field_id_invalidation() {
		// Prime the cache
		$id = bp_xprofile_fullname_field_id();

		bp_update_option( 'bp-xprofile-fullname-field-name', 'foo' );

		$this->assertFalse( wp_cache_get( 'fullname_field_id', 'bp_xprofile' ) );
	}

	/**
	 * @group xprofile_get_field_visibility_level
	 */
	public function test_bp_xprofile_get_field_visibility_level_missing_params() {
		$this->assertSame( '', xprofile_get_field_visibility_level( 0, 1 ) );
		$this->assertSame( '', xprofile_get_field_visibility_level( 1, 0 ) );
	}

	/**
	 * @group xprofile_get_field_visibility_level
	 */
	public function test_bp_xprofile_get_field_visibility_level_user_set() {
		$u = self::factory()->user->create();
		$g = self::factory()->xprofile_group->create();
		$f = self::factory()->xprofile_field->create( array(
			'field_group_id' => $g,
		) );

		bp_xprofile_update_meta( $f, 'field', 'default_visibility', 'adminsonly' );
		bp_xprofile_update_meta( $f, 'field', 'allow_custom_visibility', 'allowed' );

		xprofile_set_field_visibility_level( $f, $u, 'loggedin' );

		$this->assertSame( 'loggedin', xprofile_get_field_visibility_level( $f, $u ) );
	}

	/**
	 * @group xprofile_get_field_visibility_level
	 */
	public function test_bp_xprofile_get_field_visibility_level_user_unset() {
		$u = self::factory()->user->create();
		$g = self::factory()->xprofile_group->create();
		$f = self::factory()->xprofile_field->create( array(
			'field_group_id' => $g,
		) );

		bp_xprofile_update_meta( $f, 'field', 'default_visibility', 'adminsonly' );
		bp_xprofile_update_meta( $f, 'field', 'allow_custom_visibility', 'allowed' );

		$this->assertSame( 'adminsonly', xprofile_get_field_visibility_level( $f, $u ) );

	}

	/**
	 * @group xprofile_get_field_visibility_level
	 */
	public function test_bp_xprofile_get_field_visibility_level_admin_override() {
		$u = self::factory()->user->create();
		$g = self::factory()->xprofile_group->create();
		$f = self::factory()->xprofile_field->create( array(
			'field_group_id' => $g,
		) );

		bp_xprofile_update_meta( $f, 'field', 'default_visibility', 'adminsonly' );
		bp_xprofile_update_meta( $f, 'field', 'allow_custom_visibility', 'disabled' );

		xprofile_set_field_visibility_level( $f, $u, 'loggedin' );

		$this->assertSame( 'adminsonly', xprofile_get_field_visibility_level( $f, $u ) );
	}

	/**
	 * @group xprofilemeta
	 * @group bp_xprofile_delete_meta
	 */
	public function test_bp_xprofile_delete_meta_empty_object_id() {
		$this->assertFalse( bp_xprofile_delete_meta( '', 'group' ) );
	}

	/**
	 * @group xprofilemeta
	 * @group bp_xprofile_delete_meta
	 */
	public function test_bp_xprofile_delete_meta_empty_object_type() {
		$this->assertFalse( bp_xprofile_delete_meta( 1, '' ) );
	}

	/**
	 * @group xprofilemeta
	 * @group bp_xprofile_delete_meta
	 */
	public function test_bp_xprofile_delete_meta_illegal_object_type() {
		$this->assertFalse( bp_xprofile_delete_meta( 1, 'foo' ) );
	}

	/**
	 * @group xprofilemeta
	 * @group bp_xprofile_delete_meta
	 * @ticket BP5399
	 */
	public function test_bp_xprofile_delete_meta_illegal_characters() {
		$g = self::factory()->xprofile_group->create();
		bp_xprofile_update_meta( $g, 'group', 'foo', 'bar' );
		$this->assertSame( 'bar', bp_xprofile_get_meta( $g, 'group', 'foo' ) );

		$krazy_key = ' f!@#$%^o *(){}o?+';
		bp_xprofile_delete_meta( $g, 'group', $krazy_key );
		$this->assertSame( 'bar', bp_xprofile_get_meta( $g, 'group', 'foo' ) );
	}

	/**
	 * @group xprofilemeta
	 * @group bp_xprofile_delete_meta
	 * @ticket BP5399
	 */
	public function test_bp_xprofile_delete_meta_trim_meta_value() {
		$g = self::factory()->xprofile_group->create();
		bp_xprofile_update_meta( $g, 'group', 'foo', 'bar' );
		$this->assertSame( 'bar', bp_xprofile_get_meta( $g, 'group', 'foo' ) );

		bp_xprofile_delete_meta( $g, 'group', 'foo', ' bar  ' );
		$this->assertSame( 'bar', bp_xprofile_get_meta( $g, 'group', 'foo' ) );
	}

	/**
	 * @group xprofilemeta
	 * @group bp_xprofile_delete_meta
	 */
	public function test_bp_xprofile_delete_meta_meta_value_match() {
		$g = self::factory()->xprofile_group->create();
		bp_xprofile_update_meta( $g, 'group', 'foo', 'bar' );
		$this->assertSame( 'bar', bp_xprofile_get_meta( $g, 'group', 'foo' ) );
		$this->assertTrue( bp_xprofile_delete_meta( $g, 'group', 'foo', 'bar' ) );
		$this->assertSame( '', bp_xprofile_get_meta( $g, 'group', 'foo' ) );
	}

	/**
	 * @group xprofilemeta
	 * @group bp_xprofile_delete_meta
	 */
	public function test_bp_xprofile_delete_meta_delete_all() {
		$g = self::factory()->xprofile_group->create();
		bp_xprofile_update_meta( $g, 'group', 'foo', 'bar' );
		bp_xprofile_update_meta( $g, 'group', 'foo2', 'bar' );
		$this->assertSame( 'bar', bp_xprofile_get_meta( $g, 'group', 'foo' ) );
		$this->assertSame( 'bar', bp_xprofile_get_meta( $g, 'group', 'foo2' ) );

		$this->assertTrue( bp_xprofile_delete_meta( $g, 'group' ) );

		$this->assertSame( '', bp_xprofile_get_meta( $g, 'group', 'foo' ) );
		$this->assertSame( '', bp_xprofile_get_meta( $g, 'group', 'foo2' ) );
	}

	/**
	 * @group xprofilemeta
	 * @group bp_xprofile_delete_meta
	 */
	public function test_bp_xprofile_delete_meta_with_delete_all_but_no_meta_key() {
		// With no meta key, don't delete for all items - just delete
		// all for a single item
		$g1 = self::factory()->xprofile_group->create();
		$g2 = self::factory()->xprofile_group->create();
		bp_xprofile_add_meta( $g1, 'group', 'foo', 'bar' );
		bp_xprofile_add_meta( $g1, 'group', 'foo1', 'bar1' );
		bp_xprofile_add_meta( $g2, 'group', 'foo', 'bar' );
		bp_xprofile_add_meta( $g2, 'group', 'foo1', 'bar1' );

		$this->assertTrue( bp_xprofile_delete_meta( $g1, 'group', '', '', true ) );
		$this->assertEmpty( bp_xprofile_get_meta( $g1, 'group' ) );
		$this->assertSame( 'bar', bp_xprofile_get_meta( $g2, 'group', 'foo' ) );
		$this->assertSame( 'bar1', bp_xprofile_get_meta( $g2, 'group', 'foo1' ) );
	}

	/**
	 * @group xprofilemeta
	 * @group bp_xprofile_delete_meta
	 */
	public function test_bp_xprofile_delete_meta_with_delete_all() {
		// With no meta key, don't delete for all items - just delete
		// all for a single item
		$g1 = self::factory()->xprofile_group->create();
		$g2 = self::factory()->xprofile_group->create();
		bp_xprofile_add_meta( $g1, 'group', 'foo', 'bar' );
		bp_xprofile_add_meta( $g1, 'group', 'foo1', 'bar1' );
		bp_xprofile_add_meta( $g2, 'group', 'foo', 'bar' );
		bp_xprofile_add_meta( $g2, 'group', 'foo1', 'bar1' );

		$this->assertTrue( bp_xprofile_delete_meta( $g1, 'group', 'foo', '', true ) );
		$this->assertSame( '', bp_xprofile_get_meta( $g1, 'group', 'foo' ) );
		$this->assertSame( '', bp_xprofile_get_meta( $g2, 'group', 'foo' ) );
		$this->assertSame( 'bar1', bp_xprofile_get_meta( $g1, 'group', 'foo1' ) );
		$this->assertSame( 'bar1', bp_xprofile_get_meta( $g2, 'group', 'foo1' ) );
	}

	/**
	 * @group xprofilemeta
	 * @group bp_xprofile_get_meta
	 */
	public function test_bp_xprofile_get_meta_empty_object_id() {
		$this->assertFalse( bp_xprofile_get_meta( 0, 'group' ) );
	}

	/**
	 * @group xprofilemeta
	 * @group bp_xprofile_get_meta
	 */
	public function test_bp_xprofile_get_meta_empty_object_type() {
		$this->assertFalse( bp_xprofile_get_meta( 1, '' ) );
	}

	/**
	 * @group xprofilemeta
	 * @group bp_xprofile_get_meta
	 */
	public function test_bp_xprofile_get_meta_illegal_object_type() {
		$this->assertFalse( bp_xprofile_get_meta( 1, 'foo' ) );
	}

	/**
	 * @group xprofilemeta
	 * @group bp_xprofile_get_meta
	 */
	public function test_bp_xprofile_get_meta_no_meta_key() {
		$g = self::factory()->xprofile_group->create();
		bp_xprofile_update_meta( $g, 'group', 'foo', 'bar' );
		bp_xprofile_update_meta( $g, 'group', 'foo2', 'bar' );

		$meta = bp_xprofile_get_meta( $g, 'group' );

		// Group creation stores its own meta (is_repeater_enabled), so assert on the keys
		// this test sets rather than on the whole meta bag.
		$this->assertSame( array( 'bar' ), $meta['foo'] );
		$this->assertSame( array( 'bar' ), $meta['foo2'] );
	}

	/**
	 * @group xprofilemeta
	 * @group bp_xprofile_get_meta
	 */
	public function test_bp_xprofile_get_meta_single_true() {
		$g = self::factory()->xprofile_group->create();
		bp_xprofile_add_meta( $g, 'group', 'foo', 'bar' );
		bp_xprofile_add_meta( $g, 'group', 'foo', 'baz' );
		$this->assertSame( 'bar', bp_xprofile_get_meta( $g, 'group', 'foo' ) ); // default is true
		$this->assertSame( 'bar', bp_xprofile_get_meta( $g, 'group', 'foo', true ) );
	}

	/**
	 * @group xprofilemeta
	 * @group bp_xprofile_get_meta
	 */
	public function test_bp_xprofile_get_meta_single_false() {
		$g = self::factory()->xprofile_group->create();
		bp_xprofile_add_meta( $g, 'group', 'foo', 'bar' );
		bp_xprofile_add_meta( $g, 'group', 'foo', 'baz' );
		$this->assertSame( array( 'bar', 'baz' ), bp_xprofile_get_meta( $g, 'group', 'foo', false ) );
	}

	/**
	 * @group xprofilemeta
	 * @group bp_xprofile_get_meta
	 */
	public function test_bp_xprofile_get_meta_no_meta_key_no_results() {
		$g = self::factory()->xprofile_group->create();

		// A freshly created group is not meta-free (group creation stores
		// is_repeater_enabled), so assert that no user meta key was invented for it.
		$meta = bp_xprofile_get_meta( $g, 'group' );
		$this->assertArrayNotHasKey( 'foo', $meta );
		$this->assertArrayNotHasKey( 'foo2', $meta );
	}

	/**
	 * @group xprofilemeta
	 * @group bp_xprofile_update_meta
	 */
	public function test_bp_xprofile_update_meta_no_object_id() {
		$this->assertFalse( bp_xprofile_update_meta( 0, 'group', 'foo', 'bar' ) );
	}

	/**
	 * @group xprofilemeta
	 * @group bp_xprofile_update_meta
	 */
	public function test_bp_xprofile_update_meta_no_object_type() {
		$this->assertFalse( bp_xprofile_update_meta( 1, '', 'foo', 'bar' ) );
	}

	/**
	 * @group xprofilemeta
	 * @group bp_xprofile_update_meta
	 */
	public function test_bp_xprofile_update_meta_illegal_object_type() {
		$this->assertFalse( bp_xprofile_update_meta( 1, 'foo', 'foo', 'bar' ) );
	}

	/**
	 * @group xprofilemeta
	 * @group bp_xprofile_update_meta
	 * @ticket BP5399
	 */
	public function test_bp_xprofile_update_meta_illegal_characters() {
		$g = self::factory()->xprofile_group->create();
		$krazy_key = ' f!@#$%^o *(){}o?+';
		bp_xprofile_update_meta( $g, 'group', $krazy_key, 'bar' );
		$this->assertSame( '', bp_xprofile_get_meta( $g, 'group', 'foo' ) );
	}

	/**
	 * @group xprofilemeta
	 * @group bp_xprofile_update_meta
	 */
	public function test_bp_xprofile_update_meta_stripslashes() {
		$g = self::factory()->xprofile_group->create();
		$v = "Totally \'tubular\'";
		bp_xprofile_update_meta( $g, 'group', 'foo', $v );
		$this->assertSame( stripslashes( $v ), bp_xprofile_get_meta( $g, 'group', 'foo' ) );
	}

	/**
	 * @group xprofilemeta
	 * @group bp_xprofile_update_meta
	 */
	public function test_bp_xprofile_update_meta_empty_value_delete() {
		$g = self::factory()->xprofile_group->create();
		bp_xprofile_update_meta( $g, 'group', 'foo', 'bar' );
		bp_xprofile_update_meta( $g, 'group', 'foo', '' );
		$this->assertSame( '', bp_xprofile_get_meta( $g, 'group', 'foo' ) );
	}

	/**
	 * @group xprofilemeta
	 * @group bp_xprofile_update_meta
	 */
	public function test_bp_xprofile_update_meta_new() {
		$g = self::factory()->xprofile_group->create();
		$this->assertSame( '', bp_xprofile_get_meta( $g, 'group', 'foo' ) );
		$this->assertNotEmpty( bp_xprofile_update_meta( $g, 'group', 'foo', 'bar' ) );
		$this->assertSame( 'bar', bp_xprofile_get_meta( $g, 'group', 'foo' ) );
	}

	/**
	 * @group xprofilemeta
	 * @group bp_xprofile_update_meta
	 */
	public function test_bp_xprofile_update_meta_existing() {
		$g = self::factory()->xprofile_group->create();
		bp_xprofile_update_meta( $g, 'group', 'foo', 'bar' );
		$this->assertSame( 'bar', bp_xprofile_get_meta( $g, 'group', 'foo' ) );
		$this->assertTrue( bp_xprofile_update_meta( $g, 'group', 'foo', 'baz' ) );
		$this->assertSame( 'baz', bp_xprofile_get_meta( $g, 'group', 'foo' ) );
	}

	/**
	 * @group xprofilemeta
	 * @group bp_xprofile_update_meta
	 */
	public function test_bp_xprofile_update_meta_same_value() {
		$g = self::factory()->xprofile_group->create();
		bp_xprofile_update_meta( $g, 'group', 'foo', 'bar' );
		$this->assertSame( 'bar', bp_xprofile_get_meta( $g, 'group', 'foo' ) );
		$this->assertFalse( bp_xprofile_update_meta( $g, 'group', 'foo', 'bar' ) );
	}

	/**
	 * @group xprofilemeta
	 * @group bp_xprofile_update_meta
	 */
	public function test_bp_xprofile_update_meta_prev_value() {
		$g = self::factory()->xprofile_group->create();
		bp_xprofile_add_meta( $g, 'group', 'foo', 'bar' );

		// In earlier versions of WordPress, bp_activity_update_meta()
		// returns true even on failure. However, we know that in these
		// cases the update is failing as expected, so we skip this
		// assertion just to keep our tests passing
		// See https://core.trac.wordpress.org/ticket/24933
		if ( version_compare( $GLOBALS['wp_version'], '3.7', '>=' ) ) {
			$this->assertFalse( bp_xprofile_update_meta( $g, 'group', 'foo', 'bar2', 'baz' ) );
		}

		$this->assertTrue( bp_xprofile_update_meta( $g, 'group', 'foo', 'bar2', 'bar' ) );
	}

	/**
	 * @group xprofilemeta
	 * @group bp_xprofile_update_meta
	 * @ticket BP5919
	 */
	public function test_bp_xprofile_update_meta_where_sql_filter_keywords_are_in_quoted_value() {
		$g = self::factory()->xprofile_group->create();
		$value = "SELECT object_id FROM wp_bp_xprofile_groups WHERE \"foo\" VALUES (foo = 'bar'";
		bp_xprofile_add_meta( $g, 'group', 'foo', 'bar' );
		bp_xprofile_update_meta( $g, 'group', 'foo', $value );
		$this->assertSame( $value, bp_xprofile_get_meta( $g, 'group', 'foo' ) );
	}

	/**
	 * @group xprofilemeta
	 * @group bp_xprofile_update_meta
	 * @ticket BP5919
	 */
	public function test_bp_xprofile_update_meta_where_meta_id_is_in_quoted_value() {
		$g = self::factory()->xprofile_group->create();
		$value = "foo meta_id bar";
		bp_xprofile_add_meta( $g, 'group', 'foo', 'bar' );
		bp_xprofile_update_meta( $g, 'group', 'foo', $value );
		$this->assertSame( $value, bp_xprofile_get_meta( $g, 'group', 'foo' ) );
	}

	/**
	 * @group xprofilemeta
	 * @group bp_xprofile_add_meta
	 */
	public function test_bp_xprofile_add_meta_no_meta_key() {
		$this->assertFalse( bp_xprofile_add_meta( 1, 'group', '', 'bar' ) );
	}

	/**
	 * @group xprofilemeta
	 * @group bp_xprofile_add_meta
	 */
	public function test_bp_xprofile_add_meta_empty_object_id() {
		$this->assertFalse( bp_xprofile_add_meta( 0, 'group', 'foo', 'bar' ) );
	}

	/**
	 * @group xprofilemeta
	 * @group bp_xprofile_add_meta
	 */
	public function test_bp_xprofile_add_meta_existing_unique() {
		$g = self::factory()->xprofile_group->create();
		bp_xprofile_add_meta( $g, 'group', 'foo', 'bar' );
		$this->assertFalse( bp_xprofile_add_meta( $g, 'group', 'foo', 'baz', true ) );
	}

	/**
	 * @group xprofilemeta
	 * @group bp_xprofile_add_meta
	 */
	public function test_bp_xprofile_add_meta_existing_not_unique() {
		$g = self::factory()->xprofile_group->create();
		bp_xprofile_add_meta( $g, 'group', 'foo', 'bar' );
		$this->assertNotEmpty( bp_xprofile_add_meta( $g, 'group', 'foo', 'baz' ) );
	}

	/**
	 * @group bp_get_member_profile_data
	 */
	public function test_bp_get_member_profile_data_inside_loop() {
		$u = self::factory()->user->create();
		$g = self::factory()->xprofile_group->create();
		$f = self::factory()->xprofile_field->create( array(
			'field_group_id' => $g,
			'name' => 'Neato',
		) );
		xprofile_set_field_data( $f, $u, 'foo' );

		if ( bp_has_members() ) : while ( bp_members() ) : bp_the_member();
		$found = bp_get_member_profile_data( array(
			'user_id' => $u,
			'field' => 'Neato',
		) );
		endwhile; endif;

		// Cleanup
		unset( $GLOBALS['members_template'] );

		$this->assertSame( 'foo', $found );
	}
	/**
	 * @group bp_get_member_profile_data
	 */
	public function test_bp_get_member_profile_data_outside_of_loop() {
		$u = self::factory()->user->create();
		$g = self::factory()->xprofile_group->create();
		$f = self::factory()->xprofile_field->create( array(
			'field_group_id' => $g,
			'name' => 'Kewl',
		) );
		xprofile_set_field_data( $f, $u, 'foo' );

		$found = bp_get_member_profile_data( array(
			'user_id' => $u,
			'field' => 'Kewl',
		) );

		$this->assertSame( 'foo', $found );
	}

	/**
	 * @group xprofile_set_field_data
	 */
	public function test_get_field_data_integer_zero() {
		$u = self::factory()->user->create();
		$g = self::factory()->xprofile_group->create();
		$f = self::factory()->xprofile_field->create( array(
			'field_group_id' => $g,
			'type' => 'number',
			'name' => 'Pens',
		) );
		xprofile_set_field_data( $f, $u, 0 );

		$this->assertEquals( 0, xprofile_get_field_data( 'Pens', $u ) );
	}

	/**
	 * @group xprofile_set_field_data
	 * @ticket BP5836
	 */
	public function test_xprofile_sync_bp_profile_new_user() {
		$post_vars = $_POST;

		// add_user()/edit_user() are wp-admin operations performed by a logged-in
		// administrator. Without a current user, bp_xprofile_validate_nickname_value()
		// takes its registration-page branch (! is_user_logged_in()), which has no notion
		// of "the user being edited" and rejects the member's own unchanged nickname.
		$old_user = get_current_user_id();
		$this->set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );

		$_POST = array(
			'user_login' => 'foobar',
			'pass1'      => 'password',
			'pass2'      => 'password',
			'role'       => 'subscriber',
			'email'      => 'foo@bar.com',
			'first_name' => 'Foo',
			'last_name'  => 'Bar',
		);

		$id = add_user();
		$this->assertNotWPError( $id, 'The fixture user could not be created.' );

		$_POST = array(
			'display_name' => 'Bar Foo',
			'email' => 'foo@bar.com',
			'nickname' => 'foobar',
		);

		$id = edit_user( $id );
		$this->assertNotWPError( $id, 'Re-saving a user with an unchanged nickname must not be rejected.' );

		// clean up post vars
		$_POST = $post_vars;
		$this->set_current_user( $old_user );

		$this->assertEquals( 'foobar', xprofile_get_field_data( bp_xprofile_nickname_field_id(), $id ) );
	}

	/**
	 * @group xprofile_insert_field
	 */
	public function test_xprofile_insert_field_type_option() {
		$g = self::factory()->xprofile_group->create();
		$parent = self::factory()->xprofile_field->create( array(
			'field_group_id' => $g,
			'type' => 'selectbox',
			'name' => 'Parent',
		) );

		$f = xprofile_insert_field( array(
			'field_group_id' => $g,
			'parent_id' => $parent,
			'type' => 'option',
			'name' => 'Option 1',
			'field_order' => 5,
		) );

		$this->assertNotEmpty( $f );
	}

	/**
	 * @group xprofile_insert_field
	 * @ticket BP6354
	 */
	public function test_xprofile_insert_field_should_process_falsey_values_for_boolean_params_on_existing_fields() {
		$g = self::factory()->xprofile_group->create();
		$f = xprofile_insert_field( array(
			'field_group_id' => $g,
			'type' => 'textbox',
			'name' => 'Foo',
			'is_required' => true,
			'can_delete' => true,
			'is_default_option' => true,
			'parent_id' => 13,
			'field_order' => 5,
			'option_order' => 8,
			'description' => 'foo',
			'order_by' => 'custom',
		) );

		$this->assertNotEmpty( $f );

		$field = new BP_XProfile_Field( $f );
		$this->assertEquals( 1, $field->is_required );
		$this->assertEquals( 1, $field->can_delete );
		$this->assertEquals( 1, $field->is_default_option );
		$this->assertEquals( 13, $field->parent_id );
		$this->assertEquals( 5, $field->field_order );
		$this->assertEquals( 8, $field->option_order );
		$this->assertEquals( 'foo', $field->description );
		$this->assertEquals( 'custom', $field->order_by );

		$f = xprofile_insert_field( array(
			'field_group_id' => $g,
			'type' => 'textbox',
			'name' => 'Foo',
			'is_required' => false,
			'can_delete' => false,
			'is_default_option' => false,
			'parent_id' => 0,
			'field_order' => 0,
			'option_order' => 0,
			'description' => '',
			'order_by' => '',
		) );

		$this->assertNotEmpty( $f );

		$field = new BP_XProfile_Field( $f );
		$this->assertEquals( 0, $field->is_required );
		$this->assertEquals( 0, $field->can_delete );
		$this->assertEquals( 0, $field->is_default_option );
		$this->assertEquals( 0, $field->parent_id );
		$this->assertEquals( 0, $field->field_order );
		$this->assertEquals( 0, $field->option_order );
		$this->assertEquals( '', $field->description );
		$this->assertEquals( '', $field->order_by );
	}

	/**
	 * @group xprofile_insert_field
	 */
	public function test_xprofile_insert_field_type_option_option_order() {
		$g = self::factory()->xprofile_group->create();
		$parent = self::factory()->xprofile_field->create( array(
			'field_group_id' => $g,
			'type' => 'selectbox',
			'name' => 'Parent',
		) );

		$f = xprofile_insert_field( array(
			'field_group_id' => $g,
			'parent_id' => $parent,
			'type' => 'option',
			'name' => 'Option 1',
			'option_order' => 5,
		) );

		$field = new BP_XProfile_Field( $f );

		$this->assertEquals( 5, $field->option_order );
	}

	/**
	 * @group xprofile_insert_field
	 * @ticket BP6137
	 */
	public function test_xprofile_insert_field_should_set_is_default_option_to_false_for_new_option() {
		$g = self::factory()->xprofile_group->create();
		$parent = self::factory()->xprofile_field->create( array(
			'field_group_id' => $g,
			'type' => 'selectbox',
			'name' => 'Parent',
		) );

		$f = xprofile_insert_field( array(
			'field_group_id' => $g,
			'parent_id' => $parent,
			'type' => 'option',
			'name' => 'Option 1',
			'field_order' => 5,
			'is_default_option' => false,
		) );

		$this->assertNotEmpty( $f );
		$field = new BP_XProfile_Field( $f );
		$this->assertEquals( 0, $field->is_default_option );
	}

	/**
	 * @group xprofile_insert_field
	 * @ticket BP6137
	 */
	public function test_xprofile_insert_field_should_set_is_default_option_to_true_for_new_option() {
		$g = self::factory()->xprofile_group->create();
		$parent = self::factory()->xprofile_field->create( array(
			'field_group_id' => $g,
			'type' => 'selectbox',
			'name' => 'Parent',
		) );

		$f = xprofile_insert_field( array(
			'field_group_id' => $g,
			'parent_id' => $parent,
			'type' => 'option',
			'name' => 'Option 1',
			'field_order' => 5,
			'is_default_option' => true,
		) );

		$this->assertNotEmpty( $f );
		$field = new BP_XProfile_Field( $f );
		$this->assertEquals( 1, $field->is_default_option );
	}

	/**
	 * @group xprofile_insert_field
	 * @ticket BP6137
	 */
	public function test_xprofile_insert_field_should_set_is_default_option_to_false_for_existing_option() {
		$g = self::factory()->xprofile_group->create();
		$parent = self::factory()->xprofile_field->create( array(
			'field_group_id' => $g,
			'type' => 'selectbox',
			'name' => 'Parent',
		) );

		$f = xprofile_insert_field( array(
			'field_group_id' => $g,
			'parent_id' => $parent,
			'type' => 'option',
			'name' => 'Option 1',
			'field_order' => 5,
			'is_default_option' => true,
		) );

		$this->assertNotEmpty( $f );
		$field = new BP_XProfile_Field( $f );
		$this->assertEquals( 1, $field->is_default_option );

		$f = xprofile_insert_field( array(
			'field_id' => $f,
			'field_group_id' => $g,
			'type' => 'textbox',
			'is_default_option' => false,
		) );

		$field2 = new BP_XProfile_Field( $f );
		$this->assertEquals( 0, $field2->is_default_option );
	}

	/**
	 * @group xprofile_insert_field
	 * @ticket BP6137
	 */
	public function test_xprofile_insert_field_should_set_is_default_option_to_true_for_existing_option() {
		$g = self::factory()->xprofile_group->create();
		$parent = self::factory()->xprofile_field->create( array(
			'field_group_id' => $g,
			'type' => 'selectbox',
			'name' => 'Parent',
		) );

		$f = xprofile_insert_field( array(
			'field_group_id' => $g,
			'parent_id' => $parent,
			'type' => 'option',
			'name' => 'Option 1',
			'field_order' => 5,
			'is_default_option' => false,
		) );

		$this->assertNotEmpty( $f );
		$field = new BP_XProfile_Field( $f );
		$this->assertEquals( 0, $field->is_default_option );

		$f = xprofile_insert_field( array(
			'field_id' => $f,
			'field_group_id' => $g,
			'type' => 'textbox',
			'is_default_option' => true,
		) );

		$field2 = new BP_XProfile_Field( $f );

		$this->assertEquals( 1, $field2->is_default_option );
	}

	/**
	 * @group xprofile_update_field_group_position
	 * @group bp_profile_get_field_groups
	 */
	public function test_bp_profile_get_field_groups_update_position() {
		$g1 = self::factory()->xprofile_group->create();
		$g2 = self::factory()->xprofile_group->create();
		$g3 = self::factory()->xprofile_group->create();

		// prime the cache
		bp_profile_get_field_groups();

		// switch the field group positions for the last two groups
		xprofile_update_field_group_position( $g2, 3 );
		xprofile_update_field_group_position( $g3, 2 );

		// now refetch field groups
		$field_groups = bp_profile_get_field_groups();

		// assert!
		$this->assertEquals( array( 1, $g1, $g3, $g2 ), wp_list_pluck( $field_groups, 'id' ) );
	}

	/**
	 * @ticket BP6638
	 */
	public function test_xprofile_get_field_should_return_bp_xprofile_field_object() {
		global $wpdb;

		$g = self::factory()->xprofile_group->create();
		$f = self::factory()->xprofile_field->create( array(
			'field_group_id' => $g,
			'type' => 'selectbox',
			'name' => 'Foo',
		) );

		$field = xprofile_get_field( $f );

		$this->assertTrue( $field instanceof BP_XProfile_Field );
	}

	/**
	 * @ticket BP6638
	 * @group cache
	 */
	public function test_xprofile_get_field_should_prime_field_cache() {
		global $wpdb;

		$g = self::factory()->xprofile_group->create();
		$f = self::factory()->xprofile_field->create( array(
			'field_group_id' => $g,
			'type' => 'selectbox',
			'name' => 'Foo',
		) );

		$num_queries = $wpdb->num_queries;

		// Prime the cache.
		$field_1 = xprofile_get_field( $f );
		$num_queries++;
		$this->assertSame( $num_queries, $wpdb->num_queries );

		// No more queries.
		$field_2 = xprofile_get_field( $f );
		$this->assertEquals( $field_1, $field_2 );
		$this->assertSame( $num_queries, $wpdb->num_queries );
	}

	/**
	 * @ticket BP5625
	 */
	public function test_bp_xprofie_is_richtext_enabled_for_field_should_default_to_true_for_textareas() {
		$g = self::factory()->xprofile_group->create();
		$f = self::factory()->xprofile_field->create( array(
			'field_group_id' => $g,
			'type' => 'textarea',
		) );

		$this->assertTrue( bp_xprofile_is_richtext_enabled_for_field( $f ) );
	}

	/**
	 * @ticket BP5625
	 */
	public function test_bp_xprofie_is_richtext_enabled_for_field_should_default_to_false_for_non_textareas() {
		$g = self::factory()->xprofile_group->create();
		$f = self::factory()->xprofile_field->create( array(
			'field_group_id' => $g,
			'type' => 'radio',
		) );

		$this->assertFalse( bp_xprofile_is_richtext_enabled_for_field( $f ) );
	}

	/**
	 * @group bp_get_field_css_class
	 */
	public function test_bp_get_field_css_class_empty_param() {
		// Fake the global
		global $profile_template;
		$reset_profile_template = $profile_template;

		$profile_template = new stdClass;
		// Avoid the 'alt' class being added
		$profile_template->current_field = 2;
		// bp_get_field_css_class() reads field_order off the looped field, so the stub must
		// carry it as a real BP_XProfile_Field would; without it the tag emits an undefined
		// property notice and the expected class list is incomplete.
		$profile_template->field = new stdClass;
		$profile_template->field->id = 145;
		$profile_template->field->name = 'Pie';
		$profile_template->field->type = 'textbox';
		$profile_template->field->field_order = 3;

		$expected_classes = array(
			'optional-field',
			'field_' . $profile_template->field->id,
			'field_' . sanitize_title( $profile_template->field->name ),
			'field_type_' . sanitize_title( $profile_template->field->type ),
			'field_order_' . $profile_template->field->field_order,
			'visibility-public'
			);

		$classes = bp_get_field_css_class();
		preg_match( '/class=["\']?([^"\']*)["\' ]/is', $classes, $matches );
		$ret_classes = explode( ' ', $matches[1] );
		$this->assertEqualSets( $expected_classes, $ret_classes );

		// Clean up!
		$profile_template = $reset_profile_template;
	}

	/**
	 * @group bp_get_field_css_class
	 */
	public function test_bp_get_field_css_class_space_delimited_string() {
		// Fake the global
		global $profile_template;
		$reset_profile_template = $profile_template;

		$profile_template = new stdClass;
		// Avoid the 'alt' class being added
		$profile_template->current_field = 2;
		$profile_template->field = new stdClass;
		$profile_template->field->id = 145;
		$profile_template->field->name = 'Pie';
		$profile_template->field->type = 'textbox';
		$profile_template->field->field_order = 3;

		$expected_classes = array(
			'optional-field',
			'field_' . $profile_template->field->id,
			'field_' . sanitize_title( $profile_template->field->name ),
			'field_type_' . sanitize_title( $profile_template->field->type ),
			'field_order_' . $profile_template->field->field_order,
			'visibility-public',
			'rhubarb',
			'apple'
			);

		$classes = bp_get_field_css_class( 'rhubarb apple' );
		preg_match( '/class=["\']?([^"\']*)["\' ]/is', $classes, $matches );
		$ret_classes = explode( ' ', $matches[1] );
		$this->assertEqualSets( $expected_classes, $ret_classes );

		// Clean up!
		$profile_template = $reset_profile_template;
	}

	/**
	 * @group bp_get_field_css_class
	 */
	public function test_bp_get_field_css_class_array() {
		// Fake the global
		global $profile_template;
		$reset_profile_template = $profile_template;

		$profile_template = new stdClass;
		// Avoid the 'alt' class being added
		$profile_template->current_field = 2;
		$profile_template->field = new stdClass;
		$profile_template->field->id = 145;
		$profile_template->field->name = 'Pie';
		$profile_template->field->type = 'textbox';
		$profile_template->field->field_order = 3;

		$expected_classes = array(
			'optional-field',
			'field_' . $profile_template->field->id,
			'field_' . sanitize_title( $profile_template->field->name ),
			'field_type_' . sanitize_title( $profile_template->field->type ),
			'field_order_' . $profile_template->field->field_order,
			'visibility-public',
			'blueberry',
			'gooseberry'
			);

		$classes = bp_get_field_css_class( array( 'blueberry', 'gooseberry' ) );
		preg_match( '/class=["\']?([^"\']*)["\' ]/is', $classes, $matches );
		$ret_classes = explode( ' ', $matches[1] );
		$this->assertEqualSets( $expected_classes, $ret_classes );

		// Clean up!
		$profile_template = $reset_profile_template;
	}

	/**
	 * @group xprofile_filter_link_profile_data
	 * @version BuddyBoss 3.1.1 Autolink features is removed
	 */
	// public function test_field_comma_seperated_values_are_autolinked() {
	// 	$field_group_id = self::factory()->xprofile_group->create();
	// 	$field_id = self::factory()->xprofile_field->create( array( 'field_group_id' => $field_group_id ) );
	// 	$GLOBALS['field'] = new BP_XProfile_Field( $field_id );
	// 	$GLOBALS['field']->do_autolink = true;

	// 	$output = xprofile_filter_link_profile_data( 'Hello world this is a test; with, some, words', 'textbox' );
	// 	$regex = '#^Hello world this is a test; with, <a href="([^"]+)" rel="nofollow">some</a>, <a href="([^"]+)" rel="nofollow">words</a>$#i';

	// 	$this->assertRegExp( $regex, $output );
	// 	unset( $GLOBALS['field'] );
	// }

	/**
	 * @group xprofile_filter_link_profile_data
	 * @version BuddyBoss 3.1.1 Autolink features is removed
	 */
	// public function test_field_semicolon_seperated_values_are_autolinked() {
	// 	$field_group_id = self::factory()->xprofile_group->create();
	// 	$field_id = self::factory()->xprofile_field->create( array( 'field_group_id' => $field_group_id ) );
	// 	$GLOBALS['field'] = new BP_XProfile_Field( $field_id );
	// 	$GLOBALS['field']->do_autolink = true;

	// 	$output = xprofile_filter_link_profile_data( 'Hello world this is a test with; some; words', 'textbox' );
	// 	$regex = '#^Hello world this is a test with; <a href="([^"]+)" rel="nofollow">some</a>; <a href="([^"]+)" rel="nofollow">words</a>$#i';

	// 	$this->assertRegExp( $regex, $output );
	// 	unset( $GLOBALS['field'] );
	// }
	/**
	 * @group bb_xprofile_can_change_field_visibility
	 */
	public function test_bb_xprofile_can_change_field_visibility_enforced_field() {
		$u = self::factory()->user->create();
		$f = $this->create_visibility_field( 'disabled', 'public' );
		$this->set_current_user( $u );
		buddypress()->displayed_user->id = $u;

		$this->assertFalse( bb_xprofile_can_change_field_visibility( $f ) );
	}

	/**
	 * @group bb_xprofile_can_change_field_visibility
	 */
	public function test_bb_xprofile_can_change_field_visibility_allowed_field() {
		$u = self::factory()->user->create();
		$f = $this->create_visibility_field( 'allowed', 'public' );
		$this->set_current_user( $u );
		buddypress()->displayed_user->id = $u;

		$this->assertTrue( bb_xprofile_can_change_field_visibility( $f ) );
	}

	/**
	 * @group bb_xprofile_can_change_field_visibility
	 */
	public function test_bb_xprofile_can_change_field_visibility_nickname_locked_by_display_name_format() {
		$u = self::factory()->user->create();
		$f = $this->create_visibility_field( 'allowed', 'public' );
		bp_update_option( 'bp-xprofile-nickname-field-id', $f );
		$this->set_current_user( $u );
		buddypress()->displayed_user->id = $u;

		$this->assertFalse( bb_xprofile_can_change_field_visibility( $f ) );

		bp_delete_option( 'bp-xprofile-nickname-field-id' );
	}

	/**
	 * @group bb_xprofile_can_change_field_visibility
	 */
	public function test_bb_xprofile_can_change_field_visibility_does_not_leak_globals() {
		$u = self::factory()->user->create();
		$f = $this->create_visibility_field( 'allowed', 'public' );
		$this->set_current_user( $u );
		buddypress()->displayed_user->id = $u;
		unset( $GLOBALS['profile_template'], $GLOBALS['field'] );

		bb_xprofile_can_change_field_visibility( $f );

		$this->assertFalse( isset( $GLOBALS['profile_template'] ) );
		$this->assertFalse( isset( $GLOBALS['field'] ) );
	}

	/**
	 * The restore path must hand the caller back the exact globals it had, including a
	 * NULL-valued $GLOBALS['field'] — which is why the helper uses array_key_exists()
	 * rather than isset(). The sibling test covers the "globals were absent" branch.
	 *
	 * @group bb_xprofile_can_change_field_visibility
	 */
	public function test_bb_xprofile_can_change_field_visibility_restores_pre_existing_globals() {
		$u = self::factory()->user->create();
		$f = $this->create_visibility_field( 'allowed', 'public' );
		$this->set_current_user( $u );
		buddypress()->displayed_user->id = $u;

		$template          = new stdClass();
		$template->sentinel = 'caller';
		$field             = new stdClass();
		$field->sentinel   = 'caller';

		$GLOBALS['profile_template'] = $template;
		$GLOBALS['field']            = $field;

		bb_xprofile_can_change_field_visibility( $f );

		// Same object handles, not merely equal copies.
		$this->assertSame( $template, $GLOBALS['profile_template'] );
		$this->assertSame( $field, $GLOBALS['field'] );

		// A NULL-valued global is a set global, so it must come back as NULL rather than
		// being unset — the array_key_exists()/isset() distinction the helper relies on.
		$GLOBALS['field'] = null;

		bb_xprofile_can_change_field_visibility( $f );

		$this->assertTrue( array_key_exists( 'field', $GLOBALS ) );
		$this->assertNull( $GLOBALS['field'] );

		unset( $GLOBALS['profile_template'], $GLOBALS['field'] );
	}

	/**
	 * bp_xprofile_action_settings() skips locked fields entirely. Before the gate every
	 * posted field id fell through to the 'public' fallback, so a locked field's stored
	 * level was silently overwritten on any Profile Visibility save.
	 *
	 * @group bp_xprofile_action_settings
	 */
	public function test_bp_xprofile_action_settings_skips_locked_field_and_saves_the_rest() {
		$u      = self::factory()->user->create();
		$locked = $this->create_visibility_field( 'disabled', 'adminsonly' );
		$open   = $this->create_visibility_field( 'allowed', 'public' );

		$this->set_current_user( $u );

		$bp                     = buddypress();
		$displayed_user_backup  = $bp->displayed_user->id;
		$component_backup       = $bp->current_component;
		$action_backup          = $bp->current_action;
		$action_vars_backup     = $bp->action_variables;
		$request_method_backup  = isset( $_SERVER['REQUEST_METHOD'] ) ? $_SERVER['REQUEST_METHOD'] : null;

		$bp->displayed_user->id = $u;
		$bp->current_component  = 'settings';
		$bp->current_action     = 'profile';
		$bp->action_variables   = array();

		try {
			$_SERVER['REQUEST_METHOD']         = 'POST';
			$_POST['xprofile-settings-submit'] = '1';
			$_REQUEST['_wpnonce']              = wp_create_nonce( 'bp_xprofile_settings' );
			$_POST['field_ids']                = $locked . ',' . $open;
			// The locked field renders no control, so the browser posts no value for it -
			// exactly the case that used to fall through to the 'public' default.
			$_POST[ 'field_' . $open . '_visibility' ] = 'adminsonly';

			bp_xprofile_action_settings();
		} finally {
			// Restore in a finally block: a failed nonce check raises WPDieException in the
			// test suite, and leaking $_SERVER/$_POST or the BP globals would corrupt every
			// test that runs after this one.
			unset(
				$_POST['xprofile-settings-submit'],
				$_POST['field_ids'],
				$_POST[ 'field_' . $open . '_visibility' ],
				$_REQUEST['_wpnonce']
			);
			if ( null === $request_method_backup ) {
				unset( $_SERVER['REQUEST_METHOD'] );
			} else {
				$_SERVER['REQUEST_METHOD'] = $request_method_backup;
			}
			$bp->displayed_user->id = $displayed_user_backup;
			$bp->current_component  = $component_backup;
			$bp->current_action     = $action_backup;
			$bp->action_variables   = $action_vars_backup;
		}

		// The locked field was skipped: nothing member-chosen reached storage.
		$levels = bp_get_user_meta( $u, 'bp_xprofile_visibility_levels', true );
		$this->assertTrue( empty( $levels[ $locked ] ), 'A locked field must not be written by the settings screen.' );

		// Positive control: the unlocked field in the same POST still saved, proving the
		// `continue` skips only the locked field rather than aborting the loop.
		$this->assertSame( 'adminsonly', xprofile_get_field_visibility_level( $open, $u ) );
	}

	/**
	 * @group bb_xprofile_save_fields
	 */
	public function test_bb_xprofile_save_fields_skips_visibility_write_for_enforced_field() {
		$u = self::factory()->user->create();
		$f = $this->create_visibility_field( 'disabled', 'loggedin' );
		$this->set_current_user( $u );
		buddypress()->displayed_user->id = $u;

		$_POST[ 'field_' . $f ]                 = 'hello';
		$_POST[ 'field_' . $f . '_visibility' ] = 'friends';
		bb_xprofile_save_fields( array( $f ), array( $f => false ) );
		unset( $_POST[ 'field_' . $f ], $_POST[ 'field_' . $f . '_visibility' ] );

		$this->assertSame( 'hello', xprofile_get_field_data( $f, $u ) );
		$levels = bp_get_user_meta( $u, 'bp_xprofile_visibility_levels', true );
		$this->assertTrue( empty( $levels[ $f ] ) );
	}

	/**
	 * @group bb_xprofile_save_fields
	 */
	public function test_bb_xprofile_save_fields_writes_visibility_for_allowed_field() {
		$u = self::factory()->user->create();
		$f = $this->create_visibility_field( 'allowed', 'public' );
		$this->set_current_user( $u );
		buddypress()->displayed_user->id = $u;

		$_POST[ 'field_' . $f ]                 = 'hello';
		$_POST[ 'field_' . $f . '_visibility' ] = 'friends';
		bb_xprofile_save_fields( array( $f ), array( $f => false ) );
		unset( $_POST[ 'field_' . $f ], $_POST[ 'field_' . $f . '_visibility' ] );

		$this->assertSame( 'friends', xprofile_get_field_visibility_level( $f, $u ) );
	}

	/**
	 * @group bb_xprofile_can_change_field_visibility
	 */
	public function test_signup_activation_ignores_submitted_visibility_for_enforced_field() {
		$f = $this->create_visibility_field( 'disabled', 'adminsonly' );

		$signup_id = BP_Signup::add(
			array(
				'user_login'     => 'lockedsignupuser',
				'user_email'     => 'lockedsignup@example.test',
				'activation_key' => 'lockedsignupkey',
				'meta'           => array(
					'password'              => 'password',
					'profile_field_ids'     => (string) $f,
					"field_{$f}"            => 'Locked value',
					"field_{$f}_visibility" => 'public',
				),
			)
		);
		$this->assertNotEmpty( $signup_id );

		$user_id = bp_core_activate_signup( 'lockedsignupkey' );
		$this->assertNotWPError( $user_id );

		// Assert the RAW stored meta, not the accessor: for enforced fields the
		// accessor always returns the admin default regardless of storage, so it
		// cannot distinguish a blocked write from a stored crafted level. The
		// guard stores the default, so the crafted 'public' must not be present.
		$levels = bp_get_user_meta( $user_id, 'bp_xprofile_visibility_levels', true );
		$this->assertIsArray( $levels, 'Activation must store the visibility levels it resolved.' );
		$this->assertArrayHasKey( $f, $levels, 'Activation writes the resolved level for every field; a missing key would hide a skipped write.' );
		$this->assertSame( 'adminsonly', $levels[ $f ], 'Stored level must be the admin default, never the crafted value.' );
	}

	/**
	 * @group bb_xprofile_can_change_field_visibility
	 */
	public function test_signup_activation_honors_submitted_visibility_for_allowed_field() {
		$f = $this->create_visibility_field( 'allowed', 'adminsonly' );

		$signup_id = BP_Signup::add(
			array(
				'user_login'     => 'allowedsignupuser',
				'user_email'     => 'allowedsignup@example.test',
				'activation_key' => 'allowedsignupkey',
				'meta'           => array(
					'password'              => 'password',
					'profile_field_ids'     => (string) $f,
					"field_{$f}"            => 'Allowed value',
					"field_{$f}_visibility" => 'loggedin',
				),
			)
		);
		$this->assertNotEmpty( $signup_id );

		$user_id = bp_core_activate_signup( 'allowedsignupkey' );
		$this->assertNotWPError( $user_id );

		// Control: activation still honors the submitted level for fields the
		// member may change — proves the path processes the signup meta at all.
		$this->assertSame( 'loggedin', xprofile_get_field_visibility_level( $f, $user_id ) );
	}

	/**
	 * Create an xprofile field with the given visibility settings.
	 *
	 * @param string $allow_custom_visibility 'allowed' or 'disabled'.
	 * @param string $default_visibility      Admin-set default visibility level.
	 *
	 * @return int Field ID.
	 */
	protected function create_visibility_field( $allow_custom_visibility, $default_visibility ) {
		$g = self::factory()->xprofile_group->create();
		$f = self::factory()->xprofile_field->create( array( 'field_group_id' => $g ) );

		bp_xprofile_update_field_meta( $f, 'default_visibility', $default_visibility );
		bp_xprofile_update_field_meta( $f, 'allow_custom_visibility', $allow_custom_visibility );

		return $f;
	}
}
