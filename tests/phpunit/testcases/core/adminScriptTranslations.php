<?php
/**
 * Tests for the admin React apps' script translations
 * (src/bp-core/admin/bb-admin-script-translations.php).
 *
 * Each test forces its own locale: the fallback memoises per handle + locale,
 * and a shared locale would let one test's result answer the next.
 *
 * @group core
 * @group textdomain
 */
class BP_Tests_Core_AdminScriptTranslations extends BP_UnitTestCase {

	/**
	 * Base directory for the .mo fixtures of one test.
	 *
	 * @var string
	 */
	protected $fixture_base = '';

	/**
	 * JSON fixtures written into the bundled languages/ dir, removed on tear down.
	 *
	 * @var array
	 */
	protected $bundled_files = array();

	/**
	 * Locale forced for the current test.
	 *
	 * @var string
	 */
	protected $locale = '';

	public function set_up() {
		parent::set_up();

		require_once ABSPATH . WPINC . '/pomo/mo.php';
		require_once buddypress()->plugin_dir . 'bp-core/admin/bb-admin-script-translations.php';

		// The file registers its hooks when it is first included; the test case
		// restores $wp_filter after every test, so run the same registration.
		bb_admin_script_translations_register_hooks();

		$this->fixture_base = trailingslashit( get_temp_dir() ) . 'bb-script-i18n-' . uniqid( '', true );
		wp_mkdir_p( $this->fixture_base );

		// A unique, valid-looking locale per test.
		$this->locale = 'de_DE' . wp_rand( 1000, 9999 ) . substr( md5( uniqid( '', true ) ), 0, 4 );
		$locale       = $this->locale;
		add_filter(
			'locale',
			function () use ( $locale ) {
				return $locale;
			},
			99
		);

		unload_textdomain( 'buddyboss', true );
	}

