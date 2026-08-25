<?php
/**
 * Tests for the `_fields` support of the activity REST controller.
 *
 * The controller resolves the client's `_fields` selection before it builds
 * the response, so an unrequested field costs no server-side work. These
 * tests pin both halves of that contract: the payload must not change, and
 * the work must.
 *
 * @group activity
 * @group rest
 */
class BP_Tests_Activity_REST_Fields extends BP_UnitTestCase {

	/**
	 * REST server.
	 *
	 * @var WP_REST_Server
	 */
	protected $server;

	/**
	 * Activity collection route.
	 *
	 * @var string
	 */
	protected $endpoint_url;

	/**
	 * Activity endpoint controller.
	 *
	 * @var BP_REST_Activity_Endpoint
	 */
	protected $endpoint;

	/**
	 * ID of the user the fixtures belong to.
	 *
	 * @var int
	 */
	protected $user_id;

	/**
	 * ID of the activity used by the single-item assertions.
	 *
	 * @var int
	 */
	protected $activity_id;

	/**
	 * Set up the REST server and the activity fixtures.
	 */
	public function setUp(): void {
		parent::setUp();

		/*
		 * Booting the REST API brings up every BuddyBoss controller, and some
		 * of them read the email taxonomy while registering their routes.
		 * WordPress's installer wipes those terms, so put them back first.
		 */
		if ( ! get_term_by( 'name', 'invites-member-invite', bp_get_email_tax_type() ) ) {
			require_once buddypress()->plugin_dir . 'bp-core/admin/bp-core-admin-schema.php';
			bp_core_install_emails();
		}

		/*
		 * The test case restores the hook snapshot after every test, which
		 * unregisters the callbacks `rest_api_init` added -- including
		 * `rest_filter_response_fields()`. Rebuild the server per test so the
		 * dispatch pipeline is the one a real request goes through.
		 */
		global $wp_rest_server;
		$wp_rest_server = new WP_REST_Server();
		do_action( 'rest_api_init', $wp_rest_server );

		$this->server       = $wp_rest_server;
		$this->endpoint     = new BP_REST_Activity_Endpoint();
		$this->endpoint_url = '/' . bp_rest_namespace() . '/' . bp_rest_version() . '/activity';

		$this->user_id = self::factory()->user->create( array( 'role' => 'subscriber' ) );

		$this->activity_id = self::factory()->activity->create(
			array(
				'user_id'   => $this->user_id,
				'component' => 'activity',
				'type'      => 'activity_update',
				'content'   => 'Field selection fixture.',
			)
		);

		wp_set_current_user( $this->user_id );
	}

	/**
	 * Drop the REST server so the next test builds a fresh one.
	 */
	public function tearDown(): void {
		global $wp_rest_server;
		$wp_rest_server = null;

		parent::tearDown();
	}

	/**
	 * Fields whose construction is guarded by the `_fields` selection.
	 *
	 * `comments` is deliberately absent: it is gated on `display_comments`
	 * and is covered by its own test.
	 *
	 * @return array
	 */
	public static function guarded_field_provider() {
		return array(
			array( 'name' ),
			array( 'mention_name' ),
			array( 'user_link' ),
			array( 'link' ),
			array( 'content' ),
			array( 'favorited' ),
			array( 'favorite_count' ),
			array( 'activity_data' ),
			array( 'feature_media' ),
			array( 'preview_data' ),
			array( 'link_embed_url' ),
			array( 'is_pinned' ),
			array( 'can_pin' ),
			array( 'reacted_names' ),
			array( 'reacted_counts' ),
			array( 'reacted_id' ),
			array( 'can_close_comment' ),
			array( 'comment_count' ),
			array( 'user_avatar' ),
			array( 'can_toggle_notification' ),
			array( 'is_receive_notification' ),
			array( 'bb_activity_post_feature_image' ),
			array( 'can_edit' ),
			array( 'can_delete' ),
			array( 'can_comment' ),
			array( 'can_favorite' ),
			array( 'content_stripped' ),
			array( 'title' ),
			array( 'is_edited' ),
		);
	}

