<?php
/**
 * ReadyLaunch WC4BP integration (PROD-10487).
 *
 * The member profile Shop tabs render blank under ReadyLaunch when the WC4BP
 * template directory resolves to a folder that doesn't exist, and render without
 * any form fields when the WooCommerce shortcode output is passed through
 * wp_kses_post().
 *
 * @group core
 * @group bb_readylaunch_wc4bp
 */
class BB_Tests_Readylaunch_WC4BP_Helper extends BP_UnitTestCase {

	/**
	 * Original buddypress()->plugin_dir value.
	 *
	 * @var string
	 */
	protected $plugin_dir;

	/**
	 * Shortcodes replaced by a test, keyed by tag, with their original callback (or null).
	 *
	 * @var array
	 */
	protected $replaced_shortcodes = array();

	public function setUp(): void {
		parent::setUp();

		if ( ! class_exists( 'BB_Readylaunch_WC4BP_Helper' ) ) {
			require_once buddypress()->compatibility_dir . 'class-bb-readylaunch-wc4bp-helper.php';
		}

		$this->plugin_dir = buddypress()->plugin_dir;
	}

	public function tearDown(): void {
		buddypress()->plugin_dir = $this->plugin_dir;

		foreach ( $this->replaced_shortcodes as $tag => $callback ) {
			remove_shortcode( $tag );

			if ( null !== $callback ) {
				add_shortcode( $tag, $callback );
			}
		}

		parent::tearDown();
	}

	/**
	 * Release builds ship the plugin without a src/ folder.
	 */
	public function test_template_directory_in_release_layout() {
		buddypress()->plugin_dir = '/srv/www/wp-content/plugins/buddyboss-platform/';

		$this->assertSame(
			'/srv/www/wp-content/plugins/buddyboss-platform/bp-templates/bp-nouveau/readylaunch/wc4bp',
			BB_Readylaunch_WC4BP_Helper::bb_rl_wc4bp_get_template_directory()
		);
	}

	/**
	 * Source checkouts load the plugin from the src/ folder.
	 */
	public function test_template_directory_in_source_layout() {
		buddypress()->plugin_dir = '/srv/www/wp-content/plugins/buddyboss-platform/src/';

		$this->assertSame(
			'/srv/www/wp-content/plugins/buddyboss-platform/src/bp-templates/bp-nouveau/readylaunch/wc4bp',
			BB_Readylaunch_WC4BP_Helper::bb_rl_wc4bp_get_template_directory()
		);
	}

	/**
	 * The resolved directory is the one that ships the Shop templates.
	 */
	public function test_template_directory_contains_shop_templates() {
		$dir = BB_Readylaunch_WC4BP_Helper::bb_rl_wc4bp_get_template_directory();

		$this->assertDirectoryExists( $dir );
		$this->assertFileExists( $dir . '/shop/member/checkout.php' );
		$this->assertFileExists( $dir . '/shop/member/cart.php' );
	}

	/**
	 * Templates that print a WooCommerce shortcode whose output is returned as a string.
	 *
	 * @return array
	 */
	public function data_shortcode_templates() {
		return array(
			'checkout' => array( 'checkout.php', 'woocommerce_checkout' ),
			'cart'     => array( 'cart.php', 'woocommerce_cart' ),
			'track'    => array( 'track.php', 'woocommerce_order_tracking' ),
		);
	}

	/**
	 * Form fields in the shortcode output reach the page untouched.
	 *
	 * @dataProvider data_shortcode_templates
	 *
	 * @param string $template Template file name.
	 * @param string $tag      Shortcode tag the template renders.
	 */
	public function test_shortcode_form_fields_are_preserved( $template, $tag ) {
		global $shortcode_tags;

		$this->replaced_shortcodes[ $tag ] = isset( $shortcode_tags[ $tag ] ) ? $shortcode_tags[ $tag ] : null;

		add_shortcode(
			$tag,
			function () {
				return '<form name="checkout" method="post"><input type="text" name="billing_first_name" value="" /><select name="billing_country"><option value="CA">Canada</option></select><input type="hidden" name="woocommerce-process-checkout-nonce" value="abc123" /></form>';
			}
		);

		ob_start();
		include BB_Readylaunch_WC4BP_Helper::bb_rl_wc4bp_get_template_directory() . '/shop/member/' . $template;
		$output = ob_get_clean();

		$this->assertStringContainsString( '<form name="checkout" method="post">', $output );
		$this->assertStringContainsString( '<input type="text" name="billing_first_name" value="" />', $output );
		$this->assertStringContainsString( '<select name="billing_country">', $output );
		$this->assertStringContainsString( 'name="woocommerce-process-checkout-nonce"', $output );
	}
}
