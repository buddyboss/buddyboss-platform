<?php
/**
 * The activity comment tree must have the same shape whatever order the comment query
 * returns the rows in (PROD-10346), and the "View more comments" paging must follow
 * that order.
 *
 * Fixture tree, with one second between comments:
 *
 *     A ─┬─ c1 ─┬─ c2 ── c3
 *        │      └─ c5
 *        └─ c4
 *
 * @group activity
 * @group get_activity_comments
 * @group PROD-10346
 */
class BB_Tests_Activity_Comment_Tree extends BP_UnitTestCase {

	/**
	 * IDs of the fixture: 'a', 'c1' ... 'c5'.
	 *
	 * @var int[]
	 */
	protected $ids = array();

	/**
	 * Values of the BP globals changed for the single-activity context.
	 *
	 * @var array
	 */
	protected $bp_backup = array();

	/**
	 * Captured JSON of the AJAX handler.
	 *
	 * @var string
	 */
	protected $last_response = '';

	/**
	 * Saved error_reporting level.
	 *
	 * @var int
	 */
	protected $error_level = 0;

	public function set_up() {
		parent::set_up();

		$bp              = buddypress();
		$this->bp_backup = array(
			'current_component' => $bp->current_component,
			'current_action'    => $bp->current_action,
		);
		$this->error_level = error_reporting();
	}

	public function tear_down() {
		$bp                    = buddypress();
		$bp->current_component = $this->bp_backup['current_component'];
		$bp->current_action    = $this->bp_backup['current_action'];

		$_POST    = array();
		$_REQUEST = array();
		remove_filter( 'wp_doing_ajax', '__return_true' );
		remove_filter( 'wp_die_ajax_handler', array( $this, 'get_die_handler' ), 1 );
		error_reporting( $this->error_level );
		set_time_limit( 0 );

		parent::tear_down();
	}

	/**
	 * Create the fixture tree with strictly increasing dates.
	 *
	 * @return int[]
	 */
	protected function create_tree() {
		$u = self::factory()->user->create();
		$a = self::factory()->activity->create(
			array(
				'type'    => 'activity_update',
				'user_id' => $u,
			)
		);

		$c1 = bp_activity_new_comment( array( 'user_id' => $u, 'activity_id' => $a, 'content' => 'c1' ) );
		$c2 = bp_activity_new_comment( array( 'user_id' => $u, 'activity_id' => $a, 'parent_id' => $c1, 'content' => 'c2' ) );
		$c3 = bp_activity_new_comment( array( 'user_id' => $u, 'activity_id' => $a, 'parent_id' => $c2, 'content' => 'c3' ) );
		$c4 = bp_activity_new_comment( array( 'user_id' => $u, 'activity_id' => $a, 'content' => 'c4' ) );
		$c5 = bp_activity_new_comment( array( 'user_id' => $u, 'activity_id' => $a, 'parent_id' => $c1, 'content' => 'c5' ) );

		$this->ids = compact( 'u', 'a', 'c1', 'c2', 'c3', 'c4', 'c5' );

		// The threading depth setting can re-parent a deep reply on save; pin c3 under c2.
		$this->set_column( $c3, 'secondary_item_id', $c2 );

		$base = strtotime( '2026-01-01 10:00:00' );
		foreach ( array( 'c1', 'c2', 'c3', 'c4', 'c5' ) as $i => $key ) {
			$this->set_column( $this->ids[ $key ], 'date_recorded', gmdate( 'Y-m-d H:i:s', $base + $i + 1 ) );
		}

		return $this->ids;
	}

	/**
	 * Write one activity column directly and drop the caches that hold it.
	 */
	protected function set_column( $id, $column, $value ) {
		global $wpdb;

		$wpdb->update( buddypress()->activity->table_name, array( $column => $value ), array( 'id' => $id ) );
		wp_cache_delete( $id, 'bp_activity' );
		if ( ! empty( $this->ids['a'] ) ) {
			wp_cache_delete( $this->ids['a'], 'bp_activity_comments' );
		}
	}

