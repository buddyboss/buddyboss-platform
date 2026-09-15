<?php
/**
 * Regressions found while reviewing the name-visibility work.
 *
 * Both cases below passed the suite that shipped with that work, because nothing in it exercised
 * them: the first needs a restricted field that is NOT a name field, and the second needs a match
 * set larger than the search candidate budget. Each test here is paired with a control asserting
 * that the redaction it guards against over-reach still fires, so neither an absent filter nor an
 * over-broad one can pass.
 *
 * @group xprofile
 * @group bb_search_visibility
 * @group bb_name_visibility_regressions
 */
class BP_Tests_XProfile_NameVisibilityRegressions extends BP_UnitTestCase {

	/**
	 * Display Name Format backup.
	 *
	 * @var string
	 */
	protected $format_backup;

	/**
	 * Last Name field allow-custom-visibility backup.
	 *
	 * @var string
	 */
	protected $ln_allow_backup;

	/**
	 * Last Name field default-visibility backup.
	 *
	 * @var string
	 */
	protected $ln_default_backup;

	/**
	 * Pin the "First Name & Last Name" format: under the harness default ("First Name") the surname
	 * is not part of anybody's visible name, so the rebuild would be decided by the format rather
	 * than by the field visibility these tests are about. Custom visibility is enabled on the Last
	 * Name field for the same reason - where it is 'disabled' the admin default overrides every
	 * per-member level and the assertions would be vacuous.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->format_backup   = bp_get_option( 'bp-display-name-format' );
		$this->ln_allow_backup   = bp_xprofile_get_meta( bp_xprofile_lastname_field_id(), 'field', 'allow_custom_visibility' );
		$this->ln_default_backup = bp_xprofile_get_meta( bp_xprofile_lastname_field_id(), 'field', 'default_visibility' );

		bp_update_option( 'bp-display-name-format', 'first_last_name' );
		bp_xprofile_update_meta( bp_xprofile_lastname_field_id(), 'field', 'allow_custom_visibility', 'allowed' );

		$this->flush_visibility_caches();
	}

	/**
	 * Restore what the harness started with.
	 */
	public function tearDown(): void {
		bp_update_option( 'bp-display-name-format', $this->format_backup );
		bp_xprofile_update_meta( bp_xprofile_lastname_field_id(), 'field', 'allow_custom_visibility', $this->ln_allow_backup );
		bp_xprofile_update_meta( bp_xprofile_lastname_field_id(), 'field', 'default_visibility', $this->ln_default_backup );

		$this->flush_visibility_caches();

		parent::tearDown();
	}

	/**
	 * Drop every memo that visibility answers are served from.
	 */
	protected function flush_visibility_caches() {
		wp_cache_delete( 'default_visibility_levels', 'bp_xprofile' );

		if ( class_exists( 'BB_XProfile_Visibility' ) ) {
			BB_XProfile_Visibility::flush_field_ids_cache();
		}
	}

	/**
	 * A member whose name parts are all public, whose stored display_name has drifted away from
	 * "First Last".
	 *
	 * The drift is the point: it is what an import, a third-party write or the wp-admin "Display
	 * name publicly as" dropdown leaves behind, and it is the only state in which rebuilding the
	 * name from the fields produces something DIFFERENT from the stored column.
	 *
	 * @param string $first_name   First name to store.
	 * @param string $surname      Surname to store.
	 * @param string $display_name Drifted display name to store.
	 * @return int
	 */
	protected function create_member_with_drifted_display_name( $first_name, $surname, $display_name ) {
		$user_id = self::factory()->user->create();

		wp_update_user(
			array(
				'ID'         => $user_id,
				'first_name' => $first_name,
				'last_name'  => $surname,
			)
		);

		xprofile_set_field_data( bp_xprofile_firstname_field_id(), $user_id, $first_name );
		xprofile_set_field_data( bp_xprofile_lastname_field_id(), $user_id, $surname );
		xprofile_set_field_visibility_level( bp_xprofile_firstname_field_id(), $user_id, 'public' );
		xprofile_set_field_visibility_level( bp_xprofile_lastname_field_id(), $user_id, 'public' );

		// Last, because writing a name field syncs the column back to "First Last".
		wp_update_user(
			array(
				'ID'           => $user_id,
				'display_name' => $display_name,
			)
		);

		$this->flush_visibility_caches();

		return $user_id;
	}

	/**
	 * Create a profile field that is not one of the name fields.
	 *
	 * @return int
	 */
	protected function create_non_name_field() {
		$group_id = self::factory()->xprofile_group->create();

		return self::factory()->xprofile_field->create(
			array(
				'field_group_id' => $group_id,
				'type'           => 'textbox',
			)
		);
	}

