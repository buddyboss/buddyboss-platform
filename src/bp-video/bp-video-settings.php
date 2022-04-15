<?php
/**
 * Video Settings
 *
 * @package BuddyBoss\Video
 * @since BuddyBoss 1.7.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Setting > Media > Video > Profile support
 *
 * @since BuddyBoss 1.7.0
 */
function bp_video_settings_callback_profile_video_support() {
	?>
	<input name="bp_video_profile_video_support" id="bp_video_profile_video_support" type="checkbox" value="1" <?php checked( bp_is_profile_video_support_enabled() ); ?> />
	<label for="bp_video_profile_video_support">
		<?php
		if ( bp_is_active( 'activity' ) ) {
			_e( 'Allow members to upload videos in <strong>profiles</strong> and <strong>activity posts</strong>', 'buddyboss' );  // phpcs:ignore
		} else {
			_e( 'Allow members to upload videos in <strong>profiles</strong>', 'buddyboss' );  // phpcs:ignore
		}
		?>
	</label>
	<?php
}

/**
 * Checks if profile video support is enabled.
 *
 * @param int $default default value.
 *
 * @return bool Is profile video support enabled or not
 * @since BuddyBoss 1.7.0
 */
function bp_is_profile_video_support_enabled( $default = 0 ) {
	/**
	 * Filters to checks if profile video support is enabled.
	 *
	 * @param int $default default value.
	 *
	 * @since BuddyBoss 1.7.0
	 */
	return (bool) apply_filters( 'bp_is_profile_video_support_enabled', (bool) get_option( 'bp_video_profile_video_support', $default ) );
}

/**
 * Setting > Media > Video > Groups support
 *
 * @since BuddyBoss 1.7.0
 */
function bp_video_settings_callback_group_video_support() {
	?>
	<input name="bp_video_group_video_support" id="bp_video_group_video_support" type="checkbox" value="1"
		<?php checked( bp_is_group_video_support_enabled() ); ?>
	/>
	<label for="bp_video_group_video_support">
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
			__( 'Allow members to upload videos in', 'buddyboss' ),
			$display_string
		);
		?>
	</label>
	<?php
}

/**
 * Checks if group video support is enabled.
 *
 * @param int $default default value.
 *
 * @return bool Is group video support enabled or not
 * @since BuddyBoss 1.7.0
 */
function bp_is_group_video_support_enabled( $default = 0 ) {
	/**
	 * Filters to checks if group video support is enabled.
	 *
	 * @param int $default default value.
	 *
	 * @since BuddyBoss 1.7.0
	 */
	return (bool) apply_filters( 'bp_is_group_video_support_enabled', (bool) get_option( 'bp_video_group_video_support', $default ) );
}

/**
 * Setting > Media > Video > Messages support
 *
 * @since BuddyBoss 1.7.0
 */
