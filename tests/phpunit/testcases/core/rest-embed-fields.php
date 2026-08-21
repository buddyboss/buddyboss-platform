<?php
/**
 * Tests for `embed_fields`, the selection that reaches the items WordPress
 * embeds under `_embedded`.
 *
 * `WP_REST_Server::embed_links()` builds each embedded item from its link
 * alone: `WP_REST_Request::from_url()` is handed nothing but the `href`, and
 * the caller's request is never consulted. The caller's own `_fields` can
 * therefore never reach an embedded item. `embed_fields` is carried onto that
 * request instead, which both narrows what comes back and skips the work
 * behind everything left out.
 *
 * @group core
 * @group rest
 */
class BP_Tests_Core_REST_Embed_Fields extends BP_UnitTestCase {

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
	 * ID of the group the fixture activity belongs to.
	 *
	 * @var int
	 */
	protected $group_id;

	/**
	 * ID of the fixture activity.
	 *
	 * @var int
	 */
	protected $activity_id;

	/**
	 * Set up the REST server and a group activity, so that the item carries
	 * both of the relations the activity controller marks embeddable.
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

		$this->group_id = self::factory()->group->create(
			array(
				'creator_id' => $this->user_id,
				'status'     => 'public',
			)
		);

		$this->activity_id = self::factory()->activity->create(
			array(
				'user_id'   => $this->user_id,
				'component' => 'groups',
				'item_id'   => $this->group_id,
				'type'      => 'activity_update',
				'content'   => 'Embedded field selection fixture.',
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
	 * Dispatch a request the way `WP_REST_Server::serve_request()` does.
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
	 * Fetch the first activity of the collection with its links embedded.
	 *
	 * @param array      $params Request parameters.
	 * @param array|true $rels   Relations to embed.
	 *
	 * @return array The first item of the collection, embeds included.
	 */
	protected function get_first_item( $params = array(), $rels = true ) {
		$request = new WP_REST_Request( 'GET', $this->endpoint_url );
		$request->set_param( 'context', 'view' );
		$request->set_param( '_fields', 'id,_links,_embedded' );

		foreach ( $params as $key => $value ) {
			$request->set_param( $key, $value );
		}

		$response = $this->dispatch( $request );
		$data     = $this->server->response_to_data( $response, $rels );

		$this->assertNotEmpty( $data, 'The activity collection came back empty.' );
		$this->assertArrayHasKey( '_embedded', $data[0], 'The activity came back with nothing embedded.' );

		return $data[0];
	}

	/**
	 * Read one embedded item off an activity.
	 *
	 * @param array  $item Activity item.
	 * @param string $rel  Link relation.
	 *
	 * @return array The embedded item.
	 */
	protected function get_embedded( $item, $rel ) {
		$this->assertArrayHasKey( $rel, $item['_embedded'], "Nothing was embedded for the `{$rel}` relation." );
		$this->assertNotEmpty( $item['_embedded'][ $rel ] );

		return $item['_embedded'][ $rel ][0];
	}

	/**
	 * Count how many times a hook fires while a collection is embedded.
	 *
	 * @param string     $hook   Hook name.
	 * @param array      $params Request parameters.
	 * @param array|true $rels   Relations to embed.
	 *
	 * @return int
	 */
	protected function count_hook( $hook, $params, $rels = true ) {
		$calls = 0;

		$counter = function ( $value ) use ( &$calls ) {
			$calls++;

			return $value;
		};

		add_filter( $hook, $counter );

		try {
			$this->get_first_item( $params, $rels );
		} finally {
			remove_filter( $hook, $counter );
		}

		return $calls;
	}

	/**
	 * Without a selection the embedded items stay exactly as they were.
	 */
	public function test_embedded_items_are_whole_without_a_selection() {
		$item = $this->get_first_item();

		$user = $this->get_embedded( $item, 'user' );

		$this->assertArrayHasKey( 'id', $user );
		$this->assertArrayHasKey( 'mention_name', $user );
		$this->assertArrayHasKey( 'link', $user );
		$this->assertArrayHasKey( '_links', $user );

		$group = $this->get_embedded( $item, 'group' );

		$this->assertArrayHasKey( 'name', $group );
		$this->assertArrayHasKey( 'status', $group );
	}

	/**
	 * One list narrows every embedded relation.
	 */
	public function test_embed_fields_narrows_every_relation() {
		$item = $this->get_first_item( array( 'embed_fields' => 'id,name' ) );

		$this->assertSame( array( 'id', 'name' ), array_keys( $this->get_embedded( $item, 'user' ) ) );
		$this->assertSame( array( 'id', 'name' ), array_keys( $this->get_embedded( $item, 'group' ) ) );
	}

