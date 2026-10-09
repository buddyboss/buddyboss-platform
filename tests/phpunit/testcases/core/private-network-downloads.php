<?php
/**
 * Private Website and file download links.
 *
 * Document, folder, photo and video download links are handled on `init`, before
 * bp_private_network_template_redirect() runs on `template_redirect`, so they
 * need their own Private Website check.
 *
 * Note: bp_enable_private_network() returns TRUE for a PUBLIC site.
 *
 * @package BuddyBoss\Tests\Core
 */

/**
 * @group core
 * @group private_network
 * @group PROD10601
 */
class BB_Tests_Core_Private_Network_Downloads extends BP_UnitTestCase {

	/**
	 * REQUEST_URI before the test.
	 *
	 * @var string|null
	 */
	protected $request_uri;

	/**
	 * Absolute URL of the faked download request.
	 *
	 * @var string
	 */
	protected $download_url = '';

	/**
	 * Remember REQUEST_URI so tearDown() can restore it.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->request_uri = isset( $_SERVER['REQUEST_URI'] ) ? $_SERVER['REQUEST_URI'] : null;
	}

	/**
	 * Restore the request globals changed by the tests.
	 */
	public function tearDown(): void {
		$_GET = array();

		if ( null === $this->request_uri ) {
			unset( $_SERVER['REQUEST_URI'] );
		} else {
			$_SERVER['REQUEST_URI'] = $this->request_uri;
		}

		parent::tearDown();
	}

	/**
	 * Turn Private Website on.
	 */
	protected function enable_private_website() {
		add_filter( 'bp_enable_private_network', '__return_false' );
	}

	/**
	 * Turn Private Website off.
	 */
	protected function disable_private_website() {
		add_filter( 'bp_enable_private_network', '__return_true' );
	}

	/**
	 * Fake a download request.
	 *
	 * @param array $args Query arguments of the download link.
	 */
	protected function set_download_request( $args ) {
		$_GET                   = $args;
		$_SERVER['REQUEST_URI'] = '/?' . http_build_query( $args );

		// Same URL bp_core_no_access() rebuilds as the redirect_to target.
		$this->download_url = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
	}

	/**
	 * Run a download handler and return where it redirected to.
	 *
	 * The handlers redirect and exit. Throwing from `wp_redirect` (before the
	 * suite-wide `__return_false` callback) stops them before exit().
	 *
	 * @param callable $handler Download handler.
	 *
	 * @return string Redirect location, or '' when the handler did not redirect.
	 */
	protected function get_handler_redirect( $handler ) {
		add_filter(
			'wp_redirect',
			static function ( $location ) {
				throw new Exception( $location );
			},
			1
		);

		try {
			call_user_func( $handler );
		} catch ( Exception $e ) {
			return $e->getMessage();
		}

		return '';
	}

	/**
	 * Read the redirect_to argument of a login URL.
	 *
	 * @param string $location Login URL.
	 *
	 * @return string
	 */
	protected function get_redirect_to( $location ) {
		wp_parse_str( (string) wp_parse_url( $location, PHP_URL_QUERY ), $query );

		return isset( $query['redirect_to'] ) ? $query['redirect_to'] : '';
	}

	/**
	 * The reported bug: a logged-out visitor on a private site must be blocked.
	 */
	public function test_guest_is_restricted_when_private_website_is_on() {
		$this->enable_private_website();
		self::set_current_user( 0 );

		foreach ( array( 'document', 'folder', 'photo', 'video' ) as $type ) {
			$this->assertTrue(
				bb_is_private_network_download_restricted( $type ),
				"Guest was not blocked for type '{$type}'."
			);
		}
	}

	/**
	 * Members still download; bb_media_user_can_access() decides what they get.
	 */
	public function test_logged_in_member_is_not_restricted() {
		$this->enable_private_website();
		self::set_current_user( self::factory()->user->create() );

		$this->assertFalse( bb_is_private_network_download_restricted( 'document' ) );
	}

	/**
	 * Public sites keep their current behaviour for guests.
	 */
	public function test_guest_is_not_restricted_when_private_website_is_off() {
		$this->disable_private_website();
		self::set_current_user( 0 );

		$this->assertFalse( bb_is_private_network_download_restricted( 'document' ) );
	}

