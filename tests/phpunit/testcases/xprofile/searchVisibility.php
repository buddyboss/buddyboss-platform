<?php
/**
 * Profile-field visibility applied to member search and name resolution.
 *
 * PROD-9896: a member search must not confirm the contents of a name or profile field the
 * searcher is not allowed to read, and no viewer may be shown more of a name than a viewer with
 * broader permission.
 *
 * @group xprofile
 * @group bb_search_visibility
 */
class BP_Tests_XProfile_SearchVisibility extends BP_UnitTestCase {

	/**
	 * Display Name Format backup.
	 *
	 * @var string
	 */
	protected $format_backup;

	/**
	 * First Name field default-visibility backup.
	 *
	 * @var string
	 */
	protected $fn_default_backup;

	/**
	 * First Name field allow-custom-visibility backup.
	 *
	 * @var string
	 */
	protected $fn_allow_backup;

	/**
	 * The harness default is the "First Name" format, under which the surname is not part of any
	 * member's visible name and every assertion below about surname visibility would be decided by
	 * the format rather than by the field's visibility level. Pin the "First Name & Last Name"
	 * format so these tests exercise what they claim to.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->format_backup     = bp_get_option( 'bp-display-name-format' );
		$this->fn_default_backup = bp_xprofile_get_meta( bp_xprofile_firstname_field_id(), 'field', 'default_visibility' );
		$this->fn_allow_backup   = bp_xprofile_get_meta( bp_xprofile_firstname_field_id(), 'field', 'allow_custom_visibility' );

		bp_update_option( 'bp-display-name-format', 'first_last_name' );
	}

	/**
	 * Restore the options the harness started with.
	 */
	public function tearDown(): void {
		bp_update_option( 'bp-display-name-format', $this->format_backup );
		bp_xprofile_update_meta( bp_xprofile_firstname_field_id(), 'field', 'default_visibility', $this->fn_default_backup );
		bp_xprofile_update_meta( bp_xprofile_firstname_field_id(), 'field', 'allow_custom_visibility', $this->fn_allow_backup );
		wp_cache_delete( 'default_visibility_levels', 'bp_xprofile' );

		parent::tearDown();
	}

	/**
	 * Run a member search as a given viewer and return the matched user IDs.
	 *
	 * @param int    $viewer_id Viewer, 0 for a logged-out visitor.
	 * @param string $term      Search term.
	 * @return array
	 */
	protected function search_as( $viewer_id, $term ) {
		$this->set_current_user( $viewer_id );

		$query = new BP_User_Query(
			array(
				'search_terms' => $term,
				'per_page'     => 20,
			)
		);

		return array_map( 'intval', (array) $query->user_ids );
	}

	/**
	 * Create a member whose surname is stored in both the WP column and the xprofile field.
	 *
	 * Each test uses its own surname on purpose. bp_xprofile_bp_user_query_search() memoizes its
	 * built SQL - including the resolved ID list - in a function-local static keyed by the search
	 * term. That is correct within a single request, where the data cannot change underneath it,
	 * but a PHPUnit process spans many notional requests, so reusing one term across tests would
	 * serve an earlier test's ID list to a later one. The same applies to the first name, so a test
	 * that searches on it passes its own.
	 *
	 * @param string $visibility Visibility level for the Last Name field.
	 * @param string $surname    Surname to store, unique per test.
	 * @param string $first_name First name to store, unique per test that searches on it.
	 * @return int
	 */
	protected function create_member_with_hidden_surname( $visibility, $surname, $first_name = 'Alex' ) {
		$user_id = self::factory()->user->create( array( 'nickname' => 'quillnick' ) );

		wp_update_user(
			array(
				'ID'           => $user_id,
				'first_name'   => $first_name,
				'last_name'    => $surname,
				'display_name' => $first_name . ' ' . $surname,
			)
		);

		xprofile_set_field_data( bp_xprofile_firstname_field_id(), $user_id, $first_name );
		xprofile_set_field_data( bp_xprofile_lastname_field_id(), $user_id, $surname );
		xprofile_set_field_visibility_level( bp_xprofile_lastname_field_id(), $user_id, $visibility );

		return $user_id;
	}

	/**
	 * A logged-out visitor must not be able to confirm a surname hidden from them.
	 */
	public function test_guest_cannot_find_member_by_admins_only_surname() {
		$user_id = $this->create_member_with_hidden_surname( 'adminsonly', 'Quillfeather' );

		$this->assertNotContains( $user_id, $this->search_as( 0, 'Quillfeather' ), 'guest confirmed an admins-only surname' );
	}

	/**
	 * The same search must still find them by a name part that IS visible - the filter must not
	 * remove a member whose visible name legitimately matches.
	 */
	public function test_guest_can_still_find_member_by_visible_first_name() {
		$user_id = $this->create_member_with_hidden_surname( 'adminsonly', 'Thornbury', 'Bartholomew' );

		$this->assertContains( $user_id, $this->search_as( 0, 'Bartholomew' ), 'the visible first name stopped matching' );
	}

	/**
	 * A logged-in member who is not a friend is denied 'friends' and 'adminsonly'.
	 */
	public function test_logged_in_stranger_cannot_find_member_by_hidden_surname() {
		$user_id  = $this->create_member_with_hidden_surname( 'adminsonly', 'Marlowgate' );
		$stranger = self::factory()->user->create();

		$this->assertNotContains( $user_id, $this->search_as( $stranger, 'Marlowgate' ) );
	}

	/**
	 * 'loggedin' is the level the previous implementation did not recognise at all: it is hidden
	 * from a visitor and visible to any logged-in member. Both directions are asserted, so neither
	 * an over-broad nor an absent filter can pass.
	 */
	public function test_loggedin_level_is_hidden_from_guests_but_visible_to_members() {
		$user_id = $this->create_member_with_hidden_surname( 'loggedin', 'Pennyworth' );
		$member  = self::factory()->user->create();

		$this->assertNotContains( $user_id, $this->search_as( 0, 'Pennyworth' ), 'a guest confirmed a members-only surname' );
		$this->assertContains( $user_id, $this->search_as( $member, 'Pennyworth' ), 'a logged-in member lost a surname they may see' );
	}

	/**
	 * A moderator may read every field, so nothing is filtered from their search.
	 */
	public function test_moderator_can_find_member_by_hidden_surname() {
		$user_id = $this->create_member_with_hidden_surname( 'adminsonly', 'Ravenscroft' );
		$admin   = self::factory()->user->create( array( 'role' => 'administrator' ) );

		$this->assertContains( $user_id, $this->search_as( $admin, 'Ravenscroft' ) );
	}

	/**
	 * A public surname must behave exactly as before - this pins the filter against over-reach,
	 * which would silently break member search for every ordinary community.
	 */
	public function test_public_surname_is_unaffected() {
		$user_id = $this->create_member_with_hidden_surname( 'public', 'Wintersedge', 'Clementine' );

		$this->assertContains( $user_id, $this->search_as( 0, 'Wintersedge' ) );
		$this->assertContains( $user_id, $this->search_as( 0, 'Clementine' ) );
	}

	/**
	 * A member is hidden from nobody on their own search.
	 */
	public function test_member_can_find_themselves_by_their_own_hidden_surname() {
		$user_id = $this->create_member_with_hidden_surname( 'adminsonly', 'Hollybrook' );

		$this->assertContains( $user_id, $this->search_as( $user_id, 'Hollybrook' ) );
	}

