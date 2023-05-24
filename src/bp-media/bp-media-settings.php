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
 * @return array
 * @since BuddyBoss 1.0.0
 */
function bp_media_get_settings_sections() {

	$settings = array(
		'bp_media_settings_photos'    => array(
			'page'              => 'media',
			'title'             => __( 'Photos', 'buddyboss' ),
			'tutorial_callback' => 'bp_photo_uploading_tutorial',
		),
		'bp_media_settings_documents' => array(
			'page'              => 'doc',
			'title'             => __( 'Documents', 'buddyboss' ),
			'callback'          => 'bp_media_admin_setting_callback_document_section',
			'tutorial_callback' => 'bp_document_uploading_tutorial',
		),
		'bp_media_settings_videos'    => array(
			'page'              => 'video',
			'title'             => __( 'Videos', 'buddyboss' ),
			'callback'          => 'bp_video_admin_setting_callback_video_section',
			'tutorial_callback' => 'bp_video_uploading_tutorial',
		),
		'bp_media_settings_emoji'     => array(
			'page'              => 'media',
			'title'             => __( 'Emoji', 'buddyboss' ),
			'tutorial_callback' => 'bp_emoji_tutorial',
		),
		'bp_media_settings_gifs'      => array(
			'page'              => 'media',
			'title'             => __( 'Animated GIFs', 'buddyboss' ),
			'tutorial_callback' => 'bp_animated_gifs_tutorial',
		),
	);

	return (array) apply_filters( 'bp_media_get_settings_sections', $settings );
}

/**
 * Get all of the settings fields.
 *
 * @return array
 * @since BuddyBoss 1.0.0
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

	$fields['bp_media_settings_gifs'] = array(

		'bp_media_gif_api_key' => array(
			'title'             => __( 'GIPHY API Key', 'buddyboss' ),
			'callback'          => 'bp_media_settings_callback_gif_key',
			'sanitize_callback' => 'string',
			'args'              => array(),
		),

	);

	if ( bp_is_active( 'activity' ) ) {
		$fields['bp_media_settings_emoji'] = array(

			'bp_media_profiles_emoji_support' => array(
				'title'             => __( 'Profiles', 'buddyboss' ),
				'callback'          => 'bp_media_settings_callback_profiles_emoji_support',
				'sanitize_callback' => 'absint',
				'args'              => array(),
			),
		);

		$fields['bp_media_settings_gifs']['bp_media_profiles_gif_support'] = array(
			'title'             => __( 'Profiles', 'buddyboss' ),
			'callback'          => 'bp_media_settings_callback_profiles_gif_support',
			'sanitize_callback' => 'absint',
			'args'              => array(),
		);
	}

	$fields['bp_media_settings_documents']['bp_media_profile_document_support'] = array(
		'title'             => __( 'Profiles', 'buddyboss' ),
		'callback'          => 'bp_media_settings_callback_profile_document_support',
		'sanitize_callback' => 'absint',
		'args'              => array(),
	);

	$fields['bp_media_settings_videos']['bp_video_profile_video_support'] = array(
		'title'             => __( 'Profiles', 'buddyboss' ),
		'callback'          => 'bp_video_settings_callback_profile_video_support',
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

		$fields['bp_media_settings_videos']['bp_video_group_video_support'] = array(
			'title'             => __( 'Groups', 'buddyboss' ),
			'callback'          => 'bp_video_settings_callback_group_video_support',
			'sanitize_callback' => 'absint',
			'args'              => array(),
		);

		if ( bp_is_active( 'activity' ) ) {

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

		$fields['bp_media_settings_videos']['bp_video_messages_video_support'] = array(
			'title'             => __( 'Messages', 'buddyboss' ),
			'callback'          => 'bp_video_settings_callback_messages_video_support',
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

		$fields['bp_media_settings_videos']['bp_video_forums_video_support'] = array(
			'title'             => __( 'Forums', 'buddyboss' ),
			'callback'          => 'bp_video_settings_callback_forums_video_support',
			'sanitize_callback' => 'absint',
			'args'              => array(),
		);
	}

	$fields['bp_media_settings_photos']['bp_media_allowed_size'] = array(
		'title'             => __( 'Upload Size', 'buddyboss' ),
		'callback'          => 'bp_media_settings_callback_media_allowed_size',
		'sanitize_callback' => 'absint',
		'args'              => array(),
	);

	$fields['bp_media_settings_photos']['bp_media_allowed_per_batch'] = array(
		'title'             => __( 'Upload Limit', 'buddyboss' ),
		'callback'          => 'bp_media_settings_callback_media_allowed_per_batch',
		'sanitize_callback' => 'absint',
		'args'              => array(),
	);

	$fields['bp_media_settings_documents']['bp_document_allowed_size'] = array(
		'title'             => __( 'Upload Size', 'buddyboss' ),
		'callback'          => 'bp_media_settings_callback_document_allowed_size',
		'sanitize_callback' => 'absint',
		'args'              => array(),
	);

	$fields['bp_media_settings_documents']['bp_document_allowed_per_batch'] = array(
		'title'             => __( 'Upload Limit', 'buddyboss' ),
		'callback'          => 'bp_media_settings_callback_document_allowed_per_batch',
		'sanitize_callback' => 'absint',
		'args'              => array(),
	);

	$fields['bp_media_settings_videos']['bp_video_allowed_size'] = array(
		'title'             => __( 'Upload Size', 'buddyboss' ),
		'callback'          => 'bp_video_settings_callback_video_allowed_size',
		'sanitize_callback' => 'absint',
		'args'              => array(),
	);

	$fields['bp_media_settings_videos']['bp_video_allowed_per_batch'] = array(
		'title'             => __( 'Allowed Per Batch', 'buddyboss' ),
		'callback'          => 'bp_video_settings_callback_video_allowed_per_batch',
		'sanitize_callback' => 'absint',
		'args'              => array(),
	);

	$fields['bp_media_settings_documents']['bp_media_extension_document_support'] = array(
		'title'    => __( 'File Extensions', 'buddyboss' ),
		'callback' => 'bp_media_settings_callback_extension_link',
	);

	$fields['bp_media_settings_videos']['bp_video_extension_video_support'] = array(
		'title'    => __( 'File Extensions', 'buddyboss' ),
		'callback' => 'bp_video_settings_callback_extension_link',
	);

	return (array) apply_filters( 'bp_media_get_settings_fields', $fields );
}

/**
 * Register the settings field.
 *
 * @param array $setting settings field.
 *
 * @since 1.1.0
 */
function bb_admin_setting_media_access_control_register_fields( $setting ) {
	$setting->add_section(
		'bp_media_settings_symlinks',
		__( 'Media Security & Performance', 'buddyboss' ),
		''
	);
	$setting->add_field(
		'bp_media_symlink_support',
		__( 'Symbolic Links', 'buddyboss' ),
		'bb_media_settings_callback_symlink_support',
		'absint'
	);
	$setting->add_field(
		'bp_media_symlink_direct_access',
		__( 'Direct Access', 'buddyboss' ),
		'bb_media_settings_callback_symlink_direct_access',
		'absint'
	);
}
add_action( 'bp_admin_setting_media_register_fields', 'bb_admin_setting_media_access_control_register_fields' );

/** General Section **************************************************************/

/**
 * Get settings fields by section.
 *
 * @param string $section_id
 *
 * @return mixed False if section is invalid, array of fields otherwise.
 * @since BuddyBoss 1.0.0
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
 * @param string $option
 * @param string $default
 * @param bool   $slug
 *
 * @since BuddyBoss 1.0.0
 */
function bp_media_form_option( $option, $default = '', $slug = false ) {
	echo bp_media_get_form_option( $option, $default, $slug );
}

/**
 * Return settings API option
 *
 * @param string $option
 * @param string $default
 * @param bool   $slug
 *
 * @return mixed
 * @since BuddyBoss 1.0.0
 *
 * @uses get_option()
 * @uses esc_attr()
 * @uses apply_filters()
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
		<?php
		if ( bp_is_active( 'activity' ) ) {
			_e( 'Allow members to upload photos in <strong>profiles</strong> and <strong>activity posts</strong>', 'buddyboss' );
		} else {
			_e( 'Allow members to upload photos in <strong>profiles</strong>', 'buddyboss' );
		}
		?>
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
		<?php _e( 'Enable albums in profiles', 'buddyboss' ); ?>
	</label>
	<?php
}

/**
 * Checks if media profile media support is enabled.
 *
 * @param $default integer
 *
 * @return bool Is media profile media support enabled or not
 * @since BuddyBoss 1.0.0
 */
