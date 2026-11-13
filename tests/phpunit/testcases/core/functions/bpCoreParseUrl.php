<?php

/**
 * Tests for bp_core_parse_url() link-preview resolution.
 *
 * Outbound HTTP is stubbed through `pre_http_request`, which WP_Http applies
 * before URL validation, so these tests make no network calls.
 *
 * @group core
 * @group functions
 * @group bp_core_parse_url
 * @group PROD10875
 */
class BP_Tests_Core_Functions_BpCoreParseUrl extends BP_UnitTestCase {

	/**
	 * Canned responses keyed by the exact URL requested.
	 *
	 * @var array
	 */
	private $pages = array();

	/**
	 * Canned oEmbed JSON bodies keyed by endpoint URL (without query string),
	 * then by the `url` being looked up. Any other lookup gets a 400, as the
	 * real providers answer for URLs they cannot embed.
	 *
	 * @var array
	 */
	private $oembed_endpoints = array();

	/**
	 * Every URL requested during the test, in order.
	 *
	 * @var array
	 */
	private $requested = array();

	public function setUp(): void {
		parent::setUp();

		$this->pages            = array();
		$this->oembed_endpoints = array();
		$this->requested        = array();

		add_filter( 'pre_http_request', array( $this, 'fake_http' ), 10, 3 );
	}

	public function tearDown(): void {
		remove_filter( 'pre_http_request', array( $this, 'fake_http' ), 10 );

		parent::tearDown();
	}

	/**
	 * Serves canned responses; any other request fails.
	 *
	 * @param false|array $pre  Short-circuit value.
	 * @param array       $args Request arguments.
	 * @param string      $url  Request URL.
	 *
	 * @return array|WP_Error
	 */
	public function fake_http( $pre, $args, $url ) {
		$this->requested[] = $url;

		if ( isset( $this->pages[ $url ] ) ) {
			return $this->pages[ $url ];
		}

		$endpoint = strtok( $url, '?' );
		if ( isset( $this->oembed_endpoints[ $endpoint ] ) ) {
			parse_str( (string) wp_parse_url( $url, PHP_URL_QUERY ), $query );
			$lookup = isset( $query['url'] ) ? $query['url'] : '';

			if ( isset( $this->oembed_endpoints[ $endpoint ][ $lookup ] ) ) {
				return $this->response( $url, $this->oembed_endpoints[ $endpoint ][ $lookup ], 'application/json; charset=UTF-8' );
			}

			return $this->response( $url, wp_json_encode( array( 'error' => 'Url was not found: ' . $lookup ) ), 'application/json; charset=UTF-8', 400 );
		}

		return new WP_Error( 'unexpected_request', $url );
	}

	/**
	 * Builds a response shaped like WP_Http::request() output, whose `http_response`
	 * reports the URL the request ended on after following redirects.
	 *
	 * @param string $effective_url URL the request ended on.
	 * @param string $body          Response body.
	 * @param string $content_type  Content-Type header.
	 * @param int    $status        HTTP status code.
	 *
	 * @return array
	 */
	private function response( $effective_url, $body, $content_type = 'text/html; charset=UTF-8', $status = 200 ) {
		$raw                          = new WpOrg\Requests\Response();
		$raw->url                     = $effective_url;
		$raw->status_code             = $status;
		$raw->success                 = $status < 400;
		$raw->body                    = $body;
		$raw->headers['content-type'] = $content_type;

		$http_response             = new WP_HTTP_Requests_Response( $raw );
		$response                  = $http_response->to_array();
		$response['http_response'] = $http_response;

		return $response;
	}

	/**
	 * Returns the `url` argument of each request made to an oEmbed endpoint.
	 *
	 * @param string $endpoint oEmbed endpoint URL.
	 *
	 * @return array
	 */
	private function oembed_lookups( $endpoint ) {
		$lookups = array();

		foreach ( $this->requested as $url ) {
			if ( strtok( $url, '?' ) === $endpoint ) {
				parse_str( (string) wp_parse_url( $url, PHP_URL_QUERY ), $query );
				$lookups[] = $query['url'];
			}
		}

		return $lookups;
	}

