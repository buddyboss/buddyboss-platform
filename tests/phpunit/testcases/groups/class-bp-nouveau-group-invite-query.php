<?php
/**
 * @group groups
 * @group invites
 */
class BP_Tests_BP_Nouveau_Group_Invite_Query extends BP_UnitTestCase {

	/**
	 * Meta key used by the restrict-invites-to-friends setting.
	 *
	 * @var string
	 */
	protected static $restrict_key = '_bp_nouveau_restrict_invites_to_friends';

	public function setUp(): void {
		parent::setUp();

		if ( ! class_exists( 'BP_Nouveau_Group_Invite_Query' ) ) {
			require_once buddypress()->plugin_dir . 'bp-templates/bp-nouveau/includes/groups/classes.php';
		}
	}

	/**
	 * Run a potential-invites query and clean up the instance hooks afterwards.
	 *
	 * Each BP_Nouveau_Group_Invite_Query instance registers its callbacks on
	 * global hooks and never removes them, so instances leak into later
	 * queries in the same request unless removed here.
	 *
	 * @param array $args Query args.
	 * @return BP_Nouveau_Group_Invite_Query The completed query.
	 */
	protected function run_invite_query( $args ) {
		$query = new BP_Nouveau_Group_Invite_Query( $args );

		remove_action( 'bp_pre_user_query_construct', array( $query, 'build_exclude_args' ) );
		remove_action( 'bp_pre_user_query', array( $query, 'build_meta_query' ) );

		return $query;
	}

	/**
	 * Get the sorted user IDs found by a query.
	 *
	 * @param BP_Nouveau_Group_Invite_Query $query The completed query.
	 * @return array Sorted user IDs.
	 */
	protected function get_user_ids( $query ) {
		$user_ids = array_map( 'intval', (array) $query->user_ids );
		sort( $user_ids );

		return $user_ids;
	}

	/**
	 * Assert which build_meta_query() branch produced the query.
	 *
	 * The fast path appends only a WHERE condition; the WP_Meta_Query
	 * fallback adds a usermeta LEFT JOIN to the SELECT clause.
	 *
	 * Matches the JOIN specifically rather than any mention of the usermeta
	 * table: BP_User_Query selects *from* usermeta for `type => 'popular'`, so
	 * a bare table-name search would report a join that is not there.
	 *
	 * @param BP_Nouveau_Group_Invite_Query $query     The completed query.
	 * @param bool                          $fast_path Whether the fast path is expected.
	 * @param string                        $message   Optional failure message.
	 */
	protected function assertQueryPath( $query, $fast_path, $message = '' ) {
		global $wpdb;

		$joined = (bool) preg_match( '/JOIN\s+' . preg_quote( $wpdb->usermeta, '/' ) . '\b/i', $query->uid_clauses['select'] );

		if ( $fast_path ) {
			$this->assertFalse( $joined, $message ? $message : 'Fast path must not join usermeta.' );
		} else {
			$this->assertTrue( $joined, $message ? $message : 'WP_Meta_Query path must join usermeta.' );
		}
	}

	/**
	 * Return the NOT IN clauses present in $query but not in $baseline.
	 *
	 * BP_User_Query emits its own NOT IN clauses (group members, moderation),
	 * so comparing against a baseline built from identical args isolates the
	 * clauses build_meta_query() appended.
	 *
	 * @param BP_Nouveau_Group_Invite_Query $baseline Query with no exclusion applied.
	 * @param BP_Nouveau_Group_Invite_Query $query    Query under test.
	 * @return array Array of int arrays, one per appended clause.
	 */
	protected function added_not_in_clauses( $baseline, $query ) {
		$extract = function ( $where ) {
			preg_match_all( '/NOT IN \(([0-9,]+)\)/', $where, $matches );

			return $matches[1];
		};

		$before = $extract( $baseline->uid_clauses['where'] );
		$after  = $extract( $query->uid_clauses['where'] );

		$added = array();

		foreach ( array_diff_assoc( $after, $before ) as $list ) {
			$added[] = array_map( 'intval', explode( ',', $list ) );
		}

		return $added;
	}

	/**
	 * Build the standard lone NOT EXISTS meta_query clause.
	 *
	 * @param array $extra Extra keys to merge into the clause.
	 * @return array The meta_query.
	 */
	protected function not_exists_meta_query( $extra = array() ) {
		return array(
			array_merge(
				array(
					'key'     => self::$restrict_key,
					'compare' => 'NOT EXISTS',
				),
				$extra
			),
		);
	}

