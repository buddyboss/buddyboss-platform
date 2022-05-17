<?php
/**
 * Notification icon attachment class.
 *
 * @package BuddyBoss\Core
 * @since [BBVERSION]
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * BP Attachment Notification icon class.
 *
 * Extends BP Attachment to manage the push notification icon uploads.
 *
 * @since [BBVERSION]
 */
class BP_Attachment_Notification_Icon extends BP_Attachment {
	/**
	 * The constuctor.
	 *
	 * @since [BBVERSION]
	 */
	public function __construct() {
		// Allowed notification icon types & upload size.
		$allowed_types        = bp_attachments_get_allowed_types();
		$max_upload_file_size = bp_attachments_get_max_upload_file_size( 'notification_icon' );

		parent::__construct(
			array(
				'action'                => 'bp_notification_icon_upload',
				'file_input'            => 'file',
				'original_max_filesize' => $max_upload_file_size,
				'base_dir'              => bp_attachments_uploads_dir_get( 'dir' ),
				'required_wp_files'     => array( 'file', 'image' ),

				// Specific errors for notifications.
				'upload_error_strings'  => array(
					11 => sprintf( __( 'That image is too big. Please upload one smaller than %s', 'buddyboss' ), size_format( $max_upload_file_size ) ),
					12 => sprintf( _n( 'Please upload only this file type: %s.', 'Please upload only these file types: %s.', count( $allowed_types ), 'buddyboss' ), self::get_notification_icon_types( $allowed_types ) ),
				),
			)
		);
	}

	/**
	 * Gets the available notification icon types.
	 *
	 * @since [BBVERSION]
	 *
	 * @param array $allowed_types Array of allowed notification icon types.
	 * @return string $value Comma-separated list of allowed notification icon types.
	 */
	public static function get_notification_icon_types( $allowed_types = array() ) {
		$types = array_map( 'strtoupper', $allowed_types );
		$comma = _x( ',', 'notification icon types separator', 'buddyboss' );
		return join( $comma . ' ', $types );
	}

	/**
	 * notification icon specific rules.
	 *
	 * Adds an error if the notification icon size or type don't match BuddyPress needs.
	 * The error code is the index of $upload_error_strings.
	 *
	 * @since [BBVERSION]
	 *
	 * @param array $file The temporary file attributes (before it has been moved).
	 * @return array $file The file with extra errors if needed.
	 */
	public function validate_upload( $file = array() ) {
		// Bail if already an error.
		if ( ! empty( $file['error'] ) ) {
			return $file;
		}

		// File size is too big.
		if ( isset( $file['size'] ) && ( $file['size'] > $this->original_max_filesize ) ) {
			$file['error'] = 11;

			// File is of invalid type.
		} elseif ( isset( $file['tmp_name'] ) && isset( $file['name'] ) && ! bp_attachments_check_filetype( $file['tmp_name'], $file['name'], bp_attachments_get_allowed_mimes() ) ) {
			$file['error'] = 12;
		}

		// Return with error code attached.
		return $file;
	}

	/**
	 * Set the directory when uploading a file.
	 *
	 * @since [BBVERSION]
	 *
	 * @param array $upload_dir The original Uploads dir.
	 * @return array $value Upload data (path, url, basedir...).
	 */
	public function upload_dir_filter( $upload_dir = array() ) {
		if ( function_exists( 'bp_attachments_notification_icon_upload_dir' ) ) {
			return bp_attachments_notification_icon_upload_dir();
		}

		return $upload_dir;
	}

