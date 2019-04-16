<?php
/**
 * Media Settings
 *
 * @package BuddyBoss\Media
 * @since BuddyBoss 1.0.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get the Media settings sections.
 *
 * @since BuddyBoss 1.0.0
 * @return array
 */
function bp_media_get_settings_sections() {

	$settings = array(
		'bp_media_settings_general' => array(
			'page'  => 'media',
			'title' => __( 'Media Settings', 'buddyboss' ),
		),
	);

	return (array) apply_filters( 'bp_media_get_settings_sections', $settings );
}

/**
 * Get all of the settings fields.
 *
 * @since BuddyBoss 1.0.0
 * @return array
 */
function bp_media_get_settings_fields() {

	$fields = [];

	/** General Section ******************************************************/
	$fields['bp_media_settings_general'] = [

//		'bp_media_delete_media_permanently' => [
//			'title'             => __( 'Media Management', 'buddyboss' ),
//			'callback'          => 'bp_media_settings_callback_delete_media_permanently',
//			'sanitize_callback' => 'absint',
//			'args'              => []
//		],

	];

	if ( bp_is_active( 'groups' ) ) {

		$fields['bp_media_settings_general']['bp_media_group_media_support'] = [
			'title'             => __( 'Group Media', 'buddyboss' ),
			'callback'          => 'bp_media_settings_callback_group_media_support',
			'sanitize_callback' => 'absint',
			'args'              => []
		];

		$fields['bp_media_settings_general']['bp_media_group_albums'] = [
			'title'             => __( 'Group Albums', 'buddyboss' ),
			'callback'          => 'bp_media_settings_callback_group_albums',
			'sanitize_callback' => 'absint',
			'args'              => []
		];
	}

	if ( bp_is_active( 'forums' ) ) {

		$fields['bp_media_settings_general']['bp_media_forums_media_support'] = [
			'title'             => __( 'Forums Media', 'buddyboss' ),
			'callback'          => 'bp_media_settings_callback_forums_media_support',
			'sanitize_callback' => 'absint',
			'args'              => []
		];
	}

	if ( bp_is_active( 'messages' ) ) {

		$fields['bp_media_settings_general']['bp_media_messages_media_support'] = [
			'title'             => __( 'Messages Media', 'buddyboss' ),
			'callback'          => 'bp_media_settings_callback_messages_media_support',
			'sanitize_callback' => 'absint',
			'args'              => []
		];
	}

	return (array) apply_filters( 'bp_media_get_settings_fields', $fields );
}

/** General Section **************************************************************/

/**
 * Get settings fields by section.
 *
 * @since BuddyBoss 1.0.0
 *
 * @param string $section_id
 *
 * @return mixed False if section is invalid, array of fields otherwise.
 */
function bp_media_get_settings_fields_for_section( $section_id = '' ) {

	// Bail if section is empty
	if ( empty( $section_id ) ) {
		return false;
	}

	$fields = bp_media_get_settings_fields();
	$retval = isset( $fields[ $section_id ] ) ? $fields[ $section_id ] : false;

	return (array) apply_filters( 'bp_media_get_settings_fields_for_section', $retval, $section_id );
}

/**
 * Output settings API option
 *
 * @since BuddyBoss 1.0.0
 *
 * @param string $option
 * @param string $default
 * @param bool $slug
 */
function bp_media_form_option( $option, $default = '', $slug = false ) {
	echo bp_media_get_form_option( $option, $default, $slug );
}

/**
 * Return settings API option
 *
 * @since BuddyBoss 1.0.0
 *
 * @uses get_option()
 * @uses esc_attr()
 * @uses apply_filters()
 *
 * @param string $option
 * @param string $default
 * @param bool $slug
 *
 * @return mixed
 */
function bp_media_get_form_option( $option, $default = '', $slug = false ) {

	// Get the option and sanitize it
	$value = get_option( $option, $default );

	// Slug?
	if ( true === $slug ) {
		$value = esc_attr( apply_filters( 'editable_slug', $value ) );

		// Not a slug
	} else {
		$value = esc_attr( $value );
	}

	// Fallback to default
	if ( empty( $value ) ) {
		$value = $default;
	}

	// Allow plugins to further filter the output
	return apply_filters( 'bp_media_get_form_option', $value, $option );
}

/**
 * Setting > Media > Delete Media Permanently
 *
 * @since BuddyBoss 1.0.0
 */
