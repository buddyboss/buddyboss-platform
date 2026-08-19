<?php
/**
 * Tests for the `_fields` support of the attachment REST controllers.
 *
 * Media, video and documents are returned two ways: as a collection of their
 * own, where `_fields` addresses them directly, and nested under an activity,
 * where it cannot -- WordPress hands a list back whole. `attachment_fields` is
 * the selection that reaches the nested case.
 *
 * @group media
 * @group document
 * @group rest
 */
class BP_Tests_Attachments_REST_Fields extends BP_UnitTestCase {

	/**
	 * REST server.
	 *
	 * @var WP_REST_Server
	 */
	protected $server;

	/**
	 * Media endpoint controller.
	 *
	 * @var BP_REST_Media_Endpoint
	 */
	protected $media_endpoint;

	/**
	 * Document endpoint controller.
	 *
	 * @var BP_REST_Document_Endpoint
	 */
	protected $document_endpoint;

	/**
	 * Fixture IDs.
	 *
	 * @var int
	 */
	protected $user_id;
	protected $activity_id;
	protected $media_id;
	protected $document_id;

	/**
	 * Route bases.
	 *
	 * @var string
	 */
	protected $activity_url;
	protected $media_url;
	protected $document_url;

	/**
	 * Set up the REST server and the attachment fixtures.
	 */
	public function setUp(): void {
		parent::setUp();

		if ( ! bp_is_active( 'media' ) || ! bp_is_active( 'document' ) ) {
			$this->markTestSkipped( 'Media and documents are not both active on this install.' );
		}

		/*
		 * Booting the REST API brings up every BuddyBoss controller, and some
		 * of them read the email taxonomy while registering their routes.
		 */
		if ( ! get_term_by( 'name', 'invites-member-invite', bp_get_email_tax_type() ) ) {
			require_once buddypress()->plugin_dir . 'bp-core/admin/bp-core-admin-schema.php';
			bp_core_install_emails();
		}

		global $wp_rest_server;
		$wp_rest_server = new WP_REST_Server();
		do_action( 'rest_api_init', $wp_rest_server );
		$this->server = $wp_rest_server;

		$this->media_endpoint    = new BP_REST_Media_Endpoint();
		$this->document_endpoint = new BP_REST_Document_Endpoint();

		$base               = '/' . bp_rest_namespace() . '/' . bp_rest_version();
		$this->activity_url = $base . '/activity';
		$this->media_url    = $base . '/media';
		$this->document_url = $base . '/document';

		$this->user_id = self::factory()->user->create( array( 'role' => 'subscriber' ) );
		wp_set_current_user( $this->user_id );

		$this->activity_id = self::factory()->activity->create(
			array(
				'user_id'   => $this->user_id,
				'component' => 'activity',
				'type'      => 'activity_update',
				'content'   => 'Activity carrying attachments.',
			)
		);

		// The attachment callbacks are gated on profile support being on.
		add_filter( 'bp_is_profile_media_support_enabled', '__return_true' );
		add_filter( 'bp_is_profile_document_support_enabled', '__return_true' );

		$this->media_id    = $this->create_media();
		$this->document_id = $this->create_document();

		// They find the attachments through the activity's metadata, not
		// through the attachment's own activity_id.
		bp_activity_update_meta( $this->activity_id, 'bp_media_ids', (string) $this->media_id );
		bp_activity_update_meta( $this->activity_id, 'bp_document_ids', (string) $this->document_id );
		wp_cache_delete( $this->activity_id, 'activity_meta' );
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
	 * Upload a real file and return its attachment ID.
	 *
	 * A genuine upload rather than a stub post: the document controller stats
	 * the file for `size` and the media controller reads image metadata.
	 *
	 * @param string $source Full path to the file to upload.
	 *
	 * @return int
	 */
	protected function create_attachment( $source ) {
		return self::factory()->attachment->create_upload_object( $source );
	}

	/**
	 * Attach a media item to the fixture activity.
	 *
	 * @return int
	 */
	protected function create_media() {
		return bp_media_add(
			array(
				'attachment_id' => $this->create_attachment( DIR_TESTDATA . '/images/canola.jpg' ),
				'user_id'       => $this->user_id,
				'title'         => 'Fixture photo',
				'activity_id'   => $this->activity_id,
				'privacy'       => 'public',
			)
		);
	}

	/**
	 * Attach a document to the fixture activity.
	 *
	 * @return int
	 */
	protected function create_document() {
		return bp_document_add(
			array(
				'attachment_id' => $this->create_attachment( $this->create_text_file() ),
				'user_id'       => $this->user_id,
				'title'         => 'Fixture document',
				'activity_id'   => $this->activity_id,
				'privacy'       => 'public',
			)
		);
	}

	/**
	 * Write a small text file for the document fixture to upload.
	 *
	 * @return string Full path.
	 */
	protected function create_text_file() {
		$path = get_temp_dir() . 'bb-rest-fields-fixture.txt';
		file_put_contents( $path, 'attachment fixture' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents

		return $path;
	}

	/**
	 * Dispatch a request the way `WP_REST_Server::serve_request()` does.
	 *
	 * @param string $route  Route.
	 * @param array  $params Request parameters.
	 *
	 * @return array
	 */
	protected function get_data( $route, $params = array() ) {
		$request = new WP_REST_Request( 'GET', $route );
		$request->set_param( 'context', 'view' );

		foreach ( $params as $key => $value ) {
			$request->set_param( $key, $value );
		}

		$response = $this->server->dispatch( $request );
		$response = apply_filters( 'rest_post_dispatch', rest_ensure_response( $response ), $this->server, $request );

		return $response->get_data();
	}

	/**
	 * Fetch the fixture activity.
	 *
	 * @param array $params Request parameters.
	 *
	 * @return array
	 */
	protected function get_activity( $params = array() ) {
		$data = $this->get_data(
			$this->activity_url,
			array_merge( array( 'include' => $this->activity_id ), $params )
		);

		$this->assertNotEmpty( $data, 'The activity collection came back empty.' );

		return $data[0];
	}

	/**
	 * Count how many times a hook fires while a request is dispatched.
	 *
	 * @param string $hook   Hook name.
	 * @param string $route  Route.
	 * @param array  $params Request parameters.
	 *
	 * @return int
	 */
	protected function count_hook( $hook, $route, $params = array() ) {
		$calls = 0;

		$counter = function ( $value ) use ( &$calls ) {
			$calls++;

			return $value;
		};

		add_filter( $hook, $counter );

		try {
			$this->get_data( $route, $params );
		} finally {
			remove_filter( $hook, $counter );
		}

		return $calls;
	}

	/**
	 * Every key the media controller builds has to be declared in its schema,
	 * otherwise `get_fields_for_response()` cannot see it and it can never be
	 * guarded without disappearing from unfiltered responses.
	 */
	public function test_every_media_field_is_declared_in_the_schema() {
		$schema = $this->media_endpoint->get_item_schema();
		$item   = $this->get_data( $this->media_url );

		$this->assertNotEmpty( $item );

		foreach ( array_keys( $item[0] ) as $field ) {
			if ( '_links' === $field || '_embedded' === $field ) {
				continue;
			}

			$this->assertArrayHasKey(
				$field,
				$schema['properties'],
				sprintf( 'Media returns "%s" but does not declare it.', $field )
			);
		}
	}

	/**
	 * The same for documents. A field the controller returns but does not
	 * declare cannot be guarded: `get_fields_for_response()` would not list it
	 * when no `_fields` is sent, and it would vanish from every response.
	 */
	public function test_every_document_field_is_declared_in_the_schema() {
		$schema = $this->document_endpoint->get_item_schema();
		$item   = $this->get_data( $this->document_url . '/' . $this->document_id );

		$this->assertNotEmpty( $item );

		foreach ( array_keys( $item ) as $field ) {
			if ( '_links' === $field || '_embedded' === $field ) {
				continue;
			}

			$this->assertArrayHasKey(
				$field,
				$schema['properties'],
				sprintf( 'The document controller returns "%s" but does not declare it.', $field )
			);
		}
	}

	/**
	 * A request that sends no `_fields` must return every media field.
	 */
	public function test_no_field_selection_returns_every_media_field() {
		$schema = $this->media_endpoint->get_item_schema();
		$item   = $this->get_data( $this->media_url );

		foreach ( array_keys( $schema['properties'] ) as $field ) {
			$this->assertArrayHasKey( $field, $item[0], sprintf( 'Media field "%s" went missing.', $field ) );
		}
	}

	/**
	 * A narrow selection returns exactly the requested media keys.
	 */
	public function test_narrow_field_selection_returns_only_the_requested_media_keys() {
		$item = $this->get_data( $this->media_url, array( '_fields' => 'id,title' ) );

		$actual = array_keys( $item[0] );
		sort( $actual );

		$this->assertSame( array( 'id', 'title' ), $actual );
	}

	/**
	 * An unselected `display_name` must not be resolved.
	 */
	public function test_media_display_name_is_not_resolved_when_it_is_not_selected() {
		$this->assertSame(
			0,
			$this->count_hook( 'bp_core_get_user_displayname', $this->media_url, array( '_fields' => 'id,title' ) )
		);
	}

	/**
	 * ...but a selected one still is.
	 */
	public function test_media_display_name_is_resolved_when_it_is_selected() {
		$this->assertGreaterThan(
			0,
			$this->count_hook( 'bp_core_get_user_displayname', $this->media_url, array( '_fields' => 'id,display_name' ) )
		);
	}

	/**
	 * The same contract for documents.
	 */
	public function test_narrow_field_selection_returns_only_the_requested_document_keys() {
		$item = $this->get_data( $this->document_url . '/' . $this->document_id, array( '_fields' => 'id,title' ) );

		$actual = array_keys( $item );
		sort( $actual );

		$this->assertSame( array( 'id', 'title' ), $actual );
	}

	/**
	 * An unselected document `display_name` must not be resolved.
	 */
	public function test_document_display_name_is_not_resolved_when_it_is_not_selected() {
		$this->assertSame(
			0,
			$this->count_hook( 'bp_core_get_user_displayname', $this->document_url . '/' . $this->document_id, array( '_fields' => 'id,title' ) )
		);
	}

	/**
	 * Nested attachments are returned whole unless `attachment_fields` says
	 * otherwise: the activity's own `_fields` cannot reach inside a list.
	 */
	public function test_nested_media_is_whole_without_attachment_fields() {
		$activity = $this->get_activity();

		$this->assertNotEmpty( $activity['bp_media_ids'] );

		$media = $activity['bp_media_ids'][0];

		$this->assertArrayHasKey( 'title', $media );
		$this->assertArrayHasKey( 'attachment_data', $media );
		$this->assertArrayHasKey( 'display_name', $media );
	}

	/**
	 * ...and `attachment_fields` narrows them.
	 */
	public function test_attachment_fields_narrows_nested_media() {
		$activity = $this->get_activity( array( 'attachment_fields' => 'id,title' ) );

		$this->assertNotEmpty( $activity['bp_media_ids'] );

		$media = $activity['bp_media_ids'][0];

		$this->assertArrayHasKey( 'id', $media );
		$this->assertArrayHasKey( 'title', $media );
		$this->assertArrayNotHasKey( 'attachment_data', $media );
		$this->assertArrayNotHasKey( 'display_name', $media );
	}

	/**
	 * It reaches nested documents too.
	 */
	public function test_attachment_fields_narrows_nested_documents() {
		$activity = $this->get_activity( array( 'attachment_fields' => 'id,title' ) );

		$this->assertNotEmpty( $activity['bp_documents'] );

		$document = $activity['bp_documents'][0];

		$this->assertArrayHasKey( 'title', $document );
		$this->assertArrayNotHasKey( 'msg_preview', $document );
		$this->assertArrayNotHasKey( 'display_name', $document );
	}

	/**
	 * It applies to the attachments only, never to their parent activity.
	 */
	public function test_attachment_fields_leaves_the_parent_activity_whole() {
		$activity = $this->get_activity( array( 'attachment_fields' => 'id' ) );

		$this->assertArrayHasKey( 'content', $activity );
		$this->assertArrayHasKey( 'activity_data', $activity );
	}

	/**
	 * Narrowing the nested attachments has to cost less, not merely return
	 * less: the guards sit in front of the preview URLs, the download links
	 * and the per-user permission checks.
	 *
	 * `bp_core_get_user_displayname` is deliberately not used as the probe
	 * here -- it also fires while the activity action is generated, well
	 * before any attachment is prepared.
	 */
	public function test_attachment_fields_reduces_the_work_behind_nested_attachments() {
		// Warm anything memoised outside the object cache first.
		$this->get_activity();

		$full   = $this->count_activity_queries( array() );
		$narrow = $this->count_activity_queries( array( 'attachment_fields' => 'id,title' ) );

		$this->assertLessThan(
			$full,
			$narrow,
			sprintf( 'No selection cost %d queries, attachment_fields cost %d.', $full, $narrow )
		);
	}

	/**
	 * Count the queries one activity request costs, from a cold cache.
	 *
	 * @param array $params Request parameters.
	 *
	 * @return int
	 */
	protected function count_activity_queries( $params ) {
		global $wpdb;

		wp_cache_flush();

		$before = $wpdb->num_queries;
		$this->get_activity( $params );

		return $wpdb->num_queries - $before;
	}

	/**
	 * `DELETE` answers with an envelope, so its `_fields` names envelope keys.
	 * The media nested under `previous` must still be built in full.
	 */
	public function test_delete_returns_a_whole_previous_media() {
		$request = new WP_REST_Request( 'DELETE', $this->media_url . '/' . $this->media_id );
		$request->set_param( 'context', 'edit' );
		$request->set_param( '_fields', 'deleted,previous' );

		$response = $this->server->dispatch( $request );
		$response = apply_filters( 'rest_post_dispatch', rest_ensure_response( $response ), $this->server, $request );
		$data     = $response->get_data();

		$this->assertArrayHasKey( 'previous', $data );
		$this->assertArrayHasKey( 'title', $data['previous'] );
		$this->assertArrayHasKey( 'attachment_data', $data['previous'] );
	}
}
