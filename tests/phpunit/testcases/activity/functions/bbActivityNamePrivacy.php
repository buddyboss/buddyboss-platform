<?php

/**
 * Last-name visibility must hold on every activity output path, including the
 * per-activity comment tree cache that is shared across viewers.
 *
 * @group activity
 * @group bb_activity_get_item_user_displayname
 * @group bp_activity_comments_cache
 */
class BP_Tests_Activity_Functions_BbActivityNamePrivacy extends BP_UnitTestCase {

	protected $xprofile_was_active;
	protected $format_backup;

	public function set_up() {
		parent::set_up();

		$this->xprofile_was_active = bp_is_active( 'xprofile' );
		buddypress()->active_components['xprofile'] = '1';

		$this->format_backup = bp_get_option( 'bp-display-name-format' );
		bp_update_option( 'bp-display-name-format', 'first_last_name' );
	}

	public function tear_down() {
		$GLOBALS['bb_default_display_avatar'] = false;
		bp_update_option( 'bp-display-name-format', $this->format_backup );

		if ( ! $this->xprofile_was_active ) {
			unset( buddypress()->active_components['xprofile'] );
		}

		parent::tear_down();
	}

	/**
	 * Create a member whose Last Name is visible to logged-in members only.
	 *
	 * The stored WP display_name legitimately holds the full name (the sync writes it with
	 * viewer = self); only the read side is viewer-dependent.
	 *
	 * @return int User ID.
	 */
	protected function create_member_with_hidden_last_name() {
		$u = self::factory()->user->create();

		// `profile_update` syncs first/last name into the xprofile fields.
		wp_update_user(
			array(
				'ID'           => $u,
				'first_name'   => 'Alex',
				'last_name'    => 'Quillfeather',
				'display_name' => 'Alex Quillfeather',
			)
		);

		// Set the visibility last: the sync above re-saves the default levels.
		xprofile_set_field_visibility_level( bp_xprofile_lastname_field_id(), $u, 'loggedin' );

		// The format layer memoises the member's name per request; the first resolution
		// happened during user creation, before the names existed. Refresh it once.
		$GLOBALS['bb_default_display_avatar'] = true;
		bp_core_get_user_displayname( $u, $u );

		return $u;
	}

	/**
	 * @group bb_activity_get_item_user_displayname
	 */
	public function test_item_displayname_hides_last_name_from_guest_and_shows_it_to_members() {
		$u      = $this->create_member_with_hidden_last_name();
		$member = self::factory()->user->create();

		$item               = new stdClass();
		$item->user_id      = $u;
		$item->display_name = 'Alex Quillfeather'; // Raw WP column, as joined by the activity query.

		$this->set_current_user( 0 );
		$this->assertSame( 'Alex', bb_activity_get_item_user_displayname( $item ) );

		$this->set_current_user( $member );
		$this->assertSame( 'Alex Quillfeather', bb_activity_get_item_user_displayname( $item ) );
	}

	/**
	 * @group bb_activity_get_item_user_displayname
	 */
	public function test_item_displayname_prefers_resolved_user_fullname() {
		$item                = new stdClass();
		$item->user_id       = 0;
		$item->display_name  = 'Raw Column';
		$item->user_fullname = 'Already Resolved';

		$this->assertSame( 'Already Resolved', bb_activity_get_item_user_displayname( $item ) );
		$this->assertSame( '', bb_activity_get_item_user_displayname( null ) );
	}

	/**
	 * bp_core_get_user_displayname() must strip a hidden last name wherever it sits in the
	 * stored display_name - a bare "Last", a "Last First" order, or any value not written by
	 * BuddyBoss's "First Last" sync (the wp-admin "Display name publicly as" dropdown,
	 * importers, other plugins). A plain str_replace of ' ' . $last_name only matched a
	 * space-prefixed trailing token and leaked the name for every other shape.
	 *
	 * @group bb_activity_get_item_user_displayname
	 */
	public function test_get_user_displayname_strips_hidden_last_name_in_any_order() {
		$member = self::factory()->user->create();

		// Bare last name (display_name is only the hidden last name): a guest must not see it;
		// it falls back to the public first name.
		$u1 = $this->create_member_with_hidden_last_name();
		wp_update_user( array( 'ID' => $u1, 'display_name' => 'Quillfeather' ) );
		$GLOBALS['bb_default_display_avatar'] = true;
		$this->set_current_user( 0 );
		$this->assertStringNotContainsString( 'Quillfeather', bp_core_get_user_displayname( $u1, 0 ) );
		$GLOBALS['bb_default_display_avatar'] = true;
		$this->assertSame( 'Alex', bp_core_get_user_displayname( $u1, 0 ) );

		// Last-first order.
		$u2 = $this->create_member_with_hidden_last_name();
		wp_update_user( array( 'ID' => $u2, 'display_name' => 'Quillfeather Alex' ) );
		$GLOBALS['bb_default_display_avatar'] = true;
		$this->set_current_user( 0 );
		$this->assertSame( 'Alex', bp_core_get_user_displayname( $u2, 0 ) );

		// Control: normal "First Last" still redacts to the first name for a guest and stays
		// full for a logged-in member.
		$u3 = $this->create_member_with_hidden_last_name();
		$GLOBALS['bb_default_display_avatar'] = true;
		$this->set_current_user( 0 );
		$this->assertSame( 'Alex', bp_core_get_user_displayname( $u3, 0 ) );
		$GLOBALS['bb_default_display_avatar'] = true;
		$this->set_current_user( $member );
		$this->assertSame( 'Alex Quillfeather', bp_core_get_user_displayname( $u3, $member ) );
	}

