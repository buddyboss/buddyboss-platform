<?php
/**
 * Tests for the Settings 2.0 Profile Fields AJAX field-editor lock flags.
 *
 * @group bb_admin_profile_fields
 * @group PROD-10439
 */
class BB_Tests_Admin_ProfileFieldsAjax extends BP_UnitTestCase {

	/**
	 * AJAX handler instance under test.
	 *
	 * @var BB_Admin_Profile_Fields_Ajax
	 */
	protected $ajax;

	/**
	 * Profile Type field created fresh for each test.
	 *
	 * @var BP_XProfile_Field|null
	 */
	protected $member_type_field;

	/**
	 * Load the admin-context-only AJAX class and build the instance under test.
	 *
	 * Also creates a `membertypes` field BEFORE any test body runs, so whichever
	 * test first triggers `bp_get_xprofile_member_type_field_id()` — whose result
	 * is memoized in a process-wide static that cannot be reset — memoizes a real
	 * field ID instead of 0, in any test-execution order.
	 */
	public function set_up() {
		parent::set_up();

		if ( ! class_exists( 'BB_Admin_Profile_Fields_Ajax' ) ) {
			require_once buddypress()->plugin_dir . 'bp-core/admin/classes/class-bb-admin-profile-fields-ajax.php';
		}

		$this->ajax = new BB_Admin_Profile_Fields_Ajax();

		// The membertypes field type is registered only when member types exist,
		// and the type whitelist is frozen into buddypress()->profile->field_types
		// at bootstrap — register a type and extend the whitelist first.
		if ( ! bp_get_member_type_object( 'prod10439type' ) ) {
			bp_register_member_type( 'prod10439type' );
		}
		if ( ! in_array( 'membertypes', buddypress()->profile->field_types, true ) ) {
			buddypress()->profile->field_types[] = 'membertypes';
		}

		$this->member_type_field = xprofile_get_field(
			self::factory()->xprofile_field->create(
				array(
					'field_group_id' => self::factory()->xprofile_group->create(),
					'type'           => 'membertypes',
				)
			)
		);
	}

	/**
	 * Remove the display-format and name-field options the tests set.
	 */
	public function tear_down() {
		bp_delete_option( 'bp-display-name-format' );
		bp_delete_option( 'bp-xprofile-nickname-field-id' );
		bp_delete_option( 'bp-xprofile-firstname-field-id' );
		bp_delete_option( 'bp-xprofile-lastname-field-id' );

		parent::tear_down();
	}

	/**
	 * Invoke a private method on the AJAX handler.
	 *
	 * @param string $method Method name.
	 * @param mixed  ...$args Arguments.
	 * @return mixed
	 */
	protected function invoke( $method, ...$args ) {
		$reflection = new ReflectionMethod( 'BB_Admin_Profile_Fields_Ajax', $method );
		$reflection->setAccessible( true );

		return $reflection->invoke( $this->ajax, ...$args );
	}

	/**
	 * Create a field and return its ID.
	 *
	 * @param int $group_id Field group ID.
	 * @return int
	 */
	protected function create_field( $group_id ) {
		return self::factory()->xprofile_field->create(
			array(
				'field_group_id' => $group_id,
				'type'           => 'textbox',
			)
		);
	}

	/**
	 * The reported bug: under first_last_name, Last Name must NOT be settings-locked
	 * (legacy always offered its Visibility/Enforce, Requirement and Type controls),
	 * while it must STAY move-locked via the bb_is_default_field() superset.
	 */
	public function test_last_name_settings_not_locked_under_first_last_name() {
		$group_id  = self::factory()->xprofile_group->create();
		$nickname  = $this->create_field( $group_id );
		$firstname = $this->create_field( $group_id );
		$lastname  = $this->create_field( $group_id );

		bp_update_option( 'bp-display-name-format', 'first_last_name' );
		bp_update_option( 'bp-xprofile-nickname-field-id', $nickname );
		bp_update_option( 'bp-xprofile-firstname-field-id', $firstname );
		bp_update_option( 'bp-xprofile-lastname-field-id', $lastname );

		// Legacy-parity locks: Nickname and First Name only.
		$this->assertTrue( $this->invoke( 'bb_is_settings_locked_field', $nickname ) );
		$this->assertTrue( $this->invoke( 'bb_is_settings_locked_field', $firstname ) );
		$this->assertFalse( $this->invoke( 'bb_is_settings_locked_field', $lastname ), 'Last Name must keep its Visibility/Requirement/Type controls (PROD-10439).' );

		// Move protection is a different concept and must keep the superset.
		$this->assertTrue( $this->invoke( 'bb_is_default_field', $lastname ), 'Last Name must remain move-locked under first_last_name.' );
	}

