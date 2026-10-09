<?php
/**
 * Private Website on guest AJAX tests.
 *
 * Private Website was only enforced when a page template rendered, so logged-out visitors could
 * still load members, groups, activity and media through the `wp_ajax_nopriv_` handlers.
 *
 * @group core
 * @group private-network
 */
class BB_Tests_Core_Private_Network_Guest_Ajax extends BP_UnitTestCase {

	/**
	 * Public Website Content used by the current test.
	 *
	 * @var string
	 */
	protected $public_content = '';

	/**
	 * POST values replaced by a test.
	 *
	 * @var array
	 */
	protected $old_post = array();

	/**
	 * Whether the AJAX handler ran.
	 *
	 * @var bool
	 */
	protected $handler_reached = false;

	public function set_up() {
		parent::set_up();

		$this->old_post       = $_POST;
		$this->public_content = '';

		$this->set_current_user( 0 );

		// BuddyBoss directories need pretty permalinks.
		$this->set_permalink_structure( '/%postname%/' );

		add_filter( 'bp_enable_private_network_public_content', array( $this, 'public_content' ) );
		add_filter( 'wp_doing_ajax', '__return_true' );
		add_filter( 'wp_die_ajax_handler', array( $this, 'die_handler' ) );
	}

	public function tear_down() {
		$_POST = $this->old_post;

		remove_filter( 'bp_enable_private_network_public_content', array( $this, 'public_content' ) );
		remove_filter( 'wp_doing_ajax', '__return_true' );
		remove_filter( 'wp_die_ajax_handler', array( $this, 'die_handler' ) );
		remove_filter( 'bp_enable_private_network', '__return_false' );
		remove_filter( 'bp_enable_private_network', '__return_true' );
		remove_all_filters( 'bb_private_network_guest_ajax_allowed' );

		foreach ( array_keys( bb_private_network_guest_ajax_actions() ) as $action ) {
			remove_action( 'wp_ajax_nopriv_' . $action, 'bb_private_network_restrict_guest_ajax', 1 );
		}

		parent::tear_down();
	}

	/**
	 * @return string
	 */
	public function public_content() {
		return $this->public_content;
	}

	/**
	 * @return callable
	 */
	public function die_handler() {
		return array( $this, 'throw_die' );
	}

	/**
	 * Turn wp_send_json_error() into an exception the test can assert on.
	 *
	 * @param string $message Die message.
	 *
	 * @throws WPDieException Always.
	 */
	public function throw_die( $message ) {
		throw new WPDieException( (string) $message );
	}

	/**
	 * Record that the AJAX handler ran.
	 */
	public function mark_handler_reached() {
		$this->handler_reached = true;
	}

	/**
	 * Turn Private Website on.
	 */
	protected function private_website_on() {
		add_filter( 'bp_enable_private_network', '__return_false' );
	}

	/**
	 * Run the Private Website check the way admin-ajax.php dispatches a guest action.
	 *
	 * @param string $action AJAX action.
	 * @param array  $post   POST values.
	 *
	 * @return bool True when the action was allowed to reach its handler.
	 */
	protected function guest_ajax_allowed( $action, $post = array() ) {
		$_POST = $post;

		// Swap the real handler for a marker; set_up() backed up the hooks.
		remove_all_actions( 'wp_ajax_nopriv_' . $action );
		$this->handler_reached = false;
		add_action( 'wp_ajax_nopriv_' . $action, array( $this, 'mark_handler_reached' ) );

		bb_private_network_register_guest_ajax_restriction();

		ob_start();
		try {
			do_action( 'wp_ajax_nopriv_' . $action );
		} catch ( WPDieException $e ) {
			unset( $e );
		}
		$output  = ob_get_clean();
		$allowed = $this->handler_reached;

		if ( ! $allowed ) {
			$response = json_decode( $output, true );
			$this->assertFalse( $response['success'] );
			$this->assertSame( 'Please login to access this website.', $response['data']['message'] );
		}

		return $allowed;
	}

	/**
	 * Directory page URL of a component.
	 *
	 * @param string $component Component.
	 *
	 * @return string
	 */
	protected function directory_url( $component ) {
		$page_ids = bp_core_get_directory_page_ids();

		return get_permalink( $page_ids[ $component ] );
	}

	/**
	 * Put a component's directory page in Public Website Content.
	 *
	 * @param string $component Component.
	 */
	protected function allowlist_directory( $component ) {
		$this->public_content = '/' . get_post_field( 'post_name', bp_core_get_directory_page_ids()[ $component ] ) . '/';
	}