	/**
	 * The stored display_name casing can drift from the profile field value - imports, the
	 * wp-admin "Display name publicly as" dropdown and third-party writes are not bound to the
	 * field's casing. The strip matches case-insensitively so a differently-cased last name is
	 * still redacted, while a display_name whose casing already agrees behaves exactly as before
	 * (the token-bounded match cannot truncate a longer word).
	 *
	 * @group bb_activity_get_item_user_displayname
	 */
	public function test_get_user_displayname_strips_hidden_last_name_case_insensitively() {
		// Casing drift: field is "Quillfeather", stored display_name upper-cased.
		$u1 = $this->create_member_with_hidden_last_name();
		wp_update_user( array( 'ID' => $u1, 'display_name' => 'ALEX QUILLFEATHER' ) );
		$GLOBALS['bb_default_display_avatar'] = true;
		$this->set_current_user( 0 );
		$this->assertStringNotContainsStringIgnoringCase( 'quillfeather', bp_core_get_user_displayname( $u1, 0 ) );

		// Control: casing already agrees - unchanged behaviour (guest first-name-only, member full).
		$member = self::factory()->user->create();
		$u2     = $this->create_member_with_hidden_last_name();
		$GLOBALS['bb_default_display_avatar'] = true;
		$this->set_current_user( 0 );
		$this->assertSame( 'Alex', bp_core_get_user_displayname( $u2, 0 ) );
		$GLOBALS['bb_default_display_avatar'] = true;
		$this->set_current_user( $member );
		$this->assertSame( 'Alex Quillfeather', bp_core_get_user_displayname( $u2, $member ) );
	}

	/**
	 * A longer word that merely BEGINS with the hidden last name must never be truncated to a
	 * fragment. The token-bounded strip removes only the standalone `SMITH` token, leaving the
	 * longer `SMITHERS` intact (display casing preserved); the word-boundary fail-safe does not
	 * fire because the surviving `SMITH` inside `SMITHERS` is not a whole token, so it is not
	 * over-redacted either.
	 *
	 * @group bb_activity_get_item_user_displayname
	 */
	public function test_get_user_displayname_case_insensitive_does_not_truncate_longer_word() {
		$u = self::factory()->user->create();
		wp_update_user(
			array(
				'ID'           => $u,
				'first_name'   => 'Smithers',
				'last_name'    => 'Smith',
				'display_name' => 'Smithers Smith',
			)
		);
		xprofile_set_field_visibility_level( bp_xprofile_lastname_field_id(), $u, 'loggedin' );
		$GLOBALS['bb_default_display_avatar'] = true;
		bp_core_get_user_displayname( $u, $u );

		wp_update_user( array( 'ID' => $u, 'display_name' => 'SMITHERS SMITH' ) );
		$GLOBALS['bb_default_display_avatar'] = true;
		$this->set_current_user( 0 );
		$guest = bp_core_get_user_displayname( $u, 0 );
		$this->assertSame( 'SMITHERS', $guest );
		// Never a truncated fragment, and never the hidden standalone surname.
		$this->assertStringStartsWith( 'SMITHERS', $guest );
	}

	/**
	 * The hidden last name must not leak when the stored display_name has drifted so the surname
	 * sits against punctuation ("Anna Smith-Jones", "O.Smith") or is glued directly to the first
	 * name with no separator ("AnnaSmith", "SmithAnna") - the bypasses the whitespace-token strip
	 * cannot catch. The word-boundary fail-safe handles punctuation and the exact first+last
	 * concatenation check handles the glued case; both fall back to the visible first name.
	 *
	 * @group bb_activity_get_item_user_displayname
	 */
	public function test_get_user_displayname_no_leak_when_last_name_adjacent_to_punctuation() {
		$formats = array( 'first_last_name', 'first_name' );
		// Punctuation-adjacent (caught by the word-boundary fail-safe) plus separator-less glue
		// (caught by the exact first+last concatenation check).
		$drifted = array( 'Anna Smith-Jones', 'Anna Smith, PhD', 'Anna (Smith)', 'O.Smith', 'AnnaSmith', 'SmithAnna' );

		foreach ( $formats as $format ) {
			bp_update_option( 'bp-display-name-format', $format );
			foreach ( $drifted as $display ) {
				$u = self::factory()->user->create();
				wp_update_user(
					array(
						'ID'           => $u,
						'first_name'   => 'Anna',
						'last_name'    => 'Smith',
						'display_name' => $display,
					)
				);
				xprofile_set_field_visibility_level( bp_xprofile_lastname_field_id(), $u, 'loggedin' );

				$GLOBALS['bb_default_display_avatar'] = true;
				$this->set_current_user( 0 );
				$guest = bp_core_get_user_displayname( $u, 0 );
				// The essential privacy property: the hidden surname never surfaces. The visible
				// name always starts with the first name; adjacent non-surname tokens (a suffix
				// like "PhD") may legitimately remain.
				$this->assertStringNotContainsStringIgnoringCase( 'smith', $guest, "leak under {$format} for '{$display}'" );
				$this->assertStringStartsWith( 'Anna', $guest, "first name under {$format} for '{$display}'" );
			}
		}
	}

