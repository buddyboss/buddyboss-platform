<?php
/**
 * Tests for bp_core_load_buddypress_textdomain().
 *
 * The loader is locale-change aware: it re-resolves the locale and reloads the
 * catalog on init, change_locale, and the WPML/Polylang switch actions. These
 * tests pin the branches that are easy to break and expensive to notice —
 * multi-location precedence, the third-party override chain, the change_locale
 * fast path, and the self-heal after an external unload_textdomain().
 *
 * @group core
 * @group textdomain
 */
class BP_Tests_Core_Textdomain extends BP_UnitTestCase {

	/**
	 * Base directory holding the generated .mo fixtures for one test.
	 *
	 * @var string
	 */
	protected $fixture_base = '';

	/**
	 * Locale forced through the `locale` filter for the current test.
	 *
	 * @var string
	 */
	protected $forced_locale = '';

	/**
	 * Fixture catalogs written into WP_LANG_DIR, removed on tear down.
	 *
	 * @var array
	 */
	protected $lang_dir_files = array();

	/**
	 * The textdomain registry in place before the test replaced it.
	 *
	 * @var WP_Textdomain_Registry|null
	 */
	protected $orig_registry = null;

	public function set_up() {
		parent::set_up();

		require_once ABSPATH . WPINC . '/pomo/mo.php';

		$this->fixture_base = trailingslashit( get_temp_dir() ) . 'bb-textdomain-' . uniqid( '', true );
		wp_mkdir_p( $this->fixture_base );

		// Start every test from a known state: nothing loaded for the domain.
		unload_textdomain( 'buddyboss', true );

		// A plain (non-reloadable) unload_textdomain() in an earlier test sets
		// this flag, and _load_textdomain_just_in_time() short-circuits on it for
		// the rest of the process — which would silently disable core's own JIT
		// reload in later tests. Clear it so each test starts from clean core state.
		unset( $GLOBALS['l10n_unloaded']['buddyboss'] );

		$this->orig_registry = isset( $GLOBALS['wp_textdomain_registry'] ) ? $GLOBALS['wp_textdomain_registry'] : null;
	}

	public function tear_down() {
		remove_all_filters( 'buddyboss_locale_locations' );
		remove_all_filters( 'override_load_textdomain' );
		remove_all_filters( 'pre_load_textdomain' );
		remove_all_filters( 'load_textdomain_mofile' );
		remove_all_filters( 'plugin_locale' );
		remove_all_filters( 'locale' );

		unload_textdomain( 'buddyboss', true );

		foreach ( $this->lang_dir_files as $file ) {
			if ( file_exists( $file ) ) {
				unlink( $file );
			}
		}
		$this->lang_dir_files = array();

		if ( null !== $this->orig_registry ) {
			$GLOBALS['wp_textdomain_registry'] = $this->orig_registry;
			$this->orig_registry               = null;
		}

		$this->remove_fixture_dir( $this->fixture_base );

		parent::tear_down();
	}

	/* Helpers ************************************************************/

