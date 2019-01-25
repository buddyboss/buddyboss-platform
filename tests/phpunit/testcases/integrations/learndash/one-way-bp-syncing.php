<?php

/**
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
	 * @package Integration
	 * @since BuddyBoss 1.0.0
	 */
	public function test_bp_group_will_not_create_ld_group_if_syncing_is_off()
	{
		$st = bp_ld_sync('settings')->set('buddypress.enabled', false)->update();

		$bpGroupId = self::factory()->group->create();

		$this->assertFalse(!! groups_get_groupmeta($bpGroupId, '_sync_group_id'));
	}

	/**
	 * @package Integration
	 * @since BuddyBoss 1.0.0
	 */
	public function test_bp_group_will_create_ld_group_if_syncing_is_on()
	{
		$st = bp_ld_sync('settings')->set('buddypress.enabled', true)->update();

		$bpGroupId = self::factory()->group->create();

		$generator = bp_ld_sync('buddypress')->sync->generator($bpGroupId);
		$bpGroup = groups_get_group($generator->getBpGroupId());
		$ldGroup = get_post($generator->getLdGroupId());
		$this->assertTrue(!! groups_get_groupmeta($bpGroupId, '_sync_group_id'));
		$this->assertEquals($ldGroup->post_title, $bpGroup->name);
	}

	/**
	 * @package Integration
	 * @since BuddyBoss 1.0.0
	 */
	public function test_bp_group_will_not_create_ld_group_if_syncing_is_on_but_auto_is_off()
	{
		$st = bp_ld_sync('settings')
			->set('buddypress.enabled', true)
			->set('buddypress.default_auto_sync', false)
			->update();

		$bpGroupId = self::factory()->group->create();

		$this->assertFalse(!! groups_get_groupmeta($bpGroupId, '_sync_group_id'));
	}

	/**
	 * @package Integration
	 * @since BuddyBoss 1.0.0
	 */
	public function test_bp_group_will_sync_users_when_added()
	{
		$st = bp_ld_sync('settings')->set('buddypress.enabled', true)->update();
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
	 * @package Integration
	 * @since BuddyBoss 1.0.0
	 */
	public function test_bp_group_will_sync_users_to_admin_when_added()
	{
		$st = bp_ld_sync('settings')->set('buddypress.enabled', true)->set('buddypress.default_user_sync_to', 'admin')->update();
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
	 * @package Integration
	 * @since BuddyBoss 1.0.0
	 */
	public function test_bp_group_will_sync_admins_when_added()
	{
		$st = bp_ld_sync('settings')->set('buddypress.enabled', true)->update();
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
	 * @package Integration
	 * @since BuddyBoss 1.0.0
	 */
	public function test_bp_group_will_sync_mods_when_added()
	{
		$st = bp_ld_sync('settings')->set('buddypress.enabled', true)->update();
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
	 * @package Integration
	 * @since BuddyBoss 1.0.0
	 */
	public function test_bp_group_will_sync_mods_to_user_when_added()
	{
		$st = bp_ld_sync('settings')->set('buddypress.enabled', true)->set('buddypress.default_mod_sync_to', 'user')->update();
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

	// setting turn on half way
	// setting turn off half way
	// rolw setting changed half way

	protected function resetIntegrationOptions()
	{
		$settings = bp_ld_sync('settings');

		$settings->set(null, $settings->defaultOptions())->update();
	}
}