	/**
	 * Page-level exceptions must not open downloads. The Events Calendar
	 * compatibility returns true on this filter for any URL with ?ical=1.
	 */
	public function test_private_network_pre_check_does_not_open_downloads() {
		$this->enable_private_website();
		self::set_current_user( 0 );
		add_filter( 'bp_private_network_pre_check', '__return_true' );

		$this->assertTrue( bb_is_private_network_download_restricted( 'document' ) );
	}

	/**
	 * The download filter receives the type and can allow a download.
	 */
	public function test_download_filter_receives_type_and_can_allow() {
		$this->enable_private_website();
		self::set_current_user( 0 );

		$seen = array();
		add_filter(
			'bb_is_private_network_download_restricted',
			static function ( $restricted, $type ) use ( &$seen ) {
				$seen[] = array( $restricted, $type );

				return 'video' === $type ? false : $restricted;
			},
			10,
			2
		);

		$this->assertTrue( bb_is_private_network_download_restricted( 'document' ) );
		$this->assertFalse( bb_is_private_network_download_restricted( 'video' ) );
		$this->assertSame(
			array(
				array( true, 'document' ),
				array( true, 'video' ),
			),
			$seen
		);
	}

	/**
	 * The filter must never block a logged-in member: the login page would send
	 * them straight back to the download URL and loop.
	 */
	public function test_download_filter_cannot_block_logged_in_member() {
		$this->enable_private_website();
		self::set_current_user( self::factory()->user->create() );
		add_filter( 'bb_is_private_network_download_restricted', '__return_true' );

		$this->assertFalse( bb_is_private_network_download_restricted( 'document' ) );
	}

	/**
	 * A guest opening a document or folder link goes to the login page, which
	 * then returns them to the same link.
	 */
	public function test_document_and_folder_links_send_guest_to_login() {
		$this->enable_private_website();
		self::set_current_user( 0 );

		foreach ( array( 'document', 'folder' ) as $document_type ) {
			$this->set_download_request(
				array(
					'attachment'             => '123',
					'document_type'          => $document_type,
					'download_document_file' => '1',
					'document_file'          => '45',
				)
			);

			$location = $this->get_handler_redirect( 'bp_document_download_url_file' );

			$this->assertStringContainsString( 'wp-login.php', $location, "No login redirect for '{$document_type}'." );
			$this->assertSame(
				$this->download_url,
				$this->get_redirect_to( $location ),
				"Login does not return to the '{$document_type}' link."
			);
		}
	}

	/**
	 * A guest opening a photo link goes to the login page.
	 */
	public function test_photo_link_sends_guest_to_login() {
		$this->enable_private_website();
		self::set_current_user( 0 );
		$this->set_download_request(
			array(
				'attachment_id'       => '123',
				'media_type'          => 'media',
				'download_media_file' => '1',
				'media_file'          => '45',
			)
		);

		$location = $this->get_handler_redirect( 'bp_media_download_url_file' );

		$this->assertStringContainsString( 'wp-login.php', $location );
		$this->assertSame( $this->download_url, $this->get_redirect_to( $location ) );
	}

	/**
	 * Without Private Website, guests reach the existing permission check, which
	 * sends them to the home page for an item they cannot download.
	 */
	public function test_document_link_skips_login_when_private_website_is_off() {
		$this->disable_private_website();
		self::set_current_user( 0 );
		$this->set_download_request(
			array(
				'attachment'             => '123',
				'document_type'          => 'document',
				'download_document_file' => '1',
				'document_file'          => '999999',
			)
		);

		$this->assertSame( site_url(), $this->get_handler_redirect( 'bp_document_download_url_file' ) );
	}

	/**
	 * Members on a private site also reach the existing permission check.
	 */
	public function test_document_link_skips_login_for_member() {
		$this->enable_private_website();
		self::set_current_user( self::factory()->user->create() );
		$this->set_download_request(
			array(
				'attachment'             => '123',
				'document_type'          => 'document',
				'download_document_file' => '1',
				'document_file'          => '999999',
			)
		);

		$this->assertSame( site_url(), $this->get_handler_redirect( 'bp_document_download_url_file' ) );
	}
}