	/**
	 * Response fields that shipped for years without a schema entry.
	 *
	 * @return array
	 */
	public static function undeclared_field_provider() {
		return array(
			array( 'bb_activity_post_feature_image' ),
			array( 'comment_closed_notice' ),
			array( 'comment_depth' ),
		);
	}

	/**
	 * Dispatch a request the way `WP_REST_Server::serve_request()` does.
	 *
	 * `rest_do_request()` skips `rest_post_dispatch`, which is where
	 * WordPress trims the response down to `_fields`. Without it these
	 * tests would not exercise the trimming at all.
	 *
	 * @param WP_REST_Request $request Request to dispatch.
	 *
	 * @return WP_REST_Response
	 */
	protected function dispatch( $request ) {
		$response = $this->server->dispatch( $request );

		return apply_filters( 'rest_post_dispatch', rest_ensure_response( $response ), $this->server, $request );
	}

	/**
	 * Fetch the activity collection and return the first item.
	 *
	 * @param array $params Request parameters.
	 *
	 * @return array
	 */
	protected function get_first_item( $params = array() ) {
		$request = new WP_REST_Request( 'GET', $this->endpoint_url );
		$request->set_param( 'context', 'view' );

		foreach ( $params as $key => $value ) {
			$request->set_param( $key, $value );
		}

		$data = $this->dispatch( $request )->get_data();

		$this->assertNotEmpty( $data, 'The activity collection came back empty.' );

		return $data[0];
	}

	/**
	 * Count the queries a single collection request costs, from a cold cache.
	 *
	 * @param array $params Request parameters.
	 *
	 * @return int
	 */
	protected function count_queries( $params = array() ) {
		global $wpdb;

		wp_cache_flush();

		$before = $wpdb->num_queries;
		$this->get_first_item( $params );

		return $wpdb->num_queries - $before;
	}

	/**
	 * Count the queries a request sends to the reaction tables.
	 *
	 * @param array $params Request parameters.
	 *
	 * @return int
	 */
	protected function count_reaction_queries( $params = array() ) {
		$count = 0;

		$counter = function ( $query ) use ( &$count ) {
			if ( false !== strpos( $query, 'bb_user_reactions' ) || false !== strpos( $query, 'bb_reactions_data' ) ) {
				++$count;
			}

			return $query;
		};

		wp_cache_flush();

		add_filter( 'query', $counter );

		try {
			$this->get_first_item( $params );
		} finally {
			remove_filter( 'query', $counter );
		}

		return $count;
	}

	/**
	 * A request that sends no `_fields` must behave exactly as it did before
	 * the controller became field-aware: every field is still built.
	 */
	public function test_no_field_selection_returns_every_field() {
		$item       = $this->get_first_item();
		$properties = $this->endpoint->get_item_schema();
		$properties = $properties['properties'];

		foreach ( self::guarded_field_provider() as $args ) {
			$field = $args[0];

			// Fields whose schema entry is registered conditionally are only
			// expected when the feature behind them is active on this install.
			if ( ! isset( $properties[ $field ] ) ) {
				continue;
			}

			$this->assertArrayHasKey(
				$field,
				$item,
				sprintf( 'Field "%s" disappeared from an unfiltered response.', $field )
			);
		}
	}

	/**
	 * A narrow selection returns exactly the requested keys.
	 */
	public function test_narrow_field_selection_returns_only_the_requested_keys() {
		$item = $this->get_first_item( array( '_fields' => 'id,user_id,date' ) );

		$actual = array_keys( $item );
		sort( $actual );

		$this->assertSame( array( 'date', 'id', 'user_id' ), $actual );
	}

