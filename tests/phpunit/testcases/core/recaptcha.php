<?php
/**
 * reCAPTCHA integration tests.
 *
 * Covers the PHP side of PROD-10455: the markup and localized data the
 * front-end submit-time token refresh depends on, and the fail-closed server
 * contract it protects visitors from hitting with an empty token.
 *
 * @group core
 * @group recaptcha
 */
class BB_Tests_Core_Recaptcha extends BP_UnitTestCase {

	/**
	 * Option value before the test.
	 *
	 * @var mixed
	 */
	protected $old_option;

	/**
	 * A connected v3 configuration with every action enabled.
	 *
	 * @var array
	 */
	protected $v3_settings = array(
		'recaptcha_version' => 'recaptcha_v3',
		'v2_option'         => 'v2_checkbox',
		'site_key'          => 'test-site-key-10455',
		'secret_key'        => 'test-secret-key-10455',
		'connection_status' => 'connected',
		'enabled_for'       => array(
			'bb_login'         => 1,
			'bb_register'      => 1,
			'bb_lost_password' => 1,
			'bb_activate'      => 1,
		),
	);

	public function set_up() {
		parent::set_up();
		$this->old_option = bp_get_option( 'bb_recaptcha', array() );
		$this->reset_scripts();
	}

	public function tear_down() {
		bp_update_option( 'bb_recaptcha', $this->old_option );
		$this->reset_scripts();
		remove_action( 'login_footer', 'bb_recaptcha_add_scripts_login_footer' );
		remove_action( 'wp_footer', 'bb_recaptcha_add_scripts_login_footer' );
		parent::tear_down();
	}

	/**
	 * Drop the handles bb_recaptcha_display() registers so each test starts clean.
	 */
	protected function reset_scripts() {
		wp_deregister_script( 'bb-recaptcha' );
		wp_deregister_script( 'bb-recaptcha-api' );
		wp_dequeue_style( 'bb-recaptcha' );
		wp_deregister_style( 'bb-recaptcha' );
	}

	/**
	 * Run bb_recaptcha_display() and return what it printed.
	 *
	 * @param string $action reCAPTCHA action.
	 * @return string
	 */
	protected function render( $action ) {
		ob_start();
		bb_recaptcha_display( $action );
		return ob_get_clean();
	}

	/**
	 * Decode the bbRecaptcha object localized on the bb-recaptcha handle.
	 *
	 * @return array|null
	 */
	protected function localized_data() {
		$data = wp_scripts()->get_data( 'bb-recaptcha', 'data' );
		$this->assertIsString( $data, 'bb-recaptcha has no localized data.' );
		$this->assertMatchesRegularExpression( '/^var bbRecaptcha = (.+);$/s', $data );
		preg_match( '/^var bbRecaptcha = (.+);$/s', $data, $m );
		return json_decode( $m[1], true );
	}

	public function test_display_prints_the_v3_response_field_the_script_writes_into() {
		bp_update_option( 'bb_recaptcha', $this->v3_settings );

		$html = $this->render( 'bb_login' );

		$this->assertStringContainsString( 'id="bb_recaptcha_response_id"', $html );
		$this->assertStringContainsString( 'name="g-recaptcha-response"', $html );
		$this->assertStringContainsString( 'type="hidden"', $html );
	}

	public function test_display_registers_google_api_in_v3_render_mode_and_the_handler_script() {
		bp_update_option( 'bb_recaptcha', $this->v3_settings );

		$this->render( 'bb_login' );
		$scripts = wp_scripts();

		$api = $scripts->query( 'bb-recaptcha-api', 'registered' );
		$this->assertInstanceOf( '_WP_Dependency', $api );
		$this->assertStringContainsString( 'google.com/recaptcha/api.js', $api->src );
		$this->assertStringContainsString( 'render=test-site-key-10455', $api->src );

		$handler = $scripts->query( 'bb-recaptcha', 'registered' );
		$this->assertInstanceOf( '_WP_Dependency', $handler );
		$this->assertStringContainsString( 'bb-features/integrations/recaptcha/assets/js/bb-recaptcha', $handler->src );
		$this->assertContains( 'jquery', $handler->deps );
		$this->assertContains( 'bb-recaptcha-api', $handler->deps, 'Handler must load after Google api.js.' );
	}

