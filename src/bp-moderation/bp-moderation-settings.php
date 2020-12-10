<?php
/**
 * Moderation Settings
 *
 * @package BuddyBoss\Moderation
 * @since   BuddyBoss 2.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get the Moderation settings sections.
 *
 * @since BuddyBoss 2.0.0
 * @return array
 */
function bp_moderation_get_settings_sections() {

	$settings = array(
		'bp_moderation_settings_blocking'  => array(
			'page'  => 'moderation',
			'title' => __( 'Blocking', 'buddyboss' ),
		),
		'bp_moderation_settings_reporting' => array(
			'page'  => 'moderation',
			'title' => __( 'Reporting', 'buddyboss' ),
		),
	);

	return (array) apply_filters( 'bp_moderation_get_settings_sections', $settings );
}

/**
 * Get all of the settings fields.
 *
 * @since BuddyBoss 2.0.0
 * @return array
 */
function bp_moderation_get_settings_fields() {

	$fields = array();

	$fields['bp_moderation_settings_blocking'] = array(

		'bpm_blocking_member_blocking'        => array(
			'title'             => __( 'Member Blocking', 'buddyboss' ),
			'callback'          => 'bpm_blocking_settings_callback_member_blocking',
			'sanitize_callback' => 'intval',
			'args'              => array(),
		),

		'bpm_blocking_auto_suspend'           => array(
			'title'             => __( 'Auto Suspend', 'buddyboss' ),
			'callback'          => 'bpm_blocking_settings_callback_auto_suspend',
			'sanitize_callback' => 'intval',
			'args'              => array(),
		),

		'bpm_blocking_auto_suspend_threshold' => array(
			'sanitize_callback' => 'intval',
			'args'              => array(),
		),

		'bpm_blocking_email_notification'     => array(
			'title'             => __( 'Email Notification', 'buddyboss' ),
			'callback'          => 'bpm_blocking_settings_callback_email_notification',
			'sanitize_callback' => 'intval',
			'args'              => array(),
		),
	);

	$fields['bp_moderation_settings_reporting'] = array(
		'bpm_reporting_content_reporting'   => array(
			'title'             => __( 'Content Reporting', 'buddyboss' ),
			'callback'          => 'bpm_reporting_settings_callback_content_reporting',
			'sanitize_callback' => '',
			'args'              => array(),
		),

		'bpm_reporting_auto_hide'           => array(
			'title'             => __( 'Auto Hide', 'buddyboss' ),
			'sanitize_callback' => '',
			'args'              => array(),
		),

		'bpm_reporting_auto_hide_threshold' => array(
			'sanitize_callback' => '',
			'args'              => array(),
		),

		'bpm_reporting_email_notification'  => array(
			'title'             => __( 'Email Notification', 'buddyboss' ),
			'callback'          => 'bpm_reporting_settings_callback_email_notification',
			'sanitize_callback' => 'intval',
			'args'              => array(),
		),
	);

	return (array) apply_filters( 'bp_moderation_get_settings_fields', $fields );
}

/**
 * Get settings fields by section.
 *
 * @since BuddyBoss 2.0.0
 *
 * @param string $section_id Section id.
 *
 * @return mixed False if section is invalid, array of fields otherwise.
 */
function bp_moderation_get_settings_fields_for_section( $section_id = '' ) {

	// Bail if section is empty.
	if ( empty( $section_id ) ) {
		return false;
	}

	$fields = bp_moderation_get_settings_fields();
	$retval = isset( $fields[ $section_id ] ) ? $fields[ $section_id ] : false;

	return (array) apply_filters( 'bp_moderation_get_settings_fields_for_section', $retval, $section_id );
}

/**
 * Return Moderation settings API option
 *
 * @since BuddyBoss 2.0.0
 *
 * @param string $option  Option name.
 * @param string $default Default value.
 *
 * @return mixed
 * @uses  get_option()
 * @uses  esc_attr()
 * @uses  apply_filters()
 */
