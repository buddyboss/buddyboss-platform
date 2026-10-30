<?php

/**
 * @group settings
 */
class BP_Tests_Settings_BpSettingsSanitizeNotificationSettings extends BP_UnitTestCase {
	protected $core_notification_settings = array(
		'notification_messages_new_message',
		'notification_activity_new_mention',
		'notification_activity_new_reply',
		'notification_groups_invite',
		'notification_groups_group_updated',
		'notification_groups_admin_promotion',
		'notification_groups_membership_request',
		'notification_friends_friendship_request',
		'notification_friends_friendship_accepted',
	);

	public function test_should_ignore_setting_not_added_via_bp_notification_settings_callback() {
		$settings = array(
			'foo' => 'yes',
		);

		$sanitized = bp_settings_sanitize_notification_settings( $settings );

		$this->assertArrayNotHasKey( 'foo', $sanitized );
	}

	public function test_should_accept_setting_added_via_bp_notification_settings_callback() {
		$settings = array(
			'foo' => 'yes',
		);

		add_action( 'bp_notification_settings', array( $this, 'add_custom_notification_setting' ) );
		$sanitized = bp_settings_sanitize_notification_settings( $settings );
		remove_action( 'bp_notification_settings', array( $this, 'add_custom_notification_setting' ) );

		$this->assertArrayHasKey( 'foo', $sanitized );
		$this->assertSame( 'yes', $sanitized['foo'] );
	}

	public function test_should_sanitize_invalid_values_to_no_for_core_settings() {

		$settings = array();
		foreach ( $this->core_notification_settings as $key ) {
			$settings[ $key ] = 'foo';
		}

		add_action( 'bp_notification_settings', array( $this, 'add_core_notification_settings' ) );
		$sanitized = bp_settings_sanitize_notification_settings( $settings );
		remove_action( 'bp_notification_settings', array( $this, 'add_core_notification_settings' ) );

		$expected = array();
		foreach ( $this->core_notification_settings as $key ) {
			$expected[ $key ] = 'no';
		}

		$this->assertEqualSets( $expected, $sanitized );
	}

	/**
	 * @dataProvider data_invalid_values
	 */
	public function test_should_sanitize_invalid_values_to_no_for_custom_setting( $value ) {
		$settings = array(
			'foo' => $value,
		);

		add_action( 'bp_notification_settings', array( $this, 'add_custom_notification_setting' ) );
		$sanitized = bp_settings_sanitize_notification_settings( $settings );
		remove_action( 'bp_notification_settings', array( $this, 'add_custom_notification_setting' ) );

		$this->assertArrayHasKey( 'foo', $sanitized );
		$this->assertSame( 'no', $sanitized['foo'] );
	}

	public function data_invalid_values() {
		return array(
			'text'        => array( 'HELLO I AM NOT YES OR NO' ),
			'long string' => array( str_repeat( 'a', 1000000 ) ),
			'array'       => array( array( 'x' ) ),
			'uppercase'   => array( 'YES' ),
			'boolean'     => array( true ),
			'empty'       => array( '' ),
		);
	}

	public function test_should_keep_no_for_custom_setting() {
		$settings = array(
			'foo' => 'no',
		);

		add_action( 'bp_notification_settings', array( $this, 'add_custom_notification_setting' ) );
		$sanitized = bp_settings_sanitize_notification_settings( $settings );
		remove_action( 'bp_notification_settings', array( $this, 'add_custom_notification_setting' ) );

		$this->assertSame( 'no', $sanitized['foo'] );
	}

	public function test_should_save_non_boolean_setting_as_plain_text() {
		$settings = array(
			'foo' => '<b>Weekly</b> digest',
		);

		add_action( 'bp_notification_settings', array( $this, 'add_custom_notification_setting' ) );
		add_filter( 'bb_settings_non_boolean_notification_keys', array( $this, 'add_non_boolean_custom_setting' ) );
		$sanitized = bp_settings_sanitize_notification_settings( $settings );
		remove_filter( 'bb_settings_non_boolean_notification_keys', array( $this, 'add_non_boolean_custom_setting' ) );
		remove_action( 'bp_notification_settings', array( $this, 'add_custom_notification_setting' ) );

		$this->assertSame( 'Weekly digest', $sanitized['foo'] );
	}

	public function test_should_limit_non_boolean_setting_to_255_characters() {
		$settings = array(
			'foo' => str_repeat( 'a', 1000000 ),
		);

		add_action( 'bp_notification_settings', array( $this, 'add_custom_notification_setting' ) );
		add_filter( 'bb_settings_non_boolean_notification_keys', array( $this, 'add_non_boolean_custom_setting' ) );
		$sanitized = bp_settings_sanitize_notification_settings( $settings );
		remove_filter( 'bb_settings_non_boolean_notification_keys', array( $this, 'add_non_boolean_custom_setting' ) );
		remove_action( 'bp_notification_settings', array( $this, 'add_custom_notification_setting' ) );

		$this->assertSame( str_repeat( 'a', 255 ), $sanitized['foo'] );
	}

	public function test_should_save_empty_string_for_non_scalar_non_boolean_setting() {
		$settings = array(
			'foo' => array( 'x' ),
		);

		add_action( 'bp_notification_settings', array( $this, 'add_custom_notification_setting' ) );
		add_filter( 'bb_settings_non_boolean_notification_keys', array( $this, 'add_non_boolean_custom_setting' ) );
		$sanitized = bp_settings_sanitize_notification_settings( $settings );
		remove_filter( 'bb_settings_non_boolean_notification_keys', array( $this, 'add_non_boolean_custom_setting' ) );
		remove_action( 'bp_notification_settings', array( $this, 'add_custom_notification_setting' ) );

		$this->assertSame( '', $sanitized['foo'] );
	}

	public function test_should_not_allow_core_setting_to_opt_out_of_yes_no() {
		$settings = array(
			'notification_messages_new_message' => 'foo',
		);

		add_action( 'bp_notification_settings', array( $this, 'add_core_notification_settings' ) );
		add_filter( 'bb_settings_non_boolean_notification_keys', array( $this, 'add_non_boolean_core_setting' ) );
		$sanitized = bp_settings_sanitize_notification_settings( $settings );
		remove_filter( 'bb_settings_non_boolean_notification_keys', array( $this, 'add_non_boolean_core_setting' ) );
		remove_action( 'bp_notification_settings', array( $this, 'add_core_notification_settings' ) );

		$this->assertSame( 'no', $sanitized['notification_messages_new_message'] );
	}

	public function add_non_boolean_custom_setting( $keys ) {
		$keys[] = 'foo';
		return $keys;
	}

	public function add_non_boolean_core_setting( $keys ) {
		$keys[] = 'notification_messages_new_message';
		return $keys;
	}

	public function add_custom_notification_setting() {
		echo '<input name="notifications[foo]" value="yes" />';
	}

	public function add_core_notification_settings() {
		foreach ( $this->core_notification_settings as $key ) {
			echo '<input name="notifications[' . $key . ']" value="yes" />';
		}
	}
}
