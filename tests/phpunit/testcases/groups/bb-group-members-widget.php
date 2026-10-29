<?php
/**
 * ReadyLaunch group members sidebar widget (PROD-10670 S4).
 *
 * - The tab AJAX must not list the members of a private/hidden group to someone
 *   who cannot see that group (the nonce is shared by every guest).
 * - The first render and the tab AJAX use the same query args (organizers and
 *   moderators are included on every tab).
 * - The "Active" tab lists recently active members instead of an `online` window
 *   that is shorter than the presence DB write interval.
 * - An empty result keeps the tabs and returns an empty state, never null HTML.
 *
 * @group groups
 * @group bb_group_members_widget
 */
class BB_Tests_Group_Members_Widget extends BP_UnitTestCase {

	/**
	 * Widget under test.
	 *
	 * @var BB_Group_Members_Widget
	 */
	protected $widget;

	public function setUp(): void {
		parent::setUp();

		if ( ! class_exists( 'BB_Group_Members_Widget' ) ) {
			require_once buddypress()->plugin_dir . 'bp-core/classes/class-bb-group-members-widget.php';
		}

		$this->widget = new BB_Group_Members_Widget();

		add_filter( 'wp_doing_ajax', '__return_true' );
		add_filter( 'wp_die_ajax_handler', array( $this, 'get_die_handler' ), 1 );
	}

	public function tearDown(): void {
		unset( $_POST['_wpnonce'], $_POST['filter'], $_POST['max'], $_POST['group_id'], $_REQUEST['_wpnonce'] );

		remove_filter( 'wp_doing_ajax', '__return_true' );
		remove_filter( 'wp_die_ajax_handler', array( $this, 'get_die_handler' ), 1 );

		parent::tearDown();
	}

	/**
	 * Die handler factory.
	 *
	 * @return callable
	 */
	public function get_die_handler() {
		return array( $this, 'die_handler' );
	}

	/**
	 * Throw instead of exiting.
	 *
	 * @param string $message Die message.
	 *
	 * @throws WPAjaxDieContinueException Always.
	 */
	public function die_handler( $message ) {
		throw new WPAjaxDieContinueException( (string) $message );
	}

	/**
	 * Call the widget AJAX handler as the current user.
	 *
	 * @param int    $group_id Group ID.
	 * @param string $filter   Tab filter.
	 *
	 * @return array Decoded JSON response.
	 */
	protected function call_ajax( $group_id, $filter = 'active' ) {
		$_POST['_wpnonce']    = wp_create_nonce( 'groups_widget_groups_members_list' );
		$_REQUEST['_wpnonce'] = $_POST['_wpnonce'];
		$_POST['group_id']    = (string) $group_id;
		$_POST['filter']      = $filter;
		$_POST['max']         = '5';

		ob_start();
		try {
			$this->widget->groups_ajax_widget_groups_members_list();
		} catch ( WPAjaxDieContinueException $e ) {
			unset( $e );
		}
		$output = ob_get_clean();

		return (array) json_decode( $output, true );
	}

	/**
	 * Create a group with an organizer and one regular member.
	 *
	 * @param string $status        Group status.
	 * @param string $last_activity Last activity for both users (mysql GMT).
	 *
	 * @return array { group_id, admin_id, member_id }
	 */
	protected function make_group( $status, $last_activity = '' ) {
		$args = array();
		if ( $last_activity ) {
			$args['last_activity'] = $last_activity;
		}

		$admin_id  = self::factory()->user->create( $args );
		$member_id = self::factory()->user->create( $args );
		$group_id  = self::factory()->group->create(
			array(
				'creator_id' => $admin_id,
				'status'     => $status,
			)
		);
		self::add_user_to_group( $member_id, $group_id );

		return compact( 'group_id', 'admin_id', 'member_id' );
	}

	/**
	 * Whether the HTML lists the given user.
	 *
	 * @param string $html    HTML.
	 * @param int    $user_id User ID.
	 *
	 * @return bool
	 */
	protected function lists_user( $html, $user_id ) {
		return false !== strpos( (string) $html, esc_url( bp_core_get_user_domain( $user_id ) ) );
	}