	/**
	 * Under the nickname format only Nickname is settings-locked — First and Last
	 * Name keep every control (legacy parity).
	 */
	public function test_only_nickname_settings_locked_under_nickname_format() {
		$group_id  = self::factory()->xprofile_group->create();
		$nickname  = $this->create_field( $group_id );
		$firstname = $this->create_field( $group_id );
		$lastname  = $this->create_field( $group_id );

		bp_update_option( 'bp-display-name-format', 'nickname' );
		bp_update_option( 'bp-xprofile-nickname-field-id', $nickname );
		bp_update_option( 'bp-xprofile-firstname-field-id', $firstname );
		bp_update_option( 'bp-xprofile-lastname-field-id', $lastname );

		$this->assertTrue( $this->invoke( 'bb_is_settings_locked_field', $nickname ) );
		$this->assertFalse( $this->invoke( 'bb_is_settings_locked_field', $firstname ) );
		$this->assertFalse( $this->invoke( 'bb_is_settings_locked_field', $lastname ) );
	}

	/**
	 * Under the first_name format Nickname and First Name are settings-locked;
	 * Last Name keeps every control (covers the `first_name` arm of the helper).
	 */
	public function test_first_and_nickname_settings_locked_under_first_name_format() {
		$group_id  = self::factory()->xprofile_group->create();
		$nickname  = $this->create_field( $group_id );
		$firstname = $this->create_field( $group_id );
		$lastname  = $this->create_field( $group_id );

		bp_update_option( 'bp-display-name-format', 'first_name' );
		bp_update_option( 'bp-xprofile-nickname-field-id', $nickname );
		bp_update_option( 'bp-xprofile-firstname-field-id', $firstname );
		bp_update_option( 'bp-xprofile-lastname-field-id', $lastname );

		$this->assertTrue( $this->invoke( 'bb_is_settings_locked_field', $nickname ) );
		$this->assertTrue( $this->invoke( 'bb_is_settings_locked_field', $firstname ) );
		$this->assertFalse( $this->invoke( 'bb_is_settings_locked_field', $lastname ) );
	}

	/**
	 * Profile Types selector visibility mirrors legacy member_type_metabox():
	 * hidden for the primary field (ID 1) and undeletable fields, shown for
	 * every other field.
	 */
	public function test_hide_member_types_matches_legacy_metabox_rule() {
		$group_id = self::factory()->xprofile_group->create();

		$regular = xprofile_get_field( $this->create_field( $group_id ) );
		$this->assertFalse( $this->invoke( 'bb_hide_member_types_for_field', $regular ) );

		// Undeletable field (e.g. First Name / Nickname on a default install).
		$undeletable             = xprofile_get_field( $this->create_field( $group_id ) );
		$undeletable->can_delete = 0;
		$this->assertTrue( $this->invoke( 'bb_hide_member_types_for_field', $undeletable ) );

		// The primary field (ID 1) never offers Profile Types. The helper reads
		// only id/can_delete, so an in-memory stub exercises the branch without
		// depending on the install's fixture data.
		$primary             = xprofile_get_field( $this->create_field( $group_id ) );
		$primary->id         = 1;
		$primary->can_delete = 1;
		$this->assertTrue( $this->invoke( 'bb_hide_member_types_for_field', $primary ) );
	}

	/**
	 * The Profile Type field itself cannot be restricted by profile types
	 * (third branch of the helper).
	 *
	 * set_up() creates a membertypes field before every test, so the getter's
	 * process-wide static memo holds a real field ID: cold memo → this test's
	 * own fixture is asserted directly; warm memo (an earlier test primed it) →
	 * an in-memory stub pinned to the memoized ID keeps the branch exercised.
	 * A memo of 0 (only possible when code outside this class primed it before
	 * any set_up() ran) makes the branch unreachable in the process — skip.
	 */
	public function test_hide_member_types_for_the_profile_type_field() {
		$this->assertInstanceOf( 'BP_XProfile_Field', $this->member_type_field );

		$memo_id = (int) bp_get_xprofile_member_type_field_id();

		if ( 0 === $memo_id ) {
			$this->markTestSkipped( 'bp_get_xprofile_member_type_field_id() static memo was primed to 0 before this class ran; the branch is unreachable in this process.' );
		}

		if ( $memo_id === (int) $this->member_type_field->id ) {
			$this->assertTrue( $this->invoke( 'bb_hide_member_types_for_field', $this->member_type_field ) );
		} else {
			$group_id         = self::factory()->xprofile_group->create();
			$stub             = xprofile_get_field( $this->create_field( $group_id ) );
			$stub->id         = $memo_id;
			$stub->can_delete = 1;
			$this->assertTrue( $this->invoke( 'bb_hide_member_types_for_field', $stub ) );
		}
	}

	/**
	 * The AJAX field payload must carry both new flags alongside the unchanged
	 * is_default_field superset, so the React modal can gate sections per legacy.
	 */
	public function test_field_payload_carries_lock_flags() {
		$group_id = self::factory()->xprofile_group->create();
		$lastname = $this->create_field( $group_id );

		bp_update_option( 'bp-display-name-format', 'first_last_name' );
		bp_update_option( 'bp-xprofile-lastname-field-id', $lastname );

		$payload = $this->invoke( 'bb_format_field', xprofile_get_field( $lastname ) );

		$this->assertArrayHasKey( 'is_settings_locked', $payload );
		$this->assertArrayHasKey( 'hide_member_types', $payload );
		$this->assertTrue( $payload['is_default_field'] );
		$this->assertFalse( $payload['is_settings_locked'] );
		$this->assertFalse( $payload['hide_member_types'] );
	}
}
