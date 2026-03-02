<?php
/**
 * BuddyBoss Admin Settings - Media Callbacks.
 *
 * Sanitize callback functions for Media feature settings.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Get the server maximum upload size in megabytes.
 *
 * Uses bp_media_format_size_units() when available, otherwise falls back
 * to wp_max_upload_size(). Result is always >= 1.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @return int Server max upload size in MB.
 */
function bb_media_get_server_max_upload_size() {
	if ( function_exists( 'bp_media_format_size_units' ) ) {
		return max( 1, (int) bp_media_format_size_units( bp_core_upload_max_size(), false, 'MB' ) );
	}

	return max( 1, (int) ( wp_max_upload_size() / ( 1024 * 1024 ) ) );
}

/**
 * Build a human-readable description from a prefix and a list of context words.
 *
 * Joins the list with commas and "and" before the last item to produce strings
 * like "Allow members to upload photos in groups, activity posts, messages and forums".
 *
 * Used by Photos, Videos, Documents, Emoji, and GIFs panels to dynamically
 * describe where uploading is enabled based on active components.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param string $prefix   The opening text (e.g., "Allow members to upload photos in").
 * @param array  $contexts List of context words (e.g., array( 'groups', 'activity posts' )).
 *
 * @return string The formatted description string.
 */
function bb_media_build_context_description( $prefix, $contexts ) {
	if ( empty( $contexts ) ) {
		return $prefix;
	}

	$last = array_pop( $contexts );

	if ( count( $contexts ) > 0 ) {
		return $prefix . ' ' . implode( ', ', $contexts ) . ' and ' . $last;
	}

	return $prefix . ' ' . $last;
}

/**
 * Force photo-related support functions to return false when the Photos
 * section toggle (bb_media_photos_support) is disabled.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param bool $enabled Current value.
 *
 * @return bool False if photos section is off, otherwise the original value.
 */
function bb_media_photos_force_disable( $enabled ) {
	if ( ! (bool) bp_get_option( 'bb_media_photos_support', 1 ) ) {
		return false;
	}

	return $enabled;
}
add_filter( 'bp_is_profile_media_support_enabled', 'bb_media_photos_force_disable' );
add_filter( 'bp_is_profile_albums_support_enabled', 'bb_media_photos_force_disable' );
add_filter( 'bp_is_group_media_support_enabled', 'bb_media_photos_force_disable' );
add_filter( 'bp_is_group_albums_support_enabled', 'bb_media_photos_force_disable' );
add_filter( 'bp_is_messages_media_support_enabled', 'bb_media_photos_force_disable' );
add_filter( 'bp_is_forums_media_support_enabled', 'bb_media_photos_force_disable' );

/**
 * Force video-related support functions to return false when the Videos
 * section toggle (bb_media_videos_support) is disabled.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param bool $enabled Current value.
 *
 * @return bool False if videos section is off, otherwise the original value.
 */
function bb_media_videos_force_disable( $enabled ) {
	if ( ! (bool) bp_get_option( 'bb_media_videos_support', 1 ) ) {
		return false;
	}

	return $enabled;
}
add_filter( 'bp_is_profile_video_support_enabled', 'bb_media_videos_force_disable' );
add_filter( 'bp_is_group_video_support_enabled', 'bb_media_videos_force_disable' );
add_filter( 'bp_is_messages_video_support_enabled', 'bb_media_videos_force_disable' );
add_filter( 'bp_is_forums_video_support_enabled', 'bb_media_videos_force_disable' );

/**
 * Force document-related support functions to return false when the Documents
 * section toggle (bb_media_documents_support) is disabled.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param bool $enabled Current value.
 *
 * @return bool False if documents section is off, otherwise the original value.
 */
function bb_media_documents_force_disable( $enabled ) {
	if ( ! (bool) bp_get_option( 'bb_media_documents_support', 1 ) ) {
		return false;
	}

	return $enabled;
}
add_filter( 'bp_is_profile_document_support_enabled', 'bb_media_documents_force_disable' );
add_filter( 'bp_is_group_document_support_enabled', 'bb_media_documents_force_disable' );
add_filter( 'bp_is_messages_document_support_enabled', 'bb_media_documents_force_disable' );
add_filter( 'bp_is_forums_document_support_enabled', 'bb_media_documents_force_disable' );