	/**
	 * The lone NOT EXISTS clause must exclude exactly the opted-in users,
	 * via the fast path.
	 */
	public function test_lone_not_exists_clause_excludes_opted_in_users() {
		$creator = self::factory()->user->create();
		$users   = self::factory()->user->create_many( 5 );
		$group   = self::factory()->group->create( array( 'creator_id' => $creator ) );

		update_user_meta( $users[0], self::$restrict_key, 1 );
		update_user_meta( $users[1], self::$restrict_key, 1 );

		$query = $this->run_invite_query( array(
			'group_id'   => $group,
			'scope'      => 'members',
			'type'       => 'alphabetical',
			'per_page'   => 100,
			'meta_query' => $this->not_exists_meta_query(),
		) );
		$found = $this->get_user_ids( $query );

		$this->assertQueryPath( $query, true );
		$this->assertNotContains( $users[0], $found, 'Opted-in user must be excluded.' );
		$this->assertNotContains( $users[1], $found, 'Opted-in user must be excluded.' );
		$this->assertNotContains( $creator, $found, 'Group members must be excluded.' );
		$this->assertContains( $users[2], $found );
		$this->assertContains( $users[3], $found );
		$this->assertContains( $users[4], $found );
	}

	/**
	 * The fast path must return the same set as the WP_Meta_Query fall-through.
	 *
	 * A keyed clause misses the fast-path guard and takes the legacy
	 * WP_Meta_Query path, so both paths can be compared on identical data.
	 */
	public function test_fast_path_matches_wp_meta_query_path() {
		$creator = self::factory()->user->create();
		$users   = self::factory()->user->create_many( 4 );
		$group   = self::factory()->group->create( array( 'creator_id' => $creator ) );

		update_user_meta( $users[0], self::$restrict_key, 1 );

		$base_args = array(
			'group_id' => $group,
			'scope'    => 'members',
			'type'     => 'alphabetical',
			'per_page' => 100,
		);

		$fast_query = $this->run_invite_query( array_merge( $base_args, array(
			'meta_query' => $this->not_exists_meta_query(),
		) ) );

		$legacy_query = $this->run_invite_query( array_merge( $base_args, array(
			'meta_query' => array(
				'restrict_clause' => array(
					'key'     => self::$restrict_key,
					'compare' => 'NOT EXISTS',
				),
			),
		) ) );

		$this->assertQueryPath( $fast_query, true );
		$this->assertQueryPath( $legacy_query, false );

		$fast   = $this->get_user_ids( $fast_query );
		$legacy = $this->get_user_ids( $legacy_query );

		$this->assertNotEmpty( $fast );
		$this->assertSame( $legacy, $fast, 'Fast path and WP_Meta_Query path must return identical user IDs.' );
		$this->assertSame( (int) $legacy_query->total_users, (int) $fast_query->total_users, 'Both paths must report the same total user count.' );
	}

	/**
	 * A meta_query with a relation and multiple clauses must fall back to
	 * WP_Meta_Query and still honour the NOT EXISTS exclusion.
	 */
	public function test_relation_multi_clause_falls_back() {
		$creator = self::factory()->user->create();
		$users   = self::factory()->user->create_many( 3 );
		$group   = self::factory()->group->create( array( 'creator_id' => $creator ) );

		update_user_meta( $users[0], self::$restrict_key, 1 );

		$query = $this->run_invite_query( array(
			'group_id'   => $group,
			'scope'      => 'members',
			'type'       => 'alphabetical',
			'per_page'   => 100,
			'meta_query' => array(
				'relation' => 'AND',
				array(
					'key'     => self::$restrict_key,
					'compare' => 'NOT EXISTS',
				),
				array(
					'key'     => '_bp_some_other_meta_key',
					'compare' => 'NOT EXISTS',
				),
			),
		) );
		$found = $this->get_user_ids( $query );

		$this->assertQueryPath( $query, false );
		$this->assertNotContains( $users[0], $found, 'Opted-in user must be excluded on the multi-clause fallback path.' );
		$this->assertContains( $users[1], $found );
		$this->assertContains( $users[2], $found );
	}

