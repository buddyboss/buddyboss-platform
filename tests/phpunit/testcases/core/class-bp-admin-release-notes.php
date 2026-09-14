<?php

/**
 * Tests for the BP_Admin plugin-details and release-notes helpers.
 *
 * These cover the pure helpers behind the Plugins-page "View details" modal:
 * version normalization, release-page URL construction, the markup pipeline
 * that makes remote release notes survive core's own filtering, the add-on
 * release maps, and the two plugins_api entry points.
 *
 * Most of the methods under test are protected. They are reached by reflection
 * on an instance built without its constructor, so no hooks are registered and
 * no globals are touched: every one of them is a pure function of its
 * arguments. The two tests that do need the real registrations say so and
 * build a full instance; WP_UnitTestCase backs up and restores $wp_filter
 * around every test, so those registrations do not leak.
 *
 * @group core
 * @group bp_admin
 * @group bb_release_notes
 */
class BB_Tests_Admin_Release_Notes extends BP_UnitTestCase {

	/**
	 * Constructor-less instance used for the pure-helper tests.
	 *
	 * @var BP_Admin
	 */
	protected $admin;

	/**
	 * Build the instance under test.
	 */
	public function set_up() {
		parent::set_up();

		$reflection  = new ReflectionClass( 'BP_Admin' );
		$this->admin = $reflection->newInstanceWithoutConstructor();
	}

	/**
	 * Call a protected method on the instance under test.
	 *
	 * @param string $method Method name.
	 * @param array  $args   Positional arguments.
	 *
	 * @return mixed Whatever the method returns.
	 */
	protected function call( $method, $args = array() ) {
		$reflection = new ReflectionMethod( 'BP_Admin', $method );
		$reflection->setAccessible( true );

		return $reflection->invokeArgs( $this->admin, $args );
	}

	/**
	 * Shorthand for bb_normalize_release_version().
	 *
	 * @param mixed $version Value to normalize.
	 *
	 * @return string
	 */
	protected function normalize( $version ) {
		return $this->call( 'bb_normalize_release_version', array( $version ) );
	}

	/* bb_normalize_release_version ******************************************/

	/**
	 * Behaviour that predates the hardening and must not regress.
	 */
	public function test_normalize_version_preserved_behaviour() {
		$this->assertSame( '3.4.4', $this->normalize( '3.4.4' ) );

		// A suffixed build truncates to its base release rather than splicing.
		$this->assertSame( '3.4.4', $this->normalize( '3.4.4-beta1' ) );
		$this->assertSame( '3.4.4', $this->normalize( '3.4.4+build.9' ) );

		// Stray dots are tidied so the slug cannot end up as '3-4-1-'.
		$this->assertSame( '3.4.1', $this->normalize( '3.4.1.' ) );
		$this->assertSame( '3.4', $this->normalize( '3..4' ) );
		$this->assertSame( '3.4', $this->normalize( '.3.4' ) );

		// A mangled value must not be able to reach a URL path.
		$this->assertSame( '', $this->normalize( '../..' ) );
		$this->assertSame( '', $this->normalize( '..' ) );
		$this->assertSame( '', $this->normalize( 'abc' ) );
		$this->assertSame( '', $this->normalize( '' ) );

		// Markup in the feed's version is dropped, not carried.
		$this->assertSame( '3.4.4', $this->normalize( '3.4.4<img src=x onerror=1>' ) );
	}

	/**
	 * A padded version used to yield '' and silently disable the whole fetch.
	 */
	public function test_normalize_version_trims_surrounding_whitespace() {
		$this->assertSame( '3.4.4', $this->normalize( '  3.4.4' ) );
		$this->assertSame( '3.4.4', $this->normalize( "\t3.4.4\n" ) );
		$this->assertSame( '3.4.4', $this->normalize( '3.4.4 ' ) );
	}

	/**
	 * A separator this does not understand must yield nothing, not a confident
	 * wrong answer: '3,4,4' truncating to '3' would link a real-looking /3/
	 * release page that is not the user's release.
	 */
	public function test_normalize_version_rejects_unrecognized_separators() {
		$this->assertSame( '', $this->normalize( '3,4,4' ) );
		$this->assertSame( '', $this->normalize( '3_4_4' ) );
		$this->assertSame( '', $this->normalize( '3 4 4' ) );
	}

