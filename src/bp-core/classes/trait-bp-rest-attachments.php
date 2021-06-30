<?php
/**
 * BP REST: Attachments Trait
 *
 * @package BuddyBoss
 * @since 0.1.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Attachments Trait
 *
 * @since 0.1.0
 */
trait BP_REST_Attachments {

	/**
	 * Cover upload from file.
	 *
	 * @since 0.1.0
	 *
	 * @param array $file $_FILES superglobal.
	 * @return string|WP_Error
	 */
	protected function upload_cover_from_file( $file ) {

		// Set global variables.
		$bp = buddypress();
		switch ( $this->object ) {
			case 'group':
				$bp->groups->current_group = $this->group;
				$bp->current_component     = 'groups';
				break;
			case 'user':
			default:
				$bp->displayed_user     = new stdClass();
				$bp->displayed_user->id = (int) $this->user->ID;
				break;
		}

		// Try to upload image.
		$uploaded_image = $this->attachment_instance->upload( $file );

		// Bail with error.
		if ( ! empty( $uploaded_image['error'] ) ) {
			return new WP_Error(
				"bp_rest_attachments_{$this->object}_cover_upload_error",
				sprintf(
					/* translators: %s: the upload error message */
					__( 'Upload Failed! Error was: %s', 'buddyboss' ),
					$uploaded_image['error']
				),
				array(
					'status' => 500,
					'reason' => 'upload_error',
				)
			);
		}

		$component                  = $this->get_cover_object_component();
		$item_id                    = $this->get_item_id();
		$bp_attachments_uploads_dir = bp_attachments_cover_image_upload_dir(
			array(
				'object_directory' => $component,
				'object_id'        => $item_id,
			)
		);

		// The BP Attachments Uploads Dir is not set, stop.
		if ( ! $bp_attachments_uploads_dir ) {
			return new WP_Error(
				"bp_rest_attachments_{$this->object}_cover_upload_error",
				__( 'The BuddyPress attachments uploads directory is not set.', 'buddyboss' ),
				array(
					'status' => 500,
					'reason' => 'attachments_upload_dir',
				)
			);
		}

		$cover_subdir = $bp_attachments_uploads_dir['subdir'];
		$cover_dir    = $bp_attachments_uploads_dir['basedir'] . $cover_subdir;

		// Create directory if not exists.
		if ( ! is_dir( $cover_dir ) ) {
			wp_mkdir_p( $cover_dir );
		}

		// If upload path doesn't exist, stop.
		if ( 1 === validate_file( $cover_dir ) || ! is_dir( $cover_dir ) ) {
			return new WP_Error(
				"bp_rest_attachments_{$this->object}_cover_upload_error",
				__( 'The cover image directory is not valid.', 'buddyboss' ),
				array(
					'status' => 500,
					'reason' => 'cover_image_dir',
				)
			);
		}

		// Added buddypress support.
		if ( isset( buddypress()->buddyboss ) && 'members' === $component ) {
			$component = 'xprofile';
		}

		// Upload cover.
		$cover = bp_attachments_cover_image_generate_file(
			array(
				'file'            => $uploaded_image['file'],
				'component'       => $component,
				'cover_image_dir' => $cover_dir,
			)
		);

		// Bail if any error happened.
		if ( false === $cover ) {
			return new WP_Error(
				"bp_rest_attachments_{$this->object}_cover_upload_error",
				__( 'There was a problem uploading the cover image.', 'buddyboss' ),
				array(
					'status' => 500,
					'reason' => 'unknown',
				)
			);
		}

		$warning = '';

		// Bail with error if too small.
		if ( true === $cover['is_too_small'] ) {

			// Get cover image advised dimensions.
			$cover_dimensions = bp_attachments_get_cover_image_dimensions( $component );
			$warning          = sprintf(
				/* translators: %$1s and %$2s is replaced with the correct sizes. */
				__( 'You have selected an image that is smaller than recommended. For better results, make sure to upload an image that is larger than %1$spx wide, and %2$spx tall.', 'buddyboss' ),
				(int) $cover_dimensions['width'],
				(int) $cover_dimensions['height']
			);
		}

		// Set the name of the file.
		$name       = $file['file']['name'];
		$name_parts = pathinfo( $name );
		$name       = trim( substr( $name, 0, - ( 1 + strlen( $name_parts['extension'] ) ) ) );

		$cover_url = sprintf(
			'%1$s%2$s%3$s',
			$bp_attachments_uploads_dir['baseurl'],
			trailingslashit( $cover_subdir ),
			$cover['cover_basename']
		);

		/**
		 * Fires if the new cover photo was successfully uploaded.
		 *
		 * The dynamic portion of the hook will be xprofile in case of a user's
		 * cover photo, groups in case of a group's cover photo. For instance:
		 * Use add_action( 'xprofile_cover_image_uploaded' ) to run your specific
		 * code once the user has set his cover photo.
		 *
		 * @param int    $item_id       Inform about the item id the cover photo was set for.
		 * @param string $name          Filename.
		 * @param string $cover_url     URL to the image.
		 * @param int    $feedback_code If value not 1, an error occured.
		 */
		do_action(
			$component . '_cover_image_uploaded',
			(int) $item_id,
			$name,
			$cover_url,
			1
		);

		return array(
			'cover'   => $cover_url,
			'warning' => $warning,
		);
	}

