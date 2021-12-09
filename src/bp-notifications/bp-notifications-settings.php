<?php
/**
 * Notifications Settings
 *
 * @package BuddyBoss\Notifications
 * @since BuddyBoss [BBVERSION]
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get the Notification settings sections.
 *
 * @return array
 * @since BuddyBoss [BBVERSION]
 */
function bb_notification_get_settings_sections() {

	$settings = array(
		'bp_notifications'                   => array(
			'page'              => 'notifications',
			'title'             => __( 'On-screen Notifications', 'buddyboss' ),
			'tutorial_callback' => 'bp_admin_on_screen_notification_setting_tutorial',
		),
		'bp_notification_settings_automatic' => array(
			'page'              => 'notifications',
			'title'             => __( 'Notification Types', 'buddyboss' ),
			'tutorial_callback' => 'bb_automatic_notifications_tutorial',
		),
	);

	return (array) apply_filters( 'bb_notification_get_settings_sections', $settings );
}

/**
 * Link to Automatic Notification tutorial
 *
 * @since BuddyBoss [BBVERSION]
 */
function bb_automatic_notifications_tutorial() {
	?>

	<p>
		<a class="button" href="
		<?php
		echo esc_url(
			bp_get_admin_url(
				add_query_arg(
					array(
						'page'    => 'bp-help',
						'article' => 62829,
					),
					'admin.php'
				)
			)
		);
		?>
		"><?php esc_html_e( 'View Tutorial', 'buddyboss' ); ?></a>
	</p>

	<?php
}

/**
 * Get settings fields by section.
 *
 * @param string $section_id
 *
 * @return mixed False if section is invalid, array of fields otherwise.
 * @since BuddyBoss [BBVERSION]
 */
function bb_notification_get_settings_fields_for_section( $section_id = '' ) {

	// Bail if section is empty.
	if ( empty( $section_id ) ) {
		return false;
	}

	$fields = bb_notification_get_settings_fields();
	$retval = isset( $fields[ $section_id ] ) ? $fields[ $section_id ] : false;

	return (array) apply_filters( 'bb_notification_get_settings_fields_for_section', $retval, $section_id );
}

/**
 * Get all the settings fields.
 *
 * @return array
 * @since BuddyBoss [BBVERSION]
 */
function bb_notification_get_settings_fields() {

	$fields = array();

	$fields['bp_notifications'] = array(
		'_bp_on_screen_notifications_enable'        => array(
			'title'             => __( 'On-screen notifications', 'buddyboss' ),
			'callback'          => 'bb_admin_setting_callback_on_screen_notifications_enable',
			'sanitize_callback' => 'intval',
			'args'              => array(),
		),
		'_bp_on_screen_notification_position'       => array(
			'title'             => __( 'Position on Screen', 'buddyboss' ),
			'callback'          => 'bb_admin_setting_callback_on_screen_notifications_position',
			'sanitize_callback' => 'intval',
			'args'              => array(),
		),
		'_bp_on_screen_notification_mobile_support' => array(
			'title'             => __( 'Mobile Support', 'buddyboss' ),
			'callback'          => 'bb_admin_setting_callback_on_screen_notifications_mobile_support',
			'sanitize_callback' => 'intval',
			'args'              => array(),
		),
		'_bp_on_screen_notification_visibility'     => array(
			'title'             => __( 'Automatically Hide', 'buddyboss' ),
			'callback'          => 'bb_admin_setting_callback_on_screen_notifications_visibility',
			'sanitize_callback' => 'intval',
			'args'              => array(),
		),
		'_bp_on_screen_notification_browser_tab'    => array(
			'title'             => __( 'Show in Browser Tab', 'buddyboss' ),
			'callback'          => 'bb_admin_setting_callback_on_screen_notifications_browser_tab',
			'sanitize_callback' => 'intval',
			'args'              => array(),
		),
	);

	$all_notifications = bb_register_notification_preferences();

	$fields['bp_notification_settings_automatic'] = array();

	$fields['bp_notification_settings_automatic']['infos'] = array(
		'title'             => __( 'Notes', 'buddyboss' ),
		'callback'          => 'bb_admin_setting_callback_on_automatic_notification_information',
		'sanitize_callback' => 'string',
		'args'              => array( 'class' => 'notes-hidden-header' ),
	);

	if ( ! empty( $all_notifications ) ) {

		foreach ( $all_notifications as $key => $data ) {
			$c = 1;
			if ( ! empty( $data['fields'] ) ) {
				foreach ( $data['fields'] as $field ) {
					$field['class'] = ( 1 === $c ? 'child-no-padding-first' : 'child-no-padding' );

					$fields['bp_notification_settings_automatic'][ $field['key'] ] = array(
						'title'             => ( 1 === $c ? $data['label'] . ' ' . esc_html_x( 'Notifications', 'Notification label for an admin', 'buddyboss' ) : ' ' ),
						'callback'          => 'bb_activate_notification',
						'sanitize_callback' => 'string',
						'args'              => $field,
					);

					$c ++;
				}
			}
		}
	}

	return (array) apply_filters( 'bb_notification_get_settings_fields', $fields );
}

/**
 * Callback fields for the notification fields options.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param array $field Fieldset data.
 */
function bb_activate_notification( $field ) {
	$enabled_notification = bp_get_option( 'bb_enabled_notification', array() );
	$checked              = in_array( esc_attr( $field['key'] ), $enabled_notification, true );
	$label                = ( ! empty( $field['admin_label'] ) ? $field['admin_label'] : $field['label'] );
	?>

	<input id="bb_enabled_notification_<?php echo esc_attr( $field['key'] ); ?>" name="bb_enabled_notification[]" type="checkbox" value="<?php echo esc_attr( $field['key'] ); ?>" <?php checked( $checked, 1 ); ?> />
	<label class="notification-label" for="bb_enabled_notification_<?php echo esc_attr( $field['key'] ); ?>"><?php echo esc_html( $label ); ?></label>

	<?php
}

/**
 * Added instructions for the notification type.
 *
 * @since BuddyBoss [BBVERSION]
 */
function bb_admin_setting_callback_on_automatic_notification_information() {
	echo '<p class="description">' .
		esc_html__( 'Select which types of notifications are sent to members when specific actions happen on your site. When a notification is disabled, it will not be generated for any member. Members can configure which notifications they receive via email, web or app in their Notification Preferences.', 'buddyboss' ) .
	'</p>';
}