	/**
	 * The site-wide Display Name Format hide, which is the configuration this ticket is about.
	 *
	 * Under "First Name" the surname is not part of anybody's visible name, whatever the Last Name
	 * field's visibility says, and bp_core_get_user_displayname() redacts it accordingly. Before
	 * this, none of the search filter's candidate sources looked at the format at all, so a member
	 * whose only match was that surname stayed in the results and confirmed it.
	 *
	 * The member here has a PUBLIC Last Name field and no visibility row of any kind, so sources
	 * (1) and (2) cannot see them - only the format source can. The same search under the "First
	 * Name & Last Name" format is the negative control: nothing is hidden there, so the member must
	 * still be found and the filter must not have become a blanket surname block.
	 *
	 * @group bb_search_visibility_display_format
	 */
	public function test_display_name_format_hide_removes_a_surname_only_match() {
		$user_id = $this->create_member_with_hidden_surname( 'public', 'Ashdownly', 'Rosalind' );
		$pattern = '%Ashdownly%';

		bp_update_option( 'bp-display-name-format', 'first_last_name' );
		$this->set_current_user( 0 );
		$this->assertSame(
			array( $user_id ),
			bb_xprofile_filter_user_search_matches( array( $user_id ), array( $pattern ), 0 ),
			'Nothing is hidden under the First Name & Last Name format, so the match must survive.'
		);

		bp_update_option( 'bp-display-name-format', 'first_name' );
		$this->assertSame(
			array(),
			bb_xprofile_filter_user_search_matches( array( $user_id ), array( $pattern ), 0 ),
			'A guest confirmed a surname the First Name format hides from everyone.'
		);

		// The name part the format DOES show still matches, so the member stays findable.
		$this->assertSame(
			array( $user_id ),
			bb_xprofile_filter_user_search_matches( array( $user_id ), array( '%Rosalind%' ), 0 ),
			'The visible first name stopped matching under the format hide.'
		);
	}

	/**
	 * The format hide has to reach a surname that lives ONLY in the stored `display_name` column.
	 *
	 * That drift - an import, the wp-admin "Display name publicly as" dropdown, a third-party write
	 * - is the shape this whole redaction exists for, and it is invisible to a candidate source
	 * bounded by the Last Name field's stored value, because that field is empty.
	 *
	 * @group bb_search_visibility_display_format
	 */
	public function test_display_name_format_hide_reaches_a_drifted_display_name() {
		global $wpdb;

		$user_id = self::factory()->user->create( array( 'nickname' => 'driftnick' ) );
		xprofile_set_field_data( bp_xprofile_firstname_field_id(), $user_id, 'Dorothea' );
		xprofile_set_field_data( bp_xprofile_lastname_field_id(), $user_id, '' );

		$wpdb->update( $wpdb->users, array( 'display_name' => 'Dorothea Quillmere' ), array( 'ID' => $user_id ) );
		clean_user_cache( $user_id );

		$this->assertSame(
			'',
			(string) xprofile_get_field_data( bp_xprofile_lastname_field_id(), $user_id ),
			'Fixture: the surname must exist only in the stored column.'
		);

		bp_update_option( 'bp-display-name-format', 'first_name' );
		$this->set_current_user( 0 );

		$this->assertSame( 'Dorothea', bp_core_get_user_displayname( $user_id, 0 ), 'Fixture: the rendered name is redacted.' );
		$this->assertSame(
			array(),
			bb_xprofile_filter_user_search_matches( array( $user_id ), array( '%Quillmere%' ), 0 ),
			'A guest confirmed a surname that is redacted everywhere it is rendered.'
		);
	}

	/**
	 * Create a member whose search match exists ONLY in the stored `display_name` column.
	 *
	 * The shape the format hide exists for, and the one that used to promote every matched member
	 * to a candidate: the surname is not in the Last Name field, so no stored value bounds the set.
	 *
	 * @param string $token    Token that appears only in the stored column.
	 * @param string $nickname Nickname, deliberately not matching $token.
	 * @param string $surname  Value for the Last Name field. '' leaves the field unset.
	 * @return int
	 */
	protected function create_member_with_drifted_column( $token, $nickname, $surname = '' ) {
		global $wpdb;

		$user_id = self::factory()->user->create(
			array(
				'nickname'      => $nickname,
				'user_nicename' => $nickname,
			)
		);

		xprofile_set_field_data( bp_xprofile_firstname_field_id(), $user_id, 'Wilhelmina' );
		if ( '' !== $surname ) {
			xprofile_set_field_data( bp_xprofile_lastname_field_id(), $user_id, $surname );
		}

		$wpdb->update( $wpdb->users, array( 'display_name' => 'Wilhelmina ' . $token ), array( 'ID' => $user_id ) );
		clean_user_cache( $user_id );

		return $user_id;
	}

	/**
	 * Count how many display names a call resolves.
	 *
	 * The per-candidate resolution is the cost that made this filter an unauthenticated memory
	 * exhaustion (PROD-9896 B1), so the bound is asserted by counting resolutions, not by timing.
	 *
	 * @param callable $callback Code to measure.
	 * @return array array( return value, resolution count ).
	 */
	protected function count_name_resolutions( $callback ) {
		$counter = 0;

		$probe = function ( $name ) use ( &$counter ) {
			++$counter;

			return $name;
		};

		add_filter( 'bp_core_get_user_displayname', $probe, 1 );
		$result = call_user_func( $callback );
		remove_filter( 'bp_core_get_user_displayname', $probe, 1 );

		return array( $result, $counter );
	}

	/**
	 * Under a format-level hide the answer must come from SQL, not from a name per matched member.
	 *
	 * Source (3) promoted EVERY matched user to a candidate and resolved a full display name for
	 * each, before pagination and with no bound, on a request an anonymous visitor can issue. On a
	 * 70k-member install a one-word term exhausted 512 MB. The matches here are all decidable in
	 * SQL - their visible name is the first name, the nickname or the user_nicename, none of which
	 * match - so the correct cost is ZERO resolutions.
	 *
	 * @group bb_search_visibility_display_format
	 */
	public function test_format_hide_decides_matches_without_resolving_a_name_each() {
		$user_ids = array();
		for ( $i = 0; $i < 12; $i++ ) {
			$user_ids[] = $this->create_member_with_drifted_column( 'Zarquonade', 'zarqnick' . $i );
		}

		bp_update_option( 'bp-display-name-format', 'first_name' );
		$this->set_current_user( 0 );

		list( $result, $resolutions ) = $this->count_name_resolutions(
			function () use ( $user_ids ) {
				return bb_xprofile_filter_user_search_matches( $user_ids, array( '%Zarquonade%' ), 0 );
			}
		);

		$this->assertSame( array(), $result, 'A guest confirmed a surname the format hides from everyone.' );
		$this->assertSame(
			0,
			$resolutions,
			'Every matched member was still resolved in PHP - the work this filter does is unbounded again.'
		);
	}

	/**
	 * Past the budget the remaining unverified matches must be DROPPED, never served.
	 *
	 * A bound that failed open would re-open the very oracle the filter exists to close, so the
	 * expensive answer and the safe answer have to be the same answer.
	 *
	 * @group bb_search_visibility_display_format
	 */
	public function test_candidate_budget_drops_unverified_matches_instead_of_serving_them() {
		$over_budget = array();
		for ( $i = 0; $i < 4; $i++ ) {
			// A stored surname makes each of these undecidable in SQL, so they are exactly the
			// matches the budget has to rule on.
			$over_budget[] = $this->create_member_with_drifted_column( 'Vandergloom', 'vglnick' . $i, 'Vandergloom' );
		}

		// A member the format still shows: their match is in the first name, so it is kept without
		// any re-testing and the budget must not touch it.
		$visible_id = $this->create_member_with_hidden_surname( 'public', 'Ellsworthy', 'Vandergloom' );

		bp_update_option( 'bp-display-name-format', 'first_name' );
		$this->set_current_user( 0 );

		$cap = function () {
			return 2;
		};
		add_filter( 'bb_xprofile_user_search_visibility_candidate_limit', $cap );

		list( $result, $resolutions ) = $this->count_name_resolutions(
			function () use ( $over_budget, $visible_id ) {
				return bb_xprofile_filter_user_search_matches(
					array_merge( $over_budget, array( $visible_id ) ),
					array( '%Vandergloom%' ),
					0
				);
			}
		);

		remove_filter( 'bb_xprofile_user_search_visibility_candidate_limit', $cap );

		$this->assertSame(
			array( $visible_id ),
			$result,
			'Over budget the unverified matches were served instead of dropped - the oracle is open again.'
		);
		$this->assertSame( 0, $resolutions, 'The budget was exceeded, so nothing should have been resolved at all.' );
	}

