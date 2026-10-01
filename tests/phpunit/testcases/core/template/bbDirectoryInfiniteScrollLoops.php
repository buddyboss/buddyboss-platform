<?php
/**
 * Tests for the Members/Groups directory loop markup under the Directory Loading setting.
 *
 * Each loop is rendered the way bp_nouveau_ajax_object_template_loader() renders it:
 * the current component is set and flagged as a directory, then the template file is
 * loaded. Both the BP Nouveau and the ReadyLaunch copies are covered.
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
		buddypress()->displayed_user->id = 0;
		buddypress()->current_action     = '';

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
		}
	}

	/**
	 * Render a loop on its directory, or on a member's profile tab, at a given page.
	 *
	 * @param string $object       'members' or 'groups'.
	 * @param string $template     Template path relative to bp-templates/bp-nouveau/.
	 * @param int    $page         Page to render.
	 * @param bool   $is_directory True for the directory; false for the profile
	 *                             Connections (members) or Groups (groups) tab.
	 *
	 * @return string
	 */
	protected function render( $object, $template, $page = 1, $is_directory = true ) {
		$this->page                     = $page;
		buddypress()->current_component = $object;
		bp_update_is_directory( $is_directory, $object );

		if ( ! $is_directory ) {
			buddypress()->displayed_user->id = $this->owner_id;
			buddypress()->current_action     = 'groups' === $object ? 'my-groups' : 'my-friends';
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
	 * Profile tabs that reuse these loops keep classic pagination.
	 *
	 * @dataProvider data_loops
	 */
	public function test_infinite_mode_is_limited_to_the_directory( $object, $template ) {
		$this->create_items( $object );
		bp_update_option( 'bb_directory_load_type', 'infinite' );

		$html = $this->render( $object, $template, 2, false );

		$this->assertFalse( 'groups' === $object ? bp_is_groups_directory() : bp_is_members_directory(), 'precondition: a profile tab is not the directory' );
		$this->assertSame( array(), $this->load_more_links( $html ) );
		$this->assertStringContainsString( 'data-bp-pagination', $html );
		$this->assertSame( 1, $this->list_wrappers( $object, $html ), 'A page-2 request outside the directory is a full list, not an append' );
	}
}
