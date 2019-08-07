<?php

/**
 * @package Integration
 * @since BuddyBoss 1.0.0
 * 
 * @group messages
 * @group functions
 */
class BP_Tests_Integration_Learndash_Members_Sync extends BP_UnitTestCase {

	public function setUp() {
		parent::setUp();

		if (! defined('LEARNDASH_VERSION')) {
			$this->markTestSkipped('Learndash Not Loaded!');
		}

		$this->resetIntegrationOptions();
	}

	/**
	 * 
	 * @since BuddyBoss 1.0.0
	 */
	public function test_bp_group_will_not_create_ld_group_if_syncing_is_off()
	{
		bp_ld_sync('settings')->set('buddypress.enabled', false)->update();

		$bpGroupId = self::factory()->group->create();

		$this->assertFalse(!! groups_get_groupmeta($bpGroupId, '_sync_group_id'));
	}

	/**
	 * 
	 * @since BuddyBoss 1.0.0
	 */
	public function test_bp_group_will_create_ld_group_if_syncing_is_on()
	{
		bp_ld_sync('settings')->set('buddypress.enabled', true)->update();

		$bpGroupId = self::factory()->group->create();

		$generator = bp_ld_sync('buddypress')->sync->generator($bpGroupId);
		$ldGroupId = $generator->getLdGroupId();
		$bpGroup = groups_get_group($generator->getBpGroupId());
		$ldGroup = get_post($ldGroupId);

		$this->assertTrue(!! groups_get_groupmeta($bpGroupId, '_sync_group_id'));
		$this->assertTrue(!! get_post_meta($ldGroupId, '_sync_group_id'));
		$this->assertEquals($ldGroup->post_title, $bpGroup->name);
	}

	/**
	 * 
	 * @since BuddyBoss 1.0.0
	 */
	public function test_bp_group_will_not_create_ld_group_if_syncing_is_on_but_auto_is_off()
	{
		bp_ld_sync('settings')
			->set('buddypress.enabled', true)
			->set('buddypress.default_auto_sync', false)
			->update();

		$bpGroupId = self::factory()->group->create();

		$this->assertFalse(!! groups_get_groupmeta($bpGroupId, '_sync_group_id'));
	}

	/**
	 * 
	 * @since BuddyBoss 1.0.0
	 */
	public function test_bp_group_will_sync_users_when_added()
	{
		bp_ld_sync('settings')->set('buddypress.enabled', true)->update();
		$member         = self::factory()->user->create();
		$bpGroupId      = self::factory()->group->create();
		$ldGroupId      = bp_ld_sync('buddypress')->sync->generator($bpGroupId)->getLdGroupId();
		$ldStudentCount = count(learndash_get_groups_user_ids($ldGroupId));

		groups_join_group($bpGroupId, $member);

		$newLdStudents = learndash_get_groups_user_ids($ldGroupId);
		$this->assertEquals($ldStudentCount + 1, count($newLdStudents));
		$this->assertContains($member, $newLdStudents);
	}

	/**
	 * 
	 * @since BuddyBoss 1.0.0
	 */
	public function test_bp_group_will_sync_users_to_admin_when_added()
	{
		bp_ld_sync('settings')->set('buddypress.enabled', true)->set('buddypress.default_user_sync_to', 'admin')->update();
		$member         = self::factory()->user->create();
		$bpGroupId      = self::factory()->group->create();
		$ldGroupId      = bp_ld_sync('buddypress')->sync->generator($bpGroupId)->getLdGroupId();
		$ldStudentCount = count(learndash_get_groups_user_ids($ldGroupId));
		$ldLeaderCount  = count(learndash_get_groups_administrator_ids($ldGroupId));

		groups_join_group($bpGroupId, $member);

		$newLdStudents = learndash_get_groups_user_ids($ldGroupId);
		$newLdLeaders  = learndash_get_groups_administrator_ids($ldGroupId);
		$this->assertEquals($ldStudentCount, count($newLdStudents));
		$this->assertNotContains($member, $newLdStudents);
		$this->assertEquals($ldLeaderCount + 1, count($newLdLeaders));
		$this->assertContains($member, $newLdLeaders);
	}

	/**
	 * 
	 * @since BuddyBoss 1.0.0
	 */
	public function test_bp_group_will_sync_admins_when_added()
	{
		bp_ld_sync('settings')->set('buddypress.enabled', true)->update();
		$admin          = self::factory()->user->create();
		$bpGroupId      = self::factory()->group->create();
		$ldGroupId      = bp_ld_sync('buddypress')->sync->generator($bpGroupId)->getLdGroupId();
		$ldStudentCount = count(learndash_get_groups_user_ids($ldGroupId));
		$ldLeaderCount  = count(learndash_get_groups_administrator_ids($ldGroupId));
		bp_update_is_item_admin(true, 'groups'); // bypass group promotion

		groups_join_group($bpGroupId, $admin);
		groups_promote_member($admin, $bpGroupId, 'admin');

		$newLdStudents = learndash_get_groups_user_ids($ldGroupId);
		$newLdLeaders  = learndash_get_groups_administrator_ids($ldGroupId);

		$this->assertEquals($ldStudentCount, count($newLdStudents));
		$this->assertNotContains($admin, $newLdStudents);
		$this->assertEquals($ldLeaderCount + 1, count($newLdLeaders));
		$this->assertContains($admin, $newLdLeaders);
	}

