<?php

/**
 * @group xprofile
 * @group cache
 */
class BP_Tests_XProfile_Cache extends BP_UnitTestCase {
	/**
	 * @group bp_xprofile_update_meta_cache
	 */
	public function test_bp_xprofile_update_meta_cache() {
		$u = self::factory()->user->create();
		$g = self::factory()->xprofile_group->create();
		$f = self::factory()->xprofile_field->create( array(
			'field_group_id' => $g,
		) );

		$d = new BP_XProfile_ProfileData( $f, $u );
		$d->user_id = $u;
		$d->field_id = $f;
		$d->value = 'foo';
		$d->last_updated = bp_core_current_time();
		$d->save();

		bp_xprofile_add_meta( $g, 'group', 'group_foo', 'group_bar' );
		bp_xprofile_add_meta( $f, 'field', 'field_foo', 'field_bar' );
		bp_xprofile_add_meta( $d->id, 'data', 'data_foo', 'data_bar' );

		// prime cache
		bp_xprofile_update_meta_cache( array(
			'group' => array( $g ),
			'field' => array( $f ),
			'data' => array( $d->id ),
		) );

		$g_expected = array(
			'group_foo' => array(
				'group_bar',
			),
		);

		$this->assertSame( $g_expected, wp_cache_get( $g, 'xprofile_group_meta' ) );

		$f_expected = array(
			'field_foo' => array(
				'field_bar',
			),
		);

		$this->assertSame( $f_expected, wp_cache_get( $f, 'xprofile_field_meta' ) );

		$d_expected = array(
			'data_foo' => array(
				'data_bar',
			),
		);

		$this->assertSame( $d_expected, wp_cache_get( $d->id, 'xprofile_data_meta' ) );
	}

	/**
	 * @group bp_xprofile_update_meta_cache
	 * @group bp_has_profile
	 */
	public function test_bp_has_profile_meta_cache() {
		$u = self::factory()->user->create();
		$g = self::factory()->xprofile_group->create();
		$f = self::factory()->xprofile_field->create( array(
			'field_group_id' => $g,
		) );

		$d = new BP_XProfile_ProfileData( $f, $u );
		$d->user_id = $u;
		$d->field_id = $f;
		$d->value = 'foo';
		$d->last_updated = bp_core_current_time();
		$d->save();

		bp_xprofile_add_meta( $g, 'group', 'group_foo', 'group_bar' );
		bp_xprofile_add_meta( $f, 'field', 'field_foo', 'field_bar' );
		bp_xprofile_add_meta( $d->id, 'data', 'data_foo', 'data_bar' );

		// prime cache
		bp_has_profile( array(
			'user_id' => $u,
			'profile_group_id' => $g,
		) );

		$g_expected = array(
			'group_foo' => array(
				'group_bar',
			),
		);

		$this->assertSame( $g_expected, wp_cache_get( $g, 'xprofile_group_meta' ) );

		$f_expected = array(
			'field_foo' => array(
				'field_bar',
			),
		);

		$this->assertSame( $f_expected, wp_cache_get( $f, 'xprofile_field_meta' ) );

		$d_expected = array(
			'data_foo' => array(
				'data_bar',
			),
		);

		$this->assertSame( $d_expected, wp_cache_get( $d->id, 'xprofile_data_meta' ) );
	}

	/**
	 * @group bp_xprofile_update_meta_cache
	 * @group bp_has_profile
	 */
	public function test_bp_has_profile_meta_cache_update_meta_cache_false() {
		$u = self::factory()->user->create();
		$g = self::factory()->xprofile_group->create();
		$f = self::factory()->xprofile_field->create( array(
			'field_group_id' => $g,
		) );

		$d = new BP_XProfile_ProfileData( $f, $u );
		$d->user_id = $u;
		$d->field_id = $f;
		$d->value = 'foo';
		$d->last_updated = bp_core_current_time();
		$d->save();

		bp_xprofile_add_meta( $g, 'group', 'group_foo', 'group_bar' );
		bp_xprofile_add_meta( $f, 'field', 'field_foo', 'field_bar' );
		bp_xprofile_add_meta( $d->id, 'data', 'data_foo', 'data_bar' );

		// prime cache
		bp_has_profile( array(
			'user_id' => $u,
			'profile_group_id' => $g,
			'update_meta_cache' => false,
		) );

		$this->assertFalse( wp_cache_get( $g, 'xprofile_group_meta' ) );
		$this->assertFalse( wp_cache_get( $f, 'xprofile_field_meta' ) );
		$this->assertFalse( wp_cache_get( $d->id, 'xprofile_data_meta' ) );
	}

	/**
	 * @ticket BP6638
	 */
	public function test_field_cache_should_be_invalidated_on_save() {
		$g = self::factory()->xprofile_group->create();
		$f = self::factory()->xprofile_field->create( array(
			'field_group_id' => $g,
			'name' => 'Foo',
		) );

		$field = xprofile_get_field( $f );
		$this->assertSame( 'Foo', $field->name );

		$field->name = 'Bar';
		$this->assertNotEmpty( $field->save() );

		$field_2 = xprofile_get_field( $f );
		$this->assertSame( 'Bar', $field_2->name );
	}

	/**
	 * @ticket BP7407
	 */
	public function test_get_field_id_from_name_should_be_cached() {
		global $wpdb;

		$g = self::factory()->xprofile_group->create();
		$f = self::factory()->xprofile_field->create( array(
			'field_group_id' => $g,
			'name' => 'Foo',
		) );

		// Prime cache.
		BP_XProfile_Field::get_id_from_name( 'Foo' );

		$num_queries = $wpdb->num_queries;

		$this->assertSame( $f, BP_XProfile_Field::get_id_from_name( 'Foo' ) );
		$this->assertSame( $num_queries, $wpdb->num_queries );
	}

