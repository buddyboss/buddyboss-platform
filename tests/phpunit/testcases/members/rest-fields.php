<?php
/**
 * Tests for the `_fields` support of the members REST controller.
 *
 * A member row is expensive: follower and following ID lists that exist only
 * to be counted, two friendship lookups, the xprofile field set, two avatar
 * resolutions and a messaging permission check. None of it should run for a
 * field the client did not ask for.
 *
 * @group members
 * @group rest
 */
class BP_Tests_Members_REST_Fields extends BP_UnitTestCase {

	/**
	 * REST server.
	 *
	 * @var WP_REST_Server
	 */
	protected $server;

	/**
	 * Members endpoint controller.
	 *
	 * @var BP_REST_Members_Endpoint
	 */
	protected $endpoint;

	/**
	 * Collection route.
	 *
	 * @var string
	 */
	protected $endpoint_url;

	/**
	 * Fixture user.
	 *
	 * @var int
	 */
	protected $user_id;

	/**
	 * Set up the REST server and a member fixture.
	 */
	public function setUp(): void {
		parent::setUp();

		if ( ! get_term_by( 'name', 'invites-member-invite', bp_get_email_tax_type() ) ) {
			require_once buddypress()->plugin_dir . 'bp-core/admin/bp-core-admin-schema.php';
			bp_core_install_emails();
		}

		global $wp_rest_server;
		$wp_rest_server = new WP_REST_Server();
		do_action( 'rest_api_init', $wp_rest_server );
		$this->server = $wp_rest_server;

		$this->endpoint     = new BP_REST_Members_Endpoint();
		$this->endpoint_url = '/' . bp_rest_namespace() . '/' . bp_rest_version() . '/members';

		$this->user_id = self::factory()->user->create( array( 'role' => 'subscriber' ) );
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
	 * Dispatch the way `WP_REST_Server::serve_request()` does.
	 *
	 * @param array $params Request parameters.
	 *
	 * @return array First member in the collection.
	 */
	protected function get_first_member( $params = array() ) {
		$request = new WP_REST_Request( 'GET', $this->endpoint_url );
		$request->set_param( 'context', 'view' );

		foreach ( $params as $key => $value ) {
			$request->set_param( $key, $value );
		}

		$response = $this->server->dispatch( $request );
		$response = apply_filters( 'rest_post_dispatch', rest_ensure_response( $response ), $this->server, $request );
		$data     = $response->get_data();

		$this->assertNotEmpty( $data, 'The members collection came back empty.' );

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
			$this->get_first_member( $params );
		} finally {
			remove_filter( $hook, $counter );
		}

		return $calls;
	}

	/**
	 * Count the queries one members request costs, from a cold cache.
	 *
	 * @param array $params Request parameters.
	 *
	 * @return int
	 */
	protected function count_queries( $params ) {
		global $wpdb;

		wp_cache_flush();

		$before = $wpdb->num_queries;
		$this->get_first_member( $params );

		return $wpdb->num_queries - $before;
	}

	/**
	 * A request that sends no `_fields` must still return every field the
	 * `view` context exposes.
	 */
	public function test_no_field_selection_returns_every_view_field() {
		$member = $this->get_first_member();
		$schema = $this->endpoint->get_item_schema();

		foreach ( $schema['properties'] as $field => $property ) {
			if ( empty( $property['context'] ) || ! in_array( 'view', (array) $property['context'], true ) ) {
				continue;
			}

			$this->assertArrayHasKey( $field, $member, sprintf( 'Member field "%s" went missing.', $field ) );
		}
	}

	/**
	 * Every key the unfiltered response returns has to be declared in the
	 * schema. An undeclared one cannot be guarded -- it would disappear from
	 * responses that send no `_fields` at all.
	 */
	public function test_every_returned_field_is_declared_in_the_schema() {
		$schema = $this->endpoint->get_item_schema();
		$item   = $this->get_first_member();

		foreach ( array_keys( $item ) as $field ) {
			if ( '_links' === $field || '_embedded' === $field ) {
				continue;
			}

			$this->assertArrayHasKey(
				$field,
				$schema['properties'],
				sprintf( 'The members controller returns "%s" but does not declare it.', $field )
			);
		}
	}