	/**
	 * Restricting a field that is NOT a name field must not touch the visible name.
	 *
	 * bp_xprofile_get_hidden_fields_for_user() reports every restricted field on the profile, so
	 * gating the rebuild on that list being non-empty let one friends-only Phone or Address field
	 * discard the stored display_name for a member whose name parts are entirely public. On drifted
	 * data that did not merely rebuild the name, it DISCLOSED a surname the column withheld.
	 */
	public function test_hidden_non_name_field_does_not_rebuild_the_display_name() {
		$user_id  = $this->create_member_with_drifted_display_name( 'Corin', 'Ashdown', 'Corin A.' );
		$field_id = $this->create_non_name_field();

		xprofile_set_field_data( $field_id, $user_id, 'a private note' );
		xprofile_set_field_visibility_level( $field_id, $user_id, 'friends' );
		$this->flush_visibility_caches();

		// Precondition: the field really is hidden from a guest, so this test cannot pass simply
		// because nothing was restricted.
		$this->assertContains(
			(int) $field_id,
			array_map( 'intval', (array) bp_xprofile_get_hidden_fields_for_user( $user_id, 0 ) ),
			'precondition: the non-name field is not actually hidden from a guest'
		);

		$this->set_current_user( 0 );

		$this->assertSame(
			'Corin A.',
			bp_core_get_user_displayname( $user_id, 0 ),
			'a restricted NON-name field rebuilt the display name and disclosed the surname'
		);
	}

	/**
	 * Control for the above: restricting the Last Name field itself must still withhold it.
	 */
	public function test_hidden_last_name_field_still_withholds_the_surname() {
		$user_id = $this->create_member_with_drifted_display_name( 'Rowan', 'Bellhaven', 'Rowan B.' );

		xprofile_set_field_visibility_level( bp_xprofile_lastname_field_id(), $user_id, 'adminsonly' );
		$this->flush_visibility_caches();

		$this->set_current_user( 0 );

		$this->assertSame(
			'Rowan',
			bp_core_get_user_displayname( $user_id, 0 ),
			'an admins-only surname was not withheld from a guest'
		);
	}

	/**
	 * NOTE on the logged-in path.
	 *
	 * xprofile_filter_get_user_display_name() (priority 15) carried the identical over-broad gate and
	 * it is fixed alongside the guest path, but it is deliberately NOT asserted here. That filter has
	 * always returned the FORMAT-assembled name rather than the stored column, and under
	 * "First Name & Last Name" with both fields populated the rebuild produces the same string - so a
	 * test of it would pass whether or not the gate is fixed. Adding one would assert nothing. The
	 * change there is a consistency fix, verified by reading. Both paths read the xprofile Nickname
	 * FIELD under the Nickname format, so they agree there too; the guest-path leg that used to read
	 * the `nickname` user META instead is pinned in
	 * BP_Tests_Activity_Functions_BbActivityNamePrivacy.
	 */

	/**
	 * Force a tiny search candidate budget.
	 *
	 * @return int
	 */
	public function force_candidate_limit_of_two() {
		return 2;
	}

	/**
	 * Build the row set bb_xprofile_filter_field_search_matches() receives.
	 *
	 * @param array $user_ids Matched user IDs.
	 * @param int   $field_id Field the match came from.
	 * @return array
	 */
	protected function matched_rows_for( $user_ids, $field_id ) {
		$rows = array();

		foreach ( $user_ids as $user_id ) {
			$rows[] = (object) array(
				'user_id'  => $user_id,
				'field_id' => $field_id,
			);
		}

		return $rows;
	}

	/**
	 * The candidate budget must not drop members that no privacy rule applies to.
	 *
	 * The budget was applied before the keep-test, so everything past it was removed without ever
	 * being asked whether anything was hidden. On a real community that silently truncated member
	 * search - 600 members with a public first name returned 500 to a logged-out visitor.
	 */
	public function test_candidate_budget_does_not_drop_unrestricted_members() {
		$field_id = bp_xprofile_lastname_field_id();
		$user_ids = array();

		for ( $i = 0; $i < 5; $i++ ) {
			$user_id = self::factory()->user->create();
			xprofile_set_field_data( $field_id, $user_id, 'Wexcombe' . $i );
			xprofile_set_field_visibility_level( $field_id, $user_id, 'public' );
			$user_ids[] = $user_id;
		}

		$this->flush_visibility_caches();

		add_filter( 'bb_xprofile_user_search_visibility_candidate_limit', array( $this, 'force_candidate_limit_of_two' ) );
		$kept = bb_xprofile_filter_field_search_matches( $user_ids, $this->matched_rows_for( $user_ids, $field_id ), 0 );
		remove_filter( 'bb_xprofile_user_search_visibility_candidate_limit', array( $this, 'force_candidate_limit_of_two' ) );

		$this->assertCount( 5, $kept, 'the candidate budget dropped members with nothing restricted' );

		foreach ( $user_ids as $user_id ) {
			$this->assertContains( (int) $user_id, array_map( 'intval', $kept ) );
		}
	}