	/**
	 * A nested selection still builds the parent field.
	 *
	 * @dataProvider guarded_field_provider
	 *
	 * @param string $field Field name.
	 */
	public function test_guarded_field_is_returned_when_explicitly_selected( $field ) {
		$properties = $this->endpoint->get_item_schema();
		$properties = $properties['properties'];

		if ( ! isset( $properties[ $field ] ) ) {
			$this->markTestSkipped( sprintf( 'Field "%s" is not registered on this install.', $field ) );
		}

		$item = $this->get_first_item( array( '_fields' => 'id,' . $field ) );

		$this->assertArrayHasKey( $field, $item );
	}

	/**
	 * `_fields=content.rendered` must still build `content`.
	 */
	public function test_nested_field_selection_still_builds_the_parent() {
		$item = $this->get_first_item( array( '_fields' => 'id,content.rendered' ) );

		$this->assertArrayHasKey( 'content', $item );
		$this->assertArrayHasKey( 'rendered', $item['content'] );
		$this->assertNotEmpty( $item['content']['rendered'] );

		// WordPress trims the sibling away; the controller must not have to.
		$this->assertArrayNotHasKey( 'raw', $item['content'] );
	}

	/**
	 * A selection combined with `_embed` still embeds.
	 */
	public function test_field_selection_with_embed_still_embeds() {
		$request = new WP_REST_Request( 'GET', $this->endpoint_url );
		$request->set_param( 'context', 'view' );
		$request->set_param( '_embed', true );
		$request->set_param( '_fields', 'id,user_id,_links,_embedded' );

		$response = $this->dispatch( $request );
		$data     = $this->server->response_to_data( $response, true );

		$this->assertNotEmpty( $data );
		$this->assertArrayHasKey( '_links', $data[0] );
		$this->assertArrayHasKey( 'user', $data[0]['_links'] );
		$this->assertArrayHasKey( '_embedded', $data[0] );
		$this->assertArrayHasKey( 'user', $data[0]['_embedded'] );
	}

	/**
	 * `comments` is gated on `display_comments`, not on `_fields`, and must
	 * keep working when no selection is sent.
	 */
	public function test_threaded_comments_are_returned_without_a_field_selection() {
		$this->create_comment();

		$item = $this->get_first_item(
			array(
				'display_comments' => 'threaded',
				'include'          => $this->activity_id,
			)
		);

		$this->assertArrayHasKey( 'comments', $item );
		$this->assertNotEmpty( $item['comments'] );
	}

	/**
	 * Selecting `comments` must still return whole comment objects: WordPress
	 * does not trim inside a numerically indexed list, so the controller must
	 * not narrow the nested items either.
	 */
	public function test_selecting_comments_returns_whole_comment_objects() {
		$this->create_comment();

		$item = $this->get_first_item(
			array(
				'display_comments' => 'threaded',
				'include'          => $this->activity_id,
				'_fields'          => 'id,comments',
			)
		);

		$this->assertArrayHasKey( 'comments', $item );
		$this->assertNotEmpty( $item['comments'] );

		$comment = $item['comments'][0];

		$this->assertArrayHasKey( 'content', $comment );
		$this->assertArrayHasKey( 'user_id', $comment );
		$this->assertArrayHasKey( 'date', $comment );

		// Fields added with `bp_rest_register_field()` belong to a nested
		// comment too: `_fields` addresses the parent, never its comments.
		$properties = $this->endpoint->get_item_schema();

		if ( isset( $properties['properties']['can_report'] ) ) {
			$this->assertArrayHasKey( 'can_report', $comment );
		}
	}

	/**
	 * An unselected `content` must not be rendered.
	 */
	public function test_content_is_not_rendered_when_it_is_not_selected() {
		$renders = 0;
		$counter = function ( $content ) use ( &$renders ) {
			$renders++;

			return $content;
		};

		add_filter( 'bp_get_activity_content_body', $counter );

		try {
			$this->get_first_item( array( '_fields' => 'id,user_id' ) );
		} finally {
			remove_filter( 'bp_get_activity_content_body', $counter );
		}

		$this->assertSame( 0, $renders );
	}

