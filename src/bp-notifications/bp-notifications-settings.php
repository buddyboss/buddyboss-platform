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
			'title'             => __( 'Automatic Notifications', 'buddyboss' ),
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

	$fields['bp_notification_settings_automatic'] = array(
		'bp_media_gif_api_key' => array(
			'title'             => __( 'GIPHY API Key', 'buddyboss' ),
			'callback'          => 'bb_notification_settings_callback_gif_key',
			'sanitize_callback' => 'string',
			'args'              => array(),
		),
	);

	return (array) apply_filters( 'bb_notification_get_settings_fields', $fields );
}

/**
 * Setting > Notifications > Automatic Notifications
 *
 * @since BuddyBoss [BBVERSION]
 */
function bb_notification_settings_callback_gif_key() {
	?>
	<input type="text" name="bp_media_gif_api_key" id="bp_media_gif_api_key" value="<?php echo bp_media_get_gif_api_key(); ?>" placeholder="<?php _e( 'GIPHY API Key', 'buddyboss' ); ?>" style="width: 300px;" />
	<p class="description">
		<?php
		printf(
			'%1$s <a href="%2$s" target="_blank">GIPHY</a>. %3$s <a href="%4$s" target="_blank">Create an App</a>. %5$s',
			__( 'This feature requires an account at', 'buddyboss' ),
			esc_url( 'https://developers.giphy.com/' ),
			__( 'Create your account, and then click', 'buddyboss' ),
			esc_url( 'https://developers.giphy.com/dashboard/?create=true' ),
			__( 'Once done, copy the API key and paste it in the field above.', 'buddyboss' )
		);
		?>
	</p>
	<?php
}