	/**
	 * The leak fail-safe must be a whole-token check, not a bare substring test: a hidden surname
	 * that is merely a substring of a visible first/middle name (a short surname such as "Lin"
	 * inside "Linda", or "Ng" at the end of "Armstrong") must NOT trigger over-redaction that drops
	 * legitimate name parts. Only the standalone surname token is removed.
	 *
	 * @group bb_activity_get_item_user_displayname
	 */
	public function test_get_user_displayname_does_not_over_redact_surname_substring_of_other_name() {
		$cases = array(
			// first, last (hidden), display_name => expected guest value.
			// Substring / word-ending coincidences must NOT be over-redacted:
			array( 'Linda', 'Lin', 'Linda Marie Lin', 'Linda Marie' ),
			array( 'Louis', 'Ng', 'Louis Armstrong Ng', 'Louis Armstrong' ),
			array( 'Wendy', 'Wu', 'Wendy Wu', 'Wendy' ),
			// Glue variants MUST be redacted (surname removed, non-surname tokens kept):
			array( 'Alex', 'Quillfeather', 'AlexQuillfeather Jr', 'Alex Jr' ),
			array( 'James', 'Smith', 'JamesSmith', 'James' ),
			array( 'Anna', 'Van Der Berg', 'AnnaVanDerBerg', 'Anna' ),
			array( 'Anna', 'Smith', 'Anna Marie Smith', 'Anna Marie' ),
		);

		foreach ( $cases as $case ) {
			list( $first, $last, $display, $expected ) = $case;
			$u = self::factory()->user->create();
			wp_update_user(
				array(
					'ID'           => $u,
					'first_name'   => $first,
					'last_name'    => $last,
					'display_name' => $display,
				)
			);
			xprofile_set_field_visibility_level( bp_xprofile_lastname_field_id(), $u, 'loggedin' );

			$GLOBALS['bb_default_display_avatar'] = true;
			$this->set_current_user( 0 );
			$this->assertSame( $expected, bp_core_get_user_displayname( $u, 0 ), "over-redaction for '{$display}'" );
		}
	}

	/**
	 * A custom display_name that does NOT contain the hidden last name must be preserved, not
	 * over-corrected. "The Boss" (no surname present) stays as-is for a guest; only when the
	 * surname actually survives the strip does the fallback replace it.
	 *
	 * @group bb_activity_get_item_user_displayname
	 */
	public function test_get_user_displayname_preserves_custom_name_without_last_name() {
		$u = self::factory()->user->create();
		wp_update_user(
			array(
				'ID'           => $u,
				'first_name'   => 'Anna',
				'last_name'    => 'Smith',
				'display_name' => 'The Boss',
			)
		);
		xprofile_set_field_visibility_level( bp_xprofile_lastname_field_id(), $u, 'loggedin' );

		$GLOBALS['bb_default_display_avatar'] = true;
		$this->set_current_user( 0 );
		$this->assertSame( 'The Boss', bp_core_get_user_displayname( $u, 0 ) );
	}

	/**
	 * Fail-closed on malformed UTF-8: legacy/imported rows can carry invalid byte sequences that
	 * make the `/u` strip return null. The resolver must treat that as "nothing left" and fall
	 * back to the first name, never return the raw column that still holds the hidden surname.
	 *
	 * @group bb_activity_get_item_user_displayname
	 */
	public function test_get_user_displayname_fails_closed_on_malformed_utf8() {
		global $wpdb;

		$u = $this->create_member_with_hidden_last_name();

		// Write an invalid UTF-8 byte sequence directly (wp_update_user would sanitise it), with
		// the surname present so a fail-open would leak it.
		$wpdb->update( $wpdb->users, array( 'display_name' => "Alex Quillfeather \xFF\xFE" ), array( 'ID' => $u ) );
		clean_user_cache( $u );

		$GLOBALS['bb_default_display_avatar'] = true;
		$this->set_current_user( 0 );
		$guest = bp_core_get_user_displayname( $u, 0 );
		$this->assertStringNotContainsStringIgnoringCase( 'quillfeather', $guest );
		$this->assertSame( 'Alex', $guest );
	}

