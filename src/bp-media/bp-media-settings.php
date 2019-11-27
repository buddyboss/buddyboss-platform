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
		'bp_media_settings_photos' => array(
			'page'  => 'media',
			'title' => __( 'Photo Uploading', 'buddyboss' ),
		),
		'bp_media_settings_documents' => array(
			'page'  => 'doc',
			'title' => __( 'Document Uploading', 'buddyboss' ),
		),
		'bp_media_settings_emoji'  => array(
			'page'  => 'media',
			'title' => __( 'Emoji', 'buddyboss' ),
		),
		'bp_media_settings_gifs'   => array(
			'page'  => 'media',
			'title' => __( 'Animated GIFs', 'buddyboss' ),
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

	$fields = array();

	/** Photos Section */
	$fields['bp_media_settings_photos'] = array(

		'bp_media_profile_media_support'  => array(
			'title'             => __( 'Profiles', 'buddyboss' ),
			'callback'          => 'bp_media_settings_callback_profile_media_support',
			'sanitize_callback' => 'absint',
			'args'              => array(),
		),

		'bp_media_profile_albums_support' => array(
			'title'             => __( 'Profile Albums', 'buddyboss' ),
			'callback'          => '__return_true',
			'sanitize_callback' => 'absint',
			'args'              => array(
				'class' => 'hidden',
			),
		),
	);

	$fields['bp_media_settings_photos'] = array(

		'bp_media_profile_media_support'  => array(
			'title'             => __( 'Profiles', 'buddyboss' ),
			'callback'          => 'bp_media_settings_callback_profile_media_support',
			'sanitize_callback' => 'absint',
			'args'              => array(),
		),

		'bp_media_profile_albums_support' => array(
			'title'             => __( 'Profile Albums', 'buddyboss' ),
			'callback'          => '__return_true',
			'sanitize_callback' => 'absint',
			'args'              => array(
				'class' => 'hidden',
			),
		),
	);

	$fields['bp_media_settings_emoji'] = array(

		'bp_media_profiles_emoji_support' => array(
			'title'             => __( 'Profiles', 'buddyboss' ),
			'callback'          => 'bp_media_settings_callback_profiles_emoji_support',
			'sanitize_callback' => 'absint',
			'args'              => array(),
		),
	);

	$fields['bp_media_settings_gifs'] = array(

		'bp_media_gif_api_key'          => array(
			'title'             => __( 'GIPHY API Key', 'buddyboss' ),
			'callback'          => 'bp_media_settings_callback_gif_key',
			'sanitize_callback' => 'string',
			'args'              => array(),
		),

		'bp_media_profiles_gif_support' => array(
			'title'             => __( 'Profiles', 'buddyboss' ),
			'callback'          => 'bp_media_settings_callback_profiles_gif_support',
			'sanitize_callback' => 'absint',
			'args'              => array(),
		),
	);

	$fields['bp_media_settings_documents']['bp_media_profiles_document_support'] = array(
		'title'             => __( 'Profiles', 'buddyboss' ),
		'callback'          => 'bp_media_settings_callback_profile_document_support',
		'sanitize_callback' => 'absint',
		'args'              => array(),
	);

	if ( bp_is_active( 'groups' ) ) {

		$fields['bp_media_settings_photos']['bp_media_group_media_support'] = array(
			'title'             => __( 'Groups', 'buddyboss' ),
			'callback'          => 'bp_media_settings_callback_group_media_support',
			'sanitize_callback' => 'absint',
			'args'              => array(),
		);

		$fields['bp_media_settings_documents']['bp_media_group_document_support'] = array(
			'title'             => __( 'Groups', 'buddyboss' ),
			'callback'          => 'bp_media_settings_callback_group_document_support',
			'sanitize_callback' => 'absint',
			'args'              => array(),
		);

		$fields['bp_media_settings_photos']['bp_media_group_albums_support'] = array(
			'title'             => __( 'Group Albums', 'buddyboss' ),
			'callback'          => '__return_true',
			'sanitize_callback' => 'absint',
			'args'              => array(
				'class' => 'hidden',
			),
		);

		$fields['bp_media_settings_emoji']['bp_media_groups_emoji_support'] = array(
			'title'             => __( 'Groups', 'buddyboss' ),
			'callback'          => 'bp_media_settings_callback_groups_emoji_support',
			'sanitize_callback' => 'absint',
			'args'              => array(),
		);

		$fields['bp_media_settings_gifs']['bp_media_groups_gif_support'] = array(
			'title'             => __( 'Groups', 'buddyboss' ),
			'callback'          => 'bp_media_settings_callback_groups_gif_support',
			'sanitize_callback' => 'absint',
			'args'              => array(),
		);
	}

	if ( bp_is_active( 'messages' ) ) {

		$fields['bp_media_settings_photos']['bp_media_messages_media_support'] = array(
			'title'             => __( 'Messages', 'buddyboss' ),
			'callback'          => 'bp_media_settings_callback_messages_media_support',
			'sanitize_callback' => 'absint',
			'args'              => array(),
		);

		$fields['bp_media_settings_documents']['bp_media_messages_document_support'] = array(
			'title'             => __( 'Messages', 'buddyboss' ),
			'callback'          => 'bp_media_settings_callback_messages_document_support',
			'sanitize_callback' => 'absint',
			'args'              => array(),
		);

		$fields['bp_media_settings_emoji']['bp_media_messages_emoji_support'] = array(
			'title'             => __( 'Messages', 'buddyboss' ),
			'callback'          => 'bp_media_settings_callback_messages_emoji_support',
			'sanitize_callback' => 'absint',
			'args'              => array(),
		);

		$fields['bp_media_settings_gifs']['bp_media_messages_gif_support'] = array(
			'title'             => __( 'Messages', 'buddyboss' ),
			'callback'          => 'bp_media_settings_callback_messages_gif_support',
			'sanitize_callback' => 'absint',
			'args'              => array(),
		);
	}

	if ( bp_is_active( 'forums' ) ) {

		$fields['bp_media_settings_photos']['bp_media_forums_media_support'] = array(
			'title'             => __( 'Forums', 'buddyboss' ),
			'callback'          => 'bp_media_settings_callback_forums_media_support',
			'sanitize_callback' => 'absint',
			'args'              => array(),
		);

		$fields['bp_media_settings_documents']['bp_media_forums_document_support'] = array(
			'title'             => __( 'Forums', 'buddyboss' ),
			'callback'          => 'bp_media_settings_callback_forums_document_support',
			'sanitize_callback' => 'absint',
			'args'              => array(),
		);

		$fields['bp_media_settings_emoji']['bp_media_forums_emoji_support'] = array(
			'title'             => __( 'Forums', 'buddyboss' ),
			'callback'          => 'bp_media_settings_callback_forums_emoji_support',
			'sanitize_callback' => 'absint',
			'args'              => array(),
		);

		$fields['bp_media_settings_gifs']['bp_media_forums_gif_support'] = array(
			'title'             => __( 'Forums', 'buddyboss' ),
			'callback'          => 'bp_media_settings_callback_forums_gif_support',
			'sanitize_callback' => 'absint',
			'args'              => array(),
		);
	}

	$fields['bp_media_settings_photos']['bp_photo_uploading_tutorial'] = array(
		'title'    => __( '&#160;', 'buddyboss' ),
		'callback' => 'bp_photo_uploading_tutorial',
	);

	$fields['bp_media_settings_emoji']['bp_emoji_tutorial'] = array(
		'title'    => __( '&#160;', 'buddyboss' ),
		'callback' => 'bp_emoji_tutorial',
	);

	$fields['bp_media_settings_gifs']['bp_animated_gifs_tutorial'] = array(
		'title'    => __( '&#160;', 'buddyboss' ),
		'callback' => 'bp_animated_gifs_tutorial',
	);

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
 * @param bool   $slug
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
 * @param bool   $slug
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
 * Setting > Media > Profile support
 *
 * @since BuddyBoss 1.0.0
 */
function bp_media_settings_callback_profile_media_support() {
	?>
	<input name="bp_media_profile_media_support"
		   id="bp_media_profile_media_support"
		   type="checkbox"
		   value="1"
		<?php checked( bp_is_profile_media_support_enabled() ); ?>
	/>
	<label for="bp_media_profile_media_support">
		<?php _e( 'Allow members to upload photos in <strong>profiles</strong>', 'buddyboss' ); ?>
	</label>
	<br/>
	<input name="bp_media_profile_albums_support"
		   id="bp_media_profile_albums_support"
		   type="checkbox"
		   value="1"
		<?php echo ! bp_is_profile_media_support_enabled() ? 'disabled="disabled"' : ''; ?>
		<?php checked( bp_is_profile_albums_support_enabled() ); ?>
	/>
	<label for="bp_media_profile_albums_support">
		<?php _e( 'Enable Albums', 'buddyboss' ); ?>
	</label>
	<?php
}

/**
 * Checks if media profile media support is enabled.
 *
 * @since BuddyBoss 1.0.0
 *
 * @param $default integer
 *
 * @return bool Is media profile media support enabled or not
 */
function bp_is_profile_media_support_enabled( $default = 1 ) {
	return (bool) apply_filters( 'bp_is_profile_media_support_enabled', (bool) get_option( 'bp_media_profile_media_support', $default ) );
}

/**
 * Checks if media profile albums support is enabled.
 *
 * @since BuddyBoss 1.0.0
 *
 * @param $default integer
 *
 * @return bool Is media profile albums support enabled or not
 */
function bp_is_profile_albums_support_enabled( $default = 1 ) {
	return (bool) apply_filters( 'bp_is_profile_albums_support_enabled', (bool) get_option( 'bp_media_profile_albums_support', $default ) );
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
		<?php _e( 'Allow members to upload photos in <strong>groups</strong>', 'buddyboss' ); ?>
	</label>
	<br/>
	<input name="bp_media_group_albums_support"
		   id="bp_media_group_albums_support"
		   type="checkbox"
		   value="1"
		<?php echo ! bp_is_group_media_support_enabled() ? 'disabled="disabled"' : ''; ?>
		<?php checked( bp_is_group_albums_support_enabled() ); ?>
	/>
	<label for="bp_media_group_albums_support">
		<?php _e( 'Enable Albums', 'buddyboss' ); ?>
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
 * Checks if media group album support is enabled.
 *
 * @since BuddyBoss 1.0.0
 *
 * @param $default integer
 *
 * @return bool Is media group album support enabled or not
 */
function bp_is_group_albums_support_enabled( $default = 1 ) {
	return (bool) apply_filters( 'bp_is_group_albums_support_enabled', (bool) get_option( 'bp_media_group_albums_support', $default ) );
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
		<?php _e( 'Allow members to upload photos in <strong>private messages</strong>', 'buddyboss' ); ?>
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
function bp_is_messages_media_support_enabled( $default = 0 ) {
	return (bool) apply_filters( 'bp_is_messages_media_support_enabled', (bool) get_option( 'bp_media_messages_media_support', $default ) );
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
		<?php _e( 'Allow members to upload photos in <strong>forum discussions</strong>', 'buddyboss' ); ?>
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
function bp_is_forums_media_support_enabled( $default = 0 ) {
	return (bool) apply_filters( 'bp_is_forums_media_support_enabled', (bool) get_option( 'bp_media_forums_media_support', $default ) );
}

/**
 * Link to Photo Uploading tutorial
 *
 * @since BuddyBoss 1.0.0
 */
function bp_photo_uploading_tutorial() {
	?>

	<p>
		<a class="button" href="<?php echo bp_core_help_docs_link( 'components/media/photo-uploading.md' ); ?>"><?php _e( 'View Tutorial', 'buddyboss' ); ?></a>
	</p>

	<?php
}

/**
 * Setting > Media > Profiles Emojis support
 *
 * @since BuddyBoss 1.0.0
 */
function bp_media_settings_callback_profiles_emoji_support() {
	?>
	<input name="bp_media_profiles_emoji_support"
		   id="bp_media_profiles_emoji_support"
		   type="checkbox"
		   value="1"
		<?php checked( bp_is_profiles_emoji_support_enabled() ); ?>
	/>
	<label for="bp_media_profiles_emoji_support">
		<?php _e( 'Allow members to use emoji in <strong>profile activity posts</strong>', 'buddyboss' ); ?>
	</label>
	<?php
}

/**
 * Setting > Media > Groups Emojis support
 *
 * @since BuddyBoss 1.0.0
 */
function bp_media_settings_callback_groups_emoji_support() {
	?>
	<input name="bp_media_groups_emoji_support"
		   id="bp_media_groups_emoji_support"
		   type="checkbox"
		   value="1"
		<?php checked( bp_is_groups_emoji_support_enabled() ); ?>
	/>
	<label for="bp_media_groups_emoji_support">
		<?php _e( 'Allow members to use emoji in <strong>group activity posts</strong>', 'buddyboss' ); ?>
	</label>
	<?php
}

/**
 * Setting > Media > Messages Emojis support
 *
 * @since BuddyBoss 1.0.0
 */
function bp_media_settings_callback_messages_emoji_support() {
	?>
	<input name="bp_media_messages_emoji_support"
		   id="bp_media_messages_emoji_support"
		   type="checkbox"
		   value="1"
		<?php checked( bp_is_messages_emoji_support_enabled() ); ?>
	/>
	<label for="bp_media_messages_emoji_support">
		<?php _e( 'Allow members to use emoji in <strong>private messages</strong>', 'buddyboss' ); ?>
	</label>
	<?php
}

/**
 * Setting > Media > Forums Emojis support
 *
 * @since BuddyBoss 1.0.0
 */
function bp_media_settings_callback_forums_emoji_support() {
	?>
	<input name="bp_media_forums_emoji_support"
		   id="bp_media_forums_emoji_support"
		   type="checkbox"
		   value="1"
		<?php checked( bp_is_forums_emoji_support_enabled() ); ?>
	/>
	<label for="bp_media_forums_emoji_support">
		<?php _e( 'Allow members to use emoji in <strong>forum discussions</strong>', 'buddyboss' ); ?>
	</label>
	<?php
}

/**
 * Checks if media emoji support is enabled in profiles.
 *
 * @since BuddyBoss 1.0.0
 *
 * @param $default integer
 *
 * @return bool Is media emoji support enabled or not in profiles
 */
function bp_is_profiles_emoji_support_enabled( $default = 0 ) {
	return (bool) apply_filters( 'bp_is_profiles_emoji_support_enabled', (bool) get_option( 'bp_media_profiles_emoji_support', $default ) );
}

/**
 * Checks if media emoji support is enabled in groups.
 *
 * @since BuddyBoss 1.0.0
 *
 * @param $default integer
 *
 * @return bool Is media emoji support enabled or not in groups
 */
function bp_is_groups_emoji_support_enabled( $default = 0 ) {
	return (bool) apply_filters( 'bp_is_groups_emoji_support_enabled', (bool) get_option( 'bp_media_groups_emoji_support', $default ) );
}

/**
 * Checks if media emoji support is enabled in messages.
 *
 * @since BuddyBoss 1.0.0
 *
 * @param $default integer
 *
 * @return bool Is media emoji support enabled or not in messages
 */
function bp_is_messages_emoji_support_enabled( $default = 0 ) {
	return (bool) apply_filters( 'bp_is_messages_emoji_support_enabled', (bool) get_option( 'bp_media_messages_emoji_support', $default ) );
}

/**
 * Checks if media emoji support is enabled in forums.
 *
 * @since BuddyBoss 1.0.0
 *
 * @param $default integer
 *
 * @return bool Is media emoji support enabled or not in forums
 */
function bp_is_forums_emoji_support_enabled( $default = 0 ) {
	return (bool) apply_filters( 'bp_is_forums_emoji_support_enabled', (bool) get_option( 'bp_media_forums_emoji_support', $default ) );
}

/**
 * Link to Emoji tutorial
 *
 * @since BuddyBoss 1.0.0
 */
function bp_emoji_tutorial() {
	?>

	<p>
		<a class="button" href="<?php echo bp_core_help_docs_link( 'components/media/emoji.md' ); ?>"><?php _e( 'View Tutorial', 'buddyboss' ); ?></a>
	</p>

	<?php
}

/**
 * Setting > Media > GIFs support
 *
 * @since BuddyBoss 1.0.0
 */
function bp_media_settings_callback_gif_key() {
	?>
	<input type="text"
		   name="bp_media_gif_api_key"
		   id="bp_media_gif_api_key"
		   value="<?php echo bp_media_get_gif_api_key(); ?>"
		   placeholder="<?php _e( 'GIPHY API Key', 'buddyboss' ); ?>"
		   style="width: 300px;"
	/>
	<p class="description"><?php _e( 'This feature requires an account at <a href="https://developers.giphy.com/">GIPHY</a>. Create your account, and then click "Create an App". Once done, copy the API key and paste it above.', 'buddyboss' ); ?></p>
	<?php
}

/**
 * Return GIFs API Key
 *
 * @since BuddyBoss 1.0.0
 *
 * @param string $default Optional. Fallback value if not found in the database.
 *                      Default: true.
 * @return GIF Api Key if, empty string.
 */
function bp_media_get_gif_api_key( $default = '' ) {

	/**
	 * Filters whether GIF key.
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @param GIF Api Key if, empty sting.
	 */
	return apply_filters( 'bp_media_get_gif_api_key', bp_get_option( 'bp_media_gif_api_key', $default ) );
}

/**
 * Setting > Media > Profiles GIFs support
 *
 * @since BuddyBoss 1.0.0
 */
function bp_media_settings_callback_profiles_gif_support() {
	?>
	<input name="bp_media_profiles_gif_support"
		   id="bp_media_profiles_gif_support"
		   type="checkbox"
		   value="1"
		<?php checked( bp_is_profiles_gif_support_enabled() ); ?>
	/>
	<label for="bp_media_profiles_gif_support">
		<?php _e( 'Allow members to use animated GIFs in <strong>profile activity posts</strong>', 'buddyboss' ); ?>
	</label>
	<?php
}

/**
 * Setting > Media > Groups GIFs support
 *
 * @since BuddyBoss 1.0.0
 */
function bp_media_settings_callback_groups_gif_support() {
	?>
	<input name="bp_media_groups_gif_support"
		   id="bp_media_groups_gif_support"
		   type="checkbox"
		   value="1"
		<?php checked( bp_is_groups_gif_support_enabled() ); ?>
	/>
	<label for="bp_media_groups_gif_support">
		<?php _e( 'Allow members to use animated GIFs in <strong>group activity posts</strong>', 'buddyboss' ); ?>
	</label>
	<?php
}

/**
 * Setting > Media > Messages GIFs support
 *
 * @since BuddyBoss 1.0.0
 */
function bp_media_settings_callback_messages_gif_support() {
	?>
	<input name="bp_media_messages_gif_support"
		   id="bp_media_messages_gif_support"
		   type="checkbox"
		   value="1"
		<?php checked( bp_is_messages_gif_support_enabled() ); ?>
	/>
	<label for="bp_media_messages_gif_support">
		<?php _e( 'Allow members to use animated GIFs in <strong>private messages</strong>', 'buddyboss' ); ?>
	</label>
	<?php
}

/**
 * Setting > Media > Forums GIFs support
 *
 * @since BuddyBoss 1.0.0
 */
function bp_media_settings_callback_forums_gif_support() {
	?>
	<input name="bp_media_forums_gif_support"
		   id="bp_media_forums_gif_support"
		   type="checkbox"
		   value="1"
		<?php checked( bp_is_forums_gif_support_enabled() ); ?>
	/>
	<label for="bp_media_forums_gif_support">
		<?php _e( 'Allow members to use animated GIFs in <strong>forum discussions</strong>', 'buddyboss' ); ?>
	</label>
	<?php
}

/**
 * Checks if media gif support is enabled in profiles.
 *
 * @since BuddyBoss 1.0.0
 *
 * @param $default integer
 *
 * @return bool Is media gif support enabled or not in profiles
 */
function bp_is_profiles_gif_support_enabled( $default = 0 ) {
	return (bool) apply_filters( 'bp_is_profiles_gif_support_enabled', (bool) get_option( 'bp_media_profiles_gif_support', $default ) );
}

/**
 * Checks if media gif support is enabled in groups.
 *
 * @since BuddyBoss 1.0.0
 *
 * @param $default integer
 *
 * @return bool Is media gif support enabled or not in groups
 */
function bp_is_groups_gif_support_enabled( $default = 0 ) {
	return (bool) apply_filters( 'bp_is_groups_gif_support_enabled', (bool) get_option( 'bp_media_groups_gif_support', $default ) );
}

/**
 * Checks if media gif support is enabled in messages.
 *
 * @since BuddyBoss 1.0.0
 *
 * @param $default integer
 *
 * @return bool Is media gif support enabled or not in messages
 */
function bp_is_messages_gif_support_enabled( $default = 0 ) {
	return (bool) apply_filters( 'bp_is_messages_gif_support_enabled', (bool) get_option( 'bp_media_messages_gif_support', $default ) );
}

/**
 * Checks if media gif support is enabled in forums.
 *
 * @since BuddyBoss 1.0.0
 *
 * @param $default integer
 *
 * @return bool Is media gif support enabled or not in forums
 */
function bp_is_forums_gif_support_enabled( $default = 0 ) {
	return (bool) apply_filters( 'bp_is_forums_gif_support_enabled', (bool) get_option( 'bp_media_forums_gif_support', $default ) );
}

/**
 * Link to Animated GIFs tutorial
 *
 * @since BuddyBoss 1.0.0
 */
function bp_animated_gifs_tutorial() {
	?>

	<p>
		<a class="button" href="<?php echo bp_core_help_docs_link( 'components/media/animated-gifs.md' ); ?>"><?php _e( 'View Tutorial', 'buddyboss' ); ?></a>
	</p>

	<?php
}

/**
 * Setting > Media > Documents support
 *
 * @since BuddyBoss 1.2.3
 */
function bp_media_settings_callback_messages_document_support() {
	?>
	<input name="bp_media_messages_document_support"
	       id="bp_media_messages_document_support"
	       type="checkbox"
	       value="1"
		<?php checked( bp_is_messages_document_support_enabled() ); ?>
	/>
	<label for="bp_media_messages_document_support">
		<?php _e( 'Allow members to upload documents in <strong>private messages</strong>', 'buddyboss' ); ?>
	</label>
	<?php
}

/**
 * Checks if media messages doc support is enabled.
 *
 * @since BuddyBoss 1.2.3
 *
 * @param $default integer
 *
 * @return bool Is media messages doc support enabled or not
 */
function bp_is_messages_document_support_enabled( $default = 0 ) {
	return (bool) apply_filters( 'bp_is_messages_document_support_enabled', (bool) get_option( 'bp_media_messages_document_support', $default ) );
}

/**
 * Setting > Media > Groups support
 *
 * @since BuddyBoss 1.2.3
 */
function bp_media_settings_callback_group_document_support() {
	?>
	<input name="bp_media_group_document_support"
	       id="bp_media_group_document_support"
	       type="checkbox"
	       value="1"
		<?php checked( bp_is_group_document_support_enabled() ); ?>
	/>
	<label for="bp_media_group_document_support">
		<?php _e( 'Allow members to upload documents in <strong>groups</strong>', 'buddyboss' ); ?>
	</label>
	<?php
}

/**
 * Checks if media group document support is enabled.
 *
 * @since BuddyBoss 1.2.3
 *
 * @param $default integer
 *
 * @return bool Is media group document support enabled or not
 */
function bp_is_group_document_support_enabled( $default = 0 ) {
	return (bool) apply_filters( 'bp_is_group_document_support_enabled', (bool) get_option( 'bp_media_group_document_support', $default ) );
}

/**
 * Setting > Media > Forums support
 *
 * @since BuddyBoss 1.2.3
 */
function bp_media_settings_callback_forums_document_support() {
	?>
	<input name="bp_media_forums_document_support"
	       id="bp_media_forums_document_support"
	       type="checkbox"
	       value="1"
		<?php checked( bp_is_forums_document_support_enabled() ); ?>
	/>
	<label for="bp_media_forums_document_support">
		<?php _e( 'Allow members to upload documents in <strong>forum discussions</strong>', 'buddyboss' ); ?>
	</label>
	<?php
}

/**
 * Checks if media forums document support is enabled.
 *
 * @since BuddyBoss 1.2.3
 *
 * @param $default integer
 *
 * @return bool Is media forums document support enabled or not
 */
function bp_is_forums_document_support_enabled( $default = 0 ) {
	return (bool) apply_filters( 'bp_is_forums_document_support_enabled', (bool) get_option( 'bp_media_forums_document_support', $default ) );
}

/**
 * Setting > Media > Forums support
 *
 * @since BuddyBoss 1.2.3
 */
function bp_media_settings_callback_profile_document_support() {
	?>
	<input name="bp_media_profiles_document_support"
	       id="bp_media_profiles_document_support"
	       type="checkbox"
	       value="1"
		<?php checked( bp_is_profiles_document_support_enabled() ); ?>
	/>
	<label for="bp_media_profiles_document_support">
		<?php _e( 'Allow members to upload documents in <strong>profiles</strong>', 'buddyboss' ); ?>
	</label>
	<?php
}

/**
 * Checks if media forums document support is enabled.
 *
 * @since BuddyBoss 1.2.3
 *
 * @param $default integer
 *
 * @return bool Is media forums document support enabled or not
 */
function bp_is_profiles_document_support_enabled( $default = 0 ) {
	return (bool) apply_filters( 'bp_is_profiles_document_support_enabled', (bool) get_option( 'bp_media_profiles_document_support', $default ) );
}

