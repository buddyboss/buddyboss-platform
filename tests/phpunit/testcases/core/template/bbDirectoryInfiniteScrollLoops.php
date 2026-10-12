<?php
/**
 * Tests for the Members/Groups loop markup under the Members & Groups Loading setting.
 *
 * Each loop is rendered the way bp_nouveau_ajax_object_template_loader() renders it:
 * the current screen (directory, profile tab or group tab) is set up, then the template
 * file is loaded. Both the BP Nouveau and the ReadyLaunch copies are covered.
 *
 * @group core
 * @group PROD-9724
 */
class BB_Tests_Core_Template_BbDirectoryInfiniteScrollLoops extends BP_UnitTestCase {

	/**
	 * Items per page used for every rendered loop.
	 */
	const PER_PAGE = 2;

	/**
	 * Page requested by the current render, fed into the loop query string.
	 *
	 * @var int
	 */
	protected $page = 1;

	/**
	 * IDs created by the current test. The loop is limited to them so items left
	 * in memory by earlier tests in the same process cannot reach the render.
	 *
	 * @var int[]
	 */
	protected $item_ids = array();

	/**
	 * Member who owns the fixture groups, used as the displayed user on profile tabs.
	 *
	 * @var int
	 */
	protected $owner_id = 0;

	public function set_up() {
		parent::set_up();

		add_filter( 'bp_ajax_querystring', array( $this, 'filter_querystring' ), 1000, 2 );
	}

	public function tear_down() {
		remove_filter( 'bp_ajax_querystring', array( $this, 'filter_querystring' ), 1000 );

		unset( $_POST['page'] );
		bp_delete_option( 'bb_directory_load_type' );
		bp_update_is_directory( false, '' );
		buddypress()->displayed_user->id    = 0;
		buddypress()->current_action        = '';
		buddypress()->current_item          = '';
		buddypress()->is_single_item        = false;
		buddypress()->groups->current_group = null;
		buddypress()->current_member_type           = '';
		buddypress()->groups->current_directory_type = '';

		parent::tear_down();
	}

	/**
	 * Pin the loop to a small, deterministic page.
	 *
	 * @param string $qs     Query string.
	 * @param string $object Loop object.
	 *
	 * @return string
	 */
	public function filter_querystring( $qs, $object ) {
		// The group members loop is limited to the current group and appends its own type.
		if ( 'group_members' === $object ) {
			return build_query(
				array(
					'per_page' => self::PER_PAGE,
					'page'     => $this->page,
				)
			);
		}

		if ( ! in_array( $object, array( 'members', 'groups' ), true ) ) {
			return $qs;
		}

		return build_query(
			array(
				'type'     => 'newest',
				'per_page' => self::PER_PAGE,
				'page'     => $this->page,
				'include'  => implode( ',', $this->item_ids ),
			)
		);
	}

	/**
	 * Loop templates under test.
	 *
	 * @return array
	 */
	public function data_loops() {
		return array(
			'nouveau members'     => array( 'members', 'buddypress/members/members-loop.php' ),
			'nouveau groups'      => array( 'groups', 'buddypress/groups/groups-loop.php' ),
			'readylaunch members' => array( 'members', 'readylaunch/members/members-loop.php' ),
			'readylaunch groups'  => array( 'groups', 'readylaunch/groups/groups-loop.php' ),
		);
	}

	/**
	 * Group members loop templates under test.
	 *
	 * @return array
	 */
	public function data_group_member_loops() {
		return array(
			'nouveau group members'     => array( 'buddypress/groups/single/members-loop.php' ),
			'readylaunch group members' => array( 'readylaunch/groups/single/members-loop.php' ),
		);
	}

	/**
	 * Create enough members or groups for at least three pages.
	 *
	 * @param string $object 'members' or 'groups'.
	 */
	protected function create_items( $object ) {
		$this->owner_id = self::factory()->user->create();

		if ( 'groups' === $object ) {
			$this->item_ids = self::factory()->group->create_many( 5, array( 'creator_id' => $this->owner_id ) );
			return;
		}

		$this->item_ids = self::factory()->user->create_many( 5 );

		foreach ( $this->item_ids as $user_id ) {
			bp_update_user_last_activity( $user_id, bp_core_current_time() );

			// The profile Connections tab lists the displayed member's connections.
			friends_add_friend( $this->owner_id, $user_id, true );
		}
	}

