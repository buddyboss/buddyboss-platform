<?php

/**
 * A member who renames themself must stop matching their old name, on every path and format.
 *
 * Every request resolves the logged-in member's name at `bp_setup_globals`
 * (xprofile_override_user_fullnames()) before the profile form is processed, and the two name
 * resolvers keep that answer in per-request static caches. The save's display_name sync used to
 * read those caches and write the PREVIOUS name back, so @mention search - which matches
 * `wp_users.display_name` - kept suggesting the member for the name they had given up.
 *
 * @group xprofile
 * @group bb_xprofile_member_display_name_cache
 * @ticket PROD-10542
 */
class BP_Tests_XProfile_DisplayNameSync extends BP_UnitTestCase {

	/**
	 * Display Name Format in effect before the test.
	 *
	 * @var string
	 */
	private $format_backup;

	public function setUp(): void {
		parent::setUp();

		$this->format_backup = bp_get_option( 'bp-display-name-format' );
		bp_update_option( 'bp-display-name-format', 'first_last_name' );
		$GLOBALS['bb_default_display_avatar'] = false;
	}

	public function tearDown(): void {
		bp_update_option( 'bp-display-name-format', $this->format_backup );
		$GLOBALS['bb_default_display_avatar'] = false;

		parent::tearDown();
	}

	/**
	 * Create a member named Harry Lime (nickname harrylime) whose WordPress and xprofile names agree.
	 *
	 * @return int User ID.
	 */
	private function create_harry() {
		$u = self::factory()->user->create();

		// `profile_update` copies the WordPress names into the xprofile name fields.
		wp_update_user(
			array(
				'ID'           => $u,
				'first_name'   => 'Harry',
				'last_name'    => 'Lime',
				'nickname'     => 'harrylime',
				'display_name' => 'Harry Lime',
			)
		);

		return $u;
	}

	/**
	 * Resolve the member's name the way the start of every request does (bp_setup_globals).
	 *
	 * @param int $user_id Member logged in for this request.
	 *
	 * @return string The resolved name.
	 */
	private function start_request_as( $user_id ) {
		$this->set_current_user( $user_id );
		$GLOBALS['bb_default_display_avatar'] = false;

		return bp_core_get_user_displayname( $user_id );
	}

	/**
	 * Save name fields as the profile form does: every field is written, then
	 * `xprofile_updated_profile` runs the WordPress sync.
	 *
	 * @param int   $user_id Member.
	 * @param array $values  Field ID => value.
	 */
	private function save_profile_form( $user_id, $values ) {
		$new_values = array();

		foreach ( $values as $field_id => $value ) {
			xprofile_set_field_data( $field_id, $user_id, $value );
			$new_values[ $field_id ] = array( 'value' => $value );
		}

		// An unchanged name field saved last leaves the default-avatar flag - an incidental cache
		// bypass - off, exactly as the real form does; assert it so the test cannot pass by accident.
		$this->assertFalse( $GLOBALS['bb_default_display_avatar'], 'Fixture must not bypass the name cache through the avatar flag.' );

		do_action( 'xprofile_updated_profile', $user_id, array_keys( $values ), false, array(), $new_values );
		clean_user_cache( $user_id );
	}

	/**
	 * User IDs suggested for an @mention term, as seen by the given member.
	 *
	 * @param string $term      Text typed after "@".
	 * @param int    $viewer_id Member who is typing.
	 *
	 * @return int[]
	 */
	private function mention_suggestion_ids( $term, $viewer_id ) {
		$this->set_current_user( $viewer_id );

		$results = bp_core_get_suggestions(
			array(
				'term' => $term,
				'type' => 'members',
			)
		);

		$this->assertIsArray( $results, 'bp_core_get_suggestions() returned an error.' );

		return array_map( 'intval', wp_list_pluck( $results, 'user_id' ) );
	}

	/**
	 * The ticket's steps: First Name Harry -> Donald and Nickname harrylime -> donaldlime.
	 */
	public function test_rename_from_ticket_writes_new_display_name_and_old_name_stops_matching() {
		$u      = $this->create_harry();
		$viewer = self::factory()->user->create();

		$this->assertContains( $u, $this->mention_suggestion_ids( 'Harry', $viewer ), 'Baseline: the old name matches before the rename.' );

		$this->assertSame( 'Harry Lime', $this->start_request_as( $u ) );
		$this->save_profile_form(
			$u,
			array(
				bp_xprofile_firstname_field_id() => 'Donald',
				bp_xprofile_lastname_field_id()  => 'Lime',
				bp_xprofile_nickname_field_id()  => 'donaldlime',
			)
		);

		$this->assertSame( 'Donald Lime', get_userdata( $u )->display_name );
		$this->assertSame( 'Donald', get_user_meta( $u, 'first_name', true ) );
		$this->assertSame( 'donaldlime', get_user_meta( $u, 'nickname', true ) );

		// Expected behaviour 1: "@Harry" no longer suggests the member.
		$this->assertNotContains( $u, $this->mention_suggestion_ids( 'Harry', $viewer ) );

		// Expected behaviour 2: the current name parts still match.
		$this->assertContains( $u, $this->mention_suggestion_ids( 'Donald', $viewer ) );
		$this->assertContains( $u, $this->mention_suggestion_ids( 'Lime', $viewer ) );
		$this->assertContains( $u, $this->mention_suggestion_ids( 'donaldlime', $viewer ) );
	}

