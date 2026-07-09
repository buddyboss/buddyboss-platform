<?php

/**
 * @group groups
 * @group activity
 */
class BP_Tests_Groups_Activity extends BP_UnitTestCase {
	/**
	 * @group activity_action
	 * @group bp_groups_format_activity_action_created_group
	 */
	public function test_bp_groups_format_activity_action_created_group() {
		$u = self::factory()->user->create();
		$g = self::factory()->group->create();
		$a = self::factory()->activity->create( array(
			'component' => buddypress()->groups->id,
			'type' => 'created_group',
			'user_id' => $u,
			'item_id' => $g,
		) );

		$a_obj = new BP_Activity_Activity( $a );
		$g_obj = groups_get_group( $g );

		$expected = sprintf( __( '%s created the group %s', 'buddyboss' ), bp_core_get_userlink( $u ),  '<a href="' . bp_get_group_permalink( $g_obj ) . '">' . $g_obj->name . '</a>' );

		$this->assertSame( $expected, $a_obj->action );
	}

	/**
	 * @group activity_action
	 * @group bp_groups_format_activity_action_joined_group
	 */
	public function test_bp_groups_format_activity_action_joined_group() {
		$u = self::factory()->user->create();
		$g = self::factory()->group->create();
		$a = self::factory()->activity->create( array(
			'component' => buddypress()->groups->id,
			'type' => 'joined_group',
			'user_id' => $u,
			'item_id' => $g,
		) );

		$a_obj = new BP_Activity_Activity( $a );
		$g_obj = groups_get_group( $g );

		$expected = sprintf( __( '%s joined the group %s', 'buddyboss' ), bp_core_get_userlink( $u ),  '<a href="' . bp_get_group_permalink( $g_obj ) . '">' . $g_obj->name . '</a>' );

		$this->assertSame( $expected, $a_obj->action );
	}

	/**
	 * @group activity_action
	 * @group bp_groups_format_activity_action_group_details_updated
	 */
	public function test_bp_groups_format_activity_action_group_details_updated_with_no_change() {
		$group = self::factory()->group->create_and_get();
		groups_edit_base_group_details( array(
				'group_id'       => $group->id,
				'name'           => $group->name,
				'slug'           => $group->slug,
				'description'    => $group->description,
				'notify_members' => true,
		) );

		$a = bp_activity_get( array(
			'component' => buddypress()->groups->id,
			'action' => 'group_details_updated',
			'item_id' => $group->id,
		) );

		$this->assertTrue( empty( $a['activities'] ) );
	}

	/**
	 * @group activity_action
	 * @group bp_groups_format_activity_action_group_details_updated
	 */
	public function test_bp_groups_format_activity_action_group_details_updated_with_notify_members_false() {
		$group = self::factory()->group->create_and_get();
		groups_edit_base_group_details( array(
			'group_id'       => $group->id,
			'name'           => 'Foo',
			'slug'           => $group->slug,
			'description'    => $group->description,
			'notify_members' => false,
		) );

		$a = bp_activity_get( array(
			'component' => buddypress()->groups->id,
			'action' => 'group_details_updated',
			'item_id' => $group->id,
		) );

		$this->assertTrue( empty( $a['activities'] ) );
	}

	/**
	 * @group activity_action
	 * @group bp_groups_format_activity_action_group_details_updated
	 */
	public function test_bp_groups_format_activity_action_group_details_updated_with_updated_name() {
		$old_user = get_current_user_id();
		$u = self::factory()->user->create();
		$this->set_current_user( $u );

		$group = self::factory()->group->create_and_get();
		groups_edit_base_group_details( array(
			'group_id'       => $group->id,
			'name'           => 'Foo',
			'slug'           => $group->slug,
			'description'    => $group->description,
			'notify_members' => true,
		) );

		$a = bp_activity_get( array(
			'component' => buddypress()->groups->id,
			'action' => 'group_details_updated',
			'item_id' => $group->id,
		) );

		$this->assertNotEmpty( $a['activities'] );

		$expected = sprintf( __( '%s changed the name of the group %s from "%s" to "%s"', 'buddyboss' ), bp_core_get_userlink( $u ),  '<a href="' . bp_get_group_permalink( $group ) . '">Foo</a>', $group->name, 'Foo' );
		$this->assertSame( $expected, $a['activities'][0]->action );

		$this->set_current_user( $old_user );
	}

