<?php
/**
 * Core Cover Photo attachment class.
 *
 * @package BuddyBoss\Core
 * @since BuddyPress 2.4.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * BP Attachment Cover Photo class.
 *
 * Extends BP Attachment to manage the cover photos uploads.
 *
 * @since BuddyPress 2.4.0
 */
#[\AllowDynamicProperties]
class BP_Attachment_Cover_Image extends BP_Attachment {
	/**
	 * The constuctor.
	 *
	 * @since BuddyPress 2.4.0
	 */
	public function __construct() {
		// Allowed cover photo types & upload size.
		$allowed_types        = bp_attachments_get_allowed_types( 'cover_image' );
		$max_upload_file_size = bp_attachments_get_max_upload_file_size( 'cover_image' );

		parent::__construct(
			array(
				'action'                => 'bp_cover_image_upload',
				'file_input'            => 'file',
				'original_max_filesize' => $max_upload_file_size,
				'base_dir'              => bp_attachments_uploads_dir_get( 'dir' ),
				'required_wp_files'     => array( 'file', 'image' ),

				// Specific errors for cover photos.
				'upload_error_strings'  => array(
					11 => sprintf( __( 'That image is too big. Please upload one smaller than %s', 'buddyboss' ), size_format( $max_upload_file_size ) ),
					12 => sprintf( _n( 'Please upload only this file type: %s.', 'Please upload only these file types: %s.', count( $allowed_types ), 'buddyboss' ), self::get_cover_image_types( $allowed_types ) ),
				),
			)
		);
	}

	/**
	 * Gets the available cover photo types.
	 *
	 * @since BuddyPress 2.4.0
	 *
	 * @param array $allowed_types Array of allowed cover photo types.
	 * @return string $value Comma-separated list of allowed cover photo types.
	 */
	public static function get_cover_image_types( $allowed_types = array() ) {
		$types = array_map( 'strtoupper', $allowed_types );
		$comma = _x( ',', 'cover photo types separator', 'buddyboss' );
		return join( $comma . ' ', $types );
	}

	/**
	 * cover photo specific rules.
	 *
	 * Adds an error if the cover photo size or type don't match BuddyPress needs.
	 * The error code is the index of $upload_error_strings.
	 *
	 * @since BuddyPress 2.4.0
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
		} elseif ( isset( $file['tmp_name'] ) && isset( $file['name'] ) && ! bp_attachments_check_filetype( $file['tmp_name'], $file['name'], bp_attachments_get_allowed_mimes( 'cover_image' ) ) ) {
			$file['error'] = 12;
		}

		// Return with error code attached.
		return $file;
	}

	/**
	 * Set the directory when uploading a file.
	 *
	 * @since BuddyPress 2.4.0
	 *
	 * @param array $upload_dir The original Uploads dir.
	 * @return array $value Upload data (path, url, basedir...).
	 */
	public function upload_dir_filter( $upload_dir = array() ) {
		return bp_attachments_cover_image_upload_dir();
	}

