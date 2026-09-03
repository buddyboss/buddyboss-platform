<?php

/**
 * @group members
 * @group signups
 * @group BP_Signup
 */
class BP_Tests_BP_Signup extends BP_UnitTestCase {
	protected $signup_allowed;

	public function setUp(): void {

		if ( is_multisite() ) {
			$this->signup_allowed = get_site_option( 'registration' );
			update_site_option( 'registration', 'all' );
		} else {
			bp_get_option( 'users_can_register' );
			bp_update_option( 'users_can_register', 1 );
		}

		parent::setUp();
	}

	public function tearDown(): void {
		if ( is_multisite() ) {
			update_site_option( 'registration', $this->signup_allowed );
		} else {
			bp_update_option( 'users_can_register', $this->signup_allowed );
		}

		parent::tearDown();
	}

	/**
	 * @group add
	 */
	public function test_add() {
		$time = bp_core_current_time();
		$args = array(
			'domain' => 'foo',
			'path' => 'bar',
			'title' => 'Foo bar',
			'user_login' => 'user1',
			'user_email' => 'user1@example.com',
			'registered' => $time,
			'activation_key' => '12345',
			'meta' => array(
				'field_1' => 'Foo Bar',
				'meta1' => 'meta2',
			),
		);

		$signup = BP_Signup::add( $args );
		$this->assertNotEmpty( $signup );

		$s = new BP_Signup( $signup );

		// spot check
		$this->assertSame( $signup, $s->id );
		$this->assertSame( 'user1', $s->user_login );
		$this->assertSame( '12345', $s->activation_key );
	}

	/**
	 * @group add
	 */
	public function test_add_no_visibility_level_set_should_use_default_visiblity_level() {
		// Update field_1's default visiblity to 'adminsonly'
		bp_xprofile_update_field_meta( 1, 'default_visibility', 'adminsonly' );

		// Add new signup without a custom field visibility set for field_1.
		$signup = BP_Signup::add( array(
			'title' => 'Foo bar',
			'user_login' => 'user1',
			'user_email' => 'user1@example.com',
			'registered' => bp_core_current_time(),
			'activation_key' => '12345',
			'meta' => array(
				'field_1' => 'Foo Bar',
				'meta1' => 'meta2',
				'password' => 'password',

				/*
				 * Ensure we pass the field ID.
				 *
				 * See bp_core_activate_signup() and BP_Signup::add_backcompat().
				 */
				'profile_field_ids' => '1'
			),
		) );

		// Activate the signup.
		$activate = BP_Signup::activate( (array) $signup );

		// Assert that field 1's visibility for the signup is still 'adminsonly'
		$vis = xprofile_get_field_visibility_level( 1, $activate['activated'][0] );
		$this->assertSame( 'adminsonly', $vis );
	}

	/**
	 * A field with "Enforce field visibility" must ignore a member-chosen level
	 * supplied in the registration payload and store the admin default instead.
	 *
	 * @group signup_visibility
	 */
	public function test_activate_enforced_field_ignores_crafted_visibility() {
		$field_id = self::factory()->xprofile_field->create( array( 'field_group_id' => 1 ) );
		bp_xprofile_update_field_meta( $field_id, 'default_visibility', 'loggedin' );
		bp_xprofile_update_field_meta( $field_id, 'allow_custom_visibility', 'disabled' );

		$user_id = $this->activate_signup_with_visibility( $field_id, 'public' );

		// The default is written; the crafted 'public' must not be.
		$levels = bp_get_user_meta( $user_id, 'bp_xprofile_visibility_levels', true );
		$this->assertSame( 'loggedin', isset( $levels[ $field_id ] ) ? $levels[ $field_id ] : null );
		$this->assertNotSame( 'public', isset( $levels[ $field_id ] ) ? $levels[ $field_id ] : null );
	}

	/**
	 * The nickname field is always locked by the display-name rules, so a crafted
	 * visibility in the registration payload must be ignored at activation - even
	 * though it is not flagged "Enforce field visibility". This is the case the
	 * previous bp_core_hide_display_name_field() check missed (it never locks nickname).
	 *
	 * @group signup_visibility
	 */
	public function test_activate_nickname_field_ignores_crafted_visibility() {
		$field_id = self::factory()->xprofile_field->create( array( 'field_group_id' => 1 ) );
		bp_xprofile_update_field_meta( $field_id, 'default_visibility', 'loggedin' );
		bp_xprofile_update_field_meta( $field_id, 'allow_custom_visibility', 'allowed' );

		$nick_backup = bp_get_option( 'bp-xprofile-nickname-field-id' );
		bp_update_option( 'bp-xprofile-nickname-field-id', $field_id );

		$user_id = $this->activate_signup_with_visibility( $field_id, 'public' );

		// allow_custom_visibility is 'allowed', so the getter returns the stored value;
		// after the fix that must be the default, not the crafted 'public'.
		$this->assertSame( 'loggedin', xprofile_get_field_visibility_level( $field_id, $user_id ) );

		bp_update_option( 'bp-xprofile-nickname-field-id', $nick_backup );
	}

