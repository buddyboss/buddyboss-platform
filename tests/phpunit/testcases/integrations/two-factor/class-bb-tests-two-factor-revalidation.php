<?php
/**
 * Two-Factor integration: Security URL, revalidation return and string override.
 *
 * @since BuddyBoss 3.6.0
 * @package BuddyBoss\Tests
 */

// Shared base class.
require_once dirname( dirname( dirname( __DIR__ ) ) ) . '/includes/testcase-two-factor.php';

/**
 * Security URL, revalidation return and string override.
 *
 * @group two-factor
 */
class BB_Tests_Two_Factor_Revalidation extends BB_Two_Factor_UnitTestCase {

	/**
	 * Member under test.
	 *
	 * @var int
	 */
	protected $member;

	/**
	 * Create and log in the member under test.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->member = $this->create_member( 'tfa_r2_test_reval' );
		self::set_current_user( $this->member );
	}

	/**
	 * Put Platform's Login Redirect override back where core registers it.
	 */
	public function tearDown(): void {
		if ( ! has_filter( 'bp_login_redirect', 'bb_login_redirect' ) ) {
			add_filter( 'bp_login_redirect', 'bb_login_redirect', PHP_INT_MAX, 3 );
		}

		remove_filter( 'gettext', 'bb_two_factor_filter_plugin_strings', 10 );

		parent::tearDown();
	}

	// Security URL.

	/**
	 * Settings url for member.
	 */
	public function test_settings_url_for_member() {
		$this->assertSame(
			'http://example.org/members/tfa_r2_test_reval/settings/security/',
			bb_two_factor_get_settings_url( $this->member )
		);
	}

	/**
	 * Settings url falls back to logged in member.
	 */
	public function test_settings_url_falls_back_to_logged_in_member() {
		$this->assertStringEndsWith( '/settings/security/', bb_two_factor_get_settings_url() );
		$this->assertSame( 'http://example.org/members/tfa_r2_test_reval/settings/security/', bb_two_factor_get_settings_url() );
	}

	/**
	 * Settings url empty for logged out with no member.
	 */
	public function test_settings_url_empty_for_logged_out_with_no_member() {
		self::set_current_user( 0 );

		$this->assertSame( '', bb_two_factor_get_settings_url() );
	}

	// Revalidate link rewrite.

	/**
	 * Set revalidate return rewrites only the revalidate href.
	 */
	public function test_set_revalidate_return_rewrites_only_the_revalidate_href() {
		$html = '<p class="intro">Keep <a href="http://example.org/keep-me/">this link</a>.</p>'
			. '<a class="button" href="http://example.org/wp-login.php?action=revalidate_2fa&amp;redirect_to=http%3A%2F%2Fexample.org%2Fwp-admin%2Fprofile.php">Revalidate now</a>';

		$result = bb_two_factor_set_revalidate_return( $html, 'http://example.org/members/tfa_r2_test_reval/settings/security/' );

		$this->assertSame(
			'<p class="intro">Keep <a href="http://example.org/keep-me/">this link</a>.</p>'
			. '<a class="button" href="http://example.org/wp-login.php?action=revalidate_2fa&#038;redirect_to=http%3A%2F%2Fexample.org%2Fmembers%2Ftfa_r2_test_reval%2Fsettings%2Fsecurity%2F">Revalidate now</a>',
			$result
		);
		$this->assertStringNotContainsString( 'wp-admin', $result );
	}

	/**
	 * Set revalidate return leaves html without revalidate link.
	 */
	public function test_set_revalidate_return_leaves_html_without_revalidate_link() {
		$html = '<p>No link here. <a href="http://example.org/other/">other</a></p>';

		$this->assertSame( $html, bb_two_factor_set_revalidate_return( $html, 'http://example.org/members/tfa_r2_test_reval/settings/security/' ) );
	}

	/**
	 * Set revalidate return leaves html when return url empty.
	 */
	public function test_set_revalidate_return_leaves_html_when_return_url_empty() {
		$html = '<a href="http://example.org/wp-login.php?action=revalidate_2fa">Revalidate now</a>';

		$this->assertSame( $html, bb_two_factor_set_revalidate_return( $html, '' ) );
	}

	// Revalidation return vs Login Redirect (F-3).

	/**
	 * Keep revalidation return is hooked.
	 */
	public function test_keep_revalidation_return_is_hooked() {
		$this->assertSame( 10, has_action( 'two_factor_user_revalidated', 'bb_two_factor_keep_revalidation_return' ) );
	}

	/**
	 * Login redirect override registered by default.
	 */
	public function test_login_redirect_override_registered_by_default() {
		$this->assertSame( PHP_INT_MAX, has_filter( 'bp_login_redirect', 'bb_login_redirect' ) );
	}

	/**
	 * Own security url lifts login redirect override.
	 */
	public function test_own_security_url_lifts_login_redirect_override() {
		$_REQUEST['redirect_to'] = 'http://example.org/members/tfa_r2_test_reval/settings/security/';

		do_action( 'two_factor_user_revalidated', get_userdata( $this->member ) );

		$this->assertFalse( has_filter( 'bp_login_redirect', 'bb_login_redirect' ) );
	}