	/**
	 * @dataProvider data_hidden_statuses
	 *
	 * @param string $status Group status.
	 */
	public function test_guest_cannot_list_members_of_non_public_group( $status ) {
		$g = $this->make_group( $status );
		self::set_current_user( 0 );

		$response = $this->call_ajax( $g['group_id'], 'last_joined' );

		$this->assertFalse( $response['success'] );
		$this->assertFalse( $this->lists_user( wp_json_encode( $response ), $g['admin_id'] ) );
		$this->assertFalse( $this->lists_user( wp_json_encode( $response ), $g['member_id'] ) );
	}

	/**
	 * @dataProvider data_hidden_statuses
	 *
	 * @param string $status Group status.
	 */
	public function test_logged_in_non_member_cannot_list_members_of_non_public_group( $status ) {
		$g        = $this->make_group( $status );
		$outsider = self::factory()->user->create();
		self::set_current_user( $outsider );

		$response = $this->call_ajax( $g['group_id'], 'last_joined' );

		$this->assertFalse( $response['success'] );
		$this->assertFalse( $this->lists_user( wp_json_encode( $response ), $g['member_id'] ) );
	}

	/**
	 * @dataProvider data_hidden_statuses
	 *
	 * @param string $status Group status.
	 */
	public function test_group_member_can_list_members_of_non_public_group( $status ) {
		$g = $this->make_group( $status );
		self::set_current_user( $g['member_id'] );

		$response = $this->call_ajax( $g['group_id'], 'last_joined' );

		$this->assertTrue( $response['success'] );
		$this->assertTrue( $this->lists_user( $response['data']['html'], $g['member_id'] ) );
	}

	/**
	 * Data provider.
	 *
	 * @return array
	 */
	public function data_hidden_statuses() {
		return array(
			'private' => array( 'private' ),
			'hidden'  => array( 'hidden' ),
		);
	}

	public function test_guest_can_list_members_of_public_group() {
		$g = $this->make_group( 'public' );
		self::set_current_user( 0 );

		$response = $this->call_ajax( $g['group_id'], 'last_joined' );

		$this->assertTrue( $response['success'] );
		$this->assertTrue( $this->lists_user( $response['data']['html'], $g['member_id'] ) );
	}

	public function test_unknown_group_returns_error() {
		self::set_current_user( 0 );

		$response = $this->call_ajax( PHP_INT_MAX >> 33 );

		$this->assertFalse( $response['success'] );
	}

	/**
	 * New/Popular tabs must include organizers like the first render does.
	 *
	 * @dataProvider data_tabs
	 *
	 * @param string $filter Tab filter.
	 */
	public function test_ajax_tabs_include_group_organizers( $filter ) {
		$g = $this->make_group( 'public' );
		// The Popular sort reads total_friend_count; give both users a value.
		bp_update_user_meta( $g['admin_id'], 'total_friend_count', 3 );
		bp_update_user_meta( $g['member_id'], 'total_friend_count', 1 );
		self::set_current_user( $g['member_id'] );

		$response = $this->call_ajax( $g['group_id'], $filter );

		$this->assertTrue( $response['success'] );
		$this->assertTrue( $this->lists_user( $response['data']['html'], $g['admin_id'] ), "Organizer missing on the {$filter} tab." );
		$this->assertTrue( $this->lists_user( $response['data']['html'], $g['member_id'] ) );
	}

	/**
	 * Data provider.
	 *
	 * @return array
	 */
	public function data_tabs() {
		return array(
			'active'      => array( 'active' ),
			'last_joined' => array( 'last_joined' ),
			'popular'     => array( 'popular' ),
		);
	}

	/**
	 * A member last recorded 4 minutes ago (inside the 300s presence DB write
	 * throttle) must appear on the Active tab, including via the legacy `online` value.
	 *
	 * @dataProvider data_active_filters
	 *
	 * @param string $filter Tab filter.
	 */
	public function test_active_tab_lists_recently_active_members( $filter ) {
		$four_minutes_ago = gmdate( 'Y-m-d H:i:s', time() - 4 * MINUTE_IN_SECONDS );
		$g                = $this->make_group( 'public', $four_minutes_ago );
		self::set_current_user( $g['member_id'] );

		$response = $this->call_ajax( $g['group_id'], $filter );

		$this->assertTrue( $response['success'] );
		$this->assertTrue( $this->lists_user( $response['data']['html'], $g['member_id'] ) );
		$this->assertTrue( $this->lists_user( $response['data']['html'], $g['admin_id'] ) );
	}