/**
 * Force emoji-related support functions to return false when the Emoji
 * section toggle (bb_media_emoji_support) is disabled.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param bool $enabled Current value.
 *
 * @return bool False if emoji section is off, otherwise the original value.
 */
function bb_media_emoji_force_disable( $enabled ) {
	if ( ! (bool) bp_get_option( 'bb_media_emoji_support', 1 ) ) {
		return false;
	}

	return $enabled;
}
add_filter( 'bp_is_profiles_emoji_support_enabled', 'bb_media_emoji_force_disable' );
add_filter( 'bp_is_groups_emoji_support_enabled', 'bb_media_emoji_force_disable' );
add_filter( 'bp_is_messages_emoji_support_enabled', 'bb_media_emoji_force_disable' );
add_filter( 'bp_is_forums_emoji_support_enabled', 'bb_media_emoji_force_disable' );

/**
 * Force GIF-related support functions to return false when the Animated GIFs
 * section toggle (bb_media_gif_support) is disabled.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param bool $enabled Current value.
 *
 * @return bool False if GIFs section is off, otherwise the original value.
 */
function bb_media_gifs_force_disable( $enabled ) {
	if ( ! (bool) bp_get_option( 'bb_media_gif_support', 1 ) ) {
		return false;
	}

	return $enabled;
}
add_filter( 'bp_is_profiles_gif_support_enabled', 'bb_media_gifs_force_disable' );
add_filter( 'bp_is_groups_gif_support_enabled', 'bb_media_gifs_force_disable' );
add_filter( 'bp_is_messages_gif_support_enabled', 'bb_media_gifs_force_disable' );
add_filter( 'bp_is_forums_gif_support_enabled', 'bb_media_gifs_force_disable' );

/**
 * Sanitize upload size fields.
 *
 * Ensures the value is a positive integer that does not exceed the server's max upload size.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param mixed $value The value to sanitize.
 *
 * @return int Sanitized upload size in MB.
 */
function bb_media_sanitize_upload_size( $value ) {
	$value      = absint( $value );
	$server_max = bb_media_get_server_max_upload_size();

	if ( $value > $server_max ) {
		$value = $server_max;
	}

	return max( 1, $value );
}

/**
 * Sanitize upload limit (per batch) fields.
 *
 * Ensures the value is a positive integer with a reasonable maximum.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param mixed $value The value to sanitize.
 *
 * @return int Sanitized upload limit.
 */
function bb_media_sanitize_upload_limit( $value ) {
	$value = absint( $value );

	return max( 1, min( $value, 100 ) );
}

/**
 * Sanitize file extensions array (video/document).
 *
 * Handles two input formats from the React admin UI:
 *
 * 1. Toggle-only update: { bb_vid_0: 1, bb_vid_1: 0, ... }
 *    Merges is_active values into the existing stored extension data.
 *
 * 2. Full extension data: { bb_vid_0: { extension: '.mp4', ... }, ... }
 *    Full sanitization of each entry (used when adding new extensions).
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param mixed $value The value to sanitize.
 *
 * @return array Sanitized extensions array.
 */
