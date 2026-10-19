<?php
/**
 * Private Website and Private RSS Feeds tests.
 *
 * Covers PROD-10602: both checks used to decide "is this a feed / REST / AJAX
 * request?" from substrings of the full URL, so `?nofeed=wp-json` skipped the
 * Private Website redirect and the RSS restriction at once.
 *
 * @group core
 * @group private-network
 */
class BB_Tests_Core_Private_Network extends BP_UnitTestCase {

	/**
	 * Server values replaced by a test.
	 *
	 * @var array
	 */
	protected $old_server = array();

	/**
	 * Query vars replaced by a test.
	 *
	 * @var array
	 */
	protected $old_get = array();

	public function set_up() {
		parent::set_up();

		$this->old_server = $_SERVER;
		$this->old_get    = $_GET;

		$this->set_current_user( 0 );

		// Stop bp_core_no_access() before it redirects and exits.
		add_filter( 'bp_core_no_access', array( $this, 'throw_no_access' ) );
	}

	public function tear_down() {
		$_SERVER = $this->old_server;
		$_GET    = $this->old_get;

		remove_filter( 'bp_core_no_access', array( $this, 'throw_no_access' ) );
		remove_filter( 'bp_enable_private_network', '__return_false' );
		remove_filter( 'bp_enable_private_rss_feeds', '__return_true' );
		remove_filter( 'bb_enable_private_rss_feeds_public_content', array( $this, 'public_rss_feeds' ) );
		remove_filter( 'wp_doing_ajax', '__return_true' );

		parent::tear_down();
	}

	/**
	 * go_to() re-runs `init`, which registers the ReadyLaunch header block a second time.
	 *
	 * @param string $url URL to load.
	 */
	public function go_to( $url ) {
		$this->setExpectedIncorrectUsage( 'WP_Block_Type_Registry::register' );
		parent::go_to( $url );
	}

	/**
	 * Turn a no-access redirect into an exception the test can assert on.
	 *
	 * @param array $args bp_core_no_access() arguments.
	 *
	 * @throws Exception Always.
	 */
	public function throw_no_access( $args ) {
		throw new Exception( 'bp_core_no_access:' . $args['redirect'] );
	}

	/**
	 * Public RSS Feeds allowlist used by a test.
	 *
	 * @return string
	 */
	public function public_rss_feeds() {
		return '/sample-feed-page/';
	}

	/**
	 * Whether running the callback ended in a no-access redirect.
	 *
	 * @param callable $callback Code under test.
	 *
	 * @return bool
	 */
	protected function is_blocked( $callback ) {
		try {
			call_user_func( $callback );
		} catch ( Exception $e ) {
			if ( 0 === strpos( $e->getMessage(), 'bp_core_no_access:' ) ) {
				return true;
			}
			throw $e;
		}

		return false;
	}

	/**
	 * Enable "Private Website". The stored option is inverted: true means public.
	 */
	protected function enable_private_website() {
		add_filter( 'bp_enable_private_network', '__return_false' );
	}

	protected function enable_private_rss_feeds() {
		add_filter( 'bp_enable_private_rss_feeds', '__return_true' );
	}

	/**
	 * Point the request globals the RSS restriction reads at a URL path.
	 *
	 * @param string $request_uri Path and query string.
	 * @param string $script_name Executed script.
	 */
	protected function set_request( $request_uri, $script_name = '/index.php' ) {
		$_SERVER['HTTP_HOST']   = WP_TESTS_DOMAIN;
		$_SERVER['REQUEST_URI'] = $request_uri;
		$_SERVER['SCRIPT_NAME'] = $script_name;
		$_SERVER['PHP_SELF']    = $script_name;
	}

	/**
	 * A page whose `rss2` endpoint is a real feed without "/feed/" or "feed=" in the URL.
	 *
	 * @return string Feed URL.
	 */
	protected function page_rss2_feed_url() {
		$this->set_permalink_structure( '/%postname%/' );

		$page_id = self::factory()->post->create(
			array(
				'post_type'  => 'page',
				'post_name'  => 'sample-feed-page',
				'post_title' => 'Sample feed page',
			)
		);

		return trailingslashit( get_permalink( $page_id ) ) . 'rss2';
	}

