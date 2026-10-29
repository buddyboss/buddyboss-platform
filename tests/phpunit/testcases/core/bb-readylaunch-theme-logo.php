<?php
/**
 * ReadyLaunch logo resolution (PROD-10670 S4-B).
 *
 * The logo option stores { id, url, alt, title }. The URL must be resolved from the
 * attachment ID so a deleted attachment does not render a broken <img>; templates
 * then fall back to the site title.
 *
 * @group core
 * @group bb_readylaunch_logo
 */
class BB_Tests_Readylaunch_Theme_Logo extends BP_UnitTestCase {

	/**
	 * Path to the ReadyLaunch templates.
	 *
	 * @var string
	 */
	protected $rl_dir;

	public function setUp(): void {
		parent::setUp();

		if ( ! function_exists( 'bb_load_readylaunch' ) || ! bb_load_readylaunch() ) {
			$this->markTestSkipped( 'ReadyLaunch class is not loaded.' );
		}

		$this->rl_dir = buddypress()->plugin_dir . 'bp-templates/bp-nouveau/readylaunch/';
	}

	public function tearDown(): void {
		bp_delete_option( 'bb_rl_light_logo' );
		bp_delete_option( 'bb_rl_dark_logo' );
		bp_delete_option( 'bb_rl_theme_mode' );

		parent::tearDown();
	}

	/**
	 * Create an image attachment.
	 *
	 * @return int
	 */
	protected function make_attachment() {
		return self::factory()->attachment->create_object(
			'bb-rl-logo.png',
			0,
			array(
				'post_mime_type' => 'image/png',
				'post_type'      => 'attachment',
			)
		);
	}

	public function test_url_is_resolved_from_attachment() {
		$id = $this->make_attachment();
		bp_update_option(
			'bb_rl_light_logo',
			array(
				'id'    => $id,
				'url'   => 'https://cdn.example.test/stale.png',
				'alt'   => '',
				'title' => 'Logo',
			)
		);

		$logo = bb_load_readylaunch()->bb_rl_get_theme_logo( 'light' );

		$this->assertSame( wp_get_attachment_url( $id ), $logo['url'] );
		$this->assertSame( 'Logo', $logo['title'] );
		$this->assertSame( $id, $logo['id'] );
	}

	public function test_deleted_attachment_returns_empty() {
		$id = $this->make_attachment();
		bp_update_option(
			'bb_rl_dark_logo',
			array(
				'id'    => $id,
				'url'   => 'https://cdn.example.test/gone.png',
				'alt'   => '',
				'title' => 'image-12',
			)
		);
		wp_delete_attachment( $id, true );

		$this->assertSame( array(), bb_load_readylaunch()->bb_rl_get_theme_logo( 'dark' ) );

		// The stored option is left untouched.
		$stored = bp_get_option( 'bb_rl_dark_logo' );
		$this->assertSame( 'https://cdn.example.test/gone.png', $stored['url'] );
	}

	public function test_legacy_integer_id_is_resolved() {
		$id = $this->make_attachment();
		bp_update_option( 'bb_rl_light_logo', $id );

		$logo = bb_load_readylaunch()->bb_rl_get_theme_logo( 'light' );

		$this->assertSame( wp_get_attachment_url( $id ), $logo['url'] );
		$this->assertSame( '', $logo['title'] );
	}

	public function test_url_without_id_is_kept() {
		bp_update_option(
			'bb_rl_light_logo',
			array(
				'url'   => home_url( '/logo.png' ),
				'title' => 'Logo',
			)
		);

		$logo = bb_load_readylaunch()->bb_rl_get_theme_logo( 'light' );

		$this->assertSame( home_url( '/logo.png' ), $logo['url'] );
		$this->assertSame( '', $logo['alt'] );
	}

	public function test_no_logo_returns_empty() {
		$this->assertSame( array(), bb_load_readylaunch()->bb_rl_get_theme_logo( 'light' ) );
	}

	public function test_header_falls_back_to_site_title_for_deleted_logo() {
		$id = $this->make_attachment();
		bp_update_option( 'bb_rl_theme_mode', 'choice' );
		bp_update_option(
			'bb_rl_light_logo',
			array(
				'id'    => $id,
				'url'   => 'https://cdn.example.test/gone.png',
				'alt'   => '',
				'title' => 'image-11',
			)
		);
		wp_delete_attachment( $id, true );

		$html = $this->render( 'header/site-logo.php' );

		$this->assertStringNotContainsString( 'gone.png', $html );
		$this->assertStringContainsString( 'bb-rl-light-logo-text', $html );
	}

	public function test_register_header_falls_back_to_site_title_for_deleted_logo() {
		$id = $this->make_attachment();
		bp_update_option(
			'bb_rl_light_logo',
			array(
				'id'    => $id,
				'url'   => 'https://cdn.example.test/gone.png',
				'alt'   => '',
				'title' => 'image-11',
			)
		);
		wp_delete_attachment( $id, true );

		$html = $this->render( 'common/header-register.php' );

		$this->assertStringNotContainsString( 'gone.png', $html );
		$this->assertStringContainsString( '<h2>', $html );
	}

	public function test_register_header_uses_attachment_url() {
		$id = $this->make_attachment();
		bp_update_option(
			'bb_rl_light_logo',
			array(
				'id'    => $id,
				'url'   => 'https://cdn.example.test/stale.png',
				'alt'   => '',
				'title' => 'Logo',
			)
		);

		$html = $this->render( 'common/header-register.php' );

		$this->assertStringNotContainsString( 'stale.png', $html );
		$this->assertStringContainsString( esc_url( wp_get_attachment_url( $id ) ), $html );
	}

	/**
	 * Render a ReadyLaunch template file.
	 *
	 * @param string $file Path relative to the ReadyLaunch directory.
	 *
	 * @return string
	 */
	protected function render( $file ) {
		ob_start();
		include $this->rl_dir . $file;

		return ob_get_clean();
	}
}