	/**
	 * A feed that files its releases as 'v2.0' is naming 2.0.
	 */
	public function test_normalize_version_strips_leading_v() {
		$this->assertSame( '2.0', $this->normalize( 'v2.0' ) );
		$this->assertSame( '2.10.0', $this->normalize( 'V2.10.0' ) );

		// Only a prefix, not a 'v' floating anywhere in the value.
		$this->assertSame( '', $this->normalize( 'v 2.0' ) );
	}

	/**
	 * Two numeric segments or more, or nothing. A bare integer is no more
	 * resolvable to a release page than a comma-separated value was.
	 */
	public function test_normalize_version_requires_two_segments() {
		$this->assertSame( '2.0', $this->normalize( '2.0' ) );
		$this->assertSame( '1.2.3.4.5', $this->normalize( '1.2.3.4.5' ) );
		$this->assertSame( '', $this->normalize( '3' ) );
		$this->assertSame( '', $this->normalize( '0' ) );
	}

	/**
	 * The value reaches a query string, a cache key and a URL path, so a feed
	 * answering with a megabyte of digits must not be carried into any of them.
	 */
	public function test_normalize_version_caps_length() {
		$this->assertSame( '', $this->normalize( str_repeat( '9', 500 ) . '.1' ) );
		$this->assertLessThanOrEqual( 128, strlen( $this->normalize( '1.' . str_repeat( '2', 500 ) ) ) );
	}

	/**
	 * Non-scalar input must not warn or stringify to 'Array'.
	 */
	public function test_normalize_version_rejects_non_scalar() {
		$this->assertSame( '', $this->normalize( array( '3.4.4' ) ) );
		$this->assertSame( '', $this->normalize( null ) );
		$this->assertSame( '', $this->normalize( new stdClass() ) );
	}

	/* bb_get_release_notes_page_url *****************************************/

	/**
	 * An empty version links the archive, which always resolves.
	 */
	public function test_release_notes_page_url_defaults_to_archive() {
		$this->assertSame(
			'https://buddyboss.com/resources/buddyboss-platform-releases/',
			$this->admin->bb_get_release_notes_page_url()
		);
		$this->assertSame(
			'https://buddyboss.com/resources/buddyboss-platform-releases/',
			$this->admin->bb_get_release_notes_page_url( 'not-a-version' )
		);
	}

	/**
	 * A version becomes a dash-separated path segment.
	 */
	public function test_release_notes_page_url_builds_version_path() {
		$this->assertSame(
			'https://buddyboss.com/resources/buddyboss-platform-releases/3-4-4/',
			$this->admin->bb_get_release_notes_page_url( '3.4.4' )
		);
	}

	/**
	 * The invariant the normalizer exists for: the release named in the link
	 * text is the release the link opens. A caller that renders
	 * bb_normalize_release_version() as text and passes the raw version here
	 * must not end up with the two disagreeing.
	 */
	public function test_release_notes_page_url_matches_its_link_text() {
		$raw = '3.4.4-beta1';

		$linked = $this->normalize( $raw );
		$url    = $this->admin->bb_get_release_notes_page_url( $raw );

		$this->assertSame( '3.4.4', $linked );
		$this->assertSame(
			'https://buddyboss.com/resources/buddyboss-platform-releases/3-4-4/',
			$url
		);
		$this->assertStringContainsString( str_replace( '.', '-', $linked ) . '/', $url );
	}

	/**
	 * Add-on plugins reuse the helper with their own archive base.
	 */
	public function test_release_notes_page_url_honours_custom_base() {
		$this->assertSame(
			'https://example.org/rel/3-4-4/',
			$this->admin->bb_get_release_notes_page_url( '3.4.4', 'https://example.org/rel' )
		);
		$this->assertSame(
			'https://example.org/rel/',
			$this->admin->bb_get_release_notes_page_url( '', 'https://example.org/rel' )
		);
	}

	/* bb_remap_release_notes_tags *******************************************/

	/**
	 * Tags outside core's $plugins_allowedtags are rewritten into ones inside it.
	 */
	public function test_remap_tags_rewrites_exact_equivalents() {
		$remapped = $this->call( 'bb_remap_release_notes_tags', array( '<b>x</b><i class="ico">y</i>' ) );

		$this->assertStringContainsString( '<strong>x</strong>', $remapped );
		$this->assertStringContainsString( '<em>y</em>', $remapped );
		$this->assertStringNotContainsString( '<b>', $remapped );
		$this->assertStringNotContainsString( '<i ', $remapped );
	}