	/**
	 * Fetch the full comment tree as the single-activity page does.
	 */
	protected function get_tree( $spam = 'ham_only' ) {
		$bp                    = buddypress();
		$bp->current_component = 'activity';
		$bp->current_action    = (string) $this->ids['a'];
		$this->assertTrue( bp_is_single_activity() );

		// The tree cache is keyed by activity only, so start each read from the database.
		wp_cache_delete( $this->ids['a'], 'bp_activity' );
		wp_cache_delete( $this->ids['a'], 'bp_activity_comments' );
		$activity = new BP_Activity_Activity( $this->ids['a'] );

		return BP_Activity_Activity::get_activity_comments( $this->ids['a'], $activity->mptt_left, $activity->mptt_right, $spam );
	}

	/**
	 * Reduce a tree to "id => [depth, children]", keeping sibling order.
	 */
	protected function shape( $nodes ) {
		$out = array();
		foreach ( (array) $nodes as $node ) {
			$out[ (int) $node->id ] = array(
				'depth'    => (int) $node->depth,
				'children' => $this->shape( $node->children ),
			);
		}

		return $out;
	}

	/**
	 * Same as shape(), with siblings sorted by id, to compare structure only.
	 */
	protected function sorted_shape( $nodes ) {
		$out = $this->shape( $nodes );
		$this->ksort_recursive( $out );

		return $out;
	}

	protected function ksort_recursive( &$shape ) {
		ksort( $shape );
		foreach ( $shape as &$node ) {
			$this->ksort_recursive( $node['children'] );
		}
	}

	/**
	 * Count every node of a tree.
	 */
	protected function count_nodes( $nodes ) {
		$count = 0;
		foreach ( (array) $nodes as $node ) {
			$count += 1 + $this->count_nodes( $node->children );
		}

		return $count;
	}

	/**
	 * Expected ASC shape of the fixture.
	 */
	protected function expected_asc_shape() {
		$i = $this->ids;

		return array(
			$i['c1'] => array(
				'depth'    => 1,
				'children' => array(
					$i['c2'] => array(
						'depth'    => 2,
						'children' => array(
							$i['c3'] => array( 'depth' => 3, 'children' => array() ),
						),
					),
					$i['c5'] => array( 'depth' => 2, 'children' => array() ),
				),
			),
			$i['c4'] => array( 'depth' => 1, 'children' => array() ),
		);
	}

	/**
	 * Default ASC order: the nesting, sibling order and depths of the fixture.
	 */
	public function test_asc_builds_expected_nesting() {
		$this->create_tree();

		$this->assertSame( $this->expected_asc_shape(), $this->shape( $this->get_tree() ) );
	}

	/**
	 * A DESC misc filter (the ticket's repro) gives the same tree, with siblings newest first.
	 */
	public function test_desc_order_gives_same_shape_in_fetch_order() {
		$this->create_tree();
		$i = $this->ids;

		$filter = function () {
			return 'ORDER BY a.date_recorded DESC';
		};
		add_filter( 'bp_activity_comments_get_misc_sql', $filter );
		$tree = $this->get_tree();
		remove_filter( 'bp_activity_comments_get_misc_sql', $filter );

		$this->assertSame( $this->sorted_shape_of( $this->expected_asc_shape() ), $this->sorted_shape( $tree ) );

		// Fetch order is kept at every level.
		$this->assertSame( array( $i['c4'], $i['c1'] ), array_keys( $tree ) );
		$this->assertSame( array( $i['c5'], $i['c2'] ), array_keys( $tree[ $i['c1'] ]->children ) );
	}

	/**
	 * Any other order gives the same structure.
	 */
	public function test_shuffled_order_gives_same_shape() {
		$this->create_tree();
		$expected = $this->sorted_shape_of( $this->expected_asc_shape() );

		$orders = array( 'ORDER BY a.id DESC', 'ORDER BY RAND()', 'ORDER BY a.content ASC' );
		foreach ( $orders as $order ) {
			$filter = function () use ( $order ) {
				return $order;
			};
			add_filter( 'bp_activity_comments_get_misc_sql', $filter );
			$this->assertSame( $expected, $this->sorted_shape( $this->get_tree() ), $order );
			remove_filter( 'bp_activity_comments_get_misc_sql', $filter );
		}
	}