	/**
	 * Adjust the cover photo to fit with advised width & height.
	 *
	 * @since BuddyPress 2.4.0
	 *
	 * @param string $file       The absolute path to the file.
	 * @param array  $dimensions Array of dimensions for the cover photo.
	 * @return mixed
	 */
	public function fit( $file = '', $dimensions = array() ) {
		if ( empty( $dimensions['width'] ) || empty( $dimensions['height'] ) ) {
			return false;
		}

		// Get image size.
		$cover_data = parent::get_image_data( $file );

		$image_width  = $cover_data['width'];
		$image_height = $cover_data['height'];

		$max_width  = ( ! empty( $image_width ) && ! empty( $image_height ) && $image_height > $dimensions['height'] ? ( $image_width * $dimensions['height'] ) / $image_height : 0 );
		$max_height = ( ! empty( $image_width ) && ! empty( $image_height ) && $image_width > $dimensions['width'] ? ( $image_height * $dimensions['width'] ) / $image_width : 0 );

		// Init the edit args.
		$edit_args = array();

		// Do we need to resize the image?
		if (
			isset( $cover_data['width'] )
			&& $cover_data['width'] > $dimensions['width']
			&& $max_height >= $dimensions['height']
		) {
			$edit_args = array(
				'max_w' => $dimensions['width'],
				'crop'  => false,
			);
		} elseif (
			isset( $cover_data['height'] )
			&& $cover_data['height'] > $dimensions['height']
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

		if ( isset( $cover_data['meta']['orientation'] ) && isset( $angles[ $cover_data['meta']['orientation'] ] ) ) {
			$edit_args['rotate'] = $angles[ $cover_data['meta']['orientation'] ];
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
		$editor = parent::edit_image( 'cover_image', $edit_args );

		if ( is_wp_error( $editor ) ) {
			return $editor;
		} elseif ( ! is_a( $editor, 'WP_Image_Editor' ) ) {
			return false;
		}

		// Save the new image file.
		return $editor->save( $this->generate_filename( $file ) );
	}

	/**
	 * Generate a filename for the cover photo.
	 *
	 * @since BuddyPress 2.4.0
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
		$name = wp_unique_filename( $info['dirname'], uniqid() . "-bp-cover-image.$ext" );

		return trailingslashit( $info['dirname'] ) . $name;
	}

	/**
	 * Build script datas for the Uploader UI.
	 *
	 * @since BuddyPress 2.4.0
	 *
	 * @return array The javascript localization data
	 */
	public function script_data() {
		// Get default script data.
		$script_data = parent::script_data();

		if ( bp_is_user() ) {
			$item_id = bp_displayed_user_id();

			$script_data['bp_params'] = array(
				'object'            => 'user',
				'item_id'           => $item_id,
				'has_cover_image'   => bp_attachments_get_user_has_cover_image( $item_id ),
				'has_default_class' => ( ! bp_disable_cover_image_uploads() && 'custom' !== bb_get_default_profile_cover_type() ) ? 'has-default' : '',
				'nonces'            => array(
					'remove' => wp_create_nonce( 'bp_delete_cover_image' ),
				),
			);

			// Set feedback messages.
			$script_data['feedback_messages'] = array(
				1 => __( 'Your new cover photo was uploaded successfully.', 'buddyboss' ),
				2 => __( 'There was a problem deleting your cover photo. Please try again.', 'buddyboss' ),
				3 => __( 'Your cover photo was deleted successfully.', 'buddyboss' ),
			);
		} elseif ( bp_is_group() ) {
			$item_id = bp_get_current_group_id();

			$script_data['bp_params'] = array(
				'object'            => 'group',
				'item_id'           => bp_get_current_group_id(),
				'has_cover_image'   => bp_attachments_get_group_has_cover_image( $item_id ),
				'has_default_class' => ( ! bp_disable_group_cover_image_uploads() && 'custom' !== bb_get_default_group_cover_type() ) ? 'has-default' : '',
				'nonces'            => array(
					'remove' => wp_create_nonce( 'bp_delete_cover_image' ),
				),
			);

			// Set feedback messages.
			$script_data['feedback_messages'] = array(
				1 => __( 'The group cover photo was uploaded successfully.', 'buddyboss' ),
				2 => __( 'There was a problem deleting the group cover photo. Please try again.', 'buddyboss' ),
				3 => __( 'The group cover photo was deleted successfully!', 'buddyboss' ),
			);
		} else {

			/**
			 * Filters the cover photo params to include specific BuddyPress params for your object.
			 * e.g. cover photo for blogs single item.
			 *
			 * @since BuddyPress 2.4.0
			 *
			 * @param array $value The cover photo specific BuddyPress parameters.
			 */
			$script_data['bp_params'] = apply_filters( 'bp_attachment_cover_image_params', array() );
		}

		// Include our specific js & css.
		$script_data['extra_js']  = array( 'bp-cover-image' );
		$script_data['extra_css'] = array( 'bp-avatar' );

		/**
		 * Filters the cover photo script data.
		 *
		 * @since BuddyPress 2.4.0
		 *
		 * @param array $script_data Array of data for the cover photo.
		 */
		return apply_filters( 'bp_attachments_cover_image_script_data', $script_data );
	}
}
