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

	/* bb_sanitize_plugin_version ********************************************/

	/**
	 * The 'version' key served to plugins_api() is both printed and compared,
	 * so it must keep the suffix that bb_normalize_release_version() drops.
	 *
	 * install_plugin_install_status() feeds it to version_compare() against the
	 * installed plugin's own Version header. For an up-to-date plugin core's
	 * slug lookup scans only the transient's 'response' container and therefore
	 * cannot match, so that comparison always runs - and an api version that is
	 * greater than the installed one sends core into a branch that runs
	 * delete_site_transient( 'update_plugins' ) and a blocking
	 * wp_update_plugins(), on every modal open. A normalized '2.0.3' against an
	 * installed '2.0.3-beta2' is exactly that case.
	 */
	public function test_sanitize_plugin_version_keeps_prerelease_suffixes() {
		$sanitize = function ( $v ) {
			return $this->call( 'bb_sanitize_plugin_version', array( $v ) );
		};

		foreach ( array( '2.0.3-beta2', '3.4.1-RC1', '1.2.3b', '2.0.3', '1.2.3+build.9' ) as $raw ) {
			$this->assertSame( $raw, $sanitize( $raw ), 'A real version must survive unchanged.' );

			// The property that matters: core reports 'latest_installed' rather
			// than falling through to the transient-deleting branch.
			$this->assertTrue(
				version_compare( $sanitize( $raw ), $raw, '=' ),
				sprintf( 'Sanitized "%s" must still compare equal to the installed header.', $raw )
			);
		}

		// The contrast that makes the two helpers different functions.
		$this->assertSame( '3.4.4', $this->normalize( '3.4.4-beta1' ) );
		$this->assertSame( '3.4.4-beta1', $sanitize( '3.4.4-beta1' ) );
		$this->assertFalse( version_compare( '3.4.4', '3.4.4-beta1', '=' ) );
		$this->assertFalse( version_compare( '3.4.4', '3.4.4-beta1', '<' ) );
	}

	/**
	 * Core echoes this key after wp_kses() with an allowlist that still passes
	 * links, images and class attributes, so nothing that can open a tag may
	 * survive sanitizing.
	 */
	public function test_sanitize_plugin_version_cannot_carry_markup() {
		$sanitize = function ( $v ) {
			return $this->call( 'bb_sanitize_plugin_version', array( $v ) );
		};

		foreach (
			array(
				'3.4.4<img src=x onerror=alert(1)>',
				'3.4.4"><a href="//evil">x</a>',
				"3.4.4'onmouseover='x",
			) as $hostile
		) {
			$out = $sanitize( $hostile );

			$this->assertStringNotContainsString( '<', $out );
			$this->assertStringNotContainsString( '>', $out );
			$this->assertStringNotContainsString( '"', $out );
			$this->assertStringNotContainsString( "'", $out );
			$this->assertSame( $out, wp_kses( $out, array() ), 'Nothing left for kses to strip.' );
		}

		// Bounded, and non-scalars name no version.
		$this->assertLessThanOrEqual( 128, strlen( $sanitize( str_repeat( '9', 500 ) ) ) );
		$this->assertSame( '', $sanitize( array( '1.0' ) ) );
		$this->assertSame( '', $sanitize( null ) );
		$this->assertSame( '', $sanitize( new stdClass() ) );
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
	 * Interleaved tags balance on the final count while still leaving openings
	 * with no partner ahead of them, so the end-of-loop total proves nothing on
	 * its own. This is the payload that passed the guard and still cost tens of
	 * seconds in the conversion, so it is the case that must stay covered.
	 */
	public function test_tags_balance_rejects_closings_before_openings() {
		$this->assertFalse(
			$this->call(
				'bb_release_notes_tags_balance',
				array( '<table>' . str_repeat( '</tr>', 5 ) . str_repeat( '<tr><td>x</td>', 5 ) . '</table>' )
			)
		);

		// The smallest shape of it: nets to zero, still interleaved.
		$this->assertFalse(
			$this->call( 'bb_release_notes_tags_balance', array( '</tr><tr>' ) )
		);
	}

	/**
	 * td and th are counted apart, because the conversion pairs a cell with a
	 * backreference and so never matches a <th> against a </td>.
	 *
	 * Counted together, both shapes below balance on every test the guard used
	 * to apply - the running total never goes negative and ends at zero - while
	 * not one opening has a partner, so every one of them scans to the end of
	 * the document. Measured at the byte cap the fetch allows, the first cost
	 * 164 seconds and the second 91, with the guard answering true throughout.
	 * These are the two payloads that got through; they are the two that must
	 * stay refused.
	 */
	public function test_tags_balance_counts_td_and_th_separately() {
		$this->assertFalse(
			$this->call(
				'bb_release_notes_tags_balance',
				array( '<table>' . str_repeat( '<th>', 40 ) . str_repeat( '</td>', 40 ) . '</table>' )
			),
			'<th> openings closed by </td> must not be treated as balanced.'
		);

		$this->assertFalse(
			$this->call(
				'bb_release_notes_tags_balance',
				array( '<table>' . str_repeat( '<td>x</th>', 40 ) . '</table>' )
			),
			'Alternating <td>x</th> must not be treated as balanced.'
		);

		// Valid markup is unaffected by the split, including the shapes where
		// th and td appear in the same document and the same row.
		foreach (
			array(
				'<table><tr><th>A</th><th>B</th></tr><tr><td>1</td><td>2</td></tr></table>',
				'<table><caption>C</caption><thead><tr><th>A</th></tr></thead><tbody><tr><td>1</td></tr></tbody></table>',
				'<table><tr><td><table><tr><td>x</td></tr></table></td></tr></table>',
				"<table class=\"x\">\n<tr id=\"r\">\n<th scope=\"col\">A</th>\n<td colspan=\"2\">1</td>\n</tr>\n</table>",
			) as $valid
		) {
			$this->assertTrue(
				$this->call( 'bb_release_notes_tags_balance', array( $valid ) ),
				'Valid table markup must still convert: ' . $valid
			);
		}

		/*
		 * Scoped per element, and only per element. Tags of different names are
		 * not checked against each other, so this still passes - correctly:
		 * both patterns find their partner, neither scans past it, and nothing
		 * here is quadratic. The guard answers "will a pattern hunt to the end
		 * of the document", not "is this well-formed".
		 */
		$this->assertTrue(
			$this->call( 'bb_release_notes_tags_balance', array( '<tr><td></tr></td>' ) )
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

		// Interleaved, and therefore refused for the same reason.
		$interleaved = '<table>' . str_repeat( '</tr>', 200 ) . str_repeat( '<tr><td>cell</td>', 200 ) . '</table>';

		$this->assertSame( $interleaved, $this->call( 'bb_convert_release_notes_tables', array( $interleaved ) ) );
	}

	/**
	 * The guard is what keeps the conversion linear, and it has to hold for
	 * both shapes of unbalanced markup: a surplus of openings, and openings
	 * interleaved behind closings so the net count is zero. The second is not
	 * a variant of the first - it passed an end-of-loop total test while still
	 * taking tens of seconds - so both are timed here.
	 *
	 * @dataProvider data_unbalanced_table_markup
	 *
	 * @param string $html Unbalanced table markup.
	 */
	public function test_convert_tables_is_not_quadratic_on_unbalanced_markup( $html ) {
		$start = microtime( true );

		$this->call( 'bb_convert_release_notes_tables', array( $html ) );

		$this->assertLessThan(
			5,
			microtime( true ) - $start,
			'Unbalanced table conversion should short-circuit, not backtrack.'
		);
	}

	/**
	 * Data provider for test_convert_tables_is_not_quadratic_on_unbalanced_markup().
	 *
	 * @return array[] Each entry is one unbalanced document.
	 */
	public function data_unbalanced_table_markup() {
		return array(
			'surplus openings' => array(
				'<table>' . str_repeat( '<tr><td>cell</td>', 4000 ) . '</table>',
			),
			'closings first'   => array(
				'<table>' . str_repeat( '</tr>', 4000 ) . str_repeat( '<tr><td>cell</td>', 4000 ) . '</table>',
			),
			// Balanced on a combined td/th tally, and 164 seconds to convert.
			'th open, td close' => array(
				'<table>' . str_repeat( '<th>', 4000 ) . str_repeat( '</td>', 4000 ) . '</table>',
			),
			// The same hole reached by alternating rather than by grouping.
			'alternating cells' => array(
				'<table>' . str_repeat( '<td>x</th>', 4000 ) . '</table>',
			),
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

	/* Cross-repo API surface ************************************************/

	/**
	 * Every method the BuddyBoss add-on plugins call must stay public.
	 *
	 * This is not a style assertion, and it is not a loud failure either. The
	 * add-ons guard every one of these calls with is_callable(), which reports
	 * false for a method they cannot reach - so narrowing one does not fatal.
	 * Each add-on silently renders a bare link where its changelog was, on a
	 * screen nobody watches, and no error is raised in any of the seven
	 * repositories. Quiet is what makes it worth asserting: a fatal would find
	 * itself.
	 *
	 * Read this as a record of the contract rather than as a gate on it. The
	 * PHPUnit suite does not currently run on this branch, so nothing here has
	 * executed; treat a change to the list below as a change to six products'
	 * behaviour and verify it in those products.
	 *
	 * @dataProvider data_contract_methods
	 *
	 * @param string $method Method name that add-on plugins call.
	 */
	public function test_contract_methods_are_public( $method ) {
		$this->assertTrue(
			method_exists( 'BP_Admin', $method ),
			sprintf( 'BP_Admin::%s() is part of the add-on API and has gone missing.', $method )
		);

		$reflection = new ReflectionMethod( 'BP_Admin', $method );

		$this->assertTrue(
			$reflection->isPublic(),
			sprintf(
				'BP_Admin::%s() is part of the add-on API and must stay public: '
					. 'the add-ons guard on is_callable(), which reports false for a method '
					. 'they cannot reach, so narrowing it costs six products their changelog '
					. 'section without raising anything anywhere.',
				$method
			)
		);

		$this->assertFalse(
			$reflection->isStatic(),
			sprintf( 'BP_Admin::%s() is called on an instance by the add-ons.', $method )
		);
	}

	/**
	 * Data provider for test_contract_methods_are_public().
	 *
	 * @return array[] Each entry is one method name.
	 */
	public function data_contract_methods() {
		$methods = array(
			'bb_build_changelog_section',
			'bb_get_addon_changelog_section',
			'bb_get_addon_release_archive_url',
			'bb_get_addon_release_notes_html',
			'bb_get_plugin_download_link',
			'bb_get_plugin_last_updated',
			'bb_get_release_notes_html',
			'bb_get_release_notes_page_url',
			'bb_normalize_release_version',
			'bb_should_fetch_release_notes',
		);

		return array_map(
			function ( $method ) {
				return array( $method );
			},
			$methods
		);
	}

	/**
	 * The add-on wrapper must keep accepting a plugins_api $args object, and
	 * must keep treating "not supplied" as "fetch".
	 *
	 * Both halves are contract. An add-on that hands over its $args gets the
	 * same gate this plugin applies to itself; one that gated already and
	 * passes nothing must keep getting its changelog.
	 */
	public function test_addon_changelog_section_gate_is_optional_and_defaults_to_fetching() {
		$reflection = new ReflectionMethod( 'BP_Admin', 'bb_get_addon_changelog_section' );
		$parameters = $reflection->getParameters();

		$this->assertCount( 5, $parameters );
		$this->assertSame( 'args', $parameters[4]->getName() );
		$this->assertTrue( $parameters[4]->isOptional() );
		$this->assertNull( $parameters[4]->getDefaultValue() );

		// A caller that asks for no sections gets the link alone, no fetch and
		// no sentence claiming the release has nothing published.
		$args = (object) array( 'fields' => array( 'sections' => false ) );

		$section = $this->admin->bb_get_addon_changelog_section( '2.0.3', 'buddyboss-sharing-releases', '', '', $args );

		$this->assertStringNotContainsString( 'No release notes have been published', $section );
		$this->assertStringNotContainsString( 'could not be loaded', $section );
		$this->assertStringContainsString( 'buddyboss.com/resources/addons/buddyboss-sharing-releases/', $section );
	}

	/**
	 * An unrecognized term slug is not a claim about the remote.
	 *
	 * Nothing was asked of buddyboss.com, so "no release notes have been
	 * published for this version yet" would be an invention.
	 */
	public function test_addon_changelog_section_says_nothing_it_did_not_check() {
		$section = $this->admin->bb_get_addon_changelog_section( '2.0.3', '' );

		$this->assertStringNotContainsString( 'No release notes have been published', $section );
		$this->assertStringContainsString( 'buddyboss.com/resources/buddyboss-addons/', $section );
	}

	/* release permalink ******************************************************/

	/**
	 * Answer the buddyboss.com REST chain locally: term lookup, release search, release body.
	 *
	 * @param string $link Permalink the release search should report.
	 *
	 * @return callable pre_http_request filter.
	 */
	protected function mock_addon_release_remote( $link = 'https://buddyboss.com/resources/buddyboss-addons/addons-1-1-1-2/' ) {
		return function ( $preempt, $args, $url ) use ( $link ) {
			if ( false === strpos( $url, 'buddyboss.com/resources/wp-json/wp/v2/' ) ) {
				return $preempt;
			}

			if ( false !== strpos( $url, '/wp/v2/addons?' ) ) {
				$body = array( array( 'id' => 3105 ) );
			} elseif ( false !== strpos( $url, '/wp/v2/bb-addons?' ) ) {
				$body = array(
					array(
						'id'    => 129177,
						'title' => array( 'rendered' => '1.1.1' ),
						'link'  => $link,
					),
				);
			} else {
				$body = array(
					'title'          => array( 'rendered' => '1.1.1' ),
					'release_fields' => array( 'changelog' => '<ul><li>Bug: Core - Fixed minor UI issue</li></ul>' ),
				);
			}

			return array(
				'response' => array(
					'code'    => 200,
					'message' => 'OK',
				),
				'headers'  => array(),
				'body'     => wp_json_encode( $body ),
				'cookies'  => array(),
				'filename' => null,
			);
		};
	}

	/**
	 * Fail the test if anything reaches the network.
	 *
	 * @return callable pre_http_request filter.
	 */
	protected function forbid_remote_requests() {
		return function ( $preempt, $args, $url ) {
			$this->fail( 'Unexpected remote request to ' . $url );
		};
	}

	public function test_addon_changelog_section_links_the_release_page_when_notes_are_found() {
		$filter = $this->mock_addon_release_remote();
		add_filter( 'pre_http_request', $filter, 10, 3 );

		$section = $this->admin->bb_get_addon_changelog_section( '1.1.1', 'buddyboss-addons', '', 'Visit the plugin website for release information' );

		remove_filter( 'pre_http_request', $filter, 10 );

		$this->assertStringContainsString( 'Fixed minor UI issue', $section );
		$this->assertStringContainsString( 'href="https://buddyboss.com/resources/buddyboss-addons/addons-1-1-1-2/"', $section );
		$this->assertStringContainsString( 'View the full release notes for version 1.1.1 on buddyboss.com', $section );
		$this->assertStringNotContainsString( 'resources/addons/buddyboss-addons/', $section, 'The archive is the fallback, not the link for a release that was found.' );
		$this->assertStringNotContainsString( 'Visit the plugin website', $section );
	}

	public function test_addon_changelog_section_keeps_the_archive_when_the_permalink_is_off_site() {
		$filter = $this->mock_addon_release_remote( 'https://example.com/not-ours/' );
		add_filter( 'pre_http_request', $filter, 10, 3 );

		$section = $this->admin->bb_get_addon_changelog_section( '1.1.1', 'buddyboss-addons', '', 'Visit the plugin website for release information' );

		remove_filter( 'pre_http_request', $filter, 10 );

		$this->assertStringContainsString( 'Fixed minor UI issue', $section, 'The notes are still shown; only the link is refused.' );
		$this->assertStringNotContainsString( 'example.com', $section );
		$this->assertStringContainsString( 'resources/addons/buddyboss-addons/', $section );
		$this->assertStringContainsString( 'Visit the plugin website for release information', $section );
	}

	public function test_addon_release_permalink_survives_the_cache() {
		$filter = $this->mock_addon_release_remote();
		add_filter( 'pre_http_request', $filter, 10, 3 );

		$state = '';
		$link  = '';
		$html  = $this->admin->bb_get_addon_release_notes_html( '1.1.1', 'buddyboss-addons', $state, $link );

		remove_filter( 'pre_http_request', $filter, 10 );

		$this->assertSame( 'ok', $state );
		$this->assertSame( 'https://buddyboss.com/resources/buddyboss-addons/addons-1-1-1-2/', $link );

		$forbid = $this->forbid_remote_requests();
		add_filter( 'pre_http_request', $forbid, 10, 3 );

		$cached_state = '';
		$cached_link  = '';
		$cached_html  = $this->admin->bb_get_addon_release_notes_html( '1.1.1', 'buddyboss-addons', $cached_state, $cached_link );

		remove_filter( 'pre_http_request', $forbid, 10 );

		$this->assertSame( $html, $cached_html );
		$this->assertSame( 'ok', $cached_state );
		$this->assertSame( $link, $cached_link, 'The permalink is cached with the notes it belongs to.' );
	}

	public function test_release_link_sanitizer_accepts_only_https_pages_on_buddyboss_com() {
		$this->assertSame(
			'https://buddyboss.com/resources/buddyboss-addons/addons-1-1-1-2/',
			$this->call( 'bb_sanitize_release_link', array( 'https://buddyboss.com/resources/buddyboss-addons/addons-1-1-1-2/' ) )
		);
		$this->assertSame(
			'https://www.buddyboss.com/resources/x/',
			$this->call( 'bb_sanitize_release_link', array( 'https://www.buddyboss.com/resources/x/' ) )
		);
		$this->assertSame( '', $this->call( 'bb_sanitize_release_link', array( 'http://buddyboss.com/resources/x/' ) ), 'Plain http is refused.' );
		$this->assertSame( '', $this->call( 'bb_sanitize_release_link', array( 'https://evilbuddyboss.com/' ) ), 'A host that merely ends in the letters is not a subdomain.' );
		$this->assertSame( '', $this->call( 'bb_sanitize_release_link', array( 'https://example.com/buddyboss.com/' ) ) );
		$this->assertSame( '', $this->call( 'bb_sanitize_release_link', array( 'javascript:alert(1)' ) ) );
		$this->assertSame( '', $this->call( 'bb_sanitize_release_link', array( array( 'https://buddyboss.com/' ) ) ), 'Only a string is a link.' );
	}
}