	/**
	 * The SQL answer must not over-filter: a match that survives in the VISIBLE name is still a hit.
	 *
	 * The stored column drifts, so the redacted name can contain a token that is in none of the
	 * fields ("Reggie Quenlingham" minus the surname, with a First Name field reading "Reginald").
	 * SQL cannot compute that residue, so those matches - and only those - are still re-tested.
	 *
	 * @group bb_search_visibility_display_format
	 */
	public function test_format_hide_keeps_a_match_that_survives_in_the_visible_name() {
		global $wpdb;

		$user_id = self::factory()->user->create(
			array(
				'nickname'      => 'holdnick',
				'user_nicename' => 'holdnick',
			)
		);

		xprofile_set_field_data( bp_xprofile_firstname_field_id(), $user_id, 'Reginald' );
		xprofile_set_field_data( bp_xprofile_lastname_field_id(), $user_id, 'Quenlingham' );

		$wpdb->update( $wpdb->users, array( 'display_name' => 'Reggiebert Quenlingham' ), array( 'ID' => $user_id ) );
		clean_user_cache( $user_id );

		bp_update_option( 'bp-display-name-format', 'first_name' );
		$this->set_current_user( 0 );

		$this->assertSame(
			'Reggiebert',
			bp_core_get_user_displayname( $user_id, 0 ),
			'Fixture: the visible name must keep the searched token after the surname is removed.'
		);

		$this->assertSame(
			array( $user_id ),
			bb_xprofile_filter_user_search_matches( array( $user_id ), array( '%Reggiebert%' ), 0 ),
			'A member whose VISIBLE name matches was filtered out of the results.'
		);

		$this->assertSame(
			array(),
			bb_xprofile_filter_user_search_matches( array( $user_id ), array( '%Quenlingham%' ), 0 ),
			'The surname the format hides still answered a search.'
		);
	}

	/**
	 * The budget also bounds the per-user visibility sources, and fails closed there too.
	 *
	 * Sources (1) and (2) are bounded by stored rows rather than by the search term, which is not
	 * the same as small: on a community where a name field's default visibility is restricted, or
	 * where many members restrict their own, every matched member carries a row and the re-test is
	 * as large as the match count again. Exercised under the "First Name & Last Name" format so
	 * the site-wide format source is not involved at all.
	 *
	 * @group bb_search_visibility_display_format
	 */
	public function test_candidate_budget_also_bounds_the_per_user_visibility_sources() {
		$user_ids = array();
		for ( $i = 0; $i < 4; $i++ ) {
			// Restricted surname, but a FIRST name that matches the term - so the re-test keeps
			// them, and a truncated re-test is observable as a member going missing.
			$user_ids[] = $this->create_member_with_hidden_surname( 'adminsonly', 'Underbough' . $i, 'Underbough' );
		}

		bp_update_option( 'bp-display-name-format', 'first_last_name' );
		$this->set_current_user( 0 );

		$unbounded = bb_xprofile_filter_user_search_matches( $user_ids, array( '%Underbough%' ), 0 );
		sort( $unbounded );
		$expected = $user_ids;
		sort( $expected );

		$this->assertSame(
			$expected,
			$unbounded,
			'Fixture: with no budget every one of these members is kept, because their visible first name matches.'
		);

		$cap = function () {
			return 2;
		};
		add_filter( 'bb_xprofile_user_search_visibility_candidate_limit', $cap );

		list( $result, $resolutions ) = $this->count_name_resolutions(
			function () use ( $user_ids ) {
				return bb_xprofile_filter_user_search_matches( $user_ids, array( '%Underbough%' ), 0 );
			}
		);

		remove_filter( 'bb_xprofile_user_search_visibility_candidate_limit', $cap );

		$this->assertCount(
			2,
			$result,
			'Past the budget the unverified matches were served instead of dropped.'
		);
		$this->assertSame( 2, $resolutions, 'The budget did not bound the number of names resolved.' );
		$this->assertEmpty(
			array_diff( $result, $user_ids ),
			'The budget removed members that were never candidates.'
		);
	}

	/**
	 * The Nickname format takes a different set of visible sources, and needs its own cover.
	 *
	 * Under "Nickname" the visible name is the nickname meta, NOT the nickname profile field (the
	 * two drift apart), and only when that meta is empty does the resolver fall through to the
	 * first name. So the SQL answer is decided by different columns than under "First Name", and a
	 * member with no nickname at all is the one shape that still has to be re-tested.
	 *
	 * @group bb_search_visibility_display_format
	 */
	public function test_nickname_format_decides_matches_from_the_nickname_not_the_profile_field() {
		global $wpdb;

		// Nickname stored, and it does not match: the visible name is that nickname, so the match
		// exists only in the hidden part and the answer is decidable in SQL.
		$hidden_id = self::factory()->user->create(
			array(
				'nickname'      => 'gladhollow',
				'user_nicename' => 'gladhollow',
			)
		);
		xprofile_set_field_data( bp_xprofile_firstname_field_id(), $hidden_id, 'Perrin' );
		$wpdb->update( $wpdb->users, array( 'display_name' => 'Perrin Ashgrovely' ), array( 'ID' => $hidden_id ) );
		clean_user_cache( $hidden_id );

		// No nickname at all: the resolver falls through to the first name, which no column in the
		// visible-source query can tell us about, so this one must still be re-tested.
		$fallback_id = self::factory()->user->create( array( 'user_nicename' => 'nonicknamer' ) );
		delete_user_meta( $fallback_id, 'nickname' );
		xprofile_set_field_data( bp_xprofile_firstname_field_id(), $fallback_id, 'Ashgrovely' );
		$wpdb->update( $wpdb->users, array( 'display_name' => 'Ashgrovely Winterbourne' ), array( 'ID' => $fallback_id ) );
		clean_user_cache( $fallback_id );

		bp_update_option( 'bp-display-name-format', 'nickname' );
		$this->set_current_user( 0 );

		$this->assertSame(
			'Ashgrovely',
			bp_core_get_user_displayname( $fallback_id, 0 ),
			'Fixture: with no nickname the visible name must fall through to the first name.'
		);

		list( $result, $resolutions ) = $this->count_name_resolutions(
			function () use ( $hidden_id, $fallback_id ) {
				return bb_xprofile_filter_user_search_matches(
					array( $hidden_id, $fallback_id ),
					array( '%Ashgrovely%' ),
					0
				);
			}
		);

		$this->assertSame(
			array( $fallback_id ),
			$result,
			'Under the Nickname format the wrong members survived: the hidden one must go, the one whose visible name matches must stay.'
		);
		$this->assertSame(
			1,
			$resolutions,
			'Only the member whose visible name SQL cannot compute should have been resolved.'
		);
	}

