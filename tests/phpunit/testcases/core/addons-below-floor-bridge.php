<?php
/**
 * Tests for the bridge applied while BuddyBoss Add-ons is below the required version.
 *
 * @package BuddyBoss\Tests
 * @since   BuddyBoss [BBVERSION]
 */

/**
 * Add-ons builds below the floor: Platform applies the Private Website download
 * guard (PROD-10601) and the REST document-folder rules (PROD-10531) itself.
 *
 * The REST tests need Add-ons' document folders; they skip without them.
 *
 * @package BuddyBoss
 * @group core
 * @group addons_below_floor_bridge
 */
class BP_Tests_Addons_Below_Floor_Bridge extends BP_UnitTestCase {

	/**
	 * Download types seen by the Private Website guard.
	 *
	 * @var string[]
	 */
	protected $guarded_types = array();

	/**
	 * Saved $_GET.
	 *
	 * @var array
	 */
	protected $saved_get = array();

	/**
	 * Set up.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	public function setUp(): void {
		parent::setUp();

		$this->saved_get     = $_GET;
		$this->guarded_types = array();

		// Record the guard call and let it pass, so the test is not ended by exit().
		add_filter( 'bb_is_private_network_download_restricted', array( $this, 'record_guard' ), 10, 2 );
	}

	/**
	 * Tear down.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	public function tearDown(): void {
		$_GET = $this->saved_get;

		remove_filter( 'bb_is_private_network_download_restricted', array( $this, 'record_guard' ), 10 );
		remove_filter( 'bb_addons_below_floor_bridge_is_active', '__return_true' );
		remove_filter( 'bp_is_active', array( $this, 'force_components_active' ), 10 );
		remove_filter( 'bp_enable_private_network', '__return_false' );
		remove_filter( 'bp_is_profile_document_support_enabled', '__return_true' );

		parent::tearDown();
	}

	/**
	 * Record the download type and allow the download.
	 *
	 * @param bool   $restricted Whether the download is blocked.
	 * @param string $type       Download type.
	 * @return bool
	 */
	public function record_guard( $restricted, $type ) {
		$this->guarded_types[] = $type;

		return false;
	}

	/**
	 * Force the components the folder rules need.
	 *
	 * @param bool   $is_active Whether the component is active.
	 * @param string $component Component ID.
	 * @return bool
	 */
	public function force_components_active( $is_active, $component ) {
		return in_array( $component, array( 'document', 'groups' ), true ) ? true : $is_active;
	}

	/**
	 * Logged out on a Private Website.
	 */
	protected function guest_on_private_site() {
		self::set_current_user( 0 );

		// bp_enable_private_network() returns false when Private Website is on.
		add_filter( 'bp_enable_private_network', '__return_false' );
	}

	/**
	 * The bridge is off unless an active Add-ons build is below the floor.
	 */
	public function test_bridge_is_inactive_without_an_old_addons() {
		if ( defined( 'BB_ADDONS_PLUGIN_VERSION' ) && bb_is_addons_below_required_version( (string) BB_ADDONS_PLUGIN_VERSION ) ) {
			$this->markTestSkipped( 'An Add-ons build below the floor is loaded.' );
		}

		$this->assertFalse( bb_addons_below_floor_bridge_is_active() );
		$this->assertSame( 9, has_action( 'init', 'bb_addons_below_floor_bridge_restrict_downloads' ) );
	}

	/**
	 * Guests hit the guard for document, folder and video download links.
	 */
	public function test_guest_downloads_are_guarded_when_active() {
		add_filter( 'bb_addons_below_floor_bridge_is_active', '__return_true' );
		$this->guest_on_private_site();

		$_GET = array(
			'attachment'             => '10',
			'download_document_file' => '1',
			'document_file'          => '20',
			'document_type'          => 'document',
		);
		bb_addons_below_floor_bridge_restrict_downloads();

		$_GET['document_type'] = 'folder';
		bb_addons_below_floor_bridge_restrict_downloads();

		$_GET = array(
			'attachment_id'       => '10',
			'download_video_file' => '1',
			'video_file'          => '20',
			'video_type'          => 'video',
		);
		bb_addons_below_floor_bridge_restrict_downloads();

		$this->assertSame( array( 'document', 'folder', 'video' ), $this->guarded_types );
	}