function bp_is_profile_media_support_enabled( $default = 1 ) {
	return (bool) apply_filters( 'bp_is_profile_media_support_enabled', (bool) get_option( 'bp_media_profile_media_support', $default ) );
}

/**
 * Checks if media profile albums support is enabled.
 *
 * @param $default integer
 *
 * @return bool Is media profile albums support enabled or not
 * @since BuddyBoss 1.0.0
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
	<input name="bp_media_group_media_support" id="bp_media_group_media_support" type="checkbox" value="1" <?php checked( bp_is_group_media_support_enabled() ); ?> />
	<label for="bp_media_group_media_support">
		<?php

		$string_array = array();

		if ( bp_is_active( 'groups' ) ) {
			$string_array[] = __( 'groups', 'buddyboss' );
		}

		if ( bp_is_active( 'activity' ) ) {
			$string_array[] = __( 'activity posts', 'buddyboss' );
		}

		if ( true === bp_disable_group_messages() ) {
			$string_array[] = __( 'messages', 'buddyboss' );
		}

		if ( bp_is_active( 'forums' ) ) {
			$string_array[] = __( 'forums', 'buddyboss' );
		}

		$last_string    = array_pop( $string_array );
		$display_string = '';
		if ( count( $string_array ) ) {
			$second_to_last_string_name = array_pop( $string_array );
			$display_string            .= implode( ', ', $string_array );
			if ( ! empty( $second_to_last_string_name ) ) {
				$display_string .= ', ' . esc_html( $second_to_last_string_name ) . '</strong> and <strong>';
			} else {
				$display_string .= '</strong> and <strong>';
			}
		}
		$display_string .= $last_string;

		printf(
			'%1$s <strong>%2$s</strong>',
			__( 'Allow members to upload photos in', 'buddyboss' ),
			$display_string
		);

		?>
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
		<?php _e( 'Enable albums in groups', 'buddyboss' ); ?>
	</label>
	<?php
}

/**
 * Checks if media group media support is enabled.
 *
 * @param $default integer
 *
 * @return bool Is media group media support enabled or not
 * @since BuddyBoss 1.0.0
 */
function bp_is_group_media_support_enabled( $default = 1 ) {
	return (bool) apply_filters( 'bp_is_group_media_support_enabled', (bool) get_option( 'bp_media_group_media_support', $default ) );
}

/**
 * Checks if media group album support is enabled.
 *
 * @param $default integer
 *
 * @return bool Is media group album support enabled or not
 * @since BuddyBoss 1.0.0
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
	<input name="bp_media_messages_media_support" id="bp_media_messages_media_support" type="checkbox" value="1" <?php checked( bp_is_messages_media_support_enabled() ); ?> />
	<label for="bp_media_messages_media_support">
	<?php
		_e( 'Allow members to upload photos in <strong>private messages</strong>', 'buddyboss' );
	?>
	</label>
	<?php
}

/**
 * Checks if media messages media support is enabled.
 *
 * @param $default integer
 *
 * @return bool Is media messages media support enabled or not
 * @since BuddyBoss 1.0.0
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
		<?php _e( 'Allow members to upload photos in <strong>forum discussions</strong> and <strong>replies</strong>', 'buddyboss' ); ?>
	</label>
	<?php
}

/**
 * Checks if media forums media support is enabled.
 *
 * @param $default integer
 *
 * @return bool Is media forums media support enabled or not
 * @since BuddyBoss 1.0.0
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
		<a class="button" href="
		<?php
		echo bp_get_admin_url(
			add_query_arg(
				array(
					'page'    => 'bp-help',
					'article' => 62827,
				),
				'admin.php'
			)
		);
		?>
		"><?php _e( 'View Tutorial', 'buddyboss' ); ?></a>
	</p>

	<?php
}

/**
 * Link to Document Uploading tutorial
 *
 * @since BuddyBoss 1.4.0
 */
function bp_document_uploading_tutorial() {
	?>

	<p>
		<a class="button" href="
		<?php
		echo bp_get_admin_url(
			add_query_arg(
				array(
					'page'    => 'bp-help',
					'article' => 87466,
				),
				'admin.php'
			)
		);
		?>
		"><?php _e( 'View Tutorial', 'buddyboss' ); ?></a>
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
	<input name="bp_media_profiles_emoji_support" id="bp_media_profiles_emoji_support" type="checkbox" value="1" <?php checked( bp_is_profiles_emoji_support_enabled() ); ?> />
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

	$string_array = array();

	if ( bp_is_active( 'groups' ) ) {
		$string_array[] = __( 'groups', 'buddyboss' );
	}

	if ( bp_is_active( 'activity' ) ) {
		$string_array[] = __( 'activity posts', 'buddyboss' );
	}

	if ( true === bp_disable_group_messages() ) {
		$string_array[] = __( 'messages', 'buddyboss' );
	}

	if ( bp_is_active( 'forums' ) ) {
		$string_array[] = __( 'forums', 'buddyboss' );
	}

	$last_string    = array_pop( $string_array );
	$display_string = '';
	if ( count( $string_array ) ) {
		$second_to_last_string_name = array_pop( $string_array );
		$display_string            .= implode( ', ', $string_array );
		if ( ! empty( $second_to_last_string_name ) ) {
			$display_string .= ', ' . esc_html( $second_to_last_string_name ) . '</strong> and <strong>';
		} else {
			$display_string .= '</strong> and <strong>';
		}
	}
	$display_string .= $last_string;

	?>
	<input name="bp_media_groups_emoji_support" id="bp_media_groups_emoji_support" type="checkbox" value="1" <?php checked( bp_is_groups_emoji_support_enabled() ); ?> />
	<label for="bp_media_groups_emoji_support">
		<?php
		printf(
			'%1$s <strong>%2$s</strong>',
			__( 'Allow members to use emoji in', 'buddyboss' ),
			$display_string
		);
		?>
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
	<input name="bp_media_messages_emoji_support" id="bp_media_messages_emoji_support" type="checkbox" value="1" <?php checked( bp_is_messages_emoji_support_enabled() ); ?> />
	<label for="bp_media_messages_emoji_support">
		<?php
		_e( 'Allow members to use emoji in <strong>private messages</strong>', 'buddyboss' );
		?>
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
	<input name="bp_media_forums_emoji_support" id="bp_media_forums_emoji_support" type="checkbox" value="1" <?php checked( bp_is_forums_emoji_support_enabled() ); ?> />
	<label for="bp_media_forums_emoji_support">
		<?php _e( 'Allow members to use emoji in <strong>forum discussions</strong> and <strong>replies</strong>', 'buddyboss' ); ?>
	</label>
	<?php
}

/**
 * Checks if media emoji support is enabled in profiles.
 *
 * @param int $default default value.
 *
 * @return bool Is media emoji support enabled or not in profiles
 * @since BuddyBoss 1.0.0
 */
function bp_is_profiles_emoji_support_enabled( $default = 0 ) {
	return (bool) apply_filters( 'bp_is_profiles_emoji_support_enabled', (bool) get_option( 'bp_media_profiles_emoji_support', $default ) );
}

/**
 * Checks if media emoji support is enabled in groups.
 *
 * @param int $default default value.
 *
 * @return bool Is media emoji support enabled or not in groups
 * @since BuddyBoss 1.0.0
 */
function bp_is_groups_emoji_support_enabled( $default = 0 ) {
	return (bool) apply_filters( 'bp_is_groups_emoji_support_enabled', (bool) get_option( 'bp_media_groups_emoji_support', $default ) );
}

/**
 * Checks if media emoji support is enabled in messages.
 *
 * @param int $default default value.
 *
 * @return bool Is media emoji support enabled or not in messages
 * @since BuddyBoss 1.0.0
 */