function bb_media_sanitize_extensions( $value ) {
	if ( ! is_array( $value ) ) {
		return array();
	}

	// Determine format: if the first value is scalar (int), it's a toggle-only update.
	$first_value    = reset( $value );
	$is_toggle_only = ! is_array( $first_value );

	if ( $is_toggle_only ) {
		// Determine the correct option name from key prefix.
		$first_key   = key( $value );
		$option_name = ( 0 === strpos( $first_key, 'bb_vid' ) )
			? 'bp_video_extensions_support'
			: 'bp_document_extensions_support';

		// Merge toggle states into existing stored data.
		$existing = bp_get_option( $option_name, array() );

		foreach ( $value as $key => $is_active ) {
			$sanitized_key = sanitize_key( $key );

			if ( isset( $existing[ $sanitized_key ] ) ) {
				$existing[ $sanitized_key ]['is_active'] = absint( $is_active );
			}
		}

		return $existing;
	}

	// Full extension data format.
	$sanitized = array();

	foreach ( $value as $key => $ext ) {
		if ( ! is_array( $ext ) ) {
			continue;
		}

		$sanitized_key = sanitize_key( $key );

		$sanitized[ $sanitized_key ] = array(
			'extension'   => isset( $ext['extension'] ) ? sanitize_text_field( $ext['extension'] ) : '',
			'mime_type'   => isset( $ext['mime_type'] ) ? sanitize_mime_type( $ext['mime_type'] ) : '',
			'description' => isset( $ext['description'] ) ? sanitize_text_field( $ext['description'] ) : '',
			'is_default'  => isset( $ext['is_default'] ) ? absint( $ext['is_default'] ) : 0,
			'is_active'   => isset( $ext['is_active'] ) ? absint( $ext['is_active'] ) : 0,
			'icon'        => isset( $ext['icon'] ) ? sanitize_text_field( $ext['icon'] ) : '',
		);
	}

	return $sanitized;
}

/**
 * Sanitize GIPHY API key.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param mixed $value The value to sanitize.
 *
 * @return string Sanitized API key.
 */
function bb_media_sanitize_gif_api_key( $value ) {
	return sanitize_text_field( wp_unslash( $value ) );
}

/**
 * AJAX handler for GIPHY API key connect/disconnect.
 *
 * Validates the API key against the GIPHY API and saves or clears it.
 * Returns updated connection status for the React UI to update section badges.
 *
 * @since BuddyBoss [BBVERSION]
 */
function bb_media_ajax_giphy_connect() {
	// Verify nonce.
	check_ajax_referer( 'bb_admin_settings', 'nonce' );

	// Capability check.
	if ( ! bp_current_user_can( 'bp_moderate' ) ) {
		wp_send_json_error( array( 'message' => __( 'Unauthorized.', 'buddyboss' ) ) );
	}

	$action  = isset( $_POST['connect_action'] ) ? sanitize_text_field( wp_unslash( $_POST['connect_action'] ) ) : '';
	$api_key = isset( $_POST['api_key'] ) ? sanitize_text_field( wp_unslash( $_POST['api_key'] ) ) : '';

	if ( 'disconnect' === $action ) {
		// Clear the API key.
		bp_update_option( 'bp_media_gif_api_key', '' );

		// Clear the validation cache.
		delete_transient( 'bb_check_valid_giphy_api_key' );

		wp_send_json_success(
			array(
				'is_connected' => false,
				'button_label' => __( 'Connect', 'buddyboss' ),
				'status'       => array(
					'type' => 'warning',
					'text' => __( 'Not Connected', 'buddyboss' ),
				),
				'message'      => __( 'GIPHY API key disconnected.', 'buddyboss' ),
			)
		);
	}

	// Connect: save and validate the API key.
	if ( empty( $api_key ) ) {
		wp_send_json_error( array( 'message' => __( 'Please enter an API key.', 'buddyboss' ) ) );
	}

	// Save the key first (matches legacy behavior where Settings API saves regardless of validation).
	bp_update_option( 'bp_media_gif_api_key', $api_key );

	// Clear old validation cache so it picks up the new key.
	delete_transient( 'bb_check_valid_giphy_api_key' );

	// Validate via GIPHY API.
	$is_valid = false;
	if ( function_exists( 'bb_check_valid_giphy_api_key' ) ) {
		$is_valid = bb_check_valid_giphy_api_key( $api_key );
	}

	if ( $is_valid ) {
		wp_send_json_success(
			array(
				'is_connected' => true,
				'button_label' => __( 'Disconnect', 'buddyboss' ),
				'status'       => array(
					'type' => 'success',
					'text' => __( 'Connected', 'buddyboss' ),
				),
				'message'      => __( 'GIPHY API key connected successfully.', 'buddyboss' ),
			)
		);
	}

	// Key saved but validation failed — keep as not connected so user can re-enter.
	wp_send_json_success(
		array(
			'is_connected' => false,
			'button_label' => __( 'Connect', 'buddyboss' ),
			'status'       => array(
				'type' => 'warning',
				'text' => __( 'Not Connected', 'buddyboss' ),
			),
			'message'      => __( 'API key saved, but GIPHY could not verify the key. Please check that your key is correct.', 'buddyboss' ),
			'has_warning'  => true,
		)
	);
}
add_action( 'wp_ajax_bb_media_giphy_connect', 'bb_media_ajax_giphy_connect' );