function bp_media_settings_callback_delete_media_permanently() {
	?>
    <input name="bp_media_delete_media_permanently"
           id="bp_media_delete_media_permanently"
           type="checkbox"
           value="1"
		<?php checked( bp_is_media_delete_enabled() ); ?>
    />
    <label for="bp_media_delete_media_permanently">
		<?php esc_html_e( 'When a photo upload is removed, permanently delete the associated media file', 'buddyboss' ) ?>
    </label>
	<?php
}

/**
 * Checks if media delete is enabled.
 *
 * @since BuddyBoss 1.0.0
 *
 * @param $default integer
 *
 * @return bool Is media delete enabled or not
 */
function bp_is_media_delete_enabled( $default = 0 ) {
	return (bool) apply_filters( 'bp_is_media_delete_enabled', (bool) get_option( 'bp_media_delete_media_permanently', $default ) );
}

/**
 * Setting > Media > Forums support
 *
 * @since BuddyBoss 1.0.0
 */
function bp_media_settings_callback_forums_media_support() {
	?>
    <input name="bp_media_forums_media_support"
           id="bp_media_forums_media_support"
           type="checkbox"
           value="1"
		<?php checked( bp_is_forums_media_support_enabled() ); ?>
    />
    <label for="bp_media_forums_media_support">
		<?php esc_html_e( 'Allow photo posting in forum discussions', 'buddyboss' ) ?>
    </label>
	<?php
}

/**
 * Checks if media forums media support is enabled.
 *
 * @since BuddyBoss 1.0.0
 *
 * @param $default integer
 *
 * @return bool Is media forums media support enabled or not
 */
function bp_is_forums_media_support_enabled( $default = 1 ) {
	return (bool) apply_filters( 'bp_is_forums_media_support_enabled', (bool) get_option( 'bp_media_forums_media_support', $default ) );
}

/**
 * Setting > Media > Groups support
 *
 * @since BuddyBoss 1.0.0
 */
function bp_media_settings_callback_group_media_support() {
	?>
    <input name="bp_media_group_media_support"
           id="bp_media_group_media_support"
           type="checkbox"
           value="1"
		<?php checked( bp_is_group_media_support_enabled() ); ?>
    />
    <label for="bp_media_group_media_support">
		<?php esc_html_e( 'Allow photo posting in social group activity updates and comments', 'buddyboss' ) ?>
    </label>
	<?php
}

/**
 * Checks if media group media support is enabled.
 *
 * @since BuddyBoss 1.0.0
 *
 * @param $default integer
 *
 * @return bool Is media group media support enabled or not
 */
function bp_is_group_media_support_enabled( $default = 1 ) {
	return (bool) apply_filters( 'bp_is_group_media_support_enabled', (bool) get_option( 'bp_media_group_media_support', $default ) );
}


/**
 * Setting > Media > Group Albums support
 *
 * @since BuddyBoss 1.0.0
 */
function bp_media_settings_callback_group_albums() {
	?>
    <input name="bp_media_group_albums"
           id="bp_media_group_albums"
           type="checkbox"
           value="1"
		<?php checked( bp_is_group_album_support_enabled() ); ?>
    />
    <label for="bp_media_group_albums">
		<?php esc_html_e( 'Enable social group photo albums', 'buddyboss' ) ?>
    </label>
	<?php
}

/**
 * Checks if media group album support is enabled.
 *
 * @since BuddyBoss 1.0.0
 *
 * @param $default integer
 *
 * @return bool Is media group album support enabled or not
 */
function bp_is_group_album_support_enabled( $default = 1 ) {
	return (bool) apply_filters( 'bp_is_group_album_support_enabled', (bool) get_option( 'bp_media_group_albums', $default ) );
}

/**
 * Setting > Media > Messages support
 *
 * @since BuddyBoss 1.0.0
 */
function bp_media_settings_callback_messages_media_support() {
	?>
    <input name="bp_media_messages_media_support"
           id="bp_media_messages_media_support"
           type="checkbox"
           value="1"
		<?php checked( bp_is_messages_media_support_enabled() ); ?>
    />
    <label for="bp_media_messages_media_support">
		<?php esc_html_e( 'Allow photo posting in messages', 'buddyboss' ) ?>
    </label>
	<?php
}

/**
 * Checks if media messages media support is enabled.
 *
 * @since BuddyBoss 1.0.0
 *
 * @param $default integer
 *
 * @return bool Is media messages media support enabled or not
 */
function bp_is_messages_media_support_enabled( $default = 1 ) {
	return (bool) apply_filters( 'bp_is_messages_media_support_enabled', (bool) get_option( 'bp_media_messages_media_support', $default ) );
}