	/**
	 * With Display Name Format = First Name, the first-name field is locked, so a
	 * crafted visibility must be ignored at activation. The previous
	 * bp_core_hide_display_name_field() check only locked first-name for the
	 * nickname format, so this case slipped through.
	 *
	 * @group signup_visibility
	 */
	public function test_activate_firstname_field_ignores_crafted_visibility_for_first_name_format() {
		$field_id = self::factory()->xprofile_field->create( array( 'field_group_id' => 1 ) );
		bp_xprofile_update_field_meta( $field_id, 'default_visibility', 'loggedin' );
		bp_xprofile_update_field_meta( $field_id, 'allow_custom_visibility', 'allowed' );

		$first_backup = bp_get_option( 'bp-xprofile-firstname-field-id' );
		$fmt_backup   = bp_get_option( 'bp-display-name-format' );
		bp_update_option( 'bp-xprofile-firstname-field-id', $field_id );
		bp_update_option( 'bp-display-name-format', 'first_name' );

		$user_id = $this->activate_signup_with_visibility( $field_id, 'public' );

		$this->assertSame( 'loggedin', xprofile_get_field_visibility_level( $field_id, $user_id ) );

		bp_update_option( 'bp-xprofile-firstname-field-id', $first_backup );
		bp_update_option( 'bp-display-name-format', $fmt_backup );
	}

	/**
	 * A normal, member-editable field must still honor the member's chosen
	 * visibility from the registration payload (regression guard - the fix must
	 * not over-lock allowed fields).
	 *
	 * @group signup_visibility
	 */
	public function test_activate_allowed_field_honors_member_visibility() {
		$field_id = self::factory()->xprofile_field->create( array( 'field_group_id' => 1 ) );
		bp_xprofile_update_field_meta( $field_id, 'default_visibility', 'loggedin' );
		bp_xprofile_update_field_meta( $field_id, 'allow_custom_visibility', 'allowed' );

		$user_id = $this->activate_signup_with_visibility( $field_id, 'adminsonly' );

		$this->assertSame( 'adminsonly', xprofile_get_field_visibility_level( $field_id, $user_id ) );
	}

	/**
	 * BP_Signup::add_backcompat() carries its own copy of the visibility block and runs at
	 * REGISTRATION time on a default single-site install (bp_core_signup_user() calls it
	 * unless BP_SIGNUPS_SKIP_USER_CREATION is set). The activation tests above go through
	 * bp_core_activate_signup(), so this copy needs its own coverage.
	 *
	 * @group signup_visibility
	 */
	public function test_add_backcompat_enforced_field_ignores_crafted_visibility() {
		if ( is_multisite() ) {
			$this->markTestSkipped( 'add_backcompat() is only reached on single-site installs.' );
		}

		$field_id = self::factory()->xprofile_field->create( array( 'field_group_id' => 1 ) );
		bp_xprofile_update_field_meta( $field_id, 'default_visibility', 'loggedin' );
		bp_xprofile_update_field_meta( $field_id, 'allow_custom_visibility', 'disabled' );

		$user_id = $this->signup_backcompat_with_visibility( $field_id, 'public' );

		$levels = bp_get_user_meta( $user_id, 'bp_xprofile_visibility_levels', true );
		$this->assertSame( 'loggedin', isset( $levels[ $field_id ] ) ? $levels[ $field_id ] : null );
		$this->assertNotSame( 'public', isset( $levels[ $field_id ] ) ? $levels[ $field_id ] : null );
	}