	/**
	 * The alternation that matches <b> and <i> must not reach <br> or <img>:
	 * after '<b' the optional attribute run needs whitespace or '>', not 'r'.
	 * This is the specific non-match the method's docblock reasons about.
	 */
	public function test_remap_tags_leaves_br_and_img_alone() {
		$html     = '<br /><br><img src="a.png" alt="a" />';
		$remapped = $this->call( 'bb_remap_release_notes_tags', array( $html ) );

		$this->assertStringContainsString( '<br />', $remapped );
		$this->assertStringContainsString( '<br>', $remapped );
		$this->assertStringContainsString( '<img src="a.png"', $remapped );
		$this->assertStringNotContainsString( '<strong />', $remapped );
		$this->assertStringNotContainsString( '<strongr', $remapped );
	}

	/**
	 * Headings shift down one level, floored at h3 so they stay under the
	 * modal's own h2, and capped at h6.
	 */
	public function test_remap_tags_demotes_headings() {
		$remapped = $this->call(
			'bb_remap_release_notes_tags',
			array( '<h1 id="x">A</h1><h2>B</h2><h5>C</h5><h6>D</h6>' )
		);

		$this->assertSame( '<h3>A</h3><h3>B</h3><h6>C</h6><h6>D</h6>', $remapped );
	}

	/* bb_convert_release_notes_tables / bb_release_notes_tags_balance *******/

	/**
	 * A table becomes a list whose cells stay separated and in order; header
	 * cells keep their emphasis.
	 */
	public function test_convert_tables_rewrites_rows_as_list_items() {
		$html = '<table><thead><tr><th>Type</th><th>Component</th></tr></thead>'
			. '<tbody><tr><td>Fixed</td><td>Activity feed</td></tr></tbody></table>';

		$converted = $this->call( 'bb_convert_release_notes_tables', array( $html ) );

		$this->assertSame(
			'<ul><li><strong>Type</strong> &middot; <strong>Component</strong></li>'
			. '<li>Fixed &middot; Activity feed</li></ul>',
			$converted
		);
	}

	/**
	 * Markup with no table is returned untouched, without walking it.
	 */
	public function test_convert_tables_ignores_markup_without_a_table() {
		$html = '<p>No table here.</p><ul><li>a</li></ul>';

		$this->assertSame( $html, $this->call( 'bb_convert_release_notes_tables', array( $html ) ) );
	}

	/**
	 * The balance test: a surplus of openings is what makes the pair patterns
	 * search to the end of the document for a partner that is not there.
	 */
	public function test_tags_balance_detects_unclosed_pairs() {
		$this->assertTrue(
			$this->call( 'bb_release_notes_tags_balance', array( '<table><tr><td>a</td></tr></table>' ) )
		);
		$this->assertFalse(
			$this->call( 'bb_release_notes_tags_balance', array( '<table><tr><td>a</td><tr><td>b</td></table>' ) )
		);
		$this->assertFalse(
			$this->call( 'bb_release_notes_tags_balance', array( '<table><tr><td>a</tr></table>' ) )
		);
	}

	/**
	 * A word boundary keeps the element names exact: '<th' must not count
	 * '<thead', and '<tr' must not count '<track'.
	 */
	public function test_tags_balance_does_not_count_prefixes() {
		$this->assertTrue(
			$this->call(
				'bb_release_notes_tags_balance',
				array( '<table><thead><tr><th>a</th></tr></thead></table>' )
			)
		);
	}

	/**
	 * Unbalanced table markup passes through untouched rather than being
	 * converted at quadratic cost. Nothing is lost: it still reaches
	 * wp_kses_post() afterwards.
	 */
	public function test_convert_tables_leaves_unbalanced_markup_alone() {
		$html = '<table>' . str_repeat( '<tr><td>cell</td>', 200 ) . '</table>';

		$this->assertSame( $html, $this->call( 'bb_convert_release_notes_tables', array( $html ) ) );
	}

	/**
	 * The guard is what keeps the conversion linear. A document that would
	 * previously have taken tens of seconds must now return promptly.
	 */
	public function test_convert_tables_is_not_quadratic_on_unbalanced_markup() {
		$html  = '<table>' . str_repeat( '<tr><td>cell</td>', 4000 ) . '</table>';
		$start = microtime( true );

		$this->call( 'bb_convert_release_notes_tables', array( $html ) );

		$this->assertLessThan(
			5,
			microtime( true ) - $start,
			'Unbalanced table conversion should short-circuit, not backtrack.'
		);
	}