	public function test_restriction_is_wired_to_admin_ajax_and_heartbeat() {
		$this->assertSame( 1, has_action( 'admin_init', 'bb_private_network_register_guest_ajax_restriction' ) );
		$this->assertSame( 999, has_filter( 'heartbeat_nopriv_received', 'bb_private_network_restrict_guest_heartbeat' ) );
	}

	public function test_private_website_off_allows_guest_ajax() {
		add_filter( 'bp_enable_private_network', '__return_true' );

		$this->assertTrue( $this->guest_ajax_allowed( 'members_filter', array( 'object' => 'members' ) ) );
		$this->assertTrue( $this->guest_ajax_allowed( 'get_single_activity_content', array( 'id' => 1 ) ) );
		$this->assertTrue( $this->guest_ajax_allowed( 'bp_search_ajax' ) );
	}

	/**
	 * @dataProvider data_restricted_actions
	 *
	 * @param string $action AJAX action.
	 * @param array  $post   POST values.
	 */
	public function test_private_website_on_blocks_guest_ajax( $action, $post ) {
		$this->private_website_on();

		$this->assertFalse( $this->guest_ajax_allowed( $action, $post ) );
	}

	public function data_restricted_actions() {
		return array(
			'members directory'   => array( 'members_filter', array( 'object' => 'members' ) ),
			'groups directory'    => array( 'groups_filter', array( 'object' => 'groups' ) ),
			'activity feed'       => array( 'activity_filter', array( 'object' => 'activity' ) ),
			'photos'              => array( 'media_filter', array( 'object' => 'media' ) ),
			'documents'           => array( 'document_filter', array( 'object' => 'document' ) ),
			'videos'              => array( 'video_filter', array( 'object' => 'video' ) ),
			'loader, no object'   => array( 'members_filter', array() ),
			'activity read more'  => array( 'get_single_activity_content', array( 'id' => 1 ) ),
			'more comments'       => array( 'activity_loadmore_comments', array( 'activity_id' => 1 ) ),
			'ReadyLaunch comments' => array( 'bb_rl_activity_loadmore_comments', array( 'activity_id' => 1 ) ),
			'photo theatre'       => array( 'media_get_activity', array( 'id' => 1 ) ),
			'photo albums'        => array( 'media_albums_loader', array() ),
			'document theatre'    => array( 'document_get_document_description', array( 'id' => 1 ) ),
			'video theatre'       => array( 'video_get_video_description', array( 'id' => 1 ) ),
			'reactions'           => array( 'bb_get_reactions', array( 'item_id' => 1 ) ),
			'search'              => array( 'bp_search_ajax', array() ),
			'members widget'      => array( 'widget_members', array() ),
			'blocked members'     => array( 'moderation_filter', array() ),
		);
	}

	public function test_registration_helpers_stay_public() {
		$this->private_website_on();

		$this->assertTrue( $this->guest_ajax_allowed( 'check_email' ) );
		$this->assertTrue( $this->guest_ajax_allowed( 'xprofile_get_field' ) );
	}

	public function test_logged_in_member_is_not_restricted() {
		$this->private_website_on();
		$this->set_current_user( self::factory()->user->create() );

		bb_private_network_register_guest_ajax_restriction();

		$this->assertFalse( has_action( 'wp_ajax_nopriv_members_filter', 'bb_private_network_restrict_guest_ajax' ) );
	}

	public function test_public_directory_unlocks_its_own_list() {
		$this->private_website_on();
		$this->public_content = '/' . get_post_field( 'post_name', bp_core_get_directory_page_ids()['members'] ) . '/';

		$this->assertTrue( $this->guest_ajax_allowed( 'members_filter', array( 'object' => 'members' ) ) );
		$this->assertTrue( $this->guest_ajax_allowed( 'widget_members' ) );
	}

	public function test_public_directory_does_not_unlock_other_components() {
		$this->private_website_on();
		$this->public_content = '/' . get_post_field( 'post_name', bp_core_get_directory_page_ids()['members'] ) . '/';

		// The shared loader renders the posted object, whatever the action name says.
		$this->assertFalse( $this->guest_ajax_allowed( 'members_filter', array( 'object' => 'document' ) ) );
		$this->assertFalse( $this->guest_ajax_allowed( 'members_filter', array( 'object' => 'activity' ) ) );
		$this->assertFalse( $this->guest_ajax_allowed( 'groups_filter', array( 'object' => 'groups' ) ) );
		$this->assertFalse( $this->guest_ajax_allowed( 'get_single_activity_content', array( 'id' => 1 ) ) );
		$this->assertFalse( $this->guest_ajax_allowed( 'bp_search_ajax' ) );
	}

	public function test_public_full_url_unlocks_its_list() {
		$this->private_website_on();
		$this->public_content = $this->directory_url( 'groups' );

		$this->assertTrue( $this->guest_ajax_allowed( 'groups_filter', array( 'object' => 'groups' ) ) );
		$this->assertFalse( $this->guest_ajax_allowed( 'groups_filter', array( 'object' => 'members' ) ) );
	}