	/**
	 * A search is a read: re-testing a candidate must not write to their profile fields.
	 *
	 * bp_xprofile_get_member_display_name() back-fills a missing name field from the WP user meta
	 * and DELETEs the row when that value is empty. Reached from the search re-test that is one
	 * destructive write per candidate, against members who are not even in the results
	 * (PROD-9896 H1).
	 *
	 * @group bb_search_visibility_display_format
	 */
	public function test_search_re_test_does_not_write_to_profile_fields() {
		global $wpdb;

		$bp      = buddypress();
		$viewer  = self::factory()->user->create();
		$user_id = self::factory()->user->create(
			array(
				'nickname'      => 'healnick',
				'user_nicename' => 'healnick',
			)
		);

		// No First Name row at all - that absence is what the self-heal repairs.
		$wpdb->delete( $bp->profile->table_name_data, array( 'field_id' => bp_xprofile_firstname_field_id(), 'user_id' => $user_id ) );
		xprofile_set_field_data( bp_xprofile_lastname_field_id(), $user_id, 'Quenlingham' );

		$wpdb->update( $wpdb->users, array( 'display_name' => 'Reggiebert Quenlingham' ), array( 'ID' => $user_id ) );
		clean_user_cache( $user_id );

		$before = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$bp->profile->table_name_data} WHERE field_id = %d AND user_id = %d",
				bp_xprofile_firstname_field_id(),
				$user_id
			)
		);
		$this->assertSame( 0, $before, 'Fixture: the First Name row must be missing before the search.' );

		bp_update_option( 'bp-display-name-format', 'first_name' );
		$this->set_current_user( $viewer );

		bb_xprofile_filter_user_search_matches( array( $user_id ), array( '%Reggiebert%' ), $viewer );

		$after = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$bp->profile->table_name_data} WHERE field_id = %d AND user_id = %d",
				bp_xprofile_firstname_field_id(),
				$user_id
			)
		);

		$this->assertSame( 0, $after, 'A read-only member search wrote to another member\'s profile fields.' );
		$this->assertFalse(
			bb_xprofile_is_display_name_self_heal_suspended(),
			'The self-heal suspension outlived the search and will silence the repair for the rest of the request.'
		);
	}

	/**
	 * The re-entrancy marker must be cleared even when the resolution throws.
	 *
	 * The marker is what stands WordPress core's author filters down while a resolution reads the
	 * stored column. Left raised it fails OPEN: every later get_the_author_display_name /
	 * the_author / document_title_parts / rest_prepare_user returns the RAW column (PROD-9896).
	 *
	 * @group bb_search_visibility_display_format
	 */
	public function test_core_author_redaction_clears_the_marker_when_a_filter_throws() {
		$user_id = $this->create_member_with_hidden_surname( 'adminsonly', 'Thrimbleby', 'Cornelius' );

		$this->set_current_user( 0 );

		$boom = function () {
			throw new RuntimeException( 'boom' );
		};

		add_filter( 'bp_core_get_user_displayname', $boom, 999 );

		try {
			bb_core_get_redacted_core_author_name( $user_id );
		} catch ( Exception $e ) {
			unset( $e );
		}

		remove_filter( 'bp_core_get_user_displayname', $boom, 999 );

		$this->assertFalse(
			bb_core_is_resolving_user_displayname(),
			'The marker stayed raised after a throw - WordPress core author output now leaks the raw display_name.'
		);

		$this->assertSame(
			'Cornelius',
			apply_filters( 'get_the_author_display_name', 'Cornelius Thrimbleby', $user_id ),
			'WordPress core author output served the raw column after the failed resolution.'
		);
	}

	/**
	 * A moderator may read every name, so the format source must not filter their search either -
	 * the function returns before any candidate is built for them.
	 *
	 * @group bb_search_visibility_display_format
	 */
	public function test_display_name_format_hide_does_not_filter_a_moderator() {
		$user_id = $this->create_member_with_hidden_surname( 'public', 'Fenwicker', 'Marguerite' );
		$admin   = self::factory()->user->create( array( 'role' => 'administrator' ) );

		bp_update_option( 'bp-display-name-format', 'first_name' );

		$this->assertSame(
			array( $user_id ),
			bb_xprofile_filter_user_search_matches( array( $user_id ), array( '%Fenwicker%' ), $admin )
		);
	}

	/**
	 * The site-wide search engine (Bp_Search_Members) builds its own SQL against wp_users and never
	 * runs BP_User_Query, so it used to answer a surname question the member directory refuses. The
	 * wp_users leg must now exclude the same members.
	 *
	 * @group bb_search_visibility_display_format
	 */
	public function test_site_search_members_sql_excludes_a_hidden_display_name_match() {
		// The harness does not boot the Search component, and its class autoloader require()s any
		// bp-* class file it is asked for whether the component was booted or not - so probing for
		// Bp_Search_Members with autoloading on is a fatal, because its base class is defined by an
		// include the component never ran. Load the base class first, then the subclass.
		$search_dir = buddypress()->plugin_dir . 'bp-search/';

		foreach ( array( 'bp-search-functions.php', 'bp-search-settings.php' ) as $search_file ) {
			if ( file_exists( $search_dir . $search_file ) ) {
				require_once $search_dir . $search_file;
			}
		}

		if ( ! class_exists( 'Bp_Search_Type', false ) && file_exists( $search_dir . 'classes/class-bp-search-types.php' ) ) {
			require_once $search_dir . 'classes/class-bp-search-types.php';
		}

		if ( ! class_exists( 'Bp_Search_Type', false ) ) {
			$this->markTestSkipped( 'The Search component is not available in this configuration.' );
		}

		if ( ! class_exists( 'Bp_Search_Members', false ) && file_exists( $search_dir . 'classes/class-bp-search-members.php' ) ) {
			require_once $search_dir . 'classes/class-bp-search-members.php';
		}

		if ( ! class_exists( 'Bp_Search_Members', false ) || ! function_exists( 'bp_get_search_user_fields' ) || ! function_exists( 'bp_is_search_user_field_enable' ) ) {
			$this->markTestSkipped( 'The Search component is not available in this configuration.' );
		}

		$user_id = $this->create_member_with_hidden_surname( 'adminsonly', 'Thistlewood', 'Genevieve' );
		bp_update_user_last_activity( $user_id, bp_core_current_time() );

		// The Display Name search field is what the site owner switches on in Search settings; the
		// harness has no settings saved, so turn it on for this assertion and put it back after.
		$field_option = 'bp_search_user_field_display_name';
		$field_backup = get_option( $field_option );
		update_option( $field_option, 1 );

		$this->set_current_user( 0 );

		try {
			$sql = Bp_Search_Members::instance()->sql( 'Thistlewood' );
		} finally {
			if ( false === $field_backup ) {
				delete_option( $field_option );
			} else {
				update_option( $field_option, $field_backup );
			}
		}

		$this->assertStringContainsString( 'display_name LIKE', $sql, 'Fixture: the display_name comparison must be part of this query.' );
		$this->assertStringContainsString(
			'ID NOT IN ( ' . $user_id . ' )',
			$sql,
			'The wp_users leg still matches a member on a surname the viewer may not see.'
		);
	}

	/**
	 * The profile-field leg, tested at the function contract rather than through the whole query
	 * pipeline: a member whose every matching field is hidden from the viewer is dropped, and one
	 * who also matched a visible field is kept.
	 *
	 * The per-row implementation this replaced failed both halves - it did not recognise the
	 * 'loggedin' level at all, and it dropped a member on their first restricted match even when a
	 * visible field had matched too.
	 */
	public function test_field_search_matches_are_filtered_by_visibility() {
		$group_id  = self::factory()->xprofile_group->create();
		$hidden_id = (int) self::factory()->xprofile_field->create(
			array(
				'field_group_id' => $group_id,
				'type'           => 'textbox',
			)
		);
		$public_id = (int) self::factory()->xprofile_field->create(
			array(
				'field_group_id' => $group_id,
				'type'           => 'textbox',
			)
		);

		// Custom visibility has to be permitted for a stored level to be honoured by the getter.
		bp_xprofile_update_meta( $hidden_id, 'field', 'allow_custom_visibility', 'allowed' );
		bp_xprofile_update_meta( $public_id, 'field', 'allow_custom_visibility', 'allowed' );

		$hidden_only = self::factory()->user->create();
		$both        = self::factory()->user->create();
		$member      = self::factory()->user->create();

		foreach ( array( $hidden_only, $both ) as $user_id ) {
			xprofile_set_field_data( $hidden_id, $user_id, 'Umbraclesecret' );
			xprofile_set_field_visibility_level( $hidden_id, $user_id, 'loggedin' );
		}
		xprofile_set_field_data( $public_id, $both, 'Umbraclesecret' );
		xprofile_set_field_visibility_level( $public_id, $both, 'public' );

		// Fixture precondition: without this the assertions below would pass vacuously.
		$this->assertSame( 'loggedin', xprofile_get_field_visibility_level( $hidden_id, $hidden_only ) );

		$matched_ids  = array( $hidden_only, $both );
		$matched_rows = array(
			(object) array(
				'user_id'  => $hidden_only,
				'field_id' => $hidden_id,
			),
			(object) array(
				'user_id'  => $both,
				'field_id' => $hidden_id,
			),
			(object) array(
				'user_id'  => $both,
				'field_id' => $public_id,
			),
		);

		// A logged-out visitor is denied 'loggedin'.
		$guest_result = bb_xprofile_filter_field_search_matches( $matched_ids, $matched_rows, 0 );
		$this->assertNotContains( $hidden_only, $guest_result, 'a guest confirmed a members-only field value' );
		$this->assertContains( $both, $guest_result, 'a member who also matched a public field was dropped' );

		// A logged-in member may read 'loggedin', so nothing is removed.
		$member_result = bb_xprofile_filter_field_search_matches( $matched_ids, $matched_rows, $member );
		$this->assertContains( $hidden_only, $member_result, 'a logged-in member lost a field value they may see' );
		$this->assertContains( $both, $member_result );
	}

	/**
	 * A hidden FIRST name must not be served to a viewer who is denied it, even when the last name
	 * is visible - otherwise the guest (denied the most) is shown the full stored column while a
	 * permitted member gets the field-resolved name, i.e. the guest sees MORE.
	 *
	 * The first name is hidden here through the field's own default visibility, which
	 * bp_xprofile_get_fields_by_visibility_levels() applies to every member even where custom
	 * visibility is disabled - the way a site actually hides it.
	 */
	public function test_hidden_first_name_is_not_served_to_a_denied_viewer() {
		$fn_id = bp_xprofile_firstname_field_id();

		$user_id = $this->create_member_with_hidden_surname( 'public', 'Ashgrove', 'Cornelius' );
		$member  = self::factory()->user->create();

		// BuddyBoss locks the First Name field's custom visibility under this format, so the site-wide
		// default is what applies to every member - which is how a site actually hides it.
		bp_xprofile_update_meta( $fn_id, 'field', 'allow_custom_visibility', 'disabled' );
		bp_xprofile_update_meta( $fn_id, 'field', 'default_visibility', 'loggedin' );

		// BP_XProfile_Group::fetch_default_visibility_levels() caches the whole default map under a
		// single key that bp_xprofile_update_meta() does not invalidate, so the change is invisible
		// until it is cleared.
		wp_cache_delete( 'default_visibility_levels', 'bp_xprofile' );
		BB_XProfile_Visibility::flush_field_ids_cache();

		// Fixture precondition: the first name really is hidden from a guest and visible to a member.
		$this->assertContains( (int) $fn_id, array_map( 'intval', (array) bp_xprofile_get_hidden_fields_for_user( $user_id, 0 ) ) );
		$this->assertNotContains( (int) $fn_id, array_map( 'intval', (array) bp_xprofile_get_hidden_fields_for_user( $user_id, $member ) ) );

		$guest_name  = bp_core_get_user_displayname( $user_id, 0 );
		$member_name = bp_core_get_user_displayname( $user_id, $member );

		$this->assertStringNotContainsStringIgnoringCase( 'cornelius', $guest_name, 'the hidden first name was served to a guest' );
		$this->assertSame( 'Ashgrove', $guest_name );
		$this->assertStringContainsStringIgnoringCase( 'cornelius', $member_name, 'a permitted member lost the first name' );
	}

	/**
	 * The hidden first name must also be redacted when the stored display_name has drifted away
	 * from a plain "First Last" string - glued with no separator, joined by punctuation, or joined
	 * by a Unicode space. Stage 1 of the strip only matches a whole whitespace-delimited token, so
	 * without the token pass these shapes were returned with the hidden first name still in them.
	 *
	 * Each shape is asserted for a denied viewer AND for a permitted one, so neither an absent nor
	 * an over-broad redaction can pass.
	 */
	public function test_hidden_first_name_is_redacted_when_display_name_has_drifted() {
		$fn_id = bp_xprofile_firstname_field_id();

		$member = self::factory()->user->create();

		$shapes = array(
			'plain'       => 'Marigold Thistlewood',
			'glued'       => 'MarigoldThistlewood',
			'punctuation' => 'Marigold-Thistlewood',
			'nbsp'        => "Marigold\xc2\xa0Thistlewood",
		);

		foreach ( $shapes as $label => $display_name ) {
			$user_id = self::factory()->user->create( array( 'nickname' => 'marinick' ) );

			xprofile_set_field_data( $fn_id, $user_id, 'Marigold' );
			xprofile_set_field_data( bp_xprofile_lastname_field_id(), $user_id, 'Thistlewood' );
			xprofile_set_field_visibility_level( bp_xprofile_lastname_field_id(), $user_id, 'public' );

			global $wpdb;
			$wpdb->update( $wpdb->users, array( 'display_name' => $display_name ), array( 'ID' => $user_id ) );
			clean_user_cache( $user_id );

			bp_xprofile_update_meta( $fn_id, 'field', 'allow_custom_visibility', 'disabled' );
			bp_xprofile_update_meta( $fn_id, 'field', 'default_visibility', 'loggedin' );
			wp_cache_delete( 'default_visibility_levels', 'bp_xprofile' );
			wp_cache_delete( $fn_id, 'bp_xprofile_fields' );
			BB_XProfile_Visibility::flush_field_ids_cache();

			// Fixture precondition: the first name really is hidden from a guest.
			$this->assertContains( (int) $fn_id, array_map( 'intval', (array) bp_xprofile_get_hidden_fields_for_user( $user_id, 0 ) ), "precondition ({$label})" );

			$guest = bp_core_get_user_displayname( $user_id, 0 );
			$this->assertStringNotContainsStringIgnoringCase( 'marigold', $guest, "hidden first name leaked to a guest ({$label})" );
			$this->assertSame( 'Thistlewood', $guest, "the visible last name should survive ({$label})" );

			// A viewer who may see the first name keeps the full name.
			$permitted = bp_core_get_user_displayname( $user_id, $member );
			$this->assertStringContainsStringIgnoringCase( 'marigold', $permitted, "a permitted viewer lost the first name ({$label})" );
		}
	}

	/**
	 * When the first name is empty in every source bp_xprofile_get_member_display_name() consults
	 * (xprofile field, first_name usermeta and nickname usermeta), the name it rebuilds is the bare
	 * surname with no leading space. The priority-15 filter's strip needed a leading space, so it
	 * did nothing and returned the hidden surname verbatim to a logged-in viewer denied it.
	 *
	 * Also asserts the result is never blank - redacting the only stored name part must still yield
	 * a usable public label.
	 */
	public function test_hidden_last_name_is_redacted_when_it_is_the_entire_display_name() {
		$user_id  = self::factory()->user->create();
		$stranger = self::factory()->user->create();

		// Empty every first-name source, so the rebuilt name is the bare surname.
		xprofile_set_field_data( bp_xprofile_firstname_field_id(), $user_id, '' );
		update_user_meta( $user_id, 'first_name', '' );
		update_user_meta( $user_id, 'nickname', '' );
		xprofile_set_field_data( bp_xprofile_lastname_field_id(), $user_id, 'Ravensworth' );
		update_user_meta( $user_id, 'last_name', 'Ravensworth' );
		xprofile_set_field_visibility_level( bp_xprofile_lastname_field_id(), $user_id, 'adminsonly' );

		global $wpdb;
		$wpdb->update( $wpdb->users, array( 'display_name' => 'Ravensworth' ), array( 'ID' => $user_id ) );
		clean_user_cache( $user_id );
		BB_XProfile_Visibility::flush_field_ids_cache();

		// Fixture precondition: the rebuild really is the bare surname, and it really is hidden.
		$this->assertSame( 'Ravensworth', bp_xprofile_get_member_display_name( $user_id ) );
		$this->assertContains( (int) bp_xprofile_lastname_field_id(), array_map( 'intval', (array) bp_xprofile_get_hidden_fields_for_user( $user_id, $stranger ) ) );

		$this->set_current_user( $stranger );
		$name = bp_core_get_user_displayname( $user_id, $stranger );

		$this->assertStringNotContainsStringIgnoringCase( 'ravensworth', $name, 'the hidden surname was returned verbatim' );
		$this->assertNotSame( '', trim( (string) $name ), 'a redacted name must never be blank' );

		// The member themselves still sees it.
		$this->set_current_user( $user_id );
		$this->assertStringContainsStringIgnoringCase( 'ravensworth', bp_core_get_user_displayname( $user_id, $user_id ) );
	}

	/**
	 * The members directory renders names through bp_member_name()/bp_get_member_name(), which had
	 * no test at all and was still on the pre-fix logic: it preferred the loop's own `fullname`,
	 * fell back to the raw display_name column when that was unset, and redacted with a naive
	 * str_replace( ' ' . $last_name, ... ).
	 *
	 * Both halves are exercised here. `populate_extras => false` is the realistic path that leaves
	 * `fullname` unset - widgets and custom loops use it - and the drifted columns are the shapes
	 * the naive strip silently no-ops on.
	 *
	 * @group bb_member_directory
	 */
	public function test_members_loop_name_hides_last_name_from_guest() {
		$user_id = $this->create_member_with_hidden_surname( 'adminsonly', 'Bramblewick', 'Octavia' );

		global $wpdb;

		foreach ( array( 'Octavia Bramblewick', 'OctaviaBramblewick', 'Octavia-Bramblewick', 'Bramblewick' ) as $display_name ) {
			$wpdb->update( $wpdb->users, array( 'display_name' => $display_name ), array( 'ID' => $user_id ) );
			clean_user_cache( $user_id );
			BB_XProfile_Visibility::flush_field_ids_cache();

			foreach ( array( true, false ) as $populate_extras ) {
				$this->set_current_user( 0 );

				$has = bp_has_members(
					array(
						'include'         => array( $user_id ),
						'per_page'        => 1,
						'populate_extras' => $populate_extras,
					)
				);
				$this->assertTrue( $has, 'the members loop should return the fixture member' );

				global $members_template;
				while ( bp_members() ) {
					bp_the_member();

					$name = bp_get_member_name();
					$this->assertStringNotContainsStringIgnoringCase(
						'bramblewick',
						$name,
						"hidden surname leaked in the members loop (display_name '{$display_name}', populate_extras " . var_export( $populate_extras, true ) . ')'
					);
					$this->assertSame( 'Octavia', $name );
				}
			}
		}

		// The member themselves still sees their own full name in the loop.
		$wpdb->update( $wpdb->users, array( 'display_name' => 'Octavia Bramblewick' ), array( 'ID' => $user_id ) );
		clean_user_cache( $user_id );
		BB_XProfile_Visibility::flush_field_ids_cache();
		$this->set_current_user( $user_id );

		if ( bp_has_members( array( 'include' => array( $user_id ), 'per_page' => 1, 'populate_extras' => true ) ) ) {
			while ( bp_members() ) {
				bp_the_member();
				$this->assertStringContainsStringIgnoringCase( 'bramblewick', bp_get_member_name(), 'the member lost their own surname' );
			}
		}
	}

	/**
	 * A hidden name part welded into a longer token with letters or digits against it must still be
	 * redacted. The punctuation-boundary fail-safe cannot see these, and an exact first+last glue
	 * test does not match them either, so before this they were returned whole:
	 * "pzebrastripe" (initial + surname, the shape an LDAP or forum import produces),
	 * "PeterZebrastripeJr", "Zebrastripe2" (de-duplication suffix), "MrPeterZebrastripe".
	 *
	 * The second half of the table is the counter-pressure: a hidden part that is a coincidental
	 * fragment of a longer, unrelated word must NOT be over-redacted, or the fix silently deletes
	 * name parts the viewer is entitled to.
	 *
	 * @group bb_name_redaction
	 */
	public function test_strip_hidden_name_part_redacts_embedded_tokens_without_over_redacting() {
		$leaky = array(
			// display_name, hidden, visible.
			array( 'pzebrastripe', 'Zebrastripe', 'Peter' ),
			array( 'PeterZebrastripeJr', 'Zebrastripe', 'Peter' ),
			array( 'Zebrastripe2', 'Zebrastripe', 'Peter' ),
			array( 'MrPeterZebrastripe', 'Zebrastripe', 'Peter' ),
			array( 'Zebrastripes', 'Zebrastripe', 'Peter' ),
			// The visible first name has itself drifted, so the exact glue test cannot match.
			array( 'PeterZebrastripe', 'Zebrastripe', 'Pete' ),
		);

		foreach ( $leaky as $case ) {
			list( $display_name, $hidden, $visible ) = $case;
			$result = bb_core_strip_hidden_name_part( $display_name, $hidden, $visible );

			$this->assertStringNotContainsStringIgnoringCase(
				$hidden,
				$result,
				"hidden part survived in '{$display_name}'"
			);
		}

		// Coincidental fragments of unrelated words must survive untouched.
		$keep = array(
			array( 'Louis Armstrong Ng', 'Ng', 'Louis', 'Louis Armstrong' ),
			array( 'Linda Marie Lin', 'Lin', 'Linda', 'Linda Marie' ),
			array( 'Wendy Wu', 'Wu', 'Wendy', 'Wendy' ),
			array( 'AlexQuillfeather Jr', 'Quillfeather', 'Alex', 'Alex Jr' ),
			array( 'Anna Marie Smith', 'Smith', 'Anna', 'Anna Marie' ),
		);

		foreach ( $keep as $case ) {
			list( $display_name, $hidden, $visible, $expected ) = $case;
			$this->assertSame(
				$expected,
				bb_core_strip_hidden_name_part( $display_name, $hidden, $visible ),
				"over-redaction for '{$display_name}'"
			);
		}
	}

	/**
	 * A name value that is not valid UTF-8 must fail CLOSED. preg_replace() with the /u modifier
	 * returns null on such a subject, and if that null is treated as "no hidden part" the raw
	 * display_name is returned to a viewer who is denied it - the exact legacy/imported data shape
	 * this redaction exists for.
	 *
	 * The empty case is asserted alongside it because the two must not collapse: an empty hidden
	 * part legitimately means "nothing to strip" and returns the name unchanged.
	 *
	 * @group bb_name_redaction
	 */
	public function test_strip_hidden_name_part_fails_closed_on_unprocessable_input() {
		$malformed = "Zebra\xb0stripe"; // Lone 0xB0 - not valid UTF-8.

		$this->assertFalse( mb_check_encoding( $malformed, 'UTF-8' ), 'fixture must really be malformed' );

		// Malformed hidden part, valid display name: must not return the column.
		$this->assertSame( '', bb_core_strip_hidden_name_part( 'Peter Zebrastripe', $malformed, 'Peter' ) );

		// The callers normalise before calling and pass null when that fails - same meaning.
		$this->assertSame( '', bb_core_strip_hidden_name_part( 'Peter Zebrastripe', null, 'Peter' ) );

		// Malformed display name with a valid hidden part: also fail closed.
		$this->assertSame( '', bb_core_strip_hidden_name_part( "Peter Zebra\xb0stripe", 'Zebrastripe', 'Peter' ) );

		// A genuinely empty hidden part is NOT a failure - nothing to strip.
		$this->assertSame( 'Peter Zebrastripe', bb_core_strip_hidden_name_part( 'Peter Zebrastripe', '', 'Peter' ) );

		// And the ordinary case still works.
		$this->assertSame( 'Peter', bb_core_strip_hidden_name_part( 'Peter Zebrastripe', 'Zebrastripe', 'Peter' ) );
	}

	/**
	 * The LIKE matcher has to agree with the SQL comparison that produced the candidate rows,
	 * including the backslash escaping bp_esc_like() applies.
	 */
	public function test_sql_like_match_mirrors_mysql_semantics() {
		$this->assertTrue( bb_core_sql_like_match( '%quill%', 'Alex Quillfeather' ), 'case-insensitive contains' );
		$this->assertTrue( bb_core_sql_like_match( '%Quillfeather', 'Alex Quillfeather' ), 'suffix' );
		$this->assertTrue( bb_core_sql_like_match( 'Alex%', 'Alex Quillfeather' ), 'prefix' );
		$this->assertFalse( bb_core_sql_like_match( '%Quillfeather%', 'Alex' ), 'redacted name must not match' );
		$this->assertTrue( bb_core_sql_like_match( 'Al_x%', 'Alex Quillfeather' ), 'single-character wildcard' );

		// A literal % typed by a member is escaped by bp_esc_like() and must stay literal.
		$this->assertTrue( bb_core_sql_like_match( '%' . bp_esc_like( '100%' ) . '%', 'Scored 100% today' ) );
		$this->assertFalse( bb_core_sql_like_match( '%' . bp_esc_like( '100%' ) . '%', 'Scored 100 today' ) );

		// Regex metacharacters in the pattern are literal in LIKE.
		$this->assertTrue( bb_core_sql_like_match( '%a.b%', 'x a.b y' ) );
		$this->assertFalse( bb_core_sql_like_match( '%a.b%', 'x axb y' ) );
	}

	/**
	 * The viewer helper prefers BuddyPress' own global, and only falls back to the WordPress
	 * current user when BuddyPress has none - the REST case it exists for.
	 */
	public function test_viewer_user_id_prefers_bp_global_then_falls_back() {
		$user_id = self::factory()->user->create();
		$this->set_current_user( $user_id );

		$this->assertSame( $user_id, bb_core_get_viewer_user_id(), 'should track the logged-in user' );

		// Simulate REST: WordPress knows the user, BuddyPress' global has not been populated.
		$bp                    = buddypress();
		$original              = $bp->loggedin_user->id;
		$bp->loggedin_user->id = 0;

		$this->assertSame( $user_id, bb_core_get_viewer_user_id(), 'should fall back to the WP current user' );

		$bp->loggedin_user->id = $original;
	}

	/**
	 * Build a matched-row object of the shape the profile-field search hands the second producer.
	 *
	 * @param int $user_id  Matched member.
	 * @param int $field_id Field the value comparison matched on.
	 * @return object
	 */
	protected function field_match_row( $user_id, $field_id ) {
		return (object) array(
			'user_id'  => (int) $user_id,
			'field_id' => (int) $field_id,
		);
	}

	/**
	 * The profile-FIELD search producer must drop a match that lives only in a hidden field.
	 *
	 * bb_xprofile_filter_field_search_matches() is the second of the two producers - a search over
	 * xprofile_data.value rather than the display_name column. It had no test at all, and a mutation
	 * in its format-hide arm survived (PROD-9896 M5). Without it a guest can confirm the contents of
	 * an admins-only field by watching whether the member comes back.
	 */
	public function test_field_search_drops_a_member_matched_only_on_a_hidden_field() {
		$user_id      = $this->create_member_with_hidden_surname( 'adminsonly', 'Blackwood' );
		$matched_data = array( $this->field_match_row( $user_id, bp_xprofile_lastname_field_id() ) );

		$this->set_current_user( 0 );
		$this->assertSame(
			array(),
			bb_xprofile_filter_field_search_matches( array( $user_id ), $matched_data, 0 ),
			'A guest confirmed the contents of an admins-only field through profile-field search.'
		);
	}

	/**
	 * A member is removed only when EVERY field they matched on is hidden - a hit that also lands on
	 * a visible field is legitimate and must survive. This pins the filter against over-reach.
	 */
	public function test_field_search_keeps_a_member_when_any_matched_field_is_visible() {
		$user_id      = $this->create_member_with_hidden_surname( 'adminsonly', 'Thistlewood', 'Cordelia' );
		$matched_data = array(
			$this->field_match_row( $user_id, bp_xprofile_firstname_field_id() ),
			$this->field_match_row( $user_id, bp_xprofile_lastname_field_id() ),
		);

		$this->set_current_user( 0 );
		$this->assertSame(
			array( $user_id ),
			bb_xprofile_filter_field_search_matches( array( $user_id ), $matched_data, 0 ),
			'A member matched on a visible field as well as a hidden one was wrongly dropped.'
		);
	}

	/**
	 * The mutation-surviving arm (PROD-9896 M5): the site-wide Display Name Format hide.
	 *
	 * The Last Name field here is PUBLIC with no visibility row of any kind, so only the "First Name"
	 * format removes it from every visible name. bb_xprofile_filter_user_search_matches() honours
	 * that; this second producer must apply the identical rule, or a surname-only field match
	 * discloses exactly the part the format suppresses. The "First Name & Last Name" run is the
	 * negative control - nothing hides the surname there, so the filter must not become a blanket
	 * surname block.
	 *
	 * @group bb_search_visibility_display_format
	 */
	public function test_field_search_format_hide_drops_a_last_name_only_match() {
		$user_id      = $this->create_member_with_hidden_surname( 'public', 'Grimsford', 'Evangeline' );
		$matched_data = array( $this->field_match_row( $user_id, bp_xprofile_lastname_field_id() ) );

		$this->set_current_user( 0 );

		bp_update_option( 'bp-display-name-format', 'first_last_name' );
		$this->assertSame(
			array( $user_id ),
			bb_xprofile_filter_field_search_matches( array( $user_id ), $matched_data, 0 ),
			'The public surname is part of the visible name under this format and must not be filtered.'
		);

		bp_update_option( 'bp-display-name-format', 'first_name' );
		$this->assertSame(
			array(),
			bb_xprofile_filter_field_search_matches( array( $user_id ), $matched_data, 0 ),
			'The First Name format hide was not applied by the profile-field search producer.'
		);
	}

	/**
	 * Under "Nickname" the visible name is the nickname alone, so BOTH the first name and the
	 * surname are out of it. A match that lives only in the first-name field must be dropped - the
	 * arm that force-adds the first-name field to the hidden set under this format.
	 *
	 * @group bb_search_visibility_display_format
	 */
	public function test_field_search_nickname_format_drops_a_first_name_only_match() {
		$user_id      = $this->create_member_with_hidden_surname( 'public', 'Nettleby', 'Percival' );
		$matched_data = array( $this->field_match_row( $user_id, bp_xprofile_firstname_field_id() ) );

		bp_update_option( 'bp-display-name-format', 'nickname' );
		$this->set_current_user( 0 );
		$this->assertSame(
			array(),
			bb_xprofile_filter_field_search_matches( array( $user_id ), $matched_data, 0 ),
			'The Nickname format hides the first name, but a first-name-only field match survived.'
		);
	}

	/**
	 * A moderator may read every field, so the second producer must filter nothing from them.
	 */
	public function test_field_search_moderator_sees_every_field() {
		$user_id      = $this->create_member_with_hidden_surname( 'adminsonly', 'Ironwood' );
		$admin        = self::factory()->user->create( array( 'role' => 'administrator' ) );
		$matched_data = array( $this->field_match_row( $user_id, bp_xprofile_lastname_field_id() ) );

		$this->assertSame(
			array( $user_id ),
			bb_xprofile_filter_field_search_matches( array( $user_id ), $matched_data, $admin ),
			'A moderator was denied a match on a field they may read.'
		);
	}

	/**
	 * A first name stored only in WordPress user-meta must be re-tested, not dropped.
	 *
	 * The imported/unhealed member shape here: the name lives in user_meta + wp_users.display_name,
	 * the xprofile First Name field is empty, and there is no stored surname. A LOGGED-IN viewer's
	 * resolver back-fills the empty field from user_meta and returns it, so their visible name
	 * genuinely matches and the member must stay findable. The GUEST path never reads user_meta
	 * first_name - it reads the field, then the nickname - so the same member is correctly NOT
	 * confirmable by a guest searching that first name (the nickname here does not contain it).
	 *
	 * Without the user-meta arm of bb_xprofile_get_format_undecidable_matches() the member is
	 * dropped for the logged-in viewer too, silently losing every unhealed member from search.
	 *
	 * @group bb_search_visibility_display_format
	 */
	public function test_format_hide_re_tests_a_first_name_stored_only_in_user_meta() {
		global $wpdb;

		$user_id = self::factory()->user->create( array( 'nickname' => 'quillby-nick' ) );

		// Force the xprofile name fields empty, THEN set the authoritative user-meta value, so the
		// only place the first name lives is user_meta and the drifted display_name column.
		xprofile_set_field_data( bp_xprofile_firstname_field_id(), $user_id, '' );
		xprofile_set_field_data( bp_xprofile_lastname_field_id(), $user_id, '' );
		update_user_meta( $user_id, 'first_name', 'Grimwald' );

		$wpdb->update( $wpdb->users, array( 'display_name' => 'Grimwald Ashforth' ), array( 'ID' => $user_id ) );
		clean_user_cache( $user_id );

		$this->assertSame(
			'',
			(string) xprofile_get_field_data( bp_xprofile_firstname_field_id(), $user_id ),
			'Fixture: the First Name field must be empty so the resolver falls back to user_meta.'
		);
		$this->assertSame(
			'Grimwald',
			(string) get_user_meta( $user_id, 'first_name', true ),
			'Fixture: the first name must live in user_meta.'
		);

		bp_update_option( 'bp-display-name-format', 'first_name' );

		$member = self::factory()->user->create();

		// Prove the fixture without persisting the self-heal (which would populate the field and
		// make the member trivially findable regardless of the fix under test).
		bb_xprofile_is_display_name_self_heal_suspended( true );
		try {
			$this->assertSame(
				'Grimwald',
				bp_core_get_user_displayname( $user_id, $member ),
				'Fixture: a logged-in viewer resolves the member to the user-meta first name.'
			);
		} finally {
			bb_xprofile_is_display_name_self_heal_suspended( false );
		}
		$this->assertSame(
			'',
			(string) xprofile_get_field_data( bp_xprofile_firstname_field_id(), $user_id ),
			'Fixture: the search re-test must not have persisted the back-filled first name.'
		);

		$this->assertContains(
			$user_id,
			bb_xprofile_filter_user_search_matches( array( $user_id ), array( '%Grimwald%' ), $member ),
			'A member whose first name lives only in user_meta was dropped from a logged-in search.'
		);

		$this->assertNotContains(
			$user_id,
			bb_xprofile_filter_user_search_matches( array( $user_id ), array( '%Grimwald%' ), 0 ),
			'A guest matched a first name their own view resolves to the nickname, not this term.'
		);
	}

	/**
	 * An SEO plugin that answers `pre_get_document_title` must not carry the surname into `<title>`.
	 *
	 * wp_get_document_title() returns the first non-empty `pre_get_document_title` value untouched,
	 * so a plugin answering there short-circuits the whole title build and
	 * bb_core_filter_author_document_title_parts() never fires. Every major SEO plugin does this -
	 * All in One SEO at priority 99999, Yoast, Rank Math - and they read the name off the WP_User
	 * object's display_name PROPERTY, which no WordPress filter intercepts. Replicated live against
	 * All in One SEO before this test was written (PROD-9896).
	 *
	 * @group bb_search_visibility_display_format
	 */
	public function test_seo_plugin_author_title_is_redacted() {
		// go_to() re-fires `init` in an already-booted process, so Platform re-registers its block.
		// A harness artefact of navigating, not something this test exercises.
		$this->setExpectedIncorrectUsage( 'WP_Block_Type_Registry::register' );

		$user_id = $this->create_member_with_hidden_surname( 'public', 'Ravensmere', 'Tobias' );

		bp_update_option( 'bp-display-name-format', 'first_name' );
		$this->set_current_user( 0 );

		$this->go_to( get_author_posts_url( $user_id ) );

		// Stand in for the SEO plugin: answer pre_get_document_title with a title built from the
		// raw column, exactly as they do, at the priority All in One SEO uses.
		$seo = function () use ( $user_id ) {
			$author = get_userdata( $user_id );

			return $author->display_name . ' - Test Site';
		};
		add_filter( 'pre_get_document_title', $seo, 99999 );

		$title = wp_get_document_title();

		remove_filter( 'pre_get_document_title', $seo, 99999 );

		$this->assertStringNotContainsString(
			'Ravensmere',
			$title,
			'An SEO plugin short-circuited the title build and carried the hidden surname into <title>.'
		);
		$this->assertSame(
			'Tobias - Test Site',
			$title,
			'The redaction must replace only the member name, leaving the rest of the plugin template intact.'
		);
	}

	/**
	 * An SEO plugin's JSON-LD graph must not carry a name part hidden from the viewer.
	 *
	 * SEO plugins build their schema from the WP_User object's display_name PROPERTY, a read no
	 * WordPress filter reaches, so BB_SEO_Helpers intercepts the graph on its way out instead.
	 * Replicated live against All in One SEO before this test was written: its breadcrumb crumbs
	 * (Breadcrumbs.php:317) and ProfilePage mainEntity name (ProfilePage.php:86) both carried the
	 * raw surname on an anonymous author-archive request (PROD-9896).
	 *
	 * @group bb_search_visibility_display_format
	 */
	public function test_seo_plugin_schema_graph_is_redacted() {
		// go_to() re-fires `init` in an already-booted process, so Platform re-registers its block.
		$this->setExpectedIncorrectUsage( 'WP_Block_Type_Registry::register' );

		$user_id = $this->create_member_with_hidden_surname( 'public', 'Ashcombe', 'Marguerite' );

		bp_update_option( 'bp-display-name-format', 'first_name' );
		$this->set_current_user( 0 );

		$this->go_to( get_author_posts_url( $user_id ) );

		require_once buddypress()->compatibility_dir . '/class-bb-seo-helpers.php';
		$helper = BB_SEO_Helpers::instance();

		$permalink = get_author_posts_url( $user_id );
		$graph     = array(
			array(
				'@type'      => 'BreadcrumbList',
				'itemListElement' => array(
					array( '@type' => 'ListItem', 'position' => 2, 'name' => 'Marguerite Ashcombe', 'item' => $permalink ),
				),
			),
			array(
				'@type'      => 'ProfilePage',
				'name'       => 'Marguerite Ashcombe - Test Site',
				'url'        => $permalink,
				'mainEntity' => array( '@type' => 'Person', 'name' => 'Marguerite Ashcombe' ),
			),
		);

		$redacted = $helper->redact_schema_graph( $graph );

		$this->assertStringNotContainsString(
			'Ashcombe',
			wp_json_encode( $redacted ),
			'An SEO plugin graph carried a surname the display format hides from every viewer.'
		);
		$this->assertSame(
			'Marguerite',
			$redacted[0]['itemListElement'][0]['name'],
			'The breadcrumb name should be the visible name, not blank or mangled.'
		);
		$this->assertSame(
			'Marguerite - Test Site',
			$redacted[1]['name'],
			'Only the member name should be replaced; the rest of the plugin template must survive.'
		);
		$this->assertSame(
			$permalink,
			$redacted[1]['url'],
			'A URL must never be rewritten - permalinks are built from user_nicename.'
		);
		$this->assertSame(
			'Person',
			$redacted[1]['mainEntity']['@type'],
			'The graph shape must be preserved exactly.'
		);
	}
}