	/**
	 * Render a loop on a screen at a given page.
	 *
	 * @param string $object   'members' or 'groups'.
	 * @param string $template Template path relative to bp-templates/bp-nouveau/.
	 * @param int    $page     Page to render.
	 * @param string $screen   'directory'; 'profile' for the profile Connections (members) or
	 *                         Groups (groups) tab; 'mutual' for profile Mutual Connections;
	 *                         'none' for a loop rendered outside these screens (widget, shortcode).
	 *
	 * @return string
	 */
	protected function render( $object, $template, $page = 1, $screen = 'directory' ) {
		$this->page                     = $page;
		buddypress()->current_component = $object;
		bp_update_is_directory( 'directory' === $screen, $object );

		if ( in_array( $screen, array( 'profile', 'mutual' ), true ) ) {
			buddypress()->current_component  = 'groups' === $object ? 'groups' : 'friends';
			buddypress()->displayed_user->id = $this->owner_id;
			buddypress()->current_action     = 'groups' === $object ? 'my-groups' : ( 'mutual' === $screen ? 'mutual' : 'my-friends' );
		} elseif ( 'none' === $screen ) {
			buddypress()->current_component = '';
		}

		// A load-more request posts the page it wants; a full (re)load posts page 1.
		$_POST['page'] = $page;

		ob_start();
		require buddypress()->plugin_dir . 'bp-templates/bp-nouveau/' . $template;

		return ob_get_clean();
	}

	/**
	 * Total pages of the loop that was rendered last.
	 *
	 * @param string $object 'members' or 'groups'.
	 *
	 * @return int
	 */
	protected function total_pages( $object ) {
		if ( 'groups' === $object ) {
			global $groups_template;
			return (int) ceil( $groups_template->total_group_count / self::PER_PAGE );
		}

		global $members_template;
		return (int) ceil( $members_template->total_member_count / self::PER_PAGE );
	}

	/**
	 * Load More link hrefs found in the markup.
	 *
	 * @param string $html Rendered loop.
	 *
	 * @return array
	 */
	protected function load_more_links( $html ) {
		preg_match_all( '/<li class="load-more[^"]*">\s*<a[^>]*href="([^"]*)"/', $html, $matches );

		return $matches[1];
	}

	/**
	 * Opening tags of the list wrapper.
	 *
	 * @param string $object 'members' or 'groups'.
	 * @param string $html   Rendered loop.
	 *
	 * @return int
	 */
	protected function list_wrappers( $object, $html ) {
		return substr_count( $html, 'id="' . $object . '-list"' );
	}

	/**
	 * Pagination query arg of the loop.
	 *
	 * @param string $object 'members' or 'groups'.
	 *
	 * @return string
	 */
	protected function pag_arg( $object ) {
		return 'groups' === $object ? 'grpage' : 'upage';
	}

	/**
	 * @dataProvider data_loops
	 */
	public function test_pagination_mode_keeps_classic_pagination( $object, $template ) {
		$this->create_items( $object );
		bp_update_option( 'bb_directory_load_type', 'pagination' );

		$html = $this->render( $object, $template );

		$this->assertStringContainsString( 'data-bp-pagination', $html );
		$this->assertSame( array(), $this->load_more_links( $html ) );
		$this->assertSame( 1, $this->list_wrappers( $object, $html ) );
	}

	/**
	 * @dataProvider data_loops
	 */
	public function test_infinite_mode_first_page_has_load_more_instead_of_pagination( $object, $template ) {
		$this->create_items( $object );
		bp_update_option( 'bb_directory_load_type', 'infinite' );

		$html = $this->render( $object, $template );

		$this->assertGreaterThanOrEqual( 3, $this->total_pages( $object ), 'precondition: the fixture spans three pages' );
		$this->assertStringNotContainsString( 'data-bp-pagination', $html );
		$this->assertSame( array( '?' . $this->pag_arg( $object ) . '=2' ), $this->load_more_links( $html ) );
		$this->assertSame( 1, $this->list_wrappers( $object, $html ) );
	}

	/**
	 * The Load More link is the source of the next page for the JS (PROD-9724), so a
	 * list opened on a later page must link to the page after it, not to page 2.
	 *
	 * @dataProvider data_loops
	 */
	public function test_infinite_mode_load_more_links_to_the_page_after_the_rendered_one( $object, $template ) {
		$this->create_items( $object );
		bp_update_option( 'bb_directory_load_type', 'infinite' );

		$html = $this->render( $object, $template, 2 );

		$this->assertSame( array( '?' . $this->pag_arg( $object ) . '=3' ), $this->load_more_links( $html ) );
	}