/**
 * Sanitize symbolic links support toggle.
 *
 * Forces the value to 0 if symlinks are disabled on the server or media is offloaded.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param mixed $value The value to sanitize.
 *
 * @return int 0 or 1.
 */
function bb_media_sanitize_symlink_support( $value ) {
	$value = absint( $value );

	// Force off if server has symlink function disabled.
	if ( function_exists( 'bb_check_server_disabled_symlink' ) && bb_check_server_disabled_symlink() ) {
		return 0;
	}

	// Force off if media is offloaded.
	if ( (bool) apply_filters( 'bb_media_offload_delivered', false ) ) {
		return 0;
	}

	return $value ? 1 : 0;
}

/**
 * AJAX handler for Symbolic Links status check.
 *
 * Returns the current symlink status as a notice (success/warning)
 * by re-evaluating server state and the current option value.
 * Called on mount and whenever the Symbolic Links toggle changes.
 *
 * @since BuddyBoss [BBVERSION]
 */
function bb_media_ajax_check_symlink_status() {
	// Verify nonce.
	check_ajax_referer( 'bb_admin_settings', 'nonce' );

	// Capability check.
	if ( ! bp_current_user_can( 'bp_moderate' ) ) {
		wp_send_json_error( array( 'message' => __( 'Unauthorized.', 'buddyboss' ) ) );
	}

	$is_offloaded        = (bool) apply_filters( 'bb_media_offload_delivered', false );
	$is_symlink_disabled = function_exists( 'bb_check_server_disabled_symlink' ) && bb_check_server_disabled_symlink();

	// Use the watch_value from the client if provided (reflects the current toggle
	// state which may not be saved to the DB yet due to auto-save debounce).
	// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce already verified above.
	if ( isset( $_POST['watch_field'] ) && 'bp_media_symlink_support' === sanitize_text_field( wp_unslash( $_POST['watch_field'] ) ) ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce already verified above.
		$is_symlinks_enabled = ! empty( $_POST['watch_value'] );
	} else {
		$is_symlinks_enabled = function_exists( 'bb_enable_symlinks' ) && bb_enable_symlinks();
	}

	$symlink_type        = bp_get_option( 'bb_media_symlink_type' );
	$display_support_err = (bool) bp_get_option( 'bb_display_support_error' );

	if ( $is_offloaded ) {
		$delivery_provider = apply_filters( 'bb_media_offload_delivery_provider', __( 'Other', 'buddyboss' ) );
		wp_send_json_success(
			array(
				'status'  => 'warning',
				'message' => sprintf(
					/* translators: %s: Offload delivery provider name. */
					__( 'Symbolic links are disabled due to media being offloaded to %s', 'buddyboss' ),
					$delivery_provider
				),
			)
		);
	} elseif ( $is_symlink_disabled ) {
		wp_send_json_success(
			array(
				'status'  => 'warning',
				'message' => __( 'Symbolic function disabled on your server. Please contact your hosting provider.', 'buddyboss' ),
			)
		);
	} elseif ( $is_symlinks_enabled && empty( $symlink_type ) ) {
		wp_send_json_success(
			array(
				'status'  => 'warning',
				'message' => __( 'Symbolic links don\'t seem to work on your server. Please contact BuddyBoss for support.', 'buddyboss' ),
			)
		);
	} elseif ( $display_support_err ) {
		wp_send_json_success(
			array(
				'status'  => 'warning',
				'message' => __( 'Symbolic links don\'t seem to work on your server. Please contact BuddyBoss for support.', 'buddyboss' ),
			)
		);
	} elseif ( ! $is_symlinks_enabled ) {
		wp_send_json_success(
			array(
				'status'  => 'warning',
				'message' => __( 'Symbolic links are disabled', 'buddyboss' ),
			)
		);
	} else {
		wp_send_json_success(
			array(
				'status'  => 'success',
				'message' => __( 'Symbolic links are activated', 'buddyboss' ),
			)
		);
	}
}
add_action( 'wp_ajax_bb_media_check_symlink_status', 'bb_media_ajax_check_symlink_status' );