	/**
	 * Avatar upload from File.
	 *
	 * @since 0.1.0
	 *
	 * @param array $files $_FILES superglobal.
	 * @return stdClass|WP_Error
	 */
	protected function upload_avatar_from_file( $files ) {

		// Set global variables.
		$bp = buddypress();
		switch ( $this->object ) {
			case 'group':
				$bp->groups->current_group = $this->group;
				$upload_main_dir           = 'groups_avatar_upload_dir';
				break;
			case 'user':
			default:
				$upload_main_dir        = 'xprofile_avatar_upload_dir';
				$bp->displayed_user     = new stdClass();
				$bp->displayed_user->id = (int) $this->user->ID;
				break;
		}

		$avatar_attachment = $this->avatar_instance;
		$avatar_original   = $avatar_attachment->upload( $files, $upload_main_dir );

		// Bail early in case of an error.
		if ( ! empty( $avatar_original['error'] ) ) {
			return new WP_Error(
				"bp_rest_attachments_{$this->object}_avatar_upload_error",
				sprintf(
					/* translators: %s: the upload error message */
					__( 'Upload failed! Error was: %s.', 'buddyboss' ),
					$avatar_original['error']
				),
				array(
					'status' => 500,
					'reason' => 'upload_error',
				)
			);
		}

		// set crop true while uploading a avatar.
		add_filter( 'bp_after_attachment_avatar_edit_image_parse_args', array( $this, 'bp_rest_after_attachment_avatar_edit_image_parse_args' ), 10, 1 );

		// Get image and bail early if there is an error.
		$image_file = $this->resize( $avatar_original['file'] );

		remove_filter( 'bp_after_attachment_avatar_edit_image_parse_args', array( $this, 'bp_rest_after_attachment_avatar_edit_image_parse_args' ), 10, 1 );

		if ( is_wp_error( $image_file ) ) {
			return $image_file;
		}

		// If the uploaded image is smaller than the "full" dimensions, throw a warning.
		if ( $avatar_attachment->is_too_small( $image_file ) ) {
			$full_width  = bp_core_avatar_full_width();
			$full_height = bp_core_avatar_full_height();

			return new WP_Error(
				"bp_rest_attachments_{$this->object}_avatar_error",
				sprintf(
					/* translators: %1$s and %2$s is replaced with the correct sizes. */
					__( 'You have selected an image that is smaller than recommended. For best results, upload a picture larger than %1$s x %2$s pixels.', 'buddyboss' ),
					$full_width,
					$full_height
				),
				array(
					'status'     => 400,
					'reason'     => 'image_too_small',
					'min_width'  => $full_width,
					'min_height' => $full_height,
				)
			);
		}

		// Delete existing image if one exists.
		$this->delete_existing_image();

		// Crop the profile photo accordingly and bail early in case of an error.
		$cropped = $this->crop_image( $image_file );
		if ( is_wp_error( $cropped ) ) {
			return $cropped;
		}

		// Set the arguments for the avatar.
		$args = array();
		foreach ( array( 'full', 'thumb' ) as $key_type ) {

			// Update path with an url.
			$url = str_replace( bp_core_avatar_upload_path(), '', $cropped[ $key_type ] );

			// Set image url to its size/type.
			$args[ $key_type ] = bp_core_avatar_url() . $url;
		}

		// Build response object.
		$avatar_object = $this->get_avatar_object( $args );

		if ( file_exists( $avatar_original['file'] ) ) {
			unlink( $avatar_original['file'] );
		}

		return $avatar_object;
	}

	/**
	 * Resize image.
	 *
	 * @since 0.1.0
	 *
	 * @param string $file Image to resize.
	 * @return string|WP_Error
	 */
	protected function resize( $file ) {
		$bp          = buddypress();
		$upload_path = bp_core_avatar_upload_path();

		if ( ! isset( $bp->avatar_admin ) ) {
			$bp->avatar_admin = new stdClass();
		}

		// The Avatar UI available width.
		$ui_available_width = 0;

		// Try to set the ui_available_width using the avatar_admin global.
		if ( isset( $bp->avatar_admin->ui_available_width ) ) {
			$ui_available_width = $bp->avatar_admin->ui_available_width;
		}

		$resized = $this->avatar_instance->shrink( $file, $ui_available_width );

		// We only want to handle one image after resize.
		if ( empty( $resized ) ) {
			$image_file = $file;
			$img_dir    = str_replace( $upload_path, '', $file );
		} else {
			$image_file = $resized['path'];
			$img_dir    = str_replace( $upload_path, '', $resized['path'] );
			unlink( $file );
		}

		// Check for WP_Error on what should be an image.
		if ( is_wp_error( $img_dir ) ) {
			return new WP_Error(
				"bp_rest_attachments_{$this->object}_avatar_upload_error",
				sprintf(
					/* translators: %s: the upload error message */
					__( 'Upload failed! Error was: %s', 'buddyboss' ),
					$img_dir->get_error_message()
				),
				array(
					'status' => 500,
					'reason' => 'resize_error',
				)
			);
		}

		return $image_file;
	}