	/**
	 * @group activity_action
	 * @group bp_groups_format_activity_action_group_details_updated
	 */
	public function test_bp_groups_format_activity_action_group_details_updated_with_updated_description() {
		$old_user = get_current_user_id();
		$u = self::factory()->user->create();
		$this->set_current_user( $u );

		$group = self::factory()->group->create_and_get();
		groups_edit_base_group_details( array(
			'group_id'       => $group->id,
			'name'           => $group->name,
			'slug'           => $group->slug,
			'description'    => 'Bar',
			'notify_members' => true,
		) );

		$a = bp_activity_get( array(
			'component' => buddypress()->groups->id,
			'action' => 'group_details_updated',
			'item_id' => $group->id,
		) );

		$this->assertNotEmpty( $a['activities'] );

		$expected = sprintf( __( '%s changed the description of the group %s from "%s" to "%s"', 'buddyboss' ), bp_core_get_userlink( $u ),  '<a href="' . bp_get_group_permalink( $group ) . '">' . $group->name . '</a>', $group->description, 'Bar' );
		$this->assertSame( $expected, $a['activities'][0]->action );

		$this->set_current_user( $old_user );
	}

	/**
	 * @group activity_action
	 * @group bp_groups_format_activity_action_group_details_updated
	 */
	public function test_bp_groups_format_activity_action_group_details_updated_with_updated_slug() {
		$old_user = get_current_user_id();
		$u = self::factory()->user->create();
		$this->set_current_user( $u );

		$group = self::factory()->group->create_and_get();
		groups_edit_base_group_details( array(
			'group_id'       => $group->id,
			'name'           => $group->name,
			'slug'           => 'flaxen',
			'description'    => $group->description,
			'notify_members' => true,
		) );
		$new_group_details = groups_get_group( $group->id );

		$a = bp_activity_get( array(
			'component' => buddypress()->groups->id,
			'action' => 'group_details_updated',
			'item_id' => $group->id,
		) );

		$this->assertNotEmpty( $a['activities'] );

		$expected = sprintf( __( '%s changed the permalink of the group %s.', 'buddyboss' ), bp_core_get_userlink( $u ),  '<a href="' . bp_get_group_permalink( $new_group_details ) . '">' . $group->name . '</a>' );
		$this->assertSame( $expected, $a['activities'][0]->action );

		$this->set_current_user( $old_user );
	}

	/**
	 * @group activity_action
	 * @group bp_groups_format_activity_action_group_details_updated
	 */
	public function test_bp_groups_format_activity_action_group_details_updated_with_updated_name_and_description() {
		$old_user = get_current_user_id();
		$u = self::factory()->user->create();
		$this->set_current_user( $u );

		$group = self::factory()->group->create_and_get();
		groups_edit_base_group_details( array(
			'group_id'       => $group->id,
			'name'           => 'Foo',
			'slug'           => $group->slug,
			'description'    => 'Bar',
			'notify_members' => true,
		) );

		$a = bp_activity_get( array(
			'component' => buddypress()->groups->id,
			'action' => 'group_details_updated',
			'item_id' => $group->id,
		) );

		$this->assertNotEmpty( $a['activities'] );

		$expected = sprintf( __( '%s changed the name and description of the group %s', 'buddyboss' ), bp_core_get_userlink( $u ),  '<a href="' . bp_get_group_permalink( $group ) . '">Foo</a>' );
		$this->assertSame( $expected, $a['activities'][0]->action );

		$this->set_current_user( $old_user );
	}