	/**
	 * Under the Nickname display format the visible name is the nickname (its own visibility
	 * governs it), so the Last Name field's visibility must neither leak a drifted full-name
	 * display_name nor strip a nickname word that coincides with the surname.
	 *
	 * @group bb_activity_get_item_user_displayname
	 */
	public function test_get_user_displayname_nickname_format_uses_nickname_not_stripped_column() {
		bp_update_option( 'bp-display-name-format', 'nickname' );

		// Drift: the stored column holds the full name, but the visible value is the nickname.
		$u = self::factory()->user->create();
		wp_update_user(
			array(
				'ID'           => $u,
				'first_name'   => 'Peter',
				'last_name'    => 'Zebrastripe',
				'nickname'     => 'peternick',
				'display_name' => 'Peter Zebrastripe',
			)
		);
		xprofile_set_field_visibility_level( bp_xprofile_lastname_field_id(), $u, 'loggedin' );
		$GLOBALS['bb_default_display_avatar'] = true;
		$this->set_current_user( 0 );
		$guest = bp_core_get_user_displayname( $u, 0 );
		$this->assertSame( 'peternick', $guest );
		$this->assertStringNotContainsStringIgnoringCase( 'zebrastripe', $guest );
	}

	/**
	 * Existing customers who do NOT hide their last name are wholly unaffected by the strip
	 * (case-insensitive or not): a public last name never enters the strip branch, so the full
	 * name shows to everyone including guests.
	 *
	 * @group bb_activity_get_item_user_displayname
	 */
	public function test_get_user_displayname_public_last_name_unaffected_for_guest() {
		$u = self::factory()->user->create();
		wp_update_user(
			array(
				'ID'           => $u,
				'first_name'   => 'Arianna',
				'last_name'    => 'Julie',
				'display_name' => 'Arianna Julie',
			)
		);
		xprofile_set_field_visibility_level( bp_xprofile_lastname_field_id(), $u, 'public' );
		$GLOBALS['bb_default_display_avatar'] = true;
		$this->set_current_user( 0 );
		$this->assertSame( 'Arianna Julie', bp_core_get_user_displayname( $u, 0 ) );
	}

	/**
	 * Edge: first and last name are the same word in a different case. Hiding the last name still
	 * yields the (visible) first name - never an empty label and never a leak of the raw column.
	 *
	 * @group bb_activity_get_item_user_displayname
	 */
	public function test_get_user_displayname_case_insensitive_first_equals_last() {
		$u = self::factory()->user->create();
		wp_update_user(
			array(
				'ID'           => $u,
				'first_name'   => 'Peter',
				'last_name'    => 'Peter',
				'display_name' => 'Peter PETER',
			)
		);
		xprofile_set_field_visibility_level( bp_xprofile_lastname_field_id(), $u, 'loggedin' );
		$GLOBALS['bb_default_display_avatar'] = true;
		$this->set_current_user( 0 );
		$this->assertSame( 'Peter', bp_core_get_user_displayname( $u, 0 ) );
	}

	/**
	 * The hidden last name must not leak under any of the three Display Name Format options, with
	 * the stored display_name matching what profile sync writes for that format. A guest never
	 * sees the last name; a permitted (logged-in) viewer still gets the format-appropriate name.
	 *
	 * @group bb_activity_get_item_user_displayname
	 */
	public function test_get_user_displayname_hides_last_name_across_all_display_name_formats() {
		$member = self::factory()->user->create();

		// format => [ stored display_name sync would write, expected guest label ].
		$cases = array(
			'first_last_name' => array( 'Peter Zebrastripe', 'Peter' ),
			'first_name'      => array( 'Peter', 'Peter' ),
			'nickname'        => array( 'peternick', 'peternick' ),
		);

		foreach ( $cases as $format => $case ) {
			list( $display, $expected_guest ) = $case;
			bp_update_option( 'bp-display-name-format', $format );

			$u = self::factory()->user->create();
			wp_update_user(
				array(
					'ID'           => $u,
					'first_name'   => 'Peter',
					'last_name'    => 'Zebrastripe',
					'nickname'     => 'peternick',
					'display_name' => $display,
				)
			);
			xprofile_set_field_visibility_level( bp_xprofile_lastname_field_id(), $u, 'loggedin' );

			$GLOBALS['bb_default_display_avatar'] = true;
			$this->set_current_user( 0 );
			$guest = bp_core_get_user_displayname( $u, 0 );
			$this->assertStringNotContainsStringIgnoringCase( 'Zebrastripe', $guest, "guest leak under format {$format}" );
			$this->assertSame( $expected_guest, $guest, "guest label under format {$format}" );

			// A permitted viewer sees the format-appropriate full value (unchanged behaviour).
			$GLOBALS['bb_default_display_avatar'] = true;
			$this->set_current_user( $member );
			$this->assertSame( $display, bp_core_get_user_displayname( $u, $member ), "member label under format {$format}" );
		}
	}

	/**
	 * Drift case flagged in the root doc (C1): the format is first-name-only or nickname, but the
	 * stored display_name column still holds the full "First Last" (profile sync disabled, an
	 * importer, or a pre-Repair state). The strip must still keep the hidden last name from a
	 * guest - the fix does not rely on the column already matching the format.
	 *
	 * @group bb_activity_get_item_user_displayname
	 */
	public function test_get_user_displayname_no_last_name_leak_when_display_name_drifts_from_format() {
		foreach ( array( 'first_name', 'nickname' ) as $format ) {
			bp_update_option( 'bp-display-name-format', $format );

			$u = self::factory()->user->create();
			wp_update_user(
				array(
					'ID'           => $u,
					'first_name'   => 'Peter',
					'last_name'    => 'Zebrastripe',
					'nickname'     => 'peternick',
					'display_name' => 'Peter Zebrastripe', // Stale full name, drifted from the format.
				)
			);
			xprofile_set_field_visibility_level( bp_xprofile_lastname_field_id(), $u, 'loggedin' );

			$GLOBALS['bb_default_display_avatar'] = true;
			$this->set_current_user( 0 );
			$this->assertStringNotContainsStringIgnoringCase(
				'Zebrastripe',
				bp_core_get_user_displayname( $u, 0 ),
				"drift leak under format {$format}"
			);
		}
	}

