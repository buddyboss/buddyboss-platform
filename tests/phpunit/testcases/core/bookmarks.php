<?php
/**
 * @group core
 * @group bookmarks
 */
class BP_Tests_Core_Bookmarks extends BP_UnitTestCase {

	/**
	 * Register a 'post' bookmark type for the duration of a test.
	 */
	protected function register_post_type_bookmark() {
		add_filter(
			'bb_bookmark_register_types',
			function ( $types ) {
				$types['post'] = array(
					'label'          => array(
						'singular' => 'Post',
						'plural'   => 'Posts',
					),
					'items_callback' => '__return_empty_array',
				);

				return $types;
			}
		);
	}

	public function test_bookmark_table_exists() {
		global $wpdb;

		$table = bp_core_get_table_prefix() . 'bb_bookmark';
		$found = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );

		$this->assertSame( $table, $found, 'The bb_bookmark table was not installed.' );
	}

	public function test_bookmark_table_has_unique_key() {
		global $wpdb;

		$table = bp_core_get_table_prefix() . 'bb_bookmark';
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name from a trusted helper.
		$indexes = $wpdb->get_results( "SHOW INDEX FROM {$table} WHERE Key_name = 'bookmark_item'" );

		$this->assertCount( 4, $indexes, 'bookmark_item must be a 4-column unique index.' );
		$this->assertSame( '0', (string) $indexes[0]->Non_unique, 'bookmark_item must be UNIQUE.' );

		$columns = wp_list_pluck( $indexes, 'Column_name' );
		sort( $columns );
		$this->assertSame( array( 'blog_id', 'item_id', 'type', 'user_id' ), $columns );
	}

	public function test_store_add_and_get() {
		$u = $this->factory->user->create();
		$p = $this->factory->post->create( array( 'post_type' => 'post', 'post_status' => 'publish' ) );

		$obj                = new stdClass();
		$obj->id            = 0;
		$obj->blog_id       = get_current_blog_id();
		$obj->user_id       = $u;
		$obj->type          = 'post';
		$obj->item_id       = $p;
		$obj->status        = 1;
		$obj->date_recorded = current_time( 'mysql' );

		$id = BB_Bookmarks::add( $obj );
		$this->assertIsInt( $id );
		$this->assertGreaterThan( 0, $id );

		$result = BB_Bookmarks::get(
			array(
				'type'    => 'post',
				'user_id' => $u,
				'item_id' => $p,
				'fields'  => 'id',
				'cache'   => false,
			)
		);

		$this->assertSame( 1, (int) $result['total'] );
		$this->assertSame( array( $id ), $result['bookmarks'] );
	}

	public function test_store_delete() {
		$u = $this->factory->user->create();
		$p = $this->factory->post->create();

		$obj                = new stdClass();
		$obj->id            = 0;
		$obj->blog_id       = get_current_blog_id();
		$obj->user_id       = $u;
		$obj->type          = 'post';
		$obj->item_id       = $p;
		$obj->status        = 1;
		$obj->date_recorded = current_time( 'mysql' );

		$id = BB_Bookmarks::add( $obj );

		$row     = new stdClass();
		$row->id = $id;

		$this->assertTrue( BB_Bookmarks::delete( $row ) );

		$result = BB_Bookmarks::get(
			array(
				'type'    => 'post',
				'user_id' => $u,
				'item_id' => $p,
				'cache'   => false,
			)
		);

		$this->assertSame( 0, (int) $result['total'] );
	}

	public function test_update_status_purges_stale_per_row_cache() {
		global $wpdb;

		$u = $this->factory->user->create();
		$p = $this->factory->post->create( array( 'post_type' => 'post', 'post_status' => 'publish' ) );

		$obj                = new stdClass();
		$obj->id            = 0;
		$obj->blog_id       = get_current_blog_id();
		$obj->user_id       = $u;
		$obj->type          = 'post';
		$obj->item_id       = $p;
		$obj->status        = 1;
		$obj->date_recorded = current_time( 'mysql' );

		$id = BB_Bookmarks::add( $obj );
		$this->assertIsInt( $id );
		$this->assertGreaterThan( 0, $id );

		// No per-row cache entry yet.
		$this->assertFalse( wp_cache_get( $id, 'bb_bookmarks' ) );

		// Prime the per-row cache the same way get_single_bookmark() would
		// have, without calling it directly — it calls
		// bb_bookmark_register_types(), which does not exist yet.
		$table = BB_Bookmarks::get_bookmark_tbl();
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name from a trusted helper.
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT bm.* FROM {$table} bm WHERE bm.id = %d", $id ) );
		wp_cache_set( $id, $row, 'bb_bookmarks' );

		$cached = wp_cache_get( $id, 'bb_bookmarks' );
		$this->assertNotFalse( $cached );
		$this->assertSame( 1, (int) $cached->status );

		$this->assertTrue( BB_Bookmarks::update_status( 'post', $p, 0 ) );

		// The stale per-row cache entry must be purged by update_status().
		$this->assertFalse( wp_cache_get( $id, 'bb_bookmarks' ) );

		// The database write really happened.
		$result = BB_Bookmarks::get(
			array(
				'type'    => 'post',
				'user_id' => $u,
				'item_id' => $p,
				'status'  => 0,
				'fields'  => 'id',
				'cache'   => false,
			)
		);

		$this->assertSame( 1, (int) $result['total'] );
		$this->assertSame( array( $id ), $result['bookmarks'] );
	}

	public function test_type_registration() {
		$type = new BB_Tests_Fake_Bookmark_Type();
		$type::instance();

		$types = bb_bookmark_register_types();

		$this->assertArrayHasKey( 'fake', $types );
		$this->assertSame( 'Fake', $types['fake']['label']['singular'] );
		$this->assertTrue( is_callable( $types['fake']['items_callback'] ) );

		$single = bb_bookmark_register_types( 'fake' );
		$this->assertSame( 'Fakes', $single['label']['plural'] );
	}

	public function test_add_bookmark_is_idempotent() {
		$u = $this->factory->user->create();
		$p = $this->factory->post->create();

		$args = array( 'type' => 'post', 'user_id' => $u, 'item_id' => $p );

		$first  = bb_bookmark_add( $args );
		$second = bb_bookmark_add( $args );

		$this->assertIsInt( $first );
		$this->assertSame( $first, $second, 'A repeat add must return the same row, not create a second.' );
		$this->assertSame( 1, bb_bookmark_get_count( 'post', $p ) );
	}

	public function test_toggle_returns_real_state() {
		$this->register_post_type_bookmark();

		$u = $this->factory->user->create();
		$p = $this->factory->post->create();

		$this->assertTrue( bb_bookmark_toggle( 'post', $p, $u ), 'First toggle must return true (now bookmarked).' );
		$this->assertTrue( bb_bookmark_is_bookmarked( 'post', $p, $u ) );

		$this->assertFalse( bb_bookmark_toggle( 'post', $p, $u ), 'Second toggle must return false (now removed).' );
		$this->assertFalse( bb_bookmark_is_bookmarked( 'post', $p, $u ) );
	}

	public function test_toggle_errors_without_a_user() {
		$this->register_post_type_bookmark();

		$p = $this->factory->post->create();

		$result = bb_bookmark_toggle( 'post', $p, 0 );

		$this->assertWPError( $result );
		$this->assertSame( 'bb_bookmark_no_user', $result->get_error_code() );
	}

	public function test_toggle_rejects_unregistered_type() {
		$u = $this->factory->user->create();
		$p = $this->factory->post->create();

		$result = bb_bookmark_toggle( 'not_a_registered_type', $p, $u );

		$this->assertWPError( $result );
		$this->assertSame( 'bb_bookmark_invalid_type', $result->get_error_code() );
	}

	public function test_count_is_derived_not_stored() {
		$u1 = $this->factory->user->create();
		$u2 = $this->factory->user->create();
		$p  = $this->factory->post->create();

		bb_bookmark_add( array( 'type' => 'post', 'user_id' => $u1, 'item_id' => $p ) );
		bb_bookmark_add( array( 'type' => 'post', 'user_id' => $u2, 'item_id' => $p ) );

		$this->assertSame( 2, bb_bookmark_get_count( 'post', $p ) );

		$row = bb_bookmark_get_by_item( 'post', $p, $u1 );
		bb_bookmark_delete( $row->id );

		$this->assertSame( 1, bb_bookmark_get_count( 'post', $p ) );
	}

	public function test_delete_bookmarks_by_item() {
		$u1 = $this->factory->user->create();
		$u2 = $this->factory->user->create();
		$p  = $this->factory->post->create();

		bb_bookmark_add( array( 'type' => 'post', 'user_id' => $u1, 'item_id' => $p ) );
		bb_bookmark_add( array( 'type' => 'post', 'user_id' => $u2, 'item_id' => $p ) );

		$this->assertSame( 2, bb_bookmark_delete_by_item( 'post', $p ) );
		$this->assertSame( 0, bb_bookmark_get_count( 'post', $p ) );
	}

	/**
	 * Finding 1: bb_bookmark_prime_cache() must prime a cache that
	 * bb_bookmark_is_bookmarked() actually reads — for both bookmarked and
	 * unbookmarked items — and priming must eliminate the per-item query.
	 */
	public function test_prime_bookmark_cache_is_read_by_is_bookmarked() {
		global $wpdb;

		$this->register_post_type_bookmark();

		$u  = $this->factory->user->create();
		$p1 = $this->factory->post->create();
		$p2 = $this->factory->post->create();
		$p3 = $this->factory->post->create();

		bb_bookmark_add( array( 'type' => 'post', 'user_id' => $u, 'item_id' => $p1 ) );
		bb_bookmark_add( array( 'type' => 'post', 'user_id' => $u, 'item_id' => $p2 ) );
		// $p3 is deliberately left unbookmarked -- proves the negative cache entry is written too.

		bb_bookmark_prime_cache( 'post', array( $p1, $p2, $p3 ), $u );

		$num_queries_before = $wpdb->num_queries;

		$this->assertTrue( bb_bookmark_is_bookmarked( 'post', $p1, $u ), 'p1 was bookmarked and primed.' );
		$this->assertTrue( bb_bookmark_is_bookmarked( 'post', $p2, $u ), 'p2 was bookmarked and primed.' );
		$this->assertFalse( bb_bookmark_is_bookmarked( 'post', $p3, $u ), 'p3 was never bookmarked and was primed as such.' );

		$this->assertSame(
			$num_queries_before,
			$wpdb->num_queries,
			'Every primed id must be served from cache -- no new queries after priming.'
		);
	}

	/**
	 * Proves `before_delete_post` is wired to purge that post's bookmarks.
	 */
	public function test_bookmarks_purged_when_post_deleted() {
		$this->register_post_type_bookmark();

		$u = $this->factory->user->create();
		$p = $this->factory->post->create( array( 'post_type' => 'post' ) );

		bb_bookmark_add( array( 'type' => 'post', 'user_id' => $u, 'item_id' => $p ) );
		$this->assertSame( 1, bb_bookmark_get_count( 'post', $p ) );

		wp_delete_post( $p, true );

		$this->assertSame( 0, bb_bookmark_get_count( 'post', $p ) );
	}

	/**
	 * Proves the `deleted_user` hook itself is wired -- not merely that
	 * bb_bookmark_delete_user_items() works when called directly, which
	 * test_delete_user_bookmarks_fires_removed_action_per_row() already covers.
	 */
	public function test_bookmarks_purged_when_user_deleted() {
		$u = $this->factory->user->create();
		$p = $this->factory->post->create();

		bb_bookmark_add( array( 'type' => 'post', 'user_id' => $u, 'item_id' => $p ) );
		$this->assertSame( 1, bb_bookmark_get_count( 'post', $p ) );

		wp_delete_user( $u );

		$this->assertSame( 0, bb_bookmark_get_count( 'post', $p ) );
	}

	/**
	 * A user must be able to remove a bookmark on a post that has since been
	 * unpublished -- the removal path deliberately has no availability check.
	 */
	public function test_can_unbookmark_an_unpublished_post() {
		$this->register_post_type_bookmark();

		$u = $this->factory->user->create();
		$p = $this->factory->post->create( array( 'post_status' => 'publish' ) );

		$this->assertTrue( bb_bookmark_toggle( 'post', $p, $u ) );

		// The author unpublishes the post.
		wp_update_post( array( 'ID' => $p, 'post_status' => 'draft' ) );

		$this->assertFalse(
			bb_bookmark_toggle( 'post', $p, $u ),
			'Removing a bookmark must work even when the item is no longer published.'
		);
		$this->assertFalse( bb_bookmark_is_bookmarked( 'post', $p, $u ) );
	}

	/**
	 * Finding 4: bulk-deleting a user's bookmarks must fire
	 * bb_bookmark_removed once per row, same as the single-row delete path.
	 */
	public function test_delete_user_bookmarks_fires_removed_action_per_row() {
		$this->register_post_type_bookmark();

		$u  = $this->factory->user->create();
		$p1 = $this->factory->post->create();
		$p2 = $this->factory->post->create();

		bb_bookmark_add( array( 'type' => 'post', 'user_id' => $u, 'item_id' => $p1 ) );
		bb_bookmark_add( array( 'type' => 'post', 'user_id' => $u, 'item_id' => $p2 ) );

		$fired = array();
		add_action(
			'bb_bookmark_removed',
			function ( $type, $item_id, $user_id ) use ( &$fired ) {
				$fired[] = array( $type, $item_id, $user_id );
			},
			10,
			3
		);

		bb_bookmark_delete_user_items( $u );

		$this->assertCount( 2, $fired, 'bb_bookmark_removed must fire once per deleted row.' );
		$this->assertSame( 0, bb_bookmark_get_count( 'post', $p1 ) );
		$this->assertSame( 0, bb_bookmark_get_count( 'post', $p2 ) );
	}

	/**
	 * bb_bookmark_delete_blog_items() must be selective: it must delete
	 * only the rows for the target blog_id, and must leave every other
	 * blog's rows untouched. A WHERE clause bug here could delete the wrong
	 * site's bookmarks -- or none at all -- without anything catching it.
	 */
	public function test_delete_blog_bookmarks_only_deletes_target_blog() {
		global $wpdb;

		$u = $this->factory->user->create();
		$p = $this->factory->post->create();

		$current_blog_id = get_current_blog_id();
		$other_blog_id   = $current_blog_id + 1;

		$current_obj                = new stdClass();
		$current_obj->id            = 0;
		$current_obj->blog_id       = $current_blog_id;
		$current_obj->user_id       = $u;
		$current_obj->type          = 'post';
		$current_obj->item_id       = $p;
		$current_obj->status        = 1;
		$current_obj->date_recorded = current_time( 'mysql' );

		$current_id = BB_Bookmarks::add( $current_obj );
		$this->assertIsInt( $current_id );
		$this->assertGreaterThan( 0, $current_id );

		$other_obj                = new stdClass();
		$other_obj->id            = 0;
		$other_obj->blog_id       = $other_blog_id;
		$other_obj->user_id       = $u;
		$other_obj->type          = 'post';
		$other_obj->item_id       = $p;
		$other_obj->status        = 1;
		$other_obj->date_recorded = current_time( 'mysql' );

		$other_id = BB_Bookmarks::add( $other_obj );
		$this->assertIsInt( $other_id );
		$this->assertGreaterThan( 0, $other_id );

		$table = BB_Bookmarks::get_bookmark_tbl();

		// Query the table directly -- BB_Bookmarks::get() defaults blog_id to
		// the current site, which would silently hide the other blog's row.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name from a trusted helper.
		$current_row_before = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $current_id ) );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name from a trusted helper.
		$other_row_before = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $other_id ) );

		$this->assertNotEmpty( $current_row_before, 'The current blog bookmark row must exist before the delete.' );
		$this->assertNotEmpty( $other_row_before, 'The other blog bookmark row must exist before the delete.' );

		bb_bookmark_delete_blog_items( $other_blog_id );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name from a trusted helper.
		$other_row_after = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $other_id ) );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name from a trusted helper.
		$current_row_after = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $current_id ) );

		$this->assertNull( $other_row_after, 'The other blog bookmark row must be deleted.' );
		$this->assertNotEmpty( $current_row_after, 'The current blog bookmark row must be untouched.' );
	}

	/**
	 * BB_Bookmarks::get() must not hydrate-then-discard when a specific column
	 * is requested. Requesting `fields => 'item_id'` (the shape
	 * bb_blog_pro_get_user_bookmarks() uses, unpaginated, on every profile nav
	 * render) must select that column directly -- not run one
	 * `SELECT bm.*` + full items_callback hydration PER ROW via
	 * get_single_bookmark() only to throw all of it away with
	 * wp_list_pluck(). Query cost must not grow with the number of bookmarks.
	 */
	public function test_get_user_items_by_field_does_not_hydrate_per_row() {
		global $wpdb;

		$this->register_post_type_bookmark();

		$u = $this->factory->user->create();

		$expected_item_ids = array();
		for ( $i = 0; $i < 5; $i++ ) {
			$p = $this->factory->post->create( array( 'post_type' => 'post', 'post_status' => 'publish' ) );
			bb_bookmark_add( array( 'type' => 'post', 'user_id' => $u, 'item_id' => $p ) );
			$expected_item_ids[] = $p;
		}

		$num_queries_before = $wpdb->num_queries;

		$result = bb_bookmark_get_user_items(
			array(
				'type'     => 'post',
				'user_id'  => $u,
				'status'   => 1,
				'fields'   => 'item_id',
				'order_by' => 'date_recorded',
				'order'    => 'DESC',
				'count'    => false,
			)
		);

		$queries_used = $wpdb->num_queries - $num_queries_before;

		$got = $result['bookmarks'];
		sort( $expected_item_ids );
		sort( $got );

		$this->assertSame( $expected_item_ids, $got, 'fields=item_id must return exactly the bookmarked item IDs.' );

		// One query to select the item_id column. Must NOT scale with the
		// number of bookmarked rows -- the pre-fix code ran one extra
		// `SELECT bm.*` per row on top of the id lookup (6 queries for 5
		// bookmarks here), which this bound would catch.
		$this->assertLessThanOrEqual(
			2,
			$queries_used,
			'Query cost for fields=item_id must not scale with the number of bookmarked rows.'
		);
	}
}

/**
 * A minimal bookmark type used only by the tests above.
 */
class BB_Tests_Fake_Bookmark_Type extends BB_Bookmark_Type {

	public function setup() {
		$this->register_type(
			'fake',
			array(
				'label'          => array(
					'singular' => 'Fake',
					'plural'   => 'Fakes',
				),
				'items_callback' => array( $this, 'items_callback' ),
			)
		);
	}

	public function items_callback( $items ) {
		return $items;
	}
}