	/**
	 * With nobody opted in, the fast path must leave the query untouched and
	 * return everyone who is not a member.
	 */
	public function test_fast_path_with_no_opted_in_users() {
		$creator = self::factory()->user->create();
		$users   = self::factory()->user->create_many( 3 );
		$group   = self::factory()->group->create( array( 'creator_id' => $creator ) );

		$query = $this->run_invite_query( array(
			'group_id'   => $group,
			'scope'      => 'members',
			'type'       => 'alphabetical',
			'per_page'   => 100,
			'meta_query' => $this->not_exists_meta_query(),
		) );
		$found = $this->get_user_ids( $query );

		$this->assertQueryPath( $query, true );
		$this->assertNotContains( $creator, $found );
		$this->assertContains( $users[0], $found );
		$this->assertContains( $users[1], $found );
		$this->assertContains( $users[2], $found );
	}

	/**
	 * A falsy stored meta value still means the row exists: the user must be
	 * excluded, matching NOT EXISTS row-presence semantics.
	 */
	public function test_falsy_meta_value_is_still_excluded() {
		$creator = self::factory()->user->create();
		$users   = self::factory()->user->create_many( 2 );
		$group   = self::factory()->group->create( array( 'creator_id' => $creator ) );

		update_user_meta( $users[0], self::$restrict_key, '0' );

		$query = $this->run_invite_query( array(
			'group_id'   => $group,
			'scope'      => 'members',
			'type'       => 'alphabetical',
			'per_page'   => 100,
			'meta_query' => $this->not_exists_meta_query(),
		) );
		$found = $this->get_user_ids( $query );

		$this->assertQueryPath( $query, true );
		$this->assertNotContains( $users[0], $found, 'A user with a falsy meta value still has the row and must be excluded.' );
		$this->assertContains( $users[1], $found );
	}

	/**
	 * Lowercase compare and compare_key '=' must stay on the fast path;
	 * any other compare_key must fall back to WP_Meta_Query.
	 */
	public function test_guard_edges_select_the_right_path() {
		$creator = self::factory()->user->create();
		$users   = self::factory()->user->create_many( 2 );
		$group   = self::factory()->group->create( array( 'creator_id' => $creator ) );

		update_user_meta( $users[0], self::$restrict_key, 1 );

		$base_args = array(
			'group_id' => $group,
			'scope'    => 'members',
			'type'     => 'alphabetical',
			'per_page' => 100,
		);

		// Lowercase compare: fast path.
		$query = $this->run_invite_query( array_merge( $base_args, array(
			'meta_query' => $this->not_exists_meta_query( array( 'compare' => 'not exists' ) ),
		) ) );
		$this->assertQueryPath( $query, true );
		$this->assertNotContains( $users[0], $this->get_user_ids( $query ) );

		// compare_key '=': fast path.
		$query = $this->run_invite_query( array_merge( $base_args, array(
			'meta_query' => $this->not_exists_meta_query( array( 'compare_key' => '=' ) ),
		) ) );
		$this->assertQueryPath( $query, true );
		$this->assertNotContains( $users[0], $this->get_user_ids( $query ) );

		// compare_key 'LIKE': semantics the fast path cannot replicate — fall back.
		$query = $this->run_invite_query( array_merge( $base_args, array(
			'meta_query' => $this->not_exists_meta_query( array( 'compare_key' => 'LIKE' ) ),
		) ) );
		$this->assertQueryPath( $query, false );
	}

	/**
	 * The cached excluded-ID list must be invalidated when a member toggles
	 * the restrict-invites setting between queries.
	 */
	public function test_cache_invalidated_when_setting_changes() {
		$creator = self::factory()->user->create();
		$users   = self::factory()->user->create_many( 3 );
		$group   = self::factory()->group->create( array( 'creator_id' => $creator ) );

		update_user_meta( $users[0], self::$restrict_key, 1 );

		$args = array(
			'group_id'   => $group,
			'scope'      => 'members',
			'type'       => 'alphabetical',
			'per_page'   => 100,
			'meta_query' => $this->not_exists_meta_query(),
		);

		// Prime the cache.
		$found = $this->get_user_ids( $this->run_invite_query( $args ) );
		$this->assertNotContains( $users[0], $found );
		$this->assertContains( $users[1], $found );

		// Toggling the setting must bust the cache via the user meta hooks.
		update_user_meta( $users[1], self::$restrict_key, 1 );

		$found = $this->get_user_ids( $this->run_invite_query( $args ) );
		$this->assertNotContains( $users[1], $found, 'A newly opted-in user must be excluded — a stale cache would still list them.' );
		$this->assertNotContains( $users[0], $found );
		$this->assertContains( $users[2], $found );

		// Opting back out must bust the cache again.
		delete_user_meta( $users[1], self::$restrict_key );

		$found = $this->get_user_ids( $this->run_invite_query( $args ) );
		$this->assertContains( $users[1], $found, 'An opted-out user must reappear — a stale cache would still exclude them.' );
		$this->assertNotContains( $users[0], $found );
	}