	public function data_short_links() {
		return array(
			'reddit share link' => array(
				'https://www.reddit.com/r/whatisit/s/8CP7LYZSiK',
				'https://www.reddit.com/r/whatisit/comments/1xs46wk/found_something_in_my_toilet/?share_id=-6D6Q8oh7jxPwcefkS4zr&utm_content=share_button',
				'<title>Reddit</title>',
				'https://www.reddit.com/oembed',
				// Shape of a live reddit.com/oembed response.
				array(
					'author_name'   => 'jax_20',
					'html'          => '<blockquote class="reddit-embed-bq" style="height:500px" >' . "\n" . '<a href="https://www.reddit.com/r/whatisit/comments/1xs46wk/found_something_in_my_toilet/">Found something in my toilet.</a><br> by' . "\n" . '<a href="https://www.reddit.com/user/jax_20/">u/jax_20</a> in' . "\n" . '<a href="https://www.reddit.com/r/whatisit/">whatisit</a>' . "\n" . '</blockquote>' . "\n" . '<script async src="https://embed.reddit.com/widgets.js" charset="UTF-8"></script>',
					'provider_name' => 'reddit',
					'provider_url'  => 'https://www.reddit.com',
					'title'         => 'Found something in my toilet.',
					'type'          => 'rich',
					'height'        => 500,
				),
				'<blockquote class="reddit-embed-bq"',
			),
			'tiktok short link' => array(
				'https://vm.tiktok.com/ZN8PrWmMJ/',
				// The short link redirects to the video URL with `_r` and `_t` query parameters (values illustrative).
				'https://www.tiktok.com/@world.presidents0/video/7701046078031908118?_r=1&_t=ZN8PrWmMJ',
				'<title>TikTok - Make Your Day</title>',
				'https://www.tiktok.com/oembed',
				// Shape of a live tiktok.com/oembed response (2026-11-13); the player src carries a signed query string in reality.
				array(
					'version'          => '1.0',
					'type'             => 'video',
					'width'            => '325',
					'height'           => '578',
					'html'             => '<iframe height="578" width="325" src="https://www.tiktok.com/player/v1/7701046078031908118" frameborder="0" allowfullscreen loading="lazy"></iframe>',
					'provider_url'     => 'https://www.tiktok.com',
					'provider_name'    => 'TikTok',
					'embed_product_id' => '7701046078031908118',
					'embed_type'       => 'video',
				),
				'https://www.tiktok.com/player/v1/7701046078031908118',
			),
		);
	}

	/**
	 * @dataProvider data_short_links
	 */
	public function test_short_link_redirecting_to_an_oembed_provider_returns_the_embed( $short_url, $resolved_url, $page_head, $endpoint, $oembed, $embed_marker ) {
		$this->pages[ $short_url ]           = $this->response( $resolved_url, '<html><head>' . $page_head . '</head><body></body></html>' );
		$this->oembed_endpoints[ $endpoint ] = array( $resolved_url => wp_json_encode( $oembed ) );

		$data = bp_core_parse_url( $short_url );

		$this->assertArrayHasKey( 'wp_embed', $data, 'The short link should resolve to the provider embed, not a scraped card.' );
		$this->assertTrue( $data['wp_embed'] );
		$this->assertSame( ' ', $data['title'] );
		$this->assertStringContainsString( $embed_marker, $data['description'] );
		$this->assertSame( array( $resolved_url ), $this->oembed_lookups( $endpoint ), 'The provider should be asked about the URL the short link redirected to.' );
	}