	/**
	 * A reply recorded in the same second as its parent nests under it, even when the
	 * reply is returned first.
	 */
	public function test_same_second_parent_and_reply_nest() {
		$this->create_tree();
		$i = $this->ids;

		$same = gmdate( 'Y-m-d H:i:s', strtotime( '2026-01-01 10:00:01' ) );
		$this->set_column( $i['c2'], 'date_recorded', $same );
		$this->set_column( $i['c3'], 'date_recorded', $same );

		// Default order: the id tie-breaker returns the parent first.
		$this->assertSame( $this->expected_asc_shape(), $this->shape( $this->get_tree() ) );

		// Ties broken the other way: the reply comes before its parent.
		$filter = function () {
			return 'ORDER BY a.date_recorded ASC, a.id DESC';
		};
		add_filter( 'bp_activity_comments_get_misc_sql', $filter );
		$tree = $this->get_tree();
		remove_filter( 'bp_activity_comments_get_misc_sql', $filter );

		$this->assertSame( $this->sorted_shape_of( $this->expected_asc_shape() ), $this->sorted_shape( $tree ) );
	}

	/**
	 * The default ORDER BY has an id tie-breaker, and a misc filter still replaces the
	 * whole clause.
	 */
	public function test_order_by_tie_breaker_and_filter_replaces_clause() {
		$this->create_tree();

		$queries = array();
		$capture = function ( $query ) use ( &$queries ) {
			if ( false !== strpos( $query, "a.type = 'activity_comment'" ) ) {
				$queries[] = $query;
			}

			return $query;
		};
		add_filter( 'query', $capture );

		$this->get_tree();
		$this->assertNotEmpty( $queries );
		$this->assertStringContainsString( 'ORDER BY a.date_recorded ASC, a.id ASC', end( $queries ) );

		$queries = array();
		$filter  = function ( $misc ) {
			return 'ORDER BY a.id DESC';
		};
		add_filter( 'bp_activity_comments_get_misc_sql', $filter );
		$this->get_tree();
		remove_filter( 'bp_activity_comments_get_misc_sql', $filter );
		remove_filter( 'query', $capture );

		$this->assertNotEmpty( $queries );
		$this->assertStringContainsString( 'ORDER BY a.id DESC', end( $queries ) );
		$this->assertStringNotContainsString( 'date_recorded ASC', end( $queries ) );
	}

	/**
	 * Replies to a spam or missing comment go to the root and keep their own subtree.
	 */
	public function test_orphans_go_to_root() {
		global $wpdb;

		$this->create_tree();
		$i = $this->ids;

		$expected = array(
			$i['c2'] => array(
				'depth'    => 2,
				'children' => array(
					$i['c3'] => array( 'depth' => 3, 'children' => array() ),
				),
			),
			$i['c4'] => array( 'depth' => 1, 'children' => array() ),
			$i['c5'] => array( 'depth' => 2, 'children' => array() ),
		);

		// Spam parent.
		$this->set_column( $i['c1'], 'is_spam', 1 );
		$this->assertSame( $expected, $this->shape( $this->get_tree() ) );

		$filter = function () {
			return 'ORDER BY a.date_recorded DESC';
		};
		add_filter( 'bp_activity_comments_get_misc_sql', $filter );
		$this->assertSame( $expected, $this->sorted_shape( $this->get_tree() ) );
		remove_filter( 'bp_activity_comments_get_misc_sql', $filter );

		// With spam included, c1 is back with its replies.
		$this->assertSame( $this->expected_asc_shape(), $this->shape( $this->get_tree( 'all' ) ) );

		// Missing parent: the row is gone, its replies stay.
		$this->set_column( $i['c1'], 'is_spam', 0 );
		$wpdb->delete( buddypress()->activity->table_name, array( 'id' => $i['c1'] ) );
		wp_cache_delete( $i['c1'], 'bp_activity' );
		wp_cache_delete( $i['a'], 'bp_activity_comments' );

		$tree = $this->get_tree();
		$this->assertSame( array( $i['c2'], $i['c4'], $i['c5'] ), array_keys( $tree ) );
		$this->assertSame( array( $i['c3'] ), array_keys( $tree[ $i['c2'] ]->children ) );
	}