	/**
	 * @ticket BP7407
	 */
	public function test_get_field_id_from_name_cache_should_be_invalidated_on_field_update() {
		$g = self::factory()->xprofile_group->create();
		$f1 = self::factory()->xprofile_field->create( array(
			'field_group_id' => $g,
			'name' => 'Foo',
		) );
		$f2 = self::factory()->xprofile_field->create( array(
			'field_group_id' => $g,
			'name' => 'Bar',
		) );

		// Prime cache.
		xprofile_get_field_id_from_name( 'Foo' );
		xprofile_get_field_id_from_name( 'Bar' );

		// Free up the name 'Bar'.
		$field2 = xprofile_get_field( $f2 );
		$field2->name = 'Quz';
		$field2->save();

		// Take the name 'Bar'.
		$field1 = xprofile_get_field( $f1 );
		$field1->name = 'Bar';
		$field1->save();

		$this->assertSame( $f1, BP_XProfile_Field::get_id_from_name( 'Bar' ) );
	}

	/**
	 * @ticket BP7407
	 */
	public function test_get_field_id_from_name_cache_should_be_invalidated_on_field_deletion() {
		$g = self::factory()->xprofile_group->create();
		$f = self::factory()->xprofile_field->create( array(
			'field_group_id' => $g,
			'name' => 'Foo',
		) );

		// Prime cache.
		xprofile_get_field_id_from_name( 'Foo' );

		xprofile_delete_field( $f );

		$this->assertNull( BP_XProfile_Field::get_id_from_name( 'Bar' ) );
	}

	/**
	 * A member who renames themself must have the new name written to `wp_users.display_name`,
	 * even when their name was already resolved earlier in the same request.
	 *
	 * Every request resolves the logged-in member's name at `bp_setup_globals`
	 * (xprofile_override_user_fullnames()), before the profile form is processed, and the
	 * resolvers keep that answer in a per-request static cache. The save then ran
	 * bp_xprofile_update_display_name() against that cache and wrote the PREVIOUS name back,
	 * which left @mention search matching the name the member had given up.
	 *
	 * @group bb_xprofile_member_display_name_cache
	 * @ticket PROD-10542
	 */
	public function test_profile_save_writes_new_display_name_after_name_was_resolved_in_same_request() {
		bp_update_option( 'bp-display-name-format', 'first_last_name' );

		$first_name_id = bp_xprofile_firstname_field_id();
		$last_name_id  = bp_xprofile_lastname_field_id();
		$u             = self::factory()->user->create();

		// `profile_update` syncs the WordPress names into the xprofile name fields.
		wp_update_user(
			array(
				'ID'           => $u,
				'first_name'   => 'Harry',
				'last_name'    => 'Lime',
				'display_name' => 'Harry Lime',
			)
		);
		$this->assertSame( 'Lime', xprofile_get_field_data( $last_name_id, $u ) );

		// The start of the save request: bp_setup_globals resolves the logged-in member's name.
		wp_set_current_user( $u );
		$GLOBALS['bb_default_display_avatar'] = false;
		$this->assertSame( 'Harry Lime', bp_core_get_user_displayname( $u ) );

		// The profile form saves every posted field. Last Name is unchanged, which leaves the
		// default-avatar flag (an incidental cache bypass) off, exactly as on the real form.
		xprofile_set_field_data( $first_name_id, $u, 'Donald' );
		xprofile_set_field_data( $last_name_id, $u, 'Lime' );
		$this->assertFalse( $GLOBALS['bb_default_display_avatar'], 'Fixture must not bypass the name cache through the avatar flag.' );

		do_action(
			'xprofile_updated_profile',
			$u,
			array( $first_name_id, $last_name_id ),
			false,
			array(),
			array(
				$first_name_id => array( 'value' => 'Donald' ),
				$last_name_id  => array( 'value' => 'Lime' ),
			)
		);

		clean_user_cache( $u );
		$this->assertSame( 'Donald Lime', get_userdata( $u )->display_name );
		$this->assertSame( 'Donald Lime', bp_core_get_user_displayname( $u ) );
	}

	/**
	 * Removing a name part must also stop the cached name from being served for the rest of the
	 * request.
	 *
	 * @group bb_xprofile_member_display_name_cache
	 * @ticket PROD-10542
	 */
	public function test_deleting_name_field_data_refreshes_resolved_name_in_same_request() {
		bp_update_option( 'bp-display-name-format', 'first_last_name' );

		$first_name_id = bp_xprofile_firstname_field_id();
		$last_name_id  = bp_xprofile_lastname_field_id();
		$u             = self::factory()->user->create();

		wp_update_user(
			array(
				'ID'           => $u,
				'first_name'   => 'Harry',
				'last_name'    => 'Lime',
				'display_name' => 'Harry Lime',
			)
		);
		$this->assertSame( 'Lime', xprofile_get_field_data( $last_name_id, $u ) );

		// The resolver falls back to the user meta when a name field is empty.
		bp_update_user_meta( $u, 'last_name', '' );

		wp_set_current_user( $u );
		$GLOBALS['bb_default_display_avatar'] = false;
		$this->assertSame( 'Harry Lime', bp_core_get_user_displayname( $u ) );

		xprofile_delete_field_data( $last_name_id, $u );
		$this->assertFalse( $GLOBALS['bb_default_display_avatar'], 'Fixture must not bypass the name cache through the avatar flag.' );

		$this->assertSame( 'Harry', bp_core_get_user_displayname( $u ) );
	}
}