	/**
	 * A non-array meta_query must not fatal and must not filter anyone out.
	 */
	public function test_non_array_meta_query_does_not_fatal() {
		$creator = self::factory()->user->create();
		$users   = self::factory()->user->create_many( 2 );
		$group   = self::factory()->group->create( array( 'creator_id' => $creator ) );

		$query = $this->run_invite_query( array(
			'group_id'   => $group,
			'scope'      => 'members',
			'type'       => 'alphabetical',
			'per_page'   => 100,
			'meta_query' => 'not-an-array',
		) );
		$found = $this->get_user_ids( $query );

		$this->assertContains( $users[0], $found );
		$this->assertContains( $users[1], $found );
	}

	/**
	 * Exceeding the excluded-IDs limit must fall back to WP_Meta_Query with
	 * identical exclusions.
	 */
	public function test_over_limit_falls_back_to_wp_meta_query() {
		$creator = self::factory()->user->create();
		$users   = self::factory()->user->create_many( 4 );
		$group   = self::factory()->group->create( array( 'creator_id' => $creator ) );

		update_user_meta( $users[0], self::$restrict_key, 1 );
		update_user_meta( $users[1], self::$restrict_key, 1 );

		$force_fallback = function () {
			return 1;
		};
		add_filter( 'bb_nouveau_group_invites_excluded_ids_limit', $force_fallback );

		$query = $this->run_invite_query( array(
			'group_id'   => $group,
			'scope'      => 'members',
			'type'       => 'alphabetical',
			'per_page'   => 100,
			'meta_query' => $this->not_exists_meta_query(),
		) );
		$found = $this->get_user_ids( $query );

		remove_filter( 'bb_nouveau_group_invites_excluded_ids_limit', $force_fallback );

		$this->assertQueryPath( $query, false );
		$this->assertNotContains( $users[0], $found, 'Opted-in user must be excluded on the fallback path.' );
		$this->assertNotContains( $users[1], $found, 'Opted-in user must be excluded on the fallback path.' );
		$this->assertContains( $users[2], $found );
		$this->assertContains( $users[3], $found );
	}

	/**
	 * The exclusion is emitted as one NOT IN clause holding exactly the
	 * opted-in IDs.
	 *
	 * Asserts clause *contents*, not just that a clause exists: an
	 * implementation that emitted the wrong IDs, or the right IDs spread over
	 * redundant clauses, would still return the correct users on a small
	 * fixture and pass a count-only assertion.
	 */
	public function test_excluded_ids_emitted_as_a_single_exact_not_in_clause() {
		$creator = self::factory()->user->create();
		$users   = self::factory()->user->create_many( 5 );
		$group   = self::factory()->group->create( array( 'creator_id' => $creator ) );

		$args = array(
			'group_id'   => $group,
			'scope'      => 'members',
			'type'       => 'alphabetical',
			'per_page'   => 100,
			'meta_query' => $this->not_exists_meta_query(),
		);

		// Baseline before anyone opts in: BP_User_Query emits its own NOT IN
		// clauses (group members, moderation), so only the delta is ours.
		$baseline = $this->run_invite_query( $args );

		update_user_meta( $users[0], self::$restrict_key, 1 );
		update_user_meta( $users[1], self::$restrict_key, 1 );
		update_user_meta( $users[2], self::$restrict_key, 1 );

		$query = $this->run_invite_query( $args );
		$found = $this->get_user_ids( $query );

		$this->assertQueryPath( $query, true );

		$added = $this->added_not_in_clauses( $baseline, $query );

		$this->assertCount( 1, $added, 'The exclusion must be a single NOT IN clause.' );

		$expected = array( (int) $users[0], (int) $users[1], (int) $users[2] );
		$actual   = $added[0];
		sort( $expected );
		sort( $actual );

		$this->assertSame( $expected, $actual, 'The NOT IN clause must hold exactly the opted-in IDs.' );

		$this->assertNotContains( $users[0], $found );
		$this->assertNotContains( $users[1], $found );
		$this->assertNotContains( $users[2], $found );
		$this->assertContains( $users[3], $found );
		$this->assertContains( $users[4], $found );
	}