	/**
	 * ...but a selected `content` still is.
	 */
	public function test_content_is_rendered_when_it_is_selected() {
		$renders = 0;
		$counter = function ( $content ) use ( &$renders ) {
			$renders++;

			return $content;
		};

		add_filter( 'bp_get_activity_content_body', $counter );

		try {
			$this->get_first_item( array( '_fields' => 'id,content' ) );
		} finally {
			remove_filter( 'bp_get_activity_content_body', $counter );
		}

		$this->assertGreaterThan( 0, $renders );
	}

	/**
	 * `link_embed_url` is stored in the activity metadata whenever the activity
	 * has one, so selecting it alone must not drag in the embed resolution --
	 * nor the content render that resolution reads.
	 */
	public function test_link_embed_url_alone_skips_the_embed_resolution() {
		bp_activity_update_meta( $this->activity_id, '_link_embed', 'https://example.org/embedded' );
		wp_cache_delete( $this->activity_id, 'activity_meta' );

		$renders = 0;
		$counter = function ( $content ) use ( &$renders ) {
			$renders++;

			return $content;
		};

		add_filter( 'bp_get_activity_content_body', $counter );

		try {
			$item = $this->get_first_item(
				array(
					'include' => $this->activity_id,
					'_fields' => 'id,link_embed_url',
				)
			);
		} finally {
			remove_filter( 'bp_get_activity_content_body', $counter );
		}

		$this->assertSame( 'https://example.org/embedded', $item['link_embed_url'] );
		$this->assertSame( 0, $renders );
	}

	/**
	 * `can_close_comment` is derived from whether comments are closed. That
	 * value has to be resolved independently of the `is_comment_closed`
	 * selection, or one guard would be reading what another produced.
	 */
	public function test_close_comment_permission_is_independent_of_is_comment_closed() {
		$full   = $this->get_first_item( array( 'include' => $this->activity_id ) );
		$narrow = $this->get_first_item(
			array(
				'include' => $this->activity_id,
				'_fields' => 'id,can_close_comment',
			)
		);

		if ( ! isset( $full['can_close_comment'] ) ) {
			$this->markTestSkipped( 'Closing activity comments is not available on this install.' );
		}

		$this->assertArrayNotHasKey( 'is_comment_closed', $narrow );
		$this->assertSame( $full['can_close_comment'], $narrow['can_close_comment'] );
	}

	/**
	 * An unselected `activity_data` must not build the edit payload.
	 */
	public function test_activity_data_is_not_built_when_it_is_not_selected() {
		$builds  = 0;
		$counter = function ( $data ) use ( &$builds ) {
			$builds++;

			return $data;
		};

		add_filter( 'bp_activity_get_edit_data', $counter );

		try {
			$this->get_first_item( array( '_fields' => 'id,user_id' ) );
		} finally {
			remove_filter( 'bp_activity_get_edit_data', $counter );
		}

		$this->assertSame( 0, $builds );
	}

	/**
	 * Skip a test that needs the reactions feature.
	 */
	protected function require_reactions() {
		if ( ! function_exists( 'bb_load_reaction' ) || ! bb_load_reaction() ) {
			$this->markTestSkipped( 'The reactions feature is not available on this install.' );
		}
	}

	/**
	 * The reaction lookups behind `reacted_*` and `favorite_count` are three
	 * of the most expensive things the feed does per row. An unselected
	 * reaction field must not reach the reaction tables at all.
	 */
	public function test_reaction_tables_are_untouched_when_no_reaction_field_is_selected() {
		$this->require_reactions();

		$this->assertSame( 0, $this->count_reaction_queries( array( '_fields' => 'id,user_id,date' ) ) );
	}

	/**
	 * ...and a selected one still does.
	 */
	public function test_reaction_tables_are_read_when_a_reaction_field_is_selected() {
		$this->require_reactions();

		$this->assertGreaterThan( 0, $this->count_reaction_queries( array( '_fields' => 'id,reacted_counts' ) ) );
	}