	public function data_feed_like_query_args() {
		return array(
			'reported payload'         => array( 'nofeed=wp-json' ),
			'no wp-json needed'        => array( 'nofeed=1' ),
			'other prefix'             => array( 'xfeed=wp-json' ),
			'"/feed/" inside a value'  => array( 'redirect=/feed/' ),
		);
	}

	/**
	 * @dataProvider data_feed_like_query_args
	 *
	 * @param string $query_arg Query string appended to a non-feed page.
	 */
	public function test_private_website_redirects_page_with_feed_like_query_arg( $query_arg ) {
		$this->enable_private_website();

		$post_id = self::factory()->post->create();
		$url     = get_permalink( $post_id );
		$url    .= ( false === strpos( $url, '?' ) ? '?' : '&' ) . $query_arg;

		$this->assertTrue(
			$this->is_blocked(
				function () use ( $url ) {
					$this->go_to( $url );
				}
			),
			'A guest must be sent to login for ' . $url
		);
		$this->assertFalse( is_feed() );
	}

	public function test_private_website_leaves_a_real_feed_to_the_rss_setting() {
		$this->enable_private_website();

		$this->assertFalse(
			$this->is_blocked(
				function () {
					$this->go_to( home_url( '/?feed=rss2' ) );
				}
			)
		);
		$this->assertTrue( is_feed() );
	}

	public function test_private_website_does_not_affect_logged_in_members() {
		$this->enable_private_website();
		$this->set_current_user( self::factory()->user->create() );

		$post_id = self::factory()->post->create();

		$this->assertFalse(
			$this->is_blocked(
				function () use ( $post_id ) {
					$this->go_to( get_permalink( $post_id ) );
				}
			)
		);
	}

	public function data_exempt_requests() {
		return array(
			'REST path'            => array( '/wp-json/buddyboss/v1/signup/form?nofeed=1', '/index.php', array(), true ),
			'REST route arg'       => array( '/?rest_route=/buddyboss/v1/signup/form&feed=x', '/index.php', array( 'rest_route' => '/buddyboss/v1/signup/form' ), true ),
			'login page'           => array( '/wp-login.php?redirect_to=%2Ffeed%2F', '/wp-login.php', array(), true ),
			'feed with wp-json'    => array( '/?feed=rss2&x=wp-json', '/index.php', array(), false ),
			'feed with ajax text'  => array( '/feed/?x=admin-ajax.php', '/index.php', array(), false ),
			'feed with login text' => array( '/feed/?x=wp-login.php', '/index.php', array(), false ),
			'feed with cron text'  => array( '/feed/?x=wp-cron.php', '/index.php', array(), false ),
			'empty rest_route arg' => array( '/?rest_route=&feed=rss2', '/index.php', array( 'rest_route' => '' ), false ),
		);
	}

	/**
	 * @dataProvider data_exempt_requests
	 *
	 * @param string $request_uri Request path and query.
	 * @param string $script_name Executed script.
	 * @param array  $get         Parsed query vars.
	 * @param bool   $expected    Whether the request is exempt.
	 */
	public function test_rss_restriction_exemption_follows_request_type( $request_uri, $script_name, $get, $expected ) {
		$this->set_permalink_structure( '/%postname%/' );
		$this->set_request( $request_uri, $script_name );
		$_GET = $get;

		$this->assertSame( $expected, bb_is_rss_feed_restriction_exempt_request() );
	}

	public function test_rss_restriction_exemption_ignores_login_path_info() {
		$this->set_request( '/index.php/wp-login.php?feed=rss2' );
		// WordPress derives $pagenow from PHP_SELF, which carries the PATH_INFO.
		$_SERVER['PHP_SELF'] = '/index.php/wp-login.php';

		$this->assertFalse( bb_is_rss_feed_restriction_exempt_request() );
	}

