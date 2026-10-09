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
}
