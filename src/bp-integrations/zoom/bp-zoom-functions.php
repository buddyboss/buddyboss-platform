<?php
/**
 * Zoom integration helpers
 *
 * @package BuddyBoss\Zoom
 * @since BuddyBoss 1.2.10
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Callback function for api key in zoom integration.
 *
 * @since BuddyBoss 1.2.10
 */
function bp_zoom_settings_callback_api_key_field() {
	?>
	<input name="bp-zoom-api-key"
	       id="bp-zoom-api-key"
	       type="text"
	       value="<?php echo esc_html( bp_zoom_api_key() ); ?>"
	       placeholder="<?php _e( 'Zoom API Key', 'buddyboss' ); ?>"
	       aria-label="<?php _e( 'Zoom API Key', 'buddyboss' ); ?>"
	/>
	<?php
}

/**
 * Get Zoom API Key
 *
 * @since BuddyBoss 1.2.10
 * @param string $default
 *
 * @return mixed|void Zoom API Key
 */
function bp_zoom_api_key( $default = '' ) {
	return apply_filters( 'bp_zoom_api_key', bp_get_option( 'bp-zoom-api-key', $default ) );
}

/**
 * Callback function for api secret in zoom integration.
 *
 * @since BuddyBoss 1.2.10
 */
function bp_zoom_settings_callback_api_secret_field() {
	?>
	<input name="bp-zoom-api-secret"
	       id="bp-zoom-api-secret"
	       type="text"
	       value="<?php echo esc_html( bp_zoom_api_secret() ); ?>"
	       placeholder="<?php _e( 'Zoom API Secret', 'buddyboss' ); ?>"
	       aria-label="<?php _e( 'Zoom API Secret', 'buddyboss' ); ?>"
	/>
	<?php
}

/**
 * Get Zoom API Secret
 *
 * @since BuddyBoss 1.2.10
 * @param string $default
 *
 * @return mixed|void Zoom API Key
 */
function bp_zoom_api_secret( $default = '' ) {
	return apply_filters( 'bp_zoom_api_secret', bp_get_option( 'bp-zoom-api-secret', $default ) );
}