	/**
	 * A comment that is its own parent ends up at the root; the request does not hang
	 * and no comment is lost.
	 */
	public function test_self_parent_comment_does_not_hang() {
		$this->create_tree();
		$i = $this->ids;

		$this->set_column( $i['c4'], 'secondary_item_id', $i['c4'] );

		// Release looped forever here; fail fast instead of hanging the suite.
		set_time_limit( 20 );
		$tree = $this->get_tree();
		set_time_limit( 0 );

		$this->assertSame( $this->expected_asc_shape(), $this->shape( $tree ) );
		$this->assertSame( 5, $this->count_nodes( $tree ) );
	}

	/**
	 * Two comments that are each other's parent both go to the root, and their other
	 * replies still nest. Nothing hangs and nothing is lost.
	 */
	public function test_two_comment_cycle_does_not_hang() {
		$this->create_tree();
		$i = $this->ids;

		// c2 -> c3 -> c2.
		$this->set_column( $i['c2'], 'secondary_item_id', $i['c3'] );

		set_time_limit( 20 );
		$tree = $this->get_tree();
		set_time_limit( 0 );

		$this->assertSame( 5, $this->count_nodes( $tree ) );
		$this->assertSame(
			array(
				$i['c1'] => array(
					'depth'    => 1,
					'children' => array(
						$i['c5'] => array( 'depth' => 2, 'children' => array() ),
					),
				),
				$i['c2'] => array( 'depth' => 1, 'children' => array() ),
				$i['c3'] => array( 'depth' => 1, 'children' => array() ),
				$i['c4'] => array( 'depth' => 1, 'children' => array() ),
			),
			$this->shape( $tree )
		);
	}

	/**
	 * A loop that runs through a comment outside the result (here a spam one) does not
	 * hang the depth calculation either.
	 */
	public function test_cycle_through_excluded_parent_does_not_hang() {
		$this->create_tree();
		$i = $this->ids;

		// c2 -> c3 -> c2, and c2 is spam so only c3 is fetched.
		$this->set_column( $i['c2'], 'secondary_item_id', $i['c3'] );
		$this->set_column( $i['c2'], 'is_spam', 1 );

		set_time_limit( 20 );
		$tree = $this->get_tree();
		set_time_limit( 0 );

		$this->assertArrayHasKey( $i['c3'], $tree );
		$this->assertSame( 4, $this->count_nodes( $tree ) );
	}

	/**
	 * Trees cached by the old builder (a bare array under the activity id) are rebuilt,
	 * and the new entry carries the builder version.
	 */
	public function test_tree_cached_before_update_is_rebuilt() {
		$this->create_tree();
		$i = $this->ids;

		// What the old builder cached under a DESC filter: every comment at the root.
		$flat = array();
		foreach ( array( 'c5', 'c4', 'c3', 'c2', 'c1' ) as $key ) {
			$node           = new stdClass();
			$node->id       = $i[ $key ];
			$node->children = array();
			$node->depth    = 1;
			$flat[ $i[ $key ] ] = $node;
		}

		$bp                    = buddypress();
		$bp->current_component = 'activity';
		$bp->current_action    = (string) $i['a'];
		$activity              = new BP_Activity_Activity( $i['a'] );

		wp_cache_set( $i['a'], $flat, 'bp_activity_comments' );
		$tree = BP_Activity_Activity::get_activity_comments( $i['a'], $activity->mptt_left, $activity->mptt_right );
		$this->assertSame( $this->expected_asc_shape(), $this->shape( $tree ) );

		$cached = wp_cache_get( $i['a'], 'bp_activity_comments' );
		$this->assertIsArray( $cached );
		$this->assertSame( BP_Activity_Activity::COMMENT_TREE_CACHE_VERSION, $cached['tree_version'] );
		$this->assertSame( $this->expected_asc_shape(), $this->shape( $cached['tree'] ) );

		// The next request is served from the new entry.
		$queries = array();
		$capture = function ( $query ) use ( &$queries ) {
			if ( false !== strpos( $query, "a.type = 'activity_comment'" ) ) {
				$queries[] = $query;
			}

			return $query;
		};
		add_filter( 'query', $capture );
		$again = BP_Activity_Activity::get_activity_comments( $i['a'], $activity->mptt_left, $activity->mptt_right );
		remove_filter( 'query', $capture );

		$this->assertSame( array(), $queries, 'The versioned entry is a cache hit.' );
		$this->assertSame( $this->expected_asc_shape(), $this->shape( $again ) );

		// An old "no comments" marker is also rebuilt, not trusted.
		wp_cache_set( $i['a'], 'none', 'bp_activity_comments' );
		$tree = BP_Activity_Activity::get_activity_comments( $i['a'], $activity->mptt_left, $activity->mptt_right );
		$this->assertSame( 5, $this->count_nodes( $tree ) );
	}

