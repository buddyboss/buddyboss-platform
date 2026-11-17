<?php
/**
 * Verbose page rules during BuddyBoss page requests.
 *
 * Covers PROD-10720: bp_core_set_uri_globals() turns off
 * $wp_rewrite->use_verbose_page_rules on every BuddyBoss page request. Rules
 * regenerated later in that request (a deferred or shutdown flush, a WP-CLI
 * flush, or a lazy rebuild of an emptied rewrite_rules option) were stored with
 * the post rule ahead of the page rule, so every regular page returned 404
 * until the permalinks were saved again.
 *
 * @group core
 * @group rewrite
 */
class BB_Tests_Core_Rewrite_Verbose_Page_Rules extends BP_UnitTestCase {

	/**
	 * Page rule WordPress generates for a /%postname%/ structure.
	 */
	const PAGE_RULE = '(.?.+?)(?:/([0-9]+))?/?$';

	/**
	 * Post rule WordPress generates for a /%postname%/ structure.
	 */
	const POST_RULE = '([^/]+)(?:/([0-9]+))?/?$';

	/**
	 * Permalink structure before the test.
	 *
	 * @var string
	 */
	protected $old_permalink_structure = '';

	/**
	 * Server values replaced by go_to().
	 *
	 * @var array
	 */
	protected $old_server = array();

	/**
	 * Save the state a test changes.
	 */
	public function set_up() {
		parent::set_up();

		$this->old_permalink_structure = (string) get_option( 'permalink_structure' );
		$this->old_server              = $_SERVER;
	}

	/**
	 * Restore the permalink structure. This re-runs WP_Rewrite::init(), which
	 * also resets use_verbose_page_rules for the next test.
	 */
	public function tear_down() {
		$_SERVER = $this->old_server;
		$this->set_permalink_structure( $this->old_permalink_structure );

		parent::tear_down();
	}

	/**
	 * A flush during a BuddyBoss page request stores the same rules an admin
	 * request stores, with the page rule ahead of the post rule, and leaves
	 * verbose page rules off for the rest of the request.
	 */
	public function test_flush_during_buddyboss_request_keeps_page_rule_ahead_of_post_rule() {
		global $wp_rewrite;

		$this->set_permalink_structure( '/%postname%/' );

		// go_to() re-fires `init` in an already-booted process, so Platform re-registers its block.
		$this->setExpectedIncorrectUsage( 'WP_Block_Type_Registry::register' );
		$this->go_to( bp_get_members_directory_permalink() );

		// bp_core_set_uri_globals() turned verbose page rules off for this request.
		$this->assertFalse( $wp_rewrite->use_verbose_page_rules, 'The members directory should load as a BuddyBoss page request.' );

		// Rules an admin request stores, where verbose page rules are on.
		$wp_rewrite->use_verbose_page_rules = true;
		flush_rewrite_rules( false );
		$expected = get_option( 'rewrite_rules' );

		// A flush in the same BuddyBoss request, as LearnDash, WooCommerce or WP-CLI do.
		$wp_rewrite->use_verbose_page_rules = false;
		flush_rewrite_rules( false );
		$rules = get_option( 'rewrite_rules' );

		$this->assertArrayHasKey( self::PAGE_RULE, $rules );
		$this->assertArrayHasKey( self::POST_RULE, $rules );

		$keys = array_keys( $rules );
		$this->assertLessThan(
			array_search( self::POST_RULE, $keys, true ),
			array_search( self::PAGE_RULE, $keys, true ),
			'The page rule must be stored ahead of the post rule, or regular pages return 404.'
		);
		$this->assertSame( $expected, $rules );
		$this->assertFalse( $wp_rewrite->use_verbose_page_rules, 'The current request must still be parsed without verbose page rules.' );
	}

	/**
	 * Requests that already use verbose page rules (admin and regular pages)
	 * get their rules back unchanged.
	 */
	public function test_returns_rules_unchanged_when_verbose_page_rules_are_on() {
		global $wp_rewrite;

		$this->set_permalink_structure( '/%postname%/' );
		$this->assertTrue( $wp_rewrite->use_verbose_page_rules );

		$rules = array( 'bb-sentinel/?$' => 'index.php?bb_sentinel=1' );

		$this->assertSame( $rules, bb_core_keep_verbose_page_rules_on_generate( $rules ) );
	}

	/**
	 * Structures WordPress builds without verbose page rules are left alone,
	 * because the flag BuddyBoss turns off already matches them.
	 *
	 * @dataProvider data_structures_without_verbose_page_rules
	 *
	 * @param string $structure Permalink structure.
	 */
	public function test_returns_rules_unchanged_for_structures_without_verbose_page_rules( $structure ) {
		global $wp_rewrite;

		$this->set_permalink_structure( $structure );
		$wp_rewrite->use_verbose_page_rules = false;

		$rules = array( 'bb-sentinel/?$' => 'index.php?bb_sentinel=1' );

		$this->assertSame( $rules, bb_core_keep_verbose_page_rules_on_generate( $rules ) );
		$this->assertFalse( $wp_rewrite->use_verbose_page_rules );
	}

	/**
	 * Permalink structures WordPress builds without verbose page rules.
	 *
	 * @return array
	 */
	public function data_structures_without_verbose_page_rules() {
		return array(
			'plain'         => array( '' ),
			'date and name' => array( '/%year%/%monthnum%/%postname%/' ),
			'numeric'       => array( '/archives/%post_id%' ),
		);
	}
}
