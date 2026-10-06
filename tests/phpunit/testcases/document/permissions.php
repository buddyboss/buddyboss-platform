<?php
/**
 * Tests for document folder permission functions.
 *
 * @package BuddyBoss\Tests\Document
 */

/**
 * @group document
 * @group document_permissions
 */
class BP_Tests_Document_Permissions extends BP_UnitTestCase {

	/**
	 * Set up group document support, which groups_can_user_manage_document() requires.
	 */
	public function setUp(): void {
		parent::setUp();

		bp_update_option( 'bp_media_group_document_support', 1 );
		add_filter( 'bp_is_active', array( $this, 'force_components_active' ), 10, 2 );
	}

	public function tearDown(): void {
		remove_filter( 'bp_is_active', array( $this, 'force_components_active' ), 10 );

		parent::tearDown();
	}

	/**
	 * The document component is not enabled in the default test install.
	 *
	 * @param bool   $is_active Whether the component is active.
	 * @param string $component Component ID.
	 * @return bool
	 */
	public function force_components_active( $is_active, $component ) {
		if ( in_array( $component, array( 'document', 'groups' ), true ) ) {
			return true;
		}

		return $is_active;
	}

	/**
	 * Create a group with a document status and a folder owned by $owner_id.
	 *
	 * @param int    $owner_id Folder owner.
	 * @param int    $group_id Group ID.
	 * @param string $status   Group document status.
	 * @return int Folder ID.
	 */
	protected function create_group_folder( $owner_id, $group_id, $status = 'members' ) {
		groups_update_groupmeta( $group_id, 'document_status', $status );

		return bp_folder_add(
			array(
				'user_id'  => $owner_id,
				'group_id' => $group_id,
				'title'    => 'Folder of ' . $owner_id,
				'privacy'  => 'grouponly',
			)
		);
	}

	/**
	 * The reported bug: a member could upload into another member's group folder
	 * but could not move a document into it.
	 *
	 * @group bb_document_user_can_add_to_folder
	 */
	public function test_group_member_can_move_into_another_members_folder() {
		$owner  = self::factory()->user->create();
		$member = self::factory()->user->create();
		$group  = self::factory()->group->create( array( 'creator_id' => $owner ) );

		self::add_user_to_group( $member, $group );
		$folder = $this->create_group_folder( $owner, $group, 'members' );

		self::set_current_user( $member );

		$this->assertTrue( bb_document_user_can_add_to_folder( $folder ) );
	}

	/**
	 * With group document support disabled, bb_document_user_can_upload() falls
	 * through to the PROFILE document rule and returns true for a group folder,
	 * ignoring the group entirely. The move gate must not inherit that: it has to
	 * deny, including for someone who is not a member of the group at all.
	 *
	 * This pins the deliberate divergence from that wrapper. Asserting the two
	 * agree would be near-tautological and would pass while the hole was open.
	 *
	 * @group bb_document_user_can_add_to_folder
	 */
	public function test_group_support_disabled_denies_and_diverges_from_upload_wrapper() {
		$owner    = self::factory()->user->create();
		$member   = self::factory()->user->create();
		$outsider = self::factory()->user->create();
		$group    = self::factory()->group->create( array( 'creator_id' => $owner ) );

		self::add_user_to_group( $member, $group );
		$folder = $this->create_group_folder( $owner, $group, 'members' );

		// The vulnerable configuration: group documents OFF, profile documents ON.
		bp_update_option( 'bp_media_group_document_support', 0 );
		bp_update_option( 'bp_media_profile_document_support', 1 );

		self::set_current_user( $outsider );

		// The wrapper the helper must NOT delegate to reports true here, because
		// it falls through to the profile rule and ignores the group.
		$this->assertTrue( bb_document_user_can_upload( $outsider, $group ) );

		// The gate itself must refuse a non-member.
		$this->assertFalse( bb_document_user_can_add_to_folder( $folder ) );

		// ...and refuse an actual group member too, since group documents are off.
		self::set_current_user( $member );
		$this->assertFalse( bb_document_user_can_add_to_folder( $folder ) );
	}

	/**
	 * A folder owner could always place content in their own folder. The added
	 * group allowance must never take that away, whatever the group setting is.
	 *
	 * @group bb_document_user_can_add_to_folder
	 */
	public function test_folder_owner_can_move_into_own_group_folder_for_every_status() {
		$organizer = self::factory()->user->create();
		$member    = self::factory()->user->create();
		$group     = self::factory()->group->create( array( 'creator_id' => $organizer ) );

		self::add_user_to_group( $member, $group );

		// Folder owned by a plain member, not by the group organizer.
		$folder = bp_folder_add(
			array(
				'user_id'  => $member,
				'group_id' => $group,
				'title'    => 'Member owned folder',
				'privacy'  => 'grouponly',
			)
		);

		self::set_current_user( $member );

		foreach ( array( 'members', 'mods', 'admins' ) as $status ) {
			groups_update_groupmeta( $group, 'document_status', $status );

			$this->assertTrue(
				bb_document_user_can_add_to_folder( $folder ),
				"Owner was denied their own folder with document_status '{$status}'."
			);
		}
	}