	/**
	 * @dataProvider provider_actions
	 */
	public function test_display_localizes_the_action_and_both_submit_time_notice_strings( $action ) {
		bp_update_option( 'bb_recaptcha', $this->v3_settings );

		$this->render( $action );
		$data = $this->localized_data();

		$this->assertSame( 'recaptcha_v3', $data['data']['selected_version'] );
		$this->assertSame( 'test-site-key-10455', $data['data']['site_key'] );
		$this->assertSame( $action, $data['data']['action'] );

		$this->assertArrayHasKey( 'i18n', $data['data'] );
		$this->assertArrayHasKey( 'script_failed', $data['data']['i18n'] );
		$this->assertArrayHasKey( 'token_failed', $data['data']['i18n'] );
		$this->assertNotSame( '', $data['data']['i18n']['script_failed'] );
		$this->assertNotSame( '', $data['data']['i18n']['token_failed'] );
		$this->assertNotSame( $data['data']['i18n']['script_failed'], $data['data']['i18n']['token_failed'] );
	}

	public function provider_actions() {
		return array(
			array( 'bb_login' ),
			array( 'bb_lost_password' ),
			array( 'bb_register' ),
			array( 'bb_activate' ),
		);
	}

	public function test_display_notice_strings_are_translatable_and_match_the_source_strings() {
		bp_update_option( 'bb_recaptcha', $this->v3_settings );

		$this->render( 'bb_login' );
		$data = $this->localized_data();

		$this->assertSame(
			__( 'The security check could not be loaded. Please reload the page and try again.', 'buddyboss' ),
			$data['data']['i18n']['script_failed']
		);
		$this->assertSame(
			__( 'The security check could not be completed. Please try again.', 'buddyboss' ),
			$data['data']['i18n']['token_failed']
		);
	}

	public function test_display_prints_nothing_and_registers_nothing_when_not_connected() {
		$settings                      = $this->v3_settings;
		$settings['connection_status'] = 'not_connected';
		bp_update_option( 'bb_recaptcha', $settings );

		$html = $this->render( 'bb_login' );

		$this->assertSame( '', trim( $html ) );
		$this->assertFalse( wp_scripts()->query( 'bb-recaptcha', 'registered' ) );
		$this->assertFalse( wp_scripts()->query( 'bb-recaptcha-api', 'registered' ) );
	}

	public function test_display_prints_nothing_for_an_empty_action() {
		bp_update_option( 'bb_recaptcha', $this->v3_settings );

		$this->assertSame( '', $this->render( '' ) );
		$this->assertFalse( wp_scripts()->query( 'bb-recaptcha', 'registered' ) );
	}

	public function test_display_prints_nothing_when_the_visitor_ip_is_excluded() {
		$settings               = $this->v3_settings;
		$settings['exclude_ip'] = '203.0.113.7';
		bp_update_option( 'bb_recaptcha', $settings );

		$old_ip                 = isset( $_SERVER['REMOTE_ADDR'] ) ? $_SERVER['REMOTE_ADDR'] : null;
		$_SERVER['REMOTE_ADDR'] = '203.0.113.7';

		$html = $this->render( 'bb_login' );

		if ( null === $old_ip ) {
			unset( $_SERVER['REMOTE_ADDR'] );
		} else {
			$_SERVER['REMOTE_ADDR'] = $old_ip;
		}

		$this->assertSame( '', trim( $html ) );
		$this->assertFalse( wp_scripts()->query( 'bb-recaptcha', 'registered' ) );
	}

	public function test_login_form_hook_prints_the_field_and_schedules_the_footer_enqueue() {
		bp_update_option( 'bb_recaptcha', $this->v3_settings );

		ob_start();
		bb_recaptcha_login();
		$html = ob_get_clean();

		$this->assertStringContainsString( 'id="bb_recaptcha_response_id"', $html );
		$this->assertNotFalse( has_action( 'login_footer', 'bb_recaptcha_add_scripts_login_footer' ) );
	}

	public function test_login_form_hook_prints_nothing_when_login_is_not_enabled() {
		$settings                            = $this->v3_settings;
		$settings['enabled_for']['bb_login'] = 0;
		bp_update_option( 'bb_recaptcha', $settings );

		ob_start();
		bb_recaptcha_login();
		$html = ob_get_clean();

		$this->assertSame( '', $html );
		$this->assertFalse( has_action( 'login_footer', 'bb_recaptcha_add_scripts_login_footer' ) );
	}