	/**
	 * The nickname field is locked by the display-name rules even though overrides are
	 * "allowed", so add_backcompat() must ignore a crafted level for it too.
	 *
	 * @group signup_visibility
	 */
	public function test_add_backcompat_nickname_field_ignores_crafted_visibility() {
		if ( is_multisite() ) {
			$this->markTestSkipped( 'add_backcompat() is only reached on single-site installs.' );
		}

		$field_id = self::factory()->xprofile_field->create( array( 'field_group_id' => 1 ) );
		bp_xprofile_update_field_meta( $field_id, 'default_visibility', 'loggedin' );
		bp_xprofile_update_field_meta( $field_id, 'allow_custom_visibility', 'allowed' );

		$nick_backup = bp_get_option( 'bp-xprofile-nickname-field-id' );
		bp_update_option( 'bp-xprofile-nickname-field-id', $field_id );

		$user_id = $this->signup_backcompat_with_visibility( $field_id, 'public' );

		// allow_custom_visibility is 'allowed', so the getter returns the stored value -
		// it must be the admin default, not the crafted 'public'.
		$this->assertSame( 'loggedin', xprofile_get_field_visibility_level( $field_id, $user_id ) );

		bp_update_option( 'bp-xprofile-nickname-field-id', $nick_backup );
	}

	/**
	 * Regression guard: add_backcompat() must not over-lock a field the member may change.
	 *
	 * @group signup_visibility
	 */
	public function test_add_backcompat_allowed_field_honors_member_visibility() {
		if ( is_multisite() ) {
			$this->markTestSkipped( 'add_backcompat() is only reached on single-site installs.' );
		}

		$field_id = self::factory()->xprofile_field->create( array( 'field_group_id' => 1 ) );
		bp_xprofile_update_field_meta( $field_id, 'default_visibility', 'loggedin' );
		bp_xprofile_update_field_meta( $field_id, 'allow_custom_visibility', 'allowed' );

		$user_id = $this->signup_backcompat_with_visibility( $field_id, 'adminsonly' );

		$this->assertSame( 'adminsonly', xprofile_get_field_visibility_level( $field_id, $user_id ) );
	}

	/**
	 * Register through BP_Signup::add_backcompat() with a chosen visibility for one field
	 * and return the created user ID.
	 *
	 * @param int    $field_id   Profile field ID.
	 * @param string $visibility Visibility level supplied in the registration payload.
	 *
	 * @return int Created user ID.
	 */
	protected function signup_backcompat_with_visibility( $field_id, $visibility ) {
		static $i = 0;
		++$i;

		$user_id = BP_Signup::add_backcompat(
			'bcvis_user_' . $i,
			'password',
			'bcvis_user_' . $i . '@example.com',
			array(
				'field_' . $field_id                => 'Foo Bar',
				'field_' . $field_id . '_visibility' => $visibility,
				'profile_field_ids'                 => (string) $field_id,
			)
		);

		$this->assertNotWPError( $user_id, 'The fixture signup could not be created.' );

		return $user_id;
	}

	/**
	 * Create a signup that supplies a chosen visibility for one field, activate it,
	 * and return the activated user ID.
	 *
	 * @param int    $field_id   Profile field ID.
	 * @param string $visibility Visibility level supplied in the registration payload.
	 *
	 * @return int Activated user ID.
	 */
	protected function activate_signup_with_visibility( $field_id, $visibility ) {
		static $i = 0;
		++$i;

		$field_key            = 'field_' . $field_id;
		$field_visibility_key = $field_key . '_visibility';

		$signup = BP_Signup::add(
			array(
				'title'          => 'Foo bar',
				'user_login'     => 'vis_user_' . $i,
				'user_email'     => 'vis_user_' . $i . '@example.com',
				'registered'     => bp_core_current_time(),
				'activation_key' => wp_generate_password( 32, false ),
				'meta'           => array(
					$field_key            => 'Foo Bar',
					$field_visibility_key => $visibility,
					'password'            => 'password',
					'profile_field_ids'   => (string) $field_id,
				),
			)
		);

		$activate = BP_Signup::activate( (array) $signup );

		return $activate['activated'][0];
	}

	/**
	 * @group get
	 */
	public function test_get_with_offset() {
		$s1 = self::factory()->signup->create();
		$s2 = self::factory()->signup->create();
		$s3 = self::factory()->signup->create();

		$ss = BP_Signup::get( array(
			'offset' => 1,
			'fields' => 'ids',
		) );

		$this->assertEquals( array( $s2 ), $ss['signups'] );
	}

	/**
	 * @group get
	 */
	public function test_get_with_number() {
		$s1 = self::factory()->signup->create();
		$s2 = self::factory()->signup->create();
		$s3 = self::factory()->signup->create();

		$ss = BP_Signup::get( array(
			'number' => 2,
			'fields' => 'ids',
		) );

		$this->assertEquals( array( $s3, $s2 ), $ss['signups'] );
	}

	/**
	 * @group get
	 */
	public function test_get_with_usersearch() {
		$s1 = self::factory()->signup->create( array(
			'user_email' => 'fghij@example.com',
		) );
		$s2 = self::factory()->signup->create();
		$s3 = self::factory()->signup->create();

		$ss = BP_Signup::get( array(
			'usersearch' => 'ghi',
			'fields' => 'ids',
		) );

		$this->assertEquals( array( $s1 ), $ss['signups'] );
	}