/**
 * AJAX handler for FFmpeg status check.
 *
 * Checks whether the FFmpeg PHP library is installed and whether
 * the FFmpeg binary is accessible on the server. Returns a warning
 * notice if FFmpeg is missing or misconfigured.
 *
 * @since BuddyBoss [BBVERSION]
 */
function bb_media_ajax_check_ffmpeg_status() {
	// Verify nonce.
	check_ajax_referer( 'bb_admin_settings', 'nonce' );

	// Capability check.
	if ( ! bp_current_user_can( 'bp_moderate' ) ) {
		wp_send_json_error( array( 'message' => __( 'Unauthorized.', 'buddyboss' ) ) );
	}

	$has_ffmpeg_class = class_exists( 'BuddyBossPlatform\FFMpeg\FFMpeg' ) || class_exists( 'FFMpeg\FFMpeg' );

	if ( ! $has_ffmpeg_class ) {
		// FFmpeg library not installed at all.
		wp_send_json_success(
			array(
				'status'  => 'info',
				'message' => sprintf(
					/* translators: %s: FFmpeg link */
					_x(
						'Your server needs %s installed to automatically generate multiple thumbnails from video files (optional). Ask your web host.',
						'extension notification',
						'buddyboss'
					),
					'<code><a href="https://ffmpeg.org/" target="_blank" rel="noopener noreferrer">FFmpeg</a></code>'
				),
			)
		);
	}

	// FFmpeg class exists — check if the binary is accessible.
	if ( function_exists( 'bb_video_check_is_ffmpeg_binary' ) ) {
		$ffmpeg = bb_video_check_is_ffmpeg_binary();
		if ( ! empty( $ffmpeg->error ) && ! empty( trim( $ffmpeg->error ) ) ) {
			wp_send_json_success(
				array(
					'status'  => 'info',
					'message' => sprintf(
						/* translators: 1: FFmpeg link, 2: wp-config.php, 3: FFMPEG constant, 4: FFPROBE constant */
						_x(
							'Your server needs %1$s installed to automatically create thumbnails after uploading videos (optional). Ask your web host.<br /><br />If FFmpeg is already installed on your server and you still see the above warning, this means BuddyBoss Platform is unable to auto-detect the binary file path for FFmpeg. You will need to add the below FFmpeg absolute path constants into your %2$s file, replacing PATH_OF_BINARY_FILE with the actual file path to the FFmpeg binary file. Ask your web host to provide the absolute path for the FFmpeg binary file.<br /><br />%3$s<br />%4$s',
							'extension notification',
							'buddyboss'
						),
						'<code><a href="https://ffmpeg.org/" target="_blank" rel="noopener noreferrer">FFmpeg</a></code>',
						'<code>wp-config.php</code>',
						'<code>define( "BB_FFMPEG_BINARY_PATH", "PATH_OF_BINARY_FILE" );</code>',
						'<code>define( "BB_FFPROBE_BINARY_PATH", "PATH_OF_BINARY_FILE" );</code>'
					),
				)
			);
		}
	}

	// FFmpeg is installed and binary is accessible — no notice needed.
	wp_send_json_success(
		array(
			'status'  => 'success',
			'message' => '',
		)
	);
}
add_action( 'wp_ajax_bb_media_check_ffmpeg_status', 'bb_media_ajax_check_ffmpeg_status' );

/**
 * AJAX handler for Direct Access status check.
 *
 * Creates test file uploads in video, media, and document directories,
 * then checks via HTTP if those files are directly accessible.
 * Reports whether direct access is blocked or not.
 *
 * Replicates the legacy `bb_media_settings_callback_symlink_direct_access()` behavior
 * as an on-demand AJAX call instead of running on every settings page load.
 *
 * @since BuddyBoss [BBVERSION]
 */
