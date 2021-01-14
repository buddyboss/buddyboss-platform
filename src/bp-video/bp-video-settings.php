<?php
/**
 * Video Settings
 *
 * @package BuddyBoss\Video
 * @since BuddyBoss 1.5.7
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Setting > Media > Video > Profile support
 *
 * @since BuddyBoss 1.5.7
 */
function bp_video_settings_callback_profile_video_support() {
	?>
	<input name="bp_video_profile_video_support"
		   id="bp_video_profile_video_support"
		   type="checkbox"
		   value="1"
		<?php checked( bp_is_profile_video_support_enabled() ); ?>
	/>
	<label for="bp_video_profile_video_support">
		<?php
		if ( bp_is_active( 'activity' ) ) {
			_e( 'Allow members to upload videos in <strong>profiles</strong> and <strong>profile activity</strong>', 'buddyboss' );
		} else {
			_e( 'Allow members to upload videos in <strong>profiles</strong>', 'buddyboss' );
		}
		?>
	</label>
	<?php
}

/**
 * Checks if profile video support is enabled.
 *
 * @param $default integer
 *
 * @return bool Is profile video support enabled or not
 * @since BuddyBoss 1.5.7
 */
function bp_is_profile_video_support_enabled( $default = 0 ) {
	return (bool) apply_filters( 'bp_is_profile_video_support_enabled', (bool) get_option( 'bp_video_profile_video_support', $default ) );
}

/**
 * Checks if profile albums support is enabled.
 *
 * @param $default integer
 *
 * @return bool Is profile albums support enabled or not
 * @since BuddyBoss 1.5.7
 */
function bp_is_profile_video_albums_support_enabled( $default = 0 ) {
	return (bool) apply_filters( 'bp_is_profile_video_albums_support_enabled', (bool) get_option( 'bp_video_profile_albums_support', $default ) );
}

/**
 * Setting > Media > Video > Groups support
 *
 * @since BuddyBoss 1.5.7
 */
function bp_video_settings_callback_group_video_support() {
	?>
	<input name="bp_video_group_video_support"
		   id="bp_video_group_video_support"
		   type="checkbox"
		   value="1"
		<?php checked( bp_is_group_video_support_enabled() ); ?>
	/>
	<label for="bp_video_group_video_support">
		<?php
		if ( bp_is_active( 'activity' ) ) {
			_e( 'Allow members to upload videos in <strong>groups</strong> and <strong>group activity</strong>', 'buddyboss' );
		} else {
			_e( 'Allow members to upload videos in <strong>groups</strong>', 'buddyboss' );
		}
		?>
	</label>
	<?php
}

/**
 * Checks if group video support is enabled.
 *
 * @param $default integer
 *
 * @return bool Is group video support enabled or not
 * @since BuddyBoss 1.5.7
 */
function bp_is_group_video_support_enabled( $default = 0 ) {
	return (bool) apply_filters( 'bp_is_group_video_support_enabled', (bool) get_option( 'bp_video_group_video_support', $default ) );
}

/**
 * Checks if group album support is enabled.
 *
 * @param $default integer
 *
 * @return bool Is group album support enabled or not
 * @since BuddyBoss 1.5.7
 */
function bp_is_group_video_albums_support_enabled( $default = 0 ) {
	return (bool) apply_filters( 'bp_is_group_video_albums_support_enabled', (bool) get_option( 'bp_video_group_albums_support', $default ) );
}

/**
 * Setting > Media > Video > Messages support
 *
 * @since BuddyBoss 1.5.7
 */
function bp_video_settings_callback_messages_video_support() {
	?>
	<input name="bp_video_messages_video_support" id="bp_video_messages_video_support" type="checkbox" value="1"
		<?php checked( bp_is_messages_video_support_enabled() ); ?>
	/>
	<label for="bp_video_messages_video_support">
		<?php
		if ( true === bp_disable_group_messages() ) {
			_e( 'Allow members to upload videos in <strong>private messages</strong> and <strong>group messages</strong>', 'buddyboss' );
		} else {
			_e( 'Allow members to upload videos in <strong>private messages</strong>', 'buddyboss' );
		}
		?>
	</label>
	<?php
}

/**
 * Checks if messages video support is enabled.
 *
 * @param int $default default value.
 *
 * @return bool Is messages video support enabled or not
 * @since BuddyBoss 1.5.7
 */
function bp_is_messages_video_support_enabled( $default = 0 ) {
	return (bool) apply_filters( 'bp_is_messages_video_support_enabled', (bool) get_option( 'bp_video_messages_video_support', $default ) );
}

/**
 * Setting > Media > Video > Forums support
 *
 * @since BuddyBoss 1.5.7
 */
function bp_video_settings_callback_forums_video_support() {
	?>
	<input name="bp_video_forums_video_support" id="bp_video_forums_video_support" type="checkbox" value="1"
		<?php checked( bp_is_forums_video_support_enabled() ); ?>
	/>
	<label for="bp_video_forums_video_support">
		<?php _e( 'Allow members to upload videos in <strong>forum discussions</strong>', 'buddyboss' ); // phpcs:ignore ?>
	</label>
	<?php
}