	/**
	 * An appended page returns list items only, so nothing is nested or duplicated in the list.
	 *
	 * @dataProvider data_loops
	 */
	public function test_infinite_mode_load_more_request_returns_items_only( $object, $template ) {
		$this->create_items( $object );
		bp_update_option( 'bb_directory_load_type', 'infinite' );

		$html  = $this->render( $object, $template, 2 );
		$popup = 'groups' === $object ? 'bb-leave-group-popup' : 'bb-remove-connection';

		$this->assertSame( 0, $this->list_wrappers( $object, $html ) );
		$this->assertStringNotContainsString( '</ul>', $html );
		$this->assertStringNotContainsString( $popup, $html );
		$this->assertStringNotContainsString( 'data-bp-pagination', $html );
		$this->assertSame( self::PER_PAGE, preg_match_all( '/data-bp-item-id="\d+"/', $html ) );
	}

	/**
	 * @dataProvider data_loops
	 */
	public function test_infinite_mode_last_page_has_no_load_more( $object, $template ) {
		$this->create_items( $object );
		bp_update_option( 'bb_directory_load_type', 'infinite' );

		$this->render( $object, $template );
		$last = $this->total_pages( $object );

		$html = $this->render( $object, $template, $last );

		$this->assertGreaterThan( 0, preg_match_all( '/data-bp-item-id="\d+"/', $html ), 'precondition: the last page has items' );
		$this->assertSame( array(), $this->load_more_links( $html ) );
	}

	/**
	 * Members & Groups Loading applies to the profile Connections and Groups tabs, like Feed
	 * Page Loading applies to the profile Timeline (PROD-9724 scope update).
	 *
	 * @dataProvider data_loops
	 */
	public function test_infinite_mode_applies_to_the_profile_tabs( $object, $template ) {
		$this->create_items( $object );
		bp_update_option( 'bb_directory_load_type', 'infinite' );

		$first  = $this->render( $object, $template, 1, 'profile' );
		$append = $this->render( $object, $template, 2, 'profile' );

		$this->assertFalse( 'groups' === $object ? bp_is_groups_directory() : bp_is_members_directory(), 'precondition: a profile tab is not the directory' );
		$this->assertStringNotContainsString( 'data-bp-pagination', $first );
		$this->assertSame( array( '?' . $this->pag_arg( $object ) . '=2' ), $this->load_more_links( $first ) );
		$this->assertSame( 1, $this->list_wrappers( $object, $first ) );
		$this->assertSame( 0, $this->list_wrappers( $object, $append ), 'A page-2 request on a profile tab is an append' );
	}

	/**
	 * @dataProvider data_member_loops
	 */
	public function test_infinite_mode_applies_to_mutual_connections( $object, $template ) {
		$this->create_items( $object );
		bp_update_option( 'bb_directory_load_type', 'infinite' );

		$html = $this->render( $object, $template, 1, 'mutual' );

		$this->assertStringNotContainsString( 'data-bp-pagination', $html );
		$this->assertSame( array( '?upage=2' ), $this->load_more_links( $html ) );
	}

	/**
	 * Lists rendered outside the supported screens (widgets, Network Search, Elementor) keep
	 * classic pagination.
	 *
	 * @dataProvider data_loops
	 */
	public function test_infinite_mode_keeps_pagination_outside_the_supported_screens( $object, $template ) {
		$this->create_items( $object );
		bp_update_option( 'bb_directory_load_type', 'infinite' );

		$html = $this->render( $object, $template, 2, 'none' );

		$this->assertSame( array(), $this->load_more_links( $html ) );
		$this->assertStringContainsString( 'data-bp-pagination', $html );
		$this->assertSame( 1, $this->list_wrappers( $object, $html ), 'A page-2 request outside the supported screens is a full list, not an append' );
	}

	/**
	 * Members loop templates only.
	 *
	 * @return array
	 */
	public function data_member_loops() {
		return array_filter(
			$this->data_loops(),
			function ( $loop ) {
				return 'members' === $loop[0];
			}
		);
	}

	/**
	 * Create a group with enough members for at least three pages and open its Members tab.
	 */
	protected function open_group_members_tab() {
		$this->owner_id = self::factory()->user->create();
		$group_id       = self::factory()->group->create( array( 'creator_id' => $this->owner_id ) );

		foreach ( self::factory()->user->create_many( 5 ) as $user_id ) {
			groups_join_group( $group_id, $user_id );
		}

		$group = groups_get_group( $group_id );

		buddypress()->current_component     = 'groups';
		buddypress()->current_action        = 'members';
		buddypress()->current_item          = $group->slug;
		buddypress()->is_single_item        = true;
		buddypress()->groups->current_group = $group;
		bp_update_is_directory( false, 'groups' );
	}