	/**
	 * Invalid document args are left to the Add-ons handler, which rejects them.
	 */
	public function test_invalid_document_args_are_left_to_the_handler() {
		add_filter( 'bb_addons_below_floor_bridge_is_active', '__return_true' );
		$this->guest_on_private_site();

		$_GET = array(
			'attachment'             => '0',
			'download_document_file' => '1',
			'document_file'          => '20',
			'document_type'          => 'document',
		);
		bb_addons_below_floor_bridge_restrict_downloads();

		$_GET['attachment']    = '10';
		$_GET['document_type'] = 'photo';
		bb_addons_below_floor_bridge_restrict_downloads();

		$this->assertSame( array(), $this->guarded_types );
	}

	/**
	 * Nothing runs when the bridge is off, for members, or without download args.
	 */
	public function test_guard_is_skipped_when_not_needed() {
		$this->guest_on_private_site();
		$_GET = array(
			'attachment_id'       => '10',
			'download_video_file' => '1',
			'video_file'          => '20',
			'video_type'          => 'video',
		);

		// Bridge off (current or no Add-ons).
		add_filter( 'bb_addons_below_floor_bridge_is_active', '__return_false' );
		bb_addons_below_floor_bridge_restrict_downloads();
		remove_filter( 'bb_addons_below_floor_bridge_is_active', '__return_false' );

		add_filter( 'bb_addons_below_floor_bridge_is_active', '__return_true' );

		// Logged in.
		self::set_current_user( self::factory()->user->create() );
		bb_addons_below_floor_bridge_restrict_downloads();

		// No download args.
		self::set_current_user( 0 );
		$_GET = array( 'video_type' => 'video' );
		bb_addons_below_floor_bridge_restrict_downloads();

		$this->assertSame( array(), $this->guarded_types );
	}

	/**
	 * Set up a folder owner with document uploads allowed, or skip without Add-ons folders.
	 *
	 * @return int User ID.
	 */
	protected function set_up_folder_owner() {
		if ( ! function_exists( 'bp_folder_add' ) || ! class_exists( 'BP_Document_Folder' ) ) {
			$this->markTestSkipped( 'Document folders are provided by BuddyBoss Add-ons, which is not loaded.' );
		}

		add_filter( 'bp_is_active', array( $this, 'force_components_active' ), 10, 2 );
		add_filter( 'bp_is_profile_document_support_enabled', '__return_true' );
		add_filter( 'bb_addons_below_floor_bridge_is_active', '__return_true' );

		$user_id = self::factory()->user->create();
		self::set_current_user( $user_id );

		return $user_id;
	}

	/**
	 * Build a REST request for a folder route.
	 *
	 * @param string $method HTTP method.
	 * @param string $path   Path after the namespace.
	 * @param array  $params Request params.
	 * @return WP_REST_Request
	 */
	protected function folder_request( $method, $path, $params ) {
		$request = new WP_REST_Request( $method, '/' . bp_rest_namespace() . '/' . bp_rest_version() . $path );
		foreach ( $params as $key => $value ) {
			$request->set_param( $key, $value );
		}

		return $request;
	}

	/**
	 * Update: a group_id other than the folder's is rejected; the document route is untouched.
	 */
	public function test_folder_update_rejects_moving_into_another_group() {
		$user_id   = $this->set_up_folder_owner();
		$folder_id = bp_folder_add(
			array(
				'user_id' => $user_id,
				'title'   => 'Root',
				'privacy' => 'public',
			)
		);
		$group_id  = self::factory()->group->create( array( 'creator_id' => $user_id ) );

		$moved = $this->folder_request(
			'PATCH',
			'/document/folder/' . $folder_id,
			array(
				'id'       => $folder_id,
				'group_id' => $group_id,
			)
		);
		$this->assertWPError( bb_addons_below_floor_bridge_folder_update_permissions( true, $moved ) );
		$this->assertSame( 'bp_rest_invalid_permission', bb_addons_below_floor_bridge_folder_update_permissions( true, $moved )->get_error_code() );

		$renamed = $this->folder_request(
			'PATCH',
			'/document/folder/' . $folder_id,
			array(
				'id'    => $folder_id,
				'title' => 'New',
			)
		);
		$this->assertTrue( bb_addons_below_floor_bridge_folder_update_permissions( true, $renamed ) );

		$document = $this->folder_request(
			'PATCH',
			'/document/' . $folder_id,
			array(
				'id'       => $folder_id,
				'group_id' => $group_id,
			)
		);
		$this->assertTrue( bb_addons_below_floor_bridge_folder_update_permissions( true, $document ) );

		// Bridge off: the endpoint's own result is returned.
		remove_filter( 'bb_addons_below_floor_bridge_is_active', '__return_true' );
		add_filter( 'bb_addons_below_floor_bridge_is_active', '__return_false' );
		$this->assertTrue( bb_addons_below_floor_bridge_folder_update_permissions( true, $moved ) );
		remove_filter( 'bb_addons_below_floor_bridge_is_active', '__return_false' );
	}