	/**
	 * Changing only the last name, keeping its first letter, never touched the avatar-flag bypass.
	 */
	public function test_last_name_only_change_with_same_initial_is_written() {
		$u      = $this->create_harry();
		$viewer = self::factory()->user->create();

		$this->start_request_as( $u );
		$this->save_profile_form(
			$u,
			array(
				bp_xprofile_firstname_field_id() => 'Harry',
				bp_xprofile_lastname_field_id()  => 'Lyme',
			)
		);

		$this->assertSame( 'Harry Lyme', get_userdata( $u )->display_name );
		$this->assertNotContains( $u, $this->mention_suggestion_ids( 'Lime', $viewer ) );
		$this->assertContains( $u, $this->mention_suggestion_ids( 'Lyme', $viewer ) );
	}

	/**
	 * Release wrote the previous name, so the stored name was always one save behind.
	 */
	public function test_consecutive_renames_in_one_request_store_the_current_name() {
		$u = $this->create_harry();

		$this->start_request_as( $u );
		$this->save_profile_form( $u, array( bp_xprofile_firstname_field_id() => 'Donald', bp_xprofile_lastname_field_id() => 'Lime' ) );
		$this->assertSame( 'Donald Lime', get_userdata( $u )->display_name );

		$this->save_profile_form( $u, array( bp_xprofile_firstname_field_id() => 'Daniel', bp_xprofile_lastname_field_id() => 'Lime' ) );
		$this->assertSame( 'Daniel Lime', get_userdata( $u )->display_name );
	}

	/**
	 * Display Name Format "First Name".
	 *
	 * The new name keeps the old initial: under this format the default-avatar flag watches only the
	 * First Name, so a new initial would switch the incidental cache bypass on and hide the bug.
	 */
	public function test_first_name_format_writes_new_first_name() {
		bp_update_option( 'bp-display-name-format', 'first_name' );
		$u      = $this->create_harry();
		$viewer = self::factory()->user->create();

		$this->assertSame( 'Harry', $this->start_request_as( $u ) );
		// The nickname changes too, as in the ticket: a nickname still starting with "harry" would
		// legitimately keep matching "@Harry" because it is part of the member's current name.
		$this->save_profile_form(
			$u,
			array(
				bp_xprofile_firstname_field_id() => 'Henry',
				bp_xprofile_nickname_field_id()  => 'henrylime',
			)
		);

		$this->assertSame( 'Henry', get_userdata( $u )->display_name );
		$this->assertNotContains( $u, $this->mention_suggestion_ids( 'Harry', $viewer ) );
		$this->assertContains( $u, $this->mention_suggestion_ids( 'Henry', $viewer ) );
	}

	/**
	 * Display Name Format "Nickname" (same initial for the same reason as the First Name format).
	 */
	public function test_nickname_format_writes_new_nickname() {
		bp_update_option( 'bp-display-name-format', 'nickname' );
		$u      = $this->create_harry();
		$viewer = self::factory()->user->create();

		$this->assertSame( 'harrylime', $this->start_request_as( $u ) );
		$this->save_profile_form( $u, array( bp_xprofile_nickname_field_id() => 'hlime' ) );

		$this->assertSame( 'hlime', get_userdata( $u )->display_name );
		$this->assertNotContains( $u, $this->mention_suggestion_ids( 'harrylime', $viewer ) );
		$this->assertContains( $u, $this->mention_suggestion_ids( 'hlime', $viewer ) );
	}

	/**
	 * A last name hidden from logged-out visitors stays hidden after a rename in the same request
	 * (PROD-9896 behaviour), while the member's own view and the stored name are the full new name.
	 */
	public function test_hidden_last_name_stays_hidden_for_guests_after_rename() {
		$u = $this->create_harry();
		xprofile_set_field_visibility_level( bp_xprofile_lastname_field_id(), $u, 'loggedin' );

		$this->start_request_as( $u );
		$this->assertSame( 'Harry', bp_core_get_user_displayname( $u, bb_core_guest_viewer_id() ), 'Baseline: guests do not see the last name.' );

		$this->save_profile_form( $u, array( bp_xprofile_firstname_field_id() => 'Donald', bp_xprofile_lastname_field_id() => 'Lime' ) );

		$this->assertSame( 'Donald Lime', get_userdata( $u )->display_name );
		$this->assertSame( 'Donald Lime', bp_core_get_user_displayname( $u, $u ) );
		$this->assertSame( 'Donald', bp_core_get_user_displayname( $u, bb_core_guest_viewer_id() ) );
	}

	/**
	 * Saving one member's profile must not disturb another member's cached name.
	 */
	public function test_version_advances_only_for_the_member_whose_data_changed() {
		$u     = $this->create_harry();
		$other = self::factory()->user->create();

		$u_before     = bb_xprofile_member_display_name_cache_version( $u );
		$other_before = bb_xprofile_member_display_name_cache_version( $other );

		xprofile_set_field_data( bp_xprofile_firstname_field_id(), $u, 'Donald' );

		$this->assertGreaterThan( $u_before, bb_xprofile_member_display_name_cache_version( $u ) );
		$this->assertSame( $other_before, bb_xprofile_member_display_name_cache_version( $other ) );
	}
}