	/**
	 * Control for the above: a member whose only matching field IS hidden is still dropped, even
	 * when they sort past the budget.
	 */
	public function test_candidate_budget_still_drops_a_restricted_member() {
		$field_id = bp_xprofile_lastname_field_id();
		$user_ids = array();

		for ( $i = 0; $i < 5; $i++ ) {
			$user_id = self::factory()->user->create();
			xprofile_set_field_data( $field_id, $user_id, 'Harrowmere' . $i );
			xprofile_set_field_visibility_level( $field_id, $user_id, 'public' );
			$user_ids[] = $user_id;
		}

		// Last in the list, so the old budget would have dropped them for the wrong reason and the
		// assertion would pass without testing anything.
		$restricted = $user_ids[4];
		xprofile_set_field_visibility_level( $field_id, $restricted, 'adminsonly' );
		$this->flush_visibility_caches();

		$this->assertContains(
			(int) $field_id,
			array_map( 'intval', (array) bp_xprofile_get_hidden_fields_for_user( $restricted, 0 ) ),
			'precondition: the surname is not actually hidden from a guest'
		);

		add_filter( 'bb_xprofile_user_search_visibility_candidate_limit', array( $this, 'force_candidate_limit_of_two' ) );
		$kept = bb_xprofile_filter_field_search_matches( $user_ids, $this->matched_rows_for( $user_ids, $field_id ), 0 );
		remove_filter( 'bb_xprofile_user_search_visibility_candidate_limit', array( $this, 'force_candidate_limit_of_two' ) );

		$kept = array_map( 'intval', $kept );

		$this->assertNotContains( (int) $restricted, $kept, 'a guest kept a match on an admins-only surname' );
		$this->assertCount( 4, $kept, 'the permitted members were not all kept' );
	}

	/**
	 * A restricted field default does NOT reach a member who has their own visibility row.
	 *
	 * bp_xprofile_get_fields_by_visibility_levels() resolves such a member from the visibility table,
	 * where a field they never set is simply not hidden. Treating the default as community-wide swept
	 * fully-permitted members into the candidate budget, and whatever sorted past it was dropped.
	 */
	public function test_restricted_default_does_not_capture_members_with_their_own_row() {
		$field_id = bp_xprofile_lastname_field_id();
		$user_id  = self::factory()->user->create();

		xprofile_set_field_visibility_level( $field_id, $user_id, 'public' );

		bp_xprofile_update_meta( $field_id, 'field', 'default_visibility', 'loggedin' );
		bp_xprofile_update_meta( $field_id, 'field', 'allow_custom_visibility', 'allowed' );
		$this->flush_visibility_caches();

		$possible = bb_xprofile_filter_possible_hidden_users( array( $user_id ) );

		$this->assertIsArray( $possible, 'a restricted default with custom visibility allowed must still narrow' );
		$this->assertNotContains(
			(int) $user_id,
			array_map( 'intval', $possible ),
			'a member with their own public row was treated as inheriting the restricted default'
		);
	}

	/**
	 * ...but it DOES reach a member with no visibility rows at all, who is resolved from user meta
	 * where an unset field falls back to that default.
	 */
	public function test_restricted_default_captures_members_without_any_row() {
		$field_id = bp_xprofile_lastname_field_id();
		$user_id  = self::factory()->user->create();

		// Deliberately no visibility row and no levels meta for this member.
		delete_user_meta( $user_id, 'bp_xprofile_visibility_levels' );

		bp_xprofile_update_meta( $field_id, 'field', 'default_visibility', 'loggedin' );
		bp_xprofile_update_meta( $field_id, 'field', 'allow_custom_visibility', 'allowed' );
		$this->flush_visibility_caches();

		$possible = bb_xprofile_filter_possible_hidden_users( array( $user_id ) );

		$this->assertIsArray( $possible );
		$this->assertContains(
			(int) $user_id,
			array_map( 'intval', $possible ),
			'a member with no rows did not inherit the restricted default'
		);
	}