	/**
	 * The paging direction follows the comment query unless the paging filter is set.
	 */
	public function test_comments_order_follows_misc_sql() {
		$this->assertSame( 'ASC', bb_get_activity_comments_order() );

		$cases = array(
			'ORDER BY a.date_recorded DESC'           => 'DESC',
			'ORDER BY a.date_recorded desc, a.id ASC' => 'DESC',
			'ORDER BY a.date_recorded ASC, a.id DESC' => 'ASC',
			'ORDER BY a.id'                           => 'ASC',
			'ORDER BY RAND()'                         => 'ASC',
		);
		foreach ( $cases as $sql => $expected ) {
			$filter = function () use ( $sql ) {
				return $sql;
			};
			add_filter( 'bp_activity_comments_get_misc_sql', $filter );
			$this->assertSame( $expected, bb_get_activity_comments_order(), $sql );
			remove_filter( 'bp_activity_comments_get_misc_sql', $filter );
		}
	}

	/**
	 * Data for the paging test: nouveau and ReadyLaunch handlers, with or without an
	 * explicit paging filter.
	 */
	public function data_load_more_handlers() {
		return array(
			'nouveau, misc filter only'            => array( 'nouveau', false ),
			'ReadyLaunch, misc filter only'        => array( 'readylaunch', false ),
			'nouveau, both filters set to DESC'    => array( 'nouveau', true ),
			'ReadyLaunch, both filters set to DESC' => array( 'readylaunch', true ),
		);
	}

	/**
	 * With only the misc filter set to DESC, "View more comments" pages through every
	 * top-level comment once, newest first.
	 *
	 * @dataProvider data_load_more_handlers
	 */
	public function test_load_more_paging_under_desc_returns_each_comment_once( $handler, $explicit_order ) {
		$u = self::factory()->user->create();
		$a = self::factory()->activity->create(
			array(
				'type'    => 'activity_update',
				'user_id' => $u,
			)
		);

		$this->ids = array( 'a' => $a );
		$comments  = array();
		$base      = strtotime( '2026-01-01 10:00:00' );
		for ( $n = 1; $n <= 7; $n++ ) {
			$comments[ $n ] = bp_activity_new_comment( array( 'user_id' => $u, 'activity_id' => $a, 'content' => 'top ' . $n ) );
			$this->set_column( $comments[ $n ], 'date_recorded', gmdate( 'Y-m-d H:i:s', $base + $n ) );
		}
		$newest_first = array_reverse( array_values( $comments ) );

		$misc = function () {
			return 'ORDER BY a.date_recorded DESC';
		};
		$page = function () {
			return 3;
		};
		$seen_default = array();
		$order        = function ( $value ) use ( &$seen_default ) {
			$seen_default[] = $value;

			return 'DESC';
		};
		add_filter( 'bp_activity_comments_get_misc_sql', $misc );
		add_filter( 'bb_get_activity_comment_loading', $page );
		if ( $explicit_order ) {
			add_filter( 'bb_activity_recurse_comments_order_by', $order );
		}

		$loaded = array();
		$cursor = array();
		for ( $request = 0; $request < 6; $request++ ) {
			$items = $this->load_more( $handler, $u, $a, $cursor );
			if ( empty( $items ) ) {
				break;
			}
			foreach ( $items as $item ) {
				$loaded[] = $item['id'];
			}
			$last   = end( $items );
			$cursor = array(
				'last_comment_id'        => $last['id'],
				'last_comment_timestamp' => $last['timestamp'],
			);
			if ( count( $items ) < 3 ) {
				break;
			}
		}

		remove_filter( 'bp_activity_comments_get_misc_sql', $misc );
		remove_filter( 'bb_get_activity_comment_loading', $page );
		remove_filter( 'bb_activity_recurse_comments_order_by', $order );

		$this->assertSame( $newest_first, $loaded, 'Every comment once, newest first.' );
		if ( $explicit_order ) {
			$this->assertContains( 'DESC', $seen_default, 'The paging filter gets the query direction as its default.' );
		}
	}