	/**
	 * A narrow selection returns exactly the requested keys.
	 */
	public function test_narrow_field_selection_returns_only_the_requested_keys() {
		$member = $this->get_first_member( array( '_fields' => 'id,name,user_login' ) );

		$actual = array_keys( $member );
		sort( $actual );

		$this->assertSame( array( 'id', 'name', 'user_login' ), $actual );
	}

	/**
	 * Avatars cost two resolutions per row; an unselected `avatar_urls` must
	 * not fetch them.
	 */
	public function test_avatars_are_not_fetched_when_not_selected() {
		$this->assertSame( 0, $this->count_hook( 'bp_core_fetch_avatar_url', array( '_fields' => 'id,name' ) ) );
	}

	/**
	 * ...but a selected one still does.
	 */
	public function test_avatars_are_fetched_when_selected() {
		$schema = $this->endpoint->get_item_schema();

		if ( empty( $schema['properties']['avatar_urls'] ) ) {
			$this->markTestSkipped( 'Avatars are disabled on this install.' );
		}

		$this->assertGreaterThan( 0, $this->count_hook( 'bp_core_fetch_avatar_url', array( '_fields' => 'id,avatar_urls' ) ) );
	}

	/**
	 * The xprofile field set is the most expensive thing a member row builds.
	 * `bp_xprofile_get_groups` fires only from the assembly itself, so it is a
	 * clean probe -- unlike a raw query count, which the member query pollutes
	 * by resolving display names through the same tables.
	 */
	public function test_xprofile_is_not_assembled_when_not_selected() {
		if ( ! bp_is_active( 'xprofile' ) ) {
			$this->markTestSkipped( 'XProfile is not active on this install.' );
		}

		$this->assertSame( 0, $this->count_hook( 'bp_xprofile_get_groups', array( '_fields' => 'id,name' ) ) );
	}

	/**
	 * ...but a selected one still is.
	 */
	public function test_xprofile_is_assembled_when_selected() {
		if ( ! bp_is_active( 'xprofile' ) ) {
			$this->markTestSkipped( 'XProfile is not active on this install.' );
		}

		$this->assertGreaterThan( 0, $this->count_hook( 'bp_xprofile_get_groups', array( '_fields' => 'id,xprofile' ) ) );
	}

	/**
	 * A narrow selection must cost measurably fewer queries than no selection.
	 */
	public function test_narrow_field_selection_runs_fewer_queries() {
		self::factory()->user->create_many( 5 );

		// Warm anything memoised outside the object cache first.
		$this->get_first_member();

		$full   = $this->count_queries( array() );
		$narrow = $this->count_queries( array( '_fields' => 'id,name,user_login' ) );

		$this->assertLessThan(
			$full,
			$narrow,
			sprintf( 'No selection cost %d queries, a narrow selection cost %d.', $full, $narrow )
		);
	}

	/**
	 * `member_types` is refined after it is first resolved; the refinement
	 * must not depend on the field having been selected.
	 */
	public function test_member_types_survive_a_selection_that_names_only_them() {
		$member = $this->get_first_member( array( '_fields' => 'id,member_types' ) );

		$this->assertArrayHasKey( 'member_types', $member );
		$this->assertIsArray( $member['member_types'] );
	}

	/**
	 * The group membership controller merges `user_data()` into a payload of
	 * its own, so a selection there must not break either half.
	 */
	public function test_group_membership_still_merges_member_data() {
		if ( ! bp_is_active( 'groups' ) ) {
			$this->markTestSkipped( 'Groups are not active on this install.' );
		}

		$group_id = self::factory()->group->create( array( 'creator_id' => $this->user_id ) );
		$member   = self::factory()->user->create();
		groups_join_group( $group_id, $member );

		$request = new WP_REST_Request( 'GET', '/' . bp_rest_namespace() . '/' . bp_rest_version() . '/groups/' . $group_id . '/members' );
		$request->set_param( 'context', 'view' );

		$response = $this->server->dispatch( $request );
		$response = apply_filters( 'rest_post_dispatch', rest_ensure_response( $response ), $this->server, $request );
		$data     = $response->get_data();

		$this->assertNotEmpty( $data );
		$this->assertArrayHasKey( 'name', $data[0] );
		$this->assertArrayHasKey( 'is_admin', $data[0] );
	}
}