	public function test_public_profile_does_not_unlock_directory_lists() {
		$this->private_website_on();
		$this->public_content = trailingslashit( $this->directory_url( 'members' ) ) . 'member5/';

		$this->assertFalse( $this->guest_ajax_allowed( 'members_filter', array( 'object' => 'members' ) ) );
		$this->assertFalse( $this->guest_ajax_allowed( 'activity_filter', array( 'object' => 'activity' ) ) );
	}

	/**
	 * @dataProvider data_loader_templates
	 *
	 * @param string $template Loader template.
	 */
	public function test_public_directory_never_unlocks_loader_templates( $template ) {
		$this->private_website_on();
		$this->allowlist_directory( 'members' );

		$this->assertFalse( $this->guest_ajax_allowed( 'members_filter', array( 'object' => 'members', 'template' => $template ) ) );
		$this->assertTrue( $this->guest_ajax_allowed( 'members_filter', array( 'object' => 'members', 'template' => '' ) ) );
	}

	public function data_loader_templates() {
		return array(
			'group members'        => array( 'group_members' ),
			'manage group members' => array( 'manage_group_members' ),
			'group requests'       => array( 'group_requests' ),
			'notifications'        => array( 'member_notifications' ),
			'connections'          => array( 'friends' ),
		);
	}

	public function test_loader_object_sent_as_array_is_blocked() {
		$this->private_website_on();
		$this->allowlist_directory( 'members' );

		$this->assertFalse( $this->guest_ajax_allowed( 'members_filter', array( 'object' => array( 'members' ) ) ) );
	}

	public function test_public_news_feed_unlocks_activity_items_and_media_theatre() {
		$this->private_website_on();
		$this->allowlist_directory( 'activity' );

		$activity_id = self::factory()->activity->create();

		$this->assertTrue( $this->guest_ajax_allowed( 'get_single_activity_content', array( 'id' => $activity_id ) ) );
		$this->assertTrue( $this->guest_ajax_allowed( 'activity_loadmore_comments', array( 'activity_id' => $activity_id ) ) );
		$this->assertTrue( $this->guest_ajax_allowed( 'bb_get_reactions', array( 'item_id' => $activity_id ) ) );
		$this->assertTrue( $this->guest_ajax_allowed( 'media_get_activity', array( 'id' => $activity_id ) ) );
		$this->assertTrue( $this->guest_ajax_allowed( 'media_get_media_description', array( 'id' => 1 ) ) );
		$this->assertTrue( $this->guest_ajax_allowed( 'document_get_document_description', array( 'id' => 1 ) ) );
		$this->assertTrue( $this->guest_ajax_allowed( 'video_get_video_description', array( 'id' => 1 ) ) );
		$this->assertFalse( $this->guest_ajax_allowed( 'media_albums_loader' ) );
		$this->assertFalse( $this->guest_ajax_allowed( 'members_filter', array( 'object' => 'members' ) ) );
	}

	/**
	 * @dataProvider data_media_directories
	 *
	 * @param string $component Media component.
	 */
	public function test_public_media_directory_unlocks_only_its_own_activities( $component ) {
		$this->private_website_on();
		$this->allowlist_directory( $component );

		$text_activity  = self::factory()->activity->create();
		$media_activity = self::factory()->activity->create();
		bp_activity_update_meta( $media_activity, 'bp_' . $component . '_ids', '1' );
		$child_activity = self::factory()->activity->create();
		bp_activity_update_meta( $child_activity, 'bp_' . $component . '_activity', '1' );

		$action = $component . '_get_activity';

		$this->assertTrue( $this->guest_ajax_allowed( $action, array( 'id' => $media_activity ) ) );
		$this->assertTrue( $this->guest_ajax_allowed( $action, array( 'id' => $child_activity ) ) );
		$this->assertFalse( $this->guest_ajax_allowed( $action, array( 'id' => $text_activity ) ) );
		$this->assertFalse( $this->guest_ajax_allowed( $action, array() ) );
		$this->assertFalse( $this->guest_ajax_allowed( 'get_single_activity_content', array( 'id' => $text_activity ) ) );
		$this->assertFalse( $this->guest_ajax_allowed( 'bb_get_reactions', array( 'item_id' => $text_activity ) ) );
	}

	public function data_media_directories() {
		return array(
			'photos'    => array( 'media' ),
			'documents' => array( 'document' ),
			'videos'    => array( 'video' ),
		);
	}

