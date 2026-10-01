<?php
/**
 * Tests for the Members/Groups directory loading setting (Pagination vs Infinite Scroll).
 *
 * @group core
 * @group PROD-9724
 */
class BB_Tests_Core_Functions_BbDirectoryLoadType extends BP_UnitTestCase {

	/**
	 * Feature registry state captured before each test, restored afterwards.
	 *
	 * The registry is a process-wide singleton that suffixes duplicate feature,
	 * side panel and section IDs instead of overwriting them, so every test that
	 * registers the Advanced feature must start from, and return to, this state.
	 *
	 * @var array
	 */
	protected $registry_snapshot = array();

	public function set_up() {
		parent::set_up();

		// The sanitizer and the Settings 2.0 registration are admin-only files.
		if ( ! function_exists( 'bb_advanced_sanitize_directory_load_type' ) ) {
			require_once buddypress()->plugin_dir . 'bp-core/admin/settings/advanced/callbacks.php';
		}
		if ( ! function_exists( 'bb_admin_settings_register_advanced_feature' ) ) {
			require_once buddypress()->plugin_dir . 'bp-core/admin/bb-admin-settings-advanced.php';
		}

		$this->registry_snapshot = $this->registry_state();

		bp_delete_option( 'bb_directory_load_type' );
	}

	public function tear_down() {
		$this->registry_state( $this->registry_snapshot );

		bp_delete_option( 'bb_directory_load_type' );

		parent::tear_down();
	}

	/**
	 * Read, or restore, every instance property of the feature registry.
	 *
	 * @param array|null $state State to restore. Null to only read.
	 *
	 * @return array
	 */
	protected function registry_state( $state = null ) {
		$registry = bb_feature_registry();
		$values   = array();

		foreach ( ( new ReflectionObject( $registry ) )->getProperties() as $property ) {
			if ( $property->isStatic() ) {
				continue;
			}

			$property->setAccessible( true );

			if ( null !== $state && array_key_exists( $property->getName(), $state ) ) {
				$property->setValue( $registry, $state[ $property->getName() ] );
			}

			$values[ $property->getName() ] = $property->getValue( $registry );
		}

		return $values;
	}

	/**
	 * Register the Advanced feature and return its Page Loading fields.
	 *
	 * @return array
	 */
	protected function page_loading_fields() {
		bb_admin_settings_register_advanced_feature();

		return bb_feature_registry()->bb_get_fields( 'advanced', 'general', 'advanced_page_loading' );
	}

	public function test_default_option_is_pagination() {
		$defaults = bp_get_default_options();

		$this->assertSame( 'pagination', $defaults['bb_directory_load_type'] );
	}

	public function test_unsaved_setting_uses_pagination() {
		$this->assertSame( 'pagination', bb_get_directory_load_type() );
		$this->assertFalse( bb_is_directory_autoload_active() );
	}

	public function test_saved_infinite_enables_autoload() {
		bp_update_option( 'bb_directory_load_type', 'infinite' );

		$this->assertSame( 'infinite', bb_get_directory_load_type() );
		$this->assertTrue( bb_is_directory_autoload_active() );
	}

	public function test_saved_pagination_disables_autoload() {
		bp_update_option( 'bb_directory_load_type', 'pagination' );

		$this->assertSame( 'pagination', bb_get_directory_load_type() );
		$this->assertFalse( bb_is_directory_autoload_active() );
	}

	public function test_invalid_stored_value_falls_back_to_pagination() {
		// 'load_more' is valid for the activity feed setting, not for directories.
		bp_update_option( 'bb_directory_load_type', 'load_more' );

		$this->assertSame( 'pagination', bb_get_directory_load_type() );
		$this->assertFalse( bb_is_directory_autoload_active() );
	}

	public function test_load_type_filter_is_applied() {
		$filter = function () {
			return 'infinite';
		};
		add_filter( 'bb_get_directory_load_type', $filter );

		$type   = bb_get_directory_load_type();
		$active = bb_is_directory_autoload_active();

		remove_filter( 'bb_get_directory_load_type', $filter );

		$this->assertSame( 'infinite', $type );
		$this->assertTrue( $active, 'bb_is_directory_autoload_active() must honour the load type filter' );
	}