	public function test_pinterest_share_link_is_embedded_through_the_canonical_pin_url() {
		$short_url    = 'https://pin.it/21lcySiTX';
		$resolved_url = 'https://at.pinterest.com/pin/987836499545630251/sent/?invite_code=f79c5f5f189f4faab8d018b966f34acc&sender=159033567999954022&sfo=1';
		$pin_url      = 'https://www.pinterest.com/pin/987836499545630251/';

		$this->pages[ $short_url ] = $this->response( $resolved_url, '<html><head><title>面白い猫の動画 | 動物のおもしろ動画, 可愛すぎる動物, ネコ</title></head><body></body></html>' );
		// Pinterest's oEmbed answers for the canonical pin URL only; the "/sent/" share URL gets a 400.
		$this->oembed_endpoints['https://www.pinterest.com/oembed.json'] = array(
			// Shape of a live pinterest.com/oembed.json response.
			$pin_url => wp_json_encode(
				array(
					'version'          => '1.0',
					'type'             => 'rich',
					'provider_name'    => 'Pinterest',
					'provider_url'     => 'https://www.pinterest.com',
					'width'            => 450,
					'height'           => 697,
					'title'            => '“I love cats not dancing cats.”',
					'author_name'      => 'Valeria',
					'author_url'       => 'https://www.pinterest.com/valeria27780947/',
					'html'             => '<iframe src="https://assets.pinterest.com/ext/embed.html?id=987836499545630251&src=oembed" height="697" width="450" frameborder="0" scrolling="no" ></iframe>',
					'thumbnail_url'    => 'https://i.pinimg.com/236x/18/73/3c/18733c19bd7f14cd6aeaadb8fb08b967.jpg',
					'thumbnail_width'  => 236,
					'thumbnail_height' => 313,
				)
			),
		);

		$data = bp_core_parse_url( $short_url );

		$this->assertArrayHasKey( 'wp_embed', $data, 'The Pinterest share link should resolve to the pin embed, not the scraped card.' );
		$this->assertTrue( $data['wp_embed'] );
		$this->assertStringContainsString( 'https://assets.pinterest.com/ext/embed.html?id=987836499545630251', $data['description'] );
		$this->assertSame( array( $pin_url ), $this->oembed_lookups( 'https://www.pinterest.com/oembed.json' ), 'Pinterest should only be asked about the canonical pin URL.' );
	}

	public function test_redirect_to_a_page_without_an_oembed_provider_keeps_the_scraped_card() {
		$url                 = 'http://example.net/old-page';
		$this->pages[ $url ] = $this->response( 'https://example.net/new-page', '<html><head><meta property="og:title" content="Example Article"><meta property="og:description" content="About the example."></head><body></body></html>' );

		$data = bp_core_parse_url( $url );

		$this->assertArrayNotHasKey( 'wp_embed', $data );
		$this->assertSame( 'Example Article', $data['title'] );
		$this->assertSame( 'About the example.', $data['description'] );
		$this->assertSame( array( $url ), $this->requested, 'No oEmbed lookup should be made for a page no provider handles.' );
	}

	public function test_failed_request_returns_no_data_and_makes_no_oembed_lookup() {
		$url                 = 'https://example.net/timed-out';
		$this->pages[ $url ] = new WP_Error( 'http_request_failed', 'Operation timed out' );

		$data = bp_core_parse_url( $url );

		$this->assertSame( array(), $data );
		$this->assertSame( array( $url ), $this->requested );
	}

	public function test_same_site_link_redirecting_to_a_post_keeps_the_scraped_card() {
		$post_id             = self::factory()->post->create( array( 'post_title' => 'Same Site Post' ) );
		$url                 = home_url( '/go/same-site-post/' );
		$this->pages[ $url ] = $this->response( get_permalink( $post_id ), '<html><head><meta property="og:title" content="Same Site Post"></head><body></body></html>' );

		$data = bp_core_parse_url( $url );

		$this->assertArrayNotHasKey( 'wp_embed', $data, 'A same-site link should stay a link card instead of becoming a WordPress post embed.' );
		$this->assertSame( 'Same Site Post', $data['title'] );
	}
}