	/**
	 * Own security url without trailing slash lifts override.
	 */
	public function test_own_security_url_without_trailing_slash_lifts_override() {
		$_REQUEST['redirect_to'] = 'http://example.org/members/tfa_r2_test_reval/settings/security';

		bb_two_factor_keep_revalidation_return( get_userdata( $this->member ) );

		$this->assertFalse( has_filter( 'bp_login_redirect', 'bb_login_redirect' ) );
	}

	/**
	 * Other url keeps login redirect override.
	 */
	public function test_other_url_keeps_login_redirect_override() {
		$_REQUEST['redirect_to'] = 'http://example.org/wp-admin/profile.php';

		bb_two_factor_keep_revalidation_return( get_userdata( $this->member ) );

		$this->assertSame( PHP_INT_MAX, has_filter( 'bp_login_redirect', 'bb_login_redirect' ) );
	}

	/**
	 * Another members security url keeps login redirect override.
	 */
	public function test_another_members_security_url_keeps_login_redirect_override() {
		$this->create_member( 'tfa_r2_test_reval_other' );
		$_REQUEST['redirect_to'] = 'http://example.org/members/tfa_r2_test_reval_other/settings/security/';

		bb_two_factor_keep_revalidation_return( get_userdata( $this->member ) );

		$this->assertSame( PHP_INT_MAX, has_filter( 'bp_login_redirect', 'bb_login_redirect' ) );
	}

	/**
	 * Missing redirect to keeps login redirect override.
	 */
	public function test_missing_redirect_to_keeps_login_redirect_override() {
		unset( $_REQUEST['redirect_to'] );

		bb_two_factor_keep_revalidation_return( get_userdata( $this->member ) );

		$this->assertSame( PHP_INT_MAX, has_filter( 'bp_login_redirect', 'bb_login_redirect' ) );
	}

	/**
	 * Non user argument keeps login redirect override.
	 */
	public function test_non_user_argument_keeps_login_redirect_override() {
		$_REQUEST['redirect_to'] = 'http://example.org/members/tfa_r2_test_reval/settings/security/';

		bb_two_factor_keep_revalidation_return( $this->member );

		$this->assertSame( PHP_INT_MAX, has_filter( 'bp_login_redirect', 'bb_login_redirect' ) );
	}

	// String override (F-7).

	/**
	 * String filter rewords defined above for two factor domain.
	 */
	public function test_string_filter_rewords_defined_above_for_two_factor_domain() {
		$this->assertSame(
			'Authentication for the REST API and XML-RPC must use an application password instead of your regular password.',
			bb_two_factor_filter_plugin_strings(
				'translated',
				'Authentication for REST API and XML-RPC must use application passwords (defined above) instead of your regular password.',
				'two-factor'
			)
		);
	}

	/**
	 * String filter ignores other domains.
	 */
	public function test_string_filter_ignores_other_domains() {
		$this->assertSame(
			'translated',
			bb_two_factor_filter_plugin_strings(
				'translated',
				'Authentication for REST API and XML-RPC must use application passwords (defined above) instead of your regular password.',
				'default'
			)
		);
	}

	/**
	 * String filter ignores other two factor strings.
	 */
	public function test_string_filter_ignores_other_two_factor_strings() {
		$this->assertSame( 'translated', bb_two_factor_filter_plugin_strings( 'translated', 'Recovery Codes', 'two-factor' ) );
	}

	/**
	 * String filter not attached outside render.
	 */
	public function test_string_filter_not_attached_outside_render() {
		$this->assertFalse( has_filter( 'gettext', 'bb_two_factor_filter_plugin_strings' ) );
	}

	/**
	 * Control: the plugin's own renderer still prints the wp-admin wording.
	 */
	public function test_plugin_renderer_prints_defined_above_without_the_override() {
		add_filter( 'wp_is_application_passwords_available', '__return_true' );

		ob_start();
		Two_Factor_Core::user_two_factor_options( get_userdata( $this->member ) );
		$html = ob_get_clean();

		$this->assertStringContainsString( 'application passwords (defined above)', $html );
	}

	/**
	 * Render options rewords string and detaches filter.
	 */
	public function test_render_options_rewords_string_and_detaches_filter() {
		add_filter( 'wp_is_application_passwords_available', '__return_true' );

		ob_start();
		bb_two_factor_render_options( get_userdata( $this->member ) );
		$html = ob_get_clean();

		$this->assertStringNotContainsString( 'defined above', $html );
		$this->assertStringContainsString( 'must use an application password instead of your regular password.', $html );
		$this->assertFalse( has_filter( 'gettext', 'bb_two_factor_filter_plugin_strings' ) );
	}

	/**
	 * Render options prints nothing when feature off.
	 */
	public function test_render_options_prints_nothing_when_feature_off() {
		bp_update_option( 'bb-active-features', array( 'two-factor' => 0 ) );

		ob_start();
		bb_two_factor_render_options( get_userdata( $this->member ) );
		$html = ob_get_clean();

		$this->assertSame( '', $html );
	}
}