	/**
	 * Same guarantee for a group moderator who owns the folder, under the
	 * strictest setting.
	 *
	 * @group bb_document_user_can_add_to_folder
	 */
	public function test_moderator_who_owns_folder_can_move_when_status_is_admins() {
		$organizer = self::factory()->user->create();
		$mod       = self::factory()->user->create();
		$group     = self::factory()->group->create( array( 'creator_id' => $organizer ) );

		self::add_user_to_group( $mod, $group, array( 'is_mod' => 1 ) );

		$folder = bp_folder_add(
			array(
				'user_id'  => $mod,
				'group_id' => $group,
				'title'    => 'Moderator owned folder',
				'privacy'  => 'grouponly',
			)
		);

		groups_update_groupmeta( $group, 'document_status', 'admins' );

		self::set_current_user( $mod );

		$this->assertTrue( bb_document_user_can_add_to_folder( $folder ) );
	}

	/**
	 * The gate must never widen access to a group the user cannot even see.
	 *
	 * @group bb_document_user_can_add_to_folder
	 */
	public function test_non_member_denied_on_hidden_group_folder_with_support_disabled() {
		$owner    = self::factory()->user->create();
		$outsider = self::factory()->user->create();
		$group    = self::factory()->group->create(
			array(
				'creator_id' => $owner,
				'status'     => 'hidden',
			)
		);

		$folder = $this->create_group_folder( $owner, $group, 'members' );

		self::set_current_user( $outsider );
		$this->assertFalse( bb_document_user_can_add_to_folder( $folder ) );

		bp_update_option( 'bp_media_group_document_support', 0 );
		bp_update_option( 'bp_media_profile_document_support', 1 );
		$this->assertFalse( bb_document_user_can_add_to_folder( $folder ) );
	}

	/**
	 * @group bb_document_user_can_add_to_folder
	 */
	public function test_folder_owner_can_move_into_own_group_folder() {
		$owner = self::factory()->user->create();
		$group = self::factory()->group->create( array( 'creator_id' => $owner ) );

		$folder = $this->create_group_folder( $owner, $group, 'members' );

		self::set_current_user( $owner );

		$this->assertTrue( bb_document_user_can_add_to_folder( $folder ) );
	}

	/**
	 * Negative: "Organizers and Moderators only" must still lock members out.
	 *
	 * @group bb_document_user_can_add_to_folder
	 */
	public function test_member_cannot_move_into_group_folder_when_status_is_mods() {
		$owner  = self::factory()->user->create();
		$member = self::factory()->user->create();
		$mod    = self::factory()->user->create();
		$group  = self::factory()->group->create( array( 'creator_id' => $owner ) );

		self::add_user_to_group( $member, $group );
		self::add_user_to_group( $mod, $group, array( 'is_mod' => 1 ) );
		$folder = $this->create_group_folder( $owner, $group, 'mods' );

		self::set_current_user( $member );
		$this->assertFalse( bb_document_user_can_add_to_folder( $folder ) );

		// The moderator is still allowed, so this is not a blanket denial.
		self::set_current_user( $mod );
		$this->assertTrue( bb_document_user_can_add_to_folder( $folder ) );
	}

	/**
	 * Negative: "Organizers only" must lock out members and moderators.
	 *
	 * @group bb_document_user_can_add_to_folder
	 */
	public function test_member_and_mod_cannot_move_into_group_folder_when_status_is_admins() {
		$owner  = self::factory()->user->create();
		$member = self::factory()->user->create();
		$mod    = self::factory()->user->create();
		$group  = self::factory()->group->create( array( 'creator_id' => $owner ) );

		self::add_user_to_group( $member, $group );
		self::add_user_to_group( $mod, $group, array( 'is_mod' => 1 ) );
		$folder = $this->create_group_folder( $owner, $group, 'admins' );

		self::set_current_user( $member );
		$this->assertFalse( bb_document_user_can_add_to_folder( $folder ) );

		self::set_current_user( $mod );
		$this->assertFalse( bb_document_user_can_add_to_folder( $folder ) );

		// The group organizer is still allowed.
		self::set_current_user( $owner );
		$this->assertTrue( bb_document_user_can_add_to_folder( $folder ) );
	}