	/**
	 * A list per relation narrows only the relations it names.
	 */
	public function test_embed_fields_narrows_one_relation_at_a_time() {
		$item = $this->get_first_item( array( 'embed_fields' => array( 'user' => 'id,name' ) ) );

		$this->assertSame( array( 'id', 'name' ), array_keys( $this->get_embedded( $item, 'user' ) ) );

		$group = $this->get_embedded( $item, 'group' );

		$this->assertArrayHasKey( 'status', $group );
		$this->assertArrayHasKey( '_links', $group );
	}

	/**
	 * `*` is the selection every relation without one of its own falls back to.
	 */
	public function test_embed_fields_falls_back_to_the_default_selection() {
		$item = $this->get_first_item(
			array(
				'embed_fields' => array(
					'*'    => 'id',
					'user' => 'id,name',
				),
			)
		);

		$this->assertSame( array( 'id', 'name' ), array_keys( $this->get_embedded( $item, 'user' ) ) );
		$this->assertSame( array( 'id' ), array_keys( $this->get_embedded( $item, 'group' ) ) );
	}

	/**
	 * The selection reaches the embedded items only, never their parent.
	 */
	public function test_embed_fields_leaves_the_outer_item_whole() {
		$request = new WP_REST_Request( 'GET', $this->endpoint_url );
		$request->set_param( 'context', 'view' );
		$request->set_param( 'embed_fields', 'id,name' );

		$data = $this->dispatch( $request )->get_data();

		$this->assertNotEmpty( $data );
		$this->assertArrayHasKey( 'content', $data[0] );
		$this->assertArrayHasKey( 'activity_data', $data[0] );
	}

	/**
	 * A selection that does not name `_links` is answered without them, the
	 * same way `_fields` answers a collection.
	 */
	public function test_embed_fields_drops_the_links_it_was_not_asked_for() {
		$item = $this->get_first_item( array( 'embed_fields' => 'id,name' ) );

		$this->assertArrayNotHasKey( '_links', $this->get_embedded( $item, 'user' ) );
	}

	/**
	 * ...and a selection that names them keeps them.
	 */
	public function test_embed_fields_keeps_the_links_it_was_asked_for() {
		$item = $this->get_first_item( array( 'embed_fields' => 'id,_links' ) );

		$user = $this->get_embedded( $item, 'user' );

		$this->assertArrayHasKey( '_links', $user );
		$this->assertArrayNotHasKey( 'name', $user );
	}

	/**
	 * A single item keeps its links on the response rather than in its data,
	 * and the selection has to find them there too.
	 */
	public function test_embed_fields_narrows_a_single_item_response() {
		$request = new WP_REST_Request( 'GET', $this->endpoint_url . '/' . $this->activity_id );
		$request->set_param( 'context', 'view' );
		$request->set_param( 'embed_fields', 'id,name' );

		$response = $this->dispatch( $request );
		$data     = $this->server->response_to_data( $response, true );

		$this->assertArrayHasKey( '_embedded', $data );
		$this->assertSame( array( 'id', 'name' ), array_keys( $data['_embedded']['user'][0] ) );

		// The activity itself was asked for nothing, so it comes back whole.
		$this->assertArrayHasKey( 'content', $data );
	}

	/**
	 * The selection stands on its own: the caller does not have to send a
	 * `_fields` of its own for it to reach the embedded items.
	 */
	public function test_embed_fields_needs_no_field_selection_of_its_own() {
		$request = new WP_REST_Request( 'GET', $this->endpoint_url );
		$request->set_param( 'context', 'view' );
		$request->set_param( 'embed_fields', 'id,name' );

		$response = $this->dispatch( $request );
		$data     = $this->server->response_to_data( $response, array( 'user' ) );

		$this->assertNotEmpty( $data );
		$this->assertArrayHasKey( 'content', $data[0] );
		$this->assertSame( array( 'id', 'name' ), array_keys( $data[0]['_embedded']['user'][0] ) );
	}

	/**
	 * An unselected field of an embedded item must not be built either.
	 */
	public function test_embed_fields_skips_the_work_behind_an_unselected_field() {
		$this->assertSame(
			0,
			$this->count_hook(
				'bp_core_fetch_avatar_url',
				array( 'embed_fields' => 'id,name' ),
				array( 'user' )
			)
		);
	}

	/**
	 * ...and a selected one still is.
	 */
	public function test_embed_fields_does_the_work_behind_a_selected_field() {
		$this->assertGreaterThan(
			0,
			$this->count_hook(
				'bp_core_fetch_avatar_url',
				array( 'embed_fields' => 'id,name,avatar_urls' ),
				array( 'user' )
			)
		);
	}

	/**
	 * Every BuddyBoss controller answers a single item the way it answers a
	 * member of a collection, with the links flattened into the data. A
	 * controller that keeps them on the response instead -- the shape
	 * `WP_REST_Controller` produces -- has to be read just the same.
	 */
	public function test_links_kept_on_the_response_are_mapped_too() {
		$response = new WP_REST_Response( array( 'id' => 1 ) );

		$response->add_link( 'self', 'https://example.org/self' );
		$response->add_link( 'user', 'https://example.org/user', array( 'embeddable' => true ) );

		$map = bb_rest_map_embed_fields_to_links( $response, array( '*' => 'id,name' ) );

		$this->assertSame( array( 'https://example.org/user' => 'id,name' ), $map );
	}

