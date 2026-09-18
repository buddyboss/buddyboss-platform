<?php

/**
 * Bio field / repeater field set interaction (PROD-10308).
 *
 * The Bio field is backed by the member's single WordPress "Biographical Info"
 * value, so it cannot live in a field set that repeats its fields. These cover the
 * guards that keep the two apart, and the mirror that used to corrupt
 * `user_description` on every profile save.
 *
 * @group xprofile
 * @group bb_xprofile_bio
 * @group bb_xprofile_repeaters
 */
class BB_Tests_XProfile_Bio_Repeater extends BP_UnitTestCase {

	/**
	 * Create a Bio field inside a brand new field set.
	 *
	 * Written straight through the class rather than the factory so the group is
	 * not repeating yet — which is the only way the save is allowed.
	 *
	 * @param int $group_id Field group ID.
	 *
	 * @return int Created field ID.
	 */
	protected function create_bio_field( $group_id ) {
		$field            = new BP_XProfile_Field();
		$field->group_id  = $group_id;
		$field->parent_id = 0;
		$field->type      = 'biography';
		$field->name      = 'Bio';
		$field->can_delete = true;

		$field->save();

		return (int) $field->id;
	}

	/**
	 * Turn a field set into a repeater without going through the guards.
	 *
	 * Reproduces the state a site reached before the guards existed.
	 *
	 * @param int $group_id Field group ID.
	 *
	 * @return void
	 */
	protected function force_repeater_on( $group_id ) {
		bp_xprofile_update_meta( $group_id, 'group', 'is_repeater_enabled', 'on' );
		wp_cache_delete( 'all', 'bp_xprofile_groups' );
		wp_cache_delete( $group_id, 'bp_xprofile_groups' );
	}

	public function test_bio_field_cannot_be_created_in_a_repeating_field_set() {
		$group_id = self::factory()->xprofile_group->create();
		$this->force_repeater_on( $group_id );

		$field             = new BP_XProfile_Field();
		$field->group_id   = $group_id;
		$field->parent_id  = 0;
		$field->type       = 'biography';
		$field->name       = 'Bio';
		$field->can_delete = true;

		$this->assertFalse( $field->save() );
	}

	public function test_bio_field_cannot_be_moved_into_a_repeating_field_set() {
		$plain_group_id = self::factory()->xprofile_group->create();
		$repeat_group_id = self::factory()->xprofile_group->create();
		$this->force_repeater_on( $repeat_group_id );

		$field_id = $this->create_bio_field( $plain_group_id );
		$this->assertNotEmpty( $field_id );

		$field           = xprofile_get_field( $field_id, null, false );
		$field->group_id = $repeat_group_id;

		$this->assertFalse( $field->save() );
	}

	public function test_bio_field_already_in_a_repeating_set_stays_editable() {
		$group_id = self::factory()->xprofile_group->create();
		$field_id = $this->create_bio_field( $group_id );

		// Only now does the set start repeating — the pre-guard state.
		$this->force_repeater_on( $group_id );

		$field       = xprofile_get_field( $field_id, null, false );
		$field->name = 'About me';

		$this->assertEquals( $field_id, $field->save() );
	}

	public function test_group_save_refuses_to_switch_the_repeater_on() {
		$group_id = self::factory()->xprofile_group->create();
		$this->create_bio_field( $group_id );

		$_POST['group_is_repeater'] = 'on';

		$group = new BP_XProfile_Group( $group_id );
		$group->save();

		unset( $_POST['group_is_repeater'] );

		$this->assertNotEquals( 'on', BP_XProfile_Group::get_group_meta( $group_id, 'is_repeater_enabled' ) );
	}

	public function test_group_save_still_allows_the_repeater_without_a_bio_field() {
		$group_id = self::factory()->xprofile_group->create();

		$_POST['group_is_repeater'] = 'on';

		$group = new BP_XProfile_Group( $group_id );
		$group->save();

		unset( $_POST['group_is_repeater'] );

		$this->assertEquals( 'on', BP_XProfile_Group::get_group_meta( $group_id, 'is_repeater_enabled' ) );
	}

