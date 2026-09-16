<?php
/**
 * Profile-field visibility applied to member search and name resolution.
 *
 * A member search must not confirm the contents of a name or profile field the
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
	 * Display Name Fields "Last Name" toggle backup.
	 *
	 * @var mixed
	 */
	protected $hide_last_name_backup;

	/**
	 * Display Name Fields "First Name" toggle backup, for the "Nickname" format.
	 *
	 * @var mixed
	 */
	protected $hide_nickname_first_name_backup;

	/**
	 * The harness default is the "First Name" format, under which the surname is not part of any
	 * member's visible name and every assertion below about surname visibility would be decided by
	 * the format rather than by the field's visibility level. Pin the "First Name & Last Name"
	 * format so these tests exercise what they claim to.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->format_backup                   = bp_get_option( 'bp-display-name-format' );
		$this->fn_default_backup               = bp_xprofile_get_meta( bp_xprofile_firstname_field_id(), 'field', 'default_visibility' );
		$this->fn_allow_backup                 = bp_xprofile_get_meta( bp_xprofile_firstname_field_id(), 'field', 'allow_custom_visibility' );
		// Read with a null default so an option that was never stored is restored by deleting it:
		// both toggles fall back to "shown" only while the row is absent, and writing back the ''
		// that bp_get_option() otherwise returns would flip that default for every later test.
		$this->hide_last_name_backup           = bp_get_option( 'bp-hide-last-name', null );
		$this->hide_nickname_first_name_backup = bp_get_option( 'bp-hide-nickname-first-name', null );

		bp_update_option( 'bp-display-name-format', 'first_last_name' );
	}

	/**
	 * Restore the options the harness started with.
	 */
	public function tearDown(): void {
		bp_update_option( 'bp-display-name-format', $this->format_backup );
		$this->restore_option( 'bp-hide-last-name', $this->hide_last_name_backup );
		$this->restore_option( 'bp-hide-nickname-first-name', $this->hide_nickname_first_name_backup );
		bp_xprofile_update_meta( bp_xprofile_firstname_field_id(), 'field', 'default_visibility', $this->fn_default_backup );
		bp_xprofile_update_meta( bp_xprofile_firstname_field_id(), 'field', 'allow_custom_visibility', $this->fn_allow_backup );
		wp_cache_delete( 'default_visibility_levels', 'bp_xprofile' );

		parent::tearDown();
	}

	/**
	 * Put an option back exactly as it was found, absent row included.
	 *
	 * @param string $option_name Option key.
	 * @param mixed  $value       Value read before the test, null when the row did not exist.
	 */
	protected function restore_option( $option_name, $value ) {
		if ( is_null( $value ) ) {
			bp_delete_option( $option_name );
			return;
		}

		bp_update_option( $option_name, $value );
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
	 * exhaustion, so the bound is asserted by counting resolutions, not by timing.
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
	 * The budget also bounds the per-user visibility sources, and past it the leg is WITHHELD.
	 *
	 * Sources (1) and (2) are bounded by stored rows rather than by the search term, which is not
	 * the same as small: on a community where a name field's default visibility is restricted, or
	 * where many members restrict their own, every matched member carries a row and the re-test is
	 * as large as the match count again. Exercised under the "First Name & Last Name" format so
	 * the site-wide format source is not involved at all.
	 *
	 * The answer past the bound is nothing at all, never a slice. Every member in this fixture is
	 * legitimately visible - their first name matches the term - so a truncating bound returns two
	 * of them and drops the other two purely for their POSITION in an unordered candidate set. That
	 * is indistinguishable from a complete answer, which is what made the same shape a 99.3% result
	 * cull on a populated community. An empty answer is at least honest about having withheld
	 * everything, and the caller ORs this comparison with user_login, user_nicename, user_email,
	 * user meta and the profile fields, so the search still answers.
	 *
	 * @group bb_search_visibility_display_format
	 */
	public function test_candidate_budget_also_bounds_the_per_user_visibility_sources() {
		$candidate_ids = array();
		for ( $i = 0; $i < 4; $i++ ) {
			// Restricted surname, but a FIRST name that matches the term - so the re-test keeps
			// them, and a truncated re-test is observable as a member going missing.
			$candidate_ids[] = $this->create_member_with_hidden_surname( 'adminsonly', 'Underbough' . $i, 'Underbough' );
		}

		// Matches the same term with nothing restricted at all, so no source can make them a
		// candidate. Past the budget this member must still be served: the answer is the candidate
		// set, never false, and a false would take the caller's whole display_name leg down with it.
		$non_candidate_id = $this->create_member_with_hidden_surname( 'public', 'Underbough9', 'Underbough' );
		$user_ids         = array_merge( $candidate_ids, array( $non_candidate_id ) );

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

		// Fixture: exactly the four restricted members are candidates, so the assertion past the
		// budget can tell "withhold the candidates" apart from "withhold the leg".
		$candidates = bb_xprofile_get_hidden_name_search_user_ids( array( '%Underbough%' ), 0 );
		$this->assertIsArray( $candidates, 'Fixture: the candidate set could not be resolved.' );
		$this->assertNotContains(
			(int) $non_candidate_id,
			array_map( 'intval', $candidates ),
			'Fixture: the unrestricted member is a candidate, so nothing below distinguishes the two answers.'
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

		$this->assertSame(
			array( (int) $non_candidate_id ),
			array_map( 'intval', $result ),
			'Past the budget the candidates must be withheld and nothing else - not a slice of them, and not the caller\'s whole display_name leg.'
		);
		$this->assertSame( 0, $resolutions, 'The budget was exceeded, so no name should have been resolved at all.' );
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
	 * destructive write per candidate, against members who are not even in the results.
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
	 * the_author / document_title_parts / rest_prepare_user returns the RAW column.
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
	 * another.
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
	 * in its format-hide arm survived. Without it a guest can confirm the contents of
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
	 * The mutation-surviving arm: the site-wide Display Name Format hide.
	 *
	 * The Last Name field here is PUBLIC with no visibility row of any kind, so the only thing that
	 * can withhold it is the Display Name Fields toggle the "First Name" format exposes. The format
	 * alone is not that decision: "bp-hide-last-name" reads as 1 = SHOW, and on its default the
	 * Last Name field stays on the public profile while merely sitting outside the display name, so
	 * a surname-only match is a hit on a field the community publishes and must survive. Turn the
	 * toggle off and the field is genuinely withheld, and the same match must then be dropped or it
	 * discloses exactly the part the profile suppresses. The "First Name & Last Name" run is the
	 * outer negative control - that format offers no toggle at all.
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
		bp_update_option( 'bp-hide-last-name', 1 );
		$this->assertSame(
			array( $user_id ),
			bb_xprofile_filter_field_search_matches( array( $user_id ), $matched_data, 0 ),
			'The Last Name field is still rendered on the profile under this format, so the match must be kept.'
		);

		bp_update_option( 'bp-hide-last-name', 0 );
		$this->assertSame(
			array(),
			bb_xprofile_filter_field_search_matches( array( $user_id ), $matched_data, 0 ),
			'The withheld Last Name field was not applied by the profile-field search producer.'
		);
	}

	/**
	 * Under "Nickname" the visible name is the nickname alone, and the format exposes a toggle for
	 * the First Name field as well. On its default ("bp-hide-nickname-first-name" = 1 = SHOW) the
	 * field is still published on the profile, so a first-name-only match is legitimate; switch it
	 * off and the field is withheld, and the same match must be dropped - the arm that force-adds
	 * the first-name field to the hidden set under this format.
	 *
	 * @group bb_search_visibility_display_format
	 */
	public function test_field_search_nickname_format_drops_a_first_name_only_match() {
		$user_id      = $this->create_member_with_hidden_surname( 'public', 'Nettleby', 'Percival' );
		$matched_data = array( $this->field_match_row( $user_id, bp_xprofile_firstname_field_id() ) );

		bp_update_option( 'bp-display-name-format', 'nickname' );
		$this->set_current_user( 0 );

		bp_update_option( 'bp-hide-nickname-first-name', 1 );
		$this->assertSame(
			array( $user_id ),
			bb_xprofile_filter_field_search_matches( array( $user_id ), $matched_data, 0 ),
			'The First Name field is still rendered on the profile under this format, so the match must be kept.'
		);

		bp_update_option( 'bp-hide-nickname-first-name', 0 );
		$this->assertSame(
			array(),
			bb_xprofile_filter_field_search_matches( array( $user_id ), $matched_data, 0 ),
			'The Nickname format withholds the first name, but a first-name-only field match survived.'
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
	 * All in One SEO before this test was written.
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
	 * raw surname on an anonymous author-archive request.
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
	 * Rank Math's Slack enhanced-sharing tags must not carry a hidden surname.
	 *
	 * These are emitted as `twitter:label1` / `twitter:data1`, never as JSON-LD, so the graph
	 * filter this class already covers does not see them. On an author archive
	 * RankMath\OpenGraph\Slack::get_author_data() reads `$author->display_name` straight off the
	 * queried object - a raw property read no WordPress filter reaches - and prints it under the
	 * label `Name`, so the surname went out on a page whose `<title>`, `og:title`,
	 * `twitter:title` and JSON-LD were all correctly withheld.
	 *
	 * The tag NAMES are built with sprintf( 'twitter:data%d', ... ), which is why grepping that
	 * plugin for the literal tag finds nothing and the surface was missed.
	 *
	 * Asserted through apply_filters() rather than by calling the callback, because the defect was
	 * a MISSING REGISTRATION, not a broken redactor - a test that called redact_schema_graph()
	 * directly would have passed against the unfixed code.
	 *
	 * @group bb_search_visibility_display_format
	 */
	public function test_seo_plugin_slack_enhanced_data_is_redacted() {
		$this->setExpectedIncorrectUsage( 'WP_Block_Type_Registry::register' );

		$user_id = $this->create_member_with_hidden_surname( 'loggedin', 'Fennimore', 'Rosalind' );
		$reader  = self::factory()->user->create();

		$this->set_current_user( 0 );
		$this->go_to( get_author_posts_url( $user_id ) );

		require_once buddypress()->compatibility_dir . '/class-bb-seo-helpers.php';
		BB_SEO_Helpers::instance();

		// Exactly the shape Slack::get_author_data() builds: a flat label => value map whose
		// second entry is an integer, so the redactor must leave both labels and the count alone.
		$payload = array(
			'Name'  => 'Rosalind Fennimore',
			'Posts' => 7,
		);

		// phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores -- Rank Math's own hook name; it is not ours to rename.
		$guest = apply_filters( 'rank_math/opengraph/slack_enhanced_data', $payload );

		$this->assertStringNotContainsString(
			'Fennimore',
			wp_json_encode( $guest ),
			'The Slack enhanced-data tags published a surname hidden from a logged-out visitor.'
		);
		$this->assertSame(
			'Rosalind',
			$guest['Name'],
			'The name should be the visible name, not blank or mangled.'
		);
		$this->assertSame( 7, $guest['Posts'], 'A non-name value must not be rewritten.' );
		$this->assertSame(
			array( 'Name', 'Posts' ),
			array_keys( $guest ),
			'The labels are array KEYS and must survive untouched, or the tag pairs stop matching.'
		);

		// Negative control: a viewer the surname is NOT hidden from must keep it. Without this the
		// test would pass just as well against a redactor that blanked every name it was handed.
		$this->set_current_user( $reader );

		// phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores -- Rank Math's own hook name; it is not ours to rename.
		$permitted = apply_filters( 'rank_math/opengraph/slack_enhanced_data', $payload );

		$this->assertSame(
			'Rosalind Fennimore',
			$permitted['Name'],
			'A logged-in viewer is permitted the surname and must not be over-stripped.'
		);
	}

	/**
	 * An SEO plugin's graph must be redacted when there is no main query to identify the member.
	 *
	 * Yoast serialises the same head it prints on a page into the REST API - `yoast_head` and
	 * `yoast_head_json` on wp/v2/users and wp/v2/posts, both readable with no authentication. On a
	 * REST request is_author() and is_singular() are both false, so the member could not be
	 * identified from the query and the graph went out with the raw column in it: an unauthenticated
	 * GET /wp-json/wp/v2/users/<id> returned "name":"Alex Quillfeather" for a member whose surname
	 * the same site hides on every page.
	 *
	 * The member is taken from the plugin's own context object instead, which is what makes this
	 * work on REST and on a collection response where each item has a different author.
	 *
	 * @group bb_search_visibility_display_format
	 */
	public function test_seo_plugin_schema_graph_is_redacted_without_a_query() {
		$user_id = $this->create_member_with_hidden_surname( 'loggedin', 'Quillfeather', 'Alex' );

		$this->set_current_user( 0 );

		require_once buddypress()->compatibility_dir . '/class-bb-seo-helpers.php';
		$helper = BB_SEO_Helpers::instance();

		// No go_to(): this is the REST shape, where nothing has been queried.
		$this->assertFalse( is_author(), 'The test must run with no author query for this to mean anything.' );
		$this->assertFalse( is_singular(), 'The test must run with no singular query for this to mean anything.' );

		// Stand in for Yoast's Meta_Tags_Context: an object whose `indexable` names the entity.
		$context                         = new stdClass();
		$context->indexable              = new stdClass();
		$context->indexable->object_type = 'user';
		$context->indexable->object_id   = $user_id;

		$graph = array(
			array(
				'@type' => 'Person',
				'name'  => 'Alex Quillfeather',
			),
			array(
				'@type' => 'ProfilePage',
				'name'  => 'Alex Quillfeather - Test Site',
			),
		);

		$redacted = $helper->redact_schema_graph( $graph, $context );

		$this->assertStringNotContainsString(
			'Quillfeather',
			wp_json_encode( $redacted ),
			'A REST-rendered schema graph carried a surname hidden from the requester.'
		);
		$this->assertSame(
			'Alex',
			$redacted[0]['name'],
			'The Person name should be the visible name, not blank or mangled.'
		);
		$this->assertSame(
			'Alex - Test Site',
			$redacted[1]['name'],
			'Only the member name should be replaced; the rest of the plugin template must survive.'
		);
	}

	/**
	 * The same graph must keep the full name for a viewer who is allowed to see it.
	 *
	 * The over-strip guard for the context path. Identifying the member from the plugin's own
	 * context must not make the redaction unconditional.
	 *
	 * @group bb_search_visibility_display_format
	 */
	public function test_seo_plugin_schema_graph_keeps_name_for_permitted_viewer() {
		$user_id = $this->create_member_with_hidden_surname( 'loggedin', 'Quillfeather', 'Alex' );
		$viewer  = self::factory()->user->create();

		$this->set_current_user( $viewer );

		require_once buddypress()->compatibility_dir . '/class-bb-seo-helpers.php';
		$helper = BB_SEO_Helpers::instance();

		$context                         = new stdClass();
		$context->indexable              = new stdClass();
		$context->indexable->object_type = 'user';
		$context->indexable->object_id   = $user_id;

		$graph = array(
			array(
				'@type' => 'Person',
				'name'  => 'Alex Quillfeather',
			),
		);

		$redacted = $helper->redact_schema_graph( $graph, $context );

		$this->assertSame(
			'Alex Quillfeather',
			$redacted[0]['name'],
			'A logged-in member may see a surname hidden only from logged-out visitors; it must not be stripped.'
		);

		$this->set_current_user( 0 );
	}

	/**
	 * An SEO plugin's author meta tag must not carry a name part hidden from the viewer.
	 *
	 * Yoast's Meta_Author_Presenter reads $user_data->display_name - the WP_User property, which no
	 * WordPress filter reaches - and prints it as <meta name="author">. The schema graph and the
	 * document title were redacted while this tag was not, so a guest page source still carried the
	 * full name. The plugin offers `wpseo_meta_author`, which is what
	 * BB_SEO_Helpers hooks.
	 *
	 * @group bb_search_visibility_display_format
	 */
	public function test_seo_plugin_author_meta_tag_is_redacted() {
		$user_id = $this->create_member_with_hidden_surname( 'loggedin', 'Quillfeather', 'Alex' );
		$post_id = self::factory()->post->create( array( 'post_author' => $user_id ) );

		$this->set_current_user( 0 );

		require_once buddypress()->compatibility_dir . '/class-bb-seo-helpers.php';
		$helper = BB_SEO_Helpers::instance();

		// Stand in for Yoast's Indexable_Presentation: `model` is the indexable, not `indexable`.
		$presentation                     = new stdClass();
		$presentation->model              = new stdClass();
		$presentation->model->object_type = 'post';
		$presentation->model->object_id   = $post_id;
		$presentation->model->author_id   = $user_id;

		$name = $helper->redact_author_name( 'Alex Quillfeather', $presentation );

		$this->assertSame(
			'Alex',
			$name,
			'The author meta tag carried a surname hidden from an anonymous visitor.'
		);
	}

	/**
	 * The author meta tag must keep the full name for a viewer who is allowed to see it.
	 *
	 * @group bb_search_visibility_display_format
	 */
	public function test_seo_plugin_author_meta_tag_keeps_name_for_permitted_viewer() {
		$user_id = $this->create_member_with_hidden_surname( 'loggedin', 'Quillfeather', 'Alex' );
		$post_id = self::factory()->post->create( array( 'post_author' => $user_id ) );
		$viewer  = self::factory()->user->create();

		$this->set_current_user( $viewer );

		require_once buddypress()->compatibility_dir . '/class-bb-seo-helpers.php';
		$helper = BB_SEO_Helpers::instance();

		$presentation                     = new stdClass();
		$presentation->model              = new stdClass();
		$presentation->model->object_type = 'post';
		$presentation->model->object_id   = $post_id;
		$presentation->model->author_id   = $user_id;

		$this->assertSame(
			'Alex Quillfeather',
			$helper->redact_author_name( 'Alex Quillfeather', $presentation ),
			'A permitted viewer must still get the real name in the author meta tag.'
		);

		$this->set_current_user( 0 );
	}

	/**
	 * Each SEO plugin BuddyBoss ships support for, and the contract the redaction depends on.
	 *
	 * @return array
	 */
	public function seo_plugin_contract_provider() {
		return array(
			'Yoast schema graph' => array(
				'wordpress-seo',
				'Yoast SEO',
				'wpseo_schema_graph',
				'redact_schema_graph',
				array(
					'passes its schema graph through this filter with a second context argument' => '/apply_filters\(\s*\'wpseo_schema_graph\'\s*,\s*\$\w+\s*,\s*\$\w+/',
					'exposes the context `indexable` the member is resolved from' => '/public\s+\$indexable\s*;/',
					'stores `object_type` on the indexable' => '/@property\s+string\s+\$object_type/',
					'stores `object_id` on the indexable' => '/@property\s+int\s+\$object_id/',
					'stores `author_id` on the indexable' => '/@property\s+int\s+\$author_id/',
				),
			),
			'Yoast author name'  => array(
				'wordpress-seo',
				'Yoast SEO',
				'wpseo_meta_author',
				'redact_author_name',
				array(
					'passes the bare display_name string through this filter with a presentation' => '/apply_filters\(\s*\'wpseo_meta_author\'\s*,\s*\$\w+->display_name\s*,\s*\$[\w>$-]+/',
					'exposes the presentation `model` the member is resolved from' => '/public\s+\$model\s*;/',
				),
			),
			'All in One SEO'     => array(
				'all-in-one-seo-pack',
				'All in One SEO',
				'aioseo_schema_output',
				'redact_schema_graph',
				array(
					'passes its @graph through this filter' => '/apply_filters\(\s*\'aioseo_schema_output\'\s*,/',
				),
			),
			'Rank Math schema'   => array(
				'seo-by-rank-math',
				'Rank Math',
				'rank_math/json_ld',
				'redact_schema_graph',
				array(
					'dispatches its JSON-LD filter' => '/do_filter\(\s*\'json_ld\'/',
					'still prefixes its dispatched hook names with rank_math/' => '/\'rank_math\/\'\s*\.\s*\$args\[0\]/',
				),
			),
		);
	}

	/**
	 * The redaction must be registered on a hook the installed SEO plugin actually dispatches, and
	 * on the data shape it actually passes.
	 *
	 * The registration half is asserted against the singleton production already built, read
	 * through reflection. Asking BB_SEO_Helpers::instance() for it instead would construct the
	 * class - and the constructor is what registers the filters - so the assertion would be
	 * satisfied by the act of making it.
	 *
	 * The six simulations above each register their own closure standing in for the plugin. That is
	 * the right unit test for BuddyBoss's own ordering and for what the callbacks do with a value,
	 * but it proves nothing about the plugin: a simulation goes on passing after the real plugin
	 * renames the hook, changes its argument count, or stops exposing the property the member is
	 * resolved from - which is exactly when the leak this covers comes back, silently, on a plugin
	 * update nobody reviewed. The plugins cannot be booted inside this suite (their filters are
	 * applied while they render a head, not registered up front), so the contract is read from the
	 * installed plugin's own source, which is what the helper's docblocks assert by hand today.
	 *
	 * @dataProvider seo_plugin_contract_provider
	 * @group bb_search_visibility_display_format
	 *
	 * @param string $slug        Plugin directory name.
	 * @param string $plugin_name Human name, for the failure messages.
	 * @param string $hook        Hook BB_SEO_Helpers registers on.
	 * @param string $callback    BB_SEO_Helpers method registered on it.
	 * @param array  $contract    Map of `what the plugin must still do` => regex over its source.
	 */
	public function test_seo_plugin_contract_holds_against_the_installed_plugin( $slug, $plugin_name, $hook, $callback, $contract ) {
		$plugin_dir = $this->seo_plugin_dir( $slug );

		if ( '' === $plugin_dir ) {
			$this->markTestSkipped( sprintf( '%s is not installed in this configuration.', $plugin_name ) );
		}

		if ( ! class_exists( 'BB_SEO_Helpers', false ) ) {
			$this->markTestSkipped( 'The SEO compatibility layer is not available in this configuration.' );
		}

		// Read the instance production built, rather than asking for one. BB_SEO_Helpers registers
		// its filters in its own constructor, so `has_filter( $hook, array( BB_SEO_Helpers::instance(),
		// ... ) )` registers the very thing it then asserts is registered, and passes whether or not
		// any production code ever instantiates the class. Reflection observes the singleton without
		// creating it; the production wiring that fills it is proved separately, in
		// test_the_production_path_registers_the_seo_redaction().
		$helper = $this->existing_seo_helper_instance();

		$this->assertNotNull(
			$helper,
			'Nothing instantiated BB_SEO_Helpers, so none of its redaction filters are registered.'
		);

		// Priority 20 is above every registration these plugins make on their own graph, so the
		// redaction runs on the finished structure rather than on a half-built one.
		$this->assertSame(
			20,
			has_filter( $hook, array( $helper, $callback ) ),
			sprintf( 'The redaction is not registered on %s at the priority it needs.', $hook )
		);

		foreach ( $contract as $expectation => $pattern ) {
			$this->assertTrue(
				$this->seo_plugin_source_matches( $plugin_dir, $pattern ),
				sprintf( '%s no longer %s, so the redaction registered for it cannot work.', $plugin_name, $expectation )
			);
		}
	}

	/**
	 * Locate an installed SEO plugin.
	 *
	 * The suite runs against a WordPress checkout whose plugins directory holds nothing, while the
	 * install this plugin is checked out in is the one that has the SEO plugins - so both are
	 * searched, and on a normal site they are the same directory.
	 *
	 * @param string $slug Plugin directory name.
	 * @return string Absolute path, or '' when the plugin is not installed.
	 */
	protected function seo_plugin_dir( $slug ) {
		$roots = array( WP_PLUGIN_DIR, dirname( untrailingslashit( buddypress()->plugin_dir ), 2 ) );

		foreach ( array_unique( $roots ) as $root ) {
			if ( is_dir( $root . '/' . $slug ) ) {
				return $root . '/' . $slug;
			}
		}

		return '';
	}

	/**
	 * Whether any PHP file shipped by a plugin matches a pattern.
	 *
	 * The whole tree is searched rather than one named file so that a plugin moving its own code
	 * around does not read as a broken contract - only losing the thing entirely does.
	 *
	 * @param string $plugin_dir Absolute plugin path.
	 * @param string $pattern    Regular expression.
	 * @return bool
	 */
	protected function seo_plugin_source_matches( $plugin_dir, $pattern ) {
		$skip_dirs = array( 'vendor', 'vendor_prefixed', 'node_modules', 'languages', 'assets', 'images', 'css', 'js', 'dist', 'build' );

		$files = new RecursiveIteratorIterator(
			new RecursiveCallbackFilterIterator(
				new RecursiveDirectoryIterator( $plugin_dir, FilesystemIterator::SKIP_DOTS ),
				function ( $current ) use ( $skip_dirs ) {
					if ( $current->isDir() ) {
						return ! in_array( $current->getFilename(), $skip_dirs, true );
					}

					return 'php' === strtolower( $current->getExtension() );
				}
			)
		);

		foreach ( $files as $file ) {
			$contents = file_get_contents( $file->getPathname() );

			if ( false !== $contents && preg_match( $pattern, $contents ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * BuddyBoss must actually contribute its personal-data exporters and erasers.
	 *
	 * BP_Core_Gdpr is constructed on `bp_loaded` priority 0 and used to schedule its registration on
	 * `bp_loaded` priority 0 as well - the priority already executing. WordPress iterates one
	 * priority bucket with a foreach over a snapshot of that bucket, and WP_Hook::add_filter() can
	 * only resort the list of priorities, never rewind the inner loop, so the callback was never
	 * reached. The result: not one BuddyBoss exporter or eraser registered, and Tools > Export
	 * Personal Data produced a report containing the WordPress groups and none of the member's
	 * connections, group memberships, messages, activity, profile fields or forum content
	 *. Registering on a LATER priority of the same action is what makes it run - and
	 * it has to be later than priority 2 in any case, because every check in the callback is a
	 * bp_is_active() call and the components are not set up until then.
	 *
	 * @group bb_search_visibility_display_format
	 */
	public function test_buddyboss_registers_its_personal_data_exporters() {
		$exporters = apply_filters( 'wp_privacy_personal_data_exporters', array() );
		$erasers   = apply_filters( 'wp_privacy_personal_data_erasers', array() );

		foreach ( array( 'bp_xprofile', 'bp_activity', 'bp_notification', 'bp_message', 'bp_groups', 'bp_group_memberships', 'bp_friendship', 'bp_settings' ) as $key ) {
			$this->assertArrayHasKey(
				$key,
				$exporters,
				sprintf( 'The %s exporter did not register, so its data never reaches a member\'s personal-data export.', $key )
			);
			$this->assertArrayHasKey(
				$key,
				$erasers,
				sprintf( 'The %s eraser did not register, so its data is never removed on an erasure request.', $key )
			);
		}
	}

	/**
	 * Every changed exporter, with the fixture that makes it name another member.
	 *
	 * @return array
	 */
	public function export_name_scope_provider() {
		return array(
			'notification' => array( 'bp_notification', 'fixture_notification_naming_the_actor' ),
			'friendship'   => array( 'bp_friendship', 'fixture_friendship_naming_the_actor' ),
			'message'      => array( 'bp_message', 'fixture_message_naming_the_actor' ),
		);
	}

	/**
	 * Data exported for a member must not name someone they may not see.
	 *
	 * An export is built in the request of whoever runs it - an administrator, who may read every
	 * field - and then handed permanently to the member it belongs to. Each of these exporters
	 * resolves another member's name in its own right, so each has to resolve it for the data
	 * subject: the notification exporter through the component callback that writes the notification
	 * text (QA finding F-3, "Alex Quillfeather replied to your post" in a report produced for a
	 * member the surname is hidden from), the other two through their own direct
	 * bp_core_get_user_displayname() call. One predicate, several independent sites, and a leak here
	 * lands in a file the member keeps.
	 *
	 * @dataProvider export_name_scope_provider
	 * @group bb_search_visibility_display_format
	 *
	 * @param string $exporter_key Exporter key registered on wp_privacy_personal_data_exporters.
	 * @param string $fixture      Method that creates the data naming the actor.
	 */
	public function test_export_resolves_names_for_the_data_subject( $exporter_key, $fixture ) {
		$actor   = $this->create_member_with_hidden_surname( 'adminsonly', 'Quillfeather', 'Alex' );
		$subject = self::factory()->user->create( array( 'user_email' => 'subject-9896@example.com' ) );

		// The administrator is the one running the export, and may see the surname.
		$this->set_current_user( 1 );

		$exporters = apply_filters( 'wp_privacy_personal_data_exporters', array() );
		$this->assertArrayHasKey(
			$exporter_key,
			$exporters,
			sprintf( 'The %s exporter must register for this test to mean anything.', $exporter_key )
		);

		$this->{$fixture}( $actor, $subject );

		$export = call_user_func( $exporters[ $exporter_key ]['callback'], 'subject-9896@example.com', 1 );

		$values = array();
		foreach ( $export['data'] as $group ) {
			foreach ( $group['data'] as $row ) {
				$values[] = (string) $row['value'];
			}
		}

		$this->assertNotEmpty( $values, 'The export produced no rows.' );

		$report = implode( ' | ', $values );

		// Without this the test would pass on an export that never names the actor at all, which is
		// exactly the state a broken fixture leaves it in.
		$this->assertStringContainsString(
			'Alex',
			$report,
			'Fixture: the export does not name the other member, so it cannot show whether the name was scoped.'
		);
		$this->assertStringNotContainsString(
			'Quillfeather',
			$report,
			'A personal-data export carried a surname hidden from the member it was produced for.'
		);
	}

	/**
	 * A notification whose text names the actor.
	 *
	 * @param int $actor   Member whose name the export renders.
	 * @param int $subject Member the export belongs to.
	 */
	protected function fixture_notification_naming_the_actor( $actor, $subject ) {
		bp_notifications_add_notification(
			array(
				'user_id'           => $subject,
				'item_id'           => 1,
				'secondary_item_id' => $actor,
				'component_name'    => 'activity',
				'component_action'  => 'new_at_mention',
				'is_new'            => 1,
			)
		);
	}

	/**
	 * A confirmed connection between the two members.
	 *
	 * @param int $actor   Member whose name the export renders.
	 * @param int $subject Member the export belongs to.
	 */
	protected function fixture_friendship_naming_the_actor( $actor, $subject ) {
		friends_add_friend( $subject, $actor, true );
	}

	/**
	 * A message thread the subject sent to the actor.
	 *
	 * The exporter reports the messages the data subject SENT, and renders every participant of
	 * those threads by name - so the subject has to be the sender for the actor to appear.
	 *
	 * @param int $actor   Member whose name the export renders.
	 * @param int $subject Member the export belongs to.
	 */
	protected function fixture_message_naming_the_actor( $actor, $subject ) {
		messages_new_message(
			array(
				'sender_id'  => $subject,
				'recipients' => array( $actor ),
				'subject'    => 'Thursday',
				'content'    => 'The hall is open.',
			)
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
	 * searcher could confirm a restricted surname by searching it.
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
	 * producer did not.
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
	 * This covers the email/notification fan-out paths, which had no test.
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
	 * The group-message email fan-out must resolve {{sender.name}} for the recipient on BOTH of its
	 * branches.
	 *
	 * group_messages_notification_new_message() either queues the batch or sends it inline, and
	 * which one runs is decided by nothing but bb_is_email_queue() and the recipient count - so the
	 * two have to agree about the name they deliver. The inline branch resolves the name per
	 * recipient. The queued branch cannot: it builds its tokens once, in the sender's own request,
	 * where nobody is ever denied their own name, so what it stores is the unredacted name and the
	 * only thing keeping the withheld surname out of the inbox is
	 * bb_render_messages_recipients() overwriting the token for each recipient as it sends. On a
	 * populated community the queued branch is the one that runs, because the queue exists for
	 * exactly the recipient counts a real community produces.
	 *
	 * Equality across the two branches is necessary but not sufficient - two branches can agree on
	 * the unredacted name - so each branch is additionally pinned to the literal visible name and
	 * asserted not to carry the withheld surname, and only then compared to the other.
	 *
	 * @group bb_search_visibility_display_format
	 */
	public function test_group_message_sender_name_agrees_across_the_email_queue_branches() {
		global $wpdb, $bb_background_updater;

		$sender_id = $this->create_member_with_hidden_surname( 'adminsonly', 'Wrenfield', 'Marguerite' );

		// Both branches skip a recipient whose group-message email preference is off, so give the
		// recipients the preference a member who wants these emails has.
		$type_key = 'notification_group_messages_new_message';
		if ( ! bb_enabled_legacy_email_preference() ) {
			$type_key = bb_get_prefences_key( 'legacy', $type_key );
		}

		$recipients = array();
		foreach ( self::factory()->user->create_many( 3 ) as $recipient_id ) {
			bp_update_user_meta( $recipient_id, $type_key, 'yes' );

			$recipient            = new stdClass();
			$recipient->user_id   = (int) $recipient_id;
			$recipient->is_hidden = 0;
			$recipients[]         = $recipient;
		}

		bp_update_option( 'bp-display-name-format', 'first_last_name' );

		// bp_send_email() returns a WP_Error before it ever sets tokens when the email post for the
		// type is absent, and the harness has no bp-email posts at all.
		if ( is_wp_error( bp_get_email( 'group-message-email' ) ) ) {
			$email_post_id = wp_insert_post(
				array(
					'post_status'  => 'publish',
					'post_type'    => bp_get_email_post_type(),
					'post_title'   => '[{{{site.name}}}] New message from group: "{{{group.name}}}"',
					'post_content' => '{{{sender.name}}} sent you a message.',
					'post_excerpt' => '{{{sender.name}}} sent you a message.',
				)
			);
			wp_set_object_terms( $email_post_id, 'group-message-email', bp_get_email_tax_type() );
		}

		$args = array(
			'id'         => 0,
			'thread_id'  => 4242,
			'sender_id'  => $sender_id,
			'subject'    => 'Neighbourhood notice',
			'message'    => 'The hall is open on Thursday.',
			'recipients' => $recipients,
		);

		$captured = array();
		$capture  = function ( &$email, $email_type, $to, $send_args ) use ( &$captured ) {
			if ( isset( $send_args['tokens']['receiver-user.id'], $send_args['tokens']['sender.name'] ) ) {
				$captured[ (int) $send_args['tokens']['receiver-user.id'] ] = (string) $send_args['tokens']['sender.name'];
			}
		};
		// Stop the send once the tokens exist: the capture hook runs before validate().
		$halt = function () {
			return new WP_Error( 'bb_test_halt', 'halted' );
		};

		add_action( 'bp_send_email', $capture, 10, 4 );
		add_filter( 'bp_email_validate', $halt, 99 );

		// group_messages_notification_new_message() returns before either branch while "Delay Email
		// Notifications" is on, which is the default. Turn it off, as a site that mails its group
		// messages has to.
		add_filter( 'bb_delay_email_notifications_enabled', '__return_false' );

		// The actor is the sender - the context in which the surname is never withheld.
		$this->set_current_user( $sender_id );

		// Branch one: the queue is off, so the recipients are served inline.
		add_filter( 'bb_is_email_queue', '__return_false' );
		group_messages_notification_new_message( $args );
		remove_filter( 'bb_is_email_queue', '__return_false' );

		$unqueued = $captured;
		$captured = array();

		// Branch two: the queue is on and the recipient count clears the batch threshold, so the
		// tokens are built now and the send happens later, out of the sender's request.
		$table = BB_Background_Process::$table_name;
		$this->assertNotEmpty( $table, 'Fixture: the background queue table name is not resolved.' );
		$last_job_id = (int) $wpdb->get_var( "SELECT MAX(id) FROM {$table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		$one_per_batch = function () {
			return 1;
		};
		$block_dispatch = function () {
			return array(
				'headers'  => array(),
				'body'     => '',
				'response' => array(
					'code'    => 200,
					'message' => 'OK',
				),
			);
		};

		add_filter( 'bb_email_queue_min_count', $one_per_batch );
		add_filter( 'pre_http_request', $block_dispatch );
		group_messages_notification_new_message( $args );
		remove_filter( 'pre_http_request', $block_dispatch );
		remove_filter( 'bb_email_queue_min_count', $one_per_batch );

		$jobs = $wpdb->get_col( $wpdb->prepare( "SELECT data FROM {$table} WHERE id > %d ORDER BY id ASC", $last_job_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$this->assertNotEmpty( $jobs, 'Fixture: the queued branch stored no background job.' );

		// Run the queued jobs the way BB_Background_Updater::task() does, so the tokens under test
		// are the ones the sender's request actually stored.
		foreach ( $jobs as $job ) {
			$job = maybe_unserialize( $job );
			if ( empty( $job['callback'] ) || ! is_callable( $job['callback'] ) ) {
				continue;
			}
			call_user_func_array( $job['callback'], (array) $job['args'] );
		}

		$queued = $captured;

		remove_filter( 'bb_delay_email_notifications_enabled', '__return_false' );
		remove_action( 'bp_send_email', $capture, 10 );
		remove_filter( 'bp_email_validate', $halt, 99 );

		$this->assertNotEmpty( $unqueued, 'Fixture: the inline branch sent nothing to capture.' );
		$this->assertNotEmpty( $queued, 'Fixture: the queued branch sent nothing to capture.' );

		foreach ( $recipients as $recipient ) {
			// The literal, not bp_core_get_user_displayname() re-asked here: deriving the
			// expectation from the same resolver that produced the token reduces the assertion to
			// "the token equals whatever the resolver returns", which holds just as well when the
			// resolver hands back the full name on both branches.
			$this->assertSame(
				'Marguerite',
				$unqueued[ $recipient->user_id ],
				'The inline branch delivered a sender name that was not resolved for this recipient.'
			);
			$this->assertSame(
				'Marguerite',
				$queued[ $recipient->user_id ],
				'The queued branch delivered a sender name that was not resolved for this recipient.'
			);

			// Named separately from the equality above: the surname is what the adminsonly level
			// withholds, and a prefix-equal name ("Marguerite Wrenfield") would satisfy neither
			// assertion but only this one says why.
			$this->assertStringNotContainsStringIgnoringCase(
				'Wrenfield',
				$unqueued[ $recipient->user_id ],
				'The inline branch put the withheld surname in the recipient inbox.'
			);
			$this->assertStringNotContainsStringIgnoringCase(
				'Wrenfield',
				$queued[ $recipient->user_id ],
				'The queued branch put the withheld surname in the recipient inbox - permanently, since the token is stored.'
			);

			$this->assertSame(
				$unqueued[ $recipient->user_id ],
				$queued[ $recipient->user_id ],
				'The queued and inline branches disagree about the sender name delivered to the same recipient.'
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

	/**
	 * The BB_SEO_Helpers singleton production built, observed without building one.
	 *
	 * @return BB_SEO_Helpers|null
	 */
	protected function existing_seo_helper_instance() {
		if ( ! class_exists( 'BB_SEO_Helpers', false ) ) {
			return null;
		}

		$instance = new ReflectionProperty( 'BB_SEO_Helpers', 'instance' );
		$instance->setAccessible( true );

		return $instance->getValue();
	}

	/**
	 * The production wiring - not the test - is what instantiates the SEO redaction.
	 *
	 * BB_SEO_Helpers registers its filters from its own constructor, so any assertion phrased as
	 * `has_filter( $hook, array( BB_SEO_Helpers::instance(), ... ) )` creates the object it is about
	 * to look for and passes on an install where nothing in the plugin ever loads the class. The
	 * contract test above therefore reads the singleton through reflection, and this test proves the
	 * singleton is filled by production: the instance is dropped, every redaction filter it had
	 * registered is removed, and `bp_helper_plugins_loaded_callback()` - the `init` priority 0
	 * callback that requires the file and calls instance() - is run on its own. The assertion that
	 * the singleton is no longer null is made BEFORE anything asks for it, so it can only be
	 * satisfied by that callback.
	 *
	 * @group bb_search_visibility_display_format
	 */
	public function test_the_production_path_registers_the_seo_redaction() {
		if ( ! function_exists( 'bp_helper_plugins_loaded_callback' ) ) {
			$this->markTestSkipped( 'The compatibility loader is not available in this configuration.' );
		}

		if ( ! class_exists( 'BB_SEO_Helpers', false ) ) {
			$this->markTestSkipped( 'The SEO compatibility layer is not available in this configuration.' );
		}

		// The registration runs on `init` at priority 0, which is where the plugin puts it.
		$this->assertSame(
			0,
			has_action( 'init', 'bp_helper_plugins_loaded_callback' ),
			'The compatibility loader is no longer hooked, so nothing runs the SEO registration at all.'
		);

		$previous = $this->existing_seo_helper_instance();

		$this->assertNotNull(
			$previous,
			'BB_SEO_Helpers was never instantiated during boot, so its redaction filters are not registered on this request.'
		);

		$registered_hooks = array_merge(
			array_keys( (array) $previous->get_schema_graph_filters() ),
			array_keys( (array) $previous->get_author_name_filters() )
		);

		$this->assertNotEmpty( $registered_hooks, 'Fixture: the helper registers no hooks at all, so this proves nothing.' );

		// Tear the registration down completely, so what is asserted afterwards can only have been
		// put back by the production callback.
		foreach ( (array) $previous->get_schema_graph_filters() as $hook => $priority ) {
			remove_filter( $hook, array( $previous, 'redact_schema_graph' ), (int) $priority );
		}

		foreach ( (array) $previous->get_author_name_filters() as $hook => $priority ) {
			remove_filter( $hook, array( $previous, 'redact_author_name' ), (int) $priority );
		}

		$instance = new ReflectionProperty( 'BB_SEO_Helpers', 'instance' );
		$instance->setAccessible( true );
		$instance->setValue( null, null );

		$sample_hook = $registered_hooks[0];

		$this->assertFalse(
			has_filter( $sample_hook, array( $previous, 'redact_schema_graph' ) ),
			'Fixture: the old registration survived the teardown, so the re-registration below proves nothing.'
		);

		try {
			bp_helper_plugins_loaded_callback();

			$rebuilt = $instance->getValue();

			$this->assertNotNull(
				$rebuilt,
				'bp_helper_plugins_loaded_callback() did not instantiate BB_SEO_Helpers, so on a real request the member-name redaction is never registered with any SEO plugin.'
			);

			foreach ( (array) $rebuilt->get_schema_graph_filters() as $hook => $priority ) {
				$this->assertSame(
					(int) $priority,
					has_filter( $hook, array( $rebuilt, 'redact_schema_graph' ) ),
					sprintf( 'The production path left %s without its schema redaction.', $hook )
				);
			}

			foreach ( (array) $rebuilt->get_author_name_filters() as $hook => $priority ) {
				$this->assertSame(
					(int) $priority,
					has_filter( $hook, array( $rebuilt, 'redact_author_name' ), (int) $priority ),
					sprintf( 'The production path left %s without its author-name redaction.', $hook )
				);
			}
		} finally {
			// The harness restores $wp_filter to the snapshot it took at boot, and that snapshot
			// holds the ORIGINAL object's callbacks - so the singleton has to go back with it, or
			// every later test in this process reads an instance whose callbacks are registered
			// under a different object identity.
			$instance->setValue( null, $previous );
		}
	}

	/**
	 * Load the site-wide search engine into a harness that never boots the Search component.
	 *
	 * The class autoloader require()s any bp-* class file it is asked for whether the component was
	 * booted or not, so probing for Bp_Search_Members with autoloading on is a fatal: its base class
	 * is defined by an include the component never ran. Load the base class first, then the subclass.
	 *
	 * @return bool Whether the engine is usable in this configuration.
	 */
	protected function load_search_members_engine() {
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
			return false;
		}

		if ( ! class_exists( 'Bp_Search_Members', false ) && file_exists( $search_dir . 'classes/class-bp-search-members.php' ) ) {
			require_once $search_dir . 'classes/class-bp-search-members.php';
		}

		return class_exists( 'Bp_Search_Members', false )
			&& function_exists( 'bp_get_search_user_fields' )
			&& function_exists( 'bp_is_search_user_field_enable' );
	}

	/**
	 * Build the site-wide member search query with only the Display Name field switched on.
	 *
	 * Every other wp_users comparison - user_login, user_nicename, user_email, user meta - is a
	 * separate leg of the same OR and is off by default, so leaving them off is what makes the
	 * result attributable to the display_name leg this rule narrows.
	 *
	 * @param string $term       Search term.
	 * @param bool   $count_only Ask for the COUNT form.
	 * @return string Prepared SQL.
	 */
	protected function search_members_sql( $term, $count_only = false ) {
		$field_option = 'bp_search_user_field_display_name';
		$field_backup = get_option( $field_option );
		update_option( $field_option, 1 );

		try {
			return Bp_Search_Members::instance()->sql( $term, $count_only );
		} finally {
			if ( false === $field_backup ) {
				delete_option( $field_option );
			} else {
				update_option( $field_option, $field_backup );
			}
		}
	}

	/**
	 * The exclusions the search engine resolves for a term.
	 *
	 * Protected on the engine because nothing outside it may assemble the display_name leg, and
	 * memoized per term and viewer for the life of the request - so every test that reads it uses
	 * its own term.
	 *
	 * @param string $term Search term.
	 * @return array
	 */
	protected function display_name_visibility_exclusions( $term ) {
		$method = new ReflectionMethod( 'Bp_Search_Members', 'bb_get_display_name_visibility_exclusions' );
		$method->setAccessible( true );

		return $method->invoke( Bp_Search_Members::instance(), $term );
	}

	/**
	 * A member whose search match exists only in the stored display_name column, with last activity
	 * recorded so the site-wide engine's last_activity join keeps them.
	 *
	 * @param string $display_name Stored display name.
	 * @return int
	 */
	protected function create_searchable_member_with_display_name( $display_name ) {
		global $wpdb;

		$user_id = self::factory()->user->create();

		$wpdb->update( $wpdb->users, array( 'display_name' => $display_name ), array( 'ID' => $user_id ) );
		clean_user_cache( $user_id );
		bp_update_user_last_activity( $user_id, bp_core_current_time() );

		return $user_id;
	}

	/**
	 * There is no ceiling on the display_name leg any more, and no shape that could carry one.
	 *
	 * The engine used to bound its own work with Bp_Search_Members::bb_get_display_name_visibility_bounds(),
	 * which returned a `max_id` fence: matches above it were excluded outright. That bounded RESULTS
	 * where only WORK ever needed bounding, because it was computed from the MATCH SET rather than
	 * from the restricted population - a term matching 70,000 members admitted 500 and fenced off
	 * 69,500, while the set that actually needed hiding was empty.
	 *
	 * The replacement is bounded by the restricted population and cannot express a fence: it returns
	 * the members to exclude, a predicate, and whether the leg is withheld entirely. This pins the
	 * shape so a ceiling cannot be reintroduced quietly.
	 *
	 * @group bb_search_visibility_display_format
	 */
	public function test_the_display_name_visibility_ceiling_is_gone() {
		if ( ! $this->load_search_members_engine() ) {
			$this->markTestSkipped( 'The Search component is not available in this configuration.' );
		}

		$this->assertFalse(
			method_exists( 'Bp_Search_Members', 'bb_get_display_name_visibility_bounds' ),
			'The match-set ceiling is back on the search engine.'
		);

		$this->assertTrue(
			method_exists( 'Bp_Search_Members', 'bb_get_display_name_visibility_exclusions' ),
			'The engine no longer resolves display_name visibility exclusions at all.'
		);

		$this->set_current_user( 0 );

		$exclusions = $this->display_name_visibility_exclusions( 'Ceilinglessness' );

		$this->assertSame(
			array( 'suppress_leg', 'hidden_ids', 'visible_name_sql', 'values' ),
			array_keys( $exclusions ),
			'The exclusion contract changed shape - a caller assembling the display_name leg reads these four keys by name.'
		);

		$this->assertIsBool( $exclusions['suppress_leg'] );
		$this->assertIsArray( $exclusions['hidden_ids'] );
		$this->assertIsString( $exclusions['visible_name_sql'] );
		$this->assertIsArray( $exclusions['values'] );

		foreach ( array( 'max_id', 'limit', 'ceiling', 'bound' ) as $fence ) {
			$this->assertArrayNotHasKey(
				$fence,
				$exclusions,
				'The exclusions carry a fence again, which bounds results rather than work.'
			);
		}
	}

	/**
	 * An empty exclusion set means EXCLUDE NOBODY: 70,000 matches in, 70,000 matches out.
	 *
	 * This is the release blocker. Every mechanism the visibility work added is a narrowing, and on
	 * the overwhelmingly common configuration - a community where nobody has restricted a name field
	 * - all of them resolve to "nothing is hidden". The defect was that the work was bounded by the
	 * MATCH SET instead of by the RESTRICTED POPULATION, so a budget meant to cap a handful of
	 * visibility resolutions capped the results instead: 70,000 matched members, 500 served, a 99.3%
	 * cull of people nothing was hidden from.
	 *
	 * The budget is therefore forced far BELOW the match set here. Nobody in the fixture has
	 * restricted anything, so the candidate set is empty, the budget is never reached, and every
	 * match survives - through both xprofile producers and through the site-wide engine, whose
	 * count is asserted against the fixture size rather than against a shape. A fixture this size
	 * stands in for the 70,000: what matters is that it is many times the budget.
	 *
	 * @group bb_search_visibility_display_format
	 */
	public function test_an_empty_exclusion_set_leaves_the_whole_match_set_intact() {
		global $wpdb;

		bp_update_option( 'bp-display-name-format', 'first_last_name' );
		$this->set_current_user( 0 );

		$match_size = 40;
		$user_ids   = array();

		for ( $i = 0; $i < $match_size; $i++ ) {
			// The token lives only in the stored column, so the display_name leg is the only leg of
			// the site-wide query that can match it.
			$user_ids[] = $this->create_searchable_member_with_display_name( 'Member Zanthorpe' . $i );
		}

		$pattern  = '%Zanthorpe%';
		$expected = $user_ids;
		sort( $expected );

		$this->assertSame(
			array(),
			bb_xprofile_get_hidden_name_search_user_ids( array( $pattern ), 0 ),
			'Nobody restricted a name field, so the exclusion set must be an authoritative empty - never false, never a subset.'
		);

		// A budget forty times smaller than the match set. It bounds the restricted population, and
		// that population is empty, so it must never be reached.
		$cap = function () {
			return 1;
		};
		add_filter( 'bb_xprofile_user_search_visibility_candidate_limit', $cap );

		try {
			list( $kept, $resolutions ) = $this->count_name_resolutions(
				function () use ( $user_ids, $pattern ) {
					return bb_xprofile_filter_user_search_matches( $user_ids, array( $pattern ), 0 );
				}
			);

			$rows = array();
			foreach ( $user_ids as $user_id ) {
				$rows[] = $this->field_match_row( $user_id, bp_xprofile_lastname_field_id() );
			}

			$field_kept = bb_xprofile_filter_field_search_matches( $user_ids, $rows, 0 );

			sort( $kept );
			sort( $field_kept );

			$this->assertSame(
				$expected,
				$kept,
				sprintf( 'The display_name producer returned %d of %d matches with nothing hidden from anybody.', count( $kept ), $match_size )
			);
			$this->assertSame( 0, $resolutions, 'Names were resolved for a match set in which nobody restricted anything.' );
			$this->assertSame(
				$expected,
				$field_kept,
				sprintf( 'The profile-field producer returned %d of %d matches with nothing hidden from anybody.', count( $field_kept ), $match_size )
			);

			if ( ! $this->load_search_members_engine() ) {
				$this->markTestSkipped( 'The Search component is not available in this configuration.' );
			}

			$exclusions = $this->display_name_visibility_exclusions( 'Zanthorpe' );

			$this->assertFalse( $exclusions['suppress_leg'], 'The engine withheld its display_name leg with nothing to withhold.' );
			$this->assertSame( array(), $exclusions['hidden_ids'], 'The engine excluded members nothing is hidden about.' );
			$this->assertSame( '', $exclusions['visible_name_sql'], 'The format hides nothing under this format, so no predicate belongs on the leg.' );

			$sql = $this->search_members_sql( 'Zanthorpe', true );

			$this->assertStringNotContainsString( '1 = 0', $sql, 'The engine withheld the display_name comparison entirely.' );
			$this->assertStringNotContainsString( 'ID NOT IN', $sql, 'The engine fenced members off a leg nothing is hidden on.' );

			$this->assertSame(
				$match_size,
				// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- the engine returns its own prepared statement, and the test exists to run it.
				(int) $wpdb->get_var( $sql ),
				'The site-wide engine culled matches that no privacy rule applies to - matches in, matches out is the whole contract here.'
			);
		} finally {
			remove_filter( 'bb_xprofile_user_search_visibility_candidate_limit', $cap );
		}
	}

	/**
	 * A read that fails withholds the whole leg, on every producer - and never a slice of it.
	 *
	 * The visibility answer cannot be resolved, so serving the matches would publish exactly what
	 * the rule exists to withhold and serving part of them would decide the results by position in
	 * an unordered set. Both producers return an empty array; the site-wide engine replaces its
	 * display_name comparison with a false predicate. user_login, user_nicename, user_email, user
	 * meta and the profile fields are separate legs of the same OR, so a search still answers.
	 *
	 * Paired with the missing-table direction in
	 * BP_Tests_XProfile_NameVisibilityRegressions::test_a_missing_visibility_table_filters_normally():
	 * a failed read is an error, an absent table is the pre-migration shape, and treating the second
	 * like the first would withhold member search on every install that has not migrated yet.
	 *
	 * @group bb_search_visibility_display_format
	 */
	public function test_a_failed_visibility_read_withholds_the_leg_on_every_producer() {
		global $wpdb;

		bp_update_option( 'bp-display-name-format', 'first_last_name' );
		$this->set_current_user( 0 );

		$user_ids = array();
		for ( $i = 0; $i < 3; $i++ ) {
			$user_ids[] = $this->create_member_with_hidden_surname( 'public', 'Marrowgate' . $i, 'Perpetua' );
		}

		$pattern = '%Marrowgate%';
		$rows    = array();
		foreach ( $user_ids as $user_id ) {
			$rows[] = $this->field_match_row( $user_id, bp_xprofile_lastname_field_id() );
		}

		$expected = $user_ids;
		sort( $expected );

		// Control: with the read healthy every one of these is kept, so the empty answers below are
		// attributable to the failure and not to the fixture.
		$healthy_user  = bb_xprofile_filter_user_search_matches( $user_ids, array( $pattern ), 0 );
		$healthy_field = bb_xprofile_filter_field_search_matches( $user_ids, $rows, 0 );
		sort( $healthy_user );
		sort( $healthy_field );

		$this->assertSame( $expected, $healthy_user, 'Fixture: nothing is hidden here, so the healthy answer must keep everybody.' );
		$this->assertSame( $expected, $healthy_field, 'Fixture: nothing is hidden here, so the healthy answer must keep everybody.' );

		// Matched on the table being READ, not on a particular SELECT list. Keyed to the SQL text
		// ('DISTINCT user_id') this stopped breaking the profile-field producer the moment its two
		// reads were merged into one aggregate - the query still read the same table, the fixture
		// just no longer recognised it, and the test went green against a leg that was never
		// broken. `FROM` keeps BB_XProfile_Visibility::visibility_table_exists() out of scope: it
		// probes with `SHOW TABLES LIKE`, and breaking that would withhold the leg through the
		// missing-table path instead of the failed-read path this test is about.
		$visibility_table = BB_XProfile_Visibility::get_visibility_table_name();

		$break_query = function ( $query ) use ( $visibility_table ) {
			if ( false !== strpos( $query, 'FROM ' . $visibility_table ) ) {
				return 'SELECT user_id FROM __bb_no_such_table__ WHERE 1=1';
			}

			return $query;
		};

		$suppress = $wpdb->suppress_errors( true );
		add_filter( 'query', $break_query );

		try {
			$broken_user  = bb_xprofile_filter_user_search_matches( $user_ids, array( $pattern ), 0 );
			$broken_field = bb_xprofile_filter_field_search_matches( $user_ids, $rows, 0 );

			$engine_sql = null;
			if ( $this->load_search_members_engine() ) {
				// Its own term: the engine memoizes the resolved exclusions per term and viewer for
				// the life of the request, so a term used while the read was healthy would be
				// answered from that memo.
				$engine_sql = $this->search_members_sql( 'Marrowgate', true );
			}
		} finally {
			remove_filter( 'query', $break_query );
			$wpdb->suppress_errors( $suppress );
		}

		$this->assertSame(
			array(),
			$broken_user,
			'A failed visibility read served the display_name matches it could not verify.'
		);
		$this->assertSame(
			array(),
			$broken_field,
			'A failed visibility read served the profile-field matches it could not verify.'
		);

		if ( ! is_null( $engine_sql ) ) {
			$this->assertStringContainsString(
				'1 = 0',
				$engine_sql,
				'The site-wide engine served an unverified display_name comparison after a failed visibility read.'
			);
			$this->assertStringNotContainsString(
				'display_name LIKE',
				$engine_sql,
				'The site-wide engine kept the display_name comparison it cannot verify.'
			);
		}
	}

	/**
	 * A moderator is exempt from BOTH halves of the rule, in the display_name producer.
	 *
	 * The rule has two independent halves - the per-member restriction and the site-wide Display
	 * Name Format hide - and each is answered in a different place, so each needs its own bypass.
	 * The per-member half answers it inside the resolver; the format half is decided in the
	 * producer, and a revision that left the bypass off the producer dropped a moderator's match
	 * under a format hide even though they are shown every name part.
	 *
	 * The fixture puts both halves in play at once: an admins-only surname, under a format that
	 * hides the surname from everybody.
	 *
	 * @group bb_search_visibility_display_format
	 */
	public function test_moderator_is_exempt_from_both_halves_of_the_display_name_producer() {
		$user_id = $this->create_member_with_hidden_surname( 'adminsonly', 'Grimthwaite', 'Peregrine' );
		$admin   = self::factory()->user->create( array( 'role' => 'administrator' ) );

		bp_update_option( 'bp-display-name-format', 'first_name' );

		// Fixture: both halves really would drop this match for anybody else.
		$this->assertSame(
			array(),
			bb_xprofile_filter_user_search_matches( array( $user_id ), array( '%Grimthwaite%' ), 0 ),
			'Fixture: a guest must be denied this match, or the moderator assertion proves nothing.'
		);

		$this->assertSame(
			array( $user_id ),
			bb_xprofile_filter_user_search_matches( array( $user_id ), array( '%Grimthwaite%' ), $admin ),
			'A moderator lost a surname match they are allowed to read - one of the two halves is not checking the bypass.'
		);
	}

	/**
	 * The same exemption, in the profile-field producer.
	 *
	 * This producer answers the format half through bp_core_hide_display_name_field() and the
	 * per-member half through bp_xprofile_get_hidden_fields_for_user(), and the moderator bypass
	 * has to stand in front of both - it is one rule and two filters, and they must not disagree
	 * about who a moderator is.
	 *
	 * @group bb_search_visibility_display_format
	 */
	public function test_moderator_is_exempt_from_both_halves_of_the_field_producer() {
		$user_id = $this->create_member_with_hidden_surname( 'adminsonly', 'Thornbury', 'Peregrine' );
		$admin   = self::factory()->user->create( array( 'role' => 'administrator' ) );
		$rows    = array( $this->field_match_row( $user_id, bp_xprofile_lastname_field_id() ) );

		bp_update_option( 'bp-display-name-format', 'first_name' );
		bp_update_option( 'bp-hide-last-name', 0 );

		// Fixture: both halves really would drop this match for anybody else.
		$this->assertSame(
			array(),
			bb_xprofile_filter_field_search_matches( array( $user_id ), $rows, 0 ),
			'Fixture: a guest must be denied this match, or the moderator assertion proves nothing.'
		);

		$this->assertSame(
			array( $user_id ),
			bb_xprofile_filter_field_search_matches( array( $user_id ), $rows, $admin ),
			'A moderator lost a profile-field match they are allowed to read.'
		);
	}

	/**
	 * The same exemption, in the site-wide search engine.
	 *
	 * The engine excludes the per-member half with a `NOT IN` list and the format half with a SQL
	 * predicate. Neither applies to a moderator, and the predicate is the half that has no bypass
	 * of its own to fall back on - it is built from the term, not from the viewer.
	 *
	 * @group bb_search_visibility_display_format
	 */
	public function test_moderator_is_exempt_from_both_halves_of_the_site_search_engine() {
		if ( ! $this->load_search_members_engine() ) {
			$this->markTestSkipped( 'The Search component is not available in this configuration.' );
		}

		$user_id = $this->create_member_with_hidden_surname( 'adminsonly', 'Wrackmoor', 'Peregrine' );
		$admin   = self::factory()->user->create( array( 'role' => 'administrator' ) );
		bp_update_user_last_activity( $user_id, bp_core_current_time() );

		bp_update_option( 'bp-display-name-format', 'first_name' );

		// Fixture: for a guest both halves are in play - the member is excluded by id AND the
		// format predicate is added to the leg.
		$this->set_current_user( 0 );
		$guest_exclusions = $this->display_name_visibility_exclusions( 'Wrackmoorguest' );
		$guest_hidden     = $this->display_name_visibility_exclusions( 'Wrackmoor' );

		$this->assertSame(
			array( $user_id ),
			$guest_hidden['hidden_ids'],
			'Fixture: a guest must be excluded from this surname match, or the moderator assertion proves nothing.'
		);
		$this->assertNotSame(
			'',
			$guest_exclusions['visible_name_sql'],
			'Fixture: the format predicate must apply to a guest, or the second half is not exercised.'
		);

		$this->set_current_user( $admin );
		$moderator_exclusions = $this->display_name_visibility_exclusions( 'Wrackmoor' );

		$this->assertFalse( $moderator_exclusions['suppress_leg'], 'A moderator had the display_name leg withheld.' );
		$this->assertSame(
			array(),
			$moderator_exclusions['hidden_ids'],
			'A moderator was fenced away from a surname match they are allowed to read.'
		);
		$this->assertSame(
			'',
			$moderator_exclusions['visible_name_sql'],
			'The format predicate was applied to a moderator, who is shown every name part.'
		);

		$sql = $this->search_members_sql( 'Wrackmoor' );

		$this->assertContains(
			(int) $user_id,
			array_map( 'intval', (array) $GLOBALS['wpdb']->get_col( $sql ) ),
			'A moderator searching the site-wide engine lost a member they are allowed to find.'
		);

		$this->set_current_user( 0 );
	}

	/**
	 * Both answers to "which matches does the format still show" have to be the same answer.
	 *
	 * The ID-returning resolver and the subquery one - bb_xprofile_get_format_visible_name_matches()
	 * and bb_xprofile_get_format_visible_name_sql() - exist as two shapes because their callers
	 * differ: the member directory can afford an ID list, the site-wide engine cannot materialise
	 * one. The RULE must not differ, or the member directory and the site-wide search answer the
	 * same question differently, which is the defect this whole area exists to close.
	 *
	 * Three readings are compared on every term, and they must be one answer:
	 *
	 * - the ID list;
	 * - the subquery applied through an ALIASED outer column, `u.ID IN ( ... )`;
	 * - the subquery applied through the BARE `ID IN ( ... )` of a plain `SELECT ID FROM wp_users`,
	 *   which is exactly the call site the site-wide engine uses.
	 *
	 * The bare form is the one that matters. An earlier revision took an outer id column and
	 * spliced it into `EXISTS ( SELECT 1 FROM wp_bp_xprofile_data d WHERE d.user_id = <column> )`;
	 * that table has an `id` column of its own, so a bare `ID` bound to `d.id` instead of to the
	 * outer row, the subquery stopped being correlated, and it evaluated once and applied its
	 * answer to every member - silently, in whichever direction the data happened to fall. The
	 * subquery is uncorrelated now and references nothing of the enclosing query, so there is no
	 * column left to mis-bind. Asserting the bare reading is what turns that from "the test routes
	 * around the ambiguity" into "the ambiguity cannot exist".
	 *
	 * Every source either side compares is exercised, each by exactly one member: the profile field
	 * the format shows, the user meta that field falls back to, the Nickname field, the `nickname`
	 * meta, user_nicename, and a term that lives only in a name part the format hides. Each member
	 * is stripped to a single source first, deliberately - on a natural fixture WordPress mirrors
	 * the profile first name into user meta, so the meta arm of the union answers for the field arm
	 * and a broken arm is masked by the OR. The expected member is asserted per term for the same
	 * reason: two shapes that agree on nothing agree, and prove nothing.
	 *
	 * @dataProvider format_visible_name_format_provider
	 * @group bb_search_visibility_display_format
	 *
	 * @param string $format          Display Name Format.
	 * @param array  $expected_by_key Expected matching fixture key per term key, '' for nobody.
	 */
	public function test_format_visible_name_sql_and_matches_give_the_same_answer( $format, $expected_by_key ) {
		global $wpdb;

		$members = array(
			// First Name profile field only.
			'first_field' => self::factory()->user->create(),
			// First name only in WordPress user meta - the imported, unhealed member.
			'first_meta'  => self::factory()->user->create(),
			// Nickname profile field only.
			'nick_field'  => self::factory()->user->create(),
			// `nickname` user meta only.
			'nick_meta'   => self::factory()->user->create(),
			// user_nicename only - the last resort the resolver falls back to.
			'nicename'    => self::factory()->user->create(),
			// Last Name only, which neither format shows.
			'last_field'  => self::factory()->user->create(),
		);

		foreach ( $members as $member_id ) {
			foreach ( array( bp_xprofile_firstname_field_id(), bp_xprofile_lastname_field_id(), bp_xprofile_nickname_field_id() ) as $field_id ) {
				xprofile_set_field_data( $field_id, $member_id, '' );
			}
			delete_user_meta( $member_id, 'first_name' );
			delete_user_meta( $member_id, 'nickname' );
		}

		xprofile_set_field_data( bp_xprofile_firstname_field_id(), $members['first_field'], 'Alberic' );
		update_user_meta( $members['first_meta'], 'first_name', 'Bramwell' );
		xprofile_set_field_data( bp_xprofile_nickname_field_id(), $members['nick_field'], 'Cindervale' );
		update_user_meta( $members['nick_meta'], 'nickname', 'Dellowbrook' );
		$wpdb->update( $wpdb->users, array( 'user_nicename' => 'everwick' ), array( 'ID' => $members['nicename'] ) );
		clean_user_cache( $members['nicename'] );
		xprofile_set_field_data( bp_xprofile_lastname_field_id(), $members['last_field'], 'Fennimore' );

		$terms = array(
			'first_field' => 'Alberic',
			'first_meta'  => 'Bramwell',
			'nick_field'  => 'Cindervale',
			'nick_meta'   => 'Dellowbrook',
			'nicename'    => 'everwick',
			'last_field'  => 'Fennimore',
		);

		// No outer id column, by design: a parameter the caller could get wrong is what made the
		// subquery bind into itself. Pinned here because removing the argument is the fix, and a
		// reintroduced one would make the mis-binding possible again without changing any answer
		// this test compares.
		$signature = new ReflectionFunction( 'bb_xprofile_get_format_visible_name_sql' );

		$this->assertSame(
			2,
			$signature->getNumberOfParameters(),
			'bb_xprofile_get_format_visible_name_sql() takes an outer column again, which is the argument that used to bind inside the subquery instead of to the enclosing row.'
		);

		foreach ( $terms as $term_key => $term ) {
			$patterns = array( '%' . $term . '%' );

			$by_ids = bb_xprofile_get_format_visible_name_matches( $format, $patterns );
			sort( $by_ids );

			$built = bb_xprofile_get_format_visible_name_sql( $format, $patterns );

			$this->assertNotSame( '', $built['sql'], 'Fixture: the format must build a subquery, or the comparison is vacuous.' );

			// Applied through an aliased outer column...
			$by_sql_aliased = array_map(
				'intval',
				// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- the subquery is built by the function under test and is prepared here.
				(array) $wpdb->get_col( $wpdb->prepare( "SELECT u.ID FROM {$wpdb->users} u WHERE u.ID IN ( " . $built['sql'] . ' )', $built['values'] ) )
			);
			sort( $by_sql_aliased );

			// ...and through the bare `ID` of a plain SELECT, which is the site-wide engine's own
			// call site and the one the old correlated shape answered wrongly.
			$by_sql_bare = array_map(
				'intval',
				// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- the subquery is built by the function under test and is prepared here.
				(array) $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM {$wpdb->users} WHERE ID IN ( " . $built['sql'] . ' )', $built['values'] ) )
			);
			sort( $by_sql_bare );

			$expected = '' === $expected_by_key[ $term_key ] ? array() : array( (int) $members[ $expected_by_key[ $term_key ] ] );
			sort( $expected );

			// One assertion over all three readings: a wrong subquery moves the two SQL readings
			// together, and the diff names which reading drifted and from what.
			$this->assertSame(
				array(
					'id list'                   => $expected,
					'subquery via aliased u.ID' => $expected,
					'subquery via bare ID'      => $expected,
				),
				array(
					'id list'                   => $by_ids,
					'subquery via aliased u.ID' => $by_sql_aliased,
					'subquery via bare ID'      => $by_sql_bare,
				),
				sprintf(
					'The two shapes disagree, or agree on the wrong members, about the term "%s" under the %s format.',
					$term,
					$format
				)
			);
		}
	}

	/**
	 * Formats that hide a name part site-wide, and who each fixture term should resolve to.
	 *
	 * @return array
	 */
	public function format_visible_name_format_provider() {
		return array(
			'First Name format' => array(
				'first_name',
				array(
					'first_field' => 'first_field',
					'first_meta'  => 'first_meta',
					'nick_field'  => 'nick_field',
					'nick_meta'   => 'nick_meta',
					'nicename'    => 'nicename',
					// The surname is not part of the visible name under this format.
					'last_field'  => '',
				),
			),
			'Nickname format'   => array(
				'nickname',
				array(
					// Neither name field is part of the visible name under this format.
					'first_field' => '',
					'first_meta'  => '',
					'nick_field'  => 'nick_field',
					'nick_meta'   => 'nick_meta',
					'nicename'    => 'nicename',
					'last_field'  => '',
				),
			),
		);
	}

	/**
	 * The candidate set is "restricted AND matched", and never includes the viewer.
	 *
	 * Two properties of the same set, and each one is load-bearing on its own:
	 *
	 * - A member is never hidden from themselves, so the viewer can never be a drop candidate. That
	 *   is applied before the extension filter runs, so a site adding candidates back is not
	 *   narrowed away - and re-applied after it, so a listener cannot make a member invisible to
	 *   themselves.
	 * - A member whose display_name does not match the term is in nobody's match set and can never
	 *   be dropped, so re-testing them is pure cost. Excluding them in the correlated subquery is
	 *   what keeps the candidate set to the restricted population intersected with the term, and it
	 *   is also why a visibility row left behind by a deleted member can never surface: the row has
	 *   no wp_users row to correlate to.
	 *
	 * @group bb_search_visibility_display_format
	 */
	public function test_hidden_name_candidates_exclude_the_viewer_and_anyone_the_term_missed() {
		global $wpdb;

		bp_update_option( 'bp-display-name-format', 'first_last_name' );

		$viewer = $this->create_member_with_hidden_surname( 'adminsonly', 'Sablewick', 'Ottoline' );
		// Restricted in exactly the same way, but their display_name does not contain the term.
		$unmatched = $this->create_member_with_hidden_surname( 'adminsonly', 'Pellingsworth', 'Ottoline' );
		// The orphan: a visibility row whose member no longer exists.
		$orphan_id = 99000000;

		$orphan_row        = new BB_XProfile_Visibility( bp_xprofile_lastname_field_id(), $orphan_id );
		$orphan_row->value = 'adminsonly';
		$orphan_row->save();

		// Fixture: the unmatched member carries the same restriction as the viewer, so the term is
		// the only thing that can keep them out of the candidate set.
		$this->assertSame(
			'adminsonly',
			xprofile_get_field_visibility_level( bp_xprofile_lastname_field_id(), $unmatched ),
			'Fixture: the unmatched member must be restricted in exactly the same way.'
		);

		$seen_candidates = null;
		$observer        = function ( $candidate_ids ) use ( &$seen_candidates ) {
			$seen_candidates = array_map( 'intval', (array) $candidate_ids );

			return $candidate_ids;
		};

		add_filter( 'bb_xprofile_user_search_visibility_candidates', $observer );

		try {
			$hidden = bb_xprofile_get_hidden_name_search_user_ids( array( '%Sablewick%' ), $viewer );
		} finally {
			remove_filter( 'bb_xprofile_user_search_visibility_candidates', $observer );
		}

		$this->assertIsArray( $hidden );
		$this->assertIsArray( $seen_candidates, 'Fixture: the candidate filter never ran, so nothing below is observed.' );

		$this->assertNotContains(
			(int) $viewer,
			$seen_candidates,
			'The viewer reached the candidate set, so a site adding candidates back could hide a member from themselves.'
		);
		$this->assertNotContains(
			(int) $unmatched,
			$seen_candidates,
			'A restricted member whose display_name does not match the term was re-tested - the candidate set is bounded by the restriction alone again.'
		);
		$this->assertNotContains(
			$orphan_id,
			$seen_candidates,
			'A visibility row belonging to no member surfaced as a candidate.'
		);

		$this->assertNotContains( (int) $viewer, $hidden, 'A member was hidden from themselves.' );
		$this->assertNotContains( (int) $unmatched, $hidden );
		$this->assertNotContains( $orphan_id, $hidden );

		// A listener that adds the viewer back must not be able to hide them from themselves either.
		$re_add = function ( $candidate_ids ) use ( $viewer ) {
			$candidate_ids[] = $viewer;

			return $candidate_ids;
		};

		add_filter( 'bb_xprofile_user_search_visibility_candidates', $re_add );

		try {
			$hidden_after_filter = bb_xprofile_get_hidden_name_search_user_ids( array( '%Sablewickagain%' ), $viewer );
		} finally {
			remove_filter( 'bb_xprofile_user_search_visibility_candidates', $re_add );
		}

		$this->assertNotContains(
			(int) $viewer,
			(array) $hidden_after_filter,
			'A listener put the viewer back into the candidate set and the viewer was hidden from their own search.'
		);
	}

	/**
	 * The name leak stays closed on both producers, under every Display Name Format.
	 *
	 * The hidden surname must answer nothing on either producer, and the name part the viewer IS
	 * shown must keep the member findable on both - a filter that closed the leak by dropping the
	 * member from every search would pass the first assertion alone and silently break the member
	 * directory. The formats are covered separately because each resolves the visible name from a
	 * different set of sources, and only the last of them ("First Name & Last Name") leaves the
	 * rule to the per-member visibility level on its own.
	 *
	 * Each case carries its own terms: the site-wide engine memoizes the exclusions it resolves per
	 * term and viewer for the life of the PHP process, so a term reused under a second format would
	 * be answered from the first format's memo.
	 *
	 * @dataProvider name_leak_format_provider
	 * @group bb_search_visibility_display_format
	 *
	 * @param string $format      Display Name Format.
	 * @param string $first_name  First name, which every format resolves to a visible name part.
	 * @param string $surname     Restricted surname, which no format may answer for.
	 * @param string $nickname    Nickname, deliberately containing the first-name token so the
	 *                            member stays findable under the Nickname format too.
	 */
	public function test_the_name_leak_stays_closed_on_both_producers( $format, $first_name, $surname, $nickname ) {
		global $wpdb;

		bp_update_option( 'bp-display-name-format', $format );
		$this->set_current_user( 0 );

		$user_id = self::factory()->user->create( array( 'nickname' => $nickname ) );

		wp_update_user(
			array(
				'ID'           => $user_id,
				'first_name'   => $first_name,
				'last_name'    => $surname,
				'display_name' => $first_name . ' ' . $surname,
			)
		);

		// wp_update_user() resets the `nickname` user meta to the user_login whenever the update
		// does not carry one - and that meta, not the Nickname profile field, is what
		// bb_core_build_visible_display_name() resolves the visible name from under the Nickname
		// format. Written back afterwards so the member's visible name is the nickname the fixture
		// intends rather than their login.
		update_user_meta( $user_id, 'nickname', $nickname );

		xprofile_set_field_data( bp_xprofile_firstname_field_id(), $user_id, $first_name );
		xprofile_set_field_data( bp_xprofile_lastname_field_id(), $user_id, $surname );
		xprofile_set_field_data( bp_xprofile_nickname_field_id(), $user_id, $nickname );
		xprofile_set_field_visibility_level( bp_xprofile_lastname_field_id(), $user_id, 'adminsonly' );
		bp_update_user_last_activity( $user_id, bp_core_current_time() );

		$this->assertStringNotContainsString(
			$surname,
			(string) bp_core_get_user_displayname( $user_id, 0 ),
			'Fixture: the surname must be redacted from the rendered name, or there is nothing to leak.'
		);

		// The display_name producer.
		$this->assertSame(
			array(),
			bb_xprofile_filter_user_search_matches( array( $user_id ), array( '%' . $surname . '%' ), 0 ),
			sprintf( 'A guest confirmed a restricted surname under the %s format.', $format )
		);
		$this->assertSame(
			array( $user_id ),
			bb_xprofile_filter_user_search_matches( array( $user_id ), array( '%' . $first_name . '%' ), 0 ),
			sprintf( 'A member became unfindable by a name part the %s format shows.', $format )
		);

		// The profile-field producer, on the same two terms.
		$surname_rows = array( $this->field_match_row( $user_id, bp_xprofile_lastname_field_id() ) );
		$visible_rows = array( $this->field_match_row( $user_id, bp_xprofile_nickname_field_id() ) );

		$this->assertSame(
			array(),
			bb_xprofile_filter_field_search_matches( array( $user_id ), $surname_rows, 0 ),
			sprintf( 'A guest confirmed a restricted surname through the profile-field producer under the %s format.', $format )
		);
		$this->assertSame(
			array( $user_id ),
			bb_xprofile_filter_field_search_matches( array( $user_id ), $visible_rows, 0 ),
			sprintf( 'The profile-field producer dropped a match on a field the %s format shows.', $format )
		);

		if ( ! $this->load_search_members_engine() ) {
			return;
		}

		$hidden_sql  = $this->search_members_sql( $surname );
		$visible_sql = $this->search_members_sql( $first_name );

		$this->assertNotContains(
			(int) $user_id,
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- the engine returns its own prepared statement, and the test exists to run it.
			array_map( 'intval', (array) $wpdb->get_col( $hidden_sql ) ),
			sprintf( 'The site-wide engine confirmed a restricted surname under the %s format.', $format )
		);
		$this->assertContains(
			(int) $user_id,
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- the engine returns its own prepared statement, and the test exists to run it.
			array_map( 'intval', (array) $wpdb->get_col( $visible_sql ) ),
			sprintf( 'The site-wide engine lost a member findable by a name part the %s format shows.', $format )
		);
	}

	/**
	 * One case per Display Name Format, each with its own terms.
	 *
	 * @return array
	 */
	public function name_leak_format_provider() {
		return array(
			'First Name & Last Name format' => array( 'first_last_name', 'Alexfl', 'Quillfeatherfl', 'alexflnick' ),
			'First Name format'             => array( 'first_name', 'Alexfn', 'Quillfeatherfn', 'alexfnnick' ),
			'Nickname format'               => array( 'nickname', 'Alexnk', 'Quillfeathernk', 'alexnknick' ),
		);
	}
	/**
	 * The visibility-table probe has THREE answers, and the two empty ones are opposites.
	 *
	 * A MISSING table is the pre-migration shape of an install: nobody has a row, visibility
	 * resolves from the `bp_xprofile_visibility_levels` user meta, and every caller carries on
	 * filtering normally. A FAILED probe is not an answer at all - the table may be there and full
	 * of restricting rows - so the callers that cannot resolve their candidate set without it have
	 * to withhold their leg instead. Collapsing the two, which is what a plain bool does, turns the
	 * per-member half of the name-search protection off for the whole request on the one
	 * configuration every real community is in.
	 *
	 * The failure is also deliberately NOT memoised, so the next call re-probes rather than serving
	 * a transient error for the rest of the request.
	 *
	 * @group bb_search_visibility
	 */
	public function test_the_visibility_table_probe_is_tri_state_and_a_failed_probe_is_not_memoised() {
		global $wpdb;

		$memo = new ReflectionProperty( 'BB_XProfile_Visibility', 'table_exists_cache' );
		$memo->setAccessible( true );
		$memo_backup = $memo->getValue();

		$bp             = buddypress();
		$table_backup   = isset( $bp->profile->table_name_visibility ) ? $bp->profile->table_name_visibility : null;
		$user_id        = $this->create_member_with_hidden_surname( 'adminsonly', 'Quillfeather' );
		$like_patterns  = array( '%Quillfeather%' );

		$this->set_current_user( 0 );

		$break_probe = function ( $query ) {
			if ( 0 === stripos( ltrim( (string) $query ), 'SHOW TABLES LIKE' ) ) {
				return 'SHOW TABLES LIKE FROM __bb_not_sql__';
			}

			return $query;
		};

		try {
			// (a) Healthy install: the table is there.
			$memo->setValue( null, null );
			$this->assertTrue(
				BB_XProfile_Visibility::visibility_table_exists(),
				'Fixture: the visibility table is missing, so nothing below is what it claims to be.'
			);

			// (b) A probe that could not answer reports null - never false - and leaves the memo
			// empty so the next call asks again.
			$memo->setValue( null, null );
			$suppress = $wpdb->suppress_errors( true );
			add_filter( 'query', $break_probe );

			try {
				$failed = BB_XProfile_Visibility::visibility_table_exists();

				$this->assertNull( $failed, 'A failed probe was reported as an answer.' );
				$this->assertNull( $memo->getValue(), 'A failed probe was memoised, so the rest of the request cannot recover from it.' );

				// While the question is unanswered, the producers that need the candidate set fail
				// closed rather than serving matches they could not verify.
				$this->assertFalse(
					bb_xprofile_get_hidden_name_search_user_ids( $like_patterns, 0 ),
					'An unanswered probe was treated as "no restricting rows" and the display_name leg was served.'
				);
				$this->assertFalse(
					bb_xprofile_filter_possible_hidden_users( array( $user_id ) ),
					'An unanswered probe was treated as "no restricting rows" and the narrowing answered anyway.'
				);
			} finally {
				remove_filter( 'query', $break_probe );
				$wpdb->suppress_errors( $suppress );
			}

			// The failure was not memoised, so a healthy call straight afterwards resolves again.
			$this->assertTrue(
				BB_XProfile_Visibility::visibility_table_exists(),
				'The probe did not retry after a failure, so one transient error stands for the whole request.'
			);

			// (c) A genuinely ABSENT table is the opposite case: it is an answer, it IS memoised,
			// and the callers carry on filtering from the user meta instead of withholding.
			$memo->setValue( null, null );
			$bp->profile->table_name_visibility = $wpdb->prefix . 'bb_no_such_visibility_table';

			$this->assertFalse(
				BB_XProfile_Visibility::visibility_table_exists(),
				'An absent table must be reported as an answer, not as an unresolved probe.'
			);
			$this->assertFalse( $memo->getValue(), 'An absent table is a stable answer and must be memoised.' );

			$this->assertIsArray(
				bb_xprofile_get_hidden_name_search_user_ids( $like_patterns, 0 ),
				'A pre-migration install was treated as a read failure and lost its whole display_name leg.'
			);
			$this->assertNotFalse(
				bb_xprofile_filter_possible_hidden_users( array( $user_id ) ),
				'A pre-migration install was treated as a read failure by the narrowing.'
			);
		} finally {
			if ( null === $table_backup ) {
				unset( $bp->profile->table_name_visibility );
			} else {
				$bp->profile->table_name_visibility = $table_backup;
			}

			$memo->setValue( null, $memo_backup );
		}
	}

	/**
	 * Both memos behind the core-author redaction are keyed by the Display Name Format.
	 *
	 * The format decides on its own whether the surname is part of the visible name at all, and one
	 * request can see more than one of them: `bp_core_display_name_format` and
	 * `pre_option_bp-display-name-format` are both filterable. A memo keyed by member and viewer
	 * alone therefore serves the first format's answer for the rest of the request - on the author
	 * archive, its feed, `the_author` in a loop and the SEO name map alike.
	 *
	 * @group bb_search_visibility
	 * @group bb_core_author_surfaces
	 */
	public function test_the_core_author_name_memos_are_keyed_by_the_display_name_format() {
		$author = $this->create_member_with_hidden_surname( 'adminsonly', 'Quillfeather', 'Alex' );

		// Under the Nickname format the visible name is the xprofile Nickname FIELD, so store one.
		xprofile_set_field_data( bp_xprofile_nickname_field_id(), $author, 'quillnick' );

		$this->set_current_user( 0 );

		bp_update_option( 'bp-display-name-format', 'first_last_name' );
		$GLOBALS['bb_default_display_avatar'] = true;
		$first = bb_core_get_redacted_core_author_name( $author );

		$this->assertSame( 'Alex', $first, 'Fixture: the surname is not being withheld under the first format.' );

		bp_update_option( 'bp-display-name-format', 'nickname' );
		$GLOBALS['bb_default_display_avatar'] = true;
		$second = bb_core_get_redacted_core_author_name( $author );

		$this->assertSame(
			'quillnick',
			$second,
			'The per-request memo served the first format\'s answer after the format changed.'
		);

		// The SEO name map keeps a memo of its own, on the singleton, and has to be keyed the same
		// way - it is the one that runs once per author for a whole archive page.
		$helper = $this->existing_seo_helper_instance();

		if ( null === $helper ) {
			$this->markTestIncomplete( 'Nothing in this process instantiated BB_SEO_Helpers, so its memo cannot be observed here.' );
		}

		$name_map = new ReflectionMethod( 'BB_SEO_Helpers', 'get_name_map' );
		$name_map->setAccessible( true );

		bp_update_option( 'bp-display-name-format', 'first_last_name' );
		$GLOBALS['bb_default_display_avatar'] = true;
		$map_first = $name_map->invoke( $helper, array( $author ) );

		$this->assertSame(
			array( 'Alex Quillfeather' => 'Alex' ),
			$map_first,
			'Fixture: the SEO name map is not redacting under the first format.'
		);

		bp_update_option( 'bp-display-name-format', 'nickname' );
		$GLOBALS['bb_default_display_avatar'] = true;
		$map_second = $name_map->invoke( $helper, array( $author ) );

		$this->assertSame(
			array( 'Alex Quillfeather' => 'quillnick' ),
			$map_second,
			'The SEO name map served the first format\'s answer after the format changed.'
		);
	}

	/**
	 * The matcher must replace a name in a script written without word separators.
	 *
	 * Platform owns bb_core_replace_names() and had no test for it. The first version of the
	 * whole-word boundary asserted `(?![\p{L}\p{N}_])` for every script; in Japanese, Chinese,
	 * Korean and Thai the next character is always a letter, so the assertion never held, the
	 * replacement never fired, and the surname was published in full. Compared against strtr(),
	 * the call the boundary replaced, because that is the behaviour that must not be lost.
	 *
	 * @dataProvider continuous_script_name_provider
	 * @group bb_core_replace_names
	 *
	 * @param string $text   Text containing the stored name.
	 * @param string $stored Stored name.
	 * @param string $safe   Name this viewer may see.
	 */
	public function test_replace_names_redacts_a_name_in_a_separatorless_script( $text, $stored, $safe ) {
		$map = array( $stored => $safe );

		$this->assertSame(
			strtr( $text, $map ),
			bb_core_replace_names( $text, $map ),
			'The name was left standing where strtr() would have replaced it.'
		);
	}

	/**
	 * Names in scripts written without word separators, plus the mixed-script shape.
	 *
	 * @return array
	 */
	public function continuous_script_name_provider() {
		return array(
			'japanese'      => array( "\u{5C71}\u{7530}\u{592A}\u{90CE}\u{3055}\u{3093}\u{304C}\u{6295}\u{7A3F}", "\u{5C71}\u{7530}\u{592A}\u{90CE}", "\u{5C71}\u{7530}" ),
			'korean'        => array( "\u{AE40}\u{CCA0}\u{C218}\u{B2D8}\u{C774}", "\u{AE40}\u{CCA0}\u{C218}", "\u{AE40}" ),
			'chinese'       => array( "\u{674E}\u{660E}\u{53D1}\u{5E03}\u{4E86}", "\u{674E}\u{660E}", "\u{674E}" ),
			'thai'          => array( "\u{0E2A}\u{0E21}\u{0E0A}\u{0E32}\u{0E22}\u{0E43}\u{0E08}\u{0E14}\u{0E35}\u{0E42}\u{0E1E}\u{0E2A}\u{0E15}", "\u{0E2A}\u{0E21}\u{0E0A}\u{0E32}\u{0E22}\u{0E43}\u{0E08}\u{0E14}\u{0E35}", "\u{0E2A}\u{0E21}\u{0E0A}\u{0E32}\u{0E22}" ),
			'latin-in-kana' => array( "Alex Quillfeather\u{3055}\u{3093}\u{306E}\u{8A18}\u{4E8B}", 'Alex Quillfeather', 'Alex' ),
		);
	}

	/**
	 * The boundary must still protect an ordinary word that merely starts with a member's name.
	 *
	 * The other half of the same rule: this is the defect the boundary was added for, and a fix for
	 * the separator-less scripts must not undo it.
	 *
	 * @group bb_core_replace_names
	 */
	public function test_replace_names_does_not_rewrite_the_middle_of_a_longer_word() {
		$this->assertSame(
			'A. joined the Annapolis Anniversary group',
			bb_core_replace_names( 'Ann joined the Annapolis Anniversary group', array( 'Ann' => 'A.' ) ),
			'A longer word starting with the name was rewritten.'
		);

		$this->assertSame(
			'Zoey and Z. are friends',
			bb_core_replace_names( 'Zoey and Zoe are friends', array( 'Zoe' => 'Z.' ) ),
			'The standalone name was missed, or the longer word was corrupted.'
		);
	}

	/**
	 * A filter may only ever ADD scripts to the continuous class.
	 *
	 * The filter's own docblock promises a listener can make the redaction more aggressive and
	 * never less. A returned value that is narrower, empty, or not a valid class body would
	 * otherwise restore a boundary that cannot hold and leave the name standing - the filter would
	 * then be a way to switch the redaction off.
	 *
	 * @dataProvider hostile_script_class_provider
	 * @group bb_core_replace_names
	 *
	 * @param mixed $returned What the listener returns.
	 * @param bool  $append   Optional. Append $returned to the default class instead of replacing
	 *                        it, which is what a well-meaning listener extending the class does -
	 *                        and the shape an escape-aware check has to get right. Default false.
	 */
	public function test_a_hostile_script_class_filter_cannot_disable_the_redaction( $returned, $append = false ) {
		$callback = function ( $class_body ) use ( $returned, $append ) {
			return $append ? $class_body . $returned : $returned;
		};

		add_filter( 'bb_core_continuous_script_class', $callback, 99 );

		$text = "\u{5C71}\u{7530}\u{592A}\u{90CE}\u{3055}\u{3093}\u{304C}\u{6295}\u{7A3F}";
		$map  = array( "\u{5C71}\u{7530}\u{592A}\u{90CE}" => "\u{5C71}\u{7530}" );
		$out  = bb_core_replace_names( $text, $map );

		remove_filter( 'bb_core_continuous_script_class', $callback, 99 );

		$this->assertSame( strtr( $text, $map ), $out, 'A listener was able to switch the redaction off.' );
	}

	/**
	 * Return values a listener must not be able to weaken the redaction with.
	 *
	 * @return array
	 */
	public function hostile_script_class_provider() {
		return array(
			'empty'                  => array( '' ),
			'unbalanced'             => array( '\p{Han}]' ),
			'not a class'            => array( 'NOT_A_CLASS' ),
			'unknown script'         => array( '\p{Nosuchscript}' ),
			'narrowed'               => array( '\p{Thai}' ),

			// Appended to the default, which is how a listener that means to EXTEND the class
			// writes it. A one-character lookbehind cannot tell an escaped `]` from a `]` that
			// follows an escaped backslash, so `\\]` closed the class early and every name
			// against a separator-less script was left standing while Latin text still redacted.
			'escaped backslash then bracket' => array( '\\\\]', true ),
			'two escaped backslashes'        => array( '\\\\\\\\]', true ),
			'bracket appended'               => array( ']', true ),

		);
	}

	/**
	 * A listener that does not return a string is refused outright.
	 *
	 * Asserted on the resolved class rather than on the redaction, because the redaction still
	 * works for these shapes whether or not the guard exists - `(string) array()` yields "Array",
	 * which is a perfectly valid class body, so the hostile-return test above passes for them
	 * either way and proves nothing. What the guard actually prevents is a PHP warning raised on a
	 * public filter and a junk script spliced into the class.
	 *
	 * @dataProvider non_string_script_class_provider
	 * @group bb_core_replace_names
	 *
	 * @param mixed $returned What the listener returns.
	 */
	public function test_a_non_string_script_class_return_is_refused( $returned ) {
		$default = bb_core_get_continuous_script_class();

		$callback = function () use ( $returned ) {
			return $returned;
		};

		add_filter( 'bb_core_continuous_script_class', $callback, 99 );
		$resolved = bb_core_get_continuous_script_class();
		remove_filter( 'bb_core_continuous_script_class', $callback, 99 );

		$this->assertSame( $default, $resolved, 'A non-string listener return reached the class body.' );
		$this->assertStringNotContainsString( 'Array', $resolved, 'An array return was cast into the class.' );
	}

	/**
	 * Listener returns that are not strings at all.
	 *
	 * @return array
	 */
	public function non_string_script_class_provider() {
		return array(
			'array'   => array( array( 'x' ) ),
			'null'    => array( null ),
			'integer' => array( 5 ),
			'object'  => array( new stdClass() ),
		);
	}

	/**
	 * A combining mark is word-forming on BOTH edges of a name.
	 *
	 * The leading edge omitted `\p{M}` on the argument that a mark belongs to whatever precedes it.
	 * That is exactly why a name must not start matching straight after one: in decomposed (NFD)
	 * text "Ann" matched inside "Jose<combining acute>Ann" and rewrote the middle of a word nobody
	 * was redacting. The trailing edge already had a case; this is the mirror of it, and without it
	 * reverting the fix leaves every suite green.
	 *
	 * @group bb_core_replace_names
	 */
	public function test_a_combining_mark_is_word_forming_on_the_leading_edge() {
		$subject = "Jose\xCC\x81Ann posted";

		$this->assertSame(
			$subject,
			bb_core_replace_names( $subject, array( 'Ann' => 'A.' ) ),
			'A name matched straight after a combining mark and corrupted a decomposed word.'
		);

		// The mirror direction, which already passed, kept here so the pair cannot drift apart.
		$this->assertSame(
			"Z. and Zoe\xCC\x88 Muller",
			bb_core_replace_names( "Zoe and Zoe\xCC\x88 Muller", array( 'Zoe' => 'Z.' ) ),
			'A decomposed word was split on the trailing edge.'
		);

		// ...and an ordinary standalone name still matches, so the assertion above cannot pass by
		// the matcher simply having stopped working.
		$this->assertSame(
			'A. posted',
			bb_core_replace_names( 'Ann posted', array( 'Ann' => 'A.' ) ),
			'The standalone name stopped matching.'
		);
	}

	/**
	 * The entity-encoded spelling is found from a RAW-only map.
	 *
	 * Callers hand over the raw `wp_users.display_name` column, but the text being searched is not
	 * always raw: Yoast escapes the document title before passing it on, so the later filters see
	 * `O&#039;Brien` where the column holds `O'Brien`. Matching only the column found nothing there
	 * and the redaction failed OPEN and silently - the mechanism behind this ticket's original
	 * report. The map here carries ONLY the raw spelling, so the expansion has to be the matcher's
	 * own work; a test that pre-expands the map passes whether or not the code does anything.
	 *
	 * @dataProvider entity_spelling_provider
	 * @group bb_core_replace_names
	 *
	 * @param string $subject Text as a filter further down the chain sees it.
	 * @param string $stored  Raw stored name.
	 * @param string $safe    Name this viewer may see.
	 * @param string $leak    Fragment that must not survive.
	 */
	public function test_the_entity_spelling_is_expanded_from_a_raw_map( $subject, $stored, $safe, $leak ) {
		$out = bb_core_replace_names( $subject, array( $stored => $safe ) );

		$this->assertStringNotContainsString( $leak, $out, 'The entity-encoded spelling was not matched.' );
		$this->assertStringContainsString( $safe, $out, 'The permitted name is missing from the result.' );
	}

	/**
	 * Names whose escaped spelling differs from the column.
	 *
	 * @return array
	 */
	public function entity_spelling_provider() {
		return array(
			'apostrophe' => array( 'posted by Fiona O&#039;Brien today', "Fiona O'Brien", 'Fiona', 'Brien' ),
			'quotes'     => array( 'posted by Jo &quot;Q&quot; Smith today', 'Jo "Q" Smith', 'Jo', 'Smith' ),
			'ampersand'  => array( 'posted by Ben &amp; Co today', 'Ben & Co', 'Ben', 'Co' ),
		);
	}

	/**
	 * The continuous-script class is actually dispatched through its public filter.
	 *
	 * Every other assertion here observes what a listener could not DO. If the dispatch were
	 * removed the class would simply be the hard-coded default, every hostile-return case above
	 * would pass for the wrong reason, and the documented extension point would silently not
	 * exist.
	 *
	 * @group bb_core_replace_names
	 */
	public function test_the_continuous_script_class_is_dispatched_through_its_filter() {
		$seen     = 0;
		$callback = function ( $class_body ) use ( &$seen ) {
			++$seen;

			return $class_body;
		};

		add_filter( 'bb_core_continuous_script_class', $callback, 99 );
		bb_core_replace_names( 'Ann posted', array( 'Ann' => 'A.' ) );
		remove_filter( 'bb_core_continuous_script_class', $callback, 99 );

		$this->assertGreaterThan( 0, $seen, 'bb_core_continuous_script_class was never dispatched.' );
	}

	/**
	 * A listener that legitimately extends the class is still honoured.
	 *
	 * The guard above refuses bodies that would close the class early. It must not refuse the
	 * ordinary case as well, or the extension point is documented and inert.
	 *
	 * @group bb_core_replace_names
	 */
	public function test_a_listener_may_add_a_script_to_the_continuous_class() {
		$callback = function ( $class_body ) {
			return $class_body . '\p{Cyrillic}';
		};

		add_filter( 'bb_core_continuous_script_class', $callback, 99 );
		$class = bb_core_get_continuous_script_class();
		remove_filter( 'bb_core_continuous_script_class', $callback, 99 );

		$this->assertStringContainsString( '\p{Cyrillic}', $class, 'A valid addition was discarded.' );
		$this->assertStringContainsString( '\p{Han}', $class, 'The defaults were not preserved.' );
	}

	/**
	 * The `wp/v2/users` name exemption must not be reachable by an ordinary member.
	 *
	 * buddyboss-app grants `list_users` to EVERY caller for the duration of
	 * WP_REST_Users_Controller::get_item(), and wp_get_referer() reads the caller-supplied
	 * `_wp_http_referer` parameter, so both halves of the previous gate were forgeable and a
	 * subscriber could read a withheld surname from `?_fields=name`. The exemption now turns on
	 * `edit_users`, which that grant does not cover.
	 *
	 * @group bb_rest_name_exemption
	 */
	public function test_rest_users_name_exemption_is_not_reachable_by_a_subscriber() {
		$member = $this->create_member_with_hidden_surname( 'adminsonly', 'Quillfeather', 'Alex' );
		$viewer = self::factory()->user->create( array( 'role' => 'subscriber' ) );

		$this->set_current_user( $viewer );

		// Forge both halves of the old gate.
		$grant = function ( $allcaps ) {
			$allcaps['list_users'] = true;

			return $allcaps;
		};
		add_filter( 'user_has_cap', $grant, 10, 1 );
		$_REQUEST['_wp_http_referer'] = '/wp-admin/post-new.php';
		$_GET['_wp_http_referer']     = '/wp-admin/post-new.php';

		$request = new WP_REST_Request( 'GET', '/wp/v2/users/' . $member );
		$request->set_param( '_fields', 'name' );
		$data = rest_do_request( $request )->get_data();

		remove_filter( 'user_has_cap', $grant, 10 );
		unset( $_REQUEST['_wp_http_referer'], $_GET['_wp_http_referer'] );

		$this->assertIsArray( $data );
		$this->assertArrayHasKey( 'name', $data );

		// Both halves. Asserting only the absence of the surname passes on an empty string, so a
		// gate that redacted EVERYTHING would look correct here.
		$this->assertStringNotContainsString(
			'Quillfeather',
			(string) $data['name'],
			'A subscriber read the withheld surname by forging list_users and the referer.'
		);
		$this->assertStringContainsString(
			'Alex',
			(string) $data['name'],
			'The permitted name part was withheld too - the gate over-redacts.'
		);
	}

	/**
	 * A caller who genuinely holds `edit_users` must still receive the canonical column.
	 *
	 * The exemption exists so wp-admin user management can tell two members with the same first
	 * name apart. Without this the suite stays green when the gate is replaced by `if ( false )`.
	 *
	 * @group bb_rest_name_exemption
	 */
	public function test_rest_users_name_exemption_still_applies_to_a_user_editor() {
		$member = $this->create_member_with_hidden_surname( 'adminsonly', 'Quillfeather', 'Alex' );
		$editor = self::factory()->user->create( array( 'role' => 'administrator' ) );

		$this->set_current_user( $editor );

		$this->assertTrue( current_user_can( 'edit_users' ), 'Fixture: the viewer must hold edit_users.' );

		$request = new WP_REST_Request( 'GET', '/wp/v2/users/' . $member );
		$request->set_param( '_fields', 'name' );
		$data = rest_do_request( $request )->get_data();

		$this->assertStringContainsString(
			'Quillfeather',
			(string) $data['name'],
			'A user editor lost the canonical name the exemption exists to preserve.'
		);
	}

	/**
	 * `wp/v2/users?search=` must not confirm a name part the viewer may not read.
	 *
	 * Redacting `name` is not enough on a search route: the query matches the raw columns, so the
	 * presence or absence of a result answers "does this member's hidden surname contain X?" - an
	 * oracle an anonymous caller can walk a character at a time.
	 *
	 * @group bb_rest_name_exemption
	 */
	public function test_rest_users_search_does_not_confirm_a_withheld_name_part() {
		$member = $this->create_member_with_hidden_surname( 'adminsonly', 'Quillfeather', 'Alex' );

		// WordPress only lists a user to an anonymous caller once they have a published post.
		// Without this the member is absent from every response and the "surname is not confirmed"
		// assertion below would pass because nothing was listed at all, not because the fix works.
		self::factory()->post->create(
			array(
				'post_author' => $member,
				'post_status' => 'publish',
			)
		);

		$this->set_current_user( 0 );

		$search = function ( $term ) {
			$request = new WP_REST_Request( 'GET', '/wp/v2/users' );
			$request->set_param( 'search', $term );
			$request->set_param( '_fields', 'id' );
			$data = rest_do_request( $request )->get_data();

			return is_array( $data ) ? wp_list_pluck( $data, 'id' ) : array();
		};

		$this->assertNotContains(
			$member,
			$search( 'Quillfeather' ),
			'An anonymous search for the withheld surname returned the member - the oracle is open.'
		);

		// The other half: a search on the part the viewer MAY read must still find them, or the
		// fix has replaced a disclosure with a broken search.
		$this->assertContains(
			$member,
			$search( 'Alex' ),
			'The member is no longer findable by the name part this viewer is shown.'
		);
	}

}