	/**
	 * When stripping the hidden last name leaves nothing (a bare "Last" display name), the
	 * first-name fallback must itself honour visibility: if the First Name field is also
	 * hidden from the viewer (e.g. via the bp_xprofile_get_hidden_fields_for_user filter),
	 * fall through to the nickname rather than leaking the raw first name.
	 *
	 * @group bb_activity_get_item_user_displayname
	 */
	public function test_get_user_displayname_first_name_fallback_honours_first_name_visibility() {
		$u = $this->create_member_with_hidden_last_name();
		wp_update_user( array( 'ID' => $u, 'display_name' => 'Quillfeather', 'nickname' => 'quillnick' ) );

		$first_name_field_id = (int) bp_xprofile_firstname_field_id();
		$hide_first = static function ( $hidden, $displayed_user_id, $viewer_id ) use ( $u, $first_name_field_id ) {
			if ( (int) $displayed_user_id === (int) $u && 0 === (int) $viewer_id ) {
				$hidden[] = $first_name_field_id;
			}
			return $hidden;
		};
		add_filter( 'bp_xprofile_get_hidden_fields_for_user', $hide_first, 10, 3 );

		$GLOBALS['bb_default_display_avatar'] = true;
		$this->set_current_user( 0 );
		$resolved = bp_core_get_user_displayname( $u, 0 );

		$this->assertStringNotContainsString( 'Quillfeather', $resolved );
		$this->assertStringNotContainsString( 'Alex', $resolved );
		$this->assertSame( 'quillnick', $resolved );

		remove_filter( 'bp_xprofile_get_hidden_fields_for_user', $hide_first, 10 );
	}

	/**
	 * When BOTH the first and last name are hidden from the viewer and the stored display_name is a
	 * separator-less glue ("AlexQuillfeather"), the glue-substitution must not leak the hidden first
	 * name - the token is dropped and the resolution falls through to the nickname.
	 *
	 * @group bb_activity_get_item_user_displayname
	 */
	public function test_get_user_displayname_glue_does_not_leak_hidden_first_name() {
		$u = $this->create_member_with_hidden_last_name();
		wp_update_user( array( 'ID' => $u, 'display_name' => 'AlexQuillfeather', 'nickname' => 'quillnick' ) );

		$first_name_field_id = (int) bp_xprofile_firstname_field_id();
		$hide_first = static function ( $hidden, $displayed_user_id, $viewer_id ) use ( $u, $first_name_field_id ) {
			if ( (int) $displayed_user_id === (int) $u && 0 === (int) $viewer_id ) {
				$hidden[] = $first_name_field_id;
			}
			return $hidden;
		};
		add_filter( 'bp_xprofile_get_hidden_fields_for_user', $hide_first, 10, 3 );

		$GLOBALS['bb_default_display_avatar'] = true;
		$this->set_current_user( 0 );
		$resolved = bp_core_get_user_displayname( $u, 0 );

		$this->assertStringNotContainsString( 'Quillfeather', $resolved );
		$this->assertStringNotContainsString( 'Alex', $resolved );
		$this->assertSame( 'quillnick', $resolved );

		remove_filter( 'bp_xprofile_get_hidden_fields_for_user', $hide_first, 10 );
	}

	/**
	 * A multi-word surname glued with no internal spaces but kept apart from the first name
	 * ("Alex VanDerBerg" for last name "Van Der Berg") must still redact - the whitespace-stripped
	 * comparison catches it as a whole token.
	 *
	 * @group bb_activity_get_item_user_displayname
	 */
	public function test_get_user_displayname_redacts_multiword_surname_glued_without_spaces() {
		$u = self::factory()->user->create();
		wp_update_user(
			array(
				'ID'           => $u,
				'first_name'   => 'Alex',
				'last_name'    => 'Van Der Berg',
				'display_name' => 'Alex VanDerBerg',
			)
		);
		xprofile_set_field_visibility_level( bp_xprofile_lastname_field_id(), $u, 'loggedin' );

		$GLOBALS['bb_default_display_avatar'] = true;
		$this->set_current_user( 0 );
		$guest = bp_core_get_user_displayname( $u, 0 );
		$this->assertStringNotContainsStringIgnoringCase( 'vanderberg', preg_replace( '/\s+/', '', $guest ) );
		$this->assertSame( 'Alex', $guest );
	}