	public function test_group_has_bio_field_detects_the_field() {
		$with_bio    = self::factory()->xprofile_group->create();
		$without_bio = self::factory()->xprofile_group->create();

		$this->create_bio_field( $with_bio );

		$this->assertTrue( bb_xprofile_group_has_bio_field( $with_bio ) );
		$this->assertFalse( bb_xprofile_group_has_bio_field( $without_bio ) );
		$this->assertFalse( bb_xprofile_group_has_bio_field( 0 ) );
	}

	public function test_is_repeater_group() {
		$group_id = self::factory()->xprofile_group->create();

		$this->assertFalse( bb_xprofile_is_repeater_group( $group_id ) );

		$this->force_repeater_on( $group_id );

		$this->assertTrue( bb_xprofile_is_repeater_group( $group_id ) );
		$this->assertFalse( bb_xprofile_is_repeater_group( 0 ) );
	}

	public function test_repeater_template_mirror_is_skipped_for_a_bio_field() {
		$group_id = self::factory()->xprofile_group->create();
		$bio_id   = $this->create_bio_field( $group_id );

		$text_id = self::factory()->xprofile_field->create(
			array(
				'field_group_id' => $group_id,
				'type'           => 'textbox',
			)
		);

		$this->assertTrue( bb_xprofile_skip_repeater_template_mirror( $bio_id ) );
		$this->assertFalse( bb_xprofile_skip_repeater_template_mirror( $text_id ) );

		// A missing field must not be mirrored either.
		$this->assertTrue( bb_xprofile_skip_repeater_template_mirror( 0 ) );
	}

	/**
	 * Build member-facing profile-loop args for a user.
	 *
	 * `repeater_show_main_fields_only` has to be explicit. Left unset,
	 * `bp_xprofile_get_groups()` defaults it to true outside a profile page — the
	 * mode that deliberately shows templates and hides clones, the opposite of what
	 * a member sees. The leak only exists in the member-facing mode.
	 *
	 * @param int $user_id  User ID.
	 * @param int $group_id Field group ID.
	 *
	 * @return array Loop arguments.
	 */
	protected function profile_loop_args( $user_id, $group_id ) {
		return array(
			'user_id'                        => $user_id,
			'profile_group_id'               => $group_id,
			'fetch_fields'                   => true,
			'fetch_field_data'               => true,
			'repeater_show_main_fields_only' => false,
		);
	}

	public function test_bio_field_is_not_restored_into_a_repeating_field_set() {
		$group_id = self::factory()->xprofile_group->create();
		$bio_id   = $this->create_bio_field( $group_id );

		/*
		 * A user per assertion, on purpose. `BP_XProfile_Group::get()` memoises its
		 * resolved field-ID list in a request-lifetime static keyed only on the
		 * md5 of the parsed args — the group's repeater state is not part of that
		 * key. Querying the same args either side of the toggle would replay the
		 * first result and the assertion would pass or fail for the wrong reason.
		 */
		$before_user = self::factory()->user->create();
		$after_user  = self::factory()->user->create();

		update_user_meta( $before_user, 'description', 'A1 Bio A2 Bio' );
		update_user_meta( $after_user, 'description', 'A1 Bio A2 Bio' );

		// Not repeating yet: the read-through restore is expected to fire.
		$field_ids = $this->collect_field_ids( bp_xprofile_get_groups( $this->profile_loop_args( $before_user, $group_id ) ) );
		$this->assertContains( $bio_id, $field_ids );

		$this->force_repeater_on( $group_id );

		$field_ids = $this->collect_field_ids( bp_xprofile_get_groups( $this->profile_loop_args( $after_user, $group_id ) ) );
		$this->assertNotContains( $bio_id, $field_ids );
	}

	/**
	 * Flatten the field IDs out of a profile-loop group list.
	 *
	 * @param array $groups Groups returned by `bp_xprofile_get_groups()`.
	 *
	 * @return array Field IDs.
	 */
	protected function collect_field_ids( $groups ) {
		$ids = array();

		foreach ( (array) $groups as $group ) {
			if ( empty( $group->fields ) ) {
				continue;
			}

			foreach ( (array) $group->fields as $field ) {
				$ids[] = (int) $field->id;
			}
		}

		return $ids;
	}
}