	public function test_wp_login_form_embed_receives_the_same_field_and_schedules_the_site_footer_enqueue() {
		bp_update_option( 'bb_recaptcha', $this->v3_settings );

		$content = bb_recaptcha_wp_login_form( '<p>before</p>' );

		$this->assertStringStartsWith( '<p>before</p>', $content );
		$this->assertStringContainsString( 'name="g-recaptcha-response"', $content );
		$this->assertNotFalse( has_action( 'wp_footer', 'bb_recaptcha_add_scripts_login_footer' ) );
	}

	public function test_wp_login_form_embed_is_untouched_when_not_connected() {
		$settings                      = $this->v3_settings;
		$settings['connection_status'] = 'not_connected';
		bp_update_option( 'bb_recaptcha', $settings );

		$this->assertSame( '<p>before</p>', bb_recaptcha_wp_login_form( '<p>before</p>' ) );
		$this->assertFalse( has_action( 'wp_footer', 'bb_recaptcha_add_scripts_login_footer' ) );
	}

	/**
	 * The server contract the front-end fix exists to keep visitors away from:
	 * an empty token is an unsolved challenge, never a pass.
	 *
	 * @dataProvider provider_actions
	 */
	public function test_verification_fails_closed_when_the_token_is_missing( $action ) {
		bp_update_option( 'bb_recaptcha', $this->v3_settings );

		$result = bb_recaptcha_verification_front( $action );

		$this->assertInstanceOf( 'WP_Error', $result );
		$this->assertSame( 'bb_recaptcha_token_missing', $result->get_error_code() );
		$this->assertSame( __( 'Google reCAPTCHA token is missing.', 'buddyboss' ), $result->get_error_message() );
	}

	public function test_verification_fails_closed_for_v2_too_when_the_token_is_missing() {
		$settings                      = $this->v3_settings;
		$settings['recaptcha_version'] = 'recaptcha_v2';
		bp_update_option( 'bb_recaptcha', $settings );

		$result = bb_recaptcha_verification_front( 'bb_login' );

		$this->assertInstanceOf( 'WP_Error', $result );
		$this->assertSame( 'bb_recaptcha_token_missing', $result->get_error_code() );
	}

	public function test_verification_is_skipped_for_an_excluded_ip() {
		$settings               = $this->v3_settings;
		$settings['exclude_ip'] = '203.0.113.7';
		bp_update_option( 'bb_recaptcha', $settings );

		$old_ip                 = isset( $_SERVER['REMOTE_ADDR'] ) ? $_SERVER['REMOTE_ADDR'] : null;
		$_SERVER['REMOTE_ADDR'] = '203.0.113.7';

		$result = bb_recaptcha_verification_front( 'bb_login' );

		if ( null === $old_ip ) {
			unset( $_SERVER['REMOTE_ADDR'] );
		} else {
			$_SERVER['REMOTE_ADDR'] = $old_ip;
		}

		$this->assertTrue( $result );
	}

	/**
	 * Production loads bb-recaptcha.min.js; a hand-edited source with a stale
	 * twin ships the old behaviour while every SCRIPT_DEBUG check looks green.
	 */
	public function test_minified_twin_carries_the_submit_time_refresh() {
		$dir    = buddypress()->plugin_dir . 'bb-features/integrations/recaptcha/assets/js/';
		$source = file_get_contents( $dir . 'bb-recaptcha.js' );
		$min    = file_get_contents( $dir . 'bb-recaptcha.min.js' );

		foreach ( array( 'setupV3', 'v3Submit', 'v3Token', 'requestSubmit', 'bb-recaptcha-notice', 'g-recaptcha-response', 'script_failed', 'token_failed' ) as $marker ) {
			$this->assertStringContainsString( $marker, $source, "Source lost marker {$marker}." );
			$this->assertStringContainsString( $marker, $min, "Minified twin lost marker {$marker} - rebuild with uglifyjs -c -m." );
		}
		$this->assertStringNotContainsString( "trigger( 'click' )", substr( $source, strpos( $source, 'setupV3:' ) ), 'setupV3 must not re-dispatch a click (PROD-10455 round 2).' );
	}
}