function bp_is_messages_emoji_support_enabled( $default = 0 ) {
	return (bool) apply_filters( 'bp_is_messages_emoji_support_enabled', (bool) get_option( 'bp_media_messages_emoji_support', $default ) );
}

/**
 * Checks if media emoji support is enabled in forums.
 *
 * @param int $default default value.
 *
 * @return bool Is media emoji support enabled or not in forums
 * @since BuddyBoss 1.0.0
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
		<a class="button" href="
		<?php
		echo bp_get_admin_url(
			add_query_arg(
				array(
					'page'    => 'bp-help',
					'article' => 62828,
				),
				'admin.php'
			)
		);
		?>
		"><?php _e( 'View Tutorial', 'buddyboss' ); ?></a>
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
	<div class="password-toggle">
		<input type="password" name="bp_media_gif_api_key" id="bp_media_gif_api_key" value="<?php echo esc_attr( bp_media_get_gif_api_key() ); ?>" placeholder="<?php esc_html_e( 'GIPHY API Key', 'buddyboss' ); ?>" <?php echo ! empty( bp_media_get_gif_api_key() ) ? 'readonly' : ''; ?> />
		<button type="button" class="button button-secondary bb-hide-pw hide-if-no-js" aria-label="<?php esc_attr_e( 'Toggle', 'buddyboss' ); ?>">
			<span class="bb-icon bb-icon-eye-small" aria-hidden="true"></span>
		</button>
	</div>
	<input type="button" data-connected="<?php echo empty( bp_media_get_gif_api_key() ) ? false : true; ?>" data-nonce="<?php echo esc_attr( wp_create_nonce( 'bb-giphy-connect' ) ); ?>" name="connect" id="bb-giphy-connect" class="button <?php echo empty( bp_media_get_gif_api_key() ) ? 'button-primary' : ''; ?>" data-disconnect-text="<?php esc_html_e( 'Disconnect', 'buddyboss' ); ?>" data-connect-text="<?php esc_html_e( 'Connect', 'buddyboss' ); ?>" value="<?php empty( bp_media_get_gif_api_key() ) ? esc_html_e( 'Connect', 'buddyboss' ) : esc_html_e( 'Disconnect', 'buddyboss' ); ?>" />
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
	<?php $is_valid_key = bb_check_valid_giphy_api_key( '', true ); ?>
	<p class="display-notice bp-new-notice-panel-notice <?php echo ( ! is_wp_error( $is_valid_key ) && isset( $is_valid_key['response']['code'] ) && 200 !== $is_valid_key['response']['code'] ) ? '' : 'hidden'; ?>">
		<strong><?php esc_html_e( 'There was a problem connecting to GIPHY with your API key:', 'buddyboss' ); ?></strong><br>(<span id="giphy_response_code"><?php echo ( isset( $is_valid_key['response']['code'] ) ) ? esc_attr( $is_valid_key['response']['code'] ) : ''; ?>)</span>) <span id="giphy_response_message"><?php echo isset( $is_valid_key['response']['message'] ) ? esc_attr( $is_valid_key['response']['message'] ) : ''; ?>.</span>
	</p>
	<?php
}

/**
 * Return GIFs API Key
 *
 * @param string $default Optional. Fallback value if not found in the database.
 *                      Default: true.
 *
 * @return GIF Api Key if, empty string.
 * @since BuddyBoss 1.0.0
 */