	/**
	 * Render the group members loop at a given page.
	 *
	 * @param string $template Template path relative to bp-templates/bp-nouveau/.
	 * @param int    $page     Page to render.
	 *
	 * @return string
	 */
	protected function render_group_members( $template, $page = 1 ) {
		$this->page    = $page;
		$_POST['page'] = $page;

		ob_start();
		require buddypress()->plugin_dir . 'bp-templates/bp-nouveau/' . $template;

		return ob_get_clean();
	}

	/**
	 * Total pages of the group members loop that was rendered last.
	 *
	 * @return int
	 */
	protected function group_member_pages() {
		global $members_template;

		return (int) ceil( $members_template->total_member_count / self::PER_PAGE );
	}

	/**
	 * @dataProvider data_group_member_loops
	 */
	public function test_group_members_pagination_mode_keeps_classic_pagination( $template ) {
		$this->open_group_members_tab();
		bp_update_option( 'bb_directory_load_type', 'pagination' );

		$html = $this->render_group_members( $template );

		$this->assertGreaterThanOrEqual( 3, $this->group_member_pages(), 'precondition: the group spans three pages' );
		$this->assertStringContainsString( 'data-bp-pagination', $html );
		$this->assertSame( array(), $this->load_more_links( $html ) );
	}

	/**
	 * @dataProvider data_group_member_loops
	 */
	public function test_group_members_infinite_mode_first_page_has_load_more( $template ) {
		$this->open_group_members_tab();
		bp_update_option( 'bb_directory_load_type', 'infinite' );

		$html = $this->render_group_members( $template );

		$this->assertGreaterThanOrEqual( 3, $this->group_member_pages(), 'precondition: the group spans three pages' );
		$this->assertStringNotContainsString( 'data-bp-pagination', $html );
		$this->assertSame( array( '?mlpage=2' ), $this->load_more_links( $html ) );
		$this->assertSame( 1, $this->list_wrappers( 'members', $html ) );
	}

	/**
	 * An appended group members page returns list items only, and the group list hooks still
	 * run for it (moderation swaps blocked members' avatars from them).
	 *
	 * @dataProvider data_group_member_loops
	 */
	public function test_group_members_load_more_request_returns_items_only( $template ) {
		$this->open_group_members_tab();
		bp_update_option( 'bb_directory_load_type', 'infinite' );

		$before = did_action( 'bp_before_group_members_list' );
		$this->render_group_members( $template );
		$full_render_hooks = did_action( 'bp_before_group_members_list' ) - $before;

		$before = did_action( 'bp_before_group_members_list' );
		$html   = $this->render_group_members( $template, 2 );

		$this->assertSame( 0, $this->list_wrappers( 'members', $html ) );
		$this->assertStringNotContainsString( '</ul>', $html );
		$this->assertStringNotContainsString( 'bb-remove-connection', $html );
		$this->assertStringNotContainsString( 'data-bp-pagination', $html );
		$this->assertSame( array( '?mlpage=3' ), $this->load_more_links( $html ) );
		$this->assertSame( self::PER_PAGE, preg_match_all( '/data-bp-item-id="\d+"/', $html ) );
		$this->assertSame( $full_render_hooks, did_action( 'bp_before_group_members_list' ) - $before, 'An appended page fires the same list hooks as a full render' );
	}

	/**
	 * @dataProvider data_group_member_loops
	 */
	public function test_group_members_last_page_has_no_load_more( $template ) {
		$this->open_group_members_tab();
		bp_update_option( 'bb_directory_load_type', 'infinite' );

		$this->render_group_members( $template );
		$html = $this->render_group_members( $template, $this->group_member_pages() );

		$this->assertGreaterThan( 0, preg_match_all( '/data-bp-item-id="\d+"/', $html ), 'precondition: the last page has items' );
		$this->assertSame( array(), $this->load_more_links( $html ) );
	}