	/**
	 * @group bp_activity_can_comment
	 */
	public function test_groups_activity_can_comment() {
		$old_user = get_current_user_id();
		$u1 = self::factory()->user->create();
		$u2 = self::factory()->user->create();

		$g = self::factory()->group->create();

		// User 1 is a group member, while user 2 isn't.
		groups_join_group( $g, $u1 );

		$a = self::factory()->activity->create( array(
			'component' => buddypress()->groups->id,
			'type' => 'created_group',
			'user_id' => $u1,
			'item_id' => $g,
		) );

		$this->set_current_user( $u1 );
		if ( bp_has_activities( array( 'in' => $a ) ) ) {
			while ( bp_activities() ) : bp_the_activity();
				// User 1 should be able to comment.
				$this->assertTrue( bp_activity_can_comment() );
			endwhile;
		}

		$this->set_current_user( $u2 );
		if ( bp_has_activities( array( 'in' => $a ) ) ) {
			while ( bp_activities() ) : bp_the_activity();
				// User 2 should not be able to comment.
				$this->assertFalse( bp_activity_can_comment() );
			endwhile;
		}

		$this->set_current_user( $old_user );
	}

	/**
	 * A non-member site admin (bp_moderate) should see activity from a
	 * hidden/private group in the "groups" activity scope, staying consistent
	 * with the access they already have to the group's own activity list.
	 *
	 * @group bp_groups_filter_activity_scope
	 * @ticket PROD-9608
	 */
	public function test_non_member_site_admin_can_see_hidden_group_activity_in_scope() {
		// grant_super_admin() (bp_moderate) is only meaningful on multisite.
		if ( ! is_multisite() ) {
			return;
		}

		$old_user = get_current_user_id();
		$owner_id = self::factory()->user->create();

		// Hidden group the admin is NOT a member of.
		$g = self::factory()->group->create(
			array(
				'status'     => 'hidden',
				'creator_id' => $owner_id,
			)
		);

		$a = self::factory()->activity->create(
			array(
				'component'     => buddypress()->groups->id,
				'type'          => 'activity_update',
				'user_id'       => $owner_id,
				'item_id'       => $g,
				'hide_sitewide' => true,
			)
		);

		// Site admin (bp_moderate) who is not a member of the hidden group.
		$admin_id = self::factory()->user->create();
		grant_super_admin( $admin_id );
		$this->set_current_user( $admin_id );

		$activity = bp_activity_get(
			array(
				'scope'       => 'groups',
				'show_hidden' => true,
			)
		);

		$ids = wp_list_pluck( $activity['activities'], 'id' );

		$this->assertContains( $a, $ids, 'Non-member site admin should see hidden group activity in the groups scope.' );

		$this->set_current_user( $old_user );
	}

	/**
	 * A non-member, non-moderator user must NOT see activity from a
	 * hidden/private group in the "groups" activity scope.
	 *
	 * @group bp_groups_filter_activity_scope
	 * @ticket PROD-9608
	 */
	public function test_non_member_regular_user_cannot_see_hidden_group_activity_in_scope() {
		$old_user = get_current_user_id();
		$owner_id = self::factory()->user->create();

		$g = self::factory()->group->create(
			array(
				'status'     => 'hidden',
				'creator_id' => $owner_id,
			)
		);

		$a = self::factory()->activity->create(
			array(
				'component'     => buddypress()->groups->id,
				'type'          => 'activity_update',
				'user_id'       => $owner_id,
				'item_id'       => $g,
				'hide_sitewide' => true,
			)
		);

		// A regular user who is not a member and not a moderator.
		$u = self::factory()->user->create();
		$this->set_current_user( $u );

		$activity = bp_activity_get(
			array(
				'scope' => 'groups',
			)
		);

		$ids = wp_list_pluck( $activity['activities'], 'id' );

		$this->assertNotContains( $a, $ids, 'Non-member non-moderator user should not see hidden group activity in the groups scope.' );

		$this->set_current_user( $old_user );
	}
}