	/**
	 * Data provider.
	 *
	 * @return array
	 */
	public function data_active_filters() {
		return array(
			'active'        => array( 'active' ),
			'legacy online' => array( 'online' ),
		);
	}

	public function test_query_args_match_between_tabs() {
		foreach ( array( 'active', 'last_joined', 'popular' ) as $filter ) {
			$args = BB_Group_Members_Widget::bb_get_query_args( 7, $filter, 5 );
			$this->assertSame( 0, $args['exclude_admins_mods'] );
			$this->assertSame( $filter, $args['type'] );
			$this->assertSame( 5, $args['max'] );
		}

		$this->assertSame( 'active', BB_Group_Members_Widget::bb_get_query_type( 'online' ) );
		$this->assertSame( 'active', BB_Group_Members_Widget::bb_get_query_type( 'alphabetical' ) );
		$this->assertSame( BB_Group_Members_Widget::MAX_MEMBERS, BB_Group_Members_Widget::bb_get_query_args( 7, 'active', 100000 )['max'] );
	}

	public function test_empty_result_returns_empty_state_html() {
		$html = BB_Group_Members_Widget::bb_get_members_list_html(
			array(
				'group_id'            => PHP_INT_MAX >> 33,
				'type'                => 'last_joined',
				'max'                 => 5,
				'exclude_admins_mods' => 0,
				'search_terms'        => '',
			)
		);

		$this->assertStringContainsString( 'bb-rl-group-members-empty', $html );
	}

	public function test_widget_keeps_tabs_and_lists_members_on_first_render() {
		$four_minutes_ago = gmdate( 'Y-m-d H:i:s', time() - 4 * MINUTE_IN_SECONDS );
		$g                = $this->make_group( 'public', $four_minutes_ago );
		self::set_current_user( $g['member_id'] );
		$this->set_current_group( $g['group_id'] );

		$html = $this->render_widget();

		$this->assertStringContainsString( 'bb-rl-popular-groups-members', $html );
		$this->assertTrue( $this->lists_user( $html, $g['member_id'] ) );
		$this->assertTrue( $this->lists_user( $html, $g['admin_id'] ) );
	}

	public function test_widget_keeps_tabs_when_active_tab_is_empty() {
		$g = $this->make_group( 'public' );
		// Remove every last_activity row: nobody is "recently active".
		BP_Core_User::delete_last_activity( $g['admin_id'] );
		BP_Core_User::delete_last_activity( $g['member_id'] );
		self::set_current_user( $g['member_id'] );
		$this->set_current_group( $g['group_id'] );

		$html = $this->render_widget();

		$this->assertStringContainsString( 'bb-rl-popular-groups-members', $html );
		$this->assertStringContainsString( 'bb-rl-group-members-empty', $html );
	}

	public function test_widget_renders_nothing_for_non_member_of_private_group() {
		$g        = $this->make_group( 'private' );
		$outsider = self::factory()->user->create();
		self::set_current_user( $outsider );
		$this->set_current_group( $g['group_id'] );

		$html = $this->render_widget();

		$this->assertSame( '', trim( $html ) );
	}

	/**
	 * Make the given group the current single group, as on a group page.
	 *
	 * @param int $group_id Group ID.
	 */
	protected function set_current_group( $group_id ) {
		buddypress()->current_component     = buddypress()->groups->id;
		buddypress()->groups->current_group = groups_get_group( $group_id );
	}

	/**
	 * Render the widget on the current page.
	 *
	 * @return string
	 */
	protected function render_widget() {
		ob_start();
		$this->widget->widget(
			array(
				'before_widget' => '<div class="widget">',
				'after_widget'  => '</div>',
			),
			array()
		);

		return ob_get_clean();
	}
}