	/**
	 * When the First Name field is genuinely EMPTY (unset on the site, or left blank by the member,
	 * or its data row missing - not merely hidden), the exact first+last glue pattern cannot be
	 * built, so a separator-less glued display_name ("AnnaSmith") that drifted from the fields
	 * (import, the wp-admin "Display name publicly as" dropdown, a third-party write) must still
	 * redact the hidden surname. The whole glued token is dropped - the first-name portion cannot be
	 * recovered or checked against a visibility rule - and resolution falls through to the nickname,
	 * exactly as the punctuation-bounded "Anna-Smith" shape already did. Covers both the "first
	 * last" (surname suffix) and "last first" (surname prefix) glue orders.
	 *
	 * @group bb_activity_get_item_user_displayname
	 */
	public function test_get_user_displayname_redacts_glued_surname_when_first_name_blank() {
		global $wpdb;

		// Reproduce the real production drift: a bulk DB import writes the display_name column
		// directly (bypassing the profile_update sync that would otherwise self-heal an empty first
		// name to the nickname and recompute the column), leaving a glued "AnnaSmith" while the
		// first-name xprofile field is genuinely empty. This site literally carries ~70k such
		// forum-imported users. The no-leak guarantee must hold under EVERY Display Name Format
		// option and both glue orders, so changing bp-display-name-format (or which fields are the
		// first/last name) can never reopen the leak. 'annanick' is the safe fallback across the
		// board: dropped-glue -> empty first-name field -> nickname; and the nickname format returns
		// the nickname outright.
		$fn_id   = bp_xprofile_firstname_field_id();
		$ln_id   = bp_xprofile_lastname_field_id();
		$formats = array( 'first_name', 'first_last_name', 'nickname' );
		$glues   = array(
			'AnnaSmith', // surname suffix (first_last order).
			'SmithAnna', // surname prefix (last_first order).
		);

		foreach ( $formats as $format ) {
			bp_update_option( 'bp-display-name-format', $format );

			foreach ( $glues as $display ) {
				$u = self::factory()->user->create();
				update_user_meta( $u, 'nickname', 'annanick' );

				// Set the profile fields directly (no profile_update sync): last name present + hidden,
				// first name genuinely empty.
				xprofile_set_field_data( $ln_id, $u, 'Smith' );
				xprofile_set_field_data( $fn_id, $u, '' );
				xprofile_set_field_visibility_level( $ln_id, $u, 'loggedin' );

				// Drift the stored column exactly as a direct SQL import would, bypassing every sync.
				$wpdb->update( $wpdb->users, array( 'display_name' => $display ), array( 'ID' => $u ) );
				clean_user_cache( $u );

				$GLOBALS['bb_default_display_avatar'] = true;
				$this->set_current_user( 0 );
				$guest = bp_core_get_user_displayname( $u, 0 );

				$this->assertStringNotContainsStringIgnoringCase( 'smith', $guest, "leak for '{$display}' under format '{$format}'" );
				$this->assertSame( 'annanick', $guest, "fallback for '{$display}' under format '{$format}'" );
			}
		}
	}

	/**
	 * The comment tree is cached per activity with no viewer in the key. A tree cached
	 * while a member viewed it must not hand that member's `user_fullname` to a guest.
	 *
	 * @group bp_activity_comments_cache
	 */
	public function test_cached_comment_tree_reresolves_names_for_current_viewer() {
		$bp                = buddypress();
		$reset_component   = $bp->current_component;
		$reset_action      = $bp->current_action;

		$author = $this->create_member_with_hidden_last_name();
		$other  = self::factory()->user->create();
		$viewer = self::factory()->user->create();

		$activity_id = self::factory()->activity->create(
			array(
				'type'    => 'activity_update',
				'user_id' => $other,
			)
		);

		$comment_id = bp_activity_new_comment(
			array(
				'user_id'     => $author,
				'activity_id' => $activity_id,
				'content'     => 'comment by the member with a hidden last name',
			)
		);

		$reply_id = bp_activity_new_comment(
			array(
				'user_id'     => $author,
				'activity_id' => $activity_id,
				'parent_id'   => $comment_id,
				'content'     => 'nested reply by the same member',
			)
		);

		// Single-activity context: this is the only front-end path that caches the tree.
		// try/finally so a failing assertion cannot leave the BP globals pointing at this
		// activity for every later test in the same process.
		try {
			$bp->current_component = 'activity';
			$bp->current_action    = (string) $activity_id;
			$this->assertTrue( bp_is_single_activity() );

			// 1) A logged-in member views the permalink first and primes the cache with the full name.
			$this->set_current_user( $viewer );
			wp_cache_delete( $activity_id, 'bp_activity_comments' );
			$as_member = $this->get_comment_tree( $activity_id );
			$this->assertSame( 'Alex Quillfeather', $as_member[ $comment_id ]->user_fullname );
			$this->assertSame( 'Alex Quillfeather', $as_member[ $comment_id ]->children[ $reply_id ]->user_fullname );

			$cached = wp_cache_get( $activity_id, 'bp_activity_comments' );
			$this->assertIsArray( $cached, 'The tree must be cached for the single-activity request.' );

			// 2) A guest reads the same activity from the warm cache.
			$this->set_current_user( 0 );
			$as_guest = $this->get_comment_tree( $activity_id );
			$this->assertSame( 'Alex', $as_guest[ $comment_id ]->user_fullname );
			$this->assertSame( 'Alex', $as_guest[ $comment_id ]->children[ $reply_id ]->user_fullname );

			// 3) The reverse direction: a member must still get the name they are entitled to.
			$this->set_current_user( $other );
			$as_other = $this->get_comment_tree( $activity_id );
			$this->assertSame( 'Alex Quillfeather', $as_other[ $comment_id ]->user_fullname );
		} finally {
			$bp->current_component = $reset_component;
			$bp->current_action    = $reset_action;
		}
	}