	public function tear_down() {
		remove_all_filters( 'locale' );
		remove_all_filters( 'gettext' );

		unload_textdomain( 'buddyboss', true );
		foreach ( array( 'bb-admin-settings', 'bb-admin-integrations', 'bb-admin-common', 'bb-rl-onboarding-script' ) as $handle ) {
			wp_dequeue_script( $handle );
			wp_deregister_script( $handle );
		}

		foreach ( $this->bundled_files as $file ) {
			if ( file_exists( $file ) ) {
				unlink( $file );
			}
		}
		$this->bundled_files = array();

		foreach ( (array) glob( trailingslashit( $this->fixture_base ) . '*' ) as $file ) {
			@unlink( $file ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- Best-effort fixture cleanup.
		}
		@rmdir( $this->fixture_base ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- Best-effort fixture cleanup.

		parent::tear_down();
	}

	/* Helpers ************************************************************/

	/**
	 * Write a buddyboss .mo fixture and load it into the `buddyboss` domain.
	 *
	 * Successive calls in one test layer catalogs like the Platform loader does
	 * (the first one loaded wins on WP 6.5+).
	 *
	 * @param string $name         Fixture file name (without extension).
	 * @param array  $entries      Translation_Entry args.
	 * @param string $plural_forms Plural-Forms header.
	 * @return string Path to the .mo file.
	 */
	protected function load_catalog( $name, array $entries, $plural_forms = 'nplurals=2; plural=n != 1;' ) {
		$mo = new MO();
		$mo->set_header( 'Project-Id-Version', 'buddyboss-tests' );
		$mo->set_header( 'Plural-Forms', $plural_forms );

		foreach ( $entries as $args ) {
			$mo->add_entry( new Translation_Entry( $args ) );
		}

		$path = trailingslashit( $this->fixture_base ) . $name . '-' . $this->locale . '.mo';
		$this->assertTrue( $mo->export_to_file( $path ), 'Failed to write the .mo fixture.' );
		$this->assertTrue( load_textdomain( 'buddyboss', $path, $this->locale ), 'Failed to load the .mo fixture.' );

		return $path;
	}

	/**
	 * Write a JSON file in the bundled languages/ dir.
	 *
	 * @param string $name   File name.
	 * @param string $member Translation for "Member".
	 */
	protected function bundled_json( $name, $member ) {
		$file                  = buddypress()->plugin_dir . 'languages/' . $name;
		$this->bundled_files[] = $file;
		file_put_contents( $file, wp_json_encode( array( 'locale_data' => array( 'messages' => array( '' => array(), 'Member' => array( $member ) ) ) ) ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
	}

	/**
	 * Decode the locale data of a make-json style payload.
	 *
	 * @param string $json JSON.
	 * @return array msgid => translations.
	 */
	protected function locale_data( $json ) {
		$this->assertIsString( $json );
		$decoded = json_decode( $json, true );
		$this->assertIsArray( $decoded );

		return $decoded['locale_data']['messages'];
	}

	/**
	 * Register the Settings handle the way bb-admin-settings-page.php does.
	 */
	protected function register_settings_handle() {
		wp_register_script( 'bb-admin-settings', buddypress()->plugin_url . 'bp-core/admin/bb-settings/settings/build/index.js', array(), '1', true );
		bb_admin_set_script_translations( 'bb-admin-settings' );
	}

	/* Tests **************************************************************/

	/**
	 * The manifest the fallback depends on ships and lists the Settings
	 * strings from the issue (rendered only by React).
	 */
	public function test_manifest_lists_react_only_strings() {
		$strings = bb_admin_get_script_translation_strings();

		$this->assertArrayHasKey( 'bb-admin-settings', $strings );
		$this->assertArrayHasKey( 'bb-admin-integrations', $strings );
		$this->assertArrayHasKey( 'bb-admin-common', $strings );
		$this->assertArrayHasKey( 'bb-rl-onboarding-script', $strings );

		$singulars = wp_list_pluck( $strings['bb-admin-settings'], 0 );
		$this->assertContains( 'Back to Settings', $singulars );
		$this->assertContains( 'Member', $singulars );
	}

	/**
	 * The standard JSON files are named for the scripts WordPress enqueues in
	 * the released plugin (src/ is the plugin root there).
	 */
	public function test_shipped_json_names_match_the_enqueued_build_paths() {
		$builds = array(
			'bp-core/admin/bb-settings/settings/build/index.js',
			'bp-core/admin/bb-settings/integrations/build/index.js',
			'bp-core/admin/bb-settings/common/build/index.js',
			'bp-core/admin/bb-settings/rl-onboarding/build/rl-onboarding.js',
		);

		foreach ( $builds as $build ) {
			$this->assertFileExists( buddypress()->plugin_dir . $build );
			$this->assertFileExists( buddypress()->plugin_dir . 'languages/buddyboss-de_DE-' . md5( $build ) . '.json', $build );
		}
	}

	/**
	 * The fallback is registered on core's pre_load_script_translations filter
	 * with the priority and arguments it needs.
	 */
	public function test_fallback_is_registered() {
		remove_all_filters( 'pre_load_script_translations' );

		bb_admin_script_translations_register_hooks();

		$this->assertSame( 10, has_filter( 'pre_load_script_translations', 'bb_admin_script_translations_fallback' ) );
		$this->assertSame( 4, $GLOBALS['wp_filter']['pre_load_script_translations']->callbacks[10]['bb_admin_script_translations_fallback']['accepted_args'] );
	}

	/**
	 * The real Settings and Integrations enqueues attach the `buddyboss`
	 * translations.
	 */
	public function test_settings_and_integrations_enqueues_attach_translations() {
		require_once buddypress()->plugin_dir . 'bp-core/admin/bb-admin-settings-page.php';
		require_once buddypress()->plugin_dir . 'bp-core/admin/bb-admin-integrations-page.php';

		bb_admin_settings_enqueue_app_script(
			buddypress()->plugin_url . 'bp-core/admin/bb-settings/settings/build',
			array(
				'dependencies' => array(),
				'version'      => '1',
			)
		);
		bb_admin_integrations_enqueue_app_script( buddypress()->plugin_url . 'bp-core/admin/bb-settings/integrations/build', array(), '1' );

		foreach ( array( 'bb-admin-settings', 'bb-admin-integrations' ) as $handle ) {
			$this->assertTrue( wp_script_is( $handle, 'enqueued' ), $handle );
			$this->assertSame( 'buddyboss', wp_scripts()->registered[ $handle ]->textdomain, $handle );
			$this->assertSame( buddypress()->plugin_dir . 'languages', wp_scripts()->registered[ $handle ]->translations_path, $handle );
		}
	}

	/**
	 * The real registration of the shared admin-common handle attaches the
	 * `buddyboss` translations.
	 */
	public function test_admin_common_registration_attaches_translations() {
		require_once buddypress()->plugin_dir . 'bp-core/admin/bb-admin-common-assets.php';
		wp_deregister_script( 'bb-admin-common' );

		bb_register_admin_common_assets();

		$script = wp_scripts()->registered['bb-admin-common'];
		$this->assertSame( 'buddyboss', $script->textdomain );
		$this->assertSame( buddypress()->plugin_dir . 'languages', $script->translations_path );
	}

	/**
	 * The real ReadyLaunch onboarding enqueue attaches the `buddyboss` translations.
	 */
	public function test_rl_onboarding_enqueue_attaches_translations() {
		if ( ! class_exists( 'BB_Setup_Wizard_Manager' ) ) {
			require_once buddypress()->plugin_dir . 'bp-core/classes/class-bb-setup-wizard-manager.php';
		}
		if ( ! class_exists( 'BB_ReadyLaunch_Onboarding' ) ) {
			require_once buddypress()->plugin_dir . 'bp-core/admin/bb-settings/rl-onboarding/index.php';
		}

		$onboarding = BB_ReadyLaunch_Onboarding::instance();
		$onboarding->build_config(); // Production builds it on bp_init.
		$enqueue    = new ReflectionMethod( $onboarding, 'enqueue_wizard_assets' );
		$enqueue->setAccessible( true );
		$enqueue->invoke( $onboarding );

		$get_config = new ReflectionMethod( $onboarding, 'get_config' );
		$get_config->setAccessible( true );
		$handle = $get_config->invoke( $onboarding, 'react_script_handle' );

		$this->assertArrayHasKey( $handle, wp_scripts()->registered );
		$this->assertSame( 'buddyboss', wp_scripts()->registered[ $handle ]->textdomain );
		$this->assertArrayHasKey( $handle, bb_admin_get_script_translation_strings(), 'The manifest must list the handle the wizard really enqueues.' );
	}

	/**
	 * End to end through core: a registered admin handle gets the strings of
	 * the loaded .mo (no site JSON exists).
	 */
	public function test_handle_gets_translations_from_the_loaded_catalog() {
		$this->load_catalog(
			'base',
			array(
				array(
					'singular'     => 'Member',
					'translations' => array( 'Mitglied' ),
				),
			)
		);
		$this->register_settings_handle();

		$data = $this->locale_data( load_script_textdomain( 'bb-admin-settings', 'buddyboss', buddypress()->plugin_dir . 'languages' ) );

		$this->assertSame( array( 'Mitglied' ), $data['Member'] );
		$this->assertSame( $this->locale, $data['']['lang'] );
	}

	/**
	 * The bundled JSON can not know which catalog PHP loaded (Loco, WPML,
	 * plugin_locale), so it never overrides the loaded translations.
	 */
	public function test_bundled_json_does_not_override_the_loaded_catalog() {
		$this->load_catalog(
			'base',
			array(
				array(
					'singular'     => 'Member',
					'translations' => array( 'Mitglied (loaded)' ),
				),
			)
		);
		$this->bundled_json( 'buddyboss-' . $this->locale . '-bb-admin-settings.json', 'FROM-BUNDLED-JSON' );
		$this->register_settings_handle();

		$data = $this->locale_data( load_script_textdomain( 'bb-admin-settings', 'buddyboss', buddypress()->plugin_dir . 'languages' ) );

		$this->assertSame( array( 'Mitglied (loaded)' ), $data['Member'] );
	}

	/**
	 * Layered catalogs (a Loco Translate override loaded before the base
	 * catalog) resolve first-match, like PHP — singulars and plurals alike.
	 */
	public function test_layered_catalogs_resolve_like_php() {
		$this->load_catalog(
			'override',
			array(
				array(
					'singular'     => 'Member',
					'translations' => array( 'Mitglied (Loco)' ),
				),
				array(
					'singular'     => 'Discussion',
					'plural'       => 'Discussions',
					'is_plural'    => true,
					'translations' => array( 'Thema (Loco)', 'Themen (Loco)' ),
				),
			)
		);
		$this->load_catalog(
			'base',
			array(
				array(
					'singular'     => 'Member',
					'translations' => array( 'Mitglied' ),
				),
				array(
					'singular'     => 'Discussion',
					'plural'       => 'Discussions',
					'is_plural'    => true,
					'translations' => array( 'Diskussion', 'Diskussionen' ),
				),
			)
		);

		$data = $this->locale_data( bb_admin_script_translations_fallback( null, false, 'bb-admin-settings', 'buddyboss' ) );

		$this->assertSame( array( __( 'Member', 'buddyboss' ) ), $data['Member'] );
		$this->assertSame( array( 'Mitglied (Loco)' ), $data['Member'] );
		$this->assertSame( array( 'Thema (Loco)', 'Themen (Loco)' ), $data['Discussion'] );
	}

	/**
	 * Runtime translators (WPML String Translation) work through the gettext
	 * filters with no .mo at all.
	 */
	public function test_gettext_filters_apply_without_a_loaded_mo() {
		add_filter(
			'gettext',
			function ( $translation, $text, $domain ) {
				return ( 'buddyboss' === $domain && 'Member' === $text ) ? 'Mitglied (WPML)' : $translation;
			},
			10,
			3
		);

		$data = $this->locale_data( bb_admin_script_translations_fallback( null, false, 'bb-admin-settings', 'buddyboss' ) );

		$this->assertSame( array( 'Mitglied (WPML)' ), $data['Member'] );
	}

	/**
	 * Plural entries carry every form, keyed by the singular (Jed format), and
	 * strings the app does not render are left out.
	 */
	public function test_plural_forms_and_manifest_scoping() {
		$this->load_catalog(
			'base',
			array(
				array(
					'singular'     => 'Discussion',
					'plural'       => 'Discussions',
					'is_plural'    => true,
					'translations' => array( 'Diskussion', 'Diskussionen' ),
				),
				array(
					'singular'     => 'A PHP-only string the React app never renders',
					'translations' => array( 'Nur PHP' ),
				),
			)
		);

		$data = $this->locale_data( bb_admin_script_translations_fallback( null, false, 'bb-admin-settings', 'buddyboss' ) );

		$this->assertSame( array( 'Diskussion', 'Diskussionen' ), $data['Discussion'] );
		$this->assertArrayNotHasKey( 'A PHP-only string the React app never renders', $data );
		$this->assertSame( 'nplurals=2; plural=n != 1;', $data['']['plural-forms'] );
	}

	/**
	 * Locales with a single plural form (ja, zh, ko, th, vi, id) keep their
	 * plural strings.
	 */
	public function test_single_plural_form_locales_keep_plural_strings() {
		$this->load_catalog(
			'base',
			array(
				array(
					'singular'     => 'Discussion',
					'plural'       => 'Discussions',
					'is_plural'    => true,
					'translations' => array( '議論' ),
				),
			),
			'nplurals=1; plural=0;'
		);

		$data = $this->locale_data( bb_admin_script_translations_fallback( null, false, 'bb-admin-settings', 'buddyboss' ) );

		$this->assertSame( array( '議論' ), $data['Discussion'] );
	}

	/**
	 * Plural slots follow the form index, also when a form no integer count
	 * selects sits in the middle (Crowdin's cs rule: form 2 is fractions only).
	 */
	public function test_plural_slots_follow_the_form_index() {
		$this->load_catalog(
			'base',
			array(
				array(
					'singular'     => 'Discussion',
					'plural'       => 'Discussions',
					'is_plural'    => true,
					'translations' => array( 'diskuse', 'diskuse (2-4)', 'diskuse (fraction)', 'diskusí' ),
				),
			),
			'nplurals=4; plural=(n == 1) ? 0 : ((n >= 2 && n <= 4) ? 1 : 3);'
		);

		$data = $this->locale_data( bb_admin_script_translations_fallback( null, false, 'bb-admin-settings', 'buddyboss' ) );

		$this->assertCount( 4, $data['Discussion'] );
		$this->assertSame( 'diskuse', $data['Discussion'][0] );
		$this->assertSame( 'diskuse (2-4)', $data['Discussion'][1] );
		$this->assertSame( _n( 'Discussion', 'Discussions', 10, 'buddyboss' ), $data['Discussion'][3] );
		$this->assertSame( 'diskusí', $data['Discussion'][3] );
	}

	/**
	 * A Plural-Forms header core can not parse (no space after ';') falls back
	 * to core's default rule, so React picks the same form as PHP _n().
	 */
	public function test_unparsable_plural_forms_header_follows_core() {
		$this->load_catalog(
			'base',
			array(
				array(
					'singular'     => 'Discussion',
					'plural'       => 'Discussions',
					'is_plural'    => true,
					'translations' => array( 'Diskussion', 'Diskussionen' ),
				),
			),
			'nplurals=2;plural=(n > 1);'
		);

		$data = $this->locale_data( bb_admin_script_translations_fallback( null, false, 'bb-admin-settings', 'buddyboss' ) );

		$this->assertSame( 'nplurals=2; plural=n != 1;', $data['']['plural-forms'] );
		$this->assertSame( _n( 'Discussion', 'Discussions', 1, 'buddyboss' ), $data['Discussion'][0] );
		$this->assertSame( _n( 'Discussion', 'Discussions', 0, 'buddyboss' ), $data['Discussion'][1] );
	}

	/**
	 * Nothing translated (an English site) means no locale data is printed.
	 */
	public function test_returns_false_when_nothing_is_translated() {
		$this->assertFalse( bb_admin_script_translations_fallback( null, false, 'bb-admin-settings', 'buddyboss' ) );
	}

	/**
	 * Other domains and handles are left to core; bundled JSON candidates are
	 * skipped; a JSON file the site placed outside the plugin is left to core.
	 */
	public function test_candidate_handling() {
		$bundled = buddypress()->plugin_dir . 'languages/buddyboss-' . $this->locale . '-bb-admin-settings.json';
		$site    = WP_LANG_DIR . '/plugins/buddyboss-' . $this->locale . '-' . md5( 'bp-core/admin/bb-settings/settings/build/index.js' ) . '.json';

		$this->assertNull( bb_admin_script_translations_fallback( null, false, 'bb-admin-settings', 'default' ) );
		$this->assertNull( bb_admin_script_translations_fallback( null, false, 'some-other-handle', 'buddyboss' ) );
		$this->assertNull( bb_admin_script_translations_fallback( null, $bundled, 'some-other-handle', 'buddyboss' ) );
		$this->assertFalse( bb_admin_script_translations_fallback( null, $bundled, 'bb-admin-settings', 'buddyboss' ) );
		$this->assertNull( bb_admin_script_translations_fallback( null, $site, 'bb-admin-settings', 'buddyboss' ) );
		$this->assertSame( 'kept', bb_admin_script_translations_fallback( 'kept', false, 'bb-admin-settings', 'buddyboss' ) );
	}
}