	public function test_rss_restriction_exemption_allows_ajax() {
		$this->set_request( '/wp-admin/admin-ajax.php?feed=x', '/wp-admin/admin-ajax.php' );
		add_filter( 'wp_doing_ajax', '__return_true' );

		$this->assertTrue( bb_is_rss_feed_restriction_exempt_request() );
	}

	public function data_feed_urls_with_exemption_text() {
		return array(
			'wp-json'        => array( '/?feed=rss2&x=wp-json' ),
			'activity feed'  => array( '/news-feed/feed/?x=wp-json' ),
			'admin-ajax.php' => array( '/feed/?x=admin-ajax.php' ),
		);
	}

	/**
	 * @dataProvider data_feed_urls_with_exemption_text
	 *
	 * @param string $request_uri Feed URL carrying text the old URL test exempted.
	 */
	public function test_rss_restriction_blocks_feed_url_carrying_exemption_text( $request_uri ) {
		$this->set_permalink_structure( '/%postname%/' );
		$this->set_request( $request_uri );

		$this->assertTrue( $this->is_blocked( 'bb_restricate_rss_feed' ) );
	}

	public function test_rss_restriction_still_exempts_rest_requests() {
		$this->set_permalink_structure( '/%postname%/' );
		$this->set_request( '/wp-json/wp/v2/posts?feed=rss2' );

		$this->assertFalse( $this->is_blocked( 'bb_restricate_rss_feed' ) );
	}

	public function test_template_redirect_restricts_feed_the_url_check_misses() {
		$feed_url = $this->page_rss2_feed_url();
		$this->go_to( $feed_url );
		$this->assertTrue( is_feed(), 'Fixture must be a real feed.' );

		$this->set_request( wp_parse_url( $feed_url, PHP_URL_PATH ) );
		$this->enable_private_rss_feeds();

		$this->assertFalse( $this->is_blocked( 'bb_restricate_rss_feed' ), 'The init check cannot see this feed from its URL.' );
		$this->assertTrue( $this->is_blocked( 'bb_restricate_rss_feed_template_redirect' ) );
	}

	public function test_template_redirect_leaves_feed_public_when_rss_feeds_are_public() {
		$feed_url = $this->page_rss2_feed_url();
		$this->go_to( $feed_url );
		$this->set_request( wp_parse_url( $feed_url, PHP_URL_PATH ) );

		$this->assertFalse( $this->is_blocked( 'bb_restricate_rss_feed_template_redirect' ) );
	}

	public function test_template_redirect_honours_public_rss_feeds_allowlist() {
		$feed_url = $this->page_rss2_feed_url();
		$this->go_to( $feed_url );
		$this->set_request( wp_parse_url( $feed_url, PHP_URL_PATH ) );
		$this->enable_private_rss_feeds();
		add_filter( 'bb_enable_private_rss_feeds_public_content', array( $this, 'public_rss_feeds' ) );

		$this->assertFalse( $this->is_blocked( 'bb_restricate_rss_feed_template_redirect' ) );
	}

	public function test_template_redirect_ignores_logged_in_members_and_non_feeds() {
		$this->enable_private_rss_feeds();

		$post_id = self::factory()->post->create();
		$this->go_to( get_permalink( $post_id ) );
		$this->assertFalse( $this->is_blocked( 'bb_restricate_rss_feed_template_redirect' ), 'Non-feed page.' );

		$this->go_to( home_url( '/?feed=rss2' ) );
		$this->set_current_user( self::factory()->user->create() );
		$this->assertFalse( $this->is_blocked( 'bb_restricate_rss_feed_template_redirect' ), 'Logged-in member.' );
	}

	public function test_template_redirect_check_runs_before_buddyboss_template_redirect() {
		$this->assertSame( 1, has_action( 'template_redirect', 'bb_restricate_rss_feed_template_redirect' ) );
		$this->assertGreaterThan( 1, has_action( 'template_redirect', 'bp_template_redirect' ) );
	}