/**
 * Checks if forums video support is enabled.
 *
 * @param int $default default value.
 *
 * @return bool Is forums video support enabled or not
 * @since BuddyBoss 1.5.7
 */
function bp_is_forums_video_support_enabled( $default = 0 ) {
	return (bool) apply_filters( 'bp_is_forums_video_support_enabled', (bool) get_option( 'bp_video_forums_video_support', $default ) );
}

/**
 * Link to Video Uploading tutorial
 *
 * @since BuddyBoss 1.5.7
 */
function bp_video_uploading_tutorial() {
	?>

	<p>
		<a class="button" href="
		<?php
		echo bp_get_admin_url( // phpcs:ignore
			add_query_arg(
				array(
					'page'    => 'bp-help',
					'article' => 62827,
				),
				'admin.php'
			)
		);
		?>
		"><?php esc_html_e( 'View Tutorial', 'buddyboss' ); ?></a>
	</p>

	<?php
}

/**
 * Print the FFMPEG notice.
 */
function bp_video_admin_setting_callback_video_section() {

	if ( ! class_exists( 'FFMpeg\FFMpeg' ) ) {
		?>
		<p class="alert">
			<?php
			echo sprintf(
			/* translators: 1: FFMpeg status */
				_x( 'Your server needs %1$s installed to create video thumbnail (optional). Ask your web host.', 'extension notification', 'buddyboss' ), //phpcs:ignore
				'<code><a href="https://ffmpeg.org/" target="_blank">FFMPEG</a></code>'
			);
			?>
		</p>
		<?php
	} elseif ( class_exists( 'FFMpeg\FFMpeg' ) ) {
		$error = '';
		try {
			$ffmpeg = FFMpeg\FFMpeg::create();
		} catch ( Exception $ffmpeg ) {
			$error = $ffmpeg->getMessage();
		}
		if ( ! empty( trim( $error ) ) ) {
			?>
			<p class="alert">
				<?php
				echo sprintf(
				/* translators: 1: FFMpeg status */
					_x( 'Your server needs %1$s installed to create video thumbnail (optional). Ask your web host.', 'extension notification', 'buddyboss' ), //phpcs:ignore
					'<code><a href="https://ffmpeg.org/" target="_blank">FFMPEG</a></code>'
				);
				?>
			</p>
			<?php
		}
	}
}

/**
 * Setting > Media > Videos > Allowed Max File Size
 *
 * @since BuddyBoss 1.5.7
 */
function bp_video_settings_callback_video_allowed_size() {
	$max_size    = bp_core_upload_max_size();
	$max_size_mb = bp_video_format_size_units( $max_size, false, 'MB' );
	?>
	<input type="number" name="bp_video_allowed_size" id="bp_video_allowed_size" class="regular-text" min="1" step="1" max="<?php echo esc_attr( $max_size_mb ); ?>" required value="<?php echo esc_attr( bp_video_allowed_upload_video_size() ); ?>" style="width: 70px;"
	/> <?php esc_html_e( 'MB', 'buddyboss' ); ?>
	<p class="description">
		<?php
		printf(
			'%1$s <strong>%2$s %3$s</strong>',
			__( 'Set a maximum file size for video uploads, in megabytes. Your server\'s maximum upload size is ', 'buddyboss' ), //phpcs:ignore
			$max_size_mb, //phpcs:ignore
			'MB.'
		);
		?>
	</p>
	<?php
}

/**
 * Allowed upload file size for the video.
 *
 * @return int Allowed upload file size for the video.
 *
 * @since BuddyBoss 1.5.7
 */
function bp_video_allowed_upload_video_size() {
	$max_size = bp_core_upload_max_size();
	$default  = bp_video_format_size_units( $max_size, false, 'MB' );
	return (int) apply_filters( 'bp_video_allowed_upload_video_size', (int) get_option( 'bp_video_allowed_size', $default ) );
}

/**
 * Print extension link.
 */
function bp_video_settings_callback_extension_link() {

	printf(
		'<label>%s</label>',
		sprintf(
			__( '<a href="%s">Manage</a> which file extensions are allowed to be uploaded.', 'buddyboss' ), //phpcs:ignore
			bp_get_admin_url( //phpcs:ignore
				add_query_arg(
					array(
						'page' => 'bp-settings',
						'tab'  => 'bp-video',
					),
					'admin.php'
				)
			)
		)
	);
}

/**
 * Setting > Media > Photos > Allowed Per Batch
 *
 * @since BuddyBoss 1.5.7
 */
function bp_video_settings_callback_video_allowed_per_batch() {
	?>
	<input type="number" name="bp_video_allowed_per_batch" id="bp_video_allowed_per_batch" value="<?php echo esc_attr( bp_video_allowed_upload_video_per_batch() ); ?>" class="small-text" />
	<?php
}

/**
 * Allowed per batch for the video.
 *
 * @return int Allowed upload per batch for the video.
 * @since BuddyBoss 1.5.7
 */
function bp_video_allowed_upload_video_per_batch() {

	$default = apply_filters( 'bp_video_upload_chunk_limit', 10 );
	return (int) apply_filters( 'bp_video_allowed_upload_video_per_batch', (int) get_option( 'bp_video_allowed_per_batch', $default ) );
}