	/**
	 * A failed exclusion lookup must fail closed.
	 *
	 * If the lookup errors the excluded list is empty, so emitting it would
	 * silently drop the restriction and make opted-in members invitable. The
	 * query must fall through to WP_Meta_Query instead, and must not cache the
	 * failed result.
	 */
	public function test_lookup_failure_fails_closed() {
		global $wpdb;

		$creator = self::factory()->user->create();
		$users   = self::factory()->user->create_many( 3 );
		$group   = self::factory()->group->create( array( 'creator_id' => $creator ) );

		update_user_meta( $users[0], self::$restrict_key, 1 );

		// Resolve the real cache key after the meta write, which retires the
		// previous incrementor, so the assertion below targets the key this
		// request would actually write to.
		$list_key = 'bb_restrict_invites_ids_' . bp_core_get_incrementor( 'bb_nouveau_group_invites' );

		wp_cache_delete( $list_key, 'bb_nouveau_group_invites' );

		$break_lookup = function ( $sql ) {
			if ( false !== strpos( $sql, 'SELECT DISTINCT user_id FROM' ) ) {
				return 'SELECT bb_no_such_column FROM bb_no_such_table';
			}

			return $sql;
		};

		$suppress = $wpdb->suppress_errors( true );
		add_filter( 'query', $break_lookup );

		$query = $this->run_invite_query( array(
			'group_id'   => $group,
			'scope'      => 'members',
			'type'       => 'alphabetical',
			'per_page'   => 100,
			'meta_query' => $this->not_exists_meta_query(),
		) );
		$found = $this->get_user_ids( $query );

		remove_filter( 'query', $break_lookup );
		$wpdb->suppress_errors( $suppress );

		$this->assertQueryPath( $query, false, 'A failed lookup must fall back to WP_Meta_Query.' );
		$this->assertNotContains( $users[0], $found, 'Opted-in user must still be excluded when the lookup fails.' );
		$this->assertContains( $users[1], $found );
		$this->assertContains( $users[2], $found );
		$this->assertFalse(
			wp_cache_get( $list_key, 'bb_nouveau_group_invites' ),
			'A failed lookup must not be cached.'
		);
	}

	/**
	 * Count exclusion lookups issued while running a callback.
	 *
	 * @param callable $callback Code to run.
	 * @return int Number of exclusion lookups issued.
	 */
	protected function count_exclusion_lookups( $callback ) {
		$count = 0;

		$counter = function ( $sql ) use ( &$count ) {
			if ( false !== strpos( $sql, 'SELECT DISTINCT user_id FROM' ) ) {
				$count++;
			}

			return $sql;
		};

		add_filter( 'query', $counter );
		$callback();
		remove_filter( 'query', $counter );

		return $count;
	}

	/**
	 * The excluded-ID list is cached and reused on the next query.
	 *
	 * Without this the caching layer is unverified: an implementation that
	 * never wrote or read the cache would still pass every other test.
	 */
	public function test_excluded_ids_are_cached_and_reused() {
		$creator = self::factory()->user->create();
		$users   = self::factory()->user->create_many( 3 );
		$group   = self::factory()->group->create( array( 'creator_id' => $creator ) );

		update_user_meta( $users[0], self::$restrict_key, 1 );

		$args = array(
			'group_id'   => $group,
			'scope'      => 'members',
			'type'       => 'alphabetical',
			'per_page'   => 100,
			'meta_query' => $this->not_exists_meta_query(),
		);

		$first  = null;
		$cold   = $this->count_exclusion_lookups( function () use ( $args, &$first ) {
			$first = $this->run_invite_query( $args );
		} );

		$second = null;
		$warm   = $this->count_exclusion_lookups( function () use ( $args, &$second ) {
			$second = $this->run_invite_query( $args );
		} );

		$this->assertSame( 1, $cold, 'A cold cache must issue exactly one exclusion lookup.' );
		$this->assertSame( 0, $warm, 'A warm cache must issue no exclusion lookup.' );
		$this->assertSame( $this->get_user_ids( $first ), $this->get_user_ids( $second ), 'Cached and uncached runs must agree.' );
		$this->assertNotContains( $users[0], $this->get_user_ids( $second ) );
	}