	/**
	 * Skip when the Forums component is not loaded.
	 */
	protected function require_forums() {
		if ( ! function_exists( 'bbp_get_forum_post_type' ) ) {
			$this->markTestSkipped( 'Forums component is not active.' );
		}
	}

	public function data_forum_feed_query_vars() {
		return array(
			'forums feed'               => array( array( 'post_type' => 'forum', 'feed' => 'feed' ), '/forums/feed/' ),
			'forums feed + rest_route'  => array( array( 'post_type' => 'forum', 'feed' => 'feed', 'rest_route' => '/' ), '/forums/feed/?rest_route=/' ),
			'path-only forums rss2'     => array( array( 'post_type' => 'forum', 'feed' => 'rss2' ), '/forums/rss2/' ),
			'topics feed via rest_route' => array( array( 'post_type' => 'topic', 'feed' => 'rss2', 'rest_route' => '/' ), '/?rest_route=/&post_type=topic&feed=rss2' ),
			'replies feed'              => array( array( 'post_type' => array( 'reply' ), 'feed' => 'atom' ), '/?post_type=reply&feed=atom' ),
			'empty feed value'          => array( array( 'post_type' => 'forum', 'feed' => '' ), '/?post_type=forum&feed=' ),
			'forum view feed'           => array( array( 'bbp_view' => 'popular', 'feed' => 'rss2' ), '/forums/view/popular/feed/' ),
		);
	}

	/**
	 * @dataProvider data_forum_feed_query_vars
	 *
	 * @param array  $query_vars  Parsed request query vars.
	 * @param string $request_uri Matching request URI.
	 */
	public function test_forum_feeds_are_restricted_before_bbpress_prints_them( $query_vars, $request_uri ) {
		$this->require_forums();
		if ( isset( $query_vars['bbp_view'] ) ) {
			// The provider uses a placeholder key; map it to the real view rewrite id.
			$view = $query_vars['bbp_view'];
			unset( $query_vars['bbp_view'] );
			$query_vars[ bbp_get_view_rewrite_id() ] = $view;
		}
		$this->set_request( $request_uri );
		$this->enable_private_rss_feeds();

		$this->assertTrue(
			$this->is_blocked(
				function () use ( $query_vars ) {
					bb_restricate_rss_feed_forums_request( $query_vars );
				}
			)
		);
	}

	public function data_requests_the_forum_check_leaves_alone() {
		return array(
			'REST request with a feed arg' => array( array( 'rest_route' => '/wp/v2/posts', 'feed' => 'rss2' ), '/wp-json/wp/v2/posts?feed=rss2' ),
			'site feed (template path)'    => array( array( 'feed' => 'rss2' ), '/?feed=rss2' ),
			'posts feed'                   => array( array( 'post_type' => 'post', 'feed' => 'rss2' ), '/?post_type=post&feed=rss2' ),
			'forum page, not a feed'       => array( array( 'post_type' => 'forum' ), '/forums/' ),
		);
	}

	/**
	 * @dataProvider data_requests_the_forum_check_leaves_alone
	 *
	 * @param array  $query_vars  Parsed request query vars.
	 * @param string $request_uri Matching request URI.
	 */
	public function test_forum_check_leaves_other_requests_alone( $query_vars, $request_uri ) {
		$this->require_forums();
		$this->set_request( $request_uri );
		$this->enable_private_rss_feeds();

		$this->assertSame( $query_vars, bb_restricate_rss_feed_forums_request( $query_vars ) );
	}

	public function test_forum_feeds_stay_public_when_rss_feeds_are_public_or_member_is_logged_in() {
		$this->require_forums();
		$query_vars = array( 'post_type' => 'forum', 'feed' => 'feed', 'rest_route' => '/' );
		$this->set_request( '/forums/feed/?rest_route=/' );

		$this->assertSame( $query_vars, bb_restricate_rss_feed_forums_request( $query_vars ), 'Private RSS Feeds off.' );

		$this->enable_private_rss_feeds();
		$this->set_current_user( self::factory()->user->create() );
		$this->assertSame( $query_vars, bb_restricate_rss_feed_forums_request( $query_vars ), 'Logged-in member.' );
	}