	/* bb_prepare_release_notes_html (orphan-tag repair boundary) ************/

	/**
	 * The bug the repair exists for: the feed emits tags whose closing bracket
	 * is missing at a line end, and without repair wp_kses_post() escapes the
	 * fragment into visible text.
	 */
	public function test_prepare_repairs_orphaned_closing_tag() {
		$prepared = $this->call(
			'bb_prepare_release_notes_html',
			array( "<ul>\n<li>a</li>\n</ul\r\n" )
		);

		$this->assertStringContainsString( '</ul>', $prepared );
		$this->assertStringNotContainsString( '&lt;/ul', $prepared );
	}

	/**
	 * A legitimately multi-line tag must survive intact. Truncating it to '<a>'
	 * would promote its attributes into the page as visible body prose.
	 */
	public function test_prepare_keeps_multiline_tag_intact() {
		$prepared = $this->call(
			'bb_prepare_release_notes_html',
			array( "<a\n  href=\"https://buddyboss.com/x\">Read more</a>" )
		);

		$this->assertStringContainsString( 'href="https://buddyboss.com/x"', $prepared );
		$this->assertStringNotContainsString( 'href=&quot;', $prepared );
	}

	/**
	 * A bare '<' in prose is not the start of a tag and must not become one.
	 */
	public function test_prepare_does_not_invent_tags_from_bare_angle_brackets() {
		$prepared = $this->call( 'bb_prepare_release_notes_html', array( '<p>a < b</p>' ) );
		$this->assertStringNotContainsString( '<b>', $prepared );

		$prepared = $this->call( 'bb_prepare_release_notes_html', array( '5 < 6 and 7 > 2' ) );
		$this->assertStringNotContainsString( '<6', $prepared );
		$this->assertStringNotContainsString( '<b', $prepared );
	}

	/**
	 * A document larger than any release produces is refused rather than walked.
	 */
	public function test_prepare_refuses_oversized_payload() {
		$max = $this->call( 'bb_release_notes_max_bytes' );

		$this->assertSame( '', $this->call( 'bb_prepare_release_notes_html', array( str_repeat( 'a', $max + 1 ) ) ) );
		$this->assertSame( '', $this->call( 'bb_prepare_release_notes_html', array( '' ) ) );
		$this->assertSame( '', $this->call( 'bb_prepare_release_notes_html', array( null ) ) );
	}

	/**
	 * Only a host under our own control is ever used as the link base: the URL
	 * arrives from the remote feed, and an attacker-chosen base would turn every
	 * relative link in the notes into a link to their site.
	 */
	public function test_prepare_refuses_foreign_and_non_http_bases() {
		$html = '<a href="relative/page/">x</a>';

		$evil = $this->call( 'bb_prepare_release_notes_html', array( $html, 'https://evil.example/' ) );
		$this->assertStringNotContainsString( 'evil.example', $evil );
		$this->assertStringContainsString( 'buddyboss.com', $evil );

		$scheme = $this->call( 'bb_prepare_release_notes_html', array( $html, 'javascript://buddyboss.com/' ) );
		$this->assertStringNotContainsString( 'javascript:', $scheme );
	}

	/* bb_extract_release_notes_html *****************************************/

	/**
	 * Rendered content wins over every release_fields spelling.
	 */
	public function test_extract_notes_precedence() {
		$item = array(
			'content'        => array( 'rendered' => '<p>rendered</p>' ),
			'release_fields' => array( 'changelog' => '<p>changelog</p>' ),
		);
		$this->assertSame( '<p>rendered</p>', $this->call( 'bb_extract_release_notes_html', array( $item ) ) );

		$order = array( 'changelog', 'changes', 'release_notes', 'content' );

		foreach ( $order as $index => $key ) {
			$fields = array();

			// Every key at or after this one is present; the first must win.
			foreach ( array_slice( $order, $index ) as $later ) {
				$fields[ $later ] = '<p>' . $later . '</p>';
			}

			$this->assertSame(
				'<p>' . $key . '</p>',
				$this->call( 'bb_extract_release_notes_html', array( array( 'release_fields' => $fields ) ) )
			);
		}
	}