	/**
	 * The [profile type=""] shortcode marks its page as the members directory, but it is not
	 * one of the screens the setting applies to, so it keeps pagination.
	 */
	public function test_member_type_shortcode_keeps_pagination() {
		$this->create_items( 'members' );
		bp_update_option( 'bb_directory_load_type', 'infinite' );

		bp_register_member_type( 'prod9724type', array( 'labels' => array( 'name' => 'PROD 9724' ) ) );
		foreach ( $this->item_ids as $user_id ) {
			bp_set_member_type( $user_id, 'prod9724type' );
		}

		$html = bp_member_type_shortcode_callback( array( 'type' => 'prod9724type' ) );

		$this->assertTrue( bp_is_members_directory(), 'precondition: the shortcode flags the page as the members directory' );
		$this->assertGreaterThan( 0, preg_match_all( '/data-bp-item-id="\d+"/', $html ), 'precondition: the shortcode lists members' );
		$this->assertSame( array(), $this->load_more_links( $html ) );
		$this->assertStringContainsString( 'data-bp-pagination', $html );
		$this->assertFalse( has_filter( 'bb_is_list_autoload_active', '__return_false' ), 'The shortcode removes its override after the loop' );
	}

	/**
	 * Same for the [group type=""] shortcode.
	 */
	public function test_group_type_shortcode_keeps_pagination() {
		$this->create_items( 'groups' );
		bp_update_option( 'bb_directory_load_type', 'infinite' );

		bp_groups_register_group_type( 'prod9724gtype' );
		foreach ( $this->item_ids as $group_id ) {
			bp_groups_set_group_type( $group_id, 'prod9724gtype' );
		}

		$html = bp_group_type_short_code_callback( array( 'type' => 'prod9724gtype' ) );

		$this->assertTrue( bp_is_groups_directory(), 'precondition: the shortcode flags the page as the groups directory' );
		$this->assertGreaterThan( 0, preg_match_all( '/data-bp-item-id="\d+"/', $html ), 'precondition: the shortcode lists groups' );
		$this->assertSame( array(), $this->load_more_links( $html ) );
		$this->assertStringContainsString( 'data-bp-pagination', $html );
		$this->assertFalse( has_filter( 'bb_is_list_autoload_active', '__return_false' ), 'The shortcode removes its override after the loop' );
	}

	/**
	 * Put the directory on a profile / group type, as /members/type/x/ and /groups/type/x/ do,
	 * and give the test items that type.
	 *
	 * @param string $object 'members' or 'groups'.
	 */
	protected function view_type( $object ) {
		if ( 'groups' === $object ) {
			bp_groups_register_group_type( 'prod9724view', array( 'labels' => array( 'name' => 'PROD 9724 View' ) ) );
			buddypress()->groups->current_directory_type = 'prod9724view';
			foreach ( $this->item_ids as $group_id ) {
				bp_groups_set_group_type( $group_id, 'prod9724view' );
			}
			return;
		}

		bp_register_member_type( 'prod9724view', array( 'labels' => array( 'name' => 'PROD 9724 View' ) ) );
		buddypress()->current_member_type = 'prod9724view';
		foreach ( $this->item_ids as $user_id ) {
			bp_set_member_type( $user_id, 'prod9724view' );
		}
	}

	/**
	 * A type directory with nobody of that type still says which type it shows, as on release.
	 *
	 * @dataProvider data_loops
	 */
	public function test_empty_type_directory_keeps_the_type_notice( $object, $template ) {
		$this->owner_id = self::factory()->user->create();
		$this->item_ids = array();
		$this->view_type( $object );
		$this->item_ids = array( PHP_INT_MAX ); // Nothing matches.

		foreach ( array( 'pagination', 'infinite' ) as $mode ) {
			bp_update_option( 'bb_directory_load_type', $mode );

			$html = $this->render( $object, $template );

			$this->assertSame( 0, $this->list_wrappers( $object, $html ), "precondition ({$mode}): the list is empty" );
			$this->assertStringContainsString( 'PROD 9724 View', $html, "{$mode}: the type notice is shown" );
		}
	}

	/**
	 * A load-more request returns only the next items, without the type notice.
	 *
	 * @dataProvider data_loops
	 */
	public function test_load_more_request_has_no_type_notice( $object, $template ) {
		$this->create_items( $object );
		$this->view_type( $object );
		bp_update_option( 'bb_directory_load_type', 'infinite' );

		$first = $this->render( $object, $template, 1 );
		$next  = $this->render( $object, $template, 2 );

		$this->assertGreaterThan( 0, preg_match_all( '/data-bp-item-id="\d+"/', $next ), 'precondition: page 2 has items of the type' );
		$this->assertStringContainsString( 'PROD 9724 View', $first, 'The first page shows the type notice' );
		$this->assertStringNotContainsString( 'PROD 9724 View', $next, 'A load-more page does not repeat it' );
	}
}