	/**
	 * A narrow selection must cost measurably fewer queries than no selection.
	 *
	 * The floor is deliberately conservative, because the exact saving depends
	 * on which components are active. Measured on the stock test install the
	 * saving is ~13 queries per row; before the controller honoured `_fields`
	 * the same comparison differed by ~1.5 per row, which is the churn of the
	 * caches those two requests happen to share. Three sits between the two
	 * with room on either side. Most of the saving is the reaction lookups, so
	 * the test stands down where that feature is absent.
	 */
	public function test_narrow_field_selection_runs_fewer_queries() {
		$this->require_reactions();

		$rows              = 10;
		$minimum_saved_row = 3;

		// Distinct authors, so the per-author lookups are not shared.
		foreach ( self::factory()->user->create_many( $rows ) as $user_id ) {
			self::factory()->activity->create(
				array(
					'user_id'   => $user_id,
					'component' => 'activity',
					'type'      => 'activity_update',
				)
			);
		}

		/*
		 * Warm up first. Some of what the feed touches is memoised outside the
		 * object cache, so an unwarmed first request would make whichever
		 * measurement ran first look more expensive than it is.
		 */
		$this->get_first_item( array( 'per_page' => $rows ) );

		$full   = $this->count_queries( array( 'per_page' => $rows ) );
		$narrow = $this->count_queries(
			array(
				'per_page' => $rows,
				'_fields'  => 'id,user_id,date',
			)
		);

		$this->assertGreaterThanOrEqual(
			$minimum_saved_row * $rows,
			$full - $narrow,
			sprintf( 'No selection cost %d queries, a narrow selection cost %d, over %d rows.', $full, $narrow, $rows )
		);
	}

	/**
	 * A field the controller returns has to be in the schema, otherwise
	 * `get_fields_for_response()` cannot see it and it can never be guarded.
	 *
	 * @dataProvider undeclared_field_provider
	 *
	 * @param string $field Field name.
	 */
	public function test_previously_undeclared_field_is_in_the_schema( $field ) {
		$schema = $this->endpoint->get_item_schema();

		$this->assertArrayHasKey( $field, $schema['properties'] );
		$this->assertNotEmpty( $schema['properties'][ $field ]['readonly'] );
	}

	/**
	 * ...and being in the schema must not make it writable.
	 *
	 * @dataProvider undeclared_field_provider
	 *
	 * @param string $field Field name.
	 */
	public function test_previously_undeclared_field_is_not_a_request_argument( $field ) {
		foreach ( array( WP_REST_Server::CREATABLE, WP_REST_Server::EDITABLE ) as $method ) {
			$this->assertArrayNotHasKey(
				$field,
				$this->endpoint->get_endpoint_args_for_item_schema( $method ),
				sprintf( 'Field "%s" became a writable argument.', $field )
			);
		}
	}

	/**
	 * `comment_fields` is the selection that reaches nested comments, since
	 * the parent's `_fields` cannot: WordPress hands a list back whole.
	 */
	public function test_comment_fields_narrows_the_nested_comments() {
		$this->create_comment();

		$item = $this->get_first_item(
			array(
				'display_comments' => 'threaded',
				'include'          => $this->activity_id,
				'comment_fields'   => 'id,content',
			)
		);

		$this->assertNotEmpty( $item['comments'] );

		$comment = $item['comments'][0];

		$this->assertArrayHasKey( 'id', $comment );
		$this->assertArrayHasKey( 'content', $comment );
		$this->assertArrayNotHasKey( 'activity_data', $comment );
		$this->assertArrayNotHasKey( 'reacted_counts', $comment );
	}

	/**
	 * ...and it applies to the comments only, never to their parent.
	 */
	public function test_comment_fields_leaves_the_parent_activity_whole() {
		$this->create_comment();

		$item = $this->get_first_item(
			array(
				'display_comments' => 'threaded',
				'include'          => $this->activity_id,
				'comment_fields'   => 'id',
			)
		);

		$this->assertArrayHasKey( 'content', $item );
		$this->assertArrayHasKey( 'activity_data', $item );
	}