	/**
	 * A string release_fields is the notes; anything unusable is empty.
	 */
	public function test_extract_notes_handles_odd_shapes() {
		$this->assertSame(
			'<p>x</p>',
			$this->call( 'bb_extract_release_notes_html', array( array( 'release_fields' => '<p>x</p>' ) ) )
		);
		$this->assertSame( '', $this->call( 'bb_extract_release_notes_html', array( array() ) ) );
		$this->assertSame( '', $this->call( 'bb_extract_release_notes_html', array( 'not-an-array' ) ) );
		$this->assertSame(
			'',
			$this->call( 'bb_extract_release_notes_html', array( array( 'release_fields' => array( 'other' => 'x' ) ) ) )
		);
	}

	/* Release maps **********************************************************/

	/**
	 * Every mapped key is the directory the add-on installs into, because that
	 * is the only name plugins_api() is given to work with.
	 */
	public function test_addon_release_term_map() {
		$expected = array(
			'buddyboss-offload-media'   => 'buddyboss-offload-media-releases',
			'buddyboss-gamification'    => 'buddyboss-gamification-releases',
			'buddyboss-sharing'         => 'buddyboss-sharing-releases',
			'buddyboss-learndash'       => 'buddyboss-learndash',
			'buddyboss-addons'          => 'buddyboss-addons',
			'buddyboss-tools'           => 'buddyboss-tools',
			'buddyboss-member-blogging' => 'member-blogging',
		);

		foreach ( $expected as $slug => $term ) {
			$this->assertSame( $term, $this->call( 'bb_get_addon_release_term', array( $slug ) ) );
		}

		$this->assertSame( '', $this->call( 'bb_get_addon_release_term', array( 'not-an-addon' ) ) );
	}

	/**
	 * The Member Blogging directory is declared by this plugin itself, so the
	 * map key and that declaration must not drift apart.
	 */
	public function test_member_blogging_map_key_matches_plugin_slug() {
		if ( ! function_exists( 'bb_member_blogging_plugin_slug' ) ) {
			$this->markTestSkipped( 'Member Blogging add-on helpers are not loaded.' );
		}

		$this->assertSame(
			'member-blogging',
			$this->call( 'bb_get_addon_release_term', array( bb_member_blogging_plugin_slug() ) )
		);
	}

	/**
	 * A few products keep their releases in a post type of their own.
	 */
	public function test_addon_release_post_type_map() {
		$this->assertSame(
			'releases-platformpro',
			$this->call( 'bb_get_addon_release_post_type', array( 'buddyboss-platform-pro' ) )
		);
		$this->assertSame(
			'releases-app',
			$this->call( 'bb_get_addon_release_post_type', array( 'buddyboss-app' ) )
		);
		$this->assertSame( '', $this->call( 'bb_get_addon_release_post_type', array( 'buddyboss-sharing' ) ) );
	}

	/**
	 * Both maps and the known-slug list are filterable, so a site that renamed
	 * a directory has a way back.
	 */
	public function test_release_maps_are_filterable() {
		add_filter(
			'bb_addon_release_terms',
			function ( $terms ) {
				$terms['renamed-dir'] = 'buddyboss-sharing-releases';

				return $terms;
			}
		);
		$this->assertSame(
			'buddyboss-sharing-releases',
			$this->call( 'bb_get_addon_release_term', array( 'renamed-dir' ) )
		);

		add_filter(
			'bb_addon_release_post_types',
			function ( $types ) {
				$types['renamed-pro'] = 'releases-platformpro';

				return $types;
			}
		);
		$this->assertSame(
			'releases-platformpro',
			$this->call( 'bb_get_addon_release_post_type', array( 'renamed-pro' ) )
		);
	}

	/**
	 * Slugs recognized without a "Requires Plugins" header are sanitized, so a
	 * filter cannot inject a path fragment into the list.
	 */
	public function test_known_addon_slugs_are_sanitized() {
		$this->assertContains( 'buddyboss-app', $this->call( 'bb_get_known_buddyboss_addon_slugs' ) );

		add_filter(
			'bb_known_buddyboss_addon_slugs',
			function ( $slugs ) {
				$slugs[] = '../../Evil Plugin';

				return $slugs;
			}
		);

		foreach ( $this->call( 'bb_get_known_buddyboss_addon_slugs' ) as $slug ) {
			$this->assertSame( sanitize_title( $slug ), $slug );
			$this->assertStringNotContainsString( '/', $slug );
		}
	}

	/* plugins_api entry points **********************************************/