function bp_moderation_get_setting( $option, $default = '' ) {

	// Get the option and sanitize it.
	$value = get_option( $option, $default );

	// Fallback to default.
	if ( empty( $value ) ) {
		$value = $default;
	}

	// Allow plugins to further filter the output.
	return apply_filters( 'bp_moderation_get_setting', $value, $option );
}

/**
 * Output Moderation settings API option
 *
 * @since BuddyBoss 2.0.0
 *
 * @param string $option  Option name.
 * @param string $default Default value.
 */
function bp_moderation_setting( $option, $default = '' ) {
	echo esc_attr( bp_moderation_get_setting( $option, $default ) );
}

/**
 * Moderation blocking Member blocking setting field
 *
 * @since BuddyBoss 2.0.0
 *
 * @uses  checked() To display the checked attribute
 */
function bpm_blocking_settings_callback_member_blocking() {
	?>
	<label for="bpm_blocking_member_blocking">
		<input name="bpm_blocking_member_blocking" id="bpm_blocking_member_blocking" type="checkbox" value="1"
			<?php checked( bp_is_moderation_member_blocking_enable( false ) ); ?> />
		<?php esc_html_e( 'Allow members to block each other.', 'buddyboss' ); ?>
	</label>
	<p class="description"><?php esc_html_e( 'When a member is blocked, their profile and all of their content is hidden from the member who blocked them.', 'buddyboss' ); ?></p>
	<?php
}

/**
 * Checks if Moderation Member blocking feature is enabled.
 *
 * @since BuddyBoss 2.0.0
 *
 * @param int $default bool Optional.Default value true.
 *
 * @return bool Is search autocomplete enabled or not
 * @uses  get_option() To get the bp_search_autocomplete option
 */
function bp_is_moderation_member_blocking_enable( $default = 0 ) {
	return (bool) apply_filters( 'bp_is_moderation_member_blocking_enable', (bool) get_option( 'bpm_blocking_member_blocking', $default ) );
}

/**
 * Moderation blocking auto suspend setting field
 *
 * @since BuddyBoss 2.0.0
 *
 * @uses  checked() To display the checked attribute
 */