	/**
	 * The activity comment controller honours it too.
	 */
	public function test_activity_comment_endpoint_honours_comment_fields() {
		$this->create_comment();

		$request = new WP_REST_Request( 'GET', $this->endpoint_url . '/' . $this->activity_id . '/comment' );
		$request->set_param( 'context', 'view' );
		$request->set_param( 'comment_fields', 'id,content' );

		$data = $this->dispatch( $request )->get_data();

		$this->assertNotEmpty( $data['comments'] );

		$comment = $data['comments'][0];

		$this->assertArrayHasKey( 'content', $comment );
		$this->assertArrayNotHasKey( 'activity_data', $comment );
	}

	/**
	 * An unselected comment field must not be built for the comments either.
	 */
	public function test_comment_fields_skips_the_work_behind_unselected_comment_fields() {
		$this->create_comment();

		$builds  = 0;
		$counter = function ( $data ) use ( &$builds ) {
			$builds++;

			return $data;
		};

		add_filter( 'bp_activity_get_edit_data', $counter );

		try {
			$this->get_first_item(
				array(
					'display_comments' => 'threaded',
					'include'          => $this->activity_id,
					'comment_fields'   => 'id,content',
					'_fields'          => 'id,comments',
				)
			);
		} finally {
			remove_filter( 'bp_activity_get_edit_data', $counter );
		}

		$this->assertSame( 0, $builds );
	}

	/**
	 * Fields added with `bp_rest_register_field()` are handed the prepared
	 * activity and read keys off it, so those keys have to survive a selection
	 * that does not name them.
	 */
	public function test_registered_rest_field_resolves_under_a_narrow_selection() {
		$properties = $this->endpoint->get_item_schema();

		if ( ! isset( $properties['properties']['can_report'] ) ) {
			$this->markTestSkipped( 'Moderation is not active on this install.' );
		}

		$item = $this->get_first_item( array( '_fields' => 'id,can_report' ) );

		$this->assertArrayHasKey( 'can_report', $item );
	}

	/**
	 * `DELETE` answers with an envelope, so its `_fields` names envelope keys.
	 * The activity nested under `previous` must still be built in full.
	 */
	public function test_delete_returns_a_whole_previous_activity() {
		$request = new WP_REST_Request( 'DELETE', $this->endpoint_url . '/' . $this->activity_id );
		$request->set_param( 'context', 'edit' );
		$request->set_param( '_fields', 'deleted,previous' );

		$data = $this->dispatch( $request )->get_data();

		$this->assertTrue( $data['deleted'] );
		$this->assertArrayHasKey( 'previous', $data );
		$this->assertArrayHasKey( 'content', $data['previous'] );
		$this->assertArrayHasKey( 'user_id', $data['previous'] );
		$this->assertArrayHasKey( 'activity_data', $data['previous'] );
	}

	/**
	 * The activity comment controller has a schema of its own, so its
	 * `_fields` must not narrow the activity items nested inside it.
	 */
	public function test_activity_comment_endpoint_returns_whole_comments() {
		$this->create_comment();

		$request = new WP_REST_Request( 'GET', $this->endpoint_url . '/' . $this->activity_id . '/comment' );
		$request->set_param( 'context', 'view' );
		$request->set_param( '_fields', 'comments' );

		$data = $this->dispatch( $request )->get_data();

		$this->assertArrayHasKey( 'comments', $data );
		$this->assertNotEmpty( $data['comments'] );

		$comment = $data['comments'][0];

		$this->assertArrayHasKey( 'content', $comment );
		$this->assertArrayHasKey( 'user_id', $comment );

		$properties = $this->endpoint->get_item_schema();

		if ( isset( $properties['properties']['can_report'] ) ) {
			$this->assertArrayHasKey( 'can_report', $comment );
		}
	}