	/**
	 * @group bb_document_user_can_add_to_folder
	 */
	public function test_non_member_cannot_move_into_group_folder() {
		$owner     = self::factory()->user->create();
		$outsider  = self::factory()->user->create();
		$group     = self::factory()->group->create( array( 'creator_id' => $owner ) );

		$folder = $this->create_group_folder( $owner, $group, 'members' );

		self::set_current_user( $outsider );

		$this->assertFalse( bb_document_user_can_add_to_folder( $folder ) );
	}

	/**
	 * @group bb_document_user_can_add_to_folder
	 */
	public function test_logged_out_user_cannot_move_into_group_folder() {
		$owner = self::factory()->user->create();
		$group = self::factory()->group->create( array( 'creator_id' => $owner ) );

		$folder = $this->create_group_folder( $owner, $group, 'members' );

		self::set_current_user( 0 );

		$this->assertFalse( bb_document_user_can_add_to_folder( $folder ) );
	}

	/**
	 * A personal folder has no group setting to delegate contribution, so the
	 * group loosening must not reach it.
	 *
	 * @group bb_document_user_can_add_to_folder
	 */
	public function test_user_cannot_move_into_another_users_personal_folder() {
		$owner = self::factory()->user->create();
		$other = self::factory()->user->create();

		$folder = bp_folder_add(
			array(
				'user_id' => $owner,
				'title'   => 'Personal folder',
				'privacy' => 'public',
			)
		);

		self::set_current_user( $other );

		$this->assertFalse( bb_document_user_can_add_to_folder( $folder ) );
	}

	/**
	 * @group bb_document_user_can_add_to_folder
	 */
	public function test_user_can_move_into_own_personal_folder() {
		$owner = self::factory()->user->create();

		$folder = bp_folder_add(
			array(
				'user_id' => $owner,
				'title'   => 'Personal folder',
				'privacy' => 'public',
			)
		);

		self::set_current_user( $owner );

		$this->assertTrue( bb_document_user_can_add_to_folder( $folder ) );
	}

	/**
	 * Site moderators were allowed by the previous gate before any user ID was
	 * resolved. That must not regress for an admin outside the group.
	 *
	 * @group bb_document_user_can_add_to_folder
	 */
	public function test_site_admin_outside_group_can_move_into_group_folder() {
		$owner = self::factory()->user->create();
		$admin = self::factory()->user->create( array( 'role' => 'administrator' ) );
		$group = self::factory()->group->create( array( 'creator_id' => $owner ) );

		$folder = $this->create_group_folder( $owner, $group, 'admins' );

		self::set_current_user( $admin );

		// Precondition: without bp_moderate this test proves nothing.
		$this->assertTrue( bp_current_user_can( 'bp_moderate' ) );
		$this->assertFalse( groups_is_user_member( $admin, $group ) );

		$this->assertTrue( bb_document_user_can_add_to_folder( $folder ) );
	}

	/**
	 * @group bb_document_user_can_add_to_folder
	 */
	public function test_invalid_folder_is_refused() {
		/*
		 * Run as a site administrator on purpose. For a plain member both the old
		 * and new gates deny a missing folder, so the assertion would hold either
		 * way; bp_moderate is the only actor whose answer the id guard changes
		 * (bp_folder_user_can_edit() would still grant it).
		 */
		$admin = self::factory()->user->create( array( 'role' => 'administrator' ) );
		self::set_current_user( $admin );

		$this->assertTrue( bp_current_user_can( 'bp_moderate' ) );

		$this->assertFalse( bb_document_user_can_add_to_folder( 0 ) );
		$this->assertFalse( bb_document_user_can_add_to_folder( false ) );
		$this->assertFalse( bb_document_user_can_add_to_folder( 999999 ) );
	}

	/**
	 * @group bb_document_user_can_add_to_folder
	 */
	public function test_add_to_folder_is_filterable() {
		$owner    = self::factory()->user->create();
		$outsider = self::factory()->user->create();
		$group    = self::factory()->group->create( array( 'creator_id' => $owner ) );

		$folder = $this->create_group_folder( $owner, $group, 'members' );

		self::set_current_user( $outsider );
		$this->assertFalse( bb_document_user_can_add_to_folder( $folder ) );

		add_filter( 'bb_document_user_can_add_to_folder', '__return_true' );
		$this->assertTrue( bb_document_user_can_add_to_folder( $folder ) );
		remove_filter( 'bb_document_user_can_add_to_folder', '__return_true' );
	}