	/**
	 * 'disabled' custom visibility makes the admin default replace every member's own setting, on
	 * both branches - so no candidate set is smaller than the whole match set and narrowing must
	 * stand down rather than return a partial answer.
	 */
	public function test_disabled_custom_visibility_disables_narrowing() {
		$field_id = bp_xprofile_lastname_field_id();
		$user_id  = self::factory()->user->create();

		xprofile_set_field_visibility_level( $field_id, $user_id, 'public' );

		bp_xprofile_update_meta( $field_id, 'field', 'default_visibility', 'loggedin' );
		bp_xprofile_update_meta( $field_id, 'field', 'allow_custom_visibility', 'disabled' );
		$this->flush_visibility_caches();

		$this->assertNull(
			bb_xprofile_filter_possible_hidden_users( array( $user_id ) ),
			'a community-wide forced default must disable narrowing, not return a partial set'
		);
	}

	/**
	 * A member who restricts a field is picked up immediately - the narrowing reads current rows
	 * rather than a memo that could outlive the write.
	 */
	public function test_narrowing_sees_a_new_restriction_immediately() {
		$field_id = bp_xprofile_lastname_field_id();
		$user_id  = self::factory()->user->create();

		xprofile_set_field_visibility_level( $field_id, $user_id, 'public' );
		$this->flush_visibility_caches();

		$before = bb_xprofile_filter_possible_hidden_users( array( $user_id ) );
		$this->assertIsArray( $before, 'precondition: narrowing is unexpectedly disabled' );
		$this->assertNotContains( (int) $user_id, array_map( 'intval', $before ) );

		xprofile_set_field_visibility_level( $field_id, $user_id, 'adminsonly' );

		$after = bb_xprofile_filter_possible_hidden_users( array( $user_id ) );
		$this->assertIsArray( $after );
		$this->assertContains(
			(int) $user_id,
			array_map( 'intval', $after ),
			'a fresh restriction was not seen by the narrowing'
		);
	}

	/**
	 * Past the budget the profile-field leg withholds exactly the CANDIDATES, and serves every
	 * other match untouched.
	 *
	 * Its sibling, the display_name producer, is pinned the same way in
	 * BP_Tests_XProfile_SearchVisibility::test_candidate_budget_also_bounds_the_per_user_visibility_sources().
	 * One rule, two filters, and the answer past the bound has to be the same on both.
	 *
	 * Three answers are possible past the bound and only one of them is right, so the fixture has to
	 * be able to tell them apart - which is why every member here matches on a PUBLIC custom field
	 * rather than on the restricted one. The keep-test therefore keeps all five, and an unbounded run
	 * returns the whole match set: that is the control asserted first, and without it the budget rule
	 * could be deleted outright and this test would still pass. Only three of the five have restricted
	 * a name field, so they alone are candidates. Slicing would re-test two of those three and hand
	 * back all five; withholding the whole leg would hand back none; withholding the candidates hands
	 * back exactly the two members who restricted nothing.
	 */
	public function test_budget_exceeded_withholds_the_field_search_candidates_not_the_whole_leg() {
		$last_name_field_id = bp_xprofile_lastname_field_id();
		$public_field_id    = $this->create_non_name_field();

		// The Last Name default must stay public, or the members who restricted nothing become
		// candidates too and the two groups below stop being distinguishable.
		bp_xprofile_update_meta( $last_name_field_id, 'field', 'default_visibility', 'public' );

		$restricted_ids = array();
		for ( $i = 0; $i < 3; $i++ ) {
			$user_id = self::factory()->user->create();
			xprofile_set_field_data( $public_field_id, $user_id, 'Cardingley' . $i );
			xprofile_set_field_visibility_level( $last_name_field_id, $user_id, 'adminsonly' );
			$restricted_ids[] = (int) $user_id;
		}

		$unrestricted_ids = array();
		for ( $i = 0; $i < 2; $i++ ) {
			$user_id = self::factory()->user->create();
			xprofile_set_field_data( $public_field_id, $user_id, 'Cardingley' . ( 3 + $i ) );
			$unrestricted_ids[] = (int) $user_id;
		}

		$this->flush_visibility_caches();

		$user_ids = array_merge( $restricted_ids, $unrestricted_ids );
		$rows     = $this->matched_rows_for( $user_ids, $public_field_id );

		// Fixture: the narrowing is live and names exactly the three members who restricted
		// something. If it stood down, every match would be a candidate and the assertions below
		// could not tell "withhold the candidates" from "withhold the leg".
		$possible_hidden = bb_xprofile_filter_possible_hidden_users( $user_ids );
		$this->assertIsArray( $possible_hidden, 'Fixture: the narrowing stood down, so the candidate set is the whole match set.' );
		sort( $possible_hidden );
		$expected_candidates = $restricted_ids;
		sort( $expected_candidates );
		$this->assertSame( $expected_candidates, array_map( 'intval', $possible_hidden ), 'Fixture: the candidate set is not the three restricted members.' );

		// Control: under the shipped budget nothing is withheld at all, because every member matched
		// on a field that is public to this viewer.
		$unbounded = bb_xprofile_filter_field_search_matches( $user_ids, $rows, 0 );
		sort( $unbounded );
		$all_ids = $user_ids;
		sort( $all_ids );
		$this->assertSame( $all_ids, array_map( 'intval', $unbounded ), 'Fixture: an unbounded run must keep every match, or the bounded run below proves nothing.' );

		add_filter( 'bb_xprofile_user_search_visibility_candidate_limit', array( $this, 'force_candidate_limit_of_two' ) );
		$kept = bb_xprofile_filter_field_search_matches( $user_ids, $rows, 0 );
		remove_filter( 'bb_xprofile_user_search_visibility_candidate_limit', array( $this, 'force_candidate_limit_of_two' ) );

		sort( $kept );
		$expected_kept = $unrestricted_ids;
		sort( $expected_kept );

		$this->assertSame(
			$expected_kept,
			array_map( 'intval', $kept ),
			'Past the budget the profile-field leg must withhold the candidates and nothing else - not a slice of them, and not the whole match set.'
		);
	}

