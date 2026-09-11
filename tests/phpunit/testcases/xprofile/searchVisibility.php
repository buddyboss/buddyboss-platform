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
}