	public function test_autoload_filter_can_turn_infinite_scroll_off() {
		bp_update_option( 'bb_directory_load_type', 'infinite' );
		add_filter( 'bb_is_directory_autoload_active', '__return_false' );

		$active = bb_is_directory_autoload_active();

		remove_filter( 'bb_is_directory_autoload_active', '__return_false' );

		$this->assertFalse( $active );
	}

	public function test_sanitizer_keeps_supported_values() {
		$this->assertSame( 'infinite', bb_advanced_sanitize_directory_load_type( 'infinite' ) );
		$this->assertSame( 'pagination', bb_advanced_sanitize_directory_load_type( 'pagination' ) );
		$this->assertSame( 'infinite', bb_advanced_sanitize_directory_load_type( ' infinite ' ) );
	}

	public function test_sanitizer_rejects_unsupported_values() {
		$this->assertSame( 'pagination', bb_advanced_sanitize_directory_load_type( 'load_more' ) );
		$this->assertSame( 'pagination', bb_advanced_sanitize_directory_load_type( '<script>infinite</script>' ) );
		$this->assertSame( 'pagination', bb_advanced_sanitize_directory_load_type( '' ) );
		$this->assertSame( 'pagination', bb_advanced_sanitize_directory_load_type( array( 'infinite' ) ) );
	}

	public function test_sanitizer_accepts_options_added_by_filter() {
		$filter = function ( $options ) {
			$options['custom'] = 'Custom';
			return $options;
		};
		add_filter( 'bb_performance_directory_autoload', $filter );

		$value = bb_advanced_sanitize_directory_load_type( 'custom' );

		remove_filter( 'bb_performance_directory_autoload', $filter );

		$this->assertSame( 'custom', $value );
	}

	public function test_page_loading_section_registers_directory_field() {
		$fields   = $this->page_loading_fields();
		$sections = bb_feature_registry()->bb_get_sections( 'advanced', 'general' );

		$this->assertArrayHasKey( 'advanced_page_loading', $sections );
		$this->assertArrayHasKey( 'bb_directory_load_type', $fields );
		$this->assertArrayHasKey( 'bb_load_activity_per_request', $fields );

		$field = $fields['bb_directory_load_type'];
		$this->assertSame( 'pagination', $field['default'] );
		$this->assertSame( 'bb_advanced_sanitize_directory_load_type', $field['sanitize_callback'] );

		$control = $field['description_controls'][0];
		$this->assertSame( 'bb_directory_load_type', $control['name'] );
		$this->assertSame( 'bb_advanced_sanitize_directory_load_type', $control['sanitize_callback'] );
		$this->assertSame( array( 'pagination', 'infinite' ), wp_list_pluck( $control['options'], 'value' ) );
	}

	public function test_directory_field_default_reflects_saved_value() {
		bp_update_option( 'bb_directory_load_type', 'infinite' );

		$fields = $this->page_loading_fields();

		$this->assertSame( 'infinite', $fields['bb_directory_load_type']['default'] );
		$this->assertSame( 'infinite', $fields['bb_directory_load_type']['description_controls'][0]['default'] );
	}

	public function test_directory_field_is_registered_when_activity_is_inactive() {
		$filter = function ( $retval, $component ) {
			return 'activity' === $component ? false : $retval;
		};
		add_filter( 'bp_is_active', $filter, 10, 2 );

		$section_fields = $this->page_loading_fields();

		remove_filter( 'bp_is_active', $filter, 10 );

		$this->assertArrayHasKey( 'advanced_page_loading', bb_feature_registry()->bb_get_sections( 'advanced', 'general' ) );
		$this->assertArrayHasKey( 'bb_directory_load_type', $section_fields );
		$this->assertArrayNotHasKey( 'bb_load_activity_per_request', $section_fields, 'The feed row is gated on the Activity component' );
	}
}