	/**
	 * A MISSING visibility table is the pre-migration shape, not an error.
	 *
	 * The two answers are one character apart in the callers and mean opposite things, and this is
	 * the direction that is easy to get wrong: a read that FAILED cannot be resolved and the leg is
	 * withheld, while a table that is not there yet resolves perfectly well - every member's
	 * visibility comes from the `bp_xprofile_visibility_levels` user meta, which is exactly what
	 * BB_XProfile_Visibility::user_data_exists() reports for such a site. Failing closed on the
	 * absent table would withhold member search on every install that has not migrated yet.
	 *
	 * Both directions are asserted here, against the same input, so they cannot be confused: table
	 * missing narrows normally, read failing returns false.
	 */
	public function test_a_missing_visibility_table_filters_normally_through_the_user_meta_branch() {
		global $wpdb;

		$field_id   = bp_xprofile_lastname_field_id();
		$restricted = self::factory()->user->create();
		$permitted  = self::factory()->user->create();

		xprofile_set_field_visibility_level( $field_id, $restricted, 'adminsonly' );
		xprofile_set_field_visibility_level( $field_id, $permitted, 'public' );
		$this->flush_visibility_caches();

		$levels   = array( 'friends', 'loggedin', 'adminsonly' );
		$user_ids = array( $restricted, $permitted );

		$table_memo = new ReflectionProperty( 'BB_XProfile_Visibility', 'table_exists_cache' );
		$table_memo->setAccessible( true );
		$table_memo->setValue( null, false );

		try {
			$narrowed = bb_xprofile_filter_possible_hidden_users( $user_ids, $levels );

			$this->assertIsArray(
				$narrowed,
				'An install whose visibility table has not been created yet had its search leg withheld - a missing table is the pre-migration shape, not a read failure.'
			);
			$this->assertContains(
				(int) $restricted,
				array_map( 'intval', $narrowed ),
				'The user-meta branch did not recognise a member who restricted the field, so the match would have been served unchecked.'
			);
			$this->assertNotContains(
				(int) $permitted,
				array_map( 'intval', $narrowed ),
				'The user-meta branch made a candidate of a member who restricted nothing.'
			);
		} finally {
			$table_memo->setValue( null, null );
			$this->flush_visibility_caches();
		}

		// The other direction, on the same input: the table is there, the read fails, and that is
		// the one answer that cannot be worked around.
		$break_query = function ( $query ) {
			if ( false !== strpos( $query, 'DISTINCT user_id' ) && false !== stripos( $query, 'visibility' ) ) {
				return 'SELECT DISTINCT user_id FROM __bb_no_such_table__ WHERE 1=1';
			}

			return $query;
		};

		$suppress = $wpdb->suppress_errors( true );
		add_filter( 'query', $break_query );

		try {
			$failed = bb_xprofile_filter_possible_hidden_users( $user_ids, $levels );
		} finally {
			remove_filter( 'query', $break_query );
			$wpdb->suppress_errors( $suppress );
		}

		$this->assertFalse(
			$failed,
			'A failed read was reported as a narrowing, which is the answer that publishes the names.'
		);
	}
}