	/**
	 * @group get
	 */
	public function test_get_with_orderby_email() {
		$s1 = self::factory()->signup->create( array(
			'user_email' => 'fghij@example.com',
		) );
		$s2 = self::factory()->signup->create( array(
			'user_email' => 'abcde@example.com',
		) );
		$s3 = self::factory()->signup->create( array(
			'user_email' => 'zzzzz@example.com',
		) );

		$ss = BP_Signup::get( array(
			'orderby' => 'email',
			'number' => 3,
			'fields' => 'ids',
		) );

		// default order is DESC.
		$this->assertEquals( array( $s3, $s1, $s2 ), $ss['signups'] );
	}

	/**
	 * @group get
	 */
	public function test_get_with_orderby_email_asc() {
		$s1 = self::factory()->signup->create( array(
			'user_email' => 'fghij@example.com',
		) );
		$s2 = self::factory()->signup->create( array(
			'user_email' => 'abcde@example.com',
		) );
		$s3 = self::factory()->signup->create( array(
			'user_email' => 'zzzzz@example.com',
		) );

		$ss = BP_Signup::get( array(
			'orderby' => 'email',
			'number' => 3,
			'order' => 'ASC',
			'fields' => 'ids',
		) );

		$this->assertEquals( array( $s2, $s1, $s3 ), $ss['signups'] );
	}

	/**
	 * @group get
	 */
	public function test_get_with_include() {
		$s1 = self::factory()->signup->create();
		$s2 = self::factory()->signup->create();
		$s3 = self::factory()->signup->create();

		$ss = BP_Signup::get( array(
			'include' => array( $s1, $s3 ),
			'fields' => 'ids',
		) );

		$this->assertEquals( array( $s1, $s3 ), $ss['signups'] );
	}

	/**
	 * @group get
	 */
	public function test_get_with_activation_key() {
		$s1 = self::factory()->signup->create( array(
			'activation_key' => 'foo',
		) );
		$s2 = self::factory()->signup->create( array(
			'activation_key' => 'bar',
		) );
		$s3 = self::factory()->signup->create( array(
			'activation_key' => 'baz',
		) );

		$ss = BP_Signup::get( array(
			'activation_key' => 'bar',
			'fields' => 'ids',
		) );

		$this->assertEquals( array( $s2 ), $ss['signups'] );
	}

	/**
	 * @group get
	 */
	public function test_get_with_user_login() {
		$s1 = self::factory()->signup->create( array(
			'user_login' => 'aaaafoo',
		) );
		$s2 = self::factory()->signup->create( array(
			'user_login' => 'zzzzfoo',
		) );
		$s3 = self::factory()->signup->create( array(
			'user_login' => 'jjjjfoo',
		) );

		$ss = BP_Signup::get( array(
			'user_login' => 'zzzzfoo',
			'fields' => 'ids',
		) );

		$this->assertEquals( array( $s2 ), $ss['signups'] );
	}

	/**
	 * @group activate
	 */
	public function test_activate_user_accounts() {
		$signups = array();

		$signups['accountone'] = self::factory()->signup->create( array(
			'user_login'     => 'accountone',
			'user_email'     => 'accountone@example.com',
			'activation_key' => 'activationkeyone',
		) );

		$signups['accounttwo'] = self::factory()->signup->create( array(
			'user_login'     => 'accounttwo',
			'user_email'     => 'accounttwo@example.com',
			'activation_key' => 'activationkeytwo',
		) );

		$signups['accountthree'] = self::factory()->signup->create( array(
			'user_login'     => 'accountthree',
			'user_email'     => 'accountthree@example.com',
			'activation_key' => 'activationkeythree',
		) );

		$results = BP_Signup::activate( $signups );
		$this->assertNotEmpty( $results['activated'] );

		$users = array();

		foreach ( $signups as $login => $signup_id  ) {
			$users[ $login ] = get_user_by( 'login', $login );
		}

		$this->assertEqualSets( $results['activated'], wp_list_pluck( $users, 'ID' ) );
	}

	/**
	 * @group get
	 */
	public function test_get_signup_ids_only() {
		$s1 = self::factory()->signup->create();
		$s2 = self::factory()->signup->create();
		$s3 = self::factory()->signup->create();

		$ss = BP_Signup::get( array(
			'number' => 3,
			'fields' => 'ids',
		) );

		$this->assertEquals( array( $s3, $s2, $s1 ), $ss['signups'] );
	}
}
