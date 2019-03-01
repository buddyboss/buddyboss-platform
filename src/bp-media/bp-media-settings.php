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

	if ( bp_is_active( 'groups' ) ) {
		$settings['bp_media_settings_groups'] = array(
			'page'  => 'media',
			'title' => __( 'Groups', 'buddyboss' ),
		);
	}

	if ( bp_is_active( 'forums' ) ) {
		$settings['bp_media_settings_forums'] = array(
			'page'  => 'media',
			'title' => __( 'Forums', 'buddyboss' ),
		);
	}

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

		'bp_media_user_photos_slug' => [
			'title'             => __( 'User Photos Slug', 'buddyboss' ),
			'callback'          => 'bp_media_settings_callback_user_photos_slug',
			'sanitize_callback' => 'string',
		],

		'bp_media_all_media_page' => [
			'title'             => __( 'Global Photos Page', 'buddyboss' ),
			'callback'          => 'bp_media_settings_callback_all_media_page',
			'sanitize_callback' => 'string',
		],

		'bp_media_activity_photo_size' => [
			'title'             => __( 'Activity Photo Size', 'buddyboss' ),
			'callback'          => 'bp_media_settings_callback_activity_photo_size',
			'sanitize_callback' => 'string',
		],

		'bp_media_activity_photo_layout' => [
			'title'             => __( 'Photo Layout', 'buddyboss' ),
			'callback'          => 'bp_media_settings_callback_activity_photo_layout',
			'sanitize_callback' => 'string',
		],

		'bp_media_enable_tagging' => [
			'title'             => __( 'Friend Tagging', 'buddyboss' ),
			'callback'          => 'bp_media_settings_callback_enable_tagging',
			'sanitize_callback' => 'absint',
		],

		'bp_media_files_per_batch' => [
			'title'             => __( 'Files Per Batch', 'buddyboss' ),
			'callback'          => 'bp_media_settings_callback_files_per_batch',
			'sanitize_callback' => 'absint',
		],

		'bp_media_files_rotation_fix' => [
			'title'             => __( 'Mobile Rotation Fix', 'buddyboss' ),
			'callback'          => 'bp_media_settings_callback_files_rotation_fix',
			'sanitize_callback' => 'absint',
		],

		'bp_media_delete_media_permanently' => [
			'title'             => __( 'Media Management', 'buddyboss' ),
			'callback'          => 'bp_media_settings_callback_delete_media_permanently',
			'sanitize_callback' => 'absint',
		],

		'bp_media_enable_js_debug' => [
			'title'             => __( 'Enable Unminified JS', 'buddyboss' ),
			'callback'          => 'bp_media_settings_callback_enable_js_debug',
			'sanitize_callback' => 'absint',
		],

		'bp_media_show_uploadbox' => [
			'title'             => __( 'Disable Upload Box', 'buddyboss' ),
			'callback'          => 'bp_media_settings_callback_show_uploadbox',
			'sanitize_callback' => 'absint',
		],

	];

	if ( bp_is_active( 'groups' ) ) {
		$fields['bp_media_settings_groups'] = [

			'bp_media_group_media_support' => [
				'title'             => __( 'Group Media', 'buddyboss' ),
				'callback'          => 'bp_media_settings_callback_group_media_support',
				'sanitize_callback' => 'absint',
			],

			'bp_media_group_albums' => [
				'title'             => __( 'Group Albums', 'buddyboss' ),
				'callback'          => 'bp_media_settings_callback_group_albums',
				'sanitize_callback' => 'absint',
			],
		];
	}

	if ( bp_is_active( 'forums' ) ) {
		$fields['bp_media_settings_forums'] = [

			'bp_media_forums_media_support' => [
				'title'             => __( 'Forums Media', 'buddyboss' ),
				'callback'          => 'bp_media_settings_callback_forums_media_support',
				'sanitize_callback' => 'absint',
			],

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
 * user slug setting field
 *
 * @since BuddyBoss 1.0.0
 */
function bp_media_settings_callback_user_photos_slug() {
	?>
    <input name="bp_media_user_photos_slug" id="bp_media_user_photos_slug" type="text" value="<?php bp_media_form_option( 'bp_media_user_photos_slug', 'photos' ); ?>"/>
    <p class="description"><?php _e( 'Example: ', 'buddyboss' ) ?><a href="<?php echo bp_loggedin_user_domain() . bp_media_get_form_option( 'bp_media_user_photos_slug', 'photos' ); ?>"><?php echo bp_loggedin_user_domain(); ?><strong><?php echo bp_media_form_option( 'bp_media_user_photos_slug', 'photos' ); ?></strong>/</a></p>
	<?php
}

/**
 * all media page setting field
 *
 * @since BuddyBoss 1.0.0
 */
function bp_media_settings_callback_all_media_page() {

	$all_media_page = bp_media_get_form_option( 'bp_media_all_media_page' );

	wp_dropdown_pages( array(
		'name'             => 'bp_media_all_media_page',
		'show_option_none' => __( '- None -', 'buddyboss' ),
		'selected'         => $all_media_page,
	) );
	?>
    <a href="<?php echo admin_url( esc_url( add_query_arg( array( 'post_type' => 'page' ), 'post-new.php' ) ) ); ?>"
       class="button-secondary"><?php _e( 'New Page', 'buddyboss' ); ?></a>
	<?php
	if ( ! empty( $all_media_page ) ) {
		?><a href="<?php echo get_permalink( $all_media_page ); ?>" class="button-secondary" target="_bp"
             style="margin-left: 5px;"><?php _e( 'View', 'buddyboss' ); ?></a><?php
	}
	?>
    <p class="description"><?php _e( 'Use a WordPress page to display all media uploaded by all users.<br /> You may need to reset your permalinks after changing this setting. Go to Settings > Permalinks.', 'buddyboss' ); ?></p>
	<?php
}

/**
 * Setting > Media > activity photo size
 *
 * @since BuddyBoss 1.0.0
 */
function bp_media_settings_callback_activity_photo_size() {

	$activity_photo_size = bp_media_get_form_option( 'bp_media_activity_photo_size' );

	echo '<select name="bp_media_activity_photo_size" id="bp_media_activity_photo_size">';

	$options = array(
		'medium'                     => __( 'Medium', 'buddyboss' ),
		'buddyboss_media_photo_wide' => __( 'Large', 'buddyboss' ),
	);
	foreach ( $options as $option => $label ) {
		$selected = $option == $activity_photo_size ? ' selected' : '';
		echo '<option value="' . esc_attr( $option ) . '" ' . $selected . '>' . $label . '</option>';
	}

	echo '</select>';

	echo '<p class="description">' . __( 'Image size displayed in activity posts.', 'buddyboss' ) . '</p>';
}

/**
 * Setting > Media > activity photo layout
 *
 * @since BuddyBoss 1.0.0
 */
function bp_media_settings_callback_activity_photo_layout() {

	$activity_photo_layout = bp_media_get_form_option( 'bp_media_activity_photo_layout' );

	if ( ! $activity_photo_layout ) {
		$activity_photo_layout = 'yes';
	}

	$options = array(
		'yes' => __( 'Grid', 'buddyboss' ),
		'no'  => __( 'Activity Posts', 'buddyboss' )
	);
	foreach ( $options as $option => $label ) {
		$checked = $activity_photo_layout == $option ? ' checked' : '';
		echo '<input type="radio" name="bp_media_activity_photo_layout" value="' . $option . '" ' . $checked . '>' . $label . '&nbsp;&nbsp;';
	}

	echo '<p class="description">' . __( 'In your albums, you can display photos in a grid or as activity posts.', 'buddyboss' ) . '</p>';
}

/**
 * Setting > Media > activity photo layout
 *
 * @since BuddyBoss 1.0.0
 */
function bp_media_settings_callback_enable_tagging() {
    ?>
    <input name="bp_media_enable_tagging"
            id="bp_media_enable_tagging"
            type="checkbox"
            value="1"
		<?php checked( bp_is_media_tagging_enabled( true ) ) ?>
    />
    <label for="bp_media_enable_tagging">
		<?php esc_html_e( 'Enable Tagging', 'buddyboss' ) ?>
    </label>
    <p class="description"><?php _e( 'Allow members to tag friends in media uploads.', 'buddyboss' ); ?></p>
    <?php
}

/**
 * Checks if media tagging is enabled.
 *
 * @since BuddyBoss 1.0.0
 *
 * @param $default integer
 *
 * @return bool Is media tagging enabled or not
 */
function bp_is_media_tagging_enabled( $default = 1 ) {
	return (bool) apply_filters( 'bp_is_media_tagging_enabled', (bool) get_option( 'bp_media_enable_tagging', $default ) );
}

/**
 * Setting > Media > Files Per Batch
 *
 * @since BuddyBoss 1.0.0
 */
function bp_media_settings_callback_files_per_batch() {
	$files_per_batch = bp_media_get_form_option( 'bp_media_files_per_batch' );
	if ( ! $files_per_batch ) {
		$files_per_batch = 4;
	}

	echo "<input id='bp_media_files_per_batch' name='bp_media_files_per_batch' min='1' type='number' value='" . esc_attr( $files_per_batch ) . "' />";
	echo '<p class="description">' . __( 'Maximum number of images that can be uploaded in one batch.', 'buddyboss' ) . '</p>';
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
		<?php checked( bp_is_media_delete_enabled() ) ?>
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
 * Setting > Media > Load minified version of the js
 *
 * @since BuddyBoss 1.0.0
 */
function bp_media_settings_callback_enable_js_debug() {
	?>
    <input name="bp_media_enable_js_debug"
           id="bp_media_enable_js_debug"
           type="checkbox"
           value="1"
		<?php checked( bp_is_media_js_debug_enabled() ) ?>
    />
    <label for="bp_media_enable_js_debug">
		<?php esc_html_e( 'Load the Unminified version of Javascript, for compatibility with certain plugins', 'buddyboss' ) ?>
    </label>
	<?php
}

/**
 * Checks if media js debug is enabled.
 *
 * @since BuddyBoss 1.0.0
 *
 * @param $default integer
 *
 * @return bool Is media js debug enabled or not
 */
function bp_is_media_js_debug_enabled( $default = 0 ) {
	return (bool) apply_filters( 'bp_is_media_js_debug_enabled', (bool) get_option( 'bp_media_enable_js_debug', $default ) );
}

/**
 * Setting > Media > Show light box
 *
 * @since BuddyBoss 1.0.0
 */
function bp_media_settings_callback_show_uploadbox() {
	?>
    <input name="bp_media_show_uploadbox"
           id="bp_media_show_uploadbox"
           type="checkbox"
           value="1"
		<?php checked( bp_is_media_show_uploadbox_enabled() ) ?>
    />
    <label for="bp_media_show_uploadbox">
		<?php esc_html_e( 'When adding a photo, use browser\'s default file selector instead of our popup upload box', 'buddyboss' ) ?>
    </label>
	<?php
}

/**
 * Checks if media show uploadbox is enabled.
 *
 * @since BuddyBoss 1.0.0
 *
 * @param $default integer
 *
 * @return bool Is media show uploadbox enabled or not
 */
function bp_is_media_show_uploadbox_enabled( $default = 1 ) {
	return (bool) apply_filters( 'bp_is_media_show_uploadbox_enabled', (bool) get_option( 'bp_media_show_uploadbox', $default ) );
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
		<?php checked( bp_is_media_forums_media_support_enabled() ) ?>
    />
    <label for="bp_media_forums_media_support">
		<?php esc_html_e( 'Allow photo posting in bbPress groups and forums', 'buddyboss' ) ?>
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
function bp_is_media_forums_media_support_enabled( $default = 1 ) {
	return (bool) apply_filters( 'bp_is_media_forums_media_support_enabled', (bool) get_option( 'bp_media_forums_media_support', $default ) );
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
		<?php checked( bp_is_media_group_media_support_enabled() ) ?>
    />
    <label for="bp_media_group_media_support">
		<?php esc_html_e( 'Allow photo posting in BuddyPress group activity updates and comments', 'buddyboss' ) ?>
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
function bp_is_media_group_media_support_enabled( $default = 1 ) {
	return (bool) apply_filters( 'bp_is_media_group_media_support_enabled', (bool) get_option( 'bp_media_group_media_support', $default ) );
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
		<?php checked( bp_is_media_group_album_support_enabled() ) ?>
    />
    <label for="bp_media_group_albums">
		<?php esc_html_e( 'Enable BuddyPress group photo albums', 'buddyboss' ) ?>
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
function bp_is_media_group_album_support_enabled( $default = 1 ) {
	return (bool) apply_filters( 'bp_is_media_group_album_support_enabled', (bool) get_option( 'bp_media_group_albums', $default ) );
}

/**
 * Setting > Media > Rotation Fixer Enabled
 *
 * @since BuddyBoss 1.0.0
 */
function bp_media_settings_callback_files_rotation_fix() {
	$memory_limit = @ini_get( 'memory_limit' );

	if ( empty( $memory_limit ) ) {
		$memory_limit = 'N/A';
	}
    ?>
    <input name="bp_media_files_rotation_fix"
           id="bp_media_files_rotation_fix"
           type="checkbox"
           value="1"
		<?php checked( bp_is_media_files_rotation_fix_enabled() ) ?>
    />
    <label for="bp_media_group_albums">
		<?php esc_html_e( 'Enable fix for mobile uploads rotating', 'buddyboss' ) ?>
    </label>
    <p class="description">
        <?php _e( 'It\'s recommended that you have at least 256M-512M of RAM allocated to PHP, otherwise photo uploads may fail.', 'buddyboss' ); ?>
        <br/>
        <?php _e( 'Your current memory limit is ', 'buddyboss' ); ?>
        <strong><?php echo $memory_limit; ?></strong>
        <?php _e( 'You can contact your web host to increase the memory limit.', 'buddyboss' ); ?>
    </p>
	<?php
}

/**
 * Checks if media files rotation fix is enabled.
 *
 * @since BuddyBoss 1.0.0
 *
 * @param $default integer
 *
 * @return bool Is media files rotation fix enabled or not
 */
function bp_is_media_files_rotation_fix_enabled( $default = 0 ) {
	return (bool) apply_filters( 'bp_is_media_files_rotation_fix_enabled', (bool) get_option( 'bp_media_files_rotation_fix', $default ) );
}