function bp_video_settings_callback_messages_video_support() {
	?>
	<input name="bp_video_messages_video_support" id="bp_video_messages_video_support" type="checkbox" value="1"
		<?php checked( bp_is_messages_video_support_enabled() ); ?>
	/>
	<label for="bp_video_messages_video_support">
		<?php
		_e( 'Allow members to upload videos in <strong>private messages</strong>', 'buddyboss' );
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
 * @since BuddyBoss 1.7.0
 */
function bp_is_messages_video_support_enabled( $default = 0 ) {
	/**
	 * Filters to checks if message video support is enabled.
	 *
	 * @param int $default default value.
	 *
	 * @since BuddyBoss 1.7.0
	 */
	return (bool) apply_filters( 'bp_is_messages_video_support_enabled', (bool) get_option( 'bp_video_messages_video_support', $default ) );
}

/**
 * Setting > Media > Video > Forums support
 *
 * @since BuddyBoss 1.7.0
 */
function bp_video_settings_callback_forums_video_support() {
	?>
	<input name="bp_video_forums_video_support" id="bp_video_forums_video_support" type="checkbox" value="1"
		<?php checked( bp_is_forums_video_support_enabled() ); ?>
	/>
	<label for="bp_video_forums_video_support">
		<?php _e( 'Allow members to upload videos in <strong>forum discussions</strong> and <strong>replies</strong>', 'buddyboss' ); ?>
	</label>
	<?php
}

/**
 * Checks if forums video support is enabled.
 *
 * @param int $default default value.
 *
 * @return bool Is forums video support enabled or not
 * @since BuddyBoss 1.7.0
 */
function bp_is_forums_video_support_enabled( $default = 0 ) {
	/**
	 * Filters to checks if forums video support is enabled.
	 *
	 * @param int $default default value.
	 *
	 * @since BuddyBoss 1.7.0
	 */
	return (bool) apply_filters( 'bp_is_forums_video_support_enabled', (bool) get_option( 'bp_video_forums_video_support', $default ) );
}

/**
 * Link to Video Uploading tutorial
 *
 * @since BuddyBoss 1.7.0
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
					'article' => 124807,
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
	?>
	<?php

	if ( ! class_exists( 'FFMpeg\FFMpeg' ) ) {
		?>
		<p class="alert">
			<?php
			echo sprintf(
				/* translators: 1: FFMpeg status */
				_x( 'Your server needs %1$s installed to automatically generate multiple thumbnails from video files (optional). Ask your web host.', 'extension notification', 'buddyboss' ), //phpcs:ignore
				'<code><a href="https://ffmpeg.org/" target="_blank">FFmpeg</a></code>'
			);
			?>
		</p>
		<?php
	} elseif ( class_exists( 'FFMpeg\FFMpeg' ) ) {
		$ffmpeg = bb_video_check_is_ffmpeg_binary();
		if ( ! empty( trim( $ffmpeg->error ) ) ) {
			?>
			<p class="alert">
				<?php
				echo sprintf(
				/* translators: %1$s FFmpeg status, %2$s FFMPEG Binary Path, %3$s FFPROBE Binary Path, %34$s wp-config.php file. */
					_x(
						'Your server needs %1$s installed to automatically create thumbnails after uploading videos (optional). Ask your web host.
					<br/><br/>If FFmpeg is already installed on your server and you still see the above warning, this means BuddyBoss Platform is unable to auto-detect the binary file path for FFmpeg. You will need to add the below FFmpeg absolute path constants into your %2$s file, replacing PATH_OF_BINARY_FILE with the actual file path to the FFmpeg binary file. Ask your web host to provide the absolute path for the FFmpeg binary file.
					<br /><br />%3$s
					<br />%4$s', 'extension notification', 'buddyboss' ), //phpcs:ignore
					'<code><a href="https://ffmpeg.org/" target="_blank">FFmpeg</a></code>',
					'<code>wp-config.php</code>',
					'<code>define( "BB_FFMPEG_BINARY_PATH", "PATH_OF_BINARY_FILE" );</code>',
					'<code>define( "BB_FFPROBE_BINARY_PATH", "PATH_OF_BINARY_FILE" );</code>'
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
 * @since BuddyBoss 1.7.0
 */
function bp_video_settings_callback_video_allowed_size() {
	$max_size    = bp_core_upload_max_size();
	$max_size_mb = bp_video_format_size_units( $max_size, false, 'MB' );
	?>
	<input type="number" name="bp_video_allowed_size" id="bp_video_allowed_size" class="regular-text" min="1" step="1" max="<?php echo esc_attr( $max_size_mb ); ?>" required value="<?php echo esc_attr( bp_video_allowed_upload_video_size() ); ?>" style="width: 70px;" /> <?php esc_html_e( 'MB', 'buddyboss' ); ?>
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
 * @since BuddyBoss 1.7.0
 */
function bp_video_allowed_upload_video_size() {
	$max_size = bp_core_upload_max_size();
	$default  = bp_video_format_size_units( $max_size, false, 'MB' );
	/**
	 * Filters for upload file size for the video.
	 *
	 * @param int $default default value.
	 *
	 * @since BuddyBoss 1.7.0
	 */
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
 * @since BuddyBoss 1.7.0
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
 * @since BuddyBoss 1.7.0
 */
function bp_video_allowed_upload_video_per_batch() {

	/**
	 * Filters for allowed per batch for the video.
	 *
	 * @param int $default default value.
	 *
	 * @since BuddyBoss 1.7.0
	 */
	$default = apply_filters( 'bp_video_upload_chunk_limit', 10 );

	/**
	 * Filters for allowed per batch for the video.
	 *
	 * @param int $default default value.
	 *
	 * @since BuddyBoss 1.7.0
	 */
	return (int) apply_filters( 'bp_video_allowed_upload_video_per_batch', (int) get_option( 'bp_video_allowed_per_batch', $default ) );
}
