<?php
/**
 * Tests for reading and saving the `bb_enabled_notification` option when a row has no 'main' key.
 *
 * A Settings 2.0 save that only changes an email/web/app sub-type stored rows such as
 * array( 'bb_new_mention' => array( 'email' => 'no' ) ). The readers combined
 * array_keys() with array_column( ..., 'main' ), which throws a ValueError on PHP 8
 * when the counts differ.
 *
 * @group core
 * @group notifications
 */
class BP_Tests_Core_NotificationEnabledMainSettings extends BP_UnitTestCase {

	/**
	 * Load the Settings 2.0 notification sanitizer, which is only included on admin requests.
	 */
	public function set_up() {
		parent::set_up();

		if ( ! function_exists( 'bb_notifications_sanitize_types' ) ) {
			require_once buddypress()->plugin_dir . 'bp-core/admin/settings/notifications/callbacks.php';
		}
	}

	/**
	 * Remove the option written by a test.
	 */
	public function tear_down() {
		bp_delete_option( 'bb_enabled_notification' );

		parent::tear_down();
	}

	/**
	 * First registered, non read-only notification field of a group with a default of 'yes'.
	 *
	 * @return array Array of group key and field, or an empty array when none is registered.
	 */
	protected function get_default_on_field() {
		foreach ( bb_register_notification_preferences() as $group => $group_data ) {
			if ( empty( $group_data['fields'] ) ) {
				continue;
			}

			foreach ( $group_data['fields'] as $field ) {
				if (
					! empty( $field['key'] ) &&
					isset( $field['default'] ) &&
					'yes' === $field['default'] &&
					empty( $field['notification_read_only'] )
				) {
					return array( $group, $field );
				}
			}
		}

		return array();
	}

	/**
	 * Well-formed rows map to the same values the old array_combine() code produced.
	 */
	public function test_main_settings_well_formed_rows() {
		$option = array(
			'bb_new_mention'      => array(
				'main'  => 'no',
				'email' => 'yes',
			),
			'bb_activity_comment' => array( 'main' => 'yes' ),
		);

		$this->assertSame(
			array_filter( array_combine( array_keys( $option ), array_column( $option, 'main' ) ) ),
			bb_get_enabled_notification_main_settings( $option )
		);
	}

	/**
	 * Rows without 'main', empty rows and non-array rows are skipped.
	 */
	public function test_main_settings_skip_rows_without_main() {
		$option = array(
			'bb_new_mention'      => array( 'email' => 'no' ),
			'bb_activity_comment' => array( 'main' => 'no' ),
			'bb_groups_details'   => array(),
			'bb_broken'           => 'no',
		);

		$this->assertSame(
			array( 'bb_activity_comment' => 'no' ),
			bb_get_enabled_notification_main_settings( $option )
		);
	}

	/**
	 * A non-array option value returns an empty map.
	 */
	public function test_main_settings_non_array_option() {
		$this->assertSame( array(), bb_get_enabled_notification_main_settings( '' ) );
		$this->assertSame( array(), bb_get_enabled_notification_main_settings( false ) );
	}

	/**
	 * The admin-settings check falls back to the default instead of throwing.
	 */
	public function test_admin_settings_is_enabled_with_row_without_main() {
		$found = $this->get_default_on_field();
		if ( empty( $found ) ) {
			$this->markTestSkipped( 'No default-on notification preference is registered.' );
		}

		list( $group, $field ) = $found;

		bp_update_option(
			'bb_enabled_notification',
			array(
				$field['key']   => array( 'email' => 'no' ),
				'bb_other_type' => array( 'main' => 'yes' ),
			)
		);

		$this->assertTrue( bb_get_modern_notification_admin_settings_is_enabled( $field['key'], $group ) );
	}

	/**
	 * The member notification preferences render the default-on type instead of throwing.
	 */
	public function test_render_notification_with_row_without_main() {
		$found = $this->get_default_on_field();
		if ( empty( $found ) ) {
			$this->markTestSkipped( 'No default-on notification preference is registered.' );
		}

		list( $group, $field ) = $found;

		bp_update_option(
			'bb_enabled_notification',
			array(
				$field['key']   => array( 'email' => 'no' ),
				'bb_other_type' => array( 'main' => 'yes' ),
			)
		);

		ob_start();
		bb_render_notification( $group );
		$output = ob_get_clean();

		$this->assertStringContainsString( 'main-notification-settings', $output );
		$this->assertStringContainsString( 'notifications[' . $field['key'] . ']', $output );
	}

	/**
	 * The member notification preferences hide a type whose 'main' is 'no'.
	 */
	public function test_render_notification_hides_main_no() {
		$found = $this->get_default_on_field();
		if ( empty( $found ) ) {
			$this->markTestSkipped( 'No default-on notification preference is registered.' );
		}

		list( $group, $field ) = $found;

		bp_update_option( 'bb_enabled_notification', array( $field['key'] => array( 'main' => 'no' ) ) );

		ob_start();
		bb_render_notification( $group );
		$output = ob_get_clean();

		$this->assertStringNotContainsString( 'notifications[' . $field['key'] . ']', $output );
	}

	/**
	 * An explicit 'main' => 'no' still hides the type.
	 */
	public function test_admin_settings_is_enabled_respects_main_no() {
		$found = $this->get_default_on_field();
		if ( empty( $found ) ) {
			$this->markTestSkipped( 'No default-on notification preference is registered.' );
		}

		list( $group, $field ) = $found;

		bp_update_option( 'bb_enabled_notification', array( $field['key'] => array( 'main' => 'no' ) ) );

		$this->assertFalse( bb_get_modern_notification_admin_settings_is_enabled( $field['key'], $group ) );
	}

	/**
	 * The Settings 2.0 sanitizer stores the registered default as 'main' for a sub-type-only row.
	 */
	public function test_sanitize_types_backfills_main_from_default() {
		$found = $this->get_default_on_field();
		if ( empty( $found ) ) {
			$this->markTestSkipped( 'No default-on notification preference is registered.' );
		}

		$field     = $found[1];
		$sanitized = bb_notifications_sanitize_types( array( $field['key'] => array( 'email' => 'no' ) ) );

		$this->assertSame(
			array(
				'email' => 'no',
				'main'  => 'yes',
			),
			$sanitized[ $field['key'] ]
		);
	}

	/**
	 * The sanitizer keeps a submitted 'main' value.
	 */
	public function test_sanitize_types_keeps_submitted_main() {
		$found = $this->get_default_on_field();
		if ( empty( $found ) ) {
			$this->markTestSkipped( 'No default-on notification preference is registered.' );
		}

		$field     = $found[1];
		$sanitized = bb_notifications_sanitize_types(
			array(
				$field['key'] => array(
					'main'  => 'no',
					'email' => 'yes',
				),
			)
		);

		$this->assertSame( 'no', $sanitized[ $field['key'] ]['main'] );
	}
}