	public function test_forum_check_honours_public_rss_feeds_allowlist() {
		$this->require_forums();
		$this->set_request( '/sample-feed-page/rss2' );
		$this->enable_private_rss_feeds();
		add_filter( 'bb_enable_private_rss_feeds_public_content', array( $this, 'public_rss_feeds' ) );

		$this->assertFalse(
			$this->is_blocked(
				function () {
					bb_restricate_rss_feed_forums_request( array( 'post_type' => 'forum', 'feed' => 'rss2' ) );
				}
			)
		);
	}

	/**
	 * Public RSS Feeds allowlist used by the allowlist tests.
	 *
	 * @var string
	 */
	protected $rss_allowlist = '';

	public function rss_allowlist() {
		return $this->rss_allowlist;
	}

	public function data_allowlist_requests() {
		$home = 'http://' . WP_TESTS_DOMAIN;

		return array(
			// Query-string tricks (PROD-10602 C3) — must stay private.
			'entry in a query arg on the site feed'   => array( '/news-feed/feed/', '/?feed=rss2&x=/news-feed/feed', false, true ),
			'entry in a query arg on a forum feed'    => array( '/news-feed/feed/', '/forums/feed/?x=/news-feed/feed', false, true ),
			'entry in a query arg, template path'     => array( '/news-feed/feed/', '/members/rss2?x=/news-feed/feed', true, true ),
			'encoded entry in a query arg'            => array( '/news-feed/feed/', '/?feed=rss2&x=%2Fnews-feed%2Ffeed', false, true ),
			'full-URL entry plus extra arg'           => array( $home . '/?feed=rss2', '/?feed=rss2&x=1', false, true ),
			// Real allowlist matches — must stay public.
			'allowlisted feed'                        => array( '/news-feed/feed/', '/news-feed/feed/', false, false ),
			'allowlisted feed with its own query arg' => array( '/news-feed/feed/', '/news-feed/feed/?paged=2', false, false ),
			'sub-path of an allowlisted entry'        => array( '/news-feed/feed/', '/news-feed/feed/atom/', false, false ),
			'single-segment fragment'                 => array( '/feed/', '/comments/feed/', false, false ),
			'full-URL entry, exact'                   => array( $home . '/?feed=rss2', '/?feed=rss2', false, false ),
			'host entry without a scheme'             => array( WP_TESTS_DOMAIN . '/feed/', '/feed/', false, false ),
		);
	}

	/**
	 * @dataProvider data_allowlist_requests
	 *
	 * @param string $allowlist   Public RSS Feeds entry.
	 * @param string $request_uri Request path and query.
	 * @param bool   $is_feed     Whether the caller already identified a feed (template/forum path).
	 * @param bool   $blocked     Expected outcome.
	 */
	public function test_rss_allowlist_matches_path_not_query_string( $allowlist, $request_uri, $is_feed, $blocked ) {
		$this->set_permalink_structure( '/%postname%/' );
		$this->set_request( $request_uri );
		$this->rss_allowlist = $allowlist;
		add_filter( 'bb_enable_private_rss_feeds_public_content', array( $this, 'rss_allowlist' ) );

		$this->assertSame(
			$blocked,
			$this->is_blocked(
				function () use ( $is_feed ) {
					bb_restricate_rss_feed( $is_feed );
				}
			)
		);

		remove_filter( 'bb_enable_private_rss_feeds_public_content', array( $this, 'rss_allowlist' ) );
	}

	public function test_forum_check_runs_before_the_bbpress_feed_trap() {
		$this->require_forums();
		$this->assertSame( 9, has_filter( 'bbp_request', 'bb_restricate_rss_feed_forums_request' ) );
		$this->assertSame( 10, has_filter( 'bbp_request', 'bbp_request_feed_trap' ) );
	}
}