	public function test_public_photos_unlock_photo_lists_and_theatre() {
		$this->private_website_on();
		$this->allowlist_directory( 'media' );

		$this->assertTrue( $this->guest_ajax_allowed( 'media_filter', array( 'object' => 'media' ) ) );
		$this->assertTrue( $this->guest_ajax_allowed( 'media_albums_loader' ) );
		$this->assertTrue( $this->guest_ajax_allowed( 'media_get_media_description', array( 'id' => 1 ) ) );
		$this->assertFalse( $this->guest_ajax_allowed( 'document_get_document_description', array( 'id' => 1 ) ) );
		$this->assertFalse( $this->guest_ajax_allowed( 'video_filter', array( 'object' => 'video' ) ) );
	}

	/**
	 * Guest Heartbeat response with every key BuddyBoss adds for logged-out visitors.
	 *
	 * @return array
	 */
	protected function guest_heartbeat_response() {
		return array(
			'bp_activity_newest_activities'            => '<li class="activity-item"></li>',
			'buddyboss_members_widget_active'          => '<li class="vcard"></li>',
			'boss_recently_active_widget'              => '<div class="item-avatar"></div>',
			'boss_whos_online_widget'                  => '<div class="item-avatar"></div>',
			'boss_whos_online_widget_total'            => 1,
			'boss_whos_online_widget_connection'       => '',
			'boss_whos_online_widget_total_connection' => 0,
			'users_presence'                           => array( array( 'id' => 1 ) ),
			'server_time'                              => 123,
		);
	}

	public function test_guest_heartbeat_strips_community_data_when_private() {
		$this->private_website_on();

		$response = apply_filters( 'heartbeat_nopriv_received', $this->guest_heartbeat_response(), array(), 'front' );

		$this->assertSame( array( 'server_time' => 123 ), $response );
	}

	public function test_guest_heartbeat_keeps_data_of_public_directories() {
		$this->private_website_on();
		$this->allowlist_directory( 'activity' );

		$response = bb_private_network_restrict_guest_heartbeat( $this->guest_heartbeat_response() );

		$this->assertArrayHasKey( 'bp_activity_newest_activities', $response );
		$this->assertArrayNotHasKey( 'buddyboss_members_widget_active', $response );
		$this->assertArrayNotHasKey( 'users_presence', $response );

		$this->allowlist_directory( 'members' );

		$response = bb_private_network_restrict_guest_heartbeat( $this->guest_heartbeat_response() );

		$this->assertArrayNotHasKey( 'bp_activity_newest_activities', $response );
		$this->assertArrayHasKey( 'buddyboss_members_widget_active', $response );
		$this->assertArrayHasKey( 'users_presence', $response );
	}

	public function test_guest_heartbeat_unchanged_when_public_or_logged_in() {
		add_filter( 'bp_enable_private_network', '__return_true' );
		$this->assertSame( $this->guest_heartbeat_response(), bb_private_network_restrict_guest_heartbeat( $this->guest_heartbeat_response() ) );

		remove_filter( 'bp_enable_private_network', '__return_true' );
		$this->private_website_on();
		$this->set_current_user( self::factory()->user->create() );
		$this->assertSame( $this->guest_heartbeat_response(), bb_private_network_restrict_guest_heartbeat( $this->guest_heartbeat_response() ) );
	}

	public function test_allowed_filter_can_override() {
		$this->private_website_on();
		add_filter( 'bb_private_network_guest_ajax_allowed', '__return_true' );

		$this->assertTrue( $this->guest_ajax_allowed( 'members_filter', array( 'object' => 'members' ) ) );
	}

	/**
	 * @dataProvider data_public_url
	 *
	 * @param string $content Public Website Content.
	 * @param string $path    Path of the URL to check.
	 * @param bool   $public  Expected result.
	 */
	public function test_is_private_network_public_url( $content, $path, $public ) {
		$this->public_content = str_replace( '{home}', home_url(), $content );

		$this->assertSame( $public, bb_is_private_network_public_url( home_url( $path ) ) );
	}

	public function data_public_url() {
		return array(
			'empty setting'           => array( '', '/members/', false ),
			'relative entry'          => array( '/members/', '/members/', true ),
			'relative, no slashes'    => array( 'members', '/members/', true ),
			'relative, sub-page'      => array( '/members/', '/members/member5/', true ),
			'relative, other page'    => array( '/members/', '/groups/', false ),
			'relative, deeper entry'  => array( '/members/member5/', '/members/', false ),
			'one of several entries'  => array( "/groups/\n/members/", '/members/', true ),
			'full URL'                => array( '{home}/members/', '/members/', true ),
			'full URL, other page'    => array( '{home}/members/', '/groups/', false ),
			'full URL, not sub-pages' => array( '{home}/members/', '/members/member5/', false ),
		);
	}
}