	/**
	 * A relation with no selection of its own, and no `*` to fall back to, is
	 * left out of the map entirely, so its item is built in full.
	 */
	public function test_a_relation_without_a_selection_is_not_mapped() {
		$response = new WP_REST_Response( array( 'id' => 1 ) );

		$response->add_link( 'user', 'https://example.org/user', array( 'embeddable' => true ) );

		$this->assertSame( array(), bb_rest_map_embed_fields_to_links( $response, array( 'group' => 'id' ) ) );
	}

	/**
	 * Parsing a parsed selection returns it unchanged, so that a controller
	 * can sanitise the parameter with the parser and still read it later.
	 */
	public function test_parsing_a_selection_is_idempotent() {
		$once = bb_rest_parse_embed_fields( ' id , name ' );

		$this->assertSame( array( '*' => 'id,name' ), $once );
		$this->assertSame( $once, bb_rest_parse_embed_fields( $once ) );
	}

	/**
	 * `embed_fields[]=id,name` is a spelling a client reaches for as readily
	 * as the bare string, and it has to mean the same thing rather than
	 * quietly resolving to no selection at all.
	 */
	public function test_a_list_without_relations_is_read_as_one_selection() {
		$this->assertSame( array( '*' => 'id,name' ), bb_rest_parse_embed_fields( array( 'id,name' ) ) );
		$this->assertSame( array( '*' => 'id,name' ), bb_rest_parse_embed_fields( array( 'id', 'name' ) ) );
	}

	/**
	 * WordPress builds and caches an embedded item once per `href`, so two
	 * relations sharing a URL cannot be answered with two selections. Neither
	 * may be applied, or one of them silently loses fields it asked for.
	 */
	public function test_a_shared_href_with_conflicting_selections_is_left_whole() {
		$response = new WP_REST_Response( array( 'id' => 1 ) );

		$response->add_link( 'user', 'https://example.org/members/5', array( 'embeddable' => true ) );
		$response->add_link( 'author', 'https://example.org/members/5', array( 'embeddable' => true ) );

		$map = bb_rest_map_embed_fields_to_links(
			$response,
			array(
				'user'   => 'id,name',
				'author' => 'avatar_urls',
			)
		);

		$this->assertSame( array(), $map );
	}

	/**
	 * ...but relations that agree are still narrowed.
	 */
	public function test_a_shared_href_with_one_selection_is_narrowed() {
		$response = new WP_REST_Response( array( 'id' => 1 ) );

		$response->add_link( 'user', 'https://example.org/members/5', array( 'embeddable' => true ) );
		$response->add_link( 'author', 'https://example.org/members/5', array( 'embeddable' => true ) );

		$map = bb_rest_map_embed_fields_to_links( $response, array( '*' => 'id,name' ) );

		$this->assertSame( array( 'https://example.org/members/5' => 'id,name' ), $map );
	}

	/**
	 * An embedded item is recognised by identity, never by anything the client
	 * can send. A request that dresses itself up as one keeps its links, and
	 * still clears what an earlier response left held.
	 */
	public function test_a_client_cannot_pass_itself_off_as_an_embedded_item() {
		bb_rest_held_embed_fields( array( 'https://example.org/members/5' => 'id' ) );

		$request = new WP_REST_Request( 'GET', $this->endpoint_url );
		$request->set_param( 'bb_embedded_item', true );
		$request->set_param( '_fields', 'id' );

		$response = new WP_REST_Response( array( 'id' => 1 ) );
		$response->add_link( 'self', 'https://example.org/activity/1' );

		bb_rest_prepare_embedded_fields( $response, $this->server, $request );

		$this->assertArrayHasKey( 'self', $response->get_links() );
		$this->assertSame( array(), bb_rest_held_embed_fields() );
	}

	/**
	 * Controllers whose items carry embeddable links.
	 *
	 * @return array
	 */
	public static function embeddable_controller_provider() {
		return array(
			array( 'BP_REST_Activity_Endpoint' ),
			array( 'BP_REST_Groups_Endpoint' ),
			array( 'BP_REST_Media_Endpoint' ),
			array( 'BP_REST_Document_Endpoint' ),
		);
	}

	/**
	 * The parameter is declared, so that it shows up in `OPTIONS`.
	 *
	 * @dataProvider embeddable_controller_provider
	 *
	 * @param string $controller Controller class name.
	 */
	public function test_embed_fields_is_a_collection_parameter( $controller ) {
		if ( ! class_exists( $controller ) ) {
			$this->markTestSkipped( "{$controller} is not available on this install." );
		}

		$endpoint = new $controller();

		$this->assertArrayHasKey( 'embed_fields', $endpoint->get_collection_params() );
	}
}