function bpm_blocking_settings_callback_auto_suspend() {
	ob_start();
	bpm_blocking_settings_callback_auto_suspend_threshold();
	$threshold = ob_get_clean();
	?>

	<label for="bpm_blocking_auto_suspend">
		<input name="bpm_blocking_auto_suspend" id="bpm_blocking_auto_suspend" type="checkbox" value="1"
				<?php checked( bp_is_moderation_auto_suspend_enable( false ) ); ?> />
		<?php
		// translators: html for threshold fields.
		printf( esc_html__( 'Automatically suspend members after they have been blocked at least %s times.', 'buddyboss' ), $threshold ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		?>
	</label>
	<?php
}

/**
 * Checks if Moderation Member auto suspend feature is enabled.
 *
 * @since BuddyBoss 2.0.0
 *
 * @param int $default bool Optional.Default value true.
 *
 * @return bool Is search autocomplete enabled or not
 * @uses  get_option() To get the bp_search_autocomplete option
 */
function bp_is_moderation_auto_suspend_enable( $default = 0 ) {
	return (bool) apply_filters( 'bp_is_moderation_auto_suspend_enable', (bool) get_option( 'bpm_blocking_auto_suspend', $default ) );
}

/**
 * Moderation blocking auto suspend threshold setting field
 *
 * @since BuddyBoss 2.0.0
 *
 * @uses  checked() To display the checked attribute
 */
function bpm_blocking_settings_callback_auto_suspend_threshold() {
	?>
	<input name="bpm_blocking_auto_suspend_threshold" id="bpm_blocking_auto_suspend_threshold" type="number" min="1" step="1" value="<?php bp_moderation_setting( 'bpm_blocking_auto_suspend_threshold', '5' ); ?>" class="small-text"/>
	<?php
}

/**
 * Moderation blocking auto suspend setting field
 *
 * @since BuddyBoss 2.0.0
 *
 * @uses  checked() To display the checked attribute
 */
function bpm_blocking_settings_callback_email_notification() {
	?>
	<label for="bpm_blocking_email_notification">
		<input name="bpm_blocking_email_notification" id="bpm_blocking_email_notification" type="checkbox" value="1"
			<?php checked( bp_is_moderation_blocking_email_notification_enable( false ) ); ?> />
		<?php esc_html_e( 'Notify administrators when members have been auto-suspended.', 'buddyboss' ); ?>
	</label>
	<?php
}

/**
 * Checks if Moderation blocking email notification feature is enabled.
 *
 * @since BuddyBoss 2.0.0
 *
 * @param int $default bool Optional.Default value true.
 *
 * @return bool Is search autocomplete enabled or not
 * @uses  get_option() To get the bp_search_autocomplete option
 */
function bp_is_moderation_blocking_email_notification_enable( $default = 0 ) {
	return (bool) apply_filters( 'bp_is_moderation_blocking_email_notification_enable', (bool) get_option( 'bpm_blocking_email_notification', $default ) );
}

/***************************
 * Reporting Settings
 ***************************/

/**
 * Moderation blocking Member blocking setting field
 *
 * @since BuddyBoss 2.0.0
 *
 * @uses  checked() To display the checked attribute
 */
function bpm_reporting_settings_callback_content_reporting() {
	$content_types = bp_moderation_content_types();
	?>
	<label
			for="bpm_reporting_content_reporting"><?php esc_html_e( 'Allow the following content types to be reported:', 'buddyboss' ); ?></label>
	<br/><br/>
	<?php
	foreach ( $content_types as $slug => $type ) {
		if ( in_array( $slug, array( BP_Moderation_Members::$moderation_type ), true ) ) {
			continue;
		}
		$is_enabled = bp_is_moderation_content_reporting_enable( false, $slug );
		?>
		<label for="bpm_reporting_content_reporting-<?php echo esc_attr( $slug ); ?>" class="bpm_reporting_content_content_label">
			<input name="bpm_reporting_content_reporting[<?php echo esc_attr( $slug ); ?>]"
			id="bpm_reporting_content_reporting-<?php echo esc_attr( $slug ); ?>" type="checkbox" value="1"
					<?php checked( $is_enabled ); ?> />
			<?php echo esc_html( $type ); ?>
		</label>
		<?php
		ob_start();
		bpm_reporting_settings_callback_auto_hide_threshold( $slug );
		$threshold = ob_get_clean();
		?>
		<label for="bpm_reporting_auto_hide-<?php echo esc_attr( $slug ); ?>" class="<?php echo esc_attr( empty( $is_enabled ) ? 'is_disabled' : '' ); ?>">
			<input name="bpm_reporting_auto_hide[<?php echo esc_attr( $slug ); ?>]" id="bpm_reporting_auto_hide-<?php echo esc_attr( $slug ); ?>" type="checkbox" value="1"
					<?php checked( bp_is_moderation_auto_hide_enable( false, $slug ) ); ?> />
			<?php
			// translators: html for threshold fields.
			printf( esc_html__( 'Automatically hide content after they have been reported at least %s times.', 'buddyboss' ), $threshold ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			?>
		</label>
		<br/>
	<?php } ?>
	<?php
}

/**
 * Checks if Moderation Member reporting feature is enabled.
 *
 * @since BuddyBoss 2.0.0
 *
 * @param int    $default      bool Optional.Default value true.
 * @param string $content_type content type.
 *
 * @return bool Is search autocomplete enabled or not
 * @uses  get_option() To get the bp_search_autocomplete option
 */
function bp_is_moderation_content_reporting_enable( $default = 0, $content_type = '' ) {
	$settings = get_option( 'bpm_reporting_content_reporting', array() );

	if ( ! isset( $settings[ $content_type ] ) || empty( $settings[ $content_type ] ) ) {
		$settings[ $content_type ] = $default;
	}

	return (bool) apply_filters( 'bp_is_moderation_content_reporting_enable', (bool) $settings[ $content_type ], $content_type );
}

/**
 * Checks if Moderation Member auto suspend feature is enabled.
 *
 * @since BuddyBoss 2.0.0
 *
 * @param int    $default bool Optional.Default value true.
 * @param string $content_type content type.
 *
 * @return bool Is search autocomplete enabled or not
 * @uses  get_option() To get the bp_search_autocomplete option
 */
function bp_is_moderation_auto_hide_enable( $default = 0, $content_type = '' ) {
	$is_enabled = bp_is_moderation_content_reporting_enable( false, $content_type );
	if ( ! empty( $is_enabled ) ) {
		$is_enabled = get_option( 'bpm_reporting_auto_hide', $default );
	}
	return (bool) apply_filters( 'bp_is_moderation_auto_hide_enable', (bool) $is_enabled );
}

/**
 * Moderation reporting auto suspend threshold setting field
 *
 * @since BuddyBoss 2.0.0
 *
 * @param string $content_type content type.
 *
 * @uses  checked() To display the checked attribute
 */
function bpm_reporting_settings_callback_auto_hide_threshold( $content_type = '' ) {
	?>
	<input name="bpm_reporting_auto_hide_threshold[<?php echo esc_attr( $content_type ); ?>]" id="bpm_reporting_auto_hide_threshold-<?php echo esc_attr( $content_type ); ?>" type="number" min="1"
	step="1" max="99"
	value="<?php echo esc_attr( bp_moderation_reporting_auto_hide_threshold( '5', $content_type ) ); ?>" class="small-text"/>
	<?php
}

/**
 * Checks if Moderation Member auto suspend feature is enabled.
 *
 * @since BuddyBoss 2.0.0
 *
 * @param int    $default bool Optional.Default value true.
 * @param string $content_type content type.
 *
 * @return bool Is search autocomplete enabled or not
 * @uses  get_option() To get the bp_search_autocomplete option
 */
function bp_moderation_reporting_auto_hide_threshold( $default = 5, $content_type = '' ) {

	$settings = get_option( 'bpm_reporting_auto_hide_threshold', array() );

	if ( ! isset( $settings[ $content_type ] ) || empty( $settings[ $content_type ] ) ) {
		$settings[ $content_type ] = $default;
	}

	return apply_filters( 'bp_moderation_reporting_auto_hide_threshold', (int) $settings[ $content_type ], $content_type );
}

/**
 * Moderation reporting auto suspend setting field
 *
 * @since BuddyBoss 2.0.0
 *
 * @uses  checked() To display the checked attribute
 */
function bpm_reporting_settings_callback_email_notification() {
	?>
	<label for="bpm_reporting_email_notification">
		<input name="bpm_reporting_email_notification" id="bpm_reporting_email_notification" type="checkbox" value="1"
			<?php checked( bp_is_moderation_reporting_email_notification_enable( false ) ); ?> />
		<?php esc_html_e( 'Notify administrators when content has been auto-hidden.', 'buddyboss' ); ?>
	</label>
	<?php
}

/**
 * Checks if Moderation reporting email notification feature is enabled.
 *
 * @since BuddyBoss 2.0.0
 *
 * @param int $default bool Optional.Default value true.
 *
 * @return bool Is search autocomplete enabled or not
 * @uses  get_option() To get the bp_search_autocomplete option
 */
function bp_is_moderation_reporting_email_notification_enable( $default = 0 ) {
	return (bool) apply_filters( 'bp_is_moderation_reporting_email_notification_enable', (bool) get_option( 'bpm_reporting_email_notification', $default ) );
}