	/**
	 * Crop image.
	 *
	 * @since 0.1.0
	 *
	 * @param string $image_file Image to crop.
	 * @return array|WP_Error
	 */
	protected function crop_image( $image_file ) {
		$image          = getimagesize( $image_file );
		$avatar_to_crop = str_replace( bp_core_avatar_upload_path(), '', $image_file );

		// Get avatar full width and height.
		$full_height = bp_core_avatar_full_height();
		$full_width  = bp_core_avatar_full_width();

		// Use as much as possible of the image.
		$avatar_ratio = $full_width / $full_height;
		$image_ratio  = $image[0] / $image[1];

		if ( $image_ratio >= $avatar_ratio ) {
			// Uploaded image is wider than BP ratio, so we crop horizontally.
			$crop_y = 0;
			$crop_h = $image[1];

			// Get the target width by multiplying unmodified image height by target ratio.
			$crop_w    = $avatar_ratio * $image[1];
			$padding_w = round( ( $image[0] - $crop_w ) / 2 );
			$crop_x    = $padding_w;
		} else {
			// Uploaded image is narrower than BP ratio, so we crop vertically.
			$crop_x = 0;
			$crop_w = $image[0];

			// Get the target height by multiplying unmodified image width by target ratio.
			$crop_h    = $avatar_ratio * $image[0];
			$padding_h = round( ( $image[1] - $crop_h ) / 2 );
			$crop_y    = $padding_h;
		}

		add_filter( 'bp_attachments_current_user_can', '__return_true' );

		// Crop the image.
		$cropped = $this->avatar_instance->crop(
			array(
				'object'        => $this->object,
				'avatar_dir'    => ( 'group' === $this->object ) ? 'group-avatars' : 'avatars',
				'item_id'       => $this->get_item_id(),
				'original_file' => $avatar_to_crop,
				'crop_w'        => $crop_w,
				'crop_h'        => $crop_h,
				'crop_x'        => $crop_x,
				'crop_y'        => $crop_y,
			)
		);

		remove_filter( 'bp_attachments_current_user_can', '__return_false' );

		// Check for errors.
		if ( empty( $cropped['full'] ) || empty( $cropped['thumb'] ) || is_wp_error( $cropped['full'] ) || is_wp_error( $cropped['thumb'] ) ) {
			return new WP_Error(
				"bp_rest_attachments_{$this->object}_avatar_crop_error",
				sprintf(
					/* translators: %s is replaced with object type. */
					__( 'There was a problem cropping your %s photo.', 'buddyboss' ),
					$this->object
				),
				array(
					'status' => 500,
				)
			);
		}

		return $cropped;
	}

	/**
	 * Delete group's existing avatar if one exists.
	 *
	 * @since 0.1.0
	 */
	protected function delete_existing_image() {
		// Get existing avatar.
		$existing_avatar = bp_core_fetch_avatar(
			array(
				'object'  => $this->object,
				'item_id' => $this->get_item_id(),
				'html'    => false,
			)
		);

		// Check if the avatar exists before deleting.
		if ( ! empty( $existing_avatar ) ) {
			bp_core_delete_existing_avatar(
				array(
					'object'  => $this->object,
					'item_id' => $this->get_item_id(),
				)
			);
		}
	}

	/**
	 * Returns the avatar object.
	 *
	 * @since 0.1.0
	 *
	 * @param array $args {
	 *    An array of arguments to build the Avatar object.
	 *
	 *    @type string $full  The url to the full version of the avatar.
	 *    @type string $thumb The url to the thumb version of the avatar.
	 * }
	 * @return object The avatar object.
	 */
	protected function get_avatar_object( $args = array() ) {
		$avatar_object = array_intersect_key(
			$args,
			array(
				'full'  => '',
				'thumb' => '',
			)
		);

		return (object) $avatar_object;
	}

	/**
	 * Get item id.
	 *
	 * @since 0.1.0
	 *
	 * @return int
	 */
	protected function get_item_id() {
		return ( 'group' === $this->object ) ? $this->group->id : $this->user->ID;
	}

	/**
	 * Get cover object component.
	 *
	 * @since 0.1.0
	 *
	 * @return string
	 */
	protected function get_cover_object_component() {
		return ( 'group' === $this->object ) ? 'groups' : 'members';
	}

	/**
	 * Set crop true while uploading a image.
	 *
	 * @since 0.1.0
	 *
	 * @param array $args Argument Parameter to force crop.
	 *
	 * @return array
	 */
	public function bp_rest_after_attachment_avatar_edit_image_parse_args( $args ) {
		if ( array_key_exists( 'crop', $args ) ) {
			$args['crop'] = true;
		}
		return $args;
	}
}