	/**
	 * A meta_query naming any other key must not reach the inline lookup.
	 *
	 * The AJAX handler merges $_POST wholesale, so an invite-capable member can
	 * supply their own meta_query. Only the invite screen's own key may take the
	 * fast path; anything else falls through to WP_Meta_Query as before.
	 */
	public function test_foreign_meta_key_never_reaches_the_inline_lookup() {
		$creator = self::factory()->user->create();
		$users   = self::factory()->user->create_many( 2 );
		$group   = self::factory()->group->create( array( 'creator_id' => $creator ) );

		update_user_meta( $users[0], 'bb_some_other_key', 1 );

		$query   = null;
		$lookups = $this->count_exclusion_lookups( function () use ( $group, &$query ) {
			$query = $this->run_invite_query( array(
				'group_id'   => $group,
				'scope'      => 'members',
				'type'       => 'alphabetical',
				'per_page'   => 100,
				'meta_query' => array(
					array(
						'key'     => 'bb_some_other_key',
						'compare' => 'NOT EXISTS',
					),
				),
			) );
		} );

		$this->assertSame( 0, $lookups, 'A foreign meta key must not trigger the inline lookup.' );
		$this->assertQueryPath( $query, false, 'A foreign meta key must use the WP_Meta_Query path.' );
		$this->assertNotContains( $users[0], $this->get_user_ids( $query ), 'The legacy path must still apply the exclusion.' );
	}

	/**
	 * An over-ceiling result is never cached, and the verdict is reused.
	 */
	public function test_over_ceiling_result_is_not_cached_and_verdict_is_reused() {
		$creator = self::factory()->user->create();
		$users   = self::factory()->user->create_many( 3 );
		$group   = self::factory()->group->create( array( 'creator_id' => $creator ) );

		update_user_meta( $users[0], self::$restrict_key, 1 );
		update_user_meta( $users[1], self::$restrict_key, 1 );

		$tiny_ceiling = function () {
			return 1;
		};
		add_filter( 'bb_nouveau_group_invites_excluded_ids_limit', $tiny_ceiling );

		$args = array(
			'group_id'   => $group,
			'scope'      => 'members',
			'type'       => 'alphabetical',
			'per_page'   => 100,
			'meta_query' => $this->not_exists_meta_query(),
		);

		$first = null;
		$cold  = $this->count_exclusion_lookups( function () use ( $args, &$first ) {
			$first = $this->run_invite_query( $args );
		} );

		$second = null;
		$warm   = $this->count_exclusion_lookups( function () use ( $args, &$second ) {
			$second = $this->run_invite_query( $args );
		} );

		remove_filter( 'bb_nouveau_group_invites_excluded_ids_limit', $tiny_ceiling );

		$this->assertSame( 1, $cold, 'The first over-ceiling query issues the lookup.' );
		$this->assertSame( 0, $warm, 'The over-ceiling verdict must be reused, not re-probed.' );
		$this->assertFalse(
			wp_cache_get( 'bb_restrict_invites_ids_' . bp_core_get_incrementor( 'bb_nouveau_group_invites' ), 'bb_nouveau_group_invites' ),
			'A truncated over-ceiling list must never be cached.'
		);
		$this->assertQueryPath( $first, false );
		$this->assertQueryPath( $second, false );

		foreach ( array( $first, $second ) as $query ) {
			$found = $this->get_user_ids( $query );
			$this->assertNotContains( $users[0], $found, 'Opted-in users must stay excluded over the ceiling.' );
			$this->assertNotContains( $users[1], $found, 'Opted-in users must stay excluded over the ceiling.' );
		}
	}