	/**
	 * The activity loop template tags must output the viewer's name, never the raw
	 * `display_name` joined by the activity query.
	 *
	 * @group bp_get_activity_avatar
	 * @group bp_get_activity_comment_name
	 */
	public function test_activity_loop_template_tags_hide_last_name_from_guest() {
		global $activities_template;

		$template_backup = $activities_template;
		$author          = $this->create_member_with_hidden_last_name();

		$activity_id = self::factory()->activity->create(
			array(
				'type'    => 'activity_update',
				'user_id' => $author,
			)
		);
		$comment_id  = bp_activity_new_comment(
			array(
				'user_id'     => $author,
				'activity_id' => $activity_id,
				'content'     => 'comment',
			)
		);

		$this->set_current_user( 0 );

		// try/finally so a failing assertion cannot leave $activities_template pointing at the
		// fixture for every later test in this process.
		try {
			$this->assertTrue( bp_has_activities( array( 'include' => $activity_id, 'display_comments' => 'threaded', 'show_hidden' => true ) ) );
			bp_the_activity();

			$this->assertSame( 'Alex Quillfeather', $activities_template->activity->display_name, 'Fixture: the raw joined column holds the full name.' );
			$this->assertSame( 'Alex', bp_get_activity_member_display_name() );
			$this->assertStringContainsString( 'alt="Profile photo of Alex"', bp_get_activity_avatar() );
			$this->assertStringNotContainsString( 'Quillfeather', bp_get_activity_avatar() );
			$this->assertStringNotContainsString( 'Quillfeather', bp_get_activity_secondary_avatar() );

			// Inside the comment loop the tags read from `current_comment`.
			$this->assertArrayHasKey( $comment_id, $activities_template->activity->children );
			$activities_template->activity->current_comment = $activities_template->activity->children[ $comment_id ];
			$this->assertSame( 'Alex', bp_get_activity_comment_name() );
			$this->assertStringContainsString( 'alt="Profile photo of Alex"', bp_get_activity_avatar() );
			unset( $activities_template->activity->current_comment );
		} finally {
			$activities_template = $template_backup;
		}
	}

	/**
	 * Group members loop tags must not expose a hidden last name to guests.
	 *
	 * @group bp_get_group_member_name
	 */
	public function test_group_members_loop_hides_last_name_from_guest() {
		global $members_template;

		if ( ! bp_is_active( 'groups' ) ) {
			$this->markTestSkipped( 'Groups component is not active.' );
		}

		$template_backup = $members_template;
		$author          = $this->create_member_with_hidden_last_name();
		$creator         = self::factory()->user->create();
		$group           = self::factory()->group->create( array( 'creator_id' => $creator ) );
		groups_join_group( $group, $author );

		$this->set_current_user( 0 );

		// try/finally so a failing assertion cannot leave $members_template set for later tests.
		try {
			$this->assertTrue( bp_group_has_members( array( 'group_id' => $group, 'exclude_admins_mods' => false ) ) );

			$found = false;
			while ( bp_group_members() ) {
				bp_group_the_member();
				if ( (int) bp_get_group_member_id() !== $author ) {
					continue;
				}
				$found = true;
				$this->assertSame( 'Alex', bp_get_group_member_name() );
				$this->assertStringContainsString( 'alt="Profile photo of Alex"', bp_get_group_member_avatar() );
				$this->assertStringNotContainsString( 'Quillfeather', bp_get_group_member_avatar_thumb() );
				$this->assertStringNotContainsString( 'Quillfeather', bp_get_group_member_avatar_mini() );
			}
			$this->assertTrue( $found, 'The member with the hidden last name must be in the loop.' );
		} finally {
			$members_template = $template_backup;
		}
	}