	/**
	 * 
	 * @since BuddyBoss 1.0.0
	 */
	public function test_bp_group_will_sync_mods_when_added()
	{
		bp_ld_sync('settings')->set('buddypress.enabled', true)->update();
		$mod            = self::factory()->user->create();
		$bpGroupId      = self::factory()->group->create();
		$ldGroupId      = bp_ld_sync('buddypress')->sync->generator($bpGroupId)->getLdGroupId();
		$ldStudentCount = count(learndash_get_groups_user_ids($ldGroupId));
		$ldLeaderCount  = count(learndash_get_groups_administrator_ids($ldGroupId));
		bp_update_is_item_admin(true, 'groups'); // bypass group promotion

		groups_join_group($bpGroupId, $mod);
		groups_promote_member($mod, $bpGroupId, 'mod');

		$newLdStudents = learndash_get_groups_user_ids($ldGroupId);
		$newLdLeaders  = learndash_get_groups_administrator_ids($ldGroupId);

		$this->assertEquals($ldStudentCount, count($newLdStudents));
		$this->assertNotContains($mod, $newLdStudents);
		$this->assertEquals($ldLeaderCount + 1, count($newLdLeaders));
		$this->assertContains($mod, $newLdLeaders);
	}

	/**
	 * 
	 * @since BuddyBoss 1.0.0
	 */
	public function test_bp_group_will_sync_mods_to_user_when_added()
	{
		bp_ld_sync('settings')->set('buddypress.enabled', true)->set('buddypress.default_mod_sync_to', 'user')->update();
		$mod            = self::factory()->user->create();
		$bpGroupId      = self::factory()->group->create();
		$ldGroupId      = bp_ld_sync('buddypress')->sync->generator($bpGroupId)->getLdGroupId();
		$ldStudentCount = count(learndash_get_groups_user_ids($ldGroupId));
		$ldLeaderCount  = count(learndash_get_groups_administrator_ids($ldGroupId));
		bp_update_is_item_admin(true, 'groups'); // bypass group promotion

		groups_join_group($bpGroupId, $mod);
		groups_promote_member($mod, $bpGroupId, 'mod');

		$newLdStudents = learndash_get_groups_user_ids($ldGroupId);
		$newLdLeaders  = learndash_get_groups_administrator_ids($ldGroupId);

		$this->assertEquals($ldStudentCount + 1, count($newLdStudents));
		$this->assertContains($mod, $newLdStudents);
		$this->assertEquals($ldLeaderCount, count($newLdLeaders));
		$this->assertNotContains($mod, $newLdLeaders);
	}

	/**
	 * 
	 * @since BuddyBoss 1.0.0
	 */
	public function test_bp_group_will_not_create_ld_group_on_user_join_when_setting_is_turned_on_afterwards()
	{
		bp_ld_sync('settings')->set('buddypress.enabled', false)->update();
		$bpGroupId = self::factory()->group->create();

		bp_ld_sync('settings')->set('buddypress.enabled', true)->update();
		$user = self::factory()->user->create();
		groups_join_group($bpGroupId, $user);

		$this->assertFalse(!! groups_get_groupmeta($bpGroupId, '_sync_group_id'));
	}

	/**
	 * 
	 * @since BuddyBoss 1.0.0
	 */
	public function test_bp_group_will_resync_users_on_existing_groups_when_setting_is_turned_on_afterwards()
	{
		bp_ld_sync('settings')->set('buddypress.enabled', true)->update();
		$bpGroupId = self::factory()->group->create();
		$ldGroupId = bp_ld_sync('buddypress')->sync->generator($bpGroupId)->getLdGroupId();
		$ldStudentCount = count(learndash_get_groups_user_ids($ldGroupId));

		bp_ld_sync('settings')->set('buddypress.enabled', false)->update();
		groups_join_group($bpGroupId, $u1 = self::factory()->user->create());
		groups_join_group($bpGroupId, $u2 = self::factory()->user->create());
		groups_join_group($bpGroupId, $u3 = self::factory()->user->create());

		// none shall be synced
		$this->assertEquals($ldStudentCount, count(learndash_get_groups_user_ids($ldGroupId)));

		// individual user won't trigger whole sync
		bp_ld_sync('settings')->set('buddypress.enabled', true)->update();
		groups_join_group($bpGroupId, $u4 = self::factory()->user->create());
		$this->assertEquals($ldStudentCount + 1, count(learndash_get_groups_user_ids($ldGroupId)));
		$this->assertContains($u4, learndash_get_groups_user_ids($ldGroupId));

		// update group will trigger whole sync
		groups_create_group(['group_id' => $bpGroupId]);
		$newLdStudents = learndash_get_groups_user_ids($ldGroupId);
		$this->assertEquals($ldStudentCount + 4, count($newLdStudents));
		$this->assertContains($u1, $newLdStudents);
		$this->assertContains($u2, $newLdStudents);
		$this->assertContains($u3, $newLdStudents);
		$this->assertContains($u4, $newLdStudents);
	}


	// setting turn on half way
	// setting turn off half way
	// role setting changed half way

	protected function resetIntegrationOptions()
	{
		$settings = bp_ld_sync('settings');

		$settings->set(null, $settings->defaultOptions())->update();
	}
}