	/**
	 * Adjust the notification icon to fit with advised width & height.
	 *
	 * @since [BBVERSION]
	 *
	 * @param string $file       The absolute path to the file.
	 * @param array  $dimensions Array of dimensions for the notification.
	 * @return mixed
	 */
	public function fit( $file = '', $dimensions = array() ) {
		if ( empty( $dimensions['width'] ) || empty( $dimensions['height'] ) ) {
			return false;
		}

		// Get image size.
		$notification_icon_data = parent::get_image_data( $file );

		$image_width  = $notification_icon_data['width'];
		$image_height = $notification_icon_data['height'];

		$max_width  = ( ! empty( $image_width ) && ! empty( $image_height ) && $image_height > $dimensions['height'] ? ( $image_width * $dimensions['height'] ) / $image_height : 0 );
		$max_height = ( ! empty( $image_width ) && ! empty( $image_height ) && $image_width > $dimensions['width'] ? ( $image_height * $dimensions['width'] ) / $image_width : 0 );

		// Init the edit args.
		$edit_args = array();

		// Do we need to resize the image?
		if (
			isset( $notification_icon_data['width'] )
			&& $notification_icon_data['width'] > $dimensions['width']
			&& $max_height >= $dimensions['height']
		) {
			$edit_args = array(
				'max_w' => $dimensions['width'],
				'crop'  => false,
			);
		} elseif (
			isset( $notification_icon_data['height'] )
			&& $notification_icon_data['height'] > $dimensions['height']
			&& $max_width >= $dimensions['width']
		) {
			$edit_args = array(
				'max_h' => $dimensions['height'],
				'crop'  => false,
			);
		}

		// Do we need to rotate the image?
		$angles = array(
			3 => 180,
			6 => -90,
			8 => 90,
		);

		if ( isset( $notification_icon_data['meta']['orientation'] ) && isset( $angles[ $notification_icon_data['meta']['orientation'] ] ) ) {
			$edit_args['rotate'] = $angles[ $notification_icon_data['meta']['orientation'] ];
		}

		// No need to edit the avatar, original file will be used.
		if ( empty( $edit_args ) ) {
			return false;

			// Add the file to the edit arguments.
		} else {
			$edit_args = array_merge(
				$edit_args,
				array(
					'file' => $file,
					'save' => false,
				)
			);
		}

		// Get the editor so that we can use a specific save method.
		$editor = parent::edit_image( 'notification_icon', $edit_args );

		if ( is_wp_error( $editor ) ) {
			return $editor;
		} elseif ( ! is_a( $editor, 'WP_Image_Editor' ) ) {
			return false;
		}

		// Save the new image file.
		return $editor->save( $this->generate_filename( $file ) );
	}

	/**
	 * Generate a filename for the notification.
	 *
	 * @since [BBVERSION]
	 *
	 * @param string $file The absolute path to the file.
	 * @return false|string $value The absolute path to the new file name.
	 */
	public function generate_filename( $file = '' ) {
		if ( empty( $file ) || ! file_exists( $file ) ) {
			return false;
		}

		$info = pathinfo( $file );
		$ext  = strtolower( $info['extension'] );
		$name = wp_unique_filename( $info['dirname'], uniqid() . "-bp-notification-icon.$ext" );

		return trailingslashit( $info['dirname'] ) . $name;
	}

	/**
	 * Build script datas for the Uploader UI.
	 *
	 * @since [BBVERSION]
	 *
	 * @return array The javascript localization data
	 */
	public function script_data() {
		// Get default script data.
		$script_data = parent::script_data();
		/**
		 * Filters the notification icon params to include specific BuddyPress params for your object.
		 * e.g. notification icon for blogs single item.
		 *
		 * @since [BBVERSION]
		 *
		 * @param array $value The notification icon specific BuddyPress parameters.
		 */
		$script_data['bp_params'] = apply_filters( 'bp_attachment_notification_icon_params', array() );

		// Include our specific js & css.
		$script_data['extra_js']  = array( 'bp-notification-icon' );
		$script_data['extra_css'] = array( 'bp-avatar' );

		/**
		 * Filters the notification icon script data.
		 *
		 * @since [BBVERSION]
		 *
		 * @param array $script_data Array of data for the notification icon.
		 */
		return apply_filters( 'bp_attachments_notification_icon_script_data', $script_data );
	}
}