function bb_media_ajax_check_direct_access() {
	// Verify nonce.
	check_ajax_referer( 'bb_admin_settings', 'nonce' );

	// Capability check.
	if ( ! bp_current_user_can( 'bp_moderate' ) ) {
		wp_send_json_error( array( 'message' => __( 'Unauthorized.', 'buddyboss' ) ) );
	}

	$bypass_check = (bool) apply_filters( 'bb_media_check_default_access', false );

	if ( $bypass_check ) {
		$delivery_provider = apply_filters( 'bb_media_offload_delivery_provider', __( 'Other', 'buddyboss' ) );

		wp_send_json_success(
			array(
				'status'  => 'warning',
				'message' => sprintf(
					/* translators: %s: Offload delivery provider name. */
					__( 'Direct access to your media files and folders is disabled due to media being offloaded to %s', 'buddyboss' ),
					$delivery_provider
				),
			)
		);
	}

	$get_sample_local_urls  = array();
	$video_attachment_id    = 0;
	$media_attachment_id    = 0;
	$document_attachment_id = 0;

	// Upload test files to video directory.
	if ( function_exists( 'bp_video_upload_dir_script' ) ) {
		add_filter( 'upload_dir', 'bp_video_upload_dir_script' );
		$upload_result       = bb_media_create_test_upload();
		$video_attachment_id = $upload_result['attachment_id'];
		if ( ! empty( $upload_result['url'] ) ) {
			$get_sample_local_urls['bb_videos'] = $upload_result['url'];
		}
		remove_filter( 'upload_dir', 'bp_video_upload_dir_script' );
	}

	// Upload test files to media directory.
	if ( function_exists( 'bp_media_upload_dir_script' ) ) {
		add_filter( 'upload_dir', 'bp_media_upload_dir_script' );
		$upload_result       = bb_media_create_test_upload();
		$media_attachment_id = $upload_result['attachment_id'];
		if ( ! empty( $upload_result['url'] ) ) {
			$get_sample_local_urls['bb_medias'] = $upload_result['url'];
		}
		remove_filter( 'upload_dir', 'bp_media_upload_dir_script' );
	}

	// Upload test files to document directory.
	if ( function_exists( 'bp_document_upload_dir_script' ) ) {
		add_filter( 'upload_dir', 'bp_document_upload_dir_script' );
		$upload_result          = bb_media_create_test_upload();
		$document_attachment_id = $upload_result['attachment_id'];
		if ( ! empty( $upload_result['url'] ) ) {
			$get_sample_local_urls['bb_documents'] = $upload_result['url'];
		}
		remove_filter( 'upload_dir', 'bp_document_upload_dir_script' );
	}

	// Check each uploaded file for direct HTTP access.
	$accessible_directories = array();
	foreach ( $get_sample_local_urls as $dir_id => $url ) {
		$fetch = wp_remote_get( $url, array( 'sslverify' => false ) );
		if ( ! is_wp_error( $fetch ) && isset( $fetch['response']['code'] ) && 200 === $fetch['response']['code'] ) {
			$accessible_directories[] = $dir_id;
		}
	}

	// Clean up test attachments.
	if ( 0 !== $video_attachment_id && ! is_wp_error( $video_attachment_id ) ) {
		wp_delete_attachment( $video_attachment_id, true );
	}
	if ( 0 !== $media_attachment_id && ! is_wp_error( $media_attachment_id ) ) {
		wp_delete_attachment( $media_attachment_id, true );
	}
	if ( 0 !== $document_attachment_id && ! is_wp_error( $document_attachment_id ) ) {
		wp_delete_attachment( $document_attachment_id, true );
	}

	if ( ! empty( $accessible_directories ) ) {
		wp_send_json_success(
			array(
				'status'  => 'warning',
				'message' => __( 'Direct access to your media files and folders is not blocked', 'buddyboss' ),
			)
		);
	}

	wp_send_json_success(
		array(
			'status'  => 'success',
			'message' => __( 'Direct access to your media files and folders is blocked', 'buddyboss' ),
		)
	);
}
add_action( 'wp_ajax_bb_media_check_direct_access', 'bb_media_ajax_check_direct_access' );