	/**
	 * Call a load-more handler and return the top-level comments it rendered.
	 *
	 * @return array[] Each with 'id' and 'timestamp', in render order.
	 */
	protected function load_more( $handler, $user_id, $activity_id, $cursor ) {
		self::set_current_user( $user_id );

		add_filter( 'wp_doing_ajax', '__return_true' );
		add_filter( 'wp_die_ajax_handler', array( $this, 'get_die_handler' ), 1 );
		error_reporting( $this->error_level & ~E_WARNING );

		$_POST    = array_merge(
			array(
				'nonce'             => wp_create_nonce( 'bp_nouveau_activity' ),
				'activity_id'       => $activity_id,
				'parent_comment_id' => $activity_id,
			),
			$cursor
		);
		$_REQUEST = $_POST;

		$method                    = isset( $_SERVER['REQUEST_METHOD'] ) ? $_SERVER['REQUEST_METHOD'] : null;
		$_SERVER['REQUEST_METHOD'] = 'POST';

		$this->last_response = '';
		ob_start();
		try {
			if ( 'readylaunch' === $handler ) {
				BB_Activity_Readylaunch::instance()->bb_rl_activity_loadmore_comments();
			} else {
				if ( ! function_exists( 'bb_nouveau_ajax_activity_load_more_comments' ) ) {
					require_once buddypress()->plugin_dir . 'bp-templates/bp-nouveau/includes/activity/ajax.php';
				}
				bb_nouveau_ajax_activity_load_more_comments();
			}
		} catch ( WPAjaxDieContinueException $e ) {
			unset( $e );
		} catch ( WPAjaxDieStopException $e ) {
			unset( $e );
		}
		if ( ob_get_level() > 0 && '' === $this->last_response ) {
			$this->last_response = ob_get_clean();
		}

		$_SERVER['REQUEST_METHOD'] = $method;
		remove_filter( 'wp_doing_ajax', '__return_true' );
		remove_filter( 'wp_die_ajax_handler', array( $this, 'get_die_handler' ), 1 );
		error_reporting( $this->error_level );

		$response = json_decode( $this->last_response, true );
		if ( empty( $response['success'] ) || empty( $response['data']['comments'] ) ) {
			return array();
		}

		preg_match_all( '/data-bp-activity-comment-id="(\d+)" data-bp-timestamp="([^"]*)"/', $response['data']['comments'], $matches, PREG_SET_ORDER );

		$items = array();
		foreach ( $matches as $match ) {
			$items[] = array(
				'id'        => (int) $match[1],
				'timestamp' => $match[2],
			);
		}

		return $items;
	}

	public function get_die_handler() {
		return array( $this, 'die_handler' );
	}

	public function die_handler( $message ) {
		$this->last_response .= ob_get_clean();
		throw new WPAjaxDieContinueException( is_scalar( $message ) ? (string) $message : '' );
	}

	/**
	 * sorted_shape() for an already reduced shape.
	 */
	protected function sorted_shape_of( $shape ) {
		$this->ksort_recursive( $shape );

		return $shape;
	}
}