	/**
	 * Fetch the nested comment tree for an activity through the public API.
	 *
	 * @param int $activity_id Activity ID.
	 * @return array Comment tree keyed by comment ID.
	 */
	protected function get_comment_tree( $activity_id ) {
		$activity = new BP_Activity_Activity( $activity_id );

		return BP_Activity_Activity::get_activity_comments( $activity_id, $activity->mptt_left, $activity->mptt_right );
	}
	/**
	 * The bbPress profile screens (`/forums/user/{slug}/`) read the displayed user's name via
	 * bbp_get_displayed_user_field( 'display_name' ) - the raw WP column, which always holds the
	 * full name. That value reaches the page title, the theme-compat `post_title` (rendered into
	 * the page heading and the BuddyBoss App `#bbapp-title` span) and every sub-nav link title in
	 * the theme's `bbpress/user-details.php`, so a guest could read a hidden last name there even
	 * though the BuddyBoss member profile at /members/{slug}/ redacted it.
	 *
	 * @group bb_activity_get_item_user_displayname
	 * @group bbp_get_displayed_user_field
	 */
	public function test_bbp_displayed_user_field_hides_last_name_from_guest() {
		$author = $this->create_member_with_hidden_last_name();
		$viewer = self::factory()->user->create();

		$bbp            = bbpress();
		$displayed_back = isset( $bbp->displayed_user ) ? $bbp->displayed_user : null;

		try {
			$bbp->displayed_user = get_userdata( $author );
			$this->assertSame( 'Alex Quillfeather', $bbp->displayed_user->display_name, 'The stored column must keep the full name.' );

			$GLOBALS['bb_default_display_avatar'] = true;

			// Guest: the hidden last name must not be returned.
			$this->set_current_user( 0 );
			$this->assertSame( 'Alex', bbp_get_displayed_user_field( 'display_name' ) );

			// A logged-in member is entitled to the full name.
			$this->set_current_user( $viewer );
			$this->assertSame( 'Alex Quillfeather', bbp_get_displayed_user_field( 'display_name' ) );

			// The member themself always sees their own full name.
			$this->set_current_user( $author );
			$this->assertSame( 'Alex Quillfeather', bbp_get_displayed_user_field( 'display_name' ) );

			// Unrelated fields are untouched by the redaction.
			$this->set_current_user( 0 );
			$this->assertSame( get_userdata( $author )->user_nicename, bbp_get_displayed_user_field( 'user_nicename' ) );

			// The documented "raw" filter still returns the stored column.
			$this->assertSame( 'Alex Quillfeather', bbp_get_displayed_user_field( 'display_name', 'raw' ) );
		} finally {
			$bbp->displayed_user = $displayed_back;
		}
	}

	/**
	 * bbp_get_reply_author() printed the raw WP display_name for a reply's author, leaking a last
	 * name hidden by profile-field visibility on every reply in a topic. It now resolves the name
	 * for the current viewer.
	 *
	 * @group bb_activity_get_item_user_displayname
	 * @group bbp_get_reply_author
	 */
	public function test_bbp_reply_author_hides_last_name_from_guest() {
		if ( ! function_exists( 'bbp_get_reply_author' ) ) {
			$this->markTestSkipped( 'Forums component not loaded.' );
		}

		$author = $this->create_member_with_hidden_last_name();
		$member = self::factory()->user->create();
		$reply  = self::factory()->post->create( array( 'post_author' => $author ) );

		$GLOBALS['bb_default_display_avatar'] = true;
		$this->set_current_user( 0 );
		$guest = bbp_get_reply_author( $reply );
		$this->assertStringContainsString( 'Alex', $guest );
		$this->assertStringNotContainsString( 'Quillfeather', $guest );

		$GLOBALS['bb_default_display_avatar'] = true;
		$this->set_current_user( $member );
		$this->assertStringContainsString( 'Alex Quillfeather', bbp_get_reply_author( $reply ) );
	}

	/**
	 * bbp_get_topic_author() had the same raw-display_name leak for a topic's author.
	 *
	 * @group bb_activity_get_item_user_displayname
	 * @group bbp_get_topic_author
	 */
	public function test_bbp_topic_author_hides_last_name_from_guest() {
		if ( ! function_exists( 'bbp_get_topic_author' ) ) {
			$this->markTestSkipped( 'Forums component not loaded.' );
		}

		$author = $this->create_member_with_hidden_last_name();
		$member = self::factory()->user->create();
		$topic  = self::factory()->post->create( array( 'post_author' => $author ) );

		$GLOBALS['bb_default_display_avatar'] = true;
		$this->set_current_user( 0 );
		$guest = bbp_get_topic_author( $topic );
		$this->assertStringContainsString( 'Alex', $guest );
		$this->assertStringNotContainsString( 'Quillfeather', $guest );

		$GLOBALS['bb_default_display_avatar'] = true;
		$this->set_current_user( $member );
		$this->assertStringContainsString( 'Alex Quillfeather', bbp_get_topic_author( $topic ) );
	}

	/**
	 * bbp_get_user_profile_edit_link() built its anchor text from the raw display_name; the linked
	 * name now honours the viewer's visibility of the last name.
	 *
	 * @group bb_activity_get_item_user_displayname
	 * @group bbp_get_user_profile_edit_link
	 */
	public function test_bbp_user_profile_edit_link_hides_last_name_from_guest() {
		if ( ! function_exists( 'bbp_get_user_profile_edit_link' ) ) {
			$this->markTestSkipped( 'Forums component not loaded.' );
		}

		$author = $this->create_member_with_hidden_last_name();
		$member = self::factory()->user->create();

		$GLOBALS['bb_default_display_avatar'] = true;
		$this->set_current_user( 0 );
		$guest_link = bbp_get_user_profile_edit_link( $author );
		$this->assertStringContainsString( 'Alex', $guest_link );
		$this->assertStringNotContainsString( 'Quillfeather', $guest_link );

		$GLOBALS['bb_default_display_avatar'] = true;
		$this->set_current_user( $member );
		$this->assertStringContainsString( 'Alex Quillfeather', bbp_get_user_profile_edit_link( $author ) );
	}
}