	/**
	 * A rebuild that raced a setting change must not overwrite fresh state.
	 *
	 * The cache keys carry an incrementor captured before the lookup, so a write
	 * that lands after an invalidation targets a retired key.
	 */
	public function test_stale_rebuild_write_is_never_served() {
		$creator = self::factory()->user->create();
		$users   = self::factory()->user->create_many( 3 );
		$group   = self::factory()->group->create( array( 'creator_id' => $creator ) );

		$args = array(
			'group_id'   => $group,
			'scope'      => 'members',
			'type'       => 'alphabetical',
			'per_page'   => 100,
			'meta_query' => $this->not_exists_meta_query(),
		);

		// A rebuild starts here and captures this incrementor.
		$stale_key = 'bb_restrict_invites_ids_' . bp_core_get_incrementor( 'bb_nouveau_group_invites' );

		// A member opts in mid-rebuild, which retires that incrementor.
		update_user_meta( $users[0], self::$restrict_key, 1 );

		// The in-flight rebuild now writes its pre-toggle snapshot.
		wp_cache_set( $stale_key, array(), 'bb_nouveau_group_invites', HOUR_IN_SECONDS );

		$found = $this->get_user_ids( $this->run_invite_query( $args ) );

		$this->assertNotContains( $users[0], $found, 'A stale rebuild write must not resurrect an opted-in user.' );
		$this->assertContains( $users[1], $found );
		$this->assertContains( $users[2], $found );
	}
	/**
	 * The rebuild lock is released by its owner and never by a loser.
	 */
	public function test_rebuild_lock_is_released_by_owner_and_not_by_a_loser() {
		$creator = self::factory()->user->create();
		$users   = self::factory()->user->create_many( 2 );
		$group   = self::factory()->group->create( array( 'creator_id' => $creator ) );

		update_user_meta( $users[0], self::$restrict_key, 1 );

		$args = array(
			'group_id'   => $group,
			'scope'      => 'members',
			'type'       => 'alphabetical',
			'per_page'   => 100,
			'meta_query' => $this->not_exists_meta_query(),
		);

		$lock_key = 'bb_restrict_invites_lock_' . bp_core_get_incrementor( 'bb_nouveau_group_invites' );

		// Owner path: acquires the lock, rebuilds, releases.
		$this->run_invite_query( $args );

		$this->assertFalse(
			wp_cache_get( $lock_key, 'bb_nouveau_group_invites' ),
			'The rebuild owner must release the lock.'
		);

		// Loser path: another request holds the lock and the list is cold.
		wp_cache_delete( 'bb_restrict_invites_ids_' . bp_core_get_incrementor( 'bb_nouveau_group_invites' ), 'bb_nouveau_group_invites' );
		wp_cache_set( $lock_key, 1, 'bb_nouveau_group_invites', 30 );

		$found = $this->get_user_ids( $this->run_invite_query( $args ) );

		$this->assertNotContains( $users[0], $found, 'A loser must still apply the exclusion.' );
		$this->assertContains( $users[1], $found );
		$this->assertNotFalse(
			wp_cache_get( $lock_key, 'bb_nouveau_group_invites' ),
			'A loser must not release a lock it does not own.'
		);

		wp_cache_delete( $lock_key, 'bb_nouveau_group_invites' );
	}

	/**
	 * A write under a filtered meta key still busts the cache, and both query
	 * paths stay in agreement.
	 *
	 * Writers run the key through the `bp_get_user_meta_key` filter, so the
	 * invalidator must match the filtered form as well as the raw one.
	 *
	 * Note the known, pre-existing limitation this pins: the reader does *not*
	 * apply that filter — `bp_nouveau_get_group_potential_invites()` hardcodes
	 * the raw key in the meta_query — so on a site that filters it, rows are
	 * stored under one key and queried under another and the exclusion does not
	 * apply. That is true of the `WP_Meta_Query` path too, and predates this
	 * optimisation, so the assertion here is that the two paths agree rather
	 * than that the exclusion works. Fixing the asymmetry belongs in its own
	 * ticket: it changes member-visible behaviour and would also have to widen
	 * the fast path's key gate.
	 */
	public function test_invalidation_matches_a_filtered_meta_key() {
		$creator = self::factory()->user->create();
		$users   = self::factory()->user->create_many( 2 );
		$group   = self::factory()->group->create( array( 'creator_id' => $creator ) );

		// Warm the cache so there is something to invalidate.
		$this->run_invite_query( array(
			'group_id'   => $group,
			'scope'      => 'members',
			'type'       => 'alphabetical',
			'per_page'   => 100,
			'meta_query' => $this->not_exists_meta_query(),
		) );

		$before = bp_core_get_incrementor( 'bb_nouveau_group_invites' );

		$prefix_key = function ( $key ) {
			return 'bbtest_' . $key;
		};
		add_filter( 'bp_get_user_meta_key', $prefix_key );

		bp_update_user_meta( $users[0], self::$restrict_key, 1 );

		remove_filter( 'bp_get_user_meta_key', $prefix_key );

		$this->assertNotSame(
			$before,
			bp_core_get_incrementor( 'bb_nouveau_group_invites' ),
			'A write under the filtered key must retire the cached list.'
		);

		// Both paths read the raw key, so they must agree on the result set
		// whatever the filter does to the stored key.
		$base_args = array(
			'group_id' => $group,
			'scope'    => 'members',
			'type'     => 'alphabetical',
			'per_page' => 100,
		);

		add_filter( 'bp_get_user_meta_key', $prefix_key );

		$fast   = $this->run_invite_query( array_merge( $base_args, array( 'meta_query' => $this->not_exists_meta_query() ) ) );
		$legacy = $this->run_invite_query( array_merge( $base_args, array(
			'meta_query' => array(
				'restrict_clause' => array(
					'key'     => self::$restrict_key,
					'compare' => 'NOT EXISTS',
				),
			),
		) ) );

		remove_filter( 'bp_get_user_meta_key', $prefix_key );

		$this->assertQueryPath( $fast, true );
		$this->assertQueryPath( $legacy, false );
		$this->assertSame(
			$this->get_user_ids( $legacy ),
			$this->get_user_ids( $fast ),
			'Fast path and WP_Meta_Query path must agree under a filtered meta key.'
		);
		$this->assertContains( $users[1], $this->get_user_ids( $fast ) );
	}