/**
 * Create a test upload attachment for direct access checks.
 *
 * Uses the BuddyBoss core fallback image as a test file.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @return array { attachment_id: int, url: string }
 */
function bb_media_create_test_upload() {
	$result = array(
		'attachment_id' => 0,
		'url'           => '',
	);

	$file     = buddypress()->plugin_dir . 'bp-core/images/suspended-mystery-man.jpg';
	$filename = basename( $file );

	if ( ! file_exists( $file ) ) {
		return $result;
	}

	// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Reading local file.
	$upload_file = wp_upload_bits( $filename, null, file_get_contents( $file ) );

	if ( ! empty( $upload_file['error'] ) ) {
		return $result;
	}

	$wp_filetype   = wp_check_filetype( $filename, null );
	$attachment    = array(
		'post_mime_type' => $wp_filetype['type'],
		'post_title'     => preg_replace( '/\.[^.]+$/', '', $filename ),
		'post_content'   => '',
		'post_status'    => 'inherit',
	);
	$attachment_id = wp_insert_attachment( $attachment, $upload_file['file'] );

	if ( ! is_wp_error( $attachment_id ) ) {
		require_once ABSPATH . 'wp-admin/includes/image.php';
		$attachment_data = wp_generate_attachment_metadata( $attachment_id, $upload_file['file'] );
		wp_update_attachment_metadata( $attachment_id, $attachment_data );
		$result['attachment_id'] = $attachment_id;
		$result['url']           = $upload_file['url'];
	}

	return $result;
}

/**
 * Get extension toggle options from a stored option.
 *
 * Reads extension data from the database and formats each entry as a
 * toggle list option with label, value, and is_default flag.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param string $option_name The option name storing extension data.
 * @param bool   $include_default Whether to include the is_default flag.
 *
 * @return array Toggle list options.
 */
function bb_media_get_extension_options( $option_name, $include_default = false ) {
	$extensions = bp_get_option( $option_name, array() );
	$options    = array();

	foreach ( $extensions as $key => $ext ) {
		if ( ! is_array( $ext ) || empty( $ext['extension'] ) ) {
			continue;
		}
		$option = array(
			'label' => ! empty( $ext['description'] ) ? $ext['extension'] . ' (' . $ext['description'] . ')' : $ext['extension'],
			'value' => $key,
		);

		if ( $include_default ) {
			$option['is_default'] = ! empty( $ext['is_default'] ) ? 1 : 0;
		}

		$options[] = $option;
	}

	return $options;
}

/**
 * Get full extension data from a stored option.
 *
 * Returns the raw extension array so the React UI can display extension
 * details and support adding/removing custom extensions.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param string $option_name The option name storing extension data.
 *
 * @return array Full extension data keyed by extension ID.
 */
function bb_media_get_extension_data( $option_name ) {
	$extensions = bp_get_option( $option_name, array() );
	$data       = array();

	foreach ( $extensions as $key => $ext ) {
		if ( ! is_array( $ext ) || empty( $ext['extension'] ) ) {
			continue;
		}
		$data[ $key ] = array(
			'extension'   => $ext['extension'] ?? '',
			'mime_type'   => $ext['mime_type'] ?? '',
			'description' => $ext['description'] ?? '',
			'is_default'  => ! empty( $ext['is_default'] ) ? 1 : 0,
			'is_active'   => ! empty( $ext['is_active'] ) ? 1 : 0,
		);
	}

	return $data;
}

/**
 * Format a document icon class for display.
 *
 * Applies the bb_document_icon_class filter and adds the appropriate
 * icon font prefix (ReadyLaunch or legacy).
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param string $icon_value The raw icon identifier (e.g., 'bb-icon-file-pdf').
 *
 * @return string Formatted icon class string.
 */
function bb_media_format_icon_class( $icon_value ) {
	$render_icon = apply_filters( 'bb_document_icon_class', $icon_value );

	return ( strpos( $render_icon, 'bb-icons-rl' ) !== false )
		? 'bb-icons-rl ' . $render_icon
		: 'bb-icon-l ' . $render_icon;
}