	/**
	 * With the groups component inactive the group allowance must not run at all.
	 * groups_can_user_manage_document() is not even loaded then, so the
	 * bp_is_active( 'groups' ) conjunct is what stands between a legacy group-folder
	 * row and a fatal. The owner still gets in through bp_folder_user_can_edit().
	 *
	 * setUp() pins groups active for every other test, so this one has to
	 * out-prioritise that pin. Without it, deleting the conjunct is invisible to the
	 * suite.
	 *
	 * @group bb_document_user_can_add_to_folder
	 */
	public function test_group_allowance_is_skipped_when_groups_component_is_inactive() {
		$owner  = self::factory()->user->create();
		$member = self::factory()->user->create();
		$group  = self::factory()->group->create( array( 'creator_id' => $owner ) );

		self::add_user_to_group( $member, $group );
		$folder = $this->create_group_folder( $owner, $group, 'members' );

		// The fixtures above needed groups active; the predicate under test must see it inactive.
		add_filter( 'bp_is_active', array( $this, 'force_groups_inactive' ), 20, 2 );

		self::set_current_user( $member );
		$this->assertFalse( bp_is_active( 'groups' ), 'Precondition: the groups component must read as inactive.' );
		$this->assertFalse( bb_document_user_can_add_to_folder( $folder ), 'A peer must be denied when the groups component is inactive.' );

		self::set_current_user( $owner );
		$this->assertTrue( bb_document_user_can_add_to_folder( $folder ), 'The folder owner keeps access through bp_folder_user_can_edit().' );

		remove_filter( 'bp_is_active', array( $this, 'force_groups_inactive' ), 20 );
	}

	/**
	 * Out-prioritises force_components_active() for the groups component only.
	 *
	 * @param bool   $is_active Whether the component is active.
	 * @param string $component Component ID.
	 * @return bool
	 */
	public function force_groups_inactive( $is_active, $component ) {
		if ( 'groups' === $component ) {
			return false;
		}

		return $is_active;
	}

	/**
	 * The predicate being correct proves nothing if a call site does not use it.
	 * This drives bp_document_move_document_to_folder() end to end, so reverting
	 * that destination gate to bp_folder_user_can_edit() turns this red.
	 *
	 * @group bb_document_user_can_add_to_folder
	 */
	public function test_move_function_lets_a_member_move_into_a_peers_folder() {
		$owner  = self::factory()->user->create();
		$member = self::factory()->user->create();
		$group  = self::factory()->group->create( array( 'creator_id' => $owner ) );

		self::add_user_to_group( $member, $group );
		$peer_folder = $this->create_group_folder( $owner, $group, 'members' );

		self::set_current_user( $member );

		// A document owned by the acting member, sitting at the group root.
		$document                = new BP_Document();
		$document->user_id       = $member;
		$document->group_id      = $group;
		$document->attachment_id = 1;
		$document->title         = 'PROD-9526 call-site fixture';
		$document->privacy       = 'grouponly';
		$document->folder_id     = 0;
		$document->save();

		$this->assertNotEmpty( $document->id, 'Fixture document was not created.' );

		$moved = bp_document_move_document_to_folder( $document->id, $peer_folder, $group );

		$this->assertNotEmpty( $moved, 'The move was refused at the destination gate.' );

		// Assert the stored row actually moved, not just the return value.
		$stored = new BP_Document( $document->id );
		$this->assertEquals( $peer_folder, (int) $stored->folder_id );
	}

	/**
	 * Same call site, tightened setting: the move function must refuse.
	 *
	 * @group bb_document_user_can_add_to_folder
	 */
	public function test_move_function_refuses_when_status_is_admins() {
		$owner  = self::factory()->user->create();
		$member = self::factory()->user->create();
		$group  = self::factory()->group->create( array( 'creator_id' => $owner ) );

		self::add_user_to_group( $member, $group );
		$peer_folder = $this->create_group_folder( $owner, $group, 'admins' );

		self::set_current_user( $member );

		$document                = new BP_Document();
		$document->user_id       = $member;
		$document->group_id      = $group;
		$document->attachment_id = 1;
		$document->title         = 'PROD-9526 call-site fixture';
		$document->privacy       = 'grouponly';
		$document->folder_id     = 0;
		$document->save();

		$this->assertEmpty( bp_document_move_document_to_folder( $document->id, $peer_folder, $group ) );

		$stored = new BP_Document( $document->id );
		$this->assertEquals( 0, (int) $stored->folder_id );
	}

	/**
	 * Scope guard for PROD-9526: the fix must NOT loosen the shared folder-edit
	 * gate, which still governs rename, privacy and subfolder creation.
	 *
	 * @group bb_document_user_can_add_to_folder
	 */
	public function test_folder_edit_gate_remains_strict_for_members() {
		$owner  = self::factory()->user->create();
		$member = self::factory()->user->create();
		$group  = self::factory()->group->create( array( 'creator_id' => $owner ) );

		self::add_user_to_group( $member, $group );
		$folder = $this->create_group_folder( $owner, $group, 'members' );

		self::set_current_user( $member );

		// Moving in is allowed...
		$this->assertTrue( bb_document_user_can_add_to_folder( $folder ) );

		// ...but editing the folder itself is not.
		$this->assertFalse( bp_folder_user_can_edit( $folder ) );
	}
}