	/**
	 * Capability checks that used to run for every activity whatever the
	 * request asked for.
	 *
	 * Each costs a settings read and a permission check per item, so on a page
	 * of twenty they were twenty checks nobody had asked for.
	 *
	 * @return array
	 */
	public static function permission_field_provider() {
		return array(
			'can_delete'  => array( 'can_delete', 'bp_activity_user_can_delete' ),
			'can_comment' => array( 'can_comment', 'bp_activity_can_comment' ),
			'can_edit'    => array( 'can_edit', 'bp_activity_user_can_edit' ),
		);
	}

	/**
	 * A permission the request did not ask about must not be worked out.
	 *
	 * @dataProvider permission_field_provider
	 *
	 * @param string $field Field name.
	 * @param string $hook  Filter the permission check fires.
	 */
	public function test_permission_is_not_resolved_when_its_field_is_not_selected( $field, $hook ) {
		$this->assertSame( 0, $this->count_hook( $hook, array( '_fields' => 'id' ) ) );
	}

	/**
	 * ...and one it did ask about still is.
	 *
	 * @dataProvider permission_field_provider
	 *
	 * @param string $field Field name.
	 * @param string $hook  Filter the permission check fires.
	 */
	public function test_permission_is_resolved_when_its_field_is_selected( $field, $hook ) {
		$this->assertGreaterThan( 0, $this->count_hook( $hook, array( '_fields' => 'id,' . $field ) ) );
	}

	/**
	 * `can_favorite` is a special case: `prepare_links()` asks the same question
	 * to decide whether to offer the favourite link, and that is link building
	 * rather than field building. So the field's own check has to show up as an
	 * increase over what the links already cost, not as a count from zero.
	 */
	public function test_favourite_permission_is_only_resolved_once_more_when_selected() {
		$without = $this->count_hook( 'bp_activity_can_favorite', array( '_fields' => 'id' ) );
		$with    = $this->count_hook( 'bp_activity_can_favorite', array( '_fields' => 'id,can_favorite' ) );

		$this->assertGreaterThan( $without, $with );
	}

	/**
	 * Count how many times a hook fires while a collection request is served.
	 *
	 * WordPress asks every route's permission callback for each `self` link, to
	 * fill in the `targetHints` it attaches. That happens outside the
	 * controller and would swamp what is being measured here, so the hint is
	 * declared up front for the duration of the count, which is what makes
	 * WordPress skip the probe.
	 *
	 * @param string $hook   Hook name.
	 * @param array  $params Request parameters.
	 *
	 * @return int
	 */
	protected function count_hook( $hook, $params ) {
		$calls = 0;

		$counter = function ( $value ) use ( &$calls ) {
			$calls++;

			return $value;
		};

		$declare_hints = function ( $links ) {
			$links['self']['targetHints'] = array( 'allow' => array( 'GET' ) );

			return $links;
		};

		add_filter( $hook, $counter );
		add_filter( 'bp_rest_activity_prepare_links', $declare_hints, 999 );

		/*
		 * The edit check is only reached when editing is switched on, and a
		 * fresh install has it off. Without this the `can_edit` case would
		 * short-circuit and prove nothing either way.
		 */
		add_filter( 'bp_is_activity_edit_enabled', '__return_true' );

		try {
			$this->get_first_item( $params );
		} finally {
			remove_filter( $hook, $counter );
			remove_filter( 'bp_rest_activity_prepare_links', $declare_hints, 999 );
			remove_filter( 'bp_is_activity_edit_enabled', '__return_true' );
		}

		return $calls;
	}

	/**
	 * Add a comment to the fixture activity.
	 *
	 * @return int Activity comment ID.
	 */
	protected function create_comment() {
		return bp_activity_new_comment(
			array(
				'activity_id'       => $this->activity_id,
				'parent_id'         => $this->activity_id,
				'user_id'           => $this->user_id,
				'content'           => 'A comment on the fixture.',
				'skip_error_return' => true,
			)
		);
	}
}