function bp_media_get_gif_api_key( $default = '' ) {

	/**
	 * Filters whether GIF key.
	 *
	 * @param GIF Api Key if, empty sting.
	 *
	 * @since BuddyBoss 1.0.0
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
	<input name="bp_media_profiles_gif_support" id="bp_media_profiles_gif_support" type="checkbox" value="1" <?php checked( bp_is_profiles_gif_support_enabled() ); echo ! bb_check_valid_giphy_api_key() ? ' disabled' : ''; ?>/>
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

	$string_array = array();

	if ( bp_is_active( 'groups' ) ) {
		$string_array[] = __( 'groups', 'buddyboss' );
	}

	if ( bp_is_active( 'activity' ) ) {
		$string_array[] = __( 'activity posts', 'buddyboss' );
	}

	if ( true === bp_disable_group_messages() ) {
		$string_array[] = __( 'messages', 'buddyboss' );
	}

	if ( bp_is_active( 'forums' ) ) {
		$string_array[] = __( 'forums', 'buddyboss' );
	}

	$last_string    = array_pop( $string_array );
	$display_string = '';
	if ( count( $string_array ) ) {
		$second_to_last_string_name = array_pop( $string_array );
		$display_string            .= implode( ', ', $string_array );
		if ( ! empty( $second_to_last_string_name ) ) {
			$display_string .= ', ' . esc_html( $second_to_last_string_name ) . '</strong> and <strong>';
		} else {
			$display_string .= '</strong> and <strong>';
		}
	}
	$display_string .= $last_string;
	?>
	<input name="bp_media_groups_gif_support" id="bp_media_groups_gif_support" type="checkbox" value="1" <?php checked( bp_is_groups_gif_support_enabled() );  echo ! bb_check_valid_giphy_api_key() ? ' disabled' : ''; ?>/>
	<label for="bp_media_groups_gif_support">
		<?php
		printf(
			'%1$s <strong>%2$s</strong>',
			__( 'Allow members to use animated GIFs in', 'buddyboss' ),
			$display_string
		);
		?>
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
	<input name="bp_media_messages_gif_support" id="bp_media_messages_gif_support" type="checkbox" value="1" <?php checked( bp_is_messages_gif_support_enabled() ); echo ! bb_check_valid_giphy_api_key() ? ' disabled' : ''; ?>/>
	<label for="bp_media_messages_gif_support">
		<?php
		_e( 'Allow members to use animated GIFs in <strong>private messages</strong>', 'buddyboss' );
		?>
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
	<input name="bp_media_forums_gif_support" id="bp_media_forums_gif_support" type="checkbox" value="1" <?php checked( bp_is_forums_gif_support_enabled() ); echo ! bb_check_valid_giphy_api_key() ? ' disabled' : ''; ?>/>
	<label for="bp_media_forums_gif_support">
		<?php _e( 'Allow members to use animated GIFs in <strong>forum discussions</strong> and <strong>replies</strong>', 'buddyboss' ); ?>
	</label>
	<?php
}

/**
 * Checks if media gif support is enabled in profiles.
 *
 * @param $default integer
 *
 * @return bool Is media gif support enabled or not in profiles
 * @since BuddyBoss 1.0.0
 */
function bp_is_profiles_gif_support_enabled( $default = 0 ) {
	$result = false;
	if ( bb_check_valid_giphy_api_key() ) {
		$result = (bool) get_option( 'bp_media_profiles_gif_support', $default );
	}
	return (bool) apply_filters( 'bp_is_profiles_gif_support_enabled', $result );
}

/**
 * Checks if media gif support is enabled in groups.
 *
 * @param $default integer
 *
 * @return bool Is media gif support enabled or not in groups
 * @since BuddyBoss 1.0.0
 */
function bp_is_groups_gif_support_enabled( $default = 0 ) {
	$result = false;
	if ( bb_check_valid_giphy_api_key() ) {
		$result = (bool) get_option( 'bp_media_groups_gif_support', $default );
	}
	return (bool) apply_filters( 'bp_is_groups_gif_support_enabled', $result );
}

/**
 * Checks if media gif support is enabled in messages.
 *
 * @param $default integer
 *
 * @return bool Is media gif support enabled or not in messages
 * @since BuddyBoss 1.0.0
 */
function bp_is_messages_gif_support_enabled( $default = 0 ) {
	$result = false;
	if ( bb_check_valid_giphy_api_key() ) {
		$result = (bool) get_option( 'bp_media_messages_gif_support', $default );
	}
	return (bool) apply_filters( 'bp_is_messages_gif_support_enabled', $result );
}

/**
 * Checks if media gif support is enabled in forums.
 *
 * @param $default integer
 *
 * @return bool Is media gif support enabled or not in forums
 * @since BuddyBoss 1.0.0
 */
function bp_is_forums_gif_support_enabled( $default = 0 ) {
	$result = false;
	if ( bb_check_valid_giphy_api_key() ) {
		$result = (bool) get_option( 'bp_media_forums_gif_support', $default );
	}
	return (bool) apply_filters( 'bp_is_forums_gif_support_enabled', $result );
}

/**
 * Link to Animated GIFs tutorial
 *
 * @since BuddyBoss 1.0.0
 */
function bp_animated_gifs_tutorial() {
	?>

	<p>
		<a class="button" href="
		<?php
		echo bp_get_admin_url(
			add_query_arg(
				array(
					'page'    => 'bp-help',
					'article' => 62829,
				),
				'admin.php'
			)
		);
		?>
		"><?php _e( 'View Tutorial', 'buddyboss' ); ?></a>
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
	<input name="bp_media_messages_document_support" id="bp_media_messages_document_support" type="checkbox" value="1" <?php checked( bp_is_messages_document_support_enabled() ); ?> />
	<label for="bp_media_messages_document_support">
	<?php
		_e( 'Allow members to upload documents in <strong>private messages</strong>', 'buddyboss' );
	?>
	</label>
	<?php
}

/**
 * Checks if media messages doc support is enabled.
 *
 * @param $default integer
 *
 * @return bool Is media messages doc support enabled or not
 * @since BuddyBoss 1.2.3
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
	<input name="bp_media_group_document_support" id="bp_media_group_document_support" type="checkbox" value="1" <?php checked( bp_is_group_document_support_enabled() ); ?> />
	<label for="bp_media_group_document_support">
		<?php

		$string_array = array();

		if ( bp_is_active( 'groups' ) ) {
			$string_array[] = __( 'groups', 'buddyboss' );
		}

		if ( bp_is_active( 'activity' ) ) {
			$string_array[] = __( 'activity posts', 'buddyboss' );
		}

		if ( true === bp_disable_group_messages() ) {
			$string_array[] = __( 'messages', 'buddyboss' );
		}

		if ( bp_is_active( 'forums' ) ) {
			$string_array[] = __( 'forums', 'buddyboss' );
		}

		$last_string    = array_pop( $string_array );
		$display_string = '';
		if ( count( $string_array ) ) {
			$second_to_last_string_name = array_pop( $string_array );
			$display_string            .= implode( ', ', $string_array );
			if ( ! empty( $second_to_last_string_name ) ) {
				$display_string .= ', ' . esc_html( $second_to_last_string_name ) . '</strong> and <strong>';
			} else {
				$display_string .= '</strong> and <strong>';
			}
		}
		$display_string .= $last_string;

		printf(
			'%1$s <strong>%2$s</strong>',
			__( 'Allow members to upload documents in', 'buddyboss' ),
			$display_string
		);
		?>
	</label>
	<?php
}

/**
 * Checks if media group document support is enabled.
 *
 * @param $default integer
 *
 * @return bool Is media group document support enabled or not
 * @since BuddyBoss 1.2.3
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
		<?php _e( 'Allow members to upload documents in <strong>forum discussions</strong> and <strong>replies</strong>', 'buddyboss' ); ?>
	</label>
	<?php
}

/**
 * Checks if media forums document support is enabled.
 *
 * @param $default integer
 *
 * @return bool Is media forums document support enabled or not
 * @since BuddyBoss 1.2.3
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
	<input name="bp_media_profile_document_support"
		   id="bp_media_profile_document_support"
		   type="checkbox"
		   value="1"
		<?php checked( bp_is_profile_document_support_enabled() ); ?>
	/>
	<label for="bp_media_profile_document_support">
		<?php
		if ( bp_is_active( 'activity' ) ) {
			_e( 'Allow members to upload documents in <strong>profiles</strong> and <strong>activity posts</strong>', 'buddyboss' );
		} else {
			_e( 'Allow members to upload documents in <strong>profiles</strong>', 'buddyboss' );
		}
		?>
	</label>
	<?php
}

/**
 * Checks if media forums document support is enabled.
 *
 * @param $default false.
 *
 * @return bool Is media forums document support enabled or not
 * @since BuddyBoss 1.2.3
 */
function bp_is_profile_document_support_enabled( $default = 0 ) {
	return (bool) apply_filters( 'bp_is_profile_document_support_enabled', (bool) get_option( 'bp_media_profile_document_support', $default ) );
}

function bp_media_admin_setting_callback_document_section() {
	if ( ! extension_loaded( 'imagick' ) ) {
		?>
		<p class="alert">
		<?php
			echo sprintf(
				/* translators: 1: Imagick status */
				_x( 'Your server needs %1$s installed to enable live previews for PDF documents (optional). Ask your web host.', 'extension notification', 'buddyboss' ),
				'<code><a href="https://imagemagick.org/script/install-source.php" target="_blank">Imagick</a></code>'
			);
		?>
			</p>
		<?php
	}
}

function bp_media_settings_callback_extension_document_support() {

	$extensions = bp_document_extensions_list();
	$count      = count( $extensions ) + 1;
	?>
	<table class="extension-listing wp-list-table widefat fixed striped">
		<thead>
		<td class="ext-head ext-head-enable check-column"><input id="bp_select_extensions" type="checkbox" value="1"></td>
		<th class="ext-head ext-head-extension"><?php echo esc_html__( 'Extension', 'buddyboss' ); ?></th>
		<th class="ext-head ext-head-desc"><?php echo esc_html__( 'Description', 'buddyboss' ); ?></th>
		<th class="ext-head ext-head-icon"><?php echo esc_html__( 'Icon', 'buddyboss' ); ?></th>
		<th class="ext-head ext-head-mime"><?php echo esc_html__( 'MIME Type', 'buddyboss' ); ?></th>
		</thead>
		<tbody>
		<?php
		$counter = 1;
		foreach ( $extensions as $k => $extension ) {

			$k = ( ! empty( $k ) ? $k : $counter );

			$name       = 'bp_document_extensions_support[' . $k . ']';
			$edit       = ( isset( $extension['is_default'] ) && (int) $extension['is_default'] ) ? 'readonly="readonly"' : '';
			$class      = ( isset( $extension['is_default'] ) && (int) $extension['is_default'] ) ? 'hide-border' : '';
			$is_default = ( isset( $extension['is_default'] ) && (int) $extension['is_default'] ) ? 1 : 0;
			$tr_class   = ( isset( $extension['is_default'] ) && (int) $extension['is_default'] ) ? 'default-extension' : 'extra-extension custom-extension';

			if ( isset( $extension['icon'] ) && '' !== $extension['icon'] ) {
				$document_icon = $extension['icon'];
			} else {
				$document_file_extension = substr( strrchr( $extension['extension'], '.' ), 1 );
				$document_icon           = bp_document_svg_icon( $document_file_extension );
			}

			?>
			<tr class="document-extensions <?php echo esc_attr( $tr_class ); ?> <?php echo esc_attr( $k ); ?>">
				<td>
					<input class="extension-check" name="<?php echo esc_attr( $name . '[is_active]' ); ?>" id="<?php echo esc_attr( $name ); ?>" type="checkbox" value="1" <?php ( isset( $extension['is_active'] ) ) ? checked( (int) $extension['is_active'], 1 ) : ''; ?> />
				</td>
				<td data-colname="<?php esc_attr_e( 'Extension', 'buddyboss' ); ?>">
					<input class="<?php echo esc_attr( $class ); ?> extension-extension" <?php echo esc_attr( $edit ); ?> name="<?php echo esc_attr( $name . '[extension]' ); ?>" id="<?php echo esc_attr( $name ) . 'extension'; ?>" type="text" value="<?php echo ( isset( $extension['extension'] ) ) ? esc_attr( $extension['extension'] ) : ''; ?>" placeholder="<?php echo esc_html__( '.extension', 'buddyboss' ); ?>"/>
					<input <?php echo esc_attr( $edit ); ?> class="<?php echo esc_attr( $class ); ?> extension-hidden" name="<?php echo esc_attr( $name . '[is_default]' ); ?>" id="<?php echo esc_attr( $name ) . 'is_default'; ?>" type="hidden" value="<?php echo $is_default; ?>"/>
				</td>
				<td data-colname="<?php esc_attr_e( 'Description', 'buddyboss' ); ?>">
					<input class="<?php echo esc_attr( $class ); ?> extension-desc" <?php echo esc_attr( $edit ); ?> name="<?php echo esc_attr( $name . '[description]' ); ?>" id="<?php echo esc_attr( $name ) . 'desc'; ?>" type="text" value="<?php echo esc_attr( $extension['description'] ); ?>" placeholder="<?php echo esc_html__( 'description', 'buddyboss' ); ?>"/>
				</td>
				<td data-colname="<?php esc_attr_e( 'Icon', 'buddyboss' ); ?>">
					<?php
					if ( $is_default ) {
						echo '<i class="bb-icon-l ' . esc_attr( $document_icon ) . '"></i>';
					}
					if ( ! $is_default ) {
						?>
						<select class="extension-icon" name="<?php echo esc_attr( $name . '[icon]' ); ?>" data-name="<?php echo esc_attr( $name . '[icon]' ); ?>">
							<?php
							$icons = bp_document_svg_icon_list();
							foreach ( $icons as $icon ) {
								?>
								<option <?php selected( $icon['icon'], $extension['icon'] ); ?> value="<?php echo esc_attr( $icon['icon'] ); ?>"><?php echo esc_attr( $icon['title'] ); ?></option>
								<?php
							}
							?>
						</select>
						<?php
					} else {
						?>
						<input <?php echo esc_attr( $edit ); ?> name="<?php echo esc_attr( $name . '[icon]' ); ?>" id="<?php echo esc_attr( $name ) . 'icon'; ?>" type="hidden" value="<?php echo ( isset( $extension['icon'] ) && '' !== $extension['icon'] ) ? esc_attr( $extension['icon'] ) : $document_icon; ?>"/>
						<?php
					}
					?>
				</td>
				<td data-colname="<?php esc_attr_e( 'MIME Type', 'buddyboss' ); ?>">
					<input class="<?php echo esc_attr( $class ); ?> extension-mime" <?php echo esc_attr( $edit ); ?> name="<?php echo esc_attr( $name . '[mime_type]' ); ?>" id="<?php echo esc_attr( $name ) . 'mime'; ?>" type="text" value="<?php echo esc_attr( $extension['mime_type'] ); ?>" placeholder="<?php echo esc_html__( 'MIME type', 'buddyboss' ); ?>"/>
					<?php
					if ( ! $is_default ) {
						?>
						<a href="#" id="<?php echo esc_attr( $name . '[mime_type]' ); ?>" class="btn-check-mime-type button"><?php echo esc_html__( 'MIME Checker', 'buddyboss' ); ?></a>
						<span id="btn-remove-extensions" class="dashicons dashicons-dismiss"></span>
						<?php
					}
					?>
				</td>
			</tr>
			<?php
			$counter++;
		}

		$name = 'bp_document_extensions_support[1]';
		?>
		<tr style="display: none;" class="custom-extension-data">
			<td>
				<input value="1" name="extension-check" data-name="<?php echo esc_attr( $name . '[is_active]' ); ?>" type="checkbox" class="extension-check"/>
			</td>
			<td>
				<input name="extension-extension" data-name="<?php echo esc_attr( $name . '[extension]' ); ?>" type="text" class="extension-extension" placeholder="<?php echo esc_html__( '.extension', 'buddyboss' ); ?>"/>
				<input name="extension-hidden" data-name="<?php echo esc_attr( $name . '[is_default]' ); ?>" type="hidden" value="0" class="extension-hidden" />
			</td>
			<td>
				<input name="extension-desc" data-name="<?php echo esc_attr( $name . '[description]' ); ?>" type="text" class="extension-desc" placeholder="<?php echo esc_html__( 'description', 'buddyboss' ); ?>"/>
			</td>
			<td>
				<select class="extension-icon" name="extension-icon" data-name="<?php echo esc_attr( $name . '[icon]' ); ?>">
					<?php
					$icons = bp_document_svg_icon_list();
					foreach ( $icons as $icon ) {
						?>
						<option value="<?php echo esc_attr( $icon['icon'] ); ?>"><?php echo esc_attr( $icon['title'] ); ?></option>
						<?php
					}
					?>
				</select>
			</td>
			<td>
				<input name="extension-mime" data-name="<?php echo esc_attr( $name . '[mime_type]' ); ?>" type="text" value="" class="extension-mime" placeholder="<?php echo esc_html__( 'MIME type', 'buddyboss' ); ?>"/>
				<a href="#" id="" class="button btn-check-mime-type"><?php echo esc_html__( 'MIME Checker', 'buddyboss' ); ?></a>
				<span id="btn-remove-extensions" class="dashicons dashicons-dismiss"></span>
			</td>
		</tr>
		</tbody>
		<tfoot>
		<tr>
			<td colspan="5">
				<div id="btn-add-extensions" class="button-primary"><?php echo esc_html__( 'Add Extension', 'buddyboss' ); ?></div>
			</td>
		</tr>
		</tfoot>
	</table>
	<?php
}

function bp_media_settings_callback_extension_link() {

	printf(
		'<label>%s</label>',
		sprintf(
			__( '<a href="%s">Manage</a> which file extensions are allowed to be uploaded.', 'buddyboss' ),
			bp_get_admin_url(
				add_query_arg(
					array(
						'page' => 'bp-settings',
						'tab'  => 'bp-document',
					),
					'admin.php'
				)
			)
		)
	);
}

/**
 * Checks if extension support is enabled.
 *
 * @return array Is media extension support enabled or not
 * @since BuddyBoss 1.0.0
 */
function bp_document_extensions_list() {

	$default = bp_media_allowed_document_type();
	$saved   = bp_get_option( 'bp_document_extensions_support', $default );
	$merge   = array_merge( $default, $saved );
	$final   = array_unique( $merge, SORT_REGULAR );

	/**
	 * Filter to alllow the document extensions list.
	 *
	 * @param array $final List of extensions.
	 *
	 * @since BuddyBoss 1.7.0
	 */
	return apply_filters( 'bp_document_extensions_list', $final );
}

/**
 * Get the Document settings sections.
 *
 * @return array
 * @since BuddyBoss 1.0.0
 */
function bp_document_get_settings_sections() {

	return (array) apply_filters(
		'bp_document_get_settings_sections',
		array(
			'bp_document_settings_extensions' => array(
				'page'     => 'document',
				'title'    => sprintf(
					'<a href="%1$s" rel="nofollow">%2$s</a> %3$s',
					bp_get_admin_url(
						add_query_arg(
							array(
								'page' => 'bp-settings',
								'tab'  => 'bp-media#bp_media_settings_documents',
							),
							'admin.php'
						)
					),
					__( 'Documents', 'buddyboss' ),
					__( '&#8594; File Extensions', 'buddyboss' )
				),
				'callback' => 'bp_document_settings_callback_extension_section',
			),
		)
	);
}

/**
 * Get settings fields by section.
 *
 * @param string $section_id Section id.
 *
 * @return mixed False if section is invalid, array of fields otherwise.
 * @since BuddyBoss 1.0.0
 */
function bp_document_get_settings_fields_for_section( $section_id = '' ) {

	// Bail if section is empty.
	if ( empty( $section_id ) ) {
		return false;
	}

	$fields = bp_document_get_settings_fields();
	$retval = isset( $fields[ $section_id ] ) ? $fields[ $section_id ] : false;

	return (array) apply_filters( 'bp_document_get_settings_fields_for_section', $retval, $section_id );
}

/**
 * Get all of the settings fields.
 *
 * @return array
 * @since BuddyBoss 1.0.0
 */
function bp_document_get_settings_fields() {

	$fields = array();

	/** Document Extensions Section */
	$fields['bp_document_settings_extensions'] = array(

		'bp_document_extensions_support' => array(
			'title'             => __( 'Extensions', 'buddyboss' ),
			'callback'          => 'bp_media_settings_callback_extension_document_support',
			'sanitize_callback' => 'array',
			'args'              => array(
				'class' => 'document-extensions-listing',
			),
		),
	);

	return (array) apply_filters( 'bp_document_get_settings_fields', $fields );
}

/**
 * Component document helper text.
 *
 * @since BuddyBoss 1.0.0
 */
function bp_document_settings_callback_extension_section() {
	?>
	<p><?php esc_html_e( 'Check which file extensions are allowed to be uploaded. Add custom extensions at the bottom of the table.', 'buddyboss' ); ?></p>
	<?php
}

/**
 * Setting > Media > Photos > Allowed Max File Size
 *
 * @since BuddyBoss 1.4.8
 */
function bp_media_settings_callback_media_allowed_size() {
	$max_size    = bp_core_upload_max_size();
	$max_size_mb = bp_media_format_size_units( $max_size, false, 'MB' );
	?>
	<input type="number"
		   name="bp_media_allowed_size"
		   id="bp_media_allowed_size"
		   class="small-text"
		   min="1"
		   step="1"
		   max="<?php echo esc_attr( $max_size_mb ); ?>"
		   required
		   value="<?php echo esc_attr( bp_media_allowed_upload_media_size() ); ?>"
	/> <?php esc_html_e( 'MB', 'buddyboss' ); ?>
	<p class="description">
		<?php
		printf(
			'%1$s <strong>%2$s %3$s</strong>',
			__( 'Set a maximum file size for photo uploads, in megabytes. Your server\'s maximum upload size is ', 'buddyboss' ),
			$max_size_mb,
			'MB.'
		);
		?>
	</p>
	<?php
}

/**
 * Allowed upload file size for the media.
 *
 * @return int Allowed upload file size for the media.
 * @since BuddyBoss 1.4.8
 */
function bp_media_allowed_upload_media_size() {

	$max_size = bp_core_upload_max_size();
	$default  = bp_media_format_size_units( $max_size, false, 'MB' );
	return (int) apply_filters( 'bp_media_allowed_upload_media_size', (int) get_option( 'bp_media_allowed_size', $default ) );
}

/**
 * Setting > Media > Documents > Allowed Max File Size
 *
 * @since BuddyBoss 1.4.8
 */
function bp_media_settings_callback_document_allowed_size() {
	$max_size    = bp_core_upload_max_size();
	$max_size_mb = bp_document_format_size_units( $max_size, false, 'MB' );
	?>
	<input type="number"
		   name="bp_document_allowed_size"
		   id="bp_document_allowed_size"
		   class="small-text"
		   min="1"
		   step="1"
		   max="<?php echo esc_attr( $max_size_mb ); ?>"
		   required
		   value="<?php echo esc_attr( bp_media_allowed_upload_document_size() ); ?>"
	/> <?php esc_html_e( 'MB', 'buddyboss' ); ?>
	<p class="description">
		<?php
		printf(
			'%1$s <strong>%2$s %3$s</strong>',
			__( 'Set a maximum file size for document uploads, in megabytes. Your server\'s maximum upload size is ', 'buddyboss' ),
			$max_size_mb,
			'MB.'
		);
		?>
	</p>
	<?php
}

/**
 * Allowed upload file size for the document.
 *
 * @return int Allowed upload file size for the document.
 *
 * @since BuddyBoss 1.4.8
 */
function bp_media_allowed_upload_document_size() {
	$max_size = bp_core_upload_max_size();
	$default  = bp_document_format_size_units( $max_size, false, 'MB' );
	return (int) apply_filters( 'bp_media_allowed_upload_document_size', (int) get_option( 'bp_document_allowed_size', $default ) );
}

/**
 * Setting > Media > Photos > Allowed Per Batch
 *
 * @since BuddyBoss 1.5.6
 */
function bp_media_settings_callback_media_allowed_per_batch() {
	?>
	<input type="number"
		   name="bp_media_allowed_per_batch"
		   id="bp_media_allowed_per_batch"
		   class="small-text"
		   min="1"
		   value="<?php echo esc_attr( bp_media_allowed_upload_media_per_batch() ); ?>"
	/> <?php esc_html_e( 'per batch', 'buddyboss' ); ?>
	<p class="description">
		<?php
		_e( 'Set a maximum number of images that can be added to one activity post or photo upload.', 'buddyboss' )
		?>
	</p>
	<?php
}

/**
 * Allowed per batch for the media.
 *
 * @return int Allowed upload per batch for the media.
 * @since BuddyBoss 1.5.6
 */
function bp_media_allowed_upload_media_per_batch() {

	$default = apply_filters( 'bp_media_upload_chunk_limit', 10 );
	return (int) apply_filters( 'bp_media_allowed_upload_media_per_batch', (int) get_option( 'bp_media_allowed_per_batch', $default ) );
}

/**
 * Setting > Media > Documents > Allowed Per Batch
 *
 * @since BuddyBoss 1.5.6
 */
function bp_media_settings_callback_document_allowed_per_batch() {
	?>
	<input type="number"
		   name="bp_document_allowed_per_batch"
		   id="bp_document_allowed_per_batch"
		   class="small-text"
		   min="1"
		   value="<?php echo esc_attr( bp_media_allowed_upload_document_per_batch() ); ?>"
	/> <?php esc_html_e( 'per batch', 'buddyboss' ); ?>
	<p class="description">
		<?php
		_e( 'Set a maximum number of files that can be added to one activity post or document upload.', 'buddyboss' )
		?>
	</p>
	<?php
}

/**
 * Allowed per batch for the document.
 *
 * @return int Allowed per batch for the document.
 * @since BuddyBoss 1.5.6
 */
function bp_media_allowed_upload_document_per_batch() {

	/**
	 * Filter to allow document upload per batch.
	 *
	 * @param int $default Per batch.
	 *
	 * @since BuddyBoss 1.7.0
	 */
	$default = apply_filters( 'bp_document_upload_chunk_limit', 10 );

	/**
	 * Filter to allow document upload per batch.
	 *
	 * @param int $default Per batch.
	 *
	 * @since BuddyBoss 1.7.0
	 */
	return (int) apply_filters( 'bp_media_allowed_upload_document_per_batch', (int) get_option( 'bp_document_allowed_per_batch', $default ) );
}

/**
 * Get the Video settings sections.
 *
 * @return array
 * @since BuddyBoss 1.7.0
 */
function bp_video_get_settings_sections() {

	return (array) apply_filters(
		'bp_video_get_settings_sections',
		array(
			'bp_video_settings_extensions' => array(
				'page'     => 'video',
				'title'    => sprintf(
					'<a href="%1$s" rel="nofollow">%2$s</a> %3$s',
					bp_get_admin_url(
						add_query_arg(
							array(
								'page' => 'bp-settings',
								'tab'  => 'bp-media#bp_media_settings_videos',
							),
							'admin.php'
						)
					),
					__( 'Videos', 'buddyboss' ),
					__( '&#8594; File Extensions', 'buddyboss' )
				),
				'callback' => 'bp_video_settings_callback_extension_section',
			),
		)
	);
}

/**
 * Component video helper text.
 *
 * @since BuddyBoss 1.7.0
 */
function bp_video_settings_callback_extension_section() {
	?>
	<p><?php esc_html_e( 'Check which file extensions are allowed to be uploaded. Add custom extensions at the bottom of the table.', 'buddyboss' ); ?></p>
	<?php
}

/**
 * Get settings fields by section.
 *
 * @param string $section_id Section id.
 *
 * @return mixed False if section is invalid, array of fields otherwise.
 * @since BuddyBoss 1.7.0
 */
function bp_video_get_settings_fields_for_section( $section_id = '' ) {

	// Bail if section is empty.
	if ( empty( $section_id ) ) {
		return false;
	}

	$fields = bp_video_get_settings_fields();
	$retval = isset( $fields[ $section_id ] ) ? $fields[ $section_id ] : false;

	/**
	 * Filter for settings field sections.
	 *
	 * @param bool   $retval     Return value.
	 * @param string $section_id Section id.
	 *
	 * @since BuddyBoss 1.7.0
	 */
	return (array) apply_filters( 'bp_video_get_settings_fields_for_section', $retval, $section_id );
}

/**
 * Get all of the settings fields.
 *
 * @return array
 * @since BuddyBoss 1.7.0
 */
function bp_video_get_settings_fields() {

	$fields = array();

	/** Document Extensions Section */
	$fields['bp_video_settings_extensions'] = array(

		'bp_video_extensions_support' => array(
			'title'             => __( 'Extensions', 'buddyboss' ),
			'callback'          => 'bp_media_settings_callback_extension_video_support',
			'sanitize_callback' => 'array',
			'args'              => array(
				'class' => 'video-extensions-listing',
			),
		),
	);

	/**
	 * Filter for settings fields.
	 *
	 * @param array $fields Fields array.
	 *
	 * @since BuddyBoss 1.7.0
	 */
	return (array) apply_filters( 'bp_video_get_settings_fields', $fields );
}

/**
 * Checks if extension support is enabled.
 *
 * @return array Is video extension support enabled or not
 * @since BuddyBoss 1.7.0
 */
function bp_video_extensions_list() {
	return apply_filters( 'bp_video_extensions_list', bp_get_option( 'bp_video_extensions_support', bp_video_allowed_video_type() ) );
}

/**
 * Settings for the video support extensions lists.
 *
 * @since BuddyBoss 1.7.0
 */
function bp_media_settings_callback_extension_video_support() {

	$extensions = bp_video_extensions_list();
	$count      = count( $extensions ) + 1;
	?>
	<table class="extension-listing wp-list-table widefat fixed striped">
		<thead>
		<td class="ext-head ext-head-enable check-column"><input id="bp_select_extensions" type="checkbox" value="1">
		</td>
		<th class="ext-head ext-head-extension"><?php esc_html_e( 'Extension', 'buddyboss' ); ?></th>
		<th class="ext-head ext-head-desc"><?php esc_html_e( 'Description', 'buddyboss' ); ?></th>
		<th class="ext-head ext-head-mime"><?php esc_html_e( 'MIME Type', 'buddyboss' ); ?></th>
		</thead>
		<tbody>
		<?php
		$counter = 1;
		foreach ( $extensions as $k => $extension ) {

			$k = ( ! empty( $k ) ? $k : $counter );

			$name       = 'bp_video_extensions_support[' . $k . ']';
			$edit       = ( isset( $extension['is_default'] ) && (int) $extension['is_default'] ) ? 'readonly="readonly"' : '';
			$class      = ( isset( $extension['is_default'] ) && (int) $extension['is_default'] ) ? 'hide-border' : '';
			$is_default = ( isset( $extension['is_default'] ) && (int) $extension['is_default'] ) ? 1 : 0;
			$tr_class   = ( isset( $extension['is_default'] ) && (int) $extension['is_default'] ) ? 'default-extension' : 'extra-extension custom-extension';

			?>
			<tr class="video-extensions <?php echo esc_attr( $tr_class ); ?> <?php echo esc_attr( $k ); ?>">
				<td>
					<input class="extension-check" name="<?php echo esc_attr( $name . '[is_active]' ); ?>" id="<?php echo esc_attr( $name ); ?>" type="checkbox" value="1" <?php ( isset( $extension['is_active'] ) ) ? checked( (int) $extension['is_active'], 1 ) : ''; ?> />
				</td>
				<td data-colname="<?php esc_attr_e( 'Extension', 'buddyboss' ); ?>">
					<input class="<?php echo esc_attr( $class ); ?> extension-extension" <?php echo esc_attr( $edit ); ?> name="<?php echo esc_attr( $name . '[extension]' ); ?>" id="<?php echo esc_attr( $name ) . 'extension'; ?>" type="text" value="<?php echo ( isset( $extension['extension'] ) ) ? esc_attr( $extension['extension'] ) : ''; ?>" placeholder="<?php echo esc_html__( '.extension', 'buddyboss' ); ?>"/>
					<input <?php echo esc_attr( $edit ); ?> class="<?php echo esc_attr( $class ); ?> extension-hidden" name="<?php echo esc_attr( $name . '[is_default]' ); ?>" id="<?php echo esc_attr( $name ) . 'is_default'; ?>" type="hidden" value="<?php echo esc_attr( $is_default ); ?>"/>
				</td>
				<td data-colname="<?php esc_attr_e( 'Description', 'buddyboss' ); ?>">
					<input class="<?php echo esc_attr( $class ); ?> extension-desc" <?php echo esc_attr( $edit ); ?> name="<?php echo esc_attr( $name . '[description]' ); ?>" id="<?php echo esc_attr( $name ) . 'desc'; ?>" type="text" value="<?php echo esc_attr( $extension['description'] ); ?>" placeholder="<?php echo esc_html__( 'description', 'buddyboss' ); ?>"/>
				</td>
				<td data-colname="<?php esc_attr_e( 'MIME Type', 'buddyboss' ); ?>">
					<input class="<?php echo esc_attr( $class ); ?> extension-mime" <?php echo esc_attr( $edit ); ?> name="<?php echo esc_attr( $name . '[mime_type]' ); ?>" id="<?php echo esc_attr( $name ) . 'mime'; ?>" type="text" value="<?php echo esc_attr( $extension['mime_type'] ); ?>" placeholder="<?php echo esc_html__( 'MIME type', 'buddyboss' ); ?>"/>
					<?php
					if ( ! $is_default ) {
						?>
						<a href="#" id="<?php echo esc_attr( $name . '[mime_type]' ); ?>" class="btn-check-mime-type button"><?php echo esc_html__( 'MIME Checker', 'buddyboss' ); ?></a>
						<span id="btn-remove-extensions" class="dashicons dashicons-dismiss"></span>
						<?php
					}
					?>
				</td>
			</tr>
			<?php
			$counter ++;
		}

		$name = 'bp_video_extensions_support[1]';
		?>
		<tr style="display: none;" class="custom-extension-data">
			<td>
				<input value="1" name="extension-check" data-name="<?php echo esc_attr( $name . '[is_active]' ); ?>" type="checkbox" class="extension-check"/>
			</td>
			<td>
				<input name="extension-extension" data-name="<?php echo esc_attr( $name . '[extension]' ); ?>" type="text" class="extension-extension" placeholder="<?php echo esc_html__( '.extension', 'buddyboss' ); ?>"/>
				<input name="extension-hidden" data-name="<?php echo esc_attr( $name . '[is_default]' ); ?>" type="hidden" value="0" class="extension-hidden"/>
			</td>
			<td>
				<input name="extension-desc" data-name="<?php echo esc_attr( $name . '[description]' ); ?>" type="text" class="extension-desc" placeholder="<?php echo esc_html__( 'description', 'buddyboss' ); ?>"/>
			</td>
			<td>
				<input name="extension-mime" data-name="<?php echo esc_attr( $name . '[mime_type]' ); ?>" type="text" value="" class="extension-mime" placeholder="<?php echo esc_html__( 'MIME type', 'buddyboss' ); ?>"/>
				<a href="#" id="" class="button btn-check-mime-type"><?php echo esc_html__( 'MIME Checker', 'buddyboss' ); ?></a>
				<span id="btn-remove-extensions" class="dashicons dashicons-dismiss"></span>
			</td>
		</tr>
		</tbody>
		<tfoot>
		<tr>
			<td colspan="4">
				<div id="btn-add-video-extensions" class="button-primary"><?php echo esc_html__( 'Add Extension', 'buddyboss' ); ?></div>
			</td>
		</tr>
		</tfoot>
	</table>
	<?php
}

/**
 * Setting > Media > Media Miscellaneous
 *
 * @since BuddyBoss 1.7.0
 */
function bb_media_settings_callback_symlink_support() {

	?>
	<input name="bp_media_symlink_support" id="bp_media_symlink_support" type="checkbox" value="1" <?php checked( bb_enable_symlinks() ); ?> />
	<label for="bp_media_symlink_support">
		<?php esc_html_e( 'Enable symbolic links. If you are having issues with media display, try disabling this option.', 'buddyboss' ); ?>
	</label>

	<?php
	$has_error = false;
	if ( true === bb_check_server_disabled_symlink() ) {
		bp_update_option( 'bp_media_symlink_support', 0 );
		$has_error = true;
		?>
		<div class="bp-messages-feedback">
			<div class="bp-feedback warning">
				<span class="bp-icon" aria-hidden="true"></span>
				<p><?php esc_html_e( 'Symbolic function disabled on your server. Please contact your hosting provider.', 'buddyboss' ); ?></p>
			</div>
		</div>
		<?php
	}

	if ( empty( $has_error ) && bb_enable_symlinks() && empty( bp_get_option( 'bb_media_symlink_type' ) ) ) {
		?>
		<div class="bp-messages-feedback">
			<div class="bp-feedback warning">
				<span class="bp-icon" aria-hidden="true"></span>
				<p><?php esc_html_e( 'Symbolic links don\'t seem to work on your server. Please contact BuddyBoss for support.', 'buddyboss' ); ?></p>
			</div>
		</div>
		<?php
	}

	if ( true === (bool) bp_get_option( 'bb_display_support_error' ) ) {
		?>
		<div class="bp-messages-feedback">
			<div class="bp-feedback warning">
				<span class="bp-icon" aria-hidden="true"></span>
				<p><?php esc_html_e( 'Symbolic links don\'t seem to work on your server. Please contact BuddyBoss for support.', 'buddyboss' ); ?></p>
			</div>
		</div>
		<?php
	}

	if ( empty( bb_enable_symlinks() ) ) {
		?>
		<div class="bp-messages-feedback">
			<div class="bp-feedback warning">
				<span class="bp-icon" aria-hidden="true"></span>
				<p><?php esc_html_e( 'Symbolic links are disabled', 'buddyboss' ); ?></p>
			</div>
		</div>
		<?php
	} elseif ( ! $has_error && bb_enable_symlinks() ) {
		?>
		<div class="bp-messages-feedback">
			<div class="bp-feedback success">
				<span class="bp-icon" aria-hidden="true"></span>
				<p><?php esc_html_e( 'Symbolic links are activated', 'buddyboss' ); ?></p>
			</div>
		</div>
		<?php
	}
	?>

	<p class="description"><?php esc_html_e( 'Symbolic links are used to create "shortcuts" to media files uploaded by members, providing optimal security and performance. If symbolic links are disabled, a fallback method will be used to protect your media files.', 'buddyboss' ); ?></p>

	<?php
}

/**
 * Setting > Media > Media Miscellaneous
 *
 * @since BuddyBoss 1.7.0
 */
function bb_media_settings_callback_symlink_direct_access() {

	$get_sample_ids         = array();
	$video_attachment_id    = 0;
	$media_attachment_id    = 0;
	$document_attachment_id = 0;
	$bypass_check           = apply_filters( 'bb_media_check_default_access', 0 );

	if ( ! $bypass_check ) {

		// Add upload filters.
		add_filter( 'upload_dir', 'bp_video_upload_dir_script' );
		$file        = buddypress()->plugin_dir . 'bp-core/images/suspended-mystery-man.jpg';
		$filename    = basename( $file );
		$upload_file = wp_upload_bits( $filename, null, file_get_contents( $file ) );

		if ( ! $upload_file['error'] ) {
			$wp_filetype         = wp_check_filetype( $filename, null );
			$attachment          = array(
				'post_mime_type' => $wp_filetype['type'],
				'post_title'     => preg_replace( '/\.[^.]+$/', '', $filename ),
				'post_content'   => '',
				'post_status'    => 'inherit',
			);
			$video_attachment_id = wp_insert_attachment( $attachment, $upload_file['file'] );
			if ( ! is_wp_error( $video_attachment_id ) ) {
				require_once ABSPATH . 'wp-admin/includes/image.php';
				$attachment_data = wp_generate_attachment_metadata( $video_attachment_id, $upload_file['file'] );
				wp_update_attachment_metadata( $video_attachment_id, $attachment_data );
				$get_sample_ids['bb_videos'] = $video_attachment_id;
			}
		}
		// Remove upload filters.
		remove_filter( 'upload_dir', 'bp_video_upload_dir_script' );

		add_filter( 'upload_dir', 'bp_media_upload_dir_script' );
		$file        = buddypress()->plugin_dir . 'bp-core/images/suspended-mystery-man.jpg';
		$filename    = basename( $file );
		$upload_file = wp_upload_bits( $filename, null, file_get_contents( $file ) );

		if ( ! $upload_file['error'] ) {
			$wp_filetype         = wp_check_filetype( $filename, null );
			$attachment          = array(
				'post_mime_type' => $wp_filetype['type'],
				'post_title'     => preg_replace( '/\.[^.]+$/', '', $filename ),
				'post_content'   => '',
				'post_status'    => 'inherit',
			);
			$media_attachment_id = wp_insert_attachment( $attachment, $upload_file['file'] );
			if ( ! is_wp_error( $media_attachment_id ) ) {
				require_once ABSPATH . 'wp-admin/includes/image.php';
				$attachment_data = wp_generate_attachment_metadata( $media_attachment_id, $upload_file['file'] );
				wp_update_attachment_metadata( $media_attachment_id, $attachment_data );
				$get_sample_ids['bb_medias'] = $media_attachment_id;
			}
		}
		remove_filter( 'upload_dir', 'bp_media_upload_dir_script' );

		add_filter( 'upload_dir', 'bp_document_upload_dir_script' );
		$file        = buddypress()->plugin_dir . 'bp-core/images/suspended-mystery-man.jpg';
		$filename    = basename( $file );
		$upload_file = wp_upload_bits( $filename, null, file_get_contents( $file ) );

		if ( ! $upload_file['error'] ) {
			$wp_filetype            = wp_check_filetype( $filename, null );
			$attachment             = array(
				'post_mime_type' => $wp_filetype['type'],
				'post_title'     => preg_replace( '/\.[^.]+$/', '', $filename ),
				'post_content'   => '',
				'post_status'    => 'inherit',
			);
			$document_attachment_id = wp_insert_attachment( $attachment, $upload_file['file'] );
			if ( ! is_wp_error( $document_attachment_id ) ) {
				require_once ABSPATH . 'wp-admin/includes/image.php';
				$attachment_data = wp_generate_attachment_metadata( $document_attachment_id, $upload_file['file'] );
				wp_update_attachment_metadata( $document_attachment_id, $attachment_data );
				$get_sample_ids['bb_documents'] = $document_attachment_id;
			}
		}
		remove_filter( 'upload_dir', 'bp_document_upload_dir_script' );

		$directory = array();
		foreach ( $get_sample_ids as $id => $v ) {
			$fetch = wp_remote_get( wp_get_attachment_image_url( $v ) );
			if ( ! is_wp_error( $fetch ) && isset( $fetch['response']['code'] ) && 200 === $fetch['response']['code'] ) {
				$directory[] = $id;
			}
		}

		$directory = apply_filters( 'bb_media_settings_callback_symlink_direct_access', $directory, $get_sample_ids );

		if ( ! empty( $directory ) ) {

			printf(
				'<div class="bp-messages-feedback"><div class="bp-feedback warning"><span class="bp-icon" aria-hidden="true"></span><p>%s</p></div></div>',
				esc_html__( 'Direct access to your media files and folders is not blocked', 'buddyboss' )
			);

		} else {
			printf(
				'<div class="bp-messages-feedback"><div class="bp-feedback success"><span class="bp-icon" aria-hidden="true"></span><p>%s</p></div></div>',
				esc_html__( 'Direct access to your media files and folders is blocked', 'buddyboss' )
			);
		}
	} else {
		printf(
			'<div class="bp-messages-feedback"><div class="bp-feedback success"><span class="bp-icon" aria-hidden="true"></span><p>%s</p></div></div>',
			esc_html__( 'Direct access to your media files and folders is blocked', 'buddyboss' )
		);
	}

	printf(
		'<p class="description"><p>%s <a href="%s">%s</a> %s</p></p>',
		esc_html__( 'If our plugin is unable to automatically block direct access to your media files and folders, please follow the steps in our ', 'buddyboss' ),
		esc_url( 'https://www.buddyboss.com/resources/docs/components/media/media-permissions/' ),
		esc_html__( 'Media Permissions', 'buddyboss' ),
		esc_html__( ' tutorial to configure your server.', 'buddyboss' )
	);

	if ( 0 !== $document_attachment_id && ! is_wp_error( $document_attachment_id ) ) {
		wp_delete_attachment( $document_attachment_id, true );
	}

	if ( 0 !== $media_attachment_id && ! is_wp_error( $media_attachment_id ) ) {
		wp_delete_attachment( $media_attachment_id, true );
	}

	if ( 0 !== $video_attachment_id && ! is_wp_error( $video_attachment_id ) ) {
		wp_delete_attachment( $video_attachment_id, true );
	}
}