	/**
	 * Update args: only a personal root folder's privacy changes, and the group never comes from the request.
	 */
	public function test_folder_update_args_follow_the_privacy_rules() {
		$user_id  = $this->set_up_folder_owner();
		$root_id  = bp_folder_add(
			array(
				'user_id' => $user_id,
				'title'   => 'Root',
				'privacy' => 'public',
			)
		);
		$child_id = bp_folder_add(
			array(
				'user_id' => $user_id,
				'title'   => 'Child',
				'privacy' => 'public',
				'parent'  => $root_id,
			)
		);

		// Child folder: requested privacy and group are dropped (1.2.3-shaped args in).
		$request = $this->folder_request(
			'PATCH',
			'/document/folder/' . $child_id,
			array(
				'id'       => $child_id,
				'privacy'  => 'onlyme',
				'group_id' => 99,
			)
		);
		$args    = bb_addons_below_floor_bridge_folder_update_args(
			array(
				'id'       => $child_id,
				'group_id' => 99,
				'parent'   => $root_id,
				'privacy'  => 'grouponly',
			),
			$request
		);
		$this->assertSame( 0, (int) $args['group_id'] );
		$this->assertSame( 'public', $args['privacy'] );
		$this->assertFalse( isset( $request['privacy'] ), 'The 1.2.3 privacy cascade must be skipped.' );

		// Personal root folder: the change is kept and cascades.
		$request = $this->folder_request(
			'PATCH',
			'/document/folder/' . $root_id,
			array(
				'id'      => $root_id,
				'privacy' => 'loggedin',
			)
		);
		$args    = bb_addons_below_floor_bridge_folder_update_args(
			array(
				'id'       => $root_id,
				'group_id' => 0,
				'parent'   => 0,
				'privacy'  => 'loggedin',
			),
			$request
		);
		$this->assertSame( 'loggedin', $args['privacy'] );
		$this->assertTrue( isset( $request['privacy'] ) );

		// Root folder to 'grouponly' is refused.
		$request = $this->folder_request(
			'PATCH',
			'/document/folder/' . $root_id,
			array(
				'id'      => $root_id,
				'privacy' => 'grouponly',
			)
		);
		$args    = bb_addons_below_floor_bridge_folder_update_args(
			array(
				'id'       => $root_id,
				'group_id' => 0,
				'parent'   => 0,
				'privacy'  => 'grouponly',
			),
			$request
		);
		$this->assertSame( 'public', $args['privacy'] );
		$this->assertFalse( isset( $request['privacy'] ) );
	}

	/**
	 * Create: a group_id other than the parent folder's is rejected.
	 */
	public function test_folder_create_rejects_a_group_other_than_the_parents() {
		$user_id   = $this->set_up_folder_owner();
		$parent_id = bp_folder_add(
			array(
				'user_id' => $user_id,
				'title'   => 'Root',
				'privacy' => 'public',
			)
		);

		$request = $this->folder_request(
			'POST',
			'/document/folder',
			array(
				'parent'   => $parent_id,
				'group_id' => 99,
				'title'    => 'New',
			)
		);
		$this->assertWPError( bb_addons_below_floor_bridge_folder_create_permissions( true, $request ) );

		$request = $this->folder_request(
			'POST',
			'/document/folder',
			array(
				'parent' => $parent_id,
				'title'  => 'New',
			)
		);
		$this->assertTrue( bb_addons_below_floor_bridge_folder_create_permissions( true, $request ) );
	}
}
