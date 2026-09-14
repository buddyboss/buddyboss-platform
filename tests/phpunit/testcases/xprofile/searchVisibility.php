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
	 * The 'friends' level is the only one that depends on the PAIR, not on the viewer alone.
	 *
	 * Every other level resolves from the viewer's own standing - logged out, logged in, moderator,
	 * the member themselves - so a filter that never consulted the friendship at all would still
	 * pass all of those. Only the positive direction of this one proves the per-pair resolution is
	 * reached: the file asserted that a stranger is denied a friends-only surname, but never that a
	 * confirmed friend can still find it, so an over-broad filter that hid the surname from
	 * EVERYONE was green.
	 *
	 * Both directions are asserted against the same term and the same member, so the two answers
	 * can only differ because of the friendship.
	 */
	public function test_friends_level_surname_is_visible_to_a_friend_and_hidden_from_a_stranger() {
		if ( ! bp_is_active( 'friends' ) || ! function_exists( 'friends_add_friend' ) ) {
			$this->markTestSkipped( 'The Friends component is not active in this configuration.' );
		}

		$user_id  = $this->create_member_with_hidden_surname( 'friends', 'Ashdowne', 'Cordelia' );
		$friend   = self::factory()->user->create();
		$stranger = self::factory()->user->create();

		friends_add_friend( $friend, $user_id, true );

		// Fixture guard: without a real confirmed friendship the positive assertion below would
		// pass for the wrong reason.
		$this->assertTrue(
			(bool) friends_check_friendship( $friend, $user_id ),
			'Fixture: the friendship was not established, so the positive case proves nothing.'
		);

		$this->assertContains(
			$user_id,
			$this->search_as( $friend, 'Ashdowne' ),
			'A confirmed friend lost a surname they are entitled to see.'
		);

		$this->assertNotContains(
			$user_id,
			$this->search_as( $stranger, 'Ashdowne' ),
			'A stranger confirmed a friends-only surname.'
		);
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
	 * A token that exists ONLY in the drifted column is not part of anybody's visible name.
	 *
	 * The stored display_name is a derived value that drifts away from the fields ("Reggiebert
	 * Quenlingham" for a member whose First Name field reads "Reginald"). The visible name is
	 * assembled from the fields, so that residue is shown to nobody - and a search for it must not
	 * confirm the member either, which SQL can now decide outright: no name is resolved at all.
	 *
	 * The companion assertion is the one that stops this becoming an over-filter: the token that IS
	 * in the shown field keeps the member findable.
	 *
	 * @group bb_search_visibility_display_format
	 */
	public function test_format_hide_decides_a_drifted_column_without_resolving_a_name() {
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
			'Reginald',
			bp_core_get_user_displayname( $user_id, 0 ),
			'Fixture: the visible name must come from the First Name field, not the drifted column.'
		);

		list( $result, $resolutions ) = $this->count_name_resolutions(
			function () use ( $user_id ) {
				return bb_xprofile_filter_user_search_matches( array( $user_id ), array( '%Reggiebert%' ), 0 );
			}
		);

		$this->assertSame(
			array(),
			$result,
			'A token that lives only in the drifted column still answered a search.'
		);
		$this->assertSame( 0, $resolutions, 'The format hide must be decided in SQL, resolving no names.' );

		$this->assertSame(
			array( $user_id ),
			bb_xprofile_filter_user_search_matches( array( $user_id ), array( '%Reginald%' ), 0 ),
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
	 * Under "Nickname" the visible name is the nickname - the profile field, then the `nickname`
	 * user meta it falls back to, then user_nicename. Neither name field is part of it at all, so
	 * a member whose match lives only in their first name or in the stored column is not findable
	 * by that term, and every one of those sources is a column, so the whole answer is decided in
	 * SQL with no name resolved.
	 *
	 * @group bb_search_visibility_display_format
	 */
	public function test_nickname_format_decides_matches_from_the_nickname_sources() {
		global $wpdb;

		// Nickname stored, and it does not match: the visible name is that nickname, so the match
		// exists only in the hidden part.
		$hidden_id = self::factory()->user->create(
			array(
				'nickname'      => 'gladhollow',
				'user_nicename' => 'gladhollow',
			)
		);
		xprofile_set_field_data( bp_xprofile_firstname_field_id(), $hidden_id, 'Perrin' );
		// Both nickname sources, set explicitly: the field is what a member sees under this format,
		// the user meta is what the fallback chain reads, and the factory does not set either.
		xprofile_set_field_data( bp_xprofile_nickname_field_id(), $hidden_id, 'gladhollow' );
		update_user_meta( $hidden_id, 'nickname', 'gladhollow' );
		$wpdb->update( $wpdb->users, array( 'display_name' => 'Perrin Ashgrovely' ), array( 'ID' => $hidden_id ) );
		clean_user_cache( $hidden_id );

		// No nickname at all: the visible name falls through to user_nicename, which does not carry
		// the term either. The first name is NOT a source under this format, so this member is not
		// findable by it - the leg that used to be re-tested in PHP and is now decided outright.
		$fallback_id = self::factory()->user->create( array( 'user_nicename' => 'nonicknamer' ) );
		xprofile_set_field_data( bp_xprofile_firstname_field_id(), $fallback_id, 'Ashgrovely' );
		xprofile_set_field_data( bp_xprofile_nickname_field_id(), $fallback_id, '' );
		delete_user_meta( $fallback_id, 'nickname' );
		$wpdb->update( $wpdb->users, array( 'display_name' => 'Ashgrovely Winterbourne' ), array( 'ID' => $fallback_id ) );
		clean_user_cache( $fallback_id );

		// A nickname that DOES match: this member must survive, or the filter is an over-filter.
		$visible_id = self::factory()->user->create(
			array(
				'nickname'      => 'Ashgrovely',
				'user_nicename' => 'ashg-visible',
			)
		);
		xprofile_set_field_data( bp_xprofile_firstname_field_id(), $visible_id, 'Corwin' );
		xprofile_set_field_data( bp_xprofile_nickname_field_id(), $visible_id, 'Ashgrovely' );
		update_user_meta( $visible_id, 'nickname', 'Ashgrovely' );
		$wpdb->update( $wpdb->users, array( 'display_name' => 'Corwin Dunhollow' ), array( 'ID' => $visible_id ) );
		clean_user_cache( $visible_id );

		bp_update_option( 'bp-display-name-format', 'nickname' );
		$this->set_current_user( 0 );

		$this->assertSame(
			'nonicknamer',
			bp_core_get_user_displayname( $fallback_id, 0 ),
			'Fixture: with no nickname the visible name must end at user_nicename, not the first name.'
		);

		list( $result, $resolutions ) = $this->count_name_resolutions(
			function () use ( $hidden_id, $fallback_id, $visible_id ) {
				return bb_xprofile_filter_user_search_matches(
					array( $hidden_id, $fallback_id, $visible_id ),
					array( '%Ashgrovely%' ),
					0
				);
			}
		);

		$this->assertSame(
			array( $visible_id ),
			$result,
			'Under the Nickname format only the member whose nickname matches may survive.'
		);
		$this->assertSame(
			0,
			$resolutions,
			'Every Nickname-format source is a column, so no name should have been resolved.'
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
	 * The visible name is ASSEMBLED from the profile fields, never subtracted from the column.
	 *
	 * Every row below is a shape the stored `display_name` drifts into - an initial welded to the
	 * surname, a generational suffix, a de-duplication digit, an honorific, a hyphenated surname, a
	 * member whose two name parts are the same string, a column that spells a different name
	 * entirely. Removing the hidden part from those strings is not decidable: a surname sits inside
	 * unrelated words ("Ng" in "Armstrong", "Ann" in "Cann") as readily as it is the name being
	 * hidden, and every rule that separated the two was load-bearing for one shape and wrong for
	 * another (PROD-9896).
	 *
	 * So the column is not consulted at all. One assertion covers both failure directions at once:
	 * the answer must be exactly the field the viewer may see - which no drifted shape can leak
	 * into, and from which no coincidental token can be stripped out.
	 *
	 * Asserted for a guest AND for a logged-in stranger, because the two reach it by different
	 * paths (the function body and xprofile_filter_get_user_display_name()) and used to disagree.
	 *
	 * @group bb_name_redaction
	 */
	public function test_visible_name_is_rebuilt_from_the_fields_not_the_stored_column() {
		global $wpdb;

		$cases = array(
			// Label, first name, hidden last name, stored display_name.
			array( 'initial welded to the surname', 'Peter', 'Zebrastripe', 'pzebrastripe' ),
			array( 'suffix welded on', 'Peter', 'Zebrastripe', 'PeterZebrastripeJr' ),
			array( 'de-duplication digit', 'Peter', 'Zebrastripe', 'Zebrastripe2' ),
			array( 'honorific prefix', 'Peter', 'Zebrastripe', 'MrPeterZebrastripe' ),
			array( 'generational suffix on a short surname', 'Smith', 'Ann', 'Smith AnnJr' ),
			array( 'surname is a fragment of an unrelated word', 'Louis', 'Ng', 'Louis Armstrong' ),
			array( 'hyphenated surname', 'Anna', 'Smith', 'Anna Smith-Jones' ),
			array( 'both name parts are the same string', 'Alexis', 'Alexis', 'Alexis Alexis' ),
			array( 'column spells a different name entirely', 'Reginald', 'Quenlingham', 'Reggiebert Quenlingham' ),
			array( 'column is nothing but the hidden surname', 'Wendy', 'Zebrastripe', 'Zebrastripe' ),
		);

		bp_update_option( 'bp-display-name-format', 'first_last_name' );
		$viewer = self::factory()->user->create();

		foreach ( $cases as $case ) {
			list( $label, $first_name, $last_name, $stored ) = $case;

			$user_id = self::factory()->user->create();

			xprofile_set_field_data( bp_xprofile_firstname_field_id(), $user_id, $first_name );
			xprofile_set_field_data( bp_xprofile_lastname_field_id(), $user_id, $last_name );
			xprofile_set_field_visibility_level( bp_xprofile_lastname_field_id(), $user_id, 'adminsonly' );

			$wpdb->update( $wpdb->users, array( 'display_name' => $stored ), array( 'ID' => $user_id ) );
			clean_user_cache( $user_id );
			wp_cache_flush();

			$this->assertSame(
				$first_name,
				(string) bp_core_get_user_displayname( $user_id, 0 ),
				sprintf( 'Guest view was not the visible field for the "%s" shape.', $label )
			);

			$this->assertSame(
				$first_name,
				(string) bp_core_get_user_displayname( $user_id, $viewer ),
				sprintf( 'Member view was not the visible field for the "%s" shape.', $label )
			);
		}
	}

	/**
	 * With no permitted name part left, the label falls back and never to the stored column.
	 *
	 * A member whose only stored name is the hidden one has nothing to assemble, so the answer is
	 * the nickname - which carries no hidden name part - and then the public user_nicename. The
	 * drifted column must not be reached at any point in that chain.
	 *
	 * @group bb_name_redaction
	 */
	public function test_a_fully_redacted_name_falls_back_without_reaching_the_column() {
		global $wpdb;

		bp_update_option( 'bp-display-name-format', 'first_last_name' );

		// Nickname present: it is the first fallback.
		$with_nickname = self::factory()->user->create( array( 'nickname' => 'quietquill' ) );
		xprofile_set_field_data( bp_xprofile_firstname_field_id(), $with_nickname, '' );
		xprofile_set_field_data( bp_xprofile_lastname_field_id(), $with_nickname, 'Zebrastripe' );
		xprofile_set_field_visibility_level( bp_xprofile_lastname_field_id(), $with_nickname, 'adminsonly' );
		delete_user_meta( $with_nickname, 'first_name' );
		xprofile_set_field_data( bp_xprofile_nickname_field_id(), $with_nickname, 'quietquill' );
		update_user_meta( $with_nickname, 'nickname', 'quietquill' );
		$wpdb->update( $wpdb->users, array( 'display_name' => 'Peter Zebrastripe' ), array( 'ID' => $with_nickname ) );
		clean_user_cache( $with_nickname );
		wp_cache_flush();

		$this->assertSame(
			'quietquill',
			(string) bp_core_get_user_displayname( $with_nickname, 0 ),
			'A member with nothing visible must fall back to the nickname.'
		);

		// No nickname at all: the chain ends at the public user_nicename.
		$no_nickname = self::factory()->user->create( array( 'user_nicename' => 'silent-quill' ) );
		xprofile_set_field_data( bp_xprofile_firstname_field_id(), $no_nickname, '' );
		xprofile_set_field_data( bp_xprofile_lastname_field_id(), $no_nickname, 'Zebrastripe' );
		xprofile_set_field_visibility_level( bp_xprofile_lastname_field_id(), $no_nickname, 'adminsonly' );
		delete_user_meta( $no_nickname, 'first_name' );
		xprofile_set_field_data( bp_xprofile_nickname_field_id(), $no_nickname, '' );
		delete_user_meta( $no_nickname, 'nickname' );
		$wpdb->update( $wpdb->users, array( 'display_name' => 'Peter Zebrastripe' ), array( 'ID' => $no_nickname ) );
		clean_user_cache( $no_nickname );
		wp_cache_flush();

		$this->assertSame(
			'silent-quill',
			(string) bp_core_get_user_displayname( $no_nickname, 0 ),
			'With no nickname the chain must end at user_nicename, never at the stored column.'
		);
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
	 * The profile-field producer must read the guest sentinel the same way its sibling does.
	 *
	 * bb_core_guest_viewer_id() (-1) marks "resolve this for an audience that is provably not a
	 * member". It is a NON-EMPTY id, so a bare truthy test reads it as a logged-in member.
	 * bb_xprofile_filter_user_search_matches() excludes it before bp_user_can(); this producer did
	 * not, so the sentinel was handed to a capability check for a user row that does not exist.
	 *
	 * Harmless in itself - bp_user_can() answers false for a non-existent user - but the two
	 * filters are OR'd into one search and must not disagree about who a guest is. Asserted in both
	 * directions so an over-broad guard cannot pass either: the sentinel is denied a members-only
	 * field, and a real logged-in member still gets it.
	 *
	 * @group bb_search_visibility_display_format
	 */
	public function test_field_search_guest_sentinel_is_treated_as_logged_out() {
		$user_id      = $this->create_member_with_hidden_surname( 'loggedin', 'Fenwicke', 'Ottoline' );
		$matched_data = array( $this->field_match_row( $user_id, bp_xprofile_lastname_field_id() ) );

		// Record every capability check the filter performs. The guard's whole effect is that the
		// sentinel never reaches one - the RESULT is identical either way, because bp_user_can()
		// answers false for a user row that does not exist, so asserting only the returned ids
		// would pass with the guard removed and prove nothing.
		$checked = array();
		$spy     = function ( $retval, $user_id ) use ( &$checked ) {
			$checked[] = (int) $user_id;

			return $retval;
		};
		add_filter( 'bp_user_can', $spy, 10, 2 );

		try {
			$result = bb_xprofile_filter_field_search_matches( array( $user_id ), $matched_data, bb_core_guest_viewer_id() );
		} finally {
			remove_filter( 'bp_user_can', $spy, 10 );
		}

		$this->assertSame(
			array(),
			$result,
			'A members-only field answered a profile-field search resolved for an explicitly anonymous audience.'
		);

		$this->assertNotContains(
			(int) bb_core_guest_viewer_id(),
			$checked,
			'The guest sentinel was passed to a capability check for a user row that does not exist.'
		);

		$member = self::factory()->user->create();
		$this->assertSame(
			array( $user_id ),
			bb_xprofile_filter_field_search_matches( array( $user_id ), $matched_data, $member ),
			'A logged-in viewer was wrongly denied a members-only field they may read.'
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
	 * A first name stored only in WordPress user-meta keeps the member findable, for every viewer.
	 *
	 * The imported/unhealed member shape here: the name lives in user_meta + wp_users.display_name,
	 * the xprofile First Name field is empty, and there is no stored surname. The visible name is
	 * assembled from the first-name source the format shows, and that source falls back to the user
	 * meta exactly as bp_xprofile_get_member_display_name() does - so guest and member resolve the
	 * same name, and a search for it must keep the member rather than silently losing every
	 * unhealed member from the directory.
	 *
	 * That column is compared by bb_xprofile_get_format_visible_name_matches(), so the answer costs
	 * no name resolution at all.
	 *
	 * @group bb_search_visibility_display_format
	 */
	public function test_format_hide_keeps_a_first_name_stored_only_in_user_meta() {
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

		// Prove the fixture without persisting the self-heal, which would populate the field and
		// make the member trivially findable regardless of the behaviour under test.
		bb_xprofile_is_display_name_self_heal_suspended( true );
		try {
			$this->assertSame(
				'Grimwald',
				bp_core_get_user_displayname( $user_id, $member ),
				'Fixture: a logged-in viewer resolves the member to the user-meta first name.'
			);
			$this->assertSame(
				'Grimwald',
				bp_core_get_user_displayname( $user_id, 0 ),
				'A guest must resolve the same name as a member - the surname is what the format hides, not the first name.'
			);
		} finally {
			bb_xprofile_is_display_name_self_heal_suspended( false );
		}
		$this->assertSame(
			'',
			(string) xprofile_get_field_data( bp_xprofile_firstname_field_id(), $user_id ),
			'Fixture: resolving a name for a search must not have persisted the back-filled first name.'
		);

		foreach ( array( $member, 0 ) as $viewer_id ) {
			list( $result, $resolutions ) = $this->count_name_resolutions(
				function () use ( $user_id, $viewer_id ) {
					return bb_xprofile_filter_user_search_matches( array( $user_id ), array( '%Grimwald%' ), $viewer_id );
				}
			);

			$this->assertContains(
				$user_id,
				$result,
				'A member whose first name lives only in user_meta was dropped from the results.'
			);
			$this->assertSame( 0, $resolutions, 'The user-meta first name is a column, so no name should have been resolved.' );
		}

		$this->assertSame(
			array(),
			bb_xprofile_filter_user_search_matches( array( $user_id ), array( '%Ashforth%' ), 0 ),
			'The surname the format hides still answered a search.'
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

	/**
	 * An unhealed member's restricted surname must not survive in the resolved name.
	 *
	 * bp_xprofile_get_member_display_name() back-fills a name field that has no stored row from the
	 * WordPress user meta, and returns that value whether or not the repair was persisted - a member
	 * search suspends the write while still resolving. xprofile_filter_get_user_display_name() then
	 * decided whether to strip the surname by re-reading the FIELD, a second and independent
	 * resolution of the same value: on an imported member who never re-saved their profile the field
	 * is empty while the resolved name carries the surname, so the strip was skipped and a logged-in
	 * searcher could confirm a restricted surname by searching it (PROD-9896 review finding).
	 *
	 * @group bb_search_visibility_display_format
	 */
	public function test_unhealed_member_restricted_last_name_is_stripped() {
		global $wpdb;
		$bp = buddypress();

		$viewer  = self::factory()->user->create();
		$user_id = self::factory()->user->create( array( 'nickname' => 'ravennick' ) );

		wp_update_user(
			array(
				'ID'           => $user_id,
				'first_name'   => 'Anneliese',
				'last_name'    => 'Ravenscroft',
				'display_name' => 'Anneliese Ravenscroft',
			)
		);

		xprofile_set_field_data( bp_xprofile_firstname_field_id(), $user_id, 'Anneliese' );
		xprofile_set_field_visibility_level( bp_xprofile_lastname_field_id(), $user_id, 'adminsonly' );

		// The unhealed shape: the surname lives only in user meta and the stored column.
		$wpdb->delete( $bp->profile->table_name_data, array( 'field_id' => bp_xprofile_lastname_field_id(), 'user_id' => $user_id ) );
		wp_cache_flush();

		bp_update_option( 'bp-display-name-format', 'first_last_name' );
		$this->set_current_user( $viewer );

		$this->assertSame(
			'',
			(string) xprofile_get_field_data( bp_xprofile_lastname_field_id(), $user_id ),
			'Fixture: the Last Name field must have no stored row.'
		);

		// The resolution exactly as the search re-test performs it, with the repair suspended.
		bb_xprofile_is_display_name_self_heal_suspended( true );
		try {
			$suspended_name = bp_core_get_user_displayname( $user_id, $viewer );
		} finally {
			bb_xprofile_is_display_name_self_heal_suspended( false );
		}

		$this->assertSame(
			'Anneliese',
			(string) $suspended_name,
			'A viewer denied the surname was served it while the self-heal was suspended.'
		);

		$this->assertSame(
			array(),
			bb_xprofile_filter_user_search_matches( array( $user_id ), array( '%Ravenscroft%' ), $viewer ),
			'The restricted surname still answered a member search.'
		);

		// The guest path resolves in the function body rather than through this filter; it already
		// failed closed and must keep doing so.
		$this->assertStringNotContainsString(
			'Ravenscroft',
			(string) bp_core_get_user_displayname( $user_id, 0 ),
			'A guest was served the restricted surname.'
		);
	}

	/**
	 * The counterpart: an unhealed member with NOTHING restricted keeps their whole name.
	 *
	 * The fix above reads the user-meta surname when the field is empty. That must feed the strip
	 * decision only when the field is actually hidden - it must not start redacting members whose
	 * name nobody restricted, which would take the surname off every imported member.
	 *
	 * @group bb_search_visibility_display_format
	 */
	public function test_unhealed_member_without_restriction_keeps_full_name() {
		global $wpdb;
		$bp = buddypress();

		$viewer  = self::factory()->user->create();
		$user_id = self::factory()->user->create( array( 'nickname' => 'holtnick' ) );

		wp_update_user(
			array(
				'ID'           => $user_id,
				'first_name'   => 'Bartholomew',
				'last_name'    => 'Holtsworthy',
				'display_name' => 'Bartholomew Holtsworthy',
			)
		);

		xprofile_set_field_data( bp_xprofile_firstname_field_id(), $user_id, 'Bartholomew' );
		xprofile_set_field_visibility_level( bp_xprofile_lastname_field_id(), $user_id, 'public' );

		$wpdb->delete( $bp->profile->table_name_data, array( 'field_id' => bp_xprofile_lastname_field_id(), 'user_id' => $user_id ) );
		wp_cache_flush();

		bp_update_option( 'bp-display-name-format', 'first_last_name' );
		$this->set_current_user( $viewer );

		$this->assertSame(
			'Bartholomew Holtsworthy',
			(string) bp_core_get_user_displayname( $user_id, $viewer ),
			'An unhealed member with nothing hidden lost a name part they are allowed to show.'
		);

		$this->assertSame(
			array( $user_id ),
			bb_xprofile_filter_user_search_matches( array( $user_id ), array( '%Holtsworthy%' ), $viewer ),
			'A visible surname was wrongly dropped from the search results.'
		);
	}

	/**
	 * The explicit guest viewer must be treated as logged OUT, not as member -1.
	 *
	 * bb_core_guest_viewer_id() is the marker for "resolve this for an audience that is provably not
	 * a member" (an invitation email to a plain address, say). It is a non-empty id, so a bare
	 * truthy test reads it as a logged-in member and drops 'loggedin' from the hidden set - which
	 * under-protects a "Logged-in Users only" name field for exactly the audience that must not see
	 * it. bp_xprofile_get_hidden_field_types_for_user() has always checked the sentinel; this
	 * producer did not (PROD-9896 review finding).
	 *
	 * @group bb_search_visibility_display_format
	 */
	public function test_guest_sentinel_viewer_is_treated_as_logged_out() {
		$user_id = $this->create_member_with_hidden_surname( 'loggedin', 'Marchbanks', 'Evelina' );

		bp_update_option( 'bp-display-name-format', 'first_last_name' );
		$this->set_current_user( 0 );

		$this->assertSame(
			array(),
			bb_xprofile_filter_user_search_matches( array( $user_id ), array( '%Marchbanks%' ), bb_core_guest_viewer_id() ),
			'A members-only surname answered a search resolved for an explicitly anonymous audience.'
		);

		// A logged-in member may see a 'loggedin' field, so the same search must still find them.
		$member = self::factory()->user->create();
		$this->assertSame(
			array( $user_id ),
			bb_xprofile_filter_user_search_matches( array( $user_id ), array( '%Marchbanks%' ), $member ),
			'A logged-in viewer was wrongly denied a members-only name they may read.'
		);
	}

	/**
	 * An email token must resolve the named member for the RECIPIENT, not the request actor.
	 *
	 * Email bodies are built once, in the request of whoever triggered the send, and delivered to
	 * someone else. A name resolved against the actor is never redacted - nobody is denied their own
	 * name - so the full name landed in every recipient's inbox even when the site withholds it from
	 * them on screen. groups_notification_group_invites() now passes the invitee as the viewer.
	 *
	 * This covers the email/notification fan-out paths, which had no test (PROD-9896 review finding).
	 *
	 * @group bb_search_visibility_display_format
	 */
	public function test_email_token_resolves_the_name_for_the_recipient() {
		$inviter = $this->create_member_with_hidden_surname( 'adminsonly', 'Wolstenholme', 'Prudence' );
		$invitee = self::factory()->user->create();

		bp_update_option( 'bp-display-name-format', 'first_last_name' );

		// bp_send_email() returns a WP_Error before it ever sets tokens when the bp-email posts are
		// absent, and WP's test suite wipes them. Re-establish them the way BP_UnitTestCase_Emails
		// does - bp_core_install_emails() lives in the admin schema file, which is not loaded here.
		if ( is_wp_error( bp_get_email( 'groups-invitation' ) ) ) {
			require_once buddypress()->plugin_dir . '/bp-core/admin/bp-core-admin-schema.php';
			bp_core_install_emails();
		}

		$captured = array();
		$capture  = function ( $formatted_tokens ) use ( &$captured ) {
			$captured[] = $formatted_tokens;

			return $formatted_tokens;
		};
		// Stop the send once the tokens exist: set_tokens() runs before validate().
		$halt = function () {
			return new WP_Error( 'bb_test_halt', 'halted' );
		};

		add_filter( 'bp_email_set_tokens', $capture );
		add_filter( 'bp_email_validate', $halt, 99 );

		$group_id = $this->factory->group->create( array( 'creator_id' => $inviter ) );
		$group    = groups_get_group( $group_id );
		// groups_notification_group_invites() accepts a BP_Groups_Member or a plain user id.
		$member = (int) $invitee;

		// The actor is the inviter - the context that made the name unredacted before the fix.
		$this->set_current_user( $inviter );
		groups_notification_group_invites( $group, $member, $inviter );

		remove_filter( 'bp_email_set_tokens', $capture );
		remove_filter( 'bp_email_validate', $halt, 99 );

		$inviter_names = array();
		foreach ( $captured as $tokens ) {
			foreach ( $tokens as $key => $value ) {
				if ( false !== strpos( (string) $key, 'inviter.name' ) ) {
					$inviter_names[] = (string) $value;
				}
			}
		}

		$this->assertNotEmpty( $inviter_names, 'Fixture: the invite email must carry an inviter.name token.' );

		foreach ( $inviter_names as $name ) {
			$this->assertStringNotContainsString(
				'Wolstenholme',
				$name,
				'The invite email carried a surname the recipient is denied on screen.'
			);
			$this->assertStringContainsString(
				'Prudence',
				$name,
				'The visible part of the inviter name was lost as well.'
			);
		}
	}
	/**
	 * Re-testing a batch of search candidates must not cost a user-meta query per member.
	 *
	 * The visible name is assembled from the profile fields, and each field falls back to the
	 * WordPress user meta when it has no stored row - the normal state of an imported member, the
	 * shape this install has ~70,000 of. The search re-test resolves a name for up to
	 * `bb_xprofile_user_search_visibility_candidate_limit` candidates on a request an anonymous
	 * visitor can issue, and unlike bp_core_get_user_displaynames() it does not warm the WP user
	 * caches itself - so bb_core_prime_user_displayname_caches() has to, or every candidate costs
	 * its own query before a single result is rendered.
	 *
	 * Two disjoint batches of different sizes are measured so the fixed cost cancels out and only
	 * the per-member cost is left. Only user/usermeta reads are counted: other per-member work in
	 * the resolution (the moderation suspend check, for one) is pre-existing and is not what
	 * priming the name sources is answerable for, so counting everything would measure someone
	 * else's query and make this test unfalsifiable for its own subject.
	 *
	 * @group bb_search_visibility_display_format
	 */
	public function test_search_re_test_primes_the_name_sources_for_the_whole_batch() {
		global $wpdb;

		bp_update_option( 'bp-display-name-format', 'first_last_name' );
		$this->set_current_user( 0 );

		$make_batch = function ( $size, $tag ) use ( $wpdb ) {
			$ids = array();
			for ( $i = 0; $i < $size; $i++ ) {
				$id = self::factory()->user->create();
				// The unhealed shape: no xprofile name rows, the name only in user meta and the
				// stored column. The restricted surname is what makes them search candidates.
				xprofile_set_field_data( bp_xprofile_firstname_field_id(), $id, '' );
				xprofile_set_field_data( bp_xprofile_lastname_field_id(), $id, '' );
				update_user_meta( $id, 'first_name', $tag . $i );
				update_user_meta( $id, 'last_name', 'Hollowmere' );
				xprofile_set_field_visibility_level( bp_xprofile_lastname_field_id(), $id, 'adminsonly' );
				$wpdb->update( $wpdb->users, array( 'display_name' => $tag . $i . ' Hollowmere' ), array( 'ID' => $id ) );
				clean_user_cache( $id );
				$ids[] = $id;
			}

			return $ids;
		};

		$small = $make_batch( 2, 'Smallmember' );
		$large = $make_batch( 8, 'Largemember' );

		$measure = function ( $ids ) use ( $wpdb ) {
			wp_cache_flush();

			$counted = 0;
			$counter = function ( $query ) use ( &$counted, $wpdb ) {
				if ( false !== strpos( $query, $wpdb->usermeta ) || false !== strpos( $query, $wpdb->users ) ) {
					++$counted;
				}

				return $query;
			};

			add_filter( 'query', $counter );
			bb_xprofile_filter_user_search_matches( $ids, array( '%Hollowmere%' ), 0 );
			remove_filter( 'query', $counter );

			return $counted;
		};

		// Fixture: these members really do resolve from the user meta, so the reads under test
		// happen at all.
		wp_cache_flush();
		$this->assertSame(
			'Smallmember0',
			(string) bp_core_get_user_displayname( $small[0], 0 ),
			'Fixture: the name must resolve from the user meta.'
		);

		$small_queries = $measure( $small );
		$large_queries = $measure( $large );

		// Four times the candidates, the same number of user/usermeta reads: they are answered for
		// the whole batch up front, not per member.
		$this->assertSame(
			$small_queries,
			$large_queries,
			sprintf(
				'Re-testing %d candidates cost %d user/usermeta queries against %d for %d - the name sources are not being primed for the batch.',
				count( $large ),
				$large_queries,
				$small_queries,
				count( $small )
			)
		);
	}
}