	/**
	 * A negative ceiling means "always use the WP_Meta_Query path".
	 *
	 * A zero ceiling only forces the fallback while somebody actually holds the meta:
	 * with nobody opted in the fast path returns early with no clause at all, so
	 * WP_Meta_Query never runs and third parties filtering `get_meta_sql` for this
	 * clause lose it with no way to opt back in.
	 */
	public function test_negative_ceiling_always_uses_the_meta_query_path() {
		$creator = self::factory()->user->create();
		$users   = self::factory()->user->create_many( 2 );
		$group   = self::factory()->group->create( array( 'creator_id' => $creator ) );

		// Deliberately nobody opted in - this is the case a zero ceiling cannot cover.
		$args = array(
			'group_id'   => $group,
			'scope'      => 'members',
			'type'       => 'alphabetical',
			'per_page'   => 100,
			'meta_query' => $this->not_exists_meta_query(),
		);

		add_filter( 'bb_nouveau_group_invites_excluded_ids_limit', '__return_zero' );
		$zero = $this->run_invite_query( $args );
		remove_filter( 'bb_nouveau_group_invites_excluded_ids_limit', '__return_zero' );

		$this->assertQueryPath( $zero, true, 'A zero ceiling cannot force the fallback when nobody holds the meta.' );

		$negative = function () {
			return -1;
		};
		add_filter( 'bb_nouveau_group_invites_excluded_ids_limit', $negative );
		$forced = $this->run_invite_query( $args );
		remove_filter( 'bb_nouveau_group_invites_excluded_ids_limit', $negative );

		$this->assertQueryPath( $forced, false, 'A negative ceiling must always take the WP_Meta_Query path.' );

		$found = $this->get_user_ids( $forced );
		$this->assertContains( $users[0], $found, 'The forced fallback must still return the same members.' );
		$this->assertContains( $users[1], $found );
	}

	/**
	 * A list larger than the cacheable size is still applied inline, just not stored.
	 *
	 * The cacheable size is separate from the inline ceiling because the two are sized
	 * against different limits (max_allowed_packet vs the object cache's item size), and
	 * by default it imposes no second ceiling.
	 */
	public function test_list_over_the_cacheable_size_is_used_but_not_stored() {
		$creator = self::factory()->user->create();
		$users   = self::factory()->user->create_many( 3 );
		$group   = self::factory()->group->create( array( 'creator_id' => $creator ) );

		update_user_meta( $users[0], self::$restrict_key, 1 );
		update_user_meta( $users[1], self::$restrict_key, 1 );

		$args = array(
			'group_id'   => $group,
			'scope'      => 'members',
			'type'       => 'alphabetical',
			'per_page'   => 100,
			'meta_query' => $this->not_exists_meta_query(),
		);

		// Two users hold the meta; allow only one to be cached.
		$one = function () {
			return 1;
		};
		add_filter( 'bb_nouveau_group_invites_cacheable_ids_limit', $one );
		$query = $this->run_invite_query( $args );
		remove_filter( 'bb_nouveau_group_invites_cacheable_ids_limit', $one );

		$found = $this->get_user_ids( $query );

		// The exclusion still applies inline.
		$this->assertQueryPath( $query, true, 'An uncacheable list must still use the inline path.' );
		$this->assertNotContains( $users[0], $found );
		$this->assertNotContains( $users[1], $found );
		$this->assertContains( $users[2], $found );

		// ...but nothing was stored.
		$incrementor = bp_core_get_incrementor( 'bb_nouveau_group_invites' );
		$this->assertFalse(
			wp_cache_get( 'bb_restrict_invites_ids_' . $incrementor, 'bb_nouveau_group_invites' ),
			'A list over the cacheable size must not be stored.'
		);

		// The default imposes no second ceiling: the same list caches normally.
		bp_core_reset_incrementor( 'bb_nouveau_group_invites' );
		$this->run_invite_query( $args );
		$incrementor = bp_core_get_incrementor( 'bb_nouveau_group_invites' );
		$this->assertIsArray(
			wp_cache_get( 'bb_restrict_invites_ids_' . $incrementor, 'bb_nouveau_group_invites' ),
			'By default the list must still be cached.'
		);
	}
}