	/**
	 * Recursively remove a directory tree.
	 *
	 * @param string $dir Directory to remove.
	 */
	protected function remove_fixture_dir( $dir ) {
		if ( ! $dir || ! is_dir( $dir ) ) {
			return;
		}

		foreach ( (array) glob( trailingslashit( $dir ) . '*' ) as $item ) {
			if ( is_dir( $item ) ) {
				$this->remove_fixture_dir( $item );
			} else {
				@unlink( $item ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- Best-effort fixture cleanup.
			}
		}

		@rmdir( $dir ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- Best-effort fixture cleanup.
	}

	/**
	 * Create a directory under the fixture base.
	 *
	 * @param string $name Sub-directory name.
	 * @return string Trailing-slashed absolute path.
	 */
	protected function make_dir( $name ) {
		$dir = trailingslashit( $this->fixture_base . '/' . $name );
		wp_mkdir_p( $dir );

		return $dir;
	}

	/**
	 * Write a buddyboss-{locale}.mo catalog into a directory.
	 *
	 * @param string $dir    Trailing-slashed directory.
	 * @param string $locale Locale.
	 * @param array  $pairs  msgid => msgstr map.
	 * @return string Absolute path to the written .mo file.
	 */
	protected function make_catalog( $dir, $locale, array $pairs ) {
		$mo = new MO();
		$mo->set_header( 'Project-Id-Version', 'buddyboss-tests' );
		$mo->set_header( 'Plural-Forms', 'nplurals=2; plural=n != 1;' );

		foreach ( $pairs as $singular => $translation ) {
			$mo->add_entry(
				new Translation_Entry(
					array(
						'singular'     => $singular,
						'translations' => array( $translation ),
					)
				)
			);
		}

		$path = $dir . 'buddyboss-' . $locale . '.mo';
		$this->assertTrue( $mo->export_to_file( $path ), 'Failed to write the .mo fixture.' );

		return $path;
	}

	/**
	 * Force the request locale. A unique locale per test keeps the loader's
	 * static memo (and WP's translation-file cache) from leaking between tests.
	 *
	 * @param string $locale Locale to force.
	 */
	protected function force_locale( $locale ) {
		$this->forced_locale = $locale;

		add_filter(
			'locale',
			function () use ( $locale ) {
				return $locale;
			},
			99
		);
	}

	/**
	 * Point the loader at an explicit, ordered list of locations.
	 *
	 * @param array $locations Trailing-slashed directories, highest precedence first.
	 */
	protected function set_locations( array $locations ) {
		add_filter(
			'buddyboss_locale_locations',
			function () use ( $locations ) {
				return $locations;
			},
			99
		);
	}

	/* Tests **************************************************************/

	/**
	 * A truthy load_textdomain() is not proof the catalog loaded.
	 *
	 * `override_load_textdomain` listeners (Loco Translate, WPML String
	 * Translation) answer true for paths that hold no file. Returning on the
	 * first truthy result therefore skipped the location that really had the
	 * catalog, silently dropping every translation. The location the probe
	 * found on disk must win.
	 */
	public function test_override_claiming_a_higher_precedence_path_does_not_mask_the_real_catalog() {
		$locale  = 'zz_OVR';
		$claimed = $this->make_dir( 'claimed' ); // No catalog here.
		$real    = $this->make_dir( 'real' );

		$this->make_catalog( $real, $locale, array( 'BB_OVERRIDE_MSG' => 'BB_OVERRIDE_OK' ) );

		$this->force_locale( $locale );
		$this->set_locations( array( $claimed, $real ) );

		// Claim the higher-precedence path without loading anything, exactly as
		// a third-party translation plugin does.
		add_filter(
			'override_load_textdomain',
			function ( $override, $domain, $mofile ) use ( $claimed ) {
				if ( 'buddyboss' === $domain && 0 === strpos( $mofile, $claimed ) ) {
					return true;
				}

				return $override;
			},
			10,
			3
		);

		bp_core_load_buddypress_textdomain();

		$this->assertSame(
			'BB_OVERRIDE_OK',
			__( 'BB_OVERRIDE_MSG', 'buddyboss' ), // phpcs:ignore WordPress.WP.I18n -- Fixture msgid.
			'The loop stopped at a location an override merely claimed, so the real catalog never loaded.'
		);
	}

	/**
	 * load_textdomain() is an extension point, not just a file read.
	 *
	 * When no location has a catalog on disk, every location must still be
	 * offered to load_textdomain() so that `override_load_textdomain` listeners
	 * get their chance — that is how WPML String Translation serves its custom
	 * MOs from a directory the loader never probes.
	 */
	public function test_load_textdomain_is_attempted_even_when_nothing_exists_on_disk() {
		$locale = 'zz_NOF';
		$empty  = $this->make_dir( 'empty' );

		$this->force_locale( $locale );
		$this->set_locations( array( $empty ) );

		$seen = array();
		add_filter(
			'override_load_textdomain',
			function ( $override, $domain, $mofile ) use ( &$seen ) {
				if ( 'buddyboss' === $domain ) {
					$seen[] = $mofile;
				}

				return $override;
			},
			10,
			3
		);

		bp_core_load_buddypress_textdomain();

		$this->assertNotEmpty(
			$seen,
			'load_textdomain() was skipped because no file was readable, removing the override extension point.'
		);
		$this->assertContains( $empty . 'buddyboss-' . $locale . '.mo', $seen );
	}

	/**
	 * The first location holding a catalog wins over later ones.
	 */
	public function test_higher_precedence_location_wins() {
		$locale = 'zz_PRE';
		$high   = $this->make_dir( 'high' );
		$low    = $this->make_dir( 'low' );

		$this->make_catalog( $high, $locale, array( 'BB_PRECEDENCE_MSG' => 'BB_FROM_HIGH' ) );
		$this->make_catalog( $low, $locale, array( 'BB_PRECEDENCE_MSG' => 'BB_FROM_LOW' ) );

		$this->force_locale( $locale );
		$this->set_locations( array( $high, $low ) );

		bp_core_load_buddypress_textdomain();

		$this->assertSame(
			'BB_FROM_HIGH',
			__( 'BB_PRECEDENCE_MSG', 'buddyboss' ) // phpcs:ignore WordPress.WP.I18n -- Fixture msgid.
		);
	}

	/**
	 * A locale change must invalidate the memo and reload the catalog.
	 */
	public function test_reloads_when_the_locale_changes() {
		$first  = 'zz_LC1';
		$second = 'zz_LC2';
		$dir    = $this->make_dir( 'locales' );

		$this->make_catalog( $dir, $first, array( 'BB_LOCALE_MSG' => 'BB_FIRST' ) );
		$this->make_catalog( $dir, $second, array( 'BB_LOCALE_MSG' => 'BB_SECOND' ) );

		$this->set_locations( array( $dir ) );

		$current = $first;
		add_filter(
			'locale',
			function () use ( &$current ) {
				return $current;
			},
			99
		);

		bp_core_load_buddypress_textdomain();
		$this->assertSame( 'BB_FIRST', __( 'BB_LOCALE_MSG', 'buddyboss' ) ); // phpcs:ignore WordPress.WP.I18n -- Fixture msgid.

		$current = $second;
		bp_core_load_buddypress_textdomain();
		$this->assertSame(
			'BB_SECOND',
			__( 'BB_LOCALE_MSG', 'buddyboss' ), // phpcs:ignore WordPress.WP.I18n -- Fixture msgid.
			'The loader did not reload after the locale changed.'
		);
	}

	/**
	 * A third-party unload_textdomain() must not strand the domain.
	 *
	 * Loco Translate does exactly this on live requests ("Unloaded 1 premature
	 * text domain"), and WPML's published workaround unloads before re-calling
	 * this loader. A locale-only gate would return early and leave the domain
	 * unloaded for the rest of the request.
	 */
	public function test_reloads_after_a_third_party_unloads_the_domain() {
		$locale = 'zz_UNL';
		$dir    = $this->make_dir( 'unload' );

		$this->make_catalog( $dir, $locale, array( 'BB_UNLOAD_MSG' => 'BB_UNLOAD_OK' ) );

		$this->force_locale( $locale );
		$this->set_locations( array( $dir ) );

		bp_core_load_buddypress_textdomain();
		$this->assertSame( 'BB_UNLOAD_OK', __( 'BB_UNLOAD_MSG', 'buddyboss' ) ); // phpcs:ignore WordPress.WP.I18n -- Fixture msgid.

		// Same locale, but someone else dropped the catalog.
		unload_textdomain( 'buddyboss' );
		$this->assertFalse( is_textdomain_loaded( 'buddyboss' ) );

		bp_core_load_buddypress_textdomain();
		$this->assertSame(
			'BB_UNLOAD_OK',
			__( 'BB_UNLOAD_MSG', 'buddyboss' ), // phpcs:ignore WordPress.WP.I18n -- Fixture msgid.
			'The loader did not self-heal after an external unload_textdomain().'
		);
	}

	/**
	 * During change_locale, skip the redundant unload + full MO re-parse when
	 * core's own switcher has already loaded the best available catalog.
	 *
	 * Pre-WP 6.5 has no translation-file cache, so a cron switching locale per
	 * recipient would otherwise re-parse the catalog on every switch.
	 */
	public function test_change_locale_fast_path_skips_the_reload_when_core_has_the_best_catalog() {
		$locale = 'zz_FST';
		$dir    = $this->make_dir( 'fastpath' );
		$mofile = $this->make_catalog( $dir, $locale, array( 'BB_FAST_MSG' => 'BB_FAST_OK' ) );

		$this->assertNotFalse(
			has_action( 'change_locale', 'bp_core_load_buddypress_textdomain' ),
			'The loader is not hooked on change_locale.'
		);

		$this->force_locale( $locale );

		// Point the loader at a location with no catalog, so the probe finds
		// nothing better than what core already loaded.
		$this->set_locations( array( $this->make_dir( 'fastpath-empty' ) ) );

		// Stand in for core's switcher having already reloaded the domain.
		load_textdomain( 'buddyboss', $mofile, $locale );
		$this->assertTrue( is_textdomain_loaded( 'buddyboss' ) );

		$loads = 0;
		add_filter(
			'load_textdomain_mofile',
			function ( $file, $domain ) use ( &$loads ) {
				if ( 'buddyboss' === $domain ) {
					$loads++;
				}

				return $file;
			},
			10,
			2
		);

		do_action( 'change_locale', $locale );

		$this->assertSame(
			0,
			$loads,
			'The change_locale fast path did not skip the redundant reload.'
		);
		$this->assertSame( 'BB_FAST_OK', __( 'BB_FAST_MSG', 'buddyboss' ) ); // phpcs:ignore WordPress.WP.I18n -- Fixture msgid.
	}

	/**
	 * `plugin_locale` must be applied, so the custom-location lookup, the
	 * change_locale fast path and the load_plugin_textdomain() fallback all
	 * resolve the same locale for this domain.
	 */
	public function test_plugin_locale_filter_is_applied() {
		$filtered = 'zz_PLG';
		$dir      = $this->make_dir( 'pluginlocale' );

		$this->make_catalog( $dir, $filtered, array( 'BB_PLUGIN_LOCALE_MSG' => 'BB_PLUGIN_LOCALE_OK' ) );

		// The request locale is something else entirely.
		$this->force_locale( 'zz_REQ' );
		$this->set_locations( array( $dir ) );

		add_filter(
			'plugin_locale',
			function ( $locale, $domain ) use ( $filtered ) {
				return 'buddyboss' === $domain ? $filtered : $locale;
			},
			10,
			2
		);

		bp_core_load_buddypress_textdomain();

		$this->assertSame(
			'BB_PLUGIN_LOCALE_OK',
			__( 'BB_PLUGIN_LOCALE_MSG', 'buddyboss' ), // phpcs:ignore WordPress.WP.I18n -- Fixture msgid.
			'plugin_locale was not applied when resolving the catalog locale.'
		);
	}

	/**
	 * The resolved locale must be passed to load_textdomain() explicitly.
	 *
	 * Without the third argument core calls determine_locale() itself, which
	 * defeats the deferral of user resolution and can register a catalog under
	 * a different locale than the one its path was built from.
	 */
	public function test_resolved_locale_is_passed_to_load_textdomain() {
		if ( version_compare( get_bloginfo( 'version' ), '6.3', '<' ) ) {
			$this->markTestSkipped( 'pre_load_textdomain (with $locale) requires WP 6.3+.' );
		}

		$locale = 'zz_ARG';
		$dir    = $this->make_dir( 'localearg' );

		$this->make_catalog( $dir, $locale, array( 'BB_ARG_MSG' => 'BB_ARG_OK' ) );

		$this->force_locale( $locale );
		$this->set_locations( array( $dir ) );

		$seen = array();
		add_filter(
			'pre_load_textdomain',
			function ( $loaded, $domain, $mofile, $passed_locale ) use ( &$seen ) {
				if ( 'buddyboss' === $domain ) {
					$seen[] = $passed_locale;
				}

				return $loaded;
			},
			10,
			4
		);

		bp_core_load_buddypress_textdomain();

		$this->assertNotEmpty( $seen, 'load_textdomain() was never reached.' );
		$this->assertSame(
			array( $locale ),
			array_values( array_unique( $seen ) ),
			'load_textdomain() was called without the resolved locale, letting core re-derive it.'
		);
	}

	/**
	 * A real switch_to_locale() must swap the catalog, and restoring swaps back.
	 *
	 * Exercises the whole core path rather than a synthetic do_action():
	 * WP_Locale_Switcher::load_translations() -> unload + JIT reload -> change_locale.
	 */
	public function test_real_switch_to_locale_swaps_the_catalog_and_restores_it() {
		$dir = $this->make_dir( 'switch' );

		$this->make_catalog( $dir, 'en_US', array( 'BB_SWITCH_MSG' => 'BB_SWITCH_EN' ) );
		$this->make_catalog( $dir, 'es_ES', array( 'BB_SWITCH_MSG' => 'BB_SWITCH_ES' ) );

		$this->set_locations( array( $dir ) );

		bp_core_load_buddypress_textdomain();
		$this->assertSame( 'BB_SWITCH_EN', __( 'BB_SWITCH_MSG', 'buddyboss' ) ); // phpcs:ignore WordPress.WP.I18n -- Fixture msgid.

		$this->assertTrue( switch_to_locale( 'es_ES' ), 'switch_to_locale() refused the locale.' );
		$this->assertSame(
			'BB_SWITCH_ES',
			__( 'BB_SWITCH_MSG', 'buddyboss' ), // phpcs:ignore WordPress.WP.I18n -- Fixture msgid.
			'The catalog was not reloaded for the switched-to locale.'
		);

		restore_previous_locale();
		$this->assertSame(
			'BB_SWITCH_EN',
			__( 'BB_SWITCH_MSG', 'buddyboss' ), // phpcs:ignore WordPress.WP.I18n -- Fixture msgid.
			'The catalog was not restored after restore_previous_locale().'
		);
	}

	/**
	 * The change_locale fast path is safe: core really does reload non-default domains.
	 *
	 * WP_Locale_Switcher::load_translations() iterates every entry of $l10n,
	 * skips only 'default' (handled by load_default_textdomain()), and does
	 * unload_textdomain( $domain, true ) + get_translations_for_domain( $domain )
	 * for the rest — all before do_action( 'change_locale' ). So when our
	 * priority-0 listener sees is_textdomain_loaded() === true, the loaded
	 * catalog is necessarily the freshly reloaded one for the NEW locale, and
	 * skipping our own reload cannot strand the previous locale's strings.
	 *
	 * This asserts that premise directly at priority -1, ahead of our listener.
	 */
	public function test_core_reloads_non_default_domains_before_change_locale_fires() {
		$plugins_dir = trailingslashit( WP_LANG_DIR . '/plugins' );

		if ( ! wp_mkdir_p( $plugins_dir ) || ! is_writable( $plugins_dir ) ) {
			$this->markTestSkipped( 'WP_LANG_DIR/plugins is not writable in this environment.' );
		}

		// Core's registry knows WP_LANG_DIR/plugins, so its JIT reload can find
		// these. No buddyboss_locale_locations filter: the real precedence list
		// applies, and WP_LANG_DIR/plugins is part of it.
		$this->lang_dir_files[] = $this->make_catalog( $plugins_dir, 'en_US', array( 'BB_FASTSAFE_MSG' => 'BB_FASTSAFE_EN' ) );
		$this->lang_dir_files[] = $this->make_catalog( $plugins_dir, 'es_ES', array( 'BB_FASTSAFE_MSG' => 'BB_FASTSAFE_ES' ) );

		// The registry memoises per-directory .mo listings and scanned this
		// directory before the fixtures existed; re-create it so it sees them,
		// as it would on a real site where the files predate the request.
		$GLOBALS['wp_textdomain_registry'] = new WP_Textdomain_Registry();

		bp_core_load_buddypress_textdomain();
		$this->assertSame( 'BB_FASTSAFE_EN', __( 'BB_FASTSAFE_MSG', 'buddyboss' ) ); // phpcs:ignore WordPress.WP.I18n -- Fixture msgid.

		// Capture the state our priority-0 listener will see.
		$state = array();
		add_action(
			'change_locale',
			function () use ( &$state ) {
				$state['loaded']   = is_textdomain_loaded( 'buddyboss' );
				$state['resolved'] = determine_locale();
				$state['string']   = __( 'BB_FASTSAFE_MSG', 'buddyboss' ); // phpcs:ignore WordPress.WP.I18n -- Fixture msgid.
			},
			-1
		);

		$this->assertTrue( switch_to_locale( 'es_ES' ), 'switch_to_locale() refused the locale.' );

		$this->assertSame( 'es_ES', $state['resolved'], 'The locale was not switched before change_locale fired.' );
		$this->assertTrue(
			$state['loaded'],
			'Core did not reload the non-default domain before change_locale; the fast path premise would not hold.'
		);
		$this->assertSame(
			'BB_FASTSAFE_ES',
			$state['string'],
			"Core reloaded the domain but kept the previous locale's catalog."
		);

		// Still correct after our listener ran and chose to skip.
		$this->assertSame(
			'BB_FASTSAFE_ES',
			__( 'BB_FASTSAFE_MSG', 'buddyboss' ), // phpcs:ignore WordPress.WP.I18n -- Fixture msgid.
			'The fast path skipped the reload and left the previous locale loaded.'
		);

		restore_previous_locale();
	}
}
