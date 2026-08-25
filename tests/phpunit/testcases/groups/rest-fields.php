<?php
/**
 * Tests for the `_fields` support of the groups REST controller.
 *
 * A group row resolves five membership checks, four upload permissions, the
 * sub-group list, the group type, avatars, the cover image and the admin and
 * moderator lists. None of it should run for a field the client did not ask
 * for.
 *
 * @group groups
 * @group rest
 */
class BP_Tests_Groups_REST_Fields extends BP_UnitTestCase {

	/**
	 * REST server.
	 *
	 * @var WP_REST_Server
	 */
	protected $server;

	/**
	 * Groups endpoint controller.
	 *
	 * @var BP_REST_Groups_Endpoint
	 */
	protected $endpoint;

	/**
	 * Collection route.
	 *
	 * @var string
	 */
	protected $endpoint_url;

	/**
	 * Fixtures.
	 *
	 * @var int
	 */
	protected $user_id;
	protected $group_id;

	/**
	 * Set up the REST server and a group fixture.
	 */
	public function setUp(): void {
		parent::setUp();

		if ( ! bp_is_active( 'groups' ) ) {
			$this->markTestSkipped( 'Groups are not active on this install.' );
		}

		if ( ! get_term_by( 'name', 'invites-member-invite', bp_get_email_tax_type() ) ) {
			require_once buddypress()->plugin_dir . 'bp-core/admin/bp-core-admin-schema.php';
			bp_core_install_emails();
		}

		global $wp_rest_server;
		$wp_rest_server = new WP_REST_Server();
		do_action( 'rest_api_init', $wp_rest_server );
		$this->server = $wp_rest_server;

		$this->endpoint     = new BP_REST_Groups_Endpoint();
		$this->endpoint_url = '/' . bp_rest_namespace() . '/' . bp_rest_version() . '/groups';

		$this->user_id = self::factory()->user->create( array( 'role' => 'subscriber' ) );
		wp_set_current_user( $this->user_id );

		$this->group_id = self::factory()->group->create(
			array(
				'creator_id' => $this->user_id,
				'name'       => 'Field selection fixture',
				'status'     => 'public',
			)
		);
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
	 * Dispatch the way `WP_REST_Server::serve_request()` does.
	 *
	 * @param array $params Request parameters.
	 *
	 * @return array First group in the collection.
	 */
	protected function get_first_group( $params = array() ) {
		$request = new WP_REST_Request( 'GET', $this->endpoint_url );
		$request->set_param( 'context', 'view' );

		foreach ( $params as $key => $value ) {
			$request->set_param( $key, $value );
		}

		$response = $this->server->dispatch( $request );
		$response = apply_filters( 'rest_post_dispatch', rest_ensure_response( $response ), $this->server, $request );
		$data     = $response->get_data();

		$this->assertNotEmpty( $data, 'The groups collection came back empty.' );

		return $data[0];
	}

	/**
	 * Count how many times a hook fires while a request is dispatched.
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

		add_filter( $hook, $counter );

		try {
			$this->get_first_group( $params );
		} finally {
			remove_filter( $hook, $counter );
		}

		return $calls;
	}

	/**
	 * Count the queries one groups request costs, from a cold cache.
	 *
	 * @param array $params Request parameters.
	 *
	 * @return int
	 */
	protected function count_queries( $params ) {
		global $wpdb;

		wp_cache_flush();

		$before = $wpdb->num_queries;
		$this->get_first_group( $params );

		return $wpdb->num_queries - $before;
	}

	/**
	 * A request that sends no `_fields` must still return every field the
	 * `view` context exposes.
	 */
	public function test_no_field_selection_returns_every_view_field() {
		$group  = $this->get_first_group();
		$schema = $this->endpoint->get_item_schema();

		foreach ( $schema['properties'] as $field => $property ) {
			if ( empty( $property['context'] ) || ! in_array( 'view', (array) $property['context'], true ) ) {
				continue;
			}

			// Only set when the group actually carries one.
			if ( in_array( $field, array( 'group_type', 'cover_url', 'cover_is_default', 'is_subscribed', 'subscribed_id' ), true ) ) {
				continue;
			}

			$this->assertArrayHasKey( $field, $group, sprintf( 'Group field "%s" went missing.', $field ) );
		}
	}

	/**
	 * Every key the unfiltered response returns has to be declared in the
	 * schema. An undeclared one cannot be guarded -- it would disappear from
	 * responses that send no `_fields` at all.
	 */
	public function test_every_returned_field_is_declared_in_the_schema() {
		$schema = $this->endpoint->get_item_schema();
		$item   = $this->get_first_group();

		foreach ( array_keys( $item ) as $field ) {
			if ( '_links' === $field || '_embedded' === $field ) {
				continue;
			}

			$this->assertArrayHasKey(
				$field,
				$schema['properties'],
				sprintf( 'The groups controller returns "%s" but does not declare it.', $field )
			);
		}
	}

	/**
	 * A narrow selection returns exactly the requested keys.
	 */
	public function test_narrow_field_selection_returns_only_the_requested_keys() {
		$group = $this->get_first_group( array( '_fields' => 'id,name,slug' ) );

		$actual = array_keys( $group );
		sort( $actual );

		$this->assertSame( array( 'id', 'name', 'slug' ), $actual );
	}

	/**
	 * Avatars cost two resolutions per row.
	 */
	public function test_avatars_are_not_fetched_when_not_selected() {
		$this->assertSame( 0, $this->count_hook( 'bp_core_fetch_avatar_url', array( '_fields' => 'id,name' ) ) );
	}

	/**
	 * ...but a selected one still is.
	 */
	public function test_avatars_are_fetched_when_selected() {
		$schema = $this->endpoint->get_item_schema();

		if ( empty( $schema['properties']['avatar_urls'] ) ) {
			$this->markTestSkipped( 'Avatars are disabled on this install.' );
		}

		$this->assertGreaterThan( 0, $this->count_hook( 'bp_core_fetch_avatar_url', array( '_fields' => 'id,avatar_urls' ) ) );
	}

	/**
	 * The admin and moderator lists cost a member query plus an avatar each;
	 * they must not be assembled unless asked for.
	 */
	public function test_admin_list_is_not_assembled_when_not_selected() {
		$group = $this->get_first_group( array( '_fields' => 'id,name' ) );

		$this->assertArrayNotHasKey( 'admins', $group );
	}

	/**
	 * ...and is still assembled when it is.
	 */
	public function test_admin_list_is_assembled_when_selected() {
		$group = $this->get_first_group( array( '_fields' => 'id,admins' ) );

		$this->assertArrayHasKey( 'admins', $group );
		$this->assertNotEmpty( $group['admins'] );
	}

	/**
	 * `plural_role` falls back to `role`; that fallback must not depend on
	 * `role` having been selected.
	 */
	public function test_plural_role_resolves_without_role_being_selected() {
		$full   = $this->get_first_group();
		$narrow = $this->get_first_group( array( '_fields' => 'id,plural_role' ) );

		$this->assertArrayNotHasKey( 'role', $narrow );
		$this->assertSame( $full['plural_role'], $narrow['plural_role'] );
	}

	/**
	 * The group type block refines the type list; it must read the resolved
	 * value rather than the response key.
	 */
	public function test_types_resolve_without_the_label_being_selected() {
		$full   = $this->get_first_group();
		$narrow = $this->get_first_group( array( '_fields' => 'id,types' ) );

		$this->assertArrayNotHasKey( 'group_type_label', $narrow );
		$this->assertSame( $full['types'], $narrow['types'] );
	}

	/**
	 * A narrow selection must cost measurably fewer queries than no selection.
	 */
	public function test_narrow_field_selection_runs_fewer_queries() {
		self::factory()->group->create_many( 4, array( 'creator_id' => $this->user_id, 'status' => 'public' ) );

		// Warm anything memoised outside the object cache first.
		$this->get_first_group();

		$full   = $this->count_queries( array() );
		$narrow = $this->count_queries( array( '_fields' => 'id,name,slug' ) );

		$this->assertLessThan(
			$full,
			$narrow,
			sprintf( 'No selection cost %d queries, a narrow selection cost %d.', $full, $narrow )
		);
	}
}