	/**
	 * WordPress passes $_REQUEST['plugin'] to plugins_api() with no string cast,
	 * so '?plugin[]=x' delivers an array slug. Neither handler may fatal on it.
	 */
	public function test_plugins_api_handlers_survive_an_array_slug() {
		$args = (object) array( 'slug' => array( 'buddyboss-platform' ) );

		$this->assertFalse( $this->admin->bb_plugins_api_information( false, 'plugin_information', $args ) );
		$this->assertFalse( $this->admin->bb_plugins_api_addon_fallback( false, 'plugin_information', $args ) );
	}

	/**
	 * Neither handler answers for an action that is not plugin_information, nor
	 * steps on a result somebody else already produced.
	 */
	public function test_plugins_api_handlers_decline_when_they_should() {
		$args = (object) array( 'slug' => 'buddyboss-platform' );

		$this->assertFalse( $this->admin->bb_plugins_api_information( false, 'query_plugins', $args ) );
		$this->assertFalse( $this->admin->bb_plugins_api_addon_fallback( false, 'query_plugins', $args ) );

		$existing = (object) array( 'name' => 'Someone else' );
		$this->assertSame(
			$existing,
			$this->admin->bb_plugins_api_information( $existing, 'plugin_information', $args )
		);
		$this->assertSame(
			$existing,
			$this->admin->bb_plugins_api_addon_fallback( $existing, 'plugin_information', $args )
		);
	}

	/**
	 * The add-on fallback must run later than every BuddyBoss add-on's own
	 * plugins_api handler, so an add-on that can speak for itself always does.
	 *
	 * Priorities observed in the shipped add-ons at the time of writing:
	 *
	 *   buddyboss-platform-pro    10
	 *   buddyboss-addons          10
	 *   buddyboss-sharing         10
	 *   buddyboss-gamification    10
	 *   buddyboss-offload-media   10
	 *   buddyboss-learndash       10
	 *   buddyboss-app             99   <- the reason 20 was not late enough
	 *
	 * Third parties sit in this range too: WooCommerce registers at 20, which
	 * is the slot the fallback used to occupy.
	 *
	 * This assertion is the tracking mechanism for that cross-repo hazard. If
	 * anyone lowers the priority back into the pack, this fails here rather
	 * than turning into a silent regression in another product.
	 */
	public function test_addon_fallback_runs_after_every_known_addon_handler() {
		/*
		 * Register the hooks without running the constructor: includes() uses
		 * bare require, so building a second BP_Admin in a process that already
		 * has one is a redeclaration fatal. setup_actions() only registers, and
		 * WP_UnitTestCase restores $wp_filter after this test.
		 */
		$admin = $this->admin;

		$setup = new ReflectionMethod( 'BP_Admin', 'setup_actions' );
		$setup->setAccessible( true );
		$setup->invoke( $admin );

		$priority = has_filter( 'plugins_api', array( $admin, 'bb_plugins_api_addon_fallback' ) );

		$this->assertNotFalse( $priority, 'The add-on fallback is not registered on plugins_api.' );
		$this->assertGreaterThan(
			99,
			$priority,
			'The add-on fallback must run later than buddyboss-app, which registers at 99.'
		);

		// This plugin answers for its own slug early, before any add-on.
		$this->assertSame(
			10,
			has_filter( 'plugins_api', array( $admin, 'bb_plugins_api_information' ) )
		);
	}

	/**
	 * The dependency-API payload must not carry a licensed package URL into
	 * core's never-expiring wp_plugin_dependencies_plugin_data store.
	 */
	public function test_dependency_api_data_carries_no_download_link() {
		$slug = $this->call( 'bb_get_platform_plugin_slug' );

		$value = array(
			$slug               => array(
				'Name'          => 'BuddyBoss Platform',
				'version'       => '3.4.4',
				'download_link' => 'https://licenses.example/downloads/x?signature=secret',
				'sections'      => array( 'changelog' => '<p>x</p>' ),
			),
			'some-other-plugin' => array(
				'Name'          => 'Other',
				'download_link' => 'https://downloads.wordpress.org/plugin/other.zip',
			),
		);

		$stripped = $this->admin->bb_strip_dependency_api_data( $value );

		$this->assertArrayNotHasKey( 'download_link', $stripped[ $slug ] );
		$this->assertArrayNotHasKey( 'sections', $stripped[ $slug ] );
		$this->assertSame( 'BuddyBoss Platform', $stripped[ $slug ]['Name'] );

		// Another plugin's entry is none of our business.
		$this->assertSame(
			'https://downloads.wordpress.org/plugin/other.zip',
			$stripped['some-other-plugin']['download_link']
		);
	}
}
